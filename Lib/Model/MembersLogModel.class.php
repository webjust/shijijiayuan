<?php
/**
 * 用户资料日志模型
 * @package Model
 * @version 7.3
 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
 * @date 2013-08-07
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersLogModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-07
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('members_log',C('DB_PREFIX'),'DB_CUSTOM');
    }

}
