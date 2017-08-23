<?php

/**
 * 试用报告展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-10-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
Class ReportAction extends CommonAction{

	private $tryReportModel;
	/**
     * 初始化操作
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
	public function _initialize() {
		parent::_initialize();
		$this->tryReportModel = D('TryReport');
	}

	/**
	 * 试用报告列表
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-10-13
	 */
	public function pageList(){
		$this->getSubNav(1, 3, 50);
		$ary_member = session("Members");
		$ary_where = array('member.m_id' => $ary_member['m_id']);
		$order_by = '';
		$int_page_size = 20;
		$data = $this->tryReportModel->GetTryReportPageList($ary_where,$order_by,$int_page_size,0);	// 正在试用的活动数据
		$this->assign('report_info',$data['list']);
		$this->display();
	}
}