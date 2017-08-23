<?php
/**
 * 违规公告Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author Liu Feng
 * @date 2013-1-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class AnnouncementAction extends CommonAction {
    public function index() {
        $this->redirect(U('Ucenter/Notice/pageList'));
    }
	public function pageList(){
		$this->getSubNav(5, 4, 30);
		$noticeObj = D('Announcement');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$m_id = $_SESSION['Members']['m_id'];
		$ml_id = $_SESSION['Members']['ml_id'];
		$ml = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->field('ml_id')->find();
		if($ml['ml_id'] != $ml_id){
			$ml_id = $ml['ml_id'];
			$_SESSION['Members']['ml_id'] = $ml_id;
		}
		//dump($ml_id);die();
		$groupObj = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM');
		$mGroups = $groupObj->where(array('m_id'=>$m_id))->select();
		$dataMgroup = array();
		if(!empty($mGroups) && is_array($mGroups)){
			$dataMgroup = array();
			foreach($mGroups as $group){
				$dataMgroup[] = $group['mg_id'];
			}
		}
		$mGroups = implode(',', $dataMgroup);
		$where = '';
		if($ml_id){
			$where .= " or ml_id={$ml_id}";
		}
		if($mGroups){
			$where .= " or mg_id in ({$mGroups})";
		}
		$list = $noticeObj->field('pn_id,pn_title,pn_create_time,pn_is_top')
						->join("inner join (select mc_id from fx_member_competence where
								(m_id = -1 or m_id={$m_id}{$where}) and mc_type=4 group by mc_id
								) as t on(fx_announcement.pn_id=t.mc_id)")
						->where('pn_status=1')
						->order('pn_is_top desc,pn_create_time desc')
						->page($page_no,$page_size)
						->select();
//                                                                echo "<pre>";print_r($noticeObj->getLastSql());exit;
		$count = $noticeObj->join("inner join (select mc_id from fx_member_competence where
								(m_id = -1 or m_id={$m_id}{$where}) and mc_type=4 group by mc_id
								) as t on(fx_announcement.pn_id=t.mc_id)")->where('pn_status=1')->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
//        echo "<pre>";print_r($list);exit;
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
		$this->display();
	}
	public function pageRead(){
		$this->getSubNav(5, 4, 30);
		$pnid = (int)$this->_get('pnid', 'htmlspecialchars', 0);
		$noticeObj = D('Announcement');
		$noticeData = $noticeObj->find($pnid);
		if(empty($noticeData)){
			$this->error(L('OPERATION_ERROR'));
		}
		$noticeData['pn_content'] = D('ViewGoods')->ReplaceItemDescPicDomain($noticeData['pn_content']);
		$noticeObj->where('pn_id=' . $pnid)->setInc('pn_read_num',1);
		$this->assign('data',$noticeData);
		$this->display();
	}
}