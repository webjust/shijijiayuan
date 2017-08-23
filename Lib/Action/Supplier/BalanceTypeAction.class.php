<?php

/**
 * 后台结余款类型管理
 *
 * @subpackage BalanceType
 * @package Action
 * @stage 7.2
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-06-03
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BalanceTypeAction extends AdminAction{
    private $name;

    /**
     * 控制器初始化
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function _initialize() {
        parent::_initialize();
        $this->name = $this->_name;
        $this->setTitle(' - '.L('MENU6_5'));
    }
    
    /**
     * 默认控制器
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function index(){
        $this->redirect(U('Admin/BalanceType/pageList'));
    }
    
    /**
     * 结余款类型列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function pageList(){
        $this->getSubNav(7, 5, 10);
        $count = D($this->_name)->where()->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->show();
        $ary_data = D($this->_name)->order('`bt_orderby` DESC')->limit($obj_page->firstRow, $obj_page->listRows)->select();
        $this->assign("data",$ary_data);
        $this->assign("page",$page);
        $this->display();
    }
    
    /**
     * 启用/停用结余款类型
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function doStatusBalanceType(){
        $ary_post = $this->_post();
        if(!empty($ary_post['bt_code']) && isset($ary_post['bt_code'])){
            $ary_post['bt_status'] = ($ary_post['bt_status']) ? '1' : '0';
            $ary_result = D($this->_name)->where(array('bt_code'=>$ary_post['bt_code']))->data(array('bt_status'=>$ary_post['bt_status']))->save();
            $str = $ary_post['bt_status'] ? '启用' : '停用';
            if(FALSE != $ary_result){
                $this->success($str ."成功");
            }else{
                $this->error($str ."失败");
            }
        }else{
            $this->error("类型代码不存在，请重试...");
        }
    }
}