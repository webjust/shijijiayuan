<?php
/**
 * 后台站点公告控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class NoticeAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
        $this->setTitle(' - '.L('MENU1_0'));
    }
    /**
     * 后台商品控制器默认页，需要重定向
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index(){
        $this->redirect(U('Admin/Notice/pageList'));
    }
    /**
     * 站点公告列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageList(){
        $noticeObj = D('PublicNotice');
        $title = $this->_post('title');
    	$where = array();
    	$where['pn_status'] = 1;
    	if($title){
    		$where['pn_title'] = array('LIKE', '%' . $title . '%');
    	}
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $noticeObj->field('pn_id,pn_title,pn_is_top,pn_create_time')
						->where($where)
						->order('pn_is_top desc,pn_create_time desc')
						->page($page_no,$page_size)
						->select();
        $count = $noticeObj->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('title',$title);
        $this->getSubNav(2,0,20);
        $this->display();
    }
    /**
     * 发布站内公告
     */
    public function pageAdd(){
    	$this->getSubNav(2,0,10);
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        $this->assign($data);
		$this->display();
    }

    public function doAdd(){
		$noticeObj = D('PublicNotice');
		$data = $this->_post();
		if(empty($data['pn_title'])){
			$this->error('标题不能为空');
		}
    	if(empty($data['pn_content'])){
			$this->error('内容不能为空');
    	}
    	$data['pn_status'] = 1;
    	$data['pn_create_time'] = date('Y-m-d H:i:s');
        $data['pn_content'] = _ReplaceItemDescPicDomain($data['pn_content']);		
		$notice_id = $noticeObj->add($data);
		if($notice_id){
			//发布对象
	        $memberCompetenceObj = M('member_competence',C('DB_PREFIX'),'DB_CUSTOM');
	        $insert_pn_mid = array();
	        if((int)$data['pn_is_all']==1){
	            //允许全部会员
	            $insert_pn_mid = array('mc_id'=>$notice_id,'m_id'=>-1,'mc_type'=>1);
	            $memberCompetenceObj->data($insert_pn_mid)->add();
	        }else{
	            //指定的会员
	            foreach($data['pn_mid'] as $v){
	                $insert_pn_mid[] = array('mc_id'=>$notice_id,'m_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mid);
	        }
	        //2)允许会员组
	        $insert_pn_mgid = array();
	        if((int)$data['pn_is_all']==0){
	            foreach($data['pn_mg'] as $v){
	                $insert_pn_mgid[] = array('mc_id'=>$notice_id,'mg_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mgid);
	        }
	        //3)允许会员等级
	        $insert_pn_mlid = array();
	        if((int)$data['pn_is_all']==0){
	            foreach($data['pn_ml'] as $v){
	                $insert_pn_mlid[] = array('mc_id'=>$notice_id,'ml_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mlid);
	        }
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"添加网站公告成功",'添加的网站公告为：'.$data['pn_title']));
			$this->success('操作成功',U('Admin/Notice/pageList'));
		}else{
			$this->error($noticeObj->getError());
		}
    }
    /**
     * 编辑站内公告
     */
    public function pageEdit(){
    	$this->getSubNav(2,0,10);
    	$pnid = $this->_get('pnid');
    	$noticeObj = M('public_notice',C('DB_PREFIX'),'DB_CUSTOM');
    	$noticeInfo = $noticeObj->where('pn_id=%d',array($pnid))->find();
		$noticeInfo['pn_content'] = D('ViewGoods')->ReplaceItemDescPicDomain($noticeInfo['pn_content']);
    	$this->assign('notice',$noticeInfo);
    	$mcObj = M('member_competence',C('DB_PREFIX'),'DB_CUSTOM');
    	$mcList = $mcObj->where(array('mc_type'=>'1','mc_id'=>$pnid))->select();
    	$myMember = array();
    	$myGroups = array();
    	$myLevels = array();
    	foreach($mcList as $mcInfo){
    		if($mcInfo['ml_id']){
    			$myLevels[] = $mcInfo['ml_id'];
    		}elseif($mcInfo['mg_id']){
    			$myGroups[] = $mcInfo['mg_id'];
    		}elseif($mcInfo['m_id']>0){
    			$myMember[] = $mcInfo['m_id'];
    		}
    	}
    	if($myMember){
			$myMember = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->field(array('m_name', 'm_id', 'ml_name'))->where(array('m_id' =>array('in',$myMember)))->join('left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id')->select();
    	}
    	$this->assign('myMember',$myMember);
    	$this->assign('myGroups',$myGroups);
    	$this->assign('myLevels',$myLevels);
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        $this->assign($data);
		$this->display('pageAdd');
    }

    public function doEdit(){
		$noticeObj = D('PublicNotice');
		$data = $this->_post();
		if(empty($data['pn_title'])){
			$this->error('标题不能为空');
		}
    	if(empty($data['pn_content'])){
			$this->error('内容不能为空');
    	}
    	$data['pn_update_time'] = date('Y-m-d H:i:s');
        $data['pn_content'] = _ReplaceItemDescPicDomain($data['pn_content']);		
    	$result = $noticeObj->save($data);
        $notice_id = $data['pn_id'];
//        echo "<pre>";print_r($data);exit;
		if($result){
			//发布对象
	        $memberCompetenceObj = M('member_competence',C('DB_PREFIX'),'DB_CUSTOM');
	        $memberCompetenceObj->where(array('mc_id'=>$notice_id))->delete();
	        $insert_pn_mid = array();
	        $data = $this->_post();
	        if((int)$data['pn_is_all']==1){
	            //允许全部会员
	            $insert_pn_mid = array('mc_id'=>$notice_id,'m_id'=>-1,'mc_type'=>1);
	            $memberCompetenceObj->data($insert_pn_mid)->add();
	        }else{
	            //指定的会员
	            foreach($data['pn_mid'] as $v){
	                $insert_pn_mid[] = array('mc_id'=>$notice_id,'m_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mid);
	        }
	        //2)允许会员组
	        $insert_pn_mgid = array();
	        if((int)$data['pn_is_all']==0){
	            foreach($data['pn_mg'] as $v){
	                $insert_pn_mgid[] = array('mc_id'=>$notice_id,'mg_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mgid);
	        }
	        //3)允许会员等级
	        $insert_pn_mlid = array();
	        if((int)$data['pn_is_all']==0){
	            foreach($data['pn_ml'] as $v){
	                $insert_pn_mlid[] = array('mc_id'=>$notice_id,'ml_id'=>$v,'mc_type'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mlid);
	        }
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"编辑网站公告成功",'编辑的网站公告为：'.$data['pn_title']));			
			$this->success('操作成功',U('Admin/Notice/pageList'));
		}else{
			$this->error($noticeObj->getError());
		}
    }
    
    public function doDel(){
		$pnid = intval($this->_get('pnid'));
		$noticeObj = M('public_notice',C('DB_PREFIX'),'DB_CUSTOM');
		$pn_title = $noticeObj->where(array('pn_id'=>$pnid))->getField('pn_title');
		$noticeObj->where('pn_id='.$pnid)->setField(array('pn_status'=>0,'pn_update_time'=>date('Y-m-d H:i:s')));
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除网站公告成功",'删除的网站公告为：'.$pni.'-'.$pn_title));				
		$this->success('操作成功',U('Admin/Notice/pageList'));
    }
    
    /**
     * 根据用户名，获取用户信息Tr页面用于新增促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getMemberTr() {
        layout(false);
        $name = $this->_post('name');
        $data = D('Members')->field(array('m_name', 'm_id', 'ml_name'))->where(array('m_name' => $name))->join('left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id')->find();
        if (false == $data) {
            $this->ajaxReturn(false);
            exit;
        } else {
            $this->assign($data);
            $this->display();
        }
    }    
}