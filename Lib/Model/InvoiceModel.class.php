<?php

/**
 * 发票模型
 *
 * @package Model
 * @stage 7.0
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-25
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class InvoiceModel extends GyfxModel {
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 设置发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function update($data) {
        M('invoice_config',C('DB_PREFIX'),'DB_CUSTOM')->where(array('id' => 1))->save($data);
    }
    /**
     * 多的发票信息
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function get() {
        return M('invoice_config',C('DB_PREFIX'),'DB_CUSTOM')->find();
    }
}