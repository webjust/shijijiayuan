<?php

/**
 * 前台支付回调通知
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PaymentAction extends CommonAction {

    /**
     * 接收第三方支付时的返回信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-23
     */
    public function synPayReturn() {
        //$data = $this->_param();
        $data = $_REQUEST;
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);
        unset($data['code']);
        
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
        writeLog(json_encode($data),"order_pay.log");
        if (false == $ary_pay) {
            $this->error('不存在的支付方式');
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);
        //获取最后一次支付的付款类型
        if(!empty($result['o_id'])){
            $result['o_id'] = trim($result['o_id'],"订单编号:");
        }
        $Order = D('Orders');
        $ary_order = $Order->where(array('o_id' => $result['o_id']))->find();
        $ary_pay_ser = M('payment_serial')->where(array('o_id'=>$result['o_id'],'ps_status'=>array('neq',0)))->order('ps_update_time desc')->select();
        if($ary_pay_ser[0]['pay_type'] == '1'){
            //定金支付
            $ary_order['o_pay_status'] = 3;
        }else{
            $ary_order['o_pay_status'] = 1;
        }
        
        
        writeLog(json_encode($ary_order),"order_pay.log");
        // writeLog(var_export($result),"result.log");
        M('','','DB_CUSTOM')->startTrans();
        if ($result['result']) {
            $jumpUrl = U('Ucenter/Orders/PaymentSuccess', array('oid' => $result['o_id']));
            if(check_wap()){
                $jumpUrl = U('Wap/Orders/PaymentSuccess', array('oid' => $result['o_id']));
            }
			if(empty($result['m_id']) && ($ary_order['o_pay_status'] == 1 || $ary_order['o_pay_status'] == 3)){
                //已经存在相同流水号的
				M('','','DB_CUSTOM')->commit();
				//完成并显示，返回到订单信息页面
				$this->redirect($jumpUrl);		
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
                    $gp_now_number = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gp_id'=>$gp_id))->getField('gp_now_number');
                    M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id' => $gp_id))->save(array('gp_now_number' =>$gp_now_number + $ary_tmp_order_item['oi_nums']));
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
/*                 $ary_order['o_pay_status'] = 1; */
                //$ary_order['o_source_id'] = $result['gw_code'];
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    M('','','DB_CUSTOM')->commit();
                    //完成并显示，返回到订单信息页面
                    $this->redirect($jumpUrl);
                }
            }else if($result['int_status'] == 2){
                //通过双接口中的担保交易
                $ary_order['o_pay'] = $result['total_fee'];
                /* $ary_order['o_pay_status'] = 1; */
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    M('','','DB_CUSTOM')->commit();
                    //完成并显示，返回到订单信息页面
                    $this->redirect($jumpUrl);
                }
            }else if($result['int_status'] == 3){
                //通过双接口中的即时到帐交易
                $ary_order['o_pay'] = $result['total_fee'];
                /* $ary_order['o_pay_status'] = 1; */
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);
                writeLog(json_encode($ary_pay),"order_pay.log");
                if (!$result_order['result']) {
                    //后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    M('','','DB_CUSTOM')->commit();
                    //完成并显示，返回到订单信息页面
                    $this->redirect($jumpUrl);
                }
            }
        } else {
			if(isset($data['out_trade_no'])){
				$out_trade_no = explode('-',$data['out_trade_no']);
				$int_ps_id = $out_trade_no[1];
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
				if(!empty($ary_paymentSerial) && $ary_paymentSerial['ps_status'] == 1){
					 if(check_wap()){
						$jumpUrl = U('Wap/Orders/PaymentSuccess', array('oid' => $ary_paymentSerial['o_id']));
						$this->redirect($jumpUrl);
					}
				}
			}else{
				$msg = !empty($result['msg'])?$result['msg']:'错误访问';
				$this->error($msg);
			}
        }
    }

    /**
     * 接收第三方充值时的返回信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-25
     */
    public function synChargeReturn() {
        $data = $_REQUEST;
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);
        unset($data['code']);

        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();

        if (false == $ary_pay) {
            $this->error('不存在的支付方式');
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));

        $result = $Pay->respond($data);
        M('','','DB_CUSTOM')->startTrans();

        if ($result['result']) {
			//已充值完成不允许再次充值
			if(empty($result['m_id'])){
                //已经存在相同流水号的
                M('','','DB_CUSTOM')->commit();
                $this->success('支付成功', U('Ucenter/Financial/pageDepositList'));exit;
			}
            //检查请求签名正确
            //获取会员信息
            $ary_member = D('Members')->where(array('m_id' => $result['m_id']))->find();

            //已经充值过的不能重复充值
            $where = array('ra_payment_sn'=>$result['gw_code'],'ra_payment_method'=>$ary_pay['pc_custom_name']);
            $int_running_find = D('RunningAccount')->where($where)->find();
            if(false != $int_running_find){
                //已经存在相同流水号的
                M('','','DB_CUSTOM')->commit();
                $this->success('支付成功', U('Ucenter/Financial/pageDepositList'));exit;
            }
			//已充值完成不允许再次充值
			/**
			if(!empty($result['int_ps_id'])){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$result['int_ps_id'],'ps_status'=>1))->getField('ps_id');
				if(!empty($ary_paymentSerial)){
					//已经存在相同流水号的
					M('','','DB_CUSTOM')->rollback();
					$this->success('支付成功', U('Ucenter/Financial/pageDepositList'));exit;			
				}
			}
			**/
            if($result['int_status'] == 1 || $result['int_status'] == 3){
                //直接付款成功，交易成功
                $ary_where_account = array(
                    'm_id' => $result['m_id'],
                    'ra_money' => $result['total_fee'],
                    'ra_type' => 0, //充值
                    'ra_payment_method' => $ary_pay['pc_custom_name'],
                    'ra_before_money' => (float) $ary_member['m_balance'],
                    'ra_after_money' => (float) $ary_member['m_balance'] + (float) $result['total_fee'],
                    'ra_payment_sn' => $result['gw_code']
                );
                $RunningAccount_info = D('RunningAccount')->where($ary_where_account)->find();
                if(!isset($RunningAccount_info) && empty($RunningAccount_info)){
                    $ary_where_account['ra_create_time'] = date('Y-m-d h:i:s');
                    $RunningAccount_info = D('RunningAccount')->add($ary_where_account);
                }

                if (false === $RunningAccount_info) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error('充值流水账添加错误');
                } else {
                    $ary_where_balance=array(
                        'm_id'=>$result['m_id'],
                        'bi_money'=>$result['total_fee'],
                        'bi_sn'=>$result['gw_code'],
                        'bt_id'=>3,
                        'bi_verify_status'=>1,
                        'bi_service_verify'=>1,
                        'bi_finance_verify'=>1,
                        'bi_desc'=>$ary_pay['pc_custom_name'].'线上充值'
                    );
                    $BalanceInfo_info = D('BalanceInfo')->where($ary_where_balance)->find();
                    writeLog(D('BalanceInfo')->getLastSql(),'BalanceInfo.log');
                    if(!isset($BalanceInfo_info) && empty($BalanceInfo_info)){
                        $ary_where_balance['bi_create_time'] = date('Y-m-d H:i:s');
                        $ary_where_balance['bi_update_time'] = date('Y-m-d H:i:s');
                        $BalanceInfo_info = D('BalanceInfo')->add($ary_where_balance);
                        writeLog(D('BalanceInfo')->getLastSql(),'BalanceInfo.log');
                    }
                    if(false === $BalanceInfo_info){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error('结余款调整单添加失败！');exit;
                    }
                    //更新用户预存款
                    $updata_data['m_balance']= $ary_where_account['ra_after_money'];
                    M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_where_account['m_id']))->save($updata_data);
                    M('','','DB_CUSTOM')->commit();
                    $this->success('支付成功', U('Ucenter/Financial/pageDepositList'));exit;
                }
            }else if($result['int_status'] == 2){
				/**隐藏
                //担保交易，等待管理员付款（生成结余款调整单）
                $ary_where_balance=array(
                    'm_id'=>$result['m_id'],
                    'bi_money'=>$result['total_fee'],
                    'bi_sn'=>$result['gw_code'],
                    'bt_id'=>3,
                    'bi_verify_status'=>0,
                    'bi_service_verify'=>0,
                    'bi_finance_verify'=>0,
                    'bi_desc'=>$ary_pay['pc_custom_name'].'线上充值'
                );
                $BalanceInfo_info = D('BalanceInfo')->where($ary_where_balance)->find();
                if(!isset($BalanceInfo_info) && empty($BalanceInfo_info)){
                    $ary_where_balance['bi_create_time'] = date('Y-m-d H:i:s');
                    $ary_where_balance['bi_update_time'] = date('Y-m-d H:i:s');
                    $BalanceInfo_info = D('BalanceInfo')->add($ary_where_balance);
                }
                if(false === $BalanceInfo_info){
                    M('','','DB_CUSTOM')->rollback();
                    $this->error('结余款调整单添加失败！');
                }
				**/
                M('','','DB_CUSTOM')->commit();
                $this->success('付款成功，等待发货', U('Ucenter/Financial/pageDepositList'));
            }
        } else {
            M('','','DB_CUSTOM')->rollback();
            $this->error('错误访问');
        }
    }

}
