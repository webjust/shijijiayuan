<?php
require_once('wxpay/WxPayPubHelper.php');
/**
 * 支付宝支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.8.2
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-03-30
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class WEIXIN extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-03-30
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['weixin_account'] = $param['weixin_account'];
        $config['weixin_appid'] = $param['weixin_appid'];
        $config['weixin_appsecret'] = $param['weixin_appsecret'];
		$config['weixin_key'] = $param['weixin_key'];
        $config['pc_fee'] = $param['pc_fee'];
        $this->config = $config;
    }

    /**
     * 支付订单
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date  2015-03-30
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0,$new_payment_id,$pay_code) {
        $ary_order = parent::pay($str_oid);
		if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
		$pay_config = D('Gyfx')->selectOneCache('payment_cfg','pc_config',array('pc_abbreviation' => 'WEIXIN'));
		$pay_config['pc_config'] = json_decode($pay_config['pc_config'], TRUE);
	    //如果是手机访问，自动跳转到公众号支付
        if(check_wap()){
			//使用jsapi接口
			$jsApi = new JsApi_pub($pay_config['pc_config']);

			//=========步骤1：网页授权获取用户openid============
			//通过code获得openid
			if (!isset($pay_code))
			{
				$js_api_call_url = U('Wap/Orders/paymentPage?oid=' . $str_oid.'&new_payment_id='.$new_payment_id.'&typeStat='.$pay_type, '', true, false, true);
				//触发微信返回code码
				$url = $jsApi->createOauthUrlForCode($js_api_call_url);
				Header("Location:".$url); die();
			}else
			{
				//获取code码，以获取openid
				$code = $pay_code;
				$jsApi->setCode($code);
				$openid = $jsApi->getOpenId();
			}
			//$log = new Log_();
			//$log->log_result('test.log',$openid.'-1');
			//=========步骤2：使用统一支付接口，获取prepay_id============
			//使用统一支付接口
			$unifiedOrder = new UnifiedOrder_pub($pay_config['pc_config']);
			
			//设置统一支付接口参数
			//设置必填参数
			//appid已填,商户无需重复填写
			//mch_id已填,商户无需重复填写
			//noncestr已填,商户无需重复填写
			//spbill_create_ip已填,商户无需重复填写
			//sign已填,商户无需重复填写
			$unifiedOrder->setParameter("openid",$openid);//商品描述
			$unifiedOrder->setParameter("body","订单支付");//商品描述
			//自定义订单号，此处仅作举例
			$out_trade_no = 'gysoft'.CI_SN.'-' . $int_ps_id;
			$unifiedOrder->setParameter("out_trade_no",$out_trade_no);//商户订单号 
			$unifiedOrder->setParameter("total_fee",$ary_order['o_all_price']*100);//总金额
			$unifiedOrder->setParameter("notify_url",U('Wap/User/synPayWeixinNotify?code=' . $this->code, '', true, false, true));//通知地址 
			$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
			//$unifiedOrder->setParameter("showwxpaytitle",1);//微信安全支付标题
			
			//非必填参数，商户可根据实际情况选填
			//$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号  
			//$unifiedOrder->setParameter("device_info","XXXX");//设备号 
			//$unifiedOrder->setParameter("attach","XXXX");//附加数据 
			//$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
			//$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间 
			//$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记 
			//$unifiedOrder->setParameter("openid","XXXX");//用户标识
			//$unifiedOrder->setParameter("product_id","XXXX");//商品ID
			$prepay_id = $unifiedOrder->getPrepayId();
			//=========步骤3：使用jsapi调起支付============
			$jsApi->setPrepayId($prepay_id);
			$jsApiParameters = $jsApi->getParameters();	
			//商户自行增加处理流程
			$url = U('Wap/Orders/wxZf').'?parameters=' . $jsApiParameters.'&oid='.$str_oid;
			//订单预处理有事务此处要提交
			M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
			redirect($url);						
        }else{
			echo '暂时不支持PC扫码支付';die();
			//如果是PC端支付，扫码支付
			//使用统一支付接口
			$unifiedOrder = new UnifiedOrder_pub($pay_config['pc_config']);	
			//设置统一支付接口参数
			//设置必填参数
			//appid已填,商户无需重复填写
			//mch_id已填,商户无需重复填写
			//noncestr已填,商户无需重复填写
			//spbill_create_ip已填,商户无需重复填写
			//sign已填,商户无需重复填写
			$unifiedOrder->setParameter("body","订单支付");//商品描述			
			//生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
			$int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
			$out_trade_no = 'gysoft'.CI_SN.'-' . $int_ps_id;
			$unifiedOrder->setParameter("out_trade_no",$out_trade_no);//商户订单号 
			$unifiedOrder->setParameter("total_fee",$ary_order['o_all_price']*100);//总金额
			$unifiedOrder->setParameter("notify_url",U('Wap/User/synPayWeixinNotify?code=' . $this->code, '', true, false, true));//通知地址 
			$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
			//非必填参数，商户可根据实际情况选填
			//$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号  
			//$unifiedOrder->setParameter("device_info","XXXX");//设备号 
			//$unifiedOrder->setParameter("attach","XXXX");//附加数据 
			//$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
			//$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间 
			//$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记 
			//$unifiedOrder->setParameter("openid","XXXX");//用户标识
			//$unifiedOrder->setParameter("product_id","XXXX");//商品ID			
			//获取统一支付接口结果
			$unifiedOrderResult = $unifiedOrder->getResult();
			//商户根据实际情况设置相应的处理流程
			if ($unifiedOrderResult["return_code"] == "FAIL") 
			{
				//商户自行增加处理流程
				echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";exit;
			}
			elseif($unifiedOrderResult["result_code"] == "FAIL")
			{
				//商户自行增加处理流程
				echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
				echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";exit;
			}
			elseif($unifiedOrderResult["code_url"] != NULL)
			{
				//从统一支付接口获取到code_url
				$code_url = $unifiedOrderResult["code_url"];
				//商户自行增加处理流程
				$url = U('Wap/Orders/wxCode').'?code_url=' . $code_url.'&oid='.$ary_order['o_id'];
				//订单预处理有事务此处要提交
				M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
				redirect($url);				
			}			
		}
    }

    /**
     * 预存款充值
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-25
     * @param float $flt_money 要充值的金额
     */
    public function charge($flt_money) {
		//暂时不要
    }

    /**
     * 响应支付宝通知
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-04-02
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($xml) {
        $data = xml2array($xml);
		$data['total_fee'] = $data['total_fee']/100;
        //自定义的流水号 GY+ps_id
		$out_trade_no = explode('-',$data['out_trade_no']);
		$int_ps_id = $out_trade_no[1];
        //外部网关流水号
        $str_trade_no = $data['transaction_id'];
		$pay_config = D('Gyfx')->selectOneCache('payment_cfg','pc_config',array('pc_abbreviation' => 'WEIXIN'));
		$pay_config['pc_config'] = json_decode($pay_config['pc_config'], TRUE);
		//使用通用通知接口
		$notify = new Notify_pub($pay_config['pc_config']);
		$notify->saveData($xml);
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		//$log_ = new Log_();
		//$log_name="./notify_url.log";//log文件路径
		//$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");
		$int_status = 0;
		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				//$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
				return array('result' => false);
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				//$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
				return array('result' => false);
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				//$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
				 $int_status = 1;
			}
			
			//商户自行增加处理流程,
			//例如：更新订单状态
			//例如：数据库操作
			//例如：推送支付完成信息
		}

        //支付状态
        //1为直接付款成功，2为付款至担保方，3为付款至担保方结算完成，4为其他状态.退款退货暂不处理
		if($int_status == '1'){
			if(!empty($int_ps_id)){
				$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
				if(!empty($ary_paymentSerial)){
					if($ary_paymentSerial['ps_status'] == 1){
						//已经存在相同支付流水号的
						return array('result' => true,'o_id'=>$data['subject']);
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
        $result = $this->updataPaymentSerial($int_ps_id, $int_status, $notify->data["result_code"], $str_trade_no);
        //根据流水单号返回会员ID
        $m_id = $this->getMemberIdByPsid($int_ps_id);
        return array(
            'result' => $result,
            'o_id' => $ary_paymentSerial['o_id'],
            'int_status' => $int_status,
            "total_fee" => $data['total_fee'],
            "gw_code" => $str_trade_no,
            'm_id' => $m_id,
			'int_ps_id'=>$int_ps_id
        );
    }
	
}
