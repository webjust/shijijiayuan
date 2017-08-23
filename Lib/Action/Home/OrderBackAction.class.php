<?php
class OrderBackAction extends Action{
    private $key_51bi = 'c6a8cf832f600001e2e019c017808ac0';//Ҫô�����ݿ�����ɶ�ĵõ�
    private $bid_51bi = 59735137;//�̻�id
    private $rate_51bi = 0.04;//�������

    private $shop_key_fanli = 'f50346969b92de72';//�������ṩ���ϴ�ʶ����
    private $s_id_fanli = 1693;//�����̼��ڷ������ı��,�������ṩ
    private $shop_no_fanli = 'shoprobam';
    private $rate_fanli = 0.04;//�������




    /**
     * 51bigou ��������
     * @param $order order �����Ϣ
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
        $params['oid'] = $order['o_id'];//������
        $params['cost'] = sprintf('%.2f',$order['o_all_price']);//�����ܽ�� ������λС��
        $params['ordertime'] = $order['o_create_time'];
        $params['ip'] = $order['o_ip'];

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

        $params['cback'] = $params['cost'] * $this->rate_51bi;

        $params['mcode'] = md5($u_id.$params['oid']);

        //����׼�����
        $url = 'http://www.51bi.com/orderback.jhtml?bid='.$this->bid_51bi.'&uid='.$u_id.'&oid='.$params['oid'].'&cost='.$params['cost'].'&cback='.$params['cback'].'&ordertime='.urlencode($params['ordertime']).'&ip='.$params['ip'].'&newuser='.$params['newuser'].'&cates='.$info['cates'].'&comms='.$info['percomm'].'&pp='.$info['price'].'&mcode='.$params['mcode'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);    // ������׼���ύ��URL
        curl_setopt($curl, CURLOPT_GET, true);  // ����POST��ʽ�ύ

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//�ж��Ƿ���շ���ֵ��0�������գ�1������
        $data = curl_exec($curl); // ����curl��������ҳ, ����$dataΪ�ӿڷ�������
        //дlog
        $array_params = array("code"=>$data,'order_id'=>$order['o_id'],'o_source'=>$order['o_source']);
        writeLog(var_export($array_params, true),'51bi.log');
        curl_close($curl);        // �ر�curl����

    }

    protected function get_orders_info($orders, $sign ,$query ){
        $items = array();
        $OrdersItems = M('OrdersItems',C('DB_PREFIX'),'DB_CUSTOM');
        if($query == 'query'){
            $items['otime'] =   date('YmdHis',strtotime($orders['o_create_time']));
        }else{
            $items['otime'] =$orders['o_create_time'];
        }
        $items['o_cd'] = $orders['o_id'];
        $channel_related_info  = json_decode($orders['channel_related_info'],true);
        $items['u_id'] = $channel_related_info['u_id'];
        $items['topprice'] = sprintf('%.2f',$orders['o_all_price']) ;
        $items['comm'] =  sprintf('%.2f',$orders['o_all_price'] * $this->rate_51bi);;//��Ӷ��

        $info = $OrdersItems->field('gt_id,sum(oi_price) as price')->where(array('o_id'=>$orders['o_id']))->group('gt_id')->select();

        foreach($info as $kk =>$vv){
            $items['cates'] .=$vv['gt_id'].$sign;//��Ʒ�ķ���  ��Ʒ����id
            $items['price'] .=sprintf('%.2f',$vv['price']).$sign;// ÿ����Ʒ�ļ۸�
            $items['percomm'] .=sprintf('%.2f',$vv['price'] * $this->rate_51bi).$sign;//ĳ����Ʒ��Ӷ��
        }
        $items['cates'] = rtrim(  $items['cates'],$sign);
        $items['price'] = rtrim($items['price'] ,$sign);
        $items['percomm'] = rtrim($items['percomm'],$sign);
        return $items;

    }

    /**
     * 51��������������
     *
     */
    //���Ͷ���
    public function PushOrders($order){
        $urn ="http://union.fanli.com/dingdan/push";
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$urn);// ������׼���ύ��URL
        $m_id = $order['m_id'];
        $params = array();
        //����û��Ƿ������û��µ�
        $count = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->count();
        if($count ==1 ){
            $params['newuser'] = 1;//���¿��µ�
        }else{
            $params['newuser'] = 0;//�����¿��µ�
        }
        if($order['o_source']==='pc') {
            $params['platform'] = 1;//����Դ��pc
        }else{
            $params['platform'] = 2;//����Դ��wap �ֻ���
        }
        $code = $this->shop_no_fanli.$this->shop_key_fanli;
        $code = strtolower($code);
        $code = md5($code);
        //�������ݣ����Ͷ�����Ϣ�Ͳ�ѯAPI������Ϣ��ʽһ����
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
                        <orders version="4.0">';
        $xmlStr =  $this->getOrdersXml($order,$xmlStr,$code);
        $xmlStr .='</orders>';
        $post_data = array(
            'shopid'=>$this->s_id_fanli,
            "content" => $xmlStr
        );
        curl_setopt($curl, CURLOPT_POST, true);  // ����POST��ʽ�ύ
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//�ж��Ƿ���շ���ֵ��0�������գ�1������
        $data = curl_exec($curl); // ����curl��������ҳ, ����$dataΪ�ӿڷ�������
        //дlog
        $array_params = array("code"=>$data,'order_id'=>$order['o_id'],'o_source'=>$order['o_source']);
        writeLog(var_export($array_params, true),'51fanli.log');
//        $curl_info = curl_getinfo($curl);
//        var_dump($data);
//        echo'curl_info:';
//        var_dump($curl_info);
//        echo($data);
        curl_close($curl);        // �ر�curl����
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
            $status = $this->get_status($value);
            if($value['initial_o_id'] ==0){//���û�и������򱣳����Ӷ���һ��
                $value['initial_o_id'] = $value['o_id'];
            }
            $orderxml = ' <order>
                                <s_id>'.$this->s_id_fanli.'</s_id>
                                <order_id_parent>'.$value['initial_o_id'].'</order_id_parent>
                                <order_id>'.$value['o_id'].'</order_id>
                                <order_time>'.$value["o_create_time"].'</order_time>
                                <uid>'.$data['u_id'].'</uid>
                                <uname>'.$data['username'].'</uname>
                                <tc>'.$data['tracking_code'].'</tc><pay_time></pay_time>
                                <status>'.$status.'</status><locked></locked>
                                <lastmod>'.$value['o_update_time'].'</lastmod>
                                <is_newbuyer>'.$params['newuser'].'</is_newbuyer>
                                <platform>'.$params['platform'].'</platform>
                                <code>'.$code.'</code><remark></remark>
                                <products>';
            $xmlStr .= $orderxml;
//            $info = $OrdersItems->field('count(gt_id) as num ,g_sn,oi_g_name,gt_id,g_id,pdt_sale_price')->where(array('o_id'=>$value['o_id']))->group('gt_id')->select();
            $info = $OrdersItems->field('g_id,oi_price,g_sn,oi_g_name,gt_id,g_id,pdt_sale_price,oi_nums,oi_coupon_menoy,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money,oi_balance_money')->where(array('o_id'=>$value['o_id']))->select();
            $xmlStr .=  $this->getProductsXml($info);
            $xmlStr .='</products></order>';
        }
        return $xmlStr;
    }

    //ƴ����Ʒ��xml 51����
    protected function getProductsXml($info){
        $productsxml = '';
        foreach($info as $kk =>$vv){
            $items['pid'] = $vv['g_id'];//��Ʒ���
            $items['title'] = $vv['oi_g_name'];//��Ʒ����
            $items['category'] = $vv['gt_id'];//��Ʒ����
            $items['url'] = $_SERVER['HTTP_HOST'].'/Home/Products/detail?gid='.$vv['g_id'];
            $items['oi_nums'] = $vv['oi_nums'];
            $items['price'] = $vv['oi_price'] ;//��Ʒ����
            $items['real_pay_fee'] = $items['oi_nums'] * $items['price'] - $vv['oi_coupon_menoy']-$vv['oi_bonus_money']-$vv['oi_cards_money']-$vv['oi_jlb_money']-$vv['oi_point_money']-$vv['oi_balance_money'];
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

    /*
     * ������ ��ȡ״̬��Ϣ
     */
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

}

?>