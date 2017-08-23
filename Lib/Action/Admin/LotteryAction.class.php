<?php

/**
 * 后台抽奖控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.6.1
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-07-14
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class LotteryAction extends AdminAction {

    /**
     * 后台抽奖控制器初始化
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . '抽奖活动');
    }

    /**
     * 后台抽奖活动列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function index() {
        $this->getSubNav(5, 6, 10);
        $Lottery_obj = D('Lottery');
		//搜索条件处理
		$array_cond = array();
		//未删除
		$array_cond['is_deleted'] = 0;
		//如果根据抽奖名称进行搜索
		if(isset($_GET["l_name"]) && $_GET["l_name"] != ""){
			$array_cond["l_name"] = array("LIKE","%" . $_GET["l_name"] . "%");
		}
		//是否启用
		if(isset($_GET["l_status"]) && $_GET["l_status"] != "" && $_GET["l_status"] != "-1"){
			if($_GET["l_status"] == 2){
				$array_cond["l_status"] = 0;
			}else{
				$array_cond["l_status"] = intval($_GET["l_status"]);
			}
		}		
		//如果根据优惠券的有效期进行搜索
		if(isset($_GET["l_start_time"]) && "" != $_GET["l_start_time"]){
			$array_cond["l_start_time"] = array("egt",$_GET["l_start_time"]);
		}
		if(isset($_GET["l_end_time"]) && "" != $_GET["l_end_time"]){
			$array_cond["l_end_time"] = array("elt",$_GET["l_end_time"]);
		}
        $count = $Lottery_obj->where($array_cond)->count();
        $Page = new Page($count, 15);
		$datalist = $Lottery_obj->where($array_cond)->order(array("l_id"=>"desc"))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$data['list'] = $datalist;
        $data['page'] = $Page->show();
		$this->assign("filter",$_GET);
        $this->assign($data);
        $this->display();      
    }
	
	/**
     * 后台中奖名单列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-15
     */
    public function userList() {
        $this->getSubNav(5, 6, 20);
        $Lottery_user_obj = D('LotteryUser');
		$Lottery_obj = D('Lottery');
		//搜索条件处理
		$array_cond = array();
		//未删除
		$array_cond['is_deleted'] = 0;
		//如果根据抽奖名称进行搜索
		if(isset($_GET["m_name"]) && $_GET["m_name"] != ""){
			$array_cond["m.m_name"] = array("LIKE","%" . $_GET["m_name"] . "%");
		}
		if(!empty($_GET["ul_confirm_time_1"]) && !empty($_GET["ul_confirm_time_2"])){
			$array_cond["ul_confirm_time"] = array(between,array($_GET["ul_confirm_time_1"],$_GET["ul_confirm_time_2"]));
		}else{
			if(isset($_GET["ul_confirm_time_1"]) && "" != $_GET["ul_confirm_time_1"]){
				$array_cond["ul_confirm_time"] = array("egt",$_GET["ul_confirm_time"]);
			}
			if(isset($_GET["ul_confirm_time_2"]) && "" != $_GET["ul_confirm_time_2"]){
				$array_cond["ul_confirm_time"] = array("elt",$_GET["ul_confirm_time"]);
			}		
		}
		if(isset($_GET["l_id"]) && $_GET["l_id"] != ""){
			$array_cond["l_id"] = intval($_GET["l_id"]);
		}
		if(isset($_GET["ul_type"]) && $_GET["ul_type"] != ""){
			$array_cond["ul_type"] = intval($_GET["ul_type"]);
		}
		if(isset($_GET["ul_bonus_money"]) && $_GET["ul_bonus_money"] != ""){
			$array_cond["ul_bonus_money"] = trim($_GET["ul_bonus_money"]);
		}		
        $count = $Lottery_user_obj->join('fx_members as m on fx_lottery_user.m_id = m.m_id')->where($array_cond)->count();
		$Page = new Page($count, 15);
		$datalist = $Lottery_user_obj->field('m.m_name,fx_lottery_user.*')->join('fx_members as m on fx_lottery_user.m_id = m.m_id')->where($array_cond)->order(array("fx_lottery_user.ul_id"=>"desc"))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$data['list'] = $datalist;
        $data['page'] = $Page->show();
		//获取抽奖活动
		$lottery_data = $Lottery_obj->field('l_id,l_name,is_deleted')->select();
		foreach($lottery_data as &$lottery){
			if($lottery['is_deleted'] == '1'){
				$lottery['l_name'] = '[已删除]'.$lottery['l_name'];
			}
		}
		$this->assign("lottery_data",$lottery_data);
		$this->assign("filter",$_GET);
        $this->assign($data);
        $this->display();      
    }
	
    /**
     * 后台添加抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function pageAdd() {
        $this->getSubNav(5, 6, 10);
        $this->display();
    }

    /**
     * 执行新增奖品
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doAddLoterys() {
        $data = $this->_post();
        //echo '<pre>';print_r($data);exit;
        $Lottery_obj = D('Lottery');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['l_id'])) {
            $this->error('抽奖活动不存在');
        }
        if (false == $lottery_data = $Lottery_obj->field('l_id,l_detail')->where(array('l_id' => $data['l_id'],'is_deleted'=>'0'))->find()) {
		   $this->error('抽奖活动不存在或删除');
        }
		/**
		if(!empty($lottery_data['l_detail'])){
			$this->error('奖品您已生成过,无需再次生成', U('Admin/Lottery/index'));
		}		
		**/
		/** 红包计算方式改为不直接生成数量方式**/
		//发放设置
		$ary_data = array();
		$ary_data['lottery_pic'] = str_replace('/Lib/ueditor/php/../../..','',$data['GY_SHOP_TOP_AD_22']);
		//七牛图片存入
		$ary_data['lottery_pic'] =  D('ViewGoods')->ReplaceItemPicReal($ary_data['lottery_pic']);
		if(empty($ary_data['lottery_pic'])){
			$this->error('抽奖图片必填');
		}
		//计算生成多少
		$bonus_data = array();
		//中奖总比例
		$total_ratio = 0;
		//红包比例
		$total_bonus_ratio = 0;
		//生成组数量
		//$total_bonus_num = 0;
		//批量生成红包
		if(empty($data['total_price']) || empty($data['single_price'])){
			$this->error('抽奖类型请设置');exit;
		}else{
			$ary_data['total_price'] = $data['total_price'];
			$single_prices = $data['single_price'];
			$total_price = $data['total_price'];
			$p_ratios = $data['p_ratio'];
			$lottery_sorts = $data['lottery_sort'];
			foreach($single_prices as $key=>$single_price){
				//if(!empty($p_ratios[$key])){
				if(isset($p_ratios[$key])){
					$bonus_data[] = array(
						'type_money'=>$single_price,
						'type_ratio'=>$p_ratios[$key],
						//'type_pic'=>str_replace('/Lib/ueditor/php/../../..','',$data['GY_SHOP_TOP_AD_'.$key])
						'type_sort'=>$lottery_sorts[$key]
					);
					$total_ratio +=$p_ratios[$key];
					$total_bonus_ratio +=$p_ratios[$key];
				}
			}
			//计算发放数量
			/**
			foreach($bonus_data as $b_key=>$bonus){
				$bonus_data[$b_key]['type_num'] =  floor((($total_price/$bonus['type_money']))*($bonus['type_ratio']/$total_bonus_ratio));
				$total_bonus_num += intval($bonus_data[$b_key]['type_num']);
			}
			**/
		}
		$ary_data['bonus'] = $bonus_data;
		//设置发放多少神秘大奖
		$mystery_data = array();
		if(!empty($data['ul_title']) && !empty($data['ul_ratio'])){
			$total_ratio += $data['ul_ratio'];
			if($total_ratio>100){
				$this->error('中奖总比例不能大与100%');exit;
			}
			$mystery_data['ul_title'] = $data['ul_title'];
			$mystery_data['type_ratio'] = $data['ul_ratio'];
			//$mystery_data['type_pic'] = str_replace('/Lib/ueditor/php/../../..','',$data['GY_SHOP_TOP_AD_20']);
			$mystery_data['type_num'] = intval($data['ul_num']);
			$mystery_data['type_sort'] = $data['ul_sort'];
			//$mystery_data['type_num'] = floor(($data['ul_ratio']/$total_bonus_ratio)*$total_bonus_num);
		}
		$ary_data['mystery'] = $mystery_data;
		//生成未获奖数量
		//$other_num = floor(($mystery_data['type_num']+$total_bonus_num)*((100-$total_ratio)/$total_ratio));
		//$other_pic = str_replace('/Lib/ueditor/php/../../..','',$data['GY_SHOP_TOP_AD_21']);
		$ary_data['other'] = array(
			'type_ratio' =>100-$total_ratio,
			//'type_pic' => $other_pic
			'again_sort'=>$data['again_sort'],
			'other_sort'=>$data['other_sort']
		);
		$str_l_detail = serialize($ary_data);
		//事物开启
		//M('',C(''),'DB_CUSTOM')->startTrans();
		//生成奖品
		//$this->batchAddLoterys($ary_data);
		//dump($ary_data);die();
		$res_lottery = $Lottery_obj->where(array('l_id'=>$data['l_id']))->data(array('l_detail'=>$str_l_detail,'l_update_time'=>date('Y-m-d H:i:s')))->save();
		if(!empty($res_lottery)){
			 //M('',C(''),'DB_CUSTOM')->commit();
			 $this->success('抽奖奖品生成成功', U('Admin/Lottery/index'));
		}else{
			//M('',C(''),'DB_CUSTOM')->->rollback();
			$this->error('抽奖奖品生成失败', U('Admin/Lottery/index'));
		}
    }
	
    /**
     * 执行生成奖品
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */	
	 /**
	public function batchAddLoterys($ary_data){
		//处理生成红包
		
		while(true){
		    $sql = "SELECT TOP 200 id,newcustid,oldcustid,convert(varchar(20),createdate,120) as createdate,CRMWeb from customer_merge_log where CRMWeb='11' ";
			$customers = $info->queryFetchAll($sql);
			if(empty($customers)){
				break;
			}
			$_binds = array();
			$_values = array();
			$_cids = array();
			$tmp_member = array();
			foreach($customers as $member){
				$_cids[] = $member['id'];
				$tmp_member = array(
					'newcustid' => $member['newcustid'],
				    'oldcustid' => $member['oldcustid'],
					'createdate' => $member['createdate'],
					'createtime' => date('Y-m-d H:i:s'),
				);
				$_binds = array_merge($_binds,array_values($tmp_member));
				$_value = substr(str_repeat('?,', count($tmp_member)),0,-1);
				$_values[] = "({$_value})";
			}
			$_columns = implode(',', array_keys($tmp_member));
			$_values = implode(',', $_values);
			$tab = Tom_Factory::getTable('customer_merge_log');
			$sql = "insert into #__customer_merge_log({$_columns}) values {$_values}";			
			$res = $tab->query($sql,$_binds);
			if($res){
				$where = substr(str_repeat('?,', count($_cids)),0,-1);
				$sql = "UPDATE customer_merge_log  SET CRMWeb = '23' WHERE id in({$where})";
				$binds = array();
				foreach($_cids as $key=>&$cids_value){
					$binds[] = &$cids_value;
				}
				$info->exec($sql, $binds);
			}else{
				$where = substr(str_repeat('?,', count($_cids)),0,-1);
				$sql = "UPDATE customer_merge_log  SET CRMWeb = '24' WHERE id in({$where})";
				$binds = array();
				foreach($_cids as $key=>&$cids_value){
					$binds[] = &$cids_value;
				}
				$info->exec($sql, $binds);
				sleep(1);
			}
	    }	
	}
	**/
	
    /**
     * 执行新增抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doAdd() {
        $data = $this->_post();
        //echo '<pre>';print_r($data);exit;
        $Lottery_obj = D('Lottery');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['l_name'])) {
            $this->error('抽奖名称不存在');
        }
        if (false != $Lottery_obj->where(array('l_name' => $data['l_name'],'is_deleted'=>'0'))->find()) {
            $this->error('此抽奖名称已被使用');
        }
        if ((!empty($data['l_start_time']) && !empty($data['l_end_time'])) && (strtotime($data['l_start_time']) > strtotime($data['l_end_time']) )) {
            $tmp = $data['l_start_time'];
            $data['l_start_time'] = $data['l_end_time'];
            $data['l_end_time'] = $tmp;
        }
        $data['l_create_time'] = date("Y-m-d h:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
          //开启事务
		if(empty($data['is_consume_pont'])){
			$data['is_consume_pont'] = 0;
			$data['consume_point'] = 0;
		}
        $int_lid = $Lottery_obj->data($data)->add();
		if(!empty($int_lid)){
			 $this->success('抽奖活动生成成功', U('Admin/Lottery/index'));
		}else{
			$this->error('抽奖活动生成失败', U('Admin/Lottery/index'));
		}
    }

    /**
     * 执行删除抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doDel() {
        $mix_id = $this->_get('l_id');
		if(empty($mix_id)){
		  $this->error('请选择要删除的抽签活动');exit;
		}
        if (is_array($mix_id)) {
            //批量删除
            $where = array('l_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('l_id' => $mix_id);
        }
		$ary_data = array(
			'is_deleted'=>'1',
			'l_update_time'=>date('Y-m-d H:i:s')
		);
        $Lottery_obj = D('Lottery');
        $res = $Lottery_obj->where($where)->data($ary_data)->save();
        if (false == $res) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

    /**
     * 编辑抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function pageEdit() {
        $this->getSubNav(5, 6, 10, '编辑抽奖活动');
        $l_id = $this->_get('l_id');
        //调用模型获取数据 
		$Lottery_obj = D('Lottery');
        $data['info'] = $Lottery_obj->where(array('l_id'=>$l_id))->find();
        if(false == $data['info']){
            $this->error('抽奖活动参数错误');
        }else{
            $this->assign($data);
            $this->display();
        }
    }
	
    /**
     * 生成抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function addLoterys() {
        $this->getSubNav(5, 6, 10, '生成抽奖活动');
        $l_id = $this->_get('l_id');
        //调用模型获取数据 
		$Lottery_obj = D('Lottery');
        $data['info'] = $Lottery_obj->where(array('l_id'=>$l_id))->find();
		$data['info']['l_detail'] = unserialize($data['info']['l_detail'] );
		//dump($data['info']['l_detail']);die();
        if(false == $data['info']){
            $this->error('抽奖活动参数错误');
        }else{
            $this->assign($data);
            $this->display();
        }
    }
	
    /**
     * 执行修改抽奖活动
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doEdit(){
        $data = $this->_post();
        //echo "<pre>";print_r($data);die;
        $Lottery_obj = D('Lottery');
		//验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['l_name'])) {
            $this->error('优惠券名称不存在');
        }
        if (false != $Lottery_obj->where(array('l_name' => $data['l_name'],'is_deleted'=>'0','l_id'=>array('neq',$data['l_id'])))->find()) {
            $this->error('此抽奖名称已被使用');
        }		
        if ((!empty($data['l_start_time']) && !empty($data['l_end_time'])) && (strtotime($data['l_start_time']) > strtotime($data['l_end_time']) )) {
            $tmp = $data['l_start_time'];
            $data['l_start_time'] = $data['l_end_time'];
            $data['l_end_time'] = $tmp;
        }
        $data['l_update_time'] = date("Y-m-d h:i:s");
		if(empty($data['l_status'])){
			$data['l_status'] = 0;
		}
		if(empty($data['is_consume_pont'])){
			$data['is_consume_pont'] = 0;
			$data['consume_point'] = 0;
		}		
        //修改数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $res = $Lottery_obj->where(array('l_id'=>$data['l_id']))->data($data)->save();
        if (!$res) {
            $this->error('抽奖活动修改失败');
        } else {
		//echo $Lottery_obj->getLastSql();exit;
            $this->success('抽奖活动修改成功', U('Admin/Lottery/index'));
        }
    }
	
    /**
     * 促销优惠券获取节点
     *
     * @author <qianyijun@guanyisoft.com>
     * @date 2013-10-12
     */
    public function pageSet(){
        $this->getSubNav(5, 1, 40);
        $sysconfig = D('SysConfig');
        $data = $sysconfig->getCfgByModule('GET_COUPON');
        $this->assign($data);
        $this->display();
    }
    
    /**
     * 保存促销优惠券节点
     *
     * @author <qianyijun@guanyisoft.com>
     * @date 2013-10-12
     */
    public function doSet(){
        $data = $this->_post();
        $sysconfig = D('SysConfig');
        foreach ($data as $k=>$v){
            if(false === $sysconfig->setConfig('GET_COUPON',$k,$v)){
                $this->error('保存失败');
            }
        }
        $this->success('保存成功！');
    }
}