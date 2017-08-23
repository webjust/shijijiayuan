<?php
/**
 * 购物车促销和商品类目关联模型
 * @package Model
 * @version 7.8.9
 * @author wangguibin
 * @date 2015-11-116
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedPromotionGoodsCategoryModel extends GyfxModel {
	/**
	 * 根据类目id获取促销规则
	 * @author wangguibin
	 * @param array $ary_gg_id <p>商品类目ID</p>
	 * @param bool $bl_distinct <p>是否去重</p>
	 * @param array $ary_field <p>字段</p>
	 * @date 2015-11-12
	 * @return array
	 */
	public function getProByGoodsCates($ary_cate_id, $bl_distinct=false, $ary_field="*",$is_cache=0) {
		if($is_cache == 1){
			$obj_query = $this->distinct($bl_distinct)->where(array('gc_id' => array('IN', $ary_cate_id)))->field($ary_field);
			return D('Gyfx')->queryCache($obj_query,$type=null,600);
		}else{
			return $this->distinct($bl_distinct)->where(array('gc_id' => array('IN', $ary_cate_id)))->field($ary_field)->select();
		}
	}
}