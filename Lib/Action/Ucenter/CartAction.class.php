<?php

/**
 * 前台购物车
 * @stage 7.0
 * @package Action
 * @subpackage Ucenter
 * @author jiye
 * @date 2012-12-11
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CartAction extends CommonAction {

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 订单确认信息提交
     * wangguibin
     * 2013-08-21
     */
    public function doAjaxOrderAdd() {
        $int_o_id = $this->_post('oid');
        $int_type = $this->_post('type');
        $obj = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_where = array('o_id' => $int_o_id, 'o_pay_status' => 1, 'o_status' => 1);
        $ary_orders = $obj->where($ary_where)->find();
        //    echo $obj->getLastSql();exit;
        if (count($ary_orders) > 0 && !empty($ary_orders)) {
            $this->error('此订单不能再次下单', array('确定' => U('Ucenter/Orders/pageShow/', array('oid' => $int_o_id))));
            exit;
        } else {
            $obj_where = array();
            //普通商品、积分商品、组合商品
            //商品类型，5:团购商品 4:自由组合商品,3组合商品，2赠品， 1积分商品，0普通商品
            $obj_where['o_id'] = $int_o_id;
            $obj_where['_string'] = 'oi_type !=2 and oi_type !=4 ';
            $order_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
            $order_items1 = $order_obj->where($obj_where)->field('oi_nums,pdt_id,oi_type')->select();
            //购物车组合
            $cart_data = array();
            foreach ($order_items1 as $item1) {
                $cart_data[$item1['pdt_id']] = array(
                    "pdt_id" => $item1['pdt_id'],
                    "num" => $item1['oi_nums'],
                    "type" => $item1['oi_type']
                );
            }
            //自由组合商品
            $free_where = array();
            $free_where['o_id'] = $int_o_id;
            $free_where['oi_type'] = 4;
            $order_items2 = $order_obj->where($free_where)->field('group_concat(oi_nums) as oi_nums,group_concat(pdt_id) as pdt_id,fc_id')->group('fc_id')->select();
            foreach ($order_items2 as $item2) {
                $cart_data['free' . $item2['fc_id']] = array(
                    'fc_id' => $item2['fc_id'],
                    'pdt_id' => explode(',', $item2['pdt_id']),
                    'num' => explode(',', $item2['oi_nums']),
                    'type' => 4
                );
            }
            //检测商品是否有效
            $cart_data = $this->checkOrder($cart_data);
            if (empty($cart_data)) {
                $this->error('商品已不能购买，加入购物车失败', array('确定' => U('Ucenter/Orders/pageShow/', array('oid' => $int_o_id))));
            } else {
                $order_trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
                $order_trans->startTrans();
                $ary_db_carts = D('Cart')->ReadMycart();
                foreach ($cart_data as $key => $cart) {
                    $ary_db_carts[$key] = $cart;
                }
                //加入购物车
                $cart_data = D('Cart')->WriteMycart($ary_db_carts);
                if ($int_type != 5) {
                    //如果重新下单类型为团购商品，不作废原有订单
                    $return_orders = $obj_orders->where(array('o_id' => $int_o_id))->save(array('o_status' => 2));
                    //订单日志记录
                    if ($return_orders) {
                        $ary_orders_log = array(
                            'o_id' => $int_o_id,
                            'ol_behavior' => '重新下单作废原订单',
                            'ol_uname' => $_SESSION['Members']['m_name'],
                            'ol_create' => date('Y-m-d H:i:s')
                        );
                        $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    }
                }

                if ($return_orders > 0) {
                    //冻结积分释放掉
                    $res_status = D('Orders')->releasePoint($int_o_id);
                    if (!$res_status) {
                        $order_trans->rollback();
                        $this->error('重新下单释放冻结积分失败', array('确定' => U('Ucenter/Orders/pageList')));
                    }
                    $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_o_id))->find();
                    if (!empty($ary_coupon) && is_array($ary_coupon)) {
                        $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_o_id))->save(array('c_used_id' => 0, 'c_order_id' => 0, 'c_is_use' => 0));
                        if (!$res_coupon) {
                            $order_trans->rollback();
                            $this->error('优惠券还原失败', array('确定' => U('Ucenter/Orders/pageList')));
                            exit;
                        }
                    }
                } else {
                    $order_trans->rollback();
                    $this->error('此订单不能重新下单', array('确定' => U('Ucenter/Orders/pageList')));
                    exit;
                }
            }
            $order_trans->commit();
            $this->success(L('ADD_CART_SUCCESS'), array(
            	'继续购物'=>U('Home/Index/index'),
            	'返回购物车'=>U('Ucenter/Cart/pageList'),
                //'立即支付' => U('Ucenter/Orders/pageAdd')
            ));
        }
    }

    /**
     * 加入购物车数据验证
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-28
     */
    public function checkOrder($cart_data) {
        $ary_member = session("Members");
        $date = date('Y-m-d H:i:s');
        $ary_cart = $cart_data;
        //自由组合商品搭配分开
        $ary_product_ids = array();
        foreach ($ary_cart as $k => $ary_sub) {
            if ($ary_sub['type'] == '4') {
                $fc_id = $ary_sub['fc_id'];
                //判断自由组合商品是否存在或是否在有效期
                $fc_data = M('free_collocation', C('DB_PREFIX'), 'DB_CUSTOM')
                                ->where(array('fc_id' => $fc_id, 'fc_status' => 1))->find();
                if (empty($fc_data)) {
                    $ary_db_carts = D('Cart')->ReadMycart();
                    unset($ary_db_carts[$k]);
//						D('Cart')->WriteMycart($ary_db_carts);
//						$this->error(L('自由推荐组合已不存在'));
//						return false;						
                }
                if ($fc_data['fc_start_time'] != '0000-00-00 00:00:00' && $date < $fc_data['fc_start_time']) {
                    unset($ary_db_carts[$k]);
//						$this->error(L($value['g_sn'].'自由推荐组合活动还没有开始'));
//						return false;
                }
                if ($date > $fc_data['fc_end_time']) {
                    unset($ary_db_carts[$k]);
//						$this->error(L($value['g_sn'].'自由推荐组合活动已结束'));
//						return false;
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
                unset($ary_cart[$value['pdt_id']]);
//					$this->error(L($value['g_sn'].'下架商品'));exit;
//					return false;
            }
            if ($ary_cart[$value['pdt_id']]['num'] > $value['pdt_stock'] && !$value['g_is_combination_goods'] && !$value['g_pre_sale_status']) {//购买数量
                unset($ary_cart[$value['pdt_id']]);
//					$this->error(L($value['g_sn'].'商品库存不足'));
//					return false;
            }
            if ($value['g_is_combination_goods']) {
                $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value['pdt_id'],$ary_member['m_id']);
                if ($ary_cart[$value['pdt_id']]['num'] > $tmp_stock) {
                    unset($ary_cart[$value['pdt_id']]);
//						$this->error(L($value['g_sn'].'组合商品库存不足'));
//						return false;
                }
                if ($ary_cart[$value['pdt_id']]['num'] > $value['pdt_max_num'] && $value['pdt_max_num'] > 0) {
                    unset($ary_db_carts[$value['pdt_id']]);
//						D('Cart')->WriteMycart($ary_db_carts);
//						$this->error(L($value['g_sn'].'组合商品购买数不能最大于最大下单数'));
//						return false;
                }
                if ($value['g_on_sale_time'] != '0000-00-00 00:00:00' && $date < $value['g_on_sale_time']) {
                    unset($ary_db_carts[$value['pdt_id']]);
//						$this->error(L($value['g_sn'].'组合商品活动还没有开始'));
//						return false;
                }
                if ($value['g_off_sale_time'] != '0000-00-00 00:00:00' && $date > $value['g_off_sale_time']) {
                    unset($ary_db_carts[$value['pdt_id']]);
//						$this->error(L($value['g_sn'].'组合商品活动结束'));
//						return false;
                }
            }
            if ($value['pdt_sale_price'] <= 0 && $ary_cart[$value['pdt_id']]['type'] != 1 && $value['g_gifts'] != 1) {//价格
                unset($ary_db_carts[$value['pdt_id']]);
//					$this->error(L($value['g_sn'].'商品价格不正确'));
//					return false;
            }
        }
        return $ary_cart;
    }

    /**
     * 向购物车内添加货品
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-26
     */
    public function doAdd() {
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
            $this->success(L('ADD_CART_FAILD'), array(
                L('VIEW_CART') => U('/Cart/pageList')
            ));
        }

        if ($this->_post('skip') != 1) {
            $this->success(L('ADD_CART_SUCCESS'), array(
                L('CONTINUE_BUY') => U('Ucenter/Products/pageList'),
                L('VIEW_CART') => U('Ucenter/Cart/pageList')
            ));
        } else {
            $this->success(L('ADD_CART_SUCCESS'), array(
                L('VIEW_CART') => U('Ucenter/Cart/pageList')
            ));
        }
    }

    /**
     * 快速加入购物车
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-18
     */
    public function Fastcart() {
        $ary_post = $this->_post();
        $res = D("GoodsProducts")->Search(array('pdt_sn' => $ary_post['pdt_sn']), array('pdt_id'));
        if (!empty($res)) {
            $pdt_id = $res['pdt_id'];
            $ary_member = session("Members");
            $stock = D("GoodsStock")->getProductStockByPdtid($pdt_id,$ary_member['m_id']);
            $fast_num = $ary_post['fast_num'];
            if ($ary_post['fast_num'] > $stock) {
                $this->success(L('NOT_ENOUGH_STOCK'));
                return false;
            } else {
                $ary_db_carts = D('Cart')->ReadMycart();
                if (is_array($ary_db_carts) && !empty($ary_db_carts)) {
                    foreach ($ary_db_carts as $key => $int_num) {
                        if ($int_num['num'] <= 0 || !is_int($int_num['num'])) {
                            unset($ary_db_carts[$key]);
                        }
                        if (array_key_exists($key, $ary_db_carts) && $ary_db_carts[$pdt_id]['type'] == 0) {
                            $ary_db_carts[$key]['num']+=$fast_num;
                        } else {
                            $ary_db_carts[$pdt_id] = array('pdt_id' => $pdt_id, 'num' => $fast_num, 'type' => 0);
                        }
                    }
                } else {
                    $ary_db_carts[$pdt_id] = array('pdt_id' => $pdt_id, 'num' => $fast_num, 'type' => 0);
                }
                $Cart = D('Cart')->WriteMycart($ary_db_carts);
                if ($Cart) {
                    $this->success(L('ADD_CART_SUCCESS'), array(
                        L('OK') => U('Ucenter/Cart/pageList')
                    ));
                } else {
                    $this->success(L('ADD_CART_FAIL'));
                }
            }
        } else {
            $this->success(L('SEARCH_FAIL'));
        }
    }
	
    public function pageList(){
        $this->getSubNav(1, 0, 30);
        $this->getSeoInfo(L('MY_CART'));
        $Cart = D('Cart');
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
			//获取购物车信息
            $tmp_cart_data = $Cart->ReadMycart();
			//处理购物车信息
			$cart_data = $Cart->handleCart($tmp_cart_data);
			//获取促销后优惠信息
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data,1);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
			//获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$ary_member['m_id'],1);
            }
			//处理获取的商品信息
			$ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$ary_member['m_id']);
            //处理通过促销获取的优惠信息
			$tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
//            dump($tmp_pro_datas);die;
			//处理pro_datas信息
			$pro_datas = $tmp_pro_datas['pro_datas'];
			//获取促销信息
			$pro_data = $tmp_pro_datas['pro_data'];
			//获取赠品信息
			$cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
			//获取订单总金额
			$ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
			unset($tmp_pro_datas);
        }else{}
        $this->assign("gifts_data", $cart_gifts_data);
        $this->assign("price_data", $ary_price_data);
        $this->assign("cart_data", $ary_cart);
        $this->assign("pro_data",$pro_data);
        $this->assign("promotion",$pro_datas);
		$resdata = D('SysConfig')->getConfigs('IS_AUTO_CART','IS_AUTO_CART', $str_fileds=null, $str_limit=null,1);
		$this->assign("IS_AUTO_CART",intval($resdata['IS_AUTO_CART']['sc_value']));
		//跨境贸易
		$is_foreign = D('SysConfig')->getConfigs('GY_SHOP','GY_IS_FOREIGN', $str_fileds=null, $str_limit=null,1);
		$this->assign($is_foreign);

        $sys_config = D('SysConfig')->getConfigs('GY_GOODS');
        $is_on_mulitiple = empty($sys_config['IS_ON_MULTIPLE']['sc_value']) ? 2: $sys_config['IS_ON_MULTIPLE']['sc_value'];
        $this->assign('is_on_mulitiple', $is_on_mulitiple);
        $this->display("Cart:pageCartList");
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
        $this->ajaxReturn($ary_price_data);
    }
    
    /**
     * 在session中拿取 货品的id  和货品的数（pdt_nums） 和货品的类型（type）
     * @author jiye
     * @date 2012-12-11
     * @return type array
     */
    public function pageCartList() {
        $this->getSubNav(1, 0, 30);
        $this->getSeoInfo(L('MY_CART'));
        $Cart = D('Cart');
        $ary_member = session("Members");
        $combo_all_price = 0;
        //组合价格
        $free_all_price = 0;
        if (!empty($ary_member['m_id'])) {
            $cart_data = $Cart->ReadMycart();     
            if(!empty($cart_data) && is_array($cart_data)){
                foreach($cart_data as &$val){
                	if($val['type'] == 0){
                		$ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$val['pdt_id']))->find();
                		$val['g_id'] = $ary_gid['g_id'];
                	}
                }
            }
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data);
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
            
            if (is_array($pro_datas) && !empty($pro_datas)) {
                $ary_pdt = array();
                $data_group = array();
                $pro_data = array();
                
                foreach ($cart_data as $key => $value) {
                    if ($key == 'gifts') {
                        unset($cart_data[$key]);
                    } else {
                        $pdt_id = $value['pdt_id'];
                        $int_type = isset($value['type']) ? $value['type'] : 0;
                        $ary_pdt[$key] = array('pdt_id' => $pdt_id, 'num' => $value['num'], 'type' => $int_type, 'fc_id' => $value['fc_id']);
                    }
                    if($value['type'] == '0'){
                    	$gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$value['pdt_id']))->find();
                    	//获取商品商品所在的商品分组 Terry<wanghui@guanyisoft.com>
                    	$ary_goods_group = $this->getGoodsGroup($gid['g_id']);
                    	
                    	if(!empty($ary_goods_group) && is_array($ary_goods_group)){
                    		foreach($ary_goods_group as $keygroup=>$valgroup){
                    			//                            $data_group[] = $valgroup;
                    			$ary_pro_data[$gid['g_id']][$valgroup['gg_id']] = $this->getPromotionGroup($valgroup['gg_id']);
                    			if(empty($ary_pro_data[$gid['g_id']][$valgroup['gg_id']])){
                    				unset($ary_pro_data[$gid['g_id']][$valgroup['gg_id']]);
                    			}
                    			//                            echo "<pre>";print_r($pro_data);
                    			if(empty($pro_data[$gid['g_id']])){
                    				$pro_data[$gid['g_id']] = $ary_pro_data[$gid['g_id']][$valgroup['gg_id']];
                    			}else{
                    				if($pro_data[$gid['g_id']]['pmn_order'] < $ary_pro_data[$gid['g_id']][$valgroup['gg_id']]['pmn_order']){
                    					$pro_data[$gid['g_id']] = $ary_pro_data[$gid['g_id']][$valgroup['gg_id']];
                    				}
                    			}
                    		}
                    	}
                    } 
                }
//                exit;
//               echo "<pre>";print_r($ary_pdt);exit;
                //购买货品的总价
                $ary_price_data['all_pdt_price'] = sprintf("%0.2f", $Cart->getAllPrice($ary_pdt));
                if (is_array($ary_pdt) && !empty($ary_pdt)) {
                    $ary_cart_data = $Cart->getProductInfo($ary_pdt);
                }
                $ary_pdt_info['rule_info'] = array('pmn_id' => null, 'again_discount' => null);

                foreach ($ary_cart_data as &$info) {
                    if (isset($info['pdt_id'])) {
                        if (!empty($info['rule_info']['pmn_id']) && empty($ary_pdt_info['rule_info']['pmn_id'])) {
                            $ary_pdt_info['rule_info'] = $info['rule_info'];
                        }
                        if ($info['type'] != 3) {
                            $info['pdt_rule_name'] = $info['rule_info']['name'];
                            $ary_pdt_info[$info['pdt_id']] = array('pdt_id' => $info['pdt_id'], 'num' => $info['pdt_nums'], 'type' => $info['type'], 'price' => $info['f_price']);
                        }
                        //添加产品是否允许购买
                        $info['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $info['g_id'],1);
                    } else {
                        //自由组合权限判断
                        if ($info[0]['type'] == 4) {
                            foreach ($info as &$sub_info) {
                                //添加产品是否允许购买
                                $sub_info['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $sub_info['g_id'],1);
                            }
                        }
                    }
                }
               // echo "<pre>";print_r($ary_cart_data);
                if (!empty($ary_cart_data) && is_array($ary_cart_data)) {
                	foreach ($ary_cart_data as $k => $val1) {
                		//自由推荐
                		if ($val1[0]['type'] == '4') {
                			foreach ($val1 as $item_info) {
                				$ary_price_data['all_price'] += $item_info['pdt_momery'];
                				$free_all_price += $item_info['pdt_momery'];
                			}
                		} else {
                			$promition_rule_name = $val1['pdt_rule_name'];
                			//应付的价格（不包括运费）
                			if ($val1['type'] == 0) {
                				$ary_price_data['all_price'] += $val1['pdt_momery'];
                			} elseif ($val1['type'] == 1) {
                				$ary_price_data['consume_point'] += intval($val1['pdt_momery']); //消耗总积分
                			} elseif ($val1['type'] == 3) {
                				$ary_price_data['type'] = 3;
                				$ary_price_data['all_price'] += $val1['pdt_momery'];
                				$combo_all_price += $val1['pdt_momery'];
                			}
                		}
                	}
                }
                $ary_price_data['pre_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
            }
        }
        //echo "<pre>";print_r($cart_data);exit;
        //是否开启赠送积分
        $ary_config = D('PointConfig')->getConfigs();
        if ($ary_config) {
            $ary_price_data['consumed_ratio'] = isset($ary_config['is_consumed']) && $ary_config['is_consumed'] == 1 ? $ary_config['consumed_ratio'] : 0;
        } else {
            $ary_price_data['consumed_ratio'] = 0;
        }
        //赠送积分 自由组合商品不赠送积分
        $ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price'] - $free_all_price);
        //赠送积分用下面的
        //$ary_price_data['reward_point'] =  D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price']);
        //库存提示
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $ary_member_level_id = $ary_member['member_level']['ml_id'];
        if ((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK'] == 1) {
            if ($stock_data['USER_TYPE'] == 'all') {
                $stock_data['level'] = true;
            } else {
                $ary_user_level = explode(",", $stock_data['USER_TYPE']);
                $stock_data['level'] = array_search($ary_member_level_id, $ary_user_level);
            }
        }
        
        //订单促销规则获取 自由组合商品不参与促销
        $ary_param = array('action' => 'cart', 'mid' => $ary_member['m_id'], 'all_price' => $ary_price_data['all_price'] - $combo_all_price - $free_all_price, 'ary_pdt' => $ary_pdt_info);
        $promotion_result = D('Price')->getOrderPrice($ary_param); 
        if (!empty($promotion_result['all_price']) && !empty($promotion_result['code'])) {
            if (!empty($promotion_result['gifts_pdt']) && $promotion_result['code'] == 'MZENPIN') {
                foreach ($promotion_result['gifts_pdt'] as $gifts) {
                    $cart_data['gifts'][$gifts['pdt_id']] = array('pdt_id' => $gifts['pdt_id'], 'num' => 1, 'type' => 2);
                }
                $cart_gifts_data = $Cart->getProductInfo($cart_data['gifts']);
                $Cart = D('Cart')->WriteMycart($cart_data);
            } else {
                $cart_gifts_data = array();
                $Cart = D('Cart')->WriteMycart($cart_data);
            }
            $cart_promition_rule_name = $promotion_result['name'];
            $promotion_price = sprintf("%0.2f", $promotion_result['all_price'] - $promotion_result['price']);
            if (!empty($ary_price_data['pre_price'])) {//组合商品优惠金额
                $promotion_price+=$ary_price_data['pre_price'];
            }
            $ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
            $ary_price_data['all_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $promotion_price);
        } else {//再次过滤赠品
            $Cart = D('Cart')->WriteMycart($cart_data);
        }
        
        $this->assign("promition_rule_name", $cart_promition_rule_name);
        $this->assign("cart_data", $ary_cart_data);
        $this->assign("gifts_data", $cart_gifts_data);
//        echo "<pre>";print_r($cart_gifts_data);exit;
        $this->assign("price_data", $ary_price_data);
        $this->assign("stock_data", $stock_data);
        $this->assign("pro_data",$pro_data);
//        echo "<pre>";print_r($pro_data);exit;
        $this->display();
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
        $this->success(L('OPERATION_SUCCESS'), array(L('OK') => U('Ucenter/Cart/pageList')));
    }

    /**
     * 获取商品的pdt_id 删除 session 中的货品。支持批量删除。
     * 通过GET方式传递的pid可以是一个pdt_id也可以是pdt_id组成的数组
     * @author jiye
     * @date 2012-12-11
     * @modify zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-28
	 * @modify by wanghaoyu 2014-1-16
	 * 对于:商品已加入到购物车,同时管理员从后台把该商品删除,对已作废货品做删除操作
     */
    public function doDel() {
        $ary_member = session("Members");
        //获取货品id
        $mix_pdt_id = $this->_get("pid");
		$mix_pdt_type = $this->_get("type"); //商品类型
        /*if (empty($mix_pdt_id)) {			
            $this->success(L('SELECT_GOOD'));
        }*/
        if (!empty($ary_member['m_id'])) {
            $ary_db_carts = D('Cart')->ReadMycart();
			if(!empty($ary_db_carts) && is_array($ary_db_carts)){
                foreach($ary_db_carts as &$cart_val){
                    if($cart_val['type'] == '0'){
                        $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$cart_val['pdt_id']))->find();
						//by wanghaoyu 商品加入购物车后,验证该商品是否在后台直接删除或者数据非法
						if(NULL === $ary_gid){
							$mix_pdt_id = $cart_val['pdt_id'];
						}
                    }
                }
            }
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
     * 购物车赠品
     * @author listen
     * @date 2013-05-20
     */
    public function GiftsPageList($ary_gid) {
        if (!empty($ary_gid)) {
            foreach ($ary_gid as $k => $v) {
                $ary_goods = D('Goods')->where(array($v))->select();
            }
        }
        $this->assign('goods', $ary_goods);
        $this->display();
    }

    /**
     * 计算购物车商品价格
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-09-05
     */
    public function goodsCount($buyInfo){
        $this->sum           = 0;       //原始总额(优惠前)
        $this->final_sum     = 0;       //应付总额(优惠后)
        $this->weight        = 0;       //总重量
    	$this->reduce        = 0;       //减少总额
    	$this->count         = 0;       //总数量
        $this->promotion     = array(); //促销活动规则文本
    	$this->proReduce     = 0;       //促销活动规则优惠额
        $this->isFreeFreight = false;   //是否免运费
        $user_id      = $this->user_id;
        $group_id     = $this->group_id;
    } 
    
    /**
    *  说明:二维数组去重
    *
    *  @param    array2D    要处理二维数组
    *  @param    stkeep     是否保留一级数组键值(默认不保留)
    *  @param    ndformat   是否保留二级数组键值(默认保留)
    *
    *  @return   output     返回去重后的数组
    */
    function unique_arr($array2D,$stkeep=false,$ndformat=true){
        if($stkeep){    //一级数组键可以为非数字
            $stArr = array_keys($array2D);
        }
        if($ndformat){   //二级数组键必须相同
            $ndArr = array_keys(end($array2D));
        }
        foreach ($array2D as $v){  //降维
            $v = join(",",$v);
            $temp[] = $v;
        }
        $temp = array_unique($temp);
        foreach ($temp as $k => $v){  //数组重新组合
            if($stkeep){
                $k = $stArr[$k];
            }
            if($ndformat){
                $tempArr = explode(",",$v);
                foreach($tempArr as $ndkey => $ndval){
                    $output[$k][$ndArr[$ndkey]] = $ndval;
                }
            }else{
                $output[$k] = explode(",",$v);
            }
        }
        return $output;
    }
    
    /**
     * 获取商品所有分组
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-09-05
     */
    public function getGoodsGroup($gid){
        if(!empty($gid) && $gid > 0){
            $ary_goods_group = M("goods_group",C('DB_PREFIX'),'DB_CUSTOM')
                            ->field("".C('DB_PREFIX')."goods_group.gg_id,".C('DB_PREFIX')."goods_group.gg_name,".C('DB_PREFIX')."goods_group.gg_status")
                            ->join(" ".C('DB_PREFIX')."related_goods_group ON ".C('DB_PREFIX')."related_goods_group.gg_id=".C('DB_PREFIX')."goods_group.gg_id")->where(array(C('DB_PREFIX').'related_goods_group.g_id'=>$gid,C('DB_PREFIX').'goods_group.gg_status'=>"1"))->select();
            return $ary_goods_group;
        }else{
            return array();
        }
    }
    
    /**
     * 获取分组应用的所有促销规则
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-09-05
     */
    public function getPromotionGroup($grid){
        if(!empty($grid) && $grid > 0){
            $ary_where = array();
            $date_time = $this->getDateTime();
            $ary_where[C('DB_PREFIX').'related_promotion_goods_group.gg_id'] = $grid;
            $ary_where[C('DB_PREFIX').'promotion.pmn_start_time'] = array("ELT",$date_time);
            $ary_where[C('DB_PREFIX').'promotion.pmn_end_time'] = array("EGT",$date_time);
            $ary_progroup = M("promotion",C('DB_PREFIX'),'DB_CUSTOM')
                            ->field("".C('DB_PREFIX')."promotion.*")
                            ->join(" ".C('DB_PREFIX')."related_promotion_goods_group ON ".C('DB_PREFIX')."promotion.pmn_id=".C('DB_PREFIX')."related_promotion_goods_group.pmn_id")
                            ->where($ary_where)
                            ->order(C('DB_PREFIX')."promotion.pmn_order DESC")
                            ->find();
//            echo "<pre>";print_r(M("promotion",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());
//            echo "<pre>";print_r($ary_progroup);exit;
            $pro_data = array();
            if(!empty($ary_progroup)){
               $pro_data = $ary_progroup;
//                echo "<pre>";print_r($pro_data);
                return $pro_data;
            }
            //return $ary_progroup;
        }
    }
    
    /**
     * @package  根据指定的格式输出时间
     * @param  String  $format 格式为年-月-日 时:分：秒,如‘Y-m-d H:i:s’
     * @param  String  $time   输入的时间
     * @author Terry<wanghui@guanyisoft.com>
     * @return String  $time   时间
     */
    public function getDateTime($format='',$time=''){
        $time   = !empty($time)  ? $time  : time();
        $format = !empty($format)? $format: 'Y-m-d H:i:s';
        return date($format,$time);
    }
}
