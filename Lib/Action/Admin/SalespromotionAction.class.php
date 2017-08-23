<?php

/**
 * 推广销售类
 * 后台推广销售相关
 *
 * @stage Salespromotion
 * @package Action
 * @subpackage Ucenter
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-09-15
 * @license branches
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */

class SalespromotionAction extends AdminAction {

	private $obj_server;
	public function _initialize() {
		$this->obj_server = D('Salespromotion');
        parent::_initialize();
        $this->setTitle('- 推广销售管理');
    }
	
    /**
     * 分销商引荐管理
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
     */
    public function index() {
    	$ary_params = $this->_request();
    	$m_name = '';
    	//测试数据
		//$m_name = 'test123';
		if(isset($ary_params['m_name']) && trim($ary_params['m_name']) != ''){
			$m_name = $ary_params['m_name'];
		}
		$ary_data	= array();
		$ary_data	= D('Salespromotion')->getMemberRelationAll($m_name);
		$this->setTitle(' - '.'分销商引荐管理');
		$this->getSubNav(6,7,10);
    	//echo "<pre>";print_r($ary_data);exit;
		$nodes = '';
    	$this->assign('m_name',$m_name);
		if(count($ary_data) < 0 || count($ary_data) == 0){
			$nodes	.= '[{ id:0, pId:0, name:"无分销商", open:true}';
		}else{
			if(empty($m_name)){
				$nodes	= '[{ id:0, pId:0, name:"分销商关系", open:true},';
			}else{
				if(!empty($ary_data) && is_array($ary_data)){
					$nodes = '[{ id:0, pId:0, name:"分销商关系", open:true},';
				}else{
					$nodes = '[{ id:0, pId:0, name:"分销商关系", open:true},';	
				}
			}
			for ($i=0;$i<count($ary_data);$i++) {
				$m_id	= $ary_data[$i]['m_id'] ? $ary_data[$i]['m_id'] : 0;
				$mr_p_id	= $ary_data[$i]['mr_p_id'] ? $ary_data[$i]['mr_p_id'] : 0;
				$is_open	= 'false';
				if(0 == $mr_p_id){
					$is_open	= 'true';
				}
				$nodes	.= '{ id:'.$m_id.', pId:'.$mr_p_id.', name:"'.$ary_data[$i]['m_name'].'", open:'.$is_open.'}';
				if($i < count($ary_data)-1){
					$nodes .= ',';
				}
			}
		}
    	$nodes	= $nodes.']';
    	$this->assign('ary_data', $nodes);
	   // echo "<pre>";print_r($nodes);exit;
    	$this->display("add");
    }
	
	/**
	 * @分销商层级关系管理 添加页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function addMemberRelation(){
		$ary_params = $this->_request();
		$m_id	= intval($ary_params['m_id']);
		$mr_p_id	= intval($ary_params['mr_p_id']);
		if(!$m_id){
			echo json_encode(array('status' => 'error'));
			exit;
		}
		$check_mid	= $this->obj_server->checkMemberId($m_id);
		if($check_mid>0){
			echo json_encode(array('status' => 'repeat'));
			exit;
		}
		$ary_psot = array('m_id' => $m_id,'mr_p_id'	=> $mr_p_id);
		$mrid = $this->obj_server->addMemberRelation($ary_psot);
		if(!$mrid){
			echo json_encode(array('status' => 'error'));
			exit;
		}elseif($mrid){
			echo json_encode(array('status' => 'success','mrid' => $mrid));
			exit;
		}
	}
	
	/**
	 * @分销商层级关系管理 添加页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function ajaxEditMemberRelation(){
		$ary_params = $this->_request();
		$m_id = intval($ary_params['m_id']);
		$mr_p_id = intval($ary_params['mr_p_id']);
		if(!$m_id){
			echo json_encode(array('status' => 'error'));
			exit;
		}
		$check_mid	= $this->obj_server->checkMemberId($m_id);
		if($check_mid){
			echo json_encode(array('status' => 'repeat'));
			exit;
		}
		$ary_psot = array('m_id' => $m_id,'mr_p_id'	=> $mr_p_id);
		$mrid = $this->obj_server->ajaxeditMemberRelation($ary_psot);
		if(!$mrid){
			echo json_encode(array('status' => 'error'));
			exit;
		}else{
			echo json_encode(array('status' => 'success'));
			exit;
		}
	}
	
	/**
	 * @分销商层级关系管理 编辑
	 * 
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function editMemberRelation(){
		$ary_params = $this->_request();
		$m_id = intval($ary_params['m_id']);
		$mr_p_id = intval($ary_params['mr_p_id']);
		if(!$m_id){
			echo 'error';exit;
		}
		$ary_psot = array('m_id' => $m_id,'mr_p_id'	=> $mr_p_id);
		
		//先解除之前的分销商授权关系
		$del_bool_res=D('AuthorizeLine')->DistributorDelAuthorize($m_id);
		if($del_bool_res==false){
			echo 'error1';exit;
		}
		//绑定现在的分销商授权关系
		$ary_params['m_id']=$m_id;
		$ary_params['p_id']=$mr_p_id;
		$add_bool_res=D('AuthorizeLine')->DistributorAutoAuthorize($ary_params);
	    if($add_bool_res==false){
		   echo 'error2';exit;
	    }

		//主要解决节点拖动没变化的情况
		$member_relation_info = $this->obj_server->getMemberRelationByMid($m_id);				
		if ($member_relation_info['mr_p_id'] == $mr_p_id) {
			echo 'nochange';exit();
		}
		$update	= $this->obj_server->editMemberRelation($ary_psot);
		if(!$update){
			echo 'error';exit;
		}else{
			echo 'success';exit;
		}
	}
	
	/**
	 * @分销商层级关系管理 添加页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function getMembers(){
    	$ary_filter = $this->_request();
		$ary_filter['pagesize']	= 10;
       	$ary_filter['p']	= isset($ary_filter['p']) &&  intval($ary_filter['p']) ? intval($ary_filter['p']) : 1;
        $ary_data = $this->obj_server->getMembers($ary_filter);
    	$ary_filter	= $ary_data['filter'];
    	$this->assign('memberNums', $ary_data['nums']);
    	$this->assign('mdata', $ary_data['data']);
    	$this->assign('page', $ary_data['pageinfo']);
    	$this->assign('filter', $ary_filter);
    	$this->display("show_member_list");
	}
	
	/**
	 * @分销商层级关系管理 编辑页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function ajaxEditMembers(){
    	$ary_filter = $this->_request();
		$ary_filter['pagesize']	= 10;
    	$ary_filter['page']	= isset($ary_filter['page']) &&  intval($ary_filter['page']) ? intval($ary_filter['page']) : 1;
		$members = $this->obj_server->getMembers($ary_filter);
		$ary_filter	= $ary_data['filter'];
    	$page = $ary_filter['page'];
		$pagesize = $ary_filter['pagesize'];
    	unset($ary_filter['page']);
    	unset($ary_filter['pagesize']);
    	$this->assign('memberNums', $ary_data['nums']);
    	$this->assign('mdata', $ary_data['data']);
    	$this->display("package:salescount/show_edit_member_list.html");
	}
	
	/**
	 * @销售额设定 列表页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function showSalesSetList(){
		$ary_post = $this->_request();
		$m_name	= trim($ary_post['m_name']);
		$ary_params	= array();
    	$page = 0;
    	//列表页每页要显示的条数
		$pagesize = 20;
    	//当前要展示的页面，根据post参数来，如果设置请求页码，则显示第一页
		$page = !empty($ary_post['page'])?$ary_post['page']:1;
		//开始查询的行
		$start	=($page-1)*$pagesize;
		//排序
		$ary_params	= array('page' => $page,'pagesize' => $pagesize);
		//处理查询条件
		//如果设置了搜索条件，简单搜索
    	if($m_name){
			$ary_params['m_name'] = $m_name;
			$filter['m_name'] = $m_name;
		}
		$ary_params['type'] = isset($ary_post['type'])?$ary_post['type']:'';
		$ary_data = $this->obj_server->getSalesSet($ary_params);
		$this->setTitle(' - '.'分销商销售额设定');
		$this->getSubNav(6,7,20);
    	$this->assign('m_name',$ary_params['m_name']);
    	$this->assign('type',$ary_params['type']);
    	$this->assign('ary_data', $ary_data['list']);
    	$this->assign('page', $ary_data['pageinfo']);
    	$this->display("salesset/list");
	}
	
	/**
	 * @销售额设定 显示添加页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function addSalesSet(){
		$ary_params = $this->_request();
		$mss_id	 = intval($ary_params['mssids']);
		$ary_data = $this->obj_server->getSalesSetOne($mss_id);
		$this->setTitle(' - '.'分销商销售额设定');
		$this->getSubNav(6,7,20);
		if(!empty($ary_data)){
			$this->assign('is_edit',1);
		}
    	$this->assign('ary_data',$ary_data);
    	$this->assign('m_name',$ary_data['m_name']);
    	$this->display("salesset/add");
	}
	
	/**
	 * @销售额设定 保存添加
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function saveSalesSet(){
		$postary = $this->_request();
    	$members = $this->obj_server->getMembersData(trim($postary['m_name']));
    	$m_id = 0;
    	$msg = array();
    	if($members['m_id'] > 0){
    		$m_id = $members['m_id'];
    	}else {
    		$this->error('经销商名称输入有误,请重新输入!');exit;
    	}
    	$mss_time_begin	= trim($postary['mss_time_begin']);
    	$mss_time_end = trim($postary['mss_time_end']);
		if ($mss_time_begin > $mss_time_end) {
			$this->error('年月格式有误,起始年月必须小于或等于结束年月!');exit;
		}
        if($mss_time_begin == $mss_time_end){
            $this->error('起始年月和结束年月不能相同!');exit;
        }
        $param = array(
			'm_id' => $m_id,
			'mss_time_begin' => $mss_time_begin,
			'mss_time_end' => $mss_time_end
		);
		if ($postary['method'] == 'edit') {
			$param['method'] = 'edit';
			$param['mss_id'] = $postary['mss_id'];
		}
		// 通过给定的日期查询是否能insert成功
		$ary_data = $this->obj_server->getSalesSetOneByDate($param);
		$m_name_num = $this->obj_server->getSalesSetCount($m_id);
		if ($m_name_num==0 || empty($ary_data) ){ //可以添加
			$param = array(
				'm_id' => $m_id,
				'mss_time_begin' => $mss_time_begin,
				'mss_time_end' => $mss_time_end,
				'mss_sales' => $postary['mss_sales']
			);
			if ($postary['method'] == 'edit') {
				$param['mss_id'] = $postary['mss_id'];
				$save = $this->obj_server->doEditMemberSaleSet($param);
			}else {
		    	$save = $this->obj_server->doInsertMemberSaleSet($param);
			}
			if ($save == false) {
				$this->error('您未修改数据或保存失败,请重试!');exit;
			}else {
				$this->success('保存成功',U('Admin/Salespromotion/showSalesSetList'));exit;
			}
		}else{
			$this->error('该分销商在['.$mss_time_begin.'到'.$mss_time_end.']之间已设置,请重新输入!');exit;
		}
		echo json_encode($msg);exit();
	}

	/**
     * @销售额设定删除 多个或单个删除
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-12-27
     */
    public function delSalesSetMore(){
    	$filter = $this->_request();;
    	$str_mssids	= $filter['mssids'];
    	$del = false;
    	if(empty($str_mssids)){
    		$ary_result['msg'] = '删除失败,请重新操作';
    		$ary_result['status'] = '0';
    		echo json_encode($ary_result);
			exit;
    	}
    	$del = $this->obj_server->doDeleteMemberSaleSet($str_mssids);
    	if(!$del){
    		$ary_result['msg'] = '删除失败,请重新操作';
    		$ary_result['status'] = 0;
    	}else{
    		$ary_result['msg'] = '删除成功';
    		$ary_result['status'] = 1;
    	}
    	echo json_encode($ary_result);
		exit;
    }

	/**
	 * @销售额设定 验证分销商是否存在
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
	 */
	public function ajaxCheckSalesSetMname(){
		$ary_params = $this->_request();
		$m_name	= $ary_params['m_name'];
		if($m_name == ''){
			$msg = array(
				'status' => 'fail',
				'msg' => '请输入分销商名称!'
			);
			exit;
		}
		$member_sale_set = $this->obj_server->getSalesSetOneByMname($m_name);
		if ($member_sale_set == false) {
			$msg = array(
				'status' => 'fail',
				'msg' => '此分销商不存在,请重新输入!'
			);
		}else if (!empty($member_sale_set['mss_id'])){	
			$mss_sales = number_format($member_sale_set['mss_sales'], 2);
			$mss_time_begin = str_replace('-', '/', $member_sale_set['mss_time_begin']);
			$mss_time_end = str_replace('-', '/', $member_sale_set['mss_time_end']);
			$msg = array(
				'status' => 'fail',
				'msg' => '此分销商已经设置过目标销售额.',
			);
		}else {
			$msg = array(
				'status' => 'success',
				'msg' => '此分销商未设置过目标销售额!'
			);
		}
		echo json_encode($msg);
		exit;
	}
	
	/**
     * @添加分销商 
     * @return mixed array
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
     */
	public function ajaxGetMemberNameLevelName(){
		$ary_params = $this->_request();
		$m_id	= intval($ary_params['m_id']);
		$info	= $this->obj_server->getMemberNameLevelName($m_id);
		if(!$info || empty($info)){
			$info	= array(); 
		}
		echo json_encode($info);
		exit;
	}
	
	/**
     * @添加分销商 
     * @return mixed array
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
     */
	public function showAddMember(){
		$ary_params = $this->_request();
		$mr_p_id	= intval($_POST['mr_p_id']);
		$this->assign('mr_p_id',$mr_p_id);
    	$this->display("package:salescount/add_member.html");
	}
	
	/**
     * @删除分销商引荐管理 
     * @return string 成功：success 失败：error
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-15
     */
	public function deleteRelationOne(){
		$ary_params = $this->_request();
		$m_id	= intval($ary_params['m_id']);
		$del	= $this->obj_server->deleteRelationOne($m_id);
		if(false == $del){
			echo 'error';
			exit;
		}else{
			echo 'success';
			exit;
		}
	}
	
}
    
    
