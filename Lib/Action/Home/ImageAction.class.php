<?php

/**
 * 前台图片
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-15
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ImageAction extends Action {

    /**
     * 重定义空方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-15
     */
    public function _empty() {
        $this->showImage();
    }
	/**
     * 图片处理
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-11-19
     */
     public function doImage() {
     	if(!empty($_FILES['upfile']['tmp_name']) && !empty($_POST['file_path'])){
			@unlink($_SERVER['DOCUMENT_ROOT'].$_POST['file_path']);
     		if(file_exists($_SERVER['DOCUMENT_ROOT'].$_POST['file_path'])){
				@unlink($_SERVER['DOCUMENT_ROOT'].$_POST['file_path']);
     			
				$upload_path = explode('/',$_POST['file_path']);
	     		unset($upload_path[count($upload_path)-1]);
	     		$upload_path = $_SERVER['DOCUMENT_ROOT'].implode('/',$upload_path);
				
     			if ( !file_exists($upload_path) ) {
		            if ( !mkdir( $upload_path, 0777 , true ) ) {
		                echo 0;exit;
		            }
		        }
	      		if(move_uploaded_file($_FILES['upfile']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].$_POST['file_path'])){
	     			echo 2;exit;
	     		}else{
	     			echo 0;exit;
	     		}  
     		}else{
     			$upload_path = explode('/',$_POST['file_path']);
	     		unset($upload_path[count($upload_path)-1]);
	     		$upload_path = $_SERVER['DOCUMENT_ROOT'].implode('/',$upload_path);
     			if ( !file_exists($upload_path) ) {
		            if ( !mkdir( $upload_path, 0777 , true ) ) {
		                echo 0;exit;
		            }
		        }
				$upload_file = $_SERVER['DOCUMENT_ROOT'].$_POST['file_path'];
				$upload_file = str_replace('midea.','midea',$upload_file);
				if(move_uploaded_file($_FILES['upfile']['tmp_name'],$upload_file)){
						echo 1;exit;
				}else{
						echo 0;exit;
				}      			
     		}
     	}
     	echo 0;exit;
     }

}