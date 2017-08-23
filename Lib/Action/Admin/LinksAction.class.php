<?php
/**
 * 后台友情链接控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class LinksAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU1_6'));
    }
    /**
     * 后台商品控制器默认页，需要重定向
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index(){
        $this->redirect(U('Admin/Links/pageList'));
    }
    /**
     * 友情链接列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageList(){
    	$linksObj = D('UsefulLinks');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$list = $linksObj->field('ul_id,ul_name,ul_image_path,ul_link_url,ul_is_image_link,ul_order')
						->order('ul_order desc')
						->where('ul_status=1')
						->page($page_no,$page_size)
						->select();
		foreach($list as &$val){
			$val['ul_image_path'] = D('QnPic')->picToQn($val['ul_image_path']);
		}
        $count = $linksObj->where('ul_status=1')->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->getSubNav(2,6,10);
        $this->display();
    }
    /**
     * 发布友情链接
     */
    public function pageAdd(){
    	$this->getSubNav(2,6,20);
		$this->display();
    }

    public function doAdd(){
		$linksObj = D('UsefulLinks');
		$data = $this->_post();
		if(empty($data['ul_name'])){
			$this->error('友情链接名称不能为空');
		}
    	if(empty($data['ul_link_url'])){
			$this->error('友情链接地址不能为空');
    	}
		/**
    	if($_FILES['f_imagepath']['name']){
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'links/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$data['ul_image_path'] = '/Public/Uploads/'.CI_SN.'/links/' . $info[0]['savename'];
			}
    	}**/
		$data['ul_image_path'] = D('ViewGoods')->ReplaceItemPicReal($data['ul_image_path']);
		$result = $linksObj->add($data);
		if($result){
			$this->success('操作成功',U('Admin/Links/pageList'));
		}else{
			$this->error('新增友情链接失败');
		}
    }
    /**
     * 编辑友情链接
     */
    public function pageEdit(){
		$this->getSubNav(2,6,20);
    	$ulid = $this->_get('ulid');
    	$linksObj = D('UsefulLinks');
		$ary_where = array('ul_status'=>'1');
		$ary_where['ul_id'] = $ulid;
    	$linkInfo = $linksObj->where($ary_where)->find();
		$linkInfo['ul_image_path'] = D('QnPic')->picToQn($linkInfo['ul_image_path']);
    	$this->assign('link',$linkInfo);
		$this->display('pageAdd');
    }

    public function doEdit(){
		$linksObj = D('UsefulLinks');
		$data = $this->_post();
		if(empty($data['ul_name'])){
			$this->error('友情链接名称不能为空');
		}
    	if(empty($data['ul_link_url'])){
			$this->error('友情链接地址不能为空');
		}
		/**
		if($_FILES['f_imagepath']['name']){
			//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/links/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$data['ul_image_path'] = '/Public/Uploads/'.CI_SN.'/links/' . $info[0]['savename'];
			}
    	}	**/
		$data['ul_image_path'] = D('ViewGoods')->ReplaceItemPicReal($data['ul_image_path']);		
		$result = $linksObj->save($data);
		if($result===false){
			$this->error('修改友情链接失败');
		}else{
			$this->success('操作成功',U('Admin/Links/pageList'));
		}
    }
    
    public function doDel(){
		$ulid = intval($this->_get('ulid'));
		$linksObj = D('UsefulLinks');
		$linksObj->where('ul_id='.$ulid)->setField(array('ul_status'=>0,'ul_update_time'=>date('Y-m-d H:i:s')));
		$this->success('操作成功',U('Admin/Links/pageList'));
    }
	
}