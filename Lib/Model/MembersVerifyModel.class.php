<?php
/**
 * 分销商用户模型
 * @package Model
 * @version 7.3
 * @author Zhangjiasuo
 * @date 2013-08-22
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersVerifyModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-22
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('members_verify',C('DB_PREFIX'),'DB_CUSTOM');
    }
}