<?php

/**
 * 微信接口
 *
 * @package Common
 * @subpackage Api
 * @version 7.8.4
 * @author Wangguibin <Wangguibin@guanyisoft.com>
 * @date 2015-06-18
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class WeixinApi{

    //private $code = "";
	//private $openid = "";
    private $appid = '';
    private $appsecret = '';
	//private $access_token = '';
	private $curl_timeout;//curl超时时间
    /**
     * 构造函数
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2015-06-18
     */
    public function __construct() {
		$weixin_config = D('Gyfx')->selectOneCache('payment_cfg','pc_config',array('pc_abbreviation' => 'WEIXIN'));
		$weixin_config['pc_config'] = json_decode($weixin_config['pc_config'], TRUE);
		$this->appid = $weixin_config['pc_config']['weixin_appid'];
		$this->appsecret = $weixin_config['pc_config']['weixin_appsecret'];
		//设置curl超时时间
		$this->curl_timeout = 30;
    }

    /**
     * 获取appid
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2015-06-18
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     */
    public function wxSign() {
		if(isset($_SESSION['Members'])){ 
			redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
			//return $_SESSION['Members']['wxOpenId'];
		}
		if(($this->appid == "") || ($this->appsecret == "")){
			$_SESSION['no_wx'] = 1;
			if($_SESSION['is_product'] == 0 || empty($_SESSION['REQUEST_URI'])){
				redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);exit;
			}else{//当微信扫描从商品列表页或者商品详情页 并且 $_SESSION['REQUEST_URI'] 不为空的时候 跳转到相应的链接
				redirect($_SESSION['REQUEST_URI']);exit;
			}

		}
		$host_url =D('Gyfx')->selectOneCache('sys_config','sc_value',array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'));	
		if(!empty($host_url['sc_value'])){
			if($host_url['sc_value'] !='http://test001.abcde.com:8071/'){
				$REDIRECT_URI = $host_url['sc_value'].'Wap/User/getSignId';
			}else{
				$REDIRECT_URI="http://".$_SERVER['SERVER_NAME'].'/Wap/User/getSignId';
			}			
		}else{
			$REDIRECT_URI="http://".$_SERVER['SERVER_NAME'].'/Wap/User/getSignId';
		}
		$scope='snsapi_base';
		//$scope='snsapi_userinfo';//需要授权
		$url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appid.'&redirect_uri='.urlencode($REDIRECT_URI).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
		header("Location:".$url);exit;
    }

	
    /**
     * 获取appid
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2015-06-18
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     */
    public function getSignId($code) {
		if(isset($_SESSION['Members'])){ 
			redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
			//return $_SESSION['Members']['wxOpenId'];
		}
		$get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code';
		$json_obj = $this->request($get_token_url);
		//根据openid和access_token查询用户信息 
		//$access_token = $json_obj['access_token']; 
		$openid = $json_obj['openid']; 
		return $openid;
    }
	//获取weixin session
	public function getAccessTocken(){		
		if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
			$memcaches = new Cacheds(7000);
		}else{
			$memcaches = new Caches(7000);
		}
		if($memcaches->getStat()){
			$cache_key = CI_SN.'selectWxAccesstoken';
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				$ary_return_data = json_decode($ary_return,true);
				if(empty($ary_return_data)){
					$ary_return_data = $this->getWxTocken();
				   //写入缓存
					if($memcaches->getStat()){
						$memcaches->C()->set($cache_key, json_encode($ary_return_data));
					}	
				}
	            return $ary_return_data;
	        }else{
	        	$ary_return_data = $this->getWxTocken();
			   //写入缓存
	            if($memcaches->getStat()){
	                $memcaches->C()->set($cache_key, json_encode($ary_return_data));
	            }
	        	return $ary_return_data;
	        }
		}
	}
	public function getWxTocken(){
		//失效日期7200秒
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
		$json_obj = $this->request($url);
		return $json_obj['access_token']; 
	}
	//获取用户信息
	public function getUserInfo($openid,$access_token){
		$get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
		$json_obj = $this->request($get_user_info_url);
		return $json_obj;
	}
	public function request($url){
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch,CURLOPT_URL,$url); 
		curl_setopt($ch,CURLOPT_HEADER,0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 ); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		$res = curl_exec($ch); 
		curl_close($ch); 
		$json_obj = json_decode($res,true); 
		return $json_obj;
	}
	
	
}