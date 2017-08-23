<?php
/**
 * 日志操作记录控制器
 *
 * @subpackage Operation
 * @package Action
 * @stage 7.2
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-08-29
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OperationAction extends AdminAction{
    /**
     * 默认控制器
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-29
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 后台操作记录列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-29
     */
    public function index(){
        $this->redirect(U('Admin/Operation/pageList'));
    }
    
    public function pageList(){
        $this->getSubNav(8,9,10);
        $ary_get = $this->_get();
        $operation = D("LogOperation");
        $where = array();
        if(!empty($ary_get['starttime'])){
            if(!empty($ary_get['endtime'])){
                $where['datetime'] = array('BETWEEN',array($ary_get['starttime'],$ary_get['endtime']));
            }else{
                $where['datetime'] = array('EGT',$ary_get['starttime']);
            }
            
        }else{
            if(!empty($ary_get['endtime'])){
                $where['datetime'] = array('ELT',$ary_get['endtime']);
            }
        }
        if(!empty($ary_get['val']) && isset($ary_get['val'])){
            switch ($ary_get['field']){
                case 'author':
                    $where['author'] = array('like','%'.trim($ary_get['val']).'%');
                    break;
                case 'action':
					$where['action'] = array('like','%'.trim($ary_get['val']).'%');
                    break;
                case 'content':
                    $where['content'] = array('like','%'.trim($ary_get['val']).'%');
                    break;
            }
        }
		if(empty($where)){
			$start_time = date("Y-m-d H:i:s",strtotime("-1 month"));
			$where['datetime'] = array('EGT',$start_time);
		}
		
		
        $count = $operation->where($where)->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_data = $operation->where($where)->limit($obj_page->firstRow, $obj_page->listRows)->order('id desc')->select();
        $this->assign("data", $ary_data);
        $this->assign("page", $page);
		$this->assign("filter",$ary_get);
        $this->display();
    }
}