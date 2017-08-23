<?php

/**
 * 管理员Model
 *
 * @package Model
 * @stage 7.1
 * @author czy<chenzongyao@guanyisoft.com>
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class AdminModel extends GyfxModel {
    /**
     * 根据管理员ID获取管理员信息
     * @author haophper
     * @param intger $int_uid
     * @param array $ary_field
     * @date 2013-8-19
     * @return Array
     */
    public function getAdminInfoById($int_uid, $ary_field="*") {
        return $this->where(array('u_id'=>$int_uid))->field($ary_field)->find();
        
    }
    
}