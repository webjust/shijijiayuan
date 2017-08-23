<?php
/**
 * 后台默认控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class IndexAction extends SupplierAction{
    
     public function _initialize() {
         parent::_initialize();
     }
    
    /**
     * 后台默认控制器默认页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-04
     */
    public function index(){
        echo 'f';
        //exit();
        //$tpl = "Tpl/Supplier/Index/index.html";
		//$this->display($tpl);
        $this->display();
    }
}