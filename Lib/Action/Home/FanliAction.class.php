<?php
/**
 * Created by PhpStorm.
 * User: chelsea
 * Date: 2015/6/23
 * Time: 15:58
 * ������cps
 */
class FanliAction extends GyfxAction
{
    private $shop_key ;//�������ṩ���ϴ�ʶ����
    private $s_id;//�����̼��ڷ������ı��,�������ṩ
    private $shop_no ;
    private $rate_fanli ;
    private $member_info;//�û���Ϣ ����ƽ̨������û���Ϣ
    private $addmemberinfo;
    private $usersafekey;
    private $username;
    private $u_id;
    private $password;
    private $email;
    private $member;//D('Members')
    //  private $syncaddress;//�û���Ϣ ����ƽ̨������û���Ϣ
    private $name;
//    private $province;
//    private $city;
//    private $area;
//    private $address;
//    private $zip;
//    private $phone;
//    private $mobile;
    private $m_id;//��Աid
    private $tracking_code;
    public function __construct() {
        parent::__construct();
        $is_on = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');
        if(empty($is_on) || $is_on == '0'){
            header('location:'.$this->_request('url'));
            exit;
        }
        $this->shop_key = D('SysConfig')->getConfigValueBySckey('FANLI_SHOPKEY','CPS_SET');
        $this->s_id = D('SysConfig')->getConfigValueBySckey('FANLI_SID','CPS_SET');
        $this->shop_no = D('SysConfig')->getConfigValueBySckey('FANLI_SHOPKEY','CPS_SET');
        $this->rate_fanli =  D('SysConfig')->getConfigValueBySckey('FANLI_RATE','CPS_SET');

    }

    //������� ���ϵ�½ �������� �ջ���ַ
    public function Login()
    {
        $data = $this->_request();
        $channel_id = $data['channel_id'];
        $this->u_id = $data['u_id'];//�ӷ������������û�u_id
        $target_url = $data['target_url'];
        $this->tracking_code = $data['tracking_code'];
        $code = $data['code'];
        $syncname = $data['syncname'];
        $this->username = $data['username'];
        $this->usersafekey = $data['usersafekey'];
        $action_time = $data['action_time'];
        $this->email = $data['email'];
//        $this->syncaddress = $data['syncaddress'];
        $this->name =   urldecode($data['name']);
//        $this->province =  urldecode($data['province']);;
//        $this->city = str_replace(' ','',urldecode($data['city']))   ;
//        $this->area = str_replace(' ','',urldecode($data['area']));
//        $this->address = str_replace(' ','',urldecode($data['address']));
//        $this->zip = $data['zip'];
//        $this->phone = $data['phone'];
//        $this->mobile = $data['mobile'];

        if(isset($_COOKIE['channel_id']) ||!empty($_COOKIE['channel_id']) ){//ÿ�ζ������µ�channel_idд�뵽cookie����
            cookie('channel_id', '');
            cookie('u_id', '' );
            cookie('tracking_code', '');
        }
        cookie('channel_id', $channel_id, 86400*30);//30��
        cookie('u_id', $this->u_id, 86400*30);
        cookie('tracking_code', $this->tracking_code, 86400*30);

        if ($syncname != true) {//�������ϵ�½
            header('location:' . $target_url);exit;
        }
        //�������ϵ�½ ��֤ʱ����� ��֤code����
        /*
         *  ���Ե�ʱ����ȥ����action_time����֤
         */

        if ((time() - $action_time) > 60*15) {//��������15����ʧЧ
            header('location:' . $target_url);exit;
        }

        if (md5($this->username . $this->shop_key . $action_time) !== $code) {//code��֤���ɹ�
            header('location:' . $target_url);exit;
        }

        //��֤username�Ƿ����
        $this->member = D('Members');
        $this->member_info  = D('Gyfx')->selectOneCache('members',null, array('m_name' => $this->username));

//        $this->member_info = $this->member->where(array('m_name' => $this->username))->find();
        if (!empty($this->member_info)) {//�û����� �ж�usersafekey
            if($this->verify_usersafekey()){//��֤safekey��Ϣ safekey ��֤�ɹ�
                if(empty($_SESSION['Members'])){
                    $this->changestate($this->member_info);
                }
            }
            header('location:' . $target_url);exit;
        } else {//�û������� ����û� �����ж��Ƿ���Ҫͬ��
            //����û� �ɹ��ı��û���¼״̬
            //$this->addMember() ? $this->changestate($this->addmemberinfo) : header('location:' . $target_url);
            if($this->addMember()){
                $this->changestate($this->addmemberinfo);
            }
//            $this->sync_user_address();
            header('location:'.$target_url); exit;
        }
    }


    //��ѯ��������
    public function QueryData(){
        $arr = $this->_request();
        $where = $this->getcondition($arr);
        $orders = D('Orders')->where($where)->select();
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
                        <orders version="4.0">';
        $code = $this->shop_no.$this->shop_key;
        $code = strtolower($code);
        $code = md5($code);
        $xmlStr =  $this->getOrdersXml($orders,$xmlStr,$code);
        $xmlStr.='</orders>';
        header("Content-type: text/xml");
        echo $xmlStr;
    }

    //������
    public function Superfanli(){
        $data = $this->_request();
        $product_id = $data['id'];
        $timestamp = $data['timestamp'];
        $sign = $data['sign'];
        if(empty($product_id) || empty($timestamp) || empty($sign)){
            echo json_encode(array('status'=>404 ,'message'=>'��������'));
            exit;
        }

        if(strtoupper(md5($product_id.$timestamp.$this->shop_key)) != $sign){
            echo json_encode(array('status'=>404 ,'message'=>'sign��֤����'));
            exit;
        }
        $state = $this->getStates($product_id);
        $good_info = D('GoodsInfo')->where(array('g_id'=>$product_id))->find();
        $good_url  ='/Home/Products/detail/gid/'.$good_info['g_id'];
        $wap_good_url = '';
        if($good_info['mobile_show'] == 1){
            $wap_good_url = '/Wap/Products/detail/gid/'.$good_info['g_id'];
        }

        preg_match_all('/<img.*?src="(.*?)".*?>/is',$good_info['g_desc'],$src);

        if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
            if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
                $_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_TPL_IP'];
            }
        }

        if( empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
            $domain = $_SERVER['HTTP_HOST'];
        }else{
            $domain = $_SESSION['OSS']['GY_OSS_PIC_URL'];
        }
        foreach($src[1] as $key =>$value){
            $good_info['g_desc'] = str_replace($value,'http://'.$domain.$value,$good_info['g_desc']);
        }
        $info = '<?xml version="1.0" encoding="utf-8"?>
                    <item>
                    <id>'.$good_info['g_id'].'</id>
                    <title><![CDATA['.$good_info['g_name'].']]></title>
                    <url><![CDATA[http://'. $_SERVER['HTTP_HOST'].$good_url.']]></url>
                    <url_wap><![CDATA[http://'.$_SERVER['HTTP_HOST'].$wap_good_url.']]></url_wap>
                    <price>'.$good_info['g_price'].'</price>
                    <wap_price>'.$good_info['g_price'].'</wap_price>
                    <promotion_price>'.$good_info['g_price'].'</promotion_price>
                    <detail><![CDATA['.$good_info['g_desc'].']]></detail>
                    <status>'.$state.'</status>
                    <pic_main>
                        <img>
                            <url>http://'.$domain.$good_info['g_picture'].'</url>
                            <size>420*420</size>
                        </img>
                    </pic_main>';
        $GoodsPictures = D('GoodsPictures');
        $good_pics = $GoodsPictures->where(array('g_id'=>$product_id,'gp_status'=>1))->select();
        $info .='<pic_extra>';

        if(!empty($good_pics)){
            foreach($good_pics as $key => $value){
                $pic = explode('Public',$value['gp_picture']);
                $info .='<img>
                            <url><![CDATA[http://'.$domain.'/Public'.$pic[1].']]></url>
                            <size>420*420</size>
                        </img>';
            }
        }

        $info .='</pic_extra>';
        $info .='</item>';
        header("Content-type: text/xml");
        echo $info;
        exit;

    }

    //����û�
    protected function addMember()
    {
        $this->password = rand(100000, 999999);
        $this->password = md5($this->password);
        $union_data = array('u_id' => $this->u_id, 'usersafekey' => $this->usersafekey,'username'=>$this->username);
        $union_data = json_encode($union_data);
        $this->addmemberinfo = array(
            'm_name' => $this->username,
            'm_password' => $this->password,
            'm_email' => $this->email,
            'm_status' => 1,
            'm_verify' => 1,//�Ƿ���� 1-�����
            'login_type' => 1,
            'open_source'=>'51fanli',
            'union_data' => $union_data
        );
        $this->m_id = $this->member->add($this->addmemberinfo);// ��ӵ�member������
        $this->addmemberinfo['m_id'] = $this->m_id;
        return empty($this->m_id) ? false : true;
    }

    //��֤uersafekey
    protected   function verify_usersafekey()
    {
        $userdata = json_decode($this->member_info['union_data'], true);
        $safekey = $userdata['usersafekey'];
        if ($this->usersafekey === $safekey) return true;
        return false;
    }

    //����û���¼��Ϣ
    protected function changestate($info)
    {
        session('Members', $info);
        //���û���Ϣ����memcache����ȥstart
        $uniqid = md5(uniqid(microtime()));
        writeMemberCache($uniqid, $info);
        cookie('session_mid', $uniqid, 3600);
        unset($info);
    }
    // ͬ���ջ���ַ��Ϣ
    protected function sync_user_address()
    {
        if (!$this->syncaddress) return false;//����Ҫͬ����ַ
        $CityRegion = D('CityRegion');
        $cr_id = $CityRegion->getAvailableLogisticsList(mb_convert_encoding($this->province,'utf-8','GBK'),mb_convert_encoding($this->city,'utf-8','GBK'), mb_convert_encoding($this->area,'utf-8','GBK'));
        if(empty($cr_id) || $cr_id==0) return false;
        $address = array(
            'cr_id' => $cr_id,
            'm_id' => $this->m_id,
            'ra_name' => mb_convert_encoding($this->name,'utf-8','GBK'),
            'ra_detail' =>  mb_convert_encoding($this->address,'utf-8','GBK'),
            'ra_post_code' => (string)$this->zip,
            'ra_phone' =>(string)encrypt( $this->phone),
            'ra_mobile_phone' => (string)encrypt($this->mobile),
            'ra_create_time' => date('Y-m-d H:i:s')
        );
        $ReceiveAddress = D('ReceiveAddress');
        $address_id =  $ReceiveAddress->add($address);

        if(empty($address_id))  return false ;
        return true;
    }
    //ƴ�Ӷ�����xml
    protected function getOrdersXml($orders,$xmlStr,$code){
        $OrdersItems = M('OrdersItems',C('DB_PREFIX'),'DB_CUSTOM');
        foreach($orders as $key => $value){
            $data = $value['channel_related_info'];
            $data = json_decode($data,true);
            $params = array();
            //����û��Ƿ������û��µ�
            $count = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$value['m_id']))->count();
            if($count ==1 ){
                $params['newuser'] = 1;//���¿��µ�
            }else{
                $params['newuser'] = 0;//�����¿��µ�
            }
            if($value['o_source']==='pc') {
                $params['platform'] = 1;//����Դ��pc
            }else{
                $params['platform'] = 2;//����Դ��wap �ֻ���
            }
            if($value['initial_o_id']==0){
                $value['initial_o_id'] = $value['o_id'];
            }
            $status = $this->get_status($value);
            $orderxml = ' <order>
                                <s_id>'.$this->s_id.'</s_id>
                                <order_id_parent>'.$value['initial_o_id'].'</order_id_parent>
                                <order_id>'.$value['o_id'].'</order_id>
                                <order_time>'.$value["o_create_time"].'</order_time>
                                <uid>'.$data['u_id'].'</uid>
                                <uname>'.$data['username'].'</uname>
                                <tc>'.$data['tracking_code'].'</tc><pay_time></pay_time>
                                <status>'.$status.'</status><locked></locked>
                                <lastmod>'.$value['o_update_time'].'</lastmod>
                                <is_newbuyer>'.$params['newuser'].'</is_newbuyer>
                                <platform>'.$params['platform'].'</platform><remark></remark>
                                <code>'.$code.'</code>
                                <products>';
            $xmlStr .= $orderxml;
            $info = $OrdersItems->field('g_id,oi_price,g_sn,oi_g_name,gt_id,g_id,pdt_sale_price,oi_nums,oi_coupon_menoy,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money,oi_balance_money,promotion_price')->where(array('o_id'=>$value['o_id']))->select();
            $xmlStr .=  $this->getProductsXml($info);
            $xmlStr .='</products></order>';
        }
        return $xmlStr;
    }

    //ƴ����Ʒ��xml
    protected function getProductsXml($info){
        $productsxml = '';
        foreach($info as $kk =>$vv){
            $items['pid'] = $vv['g_id'];//��Ʒ���
            $items['title'] = $vv['oi_g_name'];//��Ʒ����
            $items['category'] = $vv['gt_id'];//��Ʒ����
            $items['url'] = $_SERVER['HTTP_HOST'].'/Home/Products/detail?gid='.$vv['g_id'];
            $items['oi_nums'] = $vv['oi_nums'];
            $items['price'] = $vv['oi_price'] ;
            $items['real_pay_fee'] = $items['oi_nums'] * $items['price'] - $vv['oi_coupon_menoy']-$vv['oi_bonus_money']-$vv['oi_cards_money']-$vv['oi_jlb_money']-$vv['oi_point_money']-$vv['oi_balance_money']-$vv['promotion_price'];
            $items['commission'] = $items['real_pay_fee'] * $this->rate_fanli;
            $items['real_pay_fee'] = sprintf('%.2f',$items['real_pay_fee']);
            $items['commission'] = sprintf('%.2f',$items['commission']);
            $items['price'] = sprintf('%.2f',$items['price']);
            $items['comm_type'] = 'A';
            $productsxml  .= '<product>
                                <pid>'.$items['pid'].'</pid>
                                <title><![CDATA['.$items['title'].']]></title>
                                <category>'.$items['category'].'</category><category_title><![CDATA[]]></category_title>
                                <url><![CDATA['.$items['url'].']]></url>
                                <num>'.$items['oi_nums'].'</num>
                                <price>'.$items['price'].'</price>
                                <real_pay_fee>'.$items['real_pay_fee'].'</real_pay_fee><refund_num></refund_num>
                                <commission>'.$items['commission'].'</commission>
                                <comm_type>'.$items['comm_type'].'</comm_type>
                           </product>';
        }
        return $productsxml;
    }

    public function getcondition($arr){
        $where = array();
        if(!empty($arr['channel_id'])){
            $where['channel_id'] = $arr['channel_id'];
        }
//        $where['channel_id']='51fanli';
        if(!empty($arr['begin_date']) && empty($arr['end_date'])){
            $where['o_create_time'] = array('gt', str_replace('%20',' ',$arr['begin_date']));
        }

        if(!empty($arr['end_date']) && empty($arr['begin_date'])){
            $where['o_create_time'] = array('lt', str_replace('%20',' ',$arr['end_date']));
        }

        if(!empty($arr['end_date']) && !empty($arr['begin_date'])){
            $where['o_create_time'] = array('between',array(str_replace('%20',' ',$arr['begin_date']),str_replace('%20',' ',$arr['end_date'])));
        }

        if(!empty($arr['order_id'])){
            $where['o_id'] = $arr['order_id'];
        }

        if(!empty($arr['status'])){
            switch ($arr['status']){
                case '1' ://���¶���
                    $where['o_status'] = 1;
                    break;
                case '2' ;//�޸Ķ���
                    $where['o_status'] = 2 ;
                    $where['_string'] = 'o_update_time > o_create_time';
                    break;
                case '3' ://�����Ѹ���
                    $where['o_pay_status'] = 1;
                    break;
                case '4' ;//����  ���� δ�����˿� ���Ѻ��˿��⼸��״̬ �ڹ������涼û�� ���и���6 ���ص����ݻ�Ϊ��
                    $where['o_status'] = 6 ;
                    break;
                case '5' ://���ջ�
                    $where['o_status'] = 5;
                    break;
                case '6' ;//δ�����˿�
                    $where['o_status'] = 6 ;
                    break;
                case '7'://���Ѻ��˿�
                    $where['o_status'] = 6;
                    break;
            };
        }
        return $where;
    }
    //��ȡ״̬��Ϣ
    protected function get_status($order){
        if(empty($order['o_update_time'])){
            return 1 ;//���¶���
        }
        if(!empty($order['o_update_time'])){
            return 2 ;//�޸Ķ���
        }
        if($order['o_pay_status'] == 1){
            return 3 ;//�����Ѿ�֧��
        }
        if($order['o_status'] == 5){
            return 5 ;//���ջ�
        }

    }

    private function getStates($product_id){
        $good =  D('Goods')->where(array('g_id'=>$product_id))->find();
        $GoodsProducts = D('GoodsProducts')->where(array('g_id'=>$product_id))->find();
        if($good['g_on_sale'] == 1){//1-�ڼ� 2-�¼� 3-Ԥ�ϼ�
            return 1;//�ϼ�
        }
        if($good['g_on_sale']==2){
            return 0 ;//�¼�
        }
        if($good['g_pre_sale_status'] == 1){//Ԥ��
            return 3;//Ԥ��
        }
        if($GoodsProducts['pdt_stock'] ==0 ){
            return 4;//ȱ��
        }
    }


}
