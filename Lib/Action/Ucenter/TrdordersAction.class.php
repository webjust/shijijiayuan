<?php

/**
 * 用户中心第三方平台订单Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.0
 * @author Terry
 * @date 2012-12-11
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TrdordersAction extends CommonAction {

    /**
     * 控制器默认页，默认跳转到店铺管理页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function index() {
        $this->redirect(U('Ucenter/Distribution/pageShops'));
    }

    /**
     * 第三方平台订单列表
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-11
     */
    public function pageTaobao() {
        $this->getSubNav(1, 1, 40);
        $ary_filter = $this->_get();
        $member = session('Members');
        $obj_shops = D("ThdShops");
        $ThdOrders = D("ThdOrders");
        $arr_where = array();
        //分销商自己授权的店铺
        $arr_where['m_id'] = $member['m_id'];
       	//$arr_where['m_id'] = array("NEQ", '0');
        $arr_where['ts_source'] = '1';
        $arr_result = $obj_shops->where($arr_where)->select();
        //获取店铺信息
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] > 0) {
            $shopwhere = array();
            $shop = array();
            $shopwhere['ts_id'] = (int) $ary_filter['tsid'];
            $shopwhere['ts_source'] = '1';
            $shop = $obj_shops->where($shopwhere)->find();
            $this->assign("shop", $shop);
        }
        $where = array();
        $order_desc = array();
        $obj_orders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] != '0') {
            /** *****************拼接查询条件************************************** */
            //按外部平台订单号
            if ($ary_filter['tt_id']) {
                $where['to_oid'] = trim($ary_filter['tt_id']);
            }

            if (!empty($ary_filter['match']) && $ary_filter['match'] != '0') {
                if ($ary_filter['match'] == '1') {
                    $where['to_is_match'] = 0;
                } else {
                    $where['to_is_match'] = 1;
                }
            }
            //按买家昵称
            if ($ary_filter['buyer']) {
                $where['to_buyer_id'] = array('LIKE', '%' . trim($ary_filter['buyer']) . '%');
            }
			$start_time = date("Y-m-d H:i",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
			if(empty($ary_filter['tt_id']) && empty($ary_filter['order_minDate'])){
				$ary_filter['order_minDate'] = $start_time;
			}
            if (!empty($ary_filter['order_minDate']) && isset($ary_filter['order_minDate'])) {
                if (!empty($ary_filter['order_maxDate'])) {
                    $ary_filter['order_maxDate'] = trim($ary_filter['order_maxDate']);
                } else {
                    $ary_filter['order_maxDate'] = date("Y-m-d H:i");
                }
                //按成交时间
                if ($ary_filter['order_minDate']) {
                    $where['to_created'] = array("between", array($ary_filter['order_minDate'].":00", $ary_filter['order_maxDate'].":00"));
                }
            }
            if (!empty($ary_filter['goods_name']) && isset($ary_filter['goods_name'])) {
                //商品名称
                if ($ary_filter['goods_name']) {
                    $where['toi_titles'] =  array('LIKE', '%' . trim($ary_filter['goods_name']) . '%') ;
                }
            }

            $where['to_source'] = 1;
            $where['to_tt_status'] = empty($ary_filter['status'])? 0:$ary_filter['status'];
            $where['ts_id'] = (int) $ary_filter['tsid'];
			$where['to_thd_status'] = 'WAIT_SELLER_SEND_GOODS';
            /*             * *****************获取订单总数************************************** */
            $count = $obj_orders->where($where)->count();
//            echo "<pre>";print_r($where);exit;
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
//            $products = M("view_products",C('DB_PREFIX'),'DB_CUSTOM');
            $products = D("GoodsProductsTable");
            //获取货品价格等额外数据
            $obj_price = new ProPrice();
            $goodsSpec = D('GoodsSpec');
            $ary_product_feild = array('pdt_sn', C('DB_PREFIX') . 'goods_info.g_id', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price', 'pdt_on_way_stock', C('DB_PREFIX') . 'goods_info.g_name', C('DB_PREFIX') . 'goods_info.g_picture');
            $ary_result = $ThdOrders->getThdOrdersPageList($where, $obj_page->firstRow, $obj_page->listRows);
//            echo "<pre>";print_r($ary_result);exit;
            $gTotalPrice = '';
            if (!empty($ary_result) && is_array($ary_result)) {
                foreach ($ary_result as $keydata => $valdata) {
                    $ary_result[$keydata]['count'] = count($valdata['orders']);
                    foreach ($valdata['orders'] as $keyorder => $valorder) {
                        $ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] = unserialize($valorder['toi_b2b_pdt_sn_info']);
                        $goods = array();
//dump($ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info']);die();
                        foreach ($ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] as $keypdtsn => $valpdtsh) {
                            $goods[$keypdtsn] = $products->field($ary_product_feild)
                                            ->join(C('DB_PREFIX') . "goods_info ON " . C('DB_PREFIX') . "goods_products.g_id=" . C('DB_PREFIX') . "goods_info.g_id")
                                            ->where(array('pdt_sn' => (string)$valpdtsh['pdt_sn']))
                                            ->order(array('g_id' => 'desc'))->find();
                            //echo "<pre>";print_r($products->getLastSql());exit;
                            if (!empty($goods[$keypdtsn]) && is_array($goods[$keypdtsn])) {
                                $goods[$keypdtsn]['num'] = $valpdtsh['num'];
                            }
                        }

                        if (!empty($goods) && is_array($goods)) {
                            foreach ($goods as $keyg => $valg) {

                                $goods[$keyg]['specName'] = $goodsSpec->getProductsSpec($valg['pdt_id']);
                                $ary_price = $obj_price->getPriceInfo($valg['pdt_id'],$member['m_id']);
                                $goods[$keyg]['gPrice'] = $ary_price['pdt_price'];
                                $goods[$keyg]['cgPrice'] = sprintf("%.3f", $goods[$keyg]['gPrice'] * $valg['num']);
                                $goods[$keyg]['totalWeight'] = sprintf("%.3f", $goods[$keyg]['pdt_weight'] * $valg['num']);
                                $goods[$keyg]['num'] = $valg['num'];
                                $ary_result[$keydata]['orders'][$keyorder]['pic'] = $valg['g_picture'];
                                if ($valorder['toi_status'] == '0') {
                                    $ary_result[$keydata]['totalPrice'] += $goods[$keyg]['gPrice'] * $goods[$keyg]['num'];
                                } else {
                                    break;
                                }
                                $ary_result[$keydata]['orders'][$keyorder]['goods'] = $goods;
                            }
                        } else {
                            $empty = '';
                            $empty .= '<td colspan="3">无匹配</td>';
                            $this->assign('empty', $empty);
                        }
//                        echo "<pre>";print_r($ary_result);exit;
//                        $ary_result[$keydata]['orders'][$keyorder]['goods'] = array();
                    }
                    $gTotalPrice +=$ary_result[$keydata]['totalPrice'];
					if(!empty($valdata['to_temp_receiver_address'])){
						$arr_address = json_decode($valdata['to_temp_receiver_address'], true);
						$ary_result[$keydata]['temp_receiver_province'] = $arr_address['to_receiver_province'];
						$ary_result[$keydata]['temp_receiver_city'] = $arr_address['to_receiver_city'];
						$ary_result[$keydata]['temp_receiver_district'] = $arr_address['to_receiver_district'];
						$ary_result[$keydata]['temp_receiver_address'] = $arr_address['to_receiver_address'];
					}
                }
            }
            /*             * *****************获取商品分类信息************************************** */
        }
		//dump($ary_result);die();
		//获取物流公司
		$logistics = D("LogisticCorp")->where(array('lc_is_enable'=>1))
									  ->field("lt.lc_id,lc_name,lt_id,lt_config,lt_expressions,lt_protect,lt_protect_rate")
									  ->join(C('DB_PREFIX').'logistic_type as lt on lt.lc_id = ' . C('DB_PREFIX').'logistic_corp.lc_id')
									  ->group('lt.lc_id')->select();
		$this->assign('logistics',$logistics);$this->assign('logistics',$logistics);
		//获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $this->assign("taobaoShop", $arr_result);
        $ary_filter['payment_cfg'] = $payment_cfg;
        $goods = D("ViewGoods");
        $category = $goods->getCates();
        $this->assign("category", $category);
        $this->assign("filter", $ary_filter);
        $this->assign("countPrice", $gTotalPrice);
        $this->assign("order", $ary_result);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 第三方平台订单列表(京东)
     *
     * @return mixed array
     *
     * @author Wang <wangguibin@guanyisoft.com>
	 * @京东接口
	 * @request 
	 * @to_thd_status京东订单状态
	 * 多订单状态可以用英文逗号隔开 1）WAIT_SELLER_STOCK_OUT 等待出库 2）SEND_TO_DISTRIBUTION_CENER *发往配送中心（只适用于LBP，SOPL商家） 3）DISTRIBUTION_CENTER_RECEIVED 配送中心已收货（只适用于LBP，SOPL商家） *4）WAIT_GOODS_RECEIVE_CONFIRM 等待确认收货 5）RECEIPTS_CONFIRM 收款确认（服务完成）（只适用于LBP，SOPL商家） *6）WAIT_SELLER_DELIVERY等待发货（只适用于海外购商家） 7）FINISHED_L 完成 8）TRADE_CANCELED 取消 9）LOCKED 已锁定  	 
     * @version 7.8.2
     * @modify 2015-03-16
     */
    public function pageJd() {
        $this->getSubNav(1, 1, 42);
        $ary_filter = $this->_get();
        $member = session('Members');
        $obj_shops = D("ThdShops");
        $ThdOrders = D("ThdOrders");
        $arr_where = array();
        //分销商自己授权的店铺
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '3';
        $arr_result = $obj_shops->where($arr_where)->select();
        //获取店铺信息
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] > 0) {
            $shopwhere = array();
            $shop = array();
            $shopwhere['ts_id'] = (int) $ary_filter['tsid'];
            $shopwhere['ts_source'] = '3';
            $shop = $obj_shops->where($shopwhere)->find();
            $this->assign("shop", $shop);
        }
		
        $where = array();
        $order_desc = array();
        $obj_orders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] != '0') {
            /*             * *****************拼接查询条件************************************** */
            //按外部平台订单号
            if ($ary_filter['tt_id']) {
                $where['to_oid'] = trim($ary_filter['tt_id']);
            }

            if (!empty($ary_filter['match']) && $ary_filter['match'] != '0') {
                if ($ary_filter['match'] == '1') {
                    $where['to_is_match'] = 0;
                } else {
                    $where['to_is_match'] = 1;
                }
            }
            //按买家昵称
            if ($ary_filter['buyer']) {
                $where['to_buyer_id'] = array('LIKE', '%' . trim($ary_filter['buyer']) . '%');
            }
            if (!empty($ary_filter['order_minDate']) && isset($ary_filter['order_minDate'])) {
                if (!empty($ary_filter['order_maxDate'])) {
                    $ary_filter['order_maxDate'] = trim($ary_filter['order_maxDate']);
                } else {
                    $ary_filter['order_maxDate'] = date("Y-m-d H:i:s");
                }
                //按成交时间
                if ($ary_filter['order_minDate']) {
                    $where['to_created'] = array("between", array($ary_filter['order_minDate'], $ary_filter['order_maxDate']));
                }
            }
            $where['to_source'] = 3;
            $where['to_tt_status'] = empty($ary_filter['status'])? 0:$ary_filter['status'];
            $where['ts_id'] = (int) $ary_filter['tsid'];
			$where['to_thd_status'] = 'WAIT_SELLER_STOCK_OUT';//等待出库 
            /*             * *****************获取订单总数************************************** */
            $count = $obj_orders->where($where)->count();
            //echo "<pre>";print_r($where);exit;
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
//            $products = M("view_products",C('DB_PREFIX'),'DB_CUSTOM');
            $products = D("GoodsProductsTable");
            //获取货品价格等额外数据
            $obj_price = new ProPrice();
            $goodsSpec = D('GoodsSpec');
            $ary_product_feild = array('pdt_sn', C('DB_PREFIX') . 'goods_info.g_id', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price', 'pdt_on_way_stock', C('DB_PREFIX') . 'goods_info.g_name', C('DB_PREFIX') . 'goods_info.g_picture');
            $ary_result = $ThdOrders->getThdOrdersPageList($where, $obj_page->firstRow, $obj_page->listRows);
//            echo "<pre>";print_r($ary_result);exit;
            $gTotalPrice = '';
            if (!empty($ary_result) && is_array($ary_result)) {
                foreach ($ary_result as $keydata => $valdata) {
                    $ary_result[$keydata]['count'] = count($valdata['orders']);
                    foreach ($valdata['orders'] as $keyorder => $valorder) {
                        $ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] = unserialize($valorder['toi_b2b_pdt_sn_info']);
                        $goods = array();

                        foreach ($ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] as $keypdtsn => $valpdtsh) {
                            $goods[$keypdtsn] = $products->field($ary_product_feild)
                                            ->join(C('DB_PREFIX') . "goods_info ON " . C('DB_PREFIX') . "goods_products.g_id=" . C('DB_PREFIX') . "goods_info.g_id")
                                            ->where(array('pdt_sn' => $valpdtsh['pdt_sn']))
                                            ->order(array('g_id' => 'desc'))->find();
                           // echo "<pre>";print_r($products->getLastSql());exit;
                            if (!empty($goods[$keypdtsn]) && is_array($goods[$keypdtsn])) {
                                $goods[$keypdtsn]['num'] = $valpdtsh['num'];
                            }
                        }

                        if (!empty($goods) && is_array($goods)) {
                            foreach ($goods as $keyg => $valg) {

                                $goods[$keyg]['specName'] = $goodsSpec->getProductsSpec($valg['pdt_id']);
                                $ary_price = $obj_price->getPriceInfo($valg['pdt_id'],$member['m_id']);
                                $goods[$keyg]['gPrice'] = $ary_price['pdt_price'];
                                $goods[$keyg]['cgPrice'] = sprintf("%.3f", $goods[$keyg]['gPrice'] * $valg['num']);
                                $goods[$keyg]['totalWeight'] = sprintf("%.3f", $goods[$keyg]['pdt_weight'] * $valg['num']);
                                $goods[$keyg]['num'] = $valg['num'];
                                $ary_result[$keydata]['orders'][$keyorder]['pic'] = $valg['g_picture'];
                                if ($valorder['toi_status'] == '0') {
                                    $ary_result[$keydata]['totalPrice'] += $goods[$keyg]['gPrice'] * $goods[$keyg]['num'];
                                } else {
                                    break;
                                }
                                $ary_result[$keydata]['orders'][$keyorder]['goods'] = $goods;
                            }
                        } else {
                            $empty = '';
                            $empty .= '<td colspan="3">无匹配</td>';
                            $this->assign('empty', $empty);
                        }
//                        echo "<pre>";print_r($ary_result);exit;
//                        $ary_result[$keydata]['orders'][$keyorder]['goods'] = array();
                    }
                    $gTotalPrice +=$ary_result[$keydata]['totalPrice'];
					if(!empty($valdata['to_temp_receiver_address'])){
						$arr_address = json_decode($valdata['to_temp_receiver_address'], true);
						$ary_result[$keydata]['temp_receiver_province'] = $arr_address['to_receiver_province'];
						$ary_result[$keydata]['temp_receiver_city'] = $arr_address['to_receiver_city'];
						$ary_result[$keydata]['temp_receiver_district'] = $arr_address['to_receiver_district'];
						$ary_result[$keydata]['temp_receiver_address'] = $arr_address['to_receiver_address'];
					}
                }
            }
            /*             * *****************获取商品分类信息************************************** */
        }
		//获取物流公司
		$logistics = D("LogisticCorp")->where(array('lc_is_enable'=>1))
									  ->field("lt.lc_id,lc_name,lt_id,lt_config,lt_expressions,lt_protect,lt_protect_rate")
									  ->join(C('DB_PREFIX').'logistic_type as lt on lt.lc_id = ' . C('DB_PREFIX').'logistic_corp.lc_id')
									  ->group('lt.lc_id')->select();
		$this->assign('logistics',$logistics);$this->assign('logistics',$logistics);
		//获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $this->assign("taobaoShop", $arr_result);
        $ary_filter['payment_cfg'] = $payment_cfg;
        $goods = D("ViewGoods");
        $category = $goods->getCates();
        $this->assign("category", $category);
        $this->assign("filter", $ary_filter);
        $this->assign("countPrice", $gTotalPrice);
        $this->assign("order", $ary_result);
        $this->assign('page', $page);
        $this->display();
    }	
	
    public function doSynTaobao() {
        $platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        switch ($platform) {
            //拍拍处理流程
            case 'paipai':

            break;
            //京东处理流程
            case 'jd':
			$callback_url = "http://";
			$callback_url .= $_SERVER["HTTP_HOST"];
			if(80 != $_SERVER["SERVER_PORT"]){
				$callback_url .= ':' . $_SERVER["SERVER_PORT"];
			}
			$callback_url .= '/' . trim(U("Ucenter/Trdorders/doAuthJd",array('type'=>3)),'/');
			$jd_obj = new Jd();
			$jd_obj->topOauth($callback_url);
            break;				
            //默认淘宝处理流程
            default:
                $url = C('FX_TAOBAO_CENTER');
                $url .= '?act=create';
                $url .= '&callback=';
                $url .= rawurlencode(U('Ucenter/Trdorders/doAuthTaobao', array('pf' => 'taobao'), '', false, true));
                //echo "<pre>";print_r($url);exit;
                redirect($url);
                break;
        }
    }
    /**
     * 授权页，授权后进入订单列表(京东授权)
     * @return mixed array
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.8.2
     * @add 2015-03-16
     */
    public function doAuthJd() {
    	$redirt_url = U("Ucenter/Trdorders/pageJd");
    	$array_data = $_GET;
		$jd_obj = new Jd();
    	$jd_obj->callback($array_data,$is_exist=false,$redirt_url);
    }
	
    /**
     * 授权页，授权后进入订单列表
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-12
     */
    public function doAuthTaobao() {
        $obj_shops = D("ThdShops");
        //根据店铺授权请求店铺基本信息
        $str_platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        $ary_token = $this->_get();
		//dump($ary_token);die();
        session('Token', $ary_token);
        $obj_api = Apis::factory($str_platform, $ary_token);
        $str_shop_result = $obj_api->getShopInfo(array('nick' => rawurldecode($ary_token['taobao_user_nick'])));
        $str_seller_result = $obj_api->getSellerInfo();
        //保存店铺的基本信息及授权信息
        $ary_member = session('Members');
        $bln_result = $obj_shops->saveShop($ary_token, $str_shop_result, $str_seller_result, $str_platform, $ary_member['m_id']);
		if ($bln_result) {
            $ary_shop_info = json_decode($str_shop_result, true);
            session('Shop', $ary_shop_info['shop_get_response']['shop']);
            $this->success('登陆授权成功', U('Ucenter/Trdorders/pageTaobao'));
        } else {
            $this->error('登陆授权失败', U('Ucenter/Trdorders/pageTaobao'));
        }
    }

    /**
     * 拍拍订单列表页
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-15
     */
    public function pagePaipai() {
        $this->getSubNav(2, 1, 45);
        $member = session("Members");
        
        //$shopPai = session("ShopPai");
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '2';
        $arr_result = $obj_shops->where($arr_where)->select();
        $ary_filter = $this->_get();
        //获取店铺信息
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] > 0) {
            $shopwhere = array();
            $shopPai = array();
            $shopwhere['ts_id'] = (int) $ary_filter['tsid'];
            $shopwhere['ts_source'] = '2';
            $shopPai = $obj_shops->where($shopwhere)->find();
            $this->assign("shop", $shopPai);
            //echo "<pre>";print_r($shopPai);exit;
        }
        $where = array();
        $obj_orders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] != '0') {
            /*             * *****************拼接查询条件************************************** */
            //按外部平台订单号
            if ($ary_filter['tt_id']) {
                $where['to_oid'] = trim($ary_filter['tt_id']);
            }

            if (!empty($ary_filter['match']) && $ary_filter['match'] != '0') {
                if ($ary_filter['match'] == '1') {
                    $where['to_is_match'] = 0;
                } else {
                    $where['to_is_match'] = 1;
                }
            }

            //按买家昵称
            if ($ary_filter['buyer']) {
                $where['to_buyer_id'] = array('LIKE', '%' . trim($ary_filter['buyer']) . '%');
            }
            if (!empty($ary_filter['order_minDate']) && isset($ary_filter['order_minDate'])) {
                if (!empty($ary_filter['order_minDate'])) {
                    $ary_filter['order_maxDate'] = trim($ary_filter['order_maxDate']);
                } else {
                    $ary_filter['order_maxDate'] = date("Y-m-d H:i:s");
                }
                //按成交时间
                if ($ary_filter['order_minDate']) {
                    $where['to_created'] = array("between", array($ary_filter['order_minDate'], $ary_filter['order_maxDate']));
                }
            }
            $where['to_tt_status'] = empty($ary_filter['status'])? 0:$ary_filter['status'];
            $where['to_source'] = 2;
            /*             * *****************获取订单总数************************************** */
            $count = $obj_orders->where($where)->count();
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
            $ThdOrders = D("ThdOrders");
//            $products = M("view_products", C('DB_PREFIX'), 'DB_CUSTOM');
            $products = D("GoodsProductsTable");
            $price = new PriceModel($member['m_id']);
            $goodsSpec = D('GoodsSpec');
            $ary_product_feild = array('pdt_sn', C('DB_PREFIX') . 'goods_info.g_id', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price', 'pdt_on_way_stock', C('DB_PREFIX') . 'goods_info.g_name', C('DB_PREFIX') . 'goods_info.g_picture');
            $ary_result = $ThdOrders->getThdOrdersPageList($where, $obj_page->firstRow, $obj_page->listRows);
            
            $gTotalPrice = '';
            if (!empty($ary_result) && is_array($ary_result)) {
                foreach ($ary_result as $keydata => $valdata) {
                    $ary_result[$keydata]['count'] = count($valdata['orders']);
                    foreach ($valdata['orders'] as $keyorder => $valorder) {
                        $ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] = unserialize($valorder['toi_b2b_pdt_sn_info']);
                        $goods = array();

                        foreach ($ary_result[$keydata]['orders'][$keyorder]['pdt_sn_info'] as $keypdtsn => $valpdtsh) {
                            $goods[$keypdtsn] = $products->field($ary_product_feild)
                                            ->join(C('DB_PREFIX') . "goods_info ON " . C('DB_PREFIX') . "goods_products.g_id=" . C('DB_PREFIX') . "goods_info.g_id")
                                            ->where(array('pdt_sn' => $valpdtsh['pdt_sn']))
                                            ->order(array('g_id' => 'desc'))->find();
//                            echo "<pre>";print_r($products->getLastSql());exit;
                            if (!empty($goods[$keypdtsn]) && is_array($goods[$keypdtsn])) {
                                $goods[$keypdtsn]['num'] = $valpdtsh['num'];
                            }
                        }
                        if (!empty($goods) && is_array($goods)) {
                            foreach ($goods as $keyg => $valg) {
                                $goods[$keyg]['g_picture'] = '/' . ltrim($valg['g_picture'],'/');
//                                echo "<pre>";print_r($valg);
                                $goods[$keyg]['specName'] = $goodsSpec->getProductsSpec($valg['pdt_id']);
                                $goods[$keyg]['gPrice'] = $price->getMemberPrice($valg['pdt_id']);
//                                echo "<pre>";print_r($price->getMemberPrice($valg['pdt_id']));exit;
                                $goods[$keyg]['cgPrice'] = sprintf("%.3f", $goods[$keyg]['gPrice'] * $valg['num']);
                                $goods[$keyg]['num'] = $valg['num'];
                                $ary_result[$keydata]['orders'][$keyorder]['pic'] = $valg['g_picture'];
                                if ($valorder['toi_status'] == '0') {
                                    $ary_result[$keydata]['totalPrice'] += $goods[$keyg]['gPrice'] * $goods[$keyg]['num'];
                                } else {
                                    break;
                                }
                                $ary_result[$keydata]['orders'][$keyorder]['goods'] = $goods;
                            }
                        } else {
                            $empty = '';
                            $empty .= '<td colspan="3">无匹配</td>';
                            $this->assign('empty', $empty);
                        }
//                        $ary_result[$keydata]['orders'][$keyorder]['goods'] = $goods;
                    }
                    $gTotalPrice +=$ary_result[$keydata]['totalPrice'];
                }
            }
        }
//        echo "<pre>";print_r($goods);exit;
        /*         * *****************获取商品分类信息************************************** */
        //获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_filter['payment_cfg'] = $payment_cfg;
        $goods = D("ViewGoods");
        $category = $goods->getCates();
        $this->assign("category", $category);
        $this->assign("filter", $ary_filter);
        $this->assign("countPrice", $gTotalPrice);
        $this->assign("order", $ary_result);
        $this->assign('page', $page);
        $this->assign("shopdata", $arr_result);
        $this->assign("sessionData", $shopPai);
        $this->display();
    }

//    /**
//     * 授权页，授权后进入订单列表
//     * @return mixed array
//     * @author Terry <wanghui@guanyisoft.com>
//     * @version 7.0
//     * @since stage 1.5
//     * @modify 2012-12-11
//     */
//    public function doAuthPaipai() {
//        //echo "<pre>";print_r("111");exit;
//    }

    /**
     * 将提交的授权写入数据库
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-11
     */
    public function setPaiPaiInfo() {
        $ary_res = array('success' => '0', 'msg' => '授权失败');
        //根据店铺授权请求店铺基本信息
        $str_platform = $this->_post('pf', 'htmlspecialchars', 'paipai');
        $obj_shops = D("ThdShops");
        $ary_post = $this->_post();
        $uin = $ary_post['uin'];
        $spid = $ary_post['spid'];
        $token = $ary_post['token'];
        $seckey = $ary_post['seckey'];
        if (empty($uin) || empty($spid) || empty($token) || empty($seckey)) {
            $this->error('拍拍信息 数据 spid uin token seckey 必填 ', U('Ucenter/Distribution/pageShops'));
            //$ary_res['msg'] = ' 拍拍信息 数据 spid uin token seckey 必填 ';
        } else {
            $obj_api = Apis::factory($str_platform, $ary_post);
            $str_shop_result = $arr_data = $obj_api->getShopInfo(array());
            $str_seller_result = '';
            //保存店铺的基本信息及授权信息
            $ary_member = session('Members');
            if ($str_shop_result['errorCode'] == 0) {
                unset($ary_post['pf']);
                $str_shop_result = json_encode($str_shop_result);
                $bln_result = $obj_shops->saveShop($ary_post, $str_shop_result, $str_seller_result, $str_platform, $ary_member['m_id']);
                if ($bln_result) {
                    session("ShopPai", $arr_data);
                    $this->success('店铺授权成功', array('确认' => U('Ucenter/Trdorders/yunerpShop')));
                } else {
                    $this->error('店铺授权失败', array('确认' => U('Ucenter/Distribution/pageShops')));
                }
            } else {
                $this->error('店铺授权失败', U('Ucenter/Distribution/pageShops'));
            }
        }
    }
	
	/**
     * 淘宝京东等平台订单下载
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.8.9
     */
	public function ajaxDoTaobaoOrdersDownload(){
		$home_access = D('SysConfig')->getCfgByModule('HOME_USER_ACCESS',1);
		$is_order_on = D('SysConfig')->getCfgByModule('IS_ORDER_ON',1);
		$exitTime = intval($home_access['EXPIRED_TIME']);
		if($exitTime > 0){
			@import('ORG.Util.Session');
			Session::setExpire(time() + $exitTime * 60);
		}
		@set_time_limit(0);
    	@ignore_user_abort(TRUE); // 设置与客户机断开是否会终止脚本的执行
    	$ary_post = $this->_post();
    	if (empty($ary_post)) {
    		//分页数组
    		$array_pageinfo = array();
    		$array_pageinfo['page_no'] = 1;
    		$array_pageinfo['page_size'] = 1;
    		$ary_get = $this->_get();
    		$tmp_taobao_items = $this->searchTopTradeList($ary_get,$array_pageinfo);
			$taobao_items = $tmp_taobao_items['str_shop_result'];
    		$total = 0;
    		if(!empty($taobao_items[0]['total_results'])){
    			$total = $taobao_items[0]['total_results'];
    		}
    		//获取分类总数
    		echo $total;exit;
    	} else {
    		//分页数组
    		$array_pageinfo = array();
    		$array_pageinfo['page_no'] = $ary_post['page_no'];
    		$array_pageinfo['page_size'] = $ary_post['page_size'];
    		$tmp_shop_result = $this->searchTopTradeList($ary_post,$array_pageinfo);
			$str_shop_result = $tmp_shop_result['str_shop_result'];
			$arr_result = $tmp_shop_result['arr_result'];
			$ThdOrders = D("ThdOrders");
			$thdmatch = M('thd_orders', C('DB_PREFIX'), 'DB_CUSTOM');
    		$ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0,'updRows' => 0,'updErrRows' => 0,'errData' => '');		
		    $member = session("Members");			
			if (!empty($str_shop_result) && is_array($str_shop_result)) {
    			//总共订单数
    			$total_num = count($str_shop_result[0]['total_results']);
    			//处理成功数据
    			$success_num = 0;
    			//处理失败条数
    			$fail_num = 0;	
				foreach ($str_shop_result as $ary_key => $ary_val) {
					$ThdOrders->startTrans();
					$result = $ThdOrders->getTrdordersTtid($ary_val['tt_id']);
					if (!empty($result)) {
						$ary_val['to_id'] = $result;
						$ary_val['ts_id'] = $arr_result['ts_id'];
						$bool_result = $ThdOrders->saveUpdateTrdordersOrder($ary_val, $member['m_id']);
						if (FALSE !== $bool_result) {
							$ThdOrders->commit();
							$ary_res['updRows'] = $ary_res['updRows']+1;
						}else{
							$ary_res['updErrRows'] = $ary_res['updErrRows']+1;
						}
					} else {
						$ary_val['ts_id'] = $arr_result['ts_id'];
						$set_save_data = array(
							'to_oid' => $ary_val['tt_id'],
							'to_source' => $ary_val['tt_source'],
							'to_buyer_id' => $ary_val['buyer'],
							'to_created' => $ary_val['created'],
							'to_modified' => $ary_val['modified'],
							'to_pay_time' => $ary_val['pay_time'],
							'to_post_fee' => $ary_val['post_fee'],
							'to_payment' => $ary_val['payment'],
							'to_receiver_address' => $ary_val['receiver_address'],
							'to_receiver_city' => $ary_val['receiver_city'],
							'to_receiver_district' => $ary_val['receiver_district'],
							'to_receiver_mobile' => $ary_val['receiver_mobile'],
							'to_receiver_name' => $ary_val['receiver_name'],
							'to_receiver_province' => $ary_val['receiver_state'],
							'to_receiver_zip' => $ary_val['receiver_zip'],
							'm_id' => $member['m_id'],
							'to_seller_title' => $ary_val['title'],
							'to_thd_status' => $ary_val['thd_status'],
							'to_buyer_message' => $ary_val['buyer_message'],
							'to_seller_memo' => $ary_val['seller_memo'],
							'ts_id' => $ary_val['ts_id'],
							'to_receiver_phone' => $ary_val['receiver_phone'],
							'to_seller_flag'=> $ary_val['seller_flag']
						);
						if($ary_val["to_pay_type"]){
							$set_save_data['to_pay_type'] = $ary_val["to_pay_type"];
						}
                        if (!empty($ary_val['orders']) && is_array($ary_val['orders'])) {
                            foreach ($ary_val['orders'] as $ary_order) {
                                $set_save_data['toi_titles'] .= $ary_order['title'].":";
                            }
                        }
						$to_id = $ThdOrders->add($set_save_data);
						if (FALSE !== $to_id) {
							$is_success = 1;
							if (!empty($ary_val['orders']) && is_array($ary_val['orders'])) {
								foreach ($ary_val['orders'] as $ary_order) {
									$result = $ThdOrders->getTrdordersTotid($ary_order['to_id'], $ary_val['tt_id']);
									if (empty($result)) {
										//第三方订单并作简单匹配
										$ary_order['toi_b2b_pdt_sn_info'] = '';
										//无sku商品订单下载是自动匹配
										if(empty($ary_order['outer_sku_id'])){
											$ary_order['outer_sku_id']=$ary_order['outer_iid'];
										}
										$thd_order_goods = $ThdOrders->getMatchTrdOrders($ary_order['outer_iid'], $ary_order['outer_sku_id'], $ary_order['num']);
										if (!empty($thd_order_goods) && is_array($thd_order_goods)) {
											$ary_order['toi_b2b_pdt_sn_info'] = serialize($thd_order_goods);
										}
										$set_data = array(
											'toi_id' => $ary_order['to_id'],
											'to_id' => $ary_val['tt_id'],
											'toi_num' => $ary_order['num'],
											'toi_num_id' => $ary_order['num_iid'],
											'toi_price' => $ary_order['price'],
											'toi_title' => $ary_order['title'],
											'toi_outer_id' => $ary_order['outer_iid'],
											'toi_outer_sku_id' => $ary_order['outer_sku_id'],
											'toi_spec_name' => $ary_order['sku_properties_name'],
											'toi_url' => isset($ary_order['url'])?$ary_order['url']:'',
											'toi_b2b_pdt_sn_info' => $ary_order['toi_b2b_pdt_sn_info']
										);
										$arr_res = M('ThdOrdersItems', C('DB_PREFIX'), 'DB_CUSTOM')->add($set_data);
										if (FALSE !== $arr_res) {
											//更新商品表
											if(!empty($ary_order['outer_sku_id'])){
												$obj_top_items = D('ThdTopItems');
												$ary_sku_data = array();
												$ary_sku_data['it_nick'] = $arr_result['ts_nick'];
												$ary_sku_data['num_iid'] = $ary_order['outer_iid'];
												$ary_sku_data['sku_id'] = $ary_order['outer_sku_id'];
												$is_exists = $obj_top_items->where($ary_sku_data)->find();
												if(empty($is_exists)) {
													//插入第三方表
													$ary_insert_data = array();
													$ary_insert_data['it_nick'] = $arr_result['ts_nick'];
													$ary_insert_data['g_id'] = !empty($thd_order_goods[1]['g_id']) ? $thd_order_goods[1]['g_id']:0;
													$ary_insert_data['num_iid'] = $ary_order['outer_iid'];
											  
													$ary_insert_data['pdt_id'] = !empty($thd_order_goods[1]['pdt_id']) ? $thd_order_goods[1]['pdt_id']:0;
													$ary_insert_data['sku_id'] = $ary_order['outer_sku_id'];
													if(!empty($ary_order['sku_properties_name'])){
														$ary_insert_data['spec_name'] =  $ary_order['sku_properties_name'];
													}
													$mixed_result = $obj_top_items->add($ary_insert_data); 
													if(!$mixed_result){
														//插入记录未成功
														@writeLog(date('Y-m-d').json_encode($ary_insert_data).'执行未成功','item_top.log');
													}
												}else{
													$ary_update_data = array();
													$ary_update_data['g_id'] = !empty($thd_order_goods[1]['g_id']) ? $thd_order_goods[1]['g_id']:0;
													$ary_update_data['pdt_id'] = !empty($thd_order_goods[1]['pdt_id']) ? $thd_order_goods[1]['pdt_id']:0;
													if(!empty($ary_order['sku_properties_name'])){
														$ary_update_data['spec_name'] =  $ary_order['sku_properties_name'];
													}
													$mixed_result = $obj_top_items->where(array('it_id'=>$is_exists['it_id']))->data($ary_update_data)->save(); 
									   
													if($mixed_result == false){
														//更新记录未成功
														@writeLog(date('Y-m-d').json_encode($ary_update_data).'执行未成功','item_top.log');
													}  
												}
											}
											//echo "写入成功";
											if (!empty($ary_order['toi_b2b_pdt_sn_info'])) {
												$res = $ThdOrders->where(array('to_oid' => $ary_val['tt_id']))->data(array('to_is_match' => '1'))->save();
												if (FALSE !== $res) {
													$ThdOrders->commit();
													//匹配上的话自动转化为本地订单,可失败
													if($is_order_on['IS_ORDER_ON'] == '1'){
														$this->saveTradeToLocal($ary_val['tt_id']);	
													}
												} else {
													$ThdOrders->rollback();
													$is_success = 0;
												}
											} else {
												$ThdOrders->commit();
											}
										} else {
											$is_success = 0;
											$ThdOrders->rollback();
										}
									}
								}
								if($is_success == 1){
									$success_num = $success_num+1;
								}else{
									$fail_num++;
									$ary_res['errMsg'] = $ary_val['tt_id'].':'.'订单添加失败-订单明细;';											
								}
							}
						} else {
							$fail_num++;//$ary_val['tt_id']
							$ary_res['errMsg'] .= $ary_val['tt_id'].':'.'订单添加失败-订单;';
							$ThdOrders->rollback();
						}
					}
					unset($str_shop_result[$ary_key]);
				}
				if($success_num>0){
					if($total_num != $success_num){
						$ary_res['succRows'] = $ary_res['succRows']+$success_num;
					}else{
						$ary_res['errRows'] = $ary_res['errRows']+$fail_num;
						$ary_res['succRows'] = $ary_res['succRows']+$success_num;
					}
				}else{
					$ary_res['errRows'] = $ary_res['errRows']+$fail_num;
				}
			} else {
				$ary_res['errMsg'] .='无订单可下载';
			}			
			echo json_encode($ary_res);
    		exit;
    	}
	}
	
	/**
     * 淘宝京东等平台订单转化为本地订单
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.9
     */		
	protected function saveTradeToLocal($tt_id){
		$ary_thd_order = D("ThdOrders")->where(array('to_oid' => $tt_id))->find();
		$ary_thd_order_items = D("ThdOrdersItems")->where(array('to_id' => $tt_id))->select();
		//获取物流信息
		$ary_cart = array();
		$pdt_num = 0;
		$pdt_price = 0;
		$pdt_weight = 0;
		//订单明细数据
		$ary_order_items = array();
		foreach($ary_thd_order_items as $tmp_item){
			$tmp_toi_b2b_pdt_sn_info = unserialize($tmp_item['toi_b2b_pdt_sn_info']);
			foreach($tmp_toi_b2b_pdt_sn_info as $tmp_pdt){
				$tmp_pdt['type'] = 0;
				$ary_cart[$tmp_pdt['pdt_id']] = $tmp_pdt;
				$pdt_num +=$tmp_pdt['num'];
				$ary_order_items[$tmp_item['toi_num_id']][$tmp_pdt['pdt_sn']] = array(
					'num_iid'=>$tmp_item['toi_num_id'],
					'pdt_sn'=>$tmp_pdt['pdt_sn'],
					'nums'=>$tmp_pdt['num'],
					'g_id'=>$tmp_pdt['g_id'],
					'pdt_id'=>$tmp_pdt['pdt_id'],
					'toi_id'=>$tmp_item['toi_id']
				);
			}
		}
		$pdt_weight = D('Orders')->getGoodsAllWeight($ary_cart,1);
		$post_log_data = array('tt_id'=>$tt_id,'address_id'=>0,'autoTrd'=>1,'province'=>$ary_thd_order['to_receiver_province'],'city'=>$ary_thd_order['to_receiver_city'],'district'=>$ary_thd_order['to_receiver_district'],'goods_info'=>array('pdt_num'=>$pdt_num,'pdt_price'=>$pdt_price,'pdt_weight'=>$pdt_weight));
		$ary_logistic_data = $this->getLogisticList($post_log_data);
		$ary_logistic = $ary_logistic_data['ary_res']['data'][0];
		unset($ary_logistic_data);
		$ary_trade_data = array('payment'=>1);//默认预存款支付
		$ary_trade_data['order'][$tt_id]['logistics'] = $ary_logistic['lt_id'];
		$ary_trade_data['order'][$tt_id]['o_remark'] = '';//卖家备注
		$ary_trade_data['order'][$tt_id]['log_conf'] = json_encode($ary_logistic['lt_expressions']);
		unset($ary_logistic);
		foreach($ary_order_items as $key=>$tmp){
			$ary_trade_data['order'][$tt_id][$key] = $tmp;
		}
		unset($ary_order_items);
		$ary_res = $this->doBatchAddTrdOrder($ary_trade_data);
		return $ary_res;
	}
	
	/**
     * 淘宝京东等平台订单下载
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.8.9
     */	
	protected function searchTopTradeList($ary_request,$ary_page){
        $member = session("Members");
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['ts_id'] = (int) $ary_request['tsid'];
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '1';
        if (!empty($ary_request['data']) && $ary_request['data'] == 'paipai') {
            $arr_where['ts_source'] = '2';
        }
		if (!empty($ary_request['data']) && $ary_request['data'] == 'jd') {
            $arr_where['ts_source'] = '3';
        }
        $arr_result = $obj_shops->where($arr_where)->find();
        if (!empty($ary_post['data'])) {
            $str_platform = $ary_post['data'];
        } else {
            $str_platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        }
        $ary_token = json_decode($arr_result['ts_shop_token'], true);
        $obj_api = Apis::factory($str_platform, $ary_token);
        //同步的开始时间默认为当前系统时间向前推20天
        $start_date = date("Y-m-d H:i:s", time() - 20 * 24 * 60 * 60);
		if(!empty($ary_request['order_minDate'])){
			$start_date = $ary_request['order_minDate'].':00';
		}
		if(!empty($ary_request['order_maxDate'])){
			$end_date = $ary_request['order_maxDate'].':00';
		}else{
			$end_date = date('Y-m-d H:i:s');
		}
		$str_shop_result = $obj_api->getOrdersList($start_date.'---'.$end_date,$int_total_nums, $ary_page['page_no'], $ary_page['page_size']);
		return array('arr_result'=>$arr_result,'str_shop_result'=>$str_shop_result);
	}
	
	/**
     * 淘宝京东等平台订单下载
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.4.5.1-7.8.2
     */
    public function doTaobaoOrdersDownload() {
		@set_time_limit(0);
    	@ignore_user_abort(TRUE); // 设置与客户机断开是否会终止脚本的执行
		@ini_set('memory_limit', '512M');
        $ary_post = $this->_post();
        $member = session("Members");
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['ts_id'] = (int) $ary_post['ts_id'];
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '1';
        if (!empty($ary_post['data']) && $ary_post['data'] == 'paipai') {
            $arr_where['ts_source'] = '2';
        }
		if (!empty($ary_post['data']) && $ary_post['data'] == 'jd') {
            $arr_where['ts_source'] = '3';
        }
        $arr_result = $obj_shops->where($arr_where)->find();
		//echo $obj_shops->getLastSql();exit;
		//dump($arr_result);die();
        if (!empty($ary_post['data'])) {
            $str_platform = $ary_post['data'];
        } else {
            $str_platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        }
        $ary_token = json_decode($arr_result['ts_shop_token'], true);
		//dump($ary_token);die();
        $obj_api = Apis::factory($str_platform, $ary_token);
        //同步的开始时间默认为当前系统时间向前推20天
        $start_date = date("Y-m-d H:i:s", time() - 20 * 24 * 60 * 60);
		$min_date = $ary_post['min'];
		$max_date = $ary_post['max'];
		if(!empty($ary_post['min'])){
			$start_date = $ary_post['min'].':00';
		}
		if(!empty($ary_post['max'])){
			$end_date = $ary_post['max'].':00';
		}else{
			$end_date = date('Y-m-d H:i:s');
		}
		/**
		if($arr_where['ts_source'] == '3'){
			$min_date = $ary_post['min'];
			$max_date = $ary_post['max'];
			if(!empty($ary_post['min'])){
				$start_date = $ary_post['min'].':00';
			}
			if(!empty($ary_post['max'])){
				$end_date = $ary_post['max'].':00';
			}else{
				$end_date = date('Y-m-d H:i:s');
			}		
			$str_shop_result = $obj_api->getOrdersList($start_date.'---'.$end_date);
		}else{
			 $str_shop_result = $obj_api->getOrdersList($start_date);
		}**/
		$str_shop_result = $obj_api->getOrdersList($start_date.'---'.$end_date);
		//dump($str_shop_result);die();
        $ThdOrders = D("ThdOrders");
        $thdmatch = M('thd_orders', C('DB_PREFIX'), 'DB_CUSTOM');
        
        $tableHtml = '<div class="myOrder shopManage"><table class="tableCon"><thead><tr><td width="200">店铺名称</td><td width="200">订单ID</td><td>状态</td></tr></thead>';
        if (!empty($str_shop_result) && is_array($str_shop_result)) {
            //下载前先标记第三方订单状态为空
			/**
            $arr_where['to_tt_status'] = '0';
            $arr_where['to_created'] = array('GT',$start_date);
			$arr_where['to_created'] = array('LT',$end_date);
			if($str_platform == 'jd'){
				$arr_where['to_source'] = '3';
			}
			if($str_platform == 'paipai'){
				$arr_where['to_source'] = '2';
			}		
            $ary_status = $ThdOrders->UpdateTrdordersStatus($arr_where);
            if (false === $ary_status) {
                //$ThdOrders->rollback();
            }**/
            foreach ($str_shop_result as $ary_key => $ary_val) {
				$ThdOrders->startTrans();
                $tableHtml .= "<tbody><tr>";
                $result = $ThdOrders->getTrdordersTtid($ary_val['tt_id']);
                if (!empty($result)) {
                    //echo "<pre>";var_dump($ary_val);
                    $ary_val['to_id'] = $result;
                    $ary_val['ts_id'] = $arr_result['ts_id'];
                    $bool_result = $ThdOrders->saveUpdateTrdordersOrder($ary_val, $member['m_id']);
                    if (FALSE !== $bool_result) {
						$ThdOrders->commit();
                        $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">更新成功</td></tr>';
                    }
                } else {
                    $ary_val['ts_id'] = $arr_result['ts_id'];
                    $set_save_data = array(
                        'to_oid' => $ary_val['tt_id'],
                        'to_source' => $ary_val['tt_source'],
                        'to_buyer_id' => $ary_val['buyer'],
                        'to_created' => $ary_val['created'],
                        'to_modified' => $ary_val['modified'],
                        'to_pay_time' => $ary_val['pay_time'],
                        'to_post_fee' => $ary_val['post_fee'],
                        'to_payment' => $ary_val['payment'],
                        'to_receiver_address' => $ary_val['receiver_address'],
                        'to_receiver_city' => $ary_val['receiver_city'],
                        'to_receiver_district' => $ary_val['receiver_district'],
                        'to_receiver_mobile' => $ary_val['receiver_mobile'],
                        'to_receiver_name' => $ary_val['receiver_name'],
                        'to_receiver_province' => $ary_val['receiver_state'],
                        'to_receiver_zip' => $ary_val['receiver_zip'],
                        'm_id' => $member['m_id'],
                        'to_seller_title' => $ary_val['title'],
                        'to_thd_status' => $ary_val['thd_status'],
                        'to_buyer_message' => $ary_val['buyer_message'],
                        'to_seller_memo' => $ary_val['seller_memo'],
                        'ts_id' => $ary_val['ts_id'],
                        'to_receiver_phone' => $ary_val['receiver_phone'],
						'to_seller_flag'=> $ary_val['seller_flag']
                    );
					if($ary_val["to_pay_type"]){
						$set_save_data['to_pay_type'] = $ary_val["to_pay_type"];
					}
                    $to_id = $ThdOrders->add($set_save_data);
                    if (FALSE !== $to_id) {
//                        $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">添加成功</td>';
                        if (!empty($ary_val['orders']) && is_array($ary_val['orders'])) {
                            foreach ($ary_val['orders'] as $ary_order) {
                                $result = $ThdOrders->getTrdordersTotid($ary_order['to_id'], $ary_val['tt_id']);
                                if (empty($result)) {
                                    //第三方订单并作简单匹配
                                    $ary_order['toi_b2b_pdt_sn_info'] = '';
									//无sku商品订单下载是自动匹配
									if(empty($ary_order['outer_sku_id'])){
										$ary_order['outer_sku_id']=$ary_order['outer_iid'];
									}
                                    $thd_order_goods = $ThdOrders->getMatchTrdOrders($ary_order['outer_iid'], $ary_order['outer_sku_id'], $ary_order['num']);
                                    if (!empty($thd_order_goods) && is_array($thd_order_goods)) {
                                        $ary_order['toi_b2b_pdt_sn_info'] = serialize($thd_order_goods);
                                    }
                                    $set_data = array(
                                        'toi_id' => $ary_order['to_id'],
                                        'to_id' => $ary_val['tt_id'],
                                        'toi_num' => $ary_order['num'],
                                        'toi_num_id' => $ary_order['num_iid'],
                                        'toi_price' => $ary_order['price'],
                                        'toi_title' => $ary_order['title'],
                                        'toi_outer_id' => $ary_order['outer_iid'],
                                        'toi_outer_sku_id' => $ary_order['outer_sku_id'],
                                        'toi_spec_name' => $ary_order['sku_properties_name'],
                                        'toi_url' => isset($ary_order['url'])?$ary_order['url']:'',
                                        'toi_b2b_pdt_sn_info' => $ary_order['toi_b2b_pdt_sn_info']
                                    );
                                    $arr_res = M('ThdOrdersItems', C('DB_PREFIX'), 'DB_CUSTOM')->add($set_data);
//                                   echo "<pre>";print_r(M('ThdOrdersItems',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
                                    if (FALSE !== $arr_res) {
										//更新商品表
										if(!empty($ary_order['outer_sku_id'])){
											$obj_top_items = D('ThdTopItems');
											$ary_sku_data = array();
											$ary_sku_data['it_nick'] = $arr_result['ts_nick'];
											$ary_sku_data['num_iid'] = $ary_order['outer_iid'];
											$ary_sku_data['sku_id'] = $ary_order['outer_sku_id'];
											$is_exists = $obj_top_items->where($ary_sku_data)->find();
											if(empty($is_exists)) {
												//插入第三方表
												$ary_insert_data = array();
												$ary_insert_data['it_nick'] = $arr_result['ts_nick'];
												$ary_insert_data['g_id'] = !empty($thd_order_goods[1]['g_id']) ? $thd_order_goods[1]['g_id']:0;
												$ary_insert_data['num_iid'] = $ary_order['outer_iid'];
										  
												$ary_insert_data['pdt_id'] = !empty($thd_order_goods[1]['pdt_id']) ? $thd_order_goods[1]['pdt_id']:0;
												$ary_insert_data['sku_id'] = $ary_order['outer_sku_id'];
												if(!empty($ary_order['sku_properties_name'])){
													$ary_insert_data['spec_name'] =  $ary_order['sku_properties_name'];
												}
												$mixed_result = $obj_top_items->add($ary_insert_data); 
												if(!$mixed_result){
													//插入记录未成功
													@writeLog(date('Y-m-d').json_encode($ary_insert_data).'执行未成功','item_top.log');
												}
											}else{
												$ary_update_data = array();
												$ary_update_data['g_id'] = !empty($thd_order_goods[1]['g_id']) ? $thd_order_goods[1]['g_id']:0;
												$ary_update_data['pdt_id'] = !empty($thd_order_goods[1]['pdt_id']) ? $thd_order_goods[1]['pdt_id']:0;
												if(!empty($ary_order['sku_properties_name'])){
													$ary_update_data['spec_name'] =  $ary_order['sku_properties_name'];
												}
												$mixed_result = $obj_top_items->where(array('it_id'=>$is_exists['it_id']))->data($ary_update_data)->save(); 
								   
												if($mixed_result == false){
													//更新记录未成功
													@writeLog(date('Y-m-d').json_encode($ary_update_data).'执行未成功','item_top.log');
												}  
											}
										}
                                        //echo "写入成功";
                                        if (!empty($ary_order['toi_b2b_pdt_sn_info'])) {
                                            $res = $ThdOrders->where(array('to_oid' => $ary_val['tt_id']))->data(array('to_is_match' => '1'))->save();

                                            if (FALSE !== $res) {
                                                $ThdOrders->commit();
                                                $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">添加成功</td></tr>';
                                            } else {
                                                $ThdOrders->rollback();
                                                $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">下载失败</td></tr>';
                                            }
                                        } else {
                                            $ThdOrders->commit();
                                            $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">添加成功</td></tr>';
                                        }
                                    } else {
                                        $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">下载失败</td></tr>';
                                        $ThdOrders->rollback();
                                    }
                                }
                            }
                        }
                    } else {
                        $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:red;">下载订单失败</td></tr>';
                        $ThdOrders->rollback();
                    }
                }
                $tableHtml .= "</tbody>";
				unset($str_shop_result[$ary_key]);
            }
        } else {
            $tableHtml .= '<tbody><tr><td colspan="3">无订单可下载</td></tr></tbody>';
        }
        $tableHtml .= "</table></div>";
        echo $tableHtml;
        exit;
    }
	
	/**
     * 更新淘宝发货状态
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.4.5.1
     * @modify 2014-04-09
	 * @update by Wangguibin <wangguibin@guanyisoft.com>
	 * @version 7.8.2
	 * @更新京东发货状态
     */
	public function UpdataTaobaoOrdersStatus() {
        $ary_post = $this->_post();
        $member = session("Members");
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['ts_id'] = (int) $ary_post['ts_id'];
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '1';
        if (!empty($ary_post['data']) && $ary_post['data'] == 'paipai') {
            $arr_where['ts_source'] = '2';
        }
        if (!empty($ary_post['data']) && $ary_post['data'] == 'jd') {
            $arr_where['ts_source'] = '3';
        }		
        $arr_result = $obj_shops->where($arr_where)->find();
        if (!empty($ary_post['data'])) {
            $str_platform = $ary_post['data'];
        } else {
            $str_platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        }
        $ary_token = json_decode($arr_result['ts_shop_token'], true);
        $obj_api = Apis::factory($str_platform, $ary_token);
        //同步的开始时间默认为当前系统时间向前推20天
        $start_date = date("Y-m-d H:i:s", time() - 20 * 24 * 60 * 60);
        $str_shop_result = $obj_api->UpdataTaobaoOrdersStatus($start_date);
        $ThdOrders = D("ThdOrders");
        $tableHtml = '<div class="myOrder shopManage"><table class="tableCon"><thead><tr><td width="200">店铺名称</td><td width="200">订单ID</td><td>状态</td></tr></thead>';
        if (!empty($str_shop_result) && is_array($str_shop_result)) {
            foreach ($str_shop_result as $ary_key => $ary_val) {
                $tableHtml .= "<tbody><tr>";
                $result = $ThdOrders->getTrdordersTtid($ary_val['tt_id']);
                if (!empty($result)) {
                    $ary_val['to_id'] = $result;
                    $ary_val['ts_id'] = $arr_result['ts_id'];
                    $bool_result = $ThdOrders->saveUpdateTrdordersStatus($ary_val, $member['m_id']);
                    if ($bool_result) {
                        $tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:green;">更新成功</td></tr>';
                    } else {
                        //$tableHtml .= '<td width="200">' . $arr_result['ts_title'] . '</td><td width="200">' . $ary_val['tt_id'] . '</td><td style="color:red;">更新失败</td></tr>';
                    }
                }
                $tableHtml .= "</tbody>";
            }
        } else {
            $tableHtml .= '<tbody><tr><td colspan="3">无订单可更新</td></tr></tbody>';
        }
        $tableHtml .= "</table></div>";
        echo $tableHtml;
        exit;
    }

    /**
     * 授权页，授权后进入订单列表
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-11
     */
    public function doMark() {
        ;
    }

    /**
     * 获取商品数据
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-9
     */
    public function pageProducts() {
        $filter = $this->_get();
        $goods = D("ViewGoods");
        $condition = array();
        //拼接搜索条件
        $ary_params = array();
        //商品分类
        $ary_params['gcid'] = (int) $this->_get('gcid', 'htmlspecialchars', 0);
        //商品名称
        $ary_params['g_name'] = $this->_get('g_name', 'htmlspecialchars,trim', '');
        //商品编码
        $ary_params['g_sn'] = $this->_get('g_sn', 'htmlspecialchars,trim', '');
        //货号
        $ary_params['pdt_sn'] = $this->_get('pdt_sn', 'htmlspecialchars,trim', '');
        $where = array();
        //商品分类搜索
        if ($ary_params['gcid']) {
            $gcid = array('in', $goods->getCatesIds($ary_params['gcid']));

            $array_where = array();
            $gcids = implode(",", $gcid[1]);
            $array_where['gc_id'] = array($gcid[0], $gcids);
            $array_gids = D("RelatedGoodsCategory")->distinct(true)->where($array_where)->getField("g_id", true);
            if (is_array($array_gids) && count($array_gids) > 0) {
                $where["g_id"] = array("IN", $array_gids);
            } else {
                //表示找不到商品数据
                $where["g_id"] = array("IN", array(-1));
            }
            unset($gcid);
            //$where['gc_id'] = array('in', $goods->getCatesIds($ary_params['gcid']));
        }
        //商品名称查询
        if ($ary_params['g_name']) {
            $where['g_name'] = array('LIKE', '%' . $ary_params['g_name'] . '%');
        }
        //商品编码
        if (!empty($ary_params['g_sn'])) {
            $filde = array(' ', '\\s', '\\n', '\\r\\n', '&nbsp;', '\\r');
            $str_g_sn = str_replace($filde, ',', $ary_params['g_sn']);
            if (strpos($str_g_sn, ',') === false) {
                $where['g_sn'] = array('LIKE', '%' . $str_g_sn . '%');
            } else {
                $where['g_sn'] = array('in', $str_g_sn);
            }
        }
		$where['g_on_sale'] = 1;
        //货号
        if (!empty($ary_params['pdt_sn'])) {
            $filde = array(' ', '\\s', '\\n', '\\r\\n', '&nbsp;', '\\r');
            $str_pdt_sn = str_replace($filde, ',', $ary_params['pdt_sn']);
            //$str_pdt_sn = str_replace(array(' ', '\\s', '\\n', '\\r\\n', '&nbsp;'), ',', $ary_params['pdt_sn']);
            if (strpos($str_pdt_sn, ',') === false) {
                $condition['pdt_sn'] = array('LIKE', '%' . $str_pdt_sn . '%');
            } else {
                $condition['pdt_sn'] = array('in', $str_pdt_sn);
            }
			$gsns = D('GoodsProducts')->where($condition)->field('g_sn')->select();
			if(!empty($gsns)){
				foreach($gsns as $gsn){
					$str_g_sn .= ','.$gsn['g_sn'].',';
				}
				$str_g_sn = trim($str_g_sn,',');
			}
			if(!empty($str_g_sn)){
				 if (strpos($str_g_sn, ',') === false) {
					$where['g_sn'] = array('LIKE', '%' . $str_g_sn . '%');
				} else {
					$where['g_sn'] = array('in', $str_g_sn);
				}
			}
        }
        $goodsModel = D("UcenterQuickOrderGoodsView");
        //获取商品 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $count = $goodsModel->where($where)->count('goods.g_id');
		$this->assign('nums', $count);
        //echo "<pre>";print_r($where);exit;
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $list = $goodsModel->where($where)->order(array('g_id' => 'desc'))->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
		//获取货品
        $products = D("GoodsProductsTable");
        $goodsSpec = D('GoodsSpec');
        $authorLine = D('AuthorizeLine');
        //获取货品价格等额外数据
        $member = session('Members');
        $price = new PriceModel($member['m_id']);
        foreach ($list as $key => $data) {
            $list[$key]['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $data['g_id']);
            $condition['g_id'] = $data['g_id'];
            //$list[$key]['isauthor'] = $authorLine->isAuthorize($member['m_id'],$data['g_id']);
            $ary_pdt = $products->where(array('g_id' => $data['g_id']))->select();
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                foreach ($ary_pdt as $k => $pdt) {
					$ary_pdt[$k]['pdt_stock'] = $pdt['pdt_stock'] < 0 ? 0 : $pdt['pdt_stock'];
                    //获取其他属性
                    $ary_pdt[$k]['specName'] = $goodsSpec->getProductsSpec($pdt['pdt_id']);
                    $ary_pdt[$k]['gPrice'] = $price->getMemberPrice($pdt['pdt_id']);
                }
                $list[$key]['products'] = $ary_pdt;
            }
        }
//        echo "<pre>";print_r($list);exit;
        $this->assign('search', $filter);
        $this->assign('filter', $ary_params);
        $this->assign('lists', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->display();
    }

    /**
     * 保存第三方手工匹配的货品到数据库
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-11
     */
    public function saveTrdMatchedProductsToDb() {
        $ary_res = array('success' => 0, 'msg' => '匹配失败！');
        $ThdOrders = D("ThdOrders");
        $ary_filter = $this->_post();
        //拼接搜索条件
        $where = array();
        $data = array();
        //淘宝商品编码
        $ary_params['num_iid'] = $this->_post('num_iid', 'htmlspecialchars', 0);
        //第三方订单ID
        $ary_params['to_id'] = $this->_post('tt_id', 'htmlspecialchars,trim', '');
        //第三方主键ID
        $ary_params['toi_id'] = $this->_post('to_id', 'htmlspecialchars,trim', '');
        //是否新添加商品
        $ary_params['o_type'] = $this->_post('o_type', 'htmlspecialchars,trim', '');
        //匹配的货品以及数量
        $data['b2b_pdt_sn_info'] = $ary_filter['b2b_pdt_sn_info']['pdt_sn'];
        //淘宝商品编码
        if ($ary_params['num_iid']) {
            $where['toi_num_id'] = $ary_params['num_iid'];
        }
        //第三方订单ID
        if ($ary_params['to_id']) {
            $where['to_id'] = $ary_params['to_id'];
        }
        //第三方主键ID
        if ($ary_params['toi_id']) {
            $where['toi_id'] = $ary_params['toi_id'];
        }
//        if (count($data['b2b_pdt_sn_info']) > 1) {
//            $ary_res['success'] = '0';
//            $ary_res['msg'] = '只能匹配一个货品';
//        } else {
        //需要更新的数据

        if (!empty($data['b2b_pdt_sn_info']) && is_array($data['b2b_pdt_sn_info'])) {
            $ary_data = array();
            $pdt_sn = array_keys($data['b2b_pdt_sn_info']);
            $pdt_num = array_values($data['b2b_pdt_sn_info']);
            foreach ($pdt_sn as $key => $val) {
                $ary_data[$key]['pdt_sn'] = (string)$val;
                $ary_data[$key]['num'] = $pdt_num[$key];
            }
            $data['toi_b2b_pdt_sn_info'] = serialize($ary_data);
//                echo "<pre>";print_r($ary_data);exit;
//                //目前只能匹配一个
//                foreach ($ary_data as $val) {
//                    $data['toi_b2b_pdt_sn_info'] .= serialize($val);
//                }
             if(!empty($ary_params['o_type']) && $ary_params['o_type']=="1"){
                $goods_arr = array();
                foreach($pdt_sn as $key => $val){
                    $goods_arr[$key] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field('g_sn,pdt_sn')->where(array('pdt_sn'=>$val))->find();
                }
            }
        }
        //判断是否给订单新添加商品
        if(!empty($ary_params['o_type']) && $ary_params['o_type']=="1"){
            if(!empty($goods_arr) && is_array($goods_arr)){
                foreach($goods_arr as $k => $gs){
                    $toi_b2b_pdt_sn_info = serialize(array($ary_data[$k]));
                    if($gs['pdt_sn'] == $ary_data[$k]['pdt_sn']){
						//判断是不是存在此规格的商品
						$is_have = M('thd_orders_items',C('DB_PREFIX'),'DB_CUSTOM')->
						where(array('toi_outer_id' => $gs['g_sn'],'toi_outer_sku_id'=>$gs['pdt_sn'],'to_id' => $ary_params['to_id']))->
						count();
						if($is_have>0){
							$ary_res['success'] = '0';
							$ary_res['msg'] = '商品存在无需再次新增';
							echo json_encode($ary_res);exit;
						}else{
						    $goodsModel = D("UcenterQuickOrderGoodsView");
							$goods = $goodsModel->field('g_name,g_id,g_picture,g_price')->where(array('g_sn'=>$gs['g_sn']))->find();
							$ary_result = array(
								'to_id' => $ary_params['to_id'],
								'toi_num' => $ary_data[$k]['num'],
								'toi_num_id' => '',
								'toi_price' => $goods['g_price'],
								'toi_title' => $goods['g_name'],
								'toi_outer_id' => $gs['g_sn'],
								'toi_outer_sku_id' => $gs['pdt_sn'],
								'sku_properties_name' => '',
								'toi_url' => $goods['g_picture'],
								'toi_b2b_pdt_sn_info' => $toi_b2b_pdt_sn_info,
								'to_is_match' => 1
							);
							$ary_result = $ThdOrders->saveAddTrdordersOrderItem($ary_result, $ary_params['to_id']);
						}
					}
                }
				$ary_res['success'] = '1';
				$ary_res['msg'] = '新增商品成功';
				echo json_encode($ary_res);exit;
            }
        }else{
            //dump($ary_params);dump($data);exit("*****222****");
            $ary_result = $ThdOrders->updateTrdordersOrderItem($where, $data);
        }
        if ($ary_result) {
            $ary_res['success'] = '1';
            $ary_res['msg'] = '匹配成功';
        }else{
            $ary_res['success'] = '0';
            $ary_res['msg'] = '匹配失败';
        }
//        }
        echo json_encode($ary_res);exit;
    }

    /**
     * 删除C2C订单
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-11
     */
    public function saveTrdordersDel() {
        $ary_res = array('success' => '0', 'msg' => '删除失败', 'errCode' => '1000');
        $ThdOrders = D("ThdOrders");
        //拼接搜索条件
        $where = array();
        $data = array();
        //淘宝商品编码
        $ary_params['num_iid'] = $this->_post('num_iid', 'htmlspecialchars', 0);
        //判断是否直接删除新添加的商品
        $ary_params['o_del'] = $this->_post('o_del', 'htmlspecialchars', 0);
        //第三方订单ID
        $ary_params['to_id'] = $this->_post('tt_id', 'htmlspecialchars,trim', '');
        //第三方主键ID
        $ary_params['toi_id'] = $this->_post('to_id', 'htmlspecialchars,trim', '');
        $data['toi_status'] = $this->_post('status', 'htmlspecialchars,trim', '');
        //淘宝商品编码
        if ($ary_params['num_iid']) {
            $where['toi_num_id'] = $ary_params['num_iid'];
        }
        //第三方订单ID
        if ($ary_params['to_id']) {
            $where['to_id'] = $ary_params['to_id'];
        }
        //第三方主键ID
        if ($ary_params['toi_id']) {
            $where['toi_id'] = $ary_params['toi_id'];
        }
        //判断是否物理删除新添加的商品
        if(!empty($ary_params['o_del']) && $ary_params['o_del']==1 && empty($ary_params['num_iid'])){
            $ary_result = $ThdOrders->deleteTrdordersOrderItem($where);
        }else{
            $ary_result = $ThdOrders->updateTrdordersOrderItem($where, $data);
        }
        if ($ary_result) {
            $ary_res['success'] = '1';
            $ary_res['msg'] = '成功';
        }
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 标记处理
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-11
     */
    public function saveTrdordersOrderHandle() {
        $ary_res = array('success' => '0', 'msg' => '标记处理失败', 'errCode');
        $ary_params = array();
        $where = array();
        //第三方订单ID
        $ary_params['tt_id'] = $this->_post('tt_id', 'htmlspecialchars', 0);
		$ary_params['tt_status'] = (int)$this->_post('tt_status');
		$ary_params['tsid'] = (int)$this->_post('tsid');
		$ary_params['source_id'] = (int)$this->_post('source_id');
        if ($ary_params['tt_id']) {
            $where['to_oid'] = $ary_params['tt_id'];
        }
        $ThdOrders = D("ThdOrders");
        $data = array('to_tt_status' => $ary_params['tt_status']);
        $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
        if ($ary_result) {
			if($ary_params['source_id']==1){
				$this->success('操作成功', array('确认' => U('Ucenter/Trdorders/pageTaobao', array('tsid' => $ary_params['tsid']))));
			}else{
				$this->success('操作成功', array('确认' => U('Ucenter/Trdorders/pagePaipai', array('tsid' => $ary_params['tsid']))));
			}
        } else {
            $this->error($ary_res['msg']);
        }
    }

    /**
     * 标记处理
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-11
     */
    public function saveBatchTrdordersOrderHandle() {
        $ary_res = array('success' => '0', 'msg' => '标记处理失败', 'errCode');
        $erri = 0;
        $succi = 0;
        $ThdOrders = D("ThdOrders");
        //第三方订单ID
        $ary_ttid = explode(",", trim($this->_post('tt_id', 'htmlspecialchars', 0), ","));
		$tt_status = (int)$this->_post('tt_status');
		$tsid = (int)$this->_post('tsid');
		$source_id = (int)$this->_post('source_id');
        if (!empty($ary_ttid) && is_array($ary_ttid)) {
            foreach ($ary_ttid as $vttid) {
                if ($vttid) {
                    $where['to_oid'] = $vttid;
                }
                $data = array('to_tt_status' => $tt_status);
                $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                if ($ary_result) {
                    $succi++;
                } else {
                    $erri++;
                }
            }
            $ary_res['msg'] = '处理成功 <font color="green">' . $succi . '</font> 条<br />处理失败 <font color="red">' . $erri . "</font> 条";
			if($source_id==1){
				$this->success($ary_res['msg'], array('确认' => U('Ucenter/Trdorders/pageTaobao', array('tsid' => $tsid))));
			}else{
				$this->success($ary_res['msg'], array('确认' => U('Ucenter/Trdorders/pagePaipai', array('tsid' => $tsid))));
			}
        } else if (!empty($ary_ttid) && $ary_ttid > 0) {
            if ($ary_ttid) {
                if ($ary_ttid) {
                    $where['to_oid'] = $ary_ttid;
                }
                $data = array('to_tt_status' => $tt_status);
                $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                if ($ary_result) {
					if($source_id==1){
						$this->success('标记处理成功', array('确认' => U('Ucenter/Trdorders/pageTaobao', array('tsid' => $tsid))));
					}else{
						$this->success('标记处理成功', array('确认' => U('Ucenter/Trdorders/pagePaipai', array('tsid' => $tsid))));
					}
                } else {
                    $this->error($ary_res['msg']);
                }
            }
        }
    }
	
    /**
     * 获取可用的物流列表
     * @return mixed array
	 *address_id	0
	  autoTrd	1
	  city	杭州市
	  district	上城区
	  goods_info[pdt_num]	2
	  goods_info[pdt_price]	425.7
	  goods_info[pdt_weight]	1200.00
	  province	浙江省
	  tt_id	1510603398212125
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.9
     * @modify 2015-12-29
     */	
	protected function getLogisticList($ary_post){
		$member = session("Members");
		$ary_res = array('data' => array(), 'success' => '0', 'msg' => '读取物流列表失败');	
        //会员等级信息
        $User_Grade = D('MembersLevel')->getMembersLevels($member['ml_id']); 
        //获取第三方订单信息
        $ThdOrders = D("ThdOrders");
        //第三方订单ID
        $ary_params['to_id'] = $ary_post['tt_id'];
        if ($ary_params['to_id']) {
            $where['to_oid'] = $ary_params['to_id'];
        }
		$ary_result = $ThdOrders->getTrdordersData($where);
		if(isset($ary_result['to_temp_receiver_address']) && !empty($ary_result['to_temp_receiver_address'])){
			$ary_temp_receiver_address = json_decode($ary_result['to_temp_receiver_address'], true);
			$ary_result['to_receiver_province'] = $ary_temp_receiver_address['to_receiver_province'];
			$ary_result['to_receiver_city'] = $ary_temp_receiver_address['to_receiver_city'];
			$ary_result['to_receiver_district'] = $ary_temp_receiver_address['to_receiver_district'];
			$ary_result['to_receiver_address'] = $ary_temp_receiver_address['to_receiver_address'];
		}
        $ary_result['address'] = $ary_result['to_receiver_province'] . "&nbsp;" . $ary_result['to_receiver_city'] . "&nbsp;" . $ary_result['to_receiver_district'] . "&nbsp;" . $ary_result['to_receiver_address'];	
		$city = D("CityRegion");
        $ary_city = $city->getCurrLvItem(1);
        //根据第三方订单的地址匹配本地的地址库
        if ($ary_post['address_id'] > 0) {
            $cid = $ary_post['address_id'];
            $city_region_data = $city->getFullAddressId($cid);
        } else {
            $cid = $city->getAvailableLogisticsList($ary_result['to_receiver_province'], $ary_result['to_receiver_city'], $ary_result['to_receiver_district']);
            $city_region_data = $city->getFullAddressId($cid);
        }	
        if ($cid < 1) {
            $ary_res['success'] = 2;
            $ary_res['msg'] = '';
        } else {
            $ary_available_logistics = $city->getAvailableListById($cid);
            if (count($ary_available_logistics) < 1) {
                $ary_res['success'] = 3;
                $ary_res['msg'] = '暂无匹配的物流公司';
            } else {
                foreach ($ary_available_logistics as $key=>$val) {
                    if($val['lc_is_enable'] == 0){
                        unset($ary_available_logistics[$key]);
                    }
                }
                foreach ($ary_available_logistics as &$ary_logistics) {
                    
                    $ary_goods_configure = $ary_post['goods_info'];
                    $fl_logistics_cost = $city->getActualLogisticsFreight($ary_logistics['lt_expressions'], $ary_goods_configure['pdt_num'], $ary_goods_configure['pdt_weight'], $ary_goods_configure['pdt_price']);
					
                    //判断会员等级是否包邮
                    if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                        $fl_logistics_cost = 0;
                    }
                    //判断是否设置包邮额度
                    $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $ary_logistics['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
                    $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                    if(!empty($lt_expressions['logistics_configure']) && $ary_goods_configure['pdt_price'] >= $lt_expressions['logistics_configure']){
                        $fl_logistics_cost = 0;
                    }
                    $ary_logistics['lt_expressions']['total_freight_cost'] = $fl_logistics_cost;
                    $ary_logistics['dca_configure_json'] = htmlspecialchars(json_encode($ary_logistics['lt_expressions']));
				}
                $ary_res['data'] = $ary_available_logistics;
                $ary_res['msg'] = '获取配送区域成功';
            }
        }
		return array('ary_city'=>$ary_city,'ary_result'=>$ary_result,'ary_logistics'=>$ary_logistics,'ary_res'=>$ary_res);
	}
    /**
     * 获取可用的物流列表
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-12
     */
    public function getAvailableLogisticsList() {
        $ary_post = $this->_post();
        $return_data = $this->getLogisticList($ary_post);
		$ary_city = $return_data['ary_city'];
		$ary_result = $return_data['ary_result'];
		$ary_logistics = $return_data['ary_logistics'];
		$ary_res = $return_data['ary_res'];
        $this->assign("ary_post", $ary_post);
        $this->assign("tt_id", $ary_post['tt_id']);
        $this->assign("city", $ary_city);
        $this->assign("ary_result", $ary_result);
        $this->assign("ary_logistics", $ary_logistics);
        $this->assign("res", $ary_res);
        //$this->display();exit;
        //echo "<pre>";print_r($ary_res);exit;
        if (($ary_post['address_id'] > 0 || $ary_post['type'] == 'trd') && !isset($ary_post['autoTrd'])) {
            $strHtml = '<dl class="dl02">';
            if (!empty($ary_res['data']) && is_array($ary_res['data'])) {
                foreach ($ary_res['data'] as $key => $data) {
                    $strHtml .='<dd><input type="radio" onClick="checkLogistic(' . $data['lt_id'] . ');" value="' . $data['lt_id'] . '" id="kuaidi" total_freight_cost="' . $data['lt_expressions']['total_freight_cost'] . '" weight="' . $data['lt_expressions']['total_freight_cost'] . '" tt_id="' . $ary_post['tt_id'] . '" lc_name="' . $data['lc_name'] . '" title="' . $data['lc_name'] . '"  class="selectLog" name="lt_id"><label for="kuaidi">' . $data['lt_name'] . '</label><span>运费 + <i id="logistic_price_' . $data['lt_id'] . '">' . $data['lt_expressions']['total_freight_cost'] . '</i>元</span>&nbsp;&nbsp;&nbsp;&nbsp;<span>' . $data['lt_expressions_text'] . '</span></dd>';
                }
            } else {
                $strHtml .='<dd>无配送区域</dd>';
            }

            $strHtml .='</dl>';
            echo $strHtml;
        } else {
            $this->display();
        }
    }

    /**
     * 淘宝订单状态更新
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-12
     */
    public function getTrdOrderTaobaoStatus($status) {
        $ary_res = array('success' => '0', 'msg' => '');
        $order_status = array(
            'WAIT_BUYER_CONFIRM_GOODS' => '卖家已发货',
            'TRADE_BUYER_SIGNED' => '买家已签收',
            'TRADE_FINISHED' => '交易成功',
            'TRADE_CLOSED_BY_TAOBAO' => '交易被关闭',
            'TRADE_CLOSED' => '退款成功'
        );
        foreach ($order_status as $key => $val) {
            if ($status == $key) {
                $ary_res['success'] = '1';
                $ary_res['msg'] = $order_status[$status];
            }
        }
        return $ary_res;
    }

    /**
     * 拍拍订单状态更新
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-12
     */
    public function getTrdOrderStatus($status) {
        $ary_res = array('success' => '0', 'msg' => '');
        $order_status = array(
            'DS_WAIT_BUYER_RECEIVE' => '卖家已发货',
            'DS_DEAL_CANCELLED' => '订单取消',
            'DS_DEAL_SHIPPING_PREPARE' => '卖家配货中',
            'DS_DEAL_END_NORMAL' => '交易成功',
            'DS_BUYER_EVALUATED' => '买家已评价',
            'DS_REFUND_WAIT_BUYER_DELIVERY' => '等待买家发送退货',
            'DS_REFUND_WAIT_SELLER_RECEIVE' => '等待卖家确认收货',
            'DS_REFUND_WAIT_SELLER_AGREE' => '等待卖家同意退款',
            'DS_REFUND_OK' => '退款成功',
            'DS_REFUND_ALL_WAIT_SELLER_AGREE' => '等待卖家同意全额退款',
            'DS_REFUND_WAIT_MODIFY' => '等待买家修改退款申请',
            'DS_REFUND_ALL_OK' => '全额退款成功',
            'DS_TIMEOUT_BUYER_RECEIVE' => '等待买家确认收货超时',
            'DS_TIMEOUT_SELLER_RECEIVE' => '等待卖家确认收货超时',
            'DS_CLOSED' => '订单已关闭',
            'DS_TIMEOUT_SELLER_PASS_REFUND_ALL' => '等待卖家确认全额退款超时',
            'DS_TIMEOUT_SELLER_PASS_RETURN' => '等待卖家响应买家退货请求超时',
            'STATE_COD_SHIP_OK' => '货到付款已发货',
            'STATE_COD_SIGN' => '货到付款已签收',
            'STATE_COD_REFUSE' => '货到付款拒签'
        );
        foreach ($order_status as $key => $val) {
            if ($status == $key) {
                $ary_res['success'] = '1';
                $ary_res['msg'] = $val;
            }
        }
        return $ary_res;
    }

    /**
     * C2C添加购物车
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-12
     */
    public function addTrdOredrToShoppingCart() {
        $ary_res = array('success' => '0', 'msg' => '添加购物车失败', 'data' => array());
        $member = session('Members');
        //$ary_post = $this->_post();
        $ary_post = $this->_post();
//        echo "<pre>";print_r($ary_post);exit;
        if (empty($ary_post) && is_array($ary_post)) {
            return array();
        }
        //获取第三方订单信息
        $ThdOrders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_id'] = $ary_post['ts_id'];
        $arr_where['ts_source'] = '1';
        if (!empty($ary_post['pf']) && $ary_post['pf'] == 'paipai') {
            $arr_where['ts_source'] = '2';
        }
        $arr_result = $obj_shops->where($arr_where)->find();
        $ary_token = json_decode($arr_result['ts_shop_token'], true);
        $obj_api = Apis::factory($ary_post['pf'], $ary_token);
        if (!empty($ary_post['pf']) && $ary_post['pf'] == 'paipai') {
            $order_detial = $obj_api->getThdTradeDetial(array('tt_id' => $ary_post['order']['tt_id']));
            $ary_status = $this->getTrdOrderStatus($order_detial['dealState']);
        } else {
            $order_detial = $obj_api->getThdTradeDetial(array('tt_id' => $ary_post['order']['tt_id']));
            $ary_status = $this->getTrdOrderTaobaoStatus($order_detial['data']['status']);
            //$ary_status = $this->getTrdOrderStatus($order_detial['dealState']);
        }
        if (!empty($ary_status['success']) && $ary_status['success'] == '1') {
            $ary_res['success'] = '0';
            $ary_res['msg'] = $ary_status['msg'];
            $ThdOrders->where(array('to_oid' => $ary_post['order']['tt_id']))->data(array('to_tt_status' => '1'))->save();
        } else {
            $ary_products = $ary_post['order']['products'];
            $str_tt_id = $ary_post['order']['tt_id'];
            $ary_tmp_trd_cart = array();
            //获取货品价格等额外数据
            //$price = new PriceModel($member['m_id']);
            //$goodsSpec = D('GoodsSpec');
            //unset($_SESSION['trdShoppingCart']['products']);
            foreach ($ary_products as &$pdts) {
                foreach ($pdts as &$pdt) {
                    if (!array_key_exists($pdt['pdt_id'], $ary_tmp_trd_cart)) {
                        $ary_tmp_trd_cart[$pdt['pdt_id']] = $pdt;
                    } else {
                        $ary_tmp_trd_cart[$pdt['pdt_id']]['nums'] += $pdt['nums'];
                    }
                    $ary_tmp_trd_cart[$pdt['pdt_id']] = $pdt;
                }
            }
            //echo "<pre>";print_r($ary_tmp_trd_cart);exit;
            $shopCart = session("trdShoppingCart");
            $shopCart['products'] = $ary_tmp_trd_cart;
            $shopCart['tt_id'] = $str_tt_id;
            session("trdShoppingCart", $shopCart);
            $ary_res['success'] = 1;
            $ary_res['msg'] = '加入购物车成功！';
        }
        echo json_encode($ary_res);
        exit;
    }

    /**
     * C2C购物车列表
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-13
     */
    public function showShopscartList() {
        $this->getSubNav(2, 2, 40);
        $member = session("Members");
        $Cart = D('Cart');
        //价格信息
        $ary_price_data = array();
        $priceData = array();
        //取出购物车中的数据
        $shopCart = session("trdShoppingCart");
//        echo "<pre>";print_r($shopCart);exit;
        $price = new PriceModel($member['m_id']);
        //获取商品的库存和最终价格信息
        foreach ($shopCart['products'] as &$pdt) {
            $pdt['final_price'] = $price->getMemberPrice($pdt['pdt_id']);
            $priceData[$pdt['pdt_id']] = $pdt['nums'];
            //总价
            $ary_price_data['all_price'] += ($pdt['final_price'] * $pdt['nums']);
        }
        //购买货品的总价
        $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $Cart->getAllPrice($priceData));
        //优惠的价格
        $ary_price_data['pre_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
        //货品信息
        $ary_cart_data = $Cart->getProductInfo($priceData);
        $this->assign("cart_data", $ary_cart_data);
        $this->assign('cart', $shopCart);
        $this->assign("price_data", $ary_price_data);
        $this->display();
    }

    /**
     * C2C订单确认页
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-14
     */
    public function pageAddTrdorders() {
        $this->getSubNav(2, 2, 40);
        //取出购物车中的数据
        $shopCart = session("trdShoppingCart");
        $ary_data = array();
        $Cart = D('Cart');
        $member = session('Members');
        $price = new PriceModel($member['m_id']);
        //获取第三方订单信息
        $ThdOrders = D("ThdOrders");
        //第三方订单ID
        if ($shopCart['tt_id']) {
            $where['to_oid'] = $shopCart['tt_id'];
        }
        $priceData = array();
        $ary_result = $ThdOrders->getTrdordersData($where);
        $city = D("CityRegion");
        //获取商品的库存和最终价格信息
        foreach ($shopCart['products'] as &$pdt) {
            $pdt['final_price'] = $price->getMemberPrice($pdt['pdt_id']);
            $priceData[$pdt['pdt_id']] = $pdt['nums'];
            //总价
            $ary_price_data['all_price'] += ($pdt['final_price'] * $pdt['nums']);
            //总重量
            $ary_price_data['all_weight'] += ($pdt['pdt_weight'] * $pdt['nums']);
            $ary_price_data['nums'] +=$pdt['nums'];
        }
        //购买货品的总价
        $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $Cart->getAllPrice($priceData));
        //优惠的价格
        $ary_price_data['pre_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
        //获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data['payment_cfg'] = $payment_cfg;
        $ary_city = $city->getCurrLvItem(1);
        //根据第三方订单的地址匹配本地的地址库
        $cid = $city->getAvailableLogisticsList($ary_result['to_receiver_province'], $ary_result['to_receiver_city'], $ary_result['to_receiver_district']);
        $city_region_data = $city->getFullAddressId($cid);
        $js_city = "<script>
						selectCityRegion('1','province','{$city_region_data[1]}');
						selectCityRegion('{$city_region_data[1]}','city','{$city_region_data[2]}');
						selectCityRegion('{$city_region_data[2]}','region','{$city_region_data[3]}');
					   </script>";
        $this->assign("js_city", $js_city);
        $this->assign("city", $ary_city);
        $this->assign("ary_result", $ary_result);
        $this->assign('cart', $shopCart);
        $this->assign('data', $ary_price_data);
        $this->assign('ary_paymentcfg', $payment_cfg);
        //货品信息
        $ary_cart_data = $Cart->getProductInfo($priceData);
        $this->assign("cart_data", $ary_cart_data);
        //echo "<pre>";print_r($ary_data);exit;
        $this->display();
    }

    /**
     * C2C订单提交
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-14
     */
    public function doAddTrdorders() {
        $ThdOrders = D("ThdOrders");
        $ary_post = $this->_post();
        $orders = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $city = D('CityRegion');
        $logistic = D('Logistic');
        $cart = D('Cart');
        $orders->startTrans();
        //收货人
        $ary_orders['o_receiver_name'] = $ary_post['ra_name'];
        //第三方订单ID
        $ary_orders['o_source_id'] = $ary_post['tt_id'];
        //收货人电话
        $ary_orders['o_receiver_telphone'] = $ary_post['ra_phone'];
        //收货人手机
        $ary_orders['o_receiver_mobile'] = $ary_post['ra_mobile_phone'];
        //收货人邮编
        $ary_orders['o_receiver_zipcode'] = $ary_post['ra_post_code'];
        //收货人地址
        $ary_orders['o_receiver_address'] = $ary_post['ra_detail'];
        $ary_city_data = $city->getFullAddressId($ary_post['region']);
        //收货人省份
        $ary_orders['o_receiver_state'] = $city->getAddressName($ary_city_data[1]);

        //收货人城市
        $ary_orders['o_receiver_city'] = $city->getAddressName($ary_city_data[2]);

        //收货人地区
        $ary_orders['o_receiver_county'] = $city->getAddressName($ary_city_data[3]);
        $member = session('Members');
        //会员id
        $ary_orders['m_id'] = $member['m_id'];
        //订单id
        $ary_orders['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
        $priceData = array();
        //取出购物车中的数据
        $shopCart = session("trdShoppingCart");
        //获取商品的库存和最终价格信息
        foreach ($shopCart['products'] as &$pdt) {
            $priceData[$pdt['pdt_id']] = $pdt['nums'];
        }
        $ary_orders['o_source_type'] = 'taobao';
        $ary_orders['o_buyer_comments'] = $ary_post['order_massage'];
        $ary_orders['ra_id'] = $ary_post['region'];
        $ary_orders['o_receiver_time'] = $ary_post['o_receiver_time'];
        $ary_orders['o_payment'] = $ary_post['o_payment'];
        $ary_orders['o_cost_freight'] = sprintf("%0.2f", $logistic->getLogisticPrice($ary_orders['lt_id'], $priceData,$member['m_id']));
        //商品总价
        $ary_orders['o_goods_all_price'] = sprintf("%0.2f", $cart->getAllPrice($priceData));
        //订单总价
        $all_price = $ary_orders['o_goods_all_price'] + $ary_orders['o_cost_freight'];
        $ary_orders['o_all_price'] = sprintf("%0.2f", $all_price);
        $bool_orders = D('Orders')->doInsert($ary_orders);
        if (!$bool_orders) {
            $orders->rollback();
            $this->error('失败', array('失败' => U('Ucenter/Orders/pageList')));
            exit;
        } else {
            $ary_orders_items = array();
            $ary_orders_goods = $cart->getProductInfo($priceData);
            if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                foreach ($ary_orders_goods as $k => $v) {
                    //订单id
                    $ary_orders_items['o_id'] = $ary_orders['o_id'];
                    //商品id
                    $ary_orders_items['g_id'] = $v['g_id'];
                    //货品id
                    $ary_orders_items['pdt_id'] = $v['pdt_id'];
                    //类型id
                    $ary_orders_items['gt_id'] = $v['gt_id'];
                    //商品sn
                    $ary_orders_items['g_sn'] = $v['g_sn'];
                    //o_sn
                    //$ary_orders_items['g_id'] = $v['g_id'];
                    //货品sn
                    $ary_orders_items['pdt_sn'] = $v['pdt_sn'];
                    //商品名字
                    $ary_orders_items['oi_g_name'] = $v['g_name'];
                    //成本价
                    $ary_orders_items['oi_cost_price'] = $v['pdt_cost_price'];
                    //货品销售原价
                    $ary_orders_items['pdt_sale_price'] = $v['pdt_sale_price'];
                    //购买单价
                    $ary_orders_items['oi_price'] = $v['pdt_momery'];
                    //商品积分
                    //商品数量
                    $ary_orders_items['oi_nums'] = $v['pdt_nums'];
                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                    if (!$bool_orders_items) {
                        $orders->rollback();
                        $this->error('失败', array('失败' => U('Ucenter/Orders/pageList')));
                        exit;
                    } else {
                        if ($ary_post['tt_id']) {
                            $where['to_oid'] = $ary_post['tt_id'];
                        }
                        $data = array('to_tt_status' => '1');
                        $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                        if (!$ary_result) {
                            $orders->rollback();
                            $this->error('处理订单失败', array('失败' => U('Ucenter/Orders/pageList')));
                            exit;
                        }
                    }
                }
            }
        }
        $orders->commit();
        //$return_orders = true;
        //dump(U("Ucenter/Orders/pageShow"));
        //unset(session("trdShoppingCart"));
        session('trdShoppingCart', null);
        //unset(session("trdShoppingCart"));
        $this->success('成功', array('付款' => U("Ucenter/Orders/pageShow", array('oid' => $order_id))));
        exit;
    }

	protected function doBatchAddTrdOrder($ary_post){
		
        $ary_res = array('data' => array(), 'success' => '0', 'msg' => '下单失败');
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_cart = session("trdShoppingCart");
        $ary_order_info = array();
        $ThdOrders = D("ThdOrders");
        $city = D('CityRegion');
        $member = session("Members");
        $int_mid = $ary_order_info['m_id'] = $member['m_id'];
        if (!(int) $ary_post['payment']) {
            $ary_res['msg'] = '订单提交失败(没有可用的支付方式！)';
			return $ary_res;
        }

        //判断支付方式
        $payment = D('PaymentCfg');
        $where = array('pc' => $ary_post['payment']);
        $payment_cfg = $payment->getPayCfgId($where);
        if (empty($payment_cfg) || !is_array($payment_cfg)) {
            $ary_res['msg'] = '支付方式不存在或者已被停用！)';
			return $ary_res;
        }
        $obj_orders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        $orders = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $cart = D('Cart');
        $logistic = D('Logistic');
        //购物车信息
        $ary_cart_data = array();
        $User_Grade = D('MembersLevel')->getMembersLevels($member['ml_id']);
        $orders->startTrans();
        $price = new PriceModel($member['m_id']);
		//writeLog(json_encode($ary_post), 'TrOrders_batchAddTrdOrder.log');
        if (!empty($ary_post['order']) && is_array($ary_post['order'])) {
            foreach ($ary_post['order'] as $tt_id => &$order) {
                //计算订单最终金额，总金额，总重量,货品总数量
                $float_order_final_cost = 0;
                $float_order_total_cost = 0;
                $float_order_total_weight = 0;
                $int_order_pdt_total_nums = 0;
                $ary_tmp_order = $order;
                $priceData = array();
                $ary_logistics = array('logistics' => $ary_tmp_order['logistics'], 'log_conf' => $ary_tmp_order['log_conf']);
                unset($ary_tmp_order['logistics']);
                unset($ary_tmp_order['log_conf']);
                //商品总价
                $ary_order_info['o_goods_all_price'] = 0;
                foreach ($ary_tmp_order as $k => $o) {
                    foreach ($o as $p => $ps) {
                        if(!empty($ps['pdt_id'])){
                            if(isset($ary_cart_data[$ps['pdt_id']])){   // 去除重复 By Tom <helong@guanyisoft.com>
                                unset($ary_cart_data[$ps['pdt_id']]['toi_id']);
                                continue;
                            }
                            $ary_cart_data[$ps['pdt_id']] = array(
                                'pdt_id'=>trim($ps['pdt_id']),
                                'num'=>isset($ps['nums']) ? $ps['nums'] : $o['nums'],
                                'g_id'=>$ps['g_id'],
                                'type'=>0,
                                'pdt_sn'=>$ps['pdt_sn'],
                                'toi_id'=>$ps['toi_id']
                            );
                        }
                    }
                }
                foreach ($ary_cart_data as $ary) {
                    $is_authorize = D('AuthorizeLine')->isAuthorize($member ['m_id'], $ary['g_id']);
                    if (empty($is_authorize)) {
                        $ary_res['msg'] = '部分商品已不允许购买,请先在购物车里删除这些商品';
						return $ary_res;
                    }
                    $return_stock =  D("GoodsProducts")->where(array(
                        'pdt_id' => $ary ['pdt_id']
                    ))->getField('pdt_stock');
                    if(intval($return_stock)<=0){
                        $ary_res['msg'] = '货品编码为'.$ary['pdt_sn'].'的商品库存已不足';
                        $ary_res['msg'] = '部分商品已不允许购买,请先在购物车里删除这些商品';
						return $ary_res;
                    }
                }
                //普通订单商品
                $ary_goods = $ary_cart_data;
                $pro_datas = D('Promotion')->calShopCartPro($member ['m_id'], $ary_cart_data);
                $subtotal = $pro_datas ['subtotal'];
                unset($pro_datas ['subtotal']);

                // 商品总价
                $promotion_total_price = '0';
                $promotion_price = '0';
                //赠品数组
                $gifts_cart = array();
                foreach ($pro_datas as $keys => $vals) {
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
                //订单商品总价（销售价格带促销）
                $ary_order_info ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
                //商品销售总价
                $ary_order_info ['o_goods_all_saleprice'] = sprintf("%0.2f", $promotion_total_price);
                $ary_data ['ary_product_data'] = D('Cart')->getProductInfo($ary_cart);

                $ary_order_info['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
                //获取订单的配送信息
                $ary_delivery_info = $obj_orders->where(array('to_oid' => $tt_id))->find();
                if (empty($ary_delivery_info)) {
                    $orders->rollback();
                    $ary_res['msg'] = '订单：' . $tt_id . '提交失败(配送信息为空！)';
					$ary_res['msg'] = '部分商品已不允许购买,请先在购物车里删除这些商品';
					return $ary_res;					
                }
                $citydata = $city->getAvailableLogisticsList($ary_delivery_info['to_receiver_province'], $ary_delivery_info['to_receiver_city'], $ary_delivery_info['to_receiver_district']);
                $ary_order_info['ra_id'] = $citydata;
                //物流费用
                //$ary_json_decode = json_decode($ary_logistics['log_conf'], true);
                //$logistic_price = $ary_json_decode['total_freight_cost'];
                $logistic_price = D('Logistic')->getLogisticPrice($ary_logistics['logistics'], $ary_tmp_cart,$member['m_id']);
                //判断会员等级是否包邮
                if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                    $logistic_price = 0;
                }
                //物流公司设置包邮额度
                $lt_expressions = json_decode(M('logistic_type')->where(array('lt_id'=>$ary_logistics['logistics']))->getField('lt_expressions'),true);
                if(!empty($lt_expressions['logistics_configure']) && $ary_order_info['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                    $logistic_price = 0;
                }
                $ary_order_info['m_id'] = $int_mid;
                //支付手续费
                $ary_order_info['o_cost_payment'] = $payment_cfg['pc_fee'];
                //收货人
                $ary_order_info['o_receiver_name'] = $ary_delivery_info['to_receiver_name'];
                //收货人电话
                $ary_order_info['o_receiver_telphone'] = (string) $ary_delivery_info['to_receiver_phone'];
                //收货人手机
                $ary_order_info['o_receiver_mobile'] = (string) $ary_delivery_info['to_receiver_mobile'];
                //收货人邮编
                $ary_order_info['o_receiver_zipcode'] = $ary_delivery_info['to_receiver_zip'];
                if (empty($ary_order_info['o_receiver_mobile']) && empty($ary_order_info['o_receiver_telphone'])) {
                    $orders->rollback();
                    $ary_res['msg'] = '订单：' . $tt_id . '提交失败(手机号和电话号至少一个不能为空！)';
					$ary_res['msg'] = '部分商品已不允许购买,请先在购物车里删除这些商品';
					return $ary_res;					
                }
                //处理收货人地址
                if(isset($ary_delivery_info['to_temp_receiver_address']) && !empty($ary_delivery_info['to_temp_receiver_address'])){
                    $ary_to_temp_receiver_address = json_decode($ary_delivery_info['to_temp_receiver_address'], true);
                    //收货人省份
                    $ary_delivery_info['to_receiver_province'] = $ary_to_temp_receiver_address['to_receiver_province'];
                    //收货人城市
                    $ary_delivery_info['to_receiver_city'] = $ary_to_temp_receiver_address['to_receiver_city'];
                    //收货人地区
                    $ary_delivery_info['to_receiver_district'] = $ary_to_temp_receiver_address['to_receiver_district'];
                    //收货人地址
                    $ary_delivery_info['to_receiver_address'] = $ary_to_temp_receiver_address['to_receiver_address'];
                }
                //收货人省份
                $ary_order_info['o_receiver_state'] = $ary_delivery_info['to_receiver_province'];
                //收货人城市
                $ary_order_info['o_receiver_city'] = $ary_delivery_info['to_receiver_city'];
                //收货人地区
                $ary_order_info['o_receiver_county'] = $ary_delivery_info['to_receiver_district'];
                //收货人地址
                $ary_order_info['o_receiver_address'] = $ary_delivery_info['to_receiver_address'];
                //配送id
                $ary_order_info['lt_id'] = $ary_logistics['logistics'];
                //订单总价
                $all_price = $ary_order_info['o_goods_all_price'] + $ary_order_info['o_cost_freight'];
                $ary_order_info['o_all_price'] = sprintf("%0.2f", $all_price);
                //支付方式
                $ary_order_info['o_payment'] = $ary_post['payment'];
                //订单创建时间
                $ary_order_info['o_create_time'] = date("Y-m-d H:i:s");
                //第三方订单ID
                $ary_order_info['o_source_id'] = $tt_id;

                //买家订单留言
                $ary_order_info['o_buyer_comments'] = $ary_delivery_info['to_buyer_message'];
                $ary_order_info['o_source_type'] = 'taobao';
                $ary_order_info['o_create_time'] = date('Y-m-d H:i:s');
                $ary_order_info['o_cost_freight'] = $logistic_price;
                $all_price = $ary_order_info['o_all_price'];
                if ($all_price <= 0) {
                    $all_price = 0;
                }
                // 订单应付总价 订单总价+运费
                $all_price += $logistic_price;
                $ary_order_info ['o_all_price'] = sprintf("%0.3f", $all_price);
                //判断是否开启自动审核功能
                $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
                if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_post['o_payment'] == 6){
                    $ary_order_info['o_audit'] = 1;
                }
                //促销信息存起来
                $ary_order_info['promotion'] = serialize($pro_datas);
                $bool_orders = D('Orders')->add($ary_order_info);
                // $bool_orders = D('Orders')->doInsert($ary_order_info);
                if (FALSE == $bool_orders) {
                    $orders->rollback();
				    $ary_res['msg'] = '新增订单失败';
					return $ary_res;
                } else {
                    $ary_orders_items = array();
                    $ary_orders_goods = D('Cart')->getProductInfo($ary_cart_data);
                    if (!empty($gifts_cart)) {
                        $ary_gifts_goods = D('Cart')->getProductInfo($gifts_cart);
                        if (!empty($ary_gifts_goods)) {
                            foreach ($ary_gifts_goods as $gift) {
                                array_push($ary_orders_goods, $gift);
                            }
                        }
                    }

                    if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                        if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                            $total_consume_point = 0; // 消耗积分
                            $int_pdt_sale_price = 0; // 货品销售原价总和
                            foreach ($ary_orders_goods as $k => $v) {
                                $ary_orders_items = array();
                                if (!empty($v['rule_info']['name'])) {
                                    $v['pmn_name'] = $v['rule_info']['name'];
                                }
                                //促销信息
                                foreach ($pro_datas as $vals) {
                                    foreach ($vals['products'] as $key => $val) {
                                        if (($val['type'] == $v['type']) && ($val['pdt_id'] == $v['pdt_id'])) {
                                            if (!empty($vals['pmn_name'])) {
                                                $v['pmn_name'] .= ' ' . $vals['pmn_name'];
                                            }
                                        }
                                    }
                                }
                                // 第三方价格查询 By Tom <helong@guanyisoft.com>
                                foreach($ary_cart_data as $ary_cart){
                                    if(isset($ary_cart['toi_id']) && !empty($ary_cart['toi_id']) && $ary_cart['pdt_id'] == $v['pdt_id']){
                                        $toi_price = D('thd_orders_items')->where(array('toi_id'=>$val['toi_id']))->getField('toi_price');
                                        if(!empty($toi_price)){
                                            $ary_orders_items['oi_thd_sale_price'] = $toi_price;
                                            continue;
                                        }
                                    }
                                }
                                // 商品积分
                                $int_pdt_sale_price += $v ['pdt_sale_price'] * $v ['pdt_nums'];
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_order_info ['o_id'];
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
                                if (isset($v['pmn_name'])) {
                                    $ary_orders_items['promotion'] = $v['pmn_name'];
                                }
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                                $int_pdt_stock = D("GoodsProducts")->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->getField("pdt_stock");
                                if($ary_orders_items['oi_nums'] > $int_pdt_stock){
                                    $orders->rollback();
                                    $this->ajaxReturn(array('success'=>0, 'msg'=>'商品编码为' . "{$ary_orders_items[g_sn]}" . '的库存不足！'));
                                    exit;
                                }
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $ary_res['msg'] = '新增订单数据失败！)';
									return $ary_res;									
                                    echo json_encode($ary_res);
                                    exit;
                                }else{
									//增加销量
									$ary_goods_num = M("goods_info")->where(array('g_id' => $v ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$v['pdt_nums'])))->save();
									if (!$ary_goods_num) {
										$orders->rollback();
										$ary_res['msg'] = '销量添加失败)';
										return $ary_res;									
										echo json_encode($ary_res);
										exit;
									}
								}
                            }
                        }
                        if (!empty($tt_id)) {
                            $where['to_oid'] = $tt_id;
                        }
                        $data = array('to_tt_status' => '1');
                        $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                        if (!$ary_result) {
                            $orders->rollback();
							return $ary_res;	
                            $this->error('处理订单失败');
                            exit;
                        }
                    }
                    $bl_to_erp = true;
                }
                
            }
			
			$orders->commit();
        } else {
			$orders->rollback();
            $ary_res['msg'] = '提交数据错误！)';
			return $ary_res;	
            echo json_encode($ary_res);
            exit;
        }
        $ary_res['success'] = 1;
        if ($bl_to_erp !== false) {
            $ary_res['msg'] = '提交成功！';
        }		
		return $ary_res;	
	}
	
    /**
     * 批量下单
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-14
     */
    public function batchAddTrdOrder() {
		$ary_post = $this->_post();
		$ary_res = $this->doBatchAddTrdOrder($ary_post);
        echo json_encode($ary_res);
        exit;
    }
	
    /**
     * 批量下单
     * @return mixed array
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-14
     */
    public function batchAddTrdOrderBak() {
        $ary_res = array('data' => array(), 'success' => '0', 'msg' => '下单失败');
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_cart = session("trdShoppingCart");
        $ary_post = $this->_post();
        $ary_order_info = array();
        $ThdOrders = D("ThdOrders");
        $city = D('CityRegion');
        $member = session("Members");
        $int_mid = $ary_order_info['m_id'] = $member['m_id'];
        if (!(int) $ary_post['payment']) {
            $ary_res['msg'] = '订单提交失败(没有可用的支付方式！)';
            echo json_encode($ary_res);
            exit;
        }

        //判断支付方式
        $payment = D('PaymentCfg');
        $where = array('pc' => $ary_post['payment']);
        $payment_cfg = $payment->getPayCfgId($where);
        if (empty($payment_cfg) || !is_array($payment_cfg)) {
            $ary_res['msg'] = '支付方式不存在或者已被停用！)';
            echo json_encode($ary_res);
            exit;
        }
        $obj_orders = M("thd_orders", C('DB_PREFIX'), 'DB_CUSTOM');
        $orders = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $cart = D('Cart');
        $logistic = D('Logistic');
        $orders->startTrans();
        $price = new PriceModel($member['m_id']);
        if (!empty($ary_post['order']) && is_array($ary_post['order'])) {
            foreach ($ary_post['order'] as $tt_id => &$order) {
			
                //计算订单最终金额，总金额，总重量,货品总数量
                $float_order_final_cost = 0;
                $float_order_total_cost = 0;
                $float_order_total_weight = 0;
                $int_order_pdt_total_nums = 0;
                $ary_tmp_order = $order;
                $priceData = array();
                $ary_logistics = array('logistics' => $ary_tmp_order['logistics'], 'log_conf' => $ary_tmp_order['log_conf']);
                unset($ary_tmp_order['logistics']);
                unset($ary_tmp_order['log_conf']);
                //商品总价
                $ary_order_info['o_goods_all_price'] = 0;
                foreach ($ary_tmp_order as $k => $o) {
                    foreach ($o as $p => $ps) {
                    	if(!empty($ps['pdt_id'])){
                         	$float_pdt_promotion_price = $price->getMemberPrice($ps['pdt_id']);
	                        $order[$k][$p]['oi_price'] = $float_pdt_promotion_price;
	                        $float_order_final_cost += $float_pdt_promotion_price * $ps['nums'];
	                        $float_order_total_cost += $ps['pdt_sale_price'] * $ps['nums'];
	                        $priceData[$ps['pdt_id']] = $ps['nums'];
	                        $float_order_total_weight += floatval($ps['pdt_weight']) * $ps['nums'];
	                        $int_order_pdt_total_nums += $ps['nums'];
	                        $ary_order_info['o_goods_all_price']+= sprintf("%0.3f", $ps['nums'] * $price->getItemPrice($ps['pdt_id']));
	                        $ary_order_info['o_goods_all_saleprice']+= sprintf("%0.3f", $ps['nums'] * $price->getItemPrice($ps['pdt_id']));                		
                    	}                  
                    }
                }
				 //物流费用
				$ary_json_decode = json_decode($ary_logistics['log_conf'], true);
				$ary_order_info['o_cost_freight'] = $ary_json_decode['total_freight_cost'];
				//$ary_order_info['o_cost_freight'] = $city->getActualLogisticsFreight($ary_json_encode, $priceData[$ps['pdt_id']], $float_order_total_weight, $ary_order_info['o_goods_all_price']);
				//$ary_order_info['o_cost_freight'] = sprintf("%0.2f", $logistic->getLogisticPrice($ary_logistics['logistics'], $priceData));
                //$float_logistics_cost =$logistic->getLogisticPrice($ary_logistics['logistics'], $priceData);
                $ary_order_info['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
                //获取订单的配送信息
                $ary_delivery_info = $obj_orders->where(array('to_oid' => $tt_id))->find();
                if (empty($ary_delivery_info)) {
                    $orders->rollback();
                    $ary_res['msg'] = '订单：' . $tt_id . '提交失败(配送信息为空！)';
                    echo json_encode($ary_res);
                    exit;
                }
                $citydata = $city->getAvailableLogisticsList($ary_delivery_info['to_receiver_province'], $ary_delivery_info['to_receiver_city'], $ary_delivery_info['to_receiver_district']);
                $ary_order_info['m_id'] = $int_mid;
                $ary_order_info['ra_id'] = $citydata;
                //支付手续费
                $ary_order_info['o_cost_payment'] = $payment_cfg['pc_fee'];
                //收货人
                $ary_order_info['o_receiver_name'] = $ary_delivery_info['to_receiver_name'];
                //收货人电话
                $ary_order_info['o_receiver_telphone'] = (string) $ary_delivery_info['to_receiver_phone'];
                //收货人手机
                $ary_order_info['o_receiver_mobile'] = (string) $ary_delivery_info['to_receiver_mobile'];
                //收货人邮编
                $ary_order_info['o_receiver_zipcode'] = $ary_delivery_info['to_receiver_zip'];
                if (empty($ary_order_info['o_receiver_mobile']) && empty($ary_order_info['o_receiver_telphone'])) {
                    $orders->rollback();
                    $ary_res['msg'] = '订单：' . $tt_id . '提交失败(手机号和电话号至少一个不能为空！)';
                    echo json_encode($ary_res);
                    exit;
                }
				//处理收货人地址
				if(isset($ary_delivery_info['to_temp_receiver_address']) && !empty($ary_delivery_info['to_temp_receiver_address'])){
					$ary_to_temp_receiver_address = json_decode($ary_delivery_info['to_temp_receiver_address'], true);
					//收货人省份
					$ary_delivery_info['to_receiver_province'] = $ary_to_temp_receiver_address['to_receiver_province'];
					//收货人城市
					$ary_delivery_info['to_receiver_city'] = $ary_to_temp_receiver_address['to_receiver_city'];
					//收货人地区
					$ary_delivery_info['to_receiver_district'] = $ary_to_temp_receiver_address['to_receiver_district'];
					//收货人地址
					$ary_delivery_info['to_receiver_address'] = $ary_to_temp_receiver_address['to_receiver_address'];
				}
				//收货人省份
				$ary_order_info['o_receiver_state'] = $ary_delivery_info['to_receiver_province'];
				//收货人城市
				$ary_order_info['o_receiver_city'] = $ary_delivery_info['to_receiver_city'];				
				//收货人地区
				$ary_order_info['o_receiver_county'] = $ary_delivery_info['to_receiver_district'];
				//收货人地址
                $ary_order_info['o_receiver_address'] = $ary_delivery_info['to_receiver_address'];
                //配送id
                $ary_order_info['lt_id'] = $ary_logistics['logistics'];
                //订单总价
                $all_price = $ary_order_info['o_goods_all_price'] + $ary_order_info['o_cost_freight'];
                $ary_order_info['o_all_price'] = sprintf("%0.2f", $all_price);
                //支付方式
                $ary_order_info['o_payment'] = $ary_post['payment'];
                //订单创建时间
                $ary_order_info['o_create_time'] = date("Y-m-d H:i:s");
                //第三方订单ID
                $ary_order_info['o_source_id'] = $tt_id;

                //买家订单留言
                $ary_order_info['o_buyer_comments'] = $ary_delivery_info['to_buyer_message'];
                $ary_order_info['o_source_type'] = 'taobao';
                $ary_order_info['o_create_time'] = date('Y-m-d H:i:s');
                $bool_orders = D('Orders')->add($ary_order_info);
               // $bool_orders = D('Orders')->doInsert($ary_order_info);
                
                if (FALSE == $bool_orders) {
                    $orders->rollback();
                    $this->error('失败', array('失败' => U('Ucenter/Orders/123')));
                    exit;
                } else {
                    $ary_orders_items = array();
                    $goods_data = array();
                    $arr_data = $ary_post['order'][$tt_id];
                    unset($arr_data['logistics']);
                    unset($arr_data['o_remark']);
                    unset($arr_data['log_conf']);
                    if(!empty($arr_data) && is_array($arr_data)){
                        $data = array();
                        foreach($arr_data as $ky=>$vl){
                            
                            $pdt_id = '';
                            foreach($vl as $kys=>$vls){
                               // echo "<pre>";print_r($vl[$kys]['pdt_sale_price']);
                                if(isset($vls['pdt_id'])){
                                    $data[$kys]['pdt_id'] = $vl[$kys]['pdt_id'];
                                }
                                if(isset($vl[$kys]['pdt_sale_price'])){
                                    $data[$kys]['pdt_sale_price'] = $vl[$kys]['pdt_sale_price'];
                                }
                                if(isset($vl[$kys]['nums'])){
                                    $data[$kys]['num'] = $vl[$kys]['nums'];
                                }
                               // $data[$kys]['num'] = $vl[$kys]['nums'];
                                
                            }
                            
                           // $goods_data = $cart->getProductInfo($data);
                        }
                        
                        if(!empty($data) && is_array($data)){
                            foreach($data as $keyd=>$vald){
                                $arr_data = array();
                                $arr_data[$vald['pdt_id']]['pdt_sale_price'] = $vald['pdt_sale_price'];
                                $arr_data[$vald['pdt_id']]['num'] = $vald['num'];
                                $ary_product = $cart->getProductInfo($arr_data);
                                $goods_data[$vald['pdt_id']] = $ary_product[0];
                            }
                        }
                    }
                    if (!empty($goods_data) && is_array($goods_data)) {
                        foreach ($goods_data as $k => $v) {
                            //订单id
                            $ary_orders_items['o_id'] = $ary_order_info['o_id'];
                            //商品id
                            $ary_orders_items['g_id'] = $v['g_id'];
                            //货品id
                            $ary_orders_items['pdt_id'] = $v['pdt_id'];
                            //类型id
                            $ary_orders_items['gt_id'] = $v['gt_id'];
                            //商品sn
                            $ary_orders_items['g_sn'] = $v['g_sn'];
                            //o_sn
                           // $ary_orders_items['g_id'] = $v['g_id'];
                            //货品sn
                            $ary_orders_items['pdt_sn'] = $v['pdt_sn'];
                            //商品名字
                            $ary_orders_items['oi_g_name'] = $v['g_name'];
                            //成本价
                            $ary_orders_items['oi_cost_price'] = $v['pdt_cost_price'];
                            //货品销售原价
                            $ary_orders_items['pdt_sale_price'] = $v['pdt_sale_price'];
                            //购买单价
                            $ary_orders_items['oi_price'] = $v['pdt_momery'];
                            //商品积分
                            //商品数量
                            $ary_orders_items['oi_nums'] = $v['pdt_nums'];
                            $ary_orders_items['oi_create_time'] = date('Y-m-d H:i:s');
							//判断货品的库存数
							$int_pdt_stock = D("GoodsProducts")->where(array('pdt_id'=>$ary_orders_items['pdt_id']))->getField("pdt_stock");
							if($ary_orders_items['oi_nums'] > $int_pdt_stock){
								$orders->rollback();
								$this->ajaxReturn(array('success'=>0, 'msg'=>'商品编码为' . "{$ary_orders_items[g_sn]}" . '的库存不足！'));
								exit;
							}
                            $bool_orders_items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_orders_items);
                           // echo "<pre>";print_R(M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
                           // $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                $orders->rollback();
                                $this->error('失败', array('失败' => U('Ucenter/Orders/1234')));
                                exit;
                            }
                        }
                        if (!empty($tt_id)) {
                            $where['to_oid'] = $tt_id;
                        }
                        $data = array('to_tt_status' => '1');
                        $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                        if (!$ary_result) {
                            $orders->rollback();
                            $this->error('处理订单失败');
                            exit;
                        }
                    }
                    $bl_to_erp = true;
                }
                $orders->commit();
            }
        } else {
            $ary_res['msg'] = '提交数据错误！)';
            echo json_encode($ary_res);
            exit;
        }
        $ary_res['success'] = 1;
        if ($bl_to_erp !== false) {
            $ary_res['msg'] = '提交成功！';
        }
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 云erp店铺信息
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function YunerpShop() {
        $this->getSubNav(2, 1, 80);
        $member = session("Members");
        $arr_result = D("ThdShops")->where(array('m_id' => $member['m_id']))->select();
        $this->assign("shop_info", $arr_result);
        $this->display();
    }

    /**
     * 云erp绑定店铺昵称check
     * @return mixed boolean 
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-30
     */
    public function checkShopNick($shop_name) {
        $res = D("ThdShops")->where(array('ts_nick' => $shop_name))->find();
        if (empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 云erp店铺信息添加
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function DoAddYunerpShop() {
        $this->getSubNav(4, 3, 110);
        $member = session("Members");
        $data['m_id'] = $member['m_id'];
        $data['ts_source'] = $this->_post("types");
        $data['ts_nick'] = $this->_post("nick");
        $data['ts_created'] = date('Y-m-d H:i:s');
        $data['ts_modified'] = date('Y-m-d H:i:s');
        $res = $this->checkShopNick(trim($data['ts_nick']));
        if ($res == false) {
            $this->error('你输入的店铺昵称已存在！', U('Ucenter/Trdorders/yunerpShop'));
        } else {
            $int_res = D("ThdShops")->add($data);
            if ($int_res) {
                $this->success('店铺信息保存成功！', U('Ucenter/Trdorders/yunerpShop'));
            } else {
                $this->error('店铺信息保存失败！', U('Ucenter/Trdorders/yunerpShop'));
            }
        }
    }

    /**
     * 云erp店铺信息修改页面
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function yunerpshopEdit() {
        $this->getSubNav(4, 3, 110);
        $member = session("Members");
        $arr_result = D("ThdShops")->where(array('m_id' => $member['m_id']))->find();
        $this->assign("shop_info", $arr_result);
        $this->display();
    }

    /**
     * 云erp店铺信息修改
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function DoEditYunerpShop() {
        $this->getSubNav(4, 3, 110);
        $member = session("Members");
        $where['m_id'] = $member['m_id'];
        $where['ts_id'] = $this->_post("id");
        $data['ts_nick'] = $this->_post("nick");
        $data['ts_modified'] = date('Y-m-d H:i:s');
        $int_res = D("ThdShops")->where($where)->save($data);
        if ($int_res) {
            $this->success('店铺信息保存成功！', U('Ucenter/Trdorders/yunerpShop'));
        } else {
            $this->error('店铺信息保存失败！', U('Ucenter/Trdorders/yunerpShop'));
        }
    }

    /**
     * 云erp店铺信息删除
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function YunerpshopDel() {
        $this->getSubNav(4, 3, 110);
        $member = session("Members");
        $where['m_id'] = $member['m_id'];
        $where['ts_id'] = $this->_get("id");
        $res = D("ThdShops")->where($where)->delete();
        if ($res) {
            $this->success('删除成功！', U('Ucenter/Trdorders/yunerpShop'));
        } else {
            $this->error('删除失败！', U('Ucenter/Trdorders/yunerpShop'));
        }
    }

    /**
     * 云erp订单列表页
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function pageYunerp() {
        $this->getSubNav(1, 1, 70);
        $member = session("Members");
        $data = $this->_post();
        $where['m_id'] = $member['m_id'];
        if (!empty($data['oid'])) {
            $where['to_oid'] = $data['oid'];
        }
        if (!empty($data['o_receiver_name'])) {
            $where['to_receiver_name'] = $data['o_receiver_name'];
        }
        if (!empty($data['status'])) {
            $where['to_thd_status'] = $data['status'];
        }
        if (!empty($data['types'])) {
            $where['to_source'] = $data['types'];
        }
        $count = D("ThdOrders")->where($where)->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $arr_result = D("ThdOrders")->where($where)->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
        $this->assign("orders_info", $arr_result);
        $this->assign('page', $page);    //赋值分页输出
        $this->display();
    }

    public function doYunerpOrdersDownload() {
        $member = session("Members");
        $down_load = false;
        @set_time_limit(0);
        @ignore_user_abort(TRUE);
        $yun_obj = new Yunerp();
        $res = $yun_obj->request('gy.download.trade.get');
        $ary_data = json_decode($res, true);
        if (!$ary_data['status']) {
            $ary_datas = json_decode($ary_data['data'], true);
            $arr_result = D("ThdShops")->where(array('m_id' => $member['m_id']))->select();
            if (empty($arr_result)) {
                $this->error('请设定您的店铺昵称！', U('Ucenter/Trdorders/pageYunerp'));
            }
            foreach ($arr_result as $shop) {
                $seller_name[$shop['ts_nick']] = $shop['ts_id'];
            }
            foreach ($ary_datas as $key => $value) {
                if (array_key_exists($value['seller_nick'], $seller_name)) {
                    $down_load = ture;
                    $order_data['ts_id'] = $seller_name[$value['seller_nick']]; //第三方网店id
                    $order_data['to_oid'] = $value['tid']; //交易编号
                    //1.淘宝,2其他,3.淘宝分销,4.拍拍,5.京东,6.当当
                    $order_data['to_source'] = $value['storetype'];
                    $order_data['to_buyer_id'] = $value['buyer_nick']; //买家昵称
                    $order_data['to_created'] = $value['created']; //交易创建时间
                    $order_data['to_modified'] = $value['modified']; //交易修改时间
                    $order_data['to_pay_time'] = $value['pay_time']; //付款时间
                    $order_data['to_post_fee'] = $value['post_fee']; //邮费
                    $order_data['to_payment'] = $value['payment']; //实付金额
                    $order_data['to_receiver_address'] = $value['receiver_address']; //收货人地址
                    $order_data['to_receiver_province'] = $value['receiver_state']; //收货人所在省
                    $order_data['to_receiver_city'] = $value['receiver_city']; //收货人所在市
                    $order_data['to_receiver_district'] = $value['receiver_district']; //收货人所在区
                    $order_data['to_receiver_name'] = $value['receiver_name']; //收货人姓名
                    $order_data['to_receiver_mobile'] = $value['receiver_mobile']; //收货人手机号
                    $order_data['to_receiver_phone'] = $value['receiver_phone']; //收货人电话
                    $order_data['to_receiver_zip'] = $value['receiver_zip']; //收货人邮编
                    //$order_data['to_tt_status']=$value[''];
                    $order_data['m_id'] = $member['m_id']; //会员ID（本地）
                    //$order_data['to_visitor_id']=$value[''];//店铺来源id
                    $order_data['to_buyer_message'] = $value['buyer_message']; //买家留言
                    $order_data['to_seller_memo'] = $value['seller_memo']; //卖家备注
                    $order_data['to_seller_title'] = $value['seller_nick']; //卖家店铺名称
                    $order_data['to_thd_status'] = $value['status']; //交易状态
                    //$order_data['to_is_match']=$value[''];
                    foreach ($order_data as $order_key => $order_value) {
                        if (empty($order_value)) {
                            unset($order_data[$order_key]);
                        }
                    }
                    $counts = D("ThdOrders")->where(array('to_oid' => $order_data['to_oid']))->count();
                    D("ThdOrders")->startTrans();
                    if ($counts == 0) {
                        $thd_id = D("ThdOrders")->add($order_data);
                    } else {
                        $thd_id = D("ThdOrders")->where(array('to_oid' => $order_data['to_oid']))->save($order_data);
                    }
                    if (empty($thd_id)) {
                        D("ThdOrders")->rollback();
                    }
                    if (!empty($value['orders']) && !empty($thd_id)) {
                        $order_item_data['to_id'] = $thd_id;
                        foreach ($value['orders'] as $key => $item_val) {
                            $order_item_data['toi_adjust_money'] = $item_val['adjust_fee']; //手工调整金额
                            $order_item_data['toi_discount_money'] = $item_val['discount_fee']; //订单优惠金额
                            $order_item_data['toi_num'] = $item_val['num']; //购买数量
                            $order_item_data['toi_num_id'] = $item_val['num_iid']; //商品数字ID
                            $order_item_data['toi_price'] = $item_val['price']; //商品价格
                            $order_item_data['toi_title'] = $item_val['title']; //商品标题
                            $order_item_data['toi_outer_id'] = $item_val['outer_iid']; //商家外部编码
                            $order_item_data['toi_outer_sku_id'] = $item_val['outer_sku_id']; //外部网店自己定义的Sku编号
                            $order_item_data['toi_url'] = $item_val['snapshot_url']; //快照URL
                            //$order_item_data['toi_b2b_pdt_sn_info']=$item_val[''];
                            //$order_item_data['toi_status']=$item_val['status'];//订单状态
                            $order_item_data['toi_spec_name'] = $item_val['sku_properties_name']; //SKU的值
                            $thd_match_goods = D("ThdOrders")->getMatchTrdOrders($item_val['outer_iid'], $item_val['outer_sku_id'], $item_val['num']);
                            if (!empty($thd_match_goods) && is_array($thd_match_goods)) {
                                $order_item_data['toi_b2b_pdt_sn_info'] = serialize($thd_match_goods);
                            }
                            foreach ($order_item_data as $item_key => $item_value) {
                                if (empty($item_value)) {
                                    unset($order_item_data[$item_key]);
                                }
                            }
                            if ($counts == 0) {
                                $thd_item_id = D("ThdOrdersItems")->add($order_item_data);
                            } else {
                                $thd_id = D("ThdOrders")->where(array('to_id' => $order_item_data['to_oid'], 'toi_num_id' => $order_item_data['toi_num_id']))->save($order_item_data);
                            }
                            if (empty($thd_item_id)) {
                                D("ThdOrdersItems")->rollback();
                            }
                        }
                    }
                    D("ThdOrders")->commit();
                }
            }
            if ($ary_data['hasMore']) {
                $del_res = $yun_obj->tradeaccept('gy.download.trade.get', $ary_data['sessionId']);
                $ary_del_data = json_decode($del_res, true);
                if (!$ary_del_data['status']) {
                    $this->doYunerpOrdersDownload();
                }
            }
            if ($down_load) {
                $this->success('订单下载成功', U('Ucenter/Trdorders/thdOrderList'));
            } else {
                $this->error('没有符合条件的记录', U('Ucenter/Trdorders/thdOrderList'));
            }
        } else {
            $this->error($ary_data['error']['message'], U('Ucenter/Trdorders/thdOrderList'));
        }
    }

    /**
     * 第三方平台订单列表
     * @return mixed array
     * @author Terry <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-10
     */
    public function thdOrderList() {
        $this->getSubNav(2, 2, 70);
        $member = session('Members');
        $ary_filter = $this->_get();
        $arr_where = array();
        $arr_where['m_id'] = $member['m_id'];
        $arr_result = D("ThdShops")->where($arr_where)->select();

        $where = array();
        $order_desc = array();
        if (!empty($ary_filter['tsid']) && (int) $ary_filter['tsid'] != '0') {
            /*             * *****************拼接查询条件************************************** */
            if ($ary_filter['tt_id']) {//按外部平台订单号
                $where['to_oid'] = trim($ary_filter['tt_id']);
            }

            if (!empty($ary_filter['match']) && $ary_filter['match'] != '0') {
                if ($ary_filter['match'] == '1') {
                    $where['to_is_match'] = 0;
                } else {
                    $where['to_is_match'] = 1;
                }
            }

            if ($ary_filter['buyer']) {//按买家昵称
                $where['to_buyer_id'] = array('LIKE', '%' . trim($ary_filter['buyer']) . '%');
            }
            if (!empty($ary_filter['order_minDate']) && isset($ary_filter['order_minDate'])) {
                if (!empty($ary_filter['order_maxDate'])) {
                    $ary_filter['order_maxDate'] = trim($ary_filter['order_maxDate']);
                } else {
                    $ary_filter['order_maxDate'] = date("Y-m-d H:i:s");
                }

                if ($ary_filter['order_minDate']) {//按成交时间
                    $where['to_created'] = array("between", array($ary_filter['order_minDate'], $ary_filter['order_maxDate']));
                }
            }
            $where['to_tt_status'] = 0;
            $where['ts_id'] = (int) $ary_filter['tsid'];
            /*             * *****************获取订单总数************************************** */
            $count = D('ThdOrders')->where($where)->count();
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
            //获取货品价格等额外数据
            $price = new PriceModel($member['m_id']);
            $goodsSpec = D('GoodsSpec');
            $ary_product_feild = array('fx_goods_products.pdt_sn', 'fx_goods_info.g_id', 'fx_goods_products.pdt_weight', 'fx_goods_products.pdt_stock', 'fx_goods_products.pdt_memo', 'fx_goods_products.pdt_id', 'fx_goods_products.pdt_sale_price', 'fx_goods_products.pdt_on_way_stock', 'fx_goods_info.g_name', 'fx_goods_info.g_picture');
            $ary_result = D("ThdOrders")->getThdOrdersPageList($where, $obj_page->firstRow, $obj_page->listRows);
            $gTotalPrice = '';
            if (!empty($ary_result) && is_array($ary_result)) {
                foreach ($ary_result as $keydata => &$valdata) {
                    foreach ($valdata['orders'] as $keyorder => &$valorder) {
                        $valorder['pdt_sn_info'] = unserialize($valorder['toi_b2b_pdt_sn_info']);
                        $goods = array();
                        foreach ($valorder['pdt_sn_info'] as $keypdtsn => $valpdtsh) {
                            $ary_products = D("GoodsProducts")->GetProductList(array('fx_goods_products.pdt_sn' => $valpdtsh['pdt_sn']), $ary_product_feild);
                            $goods[$keypdtsn] = $ary_products[0];
                            if (!empty($goods[$keypdtsn]) && is_array($goods[$keypdtsn])) {
                                $goods[$keypdtsn]['num'] = $valpdtsh['num'];
                            }
                        }
                        if (!empty($goods) && is_array($goods)) {
                            foreach ($goods as $keyg => $valg) {
                                $goods[$keyg]['specName'] = $goodsSpec->getProductsSpec($valg['pdt_id']);
                                $goods[$keyg]['cgPrice'] = sprintf("%.2f", $goods[$keyg]['pdt_sale_price'] * $valg['num']);
                                $goods[$keyg]['pdt_sale_price'] = sprintf("%.2f", $goods[$keyg]['pdt_sale_price']);
                                $goods[$keyg]['num'] = $valg['num'];
                                $valorder['pic'] = getFullPictureWebPath($valg['g_picture']);
                                if ($valorder['toi_status'] == '0') {//未删除
                                    $valdata['totalPrice'] += $goods[$keyg]['pdt_sale_price'] * $goods[$keyg]['num'];
                                } else {
                                    break;
                                }
                                $valorder['goods'] = $goods;
                            }
                        } else {
                            $empty = '';
                            $empty .= '<td colspan="3">无匹配</td>';
                            $this->assign('empty', $empty);
                        }
                    }
                    $gTotalPrice +=$valdata['totalPrice'];
                }
            }
        }

        //获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $this->assign("thdShop", $arr_result);
        $ary_filter['payment_cfg'] = $payment_cfg;
        $goods = D("ViewGoods");
        $category = $goods->getCates();
        $this->assign("category", $category);
        $this->assign("filter", $ary_filter);
        $this->assign("countPrice", $gTotalPrice);
        $this->assign("order", $ary_result);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 第三方平台购物车
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-10
     */
    public function addthdCart() {
        $ary_res = array('success' => '0', 'msg' => '添加购物车失败', 'data' => array());
        $member = session('Members');
        $ary_post = $this->_post();
        $ary_cart = $ary_post['cart'];
        if (empty($ary_cart) && is_array($ary_cart)) {
            return array();
        }
        foreach ($ary_cart as $str_pdt => $int_num) {
            $int_num = (int) $int_num;
            //过滤小于零的
            if ($int_num <= 0) {
                unset($ary_cart[$str_pdt]);
            }
        }
        session("trdShoppingCart", $ary_cart);
        $ary_res['success'] = 1;
        $ary_res['msg'] = '加入购物城成功！';
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 第三方平台购物车列表
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-10
     */
    public function thdCartList() {
        $this->getSubNav(1, 0, 30);
        $this->getSeoInfo(L('MY_CART'));
        $Cart = D('Cart');
        $free_all_price = 0;
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
            $cart_data = session("trdShoppingCart");
            if(!empty($cart_data) && is_array($cart_data)){
                foreach($cart_data as &$val){
                    if($val['type'] == '0'){
                        $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$val['pdt_id']))->find();
                        $val['g_id'] = $ary_gid['g_id'];
                    }
                }
            }
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
            $ary_pdt = array();
            
            //购买商品总金额
            foreach ($cart_data as $key => $value) {
                if ($key == 'gifts') {
                    unset($cart_data[$key]);
                } else {
                    $pdt_id = $value['pdt_id'];
                    $int_type = isset($value['type']) ? $value['type'] : 0;
                    $ary_pdt[$key] = array('pdt_id' => $pdt_id, 'g_id'=>$value['g_id'],'num' => $value['num'], 'type' => $int_type, 'fc_id' => $value['fc_id']);
                }
            }
            if (is_array($ary_pdt) && !empty($ary_pdt)) {
                $ary_cart_data = $Cart->getProductInfo($ary_pdt);
            }
            $ary_cart = array();
            foreach ($ary_cart_data as $key=>$info) {
                if (isset($info['pdt_id'])) {
                    $ary_cart[$info['pdt_id']] = $info;
                    //添加产品是否允许购买
                    $ary_cart[$info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $info['g_id']);
                } else {
                    //自由组合权限判断
                    if ($info[0]['type'] == 4 || $info[0]['type'] == 6) {
                        foreach ($info as $subkey=>$sub_info) {
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
            foreach($pro_datas as $keys=>$vals){ 
                foreach($vals['products'] as $key=>$val){
                    $arr_products = $Cart->getProductInfo(array($key=>$val));
                    if($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6'){
                        foreach($arr_products[0] as &$provals){
                             $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                        }
                    }
                    $pro_datas[$keys]['products'][$key] =  $arr_products[0];
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
                if($keys != '0'){
                    $promotion_price += $vals['pro_goods_discount'];
                }
            }
            //获取赠品信息
            if(!empty($cart_gifts)){
            	$cart_gifts_data = array();
            	$cart_gifts_data = $Cart->getProductInfo($cart_gifts);
            }
            $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
            $ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
            $ary_price_data['all_price'] = $preferential_price = (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) > 0 ? (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) : '0.00';
            $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price'] - $free_all_price);
            //需消耗总积分
            $ary_price_data['consume_point'] = intval($subtotal['goods_all_point']);
        }
       //echo "<pre>";print_r($pro_datas);exit;
        $this->assign("gifts_data", $cart_gifts_data);
		//echo "<pre>";print_r($cart_gifts_data);die;
        $this->assign("price_data", $ary_price_data);
        $this->assign("cart_data", $ary_cart);
        $this->assign("pro_data",$pro_data);
        $this->assign("promotion",$pro_datas);
		//echo "<pre>";print_r($pro_datas);die;
        $this->display();
    }
    
    /**
     * 根据商品的pdt_id和购买数量，修改购物车
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     */
    public function doEdit() {
        $int_pdt_nums = $this->_post("pdt_nums");
        $int_pdt_id = $this->_post("pdt_id");
        $int_good_type = $this->_post("good_type", "", 0);
        $all_price = $this->_post("all_price", "", 0);
        $all_dis = $this->_post("all_dis", "", 0);
        $promotion_flg = false;
        $Cart = D('Cart');
        $promotion_price = 0;
        $preferential_price = 0;
        $combo_all_price = 0;
        //组合价格
        $free_all_price = 0;
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
            $ary_db_carts = session("trdShoppingCart");
            foreach ($ary_db_carts as $key => &$value) {
                if ($key == $int_pdt_id) {
                    $value['num'] = $int_pdt_nums;
                }
                if($value['type'] == '0'){
                    $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$value['pdt_id']))->find();
                    $value['g_id'] = $ary_gid['g_id'];
                }
            }
            $pro_datas = array();
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $ary_db_carts);
            //echo "<pre>";print_r($pro_datas);exit;
            if(!empty($pro_datas) && is_array($pro_datas)){
                $subtotal = $pro_datas['subtotal']; //促销金额
                //剔除商品价格信息
                unset($pro_datas['subtotal']);
                $ary_gifts = array();
                foreach($pro_datas as $keys=>$vals){
                    foreach($vals['products'] as $key=>$val){
                        $arr_products = $Cart->getProductInfo(array($key=>$val));
//                        echo "<pre>";print_r($arr_products);
                        if($arr_products[0][0]['type'] == '4'){
                            foreach($arr_products[0] as &$provals){
                                 $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                            }
                        }
                        $pro_datas[$keys]['products'][$key] =  $arr_products[0];
                        $pro_data[$key] = $val;
                        if(!empty($vals['pmn_name'])){
                            $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
                            $pro_data[$key]['pmn_id'] = $vals['pmn_id'];
                        }else{
                            $pro_data[$key]['pmn_name'] = $arr_products[0]['rule_info']['name'];
                            $pro_data[$key]['pmn_id'] = $arr_products[0]['rule_info']['pmn_id'];
                        }
                    }
                    $ary_gifts = D('Cart')->getProductInfo($vals['gifts']);
                }
            }
            $ary_price_data['all_pdt_price'] = sprintf("%0.2f", D('Cart')->getAllPrice($ary_db_carts));
            if (is_array($ary_db_carts) && !empty($ary_db_carts)) {
                $ary_cart_res = D('Cart')->getProductInfo($ary_db_carts);
            }
            $ary_pdt_info['rule_info'] = array('pmn_id' => null, 'again_discount' => null);
            foreach ($ary_cart_res as $info) {
                if (!empty($info['rule_info']['pmn_id']) && empty($ary_pdt_info['rule_info']['pmn_id'])) {
                    $ary_pdt_info['rule_info'] = $info['rule_info'];
                }
                if ($info['type'] != 3) {
                    $info['pdt_rule_name'] = $info['rule_info']['name'];
                    $ary_pdt_info[$info['pdt_id']] = array('pdt_id' => $info['pdt_id'], 'num' => $info['pdt_nums'], 'type' => $info['type'], 'price' => $info['f_price']);
                }
            }
            if (!empty($ary_cart_res) && is_array($ary_cart_res)) {
                foreach ($ary_cart_res as $key => $val) {
                    //自由推荐
                    if ($val[0]['type'] == '4') {
                        foreach ($val as $key => $item_info) {
                            $ary_price_data['all_price'] += $item_info['pdt_momery'];
                            $free_all_price += $item_info['pdt_momery'];
                        }
                    } else {
                        $promition_rule_name = $val['pdt_rule_name'];
                        //应付的价格（不包括运费）
                        if ($val['type'] == 0) {
                            $ary_price_data['all_price'] += $val['pdt_momery'];
                        } elseif ($val['type'] == 1) {
                            $ary_price_data['consume_point'] += intval($val['pdt_momery']); //消耗总积分
                        } elseif ($val['type'] == 3) {
                            $ary_price_data['type'] = 3;
                            $ary_price_data['all_price'] += $val['pdt_momery'];
                            $combo_all_price += $val['pdt_momery'];
                        }
                    }
                }
            }
            $ary_price_data['pre_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);

            if (isset($ary_db_carts[$int_pdt_id]) && $ary_db_carts[$int_pdt_id]['type'] == $int_good_type) {
                $ary_db_carts[$int_pdt_id]['num'] = (int) $int_pdt_nums;
                if ($int_good_type == 1) {
                    //判断会员的当前有效积分是否满足购买积分条件
                    if (false == ($flag = D('Cart')->enablePoint($ary_member['m_id'], $ary_db_carts, $info))) {
                        $this->ajaxReturn(array('status'=>false,"message"=>"会员不满足购买积分条件"));exit;
                    }
                }
                session("trdShoppingCart", $ary_db_carts);
            } else {
                $this->ajaxReturn(array('status'=>false,"message"=>"没有此货品"));exit;
            }
            $promotion_price = $ary_price_data['all_price'];
            $preferential_price = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
            //订单促销规则获取 自由组合商品不参与促销
            $ary_param = array('action' => 'cart', 'mid' => $ary_member['m_id'], 'all_price' => $ary_price_data['all_price'] - $combo_all_price - $free_all_price, 'ary_pdt' => $ary_pdt_info);
            $promotion_result = D('Price')->getOrderPrice($ary_param);
            if (!empty($promotion_result['all_price'])) {

                $cart_promition_rule_name = $promotion_result['name'];
                $preferential_price = sprintf("%0.2f", $promotion_result['all_price'] - $promotion_result['price']);
                if (!empty($ary_price_data['pre_price'])) {//组合商品优惠金额
                    $preferential_price+=$ary_price_data['pre_price'];
                }
                $promotion_price = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $preferential_price);
            }
        }
        $ary_param = array('action' => 'cart', 'mid' => $ary_member['m_id'], 'all_price' => $ary_price_data['all_price'], 'ary_pdt' => $ary_pdt_info);
        $promotion_result = D('Price')->getOrderPrice($ary_param);
        $cart_gifts_data = array();
        if (!empty($promotion_result['all_price'])) {
            if (!empty($promotion_result['gifts_pdt']) && $promotion_result['code'] == 'MZENPIN') {
                foreach ($promotion_result['gifts_pdt'] as $gifts) {
                    $ary_db_carts['gifts'][$gifts['pdt_id']] = array('pdt_id' => $gifts['pdt_id'], 'num' => 1, 'type' => 2);
                }
                $cart_gifts_data = D('Cart')->getProductInfo($ary_db_carts['gifts']);
                session("trdShoppingCart", $ary_db_carts);
            } else {
                if (isset($ary_db_carts['gifts'])) {
                    $cart_gifts_data = array();
                    unset($ary_db_carts['gifts']);
                    session("trdShoppingCart", $ary_db_carts);
                }
            }
        }
        //计算最终价格
        $promotion_total_price = '0';
        $promotion_prices = '0';
        if(!empty($pro_datas) && is_array($pro_datas)){
            foreach($pro_datas as $pkey=>$pval){
                $promotion_total_price += $pval['goods_total_price'];     //商品总价
                if($pkey != '0'){
                    $promotion_prices += $pval['pro_goods_discount'];
                }
            }
            
            $preferential_price = ( $promotion_total_price - $promotion_prices ) > 0 ? ( $promotion_total_price - $promotion_prices ) : '0.00';
        }

        if(!empty($ary_member['m_id']) && isset($ary_member['m_id'])){
            if(empty($pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['pmn_name'])){
                $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['pmn_name'] = $pro_data[$int_pdt_id]['pmn_name'];
                $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['goods_total_price'] = $pro_datas[0]['products'][$int_pdt_id]['pdt_momery'];
            }
//            echo "<pre>";print_r($pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['rule_info']['name']);exit;
            $pmn_names = $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['rule_info']['name'];
            $result = array('stauts' => true, 'price' => $preferential_price, 'pre_price' => $promotion_prices, 'promotion' => $promotion_flg, 'promotion_result_name' => $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['pmn_name'],'promotion_names'=>$pmn_names, 'cart_gifts_data' => $ary_gifts,'promotion_price'=>  sprintf("%0.2f",$pro_data[$int_pdt_id]['pdt_price'] * $pro_data[$int_pdt_id]['num']));
        }else{
            $result = array('stauts' => true, 'price' => $preferential_price, 'pre_price' => $promotion_prices, 'promotion' => $promotion_flg, 'promotion_result_name' => $promotion_result['name'], 'cart_gifts_data' => $cart_gifts_data);
        }
        
        $this->ajaxReturn($result);
    
    }

    /**
     * 清空购物车
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2012-12-28
     */
    public function doDelAll() {
        $member = session("Members");
        session('trdShoppingCart', NULL);
        $this->success(L('OPERATION_SUCCESS'), array(L('OK') => U('Ucenter/Trdorders/thdCartList')));
    }

    /**
     * 获取商品的pdt_id 删除 session 中的货品。支持批量删除。
     * 通过GET方式传递的pid可以是一个pdt_id也可以是pdt_id组成的数组
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     */
    public function doDel() {
        $member = session("Members");
        //获取货品id
        $mix_pdt_id = $this->_get("pid");
        $mix_pdt_type = $this->_get("type"); //商品类型
        if (empty($mix_pdt_id)) {
            $this->success(L('SELECT_GOOD'));
        }
        if (!empty($member['m_id'])) {
            $ary_db_carts = session("trdShoppingCart");
            if (is_array($mix_pdt_id)) {
                foreach ($mix_pdt_id as $key => $val) {
                    if ($mix_pdt_type[$key] == 2) {
                        if (isset($ary_db_carts['gifts'][$val]) && $ary_db_carts['gifts'][$val]['type'] == $mix_pdt_type[$key]) {
                            if (count($ary_db_carts['gifts']) < 2) {
                                unset($ary_db_carts['gifts']);
                            } else {
                                unset($ary_db_carts['gifts'][$val]);
                            }
                        }
                    } else {
                        if (isset($ary_db_carts[$val]) && $ary_db_carts[$val]['type'] == $mix_pdt_type[$key]) {
                            unset($ary_db_carts[$val]);
                        }
                    }
                }
            } else {
                $pdt_id = $mix_pdt_id;
                if ($mix_pdt_type == 2) {
                    if (isset($ary_db_carts['gifts'][$pdt_id]) && $ary_db_carts['gifts'][$pdt_id]['type'] == $mix_pdt_type) {
                        if (count($ary_db_carts['gifts']) < 2) {
                            unset($ary_db_carts['gifts']);
                        } else {
                            unset($ary_db_carts['gifts'][$pdt_id]);
                        }
                    }
                } else {
                    if (isset($ary_db_carts[$pdt_id]) && $ary_db_carts[$pdt_id]['type'] == $mix_pdt_type) {
                        unset($ary_db_carts[$pdt_id]);
                    }
                }
            }
            session("trdShoppingCart", $ary_db_carts);
        }
        $this->success(L('OPERATION_SUCCESS'), array(L('BACK') => U('Ucenter/Trdorders/thdCartList')));
    }

    /**
     * 订单数据验证
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-16
     */
    public function ajaxCheckOrder() {
        $result = $this->checkOrder();
        if (!empty($result)) {
            $this->success(L('订单数据验证成功'));
            return true;
        } else {
            $this->error(L('订单数据验证失败'));
            return false;
        }
    }

    /**
     * 订单数据验证
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-28
     */
    public function checkOrder() {
        $member = session("Members");
        $date = date('Y-m-d H:i:s');
        if (!empty($member['m_id'])) {
            $ary_cart = session("trdShoppingCart");
            //自由组合商品搭配分开
            $ary_product_ids = array();
            foreach ($ary_cart as $k => $ary_sub) {
                if ($ary_sub['type'] == '4') {
                    $fc_id = $ary_sub['fc_id'];
                    //判断自由组合商品是否存在或是否在有效期
                    $fc_data = M('free_collocation', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('fc_id' => $fc_id, 'fc_status' => 1))->find();
                    if (empty($fc_data)) {
                        $ary_db_carts = session("trdShoppingCart");
                        unset($ary_db_carts[$k]);
                        session("trdShoppingCart", $ary_db_carts);
                        $this->error(L('自由推荐组合已不存在'));
                        return false;
                    }
                    if ($fc_data['fc_start_time'] != '0000-00-00 00:00:00' && $date < $fc_data['fc_start_time']) {
                        $this->error(L($value['g_sn'] . '自由推荐组合活动还没有开始'));
                        return false;
                    }
                    if ($date > $fc_data['fc_end_time']) {
                        $this->error(L($value['g_sn'] . '自由推荐组合活动已结束'));
                        return false;
                    }
                    //判断自由组合商品
                    foreach ($ary_sub['pdt_id'] as $pid) {
                        $ary_product_ids[] = $pid;
                    }
                } else {
                    $ary_product_ids[] = $k;
                }
            }
            $ary_product_ids = array_unique($ary_product_ids);
            $field = array('fx_goods_products.pdt_stock', 'fx_goods_products.pdt_id', 'fx_goods.g_on_sale',
                'fx_goods.g_sn', 'fx_goods.g_gifts', 'fx_goods.g_is_combination_goods', 'fx_goods.g_pre_sale_status',
                'fx_goods_info.is_exchange', 'fx_goods_info.g_name', 'fx_goods_info.g_id', 'fx_goods_products.pdt_sale_price',
                'fx_goods_products.pdt_max_num', 'fx_goods.g_on_sale_time', 'fx_goods.g_off_sale_time');
            $where = array('fx_goods_products.pdt_id' => array('IN', $ary_product_ids));
            $data = D("GoodsProducts")->GetProductList($where, $field, $group, $limit);
            foreach ($data as $key => $value) {
                if ($value['g_on_sale'] != 1) {//上架
                    $this->error(L($value['g_sn'] . '下架商品'));
                    exit;
                    return false;
                }
                if ($ary_cart[$value['pdt_id']]['num'] > $value['pdt_stock'] && !$value['g_is_combination_goods'] && !$value['g_pre_sale_status']) {//购买数量
                    $this->error(L($value['g_sn'] . '商品库存不足'));
                    return false;
                }
                if ($value['g_is_combination_goods']) {
                    $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value['pdt_id'],$member['m_id']);
                    if ($ary_cart[$value['pdt_id']]['num'] > $tmp_stock) {
                        $this->error(L($value['g_sn'] . '组合商品库存不足'));
                        return false;
                    }
                    if ($ary_cart[$value['pdt_id']]['num'] > $value['pdt_max_num'] && $value['pdt_max_num'] > 0) {
                        //edit by Joe 组合商品数量超出最大下单数时，当前组合商品购物车情空
                        $ary_db_carts = session("trdShoppingCart");
                        unset($ary_db_carts[$value['pdt_id']]);
                        session("trdShoppingCart", $ary_db_carts);
                        $this->error(L($value['g_sn'] . '组合商品购买数不能最大于最大下单数'));
                        return false;
                    }
                    if ($value['g_on_sale_time'] != '0000-00-00 00:00:00' && $date < $value['g_on_sale_time']) {
                        $this->error(L($value['g_sn'] . '组合商品活动还没有开始'));
                        return false;
                    }
                    if ($value['g_off_sale_time'] != '0000-00-00 00:00:00' && $date > $value['g_off_sale_time']) {
                        $this->error(L($value['g_sn'] . '组合商品活动结束'));
                        return false;
                    }
                }
                if ($value['pdt_sale_price'] <= 0 && $ary_cart[$value['pdt_id']]['type'] != 1 && $value['g_gifts'] != 1) {//价格
                    $this->error(L($value['g_sn'] . '商品价格不正确'));
                    return false;
                }
                if ($value['pdt_sale_price'] < 0 && $ary_cart[$value['pdt_id']]['type'] == 1 && $value['g_gifts'] == 1) {
                    $this->error(L($value['g_sn'] . '商品价格不正确'));
                    return false;
                }
                $is_authorize = D('AuthorizeLine')->isAuthorize($member['m_id'], $value['g_id']);
                if (empty($is_authorize)) {
                    $this->error(L($value['g_name'] . '已不允许购买,请先删除'));
                    return false;
                }
            }
        } else {
            $ary_cart = (session("?trdShoppingCart")) ? session("trdShoppingCart") : array();
        }
        if (count($ary_cart) > 50) {
            $this->success(L('CART_MAX_NUM'));
            exit;
        }
        if (empty($ary_cart)) {
            $this->redirect(U('Ucenter/Products/pageList'));
            exit;
        }
        return $ary_cart;
    }

    /**
     * 第三方订单确认页
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date zhangjiasuo
     */
    public function thdpageAdd() {
        $this->getSubNav(2, 1, 1);
        $combo_all_price = 0;
        $free_all_price = 0;
        $fla_pmn_price = 0;
        $member = session("Members");
        $ary_cart = $this->checkOrder();
        $ary_tmp_cart = $ary_cart;
        foreach ($ary_cart as $value) {
            if ($value['tt_id'] != "") {
                $where['to_oid'] = $value['tt_id'];
                $ary_result = D("ThdOrders")->getTrdordersData($where);
            }
        }
		if(!empty($ary_result['to_temp_receiver_address'])){
			$ary_temp_address = json_decode($ary_result['to_temp_receiver_address'], true);
			//echo "<pre>";print_r($ary_temp_address);die;
			$cid = D("CityRegion")->getAvailableLogisticsList($ary_temp_address['to_receiver_province'], $ary_temp_address['to_receiver_city'], $ary_temp_address['to_receiver_district']);
		}else{
			$cid = D("CityRegion")->getAvailableLogisticsList($ary_result['to_receiver_province'], $ary_result['to_receiver_city'], $ary_result['to_receiver_district']);
		}
        $city_region_data = D("CityRegion")->getFullAddressId($cid);
        $to_receiver_mobile = $ary_result['to_receiver_mobile'];
        $to_receiver_phone = decrypt($ary_result['to_receiver_phone']);
        $RegExp  = "/^((13[0-9])|(14[0-9])|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
        if(!preg_match($RegExp,$to_receiver_mobile)){
            $to_receiver_mobile = (decrypt($to_receiver_mobile));
        }else{
            $to_receiver_mobile = ($to_receiver_mobile);
        }
        $ary_thdaddr = array(
			'cr_id' => isset($city_region_data[3]) ? $city_region_data[3] : $city_region_data[2],
            'm_id' => $ary_result['m_id'],
            'ra_name' => $ary_result['to_receiver_name'],
            'ra_detail' => !empty($ary_result['to_temp_receiver_address']) ? $ary_temp_address['to_receiver_address'] : $ary_result['to_receiver_address'],
            'ra_post_code' => $ary_result['to_receiver_zip'],
            'ra_phone' => $to_receiver_phone,
            'ra_mobile_phone' => $to_receiver_mobile,
            'ra_is_default' => 1,
            'ra_status' => 1,
            'address' => $ary_result['to_receiver_province'] . $ary_result['to_receiver_city'] . $ary_result['to_receiver_district']
        );
        $set_edit_js = "<script>
                        selectCityRegion('1','province','$city_region_data[1]');
                        selectCityRegion('$city_region_data[1]','city','$city_region_data[2]');
                        selectCityRegion('$city_region_data[2]','region','$city_region_data[3]');
                        </script>";
        //发票信息
        $p_invoice = D('Invoice')->get();
        $invoice_type = explode(",", $p_invoice['invoice_type']);
        $invoice_head = explode(",", $p_invoice['invoice_head']);
        $invoice_content = explode(",", $p_invoice['invoice_content']);

        $invoice_info['invoice_comom'] = $invoice_type[0];
        $invoice_info['invoice_special'] = $invoice_type[1];

        $invoice_info['invoice_personal'] = $invoice_head[0];
        $invoice_info['invoice_unit'] = $invoice_head[1];
        $invoice_info['is_invoice'] = $p_invoice['is_invoice'];
        $invoice_info['is_auto_verify'] = $p_invoice['is_auto_verify'];
        //发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($member['m_id']);
        
        //获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg();
        $ary_data['payment_cfg'] = $payment_cfg;
        
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as &$val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $val['g_id'] = $ary_gid['g_id'];
                }
            }
        }
		$pro_datas = D('Promotion')->calShopCartPro($member ['m_id'], $ary_cart);
        $subtotal = $pro_datas['subtotal']; //促销金额
        //剔除商品价格信息
        unset($pro_datas['subtotal']);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        
        // 购买货品的总价
        $ary_price_data ['all_pdt_price'] = sprintf("%0.2f", $subtotal['goods_total_sale_price']);
        $ary_data ['ary_product_data'] = D('Cart')->getProductInfo($ary_cart);
        // 获得赠送积分
        $ary_price_data ['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data ['all_pdt_price']);
        // 添加产品是否允许购买
        $is_authorize = true;

        foreach ($ary_data ['ary_product_data'] as &$prod) {
            if ($prod [0] ['type'] == '4') {
                foreach ($prod as &$item_info) {
                    $item_info ['authorize'] = D('AuthorizeLine')->isAuthorize($member ['m_id'], $item_info ['g_id']);
                    
                    if ($item_info ['authorize'] == false) {
                        $is_authorize = false;
                    }
                }
            } else {
                $prod ['authorize'] = D('AuthorizeLine')->isAuthorize($member ['m_id'], $prod ['g_id']);
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
                $ary_cart[$info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $info['g_id']);
            } else {
                //自由组合权限判断
                if ($info[0]['type'] == 4 || $info[0]['type'] == 6) {
                    foreach ($info as $subkey => $sub_info) {
                        $ary_cart[$key][$sub_info['pdt_id']] = $sub_info;
                        //添加产品是否允许购买
                        $ary_cart[$key][$sub_info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $sub_info['g_id']);
                    }
                }
            }
        }
        $promotion_total_price = '0';
        $promotion_price = '0';
        //赠品数组
        $cart_gifts = array();
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals ['products'] as $key => $val) {
                $arr_products = D('Cart')->getProductInfo(array(
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
            }
        }
        //获取赠品信息
        if (!empty($cart_gifts)) {
            $cart_gifts_data = array();
            $cart_gifts_data = D("Cart")->getProductInfo($cart_gifts);
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
        if (!empty($ary_thdaddr) && is_array($ary_thdaddr)) {
            $cr_id = $ary_thdaddr['cr_id'];
            $ary_logistic = D('Logistic')->getLogistic($cr_id,$ary_tmp_cart,$member ['m_id']);
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
        //$ary_price_data['pre_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
        //获得赠送积分
        $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price']);
        
         //是否显示发货选择
        $ary_is_show = D('SysConfig')->getCfgByModule('IS_SHOW');
        $this->assign('is_show', $ary_is_show['IS_SHOW']);
        $this->assign('ary_result', $ary_result);
        $this->assign('set_edit_js', $set_edit_js);
        $this->assign('addr', $ary_thdaddr);
        $this->assign("is_authorize", $is_authorize);
        $this->assign("cart_data", $ary_cart);
        //赠品
        $this->assign("gifts_data", $cart_gifts_data);

        $this->assign("price_data", $ary_price_data);
        //配送公司
        $this->assign('ary_logistic', $ary_logistic);
        //支付方式
        $this->assign('ary_paymentcfg', $ary_data['payment_cfg']);
        //订单实付金额
        //发票信息
        $this->assign('invoice_info', $invoice_info);
        $this->assign('invoice_content', $invoice_content);
        $this->assign("promotion", $pro_datas);
        //发票收藏列表
        $this->assign('invoice_list', $invoice_list);
        //促销优惠价
        $this->assign('fla_pmn_price', $ary_price_data ['pre_price']);
        //订单锁定
		$ary_erp = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);
        $this->assign('order_lock', $ary_erp['ORDER_LOCK']);
        $ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
         //是否显示粘贴收货地址
        $ary_is_show_address = D('SysConfig')->getCfgByModule('IS_SHOW_ADDRESS');
        $this->assign('is_show_address', $ary_is_show_address['IS_SHOW_ADDRESS']);
        $this->assign('order_time', $ary_order_time['ORDERS_TIME']);
        $this->assign("web_type", 'Trdorders');
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);
        $this->display();
    }

    /**
     * 选择物流公司
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     */
    public function ChangeLogistic() {
        $promotion_price = 0;
        $combo_all_price = 0;
        $free_all_price = 0;
        $data = $this->_post();
        $ary_cart = session("trdShoppingCart");
        $ary_member = session("Members");
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as &$val) {
                if ($val ['type'] == 0) {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array(
                                'pdt_id' => $val ['pdt_id']
                            ))->find();
                    $val ['g_id'] = $ary_gid ['g_id'];
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
                $arr_products = D("Cart")->getProductInfo(array($key => $val));
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
        
        //设置包邮额度
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
            // 购买货品的总价
            $logistic_price = D('Logistic')->getLogisticPrice($data ['lt_id'], $ary_tmp_cart,$ary_member['m_id']);
        }
        $ary_return ['status'] = 1;
        $ary_return ['goods_total_sale_price'] = $promotion_total_price;
        $ary_return ['logistic_price'] = $logistic_price;
        $ary_return ['promotion_price'] = sprintf("%0.2f", $promotion_price);
        $ary_return ['logistic_delivery'] = $logistic_info ['lc_cash_on_delivery'];
        $this->ajaxReturn($ary_return);
    }

    /**
     * 第三方平台订单提交
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-09-10
     */
    public function thddoAdd() {
        $return_orders = false;
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_orders = $this->_post();
        $member = session("Members");
        $ary_cart = session("trdShoppingCart");
		$str_code = $_SESSION['auto_code'];
		if(isset($ary_orders['originator'])) {
			if($ary_orders['originator'] == $_SESSION['auto_code']){
				//将其清除掉此时再按F5则无效
				unset($_SESSION["auto_code"]);
			}else{
				$this->error("订单提交中,请不要刷新本页面或重复提交表单");
                exit;
			}
		}
        foreach ($ary_cart as $ary) {
            //自由推荐商品
            if ($ary['type'] == 4) {
                foreach ($ary['pdt_id'] as $pdtId) {
                    $g_id = D("GoodsProducts")->where(array('pdt_id' => $pdtId))->getField('g_id');
                    $is_authorize = D('AuthorizeLine')->isAuthorize($member['m_id'], $g_id);
                    if (empty($is_authorize)) {
                        $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
                        exit;
                    }
                }
            } else {
                $g_id = D("GoodsProducts")->where(array('pdt_id' => $ary['pdt_id']))->getField('g_id');
                $is_authorize = D('AuthorizeLine')->isAuthorize($member['m_id'], $g_id);
                if (empty($is_authorize)) {
                    $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', array('返回购物车' => U('Ucenter/Cart/pageList')));
                    exit;
                }
            }
            $thd_order_data = D('ThdOrders')->where(array('to_oid' => $ary['tt_id']))->find();

            $ary_orders['o_source_id'] = $ary['tt_id'];
            $ary_orders['o_source_type'] = self::$shoptypes[$thd_order_data['to_source']];
        }
        
        //会员等级信息
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); 
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
            if (!empty($ary_orders['invoices_val']) && $ary_orders['invoices_val'] == "1") {
                if (isset($ary_orders['invoice_type']) && isset($ary_orders['invoice_head'])) {
                    $ary_orders['is_invoice'] = 1;
                    if ($ary_orders['invoice_type'] == 2) {
                        //如果为增值税发票，发票抬头默认为单位
                        $ary_orders['invoice_head'] = 2;
                    } else {
                        if ($ary_orders['invoice_head'] == 2) {
                            //如果发票类型为普通发票，并且发票抬头为单位，将个人姓名删除
                            unset($ary_orders['invoice_people']);
                        }
                        if ($ary_orders['invoice_head'] == 1) {
                            //如果发票类型为普通发票，并且发票抬头为个人，将单位删除
                            unset($ary_orders['invoice_name']);
                        }
                    }
                    if (empty($ary_orders['invoice_name'])) {
                        $ary_orders['invoice_name'] = '个人';
                    } else {
                        $ary_orders['invoice_name'] = $ary_orders['invoice_name'];
                    }
                    if (isset($ary_orders['invoice_content'])) {
                        $ary_orders['invoice_content'] = $ary_orders['invoice_content'];
                    }
                } else {

                    if (isset($ary_orders['is_default']) && !empty($ary_orders['is_default'])) {

                        $res_invoice = D('InvoiceCollect')->getid($ary_orders['is_default']);

                        if (!empty($res_invoice)) {
                            $ary_orders['is_invoice'] = 1;
                            $ary_orders['invoice_type'] = $res_invoice['invoice_type'];
                            $ary_orders['invoice_head'] = $res_invoice['invoice_head'];
                            $ary_orders['invoice_people'] = $res_invoice['invoice_people'];
                            if (empty($res_invoice['invoice_name'])) {
                                $ary_orders['invoice_name'] = '个人';
                            } else {
                                $ary_orders['invoice_name'] = $res_invoice['invoice_name'];
                            }
                            $ary_orders['invoice_content'] = $res_invoice['invoice_content'];
                            //如果是增值税发票，添加增值税发票信息

                            if ($ary_orders['invoice_type'] == 2) {
                                //纳税人识别号
                                $ary_orders['invoice_identification_number'] = $res_invoice['invoice_identification_number'];
                                //注册地址
                                $ary_orders['invoice_address'] = $res_invoice['invoice_address'];
                                //注册电话
                                $ary_orders['invoice_phone'] = $res_invoice['invoice_phone'];
                                //开户银行
                                $ary_orders['invoice_bank'] = $res_invoice['invoice_bank'];
                                //银行帐户
                                $ary_orders['invoice_account'] = $res_invoice['invoice_account'];
                            }
                        }
                    }
                    //添加增值税发票
                    if (!empty($ary_orders['in_id'])) {

                        $ary_res = M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->where(array("id" => $ary_orders['in_id']))->find();
                        $ary_orders['invoice_type'] = $ary_res['invoice_type'];
                        $ary_orders['invoice_head'] = $ary_res['invoice_head'];
                        // echo "<pre>";print_r($ary_res);exit;
                        $ary_orders['is_invoice'] = 1;
                        if (empty($ary_res['invoice_name'])) {
                            $ary_orders['invoice_name'] = '个人';
                        } else {
                            $ary_orders['invoice_name'] = $ary_orders['invoice_name'];
                        }
                        //个人姓名
                        $ary_orders['invoice_people'] = $ary_orders['invoice_people'];
                        //纳税人识别号
                        $ary_orders['invoice_identification_number'] = $ary_orders['invoice_identification_number'];
                        //注册地址
                        $ary_orders['invoice_address'] = $ary_orders['invoice_address'];
                        //注册电话
                        $ary_orders['invoice_phone'] = $ary_orders['invoice_phone'];
                        //开户银行
                        $ary_orders['invoice_bank'] = $ary_orders['invoice_bank'];
                        //银行帐户
                        $ary_orders['invoice_account'] = $ary_orders['invoice_account'];
                        $ary_orders['invoice_content'] = $ary_res['invoice_content'];
                    }
                }
            } else {
                unset($ary_orders['invoice_type']);
                unset($ary_orders['invoice_head']);
                unset($ary_orders['invoice_people']);
                unset($ary_orders['invoice_name']);
                unset($ary_orders['invoice_content']);
                unset($ary_orders['invoices_val']);
            }

            //收货人
            $ary_orders['o_receiver_name'] = $ary_orders['ra_name'];
            //收货人电话
            $ary_orders['o_receiver_telphone'] = $ary_orders['ra_phone'];
            //收货人手机
            $ary_orders['o_receiver_mobile'] = $ary_orders['ra_mobile_phone'];
            //收货人邮编
            $ary_orders['o_receiver_zipcode'] = $ary_orders['ra_post_code'];
            //收货人地址
            $ary_orders['o_receiver_address'] = $ary_orders['ra_detail'];
            //收货人省份
            $ary_orders['o_receiver_state'] = D("CityRegion")->getAddressName($ary_orders['province']);
            //收货人城市
            $ary_orders['o_receiver_city'] = D("CityRegion")->getAddressName($ary_orders['city']);
            //收货人地区
            $ary_orders['o_receiver_county'] = D("CityRegion")->getAddressName($ary_orders['region']);

           if(empty($ary_orders['o_receiver_state']) || empty($ary_orders['o_receiver_city'])){
                $this->error('收货地址信息不存在。');
                exit;		   
		   }
            unset($ary_orders['ra_name']);
            unset($ary_orders['ra_phone']);
            unset($ary_orders['ra_mobile_phone']);
            unset($ary_orders['ra_detail']);
            unset($ary_orders['ra_post_code']);
            unset($ary_orders['province']);
            unset($ary_orders['city']);
            unset($ary_orders['region']);

            //会员id
            $ary_orders['m_id'] = $member['m_id'];
            //订单id
            $ary_orders['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);

            
            if (empty($ary_orders['lt_id'])) {
                $this->success(L('SELECT_LOGISTIC'));
                exit;
            }

            //商品总价
            $ary_orders['o_goods_all_price'] = 0;
            $m_id = $_SESSION['Members']['m_id'];
            //$price = new PriceModel($m_id);
            if (!empty($ary_cart) && is_array($ary_cart)) {
                foreach ($ary_cart as $key => $val) {
                    if ($val['type'] == '0') {
                        $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                        $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                    }
                }
            }
            $pro_datas = D('Promotion')->calShopCartPro($member ['m_id'], $ary_cart);
            $subtotal = $pro_datas ['subtotal'];
            unset($pro_datas ['subtotal']);
            
            // 商品总价
            $promotion_total_price = '0';
            $promotion_price = '0';
            //赠品数组
            $gifts_cart = array();
            foreach ($pro_datas as $keys => $vals) {
                foreach ($vals['products'] as $key => $val) {
                    $arr_products = D("Cart")->getProductInfo(array($key => $val));

                    if ($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6') {
                        foreach ($arr_products[0] as &$provals) {
                            $provals['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $provals['g_id']);
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
                $ary_tmp_cart = array_merge($ary_cart,$gifts_cart);
                foreach($ary_tmp_cart as $atck=>$atcv){
                    $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                    unset($ary_tmp_cart[$atck]);
                }
            }else{
                $ary_tmp_cart = $ary_cart;
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
            $logistic_price = D("Logistic")->getLogisticPrice($ary_orders['lt_id'], $ary_tmp_cart,$member ['m_id']);
            //订单商品总价（销售价格带促销）
            $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
            //商品销售总价
            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $promotion_total_price);
            $ary_data ['ary_product_data'] = D("Cart")->getProductInfo($ary_cart);
            
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
                
            //优惠券金额
			/**
            if (isset($ary_orders['coupon_input'])) {
                $str_csn = $ary_orders['coupon_input'];
                $ary_coupon = D('Coupon')->CheckCoupon($str_csn,$ary_data['ary_product_data']);
                if (!empty($ary_coupon) && is_array($ary_coupon)) {
                    $ary_orders['o_coupon_menoy'] = $ary_coupon['c_money'];
                }
            }**/
            // 订单总价 商品会员折扣价-优惠券金额
            $all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'];
            if ($all_price <= 0) {
                $all_price = 0;
            }
            // 订单应付总价 订单总价+运费
            $all_price += $ary_orders ['o_cost_freight'];
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
                //订单优惠金额
                $ary_orders ['o_discount'] = sprintf("%0.2f", $promotion_price);
            }
            // 发货备注
            if (!empty($ary_orders ['shipping_remarks'])) {
                $ary_orders ['o_shipping_remarks'] = $ary_orders ['shipping_remarks'];
                unset($ary_orders ['shipping_remarks']);
            }
            $ary_orders_goods = D("Cart")->getProductInfo($ary_cart);
            // 管理员操作者ID
            if ($ary_member ['admin_id']) {
                $ary_orders ['o_addorder_id'] = $ary_member ['admin_id'];
            }
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
                $ary_orders['o_audit'] = 1;
            }
            //促销信息存起来
            $ary_orders['promotion'] = serialize($pro_datas);
            //订单信息入库
            $bool_orders = D('Orders')->doInsert($ary_orders);
            if (!$bool_orders) {
                $orders->rollback();
                $this->error('失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                exit;
            } else {
			/**
                if (isset($ary_coupon['c_sn'])) {
                    //更新优惠券使用
                    $ary_data = array(
                        'c_is_use' => 1,
                        'c_used_id' => $_SESSION['Members']['m_id'],
                        'c_order_id' => $ary_orders['o_id'],
                    );
                    $res_coupon = D('Coupon')->doCouponUpdate($ary_coupon['c_sn'], $ary_data);

                    if (!$res_coupon) {
                        $this->error('失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                        exit;
                    }
                }//exit;
				**/
                $ary_orders_items = array();
                $ary_orders_goods = D('Cart')->getProductInfo($ary_cart);
                if (!empty($gifts_cart)) {
                    $ary_gifts_goods = D('Cart')->getProductInfo($gifts_cart);
                    if (!empty($ary_gifts_goods)) {
                        foreach ($ary_gifts_goods as $gift) {
                            array_push($ary_orders_goods, $gift);
                        }
                    }
                }
                if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                    $total_consume_point = 0; //消耗积分
                    $int_pdt_sale_price = 0; //货品销售原价总和

                    foreach ($ary_orders_goods as $k => $v) {
                        if ($v['type'] == 3) {
                            $combo_list = D('ReletedCombinationGoods')->getComboList($v['pdt_id']);
                            if (!empty($combo_list)) {
                                foreach ($combo_list as $combo) {
                                    //订单id
                                    $ary_orders_items['o_id'] = $ary_orders['o_id'];
                                    //商品id
                                    $combo_item_data = D('GoodsProducts')->Search(array('pdt_id' => $combo['releted_pdt_id']), array('g_sn', 'g_id'));
                                    $ary_orders_items['g_id'] = $combo_item_data['g_id'];
                                    //货品id
                                    $ary_orders_items['pdt_id'] = $combo['releted_pdt_id'];
                                    //类型id
                                    $ary_orders_items['gt_id'] = $combo['gt_id'];
                                    //商品sn
                                    $ary_orders_items['g_sn'] = $combo_item_data['g_sn'];
                                    //货品sn
                                    $ary_orders_items['pdt_sn'] = $combo['pdt_sn'];
                                    //商品名字
                                    $combo_good_data = D('GoodsInfo')->Search(array('g_id' => $combo_item_data['g_id']), array('g_name'));
                                    $ary_orders_items['oi_g_name'] = $combo_good_data['g_name'];
                                    //成本价
                                    $ary_orders_items['oi_cost_price'] = $combo['pdt_cost_price'];
                                    //货品销售原价
                                    $ary_orders_items['pdt_sale_price'] = $combo['pdt_sale_price'];
                                    //购买单价
                                    $ary_orders_items['oi_price'] = $combo['com_price'];
                                    //组合商品
                                    $ary_orders_items['oi_type'] = 3;

                                    $int_pdt_sale_price += $combo['com_price'] * $combo['com_nums'];

                                    //商品数量
                                    $ary_orders_items['oi_nums'] = $combo['com_nums'] * $v['pdt_nums'];
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
                                        $this->error('失败', array('失败' => U('Ucenter/Trdorders/pageAddTrdorders')));
                                        exit;
                                    }
                                    //商品库存扣除
                                    $ary_payment_where = array('pc_id' => $ary_orders['o_payment']);
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment['pc_abbreviation'] == 'DELIVERY' || $ary_payment['pc_abbreviation'] == 'OFFLINE') {
                                        //by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $ary_orders_items['g_id']))->find();
                                        if ($good_sale_status['g_pre_sale_status'] != 1) {//如果是预售商品不扣库存
                                            $array_result = D('GoodsProducts')->UpdateStock($combo['releted_pdt_id'], $ary_orders_items['oi_nums']);
                                            if (false == $array_result["status"]) {
                                                D('GoodsProducts')->rollback();
                                                $this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            //自由推荐商品
                            if ($v[0]['type'] == '4') {
                                foreach ($v as $key => $item_info) {

                                    //订单id
                                    $ary_orders_items['o_id'] = $ary_orders['o_id'];
                                    //商品id
                                    $ary_orders_items['g_id'] = $item_info['g_id'];
                                    //货品id
                                    $ary_orders_items['pdt_id'] = $item_info['pdt_id'];
                                    //类型id
                                    $ary_orders_items['gt_id'] = $item_info['gt_id'];
                                    //商品sn
                                    $ary_orders_items['g_sn'] = $item_info['g_sn'];
                                    //o_sn
                                    //$ary_orders_items['g_id'] = $v['g_id'];
                                    //货品sn
                                    $ary_orders_items['pdt_sn'] = $item_info['pdt_sn'];
                                    //商品名字
                                    $ary_orders_items['oi_g_name'] = $item_info['g_name'];
                                    //成本价
                                    $ary_orders_items['oi_cost_price'] = $item_info['pdt_cost_price'];
                                    //货品销售原价
                                    $ary_orders_items['pdt_sale_price'] = $item_info['pdt_sale_price'];
                                    //购买单价
                                    $ary_orders_items['oi_price'] = $item_info['pdt_momery'];
                                    //商品积分
                                    if (isset($v[0]['type']) && $v[0]['type'] == 4) {
                                        $ary_orders_items['oi_type'] = 4;
                                        $int_pdt_sale_price += $item_info['pdt_sale_price'] * $item_info['pdt_nums'];
                                    }
                                    //商品数量
                                    $ary_orders_items['oi_nums'] = $item_info['pdt_nums'];
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
                                        $this->error('失败', array('失败' => U('Ucenter/Trdorders/pageAddTrdorders')));
                                        exit;
                                    }
                                    //商品库存扣除
                                    $ary_payment_where = array('pc_id' => $ary_orders['o_payment']);
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment['pc_abbreviation'] == 'DELIVERY' || $ary_payment['pc_abbreviation'] == 'OFFLINE') {
                                        //by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $item_info['g_id']))->find();
                                        if ($good_sale_status['g_pre_sale_status'] != 1) {//如果是预售商品不扣库存
                                            $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'], $item_info['pdt_nums']);
                                            if (false == $array_result["status"]) {
                                                D('GoodsProducts')->rollback();
                                                $this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (!empty($v['rule_info']['name'])) {
                                    $v['pmn_name'] = $v['rule_info']['name'];
                                }
                                //促销信息
                                foreach ($pro_datas as $vals) {
                                    foreach ($vals['products'] as $key => $val) {
                                        if (($val['type'] == $v['type']) && ($val['pdt_id'] == $v['pdt_id'])) {
                                            if (!empty($vals['pmn_name'])) {
                                                $v['pmn_name'] .= ' ' . $vals['pmn_name'];
                                            }
                                        }
                                    }
                                }
                                // 第三方价格 By Tom <helong@guanyisoft.com>
                                foreach($ary_cart as $cart){
                                    if(isset($cart['toi_id']) && !empty($cart['toi_id']) && $cart['pdt_id'] == $v['pdt_id']){
                                        $toi_price = D('thd_orders_items')->where(array('toi_id'=>$cart['toi_id']))->getField('toi_price');
                                        if(!empty($toi_price)){
                                            $ary_orders_items['oi_thd_sale_price'] = $toi_price;
                                            continue;
                                        }
                                    }
                                }
                                //订单id
                                $ary_orders_items['o_id'] = $ary_orders['o_id'];
                                //商品id
                                $ary_orders_items['g_id'] = $v['g_id'];
                                //货品id
                                $ary_orders_items['pdt_id'] = $v['pdt_id'];
                                //类型id
                                $ary_orders_items['gt_id'] = $v['gt_id'];
                                //商品sn
                                $ary_orders_items['g_sn'] = $v['g_sn'];
                                //o_sn
                                //$ary_orders_items['g_id'] = $v['g_id'];
                                //货品sn
                                $ary_orders_items['pdt_sn'] = $v['pdt_sn'];
                                //商品名字
                                $ary_orders_items['oi_g_name'] = $v['g_name'];
                                //成本价
                                $ary_orders_items['oi_cost_price'] = $v['pdt_cost_price'];
                                //货品销售原价
                                $ary_orders_items['pdt_sale_price'] = $v['pdt_sale_price'];
                                //购买单价
                                $ary_orders_items['oi_price'] = $v['pdt_price'];
                                //商品积分
                                if (isset($v['type']) && $v['type'] == 1) {
                                    $ary_orders_items['oi_score'] = $v['pdt_sale_price'];
                                    $total_consume_point += $v['pdt_sale_price'] * $v['pdt_nums'];
                                    $ary_orders_items['oi_type'] = 1;
                                } else {
                                    if (isset($v['type']) && $v['type'] == 2) {
                                        $ary_orders_items['oi_type'] = 2;
                                    }
                                    $int_pdt_sale_price += $v['pdt_sale_price'] * $v['pdt_nums'];
                                }
                                //商品数量
                                $ary_orders_items['oi_nums'] = $v['pdt_nums'];

                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $this->error('失败', array('失败' => U('Ucenter/Trdorders/pageAddTrdorders')));
                                    exit;
                                }
                                //商品库存扣除
                                $ary_payment_where = array('pc_id' => $ary_orders['o_payment']);
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment['pc_abbreviation'] == 'DELIVERY' || $ary_payment['pc_abbreviation'] == 'OFFLINE') {
                                    //by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v['g_id']))->find();
                                    if ($good_sale_status['g_pre_sale_status'] != 1) {//如果是预售商品不扣库存
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'], $v['pdt_nums']);
                                        if (false == $array_result["status"]) {
                                            D('GoodsProducts')->rollback();
                                            $this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // 产品销量
                        if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
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
                                    $this->error('失败', array(
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
                                $this->error('失败', array(
                                    '失败' => U('Ucenter/Orders/OrderFail')
                                ));
                                exit();
                            }
                        }
                    }
                    //商品下单获得总积分
                    $total_reward_point = D('PointConfig')->getrRewardPoint($int_pdt_sale_price);

                    //有消耗积分或者获得积分，消耗积分插入订单表进行冻结操作
                    if ($total_consume_point > 0 || $total_reward_point > 0) {
                        $ary_freeze_point = array(
                            'o_id' => $ary_orders['o_id'],
                            'm_id' => $_SESSION['Members']['m_id'],
                            'freeze_point' => $total_consume_point,
                            'reward_point' => $total_reward_point
                        );
                        $res_point = D('Orders')->updateFreezePoint($ary_freeze_point);
                        if (!$res_point) {
                            $this->error('更新冻结积分失败', array('失败' => U('Ucenter/Trdorders/pageAddTrdorders')));
                            exit;
                        }
                    }
                }
                // 标记第三方订单已处理
                if ($ary_orders['o_source_id']) {
                    $thdwhere['to_oid'] = $ary_orders['o_source_id'];
                }
                $thddata = array('to_tt_status' => '1');
                $ary_thdresult = D('ThdOrders')->saveTrdordersOrderHandle($thdwhere, $thddata);
                if (!$ary_thdresult) {
                    D('ThdOrders')->rollback();
                    $this->error('第三方订单处理订单失败', array('失败' => U('Ucenter/Trdorders/pageAddTrdorders')));
                    exit;
                }
            }
            //订单日志记录
            $ary_orders_log = array(
                'o_id' => $ary_orders['o_id'],
                'ol_behavior' => '创建',
                'ol_uname' => $_SESSION['Members']['m_name'],
                'ol_create' => date('Y-m-d H:i:s')
            );
            $res_orders_log = D('OrdersLog')->add($ary_orders_log);
            if (!$res_orders_log) {
                $this->error('订单日志记录失败', array('失败' => U('Ucenter/Orders/OrderFail')));
                exit;
            }
            $orders->commit();
            session('trdShoppingCart', null);
            $this->success('订单提交成功，请您尽快付款！', array('付款' => U("Ucenter/Orders/OrderSuccess", array('oid' => $order_id))));
            exit;
    }
    

    /**
     * 店铺种类
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-9-9
     */
    public static $shoptypes = array(
        '1' => 'taobao', //淘宝
        '2' => 'paipai',
        '3' => 'taobaofx', //淘宝分销
        '4' => 'paipai', //拍拍
        '5' => 'jingdong', //京东
        '6' => 'dangdang', //当当
        '8' => 'shangpaiweb', //商派网站
        '10' => 'shangpaifx', //商派网站
        '11' => 'yihaodian', //一号店
        '12' => 'vancl', //凡客
        '14' => 'amazon', //亚马逊
        '16' => 'ule', //邮乐
        '17' => 'okbuy', //好乐买
        '18' => 'wanggou', //QQ网购
        '19' => 'qqcb', //QQ彩贝
        '20' => 'alibaba', //阿里巴巴
        '21' => 'yougou', //优购
        '22' => 'newegg', //新蛋网
        '23' => 'jumei', //聚美优品
        '24' => 'tianpin', //天品网
        '25' => 'xiu', //走秀网
        '26' => 'lefeng', //乐蜂网
    );

    /**
     * 获取商品数据
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-10
     */
    public function ProductsListpage() {
        $filter = $this->_get();
        $goods = D("ViewGoods");
        $condition = array();
        //拼接搜索条件
        $ary_params = array();
        //商品分类
        $ary_params['gcid'] = (int) $this->_get('gcid', 'htmlspecialchars', 0);
        //商品名称
        $ary_params['g_name'] = $this->_get('g_name', 'htmlspecialchars,trim', '');
        //商品编码
        $ary_params['g_sn'] = $this->_get('g_sn', 'htmlspecialchars,trim', '');
        //货号
        $ary_params['pdt_sn'] = $this->_get('pdt_sn', 'htmlspecialchars,trim', '');
        $where = array();
        //商品分类搜索
        if ($ary_params['gcid']) {
            $where['fx_goods_category.gc_id'] = array('in', $goods->getCatesIds($ary_params['gcid']));
        }
        //商品名称查询
        if ($ary_params['g_name']) {
            $where['fx_goods_info.g_name'] = array('LIKE', '%' . $ary_params['g_name'] . '%');
        }
        //商品编码
        if (!empty($ary_params['g_sn'])) {
            $filde = array(' ', '\\s', '\\n', '\\r\\n', '&nbsp;', '\\r');
            $str_g_sn = str_replace($filde, ',', $ary_params['g_sn']);
            if (strpos($str_g_sn, ',') === false) {
                $where['fx_goods.g_sn'] = array('LIKE', '%' . $str_g_sn . '%');
            } else {
                $where['fx_goods.g_sn'] = array('in', $str_g_sn);
            }
        }
        //货号
        if (!empty($ary_params['pdt_sn'])) {
            $filde = array(' ', '\\s', '\\n', '\\r\\n', '&nbsp;', '\\r');
            $str_pdt_sn = str_replace($filde, ',', $ary_params['pdt_sn']);
            if (strpos($str_pdt_sn, ',') === false) {
                $condition['pdt_sn'] = array('LIKE', '%' . $str_pdt_sn . '%');
            } else {
                $condition['pdt_sn'] = array('in', $str_pdt_sn);
            }
        }
        //获取商品 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $group = 'fx_goods.g_id,fx_goods_products.pdt_id';
        $count = D('ThdGoods')->GetProductCount($where, $group);
        $this->assign('nums', $count);
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $list = D('ThdGoods')->GetProductList($where, '', $group, $limit);

        //获取货品
        $products = M("view_products", C('DB_PREFIX'), 'DB_CUSTOM');
        $goodsSpec = D('GoodsSpec');
        $authorLine = D('AuthorizeLine');
        //获取货品价格等额外数据
        $member = session('Members');
        $price = new PriceModel($member['m_id']);
        $ary_product_feild = array('fx_goods_products.pdt_sn', 'fx_goods_products.pdt_weight', 'fx_goods_products.pdt_stock', 'fx_goods_products.pdt_memo', 'fx_goods_products.pdt_id', 'fx_goods_products.pdt_sale_price', 'fx_goods_products.pdt_on_way_stock', 'fx_goods_info.g_picture');
        foreach ($list as $key => $data) {
            $condition['fx_goods.g_id'] = $data['g_id'];
            $ary_pdt = D('GoodsProducts')->GetProductList($condition, $ary_product_feild);
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                foreach ($ary_pdt as $k => $pdt) {
                    //获取其他属性
                    $ary_pdt[$k]['specName'] = $goodsSpec->getProductsSpec($pdt['pdt_id']);
                    $ary_pdt[$k]['gPrice'] = $price->getItemPrice($pdt['pdt_id']);
                }
                $list[$key]['products'] = $ary_pdt;
            }
        }
        $this->assign('search', $filter);
        $this->assign('filter', $ary_params);
        $this->assign('lists', $list);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 保存第三方手工匹配的货品到数据库
     * @return mixed array
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-09-10
     */
    public function saveTrdMatch() {
        $ary_res = array('success' => 0, 'msg' => '匹配失败！');
        $ThdOrders = D("ThdOrders");
        $ary_filter = $this->_post();
        //拼接搜索条件
        $where = array();
        $data = array();
        //淘宝商品编码
        $ary_params['num_iid'] = $this->_post('num_iid', 'htmlspecialchars', 0);
        //第三方订单ID
        $ary_params['to_id'] = $this->_post('tt_id', 'htmlspecialchars,trim', '');
        //第三方主键ID
        $ary_params['toi_id'] = $this->_post('to_id', 'htmlspecialchars,trim', '');
        //匹配的货品以及数量
        $data['b2b_pdt_sn_info'] = $ary_filter['b2b_pdt_sn_info']['pdt_sn'];
        //淘宝商品编码
        if ($ary_params['num_iid']) {
            $where['toi_num_id'] = $ary_params['num_iid'];
        }
        //第三方订单ID
        if ($ary_params['to_id']) {
            $where['to_id'] = $ary_params['to_id'];
        }
        //第三方主键ID
        if ($ary_params['toi_id']) {
            $where['toi_id'] = $ary_params['toi_id'];
        }

        if (!empty($data['b2b_pdt_sn_info']) && is_array($data['b2b_pdt_sn_info'])) {
            $ary_data = array();
            $pdt_sn = array_keys($data['b2b_pdt_sn_info']);
            $pdt_num = array_values($data['b2b_pdt_sn_info']);
            foreach ($pdt_sn as $key => $val) {
                $ary_data[$key]['pdt_sn'] = $val;
                $ary_data[$key]['num'] = $pdt_num[$key];
            }
            $data['toi_b2b_pdt_sn_info'] .= serialize($ary_data);
        }
        $ary_result = $ThdOrders->updateTrdordersOrderItem($where, $data);
        if ($ary_result) {
            $ary_res['success'] = '1';
            $ary_res['msg'] = '成功';
        }
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 标记处理
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-11
     */
    public function DoMarkHandle() {
        $ary_res = array('success' => '0', 'msg' => '标记处理失败', 'errCode');
        $ary_params = array();
        $where = array();
        //第三方订单ID
        $ary_params['tt_id'] = $this->_post('tt_id', 'htmlspecialchars', 0);
        $shop_id = $this->_post('shop_id', 'htmlspecialchars', 0);
        if ($ary_params['tt_id']) {
            $where['to_oid'] = $ary_params['tt_id'];
        }
        $ThdOrders = D("ThdOrders");
        $data = array('to_tt_status' => '1');
        $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
        if ($ary_result) {
            $this->success('标记处理成功', array('确认' => U('Ucenter/Trdorders/thdOrderList', array('tsid' => $shop_id))));
        } else {
            $this->error($ary_res['msg']);
        }
    }

    /**
     * 标记处理
     * @return mixed array
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @modify 2013-09-11
     */
    public function DoBatchMarkHandle() {
        $ary_res = array('success' => '0', 'msg' => '标记处理失败', 'errCode');
        $erri = 0;
        $succi = 0;
        $ThdOrders = D("ThdOrders");
        //第三方订单ID
        $ary_ttid = explode(",", trim($this->_post('tt_id', 'htmlspecialchars', 0), ","));
        $shop_id = $this->_post('shop_id', 'htmlspecialchars', 0);
        if (!empty($ary_ttid) && is_array($ary_ttid)) {
            foreach ($ary_ttid as $vttid) {
                if (!empty($vttid)) {
                    $where['to_oid'] = $vttid;
                }
                $data = array('to_tt_status' => '1');
                $ary_result = $ThdOrders->saveTrdordersOrderHandle($where, $data);
                if ($ary_result) {
                    $succi++;
                } else {
                    $erri++;
                }
            }
            $ary_res['msg'] = '处理成功 <font color="green">' . $succi . '</font> 条<br />处理失败 <font color="red">' . $erri . "</font> 条";
            $this->success($ary_res['msg'], array('确认' => U('Ucenter/Trdorders/thdOrderList', array('tsid' => $shop_id))));
        }
    }

    /**
     * 配送方式页面
     * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
     * @date 2013-09-13
     */
    public function getLogisticType() {
        $cr_id = $this->_post('cr_id');
        $ary_pid = $this->_post();
        unset($ary_pid['cr_id']);
        $ary_member = session("Members");
        $ary_cart = session("trdShoppingCart");
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
        $ary_logistic = D("Logistic")->getLogistic($cr_id,$ary_tmp_cart,$ary_member['m_id']);
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
	 * 异步请求第三方订单表修改收货人地址
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-21
	*/
	public function editReceiveAddress() {
		$param = $this->_post();
		$param['to_receiver_address'] = trim(($param['to_receiver_address']));
		if($param['to_receiver_province'] == '请选择' || $param['to_receiver_city'] == '请选择'){
			$this->ajaxReturn(array('status'=>false, 'msg'=>'修改收货地址失败！'));exit;
		}
		$json_data = json_encode($param);
		$to_id = D("ThdOrders")->where(array('to_id'=>$param['to_id']))->getField("to_id");
		if(!empty($to_id)){
			$result = D("ThdOrders")->where(array('to_id'=>$param['to_id']))->save(array('to_temp_receiver_address'=>$json_data));
		}else{
			$result = D("ThdOrders")->where(array('to_id'=>$param['to_id']))->add(array('to_temp_receiver_address'=>$json_data));
		}
		if(FALSE !== $result){
			$this->ajaxReturn(array('status'=>true, 'msg'=>'修改收货地址成功！'));exit;
		}else{
			$this->ajaxReturn(array('status'=>false, 'msg'=>'修改收货地址失败！'));exit;
		}
	}
	
	/**
	 * 淘宝订单快速下载获取三级联动地址
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-22
	*/
	public function thdAddressPage() {
		$thd_to_oid['to_oid'] = $this->_post('to_oid');
		$ary_result = D("ThdOrders")->getTrdordersData($thd_to_oid);
		if(!empty($ary_result['to_temp_receiver_address'])){
			$ary_result = json_decode($ary_result['to_temp_receiver_address'], true);
		}
		$cid = D("CityRegion")->getAvailableLogisticsList($ary_result['to_receiver_province'], $ary_result['to_receiver_city'], $ary_result['to_receiver_district']);
		$city_region_data = D("CityRegion")->getFullAddressId($cid);
		$set_edit_js .= "selectCityRegion('1','province_'" . '+' . $thd_to_oid['to_oid'] . ",'$city_region_data[1]');";
		$set_edit_js .=	"selectCityRegion('$city_region_data[1]','city_'" . '+' . $thd_to_oid['to_oid'] . ",'$city_region_data[2]');";
		$set_edit_js .= "selectCityRegion('$city_region_data[2]','region_'" . '+' . $thd_to_oid['to_oid'] . ",'$city_region_data[3]');";
		$this->assign('set_edit_js',$set_edit_js);
		$this->assign('to_receiver_address',$ary_result['to_receiver_address']);
		$this->assign('tt_id', $thd_to_oid['to_oid']);
		$this->display();
	}
	
	/**
	 * 异步更改第三方卖家备注/旗帜到淘宝平台  /同步旗帜到淘宝平台
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-21
	 * 
	*/
	public function updateMemo() {
		$ary_post = $this->_post();
        $member = session("Members");
        $obj_shops = D("ThdShops");
        $arr_where = array();
        $arr_where['ts_id'] = (int) $ary_post['ts_id'];
        $arr_where['m_id'] = $member['m_id'];
        $arr_where['ts_source'] = '1';
        if (!empty($ary_post['data']) && $ary_post['data'] == 'paipai') {
            $arr_where['ts_source'] = '2';
        }
        $arr_result = $obj_shops->where($arr_where)->find();
        if (!empty($ary_post['data'])) {
            $str_platform = $ary_post['data'];
        }
        if(!empty($ary_post['seller_flag'])){
			if(is_array($ary_post['to_oid'])){
				foreach($ary_post['to_oid'] as $tkey=>$to_oid){
					if($ary_post['seller_flag'][$tkey]){
						$where = array();
						$where['to_oid'] = array("EQ",$to_oid);
						$return = D('ThdOrders')->where($where)->save(array('to_temp_seller_flag'=>$ary_post['seller_flag']));
						if(FALSE === $return){
						   // $this->ajaxReturn(array('status'=>false, 'msg'=>'分销保存旗帜失败！','Error_Code'=>'TrdordersAction_updateMemo_1'));
						}					
					}
						
				}
			}else{
				$where = array();
				$where['to_oid'] = array("EQ",$ary_post['to_oid']);
				$return = D('ThdOrders')->where($where)->save(array('to_temp_seller_flag'=>$ary_post['seller_flag']));
				if(FALSE === $return){
				   // $this->ajaxReturn(array('status'=>false, 'msg'=>'分销保存旗帜失败！','Error_Code'=>'TrdordersAction_updateMemo_1'));
				}				
			}
        }
        if(!empty($ary_post['memo'])){
            $where = array();
            $where['to_oid'] = array("EQ",$ary_post['to_oid']);
            $return = D('ThdOrders')->where($where)->save(array('to_seller_memo'=>$ary_post['memo']));
            if(FALSE === $return){
               // $this->ajaxReturn(array('status'=>false, 'msg'=>'分销保存旗帜失败！','Error_Code'=>'TrdordersAction_updateMemo_1'));
            }
        }
		$ary_token = json_decode($arr_result['ts_shop_token'], true);
        $obj_api = Apis::factory($str_platform, $ary_token);
		if(is_array($ary_post['to_oid'])){
			foreach($ary_post['to_oid'] as $tkey=>$to_oid){
				if($ary_post['seller_flag'][$tkey]){
					$tmp_post = $ary_post;
					$tmp_post['to_oid'] = $to_oid;
					$tmp_post['seller_flag'] = $ary_post['seller_flag'][$tkey];
					$ary_return = $obj_api->updateMemo($tmp_post);
				}
			}
		}else{
			$ary_return = $obj_api->updateMemo($ary_post);
		}
		if($ary_return){
			$this->ajaxReturn(array('status'=>true, 'msg'=>'更新备注成功！'));
		}else{
			$this->ajaxReturn(array('status'=>false, 'msg'=>'更新备注失败！'));
		}
	}
	
	/**
	 * 异步请求批量修改旗帜
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-23
	*/
	public function batchEditFlag() {
		$ary_data = $this->_post();
		$where = array();
		$where['to_oid'] = array("IN",$ary_data['to_oid']);
		unset($ary_data['to_oid']);
		$return = D('ThdOrders')->where($where)->save($ary_data);
		if($return){
			$this->ajaxReturn(array('status'=>true, 'msg'=>'批量修改成功！'));
		}else{
			$this->ajaxReturn(array('status'=>false, 'msg'=>'批量修改失败！'));
		}
	}
	
	/**
     * 获取可用的物流列表
     * @return mixed array
     * @modify 2013-1-12
     */
    public function batchGetAvailableLogisticsList() {
        $ary_res = array('success' => '0', 'msg' => '匹配物流失败!');
        $ary_post = $this->_post();
        //获取第三方订单信息
        $ThdOrders = D("ThdOrders");
        $ary_member = session("Members");
        //会员等级信息
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); 
        //第三方订单ID
        $ary_params['to_id'] = $this->_post('tt_id', 'htmlspecialchars,trim', '');
        if ($ary_params['to_id']) {
            $where['to_oid'] = $ary_params['to_id'];
        }
		//获取订单的配送信息
        $ary_delivery_info = $ThdOrders->where($where)->find();
		//处理收货人地址
		if(isset($ary_delivery_info['to_temp_receiver_address']) && !empty($ary_delivery_info['to_temp_receiver_address'])){
			$ary_to_temp_receiver_address = json_decode($ary_delivery_info['to_temp_receiver_address'], true);
			//收货人省份
			$ary_post['province'] = $ary_to_temp_receiver_address['to_receiver_province'];
			//收货人城市
			$ary_post['city'] = $ary_to_temp_receiver_address['to_receiver_city'];
			//收货人地区
			$ary_post['district'] = $ary_to_temp_receiver_address['to_receiver_district'];
			//收货人地址
			//$ary_post['to_receiver_address'] = $ary_to_temp_receiver_address['to_receiver_address'];
		}
        $city = D("CityRegion");
		//获取所有省份
        $ary_city = $city->getCurrLvItem(1);
        //根据第三方订单的地址匹配本地的地址库获取最后一级地址cr_id;
		$cid = $city->getAvailableLogisticsList($ary_post['province'], $ary_post['city'], $ary_post['district']);
		if ($cid < 1) {
            $ary_res['success'] = 2;
            $ary_res['msg'] = '';
        } else {
			//核对物流匹配
			$tag = $this->checkIsBatch($cid, $ary_post['lc_id']);
            $ary_available_logistics = $city->getAvailableListById($cid);
            if (count($ary_available_logistics) < 1) {
                $ary_res['success'] = 3;
                $ary_res['msg'] = '暂无匹配的物流公司';
            } else {
                $ary_logistic_type = D('LogisticType')->where(array('lc_id'=>$ary_post['lc_id']))->select();
                foreach ($ary_available_logistics as $ary_logistics) {
                    foreach($ary_logistic_type as $logistic_type){
    					if($ary_logistics['lt_id'] == $logistic_type['lt_id']){
    						$ary_goods_configure = $ary_post['goods_info'];
    						$fl_logistics_cost = $city->getActualLogisticsFreight($ary_logistics['lt_expressions'], $ary_goods_configure['pdt_num'], $ary_goods_configure['pdt_weight'], $ary_goods_configure['pdt_price']);
                            //判断会员等级是否包邮
                            if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                                $fl_logistics_cost = 0;
                            }
                            
                            //判断是否设置包邮额度
                            $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $ary_post['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
                            $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                            if(!empty($lt_expressions['logistics_configure']) && $ary_goods_configure['pdt_price'] >= $lt_expressions['logistics_configure']){
                                $fl_logistics_cost = 0;
                            }
                            $ary_logistics['lt_expressions']['total_freight_cost'] = $fl_logistics_cost;
    						$ary_logistics['dca_configure_json'] = htmlspecialchars(json_encode($ary_logistics['lt_expressions']));
    						$cost = $ary_logistics['lt_expressions']['total_freight_cost'];
    						$html = '';
    						$html .= "
    							<div class='selectLog'
    							total_freight_cost='".$ary_logistics[lt_expressions][total_freight_cost]."'
    							weight='".$ary_logistics[lt_expressions][total_freight_cost]."'
    							lc_name='$ary_logistics[lc_name]'
    							title='$ary_logistics[lc_name]'
    							lt_id='$ary_logistics[lt_id]'
    							conf='$ary_logistics[dca_configure_json]'
    							>
    						</div>
    						";
    						echo $html;die();
    					}
                    }
                }
            }
        }
    }
	
	/**
	 * 核对物流是否配送该区域
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-25
	 * @param int $lc_id 物流公司ID
	 * @param int $cr_id 城市区域id
	*/
	public function checkIsBatch($cid, $lc_id) {
		$full_address_id = D("CityRegion")->getFullAddressId($cid);
		unset($full_address_id[0]);
		foreach($full_address_id as $k=>$v){
			//检测物流是否配送该区域
			$result = $this->batch($v, $lc_id);
			if(false === $result){
				if(3 == $k){
					if(false === $result){
						echo 'error';
						exit;
					}
				}
				continue;
			}else{
				return true;
			}
		}
	}
	
	/**
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
     * @modify by Tom <helong@guanyisoft.com> <2014-11-10>
	 * @date 2014-4-26
	*/
	private function batch($cid, $lc_id) {
		$ary_region = array();
        $ary_logistic_type = D('LogisticType')->where(array('lc_id'=>$lc_id))->select();
        $tag = false;
        foreach($ary_logistic_type as $logistic_type){
            $ary_region_id = D("RelatedLogisticCity")->field("cr_id")->where(array('lt_id'=>$logistic_type['lt_id']))->select();
            foreach($ary_region_id as $v){
                $ary_region[] = $v['cr_id'];
            }
            if(in_array($cid,$ary_region) || in_array(1,$ary_region)){
                $tag = true;
                break;
            }
            // if(!in_array(1,$ary_region)){
            //     if(!in_array($cid, $ary_region)){
            //         $tag = false;
            //     }
            // }
            // return true;
        }
        return $tag;
	}
	
	/**
	 * 根据输入的值 物流费用随着改变
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-8
	*/
	public function getLogisFree(){
		$ary_post = $this->_post();
        $member = session("Members");
        //会员等级信息
        $User_Grade = D('MembersLevel')->getMembersLevels($member['ml_id']); 
		if(!empty($ary_post)){
			$ary_logistic = array();
			foreach($ary_post as $v){
                $ary_logistic['logistics'] = $v['logistics'];
				$ary_logistic['log_conf'] = json_decode($v['log_conf'], true);
				$ary_logistic['pdt_weight'] = $v['pdt_weight'];
				$ary_logistic['pdt_num'] = $v['pdt_num'];
                $ary_logistic['pdt_price'] = $v['pdt_price'];
			}
			unset($ary_logistic['total_freight_cost']);
		}
		$city = D("CityRegion");
		$fl_logistics_cost = $city->getActualLogisticsFreight($ary_logistic['log_conf'], $ary_logistic['pdt_num'], $ary_logistic['pdt_weight']);
		
        //判断会员等级是否包邮
        if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
            $this->ajaxReturn(array('success'=>true, 'data'=>0));
        }
        //判断是否设置包邮额度
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $ary_logistic['logistics']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $ary_logistic['pdt_price'] >= $lt_expressions['logistics_configure']){
            $this->ajaxReturn(array('success'=>true, 'data'=>0));
        }
        if($fl_logistics_cost){
			$this->ajaxReturn(array('success'=>true, 'data'=>$fl_logistics_cost));
		}else{
			$this->ajaxReturn(array('success'=>false, 'data'=>0));
		}
	}
    
     /**
     * 使用优惠券
     * 
     * @author listen
     * @date 2013-02-27
     */
    public function trdDoCoupon() {
        $str_csn = $this->_post('csn');
        $lt_id = $this->_post('lt_id');
        $ary_cart = session("trdShoppingCart");
        $ary_tmp_cart = $ary_cart;
        $ary_member = session("Members");
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as &$val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $val['g_id'] = $ary_gid['g_id'];
                }
            }
        }
        $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $ary_cart);
        $subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);
        $ary_data ['ary_product_data'] = D("Cart")->getProductInfo($ary_cart);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        $ary_promotion = array();
        $promotion_total_price = '0';
        $promotion_price = '0';
        
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals['products'] as $key => $val) {
                $arr_products = D("Cart")->getProductInfo(array($key => $val));
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
        if (isset($str_csn)) {
            $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
            $date = date('Y-m-d H:i:s');
            if (empty($ary_coupon) || !is_array($ary_coupon)) {
                $ary_res ['errMsg'] = '优惠券编号错误或已使用或不满足使用条件';
                $ary_res ['success'] = 0;
            } else {
                if ($ary_coupon ['c_condition_money'] > 0 && $ary_res ['all_price'] < $ary_coupon ['c_condition_money']) {
                    $ary_res ['errMsg'] = '优惠券不满足使用条件';
                    $ary_res ['success'] = 0;
                } elseif ($ary_coupon ['c_is_use'] == 1 || $ary_coupon ['c_used_id'] != 0) {
                    $ary_res ['errMsg'] = '优惠券被使用';
                    $ary_res ['success'] = 0;
                } elseif ($ary_coupon ['c_start_time'] > $date) {
                    $ary_res ['errMsg'] = '优惠券不能使用';
                    $ary_res ['success'] = 0;
                } elseif ($date > $ary_coupon ['c_end_time']) {
                    $ary_res ['errMsg'] = '活动已经结束';
                    $ary_res ['success'] = 0;
                } else {
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['coupon_price'] = $ary_coupon ['c_money'];
                }
            }
        } else {
            $ary_res ['errMsg'] = '优惠券编号错误';
            $ary_res ['success'] = 0;
        }
        $ary_res ['logistic_price'] = $logistic_price;
        $ary_res ['promotion_price'] = sprintf("%0.2f", $promotion_price);
        echo json_encode($ary_res);
        exit();
    }
    
     
    /**
     * 第三方购物车添加/减少商品时计算价格
     */
    public function checkTrdCartGoods(){
        $Cart = D('Cart');
        $ary_cart_tmp = array();
        $ary_member = session("Members");
        if(!empty($ary_member['m_id'])){
            $ary_cart = session("trdShoppingCart");
            $ary_cart_tmp = $ary_cart;
            if(!empty($ary_cart_tmp) && is_array($ary_cart_tmp)){
                foreach($ary_cart_tmp as &$val){
                    if($val['type'] == '0'){
                        $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$val['pdt_id']))->find();
                        $val['g_id'] = $ary_gid['g_id'];
                    }
                }
            }
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $ary_cart_tmp);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
            $ary_pdt = array();
            
            //购买商品总金额
            
            foreach ($ary_cart_tmp as $key => $value) {
                if ($key == 'gifts') {
                    unset($ary_cart_tmp[$key]);
                } else {
                    $pdt_id = $value['pdt_id'];
                    $int_type = isset($value['type']) ? $value['type'] : 0;
                    $ary_pdt[$key] = array('pdt_id' => $pdt_id, 'num' => $value['num'], 'type' => $int_type, 'fc_id' => $value['fc_id']);
                }
            }
            //dump($ary_pdt);die();
            if (is_array($ary_pdt) && !empty($ary_pdt)) {
                $ary_cart_data = $Cart->getProductInfo($ary_pdt);
            }
            $ary_cart = array();
            
            foreach ($ary_cart_data as $key=>$info) {
                if (isset($info['pdt_id'])) {
                    $ary_cart[$info['pdt_id']] = $info;
                    //添加产品是否允许购买
                    $ary_cart[$info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $info['g_id']);
                } else {
                    //自由组合权限判断
                    if ($info[0]['type'] == 4 || $info[0]['type'] == 6) {
                        foreach ($info as $subkey=>$sub_info) {
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
            foreach($pro_datas as $keys=>$vals){
                foreach($vals['products'] as $key=>$val){
                    $arr_products = $Cart->getProductInfo(array($key=>$val));
                    if($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6'){
                        foreach($arr_products[0] as &$provals){
                             $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                        }
                    }
                    $pro_datas[$keys]['products'][$key] =  $arr_products[0];
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
                if($keys != '0'){
                    $promotion_price += $vals['pro_goods_discount'];
                }
            }
            //获取赠品信息
            if(!empty($cart_gifts)){
            	$cart_gifts_data = array();
            	$cart_gifts_data = $Cart->getProductInfo($cart_gifts);
            }
            $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
            $ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
            $ary_price_data['all_price'] = (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) > 0 ? (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) : '0.00';
            $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price'] - $free_all_price);
            //需消耗总积分
            $ary_price_data['consume_point'] = intval($subtotal['goods_all_point']);
        }
        $this->ajaxReturn($ary_price_data);
    }
	
}
