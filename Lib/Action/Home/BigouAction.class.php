<?php

/**
 * Class BigouAction 51比购信息
 */
class BigouAction extends GyfxAction{
    private $key ;//要么从数据看或者啥的得到
    private $bid ;//商户id
    private $rate ;//返利金额
    private $channelid;
    private $u_id ;
    private $m_id;
    private $url;
    private $code;
    private $action_time;
    private $username;
    private $password;
    private $usersafekey;
    private $email;
    private $member;
    private $addmemberinfo;

    public function __construct() {
        parent::__construct();
        $is_on = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');
        if(empty($is_on) || $is_on == '0'){
            header('location:'.$this->_request('url'));
            exit;
        }
        $this->key= D('SysConfig')->getConfigValueBySckey('BIGOU_SHOPKEY','CPS_SET');
        $this->bid = D('SysConfig')->getConfigValueBySckey('BIGOU_SID','CPS_SET');
        $this->rate =  D('SysConfig')->getConfigValueBySckey('BIGOU_RATE','CPS_SET');

    }

    //用户登录验证
    public function cpsRecord(){
        $arr_request = $this->_request();
        $channelid = $arr_request['channelid'] ;
        $this->u_id = $arr_request['u_id'];
        $url = $arr_request['url'];//以utf-8 编码过
        $syncname = $arr_request['syncname'];//是否联合登陆
        if(empty($channelid) || empty( $this->u_id) || empty($url)){
            echo json_encode(array('404'=>'参数错误!'));
            exit;
        }
        if(isset($_COOKIE['channel_id']) ||!empty($_COOKIE['channel_id']) || isset($_COOKIE['u_id']) || !empty($_COOKIE['u_id'])){
            cookie('channel_id','',time() -1 );
            cookie('u_id','',time() -1 );
        }
        cookie('channel_id',$channelid,time() + 60*1000*30);//30分钟
        cookie('u_id',$this->u_id,time() + 60*1000*30);
        $url = urldecode($url);
        if(empty($syncname) || $syncname === false){
            header('location:'.$url);
        }else{//联合登陆
            $action_time = $arr_request['action_time'];
            $this->username = $arr_request['username'];
            $this->usersafekey = $arr_request['usersafekey'];
            $password = rand(100000,999999);
            $this->password = md5($password);
            $this->email = $arr_request['email'];
            $code = $arr_request['code'];
            if((time() - $action_time ) > 60*1000*15){//联合链接15分钟失效
                header('location:'.$url);
            }
            if(md5($this->username.$this->key.$action_time) !== $code){//code验证不成功
                header('location:'.$url);
            }
            //链接没有失效
            //验证username是否存在
            $this->member = D('Members');
            $result = $this->member->where(array('m_name'=>$this->username))->find();
            if(empty($result)){//用户不存在 保存用户信息以及安全码
                $is_add = $this->addMember();
                if(!($is_add)) {
                    echo json_encode(array('404'=>'联合登陆失败!'));
                    exit;
                };
                $this->changestate($this->addmemberinfo);
            }else{
                $union_data = $result['union_data'];
                $union_data = json_decode($union_data,true);
                if($this->usersafekey != $union_data['usersafekey']){
                    echo  json_encode(array('404'=>'联合登陆失败!'));
                    exit;
                }
                $this->changestate($result);
            }
            header('location:'.$url);
        }
    }
    //用于返利给商城 主要是拼接各种参数 然后就没有了 oyeah
    /**
     * @param $order order 相关信息 按我说直接把order表里面的数据 扔到里面算了
     * 用select获得的接口
     */
    public function orderback($order){
        if(!isset($_COOKIE['channel_id']) || empty($_COOKIE['channel_id']) || $_COOKIE['channel_id'] != '51bi'){
            echo json_encode(array('403'=>'参数错误！')); exit;
        }
        if(!isset($_COOKIE['u_id']) || empty($_COOKIE['u_id'])){
            echo json_encode(array('403'=>'参数错误！')); exit;
        }
        $u_id = $_COOKIE['u_id'];
        if(empty($order)){
            echo json_encode(array('403'=>'参数错误！'));exit;
        }

        $params = array();
        $params['oid'] = $order[0]['o_id'];//订单号
        $params['cost'] = sprintf('%.2f',$order[0]['o_goods_all_price']);//订单总金额 保留两位小数
        $params['ordertime'] = $order[0]['o_create_time'];
        $params['ip'] = $order[0]['o_ip'];

        //订单用户mid
        $m_id = $order['m_id'];
        //检查用户是否是新用户下单
        $count = M('Orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->count();
        if($count ==1 ){
            $params['newuser'] = 1;//是新客下单
        }else{
            $params['newuser'] = 0;//不是新客下单
        }
        $info = $this->get_orders_info($order,'||','');

        //计算 返回给51bi的佣金 小数点后保留两位小数 同统一佣金比例商家可以不计算此佣金
        $order['cback'] = $params['cost'] * $this->rate;
        $params['mcode'] = md5($u_id.$order['oid']);

        //参数准备完毕
        $url = 'http://www.51bi.com/getProductInfo.jhtml?bid='.$this->bid.'&uid='.$u_id.'&oid='.$params['o_id'].'&cost='.$params['cost'].'&cback='.$params['cback'].'&ordertime='.urlencode($params['ordertime']).'&ip='.$params['ip'].'&newuser='.$params['newuser'].'&cates='.$info['cates'].'&comms='.$info['comms'].'&pp='.$info['pp'].'&mcode='.$params['mcode'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);    // 设置你准备提交的URL
        curl_setopt($curl, CURLOPT_GET, true);  // 设置POST方式提交

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//判断是否接收返回值，0：不接收，1：接收
        $data = curl_exec($curl); // 运行curl，请求网页, 其中$data为接口返回内容
        curl_close($curl);        // 关闭curl请求

    }

    //订单查询
    public function queryData(){
        $data = $this->_request();
        if(empty($data['unionid'])){
            echo json_encode(array('status'=>'404','message'=>'请填写unionid'));exit;
        }

        $where['channel_id'] = $data['unionid'] ;

        if(!empty($data['sdate']) && empty($data['edate'])){
            $where['o_create_time'] = array('gt', str_replace('%20',' ',$data['sdate']));
        }

        if(!empty($data['sdate']) && empty($data['edate'])){
            $where['o_create_time'] = array('lt', str_replace('%20',' ',$data['edate']));
        }

        if(!empty($data['sdate']) && !empty($data['edate'])){
            $where['o_create_time'] = array('between',array(str_replace('%20',' ',$data['sdate']),str_replace('%20',' ',$data['edate'])));
        }

        $orders = D('Orders')->where($where)->select();
        //query标识是否 time的样式
        $items = $this->get_orders_info($orders,',','query');
        foreach($items as  $key => $value){
            $info = '';
            foreach($value as $kk => $vv){
                if(empty($vv)){
                    $info.='_'.'|';
                }else{
                    $info .=$vv.'|';
                }
            }
//            $info = rtrim( $info ,'|');
            $info .='_|';
            echo $info;

        }
    }

    //添加用户
    protected function addMember()
    {
        $this->password = rand(100000, 999999);
        $this->password = md5($this->password);
        $union_data = array('u_id' => $this->u_id, 'usersafekey' => $this->usersafekey,'username'=>$this->username);
        $union_data = json_encode($union_data);

        $this->addmemberinfo = array(
            'union_data' =>$union_data ,
            'm_name' => $this->username ,
            'm_password' => $this->password ,
            'm_email' => $this->email ,
            'm_status' => 1,
            'm_verify' => 1,//是否审核 1-审核了
            'login_type' => 1,
            'open_source'=>'51bi'
        );
//        $this->m_id = $this->member->add($this->addmemberinfo);// 添加到member表里面
        $this->m_id = $this->member->data($this->addmemberinfo)->add();// 添加到member表里面
        $this->addmemberinfo['m_id'] = $this->m_id;
        return empty($this->m_id) ? false : true;
    }

    //添加用户登录信息
    protected function changestate($info)
    {
        session('Members', $info);
        //把用户信息存在memcache里面去start
        $uniqid = md5(uniqid(microtime()));
        writeMemberCache($uniqid, $info);
        cookie('session_mid', $uniqid, 3600);
    }

    protected function get_orders_info($orders, $sign ,$query ){
        $items = array();
        $OrdersItems = M('OrdersItems',C('DB_PREFIX'),'DB_CUSTOM');
        $i = 0;
        foreach($orders as $key =>$value){
            if($query == 'query'){
                $items[$i]['otime'] =   date('YmdHis',strtotime($value['o_create_time']));
            }else{
                $items[$i]['otime'] =$value['o_create_time'];
            }
            $items[$i]['o_cd'] = $value['o_id'];

            $channel_related_info  = json_decode($value['channel_related_info'],true);
            $items[$i]['u_id'] = $channel_related_info['u_id'];

            $items[$i]['topprice'] = sprintf('%.2f',$value['o_goods_all_price']) ;
            $items[$i]['comm'] =  sprintf('%.2f',$value['o_goods_all_price']*$this->rate);;//总佣金
            /*
            $info = $OrdersItems->field('gt_id,sum(oi_price) as price')->where(array('o_id'=>$value['o_id']))->group('gt_id')->select();
            foreach($info as $kk =>$vv){
                $items[$i]['cates'] .=$vv['gt_id'].$sign;//商品的分类  商品类型id
                $items[$i]['price'] .=sprintf('%.2f',$vv['price']).$sign;// 每类商品的价格
                $items[$i]['percomm'] .=sprintf('%.2f',$vv['price'] * $this->rate).$sign;//某类商品的佣金
            }
            $items[$i]['cates'] = rtrim(  $items[$i]['cates'],$sign);
            $items[$i]['price'] = rtrim($items[$i]['price'] ,$sign);
            $items[$i]['percomm'] = rtrim($items[$i]['percomm'],$sign);
*/
            $i++;
        }
        return $items;

    }
}