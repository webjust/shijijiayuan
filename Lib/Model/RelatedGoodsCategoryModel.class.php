<?php

/**
 * 商品分类模型
 *
 * @package Model
 * @version 7.8.9
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-11-16
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedGoodsCategoryModel extends GyfxModel {
	
	/**
	 * 根据商品的id获取所有相关分组
	 * @author wangguibin
	 * @param array $ary_gid <p>商品ID</p>
	 * @date 2015-11-16
	 * @return array
	 */
	public function getGoodsCatesByGid($ary_gid,$is_cache=0) {
		if(!is_array($ary_gid) || empty($ary_gid)) {
			return array();
		}				
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('related_goods_category',array('gc_id','g_id'), array('g_id' => array('IN', $ary_gid)), $ary_order=null,'',$ary_limit=null,60);			
		}else{
			return $this->where(array('g_id' => array('IN', $ary_gid)))->field(array('gc_id','g_id'))->select();
		}
	}

}