<?php
/**
 * 工商银行支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author Wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-12-16
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class ICBC extends Payments implements IPayments {
    /**
     * 设置支付方式的配置信息
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-12-16
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['icbc_account'] = $param['icbc_account'];
        $config['icbc_zh'] = $param['icbc_zh'];
        $config['icbc_sykey'] = $param['icbc_sykey'];
		$config['icbcFile'] = $param['icbcFile'];
		$config['certFile'] = $param['certFile'];
		$config['keyFile'] = $param['keyFile'];
        $this->config = $config;
    }
    
    /**
     * 支付网关请求参数
     * 
     * @param o_id 订单id
     * @authoe Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-12-16
     */
    public function pay($o_id,$type=0,$o_pay=0.000,$pay_type=0){
        $ary_order = parent::pay($o_id);
	    //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(0, $ary_order,$pay_type);
        if(empty($int_ps_id)){
            $this->error('生成支付序列号失败!');
            return false;die();
        }		
        if($type == '5'){
            $ary_order['o_all_price'] = $o_pay;
        }else{
            $ary_order['o_all_price'] = $ary_order ['o_all_price'] - $ary_order ['o_pay'];
        }
        //商户id
        $pay_config = D('Gyfx')->selectOneCache('payment_cfg',$ary_field=null, array('pc_pay_type'=>'icbc'), null);
		$m_key_info = json_decode($pay_config['pc_config'],1);
		//私钥保护密码
        $keyPass = trim($m_key_info['icbc_sykey']);
        //商城代码
        $merId = trim($m_key_info['icbc_account']);
        //工行帐号
        $icbcno = trim($m_key_info['icbc_zh']);
        $key = $m_key_info['keyFile']['upload_path'];//私钥文件
        $cert = $m_key_info['certFile']['upload_path'];//公钥文件
        $icbc = $m_key_info['icbcFile']['upload_path'];//工行公钥
        if(!file_exists($key)){ 
			$this->error('ICBC key file not found!');exit;
        }
         if(!file_exists($cert)){ 
			$this->error('ICBC Cert file not found!');exit;
        }		
		//提交参数
		$aREQ = array();
         //接口名称固定为“ICBC_PERBANK_B2C”
         $aREQ["interfaceName"] = "ICBC_PERBANK_B2C"; 
         //接口版本目前为“1.0.0.11”
         $aREQ["interfaceVersion"] = "1.0.0.11";   
         //商城代码，ICBC提供
         $aREQ["merID"] = $merId;
         //商户帐号，ICBC提供
         $aREQ["merAcct"] = $icbcno;
         //接收银行通知地址，目前只支持http协议80端口
		 $aREQ["merURL"] = U('Home/Icbc/synPayNotify?code=' . $this->code, '', true, false, true);
        // $aREQ["merURL"] = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
		//后台接收URL
        //$v_BgRetUrll = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
        //页面接收URL
        //$v_PageRetUrl = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
         //HS方式实时发送通知；AG方式不发送通知；
         $aREQ["notifyType"] = "HS";
         //订单号商户端产生，一天内不能重复,拼接上订单号和支付号。
         //$aREQ["orderid"] = $o_id.'-' . $int_ps_id;
		 $aREQ["orderid"] = $o_id.'-' . $int_ps_id;
         //金额以分为单位
         $aREQ["amount"] = $ary_order['o_all_price'] * 100;
         //分期付款期限
          $aREQ["installmentTimes"] = '1';
         //币种目前只支持人民币，代码为“001”
         $aREQ["curType"] = "001"; 
         //对于HS方式“0”：发送成功或者失败信息；“1”，只发送交易成功信息。
         $aREQ["resultType"] = '0';
         $aREQ["creditType"] = '2';
         //14位时间戳
         $aREQ["orderDate"] = date("YmdHis");
		 $sql ="select group_concat(g_sn) as g_sn,group_concat(oi_g_name) as oi_g_name,group_concat(oi_nums) as oi_nums from fx_orders_items where o_id=".$o_id." group by o_id limit 1";
		 $order_info = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->query($sql);
		 $oi_nums = explode(',',$order_info[0]['oi_nums']);
		 $oi_num = 0;
		 foreach($oi_nums as $tmp_num){
			$oi_num +=intval($tmp_num);
		 }
         //以上五个字段用于客户支付页面显示
         $aREQ["goodsID"] = $order_info[0]['g_sn'];
         //网关只认GB2312
         $aREQ["goodsName"]  = iconv("UTF-8","GB2312//IGNORE",$order_info[0]['oi_g_name']);
         $aREQ["goodsNum"] = $oi_num;
         //运费金额以分为单位
         $aREQ["carriageAmt"] = "0";
         //商户reference  商户网站域名
		 $GY_SHOP_HOST = D('SysConfig')->getConfigValueBySckey('GY_SHOP_HOST', 'GY_SHOP');
		 $ARY_GY_SHOP_HOST = explode(".",$GY_SHOP_HOST);
		 if($ARY_GY_SHOP_HOST!=''){
			 $ARY_GY_SHOP_HOST[0]='*';
			 $merReference=implode('.',$ARY_GY_SHOP_HOST);
			 $merReference = substr($merReference,0,strlen($merReference)-1); 
		 }
         $aREQ["merReference"] = $merReference;
         //客户端IP
         //获取客户端ip地址
		$ip = '';
		foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v) {
			if( isset($_SERVER[$v]) ){
				$ip = $_SERVER[$v];
				if(!empty($ip)) break;
			}
		}
         $aREQ["merCustomIp"] = $ip;   
         //虚拟商品/实物商品标识位
         $aREQ["goodsType"] = "";   
         //买家用户号
         $aREQ["merCustomID"] = "";    
         //买家联系电话
         $aREQ["merCustomPhone"] = "";          
         //收货地址
         $aREQ["goodsAddress"] = "";   
         //订单备注
         $aREQ["merOrderRemark"] = "";
         //商城提示
         $aREQ["merHint"] = "";
         //备注
         $aREQ["remark1"] = "";
         //备注2
         $aREQ["remark2"] = "";                      
         //“1”判断该客户是否与商户联名；取值“0”不检验客户是否与商户联名。
         $aREQ["verifyJoinFlag"] = '0';		
		 //构造V3版的xml
		$tranData = "<?xml version=\"1.0\" encoding=\"GBK\" standalone=\"no\"?><B2CReq><interfaceName>".$aREQ["interfaceName"]."</interfaceName><interfaceVersion>".$aREQ["interfaceVersion"]."</interfaceVersion><orderInfo><orderDate>".$aREQ["orderDate"]."</orderDate><curType>".$aREQ["curType"]."</curType><merID>".$aREQ["merID"]."</merID><subOrderInfoList><subOrderInfo><orderid>".$aREQ["orderid"]."</orderid><amount>".$aREQ["amount"]."</amount><installmentTimes>".$aREQ["installmentTimes"]."</installmentTimes><merAcct>".$aREQ["merAcct"]."</merAcct><goodsID>".$aREQ["goodsID"]."</goodsID><goodsName>".$aREQ["goodsName"]."</goodsName><goodsNum>".$aREQ["goodsNum"]."</goodsNum><carriageAmt>".$aREQ["carriageAmt"]."</carriageAmt></subOrderInfo></subOrderInfoList></orderInfo><custom><verifyJoinFlag>".$aREQ["verifyJoinFlag"]."</verifyJoinFlag><Language>ZH_CN</Language></custom><message><creditType>".$aREQ["creditType"]."</creditType><notifyType>".$aREQ["notifyType"]."</notifyType><resultType>".$aREQ["resultType"]."</resultType><merReference>".$aREQ["merReference"]."</merReference><merCustomIp>".$aREQ["merCustomIp"]."</merCustomIp><goodsType>".$aREQ["goodsType"]."</goodsType><merCustomID>".$aREQ["merCustomID"]."</merCustomID><merCustomPhone>".$aREQ["merCustomPhone"]."</merCustomPhone><goodsAddress>".$aREQ["goodsAddress"]."</goodsAddress><merOrderRemark>".$aREQ["merOrderRemark"]."</merOrderRemark><merHint>".$aREQ["merHint"]."</merHint><remark1>".$aREQ["remark1"]."</remark1><remark2>".$aREQ["remark2"]."</remark2><merURL>".$aREQ["merURL"]."</merURL><merVAR>".$aREQ["orderid"]."</merVAR></message></B2CReq>";
		
		//dump($tranData);
		if (strtoupper(substr(PHP_OS,0,3))=="WIN"){
             $bb = new COM("ICBCEBANKUTIL.B2CUtil");
             $rc=$bb->init($icbc,$cert,$key,$keyPass);
             $merSignMsg = $bb->signC($tranData, strlen($tranData));
             //print_r($bb->getRC());
			 $fp = fopen($cert,"rb");
			 $merCert = fread($fp,filesize($cert));
			 $merCert = base64_encode($merCert);
			 fclose($fp);
			 $tranData_info = base64_encode($tranData);
         }
         else{
			 /**
             //商户签名数据BASE64编码
             $cmd = "/bin/icbc_sign '{$key}' '{$keyPass}' '{$tranData}'";
             //error_log($cmd,3,__FILE__.".log");
             $handle = popen($cmd, 'r');
             $merSignMsg = fread($handle, 2096);
             pclose($handle);
			 **/	
			//读取商户私钥文件
			$keyfile=$key;
			if(strlen($keyfile) <= 0)
			{
				echo "WARNING : no key data input<br/>";
				exit();
			}
			
			$fp = fopen($keyfile,"rb");
			if($fp == NULL)
			{
				echo "open file error<br/>";
				exit();
			}
			
			fseek($fp,0,SEEK_END);
			$filelen=ftell($fp);
			fseek($fp,0,SEEK_SET);
			$contents = fread($fp,$filelen);
			fclose($fp);
			
			$key = substr($contents,2);
			//获取密钥口令
			$pass=$keyPass;
			if(strlen($pass) <= 0)
			{
				echo "WARNING : no key password input<br/>";
				exit();
			}
			//读取商户公钥文件
			$merCert=$cert;
			if(strlen($merCert) <= 0)
			{
				echo "WARNING : no merCert input<br/>";
				exit();
			}
			else
			{
				$fp2 = fopen($merCert,"rb");
				if($fp2 == NULL)
				{
					echo "open file error<br/>";
					exit();
				}
				fseek($fp2,0,SEEK_END);
				$filelen2=ftell($fp2);
				fseek($fp2,0,SEEK_SET);
				$cert = fread($fp2,$filelen2);
				fclose($fp2);
			}
			/*签名*/
			$signature = sign($tranData,$key,$pass);//签名
			$code = current($signature);//获取签名数据
			$len = next($signature);//获取签名数据长度
			$signcode = base64enc($code);//对签名数据BASE64编码
			//echo $signcode;exit;
			$merSignMsg = current($signcode);
			$tranDataBase64 = base64enc($tranData);//对表单数据BASE64编码
			$tranData_info = current($tranDataBase64);
			$merCertBase64 = base64enc($cert);//对证书BASE64编码
			$merCert = current($merCertBase64);
         } 	
	     $aFinalReq['interfaceName'] = $aREQ["interfaceName"];
	     $aFinalReq['interfaceVersion'] = $aREQ["interfaceVersion"];
	     $aFinalReq['tranData'] = $tranData_info;
	     $aFinalReq['merSignMsg'] = $merSignMsg;
		 //$aFinalReq['tranDataBase64'] = $tranDataBase64;
		 //$aFinalReq['merCertBase64'] = $merCertBase64;
	     $aFinalReq['merCert'] = $merCert;		 
		 //dump($aFinalReq);die();
        $this->assign('aFinalReq',$aFinalReq);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:ICBC");exit;
        
    }
    
    
    /**
     * 预存款在线充值请求参数
     * 
     * @param o_id 订单id
     * @authoe Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-12-16
     */
    public function charge($flt_money){
	    //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
        if(empty($int_ps_id)){
            $this->error('生成支付序列号失败!');
            return false;die();
        }		
        //商户id
        $pay_config = D('Gyfx')->selectOneCache('payment_cfg',$ary_field=null, array('pc_pay_type'=>'icbc'), null);
		$m_key_info = json_decode($pay_config['pc_config'],1);
		//私钥保护密码
        $keyPass = trim($m_key_info['icbc_sykey']);
        //商城代码
        $merId = trim($m_key_info['icbc_account']);
        //工行帐号
        $icbcno = trim($m_key_info['icbc_zh']);
        $key = $m_key_info['keyFile']['upload_path'];//私钥文件
        $cert = $m_key_info['certFile']['upload_path'];//公钥文件
        $icbc = $m_key_info['icbcFile']['upload_path'];//工行公钥
        if(!file_exists($key)){ 
			$this->error('ICBC key file not found!');exit;
        }
         if(!file_exists($cert)){ 
			$this->error('ICBC Cert file not found!');exit;
        }		
		//提交参数
		$aREQ = array();
         //接口名称固定为“ICBC_PERBANK_B2C”
         $aREQ["interfaceName"] = "ICBC_PERBANK_B2C"; 
         //接口版本目前为“1.0.0.11”
         $aREQ["interfaceVersion"] = "1.0.0.11";   
         //商城代码，ICBC提供
         $aREQ["merID"] = $merId;
         //商户帐号，ICBC提供
         $aREQ["merAcct"] = $icbcno;
         //接收银行通知地址，目前只支持http协议80端口
		 $aREQ["merURL"] = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
        // $aREQ["merURL"] = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
		//后台接收URL
        //$v_BgRetUrll = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
        //页面接收URL
        //$v_PageRetUrl = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
         //HS方式实时发送通知；AG方式不发送通知；
         $aREQ["notifyType"] = "HS";
         //订单号商户端产生，一天内不能重复,拼接上订单号和支付号。
         $aREQ["orderid"] = 'gysoft'.CI_SN.'-' . $int_ps_id;
         //金额以分为单位
         $aREQ["amount"] = $flt_money * 100;
         //分期付款期限
          $aREQ["installmentTimes"] = '1';
         //币种目前只支持人民币，代码为“001”
         $aREQ["curType"] = "001"; 
         //对于HS方式“0”：发送成功或者失败信息；“1”，只发送交易成功信息。
         $aREQ["resultType"] = '1';
         $aREQ["creditType"] = '2';
         //14位时间戳
         $aREQ["orderDate"] = date("YmdHis");
         //以上五个字段用于客户支付页面显示
         $aREQ["goodsID"] = '';
         //网关只认GB2312
         $aREQ["goodsName"]  = '账号充值';
         $aREQ["goodsNum"] = 1;
         //运费金额以分为单位
         $aREQ["carriageAmt"] = "0";
         //商户reference  商户网站域名
		 $GY_SHOP_HOST = D('SysConfig')->getConfigValueBySckey('GY_SHOP_HOST', 'GY_SHOP');
		 $ARY_GY_SHOP_HOST = explode(".",$GY_SHOP_HOST);
		 if($ARY_GY_SHOP_HOST!=''){
			 $ARY_GY_SHOP_HOST[0]='*';
			 $merReference=implode('.',$ARY_GY_SHOP_HOST);
			 $merReference = substr($merReference,0,strlen($merReference)-1); 
		 }
         $aREQ["merReference"] = $merReference;
         //客户端IP
         //获取客户端ip地址
		$ip = '';
		foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v) {
			if( isset($_SERVER[$v]) ){
				$ip = $_SERVER[$v];
				if(!empty($ip)) break;
			}
		}
         $aREQ["merCustomIp"] = $ip;   
         //虚拟商品/实物商品标识位
         $aREQ["goodsType"] = "";   
         //买家用户号
         $aREQ["merCustomID"] = "";    
         //买家联系电话
         $aREQ["merCustomPhone"] = "";          
         //收货地址
         $aREQ["goodsAddress"] = "";   
         //订单备注
         $aREQ["merOrderRemark"] = "";
         //商城提示
         $aREQ["merHint"] = "";
         //备注
         $aREQ["remark1"] = "";
         //备注2
         $aREQ["remark2"] = "";                      
         //“1”判断该客户是否与商户联名；取值“0”不检验客户是否与商户联名。
         $aREQ["verifyJoinFlag"] = '0';		
		 //构造V3版的xml
		$tranData = "<?xml version=\"1.0\" encoding=\"GBK\" standalone=\"no\"?><B2CReq><interfaceName>".$aREQ["interfaceName"]."</interfaceName><interfaceVersion>".$aREQ["interfaceVersion"]."</interfaceVersion><orderInfo><orderDate>".$aREQ["orderDate"]."</orderDate><curType>".$aREQ["curType"]."</curType><merID>".$aREQ["merID"]."</merID><subOrderInfoList><subOrderInfo><orderid>".$aREQ["orderid"]."</orderid><amount>".$aREQ["amount"]."</amount><installmentTimes>".$aREQ["installmentTimes"]."</installmentTimes><merAcct>".$aREQ["merAcct"]."</merAcct><goodsID>".$aREQ["goodsID"]."</goodsID><goodsName>".$aREQ["goodsName"]."</goodsName><goodsNum>".$aREQ["goodsNum"]."</goodsNum><carriageAmt>".$aREQ["carriageAmt"]."</carriageAmt></subOrderInfo></subOrderInfoList></orderInfo><custom><verifyJoinFlag>".$aREQ["verifyJoinFlag"]."</verifyJoinFlag><Language>ZH_CN</Language></custom><message><creditType>".$aREQ["creditType"]."</creditType><notifyType>".$aREQ["notifyType"]."</notifyType><resultType>".$aREQ["resultType"]."</resultType><merReference>".$aREQ["merReference"]."</merReference><merCustomIp>".$aREQ["merCustomIp"]."</merCustomIp><goodsType>".$aREQ["goodsType"]."</goodsType><merCustomID>".$aREQ["merCustomID"]."</merCustomID><merCustomPhone>".$aREQ["merCustomPhone"]."</merCustomPhone><goodsAddress>".$aREQ["goodsAddress"]."</goodsAddress><merOrderRemark>".$aREQ["merOrderRemark"]."</merOrderRemark><merHint>".$aREQ["merHint"]."</merHint><remark1>".$aREQ["remark1"]."</remark1><remark2>".$aREQ["remark2"]."</remark2><merURL>".$aREQ["merURL"]."</merURL><merVAR>".$aREQ["orderid"]."</merVAR></message></B2CReq>";
		if (strtoupper(substr(PHP_OS,0,3))=="WIN"){
             $bb = new COM("ICBCEBANKUTIL.B2CUtil");
             $rc=$bb->init($icbc,$cert,$key,$keyPass);
             $merSignMsg = $bb->signC($tranData, strlen($tranData));
             //print_r($bb->getRC());
            //print_r($merSignMsg);die();
			 $fp = fopen($cert,"rb");
			 $merCert = fread($fp,filesize($cert));
			 $merCert = base64_encode($merCert);
			 fclose($fp);
			 $tranData_info = base64_encode($tranData);
         }
         else{
			 /**
             //商户签名数据BASE64编码
             $cmd = "/bin/icbc_sign '{$key}' '{$keyPass}' '{$tranData}'";
             //error_log($cmd,3,__FILE__.".log");
             $handle = popen($cmd, 'r');
             $merSignMsg = fread($handle, 2096);
             pclose($handle);
			 **/	
			//读取商户私钥文件
			$keyfile=$key;
			if(strlen($keyfile) <= 0)
			{
				echo "WARNING : no key data input<br/>";
				exit();
			}
			
			$fp = fopen($keyfile,"rb");
			if($fp == NULL)
			{
				echo "open file error<br/>";
				exit();
			}
			
			fseek($fp,0,SEEK_END);
			$filelen=ftell($fp);
			fseek($fp,0,SEEK_SET);
			$contents = fread($fp,$filelen);
			fclose($fp);
			
			$key = substr($contents,2);
			//获取密钥口令
			$pass=$keyPass;
			if(strlen($pass) <= 0)
			{
				echo "WARNING : no key password input<br/>";
				exit();
			}
			//读取商户公钥文件
			$merCert=$cert;
			if(strlen($merCert) <= 0)
			{
				echo "WARNING : no merCert input<br/>";
				exit();
			}
			else
			{
				$fp2 = fopen($merCert,"rb");
				if($fp2 == NULL)
				{
					echo "open file error<br/>";
					exit();
				}
				fseek($fp2,0,SEEK_END);
				$filelen2=ftell($fp2);
				fseek($fp2,0,SEEK_SET);
				$cert = fread($fp2,$filelen2);
				fclose($fp2);
			}
			/*签名*/
			$signature = sign($tranData,$key,$pass);//签名
			$code = current($signature);//获取签名数据
			$len = next($signature);//获取签名数据长度
			$signcode = base64enc($code);//对签名数据BASE64编码
			$merSignMsg = current($signcode);
			$tranDataBase64 = base64enc($tranData);//对表单数据BASE64编码
			$tranData_info = current($tranDataBase64);
			
			$merCertBase64 = base64enc($cert);//对证书BASE64编码
			$merCert = current($merCertBase64);
         }
	     $aFinalReq['interfaceName'] = $aREQ["interfaceName"];
	     $aFinalReq['interfaceVersion'] = $aREQ["interfaceVersion"];
	     $aFinalReq['tranData'] = $tranData_info;
		 $aFinalReq['tranDataBase64'] = $tranDataBase64;
	     $aFinalReq['merSignMsg'] = $merSignMsg;
		 $aFinalReq['merCertBase64'] = $merCertBase64;
	     $aFinalReq['merCert'] = $merCert;		 
        $this->assign('aFinalReq',$aFinalReq);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:ICBC");exit;
    }

    /**
     * 响应网银在线通知
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-26
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
		$params = $data;
		$paymentId=$data['merVAR'];
		$paymentId = explode('-',$paymentId);
        $int_ps_id = $paymentId[1];
        //商户id
        $pay_config = D('Gyfx')->selectOneCache('payment_cfg',$ary_field=null, array('pc_pay_type'=>'icbc'), null);
		$m_key_info = json_decode($pay_config['pc_config'],1);
		//私钥保护密码
        $keyPass = trim($m_key_info['icbc_sykey']);	
        //商城代码
        $merId = trim($m_key_info['icbc_account']);
        //工行帐号
        $icbcno = trim($m_key_info['icbc_zh']);
        $key = $m_key_info['keyFile']['upload_path'];//商户私钥文件 wanrong.key
        $merCert = $m_key_info['certFile']['upload_path'];//商户公钥文件 wanrong.cer
        $icbc = $m_key_info['icbcFile']['upload_path'];//工行公钥 ebb2cpublic.crt
        if(!file_exists($key)){ 
			$this->error('ICBC key file not found!');exit;
        }
         if(!file_exists($merCert)){ 
			$this->error('ICBC Cert file not found!');exit;
        }
		$tmp_notify_data = current(base64dec($params['notifyData']));
		$notifyData = iconv('gb2312', 'utf-8', $tmp_notify_data);		
        if (strtoupper(substr(PHP_OS,0,3))=="WIN"){
            $bb = new COM('ICBCEBANKUTIL.B2CUtil');
            $bb->init($icbc,$merCert,$key,$keyPass);
            $isok = $bb->verifySignC($notifyData,strlen($notifyData),$params['signMsg'],strlen($params['signMsg']));
        }
        else{
			$isok = $this->verifySign($params,$key, $merCert,$keyPass);
        }	
		
	    if ($isok == 0){
            preg_match("/\<amount\>(.*)\<\/amount\>.+\<TranSerialNo\>(.*)\<\/TranSerialNo\>.+\<tranStat\>(.*)\<\/tranStat\>/i",$notifyData,$rnt);
            $money = $rnt[1]/100;
			$v_amount = $money;
            $tradeno = $rnt[2];
            /* 检查支付的金额是否相符 */
			$double_all_price = sprintf('%.2f',D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->getField('ps_money'));
            if($money != $double_all_price){
                //将应支付金额重新转成支付格式，判断2次支付金额是否一致
                return array('result'=>false);
            }
			$int_status = 0;
            if ($rnt[3]==1){
                $message="支付成功！";
				$int_status = 1;
				//根据流水单号返回订单ID
				$o_id = $this->getOidByPsid($int_ps_id);
				$gw_code=date('YmdHis') . rand(10000, 99999);
				if($int_status == '1'){
					if(!empty($int_ps_id)){
						$ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id,'ps_status'=>1))->getField('ps_id');
						if(!empty($ary_paymentSerial)){
							//已经存在相同支付流水号的
							//根据流水单号返回订单ID
							$o_id = $this->getOidByPsid($int_ps_id);					
							return array('result' => true,'o_id'=>$o_id);
						}
					}
				}
				$result = $this->updataPaymentSerial($int_ps_id, $int_status, $rnt[3], $gw_code);

				//根据流水单号返回会员ID
				$m_id = $this->getMemberIdByPsid($int_ps_id);
				//根据流水单号返回订单ID
				$o_id = $this->getOidByPsid($int_ps_id);
				return array(
					'result'     => $result,
					'o_id'       => $o_id,
					'int_status' => $int_status,
					"total_fee"  => $v_amount,
					"gw_code"    => $gw_code,
					'm_id'       => $m_id,
					'int_ps_id'  =>$int_ps_id
				);

                return array('status'=>200,'msg'=>'ok');         
            }
            else{
               // $message = "支付失败！";
               //eturn array('status'=>201,'msg'=>'error,支付失败！');
				return array('result'=>false);
            }
        }
        else{
           //message = "验证签名错误！";
      		//turn array('status'=>201,'msg'=>'error,验证签名错误！');
			return array('result'=>false);
        }	
    }
	
	/**
     * 验证签名
     * 16位
     * @param ps_id
	 * @by wangguibin
	 * @date 2015-10-33 12:30
     */
	private function verifySign($params,$key, $merCert,$keyPass){
		//获取密钥口令
		$key = $this->getKey($key);
		//商户密码
		if(strlen($keyPass) <= 0)
		{
			echo "WARNING : no key password input<br/>";
			exit();
		}		
		//获取订单提交数据解码原文
		$tmp_notify_data = current(base64dec($params['notifyData']));
		$tranData = iconv('gb2312', 'utf-8', $tmp_notify_data);
		if(strlen($tranData) <= 0)
		{
			echo "WARNING : no tranData input<br/>";
			exit();
		}
		$cert = $this->getMerCert($merCert);	
		/*签名*/
		$signature = sign($tranData,$key,$keyPass);//签名
		$code = current($signature);//获取签名数据
		$len = next($signature);//获取签名数据长度
		$signcode = base64enc($code);//对签名数据BASE64编码
		$signMsg = current($signcode);
		$notifyDataBase64 = base64enc($tranData);//对表单数据BASE64编码
		$notifyData = current($notifyDataBase64);
		//$merCertBase64 = base64enc($cert);//对证书BASE64编码
		/*签名*/
		//返回商户变量
		$merVAR=$params['merVAR'];
		if(strlen($merVAR) <= 0)
		{
			echo "WARNING : no merVAR input<br/>";
			exit();
		}
		//获取通知明文
		if(strlen($notifyData) <= 0)
		{
			echo "WARNING : no notifyData input<br/>";
			exit();
		}
		//获取密文
		if(strlen($signMsg) <= 0)
		{
			echo "WARNING : no signMsg input<br/>";
			exit();
		}
		//解码签名数据
		$sign = base64dec($signMsg);
		//解码原文
		$plaint = base64dec($notifyData);
		//验签名
		$isok = verifySign(current($plaint),$cert,current($sign));
		return $isok;	
	}
	private function getKey($key){
		$fp = fopen($key,"rb");
		if($fp == NULL)
		{
			echo "open file error<br/>";
			exit();
		}
		fseek($fp,0,SEEK_END);
		$filelen=ftell($fp);
		fseek($fp,0,SEEK_SET);
		$contents = fread($fp,$filelen);
		fclose($fp);
		$key = substr($contents,2);
		return $key;
	}
	private function getMerCert($merCert){
		//读取商户公钥文件
		if(strlen($merCert) <= 0)
		{
			echo "WARNING : no merCert input<br/>";
			exit();
		}
		else
		{
			$fp2 = fopen($merCert,"rb");
			if($fp2 == NULL)
			{
				echo "open file error<br/>";
				exit();
			}
			fseek($fp2,0,SEEK_END);
			$filelen2=ftell($fp2);
			fseek($fp2,0,SEEK_SET);
			$cert = fread($fp2,$filelen2);
			fclose($fp2);
		}
		return $cert;
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