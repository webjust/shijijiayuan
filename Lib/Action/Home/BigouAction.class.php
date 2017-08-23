<?php

/**
 * Class BigouAction 51�ȹ���Ϣ
 */
class BigouAction extends GyfxAction{
    private $key ;//Ҫô�����ݿ�����ɶ�ĵõ�
    private $bid ;//�̻�id
    private $rate ;//�������
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

    //�û���¼��֤
    public function cpsRecord(){
        $arr_request = $this->_request();
        $channelid = $arr_request['channelid'] ;
        $this->u_id = $arr_request['u_id'];
        $url = $arr_request['url'];//��utf-8 �����
        $syncname = $arr_request['syncname'];//�Ƿ����ϵ�½
        if(empty($channelid) || empty( $this->u_id) || empty($url)){
            echo json_encode(array('404'=>'��������!'));
            exit;
        }
        if(isset($_COOKIE['channel_id']) ||!empty($_COOKIE['channel_id']) || isset($_COOKIE['u_id']) || !empty($_COOKIE['u_id'])){
            cookie('channel_id','',time() -1 );
            cookie('u_id','',time() -1 );
        }
        cookie('channel_id',$channelid,time() + 60*1000*30);//30����
        cookie('u_id',$this->u_id,time() + 60*1000*30);
        $url = urldecode($url);
        if(empty($syncname) || $syncname === false){
            header('location:'.$url);
        }else{//���ϵ�½
            $action_time = $arr_request['action_time'];
            $this->username = $arr_request['username'];
            $this->usersafekey = $arr_request['usersafekey'];
            $password = rand(100000,999999);
            $this->password = md5($password);
            $this->email = $arr_request['email'];
            $code = $arr_request['code'];
            if((time() - $action_time ) > 60*1000*15){//��������15����ʧЧ
                header('location:'.$url);
            }
            if(md5($this->username.$this->key.$action_time) !== $code){//code��֤���ɹ�
                header('location:'.$url);
            }
            //����û��ʧЧ
            //��֤username�Ƿ����
            $this->member = D('Members');
            $result = $this->member->where(array('m_name'=>$this->username))->find();
            if(empty($result)){//�û������� �����û���Ϣ�Լ���ȫ��
                $is_add = $this->addMember();
                if(!($is_add)) {
                    echo json_encode(array('404'=>'���ϵ�½ʧ��!'));
                    exit;
                };
                $this->changestate($this->addmemberinfo);
            }else{
                $union_data = $result['union_data'];
                $union_data = json_decode($union_data,true);
                if($this->usersafekey != $union_data['usersafekey']){
                    echo  json_encode(array('404'=>'���ϵ�½ʧ��!'));
                    exit;
                }
                $this->changestate($result);
            }
            header('location:'.$url);
        }
    }
    //���ڷ������̳� ��Ҫ��ƴ�Ӹ��ֲ��� Ȼ���û���� oyeah
    /**
     * @param $order order �����Ϣ ����˵ֱ�Ӱ�order����������� �ӵ���������
     * ��select��õĽӿ�
     */
    public function orderback($order){
        if(!isset($_COOKIE['channel_id']) || empty($_COOKIE['channel_id']) || $_COOKIE['channel_id'] != '51bi'){
            echo json_encode(array('403'=>'��������')); exit;
        }
        if(!isset($_COOKIE['u_id']) || empty($_COOKIE['u_id'])){
            echo json_encode(array('403'=>'��������')); exit;
        }
        $u_id = $_COOKIE['u_id'];
        if(empty($order)){
            echo json_encode(array('403'=>'��������'));exit;
        }

        $params = array();
        $params['oid'] = $order[0]['o_id'];//������
        $params['cost'] = sprintf('%.2f',$order[0]['o_goods_all_price']);//�����ܽ�� ������λС��
        $params['ordertime'] = $order[0]['o_create_time'];
        $params['ip'] = $order[0]['o_ip'];

        //�����û�mid
        $m_id = $order['m_id'];
        //����û��Ƿ������û��µ�
        $count = M('Orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->count();
        if($count ==1 ){
            $params['newuser'] = 1;//���¿��µ�
        }else{
            $params['newuser'] = 0;//�����¿��µ�
        }
        $info = $this->get_orders_info($order,'||','');

        //���� ���ظ�51bi��Ӷ�� С���������λС�� ͬͳһӶ������̼ҿ��Բ������Ӷ��
        $order['cback'] = $params['cost'] * $this->rate;
        $params['mcode'] = md5($u_id.$order['oid']);

        //����׼�����
        $url = 'http://www.51bi.com/getProductInfo.jhtml?bid='.$this->bid.'&uid='.$u_id.'&oid='.$params['o_id'].'&cost='.$params['cost'].'&cback='.$params['cback'].'&ordertime='.urlencode($params['ordertime']).'&ip='.$params['ip'].'&newuser='.$params['newuser'].'&cates='.$info['cates'].'&comms='.$info['comms'].'&pp='.$info['pp'].'&mcode='.$params['mcode'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);    // ������׼���ύ��URL
        curl_setopt($curl, CURLOPT_GET, true);  // ����POST��ʽ�ύ

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//�ж��Ƿ���շ���ֵ��0�������գ�1������
        $data = curl_exec($curl); // ����curl��������ҳ, ����$dataΪ�ӿڷ�������
        curl_close($curl);        // �ر�curl����

    }

    //������ѯ
    public function queryData(){
        $data = $this->_request();
        if(empty($data['unionid'])){
            echo json_encode(array('status'=>'404','message'=>'����дunionid'));exit;
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
        //query��ʶ�Ƿ� time����ʽ
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

    //����û�
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
            'm_verify' => 1,//�Ƿ���� 1-�����
            'login_type' => 1,
            'open_source'=>'51bi'
        );
//        $this->m_id = $this->member->add($this->addmemberinfo);// ��ӵ�member������
        $this->m_id = $this->member->data($this->addmemberinfo)->add();// ��ӵ�member������
        $this->addmemberinfo['m_id'] = $this->m_id;
        return empty($this->m_id) ? false : true;
    }

    //����û���¼��Ϣ
    protected function changestate($info)
    {
        session('Members', $info);
        //���û���Ϣ����memcache����ȥstart
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
            $items[$i]['comm'] =  sprintf('%.2f',$value['o_goods_all_price']*$this->rate);;//��Ӷ��
            /*
            $info = $OrdersItems->field('gt_id,sum(oi_price) as price')->where(array('o_id'=>$value['o_id']))->group('gt_id')->select();
            foreach($info as $kk =>$vv){
                $items[$i]['cates'] .=$vv['gt_id'].$sign;//��Ʒ�ķ���  ��Ʒ����id
                $items[$i]['price'] .=sprintf('%.2f',$vv['price']).$sign;// ÿ����Ʒ�ļ۸�
                $items[$i]['percomm'] .=sprintf('%.2f',$vv['price'] * $this->rate).$sign;//ĳ����Ʒ��Ӷ��
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