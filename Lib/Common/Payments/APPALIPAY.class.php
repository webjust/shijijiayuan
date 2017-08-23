<?php

/**
 * 手机APP支付宝支付类
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
class APPALIPAY extends Payments implements IPayments {

    private $_input_charset = 'utf-8';  // 编码方式
    private $result         = array();  // 返回结果
    private $op_source      = 'app';    // 操作来源  (wap/pc/app)
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
        $config['app_alipay_public_key'] = $param['app_alipay_public_key']; // 支付宝公钥
        $config['app_shop_public_key']   = $param['app_shop_public_key'];   // 商户公钥
        $config['app_shop_private_key']  = $param['app_shop_private_key'];  // 商户私钥
        $this->config = $config;
        // chdir(dirname(realpath(__FILE__)));
        // $this->cacert = getcwd() . DIRECTORY_SEPARATOR . 'WAPALIPAY' . DIRECTORY_SEPARATOR . 'cacert.pem';
        $this->cacert = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'APPALIPAY' . DIRECTORY_SEPARATOR . 'cacert.pem';
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
     * @date 2015-01-23
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
 writeLog(json_encode($ary_order),"AAorder_pay.log");
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        
        //请求参数
        $param = array(
            'service'        => $str_service,
            'partner'        => $this->config['identity_id'],
            'seller_id'      => $this->config['alipay_account'],
            'out_trade_no'   => 'gysoft'.CI_SN.'-' . $int_ps_id,
            'subject'        => "订单编号:".$ary_order['o_id'],
            '_input_charset' => $this->_input_charset,
            'notify_url'     => U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true), //异步通知地址
            'total_fee'      => sprintf("%.2f",$ary_order['o_all_price']),
            'body'           => '测试测试目前写死的,需要更改',
            'payment_type'   => 1,
            // 未付款交易的超时时间 (string) (1m～15d，或者使用绝对时间（示例格式：2014-06-13 16:00:00）m-分钟， h-小时， d-天， 1c当天)
            // 'it_b_pay' => ,
        );

        $ali_config = $this->getAliConfig();
        $alipaySubmit = new AlipaySubmit($ali_config);

        
        if($this->op_source == 'app'){
            $data = $alipaySubmit->buildRequestPara($param);
            $this->result['status'] = true;
            $this->result['info'] = $data;
            M('',C('DB_PREFIX'),'DB_CUSTOM')->commit(); //订单预处理有事务此处要提交
            return $this->result;
        }else{
            echo "开发调试中......";die;
            $data = $alipaySubmit->buildRequestForm($param,'get','提交');
            M('',C('DB_PREFIX'),'DB_CUSTOM')->commit(); //订单预处理有事务此处要提交
            echo $data;die;
        }
        
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
     * 响应支付宝通知
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-26
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data,$tag = false) {
        $ali_config = $this->getAliConfig();
        $alipayNotify = new AlipayNotify($ali_config);
        if($tag === false){
            $verify_result = $alipayNotify->OldverifyNotify($data);
        }else{
            echo "开发调试中.......";die; // 等待调试
            $verify_result = $alipayNotify->OldverifyReturn($data);
        }
        
        if($verifyReturn === false){
            return array('result'=>false);
        }
        //自定义的流水号 GY+ps_id
        //$int_ps_id = ltrim($data['out_trade_no'], 'gysoft'.CI_SN.'-');
		$int_ps_id = str_replace('gysoft'.CI_SN.'-','',$data['out_trade_no']);
        //外部网关流水号
        $str_trade_no = $data['trade_no'];
        //支付状态
        $int_status = $this->getTradeStatus($data['trade_status']);
        $ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
        if($int_status == '1' && !empty($int_ps_id)){
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
     * 获取配置信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    protected function getAliConfig($service){
        $config = array(
            'partner'             => $this->config['identity_id'],
            'key'                 => $this->config['pay_safe_code'],
            'private_key_path'    => $this->config['app_shop_private_key']['upload_path'],
            'ali_public_key_path' => $this->config['app_alipay_public_key']['upload_path'],
            'sign_type'           => $this->config['pay_encryp'],
            'input_charset'       => $this->_input_charset,
            'cacert'              => $this->cacert,
            'transport'           => $this->transport
            );
        return $config;
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
     * 获取接口名称
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-25
     */
    protected function getService($interface_type){
        switch ($interface_type) {
            case '1':
                // 支付宝手机app支付接口
                $str_service = 'mobile.securitypay.pay';
                break;
            default:
                break;
        }
        return $str_service;
    }
    
}