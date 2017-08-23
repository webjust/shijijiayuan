<?php

/**
 * 支付宝支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ALIPAY extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['alipay_account'] = $param['alipay_account'];
        $config['pay_safe_code'] = $param['pay_safe_code'];
        $config['identity_id'] = $param['identity_id'];
        $config['interface_type'] = $param['interface_type'];
        $this->config = $config;
    }

    /**
     * 支付订单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = parent::pay($str_oid);
        switch ($this->config['interface_type']) {
            case '1':
                //支付宝即时到账接口
                $str_service = 'create_direct_pay_by_user';
                break;
            case '2':
                //支付宝担保交易接口
                $str_service = 'create_partner_trade_by_buyer';
                break;
            case '3':
                //支付宝双接口
                $str_service = 'trade_create_by_buyer';
                break;
        }
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }

        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        
        //请求参数
        $parame = array(
            /* 基本参数 */
            'service' => $str_service,
            'partner' => $this->config['identity_id'],
            '_input_charset' => 'utf-8',
            'notify_url' => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true), //异步通知地址
            'return_url' => U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true), //直接回跳地址
            /* 业务参数 */
            'subject' => "订单编号:".$ary_order['o_id'],
            'out_trade_no' => 'gysoft'.CI_SN.'-' . $int_ps_id,
            'price' => sprintf("%.2f",$ary_order['o_all_price']),
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* 买卖双方信息 */
            'seller_email' => $this->config['alipay_account']
        );
        if($parame['service'] == 'trade_create_by_buyer'){
		$receive_arr = D('Orders')->where(array('o_id'=>$ary_order['o_id']))->field('o_receiver_name,o_receiver_state,o_receiver_city,o_receiver_county,o_receiver_address,o_receiver_zipcode,o_receiver_telphone,o_receiver_mobile')->find(); 
            /* 收货人信息 */
            $array_receive['receive_name'] = $receive_arr['o_receiver_name'];
            $array_receive['receive_address'] = $receive_arr['o_receiver_state'].$receive_arr['o_receiver_city'].$receive_arr['o_receiver_county'].$receive_arr['o_receiver_address'];
            $array_receive['receive_zip'] = $receive_arr['o_receiver_zipcode'];
            $array_receive['receive_phone'] = $receive_arr['o_receiver_telphone'] == '--' ?'':$receive_arr['o_receiver_telphone'];
            $array_receive['receive_mobile'] = strpos($receive_arr['receive_mobile'],':') ? decrypt($receive_arr['receive_mobile']) : $receive_arr['receive_mobile'];
            $array_receive['receive_phone'] = strpos($array_receive['receive_phone'],':') ? decrypt($array_receive['receive_phone']) : $array_receive['receive_phone'];
	    foreach($array_receive as $k => $rec){
	    	if(!empty($rec) ){
			$parame[$k] = $rec;
		}
	    }
        } 

        $parame = $this->argSort($parame);
        
        $str_param = $this->createLinkstringUrlencode($parame);
        $str_sign = $this->buildMysign($parame, $this->config['pay_safe_code'], 'MD5');
        $url = 'https://mapi.alipay.com/gateway.do?' . $str_param . '&sign=' . $str_sign . '&sign_type=MD5';
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        redirect($url);
    }

    /**
     * 预存款充值
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-25
     * @param float $flt_money 要充值的金额
     */
    public function charge($flt_money) {
        switch ($this->config['interface_type']) {
            case '1':
                //支付宝即时到账接口
                $str_service = 'create_direct_pay_by_user';
                break;
            case '2':
                //支付宝担保交易接口
                $str_service = 'create_partner_trade_by_buyer';
                break;
            case '3':
                //支付宝双接口
                $str_service = 'trade_create_by_buyer';
                break;
        }
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
        $shop_title = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_TITLE'))->getField('sc_value');
        //请求参数
        $parame = array(
            /* 基本参数 */
            'service' => $str_service,
            'partner' => $this->config['identity_id'],
            '_input_charset' => 'utf-8',
            'notify_url' => U('Home/User/synChargeNotify?code=' . $this->code, '', true, false, true), //充值异步通知地址
            'return_url' => U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true), //充值直接回跳地址
            /* 业务参数 */
            'subject' => $shop_title.'在线充值',
            'out_trade_no' => 'gysoft'.CI_SN.'-' . $int_ps_id,
            'price' => sprintf('%.2f', $flt_money),
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* 买卖双方信息 */
            'seller_email' => $this->config['alipay_account']
        );

        $parame = $this->argSort($parame);

        $str_param = $this->createLinkstringUrlencode($parame);
        $str_sign = $this->buildMysign($parame, $this->config['pay_safe_code'], 'MD5');
        $url = 'https://mapi.alipay.com/gateway.do?' . $str_param . '&sign=' . $str_sign . '&sign_type=MD5';
        //M()->commit();
		M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        redirect($url);
    }

    /**
     * 响应支付宝通知
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-24
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $seller_email = rawurldecode($data['seller_email']);
        //自定义的流水号 GY+ps_id
       // $int_ps_id = ltrim($data['out_trade_no'], 'gysoft'.CI_SN.'-');
		$int_ps_id = str_replace('gysoft'.CI_SN.'-','',$data['out_trade_no']);
        //外部网关流水号
        $str_trade_no = $data['trade_no'];

        /* 检查数字签名是否正确 */
        $parame = $this->paraFilter($data);
        $parame = $this->argSort($parame);

        $str_sign = $this->buildMysign($parame, $this->config['pay_safe_code'], 'MD5');

        if ($str_sign != $data['sign']) {
            return array('result' => false);
        }

        //支付状态
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
        if ($data['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
            $int_status = 2;
        } elseif ($data['trade_status'] == 'TRADE_FINISHED') {
            $int_status = 3;
        } elseif ($data['trade_status'] == 'TRADE_SUCCESS') {
            $int_status = 1;
        }else if ($data['trade_status'] == 'WAIT_BUYER_PAY'){
            $int_status = 5;
        } else {
            $int_status = 4;
        }
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
				if(!empty($ary_paymentSerial)){
					if($ary_paymentSerial['ps_status'] == 1){
						//已经存在相同支付流水号的
						return array('result' => true,'o_id'=>$data['subject']);
					}
					$ps_update_timestamp = strtotime($ary_paymentSerial['ps_update_time']);
					$now_timestamp = time();
					
					$time_difference = $now_timestamp - $ps_update_timestamp;
					//流水号已经超过15天没有更新过，不再接受异步通知消息
					if($time_difference > 60*60*24*15) {
						return array('result' => false);
					}
				}else{
					//流水号不存在
					return array('result' => false);
				}
				
			}else{				
				return array('result' => false);
			}
		}
        //更改第三方流水单状态
        $result = $this->updataPaymentSerial($int_ps_id, $int_status, $data['trade_status'], $str_trade_no);
		//根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
//        $ary_result = array(
//            'result' => true,
//            'o_id' => $data['subject'],
//            'int_status' => $int_status,
//            "total_fee" => $data['total_fee'],
//            "gw_code" => $str_trade_no,
//            'm_id' => $m_id
//        );
//        $ary_get = $this->_get();
//        echo "<pre>";print_r($ary_result);exit;
        return array(
            'result' => $result,
            'o_id' => $data['subject'],
            'int_status' => $int_status,
            "total_fee" => $data['total_fee'],
            "gw_code" => $str_trade_no,
            'm_id' => $m_id,
			'int_ps_id'=>$int_ps_id
        );
    }

    ##########################################################################

    /**
     * 生成签名结果
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param array $sort_para 要签名的数组
     * @param string $key 支付宝交易安全校验码
     * @param string $sign_type 签名类型 默认值：MD5
     * @return string 签名结果字符串
     */
    protected function buildMysign($sort_para, $key, $sign_type = "MD5") {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($sort_para);
        //把拼接后的字符串再与安全校验码直接连接起来
        $prestr = $prestr . $key;
        //把最终的字符串签名，获得签名结果
        $mysgin = $this->sign($prestr, $sign_type);
        return $mysgin;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param array $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    protected function createLinkstring($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
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

    /**
     * 除去数组中的空值和签名参数
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param array $para 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    protected function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else
                $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param array $para 排序前的数组
     * @return array 排序后的数组
     */
    protected function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 签名字符串
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param string $prestr 需要签名的字符串
     * @param string $sign_type 签名类型 默认值：MD5
     * @return string 签名结果
     */
    protected function sign($prestr, $sign_type = 'MD5') {
        $sign = '';
        if ($sign_type == 'MD5') {
            $sign = md5($prestr);
        } elseif ($sign_type == 'DSA') {
            //DSA 签名方法待后续开发
            die("DSA 签名方法待后续开发，请先使用MD5签名方式");
        } else {
            die("支付宝暂不支持" . $sign_type . "类型的签名方式");
        }
        return $sign;
    }

    /**
     * 支付订单
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-07-31
     * @param array $param 发货信息
     */
    public function ship($param){
        $parameter = array(
            "service" => "send_goods_confirm_by_platform",
            "partner" => trim($this->config['identity_id']),
            "trade_no"	=> $param['WIDtrade_no'],
            "logistics_name"	=> $param['WIDlogistics_name'],
            "invoice_no"	=> $param['WIDinvoice_no'],
            "transport_type"	=> $param['WIDtransport_type'],
            "_input_charset"	=> trim(strtolower('utf-8'))
        );
        $parame = $this->argSort($parameter);
//        echo "<pre>";print_r($param);exit;
        $str_param = $this->createLinkstringUrlencode($parame);
        $str_sign = $this->buildMysign($parame, $this->config['pay_safe_code'], 'MD5');
//        $url = 'https://mapi.alipay.com/gateway.do?' . $str_param . '&sign=' . $str_sign . '&sign_type=MD5';
//        redirect($url);
        $parame['sign'] = $str_sign;
        $parame['sign_type'] = "MD5";
//        $ary_data = $str_param . '&sign=' . $str_sign . '&sign_type=MD5';
        return makeRequest("https://mapi.alipay.com/gateway.do", $parame,"GET");
    }
}
