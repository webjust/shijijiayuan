<?php

/**
 * 网银在线支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CHINABANK extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['pay_safe_code'] = $param['pay_safe_code'];
        $config['identity_id'] = $param['identity_id'];
        $this->config = $config;
    }

    /**
     * 支付订单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-01
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = parent::pay($str_oid);

        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        if(empty($int_ps_id)){
            $this->error('生成支付序列号失败!');
            return false;die();
        }
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //获取商户编号
        $v_mid = $this->config['identity_id'];
        //支付进金额
        $v_amount = sprintf('%.2f', $ary_order['o_all_price']);
        //商户KEY
        $key = $this->config['pay_safe_code'];
        //支付币种。默认RMB
        $v_moneytype = "CNY";
        //订单支付编号
        $v_oid = 'GY' . $int_ps_id;
        //回打地址
        $v_url = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
		$remark2 = '[url:='.U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true).']'; //服务器异步通知的接收地址。对应AutoReceive.php示例。必须要有[url:=]格式。
        //md5加密字符串
        $v_md5info = strtoupper(md5($v_amount . $v_moneytype . $v_oid . $v_mid . $v_url . $key));
        //https://Pay3.chinabank.com.cn/PayGate
        $data = array(
            'v_mid' => $v_mid,
            'v_amount' => $v_amount,
            'key' => $key,
            'v_moneytype' => $v_moneytype,
            'v_oid' => $v_oid,
            'v_url' => $v_url,
			'remark2'=>$remark2,
            'v_md5info' => $v_md5info
        );
        $this->assign($data);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:CHINABANK");
        exit;
    }

    /**
     * 预存款充值
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-25
     * @param float $flt_money 要充值的金额
     */
    public function charge($flt_money) {
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));

        //获取商户编号
        $v_mid = $this->config['identity_id'];
        //支付进金额
        $v_amount = sprintf('%.2f', $flt_money);
        //商户KEY
        $key = $this->config['pay_safe_code'];
        //支付币种。默认RMB
        $v_moneytype = "CNY";
        //订单支付编号
        $v_oid = 'GY' . $int_ps_id;
        //回打地址
        $v_url = U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true);
		$remark2 = '[url:='.U('Home/User/synChargeNotify?code=' . $this->code, '', true, false, true).']'; //服务器异步通知的接收地址。对应AutoReceive.php示例。必须要有[url:=]格式。
        //md5加密字符串
        $v_md5info = strtoupper(md5($v_amount . $v_moneytype . $v_oid . $v_mid . $v_url . $key));
        //https://Pay3.chinabank.com.cn/PayGate

        $data = array(
            'v_mid' => $v_mid,
            'v_amount' => $v_amount,
            'key' => $key,
            'v_moneytype' => $v_moneytype,
            'v_oid' => $v_oid,
            'v_url' => $v_url,
			'remark2'=>$remark2,
            'v_md5info' => $v_md5info
        );
        $this->assign($data);
        //订单预处理有事务此处要提交
        //M()->commit();
        $this->display("Ucenter:Pay:CHINABANK");
        exit;

    }

    /**
     * 响应网银在线通知
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-01
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $v_oid = $data['v_oid'];
        //自定义的流水号 GY+ps_id
        $int_ps_id = ltrim($v_oid, 'GY');
        //外部网关流水号
        $v_pstatus = $data['v_pstatus'];
        $v_amount = $data['v_amount'];
        $v_moneytype = $data['v_moneytype'];
        $v_md5str = $data['v_md5str'];
        $key = $this->config['pay_safe_code'];

        $str_sign = strtoupper(md5($v_oid . $v_pstatus . $v_amount . $v_moneytype . $key));

        //回调验证不通过
        if ($v_md5str != $str_sign) {
            return array('result' => false);
        }

        //支付状态
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
        if ($v_pstatus == 20) {
            $int_status = 1;
        } else {
            $int_status = 0;
            return array('result' => false);
        }

        //更改第三方流水单状态
        //网银在线无第三方网关的流水号
        //系统自动生成一个流水号
        $gw_code=date('YmdHis') . rand(10000, 99999);
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id,'ps_status'=>1))->getField('ps_id');
				if(!empty($ary_paymentSerial)){
					//已经存在相同支付流水号的
					//根据流水单号返回订单ID
					$o_id = $this->getOidByPsid($int_ps_id);					
					return array('result' => true,'o_id'=>$o_id);
				}
			}
		}
        $result = $this->updataPaymentSerial($int_ps_id, $int_status, $v_pstatus, $gw_code);

        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
        return array(
            'result'     => $result,
            'o_id'       => $o_id,
            'int_status' => $int_status,
            "total_fee"  => $v_amount,
            "gw_code"    => $gw_code,
            'm_id'       => $m_id,
            'int_ps_id'  =>$int_ps_id
        );
    }

}