<?php
/**
 * 确认订单控制器
 * author Nick <shanguangkun@guanyisoft.com>
 * date 2014-05-29
 */
class OrdersAction extends WapAction {

    private $orders;
    private $cityRegion;
    private $logistic;
    private $cart;
    /**
     * 订单控制器初始化
     */
    public function _initialize() {
        parent::_initialize();
        $this->orders = D('Orders');
        $this->cityRegion = D('CityRegion');
        $this->logistic = D('Logistic');
        $this->cart = D('Cart');
        $m_id = $this->getMemberInfo('m_id');
        if(empty($m_id) && !isset($m_id)){
            $string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			$this->error(L('NO_LOGIN'), U('/Wap/User/Login') . '?redirect_uri=' . urlencode($string_request_uri));
        }
    }
    
    /**
	 * 获取会员信息
	*/
	public function getMemberInfo($key = null) {
		if(empty($key)){
			return $_SESSION['Members'];
		}else{
			return $_SESSION['Members'][$key];
		}
	}
    
    
	/**
	 * 取出sesion 值
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-8
	 * return array
	*/
    public function getOrderConfirmInfo($key=null) {
		$config = session('OrderConfirmInfo');
        if(isset($config[$key])) {
            return $config[$key];
        } else {
            return $config;
        }
    }
	
     /**
     * 暂时去掉自由推荐
     * @author <wangguibin@guanyisoft.com>
     * @date 2015-10-22
     * @return type array
	 * @update by Wangguibin 
     */
	 protected function unCarts($tmp_cart_data){
		//暂时去掉自由推荐
		foreach($tmp_cart_data as $key=>$sub_cart){
			if(isset($sub_cart['type']) && $sub_cart['type'] !=0 && $sub_cart['type'] !=4){
				unset($tmp_cart_data[$key]);
			}
		}	
		return $tmp_cart_data;
	 }   
	 
    /**
     * 处理pid
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2015-10-20
     */	
	protected function getPidsStr($ary_cart){
		$tmp_pids = '';
		foreach($ary_cart as $tmp_pid=>$tmp_cart_info){
			$tmp_pids .=$tmp_pid.','; 
		}
		$tmp_pids = trim($tmp_pids,',');	
		return $tmp_pids;
	}	 
	/**
	 * 订单确认页面
	 * @param $c_id 优惠劵ID
	*/
	public function addOrderPage(){
        $m_id = $this->getMemberInfo('m_id');
        $ary_member = session("Members");
        $ary_tmp_cart = D('Cart')->ReadMycart();
        $pids = $this->_request('pid');
		$zt_type = $this->_request('zt');  //自提
		$redirect_url = '/Wap/Cart/pageCartList';
		$checkOrder = $this->cart->getCartItems($pids, $ary_member['m_id'], $gift_except=false);
		$checkOrder = $this->unCarts($checkOrder);
		//验证购物车信息
		$return_cart_res = $this->cart->checkOrder($checkOrder,$np=0,$ary_member['m_id']);
		if($return_cart_res['status'] == 1){
			$ary_cart = $return_cart_res['ary_cart'];
		}else{
			if(IS_AJAX){
				$this->ajaxReturn($return_cart_res['pdt_id'],$return_cart_res ['message'],2);
			}else{
				$this->error($return_cart_res['message'],$redirect_url);
			}
		}
		unset($return_cart_res);
		$tmp_pids = $this->getPidsStr($ary_cart);
		//要购买商品信息
        $this->assign('pids',$tmp_pids);			
		//数据处理
        $ary_data = array();
		//处理购物车信息
		$cart_data = $this->cart->handleCart($ary_cart);
		if(empty($cart_data)){
			$this->error('购物车数据有误',$redirect_url);
		}		
		//获取促销后优惠信息
		$pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data,1);		
		$subtotal = $pro_datas['subtotal']; //促销金额
        //剔除商品价格信息
        unset($pro_datas['subtotal']);	
		//获取商品详细信息
		if (is_array($cart_data) && !empty($cart_data)) {
			$ary_data ['ary_product_data'] = $this->cart->getProductInfo($cart_data,$ary_member['m_id'],1);
		}	
		//处理获取的商品信息
		$ary_cart_info = $this->cart->handleCartProductsAuthorize($ary_data['ary_product_data'],$ary_member['m_id']);
		//判断是否有商品部允许购买
		$is_authorize = $this->cart->isCartAuthorize($ary_cart_info);
        $this->assign("is_authorize", $is_authorize);		
		//处理通过促销获取的优惠信息
		$tmp_pro_datas = $this->cart->handleProdatas($pro_datas,$ary_cart_info);
		//处理pro_datas信息
		$pro_datas = $tmp_pro_datas['pro_datas'];
		//获取促销信息
		$pro_data = $tmp_pro_datas['pro_data'];
		//参数促销名称
		$promotion_name = $tmp_pro_datas['promotion_name'];
		if($promotion_name){
			$this->assign("promotion_name", $promotion_name);
		}
		//dump($pro_datas);die();
		//获取每个商品促销信息
		$ary_cart_info = $this->cart->handleCartName($pro_data,$ary_cart_info);		
		//获取赠品信息
		$cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
		//跨境贸易
		$total_tax_rate = $tmp_pro_datas['total_tax_rate'];
		if(!empty($total_tax_rate)){
			$this->assign("total_tax_rate", $total_tax_rate);
		}
		//获取订单总金额
		$ary_price_data = $this->cart->getPriceData($tmp_pro_datas,$subtotal,$cart_gifts_data);
		unset($tmp_pro_datas);				
		//获取发票信息
		$ary_invoice = $this->cart->getInvoiceData($ary_member ['m_id']);
        // 发票信息
        $this->assign('invoice_info', $ary_invoice['invoice_info']);
        $this->assign('invoice_content', $ary_invoice['invoice_content']);
        // 发票收藏列表
        $this->assign('invoice_list', $ary_invoice['invoice_list']);
		//获取会员物流方式
        // 获取常用收货地址
		$raid = $this->_request('raid');

        $tmp_ary_logitic = $this->cart->getMembersLogistic($ary_member['m_id'],$ary_cart,$pro_datas,$ary_price_data,$raid);
		/**
        $ary_sgp = array();
        foreach($ary_cart as $ary_item) {
            if(!isset($ary_item['g_id'])) {
                $ary_item['g_id'] = D('GoodsProductsTable')->where(array(
                    'pdt_id'    =>  $ary_item['pdt_id']
                ))->getField('g_id');
            }
            $str_sgp = array(
                $ary_item['g_id'],
                $ary_item['pdt_id'],
                $ary_item['num'],
                $ary_item['type'],
            );
            $str_sgp = implode(',', $str_sgp);
            $ary_sgp[] = $str_sgp;
        }

        $tmp_ary_logistic = D('ApiOrders')->fxLogisticListGet($ary_sgp, $ary_member['m_id'], $raid);
        if(!$tmp_ary_logistic['result']) {
            $this->error($tmp_ary_logistic['message']);
        }

		$ary_data['ary_logistic'] = $tmp_ary_logistic['data'];

        //获取收货地址的区域ID (cr_id)
        $address_data = D('CityRegion')->getFindReciveAddr($raid, $ary_member['m_id']);
		$ary_data['default_addr'] = $address_data;
		//是否自提
		//是否开启自提功能
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		if($is_zt['IS_ZT']['sc_value'] == 1 ){
			if(!empty($tmp_ary_logitic['zt_logistic'])){
				$this->assign('zt_logistic',$tmp_ary_logitic['zt_logistic']);
			}
		}
		unset($tmp_ary_logitic);**/
		$ary_data['ary_addr'] = $tmp_ary_logitic['ary_addr'];
		$ary_data['default_addr'] = $tmp_ary_logitic['default_addr'];
		$ary_data['ary_logistic'] = $tmp_ary_logitic['ary_logistic'];
		$count_addr =  $tmp_ary_logitic['count_addr'];
		//是否自提
		//是否开启自提功能
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		if($is_zt['IS_ZT']['sc_value'] == 1 ){
			if(!empty($tmp_ary_logitic['zt_logistic'])){
				$this->assign('zt_logistic',$tmp_ary_logitic['zt_logistic']);	
			}
		}
		unset($tmp_ary_logitic);		
        // 页面输出
        //$this->assign('ary_addr', $ary_data ['ary_addr']);
		$this->assign("address",$ary_data ['default_addr']);
        //$this->assign('default_addr', $ary_data ['default_addr']);
        // 配送公司
        $this->assign('ary_logistic',$ary_data['ary_logistic']);				
        //更新会员等级
        D("MembersLevel")->autoUpgrade($ary_member ['m_id']);
        //是否开启重复下单提示
        $is_confirm_order = D('SysConfig')->getCfg('IS_CONFIRM_ORDER', 'IS_CONFIRM_ORDER', '0', '是否开启重复下单提示');
        $this->assign($is_confirm_order);
        $this->assign("cart_data", $ary_cart_info);
        $this->assign("promotion", $pro_datas);
        // 赠品
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        // 促销优惠价
        $this->assign('fla_pmn_price', $ary_price_data['pre_price']);		
		//获取支付方式
		$ary_data['payment_cfg'] = $this->cart->getMembersPaymentCfgWap($ary_member['m_id'],$ary_data['ary_logistic']);
		$this->assign('ary_paymentcfg', $ary_data ['payment_cfg']);
        // 订单锁定
        $ary_erp = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);
        $this->assign('order_lock', $ary_erp ['ORDER_LOCK']);
        $ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
        $ary_is_show = D('SysConfig')->getCfgByModule('IS_SHOW');
        $this->assign('is_show', $ary_is_show['IS_SHOW']);
        $ary_member = D('Members')->where(array('m_id' => $_SESSION ['Members'] ['m_id']))->find();
        //是否开启红包
        $bonus_set = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
        if ($bonus_set['BONUS_AUTO_OPEN'] == 1) {
            $this->assign('bonus', $ary_member['m_bonus']);
        }
        //是否开启优惠券
        $coupon_set = D('SysConfig')->getCfgByModule('COUPON_SET');
        $this->assign('coupon', $coupon_set['COUPON_AUTO_OPEN']);
        if($coupon_set['COUPON_AUTO_OPEN'] == 1){
            $mid = $_SESSION ['Members'] ['m_id'];
            $ary_params = array(
                'm_id' => $mid,
                'ary_pdt_id' => $pids
            );
            $ary_coupon = D('Orders')->getCheckoutAvailableCoupons($ary_params);
            $default_coupon = array();
            foreach($ary_coupon as $key => $value){//折扣券
                if($value['c_type'] == 1){
                    $default_coupon = $value;
                    break;
                }else{//现金券
                    if($value['c_money'] <= $ary_price_data['all_price'] ){
                        $default_coupon = $value;
                        break;
                    }
                }
            }
            $this->assign('default_coupon', $default_coupon);
        }
        //是否开启积分抵金
        $pointCfg = D('PointConfig')->getConfigs();
        $this->assign('point', $pointCfg['is_buy_consumed']);
		//是否开启积分最低抵用
		$this->assign('is_low_consumed',$pointCfg['is_low_consumed']);
		//积分最低抵用金额
        $this->assign('low_consumed_points',$pointCfg['low_consumed_points']);
        $this->assign('pids', $tmp_pids);
        // 订单促销规则名称$this->assign("promition_rule_name", $promition_rule_name);
        //根据当前SESSION生成随机数非法提交订单
        $code = mt_rand(0, 1000000);
        $_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
        $this->assign("auto_code", $code);
		
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_info = D('Gyfx')->selectOneCache('payment_cfg','pc_custom_name', array('pc_abbreviation'=>'DELIVERY'));
			$pay_name = $pay_info['pc_custom_name'];
		}
		$member_auth_data=D('Members')->where(array('m_id'=>$_SESSION ['Members'] ['m_id']))->field('m_real_name,m_id_card')->find();
		$this->assign('member_auth_data',$member_auth_data);
		$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);
		$this->assign('pay_name',$pay_name);	
		$this->assign('zt_type',$zt_type);	
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        $this->assign($is_foreign);


        $this->display($tpl);
    }
	
	public function saveAuthInfo() {
        $data = $this->_post();
		
        $update_data ['m_real_name'] = $data['mrealname'];
        $update_data ['m_id_card'] = $data['midcard'];
        $update_data ['m_update_time'] = date("Y-m-d H:i:s");

        $ary_res = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$_SESSION ['Members'] ['m_id']))->save($update_data);
        if (FALSE !== $ary_res) {
            $this->ajaxReturn(array(
                'status' => 1,
                'info' => '添加成功',
                "data" => $ary_res
            ));
        } else {
            $this->ajaxReturn(array(
                'status' => 0,
                'info' => '添加失败'
            ));
        }
    }
	/**
     * 使用各种促销支付
	 * $type = 
	 * 0：优惠券
	 * 1：红包
	 * 2：充值卡
	 * 4：积分
     */
    public function doPromotions() {

        $array_params = $this->_post();
        $ary_member = session("Members");
        $array_params['m_id'] = $m_id = (int)$ary_member["m_id"];
        $ary_res = D('Orders')->getAllOrderPrice($array_params);
        echo json_encode($ary_res);
        exit();
    }
	
	/**
	 * 设置配送地址和优惠券
	*/
	public function setOrderConfirmInfo() {
        $ary_post = $this->_post();
        if($this->getMemberInfo('m_id')) {
            if(!empty($ary_post['type'])) {
                D('Orders')->setOrderConfirmInfo($ary_post['type'], $ary_post['data']);
                $this->success(array('status'=>true));
            }
        }
    }

	/**
     * 查看我的优惠券
     * @date 2015-10-16
	 * @ by zhuwenwei 
     */
    public function myCouponPage() {
		$ary_pdt_id = $this->_post();
        $mid = $_SESSION ['Members'] ['m_id'];
        $ary_params = array(
            'm_id' => $mid,
            'ary_pdt_id' => $ary_pdt_id
        );
        $ary_coupon = D('Orders')->getCheckoutAvailableCoupons($ary_params);
        $this->assign('ary_coupon', $ary_coupon);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }	
	/**
     * 激活优惠劵
     * @author wanghaijun
     * @date 2014-5-8
     */
	public function activeCoupon(){
		$m_id = $this->getMemberInfo('m_id');
		$str_csn = $this->_post('csn');
        $Coupon = D('Coupon');
		
		if (empty($str_csn)) {
            $ary_res['success'] = 0;
			$ary_res['errMsg'] ='优惠券账号不能为空';
			echo json_encode($ary_res);
			exit;
        }
		if(empty($m_id) && !isset($m_id)){
            $ary_res['success'] = 0;
			$ary_res['errMsg'] ='登录超时，请重新登录';
			echo json_encode($ary_res);
			exit;
        } else {
            $data['c_user_id'] = $m_id;
        }
		
		$nowtime = time();
		$ary_count = $Coupon->where(array('c_sn'=>$str_csn,'c_user_id'=>array("in",array(0,$m_id)),'c_used_id'=>0))->limit(1)->find();
        
		if (!$ary_count) {
			$ary_res['success'] = 0;
			$ary_res['errMsg'] ='无效的优惠劵';
			echo json_encode($ary_res);
			exit;
		}else if(strtotime($ary_count['c_start_time']) > $nowtime || strtotime($ary_count['c_end_time']) < $nowtime ){
            $ary_res['success'] = 0;
            $ary_res['errMsg'] ='优惠劵已过期';
            echo json_encode($ary_res);
            exit;
            
        }else if($ary_count['c_user_id'] == $m_id){
            
            $ary_res['sucMsg'] ='使用优惠券成功';
			$ary_res['success'] = 1;
            $ary_res['data'] = $ary_count;
            echo json_encode($ary_res);
            exit;
        }
		
		$res = $Coupon->where(array('c_id'=>$ary_count['c_id']))->save($data);
		if (!$res) {
            $ary_res['errMsg'] ='使用优惠券失败';
			$ary_res['success'] = 0;
        } else {
            $ary_res['data'] = $ary_count;
            $ary_res['sucMsg'] ='使用优惠券成功';
			$ary_res['success'] = 1;
        }
		echo json_encode($ary_res);
		exit;
	}
	
	/**
	 * 使用优惠劵
	 * 
	 * @author wanghaijun
	 * @date 2014-5-8
	 */
	public function useCoupon() {
		/*
		$m_id = $this->getMemberInfo('m_id');
		if(empty($m_id) && !isset($m_id)){
            $this->redirect(U('/Wap/User/Login'));
        }
		*/
		$mid = $_SESSION['Members']['m_id'];
		$ary_where = array('c_user_id' => $mid, 'c_is_use' => 0);
		$ary_where['c_end_time'] = array('EGT', date('Y-m-d H:i:s'));
		$ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where($ary_where)->select();
		$this->assign('ary_coupon', $ary_coupon);
		
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}

    /**
     * 订单数据验证
     *
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-28
     */
    public function checkOrder($ary_tmp_cart) {
        $ary_member = session("Members");
        $date = date('Y-m-d H:i:s');
        if (!empty($ary_member ['m_id'])) {
            if($ary_tmp_cart){
                $ary_cart = $ary_tmp_cart;
            }else{
                $ary_cart = D('Cart')->ReadMycart();
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
                        $ary_db_carts = D('Cart')->ReadMycart();
                        unset($ary_db_carts [$k]);
                        D('Cart')->WriteMycart($ary_db_carts);
                        $this->error(L('自由推荐组合已不存在'));
                        return false;
                    }
                    if ($fc_data ['fc_start_time'] != '0000-00-00 00:00:00' && $date < $fc_data ['fc_start_time']) {
                        $this->error(L($ary_sub ['g_sn'] . '自由推荐组合活动还没有开始'));
                        return false;
                    }
                    if ($date > $fc_data ['fc_end_time']) {
                        $this->error(L($ary_sub ['g_sn'] . '自由推荐组合活动已结束'));
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
            $data = D("GoodsProducts")->GetProductList($where, $field);
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
                        $ary_db_carts = D('Cart')->ReadMycart();
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
        if (count($ary_cart) > 50) {
            $this->success(L('CART_MAX_NUM'));
            exit();
        }
        if (empty($ary_cart)) {
            $this->redirect(U('/Wap/Cart/pageCartList'));
            exit();
        }
        return $ary_cart;
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
     * 'type'	=>'',	   //可选，0：优惠券|1：红包|2：存储卡|4：积分
     * 'admin_id' => '',	//可选，管理员id
     * 'shipping_remarks' => '', //发货备注
     * );
     */
    public function doAdd() {
        $array_params = $this->_post();
        $array_params['resource'] = 'WAP';
     
        $ary_sgp = array();
        $ary_datas = $this->_post();
        //$ary_member = session("Members");
        $ary_datas = $this->_beforeDoAdd($ary_datas);

        if(isset($ary_datas['sp_id'])) {
            $ary_cart = session('spike_cart');
            $ary_sgp[] = array(
                $ary_cart['g_id'],$ary_cart['pdt_id'],$ary_cart['num'],'spike',$ary_cart['sp_id']
            );
        }
        elseif(isset($ary_datas['gp_id'])) {
            $ary_cart = session('bulk_cart');
            $ary_sgp[] = array(
                $ary_cart['g_id'],$ary_cart['pdt_id'],$ary_cart['num'],'bulk',$ary_cart['gp_id']
            );
        }
        elseif(isset($ary_datas['p_id'])) {
            $ary_cart = session('presale_cart');
            $ary_sgp[] = array(
                $ary_cart['g_id'],$ary_cart['pdt_id'],$ary_cart['num'],'presale',$ary_cart['p_id']
            );
        }elseif(isset($ary_datas['integral_id'])) {
            $ary_datas['g_id'] = M('goods_products')->where(array('pdt_id'=>$ary_datas['pdt_id']))->getField('g_id');
            $ary_sgp[] = array(
                $ary_datas['g_id'],$ary_datas['pdt_id'],$ary_datas['num'],'integral',$ary_datas['integral_id']
            );
			
        }else {
            if (!empty($ary_datas['m_id'])) {
                $cart_data = D('Cart')->ReadMycart();
            }else {
                $cart_data = session('Cart');
            }
//            dump($cart_data);die;
			$ary_sgp = D('Cart')->getSgp($ary_datas['goods_pids'],$cart_data);
		}
        $sgp = array();
        foreach($ary_sgp as $sgp_item) {
            $sgp[] = implode(',', $sgp_item);
        }
        $array_params = array(
            'ra_id' =>  $ary_datas['ra_id'], //地址ID (必填)
            'm_id'  =>  $ary_datas['m_id'],   //会员ID (必填)
            'ml_id' =>  $ary_datas['ml_id'],   //会员ID (必填)
            'pc_id' =>  $ary_datas['o_payment'], //支付ID (必填)
            'lt_id' =>  $ary_datas['lt_id'],  //物流ID (必填)
            'sgp'   =>  base64_encode(implode(';', $sgp)),    //g_id,pdt_id(规格ID),num,type,type_id;g_id,pdt_id(规格ID),num,type,type_id
            'resource' => 'WAP', //订单来源 (必填) (android或ios)
            'bonus' =>  $ary_datas['bonus_input'],      //可选，红包
            'cards' => '',      //可选，储值卡
            'csn'   =>  $ary_datas['coupon_input'],      //可选，优惠码
            'point' =>  $ary_datas['point_input'],      //可选，积分
            'type'	=>  '10',	       //可选，0：优惠券|1：红包|2：存储卡|4：积分
            'admin_id' => $ary_datas['admin_id'],	//可选，管理员id
            'shipping_remarks' => $ary_datas ['shipping_remarks'], //发货备注
            'o_point_money' => $ary_datas ['o_point_money'], //积分兑换金额
        );

        //发票信息

        $array_params ['is_on'] = '0';
        if (!empty($ary_datas ['invoices_val']) && $ary_datas ['invoices_val'] == "1") {//是否需要开启发票
            if (isset($ary_datas ['invoice_type']) ) {//需要开发票
                if($ary_datas ['invoice_type'] == 2){//增值税发票
                    $array_params ['invoice_head'] = 2;//增值税发票 默认抬头为单位
                    //保存增值税 发票信息
                    if (!empty($ary_datas ['in_id'])) {
                        $array_params ['is_on'] = '4';//添加增值税 发票
                    }
                }elseif($ary_datas ['invoice_type'] == 1 && isset($ary_datas ['invoice_head'])){//普通发票
                    if ($ary_datas ['invoice_head'] == 2){//普通发票且为个人
                        $array_params ['is_on'] = '1';//需要个人开发票
                    }elseif($ary_datas ['invoice_head'] == 1){
                        $array_params ['is_on'] = '2';//需要开公司发票
                    }
                }
            }else{
                if (isset($ary_datas ['is_default']) && !empty($ary_datas ['is_default']) && !isset($ary_datas ['in_id'])) {//取默认发票
                    $array_params ['is_on'] = '3';//默认发票
                }
            }
        }

        $array_params = array_merge($ary_datas, $array_params);
        $ary_config = D('SysConfig')->getConfigs("GY_CAHE");
        if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ) {//队列化开始
            $queue_name= 'gyfx';
            $queue_obj = new Queue($queue_name);
        }
        $sql_model = M('', C('DB_PREFIX'), 'DB_CUSTOM');
        $sql_model->startTrans();
        $add_order_res = D('ApiOrders')->fxOrderDoAdd($array_params);
        if($add_order_res['result']) {
            $sql_model->commit();
            $order_id = $add_order_res['data']['o_id'];
            $this->success('订单提交成功，请您尽快付款！', U("/Wap/Orders/OrderSuccess",array('oid' => $order_id )));
        }
        else {
            isset($queue_obj) && $queue_obj->unLock();
            $sql_model->rollback();
            $this->error($add_order_res['message'],"/Wap/Cart/pageCartList");
        }
    }

    private function _beforeDoAdd($ary_datas) {
        $ary_member = session("Members");
        $ary_datas ['m_id']=$ary_member['m_id'];
        $ary_datas ['ml_id']=$ary_member['ml_id'];
        $ary_datas ['admin_id']=$ary_member['admin_id'];

        if(empty($ary_datas)){
            $this->error('未知的访问请求！');
        }
        if (empty($ary_datas ['m_id'])) {
            $this->error('登录过期，请重新登录！');
        }
        $goods_pids = $ary_datas['goods_pids'];
        if(empty($goods_pids)){
            $this->error('没有要购买的商品，请重新选择商品', array('返回购物车' => U('/Wap/Cart/pageCartList')));

        }
        if (empty($ary_datas ['ra_id'])) {
            $this->error('请选择收货地址！');
        }
		$now_tome = date('Y-m-d H:i',strtotime('+2 hours'));
		if(isset($ary_datas['o_receiver_time']) && $ary_datas['o_receiver_time'] < $now_tome && $ary_datas['o_receiver_time'] != ''){
            $date = new DateTime($ary_datas['o_receiver_time']);
            if(!$date) $this->error('请选择正确的送货时间!');
            $o_receiver_time = $date->format('Y-m-d H:i:s');
            $now_time = strtotime('+2 hours');
            if(strtotime($o_receiver_time) < $now_time )
                $this->error('请选择正确的送货时间,送货时间在当前时间两小时之后!');
        }
        if(empty($ary_datas['o_payment'])){
            $this->error('请选择支付方式!');
        }
        // 配送方式
        $str_code = $_SESSION['auto_code'];
        if(isset($ary_datas['originator'])) {
            if($ary_datas['originator'] == $str_code){
                //将其清除掉此时再按F5则无效
                unset($_SESSION["auto_code"]);
            }else{
                //$this->error("订单提交中,请不要刷新本页面或重复提交表单");
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
        if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && $ary_datas['o_receiver_time'] == '' && $is_zt == 1){
            $this->error('请选择提货时间！');
        }
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
			if((empty($ary_datas ['mrealname']) && isset($ary_datas ['mrealname'])) || (empty($ary_datas ['midcard']) && isset($ary_datas ['midcard']))){
				$this->error('实名信息备案为必填选项！');
			}
		}
        return $ary_datas;
    }
    public function doAddBak(){
		//dump($_SESSION['bulk_cart']);die();
        $return_orders = false;
        $combo_all_price=0;
        $free_all_price=0;
        $ary_orders = $this->_post();

        if(empty($ary_orders['lt_id'])){
            $this->error(L('SELECT_LOGISTIC'));
            exit;
        }
        if(empty($ary_orders['o_payment'])){
            $this->error('请选择支付方式!');
            die;
        }	
		// 配送方式
        $str_code = $_SESSION['auto_code'];
        if(isset($ary_orders['originator'])) {
            if($ary_orders['originator'] == $_SESSION['auto_code']){
                //将其清除掉此时再按F5则无效
                unset($_SESSION["auto_code"]);
            }else{
                $this->error("订单提交中,请不要刷新本页面或重复提交表单","/Cart/pageCartList");
                exit;
            }
        }
        $member = session("Members");
        $m_id = $member['m_id'];

		$ary_logistic_where = array('lt_id' => $ary_orders ['lt_id']);
        $ary_field = array('lc_abbreviation_name');
		$ary_log = $this->logistic->getLogisticInfo($ary_logistic_where, $ary_field);
		if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && $ary_orders['o_receiver_time'] == '' && $is_zt == 1){
            $this->error('当选择门店提货时提货时间必填');
            exit;			
		}
        //秒杀商品每人限购1件 判断秒杀是否已结束
        if(isset($ary_orders ['sp_id'])){
            $btween_time = D('Spike')->where(array('sp_id'=>$ary_orders ['sp_id']))->field("sp_start_time,sp_end_time,sp_number,sp_now_number")->find();
            if($btween_time['sp_number'] <= $btween_time['sp_now_number']){
				$this->error("已售罄","/Wap/Spike");
                exit;
            }

            if(strtotime($btween_time['sp_start_time']) > mktime()){
				$this->error("秒杀未开始","/Wap/Spike");
                exit;
            }

            if(strtotime($btween_time['sp_end_time']) < mktime()){
				$this->error("秒杀已结束","/Wap/Spike");
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
                $this->error("秒杀限购1件","/Wap/Spike");
                exit;
            }
        }
		
        //团购判断
        if(isset($ary_orders ['gp_id'])){
            $now_count =  D('OrdersItems')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
                ->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
                ->where(array('fx_orders.o_status'=>array('neq',2),'fx_orders_items.fc_id'=>$ary_orders ['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
                ->find();
            $btween_time = D('Groupbuy')->where(array('gp_id'=>$ary_orders ['gp_id']))->field("gp_start_time,gp_end_time,gp_number,gp_now_number")->find();
            if($now_count['buy_nums'] >= $btween_time['gp_number']){
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $_SESSION['bulk_cart'] = "";
                $this->error('已售完',"/Wap/Bulk");
                exit;
            }
            if(strtotime($btween_time['gp_start_time']) > mktime()){
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('团购未开始',"/Wap/Bulk");
                exit;
            }

            if(strtotime($btween_time['gp_end_time']) < mktime()){
                if($ary_config['Memcache_stat']['sc_value']==true || C('MEMCACHE_STAT') == true ){//队列化结束
                    $queue_obj->unLock();
                }
                $this->error('团购已结束',"/Wap/Bulk");
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
        if (!empty($member['m_id'])) {
			if (isset($ary_orders ['sp_id'])) {
                // 秒杀商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id' => $ary_orders ['pdt_id'],
                    'num' => $ary_orders ['num'],
                    'sp_id' => $ary_orders ['sp_id'],
                    'type' => 7
                );				
			}else if(isset($ary_orders ['gp_id'])){
                // 团购商品
                $ary_cart [$ary_orders ['pdt_id']] = array(
                    'pdt_id' => $ary_orders ['pdt_id'],
                    'num' => $ary_orders ['num'],
                    'gp_id' => $ary_orders ['gp_id'],
                    'type' => 5
                );
            }else{
				$cart_data = D('Cart')->ReadMycart();
				$ary_pid = explode(',',$ary_orders['goods_pids']);
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
        foreach($ary_cart as $ary){
            //自由搭配商品
            if($ary['type'] == 4){
                foreach($ary['pdt_id'] as $pdtId){
                    $g_id = D("GoodsProducts")->where(array('pdt_id' => $pdtId))->getField('g_id');
                    $is_authorize = D('AuthorizeLine')->isAuthorize($member['m_id'], $g_id);
                    if(empty($is_authorize)){
                        $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', "/Cart/pageCartList");
                        
                        exit;
                    }						
                }	
            }else{
                $g_id = D("GoodsProducts")->where(array('pdt_id' => $ary['pdt_id']))->getField('g_id');
                $is_authorize = D('AuthorizeLine')->isAuthorize($member['m_id'], $g_id);
                if(empty($is_authorize)){
                    $this->error('部分商品已不允许购买,请先在购物车里删除这些商品', "/Cart/pageCartList");
                    exit;
                }				
            }
        }
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
            //获取用户使用的优惠劵信息
            $c_sn = $ary_orders['coupon_sn'] ? trim($ary_orders['coupon_sn']) : '';
            if($c_sn){
                $coupon_info = $coupon->where("c_sn='".$c_sn."'")->find();
            }else{
                $coupon_info = null;
            }
            $ary_receive_address = $this->cityRegion->getReceivingAddress($ary_datas ['m_id']);
            foreach ($ary_receive_address as $ara_k=>$ara_v){
                if($ara_v['ra_id'] == $ary_orders['ra_id']){
                    $default_address['default_addr'] = $ara_v;
                }
            }
            if (isset($default_address['default_addr']['ra_id'])) {
                //收货人
                $ary_orders['o_receiver_name'] = $default_address['default_addr']['ra_name'];
                //收货人电话
                $ary_orders['o_receiver_telphone'] = $default_address['default_addr']['ra_phone'];
                //收货人手机
                $ary_orders['o_receiver_mobile'] = $default_address['default_addr']['ra_mobile_phone'];
                //收货人邮编
                $ary_orders['o_receiver_zipcode'] = $default_address['default_addr']['ra_post_code'];
                //收货人地址
                $ary_orders['o_receiver_address'] = $default_address['default_addr']['ra_detail'];
                $ary_city_data = $this->cityRegion->getFullAddressId($default_address['default_addr']['cr_id']);

                //收货人省份
                $ary_orders['o_receiver_state'] = $this->cityRegion->getAddressName($ary_city_data[1]);

                //收货人城市
                $ary_orders['o_receiver_city'] = $this->cityRegion->getAddressName($ary_city_data[2]);

                //收货人地区
                $ary_orders['o_receiver_county'] = $this->cityRegion->getAddressName($ary_city_data[3]);
            }
        }
        
        $ary_orders['m_id'] = $member['m_id'];
        $ary_orders['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);

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
			
        $ary_orders['o_goods_all_price'] = 0;
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as $key => $val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                }
            }
        }
		if(isset($ary_orders ['sp_id'])){
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
				//dump($ary_orders ['o_discount']);die();
				$logistic_price = $ary_orders ['o_cost_freight'];
			}
		}else if(isset($ary_orders ['gp_id'])){
			//获取团购商品金额方式和普通商品不同
			$ary_orders ['o_goods_all_price'] = 0;
			$m_id = $_SESSION ['Members'] ['m_id'];
			//团购商品
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
				//订单应付总价 订单总价+运费
				$ary_orders['o_cost_freight'] = $logistic_price;
				$all_price = $ary_orders ['o_goods_all_price'];
				$all_price  += $logistic_price;
				$ary_orders['o_all_price'] = sprintf("%0.3f", $all_price);
				$ary_orders['o_buyer_comments'] = $ary_orders['o_buyer_comments'];
			}
		}else{
			$pro_datas = D('Promotion')->calShopCartPro($ary_orders['m_id'], $ary_cart);
			$subtotal = $pro_datas ['subtotal'];
			unset($pro_datas ['subtotal']);
			// 商品总价
			$promotion_total_price = '0';
			$promotion_price = '0';
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
			//物流费用
			$logistic_price = $this->logistic->getLogisticPrice($ary_orders['lt_id'], $ary_cart);			
		}
		
		$User_Grade = D('MembersLevel')->getMembersLevels($ary_datas['ml_id']); //会员等级信息
		//判断会员等级是否包邮
		if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
			$logistic_price = 0;
		}
		//物流公司设置包邮额度
		$lt_expressions = json_decode(M('logistic_type')->where(array('lt_id'=>$this->_request('lt_id')))->getField('lt_expressions'),true);
		if(!empty($lt_expressions['logistics_configure']) && $ary_orders['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
			$logistic_price = 0;
		}
		$ary_orders['o_cost_freight'] = $logistic_price;
		// 优惠券金额
		if (isset($ary_orders ['coupon_sn'])) {
			$str_csn = $ary_orders ['coupon_sn'];

			$ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);

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
						}
						$o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$coupon_all_price);
					}else{
						$o_coupon_menoy += $coupon['c_money'];
					}
				}
				$ary_orders ['o_coupon_menoy'] = $o_coupon_menoy;
                $arra_order_insert['o_coupon_menoy'] = $o_coupon_menoy;
			}
		}
        if(isset($ary_orders['point_input'])){
            //需要冻结的积分数,下面生成订单成功要加到积分商品冻结的积分上
            $freeze_point = $ary_orders['point_input'];
            $ary_data = D('PointConfig')->getConfigs();
            $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
            //积分抵扣的总金额
            $point_price = sprintf("%0.2f", (0.01/$consumed_points)*$freeze_point);
            $arra_order_insert['o_point_money'] = $point_price;
        }
		// 订单总价 商品会员折扣价-优惠券金额-红包金额-储值卡金额-金币-积分抵扣金额

		if (!isset($ary_orders ['gp_id']) && !isset($ary_orders['p_id']) && !isset($ary_orders['sp_id'])) {
			$all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'] - $point_price;
		}else{
             $all_price = $o_all_price;
		}
		if ($all_price <= 0) {
			$all_price = 0;
		}
		//订单应付总价 订单总价+运费
		$all_price  += $ary_orders['o_cost_freight'];
		$ary_orders['o_all_price'] = sprintf("%0.3f", $all_price);

		$ary_orders['o_buyer_comments'] = $ary_orders['o_buyer_comments'];
        
        if(isset($_COOKIE['yiqifa'])){
             $cookie_178=explode(":",urldecode($_COOKIE['yiqifa']));
             $campaignid_178=$cookie_178[2];
             $feedback_178=$cookie_178[3];
             $ary_orders['o_source_type']=$campaignid_178;
             $ary_orders['o_cps_code']=$feedback_178;
        } 
        //是否预售单
        if (isset($ary_orders['g_pre_sale_status']) && $ary_orders['g_pre_sale_status'] == 1) {
            $ary_orders['o_pre_sale'] = 1;
        }else{
            $ary_orders['o_pre_sale'] = 0;
        }
        if(empty($ary_orders['o_receiver_county'])){//没有区时
            unset($ary_orders['o_receiver_county']);
        }
        //发票信息
        $invoice_type = $ary_orders['invoice_type'] ? (int)$ary_orders['invoice_type'] : 0;
		
        switch ($invoice_type){
            case 0: //不需要发票
                break;
            case 1: //普通发票
                $arra_order_insert['is_invoice'] = 1;
                $arra_order_insert['invoice_head'] = $ary_orders['invoice_head'];
                $arra_order_insert['invoice_people'] = $ary_orders['invoice_people']?$ary_orders['invoice_people']:"";
                $arra_order_insert['invoice_name'] = $ary_orders['invoice_name']?$ary_orders['invoice_name']:"";
                $arra_order_insert['invoice_content'] = $ary_orders['invoice_content'];
                break;
            case 2: //增值税发票
                $arra_order_insert['is_invoice'] = 1;
                $arra_order_insert['invoice_head'] = $ary_orders['invoice_head'];
                $arra_order_insert['invoice_people'] = $ary_orders['invoice_people'];
                $arra_order_insert['invoice_name'] = $ary_orders['add_invoice_name'];
                $arra_order_insert['invoice_content'] = $ary_orders['invoice_content'];
                $arra_order_insert['invoice_identification_number'] = $ary_orders['invoice_identification_number'];
                $arra_order_insert['invoice_address'] = $ary_orders['invoice_address'];
                $arra_order_insert['invoice_phone'] = $ary_orders['invoice_phone'];
                $arra_order_insert['invoice_bank'] = $ary_orders['invoice_bank'];
                $arra_order_insert['invoice_account'] = $ary_orders['invoice_account'];
                break;
        }
        
		
        $ary_orders ['o_discount'] = 0;
        if (!isset($ary_orders ['gp_id']) && !empty($promotion_price)) {
            //订单优惠金额
            $ary_orders ['o_discount'] = sprintf("%0.2f", $promotion_price);
        }
        $arra_order_insert['o_id'] = $ary_orders['o_id'];
        $arra_order_insert['m_id'] = $ary_orders['m_id'];
        $arra_order_insert['o_pay_status'] = 0;
        $arra_order_insert['o_goods_all_price'] = $ary_orders['o_goods_all_price'];

		$arra_order_insert['o_goods_all_saleprice'] = $ary_orders['o_goods_all_saleprice'];
        $arra_order_insert['o_all_price'] = $ary_orders['o_all_price'];
        $arra_order_insert['o_discount'] = $ary_orders['o_discount'];
		if(empty($arra_order_insert['o_discount'])){
			$arra_order_insert['o_discount'] = 0;
		}
        $arra_order_insert['o_pay'] = 0.000;
        $arra_order_insert['o_pre_sale'] = $ary_orders['o_pre_sale'];
        if($coupon_info){
            $arra_order_insert['o_coupon'] = 1;
            $arra_order_insert['coupon_sn'] = $coupon_info['c_sn'];
            $arra_order_insert['coupon_value'] = $coupon_info['c_money'];
//            $arra_order_insert['o_all_price'] -= $coupon_info['c_money'];
            $arra_order_insert['coupon_start_date'] = $coupon_info['c_start_time'];
            $arra_order_insert['coupon_end_date'] = $coupon_info['c_end_time'];
        } else {
            $arra_order_insert['o_coupon'] = 0;
        }

        $arra_order_insert['o_cost_freight'] = $ary_orders['o_cost_freight'];
        $arra_order_insert['o_payment'] = $ary_orders['o_payment'];
        $arra_order_insert['o_receiver_name'] = $ary_orders['o_receiver_name'];
        $arra_order_insert['o_receiver_mobile'] = $ary_orders['o_receiver_mobile'];
        $arra_order_insert['o_receiver_telphone'] = $ary_orders['o_receiver_telphone'];
        $arra_order_insert['o_receiver_state'] = $ary_orders['o_receiver_state'];
        $arra_order_insert['o_receiver_city'] = $ary_orders['o_receiver_city'];
		if($ary_orders['o_receiver_county']!=''){
			$arra_order_insert['o_receiver_county'] = $ary_orders['o_receiver_county'];
		}
        $arra_order_insert['o_receiver_address'] = $ary_orders['o_receiver_address'];
        $arra_order_insert['ra_id'] = $default_address['default_addr']['ra_id'];
        $arra_order_insert['o_receiver_zipcode'] = $ary_orders['o_receiver_zipcode'];
        $arra_order_insert['o_create_time'] = date('Y-m-d H:i:s');
        $arra_order_insert['o_status'] = 1;
        $arra_order_insert['o_source'] = $ary_orders['o_source'];
        $arra_order_insert['lt_id'] = $ary_orders['lt_id'];
        $arra_order_insert['o_buyer_comments'] = $ary_orders['o_buyer_comments'];
		if(empty($arra_order_insert['o_buyer_comments'])){
			unset($arra_order_insert['o_buyer_comments']);
		}
        $arra_order_insert['invoice_type'] = $invoice_type;
        $arra_order_insert['o_promotion_price'] = $ary_orders['all_orders_promotion_price'];
        //无效数据
        //$arra_order_insert['promotion'] = $ary_orders['promotion'];
        $arra_order_insert['erp_id'] = $ary_orders['erp_id'];
        //无效数据end
        $arra_order_insert['o_receiver_time'] = $ary_orders['o_receiver_time'];
		if(empty($arra_order_insert['o_receiver_time'])){
			unset($arra_order_insert['o_receiver_time']);
		}
		if(isset($ary_orders['sp_id'])){
			 $arra_order_insert['oi_type'] = 7;
			 $arra_order_insert['goods_pids'] = 'spike';
		}else if(isset($ary_orders['gp_id'])){
			 $arra_order_insert['oi_type'] = 5;
			 $arra_order_insert['goods_pids'] = 'bulk';
		}else{
			$arra_order_insert['oi_type'] = 0;
		}
		// echo "<pre>";
		// print_r($arra_order_insert);die;
		$o_ip = $this->getIp();
		if(isset($o_ip)){
			$arra_order_insert['o_ip'] = $o_ip;
		}
        $bool_orders = D('Orders')->doInsert($arra_order_insert);

        if (!$bool_orders) {
			//echo D('Orders')->getLastSql();exit;
            $orders->rollback();
            $this->error('订单生成失败', "/Wap/Cart/pageCartList");
            exit;
        }else{
            $ary_orders_items = array();
            $ary_orders_goods = $this->cart->getProductInfo($ary_cart);
            if(!empty($gifts_cart)){
                $ary_gifts_goods= $this->cart->getProductInfo($gifts_cart);
                if(!empty($ary_gifts_goods)){
                    foreach($ary_gifts_goods as $gift){
                        array_push($ary_orders_goods,$gift);
                    }
                }
            }
			
            if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                $total_consume_point = 0;//消耗积分
                $int_pdt_sale_price = 0;//货品销售原价总和
				$gifts_point_reward = '0'; //有设置购商品赠积分所获取的积分数
				$gifts_point_goods_price  = '0'; //设置了购商品赠积分的商品的总价				
				//获取明细分配的金额
                $ary_orders_goods = D('OrdersItems')->getOrdersGoods($ary_orders_goods,$arra_order_insert,$ary_coupon,$pro_datas);
                foreach ($ary_orders_goods as $k => $v) {
                    if($v['type']==3){
                        $combo_list=D('ReletedCombinationGoods')->getComboList($v['pdt_id']);
                        if(!empty($combo_list)){
                            foreach ($combo_list as $combo) {
                                //订单id
                                $ary_orders_items['o_id'] = $arra_order_insert['o_id'];
                                //商品id
                                $combo_item_data=D('GoodsProducts')->Search(array('pdt_id'=>$combo['releted_pdt_id']),array('g_sn','g_id'));
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
                                $combo_good_data=D('GoodsInfo')->Search(array('g_id'=>$combo_item_data['g_id']),array('g_name'));
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
                                $ary_orders_items['oi_nums'] = $combo['com_nums']*$v['pdt_nums'];
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
                                    $this->error('组合商品订单明细生成失败', array('失败' => U('/Wap/Cart/pageCartList')));
                                    exit;
                                }
                                //商品库存扣除
                                $ary_payment_where = array('pc_id' => $ary_orders['o_payment']);
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if($ary_payment['pc_abbreviation']=='DELIVERY' || $ary_payment['pc_abbreviation']=='OFFLINE'){
                                    //by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$ary_orders_items['g_id']))->find();
                                    if($good_sale_status['g_pre_sale_status']!=1){//如果是预售商品不扣库存
                                        $array_result = D('GoodsProducts')->UpdateStock($combo['releted_pdt_id'],$ary_orders_items['oi_nums']);
                                        if(false == $array_result["status"]){
                                            D('GoodsProducts')->rollback();
                                            $this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        if($v[0]['type'] == '4'){
                            foreach($v as $key=>$item_info){
                                //订单id
                                $ary_orders_items['o_id'] = $arra_order_insert['o_id'];
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
                                    $this->error('自由推荐商品订单明细生成失败', array('失败' => U('/Wap/Cart/pageCartList')));
                                    exit;
                                }
                                //商品库存扣除
                                $ary_payment_where = array('pc_id' => $arra_order_insert['o_payment']);
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if($ary_payment['pc_abbreviation']=='DELIVERY' || $ary_payment['pc_abbreviation']=='OFFLINE'){
                                    //by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$item_info['g_id']))->find();
                                    if($good_sale_status['g_pre_sale_status']!=1){//如果是预售商品不扣库存
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'],$item_info['pdt_nums']);
                                        if(false == $array_result["status"]){
                                            D('GoodsProducts')->rollback();
                                            $this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
                                        }
                                    }
                                }	
                            }
                        } else {
							//秒杀
							if($v ['type'] == '7'){
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
                                    $this->error('订单明细新增失败', array('失败' => U('/Wap/Cart/pageCartList')));
                                    exit();
                                }
                                $retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$ary_orders['num']);
                                if (!$retun_buy_nums) {
                                    $orders->rollback();
                                    $this->error('更新秒杀量失败', array('失败' => U('/Wap/Cart/pageCartList')));
                                    exit();
                                }
								//商品库存扣除
								$ary_payment_where = array('pc_id' => $arra_order_insert['o_payment']);
								$ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
								if($ary_payment['pc_abbreviation']=='DELIVERY' || $ary_payment['pc_abbreviation']=='OFFLINE'){
									//by Mithern 扣除可下单库存生成库存调整单
									$good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$v['g_id']))->find();
									if($good_sale_status['g_pre_sale_status']!=1){//如果是预售商品不扣库存
										$array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'],$v['pdt_nums']);
										if(false == $array_result["status"]){
											D('GoodsProducts')->rollback();
											$this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
										}
									}
								}								
							}else if($v ['type'] == '5') { 
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
									$this->error('订单明细新增失败', array('失败' => U('/Wap/Cart/pageCartList')));
									exit();
								}
								
								$retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$ary_orders['num']);
                                if (!$retun_buy_nums) {
                                    $orders->rollback();
                                    $this->error('更新团购量失败', array('失败' => U('/Wap/Cart/pageCartList')));
                                    exit();
                                }
								//商品库存扣除
								$ary_payment_where = array('pc_id' => $arra_order_insert['o_payment']);
								$ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
								if($ary_payment['pc_abbreviation']=='DELIVERY' || $ary_payment['pc_abbreviation']=='OFFLINE'){
									//by Mithern 扣除可下单库存生成库存调整单
									$good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$v['g_id']))->find();
									if($good_sale_status['g_pre_sale_status']!=1){//如果是预售商品不扣库存
										$array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'],$v['pdt_nums']);
										if(false == $array_result["status"]){
											D('GoodsProducts')->rollback();
											$this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
										}
									}
								}								
							}else{
								//订单id
								$ary_orders_items['o_id'] = $arra_order_insert['o_id'];
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
								$ary_orders_items['oi_g_name'] = ($v['g_name']==null) ? '' : $v['g_name'];
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
								}
								else {
									if (isset($v['type']) && $v['type'] == 2) {
										$ary_orders_items['oi_type'] = 2;
									}else{
										$ary_orders_items['oi_type'] = 0;
									}
									$int_pdt_sale_price += $v['pdt_sale_price'] * $v['pdt_nums'];
									if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
										$gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
										$gifts_point_goods_price += $v['pdt_sale_price']*$v['pdt_nums'];
									}									
								}
								//商品数量
								$ary_orders_items['oi_nums'] = $v['pdt_nums'];
								
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
									$this->error('商品订单明细生成失败', array('失败' => U('/Wap/Cart/pageCartList')));
									exit;
								}
								//商品库存扣除
								$ary_payment_where = array('pc_id' => $arra_order_insert['o_payment']);
								$ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
								if($ary_payment['pc_abbreviation']=='DELIVERY' || $ary_payment['pc_abbreviation']=='OFFLINE'){
									//by Mithern 扣除可下单库存生成库存调整单
									$good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$v['g_id']))->find();
									if($good_sale_status['g_pre_sale_status']!=1){//如果是预售商品不扣库存
										$array_result = D('GoodsProducts')->UpdateStock($ary_orders_items['pdt_id'],$v['pdt_nums']);
										if(false == $array_result["status"]){
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
                
                //推送CPS订单数据
                if(isset($_COOKIE['yiqifa'])){
                    $yiqifa =urldecode($_COOKIE['yiqifa']);
                    include_once $_SERVER["DOCUMENT_ROOT"].'/Lib/Action/Ucenter/trunk/advertiser/Sender.php';	
                    $order = new Order();
                    $order -> setOrderNo($order_id);
                    $order_date_178=date('Y-m-d H:i:s');
                    $update_date_178=date('Y-m-d H:i:s');
                    $cookie_178=explode(":",$yiqifa);
                    $campaignid_178=$cookie_178[2];
                    $feedback_178=$cookie_178[3];

                    $order -> setOrderTime($order_date_178);      // 设置下单时间
                    $order -> setUpdateTime($update_date_178);    // 设置订单更新时间，如果没有下单时间，要提前对接人提前说明
                    $order -> setCampaignId($campaignid_178);     // 测试时使用"101"，正式上线之后活动id必须要从cookie中获取
                    $order -> setFeedback($feedback_178);		  // 测试时使用"101"，正式上线之后活动id必须要从cookie中获取
                    $order -> setFare($ary_orders['o_cost_freight']);   // 设置邮费
                    $order -> setFavorable($promotion_price);           // 设置优惠券
                    $order -> setFavorableCode("YHQ"); 
                    $order -> setOrderStatus("0");             // 设置订单状态
                    $order -> setPaymentStatus("0");   				// 设置支付状态
                    $order -> setPaymentType($ary_payment['pc_id']);		// 支付方式

                    $products = array();
                    if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
						foreach ($ary_orders_goods as $k => $v) {
						$products[$k]=new Product();
						$products[$k] -> setProductNo($v['g_id']);              // 设置商品编号
						$products[$k] -> setName($v['g_name']);                 // 设置商品名称
						$products[$k] -> setCategory("asdf");                   // 设置商品类型
						$products[$k] -> setCommissionType("");                 // 设置佣金类型，如：普通商品 佣金比例是10%、佣金编号（可自行定义然后通知双方商务）A
						$products[$k] -> setAmount($v['pdt_nums']);             // 设置商品数量
						//$products[$k] -> setPrice($v['pdt_price']);  
						$products[$k] -> setPrice(sprintf("%0.2f",$v['pdt_price']*(1-$ary_orders['o_coupon_menoy']/$ary_orders['o_goods_all_price']))); 						
						}
                    }      
                    $order -> setProducts($products);
                    //var_dump(get_object_vars($order));
                    $sender = new Sender();
                    $sender -> setOrder($order);
                    $sender -> sendOrder();						
                }
				 // 商品下单获得总积分
				$other_all_price = $int_pdt_sale_price-$gifts_point_goods_price;
				$total_reward_point = D('PointConfig')->getrRewardPoint($other_all_price);
				$total_reward_point = ceil((($ary_orders ['o_all_price']-$ary_orders ['o_cost_freight'])/$int_pdt_sale_price)*$total_reward_point);
				$total_reward_point += $gifts_point_reward;
				$total_consume_point += $freeze_point;
                //有消耗积分或者获得积分，消耗积分插入订单表进行冻结操作
                if ($total_consume_point > 0 || $total_reward_point>0) {
                    $ary_freeze_point = array(
                        'o_id' => $ary_orders['o_id'],
                        'm_id' => $_SESSION['Members']['m_id'],
                        'freeze_point' => $total_consume_point,
                        'reward_point' => $total_reward_point
                    );
                    $res_point = D('Orders')->updateFreezePoint($ary_freeze_point);
                    if (!$res_point) {
                        $this->error('更新冻结积分失败', "/Wap/Cart/pageCartList");
                        exit;
                    }
                }
            }
        }
		$date_coupon['c_used_id'] = $member['m_id'];
		$date_coupon['c_is_use'] = 1;
		$date_coupon['c_order_id'] = $ary_orders['o_id'];
		if(isset($coupon_info['c_id']) && $coupon_info['c_id']!=''){
			$coupon_res = D('Coupon')->where(array('c_id'=>$coupon_info['c_id']))->save($date_coupon);
			if(!$coupon_res) {
				$orders->rollback();
				$this->error('优惠劵使用失败', array('失败' => U('/Wap/Cart/pageCartList')));
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
            $this->error('订单日志记录失败', "/Wap/Cart/pageCartList");
            exit;
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
        if (!empty($_SESSION ['Members'] ['m_id'])) {
            $mix_pdt_id = array();
            $mix_pdt_type = array();
            foreach ($ary_cart as $key=>$val){
                $mix_pdt_id[] = $key;
                $mix_pdt_type[] = $val['type'];
            }
            D('Cart')->doUpadteOrdersCart($mix_pdt_id,$mix_pdt_type);
        } else {
            unset($_SESSION ['Cart']);
        }

        $this->success('订单提交成功，请您尽快付款！', U("/Wap/Orders/OrderSuccess",array('oid' => $order_id)));
        exit;
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
	
    public function OrderSuccess() {
        $o_id = $this->_get('oid');
        $where = array('o_id' => $o_id, 'm_id' => $_SESSION['Members']['m_id']);
        $ary_orders_info = D('Orders')->getOrdersInfo($where);
        $ary_orders = $ary_orders_info[0];
        //订单作废状态
		$ary_status = array('o_status' => $ary_orders['o_status']);
		$str_status = $this->orders->getOrderItmesStauts('o_status', $ary_status);
		$ary_orders['str_status'] = $str_status;

		//订单支付状态
		$ary_pay_status = array('o_pay_status' => $ary_orders['o_pay_status']);
		$str_pay_status = $this->orders->getOrderItmesStauts('o_pay_status', $ary_pay_status);
		$ary_orders['str_pay_status'] = $str_pay_status;
        //订单状态
		$ary_orders_status = $this->orders->getOrdersStatus($o_id);
		//退款
		$ary_orders['refund_status'] = $ary_orders_status['refund_status'];
		//退货
		$ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
		//发货
		$ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
		//退款、退货类型
		$ary_orders['refund_type'] = $ary_orders_status['refund_type'];
		
		// 当前订单类型 8:预售商品 5:团购商品，4:自由组合商品,3组合商品，2赠品， 1积分商品，0普通商品
		$oi_type = M('orders_items')->where(array(
                    'o_id' => $o_id
                ))->getField('oi_type');
		
        switch ($oi_type) {
            case '5' : // 团购商品
                // 1，判断团购商品是否开启定金支付
                $groupbuy_info = M('groupbuy')->field(array('gp_id,gp_overdue_start_time,gp_overdue_end_time,gp_deposit_price,is_deposit'))
                                              ->where(array('g_id' => $ary_orders ['g_id'],'deleted' => 0))
                                              ->find();
                // 2,当前订单是否已经支付过定金
				
                if ($groupbuy_info ['is_deposit'] == 1) {
                    $pay_type_count = M('payment_serial')->where(array('o_id' => $o_id,'ps_status' => array('neq',0)))->count();
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
        }
		//支付方式
		$ary_payment_where = array('pc_id' => $ary_orders['o_payment']);
		$ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);

        // 获取支付方式列表
        $payment = D('PaymentCfg');
        $payment_list = $payment->getPaymentList('wap');
        //下单成功页面，过滤货到付款的支付方式
        foreach($payment_list as $key=>$val){
            if(strtoupper($val['pc_abbreviation']) == 'DELIVERY') unset($payment_list[$key]);
			if(strtoupper($val['pc_abbreviation']) == 'WEIXIN'){
				$is_weixin = is_weixin();
				if($is_weixin != true){
					unset($payment_list[$key]);
				}
			}
        }
		$ary_orders['payment_name'] = $ary_payment['pc_custom_name'];
		$ary_orders['pc_abbreviation'] = $ary_payment['pc_abbreviation'];
		$ary_orders['pc_id'] = $ary_payment['pc_id'];
        if($ary_orders['str_pay_status'] == '已支付' && $ary_orders['o_pay_status'] == 1 & $ary_orders['o_status'] == 1){
            $this->redirect(U('Wap/Orders/PaymentSuccess','oid='.$o_id));
        }
//        echo "<pre>";print_r($payment_list);die();
        $this->assign('orders_goods_info', $ary_orders_info);
		$this->assign('ary_orders', $ary_orders);
		$this->assign('payment_list', $payment_list);
        //$tpl =$this->wap_theme_path . 'orderSuccess.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
    
    /**
	 * 订单支付
	 * @param int $order_id 订单ID
	 * @param ary $oreder 更新订单表数组
	 * @return boolean
	 */
    public function paymentPage() {
        $int_id = $this->_request('oid');
        $payment_id = $this->_request('o_payment');
        $pay_stat = $this->_request('typeStat');
		$pay_code = $this->_request('code');
//        dump($this->_request());die;
 //writeLog($int_id.'-'.$payment_id.'-'.$pay_stat.'-'.$pay_code.'-'.date('Y-m-d H:i:s').'<br>', "order_weixinpay.log");
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
            'fx_orders_items.g_id',
			'fx_orders.lt_id',
			'fx_orders.o_receiver_mobile',
			'fx_orders.o_receiver_name'
        );

        if (isset($int_id)) {
            $ary_orders_data = $this->orders->getOrdersData($where, $search_field);
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
                    $this->error($is_pay['msg'], U('Wap/Orders/orderDetail', array('oid' => $int_id)));
                }
                $o_pay = $ary_orders ['o_all_price'];
            } elseif ($pay_stat == 1) {
                // 团购定金支付,获取定金
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id,1);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($is_pay['msg'], U('Wap/Orders/orderDetail', array('oid' => $int_id)));
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
        }
        elseif ($ary_orders['oi_type'] == '8') {   //预售商品
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
        }
        else if($ary_orders ['oi_type'] == '7'){
			/**
            $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
            if (!$result_spike) {
                // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }**/
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }
        else {
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

        if($int_id){
            //更新最后支付时间
            $ps_update = D('PaymentSerial')
                ->data(array(
                    'ps_update_time'=>date('Y-m-d H:i:s'),
                    'pc_code'   =>  $info['pc_abbreviation'],
                ))
                ->where(array(
                    'o_id' => $int_id
                ))
                ->save();
        }
        // 线下支付进erp
        if ($info ['pc_abbreviation'] != 'OFFLINE' && $info ['pc_abbreviation'] != 'DELIVERY' && $ary_orders ['pc_abbreviation'] != 'DELIVERY') {
            $Pay = $Payment::factory($info ['pc_abbreviation'], json_decode($info ['pc_config'], true));
            $result = $Pay->pay($int_id, $ary_orders ['oi_type'], $o_pay, $pay_stat,$payment_id,$pay_code);
           
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
        }
        else {

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
        $url = U("Wap/Orders/PaymentSuccess", array(
            'oid' => $int_id
        ));
        redirect($url);
        exit();
    }
    
    /**
	 * 支付成功显示页面
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2013-04-24
	 */
	 
	public function PaymentSuccess() {
		
		$member = session("Members");
		$o_id = $this->_get('oid');
		$where = array('fx_orders.o_id' => $o_id, 'fx_orders.m_id' => $member['m_id']);
		$ary_field=array('fx_orders.o_pay','fx_orders.o_all_price','fx_orders.coupon_sn','fx_orders_items.pdt_id',
                         'fx_orders_items.oi_nums','fx_orders_items.oi_type');
		$ary_orders = $this->orders->getOrdersData($where,$ary_field);
		// 本次消费金额=支付单最后一次消费记录
        $payment_serial = M('payment_serial')->where(array('o_id' => $o_id))->order('ps_create_time desc')->select();
		$payment_price =$payment_serial[0]['ps_money'];
		$all_price =$ary_orders[0]['o_all_price'];
		$coupon_sn =$ary_orders[0]['coupon_sn'];
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        //送优惠券
        if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '0') {
            D('Coupon')->setPoinGetCoupon($ary_orders, $member['m_id']);
        }
		//账户余额
		$member_balance = D('Members')->GetBalance(array('m_id' => $_SESSION['Members']['m_id']),array('m_balance'));
		$this->assign('o_id', $o_id);
		$this->assign('balance', $member_balance['m_balance']);
		$this->assign('payment_price', $payment_price);
        //$tpl = $this->wap_theme_path . 'paymentSuccess.html';
       // echo $tpl;die;
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}
        
        
    /**
	 * 订单支付
	 * @param int $order_id 订单ID
	 * @param ary $oreder 更新订单表数组
	 * @return boolean
	 */
	public function wapPaymentPage() {
		$int_id = $this->_request('oid');
            
		$where = array();
		$where['fx_orders.o_id'] = $int_id;
		$where['fx_orders.o_pay_status'] = 0;
		$where['fx_orders.o_status'] = array(array('eq', '1'), array('eq', '3'), 'or');
		$search_field=array('fx_orders.o_all_price','fx_orders.o_payment','fx_orders.o_pay','fx_members.m_id',
                            'fx_orders.o_pay_status','fx_orders.o_reward_point','fx_orders.o_freeze_point',
                            'fx_orders_items.pdt_id','fx_orders_items.oi_nums','fx_orders_items.oi_type','fx_orders_items.g_id');
		if (isset($int_id)) {
			$ary_orders_data = $this->orders->getOrdersData($where,$search_field);
			if (empty($ary_orders_data) && count($ary_orders_data) <= 0) {
				$this->error('订单不存在或已支付'); //XXXXXXXXXXXXXXXXXXXXX
			}
			$ary_orders = $ary_orders_data[0];
		}
		$o_pay = $ary_orders['o_all_price'];
		M('', '', 'DB_CUSTOM')->startTrans();
		### 使用支付模型 支付订单 ###############################################
		$Payment = D('PaymentCfg');
		$info = $Payment->where(array('pc_id' => $ary_orders['o_payment']))->find();
		if (false == $info) {
			//支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
			M('', '', 'DB_CUSTOM')->rollback();
			$this->error('支付方式不存在，或不可用');
			exit;
		}
		
		if ($info['pc_abbreviation'] != 'OFFLINE' 
                        && $info['pc_abbreviation'] != 'DELIVERY' 
                        && $ary_orders['pc_abbreviation'] != 'DELIVERY') {

                    $Pay = $Payment::factory($info['pc_abbreviation'], json_decode($info['pc_config'], true));
                    $result = $Pay->pay($int_id);
                    writeLog($result,"order_pay.log");
                    if (!$result['result']) {
                            //支付失败了 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($result['message']);
                    }
                    M('', '', 'DB_CUSTOM')->commit();
                    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>正在跳转到支付宝。。。</title></head>
                    <body><p>正在跳转到支付宝。。。</p>';
                    echo $result['html_text'];
                    echo '</body></html>';
                    exit;
            
                }else{  //线下支付进erp
                    die('线下支付');
                    $ary_orders['o_pay_status'] = 0;
                    $result_order = $this->orders->orderPayment($int_id, $ary_orders);
                    if(!$result_order['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($result_order['message']);
                            exit;
                    }
                    M('', '', 'DB_CUSTOM')->commit();
                    $url = U("Wap/Orders/PaymentSuccess", array('oid' => $int_id));
                    redirect($url);
                    exit;
                }
	}

    /**
     * 我的订单
     *
     */
    public function orderList(){
        $member = D('WapInfo')->memberInfo();
        $ary_where = array();
        $ary_chose = array();
        $ary_chose ['o_id']  = $this->_get('o_id');
        if (isset($ary_chose ['o_id']) && $ary_chose ['o_id'] != '') {
            $ary_where ['fx_orders.o_id'] = array(
                'EQ',
                $ary_chose ['o_id']
            );
        }
        $ary_where ['fx_orders.m_id'] = $_SESSION ['Members'] ['m_id'];

        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $all_orders = $orders
            ->field("fx_orders_items.oi_ship_status,
                    fx_orders.o_pay_status,
                    fx_orders_refunds.or_refund_type,
                    fx_orders.o_create_time,
                    fx_orders.o_status,
                    fx_orders.is_evaluate")
            ->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')
            ->join("fx_orders_refunds on fx_orders_refunds.o_id = fx_orders.o_id ")
            ->group('fx_orders.o_id')
            ->where($ary_where)
            ->select();
        $order_count = array('os_1'=>0,'os_2'=>0,'os_3'=>0,'os_4'=>0,'os_5'=>0,'os_6'=>0,'os_7'=>0,'os_0'=>0,'os_9'=>0);
        foreach($all_orders as $o){
            if($o['oi_ship_status'] === '0' && $o['o_pay_status'] == 1){
                $order_count['os_1'] ++;
            }elseif($o['oi_ship_status'] == 2){
                $order_count['os_2'] ++;
            }
            if($o['o_pay_status'] == 0){
                $order_count['os_3'] ++;
            }
            if($o['or_refund_type'] == 1 || $o['or_refund_type'] == 2){
                $order_count['os_4'] ++;
            }
            $creat_time = strtotime($o['o_create_time']);
            $three_month_time = strtotime("-3 month");
            if($creat_time > $three_month_time){
                $order_count['os_5'] ++;
            }else{
                $order_count['os_6'] ++;
            }
            if($o['o_status'] == 5 && $o['is_evaluate'] == 0){
                $order_count['os_7'] ++;
            }
            if($o['o_status'] != 5 && $o['oi_ship_status'] == 2){
                $order_count['os_9'] ++;
            }
            $order_count['os_0'] ++;
        }
        $this->assign("status_count",$order_count);
//        $ary_chose ['o_id'] = $this->_get('oid');
        $ary_chose ['o_status'] = $this->_get('status');

        //3个月前的时间
        $three_month_ago = date("Y-m-d H:i:s",  strtotime("-3 month"));
        // 订单的状态条件搜索处理
        switch ($ary_chose ['o_status']) {
            case '1' :
                $str_orders_status = '未发货';
                $ary_where ['fx_orders.o_pay_status'] = 1;
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
                $ary_where ['fx_orders_refunds.or_refund_type'] = array('in',array(1,2));
                break;
            case '5' :
                $str_orders_status = '三个月内订单';
                $ary_where ['fx_orders.o_create_time'] = array(
                    'EGT',
                    $three_month_ago
                );
                break;
            case '6' :
                $str_orders_status = '三个月前订单';
                $ary_where ['fx_orders.o_create_time'] = array(
                    'LT',
                    $three_month_ago
                );
                break;
            case '7' :
                $str_orders_status = '待评价';
                $ary_where ['o_status'] = 5;
                $ary_where ['is_evaluate'] = 0;
                break;
            case '9' :
                $str_orders_status = '待收货';
                $ary_where ['o_status'] = array('neq','5');
                $ary_where ['fx_orders_items.oi_ship_status'] = 2;
                break;
            default :
                $str_orders_status = '所有订单';
        }

        // 过滤掉子订单
        $ary_where ['fx_orders.initial_o_id'] = 0;


        $orders->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')->group('fx_orders.o_id')->where($ary_where)->getField('fx_orders.o_id',true);
        $count_tmp = M()->query(M()->getLastSql());
        $count = count($count_tmp);
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        if($ary_where ['fx_orders_refunds.or_refund_type']){
            $ary_orders_info = $orders
                ->field('fx_orders_items.*,fx_orders.*')
                ->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')
                ->join("fx_orders_refunds on fx_orders_refunds.o_id = fx_orders.o_id ")
                ->where($ary_where)->order(array(
                    'o_create_time' => 'desc'
                ))->group('fx_orders.o_id')->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
        }else{
            $ary_orders_info = $orders
                ->field('fx_orders_items.*,fx_orders.*')
                ->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')->where($ary_where)->order(array(
                    'o_create_time' => 'desc'
                ))->group('fx_orders.o_id')->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
        }
//				echo $orders->getLastSql();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $k => $v) {
                //订单商品
                $field = array(
                    'fx_orders.o_id',
                    'fx_orders_items.oi_id',
                    'fx_orders.o_status',
                    'fx_orders.o_all_price',
                    'fx_orders.o_pay',
                    'fx_orders.o_create_time',
                    'fx_orders.o_goods_all_price',
                    'fx_orders.o_goods_all_saleprice',
                    'fx_orders.o_tax_rate',
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
                    'fx_orders.o_reward_jlb',
                    'fx_orders.o_receiver_idcard'
                );
                $where = array(
                    'fx_orders.o_id' => $v['o_id'],
                    'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
                );
                $order_items = $this->orders->getOrdersData($where, $field);
               //echo'<pre>';print_r($order_items);
                foreach ($order_items as $ky => $va) {
                    $order_items [$ky] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($va ['pdt_id']);
                    $order_items [$ky] ['g_picture'] = D("GoodsInfo")->Search(array('g_id' => $va ['g_id']),'g_picture');
                }

                $ary_orders_info[$k]['items'] = $order_items;

                // 订单状态
                $ary_orders_status = $this->orders->getOrdersStatus($v ['o_id']);
                $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'o_id' => $v ['o_id']
                ))->order('or_update_time desc')->select();
                if (!empty($ary_afersale) && is_array($ary_afersale)) {
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
                        } //退运费
                        elseif($valaf['or_refund_type'] == 3){
                            switch($valaf['or_processing_status']){
                                case 0:
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退运费中';
                                    break;
                                case 1:
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退运费成功';
                                    break;
                                case 2:
                                    $ary_orders_info [$k] ['refund_goods_status'] = '退运费驳回';
                                    break;
                                default:
                                    $ary_orders_info [$k] ['refund_goods_status'] = ''; // 没有退款
                            }
                        }
						break;
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
        }//exit;

        // 订单状态
        //$this->assign('mp_id',$mp_id);
        $this->assign('chose', $ary_chose);
        //echo "<pre>";print_r($ary_orders_info);exit;
        $this->assign('member',$member);
        $this->assign('str_orders_status',$str_orders_status);
        $this->assign('orders_info', $ary_orders_info);
        $this->assign('page', $page); // 赋值分页输出
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    public function orderDetail(){
        $member = D('WapInfo')->memberInfo();
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
            'fx_orders.o_source',
            'fx_orders.o_source_id',
            'fx_orders.o_payment',
            'fx_orders.lt_id',
            'fx_orders.ra_id',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders.o_cost_freight',
            'fx_orders.o_receiver_time',
            'fx_orders.o_point_money',
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
            'fx_members.m_balance',
            'fx_orders.o_audit',
            'fx_orders.is_evaluate'
        );
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = $this->orders->getOrdersData($where, $field);
        if($ary_orders_info[0]['o_receiver_telphone']){
            $ary_orders_info[0]['o_receiver_telphone'] = decrypt($ary_orders_info[0]['o_receiver_telphone']);
        }
        $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
        if(!preg_match($RegExp,$ary_orders_info[0]['o_receiver_mobile'])){
            $ary_orders_info[0]['o_receiver_mobile'] = vagueMobile(decrypt($ary_orders_info[0]['o_receiver_mobile']));
        }else{
            $ary_orders_info[0]['o_receiver_mobile'] = vagueMobile( $ary_orders_info[0]['o_receiver_mobile']);
        }
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
            $tpl = './Tpl/Wap/Orders/orderRefund.html';
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
        //if ($ary_orders ['refund_status'] == '') {
            // 订单支付状态
            $ary_pay_status = array(
                'o_pay_status' => $ary_orders ['o_pay_status']
            );
            $str_pay_status = $this->orders->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders ['str_pay_status'] = $str_pay_status;
        //}

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

				if($_SESSION['OSS']['GY_QN_ON'] == '1'){//七牛图片显示
					$ary_orders_info [$k] ['g_picture']=D('QnPic')->picToQn($ary_goods_pic['g_picture']); 
				}else{
					$ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
				}
				
                // 订单商品退款、退货状态
                $ary_orders_info [$k] ['str_refund_status'] = $this->orders->getOrderItmesStauts('oi_refund_status', $v);
                // 订单商品发货
//                 echo "<pre>";print_r($ary_orders_info);exit;
                $ary_orders_info [$k] ['str_ship_status'] = $this->orders->getOrderItmesStauts('oi_ship_status', $v);
                // 分销系统商品小计 和  从第三下单的商品小计
                $ary_orders_info [$k] ['subtotal'] = empty($v['o_source_id']) ? $v ['oi_nums'] * $v ['oi_price'] : $v['oi_price'];
				//订单商品总数量
				$order_goodsnum += $ary_orders_info [$k] ['oi_nums'];
//                echo "<pre>";print_r($v);die;
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
        $ary_delivery = $this->orders->ordersLogistic($int_o_id);
        // 商品总价=商品折扣后价格+折扣价
        $ary_orders ['o_goods_all_price'] = sprintf("%0.3f", $ary_orders['o_goods_all_price']);
//        echo "<pre>";print_r($ary_orders);exit;
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
		$this->assign($resdata);
        $this->assign("info",$member);
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign('order_goodsnum', $order_goodsnum);
        $this->assign('ary_combo', $ary_combo);
        $this->assign('ary_orders', $ary_orders);
        $this->assign('ary_delivery', $ary_delivery);
        //echo "<pre>";print_r($ary_orders);die;
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_info = D('Gyfx')->selectOneCache('payment_cfg','pc_custom_name', array('pc_abbreviation'=>'DELIVERY'));
			$pay_name = $pay_info['pc_custom_name'];
			$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);	
		}
		$this->assign('pay_name',$pay_name);

        if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        if(isset($tpl)){
            $this->display($tpl);
        }else{
            $this->display();
        }
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
        $ary_cart = D('Cart')->ReadMycart();
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
     * 扫码支付页面
     * 
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-04-01
     */
    public function wxCode() {
		$this->assign('code_url',$this->_get('code_url'));
		$this->assign('oid',$this->_get('oid'));
        $this->display();
    }
	
	/**
     * 公众号支付页面
     * 
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-04-01
     */
    public function wxZf() {
		$parameters = htmlspecialchars_decode($this->_get('parameters'));
		//writeLog(htmlspecialchars_decode($this->_get('parameters')).'<br>', "order_weixinpay20150422.log");
		//writeLog(dump(json_decode($parameters,true)).'<br>', "order_weixinpay20150422.log");
		$tmp_par = json_decode($parameters,true);
		$tmp_par['timeStamp'] = (string)$tmp_par['timeStamp'];
		$parameters = json_encode($tmp_par);
		$this->assign('parameters',$parameters);
		$this->assign('oid',trim($this->_get('oid')));
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
                $this->error('此订单不能不存在', array('确定' => U('Wap/Orders/orderDetail/', array('oid' => $int_oid))));
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
                            '确定' => U('Wap/Orders/orderDetail/', array(
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
                                    '确定' => U('Wap/Orders/orderDetail/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                            }
                        }elseif($item['oi_type']==7 && !empty($item['fc_id'])){
                            $retun_spike_nums=D("Spike")->where(array('sp_id' => $item['fc_id']))->setDec("sp_now_number",$item['oi_nums']);
                            if(!$retun_spike_nums){
                                M()->rollback();
                                $this->error('作废订单秒杀量更新失败', array(
                                    '确定' => U('Wap/Orders/orderDetail/', array(
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
                                '确定' => U('Wap/Orders/orderDetail/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                        }
						//库存释放
						$item_stock_info=M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->field('pdt_freeze_stock,pdt_stock,pdt_total_stock')->where(array('g_id' => $item ['g_id']))->find();
						if(isset($item_stock_info['pdt_freeze_stock']) && $item_stock_info['pdt_freeze_stock'] >0){
							$item_stock_data['pdt_freeze_stock']=$item_stock_info['pdt_freeze_stock']-$item['oi_nums'];
							if($item_stock_data['pdt_freeze_stock'] < 0 ){
								$item_stock_data['pdt_freeze_stock']= 0;
								$item_stock_data['pdt_stock'] = $item_stock_info['pdt_stock'] + $item_stock_info['pdt_freeze_stock'];
								//$item_stock_data['pdt_total_stock'] =$item_stock_info['pdt_total_stock'] + $item_stock_info['pdt_freeze_stock'];
							}else{
								$item_stock_data['pdt_stock'] = $item_stock_info['pdt_stock'] + $item['oi_nums'];
								//$item_stock_data['pdt_total_stock'] =$item_stock_info['pdt_total_stock'] + $item['oi_nums'];
							}
							$updata_item_stock = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $item ['g_id']))->save($item_stock_data);
							if(!$updata_item_stock){
								M()->rollback();
								$this->error('作废订单销量更新失败', array('确定' => U('Wap/Orders/orderDetail/', array('oid' => $int_oid)) ));
							}
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
                                                    '确定' => U('Wap/Orders/orderDetail/', array(
                                                        'oid' => $int_oid
                                                    ))
                                                ));
                                     exit;
                                 }
                            }else{
                                 $orders->rollback();
                                 $this->error('作废返回冻结积分写日志失败', array(
                                                '确定' => U('Wap/Orders/orderDetail/', array(
                                                    'oid' => $int_oid
                                                ))
                                            ));
                                 exit;
                            }
                        }else{
                             $orders->rollback();
                             $this->error('作废返回冻结积分没有找到要返回的用户冻结金额', array(
                                '确定' => U('Wap/Orders/orderDetail/', array(
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
                                '确定' => U('Wap/Orders/orderDetail/', array(
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
                                '确定' => U('Wap/Orders/orderDetail/', array(
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
                                '确定' => U('Wap/Orders/orderDetail/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
					**/
                    //优惠券还原
                    $res_coupon = D("CouponActivities")->delCoupon($int_oid);
                    if (!$res_coupon) {
                        M()->rollback();
                        $this->error('优惠券还原失败', array(
                            '确定' => U('Wap/Orders/orderDetail/', array(
                                'oid' => $int_oid
                            ))
                        ));
                        exit();
                    }
                    if(!empty($ary_orders['o_source_id'])){
                        $bool_result = M('thd_orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('to_oid'=>$ary_orders['o_source_id']))->data(array('to_tt_status'=>'0'))->save();
                        if(false === $bool_result){
                            M()->rollback();
                             $this->error('更新第三方来源单号失败', array(
                                    '确定' => U('Wap/Orders/orderDetail/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                                exit();
                        }
                    }
                    M()->commit();
                    $this->success('作废成功', array(
                        '确定' => U('Wap/Orders/orderList')
                    ));
                    exit();
                } else {
                    M()->rollback();
                    $this->error('此订单不能作废', array(
                        '确定' => U('Wap/Orders/orderDetail/', array(
                            'oid' => $int_oid
                        ))
                    ));
                    exit();
                }
            }
        }
    }
	
    /**
	 * 团购订单确认页
	 * @author zhuwenwei <zhuwenwei@guanyisoft.com> 
	 * date 2015-08-18
	 */
	public function pageBulkAdd_old() {
		$param_bulk = $_SESSION ['bulk_cart'];
		if (empty ( $param_bulk )) {
			$this->error ( '请选择团购商品！',U('Wap/Bulk') );
		} 
		$groupbuy = M ( 'groupbuy', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$gp_price = M ( 'related_groupbuy_price', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$gp_city = M ( 'related_groupbuy_area', C ( 'DB_PREFIX' ), 'DB_CUSTOM' );
		$ary_data = array ();
		// 获取常用收货地址
		$ary_addr = $this->cityRegion->getReceivingAddress ( $_SESSION ['Members'] ['m_id'] );
		if (count ( $ary_addr ) > 0) {
			$ary_data ['default_addr'] = $ary_addr [0];
			unset ( $ary_addr [0] );
		}else{
            $ary_data ['ary_addr'] = $ary_addr;
        }
		/**
		$ary_wap_addr = $this->cityRegion->getReceivingAddress($m_id);
		if (count($ary_wap_addr) > 0) {
			$ary_wap_addr ['default_addr'] = $ary_wap_addr [0];
			unset($ary_wap_addr [0]);
		}
        $this->assign("address",$ary_wap_addr ['default_addr']);
		**/
		// 获取常用收货地址
		$raid = $this->_request('raid');
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id'],$raid);
        //echo'<pre>'; print_r($ary_addr);die;
        if (count($ary_addr) > 0) {
			if(isset($ary_addr [0]) && $ary_addr [0]!=''){
				$ary_data ['default_addr'] = $ary_addr[0];
				unset($ary_addr [0]);
			}else{
				$ary_addr ['ra_is_default'] = 1;//临时默认收货地址
				$ary_data ['default_addr'] = $ary_addr;
			}
        }else{
            $ary_data ['ary_addr'] = $ary_addr;
        }
		$this->assign("address",$ary_data ['default_addr']);
		
		$ary_data ['ary_addr'] = $ary_addr;
		// 获取支付方式
		$payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg(1);
        $ary_data ['payment_cfg'] = $payment_cfg;
		
		//是否开启自提功能
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		
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
						'type' => 0,
						'type_code'=>'bulk',
						'type_id'=>$data['gp_id']
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
					'type_code'=>'bulk',
					'type_id'=>$data['gp_id']
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
		$this->assign ( 'product_data', $data );
		$this->assign ( 'ary_addr', $ary_data ['ary_addr'] );
		$this->assign ( 'default_addr', $ary_data ['default_addr'] );
        // 支付方式
		foreach($ary_data ['payment_cfg'] as $k=>$cfg){
			if($ary_logistic=='' && $cfg['pc_abbreviation']=='DELIVERY'){
				unset($ary_data ['payment_cfg'][$k]);
				continue;
			}
			if($ary_logistic!=''){
				$first_logistic=reset($ary_logistic); 
				if($first_logistic['lc_cash_on_delivery']=='0' && $cfg['pc_abbreviation']=='DELIVERY'){
					unset($ary_data ['payment_cfg'][$k]);
					continue;
				}
			}
			if($cfg['pc_abbreviation'] == 'WEIXIN'){
				$is_weixin = is_weixin();
				if($is_weixin != true){
					unset($ary_data ['payment_cfg'][$k]);
				}
			}
		}	
		// 支付方式
		$this->assign ( 'ary_paymentcfg', $ary_data ['payment_cfg'] );
		// 配送公司
		$this->assign ( 'ary_logistic', $ary_logistic );
		// 发票收藏列表
		$this->assign ( 'invoice_list', $invoice_list );
		// 发票信息
		$this->assign ( 'invoice_info', $invoice_info );
		$this->assign ( 'invoice_content', $invoice_content );
		//订单锁定
		$ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
		// 获取商品基本信息
		$field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic';
		$goods_info = D('GoodsInfo')->field($field)->where (array('g_id' => $data ['g_id']))->find();
		$goods_info ['gpic'] = '/'.ltrim($goods_info['gpic'],'/');
		$goods_info ['save_price'] = $goods_info ['gprice'] - $data ['gp_price'];
        /*标明这个是团购订单确认页*/
        $this->assign ( 'web_type', 'Bulk' );
		//根据当前SESSION生成随机数非法提交订单
		$code = mt_rand(0,1000000);
		$_SESSION['auto_code'] = $code;      //将此随机数暂存入到session
		$this->assign("auto_code",$code);	
		//开启收货时间
		$ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
		//开启自提
		$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);		
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}
	
	/**
     * 计算秒杀物流费用
     * @author zhuwenwei <zhuwenwei@guanyisoft.com>
     * @date 2015-08-17
     */
    public function checkBulkLogistic() {
        $bulk_cart = $_SESSION['bulk_cart'];
        $ary_member = session("Members");
        $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
//        $ary_return['all_price'] = $array_bulk['data']['cust_price'] * $bulk_cart['num'];
        $ary_return['all_price'] = $array_bulk['data']['pdt_all_sale_price'];
        $ary_return['all_goods_price'] = $array_bulk['data']['gp_subtotal_price'];
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
     * 公众号支付页面
     * 
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-08-17
     */
	public function pageSpikeAdd() {
        $param_spike = $_SESSION ['spike_cart'];
        $ary_member = $_SESSION['Members'];
        if (empty($param_spike)) {
            $this->error('请选择秒杀商品！',U('Wap/Spike'));
        }
        $spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price', C('DB_PREFIX'), 'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_data = array();
        // 获取常用收货地址
		/**
        $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
        if (count($ary_addr) > 0) {
            $ary_data ['default_addr'] = $ary_addr [0];
            unset($ary_addr [0]);
        }
        $ary_data ['ary_addr'] = $ary_addr;
		$this->assign("address",$ary_data ['default_addr']);
		**/

        // 获取常用收货地址
        $ary_addr = D('CityRegion')->getReceivingAddress ($ary_member ['m_id'] );
        if (count ( $ary_addr ) > 0) {
            $ary_data ['default_addr'] = array_shift($ary_addr);
            $ary_data ['default_addr']['ra_is_default'] = 1;//设置为默认
        }
        $ary_data ['ary_addr'] = $ary_addr;
		$this->assign("address",$ary_data ['default_addr']);
		
		//是否开启自提功能
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		
        // 获取支付方式
        $payment = D('PaymentCfg');
        $payment_cfg = $payment->getPayCfg(1);
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
		$spike = D('Spike');
        $arry_spike = $spike->getSpikePrice($param_spike);
        if ($arry_spike['status'] != 'success') {
            $this->error('秒杀商品不存在！');
        }
	        $data = $arry_spike['data'];
        $data ['cust_price'] = $data['pdt_sale_price'];
        $buy_nums = $data ['sp_now_number'] + $data['sp_pre_number'];
        $data ['sp_now_number'] = $buy_nums;

        // 获取商品基本信息
        $field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic';
        $goods_info = D('GoodsInfo')->field($field)->where(array(
                    'g_id' => $data ['g_id']
                ))->find();
        $goods_info ['gpic'] = '/' . ltrim($goods_info ['gpic'], '/');
        $goods_info ['save_price'] = $data ['pdt_sale_price'] - $data ['gp_price'];
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

//        $i = 0;
//        foreach ($ary_logistic as $logustic_key => $logustic_val) {
//            if ($i == 0) {
//                $ary_cart [$data ['sku'] ['pdt_id']] = array(
//                    'pdt_id' => $data ['sku'] ['pdt_id'],
//                    'num' => $data ['sku'] ['num'],
//                    'type' => 0
//                );
//                $logistic_price = D('Logistic')->getLogisticPrice($logustic_val ['lt_id'], $ary_cart);
//            }
//            $i ++;
//        }
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
        $this->assign('ary_logistic', $ary_logistic);
	   // 支付方式
		foreach($ary_data ['payment_cfg'] as $k=>$cfg){
			if($ary_logistic=='' && $cfg['pc_abbreviation']=='DELIVERY'){
				unset($ary_data ['payment_cfg'][$k]);
				continue;
			}
			if($ary_logistic!=''){
				$first_logistic=reset($ary_logistic); 
				if($first_logistic['lc_cash_on_delivery']=='0' && $cfg['pc_abbreviation']=='DELIVERY'){
					unset($ary_data ['payment_cfg'][$k]);
					continue;
				}
			}
			if($cfg['pc_abbreviation'] == 'WEIXIN'){
				$is_weixin = is_weixin();
				if($is_weixin != true){
					unset($ary_data ['payment_cfg'][$k]);
				}
			}
		}
        $this->assign('ary_paymentcfg', $ary_data ['payment_cfg']);
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
		//开启收货时间
		$ary_order_time = D('SysConfig')->getCfgByModule('ORDERS_TIME');
        $this->assign('order_time', $ary_order_time ['ORDERS_TIME']);
		//开启自提
		$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);
		//$tpl = $this->wap_theme_path.'Spike/pageSpikeAdd.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
	
    /**
     * 计算秒杀物流费用
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-08-17
     */
    public function checkSpikeLogistic() {
        $param_spike = $_SESSION ['spike_cart'];
        $spike = D('Spike');
        
        $array_where = array(
            'sp_status' => 1,
            'sp_id' => $param_spike ['sp_id']
        );
        $spike_data = $spike->getSpikePrice($param_spike);
        if ($spike_data['status'] != 'success') {
            $this->error('秒杀商品不存在！');
        }
        $data = $spike_data['data'];

		$buy_nums = $data ['sp_now_number'] + $data['sp_pre_number'];
        $data ['sp_now_number'] = $buy_nums;
        $ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 7);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        if(!empty($lt_expressions['logistics_configure']) && $data ['sp_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
			$ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 8);
			$logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart);
		}
		$ary_return['status'] = 1;
        $ary_return['all_price'] = $data['pdt_all_sale_price'];
        $ary_return['logistic_price'] = $logistic_price;
        $ary_return['promotion_price'] = $data['pdt_all_sale_price'] - $data['sp_subtotal_price'];
        $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
		$ary_return['sp_price'] = $data['sp_subtotal_price'];
        $this->ajaxReturn($ary_return);
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
        $ary_orders_info = $this->orders->getOrdersData($where, $field);
        // echo "<pre>";print_r($this->orders->getLastSql());exit;
        // 订单详情商品
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $combo_price_total = 0;
            foreach ($ary_orders_info as $k => $v) {
                $ary_orders_info [$k] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v ['pdt_id']);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'g_id' => $v ['g_id']
                ))->field('g_picture')->find();

                $ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
                $ary_orders_info [$k] ['g_picture'] = D('QnPic')->picToQn($ary_orders_info [$k] ['g_picture'],60,60);
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
        $tpl = '';
        if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
//        $this->display();
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
                $good['gcom_pics'] = D('ViewGoods')->ReplaceItemPicReal($good['gcom_pics']);
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
                        //添加到评论统计表
                        $data['gcom_id'] = $arr_res;
                        $data['gcom_status'] = 1;
                        $res = D('GoodsComments')->addGoodsCommentStatistics($data);
                        if(!$res) {
                            M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                            $this->error('评论失败，请重试...');
                        }
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
//                        if(!empty($point_config['show_recommend_points'])){
//                            $ary_data = array();
//                            $ary_data['type'] = 15;
//                            $ary_data['reward_point'] = intval($point_config['show_recommend_points']);
//                            $ary_data['memo'] = '会员晒单';
//                            //事物开启
//                            $point_res = D('PointLog')->addPointLog($ary_data, $member['m_id']);
//                            if($point_res['status'] != '1'){
//                                M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
//                                $this->error("添加积分日志表失败...");exit;
//                            }else{
//                                $mem_res = D('Members')->where(array('m_id'=>$member['m_id']))->data(array('total_point'=>$_SESSION['Members']['total_point']+$ary_data['reward_point'],'m_update_time'=>date('Y-m-d H:i:s')))->save();
//                                if(!$mem_res){
//                                    M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
//                                    $this->error("更新会员积分信息失败");exit;
//                                }
//                            }
//                            //更新会员SESSION信息
//                            $_SESSION['Members']['total_point'] = $_SESSION['Members']['total_point']+$ary_data['reward_point'];
//                        }

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
            $this->success("评论成功", U("Wap/Orders/orderList", 3));
        } else {
            $this->error("评论内容不能为空");
        }
    }

    public function AddAppInvoice() {
        $data = $this->_post();
        $data ['m_id'] = $_SESSION ['Members'] ['m_id'];
        $data ['create_time'] = date("Y-m-d H:i:s");
        $data ['modify_time'] = date("Y-m-d H:i:s");
        $data ['is_default'] = 0;

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
     * 团购订单确认页
     */
    public function pageBulkAdd() {
        $param_bulk = $_SESSION ['bulk_cart'];
        $raid = $this->_get('raid');
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
            if($raid) {
                foreach($ary_addr as $addr) {
                    if($addr['ra_id'] == $raid) {
                        $ary_data ['default_addr'] = $addr;
                    }
                }
            }else {
                $ary_data ['default_addr'] = array_shift($ary_addr);
            }
            $ary_data ['default_addr']['ra_is_default'] = 1;//设置为默认
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
//        dump($ary_data ['default_addr'] );die;
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

        //是否开启自提功能
        $is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        //开启自提
        $this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);
        $tpl = '';
        if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display ($tpl);
    }




	
}
