<?php
include_once("chinapay/".CI_SN."/netpayclient.php");
/**
 * 银联在线支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-08-21
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CHINAPAY extends Payments implements IPayments {
    /**
     * 设置支付方式的配置信息
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['MerPrk'] = $param['MerPrk'];
        $config['netpayclient'] = $param['netpayclient'];
        $config['PgPubk'] = $param['PgPubk'];
        $this->config = $config;
    }
    
    /**
     * 支付网关请求参数
     * 
     * @param o_id 订单id
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-21
     */
    public function pay($o_id,$type=0,$o_pay=0.000,$pay_type=0){
        $ary_order = parent::pay($o_id);
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        $amt = sprintf('%.2f', $ary_order['o_all_price']);
        $ary_amt = explode('.',$amt);
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        //商户id
        $pay_config = D('Gyfx')->selectOneCache('payment_cfg',$ary_field=null, array('pc_pay_type'=>'chinapay'), $ary_order=null);
		$m_key_info = json_decode($pay_config['pc_config'],1);
		$m_key_info = explode('/',$m_key_info['MerPrk']['upload_path']);$m_key_info['MerPrk']['upload_path'];
       $v_MerId = buildKey($m_key_info[count($m_key_info)-1]);
        if(!$v_MerId){
            $this->error('商户号不存在！请联系客服人员');exit;
        }
		/**
        $o_thd_sn = D("orders")->where(array('o_id'=>$o_id))->getField('o_thd_sn');
        if(empty($o_thd_sn)){
            $o_thd_sn = date('Ym').$this->createSn($int_ps_id).'1';
            D("orders")->where(array('o_id'=>$o_id))->save(array('o_thd_sn'=>$o_thd_sn));
        } **/
		$o_thd_sn = $this->createSnAuto($int_ps_id);
        //第三方支付订单id
        $v_OrdId = $o_thd_sn;
        //订单金额
        $v_TransAmt = padstr($ary_amt[0].$ary_amt[1],12);
        //支付币种 156默认为人民币
        $v_CuryId = '156';
        //支付时间
        $v_TransDate = date("Ymd");
        //支付类型 0001为消费付款
        $v_TransType = '0001';
        //支付接入版本号
        $v_Version = '20070129';
        //后台接收URL
        $v_BgRetUrll = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
        //页面接收URL
        $v_PageRetUrl = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
        //商户私有域1
        $v_priv1 = $this->getVprivInfo($ary_order);
        //签名参数拼接
        $plain = $v_MerId.$v_OrdId.$v_TransAmt.$v_CuryId.$v_TransDate.$v_TransType.$v_priv1;
        //生成签名
        $chkvalue = sign($plain);
        $data = array('v_MerId'=>$v_MerId,
                      'v_OrdId'=>$v_OrdId,
                      'v_TransAmt'=>$v_TransAmt,
                      'v_CuryId'=>$v_CuryId,
                      'v_TransDate'=>$v_TransDate,
                      'v_TransType'=>$v_TransType,
                      'v_Version'=>$v_Version,
                      'v_BgRetUrll'=>$v_BgRetUrll,
                      'v_PageRetUrl'=>$v_PageRetUrl,
                      'v_ChkValue'=>$chkvalue,
                      'v_priv1'=>$v_priv1);
        $this->assign($data);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:CHINAPAY");exit;
        
    }
    
    
    /**
     * 预存款在线充值请求参数
     * 
     * @param o_id 订单id
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-21
     */
    public function charge($flt_money){
        $amt = sprintf('%.2f', $flt_money);
        $ary_amt = explode('.',$amt);
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
		$o_thd_sn = $this->createSnAuto($int_ps_id);
        //商户id
        $pay_config = D('Gyfx')->selectOneCache('payment_cfg',$ary_field=null, array('pc_pay_type'=>'chinapay'), $ary_order=null);
		$m_key_info = json_decode($pay_config['pc_config'],1);
		$m_key_info = explode('/',$m_key_info['MerPrk']['upload_path']);$m_key_info['MerPrk']['upload_path'];
       $v_MerId = buildKey($m_key_info[count($m_key_info)-1]);
        if(!$v_MerId){
            $this->error('商户号不存在！请联系客服人员');exit;
        }
        //第三方支付订单id
        $v_OrdId = $o_thd_sn;
        //订单金额
        $v_TransAmt = padstr($ary_amt[0].$ary_amt[1],12);
        //支付币种 156默认为人民币
        $v_CuryId = '156';
        //支付时间
        $v_TransDate = date("Ymd");
        //支付类型 0001为消费付款
        $v_TransType = '0001';
        //支付接入版本号
        $v_Version = '20070129';
        //后台接收URL
        $v_BgRetUrll = U('Home/User/synChargeNotify?code=' . $this->code, '', true, false, true);
        //页面接收URL
        $v_PageRetUrl = U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true);
        //商户私有域1
        $v_priv1 = 'Charge';
        //签名参数拼接
        $plain = $v_MerId.$v_OrdId.$v_TransAmt.$v_CuryId.$v_TransDate.$v_TransType.$v_priv1;
        //生成签名
        $chkvalue = sign($plain);
        $data = array('v_MerId'=>$v_MerId,
                      'v_OrdId'=>$v_OrdId,
                      'v_TransAmt'=>$v_TransAmt,
                      'v_CuryId'=>$v_CuryId,
                      'v_TransDate'=>$v_TransDate,
                      'v_TransType'=>$v_TransType,
                      'v_Version'=>$v_Version,
                      'v_BgRetUrll'=>$v_BgRetUrll,
                      'v_PageRetUrl'=>$v_PageRetUrl,
                      'v_ChkValue'=>$chkvalue,
                      'v_priv1'=>$v_priv1);
        $this->assign($data);
        $this->display("Ucenter:Pay:CHINAPAY");
    }

    /**
     * 响应网银在线通知
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-26
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $int_ps_id = intval(substr($data['orderno'],8));
        if($data['status'] != '1001'){
            $this->erroe('支付失败');exit;
        }
        //导入公钥文件
        $flag = buildKey('PgPubk.key');
        
        //商户号
        $merid = $data['merid'];
        //第三方订单sn
        $orderno = $data['orderno'];
        //支付时间
        $transdate = $data['transdate'];
        //支付金额
        $amount = $data['amount'];
        //支付币种
        $currencycode = $data['currencycode'];
        //支付类型 ---0001 消费支付
        $transtype = $data['transtype'];
        //是否支付成功 --- 1001支付成功
        $status = $data['status'];
        //支付方签名
        $checkvalue = $data['checkvalue'];
        //付款银行
        $GateId = $data['GateId'];
        //商户私有域
        $Priv1 = $data['Priv1'];
        //对订单的签名验证
        $flag = verifyTransResponse($merid,$orderno,$amount,$currencycode,$transdate,$transtype,$status,$checkvalue);
        //响应的签名验证不通过
        if(!$flag){
            return array('result' => false);
        }
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
        if($o_id == '0'){
            //充值
            $double_all_price = sprintf('%.2f',D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->getField('ps_money'));
            $ary_amt = explode('.',$double_all_price);
            $v_TransAmt = padstr($ary_amt[0].$ary_amt[1],12);
            if($v_TransAmt != $amount){
                //将应支付金额重新转成支付格式，判断2次支付金额是否一致
                return array('result'=>false);
            }
        }else{
            //获取订单应支付金额
            $double_all_price = sprintf('%.2f', M('orders')->where(array('o_id'=>$o_id))->getField('o_all_price'));
            $ary_amt = explode('.',$double_all_price);
            $v_TransAmt = padstr($ary_amt[0].$ary_amt[1],12);
            if($v_TransAmt != $amount){
                //将应支付金额重新转成支付格式，判断2次支付金额是否一致
                return array('result'=>false,'o_id'=>$o_id);
            }
        }
        
        //付款成功
        $int_status = 1;
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id,'ps_status'=>1))->getField('ps_id');
				if(!empty($ary_paymentSerial)){
					//已经存在相同支付流水号的
					return array('result' => true,'o_id'=>$o_id);
				}
			}
		}
        $this->updataPaymentSerial($int_ps_id, $int_status, $status, $orderno);
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        
        
        return array(
            'result' => true,
            'o_id' => $o_id,
            'int_status' => $int_status,
            "total_fee" => $double_all_price,
            "gw_code" => $orderno,
            'm_id' => $m_id,
            'merid'=>$merid,
			'int_ps_id'=>$int_ps_id
        );

    }
	
	/**
     * 生成序列号
     * 16位
     * @param ps_id
	 * @by wangguibin
	 * @date 2014-11-18 12:30
     */
    private function createSnAuto($ps_id){
		$random_string = '';
		//ps_id不足8位用零填充
		$random_length = 8-strlen($ps_id);
		if($random_length>0){
			for ($i = 0; $i < $random_length; $i++) {
				$random_string .= '0';
			}		
		}
		return date('Ymd').$random_string.$ps_id;
	}
	
}