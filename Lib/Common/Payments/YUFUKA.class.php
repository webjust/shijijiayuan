<?php
/**
 * 友好宝在线支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.8
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-11-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class YUFUKA extends Payments implements IPayments {

    /**
     * 设置支付方式的配置信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-11-13
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['app_account'] = $param['app_account'];
        $config['appId'] = $param['appId'];
		$config['appKey'] = $param['appKey'];
		$config['sign_type']='HmacMD5';
		$config['https_verify_url'] = 'https://pay.astpay.com/gateway.do?service=notify_verify&';
		$config['http_verify_url'] = 'http://notify.alipay.com/trade/notify_query.do?';
        $this->config = $config;
    }

    /**
     * 支付订单
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-11-13
     * @param string $str_oid 订单编号
     * @param type $ary_param 订单参数
     */
    public function pay($str_oid,$type=0,$o_pay=0.000,$pay_type=0) {
        $ary_order = parent::pay($str_oid);

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
		//必填，不能修改
		//服务器异步通知页面路径
		$notify_url = U('Home/User/synPayNotify?code=' . $this->code, '', true, false, true);
		//需http://格式的完整路径，不能加?id=123这类自定义参数
		//页面跳转同步通知页面路径
		$return_url = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
		//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
		//卖家友好宝帐户
		//$member_info = D('Gyfx')->selectOneCache('members',"m_name", array('m_id' => $ary_order['m_id']));
		//$m_name = $_SESSION['Members']['m_name'];
		$mall_email = $this->config['app_account'];
		//必填
		//商户订单号
		//$order_no = $ary_order['o_id'];
		$order_no = 'GY' . $int_ps_id;
		//商户网站订单系统中唯一订单号，必填
		//订单名称
		//$subject = "订单编号:".$ary_order['o_id'];
		$subject = $ary_order['o_id'];
		//必填
		//付款金额
		$amount = sprintf('%.2f', $ary_order['o_all_price']);
		//必填
		//订单描述
		$body = '付款中';
		//默认支付方式
		$paymethod = "precardPay";
	
		//把请求参数打包成数组
		$sParaTemp["service"]="payorder";
		$sParaTemp["version"]="B2C1.0";
		$sParaTemp["partner"]=$this->config['appId'];
		$sParaTemp["notify_url"]=$notify_url;
		$sParaTemp["return_url"]=$return_url;
		$sParaTemp["mall_email"]=$mall_email;
		$sParaTemp["orderno"]=$order_no;
		$sParaTemp["ordertime"]=date('YmdHis');
		$sParaTemp["subject"]=$subject;
		$sParaTemp["amount"]=$amount;
		$sParaTemp["paymethod"]=$paymethod;		
		//建立请求
		$params = $this->buildRequestPara($sParaTemp, $this->config['appKey']); //构建好提交参数主要是增加sign_type和sign       
		$this->assign('params',$params);
		//dump($params);die();
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:YOUHAOPAY");
        exit;
    }

    /**
     * 预存款充值
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-11-13
     * @param float $flt_money 要充值的金额
     */
    public function charge($flt_money) {
        //生成支付序列号 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_ps_id = $this->addPaymentSerial(1, array('o_all_price' => $flt_money, 'o_id' => date('YmdHis')));
		//必填，不能修改
		//服务器异步通知页面路径
		$notify_url = U('Home/User/synChargeNotify?code=' . $this->code, '', true, false, true);//充值异步通知地址
		//需http://格式的完整路径，不能加?id=123这类自定义参数
		//页面跳转同步通知页面路径
		$return_url = U('Ucenter/Payment/synChargeReturn?code=' . $this->code, '', true, false, true);
		//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
		//卖家友好宝帐户
		//$m_name = $_SESSION['Members']['m_name'];
		//$member_info = D('Gyfx')->selectOneCache('members',"m_name", array('m_id' => $m_id));
		$mall_email = $this->config['app_account'];
		//必填
		//商户订单号
		$order_no = 'GY' . $int_ps_id;
		//商户网站订单系统中唯一订单号，必填
		//订单名称
		$subject = "帐号充值:".$int_ps_id;
		//必填
		//付款金额
		$amount = sprintf('%.2f', $flt_money);
		//必填
		//订单描述
		$body = '付款中';
		//默认支付方式
		$paymethod = "precardPay";
	
		//把请求参数打包成数组
		$sParaTemp["service"]="payorder";
		$sParaTemp["version"]="B2C1.0";
		$sParaTemp["partner"]=$this->config['appId'];
		$sParaTemp["notify_url"]=$notify_url;
		$sParaTemp["return_url"]=$return_url;
		$sParaTemp["mall_email"]=$mall_email;
		$sParaTemp["orderno"]=$order_no;
		$sParaTemp["ordertime"]=date('YmdHis');
		$sParaTemp["subject"]=$subject;
		$sParaTemp["amount"]=$amount;
		$sParaTemp["paymethod"]=$paymethod;		
		//建立请求
		$params = $this->buildRequestPara($sParaTemp, $this->config['appKey']); //构建好提交参数主要是增加sign_type和sign       
		$this->assign($params);
        //订单预处理有事务此处要提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->display("Ucenter:Pay:YOUHAOPAY");
        exit;
    }

    /**
     * 响应友好宝通知
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-11-13
     * @param array $data 从服务器端返回的数据
     * @return array 返回订单号和支付状态
     */
    public function respond($data) {
		if(empty($data)) {//判断POST来的数组是否为空
			return array('result' => false);
		}
		else {
			//生成签名结果
			$verify_result  =  $this->getSignVerify($data, $data["sign"]);
		}	
		if($verify_result) {//验证成功
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//请在这里加上商户的业务逻辑程序代码
		
		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
	
		//商户订单号
	
        $v_oid = $data['orderno'];
		
        //自定义的流水号 GY+ps_id
        $int_ps_id = ltrim($v_oid, 'GY');
        //外部网关流水号
        $v_pstatus = $data['trade_no'];
        $v_amount = $data['amount'];
		//交易状态
		$trade_status = $data['trade_status'];
	    if($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS') {
			//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
			$int_status = 1;
			//更改第三方流水单状态
			//友好宝无第三方网关的流水号
			//系统自动生成一个流水号
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
			$result = $this->updataPaymentSerial($int_ps_id, $int_status, $v_pstatus, $gw_code);

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
		}
	    else {
		  $int_status = 0;
	      echo "trade_status=".$_REQUEST['trade_status'];
		  return array('result' => false);
	    }
	
		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
		
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		else {
			//验证失败
			//如要调试，请看alipay_notify.php页面的verifyReturn函数
			$int_status = 0;
			echo "验证失败";
			return array('result' => false);
		}		
    }
	/**
	 * 基于md5的加密算法hmac
	 * @param String $data 预加密数据
	 * @param String $key  密钥
	 * @return String 
	 */
	function hmac($data, $key){
	    if (function_exists('hash_hmac')) {
	        return hash_hmac('md5', $data, $key);
	    }
	
	    $key = (strlen($key) > 64) ? pack('H32', 'md5') : str_pad($key, 64, chr(0));
	    $ipad = substr($key,0, 64) ^ str_repeat(chr(0x36), 64);
	    $opad = substr($key,0, 64) ^ str_repeat(chr(0x5C), 64);
	    return md5($opad.pack('H32', md5($ipad.$data)));
	}
	/**
	 * 签名字符串
	 * @param $prestr 需要签名的字符串
	 * @param $key 私钥
	 * return 签名结果
	 */
	function hmacmd5Sign($prestr, $key) {
		return $this->hmac($prestr,$key);
	}
	
	/**
	 * 验证签名
	 * @param $prestr 需要签名的字符串
	 * @param $sign 签名结果
	 * @param $key 私钥
	 * return 签名结果
	 */
	function hmacmd5Verify($prestr, $sign, $key) {
		$mysgin = $this->hmac($prestr,$key);
	
		if($mysgin == $sign) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	function buildRequestMysign($para_sort) {
		$pay_config = $this->config;
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		
		$mysign = "";
		switch (trim($pay_config['sign_type'])) {
			case "HmacMD5" :
				$mysign = $this->hmacmd5Sign($prestr, $pay_config['appKey']);
				break;
			default :
				$mysign = "";
		}
		
		return $mysign;
	}

	/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp) {
		$pay_config = $this->config;
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = trim($pay_config['sign_type']);
		
		return $para_sort;
	}
	
	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		
		return $arg;
	}
	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	function createLinkstringUrlencode($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".urlencode($val)."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		
		return $arg;
	}
	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}
	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}
	/**
	 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
	 * 注意：服务器需要开通fopen配置
	 * @param $word 要写入日志里的文本内容 默认值：空值
	 */
	function logResult($word='') {
		$fp = fopen("log.txt","a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	
	/**
	 * 远程获取数据，POST模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * @param $para 请求的数据
	 * @param $input_charset 编码格式。默认值：空值
	 * return 远程输出的数据
	 */
	function httpPost($url, $cacert_url, $para, $input_charset = '') {
	
		if (trim($input_charset) != '') {
			$url = $url."_input_charset=".$input_charset;
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_POST,true); // post传输数据
		curl_setopt($curl, CURLOPT_POSTFIELDS,$para);// post传输数据
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);
		
		return $responseText;
	}
	
	/**
	 * 远程获取数据，GET模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * return 远程输出的数据
	 */
	function httpGet($url,$cacert_url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);
		
		return $responseText;
	}
	
	/**
	 * 实现多种字符编码方式
	 * @param $input 需要编码的字符串
	 * @param $_output_charset 输出的编码格式
	 * @param $_input_charset 输入的编码格式
	 * return 编码后的字符串
	 */
	function charsetEncode($input,$_output_charset ,$_input_charset) {
		$output = "";
		if(!isset($_output_charset) )$_output_charset  = $_input_charset;
		if($_input_charset == $_output_charset || $input ==null ) {
			$output = $input;
		} elseif (function_exists("mb_convert_encoding")) {
			$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
		} elseif(function_exists("iconv")) {
			$output = iconv($_input_charset,$_output_charset,$input);
		} else die("sorry, you have no libs support for charset change.");
		return $output;
	}
	/**
	 * 实现多种字符解码方式
	 * @param $input 需要解码的字符串
	 * @param $_output_charset 输出的解码格式
	 * @param $_input_charset 输入的解码格式
	 * return 解码后的字符串
	 */
	function charsetDecode($input,$_input_charset ,$_output_charset) {
		$output = "";
		if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
		if($_input_charset == $_output_charset || $input ==null ) {
			$output = $input;
		} elseif (function_exists("mb_convert_encoding")) {
			$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
		} elseif(function_exists("iconv")) {
			$output = iconv($_input_charset,$_output_charset,$input);
		} else die("sorry, you have no libs support for charset changes.");
		return $output;
	}
	
	/**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			return $this->getSignVerify($_POST, $_POST["sign"]);
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			return $this->getSignVerify($_GET, $_GET["sign"]);
		}
	}
	
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
	function getSignVerify($para_temp, $sign) {
		$pay_config = $this->config;
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);
		
		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		
		$isSgin = false;
		switch (trim($pay_config['sign_type'])) {
			case "HmacMD5" :
				$isSgin = $this->hmacmd5Verify($prestr, $sign, $pay_config['appKey']);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$pay_config = $this->config;
		$transport = strtolower(trim($pay_config['transport']));
		$partner = trim($pay_config['appId']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $https_verify_url;
		}
		else {
			$veryfy_url = $http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = $this->httpGet($veryfy_url, $pay_config['cacert']);
		
		return $responseTxt;
	}
}