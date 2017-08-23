<?php
/**
 * 后台商品咨询控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author Terry<wanghui@guanyisoft.com> 
 * @date 2013-06-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ConsultationAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
    }
    
    public function index(){
        $this->redirect(U('Admin/Consultation/pageList'));
    }
    
    /**
     * 商品咨询列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-25
     * @modify by wanghaoyu 2013-10-09
     */
    public function pageList(){
        $this->getSubNav(2,5,80);
        $ary_get = $this->_get();
        $ary_where = array();
        if(!empty($ary_get['starttime']) && !empty($ary_get['endtime'])){
            if($ary_get['starttime'] > $ary_get['endtime']){
                $ary_where[C('DB_PREFIX').'purchase_consultation.pc_create_time'] = array('BETWEEN',array($ary_get['endtime'],$ary_get['starttime']));
            }else if($ary_get['starttime'] < $ary_get['endtime']){
                $ary_where[C('DB_PREFIX').'purchase_consultation.pc_create_time'] = array('BETWEEN',array($ary_get['starttime'],$ary_get['endtime']));
            }else{
                $ary_where[C('DB_PREFIX').'purchase_consultation.pc_create_time'] = array('BETWEEN',array($ary_get['starttime'],date("Y-m-d H:i")));
            }
        }else{
            if(!empty($ary_get['starttime']) && empty($ary_get['endtime'])){
                $ary_where[C('DB_PREFIX').'purchase_consultation.pc_create_time'] = array('EGT',$ary_get['starttime']);
            }else if(empty($ary_get['starttime']) && !empty($ary_get['endtime'])){
                $ary_where[C('DB_PREFIX').'purchase_consultation.pc_create_time'] = array('ELT',$ary_get['end']);
            }
        }
        if(!empty($ary_get['title'])){
            $ary_where[C('DB_PREFIX').'purchase_consultation.pc_question_title'] = array('LIKE',"%".$ary_get['title']."%");
        }
        if(!empty($ary_get['is_reply']) && $ary_get['is_reply'] == 1) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = array('eq',1);
        }
        if(!empty($ary_get['is_reply']) && $ary_get['is_reply'] == 2) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = array('eq',0);
        }
      
        $count = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
                                   ->join(' '.C('DB_PREFIX')."goods ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods.g_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")
                                   ->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->show();
        $ary_data = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods_info.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
                                   ->join(' '.C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods_info.g_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")
                                   ->limit($obj_page->firstRow, $obj_page->listRows)->select();
        if(!empty($ary_data) && is_array($ary_data)){
            foreach($ary_data as $ky=>$vl){
                $ary_data[$ky]['pc_question_title'] = htmlspecialchars($vl['pc_question_title']);
                $ary_data[$ky]['pc_question_content'] = htmlspecialchars($vl['pc_question_content']);
                $ary_data[$ky]['pc_answer'] = htmlspecialchars_decode($vl['pc_answer']);
            }
        }
        $this->assign("page",$page);
        $this->assign("data",$ary_data);
        $this->assign("filter",$ary_get);
//        echo "<pre>";print_r($ary_data);exit;
        $this->display();
    }
    
    /**
     * 删除咨询
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-25
     */
    public function doDel(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && isset($ary_post['id'])){
            $ary_result = D("PurchaseConsultation")->where(array('pc_id'=>$ary_post['id']))->delete();
            if(FALSE != $ary_result){
                $this->success("删除成功");
            }else{
                $this->errror("删除失败，请尝试重新操作");
            }
        }else{
            $this->error("该咨询不存在，请重试...");
        }
        
    }
    
    /**
     * 咨询回复
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-25
     */
    public function pageDetail(){
        $this->getSubNav(2,5,80);
        $ary_get = $this->_get();
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $ary_data = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods_info.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
                                   ->join(' '.C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods_info.g_id")
                                   ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
                                   ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
                                   ->where(array('pc_id'=>$ary_get['id']))->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")
                                   ->find();		   
            if(!empty($ary_data) && is_array($ary_data)){
                    $ary_data['pc_question_title'] = htmlspecialchars($ary_data['pc_question_title']);
                    $ary_data['pc_question_content'] = htmlspecialchars($ary_data['pc_question_content']);
					$ary_data['pc_question_content'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['pc_question_content']);	
                    $ary_data['pc_answer'] = htmlspecialchars_decode($ary_data['pc_answer']);
					$ary_data['pc_answer'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['pc_answer']);	
            }
//            echo "<pre>";print_r($ary_data);exit;
            $this->assign("data",$ary_data);
            $this->display();
        }else{
            $this->error("该咨询不存在，请重试...");
        }
    }
    
    /**
     * 回复咨询
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-25
     */
    public function doConsultationReply(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && isset($ary_post['id'])){
            if(!empty($ary_post['answer']) && isset($ary_post['answer'])){
                $ary_data = array(
                    'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                    'pc_answer'   => htmlspecialchars($ary_post['answer']),
                    'pc_is_reply' =>'1',
                    'pc_update_time' =>date("Y-m-d H:i:s"),
                    'pc_reply_time' =>date("Y-m-d H:i:s"),
                );
				$ary_data['pc_answer'] = _ReplaceItemDescPicDomain($ary_data['pc_answer']);
//                echo "<pre>";print_r($ary_data);return false;
                $ary_result = D("PurchaseConsultation")->where(array('pc_id'=>$ary_post['id']))->data($ary_data)->save();
                if(FALSE != $ary_result){
                    $this->success("回复咨询成功","pageList");
                }else{
                    $this->error("回复失败");
                }
            }else{
                $this->error("回复内容不能为空");
            }
        }else{
           $this->error("该咨询不存在，请重试..."); 
        }
    }
}