<?php

/**
 * 后台城建项目资源再生数据
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author hcaijin 
 * @date 2013年 08月 28日 星期三 10:19:29 CST
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RecourcesAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU2_5'));
    }
    
    /**
    * 控制器默认方法，暂时重定向到资源列表
    * @author HCaijin
    * @date 2013年 08月 28日 星期三 10:29:30 CST
    */
    public function index(){
        $this->redirect(U('Admin/Recources/recourcesList'));
    }

    /**
     * 再生资源列表
     * @author HCaijin 
     * @date 2013-08-28
     */
    public function recourcesList(){
        $this->getSubNav(3,5,10);
        $where = array();
        $where['cr_status']=1;
        $content = trim($this->_post('rc_name'));
    	if($content){
    		$where['cr_name'] = array('LIKE', '%' . $content . '%');
    	}
        $count =  M('cj_recources',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->count();
        //echo M('cj_recources',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_data =  M('cj_recources',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->limit($page_no,$page_size)->order('`cr_create_time` DESC')->select();
        //echo  M('cj_recources',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        $this->assign("page", $page);
        $this->assign('ary_data',$ary_data);
        $this->display();
    }

    /**
     * 回收单位列表页面显示
     * @author HCaijin
     * @date 2013年 08月 28日 星期三 11:05:52 CST
     */
    public function recoveryUnitList(){
        $this->getSubNav(3,5,20);
        $count =  M('cj_recovery_unit',C('DB_PREFIX'),'DB_CUSTOM')->count();
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_data =  M('cj_recovery_unit',C('DB_PREFIX'),'DB_CUSTOM')->limit($page_no,$page_size)->order('`cru_name` DESC')->select();
        $this->assign("page", $page);
        $this->assign('ary_data',$ary_data);
        $this->display();
    }

    /**
     * 称重地磅列表页面显示
     * @author HCaijin
     * @date 2013年 08月 28日 星期三 11:05:52 CST
     */
    public function weighingMachineList(){
        $this->getSubNav(3,5,30);
        $count =  M('cj_weighing_machine',C('DB_PREFIX'),'DB_CUSTOM')->count();
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_data =  M('cj_weighing_machine',C('DB_PREFIX'),'DB_CUSTOM')->limit($page_no,$page_size)->order('`cwm_sn` DESC')->select();
        $this->assign("page", $page);
        $this->assign('ary_data',$ary_data);
        $this->display();
    }

    /**
     * 运输公司信息列表页面显示
     * @author HCaijin
     * @date 2013年 08月 28日 星期三 11:05:52 CST
     */
    public function carriersList(){
        $this->getSubNav(3,5,40);
        $count =  M('cj_carriers',C('DB_PREFIX'),'DB_CUSTOM')->count();
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_data =  M('cj_carriers',C('DB_PREFIX'),'DB_CUSTOM')->limit($page_no,$page_size)->order('`cc_name` DESC')->select();
        $this->assign("page", $page);
        $this->assign('ary_data',$ary_data);
        $this->display();
    }

    /**
     * 回收合同页面显示
     * @author HCaijin
     * @date 2013年 08月 28日 星期三 11:05:52 CST
     */
    public function agreementList(){
        $this->getSubNav(3,5,50);
        $count =  M('cj_agreement',C('DB_PREFIX'),'DB_CUSTOM')->count();
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_data =  M('cj_agreement',C('DB_PREFIX'),'DB_CUSTOM')->limit($page_no,$page_size)->order('`ca_sign_time` DESC')->select();
        $this->assign("page", $page);
        $this->assign('cj_agreement',$ary_data);
        $this->display();
    }

}
?>
