<?php

/**
 * 会员组关联模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedMembersGroupModel extends GyfxModel {

	/**
	 * 根据会员id获取会员分组信息
	 * @author zhanghao
	 * @param intget $int_mid <p>会员id</p>
	 * @date 2013-9-9
	 * @return array
	 */
	public function getMemGroupsByMid($int_mid,$is_cache=0) {
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('related_members_group','mg_id', array('m_id'=>$int_mid), $ary_order=null,$ary_group=null,$ary_limit=null,600);
		}else{
			return $this->where(array('m_id'=>$int_mid))->field('mg_id')->select();
		}
	}
}