<?php
/**
 * 404错误页面
 *
 * @stage 7.5
 * @package Action
 * @subpackage Home
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-08-28
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class WrongAction extends WapAction{
    /**
     * 控制器初始化
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-28
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 404错误页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-29
     */
    public function Index(){
        header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
        $tpl = './Public/Tpl/' . CI_SN . '/' . $this->wapDir . TPL . '/404.html';
        $this->assign('page_title', "404-对不起！您访问的页面不存在");
        $this->display($tpl);
    }
}