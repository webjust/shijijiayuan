<?php

/**
 * 前台购物车
 * @stage 7.5
 * @package Action
 * @subpackage Wap
 * @author Nick <shanguangkun@guanyisoft.com>
 * @date 2014-05-26
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CartAction extends WapAction {

    /**
     * 控制器初始化
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     */
    public function _initialize() {
		$is_weixin = is_weixin();
        $Member = session("Members");
		
        if(empty($Member) && !isset($Member['m_id'])){
			//微信商城自动注册登录会员
			if($_SESSION['no_wx'] !=1 && $is_weixin == 1){
				$this->doCheckLogin();
			}
            //$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			//$this->redirect(U('/Wap/User/Login')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
        }		
        parent::_initialize();
    }
	
    /**
     * 微信商城登陆判断
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-18
     */	
	public function doCheckLogin(){
		$_SESSION['is_cart'] = 1;
		$this->redirect(U('/Wap/User/isWeiXin')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
	}
    /**
     * 向购物车内添加货品
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2012-12-26
     */
    public function doAdd() {
		$ary_parms = $this->_param();
        $type = $ary_parms['type'];
        $ary_insert = array();
        $ary_member = session('Members');
        $int_good_type = 0;
        $error_msg = array(
            '0' =>  '活动已失效！',
            '2' =>  '请先登录！',
            '3' =>  '活动尚未开始！',
            '4' =>  '活动已结束！',
            '5' =>  '已售罄！',
        );
        if ('bulk' == $type) {
            $data = D('Groupbuy')->getDetails($ary_parms['cart']['gp_id'], $ary_member, $ary_parms['cart']['pdt_id']);
            $buy_status = $data['buy_status'];
            if($buy_status == 1) {
                //判断是否验证码验证
                if($data['gp_start_code'] == '1'){
                    if ($_COOKIE['verify'] != md5($ary_parms['cart']['code_data']['verify'])) {
                        $this->ajaxReturn(array('status'=>0,'msg'=>'验证码不正确'));
                        exit;
                    }else{
                        $_COOKIE['verify'] = null;
                    }
                }
                $_SESSION['bulk_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status' => 1, 'url' => '/Wap/Orders/pageBulkAdd'));
            }
            else {
                $this->ajaxReturn(array('status'=>0,'msg'=>$error_msg[$buy_status]));
            }
            exit;

        }
        elseif('spike' == $type){
            $data = D('Spike')->getDetails($ary_parms['cart']['sp_id'], $ary_member, $ary_parms['cart']['pdt_id']);
            $buy_status = $data['buy_status'];
            if($buy_status == 1) {
                $_SESSION['spike_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status' => 1, 'url' => '/Wap/Orders/pageSpikeAdd'));
            }
            else {
                $this->ajaxReturn(array('status'=>0,'msg'=>$error_msg[$buy_status]));
            }
            exit;
        }
        elseif('presale' == $type){
            $data = D('Presale')->getDetails($ary_parms['cart']['p_id'], $ary_member, $ary_parms['cart']['pdt_id']);
            $buy_status = $data['buy_status'];
            if($buy_status == 1) {
                $_SESSION['presale_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status'=>1, 'url' => '/Wap/Orders/pagePresaleAdd'));
            }
            else {
                $this->ajaxReturn(array('status'=>0,'msg'=>$error_msg[$buy_status]));
            }
            exit;

        }
        elseif('integral' == $type){//积分兑换 带金额
            $_SESSION['integral_cart'] = $ary_parms['cart'];
            $member = $_SESSION['Members'];
            $ary_where = array();
            $ary_where[C('DB_PREFIX')."orders.m_id"] = $member['m_id'];
            $ary_where[C('DB_PREFIX')."orders.o_status"] = array('neq','2');
            $ary_where[C('DB_PREFIX')."orders_items.oi_type"] = "11";//积分兑换+金额
            $ary_where[C('DB_PREFIX')."integral.integral_id"] = $ary_parms['cart']['integral_id'];
            $arr_integral = M('Integral',C('DB_PREFIX'),'DB_CUSTOM')->where(array('integral_status'=>'1','integral_id'=>$ary_parms['cart']['integral_id']))->find();
            if(!empty($arr_integral) && is_array($arr_integral)){
                if($arr_integral['integral_num'] == $arr_integral['integral_now_number']){
                    $this->ajaxReturn(array('status' => 0,'info'=>'已售罄！'));
                }
                $ary_integral=M('Integral',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field(array(C('DB_PREFIX').'orders_items.fc_id',C('DB_PREFIX').'integral.*'))
                    ->join(C('DB_PREFIX').'orders_items ON '.C('DB_PREFIX').'integral.integral_id = '.C('DB_PREFIX').'orders_items.fc_id')
                    ->join(C('DB_PREFIX').'orders ON '.C('DB_PREFIX').'orders.o_id = '.C('DB_PREFIX').'orders_items.o_id')
                    ->where($ary_where)->find();
                if(!empty($ary_integral) && is_array($ary_integral)){
                    $this->ajaxReturn(array('status' => 0,'info'=>'您已经兑换过该商品！'));
                }
                $_SESSION['integral_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status'=>1, 'url' => '/Wap/Orders/pageIntegralAdd'));
            }else{
                $this->ajaxReturn(array('status' => 0,'info'=>'该积分兑换活动不存在，请重试……'));
            }
            die;
        }
        elseif ('item' == $type) {
            $pdt_id = $ary_parms['pdt_id'];
            $num = $ary_parms['num'];
            $int_good_type = $this->_param('good_type', '', 0);
            $ary_insert[$pdt_id] = $num;
        }
        else {
        	//积分商城
        	if($ary_parms['type'] == '1'){
        		$pdt_id = $ary_parms['pdt_id'];
        		$num = $ary_parms['num'];
        		$int_good_type = 1;
        		$ary_insert[$pdt_id] = $num;
        	}else{
        		$ary_insert = $ary_parms['cart'];
        	}
        }
        foreach ($ary_insert as $str_pdt => $int_num) {
            $int_num = (int) $int_num;
            //大于0的新插入/更新. 小于等于0的不作处理
            if ($int_num > 0) {
                $ary_cart[$str_pdt] = $int_num;
            }
        }
        //$type=item&num=1&pdt_id=111
        //过滤一遍数据，以防有小于0的或者不是数字的
        $ary_db_carts = array();
        if (!empty($ary_member['m_id'])) {
            $ary_db_carts = D('Cart')->ReadMycart();
        } else {
            $ary_session_carts = (session("?Cart")) ? session("Cart") : array();
        }
        foreach ($ary_cart as $key => $int_num) {
            if ($int_num <= 0 || !is_int($int_num)) {
                unset($ary_cart[$key]);
            }
            $goods_info = D('GoodsProducts')->GetProductList(array('fx_goods_products.pdt_id' => $key), array('fx_goods.g_is_combination_goods', 'fx_goods.g_gifts', 'fx_goods.g_id'));
            if ($goods_info[0]['g_is_combination_goods']) {//组合商品
                $int_good_type = 3;
            }

            if ($goods_info[0]['g_gifts'] == 1) {
                $this->error('赠品不能购买！');
                return false;
            }
            if (!empty($ary_member['m_id'])) {//database
                if (array_key_exists($key, $ary_db_carts) && isset($ary_db_carts[$key]['type']) && ($int_good_type == $ary_db_carts[$key]['type'])) {
                    if('item' == $type && $this->_post('way_type') == '1'){
						$ary_db_carts[$key]['num'] =$int_num;
					}else{
						$ary_db_carts[$key]['num']+=$int_num;
					}
                } else {
                    $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num, 'type' => $int_good_type, 'g_id' => $goods_info[0]['g_id']);
                }
            } else {//session
                if (array_key_exists($key, $ary_session_carts) && isset($ary_session_carts[$key]['type']) && ($int_good_type == $ary_session_carts[$key]['type'])) {
					if('item' == $type && $this->_post('way_type') == '1'){
						$ary_session_carts[$key]['num'] =$int_num;
					}else{
						$ary_session_carts[$key]['num']+=$int_num;
					}
                } else {
                    $ary_session_carts[$key] = array('pdt_id' => $key, 'num' => $int_num, 'type' => $int_good_type, 'g_id' => $goods_info[0]['g_id']);
                }
            }
        }

        if (!empty($ary_member['m_id'])) {//保存到databese
            if ($int_good_type == 1) {
                //判断会员的当前有效积分是否满足购买积分条件
				$ary_point_datas=array();
				foreach($ary_db_carts as $pkey=>$pvalue){
					if($pvalue['type']==$int_good_type){
						$ary_point_datas[$pvalue['pdt_id']]=$pvalue;
					}
				}
                if (false == ($flag = D('Cart')->enablePoint($ary_member['m_id'], $ary_point_datas, $info))) {
                    $this->error($info);
                }
            }
            $Cart = D('Cart')->WriteMycart($ary_db_carts);

            if ($Cart == false) {
				$this->success("加入购物车失败！", array(
                    "继续购物" => U('/Wap/Products/index'),
                    "去购物车结算" => U('Wap/Cart/pageCartList')
                ));
            }
        } else {//保存到session
            session("Cart", $ary_session_carts);
        }
        if ($type == 'item') {
            if ($this->_post('skip') != 1) {
                $this->success("加入购物车成功！", array(
                    "继续购物" => U('/Wap/Products'),
                    "去购物车结算" => U('Wap/Cart/pageCartList')
                ));
            } else {
                $this->success("加入购物车成功！", array(
                    '立即结算' => U('Wap/Orders/pageAdd')
                ));
            }
        } else {
            if ($this->_post('skip') != 1) {
                $this->success("加入购物车成功！", array(
                    "继续购物" => U('/Wap/Products'),
                    "去购物车结算" => U('Wap/Cart/pageCartList')
                ));
            } else {
                $this->success("加入购物车成功！", array(
                    '立即支付' => U('Wap/Orders/pageAdd')
                ));
            }
        }
		
	
    }

    /**
     * 向购物车内添加货品
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-30
     */
    public function doBulkAdd() {
        $good_type = 0;
        $ary_insert = $this->_post('cart');
        foreach ($ary_insert as $str_pdt => $int_num) {
            $int_num = (int) $int_num;
            //大于0的新插入/更新. 小于等于0的不作处理
            if ($int_num > 0) {
                $ary_cart[$str_pdt] = $int_num;
            }
        }

        //$int_type=item&num=1&pdt_id=111
        //过滤一遍数据，以防有小于0的或者不是数字的
        $ary_db_carts = array();
        $ary_db_carts = D('Cart')->ReadMycart();
        foreach ($ary_cart as $key => $int_num) {
            if ($int_num <= 0 || !is_int($int_num)) {
                unset($ary_cart[$key]);
            }
            $where = array('fx_goods_products.pdt_id' => $key);
            $field = array('fx_goods.g_is_combination_goods', 'fx_goods.g_gifts','fx_goods.g_id');
            $goods_info = D('GoodsProducts')->GetProductList($where, $field);
            if(!$goods_info){
                $this->error('该商品已下架！');
                return false;
            }
//            dump(D('GoodsProducts')->getLastSql());die;
            if ($goods_info[0]['g_is_combination_goods']) {//组合商品
                $good_type = 3;
            }
            if ($goods_info[0]['g_gifts'] == 1) {
                $this->error('赠品不能购买！');
                return false;
            }

            if (array_key_exists($key, $ary_db_carts) && isset($ary_db_carts[$key]['type']) && ($good_type == $ary_db_carts[$key]['type'])) {
                $ary_db_carts[$key]['num']+=$int_num;
            } else {
                $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num, 'type' => $good_type,'g_id'=>$goods_info[0]['g_id']);
            }
        }

        $Cart = D('Cart')->WriteMycart($ary_db_carts);

        if ($Cart == false) {
            $this->success(L('添加到购物车失败'), array(
                L('VIEW_CART') => U('/Cart/pageCartList')
            ));
        }

        if ($this->_post('skip') != 1) {
            $this->success(L('添加到购物车成功'), array(
                L('CONTINUE_BUY') => U('Wap/Products/pageList'),
                L('VIEW_CART') => U('Wap/Cart/pageCartList')
            ));
        } else {
            $this->success(L('添加到购物车成功'), array(
                L('VIEW_CART') => U('Wap/Cart/pageCartList')
            ));
        }
    }
    
    /**
     * 将自由组合商品加入购物车
     * 需要传过来的参数
     * fc_id 自由组合ID
     * pdt_id 商品货号
     * num 商品货号对应商品数量
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2013-08-14
     */
    public function doAddFreeCollocation() {
        $ary_parms = $this->_param();
        $ary_member = session("Members");
		$ary_parms['m_id'] = $ary_member['m_id'];	
		$ary_parms['fc_type'] = 4;
		$add_res = D('FreeRecommend')->addFreeCollocation($ary_parms);
		if($add_res['status'] == true){
			if ($ary_parms['skip'] != '') {
				$this->success('加入购物车成功', array(U('Wap/Orders/pageAdd')));
			} else {
				$this->success('加入购物车成功');
			}
		}else{
			$this->error($add_res['msg']);
		}
		exit;
    }

   /**
     * 在session中拿取 货品的id  和货品的数（pdt_nums）
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     * @return type array
     */
	public function _pageList() {
        $this->setTitle('我的购物车');
        $Cart = D('Cart');
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
         	$cart_data = $Cart->ReadMycart();
//            dump($cart_data);die;
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
                    $ary_pdt[$key] = array('pdt_id' => $pdt_id, 'num' => $value['num'], 'type' => $int_type, 'fc_id' => $value['fc_id']);
                }
            }
//            dump($ary_pdt);die;
            if (is_array($ary_pdt) && !empty($ary_pdt)) {
                $ary_cart_data = $Cart->getProductInfo($ary_pdt);
            } 
//            echo "<pre>";print_r($ary_cart_data);exit;
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
            //商品总数
            $ary_price_data['all_nums'] = 0;
            foreach($pro_datas as $keys=>$vals){ 
                foreach($vals['products'] as $key=>$val){
                    $arr_products = $Cart->getProductInfo(array($key=>$val));
                    if($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6'){
                        foreach($arr_products[0] as &$provals){
                             $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                             $ary_price_data['all_nums'] += $provals['pdt_nums'];
                        }
                    }else{
                    	foreach($arr_products as $ary_sub_product){
                    		$ary_price_data['all_nums'] += $ary_sub_product['pdt_nums'];
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
        }else {
            if (session("?Cart")) {
                $ary_cart = session("Cart");
                //货品信息
                //购买货品的总价
                $ary_cart_data = $Cart->getProductInfo($ary_cart);
               
                if (!empty($ary_cart_data) && is_array($ary_cart_data)) {
                    foreach ($ary_cart_data as $key => $val) {
                        $promition_rule_name = $val['pdt_rule_name'];
                        //应付的价格（不包括运费
                        if($val['type'] == 1){
                        	$ary_price_data['consume_point'] += intval($val['pdt_momery']); //消耗总积分
                        }else{
                        	//自由组合商品
                        	if($val[0]['type'] == '4' || $val[0]['type'] == '6'){
                        		foreach($val as $ary_sub_val){
                        			$ary_price_data['all_pdt_price'] +=$ary_sub_val['pdt_sale_price']*$ary_sub_val['pdt_nums'];
                        			$ary_price_data['all_price'] += $ary_sub_val['pdt_momery'];
                        			$ary_price_data['all_nums'] += $ary_sub_val['pdt_nums'];
                        		}
                        	}else{
                        		$ary_price_data['all_pdt_price'] +=$val['pdt_sale_price']*$val['pdt_nums'];
                        		$ary_price_data['all_price'] += $val['pdt_momery'];
                        		$ary_price_data['all_nums'] += $val['pdt_nums'];
                        	}
                        } 
                    }
                }
                //商品总价
                $promotion_total_price = $ary_price_data['all_pdt_price'];
                //优惠价
                $promotion_price = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
            }
        }
        //是否开启赠送积分
        $ary_config = D('PointConfig')->getConfigs();
        if ($ary_config) {
            $ary_price_data['consumed_ratio'] = isset($ary_config['is_consumed']) && $ary_config['is_consumed'] == 1 ? $ary_config['consumed_ratio'] : 0;
        } else
            $ary_price_data['consumed_ratio'] = 0;

        //赠送积分
        $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price']);
        //库存提示
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $member_level_id = $ary_member['member_level']['ml_id'];
        if ((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK'] == 1) {
            if ($stock_data['USER_TYPE'] == 'all') {
                $stock_data['level'] = true;
            } else {
                $ary_user_level = explode(",", $stock_data['USER_TYPE']);
                $stock_data['level'] = array_search($member_level_id, $ary_user_level);
            }
        }
        $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
        $ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
        $ary_price_data['all_price'] = $preferential_price = (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) > 0 ? (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) : '0.00';
        $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price'] - $free_all_price);
        $this->assign("promition_rule_name", $cart_promition_rule_name);
        $this->assign("cart_data", $ary_cart_data);
//        dump($ary_cart_data);die;
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        $this->assign("stock_data", $stock_data);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path .'preview_' . $ary_request['dir'] . '/shopcart.html';
        } else {
            $tpl =$this->wap_theme_path. 'shopcart.html';
        }
//        $this->assign('skip',$_POST['skip']);
        $this->display($tpl);
    }
    
    /**
     * 在session中拿取 货品的id  和货品的数（pdt_nums）
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-28
     * @return type array
	 * @update by Wangguibin 2015-10-21
     */
    public function pageCartList() {
        $this->setTitle('我的购物车');
        $Cart = D('Cart');
        $member = session("Members");
        $m_id = isset($member['m_id']) ? $member['m_id'] : 0;
        if (!empty($m_id)) {
 			//获取购物车信息
            $tmp_cart_data = $this->unCarts($Cart->ReadMycart());  	
			//处理购物车信息
			$cart_data = $Cart->handleCart($tmp_cart_data);
			//获取促销后优惠信息
            $pro_datas = D('Promotion')->calShopCartPro($member['m_id'], $cart_data,1);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
			//获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$member['m_id'],1);
            }
			//处理获取的商品信息
			$ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$member['m_id']);
			//处理通过促销获取的优惠信息
			$tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
			//处理pro_datas信息
			$pro_datas = $tmp_pro_datas['pro_datas'];
			//获取促销信息
			$pro_data = $tmp_pro_datas['pro_data'];
			//获取每个商品促销信息
			$ary_cart = $Cart->handleCartName($pro_data,$ary_cart);
			//获取赠品信息
			$cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
			//获取订单总金额
			$ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
			unset($tmp_pro_datas);			
        }else {
            $this->error('请先登录',U('Wap/User/Login').'?redirect_uri=' . urlencode( ltrim($_SERVER['REQUEST_URI'],'/')));
        }
        $this->assign("cart_data", $ary_cart);
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path .'preview_' . $ary_request['dir'] . '/shopcart.html';
        } else {
            $tpl = $this->wap_theme_path . 'shopcart.html';
        }
		$resdata = D('SysConfig')->getConfigs('IS_AUTO_CART','IS_AUTO_CART', $str_fileds=null, $str_limit=null,1);
		$this->assign("IS_AUTO_CART",intval($resdata['IS_AUTO_CART']['sc_value']));		
        $this->display($tpl);
    }
    
    /**
     * 暂时去掉自由推荐
     * @author <wangguibin@guanyisoft.com>
     * @date 2015-10-21
     * @return type array
	 * @update by Wangguibin 
     */
	 protected function unCarts($tmp_cart_data){
		//暂时去掉自由推荐和普通商品
		foreach($tmp_cart_data as $key=>$sub_cart){
			if(isset($sub_cart['type']) && $sub_cart['type'] !=0 && $sub_cart['type'] !=4){
				unset($tmp_cart_data[$key]);
			}
		}	
		return $tmp_cart_data;
	 }
    /**
     * 在session中拿取 货品的总数（pdt_nums）
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     * @return type array
     */
    public function mycartAjax() {
        layout(false);
        $Cart = D('Cart');
        $ary_member = session("Members");
        $int_total = 0;
        if (!empty($ary_member['m_id'])) {
            $ary_cart_data = $Cart->ReadMycart();
            if (is_array($ary_cart_data) && !empty($ary_cart_data)) {
                foreach ($ary_cart_data as $key => $val) {
                    if ($key == 'gifts') {
                        unset($ary_cart_data[$key]);
                    } else {
						if(is_array($val['num']) && !empty($ary_cart_data)){
							//暂不组合商品
							foreach($val['num'] as $n){
								$int_total += $n;
							}
						}else{
							$int_total += $val['num'];
						}
                    }
                }
            }
        } else {
            if (session("?Cart")) {
                $ary_cart = session("Cart");
                //购买货品的总数
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $key => $val) {
						if(is_array($val['num']) && !empty($ary_cart_data)){
							//暂不组合商品
							foreach($val['num'] as $n){
								$int_total += $n;
							}
						}else{
							$int_total += $val['num'];
						}											
                    }
                }
            }
        }
        //$this->ajaxReturn(array('status' => 200, 'totalNum' => $int_total));
        $this->ajaxReturn($int_total);
    }

    /**
     * 清空购物车
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     */
    public function doDelAll() {
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
            $Cart = D('Cart')->DelMycart();
        } else {
            session('Cart', NULL);
        }
        $this->success(L('OPERATION_SUCCESS'));
    }

    /**
     * 获取商品的pdt_id 删除 session 中的货品。支持批量删除。
     * 通过GET方式传递的pid可以是一个pdt_id也可以是pdt_id组成的数组
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     */
    public function doDel() {
        $ary_member = session("Members");
        //获取货品id
        $mix_pdt_id = $this->_get("pid");
        $mix_pdt_type = $this->_get("type"); //商品类型
        if (empty($mix_pdt_id)) {
            $this->success(L('SELECT_GOOD'));
        }
        if (!empty($ary_member['m_id'])) {
            $ary_db_carts = D('Cart')->ReadMycart();
            if (is_array($mix_pdt_id)) {
                foreach ($mix_pdt_id as $key => $val) {
                    //$val = (int)$val;
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
                //$pdt_id = (int) $mix_pdt_id;
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
            $Cart = D('Cart')->WriteMycart($ary_db_carts);
        } else {
            $mix_pdt_id = $this->_get("pid");
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
            if (is_array($mix_pdt_id)) {
                foreach ($mix_pdt_id as $val) {
                    $val = $val;
                    if (isset($ary_cart[$val]) && $ary_cart[$val]['type'] == $mix_pdt_type) {
                        unset($ary_cart[$val]);
                    }
                }
            } else {
                $mix_pdt_id = $mix_pdt_id;
                if (isset($ary_cart[$mix_pdt_id]) && $ary_cart[$mix_pdt_id]['type'] == $mix_pdt_type) {
                    unset($ary_cart[$mix_pdt_id]);
                }
            }
            session("Cart", $ary_cart);
        }
        $this->success(L('OPERATION_SUCCESS'), array(L('BACK') => U('Wap/Cart/pageCartList')));
    }

    /**
     * 根据商品的pdt_id和购买数量，修改购物车
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     */
    public function doEdit() {
        $int_pdt_nums = $this->_post("pdt_nums");
        $int_pdt_id = $this->_post("pdt_id");
        $int_good_type = $this->_post("good_type", "", 0);
		$Cart = D('Cart');
		$ary_db_carts = $Cart->ReadMycart();
		$ary_db_carts = $this->unCarts($ary_db_carts);
		foreach ($ary_db_carts as $key => &$value) {
			if ($key == $int_pdt_id) {
				$value['num'] = $int_pdt_nums;
			}
		}
		$ary_member = session("Members");
		if (!empty($ary_member['m_id'])) {
			//处理购物车信息
			$ary_db_carts = $Cart->handleCart($ary_db_carts);	
			//获取促销后优惠信息
			$pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $ary_db_carts,1);
			$subtotal = $pro_datas['subtotal']; //促销金额
			//剔除商品价格信息
			unset($pro_datas['subtotal']);
			//获取商品详细信息
			if (is_array($ary_db_carts) && !empty($ary_db_carts)) {
				$ary_cart_data = $Cart->getProductInfo($ary_db_carts,$ary_member['m_id'],1);
			}
			//处理获取的商品信息
			$ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$ary_member['m_id']);
			//处理通过促销获取的优惠信息
			$tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
			//处理pro_datas信息
			$pro_datas = $tmp_pro_datas['pro_datas'];
			//获取促销信息
			$pro_data = $tmp_pro_datas['pro_data'];
			//获取赠品信息
			$cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
			//获取订单总金额
			$ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
			unset($tmp_pro_datas);		
			if (isset($ary_db_carts[$int_pdt_id]) && $ary_db_carts[$int_pdt_id]['type'] == $int_good_type) {
				$ary_db_carts[$int_pdt_id]['num'] = (int) $int_pdt_nums;
				if ($int_good_type == 1) {
					//判断会员的当前有效积分是否满足购买积分条件
					if (false == ($flag = D('Cart')->enablePoint($ary_member['m_id'], $ary_db_carts, $info))) {
						$this->ajaxReturn(array('status'=>false,"message"=>"会员不满足购买积分条件"));exit;
					}
				}
				if(!empty($cart_gifts_data)){
					foreach($cart_gifts_data as $ary_gift){
						 $ary_db_carts['gifts'][$ary_gift['pdt_id']] = array('pdt_id' => $ary_gift['pdt_id'], 'num' => 1, 'type' => 2);
					}
				}
				D('Cart')->WriteMycart($ary_db_carts);
			} else {
				$this->ajaxReturn(array('status'=>false,"message"=>"没有此货品"));exit;
			}	
		}else{
			$this->ajaxReturn(array('status'=>false,"message"=>"请重新登陆"));exit;
		}		
		
        if(!empty($ary_member['m_id']) && isset($ary_member['m_id'])){
            $pmn_names = $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['rule_info']['name'];
            $tax_rate = $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['g_tax_rate'];
			if($tax_rate <=0){
				$tax_rate = $pro_datas[0]['products'][$int_pdt_id]['g_tax_rate'];
			}
			//税率
			$tax_price = sprintf("%0.2f",($pro_data[$int_pdt_id]['pdt_price'] * $pro_data[$int_pdt_id]['num'])*$tax_rate);
			//参数说明 tax_price税额计算，promotion_result_name:促销名称；promotion_names：促销名称 cart_gifts_data:赠品 promotion_price:商品总金额
			$result = array('stauts' => true,'tax_price' =>$tax_price,'promotion_result_name' => $pro_data[$int_pdt_id]['pmn_name'],'promotion_names'=>$pmn_names, 'cart_gifts_data' => $cart_gifts_data,'promotion_price'=>  sprintf("%0.2f",$pro_data[$int_pdt_id]['pdt_price'] * $pro_data[$int_pdt_id]['num']));
        }else{
            $this->ajaxReturn(array('status'=>false,"message"=>"请重新登陆"));exit;
        }
        $this->ajaxReturn($result);exit;
    }

    /**
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     * @param  array(["pdt_id"] =>pdt_nums);
     * @param 商品总价  优惠总额  总计
     * @param return array
     */
    public function getPtdPrice($arr_cart = array()) {
        //购买货品的总价
        $arr_price_data['all_pdt_price'] = sprintf("%0.2f", D("Cart")->getAllPrice($arr_cart));
        //应付的价格（不包括运费）
        $arr_price_data['all_price'] = sprintf("%0.2f", D("Orders")->getCartPrice($arr_cart));
        //优惠的价格
        $arr_price_data['pre_price'] = sprintf("%0.2f", $arr_price_data['all_pdt_price'] - $arr_price_data['all_price']);

        return $arr_price_data;
    }

    /**
     * 将商品列表中的商品加入购物车
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-26
     */
   public function getAddGoodsCart() {
        $tpl =$this->wap_theme_path . 'goodsCart.html';
        $ary_post = $this->_post();
        //销售类型（团购，预售，秒杀，正常购物，...）
        $item_id = $ary_post['gid'];
        $members = session('Members');

        $ary_goods_pdts = D('Goods')->getDetails($item_id, $members);
//        echo "<pre>";print_r($data);exit;
       $this->assign($ary_goods_pdts);
       $this->assign('ary_request',$ary_post);
        $this->display($tpl);
   }

    /**
     * 购物车选择商品时计算价格
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-03
     */
    public function checkCartGoods(){
        $ary_post = $this->_post();
        $Cart = D('Cart');
        $ary_cart_tmp = array();
        $ary_member = session("Members");
        if($ary_post && !empty($ary_member['m_id'])){
            $cart_data = $Cart->ReadMycart();
            if($ary_post['pid'] == 'all'){
                $ary_cart_tmp = $cart_data;
            }else{
                $ary_pid = explode(',',$ary_post['pid']);
                foreach ($cart_data as $key=>$cd){
                    foreach ($ary_pid as $pid){
                        if($pid == $key){
                            $ary_cart_tmp[$pid] = $cart_data[$key];
                        }
                    }
                }
            }
			$ary_cart_tmp = $this->unCarts($ary_cart_tmp);
			//处理购物车信息
			$cart_data = $Cart->handleCart($ary_cart_tmp);
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $ary_cart_tmp);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
			//获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$ary_member['m_id']);
            }
			//处理获取的商品信息
			$ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$ary_member['m_id']);
			//处理通过促销获取的优惠信息
			$tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
			//获取订单总金额
			$ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
			unset($tmp_pro_datas);	
        }
        $this->ajaxReturn($ary_price_data);exit;
    }

    /**
     * 购物车选择商品时计算价格
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-03
     */
    public function checkCartprice(){
        $this->setTitle('我的购物车');
        $Cart = D('Cart');
        $member = session("Members");
        $m_id = isset($member['m_id']) ? $member['m_id'] : 0;
        if (!empty($m_id)) {
            //获取购物车信息
            $tmp_cart_data = $this->unCarts($Cart->ReadMycart());   
            //处理购物车信息
            $cart_data = $Cart->handleCart($tmp_cart_data);
            //获取促销后优惠信息
            $pro_datas = D('Promotion')->calShopCartPro($member['m_id'], $cart_data,1);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
            //获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$member['m_id'],1);
            }
            //处理获取的商品信息
            $ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$member['m_id']);
            //处理通过促销获取的优惠信息
            $tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
            //处理pro_datas信息
            $pro_datas = $tmp_pro_datas['pro_datas'];
            //获取促销信息
            $pro_data = $tmp_pro_datas['pro_data'];
            //获取每个商品促销信息
            $ary_cart = $Cart->handleCartName($pro_data,$ary_cart);
            //获取赠品信息
            $cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
            //获取订单总金额
            $ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
            unset($tmp_pro_datas);          
        }else {
            
        }
        /*$this->assign("cart_data", $ary_cart);
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);*/
        $this->ajaxReturn($ary_price_data);exit;
    }

}