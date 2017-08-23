<?php
/**
 * 系统配置模型
 * @package Model
 * @version 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-12
 */
class SysConfigModel extends GyfxModel{

    /**
     * 从系统配置表中取出邮件发送相关配置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     * @return array
     * @example array('GY_SMTP_AUTH'=>1,'GY_SMTP_FROM=>'guanyitest@163.com');
     */
    public function getEmailCfg(){
        return $this->getCfgByModule('GY_SMTP',C('MEMCACHE_STAT'));
    }
	
	/**
     * 获取跨境贸易相关配置
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-03-19
     * @return array
     */
    public function getForeignOrderCfg(){
        return $this->getCfgByModule('GY_FOREIGN_ORDER',C('MEMCACHE_STAT'));
    }
	/**
     * 获取OSS相关配置
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-02
     * @return array
     */
    public function getOssCfg(){
        return $this->getCfgByModule('GY_OSS',C('MEMCACHE_STAT'));
    }
    /**
     * 从系统配置表中取出模块相关配置
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-1-9
     * @return array
     * @example array('GY_SMTP_AUTH'=>1,'GY_SMTP_FROM=>'guanyitest@163.com');
     */
    public function getCfgByModule($module_name,$is_cache = 0){
		if($is_cache != '1'){
			$result = $this->field(array('sc_key','sc_value'))->where(array('sc_module'=>$module_name))->select();
		}else{
			$result = D('Gyfx')->selectAllCache('sys_config','sc_key,sc_value',array('sc_module'=>$module_name));
		}
        $return = array();
        foreach($result as $v){
            $return[$v['sc_key']] = $v['sc_value'];
        }
        return $return;
    }
	
    /**
     * 删除从系统配置表中取出模块相关配置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-04-28
     * @return array
     * @example array('GY_SMTP_AUTH'=>1,'GY_SMTP_FROM=>'wangguibin@guanyisoft.com');
     */
    public function deleteCfgByModule($module_name){
		return D('Gyfx')->deleteAllCache('sys_config','sc_key,sc_value',array('sc_module'=>$module_name));
    }	
	
    /**
     * 保存配置
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-1-9
     * @param string $module 配置项分组
     * @param string $key 配置项
     * @param string $value 配置值
     * @param string $desc 配置项描述，为空则不修改描述。修改配置项时一般不用修改描述的
     */
    public function setConfig($module,$key,$value,$desc=''){
    	$cfg = $this->where(array('sc_module'=>$module,'sc_key'=>$key))->find();
    	if($cfg){
    		$data = array(
    			'sc_id' => $cfg['sc_id'],
    			'sc_module' => $module,
    			'sc_key' => $key,
    			'sc_value' => $value,
    			'sc_value_desc' => $desc,
    			'sc_status' => 1,
    			'sc_update_time' => date('Y-m-d H:i:s'),
    		);
            if(empty($desc)){
                unset($data['sc_value_desc']);
            }
    		$res = $this->data($data)->save();
			return $res;
    	}else{
    		$data = array(
    			'sc_module' => $module,
    			'sc_key' => $key,
    			'sc_value' => $value,
    			'sc_value_desc' => $desc,
    			'sc_status' => 1,
    			'sc_create_time' => date('Y-m-d H:i:s'),
    		);
            if(empty($desc)){
                unset($data['sc_value_desc']);
            }
    		return $this->data($data)->add();
    	}
    }
    /**
     * 获取数据中的配置项
     * @author Jerry
     * @param string $str_module 模块
     * @param string $str_key 键值
     * @param string $str_fileds 要获取的数据字段
     * @param string $str_fileds 非空时使用分页
     */
    public function getConfigs($str_module=null, $str_key=null, $str_fileds=null, $str_limit=null,$is_cache=0) {
        $ary_result = array();
        if(empty($str_fileds)) {
            $str_fileds = 'sc_module,sc_key,sc_value';
        }
        $ary_where = array();
        //获取模块内配置
        if(!empty($str_module)) {
            //获取整个模块的配置
            $ary_where['sc_module'] = $str_module;
            if(!empty($str_key)) {
                $ary_where['sc_key'] = $str_key;
            }
        }
        if(empty($str_limit)) {
			if($is_cache == '1'){
				$ary_tmp_result = D('Gyfx')->selectAllCache('sys_config',$str_fileds, $ary_where, $ary_order=null,$ary_group=null,$ary_limit=null);
			}else{
				$ary_tmp_result = $this->where($ary_where)->field($str_fileds)->select();
			}
            //$ary_tmp_result = $this->where($ary_where)->field($str_fileds)->select();
        } else {
			if($is_cache == '1'){
				$ary_tmp_result = D('Gyfx')->selectAllCache('sys_config',$str_fileds, $ary_where, $ary_order=null,$ary_group=null,$str_limit);
			}else{
				$ary_tmp_result = $this->where($ary_where)->field($str_fileds)->limit($str_limit)->select();
			}
            //$ary_tmp_result = $this->where($ary_where)->field($str_fileds)->limit($str_limit)->select();
        }
        if(is_array($ary_result) && !empty($ary_tmp_result)) {
            foreach ($ary_tmp_result as $ary_c) {
                $ary_result[$ary_c['sc_key']] = $ary_c;
            }
        }
        return $ary_result;
    }

	/**
	 * @param $sc_key
	 * @param string $str_module
	 * @param bool $bool
	 * @return null|string
	 */
	public function getConfigValueBySckey($sc_key, $str_module='', $bool = false) {

		if(!empty($sc_key)) {
			$ary_where['sc_key'] = $sc_key;
		}else{
			return null;
		}
		if($str_module) {
			$ary_where['sc_module'] = $str_module;
		}
		return $this->where($ary_where)->getField('sc_value', $bool);

	}
    
    /**
     * 取sys_config里面的某个键值，如果不存在则新插入一条
     */
    public function getCfg($sc_module, $sc_key, $sc_value = '默认值', $sc_value_desc = '描述文字') {
        $ary_result = array();
        $str_fileds = 'sc_module,sc_key,sc_value';
        $array_where['sc_module'] = $sc_module;
        $array_where['sc_key'] = $sc_key;
        $ary_tmp_result = $this->where($array_where)->field($str_fileds)->select();
        //判断是否存在
        if (!$ary_tmp_result) {

            //不存在则插入一条记录
            $array_insert = array("sc_module"=>$sc_module,'sc_key'=>$sc_key,'sc_value'=>$sc_value,'sc_value_desc'=>$sc_value_desc,'sc_status'=>1,'sc_create_time'=>date('Y-m-d H:i:s'));
            //echo "<pre>";print_r($array_insert);exit;
            $last_insert_id = $this->add($array_insert);
            $ary_tmp_result = $this->where(array('sc_id'=>$last_insert_id))->field($str_fileds)->select();
        }
        if(is_array($ary_result) && !empty($ary_tmp_result)) {
            foreach ($ary_tmp_result as $ary_c) {
                $ary_result[$ary_c['sc_key']] = $ary_c;
            }
        }
        return $ary_result;
    }
	
	/**
	 * 商品图片水印设置信息获取
	 * 前台所有商品图片水印设置的信息请调用此方法获取！！务必调用此方法获取！！！
	 * 此方法用于读取和初始化商品资料图片的配置信息
	 *
	 * 此方法返回一个商品图片设置的信息
	 *
	 * @author Mithern<sunguangxu@guanyisoft.com>
	 * @version 1.0
	 * @date 2013-07-27
	 */
	public function itemImageConfigInfoGet(){
		$array_config_info = $this->where(array('sc_module'=>"ITEM_IMAGE_CONFIG"))->getField('sc_key,sc_value');
		//定义初始化设置
		//1. 水印开关是否开启，默认不开启，0表示不开启，1表示文字水印，2表示图片水印
		if(!isset($array_config_info["WATER_SWITCH"]) || !in_array($array_config_info["WATER_SWITCH"],array(0,1,2))){
			$array_config_info["WATER_SWITCH"] = 0;
		}
		
		//2. 水印位置：九宫格的位置：顶部（左、中、右）、中部（左、中、右）、底部（左、中、右）
		//水印所在位置定义，用于页面显示使用
		$array_water_locate = implode(',','TOP_LEFT,TOP_CENTER,TOP_RIGHT,MIDDLE_LEFT,MIDDLE_CENTER,MIDDLE_RIGHT,BOTTOM_LEFT,BOTTOM_CENTER,BOTTOM_RIGHT');
		if(!isset($array_config_info["WATER_LOCATE"]) || !in_array($array_config_info["WATER_LOCATE"],$array_water_locate)){
			//默认位置：底部居右
			$array_config_info["WATER_LOCATE"] = 'BOTTOM_RIGHT';
		}
		
		//图片水印，需要设置水印图片，透明度
		if(!isset($array_config_info["WATER_IMAGE"]) || "" == $array_config_info["WATER_IMAGE"]){
			$array_config_info["WATER_IMAGE"] = "";
		}
		
		//图片水印透明度，默认100，且必须是数字
		if(!isset($array_config_info["WATER_IMAGE_TRANSPARENCY"]) || "" == $array_config_info["WATER_IMAGE_TRANSPARENCY"] || !is_numeric($array_config_info["WATER_IMAGE_TRANSPARENCY"])){
			$array_config_info["WATER_IMAGE"] = 100;
		}
		
		//文字水印，需要设置水印文字，水印文字字体，水印文字字体大小，水印文字颜色
		//水印文字，默认值：EC-FX
		if(!isset($array_config_info["WATER_WRITING"]) || "" == $array_config_info["WATER_WRITING"]){
			$array_config_info["WATER_WRITING"] = '自豪的采用EC-FX';
		}
		
		//水印文字字体，默认宋体
		if(!isset($array_config_info["WATER_WRITING_FONT"]) || "" == $array_config_info["WATER_WRITING_FONT"]){
			$array_config_info["WATER_WRITING_FONT"] = 'SimSun.ttc';
		}
		
		//水印文字字体大小，默认10
		if(!isset($array_config_info["WATER_WRITING_SIZE"]) || "" == $array_config_info["WATER_WRITING_SIZE"]){
			$array_config_info["WATER_WRITING_SIZE"] = 10;
		}
		
		//水印文字字体颜色，默认红色
		if(!isset($array_config_info["WATER_WRITING_FONTCOLOR"]) || "" == $array_config_info["WATER_WRITING_FONTCOLOR"]){
			$array_config_info["WATER_WRITING_FONTCOLOR"] = '#FF0000';
		}
		
		//返回商品图片水印配置信息
		return $array_config_info;
	}
	
	/**
     * 从系统配置表中取出SMS发送相关配置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     * @return array
     * @example array('GY_SMTP_AUTH'=>1,'GY_SMTP_FROM=>'guanyitest@163.com');
     */
    public function getSmsCfg($is_cache = 0){
        return $this->getCfgByModule('GY_SMS',$is_cache);
    }
	
}
