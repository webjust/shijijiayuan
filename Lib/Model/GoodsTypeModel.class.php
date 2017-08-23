<?php

/**
 * 商品类型模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsTypeModel extends GyfxModel {
	
	/**
	 * 获取相应商品类型
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 * @param array $ary_where
	 * @return array $res
	 */
	public function getGoodsType($ary_where = array(),$ary_field='*',$ary_order="gt_id asc") {
		return $this->where($ary_where)->field($ary_field)->order($ary_order)->select();
	}

	/**
	 * 新增商品类型
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 * @param array $ary_where
	 * @return array $res
	 */
	public function addGoodsType($ary_data) {
		$ary_data['gt_create_time'] = date('Y-m-d H:i:s');
		$ary_data['gt_update_time'] = date('Y-m-d H:i:s');
		return $this->data($ary_data)->add();
	}
		
}