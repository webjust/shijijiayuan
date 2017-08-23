<?php
/**
 * 站内信Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author Liu Feng
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MessageAction extends CommonAction {
    public function index() {
        $this->redirect(U('Ucenter/Message/pageMailBox'));
    }

	public function pageMailBox(){
		$this->getSubNav(5, 4, 20);
		$noticeObj = D('StationLetters');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$m_id = $_SESSION['Members']['m_id'];
		$list = $noticeObj->field('fx_station_letters.sl_id,sl_title,ifnull(m_name,\'管理员\') from_name,rsl_is_look,sl_create_time')
						->join('inner join fx_related_station_letters on(fx_station_letters.sl_id=fx_related_station_letters.sl_id)')
						->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
						->where("rsl_to_del_status=1 and rsl_to_m_id={$m_id}")
						->order('sl_create_time desc')
						->page($page_no,$page_size)
						->select();
		$count = $noticeObj->where("sl_status=1 and sl_to_m_id={$m_id}")->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
		$this->display();
	}

	public function pageSendBox(){
		$this->getSubNav(5, 4, 20);
		$noticeObj = D('StationLetters');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$m_id = $_SESSION['Members']['m_id'];
		$list = $noticeObj->field('fx_station_letters.sl_id,sl_title,ifnull(m_name,\'管理员\') to_name,rsl_is_look,sl_create_time')
						->join('inner join fx_related_station_letters on(fx_station_letters.sl_id=fx_related_station_letters.sl_id)')
						->join('left join fx_members on(fx_related_station_letters.rsl_to_m_id=fx_members.m_id)')
						->where("sl_from_del_status=1 and sl_from_m_id={$m_id}")
						->order('sl_create_time desc')
						->page($page_no,$page_size)
						->select();
		$count = $noticeObj->join('inner join fx_related_station_letters on(fx_station_letters.sl_id=fx_related_station_letters.sl_id)')
							->where("rsl_status=1 and sl_from_m_id={$m_id}")->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
		$this->display();
	}

	public function pageSend(){
		$this->getSubNav(5, 4, 20);
		$this->display();
	}

	public function pageReply(){
		$this->getSubNav(5, 4, 20);
		$slid = $this->_get('mid');
    	$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$messageInfo = $messageObj->field('sl_id,sl_title,sl_content,ifnull(m_name,\'管理员\') to_name,sl_create_time')
						->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$slid))
						->find();
    	if($messageInfo){
    		$lettersObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
    		$letter = $lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>$_SESSION['Members']['m_id']))->find();
    		if($letter['rsl_is_look']==0){
    			$lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>$_SESSION['Members']['m_id']))->setField('rsl_is_look', 1);
    		}
			$messageInfo['reply_title'] = 'Re:' . $messageInfo['sl_title'];
			$messageInfo['reply_content'] = "\n\r\n\r=========================================\n\r" . $messageInfo['sl_content'];
			$this->assign('messageInfo',$messageInfo);
    	}else{
			$this->error('站内信不存在');
    	}
		$this->display();
	}

	public function pageRead(){
		$this->getSubNav(5, 4, 20);
		$slid = $this->_get('mid');
		$type = $this->_get('type');
		$ary_where=array('sl_id'=>$slid);
		if($type=="to"){
			$ary_where['rsl_to_m_id']=$_SESSION['Members']['m_id'];
		}
		$count = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
		if($count == '0'){
			$this->error('站内信不存在');
		}else{
		    $messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
			$messageInfo = $messageObj->field('letter.sl_id,sl_title,sl_content,ifnull(m1.m_name,\'管理员\') from_name,ifnull(m2.m_name,\'管理员\') to_name,sl_from_m_id,sl_create_time')
							->join('left join fx_related_station_letters as letter on(letter.sl_id=fx_station_letters.sl_id)')
							->join('left join fx_members as m1 on(fx_station_letters.sl_from_m_id=m1.m_id)')
							->join('left join fx_members as m2 on(letter.rsl_to_m_id=m2.m_id)')
							->where(array('fx_station_letters.sl_id'=>$slid))
							->find();
			if($messageInfo){
				$lettersObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
				$letter = $lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>$_SESSION['Members']['m_id']))->find();
	    		if($letter['rsl_is_look']==0){
	    			$lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>$_SESSION['Members']['m_id']))->setField('rsl_is_look', 1);
	    		}
	    		$messageInfo['sl_content'] = nl2br($messageInfo['sl_content']);
				$this->assign('messageInfo',$messageInfo);
				$this->assign('type',$type);
			}else{
				$this->error('站内信不存在');
			}			
		}
		$this->display();
	}

	public function doSend(){
		$to_name = $this->_post('to_name');
		$title = $this->_post('title');
		$content = $this->_post('content');
		if($to_name=='管理员'){
			$sl_to_m_id = -1;
		}else{
			$memberObj = M('members',C('DB_PREFIX'),'DB_CUSTOM');
			$toMemInfo = $memberObj->where(array('m_name' => $to_name))->find();
			if($toMemInfo==false){
				$this->error('收信人不存在');
			}
			$sl_to_m_id = $toMemInfo['m_id'];
		}
		$m_id = $_SESSION['Members']['m_id'];
		$msgData = array(
			'sl_title' => $title,
			'sl_content' => $content,
			'sl_from_m_id' => $m_id,
			'sl_from_m_name' => $_SESSION['Members']['m_name'],
			'sl_from_del_status' => 1,
			'sl_parentid' => 0,
			'sl_create_time' => date('Y-m-d H:i:s')
		);
		$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$result = $messageObj->add($msgData);
		if($result){
			$message_id = $messageObj->getLastInsID();
			$memberCompetenceObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
			$relatedData = array('sl_id'=>$message_id,'rsl_to_m_id'=>$sl_to_m_id,'rsl_is_look'=>0,'rsl_to_del_status'=>1);
            $memberCompetenceObj->add($relatedData);
			$this->success(L('OPERATION_SUCCESS'),U('Ucenter/Message/pageSendBox'));
		}else{
			$this->error('站内信发送失败');
		}
	}

	public function doReply(){
		$sl_id = $this->_post('sl_id');
		$title = $this->_post('title');
		$content = $this->_post('content');
		$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$letterInfo = $messageObj->where(array('sl_id'=>$sl_id))->find();
		if($letterInfo){
			$msgData = array(
				'sl_title' => $title,
				'sl_content' => $content,
				'sl_from_m_id' => $_SESSION['Members']['m_id'],
				'sl_from_m_name' => $_SESSION['Members']['m_name'],
				'sl_from_del_status' => 1,
				'sl_parentid' => 0,
				'sl_create_time' => date('Y-m-d H:i:s')
			);
			$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
			$result = $messageObj->add($msgData);
			if($result){
				$message_id = $messageObj->getLastInsID();
				$memberCompetenceObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
				$relatedData = array('sl_id'=>$message_id,'rsl_to_m_id'=>$letterInfo['sl_from_m_id'],'rsl_is_look'=>0,'rsl_to_del_status'=>1);
	            $memberCompetenceObj->add($relatedData);
				$this->success(L('OPERATION_SUCCESS'),U('Ucenter/Message/pageSendBox'));
			}else{
				$this->error('站内信发送失败');
			}
		}else{
			$this->error('站内信发送失败');
		}
	}

    /**
     * 判断收信人是否有效
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-22
     */
    public function getMember() {
        $m_name = $this->_get('to_name');
        if (empty($m_name)) {
            $this->ajaxReturn('请输入收信人');
        } else {
        	if($m_name=='管理员'){
        		$this->ajaxReturn(true);
        	}
            $memberObj = M('members',C('DB_PREFIX'),'DB_CUSTOM');
            $ary_result = $memberObj->where(array('m_name' => $m_name))->find();
            if (false == $ary_result) {
                $this->ajaxReturn('收信人不存在');
            } else {
                $this->ajaxReturn(true);
            }
        }
    }

	public function doDelete(){
		$slid = $this->_post('slid');
		$m_id = $_SESSION['Members']['m_id'];
		$type = intval($this->_post('type'));
		$slid = explode(',', $slid);
		array_walk($slid,array($this,'intArrayVal'));
		$slid = array('in',$slid);
		if($type==0){
			$noticeObj = D('RelatedStationLetters');
			$noticeObj->where(array('sl_id'=>$slid,'rsl_to_m_id'=>$m_id))->setField('rsl_to_del_status', 0);
			$this->success(L('OPERATION_SUCCESS'),array(L('BACK')=>U('Ucenter/Message/pageMailBox')));
		}else{
			$noticeObj = D('StationLetters');
			$noticeObj->where(array('sl_id'=>$slid,'sl_from_m_id'=>$m_id))->setField('sl_from_del_status', 0);
			$this->success(L('OPERATION_SUCCESS'),array(L('BACK')=>U('Ucenter/Message/pageSendBox')));
		}
	}
	/**
	 * @brief 取整
	 * @param $value: 将数组中的元素取整
	 */
	private function intArrayVal(&$value) {
		$value = $value + 0;
	}
}