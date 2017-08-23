<?php
/**
 * SAAS 化API统一验证类
 */
class ApiAction extends GyfxAction{

	protected $format;
	protected $ci_sn;

	/**
	 * 构造函数，用于验证签名，验证必填参数合法性等
	 *
	 */
	public function __construct(){
		parent::__construct();
		$array_params = $_REQUEST;
//        writeLog('Erp request: '.var_export($array_params, true), 'erpapi.log');
		//可选，指定响应格式。默认xml,目前支持格式为xml,json
		if($array_params["format"] !='json' || "" == $array_params["format"]){
			$this->format = 'xml';
		}else{
			$this->format = 'json';
		}

		//验证是否传入app_key 参数
		if(!isset($array_params["app_key"]) || "" == $array_params["app_key"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数app_key');
		}

		//验证app_secret 合法性
		$str_app_key = $array_params["app_key"];
		//		$array_authz = M("client_info")->field('ci_app_secret')->where(array("ci_sn"=>$str_app_key))->find();
		//		if(!is_array($array_authz) || empty($array_authz)){
		//			$this->errorResult(false,10001,array(),'错误的app_key');
		//		}else{
		//			$app_secret = $array_authz['ci_app_secret'];
		//			$this->ci_sn = $str_app_key;
		//			$str_db_info = 'mysql://'.C('DB_USER').':'.C('DB_PWD').'@'.C('DB_HOST').'/' . $str_app_key;
		//			C('DB_CUSTOM', $str_db_info);
		//			$sys_obj = M('sys_config',C('FX_PREFIX'),'DB_CUSTOM');
		//			$host = $sys_obj->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
		//			$_SESSION['HOST_URL'] = $host['sc_value'];
		//		}
		//是否启用SAAS化环境
		if(SAAS_ON == TRUE){
			//API放在本地调用
			$array_center_config = explode('/',ltrim(C("DB_CENTER"),'mysql://'));
			$array_hostinfo = explode("@",$array_center_config[0]);
			$array_host_info = explode(":",$array_hostinfo[1]);
			$array_userinfo = explode(":",$array_hostinfo[0]);
			$array_userinfo[1] = (!isset($array_userinfo[1]))?"":$array_userinfo[1];
			$string_conn = "mysql:host=" . $array_host_info[0] . ";dbname=" . $array_center_config[1];
			if(3306 != $array_host_info[1]){
				$string_conn .= ";port=" . $array_host_info[1];
			}
			try {
				$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);
				$pdo_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$pdo_conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);//Display exception
			} catch (PDOExceptsddttrtion $e) {//return PDOException
				//连接管易数据中心失败
				$this->errorResult(false,10001,array(),'验证管易分销授权中心数据库失败。');
			}
			$obj_stmt = $pdo_conn->prepare("select ci_app_secret from `gy_client_info` where `ci_sn`=? limit 1");
			$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
			if(!$obj_stmt->execute(array($str_app_key))){
				die("无法获取此域名的用户授权信息。");
			}
			$array_authz =  $obj_stmt->fetch();
		}else{
			if($str_app_key != CI_SN){
				$this->errorResult(false,10001,array(),'错误的app_key');
			}
			$array_authz = array('ci_app_secret'=>APP_SECRET);
		}

		if(!is_array($array_authz) || empty($array_authz)){
			$this->errorResult(false,10001,array(),'错误的app_key');
		}else{
			$app_secret = $array_authz['ci_app_secret'];
			$this->ci_sn = $str_app_key;
			//$str_db_info = 'mysql://'.C('DB_USER').':'.C('DB_PWD').'@'.C('DB_HOST').'/' . $str_app_key;
			//C('DB_CUSTOM', $str_db_info);
			$sys_obj = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM');
			$host = $sys_obj->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
			$_SESSION['HOST_URL'] = $host['sc_value'];
		}

		//验证是否传递timestamp参数
		if(!isset($array_params["timestamp"]) || "" == $array_params["timestamp"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数timestamp');
		}

		//验证是否传递sign参数
		if(!isset($array_params["sign"]) || "" == $array_params["sign"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数sign');
		}

		//验证是否传入app_secret参数 放在签名里验证
		//生成签名
		//签名时，根据参数名称，将除签名（sign）和图片外所有请求参数按照字母先后顺序排序:key + value .... key + value
		$paramArr = $array_params;
		unset($paramArr['_URL_']);
		unset($paramArr['sign']);
		//dump($paramArr);die();
		$sign = $this->createSign($paramArr,$array_authz['ci_app_secret']);
		if($sign != $array_params['sign']){
			$this->errorResult(false,10001,array(),'数据签名不正确');
		}
		//管易API服务端允许客户端请求时间误差为10分钟(授权码10分钟有效期)。
		//使用（当前系统时间 - 用户提交请求的时间）的绝对值小于等于300秒的为有效请求
		$now_date = time();
		$data = strtotime($array_params["timestamp"]);
		if(abs($now_date-$data) > 300){
			$this->errorResult(false,10001,array(),'接口服务器允许的时间差在10分钟之内。');
		}

		}

		/**
		 * 获取实际方法名
		 */
		protected function getRealMethodName($str_mathod=""){
			//按照点分割字符串
			$array_method_name = explode(".",$str_mathod);
            //方法命名第一个单词全部小写
			$str_method_name = strtolower($array_method_name[0]);
			//第二个单词首字母大写
			$str_method_name .= ucfirst($array_method_name[1]);
			//第三个单词首字母大写
			$str_method_name .= ucfirst($array_method_name[2]);
			//第四个单词首字母大写
			$str_method_name .= ucfirst($array_method_name[3]);
			//第五个单词首字母大写
			if($array_method_name[4]){
				$str_method_name .= ucfirst($array_method_name[4]);
			}
			return $str_method_name;
		}

	/**
	 * 产生json字符串
	 * @param bool $status
	 * @param string $error_code
	 * @param array $ary_data
	 * @param string $str_msg
	 * @param array $options
	 */
		protected function result($status=false,$error_code="",$ary_data = array(),$str_msg="",$options=array()){
			if($this->format == 'json'){
				$response = array();
				if($options['root_tag']){
					$response[$options['root_tag']] = $ary_data;
				}else{
					$response = $ary_data;
				}
				echo json_encode($response);
				exit;
			}else{
				$xmlData = toXml($ary_data,$options);
				echo $xmlData;
				exit;
			}
		}

		/**
		 * 错误信息字符串
		 * 产生xml字符串
		 * return xml
		 * <?xml version="1.0" encoding="utf-8" ?>
		 * <error_response>
		 * <code>15</code>
		 * <msg>Remote service error</msg>
		 * <sub_code>isv.logistics-update-company-or-mailno-error:P08</sub_code>
		 * <sub_msg>该订单不支持修改</sub_msg>
		 * </error_response>
		 *
		 */
		protected function errorResult($status=false,$error_code="",$ary_data = array(),$str_msg="",$msg='Remote service error'){
			$array_data = array(
			'code'=>$error_code,
			'msg'=>$msg,
			'sub_code'=>$ary_data,
			'sub_msg'=>$str_msg
			);
			if($this->format == 'json'){
				$response = array();
				$response['error_response'] = $array_data;
				unset($array_data);
				echo json_encode($response);
//                writeLog('Fx response error: '.json_encode($response), 'erpapi.log');
				exit;
			}else{
				$xmlData = toXml($array_data);
//                writeLog('Fx response error: '.$xmlData, 'erpapi.log');
				echo $xmlData;
				exit;
			}
		}

		/**
		 *
		 * Enter 签名函数
		 * @param  $paramArr
		 */

		protected function createSign ($paramArr,$app_secret) {

			$sign = $app_secret;

			ksort($paramArr);

			foreach ($paramArr as $key => $val) {

				if ($key != '' && $val != '') {

					$sign .= $key.$val;

				}

			}

			$sign.=$app_secret;
			//dump($sign);die();
			$sign = strtoupper(md5($sign));

			return $sign;

		}

		//
		/**
		*
		* 组参函数
		* @param $paramArr
		*/
		protected function createStrParam ($paramArr) {

			$strParam = '';

			foreach ($paramArr as $key => $val) {

				if ($key != '' && $val != '') {

					$strParam .= $key.'='.urlencode($val).'&';

				}

			}

			return $strParam;

		}

}
