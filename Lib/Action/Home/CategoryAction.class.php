<?php
/* *
 * 前台商品主题分类列表页
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author Mickle <yangkewei@guanyisoft.com>
 * @date 2014-07-28
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CategoryAction extends HomeAction {
	
    /**
     * 初始化操作
     * @author Mickle <yangkewei@guanyisoft.com>
     * @date 2014-07-28
     */
    public function _initialize() {
        parent::_initialize();
    }
	
    /**
     * 五金列表页
     * @author Mickle
     * @date 2014-07-28
     */
    public function wj(){
		$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/wj.html';
        $this->display($tpl);
    }
    /**
     * 汽配列表页
     * @author Mickle
     * @date 2014-07-28
     */
    public function qp(){
		$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/qp.html';
        $this->display($tpl);
    }
	
	

}