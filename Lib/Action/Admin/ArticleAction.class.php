<?php
/**
 * 后台资讯控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ArticleAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件		
        $this->setTitle(' - '.L('MENU1_2'));
    }
    /**
     * 后台商品控制器默认页，需要重定向
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index(){
        $this->redirect(U('Admin/Article/pageList'));
    }
    /**
     * 资讯列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageList(){
    	$articleOjb = M('article',C('DB_PREFIX'),'DB_CUSTOM');
    	$title = $this->_request('title');
        $cat_id = $this->_request('cat_id');
    	$where = array();
    	$where['a_status'] = 1;
    	if($title){
    		$where['a_title'] = array('LIKE', '%' . $title . '%');
    	}
        if(!empty($cat_id)){
            $where[C('DB_PREFIX').'article.cat_id'] = $cat_id;
        }
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $articleOjb->field('a_id,a_title,a_is_display,a_create_time,fx_article_cat.cat_name')
						->join('fx_article_cat on fx_article.cat_id=fx_article_cat.cat_id')
						->where($where)
						->order('a_update_time desc,a_order desc')
						->page($page_no,$page_size)
						->select();
		$count = $articleOjb->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $categoryInfo = $this->getCategoryInfo();
    	$this->assign('cateinfo',$categoryInfo);
            $this->assign("filter",  $this->_request());
//        echo "<pre>";print_r($categoryInfo);exit;
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('title',$title);
        $this->getSubNav(2,2,20);
        $this->display();
    }
    /**
     * 发布资讯
     */
    public function pageAdd(){
    	$this->getSubNav(2,2,10);
    	$categoryInfo = $this->getCategoryInfo();
    	$this->assign('cateinfo',$categoryInfo);
		$this->display();
    }
	/**
     * 编辑资讯
	 * 新增定时发送
	 * author lxl <lixiaolong@guanyisoft.com>
     */
    public function doAdd(){
		$data = $this->_post();
		if(empty($data['a_title'])) {
            $this->error('文章标题不能为空');
        }
		if(empty($data['a_content'])) {
            $this->error('文章描述不能为空');
        }
		
		if($_FILES['f_imagepath']['name']){
	    	import('ORG.Net.UploadFile');
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
    	}
        //添加新增时间
        $data['a_create_time'] = date('Y-m-d H:i:s');
		
		//添加定时放送时间
		$data['a_startime'] = $data['startime'];
		$data['a_endtime'] = $data['endtime'];
		if($data['a_endtime'] < $data['a_startime']){
			$this->error('结束时间必须晚于开始时间');
		}
		//dump($data['a_endtime']);die;
		$articleOjb = M('article',C('DB_PREFIX'),'DB_CUSTOM');
        $data['a_content'] = _ReplaceItemDescPicDomain($data['a_content']);		
		$result = $articleOjb->add($data);
		if($result){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"文章添加成功",'文章添加成功为：'.$data['a_title']));		
			$this->success('文章添加成功', U('Admin/Article/pageList'));
		}else{
			$this->error('文章添加失败');
		}
    }
    /**
     * 编辑资讯
     */
     public function pageEdit(){
    	$this->getSubNav(2,2,10);
    	$aid = $this->_get('aid');
    	$articleObj = M('article',C('DB_PREFIX'),'DB_CUSTOM');
    	$articleInfo = $articleObj->where('a_id=%d',array($aid))->find();
		$articleInfo['a_content'] = D('ViewGoods')->ReplaceItemDescPicDomain($articleInfo['a_content']);
		$articleInfo['ul_image_path'] = D('QnPic')->picToQn($articleInfo['ul_image_path']);
    	$categoryInfo = $this->getCategoryInfo();
    	$this->assign('cateinfo',$categoryInfo);    	
    	$this->assign('article',$articleInfo);
		$this->display('pageAdd');
    }

    public function doEdit(){
		$data = $this->_post();
		//dump($data);die;
		if(empty($data['a_title'])) {
            $this->error('文章标题不能为空');
        }
		if(empty($data['a_content'])) {
            $this->error('文章描述不能为空');
        }
        //添加修改时间
        $data['a_update_time'] = date('Y-m-d H:i:s');
		//添加修改定时放送时间
		$data['a_startime'] = $data['startime'];
		$data['a_endtime'] = $data['endtime'];
		if($data['a_endtime'] < $data['a_startime']){
			$this->error('结束时间必须晚于开始时间');
		}
        //上传文章图片
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
    	}
		$articleOjb = M('article',C('DB_PREFIX'),'DB_CUSTOM');
        $data['a_content'] = _ReplaceItemDescPicDomain($data['a_content']);		
		$result = $articleOjb->save($data);
		if($result===false){
			$this->error('文章修改失败');
		}else{
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"文章修改成功",'文章修改成功为：'.$data['a_title']));					
			$this->success('文章修改成功', U('Admin/Article/pageList'));
		}
    }
    
    public function doDel(){
		$aid = intval($this->_get('aid'));
		$articleObj = M('article',C('DB_PREFIX'),'DB_CUSTOM');
		$atitle = $articleObj->where(array('a_id'=>$aid))->getField('a_title');
		$articleObj->where('a_id='.$aid)->setField(array('a_status'=>0,'a_update_time'=>date('Y-m-d H:i:s')));
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"文章删除成功",'删除的文章为：'.$aid.'-'.$atitle));			
		$this->success('操作成功',U('Admin/Article/pageList'));
    }
    
    public function pageListCate(){
		$cateinfo = $this->getCategoryInfo();
		$this->assign('cateinfo', $cateinfo);
        $this->getSubNav(2,2,30);
        $this->display();
    }
    
    public function pageAddCate(){
        $this->getSubNav(2,2,40);
        $categoryObj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $cateList = $categoryObj->where('parent_id=0')->select();
        $this->assign('cateList',$cateList);
        $this->display();
    }
    
    public function pageEditCate(){
        $this->getSubNav(2,2,40);
        $categoryObj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $cateList = $categoryObj->where('parent_id=0')->select();
        $this->assign('cateList',$cateList);
		$catid = $this->_get('catid');
		$category = $categoryObj->find($catid);
		$this->assign('category', $category);
		$this->display('pageAddCate');
    }
    
    public function doAddCate(){
    	$data = $this->_post();
		if(empty($data['cat_name'])) {
            $this->error('文章分类名称不能为空');
        }
        $categoryObj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $result = $categoryObj->add($data);
		if($result){
            $this->success('文章分类添加成功', U('Admin/Article/pageListCate'));
        }else {
            $this->error('文章分类添加失败');
        }
    }
    
    public function doEditCate(){
		$data = $this->_post();
		if(empty($data['cat_name'])) {
            $this->error('文章分类名称不能为空');
        }
        if($data['cat_id']==$data['parent_id']){
        	$this->error('不能选择自己作为上级分类');
        }
        $categoryObj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
        $result = $categoryObj->save($data);
		if($result===false){
            $this->error('文章分类修改失败');
        }else {
            $this->success('文章分类修改成功', U('Admin/Article/pageListCate'));
        }
    }
    
    public function doDelCate(){
    	$catid = $this->_get('catid');
		$category = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
		$subcount = $category->where('parent_id=%d',array($catid))->count();
		if($subcount>0){
			$this->error('该分类下有子分类不能删除');
		}
        $result = $category->where('cat_id=%d',array($catid))->delete();
        if($result){
        	M('article',C('DB_PREFIX'),'DB_CUSTOM')->where('cat_id=%d',array($catid))->setField('cat_id', 0);
			$this->success('删除成功');
        }else{
			$this->error('删除失败');
        }
    }
    
    public function getCategoryInfo($cid){
    	$categoryObj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
    	$cateinfo = null;
		if ( null === $cateinfo ) {
            // 为了提高性能，一次性从列表中读取所有信息
            // 然后程序进行1，2级排序
            $array = array();
            $data = $categoryObj->order('sort_order asc')->select();
            if(!$data || empty($data))
                return $array;
            $first = array_values(array_filter($data,create_function('$val','return $val["parent_id"]==0;')));
            foreach ($first as &$cate){
            	$second = array_values(array_filter($data,create_function('$val','return $val["parent_id"]=='.$cate['cat_id'].';')));
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
                if ( $cat['cate_id'] == $cid ) {
                    return $cat;
                }
                if ( isset($cat['sub']) ) {
                	unset($subcat);
                    foreach ( $cat['sub'] as $subcat ) {
                        if ( $subcat['cate_id'] == $cid ) {
                            return $subcat;
                        }
                    }
                }
            }
        }
    }
}