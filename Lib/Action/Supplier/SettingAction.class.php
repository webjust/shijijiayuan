<?php

/**
 * 后台设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SettingAction extends AdminAction{
    /**
     * 默认控制器重定向
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     * @todo 暂时重定向到Email连接设置
     */
    public function index(){
        $this->redirect(U('Admin/Email/pageSmtp'));
    }
}