<?php

/**
 * 快钱支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class KUAIQIAN extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['kuaiqian_account'] = $param['kuaiqian_account'];
        $config['pay_safe_code'] = $param['pay_safe_code'];
        $this->config = $config;
    }

    /**
     * 支付订单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-18
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = parent::pay($str_oid);

        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }

        //支付信息 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $merchantAcctId = trim($this->config['kuaiqian_account']);              //人民币网关账户号
        $key = trim($this->config['pay_safe_code']);                            //人民币网关密钥
        $inputCharset = 1;                                                     //字符集.固定选择值。可为空。只能选择1、2、3.1代表UTF-8; 2代表GBK; 3代表gb2312 默认值为1
        $pageUrl = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);  //直接回跳地址
        $bgUrl = '';                                                            //服务器接受支付结果的后台地址.与[pageUrl]不能同时为空。必须是绝对地址。
        $version = 'v2.0';                                                      //网关版本.固定值 快钱会根据版本号来调用对应的接口处理程序。
        $language = 1;                                                         //语言种类.固定选择值。 只能选择1、2、3  1代表中文；2代表英文 默认值为1
        $signType = 1;                                                         //签名类型 不可空 固定值 1:md5
        $payerName = '管易全网分销系统在线支付';                                   //支付人姓名  //可为中文或英文字符
        $payerContactType = '';                                                 //支付人联系方式类型.固定选择值 只能选择1  1代表Email
        $payerContact = '';                                                     //支付人联系方式 只能选择Email或手机号
        $orderId = 'GY' . $int_ps_id;                                           //商户订单号 不可空
        $orderAmount = sprintf('%.2f', $ary_order['o_all_price']) * 100;               //商户订单金额 不可空
        $orderTime = date('YmdHis');                                            //订单提交时间 14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位] 以分为单位，必须是整型数字
        $productName = '';                                                      //商品名称
        $productNum = '';                                                       //商品数量
        $productId = '';                                                        //商品代码
        $productDesc = '';                                                      //商品描述
        $ext1 = $int_ps_id;                                                    //扩展字段1
        $ext2 = '';                                                             //扩展字段2
        $payType = '00';                                                        //支付方式 不可空
        $bank_id = '';
        $redoFlag = '0';                                                        //同一订单禁止重复提交标志
        $pid = '';                                                              //合作伙伴在快钱的用户编号
        $param = array(
            'inputCharset' => $inputCharset,
            'pageUrl' => $pageUrl,
            'bgUrl' => $bgUrl,
            'version' => $version,
            'language' => $language,
            'signType' => $signType,
            'merchantAcctId' => $merchantAcctId,
            'payerName' => $payerName,
            'payerContactType' => $payerContactType,
            'payerContact' => $payerContact,
            'orderId' => $orderId,
            'orderAmount' => $orderAmount,
            'orderTime' => $orderTime,
            'productName' => $productName,
            'productNum' => $productNum,
            'productId' => $productId,
            'productDesc' => $productDesc,
            'ext1' => $ext1,
            'ext2' => $ext2,
            'payType' => $payType,
            'bankId' => $bank_id,
            'redoFlag' => $redoFlag,
            'pid' => $pid,
            'key' => $key
        );
        /* 生成加密签名串 请务必按照如下顺序和规则组成加密串！ */
        $signmsgval = $this->signMsgval($param);
        $signmsg = strtoupper(md5($signmsgval));    //签名字符串 不可空
        unset($param['key']);
        $this->assign('data', $param);
        $this->assign('signmsg', $signmsg);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:KUAIQIAN");
        exit;
    }

    /**
     * 预存款充值
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-18
     * @param float $flt_money 要充值的金额
     */
    public function charge($flt_money) {
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));

        //支付信息 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $merchantAcctId = trim($this->config['kuaiqian_account']);              //人民币网关账户号
        $key = trim($this->config['pay_safe_code']);                            //人民币网关密钥
        $inputCharset = 1;                                                     //字符集.固定选择值。可为空。只能选择1、2、3.1代表UTF-8; 2代表GBK; 3代表gb2312 默认值为1
        $pageUrl = U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true);  //直接回跳地址
        $bgUrl = '';                                                            //服务器接受支付结果的后台地址.与[pageUrl]不能同时为空。必须是绝对地址。
        $version = 'v2.0';                                                      //网关版本.固定值 快钱会根据版本号来调用对应的接口处理程序。
        $language = 1;                                                         //语言种类.固定选择值。 只能选择1、2、3  1代表中文；2代表英文 默认值为1
        $signType = 1;                                                         //签名类型 不可空 固定值 1:md5
        $payerName = '管易全网分销系统在线支付';                                   //支付人姓名  //可为中文或英文字符
        $payerContactType = '';                                                 //支付人联系方式类型.固定选择值 只能选择1  1代表Email
        $payerContact = '';                                                     //支付人联系方式 只能选择Email或手机号
        $orderId = 'GY' . $int_ps_id;                                           //商户订单号 不可空
        $orderAmount = sprintf('%.2f', $flt_money) * 100;                       //商户订单金额 不可空
        $orderTime = date('YmdHis');                                            //订单提交时间 14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位] 以分为单位，必须是整型数字
        $productName = '';                                                      //商品名称
        $productNum = '';                                                       //商品数量
        $productId = '';                                                        //商品代码
        $productDesc = '';                                                      //商品描述
        $ext1 = $int_ps_id;                                                    //扩展字段1
        $ext2 = '';                                                             //扩展字段2
        $payType = '00';                                                        //支付方式 不可空
        $bank_id = '';
        $redoFlag = '0';                                                        //同一订单禁止重复提交标志
        $pid = '';                                                              //合作伙伴在快钱的用户编号
        $param = array(
            'inputCharset' => $inputCharset,
            'pageUrl' => $pageUrl,
            'bgUrl' => $bgUrl,
            'version' => $version,
            'language' => $language,
            'signType' => $signType,
            'merchantAcctId' => $merchantAcctId,
            'payerName' => $payerName,
            'payerContactType' => $payerContactType,
            'payerContact' => $payerContact,
            'orderId' => $orderId,
            'orderAmount' => $orderAmount,
            'orderTime' => $orderTime,
            'productName' => $productName,
            'productNum' => $productNum,
            'productId' => $productId,
            'productDesc' => $productDesc,
            'ext1' => $ext1,
            'ext2' => $ext2,
            'payType' => $payType,
            'bankId' => $bank_id,
            'redoFlag' => $redoFlag,
            'pid' => $pid,
            'key' => $key
        );

        /* 生成加密签名串 请务必按照如下顺序和规则组成加密串！ */
        $signmsgval = $this->signMsgval($param);
        $signmsg = strtoupper(md5($signmsgval));    //签名字符串 不可空
        unset($param['key']);
        $this->assign('data', $param);
        $this->assign('signmsg', $signmsg);
        //订单预处理有事务此处要提交
        //M()->commit();
        $this->display("Ucenter:Pay:KUAIQIAN");
        exit;
    }

    /**
     * 响应快钱通知
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-18
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $merchant_acctid = trim($this->config['kuaiqian_account']);             //人民币账号 不可空
        $key = trim($this->config['pay_safe_code']);
        $get_merchant_acctid = trim($data['merchantAcctId']);
        $pay_result = trim($data['payResult']);                                 //处理结果  10为支付成功
        $version = trim($data['version']);
        $language = trim($data['language']);
        $sign_type = trim($data['signType']);
        $pay_type = trim($data['payType']);
        $bank_id = trim($data['bankId']);
        $order_id = trim($data['orderId']);
        $order_time = trim($data['orderTime']);
        $order_amount = trim($data['orderAmount']);
        $deal_id = trim($data['dealId']);
        $bank_deal_id = trim($data['bankDealId']);
        $deal_time = trim($data['dealTime']);
        $pay_amount = trim($data['payAmount']);
        $fee = trim($data['fee']);
        $ext1 = trim($data['ext1']);
        $ext2 = trim($data['ext2']);
        $err_code = trim($data['errCode']);
        $sign_msg = trim($data['signMsg']);


        $v_oid = $order_id;
        //自定义的流水号 GY+ps_id
        $int_ps_id = ltrim($v_oid, 'GY');
        //外部网关流水号
        $gw_code = $deal_id;
        //返回金额以分为单位，转换成元
        $v_amount = sprintf('%.2f', $order_amount / 100);

        //待加密数组，顺序不能错
        $param = array(
            'merchantAcctId' => $get_merchant_acctid,
            'version' => $version,
            'language' => $language,
            'signType' => $sign_type,
            'payType' => $pay_type,
            'bankId' => $bank_id,
            'orderId' => $order_id,
            'orderTime' => $order_time,
            'orderAmount' => $order_amount,
            'dealId' => $deal_id,
            'bankDealId' => $bank_deal_id,
            'dealTime' => $deal_time,
            'payAmount' => $pay_amount,
            'fee' => $fee,
            'ext1' => $ext1,
            'ext2' => $ext2,
            'payResult' => $pay_result,
            'errCode' => $err_code,
            'key' => $key
        );

        /* 生成加密签名串 请务必按照如下顺序和规则组成加密串！ */
        $signmsgval = $this->signMsgval($param);
        $signmsg = strtoupper(md5($signmsgval));    //签名字符串 不可空
        //回调验证不通过
        if ($merchant_acctid != $get_merchant_acctid || $sign_msg != $signmsg) {
            return array('result' => false);
        }

        //支付状态
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
        if ($pay_result == 10) {
            $int_status = 1;
        } else {
            $int_status = 0;
            return array('result' => false);
        }
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id,'ps_status'=>1))->getField('ps_id');
				if(!empty($ary_paymentSerial)){
					//已经存在相同支付流水号的
					$o_id = $this->getOidByPsid($int_ps_id);
					return array('result' => true,'o_id'=>$o_id);
				}
			}
		}
        //更改第三方流水单状态
        //网银在线无第三方网关的流水号
        $this->updataPaymentSerial($int_ps_id, $int_status, $pay_result, $gw_code);

        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);

        return array(
            'result' => true,
            'o_id' => $o_id,
            'int_status' => $int_status,
            'total_fee' => $v_amount,
            'gw_code' => $gw_code,
            'm_id' => $m_id,
			'int_ps_id'=>$int_ps_id
        );
    }

    ##########################################################################
    /**
     * 将变量值不为空的参数组成字符串
     * @param   string   $strs  参数字符串
     * @param   string   $key   参数键名
     * @param   string   $val   参数键对应值
     */

    private function append_param($strs, $key, $val) {
        if ($strs != "") {
            if ($key != '' && $val != '') {
                $strs .= '&' . $key . '=' . $val;
            }
        } else {
            if ($val != '') {
                $strs = $key . '=' . $val;
            }
        }
        return $strs;
    }

    private function signMsgval($ary_param) {
        $ary_res = array('msgVal' => '');
        foreach ($ary_param as $key => $val) {
            if (!empty($ary_res['msgVal'])) {
                if (!empty($key) && $key == 'redoFlag') {
                    $ary_res['msgVal'] .= '&' . $key . '=' . $val;
                }
                if (!empty($key) && !empty($val)) {
                    $ary_res['msgVal'] .= '&' . $key . '=' . $val;
                }
            } else {
                if (!empty($val)) {
                    $ary_res['msgVal'] .= $key . '=' . $val;
                }
            }
        }
        return $ary_res['msgVal'];
    }

}