<?php
/**
 * 后台自定义图片广告控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-05-08
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class AdAction extends AdminAction{
	
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
        $this->setTitle(' - '.L('MENU1_10'));
    }
    
    /**
     * 后台商品控制器默认页，需要重定向
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index(){
        $this->redirect(U('Admin/Ad/pageList'));
    }
    
    /**250319674
     * 自定义图片广告列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageList(){
    	$AdObj = D('Ad');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$list = $AdObj->field('n_id,n_name,n_position,n_aurl,n_imgurl,n_status,n_target,n_order')
						->order('n_position,n_order asc')
						//->where('n_status=1')
						->page($page_no,$page_size)
						->select();
        //$int_count = $AdObj->where('ul_status=1')->count();
        $int_count = $AdObj->count();
		$obj_page = new Page($int_count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->getSubNav(2,10,10);
        $this->display();
    }

    /**
     * 发布自定义导航
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageAdd(){
    	$this->getSubNav(2,10,20);
		$this->display();
    }
    
    /**
     * 新增自定义导航
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doAdd(){
		$AdObj = D('Ad');
		$ary_data = $this->_post();
		if(empty($ary_data['n_name'])){
			$this->error('自定义广告名称不能为空');
		}
    	if(empty($ary_data['n_aurl'])){
            $ary_data['n_aurl'] = 'javascript:void(0);';
			//$this->error('自定义广告地址不能为空');
    	}
    	$ary_data['n_create_time'] = date("Y-m-d H:i:s");
		$result = $AdObj->add($ary_data);
		if($result){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"新增自定义广告",'新增自定义广告为：'.$ary_data['n_name']));
			$this->success('操作成功',U('Admin/Ad/pageList'));
		}else{
			$this->error('新增自定义广告失败');
		}
    }
    
    /**
     * 编辑自定义导航页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageEdit(){
    	$this->getSubNav(2,10,20);
    	$nid = $this->_get('n_id');
    	$AdObj = D('Ad');
    	$adInfo = $AdObj->where('n_id=%d',array($nid))->find();
    	$this->assign('ad',$adInfo);
		$this->display('pageAdd');
    }

    /**
     * 编辑自定义导航
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doEdit(){
		$AdObj = D('Ad');
		$ary_data = $this->_post();
		if(empty($ary_data['n_name'])){
			$this->error('自定义广告名称不能为空');
		}
    	if(empty($ary_data['n_aurl'])){
            $ary_data['n_aurl'] = 'javascript:void(0);';
			//$this->error('自定义广告地址不能为空');
		}
		$ary_data['n_update_time'] = date("Y-m-d H:i:s");	
		$result = $AdObj->save($ary_data);
		if($result===false){		
			$this->error('修改自定义广告失败');
		}else{
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"修改自定义广告",'修改自定义广告为：'.$ary_data['n_name']));				
			$this->success('操作成功',U('Admin/Ad/pageList'));
		}
    }
    
    /**
     * 删除自定义导航
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doDel(){
		$nid = intval($this->_get('n_id'));
		$AdObj = D('Ad');
		$ntitle = $AdObj->where(array('n_id'=>$nid))->getField('n_name');
		//$AdObj->where('n_id='.$nid)->setField(array('n_status'=>0,'n_update_time'=>date('Y-m-d H:i:s')));
		$AdObj->where('n_id='.$nid)->delete();
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除自定义广告",'删除自定义广告为：'.$nid.'-'.$ntitle));				
		$this->success('操作成功',U('Admin/Ad/pageList'));
    }
    
}