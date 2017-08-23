<?php

/**
 * 交通银行支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 8.3
 * @author hcaijin <huangcaijin@guanyisoft.com>
 * @date 2015-07-14
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class BOCOMPAY extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-07-14
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $this->config = $param;
    }

    /**
     * 支付订单
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-07-14
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
		$payType = $this->_request('payType');
        $ary_order = parent::pay($str_oid);

        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        if(empty($int_ps_id)){
            return array("result"=>false,"message"=>"生成支付序列号失败,请重试!");
        }
        if($type == '5' || $pay_type != 0){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //获取商户编号
        $v_mid = $this->config['MERCHANT_ID'];
        //支付进金额
        $v_amount = sprintf('%.2f', $ary_order['o_all_price']);
        //支付币种。默认RMB
        $v_moneytype = "CNY";
        //订单支付编号
        $v_oid = 'gysoft'.CI_SN.'-' . $int_ps_id;
        //回打地址
        $v_url = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
		$remark2 = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true); //服务器异步通知的接收地址。
        //获得表单传过来的数据
        $merID = $v_mid;
        $interfaceVersion = '1.0.0.0';
        $orderid = $v_oid;
        $orderDate = date('Ymd');
        $orderTime = date('His');
        $tranType = '0';
        $amount = $v_amount;
        $curType = $v_moneytype;
        $orderContent = '';
        $orderMono = '';
        $phdFlag = '1';
        $notifyType = '1'; //通知方式 0不通知，1通知
        $merURL = $remark2;
        $goodsURL = $v_url;
        $jumpSeconds = '5'; //自动跳转时间
        $payBatchNo = '';
        $proxyMerName = '';
        $proxyMerType = '';
        $proxyMerCredentials = '';
        $netType = '0';
        $issBankNo = empty($payType)?"":strtoupper($payType); //发卡行行号,不输默认为交行
        $tranCode = "cb2200_sign";

        $source = "";
        
        //htmlentities($orderMono,"ENT_QUOTES","GB2312");
        //连接字符串
        $source = $interfaceVersion."|".$merID."|".$orderid."|".$orderDate."|".$orderTime."|".$tranType."|"
        .$amount."|".$curType."|".$orderContent."|".$orderMono."|".$phdFlag."|".$notifyType."|".$merURL."|"
        .$goodsURL."|".$jumpSeconds."|".$payBatchNo."|".$proxyMerName."|".$proxyMerType."|".$proxyMerCredentials."|".$netType;


        //连接地址
        $socketUrl = "tcp://127.0.0.1:8891";
        $fp = stream_socket_client($socketUrl, $errno, $errstr, 30);
        $retMsg="";
        //
        if (!$fp) {
            return array("result"=>false,"message"=>"$errstr ($errno)<br />\n");
        } else 
        {
            $in  = "<?xml version='1.0' encoding='UTF-8'?>";
            $in .= "<Message>";
            $in .= "<TranCode>".$tranCode."</TranCode>";
            $in .= "<MsgContent>".$source."</MsgContent>";
            $in .= "</Message>";
            fwrite($fp, $in);
            while (!feof($fp)) {
                $retMsg =$retMsg.fgets($fp, 1024);
                
            }
            fclose($fp);
        }	
        //解析返回xml
        $dom = new DOMDocument;
        $dom->loadXML($retMsg);

        $retCode = $dom->getElementsByTagName('retCode');
        $retCode_value = $retCode->item(0)->nodeValue;
        
        $errMsg = $dom->getElementsByTagName('errMsg');
        $errMsg_value = $errMsg->item(0)->nodeValue;

        $signMsg = $dom->getElementsByTagName('signMsg');
        $signMsg_value = $signMsg->item(0)->nodeValue;

        $orderUrl = $dom->getElementsByTagName('orderUrl');
        $orderUrl_value = $orderUrl->item(0)->nodeValue;
        
        $MerchID = $dom->getElementsByTagName('MerchID');
        $merID = $MerchID->item(0)->nodeValue;
        //echo "retMsg=".$retMsg;
        //echo $retCode_value." ".$errMsg_value." ".$signMsg_value." ".$orderUrl_value;

        if($retCode_value != "0"){
            //echo "交易返回码：".$retCode_value."<br>";
            //echo "交易错误信息：" .$errMsg_value."<br>";
            if(empty($retCode_value) && empty($errMsg_value)){
                $str_errMsg = "交易失败！";
            }else{
                $str_errMsg = "交易返回码：".$retCode_value."<br>"."交易错误信息：" .$errMsg_value."<br>";
            }
            return array("result"=>false,"message"=>$str_errMsg);
        }else{
            $data = array(
                'orderUrl_value'=>$orderUrl_value,
                'interfaceVersion'=>$interfaceVersion,
                'merID'=>$merID,
                'orderid'=>$orderid,
                'orderDate'=>$orderDate,
                'orderTime'=>$orderTime,
                'tranType'=>$tranType,
                'amount'=>$amount,
                'curType'=>$curType,
                'orderContent'=>$orderContent,
                'orderMono'=>$orderMono,
                'phdFlag'=>$phdFlag,
                'notifyType'=>$notifyType,
                'merURL'=>$merURL,
                'goodsURL'=>$goodsURL,
                'jumpSeconds'=>$jumpSeconds,
                'payBatchNo'=>$payBatchNo,
                'proxyMerName'=>$proxyMerName,
                'proxyMerType'=>$proxyMerType,
                'proxyMerCredentials'=>$proxyMerCredentials,
                'netType'=>$netType,
                'signMsg_value'=>$signMsg_value,
                'issBankNo'=>$issBankNo,
            );
            $this->assign($data);
            //订单预处理有事务此处要提交
            M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
            $this->display("Ucenter:Pay:BOCOMPAY");
            exit;
        }
    }

    /**
     * 响应交行通知
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-07-14
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
        $tranCode = "cb2200_verify";
        $notifyMsg = $data["notifyMsg"];   
        $lastIndex = strripos($notifyMsg,"|");
        $signMsg = substr($notifyMsg,$lastIndex+1); //签名信息
        $srcMsg = substr($notifyMsg,0,$lastIndex+1);//原文
        $merID = $this->config['MERCHANT_ID'];

        //连接地址
        $socketUrl = "tcp://127.0.0.1:8891";
        $fp = stream_socket_client($socketUrl, $errno, $errstr, 30);
        $retMsg="";
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
            $in  = "<?xml version='1.0' encoding='UTF-8'?>";
            $in .= "<Message>";
            $in .= "<TranCode>".$tranCode."</TranCode>";
            $in .= "<merchantID>".$merID."</merchantID>";
            $in .= "<MsgContent>".$notifyMsg."</MsgContent>";
            $in .= "</Message>";
            fwrite($fp, $in);
            while (!feof($fp)) {
                $retMsg =$retMsg.fgets($fp, 1024);
                
            }
            fclose($fp);
        }	
        
        //解析返回xml
        $dom = new DOMDocument;
        $dom->loadXML($retMsg);

        $retCode = $dom->getElementsByTagName('retCode');
        $retCode_value = $retCode->item(0)->nodeValue;
        
        $errMsg = $dom->getElementsByTagName('errMsg');
        $errMsg_value = $errMsg->item(0)->nodeValue;

        //echo "retCode=".$retCode_value."  "."errMsg=".$errMsg_value;
        if($retCode_value != '0'){
            echo "交易返回码：".$retCode_value."<br>";
            echo "交易错误信息：" .$errMsg_value."<br>";
            return array('result' => false);
        }else{
            $arr = preg_split("/\|{1,}/",$srcMsg);
        }

        //自定义的流水号 GY+ps_id
        $int_ps_id = ltrim($arr[1], 'gysoft'.CI_SN.'-');
        //交易金额
        $total_fee = $arr[2];
        //交易网关流水号
        $str_trade_no = $arr[8];
        //错误信息描述
        $payError = $arr[13];
        //支付状态
        $int_status = $arr[9];
        //支付状态=1为成功
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
        $result = $this->updataPaymentSerial($int_ps_id, $int_status, $payError, $str_trade_no);
        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        //根据流水单号返回订单ID
        $o_id = $this->getOidByPsid($int_ps_id);
        return array(
            'result' => $result,
            'o_id' => $o_id,
            'int_status' => $int_status,
            "total_fee" => $total_fee,
            "gw_code" => $str_trade_no,
            'm_id' => $m_id,
			'int_ps_id'=>$int_ps_id
        );
    }

}
