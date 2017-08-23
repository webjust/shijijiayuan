<?php

class IcbcAction extends GyfxAction {
	function test() {
		$data = $_REQUEST;
$data = array (
  'merVAR' => '201510300937518350-425',
  'notifyData' => 'PD94bWwgIHZlcnNpb249IjEuMCIgZW5jb2Rpbmc9IkdCSyIgc3RhbmRhbG9uZT0ibm8iID8+PEIyQ1Jlcz48aW50ZXJmYWNlTmFtZT5JQ0JDX1BFUkJBTktfQjJDPC9pbnRlcmZhY2VOYW1lPjxpbnRlcmZhY2VWZXJzaW9uPjEuMC4wLjExPC9pbnRlcmZhY2VWZXJzaW9uPjxvcmRlckluZm8+PG9yZGVyRGF0ZT4yMDE1MTAzMDA5Mzc1Mzwvb3JkZXJEYXRlPjxjdXJUeXBlPjAwMTwvY3VyVHlwZT48bWVySUQ+MjAxMUVEMjAwMTE2MDM8L21lcklEPjxzdWJPcmRlckluZm9MaXN0PjxzdWJPcmRlckluZm8+PG9yZGVyaWQ+MjAxNTEwMzAwOTM3NTE4MzUwLTQyNTwvb3JkZXJpZD48YW1vdW50PjE8L2Ftb3VudD48aW5zdGFsbG1lbnRUaW1lcz4xPC9pbnN0YWxsbWVudFRpbWVzPjxtZXJBY2N0PjIwMTEwMjgwMTkyMDAwNjQ0NzY8L21lckFjY3Q+PHRyYW5TZXJpYWxObz5IRVowMDAwMDc0MzA1MTU3Mzc8L3RyYW5TZXJpYWxObz48L3N1Yk9yZGVySW5mbz48L3N1Yk9yZGVySW5mb0xpc3Q+PC9vcmRlckluZm8+PGN1c3RvbT48dmVyaWZ5Sm9pbkZsYWc+MDwvdmVyaWZ5Sm9pbkZsYWc+PEpvaW5GbGFnPjwvSm9pbkZsYWc+PFVzZXJOdW0+PC9Vc2VyTnVtPjwvY3VzdG9tPjxiYW5rPjxUcmFuQmF0Y2hObz48L1RyYW5CYXRjaE5vPjxub3RpZnlEYXRlPjIwMTUxMDMwMDkzODQzPC9ub3RpZnlEYXRlPjx0cmFuU3RhdD4xPC90cmFuU3RhdD48Y29tbWVudD69u9LXs8m5pqOhPC9jb21tZW50PjwvYmFuaz48L0IyQ1Jlcz4=',
  'signMsg' => 'bxVQ69mJ+kKaAKFjEnFrVsofde4qotuo5AX/vvdbel3x9tIp5jjUrzXuTWZQ8jAYHTNB4IlIEsLpKq6twT3EEgwLbR5H6evfSQMh3/vU98jVaXylM+KQFhI7WKuttdZJ6xbpB2FdSEou2E/KbAH4ltF/DRFeq6NFGmwLGoVi4+A=',
  'code' => 'ICBC',
  '_URL_' => 
  array (
    0 => 'Home',
    1 => 'Icbc',
    2 => 'test',
    3 => 'code',
    4 => 'ICBC',
  ));
		$code = $data['code'];
		//过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
		//验证支付方式是否存在
		if (false === $ary_pay) {
            $this->error('不存在的支付方式');
			die;
        }
		$Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);		
		//$data=array('上海市','宝山市');
		writeLog("REQUEST: ".var_export($data,true),date('Ymd')."icbc.log");
	}
	
	
    /**
     * 接收第三方支付时的异步通知
     * 注：异步通知无需登录
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-23
     */

    public function synPayNotify() {
        $data = $_REQUEST;	
		writeLog("REQUEST: ".json_encode($data),date('Ymd')."order_status.log");
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);        
        writeLog(json_encode($data),date('Ymd')."order_status.log");
		unset($data['code']);
		writeLog("SERVER: ".json_encode($_SERVER),date('Ymd')."order_status.log");
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
        if (false === $ary_pay) {
            $this->error('不存在的支付方式');
			die;
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);
        M('','','DB_CUSTOM')->startTrans();
        $result['o_id'] = trim($result['o_id'],"订单编号:");
        $ary_member = D('Members')->where(array('m_id'=>$result['m_id']))->find();
        if ($result['result']) {
            //检查请求签名正确
            $Order = D('Orders');
            $ary_order = $Order->where(array('o_id' => $result['o_id']))->find();
            writeLog(json_encode($result),date('Ymd')."order_status.log");
            writeLog(json_encode($ary_order),date('Ymd')."order_status.log");	
			if(empty($result['m_id']) && $ary_order['o_pay_status'] == 1){
                //已经存在相同流水号的
				M('','','DB_CUSTOM')->commit();
				echo "success";
				die();
			}
			if($ary_order['o_status'] != 1) {
				writeLog("当前订单状态【o_status={$ary_order['o_status']}】不允许做此操作",date('Ymd')."order_status.log");	
				die;				
			}elseif($ary_order['o_pay_status'] == 1) {
				writeLog("订单为已支付，丢弃此次通知【{$ary_order['o_id']}】",date('Ymd')."order_status.log");	
				die;				
			}
            if ($result['int_status'] == 1) {
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),date('Ymd')."order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }else if($result['int_status'] == 2){
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),date('Ymd')."order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }

                }
            }elseif($result['int_status'] == 3){
		//在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {

                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),date('Ymd')."order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }
            else if($result['int_status'] == 5){//创建交易

                //订单日志记录
                if(D('OrdersLog')->where(array('o_id'=>$result['o_id'],'ol_behavior'=>'创建'.$ary_pay['pc_custom_name'].'交易','ol_uname'=>$ary_member['m_name']))->count() == 0){
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '创建'.$ary_pay['pc_custom_name'].'交易',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),date('Ymd')."order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }   else{
                //echo D('OrdersLog')->getLastSql();exit;
                    M('','','DB_CUSTOM')->commit();
                    echo "success";
                    die();
                }


            }
//            elseif($result['int_status'] == 4){
//                //订单日志记录
//                $ary_orders_log = array(
//                    'o_id' => $result['o_id'],
//                    'ol_behavior' => '支付已在第三方创建--等待支付',
//                    'ol_uname' => $ary_member['m_name'],
//                    'ol_create' => date('Y-m-d H:i:s')
//                );
//                $res_orders_log = D('OrdersLog')->add($ary_orders_log);
//                writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
//                if (!$res_orders_log) {
//                    M('','','DB_CUSTOM')->rollback();
//                    $this->error('订单日志记录失败');
//                    exit;
//                }else{
//                    M('','','DB_CUSTOM')->commit();
//                }
//            }
            else{
                /* $arr_result = D('Orders')->where(array('o_id'=>$result['o_id']))->data(array('o_status'=>'5'))->save();
                if (!$arr_result) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error("确认收货失败");
                } else {
                    M('','','DB_CUSTOM')->commit();
                    echo "success";
                    die();
                } */
            }
        }else{
            $this->error('支付失败');
        }
    }
	
}
?>
