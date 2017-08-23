<?php

/**
 * 后台销货收款单功能相关控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2013-01-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SalesReceiptsAction extends AdminAction {

    /**
     * 本控制器初始化操作
     * @author Hcaijin <huangcaijin@guanyisoft.com>
     * @date 2013-05-29
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU6_1'));
    }

    /**
     * 默认控制器，需要重定向
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-11-13
     * @todo 暂时重定向到线下收款账户列表页，再等调整
     */
    public function index() {
        $this->redirect(u('Admin/SalesReceipts/lists'));
    }

    /**
     * 线下收款帐号列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     */
    public function lists() {
        $this->getSubNav(7, 1, 20);
        $sr_info = D('SalesReceipts');
        $data['list'] = $sr_info->select();
        echo D()->getLastSql();
        $this->assign($data);
        $this->display();
    }

   
}
