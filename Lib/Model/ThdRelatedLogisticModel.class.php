<?php

/**
 * 物流模版铺货新旧店内关系模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdRelatedLogisticModel extends GyfxModel {
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     */

    public function __construct() {
        parent::__construct();
    }
	
	/**
     * 获取相应店铺信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $ary_where
	 * @param array $ary_field
	 * @param array $ary_order
     * @return array $res
     */
    public function getLogisticInfo($ary_where = array(),$ary_field='*',$ary_order) {
        $res=$this->where($ary_where)->field($ary_field)->order($ary_order)->select();
        return $res;
    }

}