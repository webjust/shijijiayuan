<?php
/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/11/20
 */

class OrdersGroupbuyAction extends CommonAction
{
    /**
     * 团购订单确认页
     */
    public function pageBulkAdd() {

        $param_bulk = $_SESSION ['bulk_cart'];

        if (empty ( $param_bulk )) {
            $this->error ( '请选择团购商品！',U('Home/Bulk/index') );
        }
        $ary_members = $_SESSION ['Members'];
        // unset($_SESSION['bulk_cart']);
        $groupbuy = D ('Groupbuy');
        $ary_data = array ();
        // 获取常用收货地址
        $ary_addr = D('CityRegion')->getReceivingAddress ( $ary_members ['m_id'] );
        if (count ( $ary_addr ) > 0) {
            $ary_data ['default_addr'] = array_shift($ary_addr);
        }
        $ary_data ['ary_addr'] = $ary_addr;
        // 获取支付方式
        $payment = D ( 'PaymentCfg' );
		$payment_cfg = $payment->getPaymentList('pc');
        $ary_data ['payment_cfg'] = $payment_cfg;
        // 发票信息
        $p_invoice = D ( 'Invoice' )->get ();

        $invoice_type = explode ( ",", $p_invoice ['invoice_type'] );
        $invoice_head = explode ( ",", $p_invoice ['invoice_head'] );
        $invoice_content = explode ( ",", $p_invoice ['invoice_content'] );

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D ( 'InvoiceCollect' )->get ( $ary_members ['m_id'] );
        $ary_details = $groupbuy->getDetails($param_bulk['gp_id'], $ary_members, $param_bulk['pdt_id']);
        $ary_details['num'] = $param_bulk['num'];
        $ary_details['pdt_set_sale_all_price'] = $ary_details['pdt_set_sale_price'] * $ary_details['num'];
        $goods_all_price = $ary_details['gp_price']*$ary_details['num'];
        $ary_details ['gp_all_price'] = $goods_all_price;
        $ary_cart [$param_bulk['pdt_id']] = array (
            'pdt_id' => $param_bulk['pdt_id'],
            'num' => $param_bulk['num'],
            'type' => 0,
            'type_code'=>'bulk',
            'type_id'=>$param_bulk['gp_id']
        );

        // 获取配送公司表
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                $ary_logistic = D('Logistic')->getLogistic($cr_id,$ary_cart);
            }
        }

        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $goods_all_price >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
        $logistic = reset($ary_logistic);
        $ary_details['logistic_price']  = $logistic['logistic_price'];
        //订单总金额
        $ary_details ['all_price'] += $logistic['logistic_price'];
//		 echo "<pre>";print_r($ary_details);exit;
        $this->assign ( $ary_details );
        $this->assign ( 'ary_addr', $ary_data ['ary_addr'] );
        $this->assign ( 'default_addr', $ary_data ['default_addr'] );
        // 支付方式
        $this->assign ( 'ary_paymentcfg', $ary_data ['payment_cfg'] );
        // 配送公司
        $this->assign ( 'ary_logistic', $ary_logistic );
        // 发票收藏列表
        $this->assign ( 'invoice_list', $invoice_list );
        // 发票信息
        $this->assign ( 'invoice_info', $invoice_info );
        $this->assign ( 'invoice_content', $invoice_content );
        //送货时间
        $ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
        /*标明这个是团购订单确认页*/
        $this->assign ( 'web_type', 'Bulk' );
        //根据当前SESSION生成随机数非法提交订单
        $code = mt_rand(0,1000000);
        $_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
        $this->assign("auto_code",$code);
        $tpl = 'Orders:pageBulkAdd';
        $this->display ($tpl);
    }

    /**
     * 获取团购订单配送列表
     */
    public function getBulkLogisticType(){
        $ary_post = $this->_post();
        $bulk_cart = session('bulk_cart');
        $ary_member = session("Members");
        $ra_id = $ary_post['ra_id'];
        $cr_id = $ary_post['cr_id'];

        $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
        if($array_bulk['status'] == 'error') {
            $ary_logistic = array();
        }
        else {
            $ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);
            $ary_logistic = D('Logistic')->getLogistic($cr_id, $ary_cart);
           // dump($ary_logistic);die;
            if (!empty($ary_logistic) && is_array($ary_logistic)) {
                foreach ($ary_logistic as $k1 => $v1) {
                    $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $v1['lt_id']), array('fx_logistic_type.lt_expressions'));
                    $lt_expressions = json_decode($logistic_info['lt_expressions'], true);
                    if (!empty($lt_expressions['logistics_configure']) && $array_bulk['data']['gp_subtotal_price'] >= $lt_expressions['logistics_configure']) {
                        $ary_logistic [$k1] ['logistic_price'] = 0;
                    }

                    //设置包邮
                    if ($array_bulk['data']['gp_is_baoyou'] == 1) {
                        $ary_logistic [$k1] ['logistic_price'] = 0;
                    }
                }
            }
        }
        //print_r($array_bulk);die();
        $this->assign('ary_logistic', $ary_logistic);
        //$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $pay_name = '';
        //if($is_zt['IS_ZT']['sc_value'] == 1){
        $pay_info = D('Gyfx')->selectOneCache('payment_cfg','pc_custom_name', array('pc_abbreviation'=>'DELIVERY'));
        $pay_name = $pay_info['pc_custom_name'];
        //}
        $this->assign('pay_name',$pay_name);
        $tpl = 'Orders:getBulkLogisticType';
        $this->display($tpl);
    }

    /**
     * 计算团购物流费用
     * @author Nick
     * @date 2015-11-20
     */
    public function changeBulkLogistic() {
        $ary_post = $this->_post();
        $bulk_cart = session('bulk_cart');
        $ary_member = session("Members");

        $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
        if($array_bulk['status'] == 'error'){
            $ary_return['status'] = 0;
        }
        else {
            $array_bulk = $array_bulk['data'];
            $ary_return['all_price'] = $array_bulk['pdt_all_sale_price'];
            $ary_return['all_goods_price'] = $array_bulk['gp_subtotal_price'];
            $ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);

            $logistic_price = D('Logistic')->getLogisticPrice($ary_post['lt_id'], $ary_cart, $ary_member['m_id']);
            $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $ary_post['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery', 'fx_logistic_type.lt_expressions'));
            $lt_expressions = json_decode($logistic_info['lt_expressions'], true);
            if (!empty($lt_expressions['logistics_configure']) && $ary_return ['all_goods_price'] >= $lt_expressions['logistics_configure']) {
                $logistic_price = 0;
            }
            //如果设置包邮
            if ($array_bulk['gp_is_baoyou'] == 1) {
                $logistic_price = 0;
            }
            $ary_return['status'] = 1;
            $ary_return['logistic_price'] = $logistic_price;
            $ary_return['promotion_price'] = $ary_return['all_price'] - $ary_return['all_goods_price'];
            $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
        }
        $this->ajaxReturn($ary_return);
    }

    private function _beforeDoAdd($ary_datas, $queue_obj=null) {
        $ary_member = session("Members");
        //order queue add by zhangjiasuo 2014-10-29 start
        $ary_datas ['m_id']=$ary_member['m_id'];
        $ary_datas ['ml_id']=$ary_member['ml_id'];
        $ary_datas ['admin_id']=$ary_member['admin_id'];
        $ary_orders=$ary_datas;
        if(empty($ary_orders)){
            $this->redirect('Ucenter/Cart/pageList');
        }
        if (empty($ary_orders ['m_id'])) {
            $this->error('登录过期，请重新登录！');
        }
        if(!isset($ary_orders ['gp_id'])){
            $this->error('团购不存在！');
        }
        $now_tome = date('Y-m-d H:i',strtotime('+2 hours'));
        if($ary_orders['o_receiver_time'] < $now_tome && isset($ary_orders['o_receiver_time'])){
            if(isset($queue_obj)){ $queue_obj->unLock(); }
            $this->error('请选择正确的送货时间,送货时间在当前时间两小时之后!');
        }
        if(empty($ary_orders['o_payment'])){
            if(isset($queue_obj)){ $queue_obj->unLock(); }
            $this->error('请选择支付方式!');
        }
        // 配送方式
        $str_code = $_SESSION['auto_code'];
        if(isset($ary_orders['originator'])) {
            if($ary_orders['originator'] == $_SESSION['auto_code']){
                //将其清除掉此时再按F5则无效
                unset($_SESSION["auto_code"]);
            }else{
                if(isset($queue_obj)){ $queue_obj->unLock(); }
                $this->error("订单提交中,请不要刷新本页面或重复提交表单");
            }
        }

        //是否开启门店提货
        $zt_info =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $is_zt = $zt_info['IS_ZT']['sc_value'];
        $ary_logistic_where = array(
            'lt_id' => $ary_datas ['lt_id']
        );
        $ary_field = array(
            'lc_abbreviation_name'
        );
        $ary_log = D('logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
        if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && $ary_orders['o_receiver_time'] == '' && $is_zt == 1){
            if(isset($queue_obj)){ $queue_obj->unLock(); }
            $this->error('请选择提货时间！');
        }

        return $ary_orders;
    }

    /**
     * 订单确认信息提交
     * @author Nick
     * @date 2015-11-20
     */
    public function doAdd() {
        $return_orders = false;
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_datas = $this->_post();
        $ary_config = D('SysConfig')->getConfigs("GY_CAHE");
        $queue_obj = null;
        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化开始
            $queue_name='groupbuyDoAdd';
            $queue_obj = new Queue($queue_name);
        }
        $ary_orders = $this->_beforeDoAdd($ary_datas, $queue_obj);
        $ary_member = session("Members");
        $ary_cart = session('bulk_cart');
        //团购判断
        $error_msg = array(
            '0' =>  '活动已失效！',
            '2' =>  '请先登录！',
            '3' =>  '活动尚未开始！',
            '4' =>  '活动已结束！',
            '5' =>  '已售罄！',
        );
        $data = D('Groupbuy')->getDetails($ary_cart['gp_id'], $ary_member, $ary_cart['pdt_id']);
        $buy_status = $data['buy_status'];
        if($buy_status != 1) {
            $this->error($error_msg[$buy_status]);
        }

        //跨境贸易
        $is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
            $total_tax_rate=0;
            $tax_rate_item_num=0;
            $order_item_nums=0;
        }
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_datas['ml_id']); //会员等级信息
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();

        $array_params = array(
            'ra_id' => '', //地址ID (必填)
            'm_id' => '',   //会员ID (必填)
            'pc_id' => '', //支付ID (必填)
            'lt_id' => '',  //物流ID (必填)
            'sgp' => '',    //g_id,pdt_id(规格ID),num,type,type_id;g_id,pdt_id(规格ID),num,type,type_id
            'resource' => '', //订单来源 (必填) (android或ios)
            'bonus' => '',      //可选，红包
            'cards' => '',      //可选，储值卡
            'csn'   => '',      //可选，优惠码
            'point' => '',      //可选，积分
            'type'	=>'',	       //可选，0：优惠券|1：红包|2：存储卡|4：积分
            'admin_id' => '',	//可选，管理员id
            'shipping_remarks' => '', //发货备注
        );
        D('ApiOrders')->fxOrderDoAdd();

        //
            if (!empty($ary_orders ['invoices_val']) && $ary_orders ['invoices_val'] == "1") {
                if (isset($ary_orders ['invoice_type']) && isset($ary_orders ['invoice_head'])) {
                    $ary_orders ['is_invoice'] = 1;
                    if ($ary_orders ['invoice_type'] == 2) {
                        // 如果为增值税发票，发票抬头默认为单位
                        $ary_orders ['invoice_head'] = 2;
                    } else {
                        if ($ary_orders ['invoice_head'] == 2) {
                            // 如果发票类型为普通发票，并且发票抬头为单位，将个人姓名删除
                            unset($ary_orders ['invoice_people']);
                        }
                        if ($ary_orders ['invoice_head'] == 1) {
                            // 如果发票类型为普通发票，并且发票抬头为个人，将单位删除
                            unset($ary_orders ['invoice_name']);
                        }
                    }
                    if (empty($ary_orders ['invoice_name'])) {
                        $ary_orders ['invoice_name'] = '个人';
                    } else {
                        $ary_orders ['invoice_name'] = $ary_orders ['invoice_name'];
                    }
                    if (isset($ary_orders ['invoice_content'])) {
                        $ary_orders ['invoice_content'] = $ary_orders ['invoice_content'];
                    }
                }
                else {
                    if (isset($ary_orders ['is_default']) && !empty($ary_orders ['is_default']) && !isset($ary_orders ['in_id'])) {
                        $res_invoice = D('InvoiceCollect')->getid($ary_orders ['is_default']);

                        if (!empty($res_invoice)) {
                            $ary_orders ['is_invoice'] = 1;
                            $ary_orders ['invoice_type'] = $res_invoice ['invoice_type'];
                            $ary_orders ['invoice_head'] = $res_invoice ['invoice_head'];
                            $ary_orders ['invoice_people'] = $res_invoice ['invoice_people'];
                            if (empty($res_invoice ['invoice_name'])) {
                                $ary_orders ['invoice_name'] = '个人';
                            } else {
                                $ary_orders ['invoice_name'] = $res_invoice ['invoice_name'];
                            }
                            $ary_orders ['invoice_content'] = $res_invoice ['invoice_content'];
                            // 如果是增值税发票，添加增值税发票信息

                            if ($ary_orders ['invoice_type'] == 2) {
                                // 纳税人识别号
                                $ary_orders ['invoice_identification_number'] = $res_invoice ['invoice_identification_number'];
                                // 注册地址
                                $ary_orders ['invoice_address'] = $res_invoice ['invoice_address'];
                                // 注册电话
                                $ary_orders ['invoice_phone'] = $res_invoice ['invoice_phone'];
                                // 开户银行
                                $ary_orders ['invoice_bank'] = $res_invoice ['invoice_bank'];
                                // 银行帐户
                                $ary_orders ['invoice_account'] = $res_invoice ['invoice_account'];
                            }
                        }
                    }
                    // 添加增值税发票
                    if (!empty($ary_orders ['in_id'])) {
                        $ary_res = M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            "id" => $ary_orders ['in_id']
                        ))->find();
                        $ary_orders ['invoice_type'] = $ary_res ['invoice_type'];
                        $ary_orders ['invoice_head'] = $ary_res ['invoice_head'];
                        // echo "<pre>";print_r($ary_res);exit;
                        $ary_orders ['is_invoice'] = 1;
                        if (empty($ary_res ['invoice_name'])) {
                            $ary_orders ['invoice_name'] = '个人';
                        } else {
                            $ary_orders ['invoice_name'] = $ary_orders ['invoice_name'];
                        }
                        // 个人姓名
                        $ary_orders ['invoice_people'] = $ary_orders ['invoice_people'];
                        // 纳税人识别号
                        $ary_orders ['invoice_identification_number'] = $ary_orders ['invoice_identification_number'];
                        // 注册地址
                        $ary_orders ['invoice_address'] = $ary_orders ['invoice_address'];
                        // 注册电话
                        $ary_orders ['invoice_phone'] = $ary_orders ['invoice_phone'];
                        // 开户银行
                        $ary_orders ['invoice_bank'] = $ary_orders ['invoice_bank'];
                        // 银行帐户
                        $ary_orders ['invoice_account'] = $ary_orders ['invoice_account'];
                        $ary_orders ['invoice_content'] = $ary_res ['invoice_content'];
                    }
                }
            }
            else {
                unset($ary_orders ['invoice_type']);
                unset($ary_orders ['invoice_head']);
                unset($ary_orders ['invoice_people']);
                unset($ary_orders ['invoice_name']);
                unset($ary_orders ['invoice_content']);
                unset($ary_orders ['invoices_val']);
            }
            $ary_receive_address = $this->cityRegion->getReceivingAddress($ary_datas ['m_id']);
            foreach ($ary_receive_address as $ara_k=>$ara_v){
                if($ara_v['ra_id'] == $ary_orders['ra_id']){
                    $default_address ['default_addr'] = $ara_v;
                }
            }
            if (isset($default_address ['default_addr'] ['ra_id'])) {
                // 收货人
                $ary_orders ['o_receiver_name'] = $default_address ['default_addr'] ['ra_name'];
                //收货人身份证号
                if($default_address ['default_addr'] ['ra_id_card']){
                    $ary_orders ['o_receiver_idcard'] = $default_address ['default_addr'] ['ra_id_card'];
                }
                // 收货人电话
                $ary_orders ['o_receiver_telphone'] = trim($default_address ['default_addr'] ['ra_phone']);
                // 收货人手机
                $ary_orders ['o_receiver_mobile'] = $default_address ['default_addr'] ['ra_mobile_phone'];
                // 收货人邮编
                $ary_orders ['o_receiver_zipcode'] = $default_address ['default_addr'] ['ra_post_code'];
                // 收货人地址
                $ary_orders ['o_receiver_address'] = $default_address ['default_addr'] ['ra_detail'];
                $ary_city_data = $this->cityRegion->getFullAddressId($default_address ['default_addr'] ['cr_id']);

                // 收货人省份
                $ary_orders ['o_receiver_state'] = $this->cityRegion->getAddressName($ary_city_data [1]);

                // 收货人城市
                $ary_orders ['o_receiver_city'] = $this->cityRegion->getAddressName($ary_city_data [2]);

                // 收货人地区
                $ary_orders ['o_receiver_county'] = $this->cityRegion->getAddressName($ary_city_data [3]);
            }
            elseif($ary_orders['ra_id'] == 'other') {
                //使用临时收货地址
                // 收货人
                $ary_orders ['o_receiver_name'] = $ary_orders['ra_name'];
                //收货人身份证号
                if($ary_orders['ra_id_card']){
                    $ary_orders ['o_receiver_idcard'] = $ary_orders['ra_id_card'];
                }
                // 收货人电话
                // $ary_orders ['o_receiver_telphone'] = trim($default_address ['default_addr'] ['ra_phone']);
                $ary_receiver_telphone = array();
                if(!empty($ary_orders['ra_phone_area'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone_area']);
                if(!empty($ary_orders['ra_phone'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone']);
                if(!empty($ary_orders['ra_phone_ext'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone_ext']);
                $ary_orders ['ra_id'] = 0;
                $ary_orders ['o_receiver_telphone'] = !empty($ary_receiver_telphone) ? implode('-',$ary_receiver_telphone): '';
                // 收货人手机
                $ary_orders ['o_receiver_mobile'] = trim($ary_orders['ra_mobile_phone']);
                // 收货人邮编

                $ary_orders ['o_receiver_zipcode'] = trim($ary_orders['ra_post_code']);

                // 收货人地址

                $ary_orders ['o_receiver_address'] = trim($ary_orders['ra_detail']);


                // 收货人省份
                $ary_orders ['o_receiver_state'] = $this->cityRegion->getAddressName($ary_orders['province']);

                // 收货人城市
                $ary_orders ['o_receiver_city'] = $this->cityRegion->getAddressName($ary_orders['city']);

                // 收货人地区
                $ary_orders ['o_receiver_county'] = $this->cityRegion->getAddressName($ary_orders['region']);

            }

            if(empty($ary_orders['o_receiver_city'])){
                if(!empty($ary_orders['ra_id'])){
                    $ary_address = D('CityRegion')->getReceivingAddress($ary_datas ['m_id'],$ary_orders['ra_id']);
                    $ary_addr = explode(' ',$ary_address['address']);
                    if(!empty($ary_addr[1])){
                        $ary_orders ['o_receiver_state'] = $ary_addr[0];
                        $ary_orders ['o_receiver_city'] = $ary_addr[1];
                        $ary_orders ['o_receiver_county'] = $ary_addr[2];
                    }else{
                        $_SESSION['auto_code'] = $str_code;
                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                            $queue_obj->unLock();
                        }
                        $this->error('请检查您的收货地址是否正确');
                        exit();
                    }
                }else{
                    $_SESSION['auto_code'] = $str_code;
                    if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                        $queue_obj->unLock();
                    }
                    $this->error('请检查您的收货地址是否正确');
                    exit();
                }
            }
            //团购区域限售
            if(isset($ary_orders ['gp_id'])){
                $is_can_buy = D('CityRegion')->isGroupCanBuy($ary_orders['o_receiver_city'],$ary_orders ['gp_id']);
                if($is_can_buy == false){
                    $_SESSION['auto_code'] = $str_code;
                    if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                        $queue_obj->unLock();
                    }
                    $this->error('您收货地址所对应的区域不支持购买此商品');
                    exit();
                }
            }
            //  print_r($ary_orders);exit;
            // 会员id
            $ary_orders ['m_id'] = $ary_datas ['m_id'];
            // 订单id
            $ary_orders ['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
            // 物流费用
            $ary_goods = array();
            if(isset($ary_orders ['p_id'])) {
                //预售订单计算物流费用
                $ary_goods[$_SESSION['presale_cart']['pdt_id']] = $_SESSION['presale_cart'];
            } else if(isset($ary_orders ['sp_id'])) {
                //秒杀物流费用
                $ary_goods[$_SESSION['spike_cart']['pdt_id']] = $_SESSION['spike_cart'];
            } else if(isset($ary_orders ['gp_id'])) {
                //团购商品物流费用
                $ary_goods[$_SESSION['bulk_cart']['pdt_id']] = $_SESSION['bulk_cart'];
            } else {
                //普通订单商品
                $ary_goods = $ary_cart;
            }

            if (empty($ary_orders ['lt_id'])) {
                $this->success(L('SELECT_LOGISTIC'));
                exit();
            }
            //获取团购商品金额方式和普通商品不同
            $ary_orders ['o_goods_all_price'] = 0;
            $m_id = $_SESSION ['Members'] ['m_id'];
            //团购商品
            if (isset($ary_orders ['gp_id'])) {
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {

                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 5) {
                            // 获取团购价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 5, $ary_orders ['gp_id']);
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);

                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                        }
                    }
                    $logistic_price = $ary_orders ['o_cost_freight'];
                }
            }
            //秒杀商品
            else if(isset($ary_orders ['sp_id'])){
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {

                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 7) {
                            // 获取秒杀价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 7, $ary_orders ['sp_id']);
                            // echo "<pre>";print_r($array_all_price);exit;
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                        }
                    }
                    $logistic_price = $ary_orders ['o_cost_freight'];
                }
            }
            else if(isset($ary_orders ['integral_id'])){
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 11) {
                            // 获取预售价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 11, $ary_orders ['integral_id']);
                            // echo "<pre>";print_r($array_all_price);exit;
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                        }
                    }
                    $logistic_price = $ary_orders ['o_cost_freight'];
                }
            }
            else if(isset($ary_orders ['p_id'])){
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {

                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 8) {
                            // 获取预售价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 8, $ary_orders ['p_id']);
                            // echo "<pre>";print_r($array_all_price);exit;
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                            // echo "<pre>";print_r($ary_orders);exit;
                        }
                    }
                    $logistic_price = $ary_orders ['o_cost_freight'];
                }
            }
            //普通商品
            else {
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $key => $val) {
                        if ($val['type'] == '0') {
                            $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                            $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                        }
                    }
                }
                $pro_datas = D('Promotion')->calShopCartPro($ary_datas ['m_id'], $ary_cart);
                $subtotal = $pro_datas ['subtotal'];
                unset($pro_datas ['subtotal']);
                // 商品总价
                $promotion_total_price = '0';
                $promotion_price = '0';
                $int_total_promotion_price = $int_total_promotion_price = $i = 0;
                //赠品数组
                $gifts_cart = array();
                foreach ($pro_datas as $keys => $vals) {
                    $int_promotion_count = count($vals['products']);
                    foreach ($vals['products'] as $key => $val) {
                        $arr_products = $this->cart->getProductInfo(array($key => $val));

                        if ($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6') {
                            foreach ($arr_products[0] as &$provals) {
                                $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_datas['m_id'], $provals['g_id']);
                            }
                        }
                        $pro_datas[$keys]['products'][$key] = $arr_products[0];
                        $pro_data[$key] = $val;
                        $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
                        //购物车优惠优惠金额放到订单明细里拆分
                        if($keys != 0 && !empty($vals['pro_goods_discount'])){
                            if($int_promotion_count == $i+1){
                                $pro_datas [$keys] ['products'] [$key]['promotion_price'] = $vals['pro_goods_discount']-$int_total_promotion_price;
                            }else{
                                $pro_datas[$keys]['products'][$key]['promotion_price'] = sprintf("%.2f", ($arr_products [0]['f_price']*$arr_products [0]['pdt_nums']/$vals['goods_total_price'])*$vals['pro_goods_discount']);
                                $int_total_promotion_price = $int_total_promotion_price+$pro_datas[$keys]['products'][$key]['promotion_price'];
                            }
                        }
                        //跨境贸易
                        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
                            if(isset($arr_products [0]['g_tax_rate']) && !empty($arr_products [0]['g_tax_rate'])){
                                $tax_rate_item_num +=$arr_products [0]['pdt_nums'];
                                $order_item_nums += $arr_products [0]['pdt_nums'];
                                if(isset($vals['pro_goods_total_price'])){
                                    $total_tax_rate += ($arr_products [0]['pdt_momery']-$pro_datas [$keys] ['products'][$key]['promotion_price'])*$arr_products [0]['g_tax_rate'];
                                }else{
                                    $total_tax_rate += $arr_products [0]['pdt_momery']*$arr_products [0]['g_tax_rate'];
                                }
                            }else{
                                $order_item_nums += $arr_products [0]['pdt_nums'];
                            }
                        }
                    }
                    //赠品数组
                    if (!empty($vals['gifts'])) {
                        foreach ($vals['gifts'] as $gifts) {
                            //随机取一个pdt_id
                            $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                            $gifts_cart[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2);
                        }
                    }
                    $promotion_total_price += $vals['goods_total_price'];     //商品总价

                    if ($keys != '0') {
                        $promotion_price += $vals['pro_goods_discount'];
                    }
                    $i++;
                }
                //跨境贸易税额起征点
                if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
                    $foreign_info=D('SysConfig')->getForeignOrderCfg();
                    if(!empty($foreign_info['IS_AUTO_TAX_THRESHOLD']) && $foreign_info['TAX_THRESHOLD'] >= $total_tax_rate){
                        $total_tax_rate=0;
                    }
                }
                if(!empty($gifts_cart)){
                    $ary_tmp_cart = array_merge($ary_goods,$gifts_cart);
                    foreach($ary_tmp_cart as $atck=>$atcv){
                        $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                        unset($ary_tmp_cart[$atck]);
                    }
                }else{
                    $ary_tmp_cart = $ary_goods;
                }
                foreach ($pro_datas as $pro_data) {
                    if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                        foreach($pro_data['products'] as $proDatK=>$proDatV){
                            unset($ary_tmp_cart[$proDatK]);
                        }
                    }
                    if (!empty($pro_data ['pmn_class'])) {//订单只要包含一个促销商品，整个订单为促销，不返点
                        $User_Grade['ml_rebate'] = 0;
                    }
                }
                if(empty($ary_tmp_cart)){
                    $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
                }
                $logistic_price = $this->logistic->getLogisticPrice($this->_request('lt_id'), $ary_tmp_cart);
                //订单商品总价（销售价格带促销）
                $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
                //商品销售总价
                $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $promotion_total_price);
                $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
            }

            //判断会员等级是否包邮
            if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                $logistic_price = 0;
            }
            //物流公司设置包邮额度
            $lt_expressions = json_decode(M('logistic_type')->where(array('lt_id'=>$this->_request('lt_id')))->getField('lt_expressions'),true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_orders['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $logistic_price = 0;
            }
            //物流费用
            $ary_orders ['o_cost_freight'] = $logistic_price;
            // 优惠券金额
            if (isset($ary_orders ['coupon_input'])) {
                $str_csn = $ary_orders ['coupon_input'];

                $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
                $o_coupon_menoy = 0;
                if($ary_coupon['status'] == 'success'){
                    foreach ($ary_coupon['msg'] as $coupon){
                        if ($coupon ['c_condition_money'] > 0 && $ary_orders ['o_goods_all_price'] < $coupon ['c_condition_money']) {
                            $string_res = '编号'.$coupon['ci_sn'].'优惠券不满足使用条件';
                            $this->error($string_res);
                            break;
                        }
                        if($coupon['c_type'] == '1'){
                            //计算参与优惠券使用的商品
                            if($coupon['gids'] == 'All'){
                                $o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$ary_orders ['o_goods_all_price']);
                            }else{
                                //计算可以使用优惠券总金额
                                $coupon_all_price = 0;
                                $tmp_coupon_all_price = 0;
                                $tmp_goods_total_price = 0;
                                //参与优惠券的数量
                                foreach ($pro_datas as $keys => $vals) {
                                    //是否可以使用优惠券
                                    $is_exsit_coupon = 0;
                                    foreach ($vals['products'] as $key => $val) {
                                        $arr_products = $this->cart->getProductInfo(array($key => $val));
                                        if ($arr_products[0][0]['type'] == '4') {
                                            foreach ($arr_products[0] as $provals) {
                                                if(in_array($vals['g_id'],$coupon['gids'])){
                                                    $coupon_all_price += ($provals['pdt_price']*$provals['pdt_nums']);
                                                }
                                                $tmp_goods_total_price +=$provals['pdt_price']*$provals['pdt_nums'];
                                            }
                                        }
                                        if(in_array($val['g_id'],$coupon['gids'])){
                                            $coupon_all_price += ($val['pdt_price']*$val['pdt_nums']);
                                        }
                                        $tmp_goods_total_price +=$val['pdt_price']*$val['pdt_nums'];
                                    }
                                    if($coupon_all_price>0){
                                        $coupon_all_price = sprintf('%.2f',$coupon_all_price-($coupon_all_price/$tmp_goods_total_price)*$vals['pro_goods_discount']);
                                    }
                                }

                                /**
                                foreach ($pro_datas as $keys => $vals) {
                                //是否可以使用优惠券
                                $is_exsit_coupon = 0;
                                foreach ($vals['products'] as $key => $val) {
                                $arr_products = $this->cart->getProductInfo(array($key => $val));
                                if ($arr_products[0][0]['type'] == '4') {
                                foreach ($arr_products[0] as $provals) {
                                if(in_array($vals['g_id'],$coupon['gids'])){
                                $is_exsit_coupon = 1;break;
                                }
                                }
                                }
                                if(in_array($val['g_id'],$coupon['gids'])){
                                $is_exsit_coupon = 1;break;
                                }
                                }
                                if($is_exsit_coupon == 1){
                                //$coupon_all_price += $vals['goods_total_price'];     //商品总价
                                $coupon_all_price += ($val['pdt_sale_price']*$val['pdt_nums']);     //符合条件商品总价
                                }
                                }**/
                            }
                            $o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$coupon_all_price);
                        }else{
                            $o_coupon_menoy += $coupon['c_money'];
                        }
                    }
                    $ary_orders ['o_coupon_menoy'] = $o_coupon_menoy;
                }
            }
            //使用红包
            if(isset($ary_orders['bonus_input'])){
                $bonus_price = sprintf("%0.2f", $ary_orders['bonus_input']);
                $ary_orders['o_bonus_money'] = $bonus_price;
            }
            //使用积分
            if(isset($ary_orders['point_input'])){
                //需要冻结的积分数,下面生成订单成功要加到积分商品冻结的积分上
                $freeze_point = $ary_orders['point_input'];
                $ary_data = D('PointConfig')->getConfigs();
                $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
                //积分抵扣的总金额
                $point_price = sprintf("%0.2f", (0.01/$consumed_points)*$freeze_point);
                $ary_orders['o_point_money'] = $point_price;
            }
            // 订单总价 商品会员折扣价-优惠券金额-红包金额-储值卡金额-金币-积分抵扣金额
            if (!isset($ary_orders ['gp_id']) && !isset($ary_orders['p_id']) && !isset($ary_orders['sp_id']) &&!isset($ary_orders ['integral_id'])) {
                //$all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'] - $bonus_price - $cards_price - $jlb_price - $point_price;
                $all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'] - $bonus_price - $point_price;
            }else{
                $all_price = $o_all_price;
            }
            if ($all_price <= 0) {
                $all_price = 0;
            }
            // 订单应付总价 订单总价+运费
            $all_price += $ary_orders ['o_cost_freight'];
            //跨境贸易
            if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
                $foreign_info=D('SysConfig')->getForeignOrderCfg();
                $all_price += $total_tax_rate; //税额
                $ary_orders['o_tax_rate'] = $total_tax_rate;
                if($foreign_info['IS_AUTO_LIMIT_ORDER_AMOUNT']==1 && $foreign_info['LIMIT_ORDER_AMOUNT']> 0 ){
                    if($all_price > $foreign_info['LIMIT_ORDER_AMOUNT'] && $total_tax_rate > 0 && $order_item_nums >1 ){
                        $mgs='订单总价不可以超过'.$foreign_info['LIMIT_ORDER_AMOUNT'].'元，请重新选择商品';
                        $this->error($mgs, array('返回购物车' => U('Ucenter/Cart/pageList')));exit;
                    }
                }
            }
            if(empty($ary_orders ['o_goods_all_price'])){
                $this->error('没有要购买的商品，请重新选择商品', array('返回购物车' => U('Ucenter/Cart/pageList')));exit;
            }
            //当订单总价为0 且物流也为0时，订单状态为已支付
            if(0 == $all_price) {
                $ary_orders ['o_pay_status'] = 1;
                $ary_orders ['o_status'] = 1;
            }
            $ary_orders ['o_all_price'] = sprintf("%0.3f", $all_price);
            $ary_orders ['o_buyer_comments'] = $ary_orders ['o_buyer_comments'];
            // 是否预售单
            if (isset($ary_orders ['g_pre_sale_status']) && $ary_orders ['g_pre_sale_status'] == 1) {
                $ary_orders ['o_pre_sale'] = 1;
            }
            if (empty($ary_orders ['o_receiver_county'])) { // 没有区时
                unset($ary_orders ['o_receiver_county']);
            }
            if (!isset($ary_orders ['gp_id']) && !empty($promotion_price)) {
                //订单优惠金额
                $ary_orders ['o_discount'] = sprintf("%0.2f", $promotion_price);
            }
            // 发货备注
            if (!empty($ary_orders ['shipping_remarks'])) {
                $ary_orders ['o_shipping_remarks'] = $ary_orders ['shipping_remarks'];
                unset($ary_orders ['shipping_remarks']);
            }
            $ary_orders_goods = $this->cart->getProductInfo($ary_cart);

            // 管理员操作者ID
            if ($ary_datas ['admin_id']) {
                $ary_orders ['o_addorder_id'] = $ary_datas ['admin_id'];
            }
            //促销信息存起来暂时隐藏
            //$ary_orders['promotion'] = serialize($pro_datas);
            if(empty($ary_orders['o_goods_all_price'])){
                $orders->rollback();
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('商品金额为0，保存失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                exit();
            }
            if(empty($ary_orders['o_goods_all_price'])){
                $orders->rollback();
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('商品金额为0，保存失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                exit();
            }
            //是否是匿名购买
            if($ary_orders['is_anonymous'] != '1'){
                unset($ary_orders['is_anonymous']);
            }
            $o_ip = $this->getIp();
            if(isset($o_ip)){
                $ary_orders['o_ip'] = $o_ip;
            }
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 ){
                if($ary_orders['o_payment'] == 6){
                    $ary_orders['o_audit'] = 1;
                }
                if($ary_orders['o_all_price']<=0){
                    $ary_orders['o_audit'] = 1;
                }
            }
            $bool_orders = D('Orders')->doInsert($ary_orders);

            // $bool_orders = true;
            if (!$bool_orders) {
                $orders->rollback();
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('订单生成失败', array('失败' => U('Ucenter/Orders/OrderFail') ));
                exit();
            }
            else {
                //当订单总价为0 且物流也为0时，订单状态为已支付时，日志记录一下
                if(0 == $all_price) {
                    $ary_orders_log = array(
                        'o_id' => $ary_orders ['o_id'],
                        'ol_behavior' => '积分/优惠券支付',
                        'ol_uname' => $_SESSION ['Members'] ['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    D('OrdersLog')->add($ary_orders_log);
                }

                $ary_orders_items = array();
                $ary_orders_goods = $this->cart->getProductInfo($ary_cart);
                if (!empty($gifts_cart)) {
                    $ary_gifts_goods = $this->cart->getProductInfo($gifts_cart);
                    if (!empty($ary_gifts_goods)) {
                        foreach ($ary_gifts_goods as $gift) {
                            array_push($ary_orders_goods, $gift);
                        }
                    }
                }
                if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                    $total_consume_point = 0; // 消耗积分
                    $int_pdt_sale_price = 0; // 货品销售原价总和
                    $gifts_point_reward = '0'; //有设置购商品赠积分所获取的积分数
                    $gifts_point_goods_price  = '0'; //设置了购商品赠积分的商品的总价
                    //获取明细分配的金额

                    $ary_orders_goods = D('OrdersItems')->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);

                    foreach ($ary_orders_goods as $k => $v) {
                        $ary_orders_items = array();
                        //组合商品
                        if ($v ['type'] == 3) {
                            $combo_list = D('ReletedCombinationGoods')->getComboList($v ['pdt_id']);
                            if (!empty($combo_list)) {
                                foreach ($combo_list as $combo) {
                                    // 订单id
                                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                    // 商品id
                                    $combo_item_data = D('GoodsProducts')->Search(array('pdt_id' => $combo ['releted_pdt_id']), array('g_sn','g_id'));
                                    $ary_orders_items ['g_id'] = $combo_item_data ['g_id'];
                                    // 组合商品ID
                                    $ary_orders_items ['fc_id'] = $v ['pdt_id'];
                                    // 货品id
                                    $ary_orders_items ['pdt_id'] = $combo ['releted_pdt_id'];
                                    // 类型id
                                    $ary_orders_items ['gt_id'] = $combo ['gt_id'];
                                    // 商品sn
                                    $ary_orders_items ['g_sn'] = $combo_item_data ['g_sn'];
                                    // 货品sn
                                    $ary_orders_items ['pdt_sn'] = $combo ['pdt_sn'];
                                    // 商品名字
                                    $combo_good_data = D('GoodsInfo')->Search(array('g_id' => $combo_item_data ['g_id']), array('g_name'));
                                    $ary_orders_items ['oi_g_name'] = $combo_good_data ['g_name'];
                                    // 成本价
                                    $ary_orders_items ['oi_cost_price'] = $combo ['pdt_cost_price'];
                                    // 货品销售原价
                                    $ary_orders_items ['pdt_sale_price'] = $combo ['pdt_sale_price'];
                                    // 购买单价
                                    $ary_orders_items ['oi_price'] = $combo ['com_price'];
                                    // 组合商品
                                    $ary_orders_items ['oi_type'] = 3;

                                    $int_pdt_sale_price += $combo ['com_price'] * $combo ['com_nums'];

                                    // 商品数量
                                    $ary_orders_items ['oi_nums'] = $combo ['com_nums'] * $v ['pdt_nums'];
                                    //返点比例
                                    if (!empty($User_Grade['ml_rebate'])) {
                                        $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                    }
                                    //等级折扣
                                    if (!empty($User_Grade['ml_discount'])) {
                                        $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                    }
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('订单明细生成失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                        exit();
                                    }
                                    // 商品库存扣除
                                    $ary_payment_where = array(
                                        'pc_id' => $ary_orders ['o_payment']
                                    );
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                        // by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $ary_orders_items ['g_id']))->find();
                                        if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                            //查询库存,如果库存数为负数则不再扣除库存
                                            $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                            if(0 >= $int_pdt_stock['pdt_stock']){
                                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                    $queue_obj->unLock();
                                                }
                                                $this->error('该货品已售完！');
                                                die();
                                            }
                                            if($int_pdt_stock['pdt_stock']<$ary_orders_items ['oi_nums']){
                                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                    $queue_obj->unLock();
                                                }
                                                $this->error('该货品已售完！');
                                                die();
                                            }
                                            $array_result = D('GoodsProducts')->UpdateStock($combo ['releted_pdt_id'], $ary_orders_items ['oi_nums']);
                                            if (false == $array_result ["status"]) {
                                                D('GoodsProducts')->rollback();
                                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                    $queue_obj->unLock();
                                                }
                                                $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // 自由推荐商品
                        elseif ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
                            foreach ($v as $key => $item_info) {
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $item_info ['g_id'];
                                // 货品id
                                $ary_orders_items ['pdt_id'] = $item_info ['pdt_id'];
                                // 类型id
                                $ary_orders_items ['gt_id'] = $item_info ['gt_id'];
                                // 商品sn
                                $ary_orders_items ['g_sn'] = $item_info ['g_sn'];
                                // o_sn
                                // $ary_orders_items['g_id'] = $v['g_id'];
                                // 货品sn
                                $ary_orders_items ['pdt_sn'] = $item_info ['pdt_sn'];
                                // 商品名字
                                $ary_orders_items ['oi_g_name'] = $item_info ['g_name'];
                                // 成本价
                                $ary_orders_items ['oi_cost_price'] = $item_info ['pdt_cost_price'];
                                // 货品销售原价
                                $ary_orders_items ['pdt_sale_price'] = $item_info ['pdt_sale_price'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] = $item_info ['pdt_momery'];
                                $ary_orders_items['promotion'] = $item_info['pdt_rule_name'];
                                // 自由组合ID
                                $ary_orders_items ['fc_id'] = isset($item_info ['fc_id']) ? $item_info ['fc_id'] : $item_info ['fr_id'];
                                // 商品积分
                                if (isset($v [0] ['type']) && $v [0] ['type'] == 4 && $item_info['fc_id'] != '') {
                                    $ary_orders_items ['oi_type'] = 4;
                                    $int_pdt_sale_price += $item_info ['pdt_sale_price'] * $item_info ['pdt_nums'];
                                } elseif (isset($v [0] ['type']) && $v [0] ['type'] == 6 && $item_info['fr_id'] != '') {
                                    $ary_orders_items ['oi_type'] = 6;
                                    $int_pdt_sale_price += $item_info ['pdt_sale_price'] * $item_info ['pdt_nums'];
                                } else {
                                    unset($ary_orders_items['fc_id']);
                                    unset($ary_orders_items['promotion']);
                                    $ary_orders_items ['oi_type'] = 0;
                                }
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $item_info ['pdt_nums'];
                                //返点比例
                                if (!empty($User_Grade['ml_rebate'])) {
                                    $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                }
                                //等级折扣
                                if (!empty($User_Grade['ml_discount'])) {
                                    $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                }
                                if(!empty($item_info['oi_coupon_menoy'])){
                                    $ary_orders_items['oi_coupon_menoy'] = $item_info['oi_coupon_menoy'];
                                }
                                if(!empty($item_info['oi_bonus_money'])){
                                    $ary_orders_items['oi_bonus_money'] = $item_info['oi_bonus_money'];
                                }
                                if(!empty($item_info['oi_cards_money'])){
                                    $ary_orders_items['oi_cards_money'] = $item_info['oi_cards_money'];
                                }
                                if(!empty($item_info['oi_jlb_money'])){
                                    $ary_orders_items['oi_jlb_money'] = $item_info['oi_jlb_money'];
                                }
                                if(!empty($item_info['oi_point_money'])){
                                    $ary_orders_items['oi_point_money'] = $item_info['oi_point_money'];
                                }
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $this->error('订单明细新增失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                    if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                        $queue_obj->unLock();
                                    }
                                    exit();
                                }
                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $item_info ['g_id']))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        //查询库存,如果库存数为负数则不再扣除库存
                                        $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
                                            if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                $queue_obj->unLock();
                                            }
                                            $this->error('该货品已售完！');
                                            die();
                                        }
                                        if($int_pdt_stock['pdt_stock']<$item_info ['pdt_nums']){
                                            if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                $queue_obj->unLock();
                                            }
                                            $this->error('该货品已售完！');
                                            die();
                                        }
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $item_info ['pdt_nums']);
                                        if (false == $array_result ["status"]) {
                                            D('GoodsProducts')->rollback();
                                            if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                                $queue_obj->unLock();
                                            }
                                            $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            }
                        }
                        //秒杀商品
                        elseif($v ['type'] == '7'){
                            // 订单id
                            $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                            // 商品id
                            $ary_orders_items ['g_id'] = $v ['g_id'];
                            // 秒杀商品ID,取一下
                            /**
                            $fc_id = M('spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id'],
                            'sp_status' => '1'
                            ))->getField('sp_id');
                             **/
                            $ary_orders_items ['fc_id'] = $ary_orders['sp_id'];
                            // 货品id
                            $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                            // 类型id
                            $ary_orders_items ['gt_id'] = $v ['gt_id'];
                            // 商品sn
                            $ary_orders_items ['g_sn'] = $v ['g_sn'];
                            // 货品sn
                            $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                            // 商品名字
                            $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                            // 成本价
                            $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                            // 货品销售原价
                            $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                            // 秒杀商品
                            $ary_orders_items ['oi_type'] = $v ['type'];
                            // 购买单价
                            $ary_orders_items ['oi_price'] =  $array_all_price ['discount_price'];
                            // 商品数量
                            $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                            //返点比例
                            if (!empty($User_Grade['ml_rebate'])) {
                                $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                            }
                            // echo "<pre>";print_R($v);exit;
                            //等级折扣
                            if (!empty($User_Grade['ml_discount'])) {
                                $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                            }
                            //dump($ary_orders_items);die();
                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('订单明细新增失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            $retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$ary_orders['num']);
                            if (!$retun_buy_nums) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('更新秒杀量失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            // 商品库存扣除
                            $ary_payment_where = array(
                                'pc_id' => $ary_orders ['o_payment']
                            );
                            $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                            if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                // by Mithern 扣除可下单库存生成库存调整单
                                $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                                if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                    //查询库存,如果库存数为负数则不再扣除库存
                                    $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                    if(0 >= $int_pdt_stock['pdt_stock']){
                                        M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                    }
                                    if($int_pdt_stock['pdt_stock']<$ary_orders ['num']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                    if (false == $array_result ["status"]) {
                                        D('GoodsProducts')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    }
                                }
                            }

                        }
                        //团购商品
                        elseif ($v ['type'] == '5') { // 团购商品
                            // 订单id
                            $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                            // 商品id
                            $ary_orders_items ['g_id'] = $v ['g_id'];
                            // 团购商品ID,取一下
                            /**
                            $fc_id = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id'],
                            'deleted' => '0',
                            'is_active' => '1'
                            ))->getField('gp_id');
                            $ary_orders_items ['fc_id'] = $fc_id;
                             **/
                            $ary_orders_items ['fc_id'] = $ary_orders['gp_id'];

                            // 货品id
                            $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                            // 类型id
                            $ary_orders_items ['gt_id'] = $v ['gt_id'];
                            // 商品sn
                            $ary_orders_items ['g_sn'] = $v ['g_sn'];
                            // 货品sn
                            $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                            // 商品名字
                            $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                            // 成本价
                            $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                            // 货品销售原价
                            $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                            // 团购商品
                            $ary_orders_items ['oi_type'] = $v ['type'];
                            // 购买单价
                            $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $array_all_price ['discount_price'];
                            // 商品数量
                            $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                            //返点比例
                            if (!empty($User_Grade['ml_rebate'])) {
                                $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                            }
                            //等级折扣
                            if (!empty($User_Grade['ml_discount'])) {
                                $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                            }
                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                $orders->rollback();
                                $queue_obj->unLock();
                                $this->error('订单明细新增失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$ary_orders['num']);
                            if (!$retun_buy_nums) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('更新团购量失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }

                            // 生成团购日志
                            $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
                            $ary_gb_log ['gp_id'] = $ary_orders ['gp_id'];
                            $ary_gb_log ['m_id'] = $_SESSION ['Members'] ['m_id'];
                            $ary_gb_log ['g_id'] = $v ['g_id'];
                            $ary_gb_log ['num'] = $ary_orders ['num'];
                            if (false === M('groupbuy_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('团购日志生成失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }

                            // 商品库存扣除
                            $ary_payment_where = array(
                                'pc_id' => $ary_orders ['o_payment']
                            );
                            $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                            if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                // by Mithern 扣除可下单库存生成库存调整单
                                $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                                if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                    //查询库存,如果库存数为负数则不再扣除库存
                                    $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                    if(0 >= $int_pdt_stock['pdt_stock']){
                                        M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                    }
                                    if($int_pdt_stock['pdt_stock']<$ary_orders ['num']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                    if (false == $array_result ["status"]) {
                                        D('GoodsProducts')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    }
                                }
                            }
                        }elseif($v ['type'] == '8'){
                            // 订单id
                            $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                            // 商品id
                            $ary_orders_items ['g_id'] = $v ['g_id'];
                            // 预售商品ID
                            $fc_id = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'g_id' => $v ['g_id'],
                                'deleted' => '0',
                                'is_active' => '1'
                            ))->getField('p_id');
                            $ary_orders_items ['fc_id'] = $fc_id;
                            // 货品id
                            $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                            // 类型id
                            $ary_orders_items ['gt_id'] = $v ['gt_id'];
                            // 商品sn
                            $ary_orders_items ['g_sn'] = $v ['g_sn'];
                            // 货品sn
                            $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                            // 商品名字
                            $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                            // 成本价
                            $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                            // 货品销售原价
                            $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                            // 预售商品
                            $ary_orders_items ['oi_type'] = $v ['type'];
                            // 购买单价
                            $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $array_all_price ['discount_price'];
                            // 商品数量
                            $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                            //返点比例
                            if (!empty($User_Grade['ml_rebate'])) {
                                $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                            }
                            //等级折扣
                            if (!empty($User_Grade['ml_discount'])) {
                                $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                            }
                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('订单明细新增失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            $retun_buy_nums=D("Presale")->where(array('p_id' => $ary_orders_items['fc_id']))->setInc("p_now_number",$ary_orders['num']);
                            if(!$retun_buy_nums){
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('更新预售数量失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            // 生成预售日志
                            $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
                            $ary_gb_log ['p_id'] = $ary_orders ['p_id'];
                            $ary_gb_log ['m_id'] = $_SESSION ['Members'] ['m_id'];
                            $ary_gb_log ['g_id'] = $v ['g_id'];
                            $ary_gb_log ['num'] = $ary_orders ['num'];
                            if (false === M('presale_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('预售日志失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }

                            // 商品库存扣除
                            $ary_payment_where = array(
                                'pc_id' => $ary_orders ['o_payment']
                            );
                            $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                            if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                // by Mithern 扣除可下单库存生成库存调整单
                                $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                                if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                    //查询库存,如果库存数为负数则不再扣除库存
                                    $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                    if(0 >= $int_pdt_stock['pdt_stock']){
                                        M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                    }
                                    if($int_pdt_stock['pdt_stock']<$ary_orders ['num']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                    if (false == $array_result ["status"]) {
                                        D('GoodsProducts')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    }
                                }
                            }
                        }elseif($v ['type'] == '11'){//积分+金额
                            // 订单id
                            $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                            // 商品id
                            $ary_orders_items ['g_id'] = $v ['g_id'];
                            // 秒杀商品ID,取一下
                            /**
                            $fc_id = M('spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id'],
                            'sp_status' => '1'
                            ))->getField('sp_id');
                             **/
                            $ary_orders_items ['fc_id'] = $ary_orders['integral_id'];
                            // 货品id
                            $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                            // 类型id
                            $ary_orders_items ['gt_id'] = $v ['gt_id'];
                            // 商品sn
                            $ary_orders_items ['g_sn'] = $v ['g_sn'];
                            // 货品sn
                            $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                            // 商品名字
                            $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                            // 成本价
                            $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                            // 货品销售原价
                            $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                            // 秒杀商品
                            $ary_orders_items ['oi_type'] = 11;//积分+金额兑换
                            // 购买单价
                            $ary_orders_items ['oi_price'] =  $array_all_price ['discount_price'];
                            // 商品数量
                            $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                            //返点比例
                            if (!empty($User_Grade['ml_rebate'])) {
                                $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                            }
                            // echo "<pre>";print_R($v);exit;
                            //等级折扣
                            if (!empty($User_Grade['ml_discount'])) {
                                $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                            }

                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);

                            if (!$bool_orders_items) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('订单明细新增失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            $retun_buy_nums=D("Integral")->where(array('integral_id' => $ary_orders_items['fc_id']))->setInc("integral_now_number",$ary_orders['num']);
                            if (!$retun_buy_nums) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('更新积分兑换量失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            // 商品库存扣除
                            $ary_payment_where = array(
                                'pc_id' => $ary_orders ['o_payment']
                            );
                            $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                            if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                // by Mithern 扣除可下单库存生成库存调整单
                                $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                                if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                    //查询库存,如果库存数为负数则不再扣除库存
                                    $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                    if(0 >= $int_pdt_stock['pdt_stock']){
                                        M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                    }
                                    if($int_pdt_stock['pdt_stock']<$ary_orders ['num']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                    if (false == $array_result ["status"]) {
                                        D('GoodsProducts')->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    }
                                }
                            }

                        } else {
                            if (!empty($v['rule_info']['name'])) {
                                $v['pmn_name'] = $v['rule_info']['name'];
                            }
                            // 订单id
                            $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                            // 商品id
                            $ary_orders_items ['g_id'] = $v ['g_id'];
                            // 货品id
                            $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                            // 类型id
                            $ary_orders_items ['gt_id'] = $v ['gt_id'];
                            // 商品sn
                            $ary_orders_items ['g_sn'] = $v ['g_sn'];
                            // o_sn
                            // $ary_orders_items['g_id'] = $v['g_id'];
                            // 货品sn
                            $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                            // 商品名字
                            $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                            // 成本价
                            $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                            // 货品销售原价
                            $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                            // 购买单价
                            $ary_orders_items ['oi_price'] = $v ['pdt_price'];
                            //返点比例
                            if (!empty($User_Grade['ml_rebate'])) {
                                $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                            }
                            //等级折扣
                            if (!empty($User_Grade['ml_discount'])) {
                                $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                            }
                            // 商品积分
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
                            if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
                                $gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
                                $gifts_point_goods_price += $v['pdt_sale_price']*$v['pdt_nums'];
                            }
                            if (isset($v['pmn_name'])) {
                                $ary_orders_items['promotion'] = $v['pmn_name'];
                            }
                            if (isset($v['promotion_price']) && !empty($v['promotion_price'])) {
                                $ary_orders_items['promotion_price'] = $v['promotion_price'];
                            }

                            // 商品数量
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
                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('订单明细生成失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                            // 商品库存扣除
                            $ary_payment_where = array(
                                'pc_id' => $ary_orders ['o_payment']
                            );
                            $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                            if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                // by Mithern 扣除可下单库存生成库存调整单
                                $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                                if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                    //查询库存,如果库存数为负数则不再扣除库存
                                    $int_pdt_stock =$goods_products_table->field('pdt_stock,pdt_min_num')->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->find();
                                    if(0 >= $int_pdt_stock['pdt_stock']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    if($int_pdt_stock['pdt_stock']<$v ['pdt_nums']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品已售完！');
                                        die();
                                    }
                                    if($v['pdt_nums'] < $int_pdt_stock['pdt_min_num']){
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error('该货品至少购买'.$int_pdt_stock['pdt_min_num']);
                                        die();
                                    }
                                    $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                                    if (false == $array_result ["status"]) {
                                        $orders->rollback();
                                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                            $queue_obj->unLock();
                                        }
                                        $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        die();
                                    }
                                }
                            }
                        }
                    }
                    // 产品销量
                    if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
                        foreach ($v as $good) {
                            $ary_goods_num = M("goods_info")->where(array('g_id' => $good ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$good['pdt_nums'])))->save();
                            if (!$ary_goods_num) {
                                $orders->rollback();
                                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                    $queue_obj->unLock();
                                }
                                $this->error('销量添加失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                                exit();
                            }
                        }
                    }
                    else {
                        $ary_goods_num = M("goods_info")->where(array('g_id' => $v ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$v['pdt_nums'])))->save();
                        if (!$ary_goods_num) {
                            $orders->rollback();
                            if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                                $queue_obj->unLock();
                            }
                            $this->error('销量添加失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                            exit();
                        }
                    }
                }

                // 商品下单获得总积分
                $other_all_price = $int_pdt_sale_price-$gifts_point_goods_price;
                $total_reward_point = D('PointConfig')->getrRewardPoint($other_all_price);
                $total_reward_point = ceil((($ary_orders ['o_all_price']-$ary_orders ['o_cost_freight'])/$int_pdt_sale_price)*$total_reward_point);
                $total_reward_point += $gifts_point_reward;
                $total_consume_point += $freeze_point;
                // 有消耗积分或者获得积分，消耗积分插入订单表进行冻结操作
                if ($total_consume_point > 0 || $total_reward_point > 0) {
                    $ary_freeze_point = array(
                        'o_id' => $ary_orders ['o_id'],
                        'm_id' => $_SESSION ['Members'] ['m_id'],
                        'freeze_point' => $total_consume_point,
                        'reward_point' => $total_reward_point
                    );
                    $res_point = D('Orders')->updateFreezePoint($ary_freeze_point);
                    if (!$res_point) {
                        $orders->rollback();
                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                            $queue_obj->unLock();
                        }
                        $this->error('更新冻结积分失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                        exit();
                    }
                }
            }
            if(isset($bonus_price) && $bonus_price>0){
                //更新红包使用
                $arr_bonus = array(
                    'bt_id' => '4',
                    'm_id'  => $ary_datas['m_id'],
                    'bn_create_time'  => date("Y-m-d H:i:s"),
                    'bn_type' => '1',
                    'bn_money' => $bonus_price,
                    'bn_desc' => $bonus_price."元",
                    'o_id' => $ary_orders['o_id'],
                    'bn_finance_verify' => '1',
                    'bn_service_verify' => '1',
                    'bn_verify_status' => '1',
                    'single_type' => '2'
                );
                $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                if (!$res_bonus) {
                    if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                        $queue_obj->unLock();
                    }
                    $this->error('红包使用失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                    exit();
                }
            }
            if($ary_coupon['status'] == 'success'){
                foreach ($ary_coupon['msg'] as $coupon){
                    if($coupon['ca_type'] == 0){
                        //同号券活动
                        $res_coupon = D("CouponActivities")->doUseCoupon($ary_orders['o_id'], $coupon);
                    }else{
                        //异号券活动
                        $ary_data = array(
                            'c_is_use' => 1,
                            'c_used_id' => $_SESSION ['Members'] ['m_id'],
                            'c_order_id' => $ary_orders ['o_id']
                        );
                        $res_coupon = D('Coupon')->doCouponUpdate($coupon ['c_sn'], $ary_data);
                    }
                    if (!$res_coupon) {
                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                            $queue_obj->unLock();
                        }
                        $this->error('优惠券更新失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                        exit();
                    }
                }
            }

            // 订单日志记录
            $ary_orders_log = array(
                'o_id' => $ary_orders ['o_id'],
                'ol_behavior' => '创建',
                'ol_uname' => $_SESSION ['Members'] ['m_name'],
                'ol_create' => date('Y-m-d H:i:s')
            );

            $res_orders_log = D('OrdersLog')->add($ary_orders_log);
            if (!$res_orders_log) {
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('订单日志记录失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                exit();
            }
            //提货通知
            if (( $ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE' ) && $is_zt == 1) {
                if(isset($ary_orders ['lt_id'])){
                    $ary_orders['mobile'] = $ary_orders['o_receiver_mobile'];
                    if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && !empty($ary_orders['mobile'])){
                        D('SmsTemplates')->sendSmsGetCode($ary_orders);
                    }
                }
            }
            $orders->commit();
            if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                $queue_obj->unLock();
            }
            //order queue add by zhangjiasuo 2014-10-29 end
            if (!empty($_SESSION ['Members'] ['m_id'])) {
                $mix_pdt_id = array();
                $mix_pdt_type = array();
                foreach ($ary_cart as $key=>$val){
                    $mix_pdt_id[] = $key;
                    $mix_pdt_type[] = $val['type'];
                }
                D('Cart')->doUpadteOrdersCart($mix_pdt_id,$mix_pdt_type);
                //$Cart = D('Cart')->DelMycart();
            } else {
                unset($_SESSION ['Cart']);
            }
            $this->success('订单提交成功，请您尽快付款！', U("Ucenter/Orders/OrderSuccess", array('oid' => $order_id)));
            exit();

    }

}