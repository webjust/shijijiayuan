<?php
class Ip{
	
	private $url = "http://ip.taobao.com/service/getIpInfo.php";
	
	public function getIpInfo($str_ip = "127.0.0.1"){
		$str_request_url = $this->url . '?ip=' . $str_ip;
		$mixed_result = file_get_contents($str_request_url);
		$array_result = json_decode($mixed_result,true);
		if(false == $array_result){
			return false;
		}
		if($array_result["code"] == 1){
			return false;
		}
		return $array_result["data"];
	}
	
}