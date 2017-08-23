<?php

/**
 * 订单相关Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 1.0
 * @author Joe
 * @date 2012-12-12
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersAction extends CommonAction {

    /**
     * 订单对象
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-17
     */
    private $orders;

    /**
     * 地址对象
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-17
     */
    private $cityRegion;

    /**
     * 订单控制器初始化
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    public function _initialize() {
        parent::_initialize();
        $this->orders = D('Orders');
        $this->cityRegion = D('CityRegion');
        $this->logistic = D('Logistic');
        $this->cart = D('Cart');
    }

    /**
     * 订单控制器默认页
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     * @todo 此处需要跳转到快速选货页面
     */
    public function index() {
        $this->getSubNav(2, 0, 40);
        $this->display();
        $this->redirect(U('Ucenter/Orders/pageList'));
    }

    /**
     * 订单数据验证
     *
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-28
     */
    public function checkOrder($ary_tmp_cart,$np) {
        $ary_member = session("Members");
        $date = date('Y-m-d H:i:s');
        if (!empty($ary_member ['m_id'])) {
            if($ary_tmp_cart){
                $ary_cart = $ary_tmp_cart;
            }else{
                $ary_cart = D('Cart')->GetData();
            }
            // 自由组合商品搭配分开
            $ary_product_ids = array();
            foreach ($ary_cart as $k => $ary_sub) {
                if ($ary_sub ['type'] == '4') {
                    $fc_id = $ary_sub ['fc_id'];
                    // 判断自由组合商品是否存在或是否在有效期
                    $fc_data = M('free_collocation', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'fc_id' => $fc_id,
                                'fc_status' => 1
                            ))->find();
                    if (empty($fc_data)) {
                        $ary_db_carts = D('Cart')->GetData();
                        unset($ary_db_carts [$k]);
                        D('Cart')->WriteMycart($ary_db_carts);
                        $this->error(L('自由推荐组合已不存在'));
                        return false;
                    }
                    if ($fc_data ['fc_start_time'] != '0000-00-00 00:00:00' && $date < $fc_data ['fc_start_time']) {
                        $this->error(L($value ['g_sn'] . '自由推荐组合活动还没有开始'));
                        return false;
                    }
                    if ($date > $fc_data ['fc_end_time']) {
                        $this->error(L($value ['g_sn'] . '自由推荐组合活动已结束'));
                        return false;
                    }
                    // 判断自由组合商品
                    foreach ($ary_sub ['pdt_id'] as $pid) {
                        $ary_product_ids [] = $pid;
                    }
                } else {
                    $ary_product_ids [] = $k;
                }
            }
            $ary_product_ids = array_unique($ary_product_ids);
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
                    $ary_product_ids
                )
            );
            $data = D("GoodsProducts")->GetProductList($where, $field, $group, $limit);
            foreach ($data as $key => $value) {
                if ($value ['g_on_sale'] != 1) { // 上架
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'],$value ['g_sn'] . '下架商品',2);
                    }
                    $this->error(L($value ['g_sn'] . '下架商品'));
                    exit();
                    return false;
                }
				if($value['g_gifts'] == 1){
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'],$value ['g_sn'] . '为非销售赠品',2);
                    }
					$this->error(L($value ['g_sn'] . '为非销售赠品'));
                    exit();
                    return false;
				}
                $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value ['pdt_id'],$ary_member['m_id']);
                if ($ary_cart [$value ['pdt_id']] ['num'] > $tmp_stock && !$value ['g_is_combination_goods'] && !$value ['g_pre_sale_status']) { // 购买数量
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '商品库存不足', 2);
                    }
                    $this->error(L($value ['g_sn'] . '商品库存不足'));
                    return false;
                }
                if ($value ['g_is_combination_goods']) {
                    // $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value ['pdt_id'],$ary_member['m_id']);
                    if ($ary_cart [$value ['pdt_id']] ['num'] > $tmp_stock) {
                        if(IS_AJAX){
                            $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '组合商品库存不足', 2);
                        }
                        $this->error(L($value ['g_sn'] . '组合商品库存不足'));
                        return false;
                    }
                    if ($ary_cart [$value ['pdt_id']] ['num'] > $value ['pdt_max_num'] && $value ['pdt_max_num'] > 0) {
                        // edit by Joe 组合商品数量超出最大下单数时，当前组合商品购物车情空
                        $ary_db_carts = D('Cart')->GetData();
                        unset($ary_db_carts [$value ['pdt_id']]);
                        D('Cart')->WriteMycart($ary_db_carts);
                        if(IS_AJAX){
                            $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '组合商品购买数不能最大于最大下单数', 2);
                        }
                        $this->error(L($value ['g_sn'] . '组合商品购买数不能最大于最大下单数'));
                        return false;
                    }
                    if ($value ['g_on_sale_time'] != '0000-00-00 00:00:00' && $date < $value ['g_on_sale_time']) {
                        if(IS_AJAX){
                            $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '组合商品活动还没有开始', 2);
                        }
                        $this->error(L($value ['g_sn'] . '组合商品活动还没有开始'));
                        return false;
                    }
                    if ($value ['g_off_sale_time'] != '0000-00-00 00:00:00' && $date > $value ['g_off_sale_time']) {
                        if(IS_AJAX){
                            $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '组合商品活动结束', 2);
                        }
                        $this->error(L($value ['g_sn'] . '组合商品活动结束'));
                        return false;
                    }
                }
                if ($value ['pdt_sale_price'] <= 0 && $ary_cart [$value ['pdt_id']] ['type'] != 1 && $value ['g_gifts'] != 1) { // 价格
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '商品价格不正确', 2);
                    }
                    $this->error(L($value ['g_sn'] . '商品价格不正确'));
                    return false;
                }
                if ($value ['pdt_sale_price'] < 0 && $ary_cart [$value ['pdt_id']] ['type'] == 1 && $value ['g_gifts'] == 1) {
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'], $value ['g_sn'] . '商品价格不正确', 2);
                    }
                    $this->error(L($value ['g_sn'] . '商品价格不正确'));
                    return false;
                }
                $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $value ['g_id']);
                if (empty($is_authorize)) {
                    if(IS_AJAX){
                        $this->ajaxReturn($value['pdt_id'], $value ['g_name'] . '已不允许购买,请先删除', 2);
                    }
                    $this->error(L($value ['g_name'] . '已不允许购买,请先删除'));
                    return false;
                }
            }
        } else {
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
        }
		if($np == 1){
			if (count($ary_cart) > 300) {
				$this->success(L('CART_MAX_NUM'));
				exit();
			}		
		}else{
			if (count($ary_cart) > 50) {
				$this->success(L('CART_MAX_NUM'));
				exit();
			}				
		}

        if (empty($ary_cart)) {
            $this->redirect(U('Ucenter/Products/pageList'));
            exit();
        }
        return $ary_cart;
    }

    /**
     * 订单数据验证
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-16
     */
    public function ajaxCheckOrder() {
        $ary_request = $this->_request();
        $ary_pid = explode(',',$ary_request['pid']);
        $ary_cart = $this->getCheckCart($ary_pid);
        $result = $this->checkOrder($ary_cart);
        if (!empty($result)) {
            $this->success(L('订单数据验证成功'));
            return true;
        } else {
            $this->error(L('订单数据验证失败'));
            return false;
        }
    }

    /**
     * 验证选中的商品
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-25
     */
    private function getCheckCart($ary_pid){
        $ary_cart = D('Cart')->GetData();
        $ary_cart_check = array();
        if(!empty($ary_pid) && is_array($ary_pid)){
            foreach($ary_pid as $pid){
                foreach($ary_cart as $key=>$cart){
                    if($cart['pdt_id'] == $pid) $ary_cart_check[$key] = $ary_cart[$key];
                }
            }
            if(empty($ary_cart_check)) $ary_cart_check = $ary_cart;
        }else{
            $ary_cart_check = $ary_cart;
        }
        return $ary_cart_check;
    }

    /**
     * 确认订单页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-12
     */
    public function pageOrderAdd() {
        $this->getSubNav(2, 0, 30);
        $combo_all_price = 0;
        $free_all_price = 0;
        $fla_pmn_price = 0;
        $ary_member = session("Members");
        $ary_cart = $this->checkOrder();
        $ary_data = array();
        // 获取常用收货地址
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
        if (count($ary_addr) > 0) {
            $ary_data ['default_addr'] = $ary_addr [0];
            unset($ary_addr [0]);
        }
        $ary_data ['ary_addr'] = $ary_addr;
        // 获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data ['payment_cfg'] = $payment_cfg;
        // 获取最后一次支付方式
        // 获取配送公司表
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                // dump($cr_id);die();
                $ary_logistic = $this->logistic->getLogistic($cr_id);
            }
        }
        // 获取订单商品列表
        // 货品信息
        if (isset($ary_cart ['gifts'])) {
            $ary_gifts = $ary_cart ['gifts'];
            $cart_gifts_data = $this->cart->getProductInfo($ary_gifts);
            unset($ary_cart ['gifts']);
        }
        $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        // 赠品商品总重
        if (!empty($ary_gifts)) {
            $all_gifts_weight = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_gifts));
            $ary_price_data ['all_weight'] += $all_gifts_weight;
        }
        // 购买货品的总价
        $ary_price_data ['all_pdt_price'] = sprintf("%0.2f", $this->cart->getAllPrice($ary_cart));

        if (!empty($ary_data ['ary_product_data']) && is_array($ary_data ['ary_product_data'])) {
            foreach ($ary_data ['ary_product_data'] as $k => $v) {
                // 自由组合商品价格
                if ($v [0] ['type'] == '4') {
                    foreach ($v as $key => $item_info) {
                        $ary_price_data ['all_price'] += $item_info ['pdt_momery'];
                        $free_all_price += $item_info ['pdt_momery'];
                    }
                } else {
                    // 应付的价格（不包括运费）
                    if ($v ['type'] == 0) {
                        $ary_price_data ['all_price'] += sprintf("%0.2f", $v ['pdt_momery']);
                    } elseif ($v ['type'] == 1) {
                        $ary_price_data ['consume_point'] += intval($v ['pdt_momery']); // 消耗总积分
                    } elseif ($v ['type'] == 3) {
                        $ary_price_data ['all_price'] += sprintf("%0.2f", $v ['pdt_momery']);
                        $combo_all_price += sprintf("%0.2f", $v ['pdt_momery']);
                    }
                }
            }
            $ary_price_data ['pre_price'] = sprintf("%0.2f", $ary_price_data ['all_pdt_price'] - $ary_price_data ['all_price']);
        }

        // 订单促销规则
        $ary_promotion = array();

        foreach ($ary_data ['ary_product_data'] as $key => &$info) {
            // 过滤掉自由组合
            if (!isset($info [0] ['pdt_id'])) {
                if (!empty($info ['rule_info'] ['pmn_id']) && empty($ary_pdt_info ['rule_info'] ['pmn_id'])) {
                    $ary_pdt_info ['rule_info'] = $info ['rule_info'];
                }
                if ($info ['type'] != 3) {
                    $info ['pdt_rule_name'] = $info ['rule_info'] ['name'];
                    $ary_pdt_info [$info ['pdt_id']] = array(
                        'pdt_id' => $info ['pdt_id'],
                        'num' => $info ['pdt_nums'],
                        'type' => $info ['type'],
                        'price' => $info ['f_price']
                    );
                }
            }
        }
        $ary_param = array(
            'action' => 'order',
            'mid' => $ary_member ['m_id'],
            'all_price' => $ary_price_data ['all_price'] - $combo_all_price - $free_all_price,
            'ary_pdt' => $ary_pdt_info
        );
        $orders_discount = D('Price')->getOrderPrice($ary_param);
        if ($orders_discount ['all_price'] > 0 && !empty($orders_discount ['code'])) {
            $promition_rule_name = $orders_discount ['name'];
            // 促销优惠价格
            if (!empty($ary_price_data ['pre_price'])) { // 组合商品优惠金额
                $fla_pmn_price = $ary_price_data ['pre_price'];
            }
            if ($orders_discount ['code'] == 'MBAOYOU') {
                $ary_price_data ['total_price'] = $ary_price_data ['all_price'];
            } elseif ($orders_discount ['code'] == 'MZENPIN' || $orders_discount ['code'] == 'MQUAN') {
                $fla_pmn_price += $orders_discount ['price'];
                $ary_price_data ['total_price'] = $ary_price_data ['all_pdt_price'] - $fla_pmn_price;
            } else {
                $fla_pmn_price += $orders_discount ['all_price'] - $orders_discount ['price'];
                $ary_price_data ['total_price'] = sprintf("%0.2f", $ary_price_data ['all_pdt_price'] - $fla_pmn_price);
            }
        } else {
            $fla_pmn_price += $ary_price_data ['all_pdt_price'] - $ary_price_data ['all_price'];
            $ary_price_data ['total_price'] = $ary_price_data ['all_price'];
        }
        // 发票信息
        $p_invoice = D('Invoice')->get();
        $invoice_type = explode(",", $p_invoice ['invoice_type']);
        $invoice_head = explode(",", $p_invoice ['invoice_head']);
        $invoice_content = explode(",", $p_invoice ['invoice_content']);

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($ary_member ['m_id']);

        // 优惠的价格
        $ary_price_data ['pre_price'] = sprintf("%0.2f", $ary_price_data ['all_pdt_price'] - $ary_price_data ['all_price']);
        // 获得赠送积分
        $ary_price_data ['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data ['all_pdt_price']);
        // 添加产品是否允许购买
        $is_authorize = true;

        foreach ($ary_data ['ary_product_data'] as &$prod) {
            if ($prod [0] ['type'] == '4') {
                foreach ($prod as &$item_info) {
                    $item_info ['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $item_info ['g_id']);
                    if ($item_info ['authorize'] == false) {
                        $is_authorize = false;
                    }
                }
            } else {
                $prod ['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $prod ['g_id']);
                if ($prod ['authorize'] == false) {
                    $is_authorize = false;
                }
            }
        }
        $this->assign("is_authorize", $is_authorize);
        $this->assign("ary_product", $ary_data ['ary_product_data']);
        // 赠品
        $this->assign("gifts_data", $cart_gifts_data);

        $this->assign("price_data", $ary_price_data);
        // 页面输出
        $this->assign('ary_addr', $ary_data ['ary_addr']);
        $this->assign('default_addr', $ary_data ['default_addr']);
        // echo "<pre>";print_r($ary_logistic);exit;
        // 配送公司
        $this->assign('ary_logistic', $ary_logistic);
        // 支付方式
        $this->assign('ary_paymentcfg', $ary_data ['payment_cfg']);
        // 订单实付金额
        // 发票信息
        $this->assign('invoice_info', $invoice_info);
        $this->assign('invoice_content', $invoice_content);

        // 发票收藏列表
        $this->assign('invoice_list', $invoice_list);
        // 促销优惠价
        $this->assign('fla_pmn_price', $fla_pmn_price);
        // 订单锁定
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $this->assign('order_lock', $ary_erp ['ORDER_LOCK']);
        $ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
        // 订单促销规则名称
        $this->assign("promition_rule_name", $promition_rule_name);
        $this->display();
    }

    /**
     * 确认订单页面
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-09
     * @modify by wanghaoyu //添加是否显示发货选择
     */
    public function pageAdd() {
    
        $this->getSubNav(1, 0, 30);
        $combo_all_price = 0;
        $free_all_price = 0;
        $fla_pmn_price = 0;
        $ary_member = session("Members");
        $ary_tmp_cart = D('Cart')->GetData();
        $pids = $this->_request('pid');
		$p_type = $this->_request('pt');
		if($p_type == '1'){
		$pids = explode(',',$pids);
		}		
		//不显示商品图片
		$np = $this->_request('np');
		$this->assign("np",$np);		
        if(empty($pids) && !isset($pids)){
            $this->error('请选择购物车商品！','/Ucenter/Cart/pageList');
        }else{
            foreach ($ary_tmp_cart as $key=>$cd){
                foreach ($pids as $pid){
                    if($pid == $key){
                        $checkOrder[$pid] = $ary_tmp_cart[$key];
                    }
                }
            }
        }
        $ary_cart = $this->checkOrder($checkOrder,$np);
        $ary_tmp_cart = $ary_cart;
        $ary_data = array();
        // 获取常用收货地址
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
        if (count($ary_addr) > 0) {
            $ary_data ['default_addr'] = $ary_addr [0];
            unset($ary_addr [0]);
        }
        $ary_data ['ary_addr'] = $ary_addr;
        // 获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data ['payment_cfg'] = $payment_cfg;
        // 获取最后一次支付方式
        // 获取配送公司表
        
        //echo "<pre>";print_r($ary_logistic);exit;
        // 获取订单商品列表
        // 货品信息
        if (isset($ary_cart ['gifts'])) {
            $ary_gifts = $ary_cart ['gifts'];
            $cart_gifts_data = $this->cart->getProductInfo($ary_gifts);
            unset($ary_cart ['gifts']);
        }
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as &$val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $val['g_id'] = $ary_gid['g_id'];
                }
            }
        }
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
        $subtotal = $pro_datas['subtotal']; //促销金额
        //剔除商品价格信息
        unset($pro_datas['subtotal']);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        
        // 购买货品的总价
        $ary_price_data ['all_pdt_price'] = sprintf("%0.2f", $subtotal['goods_total_sale_price']);
        $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart, $ary_price_data['all_pdt_price']);
        //限购判断
        foreach($ary_data['ary_product_data'] as $pro){
            if($pro['pdt_nums'] < $pro['pdt_min_num']){
                $this->error(L('商品'.$pro['pdt_sn'].'没有达到限购数量！'));
                return false;
            }
        }
        // 订单促销规则
        $ary_promotion = array();
        //$promition_rule_name
        // 发票信息
        $p_invoice = D('Invoice')->get();
        $invoice_type = explode(",", $p_invoice ['invoice_type']);
        $invoice_head = explode(",", $p_invoice ['invoice_head']);
        $invoice_content = explode(",", $p_invoice ['invoice_content']);

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($ary_member ['m_id']);
        // 获得赠送积分
        $gifts_point_reward = '0';
        $gifts_point_goods_price  = '0';
        foreach($ary_data['ary_product_data'] as $pro){
            if($pro['gifts_point']>0 && isset($pro['gifts_point']) && isset($pro['is_exchange'])){
                $gifts_point_reward += $pro['gifts_point']*$pro['pdt_nums'];
                $gifts_point_goods_price += $pro['pdt_sale_price']*$pro['pdt_nums'];
            }
        }
        $other_all_price = $ary_price_data['all_pdt_price']-$gifts_point_goods_price;
        $other_point_reward = D('PointConfig')->getrRewardPoint($other_all_price);
        $ary_price_data ['reward_point'] = $gifts_point_reward+$other_point_reward;
        // 计算订单可以使用的积分
        $ary_price_data ['is_use_point'] = D('PointConfig')->getIsUsePoint($ary_price_data ['all_pdt_price'],$ary_member['m_id']);
        // 添加产品是否允许购买
        $is_authorize = true;

        foreach ($ary_data ['ary_product_data'] as &$prod) {
            if ($prod [0] ['type'] == '4') {
                foreach ($prod as &$item_info) {
                    $item_info ['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $item_info ['g_id']);
                    if ($item_info ['authorize'] == false) {
                        $is_authorize = false;
                    }
                }
            } else {
                $prod ['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $prod ['g_id']);
                if ($prod ['authorize'] == false) {
                    $is_authorize = false;
                }
            }
        }
        $ary_cart = array();
        foreach ($ary_data ['ary_product_data'] as $key => $info) {
            if (isset($info['pdt_id'])) {
                $ary_cart[$info['pdt_id']] = $info;
                //添加产品是否允许购买
                $ary_cart[$info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $info['g_id']);
            } else {
                //自由组合权限判断
                if ($info[0]['type'] == 4 || $info[0]['type'] == 6) {
                    foreach ($info as $subkey => $sub_info) {
                        $ary_cart[$key][$sub_info['pdt_id']] = $sub_info;
                        //添加产品是否允许购买
                        $ary_cart[$key][$sub_info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $sub_info['g_id']);
                    }
                }
            }
        }
        $promotion_total_price = '0';
        $promotion_price = '0';
        //赠品数组
        $cart_gifts = array();
		$i=0;
        foreach ($pro_datas as $keys => $vals) {
			$int_promotion_count = count($vals['products']);
			//促销总金额
			$int_total_promotion_price = 0;
            foreach ($vals ['products'] as $key => $val) {
                $arr_products = $this->cart->getProductInfo(array(
                    $key => $val
                        ));
                if ($arr_products [0] [0] ['type'] == '4' || $arr_products [0] [0] ['type'] == '6') {
                    foreach ($arr_products [0] as &$provals) {
                        $provals ['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $provals ['g_id']);
                    }
                }
                $pro_datas [$keys] ['products'] [$key] = $arr_products [0];
                $pro_data [$key] = $val;
                $pro_data [$key] ['pmn_name'] = $vals ['pmn_name'];
				//购物车优惠优惠金额放到订单明细里拆分
				if($keys != 0 && !empty($vals['pro_goods_discount'])){
					if($int_promotion_count == $i+1){
						$pro_datas [$keys] ['products'] [$key]['promotion_price'] = $vals['pro_goods_discount']-$int_total_promotion_price;
					}else{
						$pro_datas [$keys] ['products'] [$key][$keys]['promotion_price'] = sprintf("%.2f", ($val['f_price']*$val['pdt_nums']/$vals['goods_total_price'])*$vals['pro_goods_discount']);
						$int_total_promotion_price = $int_total_promotion_price+$v['promotion_price'];
					}
				}
            }
            //赠品数组
            if (!empty($vals['gifts'])) {
                foreach ($vals['gifts'] as $gifts) {
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                    $cart_gifts[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2);
                }
            }
            $promotion_total_price += $vals ['goods_total_price']; // 商品总价
            if ($keys != '0') {
                $promotion_price += $vals ['pro_goods_discount'];
                $promotion_jlb += $vals ['pro_goods_mjlb'];
            }
			$i++;
        }
        
        //获取赠品信息
        if (!empty($cart_gifts)) {
            $cart_gifts_data = array();
            $cart_gifts_data = $this->cart->getProductInfo($cart_gifts);
            $ary_tmp_cart = array_merge($ary_tmp_cart,$cart_gifts);
            foreach($ary_tmp_cart as $atck=>$atcv){
                $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                unset($ary_tmp_cart[$atck]);
            }
            $all_gifts_weight = sprintf("%0.2f", D('Orders')->getGoodsAllWeight(array('gifts'=>$cart_gifts)));
            $ary_price_data ['all_weight'] += $all_gifts_weight;
        }
        // 满足满包邮条件
        foreach ($pro_datas as $pro_data) {
            if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                foreach($pro_data['products'] as $proDatK=>$proDatV){
                    unset($ary_tmp_cart[$proDatK]);
                }
            }
        }
        if(empty($ary_tmp_cart)){
            $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
        }
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_tmp_cart);
            }
        }
        
        //更新会员等级
        D("MembersLevel")->autoUpgrade($ary_member ['m_id']);
        $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
        $ary_price_data ['pre_price'] = sprintf("%0.2f", $promotion_price);
        $ary_price_data ['total_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price) > 0 ? sprintf("%0.2f", $promotion_total_price - $promotion_price) : '0.00';
        //需消耗总积分
        $ary_price_data['consume_point'] = intval($subtotal['goods_all_point']);
        
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_price_data ['total_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
        //是否开启重复下单提示
		$is_confirm_order = D('SysConfig')->getCfg('IS_CONFIRM_ORDER','IS_CONFIRM_ORDER','0','是否开启重复下单提示');
        $this->assign($is_confirm_order);
        $this->assign("is_authorize", $is_authorize);
        $this->assign("cart_data", $ary_cart);
        $this->assign("promotion", $pro_datas);
        // 赠品
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        
        //echo "<pre>";print_r($ary_price_data);die();
        // 页面输出
        $this->assign('ary_addr', $ary_data ['ary_addr']);
        $this->assign('default_addr', $ary_data ['default_addr']);
        // 配送公司
        $this->assign('ary_logistic', $ary_logistic);
        // 支付方式
        $ary_paymentcfg = array();
        foreach($ary_data['payment_cfg'] as $k => $paymentcfg){
            $ary_paymentcfg[$k]['pc_id'] = $paymentcfg['pc_id'];
            $ary_paymentcfg[$k]['pc_custom_name'] = $paymentcfg['pc_custom_name'];
            $ary_paymentcfg[$k]['pc_memo'] = $paymentcfg['pc_memo'];
            $ary_paymentcfg[$k]['pc_fee'] = ($paymentcfg['pc_fee'] == "0.000")?'':(float)$paymentcfg['pc_fee']."元";
            if($paymentcfg['pc_pay_type'] == "alipay" && $paymentcfg['pc_fee'] != "0.000"){
                $ary_paymentcfg[$k]['pc_fee'] = (float)$paymentcfg['pc_fee']."%";
            }
        }
        $this->assign('ary_paymentcfg', $ary_paymentcfg);
        // 发票信息
        $this->assign('invoice_info', $invoice_info);
        $this->assign('invoice_content', $invoice_content);
        // 发票收藏列表
        $this->assign('invoice_list', $invoice_list);
        // 促销优惠价
        $this->assign('fla_pmn_price', $ary_price_data['pre_price']);
        // 订单锁定
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $this->assign('order_lock', $ary_erp ['ORDER_LOCK']);
        $ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
        $ary_is_show = D('SysConfig')->getCfgByModule('IS_SHOW');
        $this->assign('is_show', $ary_is_show['IS_SHOW']);
        $ary_member = D('Members')->where(array('m_id'=>$_SESSION ['Members'] ['m_id']))->find();
        //是否开启红包
        $bonus_set = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
        if($bonus_set['BONUS_AUTO_OPEN'] == 1){
            $this->assign('bonus', $ary_member['m_bonus']);
        }
        //是否开启储值卡
        /* $cards_set = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
        if($cards_set['CARDS_AUTO_OPEN'] == 1){
            $this->assign('cards', $ary_member['m_cards']);
        } */
        //是否开启金币
		/**
        $jlb_set = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($jlb_set['JIULONGBI_AUTO_OPEN'] == 1){
            $this->assign('jlb', $ary_member['m_jlb']);
            // 促销获赠金币
            $this->assign('fla_pmn_jlb', $promotion_jlb);
        }
		**/
        //是否开启积分抵金
        $pointCfg = D('PointConfig')->getConfigs();
        $this->assign('point',$pointCfg['is_buy_consumed']);
        
        $str_pids = '';
        foreach ($ary_cart as $pid){
            $str_pids .= $pid['pdt_id'].',';
        }
        $str_pids = trim($str_pids,',');
        $this->assign('pids',$str_pids);
        // 订单促销规则名称
        $this->assign("promition_rule_name", $promition_rule_name);
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);
        $this->display('pageAdd');
    }

    /**
	 * 团购订单确认页
	 */
	public function pageBulkAdd() {
		
		$param_bulk = $_SESSION ['bulk_cart'];
		
		if (empty ( $param_bulk )) {
			$this->error ( '请选择团购商品！',U('Home/Groupbuy/tuan') );
		} // print_r($param_bulk);exit;
		  // unset($_SESSION['bulk_cart']);
		$groupbuy = M ( 'groupbuy', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$gp_price = M ( 'related_groupbuy_price', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$gp_city = M ( 'related_groupbuy_area', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$ary_data = array ();
		// 获取常用收货地址
		$ary_addr = $this->cityRegion->getReceivingAddress ( $_SESSION ['Members'] ['m_id'] );
		if (count ( $ary_addr ) > 0) {
			$ary_data ['default_addr'] = $ary_addr [0];
			unset ( $ary_addr [0] );
		}
		$ary_data ['ary_addr'] = $ary_addr;
		// 获取支付方式
		$payment = D ( 'PaymentCfg' );
		$payment_cfg = $payment->getPayCfg ();
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
		$invoice_list = D ( 'InvoiceCollect' )->get ( $_SESSION ['Members'] ['m_id'] );
		
		// 获取最后一次支付方式
            
		
		$array_where = array (
				'is_active' => 1,
				'gp_id' => $param_bulk ['gp_id'] 
		);
		$data = $groupbuy->where ( $array_where )->find ();
		if (empty ( $data )) {
			$this->error ( '团购商品不存在！' );
		}
		$data['cust_price'] = M('goods_info')->where(array('g_id'=>$data['g_id']))->getField('g_price');
		// 取出价格阶级
		$rel_bulk_price = $gp_price->where(array('gp_id'=>$data['gp_id']))->select();
		$buy_nums = $data['gp_pre_number'] + $data['gp_now_number'];
		$array_f = array ();
		foreach ( $rel_bulk_price as $rbp_k => $rbp_v ) {
			if ($buy_nums >= $rbp_v ['rgp_num']) {
				$array_f[$rbp_v ['related_price_id']] = $rbp_v ['rgp_num'];
			}
		}
       
		if (! empty ( $array_f )) {
			$array_max = new ArrayMax ($array_f);
			$rgp_num = $array_max->arrayMax ();
			$data['gp_price'] = $gp_price->where(array('gp_id'=>$data['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
		}//echo "<pre>";print_r($data);die();
		// 获取商品基本信息
		$field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic';
		$goods_info = D('GoodsInfo')->field($field)->where (array('g_id' => $data ['g_id']))->find();
		$goods_info ['gpic'] = '/'.ltrim($goods_info['gpic'],'/');
		$goods_info ['save_price'] = $goods_info ['gprice'] - $data ['gp_price'];
		// 授权线判断是否允许购买
		$goods_info ['authorize'] = true;
		if (! empty ( $goods_info ) && is_array ( $goods_info )) {
			$ary_product_feild = array (
					'pdt_sn',
					'pdt_weight',
					'pdt_stock',
					'pdt_memo',
					'pdt_id',
					'pdt_sale_price',
					'pdt_market_price',
					'pdt_on_way_stock' 
			);
			$where = array ();
			$where ['g_id'] = $data ['g_id'];
			$where ['pdt_status'] = '1';
			$ary_pdt = M ( 'goods_products ', C ( 'DB_PREFIX' ), 'DB_CUSTOM' )->field ( $ary_product_feild )->where ( $where )->limit ()->select ();
			if (! empty ( $ary_pdt ) && is_array ( $ary_pdt )) {
				$skus = array ();
				$ary_zhgoods = array ();
				foreach ( $ary_pdt as $kypdt => $valpdt ) {
					$specInfo = D ( 'GoodsSpec' )->getProductsSpecs ( $valpdt ['pdt_id'] );
					
					$ary_pdt [$kypdt] ['specName'] = $specInfo ['spec_name'];
					
					$ary_pdt [$kypdt] ['pdt_sale_price'] = sprintf ( '%.2f', $valpdt ['pdt_sale_price'] );
					$ary_pdt [$kypdt] ['pdt_market_price'] = sprintf ( '%.2f', $valpdt ['pdt_market_price'] );
					if ($param_bulk ['pdt_id'] == $valpdt ['pdt_id']) {
						$data ['sku'] = $ary_pdt [$kypdt];
					}
				
				}
				foreach ( $skus as $key => &$sku ) {
					$skus [$key] = array_unique ( $sku );
				}
			
			}
			if (! empty ( $skus )) {
				$goods_info ['skuNames'] = $skus;
			} else {
				$goods_info ['pdt_id'] = $ary_pdt [0] ['pdt_id'];
			}
		}
		$goods_info ['skus'] = $ary_pdt;
		
		$data ['sku'] ['num'] = $param_bulk ['num'];
		$data ['sku'] ['coupon_price'] = $data ['sku'] ['pdt_sale_price'] - $data ['gp_price'];
		$data ['sku'] ['all_cprice'] = $data ['sku'] ['coupon_price'] * $param_bulk ['num'];
		$data ['sku'] ['all_price'] = $data ['gp_price'] * $param_bulk ['num'];
		$data ['sku'] ['all_sale_price'] = $data ['sku'] ['pdt_sale_price'] * $param_bulk ['num'];
		
		$data ['sku'] ['all_weight'] = $data ['sku'] ['pdt_weight'] * $param_bulk ['num'];
		
		$data ['good_info'] = $goods_info;
		if ($data ['is_deposit'] == 1) {
			$data ['gp_deposit_price'] = sprintf ( '%.2f', $data ['gp_deposit_price'] * $data ['sku'] ['num'] );
		}
		
		$i = 0;
		foreach ( $ary_logistic as $logustic_key => $logustic_val ) {
			if ($i == 0) {
				$ary_cart [$data ['sku'] ['pdt_id']] = array (
						'pdt_id' => $data ['sku'] ['pdt_id'],
						'num' => $data ['sku'] ['num'],
						'type' => 0 
				);
				$logistic_price = D ( 'Logistic' )->getLogisticPrice ( $logustic_val ['lt_id'], $ary_cart );
			}
			$i ++;
		}
        
        $ary_cart [$data ['sku'] ['pdt_id']] = array(
                    'pdt_id' => $data ['sku'] ['pdt_id'],
                    'num' => $data ['sku'] ['num'],
                    'type' => 0,
                    'g_id'=>$data['g_id'],
                );
        // 获取配送公司表
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
            }
        }
        
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $data['sku']['all_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
		$data ['sku'] ['bulk_price'] = $data ['sku'] ['all_price'];
		$data ['sku'] ['all_price'] += $logistic_price;
		 //echo "<pre>";print_r($data);exit;
		$this->assign ( 'product_data', $data );
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
        /*标明这个是团购订单确认页*/
        $this->assign ( 'web_type', 'Bulk' );
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);		
		$this->display ();
	}

   
    
   
    /**
     * 显示常用地址页面
     * 删除，添加 地址页面 这里显示
     */
    public function getAddressPage() {
        $bool_return = false;
        $ary_post_data = $this->_post();
        $m_id = $_SESSION ['Members'] ['m_id'];
        if (isset($ary_post_data ['del']) || $ary_post_data ['del'] == 'del') {
            // 删除
            $int_ra_id = $ary_post_data ['ra_id'];
            $bool_return = $this->cityRegion->doDelDeliver($int_ra_id);
        } else {
            // 添加
            if ($ary_post_data ['cr_id'] == 0) {
                if(empty($ary_post_data['region'])){
                   $ary_post_data['region']=$ary_post_data['city'];
                }
                $ary_post_data ['cr_id'] = $ary_post_data ['region'];
            }
            $ary_post_data ['ra_phone'] = $ary_post_data ['ra_phone_area'] . '-' . $ary_post_data ['ra_phone'] . '-' . $ary_post_data ['ra_phone_ext'];
            //默认设为默认地址
			$ary_post_data ['ra_is_default'] = '1';
			$bool_return = $this->cityRegion->addReceiveAddr($ary_post_data, $m_id);
            $int_ra_id = $bool_return ['data'] ['ra_id'];
        }

        if ($bool_return) {
            $ary_return ['status'] = 1;
            $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
            if (!empty($ary_addr)) {
                if (isset($ary_post_data ['del']) || $ary_post_data ['del'] == 'del') {
                    $ary_return ['data'] = $ary_addr [0];
                    $ary_return ['msg'] = '删除成功';
                    $ary_return ['num'] = count($ary_addr);
                } else {
                    foreach ($ary_addr as $val) {
                        if ($val ['ra_id'] == $int_ra_id) {
                            $ary_return ['data'] = $val;
                            $ary_return ['msg'] = '添加成功';
                            $ary_return ['num'] = count($ary_addr);
                            break;
                        }
                    }
                }
            }
            $this->ajaxReturn($ary_return);
        }
    }
    /**
     * 配送方式页面
     */
    public function getLogisticType() {
        $cr_id = $this->_post('cr_id');
        $goods_pids = $this->_post('pids');
        $ary_member = session("Members");
        if (!empty($ary_member ['m_id'])) {
			//秒杀商品
			switch($goods_pids){
				case 'spike':
				$ary_cart = array();
				$ary_cart = $_SESSION ['spike_cart'];				
				break;
				case 'bulk':
				$ary_cart = array();
				$ary_cart = $_SESSION['bulk_cart'];				
				break;
				case 'presale':
				$ary_cart = array();
				$ary_cart = $_SESSION['spike_cart'];				
				break;
				default:
					$cart_data = D('Cart')->GetData();
					$ary_pid = explode(',',$goods_pids);
					foreach ($cart_data as $key=>$cd){
						foreach ($ary_pid as $pid){
							if($pid == $key){
								$ary_cart[$pid] = $cart_data[$key];
							}
						}
					}					
			}
        } else {
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
        }
        $ary_tmp_cart = $ary_cart;
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as $key => $val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                }
            }
        }
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
        //赠品数组
        $gifts_cart = array();
        foreach ($pro_datas as $keys => $vals) {
            //赠品数组
            if (!empty($vals['gifts'])) {
                foreach ($vals['gifts'] as $gifts) {
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                    $gifts_cart[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2,'g_id' => $gifts['g_id']);
                }
            }
        }
        if(!empty($gifts_cart)){
            $ary_tmp_cart = array_merge($ary_cart,$gifts_cart);
            foreach($ary_tmp_cart as $atck=>$atcv){
                $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                unset($ary_tmp_cart[$atck]);
            }
        }
        foreach ($pro_datas as $pro_data) {
            if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                foreach($pro_data['products'] as $proDatK=>$proDatV){
                    unset($ary_tmp_cart[$proDatK]);
                }
            }
        }
        if(empty($ary_tmp_cart)){
            $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
        }
        $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_tmp_cart,$ary_member['m_id']);
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as &$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $pro_datas['subtotal']['goods_total_price'] >= $lt_expressions['logistics_configure']){
                $logistic_v['logistic_price'] = 0;
            }
        }
        $this->assign('ary_logistic', $ary_logistic);
        $this->display();
    }

    /**
     * 订单确认信息提交
     */
    public function doOrderAdd() {
        $return_orders = false;
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_orders = $this->_post();
        $ary_member = session("Members");
        if (!empty($ary_member ['m_id'])) {
            if (isset($ary_orders ['gp_id'])) {
                // 团购商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id' => $ary_orders ['pdt_id'],
                    'num' => $ary_orders ['num'],
                    'gp_id' => $ary_orders ['gp_id'],
                    'type' => 5
                );
            } else {
                $ary_cart = D('Cart')->GetData();
            }
        } else {
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
        }
        foreach ($ary_cart as $ary) {
            // 自由推荐商品
            if ($ary ['type'] == 4) {
                foreach ($ary ['pdt_id'] as $pdtId) {
                    $g_id = D("GoodsProducts")->where(array(
                                'pdt_id' => $pdtId
                            ))->getField('g_id');
                    $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $g_id);
                    if (empty($is_authorize)) {
                        $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array(
                            '返回购物车' => U('Ucenter/Cart/pageList')
                        ));
                        exit();
                    }
                }
            } else {
                $g_id = D("GoodsProducts")->where(array(
                            'pdt_id' => $ary ['pdt_id']
                        ))->getField('g_id');
                $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $g_id);
                if (empty($is_authorize)) {
                    $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array(
                        '返回购物车' => U('Ucenter/Cart/pageList')
                    ));
                    exit();
                }
            }
        }
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        // echo "<pre>";print_r($ary_orders['no_invoices']);exit;
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
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
                } else {

                    if (isset($ary_orders ['is_default']) && !empty($ary_orders ['is_default'])) {

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
            } else {
                unset($ary_orders ['invoice_type']);
                unset($ary_orders ['invoice_head']);
                unset($ary_orders ['invoice_people']);
                unset($ary_orders ['invoice_name']);
                unset($ary_orders ['invoice_content']);
                unset($ary_orders ['invoices_val']);
            }
            // echo "<pre>";print_r($ary_orders);exit;
            $ary_receive_address = $this->cityRegion->getReceivingAddress($ary_member ['m_id']);
            foreach ($ary_receive_address as $ara_k=>$ara_v){
                if($ara_v['ra_id'] == $ary_orders['ra_id']){
                    $default_address ['default_addr'] = $ara_v;
                }
            }
            if (isset($default_address ['default_addr'] ['ra_id'])) {
                // 收货人
                $ary_orders ['o_receiver_name'] = $default_address ['default_addr'] ['ra_name'];
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

            // 会员id
            $ary_orders ['m_id'] = $ary_member ['m_id'];
            // 订单id
            $ary_orders ['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
            // 物流费用
            if (!empty($default_address ['default_addr']) && is_array($default_address ['default_addr'])) {
                $ra_is_default = $default_address ['default_addr'] ['ra_is_default'];
                if ($ra_is_default == 1) {
                    $cr_id = $default_address ['default_addr'] ['cr_id'];
                    $ary_logistic = $this->logistic->getLogistic($cr_id);
                    foreach ($ary_logistic as $logistic) {
                        $logistic_price = 0;
                        if ($logistic ['lt_id'] == $ary_orders ['lt_id']) {
                            $logistic_price = $logistic ['logistic_price'];
                            break;
                        }
                    }
                }
            }
            $ary_orders ['o_cost_freight'] = $logistic_price;
            if (empty($ary_orders ['lt_id'])) {
                $this->success(L('SELECT_LOGISTIC'));
                exit();
            }

            // 商品总价
            $ary_orders ['o_goods_all_price'] = 0;
            $m_id = $_SESSION ['Members'] ['m_id'];
            $price = new PriceModel($m_id);
            if (!empty($ary_cart) && is_array($ary_cart)) {
                foreach ($ary_cart as $k => $v) {
                    // 自由组合
                    if ($v ['type'] == 4) {
                        foreach ($v ['pdt_id'] as $key => $pdt_id) {
                            // 自由推荐价格
                            $ary_orders ['o_goods_all_price'] += sprintf("%0.3f", $v ['num'] [$key] * $price->getItemPrice($pdt_id, 0, 4));
                            $free_all_price += sprintf("%0.3f", $v ['num'] [$key] * $price->getItemPrice($pdt_id, 0, 4));
                        }
                    } else if ($v ['type'] == 5) {
                        // 获取团购价与商品原价
                        $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 5, $ary_orders ['gp_id']);
                        $ary_orders ['o_goods_all_price'] = sprintf("%0.3f", $v ['num'] [$key] * $array_all_price ['pdt_price']);
                        $o_all_price = sprintf("%0.3f", $v ['num'] [$key] * $array_all_price ['discount_price']);
                        $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_price'] - $o_all_price);
                    } else {
                        if ($k == 'gifts') {
                            $ary_orders ['o_goods_all_price'] += 0;
                            $gifts_cart = $v;
                            unset($ary_cart [$k]);
                        } elseif ($v ['type'] == 3) {
                            $ary_orders ['o_goods_all_price'] += sprintf("%0.3f", $v ['num'] * $price->getItemPrice($k));
                            $combo_all_price += sprintf("%0.3f", $v ['num'] * $price->getItemPrice($k));
                        } else {
                            if (!isset($v ['type']) || $v ['type'] == 0) {
                                $ary_orders ['o_goods_all_price'] += sprintf("%0.3f", $v ['num'] * $price->getItemPrice($k));
                            }
                        }
                    }
                }
            }
            $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
            // 优惠券金额
            if (isset($ary_orders ['coupon_input'])) {
                $str_csn = $ary_orders ['coupon_input'];
                $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
                if (!empty($ary_coupon) && is_array($ary_coupon)) {
                    $ary_orders ['o_coupon_menoy'] = $ary_coupon ['c_money'];
                    $all_goods_price = $ary_orders ['o_goods_all_price'] + $ary_coupon ['c_money'];
                }
            }
            // 整单促销规则
            foreach ($ary_data ['ary_product_data'] as $info) {
                if ($info [0] ['type'] != '4') {
                    if (!empty($info ['rule_info'] ['pmn_id']) && empty($ary_pdt_info ['rule_info'] ['pmn_id'])) {
                        $ary_pdt_info ['rule_info'] = $info ['rule_info'];
                    }
                    if ($info ['type'] != 3) {
                        $ary_pdt_info [$info ['pdt_id']] = array(
                            'pdt_id' => $info ['pdt_id'],
                            'rule_info' => $info ['rule_info'],
                            'num' => $info ['pdt_nums'],
                            'type' => $info ['type'],
                            'price' => $info ['f_price']
                        );
                    }
                }
            }

            $ary_param = array(
                'action' => 'order',
                'mid' => $ary_member ['m_id'],
                'all_price' => $ary_orders ['o_goods_all_price'] - $combo_all_price - $free_all_price,
                'ary_pdt' => $ary_pdt_info
            );
            $orders_discount = D('Price')->getOrderPrice($ary_param);

            $promotion_price = 0;
            if ($orders_discount ['all_price'] > 0 && !empty($orders_discount ['code'])) {
                // 促销优惠价格
                if ($orders_discount ['code'] == 'MBAOYOU' || $orders_discount ['code'] == 'MZENPIN' || $orders_discount ['code'] == 'MQUAN') {
                    if ($orders_discount ['code'] == 'MBAOYOU' && $logistic_price < $orders_discount ['price']) {
                        $fla_pmn_price = sprintf("%0.2f", $logistic_price);
                    } else {
                        $fla_pmn_price = sprintf("%0.2f", $orders_discount ['price']);
                    }
                } else {
                    $fla_pmn_price = sprintf("%0.2f", $orders_discount ['all_price'] - $orders_discount ['price']);
                    // 获取满减后优惠金额
                    if ($orders_discount ['code'] == 'MJIAN') {
                        $ary_orders ['o_promotion_price'] = sprintf("%0.2f", $fla_pmn_price - $logistic_price);
                    }
                }
                $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $orders_discount ['all_price'] - $fla_pmn_price);
            } else {
                // $ary_orders['o_goods_all_price'] =
                // sprintf("%0.2f",$orders_discount['all_price']) ;
            }
            // 订单总价 商品会员折扣价-优惠券金额
            if (!isset($ary_orders ['gp_id'])) {
                $all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'] - $promotion_price;
            } else {
                $all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_discount'] - $promotion_price;
            }

            if ($all_price <= 0) {
                $all_price = 0;
            }
            // 订单应付总价 订单总价+运费
            $all_price += $ary_orders ['o_cost_freight'];

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
            if (!isset($ary_orders ['gp_id'])) {
                $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['goods_all_price'] - $ary_orders ['o_goods_all_price']);
            }
            // 发货备注
            if (!empty($ary_orders ['shipping_remarks'])) {
                $ary_orders ['o_shipping_remarks'] = $ary_orders ['shipping_remarks'];
                unset($ary_orders ['shipping_remarks']);
            }
            $ary_orders_goods = $this->cart->getProductInfo($ary_cart);
            // print_r($ary_orders);exit;
            // 管理员操作者ID
            if ($ary_member ['admin_id']) {
                $ary_orders ['o_addorder_id'] = $ary_member ['admin_id'];
            }
            $bool_orders = D('Orders')->doInsert($ary_orders);
            // $bool_orders = true;
            if (!$bool_orders) {
                $orders->rollback();
                $this->error('订单创建失败', array(
                    '失败' => U('Ucenter/Orders/OrderFail')
                ));
                exit();
            } else {
                if (isset($ary_coupon ['c_sn'])) {
                    // 更新优惠券使用
                    $ary_data = array(
                        'c_is_use' => 1,
                        'c_used_id' => $_SESSION ['Members'] ['m_id'],
                        'c_order_id' => $ary_orders ['o_id']
                    );
                    $res_coupon = D('Coupon')->doCouponUpdate($ary_coupon ['c_sn'], $ary_data);

                    if (!$res_coupon) {
                        $this->error('优惠券使用失败', array(
                            '失败' => U('Ucenter/Orders/OrderFail')
                        ));
                        exit();
                    }
                } // exit;
                $ary_orders_items = array();

                $ary_orders_goods = $this->cart->getProductInfo($ary_cart);
                // print_r($ary_orders_goods);exit;
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
                    foreach ($ary_orders_goods as $k => $v) {
                        $ary_orders_items = array();
                        if ($v ['type'] == 3) {
                            $combo_list = D('ReletedCombinationGoods')->getComboList($v ['pdt_id']);
                            if (!empty($combo_list)) {
                                foreach ($combo_list as $combo) {
                                    // 订单id
                                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                    // 商品id
                                    $combo_item_data = D('GoodsProducts')->Search(array(
                                        'pdt_id' => $combo ['releted_pdt_id']
                                            ), array(
                                        'g_sn',
                                        'g_id'
                                            ));
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
                                    $combo_good_data = D('GoodsInfo')->Search(array(
                                        'g_id' => $combo_item_data ['g_id']
                                            ), array(
                                        'g_name'
                                            ));
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
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
                                        $this->error('订单明细新增失败', array(
                                            '失败' => U('Ucenter/Orders/OrderFail')
                                        ));
                                        exit();
                                    }
                                    // 商品库存扣除
                                    $ary_payment_where = array(
                                        'pc_id' => $ary_orders ['o_payment']
                                    );
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                        // by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array(
                                                    'g_pre_sale_status'
                                                ))->where(array(
                                                    'g_id' => $ary_orders_items ['g_id']
                                                ))->find();
                                        if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                            $array_result = D('GoodsProducts')->UpdateStock($combo ['releted_pdt_id'], $ary_orders_items ['oi_nums']);
                                            if (false == $array_result ["status"]) {
                                                D('GoodsProducts')->rollback();
                                                $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {

                            // 自由推荐商品
                            if ($v [0] ['type'] == '4') {
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
                                    // 自由组合ID
                                    $ary_orders_items ['fc_id'] = $item_info ['fc_id'];
                                    // 商品积分
                                    if (isset($v [0] ['type']) && $v [0] ['type'] == 4) {
                                        $ary_orders_items ['oi_type'] = 4;
                                        $int_pdt_sale_price += $item_info ['pdt_sale_price'] * $item_info ['pdt_nums'];
                                    }
                                    // 商品数量
                                    $ary_orders_items ['oi_nums'] = $item_info ['pdt_nums'];
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
                                        $this->error('订单明细新增失败', array(
                                            '失败' => U('Ucenter/Orders/OrderFail')
                                        ));
                                        exit();
                                    }
                                    // 商品库存扣除
                                    $ary_payment_where = array(
                                        'pc_id' => $ary_orders ['o_payment']
                                    );
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                        // by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array(
                                                    'g_pre_sale_status'
                                                ))->where(array(
                                                    'g_id' => $item_info ['g_id']
                                                ))->find();
                                        if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                            $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $item_info ['pdt_nums']);
                                            if (false == $array_result ["status"]) {
                                                D('GoodsProducts')->rollback();
                                                $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                            }
                                        }
                                    }
                                }
                            } elseif ($v ['type'] == '5') { // 团购商品
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $v ['g_id'];
                                // 团购商品ID,取一下
                                $fc_id = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'g_id' => $v ['g_id'],
                                            'deleted' => '0',
                                            'is_active' => '1'
                                        ))->getField('gp_id');
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
                                // 团购商品
                                $ary_orders_items ['oi_type'] = $v ['type'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $array_all_price ['discount_price'];
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $this->error('订单明细新增失败', array(
                                        '失败' => U('Ucenter/Orders/OrderFail')
                                    ));
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
                                    $this->error('团购日志失败', array(
                                        '失败' => U('Ucenter/Orders/OrderFail')
                                    ));
                                    exit();
                                }

                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array(
                                                'g_pre_sale_status'
                                            ))->where(array(
                                                'g_id' => $v ['g_id']
                                            ))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                        if (false == $array_result ["status"]) {
                                            D('GoodsProducts')->rollback();
                                            $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            } else {
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
                                if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
                                    $gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
                                    $gifts_point_goods_price += $v['pdt_sale_price']*$v['pdt_nums'];
                                }
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $this->error('订单明细新增失败', array(
                                        '失败' => U('Ucenter/Orders/OrderFail')
                                    ));
                                    exit();
                                }
                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array(
                                                'g_pre_sale_status'
                                            ))->where(array(
                                                'g_id' => $v ['g_id']
                                            ))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                                        if (false == $array_result ["status"]) {
                                            D('GoodsProducts')->rollback();
                                            $this->error($array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            }
                        }
                        // 产品销量
                        if ($v [0] ['type'] == '4') {
                            foreach ($v as $good) {
                                $ary_goods_num = M("goods_info")->where(array(
                                            'g_id' => $good ['g_id']
                                        ))->data(array(
                                            'g_salenum' => array(
                                                'exp',
                                                'g_salenum + 1'
                                            )
                                        ))->save();
                                if (!$ary_goods_num) {
                                    $orders->rollback();
                                    $this->error('销量添加失败', array(
                                        '失败' => U('Ucenter/Orders/OrderFail')
                                    ));
                                    exit();
                                }
                            }
                        } else {
                            $ary_goods_num = M("goods_info")->where(array(
                                        'g_id' => $v ['g_id']
                                    ))->data(array(
                                        'g_salenum' => array(
                                            'exp',
                                            'g_salenum + 1'
                                        )
                                    ))->save();
                            if (!$ary_goods_num) {
                                $orders->rollback();
                                $this->error('销量添加失败', array(
                                    '失败' => U('Ucenter/Orders/OrderFail')
                                ));
                                exit();
                            }
                        }
                    }

                    // 商品下单获得总积分
                    $other_all_price = $int_pdt_sale_price-$gifts_point_goods_price;
                    $total_reward_point = D('PointConfig')->getrRewardPoint($other_all_price);
                    $total_reward_point += $gifts_point_reward;

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
                            $this->error('更新冻结积分失败', array(
                                '失败' => U('Ucenter/Orders/OrderFail')
                            ));
                            exit();
                        }
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
                $this->error('订单日志记录失败', array(
                    '失败' => U('Ucenter/Orders/OrderFail')
                ));
                exit();
            }
            $orders->commit();
            if (!empty($_SESSION ['Members'] ['m_id'])) {
                $Cart = D('Cart')->DelMycart();
            } else {
                unset($_SESSION ['Cart']);
            }
            $this->success('订单提交成功，请您尽快付款！', array(
                '付款' => U("Ucenter/Orders/OrderSuccess", array(
                    'oid' => $order_id
                ))
            ));
            exit();
        }
    }
	public function getIp() {
        $ip = "";
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    /**
     * 订单确认信息提交
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-09 10:50:00
     */
    public function doAdd() {
        $return_orders = false;
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_datas = $this->_post();
		$ary_member = session("Members");
		//order queue add by zhangjiasuo 2014-10-29 start 
		$ary_datas ['m_id']=$ary_member['m_id'];
		$ary_datas ['ml_id']=$ary_member['ml_id'];
		$ary_datas ['admin_id']=$ary_member['admin_id'];
		$ary_config = D('SysConfig')->getConfigs("GY_CAHE");
		
		if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化开始
			$queue_name='imidea';
			$queue_obj = new Queue($queue_name);
			$queue_obj->add($ary_datas);
			$ary_queue_datas=$queue_obj->get(1);
			$ary_orders=array_pop($ary_queue_datas);
			if(empty($ary_orders)){
				$queue_obj->unLock();
			}
		}else{
			$ary_orders=$ary_datas;
		}
		if(empty($ary_orders)){
			//$ary_orders=$ary_datas;
		}
		if(empty($ary_orders)){
			$this->error('没有要购买的商品，请重新选择商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
		}
        $now_tome = date('Y-m-d',time());
        if($ary_orders['o_receiver_time'] < $now_tome && $ary_orders['o_receiver_time'] != ''){
			if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
				$queue_obj->unLock();
			}
            $this->error('请选择正确的送货时间!');
            die;
        }
		$str_code = $_SESSION['auto_code'];
		if(isset($ary_orders['originator'])) {
			if($ary_orders['originator'] == $_SESSION['auto_code']){
				//将其清除掉此时再按F5则无效
				unset($_SESSION["auto_code"]);
			}else{
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
                //unset($_SESSION['auto_code']);
				$this->error("订单提交中,请不要刷新本页面或重复提交表单".$_SESSION['auto_code']);
                exit;
			}
		}
        
		//秒杀商品每人限购1件 判断秒杀是否已结束
        if(isset($ary_orders ['sp_id'])){
			//$now_count=D('OrdersItems')->where(array('fc_id'=>$ary_orders ['sp_id'],'oi_type'=>7))->SUM('oi_nums');
            $btween_time = D('Spike')->where(array('sp_id'=>$ary_orders ['sp_id']))->field("sp_start_time,sp_end_time,sp_number,sp_now_number")->find();
			if($btween_time['sp_number'] <= $btween_time['sp_now_number']){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
				
				$this->ajaxReturn(array('status' => 0,'info' => '已售罄'));
                exit;
			}
            
            if(strtotime($btween_time['sp_start_time']) > mktime()){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
				$this->ajaxReturn(array('status' => 0,'info' => '秒杀未开始'));
                exit;
            }
            
            if(strtotime($btween_time['sp_end_time']) < mktime()){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
				$this->ajaxReturn(array('status' => 0,'info' => '秒杀已结束'));
                exit;
            }
            
            $ary_where = array();
            $ary_where[C('DB_PREFIX')."orders.m_id"] = $ary_datas['m_id'];
            $ary_where[C('DB_PREFIX')."spike.sp_id"] = $ary_orders['sp_id'];
            $ary_where[C('DB_PREFIX')."orders.o_status"] = array('neq','2');
            $ary_where[C('DB_PREFIX')."orders_items.oi_type"] = 7;
            $ary_spike=D('Spike')
                        ->field(array(C('DB_PREFIX').'orders_items.fc_id',C('DB_PREFIX').'spike.*'))
                        ->join(C('DB_PREFIX').'orders_items ON '.C('DB_PREFIX').'spike.sp_id = '.C('DB_PREFIX').'orders_items.fc_id')
                        ->join(C('DB_PREFIX').'orders ON '.C('DB_PREFIX').'orders.o_id = '.C('DB_PREFIX').'orders_items.o_id')
                        ->where($ary_where)->find();
            if(!empty($ary_spike) && is_array($ary_spike)){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
                $this->error("秒杀限购1件");
                exit;
            }
        }
        
        //团购判断
        if(isset($ary_orders ['gp_id'])){
			$now_count =  D('OrdersItems')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
						  ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
						  ->where(array('fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$ary_orders ['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
						  ->find();
            $btween_time = D('Groupbuy')->where(array('gp_id'=>$ary_orders ['gp_id']))->field("gp_start_time,gp_end_time,gp_number")->find();
			if($now_count['buy_nums'] >= $btween_time['gp_number']){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
				$_SESSION['bulk_cart'] = "";
				 $this->error('已售罄',U('Home/Groupbuy/tuan'));
                exit;
			}

            if(strtotime($btween_time['gp_start_time']) > mktime()){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
                $this->error('团购未开始',U('Home/Bulk'));
                exit;
            }
            
            if(strtotime($btween_time['gp_end_time']) < mktime()){
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
                $this->error('团购已结束',U('Home/Bulk'));
                exit;
            }
            
            $array_where = array('is_active'=>1,'gp_id'=>$ary_orders['gp_id'],'deleted'=>0);
            $data = D('Groupbuy')->where($array_where)->find();
            $m_id = $_SESSION['Members']['m_id'];
            if($m_id){
                //当前会员已购买数量
                $member_buy_num =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
				->where(array('fx_orders.m_id'=>$m_id,'fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$data['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                ->find();
				//目前可以购买的数量
                $thisGpNums = $data['gp_number'] - $now_count['now_count'];
                //如果会员限购数量大于当前会员已购买数量
                if($data['gp_per_number'] > $member_buy_num['buy_nums']){
                    //当前会员最多可以购买的数量
                    $gp_number = $data['gp_per_number'] - $member_buy_num['buy_nums'];
                    //如果会员最多可以购买的数量大于目前库存，将库存赋予会员购买数量
                    if(($ary_orders['num'] > $gp_number) || ($ary_orders['num'] > $thisGpNums)){
                        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
							$queue_obj->unLock();
						}
						$this->error ( '卖光了或购买数量已达上限！' );
                        exit;
                    }
                }else{
					if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
						$queue_obj->unLock();
					}
                    $this->error ( '卖光了或购买数量已达上限！' );
                    exit;
                }
            }
        }  
        if (!empty($ary_datas ['m_id'])) {
            if (isset($ary_orders ['gp_id'])) {
                // 团购商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id' => $ary_orders ['pdt_id'],
                    'num' => $ary_orders ['num'],
                    'gp_id' => $ary_orders ['gp_id'],
                    'type' => 5
                );
            } else if(isset($ary_orders ['sp_id'])){
                // 秒杀商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id' => $ary_orders ['pdt_id'],
                    'num' => $ary_orders ['num'],
                    'sp_id' => $ary_orders ['sp_id'],
                    'type' => 7
                );
            } else if(isset($ary_orders ['p_id'])){
                //预售商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id'=>$ary_orders['pdt_id'],
                    'num'=>$ary_orders['num'],
                    'p_id'=>$ary_orders['p_id'],
                    'type'=> 8
                );
            }else {
                $goods_pids = $ary_orders['goods_pids'];
                if(empty($goods_pids) && !isset($goods_pids)){
					if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
						$queue_obj->unLock();
					}
                    $this->error('没有要购买的商品，请重新选择商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
					
                }else{
                    $cart_data = D('Cart')->GetData();
                    $ary_pid = explode(',',$goods_pids);
                    foreach ($cart_data as $key=>$cd){
                        foreach ($ary_pid as $pid){
                            if($pid == $key){
                                $ary_cart[$pid] = $cart_data[$key];
                            }
                        }
                    }
                }
                
            }
        } else {
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
        }
        foreach ($ary_cart as $ary) {
            // 自由推荐商品
            if ($ary ['type'] == 4 || $ary ['type'] == 6) {
                foreach ($ary ['pdt_id'] as $pdtId) {
                    $g_id = D("GoodsProducts")->where(array(
                                'pdt_id' => $pdtId
                            ))->getField('g_id');
                    $is_authorize = D('AuthorizeLine')->isAuthorize($ary_datas ['m_id'], $g_id);
                    if (empty($is_authorize)) {
						if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
							$queue_obj->unLock();
						}
                        $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
                        exit();
                    }
                    //是否开启区域限售
                    if(GLOBAL_STOCK == TRUE){
                        //临时收货地址
                        
	                    $return_stock = D('GoodsStock')->getProductsWarehouseStock($cr_id, $pdtId);
	                    if(intval($return_stock['pdt_total_stock'])<=2){
	                    	if(!$return_stock['pdt_sn']){
	                    		$pdt_sn = D("GoodsProducts")->where(array(
	                                'pdt_id' => $pdtId
	                            ))->getField('pdt_sn');
	                    	}else{
	                    		$pdt_sn = $return_stock['pdt_sn'];
	                    	}
							if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
								$queue_obj->unLock();
							}
	                    	$this->error('货品编码为'.$pdt_sn.'的商品库存已不足,请先在购物车里删除这些商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
	                        exit();
	                    }                    	
                    }
                }
            } else {
                $g_id = D("GoodsProducts")->where(array('pdt_id' => $ary ['pdt_id']))->getField('g_id');
                $is_authorize = D('AuthorizeLine')->isAuthorize($ary_datas ['m_id'], $g_id);
                if (empty($is_authorize)) {
					if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
						$queue_obj->unLock();
					}
                    $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
                    exit();
                }
                //是否开启区域限售
                if(GLOBAL_STOCK == TRUE){
                    if($ary_orders['ra_id'] != 'other') {
					   $ary_receive_address = $this->cityRegion->getReceivingAddress($ary_datas ['m_id']);
					   foreach ($ary_receive_address as $ara_k=>$ara_v){
						  if($ara_v['ra_id'] == $ary_orders['ra_id']){
							 $cr_id= $ara_v['cr_id'];
						  }
					   }
                    }
                    else{
                        if(isset($ary_orders['region']) && !empty($ary_orders['region'])){$cr_id = $ary_orders['region'];}
                        elseif(isset($ary_orders['city']) && !empty($ary_orders['city'])){ $cr_id = $ary_orders['city'];}
                        elseif(isset($ary_orders['province']) && !empty($ary_orders['province'])){ $cr_id = $ary_orders['province'];}
                    }
                    
                    $return_stock = D('GoodsStock')->getProductsWarehouseStock($cr_id, $ary ['pdt_id']);
					if(0 == $return_stock){
						$pdt_sn = D("GoodsProducts")->where(array('pdt_id' => $ary ['pdt_id']))->getField('pdt_sn');
						if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
							$queue_obj->unLock();
						}
						$this->error('货品编码为'.$pdt_sn.'的商品不在限购区域内,请先重新选择所在区域！', array('返回购物车' => U('Ucenter/Cart/pageList')));
					}
					if(!$return_stock['pdt_sn']){
						$pdt_sn = D("GoodsProducts")->where(array('pdt_id' => $ary ['pdt_id']))->getField('pdt_sn');
					}else{
						$pdt_sn = $return_stock['pdt_sn'];
					}
                    if(intval($return_stock['pdt_total_stock'])<=2){
						if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
							$queue_obj->unLock();
						}
                    	$this->error('货品编码为'.$pdt_sn.'的商品库存已不足,请先在购物车里删除这件商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
                        exit();
                    }                    	
                }
            }
        }
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_datas['ml_id']); //会员等级信息
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
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
                } else {
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
            } else {
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
            elseif(!isset($default_address ['default_addr'] ['ra_id']) && $ary_orders['ra_id'] == 'other') {
                //使用临时收货地址
                 // 收货人
                $ary_orders ['o_receiver_name'] = $ary_orders['ra_name'];
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
            } else if(isset($ary_orders ['sp_id'])){
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
            }else if(isset($ary_orders ['p_id'])){
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
            }else {
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
                //赠品数组
                $gifts_cart = array();
                foreach ($pro_datas as $keys => $vals) {
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
                
                if($ary_coupon['status'] == 'success'){
                    foreach ($ary_coupon['msg'] as $coupon){
						if($coupon['c_type'] == '1'){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$ary_orders ['o_goods_all_price']);
							}else{
								//计算可以使用优惠券总金额
								$coupon_all_price = 0;
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
										$coupon_all_price += $vals['goods_total_price'];     //商品总价
									}
								}
							}
							$o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$coupon_all_price);
						}else{
							$o_coupon_menoy += $coupon['c_money'];
						} 
                    }
                    $ary_orders ['o_coupon_menoy'] = $o_coupon_menoy;
                }
            }
            if(isset($ary_orders['bonus_input'])){
                $bonus_price = sprintf("%0.2f", $ary_orders['bonus_input']);
                $ary_orders['o_bonus_money'] = $bonus_price;
            }
            if(isset($ary_orders['point_input'])){
                //需要冻结的积分数,下面生成订单成功要加到积分商品冻结的积分上
                $freeze_point = $ary_orders['point_input'];
                $ary_data = D('PointConfig')->getConfigs();
                $consumed_points = intval($ary_data['consumed_points']);
                //积分抵扣的总金额
                $point_price = sprintf("%0.2f", (0.01/$consumed_points)*$freeze_point);
                $ary_orders['o_point_money'] = $point_price;
            }
            // 订单总价 商品会员折扣价-优惠券金额-红包金额-储值卡金额-金币-积分抵扣金额
            if (!isset($ary_orders ['gp_id']) && !isset($ary_orders['p_id']) && !isset($ary_orders['sp_id'])) {
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
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
                $ary_orders['o_audit'] = 1;
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
            $bool_orders = D('Orders')->doInsert($ary_orders);
			
            // $bool_orders = true;
            if (!$bool_orders) {
                $orders->rollback();
				if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
					$queue_obj->unLock();
				}
                $this->error('订单生成失败', array('失败' => U('Ucenter/Orders/OrderFail') ));
                exit();
            } else {
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
					$ary_orders_goods = $this->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);
					foreach ($ary_orders_goods as $k => $v) {
                        $ary_orders_items = array();
                        
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
                                            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                           ->field('pdt_stock')
                                                                           ->where(array('o_id'=>$ary_orders['o_id']))
                                                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                           ->find();
                                            if(0 >= $int_pdt_stock['pdt_stock']){
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
                        } else {
            
                            // 自由推荐商品
                            if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
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
                                            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                           ->field('pdt_stock')
                                                                           ->where(array('o_id'=>$ary_orders['o_id']))
                                                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                           ->find();
                                            if(0 >= $int_pdt_stock['pdt_stock']){
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
                            }elseif($v ['type'] == '7'){
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

                            } elseif ($v ['type'] == '5') { // 团购商品
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
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
											M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
											if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
												$queue_obj->unLock();
											}
                                            $this->error('该货品已售完！');
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
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
											M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
											if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
												$queue_obj->unLock();
											}
                                            $this->error('该货品已售完！');
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
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock,pdt_min_num')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
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
                        } else {
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
                        // 更新优惠券使用
                        $ary_data = array(
                            'c_is_use' => 1,
                            'c_used_id' => $_SESSION ['Members'] ['m_id'],
                            'c_order_id' => $ary_orders ['o_id']
                        );
                        $res_coupon = D('Coupon')->doCouponUpdate($coupon ['c_sn'], $ary_data);
                        if (!$res_coupon) {
							if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
								$queue_obj->unLock();
							}
                            $this->error('优惠券更新失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                            exit();
                        }
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
	

	/**
     * 处理订单明细金额
	 * 明细金额等于订单商品总金额$ary_orders['o_goods_all_price'] 每个明细总金额$ary_good['']
	 * 红包、储值卡、金币、积分oi_bonus_money、oi_cards_money、oi_jlb_money、oi_point_money
     * wangguibin@guanyisoft.com
	 * date 2014-09-16
     */		
	public function getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas){
		//如果优惠券存在计算每个明细优惠券金额
		//优惠券总金额为oi_coupon_menoy $ary_orders['o_coupon_menoy'] 使用优惠券的商品总金额、每个明细的商品总金额
		$int_coupon_total_money = 0;
		$int_pdt_total_price = 0;
		$int_coupon_num = 0;
		//商品总数量
		$int_good_total_num = 0;
		foreach ($ary_orders_goods as $k => &$v) {
			//促销信息
			foreach ($pro_datas as $p_key=>$vals) {
				$int_promotion_count = count($vals['products']);
				//促销总金额
				$int_total_promotion_price = 0;
				foreach ($vals['products'] as $key => $val) {
					if (($val['type'] == $v['type']) && ($val['pdt_id'] == $v['pdt_id'])) {
						if (!empty($vals['pmn_name'])) {
							$v['pmn_name'] .= ' ' . $vals['pmn_name'];
						}
						//购物车优惠优惠金额放到订单明细里拆分
						if($p_key != 0 && !empty($vals['pro_goods_discount'])){
							if($int_promotion_count == $key+1){
								$v['promotion_price'] = $vals['pro_goods_discount']-$int_total_promotion_price;
							}else{
								$v['promotion_price'] = sprintf("%.2f", ($val['f_price']*$val['pdt_nums']/$vals['goods_total_price'])*$vals['pro_goods_discount']);
								$int_total_promotion_price = $int_total_promotion_price+$v['promotion_price'];
							}
						}
					}
				}
			}
			if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
			}else{
				$v['pdt_total_price'] = 0;
			}
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						$int_good_total_num +=1;
						// 购买单价
						if (isset($v [0] ['type']) && $v [0] ['type'] == 4 && $item_info['fc_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} elseif (isset($v [0] ['type']) && $v [0] ['type'] == 6 && $item_info['fr_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} 
						$int_pdt_total_price +=$item_info['pdt_total_price'];
						if($ary_coupon['status'] == 'success'){
							$is_use_coupon = 0;
							foreach ($ary_coupon['msg'] as $coupon){
								//计算参与优惠券使用的商品
								if($coupon['gids'] == 'All'){
									$is_use_coupon = 1;
								}else{
									if(in_array($item_info['g_id'],$coupon['gids'])){
										$is_use_coupon = 1;
									}
								}
							}
							if($is_use_coupon == 1){
								$int_coupon_total_money += $item_info ['f_price'] * $item_info ['pdt_nums'];
								$item_info['is_use_coupon'] = 1;
								$int_coupon_num +=1;
							}
						}						
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				} else {
					$int_good_total_num +=1;
					// 商品积分
					if (isset($v ['type']) && $v ['type'] == 1) {
					} else {
						$v['pdt_total_price'] += $v ['f_price'] * $v ['pdt_nums']-$v['promotion_price'];
					}
					$int_pdt_total_price +=$v['pdt_total_price'];
					if($ary_coupon['status'] == 'success'){
						$is_use_coupon = 0;
						foreach ($ary_coupon['msg'] as $coupon){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$is_use_coupon = 1;
							}else{
								if(in_array($v['g_id'],$coupon['gids'])){
									$is_use_coupon = 1;
								}
							}
						}
						if($is_use_coupon == 1){
							$int_coupon_total_money += $v ['f_price'] * $v ['pdt_nums']-$v['promotion_price'];
							$v['is_use_coupon'] = 1;
							$int_coupon_num +=1;
						}
					}
				}
			}
		}
		//当前已计算优惠券金额
		$int_exist_coupon_num = 0;
		$exist_coupon_money = 0;
		//红包
		$int_exist_bonus_num = 0;
		$exist_bonus_money = 0;
		//储值卡
		$int_exist_cards_num = 0;
		$exist_cards_money = 0;	
		//金币
		$int_exist_jlb_num = 0;
		$exist_jlb_money = 0;	
		//积分
		$int_exist_point_num = 0;
		$exist_point_money = 0;		
		foreach ($ary_orders_goods as $k => &$v) {
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						if($item_info['is_use_coupon'] == 1){
							if($int_exist_coupon_num+1 == $int_coupon_num){
								$item_info['oi_coupon_menoy'] = $ary_orders['o_coupon_menoy']-$exist_coupon_money;
							}else{
								$item_info['oi_coupon_menoy'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']); 
								$exist_coupon_money +=$item_info['oi_coupon_menoy'];
								$int_exist_coupon_num +=1;
							}						
						}
						//使用红包
						if(!empty($ary_orders['o_bonus_money'])){
							if($int_exist_bonus_num+1 == $int_good_total_num){
								$item_info['oi_bonus_money'] = $ary_orders['o_bonus_money']-$exist_bonus_money;
							}else{
								$item_info['oi_bonus_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
								$exist_bonus_money +=$item_info['oi_bonus_money'];
								$int_exist_bonus_num +=1;
							}								
						}
						/**
						//使用储值卡
						if(!empty($ary_orders['o_cards_money'])){
							if($int_exist_cards_num+1 == $int_good_total_num){
								$item_info['oi_cards_money'] = $ary_orders['o_cards_money']-$exist_cards_money;
							}else{
								$item_info['oi_cards_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_cards_money']); 
								$exist_cards_money +=$item_info['oi_cards_money'];
								$int_exist_cards_num +=1;
							}								
						}	
						//使用金币
						if(!empty($ary_orders['o_jlb_money'])){
							if($int_exist_jlb_num+1 == $int_good_total_num){
								$item_info['oi_jlb_money'] = $ary_orders['o_jlb_money']-$exist_jlb_money;
							}else{
								$item_info['oi_jlb_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
								$exist_jlb_money +=$item_info['oi_jlb_money'];
								$int_exist_jlb_num +=1;
							}								
						}**/
						//使用积分
						if(!empty($ary_orders['o_point_money'])){
							if($int_exist_point_num+1 == $int_good_total_num){
								$item_info['oi_point_money'] = $ary_orders['o_point_money']-$exist_point_money;
							}else{
								$item_info['oi_point_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
								$exist_point_money +=$item_info['oi_point_money'];
								$int_exist_point_num +=1;
							}								
						}							
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				} else {
					if($v['is_use_coupon'] == 1){
						if($int_exist_coupon_num+1 == $int_coupon_num){
							$v['oi_coupon_menoy'] = $ary_orders['o_coupon_menoy']-$exist_coupon_money;
						}else{
							$v['oi_coupon_menoy'] = sprintf("%.2f", ($v['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']); 
							$int_exist_coupon_num +=1;
						}
					}
					//使用红包
					if(!empty($ary_orders['o_bonus_money'])){
						if($int_exist_bonus_num+1 == $int_good_total_num){
							$v['oi_bonus_money'] = $ary_orders['o_bonus_money']-$exist_bonus_money;
						}else{
							$v['oi_bonus_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
							$exist_bonus_money +=$v['oi_bonus_money'];
							$int_exist_bonus_num +=1;
						}								
					}
					/**
					//使用储值卡
					if(!empty($ary_orders['o_cards_money'])){
						if($int_exist_cards_num+1 == $int_good_total_num){
							$v['oi_cards_money'] = $ary_orders['o_cards_money']-$exist_cards_money;
						}else{
							$v['oi_cards_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_cards_money']); 
							$exist_cards_money +=$v['oi_cards_money'];
							$int_exist_cards_num +=1;
						}								
					}	
					//使用金币
					if(!empty($ary_orders['o_jlb_money'])){
						if($int_exist_jlb_num+1 == $int_good_total_num){
							$v['oi_jlb_money'] = $ary_orders['o_jlb_money']-$exist_jlb_money;
						}else{
							$v['oi_jlb_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
							$exist_jlb_money +=$v['oi_jlb_money'];
							$int_exist_jlb_num +=1;
						}								
					}**/
					//使用积分
					if(!empty($ary_orders['o_point_money'])){
						if($int_exist_point_num+1 == $int_good_total_num){
							$v['oi_point_money'] = $ary_orders['o_point_money']-$exist_point_money;
						}else{
							$v['oi_point_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
							$exist_point_money +=$v['oi_point_money'];
							$int_exist_point_num +=1;
						}								
					}					
				}
			}		
		}
		return $ary_orders_goods;
	}
	
    /**
     * 订单显示页面
     */
    public function pageShow() {
        $this->getSubNav(1, 0, 40);
        $int_o_id = $this->_get('oid');
        $field = array(
            'fx_orders.o_id',
            'fx_orders_items.oi_id',
            'fx_orders.o_status',
            'fx_orders.o_all_price',
            'fx_orders.o_pay',
            'fx_orders.o_create_time',
            'fx_orders.o_goods_all_price',
            'fx_orders.o_goods_all_saleprice',
            'fx_orders.o_pay_status',
            'fx_orders.o_receiver_name',
            'fx_orders.o_receiver_state',
            'fx_orders.o_receiver_city',
            'fx_orders.o_receiver_county',
            'fx_orders.o_discount',
            'fx_orders.o_shipping_remarks',
            'fx_orders.o_receiver_address',
            'fx_orders.o_receiver_telphone',
            'fx_orders.o_receiver_mobile',
            'fx_orders.o_receiver_email',
            'fx_orders.o_coupon_menoy',
            'fx_orders.o_buyer_comments',
			'fx_orders.o_source_id',
			'fx_orders.o_payment',
            'fx_orders.lt_id',
            'fx_orders.ra_id',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders.o_cost_freight',
            'fx_orders.o_receiver_time',
            'fx_orders_items.pdt_id',
            'fx_orders_items.g_id',
            'fx_orders_items.oi_ship_status',
            'fx_orders_items.g_sn',
            'fx_orders_items.oi_g_name',
            'fx_orders_items.oi_score',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.oi_price',
            'fx_orders_items.pdt_sale_price',
            'fx_orders_items.pdt_sn',
            'fx_orders_items.promotion',
			'fx_orders_items.promotion_price',
            'fx_members.m_balance',
            'fx_orders.o_audit',
            'fx_orders.o_bonus_money',
            'fx_orders.o_cards_money',
            'fx_orders.o_jlb_money',
            'fx_orders.o_point_money',
            'fx_orders.o_reward_jlb'
        );
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersData($where, $field);
        $ary_orders = $ary_orders_info[0];
      
        // 订单作废状态
        $ary_status = array(
            'o_status' => $ary_orders ['o_status']
        );
        $str_status = $this->orders->getOrderItmesStauts('o_status', $ary_status);
         
        $ary_orders ['str_status'] = $str_status;

        // 订单状态
        $ary_orders_status = $this->orders->getOrdersStatus($int_o_id);
        $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'o_id' => $int_o_id
                ))->order('or_update_time asc')->select();
        if (!empty($ary_afersale) && is_array($ary_afersale)) {
            foreach ($ary_afersale as $keyaf => $valaf) {
                if ($valaf ['or_service_verify'] == '1' && $valaf ['or_finance_verify'] == '1') {
                    M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $int_o_id,
                        'or_id'=>$valaf['or_id'],
                    ))->save(array(
                        'or_processing_status' => 1
                    ));
                }
                // 退款
                if ($valaf ['or_refund_type'] == 1) {
                    switch ($valaf ['or_processing_status']) {
                        case 0 :
                            $ary_orders ['refund_status'] = '退款中';
                            break;
                        case 1 :
                            $ary_orders ['refund_status'] = '退款成功';
                            break;
                        case 2 :
                            $ary_orders ['refund_status'] = '退款驳回';
                            break;
                        default :
                            $ary_orders ['refund_status'] = ''; // 没有退款
                    }
                } elseif ($valaf ['or_refund_type'] == 2) { // 退货
                    switch ($valaf ['or_processing_status']) {
                        case 0 :
                            $ary_orders ['refund_goods_status'] = '退货中';
                            break;
                        case 1 :
                            $ary_orders ['refund_goods_status'] = '退货成功';
                            break;
                        case 2 :
                            $ary_orders ['refund_goods_status'] = '退货驳回';
                            break;
                        default :
                            $ary_orders ['refund_goods_status'] = ''; // 没有退款
                    }
                }
            }
        }
        if ($ary_orders ['refund_goods_status'] == '') {
            $ary_orders ['deliver_status'] = $ary_orders_status ['deliver_status'];
        }
        if ($ary_orders ['refund_status'] == '') {
            // 订单支付状态
            $ary_pay_status = array(
                'o_pay_status' => $ary_orders ['o_pay_status']
            );
            $str_pay_status = $this->orders->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders ['str_pay_status'] = $str_pay_status;
        }

        // 退款、退货类型
        $ary_orders ['refund_type'] = $ary_orders_status ['refund_type'];
        // 支付方式
        $ary_payment_where = array(
            'pc_id' => $ary_orders ['o_payment']
        );
        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
        $ary_orders ['payment_name'] = $ary_payment ['pc_custom_name'];
        $ary_orders ['pc_id'] = $ary_payment ['pc_id'];
        // 配送方式
        $ary_logistic_where = array(
            'lt_id' => $ary_orders ['lt_id']
        );
        $ary_field = array(
            'lc_name'
        );
        $ary_logistic_info = $this->logistic->getLogisticInfo($ary_logistic_where, $ary_field);
        $ary_orders ['str_logistic'] = $ary_logistic_info [0] ['lc_name'];
        
        // 订单详情商品
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $combo_price_total = 0;
            foreach ($ary_orders_info as $k => $v) {

                //处理使用的促销问题
                $ary_orders_info[$k]['promotions'] = array();
                if (strlen($v['promotion']) > 0) {
                    $ary_orders_info[$k]['promotions'] = explode(' ', $v['promotion']);
                }

                $ary_orders_info [$k] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v ['pdt_id']);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id']
                        ))->field('g_picture')->find();

                $ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
                // 订单商品退款、退货状态
                $ary_orders_info [$k] ['str_refund_status'] = $this->orders->getOrderItmesStauts('oi_refund_status', $v);
                // 订单商品发货
                // echo "<pre>";print_r($ary_orders_info);exit;
                $ary_orders_info [$k] ['str_ship_status'] = $this->orders->getOrderItmesStauts('oi_ship_status', $v);
                // 分销系统商品小计 和  从第三下单的商品小计
                $ary_orders_info [$k] ['subtotal'] = empty($v['o_source_id']) ? $v ['oi_nums'] * $v ['oi_price'] : $v['oi_price'];
               // echo "<pre>";print_r($v);die;
                if ($v ['oi_type'] == 3) {
                    $combo_sn = $v ['pdt_sn'];
                    $tmp_ary = array(
                        'g_sn' => $ary_orders_info [$k] ['g_sn'],
                        'pdt_spec' => $ary_orders_info [$k] ['pdt_spec'],
                        'g_picture' => $ary_orders_info [$k] ['g_picture'],
                        'oi_g_name' => $ary_orders_info [$k] ['oi_g_name'],
                        'pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );

                    $combo_pdt_gid = D('GoodsProducts')->Search(array(
                        'pdt_sn' => $v ['pdt_sn']
                            ), array(
                        'g_id'
                            ));
                    $combo_where = array(
                        'g_id' => $combo_pdt_gid ['g_id'],
                        'releted_pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );
                    $combo_field = array(
                        'com_nums',
                        'com_price'
                    );
                    $combo_res = D('ReletedCombinationGoods')->getComboReletedList($combo_where, $combo_field);
                    
                    $combo_num = $combo_res [0] ['com_nums'];
                    $combo_price_total += sprintf("%0.3f", $combo_res [0] ['com_nums'] * $combo_res [0] ['com_price']);

                    $ary_combo [$combo_sn] ['item'] [$k] = $tmp_ary;
                    $ary_combo [$combo_sn] ['num'] = $ary_orders_info [$k] ['oi_nums'] / $combo_num;
                    $ary_combo [$combo_sn] ['pdt_sale_price'] = $ary_orders_info [$k] ['pdt_sale_price'];
                    $ary_combo [$combo_sn] ['o_all_price'] = sprintf("%0.3f", $combo_price_total * $ary_combo [$combo_sn] ['num']);
                    $ary_combo [$combo_sn] ['str_ship_status'] = $ary_orders_info [$k] ['str_ship_status'];
                    $ary_combo [$combo_sn] ['str_refund_status'] = $ary_orders_info [$k] ['str_refund_status'];
                    unset($ary_orders_info [$k]);
                }
            }
        }
        
        // 物流信息
        $ary_delivery = D('Orders')->ordersLogistic($int_o_id);
        // 商品总价=商品折扣后价格+折扣价
        $ary_orders ['o_goods_all_price'] = sprintf("%0.3f", $ary_orders['o_goods_all_price']);
       // echo "<pre>";print_r($ary_orders);exit;
        // 判断是否已生成退款/退货单
        if ($ary_orders ['refund_type'] == '1') {
            $where = array();
            $where ['o_id'] = $ary_orders ['o_id'];
            $where ['or_refund_type'] = 1;
            $where ['or_processing_status'] = array(
                'neq',
                2
            );
            $num_refund = D('OrdersRefunds')->where($where)->count();
        }

        // 审核后是否允许申请退款
        $resdata = D('SysConfig')->getCfg('ALLOW_REFUND_APPLY','ALLOW_REFUND_APPLY','0','审核后是否允许申请退款');

        // 是否开启退运费 --modify By Tom <helong@guanyisoft.com> 2014-10-30
        $openDelivery = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY_ALL');
        $isOpenDelivery = 0;
        if(isset($openDelivery['ALLOW_REFUND_DELIVERY_ALL']['sv_value']) && $openDelivery['ALLOW_REFUND_DELIVERY_ALL']['sv_value'] == 1){
            $delivery_where = array(
                'o_id' => $ary_orders['o_id'],
                'or_refund_type' => 3
                );
            $isOpenDelivery = 1;
            // 判断是否已经提交退运费申请
            $delivery_data = D('OrdersRefunds')->where($delivery_where)->count();
            if($delivery_data >= 1){
                $isOpenDelivery = 0;
            }
        }

        // 判断淘宝担保交易
        $arr_payment = M('payment_cfg', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'ps_id' => $ary_orders ['o_payment']
                ))->find();
        $payment = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'ps_code' => $arr_payment ['pc_abbreviation'],
                    "ps_status_sn" => 'WAIT_SELLER_SEND_GOODS'
                ))->find();
        if (!empty($payment) && is_array($payment)) {
            $ary_orders ['is_pay'] = true;
        } else {
            $ary_orders ['is_pay'] = false;
        } // echo "<pre>";print_r($ary_orders);exit;
        // 还应支付金额
        $ary_orders ['o_order_amount'] = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        if(in_array(C('CUSTOMER_TYPE'),array(1,3)) && !empty($ary_orders['o_source_id'])){
            $this->assign('o_source_id', $ary_orders['o_source_id']);
        }
        //是否审核
        $ary_orders['str_auto_status'] = ($ary_orders['o_audit'] == 1) ? '已审核' : '未审核';
        $this->assign('refund_num', $num_refund);
        $this->assign('open_delivery',$isOpenDelivery);
        
        $this->assign($resdata);
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign('ary_combo', $ary_combo);
        $this->assign('ary_orders', $ary_orders);
        $this->assign('ary_delivery', $ary_delivery);
        $this->display();
    }

    /**
     * 下单成功显示页面
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-24
     */
    public function OrderSuccess() {
        $this->getSubNav(1, 0, 30);
        $int_o_id = $this->_get('oid');
        $where = array(
            'o_id' => $int_o_id,
            'm_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersInfo($where);	
        $ary_orders = $ary_orders_info [0];
        //echo "<pre>";print_r($ary_orders);exit;
        // 订单作废状态
        $ary_status = array(
            'o_status' => $ary_orders ['o_status']
        );
        $str_status = $this->orders->getOrderItmesStauts('o_status', $ary_status);
        $ary_orders ['str_status'] = $str_status;

        // 订单支付状态
        $ary_pay_status = array(
            'o_pay_status' => $ary_orders ['o_pay_status']
        );
        $str_pay_status = $this->orders->getOrderItmesStauts('o_pay_status', $ary_pay_status);
        $ary_orders ['str_pay_status'] = $str_pay_status;
        // 订单状态
        $ary_orders_status = $this->orders->getOrdersStatus($int_o_id);
        // 退款
        $ary_orders ['refund_status'] = $ary_orders_status ['refund_status'];
        // 退货
        $ary_orders ['refund_goods_status'] = $ary_orders_status ['refund_goods_status'];
        // 发货
        $ary_orders ['deliver_status'] = $ary_orders_status ['deliver_status'];
        // 退款、退货类型
        $ary_orders ['refund_type'] = $ary_orders_status ['refund_type'];
        // 支付方式
        $ary_payment_where = array(
            'pc_id' => $ary_orders ['o_payment']
        );
        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
        // 获取支付方式列表
        $payment = D('PaymentCfg');
        $payment_list = $payment->getPayCfg();
        //下单成功页面，过滤货到付款的支付方式
        foreach($payment_list as $key=>$val){
            if(strtoupper($val['pc_abbreviation']) == 'DELIVERY') unset($payment_list[$key]);
        }

        $ary_orders ['payment_name'] = $ary_payment ['pc_custom_name'];
        $ary_orders ['pc_abbreviation'] = $ary_payment ['pc_abbreviation'];
        $ary_orders ['pc_id'] = $ary_payment ['pc_id'];

        // 当前订单类型 8:预售商品 5:团购商品，4:自由组合商品,3组合商品，2赠品， 1积分商品，0普通商品
        $oi_type = M('orders_items')->where(array(
                    'o_id' => $ary_orders ['o_id']
                ))->getField('oi_type');
        switch ($oi_type) {
            case '5' : // 团购商品
                // 1，判断团购商品是否开启定金支付
                $groupbuy_info = M('groupbuy')->field(array('gp_id,gp_overdue_start_time,gp_overdue_end_time,gp_deposit_price,is_deposit'))
                                              ->where(array('g_id' => $ary_orders ['g_id'],'deleted' => 0))
                                              ->find();
                // 2,当前订单是否已经支付过定金
                if ($groupbuy_info ['is_deposit'] == 1) {
                    $pay_type_count = M('payment_serial')->where(array('o_id' => $ary_orders ['o_id'],'ps_status' => array('neq',0)))->count();
                    // echo $pay_type_count;exit;
                    if (empty($pay_type_count)) {
                        $pay_status = 0; // 还未支付过，适用于定金支付和全额支付
                        // 获取定金
                        $ary_orders ['gp_deposit_price'] = sprintf("%0.3f", $groupbuy_info ['gp_deposit_price'] * $ary_orders ['oi_nums']);
                    } else {
                        $pay_status = 1; // 已经支付过定金支付，还需支付尾款金额
                    }
                } else {
                    $pay_status = 3; // 未开启定金支付
                }
                $this->assign('pay_status', $pay_status);
                break;
            case '8'://预售商品
                $presale_info = M('presale',C('DB_PREFIX'),'DB_CUSTOM')->field('p_id,p_overdue_start_time,p_overdue_end_time,p_deposit_price,is_deposit')
                                ->where(array('g_id'=>$ary_orders['g_id'],'deleted'=>0))
                                ->find();
                //判断预售商品是否开启定金支付
                if($presale_info['is_deposit'] == 1){
                    //判读当前订单是否已经支付过定金
                    $pay_type_count = M('payment_serial',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_orders['o_id'],'ps_status'=>array('neq',0)))->count();
                    if(empty($pay_type_count)){
                        $pay_status = 0;//还未支付过，适用于定金支付和全额支付
                        //获取定金
                        $ary_orders['gp_deposit_price'] = sprintf('%.2f',$presale_info['p_deposit_price'] * $ary_orders['oi_nums']); 
                    }else{
                        $pay_status = 1;//已经支付过定金支付，还需支付尾款金额
                    }
                }else{
                    $pay_status = 3; //未开启定金支付
                }
                $this->assign('pay_status',$pay_status);
                break;
        }
        // 支付金额为：订单总金额-已支付金额
        //$ary_orders ['o_all_price'] = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign('ary_orders', $ary_orders);
        $this->assign('payment_list', $payment_list);
		$resdata10 = D('SysConfig')->getCfg('PAY_SEND_CODE','PAY_SEND_CODE','0','支付发验证码');
		$this->assign('is_pay_send',intval($resdata10['PAY_SEND_CODE']['sc_value']));
        $this->display();
    }

    /**
     * 下单成功显示页面
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-24
     */
    public function OrderFail() {
        $this->getSubNav(1, 1, 30);
        $this->display();
    }

    /**
     * 订单列表
     * 
     * @author listen
     *         @date 2013-01-08
     */
    public function pageList() {
        $this->getSubNav(2, 0, 40);
		//是否关联表
		$join_where = array();
        $ary_where = array();
        $ary_chose = array();

        $ary_where ['m_id'] = $_SESSION ['Members'] ['m_id'];
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_chose ['o_id']            = $this->_get('oid');
        $ary_chose ['o_status']        = $this->_get('status');
        $ary_chose ['o_receiver_name'] = $this->_get('o_receiver_name');
        $ary_chose ['from_time']       = $this->_get('from');
        $ary_chose ['end_time']        = $this->_get('end');
        $ary_chose ['o_source_id']     = $this->_get('o_source_id');
        $ary_chose ['o_source']        = $this->_get('o_source');
        $ary_chose ['o_try']           = $this->_get('o_try');
        if(!empty($ary_chose ['end_time']) && !empty($ary_chose ['from_time'])){
        	if($ary_chose ['end_time']<$ary_chose ['from_time']){
        		$this->error('下单时间开始时间不能大于结束时间');
        	}
        }
        // 订单的状态条件搜索处理
        switch ($ary_chose ['o_status']) {
            case '1' :
                $str_orders_status = '未发货';
                $ary_where ['fx_orders_items.oi_ship_status'] = 0;
                break;
            case '2' :
                $str_orders_status = '已发货';
                $ary_where ['fx_orders_items.oi_ship_status'] = 2;
                break;
            case '3' :
                $str_orders_status = '未付款';
                $ary_where ['fx_orders.o_pay_status'] = 0;
                break;
            case '4' :
                $str_orders_status = '退款/退货';
                $ary_where ['fx_orders_items.oi_refund_status'] =  array('neq',1);
                break;
            default :
                $str_orders_status = '所有订单';
        }
		if(in_array($ary_chose ['o_status'],array(1,2,4))){
			$join_where[] = 'fx_orders_items on fx_orders.o_id=fx_orders_items.o_id';
		}
		//根据合并支付id搜出订单id
		$mp_id = $this->_get('mp_id');
		if(isset($mp_id) && !empty($mp_id)){
			$ary_o_id = M('merger_payment',C('DB_PREFIX'),'DB_CUSTOM')->field("o_id")->where(array('mp_id'=>$mp_id))->select();
			if(!empty($ary_o_id)){
				foreach($ary_o_id as $v){
					$ary_chose[] = $v['o_id'];
				}
				$ary_where ['fx_orders.o_id'] = array(
					'IN',
					$ary_chose
				);
			}
		}

        if(isset($ary_chose ['o_try']) && !empty($ary_chose ['o_try'])){
            $ary_where['fx_orders.o_goods_all_price'] = array('EQ','0.000');
            $ary_where['fx_orders.o_all_price'] = array('EQ','0.000');
        }

        // 订单号
        if (isset($ary_chose ['o_id']) && $ary_chose ['o_id'] != '') {
            $ary_where ['fx_orders.o_id'] = array(
                'EQ',
                $ary_chose ['o_id']
            );
        }

        // 订单来源搜索
        if(isset($ary_chose ['o_source']) && !empty($ary_chose ['o_source'])){
            switch ($ary_chose['o_source']) {
                case 1:
                    $ary_where['fx_orders.o_source_id'] = array(
                    'NEQ',0
                    );
                    break;
                case 2:
                    $ary_where['fx_orders.o_source_id'] = array(
                    'EQ',0
                    );
                    break;
                default:
                    # code...
                    break;
            }
        }

		//第三方订单号搜索
		if(isset($ary_chose['o_source_id']) && !empty($ary_chose['o_source_id'])){
			$ary_where['fx_orders.o_source_id'] = array(
				'LIKE',
				$ary_chose['o_source_id'] . '%'
			);
		}
        // 过滤掉子订单
        $ary_where ['fx_orders.initial_o_id'] = 0;
        // 收货人名字
        if (isset($ary_chose ['o_receiver_name']) && $ary_chose ['o_receiver_name'] != '') {
            $ary_where ['fx_orders.o_receiver_name'] = array(
                'LIKE',
                '%' . $ary_chose ['o_receiver_name'] . '%'
            );
        }
        // 下单开始时间
        if (isset($ary_chose ['from_time']) && !isset($ary_chose ['end_time'])) {
            $ary_where ['fx_orders.o_create_time'] = array(
                'EGT',
                $ary_chose ['from_time']
            );
        } else if (!isset($ary_chose ['from_time']) && isset($ary_chose ['end_time'])) {
            $ary_where ['fx_orders.o_create_time'] = array(
                'ELT',
                $ary_chose ['end_time']
            );
        } else if ((isset($ary_chose ['from_time']) && $ary_chose ['from_time'] != '') && (isset($ary_chose ['end_time']) && $ary_chose ['end_time'] != '')) {
            $ary_where ['fx_orders.o_create_time'] = array(
                array(
                    'ELT',
                    $ary_chose ['end_time']
                ),
                array(
                    'EGT',
                    $ary_chose ['from_time']
                )
            );
        }
       // $orders->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')->group('fx_orders.o_id')->where($ary_where)->getField('fx_orders.o_id',true);
        $orders->join($join_where)->where($ary_where)->getField('fx_orders.o_id',true);
		$count_tmp = M()->query(M()->getLastSql());
        $count = count($count_tmp);
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_orders_info = $orders->join($join_where)->group('fx_orders.o_id')->where($ary_where)->order(array('o_create_time' => 'desc'))
				->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
				 //echo $orders->getLastSql();die;
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $k => $v) {
                // 获取销货单状态(线下支付) -- by Tom <helong@guanyisoft.com>
                if($v['o_payment'] == 3){
                    $ary_voucher = D('sales_receipts')->where(array('o_id'=>$v['o_id'],'sr_type'=>0,'sr_status'=>1))->find();
                    if(isset($ary_voucher['sr_verify_status'])){
                        $ary_orders_info[$k]['voucher_status'] = $ary_voucher['sr_verify_status'] == 0 ? '单据未审' : '单据已审';
                    }
                }
                // 订单状态
                $ary_orders_status = $this->orders->getOrdersStatus($v ['o_id']);
                $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'o_id' => $v ['o_id']
                        ))->order('or_create_time asc')->select();
                if (!empty($ary_afersale) && is_array($ary_afersale)) {
                    $ary_orders_info[$k]['refund_part_status'] = D('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                ->where(array(
                                                                    'oi_refund_status' =>array(array('eq',1),array('eq',6),'or'),
                                                                    'o_id' => $v['o_id']
                                                                    ))
                                                                ->count();
                    foreach ($ary_afersale as $keyaf => $valaf) {
                        if ($valaf ['or_service_verify'] == '1' && $valaf ['or_finance_verify'] == '1') {
                            M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'o_id' => $v ['o_id'],
                                'or_id'=>$valaf['or_id']
                            ))->save(array(
                                'or_processing_status' => 1
                            ));
                        }
                        // 退款
                        if ($valaf ['or_refund_type'] == 1) {
                            switch ($valaf ['or_processing_status']) {
                                case 0 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款中';
                                    break;
                                case 1 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款成功';
                                    break;
                                case 2 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款驳回';
                                    break;
                                default :
                                    $ary_orders_info [$k] ['refund_status'] = ''; // 没有退款
                            }
                        } elseif ($valaf ['or_refund_type'] == 2) { // 退货
                            switch ($valaf ['or_processing_status']) {
                                case 0 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货中';
                                    break;
                                case 1 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货成功';
                                    break;
                                case 2 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货驳回';
                                    break;
                                default :
                                    $ary_orders_info [$k] ['refund_goods_status'] = ''; // 没有退款
                            }
                        }
                    }
                }
                // 付款状态
                if ($ary_orders_info [$k] ['refund_status'] == '') {
                    $ary_orders_info [$k] ['str_pay_status'] = $this->orders->getOrderItmesStauts('o_pay_status', $v ['o_pay_status']);
                }

                $ary_orders_info [$k] ['str_status'] = $this->orders->getOrderItmesStauts('o_status', $v ['o_status']);
                if ($ary_orders_info [$k] ['refund_goods_status'] == '') {
                    $ary_orders_info [$k] ['deliver_status'] = $ary_orders_status ['deliver_status'];
                }
                // 发货
				 // 物流信息
				$ary_orders_info[$k]['od_logi_no'] = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->getField("od_logi_no");
            }
        } // echo "<pre>";print_r($ary_orders_info);exit;
		$customer_type = C("CUSTOMER_TYPE");
		$this->assign('customer_type', $customer_type);
        // 订单状态
		$this->assign('mp_id',$mp_id);
        $this->assign('chose', $ary_chose);
		//echo "<pre>";print_r($ary_orders_info);exit;
        $this->assign('orders_info', $ary_orders_info);
        $this->assign('page', $page); // 赋值分页输出
		$cfg = D('SysConfig')->getCfgByModule('goods_comment_set',1);
    	$cfg['comment_show_condition'] = explode(',',$cfg['comment_show_condition']);
    	$this->assign('cfg',$cfg);
		$is_merge_payment = D('SysConfig')->getCfg('IS_MERGE_PAYMENT','IS_MERGE_PAYMENT','0','是否启用合并支付');
		$this->assign('is_merge_payment',$is_merge_payment['IS_MERGE_PAYMENT']['sc_value']);
        $this->display();
    }

    // 作废订单
    public function ajaxInvalidOrder() {
        $ary_post = $this->_post();
        $int_oid = $this->_post('oid');
        $cacel_type = $this->_post('cacel_type');
        if (isset($int_oid) && isset($cacel_type)) {
            M()->startTrans();
            //断订单是满足作废条件,只有未支付的订单才能作废
            $ary_where = array('o_id' => $int_oid, 'o_pay_status' => 0, 'o_status' => 1);
            $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_orders = $orders->where($ary_where)->find();
            if (empty($ary_orders)) {
                $this->error('此订单不能不存在', array('确定' => U('Ucenter/Orders/pageShow/', array('oid' => $int_oid))));
                exit;
            } else {
                $return_orders = $orders->where(array(
                            'o_id' => $int_oid
                        ))->save(array(
                    'o_status' => 2
                        ));	
                // 订单日志记录
                if ($return_orders) {
                    $ary_orders_log = array(
                        'o_id' => $int_oid,
                        'ol_behavior' => '作废',
                        'ol_uname' => $_SESSION ['Members'] ['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                }else{
                    M()->rollback();
                    $this->error('更新订单状态失败', array(
                            '确定' => U('Ucenter/Orders/pageShow/', array(
                                'oid' => $int_oid
                            ))
                        ));
                }
                if (false != $return_orders) {
                    //销量返回
                    $orderItems = M('orders_items')->field('oi_id,o_id,g_id,pdt_id,oi_nums,fc_id,oi_type')->where(array('o_id'=>$int_oid))->select();
                    foreach($orderItems as $item){
                        if($item['oi_type']==5 && !empty($item['fc_id'])){
                            $return_groupbuy_nums = D("Groupbuy")->where(array('gp_id' => $item['fc_id']))->setDec("gp_now_number",$item['oi_nums']);
                            if(!$return_groupbuy_nums){
                                M()->rollback();
                                $this->error('作废订单团购量更新失败', array(
                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                            }
                        }elseif($item['oi_type']==7 && !empty($item['fc_id'])){
                            $retun_spike_nums=D("Spike")->where(array('sp_id' => $item['fc_id']))->setDec("sp_now_number",$item['oi_nums']);
                            if(!$retun_spike_nums){
                                M()->rollback();
                                $this->error('作废订单秒杀量更新失败', array(
                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                            }
                        }
                        $goods_num_res = M("goods_info")->where(array(
                                    'g_id' => $item ['g_id']
                                ))->data(array(
                                    'g_salenum' => array(
                                        'exp',
                                        'g_salenum - '.$item['oi_nums']
                                    )
                                ))->save();
                        if(!$goods_num_res){
                            M()->rollback();
                            $this->error('作废订单销量更新失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                        }
                    }
                    // 冻结积分释放掉
                    $point_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $int_oid))->find();
                    if (isset($point_orders['o_freeze_point']) && $point_orders['o_freeze_point'] > 0 && $point_orders['m_id'] > 0) {
                         $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $point_orders['m_id']))->find();
                         if ($ary_member && $ary_member['freeze_point'] > 0) {
                            //订单作废返还冻结积分日志
                            $ary_log = array(
                                        'type'=>8,
                                        'consume_point'=> 0,
                                        'reward_point'=> $point_orders['o_freeze_point']
                                        );
                            $ary_info =D('PointLog')->addPointLog($ary_log,$point_orders['m_id']);
                            if($ary_info['status'] == 1){
                                 $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $point_orders['o_freeze_point'];
                                 $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $point_orders['m_id']))->save($ary_member_data);
                                 if(!$res_member){
                                     $orders->rollback();
                                     $this->error('作废返回冻结积分失败', array(
                                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                                        'oid' => $int_oid
                                                    ))
                                                ));
                                     exit;
                                 }
                            }else{
                                 $orders->rollback();
                                 $this->error('作废返回冻结积分写日志失败', array(
                                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                                    'oid' => $int_oid
                                                ))
                                            ));
                                 exit;
                            }
                        }else{
                             $orders->rollback();
                             $this->error('作废返回冻结积分没有找到要返回的用户冻结金额', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                             exit;
                        }
                    }
                    //还原,支出冻结红包金额
                    $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'bn_type'=>array('neq','0')))->find();
                    if(!empty($ary_bonus) && is_array($ary_bonus)){
                        $arr_bonus = array(
                            'bt_id' => '4',
                            'm_id'  => $ary_bonus['m_id'],
                            'bn_create_time'  => date("Y-m-d H:i:s"),
                            'bn_type' => '0',
                            'bn_money' => $ary_bonus['bn_money'],
                            'bn_desc' => '作废订单成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                            'o_id' => $ary_orders['o_id'],
                            'bn_finance_verify' => '1',
                            'bn_service_verify' => '1',
                            'bn_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                        if (!$res_bonus) {
                            M()->rollback();
                            $this->error('红包金额还原失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
					/**
                    //还原,支出冻结储值卡金额
                    $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'ci_type'=>array('neq','0')))->find();
                    if(!empty($ary_cards) && is_array($ary_cards)){
                        $arr_cards = array(
                            'ct_id' => '2',
                            'm_id'  => $ary_cards['m_id'],
                            'ci_create_time'  => date("Y-m-d H:i:s"),
                            'ci_type' => '0',
                            'ci_money' => $ary_cards['ci_money'],
                            'ci_desc' => '作废订单成功返还储值卡金额：'.$ary_cards['ci_money'].'元',
                            'o_id' => $ary_orders['o_id'],
                            'ci_finance_verify' => '1',
                            'ci_service_verify' => '1',
                            'ci_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_cards = D('CardsInfo')->addCards($arr_cards);
                        if (!$res_cards) {
                            M()->rollback();
                            $this->error('储值卡金额还原失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
                    //还原,支出冻结金币金额
                    $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'ji_type'=>array('neq','0')))->find();
                    if(!empty($ary_jlb) && is_array($ary_jlb)){
                        $arr_jlb = array(
                            'jt_id' => '2',
                            'm_id'  => $ary_jlb['m_id'],
                            'ji_create_time'  => date("Y-m-d H:i:s"),
                            'ji_type' => '0',
                            'ji_money' => $ary_jlb['ji_money'],
                            'ji_desc' => '作废订单成功返还金币金额：'.$ary_jlb['ji_money'].'元',
                            'o_id' => $ary_orders['o_id'],
                            'ji_finance_verify' => '1',
                            'ji_service_verify' => '1',
                            'ji_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                        if (!$res_jlb) {
                            M()->rollback();
                            $this->error('金币金额还原失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
					**/
                    //优惠券还原
                    $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'c_order_id' => $int_oid
                            ))->find();
                    if (!empty($ary_coupon) && is_array($ary_coupon)) {
                        $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'c_order_id' => $int_oid
                                ))->save(array(
                            'c_used_id' => 0,
                            'c_order_id' => 0,
                            'c_is_use' => 0
                                ));
                        if (!$res_coupon) {
                            M()->rollback();
                            $this->error('优惠券还原失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
                    if(!empty($ary_orders['o_source_id'])){
                        $bool_result = M('thd_orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('to_oid'=>$ary_orders['o_source_id']))->data(array('to_tt_status'=>'0'))->save();
                        if(false === $bool_result){
                            M()->rollback();
                             $this->error('更新第三方来源单号失败', array(
                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                                exit();
                        }
                    }
                    M()->commit();
                    $this->success('作废成功', array(
                        '确定' => U('Ucenter/Orders/pageList/')
                    ));
                    exit();
                } else {
                    M()->rollback();
                    $this->error('此订单不能作废', array(
                        '确定' => U('Ucenter/Orders/pageShow/', array(
                            'oid' => $int_oid
                        ))
                    ));
                    exit();
                }
            }
        }
    }

    /**
     * 订单支付
     * 
     * @param int $order_id
     *        	订单ID
     * @param ary $oreder
     *        	更新订单表数组
     * @return boolean
     */
    public function paymentPage() {
        $int_id = $this->_post('oid');
        $payment_id = $this->_post('new_payment_id');
        $pay_stat = $this->_post('typeStat');
        
        $where = array();
        $where ['fx_orders.o_id'] = $int_id;
        if ($pay_stat == 2) { // 如果当前是尾款支付
            $where ['fx_orders.o_pay_status'] = 3;
        } else {
            $where ['fx_orders.o_pay_status'] = 0;
        }
        $where ['fx_orders.o_status'] = array(array('eq','1'),array('eq','3'),'or');
        $search_field = array(
            'fx_orders.o_all_price',
            'fx_orders.o_payment',
            'fx_orders.o_pay',
            'fx_members.m_id',
            'fx_orders.o_pay_status',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders_items.pdt_id',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.fc_id',
            'fx_orders_items.g_id'
        );

        if (isset($int_id)) {
            $ary_orders_data = D('Orders')->getOrdersData($where, $search_field, $group);
            if (empty($ary_orders_data) && count($ary_orders_data) <= 0) {
                $this->error('订单不存在或已支付'); // XXXXXXXXXXXXXXXXXXXXX
            }
            $ary_orders = $ary_orders_data [0];
        }
		//查询库存,如果库存数为负数则不再扣除库存
		$int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
									   ->field('pdt_stock')
									   ->where(array('o_id'=>$int_id))
									   ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
									   ->find();
		if($ary_orders['oi_type'] ==5 || $ary_orders['oi_type'] ==8){
			if(0 >= $int_pdt_stock['pdt_stock']){
				$this->error('该货品已售完！');
			}
			//没有结果
		}else{
			if(0 >= $int_pdt_stock['pdt_stock']){
				$this->error('该货品已售完！');
			}
		}
        M('', '', 'DB_CUSTOM')->startTrans();
        
        // 支付流程改造【团购】
        if ($ary_orders ['oi_type'] == '5') { // 团购订单
       
            $groupbuy = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'gp_id' => $ary_orders ['fc_id']
                    ))->find();
            if ($pay_stat == 0) {
                // 团购全额支付
                //验证团购商品是否可以支付
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id,1);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = $ary_orders ['o_all_price'];
            } elseif ($pay_stat == 1) {
                // 团购定金支付,获取定金
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id,1);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = sprintf("%0.3f", $groupbuy ['gp_deposit_price'] * $ary_orders ['oi_nums']);
            } elseif ($pay_stat == 2) {
                // 尾款支付。检测当前时间是否在指定支付尾款时间内
                $gp_overdue_start_time = strtotime($groupbuy ['gp_overdue_start_time']);
                $gp_overdue_end_time = strtotime($groupbuy ['gp_overdue_end_time']);
                if ($gp_overdue_start_time > mktime()) {
                    // 还未到支付尾款时间
                    $this->error('请于' . date('Y年m月d日 H:i:s', $gp_overdue_start_time) . '后补交尾款');
                } elseif (($gp_overdue_start_time < mktime()) && ($gp_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    $this->error('补交尾款时间已过，请联系客服人员');
                }
                $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
            }
        }elseif ($ary_orders['oi_type'] == '8') {   //预售商品
            $presale = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'g_id' => $ary_orders ['g_id']
                    ))->find();
            if ($pay_stat == 0) {
                // 预售全额支付
                $o_pay = $ary_orders ['o_all_price'];
            } elseif ($pay_stat == 1) {
                // 预售定金支付,获取定金
                $o_pay = sprintf("%0.3f", $presale ['p_deposit_price'] * $ary_orders ['oi_nums']);
            } elseif ($pay_stat == 2) {
                // 尾款支付。检测当前时间是否在指定支付尾款时间内
                $p_overdue_start_time = strtotime($presale ['p_overdue_start_time']);
                $p_overdue_end_time = strtotime($presale ['p_overdue_end_time']);
                if ($p_overdue_start_time > mktime()) {
                    // 还未到支付尾款时间
                    $this->error('请于' . date('Y年m月d日 H:i:s', $p_overdue_start_time) . '后补交尾款');
                } elseif (($p_overdue_start_time < mktime()) && ($p_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    $this->error('补交尾款时间已过，请联系客服人员');
                }
                $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
            }
        }else if($ary_orders ['oi_type'] == '7'){
            $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
            if (!$result_spike) {
                // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }else {
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }

        // # 使用支付模型 支付订单 ###############################################
        $Payment = D('PaymentCfg');
        if ($ary_orders ['o_payment'] != $payment_id && !empty($payment_id)) {
            $ary_orders ['o_payment'] = $payment_id;
            $update_payment_res = $this->orders->UpdateOrdersPayment($int_id, $payment_id);
            if ($update_payment_res == false) {
                M('', '', 'DB_CUSTOM')->rollback();
                exit();
            }
        }
        
        $info = $Payment->where(array(
                    'pc_id' => $ary_orders ['o_payment']
                ))->find();
        
        if (false == $info) {
            // 支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
            M('', '', 'DB_CUSTOM')->rollback();
            $this->error('支付方式不存在，或不可用');
            exit();
        }
        // 线下支付进erp
        if ($info ['pc_abbreviation'] != 'OFFLINE' && $info ['pc_abbreviation'] != 'DELIVERY' && $ary_orders ['pc_abbreviation'] != 'DELIVERY') {
            $Pay = $Payment::factory($info ['pc_abbreviation'], json_decode($info ['pc_config'], true));
            $result = $Pay->pay($int_id, $ary_orders ['oi_type'], $o_pay, $pay_stat);
            writeLog($result, "order_pay.log");
            if (!$result ['result']) {
                // 支付失败了 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result ['message']);
            }
            // 支付成功了
            // die('@tudo 支付成功继续完成：1）流水账 2）更新订单状态 3）进入ERP');
            $ary_orders ['o_pay'] = $o_pay;
            if ($pay_stat == 1) {
                // 如果是定金支付，支付状态为部分支付
                $ary_orders ['o_pay_status'] = 3;
            } else {
                $ary_orders ['o_pay_status'] = 1;
            } // print_r($ary_orders);exit;
            //查询库存,如果库存数为负数则不再扣除库存
            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                           ->field('pdt_stock')
                                           ->where(array('o_id'=>$int_id))
                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                           ->find();
            if($ary_orders['oi_type'] ==5 || $ary_orders['oi_type'] ==8){
			    if(0 >= $int_pdt_stock['pdt_stock']){
                    $this->error('该货品已售完！');
                }
                //没有结果
            }else{
                if(0 >= $int_pdt_stock['pdt_stock']){
                    $this->error('该货品已售完！');
                }
            }
            
            $result_order = $this->orders->orderPayment($int_id, $ary_orders);
            
            if (!$result_order ['result']) {
                // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }
            /*秒杀数量更新 此处兼容性受限  为了兼容线上支付  改动代码位置  */
            /*else{
                
                if($ary_orders ['oi_type'] == '7'){
                    $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
                    if (!$result_spike) {
                        // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error($result_order ['message']);
                            exit();

                    }
                }
            }*/

            $ary_member = session("Members");
            $ary_balance_info = array(
                'bt_id' => '1',
                'bi_sn' => time(),
                'm_id' => $ary_member ['m_id'],
                'bi_money' => $o_pay,
                'bi_type' => '1',
                'bi_payment_time' => date("Y-m-d H:i:s"),
                'o_id' => $int_id,
                'bi_desc' => '订单支付',
                'single_type' => '2',
                'bi_verify_status' => '1',
                'bi_service_verify' => '1',
                'bi_finance_verify' => '1',
                'bi_create_time' => date("Y-m-d H:i:s")
            );
            $arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
            // echo "<pre>";print_r($ary_balance_info);exit;
            if (!$arr_res) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error('生成支付明细失败，请重试...');
                exit();
            } else {
                $arr_data = array();
                $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
                $arr_data ['bi_sn'] = time() . $str_sn;
                $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'bi_id' => $arr_res
                        ))->data($arr_data)->save();
                if (!$arr_result) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('更新支付明细失败，请重试...');
                    exit();
                }
                // 结余款调整单日志
                $add_balance_log ['u_id'] = 0;
                $add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
                $add_balance_log ['bvl_desc'] = '审核成功';
                $add_balance_log ['bvl_type'] = '2';
                $add_balance_log ['bvl_status'] = '2';
                $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
                if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成结余款调整单日志失败，请重试...');
                    exit();
                }
                $add_balance_log ['bvl_type'] = '3';
                if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成结余款调整单日志失败，请重试...');
                    exit();
                }
            }
            if ($info ['pc_abbreviation'] == 'DEPOSIT') {
                $add_payment_serial ['m_id'] = $_SESSION ['Members'] ['m_id'];
                $add_payment_serial ['pc_code'] = 'DEPOSIT';
                $add_payment_serial ['ps_money'] = $o_pay;
                $add_payment_serial ['ps_type'] = 0;
                $add_payment_serial ['o_id'] = $int_id;
                $add_payment_serial ['ps_status'] = 1;
                $add_payment_serial ['pay_type'] = $pay_stat;
                $add_payment_serial ['ps_create_time'] = date('Y-m-d H:i:s');
                $ary_result = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_payment_serial);
                if (false === $ary_result) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成支付明细失败，请重试...');
                    exit();
                }
            }
        } else {
            //查询库存,如果库存数为负数则不再扣除库存
            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                           ->field('pdt_stock')
                                           ->where(array('o_id'=>$int_id))
                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                           ->find();
            if(0 >= $int_pdt_stock['pdt_stock']){
                $this->error('该货品已售完！');
            }
            $ary_orders ['o_pay_status'] = 0;
            $result_order = $this->orders->orderPayment($int_id, $ary_orders);
            
            if (!$result_order ['result']) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }
            /*秒杀数量更新 此处兼容性受限  为了兼容线上支付  改动代码位置  */
            /*else{
                
                if($ary_orders ['oi_type'] == '7'){
                    $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
                    if (!$result_spike) {
                        // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error($result_order ['message']);
                            exit();

                    }
                }
            }*/
        }
        M('', '', 'DB_CUSTOM')->commit();
        $url = U("Ucenter/Orders/PaymentSuccess", array(
            'oid' => $int_id
                ));
        redirect($url);
        exit();
    }

    /**
     * 支付成功显示页面
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-24
     */
    public function PaymentSuccess() {
        $this->getSubNav(1, 0, 30);
        $ary_member = session("Members");
        $int_o_id = $this->_get('oid');
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_orders.m_id' => $ary_member ['m_id']
        );
        $ary_field = array(
			'fx_orders.o_id',
            'fx_orders.o_pay',
            'fx_orders.o_all_price',
            'fx_orders.coupon_sn',
			'fx_orders.o_receiver_state',
			'fx_orders.o_receiver_city',
			'fx_orders.o_receiver_county',
			'fx_orders.o_payment',
            'fx_orders_items.pdt_id',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.fc_id',
            'fx_orders_items.g_id'			
        );
        $ary_orders = D('Orders')->getOrdersData($where, $ary_field);
        // 本次消费金额=支付单最后一次消费记录
        $payment_serial = M('payment_serial')->where(array('o_id' => $int_o_id))->order('ps_create_time desc')->select();
        // echo "<pre>"; print_r($payment_serial);exit;
        $payment_price = $payment_serial [0] ['ps_money'];
        $all_price = $ary_orders[0]['o_all_price'];
        $coupon_sn = $ary_orders[0]['coupon_sn'];
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        //送优惠券
        if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '0') {
            D('Coupon')->setPoinGetCoupon($ary_orders, $ary_member['m_id']);
        }
        // 账户余额
        $ary_member_balance = D('Members')->GetBalance(array(
            'm_id' => $_SESSION ['Members'] ['m_id']
                ), array(
            'm_balance'
                ));
        $this->assign('o_id', $int_o_id);
        $this->assign('balance', $ary_member_balance ['m_balance']);
        $this->assign('payment_price', $payment_price);
        $this->display();
    }

    /**
     * 查看我的优惠券
     * 
     * @author listen
     * @date 2013-02-27
	 * @ by wanghaoyu 2014-5-15
     */
    public function myCouponPage() {
		$ary_pdt_id = $this->_post();
        $mid = $_SESSION ['Members'] ['m_id'];
		//获取购物车数据
        $ary_cart = D('Cart')->GetData();
        if (isset($ary_cart ['gifts'])) {
            unset($ary_cart ['gifts']);
        }
        $cart_data = $this->cart->getProductInfo($ary_cart);
		foreach($cart_data as $v){
			$ary_cart_pdt_id[] = $v['pdt_id'];
		}
		$int_pdt_id = count($ary_pdt_id);
		$int_cart_pdt_id = count($ary_cart_pdt_id);
		$aryPdtIds = $int_pdt_id == $int_cart_pdt_id ? $ary_cart_pdt_id : $ary_pdt_id;
		//根据pdt_id(货品id)获取g_id(商品id)
		$ary_g_ids = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field("g_id")->where(array("pdt_id"=>array("IN",$aryPdtIds)))->select();
		foreach($ary_g_ids as $v){
			$gIds[] = $v['g_id'];
		}
		//根据g_id(商品id)获取gg_id(商品组id)
		$ary_ggids = D("RelatedGoodsGroup")->getGoodsGroupByGid($gIds);
		if(!empty($ary_ggids)){
			foreach($ary_ggids as $v){
				$ary_where['gg_id'] = array("IN",$v['gg_id']);
			}
			$ary_where = array(
				'c_user_id' => $mid,
				'c_is_use' => 0
			);
			$ary_where ['c_end_time'] = array(
				'EGT',
				date('Y-m-d H:i:s')
			);
			//根据gg_id(商品组id)获取c_id(优惠券id)
			$ary_coupon = M('coupon c', C('DB_PREFIX'), 'DB_CUSTOM')
						->join(C("DB_PREFIX"). 'related_coupon_goods_group as rcgp on rcgp.c_id = c.c_id')
						->where($ary_where)->group('c.c_id')->select();
		}
        // 判断商品是否允许使用优惠券
        // dump($ary_coupon);
        /*foreach ($ary_coupon as $key => $coupon) {
            $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
            ->where(array('c_id'=>$coupon['c_id']))
            ->field('group_concat(gg_id) as group_id')->group('gg_id')->select();
            if(!empty($group_data)){
                $is_true = 0;
                //查询团购管理商品
                foreach ($group_data as $gd){
                    $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                    ->where(array('gg_id'=>array('in',$gd['group_id'])))->field('g_id')->group('g_id')->select();
                    foreach($item_data as $item){
                        foreach($cart_data as $cart){
                            if($cart['g_id'] == $item['g_id']){
                                $is_true = 1;
                                break;
                            }
                        }
                    }
                }
                if ($is_true == '0') {
                    unset($ary_coupon [$key]);
                }
                
            }
            
        }*/
        $this->assign('ary_coupon', $ary_coupon);
        $this->display();
    }

    /**
     * 添加发票收藏设置
     * 
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-26
     */
    public function AddInvoice() {
        $data ['invoice_type'] = $this->_get('type');
        $data ['invoice_head'] = $this->_get('head');
        $data ['invoice_name'] = $this->_get('name');
        $content = $this->_get('content');
        $people = $this->_get('invoice_people');
        $data ['invoice_content'] = empty($content) ? $people : $content;
        $data ['invoice_people'] = $this->_get('invoice_people');
        $data ['m_id'] = $_SESSION ['Members'] ['m_id'];
        $data ['create_time'] = date("Y-m-d H:i:s");
        $data ['modify_time'] = date("Y-m-d H:i:s");
        $data ['is_default'] = 1;
        M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
//                echo "<pre>";echo M ( 'InvoiceCollect', C ( 'DB_PREFIX' ), 'DB_CUSTOM' )->getLastSql();exit;
    }

    /**
     */
    public function AddAppInvoice() {
        $data = $this->_post();
        $data ['m_id'] = $_SESSION ['Members'] ['m_id'];
        $data ['create_time'] = date("Y-m-d H:i:s");
        $data ['modify_time'] = date("Y-m-d H:i:s");
        $data ['is_default'] = 1;
        
        //发表的抬头信息是否完整
        if($data['invoice_type'] != 2){
            if($data['invoice_people'] == ''){
                $this->error('个人姓名不能为空！');
            }
            if($data['invoice_name'] == ''){
                $this->error('单位名称不能为空！');
            }
        }    
        // 是否开启增值税发票自动审核，开启的话增值税发票自动审核
        $p_invoice = D('Invoice')->get();
        $is_auto_verify = $p_invoice ['is_auto_verify'];
        if ($is_auto_verify == 1)
            $data ['is_verify'] = 1;

        $ary_res = M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
        if (FALSE !== $ary_res) {
            $this->ajaxReturn(array(
                'status' => 1,
                'info' => '添加增值税发票成功',
                "data" => $ary_res
            ));
        } else {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '添加增值税发票失败'
            ));
        }
    }

    /**
     * 改变收藏设置
     * 
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-26
     */
    public function ChangeInvoice() {
        $data ['id'] = $this->_get('id');
        $data ['m_id'] = $_SESSION ['Members'] ['m_id'];
        D('InvoiceCollect')->change($data);
    }

    /**
     * 设置ajax打开页面
     * 
     * @author wangguibin<wangguibin@guanyisoft.com>
     *  @date 2013-06-09
     */
    public function setPage() {
        $int_o_id = $this->_post('o_id');
        $this->assign('o_id', $int_o_id);
        $this->display();
    }

	/**
     * 设置ajax打开页面
     * 
     * @author wangguibin<wangguibin@guanyisoft.com>
     *  @date 2013-08-11
     */
    public function setSendPage() {
        $int_o_id = $this->_post('o_id');
        $this->assign('o_id', $int_o_id);
        $this->display();
    }
	
    /**
     * 添加销货收款单
     * 
     * @author wangguibin<wangguibin@guanyisoft.com>
     *  @date 2013-06-09
     */
    public function addVoucher() {
        $data = $this->_post();
        $data ['o_id'] = trim($data ['o_id']);
        $data ['sr_bank_sn'] = trim($data ['sr_bank_sn']);
        if (!is_float($data ['to_post_balance']) && !is_numeric($data ['to_post_balance']) && ($data ['to_post_balance'] <= 0)) {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '金额格式必须正确且大于0！'
            ));
            exit();
        }
        if (!($data ['o_id']) || !($data ['to_post_balance']) || !($data ['sr_bank_sn'])) {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '金额和流水号必填！'
            ));
            exit();
        }
        $ary_data = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('fx_orders.o_all_price,fx_orders.m_id,fx_orders.o_pay_status,fx_orders.o_id,p.pc_pay_type,p.pc_id,fx_orders.o_status')->join("fx_payment_cfg as p on(p.pc_id=fx_orders.o_payment) ")->where(array(
                    'o_id' => $data ['o_id']
                ))->find();
        if (floatval($ary_data ['o_all_price']) > floatval($data ['to_post_balance'])) {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '输入的金额小于订单应付金额，不能生成销货单！'
            ));
            exit();
        }
        // 流水帐号在有效的单据中不可重复
        $ary_count = M('sales_receipts', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'sr_bank_sn' => $data ['sr_bank_sn'],
                    'sr_status' => '1'
                ))->count();
        if ($ary_count > 0) {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '流水帐号已存在,不可重复！'
            ));
            exit();
        }
        $r = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'o_id' => $data ['o_id']
                ))->save(array(
            'o_payment' => '3',
            'o_update_time' => date('Y-m-d H:i:s')
                ));
        if ($r) {
            $ary_data ['pc_pay_type'] = 'offline';
        }
        // 线下支付订单新增销货收款单
        if (($ary_data ['o_pay_status'] == 0) && ($ary_data ['o_status'] == '1') && ($ary_data ['pc_pay_type'] == 'offline')) {
            $ary_data1 = M('sales_receipts', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $data ['o_id'],
                        'sr_status' => '1'
                    ))->count();
            if ($ary_data1 == 0) {
                $data ['sr_create_type'] = '0';
                $data ['sr_create_time'] = date("Y-m-d H:i:s");
                $data ['sr_update_time'] = date("Y-m-d H:i:s");
                $data ['sr_verify_status'] = '0';
                $data ['sr_type'] = '0';
                $data ['m_id'] = $ary_data ['m_id'];
                $res = M('sales_receipts', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
                if ($res) {
                    $this->writeVoucherLog(array(
                        'sr_id' => $res,
                        'srml_type' => '0',
                        'srml_uid' => '0',
                        'srml_change' => json_encode($data),
                        'srml_create_time' => date("Y-m-d H:i:s")
                    ));
                    $this->ajaxReturn(array(
                        'status' => 1,
                        'info' => '添加收款单成功,请等待管理员审核！'
                    ));
                    exit();
                } else {
                    $this->ajaxReturn(array(
                        'status' => 0,
                        'info' => '添加收款单报错！'
                    ));
                    exit();
                }
            } else {
                $this->ajaxReturn(array(
                    'status' => 0,
                    'info' => '此订单已存在销货收款单，无需再次添加！'
                ));
                exit();
            }
        } else {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '添加收款单报错！'
            ));
            exit();
        }
    }

    /**
     * 记录审核日志
     * 
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-09
     */
    public function writeVoucherLog($params) {
        M('sales_receipts_modify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($params);
    }

    /**
     * 选择物流公司
     * 
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2013-06-30
     */
    public function ChangeLogistic() {
        $promotion_price = 0;
        $combo_all_price = 0;
        $free_all_price = 0;
        $data = $this->_post();
        $ary_tmp_cart = D('Cart')->GetData();
        if($data['pids']){
			$goods_pids = $data['pids'];
			switch($goods_pids){
				case 'spike':
				$ary_cart = array();
				$ary_cart = $_SESSION ['spike_cart'];				
				break;
				case 'bulk':
					$bulk_cart = $_SESSION['bulk_cart'];
					$ary_member = session("Members");
					$array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
					$ary_return['all_price'] = $array_bulk['data']['cust_price'] * $bulk_cart['num'];
					$ary_return['all_goods_price'] = $array_bulk['data']['all_price'];
					$ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);
					$logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart,$ary_member['m_id']);
					$logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
					$lt_expressions = json_decode($logistic_info['lt_expressions'],true);
					if(!empty($lt_expressions['logistics_configure']) && $ary_return ['all_goods_price'] >= $lt_expressions['logistics_configure']){
						$logistic_price = 0;
					}
					$ary_return['status'] = 1;
					$ary_return['logistic_price'] = $logistic_price;
					$ary_return['promotion_price'] = $ary_return['all_price'] - $ary_return['all_goods_price'];
					$ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
					$this->ajaxReturn($ary_return);exit;		
				break;
				case 'presale':
					$param_spike = $_SESSION ['spike_cart'];
					$spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
					
					$array_where = array(
						'sp_status' => 1,
						'sp_id' => $param_spike ['sp_id']
					);
					$data = $spike->where($array_where)->find();
					if (empty($data)) {
						$this->error('秒杀商品不存在！');
					}
					$data ['cust_price'] = sprintf("%0.2f",M('goods_info')->where(array('g_id' => $data ['g_id']))->getField('g_price')*$param_spike['num']);
					$buy_nums = $data ['sp_now_number'];
					$data ['sp_now_number'] = $buy_nums;
					$ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 7);
					$logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
					$lt_expressions = json_decode($logistic_info['lt_expressions'],true);
					if(!empty($lt_expressions['logistics_configure']) && $data ['sp_price'] >= $lt_expressions['logistics_configure']){
						$logistic_price = 0;
					}else{
						$ary_cart[$param_presale['pdt_id']] = array('pdt_id' => $param_presale['pdt_id'], 'num' => $param_presale['num'], 'type' => 8);
						$logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart);
					}
					$ary_return['status'] = 1;
					$ary_return['all_price'] = $data['cust_price'];
					$ary_return['logistic_price'] = $logistic_price;
					$ary_return['promotion_price'] = $data['cust_price'] - $data['sp_price'];
					$ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
					$ary_return['sp_price'] = $data['sp_price']; 
					$this->ajaxReturn($ary_return);exit;			
				break;
				default:
					$cart_data = D('Cart')->GetData();
					$ary_pid = explode(',',$goods_pids);
					$ary_cart = array();
					foreach ($cart_data as $key=>$cd){
						foreach ($ary_pid as $pid){
							if($pid == $key){
								$ary_cart[$pid] = $cart_data[$key];
							}
						}
					}				
			}
        }else{
            $ary_cart = $ary_tmp_cart;
        }
        $ary_member = session("Members");
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as $c_key=>$val) {
                if ($val ['type'] == 0) {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array(
                                'pdt_id' => $val ['pdt_id']
                            ))->find();
                    $ary_cart[$c_key]['g_id'] = $ary_gid ['g_id'];
                }
            }
        }
        $ary_tmp_cart = $ary_cart;
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
		$subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);
        
        $promotion_total_price = '0';
        $promotion_price = '0';
        //赠品数组
        $cart_gifts = array();
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals['products'] as $key => $val) {
                $arr_products = $this->cart->getProductInfo(array($key => $val));
                if ($arr_products[0][0]['type'] == '4') {
                    foreach ($arr_products[0] as &$provals) {
                        $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                    }
                }
                $pro_datas[$keys]['products'][$key] = $arr_products[0];
                $pro_data[$key] = $val;
                $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
            }
            //赠品数组
            if(!empty($vals['gifts'])){
                foreach($vals['gifts'] as $gifts){
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gifts['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
                    $cart_gifts[$pdt_id['pdt_id']]=array('pdt_id'=>$pdt_id['pdt_id'],'num'=>1,'type'=>2);
                }
            }
            $promotion_total_price += $vals['goods_total_price'];     //商品总价
            if ($keys != '0') {
                $promotion_price += $vals['pro_goods_discount'];
            }
        }
        $ary_return ['all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $data ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $ary_return ['all_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
            //获取赠品信息
            if(!empty($cart_gifts)){
                $ary_tmp_cart = array_merge($ary_tmp_cart,$cart_gifts);
                foreach($ary_tmp_cart as $atck=>$atcv){
                    $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                    unset($ary_tmp_cart[$atck]);
                }
            }
            // 满足满包邮条件
            foreach ($pro_datas as $pro_data) {
                if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                    foreach($pro_data['products'] as $proDatK=>$proDatV){
                        unset($ary_tmp_cart[$proDatK]);
                    }
                }
            }
            if(empty($ary_tmp_cart)){
                $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
            }
			//echo '<pre>';print_r($ary_tmp_cart);die();
            // 购买货品的总价
            $logistic_price = D('Logistic')->getLogisticPrice($data ['lt_id'], $ary_tmp_cart,$ary_member['m_id']);
        }
        $pay_fee = 0;
        if(isset($data['paymentId'])){
            $where = array(
                'pc_id'=>trim($data['paymentId']),
                'pc_status'=>1
            );
            $ary_paymentcfg = D('PaymentCfg')->getPayCfgId($where);
            if($ary_paymentcfg['pc_pay_type'] == 'alipay'){
                $pay_fee = ($ary_return['all_price']+$logistic_price)*($ary_paymentcfg['pc_fee']/100);
            }else{
                $pay_fee = $ary_paymentcfg['pc_fee'];
            }
        }
        $ary_return ['status'] = 1;
        
        $ary_return ['goods_total_sale_price'] = $promotion_total_price;
        $ary_return ['logistic_price'] = $logistic_price;
        $ary_return ['cost_price'] = $pay_fee;
        $ary_return ['promotion_price'] = sprintf("%0.2f", $promotion_price);
        $ary_return ['logistic_delivery'] = $logistic_info ['lc_cash_on_delivery'];
        $this->ajaxReturn($ary_return);
    }

    /**
     * 计算团购物流费用
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-12-25
     */
    public function checkBulkLogistic() {
        $bulk_cart = $_SESSION['bulk_cart'];
        $ary_member = session("Members");
        $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
        $ary_return['all_price'] = $array_bulk['data']['cust_price'] * $bulk_cart['num'];
        $ary_return['all_goods_price'] = $array_bulk['data']['all_price'];
        $ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);
        $logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart,$ary_member['m_id']);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $ary_return ['all_goods_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }
		//如果设置包邮
		if($array_bulk['data']['gp_is_baoyou'] == 1){
			$logistic_price = 0;
		}
        $ary_return['status'] = 1;
        $ary_return['logistic_price'] = $logistic_price;
        $ary_return['promotion_price'] = $ary_return['all_price'] - $ary_return['all_goods_price'];
        $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
        $this->ajaxReturn($ary_return);
    }
    
    /**
     * 计算秒杀物流费用
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-12-25
     */
    public function checkSpikeLogistic() {
        $param_spike = $_SESSION ['spike_cart'];
        $spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
        
        $array_where = array(
            'sp_status' => 1,
            'sp_id' => $param_spike ['sp_id']
        );
        $data = $spike->where($array_where)->find();
        if (empty($data)) {
            $this->error('秒杀商品不存在！');
        }
        $data ['cust_price'] = sprintf("%0.2f",M('goods_info')->where(array('g_id' => $data ['g_id']))->getField('g_price')*$param_spike['num']);
		$buy_nums = $data ['sp_now_number'];
        $data ['sp_now_number'] = $buy_nums;
        $ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 7);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $data ['sp_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
			$ary_cart[$param_presale['pdt_id']] = array('pdt_id' => $param_presale['pdt_id'], 'num' => $param_presale['num'], 'type' => 8);
			$logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart);
		}
		$ary_return['status'] = 1;
        $ary_return['all_price'] = $data['cust_price'];
        $ary_return['logistic_price'] = $logistic_price;
        $ary_return['promotion_price'] = $data['cust_price'] - $data['sp_price'];
        $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
		$ary_return['sp_price'] = $data['sp_price']; 
        $this->ajaxReturn($ary_return);
    }
    
    /**
     * 计算预售物流费用
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-12-25
     */
    public function checkPresaleLogistic() {
        $param_presale = $_SESSION ['presale_cart'];
        $ary_member = session("Members");
        $array_price = D("Presale")->getPresalePrice($param_presale);
		$ary_return['all_price'] = $array_price['data']['cust_price'] * $param_presale['num'];
		$ary_return['all_goods_price'] = $array_price['data']['all_price'];
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $ary_return['all_goods_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
			$ary_cart[$param_presale['pdt_id']] = array('pdt_id' => $param_presale['pdt_id'], 'num' => $param_presale['num'], 'type' => 8);
			$logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart, $ary_member['m_id']);
		}
		$ary_return['status'] = 1;
        $ary_return['logistic_price'] = $logistic_price;
        $ary_return['promotion_price'] = $ary_return['all_price'] - $ary_return['all_goods_price'];
        $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
        $this->ajaxReturn($ary_return);
    }
    
    public function getBulkLogisticType(){
        $bulk_cart = $_SESSION['bulk_cart'];
        $ary_member = session("Members");
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
        $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
        $ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);
        $ra_id = $this->_post('ra_id');
        $cr_id = $this->_post('cr_id');
        $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
        if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $v1['lt_id']), array('fx_logistic_type.lt_expressions'));
                $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                if(!empty($lt_expressions['logistics_configure']) && $array_bulk['data']['all_price'] >= $lt_expressions['logistics_configure']){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
                //判断会员等级是否包邮
                if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
				//设置包邮
				if($array_bulk['data']['gp_is_baoyou'] == 1){
					$ary_logistic [$k1] ['logistic_price'] = 0;
				}
            }
        }
        //print_r($array_bulk);die();
        $this->assign('ary_logistic', $ary_logistic);
        $this->display();
    }
    
    public function getPresaleLogisticType(){
        $param_presale = $_SESSION ['presale_cart'];
		$User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
		$array_price = D("Presale")->getPresalePrice($param_presale);
        $ra_id = $this->_post('ra_id');
        $cr_id = $this->_post('cr_id');
        $ary_cart[$param_presale['pdt_id']] = array('pdt_id' => $param_presale['pdt_id'], 'num' => $param_presale['num'], 'type' => 8);
        $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
		if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $v1['lt_id']), array('fx_logistic_type.lt_expressions'));
                $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                if(!empty($lt_expressions['logistics_configure']) && $array_price['data']['all_price'] >= $lt_expressions['logistics_configure']){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
                //判断会员等级是否包邮
                if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
            }
        }
        /*if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $ary_logistic [$k1] ['logistic_price'] = $v1 ['logistic_price'] - $p_logistic;
                if ($ary_logistic [$k1] ['logistic_price'] < 0) {
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
            }
        }*/
        $this->assign('ary_logistic', $ary_logistic);
        $this->display();
    }
    
    public function getSpikeLogisticType(){
		$param_spike = $_SESSION ['spike_cart'];
		$User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
        $spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
        $array_where = array(
            'sp_status' => 1,
            'sp_id' => $param_spike ['sp_id']
        );
        $data = $spike->where($array_where)->find();
        if (empty($data)) {
            $this->error('秒杀商品不存在！');
        }
        $data ['cust_price'] = sprintf("%0.2f",M('goods_info')->where(array('g_id' => $data ['g_id']))->getField('g_price')*$param_spike['num']);
        $ra_id = $this->_post('ra_id');
        $cr_id = $this->_post('cr_id');
        $ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 7);
        $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
        if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $v1['lt_id']), array('fx_logistic_type.lt_expressions'));
                $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                if(!empty($lt_expressions['logistics_configure']) && $data ['sp_price'] >= $lt_expressions['logistics_configure']){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
                //判断会员等级是否包邮
                if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
            }
        }
        /*if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $ary_logistic [$k1] ['logistic_price'] = $v1 ['logistic_price'] - $p_logistic;
                if ($ary_logistic [$k1] ['logistic_price'] < 0) {
                    $ary_logistic [$k1] ['logistic_price'] = 0;
                }
            }
        }*/
        $this->assign('ary_logistic', $ary_logistic);
        $this->display();
    }

    /**
     * 订单确认完成操作
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-30
     */
    public function OrderFinish() {
        $this->getSubNav(1, 1, 40);
        $ary_data = $this->_param();
        $status = 4;
        D(Orders)->UpdateOrderStatus($ary_data ['oid'], $status);
        $this->redirect(U('Ucenter/Orders/pageShow', array(
            'oid' => $ary_data ['oid']
        )));
    }

    /**
     * 查询物流跟踪信息
     */
    public function getOrdersPostTrack() {
        if (!isset($_POST ["od_id"]) || "" == $_POST ["od_id"]) {
            echo "系统集成出错：非法的发货单ID参数传入。";
            exit();
        }

        // 获取订单的物流单信息
        $array_info = D("OrdersDelivery")->where(array(
                    "od_id" => $_POST ["od_id"]
                ))->find();
        if (!is_array($array_info) || empty($array_info)) {
            echo "发货单ID错误或者发货单不存在。";
            exit();
        }

        // 根据物流公司ID获取物流公司对应的快递100的物流公司代码
        $kuaidi100_code = D("LogisticCorp")->where(array(
                    "lc_id" => $array_info ["od_logi_id"]
                ))->getField("lc_kuaidi100_name");
        if (false === $kuaidi100_code || "" == $kuaidi100_code) {
            echo "快递100物流公司代码未设置。";
            exit();
        }

        // 调用快递100接口获取物流跟踪数据
        $kuaidi100_obj = new Kuaidi100 ();
        $result = $kuaidi100_obj->queryDeliveryTrack($kuaidi100_code, $array_info ["od_logi_no"]);
        if (true == $result ["status"] && !isset($result ["data"] ["data"])) {
            $result ["data"] ["data"] = array();
        }

        krsort($result ["data"] ["data"]);
        $this->assign("post_track_info", $result ["data"] ["data"]);
        $this->assign("delivery_info", $array_info);
        $this->display("post_track");
    }

    /**
     * 确认收货
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function Receive() {
        $ary_get = $this->_get();
        $ary_order = D("Orders")->join(" " . C('DB_PREFIX') . "payment_cfg ON " . C('DB_PREFIX') . "payment_cfg.pc_id=" . C('DB_PREFIX') . "orders.o_payment")->where(array(
                    "o_id" => $ary_get ['oid']
                ))->find();

        if (!empty($ary_order ['pc_abbreviation']) && $ary_order ['pc_abbreviation'] == 'ALIPAY') {

            $ary_gateway = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'pc_code' => $ary_order ['pc_abbreviation'],
                        'o_id' => $ary_order ['o_id'],
                        'ps_status_sn' => 'WAIT_SELLER_SEND_GOODS'
                    ))->find();

            if (!empty($ary_gateway) && is_array($ary_gateway)) {
                Header("Location: https://lab.alipay.com/consume/queryDetailFromPay.htm?trade_no=" . $ary_gateway ['ps_gateway_sn']);
                exit();
            } else {
                $this->error("无需确认收货……", 3, U("Ucenter/Orders/pageList"));
            }
        }
    }

    /**
     * 订单立即评价
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function addMemberEvaluate() {
		$cfg = D('SysConfig')->getCfgByModule('goods_comment_set',1);
    	$cfg['comment_show_condition'] = explode(',',$cfg['comment_show_condition']);
    	$this->assign('cfg',$cfg);
		if($cfg['comments_switch'] != '1'){
			$this->error('未开启评论功能');exit;
		}
        $int_o_id = $this->_get('oid');
        $field = array(
            'fx_orders.o_id',
            'fx_orders_items.oi_id',
            'fx_orders.o_status',
            'fx_orders.o_all_price',
            'fx_orders.o_create_time',
            'fx_orders.o_goods_all_price',
            'fx_orders.o_pay_status',
            'fx_orders.o_receiver_name',
            'fx_orders.o_receiver_state',
            'fx_orders.o_receiver_city',
            'fx_orders.o_receiver_county',
            'fx_orders.o_discount',
            'fx_orders.o_receiver_address',
            'fx_orders.o_receiver_telphone',
            'fx_orders.o_receiver_mobile',
            'fx_orders.o_receiver_email',
            'fx_orders.o_coupon_menoy',
            'fx_orders.o_buyer_comments',
            'fx_orders.o_payment',
            'fx_orders.lt_id',
            'fx_orders.ra_id',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders.o_cost_freight',
            'fx_orders.o_receiver_time',
            'fx_orders_items.pdt_id',
            'fx_orders_items.g_id',
            'fx_orders_items.g_sn',
            'fx_orders_items.oi_g_name',
            'fx_orders_items.oi_score',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.oi_price',
            'fx_orders_items.pdt_sale_price',
            'fx_orders_items.pdt_sn',
            'fx_members.m_balance'
        );
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersData($where, $field);
        // echo "<pre>";print_r(D('Orders')->getLastSql());exit;
        // 订单详情商品
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $combo_price_total = 0;
            foreach ($ary_orders_info as $k => $v) {
                $ary_orders_info [$k] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v ['pdt_id']);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id']
                        ))->field('g_picture')->find();

                $ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
                // 订单商品退款、退货状态
                $ary_orders_info [$k] ['str_refund_status'] = $this->orders->getOrderItmesStauts('oi_refund_status', $v);
                // 订单商品发货

                $ary_orders_info [$k] ['str_ship_status'] = $this->orders->getOrderItmesStauts('oi_ship_status', $v);
                if ($v ['oi_type'] == 3) {
                    $combo_sn = $v ['pdt_sn'];
                    $tmp_ary = array(
                        'g_sn' => $ary_orders_info [$k] ['g_sn'],
                        'pdt_spec' => $ary_orders_info [$k] ['pdt_spec'],
                        'g_picture' => $ary_orders_info [$k] ['g_picture'],
                        'oi_g_name' => $ary_orders_info [$k] ['oi_g_name'],
                        'pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );

                    $combo_pdt_gid = D('GoodsProducts')->Search(array(
                        'pdt_sn' => $v ['pdt_sn']
                            ), array(
                        'g_id'
                            ));
                    $combo_where = array(
                        'g_id' => $combo_pdt_gid ['g_id'],
                        'releted_pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );
                    $combo_field = array(
                        'com_nums',
                        'com_price'
                    );
                    $combo_res = D('ReletedCombinationGoods')->getComboReletedList($combo_where, $combo_field);
                    $combo_num = $combo_res [0] ['com_nums'];
                    $combo_price_total += sprintf("%0.3f", $combo_res [0] ['com_nums'] * $combo_res [0] ['com_price']);

                    $ary_combo [$combo_sn] ['item'] [$k] = $tmp_ary;
                    $ary_combo [$combo_sn] ['num'] = $ary_orders_info [$k] ['oi_nums'] / $combo_num;
                    $ary_combo [$combo_sn] ['pdt_sale_price'] = $ary_orders_info [$k] ['pdt_sale_price'];
                    $ary_combo [$combo_sn] ['o_all_price'] = sprintf("%0.3f", $combo_price_total * $ary_combo [$combo_sn] ['num']);
                    $ary_combo [$combo_sn] ['str_ship_status'] = $ary_orders_info [$k] ['str_ship_status'];
                    $ary_combo [$combo_sn] ['str_refund_status'] = $ary_orders_info [$k] ['str_refund_status'];
                    unset($ary_orders_info [$k]);
                }
            }
        }
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign("oid", $int_o_id);
		$this->assign('m_id',$_SESSION ['Members'] ['m_id']);
        $this->display();
    }

	/**
     * 追加评价
     * 
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2014-06-17
     */
    public function againAddMemberEvaluate() {
		$cfg = D('SysConfig')->getCfgByModule('goods_comment_set',1);
    	$cfg['comment_show_condition'] = explode(',',$cfg['comment_show_condition']);
    	$this->assign('cfg',$cfg);
		if($cfg['again_comments_switch'] != 1){
			$this->error('未开启追加评论');exit;
		}
        $int_o_id = $this->_get('oid');
        $field = array(
            'fx_orders.o_id',
            'fx_orders_items.oi_id',
            'fx_orders.o_status',
            'fx_orders.o_all_price',
            'fx_orders.o_create_time',
            'fx_orders.o_goods_all_price',
            'fx_orders.o_pay_status',
            'fx_orders.o_receiver_name',
            'fx_orders.o_receiver_state',
            'fx_orders.o_receiver_city',
            'fx_orders.o_receiver_county',
            'fx_orders.o_discount',
            'fx_orders.o_receiver_address',
            'fx_orders.o_receiver_telphone',
            'fx_orders.o_receiver_mobile',
            'fx_orders.o_receiver_email',
            'fx_orders.o_coupon_menoy',
            'fx_orders.o_buyer_comments',
            'fx_orders.o_payment',
            'fx_orders.lt_id',
            'fx_orders.ra_id',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders.o_cost_freight',
            'fx_orders.o_receiver_time',
            'fx_orders_items.pdt_id',
            'fx_orders_items.g_id',
            'fx_orders_items.g_sn',
            'fx_orders_items.oi_g_name',
            'fx_orders_items.oi_score',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.oi_price',
            'fx_orders_items.pdt_sale_price',
            'fx_orders_items.pdt_sn',
            'fx_members.m_balance'
        );
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersData($where, $field);
        // echo "<pre>";print_r(D('Orders')->getLastSql());exit;
        // 订单详情商品
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $combo_price_total = 0;
            foreach ($ary_orders_info as $k => $v) {
                $ary_orders_info [$k] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v ['pdt_id']);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id']
                        ))->field('g_picture')->find();

                $ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
                // 订单商品退款、退货状态
                $ary_orders_info [$k] ['str_refund_status'] = $this->orders->getOrderItmesStauts('oi_refund_status', $v);
                // 订单商品发货

                $ary_orders_info [$k] ['str_ship_status'] = $this->orders->getOrderItmesStauts('oi_ship_status', $v);
                if ($v ['oi_type'] == 3) {
                    $combo_sn = $v ['pdt_sn'];
                    $tmp_ary = array(
                        'g_sn' => $ary_orders_info [$k] ['g_sn'],
                        'pdt_spec' => $ary_orders_info [$k] ['pdt_spec'],
                        'g_picture' => $ary_orders_info [$k] ['g_picture'],
                        'oi_g_name' => $ary_orders_info [$k] ['oi_g_name'],
                        'pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );

                    $combo_pdt_gid = D('GoodsProducts')->Search(array(
                        'pdt_sn' => $v ['pdt_sn']
                            ), array(
                        'g_id'
                            ));
                    $combo_where = array(
                        'g_id' => $combo_pdt_gid ['g_id'],
                        'releted_pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );
                    $combo_field = array(
                        'com_nums',
                        'com_price'
                    );
                    $combo_res = D('ReletedCombinationGoods')->getComboReletedList($combo_where, $combo_field);
                    $combo_num = $combo_res [0] ['com_nums'];
                    $combo_price_total += sprintf("%0.3f", $combo_res [0] ['com_nums'] * $combo_res [0] ['com_price']);

                    $ary_combo [$combo_sn] ['item'] [$k] = $tmp_ary;
                    $ary_combo [$combo_sn] ['num'] = $ary_orders_info [$k] ['oi_nums'] / $combo_num;
                    $ary_combo [$combo_sn] ['pdt_sale_price'] = $ary_orders_info [$k] ['pdt_sale_price'];
                    $ary_combo [$combo_sn] ['o_all_price'] = sprintf("%0.3f", $combo_price_total * $ary_combo [$combo_sn] ['num']);
                    $ary_combo [$combo_sn] ['str_ship_status'] = $ary_orders_info [$k] ['str_ship_status'];
                    $ary_combo [$combo_sn] ['str_refund_status'] = $ary_orders_info [$k] ['str_refund_status'];
                    unset($ary_orders_info [$k]);
                }
            }
        }
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign("oid", $int_o_id);
		$this->assign('m_id',$_SESSION ['Members'] ['m_id']);
        $this->display();
    }
	
	/**
     * 追加评论
     * 
     * @author Wanggubiin<wangguibin@guanyisoft.com>
     * @date 2014-06-17
     */
    public function addCommentAgain() {
		//后台评论设置
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
		$comment['comment_show_condition'] = explode(',', $comment['comment_show_condition']);		
        $ary_post = $this->_post();
		$i = 1;
		foreach($ary_post['goods'] as &$good){
			if(!empty($ary_post['uploadPic'.$i])){
				$good['gcom_pics'] =  substr(str_replace('/Lib/ueditor/php/../../..','',$ary_post['uploadPic'.$i]), 1);
				unset($ary_post['uploadPic'.$i]);
			}
			$i++;
		}
        //echo "<pre>";print_r($ary_post);exit;
        if (!empty($ary_post ['anony']) && $ary_post ['anony'] == '1') {
            $anony = '0';
            $gcom_contacts = '暂无';
			$gcom_mbname = '匿名';
        } else {
            $anony = $_SESSION ['Members'] ['m_id'];
            $gcom_contacts = $_SESSION ['Members'] ['m_email'];
            $gcom_mbname = $_SESSION['Members']['m_name'];
        }
        $module = M('goods_comments', C('DB_PREFIX'), 'DB_CUSTOM');
        $module->startTrans();
        if (!empty($ary_post ['goods']) && is_array($ary_post ['goods'])) {
            $msg_comments = false;
            $res = true;
            foreach ($ary_post ['goods'] as $keygoods => $valgoods) {
                if (!empty($valgoods ['comment'])) {
                    $data = array(
                        'm_id' => $anony,
                        'g_id' => $valgoods['g_id'],
                        'gcom_title' => '[追加评论]'.$valgoods ['g_name'],
                        'gcom_content' => $valgoods ['comment'],
                        'gcom_mbname'=>$gcom_mbname,
                        'gcom_email' => $gcom_contacts,
                        'gcom_create_time'=>date('Y-m-d H:i:s'),
						'gcom_update_time'=>date('Y-m-d H:i:s'),
                        'gcom_ip_address' => $_SERVER['SERVER_ADDR']
                    );
					if(!empty($_POST['oid'])){
						$data['gcom_order_id'] = $_POST['oid'];
					}					
					if(!empty($comment['comment_show_condition'][0]) && $comment['comment_show_condition'][0] == '1'){
                        $data['gcom_verify'] = '1';
                    }					
                    if(!empty($valgoods['gcom_pics'])){
						$data['gcom_pics'] = $valgoods['gcom_pics'];
					}
                    $arr_res = $module->add($data);
                    if (!$arr_res) {
                        $res = false;
                        $module->rollback();
                        $this->error('评论失败，请重试...');
                        exit();
                    }
					$msg_comments = true;
                } 
            }
            if (FALSE === $msg_comments) {
                $this->error("评论内容显示为空，请重新输入...");
            }
            $module->commit();
            $this->success("追加评论成功", U("Ucenter/Orders/pageList", 3));
        } else {
            $this->error("追加评论内容不能为空");
        }
    }
	
    /**
     * 添加会员评论
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function addComment() {
	    //后台评论设置
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
		$comment['comment_show_condition'] = explode(',', $comment['comment_show_condition']);		
        $ary_post = $this->_post();
		$i = 1;
		foreach($ary_post['goods'] as &$good){
			if(!empty($ary_post['uploadPic'.$i])){
				$good['gcom_pics'] =  substr(str_replace('/Lib/ueditor/php/../../..','',$ary_post['uploadPic'.$i]), 1);
				unset($ary_post['uploadPic'.$i]);
			}
			$i++;
		}
        //echo "<pre>";print_r($ary_post);exit;
        if (!empty($ary_post ['anony']) && $ary_post ['anony'] == '1') {
            $anony = '0';
            $gcom_contacts = '暂无';
			$gcom_mbname = '匿名';
        } else {
            $anony = $_SESSION ['Members'] ['m_id'];
            $gcom_contacts = $_SESSION ['Members'] ['m_email'];
            $gcom_mbname = $_SESSION['Members']['m_name'];
        }
		$member = $_SESSION ['Members'];
        $module = M('goods_comments', C('DB_PREFIX'), 'DB_CUSTOM');
        M('', C('DB_PREFIX'), 'DB_CUSTOM')->startTrans();
        if (!empty($ary_post ['goods']) && is_array($ary_post ['goods'])) {		
            $msg_comments = false;
            $res = true;
            foreach ($ary_post ['goods'] as $keygoods => $valgoods) {
				/*$ary_where = array();
				$ary_where['m_id'] = $_SESSION ['Members'] ['m_id'];
				$ary_where['g_id'] = $valgoods ['g_id'];
				$ary_where['gcom_order_id'] = $ary_post ['oid'];
				$comment_exsit = $module->where($ary_where)->count();
				if(!empty($comment_exsit)){
					M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
					$this->error('您已评论过，无需再次评论...');
					exit();
				}*/
                if (!empty($valgoods ['comment'])) {
                    $data = array(
                        'm_id' => $anony,
                        'g_id' => $valgoods ['g_id'],
                        'gcom_title' => $valgoods ['g_name'],
                        'gcom_content' => $valgoods ['comment'],
                        'gcom_mbname'=>$gcom_mbname,
                        'gcom_email' => $gcom_contacts,
                        'gcom_star_score' => $valgoods ['gcom_star_score'],
                        'gcom_create_time'=>date('Y-m-d H:i:s'),
						'gcom_update_time'=>date('Y-m-d H:i:s'),
                        'gcom_ip_address' => $_SERVER['SERVER_ADDR']
                    );
					if(!empty($_POST['oid'])){
						$data['gcom_order_id'] = $_POST['oid'];
					}
					if(!empty($comment['comment_show_condition'][0]) && $comment['comment_show_condition'][0] == '1'){
                        $data['gcom_verify'] = '1';
                    }
                    if(!empty($valgoods['gcom_pics'])){
						$data['gcom_pics'] = $valgoods['gcom_pics'];
					}
                    $arr_res = $module->add($data);
                    if (!$arr_res) {
                        $res = false;
                        M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                        $this->error('评论失败，请重试...');
                        exit();
                    }else{
						//判断是否设置签到赠送积分
						$point_config = D('Gyfx')->selectOneCache('point_config');
						if(!empty($point_config['recommend_points'])){
							$ary_data = array();
							$ary_data['type'] = 3;
							$ary_data['reward_point'] = intval($point_config['recommend_points']);
							$ary_data['memo'] = '会员评论';
							//事物开启
							$point_res = D('PointLog')->addPointLog($ary_data, $member['m_id']);
							if($point_res['status'] != '1'){
								M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
								$this->error("添加积分日志表失败...");exit;
							}else{
								$mem_res = D('Members')->where(array('m_id'=>$member['m_id']))->data(array('total_point'=>$_SESSION['Members']['total_point']+$ary_data['reward_point'],'m_update_time'=>date('Y-m-d H:i:s')))->save();
								if(!$mem_res){
									M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
									$this->error("更新会员积分信息失败");exit;
								}
							}
							//更新会员SESSION信息
							$_SESSION['Members']['total_point'] = $_SESSION['Members']['total_point']+$ary_data['reward_point'];
						}
						//追加评论
						if(!empty($point_config['show_recommend_points'])){
							$ary_data = array();
							$ary_data['type'] = 15;
							$ary_data['reward_point'] = intval($point_config['show_recommend_points']);
							$ary_data['memo'] = '会员晒单';
							//事物开启
							$point_res = D('PointLog')->addPointLog($ary_data, $member['m_id']);
							if($point_res['status'] != '1'){
								M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
								$this->error("添加积分日志表失败...");exit;
							}else{
								$mem_res = D('Members')->where(array('m_id'=>$member['m_id']))->data(array('total_point'=>$_SESSION['Members']['total_point']+$ary_data['reward_point'],'m_update_time'=>date('Y-m-d H:i:s')))->save();
								if(!$mem_res){
									M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
									$this->error("更新会员积分信息失败");exit;
								}
							}
							//更新会员SESSION信息
							$_SESSION['Members']['total_point'] = $_SESSION['Members']['total_point']+$ary_data['reward_point'];
						}						
					
					}
					$msg_comments = true;
                } 
            }
            if (FALSE === $msg_comments) {
                $this->error("评论内容显示为空，请重新输入...");
            }
            if ($res) {
                $ary_result = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'o_id' => $ary_post ['oid']
                        ))->data(array(
                            'is_evaluate' => '1'
                        ))->save();
                if (!$ary_result) {
                    M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                    $this->error("更新订单信息失败，请重试...");
                    exit();
                }			
            }
            M('', C('DB_PREFIX'), 'DB_CUSTOM')->commit();
            $this->success("评论成功", U("Ucenter/Orders/pageList", 3));
        } else {
            $this->error("评论内容不能为空");
        }
    }

    /**
     * 订单确认收货
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-12
     */
    public function OrderConfirmation() {
        $ary_get = $this->_get();
        $ary_orders_status = $this->orders->getOrdersStatus($ary_get['oid']);
        if ($ary_orders_status['deliver_status'] != '已发货') {
            $this->error("订单" . $ary_get['oid'] . "未发货");
        }
        $ary_order = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_get ['oid']))->find();
        if (!empty($ary_order) && is_array($ary_order)) {
            M('', '', 'DB_CUSTOM')->startTrans();
            $ary_result = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $ary_order ['o_id']
                    ))->data(array(
                        'o_status' => '5'
                    ))->save();
            if (FALSE !== $ary_result) {
                /*                 * ** 处理订单积分****start****By Joe***** */
                $array_point_config = D('PointConfig')->getConfigs();
                if ($array_point_config ['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '1') {
                    // 确认收货后处理赠送积分
                    if ($ary_order['o_reward_point'] > 0) {
                        $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_order['o_reward_point'], $ary_order ['m_id']);
                        if (!$ary_reward_result ['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($ary_reward_result['message']);
                            exit();
                        }
                    }

                    // 确认收货后处理消费积分
                    if ($ary_order ['o_freeze_point'] > 0) {
                        $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_order['o_freeze_point'], $ary_order ['m_id']);
                        if (!$ary_freeze_result['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($ary_freeze_result['message']);
                            exit();
                        }
                    }
                }
                /*                 * ** 处理订单积分****end********* */

                /*                 * * 订单发货后获取订单优惠券**star by Joe* */
                //获取优惠券节点
                $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
                $where = array('fx_orders.o_id' => $ary_get['oid']);
                $ary_field = array('fx_orders.o_pay', 'fx_orders.o_all_price', 'fx_orders.coupon_sn', 'fx_orders_items.pdt_id', 'fx_orders_items.oi_nums', 'fx_orders_items.oi_type');
                $ary_orders = D('Orders')->getOrdersData($where, $ary_field);
                // 本次消费金额=支付单最后一次消费记录
                $payment_serial = M('payment_serial')->where(array('o_id' => $ary_get['oid']))->order('ps_create_time desc')->select();
                $payment_price = $payment_serial[0]['ps_money'];
                $all_price = $ary_orders[0]['o_all_price'];
                $coupon_sn = $ary_orders[0]['coupon_sn'];
                if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '2') {
                    D('Coupon')->setPoinGetCoupon($ary_orders, $ary_order['m_id']);
                }
                /*                 * * 订单发货后获取订单优惠券****end********* */

                M('', '', 'DB_CUSTOM')->commit();
                $this->success("确认收货成功,请对商品进行评价");
            } else {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error("订单 " . $ary_get ['oid'] . " 确认收货失败，请重试...");
            }
        } else {
            $this->error("订单 " . $ary_get ['oid'] . " 不存在");
        }
    }
    
     /**
     * 预售订单确认页面
     */
    public function pagePresaleAdd() {
        $param_presale = $_SESSION ['presale_cart'];
        if (empty($param_presale)) {
            $this->error('请选择预售商品！');
        }
        $presale = M('presale', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_price = M('related_presale_price', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_city = M('related_presale_area', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_data = array();
        // 获取常用收货地址
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
        if (count($ary_addr) > 0) {
            $ary_data ['default_addr'] = $ary_addr [0];
            unset($ary_addr [0]);
        }
        $ary_data ['ary_addr'] = $ary_addr;
        // 获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data ['payment_cfg'] = $payment_cfg;
        // 发票信息
        $p_invoice = D('Invoice')->get();

        $invoice_type = explode(",", $p_invoice ['invoice_type']);
        $invoice_head = explode(",", $p_invoice ['invoice_head']);
        $invoice_content = explode(",", $p_invoice ['invoice_content']);

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($_SESSION ['Members'] ['m_id']);
        
        $array_where = array(
            'is_active' => 1,
            'p_id' => $param_presale ['p_id']
        );
        $data = $presale->where($array_where)->find();
        //echo "<pre>";print_r($invoice_info);die;
        if (empty($data)) {
            $this->error('预售商品不存在！');
        }
        $data ['cust_price'] = M('goods_info')->where(array(
                    'g_id' => $data ['g_id']
                ))->getField('g_price');
        // 取出价格阶级
        $rel_bulk_price = $gp_price->where(array(
                    'p_id' => $data ['p_id']
                ))->select();
        $buy_nums = $data ['p_pre_number'] + $data ['p_now_number'];
        $data ['p_now_number'] = $buy_nums;
        $array_f = array();
        foreach ($rel_bulk_price as $rbp_k => $rbp_v) {
            if ($buy_nums > $rbp_v ['rgp_num']) {
                $array_f [$rbp_v ['related_price_id']] = $rbp_v ['rgp_num'];
            }
        }
        if (!empty($array_f)) {
            $array_max = new ArrayMax($array_f);
            $rgp_num = $array_max->arrayMax();
            $data ['p_price'] = $gp_price->where(array(
                        'p_id' => $data ['p_id'],
                        'rgp_num' => $rgp_num
                    ))->getField('rgp_price');
        }
        // 获取商品基本信息
        $field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic';
        $goods_info = D('GoodsInfo')->field($field)->where(array(
                    'g_id' => $data ['g_id']
                ))->find();
        $goods_info ['gpic'] = '/' . ltrim($goods_info ['gpic'], '/');
        $goods_info ['save_price'] = $goods_info ['gprice'] - $data ['p_price'];
        // 授权线判断是否允许购买
        $goods_info ['authorize'] = true;
        if (!empty($goods_info) && is_array($goods_info)) {
            $ary_product_feild = array(
                'pdt_sn',
                'pdt_weight',
                'pdt_stock',
                'pdt_memo',
                'pdt_id',
                'pdt_sale_price',
                'pdt_market_price',
                'pdt_on_way_stock'
            );
            $where = array();
            $where ['g_id'] = $data ['g_id'];
            $where ['pdt_status'] = '1';
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                $ary_zhgoods = array();
                foreach ($ary_pdt as $kypdt => $valpdt) {
                    $specInfo = D('GoodsSpec')->getProductsSpecs($valpdt ['pdt_id']);

                    $ary_pdt [$kypdt] ['specName'] = $specInfo ['spec_name'];

                    $ary_pdt [$kypdt] ['pdt_sale_price'] = sprintf('%.2f', $valpdt ['pdt_sale_price']);
                    $ary_pdt [$kypdt] ['pdt_market_price'] = sprintf('%.2f', $valpdt ['pdt_market_price']);
                    if ($param_presale ['pdt_id'] == $valpdt ['pdt_id']) {
                        $data ['sku'] = $ary_pdt [$kypdt];
                    }
                }
            }
        }
        $goods_info ['skus'] = $ary_pdt;
        $data ['sku'] ['num'] = $param_presale ['num'];
        $data ['sku'] ['coupon_price'] = $data ['sku'] ['pdt_sale_price'] - $data ['p_price'];
        $data ['sku'] ['all_cprice'] = $data ['sku'] ['coupon_price'] * $param_presale ['num'];
        $data ['sku'] ['all_price'] = $data ['p_price'] * $param_presale ['num'];
        $data ['sku'] ['all_sale_price'] = $data ['sku'] ['pdt_sale_price'] * $param_presale ['num'];
        $data ['sku'] ['all_weight'] = $data ['sku'] ['pdt_weight'] * $param_presale ['num'];
        //是否启用定金
        if ($data ['is_deposit'] == 1) {
            $data ['p_deposit_price'] = sprintf('%.2f', $data ['p_deposit_price'] * $data ['sku'] ['num']);
        }
        $ary_cart [$data ['sku'] ['pdt_id']] = array(
                    'pdt_id' => $data ['sku'] ['pdt_id'],
                    'num' => $data ['sku'] ['num'],
                    'type' => 0,
                    'g_id'=>$data['g_id'],
                );
        // 获取配送公司表
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
            }
        }
		
		//判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $data['sku']['all_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }

        $data ['sku'] ['presale_price'] = $data ['sku'] ['all_price'];
        $data ['sku'] ['all_price'] += $logistic_price;
         //echo "<pre>";print_r($data);exit;
        $this->assign('product_data', $data);
        $this->assign('ary_addr', $ary_data ['ary_addr']);
        $this->assign('default_addr', $ary_data ['default_addr']);
        // 支付方式
        $this->assign('ary_paymentcfg', $ary_data ['payment_cfg']);
        // 配送公司
        $this->assign('ary_logistic', $ary_logistic);
        // 发票收藏列表
        $this->assign('invoice_list', $invoice_list);
        // 发票信息
        $this->assign('invoice_info', $invoice_info);
        $this->assign('invoice_content', $invoice_content);
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);
        $this->display();
    }
    
    
    public function pageSpikeAdd() {
        $param_spike = $_SESSION ['spike_cart'];
        $ary_member = $_SESSION['Members'];
        if (empty($param_spike)) {
            $this->error('请选择秒杀商品！');
        }
        $spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_data = array();
        // 获取常用收货地址
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
        if (count($ary_addr) > 0) {
            $ary_data ['default_addr'] = $ary_addr [0];
            unset($ary_addr [0]);
        }
        $ary_data ['ary_addr'] = $ary_addr;
        // 获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data ['payment_cfg'] = $payment_cfg;
        // 发票信息
        $p_invoice = D('Invoice')->get();
        $invoice_type = explode(",", $p_invoice ['invoice_type']);
        $invoice_head = explode(",", $p_invoice ['invoice_head']);
        $invoice_content = explode(",", $p_invoice ['invoice_content']);

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($ary_member ['m_id']);
        $array_where = array(
            'sp_status' => 1,
            'sp_id' => $param_spike ['sp_id']
        );
        $data = $spike->where($array_where)->find();
        if (empty($data)) {
            $this->error('秒杀商品不存在！');
        }
        $data ['cust_price'] = M('goods_info')->where(array(
                    'g_id' => $data ['g_id']
                ))->getField('g_price');
        $buy_nums = $data ['sp_now_number'];
        $data ['sp_now_number'] = $buy_nums;
        $array_f = array();
        foreach ($rel_bulk_price as $rbp_k => $rbp_v) {
            if ($buy_nums > $rbp_v ['rgp_num']) {
                $array_f [$rbp_v ['related_price_id']] = $rbp_v ['rgp_num'];
            }
        }
        if (!empty($array_f)) {
            $array_max = new ArrayMax($array_f);
            $rgp_num = $array_max->arrayMax();
            $data ['gp_price'] = $gp_price->where(array(
                        'gp_id' => $data ['gp_id'],
                        'rgp_num' => $rgp_num
                    ))->getField('rgp_price');
        }
        // 获取商品基本信息
        $field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic';
        $goods_info = D('GoodsInfo')->field($field)->where(array(
                    'g_id' => $data ['g_id']
                ))->find();
        $goods_info ['gpic'] = '/' . ltrim($goods_info ['gpic'], '/');
        $goods_info ['save_price'] = $goods_info ['gprice'] - $data ['gp_price'];
        // 授权线判断是否允许购买
        $goods_info ['authorize'] = true;
        if (!empty($goods_info) && is_array($goods_info)) {
            $ary_product_feild = array(
                'pdt_sn',
                'pdt_weight',
                'pdt_stock',
                'pdt_memo',
                'pdt_id',
                'pdt_sale_price',
                'pdt_market_price',
                'pdt_on_way_stock'
            );
            $where = array();
            $where ['g_id'] = $data ['g_id'];
            $where ['pdt_status'] = '1';
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                $ary_zhgoods = array();
                foreach ($ary_pdt as $kypdt => $valpdt) {
                    $specInfo = D('GoodsSpec')->getProductsSpecs($valpdt ['pdt_id']);

                    $ary_pdt [$kypdt] ['specName'] = $specInfo ['spec_name'];

                    $ary_pdt [$kypdt] ['pdt_sale_price'] = sprintf('%.2f', $valpdt ['pdt_sale_price']);
                    $ary_pdt [$kypdt] ['pdt_market_price'] = sprintf('%.2f', $valpdt ['pdt_market_price']);
                    if ($param_spike ['pdt_id'] == $valpdt ['pdt_id']) {
                        $data ['sku'] = $ary_pdt [$kypdt];
                    }
                }
                foreach ($skus as $key => &$sku) {
                    $skus [$key] = array_unique($sku);
                }
            }
            if (!empty($skus)) {
                $goods_info ['skuNames'] = $skus;
            } else {
                $goods_info ['pdt_id'] = $ary_pdt [0] ['pdt_id'];
            }
        }
        $goods_info ['skus'] = $ary_pdt;

        $data ['sku'] ['num'] = $param_spike ['num'];
        $data ['sku'] ['coupon_price'] = $data ['sku'] ['pdt_sale_price'] - $data ['sp_price'];
        $data ['sku'] ['all_cprice'] = $data ['sku'] ['coupon_price'] * $param_spike ['num'];
        $data ['sku'] ['all_price'] = $data ['sp_price'] * $param_spike ['num'];
        $data ['sku'] ['all_sale_price'] = $data ['sku'] ['pdt_sale_price'] * $param_spike ['num'];

        $data ['sku'] ['all_weight'] = $data ['sku'] ['pdt_weight'] * $param_spike ['num'];

        $data ['good_info'] = $goods_info;

        $i = 0;
        foreach ($ary_logistic as $logustic_key => $logustic_val) {
            if ($i == 0) {
                $ary_cart [$data ['sku'] ['pdt_id']] = array(
                    'pdt_id' => $data ['sku'] ['pdt_id'],
                    'num' => $data ['sku'] ['num'],
                    'type' => 0
                );
                $logistic_price = D('Logistic')->getLogisticPrice($logustic_val ['lt_id'], $ary_cart);
            }
            $i ++;
        }
        $ary_cart [$data ['sku'] ['pdt_id']] = array(
                    'pdt_id' => $data ['sku'] ['pdt_id'],
                    'num' => $data ['sku'] ['num'],
                    'type' => 0,
                    'g_id'=>$data['g_id'],
                );
        // 获取配送公司表
        if (!empty($ary_data ['default_addr']) && is_array($ary_data ['default_addr'])) {
            $ra_is_default = $ary_data ['default_addr'] ['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $ary_data ['default_addr'] ['cr_id'];
                $ary_logistic = $this->logistic->getLogistic($cr_id,$ary_cart);
            }
        }
		
		//判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $data['sku']['all_price'] >= $lt_expressions['logistics_configure']){
				$ary_logistic[$key]['logistic_price'] = 0;
            }
        }
		
        $data ['sku'] ['bulk_price'] = $data ['sku'] ['all_price'];
        $data ['sku'] ['all_price'] += $logistic_price;
		 // echo "<pre>";print_r($data);exit;
        $this->assign('product_data', $data);
        $this->assign('ary_addr', $ary_data ['ary_addr']);
        $this->assign('default_addr', $ary_data ['default_addr']);
        // 支付方式
        $this->assign('ary_paymentcfg', $ary_data ['payment_cfg']);
        // 配送公司
        $this->assign('ary_logistic', $ary_logistic);
        // 发票收藏列表
        $this->assign('invoice_list', $invoice_list);
        // 发票信息
        $this->assign('invoice_info', $invoice_info);
        $this->assign('invoice_content', $invoice_content);
        /*标明这个是秒杀确认页*/
        $this->assign ( 'web_type', 'Spike' );
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);
        $this->display();
    }
	
     /**
     * 获取买家上传的图片信息
	 * wangguibn@guanyisoft.com
	 * date 2014-06-26
     */
	function getCommentImages(){
	    header("Content-Type: text/html; charset=utf-8");
		error_reporting( E_ERROR | E_WARNING );
		//最好使用缩略图地址，否则当网速慢时可能会造成严重的延时
		$path = $_SERVER['DOCUMENT_ROOT']."/Public/Uploads/" . $_SESSION['CI_SN'] . '/'.'comments'.'/'.$_SESSION['Members']['m_id'].'/' ;
		$action = htmlspecialchars( $_POST[ "action" ] );
		if ( $action == "get" ) {
			$files = getfiles( $path );
			if ( !$files ) return;
			rsort($files,SORT_STRING);
			$str = "";
			foreach ( $files as $file ) {
				$str .= $file . "ue_separate_ue";
			}
			$str = str_replace($_SERVER['DOCUMENT_ROOT'].'/Public/','../../../',$str);
			echo $str;exit;
		}
	}

    /**
     * 判断会员3天内是否存在订单
     * @author wangguibin@guanyisoft.com
     * @date 2014-06-19 20:00:00
     */
    public function isGetOldOrder() {
        $ra_id = $this->_post('ra_id');
		if(empty($ra_id )){
			$this->error('请先选择一个收货地址');exit;
		}
		//$ra_address = D('ReceiveAddress')->getAddressByraid($ra_id);
		$ary_where = array();
		$time = strtotime('-2 day',time());  
		$beginTime = date('Y-m-d 00:00:00', $time); 
		$ary_where['o_create_time'] = array('egt',$beginTime);
		$ary_where['o_status'] = array('neq',2);
		$ary_where['ra_id'] = $ra_id;
		$is_exsit_order = D('Orders')->where($ary_where)->count();
		if($is_exsit_order>0){
            $ary_res ['status'] = 1;
		}else{
            $ary_res ['status'] = 0;
		}
        echo json_encode($ary_res);
        exit;
	}

   /**
     * 销售订单列表
    */
    function orderList(){
        $member = $_SESSION['Members'];
      /*   echo '<pre>';print_r($member);die; */
        if($member['m_type'] !=2){
            $this->error('请核对您的身份，只有供货商才能进入！');
            die;
        }
        
        
        $ary_get = $this->_request();
		$mp_id = $this->_get('mp_id');
        
        //echo '<pre>';print_r($ary_get);die;
       //订单搜索条件
        $ary_where = array();
		//关联查询条件
		$join_where = array();
		//查询内容
		$str_fields = " ".C("DB_PREFIX")."orders.*";
        //如果需要根据订单号进行搜索
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_id'] = $ary_get['o_id'];
        }
		//根据合并支付订单号进行搜索
		if(!empty($mp_id) && isset($mp_id)){
			$ary_o_id = M('merger_payment',C('DB_PREFIX'),'DB_CUSTOM')->field("o_id")->where(array('mp_id'=>$mp_id))->select();
			if(!empty($ary_o_id)){
				foreach($ary_o_id as $v){
					$ary_chose[] = $v['o_id'];
				}
				$ary_where ['fx_orders.o_id'] = array(
					'IN',
					$ary_chose
				);
			}
		}
		
		//如果需要根据第三方你订单号进行搜索
		if(!empty($ary_get['o_source_id']) && isset($ary_get['o_source_id'])){
			$ary_where[C("DB_PREFIX") . 'orders.o_source_id'] = $ary_get['o_source_id'];
		}
        //如果需要根据会员名称进行搜索
        if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where[C("DB_PREFIX") . 'members.m_name'] = $ary_get['m_name'];
			$join_where[] = " " . C("DB_PREFIX") . "members ON " . C("DB_PREFIX") . "members.m_id=" . C("DB_PREFIX") . "orders.m_id";
        }
        //如果需要根据收货人进行搜索
        if (!empty($ary_get['o_receiver_name']) && isset($ary_get['o_receiver_name'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_name'] = $ary_get['o_receiver_name'];
        }
        //判断订单状态
        if (isset($ary_get['o_status'])) {
            //如果需要根据订单状态进行搜索
            if (!empty($ary_get['o_status'])) {
				/**
				$ary_o_status_where = array();
				//去除全部选项
				foreach ($ary_get['o_status'] as $os_v) {
					if ($os_v !== 0) {
						$ary_o_status_where[] = (int) $os_v;
					}
				}
				if (!empty($ary_o_status_where)) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('in', $ary_o_status_where);
                }
				**/
                $ary_where[C("DB_PREFIX") . 'orders.o_status'] = $ary_get['o_status'];
            }
        } else {
            //如果提交的参数中没有search,则代表是点击菜单进入订单列表，默认不显示作废订单，如果是搜索的结果，则显示作废订单
            if (!isset($ary_get['search'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('neq', 2);
            }
        }
        //如果需要根据支付状态进行搜索
        if (!empty($ary_get['o_pay_status']) && isset($ary_get['o_pay_status']) && $ary_get['o_pay_status'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'orders.o_pay_status'] = $ary_get['o_pay_status'];
        }
        
        //如果需要根据支付状态进行搜索
        if (!empty($ary_get['erp_sn']) && isset($ary_get['erp_sn']) && $ary_get['erp_sn'] == 'no') {
            $ary_where[C("DB_PREFIX") . 'orders.erp_sn'] = '';
        }

        //如果需要根据配送方式进行搜索
        if (!empty($ary_get['lt_id']) && isset($ary_get['lt_id']) && $ary_get['lt_id'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'logistic_corp.lc_id'] = $ary_get['lt_id'];
			$join_where[] = " " . C("DB_PREFIX") . "logistic_type ON " . C("DB_PREFIX") . "logistic_type.lt_id=" . C("DB_PREFIX") . "orders.lt_id";
			$join_where[] = " " . C("DB_PREFIX") . "logistic_corp ON " . C("DB_PREFIX") . "logistic_type.lc_id=" . C("DB_PREFIX") . "logistic_corp.lc_id";
			$str_fields .=" ,".C("DB_PREFIX")."logistic_type.*,".C("DB_PREFIX")."logistic_corp.* ";
		}

        //如果需要根据支付方式进行搜索
        if (isset($ary_get['o_payment']) && !empty($ary_get['o_payment'])) {
            $ary_payment_where = array();
            //去除全部选项
            foreach ($ary_get['o_payment'] as $op_v) {
                if ($op_v !== '-1') {
                    $ary_payment_where[] = (int) $op_v;
                }
            }
            if (!empty($ary_payment_where)) {
                $ary_where[C("DB_PREFIX") . 'orders.o_payment'] = array('in', $ary_payment_where);
            }
        }
        //如果需要根据支付方式进行搜索
        if (isset($ary_get['oi_ship_status']) && $ary_get['oi_ship_status'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'orders_items.oi_ship_status'] = $ary_get['oi_ship_status'];
		}
		//根据订单商品名称搜索
        if(!empty($ary_get['oi_g_name']) && isset($ary_get['oi_g_name'])){
            $ary_where[C("DB_PREFIX").'orders_items.oi_g_name'] = array('like',"%".$ary_get['oi_g_name']."%");
        }
		if(!empty($ary_get['oi_g_name']) || isset($ary_get['oi_ship_status'])){
			$join_where[] = " " . C("DB_PREFIX") . "orders_items ON " . C("DB_PREFIX") . "orders_items.o_id=" . C("DB_PREFIX") . "orders.o_id";
		}
        if (!empty($ary_get['province']) && isset($ary_get['province']) && $ary_get['province'] != '0') {
            $province = D('Gyfx')->selectOneCache('city_region',"cr_name", array('cr_id' => $ary_get['province']));
			//M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['province']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_state'] = $province['cr_name'];
		}
        if (!empty($ary_get['city']) && isset($ary_get['city']) && $ary_get['city'] != '0') {
            $province = D('Gyfx')->selectOneCache('city_region',"cr_name", array('cr_id' => $ary_get['city']));
			//M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['city']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_city'] = $province['cr_name'];
        }
        if (!empty($ary_get['region1']) && isset($ary_get['region1']) && $ary_get['region1'] != '0') {
			$province = D('Gyfx')->selectOneCache('city_region',"cr_name", array('cr_id' => $ary_get['region1']));
			//M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['region1']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_county'] = $province['cr_name'];
        }

        //如果需要根据收货人手机进行搜索
        if (!empty($ary_get['o_receiver_mobile']) && isset($ary_get['o_receiver_mobile'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_mobile'] = $ary_get['o_receiver_mobile'];
        }
        //如果需要根据物流费用进行搜索
        if (!empty($ary_get['o_cost_freight_1']) && !empty($ary_get['o_cost_freight_2'])) {
            if ($ary_get['o_cost_freight_1'] > $ary_get['o_cost_freight_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($ary_get['o_cost_freight_2'], $ary_get['o_cost_freight_1']));
            } else if ($ary_get['o_cost_freight_1'] < $ary_get['o_cost_freight_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($ary_get['o_cost_freight_1'], $ary_get['o_cost_freight_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = $ary_get['o_cost_freight_1'];
            }
        } else {
            if (!empty($ary_get['o_cost_freight_1']) && empty($ary_get['o_cost_freight_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("EGT", $ary_get['o_cost_freight_1']);
            } else if (empty($ary_get['o_cost_freight_1']) && !empty($ary_get['o_cost_freight_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("ELT", $ary_get['o_cost_freight_2']);
            }
        }
        //如果需要根据订单金额进行搜索
        if (!empty($ary_get['o_all_price_1']) && !empty($ary_get['o_all_price_2'])) {
            if ($ary_get['o_all_price_1'] > $ary_get['o_all_price_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($ary_get['o_all_price_2'], $ary_get['o_all_price_1']));
            } else if ($ary_get['o_all_price_1'] < $ary_get['o_all_price_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($ary_get['o_all_price_1'], $ary_get['o_all_price_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = $ary_get['o_all_price_1'];
            }
        } else {
            if (!empty($ary_get['o_all_price_1']) && empty($ary_get['o_all_price_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("EGT", $ary_get['o_all_price_1']);
            } else if (empty($ary_get['o_all_price_1']) && !empty($ary_get['o_all_price_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("ELT", $ary_get['o_all_price_2']);
            }
        }

        //如果需要根据使用优惠券进行搜索
        if (!empty($ary_get['o_coupon_1']) && empty($ary_get['o_coupon_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_coupon_1'];            
        } else if (empty($ary_get['o_coupon_1']) && !empty($ary_get['o_coupon_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_coupon_2'];
        }

        //如果需要根据使用开发票搜索
        if (!empty($ary_get['is_invoice_1']) && empty($ary_get['is_invoice_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_all_price_1'];
            if (!empty($ary_get['invoice_type'])) {
                $ary_where[C("DB_PREFIX") . 'orders.invoice_type'] = $ary_get['invoice_type'];
            }
        } else if (empty($ary_get['is_invoice_1']) && !empty($ary_get['is_invoice_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_coupon_2'];
        }

        //如果需要根据物流费用进行搜索
        if (!empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
            if ($ary_get['o_create_time_1'] > $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_get['o_create_time_2'], $ary_get['o_create_time_1']));
            } else if ($ary_get['o_create_time_1'] < $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_get['o_create_time_1'], $ary_get['o_create_time_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $ary_get['o_create_time_1'];
            }
        } else {
            if (!empty($ary_get['o_create_time_1']) && empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $ary_get['o_create_time_1']);
            } else if (empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $ary_get['o_create_time_2']);
            }
        }
        
        //如果要根据客服名称搜索
        if(!empty($ary_get['admin_name']) && isset($ary_get['admin_name'])){
            $admin_id = D('Admin')->where(array('u_name'=>$ary_get['admin_name']))->getField('u_id');
            if(isset($admin_id) && !empty($admin_id)){
                $ary_where[C("DB_PREFIX").'orders.admin_id'] = $admin_id;
            }
        }
        
        //print_r($ary_where);exit;
        //数据分页处理，获取符合条件的记录数并分页显示
        $count = D('Orders')->
            join('fx_members on fx_orders.m_id = fx_members.m_id ')->
            join('fx_goods on fx_members.m_id = fx_goods.gm_id')->where('fx_goods.gm_id ='. $_SESSION['Members']['m_id'])->order(array(C("DB_PREFIX") . 'orders.o_id' => 'desc'))->count('distinct(fx_orders.o_id)');
       // echo D('Orders')->getLastSql();die;
        $string_count = $count;
        $obj_page = new Page($string_count, 20);
        $page = $obj_page->show();
        //订单数据获取
        $array_order = array(C("DB_PREFIX") . 'orders.o_id' => 'desc');
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        /* $ary_orders_info = D("Orders")
                        ->Distinct(true)
						->field($str_fields)
						->join($join_where)
						->where($ary_where)->order($array_order)->limit($string_limit)->select(); */
		//echo D("Orders")->getLastSql();exit;
        
        $ary_orders_info = D('Orders')->
            join('fx_members on fx_orders.m_id = fx_members.m_id ')->
            join('fx_goods on fx_members.m_id = fx_goods.gm_id')->where('fx_goods.gm_id ='. $_SESSION['Members']['m_id'])->select();
        //echo D('orders')->getLastSql();die;
	   //获取所有用户
	   $admins = D('Admin')->field('u_id,u_name')->select();
	   $ary_admin = array();
	   foreach($admins as $admin_info){
		$ary_admin[$admin_info['u_id']] = $admin_info['u_name'];
	   }
	   foreach($ary_orders_info as $o_key=>$sub_order){
		if($sub_order['admin_id']){
			$ary_orders_info[$o_key]['admin_name'] = $ary_admin[$sub_order['admin_id']];
		}
	   }
	   //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = D("PaymentCfg")->where(1)->getField("pc_id,pc_custom_name");


        // echo "<pre>";print_r(D("Orders")->getLastSql());exit;
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            //订单状态
            $ary_status = array('o_status' => $v['o_status']);
            $str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
            $ary_orders_info[$k]['str_status'] = $str_status;

            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
            $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];
            //获取会员名称
            $ary_orders_info[$k]['m_name'] = D("Members")->where(array("m_id" => $v["m_id"]))->getField("m_name");

            //订单支付方式名称
            $ary_orders_info[$k]['pc_name'] = $array_payment_cfg[$v["o_payment"]];
            //获取所有物流公司信息，用于匹配配送公司名称
            $wheres = array();
            $wheres[C("DB_PREFIX") . 'logistic_type.lt_id'] = $v['lt_id'];
            //echo "<pre>";print_r($where);exit;
            $delivery_company_info = D("LogisticCorp")
                    ->field(C("DB_PREFIX") . "logistic_corp.lc_id," . C("DB_PREFIX") . "logistic_corp.lc_name," . C("DB_PREFIX") . "logistic_corp.lc_is_enable")
                    ->join(" " . C("DB_PREFIX") . "logistic_type ON " . C("DB_PREFIX") . "logistic_type.lc_id=" . C("DB_PREFIX") . "logistic_corp.lc_id")
                    ->where($wheres)
                    ->find();
            if (empty($delivery_company_info)) {
                $delivery_company_info['lc_is_enable'] = '0';
            } else {
                $delivery_company_info['lc_is_enable'] = '1';
            }
                       // echo "<pre>";print_r($delivery_company_info);
            //订单物流公司名称
            $ary_orders_info[$k]['delivery_company_name'] = $delivery_company_info['lc_is_enable'] ? $delivery_company_info['lc_name'] : '已删除';
            //$ary_orders_info[$k]['delivery_company_name'] = isset($delivery_company_info[$v["lt_id"]])?$delivery_company_info[$v["lt_id"]]:'已删除';
            //客户人员处理
            $ary_orders_info[$k]['u_name'] = '';
            if (isset($v['admin_id']) && $v['admin_id'] > 0) {
                $ary_admin = D('Admin')->getAdminInfoById($v['admin_id'], array('u_name'));
                if (is_array($ary_admin) && !empty($ary_admin)) {
                    $ary_orders_info[$k]['u_name'] = $ary_admin['u_name'];
                }
            }
            
            //发货时间
            $ary_orders_info[$k]['order_deliver_time'] = M('orders_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id'],'ol_behavior'=>'发货成功'))->getField('ol_create');
            //付款时间
            $pay_where['ol_behavior'] = array("LIKE", "%支付成功%");
            $pay_where['ol.o_id'] = array("EQ", $v['o_id']);
            $pay_where['o_pay_status'] = array(array("EQ", 1), array("EQ", 3), "OR");
            $ary_orders_info[$k]['order_pay_time'] = M('orders_log ol',C('DB_PREFIX'),'DB_CUSTOM')
                                                   ->join(C("DB_PREFIX"). "orders o on o.o_id = ol.o_id")
                                                   ->where($pay_where)->getField('ol_create');
            //订单商品数量
            $arr_oinum = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->select();
            $int_oinum = '0';
            foreach($arr_oinum as $oinum){
                $int_oinum += $oinum['oi_nums'];
            }
            $ary_orders_info[$k]['oi_nums'] = $int_oinum;
           // echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
            //售后状态
            $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $v['o_id']))->order('or_create_time asc')->select();
            if (!empty($ary_afersale) && is_array($ary_afersale)) {
                foreach ($ary_afersale as $keyaf => $valaf) {
                    if ($valaf['or_service_verify'] == '1' && $valaf['or_finance_verify'] == '1') {
                        M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $v['o_id'],'or_update_time'=>date('Y-m-d H:i:s')))->save(array('or_processing_status' => 1));
                    }
                    //退款
                    if ($valaf['or_refund_type'] == 1) {
                        switch ($valaf['or_processing_status']) {
                            case 0:
                                $ary_orders_info[$k]['refund_status'] = '退款中';
                                break;
                            case 1:
                                $ary_orders_info[$k]['refund_status'] = '退款成功';
                                break;
                            case 2:
                                $ary_orders_info[$k]['refund_status'] = '退款驳回';
                                break;
                            default:
                                $ary_orders_info[$k]['refund_status'] = ''; //没有退款
                        }
                    } elseif ($valaf['or_refund_type'] == 2) {         //退货
                        switch ($valaf['or_processing_status']) {
                            case 0:
                                $ary_orders_info[$k]['refund_goods_status'] = '退货中';
                                break;
                            case 1:
                                $ary_orders_info[$k]['refund_goods_status'] = '退货成功';
                                break;
                            case 2:
                                $ary_orders_info[$k]['refund_goods_status'] = '退货驳回';
                                break;
                            default:
                                $ary_orders_info[$k]['refund_goods_status'] = ''; //没有退款
                        }
                    }
                }
            }
            //订单商品类型
            $ary_orders_info[$k]['o_goods_type'] = M('orders_items')->where(array('o_id'=>$v['o_id']))->getField('oi_type');
			$ary_promotion = array();
			$ary_promotion = unserialize($v['promotion']);
            
            if(!empty($ary_promotion) && is_array($ary_promotion)) {
                foreach($ary_promotion as $v_pro) {
                    if(!empty($v_pro['products']) && is_array($v_pro['products'])) {
                        foreach($v_pro['products'] as $k_name=>$v_name['products']) {
                            if(isset($v_name['products'][0])){
                                $ary_orders_info[$k]['g_name'] = $v_name['products'][0]['g_name']; 
                            }else{
                                $ary_orders_info[$k]['g_name'] = $v_name['products']['g_name']; 
                            }
							
                        }
                    }
                }
            }else{
                //if($ary_orders_info[$k]['o_goods_type'] == 5 || $ary_orders_info[$k]['o_goods_type'] == 4 || $ary_orders_info[$k]['o_goods_type'] == 6 || $ary_orders_info[$k]['o_goods_type'] == 8 || $ary_orders_info[$k]['o_goods_type'] == 7){
                    $ary_orders_info[$k]['g_name'] = D('OrdersItems')->where(array('o_id'=>$v['o_id']))->getField('oi_g_name');
                //}
            }
        }
	    // 是否开启
        $ary_order_remove_on = D('SysConfig')->getCfg('ORDERS_REMOVE','ORDERS_REMOVE','1','是否开启订单拆分');
        $ary_get['order_remove_on'] = $ary_order_remove_on['ORDERS_REMOVE']['sc_value'];
        $this->assign("filter", $ary_get);
        $this->assign("page", $page);
        $this->assign("get",json_encode($_GET));
       // echo "<pre>";print_r($ary_orders_info);exit;
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 使用促销支付
     */
    public function doPromotions() {
        $type = $this->_post('type');
        $str_csn = $this->_post('csn');
        $bonus = $this->_post('bonus');
        $cards = $this->_post('cards');
        $jlb = $this->_post('jlb');
        $point = $this->_post('point');
        $lt_id = $this->_post('lt_id');
        $data = $this->_post();
        $ary_tmp_cart = D('Cart')->GetData();
        if($data['pids']){
            $ary_pid = explode(',',$data['pids']);
            foreach ($ary_tmp_cart as $key=>$cd){
                foreach ($ary_pid as $pid){
                    if($pid == $key){
                        $ary_cart[$pid] = $ary_tmp_cart[$key];
                    }
                }
            }
        }else{
            $ary_cart = $ary_tmp_cart;
        }
        $ary_tmp_cart = $ary_cart;
        $ary_member = session("Members");
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);

        $subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);
        $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        $ary_promotion = array();
        $promotion_total_price = '0';
        $promotion_price = '0';
        //dump($pro_datas);die();
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals['products'] as $key => $val) {
                $arr_products = $this->cart->getProductInfo(array($key => $val));
                if ($arr_products[0][0]['type'] == '4') {
                    foreach ($arr_products[0] as &$provals) {
                        $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                    }
                }
                $pro_datas[$keys]['products'][$key] = $arr_products[0];
                $pro_data[$key] = $val;
                $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
            }
            //赠品数组
            if(!empty($vals['gifts'])){
                foreach($vals['gifts'] as $gifts){
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gifts['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
                    $cart_gifts[$pdt_id['pdt_id']]=array('pdt_id'=>$pdt_id['pdt_id'],'num'=>1,'type'=>2);
                }
            }
            $promotion_total_price += $vals['goods_total_price'];     //商品总价
            if ($keys != '0') {
                $promotion_price += $vals['pro_goods_discount'];
            }
        }
        $ary_res['all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $lt_id), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $ary_res['all_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
            //获取赠品信息
            if(!empty($cart_gifts)){
                $ary_tmp_cart = array_merge($ary_tmp_cart,$cart_gifts);
                foreach($ary_tmp_cart as $atck=>$atcv){
                    $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                    unset($ary_tmp_cart[$atck]);
                }
            }
            // 满足满包邮条件
            foreach ($pro_datas as $pro_data) {
                if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                    foreach($pro_data['products'] as $proDatK=>$proDatV){
                        unset($ary_tmp_cart[$proDatK]);
                    }
                }
            }
            if(empty($ary_tmp_cart)){
                $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
            }
            $logistic_price = D('Logistic')->getLogisticPrice($lt_id, $ary_tmp_cart,$ary_member['m_id']);
        }
        //使用优惠券
        if (isset($str_csn)) {
            $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
			$date = date('Y-m-d H:i:s');
            if($ary_coupon['status'] == 'error'){
                if($type == 0){
                    $ary_res ['errMsg'] = $ary_coupon['msg'];
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['coupon_price'] = '0';
                }
            } else {
                foreach ($ary_coupon['msg'] as $coupon){
                    if ($coupon ['c_condition_money'] > 0 && $ary_res ['all_price'] < $coupon ['c_condition_money']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}优惠券不满足使用条件";
                            $ary_res ['success'] = 0;
                            break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($coupon ['c_is_use'] == 1 || $coupon ['c_used_id'] != 0) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}被使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($coupon ['c_start_time'] > $date) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}不能使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($date > $coupon ['c_end_time']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}活动已经结束";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } else {
                        if($type == 0){
                            $ary_res ['sucMsg'] = '可以使用';
                            $ary_res ['success'] = 1;
                        }
						if($coupon['c_type'] == '1'){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$ary_res['all_price']);
							}else{
								//计算可以使用优惠券总金额
								$coupon_all_price = 0;
								foreach ($pro_datas as $keys => $vals) {
                                    if($keys == 0){
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = $this->cart->getProductInfo(array($key => $val));
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $coupon_all_price += $provals['pdt_price']*$provals['num'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $coupon_all_price += $val['pdt_price']*$val['num'];     //商品总价
                                            }
                                        }
                                    }else{
                                        $other_total_price = 0;
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = $this->cart->getProductInfo(array($key => $val));
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $other_total_price += $provals['pdt_price']*$provals['num'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $other_total_price += $val['pdt_price']*$val['num'];     //商品总价
                                            }
                                        }
                                        if($other_total_price > $vals['goods_total_price']){
                                            $coupon_all_price += $vals['goods_total_price'];
                                        }else{
                                            $coupon_all_price += $other_total_price;
                                        }
                                    }
								}
							}
							$ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$coupon_all_price);
						}else{
							$ary_res ['coupon_price'] += $coupon ['c_money'];
						} 
                    }
                }
            }
        }
        //红包支付
        if ($bonus > 0) {
            $arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
            if($bonus > $arr_bonus['m_bonus']){
                if($type == 1){
                    $ary_res ['errMsg'] = '红包金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['bonus_price'] = '0';
                }
            }elseif($ary_res ['all_price'] < $bonus) {
                if($type == 1){
                    $ary_res ['errMsg'] = '红包金额超过了商品总金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['bonus_price'] = '0';
                }
            }else{       
                if($type == 1){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['bonus_price'] = $bonus;
                }else{
                    $ary_res ['bonus_price'] = $bonus;
                }
            }
        }else{
            if($type == 1){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['bonus_price'] = 0;
            }else{
                $ary_res ['bonus_price'] = 0;
            }
        }
        //储值卡支付
        if ($cards > 0) {
            $arr_cards = M('Members')->field("m_cards")->where(array('m_id'=>$ary_member['m_id']))->find();
            if($cards > $arr_cards['m_cards']){
                if($type == 2){
                    $ary_res ['errMsg'] = '储值卡金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['cards_price'] = 0;
                }
            }elseif($ary_res ['all_price'] < $cards) {
                if($type == 2){
                    $ary_res ['errMsg'] = '储值卡金额超过了商品总金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['cards_price'] = 0;
                }
            }else{
                if($type == 2){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['cards_price'] = $cards;
                }else{
                    $ary_res ['cards_price'] = $cards;
                }
            }
        }else{
            if($type == 2){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['cards_price'] = 0;
            }else{
                $ary_res ['cards_price'] = 0;
            }
        }
		/**
        //金币支付
        if ($jlb > 0) {
            $arr_jlb = M('Members')->field("m_jlb")->where(array('m_id'=>$ary_member['m_id']))->find();
            if($jlb > $arr_jlb['m_jlb']){
                if($type == 3){
                    $ary_res ['errMsg'] = '金币金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['jlb_price'] = 0;
                }
            }elseif($ary_res ['all_price'] < $jlb) {
                if($type == 3){
                    $ary_res ['errMsg'] = '金币金额超过了商品总金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['jlb_price'] = 0;
                }
            }else{
                if($type == 3){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['jlb_price'] = $jlb;
                }else{
                    $ary_res ['jlb_price'] = $jlb;
                }
            }
        }else{
            if($type == 3){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['jlb_price'] = 0;
            }else{
                $ary_res ['jlb_price'] = 0;
            }
        }**/
        //积分支付
        if ($point > 0) {
            $pointCfg = D('PointConfig');
            // 计算订单可以使用的积分
            $is_use_point = $pointCfg->getIsUsePoint($ary_res['all_price'],$ary_member['m_id']);
            if($point <= $is_use_point){
                $ary_data = $pointCfg->getConfigs();
                $consumed_points = intval($ary_data['consumed_points']);
                //积分抵扣的总金额
                $ary_res['point_price'] = (0.01/$consumed_points)*$point;
                $ary_res ['points'] = $point;
                if($type == 4){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                }
            }else{
                if($type == 4){
                    $ary_res ['errMsg'] = '积分使用失败！不能大于可使用的积分';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['points'] = 0;
                    $ary_res ['point_price'] = 0;
                }
            }
        }else{
            if($type == 4){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
            }else{
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
            }
        }
        $ary_res ['logistic_price'] = $logistic_price;
        $ary_res ['promotion_price'] = sprintf("%0.2f", $promotion_price);
		$result_price = sprintf("%0.2f", $promotion_total_price - $ary_res['promotion_price'] - $ary_res['coupon_price'] - $ary_res['bonus_price'] - $ary_res['cards_price'] - $ary_res['jlb_price'] - $ary_res['point_price']);
		if($result_price<0){
			$ary_res ['errMsg'] = '使用抵扣总金额超过了商品总金额';
			$ary_res ['success'] = 2;
			$ary_res ['points'] = 0;
            $ary_res ['point_price'] = 0;
			$ary_res ['jlb_price'] = 0;
			$ary_res ['cards_price'] = 0;
			$ary_res ['bonus_price'] = 0;	
			$ary_res ['coupon_price'] = '0';
			$ary_res['all_price'] = sprintf("%0.2f", $promotion_total_price - $ary_res['promotion_price'] + $ary_res['logistic_price']);
		}else{
			$ary_res['all_price'] = sprintf("%0.2f", $promotion_total_price - $ary_res['promotion_price'] - $ary_res['coupon_price'] - $ary_res['bonus_price'] - $ary_res['cards_price'] - $ary_res['jlb_price'] - $ary_res['point_price'] + $ary_res['logistic_price']);
		}
        echo json_encode($ary_res);
        exit();
    }

	/**
     * 支付发送短信
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-08-11
     */
    public function sendMobileCode() {
		//开启手机验证
		$mobile_set = D('SysConfig')->getCfg('PAY_SEND_CODE','PAY_SEND_CODE','0','支付发验证码');
		if($mobile_set['PAY_SEND_CODE']['sc_value'] != 1){
			$this->ajaxReturn(array('status'=>0,'msg'=>'未开启手机验证'));
		}
		$m_mobile = $_SESSION['Members']['m_mobile'];
		if(empty($m_mobile)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'手机号不存在'));
		}
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = array('neq',2);
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 5;
		$ary_sms_where['mobile'] = $m_mobile;
		$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log_count = D('SmsLog')->getCount($ary_sms_where);
		if($sms_log_count>0){
			$this->ajaxReturn(array('status'=>0,'msg'=>'90秒后才允许重新获取验证码！'));
		}
		$SmsApi_obj=new SmsApi();
		//获取注册发送验证码模板
		$template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'PAY_CODE'));
		$send_content = '';
		if($template_info['status'] == true){
			$send_content = $template_info['content'];
		}
		if(empty($send_content)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败！'));
		}
		$array_params=array('mobile'=>$m_mobile,'','content'=>$send_content);
		$res=$SmsApi_obj->smsSend($array_params);
         if($res['code'] == '200'){
			//日志记录下
			$ary_data = array();
			$ary_data['sms_type'] = 5;
			$ary_data['mobile'] = $m_mobile;
			$ary_data['content'] = $send_content;
			$ary_data['code'] = $template_info['code'];
			$sms_res = D('SmsLog')->addSms($ary_data);
            if(!$sms_res){
				writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
			}
			$this->ajaxReturn(array('status'=>1,'msg'=>'短信发送成功！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败，'.$array_result['msg']));
        }
    }	
	
	/**
     * 验证手机支付
     * 
     * @author wangguibin<wangguibin@guanyisoft.com>
     *  @date 2014-08-11
     */
    public function validateSmsCode() {
        $data = $this->_post();
		//开启手机验证
		$mobile_set = D('SysConfig')->getCfg('PAY_SEND_CODE','PAY_SEND_CODE','0','支付发验证码');
		if($mobile_set['PAY_SEND_CODE']['sc_value'] != 1){
			$this->ajaxReturn(array('status'=>0,'msg'=>'未开启手机验证'));exit;
		}
		$m_mobile = $_SESSION['Members']['m_mobile'];
		$m_mobile_code = $this->_post('m_mobile_code');
		if(empty($m_mobile_code) || empty($m_mobile)){
			$this->ajaxReturn(array('status'=>0,'info'=>'已开启手机验证，请输入验证码'));exit;
		}else{
			//判断手机号是否在90秒内已发送短信验证码
			$ary_sms_where = array();
			$ary_sms_where['check_status'] = 0;
			$ary_sms_where['status'] = 1;
			$ary_sms_where['sms_type'] = 5;
			$ary_sms_where['code'] = $m_mobile_code;
			//$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
			$sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);	
			
			if($sms_log['code'] != $m_mobile_code){
				$this->ajaxReturn(array('status'=>0,'info'=>'验证码不存在或已过期'));exit;
			}else{
				//更新验证码使用状态
				$up_res = D('SmsLog')->updateSms(array('id'=>$sms_log['id']),array('check_status'=>1));
				if(!$up_res){
					$this->ajaxReturn(array('status'=>0,'info'=>'注册失败,更新验证码状态失败'));exit;
				}
				//设置其他已发送验证码无效
				D('SmsLog')->updateSms(array('sms_type'=>5,'check_status'=>0,'mobile'=>$m_mobile),array('check_status'=>2));
			}
		}
		$this->ajaxReturn(array('status'=>1,'info'=>'支付验证成功'));exit;
    }
	
    /**
     *
     * 调长益储值卡接口查看储值卡信息
     * @author Hcaijin
     * @date 2014-09-02
     */
    public function myCashCardPage() {
		$ary_cards = $this->_post();
        $ary_cards['cardsType'] = '2';    //长益卡号类型
        $ary_cards['cardsStore'] = 'BH002'; //长益门店代码，固定值
        $cardsInfo = new CashCard();
        $cashcard = $cardsInfo->findCardApi($ary_cards);
        if($cashcard['status'] == 1){
            $this->assign('cards', $cashcard['data']);
        }else{
            $this->assign('msg', $cashcard['msg']);
        }
        $this->display();
    }

    /**
     * [导出订单]
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-10
     */
    public function ExportOrder(){
        $o_id = $this->_request('o_id');
        $m_id = $_SESSION['Members']['m_id'];
        $string = '';
        $where = array('m_id' => $m_id);
        if($o_id != 'all'){
            $ary = explode(',',$o_id);
            $ary = array_unique($ary);
            $ary = array_diff($ary,array(null,'null','',' '));
            $string = implode(',',$ary);
        }
        if(!empty($string)){
            $where['o_id'] = array('in',$string);
        }
        $contents = array();
        $orders = D('Orders');
        $ary_orders_info = $orders->where($where)->order(array('o_create_time' => 'desc'))->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {   // 来自pageList方法
            foreach ($ary_orders_info as $k => $v) {
                // 获取销货单状态(线下支付) -- by Tom <helong@guanyisoft.com>
                if($v['o_payment'] == 3){
                    $ary_voucher = D('sales_receipts')->where(array('o_id'=>$v['o_id'],'sr_type'=>0,'sr_status'=>1))->find();
                    if(isset($ary_voucher['sr_verify_status'])){
                        $ary_orders_info[$k]['voucher_status'] = $ary_voucher['sr_verify_status'] == 0 ? '单据未审' : '单据已审';
                    }
                }
                // 订单状态
                $ary_orders_status = $this->orders->getOrdersStatus($v ['o_id']);
                $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'o_id' => $v ['o_id']
                        ))->order('or_create_time asc')->select();
                if (!empty($ary_afersale) && is_array($ary_afersale)) {
                    $ary_orders_info[$k]['refund_part_status'] = D('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                ->where(array(
                                                                    'oi_refund_status' =>array(array('eq',1),array('eq',6),'or'),
                                                                    'o_id' => $v['o_id']
                                                                    ))
                                                                ->count();
                    foreach ($ary_afersale as $keyaf => $valaf) {
                        if ($valaf ['or_service_verify'] == '1' && $valaf ['or_finance_verify'] == '1') {
                            M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'o_id' => $v ['o_id'],
                                'or_id'=>$valaf['or_id']
                            ))->save(array(
                                'or_processing_status' => 1
                            ));
                        }
                        // 退款
                        if ($valaf ['or_refund_type'] == 1) {
                            switch ($valaf ['or_processing_status']) {
                                case 0 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款中';
                                    break;
                                case 1 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款成功';
                                    break;
                                case 2 :
                                    $ary_orders_info [$k] ['refund_status'] = '退款驳回';
                                    break;
                                default :
                                    $ary_orders_info [$k] ['refund_status'] = ''; // 没有退款
                            }
                        } elseif ($valaf ['or_refund_type'] == 2) { // 退货
                            switch ($valaf ['or_processing_status']) {
                                case 0 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货中';
                                    break;
                                case 1 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货成功';
                                    break;
                                case 2 :
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退货驳回';
                                    break;
                                default :
                                    $ary_orders_info [$k] ['refund_goods_status'] = ''; // 没有退款
                            }
                        }
                    }
                }
                // 付款状态
                if ($ary_orders_info [$k] ['refund_status'] == '') {
                    $ary_orders_info [$k] ['str_pay_status'] = $this->orders->getOrderItmesStauts('o_pay_status', $v ['o_pay_status']);
                }

                $ary_orders_info [$k] ['str_status'] = $this->orders->getOrderItmesStauts('o_status', $v ['o_status']);
                if ($ary_orders_info [$k] ['refund_goods_status'] == '') {
                    $ary_orders_info [$k] ['deliver_status'] = $ary_orders_status ['deliver_status'];
                }
                // 物流信息
                $ary_orders_info[$k]['od_logi_no'] = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->getField("od_logi_no");
                $contents[] = array(
                    "'".$v['o_id'],
                    "'".$v['o_source_id'],
                    $v['o_receiver_name'],
                    $v['o_all_price'],
                    $v['o_create_time'],
                    $ary_orders_info [$k]['str_status'].' '.$ary_orders_info [$k]['str_pay_status'].' '.$ary_orders_info [$k]['refund_status'].' '.$ary_orders_info [$k]['refund_goods_status'].' '.$ary_orders_info [$k]['deliver_status'],
                    $ary_orders_info [$k]['od_logi_no']
                    );
            }
            // 订单导出处理
            $header = array('订单编号', '第三方订单编号', '收货人', '订单金额', '下单时间', '订单状态', '物流单号');
            $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
            $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
            if(!is_dir($filexcel)){
                    @mkdir($filexcel,0777,1);
            }
            $Export = new Export(date('YmdHis') . '.xls', $filexcel);
            $excel_file = $Export->exportExcel($header, $contents, $fields, $mix_sheet = '会员订单信息', true);
            if (!empty($excel_file)) {
                $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
            } else {
                $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'没有需要导出单据'));
        }
    }
	
	/**
     * [导入订单]
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-01-08
     */
    public function importOrder(){
		$this->getSubNav(0, 0, 70);
		$this->display();
	}	
	
	/**
     * 执行批量添加商品[订单导入]
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-01-09
     */
    public function doBatchAddGoods(){
		@set_time_limit(0);  
        @ignore_user_abort(TRUE); 
        header("Content-type: text/html;charset=utf-8");
        require_once FXINC . '/Lib/Common/' . 'PHPExcel/IOFactory.php';
        require_once FXINC . '/Lib/Common/' . 'PHPExcel.php';
        require_once FXINC . '/Lib/Common/' . 'Upfile.class.php';
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->saveRule  = date('YmdHis') ;// 设置附件上传大小
        $upload->allowExts  = array('xlsx','xls','csv');// 设置附件上传类型
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/'.date('Ymd').'/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $upload->savePath =  $filexcel;// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        $str_upload_file = $info[0]['savepath'].$info[0]['savename'];
        $objCalc = PHPExcel_Calculation::getInstance();
        //读取Excel客户模板
        $objPHPExcel = PHPExcel_IOFactory::load($str_upload_file);
        $obj_Writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //读取第一个工作表(编号从 0 开始)
        $sheet = $objPHPExcel->getSheet(0);
        //取到有多少条记录 
        $highestRow = $sheet->getHighestRow();
        $ary_cart = array();
        $i = 0;
		$str_pid = '';
        for($row=2; $row <= $highestRow; $row++){
            //商品名称的输入验证
            $ary_cart[$i]['pdt_sn'] = trim($objPHPExcel->getActiveSheet()->getCell('A' . $row)->getCalculatedValue());
			if(empty($ary_cart[$i]['pdt_sn'])){
                unlink($str_upload_file);
                $this->error('上传模板失败,请输入商品规格编码');
            }
			if(preg_match("/[^a-zA-Z0-9\._-]+/",$ary_cart[$i]['pdt_sn'])){
                $this->error("上传模板失败,‘{$ary_cart[$i]['pdt_sn']}’商品规格编码不符合要求(	字母、数字或“_”、“-”、“.”组成)！");
            }
            //商家编码的输入验证
            $ary_cart[$i]['pdt_num'] = trim($objPHPExcel->getActiveSheet()->getCell('B' . $row)->getCalculatedValue());
            if(empty($ary_cart[$i]['pdt_num'])){
                unlink($str_upload_file);
                $this->error('上传模板失败,请输入商品数量');
            }
			if(!is_numeric($ary_cart[$i]['pdt_num']) && !isset($ary_cart[$i]['pdt_num'])){
                unlink($str_upload_file);
				$this->error("上传模板失败，SKU“{$ary_cart[$i]['pdt_num']}”的库存数量值不合法。");				
			}
			//验证货号的唯一性
			if(!empty($ary_cart[$i]['pdt_sn'])){
				$ary_pdtsn_check = D("GoodsProductsTable")->where(array("pdt_sn"=>$ary_cart[$i]['pdt_sn']))->field('pdt_id,g_id,pdt_sn,pdt_stock')->find();
				if(is_array($ary_pdtsn_check) && !empty($ary_pdtsn_check)){
					if($ary_pdtsn_check['pdt_stock']<$ary_cart[$i]['pdt_num']){
						unlink($str_upload_file);
						$this->error("上传模板失败，SKU商品编码“{$ary_cart[$i]['pdt_sn']}”的库存已不足，库存只有".$ary_pdtsn_check['pdt_stock']."件。");						
					}
				}else{
					unlink($str_upload_file);
					$this->error("上传模板失败，SKU商品编码“{$ary_cart[$i]['pdt_sn']}”不存在。");				
				}
				$ary_cart[$i]['pdt_id'] = $ary_pdtsn_check['pdt_id'];
				$ary_cart[$i]['g_id'] = $ary_pdtsn_check['g_id'];
				$str_pid .= $ary_cart[$i]['pdt_id'].',';
			}
            $i++;
        }
		$str_pid = substr($str_pid,0,strlen($str_pid)-1); 
		//插入购物车的数据集合
		$ary_db_carts = array();
		$ary_db_carts = D('Cart')->GetData();
		foreach ($ary_cart as $key => $ary_cart_info) {
			if (array_key_exists($ary_cart_info['pdt_id'], $ary_db_carts) && isset($ary_db_carts[$ary_cart_info['pdt_id']]['type'])) {
				$ary_db_carts[$ary_cart_info['pdt_id']]['num']+=$ary_cart_info['pdt_num'];
			} else {
				$ary_db_carts[$ary_cart_info['pdt_id']] = array('pdt_id' => $ary_cart_info['pdt_id'], 'num' => $ary_cart_info['pdt_num'], 'type' => 0, 'g_id' => $ary_cart_info['g_id']);
			}
		}
		$Cart = D('Cart')->WriteMycart($ary_db_carts);
		if ($Cart == false) {
			$this->error("商品加入购物车失败");exit;	
		}
        //提示商品资料添加成功，并且跳转到目标页面。
		$this->success("商品导入成功",U("Ucenter/Orders/pageAdd",array('pid'=>$str_pid,'pt'=>1,'np'=>'1')));
    }
	

}
