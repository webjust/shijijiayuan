<?php
/**
 * 后台站内信控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MessageAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU1_0'));
    }
    /**
     * 站内信列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-17
     */
    public function pageMailBox(){
        load('extend');
        $messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
    	$type = $this->_post('type');
    	$title = $this->_post('title');
    	$start_time = $this->_post('c_start_time');
    	$end_time = $this->_post('c_end_time');
    	$where = array();
    	$where['sl_status'] = 1;
    	if($title){
			switch ($type){
				case 'title':
	    			$where['sl_title'] = array('LIKE', '%' . $title . '%');
	    			break;
				case 'from':
					if($title=='管理员'){
						$where['sl_from_m_id'] = -1;
					}else{
						$where['fx_members.m_name'] = $title;
					}
					break;
				case 'to';
					if($title=='管理员'){
						$subWhere['fx_related_station_letters.rsl_to_m_id'] = -1;
					}else{
						$subWhere['fx_members.m_name'] = $title;
                    }
					$subSql = $messageObj->field('sl_id')->table('fx_related_station_letters')
									->join('left join fx_members on(fx_related_station_letters.rsl_to_m_id=fx_members.m_id)')
									->where($subWhere)
									->group('sl_id')
									->select(false);
                    $where['sl_id'] = array('exp'," IN {$subSql} ");
					break;
                    
    	    }
    	}
    	if($start_time){
    		$where['sl_create_time'] = array('EGT', $start_time);
    	}
		if($end_time){
    		$where['sl_create_time'] = array('ELT', $end_time);
    	}
    	if($start_time && $end_time){
    		$where['sl_create_time'] = array(array('EGT', $start_time),array('ELT', $end_time));
    	}
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $messageObj->field('sl_id,sl_title,sl_content,sl_from_m_id,ifnull(m_name,\'管理员\') from_name,sl_create_time')
						->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
						->where($where)
						->order('sl_create_time desc')
						->page($page_no,$page_size)
						->select();
        $relatedObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		foreach($list as &$letter){
			$members = $relatedObj->field('ifnull(m_name,\'管理员\') to_name,rsl_to_m_id')
						->join('left join fx_members on(fx_related_station_letters.rsl_to_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$letter['sl_id']))
						->select();
			$to_members = array();
			$letter['is_to_admin'] = 0;
			foreach ($members as $member){
				$to_members[] = $member['to_name'];
				if($member['rsl_to_m_id']==-1){
					$letter['is_to_admin'] = 1;
				}
			}
			$letter['to_name'] = implode(',', $to_members);
		}
		$count = $messageObj->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('type',$type);
        $this->assign('title',$title);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->getSubNav(2,5,30);
        $this->display();
    }
    /**
     * 发布站内信
     */
    public function pageSend(){
    	$this->getSubNav(2,5,30);
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        $this->assign($data);
		$this->display();
    }

    public function doAdd(){
		$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$data = $this->_post();
		if(empty($data['sl_title'])){
			$this->error('站内信标题不能为空');
		}
    	if(empty($data['sl_content'])){
			$this->error('站内信内容不能为空');
    	}
    	$letterData = array(
    		'sl_title' => $data['sl_title'],
    		'sl_content' => $data['sl_content'],
    		'sl_from_m_id' => -1,
    		'sl_parentid' => 0,
    		'sl_create_time' => date('Y-m-d H:i:s')
    	);
		$result = $messageObj->add($letterData);
		if($result){
			$message_id = $messageObj->getLastInsID();
			//发布对象
	        $memberCompetenceObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
	        $insert_pn_mid = array();
	        if((int)$data['rsl_to_m_id']==1){
	        	$memberObj = M('members',C('DB_PREFIX'),'DB_CUSTOM');
	        	$memberlist = $memberObj->field('m_id')->select();
	            //允许全部会员
	            foreach($memberlist as $memberInfo){
	            	$insert_pn_mid[] = array('sl_id'=>$message_id,'rsl_to_m_id'=>$memberInfo['m_id'],'rsl_is_look'=>0,'rsl_status'=>1,'rsl_read_nums'=>1);
	            }
	            $memberCompetenceObj->addAll($insert_pn_mid);
	        }else{
	        	$sql = array();
	            //指定的会员
				if($data['pn_mid']){
					array_walk($data['pn_mid'],array($this,'intArrayVal'));
	            	$m_ids = implode(',', $data['pn_mid']);
					$sql[] = sprintf('select m_id from fx_members where m_id in(%s)',$m_ids);
	            }	            
	            if($data['pn_ml']){
					array_walk($data['pn_ml'],array($this,'intArrayVal'));
	            	$ml_ids = implode(',', $data['pn_ml']);
					$sql[] = sprintf('select m_id from fx_members where ml_id in(%s)',$ml_ids);
	            }
	            if($data['pn_mg']){
	            	array_walk($data['pn_ml'],array($this,'intArrayVal'));
	            	$mg_ids = implode(',', $data['pn_mg']);
					$sql[] = sprintf('select m_id from fx_related_members_group where mg_id in(%s)',$mg_ids);
	            }
	            if(count($sql)>0){
		            $sql = implode(' union ', $sql);
		            $m_ids = $messageObj->query($sql);
					foreach($m_ids as $m_id){
		                $insert_pn_mid[] = array('sl_id'=>$message_id,'rsl_to_m_id'=>$m_id['m_id'],'rsl_is_look'=>0,'rsl_status'=>1,'rsl_read_nums'=>1);
		            }
		            $memberCompetenceObj->addAll($insert_pn_mid);
	            }
	        }
			$this->success('操作成功',U('Admin/Message/pageMailBox'));
		}else{
			$this->error($messageObj->getError());
		}
    }
	/**
	 * @brief 取整
	 * @param $value: 将数组中的元素取整
	 */    
	private function intArrayVal(&$value) {
		$value = $value + 0;
	}    
    /**
     * 查看站内信
     */
    public function pageRead(){
    	$this->getSubNav(2,0,10);
    	$slid = $this->_get('slid');
    	$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$messageInfo = $messageObj->field('sl_id,sl_title,sl_content,ifnull(m_name,\'管理员\') from_name,sl_create_time')
						->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$slid))
						->find();
    	if($messageInfo){
    		$relatedObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
			$members = $relatedObj->field('ifnull(m_name,\'管理员\') to_name')
						->join('left join fx_members on(fx_related_station_letters.rsl_to_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$slid))
						->select();
			$to_members = array();
			foreach ($members as $member){
				$to_members[] = $member['to_name'];
			}
			$messageInfo['to_name'] = implode(',', $to_members);
    	}else{
			$this->error('站内信不存在');
    	}
    	$this->assign('messageInfo',$messageInfo);
		$this->display();
    }
    /**
     * 回复站内信
     */
    public function pageReply(){
    	$this->getSubNav(2,0,10);
    	$slid = $this->_get('slid');
    	$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$messageInfo = $messageObj->field('sl_id,sl_title,sl_content,ifnull(m_name,\'管理员\') from_name,sl_create_time')
						->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$slid))
						->find();
    	if($messageInfo){
    		$lettersObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
    		$letter = $lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>'-1'))->find();
    		if($letter['rsl_is_look']==0){
    			$lettersObj->where(array('sl_id'=>$messageInfo['sl_id'],'rsl_to_m_id'=>'-1'))->setField('rsl_is_look', 1);
    		}
    		$relatedObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
			$members = $relatedObj->field('ifnull(m_name,\'管理员\') to_name,rsl_to_m_id')
						->join('left join fx_members on(fx_related_station_letters.rsl_to_m_id=fx_members.m_id)')
						->where(array('sl_id'=>$slid))
						->select();
			$to_members = array();
			$messageInfo['is_to_admin'] = 0;
			foreach ($members as $member){
				$to_members[] = $member['to_name'];
				if($member['rsl_to_m_id']==-1){
					$messageInfo['is_to_admin'] = 1;
				}
			}
			$messageInfo['reply_title'] = 'Re:' . $messageInfo['sl_title'];
			$messageInfo['reply_content'] = "\n\r\n\r=========================================\n\r" . $messageInfo['sl_content'];
			$messageInfo['to_name'] = implode(',', $to_members);
    	}else{
			$this->error('站内信不存在');
    	}
    	$this->assign('messageInfo',$messageInfo);
		$this->display();
    }
    
    public function doReply(){
		$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$data = $this->_post();
		if(empty($data['sl_title'])){
			$this->error('站内信标题不能为空');
		}
    	if(empty($data['sl_content'])){
			$this->error('站内信内容不能为空');
    	}
    	$letter = $messageObj->where(array('sl_id'=>$data['sl_id']))->find();
    	if(empty($letter)){
    		$this->error('参数错误');
    	}
    	$letterData = array(
    		'sl_title' => $data['sl_title'],
    		'sl_content' => $data['sl_content'],
    		'sl_from_m_id' => -1,
    		'sl_parentid' => $data['sl_id'],
    		'sl_create_time' => date('Y-m-d H:i:s')
    	);
		$result = $messageObj->add($letterData);
		if($result){
			$message_id = $messageObj->getLastInsID();
			//发布对象
	        $memberCompetenceObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
        	$insert_pn_mid = array('sl_id'=>$message_id,'rsl_to_m_id'=>$letter['sl_from_m_id'],'rsl_is_look'=>0,'rsl_status'=>1,'rsl_read_nums'=>1);
            $memberCompetenceObj->add($insert_pn_mid);
			$this->success('操作成功',U('Admin/Message/pageMailBox'));
		}else{
			$this->error($messageObj->getError());
		}
    }    
    
    public function doDel(){
		$slid = intval($this->_get('slid'));
		$messageObj = M('station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$messageObj->where(array('sl_id'=>$slid))->delete();
		$relatedObj = M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM');
		$relatedObj->where(array('sl_id'=>$slid))->delete();
		$this->success('操作成功',U('Admin/Message/pageMailBox'));
    }
    
    /**
     * 根据用户名，获取用户信息Tr页面用于新增促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getMemberTr() {
        layout(false);
        $name = $this->_post('name');
        $data = D('Members')->field(array('m_name', 'm_id', 'ml_name'))->where(array('m_name' => $name))->join('left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id')->find();
        if (false == $data) {
            $this->ajaxReturn(false);
            exit;
        } else {
            $this->assign($data);
            $this->display();
        }
    }
}