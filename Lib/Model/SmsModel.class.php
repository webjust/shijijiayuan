<?php

/**
 * 短信相关模型层 Model
 * @package Model
 * @version 7.6.1
 * @author wangguibin
 * @date 2013-12-06
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class SmsModel extends GyfxModel {
	
    /**
     * 构造方法
     * @author wangguibin
     * @date 2014-08-04
     */
    public function __construct() {
        parent::__construct();
    }
    
   	/**
	  * 短信模板内容编辑
	  * @author wangguibin
	  * @param ary $data 返回短信模板信息
	  * @date 2014-08-04
	  */
    public function doEditTemp($data){
		$temp_id=$data['id'];
		unset($data['id']);
		$res = D('SmsTemplates')->where(array('id' => $temp_id))->save($data);
        return $res;
    }
	
	/**
	  * 获取短信模板
	  * @author wangguibin
	  * @param ary $data 返回短信模板信息
	  * @date 2014-08-04
	  */
    public function getTemp($data){
		$res = D('SmsTemplates')->where(array('id' => $data['id']))->find();
		return $res;
    }
    
	
	
}
?>
