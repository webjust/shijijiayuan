<?php

/**
 * 会员授权模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedAuthorizeMemberModel extends GyfxModel {
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-22
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 查询条件结果集
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-22
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @return array $res
     */
    public function GetAuthorizeData($condition = array(), $ary_field = '*',$group= '',$limit= '') {
        $res = $this->join('fx_related_authorize ON fx_related_authorize_member.al_id = fx_related_authorize.al_id')
                    ->field($ary_field)->where($condition)
                    ->select();
        return $res;
    }


}