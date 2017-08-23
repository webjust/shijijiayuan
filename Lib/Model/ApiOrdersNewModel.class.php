<?php

/**
 * 订单控制器
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-11-10
 */
Class ApiOrdersNewModel extends GyfxModel{

	private $result;

	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10702', 		// 错误初始码
			'sub_msg' => '订单错误', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
	}

	/**
	 * [获取订单列表]
	 * @param  [type] $params [description]
	 * @example $params = array(
	 *          'm_id' => 19
	 *          'page' => 0
	 *          'pagesize' => 1
	 *          'status' => 0
	 * );
	 * @return [type]         [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-10
	 */
	public function getOrders($params){
		$where = array(
			'm_id' => $params['m_id'],
			);
		// 订单状态
		switch ($params['status']) {
			case 1:	// 取消的订单
				$where['o_status'] = '2';
				break;
			case 2:	// 待支付的订单
				$where['o_pay_status'] = '0';
				$where['o_status'] = '1';
			default:
				break;
		}
		$page_start = $params['page']*$params['pagesize'];
		$ary_orders = D('Orders')->where($where)->order('`o_create_time` DESC')->limit($page_start,$params['pagesize'])->select();
		$field = 'fx_goods_info.g_picture,fx_orders_items.oi_nums,fx_orders_items.oi_ship_status,fx_goods_info.g_id';
		if(is_array($ary_orders) && !empty($ary_orders)){
			foreach($ary_orders as $key=>&$order){
				$data = $this->OrdersGoods($order['o_id'],$field,$params['m_id']);
				//$ary_orders_status = D("Orders")->getOrdersStatus($order['o_id']);
				//$order['deliver_status'] = $ary_orders_status['deliver_status'];
                $order['o_receiver_mobile'] = strpos($order['o_receiver_mobile'],':') ? decrypt($order['o_receiver_mobile']) : '';
                $order['o_receiver_telphone'] = strpos($order['o_receiver_telphone'],':') ? decrypt($order['o_receiver_telphone']) : '';
				$order = array_merge($order,$data);
			}
		}else{
			$ary_orders = array();
		}
		
		$this->result['status'] = true;
    	$this->result['code'] = 10703;
    	$this->result['sub_msg'] = '获取订单列表成功!';
    	$this->result['info'] = $ary_orders;
		return $this->result;
	}

	/**
	 * [获取订单详情]
	 * @param  [array] $params [description]
	 * @example array(
	 *          'm_id' => 用户ID
	 *          'o_id' => 订单ID
	 * )
	 * @return [type]         [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-10
	 */
	public function getOrderDetail($params){
		$where = array(
			'o_id' => $params['o_id'],
			'm_id' => $params['m_id']
			);
		$order = D('Orders')->where($where)->find();
        $order['o_receiver_mobile'] = strpos($order['o_receiver_mobile'],':') ? decrypt($order['o_receiver_mobile']) : $order['o_receiver_mobile'];
        $order['o_receiver_telphone'] = strpos($order['o_receiver_telphone'],':') ? decrypt($order['o_receiver_telphone']) : $order['o_receiver_telphone'];
		if(empty($order)){
			$this->result['sub_msg'] = '无该订单!';
			return $this->result;
		}
		// 获取商品信息
		$field = 'fx_goods_info.g_picture,fx_orders_items.oi_nums,fx_goods_info.g_id,fx_orders_items.pdt_id,fx_orders_items.oi_g_name,fx_orders_items.oi_price';
		$data = $this->OrdersGoods($order['o_id'],$field,$params['m_id']);
		$order = array_merge($order,$data);
        // 获取可退款金额
        $order['allow_refund_money'] = $this->getRefundMoney($order);
		$ary_status = array(
            'o_status' => $order['o_status']
        );
        $str_status = D('Orders')->getOrderItmesStauts('o_status', $ary_status);
        $order['str_status'] = $str_status;
		
		//订单发货状态
		$ary_orders_status = D("Orders")->getOrdersStatus($order['o_id']);

        $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'o_id' => $order['o_id']
                ))->order('or_update_time asc')->select();
        if (!empty($ary_afersale) && is_array($ary_afersale)) {
            foreach ($ary_afersale as $keyaf => $valaf) {
                if ($valaf ['or_service_verify'] == '1' && $valaf ['or_finance_verify'] == '1') {
                    M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $order['o_id'],
                        'or_id'=>$valaf['or_id'],
                    ))->save(array(
                        'or_processing_status' => 1
                    ));
                }
                // 退款
                if ($valaf['or_refund_type'] == 1) {
                    switch ($valaf['or_processing_status']) {
                        case 0 :
                            $order['refund_status'] = '退款中';
                            break;
                        case 1 :
                            $order['refund_status'] = '退款成功';
                            break;
                        case 2 :
                            $order['refund_status'] = '退款驳回';
                            break;
                        default :
                            $order['refund_status'] = ''; // 没有退款
                    }
                } elseif ($valaf['or_refund_type'] == 2) { // 退货
                    switch ($valaf['or_processing_status']) {
                        case 0 :
                            $order['refund_goods_status'] = '退货中';
                            break;
                        case 1 :
                            $order['refund_goods_status'] = '退货成功';
                            break;
                        case 2 :
                            $order['refund_goods_status'] = '退货驳回';
                            break;
                        default :
                            $order['refund_goods_status'] = ''; // 没有退款
                    }
                }
            }
        }
        if ($order['refund_goods_status'] == '') {
            $order['deliver_status'] = $ary_orders_status ['deliver_status'];
        }
		if ($order['refund_status'] == '') {
            // 订单支付状态
            $ary_pay_status = array(
                'o_pay_status' => $order ['o_pay_status']
            );
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $order['str_pay_status'] = $str_pay_status;
        }
		
        // 退款、退货类型
        $order['refund_type'] = $ary_orders_status ['refund_type'];	
		// 支付方式
        $ary_payment_where = array(
            'pc_id' => $order ['o_payment']
        );
        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
        $order['payment_name'] = $ary_payment ['pc_custom_name'];
		
        // 判断是否已生成退款/退货单
        if ($order['refund_type'] == '1') {
            $where = array();
            $where['o_id'] = $order ['o_id'];
            $where['or_refund_type'] = 1;
            $where['or_processing_status'] = array(
                'neq',
                2
            );
            $num_refund = D('OrdersRefunds')->where($where)->count();
        }
		// 审核后是否允许申请退款
        $resdata = D('SysConfig')->getCfg('ALLOW_REFUND_APPLY','ALLOW_REFUND_APPLY');
        $openDelivery = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY_ALL');
        $isOpenDelivery = 0;
        if(isset($openDelivery['ALLOW_REFUND_DELIVERY_ALL']) && $openDelivery['ALLOW_REFUND_DELIVERY_ALL']== 1){
            $delivery_where = array(
                'o_id' => $order['o_id'],
                'or_refund_type' => 3
                );
            $isOpenDelivery = 1;
            // 判断是否已经提交退运费申请
            $delivery_data = D('OrdersRefunds')->where($delivery_where)->count();
            if($delivery_data >= 1){
                $isOpenDelivery = 0;
            }
        }
		//是否审核
        $order['str_auto_status'] = ($order['o_audit'] == 1) ? '已审核' : '未审核';
		//订单状态
		$order_status = $order['str_status'];
		if($order['o_status'] == 1){
			$order_status .=$order['str_pay_status'];
		}
		$order_status .=$order['refund_goods_status'].$order['refund_status'].$order['str_auto_status'];
		if($order['o_status'] == 1){
			$order_status .=$order['deliver_status'];
		}
		$order['str_order_status'] = $order_status;
        $is_refund = 0;//不允许退款和退货
        if($order['o_pay_status'] == 1 && $order['str_pay_status'] == '已支付' && $order['o_status'] == 1 && $order['refund_type'] == 1 && ($order['o_audit'] !=1 or $resdata['ALLOW_REFUND_APPLY']['sc_value'] == 1) && empty($num_refund)){
			$is_refund = 1;//允许申请退款
		}		
        if($order['o_pay_status'] == 1 && $order['str_pay_status'] == '已支付' && $order['refund_type'] == 2 && $order['o_status'] != 4 && $order['refund_status'] != '退款中' && $order['refund_goods_status'] !='退货中' && $order['refund_goods_status'] !='退货成功' && $order['refund_goods_status'] !='退款成功' ){
			if($order['o_status'] == 5){
				$is_refund = 2;//允许申请售后
			}
		}
		$order['is_refund'] = $is_refund;
		//物流信息
	    $ary_delivery = D('Orders')->ordersLogistic($order['o_id']);
		//$order['ary_delivery'] = array('od_logi_name'=>$ary_delivery['delivery']['od_logi_name'],'od_logi_no'=>$ary_delivery['delivery']['od_logi_no']);
		$order['od_logi_name'] = $ary_delivery['delivery']['od_logi_name'];
		$order['od_logi_no'] = $ary_delivery['delivery']['od_logi_no'];
		$this->result['status'] = true;
    	$this->result['code'] = 10703;
    	$this->result['sub_msg'] = '获取订单详情成功!';
    	$this->result['info'] = $order;
		return $this->result;
	}

    /**
     * 获取可退款金额
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-17
     */
    public function getRefundMoney($order){
        $o_id = $order['o_id'];
        $ary_where = array('o_id'=>$o_id);
        $ary_orders = D('Orders')->getOrdersInfo($ary_where);
        $total_price = 0;
        if (!empty($ary_orders) && is_array($ary_orders)) {
            foreach ($ary_orders as $k => $v) {
                $pay_order_info = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field('oi_coupon_menoy,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money,oi_type,promotion_price')->where(array('oi_id' => $v['oi_id']))->find();
                if($pay_order_info['oi_type'] == 9){
                    // $this->error('试用商品不允许申请退货退款');exit();
                    return $total_price;
                }
                // $ary_orders[$k] = array_merge($ary_orders[$k],$pay_order_info);
                $ary_orders[$k]['promotion_price'] = $pay_order_info['oi_coupon_menoy']+$pay_order_info['oi_bonus_money']+$pay_order_info['oi_cards_money']+$pay_order_info['oi_jlb_money']+$pay_order_info['oi_point_money']+$pay_order_info['promotion_price'];
                $total_price +=$v['oi_price']*$v['oi_nums']-$ary_orders[$k]['promotion_price'];
            }
        }
        $ary_orders_info = $ary_orders[0];
	
        if($ary_orders_info['oi_ship_status'] == 2){
            // 判断是否开启退货包含运费
            $ary_data = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY');
            if($ary_orders_info['o_pay']>=$total_price){
                $ary_orders_info['refund_pay'] = $total_price;
                if(isset($ary_data['ALLOW_REFUND_DELIVERY']) && $ary_data['ALLOW_REFUND_DELIVERY'] == 1){
                    $ary_orders_info['refund_pay'] += $ary_orders_info['o_cost_freight'];
                }
            }else{
                $ary_orders_info['refund_pay'] = $ary_orders_info['o_pay'];
                if($ary_data['ALLOW_REFUND_DELIVERY'] != 1){
                    $ary_orders_info['refund_pay'] -= $ary_orders_info['o_cost_freight'];
                    if($ary_orders_info['refund_pay']<0){
                        $ary_orders_info['refund_pay'] = 0;
                    }
                }  
            }
            //如果为空
            if(empty($ary_orders_info['refund_pay'])){
                $refund_pay = M('payment_serial',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$o_id,'ps_type'=>0,'ps_status'=>1))->getField('ps_money');
                if(!empty($refund_pay)){
                    $ary_orders_info['refund_pay'] = $refund_pay;
                }
            }
        }else{
			$ary_orders_info['refund_pay'] = $ary_orders_info['o_pay'];
        }
        return $ary_orders_info['refund_pay'];
    }

    /**
     * [根据订单父id获取订单详情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-27
     */
    public function getOrderByPoid($params){
        $where = array(
            'po_id' => $params['po_id']
            );
        $order = D('Orders')->where($where)->select();
        if(empty($order) || !is_array($order)){
            $this->result['sub_msg'] = '无该订单!';
            return $this->result;
        }
        foreach($order as &$or){
            // 获取商品信息
            $or['shop_name'] = D('Shops')->getName($or['shop_id']);
            $field = 'fx_goods_info.g_picture,fx_orders_items.oi_nums,fx_goods_info.g_id';
            $data = $this->OrdersGoods($or['o_id'],$field,$params['m_id']);
            $or = array_merge($or,$data);
        }
        $this->result['status'] = true;
        $this->result['code'] = 10703;
        $this->result['sub_msg'] = '获取订单详情成功!';
        $this->result['info'] = $order;
        return $this->result;
    }

	/**
	 * [获取订单商品信息]
	 * @param [type] $params [description]
	 * @param string $field  [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-17
	 */
	public function OrdersGoods($o_id,$field='*',$m_id){
		$data = array();
		if(empty($o_id)){
			return $data;
		}
		$ary_where = array(
			'o_id' => $o_id,
			);
		$data['goods_data'] = D('OrdersItems')
				->field($field)
				->join("fx_goods_info on fx_orders_items.g_id = fx_goods_info.g_id")
				->where($ary_where)
				->select();
		$cfg = D('SysConfig')->getCfgByModule('goods_comment_set',1);
    	$cfg['comment_show_condition'] = explode(',',$cfg['comment_show_condition']);
		//是否允许追加评论
		foreach($data['goods_data'] as $key=>$good){
			//七牛图片显示
			if(isset($good['g_picture']) && $good['g_picture']!=''){
				$data['goods_data'][$key]['g_picture']=D('QnPic')->picToQn($good['g_picture']); 
			}
			//是否评论过
			$data['goods_data'][$key]['is_have_comment']=$this->isHaveComment($good['g_id'],$m_id,$o_id); 
			if($data['goods_data'][$key]['is_have_comment'] == 1 && $cfg['comment_show_condition'] == 1){
				$data['goods_data'][$key]['is_have_comment']=2;//等于2表示允许追加评论
			}
			if(isset($good['oi_nums'])){
				$data['goods_num'] += $good['oi_nums'];
			}
		}
		return $data;
	}

	public function isHaveComment($g_id,$m_id,$o_id){
		$is_exist = D("GoodsComments")->where(array(
			'g_id' => $g_id,
			'm_id' => $m_id,
			'gcom_order_id' => $o_id
		))->count();
		if($is_exist>0){
			return 1;
		}else{
			return 0;
		}
	}
	/**
	 * [取消交易]
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-17
	 */
	public function orderTradeClose($params){
		M()->startTrans();
        $trade_data['o_status']=2;
        $res_products = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id' => $params['o_id']))->save($trade_data);
		if($res_products == false){
			$this->result['sub_msg'] = '订单作废失败';
		}else{
			//订单作废销量返回
			$orderItems = M('orders_items')->field('oi_id,o_id,g_id,pdt_id,oi_nums,fc_id,oi_type')->where(array('o_id'=>$params['o_id']))->select();
			foreach($orderItems as $item){
				if($item['oi_type']==5 && !empty($item['fc_id'])){
					$return_groupbuy_nums = D("Groupbuy")->where(array('gp_id' => $item['fc_id']))->setDec("gp_now_number",$item['oi_nums']);
				}elseif($item['oi_type']==7 && !empty($item['fc_id'])){
					$retun_spike_nums=D("Spike")->where(array('sp_id' => $item['fc_id']))->setDec("sp_now_number",$item['oi_nums']);
				}
				$goods_num_res = M("goods_info")->where(array(
							'g_id' => $item ['g_id']
						))->data(array(
							'g_salenum' => array(
								'exp',
								'g_salenum - '.$item['oi_nums']
							)
						))->save();
			}
			// 冻结积分释放掉
			$point_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $params['o_id']))->find();
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
							 M()->rollback();
							 $this->result['sub_msg'] = '作废返回冻结积分失败';
						 }
					}else{
						  M()->rollback();
						  $this->result['sub_msg'] = '作废返回冻结积分写日志失败';
					}
				}else{
					  M()->rollback();
					  $this->result['sub_msg'] = '作废返回冻结积分没有找到要返回的用户冻结金额';
				}
			}
			//优惠券还原
			$ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'c_order_id' => $params['o_id']
					))->find();
			if (!empty($ary_coupon) && is_array($ary_coupon)) {
				$res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
							'c_order_id' => $params['o_id']
						))->save(array(
					'c_used_id' => 0,
					'c_order_id' => 0,
					'c_is_use' => 0
						));
				if (!$res_coupon) {
					M()->rollback();
					$this->result['sub_msg'] = '优惠券还原失败';
				}
			}	
			M()->commit();	
			$this->result['status'] = true;
			$this->result['code'] = 10704;
			$this->result['sub_msg'] = '作废成功';
			$this->result['info'] = array('success'=>'success');
		}
		return $this->result;
	}

	/**
	 * [确认收货]
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-17
	 */
	public function orderReceipt($params){
		$result = D('Orders')->orderConfirm($params['o_id'],$params['m_id']);
		if($result['status'] != true){
			$this->result['sub_msg'] = $result['msg'];
		}else{
			$this->result['status'] = true;
			$this->result['code'] = 10705;
			$this->result['sub_msg'] = $result['msg'];
			$this->result['info'] = array('success'=>'success');
		}
		return $this->result;
	}

	/**
     * 生成一张支付序列单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-23
     * @param int $int_type 0代表支付订单 1代表预存款充值
     * @param array $ary_order 订单信息
     * @param int $pay_type 付款类型 0全额支付 1定金支付 2尾款支付
     * @return string 返回支付单序列号，用于第三方平台支付
     */
    protected function addPaymentSerial($int_type = 0, $ary_order = array(),$pay_type = 0,$int_m_id) {
        //订单支付时，确定支付宝商户订单号 Add Terry 2013-08-26
        $ary_ps = D('PaymentSerial')
            ->where(array('o_id'=>$ary_order['o_id']))
            ->order('ps_id desc')
            ->find();
        if(!empty($ary_ps) && $ary_ps['ps_money'] == $ary_order['o_total_price']){
            $res = D('PaymentSerial')
                ->data(array(
                    'ps_update_time'=>date('Y-m-d H:i:s'),
                    'ps_sys_trade_time' => date('Y-m-d H:i:s'),
                ))
                ->where(array('o_payment_sn'=>$ary_ps['o_payment_sn']))
                ->save();
            if(false === $res){
                return array('result'=>false, 'message'=>'更新支付时间失败');
            }
            //生成支付历史数据
            $add_res = D('PaymentSerial')->updatePaymentHistory($ary_ps['o_payment_sn']);
            if(false === $add_res){
                return array('result'=>false, 'message' => "生成支付历史记录失败");
            }
            return array('result'=>true, 'data' => $ary_ps['o_payment_sn']);

        }
        else{
            $payment_sn = substr(getRandomNumber(), 0, 19);
            $data = array(
                'm_id' => $int_m_id,
                'pc_code' => $ary_order['pc_code'],
                'ps_money' => $ary_order['o_total_price'],
                'ps_type' => $int_type,
                'o_id' => $ary_order['o_id'],
                'is_parent_id' => $ary_order['is_parent_id'],
                'ps_status' => 0,
                'pay_type' => $pay_type,
                'o_payment_sn' => $payment_sn,
                'ps_create_time' => date('Y-m-d H:i:s'),
                'ps_update_time' => date('Y-m-d H:i:s'),
                'ps_sys_trade_time' => date('Y-m-d H:i:s'),
            );
            $int_ps_id = D('PaymentSerial')->data($data)->add();
            if (false === $int_ps_id) {
               return array('result'=>false, 'message'=>'生成支付序列号失败');
            }
            //生成支付历史数据
            $add_res = D('PaymentSerial')->updatePaymentHistory($payment_sn);
            if(false === $add_res){
                return array('result'=>false, 'data' => $ary_ps['o_payment_sn']);
            }
            return array('result'=>true, 'data' => $payment_sn);

        }
    }

    /**
     * 获取支付参数
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-01-21
     */
    public function payNew($params){
        $int_id = $params['oid'];
        $payment_id = $params['payment_id'];
        $pay_stat = $params['typeStat'];
        
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
            $group = '';
            $ary_orders_data = D('Orders')->getOrdersData($where, $search_field, $group);
            if (empty($ary_orders_data) && count($ary_orders_data) <= 0) {
                $this->result['sub_msg'] = '订单不存在或已支付';
                return $this->result;
                // $this->error('订单不存在或已支付'); // XXXXXXXXXXXXXXXXXXXXX
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
                $this->result['sub_msg'] = '该货品已售完！';
                return $this->result;
                // $this->error('该货品已售完！');
            }
            //没有结果
        }else{
            if(0 >= $int_pdt_stock['pdt_stock']){
                $this->result['sub_msg'] = '该货品已售完！';
                return $this->result;
                // $this->error('该货品已售完！');
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
                    $this->result['sub_msg'] = $is_pay['msg'];
                    return $this->result;
                    // $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = $ary_orders ['o_all_price'];
            } elseif ($pay_stat == 1) {
                // 团购定金支付,获取定金
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id,1);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->result['sub_msg'] = $is_pay['msg'];
                    return $this->result;
                    // $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = sprintf("%0.3f", $groupbuy ['gp_deposit_price'] * $ary_orders ['oi_nums']);
            } elseif ($pay_stat == 2) {
                // 尾款支付。检测当前时间是否在指定支付尾款时间内
                $gp_overdue_start_time = strtotime($groupbuy ['gp_overdue_start_time']);
                $gp_overdue_end_time = strtotime($groupbuy ['gp_overdue_end_time']);
                if ($gp_overdue_start_time > mktime()) {
                    // 还未到支付尾款时间
                    // $this->error('请于' . date('Y年m月d日 H:i:s', $gp_overdue_start_time) . '后补交尾款');
                    $this->result['sub_msg'] = '请于' . date('Y年m月d日 H:i:s', $gp_overdue_start_time) . '后补交尾款';
                    return $this->result;
                } elseif (($gp_overdue_start_time < mktime()) && ($gp_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    // $this->error('补交尾款时间已过，请联系客服人员');
                    $this->result['sub_msg'] = '补交尾款时间已过，请联系客服人员';
                    return $this->result;
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
                    // $this->error('请于' . date('Y年m月d日 H:i:s', $p_overdue_start_time) . '后补交尾款');
                    $this->result['sub_msg'] = '请于' . date('Y年m月d日 H:i:s', $p_overdue_start_time) . '后补交尾款';
                    return $this->result;
                } elseif (($p_overdue_start_time < mktime()) && ($p_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    // $this->error('补交尾款时间已过，请联系客服人员');
                    $this->result['sub_msg'] = '补交尾款时间已过，请联系客服人员';
                    return $this->result;
                }
                $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
            }
        }else if($ary_orders ['oi_type'] == '7'){
			/**
            $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
            if (!$result_spike) {
                // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->result['sub_msg'] = $result_order ['message'];
                return $this->result;
                // $this->error($result_order ['message']);
                // exit();
            }**/
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }else {
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }

        // # 使用支付模型 支付订单 ###############################################
        $Payment = D('PaymentCfg');
        if ($ary_orders ['o_payment'] != $payment_id && !empty($payment_id)) {
            $ary_orders ['o_payment'] = $payment_id;
            $update_payment_res = D('Orders')->UpdateOrdersPayment($int_id, $payment_id);
            if ($update_payment_res === false) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->result['sub_msg'] = '支付方式更新失败!';
                return $this->result;
                // exit();
            }
        }
        
        $info = $Payment->where(array(
                    'pc_id' => $ary_orders ['o_payment']
                ))->find();
        
        if (false == $info) {
            // 支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
            M('', '', 'DB_CUSTOM')->rollback();
            $this->result['sub_msg'] = '支付方式不存在，或不可用';
            return $this->result;
            // $this->error('支付方式不存在，或不可用');
            // exit();
        }
        if($info['pc_abbreviation'] != 'WAPALIPAY' && $info['pc_abbreviation'] != 'DEPOSIT'){
            M('', '', 'DB_CUSTOM')->rollback();
            $this->result['sub_msg'] = '手机端不支持该种支付方式!';
            return $this->result;
        }else{
            $Pay = $Payment::factory($info ['pc_abbreviation'], json_decode($info ['pc_config'], true));
			if($info['pc_abbreviation'] == 'WAPALIPAY'){
				$Pay->setSource('app');
			}
            $result = $Pay->pay($int_id, $ary_orders ['oi_type'], $o_pay, $pay_stat,$ary_orders ['m_id']);
            writeLog(json_encode($result)."\t", "order_mobile_pay.log");	
			
            if($result['result'] !== true){
                $this->result['sub_msg'] = $result['message'];
                return $this->result;
            }else{
				if($info['pc_abbreviation'] == 'WAPALIPAY'){
					
					$this->result['status'] = $result['result'];
					if(isset($result['message'])){
						$this->result['sub_msg'] = $result['message'];
					}else{
						$this->result['sub_msg'] = '支付参数组装成功!';
					}
					$this->result['info'] = $result['info'];
					return $this->result;	exit;	
				}else{
	                $ary_orders ['o_pay'] = $o_pay;
					 //结余款支付成功后续操作
					$res_last = $this->payDepositLast($int_id, $ary_orders, $pay_stat, $info);
					if($res_last['result'] === true){
						M('', '', 'DB_CUSTOM')->commit();
						$this->result['status'] = $res_last['result'];
						if(isset($res_last['message'])){
							$this->result['sub_msg'] = $res_last['message'];
						}else{
							$this->result['sub_msg'] = '支付参数组装成功!';
						}
						$this->result['info'] = $res_last['info'];
					}else{
						$this->result['status'] = $res_last['result'];
						$this->result['sub_msg'] = $res_last['message'];
					}
					return $this->result;				
				}
            }
        }
        return $this->result;exit;
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
                $this->result['sub_msg'] = '该货品已售完！';
                return $this->result;
                // $this->error('该货品已售完！');
            }
            $ary_orders ['o_pay_status'] = 0;
            $result_order = $this->orders->orderPayment($int_id, $ary_orders);
            
            if (!$result_order ['result']) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->result['sub_msg'] = $result_order ['message'];
                return $this->result;
                // $this->error($result_order ['message']);
                // exit();
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
        $this->result['sub_msg'] = '支付成功';
        $this->result['info'] = array('result'=>'success');
        $this->result['status'] = true;
        return $this->result;
        // M('', '', 'DB_CUSTOM')->commit();
        // $url = U("Ucenter/Orders/PaymentSuccess", array(
        //     'oid' => $int_id
        //         ));
        // redirect($url);
        // exit();
    }

    /**
     * 支付成功回调 (copy by Ucenter/Payment/synPayReturn方法)
     * @modify by Tom
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-26
     */
    public function ReturnPay($data){
        $code = $data['code'];
        $m_id = $data['m_id'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['code']);
        unset($data['m_id']);
        
        if($code != 'WAPALIPAY'){
            $this->result['sub_msg'] = '不支持该种回调';
            $this->result['status'] = false;
            return $this->result;
        }
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
        writeLog(json_encode($data),"order_pay.log");
        if (false == $ary_pay) {
            $this->result['sub_msg'] = '不存在的支付方式';
            $this->result['status'] = false;
            return $this->result;
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);
		 writeLog("respond返回信息\t".json_encode($result),"order_pay.log");
        //获取最后一次支付的付款类型
        if(!empty($result['o_id'])){
            $result['o_id'] = trim($result['o_id'],"订单编号:");
        }
        $Order = D('Orders');
        $ary_order = $Order->where(array('o_id' => $result['o_id']))->find();
		writeLog("ary_order \t".json_encode($ary_order),"order_pay.log");
        $ary_pay_ser = M('payment_serial')->where(array('o_id'=>$result['o_id'],'ps_status'=>array('neq',0)))->order('ps_update_time desc')->select();
        if($ary_pay_ser[0]['pay_type'] == '1'){
            $ary_order['o_pay_status'] = 3; //定金支付
        }else{
            $ary_order['o_pay_status'] = 1;
        }
        
        writeLog("付款方式 \t".json_encode($ary_order),"order_pay.log");
        M('','','DB_CUSTOM')->startTrans();
        if ($result['result']) {
            if(empty($result['m_id'])){
                //已经存在相同流水号的
                M('','','DB_CUSTOM')->commit();
                $this->result['sub_msg'] = '支付成功!';
                $this->result['status'] = true;
                $this->result['info'] = array('orderStatus'=>'success');
                return $this->result;
            }
            $ary_tmp_order_item = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$result['o_id']))->find();
            $is_add_num = 0;
            if($ary_pay_ser[0]['pay_type'] == 0){
                $is_add_num = 1;
            }elseif($ary_pay_ser[0]['pay_type'] == 1){
                $is_add_num = 1;
            }
            if($ary_tmp_order_item['oi_type'] == 5){
                //团购
                if($is_add_num == 1){
                    $gp_id = $ary_tmp_order_item['fc_id'];
					
                   // $gp_now_number = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gp_id'=>$gp_id))->getField('gp_now_number');
                   // M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id' => $gp_id))->save(array('gp_now_number' =>$gp_now_number + $ary_tmp_order_item['oi_nums']));
					
                }
            }elseif($ary_tmp_order_item['oi_type'] == 8){
                //预售
                if($is_add_num == 1){
                    $p_id = $ary_tmp_order_item['fc_id'];
                    $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' =>$p_id))->getField('p_now_number');
                    M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' => $p_id))->save(array('p_now_number' => $p_now_number + $ary_tmp_order_item ['oi_nums']));
                }
            }
            //检查请求签名正确
            if ($result['int_status'] == 1) {
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->result['sub_msg'] = $result_order['message'];
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'fail');
                    return $this->result;
                } else {
                    M('','','DB_CUSTOM')->commit();
                    $this->result['sub_msg'] = '支付成功!';
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'success');
                    return $this->result;
                }
            }else if($result['int_status'] == 2){
                //通过双接口中的担保交易
                $ary_order['o_pay'] = $result['total_fee'];
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->result['sub_msg'] = $result_order['message'];
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'fail');
                    return $this->result;
                } else {
                    M('','','DB_CUSTOM')->commit();
                    $this->result['sub_msg'] = '支付成功!';
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'success');
                    return $this->result;
                }
            }else if($result['int_status'] == 3){
                //通过双接口中的即时到帐交易
                $ary_order['o_pay'] = $result['total_fee'];
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->result['sub_msg'] = $result_order['message'];
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'fail');
                    return $this->result;
                } else {
                    M('','','DB_CUSTOM')->commit();
                    $this->result['sub_msg'] = '支付成功!';
                    $this->result['status'] = true;
                    $this->result['info'] = array('orderStatus'=>'success');
                    return $this->result;
                }
            }
        } else {
			if(isset($data['out_trade_no'])){
				$out_trade_no = explode('-',$data['out_trade_no']);
				$int_ps_id = $out_trade_no[1];
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
				if(!empty($ary_paymentSerial) && $ary_paymentSerial['ps_status'] == 1){
					 $this->result['sub_msg'] = '支付成功';
					 $this->result['info'] = array('orderStatus'=>'success');
					 $this->result['status'] = true;
				}
			}else{
				$this->result['sub_msg'] = '错误访问';
				$this->result['info'] = array('orderStatus'=>'fail');
				$this->result['status'] = false;
			}			
            return $this->result;
        }
    }

    /*
     * 结余款后续任务
       支付成功继续完成：1）流水账 2）更新订单状态 3）进入ERP');
     */
    private function payDepositLast($int_id, $ary_orders, $pay_stat, $info){
        if ($pay_stat == 1) {
            // 如果是定金支付，支付状态为部分支付
            $ary_orders ['o_pay_status'] = 3;
        } else {
            $ary_orders ['o_pay_status'] = 1;
        }
        //查询库存,如果库存数为负数则不再扣除库存
        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                       ->field('pdt_stock,oi_nums')
                                       ->where(array('o_id'=>$int_id))
                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                       ->find();
        if($ary_orders['oi_type'] ==5 || $ary_orders['oi_type'] ==8){
            if(0 >= $int_pdt_stock['pdt_stock']){
                return array('result' => false, 'message' => '该货品已售完！');
            }
            //没有结果
            if($int_pdt_stock['pdt_stock']<$int_pdt_stock['oi_nums']){
                return array('result' => false, 'message' => '该货品已售完！');
            }
        }else{
            if(0 >= $int_pdt_stock['pdt_stock']){
                return array('result' => false, 'message' => '该货品已售完！');
            }
        }
        
        $result_order = D('Orders')->orderPayment($int_id, $ary_orders);
        
        if (!$result_order ['result']) {
            // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => $result_order['message']);
        }

        $ary_balance_info = array(
            'bt_id' => '1',
            'bi_sn' => time(),
            'm_id' => $ary_orders['m_id'],
            'bi_money' => $ary_orders['o_pay'],
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
            return array('result' => false, 'message' => '生成支付明细失败，请重试.');
        } else {
            $arr_data = array();
            $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
            $arr_data ['bi_sn'] = time() . $str_sn;
            $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'bi_id' => $arr_res
                    ))->data($arr_data)->save();
            if (!$arr_result) {
                M('', '', 'DB_CUSTOM')->rollback();
                return array('result' => false, 'message' => '更新支付明细失败，请重试.');
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
                return array('result'=> false, 'message'=>'生成结余款调整单日志失败，请重试...');
            }
            $add_balance_log ['bvl_type'] = '3';
            if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                M('', '', 'DB_CUSTOM')->rollback();
                return array('result'=> false, 'message'=>'生成结余款调整单日志失败，请重试...');
            }
        }
        if ($info ['pc_abbreviation'] == 'DEPOSIT') {
            $add_payment_serial ['m_id'] = $ary_orders['m_id'];
            $add_payment_serial ['pc_code'] = 'DEPOSIT';
            $add_payment_serial ['ps_money'] = $ary_orders['o_pay'];
            $add_payment_serial ['ps_type'] = 0;
            $add_payment_serial ['o_id'] = $int_id;
            $add_payment_serial ['ps_status'] = 1;
            $add_payment_serial ['pay_type'] = $pay_stat;
            $add_payment_serial ['ps_create_time'] = date('Y-m-d H:i:s');
            $ary_result = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_payment_serial);
            if (false === $ary_result) {
                M('', '', 'DB_CUSTOM')->rollback();
                return array('result'=> false, 'message'=>'生成支付明细失败，请重试...');
            }
        }
        return array('result' => true, 'message' => '支付成功','info'=>array('success'=>'ok','code'=>'DEPOSIT'));
    }

}
