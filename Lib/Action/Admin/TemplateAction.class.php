<?php

/**
 * 后台管理员节点管理
 *
 * @subpackage RoleNode
 * @package Action
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TemplateAction extends AdminAction {
    /**
     * 控制器初始化
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-04-10
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 模板预览
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-10
     */
    public function index(){
        $ary_request = $this->_request();
        $html = 'http://'.$_SERVER['HTTP_HOST'];
        //echo "<pre>"; print_r($ary_request); exit;
        $this->redirect($html,$ary_request);
    }

    /**
     * 模版可视编辑
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-31
     */
    public function pageEditTplTemm(){
        
    }
}