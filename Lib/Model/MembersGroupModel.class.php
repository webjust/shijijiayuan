<?php

/**
 * 用户组模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersGroupModel extends GyfxModel {

	/**
	 * 根据分组ID获取分组信息
	 * @author zhanghao
	 * @param intger $int_mg_id <p>分组ID</p>
	 * @param array $ary_field <p>所需字段</p>
	 * @date 2013-9-9
	 * @return array
	 */
	public function getMemGroupInfoById($int_mg_id, $ary_field="*",$is_cache=0) {
		if($is_cache == 1){
			return D('Gyfx')->selectOneCache('members_group',$ary_field, array('mg_id'=>$int_mg_id), $ary_order=null,$ary_group=null,$ary_limit=null,600);
		}else{
			return $this->where(array('mg_id'=>$int_mg_id))->field($ary_field)->find();
		}
	}
}