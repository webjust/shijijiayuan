<?php

/**
 * 公共函数库
 */

/**
 * 简单加密解密方法，支持有效期
 * @param string $string 原文或者密文
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的原文，或者经过 base64_encode 处理后的密文
 *
 * @example
 *
 * $a = authcode('abc', 'ENCODE', 'key');
 * $b = authcode($a, 'DECODE', 'key'); // $b(abc)
 *
 * $a = authcode('abc', 'ENCODE', 'key', 3600);
 * $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600) {
	$ckey_length = 4;
	// 随机密钥长度 取值 0-32;
	// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
	// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
	// 当此值为 0 时，则不产生随机密钥
	$key = md5($key ? $key : 'guanyisoft');
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for ($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for ($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for ($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if ($operation == 'DECODE') {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace('=', '', base64_encode($result));
	}
}

/**
 * 生成随机字符串，由小写英文和数字组成。去掉了容易混淆的0o1l之类
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-12
 * @param int $int 生成的随机字串长度
 * @param boolean $caps 大小写，默认返回小写组合。true为大写，false为小写
 * @return string 返回生成好的随机字串xml
 */
function randStr($int = 6, $caps = false) {
	/*$strings = 'abcdefghjkmnpqrstuvwxyz23456789';
	$return = '';
	for ($i = 0; $i < $int; $i++) {
		srand();
		$rnd = mt_rand(0, 30);
		$return = $return . $strings[$rnd];
	}
	return $caps ? srttoupper($return) : $return;*/
	$strings = '0123456789';
    $return = '';
    for ($i = 0; $i < $int; $i++) {
        srand();
        $rnd = mt_rand(0, 9);
        $return = $return . $strings[$rnd];
    }
    return $return;
}
function make_fsockopen($request_method,$ary_request=array()){
	if($request_method){
		$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
		$host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
		request_by_fsockopen($host_url.$request_method,$ary_request);	
	}
}
/**
 * PHP发送异步请求
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 * @param string $url 请求地址
 * @param array $param 请求参数
 * @param string $httpMethod 请求方法GET或者POST
 * @return boolean
 * @link http://www.thinkphp.cn/code/71.html
 */
function makeRequest($url, $param, $httpMethod = 'GET') {
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	if ($httpMethod == 'GET') {
		curl_setopt($oCurl, CURLOPT_URL, $url . "?" . http_build_query($param));
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	} else {
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, http_build_query($param));
	}
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);
	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return FALSE;
	}
}

/**
 * PHP发送异步请求
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 * @param string $url 请求地址
 * @param array $param 请求参数
 * @param string $httpMethod 请求方法GET或者POST
 * @return boolean
 * @link http://www.thinkphp.cn/code/71.html
 */
function makeRequestUtf8($url, $param, $httpMethod = 'GET') {
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	if ($httpMethod == 'GET') {
		curl_setopt($oCurl, CURLOPT_URL, $url . "?" . http_build_query($param));
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	} else {
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, http_build_query($param));
	}
	$this_header = array("content-type:application/x-www-form-urlencoded;charset=UTF-8");
	curl_setopt($oCurl,CURLOPT_HTTPHEADER,$this_header);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	//echo '<pre>';print_r($sContent);die();
	//echo '<pre>';print_r($aStatus);die();
	curl_close($oCurl);
	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return FALSE;
	}
}

/**
 * PHP发送异步请求
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2015-07-15
 * @param string $url 请求地址
 * @param array $param 请求参数
 * @param string $httpMethod 请求方法GET或者POST
 * @return boolean
 */
function makeRequestJson($url, $param, $httpMethod = 'GET') {
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	if ($httpMethod == 'GET') {
		curl_setopt($oCurl, CURLOPT_URL, $url . "?" . http_build_query($param));
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	} else {
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		//curl_setopt($oCurl, CURLOPT_POSTFIELDS, http_build_query($param));
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, urlencode($param));  
	}
	$this_header = array("content-type:application/json;charset=UTF-8");
	curl_setopt($oCurl,CURLOPT_HTTPHEADER,$this_header);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);
	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return FALSE;
	}
}


function array2object($array) {  
   
    if (is_array($array)) {  
        $obj = new StdClass();  
   
        foreach ($array as $key => $val){  
            $obj->$key = $val;  
        }  
    }  
    else { $obj = $array; }  
   
    return $obj;  
}  
   
function object2array($object) {  
    if (is_object($object)) {  
        foreach ($object as $key => $value) {  
            $array[$key] = $value;  
        }  
    }  
    else {  
        $array = $object;  
    }  
    return $array;  
}  

/**
 * 转换XML文档为数组
 *
 * @author Luis Pater
 * @date 2011-09-06
 * @param string xml内容
 * @return mixed 返回的数组，如果失败，返回false
 */
function xml2array($xml) {
    if(is_file($xml)){
        $xml = simplexml_load_file($xml);
    }else{
        $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
    }
	return simplexml2array($xml);
}

/**
 * 转换XML文档为数组（辅助方法）
 *
 * @author Luis Pater
 * @date 2011-09-06
 * @param string xml内容
 * @return mixed 返回的数组，如果失败，返回false
 */
function simplexml2array($a) {
	if (is_object($a)) {
		settype($a, "array");
	}
	foreach ($a as $k => $v) {
		if ((count($a) == 1) && ($k == "_item_")) {
			if (is_array($v)) {
				$a = simplexml2array($v);
			} elseif (is_object($v)) {
				$a = simplexml2array($v);
			} else {
				$a = array($v);
			}
		} else {
			if (is_array($v)) {
				if (count($v)) {
					$a[$k] = simplexml2array($v);
				} else {
					$a[$k] = "";
				}
			} elseif (is_object($v)) {
				if (count($v)) {
					$a[$k] = simplexml2array($v);
				} else {
					$a[$k] = "";
				}
			}
		}
	}
	return $a;
}

/**
 * 转换数组文档为XML（辅助方法）
 *
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-05-30
 * @param string array内容
 * @return mixed 返回的XML，如果失败，返回false
 */
function toXml($arr, $options=array())
{
	if ( empty($arr) || !is_array($arr) ) {
		return;
	}
	$xml = '';
	//默认如果没有传tag头，返回error_response作为xml的头
	if ( !isset($options['root_tag']) ) {
		$options['root_tag'] = 'error_response';
	}
	if ( !isset($options['decl']) ) {
		$options['decl'] = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
	}
	$xml .= $options['decl'];
	if ( isset($options['indent']) ) {
		$options['indent'] = str_repeat(' ', $options['indent']);
	} else {
		$options['indent'] = null;
	}
	return $xml .valueToXml($options['root_tag'], $arr, 0, $options['indent']);
}

/**
 * 转换数组文档为XML（辅助方法）
 *
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-05-30
 * @param string array内容
 * @return mixed 返回的XML
 */
function valueToXml($tag, $value, $level, $indent)
{
	$str = '';
	$indent_str0 = $indent ? "\n".str_repeat($indent, $level) : '';
	$indent_str = $indent ? "\n".str_repeat($indent, $level+1) : '';
	$str .= '<' . $tag;
	if ( is_array($value) ) {
		if ( isset($value['@attributes']) ) {
			foreach ( $value['@attributes'] as $name => $val ) {
				$str .= ' '. $name. '="'. htmlentities($val, ENT_COMPAT, 'UTF-8'). '"';
			}
			unset($value['@attributes']);
		}
		$str .= '>';
		if ( $value ) {
			$children = array();
			foreach ( $value as $ctag => $cval ) {
				if ( is_array($cval) && isset($cval[0]) ) {
					foreach ( $cval as $v ) {
						$children[] = valueToXml($ctag, $v, $level+1, $indent);
					}
				} else {
					$children[] = valueToXml($ctag, $cval, $level+1, $indent);
				}
			}
			$str .= $indent_str . implode( $indent_str, $children ) . $indent_str0;
		}
	} else {
		$res = htmlentities($value, ENT_COMPAT, 'UTF-8');
		$res = str_replace('&times;','×',$res);
		$str .= '>' . $res;
	}
	$str .= '</'. $tag . '>';
	return $str;
}

/**
 * 讲数组键值转换为大写,或者全部小写
 * @author Jerry
 * @param array 要转化的数组
 * @param string 判断是转化为大写还是小写，参考array_change_key_case
 * @date 2013-1-5
 */
function array_keys_unified($ary_data, $str_case = CASE_UPPER) {
	if (is_array($ary_data) && !empty($ary_data)) {
		$ary_data = array_change_key_case($ary_data, $str_case);
		foreach ($ary_data as &$ary_v) {
			if (is_array($ary_v) && !empty($ary_v)) {
				$ary_v = array_keys_unified($ary_v, $str_case);
			}
		}
	}
	return $ary_data;
}

/**
 * 截取固定长度字符串，超过部分以...代替，兼容中文
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-25
 * @param sting $str 原始字符串
 * @param int $len 待截取的长度
 * @param sting $default 超出部分的显示符
 * @param string $code 字符编码，默认为uft-8
 * @return string 返回截取后的字符串
 */
function substrs($str, $len, $default = '...', $code = 'utf-8') {
	if (mb_strlen($str, $code) <= $len) {
		return $str;
	} else {
		return mb_substr($str, 0, $len, $code) . $default;
	}
}

/**
 * 判断图片(缩略)文件是否存在，如果存在则返回图片地址，否则返回php请求
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-03-18
 * @param string $url 图片原始地址
 * @param int $w 图片缩略图宽度
 * @param int $h 图片缩略图高度
 * @return string 返回图片缩略图地址
 */
function showImage($url, $w = 0, $h = 0) {
	//判断是否开启OSS，及图片提示是否是aliyuncs.com来源
	if(!isset($_SESSION['OSS']['GY_OSS_CNAME_URL'])){$_SESSION['OSS']['GY_OSS_CNAME_URL'] = '';}
	if(strpos($url,"aliyuncs.com")>=1 || !empty($_SESSION['OSS']['GY_OSS_CNAME_URL']) && (strpos($url,$_SESSION['OSS']['GY_OSS_CNAME_URL'])>=1)){
		$oss_info = D('Gyfx')->selectOne('oss_pic','pic_url',array('pic_oss_url'=>$url));
		if(!empty($oss_info['pic_url'])){
			$url = $oss_info['pic_url'];
			$url = str_replace("../../../","/Public/",$url);
		}else{
			if(strpos($url,"aliyuncs.com")>=1){
				$tmp_url = explode('aliyuncs.com/',$url);
				$tmp_url = explode('?',$tmp_url[1]);
				$url = '/Public/Uploads/'.$tmp_url[0];
			}
			if(strpos($url,$_SESSION['OSS']['GY_OSS_CNAME_URL'])>=1 && !empty($_SESSION['OSS']['GY_OSS_CNAME_URL'])){
				$tmp_url = explode($_SESSION['OSS']['GY_OSS_CNAME_URL'],$url);
				$tmp_url = explode('?',$tmp_url[1]);
				$url = '/Public/Uploads/'.$tmp_url[0];		
			}
		}		
	}
	//判断是否启用七牛缩略图
	if($_SESSION['OSS']['GY_QN_ON'] == 1){
		//缩略图不加水印
		if(empty($w)){
			if(!empty($water['THUMB_PIC_WIDTH'])){
				//$w=$water['THUMB_PIC_WIDTH'];//后台设定宽度
			}		
		}
		if(empty($h)){
			if(!empty($water['THUMB_PIC_HEIGHT'])){
				//$h=$water['THUMB_PIC_HEIGHT'];//后台设定宽度
			}		
		}		
		//生成缩略图可以了，暂时隐藏掉好了
		$return_url = D('QnPic')->picToQn($url, $w, $h);
		if(!empty($return_url)){
			return $return_url;
		}
	}
	$path = $url;
	$info = pathinfo($path);
    //读取图片信息
	$water = D('SysConfig')->getCfgByModule('GY_IMAGEWATER',C('MEMCACHE_STAT'));
	if ($w == 0 && $h == 0) {
		//不带宽高的原图需要增加水印
		$path_water = $info['dirname'] . '/_water/' . $info['filename'] . '.' . $info['extension'];
		
		if (!$water['WATER_ON']) {
			//关闭直接返回原地址
			return $url;
		}

		if (file_exists(APP_PATH . $path_water) && $water['WATER_TYPE'] == 'image') {
			//如果水印缓存已经存在直接返回
			$return = $path_water;
			return $return;
		} else {
			//否则生成带水印的缓存图片
			$code = base64_encode(urlencode(gzcompress($url)));
			$url_generated = U('Home/Images/showWater', array('u' => $code));
			return $url_generated;
		}
	} else {
		//缩略图不加水印
		if(empty($w)){
			if(!empty($water['THUMB_PIC_WIDTH'])){
				$w=$water['THUMB_PIC_WIDTH'];//后台设定宽度
			}		
		}
		if(empty($h)){
			if(!empty($water['THUMB_PIC_HEIGHT'])){
				$h=$water['THUMB_PIC_HEIGHT'];//后台设定宽度
			}		
		}
		$path_exists = $info['dirname'] . '/_thumb/' . $info['filename'] . '_' . $w . '_' . $h . '.' . $info['extension'];
		$path_exists = str_replace("../../../","/Public/",$path_exists);
		if (file_exists(APP_PATH . $path_exists)) {
			$return = $path_exists;
			//是否开OSS
	    	if($_SESSION['OSS']['GY_OSS_ON'] == '1'){
	        	$oss_url = D('Gyfx')->selectOne('oss_pic','pic_oss_url',array('pic_url'=>$path_exists));
	            //是否开启自动上传
	            if($_SESSION['OSS']['GY_OSS_AUTO_ON'] == '1'){
	              if(empty($oss_url['pic_oss_url'])){
	            	$oss_obj = new Oss();
		     		$bucket = $_SESSION['OSS']['GY_OSS_BUCKET_NAME'];
		     		$file_paths = explode('Uploads/',$path_exists); 
		     		$bucket = $_SESSION['OSS']['GY_OSS_BUCKET_NAME'];
		     		$object = $file_paths[1];
		     		$file_path = APP_PATH . $path_exists;
					$response = $oss_obj->is_object_exist($bucket,$object);
					//上传图片到服务器
					if($response['status'] != '200'){
						$response1 = $oss_obj->upload_by_file($bucket,$object,$file_path);
						if($response1['status'] != '200'){
							$response_url = $oss_obj->get_sign_url($bucket,$object,$timeout=3600*24*365*10);
						}
					}else{
						$response_url = $oss_obj->get_sign_url($bucket,$object,$timeout=3600*24*365*10);
					}	
					if(!empty($_SESSION['OSS']['GY_OSS_CNAME_URL'])){
						$response_url = explode('aliyuncs.com/',$response_url);
						$response_url = str_replace($response_url[0].'aliyuncs.com/',$_SESSION['OSS']['GY_OSS_CNAME_URL'],implode('aliyuncs.com/',$response_url));
					}
					D('Gyfx')->insert('oss_pic',array('pic_url'=>$path_exists,'pic_oss_url'=>$response_url),1);
					$return = $response_url;
	              }else{
	              	$return = $oss_url['pic_oss_url'];
	              }                  	
	            }else{
	            	if(!empty($oss_url['pic_oss_url'])){
	            		$return = $oss_url['pic_oss_url'];
	            	}
	            }
	    	}
			return $return;
		} else {
			//如果是png格式自动转换
			if(strpos($url,'.png')>0 || strpos($url,'.PNG')>0){
				return $url;
			}else{
				$code = base64_encode(urlencode(gzcompress($url)));
				$url_generated = U('Image/Images/showImage', array('u' => $code, 'w' => $w, 'h' => $h));
				return $url_generated;
				//return $url;				
			}
		}
	}
}

/**
 * 将日期频率的字符串转换成秒数
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-03-21
 * @param string $str_day 日期，可选项为：daily，weekly，monthly，yearly，never，forever
 * @param int $default 默认频率秒数
 * @return int 返回该频率字符串转换后的秒数
 */
function Days2Seconds($str_day, $default = 86400) {
	switch ($str_day) {
		case 'daily':
			$int_time = 86400;
			break;
		case 'weekly':
			$int_time = 86400 * 7;
			break;
		case 'monthly':
			$int_time = 86400 * 30;
			break;
		case 'yearly':
			$int_time = 86400 * 365;
			break;
		case 'never':
			$int_time = 1;
			break;
		case 'forever':
			$int_time = 86400 * 365 * 9999;
			break;
		default:
			$int_time = $default;
			break;
	}

	return $int_time;
}

/**
 * 连接两个字符串，主要用于页面模版的管道命令
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-03-26
 * @param string $str1 待连接的字符串
 * @param string $str2 待连接的字符串
 * @param boolean $after true代表第二个在第一个之后，反之在前
 * @return string 返回连接好的字符串
 */
function strjoin($str1, $str2, $after = true) {
	if ($after) {
		return $str1 . $str2;
	} else {
		return $str2 . $str1;
	}
}

/**
 * HTML标签检查关闭
 *
 * @author Mithern <sunguangxu@uanyisoft.com>
 * @date 2013-07-15
 * @param string HTML原文
 * @return string 返回状态
 */
function closeTags($html) {
	// 不需要补全的标签
	$arr_single_tags = array('meta','img','br','link','area');
	// 匹配开始标签
	preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU',$html,$result);
	$openedtags = $result[1];
	// 匹配关闭标签
	preg_match_all('#</([a-z]+)>#iU',$html,$result);
	$closedtags = $result[1];
	// 计算关闭开启标签数量，如果相同就返回html数据
	$len_opened = count($openedtags);
	if (count($closedtags) == $len_opened) {
		return $html;
	}
	// 把排序数组，将最后一个开启的标签放在最前面
	$openedtags = array_reverse($openedtags);
	// 遍历开启标签数组
	for($i=0; $i<$len_opened; $i++) {
		// 如果标签不属于需要不全的标签
		if(!in_array($openedtags[$i],$arr_single_tags)) {
			// 如果这个标签不在关闭的标签中
			if(!in_array($openedtags[$i],$closedtags)) {
				// 如果在这个标签之后还有开启的标签
				if(isset($openedtag[$i+1]) && $next_tag = $openedtags[$i+1]) {
					// 将当前的标签放在下一个标签的关闭标签的前面
					$html = preg_replace('#</'.$next_tag.'#iU','</'.$openedtags[$i].'></'.$next_tag,$html);
				}
				else {
					// 直接补全闭合标签
					$html .= '</'.$openedtags[$i].'>';
				}
			}
		}
	}
	return $html;
}

/**
 * 获取图片全路径方法
 *
 * @params $string_picture_url
 * 
 * @author Mithern<sunguangxu@guanyisoft.com>
 * @date 2013-07-30
 */
function getFullPictureWebPath($string_picture_url){
	if(0 === strpos(strtolower($string_picture_url),"http://") || 0 === strpos(strtolower($string_picture_url),"https://")){
		return $string_picture_url;
	}
	return __ROOT__ . '/' . ltrim($string_picture_url,'/');
}

/**
 * 写日志
 * @author Terry
 * @date 2013-08-015
 * @param string 日志内容
 * @param string 日志文件名
 */
function writeLog($str_content, $str_log_file) {
    $path = RUNTIME_PATH."Logs/";
    //判断是否存在logs目录，如果不存在则创建
    if(!is_dir($path)){
        @mkdir($path);
        @chmod($path, 0755);
    }
    error_log(date("c")."\t".$str_content."\n", 3, LOG_PATH.$str_log_file);
}

/**
 * 过滤path
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-08-15
 */
function bpath($path,$keyval) {
	$path   = ",".trim(strval($path),",") . ",";
	$keyval = strval($keyval);
	list($key,$val) = explode(":",$keyval);
	$my		= trim(preg_replace("/,$key(.+?),/",',',$path),",");
	if(!$val) return $my;
	return $my ? $my . "," . $keyval:$keyval;
}

/**
* @package  根据指定的格式输出时间
* @param  String  $format 格式为年-月-日 时:分：秒,如‘Y-m-d H:i:s’
* @param  String  $time   输入的时间
* @author Terry<wanghui@guanyisoft.com>
* @return String  $time   时间
*/
function getDateTime($format='',$time=''){
   $time   = !empty($time)  ? $time  : time();
   $format = !empty($format)? $format: 'Y-m-d H:i:s';
   return date($format,$time);
}

/**
 * 生成序列号
 *
 */
function createSn($ps_id) {
    $int_base_num = "9877988";
    //字符长度
    $int_len = strlen($int_base_num);
    $int_data = sprintf("%0".$int_len."d", $int_base_num - $ps_id);
    $str_base = strrev($int_data);
    $int_count = 0;
    for ($int_i=0; $int_i<$int_len; $int_i++) {
        $int_count += substr($str_base, $int_i, 1) * substr($str_base, $int_i+1, 1);
    }
    $int_sub_index = $int_count % $int_len;
    $str_output = $int_sub_index;
    for ($int_i=0; $int_i<$int_len; $int_i++) {
        if ($int_i==$int_sub_index) {
            $str_hash = md5($str_base);            
            $str_output .= ord(substr($str_hash, 0, 1)) % 9;
        }
        $str_output .= substr($str_base, $int_i, 1);
    }
    $str_output -= $int_sub_index;
    return sprintf("%0".($int_len+2)."d", $str_output);
}
/**
 * 将生成的序列号逆袭转成id
 */
function decodeSn($int_code) {
    $int_base_num = "9877988";
    $int_rand_index = substr($int_code, 0, 1);
    $int_data = substr($int_code, 1, $int_rand_index).substr($int_code, $int_rand_index+2);
    $int_data += $int_rand_index;
    $int_data = strrev($int_data);
    return $int_base_num - $int_data;
}

/**
 * 根据配置读取缓存
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-04-18
 */
function getCache($cache_key,$time=null){
	if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
		$memcache = new Cacheds($time);
	}else{
		$memcache = new Caches(time);
	}
	if($memcache->getStat() && ini_get('memcache.allow_failover')){
		//生成一个用来保存 namespace 的 key  
		$ns_key = $memcache->C()->get(CI_SN."_namespace_key");  
		//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
		if($ns_key===false) $memcache->C()->set(CI_SN."_namespace_key",time());  		
	}
	//根据tag获取缓存key
	$cache_key = $cache_key.CI_SN;
	$cache_key = $ns_key.$cache_key;
	$cache_key = md5($cache_key);
    if($memcache->getStat() && ini_get('memcache.allow_failover') && $ary_return = $memcache->C()->get($cache_key)){
        return json_decode($ary_return,true);
    }else{
        return false;
    }
}
/**
 * 根据配置写入缓存
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-04-18
 */
function writeCache($cache_key,$cache_value,$time=null){
	if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
		$memcache = new Cacheds();
	}else{
		$memcache = new Caches($time);
	}	
    if($memcache->getStat() && ini_get('memcache.allow_failover')){
		//生成一个用来保存 namespace 的 key  
		$ns_key = $memcache->C()->get(CI_SN."_namespace_key");  
		//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
		if($ns_key===false) $memcache->C()->set(CI_SN."_namespace_key",time());  
		//根据tag获取缓存key
		$cache_key = $cache_key.CI_SN;
		$cache_key = $ns_key.$cache_key;
		$cache_key = md5($cache_key);
        $memcache->C()->set($cache_key, json_encode($cache_value));
    }
    return true;
}
//处理登录会员信息益汇客户
function writeMemberCache($cache_key,$cache_value,$expire=3600){
    if(ini_get('memcache.allow_failover')){	
		 if(C('MEMCACHE_STAT') == '1'){
			 $Memcache_host = C('MEMCCACHE_HOST');
			 $Memcache_port = C('MEMCCACHE_PORT');
		 }else{
            $ary_tmp_result = D('SysConfig')->getConfigs("GY_CAHE");
			$Memcache_host = $ary_tmp_result['Memcache_host']['sc_value'];
			$Memcache_port = $ary_tmp_result['Memcache_port']['sc_value'];	 
		 }
		if(!empty($Memcache_host) && !empty($Memcache_port)){
			$info = memcache_connect($Memcache_host,$Memcache_port);
			$data = json_encode($cache_value);
			@memcache_set($info, $cache_key.CI_SN, $data, MEMCACHE_COMPRESSED, $expire);
			memcache_close();		
		}
	}
}

/**
 * 识别终端访问类型
 * @author Joe
 * @date 2014-04-09
 * @return bool true:手机端 false:PC端
 */
function check_wap() {
    if (isset($_SERVER['HTTP_VIA'])){
        return true; 
    } 
    if (isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])) {
        return true; 
    }
    if (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) {
        return true; 
    }
    if (strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"VND.WAP.WML") > 0) {
        $br = "WML"; 
    } else {
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : ''; 
        if(empty($browser)) {
            return true; 
        }
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ'); 
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod'); 
        $found_mobile=checkSubstrs($mobile_os_list,$browser) || checkSubstrs($mobile_token_list,$browser); 
        if($found_mobile){
            $br ="WML"; 
        }else {
            $br = "WWW"; 
        }
    } 
    if($br == "WML") { 
        return true; 
    } else { 
        return false; 
    } 
}
function is_weixin()
{ 
    if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {

        return true;

    }  
    return false;
}
function checkSubstrs($list,$str){
    $flag = false; 
    for($i=0;$i<count($list);$i++){
        if(strpos($str,$list[$i]) > 0){
            $flag = true; 
            break; 
        } 
    } 
    return $flag; 
}

/**
 * 遍历获取目录下的指定类型的文件
 * @param $path
 * @param array $files
 * @return array
 */
function getfiles( $path , &$files = array() )
{
	if ( !is_dir( $path ) ) return null;
	$handle = opendir( $path );
	while ( false !== ( $file = readdir( $handle ) ) ) {
		if ( $file != '.' && $file != '..' ) {
			$path2 = $path . '/' . $file;
			if ( is_dir( $path2 ) && $file != '_thumb') {
				getfiles( $path2 , $files );
			} else {
				if ( preg_match( "/\.(gif|jpeg|jpg|png|bmp)$/i" , $file ) ) {
					$files[] = $path2;
					//最多读50个
					if(count($files)>49){
						break;
					}
				}
			}
		}
	}
	return $files;
}
/** 
*  
* 返回一定位数的时间戳，多少位由参数决定 
* 
* @author wanginbin@guanyisoft.com
* @param type 多少位的时间戳 
* @return 时间戳 
 */  
function getTimestamp($digits = false) {  
	$digits = $digits > 10 ? $digits : 10;  
	$digits = $digits - 10;  
	if ((!$digits) || ($digits == 10))  
	{  
		return time();  
	}  
	else  
	{  
		return number_format(microtime(true),$digits,'','');  
	}  
} 

/**
* 生成随机字符串，由数字组成。
* @author hcaijin
* @date 2014-10-16
* @param int $int 生成的随机字串长度
* @return string 返回生成好的随机字串
*/
function randNumStr($int = 6) {
    $strings = '0123456789';
    $return = '';
    for ($i = 0; $i < $int; $i++) {
        srand();
        $rnd = mt_rand(0, 9);
        $return = $return . $strings[$rnd];
    }
    return $return;
}


/**
* 遮掩函数
* @author hcaijin
* @date 2014-10-27
* @param string $str 要遮掩的字符串
* @param int $int 遮掩的长度
* @return string 返回生成字串
*/
function coverStr($str,$length = 8){
    $string = '';
    $arr = str_split($str,1);
    $count = count($arr);
    if($count <=$length){
        $length = count($arr)/2;
    }
    foreach ($arr as $k=>$r){
        if($k+1<=($count-$length)/2 || $k+1>($count-$length)/2+$length){
            $string .=$r;
        }else{
            $string .= '*';
        }
    }
    return $string;
}
/**
* 截取中英文字符串
* @author wangguibin
* @date 2014-11-14
*/
function csubstr($str, $length, $charset="", $start=0, $suffix=true) {
    if (empty($charset))
        $charset = "utf-8";

    if (function_exists("mb_substr")) {
        if (mb_strlen($str, $charset) <= $length)
            return $str;
        $slice = mb_substr($str, $start, $length, $charset);
    }
    else {
        $re['utf-8'] = "/[\x01-\x7f]¦[\xc2-\xdf][\x80-\xbf]¦[\xe0-\xef][\x80-\xbf]{2}¦[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]¦[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]¦[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]¦[\x81-\xfe]([\x40-\x7e]¦\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        if (count($match[0]) <= $length)
            return $str;
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($suffix)
        return $slice . "***";
    return $slice;
}

/**
@par $val 字符串参数，可能包含恶意的脚本代码如<script language="javascript">alert("hello world");</script>
* @return  处理后的字符串
**/
function RemoveXSS($val) {
	// 引入AntiXSS库，防止xss
   $val = AntiXSS::setFilter($val,'gray');
   
   $val = preg_replace('/([\x00-\x08\x0b-\x0c\x0e-\x19])/', '', $val);
   $search = 'abcdefghijklmnopqrstuvwxyz'; 
   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';  
   $search .= '1234567890!@#$%^&*()'; 
   $search .= '~`";:?+/={}[]-_|\'\\'; 
   for ($i = 0; $i < strlen($search); $i++) { 
      // ;? matches the ;, which is optional 
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 
 
      // @ @ search for the hex values 
      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ; 
      // @ @ 0{0,7} matches '0' zero to seven times  
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ; 
   } 
 
   // now the only remaining whitespace attacks are \t, \n, and \r 
   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'); 
   $ra = array_merge($ra1, $ra2); 
 
   $found = true; // keep replacing as long as the previous round replaced something 
   while ($found == true) { 
      $val_before = $val; 
      for ($i = 0; $i < sizeof($ra); $i++) { 
         $pattern = '/'; 
         for ($j = 0; $j < strlen($ra[$i]); $j++) { 
            if ($j > 0) { 
               $pattern .= '(';  
               $pattern .= '(&#[xX]0{0,8}([9ab]);)'; 
               $pattern .= '|';  
               $pattern .= '|(&#0{0,8}([9|10|13]);)'; 
               $pattern .= ')*'; 
            } 
            $pattern .= $ra[$i][$j]; 
         } 
         $pattern .= '/i';  
         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag  
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags  
         if ($val_before == $val) {  
            // no replacements were made, so exit the loop  
            $found = false;  
         }  
      }  
   }  
   return $val;  
}

/**
* 遮掩函数
* @author zhangjiasuo
* @date 2014-12-10
* @param string $str 请求url
* @return  mixed 返回状态 true false
*/
function check_file_exists($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    $result = curl_exec($curl);
    $status = false;
    // 如果请求没有发送失败
    if ($result !== false) {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
        if ($statusCode == 200) {
            $status = true;   
        }
    }
    curl_close($curl);
    return $status;
}

if(!function_exists('getConf')){
	function getConf($key,$dv=null) {
	    //优先获取配置文件中的配置
	    $conf = C($key,null);
	    //如果配置文件中没有相应的配置，则读取数据库中配置
	    if(is_null($conf)){
	        $conf = D('SysConfig')->where(array('sc_key'=>$key))->getField("sc_value");
	        if(is_null($conf)) {
	            $conf = $dv;
	        }
	    }
	    return $conf;
	}
}

/**
 * 加密字符串
 * @return encodedPlainText:key
 * #encodedPlainText base64转码后的加密字符串
 * #key 加密用的那个key
 */
function encrypt_old($plaintext) {
	
	$arry_salt_pool = array('a','b','c','d','e','f','g','h','i','j','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','1','2','3','4','5','6','7','8','9','0');
	
	$key_size = 6;
	$i = 0;
	$key = array();
	//生成随机字符串
	while($i < $key_size) {
		$rand = floor(rand(0, count($arry_salt_pool)));
		$key[$i] = $arry_salt_pool[$rand];
		$i++;
	}
	$key = implode('',$key);
	
	// 使用我们随机生成的字符串作为key
    // 把key转换成16进制字符串
	$key_hex = pack('H*', $key);   
	
    // 新增一个随机IV字符串，使用CBC编码格式
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	
	//加密
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key_hex,
							 $plaintext, MCRYPT_MODE_CBC, $iv);
	
	//填充null,保证$iv的长度跟$iv_size一致
	while(strlen($iv) < $iv_size){
        $iv .= "\0";
    }
	
	//把iv跟加密后的字符串拼接在一起，供解密时使用
	$ciphertext = $iv . $ciphertext;
	//把key拼接到字符串尾部，以“:”连接，供解密时使用
	$ciphertext_result = base64_encode($ciphertext) . ':' . $key;
	
	return $ciphertext_result;

}
/**
 * 解密数据 
 * @格式：encodedPlainText:key
 * @ encodedPlainText base64转码后的加密字符串
 * @ key 加密用的那个key
 */
function decrypt_old($plaintext, $key = '') {
	
	if($key == '') {
		$ary_ciphertext = explode(':',$plaintext);
		if(count($ary_ciphertext) != 2)
			return false;
		//分别获取key和加密后的字符串
		$key = $ary_ciphertext[1];
		$plaintext = $ary_ciphertext[0];
	}	
	$key_hex = pack('H*', $key);   
	
    //获取IV长度
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

	$ciphertext_dec = base64_decode($plaintext);    
	//根据iv_size获取加密用IV
	$iv = substr($ciphertext_dec, 0, $iv_size);
	$plaintext = substr($ciphertext_dec, $iv_size);
	$ciphertext_result = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key_hex,
                                    $plaintext, MCRYPT_MODE_CBC, $iv);
	//去除末尾的NULL符号
	$ciphertext_result = rtrim($ciphertext_result, "\0");								
	return $ciphertext_result;
}

function vagueMobile($mobile) {
	return substr_replace($mobile,'*****',3,5);
}

/**
 * 加密字符串,兼容5.6.0版本
 * @return encodedPlainText:key
 * #encodedPlainText base64转码后的加密字符串
 * #key 加密用的那个key
 */
function encrypt($plaintext) {
	/**
	$arry_salt_pool = array('a','b','c','d','e','f','g','h','i','j','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','1','2','3','4','5','6','7','8','9','0');
	
	$key_size = 32;
	$i = 0;
	$key = array();
	//生成随机字符串
	while($i < $key_size) {
		$rand = floor(rand(0, count($arry_salt_pool)-1));
		$key[$i] = $arry_salt_pool[$rand];
		$i++;
	}
	$key = implode('',$key);
	**/

	$key = md5(CI_SN);
	
	/* 
	$arry_salt_pool = array('a','b','c','d','e','f','1','2','3','4','5','6','7','8','9','0');
	
	$key_size = 32;
	$i = 0;
	$key = array();
	//生成随机64位16进制字符串
	while($i < $key_size) {
		$rand = floor(rand(0, count($arry_salt_pool)-1));
		$key[$i] = $arry_salt_pool[$rand];
		$i++;
	}
	$key = implode('',$key);
    dump($key); */
    // 把16进制字符串转换成加密字符
	$key_hex = pack('H*', $key);   
	
    // 新增一个随机IV字符串，使用CBC编码格式
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	//$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	//加密
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key_hex,
							 $plaintext, MCRYPT_MODE_CBC);							 
	//填充null,保证$iv的长度跟$iv_size一致
	while(strlen($iv) < $iv_size){
        $iv .= "\0";
    }
	//把iv跟加密后的字符串拼接在一起，供解密时使用
	$ciphertext = $iv . $ciphertext;
	//把key拼接到字符串尾部，以“:”连接，供解密时使用
	$ciphertext_result = base64_encode($ciphertext). ':' . $key;
	
	return $ciphertext_result;
}
/**
 * 解密数据,兼容5.6.0版本 
 * @格式：encodedPlainText:key
 * @ encodedPlainText base64转码后的加密字符串
 * @ key 加密用的那个key
 */
function decrypt($plaintext, $key = '') {
	if(strlen($plaintext)<32){
		return $plaintext;
	}
	if($key == '') {
		$ary_ciphertext = explode(':',$plaintext);
		if(count($ary_ciphertext) != 2)
			return false;
		//分别获取key和加密后的字符串
		$key = $ary_ciphertext[1];
		$ciphertext_dec = base64_decode($ary_ciphertext[0]);
	}	
    // 把16进制字符串转换成加密字符
    $key_hex = pack('H*', $key);   
	
    //获取IV长度
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

	//根据iv_size获取加密用IV
	$iv = substr($ciphertext_dec, 0, $iv_size);
	$plaintext = substr($ciphertext_dec, $iv_size);
	$ciphertext_result = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key_hex,
                                    $plaintext, MCRYPT_MODE_CBC, $iv);
	//去除末尾的NULL符号
	$ciphertext_result = rtrim($ciphertext_result, "\0");								
	return $ciphertext_result;
}


/**
 * @param $data string 待加密的字符串
 * @param $key string 加密的密钥
 * @return string 返回加密后的字符串
 * @author add by zhangjiasuo 
 * @date 2015-08-10
 */
function encrypt2($data, $key=''){
	$data = trim($data);
	$data=(string)$data;
	if($key ==''){
		$key = CI_SN ;
	}
	$key = md5($key);
    $x  = 0;
    $len = strlen($data);
    $l  = strlen($key);
	$char = $str = '';
    for ($i = 0; $i < $len; $i++){
        if ($x == $l){
			$x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++){
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str).":";
}

/**
 * @param $data string 待解密的字符串
 * @param $key string 解密的密钥
 * @return string 返回加密后的字符串
 * @author add by zhangjiasuo 
 * @date 2015-08-10
 */
function decrypt2($plaintext, $key=''){
	if($plaintext=='' && !strpos($plaintext,':')){
		return false;
	}
	$ary_data = explode(':',$plaintext);
	
	$data = $ary_data[0];
	if($key ==''){
		$key = CI_SN ;
	}
	$key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
	$char = $str = '';
    for ($i = 0; $i < $len; $i++){
        if ($x == $l){
			$x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++){
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))){
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

function Qiniu_Encode($str) // URLSafeBase64Encode
{
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($str));
}
function Qiniu_Sign($url) {//$info里面的url
    $setting = C ( 'UPLOAD_SITEIMG_QINIU' );
    $duetime = time() + 86400;//下载凭证有效时间
    $DownloadUrl = $url . '?e=' . $duetime;
    $Sign = hash_hmac ( 'sha1', $DownloadUrl, $setting ["driverConfig"] ["secrectKey"], true );
    $EncodedSign = Qiniu_Encode ( $Sign );
    $Token = $setting ["driverConfig"] ["accessKey"] . ':' . $EncodedSign;
    $RealDownloadUrl = $DownloadUrl . '&token=' . $Token;
    return $RealDownloadUrl;
}
function _ReplaceItemDescPicDomain($str_desc = '') {
    if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
        $preg = "/<img.*?src=\"(.+?)\".*?>/i";
        preg_match_all($preg, $str_desc, $match);
        $new_str_desc = $str_desc;
        if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
            $ary_replace_goal = array();
            $ary_replace_to = array();
            foreach ($match[1] as $key => $val) {
                $ary_tmp_pic_url = explode("/Uploads/",$val);
                if(isset($ary_tmp_pic_url[0]) && !empty($ary_tmp_pic_url[0]) && $ary_tmp_pic_url[0] != C('DOMAIN_HOST').'/Public'){
                    $new_str_desc = str_replace($ary_tmp_pic_url[0], C('DOMAIN_HOST').'/Public', $str_desc);
                }
                //break;
            }
        }
        return $new_str_desc;
    }
	//是否启用七牛图片存储
	if($_SESSION['OSS']['GY_QN_ON'] == '1'){
        $preg = "/<img.*?src=\"(.+?)\".*?>/i";
        preg_match_all($preg, $str_desc, $match);
        $new_str_desc = $str_desc;
		$new_str_desc = str_replace("http://".$_SESSION['OSS']['GY_QN_DOMAIN'].'/',"",$new_str_desc);
		$new_str_desc = urldecode($new_str_desc);
        if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
            $ary_replace_goal = array();
            $ary_replace_to = array();
            foreach ($match[1] as $key => $val) {
                $ary_tmp_pic_url = explode("?",$val);
                if(isset($ary_tmp_pic_url[1]) && !empty($ary_tmp_pic_url[1])){
                    $new_str_desc = str_replace('?'.$ary_tmp_pic_url[1],'', $new_str_desc);
                }
                //break;
            }
        }
		return $new_str_desc;
	}
    return $str_desc;
}

 /**
 * 异步处理
 *
 * @param string $url 请求地址
 * @param array $post_data 请求数据
 * @author zhangjiasuo  <zhangjiasuo@guanyisoft.com>
 * @date 2015-07-13
 */
 function request_by_fsockopen($url,$post_data=array()){
	$url_array = parse_url($url);
	$hostname = $url_array['host'];
	$port = isset($url_array['port'])? $url_array['port'] : 80; 
	$requestPath = $url_array['path'] ."?". $url_array['query'];
	$fp = fsockopen($hostname, $port, $errno, $errstr, 10);
	if (!$fp) {
		echo "$errstr ($errno)";
		return false;
	}
	$method = "GET";
	if(!empty($post_data)){
		$method = "POST";
	}
	$header = "$method $requestPath HTTP/1.1\r\n";
	$header.="Host: $hostname\r\n";
	if(!empty($post_data)){
		$_post = strval(NULL);
		foreach($post_data as $k => $v){
				$_post[]= $k."=".urlencode($v);//必须做url转码以防模拟post提交的数据中有&符而导致post参数键值对紊乱
		}
		$_post = implode('&', $_post);
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";//POST数据
		$header .= "Content-Length: ". strlen($_post) ."\r\n";//POST数据的长度
		$header.="Connection: Close\r\n\r\n";//长连接关闭
		$header .= $_post; //传递POST数据
	}else{
		$header.="Connection: Close\r\n\r\n";//长连接关闭
	}
	//echo "<pre>";print_r($header);die();
	fwrite($fp, $header);
	//-----------------调试代码区间-----------------
	/*$html = '';
	while (!feof($fp)) {
		$html.=fgets($fp);
	}
	echo $html;*/
	//-----------------调试代码区间-----------------
	fclose($fp);
 }
 
function del_file($ci_sn){
	if(!empty($ci_sn)){
		$path = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . $ci_sn.'/TmpHtml';
	}
	if($handle = opendir($path)){
		while(false !== ($file = readdir($handle))){
			if($file != '.' && $file != '..'){
				$path_one = $path.'/'.$file;
				if(file_exists($path_one)){
					unlink($path_one);
				}
			}
		}
		closedir($handle);
	}
	return true;
}
/**
 * 定义静态化html的规则
 * 兼容使用负载均衡的服务器环境
 * 取服务器ip,与请求参数做md5加密
 */
function setHtmlRule(){
    $request_uri = $_SERVER['REQUEST_URI'];
    if(check_wap() && $request_uri == '/'){
        $request_uri = '/Wap';
    }
    $return = md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].$request_uri);
    return $return;
}

/**
 * 生成二维码图片，并返回图片地址
 * @param $c
 * @param string $subdir
 * @param string $prefix
 * @return bool
 */
function createQcPic($c, $subdir='', $prefix='') {
    $result = false;
    if(empty($c)) {
        goto result;
    }
    if($subdir) {
        $subdir .= '/';
    }
    //二维码图片不存在重新生成
    @mkdir('./Public/Uploads/' . CI_SN . '/images/'.$subdir);
    $file_name  = '/Public/Uploads/' . CI_SN . '/images/'. $subdir . $prefix . md5($c).'.png';

    if(file_exists('.'. $file_name)) {
        $result = $file_name;
        goto result;
    }
    require_once './Public/Lib/phpqrcode/phpqrcode.php';

    QRcode::png($c,FXINC.$file_name);
    //dump($c);
    //判断文件是否上传到七牛,没有上传,则调用上传方法上传到七牛
    $result = D('QnPic')->picToQn($file_name);

    result:
    return $result;
}

function RemovePhp($string) {
	if(preg_match_all('~(<\?(?:\w+|=)?|\?>|language\s*=\s*[\"\']?php[\"\']?)~is', $string, $sp_match))
	{
		$sp_match[1] = array_unique($sp_match[1]);
		for ($curr_sp = 0, $for_max2 = count($sp_match[1]); $curr_sp < $for_max2; $curr_sp++)
		{
			$string = str_replace($sp_match[1][$curr_sp],'%%%SMARTYSP'.$curr_sp.'%%%',$string);
		}
		for ($curr_sp = 0, $for_max2 = count($sp_match[1]); $curr_sp < $for_max2; $curr_sp++)
		{
			 $string= str_replace('%%%SMARTYSP'.$curr_sp.'%%%', '<?php echo \''.str_replace("'", "\'", $sp_match[1][$curr_sp]).'\'; ?>'."\n", $string);
		}
	 }
	 return $string;
}