<?php

/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/10/23
 * Time: 13:33
 */
class OrdersAction extends ApiAction
{
    /**
     * ��ȡ����֧���б�
     * @param array $params
     * $param = array(
     *  'from'  => '',  //������Դ��app(android_app,ios_app),wap,pc,...(����)
     *  'ra_id' => '',  //�ջ���ַID���Ǳ��
     *  'sgp'   => 'g_id,pdt_id,num,item_type;...', //������Ʒ��ϸ���Ǳ��
     *  'm_id'  => '',  //��ԱID���Ǳ��
     *  'lt_id' => '',  //���ͷ�ʽID���Ǳ��
     *  'type'  => '',  //��������
     * );
     *
     */
    private function fxOrdersPaymentsGet($params=array()) {
        $from = $params['from'];
        if(empty($from)){
            $this->errorResult(false,10101,array(),'������Դ����Ϊ��');
        }
        //����������Դ��ȡ֧����ʽ�б�
        $ary_payment = D('PaymentCfg')->getPaymentList($from);
        $options = array(
            'root_tag' => 'order_payments'
        );
        $this->result(true,'200',$ary_payment,"success",$options);
    }

    /**
     * ��ȡ��ݹ�˾�б�
     * @param array $params
     * $param = array(
     *  'ra_id' => '',  //�ջ���ַID�����
     *  'sgp'   => 'g_id,pdt_id,num,promotion_type,promotion_id;...', //������Ʒ��ϸ�����
     *  'm_id'  => '',  //��ԱID�����
     *  'from'  => '',  //������Դ��app(android_app,ios_app),wap,pc,...(�Ǳ���)
     * );
     */
    private function fxLogisticListGet($params=array()) {
        //��֤�Ƿ񴫵�ra_id����
        if(!isset($params["ra_id"]) || "" == $params["ra_id"]){
            $this->errorResult(false,10201,array(),'ȱ��Ӧ�ü������ջ���ַra_id');
        }
        //��֤�Ƿ񴫵�sgp����
        if(!isset($params["sgp"]) || "" == $params["sgp"]){
            $this->errorResult(false,10201,array(),'ȱ��Ӧ�ü�������Ʒ������Ϣ����');
        }
        $ary_sgp = explode(';', $params['sgp']);
        if(empty($ary_sgp)) {
            $this->errorResult(false,10201,array(),'������Ʒ����Ϊ��');
        }
        $ary_pdt = array();
        $order_type = 0;
        //����Ҫ�������Ʒ
        foreach($ary_sgp as $sgp) {
            $ary_item = explode(',', $sgp);
            $g_id = $ary_item[0];
            $pdt_id = $ary_item[1];
            $num = $ary_item[2];
            $order_type = $item_type = isset($ary_item[3]) ? $ary_item[3] : 0;
            $item_type_id = isset($ary_item[4]) ? $ary_item[4] : 0;
            $ary_pdt[$pdt_id] = array(
                'g_id'            =>  $g_id,
                'pdt_id'          =>  $pdt_id,
                'num'             =>  $num,
                'item_type'       =>  $item_type,
                'item_type_id'    =>  $item_type_id,
            );

        }
        //��ȡ�ջ���ַ������ID (cr_id)
        $address_data = D('CityRegion')->getFindReciveAddr($params["ra_id"]);
        if(empty($address_data)){
            $this->errorResult(false,10202,array(),'��ѯ����Ϊ�գ���鿴�ջ���ַ�Ƿ����');
            exit;
        }

        //��ȡ������˾�б�
        $logistic_data = D('Logistic')->getShippingList($address_data["cr_id"], $ary_pdt, $address_data["m_id"], $order_type);

        //������֤��ϣ���������
        $options = array();
        $options['root_tag'] = 'logistic_list_get_response';
        $this->result(true,10007,$logistic_data,"success",$options);

    }

    /**
     * ���ɶ���
     * @param  array $array_params
     * $array_params = array(
     * 'ra_id' => ��ַID (����)
     * 'm_id' => ��ԱID (����)
     * 'pc_id' => ֧��ID (����)
     * 'lt_id' => ����ID (����)
     * 'sgp' => base64_encode(g_id,pdt_id(���ID),num,type,type_id;g_id,pdt_id(���ID),num,type,type_id)
     * 'resource' => ������Դ (����) (android��ios)
     *
     * 'bonus' => '',      //��ѡ�����
     * 'cards' => '',      //��ѡ����ֵ��
     * 'csn'   => '',      //��ѡ���Ż���
     * 'point' => '',      //��ѡ������
     * 'type'	=> '',	       //��ѡ��0���Ż�ȯ|1�����|2���洢��|4������
     * ...
     * );
     */
    private function fxOrderAddDo($array_params = array()) {
        $int_m_id = $array_params['m_id'] = max(0,(int)$array_params['m_id']);
        $int_ra_id = $array_params['ra_id'] = max(0,(int)$array_params['ra_id']);
        $int_lt_id = $array_params['lt_id'] = max(0,(int)$array_params['lt_id']);
        $int_pc_id = $array_params['pc_id'] = max(0,(int)$array_params['pc_id']);
        $array_params['resource'] == 'android' ? 'android' : 'ios';
        $str_items  = base64_decode($array_params['sgp']);
        $array_params['sgp'] = $str_items;
        $ary_items = explode(';', $str_items);
        $ary_pdts = $ary_gids = array();
        foreach($ary_items as $item) {
            $item = trim($item);
            if(!empty($item)) {
                $ary_info = explode(',', $item);
                switch($ary_info[3]) {
                    //�Ź�
                    case '5':
                        //�Ź������ж�
                        $this->fxGroupbuyOrderAddDo($array_params);
                        break;
                    //��ɱ
                    case '7':
                        //��ɱ�����ж�
                        $this->checkSpikeOrderAddParams($array_params);
                        $this->fxSpikeOrderAddDo($array_params);
                        break;
                    //Ԥ��
                    case '8':
                        $this->fxPresaleOrderAddDo($array_params);
                        break;
                }

                $ary_gids[] = $ary_info[0];
                $ary_pdts[] = $ary_info[1];
            }
        }
        $array_params['gid'] = $ary_gids;
        $array_params['pids'] = implode(',', $ary_pdts);
        $ary_orders = array();
        //��������Ч��
        $this->checkOrderAddParams($array_params);
        //��鶩����Ч��
        $this->orderItemsValidate($ary_pdts, $int_m_id);
        //��ȡ�ջ���ַ����
        $this->buildOrderAddress($int_m_id, $int_ra_id, $ary_orders);


        //��ȡ��Ʊ����
        $this->buildOrderInvoice($array_params, $ary_orders);
        //��ȡ�����۸�����
        $this->buildOrderPromotion($array_params, $ary_orders);
        if(!empty($ary_orders['ary_coupon'])){
            $ary_coupon = $ary_orders['ary_coupon'];
            unset($ary_orders['ary_coupon']);
        }
        if(!empty($ary_orders['pro_datas'])){
            $pro_datas = $ary_orders['pro_datas'];
            unset($pro_datas);
        }
        //��ȡ����������Ϣ
        $now_time = date('Y-m-d',time());
        $ary_orders['o_create_time'] = $now_time;
        $ary_orders['o_update_time'] = $now_time;
        $ary_orders['o_receiver_time'] = isset($array_params['o_receiver_time']) ? $array_params['o_receiver_time'] : '';
        $ary_orders['ra_id'] = $int_ra_id;
        if(!empty($array_params['o_buyer_comments'])){
            $ary_orders['o_buyer_comments'] = $array_params['o_buyer_comments'];
        }

        $ary_orders['o_source'] = $array_params['resource'];
        $ary_orders['lt_id'] = $int_lt_id;
        $ary_orders['o_payment'] = $int_pc_id;
        $ary_orders ['m_id'] = $int_m_id;
        $ary_orders ['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
        // ������ע
        $ary_orders ['o_shipping_remarks'] = isset($array_params ['shipping_remarks']) ? $array_params ['shipping_remarks'] : '';
        // ����Ա������ID
        $ary_orders ['o_addorder_id'] = isset($array_params ['admin_id']) ? $array_params ['admin_id'] : '';
        //�ж��Ƿ����Զ���˹���
        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
        if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
            $ary_orders['o_audit'] = 1;
        }
        //�Ƿ�����������
        if($array_params['is_anonymous'] == '1'){
            $ary_orders['is_anonymous'] = 1;
        }

        $ary_orders['oi_type'] = 0;

        $ordersModel = D('Orders');
        $ordersModel->startTrans();
        //���涩��
        $bool_orders = $ordersModel->doInsert($ary_orders);
        if (!$bool_orders) {
            $ordersModel->rollback();
            $this->errorResult(false,10101,array(),'��������ʧ��');exit;
        }
        //���涩������
        $ary_insert_item = $this->insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas);
        //��Ʒ�µ�����ܻ���
        $this->getRewardPoint($ary_orders,$ary_insert_item);
        //�����Ż�ȯʹ��
        $this->updateCoupon($array_params,$ary_orders);
        //����������־
        $this->addOrderLog($ary_orders);
        //������ﳵ
        $this->cleanShoppingCartItem($ary_pdts, $int_m_id,$array_params);

        $options = array(
            'root_tag' => 'Order_confirm_response'
        );
        $response_arr['po_id'] = $order_id;
        $response_arr['total_sale_price'] = sprintf("%0.3f", $ary_orders['o_all_price']);
        $response_arr['payment'] = '֧����';
        $ordersModel->commit();
        $this->result(true,10102,$response_arr,"success",$options);
    }

    private function fxGroupbuyOrderAddDo($array_params) {
        $int_m_id = $array_params['m_id'] = max(0,(int)$array_params['m_id']);
        $int_ra_id = $array_params['ra_id'] = max(0,(int)$array_params['ra_id']);
        $int_lt_id = $array_params['lt_id'] = max(0,(int)$array_params['lt_id']);
        $int_pc_id = $array_params['pc_id'] = max(0,(int)$array_params['pc_id']);
        $str_items = $array_params['sgp'];
        if(empty($str_items)) {
            $this->errorResult(false, 500, '', '�Ź���Ʒ����Ϊ��');
        }
        $ary_items = explode(';', $str_items);
        $ary_pdts = $ary_gids = array();
        $item = current($ary_items);
        $ary_info = explode(',', $item);
        $gp_id = $ary_info[4];

        $ary_orders = array();
        //��������Ч��
        $this->checkOrderAddParams($array_params);
        //��ȡ�ջ���ַ����
        $this->buildOrderAddress($int_m_id, $int_ra_id, $ary_orders);
        //����Ź�������Ч��
        $this->checkBulkOrderAddParams($array_params, $ary_orders);

        //��ȡ��Ʊ����
        $this->buildOrderInvoice($array_params, $ary_orders);
        //��ȡ�����۸�����
        $this->buildOrderPromotion($array_params, $ary_orders);

        //��ȡ����������Ϣ
        $now_time = date('Y-m-d',time());
        $ary_orders['o_create_time'] = $now_time;
        $ary_orders['o_update_time'] = $now_time;
        $ary_orders['o_receiver_time'] = isset($array_params['o_receiver_time']) ? $array_params['o_receiver_time'] : '';
        $ary_orders['ra_id'] = $int_ra_id;
        if(!empty($array_params['o_buyer_comments'])){
            $ary_orders['o_buyer_comments'] = $array_params['o_buyer_comments'];
        }

        $ary_orders['o_source'] = $array_params['resource'];
        $ary_orders['lt_id'] = $int_lt_id;
        $ary_orders['o_payment'] = $int_pc_id;
        $ary_orders['m_id'] = $int_m_id;
        $ary_orders['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
        // ������ע
        $ary_orders ['o_shipping_remarks'] = isset($array_params ['shipping_remarks']) ? $array_params ['shipping_remarks'] : '';
        // ����Ա������ID
        $ary_orders ['o_addorder_id'] = isset($array_params ['admin_id']) ? $array_params ['admin_id'] : '';
        //�ж��Ƿ����Զ���˹���
        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
        if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
            $ary_orders['o_audit'] = 1;
        }
        //�Ƿ�����������
        $ary_orders['is_anonymous'] = (isset($array_params['is_anonymous']) && $array_params['is_anonymous'] > 0) ? 1 : 0;

        $ary_orders['oi_type'] = 5;
        $ary_orders['gp_id'] = $gp_id;
        $ary_orders['goods_pids'] = 'bulk';

        $ordersModel = D('Orders');
        $ordersModel->startTrans();
        //���涩��
        $bool_orders = $ordersModel->doInsert($ary_orders);
        if (!$bool_orders) {
            $ordersModel->rollback();
            $this->errorResult(false,10101,array(),'��������ʧ��');exit;
        }
        //���涩������
        $ary_insert_item = $this->insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas);
        //��Ʒ�µ�����ܻ���
        $this->getRewardPoint($ary_orders,$ary_insert_item);
        //�����Ż�ȯʹ��
        $this->updateCoupon($array_params,$ary_orders);
        //����������־
        $this->addOrderLog($ary_orders);
        //������ﳵ
        $this->cleanShoppingCartItem($ary_pdts, $int_m_id,$array_params);

        $options = array(
            'root_tag' => 'Order_confirm_response'
        );
        $response_arr['po_id'] = $order_id;
        $response_arr['total_sale_price'] = sprintf("%0.3f", $ary_orders['o_all_price']);
        $response_arr['payment'] = '֧����';
        $ordersModel->commit();
    }

    private function fxSpikeOrderAddDo() {
        if($ary_info['3'] == 'spike' && isset($ary_info[4])){
            $array_params['sp_id'] = $ary_info[4];
            $array_params['pdt_id'] = $ary_info[1];
            $array_params['num'] = $ary_info[2];
            $_SESSION['spike_cart'] = array(
                'pdt_id'=>$ary_info[1],
                'num'=>$ary_info[4],
                'sp_id'=>$ary_info[2]
            );
        }

        $ary_orders['oi_type'] = 7;
        $ary_orders['sp_id'] = $array_params['sp_id'];
        $ary_orders['goods_pids'] = 'spike';
    }

    /**
     * ��������Ч��
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkOrderAddParams($array_params){
        if(isset($array_params['m_id']) && $array_params['m_id'] == 0){
            $this->errorResult(false,10101,array(),'����д�û�ID:m_id');
            exit;
        }
        if(isset($array_params['ra_id']) && $array_params['ra_id'] == 0){
            $this->errorResult(false,10102,array(),'����д��ַID:ra_id');
            exit;
        }
        if(isset($array_params['lt_id']) && $array_params['lt_id'] == 0){
            $this->errorResult(false,10103,array(),'����д����ID:lt_id');
            exit;
        }
        if(isset($array_params['pdt_id']) && $array_params['pdt_id'] == 0){
            $this->errorResult(false,10104,array(),'����дѡ�����Ʒ:pdt_id');
            exit;
        }
        if(isset($array_params['pc_id']) && $array_params['pc_id'] == 0){
            $this->errorResult(false,10105,array(),'����д֧����ʽ:pc_id');
            exit;
        }
        return true;
    }

    /**
     * ����Ź�������Ч��
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkBulkOrderAddParams($array_params,$ary_orders){
        //�Ź��ж�
        if(isset($array_params['gp_id'])){
            $now_count =  D('OrdersItems')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
                ->where(array('fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$array_params ['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                ->find();
            $btween_time = D('Groupbuy')->where(array('gp_id'=>$array_params ['gp_id']))->field("gp_start_time,gp_end_time,gp_number,gp_now_number")->find();
            if($now_count['buy_nums'] >= $btween_time['gp_number']){
                $_SESSION['bulk_cart'] = "";
                $this->errorResult(false,10101,array(),'������');
                exit;
            }
            if(strtotime($btween_time['gp_start_time']) > mktime()){
                $this->errorResult(false,10101,array(),'�Ź�δ��ʼ');
                exit;
            }
            if(strtotime($btween_time['gp_end_time']) < mktime()){
                $this->errorResult(false,10101,array(),'�Ź��ѽ���');
                exit;
            }
            $array_where = array('is_active'=>1,'gp_id'=>$array_params['gp_id'],'deleted'=>0);
            $data = D('Groupbuy')->where($array_where)->find();
            if($array_params['m_id']){
                //��ǰ��Ա�ѹ�������
                $member_buy_num =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                    ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
                    ->where(array('fx_orders.m_id'=>$array_params['m_id'],'fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$data['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                    ->find();
                //Ŀǰ���Թ��������
                $thisGpNums = $data['gp_number'] - $now_count['now_count'];
                //�����Ա�޹��������ڵ�ǰ��Ա�ѹ�������
                if($data['gp_per_number'] > $member_buy_num['buy_nums']){
                    //��ǰ��Ա�����Թ��������
                    $gp_number = $data['gp_per_number'] - $member_buy_num['buy_nums'];
                    //�����Ա�����Թ������������Ŀǰ��棬����渳���Ա��������
                    if(($array_params['num'] > $gp_number) || ($array_params['num'] > $thisGpNums)){
                        $this->errorResult(false,10101,array(),'�����˻��������Ѵ����ޣ�');
                        exit;
                    }
                }else{
                    $this->errorResult(false,10101,array(),'�����˻��������Ѵ����ޣ�');
                    exit;
                }
            }
            $is_can_buy = D('CityRegion')->isGroupCanBuy($ary_orders['o_receiver_city'],$array_params ['gp_id']);
            if($is_can_buy == false){
                $this->errorResult(false,10101,array(),'���ջ���ַ����Ӧ������֧�ֹ������Ʒ��');
                exit;
            }
        }
        return true;
    }

    /**
     * �����ɱ������Ч��
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkSpikeOrderAddParams($array_params){
        //��ɱ��Ʒÿ���޹�1�� �ж���ɱ�Ƿ��ѽ���
        if(isset($array_params ['sp_id'])){
            $btween_time = D('Spike')->where(array('sp_id'=>$array_params ['sp_id']))->field("sp_start_time,sp_end_time,sp_number,sp_now_number")->find();
            if($btween_time['sp_number'] <= $btween_time['sp_now_number']){
                $this->errorResult(false,10101,array(),'��������');
                exit;
            }
            if(strtotime($btween_time['sp_start_time']) > mktime()){
                $this->errorResult(false,10101,array(),'��ɱδ��ʼ��');
                exit;
            }
            if(strtotime($btween_time['sp_end_time']) < mktime()){
                $this->errorResult(false,10101,array(),'��ɱ�ѽ�����');
                exit;
            }
            $ary_where = array();
            $ary_where[C('DB_PREFIX')."orders.m_id"] = $array_params['m_id'];
            $ary_where[C('DB_PREFIX')."spike.sp_id"] = $array_params['sp_id'];
            $ary_where[C('DB_PREFIX')."orders.o_status"] = array('neq','2');
            $ary_where[C('DB_PREFIX')."orders_items.oi_type"] = 7;
            $ary_spike=D('Spike')
                ->field(array(C('DB_PREFIX').'orders_items.fc_id',C('DB_PREFIX').'spike.*'))
                ->join(C('DB_PREFIX').'orders_items ON '.C('DB_PREFIX').'spike.sp_id = '.C('DB_PREFIX').'orders_items.fc_id')
                ->join(C('DB_PREFIX').'orders ON '.C('DB_PREFIX').'orders.o_id = '.C('DB_PREFIX').'orders_items.o_id')
                ->where($ary_where)->find();
            if(!empty($ary_spike) && is_array($ary_spike)){
                $this->errorResult(false,10101,array(),'��ɱ�޹�1��');
                exit;
            }
        }
        return true;
    }

    /**
     * ������Ч����֤
     * @param array $ary_pdt
     * @param int $int_m_id
     *
     * @return bool    true/false
     */
    private function orderItemsValidate($ary_pdt=array(), $int_m_id=0){
        if (!empty($ary_pdt)) {
            $ary_pdt = array_unique($ary_pdt);
            $field = array(
                'fx_goods_products.pdt_stock',
                'fx_goods_products.pdt_id',
                'fx_goods.g_on_sale',
                'fx_goods.g_sn',
                'fx_goods.g_gifts',
                'fx_goods.g_is_combination_goods',
                'fx_goods.g_pre_sale_status',
                'fx_goods_info.is_exchange',
                'fx_goods_info.g_name',
                'fx_goods_info.g_id',
                'fx_goods_products.pdt_sale_price',
                'fx_goods_products.pdt_max_num',
                'fx_goods.g_on_sale_time',
                'fx_goods.g_off_sale_time'
            );
            $where = array(
                'fx_goods_products.pdt_id' => array(
                    'IN',
                    $ary_pdt
                )
            );

            $goods_data = D("GoodsProducts")->GetProductList($where, $field);
            foreach ($goods_data as $key => $value) {
                if ($value ['g_on_sale'] != 1) { // �ϼ�
                    $this->errorResult(false,10207,array(),'��Ʒ���¼ܣ�');
                    return false;
                }
                $is_authorize = D('AuthorizeLine')->isAuthorize($int_m_id, $value['g_id']);
                if (empty($is_authorize)) {
                    $this->errorResult(false,10101,array(),'������Ʒ�Ѳ�������,�����ڹ��ﳵ��ɾ����Щ��Ʒ');
                    return false;
                }
            }
            return true;
        }else{
            $this->errorResult(false,10208,array(),'û����Ʒ���ݣ�');
            return false;
        }
    }

    /**
     * �����ջ���ַ����
     * @param $int_m_id
     * @param $int_ra_id
     * @param $ary_orders
     *
     * @return mixed
     */
    private function buildOrderAddress($int_m_id, $int_ra_id, &$ary_orders=array()) {

        $ary_receive_address = D('CityRegion')->getReceivingAddress($int_m_id, $int_ra_id);
        if (isset($ary_receive_address['ra_id'])) {
            // �ջ���
            $ary_orders ['o_receiver_name'] = $ary_receive_address ['ra_name'];
            // �ջ��˵绰
            $ary_orders ['o_receiver_telphone'] = empty($ary_receive_address ['ra_phone']) ? '' : trim($ary_receive_address ['ra_phone']);
            // �ջ����ֻ�
            $ary_orders ['o_receiver_mobile'] = empty($ary_receive_address ['ra_mobile_phone']) ? '' : trim($ary_receive_address ['ra_mobile_phone']);
            // �ջ����ʱ�
            $ary_orders ['o_receiver_zipcode'] = $ary_receive_address ['ra_post_code'];
            // �ջ��˵�ַ
            $ary_orders ['o_receiver_address'] = $ary_receive_address ['ra_detail'];

            $ary_addr = explode(' ', $ary_receive_address['address']);
            if(!empty($ary_addr[1])){
                // �ջ���ʡ��
                $ary_orders ['o_receiver_state'] = $ary_addr[0];
                // �ջ��˳���
                $ary_orders ['o_receiver_city'] = $ary_addr[1];
                // �ջ��˵���
                $ary_orders ['o_receiver_county'] = isset($ary_addr[2]) ? $ary_addr[2] : '';
            }else{
                $this->errorResult(false,10101,array(),'���������ջ���ַ�Ƿ���ȷ');
                exit();
            }
            if (empty($ary_orders ['o_receiver_county'])) { // û����ʱ
                unset($ary_orders ['o_receiver_county']);
            }
        }else{
            $this->errorResult(false,10101,array(),'���������ջ���ַ�Ƿ���ȷ');
            exit();
        }
    }

    /**
     * ������Ʊ��Ϣ
     * @param array $array_params
     * @param array $ary_orders
     *
     * @return array
     */
    private function buildOrderInvoice($array_params= array(), &$ary_orders=array()) {

        if(isset($array_params['is_on']) && $array_params['is_on']!=''){
            if($array_params['is_on'] =='1'){//��ͨ��Ʊ(����)
                $ary_orders['is_invoice']=1;
                $ary_orders['invoice_type']='1';
                $ary_orders['invoice_head']='1';
                $ary_orders['invoice_people']=$array_params['invoice_name'];
            }
            if($array_params['is_on'] =='2'){//��ͨ��Ʊ(��λ)
                $ary_orders['is_invoice']=1;
                $ary_orders['invoice_type']='1';
                $ary_orders['invoice_head']='��λ';
                $ary_orders['invoice_name']=$array_params['invoice_name'];
            }
            if($array_params['invoice_content']!=''){//��Ʊ����
                $ary_orders['invoice_content'] = $array_params['invoice_content'];
            }
        }
    }

    /**
     * ����������Ϣ
     * @param array $array_params
     * @param array $ary_orders
     */
    private function buildOrderPromotion($array_params= array(), &$ary_orders=array()) {
        $array_params['type'] = 10;
        writeLog(var_export($array_params, true), 'order_add_api_'. date('Y_m_d') .'.log');
        $ary_price = D('Orders')->getAllOrderPrice($array_params);
        if(!$ary_price['success']) {
            $this->errorResult(false,10101,array(), $ary_price['errMsg']);
        }
        $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $ary_price['o_goods_all_saleprice']);  //���ۼ�
        $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $ary_price['o_goods_all_price']);  //������
        if(empty($ary_orders ['o_goods_all_price'])){
            $this->errorResult(false,10101,array(),'û��Ҫ�������Ʒ��������ѡ����Ʒ');
            exit;
        }
        $ary_orders ['o_all_price'] = sprintf("%0.2f", $ary_price['all_price']);    //�����ܼ�
        $ary_orders ['o_discount'] = sprintf("%0.2f", $ary_price['o_discount']);    //�����Żݽ��
        $ary_orders ['o_goods_discount'] = sprintf("%0.2f", $ary_price['o_discount']);   //�����Żݽ��
        $ary_orders ['o_promotion_price'] = sprintf("%0.2f", $ary_price['o_discount']);   //�����Żݽ��
        if(isset($ary_price['discount_price'])){
            $ary_orders ['discount_price'] = sprintf("%0.2f", $ary_price['discount_price']);   //�����Żݽ��
        }
        if($ary_orders ['o_all_price']<0){
            $ary_orders ['o_all_price'] = 0;
        }
        if(0 == $ary_orders ['o_all_price']) { //�������ܼ�Ϊ0 ����״̬Ϊ��֧��
            $ary_orders ['o_pay_status'] = 1;
            $ary_orders ['o_status'] = 1;
        }
        $ary_orders ['o_cost_freight'] = sprintf("%0.2f", $ary_price['logistic_price']);    //�˷�
        $ary_orders ['o_coupon_menoy'] = sprintf("%0.2f", $ary_price['coupon_price']);    //�Ż�ȯ���
        if(isset($ary_price['coupon'])) {
            $ary_orders ['o_coupon']     = 1;    //�Ƿ�ʹ���Ż�ȯ
            $ary_orders ['coupon_sn']    = $ary_price['coupon']['c_sn'];    //�Ż�ȯ��
            $ary_orders ['coupon_value'] = $ary_price['coupon']['c_money'];    //�Ż�ȯ���
            $ary_orders ['coupon_start_date'] = $ary_price['coupon']['c_start_time'];    //�Ż�ȯ��Ч��ʼʱ��
            $ary_orders ['coupon_end_date'] = $ary_price['coupon']['c_end_time'];    //�Ż�ȯ��Ч����ʱ��
        }
        $ary_orders['o_freeze_point'] = intval($ary_price['points']);   //������
        $ary_orders['o_point_money'] = sprintf("%0.2f", $ary_price['point_price']);   //���ֵֿ۽��
        $ary_orders['o_cards_money'] = sprintf("%0.2f", $ary_price['cards_price']);   //ʹ�ô洢���ֿ۽��
        $ary_orders['o_bonus_money'] = sprintf("%0.2f", $ary_price['bonus_price']);   //ʹ�ú���ֿ۽��
        //�����Ż�ȯ��Ϣ��pro_datas��Ϣ
        $ary_orders['ary_coupon'] = $ary_price['ary_coupon'];
        $ary_orders['pro_datas'] = $ary_price['pro_datas'];
    }

    /**
     * ���붩������
     * @param $ary_orders
     * @param $array_params
     * @param $ary_coupon
     * @param $pro_datas
     *
     * @return array
     */
    private function insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas) {
        $str_pdt_ids = $array_params['pids'];
        $m_id = $ary_orders['m_id'];
        $ary_orders_items = array();
        $cartModel = D('Cart');
        $orders = D('Orders');
        //��ȡ���ﳵ����
        if (isset($array_params ['sp_id'])) {
            // ��ɱ��Ʒ
            $ary_cart [$array_params ['pdt_id']] = array(
                'pdt_id' => $array_params ['pdt_id'],
                'num' => $array_params ['num'],
                'sp_id' => $array_params ['sp_id'],
                'type' => 7,
                'type_code'=>'spike',
                'type_id' =>$array_params ['sp_id']
            );
        }
        else if(isset($array_params ['gp_id'])){
            // �Ź���Ʒ
            $ary_cart [$array_params ['pdt_id']] = array(
                'pdt_id' => $array_params ['pdt_id'],
                'num' => $array_params ['num'],
                'gp_id' => $array_params ['gp_id'],
                'type' => 5,
                'type_code'=>'bulk',
                'type_id' =>$array_params ['gp_id']
            );
        }
        else{
            //��ȡ���ﳵ��Ϣ
            $ary_cart = $cartModel->getCartItems($str_pdt_ids, $m_id);
        }
        $ary_orders_goods = $cartModel->getProductInfo($ary_cart, $m_id);
        if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
            $total_consume_point = 0; // ���Ļ���
            $int_pdt_sale_price = 0; // ��Ʒ����ԭ���ܺ�
            $gifts_point_reward = '0'; //�����ù���Ʒ����������ȡ�Ļ�����
            $gifts_point_goods_price  = '0'; //�����˹���Ʒ�����ֵ���Ʒ���ܼ�
            //��ȡ��ϸ����Ľ��
            $ary_orders_goods = D('OrdersItems')->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);
            foreach ($ary_orders_goods as $k => $v) {
                $ary_orders_items = array();
                //�Ź�
                if($v['type'] == 5){
                    // �Ź���Ʒ
                    // ����id
                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                    // ��Ʒid
                    $ary_orders_items ['g_id'] = $v ['g_id'];

                    $ary_orders_items ['fc_id'] = $ary_orders['gp_id'];

                    // ��Ʒid
                    $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                    // ����id
                    $ary_orders_items ['gt_id'] = $v ['gt_id'];
                    // ��Ʒsn
                    $ary_orders_items ['g_sn'] = $v ['g_sn'];
                    // ��Ʒsn
                    $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                    // ��Ʒ����
                    $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                    // �ɱ���
                    $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                    // ��Ʒ����ԭ��
                    $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                    // �Ź���Ʒ
                    $ary_orders_items ['oi_type'] = $v ['type'];
                    // ���򵥼�
                    $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $ary_orders ['discount_price'];
                    // ��Ʒ����
                    $ary_orders_items ['oi_nums'] = $v['pdt_nums'];
                    //�������
                    if (!empty($User_Grade['ml_rebate'])) {
                        $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                    }
                    //�ȼ��ۿ�
                    if (!empty($User_Grade['ml_discount'])) {
                        $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                    }
                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                    if (!$bool_orders_items) {
                        $orders->rollback();
                        $this->errorResult(false,10101,array(),'������ϸ����ʧ��');
                        exit();
                    }

                    $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$v['pdt_nums']);
                    if (!$retun_buy_nums) {
                        $orders->rollback();
                        $this->errorResult(false,10101,array(),'�����Ź���ʧ��');
                        exit();
                    }
                }else{
                    //��ɱ
                    if($v['type'] == 7){
                        // ����id
                        $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                        // ��Ʒid
                        $ary_orders_items ['g_id'] = $v ['g_id'];
                        // ��ɱ��ƷID,ȡһ��
                        /**
                        $fc_id = M('spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'g_id' => $v ['g_id'],
                        'sp_status' => '1'
                        ))->getField('sp_id');
                         **/
                        $ary_orders_items ['fc_id'] = $ary_orders['sp_id'];
                        // ��Ʒid
                        $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                        // ����id
                        $ary_orders_items ['gt_id'] = $v ['gt_id'];
                        // ��Ʒsn
                        $ary_orders_items ['g_sn'] = $v ['g_sn'];
                        // ��Ʒsn
                        $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                        // ��Ʒ����
                        $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                        // �ɱ���
                        $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                        // ��Ʒ����ԭ��
                        $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                        // ��ɱ��Ʒ
                        $ary_orders_items ['oi_type'] = $v ['type'];
                        // ���򵥼�
                        $ary_orders_items ['oi_price'] =  $ary_orders ['discount_price'];
                        // ��Ʒ����
                        $ary_orders_items ['oi_nums'] = $v['pdt_nums'];
                        //�������
                        if (!empty($User_Grade['ml_rebate'])) {
                            $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                        }
                        // echo "<pre>";print_R($v);exit;
                        //�ȼ��ۿ�
                        if (!empty($User_Grade['ml_discount'])) {
                            $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                        }
                        $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                        if (!$bool_orders_items) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'������ϸ����ʧ��');
                            exit();
                        }
                        $retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$v['pdt_nums']);
                        if (!$retun_buy_nums) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'������ɱ��ʧ��');
                            exit();
                        }
                    }else{
                        if (!empty($v['rule_info']['name'])) {
                            $v['pmn_name'] = $v['rule_info']['name'];
                        }
                        // ����id
                        $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                        // ��Ʒid
                        $ary_orders_items ['g_id'] = $v ['g_id'];
                        // ��Ʒid
                        $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                        // ����id
                        $ary_orders_items ['gt_id'] = $v ['gt_id'];
                        // ��Ʒsn
                        $ary_orders_items ['g_sn'] = $v ['g_sn'];
                        // o_sn
                        // $ary_orders_items['g_id'] = $v['g_id'];
                        // ��Ʒsn
                        $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                        // ��Ʒ����
                        $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                        // �ɱ���
                        $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                        // ��Ʒ����ԭ��
                        $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                        // ���򵥼�
                        $ary_orders_items ['oi_price'] = $v ['pdt_price'];
                        // ��Ʒ����
                        if (isset($v ['type']) && $v ['type'] == 1) {
                            $ary_orders_items ['oi_score'] = $v ['pdt_sale_price'];
                            $total_consume_point += $v ['pdt_sale_price'] * $v ['pdt_nums'];
                            $ary_orders_items ['oi_type'] = 1;
                        } else {
                            if (isset($v ['type']) && $v ['type'] == 2) {
                                $ary_orders_items ['oi_type'] = 2;
                            }
                            $int_pdt_sale_price += $v ['pdt_sale_price'] * $v ['pdt_nums'];
                        }
                        if (isset($v['pmn_name'])) {
                            $ary_orders_items['promotion'] = $v['pmn_name'];
                        }
                        if (isset($v['promotion_price']) && !empty($v['promotion_price'])) {
                            $ary_orders_items['promotion_price'] = $v['promotion_price'];
                        }

                        // ��Ʒ����
                        $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                        if(!empty($v['oi_coupon_menoy'])){
                            $ary_orders_items['oi_coupon_menoy'] = $v['oi_coupon_menoy'];
                        }
                        if(!empty($v['oi_bonus_money'])){
                            $ary_orders_items['oi_bonus_money'] = $v['oi_bonus_money'];
                        }
                        if(!empty($v['oi_cards_money'])){
                            $ary_orders_items['oi_cards_money'] = $v['oi_cards_money'];
                        }
                        if(!empty($v['oi_jlb_money'])){
                            $ary_orders_items['oi_jlb_money'] = $v['oi_jlb_money'];
                        }
                        if(!empty($v['oi_point_money'])){
                            $ary_orders_items['oi_point_money'] = $v['oi_point_money'];
                        }
                        $bool_orders_items = $orders->doInsertOrdersItems($ary_orders_items);
                        if (!$bool_orders_items) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'������ϸ����ʧ��');
                            exit();
                        }else{
                            //��Ʒ�������
                            //$ary_goods_num = M("goods_info")->where(array('g_id' => $ary_orders_items ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$ary_orders_items['oi_nums'])))->save();
                            //if (!$ary_goods_num) {
                            //$orders->rollback();
                            //$this->errorResult(false,10101,array(),'�������ʧ��');
                            //exit();
                            //}
                        }
                        // ��Ʒ���۳�
                        $ary_payment_where = array(
                            'pc_id' => $ary_orders ['o_payment']
                        );
                        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                        if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                            // by Mithern �۳����µ�������ɿ�������
                            $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                            if ($good_sale_status ['g_pre_sale_status'] != 1) { // �����Ԥ����Ʒ���ۿ��
                                //��ѯ���,��������Ϊ�������ٿ۳����
                                $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                    ->field('pdt_stock,pdt_min_num')
                                    ->where(array('o_id'=>$ary_orders['o_id']))
                                    ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                    ->find();
                                if(0 >= $int_pdt_stock['pdt_stock']){
                                    $this->errorResult(false,10101,array(),'�û�Ʒ�����꣡');
                                    die();
                                }
                                if($v['pdt_nums'] < $int_pdt_stock['pdt_min_num']){
                                    $this->errorResult(false,10101,array(),'�û�Ʒ���ٹ���'.$int_pdt_stock['pdt_min_num']);
                                    die();
                                }
                                $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                                if (false == $array_result ["status"]) {
                                    $orders->rollback();
                                    $this->errorResult(false,10101,array(),$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    die();
                                }
                            }
                        }
                    }
                }
                if ($bool_orders_items) {
                    //��Ʒ�������
                    $ary_goods_num = M("goods_info")->where(array('g_id' => $ary_orders_items ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$ary_orders_items['oi_nums'])))->save();
                    if (!$ary_goods_num) {
                        $orders->rollback();
                        $this->errorResult(false,10101,array(),'�������ʧ��');
                        exit();
                    }
                }
                if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
                    $gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
                    $gifts_point_goods_price += $v['pdt_sale_price']*$v['pdt_nums'];
                }
            }
        }
        $ary_return = array(
            'total_consume_point'=>$total_consume_point,
            'int_pdt_sale_price'=>$int_pdt_sale_price,
            'gifts_point_reward'=>$gifts_point_reward,
            'gifts_point_goods_price'=>$gifts_point_goods_price
        );
        return $ary_return;
    }

    /**
     * ����������־
     * @param $ary_orders
     */
    private function addOrderLog($ary_orders) {
        // ������־��¼
        $ary_orders_log = array(
            'o_id' => $ary_orders ['o_id'],
            'ol_behavior' => '����',
            'ol_uname' => $ary_orders['m_id'],
            'ol_create' => date('Y-m-d H:i:s')
        );

        $res_orders_log = D('OrdersLog')->add($ary_orders_log);
        if (!$res_orders_log) {
            $this->errorResult(false,10101,array(),'������־��¼ʧ��');
            exit();
        }
    }

    /**
     * ������ﳵ��Ʒ
     * @param $ary_pdts
     * @param $m_id
     */
    private function cleanShoppingCartItem($ary_pdts, $m_id,$array_params) {
        if (isset($array_params ['sp_id'])) {
            // ��ɱ��Ʒ
            unset($_SESSION['spike_cart']);
        }else if(isset($array_params ['gp_id'])){
            // �Ź���Ʒ
            unset($_SESSION['bulk_cart']);
        }else{
            $ApiCarts = D('ApiCarts');
            $car_key = base64_encode( 'mycart' . $m_id);
            $cart_data = $ApiCarts->GetData($car_key);
            foreach ($ary_pdts as $val) {
                if (isset($cart_data[$val])) {
                    unset($cart_data[$val]);
                }
            }
            $ApiCarts->WriteMycart($cart_data, $car_key);
        }
    }

}