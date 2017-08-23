<?php
class GlobalAction extends HomeAction{

    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function _initialize() {
        parent::_initialize();
    }

    public function index()
    {
        $this->setTitle('全球精选');

        $ApiUtil = D("ApiUtil");
        $goodslist = $ApiUtil->GetGlobalGoodsList($_REQUEST['keywords']);
        $this->assign("goodslist",$goodslist);
        $this->assign("keywords",$_REQUEST['keywords']);

        $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/global-v2.html';
        $this->assign("v",'-v2');
        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
        $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->display($tpl);
    }
}