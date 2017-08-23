<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://guanyisoft.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangjiasuo <zhangjiasuo@guanyisoft.com> 
// +----------------------------------------------------------------------
// | date: 2015-06-03 19:39:40
// +----------------------------------------------------------------------

require_once('Qiniu/QiniuStorage.class.php');
class Qiniu{
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;

    /**
     * 上传错误信息
     * @var string
     */
    private $error = '';

    private $config = array(
        'secrectKey'     => '', //七牛服务器
        'accessKey'      => '', //七牛用户
        'domain'         => '', //七牛密码
        'bucket'         => '', //空间名称
        'timeout'        => 300, //超时时间
    );

    /**
     * 构造函数，用于设置上传根路径
     * @param array  $config FTP配置
     */
    public function __construct($config){
		$tmp_config = array ( 
			'maxSize' => 5 * 1024 * 1024,//文件大小
			'rootPath' => './'	
		);
		if(!empty($_SESSION['OSS']['GY_QN_SECRECT_KEY'])){
			$this->config['secrectKey']=$_SESSION['OSS']['GY_QN_SECRECT_KEY'];
		}
		if(!empty($_SESSION['OSS']['GY_QN_ACCESS_KEY'])){
			$this->config['accessKey']=$_SESSION['OSS']['GY_QN_ACCESS_KEY'];
		}		
		if(!empty($_SESSION['OSS']['GY_QN_DOMAIN'])){
			$this->config['domain']=$_SESSION['OSS']['GY_QN_DOMAIN'];
		}	
		if(!empty($_SESSION['OSS']['GY_QN_BUCKET_NAME'])){
			$this->config['bucket']=$_SESSION['OSS']['GY_QN_BUCKET_NAME'];
			$config['bucket'] = $_SESSION['OSS']['GY_QN_BUCKET_NAME'];
		}		
        $this->config = array_merge($this->config, $config);
		//$this->config = array_merge($this->config,$tmp_config['driverConfig']);
        /* 设置根目录 */
        $this->qiniu = new QiniuStorage($this->config);
    }

    /**
     * 检测上传根目录(七牛上传时支持自动创建目录，直接返回)
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath){
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * 检测上传目录(七牛上传时支持自动创建目录，直接返回)
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath){
        return true;
    }

    /**
     * 创建文件夹 (七牛上传时支持自动创建目录，直接返回)
     * @param  string $savepath 目录名称
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath){
        return true;
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function upload(&$file,$replace=true) {
		if(!empty($this->config['key'])){
			$key = $this->config['key'];
		}else{
			$key = $file['name'];
		}
		//判断文件是否上传,已存在不上传
		$img_info = $this->info($key);
		if(!empty($img_info)){
			$result = true;
		}else{
	        $upfile = array(
            'name'=>'file',
            'fileName'=>$key,
            'fileBody'=>file_get_contents($file['tmp_name'])
			);
			$config = array(
				'saveName'=>$key,
				'save_name'=>$key,
			);
			$result = $this->qiniu->upload($config, $upfile);		
		}

        $url = $this->qiniu->downlink($key);
		//$thumb_url = $this->getThumbUrl($url,$width=10,$height=10);
        $file['url'] = $url;
		if($result===false){
			$state = false;
		}else{
			$state = 'SUCCESS';
		}
		//暂时设为公有
		if(!empty($_SESSION['OSS']['GY_QN_PRIVATE'])){
			$prviate_url = $this->show($url);	
		}else{
			$prviate_url = $url;
		}
		return array('state'=>$state,'url'=>$url,'prviate_url'=>$prviate_url);
    }
	public function getThumbUrl($key,$width=0,$height=0,$q=85){
		$tmp_url = $this->qiniu->downlink($key);
		$tmp_thumb_url = $tmp_url.'?'.'imageView2/0/w/'.$width.'/h/'.$height.'/q/'.$q;
		//暂时设为公有
		if(!empty($_SESSION['OSS']['GY_QN_PRIVATE'])){
			$url = $this->Qiniu_Sign($tmp_thumb_url); 
		}else{
			$url = $tmp_thumb_url;
		}
		return $url;
	}
	public function getThumbUrlByUrl($url,$width=0,$height=0,$q=85){
		//$tmp_thumb_url = $url.'?'.'imageView2/0/w/'.$width.'/h/'.$height.'/q/'.$q;
		$tmp_thumb_url = $url.'?'.'imageView2/0/w/'.$width.'/h/'.$height;
		if(empty($_SESSION['OSS']['GY_QN_PRIVATE'])){
			return $tmp_thumb_url;
		}
		$url = $this->Qiniu_Sign($tmp_thumb_url); 
		return $url;	
	}
	/**
     * 通过KEY获取签名之后url
     * @return string url
     */
	public function getSignUrl($key){
		$tmp_url = $this->qiniu->downlink($key);
		if(empty($_SESSION['OSS']['GY_QN_PRIVATE'])){
			return $tmp_url;
		}		
		$url = $this->Qiniu_Sign($tmp_url); 
		return $url;
	}
    /**
     * 获取签名之后url
     * @return string url
     */
	public function show($base_url){
		$url = $this->Qiniu_Sign($base_url); 
		return $url;
	}
	
    /**
     * 获取签名之后url
     * @return string url
     */
	public function getInfo($base_url){
		$img_info = $this->qiniu->getInfo($base_url); 
		return $img_info;
	}
	public function info($base_url){
		$img_info = $this->qiniu->info($base_url); 
		return $img_info;		
	}
    /**
     * 获取签名之后url
     * @return string url
     */
	public function getList($ary_query){
		$img_lists = $this->qiniu->getList($ary_query); 
		return $img_lists;
	}		
    /**
     * 删除图片
     * @return string files
     */
	public function delBatch($files){
		return $this->qiniu->delBatch($files); 
	}
    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->qiniu->errorStr;
    }
	
	function Qiniu_Encode($str) // URLSafeBase64Encode
	{
		$find = array('+', '/');
		$replace = array('-', '_');
		return str_replace($find, $replace, base64_encode($str));
	}
	function Qiniu_Sign($url) {//$info里面的url
		$setting = $this->config;
		$duetime = time()+86400*7;//下载凭证有效时间
		if(strpos($url,"?")!=false){
			$DownloadUrl = $url . '&e=' . $duetime;
		}else{
			$DownloadUrl = $url . '?e=' . $duetime;
		}		
		$Sign = hash_hmac ( 'sha1', $DownloadUrl, $setting["secrectKey"], true );
		$EncodedSign = $this->Qiniu_Encode($Sign);
		$Token = $setting["accessKey"] . ':' . $EncodedSign;
		$RealDownloadUrl = $DownloadUrl . '&token=' . $Token;
		return $RealDownloadUrl;
	}
}
