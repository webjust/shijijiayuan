<?php
/**
 * 后台菜单控制
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-10-21
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MenusAction extends AdminAction{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 后台菜单控制器默认页，需要重定向
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-21
     */
    public function index(){
        $this->redirect(U('Admin/Menus/pageList'));
    }
    
    public function pageList(){
        $menus = D('Menus');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
                $where=array();
                $where['fid'] = array('NEQ','0');
                $where['group'] = "Admin";
		$list = $menus->order('toporder asc')->where($where)->page($page_no,$page_size)->select();
                $count = $menus->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('data', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->getSubNav(8,8,10);
        $this->display();
    }
    
    public function getUcenterMenus(){
        $menus = D('Menus');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
                $where=array();
                $where['fid'] = array('NEQ','0');
                $where['group'] = "Ucenter";
		$list = $menus->order('toporder asc')->where($where)->page($page_no,$page_size)->select();
                $count = $menus->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('data', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->getSubNav(8,8,20);
        $this->display();
    }
    
    /**
     * 启用/停用菜单
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function doStatusMenus(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && isset($ary_post['id'])){
            $ary_result = M('Menus',C('DB_PREFIX'),'DB_CUSTOM')->where(array('id'=>$ary_post['id']))->data(array('mstatus'=>$ary_post['mstatus']))->save();
//            echo "<pre>";print_r(M('Menus',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
            $str = $ary_post['mstatus'] ? '启用' : '停用';
            if(FALSE !== $ary_result){
                $this->success($str ."成功");
            }else{
                $this->error($str ."失败");
            }
        }else{
            $this->error("菜单不存在，请重试...");
        }
    }
}