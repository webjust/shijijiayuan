<?php
/**
 * 订单合并支付 控制器
 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
 * @date 2014-5-12
*/
class MergerPaymentAction extends CommonAction{

	/**
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	*/
	public function mergerOrderPage(){
		$mp_id = $this->_get('mp_id');
		$ary_result = D("MergerPayment")->getMergerOrders($mp_id);
		$this->assign('mp_id',$mp_id);
		$this->assign('orderList', $ary_result);
		$this->display();
	}

	/**
	 * 生成支付合并数据
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	*/
	function createMergerOrdersData(){
		$ary_params = array();
		$ary_post = $this->_post();
		$ary_return_data = $this->checkOrder($ary_post);
		if(empty($ary_return_data)){
			$this->ajaxReturn(array('status'=>false, 'msg'=>'无法完成合并支付！'));
		}
		$str_sn = mt_rand(100000, 999999);
		$mp_all_price = 0;
		$mp_id = time() . $str_sn;
		foreach($ary_return_data as $k=>$v){
			$ary_params[$k]['mp_id'] = $mp_id;
			$ary_params[$k]['o_id'] = $v['o_id'];
			$ary_params[$k]['o_pay'] = $v['o_pay'];
			$ary_params[$k]['o_all_price'] = $v['o_all_price'];
			$ary_params[$k]['mp_create_time'] = date('Y-m-d H:i:s', time());
			$mp_all_price += $v['o_all_price'];
			$result = D("merger_payment")->where(array('o_id'=>$v['o_id']))->getField("o_id");
			if($result){
				$bool = D("merger_payment")->where(array('o_id'=>$v['o_id']))->delete();
				if(FALSE === $bool){
					$this->ajaxReturn(array('status'=>false, 'msg'=>'合并订单表原有订单'. $v['o_id'] . '删除失败！'));
				}
			}
			$int_return = D("merger_payment")->data($ary_params[$k])->add();
			if(!$int_return){
				$this->ajaxReturn(array('status'=>false, 'msg'=>'订单' .$v['o_id'] . '生成合并支付失败！', 'error_code'=>'MergerPaymentAction_createMergerOrdersData_001'));
			}
		}
		$return = D("merger_payment")->where(array('mp_id'=>$mp_id))->data(array('mp_all_price'=>$mp_all_price))->save();
		if(FALSE === $return){
			$this->ajaxReturn(array('status'=>false, 'msg'=>'订单' .$v['o_id'] . '生成合并支付总金额失败！', 'error_code'=>'MergerPaymentAction_createMergerOrdersData_002'));
		}
		echo json_encode(array('status'=>true, 'mp_id'=>$mp_id));
	}
	
	/**
	 * 合并支付
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	*/
	public function doMergerPayment() {
		$ary_orders_items = array();
		$ary_orders_data = array();
		$mp_id = $this->_post('mp_id');
		$pc_id = $this->_post('pc_id');
		
		//根据合并支付mp_id找到所有的订单id
		$ary_result = D("MergerPayment")->getMergerOrders($mp_id);
		
		//拼接IN查询
		if(!empty($ary_result)){
			$where = array();
			$o_ids = array();
			foreach($ary_result as $v){
				$o_ids[] = $v['o_id'];
			}
			$where[C("DB_PREFIX") . 'orders.o_id'] = array("IN",$o_ids);
		}
		
		//组装索要的字段
		$search_field = array(
			C("DB_PREFIX") . 'orders.o_id',
            C("DB_PREFIX") . 'orders.o_all_price',
            C("DB_PREFIX") . 'orders.o_payment',
            C("DB_PREFIX") . 'orders.o_pay',
			C("DB_PREFIX") . 'orders.o_status',
            C("DB_PREFIX") . 'orders.m_id',
            C("DB_PREFIX") . 'orders.o_pay_status',
            C("DB_PREFIX") . 'orders.o_reward_point',
            C("DB_PREFIX") . 'orders.o_freeze_point'
        );
		
		$search_items_field = array(
			C("DB_PREFIX") . 'orders_items.pdt_id',
			C("DB_PREFIX") . 'orders_items.oi_nums',
			C("DB_PREFIX") . 'orders_items.oi_type',
			C("DB_PREFIX") . 'orders_items.fc_id',
			C("DB_PREFIX") . 'orders_items.g_id'
		);
		
		if(!empty($where[C("DB_PREFIX") . 'orders.o_id'])){
			$ary_orders_data = D('Orders')->getOrdersList($where, $search_field);
		}
		
		if(empty($ary_orders_data)){
			$this->error('合并支付订单不存在,请重新选择订单！',U('/Ucenter/Orders/pageList'));
		}
		
		$mp_all_price = D("MergerPayment")->getMergerOrders($mp_id,"mp_all_price");//合并支付总金额
		$o_all_price = 0;
		foreach($ary_orders_data as $k=>$v){
			$items_where['o_id'] = array("IN",$v['o_id']);
			//获取订单详情
			$ary_orders_items = D("Orders")->getOrdersItem($items_where, $search_items_field);
			foreach($ary_orders_items as $items){
				//查询库存,如果库存不足无法下单
				$ary_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
										   ->field('pdt_stock,' . 'gp.g_sn,oi_g_name')
										   ->where(array('gp.pdt_id'=>$items['pdt_id']))
										   ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
										   ->find();
				if(NULL === $ary_pdt_stock){
					$this->error("单号" . $v['o_id'] . '的货品已下架或已被删除！');
				}
				if($v['oi_type'] ==5 || $v['oi_type'] ==8){
					//没有结果
				}else{
					if(0 >= $ary_pdt_stock['pdt_stock']){
						//每张订单如果有商品库存足,就把改张订单从  合并支付表  删除   重新勾选订单
						$bool = D("merger_payment")->where(array('o_id'=>$v['o_id']))->delete();
						if(FALSE === $bool){
							M('', '', 'DB_CUSTOM')->rollback();
							$this->error('合并支付表删除售完商品失败！',U('Ucenter/Orders/pageList'));
						}
						$this->error('单号' . $v['o_id'] . '商品编码为' . $ary_pdt_stock['g_sn'] . '的商品已售完！',U('Ucenter/Orders/pageList'));
					}
				}
				$ary_orders_data[$k]['pdt_id'] =$items['pdt_id'];
				$ary_orders_data[$k]['oi_nums'] = $items['oi_nums'];
				$ary_orders_data[$k]['oi_type'] = $items['oi_type'];
				$ary_orders_data[$k]['fc_id'] = $items['fc_id'];
				$ary_orders_data[$k]['g_id'] = $items['g_id'];
			}
			$o_all_price += $v['o_all_price'];
			if(2 == $v['o_status']){
				//只要一张订单被强制作废就清空合并支付表  重新勾选订单
				$bool = D("merger_payment")->where(array('mp_id'=>$mp_id))->delete();
				if(FALSE === $bool){
					$this->error("清空合并支付表失败！");
				}
				$this->error('订单' . $v['o_id'] . '已被作废,合并支付订单表被强制删除,请重新下单！', U('Ucenter/Orders/pageList'));
			}
		}
		$o_all_price = sprintf("%.3f", $o_all_price);
		//判断订单金额是否被管理员编辑过
		if($mp_all_price[0]['mp_all_price'] != $o_all_price){
			$bool = D("merger_payment")->where(array('mp_id'=>$mp_id))->delete();
			if(FALSE === $bool){
				$this->error("删除合并支付表失败！");
			}
			$this->error('部分订单已被编辑过,清空合并支付订单表，请重新下单！',U('Ucenter/Orders/pageList'));
		}
		M('', '', 'DB_CUSTOM')->startTrans();
		//依次执行合并支付的订单
		foreach($ary_orders_data as $v){
			if(0 == $v['o_pay']){
				//全额支付
				$pay_stat = 0;
			}else if(0 == $v['o_pay'] && 0 < $v['o_all_price'] - $v['o_pay']){
				//定金支付
				$pay_stat = 1;
			}else if(0 != $v['o_pay'] && 0 < $v['o_all_price'] - $v['o_pay']){
				//尾款支付
				$pay_stat = 2;
			}else if(0 != $v['o_pay'] && 0 == $v['o_all_price'] - $v['o_pay']){
				//此订单已支付过
				D("Orders")->where(array('o_id'=>$v['o_id']))->delete();
				$this->error('订单' . $v['o_id'] . '已经支付！',U("/Ucenter/Orders/pageList"));
			}
			// 支付流程改造【团购】
			if ($v ['oi_type'] == '5') { // 团购订单
				$groupbuy = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
							'g_id' => $v ['g_id'],'deleted'=>0
						))->find();
				if (0 == $pay_stat) {
					// 团购全额支付
					//验证团购商品是否可以支付
					$is_pay = D('Groupbuy')->checkBulkIsBuy($v['m_id'],$groupbuy['gp_id']);
					if($is_pay['status'] == false){
						$this->error($is_pay['msg'], U('Ucenter/Orders/pageList/'));
					}
					$o_pay = $v ['o_all_price'];
					/**
					$gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
								'gp_id' => $groupbuy ['gp_id']
							))->getField('gp_now_number');
					M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'gp_id' => $groupbuy ['gp_id']
					))->save(array(
						'gp_now_number' => $gp_now_number + $v ['oi_nums']
					));
					**/
				} else if (1 == $pay_stat) {
					// 团购定金支付,获取定金
					$is_pay = D('Groupbuy')->checkBulkIsBuy($v['m_id'],$groupbuy['gp_id']);
					if($is_pay['status'] == false){
						$this->error($is_pay['msg'], U('Ucenter/Orders/pageList/'));
					}
					$o_pay = sprintf("%0.3f", $groupbuy ['gp_deposit_price'] * $v ['oi_nums']);
					/**
					$gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
								'gp_id' => $groupbuy ['gp_id']
							))->getField('gp_now_number');
					M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'gp_id' => $groupbuy ['gp_id']
					))->save(array(
						'gp_now_number' => $gp_now_number + $v ['oi_nums']
					));**/
				} else if (2 == $pay_stat) {
					// 尾款支付。检测当前时间是否在指定支付尾款时间内
					$gp_overdue_start_time = strtotime($groupbuy ['gp_overdue_start_time']);
					$gp_overdue_end_time = strtotime($groupbuy ['gp_overdue_end_time']);
					if ($gp_overdue_start_time > mktime()) {
						// 还未到支付尾款时间
						$this->error('单号' . $v['o_id'] . '请于' . date('Y年m月d日 H:i:s', $gp_overdue_start_time) . '后补交尾款',U('Ucenter/Orders/pageList'));
					} elseif (($gp_overdue_start_time < mktime()) && ($gp_overdue_end_time < mktime())) {
						// 支付尾款时间已过
						$this->error('单号' . $v['o_id'] . '补交尾款时间已过，请联系客服人员',U('Ucenter/Orders/pageList'));
					}
					$o_pay = sprintf("%0.3f", $v ['o_all_price'] - $v ['o_pay']);
				}
			}else if ($v['oi_type'] == '8') { //预售商品
				$presale = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
							'g_id' => $v ['g_id']
						))->find();
				if (0 == $pay_stat) {
					// 预售全额支付
					$o_pay = $v ['o_all_price'];
					$p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
								'p_id' => $presale ['p_id']
							))->getField('p_now_number');
					M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'p_id' => $presale ['p_id']
					))->save(array(
						'p_now_number' => $p_now_number + $v ['oi_nums']
					));
				} else if (1 == $pay_stat) {
					// 预售定金支付,获取定金
					$o_pay = sprintf("%0.3f", $presale ['p_deposit_price'] * $v ['oi_nums']);
					$p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
								'p_id' => $presale ['p_id']
							))->getField('p_now_number');
					M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'p_id' => $presale ['p_id']
					))->save(array(
						'p_now_number' => $p_now_number + $v ['oi_nums']
					));
				} else if (2 == $pay_stat) {
					// 尾款支付。检测当前时间是否在指定支付尾款时间内
					$p_overdue_start_time = strtotime($presale ['p_overdue_start_time']);
					$p_overdue_end_time = strtotime($presale ['p_overdue_end_time']);
					if ($p_overdue_start_time > mktime()) {
						// 还未到支付尾款时间
						$this->error('单号' . $v['o_id'] . '请于' . date('Y年m月d日 H:i:s', $p_overdue_start_time) . '后补交尾款');
					} elseif (($p_overdue_start_time < mktime()) && ($p_overdue_end_time < mktime())) {
						// 支付尾款时间已过
						$this->error('单号' . $v['o_id'] . '补交尾款时间已过，请联系客服人员', U('Ucenter/Orders/pageList'));
					}
					$o_pay = sprintf("%0.3f", $v ['o_all_price'] - $v ['o_pay']);
				}
			}else {
				$o_pay = sprintf("%0.3f", $v ['o_all_price'] - $v ['o_pay']);
			}
			
			//判断支付方式是否存在
			$Payment = D('PaymentCfg');
			if (!empty($pc_id)) {
				$v ['o_payment'] = $pc_id;
				$update_payment_res = D("Orders")->UpdateOrdersPayment($v['o_id'], $pc_id);
				if ($update_payment_res === false) {
					$this->error('订单表更新支付方式失败,请重新勾选订单！', U('Ucenter/Orders/pageList'));
				}
			}
			$info = $Payment->where(array(
						'pc_id' => $v ['o_payment']
					))->find();
			
			if (FALSE === $info) {
				$this->error('支付方式不存在，或不可用');
			}
			
			// 线下支付进erp
			if ($info ['pc_abbreviation'] != 'OFFLINE' && $info ['pc_abbreviation'] != 'DELIVERY' && $v ['pc_abbreviation'] != 'DELIVERY') {
				$Pay = $Payment::factory($info ['pc_abbreviation'], json_decode($info ['pc_config'], true));
				$result = $Pay->pay($v['o_id'], $v ['oi_type'], $o_pay, $pay_stat);
				writeLog($result, "order_pay.log");
				if (!$result ['result']) {
					$this->error($result ['message']);
				}
				$v ['o_pay'] = $o_pay;
				if (1 == $pay_stat) {
					// 如果是定金支付，支付状态为部分支付
					$v ['o_pay_status'] = 3;
				} else {
					$v ['o_pay_status'] = 1;
					//判断是否开启自动审核功能
					$IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
					if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1){
						$v['o_audit'] = 1;
					}
				}
				
				//更新订单信息
				$result_order = D("Orders")->orderPayment($v['o_id'], $v);
				
				if (!$result_order ['result']) {
					// 后续工作失败
					M('', '', 'DB_CUSTOM')->rollback();
					$this->error($result_order ['message'], U('Ucenter/Orders/pageList'));
				}else{
					if($v ['oi_type'] == '7'){
						/**
						$result_spike = D("Spike")->where(array('sp_id'=>$v['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
						if (FALSE === $result_spike) {
							// 后续工作失败 
							M('', '', 'DB_CUSTOM')->rollback();
							$this->error("秒杀更新失败！", U('Ucenter/Orders/pageList'));
						}**/
					}
				}

				$ary_member = session("Members");
				$ary_balance_info = array(
					'bt_id' => '1',
					'bi_sn' => time(),
					'm_id' => $ary_member ['m_id'],
					'bi_money' => $o_pay,
					'bi_type' => '1',
					'bi_payment_time' => date("Y-m-d H:i:s"),
					'o_id' => $v['o_id'],
					'bi_desc' => '订单支付',
					'single_type' => '2',
					'bi_verify_status' => '1',
					'bi_service_verify' => '1',
					'bi_finance_verify' => '1',
					'bi_create_time' => date("Y-m-d H:i:s")
				);
				$arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
				
				if (!$arr_res) {
					M('', '', 'DB_CUSTOM')->rollback();
					$this->error('生成支付明细失败，请重试...');
				} else {
					$arr_data = array();
					$str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
					$arr_data ['bi_sn'] = time() . $str_sn;
					$arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
								'bi_id' => $arr_res
							))->data($arr_data)->save();
					if (FALSE === $arr_result) {
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('更新支付明细失败，请重试...');
					}
					// 结余款调整单日志
					$add_balance_log ['u_id'] = 0;
					$add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
					$add_balance_log ['bvl_desc'] = '审核成功';
					$add_balance_log ['bvl_type'] = '2';
					$add_balance_log ['bvl_status'] = '2';
					$add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
					$result = M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log);
					if (FALSE === $result) {
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('生成结余款调整单日志失败，请重试...');
					}
					$add_balance_log ['bvl_type'] = '3';
					$result = M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log);
					if (false === $result) {
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
					$add_payment_serial ['o_id'] = $v['o_id'];
					$add_payment_serial ['ps_status'] = 1;
					$add_payment_serial ['pay_type'] = $pay_stat;
					$add_payment_serial ['ps_create_time'] = date('Y-m-d H:i:s');
					$ary_result = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_payment_serial);
					if (FALSE === $ary_result) {
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('生成支付明细失败，请重试...');
					}
				}
				//更新合并支付表 应支付金额状态
				$bool = D('MergerPayment')->where(array('o_id'=>$v['o_id']))->data(array('o_pay'=>$o_all_price))->save();
				if(FALSE === $bool){
					M()->rollback();
					$this->error("更新合并支付表已支付金额失败！", U('/Ucenter/Orders/pageList'));
				}
			} else {
				//货到付款、线下支付
			}
		}
		M('', '', 'DB_CUSTOM')->commit();
		$url = U("Ucenter/MergerPayment/paymentSuccess", array(
			'mp_id' => $mp_id
				));
		redirect($url);
		exit();
	}
	
	/**
	 * 合并支付成功
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-14
	*/
	public function paymentSuccess() {
		$this->getSubNav(1, 0, 40);
		$mp_id = $this->_get('mp_id');
		$mp_all_price = D("MergerPayment")->where(array('mp_id'=>$mp_id))->getField("mp_all_price");
		// 账户余额
        $ary_member_balance = D('Members')->GetBalance(array(
            'm_id' => $_SESSION ['Members'] ['m_id']
                ), array(
            'm_balance'
                ));
		$this->assign('mp_id', $mp_id);
		$this->assign('balance', $ary_member_balance ['m_balance']);
		$this->assign('mp_all_price', $mp_all_price);
		$this->display();
	}
	
	/**
	 * 判断订单是否是未支付且是预存款且是否有效
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	*/
	private function checkOrder($ary_post){
		if(!empty($ary_post)){
			foreach($ary_post as $k=>&$v){
				if(2 == $v['o_status']){
					unset($ary_post[$k]);
				}else if(4 == $v['o_status']){
					unset($ary_post[$k]);
				}else if(1 != $v['o_payment']){
					$v['o_payment'] = 1;
				}else if(0 > $v['o_all_price']){
					unset($ary_post[$k]);
				}else if(0 != $v['o_pay_status']){
					unset($ary_post[$k]);
				}
			}
			return $ary_post;
		}
	}
}