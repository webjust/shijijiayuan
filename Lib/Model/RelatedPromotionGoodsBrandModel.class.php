<?php
/**
 * 购物车促销和商品品牌关联模型
 * @package Model
 * @version 7.8.9
 * @author wangguibin
 * @date 2015-11-16
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedPromotionGoodsBrandModel extends GyfxModel {
	/**
	 * 根据品牌id获取促销规则
	 * @author wangguibin
	 * @param array $ary_gg_id <p>商品品牌ID</p>
	 * @param bool $bl_distinct <p>是否去重</p>
	 * @param array $ary_field <p>字段</p>
	 * @date 2015-11-12
	 * @return array
	 */
	public function getProByGoodsBrands($ary_brand_id, $bl_distinct=false, $ary_field="*",$is_cache=0) {
		if($is_cache == 1){
			$obj_query = $this->distinct($bl_distinct)->where(array('brand_id' => array('IN', $ary_brand_id)))->field($ary_field);
			return D('Gyfx')->queryCache($obj_query,$type=null,600);
		}else{
			return $this->distinct($bl_distinct)->where(array('brand_id' => array('IN', $ary_brand_id)))->field($ary_field)->select();
		}
	}
}