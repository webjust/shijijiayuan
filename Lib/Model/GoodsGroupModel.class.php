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
class GoodsGroupModel extends GyfxModel {
	//查询所有的分组
	public function getGoodsGroup(){
		$ary_group = $this->order('gg_order asc')->select();
		return $ary_group;
	}
	/**
	 * 根据分组id获取分组信息
	 * @author zhanghao
	 * @param intger $int_gg_id <p>分组ID</p>
	 * @param array $ary_field <p>获取的字段</p>
	 * @date 2013-9-9
	 * @return array
	 */
	public function getGoodsGroupById($int_gg_id, $ary_field="*",$is_cache=0) {
		if($is_cache == 1){
			return D('Gyfx')->selectOneCache('goods_group',$ary_field, array('gg_id'=>$int_gg_id), $ary_order=null,600);
		}else{
			return $this->where(array('gg_id'=>$int_gg_id))->field($ary_field)->find();
		}
	}

	public function getGoodsGroupByIds($int_gg_ids, $ary_field="*",$is_cache=0) {
        $int_gg_ids = implode(',',$int_gg_ids);
		return $this->where(array('gg_id'=>array('in',$int_gg_ids)))->field($ary_field)->select();
	}
}




