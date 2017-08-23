<?php

/**
 * 根据货品获取属性 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsSpecDetailModel extends GyfxModel {
     /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
	
	/**
	 * 获取可搜索属性
	 * @param int $tid 商品类型
	 * @param string $field 返回字段
	 * @return array 
	 */
    public function getIsSearchSpec($tid, $field='*') {
        $ary_join = array(
            'join fx_goods_spec as gs on gs.gs_id=gsd.gs_id',
            'join fx_related_goods_type_spec as rgt on rgt.gs_id = gsd.gs_id'
        );
        $spec_list = $this->alias('gsd')
            ->field($field)
            ->join($ary_join)
            ->where(array(
                'gs.gs_is_search'=>1,
				'gsd.gsd_status'=>1,
                'rgt.gt_id' => $tid
                ))
            ->order('gsd_order asc')
            ->select();
        //echo $this->getLastSql();die;
        return $spec_list;
    }   
}