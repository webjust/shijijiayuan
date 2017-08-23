<?php

/**
 * 后台积分设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author czy<chenzongyao@guanyisoft.com>
 * @date 2013-04-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PointAction extends AdminAction {

    /**
     * 控制器初始化
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-04-16
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle(' - 积分设置');
    }

    /**
     * 默认控制器
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-04-16
     */
    public function index() {
        $this->redirect(U('Admin/Point/pageSet'));
    }

    /**
     * 积分设置页面
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-04-16
     */
    public function pageSet() {
        $this->getSubNav(8, 6, 10);
        $data = D('PointConfig')->getConfigs();
        //echo "<pre>";print_r($data);exit;
        $this->assign('ary',$data);
        $this->display();
    }

    /**
     * 修改积分设置
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-04-16
     */
    public function doSet(){
        $data = $this->_post();
        
        $ary_data = array(
                      'is_consumed' => $data['is_consumed'],
                      'cinsumed_channel' => $data['cinsumed_channel'],
    		          'consumed_ratio' => !empty($data['is_consumed']) ? $data['consumed_ratio'] : 0,
                      'regist_points' => intval($data['regist_points']),
    			      'recommend_points' => intval($data['recommend_points']),
	                  'invites_points' => intval($data['invites_points']),
					  'is_buy_consumed' => intval($data['is_buy_consumed']),
					  'is_low_consumed' => intval($data['is_low_consumed']),
					  'consumed_buy_ratio' => intval($data['consumed_buy_ratio']),
					  'consumed_points' => sprintf("%0.2f",$data['consumed_points']),
					  'low_consumed_points' => intval($data['low_consumed_points']),
					  'again_recommend_points' => intval($data['again_recommend_points']),
					  'show_recommend_points' => intval($data['show_recommend_points']),
					  'login_points' => intval($data['login_points']),
					  'sign_points' => intval($data['sign_points'])
                    );
        if(!empty($ary_data['is_consumed']) && ($ary_data['consumed_ratio']<=0 || empty($ary_data['consumed_ratio']))){
            $this->error('启用积分换算比率，请填入大于0的数');
        }
        //echo "<pre>";print_r($data);exit;
        $SysSeting = D('PointConfig');
        if(false === $SysSeting->setConfig($ary_data)){
            $this->error('保存失败');
        }else{
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"积分设置",serialize($ary_data)));
            $this->success('保存成功');
        }
    }
	
	/*验证最低抵扣金额是否为100的整数倍
	 *add by <zhuwenwei@guanyisoft.com>
	 *date 2015-11-04
	*/
	public function checkLowPoint(){
		$low_consumed_points = $this->_get('low_consumed_points');
		$num = $low_consumed_points/100;
		if (!is_int($num)) {
            $this->ajaxReturn('请确认输入的最低抵扣金额为100的整数倍！');
        } else {
            $this->ajaxReturn(true);
        }
	}
    /**
     * 九龙港项目金豆赠送活动
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function pageList() {
        $this->getSubNav(8, 6, 40);
        $PointAct = D('PointActivity');
        $ary_data = $this->_get();
		//搜索条件处理
		$array_cond = array();
		//如果根据名称进行搜索
		switch ($ary_data['field']){
			case 1:
				$array_cond["fx_point_activity.pa_title"] = array("LIKE","%" . $ary_data['val'] . "%");
			break;
			case 2:
                if(!empty($ary_data['val'])){
                    $array_cond["fx_point_activity.gc_name"] = $ary_data['val'];
                }
			break;
			default:
			break;
		}
		
		//如果根据活动的有效期进行搜索
		if(isset($ary_data["pa_start_time"]) && "" != $ary_data["pa_start_time"]){
			$array_cond["pa_start_time"] = array("elt",$ary_data["pa_start_time"]);
		}
		if(isset($ary_data["pa_end_time"]) && "" != $ary_data["pa_end_time"]){
			$array_cond["pa_end_time"] = array("egt",$ary_data["pa_end_time"]);
		}
		$array_cond["pa_status"] = array('neq',2);
        $count = $PointAct
		->join("fx_goods_category as gc on(gc.gc_id=fx_point_activity.gc_id)")
        ->join("fx_members as m on(m.m_id=fx_point_activity.m_id)")
        ->where($array_cond)->count();
        $Page = new Page($count, 20);
		$ary_datalist = $PointAct->field('gc.gc_name,m.m_name,fx_point_activity.*')
		->join("fx_goods_category as gc on(gc.gc_id=fx_point_activity.gc_id)")
        ->join("fx_members as m on(m.m_id=fx_point_activity.m_id)")
		->where($array_cond)
		->order(array("pa_order"=>"asc",'pa_update_time'=>'desc'))
		->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();
        //dump($ary_datalist);exit;
		$ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
		$this->assign("filter",$ary_data);
        $this->assign($ary_data);	
        $this->display();
    }

    /**
     * 后台添加活动
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function pageAdd() {
        $this->getSubNav(8, 6, 40);
        //获取门店并传递到模板
        $md_where = array(
            'gc_type'=>2,
            'gc_name'=>array('neq','店铺')
        );
		$array_cate = D("GoodsCategory")->where($md_where)->select();
		$this->assign("array_cate",$array_cate);
        $this->display();
    }

    /**
     * 执行新增一条活动
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function doAdd() {
        $ary_data = $this->_post();
        $PointAct = D('PointActivity');
        $ary_data['gc_id'] = trim($ary_data['cats_id']);
        //活动数组
        $ary_data['pa_title'] = trim($ary_data['pa_title']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['gc_id'])) {
            $this->error('店铺不能为空');
        }
        if (empty($ary_data['pa_title'])) {
            $this->error('活动名称不存在');
        }
        //活动标题必须输入且不能大于250个字符
        if(strlen($ary_data['pa_title'])>250){
        	$this->error('活动名称不能大与250个字符');
        }
        //验证活动名称是否重复
        if (false != $PointAct ->where(array('pa_title' => $ary_data['pa_title']))->find()) {
            $this->error('活动名称已被使用');
        }
        $ary_data['m_name'] = trim($ary_data['m_name']);
        //验证会员是否存在
        if(empty($ary_data['m_name'])){
        	 $this->error('请先选择绑定的会员代码');
        }
        $mid = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_name'=>$ary_data['m_name']))->getField('m_id');
        if(empty($mid)){
        	$this->error('会员信息不存在');
        }
        $ary_data['m_id'] = $mid;
        
        $where_sp = "1=1";
        $where_sp .= " and m_id=".$mid;
        $where_sp .= " and pa_end_time > current_timestamp()";
        $where_sp .= " and pa_status != 2";
        $pa_count = $PointAct->where($where_sp)->count();
        
        if ($pa_count != 0) {
            $this->error('此会员已被其他活动使用');
        }
        if($ary_data['pa_start_time']){
        	$ary_data['pa_start_time'] = $ary_data['pa_start_time'];
        }
        if($ary_data['pa_end_time']){
        	$ary_data['pa_end_time'] = $ary_data['pa_end_time'];
        }   
        if($ary_data['pa_start_time']>$ary_data['pa_end_time']){
        	$this->error('活动开始时间不能大于活动结束时间！');
        } 
        if($ary_data['pa_day_times']){
        	$ary_data['pa_day_times'] = intval($ary_data['pa_day_times']);
        }else{
        	$this->error('每天赠送次数必须输入！');
        }     
        if($ary_data['pa_times_num']){
        	$ary_data['pa_times_num'] = intval($ary_data['pa_times_num']);
        }else{
        	$this->error('每次赠送金豆数量必须输入！');
        }     
        if($ary_data['pa_how_time']){
        	$ary_data['pa_how_time'] = intval($ary_data['pa_how_time']);
        }else{
        	$this->error('多长时间赠送一次必须输入！');
        }     
		if($ary_data['pa_order']){
        	if(is_numeric($ary_data['pa_order'])){
        		$ary_data['pa_order'] = $ary_data['pa_order'];
        	}
        }  
        if($ary_data['pa_status']){
        	$ary_data['pa_status'] = intval($ary_data['pa_status']);
        }  
        if($ary_data['pa_desc']){
        	$ary_data['pa_desc'] = $ary_data['pa_desc'];
        }  
        $ary_data['pa_create_time'] = date("Y-m-d H:i:s");
        $ary_data['pa_update_time'] = date("Y-m-d H:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $PointAct->data($ary_data)->add();
        //echo $PointAct->getLastSql();exit;
        if (!$res_return) {
        	$trans->rollback();
            $this->error('活动生成失败');
        } else {
			$trans->commit();
            $this->success('活动生成成功', U('Admin/Point/pageList'));
        }
    }
    /**
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function doDel() {
        $pa_id = $this->_param('pa_id');
        if(empty($pa_id)){
        	$this->error('请先选择要删除的活动');
        }
        $mix_id = explode(',',$pa_id);
        if (is_array($mix_id)) {
            //批量删除
            $where = array('pa_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('pa_id' => $mix_id);
        }
        $PointAct = D('PointActivity');
        $res_return = $PointAct->where($where)->data(array('pa_status'=>2,'pa_update_time'=>date('Y-m-d H:i:s')))->save();
        if (false == $res_return) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

    /**
     * 编辑活动
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function pageEdit() {
        $this->getSubNav(8, 6, 40);
        $int_pa_id = $this->_get('pa_id');
        $PointAct = D('PointActivity');
        $ary_data = $PointAct->field('gc.gc_id,m.m_name,fx_point_activity.*')
		->join("fx_goods_category as gc on(gc.gc_id=fx_point_activity.gc_id)")
        ->join("fx_members as m on(m.m_id=fx_point_activity.m_id)")
        ->where(array('pa_id'=>$int_pa_id))->find();
        if(false == $ary_data){
            $this->error('活动参数错误');
        }else{
            //获取门店并传递到模板
            $md_where = array(
                'gc_type'=>2,
                'gc_name'=>array('neq','店铺')
            );
            $array_category = D("GoodsCategory")->where($md_where)->select();
			$this->assign("array_category",$array_category);
			$this->assign('info',$ary_data);			
            $this->display();
        }
    }

    /**
     * 执行修改活动
     * @author huangcaijin <huangcaijin@guanyisoft.com>
     * @date 2014-09-29
     */
    public function doEdit(){
        $PointAct = D('PointActivity');
		$ary_data = $this->_post();
		$int_pa_id = $ary_data['pa_id'];
        $ary_data['gc_id'] = trim($ary_data['cats_id']);
        //活动数组
        $ary_data['pa_title'] = trim($ary_data['pa_title']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $pa_count = $PointAct->where(array('pa_id'=>$int_pa_id))->count();
        if ($pa_count == 0) {
            $this->error('活动不存在或已删除');
        } 
        //更新条件
        $pa_where = array();
        $pa_where['pa_id'] =  $int_pa_id; 
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['pa_title'])) {
            $this->error('活动名称不存在');
        }
        //活动标题必须输入且不能大约250个字符
        if(strlen($ary_data['pa_title'])>250){
        	$this->error('活动名称不能大与250个字符');
        }
        //验证活动名称是否重复
		$where = array('pa_title' => $ary_data['pa_title']);
        $where['pa_id'] = array('neq',$int_pa_id);
        if (false != $PointAct ->where($where)->find()) {
            $this->error('活动名称已被使用');
        }
        $ary_data['gc_id'] = trim($ary_data['gc_id']);
        //验证店铺ID是否存在
        if(empty($ary_data['gc_id'])){
        	 $this->error('请先选择店铺信息');
        }
        $goods_count = M('GoodsCategory',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gc_id'=>$ary_data['gc_id']))->count();
        if($goods_count == 0){
        	$this->error('店铺信息不存在');
        }
        $good_where = array('gc_id'=>$ary_data['gc_id'],'pa_status'=>array('neq',2));
        $good_where['pa_id'] = array('neq',$int_pa_id);
        $PointAct_count = $PointAct->where($good_where)->count();
        if($PointAct_count != 0){
        	$this->error('此店铺已参加赠金豆活动');
        }       
        $ary_data['m_name'] = trim($ary_data['m_name']);
        //验证会员是否存在
        if(empty($ary_data['m_name'])){
        	 $this->error('请先选择绑定的会员代码');
        }
        $mid = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_name'=>$ary_data['m_name']))->getField('m_id');
        if(empty($mid)){
        	$this->error('会员信息不存在');
        }
		
        if($ary_data['pa_start_time']){
        	$ary_data['pa_start_time'] = $ary_data['pa_start_time'];
        }
        if($ary_data['pa_end_time']){
        	$ary_data['pa_end_time'] = $ary_data['pa_end_time'];
        }   
        if($ary_data['pa_start_time']>$ary_data['pa_start_time']){
        	$this->error('活动开始时间大于活动实效时间时间！');
        } 
        if($ary_data['pa_day_times']){
        	$ary_data['pa_day_times'] = intval($ary_data['pa_day_times']);
        }else{
        	$this->error('每天赠送次数必须输入！');
        }     
        if($ary_data['pa_times_num']){
        	$ary_data['pa_times_num'] = intval($ary_data['pa_times_num']);
        }else{
        	$this->error('每次赠送金豆数量必须输入！');
        }     
        if($ary_data['pa_how_time']){
        	$ary_data['pa_how_time'] = intval($ary_data['pa_how_time']);
        }else{
        	$this->error('多长时间赠送一次必须输入！');
        }     
		if($ary_data['pa_order']){
        	if(is_numeric($ary_data['pa_order'])){
        		$ary_data['pa_order'] = $ary_data['pa_order'];
        	}
        }  
        if($ary_data['pa_status']){
        	$ary_data['pa_status'] = intval($ary_data['pa_status']);
        }  
        if($ary_data['pa_desc']){
        	$ary_data['pa_desc'] = $ary_data['pa_desc'];
        }  
        $ary_data['pa_update_time'] = date("Y-m-d H:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $PointAct->data($ary_data)->where($pa_where)->save();
        if (!$res_return) {
        	 $trans->rollback();
            $this->error('活动修改失败');
        }else{ 	
        	$trans->commit();
            $this->success('活动修改成功', U('Admin/Point/pageList'));
        }
    }
}
