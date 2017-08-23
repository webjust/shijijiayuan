<?php

/**
 * 商品分组相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Mithern
 * @date 2013-08-21
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedGoodsGroupModel extends GyfxModel {
	/**
	 * 根据商品的id获取所有相关分组
	 * @author zhanghao
	 * @param array $ary_gid <p>商品ID</p>
	 * @date 2013-9-9
	 * @return array
	 */
	public function getGoodsGroupByGid($ary_gid,$is_cache=0) {
		if(!is_array($ary_gid) || empty($ary_gid)) {
			return array();
		}				
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('related_goods_group',array('gg_id','g_id'), array('g_id' => array('IN', $ary_gid)), $ary_order=null,'',$ary_limit=null,60);			
		}else{
			return $this->where(array('g_id' => array('IN', $ary_gid)))->field(array('gg_id','g_id'))->select();
		}
	}
}