<?php
/**
 * 后台数据包控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PackageAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU5_5'));
    }
    /**
     * 数据包列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-1-23
     */
    public function pageList(){
        $this->getSubNav(6,5,10);
    	$packageObj = D('Package');
    	$title = $this->_post('title');
    	$where = array();
    	$where['p_del_status'] = 1;
    	if($title){
    		$where['p_title'] = array('LIKE', '%' . $title . '%');
    	}
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $packageObj->field('p_id,p_title,p_status,p_cate,p_create_time')
						->where($where)
						->order('p_create_time desc')
						->page($page_no,$page_size)
						->select();
		$count = $packageObj->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('title',$title);
        $this->assign('cate',array(0=>'其他',1=>'淘宝',2=>'拍拍'));
        $this->display();
    }
    /**
     * 数据包下载日志
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-1-23
     */
    public function pageLogList(){
    	$packageObj = D('PackageLog');
    	$title = $this->_post('title');
    	$where = array();
    	if($title){
    		$where['p.p_title'] = array('LIKE', '%' . $title . '%');
    	}
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $packageObj->field('pl_id,p_title,p_cate,m_name,pl_create_time')
						->join('inner join fx_package p on(fx_package_log.p_id=p.p_id)')
						->join('inner join fx_members m on(fx_package_log.m_id=m.m_id)')
						->where($where)
						->order('pl_create_time desc')
						->page($page_no,$page_size)
						->select();
		$count = $packageObj->join('inner join fx_package p on(fx_package_log.p_id=p.p_id)')->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('title',$title);
        $this->assign('cate',array(0=>'其他',1=>'淘宝',2=>'拍拍'));
        $this->getSubNav(6,3,30);
        $this->display();
    }
    /**
     * 发布数据包
     */
    public function pageAdd(){
    	$this->getSubNav(6,5,20);
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
		$this->assign($data);
		
		$this->display();
    }

    public function doAdd(){
		$packageObj = D('Package');
		$data = $this->_post();
		//验证是否输入数据包名称
		if(empty($data['p_title'])){
			$this->error('数据包标题不能为空');
		}
    	
		//验证是否输入数据包描述
		if(empty($data['p_desc'])){
			$this->error('数据描述不能为空');
    	}
		
		//验证是否选择数据包文件
//		if(!isset($_FILES['p_file']) || empty($_FILES['p_file'])){
//			$this->error('请指定要上传的数据包文件。');
//		}
		//对用户上传的数据包文件进行处理
//    	if($_FILES['p_file']['name']){
//	    	import('ORG.Net.UploadFile');
//			// 实例化上传类
//			$upload = new UploadFile();
//			// 设置附件上传大小
//			//TODO:此处需要修改为上传文件的大小可以显式的配置
//			$upload->maxSize  = 3145728 ;
//			// 设置附件上传类型
//			$upload->allowExts = array('rar', 'zip');
//			// 设置附件上传目录
//			$upload->savePath =  './Public/upload/package/';
//			if(!$upload->upload()) {
//				// 上传错误提示错误信息
//				$this->error($upload->getErrorMsg());
//			}else{
//				// 上传成功 获取上传文件信息
//				$info =  $upload->getUploadFileInfo();
//				$data['p_path'] = '/Public/upload/package/' . $info[0]['savename'];
//			}
//    	}
	
		//处理数据包信息，并存入数据库
    	$pData = array(
    		'p_title' => $data['p_title'],
    		'p_cate' => $data['p_cate'],
	    	'p_path' => $data['p_file'],
	    	'p_status' => $data['p_status'],
	    	'p_desc' => $data['p_desc'],
	    	'p_create_time' => date('Y-m-d H:i:s')
    	);
		
		//事务开始
		$packageObj->startTrans();
    	$package_id = $packageObj->add($pData);
		if(false === $package_id){
			$this->error("数据包保存失败。");
		}
		//处理发布对象，分为以下四种情况：全部会员、指定会员ID、指定会员等级、指定会员组
		$memberCompetenceObj = D('MemberCompetence');
		
		//允许全部会员，这里的规则是会员ID=-1时表示全部会员
		if((int)$data['p_is_all']==1){
			$insert_p_mid = array();
			$insert_p_mid = array('mc_id'=>$package_id,'m_id'=>-1,'mc_type'=>2);
			$mixed_result = $memberCompetenceObj->data($insert_p_mid)->add();
			if(false === $mixed_result){
				$memberCompetenceObj->rollback();
				$this->error("数据包发布对象数据保存失败。");
			}
		}else{
			//指定的会员，则将指定的会员ID存入数据库
			if(isset($data['p_mid']) && !empty($data['p_mid'])){
				$insert_p_mid = array();
				foreach($data['p_mid'] as $v){
					$insert_p_mid[] = array('mc_id'=>$package_id,'m_id'=>$v,'mc_type'=>2);
				}
				$mixed_result = $memberCompetenceObj->addAll($insert_p_mid);
				if(false === $mixed_result){
					$memberCompetenceObj->rollback();
					$this->error("数据包发布对象数据保存失败。");
				}
			}
			
			//2)允许会员组
			if(isset($data['p_mg']) && !empty($data['p_mg'])){
				$insert_p_mgid = array();
				foreach($data['p_mg'] as $v){
					$insert_p_mgid[] = array('mc_id'=>$package_id,'mg_id'=>$v,'mc_type'=>2);
				}
				$mixed_result = $memberCompetenceObj->addAll($insert_p_mgid);
				if(false === $mixed_result){
					$memberCompetenceObj->rollback();
					$this->error("数据包发布对象数据保存失败。");
				}
			}
			
			//3)允许会员等级
			if(isset($data['p_ml']) && !empty($data['p_ml'])){
				$insert_p_mlid = array();
				foreach($data['p_ml'] as $v){
					$insert_p_mlid[] = array('mc_id'=>$package_id,'ml_id'=>$v,'mc_type'=>2);
				}
				$mixed_result = $memberCompetenceObj->addAll($insert_p_mlid);
				if(false === $mixed_result){
					$memberCompetenceObj->rollback();
					$this->error("数据包发布对象数据保存失败。");
				}
			}
		}
		
		//事务提交，并提示用户操作已成功
		$memberCompetenceObj->commit();
		$this->success('操作成功',U('Admin/Package/pageList'));
		
    }
    /**
     * 编辑数据包
     */
    public function pageEdit(){
    	$this->getSubNav(6,5,10);
    	$pid = $this->_get('pid');
    	$packageObj = M('package',C('DB_PREFIX'),'DB_CUSTOM');
    	$packageInfo = $packageObj->where(array('p_id'=>$pid))->find();
    	$this->assign('package',$packageInfo);
    	$mcObj = M('member_competence',C('DB_PREFIX'),'DB_CUSTOM');
    	$mcList = $mcObj->where(array('mc_type'=>'2','mc_id'=>$pid))->select();
    	$myMember = array();
    	$myGroups = array();
    	$myLevels = array();
    	foreach($mcList as $mcInfo){
    		if($mcInfo['ml_id']){
    			$myLevels[] = $mcInfo['ml_id'];
    		}elseif($mcInfo['mg_id']){
    			$myGroups[] = $mcInfo['mg_id'];
    		}elseif($mcInfo['m_id']>0){
    			$myMember[] = $mcInfo['m_id'];
    		}
    	}
    	if($myMember){
			$myMember = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->field(array('m_name', 'm_id', 'ml_name'))->where(array('m_id' =>array('in',$myMember)))->join('left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id')->select();
    	}
    	$this->assign('myMember',$myMember);
    	$this->assign('myGroups',$myGroups);
    	$this->assign('myLevels',$myLevels);
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        $this->assign($data);
		$this->display('pageAdd');
    }

    public function doEdit(){
		$data = $this->_post();
		if(empty($data['p_title'])){
			$this->error('数据包标题不能为空');
		}
    	if(empty($data['p_desc'])){
			$this->error('数据描述不能为空');
    	}
//    	if($_FILES['p_file']['name']){
//	    	import('ORG.Net.UploadFile');
//			$upload = new UploadFile();// 实例化上传类
//			$upload->maxSize  = 3145728 ;// 设置附件上传大小
//			$upload->allowExts = array('rar', 'zip');// 设置附件上传类型
//			$upload->savePath =  './Public/upload/package/';// 设置附件上传目录
//			if(!$upload->upload()) {// 上传错误提示错误信息
//				$this->error($upload->getErrorMsg());
//			}else{// 上传成功 获取上传文件信息
//				$info =  $upload->getUploadFileInfo();
//				$data['p_path'] = '/Public/upload/package/' . $info[0]['savename'];
//			}
//    	}
    	$pData = array(
    		'p_title' => $data['p_title'],
    		'p_cate' => $data['p_cate'],
	    	'p_path' => $data['p_file'],
	    	'p_status' => 1,
    		'p_is_all' => $data['p_is_all'],
	    	'p_desc' => $data['p_desc'],
	    	'p_update_time' => date('Y-m-d H:i:s'),
    	);
    	$packageObj = D('Package');
		$result = $packageObj->where(array('p_id' => $data['p_id']))->save($pData);
		if($result){
			$package_id = $data['p_id'];
			//发布对象
	        $memberCompetenceObj = M('member_competence',C('DB_PREFIX'),'DB_CUSTOM');
	        $memberCompetenceObj->where(array('mc_id'=>$package_id))->delete();
	        $insert_p_mid = array();
	        $data = $this->_post();
	        if((int)$data['p_is_all']==1){
	            //允许全部会员
	            $insert_p_mid = array('mc_id'=>$package_id,'m_id'=>-1,'mc_type'=>2);
	            $memberCompetenceObj->data($insert_p_mid)->add();
	        }else{
	            //指定的会员
	            foreach($data['p_mid'] as $v){
	                $insert_p_mid[] = array('mc_id'=>$package_id,'m_id'=>$v,'mc_type'=>2);
	            }
	            $memberCompetenceObj->addAll($insert_p_mid);
	        }
	        //2)允许会员组
	        $insert_p_mgid = array();
	        if((int)$data['p_is_all']==0){
	            foreach($data['p_mg'] as $v){
	                $insert_p_mgid[] = array('mc_id'=>$package_id,'mg_id'=>$v,'mc_type'=>2);
	            }
	            $memberCompetenceObj->addAll($insert_p_mgid);
	        }
	        //3)允许会员等级
	        $insert_p_mlid = array();
	        if((int)$data['p_is_all']==0){
	            foreach($data['p_ml'] as $v){
	                $insert_p_mlid[] = array('mc_id'=>$package_id,'ml_id'=>$v,'mc_type'=>2);
	            }
	            $memberCompetenceObj->addAll($insert_p_mlid);
	        }
			$this->success('操作成功',U('Admin/Package/pageList'));
		}else{
			$this->error('修改数据包失败');
		}
    }
    
    public function doDel(){
		$pid = intval($this->_get('pid'));
		$packageObj = M('package',C('DB_PREFIX'),'DB_CUSTOM');
		$packageObj->where(array('p_id'=>$pid))->setField(array('p_del_status'=>0,'p_update_time'=>date('Y-m-d H:i:s')));
		$this->success('操作成功',U('Admin/Package/pageList'));
    }
    
    public function doDelLog(){
		$pid = $this->_post('plid');
		$pid = explode(',', $pid);
		array_walk($pid,array($this,'intArrayVal'));
		$pid = array('in',$pid);
		$packageObj = M('package_log',C('DB_PREFIX'),'DB_CUSTOM');
		$packageObj->where(array('pl_id'=>$pid))->delete();
		$this->success('日志删除成功',array(L('BACK')=>U('Admin/Package/pageLogList')));
    }
	/**
	 * @brief 取整
	 * @param $value: 将数组中的元素取整
	 */    
	private function intArrayVal(&$value) {
		$value = $value + 0;
	}    
    /**
     * 根据用户名，获取用户信息Tr页面
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-23
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