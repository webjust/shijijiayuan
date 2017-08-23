<?php

/**
 * 试用申请展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-10-15
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
Class ApplyAction extends CommonAction{

	private $ApplyModel;
	/**
     * 初始化操作
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
	public function _initialize() {
		parent::_initialize();
		$this->ApplyModel = D('TryApply');
	}

	/**
	 * 试用报告列表
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-10-13
	 */
	public function pageList(){
		$this->getSubNav(1, 3, 40);
		$ary_member = session("Members");
		$ary_where = array('member.m_id' => $ary_member['m_id']);
		$order_by = '';
		$int_page_size = 20;
		$field = array(
			C("DB_PREFIX")."try_apply_records.try_status",
			C("DB_PREFIX")."try_apply_records.tar_id",
			C("DB_PREFIX")."try_apply_records.tar_create_time",
			C("DB_PREFIX")."try_apply_records.try_oid",
			"try.try_picture",
			"try.try_title",
			"orders.o_status",
			"member.m_name"
			);
		$data = $this->ApplyModel->GetTryApplyList($ary_where,$field,$order_by,$int_page_size,0);	// 正在试用的活动数据
		$this->assign('apply_info',$data['list']);
		$this->display();
	}
}