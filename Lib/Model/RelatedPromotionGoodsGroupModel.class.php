<?php
/**
 * 购物车促销和商品分组关联模型
 * @package Model
 * @version 7.4
 * @author zhanghao
 * @date 2013-9-9
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedPromotionGoodsGroupModel extends GyfxModel {
	/**
	 * 根据分组id获取促销规则
	 * @author zhanghao
	 * @param array $ary_gg_id <p>商品分组ID</p>
	 * @param bool $bl_distinct <p>是否去重</p>
	 * @param array $ary_field <p>字段</p>
	 * @date 2013-9-9
	 * @return array
	 */
	public function getProByGoodsGroups($ary_gg_id, $bl_distinct=false, $ary_field="*",$is_cache=0) {
		if($is_cache == 1){
			$obj_query = $this->distinct($bl_distinct)->where(array('gg_id' => array('IN', $ary_gg_id)))->field($ary_field);
			return D('Gyfx')->queryCache($obj_query,$type=null,600);
		}else{
			return $this->distinct($bl_distinct)->where(array('gg_id' => array('IN', $ary_gg_id)))->field($ary_field)->select();
		}
	}
}