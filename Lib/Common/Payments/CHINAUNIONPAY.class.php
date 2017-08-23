<?php
/**
 * 中国银联在线支付
 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
 * @date 2014-4-12
*/
class CHINAUNIONPAY extends Payments implements IPayments {
	
	private $signature = '';
	private $signMethod = 'md5';
	
	/**
	 * 设置中国银联支付方式配置信息
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-12
	 * @param array $param 支付方式的配置数组
	*/
    public function setCfg($param = array()) {
        $config = array();
        $config['chinaunionpay_account'] = $param['chinaunionpay_account'];
        $config['pay_safe_code'] = $param['pay_safe_code'];
        $this->config = $config;
    }
	
	/**
	 * 向支付网关提交请求参数
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-13
	 * @param $o_id 订单号
	 * @param int $type 订单类型
	 * @param int $o_pay 订单实际支付金额
	 * @param $int $pay_type 支付类型 0全额支付  1 定金支付 2 尾款支付
	*/
	public function pay($o_id,$type=0,$o_pay=0.000,$pay_type=0) {
		$ary_order = parent::pay($o_id);
		if($type == '5' || $type == '8'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //生成支付序列号
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
		//生成待签名的参数
		$pay_params_check = array(
			//支付预定字段
			'version' => '1.0.0', 																				//版本号
			'charset' => 'UTF-8', 																				//UTF-8, GBK等
			'merId' => $this->config['chinaunionpay_account'], 													//商户id号
			'acqCode' => '',  																					//收单机构填写
			'merCode' => '',  																					//收单机构填写
			'merAbbr' => '配送公司',																		//商户名称
			
			//支付请求必填字段
			'frontEndUrl' => U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true),  	//前台回调URL
			'backEndUrl' => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true),				//后台回调URL
			'transType' => '01',																				//交易类型 '01'=>支付
			'orderNumber' => date('YmdHis') . strval(mt_rand(100, 999)) . 'Gy' . $int_ps_id, 					//商户订单号，必须唯一
			'orderAmount' => sprintf('%.2f', $ary_order['o_all_price']) * 100, 									//订单交易金额
			'orderCurrency' => '156',																			//交易币种，'156'=>人民币
			'orderTime' => date('YmdHis'),   																	//交易时间, YYYYmmhhddHHMMSS,
			'customerIp' => $_SERVER['REMOTE_ADDR'],															//用户IP
			
			//支付请求可为空字段（但必须填写）
			'origQid'           => '',																			//上一笔关联交易的交易流水号，以便于银联互联网系统可以准确定位原始交易
			'commodityUrl'      => '',
			'commodityName'     => '',
			'commodityUnitPrice'=> '',
			'commodityQuantity' => '',
			'commodityDiscount' => '',
			'transferFee'       => '',
			'customerName'      => '',
			'defaultPayType'    => '',
			'defaultBankNumber' => '',
			'transTimeout'      => '',
			'merReserved'       => ''											
		);
        //生成签名
		$sign_str = $this->sign($pay_params_check, $this->signMethod);
		$pay_params_check['signature'] = $sign_str;
		$pay_params_check['signMethod'] = $this->signMethod;
		$this->assign('data', $pay_params_check);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:CHINAUNIONPAY");
		die;
	}
	
	 /**
	 * 中国银联在线充值
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @param $flt_money 充值金额
	*/
    public function charge($flt_money) {
        //生成支付序列号
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
		//生成待签名的参数
		$pay_params_check = array(
			//支付预定字段
			'version' => '1.0.0', 																				//版本号
			'charset' => 'UTF-8', 																				//UTF-8, GBK等
			'merId' => $this->config['chinaunionpay_account'], 													//商户id号
			'acqCode' => '',  																					//收单机构填写
			'merCode' => '',  																					//收单机构填写
			'merAbbr' => '菜篮子配送公司',																		//商户名称
			
			//支付请求必填字段
			'frontEndUrl' => U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true),  	//前台回调URL
			'backEndUrl' => U('Home/User/synChargeNotify?code=' . $this->code, '', true, false, true),			//后台回调URL
			'transType' => '01',																				//交易类型 '01'=>支付
			'orderNumber' => date('YmdHis') . strval(mt_rand(100, 999)) . 'Gy' . $int_ps_id, 					//商户订单号，必须唯一
			'orderAmount' => sprintf('%.2f', $flt_money) * 100, 	 											//订单交易金额
			'orderCurrency' => '156',																			//交易币种，'156'=>人民币
			'orderTime' => date('YmdHis'),   																	//交易时间, YYYYmmhhddHHMMSS,
			'customerIp' => $_SERVER['REMOTE_ADDR'],															//用户IP
			
			//支付请求可为空字段（但必须填写）
			'origQid'           => '',																			//上一笔关联交易的交易流水号，以便于银联互联网系统可以准确定位原始交易
			'commodityUrl'      => '',
			'commodityName'     => '',
			'commodityUnitPrice'=> '',
			'commodityQuantity' => '',
			'commodityDiscount' => '',
			'transferFee'       => '',
			'customerName'      => '',
			'defaultPayType'    => '',
			'defaultBankNumber' => '',
			'transTimeout'      => '',
			'merReserved'       => ''											
		);
        //生成签名
		$sign_str = $this->sign($pay_params_check, $this->signMethod);
		$pay_params_check['signature'] = $sign_str;
		$pay_params_check['signMethod'] = $this->signMethod;
		$this->assign('data', $pay_params_check);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:CHINAUNIONPAY");
		die;
    }
	
	/**
	 * 响应
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-12
	 * @param array $data 服务器端返回的数据
	*/
	public function respond($data) {
		$int_ps_id = ltrim(strstr($data['orderNumber'], 'G'), 'Gy');
		$notify_params_check = array(
			'version' => $data['version'],										//版本号
			'charset' => $data['charset'],										//字符集
			'transType' => $data['transType'],									//消费类型 01为支付
			'respCode' => $data['respCode'],									//应答码
			'respMsg' => $data['respMsg'],										//应答消息
			'respTime' => $data['respTime'],									//应答时间
			'merId' => $data['merId'],											//商户id号
			'merAbbr' => $data['merAbbr'],										//商户名
			'orderNumber' => $data['orderNumber'],								//商户订单号
			'traceNumber' => $data['traceNumber'],								//跟踪号
			'traceTime' => $data['traceTime'],									//跟踪时间
			'qid' => $data['qid'],												//交易流水号
			'orderAmount' => $data['orderAmount'],								//订单金额
			'orderCurrency' => $data['orderCurrency'],							//币种类型 '156' 人民币
			'settleAmount' => $data['settleAmount'],							//清算金额
			'settleCurrency' => $data['settleCurrency'],						//清算币种
			'settleDate' => $data['settleDate'],								//清算日期
			'exchangeRate' => $data['exchangeRate'],							//清算汇率
			'exchangeDate' => $data['exchangeDate'],							//兑换日期
			'cupReserved' => $data['cupReserved'],								//系统保留域
			'signMethod' => $data['signMethod'],								//签名方法
			'signature' => $data['signature'],									//签名验证
		);
		$this->signature = $notify_params_check['signature'];
		$this->signMethod= $notify_params_check['signMethod'];
		unset($notify_params_check['signature']);
		unset($notify_params_check['signMethod']);
		//验证签名
		$signature = $this->sign($notify_params_check, $this->signMethod);
		if ($signature != $this->signature) {
			return array('result' => false);
		}
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
		//支付状态
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
        if (00 == $data['respCode']) {
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
					return array('result' => true,'o_id'=>$o_id);
				}
			}
		}
        $this->updataPaymentSerial($int_ps_id, $int_status, $data['respCode'], $data['qid']);
        $m_id = $this->getMemberIdByPsid($int_ps_id);
		$qid_len = strlen($data['qid']);
        return array(
            'result' => true,
            'o_id' => $o_id,
            'int_status' => $int_status,
            'total_fee' => sprintf('%.2f', $data['orderAmount'] / 100),  //返回数据以分为单位  ，转换成 以元为单位
            'gw_code' => substr($data['qid'],0,$qid_len-2),
            'm_id' => $m_id,
            'merid'=>$data['merid'],
			'int_ps_id'=>$int_ps_id
        );
	}
	
	/**
	 * 签名方法
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-12
	 * 
	*/
	public function sign($params, $signMethod) {
		if (strtolower($signMethod) == "md5") {
            ksort($params);
            $sign_str = "";
            foreach ($params as $key=>$val) {
                $sign_str .= sprintf("%s=%s&", $key, $val);
            }
            return md5($sign_str . md5($this->config['pay_safe_code']));
        }
	}
}