<?php

/**
 * 用户中心第三方平台订单发货Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.0
 * @author Terry
 * @date 2013-1-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TrddeliverAction extends CommonAction {

    /**
     * 本控制器初始化
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-17
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 控制器默认页，默认跳转到一键发货
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-17
     */
    public function index() {
        $this->redirect(U('Ucenter/Trddeliver/pageList'));
    }

    /**
     * 第三方平台订单一键发货列表
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-17
     * @@return mixed array
     */
    public function pageList() {
        $this->getSubNav(2, 1, 50);
		//$start_time=date("Y-m-d H:i:s",mktime(0,0,0,date("m")-2,date("d"),date("Y")));
        $member = session('Members');
        $ary_get = $this->_get();
		$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-15,date("Y")));
		if (empty($ary_get['order_minDate'])) {
			$ary_get['order_minDate'] = $start_time;
		}
        $ary_result = array();
        $obj_shops = D("ThdShops");
        $ThdOrders = D("ThdOrders");
        $orders = D("Orders");
        $order = M("orders",C('DB_PREFIX'),'DB_CUSTOM');
        $arr_where = array();
        $arr_where['m_id'] = $member['m_id'];
        $ary_get['ts_source'] = empty($ary_get['ts_source']) ? '1' : $ary_get['ts_source'];
        $arr_where['ts_source'] = empty($ary_get['ts_source']) ? '1' : $ary_get['ts_source'];
        $ary_result['shop'] = $obj_shops->where($arr_where)->select();
        if (!empty($ary_get['tsid'])) {
            $ary_result['shops'] = $obj_shops->where(array('ts_id' => $ary_get['tsid']))->find();
        }
        if (!empty($ary_get['tsid']) && (int) $ary_get['tsid'] != '0') {
            /*******************拼接查询条件***************************************/
			if (!empty($ary_get['order_minDate']) && isset($ary_get['order_minDate'])) {
                if (!empty($ary_get['order_maxDate'])) {
                    $ary_get['order_maxDate'] = trim($ary_get['order_maxDate']);
                } else {
                    $ary_get['order_maxDate'] = date("Y-m-d H:i:s");
                }
                //按成交时间
                if ($ary_get['order_minDate']) {
                    $where['fx_thd_orders.to_created'] = array("between", array($ary_get['order_minDate'], $ary_get['order_maxDate']));
                }else{
					$where['fx_thd_orders.to_created'] = array("between", array($ary_get['order_minDate'], $ary_get['order_maxDate']));				
				}
				
				$where['fx_orders.o_create_time'] = array('egt',$ary_get['order_minDate']);
            }
            //按外部平台订单号
            if ($ary_get['tt_id']) {
                $where['fx_orders.o_source_id'] = trim($ary_get['tt_id']);
            }
            if ($arr_where['ts_source']) {
                $where['fx_thd_shops.ts_source'] = trim($arr_where['ts_source']);
            }
            //按买家昵称
            if ($ary_get['buyer']) {
                $where['fx_thd_orders.to_buyer_id'] = array('LIKE', '%' . trim($ary_get['buyer']) . '%');
            }
			if(empty($ary_get['order_minDate'])){
				$ary_get['order_minDate'] = $start_time;
			}
            //$where['fx_thd_orders.to_tt_status'] = 0;
			$where['fx_orders_items.oi_ship_status'] = array('eq',2);
			$where['fx_orders.o_trd_delivery_status'] = array('not in',array(3,99));
            $where['fx_thd_shops.ts_id'] = (int) $ary_get['tsid'];
            /*             * *****************获取订单总数************************************** */
            //$count = $ThdOrders->where($where)->count();

            /*$count = $order
                    ->join(' fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')
                    ->join(' fx_orders_delivery on fx_orders_delivery.o_id=fx_orders_items.o_id')
                    ->join(' fx_thd_orders on fx_orders.o_source_id=fx_thd_orders.to_oid')
                    ->join(' fx_thd_shops on fx_thd_shops.ts_id=fx_thd_orders.ts_id')
                    ->where($where)
                    ->count('DISTINCT fx_orders.o_id');*/
		$tmp_sql = "SELECT COUNT(DISTINCT fx_orders.o_id) AS tp_count 
                    FROM fx_orders
                    LEFT JOIN  fx_thd_orders on fx_thd_orders.to_oid=fx_orders.o_source_id 
                    WHERE (  (fx_thd_orders.to_created BETWEEN "."'".$ary_get['order_minDate']."' AND '".$ary_get['order_maxDate'] ."' ) ) ";
					if($ary_get['tt_id']){
						$tmp_sql .= " and fx_orders.o_source_id =".$where['fx_orders.o_source_id'] ;
					}
					if($ary_get['buyer']){
						$tmp_sql .= " and fx_thd_orders.to_buyer_id like % ".trim($ary_get['buyer']) ."%" ;
					}
					
                $tmp_sql .= " and fx_thd_orders.ts_id =".$where['fx_thd_shops.ts_id'].
                    " AND fx_orders.o_trd_delivery_status  not in (3,99)
                    AND exists(select 1 from fx_orders_items where fx_orders_items.o_id = fx_orders.o_id and fx_orders_items.oi_ship_status = 2 ) 
                    LIMIT 1 ";	//die();
		   $count = $order->query($tmp_sql);
		
            //echo "<pre>";print_r($count[0]['tp_count']);exit;
            $obj_page = new Page($count[0]['tp_count'], 10);
            /*$ary_result['orders'] = $order
                    ->field("fx_thd_shops.*,fx_orders.o_id,fx_orders.m_id,fx_orders.o_source_id,fx_thd_orders.to_created,fx_thd_orders.to_source,fx_orders_delivery.od_created,fx_thd_orders.to_oid,fx_orders_delivery.od_logi_id,fx_orders_delivery.od_logi_name,fx_orders_delivery.od_logi_no,fx_thd_shops.ts_shop_token")
                    ->join(' fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')
                    ->join(' fx_orders_delivery on fx_orders_delivery.o_id=fx_orders_items.o_id')
                    ->join(' fx_thd_orders on fx_orders.o_source_id=fx_thd_orders.to_oid')
                    ->join(' fx_thd_shops on fx_thd_shops.ts_id=fx_thd_orders.ts_id')
					->Distinct(true)->where($where)
                    ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                    ->select();*/
			$tmp_qurey_sql="select tmp_order.*,fx_orders_delivery.od_created,fx_orders_delivery.od_created,fx_orders_delivery.od_logi_id,fx_orders_delivery.od_logi_name,fx_orders_delivery.od_logi_no  from
                           (SELECT fx_thd_shops.*,fx_orders.o_id,fx_orders.o_source_id,fx_thd_orders.to_created,fx_thd_orders.to_source,fx_thd_orders.to_oid
                           FROM fx_orders
                           LEFT JOIN  fx_thd_orders on fx_thd_orders.to_oid=fx_orders.o_source_id
                           LEFT JOIN fx_thd_shops on (fx_thd_shops.ts_id=fx_thd_orders.ts_id)
                           WHERE (  (fx_thd_orders.to_created BETWEEN "."'".$ary_get['order_minDate']."' AND '".$ary_get['order_maxDate'] ."' ) ) ";
					
					if($ary_get['tt_id']){
						$tmp_qurey_sql .= " and fx_orders.o_source_id =".$where['fx_orders.o_source_id'] ;
					}
					if($ary_get['buyer']){
						$tmp_qurey_sql .= " and fx_thd_orders.to_buyer_id like % ".trim($ary_get['buyer']) ." % " ;
					}
					
           //$tmp_qurey_sql .= " and fx_thd_orders.ts_id = 179 
		   $tmp_qurey_sql .= " and fx_thd_orders.ts_id =".$where['fx_thd_shops.ts_id'].
                         "  AND fx_orders.o_trd_delivery_status  not in (3,99)
                           AND exists(select 1 from fx_orders_items where fx_orders_items.o_id = fx_orders.o_id and fx_orders_items.oi_ship_status = 2 ) 
                           limit 0,10) as tmp_order,fx_orders_delivery
                           where tmp_order.o_id=fx_orders_delivery.o_id ";
//echo $tmp_qurey_sql;die();
			$ary_result['orders'] = $order->query($tmp_qurey_sql);
//echo "<pre>";print_r($ary_result['orders']);die();
            $page = $obj_page->show();
            
              
            
            if (!empty($ary_result['orders']) && is_array($ary_result['orders'])) {
                
                $ary_token = json_decode($ary_result['shops']['ts_shop_token'], true);
                foreach ($ary_result['orders'] as $okey => $oval) {
                     if(!empty($oval['o_source_id'])){
							switch($oval['to_source']){
								case '2'://拍拍
								break;
								case '3'://京东
								   $obj_jd_api = new Jd( $ary_token['access_token']);
								   $ary_return = $obj_jd_api->getThdTradeDetial(array('tid'=>$oval['o_source_id']));
								   if(isset($ary_return['data']['order_state']) && !empty($ary_return['data']['order_state']) && $ary_return['data']['order_state']!= 'WAIT_SELLER_STOCK_OUT'){
									  //更新本地订单状态
									  $ary_orders_data['o_trd_delivery_status'] = 3;
									  M("orders",C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_source_id' => $oval['o_source_id']))->save($ary_orders_data);
									  $ary_result['orders'][$okey]['taobao_status'] = 1;
								   }								
								break;
								default:
								   $obj_taobao_api = new TaobaoApi( $ary_token['access_token']);
								   $ary_return = $obj_taobao_api->getTaobaoTradeGet(array('tid'=>$oval['o_source_id']));
								   if(isset($ary_return['data']['trade']['status']) && !empty($ary_return['data']['trade']['status']) && $ary_return['data']['trade']['status']!= 'WAIT_SELLER_SEND_GOODS'){
									  //更新本地订单状态
									  $ary_orders_data['o_trd_delivery_status'] = 3;
									  M("orders",C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_source_id' => $oval['o_source_id']))->save($ary_orders_data);
									  $ary_result['orders'][$okey]['taobao_status'] = 1;
								   }	
								break;
							} 
                     }
					$ary_result['orders'][$okey]['status'] = $orders->getOrderItmesStauts('oi_ship_status', $oval);
                }
            }
        }
        //echo "<pre>";print_r($ary_get);exit;
        $this->assign("page", $page);
        $this->assign("data", $ary_result);
        $this->assign("filter", $ary_get);
        $this->display();
    }

    /**
     * 同步第三方订单的发货信息到第三方平台
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-17
     * @@return mixed array
     */
    public function synDeliveryOrderToTrd() {
        $ary_res = array('success' => '0', 'msg' => '');
        $ary_post = $this->_post();
        $order = M("orders",C('DB_PREFIX'),'DB_CUSTOM');
        $obj_shops = D("ThdShops");
        //获取店铺信息
        $ary_shop = $obj_shops->where(array('ts_id' => $ary_post['ts_id']))->find();
        if (!empty($ary_shop) && is_array($ary_shop)) {
            if ($ary_shop['ts_source'] == '1') {
                $str_platform = 'taobao';
            } else if ($ary_shop['ts_source'] == '2') {
                $str_platform = 'paipai';
            }else if ($ary_shop['ts_source'] == '3'){
				$str_platform = 'jd';
			}
            $arr_order = $order->field("fx_orders_delivery.*")->join(" fx_orders_delivery on fx_orders_delivery.o_id=fx_orders.o_id")->where(array('fx_orders.o_id' => $ary_post['o_id']))->find();
            //echo "<pre>";print_r($arr_order);exit;
            $ary_token = json_decode($ary_shop['ts_shop_token'], true);
            $obj_api = Apis::factory($str_platform, $ary_token);
            if (!empty($arr_order['od_logi_no'])) {
				if($str_platform == 'jd'){
					if(isset($arr_order['od_logi_id'])){
						$str_jd_name = M("logistic_corp",C('DB_PREFIX'),'DB_CUSTOM')->where(array('lc_id'=>$arr_order['od_logi_id']))->getField('lc_jd_name');
						$arr_order['jd_name'] = $str_jd_name;
					}
	                $ary_result = $obj_api->logisticsSend($ary_post['tt_id'], $arr_order['od_logi_no'], $arr_order, $ary_post['type']);		
				}else{
					$ary_result = $obj_api->logisticsSend($ary_post['tt_id'], $arr_order['od_logi_no'], $arr_order['od_logi_name'], $ary_post['type']);					
				}

				if (TRUE == $ary_result['status'] ) {
					//此处的条件改为，如果发货成功，或者第三方平台已经发过货了，此处均修改发货状态
					$ary_orders_data['o_trd_delivery_status'] = 3;
					$res_member = $order->where(array('o_source_id' => $ary_post['tt_id']))->save($ary_orders_data);
					$ary_res['success'] = 1;
					$ary_res['msg'] = '发货成功！';
				}else {
                    if(empty($ary_result['sub_msg'])) $ary_result['sub_msg'] = '更新发货状态失败!';
					$ary_res['msg'] = '发货失败！原因为：' . $ary_result['sub_msg'];
				}
			}else{
				$ary_res['msg'] = '发货失败！原因为：发货单号(运单号)不能为空！';
			}
        }else{
			$ary_res['msg'] = '对应店铺不存在，发货失败';
		}
        echo json_encode($ary_res);
        exit;
    }

    /**
     * 批量发货
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2013-1-17
     * @@return mixed array
     */
    public function batchTrddeliver() {
        $ary_res = array('success' => '0', 'msg' => '');
        $ary_post = $this->_post();
        $order = M("orders",C('DB_PREFIX'),'DB_CUSTOM');
        $obj_shops = D("ThdShops");
        if (!empty($ary_post) && is_array($ary_post)) {
            foreach ($ary_post['order'] as $okey => $oval) {
                $ary_shop = $obj_shops->where(array('ts_id' => $oval['ts_id']))->find();
                if (!empty($ary_shop) && is_array($ary_shop)) {
                    if ($ary_shop['ts_source'] == '1') {
                        $str_platform = 'taobao';
                    } else if ($ary_shop['ts_source'] == '2') {
                        $str_platform = 'paipai';
                    }else  if ($ary_shop['ts_source'] == '3') {
						 $str_platform = 'jd';
					}
                    $arr_order = $order->field("fx_orders_delivery.*")->join(" fx_orders_delivery on fx_orders_delivery.o_id=fx_orders.o_id")->where(array('fx_orders.o_id' => $oval['o_id']))->find();
                    $ary_token = json_decode($ary_shop['ts_shop_token'], true);
                    $obj_api = Apis::factory($str_platform, $ary_token);
                    if (!empty($arr_order['od_logi_no'])) {
						//如果是京东
						if($str_platform  == 'jd'){
							if(isset($arr_order['od_logi_id'])){
								$str_jd_name = M("logistic_corp",C('DB_PREFIX'),'DB_CUSTOM')->where(array('lc_id'=>$arr_order['od_logi_id']))->getField('lc_jd_name');
								$arr_order['jd_name'] = $str_jd_name;
							}
							 $ary_result = $obj_api->logisticsSend($oval['tt_id'], $arr_order['od_logi_no'], $arr_order, $oval['type']);
						}else{
							 $ary_result = $obj_api->logisticsSend($oval['tt_id'], $arr_order['od_logi_no'], $arr_order['od_logi_name'], $oval['type']);
						}
                        //$ary_result = $obj_api->logisticsSend($oval['tt_id'], $arr_order['od_logi_no'], $arr_order['od_logi_name'], $oval['type']);
						if (true == $ary_result['status']) {
							//此处的条件改为，如果发货成功，此处均修改发货状态
							$ary_orders_data['o_trd_delivery_status'] = 3;
							$res_member = $order->where(array('o_source_id' => $oval['tt_id']))->save($ary_orders_data);
							$ary_res['success'] = 1;
							$ary_res['msg'] = '发货成功！';
						} else {
                            if(empty($ary_result['sub_msg'])) $ary_result['sub_msg'] = '更新发货状态失败!';
							$ary_res['msg'] = '发货失败！原因为：' . $ary_result['sub_msg'];
						}
					}else{
						$ary_res['msg'] = '发货失败！原因为：'.$oval['tt_id'].'发货单号(运单号)不能为空！';
					}
                } else {
                    $ary_res['msg'] = '对应店铺不存在，发货失败';
                }
            }
        } else {
            $ary_res['msg'] = '提交数据有误';
        }
        echo json_encode($ary_res);exit;
    }
	
	/**
     * 标记为已处理
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.8.3
     * @since stage 1.5
     * @modify 2015-05-05
     * @@return mixed array
     */
    public function doDeliveryOrderToSuccess() {
        $ary_res = array('success' => '0', 'msg' => '');
        $ary_post = $this->_post();
        $order = M("orders",C('DB_PREFIX'),'DB_CUSTOM');
		if(empty($ary_post['o_id'])){
			$ary_res['msg'] = '订单号不存在';
		}
		$res = $order->where(array('o_id'=>$ary_post['o_id']))->save(array('o_trd_delivery_status'=>99,'o_update_time'=>date('Y-m-d H:i:s')));
		if($res){
			$ary_res['success'] = 1;
			$ary_res['msg'] = '标记成功';
        }else{
			$ary_res['msg'] = '标记失败';
		}
        echo json_encode($ary_res);
        exit;
    }
	
}