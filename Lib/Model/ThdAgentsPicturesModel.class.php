<?php

/**
 * 第三方店铺图片日志操作记录
 *
 * @package Model
 * @version 7.4.5
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-10-25
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdAgentsPicturesModel extends GyfxModel {
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     */

    public function __construct() {
        parent::__construct();
    }
	/**
     * 添加指定图片上传记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $data
     * @return array $res
     */
    public function addPicturesRecord($data) {
    	$res=$this->data($data)->add();
    	return $res;
    }
	/**
     * 获取指定图片上传记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
	 * @param array $ary_field
     * @param array $ary_where
     * @return array $res
     */
    public function getPicturesRecord($ary_where = array(),$ary_field='*') {
    	$res=$this->where($ary_where)->field($ary_field)->order($ary_order)->find();
    	return $res;
    }
	function mkdirs($dir, $mode = 0777) 
	{ 
		if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE; 
		$is_dir = $this->mkdirs(dirname($dir), $mode);
		if (!$is_dir) return FALSE; 
		return @mkdir($dir, $mode);
	} 
	/**
     * 上传商品详情里图片到淘宝图片空间
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-30
     * @param array $data 应用级输入参数 
     * @return array $result
     */
    public function uploadDetailPictureTop($ary_data,$top) {
    	$ary_result	= array('status'=>0,'err_msg'=>'','err_code'=>0,'data'=>array());

		$pic_name= substr( $ary_data['picture_path'] , strrpos($ary_data['picture_path'] , '/')+1 ); 
		//是否启用七牛图片存储
		$img = str_replace('//','/',APP_PATH . '/' . ltrim($ary_data['picture_path'],'/'));
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){
			if(!file_exists($img)){
				$this->mkdirs(dirname($img));
				$qn_pic_content = file_get_contents(D('QnPic')->picToQn('/' . ltrim($ary_data['picture_path'],'/')));
				file_put_contents($img, $qn_pic_content);
				unset($qn_pic_content);
			}
		}
		$ary_pics_data	= array(
			'picture_category_id'	=> 0,       //图片分类ID，设置具体某个分类ID或设置0上传到默认分类，只能传入一个分类
			'img'		=> '@' . $img,
			'image_input_title'	=> $pic_name  //包括后缀名的图片标题,不能为空
		);		
		//echo '@' . APP_PATH . '/' . ltrim($ary_data['picture_path'],'/');
		//dump($ary_pics_data);die();
    	$picture_upload_response	= $top->uploadPicture($ary_pics_data);
        $ary_pic_res=$picture_upload_response['picture_upload_response'];
		if(isset($ary_pic_res['picture'])) {
            $ary_local_data = array();
            $ary_local_data['top_picture_id']          = $ary_pic_res['picture']['picture_id'];
            $ary_local_data['top_picture_category_id'] = $ary_pic_res['picture']['picture_category_id'];
            $ary_local_data['top_picture_path']        = $ary_pic_res['picture']['picture_path'];
            $ary_local_data['top_title']               = $ary_pic_res['picture']['title'];
            $ary_local_data['top_sizes']               = $ary_pic_res['picture']['sizes'];
            $ary_local_data['top_pixel']               = $ary_pic_res['picture']['pixel'];
            $ary_local_data['top_status']              = $ary_pic_res['picture']['status'];
            $ary_local_data['top_deleted']             = $ary_pic_res['picture']['deleted'];
            $ary_local_data['top_created']             = $ary_pic_res['picture']['created'];
            $ary_local_data['top_modified']            = $ary_pic_res['picture']['modified'];
			$ary_local_data['ecfx_picture_path']       = $ary_data['picture_path'];
			$ary_local_data['top_shop_code']           = $ary_data['shop_code'];
            $int_res = $this->addPicturesRecord($ary_local_data);
            if(!$int_res){
                $ary_result['status']	= false;
                $ary_result['err_msg']	= "第三方店铺图片日志操作记录失败!";
            }else{
				$ary_result['data']['top_picture_path']= $ary_pic_res['picture']['picture_path'];
			}
			
		}else{
		    
		    $ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['msg'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
            $file_name=date('Ymd').'uploadDetailPictureTop.log';
            writeLog('request:'.var_export($ary_pics_data,1).'response:'.var_export($ary_result,1),$file_name);
            
		}
		return $ary_result;
    }
	
	/**
     * 替换商品详情里图片到淘宝图片空间
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-30
     * @param array $data 应用级输入参数 
     * @return array $result
     */
     public function replaceDetailPictureTop($ary_data,$top) {
    	$ary_pics_data	= array(
    		'picture_id'	=> $ary_data['picture_id'], //要替换的图片的id，必须大于0
    		'image_data'	=> '@' . APP_PATH . '/' . ltrim($ary_data['picture_path'],'/')//二进制流图片
    	);
		$ary_pics_data['image_data'] = str_replace('//','/',$ary_pics_data['image_data']);
    	$picture_replace_response	= $top->replacePicture($ary_pics_data);
        return $picture_replace_response;
     }
}