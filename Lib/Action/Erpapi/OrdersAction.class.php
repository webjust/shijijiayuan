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
     * 获取订单支付列表
     * @param array $params
     * $param = array(
     *  'from'  => '',  //请求来源，app(android_app,ios_app),wap,pc,...(必填)
     *  'ra_id' => '',  //收货地址ID（非必填）
     *  'sgp'   => 'g_id,pdt_id,num,item_type;...', //订单商品详细（非必填）
     *  'm_id'  => '',  //会员ID（非必填）
     *  'lt_id' => '',  //配送方式ID（非必填）
     *  'type'  => '',  //订单类型
     * );
     *
     */
    private function fxOrdersPaymentsGet($params=array()) {
        $from = $params['from'];
        if(empty($from)){
            $this->errorResult(false,10101,array(),'请求来源不能为空');
        }
        //根据请求来源读取支付方式列表
        $ary_payment = D('PaymentCfg')->getPaymentList($from);
        $options = array(
            'root_tag' => 'order_payments'
        );
        $this->result(true,'200',$ary_payment,"success",$options);
    }

    /**
     * 获取快递公司列表
     * @param array $params
     * $param = array(
     *  'ra_id' => '',  //收货地址ID（必填）
     *  'sgp'   => 'g_id,pdt_id,num,promotion_type,promotion_id;...', //订单商品详细（必填）
     *  'm_id'  => '',  //会员ID（必填）
     *  'from'  => '',  //请求来源，app(android_app,ios_app),wap,pc,...(非必填)
     * );
     */
    private function fxLogisticListGet($params=array()) {
        //验证是否传递ra_id参数
        if(!isset($params["ra_id"]) || "" == $params["ra_id"]){
            $this->errorResult(false,10201,array(),'缺少应用级参数收货地址ra_id');
        }
        //验证是否传递sgp参数
        if(!isset($params["sgp"]) || "" == $params["sgp"]){
            $this->errorResult(false,10201,array(),'缺少应用级参数商品基本信息参数');
        }
        $ary_sgp = explode(';', $params['sgp']);
        if(empty($ary_sgp)) {
            $this->errorResult(false,10201,array(),'订单商品不能为空');
        }
        $ary_pdt = array();
        $order_type = 0;
        //遍历要购买的商品
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
        //获取收货地址的区域ID (cr_id)
        $address_data = D('CityRegion')->getFindReciveAddr($params["ra_id"]);
        if(empty($address_data)){
            $this->errorResult(false,10202,array(),'查询数据为空，请查看收货地址是否存在');
            exit;
        }

        //获取物流公司列表
        $logistic_data = D('Logistic')->getShippingList($address_data["cr_id"], $ary_pdt, $address_data["m_id"], $order_type);

        //所有验证完毕，返回数据
        $options = array();
        $options['root_tag'] = 'logistic_list_get_response';
        $this->result(true,10007,$logistic_data,"success",$options);

    }

    /**
     * 生成订单
     * @param  array $array_params
     * $array_params = array(
     * 'ra_id' => 地址ID (必填)
     * 'm_id' => 会员ID (必填)
     * 'pc_id' => 支付ID (必填)
     * 'lt_id' => 物流ID (必填)
     * 'sgp' => base64_encode(g_id,pdt_id(规格ID),num,type,type_id;g_id,pdt_id(规格ID),num,type,type_id)
     * 'resource' => 订单来源 (必填) (android或ios)
     *
     * 'bonus' => '',      //可选，红包
     * 'cards' => '',      //可选，储值卡
     * 'csn'   => '',      //可选，优惠码
     * 'point' => '',      //可选，积分
     * 'type'	=> '',	       //可选，0：优惠券|1：红包|2：存储卡|4：积分
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
                    //团购
                    case '5':
                        //团购参数判断
                        $this->fxGroupbuyOrderAddDo($array_params);
                        break;
                    //秒杀
                    case '7':
                        //秒杀参数判断
                        $this->checkSpikeOrderAddParams($array_params);
                        $this->fxSpikeOrderAddDo($array_params);
                        break;
                    //预售
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
        //检查参数有效性
        $this->checkOrderAddParams($array_params);
        //检查订单有效性
        $this->orderItemsValidate($ary_pdts, $int_m_id);
        //获取收货地址详情
        $this->buildOrderAddress($int_m_id, $int_ra_id, $ary_orders);


        //获取发票详情
        $this->buildOrderInvoice($array_params, $ary_orders);
        //获取订单价格详情
        $this->buildOrderPromotion($array_params, $ary_orders);
        if(!empty($ary_orders['ary_coupon'])){
            $ary_coupon = $ary_orders['ary_coupon'];
            unset($ary_orders['ary_coupon']);
        }
        if(!empty($ary_orders['pro_datas'])){
            $pro_datas = $ary_orders['pro_datas'];
            unset($pro_datas);
        }
        //获取订单其他信息
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
        // 发货备注
        $ary_orders ['o_shipping_remarks'] = isset($array_params ['shipping_remarks']) ? $array_params ['shipping_remarks'] : '';
        // 管理员操作者ID
        $ary_orders ['o_addorder_id'] = isset($array_params ['admin_id']) ? $array_params ['admin_id'] : '';
        //判断是否开启自动审核功能
        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
        if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
            $ary_orders['o_audit'] = 1;
        }
        //是否是匿名购买
        if($array_params['is_anonymous'] == '1'){
            $ary_orders['is_anonymous'] = 1;
        }

        $ary_orders['oi_type'] = 0;

        $ordersModel = D('Orders');
        $ordersModel->startTrans();
        //保存订单
        $bool_orders = $ordersModel->doInsert($ary_orders);
        if (!$bool_orders) {
            $ordersModel->rollback();
            $this->errorResult(false,10101,array(),'订单生成失败');exit;
        }
        //保存订单详情
        $ary_insert_item = $this->insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas);
        //商品下单获得总积分
        $this->getRewardPoint($ary_orders,$ary_insert_item);
        //更新优惠券使用
        $this->updateCoupon($array_params,$ary_orders);
        //新增订单日志
        $this->addOrderLog($ary_orders);
        //清除购物车
        $this->cleanShoppingCartItem($ary_pdts, $int_m_id,$array_params);

        $options = array(
            'root_tag' => 'Order_confirm_response'
        );
        $response_arr['po_id'] = $order_id;
        $response_arr['total_sale_price'] = sprintf("%0.3f", $ary_orders['o_all_price']);
        $response_arr['payment'] = '支付宝';
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
            $this->errorResult(false, 500, '', '团购商品不能为空');
        }
        $ary_items = explode(';', $str_items);
        $ary_pdts = $ary_gids = array();
        $item = current($ary_items);
        $ary_info = explode(',', $item);
        $gp_id = $ary_info[4];

        $ary_orders = array();
        //检查参数有效性
        $this->checkOrderAddParams($array_params);
        //获取收货地址详情
        $this->buildOrderAddress($int_m_id, $int_ra_id, $ary_orders);
        //检查团购订单有效性
        $this->checkBulkOrderAddParams($array_params, $ary_orders);

        //获取发票详情
        $this->buildOrderInvoice($array_params, $ary_orders);
        //获取订单价格详情
        $this->buildOrderPromotion($array_params, $ary_orders);

        //获取订单其他信息
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
        // 发货备注
        $ary_orders ['o_shipping_remarks'] = isset($array_params ['shipping_remarks']) ? $array_params ['shipping_remarks'] : '';
        // 管理员操作者ID
        $ary_orders ['o_addorder_id'] = isset($array_params ['admin_id']) ? $array_params ['admin_id'] : '';
        //判断是否开启自动审核功能
        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
        if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
            $ary_orders['o_audit'] = 1;
        }
        //是否是匿名购买
        $ary_orders['is_anonymous'] = (isset($array_params['is_anonymous']) && $array_params['is_anonymous'] > 0) ? 1 : 0;

        $ary_orders['oi_type'] = 5;
        $ary_orders['gp_id'] = $gp_id;
        $ary_orders['goods_pids'] = 'bulk';

        $ordersModel = D('Orders');
        $ordersModel->startTrans();
        //保存订单
        $bool_orders = $ordersModel->doInsert($ary_orders);
        if (!$bool_orders) {
            $ordersModel->rollback();
            $this->errorResult(false,10101,array(),'订单生成失败');exit;
        }
        //保存订单详情
        $ary_insert_item = $this->insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas);
        //商品下单获得总积分
        $this->getRewardPoint($ary_orders,$ary_insert_item);
        //更新优惠券使用
        $this->updateCoupon($array_params,$ary_orders);
        //新增订单日志
        $this->addOrderLog($ary_orders);
        //清除购物车
        $this->cleanShoppingCartItem($ary_pdts, $int_m_id,$array_params);

        $options = array(
            'root_tag' => 'Order_confirm_response'
        );
        $response_arr['po_id'] = $order_id;
        $response_arr['total_sale_price'] = sprintf("%0.3f", $ary_orders['o_all_price']);
        $response_arr['payment'] = '支付宝';
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
     * 检查参数有效性
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkOrderAddParams($array_params){
        if(isset($array_params['m_id']) && $array_params['m_id'] == 0){
            $this->errorResult(false,10101,array(),'请填写用户ID:m_id');
            exit;
        }
        if(isset($array_params['ra_id']) && $array_params['ra_id'] == 0){
            $this->errorResult(false,10102,array(),'请填写地址ID:ra_id');
            exit;
        }
        if(isset($array_params['lt_id']) && $array_params['lt_id'] == 0){
            $this->errorResult(false,10103,array(),'请填写物流ID:lt_id');
            exit;
        }
        if(isset($array_params['pdt_id']) && $array_params['pdt_id'] == 0){
            $this->errorResult(false,10104,array(),'请填写选择的商品:pdt_id');
            exit;
        }
        if(isset($array_params['pc_id']) && $array_params['pc_id'] == 0){
            $this->errorResult(false,10105,array(),'请填写支付方式:pc_id');
            exit;
        }
        return true;
    }

    /**
     * 检查团购参数有效性
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkBulkOrderAddParams($array_params,$ary_orders){
        //团购判断
        if(isset($array_params['gp_id'])){
            $now_count =  D('OrdersItems')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
                ->where(array('fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$array_params ['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                ->find();
            $btween_time = D('Groupbuy')->where(array('gp_id'=>$array_params ['gp_id']))->field("gp_start_time,gp_end_time,gp_number,gp_now_number")->find();
            if($now_count['buy_nums'] >= $btween_time['gp_number']){
                $_SESSION['bulk_cart'] = "";
                $this->errorResult(false,10101,array(),'已售完');
                exit;
            }
            if(strtotime($btween_time['gp_start_time']) > mktime()){
                $this->errorResult(false,10101,array(),'团购未开始');
                exit;
            }
            if(strtotime($btween_time['gp_end_time']) < mktime()){
                $this->errorResult(false,10101,array(),'团购已结束');
                exit;
            }
            $array_where = array('is_active'=>1,'gp_id'=>$array_params['gp_id'],'deleted'=>0);
            $data = D('Groupbuy')->where($array_where)->find();
            if($array_params['m_id']){
                //当前会员已购买数量
                $member_buy_num =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                    ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
                    ->where(array('fx_orders.m_id'=>$array_params['m_id'],'fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$data['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                    ->find();
                //目前可以购买的数量
                $thisGpNums = $data['gp_number'] - $now_count['now_count'];
                //如果会员限购数量大于当前会员已购买数量
                if($data['gp_per_number'] > $member_buy_num['buy_nums']){
                    //当前会员最多可以购买的数量
                    $gp_number = $data['gp_per_number'] - $member_buy_num['buy_nums'];
                    //如果会员最多可以购买的数量大于目前库存，将库存赋予会员购买数量
                    if(($array_params['num'] > $gp_number) || ($array_params['num'] > $thisGpNums)){
                        $this->errorResult(false,10101,array(),'卖光了或购买数量已达上限！');
                        exit;
                    }
                }else{
                    $this->errorResult(false,10101,array(),'卖光了或购买数量已达上限！');
                    exit;
                }
            }
            $is_can_buy = D('CityRegion')->isGroupCanBuy($ary_orders['o_receiver_city'],$array_params ['gp_id']);
            if($is_can_buy == false){
                $this->errorResult(false,10101,array(),'您收货地址所对应的区域不支持购买此商品！');
                exit;
            }
        }
        return true;
    }

    /**
     * 检查秒杀参数有效性
     * @param $array_params
     *
     * @return mixed
     * @date 2015-09-22 By Wangguibin
     */
    private function checkSpikeOrderAddParams($array_params){
        //秒杀商品每人限购1件 判断秒杀是否已结束
        if(isset($array_params ['sp_id'])){
            $btween_time = D('Spike')->where(array('sp_id'=>$array_params ['sp_id']))->field("sp_start_time,sp_end_time,sp_number,sp_now_number")->find();
            if($btween_time['sp_number'] <= $btween_time['sp_now_number']){
                $this->errorResult(false,10101,array(),'已售罄！');
                exit;
            }
            if(strtotime($btween_time['sp_start_time']) > mktime()){
                $this->errorResult(false,10101,array(),'秒杀未开始！');
                exit;
            }
            if(strtotime($btween_time['sp_end_time']) < mktime()){
                $this->errorResult(false,10101,array(),'秒杀已结束！');
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
                $this->errorResult(false,10101,array(),'秒杀限购1件');
                exit;
            }
        }
        return true;
    }

    /**
     * 订单有效性验证
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
                if ($value ['g_on_sale'] != 1) { // 上架
                    $this->errorResult(false,10207,array(),'商品已下架！');
                    return false;
                }
                $is_authorize = D('AuthorizeLine')->isAuthorize($int_m_id, $value['g_id']);
                if (empty($is_authorize)) {
                    $this->errorResult(false,10101,array(),'部分商品已不允许购买,请先在购物车里删除这些商品');
                    return false;
                }
            }
            return true;
        }else{
            $this->errorResult(false,10208,array(),'没有商品数据！');
            return false;
        }
    }

    /**
     * 订单收货地址详情
     * @param $int_m_id
     * @param $int_ra_id
     * @param $ary_orders
     *
     * @return mixed
     */
    private function buildOrderAddress($int_m_id, $int_ra_id, &$ary_orders=array()) {

        $ary_receive_address = D('CityRegion')->getReceivingAddress($int_m_id, $int_ra_id);
        if (isset($ary_receive_address['ra_id'])) {
            // 收货人
            $ary_orders ['o_receiver_name'] = $ary_receive_address ['ra_name'];
            // 收货人电话
            $ary_orders ['o_receiver_telphone'] = empty($ary_receive_address ['ra_phone']) ? '' : trim($ary_receive_address ['ra_phone']);
            // 收货人手机
            $ary_orders ['o_receiver_mobile'] = empty($ary_receive_address ['ra_mobile_phone']) ? '' : trim($ary_receive_address ['ra_mobile_phone']);
            // 收货人邮编
            $ary_orders ['o_receiver_zipcode'] = $ary_receive_address ['ra_post_code'];
            // 收货人地址
            $ary_orders ['o_receiver_address'] = $ary_receive_address ['ra_detail'];

            $ary_addr = explode(' ', $ary_receive_address['address']);
            if(!empty($ary_addr[1])){
                // 收货人省份
                $ary_orders ['o_receiver_state'] = $ary_addr[0];
                // 收货人城市
                $ary_orders ['o_receiver_city'] = $ary_addr[1];
                // 收货人地区
                $ary_orders ['o_receiver_county'] = isset($ary_addr[2]) ? $ary_addr[2] : '';
            }else{
                $this->errorResult(false,10101,array(),'请检查您的收货地址是否正确');
                exit();
            }
            if (empty($ary_orders ['o_receiver_county'])) { // 没有区时
                unset($ary_orders ['o_receiver_county']);
            }
        }else{
            $this->errorResult(false,10101,array(),'请检查您的收货地址是否正确');
            exit();
        }
    }

    /**
     * 订单发票信息
     * @param array $array_params
     * @param array $ary_orders
     *
     * @return array
     */
    private function buildOrderInvoice($array_params= array(), &$ary_orders=array()) {

        if(isset($array_params['is_on']) && $array_params['is_on']!=''){
            if($array_params['is_on'] =='1'){//普通发票(个人)
                $ary_orders['is_invoice']=1;
                $ary_orders['invoice_type']='1';
                $ary_orders['invoice_head']='1';
                $ary_orders['invoice_people']=$array_params['invoice_name'];
            }
            if($array_params['is_on'] =='2'){//普通发票(单位)
                $ary_orders['is_invoice']=1;
                $ary_orders['invoice_type']='1';
                $ary_orders['invoice_head']='单位';
                $ary_orders['invoice_name']=$array_params['invoice_name'];
            }
            if($array_params['invoice_content']!=''){//发票内容
                $ary_orders['invoice_content'] = $array_params['invoice_content'];
            }
        }
    }

    /**
     * 订单促销信息
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
        $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $ary_price['o_goods_all_saleprice']);  //销售价
        $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $ary_price['o_goods_all_price']);  //促销价
        if(empty($ary_orders ['o_goods_all_price'])){
            $this->errorResult(false,10101,array(),'没有要购买的商品，请重新选择商品');
            exit;
        }
        $ary_orders ['o_all_price'] = sprintf("%0.2f", $ary_price['all_price']);    //订单总价
        $ary_orders ['o_discount'] = sprintf("%0.2f", $ary_price['o_discount']);    //订单优惠金额
        $ary_orders ['o_goods_discount'] = sprintf("%0.2f", $ary_price['o_discount']);   //订单优惠金额
        $ary_orders ['o_promotion_price'] = sprintf("%0.2f", $ary_price['o_discount']);   //订单优惠金额
        if(isset($ary_price['discount_price'])){
            $ary_orders ['discount_price'] = sprintf("%0.2f", $ary_price['discount_price']);   //订单优惠金额
        }
        if($ary_orders ['o_all_price']<0){
            $ary_orders ['o_all_price'] = 0;
        }
        if(0 == $ary_orders ['o_all_price']) { //当订单总价为0 订单状态为已支付
            $ary_orders ['o_pay_status'] = 1;
            $ary_orders ['o_status'] = 1;
        }
        $ary_orders ['o_cost_freight'] = sprintf("%0.2f", $ary_price['logistic_price']);    //运费
        $ary_orders ['o_coupon_menoy'] = sprintf("%0.2f", $ary_price['coupon_price']);    //优惠券金额
        if(isset($ary_price['coupon'])) {
            $ary_orders ['o_coupon']     = 1;    //是否使用优惠券
            $ary_orders ['coupon_sn']    = $ary_price['coupon']['c_sn'];    //优惠券号
            $ary_orders ['coupon_value'] = $ary_price['coupon']['c_money'];    //优惠券金额
            $ary_orders ['coupon_start_date'] = $ary_price['coupon']['c_start_time'];    //优惠券生效开始时间
            $ary_orders ['coupon_end_date'] = $ary_price['coupon']['c_end_time'];    //优惠券生效结束时间
        }
        $ary_orders['o_freeze_point'] = intval($ary_price['points']);   //积分数
        $ary_orders['o_point_money'] = sprintf("%0.2f", $ary_price['point_price']);   //积分抵扣金额
        $ary_orders['o_cards_money'] = sprintf("%0.2f", $ary_price['cards_price']);   //使用存储卡抵扣金额
        $ary_orders['o_bonus_money'] = sprintf("%0.2f", $ary_price['bonus_price']);   //使用红包抵扣金额
        //返回优惠券信息和pro_datas信息
        $ary_orders['ary_coupon'] = $ary_price['ary_coupon'];
        $ary_orders['pro_datas'] = $ary_price['pro_datas'];
    }

    /**
     * 插入订单详情
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
        //获取购物车数据
        if (isset($array_params ['sp_id'])) {
            // 秒杀商品
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
            // 团购商品
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
            //获取购物车信息
            $ary_cart = $cartModel->getCartItems($str_pdt_ids, $m_id);
        }
        $ary_orders_goods = $cartModel->getProductInfo($ary_cart, $m_id);
        if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
            $total_consume_point = 0; // 消耗积分
            $int_pdt_sale_price = 0; // 货品销售原价总和
            $gifts_point_reward = '0'; //有设置购商品赠积分所获取的积分数
            $gifts_point_goods_price  = '0'; //设置了购商品赠积分的商品的总价
            //获取明细分配的金额
            $ary_orders_goods = D('OrdersItems')->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);
            foreach ($ary_orders_goods as $k => $v) {
                $ary_orders_items = array();
                //团购
                if($v['type'] == 5){
                    // 团购商品
                    // 订单id
                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                    // 商品id
                    $ary_orders_items ['g_id'] = $v ['g_id'];

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
                    $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $ary_orders ['discount_price'];
                    // 商品数量
                    $ary_orders_items ['oi_nums'] = $v['pdt_nums'];
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
                        $this->errorResult(false,10101,array(),'订单明细新增失败');
                        exit();
                    }

                    $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$v['pdt_nums']);
                    if (!$retun_buy_nums) {
                        $orders->rollback();
                        $this->errorResult(false,10101,array(),'更新团购量失败');
                        exit();
                    }
                }else{
                    //秒杀
                    if($v['type'] == 7){
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
                        $ary_orders_items ['oi_price'] =  $ary_orders ['discount_price'];
                        // 商品数量
                        $ary_orders_items ['oi_nums'] = $v['pdt_nums'];
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
                            $this->errorResult(false,10101,array(),'订单明细新增失败');
                            exit();
                        }
                        $retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$v['pdt_nums']);
                        if (!$retun_buy_nums) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'更新秒杀量失败');
                            exit();
                        }
                    }else{
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
                        $bool_orders_items = $orders->doInsertOrdersItems($ary_orders_items);
                        if (!$bool_orders_items) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'订单明细生成失败');
                            exit();
                        }else{
                            //商品销量添加
                            //$ary_goods_num = M("goods_info")->where(array('g_id' => $ary_orders_items ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$ary_orders_items['oi_nums'])))->save();
                            //if (!$ary_goods_num) {
                            //$orders->rollback();
                            //$this->errorResult(false,10101,array(),'销量添加失败');
                            //exit();
                            //}
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
                                $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                    ->field('pdt_stock,pdt_min_num')
                                    ->where(array('o_id'=>$ary_orders['o_id']))
                                    ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                    ->find();
                                if(0 >= $int_pdt_stock['pdt_stock']){
                                    $this->errorResult(false,10101,array(),'该货品已售完！');
                                    die();
                                }
                                if($v['pdt_nums'] < $int_pdt_stock['pdt_min_num']){
                                    $this->errorResult(false,10101,array(),'该货品至少购买'.$int_pdt_stock['pdt_min_num']);
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
                    //商品销量添加
                    $ary_goods_num = M("goods_info")->where(array('g_id' => $ary_orders_items ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$ary_orders_items['oi_nums'])))->save();
                    if (!$ary_goods_num) {
                        $orders->rollback();
                        $this->errorResult(false,10101,array(),'销量添加失败');
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
     * 新增订单日志
     * @param $ary_orders
     */
    private function addOrderLog($ary_orders) {
        // 订单日志记录
        $ary_orders_log = array(
            'o_id' => $ary_orders ['o_id'],
            'ol_behavior' => '创建',
            'ol_uname' => $ary_orders['m_id'],
            'ol_create' => date('Y-m-d H:i:s')
        );

        $res_orders_log = D('OrdersLog')->add($ary_orders_log);
        if (!$res_orders_log) {
            $this->errorResult(false,10101,array(),'订单日志记录失败');
            exit();
        }
    }

    /**
     * 清除购物车商品
     * @param $ary_pdts
     * @param $m_id
     */
    private function cleanShoppingCartItem($ary_pdts, $m_id,$array_params) {
        if (isset($array_params ['sp_id'])) {
            // 秒杀商品
            unset($_SESSION['spike_cart']);
        }else if(isset($array_params ['gp_id'])){
            // 团购商品
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