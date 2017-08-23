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
class ImagesAction extends Action {

    /**
     * 重定义空方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-15
     */
    public function _empty() {
        $this->showImage();
    }

    /**
     * 显示前台图片
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-23
     */
    public function showImage() {
        import('ORG.Util.Image');
        C('SHOW_PAGE_TRACE', false);
        layout(false);
		$pic_exists=false;
        $url = gzuncompress(urldecode(base64_decode($this->_get('u'))));
        $w = $this->_get('w', 'htmlspecialchars', 200);
        $h = $this->_get('h', 'htmlspecialchars', 200);
        
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
				$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_OTHER_DOMAIN'];
			}
			$info = pathinfo($url);
			$thumbname = 'http://'.$_SESSION['OSS']['GY_OSS_PIC_URL'] . $info['dirname'] . '/_thumb/' . $info['filename'] . '_' . $w . '_' . $h . '.' . $info['extension'];
			if(file_get_contents($thumbname,0,null,0,1)){
				$pic_exists=true;
				Image::showImg($thumbname);
			}
		}else{
			$info = pathinfo($url);
			$thumbname = APP_PATH . $info['dirname'] . '/_thumb/' . $info['filename'] . '_' . $w . '_' . $h . '.' . $info['extension'];
			if(file_exists($thumbname)){
				$pic_exists=true;
				Image::showImg($thumbname);
			}
		}
        
		if($pic_exists==false){
			$file_path = APP_PATH . $info['dirname'] . '/_thumb/';
			$file_path = str_replace('//', '/', $file_path);
			mkdir($file_path);
			$thumbname = str_replace('//', '/', $thumbname);

			header("Content-type: image/jpeg");
			//保存缩略图缓存
			if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
				if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
					$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_OTHER_IP'];
				}
				Image::thumb($_SESSION['OSS']['GY_OSS_PIC_URL'] . $url, $thumbname, 'jpg', $w, $h);
			}else{
				Image::thumb(APP_PATH  . $url, $thumbname, 'jpg', $w, $h);
			}
			
			if(!empty($res)){
				if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			
					if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
						$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_OTHER_IP'];
					}
					$com_obj = new Communications();
					$ary_request_data = array();

					$ary_request_data['upfile']="@".$thumbname;
					$ary_request_data['file_path']=$info['dirname'] . '/_thumb/' . $info['filename'] . '_' . $w . '_' . $h . '.' . $info['extension'];

					$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_OSS_PIC_URL'].'/Home/Images/doImage', $ary_request_data, array(), false);
					if(!$res){
						echo 'error';exit;	
					}
				}
			}
			//输出图像
			Image::showImg($thumbname);
		}
    }

    /**
     * 生成水印图
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-23
     */
    public function showWater() {
        //获取后台图片水印设置
        $water = D('SysConfig')->getCfgByModule('GY_IMAGEWATER',1);

        //import('ORG.Util.Image');
        $Image = new Image();
        C('SHOW_PAGE_TRACE', false);
        layout(false);

        $url = gzuncompress(urldecode(base64_decode($this->_get('u'))));

        //生成的带水印图片的保存路径
        $info = pathinfo($url);
        $waterpath = APP_PATH . $info['dirname'] . '/_water/' . $info['filename'] . '.' . $info['extension'];
        mkdir(APP_PATH . $info['dirname'] . '/_water/');
        $waterpath = str_replace('//', '/', $waterpath);

        if ($water['WATER_TYPE'] == 'image') {
            //图片格式水印 +++++++++++++++++++++++++++++++++++++++++++++++++++++
            //水印图片的路径
            $watername = APP_PATH . $water['WATER_IMAGE'];
            $watername = str_replace('//', '/', $watername);

            
            //保存缩略图缓存
            $Image::water(APP_PATH . $url, $watername, $waterpath, $water['WATER_ALPHA'] * 100,$water['WATER_POS']);
            //输出图像
            $Image::showImg($waterpath);
        } else {
            //文字格式 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            //水印文字
            $watertext = $water['WATER_TEXT'];

            $Image::showImg(APP_PATH . $url, $watertext, 10, 10, $water['WATER_ALPHA'] * 100,$water['WATER_POS'],$water['WATER_SIZE']);
        }
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