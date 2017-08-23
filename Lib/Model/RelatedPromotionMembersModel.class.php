<?php

/**
 * 促销会员关系模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedPromotionMembersModel extends GyfxModel {
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-22
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('related_promotion_members',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 根据用户信息获取促销规则
     * @author zhanghao
     * @param intger $int_mid <p>会员ID</p>
     * @param intger $int_ml_id <p>会员等级信息</p>
     * @param array $ary_mem_group <p> 会员分组信息<p>
     * @param bool $bl_distinct <p>是否去重</p>
     * @param array $ary_field <p>字段</p>
     */
    public function getProByMemInfo($int_mid, $int_ml_id, $ary_mem_group, $bl_distinct=false, $ary_field="*",$is_cache=0) {
    	$str_where = '';
    	if($int_mid > 0) {
    		$str_where .= 'm_id in ('.$int_mid.',-1'.')';
    	}
    	if($int_ml_id > 0) {
    		$str_where .= ' OR ml_id = '.$int_ml_id;
    	}
    	if(is_array($ary_mem_group) && !empty($ary_mem_group)) {
    		$str_where .= ' OR mg_id in ( '.implode(',', $ary_mem_group).')';
    	}
		if($is_cache == 1){
			$obj_query = $this->distinct($bl_distinct)->where($str_where)->field($ary_field);
			$ary_result = D('Gyfx')->queryCache($obj_query,$type=null,600);
		}else{
			$ary_result = $this->distinct($bl_distinct)->where($str_where)->field($ary_field)->select();
		}
        return $ary_result;
    }
}