<?php
/**
 * 数据包Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author Liu Feng
 * @date 2013-1-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PackageAction extends CommonAction {
    public function index() {
        $this->redirect(U('Ucenter/Package/pageList'));
    }
	public function pageList(){
		$this->getSubNav(2, 2, 60);
		$noticeObj = D('Package');
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$m_id = $_SESSION['Members']['m_id'];
		$ml_id = $_SESSION['Members']['ml_id'];
		$groupObj = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM');
		$mGroups = $groupObj->where(array('m_id'=>$m_id))->select();
		$ary_data = array();
       		if(!empty($mGroups) && is_array($mGroups)){
			foreach($mGroups as $vl){
				$ary_data[] = $vl['mg_id'];
			}
		}
		
		//echo "<pre>";print_r($ary_data);exit;
		$mGroups = implode(',', $ary_data);
		$where = '';
		if($ml_id){
			$where .= " or ml_id={$ml_id}";
		}
		if($mGroups){
			$where .= " or mg_id in ({$mGroups})";
		}
		$list = $noticeObj->field('p_id,p_title,p_create_time,p_path')
						->join("inner join (select mc_id from fx_member_competence where
								(m_id = -1 or m_id={$m_id}{$where}) and mc_type=2 group by mc_id
								) as t on(fx_package.p_id=t.mc_id)")
						->where('p_status=1 and p_del_status=1')
						->order('p_create_time desc')
						->page($page_no,$page_size)
						->select();
		//echo "<pre>";echo $noticeObj->getLastSql();exit;
		$count = $noticeObj->join("inner join (select mc_id from fx_member_competence where
								(m_id = -1 or m_id={$m_id}{$where}) and mc_type=2 group by mc_id
								) as t on(fx_package.p_id=t.mc_id)")->where('p_status=1 and p_del_status=1')->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
	//echo "<pre>";print_r($list);exit;
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
		$this->display();
	}
	/**
	 * 下载数据包
	 */
	public function pageDownload(){
		$this->getSubNav(2, 2, 60);
		$pid = (int)$this->_get('pid');
		$packageObj = D('Package');
		$packageData = $packageObj->where(array('p_status'=>1,'p_del_status'=>1,'p_id'=>$pid))->find($pid);
		if($packageData){
			$file_path = APP_PATH . $packageData['p_path'];
			if (!file_exists($file_path)){
				header("Content-type: text/html; charset=utf-8");
				$this->error('数据包不存在');
			} else {
				$packageObj = D('PackageLog');
				$m_id = $_SESSION['Members']['m_id'];
				$packageObj->add(array('m_id'=>$m_id,'p_id'=>$packageData['p_id'],'pl_create_time'=>date('Y-m-d H:i:s')));
				$file = fopen($file_path,"r");
				$filename = $packageData['p_title'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION);
				Header("Content-type: application/octet-stream");
				Header("Accept-Ranges: bytes");
				Header("Accept-Length: ".filesize($file_path));
				Header("Content-Disposition: attachment; filename=".$filename);
				echo fread($file, filesize($file_path));
				fclose($file);
				exit;
			}
		}else{
			$this->error('数据包不存在');
		}
	}
}