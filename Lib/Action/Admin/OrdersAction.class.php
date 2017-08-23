<?php

/**
 * 后台订单控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-01-18
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersAction extends AdminAction {

    /**
     * 控制器初始化操作
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-18
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle(' - ' . L('MENU3_0'));
    }

    /**
     * 默认控制器，重定向到订单列表页
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-18
     */
    public function index() {
        $this->redirect(U('Admin/Orders/pageList'));
    }

    /**
     * 订单列表页
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-18
     * @modify by wanghaoyu 2013-10-12
	 * 把商品名遍历出来,显示到模板上 
     */
    public function pageList() {
        $this->getSubNav(4, 0, 10);
        $ary_get = $this->_request();
		$mp_id = $this->_get('mp_id');
        if(!empty($ary_get) && is_array($ary_get)){
            foreach($ary_get as $key => &$val){
                if(!empty($val)){
                    //将乱码的中文用urldecode解码
                    $encode = mb_detect_encoding($val, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
                    if($encode == 'ASCII'){
                        $val = urldecode($val);
                    }
                }
            }
        }
        //订单搜索条件
        $ary_where = array();
		//关联查询条件
		$join_where = array();
		//查询内容
		$str_fields = " ".C("DB_PREFIX")."orders.*";
        //如果需要根据订单号进行搜索
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_id'] = $ary_get['o_id'];
        }
		//根据合并支付订单号进行搜索
		if(!empty($mp_id) && isset($mp_id)){
			$ary_o_id = M('merger_payment',C('DB_PREFIX'),'DB_CUSTOM')->field("o_id")->where(array('mp_id'=>$mp_id))->select();
			if(!empty($ary_o_id)){
				foreach($ary_o_id as $v){
					$ary_chose[] = $v['o_id'];
				}
				$ary_where ['fx_orders.o_id'] = array(
					'IN',
					$ary_chose
				);
			}
		}

        // 试用订单搜索
        if(isset($ary_get['o_try']) && !empty($ary_get['o_try'])){
            $ary_where[C("DB_PREFIX") . 'orders.o_goods_all_price'] = array('EQ','0.000');
            $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array('EQ','0.000');
        }
		//如果需要根据第三方你订单号进行搜索
		if(!empty($ary_get['o_source_id']) && isset($ary_get['o_source_id'])){
			$ary_where[C("DB_PREFIX") . 'orders.o_source_id'] = $ary_get['o_source_id'];
		}
        //如果需要根据会员名称进行搜索
        if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where[C("DB_PREFIX") . 'members.m_name'] = $ary_get['m_name'];
			$join_where[] = " " . C("DB_PREFIX") . "members ON " . C("DB_PREFIX") . "members.m_id=" . C("DB_PREFIX") . "orders.m_id";
        }
        //如果需要根据收货人进行搜索
        if (!empty($ary_get['o_receiver_name']) && isset($ary_get['o_receiver_name'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_name'] = $ary_get['o_receiver_name'];
        }
        //判断订单状态
        if (isset($ary_get['o_status'])) {
            //如果需要根据订单状态进行搜索
            if (!empty($ary_get['o_status'])) {
				
				/**$ary_o_status_where = array();
				//去除全部选项
				foreach ($ary_get['o_status'] as $os_v) {
					if ($os_v !== 0) {
						$ary_o_status_where[] = (int) $os_v;
					}
				}
				if (!empty($ary_o_status_where)) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('in', $ary_o_status_where);
                }
				**/
                $ary_where[C("DB_PREFIX") . 'orders.o_status'] = $ary_get['o_status'];
            }
        } else {
            //如果提交的参数中没有search,则代表是点击菜单进入订单列表，默认不显示作废订单，如果是搜索的结果，则显示作废订单
            if (!isset($ary_get['search'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('neq', 2);
            }
        }
        //如果需要根据支付状态进行搜索
        if (isset($ary_get['o_pay_status']) && $ary_get['o_pay_status'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'orders.o_pay_status'] = empty($ary_get['o_pay_status']) ? 0 : 1;
        }
        
        //如果需要根据支付状态进行搜索
        if (!empty($ary_get['erp_sn']) && isset($ary_get['erp_sn']) && $ary_get['erp_sn'] == 'no') {
            $ary_where[C("DB_PREFIX") . 'orders.erp_sn'] = '';
        }

        //如果需要根据配送方式进行搜索
        if (!empty($ary_get['lt_id']) && isset($ary_get['lt_id']) && $ary_get['lt_id'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'logistic_corp.lc_id'] = $ary_get['lt_id'];
			$join_where[] = " " . C("DB_PREFIX") . "logistic_type ON " . C("DB_PREFIX") . "logistic_type.lt_id=" . C("DB_PREFIX") . "orders.lt_id";
			$join_where[] = " " . C("DB_PREFIX") . "logistic_corp ON " . C("DB_PREFIX") . "logistic_type.lc_id=" . C("DB_PREFIX") . "logistic_corp.lc_id";
			$str_fields .=" ,".C("DB_PREFIX")."logistic_type.*,".C("DB_PREFIX")."logistic_corp.* ";
		}

        //如果需要根据支付方式进行搜索
        if (isset($ary_get['o_payment']) && !empty($ary_get['o_payment'])) {
            $ary_payment_where = array();
            //去除全部选项
            foreach ($ary_get['o_payment'] as $op_v) {
                if ($op_v !== '-1') {
                    $ary_payment_where[] = (int) $op_v;
                }
            }
            if (!empty($ary_payment_where)) {
                $ary_where[C("DB_PREFIX") . 'orders.o_payment'] = array('in', $ary_payment_where);
            }
        }
        //如果需要根据支付方式进行搜索
        if (isset($ary_get['oi_ship_status']) && $ary_get['oi_ship_status'] != '-1') {
            $ary_where[C("DB_PREFIX") . 'orders_items.oi_ship_status'] = $ary_get['oi_ship_status'];
		}
		//根据订单商品名称搜索
        if(!empty($ary_get['oi_g_name']) && isset($ary_get['oi_g_name'])){
            $ary_where[C("DB_PREFIX").'orders_items.oi_g_name'] = array('like',"%".$ary_get['oi_g_name']."%");
        }
		if(!empty($ary_get['oi_g_name']) || isset($ary_get['oi_ship_status'])){
			$join_where[] = " " . C("DB_PREFIX") . "orders_items ON " . C("DB_PREFIX") . "orders_items.o_id=" . C("DB_PREFIX") . "orders.o_id";
		}
        if (!empty($ary_get['province']) && isset($ary_get['province']) && $ary_get['province'] != '0') {
            $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['province']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_state'] = $province['cr_name'];
		}
        if (!empty($ary_get['city']) && isset($ary_get['city']) && $ary_get['city'] != '0') {
            $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['city']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_city'] = $province['cr_name'];
        }
        if (!empty($ary_get['region1']) && isset($ary_get['region1']) && $ary_get['region1'] != '0') {
            $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $ary_get['region1']))->find();
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_county'] = $province['cr_name'];
        }

        //如果需要根据收货人手机进行搜索
        if (!empty($ary_get['o_receiver_mobile']) && isset($ary_get['o_receiver_mobile'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_receiver_mobile'] = encrypt($ary_get['o_receiver_mobile']);
        }
        //如果需要根据物流费用进行搜索
        if (!empty($ary_get['o_cost_freight_1']) && !empty($ary_get['o_cost_freight_2'])) {
            if ($ary_get['o_cost_freight_1'] > $ary_get['o_cost_freight_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($ary_get['o_cost_freight_2'], $ary_get['o_cost_freight_1']));
            } else if ($ary_get['o_cost_freight_1'] < $ary_get['o_cost_freight_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($ary_get['o_cost_freight_1'], $ary_get['o_cost_freight_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = $ary_get['o_cost_freight_1'];
            }
        } else {
            if (!empty($ary_get['o_cost_freight_1']) && empty($ary_get['o_cost_freight_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("EGT", $ary_get['o_cost_freight_1']);
            } else if (empty($ary_get['o_cost_freight_1']) && !empty($ary_get['o_cost_freight_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("ELT", $ary_get['o_cost_freight_2']);
            }
        }
        //如果需要根据订单金额进行搜索
        if (!empty($ary_get['o_all_price_1']) && !empty($ary_get['o_all_price_2'])) {
            if ($ary_get['o_all_price_1'] > $ary_get['o_all_price_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($ary_get['o_all_price_2'], $ary_get['o_all_price_1']));
            } else if ($ary_get['o_all_price_1'] < $ary_get['o_all_price_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($ary_get['o_all_price_1'], $ary_get['o_all_price_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = $ary_get['o_all_price_1'];
            }
        } else {
            if (!empty($ary_get['o_all_price_1']) && empty($ary_get['o_all_price_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("EGT", $ary_get['o_all_price_1']);
            } else if (empty($ary_get['o_all_price_1']) && !empty($ary_get['o_all_price_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("ELT", $ary_get['o_all_price_2']);
            }
        }

        //如果需要根据使用优惠券进行搜索
        if (!empty($ary_get['o_coupon_1']) && empty($ary_get['o_coupon_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_coupon_1'];
        } else if (empty($ary_get['o_coupon_1']) && !empty($ary_get['o_coupon_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $ary_get['o_coupon_2'];
        }

        //如果需要根据使用开发票搜索
        if (!empty($ary_get['is_invoice_1']) && empty($ary_get['is_invoice_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.is_invoice'] = $ary_get['is_invoice_1'];
            if (!empty($ary_get['invoice_type'])) {
                $ary_where[C("DB_PREFIX") . 'orders.invoice_type'] = $ary_get['invoice_type'];
            }
        } else if (empty($ary_get['is_invoice_1']) && !empty($ary_get['is_invoice_2'])) {
            $ary_where[C("DB_PREFIX") . 'orders.is_invoice'] = $ary_get['is_invoice_2'];
        }
		//如果需要根据物时间进行搜索
		$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
		if(empty($ary_where['fx_orders.o_id']) && empty($ary_where['fx_orders.o_source_id']) && empty($ary_where['fx_members.m_name'])){
			if(empty($ary_get['o_create_time_1'])){
				$ary_get['o_create_time_1'] = $start_time;
			}
		}
        if (!empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
            if ($ary_get['o_create_time_1'] > $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_get['o_create_time_2'], $ary_get['o_create_time_1']));
            } else if ($ary_get['o_create_time_1'] < $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_get['o_create_time_1'], $ary_get['o_create_time_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $ary_get['o_create_time_1'];
            }
        } else {
            if (!empty($ary_get['o_create_time_1']) && empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $ary_get['o_create_time_1']);
            } else if (empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $ary_get['o_create_time_2']);
            }
        }
        //如果要根据客服名称搜索
        if(!empty($ary_get['admin_name']) && isset($ary_get['admin_name'])){
            $admin_id = D('Admin')->where(array('u_name'=>$ary_get['admin_name']))->getField('u_id');
            if(isset($admin_id) && !empty($admin_id)){
                $ary_where[C("DB_PREFIX").'orders.admin_id'] = $admin_id;
            }
        }
        //根据来源cps订单查询
        $bigou_cps_open  = D('SysConfig')->getConfigValueBySckey('CPS_51BIGOU_OPEN','CPS_SET');
        $fanli_cps_open = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');
        if($bigou_cps_open == '1' || $fanli_cps_open == '1'){
            if(!empty($ary_get['channelid']) && isset($ary_get['channelid'])){
                $ary_where[C("DB_PREFIX") . 'orders.channel_id'] = $ary_get['channelid'];
            }
        }

        //print_r($ary_where);exit;
        //数据分页处理，获取符合条件的记录数并分页显示
        $count = D("Orders")->join($join_where)->where($ary_where)->order(array(C("DB_PREFIX") . 'orders.o_id' => 'desc'))->count('distinct(fx_orders.o_id)');
        $string_count = $count;
        $obj_page = new Pager($string_count, 10);
        $page = $obj_page->showpage();
        //订单数据获取
        $array_order = array(C("DB_PREFIX") . 'orders.o_id' => 'desc');
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_orders_info = D("Orders")
                        ->Distinct(true)
						->field($str_fields)
						->join($join_where)
						->where($ary_where)->order($array_order)->limit($string_limit)->select();
		//echo D("Orders")->getLastSql();exit;
        //获取所有用户
        $admins = D('Admin')->field('u_id,u_name')->select();
        $ary_admin = array();
        foreach($admins as $admin_info){
            $ary_admin[$admin_info['u_id']] = $admin_info['u_name'];
        }
        foreach($ary_orders_info as $o_key=>$sub_order){
            if($sub_order['admin_id']){
                $ary_orders_info[$o_key]['admin_name'] = $ary_admin[$sub_order['admin_id']];
            }
			if($sub_order['o_source']!=''){//APP 端订单来源
				$ary_orders_info[$o_key]['o_ip'] = $sub_order['o_source'];
			}
        }
        //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = D("PaymentCfg")->where(1)->getField("pc_id,pc_custom_name");


        // echo "<pre>";print_r(D("Orders")->getLastSql());exit;
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            
            //订单状态
            $ary_status = array('o_status' => $v['o_status']);
            $str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
            $ary_orders_info[$k]['str_status'] = $str_status;

            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
            $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];
            //获取会员名称
            $ary_orders_info[$k]['m_name'] = D("Members")->where(array("m_id" => $v["m_id"]))->getField("m_name");

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
            //客户人员处理
            $ary_orders_info[$k]['u_name'] = '';
            if (isset($v['admin_id']) && $v['admin_id'] > 0) {
                $ary_admin = D('Admin')->getAdminInfoById($v['admin_id'], array('u_name'));
                if (is_array($ary_admin) && !empty($ary_admin)) {
                    $ary_orders_info[$k]['u_name'] = $ary_admin['u_name'];
                }
            }
            
            //发货时间
            $ary_orders_info[$k]['order_deliver_time'] = M('orders_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id'],'ol_behavior'=>'发货成功'))->getField('ol_create');
            //付款时间
            $pay_where['ol_behavior'] = array("LIKE", "%支付成功%");
            $pay_where['ol.o_id'] = array("EQ", $v['o_id']);
            $pay_where['o_pay_status'] = array(array("EQ", 1), array("EQ", 3), "OR");
            $ary_orders_info[$k]['order_pay_time'] = M('orders_log ol',C('DB_PREFIX'),'DB_CUSTOM')
                                                   ->join(C("DB_PREFIX"). "orders o on o.o_id = ol.o_id")
                                                   ->where($pay_where)->getField('ol_create');
            //订单商品数量
            $arr_oinum = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->select();
            //$int_oinum = '0';
            $int_oirange = 0;
            foreach($arr_oinum as $oinum){
                //$int_oinum += $oinum['oi_nums'];
                // 判断商品是否在价格区间
                if(!empty($oinum['oi_thd_sale_price'])){
                    $price_range_where = array(
                        'pdt_id' => $oinum['pdt_id'],
                        'pdt_price_up' => array('elt',$oinum['oi_thd_sale_price']),
                        'pdt_price_down' => array('egt',$oinum['oi_thd_sale_price'])
                        );
                    $price_range = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where($price_range_where)->find();
                    if(empty($price_range) OR (!empty($price_range) && !empty($price_range['pdt_price_up']) && !empty($price_range['pdt_price_down']))){
                        $int_oirange = 1;
                        break;
                    }
                }
            }
			$int_oinum = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->sum('oi_nums');
            $ary_orders_info[$k]['oi_range'] = $int_oirange;
            $ary_orders_info[$k]['oi_nums'] = $int_oinum;
//            echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
            //售后状态
            $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $v['o_id']))->order('or_create_time asc')->select();
            if (!empty($ary_afersale) && is_array($ary_afersale)) {
                foreach ($ary_afersale as $keyaf => $valaf) {
                    if ($valaf['or_service_verify'] == '1' && $valaf['or_finance_verify'] == '1') {
                        M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $v['o_id'],'or_update_time'=>date('Y-m-d H:i:s')))->save(array('or_processing_status' => 1));
                    }
                    //退款
                    if ($valaf['or_refund_type'] == 1) {
                        switch ($valaf['or_processing_status']) {
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
                    } elseif ($valaf['or_refund_type'] == 2) {         //退货
                        switch ($valaf['or_processing_status']) {
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
            //订单商品类型
            $ary_orders_info[$k]['o_goods_type'] = M('orders_items')->where(array('o_id'=>$v['o_id']))->getField('oi_type');
			$ary_promotion = array();
			$ary_promotion = unserialize($v['promotion']);
            
            /**if(!empty($ary_promotion) && is_array($ary_promotion)) {
                foreach($ary_promotion as $v_pro) {
                    if(!empty($v_pro['products']) && is_array($v_pro['products'])) {
                        foreach($v_pro['products'] as $k_name=>$v_name['products']) {
                            if(isset($v_name['products'][0])){
                                $ary_orders_info[$k]['g_name'] = $v_name['products'][0]['g_name']; 
                            }else{
                                $ary_orders_info[$k]['g_name'] = $v_name['products']['g_name']; 
                            }
							
                        }
                    }
                }
            }else{
                //if($ary_orders_info[$k]['o_goods_type'] == 5 || $ary_orders_info[$k]['o_goods_type'] == 4 || $ary_orders_info[$k]['o_goods_type'] == 6 || $ary_orders_info[$k]['o_goods_type'] == 8 || $ary_orders_info[$k]['o_goods_type'] == 7){
                    $ary_orders_info[$k]['g_name'] = D('OrdersItems')->where(array('o_id'=>$v['o_id']))->getField('oi_g_name');
                //}
            }
            **/
            $ary_orders_info[$k]['g_name'] = D('OrdersItems')->where(array('o_id'=>$v['o_id']))->getField('oi_g_name');
            // 判断是否有返利
            $ary_orders_info[$k]['pay_back'] = 0;
            if(D('Promotings')->IsOrderPakback(array('o_id'=>$v['o_id'],'m_id'=>$v['m_id']))){
                $ary_orders_info[$k]['pay_back'] = 1;
            }
        }
	//是否开启
        $ary_order_remove_on = D('SysConfig')->getCfg('ORDERS_REMOVE','ORDERS_REMOVE','0','是否开启订单拆分');
        $ary_get['order_remove_on'] = $ary_order_remove_on['ORDERS_REMOVE']['sc_value'];
        $this->assign("filter", $ary_get);
        $this->assign("page", $page);
        $this->assign("get",json_encode($_GET));
//        echo "<pre>";print_r($ary_orders_info);exit;
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 售后服务配置
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-18
     */
    public function setAftersale() {
        $this->getSubNav(4, 1, 10);

        $ary_order_data = D('SysConfig')->getCfgByModule('GY_ORDER_AFTERSALE_CONFIG');
        $ary_order_data['value'] = '';
        $ary_order_data['content'] = '';
        if (!empty($ary_order_data) && is_array($ary_order_data)) {
            $sc_value = explode(',', $ary_order_data['SETAFTERSALE']);
            $ary_order_data['value'] = $sc_value[0];
            $ary_order_data['content'] = $sc_value[1];
			$ary_order_data['content'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_order_data['content']);
        }
        //echo "<pre>";print_r($ary_order_data);exit;
        $this->assign('data', $ary_order_data);
        $this->display();
    }

    /**
     * 待付款的订单列表
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-18
     */
    public function pageWaitPayOrdersList() {
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $this->getSubNav(4, 0, 20);
        $ary_data = $this->_param();
        //订单搜索条件，显示未付款或者部分付款或者处理中的订单
        $ary_where = array("o_status" => array("IN", array(1)), "o_pay_status" => array("IN", array(0, 2, 3)));

        //如果需要根据订单号进行搜素
        if (!empty($ary_data['o_id']) && isset($ary_data['o_id'])) {
            $ary_where['o_id'] = $ary_data['o_id'];
        }
		//默认查最近三个月数据
		if(empty($ary_where['o_id'])){
			$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
			if(empty($ary_data['o_create_time_1'])){
				$ary_data['o_create_time_1'] = $start_time;
			}
			if (!empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
				if ($ary_data['o_create_time_1'] > $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_2'], $ary_data['o_create_time_1']));
				} else if ($ary_data['o_create_time_1'] < $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_1'], $ary_data['o_create_time_2']));
				} else {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $ary_data['o_create_time_1'];
				}
			} else {
				if (!empty($ary_data['o_create_time_1']) && empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $ary_data['o_create_time_1']);
				} else if (empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $ary_data['o_create_time_2']);
				}
			}
		}
        //数据分页处理，获取符合条件的记录数并分页显示
        $count = D("Orders")->where($ary_where)->count();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();

        //订单数据获取
        $array_order = array('o_id' => 'desc');
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_orders_info = D("Orders")->field('fx_orders.*,fx_admin.u_name as admin_name')
        ->join('fx_admin on(fx_admin.u_id=fx_orders.admin_id)')->
        where($ary_where)->order($array_order)->limit($string_limit)->select();
        //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = D("PaymentCfg")->where(1)->getField("pc_id,pc_custom_name");
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            //订单状态
            $ary_status = array('o_status' => $v['o_status']);
            $str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
            $ary_orders_info[$k]['str_status'] = $str_status;

            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
            $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];

            //获取会员名称
            $ary_orders_info[$k]['m_name'] = D("Members")->where(array("m_id" => $v["m_id"]))->getField("m_name");

            //订单支付方式名称
            $ary_orders_info[$k]['pc_name'] = $array_payment_cfg[$v["o_payment"]];
            $wheres = array();
            $wheres[C("DB_PREFIX") . 'logistic_type.lt_id'] = $v['lt_id'];
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
            $ary_orders_info[$k]['delivery_company_name'] = $delivery_company_info['lc_is_enable'] ? $delivery_company_info['lc_name'] : '已删除';
            //取出订单号
            $int_oid = $v['o_id'];
            $int_oinum = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $int_oid))->sum('oi_nums');
            $ary_orders_info[$k]['oi_nums'] = $int_oinum;
            //客户人员处理
            $ary_orders_info[$k]['u_name'] = '';
            if (isset($v['admin_id']) && $v['admin_id'] > 0) {
                $ary_admin = D('Admin')->getAdminInfoById($v['admin_id'], array('u_name'));
                if (is_array($ary_admin) && !empty($ary_admin)) {
                    $ary_orders_info[$k]['u_name'] = $ary_admin['u_name'];
                }
            }
			//
			$ary_orders_info[$k]['verify_pay_status'] = D('AdminPay')->where(array('order_id'=>$int_oid,'ap_status'=>0))->count();
        }
        $this->assign("filter", $ary_data);
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 待发货的订单列表
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-18
     */
    public function pageWaitDeliverOrdersList() {
		$start_time=date("Y-m-d H:i:s",mktime(0,0,0,date("m")-1,date("d"),date("Y")));
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $this->getSubNav(4, 0, 30);
        $ary_data = $this->_param();
        //订单搜索条件

		$ary_where = array ( 'o_status' => '1', 'fx_orders_items.oi_ship_status' => array ( 0 => 'neq', 1 => 2, ), '_string' => '(o_pay_status = 1 ) or (o_pay_status=0 and o_payment=6)','fx_orders_items.oi_refund_status'=>array('not in','4,5'));
        //如果需要根据订单号进行搜素
        if (!empty($ary_data['o_id']) && isset($ary_data['o_id'])) {
            $ary_where[C("DB_PREFIX")."orders_items.o_id"] = $ary_data['o_id'];
        }else{
			//$ary_where['fx_orders.o_create_time'] = array("EGT", $start_time);
			//默认查最近三个月数据
			$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
			if(empty($ary_data['o_create_time_1'])){
				$ary_data['o_create_time_1'] = $start_time;
			}
			if (!empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
				if ($ary_data['o_create_time_1'] > $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_2'], $ary_data['o_create_time_1']));
				} else if ($ary_data['o_create_time_1'] < $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_1'], $ary_data['o_create_time_2']));
				} else {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $ary_data['o_create_time_1'];
				}
			} else {
				if (!empty($ary_data['o_create_time_1']) && empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $ary_data['o_create_time_1']);
				} else if (empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $ary_data['o_create_time_2']);
				}
			}
		}
        //数据分页处理，获取符合条件的记录数并分页显示

        $count = D("Orders")->where($ary_where)->join(C("DB_PREFIX").'orders_items on '.C("DB_PREFIX").'orders.o_id = '.C("DB_PREFIX").'orders_items.o_id')
        ->join('fx_admin on(fx_admin.u_id=fx_orders.admin_id)')->count('DISTINCT fx_orders.o_id');
        //echo D("Orders")->getLastSql();exit;
		$obj_page = new Page($count, 20);
		
        $page = $obj_page->show();
       // echo M()->getLastSql();

        //订单数据获取
        $array_order = array(C("DB_PREFIX").'orders.o_id' => 'desc');
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_orders_info = D("Orders")->where($ary_where)->field('fx_orders.*,fx_admin.u_name as admin_name,'.C('DB_PREFIX').'orders_items.oi_refund_status')
		->join(C("DB_PREFIX").'orders_items on '.C("DB_PREFIX").'orders.o_id = '.C("DB_PREFIX").'orders_items.o_id')
        ->join('fx_admin on(fx_admin.u_id=fx_orders.admin_id)')
        ->order($array_order)->group(C("DB_PREFIX").'orders.o_id')->limit($string_limit)->select();
     //  print_r(D("Orders")->getLastSql());exit;
        //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = D("PaymentCfg")->where(1)->getField("pc_id,pc_custom_name");
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            //订单状态
            $ary_status = array('o_status' => $v['o_status']);
            $str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
            $ary_orders_info[$k]['str_status'] = $str_status;

            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
            $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];

            //获取会员名称
            $ary_orders_info[$k]['m_name'] = D("Members")->where(array("m_id" => $v["m_id"]))->getField("m_name");

            $arr_oinum = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$v['o_id']))->select();
            $int_oinum = '0';
            foreach($arr_oinum as $oinum){
                $int_oinum += $oinum['oi_nums'];
            }
            $ary_orders_info[$k]['oi_nums'] = $int_oinum;
            

            //客户人员处理
            $ary_orders_info[$k]['u_name'] = '';
            if (isset($v['admin_id']) && $v['admin_id'] > 0) {
                $ary_admin = D('Admin')->getAdminInfoById($v['admin_id'], array('u_name'));
                if (is_array($ary_admin) && !empty($ary_admin)) {
                    $ary_orders_info[$k]['u_name'] = $ary_admin['u_name'];
                }
            }

            //订单支付方式名称
            $ary_orders_info[$k]['pc_name'] = $array_payment_cfg[$v["o_payment"]];
            $wheres = array();
            $wheres[C("DB_PREFIX") . 'logistic_type.lt_id'] = $v['lt_id'];
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
            $ary_orders_info[$k]['delivery_company_name'] = $delivery_company_info['lc_is_enable'] ? $delivery_company_info['lc_name'] : '已删除';

            //付款时间
            $pay_where['ol_behavior'] = array("LIKE", "%支付成功%");
            $pay_where['ol.o_id'] = array("EQ", $v['o_id']);
            $pay_where['o_pay_status'] = array(array("EQ", 1), array("EQ", 3), "OR");
            $ary_orders_info[$k]['order_pay_time'] = M('orders_log ol',C('DB_PREFIX'),'DB_CUSTOM')
                                                   ->join(C("DB_PREFIX"). "orders o on o.o_id = ol.o_id")
                                                   ->where($pay_where)->getField('ol_create');
            //取出订单号
            $int_oid = $v['o_id'];
            //取到商品的类型
            $ary_oi_type = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field('oi_type')->where(array('o_id'=>$int_oid))->find();
            $ary_orders_info[$k]['oi_type'] = $ary_oi_type['oi_type'];
        }
        $this->assign("filter", $ary_data);
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 添加/编辑售后服务配置
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-18
     */
    public function doAddAftersale() {
        $ary_post = $this->_post();
        $SysSeting = D('SysConfig');
        $module = "GY_ORDER_AFTERSALE_CONFIG";
        $key = "SETAFTERSALE";
        $desc = "订单售后服务配置";
        if (!empty($ary_post) && is_array($ary_post)) {
            $ary_post['set_content'] = _ReplaceItemDescPicDomain($ary_post['set_content']);			
            $value = implode(',', $ary_post);
            $ary_res = $SysSeting->setConfig($module, $key, $value, $desc);
            if ($ary_res) {
                $this->success('操作成功', U('Admin/Orders/setAftersale'));
            } else {
                $this->error('操作失败', U('Admin/Orders/setAftersale'));
            }
        } else {
            $this->error('操作失败', U('Admin/Orders/setAftersale'));
        }
    }

    /**
     * 售后服务列表
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-18
     */
    public function pageAftersaleList() {
        $orders = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM');
        $this->getSubNav(4, 1, 20);
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_refunds.o_id'] = $ary_get['o_id'];
        }
        $count = $orders->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")->where($ary_where)->order(array('fx_orders_refunds.or_id' => 'desc'))->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_orders_info = $orders
                ->field("fx_orders_refunds.*,fx_members.m_name,fx_members.m_id")
                ->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                ->where($ary_where)
                ->order(array('fx_orders_refunds.or_id' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $keyde => $valde) {
                if ($valde['or_processing_status'] == 1) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理成功';
                    $ary_orders_info[$keyde]['status']['color'] = 'green';
                } elseif ($valde['or_processing_status'] == 2) {
                    $ary_orders_info[$keyde]['status']['msg'] = '作废';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } elseif ($valde['or_processing_status'] == 0) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理中';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } else {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理失败';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                }
            }
        }
        //echo "<pre>";print_r($ary_orders_info);exit;
        $this->assign("filter", $ary_get);
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 订单收款单据
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-19
     */
    public function pageOrdersProceedsList() {
        $this->getSubNav(4, 2, 10);
        $orders = M("PaymentSerial", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_payment_serial.o_id'] = trim($ary_get['o_id']);
        }
		if (!empty($ary_get['ps_gateway_sn']) && isset($ary_get['ps_gateway_sn'])) {
            $ary_where['fx_payment_serial.ps_gateway_sn'] = trim($ary_get['ps_gateway_sn']);
        }
		if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where['fx_members.m_name'] = trim($ary_get['m_name']);
        }	
		if (!empty($ary_get['pc_code']) && isset($ary_get['pc_code'])) {
            $ary_where['fx_payment_serial.pc_code'] = trim($ary_get['pc_code']);
        }		
		if(empty($ary_get['o_create_time_1'])){
			$ary_get['o_create_time_1'] = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
		}
        if (!empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
            if ($ary_get['o_create_time_1'] > $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("BETWEEN", array($ary_get['o_create_time_2'], $ary_get['o_create_time_1']));
            } else if ($ary_get['o_create_time_1'] < $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("BETWEEN", array($ary_get['o_create_time_1'], $ary_get['o_create_time_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = $ary_get['o_create_time_1'];
            }
        } else {
            if (!empty($ary_get['o_create_time_1']) && empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("EGT", $ary_get['o_create_time_1']);
            } else if (empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("ELT", $ary_get['o_create_time_2']);
            }
        }		
		
		/**if (!empty($ary_get['ps_status']) && isset($ary_get['ps_status'])) {
			if($ary_get['ps_status'] == '2'){
				$ary_where['fx_payment_serial.ps_status'] = '0';
			}else{
				$ary_where['fx_payment_serial.ps_status'] = '1';
			}
        }		
		**/		
        $ary_where['fx_payment_serial.ps_type'] = '0';
		$ary_where['fx_payment_serial.ps_status'] = array('in',array(1,2,3));
        $count = $orders
                        ->join(" fx_payment_cfg ON fx_payment_serial.pc_code=fx_payment_cfg.pc_abbreviation")
                        ->join(" fx_members ON fx_payment_serial.m_id=fx_members.m_id")
                        ->where($ary_where)->order(array('fx_payment_serial.o_id' => 'desc'))->count();

        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_orders_info = $orders
                ->field("fx_payment_serial.*,fx_members.m_name,fx_members.m_id,fx_payment_cfg.pc_custom_name")
                ->join(" fx_payment_cfg ON fx_payment_serial.pc_code=fx_payment_cfg.pc_abbreviation")
                ->join(" fx_members ON fx_payment_serial.m_id=fx_members.m_id")
                ->where($ary_where)->order(array('fx_payment_serial.o_id' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $key => $val) {
                switch ($val['ps_status']) {
                    case 0:
                        $ary_orders_info[$key]['status'] = "未支付";
                        break;
                    case 1:
                        $ary_orders_info[$key]['status'] = "支付成功";
                        break;
                    case 2:
                        $ary_orders_info[$key]['status'] = "处理中";
                        break;
                    case 3:
                        $ary_orders_info[$key]['status'] = "担保交易成功";
                        break;
                    default :
                        $ary_orders_info[$key]['status'] = "支付失败";
                        break;
                }
            }
        }
		//获取支付方式
		$paymet_cfgs = M("payment_cfg", C('DB_PREFIX'), 'DB_CUSTOM')->field('pc_abbreviation,pc_id,pc_custom_name')->select();
        $this->assign("paymet_cfgs", $paymet_cfgs);
		$this->assign("filter", $ary_get);
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filterExcel",base64_encode(json_encode($ary_get)));
        $this->display();
    }

    /**
     * 订单收款单据导出
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-25
     */
    public function exportOrdersProceeds(){
        $orders = M("PaymentSerial", C('DB_PREFIX'), 'DB_CUSTOM');
		$ary_where = array();
		$ary_get = $this->_request();
		if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_payment_serial.o_id'] = trim($ary_get['o_id']);
        }
		if (!empty($ary_get['ps_gateway_sn']) && isset($ary_get['ps_gateway_sn'])) {
            $ary_where['fx_payment_serial.ps_gateway_sn'] = trim($ary_get['ps_gateway_sn']);
        }
		if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where['fx_members.m_name'] = trim($ary_get['m_name']);
        }	
		if (!empty($ary_get['pc_code']) && isset($ary_get['pc_code'])) {
            $ary_where['fx_payment_serial.pc_code'] = trim($ary_get['pc_code']);
        }		
        if (!empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
            if ($ary_get['o_create_time_1'] > $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("BETWEEN", array($ary_get['o_create_time_2'], $ary_get['o_create_time_1']));
            } else if ($ary_get['o_create_time_1'] < $ary_get['o_create_time_2']) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("BETWEEN", array($ary_get['o_create_time_1'], $ary_get['o_create_time_2']));
            } else {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = $ary_get['o_create_time_1'];
            }
        } else {
            if (!empty($ary_get['o_create_time_1']) && empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("EGT", $ary_get['o_create_time_1']);
            } else if (empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
                $ary_where[C("DB_PREFIX") . 'payment_serial.ps_update_time'] = array("ELT", $ary_get['o_create_time_2']);
            }
        }
        $ary_where['fx_payment_serial.ps_type'] = '0';
		$ary_where['fx_payment_serial.ps_status'] = array('in',array(1,2,3));
        $ary_orders_info = $orders
                ->field("fx_payment_serial.*,fx_members.m_name,fx_members.m_id,fx_payment_cfg.pc_custom_name")
                ->join(" fx_payment_cfg ON fx_payment_serial.pc_code=fx_payment_cfg.pc_abbreviation")
                ->join(" fx_members ON fx_payment_serial.m_id=fx_members.m_id")
                ->where($ary_where)->order(array('fx_payment_serial.o_id' => 'desc'))
                ->select();
        if(!empty($ary_orders_info) && is_array($ary_orders_info)){
            $header = array('支付单号', '支付金额', '订单号', '网关流水号', '支付方式', '会员用户名', '支付状态');
            $contents = array();
            $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
            foreach($ary_orders_info as $vo){
                $status = '';
                switch ($vo['ps_status']) {
                    case 0:
                        $status = "支付成功";
                        break;
                    case 1:
                        $status = "支付成功";
                        break;
                    case 2:
                        $status = "处理中";
                        break;
                    case 3:
                        $status = "担保交易成功";
                        break;
                    default :
                        $status = "支付失败";
                        break;
                }
                $contents[0][] = array(
                    $vo['ps_id'],
                    $vo['ps_money'],
                    $vo['o_id'].' ',
                    $vo['ps_gateway_sn'].' ',
                    $vo['pc_custom_name'],
                    $vo['m_name'],
                    $status
                    );
            }
            $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
            if(!is_dir($filexcel)){
                    @mkdir($filexcel,0777,1);
            }
            $Export = new Export(date('YmdHis') . '.xls', $filexcel);
			
            $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '信息', true);
            if (!empty($excel_file)) {
                $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
            } else {
                $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'没有需要导出单据'));
        }
    }
	
    /**
     * 付款申请列表
     * @auther Wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-11
     */
    public function pageAdminOrdersPay() {
        $this->getSubNav(4, 2, 60);
        $admin_pay_obj = M("admin_pay", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['order_id'] = $ary_get['o_id'];
        }
        //状态
        switch($ary_get['status']) {
            case "1":
                $ary_where['ap_status'] = 0;
            break;
            
            case "2":
                $ary_where['ap_status'] = 1;
            break;
            
            case "3":
                $ary_where['ap_status'] = 2;
            break;	
			default:
			 $ary_where['ap_status'] = 0;
			break;
        }
        $count = $admin_pay_obj->where($ary_where)->count();
        $obj_page = new Page($count, 10);
        $ary_orders_info = $admin_pay_obj
                ->where($ary_where)
                ->order(array('ap_create_time' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
		foreach($ary_orders_info as $key=>$order){
			$str_status = '';
			if($order['ap_status'] == 0){
				$str_status = '待审核';
			}
			if($order['ap_status'] == 1){
				$str_status = '已审核';
			}	
			if($order['ap_status'] == 2){
				$str_status = '已作废';
			}	
			$ary_orders_info[$key]['str_status'] = 	$str_status;		
		}
        //echo "<pre>";print_r($ary_orders_info);exit;
        $page = $obj_page->show();
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filter", $ary_get);
        $this->display();
    }

	
    /**
     * 订单退款单据
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-19
     */
    public function pageOrdersRefundList() {
        $this->getSubNav(4, 2, 20);
        $refunds = M("orders_refunds", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_refunds.o_id'] = $ary_get['o_id'];
        }
        //退款单号
        if (!empty($ary_get['or_id']) && isset($ary_get['or_id'])) {
            $ary_where['fx_orders_refunds.or_return_sn'] = $ary_get['or_id'];
        }
        //会员名
        if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where['fx_members.m_name'] = $ary_get['m_name'];
        }
        //退款单状态
        switch($ary_get['status']) {
            case "1":
                $ary_where['fx_orders_refunds.or_service_verify'] = 1;
            break;
            
            case "2":
                $ary_where['fx_orders_refunds.or_finance_verify'] = 1;
            break;
            
            case "3":
                $ary_where['fx_orders_refunds.or_processing_status'] = 2;
            break;
            case "4":
                $ary_where['fx_orders_refunds.or_service_verify'] = 0;
				$ary_where['fx_orders_refunds.or_finance_verify'] = 0;
				$ary_where['fx_orders_refunds.or_processing_status'] = array('neq',2);
            break;			
        }
		//默认查最近3个月数据
		if($ary_get['o_id']=='' && $ary_get['or_id'] =='' && $ary_get['m_name'] =='' && $ary_get['status'] ==''){
			$ary_where[C("DB_PREFIX") . 'orders_refunds.or_create_time'] = array("EGT", date("Y-m-d H:i:s",strtotime("-1 month")));	
		}

        $ary_where['fx_orders_refunds.or_refund_type'] = "1";
        $count = $refunds->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                        ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")->where($ary_where)->count();
        $obj_page = new Page($count, 10);
        $ary_orders_info = $refunds
                ->field("fx_orders_refunds.*,fx_members.m_name,fx_members.m_id")
                ->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")
                ->where($ary_where)
                ->order(array('fx_orders_refunds.or_create_time' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $keyde => $valde) {
                if ($valde['or_processing_status'] == 1) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理成功';
                    $ary_orders_info[$keyde]['status']['color'] = 'green';
                } elseif ($valde['or_processing_status'] == 2) {
                    $ary_orders_info[$keyde]['status']['msg'] = '作废';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } elseif ($valde['or_processing_status'] == 0) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理中';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } else {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理失败';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                }
				$res =D('PaymentSerial')->getDataInfo(array('o_id'=>$valde['o_id'],'ps_status'=>1),array('ps_gateway_sn,pc_code'));
				if($res['pc_name']!=''){
					$ary_orders_info[$keyde]['gateway_sn']=$res['ps_gateway_sn'];
					$ary_orders_info[$keyde]['pc_name']=$res['pc_name'];
				}
            }
        }
        //echo "<pre>";print_r($ary_orders_info);exit;
        $page = $obj_page->show();
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filter", $ary_get);
        $this->display();
    }

    /**
     * 退款/货单据导出
     * @author wanghaijun
     * @date 2014-12-05
     */
    public function exportOrdersRefundList(){
        $refunds = M("orders_refunds", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_post();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_refunds.o_id'] = $ary_get['o_id'];
        }
        //退款单号
        if (!empty($ary_get['or_id']) && isset($ary_get['or_id'])) {
            $ary_where['fx_orders_refunds.or_return_sn'] = $ary_get['or_id'];
        }
        //会员名
        if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where['fx_members.m_name'] = $ary_get['m_name'];
        }
        //退款单状态
        switch($ary_get['status']) {
            case "1":
                $ary_where['fx_orders_refunds.or_service_verify'] = 1;
            break;
            
            case "2":
                $ary_where['fx_orders_refunds.or_finance_verify'] = 1;
            break;
            
            case "3":
                $ary_where['fx_orders_refunds.or_processing_status'] = 2;
            break;
        }
		
		if(empty($ary_get['m_name'])){
			//默认查最近1个月数据  对应会员查询查全部数据
			$ary_where[C("DB_PREFIX") . 'orders_refunds.or_create_time'] = array("EGT", date("Y-m-d H:i:s",strtotime("-1 month")));	
		}
		
        $ary_where['fx_orders_refunds.or_refund_type'] = $ary_get['or_refund_type'] ? $ary_get['or_refund_type'] : '2';
        $ary_orders_info = $refunds
                ->field("fx_orders_refunds.*,fx_members.m_name,fx_members.m_id")
                ->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")
                ->where($ary_where)
                ->order(array('fx_orders_refunds.or_create_time' => 'desc'))
                ->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $contents = array();
            if($ary_get['or_refund_type'] == 1) {
                $header = array('退款单号', '金额', '货币', '订单号', '会员用户名', '退款账号', '退款银行', '收款人', '状态','创建时间','修改时间');
                $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I','J','K');
                
                foreach ($ary_orders_info as $valde) {
                    $status = '';
                    if($valde['or_service_verify'] == 1) {
                        $status = '已客审';
                    } 
                    if($valde['or_finance_verify'] == 1) {
                        $status .= ' 已财审';
                    } 
                    if($valde['or_processing_status'] == 2) {
                        $status = '已作废';
                    }
                    $contents[0][] = array(
                                        ($valde['or_return_sn'] ? $valde['or_return_sn'] : $valde['or_id']) . ' ',
                                        $valde['or_money'],
                                        $valde['or_currency'] ? $valde['or_currency'] : 'RMB',
                                        $valde['o_id'].' ',
                                        $valde['m_name'],
                                        $valde['or_account'] . ' ',
                                        $valde['or_bank'],
                                        $valde['or_payee'],
                                        $status,
										$valde['or_create_time'],
										$valde['or_update_time']
                                    );
                }
            } else {
                $header = array('退货单号', '订单号', '退货金额', '单据创建时间', '会员用户名', '状态','创建时间','修改时间');
                $fields = array('A', 'B', 'C', 'D', 'E', 'F','G','H');
                
                foreach ($ary_orders_info as $valde) {
                    $status = '';
                    if($valde['or_service_verify'] == 1) {
                        $status = '已客审';
                    } 
                    if($valde['or_finance_verify'] == 1) {
                        $status .= ' 已财审';
                    } 
                    if($valde['or_processing_status'] == 2) {
                        $status = '已作废';
                    }
                    $contents[0][] = array(
                                        ($valde['or_return_sn'] ? $valde['or_return_sn'] : $valde['or_id']) . ' ',
                                        $valde['o_id'].' ',
                                        $valde['or_money'],
                                        $valde['or_create_time'],
                                        $valde['m_name'],
                                        $status,
										$valde['or_create_time'],
										$valde['or_update_time']
                                    );
                }
            }

            $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
            if(!is_dir($filexcel)){
                    @mkdir($filexcel,0777,1);
            }
            $Export = new Export(date('YmdHis') . '.xls', $filexcel);
            $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '信息', true);
            if (!empty($excel_file)) {
                $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
            } else {
                $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'没有需要导出单据'));
        }
    }
    
    /**
     * 订单发货单据
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-19
     */
    public function pageOrdersDeliverList() {
        $this->getSubNav(4, 2, 30);
        $deliver = M("orders_delivery", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_delivery.o_id'] = $ary_get['o_id'];
        }else{
			if(empty($ary_get['o_create_time_1'])){
				$ary_get['o_create_time_1'] = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
			}
		}
		if (!empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
			if ($ary_get['o_create_time_1'] > $ary_get['o_create_time_2']) {
				$ary_where[C("DB_PREFIX") . 'orders_delivery.od_created'] = array("BETWEEN", array($ary_get['o_create_time_2'], $ary_get['o_create_time_1']));
			} else if ($ary_get['o_create_time_1'] < $ary_get['o_create_time_2']) {
				$ary_where[C("DB_PREFIX") . 'orders_delivery.od_created'] = array("BETWEEN", array($ary_get['o_create_time_1'], $ary_get['o_create_time_2']));
			} else {
				$ary_where[C("DB_PREFIX") . 'orders_delivery.od_created'] = $ary_get['o_create_time_1'];
			}
		} else {
			if (!empty($ary_get['o_create_time_1']) && empty($ary_get['o_create_time_2'])) {
				$ary_where[C("DB_PREFIX") . 'orders_delivery.od_created'] = array("EGT", $ary_get['o_create_time_1']);
			} else if (empty($ary_get['o_create_time_1']) && !empty($ary_get['o_create_time_2'])) {
				$ary_where[C("DB_PREFIX") . 'orders_delivery.od_created'] = array("ELT", $ary_get['o_create_time_2']);
			}
		}
			
        $count = $deliver->join(" fx_members ON fx_orders_delivery.m_id=fx_members.m_id")
                        ->join(" fx_orders ON fx_orders_delivery.o_id=fx_orders.o_id")->where($ary_where)->count();
        $obj_page = new Page($count, 10);
        $ary_orders_info = $deliver
                ->field("fx_orders_delivery.*,fx_members.m_name,fx_members.m_id")
                ->join(" fx_members ON fx_orders_delivery.m_id=fx_members.m_id")
                ->join(" fx_orders ON fx_orders_delivery.o_id=fx_orders.o_id")
                ->where($ary_where)
                ->order(array('fx_orders_delivery.od_created' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
				//echo $deliver->getLastSql();exit;
        $page = $obj_page->show();
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filter", $ary_get);
        //echo "<pre>";print_r($ary_orders_info);exit;
        $this->display();
    }

    /**
     * 订单退货单据
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-19
     */
    public function pageOrdersReturnList() {
        $this->getSubNav(4, 2, 40);
        $refunds = M("orders_refunds", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_refunds.o_id'] = $ary_get['o_id'];
        }
        //退货单号
        if (!empty($ary_get['or_id']) && isset($ary_get['or_id'])) {
            $ary_where['fx_orders_refunds.or_return_sn'] = $ary_get['or_id'];
        }
        //会员名
        if (!empty($ary_get['m_name']) && isset($ary_get['m_name'])) {
            $ary_where['fx_members.m_name'] = $ary_get['m_name'];
        }
		//物流单号
        if (!empty($ary_get['logic_sn']) && isset($ary_get['logic_sn'])) {
            $ary_where['fx_orders_refunds.or_return_logic_sn'] = trim($ary_get['logic_sn']);
        }
        //退货单状态
        switch($ary_get['status']) {
            case "1":
                $ary_where['fx_orders_refunds.or_service_verify'] = 1;
            break;
            
            case "2":
                $ary_where['fx_orders_refunds.or_finance_verify'] = 1;
            break;
            
            case "3":
                $ary_where['fx_orders_refunds.or_processing_status'] = 2;
            break;
			case "4":
                 $ary_where['fx_orders_refunds.or_service_verify'] = 0;
				 $ary_where['fx_orders_refunds.or_finance_verify'] = 0;
				 $ary_where['fx_orders_refunds.or_processing_status'] = array('neq',2);
            break;
        }
        $ary_where['fx_orders_refunds.or_refund_type'] = "2";
        $count = $refunds->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                        ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")->where($ary_where)->count();
        $obj_page = new Page($count, 10);
        $ary_orders_info = $refunds
                ->field("fx_orders_refunds.*,fx_members.m_name,fx_members.m_id")
                ->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")
                ->where($ary_where)
                ->order(array('fx_orders_refunds.or_create_time' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $keyde => $valde) {
                if ($valde['or_processing_status'] == 1) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理成功';
                    $ary_orders_info[$keyde]['status']['color'] = 'green';
                } elseif ($valde['or_processing_status'] == 2) {
                    $ary_orders_info[$keyde]['status']['msg'] = '作废';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } elseif ($valde['or_processing_status'] == 0) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理中';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } else {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理失败';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                }
				$res =D('PaymentSerial')->getDataInfo(array('o_id'=>$valde['o_id'],'ps_status'=>1),array('ps_gateway_sn,pc_code'));
				if($res['ps_gateway_sn']!=''){
					$ary_orders_info[$keyde]['gateway_sn']=$res['ps_gateway_sn'];
					$ary_orders_info[$keyde]['pc_name']=$res['pc_name'];
				}
            }
        }
        //echo "<pre>";print_r($ary_orders_info);exit;
        $page = $obj_page->show();
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filter", $ary_get);
        $this->display();
    }

    /**
     * 订单详情
     * @author listen    
     * @date 2013-03-25
     */
    public function pageDetails() {
        $this->getSubNav(4, 0, 10, '订单详情页');
        if (!isset($_GET["o_id"]) || !is_numeric($_GET["o_id"])) {
            $this->error("参数订单ID不合法。");
        }

        $int_oid = $this->_get('o_id');
        $where = array('o_id' => $int_oid);

        $ary_orders = D('Orders')->where($where)->find();
        if(strpos($ary_orders['o_receiver_idcard'],':') && $ary_orders['o_receiver_idcard']){
            $ary_orders['o_receiver_idcard'] = decrypt($ary_orders['o_receiver_idcard']);
        }
        if(!strpos($ary_orders['o_receiver_idcard'],':') && $ary_orders['o_receiver_idcard']){
            $ary_orders['o_receiver_idcard'] = coverStr($ary_orders['o_receiver_idcard'],13);
        }
		if(strpos($ary_orders['o_receiver_mobile'],':') && $ary_orders['o_receiver_mobile']){
				   $ary_orders['o_receiver_mobile'] = decrypt($ary_orders['o_receiver_mobile']);
	    }
        if(!strpos($ary_orders['o_receiver_mobile'],':') && $ary_orders['o_receiver_mobile']){
            $ary_orders['o_receiver_mobile'] = vagueMobile($ary_orders['o_receiver_mobile']);
        }
		if($ary_orders['o_receiver_telphone'] && strpos($ary_orders['o_receiver_telphone'],':')){
		   $ary_orders['o_receiver_telphone'] = decrypt($ary_orders['o_receiver_telphone']);
	    }
        if(strpos($ary_orders['invoice_phone'],':') && $ary_orders['invoice_phone']){
            $ary_orders['invoice_phone'] = decrypt($ary_orders['invoice_phone']);
        }

        /*$RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
        if(preg_match($RegExp,$ary_orders['invoice_phone'])){
            $ary_orders['invoice_phone'] = vagueMobile($ary_orders['invoice_phone']);
        }*/
        //订单会员信息
        if (!empty($ary_orders['m_id']) && isset($ary_orders['m_id'])) {
            $ary_members = D('Members')->where(array('m_id' => $ary_orders['m_id']))->find();
        }
        if($ary_members['m_mobile'] && strpos($ary_members['m_mobile'],':')){
            $ary_members['m_mobile'] = decrypt($ary_members['m_mobile']);
        }
        if($ary_members['m_mobile'] && !strpos($ary_members['m_mobile'],':')){
            $ary_members['m_mobile'] = vagueMobile($ary_members['m_mobile']);
        }

        if($ary_members['m_telphone'] && strpos($ary_members['m_telphone'],':')){
            $ary_members['m_telphone'] = decrypt($ary_members['m_telphone']);
        }

        //支付方式
        if (isset($ary_orders['o_payment'])) {
            $payment = D('PaymentCfg')->field('pc_custom_name')->where(array('pc_id' => $ary_orders['o_payment']))->find();
            $ary_orders['payment_name'] = $payment['pc_custom_name'];
        }
        //会员地址
        $ary_city = D('CityRegion')->getFullAddressId($ary_members['cr_id']);
        if (!empty($ary_city) && is_array($ary_city)) {
            //会员省   
            $province = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[1]))->find();
            $ary_members['province'] = $province['cr_name'];
            //会员市
            $city = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[2]))->find();
            $ary_members['city'] = $city['cr_name'];
            //会员区
            $area = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[3]))->find();
            $ary_members['area'] = $area['cr_name'];
        }
        //echo D('CityRegion')->getLastSql();exit;
        $ary_orders_info = D('OrdersItems')->where($where)->select();
        if (!empty($ary_orders_info)) {
            foreach ($ary_orders_info as $k => $v) {
                // 判断价格区间
                $int_oirange = 0;
                $price_range = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id' => $v['pdt_id']))->find();
                if(!empty($price_range)){
                    $ary_orders_info[$k]['price_down'] = isset($price_range['pdt_price_down']) ? $price_range['pdt_price_down'] : '0.000';
                    $ary_orders_info[$k]['price_up'] = isset($price_range['pdt_price_up']) ? $price_range['pdt_price_up'] : '0.000';
                    if(!empty($v['oi_thd_sale_price']) && ($price_range['pdt_price_down'] >= $v['oi_thd_sale_price'] || $price_range['pdt_price_up'] <= $v['oi_thd_sale_price'])){
                        $int_oirange = 1;
                    }
                }
                // 是否超过价格区间
                $ary_orders_info[$k]['pdt_range'] = $int_oirange;
                //获取商品的规格，类型为2时，只是拼接销售属性的值
                $ary_orders_info[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id'], 2);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();

                $ary_orders_info[$k]['g_picture'] = getFullPictureWebPath($ary_goods_pic['g_picture']);
                //订单商品退款、退货状态
                $ary_orders_info[$k]['str_refund_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status', $v);
                //订单商品发货
                $ary_orders_info[$k]['str_ship_status'] = D('Orders')->getOrderItmesStauts('oi_ship_status', $v);
                //商品小计
                $ary_orders_info[$k]['subtotal'] = $v['oi_nums'] * $v['oi_price'];
                /* //组合商品当作普通商品显示
                  if($v['oi_type']==3){
                  $combo_sn=$v['g_sn'];
                  $tmp_ary=array('g_sn'=>$ary_orders_info[$k]['g_sn'],'pdt_spec'=>$ary_orders_info[$k]['pdt_spec'],'g_picture'=>$ary_orders_info[$k]['g_picture'],
                  'oi_g_name'=>$ary_orders_info[$k]['oi_g_name'],'pdt_id'=>$ary_orders_info[$k]['pdt_id']);

                  $combo_where = array('g_id' => $ary_orders_info[$k]['g_id'],'releted_pdt_id'=>$ary_orders_info[$k]['pdt_id']);
                  $combo_field = array('com_nums');
                  $combo_res=D('ReletedCombinationGoods')->getComboReletedList($combo_where,$combo_field);
                  $combo_num=$combo_res[0]['com_nums'];

                  $ary_combo[$combo_sn]['item'][$k]=$tmp_ary;
                  $ary_combo[$combo_sn]['num']=$ary_orders_info[$k]['oi_nums']/$combo_num;
                  $ary_combo[$combo_sn]['pdt_sale_price']=$ary_orders_info[$k]['pdt_sale_price'];
                  $ary_combo[$combo_sn]['o_all_price']=$ary_orders_info[$k]['o_all_price'];
                  $ary_combo[$combo_sn]['str_ship_status']=$ary_orders_info[$k]['str_ship_status'];
                  $ary_combo[$combo_sn]['str_refund_status']=$ary_orders_info[$k]['str_refund_status'];
                  unset($ary_orders_info[$k]);
                  }
                 */
            }
        }
        // echo "<pre>";print_r($ary_orders_info);exit();
        //订单状态
        //付款状态
        $ary_orders['str_pay_status'] = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_orders['o_pay_status']);
        $ary_orders['str_status'] = D('Orders')->getOrderItmesStauts('o_status', $ary_orders['o_status']);
        //订单状态
        $ary_orders_status = D('Orders')->getOrdersStatus($ary_orders['o_id']);
        //echo "<pre>";print_r($ary_orders_status);exit;
        //退款
        $ary_orders['refund_status'] = $ary_orders_status['refund_status'];
        //退货
        $ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
        //发货
        $ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
        //配送方式
        $ary_logistic_where = array('lt_id' => $ary_orders['lt_id']);
        $ary_field = array('lc_name');
        $ary_logistic_info = D('logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
        $ary_orders['str_logistic'] = $ary_logistic_info[0]['lc_name'];

        //echo "<pre>";print_r($ary_orders);exit;
        //物流信息 
        $ary_delivery = D('Orders')->ordersLogistic($int_oid);
        //处理作废		作废类型（1：用户不想要了;2：商品无货;3:重新下单;4:其他原因）
        switch ($ary_orders['cacel_type']) {
            case 1:
                $ary_orders['cacel_title'] = '用户不想要了';
                break;
            case 2:
                $ary_orders['cacel_title'] = '商品无货';
                break;
            case 3:
                $ary_orders['cacel_title'] = '重新下单';
                break;
            case 4:
                $ary_orders['cacel_title'] = '其他原因';
                break;
            default:
                break;
        }
        if ($ary_orders['admin_id']) {
            $ary_orders['admin_name'] = D('Orders')->where(array('u_id' => $ary_orders['admin_id']))->getField('u_name');
        }
//        echo "<pre>";print_r($ary_members);exit;
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
        $this->assign('members', $ary_members);
        $this->assign('ary_delivery', $ary_delivery);
        $this->assign('ary_orders_info', $ary_orders_info);
        $this->assign('ary_orders', $ary_orders);
		$this->assign('is_zt', $is_zt['IS_ZT']['sc_value']);
        $this->display();
    }

    /**
     * 订单日志
     * @author listen   
     * @date 2013-02-27
     */
    public function pageOrdersLog() {
        $this->getSubNav(4, 0, 10, '订单日志');
        $int_oid = $this->_get('o_id');
        // print_r($this->_session('admin_name'));exit;
        if (isset($int_oid)) {
            $where = array('o_id' => $int_oid);
        }
        $page_no = max(1, (int) $this->_get('p', '', 1));
        $page_size = 20;
        $count = D('OrdersLog')->where($where)->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        //订单日志
        $ary_orders_log = D('OrdersLog')->where($where)->order('ol_create desc')->page($page_no, $page_size)->select();
        //echo D('orders_log')->getLastSql();exit;
        //echo "<pre>";print_r($ary_orders_log);exit;
        $this->assign('int_oid', $int_oid);
        $this->assign('orders_log', $ary_orders_log);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 售后单据
     * @author listen
     * @date 2013-03-27
     */
    public function pageOrdersReceipt() {
        $this->getSubNav(4, 0, 10, '订单详情页');
        $int_oid = $this->_get('o_id');
        $page_no = max(1, (int) $this->_get('p', '', 1));
        $page_size = 20;

        $count = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')
                    ->join('fx_orders_items on fx_orders_refunds.o_id=fx_orders_items.o_id')
                    ->where(array('fx_orders_refunds.o_id' => $int_oid,'fx_orders_items.oi_refund_status'=>array('neq','1')))
                    ->count();
        /* $count = M('view_refunds', C('DB_PREFIX'), 'DB_CUSTOM')
                        ->join('fx_orders_items on fx_view_refunds.o_id = fx_orders_items.o_id')

                        ->where(array('fx_view_refunds.o_id' => $int_oid))->count(); */
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();

        /* $ary_refunds = M('view_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->distinct(true)
                        ->join('fx_orders_items on fx_view_refunds.o_id = fx_orders_items.o_id')
                        ->where(array('fx_view_refunds.o_id' => $int_oid,'fx_orders_items.oi_refund_status'=>array('neq','1')))->select();
         */
        $ary_refunds = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')
                    ->join('fx_orders_items on fx_orders_refunds.o_id=fx_orders_items.o_id')
                    ->where(array('fx_orders_refunds.o_id' => $int_oid,'fx_orders_items.oi_refund_status'=>array('neq','1')))
                    ->select();
       // echo M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        if (!empty($ary_refunds) && is_array($ary_refunds)) {
            foreach ($ary_refunds as $k => $v) {
                $ary_refunds[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id']);
                $ary_orders_status = D('Orders')->getOrdersStatus($v['o_id']);
                //echo "<pre>";print_r($ary_orders_status);exit;
                //退款
                //$ary_refunds[$k]['refund_status'] = $ary_orders_status['refund_status'];
                
                $ary_tmp_order = D('Orders')->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')->where(array('fx_orders_items.oi_id'=>$v['oi_id']))->find();
                $ary_refunds[$k]['refund_goods_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status',$ary_tmp_order,$v['or_id']);
                
                $ary_refunds[$k]['ori_num'] = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('oi_id'=>$v['oi_id']))->getField('oi_nums');
                
            }
        }
        // echo "<pre>";print_r($ary_refunds);exit;
        $this->assign('int_oid', $int_oid);
        $this->assign('ary_refunds', $ary_refunds);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 后台订单作废
     * @author listen  
     * @date 2013-02-27
     */
    public function ajaxInvalidOrder() {
        $int_oid = $this->_post('oid');
        $orders_comments = $this->_post('orders_comments');
        $cacel_type = $this->_post('cacel_type');

        if (isset($int_oid)) {
            //断订单是满足作废条件,没有同步到erp的订单
            $ary_where = array('o_id' => $int_oid, 'o_status' => 1);
            $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_orders = $orders->where($ary_where)->find();
			if(empty($ary_orders)){
				$this->ajaxReturn('订单不存在或已作废!');
				exit;				
			}
            if ($ary_orders['erp_sn'] != '') {
                $this->ajaxReturn(false);
                exit;
            } else {
                if($ary_orders['o_pay_status'] != 0){
                    $this->ajaxReturn('存在支付信息订单不能作废!');
                    exit;
                }
                $ary_order_data = array('o_status' => 2, 'o_seller_comments' => trim($orders_comments), 'cacel_type' => $cacel_type, 'o_update_time' => date('Y-m-d H:i:s'));
                $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR', 'ORDERS_OPERATOR', '1', '只记录第一次操作人');
                //查询订单是否存在操作者ID
                $admin_id = $_SESSION['Admin'];
                if ($resdata['ORDERS_OPERATOR']['sc_value'] == '1') {
                    $order_admin_id = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $int_oid))->getField('admin_id');
                    if (empty($order_admin_id)) {
                        $ary_order_data['admin_id'] = $admin_id;
                    }
                } else {
                    $ary_order_data['admin_id'] = $admin_id;
                }
                $return_orders = $orders->where(array('o_id' => $int_oid))->save($ary_order_data);
                if (false != $return_orders) {
                    // 冻结积分释放掉
                    $point_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $int_oid))->find();
                    if (isset($point_orders['o_freeze_point']) && $point_orders['o_freeze_point'] > 0 && $point_orders['m_id'] > 0) {
                         $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $point_orders['m_id']))->find();
                         if ($ary_member && $ary_member['freeze_point'] > 0) {
                            //作废订单返还冻结积分日志
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
                                     $this->ajaxReturn('作废订单返回冻结积分失败');
                                     exit;
                                 }
                            }else{
                                 $this->ajaxReturn('作废订单返回冻结积分写日志失败');
                                 exit;
                            }
                        }else{
                            $this->ajaxReturn('作废订单返回冻结积分没有找到要返回的用户冻结金额');
                            exit;
                        }
                    }
                    //还原,支出冻结优惠券
                    $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_oid))->find();
                    if (!empty($ary_coupon) && is_array($ary_coupon)) {
                        $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_oid))->save(array('c_used_id' => 0, 'c_order_id' => 0, 'c_is_use' => 0));
                        if (!$res_coupon) {
                            $this->ajaxReturn(false);
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
                            'bn_desc' => '后台强制作废成功,返还红包金额：'.$ary_bonus['bn_money'].'元',
                            'o_id' => $ary_bonus['o_id'],
                            'bn_finance_verify' => '1',
                            'bn_service_verify' => '1',
                            'bn_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                        if(!$res_bonus){
                            $this->ajaxReturn(false);
                            exit;
                        }
                    }
                    //还原,支出冻结储值卡金额
                    $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'ci_type'=>array('neq','0')))->find();
                    if(!empty($ary_cards) && is_array($ary_cards)){
                        $arr_cards = array(
                            'ct_id' => '2',
                            'm_id'  => $ary_cards['m_id'],
                            'ci_create_time'  => date("Y-m-d H:i:s"),
                            'ci_type' => '0',
                            'ci_money' => $ary_cards['ci_money'],
                            'ci_desc' => '后台强制作废成功,返还储值卡金额：'.$ary_cards['ci_money'].'元',
                            'o_id' => $ary_cards['o_id'],
                            'ci_finance_verify' => '1',
                            'ci_service_verify' => '1',
                            'ci_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_cards = D('CardsInfo')->addCards($arr_cards);
                        if(!$res_cards){
                            $this->ajaxReturn(false);
                            exit;
                        }
                    }
                    //还原,支出冻结金币金额
					/**
                    $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'ji_type'=>array('neq','0')))->find();
                    if(!empty($ary_jlb) && is_array($ary_jlb)){
                        $arr_jlb = array(
                            'jt_id' => '2',
                            'm_id'  => $ary_jlb['m_id'],
                            'ji_create_time'  => date("Y-m-d H:i:s"),
                            'ji_type' => '0',
                            'ji_money' => $ary_jlb['ji_money'],
                            'ji_desc' => '后台强制作废成功,返还金币金额：'.$ary_jlb['ji_money'].'元',
                            'o_id' => $ary_jlb['o_id'],
                            'ji_finance_verify' => '1',
                            'ji_service_verify' => '1',
                            'ji_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                        if(!$res_jlb){
                            $this->ajaxReturn(false);
                            exit;
                        }
                    }
                    **/

                    
                    //销量返回
                    $return_orders_items = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')
                    ->where(array('o_id' => $int_oid))->field('oi_nums,o_id,g_id,pdt_id,oi_type,fc_id')->select(); 
                    foreach($return_orders_items as $v){
                        if($v['oi_type']==5 && !empty($v['fc_id'])){
                            $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $v ['fc_id']))->setDec("gp_now_number",$v['oi_nums']);
                            if (!$retun_buy_nums) {
                                $this->ajaxReturn(false);
                                exit;
                            }
                        }elseif($v['oi_type']==7 && !empty($v['fc_id'])){
                            $retun_spike_nums=D("Spike")->where(array('sp_id' => $v['fc_id']))->setDec("sp_now_number",$v['oi_nums']);
                            if(!$retun_spike_nums){
                                $this->ajaxReturn(false);
                                exit;
                            }
                        }
                        $ary_goods_num = M("goods_info")->where(array(
                                    'g_id' => $v ['g_id']
                                ))->data(array(
                                    'g_salenum' => array(
                                        'exp',
                                        'g_salenum - '.$v['oi_nums']
                                    )
                                ))->save();
                                
                        if (!$ary_goods_num) {
                            $this->ajaxReturn(false);
                            exit;
                        }
						//库存释放
						if($ary_orders['o_payment'] == 3 || $ary_orders['o_payment'] == 6){
							$item_stock_info=M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->field('pdt_freeze_stock,pdt_stock,pdt_total_stock')->where(array('g_id' => $v ['g_id'],'pdt_id'=>$v ['pdt_id']))->find();
							if(isset($item_stock_info['pdt_freeze_stock']) && $item_stock_info['pdt_freeze_stock'] >0){
								$item_stock_data['pdt_freeze_stock']=$item_stock_info['pdt_freeze_stock']-$v['oi_nums'];
								if($item_stock_data['pdt_freeze_stock'] < 0 ){
									$item_stock_data['pdt_freeze_stock']= 0;
									$item_stock_data['pdt_stock'] = $item_stock_info['pdt_stock'] + $item_stock_info['pdt_freeze_stock'];
									//$item_stock_data['pdt_total_stock'] =$item_stock_info['pdt_total_stock'] + $item_stock_info['pdt_freeze_stock'];
								}else{
									$item_stock_data['pdt_stock'] = $item_stock_info['pdt_stock'] + $v['oi_nums'];
									//$item_stock_data['pdt_total_stock'] =$item_stock_info['pdt_total_stock'] + $v['oi_nums'];
								}
								$updata_item_stock = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v ['g_id'],'pdt_id'=>$v['pdt_id']))->save($item_stock_data);
								if(!$updata_item_stock){
									M()->rollback();
									$this->ajaxReturn(false);
									exit;
								}
							}							
						}

						
                    }					
                    //更新日志表
                    $ary_orders_log = array(
                        'o_id' => $int_oid,
                        'ol_behavior' => '订单作废:' . $orders_comments,
                        'ol_uname' => $this->_session('admin_name'),
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->where(array('o_id' => $int_oid))->add($ary_orders_log);
                    if (!$res_orders_log) {
                        $this->ajaxReturn(false);
                        exit;
                    }
					//判断此作废订单是否是第三方平台订单
					$int_o_source_id = $orders->where(array("o_id"=>$int_oid,"o_status"=>2))->getField("o_source_id");
					if(0 != $int_o_source_id) {
						$return = D("ThdOrders")->where(array('to_oid'=>$int_o_source_id))->find();
						if($return){
							D("ThdOrders")->where(array('to_oid'=>$int_o_source_id))->save(array('to_tt_status'=>0));
						}
					}
                    $this->ajaxReturn(true);
                    exit;
                } else {
                    $this->ajaxReturn(false);
                    exit;
                }
            }
        }
    }

    /**
     * 退款状态
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-07
     */
    public function doOrderStatus() {
		$ary_post = $this->_post();
        $orders = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM');
        if (!empty($ary_post['field']) && isset($ary_post['field'])) {
            $ary_res = $orders->where(array('or_id' => $ary_post['id']))->find();
            if (!empty($ary_post['field']) && $ary_post['field'] == 'or_processing_status') {
                if ($ary_res['or_service_verify'] == '1' && $ary_res['or_finance_verify'] == '1') {
                    $this->error('单据已客审已财审,不可作废');
                    exit;
                }
                $ary_order_data = array();
                $ary_order_data['or_processing_status'] = 2;
                $ary_order_data['or_update_time'] = date('Y-m-d H:i:s');
                $ary_order_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['u_name'] = $_SESSION['admin_name'];
                $ary_order_data['or_refuse_reason'] = $ary_post['val'];
                $orders->startTrans();
                $ary_result = $orders->where(array('or_id' => $ary_post['id']))->data($ary_order_data)->save();
                // 更新订单状态
                $ary_order_item_data = array();
                $ary_order_item_data['oi_refund_status'] = 6;
                $orders_refunds_items = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM');
                $oi_id = $orders_refunds_items->where(array('or_id'=>$ary_post['id']))->getField("oi_id",true);
                $map['oi_id'] = array('in',$oi_id);
                D('OrdersItems')->where($map)->data($ary_order_item_data)->save();

                if ((false === $ary_result) || (false === $result)) {
                    $orders->rollback();
                    $this->error('单据作废失败');
                    exit;
                }
                //更新日志表
                $ary_orders_log = array(
                		'o_id' => $ary_res['o_id'],
                		'ol_behavior' => '订单作废',
                		'ol_text' => serialize($data)
                );
                D('OrdersLog')->addOrderLog($ary_orders_log);
                $orders->commit();
                $this->success("操作成功");
                exit;
            }
            if ($ary_post['field'] == 'or_service_verify' && $ary_post['val'] == 1 && $ary_res['or_service_verify'] == 1) {
                $this->error("该单据已客审");
            }
            if ($ary_post['field'] == 'or_finance_verify' && $ary_post['val'] == 1 && $ary_res['or_finance_verify'] == 1) {
                $this->error("该单据已财审");
            }
            $orders->startTrans();
            $ary_order_data = array();
            $ary_order_data[$ary_post['field']] = $ary_post['val'];
			if($ary_post['or_refunds_type']=='' || empty($ary_post['or_refunds_type'])){
				$this->error("请选择退款方式");
			}
            $ary_order_data['or_refunds_type'] = $ary_post['or_refunds_type'];
            $ary_order_data['or_seller_memo'] = $ary_post['or_seller_memo'];
            if ($ary_order_data['or_refunds_type'] == 2) {
                $ary_order_data['or_bank'] = $ary_post['or_bank'];
                $ary_order_data['or_account'] = $ary_post['or_account'];
                $ary_order_data['or_payee'] = $ary_post['or_payee'];
            }
            if ($ary_post['field'] == 'or_finance_verify') {
                $ary_order_data['or_finance_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['or_finance_u_name'] = $_SESSION['admin_name'];
                $ary_order_data['or_finance_time'] = date('Y-m-d H:i:s');
                $ary_order_data['or_processing_status'] = 1;
            } elseif ($ary_post['field'] == 'or_service_verify') {
                $ary_order_data['or_service_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['or_service_u_name'] = $_SESSION['admin_name'];
               // $ary_order_data['service_time'] = date('Y-m-d H:i:s');
                $ary_order_data['or_service_time'] = date('Y-m-d H:i:s');
            }
            //添加操作人
            /* if($ary_post['or_finance_verify'] == '1'){
              $ary_order_data['or_finance_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
              $ary_order_data['or_finance_u_name'] = $_SESSION['admin_name'];
              $ary_order_data['or_finance_time'] = date('Y-m-d H:i:s');
              $ary_order_data['or_processing_status'] = 1;
              }else{
              $ary_order_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
              $ary_order_data['u_name'] = $_SESSION['admin_name'];
              //添加客服确认时间
              if($ary_res['or_service_verify'] == '1'){
              $ary_order_data['or_service_time'] = date('Y-m-d H:i:s');
              }

              } */
            $ary_result = $orders->where(array('or_id' => $ary_post['id']))->data($ary_order_data)->save();
            if (FALSE != $ary_result) {
                $balance = new Balance();
                $ary_balance = $balance->getBalanceInfo(array('or_id' => $ary_res['or_return_sn']));
                //获取订单号
                $o_id = $orders->where(array('or_id' => $ary_post['id']))->getField('o_id');
                $o_payment = M('orders')->where(array('o_id' => $o_id))->getField('o_payment');

                //获取订单支付方式
                $pay = 0;
				//类型为退回原账号且支付类型为预存款
                if ($o_payment == 1 && $ary_post['or_refunds_type'] == 3) {
                    $pay = 1;
                }
                if ($ary_post['or_refunds_type'] == 1) {
                    $pay = 1;
                }
				//类型为且支付类型为预存款
                if ($ary_res['or_refund_type'] == 2 && $o_payment == 1 && $ary_post['or_refunds_type']==1) {
                    //如果是退货单
                    $pay = 1;
                }
				
                //满足财审并当前退款方式为退款至预存款，生成结余款调整单
                if (!empty($ary_post['field']) && $ary_post['field'] == 'or_finance_verify' && $pay == 1) {
					if (!empty($ary_balance) && is_array($ary_balance)) {
                        $ary_post['or_id'] = $ary_res['or_return_sn'];
                        $ary_post['m_id'] = $ary_res['m_id'];
                        $ary_post['or_money'] = $ary_res['or_money'];
                        $ary_post['or_refund_type'] = $ary_res['or_refund_type'];
                        $ary_rest = $balance->doBalanceInfoStatus($ary_post);
                        if ($ary_rest['success']) {
                        	//更新日志表
                        	$ary_orders_log = array(
                        			'o_id' => $ary_res['o_id'],
                        			'ol_behavior' => '财审成功，退结余款成功',
                        			'ol_text' => serialize($ary_post)
                        	);
                        	$res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                        	if (!$res_orders_log) {
                        		$orders->rollback();
                        		$this->error('更新失败');
                        		exit;
                        	}
                            $orders->commit();
                            $this->success($ary_rest['msg']);
                        } else {
                            $orders->rollback();
                            $this->error($ary_rest['msg']);
                        }
                    } else {
                        //获取退款单详情
                        $ary_order_refund_info = $orders->where(array('or_id' => $ary_post['id']))->find();
                        $arr_data = array();

                        $arr_data['o_id'] = $ary_order_refund_info['o_id'];
                        $arr_data['m_id'] = $ary_order_refund_info['m_id'];
                        $arr_data['bi_accounts_receivable'] = $ary_order_refund_info['or_account'];
                        $arr_data['bi_accounts_bank'] = $ary_order_refund_info['or_bank'];
                        $arr_data['bi_payeec'] = $ary_order_refund_info['or_payee'];
                        $arr_data['bi_type'] = '0';
                        $arr_data['or_id'] = $ary_order_refund_info['or_return_sn'];
                        $arr_data['bi_money'] = $ary_order_refund_info['or_money'];
                        $arr_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                        $arr_data['bi_create_time'] = date("Y-m-d H:i:s");
                        $arr_data['bi_payment_time'] = date("Y-m-d H:i:s");
                        $arr_data['bt_id'] = '2';
                        $arr_data['bi_finance_verify'] = '1';
                        $arr_data['bi_service_verify'] = '1';
                        $arr_data['bi_verify_status'] = '1';
                        $arr_data['bi_desc'] = '买家退款或退货';
                        $ary_rest = $balance->addBalanceInfo($arr_data);
                        //获取结余款调整单基本表
                        $balance_info = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($arr_data)->find();

                        //写入客审结余款调整单日志
                        $balance_server_log['u_id'] = $ary_order_refund_info['or_service_u_id'];
                        $balance_server_log['u_name'] = $ary_order_refund_info['or_service_u_name'];
                        $balance_server_log['bi_sn'] = $balance_info['bi_sn'];
                        $balance_server_log['bvl_desc'] = '审核成功';
                        $balance_server_log['bvl_type'] = '2';
                        $balance_server_log['bvl_status'] = '1';
                        $balance_server_log['bvl_create_time'] = $ary_order_refund_info['or_service_time'];
                        if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_server_log)){
                            $orders->rollback();
                            $this->error("生成结余款调整单日志失败");
                        }
                        //写入财审结余款调整单日志
                        $balance_finance_log['u_id'] = $ary_order_refund_info['or_finance_u_id'];
                        $balance_finance_log['u_name'] = $ary_order_refund_info['or_finance_u_name'];
                        $balance_finance_log['bi_sn'] = $balance_info['bi_sn'];
                        $balance_finance_log['bvl_desc'] = '审核成功';
                        $balance_finance_log['bvl_type'] = '3';
                        $balance_finance_log['bvl_status'] = '1';
                        $balance_finance_log['bvl_create_time'] = $ary_order_refund_info['or_finance_time'];
                        if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_finance_log)){
                            $orders->rollback();
                            $this->error("生成结余款调整单日志失败");
                        }
                        //写入支付序列日志
                        $m_balance = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_order_refund_info['m_id']))->getField('m_balance');
                        $running_acc['ra_payment_method'] = "预存款";
                        $running_acc['ra_before_money'] = $m_balance - $ary_order_refund_info['or_money'];
                        $running_acc['ra_after_money'] = $m_balance;
                        $running_acc['ra_money'] = $ary_order_refund_info['or_money'];
                        $running_acc['m_id'] = $ary_order_refund_info['m_id'];
                        $running_acc['ra_memo'] = "买家退款或退货";
                        $running_acc['ra_type'] = 4;
                        M('running_account',C('DB_PREFIX'),'DB_CUSTOM')->add($running_acc);
					}
			     }
				 if (!empty($ary_post['field']) && $ary_post['field'] == 'or_finance_verify') {
				 //暂时这么处理$ary_rest['success']
						$ary_rest['success'] = 1;
                        if ($ary_rest['success']) {
                            if ($ary_res['or_refund_type'] == 1) {
                                //退款单
                                $order_item['oi_refund_status'] = 4;
                                $order_item['oi_update_time'] = date('Y-m-d H:i:s');
                                if (false === M('orders_items')->where(array('o_id' => $ary_res['o_id']))->save($order_item)) {
                                    $orders->rollback();
                                    $this->error("更新订单状态失败");
                                }
								//库存,销量返回
								$orderItems = M('orders_items')->field('oi_id,o_id,g_id,g_sn,pdt_sn,pdt_id,oi_nums,oi_ship_status,oi_refund_status,oi_type,fc_id')->where(array('o_id'=>$ary_res['o_id']))->select();								//库存返回
								foreach($orderItems as $item){
									//已发货，无需退货销量和库存不返回
									if($item['oi_refund_status'] == 4 && $item['oi_ship_status'] == 2){
										
									}else{
										if(empty($item['g_sn']) || empty($item['pdt_sn'])){
											$orders->rollback();
											$this->error("销量返回失败");exit;
										}
										$stock_res = M('goods_products')->where(array('g_sn'=>$item['g_sn'],'pdt_sn'=>$item['pdt_sn']))
										->data(array(
                                            'pdt_update_time'=>date('Y-m-d H:i:s'),
                                            'pdt_freeze_stock'=>array('exp',"pdt_freeze_stock-".$item['oi_nums']),
                                            'pdt_stock'=>array('exp',"pdt_stock+".$item['oi_nums']))
                                        )
										->save();
										/*****@author Tom 分销商库存返回 start *****/
										$ary_inventory = array(
											'pdt_id' => $item['pdt_id'],
											'm_id' => $ary_order_refund_info['m_id'],
											'num' => $item['oi_nums'],
											'u_id' => $_SESSION['Admin'],
											'srr_id' => 0
											);
										$stock_inventory_res = D('GoodsProducts')->BackInventoryLockStock($ary_inventory);
										/*****@author Tom 分销商库存返回 end *****/
										if(!$stock_res){
											//$orders->rollback();
											//$this->error("库存返回失败");exit;
										}
										$goods_num_res = M("goods_info")->where(array(
													'g_id' => $item ['g_id']
												))->data(array(
													'g_salenum' => array(
														'exp',
														'g_salenum - '.$item['oi_nums']
													)
												))->save();
										if(false === $goods_num_res){
											$orders->rollback();
											$this->error("销量返回失败");exit;
										}
										//团购秒杀返回
										if($item['oi_type']==5 && !empty($item['fc_id'])){
											$return_groupbuy_nums = D("Groupbuy")->where(array('gp_id' => $item['fc_id']))->setDec("gp_now_number",$item['oi_nums']);
											if(!$return_groupbuy_nums){
												$orders->rollback();
												$this->error("订单团购量更新失败");exit;
											}
										}elseif($item['oi_type']==7 && !empty($item['fc_id'])){
											$retun_spike_nums=D("Spike")->where(array('sp_id' => $item['fc_id']))->setDec("sp_now_number",$item['oi_nums']);
											if(!$retun_spike_nums){
												$orders->rollback();
												$this->error("订单秒杀量更新失败");exit;
											}
										}
									}									
								}

								if($stock_res){
									$stock_res = array(
											'o_id' => $ary_res['o_id'],
											'ol_behavior' => '订单退款库存返回成功',
											'ol_text' => serialize($orderItems)
									);
									$stock_res_log = D('OrdersLog')->addOrderLog($stock_res);
									if(!$stock_res_log){
										$orders->rollback();
										$this->error('更新库存日志失败');
										exit;
									}
								}
								// 冻结积分释放掉
                                $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $ary_res['o_id']))->find();
                                if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                                     $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                     if ($ary_member && $ary_member['freeze_point'] > 0) {
                                        //订单退款返还冻结积分日志
                                        $ary_log = array(
                                                    'type'=>8,
                                                    'consume_point'=> 0,
                                                    'reward_point'=> $ary_orders['o_freeze_point']
                                                    );
                                        $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                        if($ary_info['status'] == 1){
                                             $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_orders['o_freeze_point'];
                                             $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                                             if(!$res_member){
                                                 $orders->rollback();
                                                 $this->error('退款返回冻结积分失败');
                                                 exit;
                                             }
                                        }else{
                                             $orders->rollback();
                                             $this->error('退款返回冻结积分写日志失败');
                                             exit;
                                        }
                                    }else{
                                         $orders->rollback();
                                         $this->error('退款返回冻结积分没有找到要返回的用户冻结金额');
                                         exit;
                                    }
                                }
                                $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                                if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                                    //还原,支出冻结红包金额
                                    $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'bn_type'=>array('neq','0')))->find();
                                    if(!empty($ary_bonus) && is_array($ary_bonus)){
                                        $arr_bonus = array(
                                            'bt_id' => '4',
                                            'm_id'  => $ary_bonus['m_id'],
                                            'bn_create_time'  => date("Y-m-d H:i:s"),
                                            'bn_type' => '0',
                                            'bn_money' => $ary_bonus['bn_money'],
                                            'bn_desc' => '退款申请成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                                            'o_id' => $ary_bonus['o_id'],
                                            'bn_finance_verify' => '1',
                                            'bn_service_verify' => '1',
                                            'bn_verify_status' => '1',
                                            'single_type' => '2'
                                        );
                                        $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                        if($res_bonus === true){
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还红包成功,',
                                                    'ol_text' => serialize($ary_bonus)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还红包失败,红包金额为:'.$ary_bonus['bn_money'],
                                                    'ol_text' => serialize($ary_bonus)
                                            );
                                             D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                }
                                $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                if($card_status['CARDS_AUTO_OPEN'] == 1){
                                    //还原,支出冻结储值卡金额
                                    $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ci_type'=>array('neq','0')))->find();
                                    if(!empty($ary_cards) && is_array($ary_cards)){
                                        $arr_cards = array(
                                            'ct_id' => '2',
                                            'm_id'  => $ary_cards['m_id'],
                                            'ci_create_time'  => date("Y-m-d H:i:s"),
                                            'ci_type' => '0',
                                            'ci_money' => $ary_cards['ci_money'],
                                            'ci_desc' => '退款申请成功返还储值卡金额：'.$ary_cards['ci_money'].'元',
                                            'o_id' => $ary_cards['o_id'],
                                            'ci_finance_verify' => '1',
                                            'ci_service_verify' => '1',
                                            'ci_verify_status' => '1',
                                            'single_type' => '2'
                                        );
                                        $res_cards = D('CardsInfo')->addCards($arr_cards);
                                        if($res_cards === true){
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还储值卡成功,',
                                                    'ol_text' => serialize($ary_cards)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还储值卡失败,储值卡金额为:'.$ary_cards['ci_money'],
                                                    'ol_text' => serialize($ary_cards)
                                            );
                                             D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                }
								/**
                                $jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                                if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                    //还原,支出冻结金币金额
                                    $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ji_type'=>array('neq','0')))->find();
                                    if(!empty($ary_jlb) && is_array($ary_jlb)){
                                        $arr_jlb = array(
                                            'jt_id' => '2',
                                            'm_id'  => $ary_jlb['m_id'],
                                            'ji_create_time'  => date("Y-m-d H:i:s"),
                                            'ji_type' => '0',
                                            'ji_money' => $ary_jlb['ji_money'],
                                            'ji_desc' => '退款申请成功返还金币金额：'.$ary_jlb['ji_money'],
                                            'o_id' => $ary_jlb['o_id'],
                                            'ji_finance_verify' => '1',
                                            'ji_service_verify' => '1',
                                            'ji_verify_status' => '1',
                                            'single_type' => '2'
                                        );
                                        $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                        if($res_jlb === true){
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还金币成功,',
                                                    'ol_text' => serialize($ary_jlb)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退款返还金币失败,金币金额为:'.$ary_jlb['ji_money'],
                                                    'ol_text' => serialize($ary_jlb)
                                            );
                                             D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                }
								**/
								//检测是否需要退优惠券
								$coupon_info = M('orders')->where(array('o_id'=>$ary_res['o_id']))->field('o_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date')->find();
								if($coupon_info['o_coupon'] == '1' && $coupon_info['coupon_end_date'] >= date('Y-m-d H:i:s')){
									//赠送的优惠券作废
									$res = M('coupon')->where(array('c_sn'=>$coupon_info['coupon_sn'],'c_is_use'=>0))->data(array('c_is_use'=>'4'))->save();
									if($res){
									    $ary_orders_log = array(
												'o_id' => $ary_res['o_id'],
												'ol_behavior' => '退优惠券成功,',
												'ol_text' => serialize($coupon_info)
										);
										D('OrdersLog')->addOrderLog($ary_orders_log);
									}else{
										$ary_orders_log = array(
												'o_id' => $ary_res['o_id'],
												'ol_behavior' => '退优惠券失败,优惠券已不存在或已被使用,优惠券为:'.$coupon_info['coupon_sn'],
												'ol_text' => serialize($coupon_info)
										);
										 D('OrdersLog')->addOrderLog($ary_orders_log);
									}
								}
								//消耗的优惠券还原
                                $res_coupon = D("CouponActivities")->delCoupon($ary_res['o_id']);
                                if (!$res_coupon) {
                                    $orders->rollback();
                                    $this->error('优惠券还原失败');
                                    exit();
                                }
                                $ary_orders_log = array(
                                		'o_id' => $ary_res['o_id'],
                                		'ol_behavior' => '财审成功，订单退款成功',
                                		'ol_text' => serialize($order_item)
                                );
                                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                                if (!$res_orders_log) {
                                	$orders->rollback();
                                	$this->error('更新失败');
                                	exit;
                                }
                            } elseif ($ary_res['or_refund_type'] == 2) {
                                $order_item['oi_refund_status'] = 5; //退货成功
                                $order_item['oi_update_time'] = date('Y-m-d H:i:s');
                                //退货单
                                $ary_oi_id = M('orders_refunds_items')->field(array('oi_id,ori_num'))->where(array('or_id' => $ary_res['or_id']))->select();
                                $orderItems = M('orders_items')->field('oi_id,o_id,g_id,g_sn,pdt_sn,pdt_id,oi_bonus_money,oi_nums,oi_cards_money,oi_jlb_money,oi_point_money,oi_type,fc_id')->where(array('o_id'=>$ary_res['o_id']))->select();
								foreach ($ary_oi_id as $key) {
                                    if (false === M('orders_items')->where(array('oi_id' => $key['oi_id']))->save($order_item)) {
                                        $orders->rollback();
                                        $this->error("更新订单状态失败");
                                    }else{
										//库存返回
										foreach($orderItems as $item){
                                            if(empty($item['g_sn']) || empty($item['pdt_sn'])){
                                                $orders->rollback();
                                                $this->error("销量返回失败");exit;
                                            }
											if($item['oi_id'] == $key['oi_id']){
												$stock_res = M('goods_products')->where(array('g_sn'=>$item['g_sn'],'pdt_sn'=>$item['pdt_sn']))
												->data(array(
                                                    'pdt_update_time'=>date('Y-m-d H:i:s'),
                                                    'pdt_freeze_stock'=>array('exp',"pdt_freeze_stock-".$key['ori_num']),
                                                    'pdt_stock'=>array('exp',"pdt_stock+".$key['ori_num'])))
												->save();
												if(!$stock_res){
													//$orders->rollback();
													//$this->error("库存返回失败");exit;
												}
                                                $goods_num_res = M("goods_info")->where(array(
                                                            'g_id' => $item['g_id']
                                                        ))->data(array(
                                                            'g_salenum' => array(
                                                                'exp',
                                                                'g_salenum - '.$key['ori_num']
                                                            )
                                                        ))->save();
                                                if(false === $goods_num_res){
                                                    $orders->rollback();
                                                    $this->error("销量返回失败");exit;
                                                }
												//团购秒杀返回
												if($item['oi_type']==5 && !empty($item['fc_id'])){
													$return_groupbuy_nums = D("Groupbuy")->where(array('gp_id' => $item['fc_id']))->setDec("gp_now_number",$item['oi_nums']);
													if(!$return_groupbuy_nums){
														$orders->rollback();
														$this->error("订单团购量更新失败");exit;
													}
												}elseif($item['oi_type']==7 && !empty($item['fc_id'])){
													
													$retun_spike_nums=D("Spike")->where(array('sp_id' => $item['fc_id']))->setDec("sp_now_number",$item['oi_nums']);
													if(!$retun_spike_nums){
														$orders->rollback();
														$this->error("订单秒杀量更新失败");exit;
													}
												}
											}
										}
									}
                                }
								if($stock_res){
									$stock_res = array(
											'o_id' => $ary_res['o_id'],
											'ol_behavior' => '订单退货库存返回成功',
											'ol_text' => serialize($ary_oi_id)
									);
									$stock_res_log = D('OrdersLog')->addOrderLog($stock_res);
									if(!$stock_res_log){
										$orders->rollback();
										$this->error('更新库存日志失败');
										exit;									
									}
								}
								if(count($ary_oi_id) == count($orderItems)){
                                    // 退货返回积分
                                    $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $ary_res['o_id']))->find();
                                    if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                                         $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('total_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                        // if ($ary_member && $ary_member['freeze_point'] > 0) {
                                            //订单退货返还冻结积分日志
                                            $ary_log = array(
                                                        'type'=>8,
                                                        'consume_point'=> 0,
                                                        'reward_point'=> $ary_orders['o_freeze_point']
                                                        );
                                            $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                            if($ary_info['status'] == 1){
                                                 $ary_member_data['total_point'] = $ary_member['total_point'] + $ary_orders['o_freeze_point'];
                                                 $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                                                 if(!$res_member){
                                                     $orders->rollback();
                                                     $this->error('退货返回积分失败');
                                                     exit;
                                                 }
                                            }else{
                                                 $orders->rollback();
                                                 $this->error('退货返回积分写日志失败');
                                                 exit;
                                            }
                                        //}else{
                                          //   $orders->rollback();
                                         //    $this->error('退货返回冻结积分没有找到要返回的用户冻结金额');
                                         //    exit;
                                       // }
                                    }
                                    $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                                    if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结红包金额
                                        $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'bn_type'=>array('neq','0')))->find();
                                        if(!empty($ary_bonus) && is_array($ary_bonus)){
                                            $arr_bonus = array(
                                                'bt_id' => '4',
                                                'm_id'  => $ary_bonus['m_id'],
                                                'bn_create_time'  => date("Y-m-d H:i:s"),
                                                'bn_type' => '0',
                                                'bn_money' => $ary_bonus['bn_money'],
                                                'bn_desc' => '退货申请成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                                                'o_id' => $ary_bonus['o_id'],
                                                'bn_finance_verify' => '1',
                                                'bn_service_verify' => '1',
                                                'bn_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                            if($res_bonus === true){
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还红包成功,',
                                                        'ol_text' => serialize($ary_bonus)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还红包失败,红包金额为:'.$ary_bonus['bn_money'],
                                                        'ol_text' => serialize($ary_bonus)
                                                );
                                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
                                    $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                    if($card_status['CARDS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结储值卡金额
                                        $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ci_type'=>array('neq','0')))->find();
                                        if(!empty($ary_cards) && is_array($ary_cards)){
                                            $arr_cards = array(
                                                'ct_id' => '2',
                                                'm_id'  => $ary_cards['m_id'],
                                                'ci_create_time'  => date("Y-m-d H:i:s"),
                                                'ci_type' => '0',
                                                'ci_money' => $ary_cards['ci_money'],
                                                'ci_desc' => '退货申请成功返还储值卡金额：'.$ary_cards['ci_money'].'元',
                                                'o_id' => $ary_cards['o_id'],
                                                'ci_finance_verify' => '1',
                                                'ci_service_verify' => '1',
                                                'ci_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_cards = D('CardsInfo')->addCards($arr_cards);
                                            if($res_cards === true){
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还储值卡成功,',
                                                        'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还储值卡失败,储值卡金额为:'.$ary_cards['ci_money'],
                                                        'ol_text' => serialize($ary_cards)
                                                );
                                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
									/**
                                    $jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                                    if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                            //还原,支出冻结金币金额
                                        $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ji_type'=>array('neq','0')))->find();
                                        if(!empty($ary_jlb) && is_array($ary_jlb)){
                                            $arr_jlb = array(
                                                'jt_id' => '2',
                                                'm_id'  => $ary_jlb['m_id'],
                                                'ji_create_time'  => date("Y-m-d H:i:s"),
                                                'ji_type' => '0',
                                                'ji_money' => $ary_jlb['ji_money'],
                                                'ji_desc' => '退货申请成功返还金币金额：'.$ary_jlb['ji_money'].'元',
                                                'o_id' => $ary_jlb['o_id'],
                                                'ji_finance_verify' => '1',
                                                'ji_service_verify' => '1',
                                                'ji_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                            if($res_jlb === true){
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还金币成功,',
                                                        'ol_text' => serialize($ary_jlb)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还金币失败,金币金额为:'.$ary_jlb['ji_money'],
                                                        'ol_text' => serialize($ary_jlb)
                                                );
                                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
									**/
                                    //完全退货才退优惠券
									$coupon_info = M('orders')->where(array('o_id'=>$ary_res['o_id']))->field('o_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date')->find();
									if($coupon_info['o_coupon'] == '1' && $coupon_info['coupon_end_date'] >= date('Y-m-d H:i:s')){
										//作废
										$res = M('coupon')->where(array('c_sn'=>$coupon_info['coupon_sn'],'c_is_use'=>0))->data(array('c_is_use'=>'4'))->save();
										if($res){
											$ary_orders_log = array(
													'o_id' => $ary_res['o_id'],
													'ol_behavior' => '退优惠券成功,',
													'ol_text' => serialize($coupon_info)
											);
											D('OrdersLog')->addOrderLog($ary_orders_log);
										}else{
											$ary_orders_log = array(
													'o_id' => $ary_res['o_id'],
													'ol_behavior' => '退优惠券失败,优惠券已不存在或已被使用,优惠券为:'.$coupon_info['coupon_sn'],
													'ol_text' => serialize($coupon_info)
											);
											 D('OrdersLog')->addOrderLog($ary_orders_log);
										}
									}
									//消耗的优惠券还原
                                    $res_coupon = D("CouponActivities")->delCoupon($ary_res['o_id']);
                                    if (!$res_coupon) {
                                        $orders->rollback();
                                        $this->error('优惠券还原失败');
                                        exit();
                                    }
								}else{
									//部分退货优惠券不退，其他金额等比例退回
									$oi_point_money = 0;
									$oi_bonus_money = 0;
									$oi_cards_money = 0;
									$oi_jlb_money = 0;
									foreach($orderItems as $item){
										foreach($ary_oi_id as $ary_oi_info){
											if($item['oi_id'] == $ary_oi_info['oi_id']){
												$oi_point_money = $oi_point_money+$item['oi_point_money'];
												$oi_bonus_money = $oi_bonus_money+$item['oi_bonus_money'];
												$oi_cards_money = $oi_cards_money+$item['oi_cards_money'];
												$oi_jlb_money = $oi_jlb_money+$item['oi_jlb_money'];											
											}
										}
									}
									//var_dump($oi_point_money,$oi_bonus_money,$oi_cards_money,$oi_jlb_money);die();
									// 冻结积分释放掉
                                    $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_id')->where(array('o_id' => $ary_res['o_id']))->find();
                                    if (isset($oi_point_money) && $oi_point_money > 0 && $ary_orders['m_id'] > 0) {
                                         $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                         $point_config = D('PointConfig')->getConfigs();
										 if($point_config['consumed_points']>0){
										 	if ($ary_member && $ary_member['freeze_point'] > 0) {
												//订单退货返还冻结积分日志
												$ary_log = array(
															'type'=>8,
															'consume_point'=> 0,
															'reward_point'=> $oi_point_money*$point_config['consumed_points']
															);
												$ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
												if($ary_info['status'] == 1){
													 $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_log['reward_point'];
													 $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
													 if(!$res_member){
														 $orders->rollback();
														 $this->error('退货返回冻结积分失败');
														 exit;
													 }
												}else{
													 $orders->rollback();
													 $this->error('退货返回冻结积分写日志失败');
													 exit;
												}
											}else{
												 $orders->rollback();
												 $this->error('退货返回冻结积分没有找到要返回的用户冻结金额');
												 exit;
											}
										}
                                    }
									if($oi_bonus_money>0){
										$bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
										if($bonus_status['BONUS_AUTO_OPEN'] == 1){
											//还原,支出冻结红包金额
											if(!empty($oi_bonus_money) && $oi_bonus_money>0){
												$arr_bonus = array(
													'bt_id' => '4',
													'm_id'  => $ary_orders['m_id'],
													'bn_create_time'  => date("Y-m-d H:i:s"),
													'bn_type' => '0',
													'bn_money' => $oi_bonus_money,
													'bn_desc' => '退货申请成功返还红包金额：'.$oi_bonus_money.'元',
													'o_id' => $ary_orders['o_id'],
													'bn_finance_verify' => '1',
													'bn_service_verify' => '1',
													'bn_verify_status' => '1',
													'single_type' => '2'
												);
												$res_bonus = D('BonusInfo')->addBonus($arr_bonus);
												if($res_bonus === true){
													$ary_orders_log = array(
															'o_id' => $ary_res['o_id'],
															'ol_behavior' => '退货返还红包成功,',
															'ol_text' => serialize($ary_bonus)
													);
													D('OrdersLog')->addOrderLog($ary_orders_log);
												}else{
													$ary_orders_log = array(
															'o_id' => $ary_res['o_id'],
															'ol_behavior' => '退货返还红包失败,红包金额为:'.$oi_bonus_money,
															'ol_text' => serialize($ary_bonus)
													);
													 D('OrdersLog')->addOrderLog($ary_orders_log);
												}
											}
										}									
									}
									
                                    $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                    if($card_status['CARDS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结储值卡金额
                                        
                                        if(!empty($oi_cards_money) && $oi_cards_money>0){
                                            $arr_cards = array(
                                                'ct_id' => '2',
                                                'm_id'  => $ary_orders['m_id'],
                                                'ci_create_time'  => date("Y-m-d H:i:s"),
                                                'ci_type' => '0',
                                                'ci_money' => $oi_cards_money,
                                                'ci_desc' => '退货申请成功返还储值卡金额：'.$oi_cards_money.'元',
                                                'o_id' => $ary_orders['o_id'],
                                                'ci_finance_verify' => '1',
                                                'ci_service_verify' => '1',
                                                'ci_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_cards = D('CardsInfo')->addCards($arr_cards);
                                            if($res_cards === true){
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还储值卡成功,',
                                                        'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还储值卡失败,储值卡金额为:'.$oi_cards_money,
                                                        'ol_text' => serialize($ary_cards)
                                                );
                                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
									/**
                                    $jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                                    if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                        //还原,支出冻结金币金额	
                                        if(!empty($oi_jlb_money) && ($oi_jlb_money>0) && !empty($jlb_status['jlb_proportion'])){
                                            $arr_jlb = array(
                                                'jt_id' => '2',
                                                'm_id'  => $ary_orders['m_id'],
                                                'ji_create_time'  => date("Y-m-d H:i:s"),
                                                'ji_type' => '0',
                                                'ji_money' => $oi_jlb_money*$jlb_status['jlb_proportion'],
                                                'ji_desc' => '退货申请成功返还金币金额：'.$oi_jlb_money.'元',
                                                'o_id' => $ary_orders['o_id'],
                                                'ji_finance_verify' => '1',
                                                'ji_service_verify' => '1',
                                                'ji_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                            if($res_jlb === true){
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还金币成功,',
                                                        'ol_text' => serialize($ary_jlb)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还金币失败,金币金额为:'.$ary_jlb['ji_money'],
                                                        'ol_text' => serialize($ary_jlb)
                                                );
                                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }**/								
								}
								
                                $ary_orders_log = array(
                                		'o_id' => $ary_res['o_id'],
                                		'ol_behavior' => '订单退货成功',
                                		'ol_text' => serialize($ary_post)
                                );
								//检测是否需要退优惠券
								
                                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                                if (!$res_orders_log) {
                                	$orders->rollback();
                                	$this->error('更新失败');
                                	exit;
                                }
                            }
                            //如果单据为财审，更改订单状态
                            $ary_orders_log = array(
                            		'o_id' => $ary_res['o_id'],
                            		'ol_behavior' => '财审成功',
                            		'ol_text' => serialize($arr_data)
                            );
                            $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                            $orders->commit();
                            $this->success($ary_rest['msg']);
                        } else {
                                  
                            $orders->rollback();
                            $this->error($ary_rest['msg']);
                        }
                    
//                    echo "<pre>";print_r($ary_balance);exit;
				 }
                $ol_behavior = '';
                if($ary_post['field'] == 'or_finance_verify'){
                	$ol_behavior = '财审成功';
                }else{
                	$ol_behavior = '客审成功';
                }
                //更新日志表
                $ary_orders_log = array(
                		'o_id' => $ary_res['o_id'],
                		'ol_behavior' => $ol_behavior,
                		'ol_text' => serialize($ary_post)
                );
                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                if (!$res_orders_log) {
                	$orders->rollback();
                	$this->error('更新失败');
                	exit;
                }
                
                $orders->commit();
                $this->success("操作成功");
            } else {
                $orders->rollback();
                $this->error("操作失败");
            }
        } else {
            $orders->rollback();
            $this->error("参数错误,请重试...");
        }
    }

    /**
     * 退款单详情
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-23
     */
    public function setOrderRefund() {
        $int_or_id = intval($this->_post('or_id'));
        $int_o_id = intval($this->_post('o_id'));
        if (isset($int_or_id) && !empty($int_o_id)) {
        	//查询退款单详情
            $ary_data = D('Gyfx')->selectOne('orders_refunds',null, array("or_id" => $int_or_id)); 
            //获得自定义属性
            $ary_gs_ids = D("RelatedRefundSpec")->getRelatedRefundSpec($int_or_id);
            if ($ary_gs_ids) {
                $this->assign('ary_gs_data', $ary_gs_ids);
            }
        }
        $this->assign('ary_data', $ary_data);
        $this->display('setOrderRefund');
    }
    
    /**
     * 请选择退款方式
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-23
     */
    public function checkOrderRefundType() {
        $int_or_id = intval($this->_post('id'));
        if (isset($int_or_id) && !empty($int_or_id)) {
            $ary_data = D('Gyfx')->selectOne('orders_refunds',null, array("or_id" => $int_or_id));
			//查询订单支付方式
            $ary_data['or_refunds_type'] = 1;
        }
		$ary_oi_ids = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->field('oi_id')->select();
		$oi_ids = array();
		foreach($ary_oi_ids as $order_info){
			$oi_ids[] = $order_info['oi_id'];
		}
		$ary_where = array();
		$ary_where['oi_id'] = array('in',implode(',',$oi_ids));
		$order_data = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')
		->field('sum(oi_coupon_menoy) as coupon_money,sum(oi_bonus_money) as bonus_money,sum(oi_cards_money) as cards_money,sum(oi_jlb_money) as jlb_money,sum(oi_point_money) as point_money')
		->where($ary_where)->find();
		$this->assign('order_data', $order_data);
        $this->assign('ary_data', $ary_data);
        $this->display();
    }

    /**
     * 退货单详情
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-23
     */
    public function setOrderReturn() {
        $int_or_id = $this->_post('or_id');
        $o_id = $this->_post('o_id');
        $ary_order = D('Orders')->where(array('o_id'=>$o_id))->find();
        if (isset($int_or_id) && !empty($int_or_id)) {
            $ary_data = D('Gyfx')->selectOne('orders_refunds',null, array("or_id" => $int_or_id));
            //获得自定义属性
            $ary_gs_ids = D("RelatedRefundSpec")->getRelatedRefundSpec($int_or_id);
            $delivery = D('OrdersDelivery')->where(array('o_id'=>$o_id))->count();
            $ary_data['is_delivery'] = 1;
            if(empty($delivery)){
                $ary_data['is_delivery'] = 0;
            }
            if($ary_order['o_status'] == '5'){
                $ary_data['is_received'] = 1;
            }else{
                $ary_data['is_received'] = 0;
            }
            if ($ary_gs_ids) {
                $this->assign('ary_gs_data', $ary_gs_ids);
            }
        }
		if(!empty($ary_data['or_picture'])){
			$ary_data['or_picture'] = D('QnPic')->picToQn($ary_data['or_picture']);
		}
        $this->assign('ary_data', $ary_data);
        $this->display('setOrderReturn');
    }

    
	/**
	 * 海信项目特定加入此开关
	 * @author Terry<wanghui@guanyisoft.com>
	 * @date 2013-07-30
     * @modify by wanghaoyu
	 */
	public function pageSet(){
		$this->getSubNav(4, 0, 40);
		$ary_order_data = D('SysConfig')->getCfg('ORDERS_TIME','ORDERS_TIME','1','是否启用送货时间');
		$resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR','ORDERS_OPERATOR','0','只记录第一次操作人');
		$resdata1 = D('SysConfig')->getCfg('ORDERS_REMOVE','ORDERS_REMOVE','0','是否开启订单拆分');
		$resdata2 = D('SysConfig')->getCfg('ORDERS_REMOVETYPE','ORDERS_REMOVETYPE','0','订单拆分方式(1:自动拆分;0:手动拆分)');
        $resdata3 = D('SysConfig')->getCfg('IS_SHOW','IS_SHOW','1','是否显示发货选择');
        $resdata4 = D('SysConfig')->getCfg('IS_SHOW_ADDRESS','IS_SHOW_ADDRESS','1','是否显示粘贴收货地址');
        $resdata5 = D('SysConfig')->getCfg('IS_AUTO_AUDIT','IS_AUTO_AUDIT','0','是否显示粘贴收货地址');
		$resdata6 = D('SysConfig')->getCfg('IS_CONFIRM_ORDER','IS_CONFIRM_ORDER','0','是否开启重复下单提示');
        $resdata7 = D('SysConfig')->getCfg('IS_AUTO_CONFIRM_ORDER','IS_AUTO_CONFIRM_ORDER','0','开启订单自动确认收货');
		$resdata8 = D('SysConfig')->getCfg('CONFIRM_ORDER_DAY','CONFIRM_ORDER_DAY','7','设置发货后多少天后自动确认收货');		
		$resdata9 = D('SysConfig')->getCfg('IS_AUTO_CART','IS_AUTO_CART','0','购物车默认选中');
		$resdata10 = D('SysConfig')->getCfg('PAY_SEND_CODE','PAY_SEND_CODE','0','支付发验证码');
		$resdata11 = D('SysConfig')->getCfg('CONSUME_SEND_CODE','CONSUME_SEND_CODE','0','提现发送验证码');
		$resdata12 = D('SysConfig')->getCfg('SHIP_SEND_MESSAGE','SHIP_SEND_MESSAGE','0','发货之后发送站内信');
		$resdata13 = D('SysConfig')->getCfg('IS_MERGE_PAYMENT','IS_MERGE_PAYMENT','0','是否启用合并支付');
        $resdata14 = D('SysConfig')->getCfg('ALLOW_REFUND_APPLY','ALLOW_REFUND_APPLY','0','审核后是否允许申请退款');
        $resdata15 = D('SysConfig')->getCfg('ALLOW_REFUND_DELIVERY','ALLOW_REFUND_DELIVERY','0','审核后退货退款包含运费');
        $resdata16 = D('SysConfig')->getCfg('ALLOW_REFUND_DELIVERY_ALL','ALLOW_REFUND_DELIVERY_ALL','0','开启退运费');
		$foreign_orders=D('SysConfig')->getForeignOrderCfg();
	    $resdata17 = D('SysConfig')->getCfg('GY_GOODS_SET','IS_GOODS_KEYSTORE','0','是否统计商品访问量');
		$resdata18 = D('SysConfig')->getCfg('IS_ZT','IS_ZT','0','是否启用门店提货');
		$resdata19 = D('SysConfig')->getCfg('IS_AUTO_FINISH_ORDER','IS_AUTO_FINISH_ORDER','0','开启订单自动确认收货');
		$resdata20 = D('SysConfig')->getCfg('FINISH_ORDER_DAY','FINISH_ORDER_DAY','7','设置发货后多少天后自动确认收货');
		$resdata21 = D('SysConfig')->getCfg('IS_ORDER_ON','IS_ORDER_ON','0','第三方订单下载自动转化');		
		$this->assign($ary_order_data);
		$this->assign($resdata);
		$this->assign($resdata1);
		$this->assign($resdata2);
        $this->assign($resdata3);
        $this->assign($resdata4);
        $this->assign($resdata5);
		$this->assign($resdata6);
		$this->assign($resdata7);
		$this->assign($resdata8);
		$this->assign($resdata9);
		$this->assign($resdata10);
		$this->assign($resdata11);
		$this->assign($resdata12);
		$this->assign($resdata13);
        $this->assign($resdata14);
        $this->assign($resdata15);
        $this->assign($resdata16);
		$this->assign($resdata17);
		$this->assign($resdata18);
		$this->assign($foreign_orders);
		$this->assign($resdata19);
		$this->assign($resdata20);
		$this->assign($resdata21);
		$this->display();
	}

    /**
     *处理订单设置
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-30
     * @modify by wanghaoyu //是否显示选择
     */
    public function doSet(){
        $ary_post = $this->_post();
        //echo "<pre>";print_r($ary_post);die();
        $SysSeting = D('SysConfig');
        if($SysSeting->setConfig('ORDERS_TIME', 'ORDERS_TIME', $ary_post['ORDERS_TIME'], '是否启用送货时间')){
            //操作人配置
            if($SysSeting->setConfig('ORDERS_OPERATOR', 'ORDERS_OPERATOR', $ary_post['ORDERS_OPERATOR'], '是否只记录第一次操作人')){
                //是否开启订单拆分
                if($SysSeting->setConfig('ORDERS_REMOVE', 'ORDERS_REMOVE', $ary_post['ORDERS_REMOVE'], '是否开启订单拆分')){
                    //订单拆分方式
                    if($SysSeting->setConfig('ORDERS_REMOVETYPE', 'ORDERS_REMOVETYPE', $ary_post['ORDERS_REMOVETYPE'], '订单拆分方式(1:自动拆分;0:手动拆分)')){
                        $this->success('保存成功');
                    }else{
                        $this->error('保存失败');
                    }			
                }else{
                    $this->error('保存失败');
                }
            }else{
                $this->error('保存失败');
            }
        }else{
            $this->error('保存失败');
        }
        //是否显示发货选择
        if($SysSeting->setConfig('IS_SHOW', 'IS_SHOW', $ary_post['IS_SHOW'], '是否显示发货选择')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        //是否显示粘贴收货地址
        if($SysSeting->setConfig('IS_SHOW_ADDRESS', 'IS_SHOW_ADDRESS', $ary_post['IS_SHOW_ADDRESS'], '是否显示粘贴收货地址')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        //是否开启自动审核订单
        if($SysSeting->setConfig('IS_AUTO_AUDIT', 'IS_AUTO_AUDIT', $ary_post['IS_AUTO_AUDIT'], '是否开启自动审核订单')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//是否重复下单提示
		if($SysSeting->setConfig('IS_CONFIRM_ORDER', 'IS_CONFIRM_ORDER', $ary_post['IS_CONFIRM_ORDER'], '是否开启重复下单提示,三天内，存在同一收货人名字，再次使用这个收货人名称下单会提示 :三天内有收货人重名订单，确认提交订单')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//开启订单自动确认收货
		if($SysSeting->setConfig('IS_AUTO_CONFIRM_ORDER', 'IS_AUTO_CONFIRM_ORDER', $ary_post['IS_AUTO_CONFIRM_ORDER'], '是否开启开启订单自动确认收货')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//设置发货后多少天后自动确认收货
		if($SysSeting->setConfig('CONFIRM_ORDER_DAY', 'CONFIRM_ORDER_DAY', intval($ary_post['CONFIRM_ORDER_DAY']), '设置发货后多少天后自动确认收货')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//开启订单自动完结
		if($SysSeting->setConfig('IS_AUTO_FINISH_ORDER', 'IS_AUTO_FINISH_ORDER', $ary_post['IS_AUTO_FINISH_ORDER'], '是否开启订单自动完结')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//设置发货后多少天后自动完结
		if($SysSeting->setConfig('FINISH_ORDER_DAY', 'FINISH_ORDER_DAY', intval($ary_post['FINISH_ORDER_DAY']), '设置收货后多少天后自动完结')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		//购物车默认选中
		if($SysSeting->setConfig('IS_AUTO_CART', 'IS_AUTO_CART', intval($ary_post['IS_AUTO_CART']), '购物车默认选中')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }	
		//支付发验证码
		if($SysSeting->setConfig('PAY_SEND_CODE', 'PAY_SEND_CODE', intval($ary_post['PAY_SEND_CODE']), '支付发验证码')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }	
		//提现发送验证码
		if($SysSeting->setConfig('CONSUME_SEND_CODE', 'CONSUME_SEND_CODE', intval($ary_post['CONSUME_SEND_CODE']), '提现发送验证码')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
        // 审核后是否允许申请退款
        if(false === $SysSeting->setConfig('ALLOW_REFUND_APPLY', 'ALLOW_REFUND_APPLY', intval($ary_post['ALLOW_REFUND_APPLY']), '审核后是否允许申请退款')){
            $this->error('审核后是否允许申请退款保存失败');
        }

        // 审核后退货退款中包含运费
        if(false === $SysSeting->setConfig('ALLOW_REFUND_DELIVERY', 'ALLOW_REFUND_DELIVERY', intval($ary_post['ALLOW_REFUND_DELIVERY']), '审核后退货退款中包含运费')){
            $this->error('审核后退货退款中包含运费保存失败');
        }

        // 审核后开启退运费
        if(false === $SysSeting->setConfig('ALLOW_REFUND_DELIVERY_ALL', 'ALLOW_REFUND_DELIVERY_ALL', intval($ary_post['ALLOW_REFUND_DELIVERY_ALL']), '审核后开启运费')){
            $this->error('审核开启退运费保存失败');
        }

		//发货之后发送站内信
		if(false === $SysSeting->setConfig('SHIP_SEND_MESSAGE','SHIP_SEND_MESSAGE',intval($ary_post['SHIP_SEND_MESSAGE']),'发货之后发送站内性给客户')) {
			$this->error('是否开启发货之后发送站内信给客户');
		}
		//是否启用合并支付
		
		if(false === $SysSeting->setConfig('IS_MERGE_PAYMENT','IS_MERGE_PAYMENT',intval($ary_post['IS_MERGE_PAYMENT']),'是否启用合并支付')) {
			$this->error('是否启用合并支付保存失败');
		}

		//开启订单限额控制
		if($SysSeting->setConfig('GY_FOREIGN_ORDER', 'IS_AUTO_LIMIT_ORDER_AMOUNT', intval($ary_post['IS_AUTO_LIMIT_ORDER_AMOUNT']), '开启订单限额控制')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		
		//设置发货订单1000元控制提示（单件超过1000元商品除外）
		if($SysSeting->setConfig('GY_FOREIGN_ORDER', 'LIMIT_ORDER_AMOUNT', intval($ary_post['LIMIT_ORDER_AMOUNT']), '订单1000元控制提示（单件超过1000元商品除外）')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		
		//开启订单税额起征点
		if($SysSeting->setConfig('GY_FOREIGN_ORDER', 'IS_AUTO_TAX_THRESHOLD', intval($ary_post['IS_AUTO_TAX_THRESHOLD']), '开启订单税额起征点')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		
		//设置税额起征点
		if($SysSeting->setConfig('GY_FOREIGN_ORDER', 'TAX_THRESHOLD', intval($ary_post['TAX_THRESHOLD']), '订单中含税商品的税额之和是否小于等于50不收税')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
		
		//是否统计商品访问量
		if($SysSeting->setConfig('GY_GOODS_SET', 'IS_GOODS_KEYSTORE', intval($ary_post['IS_GOODS_KEYSTORE']), '是否统计商品访问量')){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }

		//是否启用门店提货
		if($SysSeting->setConfig('IS_ZT', 'IS_ZT', intval($ary_post['IS_ZT']), '是否启用门店提货')){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"订单设置保存成功",'订单设置保存成功'));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }	
		//是否启用第三方订单下载自动转化
		if($SysSeting->setConfig('IS_ORDER_ON', 'IS_ORDER_ON', intval($ary_post['IS_ORDER_ON']), '第三方订单下载自动转化')){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"第三方订单下载自动转化设置保存成功",'第三方订单下载自动转化设置保存成功'));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }	
    }

    /**
     * 发货设置ajax打开页面
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2013-07-30
     */
    public function setSendShip() {
        $ary_data = M("logistic_corp", C('DB_PREFIX'), 'DB_CUSTOM')->field(array('lc_id', 'lc_name'))
		->where(array('lc_is_enable'=>'1'))->select();
		$this->assign('ary_data', $ary_data);
        $this->display('setSendShip');
    }
	
    /**
     * 添加支付申请
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-11
     */
    public function payOrder() {
        $ary_data = $this->_request();
		$veriry_order = $this->verifyOrderStatus($ary_data['o_id']);
		$this->assign('veriry_order', $veriry_order);
		$this->assign('ary_data', $ary_data);
        $this->display('setPayOrder');
    }

    /**
     * 验证订单支付申请状态
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-11
     */
    public function verifyOrderStatus($int_o_id = '') {
		$ary_return  = array('status'=>0,'msg'=>'订单验证状态失败');
		if(!isset($int_o_id) || $int_o_id == ''){
			$ary_return['msg'] = '订单号不存在';
			return $ary_return;
		}
		$ary_order = D("Orders")->where(array('o_id'=>$int_o_id))->field('o_status,o_pay_status,o_id')->find();
		if(empty($ary_order)){
			$ary_return['msg'] = '订单不存在';
			return $ary_return;
		}
		$ary_status = array('o_status' => $v['o_status']);
		$str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
		if($str_status == '作废'){
			$ary_return['msg'] = '订单已作废';
			return $ary_return;
		}
		if($ary_order['o_pay_status'] == '1'){
			$ary_return['msg'] = '订单已支付无需添加申请';
			return $ary_return;
		}else{
			//验证是否已提交申请
			$count_order_pay = D('AdminPay')->where(array('order_id'=>$int_o_id,'ap_status'=>0))->count();
			if($count_order_pay>0){
				$ary_return['msg'] = '您已添加申请无需再次添加申请';
				return $ary_return;
			}else{
				$ary_return['status'] = 1;
				return $ary_return;
			}			
		}
		return $ary_return;
    }
	
    /**
     * 更改订单状态(发货状态)
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2013-07-30
     */
    public function UpdateOrderStatus() {
        $ary_post = $this->_post();
        $ary_orders_data = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->find();
        $ary_order_data['o_id'] = $ary_post['o_id'];
        $ary_order_data['od_created'] = date("Y-m-d H:i:s");
        $ary_order_data['m_id'] = $ary_orders_data['m_id'];
        $ary_order_data['od_money'] = $ary_orders_data['o_cost_freight'];
        if (!empty($ary_post['logistics_id'])) {
            $ary_order_data['od_logi_id'] = $ary_post['logistics_id'];
        }else{
            //$ary_logi = M("logistic_type", C('DB_PREFIX'), 'DB_CUSTOM')->field('lc_id')->where(array('lt_id'=>$ary_orders_data['lt_id']))->find();
            $ary_logi = explode("_",$ary_post['logistics_name']);
//            echo "<pre>";print_r($ary_logi);exit;
            $ary_order_data['od_logi_id'] = $ary_logi[0];
        }
        //$ary_order_data['od_logi_id'] = $ary_post['logistics_id'];
        $ary_order_data['od_logi_name'] = $ary_logi[1];
        if (!empty($ary_post['logistics_no'])) {
            $ary_order_data['od_logi_no'] = $ary_post['logistics_no'];
        }else{
			$ary_logistic_where = array('lt_id' => $ary_orders_data ['lt_id']);
			$ary_field = array('lc_abbreviation_name');
			$ary_logistc = D('Logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
			$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
			if($is_zt['IS_ZT']['sc_value'] == 1){
				if( $ary_logistc[0]['lc_abbreviation_name'] != 'ZT'){
					$this->error('物流单号不能为空！');
					exit;
				}
			}else{
				$this->error('物流单号不能为空！');
				exit;
			}
		}
		//判断是否重复发货
		$del_count = M("orders_delivery", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_order_data['o_id']))->count();
		if($del_count>0){
			$this->error('此订单已发货无需再次发货');
			exit;
		}
//        echo "<pre>";print_r($ary_orders_data);exit;
        $ary_order_data['u_name'] = $_SESSION['admin_name'];
        $ary_order_data['u_id'] = $_SESSION['Admin'];
        $ary_order_data['od_memo'] = $ary_post['memo'] . "系统虚拟发货";
        $ary_order_data['od_receiver_name'] = $ary_orders_data['o_receiver_name'];
        $ary_order_data['od_receiver_mobile'] = $ary_orders_data['o_receiver_mobile'];
        $ary_order_data['od_receiver_telphone'] = $ary_orders_data['o_receiver_telphone'];
        $ary_order_data['od_receiver_address'] = $ary_orders_data['o_receiver_address'];
        $ary_order_data['od_receiver_zipcode'] = $ary_orders_data['o_receiver_zipcode'];
        $ary_order_data['od_receiver_email'] = $ary_orders_data['o_receiver_email'];
        $ary_order_data['od_receiver_city'] = $ary_orders_data['o_receiver_city'];
        $ary_order_data['od_receiver_province'] = $ary_orders_data['o_receiver_state'];

        M('', '', 'DB_CUSTOM')->startTrans();
        $result_id = M("orders_delivery", C('DB_PREFIX'), 'DB_CUSTOM')->data($ary_order_data)->add();
//        echo "<pre>";print_r(M("orders_delivery", C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());exit;
        if(!$result_id){
            $this->orderLog($ary_post['o_id'],"发货失败");
            M('', '', 'DB_CUSTOM')->rollback();
            $this->error('订单发货失败，请重试...');
            exit;
        }
        $ary_orders_item_data = M("orders_items", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->select();
        foreach ($ary_orders_item_data as $value) {
            $item_data['od_id'] = $result_id;
            $item_data['o_id'] = $ary_post['o_id'];
            $item_data['oi_id'] = $value['oi_id'];
            $item_data['odi_num'] = $value['oi_nums'];
            $item_result_id = M("orders_delivery_items", C('DB_PREFIX'), 'DB_CUSTOM')->data($item_data)->add();

            if (!$item_result_id) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->orderLog($ary_post['o_id'], "发货失败");
                $this->error('订单发货明细失败，请重试...');
                exit;
            }
            //把订单明细标记为已发货
            if (false === D("OrdersItems")->where(array('oi_id' => $value['oi_id']))->save(array('oi_sendnum' => $value['oi_nums'], 'oi_ship_status' => 2))) {
                D("OrdersItems")->rollBack();
                $this->orderLog($ary_post['o_id'], "发货失败");
                $this->error('标记订单发货失败，请重试...');
                exit;
            }
        }

        //因海信双接口不需要，发货时不需要同步到TB
        if($ary_orders_data['o_payment'] == '2'){
            $Payment = D('PaymentCfg');
            $info = $Payment->where(array('pc_id' => $ary_orders_data['o_payment']))->find();
            if (false == $info) {
                D("OrdersItems")->rollBack();
                //支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                $this->error('支付方式不存在，或不可用');
                exit;
            } else {
                $Pay = $Payment::factory($info['pc_abbreviation'], json_decode($info['pc_config'], true));
                $ary_datas = $this->matchLogisticsCompanieCode($ary_post['logistics_name']);
                $gwt_sn = M("payment_serial", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id'], "ps_type" => '0'))->find();
                $ary_orders_item_data = M("orders_items", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->find();
                if($gwt_sn['ps_status'] == 4 && ($ary_orders_item_data['oi_refund_status'] == 2 || $ary_orders_item_data['oi_refund_status'] == 3) ){
					$this->error('此订单状态已为退换货状态或作废状态无法发货');
					exit;					
				}
				if($gwt_sn['ps_status'] == 2){
					$ary_params = array(
                    'WIDtrade_no' => $gwt_sn['ps_gateway_sn'],
                    'WIDlogistics_name' => $ary_datas['name'],
                    'WIDinvoice_no' => $ary_post['logistics_no'],
                    'WIDtransport_type' => 'POST'
					);
					$arr_result = $Pay->ship($ary_params);
					$arr_res = xml2array($arr_result);
					if ($arr_res['is_success'] == 'F') {
						D("OrdersItems")->rollBack();
						//支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
						$this->error('发货失败，请重试');
						exit;
					}			
				}
            }
        }
        
        
        /*** 处理订单积分****start****By Joe******/
        $array_point_config = D('PointConfig')->getConfigs();
        if($array_point_config['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '0'){
            //发货后处理赠送积分
            if($ary_orders_data['o_reward_point']>0){
                $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_orders_data['o_reward_point'],$ary_orders_data['m_id'],$ary_orders_data['o_id']);
                if(!$ary_reward_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($ary_reward_result['message']);
                    exit;
                }
            }
            
            //发货后处理消费积分
            if($ary_orders_data['o_freeze_point'] > 0){
                $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_orders_data['o_freeze_point'],$ary_orders_data['m_id']);
                if(!$ary_freeze_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($ary_freeze_result['message']);
                    exit;
                }
            }
        }
        /*** 处理订单积分****end**********/
        
        /*** 处理赠送金币****start**********/
		/**
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] == '1' && $ary_jlb_data['cinsumed_channel'] == '0'){
            //发货后处理赠送金币
            if($ary_orders_data['o_reward_jlb']>0){
                $arr_jlb = array(
                    'jt_id' => '2',
                    'm_id'  => $ary_orders_data['m_id'],
                    'ji_create_time'  => date("Y-m-d H:i:s"),
                    'ji_type' => '0',
                    'ji_money' => $ary_orders_data['o_reward_jlb'],
                    'ji_desc' => '订单发货赠送金币：'.$ary_orders_data['o_reward_jlb'],
                    'o_id' => $ary_orders_data['o_id'],
                    'ji_finance_verify' => '1',
                    'ji_service_verify' => '1',
                    'ji_verify_status' => '1',
                    'single_type' => '2'
                    );
                $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                if(!$res_jlb){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error("生成发货赠送金币调整单错误！");
                    exit;
                }
            }
        }
		**/
        /*** 处理赠送金币****end**********/
        
        /*** 订单发货后获取订单优惠券**star by Joe**/
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        $where = array ('fx_orders.o_id' => $ary_post['o_id']);
        $ary_field = array('fx_orders.o_pay','fx_orders.o_id','fx_orders.o_all_price','fx_orders.coupon_sn','fx_orders_items.pdt_id','fx_orders_items.oi_nums','fx_orders_items.oi_type');
        $ary_orders = D('Orders')->getOrdersData($where,$ary_field);
        // 本次消费金额=支付单最后一次消费记录
        $payment_serial = M('payment_serial')->where(array('o_id'=>$ary_post['o_id']))->order('ps_create_time desc')->select();
        $payment_price = $payment_serial[0]['ps_money'];
        $all_price = $ary_orders[0]['o_all_price'];
        $coupon_sn = $ary_orders[0]['coupon_sn'];
        //print_r($ary_orders);exit;
        if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '1') {
            D('Coupon')->setPoinGetCoupon($ary_orders,$ary_orders_data['m_id']);
        }
        /*** 订单发货后获取订单优惠券****end**********/
        
        
        $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR','ORDERS_OPERATOR','1','只记录第一次操作人');
        //查询订单是否存在操作者ID
        $admin_id = $_SESSION['Admin'];
        if($resdata['ORDERS_OPERATOR']['sc_value'] == '1'){
        	$order_admin_id =  M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_post['o_id']))->getField('admin_id');
        	if(empty($order_admin_id)){
        		$ary_order_up['admin_id'] = $admin_id;
        	}
        }else{
        	$ary_order_up['admin_id'] = $admin_id;
        }
        $bl_o_res = D('Orders')->updateOrderInfo($ary_post['o_id'], $ary_order_up);
        $this->orderLog($ary_post['o_id'],"发货成功");
		//发货成功是否发站内性
		$is_ship = D('SysConfig')->getCfg('SHIP_SEND_MESSAGE','SHIP_SEND_MESSAGE','0','发货之后发送站内信');
		if($is_ship['SHIP_SEND_MESSAGE']['sc_value'] == '1'){
			$sl_title = '订单'.$ary_post['o_id'].'发货成功';
			$sl_content = '订单'.$ary_post['o_id'].'已发货,请及时收货，<a href="/Ucenter/Orders/pageShow/oid/'.$ary_post['o_id'].'">查看订单</a>';
			$m_id = $ary_order_data['m_id'];
			$sl_res = D('StationLetters')->addStationLotters($sl_title,$sl_content,$m_id);
			if(!$sl_res){
				M('', '', 'DB_CUSTOM')->rollback();
				$this->error('站内信发送失败');
				exit;
			}
		}
		//更新会员等级
        D('MembersLevel')->autoUpgrade($ary_order_data['m_id']);
        M('', '', 'DB_CUSTOM')->commit();
        $this->success("发货");
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
     * 导出订单数据
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function explortOrdersInfo() {
	    @set_time_limit(0);  
        @ignore_user_abort(TRUE); 
		ini_set("memory_limit","500M");
        $ary_post = $this->_post();
        $ary_order_datas = array();
        if (!empty($ary_post['end']) && $ary_post['end'] > 50) {
            $ary_post['end'] = 50;
        }
        //导出选中
        if(!empty($ary_post['orders_type']) && $ary_post['orders_type'] == '1'){
            $ary_order_datas['o_ids'] = $ary_post['o_ids'];
            $ary_order_datas['start'] = "1";
            $ary_order_datas['end'] = "50";
        }
        //导出全部
        else if(!empty($ary_post['orders_type']) && $ary_post['orders_type'] == '2'){
            $ary_order_datas['start'] = "1";
            $ary_order_datas['end'] = "50";
        }
        //按订单生成时间导出
        else if(!empty($ary_post['orders_type']) && $ary_post['orders_type'] == '3'){
            if(empty($ary_post['o_create_time_start']) && empty($ary_post['o_create_time_end'])){
                $this->ajaxReturn(array('status'=>'0','info'=>'下单时间不能为空'));exit;
            }
            if(!empty($ary_post['o_create_time_start']) && !empty($ary_post['o_create_time_end'])){
                $ary_order_datas['o_create_time_start'] = $ary_post['o_create_time_start'];
                $ary_order_datas['o_create_time_end'] = $ary_post['o_create_time_end'];
            }else if(!empty($ary_post['o_create_time_start']) && empty($ary_post['o_create_time_end'])){
                $ary_order_datas['o_create_time_start'] = $ary_post['o_create_time_start'];
            }else{
                $ary_order_datas['o_create_time_end'] = $ary_post['o_create_time_end'];
            }
            $ary_order_datas['start'] = "1";
            $ary_order_datas['end'] = "50";
        }
        //导出当前结果
        else if(!empty($ary_post['orders_type']) && $ary_post['orders_type'] == '4'){
            if(empty($ary_post['search'])){
                $this->ajaxReturn(array('status'=>'0','info'=>'请先搜索后再导出订单'));exit;
            }
            $array_search_where = json_decode($ary_post['search'],true);
            //如果需要根据订单号进行搜索
            if (!empty($array_search_where['o_id']) && isset($array_search_where['o_id'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_id'] = $array_search_where['o_id'];
            }
            //如果需要根据会员名称进行搜索
            if (!empty($array_search_where['m_name']) && isset($array_search_where['m_name'])) {
                $ary_where[C("DB_PREFIX") . 'members.m_name'] = $array_search_where['m_name'];
            }
            //如果需要根据收货人进行搜索
            if (!empty($array_search_where['o_receiver_name']) && isset($array_search_where['o_receiver_name'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_receiver_name'] = $array_search_where['o_receiver_name'];
            }
            //判断订单状态
            if (isset($array_search_where['o_status'])) {
                //如果需要根据订单状态进行搜索
                if (!empty($array_search_where['o_status'])) {
                    $ary_o_status_where = array();
                    //去除全部选项
                    foreach ($array_search_where['o_status'] as $os_v) {
                        if ($os_v !== 0) {
                            $ary_o_status_where[] = (int) $os_v;
                        }
                    }
                    if (!empty($ary_o_status_where)) {
                        $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('in', $ary_o_status_where);
                    }
                }
            } else {
                //如果提交的参数中没有search,则代表是点击菜单进入订单列表，默认不显示作废订单，如果是搜索的结果，则显示作废订单
                if (!isset($array_search_where['search'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_status'] = array('neq', 2);
                }
            }
            //如果需要根据支付状态进行搜索
            if (!empty($array_search_where['o_pay_status']) && isset($array_search_where['o_pay_status']) && $array_search_where['o_pay_status'] != '-1') {
                $ary_where[C("DB_PREFIX") . 'orders.o_pay_status'] = $array_search_where['o_pay_status'];
            }

            //如果需要根据配送方式进行搜索
            if (!empty($array_search_where['lt_id']) && isset($array_search_where['lt_id']) && $array_search_where['lt_id'] != '-1') {
                $ary_where[C("DB_PREFIX") . 'logistic_corp.lc_id'] = $array_search_where['lt_id'];
            }

            //如果需要根据支付方式进行搜索
            if (isset($array_search_where['o_payment']) && !empty($array_search_where['o_payment'])) {
                $ary_payment_where = array();
                //去除全部选项
                foreach ($array_search_where['o_payment'] as $op_v) {
                    if ($op_v !== '-1') {
                        $ary_payment_where[] = (int) $op_v;
                    }
                }
                if (!empty($ary_payment_where)) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_payment'] = array('in', $ary_payment_where);
                }
            }
            //如果需要根据支付方式进行搜索
            if (isset($array_search_where['oi_ship_status']) && $array_search_where['oi_ship_status'] != '-1') {
                $ary_where[C("DB_PREFIX") . 'orders_items.oi_ship_status'] = $array_search_where['oi_ship_status'];
            }

            if (!empty($array_search_where['province']) && isset($array_search_where['province']) && $array_search_where['province'] != '0') {
                $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $array_search_where['province']))->find();
                $ary_where[C("DB_PREFIX") . 'orders.o_receiver_state'] = $province['cr_name'];
            }
            if (!empty($array_search_where['city']) && isset($array_search_where['city']) && $array_search_where['city'] != '0') {
                $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $array_search_where['city']))->find();
                $ary_where[C("DB_PREFIX") . 'orders.o_receiver_city'] = $province['cr_name'];
            }
            if (!empty($array_search_where['region1']) && isset($array_search_where['region1']) && $array_search_where['region1'] != '0') {
                $province = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM')->field("cr_name")->where(array('cr_id' => $array_search_where['region1']))->find();
                $ary_where[C("DB_PREFIX") . 'orders.o_receiver_county'] = $province['cr_name'];
            }

            //如果需要根据收货人手机进行搜索
            if (!empty($array_search_where['o_receiver_mobile']) && isset($array_search_where['o_receiver_mobile'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_receiver_mobile'] = $array_search_where['o_receiver_mobile'];
            }
            //如果需要根据物流费用进行搜索
            if (!empty($array_search_where['o_cost_freight_1']) && !empty($array_search_where['o_cost_freight_2'])) {
                if ($array_search_where['o_cost_freight_1'] > $array_search_where['o_cost_freight_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($array_search_where['o_cost_freight_2'], $array_search_where['o_cost_freight_1']));
                } else if ($array_search_where['o_cost_freight_1'] < $array_search_where['o_cost_freight_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("BETWEEN", array($array_search_where['o_cost_freight_1'], $array_search_where['o_cost_freight_2']));
                } else {
                    $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = $array_search_where['o_cost_freight_1'];
                }
            } else {
                if (!empty($array_search_where['o_cost_freight_1']) && empty($array_search_where['o_cost_freight_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("EGT", $array_search_where['o_cost_freight_1']);
                } else if (empty($array_search_where['o_cost_freight_1']) && !empty($array_search_where['o_cost_freight_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_cost_freight'] = array("ELT", $array_search_where['o_cost_freight_2']);
                }
            }
            //如果需要根据订单金额进行搜索
            if (!empty($array_search_where['o_all_price_1']) && !empty($array_search_where['o_all_price_2'])) {
                if ($array_search_where['o_all_price_1'] > $array_search_where['o_all_price_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($array_search_where['o_all_price_2'], $array_search_where['o_all_price_1']));
                } else if ($array_search_where['o_all_price_1'] < $array_search_where['o_all_price_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("BETWEEN", array($array_search_where['o_all_price_1'], $array_search_where['o_all_price_2']));
                } else {
                    $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = $array_search_where['o_all_price_1'];
                }
            } else {
                if (!empty($array_search_where['o_all_price_1']) && empty($array_search_where['o_all_price_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("EGT", $array_search_where['o_all_price_1']);
                } else if (empty($array_search_where['o_all_price_1']) && !empty($array_search_where['o_all_price_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_all_price'] = array("ELT", $array_search_where['o_all_price_2']);
                }
            }
            //如果需要根据使用优惠券进行搜索
            if (!empty($array_search_where['o_coupon_1']) && empty($array_search_where['o_coupon_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $array_search_where['o_all_price_1'];
            } else if (empty($array_search_where['o_coupon_1']) && !empty($array_search_where['o_coupon_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $array_search_where['o_coupon_2'];
            }

            //如果需要根据使用开发票搜索
            if (!empty($array_search_where['is_invoice_1']) && empty($array_search_where['is_invoice_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $array_search_where['o_all_price_1'];
                if (!empty($array_search_where['invoice_type'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.invoice_type'] = $array_search_where['invoice_type'];
                }
            } else if (empty($array_search_where['is_invoice_1']) && !empty($array_search_where['is_invoice_2'])) {
                $ary_where[C("DB_PREFIX") . 'orders.o_coupon'] = $array_search_where['o_coupon_2'];
            }

            //如果需要根据物流费用进行搜索
            if (!empty($array_search_where['o_create_time_1']) && !empty($array_search_where['o_create_time_2'])) {
                if ($array_search_where['o_create_time_1'] > $array_search_where['o_create_time_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($array_search_where['o_create_time_2'], $array_search_where['o_create_time_1']));
                } else if ($array_search_where['o_create_time_1'] < $array_search_where['o_create_time_2']) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($array_search_where['o_create_time_1'], $array_search_where['o_create_time_2']));
                } else {
                    $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $array_search_where['o_create_time_1'];
                }
            } else {
                if (!empty($array_search_where['o_create_time_1']) && empty($array_search_where['o_create_time_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $array_search_where['o_create_time_1']);
                } else if (empty($array_search_where['o_create_time_1']) && !empty($array_search_where['o_create_time_2'])) {
                    $ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $array_search_where['o_create_time_2']);
                }
            }

            //如果要根据客服名称搜索
            if(!empty($array_search_where['admin_name']) && isset($array_search_where['admin_name'])){
                $admin_id = D('Admin')->where(array('u_name'=>$array_search_where['admin_name']))->getField('u_id');
                if(isset($admin_id) && !empty($admin_id)){
                    $ary_where[C("DB_PREFIX").'orders.admin_id'] = $admin_id;
                }
            }
            //根据订单商品名称搜索
            $ary_get['oi_g_name'] = urldecode ($ary_get['oi_g_name']);
            if(!empty($array_search_where['oi_g_name']) && isset($array_search_where['oi_g_name'])){
                $ary_where[C("DB_PREFIX").'orders_items.oi_g_name'] = array('like',"%".$array_search_where['oi_g_name']."%");
            }
            //根据来源cps订单查询
            $bigou_cps_open  = D('SysConfig')->getConfigValueBySckey('CPS_51BIGOU_OPEN','CPS_SET');
            $fanli_cps_open = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');
            if($bigou_cps_open == '1' || $fanli_cps_open == '1'){
                if(!empty($ary_get['channelid']) && isset($ary_get['channelid'])){
                    $ary_where[C("DB_PREFIX") . 'orders.channel_id'] = $ary_get['channelid'];
                }
            }
        }
        //导出指定页
        else{
            $ary_order_datas['start'] = $ary_post['start'];
            if (!isset($ary_post['end'])) {
                $ary_order_datas['end'] = "50";
            } else {
                $ary_order_datas['end'] = $ary_post['end'];
            }
        }

        if(!empty($ary_post['orders_type']) && $ary_post['orders_type'] == 4){
            //订单数据获取
            $array_order = array(C("DB_PREFIX") . 'orders.o_id' => 'desc');
			$ocount =  M('Orders',C('DB_PREFIX'),'DB_CUSTOM')
					->Distinct(true)
					->field(C("DB_PREFIX")."orders.o_id,fx_admin.u_name as admin_name,".C("DB_PREFIX")."orders.*,".C("DB_PREFIX")."logistic_type.*,".C("DB_PREFIX")."logistic_corp.*,".C("DB_PREFIX")."payment_cfg.*,".C("DB_PREFIX")."members.*")
					->join(" ".C("DB_PREFIX")."logistic_type ON ".C("DB_PREFIX")."logistic_type.lt_id=".C("DB_PREFIX")."orders.lt_id")
					->join(" ".C("DB_PREFIX")."logistic_corp ON ".C("DB_PREFIX")."logistic_type.lc_id=".C("DB_PREFIX")."logistic_corp.lc_id")
					->join(" ".C("DB_PREFIX")."payment_cfg ON ".C("DB_PREFIX")."payment_cfg.pc_id=".C("DB_PREFIX")."orders.o_payment")
					->join(" ".C("DB_PREFIX")."orders_items ON ".C("DB_PREFIX")."orders_items.o_id=".C("DB_PREFIX")."orders.o_id")
					->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."members.m_id=".C("DB_PREFIX")."orders.m_id")
					->join("fx_admin on(fx_admin.u_id = fx_orders.admin_id)")
					->where($ary_where)->order($array_order)->count();
			$total_size = ceil($ocount/500);
			$ary_tmp_data = array();
			if($total_size>=1){
				for($i=1;$i<=$total_size;$i++){
					$page = ($i - 1)*500;
					$pagesize = 500;
					$string_limit = $page . ',' . $pagesize;
					$tmp_ary_data =   M('Orders',C('DB_PREFIX'),'DB_CUSTOM')
					->Distinct(true)
					->field(C("DB_PREFIX")."orders.o_id,fx_admin.u_name as admin_name,".C("DB_PREFIX")."orders.*,".C("DB_PREFIX")."logistic_type.*,".C("DB_PREFIX")."logistic_corp.*,".C("DB_PREFIX")."payment_cfg.*,".C("DB_PREFIX")."members.*")
					->join(" ".C("DB_PREFIX")."logistic_type ON ".C("DB_PREFIX")."logistic_type.lt_id=".C("DB_PREFIX")."orders.lt_id")
					->join(" ".C("DB_PREFIX")."logistic_corp ON ".C("DB_PREFIX")."logistic_type.lc_id=".C("DB_PREFIX")."logistic_corp.lc_id")
					->join(" ".C("DB_PREFIX")."payment_cfg ON ".C("DB_PREFIX")."payment_cfg.pc_id=".C("DB_PREFIX")."orders.o_payment")
					->join(" ".C("DB_PREFIX")."orders_items ON ".C("DB_PREFIX")."orders_items.o_id=".C("DB_PREFIX")."orders.o_id")
					->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."members.m_id=".C("DB_PREFIX")."orders.m_id")
					->join("fx_admin on(fx_admin.u_id = fx_orders.admin_id)")
					->where($ary_where)->order($array_order)->limit($string_limit)->select();
					if(!empty($tmp_ary_data)){
						$ary_tmp_data = array_merge($ary_tmp_data,$tmp_ary_data);
						unset($tmp_ary_data);
					}
				}				
			}
        }
        else{
            $ary_tmp_data = D("Orders")->getOrderInfo($ary_order_datas);
        }
        $ary_data = array();
        $i = 0;
        $array_payment_cfg = M('PaymentCfg',C('DB_PREFIX'),'DB_CUSTOM')->where(1)->getField("pc_id,pc_custom_name");
//        echo'<pre>';var_dump($ary_tmp_data);die;
        foreach($ary_tmp_data as $ad_key=>$ad_val){
            $ary_orders_item = D('OrdersItems')->getOrderItemsInfo($ad_val['o_id'],'g_id,pdt_id,promotion,g_sn,pdt_sn,oi_g_name,pdt_sale_price,oi_price,oi_nums,oi_refund_status,oi_ship_status,oi_type');
            $ary_tmp_data[$ad_key]['m_name'] = D('Members')->where(array('m_id'=>$ad_val['m_id']))->getField('m_name');
            //订单支付状态
            $ary_pay_status = array('o_pay_status' => $ad_val['o_pay_status']);
            $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
            $ary_tmp_data[$ad_key]['str_pay_status'] = $str_pay_status;
            if($ary_tmp_data[$ad_key]['o_receiver_mobile']){
                $ary_tmp_data[$ad_key]['o_receiver_mobile'] = decrypt($ary_tmp_data[$ad_key]['o_receiver_mobile']);
            }
            if($ary_tmp_data[$ad_key]['o_receiver_telphone']){
                $ary_tmp_data[$ad_key]['o_receiver_telphone'] = decrypt($ary_tmp_data[$ad_key]['o_receiver_telphone']);
            }
            if($ary_tmp_data[$ad_key]['invoice_phone']){
                $ary_tmp_data[$ad_key]['invoice_phone'] = decrypt($ary_tmp_data[$ad_key]['invoice_phone']);
            }

            //订单的发货状态
            $ary_orders_status = D("Orders")->getOrdersStatus($ad_val['o_id']);
            $ary_tmp_data[$ad_key]['deliver_status'] = $ary_orders_status['deliver_status'];
            $ary_tmp_data[$ad_key]['pc_name'] = $array_payment_cfg[$ad_val["o_payment"]];
            foreach ($ary_orders_item as $oi_k=>$oi_v){
                $ary_cate = D('RelatedGoodsCategory')->field('gc.gc_id,gc.gc_name,gc.gc_parent_id,gc.gc_is_parent')->join('fx_goods_category as gc on(fx_related_goods_category.gc_id=gc.gc_id)')->where(array('fx_related_goods_category.g_id'=>$oi_v['g_id']))->select();
                $tmp_gc_name = $gc_name = $ary_last_gc_name = '';
                foreach ($ary_cate as $ack=>$acv){
                    if($acv['gc_is_parent'] == '0'){
                        $gc_name .= $acv['gc_name'].'，';
                        $last_tmp_gc_name = D('GoodsCategory')->where(array('gc_id'=>$acv['gc_parent_id'],'gc_status'=>1))->getField('gc_name');
                        if($last_tmp_gc_name != $tmp_gc_name){
                            $ary_last_gc_name.=$last_tmp_gc_name.'，';
                            $tmp_gc_name = $last_tmp_gc_name;
                        }
                    }else{
                        if($tmp_gc_name != $acv['gc_name']){
                            $ary_last_gc_name.=$acv['gc_name'].'，';
                            $tmp_gc_name = $acv['gc_name'];
                        }
                    }
                }
                $json_pmn_config = D('promotion')->where(array('pmn_name'=>trim($oi_v['promotion'])))->getField('pmn_config');
                if(isset($json_pmn_config) && !empty($json_pmn_config)){
                    $array_pmn_config = json_decode($json_pmn_config,true);
                    $ary_orders_item[$oi_k]['oi_price'] -= $array_pmn_config['cfg_discount'];
                }
                $gc_name = trim($gc_name,'，');
                $ary_last_gc_name = trim($ary_last_gc_name,'，');
                $ary_orders_item[$oi_k]['gc_name'] = $gc_name;
                $ary_orders_item[$oi_k]['last_gc_name'] = $ary_last_gc_name;
                $ary_orders_item[$oi_k]['gb_name'] = D('GoodsBrand')->join('fx_goods as g on(g.gb_id=fx_goods_brand.gb_id)')->where(array('g.g_id'=>$oi_v['g_id']))->getField('gb_name');

                unset($gc_name);
                unset($ary_last_gc_name);
                $ary_data[$i] = array_merge($ary_tmp_data[$ad_key],$ary_orders_item[$oi_k]);
                $i++;
            }

        }
       
		$ary_fields_avaliable = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        if (!empty($ary_data) && is_array($ary_data)) {
            $heards = array();$fields = array();
            $export_type = $ary_post['export_type'];
            array_push($heards,'订单编号');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'支付状态');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'发货状态');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'是否预售订单');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'下单时间');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'支付时间');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'发货时间');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'会员');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'客服');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'产品分类');array_push($fields,array_shift($ary_fields_avaliable));
            if($export_type['二级分类']){
                array_push($heards,'二级分类');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['品牌名称']){
                array_push($heards,'品牌名称');array_push($fields,array_shift($ary_fields_avaliable));
            }
            array_push($heards,'产品名称');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'商品价格');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'成交价');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'数量');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'商品小计');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'订单总价');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'配送费用');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'已支付金额');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'支付手续费');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'优惠券使用金额');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'优惠券编号');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'优惠券面额');array_push($fields,array_shift($ary_fields_avaliable));
            if($export_type['赠送积分']){
                array_push($heards,'赠送积分');array_push($fields,array_shift($ary_fields_avaliable));
            }
            array_push($heards,'支付方式');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'收货人');array_push($fields,array_shift($ary_fields_avaliable));
            if($export_type['收货人手机']){
                array_push($heards,'收货人手机');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['收货人电话']){
                array_push($heards,'收货人电话');array_push($fields,array_shift($ary_fields_avaliable));
            }
            array_push($heards,'省份');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'市');array_push($fields,array_shift($ary_fields_avaliable));
            array_push($heards,'县/区');array_push($fields,array_shift($ary_fields_avaliable));
            if($export_type['收货地址']){
                array_push($heards,'收货地址');array_push($fields,array_shift($ary_fields_avaliable));
            }
            array_push($heards,'邮编');array_push($fields,array_shift($ary_fields_avaliable));
            if($export_type['买家留言']){
                array_push($heards,'买家留言');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['卖家备注']){
                array_push($heards,'卖家备注');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['是否开发票']){
                array_push($heards,'是否开发票');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['个人姓名']){
                array_push($heards,'个人姓名');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['发票类型']){
                array_push($heards,'发票类型');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['发票抬头']){
                array_push($heards,'发票抬头');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['发票内容']){
                array_push($heards,'发票内容');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['银行账号']){
                array_push($heards,'银行账号');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['开户银行']){
                array_push($heards,'开户银行');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['注册电话']){
                array_push($heards,'注册电话');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['注册地址']){
                array_push($heards,'注册地址');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['纳税人识别号']){
                array_push($heards,'纳税人识别号');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['商品编码']){
                array_push($heards,'商品编码');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['商品货号']){
                array_push($heards,'商品货号');array_push($fields,array_shift($ary_fields_avaliable));
            }
            if($export_type['规格名称']){
                array_push($heards,'规格名称');array_push($fields,array_shift($ary_fields_avaliable));
            }

            if($export_type['售后状态']){
                array_push($heards,'售后状态');array_push($fields,array_shift($ary_fields_avaliable));
            }
            foreach ($ary_data as $ky => $vl) {
                $is_invoice = '';
                if ($vl['is_invoice'] == '1') {
                    $is_invoice = '是';
                } else {
                    $is_invoice = '否';
                }
                $o_pre_sale = '';
                if ($vl['o_pre_sale'] == '1') {
                    $o_pre_sale = '是';
                } else {
                    $o_pre_sale = '否';
                }
                $invoice_type = '';
                if ($vl['invoice_type'] == '1') {
                    $invoice_type = '普通发票';
                } else {
                    $invoice_type = '增值税发票';
                }
                $invoice_head = '';
                if($vl['invoice_head'] == '1'){
                    $invoice_head = '个人';
                }else{
                    $invoice_head = '单位';
                }
                $pay_time = D('OrdersLog')->where(array('o_id'=>$vl['o_id'],'ol_behavior'=>'支付成功'))->getField('ol_create');
                $wait_time = D('OrdersLog')->where(array('o_id'=>$vl['o_id'],'ol_behavior'=>'发货成功'))->getField('ol_create');
                $contents[0][$ky] = array();
                array_push($contents[0][$ky],$vl['o_id']." ");//订单编号
                array_push($contents[0][$ky],$vl['str_pay_status']);//订单状态
                array_push($contents[0][$ky],$vl['deliver_status']);//发货状态
                array_push($contents[0][$ky],$o_pre_sale);//是否预售订单
                array_push($contents[0][$ky],$vl['o_create_time']);//下单时间
                array_push($contents[0][$ky],$pay_time);//付款时间
                array_push($contents[0][$ky],$wait_time);//发货时间
                array_push($contents[0][$ky],$vl['m_name']);//会员
                if(isset($vl['admin_id']) && $vl['admin_id'] > 0) {
                    $ary_admin = D('Admin')->getAdminInfoById($vl['admin_id'], array('u_name'));
                    if(is_array($ary_admin) && !empty($ary_admin)) {
                        array_push($contents[0][$ky],$ary_admin['u_name']);//客服
                    }
                }else{
                    array_push($contents[0][$ky],'暂无客服');//客服
                }
                array_push($contents[0][$ky],empty($vl['last_gc_name'])?$vl['gc_name']:$vl['last_gc_name']);//产品分类
                if($export_type['二级分类']){
                    array_push($contents[0][$ky],$vl['gc_name']);//二级分类
                }
                if($export_type['品牌名称']){
                    array_push($contents[0][$ky],$vl['gb_name']);//品牌名称
                }
                array_push($contents[0][$ky],$vl['oi_g_name']);//产品名称
                array_push($contents[0][$ky],$vl['pdt_sale_price']);//商品价格
                array_push($contents[0][$ky],$vl['oi_price']);//成交价
                array_push($contents[0][$ky],$vl['oi_nums']);//数量
                array_push($contents[0][$ky],$vl['oi_price']*$vl['oi_nums']);//商品小计
                array_push($contents[0][$ky],$vl['o_all_price']);//订单总价
                array_push($contents[0][$ky],$vl['o_cost_freight']);//配送费用
                array_push($contents[0][$ky],$vl['o_pay']);//已支付金额
                array_push($contents[0][$ky],$vl['o_cost_payment']);//支付手续费
                array_push($contents[0][$ky],$vl['o_coupon_menoy']);//优惠券使用金额
                array_push($contents[0][$ky],$vl['coupon_sn']);//优惠券编号
                array_push($contents[0][$ky],$vl['coupon_value']);//优惠券面额
                if($export_type['赠送积分']){
                    array_push($contents[0][$ky],$vl['o_reward_point']);//优惠券面额
                }
                array_push($contents[0][$ky],$vl['pc_name']);//支付方式
                array_push($contents[0][$ky],$vl['o_receiver_name']);//收货人
                if($export_type['收货人手机']){
                    array_push($contents[0][$ky],$vl['o_receiver_mobile']);//收货人手机
                }
                if($export_type['收货人电话']){
                    array_push($contents[0][$ky],$vl['o_receiver_telphone']);//收货人电话
                }
                array_push($contents[0][$ky],$vl['o_receiver_state']);//省份
                array_push($contents[0][$ky],$vl['o_receiver_city']);//市
                array_push($contents[0][$ky],$vl['o_receiver_county']);//区
                if($export_type['收货地址']){
                    array_push($contents[0][$ky],$vl['o_receiver_address']);//收货地址
                }
                array_push($contents[0][$ky],$vl['o_receiver_zipcode']);//收货人邮编
                if($export_type['买家留言']){
                    array_push($contents[0][$ky],$vl['o_buyer_comments']);//买家留言
                }
                if($export_type['卖家备注']){
                    array_push($contents[0][$ky],$vl['o_seller_comments']);//卖家备注
                }
                if($export_type['是否开发票']){
                    array_push($contents[0][$ky],$is_invoice);//是否开发票
                }
                if($export_type['个人姓名']){
					if($is_invoice == '是'){
						array_push($contents[0][$ky],$vl['invoice_people'].$vl['invoice_name']);//个人姓名
					}else{
						array_push($contents[0][$ky],' ');//个人姓名
					} 
                }
                if($export_type['发票类型']){
					if($is_invoice == '是'){
						array_push($contents[0][$ky],$invoice_type);//发票类型
					}else{
						array_push($contents[0][$ky],' ');//发票类型
					}
                }
                if($export_type['发票抬头']){
					if($is_invoice == '是'){
						array_push($contents[0][$ky],$invoice_head);//发票抬头
					}else{
						array_push($contents[0][$ky],' ');//发票抬头
					}
                }
                if($export_type['发票内容']){
                    array_push($contents[0][$ky],$vl['invoice_content']);//发票内容
                }
                if($export_type['银行账号']){
                    array_push($contents[0][$ky],$vl['invoice_account']);//银行账号
                }
                if($export_type['开户银行']){
                    array_push($contents[0][$ky],$vl['invoice_bank']);//开户银行
                }
                if($export_type['注册电话']){
                    array_push($contents[0][$ky],$vl['invoice_phone']);//注册电话
                }
                if($export_type['注册地址']){
                    array_push($contents[0][$ky],$vl['invoice_address']);//注册地址
                }
                if($export_type['纳税人识别号']){
                    array_push($contents[0][$ky],$vl['invoice_identification_number']);//纳税人识别号
                }
                if($export_type['商品编码']){
                    array_push($contents[0][$ky],$vl['g_sn']);
                }
                if($export_type['商品货号']){
                    array_push($contents[0][$ky],$vl['pdt_sn']);
                }
                if($export_type['规格名称']){
                    $pdt_name = D('GoodsSpec')->getProductsSpec($vl['pdt_id']);
                    array_push($contents[0][$ky],$pdt_name);
                }
                if($export_type['售后状态']){
                    $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $vl['o_id']))->order('or_create_time asc')->select();
                    if (!empty($ary_afersale) && is_array($ary_afersale)) {
                        foreach ($ary_afersale as $keyaf => $valaf) {
                            if ($valaf['or_service_verify'] == '1' && $valaf['or_finance_verify'] == '1') {
                                M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $vl['o_id'],'or_update_time'=>date('Y-m-d H:i:s')))->save(array('or_processing_status' => 1));
                            }
                            //退款
                            if ($valaf['or_refund_type'] == 1) {
                                switch ($valaf['or_processing_status']) {
                                    case 0:
                                        array_push($contents[0][$ky],'退款中');
                                        break;
                                    case 1:
                                        array_push($contents[0][$ky],'退款成功');
                                        break;
                                    case 2:
                                        array_push($contents[0][$ky],'退款驳回');
                                        break;
                                    default:
                                        array_push($contents[0][$ky],'暂无'); //没有退款
                                }
                            } elseif ($valaf['or_refund_type'] == 2) {         //退货
                                switch ($valaf['or_processing_status']) {
                                    case 0:
                                        array_push($contents[0][$ky],'退货中');
                                        break;
                                    case 1:
                                        array_push($contents[0][$ky],'退货成功');
                                        break;
                                    case 2:
                                        array_push($contents[0][$ky],'退货驳回');
                                        break;
                                    default:
                                        array_push($contents[0][$ky],'暂无');
                                }
                            }
                        }
                    }elseif(empty($ary_afersale)){
                        array_push($contents[0][$ky],'暂无记录');
                    }
                }
            }
            $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
            if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
            }

			foreach($contents[0] as $k=>$v){
                if(!empty($v)){
                    foreach($contents[0][$k] as $key=>$val){
                        if(!isset($val)){
                            $contents[0][$k][$key]='';
                        }
                    }
                }
            }	
			if(count($contents[0])>2000){
				$excel_file = 'exportOrders'. date('Y-m-d-H-i-s', time()).'.csv';
				$this->export_csv($contents[0], $heards, $excel_file,$filexcel);
				if(file_exists($filexcel.$excel_file)){
					 $this->ajaxReturn(array('status' => '1', 'info' => '导出成功', 'data' => $excel_file));
				}else{
					$this->ajaxReturn(array('status' => '0', 'info' => '导出失败'));
				}				
			}else{		
				$Export = new Export(date('YmdHis') . '.xls', $filexcel); 
				$excel_file = $Export->exportExcel($heards, $contents[0], $fields, $mix_sheet = '订单导出', true);
				if (!empty($excel_file)) {
					$this->ajaxReturn(array('status' => '1', 'info' => '导出成功', 'data' => $excel_file));
				} else {
					$this->ajaxReturn(array('status' => '0', 'info' => '导出失败'));
				}	
			}
        } else {
            $this->ajaxReturn(array('status' => '0', 'info' => '没有需要导出单据'));
        }
    }
	
 	//订单导出
 	function export_csv($data, $title_arr, $file_name = '',$filexcel) {
		$csv_data = '';
		/** 标题 */
		$nums = count($title_arr);
		for ($i = 0; $i < $nums - 1; ++$i) {
			$csv_data .= '"' . $title_arr[$i] . '",';
		}

		if ($nums > 0) {
		$csv_data .= '"' . $title_arr[$nums - 1] . "\"\r\n";
		}
        $csv_data = iconv('utf-8', 'GB2312',$csv_data);
		$file_name = empty($file_name) ? date('Y-m-d-H-i-s', time()) : $file_name;
		if(count($data)>300){
			file_put_contents($filexcel.$file_name,  $csv_data) ;
			foreach ($data as $k => $row) {
				$csv_data = "";
				for ($i = 0; $i < $nums - 1; ++$i) {
					if($i == 0){
						$row[$i] = str_replace("\"", "\"\"", $row[$i]);
						$csv_data .= '`'.trim($row[$i]). '`,';						
					}else{
						$row[$i] = str_replace("\"", "\"\"", trim($row[$i]));
						
						$csv_data .= '"' . $row[$i] . '",';					
					}
				}
				$csv_data .= '"' . $row[$nums - 1] . "\"\r\n";
				unset($data[$k]);
				file_put_contents($filexcel.$file_name, iconv('utf-8', 'GB2312', $csv_data),FILE_APPEND) ;
			}			
		}
        else{
			foreach ($data as $k => $row) {
                $csv_line='';
				for ($i = 0; $i < $nums - 1; ++$i) {
					if($i == 0){
						$row[$i] = str_replace("\"", "\"\"", $row[$i]);
                        $csv_line .= '`'.trim($row[$i]). '`,';
					}else{
						$row[$i] = str_replace("\"", "\"\"", trim($row[$i]));

                        $csv_line .= '"' . $row[$i] . '",';
					}
				}
                $csv_line .= '"' . $row[$nums - 1] . "\"\r\n";
                $csv_line = iconv('utf-8', 'GB2312',$csv_line);
                $csv_data .= $csv_line;
				//unset($data[$k]);
			}
			file_put_contents($filexcel.$file_name,  $csv_data) ;
//			file_put_contents($filexcel.$file_name, $csv_data) ;
		}
	}
	public function selectOrdersPropetry(){
        $this->display();
    }
    
    /**
     * 订单导出
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getOrdersDialog() {
        $ary_post = $this->_post();
//        echo "<pre>";print_r($ary_post);die;
        $this->assign("filter", $ary_post);
        $this->display();
    }

    /**
     * 订单高级搜索
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getOrdersSearch() {
        $ary_data = array();
        $ary_data['payment'] = M("payment_cfg", C('DB_PREFIX'), 'DB_CUSTOM')->where(1)->select();
        $ary_data['corp'] = M("logistic_corp", C('DB_PREFIX'), 'DB_CUSTOM')->where(1)->select();
        //根据来源cps订单查询
        $bigou_cps_open  = D('SysConfig')->getConfigValueBySckey('CPS_51BIGOU_OPEN','CPS_SET');
        $fanli_cps_open = D('SysConfig')->getConfigValueBySckey('CPS_51FANLI_OPEN','CPS_SET');

        $bigou_cps_channel_id  = D('SysConfig')->getConfigValueBySckey('BIGOU_CHANNELID','CPS_SET');
        $fanli_cps_channel_id = D('SysConfig')->getConfigValueBySckey('FANLI_CHANNELID','CPS_SET');
        $ary_data['cps_info'] = array(
            $bigou_cps_channel_id => $bigou_cps_open,
            $fanli_cps_channel_id => $fanli_cps_open
        );
        $this->assign("data", $ary_data);
        $this->display();
    }

    /**
     * 根据订单号获取订单商品明细
     * @author Haophper
     * @date 2013-8-15
     * @return json
     */
    public function ajaxGetOrderItems() {
        $ary_result = array(
            'success' => 0,
            'msg' => '',
            'data' => array()
        );
        $ary_status_msg = array(
            1 => '正常订单',
            2 => '订单已作废',
            3 => '订单已暂停',
            4 => '订单已完成',
            5 => '订单已确认收货'
        );
        $ary_pay_msg = array(
            0 => '订单未支付',
            1 => '订单已支付',
            2 => '订单正在第三方平台支付',
            3 => '订单已部分支付'
        );
        $int_oid = trim($_POST['o_id']);
        $ary_order_field = array('o_id', 'o_pay_status', 'o_all_price','o_coupon_menoy', 'o_goods_all_price', 'o_discount', 'o_status', 'o_audit', 'o_cost_freight','m_id');
        $ary_order = D('Orders')->getOrderBaseInfo($int_oid, $ary_order_field);
        //订单不存在
        if (!is_array($ary_order) || empty($ary_order)) {
            $ary_result['msg'] = '订单不存在或者已被删除！';
            echo json_encode($ary_result);
            die;
        }
        //判断订单审核状态:0：未审核，1：已审核
        if ($ary_order['o_audit'] != 0) {
            $ary_result['msg'] = '订单已审核，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }

        //判断订单转台，1：正常订单，2：作废订单，3：已暂停订单， 4：完成订单， 5：订单确认收货
        if ($ary_order['o_status'] != 1) {
            $ary_result['msg'] = $ary_status_msg[$ary_order['o_status']] . '，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }
        //只有正常且未支付的订单才可以只修改商品价格
        if ($ary_order['o_pay_status'] != 0) {
            $ary_result['msg'] = $ary_pay_msg[$ary_order['o_pay_status']] . '，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }
        $ary_oi_field = array('oi_id', 'o_id', 'g_id', 'pdt_id','pdt_sn', 'oi_price', 'pdt_sale_price', 'oi_nums', 'g_sn', 'oi_sendnum', 'oi_refund_status', 'oi_ship_status', 'oi_type','promotion','oi_single_allowance');
        //获取订单明细信息
        $ary_items = D('OrdersItems')->getOrderItemsInfo($int_oid, $ary_oi_field);
        if (!is_array($ary_items) || empty($ary_items)) {
            $ary_result['msg'] = '订单明细不存在!';
            echo json_encode($ary_result);
            die;
        }
        $ary_avai_items = array();
        //过滤订单明细信息
        foreach ($ary_items as &$ary_oi) {
            $ary_oi['not_modify_reason'] = '';
            $ary_oi['can_modify'] = 1;
            //售后状态：1：正常订单，2：退款中，3：退货中，4：退款成功，5：退货成功，6：被驳回
            //只有正常状态的明细才可以修改价格
            if ($ary_oi['oi_refund_status'] != 1) {
                $ary_oi['can_modified'] = 0;
                $ary_oi['not_modify_reason'] = '已经申请售后';
            }
            //发货状态：0：待发货，1：仓库准备中，2：已发货，3：缺货，4，退货
            //只有未发货的明细才可以修改价格
            if ($ary_oi['oi_ship_status'] != 0) {
                $ary_oi['can_modified'] = 0;
                $ary_oi['not_modify_reason'] = '已做发货处理';
            }
            //获取明细中商品信息
            $ary_goods_info = D('GoodsInfo')->Search(array('g_id' => $ary_oi['g_id']), array('g_name', 'g_picture'));
            //获取货品规格信息
            $ary_pdt_spec = D('GoodsSpec')->getProductsSpec($ary_oi['pdt_id'], 2);
            $ary_oi['g_name'] = $ary_goods_info['g_name'];
            $ary_oi['g_picture'] = $ary_goods_info['g_pictrue'];
            $ary_oi['spec_value'] = $ary_pdt_spec;
            //处理价格显示两位小数,oi_price除以数量才是单价
            $ary_oi['oi_price'] = sprintf("%0.2f", $ary_oi['oi_price']);
        }

        //处理物流费用
        $ary_order['o_cost_freight'] = sprintf("%0.2f", $ary_order['o_cost_freight']);
        $ary_order['items'] = $ary_items;
        unset($ary_items);
        $ary_result['success'] = 1;
        $ary_result['data'] = $ary_order;
        unset($ary_order);//print_r($ary_result);exit;
        echo json_encode($ary_result);
        die;
    }

    /**
     * 更新订单明细中商品价格
     * @author haophper
     * @date 2013-8-15
     * @return json
     */
    public function ajaxUpdateOrderItemsPrice() {
        $ary_result = array(
            'success' => 0,
            'msg' => '',
            'data' => array()
        );
        $ary_status_msg = array(
            1 => '正常订单',
            2 => '订单已作废',
            3 => '订单已暂停',
            4 => '订单已完成',
            5 => '订单已确认收货'
        );
        $ary_pay_msg = array(
            0 => '订单未支付',
            1 => '订单已支付',
            2 => '订单正在第三方平台支付',
            3 => '订单已部分支付'
        );
        $ary_post = $_POST;
        if (!is_array($ary_post) || empty($ary_post)) {
            $ary_result['msg'] = '没有提交任何数据！';
            echo json_encode($ary_result);
            die;
        }
        if (!isset($ary_post['o_id']) || empty($ary_post['o_id'])) {
            $ary_result['msg'] = '没有选中任何订单！';
            echo json_encode($ary_result);
            die;
        }
        if (!is_array($ary_post['pro_pdt_sn']) || empty($ary_post['pro_pdt_sn'])) {
            $ary_result['msg'] = '没有提交任何明细数据！';
            echo json_encode($ary_result);
            die;
        }
        //判断订单状态
        $ary_order_field = array('o_id', 'o_pay_status', 'o_discount', 'o_status', 'o_audit', 'o_cost_freight');
        $ary_order = D('Orders')->getOrderBaseInfo($ary_post['o_id'], $ary_order_field);
        //订单不存在
        if (!is_array($ary_order) || empty($ary_order)) {
            $this->orderLog($ary_post['o_id'], "订单不存在或者已被删除");
            $ary_result['msg'] = '订单不存在或者已被删除！';
            echo json_encode($ary_result);
            die;
        }
        //判断订单审核状态:0：未审核，1：已审核
        if ($ary_order['o_audit'] != 0) {
            $this->orderLog($ary_post['o_id'], "订单已审核，不可以修改价格");
            $ary_result['msg'] = '订单已审核，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }

        //判断订单转台，1：正常订单，2：作废订单，3：已暂停订单， 4：完成订单， 5：订单确认收货
        if ($ary_order['o_status'] != 1) {
            $this->orderLog($ary_post['o_id'], $ary_status_msg[$ary_order['o_status']] . '，不可以修改价格');
            $ary_result['msg'] = $ary_status_msg[$ary_order['o_status']] . '，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }
        //只有正常且未支付的订单才可以只修改商品价格
        if ($ary_order['o_pay_status'] != 0) {
            $this->orderLog($ary_post['o_id'], $ary_pay_msg[$ary_order['o_pay_status']] . '，不可以修改价格');
            $ary_result['msg'] = $ary_pay_msg[$ary_order['o_pay_status']] . '，不可以修改价格';
            echo json_encode($ary_result);
            die;
        }
        $ary_oi_field = array('oi_id', 'o_id', 'g_id', 'pdt_id', 'oi_nums', 'pdt_sale_price', 'oi_sendnum', 'oi_refund_status', 'oi_ship_status', 'oi_type');
        //获取订单明细信息
        $ary_items = D('OrdersItems')->getOrderItemsInfo($ary_post['o_id'], $ary_oi_field);
        if (!is_array($ary_items) || empty($ary_items)) {
            $this->orderLog($ary_post['o_id'], "订单明细不存在");
            $ary_result['msg'] = '订单明细不存在!';
            echo json_encode($ary_result);
            die;
        }
        //如果提交的订单明细数量和数据库中的明细数量不一致，不可以修改
        if (count($ary_items) != count($ary_post['pro_pdt_sn'])) {
            $this->orderLog($ary_post['o_id'], "您提交的数据有误");
            $ary_result['msg'] = '您提交的数据有误!';
            echo json_encode($ary_result);
            die;
        }
        $ary_data_items = array();
        $int_can_modify = true;
        //过滤订单明细信息
        foreach ($ary_items as $ary_oi) {
            //售后状态：1：正常订单，2：退款中，3：退货中，4：退款成功，5：退货成功，6：被驳回
            //只有正常状态的明细才可以修改价格
            if ($ary_oi['oi_refund_status'] != 1) {
                $ary_result['msg'] = '已经申请售后';
                $int_can_modify = false;
                break;
            }
            //发货状态：0：待发货，1：仓库准备中，2：已发货，3：缺货，4，退货
            //只有未发货的明细才可以修改价格
            if ($ary_oi['oi_ship_status'] != 0) {
                $ary_result['msg'] = '已做发货处理';
                $int_can_modify = false;
                break;
            }
            $ary_data_items[$ary_oi['oi_id']] = $ary_oi;
        }
        unset($ary_items);
        unset($ary_oi);
        if (!$int_can_modify) {
            echo json_encode($ary_result);
            die;
        }
        
        $order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_pdts = array();
        //修改的商品数组
        $ary_cart = array();
        foreach($ary_post['pro_pdt_id'] as $key=>$pdt_id){
            $ary_pdts[] = array(
                    'pdt_id' => $pdt_id,
                    'g_id' => $ary_post['pro_g_id'][$key],
                    'type' => $ary_post['pro_type'][$key],
                    'oi_price' => $ary_post['pro_price'][$key],
                    'num' => $ary_post['pro_num'][$key]
            );
        }
        $order_items = $order_item_obj->where(array('o_id'=>$ary_post['o_id'],'oi_type'=>array('neq',0)))->select();
        //除了普通商品之外的订单
        foreach($order_items as $v){
            $ary_pdts[] = array(
                    'pdt_id'=>$v['pdt_id'],
                    'num'=>$v['oi_nums'],
                    'type'=>$v['oi_type'],
                    'fc_id'=>$v['fc_id'],
                    'oi_price'=>0
            );
        }
        //订单处理
        $ary_new_pdts = array();
        foreach($ary_pdts as $p) {
            if($p['type'] == 0) {
                $ary_new_pdts[$p['pdt_id']] = $p;
            } else if($p['type'] == 1) {
                $ary_new_pdts[$p['pdt_id']] = $p;
            } else if($p['type'] == 2) {
                $ary_new_pdts['gifts'][$p['pdt_id']] = $p;
            } else if($p['type'] == 3) {
                //组合商品不予处理
            } else if($p['type'] == 4) {
                $ary_new_pdts['free'.$p['fc_id']]['pdt_id'][$p['pdt_id']] = $p['pdt_id'];
                $ary_new_pdts['free'.$p['fc_id']]['num'][$p['pdt_id']] = $p['num'];
                $ary_new_pdts['free'.$p['fc_id']]['oi_price'][$p['pdt_id']] = $p['oi_price'];
                if(!isset($ary_new_pdts['free'.$p['fc_id']]['fc_id'])) {
                    $ary_new_pdts['free'.$p['fc_id']]['fc_id'] = $p['fc_id'];
                    $ary_new_pdts['free'.$p['fc_id']]['type'] = 4;
                }
            }
        }

        unset($ary_new_pdts['gifts']);
        //dump($ary_new_pdts);
        $promotion_data = D('Promotion')->calShopCartPro($ary_post['m_id'], $ary_new_pdts);
        //print_r($promotion_data);die();
        //整单促销规则
        //订单优惠金额（购物车优惠金额）
        $promotion_price = 0;
        //商品优惠金额
        $o_goods_discount = 0;
        //商品总金额（计算促销）
        $goods_price = 0;
        //商品总金额（不计算促销）
        //享受促销信息
        $goods_saleprice = 0;
        $order_price = 0;
        $promotion_title = '';
        //返回订单数据
        $ary_data = array();
        $ary_promotion = array();
        $ary_data['promotion'] = '';
        foreach($promotion_data as $key=>$promotion){
            if($key != 'subtotal'){
                if($key != '0'){
                    $promotion_price +=$promotion['goods_all_discount'];
                }
            }else{
                $goods_price = $promotion['goods_total_price'];
                $goods_saleprice = $promotion['goods_total_sale_price'];
                $o_goods_discount = $promotion['goods_all_discount'];
            }
        }
        //团购商品
        //获取团购价与商品原价
        $price_info = new ProPrice();
        if($order_items[0]['oi_type'] == '5'){
            $tuan_price = $price_info->getPriceInfo($order_items[0]['pdt_id'], $ary_post['m_id'], 5, $ary_extra=array('gp_id'=>$order_items[0]['fc_id']));
            //分别是购物车优惠金额、商品总销售价、商品折扣金额
            $ary_data['o_goods_all_saleprice'] = $order_items[0]['oi_nums']*$tuan_price['pdt_sale_price'];
            $ary_data['o_goods_discount'] = $order_items[0]['oi_nums']*$tuan_price['thrift_price'];
            //商品总价
            $ary_data['o_goods_all_price'] = $order_items[0]['oi_nums']*$tuan_price['pdt_price'];
            $goods_price = $ary_post['o_goods_all_price'];
        }else{
            $pro_datas = $promotion_data;
            $subtotal = $pro_datas ['subtotal'];
            unset ( $pro_datas ['subtotal'] );
            // 满足满包邮条件
            foreach ( $pro_datas as $pro_data ) {
                if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                    $logistic_price = 0;
                }
            }
            // 商品总价
            $ary_orders ['o_goods_all_price'] = 0;
            $promotion_total_price = '0';
            $promotion_price = '0';
            //赠品数组
            $cart_gifts = array();
            foreach($pro_datas as $keys=>$vals){
                foreach($vals['products'] as $key=>$val){
                    $arr_products = D ( 'Cart' )->getProductInfo(array($key=>$val));
                    if($arr_products[0][0]['type'] == '4'){
                        foreach($arr_products[0] as &$provals){
                            $provals['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $provals['g_id']);
                        }
                    }
                    $product_data = $arr_products[0];
                    //手动输入价格按手动输入的计算
                    if($product_data['type'] == '0'){
                        if(!empty($val['oi_price'])){
                            $product_data['oi_price'] = $val['oi_price'];
                        }else{
                            $product_data['oi_price'] = $val['pdt_price'];
                        }
                    }
                    $pro_datas[$keys]['products'][$key] =  $product_data;
                    $pro_datas[$keys]['products'][$key]['pmn_name'] = $vals['pmn_name'].' '.$val['pmn_name'];
                    $pro_data[$key] = $val;
                    $pro_data[$key]['pmn_name'] = $vals['pmn_name'].' '.$val['pmn_name'];
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
            //分别是购物车优惠金额、商品总销售价、商品折扣金额
            //订单商品总价（销售价格带促销）
            $ary_data ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
            //商品销售总价
            $ary_data ['o_goods_all_saleprice'] = sprintf ( "%0.2f", $promotion_total_price);
            $ary_data ['o_discount'] = sprintf("%0.2f", $promotion_price);
            $ary_data['o_goods_discount'] = sprintf ( "%0.2f", $subtotal ['goods_total_sale_price'] - $promotion_total_price );
        }
        if(empty($ary_data['o_goods_all_price'])){
            $ary_data['status'] = '0';
        }else{
            $ary_data['status'] = '1';
        }
        //获取赠品信息
        if(!empty($cart_gifts)){
            $cart_gifts_data = array();
            $cart_gifts_data = D ( 'Cart' )->getProductInfo($cart_gifts);
        }
        //dump($pro_datas);die();
        $ary_orders_info = array();
        foreach($pro_datas as $vals){
            foreach($vals['products'] as $key=>$val){
                if($val[0]['type'] == '4'){
                    $ary_orders_info['free_item'][] = array(
                            'fc_id'=>$val[0]['type'],
                            'items'=>$val
                            );
                }else{
                    $ary_orders_info[] = array_merge(array('pmn_name'=>$vals['pmn_name'].' '+$val['pmn_name']),$val);
                }
            }
        }
        if(!empty($cart_gifts_data)){
            foreach($cart_gifts_data as $gift_data){
                $ary_orders_info[] = $gift_data;
            }
        }
        //dump($ary_orders_info);die();
        // 数据处理
        foreach($ary_orders_info as &$order_info){
            if($order_info[0]['items'][0]['type'] == '4'){
                foreach($order_info as $key=>&$sub_order_info){
                    foreach($sub_order_info['items'] as &$sub_info){
                        $sub_info['oi_type'] =  $sub_info['type'];
                        $sub_info['oi_g_name'] =  $sub_info['g_name'];
                        $sub_info['oi_price'] =  $sub_info['f_price'];
                        $sub_info['oi_nums'] =  $sub_info['pdt_nums'];
                        $sub_info['subtotal'] =  $sub_info['pdt_momery'];
                        $sub_info['promotion'] =  '自由推荐';
                    }
                }
            }else{
                $order_info['oi_type'] =  $order_info['type'];
                $order_info['oi_g_name'] =  $order_info['g_name'];
                $order_info['oi_nums'] =  $order_info['pdt_nums'];
                if(!empty($order_info['oi_price'])){
                    $order_info['oi_price'] =  $order_info['oi_price'];
                    $order_info['subtotal'] =  $order_info['oi_price']*$order_info['pdt_nums'];
                }else{
                    $order_info['oi_price'] =  $order_info['f_price'];
                    $order_info['subtotal'] =  $order_info['pdt_momery'];
                }
                $order_info['promotion'] =  $order_info['pmn_name'].$prom['rule_info']['name'];
            }			
        }
        //优惠券金额
        $ary_data['o_coupon_menoy'] = D('Orders')->where(array('o_id'=>$ary_post['o_id']))->getField('o_coupon_menoy');
        $ary_data['o_goods_all_price'] -= $ary_data['o_coupon_menoy'];
        //更新明细信息
        $obj_order_items = D('OrdersItems');
        $obj_order_items->startTrans();
        $ary_order_up = array();
        //处理订单总价
        $ary_order_up['o_all_price'] = sprintf("%0.2f", ($ary_order['o_cost_freight'] + $ary_data['o_goods_all_price']));
        //优惠价格
        $ary_order_up['o_discount'] = sprintf("%0.2f", $ary_data['o_discount']);
        //商品总价
        $ary_order_up['o_goods_all_price'] = $ary_data['o_goods_all_price'];
        $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR', 'ORDERS_OPERATOR', '1', '只记录第一次操作人');
        //查询订单是否存在操作者ID
        $admin_id = $_SESSION['Admin'];
        if ($resdata['ORDERS_OPERATOR']['sc_value'] == '1') {
            $order_admin_id = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->getField('admin_id');
            if (empty($order_admin_id)) {
                $ary_order_up['admin_id'] = $admin_id;
            }
        } else {
            $ary_order_up['admin_id'] = $admin_id;
        }
        if ($resdata['ORDERS_OPERATOR']['sc_value'] == '1') {
            $order_admin_id = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->getField('admin_id');
            if (empty($order_admin_id)) {
                $ary_order_up['admin_id'] = $admin_id;
            }
        } else {
            $ary_order_up['admin_id'] = $admin_id;
        }
        $bl_o_res = D('Orders')->updateOrderInfo($ary_post['o_id'], $ary_order_up);
        if ($bl_o_res === false) {
            $obj_order_items->rollback();
            $ary_result['msg'] = '更新订单时遇到错误！';
            echo json_encode($ary_result);
            die;
        }
        //更新订单明细表中的新价格
        foreach ($ary_orders_info as $key => $ary_oi_price){
            //计算商品总价
            $bl_oi_up_res = true;
            $ary_oi_up = array(
                'oi_price' => sprintf("%0.2f", $ary_oi_price['oi_price'])
            );
            $oi_id = M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_post['o_id'],'pdt_id'=>$ary_oi_price['pdt_id']))->getField('oi_id');
            $bl_oi_res = $obj_order_items->updateOrderItemsCostPrice($ary_post['o_id'], $oi_id, $ary_oi_up);
            if ($bl_oi_res === false) {
                $bl_oi_up_res = false;
                $ary_goods_info = D('GoodsInfo')->Search(array('g_id' => $ary_oi_price['g_id']), array('g_name'));
                $ary_result['msg'] = '更新商品（' . $ary_goods_info['g_name'] . '）价格时遇到错误！';
                break;
            }
            if (!$bl_oi_up_res) {
                $obj_order_items->rollback();
                echo json_encode($ary_result);
                die;
            }
        }
        $this->orderLog($ary_post['o_id'], "修改价格成功");
        $obj_order_items->commit();
        $ary_result['success'] = 1;

        echo json_encode($ary_result);
        die;
    }

    /**
     * 设置备注
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-08-15
     */
    public function OrderRemarkUpdate() {
        $ary_post = $this->_post();
        if (isset($ary_post['o_id']) && !empty($ary_post['o_id'])) {
            $where = array();
            $data = array();
            $ary_orders = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_post['o_id']))->field('o_seller_comments')->find();
            $data['o_seller_comments'] = $ary_orders['o_seller_comments'] . "<br/>" . $ary_post['remark'];
            $data['o_update_time'] = date('Y-m-d H:i:s');
            $where['o_id'] = $ary_post['o_id'];
            $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR', 'ORDERS_OPERATOR', '1', '只记录第一次操作人');
            //查询订单是否存在操作者ID
            $admin_id = $_SESSION['Admin'];
            if ($resdata['ORDERS_OPERATOR']['sc_value'] == '1') {
                $order_admin_id = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->getField('admin_id');
                if (empty($order_admin_id)) {
                    $data['admin_id'] = $admin_id;
                }
            } else {
                $data['admin_id'] = $admin_id;
            }
            $ary_result = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->data($data)->save();
            if (FALSE !== $ary_result) {
                $this->orderLog($ary_post['o_id'], "备注设置成功");
                $this->success("备注设置成功");
            } else {
                $this->orderLog($ary_post['o_id'], "备注设置失败");
                $this->error("备注设置失败");
            }
        } else {
            $this->error("缺少 o_id");
        }
    }

    /**
     * 设置卖家备注
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-08-15
     */
    public function setOrdersRemark() {
        $int_o_id = trim($this->_post('o_id'));
        if (isset($int_o_id)) {
            $ary_data = D('Gyfx')->selectOne('orders','o_id,o_seller_comments',array("o_id" => $int_o_id));
        }
        $this->assign('ary_data', $ary_data);
        $this->display('setOrdersRemark');
    }

    /**
     * 设置新物流费用
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-08-15
     */
    public function OrderFreightUpdate() {
        $ary_post = $this->_post();
        if (isset($ary_post['o_id']) && !empty($ary_post['o_id'])) {
            $where = array('o_id' => $ary_post['o_id']);
            $ary_order_data = array();
            $tzfx = $ary_post['tzfx'];

            if (!is_numeric($ary_post['tzje']))
                $this->error("订单物流费用不是数字格式");
            $obj_order = M("orders", C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_data = $obj_order->field('o_cost_freight,o_pay')->where($where)->find();

            $fun = 1 == $tzfx ? 'setDec' : 'setInc';
            if(1 == $tzfx && $ary_data['o_cost_freight']<$ary_post['tzje']) $this->error("调整后的订单物流费用不能小于0");
    		$obj_order->startTrans();
    		$ary_result = $obj_order->where($where)->$fun('o_cost_freight',$ary_post['tzje']);
			$ary_result1 = $obj_order->where($where)->$fun('o_all_price',$ary_post['tzje']);
			
    		if (FALSE !== $ary_result && FALSE !== $ary_result1) {
    			$resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR','ORDERS_OPERATOR','1','只记录第一次操作人');
    			//查询订单是否存在操作者ID
    			$admin_id = $_SESSION['Admin'];
    			if($resdata['ORDERS_OPERATOR']['sc_value'] == '1'){
    				$order_admin_id =  M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->getField('admin_id');
    				if(empty($order_admin_id)){
    					$ary_order_data['admin_id'] = $admin_id;
    				}
    			}else{
    				$ary_order_data['admin_id'] = $admin_id;
    			}
    			$ary_result = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->data($ary_order_data)->save();
    			if (FALSE !== $ary_result) {
    				$this->orderLog($ary_post['o_id'],"修改物流费用,调整金额为：".$ary_post['tzje']);
    			}
	            $obj_order->commit();
    			$this->success("订单物流费用设置成功");
    		} else {
	            $obj_order->rollback();
    			$this->error("订单物流费用设置失败");
    		}
    	} else {
    		$this->error("缺少 o_id");
    	}
    }

    /**
     * 订单物流费用ajax打开页面
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-09-04
     */
    public function setOrdersFreight() {
        $o_id = $this->_post('o_id');
        if (isset($o_id)) {
            $GoodsInfo = M("orders", C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_data = $GoodsInfo->field('o_id,o_cost_freight')->where(array("o_id" => $o_id))->find();
        }
        $this->assign('ary_data', $ary_data);
        $this->display('setOrdersFreight');
    }
	
    /**
     * 订单支付申请
     * @author Wanggguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-10
     * 
     */
    public function doPayOrderReply() {
        $ary_post = $this->_post();
		$verify_order = $this->verifyOrderStatus($ary_post['oid']);
		if($verify_order['status'] == 0){
			$this->error($verify_order['msg']);exit;
		}
		if(empty($ary_post['ap_remark'])){
			$this->error('备注不能为空');exit;
		}
		$ary_data = array(
			'order_id'=>$ary_post['oid'],
			'add_u_id'=>$_SESSION['Admin'],
			'add_u_name'=>$_SESSION['admin_name'],
			'ap_remark'=>trim($ary_post['ap_remark']),
			'ap_create_time'=>date('Y-m-d H:i:s'),
			'ap_update_time'=>date('Y-m-d H:i:s')
		);
		$return_status = D('AdminPay')->data($ary_data)->add();
		if($return_status){
			$this->success('订单支付申请成功,请等待审核');exit;
		}else{
			$this->error('新增订单支付申请失败');exit;
		}
    }
	
	/**
     * 添加支付申请
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-11
     */
    public function orderPay() {
        $ary_data = $this->_post();
		$reply_data = D('AdminPay')->where(array('ap_id'=>$ary_data['id'],'ap_status'=>0))->find();
		$this->assign('reply_data', $reply_data);
		$this->assign('ary_data', $ary_data);
        $this->display('orderPay');
    }
	
    /**
     * 订单支付
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-15
     * 
     */
    public function doOrderPay() {
        $ary_post = $this->_post();
        $where = array();
        $where['fx_orders.o_id'] = $ary_post['oid'];
        $where['fx_orders.o_pay_status'] = 0;
        $where['fx_orders.o_status'] = array(array('eq', '1'), array('eq', '3'), 'or');
        $search_field = array('fx_orders.o_all_price', 'fx_orders.o_payment', 'fx_orders.o_pay', 'fx_members.m_id',
            'fx_orders.o_pay_status', 'fx_orders.o_reward_point', 'fx_orders.o_freeze_point',
            'fx_orders_items.pdt_id', 'fx_orders_items.oi_nums', 'fx_orders_items.oi_type', 'fx_orders_items.g_id');
        $ary_orders_data = D('Orders')->getOrdersData($where, $search_field, $group);
        $ary_orders = $ary_orders_data[0];
        if (!empty($ary_orders) && is_array($ary_orders)) {
			M("", C('DB_PREFIX'), 'DB_CUSTOM')->startTrans();
            //订单操作
            $order_data = array();
            //查询订单是否存在操作者ID
            $admin_id = $_SESSION['Admin'];
            $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR','ORDERS_OPERATOR','1','只记录第一次操作人');
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1){
                $order_data['o_audit'] = 1;
            }
            $order_data['o_pay_status'] = 1;
            $order_data['o_update_time'] = date('Y-m-d H:i:s');
            $ary_result = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array("o_id"=>$ary_post['oid']))->data($order_data)->save();
            if(FALSE !== $ary_result){
				$result_status = 0;
                //判断支付类型
				if($ary_post['pay_type'] == 1){
					//判断用户预存款是否组
					$mem_data = D("Members")->field("m_balance")->where(array("m_id"=>$ary_orders['m_id']))->find();
					if($mem_data['m_balance']<$ary_orders['o_all_price']){
                          M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                          $this->error("预存款不足，无法完成支付");	exit;					
					}
					$str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
					$ary_balance_info = array(
						'bt_id' => '1',
						'bi_sn' => time().$str_sn,
						'm_id' => $ary_orders['m_id'],
						'bi_money' => $ary_orders['o_all_price'],
						'bi_type' => '1',
						'bi_payment_time' => date("Y-m-d H:i:s"),
						'o_id' => $ary_post['oid'],
						'bi_desc' => '订单支付',
						'bi_create_time' => date("Y-m-d H:i:s"),
						'bi_update_time'=>date("Y-m-d H:i:s"),
						'u_id'=>$_SESSION['Admin'],
						'bi_service_verify'=>1,
						'bi_finance_verify'=>1,
						'single_type'=>1
					);
					$arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
					if (FALSE !== $arr_res) {
                        $m_balance = $mem_data['m_balance']-$ary_orders['o_all_price'];
						$arr_balance_res = D("Members")->where(array('m_id'=>$ary_orders['m_id']))->data(array('m_balance'=>$m_balance))->save();
						if (FALSE == $arr_balance_res){
							M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                            $this->error("更新会员预存款失败");exit;
						}else{
							$ary_order_result = M("orders", C('DB_PREFIX'), 'DB_CUSTOM')->where(array("o_id"=>$ary_post['oid']))->data(array('o_pay'=>$ary_orders['o_all_price'],'o_update_time'=>date('Y-m-d H:i:s')))->save();
							if($ary_order_result == false){
								M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
								$this->error("更新订单已付款金额失败");exit;								
							}else{
								//payment_serial表里插入支付记录
								$ary_serial_data = array(
									'm_id'=>$ary_orders['m_id'],
									'pc_code'=>'DEPOSIT',
									'ps_money'=>$ary_orders['o_all_price'],
									'o_id'=>$ary_post['oid'],
									'ps_status'=>1,
									'ps_create_time'=>date('Y-m-d H:i:s'),
									'ps_update_time'=>date('Y-m-d H:i:s')
								);
								$serial_res = D('PaymentSerial')->data($ary_serial_data)->add();
								if($serial_res == false){
									M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
									$this->error("更新支付流水号表失败");exit;											
								}
								$result_status = 1;
							}
						}
					}
				}else{
					//直接支付
					if($ary_post['pay_type'] == 2){
						if(empty($ary_post['ps_gateway_sn'])){
							M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							$this->error("请填写流水单号");exit;									
						}
						$ary_serial_data = array(
							'm_id'=>$ary_orders['m_id'],
							//'pc_code'=>'DEPOSIT',
							'ps_money'=>$ary_orders['o_all_price'],
							'o_id'=>$ary_post['oid'],
							'ps_status'=>1,
							'ps_gateway_sn'=>trim($ary_post['ps_gateway_sn']),
							'ps_create_time'=>date('Y-m-d H:i:s'),
							'ps_update_time'=>date('Y-m-d H:i:s')
						);
						$serial_res = D('PaymentSerial')->data($ary_serial_data)->add();
						if($serial_res == false){
							M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							$this->error("更新支付流水号表失败");exit;											
						}
						$result_status = 1;
					}
				}
				if (FALSE !== $result_status) {
					//订单日志记录
					$ary_orders_log = array(
						'o_id' => $ary_post['oid'],
						'ol_behavior' => '支付成功',
						'ol_uname' => "管理员：" . $_SESSION['admin_name'],
						'ol_create' => date('Y-m-d H:i:s')
					);
					$res_orders_log = D('OrdersLog')->add($ary_orders_log);

					if ($res_orders_log) {
						//更新审核状态
						$ary_ap_res = array('ap_status'=>1,'verify_u_id'=>$_SESSION['Admin'],'ps_id'=>$serial_res,'verify_u_name'=>$_SESSION['admin_name'],'ap_update_time'=>date('Y-m-d H:i:s'));
						if(!empty($ary_post['ps_gateway_sn'])){
							$ary_ap_res['ps_gateway_sn'] = trim($ary_post['ps_gateway_sn']);
						}
						$res_ap_res = D('AdminPay')->where(array('ap_id'=>$ary_post['id']))->data($ary_ap_res)->save();
						if($res_ap_res == false){
							M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
							$this->error("更新审核状态失败");						
						}else{
							M("", C('DB_PREFIX'), 'DB_CUSTOM')->commit();
							writeLog("支付成功", "order_pay.log");
							$this->success("支付成功");							
						}
					} 
                } else {
                    $this->orderLog($ary_post['oid'], "生成单据失败");
                    M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                    writeLog("生成单据失败", "order_pay.log");
                    $this->error("生成单据失败，请重试");
                }
			 } else {
                $this->orderLog($ary_post['oid'], "支付失败，请重试");
                M("", C('DB_PREFIX'), 'DB_CUSTOM')->rollback();
                writeLog("支付失败，请重试", "order_pay.log");
                $this->error("支付失败，请重试");
            }
		 } else {
            $this->orderLog($ary_post['oid'], "订单不存在或者已经支付");
			//作废掉此单据
			D('AdminPay')->where(array('ap_id'=>$ary_post['id']))->data(array('ap_status'=>2,'verify_u_id'=>$_SESSION['Admin'],'verify_u_name'=>$_SESSION['admin_name'],'ap_update_time'=>date('Y-m-d H:i:s')))->save();
            writeLog("订单不存在或者已经支付", "order_pay.log");
            $this->error("订单不存在或者已经支付");
        }
				  
    }
	
    /**
     * 单据作废
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-09-10
     */
    public function doOrdersPayStatus(){
        $ary_data = $this->_post();
		if(!empty($ary_data['id'])){
			$res = D('AdminPay')->where(array('ap_id'=>$ary_data['id'],'ap_status'=>0))->data(array('ap_status'=>2,'verify_u_id'=>$_SESSION['Admin'],'verify_u_name'=>$_SESSION['admin_name'],'ap_update_time'=>date('Y-m-d H:i:s')))->save();
			if($res){
				$this->success('单据作废成功');
			}else{
				$this->error('单据作废失败');
			}
		}else{
			$this->error('单据作废失败');
		}
    }	 
	
    /**
     * 订单日志
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-08-14
     */
    function orderLog($oid, $msg) {
        //订单日志记录
        $ary_orders_log = array(
            'o_id' => $oid,
            'ol_behavior' => $msg,
            'ol_uname' => "管理员：" . $_SESSION['admin_name'],
            'ol_create' => date('Y-m-d H:i:s')
        );
        $obj_res_orders_log = D('OrdersLog')->add($ary_orders_log);
        if (!$obj_res_orders_log) {
            return false;
        } else {
            return true;
        }
    }
    
	/**
	 * 附件下载
	 * @author czy<chenzongyao@guanyisoft.com>
	 * @date 2013-08-14
	 */
	public function packageDownload() {
		$ary_get = $this->_get();
		$ary_where = array('or_id'=>$ary_get['or_id'],'gs_id'=>$ary_get['gs_id']);
		$ary_data = D('RelatedRefundSpec')->where($ary_where)->limit(1)->find();
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){
			$content = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['content']);
		}else{
			$content = APP_PATH.$ary_data['content'];
		}
		import('ORG.Net.Http');
		Http::download(APP_PATH.$ary_data['content']);
	}

	/**
	 * 已付款等待发货订单编辑
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-08-29
	 */
	public function pageEditOk(){
		$this->getSubNav(4, 0, 10);
		$int_oid = trim($this->_get('o_id'));
		if (!isset($int_oid) || !is_numeric($int_oid)) {
			$this->error("参数订单ID不合法。");
		}
		//查询订单主表信息
		$ary_where = array('o_id' => $int_oid);
		$ary_orders = D('Orders')->where($ary_where)->find();
		//订单会员信息
		if (!empty($ary_orders['m_id']) && isset($ary_orders['m_id'])) {
			$ary_members = D('Members')->where(array('m_id' => $ary_orders['m_id']))->find();
		}
		//支付方式
		if (isset($ary_orders['o_payment'])) {
			$ary_payment = D('PaymentCfg')->field('pc_custom_name')->where(array('pc_id' => $ary_orders['o_payment']))->find();
			$ary_orders['payment_name'] = $ary_payment['pc_custom_name'];
		}
		//会员地址
		$ary_city = D('CityRegion')->getFullAddressId($ary_members['cr_id']);
		if (!empty($ary_city) && is_array($ary_city)) {
			//会员省
			$ary_province = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_orders['o_receiver_state']))->find();
			$ary_members['province'] = $ary_province['cr_name'];
			//会员市
			$ary_city = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_orders['o_receiver_city']))->find();
			$ary_members['city'] = $ary_city['cr_name'];
			//会员区
			$ary_area = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_orders['o_receiver_county']))->find();
			$ary_members['area'] = $ary_area['cr_name'];
		}
		//查询订单明细信息下
		$ary_orders_info = D('OrdersItems')->where($ary_where)->select();
		if(!empty($ary_orders_info) ){
			foreach ($ary_orders_info as $k => $v) {
				//获取商品的规格，类型为2时，只是拼接销售属性的值
				$ary_orders_info[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id'], 2);
				$ary_orders_info[$k]['pdt_infos'] = D("GoodsProducts")->where(array('pdt_id'=>$v['pdt_id']))->field('pdt_id,pdt_stock,pdt_sale_price')->find();
				//如果是团购商品
				if($v['oi_type'] == '5'){
					$ary_goods_pic = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('gp_picture as g_picture')->find();
				}else{
					$ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();
				}
				$ary_orders_info[$k]['g_picture'] = getFullPictureWebPath($ary_goods_pic['g_picture']);
				//订单商品退款、退货状态
				$ary_orders_info[$k]['str_refund_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status', $v);
				//订单商品发货
				$ary_orders_info[$k]['str_ship_status'] = D('Orders')->getOrderItmesStauts('oi_ship_status', $v);
				//商品小计
				$ary_orders_info[$k]['subtotal'] = $v['oi_nums']*$v['oi_price'];
			}
		}
		//订单状态
		//付款状态
		$ary_orders['str_pay_status'] = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_orders['o_pay_status']);
		$ary_orders['str_status'] = D('Orders')->getOrderItmesStauts('o_status', $ary_orders['o_status']);
		//订单状态
		$ary_orders_status = D('Orders')->getOrdersStatus($ary_orders['o_id']);
		//退款
		$ary_orders['refund_status'] = $ary_orders_status['refund_status'];
		//退货
		$ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
		//发货
		$ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
		//配送方式
		$ary_logistic_where = array('lt_id' => $ary_orders['lt_id']);
		$ary_field = array('lc_name');
		$ary_logistic_info = D('logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
		$ary_orders['str_logistic'] = $ary_logistic_info[0]['lc_name'];
		//物流信息
		$ary_delivery = D('Orders')->ordersLogistic($int_oid);
		//处理作废		作废类型（1：用户不想要了;2：商品无货;3:重新下单;4:其他原因）
		switch ($ary_orders['cacel_type']){
			case 1:
				$ary_orders['cacel_title'] = '用户不想要了';
				break;
			case 2:
				$ary_orders['cacel_title'] = '商品无货';
				break;
			case 3:
				$ary_orders['cacel_title'] = '重新下单';
				break;
			case 4:
				$ary_orders['cacel_title'] = '其他原因';
				break;
			default:
				break;
		}
		if($ary_orders['admin_id']){
			$ary_orders['admin_name'] = D('Orders')->where(array('u_id'=>$ary_orders['admin_id']))->getField('u_name');
		}
		//获取最后一次支付方式
		//获取配送公司表
		$int_p_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_city']))->getField('cr_id');
		$int_cr_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_county'],'cr_parent_id'=>$int_p_id))->getField('cr_id');
		
		//获取会员收货地址（省市区行政区域ID）
		$int_cr_id = D("CityRegion")->getAvailableLogisticsList($ary_orders['o_receiver_state'], $ary_orders['to_receiver_city'], $ary_orders['o_receiver_county']);
		$array_region = D("CityRegion")->getCityRegionInfoByLastCrId($int_cr_id);
		$this->assign('region', $array_region);
		$this->assign('members', $ary_members);
		$this->assign('ary_delivery', $ary_delivery);
		//处理订单
		//freegoods 自由推荐ID
		$ary_free_ids = D('OrdersItems')->where(array('oi_type'=>4,'o_id'=>$int_oid))->field('group_concat(oi_id) as oi_ids,fc_id')->group('fc_id')->select();
		if(!empty($ary_free_ids)){
			foreach($ary_free_ids as $ary_free_id){
				$ary_free_items = '';
				$ary_free_items['fc_id'] = $ary_free_id['fc_id'];
				$ary_free_id = explode(',',$ary_free_id['oi_ids']);
				foreach($ary_free_id as $fid){
					foreach($ary_orders_info as $key=>$item){
						if($item['oi_id'] == $fid){
							$ary_free_items['items'][] = $ary_orders_info[$key];
							unset($ary_orders_info[$key]);
						}
					}
				}
				$ary_orders_info['free_item'][] = $ary_free_items;
				unset($ary_free_items);
			}
		}
		//组合ID
		$ary_group_ids = D('OrdersItems')->where(array('oi_type'=>3,'o_id'=>$int_oid))->field('group_concat(oi_id) as oi_ids,fc_id')->group('fc_id')->select();
		foreach($ary_group_ids as $ary_group_id){
			$ary_combo_items = '';
			$ary_combo_items['fc_id'] = $ary_group_id['fc_id'];
			$ary_group_id = explode(',',$ary_group_id['oi_ids']);
			foreach($ary_group_id as $gid){
				foreach($ary_orders_info as $key=>$item){
					if($item['oi_id'] == $fid){
						$ary_combo_items['items'][] = $ary_orders_info[$key];
						unset($ary_orders_info[$key]);
					}
				}
			}
			$ary_orders_info['group_item'][] = $ary_combo_items;
			unset($ary_combo_items);
		}
        foreach ($ary_orders_info as $oi_value){
            $ary_cart[$oi_value['pdt_id']] = array('pdt_id'=>$oi_value['pdt_id'],'num'=>$oi_value['oi_nums'],'type'=>$oi_value['oi_type'],'g_id'=>$oi_value['g_id']);
        }
        //获取配送公司表
		$int_parent_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_city']))->getField('cr_id');
		$int_cr_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_county'],'cr_parent_id'=>$int_parent_id))->getField('cr_id');
		$int_cr_id = (int)$int_cr_id >0 ? $int_cr_id : $int_parent_id;
		$ary_logistic = D('Logistic')->getLogistic($int_cr_id,$ary_cart,$ary_orders['m_id']);
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_orders ['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
        if($ary_orders['o_receiver_mobile'] && strpos($ary_orders['o_receiver_mobile'],':')){
            $ary_orders['o_receiver_mobile'] = vagueMobile(decrypt($ary_orders['o_receiver_mobile']));
        }
        $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
        if(preg_match($RegExp,$ary_orders['o_receiver_mobile'])){
            $ary_orders['o_receiver_mobile'] = vagueMobile($ary_orders['o_receiver_mobile']);
        }
        if($ary_orders['o_receiver_telphone'] && strpos($ary_orders['o_receiver_telphone'],':')){
            $ary_orders['o_receiver_telphone'] = decrypt($ary_orders['o_receiver_telphone']);
        }
		//配送公司
		$this->assign('ary_logistic', $ary_logistic);
		//发票信息
		$ary_invoices = D('Invoice')->get();
		$ary_invoice_type = explode(",",$ary_invoices['invoice_type']);
		$ary_invoice_head = explode(",",$ary_invoices['invoice_head']);
		$ary_invoice_content = explode(",",$ary_invoices['invoice_content']);
		$ary_invoice_info = array();
		$ary_invoice_info['invoice_comom']=$ary_invoice_type[0];
		$ary_invoice_info['invoice_special']=$ary_invoice_type[1];
		$ary_invoice_info['invoice_personal']=$ary_invoice_head[0];
		$ary_invoice_info['invoice_unit']=$ary_invoice_head[1];
		$ary_invoice_info['is_invoice']=$ary_invoices['is_invoice'];
		$ary_invoice_info['is_auto_verify']=$ary_invoices['is_auto_verify'];
		//发票收藏列表
		$ary_invoice_list = D('InvoiceCollect')->get($ary_orders['m_id']);
		//发票信息
		$this->assign('invoice_info', $ary_invoice_info);
		$this->assign('invoice_content', $ary_invoice_content);
		//发票收藏列表
		$this->assign('invoice_list', $ary_invoice_list);
		$this->assign('ary_orders_info', $ary_orders_info);
		$this->assign('ary_orders', $ary_orders);
		$this->display();
	}

	/**
	 * 已付款等待发货订单编辑
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-08-29
	 */
	public function pageEdit(){
		$this->getSubNav(4, 0, 10);
		$int_oid = trim($this->_get('o_id'));
		if (!isset($int_oid) || !is_numeric($int_oid)) {
			$this->error("参数订单ID不合法。");
		}
		$ary_where = array('o_id' => $int_oid);
		//订单主表信息
		$ary_orders = D('Orders')->where($ary_where)->find();
        $ary_orders['o_receiver_mobile'] = decrypt($ary_orders['o_receiver_mobile']);
        $ary_orders['o_receiver_telphone'] = decrypt($ary_orders['o_receiver_telphone']);
        $ary_orders['o_receiver_idcard'] = decrypt($ary_orders['o_receiver_idcard']);
        /*echo'<pre>';print_r($ary_orders);die;*/
		//订单会员信息
		if (!empty($ary_orders['m_id']) && isset($ary_orders['m_id'])) {
			$ary_members = D('Members')->where(array('m_id' => $ary_orders['m_id']))->find();
		}
		//支付方式
		if (isset($ary_orders['o_payment'])) {
			$ary_payment = D('PaymentCfg')->field('pc_custom_name')->where(array('pc_id' => $ary_orders['o_payment']))->find();
			$ary_orders['payment_name'] = $ary_payment['pc_custom_name'];
		}
		//订单明细
		$ary_orders_info = D('OrdersItems')->where($ary_where)->select();
		if(!empty($ary_orders_info) ){
			foreach ($ary_orders_info as $k => $v) {
				//获取商品的规格，类型为2时，只是拼接销售属性的值
				$ary_orders_info[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id'], 2);
				//$ary_orders_info[$k]['pdt_specs'] = D("GoodsSpec")->getGoodsSpec($v['g_id'], 2);
				$ary_orders_info[$k]['pdt_infos'] = D("GoodsProducts")->where(array('pdt_id'=>$v['pdt_id']))->field('pdt_id,pdt_stock,pdt_sale_price')->find();
				if($v['oi_type'] == '5'){
					$ary_goods_pic = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('gp_picture as g_picture')->find();
				}else{
					$ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();
				}
				$ary_orders_info[$k]['g_picture'] = getFullPictureWebPath($ary_goods_pic['g_picture']);
				//订单商品退款、退货状态
				$ary_orders_info[$k]['str_refund_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status', $v);
				//订单商品发货
				$ary_orders_info[$k]['str_ship_status'] = D('Orders')->getOrderItmesStauts('oi_ship_status', $v);
				//商品小计
				$ary_orders_info[$k]['subtotal'] = $v['oi_nums']*$v['oi_price'];
			}
		}
		//订单状态
		//付款状态
		$ary_orders['str_pay_status'] = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_orders['o_pay_status']);
		$ary_orders['str_status'] = D('Orders')->getOrderItmesStauts('o_status', $ary_orders['o_status']);
		//订单状态
		$ary_orders_status = D('Orders')->getOrdersStatus($ary_orders['o_id']);
		//退款
		$ary_orders['refund_status'] = $ary_orders_status['refund_status'];
		//退货
		$ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
		//发货
		$ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
		//配送方式
		$ary_logistic_where = array('lt_id' => $ary_orders['lt_id']);
		$ary_field = array('lc_name');
		$ary_logistic_info =  D('Logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
		$ary_orders['str_logistic'] = $ary_logistic_info[0]['lc_name'];
		//物流信息
		$ary_delivery = D('Orders')->ordersLogistic($int_oid);
        
		//处理作废		作废类型（1：用户不想要了;2：商品无货;3:重新下单;4:其他原因）
		switch ($ary_orders['cacel_type']){
			case 1:
				$ary_orders['cacel_title'] = '用户不想要了';
				break;
			case 2:
				$ary_orders['cacel_title'] = '商品无货';
				break;
			case 3:
				$ary_orders['cacel_title'] = '重新下单';
				break;
			case 4:
				$ary_orders['cacel_title'] = '其他原因';
				break;
			default:
				break;
		}
		if($ary_orders['admin_id']){
			$ary_orders['admin_name'] = D('Orders')->where(array('u_id'=>$ary_orders['admin_id']))->getField('u_name');
		}
		//获取支付方式
		$payment_cfg = D('PaymentCfg')->getPayCfg(2);
		//获取最后一次支付方式
		//支付方式
		$this->assign('ary_paymentcfg', $payment_cfg);
		//获取会员收货地址（省市区行政区域ID）
		//$cr_id = D("CityRegion")->where(array('cr_name'=>$ary_orders['o_receiver_county']，))->getField('cr_id');
		$int_cr_id = D("CityRegion")->getAvailableLogisticsList($ary_orders['o_receiver_state'], $ary_orders['o_receiver_city'], $ary_orders['o_receiver_county']);
		$ary_region = D("CityRegion")->getCityRegionInfoByLastCrId($int_cr_id);
		$this->assign('region', $ary_region);
		$this->assign('members', $ary_members);
		$this->assign('ary_delivery', $ary_delivery);
		//处理订单
		//freegoods 自由推荐ID
		$ary_free_ids = D('OrdersItems')->where(array('oi_type'=>4,'o_id'=>$int_oid))->field('group_concat(oi_id) as oi_ids,fc_id')->group('fc_id')->select();
		if(!empty($ary_free_ids)){
			foreach($ary_free_ids as $ary_free_id){
				$ary_free_items = '';
				$ary_free_items['fc_id'] = $ary_free_id['fc_id'];
				$ary_free_id = explode(',',$ary_free_id['oi_ids']);
				foreach($ary_free_id as $int_fid){
					foreach($ary_orders_info as $key=>$item){
						if($item['oi_id'] == $int_fid){
							$ary_free_items['items'][] = $ary_orders_info[$key];
							unset($ary_orders_info[$key]);
						}
					}
				}
				$ary_orders_info['free_item'][] = $ary_free_items;
				unset($ary_free_items);
			}
		}
		//组合ID
		$ary_group_ids = D('OrdersItems')->where(array('oi_type'=>3,'o_id'=>$int_oid))->field('group_concat(oi_id) as oi_ids,fc_id')->group('fc_id')->select();
		foreach($ary_group_ids as $ary_group_id){
			$ary_combo_items = '';
			$ary_combo_items['fc_id'] = $ary_group_id['fc_id'];
			$ary_group_id = explode(',',$ary_group_id['oi_ids']);
			foreach($ary_group_id as $int_gid){
				foreach($ary_orders_info as $key=>$item){
					if($item['oi_id'] == $int_gid){
						$ary_combo_items['items'][] = $ary_orders_info[$key];
						unset($ary_orders_info[$key]);
					}
				}
			}
			$ary_orders_info['group_item'][] = $ary_combo_items;
			unset($ary_combo_items);
		}
        foreach ($ary_orders_info as $oi_value){
            $ary_cart[$oi_value['pdt_id']] = array('pdt_id'=>$oi_value['pdt_id'],'num'=>$oi_value['oi_nums'],'type'=>$oi_value['oi_type'],'g_id'=>$oi_value['g_id']);
        }
		$pro_datas = D('Promotion')->calShopCartPro($ary_orders['m_id'], $ary_cart);
        $subtotal = $pro_datas['subtotal']; //促销金额
        // 满足满包邮条件
        foreach ($pro_datas as $pro_data) {
            if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                foreach($pro_data['products'] as $proDatK=>$proDatV){
                    unset($ary_cart[$proDatK]);
                }
            }
        }
        if(empty($ary_cart)){
            $ary_cart = array('pdt_id'=>'MBAOYOU');
        }		
        //获取配送公司表
		$int_parent_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_city']))->getField('cr_id');
		$int_cr_id = D('CityRegion')->where(array('cr_name' => $ary_orders['o_receiver_county'],'cr_parent_id'=>$int_parent_id))->getField('cr_id');
		$int_cr_id = (int)$int_cr_id >0 ? $int_cr_id : $int_parent_id;
		$ary_logistic = D('Logistic')->getLogistic($int_cr_id,$ary_cart,$ary_orders['m_id'],1);
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_orders ['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
		//配送公司
		$this->assign('ary_logistic', $ary_logistic);
		//发票信息
		$ary_invoices = D('Invoice')->get();
		$ary_invoice_type = explode(",",$ary_invoices['invoice_type']);
		$ary_invoice_head = explode(",",$ary_invoices['invoice_head']);
		$ary_invoice_content = explode(",",$ary_invoices['invoice_content']);
		//发票信息
		$ary_invoice_info = array();
		$ary_invoice_info['invoice_comom']=$ary_invoice_type[0];
		$ary_invoice_info['invoice_special']=$ary_invoice_type[1];
		$ary_invoice_info['invoice_personal']=$ary_invoice_head[0];
		$ary_invoice_info['invoice_unit']=$ary_invoice_head[1];
		$ary_invoice_info['is_invoice']=$ary_invoices['is_invoice'];
		$ary_invoice_info['is_auto_verify']=$ary_invoices['is_auto_verify'];
		//发票收藏列表
		$ary_invoice_list = D('InvoiceCollect')->get($ary_orders['m_id']);
		$this->assign('invoice_info', $ary_invoice_info);
		$this->assign('invoice_content', $ary_invoice_content);
		//发票收藏列表
		$this->assign('invoice_list', $ary_invoice_list);
		$this->assign('ary_orders_info', $ary_orders_info);
		$this->assign('ary_orders', $ary_orders);
		$this->display();
	}
	
    /**
     * 处理订单编辑
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-03
     */
    public function doEdit(){
		$ary_order_data = $this->_post();
		$int_oid = trim($ary_order_data['o_id']);
		if(empty($ary_order_data['o_id'])){
			$this->error('此订单号不存在');
			exit;
		}
		//判断订单后是否为未付款
		$ary_where = array('o_id' => $int_oid, 'o_pay_status' => 1, 'o_status' => 1);
		$orders_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
		$ary_orders = $orders_info->where($ary_where)->find();
		if (count($ary_orders) > 0 && !empty($ary_orders)) {
			$this->error('此订单已不能编辑', array('确定' => U('Admin/Orders/pageList')));
			exit;
		}
		//配送方式不能为空
		if(empty($ary_order_data['lt_id'])){
			$this->error('请先选择配送方式');
			exit;
		}
		//更新订单表出商品和金额信息
		$ary_where = array();
		$ary_where['o_id'] = $ary_order_data['o_id'];
		//清空数据为空的数据
		foreach($ary_order_data as $key=>$order){
			if(empty($order)){
				unset($ary_order_data[$key]);
			}
		}
		//支付手续费
		$ary_order_data['o_cost_payment'] = M('payment_cfg', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pc_id'=>$ary_order_data['o_payment']))->getField('pc_fee');
		if(empty($ary_order_data['o_cost_payment']) && ($ary_order_data['o_cost_payment'] !='0.000')){
			$this->error('支付方式不存在');
			exit;
		}
		//收货人省份
		$ary_order_data['o_receiver_state'] = D('CityRegion')->getAddressName($ary_order_data['province']);
		//收货人城市
		$ary_order_data['o_receiver_city'] = D('CityRegion')->getAddressName($ary_order_data['city']);
		//收货人地区
		//$ary_order_data['o_receiver_county'] = D('CityRegion')->getAddressName($ary_order_data['region1']);
		if(empty($ary_order_data['o_receiver_city']) || /* empty($ary_order_data['o_receiver_county']) || */ empty($ary_order_data['o_receiver_state'])){
			$this->error('省市区信息为空，请验证是否存在');
			exit;
		}
		if(empty($ary_order_data['lt_id'])){
			$this->error(L('SELECT_LOGISTIC'));
			exit;
		}
		$ary_gids = $ary_order_data['pro_g_id'];
		foreach($ary_gids as $int_gid){
			$is_authorize = D('AuthorizeLine')->isAuthorize($ary_order_data['m_id'], $int_gid);
			if(empty($is_authorize)){
				$this->error('部分商品已不允许此会员购买');
				exit;
			}
		}
		$orders = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$orders->startTrans();
		if(!isset ( $ary_order_data ['invoice_type'] )){
			$ary_order_data ['invoice_type'] = 0;
		}
		if(!isset($ary_order_data ['is_invoice'])){
			$ary_order_data ['is_invoice'] = 0;
		}
		if($ary_order_data ['is_invoice'] == 0){
			$ary_order_data ['invoice_head'] = '';
			$ary_order_data ['invoice_people'] = '';
			$ary_order_data ['invoice_name'] = '';
			$ary_order_data ['invoice_content'] = '';
			// 个人姓名
			$ary_order_data ['invoice_people'] = '';
			// 纳税人识别号
			$ary_order_data ['invoice_identification_number'] = '';
			// 注册地址
			$ary_order_data ['invoice_address'] = '';
			// 注册电话
			$ary_order_data ['invoice_phone'] = '';
			// 开户银行
			$ary_order_data ['invoice_bank'] = '';
			// 银行帐户
			$ary_order_data ['invoice_account'] = '';
		}else{
			if (isset ( $ary_order_data ['invoice_type'] ) && isset ( $ary_order_data ['invoice_head'] ) ) {
				$ary_order_data ['is_invoice'] = 1;
				if ($ary_order_data ['invoice_type'] == 2) {
					// 如果为增值税发票，发票抬头默认为单位
					$ary_order_data ['invoice_head'] = 2;
				} else {
					if ($ary_order_data ['invoice_head'] == 2) {
						// 如果发票类型为普通发票，并且发票抬头为单位，将个人姓名删除
						$ary_order_data ['invoice_people'] = '';
					}
					if ($ary_order_data ['invoice_head'] == 1) {
						// 如果发票类型为普通发票，并且发票抬头为个人，将单位删除
						$ary_order_data ['invoice_name'] = '';
					}
				}
				if (empty ( $ary_order_data ['invoice_name1'] )) {
					$ary_order_data ['invoice_name'] = '个人';
				} else {
					$ary_order_data ['invoice_name'] = $ary_order_data ['invoice_name1'];
				}
			}
			if (isset ( $ary_order_data ['invoice_content1'] )) {
				$ary_order_data ['invoice_content'] = $ary_order_data ['invoice_content1'];
			}
		}
		$m_id = $ary_order_data['m_id'];
		$price = new PriceModel($m_id);
		//修改的商品数组
		$ary_cart = array();
		foreach($ary_order_data['pro_pdt_id'] as $key=>$pdt_id){
			$ary_cart[] = array(
					'pdt_id' => $pdt_id,
					'g_id' => $ary_order_data['pro_g_id'][$key],
					'type' => $ary_order_data['pro_type'][$key],
					'pdt_price' => $ary_order_data['pro_price'][$key],
					'num' => $ary_order_data['pro_num'][$key]		
			);
		}
		$order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
		//删除赠品
		$order_item_obj->where(array('o_id'=>$ary_order_data['o_id'],'oi_type'=>2))->delete();
		//查询商品是否存在
		foreach($ary_cart as $k=>$v ){
			$v_result = $order_item_obj->where(array('oi_type'=>0,'pdt_id'=>$v['pdt_id'],'o_id'=>$ary_order_data['o_id']))->count();
			if(!empty($v_result)){
				$ary_update_data = array();
				$ary_update_data['oi_update_time'] = date("Y-m-d H:i:s");
				$ary_update_data['oi_nums'] = $v['num'];
				if(!empty($v['pdt_price'])){
					$ary_update_data['oi_price'] = $v['pdt_price'];
				}else{
					$ary_update_data['oi_price'] = $price->getItemPrice($v['pdt_id']);
				}
				$res = $order_item_obj->where(array('oi_type'=>0,'pdt_id'=>$v['pdt_id'],'o_id'=>$ary_order_data['o_id']))->data($ary_update_data)->save();				if(!$res){
					$orders->rollback();
					$this->error('订单修改失败');
					exit;
				}
			}else{
				//订单明细新增
				$ary_update_data = array();
				$item_info = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
				->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
				->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
				->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
				->where(array('pdt_id'=>$v['pdt_id']))->find();
				//订单id
				$ary_update_data['o_id'] = $ary_order_data['o_id'];
				//商品id
				$ary_update_data['g_id'] = $v['g_id'];
				//货品id
				$ary_update_data['pdt_id'] = $v['pdt_id'];
				//类型id
				$ary_update_data['gt_id'] = $item_info['gt_id'];
				//商品sn
				$ary_update_data['g_sn'] = $item_info['g_sn'];
				//货品sn
				$ary_update_data['pdt_sn'] = $item_info['pdt_sn'];
				//商品名字
				$ary_update_data['oi_g_name'] = $item_info['g_name'];
				//成本价
				$ary_update_data['oi_cost_price'] = $item_info['pdt_cost_price'];
				//货品销售原价
				$ary_update_data['pdt_sale_price'] = $item_info['pdt_sale_price'];
				//购买单价
				$ary_update_data['oi_update_time'] = date("Y-m-d H:i:s");
				$ary_update_data['oi_nums'] = $v['num'];

				if(!empty($v['pdt_price'])){
					$ary_update_data['oi_price'] = $v['pdt_price'];
				}else{
					$ary_update_data['oi_price'] = $price->getItemPrice($v['pdt_id']);
				}
				$res = $order_item_obj->data($ary_update_data)->add();
				if(!$res){
					$orders->rollback();
					$this->error('订单明细新增失败');
					exit;
				}
			}
		}
		$order_items = $order_item_obj->where(array('o_id'=>$ary_order_data['o_id']))->select();
		if(empty($order_items)){
			$orders->rollback();
			$this->error('订单明细不存在');
			exit;
		}
		foreach($order_items as $v){
			$item_price = 0;
			if($v['oi_type'] == '0'){
				$oi_price = $price->getItemPrice($v['pdt_id']);
				if($oi_price != $v['oi_price']){
					$item_price = $v['oi_price'];
				}
			}
			$ary_pdts[] = array(
				'pdt_id'=>$v['pdt_id'],
				'g_id'=>$v['g_id'],
				'num'=>$v['oi_nums'],
				'type'=>$v['oi_type'],
				'fc_id'=>$v['fc_id'],
				'oi_price'=>$item_price
			);
		}
		//订单处理
		$ary_new_pdts = array();
		foreach($ary_pdts as $p) {
			if($p['type'] == 0) {
				$ary_new_pdts[$p['pdt_id']] = $p;
			} else if($p['type'] == 1) {
				$ary_new_pdts[$p['pdt_id']] = $p;
			} else if($p['type'] == 2) {
				$ary_new_pdts['gifts'][$p['pdt_id']] = $p;
			} else if($p['type'] == 3) {
				//组合商品不予处理
			} else if($p['type'] == 4) {
				$ary_new_pdts['free'.$p['fc_id']]['pdt_id'][$p['pdt_id']] = $p['pdt_id'];
				$ary_new_pdts['free'.$p['fc_id']]['num'][$p['pdt_id']] = $p['num'];
				$ary_new_pdts['free'.$p['fc_id']]['oi_price'][$p['pdt_id']] = $p['oi_price'];
				if(!isset($ary_new_pdts['free'.$p['fc_id']]['fc_id'])) {
					$ary_new_pdts['free'.$p['fc_id']]['fc_id'] = $p['fc_id'];
					$ary_new_pdts['free'.$p['fc_id']]['type'] = 4;
				}
			}
		}
		//整单促销规则
		//订单优惠金额（购物车优惠金额）
		$promotion_price = 0;
		//商品优惠金额
		$o_goods_discount = 0;
		//商品总金额（计算促销）
		$goods_price = 0;
		//商品总金额（不计算促销）
		$goods_saleprice = 0;
		$order_price = 0;
		//团购商品
		//获取团购价与商品原价
		$price_info = new ProPrice();
		if($order_items[0]['oi_type'] == '5'){
			$tuan_price = $price_info->getPriceInfo($order_items[0]['pdt_id'], $ary_order_data['m_id'], 5, $ary_extra=array('gp_id'=>$order_items[0]['fc_id']));
			//分别是购物车优惠金额、商品总销售价、商品折扣金额
			$ary_order_data['o_goods_all_saleprice'] = $order_items[0]['oi_nums']*$tuan_price['pdt_sale_price'];
			$ary_order_data['o_goods_discount'] = $order_items[0]['oi_nums']*$tuan_price['thrift_price'];
			//商品总价
			$ary_order_data['o_goods_all_price'] = $order_items[0]['oi_nums']*$tuan_price['pdt_price'];
			$goods_price = $ary_order_data['o_goods_all_price'];
			
			//订单商品总价（销售价格带促销）
			$ary_order_data ['o_goods_all_price'] = $order_items[0]['oi_nums']*$tuan_price['pdt_price'];
			//商品销售总价
			$ary_order_data ['o_goods_all_saleprice'] =  sprintf("%0.2f", $order_items[0]['oi_nums']*$tuan_price['pdt_sale_price']);
			$ary_order_data ['o_discount'] = sprintf("%0.2f",  $order_items[0]['oi_nums']*$tuan_price['thrift_price']);
			$ary_order_data['o_goods_discount'] = $ary_order_data ['o_discount'];
			$ary_order_data['promotion'] = serialize($pro_datas);
		}else{
			//获取促销信息
			$promotion_data = D('Promotion')->calShopCartPro($ary_order_data['m_id'], $ary_new_pdts);
			foreach($promotion_data as $key=>$promotion){
				if($key === "subtotal"){
					$goods_price = $promotion['goods_total_price'];
					$goods_saleprice = $promotion['goods_total_sale_price'];
					$o_goods_discount = $promotion['goods_all_discount'];
				}else{
					if($key != '0'){
						$promotion_price +=$promotion['goods_all_discount'];
						//享受赠品
						if(!empty($promotion['gifts'])){
							foreach($promotion['gifts'] as $gift){
								//订单明细新增（赠品）
								$ary_update_data = array();
								$pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gift['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
								$item_info = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
								->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
								->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
								->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
								->where(array('pdt_id'=>$pdt_id['pdt_id']))->find();
								//订单id
								$ary_update_data['o_id'] = $ary_order_data['o_id'];
								//商品id
								$ary_update_data['g_id'] = $gift['g_id'];
								//货品id
								$ary_update_data['pdt_id'] = $gift['pdt_id'];
								//类型id
								$ary_update_data['gt_id'] = $item_info['gt_id'];
								//商品sn
								$ary_update_data['g_sn'] = $item_info['g_sn'];
								//货品sn
								$ary_update_data['pdt_sn'] = $item_info['pdt_sn'];
								//商品名字
								$ary_update_data['oi_g_name'] = $item_info['g_name'];
								//成本价
								$ary_update_data['oi_cost_price'] = $item_info['pdt_cost_price'];
								//货品销售原价
								$ary_update_data['pdt_sale_price'] = $item_info['pdt_sale_price'];
								//购买单价
								$ary_update_data['oi_update_time'] = date("Y-m-d H:i:s");
								$ary_update_data['oi_nums'] = $gift['num'];
								$ary_update_data['oi_price'] = 0;
								$ary_update_data['oi_type'] = 2;
								$ary_update_data['fc_id'] = intval($gift['pmn_id']);
								$ary_update_data['promotion'] = '赠品';
								$res = $order_item_obj->data($ary_update_data)->add();
								if(!$res){
									$orders->rollback();
									$this->error('订单明细新增失败');
									exit;
								}
							}
						}
					}
					//更新促销信息
					$pmn_name = $promotion['pmn_name'];
					foreach($promotion['products'] as $prod){
						//$g_id = $prod['g_id'];
						$pdt_id = $prod['pdt_id'];
						$oi_type = $prod['type'];
						$fc_id	 = $prod['fc_id'];
						if(!empty($prod['oi_price'])){
							$pdt_price = $prod['oi_price'];
						}else{
							$pdt_price = $prod['pdt_price'];
						}
						$promotion = $pmn_name.' '.$prod['pmn_name'];
						//if(!empty($promotion)){
						$order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
						$result = $order_item_obj
						->where(array('oi_type'=>$oi_type,'fc_id'=>$fc_id,'pdt_id'=>$pdt_id,'o_id'=>$ary_order_data['o_id']))
						->data(array('promotion'=>$promotion,'oi_price'=>$pdt_price,'oi_update_time'=>date('Y-m-d H:i:s')))
						->save();
						//}
					}
				
				}
				
			}
			$pro_datas = $promotion_data;
			$subtotal = $pro_datas ['subtotal'];
			unset ( $pro_datas ['subtotal'] );
			//物流费用重新计算
			$str_logistic_price = D('Logistic')->getLogisticPrice($ary_order_data['lt_id'],$ary_new_pdts,$ary_order_data['m_id'],1);
			// 满足满包邮条件
			$is_by = 0;
			foreach ( $pro_datas as $pro_data ) {
				if ($pro_data ['pmn_class'] == 'MBAOYOU') {
					$str_logistic_price = 0;
					//dump($pro_data ['pmn_class']);die();
					$is_by = 1;
				}
			}
			$ary_order_data['o_cost_freight'] = $str_logistic_price;
			//邮费差价
			if($ary_order_data ['o_cost_freight'] != $ary_order_data['old_cost_freight']){
				$ary_order_data['o_diff_freight'] = $ary_order_data['old_cost_freight']-$ary_order_data ['o_cost_freight'];				
				$ary_order_data ['o_cost_freight'] = $ary_order_data['old_cost_freight'];				
				if($is_by != '1'){
					$ary_order_data ['o_cost_freight'] = $ary_order_data['old_cost_freight'];
				}			
			}
            $promotion_total_price = '0';
            $promotion_price = '0';
			foreach($pro_datas as $keys=>$vals){
				foreach($vals['products'] as $key=>$val){
					$arr_products = D ( 'Cart' )->getProductInfo(array($key=>$val));
					if($arr_products[0][0]['type'] == '4'){
						foreach($arr_products[0] as &$provals){
							$provals['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $provals['g_id']);
						}
					}
					$pro_datas[$keys]['products'][$key] =  $arr_products[0];
					$pro_data[$key] = $val;
					$pro_data[$key]['pmn_name'] = $vals['pmn_name'];
				}
			    $promotion_total_price += $vals['goods_total_price'];     //商品总价
                if($keys != '0'){
                    $promotion_price += $vals['pro_goods_discount'];
                }
			}
			
			//订单商品总价（销售价格带促销）
			$ary_order_data ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
			//商品销售总价
			$ary_order_data ['o_goods_all_saleprice'] =  sprintf("%0.2f", $promotion_total_price);
			$ary_order_data ['o_discount'] = sprintf("%0.2f", $promotion_price);
			$ary_order_data['o_goods_discount'] = sprintf ( "%0.2f", $subtotal ['goods_total_sale_price'] - $promotion_total_price );
			$ary_order_data['promotion'] = serialize($pro_datas);
			
		}
		//判断订单后是否为未付款
		$ary_where = array('o_id' => $int_oid);
		$order = $orders_info->where($ary_where)->field('o_coupon_menoy,o_discount')->find();
		//订单总价 商品会员折扣价-优惠券金额
		$all_price = $ary_order_data ['o_goods_all_price'] - $order['o_coupon_menoy'];
		if($all_price<=0){
			$all_price=0;
		}
		//订单应付总价 订单总价+运费{
		$all_price  += $ary_order_data['o_cost_freight']+$ary_order_data['o_cost_payment'];
		$ary_order_data['o_all_price'] = sprintf("%0.3f", $all_price);
		$ary_order_data['o_update_time'] = date('Y-m-d H:i:s');
        if($ary_order_data['o_receiver_mobile'] && strpos($ary_order_data['o_receiver_mobile'],'*')){
            unset($ary_order_data['o_receiver_mobile']);
        }else{
            $ary_order_data['o_receiver_mobile'] = encrypt($ary_order_data['o_receiver_mobile']);
        }
        if($ary_order_data['o_receiver_telphone']){
            $ary_order_data['o_receiver_telphone'] = encrypt($ary_order_data['o_receiver_telphone']);
        }
		$obj_orders_res = $orders_info->where(array('o_id'=>$ary_order_data['o_id']))->data($ary_order_data)->save();
		if(!$obj_orders_res){
			$orders->rollback();
			$this->error('订单修改失败');
			exit;
		}
		//更新日志表
		$ary_orders_log = array(
            'o_id'=>$int_oid,
            'ol_behavior' => '卖家订单编辑:',
			'ol_text'=>serialize($ary_order_data),
			//'ol_desc'=>file_get_contents($_SERVER['HTTP_REFERER'])
			'ol_desc'=>$ary_order_data['edit_html']
		);
		$res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
		$orders->commit();
		$this->success('订单修改成功',U('Admin/Orders/pageList'));

	}
	
	/**
	 * 计算价格
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-09-13
	 */
	public function computePrice(){

		$ary_order_data = $this->_post();		
        $order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
		$ary_pdts = array();
		//修改的商品数组
		$ary_cart = array();
		foreach($ary_order_data['pro_pdt_id'] as $key=>$pdt_id){
			$ary_pdts[] = array(
					'pdt_id' => $pdt_id,
					'g_id' => $ary_order_data['pro_g_id'][$key],
					'type' => $ary_order_data['pro_type'][$key],
					'oi_price' => $ary_order_data['pro_price'][$key],
					'num' => $ary_order_data['pro_num'][$key],
					'is_new'=>intval($ary_order_data['is_new'][$key])
			);
		};
		$order_items = $order_item_obj->where(array('o_id'=>$ary_order_data['o_id'],'oi_type'=>array('neq',0)))->select();
		//除了普通商品之外的订单
		foreach($order_items as $v){
			$ary_pdts[] = array(
					'pdt_id'=>$v['pdt_id'],
					'num'=>$v['oi_nums'],
					'type'=>$v['oi_type'],
					'fc_id'=>$v['fc_id'],
					'oi_price'=>0
			);
		}
		//订单处理
		$ary_new_pdts = array();
        $i =0;
		foreach($ary_pdts as $p) {
			if($p['type'] == 0) {
				$ary_new_pdts[$p['pdt_id']] = $p;
			} else if($p['type'] == 1) {
				$ary_new_pdts[$p['pdt_id']] = $p;
			} else if($p['type'] == 2) {
				$ary_new_pdts['gifts'][$p['pdt_id']] = $p;
			} else if($p['type'] == 3) {
				//组合商品不予处理
			} else if($p['type'] == 4) {
				$ary_new_pdts['free'.$p['fc_id']]['pdt_id'][$i] = $p['pdt_id'];
				$ary_new_pdts['free'.$p['fc_id']]['num'][$i] = $p['num'];
				$ary_new_pdts['free'.$p['fc_id']]['oi_price'][$i] = $p['oi_price'];
				if(!isset($ary_new_pdts['free'.$p['fc_id']]['fc_id'])) {
					$ary_new_pdts['free'.$p['fc_id']]['fc_id'] = $p['fc_id'];
					$ary_new_pdts['free'.$p['fc_id']]['type'] = 4;
				}
                $i ++;
			}
		}
		unset($ary_new_pdts['gifts']);
		$str_logistic_price = D('Logistic')->getLogisticPrice($ary_order_data['lt_id'],$ary_new_pdts,$ary_order_data['m_id'],1);

		//获取订单促销信息
		$promotion_data = D('Promotion')->calShopCartPro($ary_order_data['m_id'], $ary_new_pdts);		//整单促销规则
		//订单优惠金额（购物车优惠金额）
		$promotion_price = 0;
		//商品优惠金额
		$o_goods_discount = 0;
		//商品总金额（计算促销）
		$goods_price = 0;
		//商品总金额（不计算促销）
		//享受促销信息
		$goods_saleprice = 0;
		$order_price = 0;
		$promotion_title = '';
		//返回订单数据
		$ary_data = array();
		$ary_promotion = array();
		$ary_data['promotion'] = '';
		foreach($promotion_data as $key=>$promotion){
			if($key != 'subtotal'){
				if($key != '0'){
					$promotion_price +=$promotion['goods_all_discount'];
// 					if(!empty($promotion['gifts'])){
// 						foreach($promotion['gifts'] as $gift){
// 							$pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gift['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
// 							$item_info = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
// 							->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
// 							->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
// 							->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
// 							->where(array('pdt_id'=>$pdt_id['pdt_id']))->find();
// 							$gift_data= array_merge($item_info,$gift);
// 							//$promotion1['gifts'][] = $gift_data;
// 							//$ary_data['promotion'][] = $promotion1;
// 						}
// 					}else{
// 						//$ary_data['promotion'][] = $promotion;
// 					}
				}
			}else{
				$goods_price = $promotion['goods_total_price'];
				$goods_saleprice = $promotion['goods_total_sale_price'];
				$o_goods_discount = $promotion['goods_all_discount'];
			}
		}
		//团购商品
		//获取团购价与商品原价
		$price_info = new ProPrice();
		if($order_items[0]['oi_type'] == '5'){
			$tuan_price = $price_info->getPriceInfo($order_items[0]['pdt_id'], $ary_order_data['m_id'], 5, $ary_extra=array('gp_id'=>$order_items[0]['fc_id']));
			//分别是购物车优惠金额、商品总销售价、商品折扣金额
			$ary_data['o_goods_all_saleprice'] = $order_items[0]['oi_nums']*$tuan_price['pdt_sale_price'];
			$ary_data['o_goods_discount'] = $order_items[0]['oi_nums']*$tuan_price['thrift_price'];
			//商品总价
			$ary_data['o_goods_all_price'] = $order_items[0]['oi_nums']*$tuan_price['pdt_price'];
			$goods_price = $ary_order_data['o_goods_all_price'];
		}else{
			$pro_datas = $promotion_data;
			$subtotal = $pro_datas ['subtotal'];
			unset ( $pro_datas ['subtotal'] );
			// 满足满包邮条件
			foreach ( $pro_datas as $pro_data ) {
				if ($pro_data ['pmn_class'] == 'MBAOYOU') {
					$str_logistic_price = 0;
				}
			}
			// 商品总价
			$ary_orders ['o_goods_all_price'] = 0;
			$m_id = $_SESSION ['Members'] ['m_id'];
            $promotion_total_price = '0';
            $promotion_price = '0';
            //赠品数组
            $cart_gifts = array();
            //拼接订单数据，用来获取订单促销信息
			foreach($pro_datas as $keys=>$vals){
				foreach($vals['products'] as $key=>$val){
					$arr_products = D ( 'Cart' )->getProductInfo(array($key=>$val));
					if($arr_products[0][0]['type'] == '4'){
						foreach($arr_products[0] as &$provals){
							$provals['authorize'] = D('AuthorizeLine')->isAuthorize($m_id, $provals['g_id']);
						}
					}
					$product_data = $arr_products[0];
					//手动输入价格按手动输入的计算
					if($product_data['type'] == '0'){
						if(!empty($val['oi_price'])){
							$product_data['oi_price'] = $val['oi_price'];
						}else{
							$product_data['oi_price'] = $val['pdt_price'];
						}
					}
					$pro_datas[$keys]['products'][$key] =  $product_data;
					$pro_datas[$keys]['products'][$key]['pmn_name'] = $vals['pmn_name'].' '.$val['pmn_name'];
					$pro_data[$key] = $val;
					$pro_data[$key]['pmn_name'] = $vals['pmn_name'].' '.$val['pmn_name'];
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
			//分别是购物车优惠金额、商品总销售价、商品折扣金额
			//订单商品总价（销售价格带促销）
			$ary_data ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
			//商品销售总价
			$ary_data ['o_goods_all_saleprice'] = sprintf ( "%0.2f", $promotion_total_price);
			$ary_data ['o_discount'] = sprintf("%0.2f", $promotion_price);
			$ary_data['o_goods_discount'] = sprintf ( "%0.2f", $subtotal ['goods_total_sale_price'] - $promotion_total_price );
		}
		if(empty($ary_data['o_goods_all_price'])){
			$ary_data['status'] = '0';
		}else{
			$ary_data['status'] = '1';
		}

        $ary_logistic = D('ViewLogistic')->where(array('lt_id'=>$ary_order_data['lt_id'],'lt_status'=>1))->select();
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_data['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $str_logistic_price = 0;
            }
        }
		//获取赠品信息
		if(!empty($cart_gifts)){
			$cart_gifts_data = array();
			$cart_gifts_data = D ( 'Cart' )->getProductInfo($cart_gifts);
		}
		$ary_orders_info = array();
		foreach($pro_datas as $vals){
			foreach($vals['products'] as $key=>$val){
				if($val[0]['type'] == '4'){
					$ary_orders_info['free_item'][] = array(
							'fc_id'=>$val[0]['type'],
							'items'=>$val
							);
				}else{
					$ary_orders_info[] = array_merge(array('pmn_name'=>$vals['pmn_name'].' '+$val['pmn_name']),$val);
				}
			}
		}
		if(!empty($cart_gifts_data)){
			foreach($cart_gifts_data as $gift_data){
				$ary_orders_info[] = $gift_data;
			}
		}
		// 数据处理
		foreach($ary_orders_info as &$order_info){
			if($order_info[0]['items'][0]['type'] == '4'){
				foreach($order_info as $key=>&$sub_order_info){
                    if(empty($sub_order_info['items']['pmn_name']) || !is_array($sub_order_info['items']['pmn_name'])){
                        unset($sub_order_info['items']['pmn_name']);
                    }
					foreach($sub_order_info['items'] as &$sub_info){
						$sub_info['oi_type'] =  $sub_info['type'];
						$sub_info['oi_g_name'] =  $sub_info['g_name'];
						$sub_info['oi_price'] =  $sub_info['f_price'];
						$sub_info['oi_nums'] =  $sub_info['pdt_nums'];
						$sub_info['subtotal'] =  $sub_info['pdt_momery'];
						$sub_info['promotion'] =  '自由推荐';
					}
				}
			}else{
				$order_info['oi_type'] =  $order_info['type'];
				$order_info['oi_g_name'] =  $order_info['g_name'];
				$order_info['oi_nums'] =  $order_info['pdt_nums'];
				if(!empty($order_info['oi_price'])){
					$order_info['oi_price'] =  $order_info['oi_price'];
					$order_info['subtotal'] =  $order_info['oi_price']*$order_info['pdt_nums'];
				}else{
					$order_info['oi_price'] =  $order_info['f_price'];
					$order_info['subtotal'] =  $order_info['pdt_momery'];
				}
				$order_info['promotion'] =  $order_info['pmn_name'].$prom['rule_info']['name'];
			}			
		}

		//优惠券金额
        $ary_data['o_coupon_menoy'] = D('Orders')->where(array('o_id'=>$ary_order_data['o_id']))->getField('o_coupon_menoy');
        $ary_data['o_goods_all_price'] -= $ary_data['o_coupon_menoy'];
		
		//print_r($ary_orders_info);die();
        //print_r($ary_order_data);die();
        if($ary_order_data['pageList'] == '1'){
            $array_return['ary_data'] = $ary_data;
            $array_return['ary_orders_info'] = $ary_orders_info;
            //print_r($array_return);die();
            $this->ajaxReturn($array_return);
        }
		//print_r($ary_data);die();
		$this->assign ( "logistic_price", $str_logistic_price );		
		$this->assign ( "ary_orders_info", $ary_orders_info );
		$this->assign ( "ary_data", $ary_data );
		$this->assign('ary_pdts',$ary_pdts);
		$this->display ('newOrdersList');
	}
	
    /**
     * 已付款订单编辑处理订单编辑
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-03
     */
    public function doEditOk() {
        $ary_order_data = $this->_post();
        $int_oid = trim($ary_order_data['o_id']);
        if (empty($ary_order_data['o_id'])) {
            $this->error('此订单号不存在');
            exit;
        }
        //判断订单后是否为未付款
        $ary_where = array('o_id' => $int_oid, 'o_pay_status' => array('neq', 1), 'o_status' => 1);
        $orders_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_orders = $orders_info->where($ary_where)->find();
        if (count($ary_orders) > 0 && !empty($ary_orders)) {
            $this->error('此订单已不能编辑', array('确定' => U('Admin/Orders/pageList')));
            exit;
        }
        //配送方式不能为空
        if (empty($ary_order_data['lt_id'])) {
            $this->error('请先选择配送方式');
            exit;
        }
        //更新订单表出商品和金额信息
        $ary_where = array();
        $ary_where['o_id'] = $ary_order_data['o_id'];

        //收货人省份
        $ary_order_data['o_receiver_state'] = D('CityRegion')->getAddressName($ary_order_data['province']);
        //收货人城市
        $ary_order_data['o_receiver_city'] = D('CityRegion')->getAddressName($ary_order_data['city']);
        //收货人地区
        //$ary_order_data['o_receiver_county'] = D('CityRegion')->getAddressName($ary_order_data['region1']);
        
		if(empty($ary_order_data['o_receiver_city']) /* || empty($ary_order_data['o_receiver_county']) */ || empty($ary_order_data['o_receiver_state'])){
			$this->error('省市区信息为空，请验证是否存在');
			exit;
		}
		//物流费用重新计算
        if (empty($ary_order_data['lt_id'])) {
            $this->error(L('SELECT_LOGISTIC'));
            exit;
        }
        if (empty($ary_order_data['o_diff_freight'])) {
            unset($ary_order_data['o_diff_freight']);
        }
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        $ary_order_data['o_update_time'] = date('Y-m-d H:i:s');
        $obj_orders_res = $orders->where(array('o_id' => $ary_order_data['o_id']))->data($ary_order_data)->save();
        if (!$obj_orders_res) {
            $orders->rollback();
            $this->error('订单修改失败');
            exit;
        }
        //更新日志表
        $ary_orders_log = array(
            'o_id' => $int_oid,
            'ol_behavior' => '卖家已付款订单编辑',
            'ol_text' => serialize($ary_order_data),
            //'ol_desc' => file_get_contents($_SERVER['HTTP_REFERER'])
        	'ol_desc'=>$ary_order_data['edit_html']
        );
        $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
        $orders->commit();
        $this->success('订单修改成功',U('Admin/Orders/pageList'));
    }
    
    /**
     * 记录订单日志
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-07-31
     * @param string $code 错误日志
     */
    function logs($code,$msg){
    	$log_dir = APP_PATH . 'Runtime/Orderslog/';
    	if(!file_exists($log_dir)){
    		mkdir($log_dir,0700);
    	}
    	$log_file = $log_dir . date('YmdHis') .$code . '.log';
    	$fp = fopen($log_file, 'w+');
    	fwrite($fp, $msg);
    	fclose($fp);
    	return $log_file;
    }

    /**
     * 使用优惠券
     * @author wangguibin
     * @date 2013-09-04
     */
    public function doCoupon() {
        $str_csn = $this->_post('csn');
        $o_id = $this->_post('o_id');
        if (isset($str_csn)) {
        	//查询订单商品信息
        	$order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
        	$order_data = $order_item_obj->where(array('o_id'=>$o_id,'oi_type'=>array('neq',2)))->field('g_id,g_sn')->select();
			if (isset($str_csn)) {
				$ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
				$date = date('Y-m-d H:i:s');
				if($ary_coupon['status'] == 'error'){
					$ary_res ['errMsg'] = $ary_coupon['msg'];
					$ary_res ['success'] = 0;
				} else {
					foreach ($ary_coupon['msg'] as $coupon){
						if ($coupon ['c_condition_money'] > 0 && $ary_res ['all_price'] < $coupon ['c_condition_money']) {
							$ary_res ['errMsg'] = "编号{$coupon['ci_sn']}优惠券不满足使用条件";
							$ary_res ['success'] = 0;
							break;
						} elseif ($coupon ['c_is_use'] == 1 || $coupon ['c_used_id'] != 0) {
							$ary_res ['errMsg'] = "编号{$coupon['ci_sn']}被使用";
							$ary_res ['success'] = 0;break;
						} elseif ($coupon ['c_start_time'] > $date) {
							$ary_res ['errMsg'] = "编号{$coupon['ci_sn']}不能使用";
							$ary_res ['success'] = 0;break;
						} elseif ($date > $coupon ['c_end_time']) {
							$ary_res ['errMsg'] = "编号{$coupon['ci_sn']}活动已经结束";
							$ary_res ['success'] = 0;break;
						} else {
							$ary_res ['sucMsg'] = '可以使用';
							$ary_res ['success'] = 1;
							if($coupon['c_type'] == '1'){
								if($coupon['gids'] == 'All'){
									$ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$ary_res['all_price']);
								}else{
									//计算可以使用优惠券总金额
									$coupon_all_price = 0;
									foreach ($order_data as $keys => $vals) {
										if(in_array($vals['g_id'],$coupon['gids'])){
											$coupon_all_price += $vals['oi_price']*$vals['oi_nums'];     //商品总价
										}
									}	
									$ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$coupon_all_price);
								}
							}else{
								$ary_res ['coupon_price'] += $coupon ['c_money'];
							} 
						}
					}
				    $ary_res['sucMsg'] = '优惠券使用成功';
                    $ary_res['success'] = 1;
                    $ary_res['coupon_price'] = $ary_res['c_money'];
                    $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
                    $order_info = $orders->where(array('o_id' => $o_id))->field('o_all_price,m_id')->find();
                    M('', '', 'DB_CUSTOM')->startTrans();
                    //优惠券金额
                    $ary_order_data['o_coupon_menoy'] = $ary_coupon['c_money'];
                    $ary_order_data['o_all_price'] = $order_info['o_all_price'] - $ary_coupon['c_money'];
                    $ary_order_data['o_update_time'] = date('Y-m-d H:i:s');
                    $res = $orders->where(array('o_id' => $o_id))->data($ary_order_data)->save();
                    if (!$res) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $ary_res['sucMsg'] = '优惠券使用失败';
                        $ary_res['success'] = 0;
                        echo json_encode($ary_res);
                        exit;
                    } else {
                        //更新日志表
                        $ary_orders_log = array(
                            'o_id' => $int_oid,
                            'ol_behavior' => '卖家订单编辑,使用优惠券:',
                            'ol_text' => serialize($ary_order_data),
                            'ol_desc' => file_get_contents($_SERVER['HTTP_REFERER'])
                        );
                        $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                    }
					 if($ary_coupon['status'] == 'success'){
						foreach ($ary_coupon['msg'] as $coupon){
							// 更新优惠券使用
							$ary_data = array(
								'c_is_use' => 1,
								'c_used_id' => $_SESSION ['Members'] ['m_id'],
								'c_order_id' => $ary_orders ['o_id']
							);
							$res_coupon = D('Coupon')->doCouponUpdate($coupon ['c_sn'], $ary_data);
							if (!$res_coupon) {
								M('', '', 'DB_CUSTOM')->rollback();
								$ary_res['sucMsg'] = '优惠券使用失败';
								$ary_res['success'] = 0;
								echo json_encode($ary_res);
								exit;
							}
						}
					}
                    M('', '', 'DB_CUSTOM')->commit();	
				}
			} else {
				$ary_res ['errMsg'] = '优惠券编号错误';
				$ary_res ['success'] = 0;
			}
		}
		echo json_encode($ary_res);
		exit;
		//判断优惠券金额是否大于订单金额
    }

    /**
     * 删除订单商品
     * @author wangguibin
     * @date 2013-09-05
     */
    public function delItems() {
        $ary_order_data = $this->_post();
        if (empty($ary_order_data['o_id'])) {
            $this->error('此订单号不存在');
            exit;
        }
        //判断订单后是否为未付款
        $ary_where = array('o_id' => $int_oid, 'o_pay_status' => 1, 'o_status' => 1);
        $orders_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_orders = $orders_info->where($ary_where)->find();
        if (count($ary_orders) > 0 && !empty($ary_orders)) {
            $this->error('此订单已不能编辑', array('确定' => U('Admin/Orders/pageList')));
            exit;
        }
        $orders = M('', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        $order_item_obj = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
        $order_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        //订单商品删除
        $del_where = array();
        $del_where['oi_type'] = $ary_order_data['type'];
        $del_where['o_id'] = $ary_order_data['o_id'];
        if ($ary_order_data['type'] == '3' || $ary_order_data['type'] == '4') {
            $del_where['fc_id'] = $ary_order_data['design_id'];
        } else {
            $del_where['pdt_id'] = $ary_order_data['design_id'];
        }
        //积分商品解除冻结积分
        if ($ary_order_data['type'] == '1') {
            $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $ary_order_data['o_id']))->find();
            //查询多少积分
            $point = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('oi_score,oi_nums')->where($del_where)->find();
            $return_point = $point['oi_nums'] * $point['oi_score'];
            if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                //订单主表减积分
                $now_point = $ary_orders['o_freeze_point'] - $return_point;
                $res_order = $order_info->where(array('o_id' => $ary_order_data['o_id']))->data(array('o_freeze_point' => $now_point))->save();
                if (!$res_order) {
                    $orders->rollback();
                    $ary_res['errMsg'] = '商品删除失败，退积分失败';
                    $ary_res['success'] = 0;
                    echo json_encode($ary_res);
                    exit;
                }
                $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                if ($ary_member && $ary_member['freeze_point'] > 0) {
                    $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_orders['o_freeze_point'];
                    $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                    if (!$res_member) {
                        $orders->rollback();
                        $ary_res['errMsg'] = '商品删除失败，退积分失败';
                        $ary_res['success'] = 0;
                        echo json_encode($ary_res);
                        exit;
                    }
                }
            }
        }
        //删除商品明细表
        $result_order = $order_item_obj->where($del_where)->delete();
        if (!$result_order) {
            $orders->rollback();
            $ary_res['errMsg'] = '商品删除失败，删除商品明细表失败';
            $ary_res['success'] = 0;
            echo json_encode($ary_res);
            exit;
        }
        //商品总价
        $ary_order_data['o_goods_all_price'] = 0;
        $other_price = 0;
        $order_items = $order_item_obj->where(array('o_id' => $ary_order_data['o_id']))->select();
        foreach ($order_items as $v) {
            //自由推荐
            if ($v['oi_type'] == 4) {
                //自由推荐价格
                $ary_order_data['o_goods_all_price']+= sprintf("%0.3f", $v['oi_nums'] * $v['oi_price']);
                $other_price +=sprintf("%0.3f", $v['oi_nums'] * $v['oi_price']);
            } else {
                if ($v['type'] == 3) {
                    $ary_order_data['o_goods_all_price']+= sprintf("%0.3f", $v['oi_nums'] * $v['oi_price']);
                    $other_price +=sprintf("%0.3f", $v['oi_nums'] * $v['oi_price']);
                } else {
                    $ary_order_data['o_goods_all_price']+= sprintf("%0.3f", $v['oi_nums'] * $v['oi_price']);
                }
            }
        }
        //整单促销规则(暂时不考虑)
        //		$ary_data['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
        //		foreach($ary_data['ary_product_data'] as $info){
        //			if($info[0]['type'] != '4'){
        //				if(!empty($info['rule_info']['pmn_id']) && empty($ary_pdt_info['rule_info']['pmn_id'])){
        //					$ary_pdt_info['rule_info']=$info['rule_info'];
        //				}
        //				if($info['type']!=3){
        //					$ary_pdt_info[$info['pdt_id']]=array('pdt_id'=>$info['pdt_id'],'rule_info'=>$info['rule_info'],'num'=>$info['pdt_nums'],'type'=>$info['type'],'price'=>$info['f_price']);
        //				}
        //			}
        //		}
        //		$ary_param=array('action'=>'order','mid'=>$ary_order_data['m_id'],'all_price'=>$ary_order_data['o_goods_all_price']-$other_price,'ary_pdt'=>$ary_pdt_info);
        //		$orders_discount=D('Price')->getOrderPrice($ary_param);
        //		$promotion_price=0;
        //		if ($orders_discount['all_price'] > 0 && !empty($orders_discount['code'])) {
        //			//促销优惠价格
        //			if($orders_discount['code']=='MBAOYOU' ||$orders_discount['code']=='MZENPIN' || $orders_discount['code']=='MQUAN'){
        //				if($orders_discount['code']=='MBAOYOU' && $str_logistic_price < $orders_discount['price']){
        //					$fla_pmn_price=sprintf("%0.2f",$str_logistic_price) ;
        //				}else{
        //					$fla_pmn_price=sprintf("%0.2f",$orders_discount['price']) ;
        //				}
        //			}else{
        //				$fla_pmn_price=sprintf("%0.2f",$orders_discount['all_price'] -  $orders_discount['price']) ;
        //			}
        //			$ary_order_data = ['o_goods_all_price'] =   sprintf("%0.2f",$orders_discount['all_price']-$fla_pmn_price) ;
        //		}else{
        //			//$ary_orders['o_goods_all_price'] =   sprintf("%0.2f",$orders_discount['all_price']) ;
        //		}
        //判断订单后是否为未付款
        $ary_where = array('o_id' => $ary_order_data['o_id']);
        $order = $orders_info->where($ary_where)->field('o_coupon_menoy,o_discount,o_cost_payment,o_cost_freight')->find();
        //订单总价 商品会员折扣价-优惠券金额
        $all_price = $ary_order_data['o_goods_all_price'] - $order['o_discount'] - $order['o_coupon_menoy'];
        if ($all_price <= 0) {
            $all_price = 0;
        }
        //订单应付总价 订单总价+运费
        $all_price += $order['o_cost_freight'] + $order['o_cost_payment'];
        $ary_order_data['o_all_price'] = sprintf("%0.3f", $all_price);
        $ary_order_data['o_update_time'] = date('Y-m-d H:i:s');
        $obj_orders_res = $orders_info->where(array('o_id' => $ary_order_data['o_id']))->data($ary_order_data)->save();
        if (!$obj_orders_res) {
            $orders->rollback();
            $this->error('订单失败');
            exit;
        }
        //更新日志表
        $ary_orders_log = array(
            'o_id' => $int_oid,
            'ol_behavior' => '卖家订单编辑:',
            'ol_text' => serialize($ary_order_data),
            'ol_desc' => file_get_contents($_SERVER['HTTP_REFERER'])
        );
        $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
        $orders->commit();

        $ary_res['errMsg'] = '订单删除成功';
        $ary_res['success'] = 1;
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 根据商品的pdt_id和购买数量，修改购物车
     * @author jiye
     * @date 2012-12-11
     * @modify zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-28
     */
    public function doEditNum() {
        $ary_data = $this->_post();
        $int_pdt_nums = $this->_post("pdt_nums");
        $int_pdt_id = $this->_post("pdt_id");
        $int_good_type = $this->_post("good_type", "", 0);
        $all_price = $this->_post("all_price", "", 0);
        $all_dis = $this->_post("all_dis", "", 0);
        $m_id = $this->_post("m_id");
        $good_info = D("GoodsProducts")->where(array('pdt_id' => $int_pdt_id, 'pdt_status' => 1))->field('g_id,pdt_stock,pdt_sale_price')->find();
        $good_info['pdt_stock'] = D('GoodsStock')->getProductStockByPdtid($int_pdt_id,$m_id);
        if ($good_info['pdt_stock'] < $int_pdt_nums) {
            $result = array('stauts' => false, 'message' => '商品库存不足');
            $this->ajaxReturn($result);
            exit;
        }
        if ($good_info['pdt_sale_price'] <= 0) {
            $result = array('stauts' => false, 'message' => '商品价格不正确');
            $this->ajaxReturn($result);
            exit;
        }
        $g_id = $good_info['g_id'];
        $is_authorize = D('AuthorizeLine')->isAuthorize($m_id, $g_id);
        if (empty($is_authorize)) {
            $result = array('stauts' => false, 'message' => '商品已不允许购买');
            $this->ajaxReturn($result);
            exit;
        }
        $promotion_flg = false;
        $promotion_price = 0;
        $preferential_price = 0;
        $price = new PriceModel($m_id);
        $arr_newdata["pdt_sale_price"] = $good_info['pdt_sale_price'];
        $arr_newdata["f_price"] = $price->getItemPrice($int_pdt_id, $arr_newdata['pdt_sale_price']);
        $arr_newdata["pdt_preferential"] = sprintf("%0.2f", $arr_newdata["pdt_sale_price"] - $arr_newdata["f_price"]);
        $arr_newdata["pdt_rule_name"] = $price->getPriceRuleName();
        $arr_newdata["rule_info"] = $price->getRuleinfo();
        $arr_newdata["pdt_nums"] = $int_pdt_nums;
        $arr_newdata["pdt_momery"] = sprintf("%0.2f", $int_pdt_nums * $arr_newdata["f_price"]);
        $arr_newdata["pdt_price"] = sprintf("%0.2f", $arr_newdata["f_price"]);
        $arr_newdata["type"] = 0;
        $result = array('stauts' => true, 'data' => $arr_newdata);
        $this->ajaxReturn($result);
    }

    /**
     * 配送方式页面
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-03
     */
    public function getLogisticType() {
        $cr_id = $this->_post('cr_id');
        
        $o_id = $this->_post('o_id');//订单编号
        $ary_mid = D('Orders')->field('m_id')->where(array('o_id'=>$o_id))->find();
        
        $ary_logistic = D('Logistic')->getLogistic($cr_id,array(),$ary_mid['m_id']);
        if (!empty($ary_logistic) && is_array($ary_logistic)) {
            foreach ($ary_logistic as $k1 => $v1) {
                $ary_logistic[$k1]['logistic_price'] = $v1['logistic_price'] - $p_logistic;
                if ($ary_logistic[$k1]['logistic_price'] < 0) {
                    $ary_logistic[$k1]['logistic_price'] = 0;
                }
            }
        }
        $this->assign('ary_logistic', $ary_logistic);
        $this->display();
    }

    /**
     * 订单审核
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-05
     */
    public function checkAudit() {
        $str_o_id = $this->_post('o_id');
        $order_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        if ($str_o_id) {
            M('', '', 'DB_CUSTOM')->startTrans();
            $array_orders = explode(',',$str_o_id);
            foreach($array_orders as $o_id){
                //订单状态必须是已付款的订单或货到付款的订单
                $ary_where['_string'] = ' o_id=' . $o_id . ' and o_status=1 and (o_pay_status=1 or (o_payment=6))';
                $orders_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
                $ary_orders = $orders_info->where($ary_where)->find();
				if($ary_orders['o_audit'] == 1){
					$this->error('订单'.$o_id.'已审核，无需再次审核');
                    exit;
				}
                //售后状态
                $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $o_id))->order('or_create_time desc')->select();
                if (!empty($ary_afersale) && is_array($ary_afersale)) {
                    $this->error('订单不允许审核');
                    exit;
                }
                if (empty($ary_orders)) {
                    $this->error('订单不允许审核');
                    exit;
                } else {
                    
                    //判断是否自动拆单
                    $resdata1 = D('SysConfig')->getCfg('ORDERS_REMOVE', 'ORDERS_REMOVE', '1', '是否开启订单拆分');
                    $resdata2 = D('SysConfig')->getCfg('ORDERS_REMOVETYPE', 'ORDERS_REMOVETYPE', '1', '订单拆分方式(1:自动拆分;0:手动拆分)');
                    if (($resdata1['ORDERS_REMOVE']['sc_value'] == '1') && ($resdata2['ORDERS_REMOVETYPE']['sc_value'] == '1')) {
                        //订单拆单
                        //判断是否已拆单
                        $ary_order_count = $orders_info->where(array('o_id' => $o_id, 'is_diff' => 0))->count();
                        if ($ary_order_count > 0) {
                            $res_diff = $this->removeOrderItems($o_id);
                            if (!$res_diff) {
                                M('', '', 'DB_CUSTOM')->rollback();
                                $this->error('订单审核失败');
                                exit;
                            }
                        }
                    }
                    $res = $order_info->where(array('o_id' => $o_id))->data(array('o_audit' => '1', 'o_update_time' => date('Y-m-d H:i:s')))->save();
                    if ($res) {
                        //更新日志表
                        $ary_orders_log = array(
                            'o_id' => $o_id,
                            'ol_behavior' => '订单审核',
                            'ol_text' => serialize($ary_orders),
                                //'ol_desc'=>file_get_contents($_SERVER['HTTP_REFERER'])
                        );
                        $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                        if (!$res_orders_log) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error('订单审核失败');
                            exit;
                        }
                        
                    } else {
                        $this->error('订单审核失败');
                        exit;
                    }
                }
            }
            M('', '', 'DB_CUSTOM')->commit();
            $this->success('订单审核成功');
        } else {
            $this->error('订单审核失败');
            exit;
        }
    }

    /**
     * 订单拆分
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-05
     */
    protected function removeOrderItems($o_id) {
        $orders_item = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_orders = $orders_item->field('o_id,oi_id,pdt_id')->where(array('o_id' => $o_id))->group('pdt_id')->select();
        //订单只有一个商品，只更新订单主表状态就好了
        //orders initial_o_id=0,is_diff,o_update_time,erp_id
        //orders_items erp_id
        if (count($ary_orders) == '1') {
            foreach ($ary_orders as $ary_order) {
                $erp_id = D('GoodsProducts')->getErpByPdtId($ary_order['pdt_id']);
                if (empty($erp_id)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单审核失败,请先手动拆单');
                    exit;
                } else {
                    $res_item = $orders_item->where(array('pdt_id' => $ary_order['pdt_id']))
                                    ->data(array('oi_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id))->save();
                    if (!$res_item) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error('订单审核失败');
                        exit;
                    }
                    $res_order = $orders->where(array('o_id' => $ary_order['o_id']))
                                    ->data(array('o_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id, 'is_diff' => '1'))->save();
                    if (!$res_order) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error('订单审核失败');
                        exit;
                    }
                }
            }
        } else {
            //订单明细多条记录
            $erp_ids = array();
            foreach ($ary_orders as $ary_order) {
                $erp_id = D('GoodsProducts')->getErpByPdtId($ary_order['pdt_id']);
                if (!$erp_id) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单审核失败,请先手动拆单');
                    exit;
                } else {
                    $res_item = $orders_item->where(array('pdt_id' => $ary_order['pdt_id']))
                                    ->data(array('oi_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id))->save();
                    if (!$res_item) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error('订单审核失败');
                        exit;
                    }
                    $erp_ids[] = $erp_id;
                }
            }
            $erp_ids = array_unique($erp_ids);
            $orders_info = $orders->where(array('o_id' => $o_id))->find();
            $order_items = $orders_item->where(array('o_id' => $o_id))->select();
            //订单拆分
            foreach ($erp_ids as $erpId) {
                $order_id = date('YmdHis') . rand(1000, 9999);
                $info = array();
                $items = array();
                $price = array();
                foreach ($order_items as $item) {
                    if ($item['erp_id'] == $erpId) {
                        unset($item['oi_id']);
                        $item['o_id'] = $order_id;
                        $item_obj = $orders_item->add($item);
                        $price += $item['oi_price'] * $item['oi_nums'];
                        if (!$item_obj) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error('订单审核失败,请先手动拆单');
                            exit;
                        }
                    }
                }
                $orders_info['o_id'] = $order_id;
                $o_goods_all_price = round(sprintf("%0.2f", $price), 3);
                $orders_info['o_all_price'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_all_price']), 3);
                $orders_info['o_discount'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_discount']), 3);
                $orders_info['o_promotion_price'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_promotion_price']), 3);
                $orders_info['o_goods_all_price'] = $o_goods_all_price;
                $orders_info['initial_o_id'] = $o_id;
                $orders_info['erp_id'] = $erpId;
                $orders_info['o_update_time'] = date('Y-m-d H:i:s');
                //暂时不处理优惠券相关
                unset($orders_info['o_cost_payment']);
                unset($orders_info['o_coupon_menoy']);
                unset($orders_info['o_coupon']);
                unset($orders_info['coupon_sn']);
                unset($orders_info['coupon_value']);
                unset($orders_info['coupon_start_date']);
                unset($orders_info['coupon_end_date']);
                unset($orders_info['o_cost_freight']);
                unset($orders_info['o_freeze_point']);
                unset($orders_info['o_reward_point']);
                $res_order_obj = $orders_item->add($orders_info);
                if (!$res_order_obj) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单审核失败,请先手动拆单');
                    exit;
                }
                $res_order = $orders->where(array('o_id' => $ary_order['o_id']))
                                ->data(array('o_update_time' => date('Y-m-d H:i:s'), 'is_diff' => '1'))->save();
                if (!$res_order) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单审核失败');
                    exit;
                }
            }
        }
        return true;
    }

    /**
     * 手工拆单
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-06
     */
    public function autoRemoveOrderItems() {
        $this->getSubNav(4, 0, 10);
        if (!isset($_GET["o_id"]) || !is_numeric($_GET["o_id"])) {
            $this->error("参数订单ID不合法。");
        }
        $int_oid = $this->_get('o_id');
        $where = array('o_id' => $int_oid);
        $ary_orders = D('Orders')->where($where)->find();

        $ary_orders_info = D('OrdersItems')->where($where)->select();
        if (!empty($ary_orders_info)) {
            foreach ($ary_orders_info as $k => $v) {
                //获取商品的规格，类型为2时，只是拼接销售属性的值
                $ary_orders_info[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id'], 2);
                //$ary_orders_info[$k]['pdt_specs'] = D("GoodsSpec")->getGoodsSpec($v['g_id'], 2);
                $ary_orders_info[$k]['pdt_infos'] = D("GoodsProducts")->where(array('pdt_id' => $v['pdt_id']))->field('pdt_id,pdt_stock,pdt_sale_price')->find();
                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();
                $ary_orders_info[$k]['g_picture'] = getFullPictureWebPath($ary_goods_pic['g_picture']);
                //订单商品退款、退货状态
                $ary_orders_info[$k]['str_refund_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status', $v);
                //订单商品发货
                $ary_orders_info[$k]['str_ship_status'] = D('Orders')->getOrderItmesStauts('oi_ship_status', $v);
                //商品小计
                $ary_orders_info[$k]['subtotal'] = $v['oi_nums'] * $v['oi_price'];
            }
        }
        //订单状态
        //付款状态
        $ary_orders['str_pay_status'] = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_orders['o_pay_status']);
        $ary_orders['str_status'] = D('Orders')->getOrderItmesStauts('o_status', $ary_orders['o_status']);
        //订单状态
        $ary_orders_status = D('Orders')->getOrdersStatus($ary_orders['o_id']);
        //退款
        $ary_orders['refund_status'] = $ary_orders_status['refund_status'];
        //退货
        $ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
        //发货
        $ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
        //查询ERP
        $erp_ids = M('warehouse', C('DB_PREFIX'), 'DB_CUSTOM')->field('erp_id')->group('erp_id')->select();
        $this->assign('erp_ids', $erp_ids);
        $this->assign('ary_orders_info', $ary_orders_info);
        $this->assign('ary_orders', $ary_orders);
        $this->display();
    }

    /**
     * 手工拆单保存
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-06
     */
    public function doRemoveOrderItems() {
        $ary_order_data = $this->_post();
        $o_id = $this->_post('o_id');
        $pdt_ids = $this->_post('pro_pdt_id');
        $erp_ids = $this->_post('pro_erp_id');
        if (count($pdt_ids) != count($pdt_ids)) {
            $this->error('请先选择ERP');
            exit;
        }
        foreach ($erp_ids as $erp_id) {
            if (empty($erp_id)) {
                $this->error('请先选择ERP');
                exit;
            }
        }
        $order_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        if ($o_id) {
            //订单状态必须是已付款的订单或货到付款的订单
            $ary_where['_string'] = ' o_id=' . $o_id . ' and o_status=1 and (o_pay_status=1 or (o_payment=6)) and is_diff="0" ';
            $orders_info = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_orders = $orders_info->where($ary_where)->find();
            //售后状态
            $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $o_id))->order('or_create_time desc')->select();
            if (!empty($ary_afersale) && is_array($ary_afersale)) {
                $this->error('订单不允许拆分');
                exit;
            }
            if (empty($ary_orders)) {
                $this->error('订单不允许拆分');
                exit;
            } else {
                M('', '', 'DB_CUSTOM')->startTrans();
                //判断是否自动拆单
                $resdata1 = D('SysConfig')->getCfg('ORDERS_REMOVE', 'ORDERS_REMOVE', '1', '是否开启订单拆分');
                $resdata2 = D('SysConfig')->getCfg('ORDERS_REMOVETYPE', 'ORDERS_REMOVETYPE', '1', '订单拆分方式(1:自动拆分;0:手动拆分)');
                if ($resdata1['ORDERS_REMOVE']['sc_value'] == '1') {
                    //订单拆单
                    //判断是否已拆单
                    $ary_order_count = $orders_info->where(array('o_id' => $o_id, 'is_diff' => 0))->count();
                    if ($ary_order_count > 0) {
                        $res_diff = $this->handOrderItems($o_id, $pdt_ids, $erp_ids);
                        if (!$res_diff) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error('订单拆分失败');
                            exit;
                        } else {
                            M('', '', 'DB_CUSTOM')->commit();
                            $this->success('订单拆分成功');
                            exit;
                        }
                    }
                } else {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单不允许拆分');
                    exit;
                }
            }
        } else {
            $this->error('订单拆分失败');
            exit;
        }
    }

     /**
     * 订单手动拆分
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-09-05
     */
    protected function handOrderItems($o_id, $pdt_ids, $erp_ids) {
        $orders_item = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_orders = $orders_item->field('o_id,oi_id,pdt_id')->where(array('o_id' => $o_id))->group('pdt_id')->select();
        //订单只有一个商品，只更新订单主表状态就好了
		//统计是否只更改了一个ERP_ID
        $count_erp = count(array_unique($erp_ids));
        if ((count($ary_orders) == '1') || ($count_erp == '1')) {
            foreach ($pdt_ids as $pdt_id) {
                $erp_id = $erp_ids[0];
                if (empty($erp_id)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单拆分失败');
                    exit;
                } else {
                    $res_item = $orders_item->where(array('pdt_id' => $pdt_id))
                                    ->data(array('oi_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id))->save();
                    if (!$res_item) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error('订单拆分失败');
                        exit;
                    }
                }
            }
            $res_order = $orders->where(array('o_id' => $o_id))
            ->data(array('o_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id, 'is_diff' => '1'))->save();
            if (!$res_order) {
            	M('', '', 'DB_CUSTOM')->rollback();
            	$this->error('订单拆分失败');
            	exit;
            }
        } else {
            //订单明细多条记录
            $erpIds = array();
            foreach ($pdt_ids as $key => $pdt_id) {
                $erp_id = $erp_ids[$key];
                if (!$erp_id) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单拆分失败');
                    exit;
                } else {
                    $res_item = $orders_item->where(array('pdt_id' => $pdt_id))
                                    ->data(array('oi_update_time' => date('Y-m-d H:i:s'), 'erp_id' => $erp_id))->save();
                    if (!$res_item) {
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error('订单拆分失败');
                        exit;
                    }
                    $erpIds[] = $erp_id;
                }
            }
            $erpIds = array_unique($erpIds);
            
            $orders_info = $orders->where(array('o_id' => $o_id))->find();
            $order_items = $orders_item->where(array('o_id' => $o_id))->select();
            //订单拆分
            foreach ($erpIds as $erpId) {
                $order_id = date('YmdHis') . rand(1000, 9999);
                $info = array();
                $items = array();
                $price = 0;
                foreach ($order_items as $item) {
                    if ($item['erp_id'] == $erpId) {
                        unset($item['oi_id']);
                        $item['o_id'] = $order_id;
                        $item_obj = $orders_item->add($item);
                        $price+= $item['oi_price']*$item['oi_nums'];
                        if (!$item_obj) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error('订单审核失败,请先手动拆单');
                            exit;
                        }
                    }
                }
                $orders_info['o_id'] = $order_id;
                $o_goods_all_price = round(sprintf("%0.2f", $price), 3);
                $orders_info['o_all_price'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_all_price']), 3);
                $orders_info['o_discount'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_discount']), 3);
                $orders_info['o_promotion_price'] = round(sprintf("%0.2f", $price / $orders_info['o_goods_all_price'] * $orders_info['o_promotion_price']), 3);
                $orders_info['o_goods_all_price'] = $o_goods_all_price;
                $orders_info['initial_o_id'] = $o_id;
                $orders_info['erp_id'] = $erpId;
                $orders_info['o_update_time'] = date('Y-m-d H:i:s');
                //暂时不处理优惠券相关
                unset($orders_info['o_cost_payment']);
                unset($orders_info['o_coupon_menoy']);
                unset($orders_info['o_coupon']);
                unset($orders_info['coupon_sn']);
                unset($orders_info['coupon_value']);
                unset($orders_info['coupon_start_date']);
                unset($orders_info['coupon_end_date']);
                unset($orders_info['o_cost_freight']);
                unset($orders_info['o_freeze_point']);
                unset($orders_info['o_reward_point']);
                $res_order_obj = $orders->add($orders_info);
                if (!$res_order_obj) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单拆分失败');
                    exit;
                }
                $res_order = $orders->where(array('o_id' => $o_id))
                                ->data(array('o_update_time' => date('Y-m-d H:i:s'), 'is_diff' => '1'))->save();
                if (!$res_order) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单拆分失败');
                    exit;
                }
            }
        }
        return true;
    }

    /**
     * 订单完结
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-09-06
     */
    public function overOrder(){
        $o_id = $this->_post('oid');
        if(empty($o_id)){
            $this->ajaxReturn(array('status'=>false,'msg'=>'参数有误，请重试'));
        }
        $ary_order = D('Orders')->where(array('o_id'=>$o_id))->find();
        if($ary_order['o_pay_status'] != '1'){
            $this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.'还有未支付或部分未支付金额'));
        }
		if($ary_order['o_status'] != '5'){
			$this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.'请先确认收货'));
		}
        $ary_refund_type = D('Orders')->getOrdersStatus($o_id);
        if($ary_refund_type['deliver_status'] == '未发货'){
            $this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.$ary_refund_type['deliver_status']));
        }
        $ary_afersale = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$o_id))->order('or_create_time desc')->select();
        if(!empty($ary_afersale) && is_array($ary_afersale)){
            foreach($ary_afersale as $keyaf=>$valaf){
                //退款
                if($valaf['or_refund_type'] == 1){
                    switch($valaf['or_processing_status']){
                        case 0:
                            $this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.'退款中'));
                            break;
                        default:
                            break;
                    }
                }elseif($valaf['or_refund_type'] == 2){         //退货
                    switch($valaf['or_processing_status']){
                        case 0:
                            $this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.'退货中'));
                            break;
                        default:
                            break;
                    }
                }
				elseif($valaf['or_refund_type'] == 3){         //退运费
                    switch($valaf['or_processing_status']){
                        case 0:
                            $this->ajaxReturn(array('status'=>false,'msg'=>'订单'.$o_id.'退运费中'));
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        M('', '', 'DB_CUSTOM')->startTrans();
        //更改订单状态
        if(false === D('Orders')->where(array('o_id'=>$o_id))->save(array('o_status'=>4))){
            $this->ajaxReturn(array('status'=>false,'msg'=>'操作失败，请重试'));
            M('', '', 'DB_CUSTOM')->rollback();
        }
        $array_point_config = D('PointConfig')->getConfigs();
        if($array_point_config['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '2'){
            //订单完结后处理赠送积分
            if($ary_order['o_reward_point']>0){
                $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_order['o_reward_point'],$ary_order['m_id'],$ary_order['o_id']);
                if(!$ary_reward_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->ajaxReturn(array('status'=>false,'msg'=>$ary_reward_result['message']));
                    exit;
                }
            }
            //订单完结后处理消费积分
            if($ary_order['o_freeze_point'] > 0){
                $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_order['o_freeze_point'],$ary_order['m_id']);
                if(!$ary_freeze_result['result']){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->ajaxReturn(array('status'=>false,'msg'=>$ary_freeze_result['message']));
                    exit;
                }
            }
        }
        /*** 处理赠送金币****start**********/
		
        /**$ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] == '1' && $ary_jlb_data['cinsumed_channel'] == '0'){
            //发货后处理赠送金币
            if($ary_order['o_reward_jlb']>0){
                $arr_jlb = array(
                    'jt_id' => '2',
                    'm_id'  => $ary_order['m_id'],
                    'ji_create_time'  => date("Y-m-d H:i:s"),
                    'ji_type' => '0',
                    'ji_money' => $ary_order['o_reward_jlb'],
                    'ji_desc' => '订单完结赠送金币：'.$ary_order['o_reward_jlb'],
                    'o_id' => $ary_order['o_id'],
                    'ji_finance_verify' => '1',
                    'ji_service_verify' => '1',
                    'ji_verify_status' => '1',
                    'single_type' => '2'
                    );
                $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                if(!$res_jlb){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error("生成订单完结赠送金币调整单错误！");
                    exit;
                }
            }
        }
		**/
        /*** 处理赠送金币****end**********/
        
        /*** 订单发货后获取订单优惠券**star by Joe**/
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        $where = array('fx_orders.o_id' => $o_id);
        $ary_field = array('fx_orders.o_pay','fx_orders.m_id','fx_orders.o_all_price','fx_orders.coupon_sn','fx_orders_items.pdt_id','fx_orders_items.oi_nums','fx_orders_items.oi_type');
        $ary_orders = D('Orders')->getOrdersData($where,$ary_field);
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
			$this->ajaxReturn(array('status'=>false,'msg'=>'订单完成订单完成触发返利错误'));
			exit;
		}		
		$this->orderLog($o_id, "订单完结");
        M('', '', 'DB_CUSTOM')->commit();
        $this->ajaxReturn(array('status'=>true,'msg'=>'操作成功！'));
        
    }
    
    /**
     * 待审核订单列表
     * @author Joe <qianyijun@Guanyisoft.com>
     * @date 2014-03-27
     */
    public function pageToAuditOrderList(){
        $this->getSubNav(4, 0, 15);
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_data = $this->_param();
		//默认查最近三个月数据
		if(empty($ary_where['o_id'])){
			$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
			if(empty($ary_data['o_create_time_1'])){
				$ary_data['o_create_time_1'] = $start_time;
			}
			if (!empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
				if ($ary_data['o_create_time_1'] > $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_2'], $ary_data['o_create_time_1']));
				} else if ($ary_data['o_create_time_1'] < $ary_data['o_create_time_2']) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("BETWEEN", array($ary_data['o_create_time_1'], $ary_data['o_create_time_2']));
				} else {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = $ary_data['o_create_time_1'];
				}
			} else {
				if (!empty($ary_data['o_create_time_1']) && empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("EGT", $ary_data['o_create_time_1']);
				} else if (empty($ary_data['o_create_time_1']) && !empty($ary_data['o_create_time_2'])) {
					$ary_where[C("DB_PREFIX") . 'orders.o_create_time'] = array("ELT", $ary_data['o_create_time_2']);
				}
			}
		}
        //订单搜索条件，显示未付款或者部分付款或者处理中的订单

        //如果需要根据订单号进行搜素
        if (!empty($ary_data['o_id']) && isset($ary_data['o_id'])) {
            $ary_where['o_id'] = $ary_data['o_id'];
        }
        $ary_where['o_status'] = 1;
        $ary_where['o_audit'] = 0;
        $ary_where['_query'] = "o_payment=6&o_pay_status=1&_logic=or";
        //数据分页处理，获取符合条件的记录数并分页显示
        $count = D("Orders")->where($ary_where)->count();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();

        //订单数据获取
        $array_order = array('o_id' => 'desc');
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_orders_info = D("Orders")->field('fx_orders.*')->where($ary_where)->order($array_order)->limit($string_limit)->select();
		//获取所有用户
	   $admins = D('Admin')->field('u_id,u_name')->select();
	   $ary_admin = array();
	   foreach($admins as $admin_info){
		$ary_admin[$admin_info['u_id']] = $admin_info['u_name'];
	   }
	   foreach($ary_orders_info as $o_key=>$sub_order){
		if($sub_order['admin_id']){
			$ary_orders_info[$o_key]['admin_name'] = $ary_admin[$sub_order['admin_id']];
		}
	   }
		//dump(D("Orders")->getLastSql());die();
        //获取所有的支付方式信息，用于匹配支付方式名称
        $array_payment_cfg = D("PaymentCfg")->where(1)->getField("pc_id,pc_custom_name");
        //遍历订单数据，处理订单的发货状态
        foreach ($ary_orders_info as $k => $v) {
            if($v['o_pay_status'] == 1 || $v['o_payment'] == 6){
                //订单状态
                $ary_status = array('o_status' => $v['o_status']);
                $str_status = D("Orders")->getOrderItmesStauts('o_status', $ary_status);
                $ary_orders_info[$k]['str_status'] = $str_status;

                //订单支付状态
                $ary_pay_status = array('o_pay_status' => $v['o_pay_status']);
                $str_pay_status = D("Orders")->getOrderItmesStauts('o_pay_status', $ary_pay_status);
                $ary_orders_info[$k]['str_pay_status'] = $str_pay_status;

                //订单的发货状态
                $ary_orders_status = D("Orders")->getOrdersStatus($v['o_id']);
                //if($ary_orders_status['deliver_status'] == '已发货'){
               //     unset($ary_orders_info[$k]);continue;
              //  }
                $ary_orders_info[$k]['deliver_status'] = $ary_orders_status['deliver_status'];

                //获取会员名称
                $ary_orders_info[$k]['m_name'] = D("Members")->where(array("m_id" => $v["m_id"]))->getField("m_name");

                //订单支付方式名称
                $ary_orders_info[$k]['pc_name'] = $array_payment_cfg[$v["o_payment"]];
                $wheres = array();
                $wheres[C("DB_PREFIX") . 'logistic_type.lt_id'] = $v['lt_id'];
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
                $ary_orders_info[$k]['delivery_company_name'] = $delivery_company_info['lc_is_enable'] ? $delivery_company_info['lc_name'] : '已删除';
                //取出订单号
                $int_oid = $v['o_id'];
                $int_oinum = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $int_oid))->sum('oi_nums');
                $ary_orders_info[$k]['oi_nums'] = $int_oinum;
                //客户人员处理
                $ary_orders_info[$k]['u_name'] = '';
                if (isset($v['admin_id']) && $v['admin_id'] > 0) {
                    $ary_admin = D('Admin')->getAdminInfoById($v['admin_id'], array('u_name'));
                    if (is_array($ary_admin) && !empty($ary_admin)) {
                        $ary_orders_info[$k]['u_name'] = $ary_admin['u_name'];
                    }
                }
                
                //售后状态
                $ary_afersale = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $v['o_id']))->order('or_create_time desc')->select();
                if (!empty($ary_afersale) && is_array($ary_afersale)) {
                    unset($ary_orders_info[$k]);continue;
                }
            }else{
                unset($ary_orders_info[$k]);continue;
            }
        }
        $this->assign("filter", $ary_data);
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->display();
    }

    /**
     * 退运费订单列表
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-30
     */
    public function pageOrdersRefundDeliverList(){
        $this->getSubNav(4, 2, 50);
        $ary_get = $this->_get();
        $ary_where = array();       //订单搜索条件
        //订单号
        if (!empty($ary_get['o_id']) && isset($ary_get['o_id'])) {
            $ary_where['fx_orders_refunds.o_id'] = $ary_get['o_id'];
        }
		$ary_field=array(
			'fx_orders_refunds.o_id',
			'fx_orders_refunds.or_id',
			'fx_orders_refunds.or_processing_status',
			'fx_orders_refunds.or_service_verify',
			'fx_orders_refunds.or_finance_verify',
			'fx_orders_refunds.or_return_sn',
			'fx_orders_refunds.or_money',
			'fx_orders_refunds.or_currency',
			'fx_orders_refunds.or_account',
			'fx_orders_refunds.or_bank',
			'fx_orders_refunds.or_payee',
			'fx_members.m_id',
			'fx_members.m_name'
		);
		$orders = array('fx_orders_refunds.or_create_time' => 'desc');
        $ary_where['fx_orders_refunds.or_refund_type'] = "3";
		
		$count =  D('OrdersRefunds')->GetRefundsOrdersCount($ary_where);
		
        $obj_page = new Page($count, 10);
		$limit  = $obj_page->firstRow . ',' . $obj_page->listRows ;
		$ary_orders_info = D('OrdersRefunds')->GetRefundsOrders($ary_where,$ary_field,$group,$orders,$limit);
        
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            foreach ($ary_orders_info as $keyde => $valde) {
                if ($valde['or_processing_status'] == 1) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理成功';
                    $ary_orders_info[$keyde]['status']['color'] = 'green';
                } elseif ($valde['or_processing_status'] == 2) {
                    $ary_orders_info[$keyde]['status']['msg'] = '作废';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } elseif ($valde['or_processing_status'] == 0) {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理中';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                } else {
                    $ary_orders_info[$keyde]['status']['msg'] = '处理失败';
                    $ary_orders_info[$keyde]['status']['color'] = 'red';
                }
            }
        }
        //echo "<pre>";print_r($ary_orders_info);exit;
        $page = $obj_page->show();
        $this->assign("page", $page);
        $this->assign("data", $ary_orders_info);
        $this->assign("filter", $ary_get);
        $this->display();
    }

    public function showMobile(){
        $o_id = $this->_post('oid');

        $resault['mobile'] = '';
        if(!empty($o_id) && is_numeric($o_id)){
            $str_receiver_mobile = D('Orders')->where(array('o_id'=>$o_id))->getField('o_receiver_mobile');
            if(!empty($str_receiver_mobile) && strpos($str_receiver_mobile,':')){
                $resault['mobile'] = decrypt($str_receiver_mobile);
                $this->ajaxReturn($resault);
            }elseif(!empty($str_receiver_mobile)){
                $resault['mobile'] = $str_receiver_mobile;
                $this->ajaxReturn($resault);
            }
        }

    }

    /**
     * 显示完整的身份证号
     */
    public function showIDcard(){
        $o_id = $this->_post('oid');

        $resault['IDcard'] = '';
        if(!empty($o_id) && is_numeric($o_id)){
            $str_receiver_idcard = D('Orders')->where(array('o_id'=>$o_id))->getField('o_receiver_idcard');
            if(!empty($str_receiver_idcard) && strpos($str_receiver_idcard,':')){
                $resault['IDcard'] = decrypt($str_receiver_idcard);
                $this->ajaxReturn($resault);
            }elseif(!empty($str_receiver_idcard)){
                $resault['IDcard'] = $str_receiver_idcard;
                $this->ajaxReturn($resault);
            }
        }

    }

    /**
     * 请选择退款方式
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-23
     */
    public function checkFinancialAudit() {
        $int_or_id = intval($this->_post('id'));
        if (isset($int_or_id) && !empty($int_or_id)) {
            $ary_data = D('Gyfx')->selectOne('orders_refunds',null, array("or_id" => $int_or_id));
            //查询订单支付方式
            $ary_data['or_refunds_type'] = 1;
        }
        $ary_oi_ids = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->field('oi_id')->select();
        $oi_ids = array();
        foreach($ary_oi_ids as $order_info){
            $oi_ids[] = $order_info['oi_id'];
        }
        $ary_where = array();
        $ary_where['oi_id'] = array('in',implode(',',$oi_ids));
        $order_data = M('orders_items', C('DB_PREFIX'), 'DB_CUSTOM')
            ->field('sum(oi_coupon_menoy) as coupon_money,sum(oi_bonus_money) as bonus_money,sum(oi_cards_money) as cards_money,sum(oi_jlb_money) as jlb_money,sum(oi_point_money) as point_money')
            ->where($ary_where)->find();
        $this->assign('order_data', $order_data);
        $this->assign('ary_data', $ary_data);
        $this->display();
    }

    /**
     * 退款状态
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-07
     */
    public function doFinancialAudit() {
        $ary_post = $this->_post();
        $orders = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM');
        if (!empty($ary_post['field']) && isset($ary_post['field'])) {
            $ary_res = $orders->where(array('or_id' => $ary_post['id']))->find();
            if (!empty($ary_post['field']) && $ary_post['field'] == 'or_processing_status') {
                if ($ary_res['or_service_verify'] == '1' && $ary_res['or_finance_verify'] == '1') {
                    $this->error('单据已客审已财审,不可作废');
                    exit;
                }
                $ary_order_data = array();
                $ary_order_data['or_processing_status'] = 2;
                $ary_order_data['or_update_time'] = date('Y-m-d H:i:s');
                $ary_order_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['u_name'] = $_SESSION['admin_name'];
                $ary_order_data['or_refuse_reason'] = $ary_post['val'];
                $orders->startTrans();
                $ary_result = $orders->where(array('or_id' => $ary_post['id']))->data($ary_order_data)->save();
                // 更新订单状态
                $ary_order_item_data = array();
                $ary_order_item_data['oi_refund_status'] = 6;
                $orders_refunds_items = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM');
                $oi_id = $orders_refunds_items->where(array('or_id'=>$ary_post['id']))->getField("oi_id",true);
                $map['oi_id'] = array('in',$oi_id);
                $result = D('OrdersItems')->where($map)->data($ary_order_item_data)->save();

                if ((false === $ary_result) || (false === $result)) {
                    $orders->rollback();
                    $this->error('单据作废失败');
                    exit;
                }
                //更新日志表
                $ary_orders_log = array(
                    'o_id' => $ary_res['o_id'],
                    'ol_behavior' => '订单作废',
                    'ol_text' => serialize($data)
                );
                D('OrdersLog')->addOrderLog($ary_orders_log);
                $orders->commit();
                $this->success("操作成功");
                exit;
            }
            if ($ary_post['field'] == 'or_service_verify' && $ary_post['val'] == 1 && $ary_res['or_service_verify'] == 1) {
                $this->error("该单据已客审");
            }
            if ($ary_post['field'] == 'or_finance_verify' && $ary_post['val'] == 1 && $ary_res['or_finance_verify'] == 1) {
                $this->error("该单据已财审");
            }
            $orders->startTrans();
            $ary_order_data = array();
            $ary_order_data[$ary_post['field']] = $ary_post['val'];
            $ary_order_data['or_refunds_type'] = $ary_post['or_refunds_type'];
            $ary_order_data['or_seller_memo'] = $ary_post['or_seller_memo'];
            if ($ary_order_data['or_refunds_type'] == 2) {
                $ary_order_data['or_bank'] = $ary_post['or_bank'];
                $ary_order_data['or_account'] = $ary_post['or_account'];
                $ary_order_data['or_payee'] = $ary_post['or_payee'];
            }
            if ($ary_post['field'] == 'or_finance_verify') {
                $ary_order_data['or_finance_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['or_finance_u_name'] = $_SESSION['admin_name'];
                $ary_order_data['or_finance_time'] = date('Y-m-d H:i:s');
                $ary_order_data['or_processing_status'] = 1;
            } elseif ($ary_post['field'] == 'or_service_verify') {
                $ary_order_data['or_service_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                $ary_order_data['or_service_u_name'] = $_SESSION['admin_name'];
                // $ary_order_data['service_time'] = date('Y-m-d H:i:s');
                $ary_order_data['or_service_time'] = date('Y-m-d H:i:s');
            }
            $ary_result = $orders->where(array('or_id' => $ary_post['id']))->data($ary_order_data)->save();
            if (FALSE != $ary_result) {
                $balance = new Balance();
                $ary_balance = $balance->getBalanceInfo(array('or_id' => $ary_res['or_return_sn']));
                //获取订单号
                $o_id = $orders->where(array('or_id' => $ary_post['id']))->getField('o_id');
                $o_payment = M('orders')->where(array('o_id' => $o_id))->getField('o_payment');

                //获取订单支付方式
                $pay = 0;
                //类型为退回原账号且支付类型为预存款
                if ($o_payment == 1 && $ary_post['or_refunds_type'] == 3) {
                    $pay = 1;
                }
                if ($ary_post['or_refunds_type'] == 1) {
                    $pay = 1;
                }
                //类型为且支付类型为预存款
                if ($ary_res['or_refund_type'] == 2 && $o_payment == 1) {
                    //如果是退货单
                    $pay = 1;
                }
                //满足财审并当前退款方式为退款至预存款，生成结余款调整单
                if (!empty($ary_post['field']) && $ary_post['field'] == 'or_finance_verify' && $pay == 1) {
                    if (!empty($ary_balance) && is_array($ary_balance)) {
                        $ary_post['or_id'] = $ary_res['or_return_sn'];
                        $ary_post['m_id'] = $ary_res['m_id'];
                        $ary_post['or_money'] = $ary_res['or_money'];
                        $ary_post['or_refund_type'] = $ary_res['or_refund_type'];
                        $ary_rest = $balance->doBalanceInfoStatus($ary_post);
                        if ($ary_rest['success']) {
                            //更新日志表
                            $ary_orders_log = array(
                                'o_id' => $ary_res['o_id'],
                                'ol_behavior' => '财审成功，退结余款成功',
                                'ol_text' => serialize($ary_post)
                            );
                            $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                            if (!$res_orders_log) {
                                $orders->rollback();
                                $this->error('更新失败');
                                exit;
                            }
                            $orders->commit();
                            $this->success($ary_rest['msg']);
                        } else {
                            $orders->rollback();
                            $this->error($ary_rest['msg']);
                        }
                    } else {
                        //获取退款单详情
                        $ary_order_refund_info = $orders->where(array('or_id' => $ary_post['id']))->find();
                        $arr_data = array();

                        $arr_data['o_id'] = $ary_order_refund_info['o_id'];
                        $arr_data['m_id'] = $ary_order_refund_info['m_id'];
                        $arr_data['bi_accounts_receivable'] = $ary_order_refund_info['or_account'];
                        $arr_data['bi_accounts_bank'] = $ary_order_refund_info['or_bank'];
                        $arr_data['bi_payeec'] = $ary_order_refund_info['or_payee'];
                        $arr_data['bi_type'] = '0';
                        $arr_data['or_id'] = $ary_order_refund_info['or_return_sn'];
                        $arr_data['bi_money'] = $ary_order_refund_info['or_money'];
                        $arr_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                        $arr_data['bi_create_time'] = date("Y-m-d H:i:s");
                        $arr_data['bi_payment_time'] = date("Y-m-d H:i:s");
                        $arr_data['bt_id'] = '2';
                        $arr_data['bi_finance_verify'] = '1';
                        $arr_data['bi_service_verify'] = '1';
                        $arr_data['bi_verify_status'] = '1';
                        $arr_data['bi_desc'] = '买家退款或退货';
                        $ary_rest = $balance->addBalanceInfo($arr_data);
                        //获取结余款调整单基本表
                        $balance_info = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($arr_data)->find();

                        //写入客审结余款调整单日志
                        $balance_server_log['u_id'] = $ary_order_refund_info['or_service_u_id'];
                        $balance_server_log['u_name'] = $ary_order_refund_info['or_service_u_name'];
                        $balance_server_log['bi_sn'] = $balance_info['bi_sn'];
                        $balance_server_log['bvl_desc'] = '审核成功';
                        $balance_server_log['bvl_type'] = '2';
                        $balance_server_log['bvl_status'] = '1';
                        $balance_server_log['bvl_create_time'] = $ary_order_refund_info['or_service_time'];
                        if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_server_log)){
                            $orders->rollback();
                            $this->error("生成结余款调整单日志失败");
                        }
                        //写入财审结余款调整单日志
                        $balance_finance_log['u_id'] = $ary_order_refund_info['or_finance_u_id'];
                        $balance_finance_log['u_name'] = $ary_order_refund_info['or_finance_u_name'];
                        $balance_finance_log['bi_sn'] = $balance_info['bi_sn'];
                        $balance_finance_log['bvl_desc'] = '审核成功';
                        $balance_finance_log['bvl_type'] = '3';
                        $balance_finance_log['bvl_status'] = '1';
                        $balance_finance_log['bvl_create_time'] = $ary_order_refund_info['or_finance_time'];
                        if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_finance_log)){
                            $orders->rollback();
                            $this->error("生成结余款调整单日志失败");
                        }
                        //写入支付序列日志
                        $m_balance = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_order_refund_info['m_id']))->getField('m_balance');
                        $running_acc['ra_payment_method'] = "预存款";
                        $running_acc['ra_before_money'] = $m_balance - $ary_order_refund_info['or_money'];
                        $running_acc['ra_after_money'] = $m_balance;
                        $running_acc['ra_money'] = $ary_order_refund_info['or_money'];
                        $running_acc['m_id'] = $ary_order_refund_info['m_id'];
                        $running_acc['ra_memo'] = "买家退款或退货";
                        $running_acc['ra_type'] = 4;
                        M('running_account',C('DB_PREFIX'),'DB_CUSTOM')->add($running_acc);
                        if ($ary_rest['success']) {
                            if ($ary_res['or_refund_type'] == 1) {
                                //退款单
                                $order_item['oi_refund_status'] = 4;
                                $order_item['oi_update_time'] = date('Y-m-d H:i:s');
                                if (false === M('orders_items')->where(array('o_id' => $ary_res['o_id']))->save($order_item)) {
                                    $orders->rollback();
                                    $this->error("更新订单状态失败");
                                }
                                //库存,销量返回
                                $orderItems = M('orders_items')->field('oi_id,o_id,g_id,g_sn,pdt_sn,pdt_id,oi_nums')->where(array('o_id'=>$ary_res['o_id']))->select();								//库存返回
                                foreach($orderItems as $item){
                                    if(empty($item['g_sn']) || empty($item['pdt_sn'])){
                                        $orders->rollback();
                                        $this->error("销量返回失败");exit;
                                    }
                                    $stock_res = M('goods_products')->where(array('g_sn'=>$item['g_sn'],'pdt_sn'=>$item['pdt_sn']))
                                        ->data(array('pdt_update_time'=>date('Y-m-d H:i:s'),'pdt_freeze_stock'=>array('exp',"pdt_freeze_stock-".$item['oi_nums']),'pdt_stock'=>array('exp',"pdt_stock+".$item['oi_nums'])))
                                        ->save();
                                    /*****@author Tom 分销商库存返回 start *****/
                                    $ary_inventory = array(
                                        'pdt_id' => $item['pdt_id'],
                                        'm_id' => $ary_order_refund_info['m_id'],
                                        'num' => $item['oi_nums'],
                                        'u_id' => $_SESSION['Admin'],
                                        'srr_id' => 0
                                    );
                                    $stock_inventory_res = D('GoodsProducts')->BackInventoryLockStock($ary_inventory);
                                    /*****@author Tom 分销商库存返回 end *****/
                                    if(!$stock_res){
                                        //$orders->rollback();
                                        //$this->error("库存返回失败");exit;
                                    }
                                    $goods_num_res = M("goods_info")->where(array(
                                        'g_id' => $item ['g_id']
                                    ))->data(array(
                                        'g_salenum' => array(
                                            'exp',
                                            'g_salenum - '.$item['oi_nums']
                                        )
                                    ))->save();
                                    if(false === $goods_num_res){
                                        $orders->rollback();
                                        $this->error("销量返回失败");exit;
                                    }
                                }
                                if($stock_res){
                                    $stock_res = array(
                                        'o_id' => $ary_res['o_id'],
                                        'ol_behavior' => '订单退款库存返回成功',
                                        'ol_text' => serialize($orderItems)
                                    );
                                    $stock_res_log = D('OrdersLog')->addOrderLog($stock_res);
                                    if(!$stock_res_log){
                                        $orders->rollback();
                                        $this->error('更新库存日志失败');
                                        exit;
                                    }
                                }

                                // 冻结积分释放掉
                                $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $ary_res['o_id']))->find();
                                if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                                    $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                    if ($ary_member && $ary_member['freeze_point'] > 0) {
                                        //订单退款返还冻结积分日志
                                        $ary_log = array(
                                            'type'=>8,
                                            'consume_point'=> 0,
                                            'reward_point'=> $ary_orders['o_freeze_point']
                                        );
                                        $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                        if($ary_info['status'] == 1){
                                            $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_orders['o_freeze_point'];
                                            $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                                            if(!$res_member){
                                                $orders->rollback();
                                                $this->error('退款返回冻结积分失败');
                                                exit;
                                            }
                                        }else{
                                            $orders->rollback();
                                            $this->error('退款返回冻结积分写日志失败');
                                            exit;
                                        }
                                    }else{
                                        $orders->rollback();
                                        $this->error('退款返回冻结积分没有找到要返回的用户冻结金额');
                                        exit;
                                    }
                                }
                                $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                                if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                                    //还原,支出冻结红包金额
                                    $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'bn_type'=>array('neq','0')))->find();
                                    if(!empty($ary_bonus) && is_array($ary_bonus)){
                                        $arr_bonus = array(
                                            'bt_id' => '4',
                                            'm_id'  => $ary_bonus['m_id'],
                                            'bn_create_time'  => date("Y-m-d H:i:s"),
                                            'bn_type' => '0',
                                            'bn_money' => $ary_bonus['bn_money'],
                                            'bn_desc' => '退款申请成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                                            'o_id' => $ary_bonus['o_id'],
                                            'bn_finance_verify' => '1',
                                            'bn_service_verify' => '1',
                                            'bn_verify_status' => '1',
                                            'single_type' => '2'
                                        );
                                        $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                        if($res_bonus === true){
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退款返还红包成功,',
                                                'ol_text' => serialize($ary_bonus)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退款返还红包失败,红包金额为:'.$ary_bonus['bn_money'],
                                                'ol_text' => serialize($ary_bonus)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                }
                                $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                if($card_status['CARDS_AUTO_OPEN'] == 1){
                                    //还原,支出冻结储值卡金额
                                    $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ci_type'=>array('neq','0')))->find();
                                    if(!empty($ary_cards) && is_array($ary_cards)){
                                        $arr_cards = array(
                                            'ct_id' => '2',
                                            'm_id'  => $ary_cards['m_id'],
                                            'ci_create_time'  => date("Y-m-d H:i:s"),
                                            'ci_type' => '0',
                                            'ci_money' => $ary_cards['ci_money'],
                                            'ci_desc' => '退款申请成功返还储值卡金额：'.$ary_cards['ci_money'].'元',
                                            'o_id' => $ary_cards['o_id'],
                                            'ci_finance_verify' => '1',
                                            'ci_service_verify' => '1',
                                            'ci_verify_status' => '1',
                                            'single_type' => '2'
                                        );
                                        $res_cards = D('CardsInfo')->addCards($arr_cards);
                                        if($res_cards === true){
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退款返还储值卡成功,',
                                                'ol_text' => serialize($ary_cards)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退款返还储值卡失败,储值卡金额为:'.$ary_cards['ci_money'],
                                                'ol_text' => serialize($ary_cards)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                }
                                //检测是否需要退优惠券
                                $coupon_info = M('orders')->where(array('o_id'=>$ary_res['o_id']))->field('o_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date')->find();
                                if($coupon_info['o_coupon'] == '1' && $coupon_info['coupon_end_date'] >= date('Y-m-d H:i:s')){
                                    //赠送的优惠券作废
                                    $res = M('coupon')->where(array('c_sn'=>$coupon_info['coupon_sn'],'c_is_use'=>0))->data(array('c_is_use'=>'4'))->save();
                                    if($res){
                                        $ary_orders_log = array(
                                            'o_id' => $ary_res['o_id'],
                                            'ol_behavior' => '退优惠券成功,',
                                            'ol_text' => serialize($coupon_info)
                                        );
                                        D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }else{
                                        $ary_orders_log = array(
                                            'o_id' => $ary_res['o_id'],
                                            'ol_behavior' => '退优惠券失败,优惠券已不存在或已被使用,优惠券为:'.$coupon_info['coupon_sn'],
                                            'ol_text' => serialize($coupon_info)
                                        );
                                        D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }
                                }
                                //消耗的优惠券还原
                                $res_coupon = D("CouponActivities")->delCoupon($ary_res['o_id']);
                                if (!$res_coupon) {
                                    $orders->rollback();
                                    $this->error('优惠券还原失败');
                                    exit();
                                }
                                $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '财审成功，订单退款成功',
                                    'ol_text' => serialize($order_item)
                                );
                                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                                if (!$res_orders_log) {
                                    $orders->rollback();
                                    $this->error('更新失败');
                                    exit;
                                }
                            } elseif ($ary_res['or_refund_type'] == 2) {
                                $order_item['oi_refund_status'] = 5; //退货成功
                                $order_item['oi_update_time'] = date('Y-m-d H:i:s');
                                //退货单
                                $ary_oi_id = M('orders_refunds_items')->field(array('oi_id,ori_num'))->where(array('or_id' => $ary_res['or_id']))->select();
                                $orderItems = M('orders_items')->field('oi_id,o_id,g_id,g_sn,pdt_sn,pdt_id,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money')->where(array('o_id'=>$ary_res['o_id']))->select();
                                foreach ($ary_oi_id as $key) {
                                    if (false === M('orders_items')->where(array('oi_id' => $key['oi_id']))->save($order_item)) {
                                        $orders->rollback();
                                        $this->error("更新订单状态失败");
                                    }else{
                                        //库存返回
                                        foreach($orderItems as $item){
                                            if(empty($item['g_sn']) || empty($item['pdt_sn'])){
                                                $orders->rollback();
                                                $this->error("销量返回失败");exit;
                                            }
                                            if($item['oi_id'] == $key['oi_id']){
                                                $stock_res = M('goods_products')->where(array('g_sn'=>$item['g_sn'],'pdt_sn'=>$item['pdt_sn']))
                                                    ->data(array(
                                                        'pdt_update_time'=>date('Y-m-d H:i:s'),
                                                        'pdt_freeze_stock'=>array('exp',"pdt_freeze_stock-".$key['ori_num']),
                                                        'pdt_stock'=>array('exp',"pdt_stock+".$key['ori_num'])))
                                                    ->save();
                                                if(!$stock_res){
                                                    //$orders->rollback();
                                                    //$this->error("库存返回失败");exit;
                                                }
                                                $goods_num_res = M("goods_info")->where(array(
                                                    'g_id' => $item['g_id']
                                                ))->data(array(
                                                    'g_salenum' => array(
                                                        'exp',
                                                        'g_salenum - '.$key['ori_num']
                                                    )
                                                ))->save();
                                                if(false === $goods_num_res){
                                                    $orders->rollback();
                                                    $this->error("销量返回失败");exit;
                                                }
                                            }
                                        }
                                    }
                                }
                                if($stock_res){
                                    $stock_res = array(
                                        'o_id' => $ary_res['o_id'],
                                        'ol_behavior' => '订单退货库存返回成功',
                                        'ol_text' => serialize($ary_oi_id)
                                    );
                                    $stock_res_log = D('OrdersLog')->addOrderLog($stock_res);
                                    if(!$stock_res_log){
                                        $orders->rollback();
                                        $this->error('更新库存日志失败');
                                        exit;
                                    }
                                }
                                if(count($ary_oi_id) == count($orderItems)){
                                    // 冻结积分释放掉
                                    $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $ary_res['o_id']))->find();
                                    if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                                        $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                        if ($ary_member && $ary_member['freeze_point'] > 0) {
                                            //订单退货返还冻结积分日志
                                            $ary_log = array(
                                                'type'=>8,
                                                'consume_point'=> 0,
                                                'reward_point'=> $ary_orders['o_freeze_point']
                                            );
                                            $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                            if($ary_info['status'] == 1){
                                                $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_orders['o_freeze_point'];
                                                $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                                                if(!$res_member){
                                                    $orders->rollback();
                                                    $this->error('退货返回冻结积分失败');
                                                    exit;
                                                }
                                            }else{
                                                $orders->rollback();
                                                $this->error('退货返回冻结积分写日志失败');
                                                exit;
                                            }
                                        }else{
                                            $orders->rollback();
                                            $this->error('退货返回冻结积分没有找到要返回的用户冻结金额');
                                            exit;
                                        }
                                    }
                                    $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                                    if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结红包金额
                                        $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'bn_type'=>array('neq','0')))->find();
                                        if(!empty($ary_bonus) && is_array($ary_bonus)){
                                            $arr_bonus = array(
                                                'bt_id' => '4',
                                                'm_id'  => $ary_bonus['m_id'],
                                                'bn_create_time'  => date("Y-m-d H:i:s"),
                                                'bn_type' => '0',
                                                'bn_money' => $ary_bonus['bn_money'],
                                                'bn_desc' => '退货申请成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                                                'o_id' => $ary_bonus['o_id'],
                                                'bn_finance_verify' => '1',
                                                'bn_service_verify' => '1',
                                                'bn_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                            if($res_bonus === true){
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还红包成功,',
                                                    'ol_text' => serialize($ary_bonus)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还红包失败,红包金额为:'.$ary_bonus['bn_money'],
                                                    'ol_text' => serialize($ary_bonus)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
                                    $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                    if($card_status['CARDS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结储值卡金额
                                        $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ci_type'=>array('neq','0')))->find();
                                        if(!empty($ary_cards) && is_array($ary_cards)){
                                            $arr_cards = array(
                                                'ct_id' => '2',
                                                'm_id'  => $ary_cards['m_id'],
                                                'ci_create_time'  => date("Y-m-d H:i:s"),
                                                'ci_type' => '0',
                                                'ci_money' => $ary_cards['ci_money'],
                                                'ci_desc' => '退货申请成功返还储值卡金额：'.$ary_cards['ci_money'].'元',
                                                'o_id' => $ary_cards['o_id'],
                                                'ci_finance_verify' => '1',
                                                'ci_service_verify' => '1',
                                                'ci_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_cards = D('CardsInfo')->addCards($arr_cards);
                                            if($res_cards === true){
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还储值卡成功,',
                                                    'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还储值卡失败,储值卡金额为:'.$ary_cards['ci_money'],
                                                    'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
                                    /**
                                    $jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                                    if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                    //还原,支出冻结金币金额
                                    $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ji_type'=>array('neq','0')))->find();
                                    if(!empty($ary_jlb) && is_array($ary_jlb)){
                                    $arr_jlb = array(
                                    'jt_id' => '2',
                                    'm_id'  => $ary_jlb['m_id'],
                                    'ji_create_time'  => date("Y-m-d H:i:s"),
                                    'ji_type' => '0',
                                    'ji_money' => $ary_jlb['ji_money'],
                                    'ji_desc' => '退货申请成功返还金币金额：'.$ary_jlb['ji_money'].'元',
                                    'o_id' => $ary_jlb['o_id'],
                                    'ji_finance_verify' => '1',
                                    'ji_service_verify' => '1',
                                    'ji_verify_status' => '1',
                                    'single_type' => '2'
                                    );
                                    $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                    if($res_jlb === true){
                                    $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '退货返还金币成功,',
                                    'ol_text' => serialize($ary_jlb)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }else{
                                    $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '退货返还金币失败,金币金额为:'.$ary_jlb['ji_money'],
                                    'ol_text' => serialize($ary_jlb)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }
                                    }
                                    }
                                     **/
                                    //完全退货才退优惠券
                                    $coupon_info = M('orders')->where(array('o_id'=>$ary_res['o_id']))->field('o_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date')->find();
                                    if($coupon_info['o_coupon'] == '1' && $coupon_info['coupon_end_date'] >= date('Y-m-d H:i:s')){
                                        //作废
                                        $res = M('coupon')->where(array('c_sn'=>$coupon_info['coupon_sn'],'c_is_use'=>0))->data(array('c_is_use'=>'4'))->save();
                                        if($res){
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退优惠券成功,',
                                                'ol_text' => serialize($coupon_info)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }else{
                                            $ary_orders_log = array(
                                                'o_id' => $ary_res['o_id'],
                                                'ol_behavior' => '退优惠券失败,优惠券已不存在或已被使用,优惠券为:'.$coupon_info['coupon_sn'],
                                                'ol_text' => serialize($coupon_info)
                                            );
                                            D('OrdersLog')->addOrderLog($ary_orders_log);
                                        }
                                    }
                                    //消耗的优惠券还原
                                    $res_coupon = D("CouponActivities")->delCoupon($ary_res['o_id']);
                                    if (!$res_coupon) {
                                        $orders->rollback();
                                        $this->error('优惠券还原失败');
                                        exit();
                                    }
                                }else{
                                    //部分退货优惠券不退，其他金额等比例退回
                                    $oi_point_money = 0;
                                    $oi_bonus_money = 0;
                                    $oi_cards_money = 0;
                                    $oi_jlb_money = 0;
                                    foreach($orderItems as $item){
                                        foreach($ary_oi_id as $ary_oi_info){
                                            if($item['oi_id'] == $ary_oi_info['oi_id']){
                                                $oi_point_money = $oi_point_money+$item['oi_point_money'];
                                                $oi_bonus_money = $oi_bonus_money+$item['oi_bonus_money'];
                                                $oi_cards_money = $oi_cards_money+$item['oi_cards_money'];
                                                $oi_jlb_money = $oi_jlb_money+$item['oi_jlb_money'];
                                            }
                                        }
                                    }
                                    //var_dump($oi_point_money,$oi_bonus_money,$oi_cards_money,$oi_jlb_money);die();
                                    // 冻结积分释放掉
                                    $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_id')->where(array('o_id' => $ary_res['o_id']))->find();
                                    if (isset($oi_point_money) && $oi_point_money > 0 && $ary_orders['m_id'] > 0) {
                                        $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                                        $point_config = D('PointConfig')->getConfigs();
                                        if($point_config['consumed_points']>0){
                                            if ($ary_member && $ary_member['freeze_point'] > 0) {
                                                //订单退货返还冻结积分日志
                                                $ary_log = array(
                                                    'type'=>8,
                                                    'consume_point'=> 0,
                                                    'reward_point'=> $oi_point_money*$point_config['consumed_points']
                                                );
                                                $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                                if($ary_info['status'] == 1){
                                                    $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $ary_log['reward_point'];
                                                    $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                                                    if(!$res_member){
                                                        $orders->rollback();
                                                        $this->error('退货返回冻结积分失败');
                                                        exit;
                                                    }
                                                }else{
                                                    $orders->rollback();
                                                    $this->error('退货返回冻结积分写日志失败');
                                                    exit;
                                                }
                                            }else{
                                                $orders->rollback();
                                                $this->error('退货返回冻结积分没有找到要返回的用户冻结金额');
                                                exit;
                                            }
                                        }
                                    }
                                    if($oi_bonus_money>0){
                                        $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                                        if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                                            //还原,支出冻结红包金额
                                            if(!empty($oi_bonus_money) && $oi_bonus_money>0){
                                                $arr_bonus = array(
                                                    'bt_id' => '4',
                                                    'm_id'  => $ary_orders['m_id'],
                                                    'bn_create_time'  => date("Y-m-d H:i:s"),
                                                    'bn_type' => '0',
                                                    'bn_money' => $oi_bonus_money,
                                                    'bn_desc' => '退货申请成功返还红包金额：'.$oi_bonus_money.'元',
                                                    'o_id' => $ary_orders['o_id'],
                                                    'bn_finance_verify' => '1',
                                                    'bn_service_verify' => '1',
                                                    'bn_verify_status' => '1',
                                                    'single_type' => '2'
                                                );
                                                $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                                if($res_bonus === true){
                                                    $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还红包成功,',
                                                        'ol_text' => serialize($ary_bonus)
                                                    );
                                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                                }else{
                                                    $ary_orders_log = array(
                                                        'o_id' => $ary_res['o_id'],
                                                        'ol_behavior' => '退货返还红包失败,红包金额为:'.$oi_bonus_money,
                                                        'ol_text' => serialize($ary_bonus)
                                                    );
                                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                                }
                                            }
                                        }
                                    }

                                    $card_status = D('SysConfig')->getCfgByModule('SAVINGS_CARDS_SET');
                                    if($card_status['CARDS_AUTO_OPEN'] == 1){
                                        //还原,支出冻结储值卡金额

                                        if(!empty($oi_cards_money) && $oi_cards_money>0){
                                            $arr_cards = array(
                                                'ct_id' => '2',
                                                'm_id'  => $ary_orders['m_id'],
                                                'ci_create_time'  => date("Y-m-d H:i:s"),
                                                'ci_type' => '0',
                                                'ci_money' => $oi_cards_money,
                                                'ci_desc' => '退货申请成功返还储值卡金额：'.$oi_cards_money.'元',
                                                'o_id' => $ary_orders['o_id'],
                                                'ci_finance_verify' => '1',
                                                'ci_service_verify' => '1',
                                                'ci_verify_status' => '1',
                                                'single_type' => '2'
                                            );
                                            $res_cards = D('CardsInfo')->addCards($arr_cards);
                                            if($res_cards === true){
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还储值卡成功,',
                                                    'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }else{
                                                $ary_orders_log = array(
                                                    'o_id' => $ary_res['o_id'],
                                                    'ol_behavior' => '退货返还储值卡失败,储值卡金额为:'.$oi_cards_money,
                                                    'ol_text' => serialize($ary_cards)
                                                );
                                                D('OrdersLog')->addOrderLog($ary_orders_log);
                                            }
                                        }
                                    }
                                    
                                    /**$jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                                    if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                    //还原,支出冻结金币金额
                                    if(!empty($oi_jlb_money) && ($oi_jlb_money>0) && !empty($jlb_status['jlb_proportion'])){
                                    $arr_jlb = array(
                                    'jt_id' => '2',
                                    'm_id'  => $ary_orders['m_id'],
                                    'ji_create_time'  => date("Y-m-d H:i:s"),
                                    'ji_type' => '0',
                                    'ji_money' => $oi_jlb_money*$jlb_status['jlb_proportion'],
                                    'ji_desc' => '退货申请成功返还金币金额：'.$oi_jlb_money.'元',
                                    'o_id' => $ary_orders['o_id'],
                                    'ji_finance_verify' => '1',
                                    'ji_service_verify' => '1',
                                    'ji_verify_status' => '1',
                                    'single_type' => '2'
                                    );
                                    $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                    if($res_jlb === true){
                                    $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '退货返还金币成功,',
                                    'ol_text' => serialize($ary_jlb)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }else{
                                    $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '退货返还金币失败,金币金额为:'.$ary_jlb['ji_money'],
                                    'ol_text' => serialize($ary_jlb)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                    }
                                    }
                                    }**/
                                }

                                $ary_orders_log = array(
                                    'o_id' => $ary_res['o_id'],
                                    'ol_behavior' => '订单退货成功',
                                    'ol_text' => serialize($ary_post)
                                );
                                //检测是否需要退优惠券

                                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                                if (!$res_orders_log) {
                                    $orders->rollback();
                                    $this->error('更新失败');
                                    exit;
                                }
                            }
                            //如果单据为财审，更改订单状态
                            $ary_orders_log = array(
                                'o_id' => $ary_res['o_id'],
                                'ol_behavior' => '财审成功',
                                'ol_text' => serialize($arr_data)
                            );
                            $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                            $orders->commit();
                            $this->success($ary_rest['msg']);
                        } else {

                            $orders->rollback();
                            $this->error($ary_rest['msg']);
                        }
                    }
//                    echo "<pre>";print_r($ary_balance);exit;
                }
                $ol_behavior = '';
                if($ary_post['field'] == 'or_finance_verify'){
                    $ol_behavior = '财审成功';
                }else{
                    $ol_behavior = '客审成功';
                }
                //更新日志表
                $ary_orders_log = array(
                    'o_id' => $ary_res['o_id'],
                    'ol_behavior' => $ol_behavior,
                    'ol_text' => serialize($ary_post)
                );
                $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                if (!$res_orders_log) {
                    $orders->rollback();
                    $this->error('更新失败');
                    exit;
                }

                $orders->commit();
                $this->success("操作成功");
            } else {
                $orders->rollback();
                $this->error("操作失败");
            }
        } else {
            $orders->rollback();
            $this->error("参数错误,请重试...");
        }
    }


    public function purchaseList(){
        if($_GET['op_time']){
            $condition = 'op_time="'.$_GET['op_time'].'"';
        }
        else{
            $condition = 1;
        }
        $count = D('Orders')->GetPurchaseCount($condition);//179
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $goodsList = D('Orders')->GetPurchaseList($condition,$ary_field='',$group=array('op_id' => 'desc' ),$limit);
        //print_r($goodsList);
        //echo D('Orders')->getLastSql();
        foreach($goodsList as $key => $value) {
            $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
            $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
            $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
            $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
            $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");
        }
        $this->assign('goodsList',$goodsList);
        //print_r($goodsList);
        $this->assign("page", $page);
        //$this->assign("timeList", D('Orders')->GetPurchaseTime());
        $this->display();      
    }


    public function checkPurchase() {
        $str_op_id = $this->_post('op_id');
        if ($str_op_id) {
            M('', '', 'DB_CUSTOM')->startTrans();
            $array_purchases = explode(',',$str_op_id);
            $data['op_status'] = 1;
            foreach($array_purchases as $op_id){
                D('Orders')->UpdatePurchase($data,$op_id);
            }
            M('', '', 'DB_CUSTOM')->commit();
            $this->success('采购单审核成功');
        } else {
            $this->error('采购单审核失败');
            exit;
        }
    }
}

