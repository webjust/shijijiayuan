<?php

/**
 * 预存款支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class DEPOSIT extends Payments implements IPayments {

    /**
     * 支付订单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-23
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数 5团购 0普通商品
     */
    public function pay($str_oid,$type = 0,$o_pay = 0.000,$pay_stat) {
        if($o_pay == 0.000){
            return array('result' => true, 'message' => '支付成功');
        }
        $ary_order = parent::pay($str_oid); 
        //查询出订单用户结余款信息
		$session_member = $_SESSION['Members']; 
		if($session_member['m_id']==''){
			$session_member['m_id']=$ary_order['m_id'];
		}
        $search_field=array('m_balance');
        $where = array('m_id' => $session_member['m_id']);
        $member_balance = D('Members')->GetBalance($where,$search_field);
        
        if($type == '5'){//团购订单，支付金额分为全额支付、定金支付、尾款支付
            if ($o_pay > $member_balance['m_balance']) {//判断余额
                return array('result' => false, 'message' => '余额不足，请先进行充值~');
            }
            $float_m_balance = $member_balance['m_balance'] - $o_pay;
        }else{
            if ($ary_order['o_all_price'] > $member_balance['m_balance']) {//判断余额
                return array('result' => false, 'message' => '余额不足，请先进行充值~');
            }
            $float_m_balance = $member_balance['m_balance'] - $ary_order['o_all_price'];
        }
        
        
        $int_return_members = D('Members')->UpdateBalance($session_member['m_id'],$float_m_balance);
        //返回扣款结果
        if (false == $int_return_members) {
            return array('result' => false, 'message' => '支付失败');
        } else {
            $ary_tmp_order_item = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_order['o_id']))->find();
            $is_add_num = 0;
            if($pay_stat == 0){
                $is_add_num = 1;
            }elseif($pay_stat == 1){
                $is_add_num = 1;
            }
            if($ary_tmp_order_item['oi_type'] == 5){
                //团购
                /* if($is_add_num == 1){
                    $gp_id = $ary_tmp_order_item['fc_id'];
                    $gp_now_number = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gp_id'=>$gp_id))->getField('gp_now_number');
                    M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id' => $gp_id))->save(array('gp_now_number' =>$gp_now_number + $ary_tmp_order_item['oi_nums']));
                } */
            }elseif($ary_tmp_order_item['oi_type'] == 8){
                //预售
                /*if($is_add_num == 1){
                    $p_id = $ary_tmp_order_item['fc_id'];
                    $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' =>$gp_id))->getField('p_now_number');
                    M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' => $presale ['p_id']))->save(array('p_now_number' => $p_now_number + $ary_tmp_order_item ['oi_nums']));
                }*/
            }
            return array('result' => true, 'message' => '支付成功','info'=>array('success'=>'ok','code'=>'DEPOSIT'));
        }
    }

}
