<?php

/**
 * 订单相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Joe
 * @date 2012-12-13
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersModel extends GyfxModel {

    /**
     * 价格对象
     * @var obj
     */
    private $Price;

	 
    /**
     * 构造方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
        $this->Price = D('Price');
        $this->orders = M('Orders',C('DB_PREFIX'),'DB_CUSTOM');
        $this->orders_items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM');
    }

    /**
     * 插入一条订单
     *
     * @param array $ary_order 订单基本信息
     * @author Joe  <qianyijun@guanyisoft.com>
     * @since 1.0
     * @date 2012-12-13
     */
    public function doInsert($ary_order = array()) {
        $ary_order['o_create_time'] = date('Y-m-d H:i:s');
        $ary_order['o_update_time'] = date('Y-m-d H:i:s');
		//加密存储手机和固话号码
		if($ary_order['o_receiver_telphone']) {
			$ary_order['o_receiver_telphone']= encrypt($ary_order['o_receiver_telphone']);
		}
		if($ary_order['o_receiver_mobile']
            && !strpos($ary_order['o_receiver_mobile'], '*')) {
			$ary_order['o_receiver_mobile'] = encrypt($ary_order['o_receiver_mobile']);
		}
		if($ary_order['o_receiver_idcard'] && !strpos($ary_order['o_receiver_idcard'], '*')) {
			$ary_order['o_receiver_idcard'] = encrypt($ary_order['o_receiver_idcard']);
		}
        $return_orders = $this->orders->data($ary_order)->add();
        return $return_orders;
    }

    /**
     * 插入订单详情数据
     * @param array $ary_orders_itmes 订单基本信息
     * @author listen  <lixin@guanyisoft.com>
     * @since 1.0
     * @date 2013-01-06
     */
    public function doInsertOrdersItems($ary_orders_itmes = array()) {
        $ary_orders_itmes['oi_create_time'] = date('Y-m-d H:i:s');
        $return_orders_items = $this->orders_items->data($ary_orders_itmes)->add();
//        echo "<pre>";print_r($this->orders_items->getLastSql());exit;
        return $return_orders_items;
    }

    /**
     * 根据会员ID和货品id获取订单总价（不包括物流和支付手续费用）
     * @parme $ary_cart 货品id和购买数量（必选）
     * @parme $m_id 会员id
     * @author Joe <qianyijun@guanyisoft.com>
     * @return float $return_price 订单总金额
     * @date 2012-12-14
     */
    public function getCartPrice($ary_cart = array(), $m_id = 0) {
        $return_price = 0.00;
        if ($m_id == 0) {
            $m_id = $_SESSION['Members']['m_id'];
        }
        foreach ($ary_cart as $key => $pdt) {
            $return_price = ($this->Price->getPrice($key, $m_id) * $pdt);
        }
        return $return_price;
    }

    /**
     * 根据购物的商品信息获得商品总重量
     * @author listen
     * @return 订单的商品的总重
     * @date 2013-01-05
     */
    public function getGoodsAllWeight($ary_data = array(),$is_cache=0) {
        $ary_data = !empty($ary_data ) ? $ary_data  : D('Cart')->ReadMycart();
        $int_weight = 0;
        if (isset($ary_data)) {
            foreach ($ary_data as $key => $value) {
            	if($value['type'] == '4'){
            		foreach($value['pdt_id'] as $key=>$val){
						if($is_cache == 1){
							$ary_all_weight = D('Gyfx')->selectOneCache('goods_products',array("pdt_weight"), array("pdt_id" => $val), $ary_order=null,100);						
						}else{
							$ary_all_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $val))->find();							
						}
	                    $int_weight += ($ary_all_weight['pdt_weight'] * $value['num'][$key]);
            		}
            	}else{
	                if($value['type']==3){
	                    $combo_weight=D('ReletedCombinationGoods')->getComboWeight($value['pdt_id']);
	                    $int_weight += $combo_weight;
	                }
	                elseif($key=='gifts' && is_array($value) && !empty($value)){
	                    foreach ($value as $gifts) {
							if($is_cache == 1){
								$ary_gifts_weight = D('Gyfx')->selectOneCache('goods_products',array("pdt_weight"), array("pdt_id" => $gifts['pdt_id']), $ary_order=null,100);						
							}else{
								$ary_gifts_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $gifts['pdt_id']))->find();						
							}														
	                        $int_weight += ($ary_gifts_weight['pdt_weight'] * $gifts['num']);
	                    }
	                }
                    elseif($value['type']==6){
                        foreach ($value['pdt_id'] as $ck=>$coll){
							if($is_cache == 1){
								$ary_all_weight = D('Gyfx')->selectOneCache('goods_products',array("pdt_weight"), array("pdt_id" => $coll), $ary_order=null,100);						
							}else{
								$ary_all_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $coll))->find();						
							}							
                            $int_weight += ($ary_all_weight['pdt_weight'] * $value['num'][$ck]);
                        }
                    }
	                else{
						if($is_cache == 1){
							$ary_all_weight = D('Gyfx')->selectOneCache('goods_products',array("pdt_weight"), array("pdt_id" => $key), $ary_order=null,100);						
						}else{
							$ary_all_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $key))->find();					
						}						
                        $int_weight += ($ary_all_weight['pdt_weight'] * $value['num']);
	                }           		
            	}
            }
            return $int_weight;
        }
    }
	
	/**
     * 根据购物的商品信息获得商品总重量(第三方平台订单)
     * @author zhangjiasuo
     * @return 订单的商品的总重
     * @date 2013-09-09
     */
    public function getThdGoodsAllWeight($ary_data = array()) {
		$ary_data = session("trdShoppingCart");
        $int_weight = 0;
        if (isset($ary_data)) {
            foreach ($ary_data as $key => $value) {
            	if($value['type'] == '4'){
            		foreach($value['pdt_id'] as $key=>$val){
            			$ary_all_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $val))->find();
	                    $int_weight += ($ary_all_weight['pdt_weight'] * $value['num'][$key]);
            		}
            	}else{
	                if($value['type']==3){
	                    $combo_weight=D('ReletedCombinationGoods')->getComboWeight($value['pdt_id']);
	                    $int_weight += $combo_weight;
	                }
	                elseif($key=='gifts' && is_array($value) && !empty($value)){
	                    foreach ($value as $gifts) {
	                        $ary_gifts_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $gifts['pdt_id']))->find();
	                        $int_weight += ($ary_gifts_weight['pdt_weight'] * $gifts['num']);
	                    }
	                }
	                else{
	                    $ary_all_weight = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_weight"))->where(array("pdt_id" => $key))->find();
	                    $int_weight += ($ary_all_weight['pdt_weight'] * $value['num']);
	                }           		
            	}
            }
            return $int_weight;
        }
    }

    /**
     * 获取订单信息以及详情
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部
     * @author listen
     * @return array $ary_orders_info 订单信息
     * @date 2013-01-07
     */
    public function getOrdersInfo($ary_where = array(), $ary_field = '',$group='') {
        $ary_orders_info = M('view_orders',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->group($group)->select();
        return $ary_orders_info;
    }
	
	/**
     * 获取订单物流信息
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部
	 * @author zhuwenwei
     * @return array $ary_logi_info 订单信息
     * @date 2015-08-05
     */
	public function getLogiInfo($logi_where = array(), $logi_field = '') {
        $ary_logi_info = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->field($logi_field)->where($logi_where)->select();
        return $ary_logi_info;
    }
	
    /**
     * 订单状态（付款，退货，发货状态）
     * @param   arry $orders 订单数组 一维数组 key字段名=> 值
     * @param string $mark 标识名称，付款->o_pay_status,退货/退款->oi_refund_status
     * @param int $int_or_id 退款单ID
     * 发货-> oi_ship_status  订单状态 ->o_status
     * @author listen
     * @return string 状态文字。
     * @date 2013-01-07
     */
    public function getOrderItmesStauts($mark, $orders = array(), $int_or_id = null) {
        //付款状态orders表中
        if (isset($mark) && $mark == 'o_pay_status') {
            switch ($orders['o_pay_status']) {
                case '0':
                    $str_orders_status = '未支付';
                    break;
                case '1':
                    $str_orders_status = '已支付';
                    break;
                case '2':
                    $str_orders_status = '处理中';
                    break;
                case '3':
                    $str_orders_status = '部分支付';
                    break;
                default:
                    $str_orders_status = '未支付';
                    break;
            }
        } else if (isset($mark) && $mark == 'oi_refund_status') {
            $ary_where = array('fx_orders_refunds_items.o_id'=>$orders['o_id'],
                              'fx_orders_refunds_items.oi_id'=>$orders['oi_id']
                             );
            if(isset($int_or_id)){
                $ary_where['fx_orders_refunds_items.or_id'] = $int_or_id;
            }
            $ary_refunds = M('orders_refunds_items',C('DB_PREFIX'),'DB_CUSTOM')
                            ->join('fx_orders_refunds ON fx_orders_refunds_items.or_id = fx_orders_refunds.or_id')
                            ->field(array('ori_num','or_processing_status','or_refund_type'))
                            ->where($ary_where)->select();//echo "<pre>";print_r($ary_refunds);exit;
           //统计实际已退货或者
           $oi_refund_status = '';
           $int_refund_img = 0;//退货中数量
           $int_refund = 0;//退货成功数量
           $int_refund_reject = 0;//退货驳回数量
           $int_orderitems = intval($orders['oi_nums']);
           if($ary_refunds) {
                foreach($ary_refunds as $val){
                    if($val['or_refund_type'] == 1) {
                        //退款
                       if($val['or_processing_status'] == 0) {
                           $oi_refund_status = 2;
                           break;
                       }
                       elseif($val['or_processing_status'] == 1) {
                            $oi_refund_status = 4;
                            break;
                       }
                       elseif($val['or_processing_status'] == 2) {
                            $oi_refund_status = 6;
                            break;
                       }
                    }
                    else{
                        //退货
                        if($val['or_processing_status'] == 0) {
                           $int_refund_img += $val['ori_num'];
                       }
                       elseif($val['or_processing_status'] == 1) {
                            $int_refund += $val['ori_num'];//退货成功数量
                            
                       }
                       elseif($val['or_processing_status'] == 2) {
                            $int_refund_reject += $val['ori_num'];
                       }
                    }
                }
				
				if($int_refund == $int_orderitems) {
				    $oi_refund_status = 5;
				}
                elseif($int_refund < $int_orderitems && $int_refund>0) {
                    $oi_refund_status = 7;
                } 
                elseif($int_refund == 0 && $int_refund_img>0) {
                    if($int_refund_img == $int_orderitems) {
                        $oi_refund_status = 3;
                    }
                    elseif($int_refund_img < $int_orderitems){
                        $oi_refund_status = 8;
                    } 
                }
                elseif($int_refund == 0 && $int_refund_reject>0) {
                    $oi_refund_status = 6;
                } 
            }
            
            //退货/退款 正常订单返回空
            switch ($oi_refund_status) {
                case '2':
                    $str_orders_status = '退款中';
                    break;
                case '3':
                    $str_orders_status = '退货中';
                    break;
                case '4':
                    $str_orders_status = '退款成功';
                    break;
                case '5':
                    $str_orders_status = '退货成功';
                    break;
                case '6':
                    $str_orders_status = '被驳回';
                    break;
                case '7':
                    $str_orders_status = '部分退货成功';
                    break;
                case '8':
                    $str_orders_status = '部分退货中';
                    break;
                default:
                    $str_orders_status = '';
            }
        } else if (isset($mark) && $mark == 'oi_ship_status') {
            //发货
            switch ($orders['oi_ship_status']) {
                case '0':
                    $str_orders_status = '待发货';
                    break;
                case '1':
                    $str_orders_status = '仓库准备';
                    break;
                case '2':
                    $str_orders_status = '已发货';
                    break;
                case '3':
                    $str_orders_status = '缺货';
                    break;
                case '4':
                    $str_orders_status = '退货';
                    break;
                default:
                    $str_orders_status = '待发货';
            }
        } else if (isset($mark) && $mark == 'o_status') {
            //订单状态 正常返回空
            switch ($orders['o_status']) {

                case '2':
                    $str_orders_status = '作废';
                    break;
                case '3':
                    $str_orders_status = '暂停';
                    break;
                case '4':
                    $str_orders_status = '完成';
                    break;
                case '5':
                    $str_orders_status = '已确认';
                     break;
                default:
                    $str_orders_status = '';
            }
        }
        return $str_orders_status;
    }

    /**
     * 统计发货，退货订单状态
     * @param $o_id  订单id
     * @return ary 返回订单中的 发货 退款/退货状态
     * @author listen
     * @date 2013-01-11
     */
    public function getOrdersStatus($o_id) {
        $str_status = array();
        $ary_where = array('o_id' => $o_id);
        $ary_delivery = D('ViewOrders')->where($ary_where)->field(array('oi_ship_status', 'oi_refund_status', 'o_id', 'oi_nums'))->select();
		
        if (!empty($ary_delivery) && is_array($ary_delivery)) {
            $ary_refunds = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->field(array('or_refund_type','or_processing_status'))->where($ary_where)->select();
            $int_total = 0;
            $int_deliver_num = 0; //发货成功
            foreach ($ary_delivery as $k => $v) {
                $int_total += $v['oi_nums'];
                if ($v['oi_ship_status'] == 2) {
                    $int_deliver_num++;
                }
            }
            
             if(is_array($ary_refunds) && count($ary_refunds)>0){
			   if(isset($ary_refunds[0]) && $ary_refunds[0]['or_refund_type'] == 1){
                    switch($ary_refunds[0]['or_processing_status']){
                        case 0:
                              $str_status['refund_status'] = '退款中';
                              break;
                        case 1:
                              $str_status['refund_status'] = '退款成功';
                              break;
                        case 2:
                              $str_status['refund_status'] = '退款驳回';
                              break;
                        default:
                              $str_status['refund_status'] = ''; //没有退款
                    }
                 }
                 elseif(isset($ary_refunds[0]) && $ary_refunds[0]['or_refund_type'] == 2){
				    $int_refund_goods = 0; //退货成功
					$int_refund_goods_ing = 0; //退货中*/
					$int_reject = 0;
					$ary_refunds_items = M('orders_refunds_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('or_refund_type', 'or_processing_status'))->where($ary_where)->select();
                    foreach($ary_refunds_items as $val){
					    if($val['or_processing_status'] == 0) $int_refund_goods_ing++;
                        elseif($val['or_processing_status'] == 1) $int_refund_goods++;
						elseif($val['or_processing_status'] == 2) $int_reject++;
                    }
					if($int_refund_goods>0){
					    $str_status['refund_goods_status'] = '退货成功';
					}
					if($int_refund_goods_ing>0){
					    $str_status['refund_goods_status'] = '退货中';
					}
					if($int_reject>0){
					    $str_status['refund_goods_status'] = '退货驳回';
					} 
                 }
               }
           
            if ($int_deliver_num == count($ary_delivery)) {
                $str_status['refund_type'] = 2; //1是退款，2是退货
                $str_status['deliver_status'] = '已发货';
            } else if ($int_deliver_num < count($ary_delivery)) {
                if ($int_deliver_num == 0) {
                    $str_status['refund_type'] = 1; //1是退款，2是退货
                    $str_status['deliver_status'] = '未发货';
                } else {
                    $str_status['refund_type'] = 0; //1是退款，2是退货，0一部分退款，一部分退货
                    $str_status['deliver_status'] = '部分发货';
                }
            }
        }
        return $str_status;
    }

    /**
     * 获取货品的信息
     * @author listen
     * @data 2013-01-11
     * @param return   array
     */
    public function getProductInfo($ary_data = array()) {
        $member = session("Members");
        if (is_array($ary_data) && isset($ary_data)) {
            $i = 0;
            foreach ($ary_data as $k => $v) {
                $arr_newdata[$i] = M('view_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->find();
                $i++;
            }
            return $arr_newdata;
        }
    }

    /**
     * 订单支付
     * @author listen
     * @param int $int_oid 订单id
     * @param array $ary_order 需要更新订单的信息
     * @param int $int_type 变更类型，0充值，1消费，2冻结，3解冻，4退款 ，5在线消费（第三方平台消费）
     * @date 2013-01-13
     * @return array
     */
    public function orderPayment($int_oid, $ary_order = array(), $int_type = 1) {
    
        $int_oid = trim($int_oid,"订单编号:");
        //订单数据
        $money = $ary_order['o_pay']; //订单实际支付的钱
        //订单所有支付的钱 = 本次支付金额+已支付金额
        //获取已支付金额
        //获取已支付金额
        $o_order = $this->where(array('o_id'=>$int_oid))->field('o_pay,o_all_price,o_pay_status')->find();
		$o_pay_status = $o_order['o_pay_status'];
        $o_pay = $o_order['o_pay'];
		$ary_order['o_pay'] = $money + $o_pay;
		if($ary_order['o_pay']>$o_order['o_all_price']){
			$ary_order['o_pay'] = $o_order['o_all_price'];
			//return array('result' => false, 'message' => '订单已经支付');
		}
        $date = date('Y-m-d H:i:s');
        $ary_order['o_update_time'] = $date;
        //查询用户信息
        $ary_member = D('Members')->where(array('m_id' => $ary_order['m_id']))->find();
        //查询支付方式
        $ary_payment = D('PaymentCfg')->where(array('pc_id' => $ary_order['o_payment']))->find();
        //判断是否开启自动审核功能
        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
        if($ary_order['o_pay_status'] == 1 && $IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1){
            $ary_order['o_audit'] = 1;
        }
        if (isset($int_oid)) {
            //更新订单状态
            $int_order_update = $this->where(array('o_id' => $int_oid))->save($ary_order);
            if (false == $int_order_update) {
                return array('result' => false, 'message' => '更新订单状态出错');
            }
			
			//是否开启门店提货
			$zt_info =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
			$is_zt = $zt_info['IS_ZT']['sc_value'];
			//提货之后发短信
			if($ary_order['o_pay_status'] == '1' && $is_zt == 1){
				if(isset($ary_order ['lt_id'])){
					$ary_logistic_where = array(
					'lt_id' => $ary_order ['lt_id']
					);
					$ary_field = array(
						'lc_abbreviation_name'
					);
					$ary_log = D('Logistic')->getLogisticInfo($ary_logistic_where, $ary_field);	
					$ary_order['mobile'] = decrypt($ary_order['o_receiver_mobile']);
					unset($ary_order['o_receiver_mobile']);					
					if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && !empty($ary_order['mobile'])){
						$ary_order['o_id'] = $int_oid;
						D('SmsTemplates')->sendSmsGetCode($ary_order);
					}
				}
			}
            //更新订单日志
            $str_m_name = $ary_member['m_name'];

            $ary_orders_log = array('o_id' => $int_oid, 
                                    'ol_behavior' => '支付成功', 
                                    'ol_uname' => $str_m_name, 
                                    'ol_create' => $date
                              );
            $order_log=D('OrdersLog')->where(array('o_id' => $int_oid))->find();
			if(empty($order_log)){
                $int_orders_log = D('OrdersLog')->add($ary_orders_log);
			}else{
                $int_orders_log = D('OrdersLog')->where(array('ol_id'=>$order_log['ol_id']))->save($ary_orders_log);
            }
            if (false === $int_orders_log) {
                return array('result' => false, 'message' => '更新订单日志出错');
            }

            $float_m_all_cost = $ary_member['m_all_cost'] + $money;
            $ary_member_data = array('m_all_cost' => $float_m_all_cost);

            //更新消费总记录
            $ary_member_where = array('m_id' => $ary_member['m_id']);
            $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
            if (false === $int_return_members) {
                return array('result' => false, 'message' => '更新会员消费记录出错');
            }

            //商品库存扣除
			//完全支付完成之后再扣库存，过滤掉部分支付
			if(($ary_order['o_pay_status'] == '1' && $o_pay_status == 0) || ($ary_order['o_pay_status'] == '3')){
				$order_item_field=array('g_id','pdt_id','oi_nums');
				$return_ary_order=D('OrdersItems')->getOrderItemsInfo($int_oid,$order_item_field);
				foreach ($return_ary_order as $products) {
					$good_sale_status=D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id'=>$products['g_id']))->find();
					//if($good_sale_status['g_pre_sale_status']!=1 && $ary_order['oi_type'] !=5 && $ary_order['oi_type'] !=8){
					//尼玛，蛋疼的不知团购商品为什么不扣库存
					//尼玛，蛋疼的团购商品扣了两次库存
                    //尼玛，蛋疼的预售商品为什么不扣库存
					if($good_sale_status['g_pre_sale_status']!=1 && $ary_order['oi_type'] !=8){
					//如果是预售商品不扣库存
						$array_result = D('GoodsProducts')->UpdateStock($products['pdt_id'],$products['oi_nums']);
							if(false == $array_result["status"]){
								D('GoodsProducts')->rollback();
								$this->error($array_result['msg'] . ',CODE:' . $array_result["code"]);
							}
					}
				}				
			}

            //更新流水账记录
            $ary_account_where = array(
                'm_id' => $ary_member['m_id'],
                'ra_money' => (0 - (float) $money),
                'ra_type' => $int_type, //消费
                'ra_payment_method' => $ary_payment['pc_custom_name'],
                'ra_create_time' => $date
            );
            if($int_type == 1){
                //预存款消费
                //预存款有变更
              //  $ary_account_where['ra_before_money'] = (float) ($ary_member['m_balance'] - $money);
                $ary_account_where['ra_before_money'] = (float) ($ary_member['m_balance'] + $money);
                $ary_account_where['ra_after_money'] = $ary_member['m_balance'];
                
            }elseif($int_type == 5){
                //在线消费
                //预存款无变更
                $ary_account_where['ra_before_money'] = $ary_member['m_balance'];
                $ary_account_where['ra_after_money'] = $ary_member['m_balance'];
                $ary_account_where['ra_payment_sn'] = $ary_order['o_source_id'];
            }
            $int_running_account = M('running_account',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_account_where);

            if (false == $int_running_account) {
                return array('result' => false, 'message' => '更新流水账记录失败');
            }
            
            return array('result' => true);
        } else {
            return array('result' => false, 'message' => '参数错误');
        }
		
    }

    /**
     * 获得退款退货原因数组
     * @param int $int_type 退款退货类型  退款:1,退货:2
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-3-25
     */
    public function getReason($int_type){
        $ary_reason =array();
        $module = "GY_ORDER_AFTERSALE_CONFIG";
        $key = "RETURN_REASON";
        $desc = "退货/退款理由";
        $ary_return_data = D('SysConfig')->getCfgByModule($module);
        unset($ary_return_data['SETAFTERSALE']);
        $ary_return_data['content'] = '';
        if(!empty($ary_return_data) && is_array($ary_return_data)){
            $sc_value = explode(',', $ary_return_data['RETURN_REASON']);
            $ary_return_data['content'] = $sc_value[0];
            $ary_data = explode("\n",$ary_return_data['content']);
        }
        switch($int_type){
            case 1:
                $ary_reason = array('卖家缺货',
                                  '收到的物品不符',
                                  '商品质量问题',
                                  '未按约定时间发货',
                                  '买家拍错商品',
                                  '与卖家协商一致',
                                  '其他',
                            );
                //$str_result = array_pop($ary_reason);
				if($sc_value[1] == $int_type){
					$ary_merge_result = array_merge($ary_reason, $ary_data);
					array_push($ary_merge_result,$str_result);
				}else{
					$ary_merge_result = $ary_reason;
				}
              break;
            case 2:
                $ary_reason = array('七天无理由退换货',
                                  '收到假货',
                                  '商品需要维修',
                                  '发票问题',
                                  '收到商品破损',
                                  '商品错发/漏发',
                                  '收到商品描述不符',
                                  '商品未按约定时间发货',
                                  '商品质量问题'
                            );
			  if($sc_value[1] == $int_type){
				  $ary_merge_result = array_merge($ary_reason, $ary_data);
			  }else{
				  $ary_merge_result = $ary_reason;
			  }
              break;
             default:
             return;
        }
        return $ary_merge_result;
    }
      /**
     * 物流信息
     * @author listen   
     * @param int $int_oid 订单id
     * @date 2013-3-4
     */
    public function ordersLogistic($int_oid){
        $ary_return_delivery = array();
        //发货信息
        $ary_where = array('o_id' => $int_oid);
        $ary_delivery = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
        if(isset($ary_delivery['od_id'])){
            $ary_return_delivery['delivery'] = $ary_delivery;
            $ary_delivery_items =  M('orders_delivery_items',C('DB_PREFIX'),'DB_CUSTOM')
                                    ->join('fx_orders_items ON fx_orders_items.oi_id = fx_orders_delivery_items.oi_id')
                                    ->where(array('od_id'=>$ary_delivery['od_id']))
                                    ->select();
            $ary_return_delivery['deluvery_item'] = $ary_delivery_items;
        }
        return $ary_return_delivery;
    }
    /**
     * 获得一段时间内付款订单
     * @return ary 返回订单信息
     * @author Zhangjiasuo
     * @date 2013-04-03
     */
    public function GetOrderPayStatus($status) {
        $oneday = mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y"));
        $create_time = date('Y-m-d H:i:s',$oneday);
        if($status==1){
            $where['o_pay_status'] = 1;
            $where['o_create_time'] = array('EGT', $create_time);
        }else{
            $where['o_pay_status'] = 0;
            $where['o_create_time'] = array('LT', $create_time);
        }
        $ary_trade = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
                     ->field('o_pay_status,o_id,o_status')->where($where)->select();
        return $ary_trade;
    }
    /**
     * 更新订单状态
     * @author Zhangjiasuo
     * @date 2013-04-07
     */
    public function UpdateOrderStatus($id,$status) {
        $trade_data['o_status']=$status;
        $res_products = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id' => $id))->save($trade_data);
        //订单作废释放积分
        if($res_products && $status == 2) {
            $this->releasePoint($id);
        }
    }
    /**
     * 更新订单冻结积分和会员表冻结积分
     * @param int $ary_data 订单id和会员id和冻结积分组成的数组
     * @author czy
     * @date 2013-04-24
     */
    public function updateFreezePoint($ary_data = array()) {
		if(empty($ary_data))  $this->error('缺乏必要参数', array('失败' => U('Ucenter/Orders/pageAdd')));
        if($ary_data['freeze_point']>0) $trade_data['o_freeze_point'] = $ary_data['freeze_point'];
        if($ary_data['reward_point']>0) $trade_data['o_reward_point'] = $ary_data['reward_point'];
//        $res_status = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
//            ->where(array('o_id' => $ary_data['o_id']))
//            ->save($trade_data);
//		echo M('orders',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();die;
        if(isset($ary_data['freeze_point']) && $ary_data['freeze_point']>0){
            //生成冻结积分日志
            $ary_log = array(
                        'type'=>6,
                        'consume_point'=> $ary_data['freeze_point'],
                        'reward_point'=> 0,
                        );
            $ary_info =D('PointLog')->addPointLog($ary_log,$ary_data['m_id']);
            if($ary_info['status'] == 1){
                $res_member_status = M('Members',C('DB_PREFIX'),'DB_CUSTOM')
                    ->where(array('m_id' => $ary_data['m_id']))
                    ->setInc('freeze_point',$ary_data['freeze_point']);
//                echo M('Members',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();die;
                return $res_member_status;
            }else {
                return false;
            }
		}
		return true;
    }
    /**
     * 订单作废释放订单冻结积分和会员表冻结积分
     * @param int $int_oid 订单id
     * @author czy
     * @date 2013-04-25
     */
    public function releasePoint($int_oid) {
        $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $int_oid))->find();
        if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
             $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
             if ($ary_member && $ary_member['freeze_point'] > 0) {
                //订单作废返还冻结积分日志
                $ary_log = array(
                            'type'=>7,
                            'consume_point'=> 0,
                            'reward_point'=> $ary_orders['o_freeze_point']
                            );
                $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                if($ary_info['status'] == 1){
                     $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_orders['o_freeze_point'];
                     $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                     return $res_member;
                 }
             }
        }
        return false;
    }
    /**
     * 获取订单商品信息
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部
     * @author zhangjiasuo
     * @return array $ary_orders_info 订单信息
     * @date 2013-06-19
     */
    public function getOrdersItem($ary_where = array(), $ary_field = '') {
        $ary_orders_item = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->select();
        return $ary_orders_item;
    }
    
    /**
     * 获取订单信息以及详情
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部
     * @author zhangjiasuo
     * @return array $ary_result 订单信息
     * @date 2013-07-08
     */
    public function getOrdersData($ary_where = array(), $ary_field = '',$group='') {
        $ary_result = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
                      ->join('fx_orders_items ON fx_orders_items.o_id = fx_orders.o_id')
                      ->join('fx_members ON fx_members.m_id = fx_orders.m_id')
                      ->field($ary_field)->where($ary_where)->group($group)->select();
        return $ary_result;
    }

    public function getOrders($ary_where = array(), $ary_field = '') {
        $ary_result = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
                      ->field($ary_field)->where($ary_where)->select();
        return $ary_result;
    }
    
    /**
     * 修改订单支付方式
     * @param string $int_oid 查询订单where条件
     * @author zhangjiasuo
     * @return Boolean $res 返回值
     * @date 2013-07-18
     */
    public function UpdateOrdersPayment($int_oid, $payment_id) {
        $data['o_payment'] = $payment_id;
        $res = $this->where(array('o_id' => $int_oid))->save($data);
        return $res;
    }
    
    /**
     * 获取订单信息
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getOrderInfo($params = array()){
        //订单搜索条件
        $ary_where = array();

        //如果需要根据订单号进行搜素
        if (!empty($params['o_id']) && isset($params['o_id'])) {
            $ary_where['o_id'] = $params['o_id'];
        }
        if(!empty($params['o_create_time_start']) && !empty($params['o_create_time_end'])){
            if($params['o_create_time_start'] > $params['o_create_time_end']){
                $ary_where['o_create_time'] = array('between',array($params['o_create_time_end'],$params['o_create_time_start']));
            }else{
                $ary_where['o_create_time'] = array('between',array($params['o_create_time_start'],$params['o_create_time_end']));
            }
            
        }else if(!empty($params['o_create_time_start']) && empty($params['o_create_time_end'])){
            $ary_where['o_create_time'] = array('EGT',$params['o_create_time_start']);
        }else if(!empty($params['o_create_time_end'])){
            $ary_where['o_create_time'] = array('ELT',$params['o_create_time_end']);
        }
        if(!empty($params['o_ids']) && isset($params['o_ids'])){
            $ary_where['o_id'] = array("in",$params['o_ids']);
        }
        $page = ($params['start'] - 1) * 20;
        $pagesize = ($params['end'])*20;
        //数据分页处理，获取符合条件的记录数并分页显示
        $count = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        $obj_page = new Page($count, 1000);
        //订单数据获取
        $array_order = array('o_id' => 'desc');
        $string_limit = $page . ',' . $pagesize;
        $ary_orders_info = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->order($array_order)->limit($string_limit)->select();

        //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = M('PaymentCfg',C('DB_PREFIX'),'DB_CUSTOM')->where(1)->getField("pc_id,pc_custom_name");

//echo M('orders',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
       // echo "<pre>";print_r($ary_orders_info);exit;
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            //订单状态
            $ary_status = array('o_status' => $v['o_status']);
            $str_status = $this->getOrderItmesStauts('o_status', $ary_status);
            $ary_orders_info[$k]['str_status'] = $str_status;

            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
            $str_pay_status = $this->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
            $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];
            //获取会员名称
            $ary_orders_info[$k]['m_name'] = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id" => $v["m_id"]))->getField("m_name");

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
//                        echo "<pre>";print_r($delivery_company_info);
            //订单物流公司名称
            $ary_orders_info[$k]['delivery_company_name'] = $delivery_company_info['lc_is_enable'] ? $delivery_company_info['lc_name'] : '已删除';
            //$ary_orders_info[$k]['delivery_company_name'] = isset($delivery_company_info[$v["lt_id"]])?$delivery_company_info[$v["lt_id"]]:'已删除';
            
            //售后状态
            $ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->order('or_create_time desc')->select();
            if(!empty($ary_afersale) && is_array($ary_afersale)){
                foreach($ary_afersale as $keyaf=>$valaf){
                    //退款
                    if($valaf['or_refund_type'] == 1){
                            switch($valaf['or_processing_status']){
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

                    }elseif($valaf['or_refund_type'] == 2){         //退货
                        switch($valaf['or_processing_status']){
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
        }
        return $ary_orders_info;
        
    }
    /**
     * 获取订单基础信息，不适用视图
     * @param bigint $int_oid <p>订单ID</p>
     * @param array $ary_field <p>需要查询的字段，默认为所有</p>
     * @author Haophper
     * @date 2013-8-15
     * @return array
     */
     public function getOrderBaseInfo($int_oid, $ary_field='*') {
     	return $this->where(array('o_id'=>$int_oid))->field($ary_field)->find();
     }
     /**
      * 更新订单内容
      * @param intger $int_o_id <p>订单ID</p>
      * @param array $ary_update <p>要更新的订单内容</p>
      * @author haophper
      * @date 2013-8-15
      * @return bool
      */
      public function updateOrderInfo($int_o_id, $ary_update) {
	  //加密存储手机和固话号码
		if($ary_update['o_receiver_telphone'] 
			&& !strpos($ary_update['o_receiver_telphone'], '*')) {
			$ary_update['o_receiver_telphone']= encrypt($ary_update['o_receiver_telphone']);
		}else{
			unset($ary_update['o_receiver_telphone']);
		}
		if($ary_update['o_receiver_mobile']
			&& !strpos($ary_update['o_receiver_mobile'], '*')) {
			$ary_order['o_receiver_mobile'] = encrypt($ary_update['o_receiver_mobile']);
		}else{
			unset($ary_update['o_receiver_mobile']);
		}
          return $this->where(array('o_id' => $int_o_id))->save($ary_update);
      }
      /**
      * 统计时间段内用户消费金额
      * @param intger $uid 
      * @param array $time 
      * @author zhangjiasuo
      * @date 2013-09-30
      * @return int
      */
      public function getUserConsumptionMoney($uid, $time) {
          $totla_price=0;
          $str_m_ids='';
          $member = session("Members");
          $ary_condition['m_recommended']=$member['m_name'];
          $ary_m_ids=D("Members")->getRecommended($ary_condition,array('m_id'));

          foreach($ary_m_ids as $key=>$val){
              $str_m_ids.=','.$val['m_id'];
          }
          
          $str_m_ids =$uid.$str_m_ids; 

          $ary_field=array('fx_orders.o_pay,fx_orders.promotion');
          $ary_where=array('fx_orders.m_id' => array('in',$str_m_ids),
                           'fx_orders.o_create_time' =>  array('egt',$time),
                           'fx_orders_items.oi_refund_status' =>1,
                           'fx_orders_items.oi_ship_status' =>2
                           );
          $result = $this->join('fx_orders_items ON fx_orders_items.o_id = fx_orders.o_id')
                      ->field($ary_field)->where($ary_where)->group('fx_orders.o_id')->select();
          foreach($result as $key=>$val){
              if(!empty($val['promotion'])){
                $datas=unserialize($val['promotion']);
                $promotion=array_shift($datas);
                if(isset($promotion['pmn_class'])&&!empty($promotion['pmn_class'])){//活动促销售金额减半
                    $totla_price+=sprintf("%0.3f",$result[$key]['o_pay']/2);
                }
				if(isset($promotion['products'])&&!empty($promotion['products']) &&!isset($promotion['pmn_class'])){
					$promotion_flg=false;
					foreach($promotion['products'] as $promotion_rule){
						if(!empty($promotion_rule['rule_info']['name'])){
							$promotion_flg=true;
						}
					}
					if($promotion_flg){//活动促销售金额减半
						$totla_price+=sprintf("%0.3f",$result[$key]['o_pay']/2);
					}else{
						$totla_price+=sprintf("%0.3f",$result[$key]['o_pay']);
					}
				}
              }else{
                  $totla_price+=sprintf("%0.3f",$result[$key]['o_pay']);
              }
          }
          return $totla_price;
      } 
      /**
      * 统计时间段内我的返利
      * @param ary $ary_where 
      * @author zhangjiasuo <Zhangjiasuo@guanyisoft.com>
      * @date 2013-10-09
      * @return array
      */
      public function getpayBack($ary_where = array(),$ary_field='',$limit='') {
          $time = mktime(0,0,0,date("m"),date("d")-30,date("Y"));
          $date=date("Y-m-d H:i:s", $time);
          $ary_where=array('fx_orders.m_id' => array('in',$ary_where['m_id']),
                           'fx_orders_items.oi_create_time' =>  array('egt',$date),
                           'fx_orders_items.oi_refund_status' =>1,
                           'fx_orders_items.oi_ship_status' =>2
                           );
          $result = $this->join('fx_orders_items ON fx_orders_items.o_id = fx_orders.o_id')
                         ->join('fx_members ON fx_members.m_id = fx_orders.m_id')
                         ->where($ary_where)->group('fx_orders.o_id')->field($ary_field)
                         ->limit($limit['start'],$limit['end'])->select();
          return $result;
      } 
      /**
      * 我的返利统计
      * @param ary $ary_where 
      * @author zhangjiasuo <Zhangjiasuo@guanyisoft.com>
      * @date 2013-10-09
      * @return array
      */
      public function getpayBackCount($ary_where = array()) {
          $m_id=$ary_where['m_id'];
          unset($ary_where['m_id']);
          $ary_m_ids=D("Members")->getRecommended($ary_where,array('m_id'));
          $str_m_ids='';
          foreach($ary_m_ids as $key=>$val){
            $str_m_ids.=','.$val['m_id'];
          }
          
          if(empty($ary_where['m_name'])){
              $str_m_ids =$m_id.$str_m_ids; 
          }
          $time = mktime(0,0,0,date("m"),date("d")-30,date("Y"));
          $date=date("Y-m-d H:i:s", $time);
          $condition=array('fx_orders.m_id' => array('in',$str_m_ids),
                           'fx_orders_items.oi_create_time' =>  array('egt',$date),
                           'fx_orders_items.oi_refund_status' =>1,
                           'fx_orders_items.oi_ship_status' =>2
                           ); 
          $count = $this->join('fx_orders_items ON fx_orders_items.o_id = fx_orders.o_id')
                         ->join('fx_members ON fx_members.m_id = fx_orders.m_id')
                         ->where($condition)->count('distinct fx_orders.o_id'); 
          return array('count'=>$count,'m_id'=>$str_m_ids);
      } 
    
    /**
     * 如果存在满减订单，根据订单里商品所属子公司平分优惠金额
     *
     * @param $ary_orders 订单
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-12-24
     */
    public function getVprivInfo($ary_orders){
        $array_subcompany = M('subcompany')->where(array('is_open'=>0))->order(array('s_sort'=>'asc'))->select();
        //订单原价
        $orders_price = sprintf("%0.2f",$ary_orders['o_all_price'] + $ary_orders['all_orders_promotion_price']);
        
        //满减优惠金额
        $o_promotion_price = $ary_orders['all_orders_promotion_price'];
        $f = array();
        $o_items = $this->getOrdersData(array('fx_orders.o_id'=>'201312241105373359'),array('fx_orders.o_id','fx_orders.o_all_price','fx_orders_items.oi_id','fx_orders_items.g_id','fx_orders_items.pdt_id','fx_orders_items.oi_price','fx_orders_items.oi_cost_price','fx_orders_items.pdt_sale_price','fx_orders_items.oi_nums'));
        
        foreach ($o_items as $key=>$val){
            $f[$val['pdt_id']]['s_id'] = D('Subcompany')->getCompanyByGid($val['g_id']);
        }
        $array = array();
        foreach ($array_subcompany as $sb_k=>$sb_v){
            $array['sid'.$sb_v['s_id']] = 0;
            foreach ($f as $fk=>$fv){
                if($fv['s_id'] != '' && $sb_v['s_id'] == $fv['s_id']){
                    //获取商品原价
                    $sale_price = $this->getOrdersSalePrice($o_items,$fk);
                    $array['sid'.$sb_v['s_id']] += sprintf("%0.2f",$sale_price/$orders_price*$o_promotion_price);
                }
            }
        }
        $return = '';
        foreach ($array as $k){
            $return .= $k.';';
        }
        $return = rtrim($return,';');
    }
    
    private function getOrdersSalePrice($orders,$pdt_id){
        foreach($orders as $key=>$val){
            if($val['pdt_id'] == $pdt_id){
                return $val['pdt_sale_price'];
            }
        }
        return 0.00;
    }
	
	  /**
     * 获取订单数据
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部
     * @author zhangjiasuo
     * @return array $ary_result 订单信息
     * @date 2013-07-08
     */
    public function getOrdersList($ary_where = array(), $ary_field = '',$group='') {
        $ary_result = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
                      ->join('fx_members ON fx_members.m_id = fx_orders.m_id')
                      ->field($ary_field)->where($ary_where)->group($group)->select();
        return $ary_result;
    }
    
    
	/**
	 * 保存收货地址id和优惠券id,存入session
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @param string $key 参数标识  如 'address'  'coupon'
	 * @param int $val 
	 * @date 2014-4-8
	*/
	public function setOrderConfirmInfo($key, $val) {
		$ary_address_info = array();
        $data = $this->getOrderConfirmInfo();
		$ary_address = D("ReceiveAddress")->getAddressByraid($val);
        $data[$key] = $ary_address;
        session('OrderConfirmInfo', $data);
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
	 * 订单确认收货
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-6-20
	 * return boolean
	*/
    public function orderConfirm($o_id) {
        $ary_orders_status = D("ApiOrders")->getOrdersStatus($o_id);
        if ($ary_orders_status['deliver_status'] != '已发货') {
			return array('status'=>0,'message'=>"订单" . $o_id . "未发货");
        }
        $ary_order = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $o_id))->find();
        if (!empty($ary_order) && is_array($ary_order)) {
            M('', '', 'DB_CUSTOM')->startTrans();
            $ary_result = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $ary_order ['o_id']
                    ))->data(array(
                        'o_status' => '5',
						'o_update_time'=>date('Y-m-d H:i:s')
                    ))->save();
            if (FALSE !== $ary_result) {
                /** ** 处理订单积分****start****By Joe***** */
                $array_point_config = D('PointConfig')->getConfigs();
                if ($array_point_config ['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '1') {
                    // 确认收货后处理赠送积分
                    if ($ary_order['o_reward_point'] > 0) {
                        $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_order['o_reward_point'], $ary_order ['m_id'],$ary_order['o_id']);
                        if (!$ary_reward_result ['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
							return array('status'=>0,'message'=>$ary_reward_result['message']);
                        }
                    }
                    // 确认收货后处理消费积分
                    if ($ary_order ['o_freeze_point'] > 0) {
                        $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_order['o_reward_point'], $ary_order ['m_id']);
                        if (!$ary_freeze_result['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
							return array('status'=>0,'message'=>$ary_freeze_result['message']);
                        }
                    }
                }
                /**** 处理订单积分****end********* */

                /** * 订单发货后获取订单优惠券**star by Joe* */
                //获取优惠券节点
                $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
                $where = array('fx_orders.o_id' => $o_id);
                $ary_field = array('fx_orders.o_pay', 'fx_orders.o_all_price', 'fx_orders.coupon_sn', 'fx_orders_items.pdt_id', 'fx_orders_items.oi_nums', 'fx_orders_items.oi_type');
                $ary_orders = $this->getOrdersData($where, $ary_field);
                // 本次消费金额=支付单最后一次消费记录
                $payment_serial = M('payment_serial')->where(array('o_id' => $o_id))->order('ps_create_time desc')->select();
                $payment_price = $payment_serial[0]['ps_money'];
                $all_price = $ary_orders[0]['o_all_price'];
                $coupon_sn = $ary_orders[0]['coupon_sn'];
                if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '2') {
                    D('Coupon')->setPoinGetCoupon($ary_orders, $ary_order['m_id']);
                }
                /** * 订单发货后获取订单优惠券****end********* */
                M('', '', 'DB_CUSTOM')->commit();
				return array('status'=>1,'message'=>'确认收货成功,请对商品进行评价');
            } else {
                M('', '', 'DB_CUSTOM')->rollback();
				return array('status'=>0,'message'=>" 确认收货失败，请重试...");
            }
        } else {
			return array('status'=>0,'message'=>"订单 " . $o_id . " 不存在");
        }
    }

    /**
     * 获取本次结算可用优惠券列表
     * @author Nick
     * @date 2015-09-11
     * @param $array_params
     * $array_params = array(
	 *  'm_id' => '',   //必填，会员ID
	 *  'cart_items' => 'g_id,pdt_id,num;g_id,pdt_id,num',    //必填，购物车货品IDs
	 * )
	 *
     * @return array
     */
    public function getCheckoutAvailableCoupons($array_params,$limit='') {
        $m_id = $array_params["m_id"];
        $ary_pdt_id = $array_params["ary_pdt_id"];
        $cartModel = D('Cart');
        $str_pdt_ids = implode(',', $ary_pdt_id);
        //获取购物车数据
        $ary_cart = $cartModel->getCartItems($str_pdt_ids, $m_id, true);
        //获取购物车商品详情
        $cart_data = $cartModel->getProductInfo($ary_cart, $m_id);
        $gIds = array();
        foreach($cart_data as $v){
            $gIds[] = $v['g_id'];
        }
        $ary_coupon = array();
        //根据g_id(商品id)获取gg_id(商品组id)
        $ary_ggids = D("RelatedGoodsGroup")->getGoodsGroupByGid($gIds);
        //没有商品组，就不会有可用优惠券dump($tmp_ary_coupon2);die();
		$ary_where = array(
			'c_user_id' => $m_id,
			'c_is_use' => 0,
			'c_end_time' => array('EGT', date('Y-m-d H:i:s')),
		);		
        if(!empty($ary_ggids)){
            $ary_ggid = array();
            foreach($ary_ggids as $ggv){
                 $ary_ggid[] = $ggv['gg_id'];
            }
            $ary_where['gg_id'] = array("IN", $ary_ggid);
            //根据gg_id(商品组id)获取c_id(优惠券id)
            $tmp_ary_coupon1 = M('coupon c', C('DB_PREFIX'), 'DB_CUSTOM')
                ->join(C("DB_PREFIX"). 'related_coupon_goods_group as rcgp on rcgp.c_id = c.c_id')
                ->where($ary_where)->limit($limit)->group('c.c_id')->select();
		}
		//获取无需分组可以使用的优惠券
		unset($ary_where['gg_id']);
		$ary_where['_string'] = "ifnull(gg_id,'') = ''";
		$tmp_ary_coupon2 = M('coupon c', C('DB_PREFIX'), 'DB_CUSTOM')
		->join(C("DB_PREFIX"). 'related_coupon_goods_group as rcgp on rcgp.c_id = c.c_id')
		->where($ary_where)->limit($limit)->group('c.c_id')->select();
		
		if(empty($tmp_ary_coupon1)){
			$ary_coupon = $tmp_ary_coupon2;
		}else{
			if(empty($tmp_ary_coupon2)){
				$ary_coupon = $tmp_ary_coupon1;
			}else{
				$ary_coupon = array_merge($tmp_ary_coupon1,$tmp_ary_coupon2);
			}
		}
		foreach ($ary_coupon as $key => $coupon) {
			//折扣券，0.88 -> 8.8折
			if($coupon['c_type']==1){
				$ary_coupon[$key]['c_money'] =$coupon['c_money']*10;
			}
		}
        return $ary_coupon;
    }

    /**
     * 获取订单运费
     * @author Nick
     * @date 2015-09-11
     * @param $logistic_info
     * @param $all_price
     * @param $ary_cart
     * @param $m_id
     * @param $type
     *
     * @return float $logistic_price
     */
    public function getOrderLogisticPrice($logistic_info, $all_price, $ary_cart, $m_id, $type) {

        $lt_expressions = json_decode( $logistic_info['lt_expressions'], true );
        //订单金额超过包邮额度，运费为0
        if ( ! empty( $lt_expressions['logistics_configure'] ) &&
             $all_price >= $lt_expressions['logistics_configure'] ) {
            $logistic_price = 0;
        } else {
            //购物车商品都参与满包邮
            if ( empty( $ary_cart ) ) {
                $ary_cart = array( 'pdt_id' => 'MBAOYOU' );
            }
            $logistic_price = D('Logistic')->getLogisticPrice($logistic_info['lt_id'], $ary_cart, $m_id, $type);
        }

        return $logistic_price;
    }

    /**
     * 获取运费
     * @author Nick
     * @date 2015-09-11
     * @param $lt_id
     * @param $all_price
     * @param $pro_datas
     * @param $ary_cart
     * @param $m_id
     * @param $oi_type
     *
     * @return mixed
     */
    public function getLogisticPrice($lt_id, $all_price, $pro_datas, $ary_cart, $m_id, $oi_type) {
        //是否开启门店提货
        $zt_info =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $is_zt = $zt_info['IS_ZT']['sc_value'];
        if($is_zt && empty($lt_id)){
            $ary_res ['errMsg'] = '';
            $ary_res ['success'] = 1;
            $ary_res ['logistic_price'] = 0;
            return $ary_res;
        }

        //计算运费前，去除购物车中包邮商品
        foreach ( $pro_datas as $pro_data ) {
            if ( $pro_data ['pmn_class'] == 'MBAOYOU' ) {
                foreach ( $pro_data['products'] as $proDatK => $proDatV ) {
                    unset( $ary_cart[ $proDatK ] );
                }
            }
        }

        //获取配送方式
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array(
            'fx_logistic_type.lt_id' => (int)$lt_id,
            array('fx_logistic_corp.lc_cash_on_delivery', 'fx_logistic_type.lt_expressions', 'fx_logistic_type.lt_id')
        ));
        if(!empty($logistic_info)) {
            $logistic_price = $this->getOrderLogisticPrice($logistic_info, $all_price, $ary_cart, $m_id, $oi_type);
            $ary_res ['errMsg'] = '';
            $ary_res ['success'] = 1;
            $ary_res ['logistic_price'] = $logistic_price;
        }else {
            $ary_res ['errMsg'] = '请先选择配送方式';
            $ary_res ['success'] = 0;
        }

        return $ary_res;

    }

    /**
     * 使用优惠券支付
     * @author Nick
     * @date 2015-09-11
     * @param $array_params
     * @param $ary_data
     * @param $all_price
     * @param $pro_datas
     *
     * @return mixed
     */
    public function useCoupon($array_params, $ary_data, $all_price, $pro_datas) {

        $str_csn = isset($array_params['csn']) ? $array_params['csn'] : '';
        //0:使用优惠券
        $type = (int)$array_params['type'];
        $ary_res = array('success'=>1, 'coupon_price'=>0);
        //使用优惠券
        if (!empty($str_csn)) {
            $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data'], $array_params['m_id']);
            writeLog('useCoupon:  '.var_export($ary_coupon, true), 'order_add_api_'. date('Y_m_d') .'.log');


            $date = date('Y-m-d H:i:s');
            if($ary_coupon['status'] == 'error'){
                if($type == 0){
                    $ary_res ['errMsg'] = $ary_coupon['msg'];
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['coupon_price'] = 0;
                }
            }
            else {
                foreach ($ary_coupon['msg'] as $coupon){
                    if ($coupon ['c_condition_money'] > 0 && $all_price < $coupon ['c_condition_money']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = '编号'.$coupon['c_sn'].'优惠券不满足使用条件';
                            $ary_res ['success'] = 0;
                            break;
                        }else{
                            $ary_res ['coupon_price'] = 0;
                        }
                    }
                    elseif ($coupon ['c_is_use'] == 1 || $coupon ['c_used_id'] != 0) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号".$coupon['c_sn']."被使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = 0;
                        }
                    }
                    elseif ($coupon ['c_start_time'] > $date) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号".$coupon['c_sn']."不能使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    }
                    elseif ($date > $coupon ['c_end_time']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号".$coupon['c_sn']."活动已经结束";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    }
                    else {
                        if($type == 0){
                            $ary_res ['sucMsg'] = '可以使用';
                            $ary_res ['success'] = 1;
                        }
                        //折扣券
                        if($coupon['c_type'] == '1'){
                            //计算参与优惠券使用的商品
                            if($coupon['gids'] == 'All'){
                                $ary_res ['coupon_price'] +=sprintf('%.2f',(1 - $coupon ['c_money'])*$all_price);
                            }else{
                                //计算可以使用优惠券总金额
								
                                /**$coupon_all_price = 0;
                                foreach ($pro_datas as $keys => $vals) {
                                    if($keys == 0){
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = D('Cart')->getProductInfo(array($key => $val), $array_params['m_id']);
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $coupon_all_price += $provals['pdt_price']*$provals['pdt_nums'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $coupon_all_price += $val['pdt_price']*$val['pdt_nums'];     //商品总价
                                            }
                                        }
                                    }else{
                                        $other_total_price = 0;
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = D('Cart')->getProductInfo(array($key => $val), $array_params['m_id']);
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $other_total_price += $provals['pdt_price']*$provals['pdt_nums'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $other_total_price += $val['pdt_price']*$val['pdt_nums'];     //商品总价
                                            }
                                        }
                                        if($other_total_price > $vals['goods_total_price']){
                                            $coupon_all_price += $vals['goods_total_price'];
                                        }else{
                                            $coupon_all_price += $other_total_price;
                                        }
                                    }
                                }**/
								//计算可以使用优惠券总金额
								$coupon_all_price = 0;
								$tmp_coupon_all_price = 0;
								$tmp_goods_total_price = 0;
								//参与优惠券的数量
								foreach ($pro_datas as $keys => $vals) {
									//是否可以使用优惠券
									$is_exsit_coupon = 0;
									foreach ($vals['products'] as $key => $val) {
										$arr_products =  D('Cart')->getProductInfo(array($key => $val), $array_params['m_id']);
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
                                /*if($coupon_all_price < $coupon ['c_condition_money']) {
                                    if($type == 0){
                                        $ary_res ['errMsg'] = '编号'.$coupon['c_sn'].'优惠券不满足使用条件';
                                        $ary_res ['success'] = 0;
                                        break;
                                    }else{
                                        $ary_res ['coupon_price'] = 0;
                                    }
                                }*/
                                $ary_res ['coupon_price'] +=sprintf('%.2f',(1- $coupon ['c_money'])*$coupon_all_price);
                            }
                        }
                        //现金券
                        else{
                            $ary_res ['coupon_price'] += $coupon ['c_money'];
                        }

                        $ary_res['coupon'] = $coupon;
						$ary_res['ary_coupon'] = $ary_coupon;
						
                    }
                }
            }
        }
        else{
            //点击了使用优惠券按钮，提示报错
            if($type == 0){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['coupon_price'] = 0;
            }
            else{
                $ary_res ['coupon_price'] = 0;
                $ary_res ['success'] = 1;
            }
        }
        return $ary_res;
    }

    /**
     * 使用红包支付
     * @author Nick
     * @date 2015-09-11
     * @param $array_params
     * @param $all_price
     * @param $m_id
     *
     * @return mixed
     */
    public function useBonus($array_params, $all_price, $m_id) {
        $bonus = isset($array_params['bonus']) ? (int)$array_params['bonus'] : 0;
        //1:使用红包
        $type = (int)$array_params['type'];
        $ary_res = array('success'=>1);
        //红包支付
        if ($array_params['bonus'] > 0) {
            $arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$m_id))->find();
            if($bonus > $arr_bonus['m_bonus']){
                if($type == 1){
                    $ary_res ['errMsg'] = '红包金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['bonus_price'] = '0';
                }
            }elseif($all_price < $bonus) {
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
            //点击了使用红包按钮，提示报错
            if($type == 1){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['bonus_price'] = 0;
            }
            else{
                $ary_res ['bonus_price'] = 0;
                $ary_res ['success'] = 1;
            }
        }

        return $ary_res;
    }

    /**
     * 使用储值卡支付
     * @author Nick
     * @date 2015-09-11
     * @param $array_params
     * @param $all_price
     * @param $m_id
     *
     * @return mixed
     */
    public function usePrepaidCard ($array_params, $all_price, $m_id) {
        $cards = isset($array_params['cards']) ? (int)$array_params['cards'] : 0;
        //2:使用储值卡
        $type = (int)$array_params['type'];
        $ary_res = array('success'=>1);
        //储值卡支付
        if ($cards > 0) {
            $arr_cards = M('Members')->field("m_cards")->where(array('m_id'=>$m_id))->find();
            if($cards > $arr_cards['m_cards']){
                if($type == 2){
                    $ary_res ['errMsg'] = '储值卡金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['cards_price'] = 0;
                }
            }elseif($all_price < $cards) {
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
        }
        else{
            //点击了使用储值卡按钮，提示报错
            if($type == 2){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['cards_price'] = 0;
            }
            else{
                $ary_res ['cards_price'] = 0;
                $ary_res ['success'] = 1;
            }
        }

        return $ary_res;
    }

    /**
     * 使用积分支付
     * @author Nick
     * @date 2015-09-11
     * @param $array_params
     * @param $all_price
     * @param $m_id
     *
     * @return mixed
     */
    public function usePoint($array_params, $all_price, $m_id) {
        $point = isset($array_params['point']) ? (int)$array_params['point'] : 0;
        //4:使用积分
        $type = (int)$array_params['type'];
        $ary_res = array('success'=>1);
        //积分支付
		/*必买特有*/
		$pointCfg = D('PointConfig');
		$point_data = $pointCfg->getConfigs();
		if($point_data['is_low_consumed'] == 1 && $point < $point_data['low_consumed_points'] ){//开启积分抵扣限制
			$ary_res ['sucMsg'] = '';
			$ary_res ['success'] = 1;
			$ary_res ['points'] = 0;
			$ary_res ['point_price'] = 0;
			return $ary_res;
		}
		
        if ($point > 0) {
            $pointCfg = D('PointConfig');
            // 计算订单可以使用的积分
            $is_use_point = $pointCfg->getIsUsePoint($all_price, $m_id);
            writeLog('usePoint:  '.var_export($is_use_point, true), 'order_add_api_'. date('Y_m_d') .'.log');
            if($point <= $is_use_point){
                $ary_data = $pointCfg->getConfigs();
                $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
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
        }
        else{
            //点击了使用积分按钮，则提示报错
            if($type == 4){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
            }
            else{
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
                $ary_res ['success'] = 1;
            }
        }

        return $ary_res;
    }

    /**
     * 普通商品金额计算
     * @param $array_params
     * @return mixed
     */
    private function getGeneralOrderPrice($array_params) {
        $lt_id   = (int)$array_params["lt_id"];
        $ary_gid = $array_params['gid'];
        $pids    = $array_params['pids'];
        $m_id    = (int)$array_params['m_id'];

        $User_Grade = D('MembersLevel')->getMembersLevels($m_id); //会员等级信息
        $cartModel = D('Cart');
        //获取购物车信息
        $ary_cart = $cartModel->getCartItems($pids, $m_id);
        foreach($ary_cart as $key=>&$item) {
            $item['type'] || $item['type'] = 0;
            $item['item_type'] = $item['type'] ;
        }

        //跨境贸易
        $is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
            $total_tax_rate=0;
            $tax_rate_item_num=0;
            $order_item_nums=0;
        }
        //获取购物车的促销详情
        $pro_datas = D('Promotion')->calShopCartPro($m_id, $ary_cart);
		$subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);		
        //处理通过促销获取的优惠信息
        $tmp_pro_datas = $cartModel->handleProdatas($pro_datas,$ary_cart);
        //获取赠品信息
        $cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
        //跨境贸易
        $total_tax_rate = $tmp_pro_datas['total_tax_rate'];
//        dump($pro_datas);die;
        $pro_data = reset($pro_datas);
		//获取订单总金额
		$ary_price_data = $cartModel->getPriceData($tmp_pro_datas,$subtotal,$cart_gifts_data,1);
        //赠品数组
        $gifts_cart = array();
        $i = $_total_discount_price = $promotion_total_price = $promotion_price =0;

        $authorizeLineModel = D('AuthorizeLine');
        $int_promotion_count = count($pro_data['products']);
        foreach ($pro_data['products'] as $key => $val) {
            //购物车详情（重复查询，后期可以整合一下）
            $arr_products = $cartModel->getProductInfo(array($key => $val));
            $arr_product = reset($arr_products);
            //购物车优惠优惠金额放到订单明细里拆分
            if($key != 0 && !empty($val['pro_goods_discount'])){
                //为避免不能整除带来的误差，最后一个商品的优惠金额=订单总优惠金额-前面所有商品优惠总额
                if($int_promotion_count == $i+1){
                    $pro_data['products'][$key]['promotion_price'] = $pro_data['goods_all_discount']-$_total_discount_price;
                }else{
                    $pro_data['products'][$key]['promotion_price'] = sprintf("%.2f", ($val['pdt_price']/$val['pdt_sale_price'])*$val['pro_goods_discount']);
                    $_total_discount_price += $pro_data['products'][$key]['promotion_price'];
                }
            }
            //跨境贸易
            if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
                if(isset($arr_product['g_tax_rate']) && !empty($arr_product['g_tax_rate'])){
                    $tax_rate_item_num += $val['num'];
                    $order_item_nums += $val['num'];
                    if(isset($pro_data['goods_total_price'])){
						if($pro_data['pmn_name']!=''){
							$total_tax_rate += ($arr_product['pdt_momery']-$pro_data ['pro_goods_discount'])*$arr_product['g_tax_rate'];
						}else{
							$total_tax_rate += ($arr_product['pdt_momery']-$pro_data ['products'][$key]['promotion_price'])*$arr_product['g_tax_rate'];
						}
					}else{
                        $total_tax_rate += $arr_product['pdt_momery']*$arr_product['g_tax_rate'];
                    }
                }else{
                    $order_item_nums += $arr_product['pdt_nums'];
                }
            }

            $i++;
        }
        //dump($pro_datas);die;
        //赠品数组
        if (!empty($cart_gifts_data)) {
            foreach ($cart_gifts_data as $gifts) {
                //随机取一个pdt_id
                $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                $gifts_cart[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2);
            }
        }

        $pro_datas[] = $pro_data;
		$ary_res = $ary_price_data;
		$ary_res['status'] = true;
        //跨境贸易税额起征点
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
            $foreign_info=D('SysConfig')->getForeignOrderCfg();
            if(!empty($foreign_info['IS_AUTO_TAX_THRESHOLD']) && $foreign_info['TAX_THRESHOLD'] >= $total_tax_rate){
                $total_tax_rate=0;
            }
            $all_prices = $ary_price_data['total_price']+$total_tax_rate; //税额
            $ary_res['o_tax_rate'] = $total_tax_rate;
             if($foreign_info['IS_AUTO_LIMIT_ORDER_AMOUNT']==1 && $foreign_info['LIMIT_ORDER_AMOUNT']> 0 ){
				if($all_prices > $foreign_info['LIMIT_ORDER_AMOUNT'] && $total_tax_rate > 0 && $order_item_nums >1 ){
					$ary_res['success'] = false;
					$mgs='订单总价不可以超过'.$foreign_info['LIMIT_ORDER_AMOUNT'].'元，请重新选择商品';
                    $ary_res['errMsg'] = $mgs;
                    return $ary_res;
                }
            } 
        }

        //会员等级返点
        $ary_res['ml_rebate'] = $User_Grade['ml_rebate'];
        foreach ($pro_datas as $pd) {
            if (!empty($pd ['pmn_class'])) {//订单只要包含一个促销商品，整个订单为促销，不返点
                $ary_res['ml_rebate'] = 0;
            }
        }
		$ary_res['goods_all_point'] = $subtotal['goods_all_point'] ? $subtotal['goods_all_point'] : 0;
//        $ary_res['ary_cart'] = $ary_cart;
        //除去包邮商品和赠品
        $ary_res['ary_cart'] = $ary_cart;

        $pro_datas['gifts'] = $cart_gifts_data;
        $ary_res['pro_datas'] = $pro_datas;
        //促销优惠金额
        $ary_res['o_discount'] = $ary_price_data['pre_price'];
        //订单商品总价（销售价格带促销）
        $ary_res ['o_goods_all_price'] = sprintf("%0.2f", $ary_price_data['all_pdt_price']);
        //商品原总金额
        $ary_res ['o_goods_all_saleprice'] = sprintf("%0.2f", $subtotal['goods_total_sale_price']);
        //商品现售总金额
        //$ary_res['all_price'] += $logistic_price ;
        return $ary_res;

    }

    /**
     * 积分优惠券积分抵扣等
     * @param $array_params
     * @param $ary_data
     * @param $ary_res
     * @param $pro_datas
     * @return mixed
     */
    private function  dicountAct($array_params,$ary_data, $ary_res, $pro_datas) {
        $lt_id   = (int)$array_params["lt_id"];
        $m_id    = (int)$array_params['m_id'];
        $all_price = $ary_res['all_price'];
        $ary_cart = $ary_res['ary_cart'];
        $order_type = 0;
//        dump($pro_datas);
//        dump($array_params);die;
        //使用优惠券
        $ary_coupon_res = $this->useCoupon($array_params, $ary_data, $all_price, $pro_datas);
        if($ary_coupon_res['success'] == 0) {
//            $ary_logistic_res = $this->getLogisticPrice($lt_id, $all_price, $pro_datas, $ary_cart, $m_id, $order_type);
//            if($ary_logistic_res['success'] == 0) {
//                $ary_logistic_res['logistic_price'] = 0;
//            }
//            $ary_coupon_res['all_price'] = sprintf("%0.2f",$all_price +$ary_logistic_res['logistic_price']);//失败时页面显示金额显示NAN
            $ary_coupon_res['all_price'] = sprintf("%0.2f",$all_price );//失败时页面显示金额显示NAN
            return $ary_coupon_res;
        }


        $ary_res['coupon_price'] = $ary_coupon_res['coupon_price'];
        //判断优惠券是否使用
        if(!empty($ary_res['coupon_price'])){
            $ary_res['ary_coupon'] = $ary_coupon_res['ary_coupon'];
        }
        //使用红包
        $ary_bouns_res = $this->useBonus($array_params, $all_price, $m_id);
        if($ary_bouns_res['success'] == 0) {
            return $ary_bouns_res;
        }
        $ary_res['bonus_price'] = $ary_bouns_res['bonus_price'];
        //储值卡支付
        $ary_card_res = $this->usePrepaidCard($array_params, $all_price, $m_id);
        if($ary_card_res['success'] == 0) {
            return $ary_card_res;
        }
        $ary_res['cards_price'] = $ary_card_res['cards_price'];
        //使用积分支付
        $ary_point_res = $this->usePoint($array_params, $all_price, $m_id);
        if($ary_point_res['success'] == 0) {
//            $ary_logistic_res = $this->getLogisticPrice($lt_id, $all_price, $pro_datas, $ary_cart, $m_id, $order_type);
//            if($ary_logistic_res['success'] == 0) {
//                $ary_logistic_res['logistic_price'] = 0;
//            }
//            $ary_point_res['all_price'] = sprintf("%0.2f",$all_price +$ary_logistic_res['logistic_price']);//失败时页面显示金额显示NAN
            $ary_point_res['all_price'] = sprintf("%0.2f",$all_price);//失败时页面显示金额显示NAN
            return $ary_point_res;
        }
        $ary_res['point_price'] = $ary_point_res['point_price'];
        $ary_res['points'] = $ary_point_res['points'];
        //$ary_res ['promotion_price'] = sprintf("%0.2f", $ary_res['o_discount'] );

        $result_price = sprintf("%0.2f", $all_price - $ary_res['coupon_price'] - $ary_res['bonus_price'] - $ary_res['cards_price'] - $ary_res['jlb_price'] - $ary_res['point_price']);
        if($result_price<0){
            $ary_res ['errMsg'] = '使用抵扣总金额超过了商品总金额';
            $ary_res ['success'] = 0;
            $ary_res ['points'] = 0;
            $ary_res ['point_price'] = 0;
            $ary_res ['jlb_price'] = 0;
            $ary_res ['cards_price'] = 0;
            $ary_res ['bonus_price'] = 0;
            $ary_res ['coupon_price'] = '0';
            $ary_res['all_price'] = sprintf("%0.2f", $ary_res['all_price']);
            $ary_res ['is_use_point'] = D('PointConfig')->getIsUsePoint($ary_res ['all_price'],$m_id);
            return $ary_res;
        }else{
            $ary_res['all_price'] = sprintf("%0.2f", $result_price);
        }

        // 获得赠送积分
        //赠品积分
        $gifts_point_reward = 0;
        //商品积分
        $gifts_point_goods_price = 0;
		$total_price = 0;
        foreach ($ary_data['ary_product_data'] as $pro) {
			if(!empty($pro[0]['type'])){
				foreach($pro as $sub_pro){
					$total_price +=$sub_pro['pdt_momery'];
				}
			}else{
				$total_price +=$pro['pdt_momery'];
			}
            if ($pro['gifts_point'] > 0 && isset($pro['gifts_point']) && isset($pro['is_exchange'])) {
                $gifts_point_reward += $pro['gifts_point'] * $pro['pdt_nums'];
               // $gifts_point_goods_price += $pro['f_price'] * $pro['pdt_nums'];
				$gifts_point_goods_price +=$pro['pdt_momery'];
            }
        }
        $other_all_price = $total_price - $gifts_point_goods_price;

        $other_point_reward = D('PointConfig')->getrRewardPoint($other_all_price);
        $other_point_reward = ceil(($ary_res ['all_price']/$total_price)*$other_point_reward);
        $ary_res ['reward_point'] = $gifts_point_reward + $other_point_reward;
		//dump($ary_res);die();
        $ary_res['points'] = $ary_res['goods_all_point'] + $ary_res['points'];
        $ary_res ['is_use_point'] = D('PointConfig')->getIsUsePoint($ary_res ['all_price'],$m_id)+intval($ary_res['points']);

        return $ary_res;
    }
    /**
     * 计算订单所有金额
     * @param array $array_params
     *
     * @return array
     */
    public function getAllOrderPrice($array_params=array()) {
        $lt_id   = (int)$array_params["lt_id"];
        $ary_gid = $array_params['gid'];
        $pids    = $array_params['pids'];
        $ary_cart    = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
        $m_id    = (int)$array_params['m_id'];
        //是否包邮
        $free_shipping = 0;
        $ary_res = $pro_datas = array();


        // 秒杀商品
		if ($ary_item['item_type'] == 7) {
            $order_type = $ary_item['item_type'];
            $ary_spike = D('Spike')->getDetailWithPrice($ary_cart);
            //商品销售总价
            $ary_res ['o_goods_all_saleprice'] = sprintf("%0.3f", $ary_spike['pdt_all_sale_price']);
            $ary_res ['o_discount'] = sprintf("%0.3f", $ary_spike['pdt_all_sale_price'] - $ary_spike['sp_subtotal_price']);
            $ary_res['o_goods_all_price'] = $ary_spike['sp_subtotal_price'];
            $ary_res['all_price'] = $ary_spike['sp_subtotal_price'];
            $ary_res['discount_price'] = $ary_res ['o_discount'];
		}
        // 团购商品
		else if($ary_item['item_type'] == 5){
            $order_type = $ary_item['item_type'];
            $ary_bulk = D('Groupbuy')->getDetailWithPrice($ary_cart);
            //商品销售总价
            $ary_res ['o_goods_all_saleprice'] = sprintf("%0.3f", $ary_bulk['pdt_all_sale_price']);
            $ary_res ['o_discount'] = sprintf("%0.3f", $ary_bulk ['pdt_all_sale_price'] - $ary_bulk['gp_subtotal_price']);
            $ary_res ['o_goods_all_price'] = sprintf("%0.3f", $ary_bulk['gp_subtotal_price']);
            $ary_res['all_price'] = $ary_bulk['gp_subtotal_price'];
            $ary_res['discount_price'] = $ary_res['o_discount'];

            if ($ary_bulk['gp_is_baoyou']) {
                $free_shipping = 1;
            }
		}
        // 预售商品
		else if($ary_item['item_type'] == 8){
            $order_type = $ary_item['item_type'];

            $ary_presale = D('Presale')->getDetailWithPrice($ary_cart);
            //商品销售总价
            $ary_res ['o_goods_all_saleprice'] = sprintf("%0.3f", $ary_presale['pdt_all_sale_price']);
            $ary_res ['o_discount'] = sprintf("%0.3f", $ary_presale ['pdt_all_sale_price'] - $ary_presale['p_subtotal_price']);
            $ary_res ['o_goods_all_price'] = sprintf("%0.3f", $ary_presale['p_subtotal_price']);
            $ary_res['all_price'] = $ary_presale['p_subtotal_price'];
            $ary_res['discount_price'] = $ary_res['o_discount'];
		}
        //积分兑换
        else if($ary_item['item_type'] == 11){
            $order_type = $ary_item['item_type'];

            $ary_integral = D('Integral')->getDetailWithPrice($ary_cart);
            //商品销售总价
            $ary_res ['o_goods_all_saleprice'] = sprintf("%0.3f", $ary_integral['money_need_to_pay']);
            $ary_res ['o_discount'] = sprintf("%0.3f", $ary_integral ['pdt_all_sale_price'] - $ary_integral['money_need_to_pay']);
            $ary_res ['o_goods_all_price'] = sprintf("%0.3f", $ary_integral['money_need_to_pay']);
            $ary_res['points'] = $ary_integral ['integral_need'];
            $ary_res['all_price'] = $ary_integral ['money_need_to_pay'];
            $ary_res['discount_price'] = $ary_res['o_discount'];

        }
        //普通商品
        else{
            $ary_result = $this->getGeneralOrderPrice($array_params);
			if($ary_result['status'] == false){
				return $ary_result;
			}
            $pro_datas = $ary_result['pro_datas'];
            $ary_cart = $ary_result['ary_cart'];
            if(!empty($pro_datas['gifts']))
                $ary_cart['gifts'] = $pro_datas['gifts'];
            $cartModel = D('Cart');
            //获取购物详情
            $ary_data ['ary_product_data'] = $cartModel->getProductInfo($ary_cart);

            $ary_res = $this->dicountAct($array_params, $ary_data, $ary_result, $pro_datas);

        }
		//如果非普通商品下单没有获得积分
//        dump($ary_res);die;
		if(!empty($ary_item['item_type'])){
			if(!$ary_res['reward_point']){
				$point_reward = D('PointConfig')->getrRewardPoint($ary_res['all_price']);
				if($point_reward){
					$ary_res['reward_point'] = $point_reward;
				}
			}
		}
//        dump($ary_cart);die;
        if($free_shipping == 0) {
            $ary_logistic_res = $this->getLogisticPrice($lt_id, $ary_res['all_price'], $pro_datas, $ary_cart, $m_id, $order_type);
            if ($ary_logistic_res['success'] == 0) {
                $ary_logistic_res['all_price'] = $ary_res['all_price'];//失败时页面显示金额显示NAN
                return $ary_logistic_res;
            }
            $ary_res['logistic_price'] = $ary_logistic_res['logistic_price'];
        }else{
            $ary_res['logistic_price'] = 0;
        }
		$ary_res['all_price'] = sprintf("%0.2f",$ary_res['all_price']+$ary_res['logistic_price']);
        //判断如果有税收费加上
        if($ary_result['o_tax_rate'] > 0){
            $ary_res['o_tax_rate'] = $ary_result['o_tax_rate'];
            $ary_res['all_price'] += $ary_result['o_tax_rate'];
        }
        if(!isset($ary_res['success']) || $ary_res['success'] != 0 ){
            $ary_res['success'] = 1;
        }
//		$ary_res['success'] = 1;
        return $ary_res;
    }

    /**
     * 获取订单商品金额
     * @param $ary_items
     */
    public function getDetailWithPrice($ary_items, $m_id) {

        $product_subtotal_price = 0;
        $proPrice = new ProPrice();
        foreach($ary_items as $key=>$order_item) {
            $item_type = $order_item['item_type'];
            $item_num  = $order_item['num'];
            $pdt_id    = $order_item['pdt_id'];
            $item_type || $item_type = 0;
            switch($item_type) {
                //积分商品
                case '1':
//                    break;
                //赠品
                case '2':
//                    break;
                //组合商品
                case '3':
//                    break;
                //自由推荐
                case '4':
//                    break;
                //自由搭配
                case '6':
//                    break;
                //普通商品
                default:
                    $ary_product_last_price = $proPrice->getPriceInfo($pdt_id, $m_id, $item_type);
                    $product_last_price = $ary_product_last_price['pdt_price'];
                    break;
            }
            $single_product_subtotal_price = $product_last_price*$item_num;
            $ary_items[$key]['single_product_subtotal_price'] = $single_product_subtotal_price;
            $product_subtotal_price += $single_product_subtotal_price;
        }
        $ary_items['product_subtotal_price'] = $product_subtotal_price;
        return $ary_items;
    }
	
	/**
     * 订单自动完结
     * @author Piupiu <Piupiu@126.com>
     * @date 2015-11-14
     */
    public function FinishOrderstatus($o_id){
        if(empty($o_id)){
			return array('status'=>false,'message'=>"参数有误，请重试");
        }
        $ary_order = $this->where(array('o_id'=>$o_id))->find();
        if($ary_order['o_pay_status'] != '1'){
			return array('status'=>false,'message'=>'订单'.$o_id.'还有未支付或部分未支付金额');
        }
		if($ary_order['o_status'] != '5'){
			return array('status'=>false,'message'=>'订单'.$o_id.'请先确认收货');
		}
        $ary_refund_type = $this->getOrdersStatus($o_id);
        if($ary_refund_type['deliver_status'] == '未发货'){
			return array('status'=>false,'message'=>'订单'.$o_id.$ary_refund_type['deliver_status']);
        }
        $ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$o_id))->order('or_create_time desc')->select();
        if(!empty($ary_afersale) && is_array($ary_afersale)){
            foreach($ary_afersale as $keyaf=>$valaf){
                //退款
                if($valaf['or_refund_type'] == 1){
                    switch($valaf['or_processing_status']){
                        case 0:
							return array('status'=>false,'message'=>'订单'.$o_id.'退款中');
                            break;
                        default:
                            break;
                    }
                }elseif($valaf['or_refund_type'] == 2){         //退货
                    switch($valaf['or_processing_status']){
                        case 0:
							return array('status'=>false,'message'=>'订单'.$o_id.'退货中');
                            break;
                        default:
                            break;
                    }
                }
				elseif($valaf['or_refund_type'] == 3){         //退运费
                    switch($valaf['or_processing_status']){
                        case 0:
							return array('status'=>false,'message'=>'订单'.$o_id.'退运费中');
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        M('', '', 'DB_CUSTOM')->startTrans();
        //更改订单状态
        if(false === $this->where(array('o_id'=>$o_id))->save(array('o_status'=>4))){
			return array('status'=>false,'message'=>'订单'.$o_id.'操作失败，请重试');
            M('', '', 'DB_CUSTOM')->rollback();
        }
        $array_point_config = D('PointConfig')->getConfigs();
        if($array_point_config['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '2'){
            //订单完结后处理赠送积分
            if($ary_order['o_reward_point']>0){
                $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_order['o_reward_point'],$ary_order['m_id'],$ary_order['o_id']);
                if(!$ary_reward_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
					return array('status'=>false,'message'=>$ary_reward_result['message']);
                    exit;
                }
            }
            //订单完结后处理消费积分
            if($ary_order['o_freeze_point'] > 0){
                $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_order['o_freeze_point'],$ary_order['m_id']);
                if(!$ary_freeze_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
					return array('status'=>false,'message'=>$ary_freeze_result['message']);
                    exit;
                }
            }
        }
        
        /*** 订单发货后获取订单优惠券**star by Joe**/
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        $where = array('fx_orders.o_id' => $o_id);
        $ary_field = array('fx_orders.o_pay','fx_orders.m_id','fx_orders.o_all_price','fx_orders.coupon_sn','fx_orders_items.pdt_id','fx_orders_items.oi_nums','fx_orders_items.oi_type');
        $ary_orders = $this->getOrdersData($where,$ary_field);
        // 本次消费金额=支付单最后一次消费记录
        $payment_serial = M('payment_serial')->where(array('o_id'=>$o_id))->order('ps_create_time desc')->select();
        $payment_price = $payment_serial[0]['ps_money'];
        $all_price = $ary_orders[0]['o_all_price'];
        $coupon_sn = $ary_orders[0]['coupon_sn'];
        if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '3') {
            D('Coupon')->setPoinGetCoupon($ary_orders,$ary_order['m_id']);
        }
        /*** 订单发货后获取订单优惠券****end**********/
		//订单完成订单完成触发返利
		$res_payback_res = D('Promotings')->ajaxOrderPakback($ary_order);
		if(!$res_payback_res){
			M('', '', 'DB_CUSTOM')->rollback();
			return array('status'=>false,'message'=>'订单完成订单完成触发返利错误');
			exit;
		}
        M('', '', 'DB_CUSTOM')->commit();
		return array('status'=>true,'message'=>'操作成功！');
    }

    public function GetPurchaseCount($condition) {
        $res=M('orders_purchases')->where($condition)->count();
        return $res;
    }

    public function GetPurchaseBillCount($condition='') {
        $res=M('orders_purchases_bill')->where($condition)->count();
        return $res;
    }

    public function GetPurchaseBillList($condition = array(), $ary_field = '',$order= '',$limit= ''){
        $res_products = M('orders_purchases_bill',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field($ary_field)
                    ->order($order)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }


    public function GetPurchaseList($condition = array(), $ary_field = '',$order= '',$limit= ''){
        $res_products = M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')
                    ->join('fx_goods_products ON fx_goods_products.pdt_id = fx_orders_purchases.pdt_id')
                    ->join('fx_goods ON fx_goods.g_id = fx_orders_purchases.g_id')
                    ->join('fx_goods_info ON fx_goods_info.g_id = fx_orders_purchases.g_id')
                    ->field($ary_field)
                    ->order($order)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }

     public function GetPurchaseBill() {
        $res=M('orders_purchases_bill')->query('select opb_id from fx_orders_purchases_bill order by opb_id desc');
        return $res;
    }   

    public function UpdatePurchase($params,$op_id='',$op_time=''){
        if($op_id){
            $where['op_id'] = $op_id;
        }
        if($op_time){
            $where['op_time'] = $op_time;
        }
        $ary_result = M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($params);
        if($ary_result){
            return true;
        }else{
            return false;
        }
    }

    public function GetDeliveryOne($odb_id){
        $delivery_bill = M('orders_delivery_bill',C('DB_PREFIX'),'DB_CUSTOM')->where(array("odb_id" => $odb_id))->find();
        return $delivery_bill;
    }

    public function GetDeliveryBillCount($condition='') {
        $res=M('orders_delivery_bill')->where($condition)->count();
        return $res;
    }

    public function GetDeliveryBillList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('orders_delivery_bill',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }

    public function GetSortingBillCount($condition='') {
        $res=M('orders_sorting_bill')->where($condition)->count();
        return $res;
    }

    public function GetSortingBillList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('orders_sorting_bill',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }

    public function GetSortingCount($condition='') {
        $res=M('orders_sorting')->where($condition)->count();
        return $res;
    }

    public function GetSortingList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('orders_sorting',C('DB_PREFIX'),'DB_CUSTOM')
                    ->join('fx_positions ON fx_positions.pdt_id = fx_orders_sorting.pdt_id')
                    ->join('fx_goods_info ON fx_goods_info.g_id = fx_orders_sorting.g_id')
                    ->join('fx_goods_products ON fx_goods_products.pdt_id = fx_orders_sorting.pdt_id')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_sorting',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;          
    }

    public function GetStorageBillCount($condition='') {
        $res=M('orders_storage_bill')->where($condition)->count();
        return $res;
    }

    public function GetStorageBillList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('orders_storage_bill',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }

    public function GetStatementsBillCount($condition='') {
        $res=M('orders_statements_bill')->where($condition)->count();
        return $res;
    }

    public function GetStatementsBillList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('orders_statements_bill',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }

    public function GetStatementsCount($condition='') {
        $res=M('orders_purchases')->where($condition)->count();
        return $res;
    }

    public function GetStatementsList($condition = array(), $ary_field = '',$order= '',$limit= ''){
        $res_products = M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')
                    ->join('fx_goods_products ON fx_goods_products.pdt_id = fx_orders_purchases.pdt_id')
                    ->join('fx_goods ON fx_goods.g_id = fx_orders_purchases.g_id')
                    ->join('fx_goods_info ON fx_goods_info.g_id = fx_orders_purchases.g_id')
                    ->field($ary_field)
                    ->order($order)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('orders_purchases',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }
}
