<?php
/**
 * 站内公告Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author Liu Feng
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class NoticeAction extends CommonAction {
    public function index() {
        $this->redirect(U('Ucenter/Notice/pageList'));
    }
	public function pageList(){
		$this->getSubNav(5, 4, 10);
		$noticeObj = D('PublicNotice');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$m_id = $_SESSION['Members']['m_id'];
                if(empty($m_id)){
                    $list = $noticeObj->field('pn_id,pn_title,pn_is_top,pn_create_time')
                                                    ->where('pn_status=1 and pn_is_all=1')
                                                    ->order('pn_is_top desc,pn_create_time desc')
                                                    ->page($page_no,$page_size)
                                                    ->select();

                    $count = $noticeObj->where('pn_status=1 and pn_is_all=1')->count();
                }else{
                    $ml_id = $_SESSION['Members']['ml_id'];
                    $groupObj = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM');
                    $mGroups = $groupObj->where(array('m_id'=>$m_id))->select();
                    $group = array();
                    if(!empty($mGroups) && is_array($mGroups)){
                        foreach($mGroups as $vl){
                            $group[] = $vl['mg_id'];
                        }
                    }
                    $mGroups = implode(',', $group);
    //                echo "<pre>";print_r($mGroups);exit;
                    $where = '';
                    if($ml_id){
                            $where .= " or ml_id={$ml_id}";
                    }
                    if($mGroups){
                            $where .= " or mg_id in ({$mGroups})";
                    }
                    $list = $noticeObj->field('pn_id,pn_title,pn_is_top,pn_create_time')
                                                    ->join("inner join (select mc_id from fx_member_competence where
                                                                    (m_id = -1 or m_id={$m_id}{$where}) and mc_type=1 group by mc_id
                                                                    ) as t on(fx_public_notice.pn_id=t.mc_id)")
                                                    ->where('pn_status=1')
                                                    ->order('pn_is_top desc,pn_create_time desc')
                                                    ->page($page_no,$page_size)
                                                    ->select();

                    $count = $noticeObj->join("inner join (select mc_id from fx_member_competence where
                                                                    (m_id = -1 or m_id={$m_id}{$where}) and mc_type=1 group by mc_id
                                                                    ) as t on(fx_public_notice.pn_id=t.mc_id)")->where('pn_status=1')->count();

                }
		
                                                                $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
//        echo "<pre>";print_r($list);exit;
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
		$this->display();
	}
	public function pageRead(){
                
		$this->getSubNav(5, 4, 10);
		$pnid = (int)$this->_get('pnid', 'htmlspecialchars', 0);
		$noticeObj = D('PublicNotice');
		$noticeData = $noticeObj->find($pnid);
		$noticeData['pn_content'] = D('ViewGoods')->ReplaceItemDescPicDomain($noticeData['pn_content']);            
		if(empty($noticeData)){
			$this->error(L('OPERATION_ERROR'));
		}
		$noticeObj->where('pn_id=' . $pnid)->setInc('pn_read_num',1);

		$this->assign('data',$noticeData);
		$this->display();
	}
}