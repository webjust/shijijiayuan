<?php
/**
 * 工具类
 * @author Tom <helong@guanyisoft.com>
 * @date 2015-01-27
 */
Class ToolAction extends GyfxAction{

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 图片显示
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-01-27
	 */
	public function show(){
		$ary_get = $this->_get();
        // $id   = isset($ary_get['img_id'])   ? (int)$ary_get['img_id'] : 0;
        $path = isset($ary_get['o']) ? trim($ary_get['o']) : '';
        $w    = isset($ary_get['w'])    ? (int)$ary_get['w'] : 0;
        $h    = isset($ary_get['h'])    ? (int)$ary_get['h'] : 0;
        $path = !empty($path) ? getFullPictureWebPath($path) : '';
        if(!empty($path) && file_exists(FXINC.$path)) {
            $img_path = $path;
        // } else if($id) {//根据图片ID获取图片信息
        //     $img_info = $this->objTable('UploadFiles')->getDetail($id);
        //     $img_path = $img_info['f_relative_path'];
        } else {
            $img_path = getConf('DEFAULT_IMAGE_PATH');
        }
        if($w <=0 && $h<= 0) {
        	$pre_size = getConf('DEFAULT_IMAGE_SIZE');
            if($pre_size) {
                $ary_pre_size = explode(',', $pre_size);
                $w = (int)$ary_pre_size[0];
                $h = (int)$ary_pre_size[1];
            }
        }
        $image = FXINC.$img_path;
        $img_size = getimagesize($image);
        $w = ($w<=0) ? $img_size[0] : $w;
        $h = ($h<=0) ? $img_size[1] : $h;
	    if ($img_size == null) return false;
	    $type = str_replace('image/', '', $img_size['mime']);
        //处理缩略图路径，兼容历史数据
        if(strpos($image, '/source/')) {
            $thumbname = str_replace('/source/', '/thumb/'.($w.'x'.$h).'/', $image);
        } else {
            $thumbname = str_replace('/Uploads/', '/Uploads/images/thumb/'.($w.'x'.$h).'/', $image);
        }
        if(!file_exists($thumbname)) {
            $thumb_dir = dirname($thumbname);
            if(!file_exists($thumb_dir)) {
                @mkdir($thumb_dir, 0777, true);
            }
            import("@.ORG.Image");
            $obj_image = new Image();
            if($w <= 0 || $h <= 0) {
                $thumbname = $image;
            } else {
                $obj_image->thumb($image, $thumbname, $type, $w, $h, true);
            }
        }
        $imagefun = 'image'.$type;
        header('Content-type:image/'.$type);
        readfile($thumbname);
        die();
	}

	public function imageShow(){
		$url = $_GET['o'];
		$width = intval($_GET['w']);
		$height = intval($_GET['h']);
		// $basedir = dirname(__FILE__); // 基本目录
		$basedir = FXINC;
		$picfile = $basedir . $url;
		$thumbfile = str_replace('/o/', '/t/', $picfile);
		$thumbdir = pathinfo($thumbfile, PATHINFO_DIRNAME);
		is_dir($thumbdir) || mkdir($thumbdir, 0777, true);
		$thumbfile = preg_replace('/[\.png|\.gif|\.jpg|\.jpeg]+$/i', "_{$width}x{$height}" . '\\0', $thumbfile);//echo $thumbfile;exit;
		$data = $this->Resize($picfile, $thumbfile, $width, $height);
	}

	/**
	 * 图片裁剪
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2015-01-27
	 */
	private function Resize($src_file = "", $dest_file = "", $width = 480, $height = 360, $makesize = 1, $watermark = 0, $quantity = "80", $method = "gd2", $upfilepath = '') {
	    // echo $src_file,'<br />';
	    // echo $dest_file;die;
	    // get infomation of iamge
	    if(is_file($src_file)) $imginfo = getimagesize($src_file);
	    if ($imginfo == null)
	        return false;

	    $imgmime = str_replace('image/', '', $imginfo['mime']);

	    // GD can only handle JPG & PNG images
	    if (!in_array($imgmime, array('jpg', 'jpeg', 'png', 'gif', 'wbmp')) && ($method == 'gd1' || $method == 'gd2')) {
	        die("file is not be supported");
	        return false;
	    }
	    $srcWidth = $imginfo [0];
	    $srcHeight = $imginfo [1];

	    $dw = (int) ($srcWidth * $height / $srcHeight);
	    $dh = (int) ($srcHeight * $width / $srcWidth);
	    $destWidth = $width == 0 ? $dw : $width;
	    $destHeight = $height == 0 ? $dh : $height;

	    switch ($method) {
	        case "gd1" :
	            if (!function_exists('imagecreatefromjpeg')) {
	                die("PHP running on your server does not support the GD image library");
	            }
	            if ($imgmime == 'jpg' || $imgmime == 'jpeg')
	                $src_img = imagecreatefromjpeg($src_file);
	            elseif ($imgmime == 'png')
	                $src_img = imagecreatefrompng($src_file);
	            else
	                $src_img = imagecreatefromgif($src_file);
	            if (!$src_img) {
	                return false;
	            }
	            $dst_img = imagecreate($destWidth, $destHeight);
	            imagecopyresized($dst_img, $src_img, 0, 0, 0, 0, $destWidth, (int) $destHeight, $srcWidth, $srcHeight);
	            imagejpeg($dst_img, $dest_file, $quantity);
	            header("Content-type: image/" . $imgmime);
	            readfile($dest_file);
	            // $func = 'image' . $imgmime;
	            // $data = $func($dst_img);
	            // imagedestroy($src_img);
	            // imagedestroy($dst_img);
	            break;

	        case "gd2" :
	            // check if support GD2
	            if (!function_exists('imagecreatefromjpeg')) {
	                die("PHP running on your server does not support the GD image library");
	            }
	            if (!function_exists('imagecreatetruecolor')) {
	                die("PHP running on your server does not support GD version 2.x, please change to GD version 1.x on your method");
	            }
	            if ($imgmime == 'jpg' || $imgmime == 'jpeg')
	                $src_img = imagecreatefromjpeg($src_file);
	            elseif ($imgmime == 'png')
	                $src_img = imagecreatefrompng($src_file);
	            else
	                $src_img = imagecreatefromgif($src_file);
	            if (!$src_img) {
	                return false;
	            }
	            if ($makesize == 1) {
	                $dst_img = @imagecreatetruecolor($destWidth, $destHeight);
	                if ($destWidth > 0 && $destHeight > 0) {
	                    $x = 0;
	                    $y = 0;
	                    $nw = $srcWidth / $destWidth;
	                    $nh = $srcHeight / $destHeight;
	                    if ($nw > $nh) {
	                        $nnw = (int) ($nh * $destWidth);
	                        $nnh = $srcHeight;
	                        $x = (int) (($srcWidth - $nnw) / 2);
	                    } else {
	                        $nnw = $srcWidth;
	                        $nnh = (int) ($nw * $destHeight);
	                        $y = (int) (($srcHeight - $nnh) / 2);
	                    }
	                    @imagecopyresampled($dst_img, $src_img, 0, 0, $x, $y, $destWidth, $destHeight, $nnw, $nnh);
	                    // echo "<script>alert('".$destWidth."|".$destHeight."|".$srcWidth."|".$srcHeight."|".$nnw."|".$nnh."|".$x."|".$y."');</script>";
	                } else {
	                    $data = @imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
	                }
	            } else {
	                $radio = max(($srcWidth / $width), ($srcHeight / $height));
	                $destWidth = (int) ($srcWidth / $radio);
	                $destHeight = (int) ($srcHeight / $radio);
	                $dst_img = @imagecreatetruecolor($destWidth, $destHeight);
	                @imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
	            }
	            // echo $dest_file;die;
	            // @imagejpeg($dst_img, $dest_file, $quantity);
	            // @ob_end_clean();
	            header("Content-type: image/" . $imgmime);
	            readfile($dest_file);
	            // $func = 'image' . $imgmime;
	            // $func($dst_img);
	            // @imagedestroy($src_img);
	            // @imagedestroy($dst_img);
	            break;
	    }
	}

}