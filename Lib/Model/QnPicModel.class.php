<?php

/**
 * 七牛相关模型层 Model
 * @package Model
 * @version 7.8.4
 * @author wangguibin
 * @date 2015-06-04
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class QnPicModel extends GyfxModel {
    
	/**
     * 构造方法
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-09
     */
    public function __construct() {
        parent::__construct();
        $this->qn_pic = M('qn_pic',C('DB_PREFIX'),'DB_CUSTOM');
    }
    /**
     * 判断文件是否上传到七牛没有上传调用上传方法上传到七牛
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-09
     * @param 
     */
    public function picToQn($key,$width=0,$height=0){
		if($_SESSION['OSS']['GY_QN_ON'] != 1){
			$str_pic = str_replace('/Public/Lib/ueditor/php/../../../Uploads/','',$key);
			$str_pic = str_replace('/Public/Lib/ueditor/php','',$str_pic);
			return $str_pic;
		}
		if(empty($key)){
			return $key;
		}
		if(!empty($_SESSION['OSS']['GY_QN_DOMAIN'])){
			//判断是否是七牛地址
			if(strpos($key,$_SESSION['OSS']['GY_QN_DOMAIN']) !=false){
				//无需生产缩略图
				if(empty($width) && empty($height)){
					return $key;
				}else{
					$key = str_replace('http://'.$_SESSION['OSS']['GY_QN_DOMAIN'].'/','',urldecode($key));
					$ary_keys = explode('?',$key);
					$key = $ary_keys[0];
					unset($ary_keys);
				}	
			}			
		}
		$key = str_replace('/Public/Lib/ueditor/php/../../../Uploads/','/Public/Uploads/',$key);
		$pic_info = D('Gyfx')->selectOneCache('qn_pic','pic_url',array('pic_url'=>$key));
		if(empty($pic_info['pic_url'])){
			//上传图片到卖买提
			//是否启用七牛，启用的话上传的七牛
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){
				if(strpos($key,'/Public/Uploads/')!== false){
					$file_path = rtrim($_SERVER['DOCUMENT_ROOT'],"/").$key;
					if(file_exists($file_path)){
						$file['tmp_name'] = $file_path;
						$setting['key'] = $key;
						$Upload = new Qiniu($setting);
						$tmp_info = $Upload->upload($file);
						if($tmp_info['state'] != 'SUCCESS'){
							//unlink($file_path);
						}else{
							$time = date("Y-m-d H:i:s",time()+86000);
							if(!M('qn_pic',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pic_url'=>$key,'pic_qn_url'=>$tmp_info['prviate_url'],'sign_end_time'=>$time), '', true)){
								return false;
							}else{
								D('Gyfx')->deleteOneCache('qn_pic','pic_url',array('pic_url'=>$key));
							}
						}
						//无需生产缩略图
						if(empty($width) && empty($height)){
							return $tmp_info['prviate_url'];
						}else{
							$url = $Upload->getThumbUrlByUrl($tmp_info['url'],$width,$height);
							return $url;
						}						
					}else{
						if(strpos($str_pic,'http')!== false && strpos($str_pic,$_SESSION['OSS']['GY_QN_DOMAIN'])===false){//去除外链第三方图片错误图片地址
							$key = ltrim($key,"/");
							$is_exist=strpos($key,$_SERVER['SERVER_NAME']);
							if($is_exist !== false){
								$key = str_replace("http://".$_SERVER['SERVER_NAME'].'/',"",$key);
								return $key;
							}
							return $key;
						}else{
							return $key;
						}
					}										
				}else{
					if(strpos($key,'http')!== false && strpos($key,$_SESSION['OSS']['GY_QN_DOMAIN'])===false){
						$key = ltrim($key,"/");
						$is_exist=strpos($key,$_SERVER['SERVER_NAME']);
						if($is_exist !== false){
							$key = str_replace("http://".$_SERVER['SERVER_NAME'].'/',"",$key);
							return $key;
						}
						return $key;
					}else{
						return $key;
					}
				}
			}
		}else{
            $setting = array();
			$Upload = new Qiniu($setting);
			//无需生产缩略图
			if(empty($width) && empty($height)){
				$url = $Upload->getSignUrl($key);
			}else{
				$url = $Upload->getThumbUrl($key,$width,$height);
			}
			return $url;
		}
		return $key;
	}
      
}