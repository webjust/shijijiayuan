<?php

/**
 * 前台售后列表
 * @stage 7.8.2
 * @package Action
 * @subpackage Wap
 * @author Hcaijin
 * @date 2015-03-31
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class AftersaleAction extends WapAction {

	/**
	 * 售后订单控制器初始化
	 */
	public function _initialize() {
		parent::_initialize();
        $m_id = $_SESSION['Members']['m_id'];
        if(empty($m_id) && !isset($m_id)){
            $string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			$this->error(L('NO_LOGIN'), U('/Wap/User/Login') . '?redirect_uri=' . urlencode($string_request_uri));
        }
	}

	/**
	 * 退款/退货订单列表页
	 */
	public function pageList() {
		$ary_where = array();
		$ary_where['fx_orders.m_id'] = $_SESSION['Members']['m_id'];
		$orders = M('orders',C('DB_PREFIX'),'DB_CUSTOM');
		$ary_chose = array();
		//订单号
		$o_id = trim($this->_post('oid'));
		$ary_chose['fx_orders.o_id'] = trim($this->_post('oid'));
		if (isset($o_id) && !empty($o_id)) {
			$ary_where['fx_orders.o_id'] = array('LIKE', '%' . $o_id . '%');
		}
		//退款/退货单号
		$ary_chose['or_return_sn'] = trim($this->_post('or_return_sn'));
		if (isset($ary_chose['or_return_sn']) && !empty($ary_chose['or_return_sn'])) {
			$ary_where['or_return_sn'] = $ary_chose['or_return_sn'];
		}
		//售后类型
		$ary_chose['or_refund_type'] = $this->_post('or_refund_type');
		if (isset($ary_chose['or_refund_type']) && $ary_chose['or_refund_type'] !='All') {
			$ary_where['or_refund_type'] = $ary_chose['or_refund_type'];
		}
		//售后状态
		$ary_chose['or_processing_status'] = $this->_post('or_processing_status');
		if (isset($ary_chose['or_processing_status']) && $ary_chose['or_processing_status'] !='All') {
			$ary_where['or_processing_status'] = $ary_chose['or_processing_status'];
		}
		//物流单号
		$ary_chose['or_return_logic_sn'] = trim($this->_post('or_return_logic_sn'));
		if (isset($ary_chose['or_return_logic_sn']) && !empty($ary_chose['or_return_logic_sn'])) {
			$ary_where['or_return_logic_sn'] = $ary_chose['or_return_logic_sn'];
		}
		//过滤掉子订单
		$ary_where['initial_tid'] = 0;
		//申请时间
		$ary_chose['from_time'] = $this->_post('from');
		$ary_chose['end_time'] = $this->_post('end');
		if (isset($ary_chose['from_time']) && !isset($ary_chose['end_time'])  && !empty($ary_chose['from_time'])) {
			$ary_where['or_create_time'] = array('EGT', $ary_chose['from_time']);
		} else if (!isset($ary_chose['from_time']) && isset($ary_chose['end_time']) && !empty($ary_chose['end_time'])) {
			$ary_where['or_create_time'] = array('ELT', $ary_chose['end_time']);
		} else if (isset($ary_chose['from_time']) && isset($ary_chose['end_time']) && !empty($ary_chose['from_time']) && !empty($ary_chose['end_time'])) {
			$ary_where['_string'] = "(or_create_time >= '{$ary_chose['from_time']}' and or_create_time <= '{$ary_chose['end_time']}')";
		}
		//第三方来源单号
		$ary_chose['our_source_sn'] = trim($this->_post('our_source_sn'));
		if (isset($ary_chose['our_source_sn']) && !empty($ary_chose['our_source_sn'])) {
			//$ary_where['fx_orders.o_source_id'] = array('LIKE', '%' . $ary_chose['our_source_sn'] . '%');
			$ary_where['fx_orders.o_source_id'] = trim($ary_chose['our_source_sn']);
            $count = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')
                ->join('fx_orders on fx_orders_refunds.oi_id = fx_orders.oi_id')
                ->where($ary_where)->count();
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
            $ary_or_id = array();
            $ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')
                ->join('fx_orders on fx_orders_refunds.o_id = fx_orders.o_id')
                ->where($ary_where)
				->field('fx_orders.o_id,fx_orders.o_source_id,fx_orders_refunds.*')
				->order('or_create_time desc')->select();
        }else{
            $count = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
            $obj_page = new Page($count, 10);
            $page = $obj_page->show();
            $ary_or_id = array();
            //$ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->order('or_create_time desc')->select();
			$ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')
			->join('fx_orders on fx_orders_refunds.o_id = fx_orders.o_id')
			->where($ary_where)
			->field('fx_orders.o_id,fx_orders.o_source_id,fx_orders.m_id,fx_orders_refunds.*')
			->order('or_create_time desc')->select();
			//echo  M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		}
		foreach($ary_afersale as $k=>$v){
			//退款
			if($v['or_refund_type'] == 1){
				switch($v['or_processing_status']){
					case 0:
						$ary_afersale[$k]['refund_status'] = '退款中';
						break;
					case 1:
						$ary_afersale[$k]['refund_status'] = '退款成功';
						break;
					case 2:
						$ary_afersale[$k]['refund_status'] = '退款驳回';
						break;
					default:
						$ary_afersale[$k]['refund_status'] = ''; //没有退款
				}

			}
			//退货
			elseif($v['or_refund_type'] == 2){

				switch($v['or_processing_status']){
					case 0:
						$ary_afersale[$k]['refund_goods_status'] = '退货中';
						break;
					case 1:
						$ary_afersale[$k]['refund_goods_status'] = '退货成功';
						break;
					case 2:
						$ary_afersale[$k]['refund_goods_status'] = '退货驳回';
						break;
					default:
						$ary_afersale[$k]['refund_goods_status'] = ''; //没有退款
				}
			}
			$ary_afersale[$k]['or_id'] = $v['or_id'];
			$ary_or_id[] = $v['or_id'];
		}

		if($ary_or_id){
			$ary_refund_items = M('orders_refunds_items')->where($ary_or_id)->select();
			if($ary_refund_items){
				$ary_temp_items = array();
				foreach($ary_refund_items as $val){
					if(!isset($ary_refund_items[$val['or_id']])){
						$ary_refund_items[$val['or_id']]['num'] = $val['ori_num'];
					}
					else{
						$ary_refund_items[$val['or_id']]['num'] += $val['ori_num'];
					}
				}
				//组装退款货商品数量
				foreach($ary_afersale as $k=>$v){
					if(isset($ary_refund_items[$v['or_id']])){
						$ary_afersale[$k]['refund_unms'] = $ary_refund_items[$v['or_id']]['num'];
					}
					else{
						$ary_afersale[$k]['refund_unms'] = 0;
					}
				}
			}
		}
		$this->assign('chose',$ary_chose);
		$this->assign('o_id',$o_id);
		$this->assign('ary_afersale',$ary_afersale);
		$this->assign('page', $page);    //赋值分页输出
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}

	/**
	 * 申请退款/退货页面
	 */
	public function pageAdd() {
        $member = D('WapInfo')->memberInfo();
		$o_id = $this->_get('oid');
		$refund_type = $this->_get('refund_type');
		$ary_where = array('fx_orders.o_id' => $o_id);
		//$ary_where['oi_refund_status'] = array('in', '1,6');
		//判断是退款/退货。1是退款，2是退货
		if ($refund_type == 1) {
			$ary_where['fx_orders_items.oi_ship_status'] = array('NEQ', 2);
			//申请退款的商品数量
			$int_refunds = 0;
			$ary_refunds = M('view_refunds',C('DB_PREFIX'),'DB_CUSTOM')
			->join('fx_view_orders on fx_view_refunds.oi_id = fx_view_orders.oi_id')
			->where(array('fx_view_refunds.o_id' => $o_id))->select();
		}
		if ($refund_type == 2) {
			//申请退货的商品数量
			//$ary_delivery = M('view_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id' => $o_id))->select();
			$ary_where['fx_orders_items.oi_ship_status'] = 2;
		}
		$ary_where['fx_orders_items.oi_refund_status'] = array('in',array(1,6));
        $ary_where['fx_members.m_id'] = $_SESSION['Members']['m_id'];
		//满足退款、退货的商品
		//$ary_orders = D('Orders')->getOrdersInfo($ary_where);
		$ary_field = array(
			'fx_orders.o_id',
			'fx_orders.o_pay',
			'fx_orders.o_all_price',
            'fx_orders.o_all_price',
			'fx_orders.o_pay_status',
			'fx_orders.o_goods_all_price',
			'fx_orders.o_cost_freight',
			'fx_orders.o_tax_rate',
			'fx_orders_items.oi_id',	
			'fx_orders_items.g_id',	
            'fx_orders_items.pdt_id',
			'fx_orders_items.oi_price',
			'fx_orders_items.g_sn',
			'fx_orders_items.oi_g_name',
			'fx_orders_items.oi_nums',
			'fx_orders_items.oi_ship_status',
			'fx_orders_items.pdt_sale_price',
			'fx_orders_items.promotion_price',
        );
		$ary_orders = D('Orders')->getOrdersData($ary_where,$ary_field);
		//print_r($ary_orders);die();
		if(empty($ary_orders)){
			$this->error('您已提交退换货单申请，请耐心等待！');exit;
		}
		//echo D()->getLastSql();exit;
		$total_price = 0;
		if (!empty($ary_orders) && is_array($ary_orders)) {
			foreach ($ary_orders as $k => $v) {
				$ary_orders[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id']);
				$ary_goods_pic = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){//七牛图片显示
					$ary_orders[$k]['g_picture'] =D('QnPic')->picToQn($ary_goods_pic['g_picture']); 
				}else{
					$ary_orders[$k]['g_picture'] = '/'.ltrim($ary_goods_pic['g_picture'],'/');
				}
				$pay_order_info = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field('oi_coupon_menoy,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money,oi_type,promotion_price')->where(array('oi_id' => $v['oi_id']))->find();
				if($pay_order_info['oi_type'] == 9){
					$this->error('试用商品不允许申请退货退款');exit();
				}
				$ary_orders[$k] = array_merge($ary_orders[$k],$pay_order_info);
				$ary_orders[$k]['promotion_price'] = $pay_order_info['oi_coupon_menoy']+$pay_order_info['oi_bonus_money']+$pay_order_info['oi_cards_money']+$pay_order_info['oi_jlb_money']+$pay_order_info['oi_point_money']+$pay_order_info['promotion_price'];
				$total_price +=$v['oi_price']*$v['oi_nums']-$ary_orders[$k]['promotion_price'];
			}
		}
		// $where = array('o_id'=>$o_id,'m_id'=>$_SESSION['Members']['m_id']);
		$ary_orders_info = $ary_orders[0];
        if($ary_orders_info['oi_ship_status'] == 2){
			// 判断是否开启退货包含运费
			$ary_data = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY');
            if($ary_orders_info['o_pay']>=$total_price){
                $ary_orders_info['refund_pay'] = $total_price;
				if(isset($ary_data['ALLOW_REFUND_DELIVERY']) && $ary_data['ALLOW_REFUND_DELIVERY'] == 1){
					$ary_orders_info['refund_pay'] += $ary_orders_info['o_cost_freight'];
					$ary_orders_info['allow_refund_delivery']=true;
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
		//订单支付状态
		$ary_pay_status = array('o_pay_status' => $ary_orders_info['o_pay_status']);
		$str_pay_status = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_pay_status);
		$ary_orders_info['str_pay_status'] = $str_pay_status;
		//订单状态
		$ary_orders_status = D('Orders')->getOrdersStatus($o_id);
		//退款
		$ary_orders_info['refund_status'] = $ary_orders_status['refund_status'];
		//退货
		$ary_orders_info['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
		//发货
		$ary_orders_info['deliver_status'] = $ary_orders_status['deliver_status'];
		$data['value'] = '';
		$data['content'] = '';
		$data = D('SysConfig')->getCfgByModule('GY_ORDER_AFTERSALE_CONFIG');
		if(!empty($data) && is_array($data)){
			$sc_value = explode(',', $data['SETAFTERSALE']);
			$data['value'] = $sc_value[0];
			$data['content'] = $sc_value[1];
		}
		
		//退款 退货 理由 后台读取
		$refund_reason = M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->field('rr_name')->where(array('rr_status'=>1,'rr_is_display'=>1,'rr_show_type'=>$refund_type))->order("rr_order")->select();
		$ary_reason= array();
		foreach($refund_reason as $key=>$value){
			array_push($ary_reason,$value['rr_name']);
		}
        /* 取出扩展自定义属性项字段 start*/
        $ary_extend_data = D('RefundsSpec')->getSpecByType($refund_type);
        //print_r($ary_extend_data);exit;
        $this->assign('ary_extend_data', $ary_extend_data);
        /* 取出扩展自定义属性项字段 end*/
		$this->assign("data",$data);
		$this->assign("info",$member);
		$this->assign('products_info', $ary_orders);
		$this->assign('refund_type', $refund_type); //1:退款  2:退货
		$this->assign('ary_orders', $ary_orders_info);
		$this->assign('ary_reason', $ary_reason);//退款退货原因
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}

	/**
	 * 检测是否已经提交
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-5
	 */
	public function checkOrderRefunds($params){
		$ary_where = array('o_id' => $params['o_id']);
		//$ary_where['oi_refund_status'] = array('in', '1,6');
		//判断是退款/退货。1是退款，2是退货
		if ($params['or_refund_type'] == 1) {
			$ary_where['oi_ship_status'] = array('NEQ', 2);
		}
		if ($params['or_refund_type'] == 2) {
			//申请退货的商品数量
			//$ary_delivery = M('view_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id' => $o_id))->select();
			$ary_where['oi_ship_status'] = 2;
		}
		$ary_where['oi_refund_status'] = array('in',array(1,6));
		//满足退款、退货的商品
		$ary_orders = D('Orders')->getOrdersInfo($ary_where);
		if(empty($ary_orders)){
			$this->error('您已提交退换货单申请，请耐心等待！');exit;
		}
		return $ary_orders[0]['o_cost_freight'];
	}

	/**
	 * 申请退款/退货页面
	 * @author czy<chenzongyao@guanyisoft.com>
	 * @date 2013-03-22
	 */
	public function doAdd() {
		//获取页面POST过来的数据
		$ary_data = $this->_request();
		$o_cost_feight = $this->checkOrderRefunds($ary_data);
          //echo "<pre>";print_r($ary_data);exit;
		//数据操作模型初始化
		$obj_refunds = D('OrdersRefunds');
		$date = date('Y-m-d H:i:s');
        
        //判断是否提交过（只能申请一次）
        // $ary_refunds = $obj_refunds->where(array('o_id'=>$ary_data['o_id']))->select();
        // if($ary_data['o_id'] == $ary_refunds['o_id']){
        //     $this->error('您已申请过，请耐心等待处理！');
        // }
		//验证是否传递要退款/退货的订单号
		if (!isset($ary_data['o_id']) || empty($ary_data['o_id'])) {
			$this->error('缺少订单号');
		}
      
        $item_where['o_id'] = $ary_data['o_id'];
        if(!empty($ary_data['checkSon'])){
            $item_where['oi_id'] = array('in',$ary_data['checkSon']);
        }
        $orders_items_info = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where($item_where)->select();
       // echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();die;
        $total_price = 0;
        if (!empty($orders_items_info) && is_array($orders_items_info)) {
			foreach ($orders_items_info as $k => $v) {
				$item_promotion['promotion_price'] = $v['oi_coupon_menoy']+$v['oi_bonus_money']+$v['oi_cards_money']+$v['oi_jlb_money']+$v['oi_point_money']+$v['promotion_price'];
				$total_price += $v['oi_price']*$v['oi_nums'] - $item_promotion['promotion_price'];
			}
		}
		
		//退款时退运费
		$result_price = $total_price;
		if($ary_data['or_refund_type']==1){
			$result_price = $result_price+$o_cost_feight;
		}else{
			$refund_delivery = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY');
			if(isset($refund_delivery['ALLOW_REFUND_DELIVERY']) && $refund_delivery['ALLOW_REFUND_DELIVERY'] == 1){
				$result_price = $result_price+$o_cost_feight;
			}		
		}
		//跨境贸易
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
		if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
			$orders_res = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->field('o_tax_rate')->where(array('o_id'=>$ary_data['o_id']))->find();
			if($orders_res['o_tax_rate']>0){
				$result_price += $orders_res['o_tax_rate'];
			}
		}
		if($_POST["application_money"] > $result_price) {
			$this->error("退款金额不合法");
			exit;
		}
			
		//erp退款退货标志  2退款:3  退货:
		$refund_type = 0;

		//售后单据基本信息
		$ary_refunds = array(
			'o_id' => $ary_data['o_id'],
			'm_id' => $_SESSION['Members']['m_id'],
			'or_money' => sprintf('%.2f',$ary_data['application_money']),
			'or_refund_type' => $ary_data['or_refund_type'],
			'or_create_time' => $date,
			'm_name' => $_SESSION['Members']['m_name'],
			'or_buyer_memo'=>$this->_post('or_buyer_memo','htmlspecialchars','')
		);
		//已经退货商品
		$ary_refunds_items = array();
		//要退货或者退款商品 - 获取此订单的订单明细数据
		$ary_orders_items = D('OrdersItems')->field('oi_id,o_id,pdt_id,oi_price,oi_nums,g_sn,oi_g_name,erp_id')->where(array('o_id'=>$ary_data['o_id']))->select();
		
		$ary_temp_items = array();
		foreach($ary_orders_items as $val){
			$ary_temp_items[$val['oi_id']] = $val;
		}

		//区分不同的售后逻辑进行退款
		if($ary_data['or_refund_type']==1){
			//退款时此订单未发货，则退款金额如果用户输入，以用户输入为准
			//如果用户没有输入  或者输入的退款金额不合法，则取订单的付款金额
			//TODO：前端需要对用户输入退款金额的数字进行验证
			$ary_refunds['or_refund_type'] = 1;
			//退款金额的处理：判断退款金额是否合法
			if(!isset($_POST["application_money"]) || !is_numeric($_POST["application_money"]) || $_POST["application_money"] < 0){
				$this->error("退款金额不合法：必须是一个大于等于0的数字。");
			}
			$ary_refunds['or_money'] = $_POST["application_money"];
				
			$refund_type = 2;
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==0){
			//未收到货，产生退款单,退款金额由双方协商确定
			//此时售后类型为退款，退款金额由双方协商确定
			$ary_refunds['or_refund_type'] = 1;
			$refund_type = 2;
			//对用户申请的退款金额合法性进行判断
			if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$this->error('未收到货，产生退款单,请正确填写退款金额');
			}
			$ary_refunds['or_money'] = $ary_data['application_money'];
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==0){
			//已收到货，且无需退货，退款金额由双方协商确定，生成的单据为退款单
			//这种情况是考虑到买家买到货以后不满意，退部分金额，此时售后类型也是退款
			$ary_refunds['or_refund_type'] = 1;
			$refund_type = 2;
			//对用户申请的退款金额合法性进行判断
			if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$this->error('已收到货且无需退货，产生退款单,请正确填写退款金额');
			}
			$ary_refunds['or_money'] = $ary_data['application_money'];
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==1){
		     //已收到货，且需要换货，无须退款金额
             //$ary_refunds['or_money'] = 0.00;
             //此处改造成退货
            if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$this->error('已收到货且需退货，产生退货单,请正确填写退货金额');
			}
             $ary_refunds['or_money'] = $ary_data['application_money'];
			//已收到货，需退货，退款金额由选择商品确定，生成的单据为退货单
			if(!isset($ary_data['checkSon']) || empty($ary_data['checkSon'])){
				$this->error('请选择您要退货的商品。');
			}
				
			//TODO:如果勾选退货的商品为积分商城商品，则不允许退货！
				
			//买家将商品寄回时的物流单号，可不填（不填是由于买家自提或者上门送回的情况）
			$ary_refunds["or_return_logic_sn"] = (isset($ary_data['od_logi_no']) && "" != trim($ary_data['od_logi_no']))?trim($ary_data['od_logi_no']):"";
			//售后申请操作类型为退货
			$ary_refunds['or_refund_type'] = 2;
			$refund_type = 3;
		}
      
		//对用户申请的售后请求进行验证，分以下几种情况
		if($ary_refunds['or_refund_type'] == 1){
			/**
			 * 第一种情况：退款：
			 * 如果未发货，应该是一次性退全款；
			 * 如果已发货，且退款时不需要退货（买家对商品不满意，双方达成一致需要补偿的）；
			 * 系统中有且仅有一张关于此订单的退款单
			 */
			$ary_where = array(
				'o_id'=>$ary_data['o_id'],
				'm_id' => $_SESSION['Members']['m_id'],
				'or_processing_status'=>array('neq',2),
				'or_refund_type'=>array('eq',1)
			);
			$ary_refunds_orders = D('OrdersRefunds')->where($ary_where)->select();
			if(false === $ary_refunds_orders){
				$this->error("无法验证此订单是否已经存在退款单。", U("Ucenter/Aftersale/pageList"));
			}
			if(is_array($ary_refunds_orders) && count($ary_refunds_orders)>0){
				$this->error('已存在此订单对应的退款单，不能重复退款', U("Ucenter/Aftersale/pageList"));
			
			}
		}elseif($ary_refunds['or_refund_type'] == 2 && $ary_data['sh_radio'] == 1 && $ary_data['th_radio'] == 1){
			/**
			 * 第二种情况：退货并且用户已收到货且需要退货
			 * 此时需要对退货的商品进行验证：退货数量不能超过（此SKU的购买量-为作废已申请退货数量）
			 * TODO:退款此处先走不通。。。。待调试
			 */
              
			//商品可能部分退掉进行商品数量判断
			foreach ($ary_data['checkSon'] as $v) {
				if(!empty($ary_data['inputNum'][$v]) && isset($ary_returns_temp[$v])) {
					if(!ctype_digit($ary_data['inputNum'][$v])) $this->error("退货数量填写需正整数");
					if($ary_data['inputNum'][$v]>$ary_returns_temp[$v]['nums']) $this->error("商品编号是{$ary_returns_temp[$v]['g_sn']}退货数量不能大于购买商量");
					if(($ary_data['inputNum'][$v] + $ary_returns_temp[$v]['num'] )> $ary_returns_temp[$v]['nums']){
						$str_th_sum = intval($ary_returns_temp[$v]['nums'] - $ary_returns_temp[$v]['num']);
						if($str_th_sum>0){
							$this->error("商品编号是{$ary_returns_temp[$v]['g_sn']}的退货数量只能退{$str_th_sum}件");
						}
						else{
							$this->error("商品编号是{$ary_returns_temp[$v]['g_sn']}的已经退过货，不能重复退货");
						}
					}
				}
			}
			$ary_where = array(
				'fx_orders_refunds.o_id'=>$ary_data['o_id'],
				'fx_orders_refunds.m_id' => $_SESSION['Members']['m_id'],
				'fx_orders_refunds.or_processing_status'=>array('neq',2),
				'fx_orders_refunds.or_refund_type'=>2
			);
			$ary_returns_orders = D('OrdersRefunds')
			->field('fx_orders_items.pdt_id,fx_orders_items.oi_nums,fx_orders_refunds_items.ori_num,fx_orders_items.g_sn')
			->join('left join fx_orders_refunds_items on fx_orders_refunds.or_id = fx_orders_refunds_items.or_id')
			->join(" fx_orders_items ON fx_orders_refunds_items.oi_id=fx_orders_items.oi_id")
			->where($ary_where)
			->select();


			if($ary_returns_orders){
				//已经加入的退货单商品详情
				$ary_returns_temp = array();
				foreach($ary_returns_orders as $val) {

					if(!isset($ary_returns_temp[$val['pdt_id']])){
						$ary_returns_temp[$val['pdt_id']]['num'] = $val['ori_num'];//已退货的货号商品总数
						$ary_returns_temp[$val['pdt_id']]['nums'] = $val['oi_nums'];//此订单货号总数
						$ary_returns_temp[$val['pdt_id']]['g_sn'] = $val['g_sn'];
					}
					else{
						$ary_returns_temp[$val['pdt_id']]['num'] += $val['ori_num'];
					}
				}
					

			}
			else{
				foreach ($ary_data['checkSon'] as $v) {

					if(!empty($ary_data['inputNum'][$v]) && isset($ary_temp_items[$v])) {
						if(!ctype_digit($ary_data['inputNum'][$v])){
                            $this->error("退货数量填写需正整数");
                        }
						if($ary_data['inputNum'][$v]>$ary_temp_items[$v]['oi_nums']){
                            $this->error("商品编号是{$ary_temp_items[$v]['g_sn']}退货数量不能大于购买商量");
                        }
					}
				}
			}
         
		}
        if(!empty($ary_data['extend_field_0']))
            $ary_refunds['or_picture'] = $ary_data['extend_field_0'];
        $ary_refunds['or_reason'] = $ary_data['ary_reason'];
        $ary_refunds['or_return_sn'] = strtotime("now");
		//售后数据存入数据库  需要启用事务机制
		M('', '', 'DB_CUSTOM')->startTrans();
		$ary_refunds['or_update_time'] = date('Y-m-d H:i:s');

		//插入退款主表
		$int_or_id = D('OrdersRefunds')->add($ary_refunds);
		if (false === $int_or_id) {
			M('', '', 'DB_CUSTOM')->rollback();
			$this->error('售后申请提交失败。');
		}
        
        /*附加属性组装数据 start*/
        $ary_extend_temp = array();
       
        $ary_extend_data=D('RefundsSpec')->getSpecByType($ary_data['or_refund_type']);
       
        foreach($ary_extend_data as $val) {
            switch($val['gs_input_type']){
                case 1://文本框
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        //echo $ary_data['extend_field_'.$val['gs_id']];var_dump(trim(htmlspecialchars($ary_data['extend_field_'.$val['gs_id']],'ENT_QUOTES')));exit;
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>trim($ary_data['extend_field_'.$val['gs_id']]));
                      }
                   break;
                case 2:
                    if($_FILES['upload_file_'.$val['gs_id']]['name']){
			            @mkdir('./Public/Uploads/' . CI_SN.'/images/'.date('Ymd').'/');
				    	import('ORG.Net.UploadFile');
						$upload = new UploadFile();// 实例化上传类
						$upload->maxSize  = 3145728 ;// 设置附件上传大小
						$upload->allowExts = array('rar', 'zip');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
						$upload->savePath =  './Public/Uploads/'.CI_SN.'/images/'.date('Ymd').'/';// 设置附件上传目录
						if(!$upload->upload()) {// 上传错误提示错误信息
							$this->error($upload->getErrorMsg());
						}else{// 上传成功 获取上传文件信息
							$info =  $upload->getUploadFileInfo();
							$ary_data['extend_field_'.$val['gs_id']] = '/Public/Uploads/'.CI_SN.'/images/' .date('Ymd').'/'. $info[0]['savename'];
						}
			    	}
                   //附件
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        //$ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>'/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$ary_data['extend_field_'.$val['gs_id']]),'/')));
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>$ary_data['extend_field_'.$val['gs_id']]);
				      }
                   break;
                case 3://文本域
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>trim($ary_data['extend_field_'.$val['gs_id']],'ENT_QUOTES'));
                      } 
                   break; 
                default:
                  break;
            }
        }
       
        if(count($ary_extend_temp)>0) {
                $int_return_refund_spec = D('RelatedRefundSpec')->addAll($ary_extend_temp);
                //var_dump($int_return_refund_spec);exit;
			     if (false == $int_return_refund_spec) {
				        M('', '', 'DB_CUSTOM')->rollback();
				        $this->error('批量插入自定义属性失败');
			   }
        }
      
        /*附加属性组装数据 end*/

		//自动生成售后单据编号，单据编号的规则为20130628+8位单据ID（不足8位左侧以0补全）
		$int_tmp_or_id = $int_or_id;
		$or_return_sn = date("Ymd") . sprintf('%07s',$int_tmp_or_id);
		$array_modify_data = array("or_return_sn"=>$or_return_sn);
		$mixed_result = D('OrdersRefunds')->where(array("or_id"=>$int_or_id,'or_update_time'=>date('Y-m-d H:i:s')))->save($array_modify_data);
		if(false === $mixed_result){
			M('', '', 'DB_CUSTOM')->rollback();
			$this->error('售后申请提交失败。CODE:CREATE-REFUND-SN-ERROR.');
		}
		//插入明细表
		$ary_refunds_items = array();
		if($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==1){
			$or_money = 0;
			//商品可能部分退掉
			foreach ($ary_data['checkSon'] as $v) {
				if(!empty($ary_data['inputNum'][$v]) && isset($ary_temp_items[$v])) {
					$ary_refunds_items[] = array(
							'o_id' => $ary_temp_items[$v]['o_id'],
							'or_id' => $int_or_id,
							'oi_id' => $ary_temp_items[$v]['oi_id'],
							'ori_num' => $ary_data['inputNum'][$v],
							'erp_id' =>$ary_temp_items[$v]['erp_id']
					);
					$or_money +=  $ary_data['inputNum'][$v]*$ary_temp_items[$v]['oi_price']/$ary_temp_items[$v]['oi_nums'];

				}
			}
            
            //如果申请退货并需要换货，无须添加退款金额
			/*$res = D('OrdersRefunds')->where(array('or_id'=>$int_or_id,'or_update_time'=>date('Y-m-d H:i:s')))->save(array('or_money'=>$or_money));
			if(!$res){
				M('', '', 'DB_CUSTOM')->rollback();
				$this->error('更新退货主表金额失败','',true);
			}*/
				
			//批量插明细表
			$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);

			if (false === $int_return_refunds_itmes) {
				M('', '', 'DB_CUSTOM')->rollback();
				$this->error('批量插入明细失败');
			}
            
            //更改订单详情表商品退货状态
            foreach ($ary_data['checkSon'] as $oi_id){
                if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'oi_id'=>$oi_id))->data(array('oi_refund_status'=>3))->save()){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('更新退货状态失败');
                }
            }
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==0){
            $or_money = 0;
            foreach ($ary_temp_items as $v) {
                $ary_refunds_items[] = array(
                            'o_id' => $v['o_id'],
                            'or_id' => $int_or_id,
                            'oi_id' => $v['oi_id'],
                            'ori_num' => $v['oi_nums'],
                            'erp_id' =>$v['erp_id']
                );
                $or_money +=  $v['oi_price']*$v['oi_nums'];
            }
			//跨境贸易
			if(isset($orders_res['o_tax_rate']) && $orders_res['o_tax_rate']>0){
				$or_money += $orders_res['o_tax_rate'];
			}
            //print_r($ary_refunds_items);exit;
            //批量插明细表
            //获取物流费用
            $o_cost_freight = D('Orders')->where(array('o_id'=>$ary_data['o_id']))->getField('o_cost_freight');
			if(($or_money+$o_cost_freight)>=$ary_data['application_money']) {
				if($ary_refunds_items){
					$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);
					if (!$int_return_refunds_itmes) {
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('批量插入明细失败');
					}
				}
			}else {
				//暂时隐藏
				$this->error('输入退款金额必须小于订单商品总金额');
			}
            //更改订单详情表商品退款状态

            if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id']))->data(array('oi_refund_status'=>2))->save()){
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error('更新退货状态失败');
            }
            
        }elseif($ary_data['or_refund_type']==1){
			/* by Mithern 退款时不需要想明细表写入数据 */
			/*	$or_money = 0;
			 foreach ($ary_temp_items as $v) {
				$ary_refunds_items[] = array(
				'o_id' => $v['o_id'],
				'or_id' => $int_or_id,
				'oi_id' => $v['oi_id'],
				'ori_num' => $v['oi_nums']
				);
				$or_money +=  $v['oi_price'];
				}

				$res = D('OrdersRefunds')->where(array('or_id'=>$int_or_id))->save(array('or_money'=>$or_money));
				if(!$res){
				$obj_refunds->rollback();
				$this->error('更新退款主表金额失败');
				}*/
			//
			$or_money = 0;
			foreach ($ary_temp_items as $v) {
				$ary_refunds_items[] = array(
							'o_id' => $v['o_id'],
							'or_id' => $int_or_id,
							'oi_id' => $v['oi_id'],
							'ori_num' => $v['oi_nums'],
							'erp_id' =>$v['erp_id']
				);
				$or_money +=  $v['oi_price']*$v['oi_nums'];
			}
			//跨境贸易
			if(isset($orders_res['o_tax_rate']) && $orders_res['o_tax_rate']>0){
				$or_money += $orders_res['o_tax_rate'];
			}
			//批量插明细表
            //获取物流费用
            $o_cost_freight = D('Orders')->where(array('o_id'=>$ary_data['o_id']))->getField('o_cost_freight');
			if(($or_money+$o_cost_freight)>=$ary_data['application_money']) {
				if($ary_refunds_items){
					$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);
					if (!$int_return_refunds_itmes) {
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('批量插入明细失败');
					}
				}
			}else {
				//暂时隐藏
				$this->error('输入退款金额必须小于订单商品总金额');
			}
            //更改订单详情表商品退款状态
 
            if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id']))->data(array('oi_refund_status'=>2))->save()){
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error('更新退货状态失败');
            }
            
		}

		//用户提示语定义
		$str_type = '售后';
		switch($refund_type){
			case 2:
				$str_type = '退款';
				break;
			case 3:
				$str_type = '退货';
				break;
		}
		//判读是否需要拆分
		$orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
		$order_info = $orders->where(array('o_id'=>$ary_data['o_id']))->find();
		$resdata1 = D('SysConfig')->getCfg('ORDERS_REMOVE','ORDERS_REMOVE','1','是否开启订单拆分');
		$resdata2 = D('SysConfig')->getCfg('ORDERS_REMOVETYPE','ORDERS_REMOVETYPE','1','订单拆分方式(1:自动拆分;0:手动拆分)');
		if(($resdata1['ORDERS_REMOVE']['sc_value'] == '1') && ($resdata2['ORDERS_REMOVETYPE']['sc_value'] == '1')){
			if($order_info['is_diff'] == '1'){
				//售后拆单$int_or_id
				$erp_ids = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->field('erp_id')->where(array('or_id'=>$int_or_id))->group('erp_id')->select();
				if(count($erp_ids) == '1'){
					$res_refund = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->data(array('or_update_time'=>date('Y-m-d H:i:s')))->save();
					if(false === $res_refund){
						M('', '', 'DB_CUSTOM')->rollback();
						$this->error('售后单更新失败');						
					}
				}else{
					foreach($erp_ids as $erp){
						$refund_data = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->find();
						$refund_data['initial_tid'] = $int_or_id;
						unset($refund_data['or_id']);
						$refund_data['or_money'] = 0;
						$refund_data['erp_id'] = $erp['erp_id'];
						$refund_data['or_update_time'] = date('Y-m-d H:i:s');
						$res_refund_id = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->data($refund_data)->add();
						if(!$res_refund_id){
							M('', '', 'DB_CUSTOM')->rollback();
							$this->error('售后单拆单失败');						
						}
						$refund_items_data = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id,'erp_id'=>$erp['erp_id']))->select();
						foreach ($refund_items_data as $refund_items){
							unset($refund_items['ori_id']);
							$refund_items['or_id'] = $res_refund_id;
							
							$refund_item_res = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->data($refund_items)->add();
							if(!$refund_item_res){
								M('', '', 'DB_CUSTOM')->rollback();
								$this->error('售后单新增失败');
							}
						}	
					}
				}
			}else{
				M('', '', 'DB_CUSTOM')->rollback();
				$this->error('此订单拆单之后才可进行售后操作');
			}
		}

		//单据id:$int_or_id;订单号：$ary_data['o_id'] 
		//更新日志表
		$ary_orders_log = array(
				'o_id'=>$ary_data['o_id'],
				'ol_behavior' => '会员新增售后申请',
				'ol_uname'=>$_SESSION['Members']['m_name']
		);
		$res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
		if(!$res_orders_log){
			M('', '', 'DB_CUSTOM')->rollback();
			$this->error('会员新增售后申请日志失败');
		}
		$res_orders = D('Orders')
		->data(array('o_update_time'=>date('Y-m-d H:i:s')))
		->where(array('o_id'=>$ary_data['o_id']))->save();
		
		if(!$res_orders){
			M('', '', 'DB_CUSTOM')->rollback();
			$this->error('会员新增售后申请失败');
		}
		//事务提交
		M('', '', 'DB_CUSTOM')->commit();
        $this->success("{$str_type}请求提交成功。", U("Wap/Orders/orderList",array('status'=>4)));
	}

    /**
     * 上传附件保存
     * @author Hcaijin 
     * @date 2015-04-08
     */
    public function upLoadFile(){
        //上传图片
        if($_FILES['upload_file_0']['name']){
			$path = './Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/';
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/', 0777, true);
			}
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
			$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload()) {
				$this->error($upload->getErrorMsg());
			}else{
				$info =  $upload->getUploadFileInfo();
                $ary_refunds['status'] = 1;
				$ary_refunds['img_src'] = '/Public/Uploads/'.CI_SN.'/images/aftersale/' .date('Ymd').'/'. $info[0]['savename'];
                $this->ajaxReturn($ary_refunds);
			}
    	}
    }
    
}
