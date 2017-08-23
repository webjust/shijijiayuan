<?php

/**
 * 前台App模版首页
 *
 * @package Action
 * @subpackage App
 * @stage 7.8.5
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2015-07-23
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class IndexAction extends AppAction {

    protected $dir = '';

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 客户模版默认首页
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-07-23
     * @version 7.8.5
     */
    public function index() {

        $ary_request = $this->_request();
		$ary_request['index']=1;//判断是否为首页
        $this->setTitle('首页','TITLE_INDEX','DESC_INDEX','KEY_INDEX');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
			$tpl = $this->app_theme_path . 'mobile_index.html';
        } else {
            $tpl = $this->app_theme_path . 'mobile_index.html';
        }

        $headerTpl = $this->app_theme_path . 'header.html';
        if(!file_exists($headerTpl)){
            $headerTpl = $this->app_theme_path . 'header.html';
        }else{
            $headerTpl = $this->app_theme_path . 'header.html';
        }

        $footerTpl = $this->app_theme_path . 'footer.html';
        if(!file_exists($footerTpl)){
            $footerTpl = $this->app_theme_path . 'footer.html';
        }else{
            $footerTpl = $this->app_theme_path . 'footer.html';
        }
		
        $this->assign("ary_request",$ary_request);
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $domain = $_SERVER['SERVER_NAME'];
        $this->assign("domain",$domain);
        $this->display($tpl);
    }
}
