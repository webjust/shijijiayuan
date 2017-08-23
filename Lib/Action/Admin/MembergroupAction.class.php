<?php

/**
 * 后台会员分组控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author listen 
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembergroupAction extends AdminAction{
    /**
     * 控制器默认方法，暂时重定向到会员等级列表
     * @author listen
     * @date 2013-01-16
     * @todo 需要重定向到会员等级列表页的
     */
    public function index(){
        $this->redirect(U('Admin/Memberlevel/pageList'));
    }
    /**
     * 会员分组列表显示页面
     * @author listen   
     * @date 2013-01-17
     */
    public function pageList(){
        $this->getSubNav(6,1,40);
        $ary_get = $this->_get();
        $int_p = (int)$ary_get['p'] > 0 ? (int)$ary_get['p'] : 1;
        $count = M('members_group',C(DB_PREFIX),'DB_CUSTOM')->where()->count();
        $obj_page = new Page($count,15);
        $page = $obj_page->show();
        $ary_group = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status'=>1))->limit(($int_p-1)*10 .',10')->select();
        $this->assign('ary_group',$ary_group);
        $this->assign('page',$page);
        $this->display();
    }
    /**
     * 会员分组编辑显示
     * @author listen   
     * @date 2013-01-17
     */
    public function pageEdit(){
        $this->getSubNav(6, 1, 40, '分组修改');
        $data = $this->_get();
        if (!isset($data['mgid'])) {
            $this->error('分组参数有误');
        } else {
            $ary_memberlevel = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_id' => $data['mgid']))->find();
            //echo "<pre>";print_r($ary_memberlevel);exit;
            $this->assign('group', $ary_memberlevel);
            $this->display();
        }
    }
     /**
     * 会员分组编辑操作
     * @author listen   
     * @date 2013-01-17
     */
    public function doEdit(){
        $data = $this->_post();
        if (!isset($data['mg_id'])) {
            $this->error('分组参数有误');
        }if(!isset($data['mg_name'])){
            $this->error('分组名称不能为空');
        }
        if(!empty($data['mg_id']) && $data['mg_id']>0){
            $mgid = $data['mg_id'];
            unset($data['mg_id']);
        }
        $res = M('MembersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_id'=>$mgid))->save($data);
        if(FALSE !==$res){
            $this->success('分组修改成功',U('Admin/Membergroup/pageList'));
        }else {
            $this->error('修改失败');
        }
    }
    /**
     * 分组显示页面
     * @author listen
     * @date 2013-01-17
     * 
     */
    public function pageAdd(){
        $this->getSubNav(6,1,50);
        $this->display();
    }
    /**
     * 分组添加操作
     * @author listen
     * @date 2013-01-17
     */
    public function doAdd(){
         $data = $this->_post();
        //echo "<pre>";print_r($data);exit;
        $data['mg_create_time'] = date("Y-m-d h:i:s");
        if(!isset($data['mg_name'])){
            $this->error('会员分组不能为空');
            exit;
        }else {
            if(M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_name'=>$data['mg_name']))->find()){
                $this->error('分组名称已经存在');
                exit;
            }
        }
        
        $result = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
        
        if ($result) {
            $this->success('会员分组添加成功', U('Admin/Membergroup/pageList'));
        } else {
            $this->error('会员分组添加失败');
        }
    }
    /**
     * 分组删除操作
     * @author listen 
     * @date 2013-01-18
     */
    public function doDel(){
        $mg_id = $this->_get('mg_id');
        if(!empty($mg_id)){
            if (is_array($mg_id)) {
            //批量删除
                $where = array('mg_id' => array('IN',$mg_id));
            } else {
                //单个删除
                $where = array('mg_id' => $mg_id);
            }
            $res = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save(array('mg_status'=>0));
            if (false == $res) {
                $this->error('删除失败');
            } else {
                $this->success('删除成功');
            }
        }else{
            $this->success('请选择需要删除的会员分组');
        }
    }
    /**
     * 会员归组
     * @author listen
     * @date 2013-01-18
     */
    public function groupingPage(){
        $ary_get = $this->_get();
        $int_p = (int)$ary_get['p'] > 0 ? (int)$ary_get['p'] : 1 ;
       // echo "<pre>";print_r($ary_get);exit;
        $this->getSubNav(6,1,60);
        $ary_request = $this->_request();
        $memberLevel = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status'=>'1'))->select();
        //echo "<pre>";print_r($memberLevel);exit;
        $where =array();
        $where[C('DB_PREFIX').'members.m_verify'] = '2';
        if(!empty($ary_request['m_name']) && isset($ary_request['m_name'])){
            $where[C('DB_PREFIX').'members.m_name'] = array('LIKE',"%".$ary_request['m_name']."%");
        }
        if(!empty($ary_request['ml_id']) && isset($ary_request['ml_id'])){
            $where[C('DB_PREFIX').'members_level.ml_id'] = $ary_request['ml_id'];
        }
         //统计总条数
        $count = M('members', C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->count();
        $obj_page = new Page($count, 15);
        $page = $obj_page->show();
        
        //获取已经审核通过的会员
        $ary_members = M('members',C('DB_PREFIX'),'DB_CUSTOM')
                ->join(" ".C('DB_PREFIX')."members_level ON ".C('DB_PREFIX')."members.`ml_id`=".C('DB_PREFIX')."members_level.`ml_id`")
                ->where($where)->limit((($int_p-1)*15).',15')->select();
         //echo "<pre>";print_r(M('members',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;

        //获取有效的分组名称
        $ary_group = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status'=>1))->select();
        //echo M('members',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        //echo "<pre>";print_r($ary_group);exit;
        if(!empty($ary_members) && is_array($ary_members)){
            foreach($ary_members as $k=>$v){
                $ary_where = array(C('DB_PREFIX').'members.m_id'=>$v['m_id']);
                $group = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')
                        ->field(array(C('DB_PREFIX').'members_group.mg_name',C('DB_PREFIX').'members_group.mg_id'))
                        ->join(" ".C('DB_PREFIX')."related_members_group ON ".C('DB_PREFIX')."related_members_group.`mg_id`=".C('DB_PREFIX')."members_group.`mg_id`")
                        ->join(" ".C('DB_PREFIX')."members ON ".C('DB_PREFIX')."related_members_group.`m_id`=".C('DB_PREFIX')."members.`m_id`")
                        ->where($ary_where)->select();
//                echo "<pre>";print_R(M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
                if(!empty($group)){
                    $ary_members[$k]['group'] = $group;
                }
            }
        }
        $this->assign("mlevel",$memberLevel);
        $this->assign('ary_group',$ary_group);
        $this->assign("filter",$ary_request);
        $this->assign('ary_members',$ary_members);
        $this->assign('page',$page);
        $this->display();
    }
    /**
     * 会员归组添加操作
     * @author listen
     * @date 2013-01-18
     * 
     */
    public function doSet(){
        layout(false);
        $mid = $this->_get('mid');
        $mgid = $this->_get('mgid');
        if(isset($mid) && isset($mgid)){
            if($info = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_id'=>$mgid,'m_id'=>$mid))->find()){
                $this->ajaxReturn(false);
                exit;
            }else{ 
                $data = array('mg_id'=>$mgid,'m_id'=>$mid);
                $result = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
                if ($result) {
                    $info = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_id'=>$mgid))->find();
                    $this->assign('info', $info);
					D('Gyfx')->deleteAllCache('related_members_group','mg_id', array('m_id'=>$mid), $ary_order=null,$ary_group=null,$ary_limit=null,600);
                    $this->display();
                    exit;
                } else {
                    $this->ajaxReturn(false);
                    exit;
                }
            }
        }
        $this->ajaxRetrun(false);
        
    }
    /**
     * 归组删除
     * @author listen
     * @date 2013-01-18
     */
    public function doDelSet(){
         layout(false);
        $mid = $this->_get('mid');
        $mgid = $this->_get('mgid');
        //print_r($mgid);exit;
        if(isset($mid)){
            $where = array();
            if (is_array($mid)) {
                $where['m_id'] = array('IN', $mid);
            } else {
                $where['m_id'] = $mid;
            }
            if ((int) $mgid != -1) {
                $where['mg_id'] = $mgid;
            }
            
            // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $result = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->delete();
            if (FALSE !== $result) {
				if (is_array($mid)) {
					foreach($mid as $sub_mid){
						D('Gyfx')->deleteAllCache('related_members_group','mg_id', array('m_id'=>$sub_mid), $ary_order=null,$ary_group=null,$ary_limit=null,600);						
					}
				} else {
					D('Gyfx')->deleteAllCache('related_members_group','mg_id', array('m_id'=>$mid), $ary_order=null,$ary_group=null,$ary_limit=null,600);
				}				
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }else{
            $this->error('请选择需要删除的会员分组');
        }
    }
    /*
     * 批量归组
     */
    public function doBacthGroup(){
        $ary_data = $this->_post();
        $ary_group = array();
        if(isset($ary_data['mg_id'])){
            $ary_group['mg_id'] = $ary_data['mg_id']; 
        }
        if(isset($ary_data['m_id'])){
            $ary_mid = explode(',',$ary_data['m_id']);
            foreach($ary_mid as $k=>$v){
                $ary_group['m_id'] = $v;
                if(isset($ary_group['mg_id']) && isset($v)){
                    $res_find = D('RelatedMembersGroup')->where(array('m_id'=>$v,'mg_id'=>$ary_group['mg_id']))->find();
                }
                if(!$res_find){
                   $res_add =  D('RelatedMembersGroup')->add($ary_group);
                   if(!$res_add){
                       $this->ajaxReturn(false);
                       exit;
                   }
                }
				D('Gyfx')->deleteAllCache('related_members_group','mg_id', array('m_id'=>$v), $ary_order=null,$ary_group=null,$ary_limit=null,600);
            }
            $this->ajaxReturn(true);          
        }   
    }
}