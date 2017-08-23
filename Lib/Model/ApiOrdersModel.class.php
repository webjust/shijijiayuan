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
class ApiOrdersModel extends GyfxModel {

	/**
	 * 价格对象
	 * @var obj
	 */
	private $Price;
    
    /*
    * 订单修改接口映射字段
     * @var obj
    */
    private $ary_update_field = array(
             'receiver_name'=>'o_receiver_name',
             'receiver_address'=>'o_receiver_address',
             'receiver_state'=>'o_receiver_state',
			 'receiver_city'=>'o_receiver_city',
             'receiver_district'=>'o_receiver_district',
		     'ra_id'=>'ra_id',
             'receiver_phone'=>'o_receiver_phone',
		     'receiver_mobile'=>'o_receiver_mobile',
             'buyer_message'=>'o_buyer_comments',
             'seller_memo'=>'o_seller_comments',
		     'receiver_zip'=>'o_receiver_zipcode',
             'post_fee'=>'o_cost_freight',
		     'invoice_head'=>'invoice_head',
             'is_invoice'=>'is_invoice',
             'invoice_type'=>'invoice_type',
             'invoice_content'=>'invoice_content',
             'trade_memo'=>'o_seller_comments',
		     'receiver_time'=>'o_receiver_time',
             'cod_fee'=>'o_cost_payment',
		     'o_status'=>'o_status',
		     'pre_sale'=>'o_pre_sale',
             'unfreeze_time'=>'o_unfreeze_time',
		     'pay_type'=>'or_pay_type_id',
             'o_all_price'=>'o_all_price',
             'discount_fee '=>'o_discount',
             'store_code' =>'erp_id'
         );

	/**
	 * 构造方法
	 * @author zuo <zuojianghua@guanyisoft.com>
	 * @date 2012-12-14
	 */
	public function __construct() {
		parent::__construct();
		$this->Price = D('Price');
		$this->orders = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM');
		$this->orders_items = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
	}

	/**
	 * 修改物流公司和运单号
	 * detail:支持卖家发货后修改物流公司和运单号。支持订单类型支持在线下单和自己联系。 自己联系只能切换为自己联系的公司，在线下单也只能切换为在线下单的物流公司。 调用时订单状态是卖家已发货，自己联系在发货后24小时内在线下单未揽收成功才可使用
	 * request params
	 * @tid 	Number 必须 	123456		交易ID
	 * @sub_tid 	Number [] 	可选 	1,2,3 		拆单子订单列表
	 * @out_sid 	String 必须 	123456789		运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；
	 * @company_code 	String 必须 	POST		物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。 如果是货到付款订单，选择的物流公司必须支持货到付款发货方式
	 * reponse params
	 * @shipping 	Shipping  		返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	public function logisticsConsignResend($array_params) {
		//测试数据
		$array_params = array(
            'tid' => '',
            'out_sid' => '',
            'company_code' => ''
            );
//             dump($array_params);
//             die();
	}

	/**
	 * 在线订单发货处理（支持货到付款）
	 * detail:用户调用该接口可实现在线订单发货（支持货到付款） 调用该接口实现在线下单发货，有两种情况：
	 * 	如果不输入运单号的情况：交易状态不会改变，需要调用taobao.logistics.online.confirm确认发货后交易状态才会变成卖家已发货。
	 * 	如果输入运单号的情况发货：交易订单状态会直接变成卖家已发货 。
	 * request params
	 * @tid 	Number 必须 	255582		交易ID
	 * @sub_tid 	Number [] 	可选 	1,2,3 		拆单子订单列表
	 * @out_sid 	String 可选 	123456789		运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；
	 * @company_code 	String 必须 	POST		物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。 如果是货到付款订单，选择的物流公司必须支持货到付款发货方式
	 * reponse params
	 * @shipping 	Shipping  	返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-31
	 */
	public function logisticsOnlineSend($array_params) {
		$order_status = $this->getOrdersStatus($array_params['tid']);
		//退换货单不允许发货
		if($order_status['refund_goods_status'] == '退货成功' || $order_status['refund_goods_status'] == '退货中' || $order_status['refund_goods_status'] == '退款中' || $order_status['refund_goods_status'] == '退款成功'){
			return 2;
		}
		if($order_status['deliver_status'] == '已发货'){
			return 3;
		}
		$array_params['company_code'] = str_replace("'", '', $array_params['company_code']);
		$d_obj = M('orders_delivery', C('DB_PREFIX'), 'DB_CUSTOM');
		$di_obj = M('orders_delivery_items', C('DB_PREFIX'), 'DB_CUSTOM');
		$i_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
		$item_info = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM');
		$item = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM');
		$l_obj = M('logistic_corp', C('DB_PREFIX'), 'DB_CUSTOM'); //物流公司存不存在，不存在新增,这期先不处理
		$lc = $l_obj->field('lc_id,lc_name')->where(array('lc_is_enable'=>1,'lc_abbreviation_name' => trim($array_params['company_code'])))->find();
		if (empty($lc)) {
			$save_data = array(
                'lc_name' => trim($array_params['company_code']),
                'lc_description' => $array_params['company_code'],
                'lc_is_enable' => '1',
                'lc_abbreviation_name' => trim($array_params['company_code']),
                'lc_create_time' => date("Y-m-d H:i:s"),
                'lc_update_time' => date("Y-m-d H:i:s")
			);
			$lc['lc_id'] = $l_obj->add($save_data);
			$lc['lc_name'] = trim($array_params['company_code']);
		}
		$o_obj = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
		$oi_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
		M('', C('DB_PREFIX'), 'DB_CUSTOM')->startTrans();

		//订单物流
		$delivery = array();
		$res_orders = $this->GetShipOrders($array_params);
		if($res_orders == false){
			return false;
		}
		if(empty($res_orders[0]['g_sn'])){
			return false;
		}
		//支付方式为支付宝
		if($res_orders[0]['o_payment'] == '2'){
			$ps_status = M('payment_serial',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$array_params['tid']))->getField('ps_status');
			//已支付到担保方
			if($ps_status == 2){
				$Payment = D('PaymentCfg');
	            $info = $Payment->where(array('pc_id' => $res_orders[0]['o_payment']))->find();
				if (false == $info) {
					M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
					//支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
					return false;
				} else {
					$Pay = $Payment::factory($info['pc_abbreviation'], json_decode($info['pc_config'], true));
					$ary_datas = $this->matchLogisticsCompanieCode($lc['lc_name']);
					$gwt_sn = M("payment_serial", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $array_params['tid'], "ps_type" => '0'))->find();
					$ary_params = array(
						'WIDtrade_no' => $gwt_sn['ps_gateway_sn'],
						'WIDlogistics_name' => $ary_datas['name'],
						'WIDinvoice_no' => $array_params['out_sid'],
						'WIDtransport_type' => 'POST'
					);
					$arr_result = $Pay->ship($ary_params);
					$arr_res = xml2array($arr_result);
					if ($arr_res['is_success'] == 'F') {
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
				}			
			}
			//作废或退换货
			if($ps_status == 4){
				return 2;
			}
		}	
		
		$where = array('o_id' => $res_orders[0]['o_id']);
		//为支持拆分发货预留
		//		if(!empty($array_params['sub_tid'])){
		//			$where['oi_id'] = $array_params['sub_tid'];
		//		}
		if (!empty($array_params['sub_tid'])) {
			$sub_tids = explode(",", $array_params['sub_tid']);
			foreach ($res_orders as $order) {
				if (in_array($order['oi_id'], $sub_tids)) {
					$res1 = $oi_obj->where(array('oi_id' => $order['oi_id']))
					->data(array('oi_update_time' => date("Y-m-d H:i:s"), 'oi_ship_status' => '2'))
					->save();
					if(!$res1){
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
				}
				//父订单更新发货状态
				if(!empty($order['initial_o_id'])){
					$res2 = $oi_obj->where(array('pdt_sn' => $order['pdt_sn'],'o_id'=>$order['initial_o_id'],'oi_type'=>$order['oi_type'],'fc_id'=>$order['fc_id']))
					->data(array('oi_update_time' => date("Y-m-d H:i:s"), 'oi_ship_status' => '2'))
					->save();
					if(!$res2){
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
				}
			}
		} else {       
			$res1 = $oi_obj->where(array('o_id' => $array_params['tid']))
			->data(array('oi_update_time' => date("Y-m-d H:i:s"), 'oi_ship_status' => '2'))
			->save();
			//更新父订单
			foreach ($res_orders as $order) {
				//父订单更新发货状态
				if(!empty($order['initial_o_id'])){
					$res2 = $oi_obj->where(array('pdt_sn' => $order['pdt_sn'],'o_id'=>$order['initial_o_id'],'oi_type'=>$order['oi_type'],'fc_id'=>$order['fc_id']))
					->data(array('oi_update_time' => date("Y-m-d H:i:s"), 'oi_ship_status' => '2'))
					->save();
					if(!$res2){
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
				}
			}
		}
		if (!$res1) {
			M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
			return false;
		}
		$delivery_count = $d_obj->where($where)->count();
		if ($delivery_count == 0) {
			$delivery = $res_orders[0];
			unset($delivery['oi_id']);
			unset($delivery['oi_nums']);
			unset($delivery['pdt_id']);
			$delivery['od_created'] = date('Y-m-d H:i:s');
			$delivery['od_logi_id'] = $lc['lc_id'];
			$delivery['od_logi_name'] = $lc['lc_name'];
			$delivery['od_logi_no'] = $array_params['out_sid'];
			$od_id = $d_obj->add($delivery);
			if (empty($od_id)) {
				M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
				return false;
			}
			//父订单生成发货单
			if(!empty($delivery['initial_o_id'])){
				$delivery['o_id'] = $delivery['initial_o_id'];
				$od_id = $d_obj->add($delivery);
				if (empty($od_id)) {
					M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
					return false;
				}
			}
			unset($delivery);
			if (!empty($res_orders)) {
				foreach ($res_orders as $order) {
					if (!empty($sub_tids)) {
						if (!in_array($order['oi_id'], $sub_tids)) {
							continue;
						}
					}
					$delivery_item = array(
                        'od_id' => $od_id,
                        'o_id' => $order['o_id'],
                        'oi_id' => $order['oi_id'],
                        'odi_num' => $order['oi_nums']
					);
					//插入明细
					$res = $di_obj->add($delivery_item);
					if (!$res) {
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
					//父订单生成明细
					if(!empty($order['initial_o_id'])){
						$delivery_item['o_id'] = $order['initial_o_id'];
						$delivery_item['oi_id'] = $oi_obj->where(array('o_id'=>$order['initial_o_id'],'pdt_id'=>$order['pdt_id'],'oi_type'=>$order['oi_type'],'fc_id'=>$order['fc_id']))->getField('oi_id');
						//插入明细
						$res = $di_obj->add($delivery_item);
						if (!$res) {
							M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							return false;
						}
					}
					
					//订单发货扣除冻结库存和实际库存并更新订单状态为已发货
					//是否开启区域限售，扣库存
					$is_global_stock = GLOBAL_STOCK;
					//开启扣仓库库存表
					if($is_global_stock == true){
						$products = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM')->field('pdt_total_stock,pdt_freeze_stock')->where(array('pdt_sn' => $order['pdt_sn']))->find();
						if (!empty($products)) {
							//$now_num = $products['pdt_total_stock'] - $order['oi_nums'];
							$free_num = $products['pdt_freeze_stock'] - $order['oi_nums'];
							if($free_num<0){
								$free_num = 0;
							}
							//M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_id' => $order['pdt_id']))
							//->save(array('pdt_total_stock' => $now_num,'pdt_stock'=>$now_num-$free_num, 'ws_update_time' => date('Y-m-d H:i:s'), 'pdt_freeze_stock' => $free_num));
							M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn' => $order['pdt_sn'],'erp_id'=>$order['erp_id']))
							->save(array('ws_update_time' => date('Y-m-d H:i:s'), 'pdt_freeze_stock' => $free_num));
						} else {
							M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							return false;
						}						
					}/*else{
						//不开启扣商品规格表
						$products = $item_info->field('pdt_total_stock,pdt_freeze_stock')->where(array('pdt_sn' => $order['pdt_sn']))->find();
						if (!empty($products)) {
							$now_num = $products['pdt_total_stock'] - $order['oi_nums'];
							$free_num = $products['pdt_freeze_stock'] - $order['oi_nums'];
							if($free_num<0){
								$free_num = 0;
							}
							$pdt_stock = $now_num-$free_num;
							if($pdt_stock<0){
								$pdt_stock = 0;
							}
							if($now_num<$free_num){
								$free_num = $now_num;
							}
							$item_info->where(array('pdt_sn' => $order['pdt_sn']))
							->save(array('pdt_total_stock' => $now_num,'pdt_stock'=>$pdt_stock,'pdt_update_time' => date('Y-m-d H:i:s'), 'pdt_freeze_stock' => $free_num, 'oi_ship_status' => '2'));
						} else {
							M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							return false;
						}
						//更新商品走库存
						$g_sn = $order['g_sn'];
						if (!empty($g_sn)) {
							$total_num = $item_info->where(array('g_sn' => $g_sn, 'pdt_status' => '0'))->sum('pdt_total_stock');
							if(!empty($total_num)){
								$item->where(array('g_sn' => $g_sn))->save(array('g_stock' => $total_num, 'g_update_time' => date("Y-m-d H:i:s")));
							}
						}
					}*/
					// 扣减分销商冻结库存  2014-09-15 --By Tom
					D('GoodsProducts')->DeductionInventoryFrozenStock(array('pdt_id'=>$order['pdt_id'],'m_id',$order['m_id'],'num'=>$order['oi_nums']));
				}
				$ary_data = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('fx_orders.o_pay_status,fx_orders.o_id,p.pc_pay_type,fx_orders.o_status')
				->join("fx_payment_cfg as p on(p.pc_id=fx_orders.o_payment) ")
				->where($where)->find();
				//货到付款订单新增销货收款单
				if (!$ary_data) {
					M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
					return false;
				}
				if (($ary_data['o_pay_status'] == 0) && ($ary_data['o_status'] == '1') && ($ary_data['pc_pay_type'] == 'cash_delivery')) {
					//父订单全部发货后生成销货收款单
					$o_id = $where['o_id'];
					if(!empty($order['initial_o_id'])){
						$o_id = $order['initial_o_id'];
					}
					$ary_order_data = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $order['initial_o_id']))->
					field('oi_ship_status')->select();
					if(empty($ary_order_data)){
						$is_ship = 0;
						foreach($ary_order_data as $ary_order){
							if($ary_order['oi_ship_status'] != '2'){
								$is_ship = 1;
							}
						}
					}
					$ary_data1 = M('sales_receipts', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $o_id, 'sr_status' => '1'))->count();
					if ($ary_data1 == 0) {
						$data = array();
						$data['sr_create_type'] = '1';
						$data['sr_create_time'] = date("Y-m-d H:i:s");
						$data['sr_update_time'] = date("Y-m-d H:i:s");
						$data['sr_verify_status'] = '0';
						$data['sr_type'] = '1';
						$data['m_id'] = $res_orders[0]['m_id'];
						$data['to_post_balance'] = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$o_id))->getField('o_all_price');
						$data['o_id'] = $o_id;
						$res = M('sales_receipts', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
						$this->writeVoucherLog(array(
								'sr_id' => $res,
								'srml_type' => '0',
								'srml_uid' => '0',
								'srml_change' => json_encode($data),
								'srml_create_time' => date("Y-m-d H:i:s")
						));
					} else {
						M('', C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
						return false;
					}
				}
			}
		}
		M('', C('DB_PREFIX'), 'DB_CUSTOM')->commit();
		$item = array();
		if ($od_id) {
			//更新日志表
			if(!empty($array_params['sub_tid']) ){
				$tid = $array_params['sub_tid'];
			}else{
				$tid = $array_params['tid'];
				 /*** 订单发货后获取订单优惠券**star by Joe**/
				//获取优惠券节点
				M('', '', 'DB_CUSTOM')->startTrans();
				$coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON','Y');
				$where = array ('fx_orders.o_id' => $tid);
				$ary_field = array('fx_orders.o_pay','fx_orders.m_id','fx_orders.o_reward_point','fx_orders.o_freeze_point','fx_orders.o_id','fx_orders.o_all_price','fx_orders.coupon_sn','fx_orders_items.pdt_id','fx_orders_items.pdt_sn','fx_orders_items.oi_nums','fx_orders_items.oi_type');
				$ary_orders = D('Orders')->getOrdersData($where,$ary_field);
				// 本次消费金额=支付单最后一次消费记录
				$payment_serial = M('payment_serial')->where(array('o_id'=>$ary_post['o_id']))->order('ps_create_time desc')->select();
				$payment_price = $payment_serial[0]['ps_money'];
				$all_price = $ary_orders[0]['o_all_price'];
				$coupon_sn = $ary_orders[0]['coupon_sn'];
				//print_r($ary_orders);exit;
				if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '1') {
					D('Coupon')->setPoinGetCoupon($ary_orders,$ary_orders['m_id']);
				}
				/*** 订单发货后获取订单优惠券****end**********/
				/*** 处理订单积分****start****By Joe******/
				$array_point_config = D('PointConfig')->getConfigs();
				if($array_point_config['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '0'){
					//发货后处理赠送积分
					if($ary_orders['o_reward_point']>0){
						$ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_orders['o_reward_point'],$ary_orders['m_id'],$ary_orders['o_id']);
						if(!$ary_reward_result['result']){
							M('', '', 'DB_CUSTOM')->rollback();
							//$this->error($ary_reward_result['message']);
							return false;
						}
					}
					//发货后处理消费积分
					if($ary_orders['o_freeze_point'] > 0){
						$ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_orders['o_reward_point'],$ary_orders['m_id']);
						if(!$ary_freeze_result['result']){
							M('', '', 'DB_CUSTOM')->rollback();
							//$this->error($ary_freeze_result['message']);
							return false;
						}
					}
				}
				//发货成功是否发站内性
				$is_ship = D('SysConfig')->getCfg('SHIP_SEND_MESSAGE','SHIP_SEND_MESSAGE','0','发货之后发送站内信');
				if($is_ship['SHIP_SEND_MESSAGE']['sc_value'] == '1'){
					$sl_title = '订单'.$tid.'发货成功';
					$sl_content = '订单'.$tid.'已发货,请及时收货，<a href="/Ucenter/Orders/pageShow/oid/'.$tid.'">查看订单</a>';
					$m_id = $ary_orders['m_id'];
					$sl_res = D('StationLetters')->addStationLotters($sl_title,$sl_content,$m_id);
					if(!$sl_res){
						M('', '', 'DB_CUSTOM')->rollback();
						//$this->error('站内信发送失败');
					}
				}
				/*** 处理订单积分****end**********/
				 M('', '', 'DB_CUSTOM')->commit();
			}
			$ary_orders_log = array(
					'o_id' => $tid,
					'ol_behavior' => '发货成功',
					'ol_text' => $od_id,
					'ol_uname'=>'ERP',
					'ol_create'=>date('Y-m-d H:i:s')
			);
	        $res = M('orders_log',C('DB_PREFIX'), 'DB_CUSTOM')->data($ary_orders_log,array(),true)->add();
			$item['shipping'] = array(
                'is_success' => true
			);
			return $item;
		} else {
			return $item;
		}
	}

    public function matchLogisticsCompanieCode($str_name) {
        if (false !== stripos($str_name, '平邮')) {
            $str_code = 'POST';
            $str_name = '平邮';
        } elseif (false !== stripos($str_name, 'EMS')) {
            $str_code = 'EMS';
            $str_name = '邮政EMS';
        } elseif (false !== stripos($str_name, '邮宝') || false !== stripos($str_name, 'e邮宝') || false !== stripos($str_name, 'E邮宝')) {
            $str_code = 'EMS';
            $str_name = 'E邮宝';
        } elseif (false !== stripos($str_name, '申通')) {
            $str_code = 'STO';
            $str_name = '申通快递';
        } elseif (false !== stripos($str_name, '圆通')) {
            $str_code = 'YTO';
            $str_name = '圆通速递';
        } elseif (false !== stripos($str_name, '中通')) {
            $str_code = 'ZTO';
            $str_name = '中通速递';
        } elseif (false !== stripos($str_name, '宅急送')) {
            $str_code = 'ZJS';
            $str_name = '宅急送';
        } elseif (false !== stripos($str_name, '顺丰')) {
            $str_code = 'SF';
            $str_name = '顺丰速运';
        } elseif (false !== stripos($str_name, '汇通')) {
            $str_code = 'HTKY';
        } elseif (false !== stripos($str_name, '韵达')) {
            $str_code = 'YUNDA';
            $str_name = '韵达快运';
        } elseif (false !== stripos($str_name, '天天')) {
            $str_code = 'TTKDEX';
            $str_name = '天天快递';
        } elseif (false !== stripos($str_name, '联邦')) {
            $str_code = 'FEDEX';
        } elseif (false !== stripos($str_name, '淘物流')) {
            $str_code = 'TWL';
        } elseif (false !== stripos($str_name, '风火天地')) {
            $str_code = 'FIREWIND';
        } elseif (false !== stripos($str_name, '华强')) {
            $str_code = 'YUD';
        } elseif (false !== stripos($str_name, '烽火')) {
            $str_code = 'DDS';
        } elseif (false !== stripos($str_name, '希伊艾斯')) {
            $str_code = 'ZOC';
        } elseif (false !== stripos($str_name, '亚风')) {
            $str_code = 'AIRFEX';
        } elseif (false !== stripos($str_name, '全一')) {
            $str_code = 'APEX';
        } elseif (false !== stripos($str_name, '小红马')) {
            $str_code = 'PONYEX';
        } elseif (false !== stripos($str_name, '龙邦')) {
            $str_code = 'LBEX';
        } elseif (false !== stripos($str_name, '长宇')) {
            $str_code = 'CYEXP';
        } elseif (false !== stripos($str_name, '大田')) {
            $str_code = 'DTW';
        } elseif (false !== stripos($str_name, '长发')) {
            $str_code = 'YUD';
        } elseif (false !== stripos($str_name, '特能')) {
            $str_code = 'SHQ';
        } else {
            $str_code = 'OTHER';
        }
        return array('name' => $str_name, 'code' => $str_code);
    }
	
	/**
	 * 记录审核日志
	 * @author Wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-06-04
	 */
	public function writeVoucherLog($params) {
		M('sales_receipts_modify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($params);
	}

	/**
	 * 获得发货订单
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @param array $data
	 * @return string
	 */
	public function GetShipOrders($array_params) {
		if (!empty($array_params['tid'])) {
			$where['fx_orders_items.o_id'] = $array_params['tid'];
		}
		if (!empty($array_params['sub_tid'])) {
			$where['fx_orders_items.oi_id'] = $array_params['sub_tid'];
		}
		$where['fx_orders_items.oi_ship_status'] = 0;
		$res_orders = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')
		->field('fx_orders_items.o_id,fx_orders_items.pdt_id,fx_orders_items.g_sn,fx_orders_items.pdt_sn,fx_orders.lt_id as od_delivery,fx_orders.m_id,fx_orders.o_cost_freight as od_money,
                     fx_orders.o_receiver_email as od_receiver_email,
                     fx_orders.o_receiver_name as od_receiver_name,
                     fx_orders.o_receiver_mobile as od_receiver_mobile,
                     fx_orders.o_receiver_telphone as od_receiver_telphone,
                     fx_orders.o_receiver_county as od_receiver_area,
                     fx_orders.o_receiver_address as od_receiver_address,
                     fx_orders.o_receiver_zipcode as od_receiver_zipcode,
                     fx_orders.o_receiver_city as od_receiver_city,
                     fx_orders.o_receiver_state as od_receiver_province,
					 fx_orders.o_payment,
                     fx_orders_items.oi_id,
                     fx_orders_items.oi_nums,
					 ifnull(fx_orders.initial_o_id,0) as initial_o_id,
					 fx_orders_items.oi_type,
					 fx_orders_items.fc_id,
					 fx_orders_items.erp_id
                     ')
		->join('fx_orders on fx_orders_items.o_id=fx_orders.o_id')
		//->group('fx_orders_items.o_id')
		->where($where)->select();
		return $res_orders;
	}

	/**
	 * 获取单笔交易的详细信息
	 * detail:
	 * 获取单笔交易的详细信息
	 * 	1. 只有在交易成功的状态下才能取到交易佣金，其它状态下取到的都是零或空值
	 * 	2. 只有单笔订单的情况下Trade数据结构中才包含商品相关的信息
	 * 	3. 获取到的Order中的payment字段在单笔子订单时包含物流费用，多笔子订单时不包含物流费用
	 * 	4. 请按需获取字段，减少TOP系统的压力
	 * request params
	 * @tid 	Number 	必须 	123456798 		交易编号
	 * @fields Field List 必须
	 * 1.Trade中可以指定返回的fields：seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag, buyer_rate, status, payment, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no,alipay_id,buyer_message, pic_path, num_iid, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city, receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no, seller_mobile, seller_phone, seller_name, seller_email, available_confirm_fee, has_post_fee, timeout_action_time, snapshot_url, cod_fee, cod_status, shipping_type, trade_memo, is_3D,buyer_email,buyer_area, trade_from,is_lgtype,is_force_wlb,is_brand_sale,buyer_cod_fee,discount_fee,seller_cod_fee,express_agency_fee,invoice_name,service_orders,credit_cardfee,step_trade_status,step_paid_fee,mark_desc,has_yfx,yfx_fee,yfx_id,yfx_type,trade_source(注：当该授权用户为卖家时不能查看买家buyer_memo,buyer_flag),eticket_ext,send_time 2.Order中可以指定返回fields：orders.title, orders.pic_path, orders.price, orders.num, orders.num_iid, orders.sku_id, orders.refund_status, orders.status, orders.oid, orders.total_fee, orders.payment, orders.discount_fee, orders.adjust_fee, orders.snapshot_url, orders.timeout_action_time，orders.sku_properties_name, orders.item_meal_name, orders.item_meal_id，item_memo,orders.buyer_rate, orders.seller_rate, orders.outer_iid, orders.outer_sku_id, orders.refund_id, orders.seller_type, orders.is_oversold,orders.end_time,orders.order_from,orders.consign_time,orders.shipping_type,orders.logistics_company,orders.invice_no 3.fields：orders（返回Order的所有内容） 4.flelds：promotion_details(返回promotion_details所有内容，优惠详情),invoice_name(发票抬头)
	 $fileds = array(
	 //'seller_nick',//卖家昵称
	 'buyer_nick',//买家昵称    m_name
	 'title',//交易标题，以店铺名作为此标题的值。注:taobao.trades.get接口返回的Trade中的title是商品名称
	 'type',//交易类型列表 分销默认fixed
	 'created',//交易创建时间。格式:yyyy-MM-dd HH:mm:ss o_create_time
	 'sid',//交易编号o_id
	 'tid',//交易编号o_id
	 'status',//交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
	 'payment',//实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分 o_pay
	 'discount_fee',//订单优惠金额 o_discount
	 //'promotion',//交易促销详细信息
	 //'adjust_fee',//手工调整金额.格式为:1.01;单位:元;精确到小数点后两位.
	 'post_fee',//邮费。o_cost_freight
	 'total_fee',//商品金额 o_goods_all_price
	 'pay_time',//付款时间。格式:yyyy-MM-dd HH:mm:ss ps_update_time
	 'end_time',//交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
	 'modified',//交易修改时间。格式:yyyy-MM-dd HH:mm:ss   o_update_time
	 'consign_time',//物流发货时间。格式:yyyy-MM-dd HH:mm:ss
	 'buyer_obtain_point_fee',//买家获得积分,返点的积分。格式:100;单位:个 o_reward_point
	 'point_fee',//买家使用积分。格式:100;单位:个.o_freeze_point
	 'real_point_fee',//买家实际使用积分（扣除部分退款使用的积分）。格式:100;单位:个
	 'received_payment',//卖家实际收到的支付宝打款金额 o_pay-coupon_value
	 'commission_fee',//交易佣金。精确到2位小数;单位:元。如:200.07，表示:200元7分
	 //'buyer_memo',//买家备注
	 'seller_memo',//卖家备注 o_seller_comments
	 'alipay_no',//第三方来源订单id o_source_id
	 'buyer_message',//买家留言 o_buyer_comments
	 'pic_path',//商品图片绝对途径
	 'num_iid',//商品数字编号
	 'num',//商品总数量
	 'price',//商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
	 'receiver_name',//收货人 o_receiver_name
	 'receiver_state',//收货人省份o_receiver_state
	 'receiver_city',//收货人城市 o_receiver_city
	 'receiver_district',//地区第三级（文字）o_receiver_county
	 'receiver_address',//收货人地址 o_receiver_address
	 'receiver_zip',//收货人邮编 o_receiver_zipcode
	 'receiver_mobile',//收货人手机 o_receiver_mobile
	 'receiver_phone',//收货人电话 o_receiver_telphone
	 'buyer_email',//买家邮箱
	 'freeze',//B2B冻结(延迟发货）,
	 'unfreeze_time',//B2B冻结解除时间（延迟发货时间）,
	 'is_presell',//预售订单( 0-否,1-是 ),
	 'qc',//质检员,
	 'is_coupon',//赠送优惠劵( 0-否,1-是 )
	 'coupon_sn',//优惠劵编号
	 'coupon_value',//优惠劵面额
	 'coupon_start_date',//优惠劵有效开始日,
	 'coupon_end_date',//优惠劵有效结束日,
	 'shipping_type',//创建交易时的物流方式（交易完成前，物流方式有可能改变，但系统里的这个字段一直不变）。可选值：ems, express, post, free, virtual。
	 'trade_memo',//交易备注，通过taobao.trade.add接口创建
	 'orders',

	 );
	 * reponse params
	 * @trade 	Trade 是 		搜索到的交易信息列表，返回的Trade和Order中包含的具体信息为入参fields请求的字段信息
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-06-01
	 */
	public function tradeFullinfoGet($array_params) {
		//测试数据
		//      	 = array(
		//    		'tid'=>'201305131229292647',
		//      		'fields'=>'orders'
		//    	);
		//$array_params['fields']='orders';
			//订单审核状态
		if($array_params['order_status'] == '0' || $array_params['order_status'] == '1'){
			$o_audit = $array_params['order_status'];
		}
		if(!empty($array_params['erp_id'])){
			$erp_id = $array_params['erp_id'];
		}
		$fields = explode(',', $array_params['fields']);
		$ary_orders_info = $this->getLocalOrdersItem($array_params['tid'],$fields,$o_audit,$erp_id);

        $trades = array();
		if ($ary_orders_info['success'] == '1') {
			$ary_data = $ary_orders_info['data'];
			if (!empty($ary_orders_info['data']['item']) && is_array($ary_orders_info['data']['item'])) {
				$fl_pdt_total_final_price = 0;
				$final_price = 0;
				$price = 0;
				$num = 0;
				$num_iid = "";
				foreach ($ary_data['item'] as $key => $item) {
					$num_iid .=$ary_data['item'][$key]['g_id'] . ",";
					$goods_item .= $ary_data['item'][$key]['g_sn'] . ",";
					$product_item .= $ary_data['item'][$key]['pdt_sn'] . ",";
					$fl_pdt_total_final_price += ($item['pdt_sale_price'] * $item['oi_nums']);
					$price += sprintf("%.2f", $item['oi_price']);
					$final_price +=sprintf("%.2f", $item['oi_price'])*$ary_data['item'][$key]['oi_nums'];
					$prices .= sprintf("%.2f", $ary_data['item'][$key]['oi_price']) . ",";
					$nums .= $ary_data['item'][$key]['oi_nums'] . ",";
					$num +=$ary_data['item'][$key]['oi_nums'];
				}
			}
			if (in_array('shipping_type', $fields)) {
				if ($ary_data['lt_id']) {
					$lc_id = M("logistic_type", C('DB_PREFIX'), 'DB_CUSTOM')->field('lc_id')->where(array('lt_id' => $ary_data['lt_id']))->find();
					$lt = M("logistic_corp", C('DB_PREFIX'), 'DB_CUSTOM')->field('lc_abbreviation_name,lc_name')->where(array('lc_id' => $lc_id['lc_id']))->find();
					if (!empty($lt)) {
						$ary_data['shipping_type'] = $lt['lc_abbreviation_name'];
						$ary_data['shipping_type_name'] = $lt['lc_name'];
					}
				}
			}
			//是否是货到付款
			$cod_status = 0;
			if ($ary_data['o_payment'] == '6') {
				$cod_status = 1;
			}
			if (in_array('PayType', $fields)) {
				if ($ary_data['o_payment']) {
					$pay = M("payment_cfg", C('DB_PREFIX'), 'DB_CUSTOM')->field('pc_pay_type,pc_custom_name')->where(array('pc_id' => $ary_data['o_payment']))->find();
					if (!empty($lt)) {
						//结余款
						if($pay['pc_pay_type'] == 'deposit'){
							$ary_data['PayType'] = '000';
						}else{
// 							if($pay['pc_pay_type'] == 'cash_delivery'){
// 								$ary_data['PayType'] = 'cod';
// 							}else{
// 								$ary_data['PayType'] = $pay['pc_pay_type'];
// 							}
							$ary_data['PayType'] = $pay['pc_pay_type'];
						}
						$ary_data['PayTypeName'] = $pay['pc_custom_name'];
					}
				}
			}
			//如果收货地址为空重新查询
			if(empty($ary_data['o_receiver_city'])){
				if(!empty($ary_data['ra_id'])){
					$ary_address = D('CityRegion')->getReceivingAddress($ary_data ['m_id'],$ary_data['ra_id']);
					$address_info = explode(" ",$ary_address['address']);
					if(!empty($address_info)){
						$ary_data['o_receiver_state'] = $address_info[0];
						$ary_data['o_receiver_city'] = $address_info[1];
						$ary_data['o_receiver_county'] = $address_info[2];
					}
				}
			}
            //print_r($ary_data);exit;
			$trades["trade"] = array(
			    'tid' => $ary_data['o_id'],
                'buyer_email' => $ary_data['m_email'],
                'buyer_nick' => $ary_data['m_name'],
                'created' => $ary_data['o_create_time'],
                //'pay_time' => M('orders_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'ol_behavior'=>'支付成功'))->getField('ol_create'),
                //'discount_fee' => sprintf("%.2f", (sprintf("%.2f", $fl_pdt_total_final_price) + sprintf("%.2f", $ary_data['o_cost_freight'])) - (sprintf("%.2f", $price))), //让利+订单促销$ary_res['data'][''],     
				'modified' => $ary_data['o_update_time'],
                'num' => $num,
                'num_iid' => $num_iid,
                'cod_status' => $cod_status,
			//'pay_time'=>'',
			//'pic_path'=>'',
			//'point_fee'=>'',
			//'real_point_fee'=>'',
				'total_fee' => sprintf("%.2f", $final_price),
                'payment' => sprintf("%.2f", $ary_data['o_all_price']),
                'post_fee' => sprintf("%.2f", $ary_data['o_cost_freight']),
                'price' => $prices,
                'discount_fee'=>sprintf("%.2f", $ary_data['o_discount'])+sprintf("%.2f", $ary_data['o_coupon_menoy'])+sprintf("%.2f", $ary_data['o_bonus_money'])+sprintf("%.2f", $ary_data['o_cards_money'])+sprintf("%.2f", $ary_data['o_jlb_money'])+sprintf("%.2f", $ary_data['o_point_money']),   
                'discount_price'=>sprintf("%.2f", $ary_data['o_discount']),
				'received_payment' => sprintf("%.2f", $ary_data['o_pay']),
                'receiver_state' => $ary_data['o_receiver_state'],
				'receiver_city' => $ary_data['o_receiver_city'],
                'receiver_district' => $ary_data['o_receiver_county'],
				'receiver_address' => $ary_data['o_receiver_address'],
                'receiver_mobile' => strpos($ary_data['o_receiver_mobile'],':') ? decrypt($ary_data['o_receiver_mobile']) : $ary_data['o_receiver_mobile'],
                'receiver_name' => $ary_data['o_receiver_name'],
				'receiver_real_name' => $ary_data['receiver_real_name'],
                'receiver_id_card' => strpos($ary_data['o_receiver_idcard'],':') ? decrypt($ary_data['o_receiver_idcard']) : $ary_data['o_receiver_idcard'],
                'receiver_phone' => strpos($ary_data['o_receiver_telphone'],':') ? decrypt($ary_data['o_receiver_telphone']) : $ary_data['o_receiver_telphone'],
                'receiver_zip' => $ary_data['o_receiver_zipcode'],
                'shipping_type' => $ary_data['shipping_type'],
                'shipping_type_name' => $ary_data['shipping_type_name'],
                'PayType' => $ary_data['PayType'],
                'PayTypeName' => $ary_data['PayTypeName'],
                'sid' => $ary_data['o_id'],
                'buyer_message' => $ary_data['o_buyer_comments'],
 				'seller_memo' => str_replace('<br/>',';',trim($ary_data['o_seller_comments'],'<br />')),
                'freeze'=>$ary_data['o_status'] == 2 ? 1 :0,//是否延迟发货
                'unfreeze_time' => $ary_data['o_unfreeze_time'],
                'is_presell' => $ary_data['o_pre_sale'],
                'qc' => $ary_data['o_qc'],
                'is_coupon' => $ary_data['o_coupon'],
                'coupon_sn' => $ary_data['coupon_sn'],
                'coupon_value' => $ary_data['coupon_value'],
                'coupon_start_date' => $ary_data['coupon_start_date'],
                'coupon_end_date' => $ary_data['coupon_end_date'],
                'store_code' => $ary_data['erp_id'],
				'receiver_time'=>$ary_data['o_receiver_time'],
			//'invoice_name'=>$ary_data['invoice_head'],
                'invoice_name' => array(
                    'invoice_type' => ($ary_data['invoice_type'] == '2')?1:0,
                    'invoice_head' => ($ary_data['invoice_head'] == '2')?$ary_data['invoice_name']:$ary_data['invoice_people'],
                    'invoice_content' => $ary_data['invoice_content'],
					'company_name'=>$ary_data['invoice_name'],//公司名称
					'invoice_account'=>$ary_data['invoice_account'],//银行帐户
					'invoice_bank'=>$ary_data['invoice_bank'],//开户银行
					'invoice_phone'=>strpos($ary_data['invoice_phone'],':') ? decrypt($ary_data['invoice_phone']) : $ary_data['invoice_phone'],//注册电话
					'invoice_address'=>$ary_data['invoice_address'],//注册地址
					'invoice_identification_number'=>$ary_data['invoice_identification_number'],//纳税人识别号
					'invoice_people'=>$ary_data['invoice_people'],//个人姓名
			)
			//'invoice_type'=>$ary_data['invoice_type']
			//'type'=>'fixed',
			);
			if (preg_match("/[\x7f-\xff]/", $ary_data['o_receiver_time'])) { //判断字符串中是否有中文
				$trades["trade"]['buyer_message'] .=$trades["trade"]['buyer_message'].'<br />'.$trades["trade"]['receiver_time'];
				$trades["trade"]['receiver_time'] = '';
			}
			$ary_data['o_receiver_time'] = date('Y-m-d H:i:s',strtotime($ary_data['o_receiver_time']));
			if($ary_data['o_receiver_time'] == 'NaN-NaN-NaN'){
				$trades["trade"]['receiver_time'] = '';
			}
			if($ary_data['o_receiver_time'] == 'NaN-NaN-NaN 00:00'){
				$trades["trade"]['receiver_time'] = '';
			}			
			if((strstr($array_params['fields'],'pay_time'))){
				$trades["trade"]['pay_time'] = M('orders_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'ol_behavior'=>'支付成功'))->getField('ol_create');
			}
			if((!strstr($array_params['fields'],'discount_price'))){
				unset($trades["trade"]['discount_price']);
			}
			//第三方流水号
			if((strstr($array_params['fields'],'gateway_sn'))){
				$trades["trade"]['gateway_sn'] = M('payment_serial',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'ps_status'=>'1'))->getField('ps_gateway_sn');
			}			
			if ($ary_data['is_invoice'] == '0') {
				$trades["trade"]['invoice_name'] = array(
                    'invoice_type' => '',//发票类型 1-普通发票，2-增值税发票
                    'invoice_head' => '',//发票抬头 1-个人，2-单位
                    'invoice_content' => '',//发票内容
                    'company_name'=>'',//公司名称
					'invoice_account'=>'',//银行帐户
					'invoice_bank'=>'',//开户银行
					'invoice_phone'=>'',//注册电话
					'invoice_address'=>'',//注册地址
					'invoice_identification_number'=>'',//纳税人识别号
					'invoice_people'=>'',//个人姓名
                    );
			}
			$otherPayType = '';
			$otherPayTypeName = '';
			$otherPayTypeMoney = '';
			if ($ary_data['o_bonus_money'] > 0) {
				$otherPayType .='BONUS,';
				$otherPayTypeName .= '红包支付,';
				$otherPayTypeMoney .= $ary_data['o_bonus_money'].',';
			}
			/**
			if ($ary_data['o_cards_money'] > 0) {
				$otherPayType .='CARD,';
				$otherPayTypeName .= '储值卡支付,';
				$otherPayTypeMoney .= $ary_data['o_cards_money'].',';
			}
			if ($ary_data['o_jlb_money'] > 0) {
				$otherPayType .='JIULONGBI,';
				$otherPayTypeName .= '金币支付,';
				$otherPayTypeMoney .= $ary_data['o_jlb_money'].',';			
			}
			**/
			if ($ary_data['o_point_money'] > 0) {
				$otherPayType .='POINT,';
				$otherPayTypeName .= '积分抵扣金额支付,';
				$otherPayTypeMoney .= $ary_data['o_point_money'].',';	
			}
			if ($ary_data['o_coupon_menoy'] > 0) {
				$otherPayType .='COUPON,';
				$otherPayTypeName .= '优惠券支付,';
				$otherPayTypeMoney .= $ary_data['o_coupon_menoy'].',';				
			}
			$otherPayType = trim($otherPayType,',');
			$otherPayTypeName = trim($otherPayTypeName,',');
			$otherPayTypeMoney = trim($otherPayTypeMoney,',');
			if(!empty($otherPayTypeMoney)){
				if((strstr($array_params['fields'],'otherPayType'))){
					$trades["trade"]['otherPayType'] = $otherPayType;
				}
				if((strstr($array_params['fields'],'otherPayTypeName'))){
					$trades["trade"]['otherPayTypeName'] = $otherPayTypeName;
				}
				if((strstr($array_params['fields'],'otherPayType'))){
					$trades["trade"]['otherPayTypeMoney'] = $otherPayTypeMoney;	
				}						
			}
			//订单支付状态
			$ary_pay_status = array('o_pay_status' => $ary_data['o_pay_status']);
			$str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
			$str_pay_status = $str_pay_status;

			//订单的发货状态oi_ship_status
			$ary_orders_status = D("Orders")->getOrdersStatus($ary_data['o_id']);
			$deliver_status = $ary_orders_status['deliver_status'];
			if ($ary_data['o_pay_status'] == '0') {
				$trades["trade"]['status'] = 'WAIT_BUYER_PAY';
			}
			if ($str_pay_status == '已支付') {
				$trades["trade"]['status'] = 'WAIT_SELLER_SEND_GOODS';
			}
			if ($cod_status == '1') {
				$trades["trade"]['status'] = 'WAIT_SELLER_SEND_GOODS';
			}
			if ($deliver_status == '已发货') {
				$trades["trade"]['status'] = 'WAIT_BUYER_CONFIRM_GOODS';
			}
			if ($deliver_status == '已发货' && $ary_data['o_status'] == '4') {
				$trades["trade"]['status'] = 'TRADE_FINISHED';
			}
			if ($ary_data['o_status'] == '2') {
				$trades["trade"]['status'] = 'TRADE_CLOSED';
			}
			foreach ($trades["trade"] as $key => $trade) {
				if (!in_array($key, $fields)) {
					unset($trades["trade"][$key]);
				}
			}

			if (in_array('orders', $fields)) {
				foreach ($ary_data['item'] as $key => $item) {
					$items_info = array();
					$items_info = array(
					//'adjust_fee'=>'';
                        'num' => $item['oi_nums'],
                        'num_iid' => $item['g_id'],
                        'oid' => $item['o_id'],
                        'outer_id' => $item['g_sn'],
                        'outer_sku_id' => $item['pdt_sn'],
                        'payment' => $item['oi_price'] * $item['oi_nums'],
                        'pic_path' => $_SESSION['HOST_URL'] . $item['g_picture'],
                        'price' => $item['pdt_sale_price'],
						'cost_price'=>$item['pdt_cost_price'],
                        'total_fee' => $item['oi_price'] * $item['oi_nums'],
                        'sku_properties_name' => $item['pdt_memo'],
                        'shipping_type' => $ary_data['shipping_type'],
                        'sku_id' => $item['pdt_id'],
						'title' => $item['oi_g_name'],
                        'coupon_money' => $item['oi_coupon_menoy'],
						'bonus_money' => $item['oi_bonus_money'],
						'cards_money' => $item['oi_cards_money'],
						'jlb_money' => $item['oi_jlb_money'],
						'point_money' => $item['oi_point_money'],
						'promotion' => $item['promotion'],
						'promotion_price' => $item['promotion_price']
					);
					if($items_info['outer_id'] == $items_info['outer_sku_id']
						&& empty($items_info['sku_properties_name'])
					) {
						$items_info['outer_sku_id'] = '';
					}
					if ($items_info['pic_path']) {
						$items_info['pic_path'] = str_replace('//', '/', $items_info['pic_path']);
					}
					switch ($item['oi_refund_status']) {
						case 1:
							$items_info['refund_status'] = 'NO_REFUND';
							break;
						case 2:
							$items_info['refund_status'] = 'WAIT_SELLER_AGREE';
							break;
						case 3:
							$items_info['refund_status'] = 'WAIT_BUYER_RETURN_GOODS';
							break;
						case 4:
							$items_info['refund_status'] = 'SUCCESS';
							break;
						case 5:
							$items_info['refund_status'] = 'SUCCESS';
							break;
						case 6:
							$items_info['refund_status'] = 'SELLER_REFUSE_BUYER';
							break;
						default:
							$items_info['refund_status'] = 'NO_REFUND';
					}
					if ($ary_data['o_pay_status'] == '0') {
						$items_info['status'] = 'WAIT_BUYER_PAY';
					} else {
						$items_info['status'] = 'WAIT_SELLER_SEND_GOODS';
					}
					if ($ary_data['o_pay_status'] == '1' && $item['oi_ship_status'] == '2') {
						$items_info['status'] = 'WAIT_BUYER_CONFIRM_GOODS';
					}
					if ($ary_data['o_pay_status'] == '1' && $item['oi_ship_status'] == '2' && $ary_data['o_status'] == '4') {
						$trades["trade"]['status'] = 'TRADE_FINISHED';
					}
					if ($ary_data['o_status'] == '2') {
						$items_info['status'] = 'TRADE_CLOSED';
					}
					if ($cod_status == '1') {
						$items_info['status'] = 'WAIT_SELLER_SEND_GOODS';
					}
					//获取门店信息
					
					$goodsSpec = D('ApiGoodsSpec');
					$specInfo = $goodsSpec->getProductsSpecs($item['pdt_id'],$item['g_id']);
					$items_info['sku_properties_name'] = $specInfo['spec_name'];
					$trades["trade"]['orders']['order'][] = $items_info;
				}
			}
		}
		//dump($trades);die();
		return $trades;
	}
	/**
     * 获取商品门店
     * @author wangguibin
     * @date 2014-11-21
     */
    public function getCateParentShop($gid){
    	$condition = array();
    	$condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
    	$condition[C('DB_PREFIX').'goods_category.gc_type'] = 2;
		//实例化缓存
       // $memcaches = new Caches;
		//实例化缓存
		if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
			$memcaches = new Cacheds();
		}else{
			$memcaches = new Caches();
		}		
        //根据tag获取缓存key
        $cache_key = json_encode($condition).CI_SN.'getshop';
		if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
            return json_decode($ary_return,true);
        }else{
			$result=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
			->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
			->where($condition)
			->getField(C('DB_PREFIX').'goods_category.gc_name');		
            if($memcaches->getStat()){
                $memcaches->C()->set($cache_key, json_encode($result));
            }			
			return $result;
		}
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
		$ary_where = array('fx_orders.o_id' => $o_id);
		$ary_delivery = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')
		->join("fx_orders_items on(fx_orders.o_id=fx_orders_items.o_id) ")
		->where($ary_where)->field(array('oi_ship_status', 'oi_refund_status', 'fx_orders.o_id', 'oi_nums'))->select();
		if (!empty($ary_delivery) && is_array($ary_delivery)) {
			$ary_refunds = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->field(array('or_refund_type', 'or_processing_status'))->where(array('o_id'=>$o_id))->select();
			$int_total = 0;
			$int_deliver_num = 0; //发货成功
			foreach ($ary_delivery as $k => $v) {
				$int_total += $v['oi_nums'];
				if ($v['oi_ship_status'] == 2) {
					$int_deliver_num++;
				}
			}

			if (is_array($ary_refunds) && count($ary_refunds) > 0) {
				if (isset($ary_refunds[0]) && $ary_refunds[0]['or_refund_type'] == 1) {
					switch ($ary_refunds[0]['or_processing_status']) {
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
				} elseif (isset($ary_refunds[0]) && $ary_refunds[0]['or_refund_type'] == 2) {
					$int_refund_goods = 0; //退货成功
					$int_refund_goods_ing = 0; //退货中*/
					$int_reject = 0;
					$ary_refunds_items = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->field(array('or_refund_type', 'or_processing_status'))->where(array('o_id'=>$o_id))->select();
					foreach ($ary_refunds_items as $val) {
						if ($val['or_processing_status'] == 0)
						$int_refund_goods_ing++;
						elseif ($val['or_processing_status'] == 1)
						$int_refund_goods++;
						elseif ($val['or_processing_status'] == 2)
						$int_reject++;
					}
					if ($int_refund_goods > 0) {
						$str_status['refund_goods_status'] = '退货成功';
					}
					if ($int_refund_goods_ing > 0) {
						$str_status['refund_goods_status'] = '退货中';
					}
					if ($int_reject > 0) {
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
	 * 获取订单信息
	 * @param int $oid 订单ID
	 * orders.status',////交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
	 * @author wangguibin<wanghui@guanyisoft.com>
	 * @date 2013-06-01
	 */
	public function getLocalOrdersItem($oid = '',$fields,$o_audit,$erp_id) {
		$ary_res = array('success' => '0', 'msg' => '获取订单明细失败', 'errCode' => '1000', 'data' => array());
		$where = array();
		$order = M("orders", C('DB_PREFIX'), 'DB_CUSTOM');

		if (!empty($oid) && intval($oid)) {
			$where['fx_orders.o_id'] = $oid;
			//订单审核条件搜索
			if($o_audit == '1' || $o_audit == '0'){
				$where['fx_orders.o_audit'] = $o_audit;
			}
			if(!empty($erp_id)){
				$where['fx_orders.erp_id'] = $erp_id;
			}
			//$where['items.oi_refund_status'] = 1;
			//过滤掉已发货订单
			//$where['items.oi_ship_status'] = array('neq',2);
			$ary_res['data'] = $order->field('fx_orders.*,fx_payment_cfg.erp_payment_id,fx_members.m_email,fx_members.m_name,fx_members.m_id,fx_members.m_email')
			->join(' fx_payment_cfg on fx_payment_cfg.pc_id=fx_orders.o_payment')
			->join(' fx_members on fx_members.m_id=fx_orders.m_id')
			->join('fx_orders_items as items on(fx_orders.o_id=items.o_id)')
			->where($where)->find();
			if (!empty($ary_res['data'])) {
				$ary_res['msg'] = '获取成功';
				$ary_res['success'] = '1';
			}
            if(is_array($ary_res['data']) && count($ary_res['data'])) {
                if($ary_res['data']['o_receiver_mobile']
                    && strpos($ary_res['data']['o_receiver_mobile'], ':')){
                    $ary_res['data']['o_receiver_mobile'] = decrypt($ary_res['data']['o_receiver_mobile']);
                }
                if($ary_res['data']['o_receiver_telphone']
                    && strpos($ary_res['data']['o_receiver_telphone'], ':')){
                    $ary_res['data']['o_receiver_telphone'] = decrypt($ary_res['data']['o_receiver_telphone']);
                }
            }

			if (in_array('orders', $fields)) {
				if (!empty($ary_res['data']) && is_array($ary_res['data'])) {
					$ary_res['data']['item'] = M("orders_items", C('DB_PREFIX'), 'DB_CUSTOM')
					->field('fx_orders_items.oi_single_allowance,fx_orders_items.pdt_id,fx_orders_items.oi_ship_status,fx_orders_items.oi_refund_status,fx_orders_items.oi_id,fx_orders_items.o_id,fx_orders_items.g_id,fx_orders_items.oi_g_name,fx_goods_info.g_picture,fx_goods_products.g_sn,fx_goods_products.pdt_sn,fx_orders_items.oi_price,fx_orders_items.oi_nums,fx_orders_items.oi_coupon_menoy,fx_orders_items.oi_bonus_money,fx_orders_items.oi_cards_money,fx_orders_items.oi_jlb_money,fx_orders_items.oi_point_money,fx_orders_items.promotion,fx_orders_items.promotion_price,fx_goods_products.pdt_sale_price,fx_goods_products.pdt_cost_price,fx_goods_products.pdt_memo')
					->join(" fx_goods_products on fx_orders_items.pdt_id=fx_goods_products.pdt_id")
					->join(" fx_goods_info on fx_orders_items.g_id=fx_goods_info.g_id")
					->where(array('fx_orders_items.o_id' => $oid))
					->select();
					if (!empty($ary_res['data']['item']) && is_array($ary_res['data']['item'])) {
						$ary_res['msg'] = '获取成功';
						$ary_res['success'] = '1';
					} else {
						$ary_res['msg'] = '获取失败';
						$ary_res['success'] = '0';
						$ary_res['errCode'] = '1002';
					}
				} else {
					$ary_res['msg'] = '商品已被删除或已不存在';
					$ary_res['errCode'] = '1001';
				}
			}
		} else {
			//用于批量同步
			$ary_res['data'] = $order->where($where)->select();
		}
		//dump($ary_res);die();
		return $ary_res;
	}

	/**
	 * 查询卖家已卖出的增量交易数据（根据修改时间
	 * detail:
	 * request params
	 * @start_modified 	Date 	必须 	2000-01-01 00:00:00 		查询修改开始时间(修改时间跨度不能大于一天)。格式:yyyy-MM-dd HH:mm:ss
	 * @end_modified 	Date 	必须 	2000-01-02 00:00:00 		查询修改结束时间，必须大于修改开始时间(修改时间跨度不能大于一天)，格式:yyyy-MM-dd HH:mm:ss。建议使用30分钟以内的时间跨度，能大大提高响应速度和成功率。
	 * @status 	String 	可选 	TRADE_NO_CREATE_PAY 		交易状态，默认查询所有交易状态的数据，除了默认值外每次只能查询一种状态。 可选值 TRADE_NO_CREATE_PAY(没有创建支付宝交易) WAIT_BUYER_PAY(等待买家付款) SELLER_CONSIGNED_PART（卖家部分发货） WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) TRADE_BUYER_SIGNED(买家已签收,货到付款专用) TRADE_FINISHED(交易成功) TRADE_CLOSED(交易关闭) TRADE_CLOSED_BY_TAOBAO(交易被淘宝关闭) ALL_WAIT_PAY(包含：WAIT_BUYER_PAY、TRADE_NO_CREATE_PAY) ALL_CLOSED(包含：TRADE_CLOSED、TRADE_CLOSED_BY_TAOBAO)
	 * @type 	String 	可选 	fixed 		交易类型列表，同时查询多种交易类型可用逗号分隔。默认同时查询guarantee_trade, auto_delivery, ec, cod,step的5种交易类型的数据；查询所有交易类型的数据，需要设置下面全部可选值。 可选值： fixed(一口价) auction(拍卖) step（分阶段付款，万人团，阶梯团订单） guarantee_trade(一口价、拍卖) independent_simple_trade(旺店入门版交易) independent_shop_trade(旺店标准版交易) auto_delivery(自动发货) ec(直冲) cod(货到付款) fenxiao(分销) game_equipment(游戏装备) shopex_trade(ShopEX交易) netcn_trade(万网交易) external_trade(统一外部交易) instant_trade (即时到账) b2c_cod(大商家货到付款) hotel_trade(酒店类型交易) super_market_trade(商超交易), super_market_cod_trade(商超货到付款交易) taohua(桃花网交易类型） waimai(外卖交易类型） nopaid（即时到帐/趣味猜交易类型） eticket(电子凭证) ; 注：guarantee_trade是一个组合查询条件，并不是一种交易类型，获取批量或单个订单中不会返回此种类型的订单。
	 * @page_no 	Number 	可选 	1 		页码。取值范围:大于零的整数;默认值:1。注：必须采用倒序的分页方式（从最后一页往回取）才能避免漏单问题。
	 * @page_size 	Number 	可选 	40 		每页条数。取值范围：1~100，默认值：40。建议使用40~50，可以提高成功率，减少超时数量。
	 * @use_has_next 	Boolean 	可选 	true 	false 	是否启用has_next的分页方式，如果指定true,则返回的结果中不包含总记录数，但是会新增一个是否存在下一页的的字段，通过此种方式获取增量交易，效率在原有的基础上有80%的提升。
	 * @field Field List 必须
	 * 需要返回的字段。目前支持有： 1.Trade中可以指定返回的fields:seller_nick, buyer_nick, title, type, created, tid, seller_rate,seller_can_rate, buyer_rate,can_rate,status, payment, discount_fee, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment,pic_path, num_iid, num, price, cod_fee, cod_status, shipping_type, receiver_name, receiver_state, receiver_city, receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,alipay_id,alipay_no,is_lgtype,is_force_wlb,is_brand_sale,has_buyer_message,credit_card_fee,step_trade_status,step_paid_fee,mark_desc,send_time,,has_yfx,yfx_fee,yfx_id,yfx_type,trade_source,seller_flag 2.Order中可以指定返回fields： orders.title, orders.pic_path, orders.price, orders.num, orders.num_iid, orders.sku_id, orders.refund_status, orders.status, orders.oid, orders.total_fee, orders.payment, orders.discount_fee, orders.adjust_fee, orders.sku_properties_name, orders.item_meal_name, orders.buyer_rate, orders.seller_rate, orders.outer_iid, orders.outer_sku_id, orders.refund_id, orders.seller_type，orders.end_time, orders.order_from,orders.consign_time,orders.shipping_type,orders.logistics_company,orders.invice_no 3.fields：orders（返回Order的所有内容） 4.fields:service_orders(返回service_order中所有内容)
	 * $fileds = array(
	 //'seller_nick',//卖家昵称
	 'buyer_nick',//买家昵称    m_name
	 'title',//交易标题，以店铺名作为此标题的值。注:taobao.trades.get接口返回的Trade中的title是商品名称
	 'type',//交易类型列表 分销默认fixed
	 'created',//交易创建时间。格式:yyyy-MM-dd HH:mm:ss o_create_time
	 'sid',//交易编号o_id
	 'tid',//交易编号o_id
	 'status',//交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
	 'payment',//实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分 o_pay
	 'discount_fee',//订单优惠金额 o_discount
	 //'promotion',//交易促销详细信息
	 'adjust_fee',//手工调整金额.格式为:1.01;单位:元;精确到小数点后两位.
	 'post_fee',//邮费。o_cost_freight
	 'total_fee',//商品金额 o_goods_all_price
	 'pay_time',//付款时间。格式:yyyy-MM-dd HH:mm:ss ps_update_time
	 'end_time',//交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
	 'modified',//交易修改时间。格式:yyyy-MM-dd HH:mm:ss   o_update_time
	 'consign_time',//物流发货时间。格式:yyyy-MM-dd HH:mm:ss
	 'buyer_obtain_point_fee',//买家获得积分,返点的积分。格式:100;单位:个 o_reward_point
	 'point_fee',//买家使用积分。格式:100;单位:个.o_freeze_point
	 'real_point_fee',//买家实际使用积分（扣除部分退款使用的积分）。格式:100;单位:个
	 'received_payment',//卖家实际收到的支付宝打款金额 o_pay-coupon_value
	 //'buyer_memo',//买家备注
	 'commission_fee',//交易佣金。精确到2位小数;单位:元。如:200.07，表示:200元7分
	 'pic_path',
	 'num_iid',//商品数字编号
	 'num',//商品总数量
	 'price',//商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
	 'shipping_type',//创建交易时的物流方式（交易完成前，物流方式有可能改变，但系统里的这个字段一直不变）。可选值：ems, express, post, free, virtual。
	 'trade_memo',//交易备注，通过taobao.trade.add接口创建
	 'orders',
	 'orders.title',//商品名称 oi_g_name
	 'orders.pic_path',//商品图片
	 'orders.price',//购买单价（单件商品成交价） oi_price
	 'orders.num',//商品数量 oi_nums
	 'orders.num_iid',//商品id g_id
	 'orders.sku_id',//货品id pdt_id
	 'orders.refund_status',//oi_refund_status1：正常订单，2:退款中，3退货中,4:退款成功,5退货成功，6：被驳回
	 'orders.status',//
	 'orders.oid',//订单详情id oi_id
	 'orders.total_fee',//
	 'orders.payment',
	 'orders.discount_fee',
	 'orders.adjust_fee',
	 'orders.sku_properties_name'
	 );
	 * reponse params
	 * @total_results 	Number 	否 	100 	搜索到的交易信息总数
	 * @trades 	Trade [] 	是 		搜索到的交易信息列表，返回的Trade和Order中包含的具体信息为入参fields请求的字段信息
	 * @has_next 	Boolean 	否 	true 	是否存在下一页
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-06-01
	 */
	public function tradesSoldIncrementGet($array_params) {
		//时间排序排序
		if (!empty($array_params['end_modified']) && !empty($array_params['start_modified'])) {
			$ary_where['o_update_time'] = array(between, array($array_params['start_modified'], $array_params['end_modified']));
		}
		//订单审核状态
		if($array_params['order_status'] == '0' || $array_params['order_status'] == '1'){
			$ary_where['o_audit'] = $array_params['order_status'];
		}
		if(!empty($array_params['erp_id'])){
			$ary_where['fx_orders.erp_id'] = $array_params['erp_id'];
		}
		$status = $array_params['status'];
		switch ($status) {
			case 'WAIT_BUYER_PAY':
				$ary_where['o_pay_status'] = '0';
				break;
			case 'WAIT_SELLER_SEND_GOODS':
				$ary_where['o_status'] = '1';
				$ary_where['items.oi_ship_status'] = array('neq',2);
				//$ary_where['items.oi_refund_status'] = 1;
				$pc_id = M("payment_cfg", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pc_pay_type' => 'cash_delivery'))->getField('pc_id');
				if(isset($pc_id)){
					$ary_where['_string'] = '(o_pay_status = 1 ) or (o_pay_status=0 and o_payment='.$pc_id.')';
				}else{
					$ary_where['_string'] = ' o_pay_status = 1 and o_status=1';
				}
				break;
			case 'TRADE_CLOSED':
				$ary_where['o_status'] = '2';
				break;
			case 'TRADE_FINISHED':
				$ary_where['o_status'] = '4';
				break;
			default:
				$ary_where['o_status'] = '1';
				$ary_where['items.oi_ship_status'] = array('neq',2);
				//$ary_where['items.oi_refund_status'] = 1;
				$pc_id = M("payment_cfg", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pc_pay_type' => 'cash_delivery'))->getField('pc_id');
				if(isset($pc_id)){
					$ary_where['_string'] = '(o_pay_status = 1 ) or (o_pay_status=0 and o_payment='.$pc_id.')';
				}else{
					$ary_where['_string'] = ' o_pay_status = 1 and o_status=1';
				}
		}
		$limit['pagesize'] = empty($array_params['page_size']) ? '20' : $array_params['page_size'];
		$limit['start'] = empty($array_params['page_no']) ? '1' : $array_params['page_no'];
		$tids = $this->GetOrderPayStatus($ary_where, $limit);
//        writeLog(" debug 2: " .var_export($tids, true), 'erpapi.log');
		$trades = array();
		if (!empty($tids['count'])) {
			$trades['total_results'] = $tids['count'];
			foreach ($tids['data'] as $tid) {
				$trade_info = $this->tradeFullinfoGet(array('tid' => $tid['o_id'], 'fields' => $array_params['fields']));
				$trades['trades']['trade'][] = $trade_info['trade'];
			}
		}
		return $trades;
	}

	/**
	 * 获得一段时间内订单
	 * @return ary 返回订单信息
	 * @author wangguibin
	 * @date 2013-06-01
	 */
	public function GetOrderPayStatus($where, $limit) {
		$ary_trade = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')
		->join('fx_orders_items as items on(fx_orders.o_id=items.o_id)')
		->field('distinct(fx_orders.o_id)')->where($where)
		->limit(($limit['start'] - 1) * $limit['pagesize'], $limit['pagesize'])
		->select();
		$count = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')
		->join('fx_orders_items as items on(fx_orders.o_id=items.o_id)')
		->field('fx_orders.o_id')->where($where)->count('distinct(fx_orders.o_id)');
		return array('count' => $count, 'data' => $ary_trade);
	}

	/**
	 * 订单状态（付款，退货，发货状态）
	 * @param   arry $orders 订单数组 一维数组 key字段名=> 值
	 * @param string $mark 标识名称，付款->o_pay_status,退货/退款->oi_refund_status
	 * 发货-> oi_ship_status  订单状态 ->o_status
	 * @author listen
	 * @return string 状态文字。
	 * @date 2013-01-07
	 */
	public function getOrderItmesStauts($mark, $orders = array()) {
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
			}
		} else if (isset($mark) && $mark == 'oi_refund_status') {
			$ary_where = array('fx_orders_refunds_items.o_id' => $orders['o_id'],
                'fx_orders_refunds_items.oi_id' => $orders['oi_id']
			);
			$ary_refunds = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')
			->join('fx_orders_refunds ON fx_orders_refunds_items.or_id = fx_orders_refunds.or_id')
			->field(array('ori_num', 'or_processing_status', 'or_refund_type'))
			->where($ary_where)->select();
			//统计实际已退货或者
			$oi_refund_status = '';
			$int_refund_img = 0; //退货中数量
			$int_refund = 0; //退货成功数量
			$int_refund_reject = 0; //退货驳回数量
			$int_orderitems = intval($orders['oi_nums']);
			if ($ary_refunds) {
				foreach ($ary_refunds as $val) {
					if ($val['or_refund_type'] == 1) {
						//退款
						if ($val['or_processing_status'] == 0) {
							$oi_refund_status = 2;
							break;
						} elseif ($val['or_processing_status'] == 1) {
							$oi_refund_status = 4;
							break;
						} elseif ($val['or_processing_status'] == 2) {
							$oi_refund_status = 6;
							break;
						}
					} else {
						//退货
						if ($val['or_processing_status'] == 0) {
							$int_refund_img += $val['ori_num'];
						} elseif ($val['or_processing_status'] == 1) {
							$int_refund += $val['ori_num']; //退货成功数量
						} elseif ($val['or_processing_status'] == 2) {
							$int_refund_reject += $val['ori_num'];
						}
					}
				}

				if ($int_refund == $int_orderitems) {
					$oi_refund_status = 5;
				} elseif ($int_refund < $int_orderitems && $int_refund > 0) {
					$oi_refund_status = 7;
				} elseif ($int_refund == 0 && $int_refund_img > 0) {
					if ($int_refund_img == $int_orderitems) {
						$oi_refund_status = 3;
					} elseif ($int_refund_img < $int_orderitems) {
						$oi_refund_status = 8;
					}
				} elseif ($int_refund == 0 && $int_refund_reject > 0) {
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
    
    //订单更新
    public function orderUpdate($array_params) {
               $flag = $this->_validate($array_params,$msg);
               $array_params['ra_id'] = $msg['extra_data']['ra_id'];
               if(array_key_exists('total_discount_fee',$array_params) && is_numeric($array_params['total_discount_fee']) && !empty($array_params['total_discount_fee'])) {
                   if($array_params['total_discount_fee']>=$msg['extra_data']['o_all_price']) $array_params['o_all_price'] = 0;
                   else $array_params['o_all_price'] = $msg['extra_data']['o_all_price'] - sprintf('%.3f',$array_params['total_discount_fee']);
                   unset($array_params['total_discount_fee']);
               }
              
               if(isset($array_params['freeze']) && $array_params['freeze']==1) {
                   $array_params['o_status'] = 2;
               }
              
               $save_data = $this->getAddThdField($array_params,'UPDATE');
               if(!empty( $save_data['o_receiver_mobile'])){
                   $save_data['o_receiver_mobile'] = encrypt($save_data['o_receiver_mobile']);
               }
                if(!empty( $save_data['o_receiver_telphone'])){
                    $save_data['o_receiver_telphone'] = encrypt($save_data['o_receiver_telphone']);
                }

			   $this->orders->startTrans();
               $res = $this->orders->where(array('o_id' => $array_params['outer_tid']))
                    ->data($save_data)
                    ->save();
                  
				     if(false !== $res){
				        
				                  if(isset($msg['data']) && !empty($msg['data'])) {
                                        foreach($msg['data'] as $val) {
                                          
                                            if(isset($val['type']) && !empty($val['type']))
                                            switch ($val['type']) {
                                                case 'insert': 
                                                    $int_return_refunds_itmes = $this->orders_items->data($val['data'])->add();
                                                    break;
                                                case 'update': 
                                                    $oi_id = $val['data']['oi_id'] ? $val['data']['oi_id'] : 0;
                                                    if(isset($val['data']['oi_id']))  unset($val['data']['oi_id']);
                                                    $res = $this->orders_items->where(array('oi_id' => $oi_id))
                                                            ->data($val['data'])
                                                            ->save();
                                                    break;
                                            }
                                        }
                                    }
                                    //写订单日志
                                    $int_return_status = $this->writeOrderLog($array_params['outer_tid']);
                                    if(false !==$int_return_status) {
                                            $this->orders->commit();
                                            $flag = true;
                                    }
                                    else {
                                        $this->orders->rollback();
                                        $flag = false;
					                    $data = array('msg'=>'写订单日志出现异常');
                                    }
                     }
					 else{
					     $this->orders->rollback();
                         $flag = false;
					     $data = array('msg'=>'更新订单信息出现异常');
                     }
				     
                    if($flag) {
		                  return $this->getUpdateOrderResponse($array_params['outer_tid'],'UPDATE');
		              }
		              else return $data;
           
    }
    
    /*记录订单日志*/
    private function writeOrderLog($o_id) {
        //订单日志记录
           $ary_orders_log = array(
                'o_id' => $o_id,
                'ol_behavior' => 'ERP调用接口修改',
                'ol_uname' => 'ERP接口调用方',
                'ol_create' => date('Y-m-d H:i:s')
			);
			$res_orders_log = D('OrdersLog')->add($ary_orders_log);
			if (!$res_orders_log) {
				return false;
			}
            else return true;
    }
    
    
    /*验证会员和订单信息*/
    private function _validate($array_params,&$message = array()) {
            $ary_items = array();
            
            $condition['m_name'] = trim($array_params['buyer_nick']);
            
            $member_lc = D('Members')->field('m_id')->where($condition)->find();
           
            if(isset($member_lc['m_id']) && !empty($member_lc['m_id'])) {
               
                   $ary_where = array(
                                'o_id' =>trim($array_params['outer_tid']),
                                'm_id' =>$member_lc['m_id'],
                              );
                   $order_lc = $this->orders->field('o_id,o_all_price')->where($ary_where)->limit(1)->find();
                
                if(isset($order_lc['o_id']) && !empty($order_lc['o_id'])) {
                     
                       if(isset($array_params['receiver_district'])) {
                           
                            $cityRegion_lc = D('CityRegion')->field('cr_id')
                                                                     ->where(array('cr_name'=>$array_params['receiver_district']))
                                                                     ->find();
                            if(isset($cityRegion_lc['cr_id']) && !empty($cityRegion_lc['cr_id'])) {
                                     $receiveAddress_lc = D('ReceiveAddress')->field('ra_id')
                                                                     ->where(array('m_id'=>$member_lc['m_id'],'cr_id'=>$cityRegion_lc['cr_id']))
                                                                     ->find();
                           
                                    if(!isset($receiveAddress_lc['ra_id']) || empty($receiveAddress_lc['ra_id'])) {
                                         $message['msg'] = "此会员的收货地址{$array_params['receiver_district']}系统不存在";
                                         return false;
                                    }
                             }
                           
                       }
                       
                       
                       //订单详情
                        $ary_orders_items = $this->orders_items->field('oi_id,o_id,pdt_id,oi_price,oi_nums,g_sn')->where(array('o_id'=>$order_lc['o_id']))->select();
                        $ary_temp_items = array();
                        $ary_itemsns = explode(',',$array_params['itemsns']);
                        $ary_prices = explode(',',$array_params['prices']);
                        $ary_nums = explode(',',$array_params['nums']);
                        $ary_combo_types = explode(',',$array_params['combo_types']);
                        $count = count(array_unique(array(count($ary_itemsns),count($ary_prices),count($ary_nums),count($ary_combo_types))));
                         
                        if($count>1) {
                            $message['msg'] = '商品信息对应的个数不一致';
                            return false;
                        }
                        if(array_key_exists('skusns',$array_params)) {
                            $ary_skusns = explode(',',$array_params['skusns']);
                            if(count($ary_itemsns) != count($ary_skusns)) {
                                $message['msg'] = '商品编号和商品规格编号对应的个数不一致';
                                return false;
                            }
                        }
                        
                        if(array_key_exists('pay_moneys',$array_params)) {
                            $ary_pay_moneys = explode(',',$array_params['pay_moneys']);
                            if(count($ary_itemsns) != count($ary_pay_moneys)) {
                                $message['msg'] = '商品编号和商品支付金额对应的个数不一致';
                                return false;
                            }
                        }
                        if(array_key_exists('skusns',$array_params)) {
                            
		                    foreach($ary_orders_items as $val){
			                   $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_id'] = $val['oi_id'];
                               $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_nums'] = intval($val['oi_nums']);
                               $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_price'] = $val['oi_price'];
                               $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_type'] = $val['oi_type'];
                              
                            }
                            
                            $ary_skusns = explode(',',$array_params['skusns']);
                            
                            foreach($ary_itemsns as $key=>$val) {
                                if(isset($ary_temp_items[$val][$ary_skusns[$key]])) {
                                    if(intval($ary_nums[$key] != $ary_temp_items[$val][$ary_skusns[$key]]['oi_nums'])) {  
                                       //已有商品数量要是数量与接口传过来的数量不相等，更新此数据
                                    
                                        $ary_arr =  array(
                                                         'oi_id'=>$ary_temp_items[$val][$ary_skusns[$key]]['oi_id'],
                                                         'oi_nums'=>intval($ary_nums[$key]),
                                                         'oi_type'=> $ary_combo_types[$key] == 1 ? 3 : 0,//是否组合商品
                                                         'pdt_sale_price'=>sprintf('%.3f',$ary_prices[$key]),
                                                         
                                                         );
                                         $ary_items[] = array_key_exists('pay_moneys',$array_params) ? array('data'=>array_merge($ary_arr,array('oi_price'=>sprintf('%.3f',$ary_pay_moneys[$key]))),'type'=>'update') : array('data'=>$ary_arr,'type'=>'update');
                                         
                                    }
                                   
                                }
                                else {
                                         $ary_goods = D('Goods')->field('g_id,gt_id')->where(array('g_sn'=>$val))->limit(1)->find();
                                         
                                         if(empty($ary_goods)) {
                                            $message['msg'] = "商品编号是{$val}的商品不存在";
                                            return false;
                                         }
                                         else {
                                            $g_id = $ary_goods['g_id'];
                                            $gt_id = $ary_goods['gt_id'];
                                         }
                                         
                                         $ary_goodsInfo = D('GoodsInfo')->field('g_name')->where(array('g_id'=>$g_id))->limit(1)->find();
                                         
                                         if(empty($ary_goodsInfo)) {
                                            $message['msg'] = "商品编号是{$val}的商品不存在";
                                            return false;
                                         }
                                         else {
                                            $g_name = $ary_goodsInfo['g_name'];
                                            
                                         }
                                         $ary_products = D('GoodsProducts')->field('pdt_id,g_price')->where(array('pdt_sn'=>$ary_skusns[$key]))->limit(1)->find();
                                         if(empty($ary_products)) {
                                            $message['msg'] = "规格编号是{$ary_skusns[$key]}的商品不存在";
                                            return false;
                                         }
                                         else {
                                            $pdt_id = $ary_products['pdt_id'];
                                            $g_price = $ary_products['g_price'];
                                         }
                                        
                                         
                                         //插入
                                         $ary_arr =  array(
                                                         
                                                         
                                                         'o_id'=>$order_lc['o_id'],
                                                         'g_id'=>$g_id,
                                                         'pdt_id'=>$pdt_id,
                                                         'gt_id'=>$gt_id,
                                                         'g_sn'=>$val,
                                                         'oi_g_name'=>$g_name,
                                                         'oi_price'=> $g_price,
                                                         'pdt_sn'=>$ary_skusns[$key],
                                                         'oi_nums'=>intval($ary_nums[$key]),
                                                         'oi_type'=> $ary_combo_types[$key] == 1 ? 3 : 0,
                                                         'pdt_sale_price'=>sprintf('%.3f',$ary_prices[$key]),
                                                         'oi_create_time'=>date('Y-m-d H:i:s')
                                                         );
                                                         
                                         $ary_items[] = array_key_exists('pay_moneys',$array_params) ? array('data'=>array_merge($ary_arr,array('oi_price'=>sprintf('%.3f',$ary_pay_moneys[$key]))),'type'=>'insert') : array('data'=>$ary_arr,'type'=>'insert');
                                   
                                }
                            }
                        }
                        else {
                            
                            
                            foreach($ary_orders_items as $val){
			                   $ary_temp_items[$val['g_sn']]['oi_id'] = $val['oi_id'];
                               $ary_temp_items[$val['g_sn']]['oi_nums'] = $val['oi_nums'];
                               $ary_temp_items[$val['g_sn']]['oi_price'] = $val['oi_price'];
                               $ary_temp_items[$val['g_sn']]['oi_type'] = $val['oi_type'];
		                    }
                          // $ary_skusns = explode(',',$array_params['skusns']);
                            
                            foreach($ary_itemsns as $key=>$val) {
                                if(isset($ary_temp_items[$val])) {
                                    //更新
                                     if(intval($ary_nums[$key] != $ary_temp_items[$val]['oi_nums'])){
                                             $ary_arr = array(
                                                                'oi_id'=>$ary_temp_items[$val]['oi_id'],
                                                                'oi_nums'=>$ary_nums[$key],
                                                                'oi_type'=> $ary_combo_types[$key] == 1 ? 3 : 0,
                                                                'pdt_sale_price'=>sprintf('%.3f',$ary_prices[$key]),
                                                             );
                                             $ary_items[] = array_key_exists('pay_moneys',$array_params) ? array('data'=>array_merge($ary_arr,array('oi_price'=>sprintf('%.3f',$ary_pay_moneys[$key]))),'type'=>'update') : array('data'=>$ary_arr,'type'=>'update');
                                     }
                                      
                                }
                                else {
                                    //插入
                                    $ary_goods = D('Goods')->field('g_id,gt_id')->where(array('g_sn'=>$val))->limit(1)->find();
                                         if(empty($ary_goods)) {
                                            $message['msg'] = "商品编号是{$val}的商品不存在";
                                            return false;
                                         }
                                         else {
                                            $g_id = $ary_goods['g_id'];
                                            $gt_id = $ary_goods['gt_id'];
                                         }
                                         $ary_goodsInfo = D('GoodsInfo')->field('g_name,g_price')->where(array('g_id'=>$g_id))->limit(1)->find();
                                         
                                         if(empty($ary_goodsInfo)) {
                                            $message['msg'] = "商品编号是{$val}的商品不存在";
                                            return false;
                                         }
                                         else {
                                            $g_name = $ary_goodsInfo['g_name'];
                                            $g_price = $ary_goodsInfo['g_price'];
                                         }
                                 
                                            $ary_arr = array(
                                                              
                                                                'o_id'=>$order_lc['o_id'],
                                                                'g_id'=>$g_id,
                                                                'gt_id'=>$gt_id,
                                                                'g_sn'=>$val,
                                                                'oi_g_name'=>$g_name,
                                                                'oi_price'=> $g_price,
                                                                'oi_nums'=>$ary_nums[$key],
                                                                'oi_type'=> $ary_combo_types[$key] == 1 ? 3 : 0,
                                                                'pdt_sale_price'=>sprintf('%.3f',$ary_prices[$key]),
                                                                'oi_create_time'=>date('Y-m-d H:i:s')
                                                             );
                                              $ary_items[] = array_key_exists('pay_moneys',$array_params) ? array('data'=>array_merge($ary_arr,array('oi_price'=>sprintf('%.3f',$ary_pay_moneys[$key]))),'type'=>'insert') : array('data'=>$ary_arr,'type'=>'insert');
                                }
                            }
                            
                        }
                    
                    
                }
                else {
                    $message['msg'] = '参数订单号和会员代码对应系统订单不存在';
                    return false;
                }
            }
            else {
                $message['msg'] = '此会员不存在';
                return false;
            }
            $message['data'] = $ary_items;
            $message['extra_data'] = array('m_id'=>$member_lc['m_id'],'ra_id'=>$receiveAddress_lc['ra_id'],'o_all_price'=>$order_lc['o_all_price']);
          
            return true;
    }
    
    /**
	 * 订单数据映射获取
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-02
	 */
	private function getAddThdField($array_client_fields = array(),$type = 'UPDATE'){
		switch ($type) {
               /* case 'ADD':
                    return $this->parseFields($this->ary_add_field,$array_client_fields);
                    break;*/
                case 'UPDATE':
                    return $this->parseFields($this->ary_update_field,$array_client_fields);
                    break;
                           } 
    }
    
    /**
	 * 处理字段映射
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-02
     * return string
     * return array
	 */
	private function parseFields($array_table_fields,$array_client_fields){
		$aray_fetch_field = array();
		foreach($array_client_fields as $field_name => $as_name){
			if(isset($array_table_fields[$field_name]) && !empty($as_name)){
				$aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);
                
			}
		}
		if(empty($aray_fetch_field)){
			return null;
		}
		return $aray_fetch_field;
	}
    
    
    
    /**
      * 组装返回订单数据
      * @author chenzongyao@guanyisoft.com
      * @date 2013-08-01
     */
    private function getUpdateOrderResponse($or_return_sn,$operate_type) {
	    $str = 'o_id,o_create_time,o_update_time';       
		$ary_response = $this->orders->field($str)->where(array('o_id' =>$or_return_sn))->find();
	   
		$ary_inventory_response = array();
		if(!empty($ary_response)) {
		   switch ($operate_type) {
		      case 'ADD':
                  $ary_inventory_response = array('created'=>$ary_response['o_create_time'],'tid'=>$ary_response['o_id']);
                  break;
              case 'UPDATE':
              $ary_inventory_response = array('created'=>$ary_response['o_update_time'],'tid'=>$ary_response['o_id']);
               break;
         } 
		  
          
		  return array('msg'=>'','data'=>$ary_inventory_response);
		}
		else return array('msg'=>'返回订单信息出现异常');
		
	}

    /**
     * 获取退款/退货理由
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-02-10
     */
    public function getOrdersReason($int_o_refund_type){
        $result = array(
            'sub_msg' => '订单接口失败',
            'status' => false,
            'info' => array()
        );
        // $str_reason =  D('OrdersRefunds')/*->field($str)*/->where(array('or_refund_type'=>$int_o_refund_type))->getField('or_reason',true);
        $str_reason = D('Orders')->getReason($int_o_refund_type);
        $result['sub_msg'] = '获取退款/退货理由成功';
        $result['status'] = true;
        $result['info'] = !empty($str_reason) ? $str_reason : '';
        return $result;
    }


	/**
	 * 检查参数有效性
	 * @param $array_params
	 *
	 * @return mixed
	 * @date 2015-09-22 By Wangguibin
	 */
	private function checkParams($array_params){
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '验证有效性失败');
		if(isset($array_params['m_id']) && $array_params['m_id'] == 0){
			$result['message'] = '请填写用户ID:m_id';
			return $result;
		}
		if(isset($array_params['ra_id']) && $array_params['ra_id'] == 0){
			if($array_params['ra_id'] !='other'){
				$result['message'] = '请填写地址ID:ra_id';
				return $result;				
			}
		}

		if(isset($array_params['ary_cart']) && $array_params['ary_cart'] == 0){
			$result['message'] = '购买的商品为空';
			$result['redirect'] = U('Ucenter/Cart/pageList');
			return $result;

		}
		if(isset($array_params['pc_id']) && $array_params['pc_id'] == 0){
			$result['message'] = '请填写支付方式:pc_id';
			return $result;
		}

        //是否开启门店提货
        $zt_info =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $is_zt = $zt_info['IS_ZT']['sc_value'];
		if(isset($array_params['lt_id']) && $array_params['lt_id'] == 0 && empty($is_zt)){
			$result['message'] = '请填写物流ID:lt_id';
			return $result;
		}
        $after_two_hours = strtotime('+2 hours');
        $receiver_time = !empty($array_params['o_receiver_time']) ? strtotime($array_params['o_receiver_time']) : 0;
		$is_date=strtotime($array_params['o_receiver_time']) ? strtotime($array_params['o_receiver_time']) : false ;
        if($is_zt && $receiver_time < $after_two_hours && $array_params['o_receiver_time'] != '' && $is_date != false ){
            $result['message'] = '请选择正确的自提时间,自提时间在当前时间两小时之后!';
            return $result;
        }
		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '验证有效性通过';
		return $result;
	}

	/**
	 * [订单有效性验证]
	 * @param array $ary_cart
	 * @param int $int_m_id
	 *
	 * @return bool    true/false
	 */
	private function checkOrder($ary_cart=array(), $int_m_id=0){

		$result = array('result'=> false, 'code'=> 10101, 'message'=> '验证订单有效性失败');
		if (!empty($ary_cart)) {
            $ary_item = reset($ary_cart);
            $item_type = $ary_item['item_type'];
            $ary_pdt = array();
            foreach($ary_cart as $item) {
                $ary_pdt[] = $item['pdt_id'];
            }
			$ary_pdt = array_unique($ary_pdt);
			$field = array(
				'fx_goods_products.pdt_stock',
				'fx_goods_products.pdt_id',
				'fx_goods.g_on_sale',
				'fx_goods.g_status',
				'fx_goods.g_sn',
				'fx_goods.g_gifts',
				'fx_goods.g_is_combination_goods',
				'fx_goods.g_pre_sale_status',
				'fx_goods_info.is_exchange',
				'fx_goods_info.g_name',
				'fx_goods_info.g_id',
				'fx_goods_products.pdt_sale_price',
				'fx_goods_products.pdt_stock',
				'fx_goods_products.pdt_min_num',
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
				if ($value['g_status'] != 1) { // 下架|已删除
					$result['message'] = '商品“'.$value['g_name'].'”已下架！';
					$result['code']	= 10207;
					return $result;
				}
                //活动商品允许下架购买
                if($value ['g_on_sale'] != 1 && !in_array($item_type, array(5,7,8))) {
                    $result['message'] = '商品“'.$value['g_name'].'”已下架！';
                    $result['code']	= 10207;
                    return $result;
                }
				$is_authorize = D('AuthorizeLine')->isAuthorize($int_m_id, $value['g_id']);
				if (empty($is_authorize)) {
					$result['message'] = '部分商品已不允许购买,请先在购物车里删除这些商品';
					return $result;
				}
                //查询库存,如果库存数为负数则不再扣除库存
                if(0 >= $value['pdt_stock']){
                    $result['message'] = '该商品已售完';
                    return $result;
                }

                $pdt_id = $value['pdt_id'];
                $item_type = $ary_cart[$pdt_id]['item_type'];
                //普通商品购买限制
                if($item_type == 0) {
                    if ($ary_cart[$value['pdt_id']]['num'] < $value['pdt_min_num']) {
                        $result['message'] = '“'.$value['g_name'].'”至少需购买' . $value['pdt_min_num'] .'件！';
                        return $result;
                    }
                    if ($value['pdt_max_num'] > 0 && $ary_cart[$value['pdt_id']]['num'] > $value['pdt_max_num']) {
                        $result['message'] = '“'.$value['g_name'].'”最大可购买' . $value['pdt_max_num'] .'件！';
                        return $result;
                    }
                }
			}
		}else{
			$result['message'] = '没有商品数据！';
			$result['code']	= 10208;
			return $result;
		}

		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

	/**
	 * 检查团购参数有效性（在生成收货地址之后调用）
	 * @param $array_params
	 * @param $ary_orders
	 *
	 * @return mixed
	 * @date 2015-09-22
	 */
	private function checkBulkParams($array_params, $ary_orders){
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '验证团购有效性失败');
        $members = D('Members')->where(array(
            'm_id'  =>  $array_params['m_id']
        ))->find();
        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
        //团购判断
		if($ary_item['item_type'] == 5){
            //错误描述
            $error_msg = array(
                '0' =>  '活动已失效或购买数超过会员限购数！',
                '2' =>  '请先登录！',
                '3' =>  '活动尚未开始！',
                '4' =>  '活动已结束！',
                '5' =>  '已售罄！',
            );
            $ary_detail = D('Groupbuy')->getDetails($ary_item['type_id'], $members, $ary_item['pdt_id']);
            if($ary_detail['buy_status'] != 1) {
                $result['message'] = $error_msg[$ary_detail['buy_status']];
                return $result;
            }
            $is_can_buy = D('CityRegion')->isGroupCanBuy($ary_orders['o_receiver_city'], $ary_item['type_id']);
			if($is_can_buy == false){
				$result['message'] = '您收货地址所对应的区域不支持本次活动商品！';
				return $result;
			}

		}
		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

    /**
	 * 检查预售参数有效性
	 * @param $array_params
	 * @param $ary_orders
	 *
	 * @return mixed
	 * @date 2015-09-22
	 */
	private function checkPresaleParams($array_params, $ary_orders){
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '验证预售有效性失败');
        $members = D('Members')->where(array(
            'm_id'  =>  $array_params['m_id']
        ))->find();
        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
		//预售判断
		if($ary_item['item_type'] == 8){
            //错误描述
            $error_msg = array(
                '0' =>  '活动已失效！',
                '2' =>  '请先登录！',
                '3' =>  '活动尚未开始！',
                '4' =>  '活动已结束！',
                '5' =>  '已售罄！',
            );
            $ary_detail = D('Presale')->getDetails($ary_item['type_id'], $members, $ary_item['pdt_id']);
            if($ary_detail['buy_status'] != 1) {
                $result['message'] = $error_msg[$ary_detail['buy_status']];
                return $result;
            }
            $is_can_buy = D('CityRegion')->isPresaleCanBuy($ary_orders['o_receiver_city'],$ary_item['p_id']);
			if($is_can_buy == false){
				$result['message'] = '您收货地址所对应的区域不支持本次活动商品！';
				return $result;
			}
		}
		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

	/**
	 * 检查秒杀参数有效性
	 * @param $array_params
	 * @param $ary_orders
	 *
	 * @return mixed
	 * @date 2015-09-22
	 */
	private function checkSpikeParams($array_params, $ary_orders){
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '验证秒杀有效性失败');
        $members = M('members')->where(array('m_id'=>$array_params['m_id']))->find();

        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
		//秒杀商品每人限购1件 判断秒杀是否已结束
        if($ary_item['item_type'] == 7){
            if($ary_item['num'] > 1) {
                $result['message'] = '秒杀限购1件';
                return $result;
            }
			$arry_details = D('Spike')->getDetails($ary_item['type_id'], $members, $ary_item['pdt_id']);
			if($arry_details['max_buy_number'] < $ary_item['num']){
				$result['message'] = '手慢无，剩余库存不足了！';
				return $result;
			}
            if($arry_details['buy_status'] != 1) {
                $error_msg = array(
                    '0' =>  '活动已失效！',
                    '2' =>  '请先登录！',
                    '3' =>  '活动尚未开始！',
                    '4' =>  '活动已结束！',
                    '5' =>  '已售罄！',
                );
                $result['message'] = $error_msg[$arry_details['buy_status']];
                return $result;
            }

            $ary_where = array();
            $ary_where[C('DB_PREFIX')."orders.m_id"] = $array_params['m_id'];
            $ary_where[C('DB_PREFIX')."spike.sp_id"] = $ary_item['type_id'];
            $ary_where[C('DB_PREFIX')."orders.o_status"] = array('neq','2');
            $ary_where[C('DB_PREFIX')."orders_items.oi_type"] = $ary_item['item_type'];
            $ary_spike=D('Spike')
                ->field(array(C('DB_PREFIX').'orders_items.oi_id'))
                ->join(C('DB_PREFIX').'orders_items ON '.C('DB_PREFIX').'spike.sp_id = '.C('DB_PREFIX').'orders_items.fc_id')
                ->join(C('DB_PREFIX').'orders ON '.C('DB_PREFIX').'orders.o_id = '.C('DB_PREFIX').'orders_items.o_id')
                ->where($ary_where)
                ->find();
				
            if(!empty($ary_spike) && is_array($ary_spike)){
                $result['message'] = '秒杀限购1件，您已参与！';
                return $result;
            }
        }
		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

    /**
     * 检测积分兑换参数
     * @param $array_params
     * @param $ary_orders
     *
     * @return array
     */
    private function checkIntegralParams($array_params, $ary_orders) {
        $result = array('result'=> false, 'code'=> 10101, 'message'=> '验证积分兑换有效性失败');
        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
        //预售判断
        //积分兑换商品每人限购1件 判断积分+金额兑换是否已结束
        if($ary_item['item_type'] == 11){
            if($ary_item['num'] > 1) {
                $result['message'] = '积分兑换限购1件';
                return $result;
            }
            $ary_detail = D('Integral')
                ->field("integral_start_time,integral_end_time,integral_num,integral_now_number")
                ->where(array('integral_id'=> $ary_item ['type_id']))
                ->find();
            if($ary_detail['integral_num'] <= $ary_detail['integral_now_number']){
                $result['message'] = '已售罄';
                return $result;
            }
            if(strtotime($ary_detail['integral_start_time']) > mktime()){
                $result['message'] = '积分兑换未开始';
                return $result;
            }
            if(strtotime($ary_detail['integral_end_time']) < mktime()){
                $result['message'] = '积分兑换已结束';
                return $result;
            }

            $ary_where = array();
            $ary_where[C('DB_PREFIX')."orders.m_id"] = $array_params['m_id'];
            $ary_where[C('DB_PREFIX')."integral.integral_id"] = $ary_item['type_id'];
            $ary_where[C('DB_PREFIX')."orders.o_status"] = array('neq','2');
            $ary_where[C('DB_PREFIX')."orders_items.oi_type"] = 11;
            $ary_integral=D('Integral')
                ->field(array(C('DB_PREFIX').'orders_items.oi_id'))
                ->join(C('DB_PREFIX').'orders_items ON '.C('DB_PREFIX').'integral.integral_id = '.C('DB_PREFIX').'orders_items.fc_id')
                ->join(C('DB_PREFIX').'orders ON '.C('DB_PREFIX').'orders.o_id = '.C('DB_PREFIX').'orders_items.o_id')
                ->where($ary_where)
                ->find();
            if(!empty($ary_integral) && is_array($ary_integral)){
                $result['message'] = '积分兑换限购1件，您已参与！';
                return $result;
            }
        }
        $result['result'] = 1;
        $result['code'] = 200;
        $result['message'] = '';
        return $result;
    }

    /**
     * 自由推荐商品下单检测
     * @param $ary_cart
     * @param bool|false $is_cache
     * @return array
     */
    private function checkFreeCollocationParams($ary_cart, $is_cache=false) {
        $result = array('result'=> false, 'code'=> 10101, 'message'=> '验证自由推荐参数失败');
        if (!empty($ary_cart)) {
            $ary_type = array();
            foreach($ary_cart as $item) {
                $item_type = $item['item_type'];
                if ($item_type == 4) {
                    $type_id = $item['type_id'];
                    if ($type_id && !in_array($type_id, $ary_type)) {
                        $ary_type[] = $type_id;
                        if($is_cache == 1){
                            $obj_query = M('free_collocation',C('DB_PREFIX'),'DB_CUSTOM')
                                ->field('fc_start_time,fc_end_time,fc_status')
                                ->where(array("fc_id" => $type_id));
                            $arr_data = D('Gyfx')->queryCache($obj_query,'find',60);
                            //$arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->getField('pdt_stock');
                        }else{
                            $arr_data =  M('free_collocation',C('DB_PREFIX'),'DB_CUSTOM')
                                ->field('fc_start_time,fc_end_time,fc_status')
                                ->where(array("fc_id" => $type_id))
                                ->find();
                        }
                        if($arr_data['fc_status'] == 0) {
                            $result['message'] = '自由推荐已停用';
                            return $result;
                        }
                        if($arr_data['fc_start_time'] && strtotime($arr_data['fc_start_time']) > mktime()) {
                            $result['message'] = '自由推荐活动尚未开始';
                            return $result;
                        }
						if($arr_data['fc_end_time'] == '2999-00-00 00:00:00'){
							$arr_data['fc_end_time'] = date('Y-m-d H:i:s',strtotime('next month'));
						}
                        if($arr_data['fc_end_time']
                            && $arr_data['fc_end_time'] != '0000-00-00 00:00:00'
                            && strtotime($arr_data['fc_end_time']) < mktime()) {
                            $result['message'] = '自由推荐活动已结束';
                            return $result;
                        }

                    }
                }
            }
        }
        $result['result'] = true;
        $result['code'] = 200;
        $result['message'] = '';
        return $result;
    }
    /**
     * 自由搭配商品下单检测
     * @param $ary_cart
     * @param bool|false $is_cache
     * @return array
     */
    private function checkFreeRecommendParams($ary_cart, $is_cache=false) {
        $result = array('result'=> false, 'code'=> 10101, 'message'=> '验证自由搭配参数失败');
        if (!empty($ary_cart)) {
            $ary_type = array();
            foreach($ary_cart as $item) {
                $item_type = $item['item_type'];
                if ($item_type == 6) {
                    $type_id = $item['type_id'];
                    if ($type_id && !in_array($type_id, $ary_type)) {
                        $ary_type[] = $type_id;
                        if($is_cache == 1){
                            $obj_query = M('free_recommend',C('DB_PREFIX'),'DB_CUSTOM')
                                ->field('fr_start_time,fr_end_time,fr_status')
                                ->where(array("fr_id" => $type_id));
                            $arr_data = D('Gyfx')->queryCache($obj_query,'find',60);
                            //$arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->getField('pdt_stock');
                        }else{
                            $arr_data =  M('free_recommend',C('DB_PREFIX'),'DB_CUSTOM')
                                ->field('fr_start_time,fr_end_time,fr_status')
                                ->where(array("fr_id" => $type_id))
                                ->find();
                        }
                        if($arr_data['fr_status'] == 0) {
                            $result['message'] = '自由搭配已停用';
                            return $result;
                        }
                        if($arr_data['fr_start_time'] && strtotime($arr_data['fr_start_time']) > mktime()) {
                            $result['message'] = '自由搭配活动尚未开始';
                            return $result;
                        }
						if($arr_data['fr_end_time'] == '2999-00-00 00:00:00'){
							$arr_data['fr_end_time'] = date('Y-m-d H:i:s',strtotime('next month'));
						}						
                        if($arr_data['fr_end_time']
                            && $arr_data['fr_end_time'] != '0000-00-00 00:00:00'
                            && strtotime($arr_data['fr_end_time']) < mktime()) {
                            $result['message'] = '自由搭配活动已结束';
                            return $result;
                        }

                    }
                }
            }
        }
        $result['result'] = true;
        $result['code'] = 200;
        $result['message'] = '';
        return $result;
    }
    /**
	 * 订单收货地址详情
	 * @param $ary_params
	 * @param $ary_orders
	 *
	 * @return mixed
	 */
	private function buildOrderAddress($ary_params, &$ary_orders=array(),$ary_datas=array()) {
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '获取订单收货地址失败');
        $int_ra_id = $ary_params['ra_id'];
        $int_m_id = $ary_params['m_id'];

        if($ary_params['ra_id'] == 'other') {
            //使用临时收货地址
            // 收货人
            $ary_orders ['o_receiver_name'] = $ary_datas['ra_name'];
            //收货人身份证号
            if($ary_orders['ra_id_card']){
                $ary_orders ['o_receiver_idcard'] = $ary_datas['ra_id_card'];
            }
            // 收货人电话
            // $ary_orders ['o_receiver_telphone'] = trim($default_address ['default_addr'] ['ra_phone']);
            $ary_receiver_telphone = array();
            if(!empty($ary_datas['ra_phone_area'])) array_push($ary_receiver_telphone,$ary_datas['ra_phone_area']);
            if(!empty($ary_datas['ra_phone'])) array_push($ary_receiver_telphone,$ary_datas['ra_phone']);
            if(!empty($ary_datas['ra_phone_ext'])) array_push($ary_receiver_telphone,$ary_datas['ra_phone_ext']);
            $ary_orders ['ra_id'] = 0;
            $ary_orders ['o_receiver_telphone'] = !empty($ary_receiver_telphone) ? implode('-',$ary_receiver_telphone): '';
            // 收货人手机
            $ary_orders ['o_receiver_mobile'] = trim($ary_datas['ra_mobile_phone']);
            // 收货人邮编
            $ary_orders ['o_receiver_zipcode'] = trim($ary_datas['ra_post_code']);
            // 收货人地址
            $ary_orders ['o_receiver_address'] = trim($ary_datas['ra_detail']);
            // 收货人省份
            $ary_orders ['o_receiver_state'] = D('CityRegion')->getAddressName($ary_datas['province']);
            // 收货人城市
            $ary_orders ['o_receiver_city'] = D('CityRegion')->getAddressName($ary_datas['city']);
            // 收货人地区
            $ary_orders ['o_receiver_county'] = D('CityRegion')->getAddressName($ary_datas['region']);
        }
        else {
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
                if (!empty($ary_addr[1])) {
                    // 收货人省份
                    $ary_orders ['o_receiver_state'] = $ary_addr[0];
                    // 收货人城市
                    $ary_orders ['o_receiver_city'] = $ary_addr[1];
                    // 收货人地区
                    $ary_orders ['o_receiver_county'] = isset($ary_addr[2]) ? $ary_addr[2] : '';
                } else {
                    $result['message'] = '请检查您的收货地址是否正确！';
                    return $result;
                }
                if (empty($ary_orders ['o_receiver_county'])) { // 没有区时
                    unset($ary_orders ['o_receiver_county']);
                }
            } else {
                $result['message'] = '请检查您的收货地址是否正确！';
                return $result;
            }
        }

		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

    /**
     * 区域限售判断
     * @param array $ary_cart
     * @param $ary_orders
     * @return array
     */
    private function checkGlobalStock($ary_cart=array(),$ary_orders) {
        $result = array('result'=> false, 'code'=> 10101, 'message'=> '获取订单收货地址失败');
        //是否开启区域限售
        if(GLOBAL_STOCK == TRUE){
            if($ary_orders['ra_id'] == 0) {

            }
            $ary_receive_address = D('CityRegion')->getReceivingAddress($ary_orders ['m_id'], $ary_orders['ra_id']);
            if(!empty($ary_receive_address)) {
                $ara_v = reset($ary_receive_address);
                $cr_id = $ara_v['cr_id'];
            }else {
                return $result;
            }
            foreach($ary_cart as $ary) {
                //预售商品可以负库存销售
                if($ary['type'] == 'presale') break;
                $return_stock = D('GoodsStock')->getProductsWarehouseStock($cr_id, $ary ['pdt_id']);
                if (0 == $return_stock) {
                    $pdt_sn = D("GoodsProducts")->where(array('pdt_id' => $ary ['pdt_id']))->getField('pdt_sn');
                    $result['message'] = '货品编码为' . $pdt_sn . '的商品不在限购区域内,请先重新选择所在区域！';
                    $result['redirect'] = U('Ucenter/Cart/pageList');
                    return $result;
                }
                if (!$return_stock['pdt_sn']) {
                    $pdt_sn = D("GoodsProducts")->where(array('pdt_id' => $ary ['pdt_id']))->getField('pdt_sn');
                } else {
                    $pdt_sn = $return_stock['pdt_sn'];
                }
                if (intval($return_stock['pdt_stock']) < $ary['num']) {
                    $result['message'] = '货品编码为' . $pdt_sn . '的商品库存已不足,请先在购物车里删除这件商品';
                    $result['redirect'] = U('Ucenter/Cart/pageList');
                    return $result;
                }
            }
        }

        $result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
    }

    /**
	 * 订单发票信息
	 * @param array $array_params
	 * @param array $ary_orders
	 *
	 * @return array
	 */
	private function buildOrderInvoice($array_params= array(), &$ary_orders=array()) {
		$result = array();
		switch ($array_params['is_on']){
			case 0: //不需要发票
				break;
			case '1': //普通发票 个人发票
			case '2':
				$ary_orders['is_invoice'] = 1;//is_invoice 需要发票
				$ary_orders['invoice_head'] = $array_params['invoice_head'];
				$ary_orders['invoice_people'] = $array_params['invoice_people']?$array_params['invoice_people']:"";
				$ary_orders['invoice_name'] = $array_params['invoice_name']?$array_params['invoice_name']:"";
				$ary_orders['invoice_content'] = $array_params['invoice_content'];
				break;
			case '3'://使用默认发票
				$res_invoice = D('InvoiceCollect')->getid($array_params ['is_default']);

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
				break;
			case '4': //增值税发票
				$ary_res = M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
					"id" => $array_params ['in_id']
				))->find();

				$ary_orders ['invoice_type'] = $ary_res ['invoice_type'];
				$ary_orders ['invoice_head'] = $ary_res ['invoice_head'];
				// echo "<pre>";print_r($ary_res);exit;
				$ary_orders ['is_invoice'] = 1;
				if (empty($ary_res ['invoice_name'])) {
					$ary_orders ['invoice_name'] = '个人';
				} else {
					$ary_orders ['invoice_name'] = $array_params ['invoice_name'];
				}

				// 个人姓名
				$ary_orders ['invoice_people'] = $array_params ['invoice_people'];
				// 纳税人识别号
				$ary_orders ['invoice_identification_number'] = $array_params ['invoice_identification_number'];
				// 注册地址
				$ary_orders ['invoice_address'] = $array_params ['invoice_address'];
				// 注册电话
				$ary_orders ['invoice_phone'] = encrypt($array_params ['invoice_phone']);
				// 开户银行
				$ary_orders ['invoice_bank'] = $array_params ['invoice_bank'];
				// 银行帐户
				$ary_orders ['invoice_account'] = $array_params ['invoice_account'];
				$ary_orders ['invoice_content'] = $ary_res ['invoice_content'];
                break;
        		}
//echo "<pre>";print_r($ary_orders);die;
		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

	/**
	 * 订单促销信息
	 * @param array $array_params
	 * @param array $ary_orders
	 *
	 * @return array
	 */
	public function buildOrderPromotion($array_params= array(), &$ary_orders=array()) {
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '获取订单商品促销信息失败');
		$array_params['type'] = 10;
		if(!empty($array_params['pids']) && !empty($array_params['gid'])){
		}else{
			$ary_sgp = explode(';', $array_params['sgp']);
			$ary_pids = $ary_gid = array();
			//遍历要购买的商品
			foreach($ary_sgp as $sgp) {
				$ary_item = explode(',', $sgp);
				$g_id = $ary_item[0];
				$pdt_id = $ary_item[1];
				$ary_pids[] = $pdt_id;
				$ary_gid[] = $g_id;
			}
			$array_params['pids'] = implode(',', $ary_pids);
			$array_params['gid'] = $ary_gid;			
		}
		$ary_price = D('Orders')->getAllOrderPrice($array_params);
		if(!$ary_price['success']) {
			$result['message'] = $ary_price['errMsg'];
			return $result;
		}

		//writeLog(var_export($array_params, true), 'order_add_api_'. date('Y_m_d') .'.log');


		$ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $ary_price['o_goods_all_saleprice']);  //销售价
		$ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $ary_price['o_goods_all_price']);  //促销价
		if(empty($ary_orders ['o_goods_all_price'])){
			$result['message'] = '没有要购买的商品，请重新选择商品';
			return $result;
		}
		$ary_orders ['o_tax_rate'] = sprintf("%0.2f", $ary_price['o_tax_rate']);    //订单税收
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
		//赠送积分、冻结积分、抵扣积分金额
		if($ary_price['reward_point']){
			$ary_orders['o_reward_point'] = $ary_price['reward_point'];
		}
		$ary_orders['o_freeze_point'] = intval($ary_price['points']);   //积分数
		$ary_orders['o_point_money'] = sprintf("%0.2f", $ary_price['point_price']);   //积分抵扣金额
		$ary_orders['o_cards_money'] = sprintf("%0.2f", $ary_price['cards_price']);   //使用存储卡抵扣金额
		$ary_orders['o_bonus_money'] = sprintf("%0.2f", $ary_price['bonus_price']);   //使用红包抵扣金额
		//返回优惠券信息和pro_datas信息
		$ary_orders['ary_coupon'] = $ary_price['ary_coupon'];
		$ary_orders['pro_datas'] = $ary_price['pro_datas'];

		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

	/**
	 * 获得总积分
	 * @param $ary_orders
	 * @param $ary_insert_item
	 * @return mixed
	 * @date 2015-09-22 By Wangguibin
	 */
	private function getRewardPoint($ary_orders,$ary_insert_item){

		$result = array('result'=> false, 'code'=> 10101, 'message'=> '获取订单总积分失败');
		// 商品下单获得总积分
		$other_all_price = $ary_insert_item['int_pdt_sale_price']-$ary_insert_item['gifts_point_goods_price'];
		$total_reward_point = intval(D('PointConfig')->getrRewardPoint($other_all_price));
		$total_reward_point = ceil((($ary_orders ['o_all_price']-$ary_orders ['o_cost_freight'])/$ary_insert_item['int_pdt_sale_price'])*$total_reward_point);
		$total_reward_point += $ary_insert_item['gifts_point_reward'];
		$total_consume_point = (int)$ary_orders['o_freeze_point'];

		//有消耗积分或者获得积分，消耗积分插入订单表进行冻结操作
		if ($total_reward_point > 0 || $total_consume_point>0) {
			$ary_freeze_point = array(
				'o_id' => $ary_orders['o_id'],
				'm_id' => $ary_orders['m_id'],
				'freeze_point' => $total_consume_point,
				'reward_point' => $total_reward_point
			);

			$res_point = D('Orders')->updateFreezePoint($ary_freeze_point);
			if (!$res_point) {
				$result['message'] = '更新冻结积分失败';
				return $result;
			}
		}

		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;
	}

    /**
     * 更新红包
     * @param $array_params
     * @param $ary_orders
     *
     * @return array
     */
    private function updateBonus($array_params,$ary_orders) {
        $bonus_price = $ary_orders['o_bonus_money'];
        if(isset($bonus_price) && $bonus_price>0){
            //更新红包使用
            $arr_bonus = array(
                'bt_id' => '4',
                'm_id'  => $array_params['m_id'],
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
                return array(
                    'result' => false,
                    'message' => '红包使用失败',
                    'code' => '',
                );
            }
        }
        return array(
            'result' => true,
            'message' => '',
            'code'  => 200,
        );
    }
    /**
     * 更新红包
     * @param $ary_orders
     *
     * @return array
     */
    private function sendSms($ary_orders) {
        //是否开启门店提货
        $zt_info =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $is_zt = $zt_info['IS_ZT']['sc_value'];
        $ary_payment = D('PaymentCfg')->where(array('pc_id'=>$ary_orders['o_payment']))->find();
        $ary_logistic_where = array(
            'lt_id' => $ary_orders ['lt_id']
        );
        $ary_field = array(
            'lc_abbreviation_name'
        );
        $ary_log = D('Logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
        //提货通知
        if (( $ary_payment ['pc_abbreviation'] == 'DELIVERY'
                || $ary_payment ['pc_abbreviation'] == 'OFFLINE' ) && $is_zt == 1) {
            if(isset($ary_orders ['lt_id'])){
                $ary_orders['mobile'] = $ary_orders['o_receiver_mobile'];
                //自提订单
                if($ary_log[0]['lc_abbreviation_name'] == 'ZT' && !empty($ary_orders['mobile'])){
                    D('SmsTemplates')->sendSmsGetCode($ary_orders);
                }
            }
        }
        return array(
            'result' => true,
            'message' => '',
            'code' => 200,
        );
    }

    /**
     * cps消息推送
     * @param $ary_orders
     */
    private function pushCpsMessage($ary_orders) {
        $bigou_cps_open  = D('SysConfig')->getConfigValueBySckey('CPS_51BIGOU_OPEN','CPS_SET');
        $fanli_cps_open = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');
        $bigou_cps_channel_id  = D('SysConfig')->getConfigValueBySckey('BIGOU_CHANNELID','CPS_SET');
        $fanli_cps_channel_id = D('SysConfig')->getConfigValueBySckey('FANLI_CHANNELID','CPS_SET');

        //订单提交成功后执行推送信息
        if($bigou_cps_open == '1' || $fanli_cps_open == '1') {
            if (isset($_COOKIE['channel_id']) && !empty($_COOKIE['channel_id'])) {
                $orders_info = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')
                    ->where(array('o_id' => $ary_orders['o_id']))->find();
                $channel_id = $_COOKIE['channel_id'];
                switch ($channel_id) {
                    case $fanli_cps_channel_id:
                        $Fanli = A('Home/OrderBack');
                        $info = array();
                        $info[0] = $orders_info;
                        $fanli_state =  $Fanli->PushOrders($info);
                        writeLog(date('Ymd H:i:s').'___'.$fanli_state.PHP_EOL,$fanli_state,date('Ymd').'cps_fanli.log');
                        break;
                    case $bigou_cps_channel_id :
                        $Bigou = A('Home/OrderBack');
                        $bigou_state =  $Bigou->orderback($orders_info);
                        writeLog(date('Ymd H:i:s').'____'.$bigou_state.PHP_EOL,$fanli_state,'cps_bigou.log');
                        break;
                }
            }
        }
    }
    /**
	 * 更新优惠券
	 * @param $array_params
	 * @param $ary_orders
	 * @return mixed
	 * @date 2015-09-22 By Wangguibin
	 */
	private function updateCoupon($array_params,$ary_orders){
        $nowDate = date('Y-m-d H:i:s');
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '更新优惠券失败');
		if(!empty($ary_orders['o_coupon_menoy']) && $ary_orders['o_coupon_menoy'] !='0.00'){
            $ary_act = D("Gyfx")->selectOne("coupon_activities","*",array("ca_sn"=>$array_params['csn']));
            if(is_array($ary_act) && $ary_act['ca_type'] == 0){
                //同号券活动
                $ary_coupon = array(
                    'c_name' => $ary_act['ca_name'],
                    'c_sn' => $ary_act['ca_sn'],
                    'c_name' => $ary_act['ca_name'],
                    'c_start_time' => $ary_act['ca_start_time'],
                    'c_end_time' => $ary_act['ca_end_time'],
                    'c_memo' => $ary_act['ca_memo'],
                    'c_money' => $ary_act['c_money'],
                    'c_condition_money' => $ary_act['c_condition_money'],
                    'c_user_id' => $ary_orders['m_id'],
                    'c_create_time' => $nowDate,
                    'c_type' => $ary_act['c_type'],
                    'ca_id' => $ary_act['ca_id']
                );
                $res_coupon = D("CouponActivities")->doUseCoupon($ary_orders['o_id'], $ary_coupon);
            }else{
                $ary_data = array(
                    'c_is_use' => 1,
                    'c_used_id' => $ary_orders['m_id'],
                    'c_order_id' => $ary_orders ['o_id']
                );
                $res_coupon = D('Coupon')->doCouponUpdate($array_params['csn'], $ary_data);
            }
			if (!$res_coupon) {
				$result['message'] = '优惠券更新失败';
				return $result;
			}
		}

		$result['result'] = true;
		$result['code'] = 200;
		$result['message'] = '';
		return $result;

	}

    /**
     * cps相关检查
     * @param $array_params
     * @param $ary_orders
     * @return mixed
     */
    private function cpsCheck($array_params, &$ary_orders)
    {
        $ary_member =$array_params['ary_members'];
        $bigou_cps_open  = D('SysConfig')->getConfigValueBySckey('CPS_51BIGOU_OPEN','CPS_SET');
        $fanli_cps_open = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');

        $bigou_cps_channel_id  = D('SysConfig')->getConfigValueBySckey('BIGOU_CHANNELID','CPS_SET');
        $fanli_cps_channel_id = D('SysConfig')->getConfigValueBySckey('FANLI_CHANNELID','CPS_SET');

        if($bigou_cps_open == '1' || $fanli_cps_open == '1'){
            if(empty($bigou_cps_channel_id) && empty($fanli_cps_channel_id)){
                writeLog(date('Ymd H:i:s').'请设置cps相关参数'.PHP_EOL ,'cps_config.log');
            }
            if(isset($_COOKIE['channel_id']) && !empty($_COOKIE['channel_id'])){
                $channel_id = $_COOKIE['channel_id'];
                $ary_orders['channel_id'] = $channel_id;
                $member_data = json_decode($ary_member['union_data'],true);
                $data=array();
                switch($channel_id){
                    case $fanli_cps_channel_id:
                        $data = array(
                            'u_id'=>$_COOKIE['u_id'],
                            'tracking_code'=>$_COOKIE['tracking_code'],
                            'username'=>$member_data['username'],
                            'channel_id'=>$_COOKIE['channel_id']
                        );
                        break;
                    case $bigou_cps_channel_id:
                        $data = array(
                            'u_id'=>$_COOKIE['u_id'],
                            'username'=>$member_data['username'],
                            'channel_id'=>$_COOKIE['channel_id']
                        );
                        break;
                }
                $ary_orders['channel_related_info'] = json_encode($data);
            }
        }

    }
	/**
	 * 新增订单日志
	 * @param $ary_orders
	 *
	 * @return array
	 */
	private function addOrderLog($array_params, $ary_orders) {
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '新增订单日志失败');
        $ary_members = $array_params['ary_members'];
        // 订单日志记录
		$ary_orders_log = array(
			'o_id' => $ary_orders ['o_id'],
			'ol_behavior' => '创建',
			'ol_uname' => $ary_orders['m_id'],
			'ol_create' => date('Y-m-d H:i:s')
		);

		$res_orders_log = D('OrdersLog')->add($ary_orders_log);
		if (!$res_orders_log) {
			$result['message'] = '订单日志记录失败';
			return $result;
		}
        if(0 == $ary_orders['o_all_price']) {
            $ary_orders_log = array(
                'o_id' => $ary_orders ['o_id'],
                'ol_behavior' => '积分/优惠券支付',
                'ol_uname' => $ary_members['m_name'],
                'ol_create' => date('Y-m-d H:i:s')
            );
            D('OrdersLog')->add($ary_orders_log);
        }

        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
        //秒杀商品每人限购1件 判断秒杀是否已结束
        if($ary_item['item_type'] == 7){
            $ary_gb_log = array();
            $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
            $ary_gb_log ['sp_id'] = $ary_item ['type_id'];
            $ary_gb_log ['m_id'] = $array_params['m_id'];
            $ary_gb_log ['g_id'] = $ary_item['g_id'];
            $ary_gb_log ['num'] = $ary_item['num'];

            if (false === M('spike_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                $result['message'] = '秒杀日志生成失败';
                return $result;
            }
        }
        //团购商品每人限购1件 判断秒杀是否已结束
        if($ary_item['item_type'] == 5){
            $ary_gb_log = array();
            $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
            $ary_gb_log ['gp_id'] = $ary_item ['type_id'];
            $ary_gb_log ['m_id'] = $array_params['m_id'];
            $ary_gb_log ['g_id'] = $ary_item['g_id'];
            $ary_gb_log ['num'] = $ary_item['num'];

            if (false === M('groupbuy_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                $result['message'] = '团购日志生成失败';
                return $result;
            }
        }
        //预售商品每人限购1件 判断秒杀是否已结束
        if($ary_item['item_type'] == 8){
            $ary_gb_log = array();
            $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
            $ary_gb_log ['p_id'] = $ary_item ['type_id'];
            $ary_gb_log ['m_id'] = $array_params['m_id'];
            $ary_gb_log ['g_id'] = $ary_item['g_id'];
            $ary_gb_log ['num'] = $ary_item['num'];

            if (false === M('presale_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                $result['message'] = '预售日志生成失败';
                return $result;
            }
        }

		$result = array('result'=> true, 'code'=> 200, 'message'=> '');
		return $result;
	}

	/**
	 * 清除购物车商品
	 * @param $ary_pdts
	 * @param $m_id
	 */
	private function cleanShoppingCartItem($ary_pdts, $m_id,$array_params) {
        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);

		if ($ary_item['item_type'] == 7) {
			// 秒杀商品
			unset($_SESSION['spike_cart']);
		}else if($ary_item['item_type'] == 5){
			// 团购商品
			unset($_SESSION['bulk_cart']);
		}else if($ary_item['item_type'] == 8){
			// 预售商品
			unset($_SESSION['presale_cart']);
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

    /**
     * 获取购物车详情
     * @param $array_params
     * @return mixed
     */
    private function getCartItems($array_params) {
        $ary_cart = $array_params['ary_cart'];
        $ary_item = reset($ary_cart);
        if(!in_array($ary_item['item_type'], array(7,8,11,5))) {
            $pids = $array_params['pids'];
            $m_id = $array_params['m_id'];
//            dump($pids);die;
            $cartModel = D('Cart');
            $ary_cart = $cartModel->getCartItems($pids, $m_id);
        }
        return $ary_cart;
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
	private function insertOrderItems($ary_orders, $array_params,$ary_coupon, $pro_datas) {
		$result = array('result'=> false, 'code'=> 10101, 'message'=> '插入订单详情失败');
		$str_pdt_ids = $array_params['pids'];
		$m_id = $ary_orders['m_id'];
		$ary_orders_items = array();
		$cartModel = D('Cart');
		$orders = D('Orders');
		//获取购物车数据
        $ary_cart = $this->getCartItems($array_params);
        $ary_gifts = $pro_datas['gifts'];
        $ary_orders_goods = $cartModel->getProductInfo($ary_cart, $m_id);
		//满送赠品
        if (!empty($ary_gifts)) {
            $ary_gifts_goods = $cartModel->getProductInfo($ary_gifts);
            if (!empty($ary_gifts_goods)) {
                foreach ($ary_gifts_goods as $gift) {
                    array_push($ary_orders_goods, $gift);
                }
            }
        }
        $ary_orders_goods = D('OrdersItems')->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);
		if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
			$total_consume_point = 0; // 消耗积分
			$int_pdt_sale_price = 0; // 货品销售原价总和
			$gifts_point_reward = '0'; //有设置购商品赠积分所获取的积分数
			$gifts_point_goods_price  = '0'; //设置了购商品赠积分的商品的总价
            $ary_orders_goods_new = array();
            foreach($ary_orders_goods as $order_goods) {
//                dump($order_goods);die;
                $skip = false;
                foreach($order_goods as $o_goods) {
                    //自由组合，自由推荐商品
                    if(is_array($o_goods) && in_array($o_goods['type'], array(6,4))) {
                        $skip = true;
                        $ary_orders_goods_new[] = $o_goods;
                    }else {
                        $skip = false;
                    }
                }
                if($skip == false) {
                    $ary_orders_goods_new[] = $order_goods;
                }

            }
			//重复赠送赠品
            //if(!empty($ary_gifts))
               // $ary_orders_goods_new = array_merge($ary_orders_goods_new, //$ary_gifts);
			//dump($ary_orders_goods_new);die;
            foreach ($ary_orders_goods_new as $k => $v) {
				$ary_orders_items = array();
                //普通商品
                if($v['type'] == 0) {
                    $ary_mem_info = D('Gyfx')->selectOneCache('members',array('ml_id'), array('m_id'=>$ary_orders['m_id']),
                        $ary_order=null,100);
                    $ary_level_info = D('Gyfx')->selectOneCache('members_level',array('ml_status','ml_discount'),
                        array('ml_id'=>$ary_mem_info['ml_id']), $ary_order=null,3600);
                    //等级折扣
                    if (!empty($ary_level_info['ml_discount'])) {
                        $ary_orders_items['ml_discount'] = $ary_level_info['ml_discount'];
                    }
                    //返点比例
                    if (!empty($ary_orders['ml_rebate'])) {
                        $ary_orders_items['ml_rebate'] = $ary_orders['ml_rebate'];
                    }
                    if (!empty($v['rule_info']['name'])) {
                        $v['pmn_name'] = $v['rule_info']['name'];
                    }

                    if (empty($v['pdt_rule_name'])) {
                        $v['pdt_rule_name'] = (isset($v['pmn_name']) ? $v['pmn_name'] : '');
                    }
                    $ary_orders_items['promotion'] = $v['pdt_rule_name'];

                    if (isset($v['promotion_price']) && !empty($v['promotion_price'])) {
                        $ary_orders_items['promotion_price'] = $v['promotion_price'];
                    }

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
                }
                //积分商品
                if ($v['type'] == 1) {
                    $ary_orders_items ['oi_score'] = $v ['g_point'];
                    $total_consume_point += $v ['g_point'] * $v ['pdt_nums'];
                }
                if($v['type'] == 11) {
                    $ary_orders_items ['oi_score'] = $v ['g_point'];
                    $total_consume_point += $v ['g_point'] * $v ['pdt_nums'];
                }
                // 订单id
                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                // 商品id
                $ary_orders_items ['g_id'] = $v ['g_id'];
                //活动ID
                $ary_orders_items ['fc_id'] = $v['type_id'] ? $v['type_id'] : 0;
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
                $ary_orders_items ['oi_price'] =  $v ['pdt_price'];
                // 商品数量
                $ary_orders_items ['oi_nums'] = $v['pdt_nums'];
                //返点比例
                if (!empty($ary_orders['ml_rebate'])) {
                    $ary_orders_items['ml_rebate'] = $ary_orders['ml_rebate'];
                }
                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                if (!$bool_orders_items) {
//                dump($ary_orders_items);
                    $result['message'] = '订单明细新增失败！';
                    return $result;
                }
                $int_pdt_sale_price += $v ['pdt_price'] * $v ['pdt_nums'];
				//团购
				if($v['type'] == 5){
					$retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$v['pdt_nums']);
					if (!$retun_buy_nums) {
						$result['message'] = '更新团购量失败';
						return $result;
					}
				}
				//预售
				elseif($v['type'] == 8){
					$retun_buy_nums=D("Presale")
                        ->where(array('p_id' => $ary_orders_items['fc_id']))
                        ->setInc("p_now_number",$v['pdt_nums']);
					//echo D('Presale')->getLastSql();die;
                    if (!$retun_buy_nums) {
						$result['message'] = '更新团购量失败';
						return $result;
					}
				}
				//秒杀
				else if($v['type'] == 7){
					$retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$v['pdt_nums']);
					if (!$retun_buy_nums) {
						$result['message'] = '更新秒杀量失败';
						return $result;
					}
				}
                //积分+金额
				else if($v['type'] == 11){
					//...
				}

                // 商品库存扣除
                $ary_payment_where = array(
                    'pc_id' => $ary_orders ['o_payment']
                );
                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                //货到付款/线下付款，立即扣库存
                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                    // by Mithern 扣除可下单库存生成库存调整单
                    $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))
                        ->where(array('g_id' => $v ['g_id']))->find();
                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                        if (false == $array_result ["status"]) {
                            $result['message'] = $array_result ['msg'] . ',CODE:' . $array_result ["code"];
                            return $result;

                        }
                    }
                }
                //增加销量
                if ($bool_orders_items) {
                    //商品销量添加
                    $ary_goods_num = M("goods_info")->where(array('g_id' => $ary_orders_items ['g_id']))->data(array('g_salenum' => array('exp','g_salenum + '.$ary_orders_items['oi_nums'])))->save();
                    if (!$ary_goods_num) {
                        $result['message'] = '销量添加失败';
                        return $result;
                    }
                }
				if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
					$gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
					//$gifts_point_goods_price += $v['f_price']*$v['pdt_nums'];
					$gifts_point_goods_price +=$pro['pdt_momery'];
				}
			}
		}

		$ary_return = array(
			'total_consume_point'=>$total_consume_point,
			'int_pdt_sale_price'=>$int_pdt_sale_price,
			'gifts_point_reward'=>$gifts_point_reward,
			'gifts_point_goods_price'=>$gifts_point_goods_price
		);

		$ary_return['result'] = true;
		$ary_return['code'] = 200;
		return $ary_return;
	}

    /**
     * 接口sgp参数解析
     * @param $array_params
     * @return mixed
     */
    public function sgpParse($array_params) {

        $str_items  = base64_decode($array_params['sgp']);
        //dump($str_items);die;
        $array_params['pdt_id'] = $str_items;
        $array_params['sgp'] = $str_items;
        $ary_items = explode(';', $array_params['sgp']);
        $ary_cart = $ary_pdts = $ary_gids = $ary_gifts = array();
        $ary_type = array(
            'item'      => 0,   //普通商品
            'point'     => 1,   //
            'gift'      => 2,   //赠品
            'grouped'   => 3,   //组合商品
            'freerecommend' => 6,   //自由搭配
            'bulk'      => 5,   //团购
            'free'      => 4, //自由推荐
            'spike'     => 7,   //秒杀
            'presale'   => 8,   //预售
            'integral'  => 11,   //积分商品
        );
        foreach($ary_items as $item) {
            $item = trim($item);
            if(!empty($item)) {
                $ary_info = explode(',', $item);
                //$type_code = preg_replace('/[0-9]/','', $ary_info[3]);
                $ary_cart[$ary_info[1]] = array(
                    'g_id'		=> $ary_info[0],
                    'pdt_id'	=> $ary_info[1],
                    'num'		=> $ary_info[2],
                    'item_type'		=> $ary_type[$ary_info[3]],
                    'type_code'		=> $ary_info[3],
                    'type_id'	    => $ary_info[4],
                );
                if ($ary_info[3] == 'free') {
                    $ary_pdts[] = $ary_info[3] . $ary_info[4];
                } elseif ($ary_info[3] == 'freerecommend') {
                    $ary_pdts[] = $ary_info[3];
                } else {
                    $ary_pdts[] = $ary_info[1];
                }

                $ary_gids[] = $ary_info[0];
            }
        }

        $array_params['ary_cart'] = $ary_cart;
        $array_params['gid'] = $ary_gids;
        $ary_pdts = array_unique($ary_pdts);
        $array_params['ary_pdts'] = $ary_pdts;
        $array_params['pids'] = implode(',', $ary_pdts);
        $ary_member = D("Members")->where(array('m_id'=>$array_params['m_id']))->find();
        $array_params['ary_members'] = $ary_member;
        return $array_params;
    }
	/**
	 * 生成订单
	 * @param  array $array_params
	 * $array_params = array(
	 * 'ra_id' => '', //地址ID (必填)
	 * 'm_id' => '',  //会员ID (必填)
	 * 'pc_id' => '', //支付ID (必填)
	 * 'lt_id' => '', //物流ID (必填)
	 * 'sgp' => '',   //g_id,pdt_id(规格ID),num,type,type_id;g_id,pdt_id(规格ID),num,type,type_id
	 * 'resource' => '', //订单来源 (必填) (android或ios)
	 *
	 * 'bonus' => '',      //可选，红包
	 * 'cards' => '',      //可选，储值卡
	 * 'csn'   => '',      //可选，优惠码
	 * 'point' => '',      //可选，积分
	 * 'type'	=>'',	       //可选，0：优惠券|1：红包|2：存储卡|4：积分
	 * 'admin_id' => '',	//可选，管理员id
	 * 'shipping_remarks' => '', //发货备注
	 * );
	 *
	 * @return array
	 */
	public function fxOrderDoAdd($array_params = array(),$ary_datas = array()) {
		$int_m_id = $array_params['m_id'] = max(0,(int)$array_params['m_id']);
		$int_ra_id = $array_params['ra_id'];
		$int_lt_id = $array_params['lt_id'] = max(0,(int)$array_params['lt_id']);
		$int_pc_id = $array_params['pc_id'] = max(0,(int)$array_params['pc_id']);
		$array_params['resource'] == strtolower(trim($array_params['resource']));

        $array_params = $this->sgpParse($array_params);
        $ary_cart = $array_params['ary_cart'];
		$ary_orders = array();

		//检查参数有效性
		$res = $this->checkParams($array_params);
		if(!$res['result']) return $res;
		//检查订单有效性
		$res = $this->checkOrder($ary_cart, $int_m_id);
		if(!$res['result']) return $res;
		//获取收货地址详情
		$res = $this->buildOrderAddress($array_params, $ary_orders,$ary_datas);
		if(!$res['result']) return $res;
		//区域限售判断
        $res = $this->checkGlobalStock($ary_cart, $ary_orders);
		if(!$res['result']) return $res;
        //团购参数判断
		$res = $this->checkBulkParams($array_params,$ary_orders);
		if(!$res['result']) return $res;
        //预售参数判断
		$res = $this->checkPresaleParams($array_params,$ary_orders);
		if(!$res['result']) return $res;
		//秒杀参数判断
		$res = $this->checkSpikeParams($array_params,$ary_orders);
		if(!$res['result']) return $res;
        //积分兑换参数判断
		$res = $this->checkIntegralParams($array_params,$ary_orders);
		if(!$res['result']) return $res;
        //自由推荐商品参数判断
        $res = $this->checkFreeCollocationParams($ary_cart, $ary_orders);
		if(!$res['result']) return $res;
        //自由搭配商品参数判断
        $res = $this->checkFreeRecommendParams($ary_cart, $ary_orders);
		if(!$res['result']) return $res;

		//获取发票详情
		$res = $this->buildOrderInvoice($array_params, $ary_orders);
		if(!$res['result']) return $res;
		
		//获取订单价格详情
		$res = $this->buildOrderPromotion($array_params, $ary_orders);
		if(!$res['result']) return $res;
		if(!empty($ary_orders['ary_coupon'])){
			$ary_coupon = $ary_orders['ary_coupon'];
			unset($ary_orders['ary_coupon']);
		}
		if(!empty($ary_orders['pro_datas'])){
			$pro_datas = $ary_orders['pro_datas'];
			unset($ary_orders['pro_datas']);
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
		if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 ){
			if($ary_orders['o_payment'] == 6){
				$ary_orders['o_audit'] = 1;
			}
			if($ary_orders['o_all_price']<=0){
				$ary_orders['o_audit'] = 1;
			}               
		}
        $this->cpsCheck($array_params, $ary_orders);
		//是否是匿名购买
		if($array_params['is_anonymous'] == '1'){
			$ary_orders['is_anonymous'] = 1;
		}
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
			if(isset($array_params['mrealname']) &&!empty($array_params['mrealname'])){
				$ary_orders['receiver_real_name'] = $array_params['mrealname'];
			}
			if(isset($array_params['midcard']) &&!empty($array_params['midcard'])){
				$ary_orders['o_receiver_idcard'] = $array_params['midcard'];
			}
		}

		$ordersModel = D('Orders');
		//保存订单
//		echo "<pre>";print_r($ary_orders);die;
		$bool_orders = $ordersModel->doInsert($ary_orders);
		if (!$bool_orders) {
			return array('result'=> false, 'message'=>'订单生成失败', 'code'=>10101);
		}
		//保存订单详情
		$res = $ary_insert_item = $this->insertOrderItems($ary_orders, $array_params,$ary_coupon,$pro_datas);
		if(!$res['result']) return $res;

		//商品下单获得总积分
		$res = $this->getRewardPoint($ary_orders,$ary_insert_item);
		if(!$res['result']) return $res;
		//更新优惠券使用
		$res = $this->updateCoupon($array_params,$ary_orders);
		if(!$res['result']) return $res;
		//更新红包使用
		$res = $this->updateBonus($array_params,$ary_orders);
		if(!$res['result']) return $res;
		//积分兑换商品冻结积分


		//新增订单日志
		$res = $this->addOrderLog($array_params, $ary_orders);
		if(!$res['result']) return $res;
        $ary_pdts = $array_params['ary_pdts'];
		//清除购物车
		$this->cleanShoppingCartItem($ary_pdts, $int_m_id,$array_params);
        //发送短信
        $this->sendSms($ary_orders);
        //消息推送
        $this->pushCpsMessage($ary_orders);
		return array('result'=> true, 'message'=>'订单生成成功', 'code'=>200 , 'data'=> $ary_orders);
	}

	/**
	 * 获取配送方式列表
	 * @param $ary_sgp
	 * @param int $m_id
	 * @param int $ra_id
	 * @return array
	 */
	public function fxLogisticListGet($ary_sgp, $m_id=0, $ra_id=0) {
		$ary_return = array('result'=>false, 'code'=> 10101, 'message'=> '获取配送方式失败！');
		$ary_pdt = array();
		$order_type = 0;
		//遍历要购买的商品
		foreach($ary_sgp as $sgp) {
			$ary_item = explode(',', $sgp);
			$g_id = $ary_item[0];
			$pdt_id = $ary_item[1];
			$num = $ary_item[2];
            switch($ary_item[3]){
                case 'bulk':
                    $order_type = '5';
                    break;
                case 'spike':
                    $order_type = '7';
                    break;
                case 'presale':
                    $order_type = '8';
                    break;
                default:
                    $order_type = 0;
            }
			$item_type = isset($ary_item[3]) ? $ary_item[3] : 0;
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
		$address_data = D('CityRegion')->getFindReciveAddr($ra_id, $m_id);
		// if(empty($address_data)){
			// $ary_return['message'] = '请检查收货地址是否存在';
			// return $ary_return;
		// }

		//获取物流公司列表
		$logistic_data = D('Logistic')->getShippingList($address_data["cr_id"], $ary_pdt, $address_data["m_id"], $order_type);
		$ary_return['data'] = $logistic_data;
		$ary_return['result'] = true;
		$ary_return['code'] = 200;
		$ary_return['message'] = '';
		return $ary_return;
	}
}
