<?php

/**
 * 手机WAP支付宝支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.8.1
 * @author Tom <helong@guanyisoft.com>
 * @date 2015-03-25
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
require_once('alipay/alipay_submit.class.php');
require_once("alipay/alipay_notify.class.php");
class WAPALIPAY extends Payments implements IPayments {

    private $_input_charset = 'utf-8';  // 编码方式
    private $result         = array();  // 返回结果
    private $op_source      = 'wap';    // 操作来源  (wap/pc/app)
    private $version        = '2.0';
    private $format         = 'xml';
    private $transport      = 'http';
    private $cacert         = '';

    /**
     * 切换操作来源
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-01-23
     */
    public function setSource($op_source){
        $this->op_source = $op_source;
    }

    /**
     * 设置支付方式的配置信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-01-23
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['alipay_account']        = $param['alipay_account'];        // 支付宝收款账户
        $config['pay_safe_code']         = $param['pay_safe_code'];         // 安全校验码
        $config['identity_id']           = $param['identity_id'];           // 合作者身份ID
        $config['interface_type']        = $param['interface_type'];        // 接口类型
        $config['pay_encryp']            = $param['pay_encryp'];            // 加密方式
        $config['wap_alipay_public_key'] = $param['wap_alipay_public_key']; // 支付宝公钥
        $config['wap_shop_public_key']   = $param['wap_shop_public_key'];   // 商户公钥
        $config['wap_shop_private_key']  = $param['wap_shop_private_key'];  // 商户私钥
        $this->config = $config;
        // chdir(dirname(realpath(__FILE__)));
        // $this->cacert = getcwd() . DIRECTORY_SEPARATOR . 'WAPALIPAY' . DIRECTORY_SEPARATOR . 'cacert.pem';
        $this->cacert = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'WAPALIPAY' . DIRECTORY_SEPARATOR . 'cacert.pem';
    }

    /**
     * 设置接口类型
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    public function setInterfaceType($type){
        $this->config['interface_type'] = $type;
    }

    /**
     * 支付订单
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = D('Orders')->where(array('o_id' => $str_oid))->find();
        if (false == $ary_order) {
            $str_error = '订单信息不存在';
            goto fail;
        }

        $str_service = $this->getService($this->config['interface_type']);
        
        if(empty($str_service)){
            $str_error = '不存在该接口';
            goto fail;
        }

        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }

        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type,$ary_order['m_id']);

        $data = array(
            'notify_url'          => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true),            // 服务器异步通知页面路径
            'call_back_url'       => U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true),      // 页面跳转同步通知页面路径
            'merchant_url'        => U('Wap/Orders/OrderSuccess?oid=' . $ary_order['o_id'], '', true, false, true), // 操作中断返回地址
            'seller_account_name' => $this->config['alipay_account'],
            'out_trade_no'        => 'gysoft'.CI_SN.'-' . $int_ps_id,
            'subject'             => "订单编号:".$ary_order['o_id'],
            'total_fee'           => sprintf("%.2f",$ary_order['o_all_price'])
        );
        $req_data = $this->getArrToHtml($data);
        
        $para = array(
            "service"        => $str_service,
            "partner"        => trim($this->config['identity_id']),
            "sec_id"         => trim($this->config['pay_encryp']),
            "format"         => $this->format,
            "v"              => $this->version,
            "req_id"         => 'gysoft' . $int_ps_id . CI_SN . date('Ymdhis'),
            "req_data"       => $req_data,
            "_input_charset" => trim(strtolower($this->_input_charset))
        );
        if($this->op_source != 'app'){
            $this->doWapPay($para);
            M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        }else{
            $data = $this->doAppPayNew($para);
            if($data['result'] == true){
                //M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
            }
			 M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
            return $data;
        }
        exit;

        fail:
            if($this->op_source != 'app'){
                $this->error($str_error);
            }else{
                $this->result['status'] = false;
                $this->result['info'] = $str_error;
                return $this->result;
            }
    }

    /**
     * app接口支付
     * @author Tom <helong@guanysoft.com>
     * @date 2015-03-26
     */
    protected function doAppPay($param){
        $ali_config = $this->getAliConfig();
        // 获取 TOKEN
        $alipaySubmit = new AlipaySubmit($ali_config);
        $param = $alipaySubmit->buildRequestPara($param);
        if(empty($param['sign'])){
            $this->result['status'] = false;
            $this->result['info'] = '签名失败';
            return $this->result;
        }
        $data = array(
            'param'   => $param,
            'cacert'  => $ali_config['cacert'],
            'gateway' => $alipaySubmit->alipay_gateway_new,
            'charset' => trim(strtolower($ali_config['input_charset'])),
            );
        $this->result['status'] = true;
        $this->result['info'] = $data;
        return $this->result;
    }

    /**
     * app接口支付 (包含获取 TOKEN 接口)
     * @author Tom <helong@guanysoft.com>
     * @date 2015-03-26
     */
    protected function doAppPayNew($param){
        $ali_config = $this->getAliConfig();
        // 获取 TOKEN
        $alipaySubmit = new AlipaySubmit($ali_config);
        $html_text = $alipaySubmit->buildRequestHttp($param);
        $html_text = urldecode($html_text);
        $para_html_text = $alipaySubmit->parseResponse($html_text);

        //获取request_token
        $request_token = $para_html_text['request_token'];
        if(empty($request_token)){
            $this->result['result'] = false;
            $this->result['info'] = '获取token失败';
            return $this->result;
        }

        // 支付
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        $param['service'] = $this->getService($this->config['interface_type'].'1');
        $param['req_data'] = $req_data;

        //建立请求
        $data = $alipaySubmit->buildRequestForm($param, 'get', '确认');
        $this->result['result'] = true;
        $this->result['info'] = $data;
        return $this->result;
    }

    /**
     * wap站支付
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    protected function doWapPay($param){
        $ali_config = $this->getAliConfig();
        // 获取 TOKEN
        $alipaySubmit = new AlipaySubmit($ali_config);
        $html_text = $alipaySubmit->buildRequestHttp($param);
        $html_text = urldecode($html_text);
        $para_html_text = $alipaySubmit->parseResponse($html_text);

        //获取request_token
        $request_token = $para_html_text['request_token'];
        if(empty($request_token)){
            $this->error('获取token失败');
        }

        // 支付
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        $param['service'] = $this->getService($this->config['interface_type'].'1');
        $param['req_data'] = $req_data;

        //建立请求
        $html_text = $alipaySubmit->buildRequestForm($param, 'get', '确认');
        echo $html_text;
    }

    /**
     * 获取接口名称
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    protected function getService($interface_type){
        switch ($interface_type) {
            case '1':
                //支付宝WAP获取token接口
                $str_service = 'alipay.wap.trade.create.direct';
                break;
            case '11':
                // 支付宝wap支付接口
                $str_service = 'alipay.wap.auth.authAndExecute';
            default:
                break;
        }
        return $str_service;
    }

    /**
     * 获取配置信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    protected function getAliConfig($service){
        $config = array(
            'partner'             => $this->config['identity_id'],
            'key'                 => $this->config['pay_safe_code'],
            'private_key_path'    => $this->config['wap_shop_private_key']['upload_path'],
            'ali_public_key_path' => $this->config['wap_alipay_public_key']['upload_path'],
            'sign_type'           => $this->config['pay_encryp'],
            'input_charset'       => $this->_input_charset,
            'cacert'              => $this->cacert,
            'transport'           => $this->transport
            );
        return $config;
    }

    /**
     * 响应支付宝通知
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-26
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $ali_config = $this->getAliConfig();
        $alipayNotify = new AlipayNotify($ali_config);
        if(isset($data['notify_data']) && !empty($data['notify_data'])){
            $verify_result = $alipayNotify->verifyNotify($data);
        }else{
            $verify_result = $alipayNotify->verifyReturn($data);
        }
		//dump($verify_result);die();
        
        if($verify_result === false){
            return array('result'=>false);
        }
		if(isset($data['notify_data']) && !empty($data['notify_data'])){
			$data = xml2array($data['notify_data']);
		}
		if($data['result'] == 'success'){
			$data['trade_status'] = 'TRADE_SUCCESS';
		}
        if($data['trade_status'] != 'TRADE_SUCCESS'){
			return array('result'=>false);
        }
		writeLog("ary_data: ".json_encode($data),"order_status.log");
        //自定义的流水号 GY+ps_id
		$out_trade_no = explode('-',$data['out_trade_no']);
		$int_ps_id = $out_trade_no[1];
        //$int_ps_id = ltrim($data['out_trade_no'], 'gysoft'.CI_SN.'-');
        //外部网关流水号
        $str_trade_no = $data['trade_no'];
        $data['trade_status'] = 'TRADE_SUCCESS';
        //支付状态
        $int_status = $this->getTradeStatus($data['trade_status']);
        if($int_status == '1' && !empty($int_ps_id)){
            $ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
            if(!empty($ary_paymentSerial) && $ary_paymentSerial['ps_status'] == 1){
                //已经存在相同支付流水号的
                return array('result' => true,'o_id'=>$ary_paymentSerial['o_id']);
            }
        }
        //更改第三方流水单状态
        $result = $this->updataPaymentSerial($int_ps_id, $int_status, $data['trade_status'], $str_trade_no);
        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        return array(
            'result'     => $result,
            'o_id'       => $ary_paymentSerial['o_id'],
            'int_status' => $int_status,
            "total_fee"  => $ary_paymentSerial['ps_money'],
            "gw_code"    => $str_trade_no,
            'm_id'       => $m_id,
            'int_ps_id'  => $int_ps_id
        );
    }

    /**
     * 获取交易状态
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    public function getTradeStatus($tradeStatus){
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
        if ($tradeStatus == 'WAIT_SELLER_SEND_GOODS') {
            $int_status = 2;
        } elseif ($tradeStatus == 'TRADE_FINISHED') {
            $int_status = 3;
        } elseif ($tradeStatus == 'TRADE_SUCCESS') {
            $int_status = 1;
        } else if ($tradeStatus == 'WAIT_BUYER_PAY'){
            $int_status = 5;
        } else {
            $int_status = 4;
        }
        return $int_status;
    }

    /**
     * 数组转换为html
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-26
     */
    public function getArrToHtml($data){
        $str_data = '<direct_trade_create_req>';
        if(is_array($data) && !empty($data)){
            foreach($data as $node=>$value){
                $str_data .= '<'.$node.'>'.$value.'</'.$node.'>';
            }
        }
        $str_data .= '</direct_trade_create_req>';
        return $str_data;
    }

}
