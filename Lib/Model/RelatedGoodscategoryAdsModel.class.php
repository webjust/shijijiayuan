<?php

/**
 * 类目和类目广告图片关联模型
 *
 * @package Model
 * @version 783.2
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2015-05-20
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedGoodscategoryAdsModel extends GyfxModel {

    public function _initialize() {
        parent::_initialize();
    }
	
	public function getListByCid($where=array(), $ary_field="*",$orders="",$is_cache=0) {
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('related_goodscategory_ads',$ary_field, $where, $orders,$ary_group=null,$ary_limit=null,$time=3600);
		}else{
			return $this->where($where)->field($ary_field)->order($orders)->select();
		} 
    }
}