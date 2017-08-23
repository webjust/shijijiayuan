<?php

/**
 * 会员优惠券模型层 Model
 * @package Model
 * @version 7.6
 * @author czy
 * @date 2014--07-09
 * @license MIT
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiMemberCouponModel extends GyfxModel {

    /**
     * 对象
     * @var obj
     */
    private $coupon;
	
    /**
     * 构造方法
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-09
     */
     
     protected $item_map = array(
            'coupon_name'=>'c_name',
            'sn'=>'c_sn',
            'start_time'=>'c_start_time',
            'end_time'=>'c_end_time',
            'is_use'=>'c_is_use',
            'memo'=>'c_memo',
            'money'=>'c_money',
            'condition_money'=>'c_condition_money',
			'member_name'=>'m_name',
            'user_id'=>'c_user_id',
            'used_id'=>'c_used_id',
            'order_id'=>'c_order_id',	
			'create_time'=>'c_create_time'			
    );
     
    protected $item_map_info = array(
            'coupon_name'=>'c_name',
            'sn'=>'c_sn',
            'start_time'=>'c_start_time',
            'end_time'=>'c_end_time',
            'is_use'=>'c_is_use',
            'memo'=>'c_memo',
            'money'=>'c_money',
            'condition_money'=>'c_condition_money',
			'member_name'=>'m_name',
            'user_id'=>'c_user_id',
            'used_id'=>'c_used_id',
            'order_id'=>'c_order_id',	
			'create_time'=>'c_create_time'	
    );
    
    
    public function __construct() {
        parent::__construct();
		$this->coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM');
    }
    
    
    /**
     *查询会员优惠券
     */
    public function MemberCouponGet($array_params=array()){
        $fields = $array_params['fields'];
        $ary_fields = explode(',',$fields);
        $ary_fields = $this->parseFieldsMapToReal($ary_fields);
        $field = implode(',',$ary_fields);
        if(empty($field)){
            $field = implode(',',$this->item_map);
        }
        $ary_where = array();
        $ary_order = '';
        $ary_page_no = 1;
        $ary_page_size = 20;
        $ary_orderby = '';
		if (isset($array_params["condition"]) && !empty($array_params["condition"])) {
			$ary_where['_string'] = mb_convert_encoding($array_params["condition"],"utf-8","gb2312");
			//dump($this->item_map);die();
			foreach($this->item_map as $key=>$val){
				if(strstr($ary_where['_string'],$key))
				{
					 $ary_where['_string'] = str_replace($key,$val,$ary_where['_string']);
				}	
			}
		}
        if (isset($array_params["page_no"]) && !empty($array_params["page_no"])) {
            
			$ary_page_no = $array_params["page_no"];
        }
        if (isset($array_params["page_size"]) && !empty($array_params["page_size"])) {
            
			$ary_page_size = $array_params["page_size"];
        }
		foreach($this->item_map as $key=>$val){
				if(strstr($val,$array_params["orderby"]))
				{
					 $array_params["orderby"] = str_replace($key,$val,$array_params["orderby"]);
				}	
		}
		//dump($array_params["orderby"]);die();
		if (isset($array_params["orderby"]) && !empty($array_params["orderby"])) {
			if (isset($array_params["orderbytype"]) && !empty($array_params["orderbytype"])) {

				$ary_orderby = $array_params["orderby"].' '.$array_params["orderbytype"];
			}else{
				$ary_orderby = $array_params["orderby"].' ASC';
			}
		}
        $mlresult = $this->coupon->field($field)->join('fx_members on c_user_id=m_id ')
                                      ->where($ary_where)
                                      ->order($ary_orderby)
                                      ->limit(($ary_page_no-1)*$ary_page_size,$ary_page_size)
                                      ->select();
									  //dump($this->coupon->getLastSql());exit;
        $items=array();
        $this->_map = $this->item_map_info; 
        foreach($mlresult as &$item){
            $items[] = D('ApiMemberCoupon')->parseFieldsMap($item);
        }
        return $items;
    }

	/**
	 * 处理字段映射
     * return string
	 */
	public function parseFieldsMaps($array_table_fields){
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
