<?php
/**
 * 后台投诉建议控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author Terry<wanghui@guanyisoft.com> 
 * @date 2013-06-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SuggestionsAction extends AdminAction{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function index(){
        
        $this->redirect(U('Admin/Suggestions/pageList'));
        
    }
    
    public function pageList(){
        $this->getSubNav(2,5,70);
        $ary_get = $this->_get();
        $ary_where = array();
        if(!empty($ary_get['starttime']) && !empty($ary_get['endtime'])){
            if($ary_get['starttime'] > $ary_get['endtime']){
                $ary_where[C('DB_PREFIX').'suggestions.s_create_time'] = array('BETWEEN',array($ary_get['endtime'],$ary_get['starttime']));
            }else if($ary_get['starttime'] < $ary_get['endtime']){
                $ary_where[C('DB_PREFIX').'suggestions.s_create_time'] = array('BETWEEN',array($ary_get['starttime'],$ary_get['endtime']));
            }else{
                $ary_where[C('DB_PREFIX').'suggestions.s_create_time'] = array('BETWEEN',array($ary_get['starttime'],date("Y-m-d H:i")));
            }
        }else{
            if(!empty($ary_get['starttime']) && empty($ary_get['endtime'])){
                $ary_where[C('DB_PREFIX').'suggestions.s_create_time'] = array('EGT',$ary_get['starttime']);
            }else if(empty($ary_get['starttime']) && !empty($ary_get['endtime'])){
                $ary_where[C('DB_PREFIX').'suggestions.s_create_time'] = array('ELT',$ary_get['end']);
            }
        }
        if(!empty($ary_get['title'])){
            $ary_where[C('DB_PREFIX').'suggestions.s_title'] = array('LIKE',"%".$ary_get['title']."%");
        }
        $count = D($this->_name)->field(" ".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."suggestions.*")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."suggestions.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."suggestions.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->where($ary_where)->order(C('DB_PREFIX')."suggestions.`s_create_time` DESC")
                                   ->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_data = D($this->_name)->field(" ".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."suggestions.*,".C('DB_PREFIX')."members.m_name")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."suggestions.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."suggestions.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->where($ary_where)->order(C('DB_PREFIX')."suggestions.`s_create_time` DESC")
                                   ->limit($obj_page->firstRow, $obj_page->listRows)->select();
//        echo "<pre>";print_r(D($this->_name)->getLastSql());exit;
//        echo "<pre>";print_r($ary_data);exit;
        $this->assign("data", $ary_data);
        $this->assign("page", $page);
        $this->assign("filter", $ary_get);
        $this->display();
    }
    
    public function pageDetail(){
        $this->getSubNav(2,5,70);
        $ary_get = $this->_get();
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $data = D($this->_name)->field(" ".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."suggestions.*,".C('DB_PREFIX')."members.m_name")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."suggestions.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."suggestions.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->where(array(C('DB_PREFIX').'suggestions.s_id'=>$ary_get['id']))->order(C('DB_PREFIX')."suggestions.`s_create_time` DESC")
                                   ->find();
            $this->assign("data",$data);
            $this->display();
        }else{
            $this->error("参数有误,请重试...");
        }
    }
    
    public function doIshandle(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && isset($ary_post['id'])){
            if(!empty($ary_post['is_handle']) && isset($ary_post['is_handle'])){
                $ary_data = array(
                    'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                    's_is_handle'   =>$ary_post['is_handle'],
                    's_handle_time' =>date("Y-m-d H:i:s"),
                    's_update_time' =>date("Y-m-d H:i:s")
                );
                
                $ary_result = D($this->_name)->where(array('s_id'=>$ary_post['id']))->data($ary_data)->save();
                if(FALSE != $ary_result){
                    $this->success("处理成功");
                }else{
                    $this->error("处理失败");
                }
            }else{
                $this->error("请选择是否处理");
            }
        }else{
           $this->error("参数有误,请重试..."); 
        }
        
        
    }
}