<?php

/**
 * 前台购物车
 * @stage 7.0
 * @package Action
 * @subpackage Home
 * @author wangguibin
 * @date 2013-04-19
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CartAction extends HomeAction {

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 向购物车内添加货品
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-26
     */
    public function doAdd() {
        $ary_parms = $this->_param();
        $type = RemoveXSS($ary_parms['type']);

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
            $data = D('Groupbuy')->getDetails((int)$ary_parms['cart']['gp_id'], $ary_member, (int)$ary_parms['cart']['pdt_id']);
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
                $ary_parms['cart']['g_id'] = $data['g_id'];
                $_SESSION['bulk_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status' => 1, 'url' => '/Ucenter/OrdersGroupbuy/pageBulkAdd'));
            }
            else {
                $this->ajaxReturn(array('status'=>0,'msg'=>$error_msg[$buy_status]));
            }
            exit;

        }
        elseif('spike' == $type){
            $data = D('Spike')->getDetails((int)$ary_parms['cart']['sp_id'], $ary_member, (int)$ary_parms['cart']['pdt_id']);
            $buy_status = $data['buy_status'];
            if($buy_status == 1) {
                $ary_parms['cart']['g_id'] = $data['g_id'];
                $_SESSION['spike_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status' => 1, 'url' => '/Ucenter/Orders/pageSpikeAdd'));
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
                $ary_parms['cart']['g_id'] = $data['g_id'];
                $_SESSION['presale_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status'=>1, 'url' => '/Ucenter/Orders/pagePresaleAdd'));
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
            $ary_where[C('DB_PREFIX')."integral.integral_id"] = (int)$ary_parms['cart']['integral_id'];
            $arr_integral = M('Integral',C('DB_PREFIX'),'DB_CUSTOM')->where(array('integral_status'=>'1','integral_id'=>(int)$ary_parms['cart']['integral_id']))->find();
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
                $ary_parms['cart']['g_id'] = (int)$ary_integral['g_id'];
                $_SESSION['integral_cart'] = $ary_parms['cart'];
                $this->ajaxReturn(array('status'=>1, 'url' => '/Ucenter/Orders/pageIntegralAdd'));
            }else{
                $this->ajaxReturn(array('status' => 0,'info'=>'该积分兑换活动不存在，请重试……'));
            }
            die;
        }
        elseif ('item' == $type) {
            $pdt_id = (int)$ary_parms['pdt_id'];
            $num = (int)$ary_parms['num'];
            $int_good_type = (int)$this->_param('good_type', '', 0);
            $ary_insert[$pdt_id] = $num;
        }
        else {
        	//积分商城
        	if($ary_parms['type'] == '1'){
        		$pdt_id = (int)$ary_parms['pdt_id'];
        		$num = (int)$ary_parms['num'];
        		$int_good_type = 1;
        		$ary_insert[$pdt_id] = $num;
        	}else{
        		$ary_insert = $ary_parms['cart'];
        	}
        }
        foreach ($ary_insert as $str_pdt => $int_num) {
            $int_num = (int)$int_num;
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
                $this->success(L('ADD_CART_FAILD'), array(
                    L('CONTINUE_BUY') => U('Home/Products'),
                    L('VIEW_CART') => U('/Cart/pageList')
                ));
            }
        } else {//保存到session
            session("Cart", $ary_session_carts);
        }
        if ($type == 'item') {
            if ($this->_post('skip') != 1) {
                $this->success(L('ADD_CART_SUCCESS'), array(
                    L('CONTINUE_BUY') => U('Home/Products'),
                    L('VIEW_CART') => U('Ucenter/Cart/pageList')
                ));
            } else {
               $this->success(L('ADD_CART_SUCCESS'), array(
                    '立即结算' => U('Ucenter/Orders/pageAdd')
                ));
            }
        } else {
            if ($ary_parms['skip'] != 1) {
                $this->success(L('ADD_CART_SUCCESS'), array(
                    L('CONTINUE_BUY') => U('Home/Products'),
                    L('VIEW_CART') => U('Ucenter/Cart/pageList')
                ));
            } else {
				if($ary_parms['type'] == '1'){
					$this->success(L('ADD_CART_SUCCESS'), array(
						'立即支付' => U('Ucenter/Orders/pageAdd',array('pid'=>$pdt_id,'pt'=>1))
					));				
				}else{
					$this->success(L('ADD_CART_SUCCESS'), array(
						'立即支付' => U('Ucenter/Orders/pageAdd')
					));					
				}
            }
        }
    }

    /**
     * 将自由组合商品加入购物车
     * 需要传过来的参数
     * fc_id 自由组合ID
     * pdt_id 商品货号
     * num 商品货号对应商品数量
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-14
     */
    public function doAddFreeCollocation() {
        $ary_parms = $this->_param();
        $ary_member = session("Members");
		$ary_parms['m_id'] = $ary_member['m_id'];	
		$ary_parms['fc_type'] = 4;
		$add_res = D('FreeRecommend')->addFreeCollocation($ary_parms);
		if($add_res['status'] == true){
			$this->success($add_res['msg'],$add_res['url']);
		}else{
			$this->error($add_res['msg']);
		}
		exit;
    }

   /**
     * 在session中拿取 货品的id  和货品的数（pdt_nums）
     * @author jiye
     * @date 2012-12-11
     * @return type array
     */
	public function pageList() {
        $this->setTitle('我的购物车');
        $Cart = D('Cart');
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
         	$tmp_cart_data = $Cart->ReadMycart();
			//处理购物车信息
			$cart_data = $Cart->handleCart($tmp_cart_data);
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data,1);
			$subtotal = $pro_datas['subtotal'];
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
			//获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$ary_member['m_id'],0,1);
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
        }else {
			$return_data = $this->getUnloginCart();
			$ary_cart_data = $return_data['ary_cart_data'];
			$ary_price_data = $return_data['ary_price_data'];
			unset($return_data);
        }
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
        $this->assign("cart_data", $ary_cart_data);
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        $this->assign("stock_data", $stock_data);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/customerCart.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/customerCart.html';
        }
        $this->assign('skip',$_POST['skip']);
        $this->display($tpl);
    }
	
	/**
     * 在session中拿取 货品的id  和货品的数（pdt_nums）
     * @author 获取未登陆状态下购物车信息
     * @date 2012-12-11
     * @return type array
     */
	public function getUnloginCart(){
		if (session("?Cart")) {
			$ary_cart = session("Cart");
			//货品信息
			//购买货品的总价
			$ary_cart_data = D('Cart')->getProductInfo($ary_cart);
			$ary_price_data = D('Cart')->getPriceDataUnlogin($ary_cart_data);
		}
		return array('ary_cart_data'=>$ary_cart_data,'ary_price_data'=>$ary_price_data);
	}
	
	
	/**
     * 在session中拿货品的数（pdt_nums）
     * @author wangguibin
     * @date 2014-06-10
     * @return type array
     */
	public function pageListNum() {
        $this->setTitle('我的购物车');
        $Cart = D('Cart');
        $ary_member = session("Members");
		$ary_price_data = array();
        if (!empty($ary_member['m_id'])) {
         	$cart_data = $Cart->ReadMycart();
			//商品总数
			$ary_price_data['all_nums'] = 0;
			foreach($cart_data as $val){
				//自由组合商品
				if($val['type'] == '4' || $val['type'] == '6'){
					foreach($val['num'] as $num){
						$ary_price_data['all_nums'] += $num;
					}
				}else{
					$ary_price_data['all_nums'] += $val['num'];
				}				
			}
        }else {
            if (session("?Cart")) {
                $ary_cart = session("Cart");
				foreach($ary_cart as $val){
					//自由组合商品
					if($val['type'] == '4' || $val['type'] == '6'){
						foreach($val['num'] as $num){
							$ary_price_data['all_nums'] += $num;
						}
					}else{
						$ary_price_data['all_nums'] += $val['num'];
					}				
				}
			}
        }
        $this->assign("price_data", $ary_price_data);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/customerCart.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/customerCart.html';
        }
        $this->display($tpl);
    }
	
    /**
     * 在session中拿取 货品的总数（pdt_nums）
     * @author czy
     * @date 2013-08-16
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

                        $int_total += $val['num'];
                    }
                }
            }
        } else {
            if (session("?Cart")) {
                $ary_cart = session("Cart");
                //购买货品的总数
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $key => $val) {
                        $int_total += $val['num'];
                    }
                }
            }
        }
        $this->ajaxReturn(array('status' => 200, 'totalNum' => $int_total));
    }

    /**
     * 清空购物车
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-28
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
     * @author jiye
     * @date 2012-12-11
     * @modify zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-28
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
        $this->success(L('OPERATION_SUCCESS'), array(L('BACK') => U('Ucenter/Cart/pageList')));
    }

    /**
     * 根据商品的pdt_id和购买数量，修改购物车
     * @author jiye
     * @date 2012-12-11
     * @modify zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-28
     */
    public function doEdit() {
        $int_pdt_nums = $this->_post("pdt_nums");
        $int_pdt_id = $this->_post("pdt_id");
        $int_good_type = $this->_post("good_type", "", 0);
        $Cart = D('Cart');
        $ary_member = session("Members");
		if (!empty($ary_member['m_id'])) {
			$ary_db_carts = D('Cart')->ReadMycart();
            foreach ($ary_db_carts as $key => &$value) {
                if ($key == $int_pdt_id) {
                    $value['num'] = $int_pdt_nums;
                }
            }
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
		}
        else{
			$arr_cart = (session("?Cart")) ? session("Cart") : array();
            if (isset($arr_cart[$int_pdt_id]) && $arr_cart[$int_pdt_id]['type'] == $int_good_type) {
                $arr_cart[$int_pdt_id]['num'] = (int) $int_pdt_nums;
                $arr_cart[$int_pdt_id]['type'] = (int) $int_good_type;
                session("Cart", $arr_cart);
				//购买货品的总价
				$ary_cart_data = D('Cart')->getProductInfo($arr_cart);				
				$ary_price_data = $Cart->getPriceDataUnlogin($ary_cart_data);
            } else{
				$this->ajaxReturn(array('status'=>false,"message"=>"没有此货品"));exit;
			}
		}
        if(!empty($ary_member['m_id']) && isset($ary_member['m_id'])){
            $pmn_names = $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['rule_info']['name'];
            $tax_rate = $pro_datas[$pro_data[$int_pdt_id]['pmn_id']]['products'][$int_pdt_id]['g_tax_rate'];
			if($tax_rate <=0){
				$tax_rate = $pro_datas[0]['products'][$int_pdt_id]['g_tax_rate'];
			}
			//税率
			$tax_price = sprintf("%0.2f",($pro_data[$int_pdt_id]['pdt_price'] * $pro_data[$int_pdt_id]['num'])*$tax_rate);
            //dump($pro_data);die;
			//参数说明 tax_price税额计算，promotion_result_name:促销名称；promotion_names：促销名称 cart_gifts_data:赠品 promotion_price:商品总金额
			$result = array('stauts' => true,
                'tax_price' =>$tax_price,
                'promotion_result_name' => $pro_data[$int_pdt_id]['pmn_name'],
                'promotion_names'=>$pmn_names,
                'cart_gifts_data' => $cart_gifts_data,
                'promotion_price'=>  sprintf("%0.2f",$pro_data[$int_pdt_id]['pdt_price'] * $pro_data[$int_pdt_id]['num'])
            );
        }else{
            $result = array('stauts' => true,'tax_rate' =>$tax_rate,'promotion_item_price' =>$promotion_item_price,'promotion_result_name' => $promotion_result['name'], 'cart_gifts_data' => $cart_gifts_data);
        }
        $this->ajaxReturn($result);exit;
    }

    /**
     * @author jiye
     * @date 2012-12-11
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
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getAddGoodsCart() {
        $ary_post = $this->_post();
        $data = array();
        $goods = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_where = array();
        $ary_where['g.g_id'] = array('eq', $ary_post['gid']);
        $goodsSpec = D('GoodsSpec');
        $ary_goods = $goods
                ->where($ary_where)
                ->field('g.g_id,g.g_sn,g.g_on_sale_time,g_salenum,`g`.`g_is_prescription_rugs` AS `g_is_pres`,g.g_off_sale_time,gi.g_name,gi.g_price,gi.g_stock,gi.g_unit,gi.g_remark,gi.g_desc,g_picture,g_new,g_hot,`g`.`gb_id` AS `gb_id`')
                ->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)')
                ->find();
//        echo "<pre>";print_r($goods->getLastSql());exit;
        if (!empty($ary_goods) && is_array($ary_goods)) {
            $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price', 'pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods');
            $where = array();
            $where['g_id'] = $ary_post['gid'];
            $where['pdt_status'] = '1';
            $ary_pdt = $products->field($ary_product_feild)->where($where)->limit()->select();

            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                foreach ($ary_pdt as $kypdt => $valpdt) {
                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                    if (!empty($specInfo['color'])) {
                        if (!empty($specInfo['color'][1])) {
                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                        }
                    }
                    if (!empty($specInfo['size'])) {
                        if (!empty($specInfo['size'][1])) {
                            $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                        }
                    }
//                    $ary_pdt['skuName'][$kypdt] = $specInfo['sku_name'];
                    $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                    $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                    $stock_i += $valpdt['pdt_stock'];
                }
                $ary_goods['g_stock'] = $stock_i;

                foreach ($skus as $key => &$sku) {
                    $skus[$key] = array_unique($sku);
                }
            }
            if (!empty($skus)) {
                $data['skuNames'] = $skus;
            }
        }
        $data['gid'] = $ary_goods['g_id'];
        $data['gsn'] = $ary_goods['g_sn'];
        $data['offsale'] = $ary_goods['g_off_sale_time'];
        $data['gname'] = $ary_goods['g_name'];
        $data['gprice'] = $ary_goods['g_price'];
        $mprice = D("Price")->getMarketPrice($data['gid']);
        //货品中最大价格
        $data['mprice'] = $mprice;
        $data['gstock'] = $ary_goods['g_stock'];
        $data['skus'] = $ary_pdt;
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/goodsCart.html';
        $this->assign("filter", $ary_post);
        $this->assign("data", $data);
//        echo "<pre>";print_r($data);exit;
        $this->display($tpl);
    }

    /**
     * 判断用户是否登录(针对蓝源)
     * @author huhaiwei<huhaiwei@guanyisoft.com>
     * @date 2014-09-29
     */
    public function checkname(){
        $m_id = $_SESSION['Members']['m_id'];
        if($m_id == ''){
            $this->ajaxReturn(array('status'=>false));
        }else{
            $this->ajaxReturn(array('status'=>true));
        }
    }
}

