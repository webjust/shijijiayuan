<?php

/**
 * 买家留言相关模型层 Model
 * @package Model
 * @version 7.2
 * @author wangguibin
 * @date 2013-06-26
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class FeedbackModel extends GyfxModel {
	
    /**
     * 构造方法
     * @author wgb
     * @date 2013-06-26
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 此模型的表名为一个视图 fx_feedback
     * @var string
     */
    protected $tableName = 'feedback';
    
    /**
     * 获取当前用户的留言信息 by wangguibin
     * @data 2013-06-26
     */
    public function getMsgByUser($uid,$pageSize=3) {
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
    	$where = array(
            'user_id' => $uid,
            'parent_id'=>0,
        );
        $num = $fb_obj->where($where)->count('msg_id');
        $obj_page = new Page($num, $pageSize);
        $page = $obj_page->show();
        $list = $fb_obj
                ->where($where)
                ->order(array('msg_time' => 'desc'))
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        return array('data'=>$list,'page'=>$page);
    }
    
    /**
     * 获取留言的回复数据 wgb
     * @data 2013.06.26
     */
    public function getReplyData($mid) {
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array(
            'parent_id'=>$mid
        );
        return $fb_obj->where($where)->select();
    }
    
    /**
     * 获取留言数据 wangguibin
     * @data 2013.06.26
     */    
    public function getMsgListById($id) {
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array(
            'msg_id' => $id,
        );
        return $fb_obj->where($where)->select();
    }
    
    /**
     * 判断是否留言 wangguibin
     * @data 2013.06.26
     */     
    public function validateMsg($data){
     	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
    	$where = array(
    		'msg_content'=>$data['msg_content'],
    		'user_id'=>$data['user_id']
    	);
    	$num = $fb_obj->where($where)->count('msg_id');
    	return $num;    	
    }
    /**
     * 留言信息入库 by wangguibin
     * @data 2013.06.26
     */
    public function saveOrderMsg($data,$type=NULL) {
    	$data['gcom_create_time'] = date('Y-m-d H:i:s');
    	$data['gcom_update_time'] = date('Y-m-d H:i:s');
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
    	$num = $this->validateMsg($data);
    	if($num > 0){
    		return 2;
    	}else{
    		if($fb_obj->add($data)){
    			return true;
    		}else{
    			return false;
    		}
    	}   
        return false;
    }
    /**
     * 获取留言 by wangguibin
     * @data 2013.06.27
     */
    public function getMsgDetail($mid,$type=NULL) {
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
    	$where = 'msg_id='.$mid;
        if($type=='all') $where .= "  or parent_id={$mid}";
        return $fb_obj->where($where)->select();
    }
    
    /**
     * 获取留言 by wangguibin
     * @data 2013.06.27
     */   
    public function saveReply($data) {
    	$fb_obj = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
        if($fb_obj->add($data) && $fb_obj->data(array('msg_status'=>1))->where(array('msg_id'=>$data['parent_id']))->save()) {
            return true;
        }
        return false;
    }
}