<?php
require_once('ChinaPayV5/SDKConfig.php');

class CHINAPAYV5 extends Payments implements IPayments {
    
    public function setCfg($params) {
        //初始化配置
        $this->config = $params;
        
        if(!defined('SDK_SIGN_CERT_PATH')){
            // 签名证书路径 （联系运营获取两码，在CFCA网站下载后配置，自行设置证书密码并配置）
            define('SDK_SIGN_CERT_PATH' , $params['SIGN_CERT_PATH']['upload_path']);
            
            // 签名证书密码
            define('SDK_SIGN_CERT_PWD', $params['SIGN_CERT_PWD']);
            
			##签名证书类型
			//acpsdk.signCert.type=PKCS12;

            // 验签证书路径 
            define('SDK_VERIFY_CERT_DIR', dirname($params['VERIFY_CERT_PATH']['upload_path']));
            
            // 验签证书
            define('SDK_VERIFY_CERT_PATH', $params['VERIFY_CERT_PATH']['upload_path']);
            
            // 密码加密证书
            define('SDK_ENCRYPT_CERT_PATH', isset($params['ENCRYPT_CERT_PATH']) 
                                                ? $params['ENCRYPT_CERT_PATH']['upload_path'] 
                                                : '');
            // 前台通知地址 (商户自行配置通知地址)
            define('SDK_FRONT_NOTIFY_URL', U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true));

            // 后台通知地址 (商户自行配置通知地址)
            define('SDK_BACK_NOTIFY_URL', U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true));
            
        }
        //dump(SDK_FRONT_NOTIFY_URL);die;
        
    }
    
    public function pay($o_id, $type=0, $o_pay=0, $pay_type=0) {
        $ary_order = parent::pay($o_id);
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        $params = array(
            'orderId'   => $o_id,
            'txnAmt'    => sprintf("%s", $o_pay*100),
            'reqReserved' => $int_ps_id     //请求方保留域--O
        );
        $params_default = $this->_frontInit();
        $params = array_merge($params_default, $params);
        signv5 ( $params );
        // 初始化日志
        $log = new PhpLog(SDK_LOG_FILE_PATH, "PRC", SDK_LOG_LEVEL);
        // 前台请求地址
        $front_uri = SDK_FRONT_TRANS_URL;
        $log->LogInfo ( "前台请求地址为>" . $front_uri );
        // 构造 自动提交的表单
        $html_form = create_html ( $params, $front_uri );
        
        $log->LogInfo ( "-------前台交易自动提交表单>--begin----" );
        $log->LogInfo ( $html_form );
        $log->LogInfo ( "-------前台交易自动提交表单>--end-------" );
        $log->LogInfo ( "============处理前台请求 结束===========" );
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        echo $html_form;
        die();
    }  

    /**
     * 交易应答码：https://open.unionpay.com/ajweb/help?id=262&level=1&from=0
     * 00 * 成功
     * 01 * 交易失败。详情请咨询95516
     * 02 * 系统未开放或暂时关闭，请稍后再试
     * 03 * 交易通讯超时，请发起查询交易
     * 04 * 交易状态未明，请查询对账结果
     * 05 * 交易已受理，请稍后查询交易结果
     * 10 * 报文格式错误
     * 11 * 验证签名失败
     */
    public function respond($data) {
		$valid = verify($data);
		$int_ps_id = $data['reqReserved'];
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
        if ('00' == $data['respCode'] && $data['orderId'] == $o_id && isset($valid)) {
            $int_status = 1;
        }elseif ('05' == $data['respCode'] || '03' == $data['respCode']) {
            //交易状态 若收到respCode为“05”的应答时，则间隔（5分、10分、30分、60分、120分）发起交易查询。
            $ary_payment = array(
                'o_id'=>$o_id,
                'ps_gateway_sn'=>$data['queryId'],
                'ps_id'=>$int_ps_id,
            );
            $int_status = $this->singleQuery($ary_payment);
        }else{
            $int_status = 0;
        }
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')
				->where(array('ps_id'=>$int_ps_id,'ps_status'=>1))
				->getField('ps_id');
				if(!empty($ary_paymentSerial)){
					//已经存在相同支付流水号的
					return array('result' => true,'o_id'=>$o_id);
				}
			}
        }else{
			return array('result' => false,'msg' => $data['respMsg']);
        }
        $this->updataPaymentSerial($int_ps_id,$int_status,$data['respCode'],$data['queryId']);
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        return array(
            'result' => true,
            'o_id' => $o_id,
            'int_status' => $int_status,
            'total_fee' => sprintf('%.2f', $data['txnAmt'] / 100),  //返回数据以分为单位，转换成 以元为单位
            'gw_code' => $data['queryId'],
            'm_id' => $m_id,
            'int_ps_id'=>$int_ps_id
        );
    }

    /**
     * 7.4 查询交易
     * 1. 如果已接收到明确的交易结果应答，则不需要发起查询。
     * 2. 前台交易，若未接收到全渠道系统的明确交易应答时需发起单笔查询交易，查询明确的交易结果。
     * 3. 后台交易，若出现交易超时的情况则需要发起单笔查询交易，查询明确的交易结果。
     * @param array $ary_payment
     * @return int 1 或 0
     */
    public function singleQuery($ary_payment) {
        $params['orderId'] = $ary_payment['o_id'];
        if($ary_payment['ps_gateway_sn']){
            $params['queryId'] = $ary_payment['ps_gateway_sn'];    //原消费的queryId，可以从查询接口或者通知接口中获取
        }
        if($ary_payment['ps_gateway_sn']){
            $params['reserved'] = $ary_payment['ps_id']; //保留域
        }
        $params_default = $this->_queryInit();
        $params = array_merge($params_default, $params);
        signv5 ( $params );
        // 初始化日志
        $log = new PhpLog(SDK_LOG_FILE_PATH, "PRC", SDK_LOG_LEVEL);
        
        $log->LogInfo ( "后台请求地址为>" . SDK_SINGLE_QUERY_URL );
        // 发送信息到后台
        $result = sendHttpRequest ( $params, SDK_SINGLE_QUERY_URL );
        $log->LogInfo ( "后台返回结果为>" . $result );

        //返回结果展示
        $result_arr = coverStringToArray ( $result );

        $return = verify ( $result_arr );
        return isset($return) ? 1 : 0;
    }  

    /**
     * 前台支付初始化
     * @return array
     */
    private function _frontInit() {
        
        $params = array(
                'version' => '5.0.0',						//版本号
                'encoding' => 'UTF-8',						//编码方式
                'certId' => getSignCertId (),				//证书ID
                'txnType' => '01',								//交易类型
                'txnSubType' => '01',							//交易子类
                //'bizType' => '000000',							//业务类型
				'bizType' => '000201',
                'frontUrl' =>  SDK_FRONT_NOTIFY_URL,  				//前台通知地址
                'backUrl' => SDK_BACK_NOTIFY_URL,				//后台通知地址
                'signMethod' => '01',		                     //签名方法
                'channelType' => '07',					        //渠道类型，07-PC，08-手机
                'accessType' => '0',							//接入类型
                'merId' =>  $this->config['MERCHANT_ID'],		//商户代码
                //'orderId' => '',					//商户订单号
                'txnTime' => date('YmdHis'),				//订单发送时间
                //'txnAmt' => '0',								//交易金额
                'currencyCode' => '156',						//交易币种
                'defaultPayType' => '0001',						//默认支付方式
				//后台类交易且卡号上送；跨行收单且收单机构收集银行卡信息时上送01：银行卡02：存折03：C卡默认取值：01取值“03”表示以IC终端发起的IC卡交易，IC作为普通银行卡进行支付时，此域填写为“01”
				//'accType'=> 'accType',//帐号类型--C
				//1、  后台类消费交易时上送全卡号或卡号后4位 2、  跨行收单且收单机构收集银行卡信息时上送、  3、前台类交易可通过配置后返回，卡号可选上送
				//'accNo'=> '',//帐号--C
        );
        return $params;
    }
    
    /**
     * 退款操作初始化
     */
    private function _refundInit() {
        
        $params = array(
                'version' => '5.0.0',			//版本号
                'encoding' => 'GBK',				//编码方式
                'certId' => getSignCertId (),	//证书ID
                'signMethod' => '01',		//签名方法
                'txnType' => '04',					//交易类型
                'txnSubType' => '00',				//交易子类
                'bizType' => '000201',			//业务类型
                'accessType' => '0',				//接入类型
                'channelType' => '07',					//渠道类型
                //'orderId' => date('YmdHis'),			//商户订单号，重新产生，不同于原消费
                'merId' =>  $this->config['MERCHANT_ID'],		//商户代码
                //'origQryId' => '201501062125593073808',    //原消费的queryId，可以从查询接口或者通知接口中获取
                'txnTime' => date('YmdHis'),				//订单发送时间，重新产生，不同于原消费
                //'txnAmt' => '100',               //交易金额，退货总金额需要小于等于原消费
                'backUrl' => SDK_BACK_NOTIFY_URL,				//后台通知地址
                'reqReserved' =>' 透传信息', //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现 
        );
        
        return $params;
    }
    
    /**
     * 查询接口初始化
     */
    private function _queryInit() {
        $params = array(
                'version' => '5.0.0',			//版本号
                'encoding' => 'GBK',				//编码方式
                'certId' => getSignCertId (),	//证书ID
                'signMethod' => '01',		//签名方法
                'txnType' => '00',					//交易类型
                'txnSubType' => '00',				//交易子类
                'bizType' => '000000',			//业务类型
                'accessType' => '0',				//接入类型
                'channelType' => '07',					//渠道类型
                //'orderId' => date('YmdHis'),			//商户订单号，重新产生，不同于原消费
                'merId' =>  $this->config['MERCHANT_ID'],		//商户代码
                'txnTime' => date('YmdHis'),				//订单发送时间，重新产生，不同于原消费
                //'queryId' => '201501062125593073808',    //原消费的queryId，可以从查询接口或者通知接口中获取
                //'reserved' => '', //保留域
        );
        
        return $params;
    }
    
    /**
     * 前台交易初始化（弃用）
     * @return multitype:string unknown NULL
     */
    private function _initPayment() {
        $params = array(
                //固定填写
                'version'=> '5.0.0',//版本号--M
                //默认取值：UTF-8
                'encoding'=> 'UTF-8',//编码方式--M
                //通过MPI插件获取
                'certId'=> getCertIdv5 (SDK_SIGN_CERT_PATH),//证书ID--M
                //01 RSA     02 MD5 (暂不支持)
                'signMethod'=> '01',//签名方法--M
                //取值：01
                'txnType'=> '01',//交易类型--M
                //01：自助消费，通过地址的方式区分前台消费和后台消费（含无跳转支付）03：分期付款
                'txnSubType'=> '00',//交易子类--M
                'bizType'=> '000000',//产品类型--M
                'channelType'=> '07',//渠道类型--M
                //前台返回商户结果时使用，前台类交易需上送
                'frontUrl'=> SDK_FRONT_NOTIFY_URL,//前台通知地址--C
                //后台返回商户结果时使用，如上送，则发送商户后台交易结果通知
                'backUrl'=> SDK_BACK_NOTIFY_URL,//后台通知地址--M
                //0：普通商户直连接入2：平台类商户接入
                'accessType'=> '0',//接入类型--M
                //　
                'merId'=> $this->config['MERCHANT_ID'],//商户代码--M
                //商户类型为平台类商户接入时必须上送
                'subMerId'=> '',//二级商户代码--C
                //商户类型为平台类商户接入时必须上送
                'subMerName'=> '',//二级商户全称--C
                //商户类型为平台类商户接入时必须上送
                'subMerAbbr'=> '',//二级商户简称--C
                //商户端生成
                'orderId'=> date('YmdHis'),//商户订单号--M
                //商户发送交易时间
                'txnTime'=> date('YmdHis'),//订单发送时间--M
                /**
                 * 后台类交易且卡号上送；跨行收单且收单机构收集银行卡信息时上送
        * 01：银行卡，02：存折，03：C卡；默认取值：01
        * 取值“03”表示以IC终端发起的IC卡交易，IC作为普通银行卡进行支付时，此域填写为“01”
        */
                'accType'=> 'accType',//帐号类型--C
                /**
                 * 1、  后台类消费交易时上送全卡号或卡号后4位
        * 2、  跨行收单且收单机构收集银行卡信息时上送、
        * 3、  前台类交易可通过配置后返回，卡号可选上送
        */
                'accNo'=> '',//帐号--C
                //交易单位为分
                'txnAmt'=> '1',//交易金额--M
                //默认为156交易 参考公参
                'currencyCode'=> '156',//交易币种--M
                /**
                 * 1、后台类消费交易时上送
        * 2、跨行收单且收单机构收集银行卡信息时上送
        * 3、认证支付2.0，后台交易时可选Key=value格式（具体填写参考数据字典）
        */
                'customerInfo'=> customerInfo(),//银行卡验证信息及身份信息--C
                /**
                 * PC
        * 1、前台类消费交易时上送
        * 2、认证支付2.0，后台交易时可选
        */
                'orderTimeout'=> '',//订单接收超时时间（防钓鱼使用）--O
                //PC超过此时间用户支付成功的交易，不通知商户，系统自动退款，大约5个工作日金额返还到用户账户
                'payTimeout'=> '',//订单支付超时时间--O
                'termId'=> '',//终端号--O
                //商户自定义保留域，交易应答时会原样返回
                'reqReserved'=> '',//请求方保留域--O
                //子域名： 活动号 marketId  移动支付订单推送时，特定商户可以通过该域上送该订单支付参加的活动号
                'reserved'=> '',//保留域--O
                //格式如下：{子域名1=值&子域名2=值&子域名3=值}
                'riskRateInfo'=> '',//风险信息域--O
                //当使用银联公钥加密密码等信息时，需上送加密证书的CertID；说明一下？目前商户、机构、页面统一套
                'encryptCertId'=> '',//加密证书ID--C
                //前台消费交易若商户上送此字段，则在支付失败时，页面跳转至商户该URL（不带交易信息，仅跳转）
                'frontFailUrl'=> $this->config['SDK_FRONT_FAIL_URL'],//失败交易前台跳转地址--O
                //分期付款交易，商户端选择分期信息时，需上送 组合域，填法见数据元说明
                'instalTransInfo'=> '',//分期付款信息域--C
                //C  取值参考数据字典
                'defaultPayType'=> '',//默认支付方式--O
                //C当帐号类型为02-存折时需填写在前台类交易时填写默认银行代码，支持直接跳转到网银商户发卡银行控制系统应答返回
                'issInsCode'=> '',//发卡机构代码--O
                //仅仅pc使用，使用哪种支付方式 由收单机构填写，取值为以下内容的一种或多种，通过逗号（，）分割。取值参考数据字典
                'supPayType'=> '',//支持支付方式--O
                //移动支付业务需要上送
                'userMac'=> '',//终端信息域--O
                //前台交易，有IP防钓鱼要求的商户上送
                'customerIp'=> '',//持卡人IP--C
                //有卡交易必填有卡交易信息域
                'cardTransData'=> '',//有卡交易信息域--C
                //渠道类型为语音支付时使用
                'vpcTransData'=> '',//VPC交易信息域--C
                //移动支付上送
                'orderDesc'=> '',//订单描述--C				//默认支付方式
        );
    
        return $params;
    }
}
