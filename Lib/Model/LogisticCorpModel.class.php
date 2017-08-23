<?php

/**
 * 物流公司模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class LogisticCorpModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-08
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 根据条件找出物流信息
     * @param $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return array 
     * @date 2013-07-08
     */
    public function getLogisticInfo($ary_where=array(),$ary_field=''){
        $ary_res = $this->field($ary_field)
                    ->join('fx_logistic_type on fx_logistic_type.lc_id = fx_logistic_corp.lc_id')
                    ->where($ary_where)->find();
        return $ary_res;
    }
}