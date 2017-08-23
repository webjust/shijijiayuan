<?php

/**
 * 站内信模型
 *
 * @package Model
 * @version 7.6.1
 * @author Wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-08-12
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class StationLettersModel extends GyfxModel {

    /**
     * 站内信对象
     * @var obj
     */
    private $sl_obj;

	 
    /**
     * 构造方法
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-12
     */
    public function __construct() {
        parent::__construct();
		//站内信表
        $this->sl_obj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		//站内信关联会员表
        $this->rsl_obj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
    }
	
    /**
     * 新增站内信
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-12
     */	
	public function addStationLotters($sl_title,$sl_content,$m_id){
		if(empty($sl_title) || empty($sl_content) || empty($m_id)){
			return false;
		}
    	$letterData = array(
    		'sl_title' => $sl_title,
    		'sl_content' => $sl_content,
    		'sl_from_m_id' => -1,
    		'sl_parentid' => 0,
    		'sl_create_time' => date('Y-m-d H:i:s')
    	);
		$result = $this->sl_obj->add($letterData);
		if(!$result){
			return false;
		}else{
			//更新会员关联表
			$insert_pn_mid = array('sl_id'=>$result,'rsl_to_m_id'=>$m_id,'rsl_is_look'=>0,'rsl_status'=>1,'rsl_read_nums'=>1);
			$m_result = $this->rsl_obj->add($insert_pn_mid);
			if(!$m_result){
				return false;
			}else{
				return true;
			}
		}
	}
	

}