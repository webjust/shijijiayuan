<?php

/**
 * 会员等级模型层 Model
 * @package Model
 * @version 7.2
 * @author czy
 * @date 2012-12-13
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiMemberLevelModel extends GyfxModel {

    /**
     * 对象
     * @var obj
     */
    private $memberlevel;
	
	//private $warehouse_delivery_area;
    //private $warehouse_stock;
    /**
     * 构造方法
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2012-12-14
     */
     
     protected $item_map = array(
            'code'=>'ml_code',
            'name'=>'ml_name',
            'discount'=>'ml_discount',
            'status'=>'ml_status',
            'default'=>'ml_default',
            'created'=>'ml_create_time',
            'erp_guid'=>'ml_erp_guid'
    );
     
    protected $item_map_info = array(
            'code'=>'ml_code',
            'name'=>'ml_name',
            'discount'=>'ml_discount',
            'status'=>'ml_status',
            'default'=>'ml_default',
            'created'=>'ml_create_time',
            'erp_guid'=>'ml_erp_guid'
    );
    
    
    public function __construct() {
	
        parent::__construct();
		$this->memberlevel = M('members_level', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->cityregion = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->warehouse_delivery_area = M('warehouse_delivery_area', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->warehouse_stock = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM');
        
    }
    
    /**
     *增加会员等级 
     */
    public function AddMemberLevel($array_params=array()){
        $array_memberlevel_field = array(
            'code'=>'ml_code',
            'name'=>'ml_name',
            'discount'=>'ml_discount',
            'status'=>'ml_status',
            'default'=>'ml_default',
            'created'=>'ml_create_time',
            'erp_guid'=>'ml_erp_guid'
        );
        $ary_data = $this->parseFields($array_memberlevel_field,$array_params);
        $ary_data['ml_name'] = mb_convert_encoding($array_params['name'],"utf-8","gb2312");
        $ary_data['ml_create_time'] = date('Y-m-d H:i:s');
        $addresult = $this->memberlevel->add($ary_data);
        if($addresult){
            return array('created'=>$ary_data['ml_create_time'],'code'=>$ary_data['ml_code']);
        }else{
            return false;   
        }
    }
    
    /**
     *修改会员等级 
     */
    public function UpdateMemberLevel($array_params=array()){
        $array_memberlevel_field = array(
            'name'=>'ml_name',
            'discount'=>'ml_discount',
            'status'=>'ml_status',
            'default'=>'ml_default',
            'created'=>'ml_create_time',
        );
        $ary_data = $this->parseFields($array_memberlevel_field,$array_params);
        $ary_data['ml_name'] = mb_convert_encoding($array_params['name'],"utf-8","gb2312");
        $ary_data['ml_update_time'] = date('Y-m-d H:i:s');
        $addresult = $this->memberlevel->where(array('ml_code'=>$array_params['code']))->save($ary_data);
        if($addresult){
            return array('created'=>$ary_data['ml_update_time'],'code'=>$ary_data['ml_code']);
        }else{
            return false;   
        }
    }
    
    /**
     *查询会员等级 
     */
    public function MemberLevelGet($array_params=array()){
        $fields = $array_params['fields'];
        $ary_fields = explode(',',$fields);
        $ary_fields = $this->parseFieldsMapToReal($ary_fields);
        $field = implode(',',$ary_fields);
        if(empty($field)){
            $field = implode(',',$this->item_map);
        }
        $ary_where = '';
        $ary_order = '';
        $ary_page_no = 1;
        $ary_page_size = 10;
        $ary_orderby = '';
        if (isset($array_params["condition"]) && !empty($array_params["condition"])) {
            
			$ary_where = $array_params["condition"];
            $ary_where = mb_convert_encoding($ary_where,"utf-8","gb2312");
        }
        if (isset($array_params["page_no"]) && !empty($array_params["page_no"])) {
            
			$ary_page_no = $array_params["page_no"];
        }
        if (isset($array_params["page_size"]) && !empty($array_params["page_size"])) {
            
			$ary_page_size = $array_params["page_size"];
        }
        if (isset($array_params["orderby"]) && !empty($array_params["orderby"])) {
            
			if (isset($array_params["orderbytype"]) && !empty($array_params["orderbytype"])) {
            
		        $ary_orderby = $array_params["orderby"].' '.$array_params["orderbytype"];
            }else{
                $ary_orderby = $array_params["orderby"].' ASC';
            }
        }
        $mlresult = $this->memberlevel->field($field)
                                      ->where($ary_where)
                                      ->order($ary_orderby)
                                      ->limit(($ary_page_no-1)*$ary_page_size,$ary_page_size)
                                      ->select();
        $items=array();
        $this->_map = $this->item_map_info; 
        foreach($mlresult as &$item){
            $items[] = D('ApiMemberLevel')->parseFieldsMap($item);
        }
        return $items;
    }

	/**
	 * 处理字段映射
     * return string
	 */
	private function parseFieldsMaps($array_table_fields){
		$aray_fetch_field = array();
		foreach($array_table_fields as $field_name => $as_name){
			$aray_fetch_field[] = '`' . $as_name . '` as `' . $field_name . '`';
		}
		if(empty($aray_fetch_field)){
			return "";
		}
		return implode(',',$aray_fetch_field);
	}
    
    /**
	 * 处理字段映射
     * return array
	 */
	private function parseFields($array_table_fields,$array_client_fields){
		$aray_fetch_field = array();
		foreach($array_client_fields as $field_name => $as_name){
			if(isset($array_table_fields[$field_name]) && !empty($as_name)){
				$aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);
                
			}
		}
		if(empty($aray_fetch_field)){
			return null;
		}
		return $aray_fetch_field;
	}
	
}
