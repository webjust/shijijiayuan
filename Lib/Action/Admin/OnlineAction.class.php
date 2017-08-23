<?php
/**
 * 后台在线客服控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-9
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OnlineAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU1_1'));
    }
    /**
     * 在线客服列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-09
     */
    public function pageList(){
    	$noticeObj = D('OnlineService');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $noticeObj->field('o_id,o_name,o_type,o_status,fx_online_cat.oc_name')
						->join('fx_online_cat on fx_online_service.oc_parent_id=fx_online_cat.oc_id')
						->order('o_order asc')
						->page($page_no,$page_size)
						->select();
		$count = $noticeObj->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->getSubNav(2,1,20);
        $this->display();
    }
    /**
     * 添加在线客服
     */
    public function pageAdd(){
    	$this->getSubNav(2,1,10);
    	$categoryInfo = $this->getCategoryInfo();
    	$this->assign('cateinfo',$categoryInfo);
		$this->display();
    }

    public function doAdd(){
		$data = $this->_post();
		if(empty($data['o_name'])) {
            $this->error('在线客服标题不能为空');
        }
		if(empty($data['o_code'])) {
            $this->error('在线客服代码不能为空');
        }
		$onlineObj = D('OnlineService');
		$result = $onlineObj->add($data);
		if($result){
			$this->success('在线客服添加成功', U('Admin/Online/pageList'));
		}else{
			$this->error('在线客服添加失败');
		}
    }
    /**
     * 编辑在线客服
     */
    public function pageEdit(){
    	$this->getSubNav(2,1,10);
    	$oid = $this->_get('oid');
    	$onlineObj = M('online_service',C('DB_PREFIX'),'DB_CUSTOM');
    	$onlineInfo = $onlineObj->where('o_id=%d',array($oid))->find();
    	$this->assign('onlineservice',$onlineInfo);
    	$categoryInfo = $this->getCategoryInfo();
    	$this->assign('cateinfo',$categoryInfo);    	
		$this->display('pageAdd');
    }

    public function doEdit(){
		$data = $this->_post();
		if(empty($data['o_name'])) {
            $this->error('在线客服标题不能为空');
        }
		if(empty($data['o_code'])) {
            $this->error('在线客服代码不能为空');
        }
		$onlineObj = D('OnlineService');
		$result = $onlineObj->save($data);
		if($result===false){
			$this->error('在线客服修改失败');
		}else{
			$this->success('在线客服修改成功', U('Admin/Online/pageList'));
		}
    }
    
    public function doDel(){
		$oid = intval($this->_get('oid'));
		$onlineObj = M('online_service',C('DB_PREFIX'),'DB_CUSTOM');
		$onlineObj->where('o_id='.$oid)->delete();
		$this->success('操作成功',U('Admin/Online/pageList'));
    }
    
    public function pageListCate(){
		$cateinfo = $this->getCategoryInfo();
		$this->assign('cateinfo', $cateinfo);
        $this->getSubNav(2,1,30);
        $this->display();
    }
    
    public function pageAddCate(){
        $this->getSubNav(2,1,40);
        $categoryObj = M('online_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $cateList = $categoryObj->where('oc_parent_id=0')->select();
        $this->assign('cateList',$cateList);
        $this->display();
    }
    
    public function pageEditCate(){
        $this->getSubNav(2,1,40);
        $categoryObj = M('online_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $cateList = $categoryObj->where('oc_parent_id=0')->select();
        $this->assign('cateList',$cateList);
		$catid = $this->_get('ocid');
		$category = $categoryObj->find($catid);
		$this->assign('category', $category);
		$this->display('pageAddCate');
    }
    
    public function doAddCate(){
    	$data = $this->_post();
		if(empty($data['oc_name'])) {
            $this->error('分类名称不能为空');
        }
        $categoryObj = D('OnlineCat');
        $result = $categoryObj->add($data);
		if($result){
            $this->success('在线客服分类添加成功', U('Admin/Online/pageListCate'));
        }else {
            $this->error('在线客服分类添加失败');
        }
    }
    
    public function doEditCate(){
		$data = $this->_post();
		if(empty($data['oc_name'])) {
            $this->error('在线客服分类名称不能为空');
        }
        if($data['oc_id']==$data['oc_parent_id']){
        	$this->error('不能选择自己作为上级分类');
        }
        $categoryObj = D('OnlineCat');
        $result = $categoryObj->save($data);
		if($result===false){
            $this->error('在线客服分类修改失败');
        }else {
            $this->success('在线客服分类修改成功', U('Admin/Online/pageListCate'));
        }
    }
    
    public function doDelCate(){
    	$catid = $this->_param('ocid');
		$category = D('OnlineCat');
		$subcount = $category->where('oc_parent_id=%d',array($catid))->count();
		if($subcount>0){
			$this->error('该分类下有子分类不能删除');
		}
        $result = $category->where('oc_id=%d',array($catid))->delete();
        if($result){
        	$this->success('删除成功');
        }else{
			$this->error('删除失败');
        }
    }
    
    public function pageHelp(){
    	$this->getSubNav(2,1,20);
    	$this->display();
    }
    
    public function getCategoryInfo($cid){
    	$categoryObj = M('online_cat',C('DB_PREFIX'),'DB_CUSTOM');
    	$cateinfo = null;
		if ( null === $cateinfo ) {
            // 为了提高性能，一次性从列表中读取所有信息
            // 然后程序进行1，2级排序
            $array = array();
            $data = $categoryObj->order('oc_order asc')->select();
            if(!$data || empty($data))
                return $array;
            $first = array_values(array_filter($data,create_function('$val','return $val["oc_parent_id"]==0;')));
            foreach ($first as &$cate){
            	$second = array_values(array_filter($data,create_function('$val','return $val["oc_parent_id"]=='.$cate['oc_id'].';')));
            	if($second) $cate['sub'] = $second;
            }
            $array = $first;
            $cateinfo = $array;
        }
        if ( null === $cid ) {
            return $cateinfo;
        }
        else {
            foreach ( $cateinfo as $cat ) {
                if ( $cat['oc_id'] == $cid ) {
                    return $cat;
                }
                if ( isset($cat['sub']) ) {
                	unset($subcat);
                    foreach ( $cat['sub'] as $subcat ) {
                        if ( $subcat['oc_id'] == $cid ) {
                            return $subcat;
                        }
                    }
                }
            }
        }
    }
}