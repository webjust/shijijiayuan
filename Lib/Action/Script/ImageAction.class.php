<?php
class ImageAction extends GyfxAction {

	
	public function gtThumb() {
		$image_path_dir = FXINC . '/Public/Uploads/';
		$this->listDir($image_path_dir);
	}
	
	public function listDir($image_path_dir) {

		if(is_dir($image_path_dir))
		{
			if ($dh = opendir($image_path_dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if($file == "." 
						|| $file == ".." 
							|| $file == '_thumb'
								|| $file == '_water') continue;
					
					if((is_dir($image_path_dir.$file)))
					{
							
						$this->listDir($image_path_dir.$file."/");
					}
					else
					{
						$type = strtolower(substr($file, strrpos($file, '.')));
						$imgType = array('.jpg','.png','.jpeg','.gif');
						if(in_array($type, $imgType)){
							$image_path = str_replace(FXINC, '', $image_path_dir.$file);

							$image_thumb = $this->showImage($image_path, 328, 328);						
							writeLog($image_thumb, 'image_thumb_'.date('Y_m_d').'.log');
						}                               
							
					}
				}
				closedir($dh);
			}
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
	public function showImage($url, $w = 0, $h = 0) {	
		
		//判断是否开启OSS，及图片提示是否是aliyuncs.com来源
		if(strpos($url,"aliyuncs.com")>=1 || (strpos($url,$_SESSION['OSS']['GY_OSS_CNAME_URL'])>=1) && !empty($_SESSION['OSS']['GY_OSS_CNAME_URL'])){
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
		$path = $url;
		$info = pathinfo($path);
		//读取图片信息
		$water = D('SysConfig')->getCfgByModule('GY_IMAGEWATER');
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
			if(!empty($water['THUMB_PIC_WIDTH'])){
				$w=$water['THUMB_PIC_WIDTH'];//后台设定宽度
			}
			if(!empty($water['THUMB_PIC_HEIGHT'])){
				$h=$water['THUMB_PIC_HEIGHT'];//后台设定宽度
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
				$code = base64_encode(urlencode(gzcompress($url)));
				$url_generated = U('Image/Images/showImage', array('u' => $code, 'w' => $w, 'h' => $h));
				return $url_generated;
			}
		}
	}
}