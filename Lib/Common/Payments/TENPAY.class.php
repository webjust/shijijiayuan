<?php

/**
 * 财付通支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TENPAY extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['tenpay_account'] = $param['tenpay_account'];
        $config['pay_safe_code'] = $param['pay_safe_code'];
        $this->config = $config;
    }
    
    /**
     * 支付订单
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-22
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = parent::pay($str_oid);
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //生成支付序列号
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        //请求基本参数
        $gate_url = 'https://gw.tenpay.com/gateway/pay.htm';
        $params = array(
        	'partner' => $this->config['tenpay_account'],
        	'out_trade_no' => 'GY' . $int_ps_id,
        	'total_fee' => intval($ary_order['o_all_price']*100),//总金额
            'notify_url' => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true), //异步通知地址
            'return_url' => U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true), //直接回跳地址
        	'body' => $ary_order['o_id'],
        	'bank_type' => 'DEFAULT',//银行类型，默认为财付通
        	'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
        	'fee_type' => '1',//币种
        	'subject' => $ary_order['o_id'],//商品名称
        	'sign_type' => 'MD5',//签名方式，默认为MD5，可选RSA
        	'service_version' => '1.0',//接口版本号
        	'input_charset' => 'UTF-8',//字符集
        	'sign_key_index' => '1',//密钥序号
        	'product_fee' => intval($ary_order['o_all_price']*100),//商品费用
        	'time_start' => date('YmdHis',strtotime($ary_order['o_create_time'])),//订单生成时间
	        'trade_mode' => '1',//交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
        	'trans_type' => '1'//交易类型
        );
        //创建md5摘要
		$sign_parameter= "";
		ksort($params);
        $sign = $this->buildSign($params, $this->config['pay_safe_code']);
		$params["sign"] = $sign;
		
		ksort($params);
        $request_parameter = $this->createLinkstringUrlencode($params);
		$requestURL = $gate_url . "?" . $request_parameter;
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        redirect($requestURL);
    }
	
	/**
     * 财付通在线充值
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-11-26
     * @param float $flt_money 充值金额
     */
    public function charge($flt_money) {
        //生成支付序列号
		$int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
		$shop_title = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_TITLE'))->getField('sc_value');
        //请求基本参数
        $gate_url = 'https://gw.tenpay.com/gateway/pay.htm';
        $params = array(
        	'partner' => $this->config['tenpay_account'],
        	'out_trade_no' => 'GY' . $int_ps_id,
        	'total_fee' => intval($flt_money*100),//总金额
            'notify_url' => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true), //异步通知地址
            'return_url' => U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true), //直接回跳地址
        	'body' => $shop_title.'在线充值',
        	'bank_type' => 'DEFAULT',//银行类型，默认为财付通
        	'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
        	'fee_type' => '1',//币种
        	'subject' => $shop_title.'在线充值',
        	'sign_type' => 'MD5',//签名方式，默认为MD5，可选RSA
        	'service_version' => '1.0',//接口版本号
        	'input_charset' => 'UTF-8',//字符集
        	'sign_key_index' => '1',//密钥序号
        	'product_fee' => intval($flt_money*100),//商品费用
        	'time_start' => date('YmdHis',strtotime($ary_order['o_create_time'])),//订单生成时间
	        'trade_mode' => '1',//交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
        	'trans_type' => '1'//交易类型
        );
        //创建md5摘要
		$sign_parameter= "";
		ksort($params);
        $sign = $this->buildSign($params, $this->config['pay_safe_code']);
		$params["sign"] = $sign;
		
		ksort($params);
        $request_parameter = $this->createLinkstringUrlencode($params);
		$requestURL = $gate_url . "?" . $request_parameter;
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        redirect($requestURL);
    }
    
    /**
     * 响应财付通通知
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-25
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        //自定义的流水号 GY+ps_id
        $int_ps_id = ltrim($data['out_trade_no'], 'GY');
        $str_trade_no = $data["transaction_id"];//财付通订单号
        $trade_state = $data["trade_state"];//支付结果
        $trade_mode = $data["trade_mode"];  //交易模式
        
        $signPars = "";
        ksort($data);
        $sign = $this->buildSign($data, $this->config['pay_safe_code']);
        
        $tenpaySign = strtolower($data["sign"]);
        if ($sign != $tenpaySign) {
            return array('result' => false);
        }
        if($trade_mode==1 && $trade_state==0){
            //交易模式为即时到帐时，支付结果：0 交易成功
            $int_status = 1;
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
        $this->updataPaymentSerial($int_ps_id, $int_status, $data['trade_status'], $str_trade_no);
        
        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
        return array(
            'result' => true,
            'o_id' => $o_id,
            'int_status' => $int_status,
            "total_fee" => $data['total_fee'],
            "gw_code" => $str_trade_no,
            'm_id' => $m_id,
			'int_ps_id'=>$int_ps_id
        );
    }
    
    /**
     * 生成签名结果
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-25
     * @param array $sort_para 要签名的数组
     * @param string $safe_code 支付宝交易安全校验码
     * @return string 签名结果字符串
     */
    protected function buildSign($params, $safe_code) {
        $request_parameter= "";
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        foreach($params as $key => $value) {
            if("sign" != $key && "code" != $key && "uri" != $key && "" != $value) {
                $sign_parameter .= $key . "=" . $value . "&";
            }
		}
        //把拼接后的字符串再与安全校验码直接连接起来
        $sign_parameter .= "key=" . $safe_code;
        //把最终的字符串签名，获得签名结果
        $sign = strtolower(md5($sign_parameter));
        return $sign;
    }
    
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-25
     * @param array $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    protected function createLinkstringUrlencode($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}