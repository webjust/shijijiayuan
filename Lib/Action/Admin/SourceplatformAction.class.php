<?php

/**
 * 后台设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author listen
 * @date 2013-05-30
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SourceplatformAction extends AdminAction{
	/**
	 * 默认控制器重定向
	 * @author listen
	 * @date 2013-05-30
	  
	 */
	public function index(){
		$this->redirect(U('Admin/Sourceplatform/pageList'));
	}
	/**
	 * 所属平台列表
	 * @author listen
	 * @date 2013-05-30
	 */
	public function pageList(){
		$this->getSubNav(6,0,30);
		$ary_platfrom = D('SourcePlatform')->where()->select();
		$this->assign('platfrom',$ary_platfrom);
		$this->display();
	}
	/**
	 * 添加平台显示页面
	 * @author listen
	 * @date 2013-05-30
	 */
	public function pageAdd(){
		$this->getSubNav(6,0,40);
		$this->display();
	}
	/**
	 * 添加来源平台
	 * @author listen
	 * @date 2013-05-30
	 */
	public function doAdd(){
		$ary_data = $this->_post();

		$res =  D('SourcePlatform')->add($ary_data);
		if(!$res){
			$this->error('添加失败');
		}else {
			$this->success('添加成功', U('Admin/Sourceplatform/pageList'));
		}

	}
	/**
	 *  检查平台代码是否重复
	 * @author listen
	 * @date 2013-03-31
	 */
	public function checkSourceplatformCode(){
		$str_param = $this->_param();
		if(isset($str_param)){
			$res = D('SourcePlatform')->where(array('sp_code'=>$str_param['filed']))->find();
		}
		if(!empty($res)){

			$this->ajaxReturn("该".$ary_get['msg']."已经存在");
		}else{
			 
			$this->ajaxReturn(true);
		}
	}
	/**
	 * 删除平台操作
	 * @author listen
	 * @date 2013-05-31
	 *
	 */
	public function doDel(){
		$int_id = $this->_get('sp_id');
		D()->startTrans();
		if(isset($int_id)){
			$res_sp = D('SourcePlatform')->where(array('sp_id'=>$int_id))->delete();
			if($res_sp){
				$ary_rsp = D('RelatedMembersSourcePlatform')->where(array('sp_id'=>$int_id))->select();
				if(!empty($ary_rsp)){
					$res_rsp = D('RelatedMembersSourcePlatform')->where(array('sp_id'=>$int_id))->delete();
					if(!$res_rsp){
						D()->rollback();
						$this->error('删除失败失败');
					}
				}
				 
				D()->commit();
				$this->success('删除成功', U('Admin/Sourceplatform/pageList'));

			}else {
				D()->rollback();
				$this->error('删除失败失败');
			}
		}else {
			$this->error('参数错误');
		}
	}

	/**
	 * 批量删除平台操作
	 * @author wangguibin
	 * @date 2013-07-22
	 *
	 */
	public function doBatDelPlat(){
		$int_id = $this->_post('sp_ids');
		D()->startTrans();
		if(isset($int_id)){
			$where = array();
			$where['sp_id'] = array('in',$int_id);
			$res_sp = D('SourcePlatform')->where($where)->delete();
			if($res_sp){
				$ary_rsp = D('RelatedMembersSourcePlatform')->where($where)->select();
				if(!empty($ary_rsp)){
					$res_rsp = D('RelatedMembersSourcePlatform')->where($where)->delete();
					if(!$res_rsp){
						D()->rollback();
						$this->error('删除失败失败');
					}
				}
				 
				D()->commit();
				$this->success('删除成功', U('Admin/Sourceplatform/pageList'));

			}else {
				D()->rollback();
				$this->error('删除失败失败');
			}
		}else {
			$this->error('参数错误');
		}
	}
	 
	/**
	 * 编辑页面显示
	 * @author listen
	 * @date 2013-5-31
	 */
	public function pageEdit(){
		$this->getSubNav(6,0,3);
		$int_id = $this->_get('sp_id');
		if(isset($int_id)){
			$ary_data  = D('SourcePlatform')->where(array('sp_id'=>$int_id))->find();
		}
		$this->assign('platform',$ary_data);
		$this->display();
	}
	/**
	 * 编辑操作
	 * @author listen
	 * @date 2013-05-31
	 */
	public function doEdit(){
		$int_sp_id = $this->_post('sp_id');
		//echo $int_sp_id;exit;
		$ary_data = array();
		if(isset($int_sp_id)){
			$ary_data['sp_code'] = $this->_post('sp_code');
			$ary_data['sp_name'] = $this->_post('sp_name');
			$ary_data['sp_stauts'] = $this->_post('sp_stauts');
			$ary_data['sp_update_time'] = date('Y-m-d h:i:s');
			$res = D('SourcePlatform')->where(array('sp_id'=>$int_sp_id))->save($ary_data);
			//echo D('SourcePlatform')->getLastSql();exit;
			if(!$res){
				$this->error('编辑失败');
			}
			$this->success("编辑成功",U('Admin/Sourceplatform/pageList'));
		}else {
			$this->error('参数错误');
		}

	}
}