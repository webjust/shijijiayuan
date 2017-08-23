<?php

/**
 * 后台优惠券控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-06
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CouponAction extends AdminAction {

    /**
     * 后台优惠券控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
        $this->setTitle(' - ' . L('MENU4_1'));
    }

    /**
     * 后台默认控制器，重定向到列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index() {
        $this->redirect(U('Admin/Coupon/pageList'));
    }
	
    /**
     * 优惠券设置页面
     * @author zhuwenwei 
     * @date 2015-07-27
     */
    public function rulesSet(){
        $this->getSubNav(5, 1, 50);
        D('SysConfig')->getCfg('COUPON_SET','COUPON_AUTO_OPEN','0','是否启用优惠券功能');
        $ary_coupon_data = D('SysConfig')->getCfgByModule('COUPON_SET');
        $this->assign($ary_coupon_data);
        $this->display();
    }
	
	/**
     * 保存优惠券设置
     * @author zhuwenwei
     * @date 2015-07-27
     */
    public function doCouponSet(){
        $ary_post = $this->_post();
        foreach ($ary_post as $name=>$set_val){
            D('SysConfig')->setConfig('COUPON_SET',$name,$set_val);
        }
        $this->success('保存成功');
    }
	
    /**
     * 后台优惠券列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageList() {
        $this->getSubNav(5, 1, 10);
        $Coupon = D('Coupon');

		//搜索条件处理
		$array_cond = array();
		
		//如果根据优惠券名称进行搜索
		if(isset($_GET["c_name"]) && $_GET["c_name"] != ""){
			$array_cond["c_name"] = array("LIKE","%" . $_GET["c_name"] . "%");
		}
		
		//如果根据优惠券券号进行搜索
		if(isset($_GET["c_sn"]) && $_GET["c_sn"] != ""){
			$array_cond["c_sn"] = array("LIKE","%" . $_GET["c_sn"] . "%");
		}
		
		//如果根据优惠券的有效期进行搜索
		if(isset($_GET["starttime"]) && "" != $_GET["starttime"]){
			$array_cond["c_start_time"] = array("egt",$_GET["starttime"]);
		}
		
		if(isset($_GET["endtime"]) && "" != $_GET["endtime"]){
			$array_cond["c_end_time"] = array("elt",$_GET["endtime"]);
		}
		if(isset($_GET["c_type"]) && "" != $_GET["c_type"]){
			$array_cond["c_type"] = intval($_GET["c_type"]);
		}		
        $count = $Coupon->where($array_cond)->count();
        $Page = new Page($count, 15);
		$datalist = $Coupon->where($array_cond)->order(array("c_id"=>"desc"))->limit($Page->firstRow . ',' . $Page->listRows)->select();
	   foreach($datalist as $key => $val){
			if($val["c_user_id"] > 0){
				$datalist[$key]["m_name"] = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$val["c_user_id"]))->getField("m_name");
			}
		}
		$data['list'] = $datalist;
        $data['page'] = $Page->show();
		$this->assign("filter",$_GET);
        $this->assign($data);
        $this->display();
    }

    /**
     * 后台添加优惠券
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageAdd() {
        $this->getSubNav(5, 1, 20);
        //查询分组
        $ary_goods = D('GoodsGroup')->getGoodsGroup();
        $this->assign('goodsgroup',$ary_goods);
        $this->display();
    }

    /**
     * 判断优惠卷SN序列号是否已经存在
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function getCheck() {
        $sn = $this->_get('c_sn');
        if (empty($sn)) {
            $this->ajaxReturn('请输入序列号');
        } else {
            $Coupon = D('Coupon');
            $ary_result = $Coupon->where(array('c_sn' => $sn))->find();
            if (false == $ary_result) {
                //SN未被使用，返回true
                $this->ajaxReturn(true);
            } else {
                $this->ajaxReturn('该SN已被使用');
            }
        }
    }

    /**
     * 执行新增一条优惠卷
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doAdd() {
        $data = $this->_post();
        /*echo '<pre>';
        print_r($data);exit;*/
        $Coupon = D('Coupon');
        $obj_related_coupon_goods_group = D('RelatedCouponGoodsGroup');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['c_name'])) {
            $this->error('优惠券名称不存在');
        }
	    //判断优惠券名称是否存在
		$name_count = $Coupon->where(array('c_name' => $data['c_name'],'c_is_use'=>0,'c_end_time'=>array('egt',date('Y-m-d H:i:s'))))->field('c_id')->find();
		if(count($name_count)>0){
			$this->error('优惠券名称已存在');
		}
        if (empty($data['c_sn'])) {
            $this->error('优惠券序列号不存在');
        }

        if (false != $Coupon->where(array('c_sn' => $data['sn']))->find()) {
            $this->error('优惠券序列号已被使用');
        }
		

        if ((!empty($data['c_start_time']) && !empty($data['c_end_time'])) && (strtotime($data['c_start_time']) > strtotime($data['c_end_time']) )) {
            $tmp = $data['c_start_time'];
            $data['c_start_time'] = $data['c_end_time'];
            $data['c_end_time'] = $tmp;
        }

        if (!empty($data['c_user_id']) && !empty($data['m_name'])) {
            $ary_member = D('Members')->field(array('m_id'))->where(array('m_name' => $data['m_name']))->find();
            $data['c_user_id'] = (false == $ary_member) ? 0 : (int) $ary_member['m_id'];
        } else {
            $data['c_user_id'] = 0;
        }

        if ($data['c_condition'] == 'all') {
            $data['c_condition_money'] = 0;
        }
        unset($data['c_condition']);
        unset($data['m_name']);
        $data['c_create_time'] = date("Y-m-d h:i:s");
		$data['c_type'] = $data['c_type'];
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
          //开启事务
        $obj_related_coupon_goods_group->startTrans();
        $int_cid = $Coupon->data($data)->add();
        foreach($data['gg_name'] as $v){
            $ary_insert = array('c_id'=>(int)$int_cid,'gg_id'=>(int)$v);
            $int_rcgg_id = $obj_related_coupon_goods_group->data($ary_insert)->add();
            if(!$int_rcgg_id) {
                $obj_related_coupon_goods_group->rollback();
                $this->error('优惠券生成失败');
            }
        }
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"优惠券生成成功",'优惠券为：'.$data['c_name']));
		//提交事务
        $obj_related_coupon_goods_group->commit(); 
        $this->success('优惠券生成成功', U('Admin/Coupon/pageList'));
    }

    /**
     * 批量新增优惠券
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageAuto() {
        D('Coupon')->doCouponUpdate();
        $ary_res = D('GoodsGroup')->getGoodsGroup();
       // echo "<pre>";print_r($ary_res);exit;
        $this->assign('goodsgroup',$ary_res);
        $this->getSubNav(5, 1, 30);
        $this->display();
    }

    /**
     * 执行批量新增
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doAuto() {
        $data = $this->_post();
        $Coupon = D('Coupon');
        $obj_related_coupon_goods_group = D('RelatedCouponGoodsGroup');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['c_name'])) {
            $this->error('优惠券名称不存在');
        }
	    //判断优惠券名称是否存在
		$name_count = $Coupon->where(array('c_name' => $data['c_name'],'c_is_use'=>0,'c_end_time'=>array('egt',date('Y-m-d H:i:s'))))->field('c_id')->find();
		if(count($name_count)>0){
			$this->error('优惠券名称已存在，请更换名称');
		}
        if ((!empty($data['c_start_time']) && !empty($data['c_end_time'])) && (strtotime($data['c_start_time']) > strtotime($data['c_end_time']) )) {
            $tmp = $data['c_start_time'];
            $data['c_start_time'] = $data['c_end_time'];
            $data['c_end_time'] = $tmp;
        }

        if ($data['c_condition'] == 'all') {
            $data['c_condition_money'] = 0;
        }
        //批量生成 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //todo 批量生成比较多这里可能会慢，还需要优化成分批操作的
         //开启事务
        $obj_related_coupon_goods_group->startTrans();
        $ary_res = $Coupon->autoAdd($data['c_name'], $data['c_money'], $data['c_num'], $data['c_long'], $data['c_memo'], $data['c_sn_prefix'], $data['c_sn_suffix'], $data['c_start_time'], $data['c_end_time'], $data['c_condition_money'],0,$data['c_type']);
        if(empty($ary_res) || count($ary_res) != $data['c_num']) {
            //如果返回的结果为空 或 生成的优惠券数量与需要生成的优惠券数量不相等时 回滚事务
            $obj_related_coupon_goods_group->rollback();
			$this->error('批量生成优惠券失败');
        }
        $data['c_id'] = $ary_res;
        if(empty($data['c_id'])){
            //如果获取`c_id`字段字段值失败时 事务回滚
            $obj_related_coupon_goods_group->rollback();
            $this->error('批量生成优惠券失败');
        }
		//添加优惠券管理分组信息
		//不勾选代表所有会员
		if(!empty($data['gg_name'])){
			foreach($data['c_id'] as $int_c_id){
				foreach($data['gg_name'] as $int_gg_id){
					$ary_insert = array('gg_id'=>(int)$int_gg_id,'c_id'=>(int)$int_c_id);
					$int_rrgc_id = $obj_related_coupon_goods_group->data($ary_insert)->add();
				}
				if(!$int_rrgc_id){
					//如果向 商品优惠券关联表 添加数据失败 事务回滚
					$obj_related_coupon_goods_group->rollback();
					$this->error('批量生成优惠券失败');
				}
			}		
		}
        //提交事务
        $obj_related_coupon_goods_group->commit();
 		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"批量生成优惠券成功",'优惠券为：'.$data['c_name']));       
        $this->success('批量生成优惠券成功', U('Admin/Coupon/pageList'));
    }

    /**
     * 删除优惠券，get接收c_id，可以是数组也可以是单个id
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doDel() {
        $mix_id = $this->_get('c_id');
        if (is_array($mix_id)) {
            //批量删除
            $where = array('c_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('c_id' => $mix_id);
        }
		$tmp_mix_id = implode(',',$mix_id);
        $Coupon = D('Coupon');
        $res = $Coupon->where($where)->delete();
        if (false == $res) {
            $this->error('删除失败');
        } else {
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除优惠券",'优惠券为：'.$tmp_mix_id));			
            $this->success('删除成功');
        }
    }
    /**
     * 删除优惠券规则，get接收c_id，可以是数组也可以是单个id
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-16
     */
    public function doRulesDel() {
        $mix_id = $this->_get('rd_id');
        if (is_array($mix_id)) {
            //批量删除
            $where = array('rd_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('rd_id' => $mix_id);
        }
        $RedEnevlope = D('RedEnevlope');
        $res = $RedEnevlope->where($where)->delete();
        if (false == $res) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }
	
    /**
     * 编辑优惠券
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function pageEdit() {
        $this->getSubNav(5, 1, 10, '编辑优惠券');
        $c_id = $this->_get('c_id');
        $ary_res_gg_id = M('related_coupon_goods_group',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_id'=>$c_id))->select();
       //定义一个空数组
       $ary_gg_id = array();
       foreach($ary_res_gg_id as $ggid){
            $ary_gg_id[] = $ggid['gg_id'];     
        }
        //调用模型获取商品分组表数据 
        $ary_goods = D('GoodsGroup')->getGoodsGroup();
        $this->assign('ggid',$ary_gg_id);
        $this->assign('goodsgroup',$ary_goods);
        $Coupon = D('Coupon');
        $data['info'] = $Coupon->join("fx_members on fx_members.m_id = fx_coupon.c_user_id")->where(array('c_id'=>$c_id))->find();
        if(false == $data['info']){
            $this->error('优惠券参数错误');
        }else{
            $this->assign($data);
            //dump($data);exit;
            $this->display();
        }
        
    }

    /**
     * 执行修改优惠券
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function doEdit(){
        $data = $this->_post();
        //echo "<pre>";print_r($data);die;
        $Coupon = D('Coupon');
        $obj_related_coupon_goods_group = M('related_coupon_goods_group',C('DB_PREFIX'), 'DB_CUSTOM');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($data['c_name'])) {
            $this->error('优惠券名称不存在');
        }

        if ((!empty($data['c_start_time']) && !empty($data['c_end_time'])) && (strtotime($data['c_start_time']) > strtotime($data['c_end_time']) )) {
            $tmp = $data['c_start_time'];
            $data['c_start_time'] = $data['c_end_time'];
            $data['c_end_time'] = $tmp;
        }

        if (!empty($data['c_user_id']) && !empty($data['m_name'])) {
            $ary_member = D('Members')->field(array('m_id'))->where(array('m_name' => $data['m_name']))->find();
            $data['c_user_id'] = (false == $ary_member) ? 0 : (int) $ary_member['m_id'];
        } else {
            $data['c_user_id'] = 0;
        }

        if ($data['c_condition'] == 'all') {
            $data['c_condition_money'] = 0;
        }

        unset($data['c_condition']);
        unset($data['m_name']);
        $data['c_create_time'] = date("Y-m-d h:i:s");
		$data['c_type'] = $data['c_type'];
        //修改数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_c_id = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('c_id'=>$data['c_id']))->field('c_id')->select();
        //开启事务
        $obj_related_coupon_goods_group->startTrans();
        if(isset($int_c_id)){
            $bool = $obj_related_coupon_goods_group->where(array('c_id'=>$data['c_id']))->delete();
            if(!$bool){
            //事务回滚
             $obj_related_coupon_goods_group->rollback();
             $this->error('优惠券修改失败');
            }
        }
        foreach($data['gg_name'] as $rggid){
            $obj_related_coupon_goods_group->where(array('c_id'=>$data['c_id']))->data(array('gg_id'=>$rggid,'c_id'=>$data['c_id']))->add();
        }   
        $res = $Coupon->where(array('c_id'=>$data['c_id']))->data($data)->save();
        if (!$res) {
            $this->error('优惠券修改失败');
        } else {
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"优惠券修改成功",'优惠券为：'.$data['c_id']));	
            $this->success('优惠券修改成功', U('Admin/Coupon/pageList'));
        }
        //提交事务
         $obj_related_coupon_goods_group->commit();
    }
    /**
     * 选择需要导出的execl的条件
     * @author listen
     * @date 2013-02-21
     */
    public function pageGetExeclCoupon(){
         $this->getSubNav(5, 1, 10, '优惠券导出');
        $this->display();
    }
    /**
     * 导出execl
     * @outhor listen
     * @date 2013-02-21
     */
    public function getExeclCoupon(){
        $ary_post = $this->_post();
        $where =  array('c_is_use'=>0);
        if((isset($ary_post['c_sn_prefix']) && $ary_post['c_sn_prefix']!='') || (isset($ary_post['c_sn_suffix']) && $ary_post['c_sn_suffix'] !='')){
            $where['c_sn'] = array('LIKE',$ary_post['c_sn_prefix']."%".$ary_post['c_sn_suffix']);
        }
        if(isset($ary_post['c_money']) && $ary_post['c_money']!=''){
            $where['c_money']=$ary_post['c_money'];
        }
        if(isset($ary_post['c_start_time']) && $ary_post['c_start_time']!=''){
            $where['c_start_time'] = array('EGT',$ary_post['c_start_time']);
        }
        if(isset($ary_post['c_end_time']) && $ary_post['c_end_time']!=''){
             $where['c_end_time'] = array('ELT',$ary_post['c_end_time']);
        }
        $ary_coupon =D('Coupon')->where($where)->order('c_create_time')->select();
        if(!empty($ary_coupon) && is_array($ary_coupon)){
            $header = array('优惠券名称', '编码', '金额或折扣', '有效期(00表示永久有效)', '是否被用','类型');
            $content = array();
            foreach ($ary_coupon as $jsval) {
                if($jsval['c_is_use'] == 0){
                    $is_use = '否';
                }else{
                    $is_use = '是';
                }
				if($jsval['c_type'] == 0){
                    $is_type = '现金券';
                }else{
                    $is_type = '折扣券';
                }
                $content[0][] = array(
                    $jsval['c_name'],
                    $jsval['c_sn'],
                    $jsval['c_money'],
                    $jsval['c_start_time'].' ~ '.$jsval['c_end_time'],
                    $is_use,
					$is_type
                );
            }
            $fields = array('A', 'B', 'C', 'D', 'E', 'F');
            //echo __PUBLIC__ . 'public/upload/excel/';exit;
            //判断目录是否存在  如果不存在   则创建之
            $file_path = APP_PATH.'Public/upload/phpexcel/';
            //$path_file_name= $file_path . $file_name;
            //$file_path=ROOTPATH . $file_path;
            if(!is_dir($file_path)){
                    @mkdir($file_path,0777,1);
            }
            $Export = new Export(date('YmdHis') . '.xls',   $file_path);
            //dump($Export);exit;
            $coupon_feild = $Export->exportExcel($header, $content[0], $fields, $mix_sheet = '优惠券', true);
            header("Location: ".U( '/Public/upload/phpexcel/'.$coupon_feild));
            //$this->success('优惠券导出成功', U( '/Public/upload/phpexcel/'.$coupon_feild));

            exit;
        }else{
            $this->error("暂未匹配到可以导出的优惠券");
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
	
     /**
     * 抢红包规则列表
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-4-28
     */
    public function pageRulesList(){
        $this->getSubNav(5, 1, 32);
        $data = $this->_get();
        if(!empty($data['rd_name']) && isset($data['rd_name'])){
            $where['rd_name'] = array('like','%'.$data['rd_name']."%");
        }
        $count = M('red_enevlope')->where($where)->count();
        $Page = new Page($count, 15);
		$datalist = M('red_enevlope')->field("rd_id,rd_name,rd_start_time,rd_end_time,rd_memo")->where($where)->order(array("rd_id"=>"desc"))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        foreach($datalist as &$val){
            $ary_related = M('related_coupon_red')->where(array('rd_id'=>$val['rd_id']))->select();
            $array_coupon = array();
			$coupon_nums=0;
            $ary_coupon_used = 0;
            $str_coupon_name = '';
            foreach($ary_related as $relVal){
				unset($coupon_where['c_user_id']);
				$coupon_where['c_name'] = array('eq',$relVal['c_name']);
				$coupon_num = D('Coupon')->where($coupon_where)->count();
				$coupon_nums += $coupon_num;
				$coupon_where['c_user_id']  = array('neq',0);
                $ary_coupon_used = D('Coupon')->where($coupon_where)->count();
                $str_coupon_name .= $relVal['c_name'].',';
            }
            $val['is_use_num'] = $ary_coupon_used;
            $val['coupon_name'] = rtrim($str_coupon_name,',');;
            $val['coupon_nums'] += $coupon_nums;
		}
		$data['list'] = $datalist;
        $data['page'] = $Page->show();
        //echo "<pre>";print_r($data);die();
		$this->assign("filter",$_GET);
        $this->assign($data);
        $this->display();
    }
    
    /**
     * 新增抢红包活动规则页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-4-28
     */
    public function pageRulesAdd(){
        $this->getSubNav(5, 1, 35);
        $ary_coupon = D('Coupon')->group('c_name')->field('c_name')->select();
		//已使用的不显示
		$ary_exist_coupon = D('RelatedCouponRed')->group('c_name')->field('c_name')->select();
		$ary_exist_coupons = array();
		foreach($ary_exist_coupon as $v){
			$ary_exist_coupons[] = $v['c_name'];
		}
		unset($ary_exist_coupon);
		foreach($ary_coupon as $ekey=>$val){
			if(in_array($val['c_name'],$ary_exist_coupons)){
				unset($ary_coupon[$ekey]);
			}
		}
        foreach($ary_coupon as $key=>&$val){
            $val['nums'] = D('Coupon')->where(array('c_name'=>$val['c_name'],'c_user_id'=>0))->count();
			//只显示大于0的 wangguibin 2014-07-17
			if($val['nums']<=1){
				unset($ary_coupon[$key]);
			}
		}
        $this->assign('coupon',$ary_coupon);
        $this->display();
    }
    
    public function doRulesAdd(){
        $data = $this->_post();
        //判断当前规则名称是否已经存在
        if(M('red_enevlope')->where(array('rd_name'=>$data['rd_name']))->find()){
            $this->error('规则名称已经存在');
        }
        //验证时间有效性
        if($data['rd_start_time']!='0000-00-00 00:00:00' || $data['rd_end_time']!='0000-00-00 00:00:00'){
            if(strtotime($data['rd_start_time']) > strtotime($data['rd_end_time'])){
                $this->error('开始时间不能小于结束时间');
            }
        }
        //检测选择规则红包是否已经存在
        foreach($data['c_name'] as $val){
            if(M('related_coupon_red')->where(array('c_name'=>$val))->find()){
                $this->error($val.'红包已经在其他规则中');
            }
        }
        M()->startTrans();
        //开始插入
        $array_add_data['rd_name'] = $data['rd_name'];
        $array_add_data['rd_start_time'] = $data['rd_start_time'];
        $array_add_data['rd_end_time'] = $data['rd_end_time'];
        $array_add_data['rd_memo'] = $data['rd_memo'];
        $array_add_data['rd_title'] = $data['rd_title'];
        $array_add_data['rd_keywords'] = $data['rd_keywords'];
        $array_add_data['rd_description'] = $data['rd_description'];
        $array_add_data['rd_is_status'] = empty($data['rd_is_status']) ? 0 : 1;
        $rd_id = M('red_enevlope')->add($array_add_data);
        if(false === $rd_id){
            M()->rollback();
            $this->error('规则基本表插入失败');
        }
        foreach($data['c_name'] as $val){
            if(false === M('related_coupon_red')->add(array('rd_id'=>$rd_id,'c_name'=>$val))){
                M()->rollback();
                $this->error('规则与红包关联表插入失败');
            }
        }
        M()->commit();
        $this->success('规则插入成功', U('Admin/Coupon/pageRulesList'));
    }
    
    public function pageRuleEdit(){
        $this->getSubNav(5, 1, 32);
        $rd_id = $_GET['rd_id'];
        $datalist = M('red_enevlope')->where(array('rd_id'=>$rd_id))->find();
        $ary_related = M('related_coupon_red')->where(array('rd_id'=>$rd_id['rd_id']))->select();
        $ary_coupon = D('Coupon')->group('c_name')->select();
        foreach($ary_coupon as $key=>&$val){
            foreach($ary_related as $ardVal){
                if(in_array($val['c_name'],$ardVal)){
                    $val['is_checked'] = 1;
                }
            }
            if($val['is_checked'] != 1){
                $val['is_checked'] = 0;
            }
            $val['nums'] = D('Coupon')->where(array('c_name'=>$val['c_name'],'c_user_id'=>0))->count();
			if($val['nums']<=1){
				unset($ary_coupon[$key]);
			}
		}
        $datalist['ary_related'] = $ary_related;
        $datalist['ary_coupon'] = $ary_coupon;
       // echo "<pre>";print_r($datalist);die();
        $this->assign($datalist);
        $this->display();
    }
    
    public function doRulesEdit(){
        $data = $this->_post();
        $rd_id = $data['rd_id'];
        unset($data['rd_id']);
        //验证时间有效性
        if($data['rd_start_time']!='0000-00-00 00:00:00' || $data['rd_end_time']!='0000-00-00 00:00:00'){
            if(strtotime($data['rd_start_time']) > strtotime($data['rd_end_time'])){
                $this->error('开始时间不能小于结束时间');
            }
        }
        //检测选择规则红包是否已经存在
        foreach($data['c_name'] as $val){
            if(M('related_coupon_red')->where(array('c_name'=>$val,'rd_id'=>array('neq',$rd_id)))->find()){
                $this->error($val.'红包已经在其他规则中');
            }
        }
        M()->startTrans();
        //开始插入
        $array_edit_data['rd_start_time'] = $data['rd_start_time'];
        $array_edit_data['rd_end_time'] = $data['rd_end_time'];
        $array_edit_data['rd_memo'] = $data['rd_memo'];
        $array_edit_data['rd_title'] = $data['rd_title'];
        $array_edit_data['rd_keywords'] = $data['rd_keywords'];
        $array_edit_data['rd_description'] = $data['rd_description'];
        $array_edit_data['rd_is_status'] = empty($data['rd_is_status']) ? 0 : 1;
        if(false === M('red_enevlope')->where(array('rd_id'=>$rd_id))->save($array_edit_data)){
            M()->rollback();
            $this->error('规则基本表编辑失败');
        }
        //删除所有关联关系
        M('related_coupon_red')->where(array('rd_id'=>$rd_id))->delete();
        foreach($data['c_name'] as $val){
            if(false === M('related_coupon_red')->add(array('rd_id'=>$rd_id,'c_name'=>$val))){
                M()->rollback();
                $this->error('规则与红包关联表插入失败');
            }
        }
        M()->commit();
        $this->success('规则编辑成功', U('Admin/Coupon/pageRulesList'));
    }

    /**
     * 新增优惠券活动类型
     * 1、异号优惠券活动    主要是抢券使用
     * 2、同号优惠券活动    主要是发放券使用
     * 3、注册送优惠券      主要是注册的时候生成优惠券
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-11-12
     */
    public function pageActivities() {
        $ary_res = D('GoodsGroup')->getGoodsGroup();
        $this->assign('goodsgroup',$ary_res);
        $this->getSubNav(5, 1, 37);
        $this->display();
    }

    /**
     * 优惠券活动列表
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-11-12
     */
    public function pageActList() {
        $this->getSubNav(5, 1, 39);
        $data = $this->_get();
        if(!empty($data['ca_name']) && isset($data['ca_name'])){
            $where['ca_name'] = array('like','%'.$data['ca_name']."%");
        }
        $count = M('CouponActivities')->where($where)->count();
        $Page = new Page($count, 15);
		$datalist = M('CouponActivities')->where($where)->order(array("ca_id"=>"desc"))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
		$data['list'] = $datalist;
        $data['page'] = $Page->show();
		$this->assign("filter",$_GET);
        $this->assign($data);
        $this->display();
    }

    public function getCheckSn() {
        $sn = $this->_get('ca_sn');
        if (empty($sn)) {
            $this->ajaxReturn('请输入同号券编码');
        } else {
            $ary_result = D("CouponActivities")->where(array('ca_sn' => $sn))->find();
            if (false == $ary_result) {
                //SN未被使用，返回true
                $this->ajaxReturn(true);
            } else {
                $this->ajaxReturn('该编码已被使用');
            }
        }
    }

    /**
     * 修改优惠券活动
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-11-12
     */
    public function pageEditAct() {
        $this->getSubNav(5, 1, 39);
        $caid = $this->_get('ca_id');
        if(empty($caid)){
            $this->error("参数错误！");
        }
        $where['ca_id'] = $caid;
        $data = M('CouponActivities')->where($where)->find();
        if($data['ca_type'] != 0){
            $ary_sn = json_decode($data['ca_sn'],1);
            $data['c_long'] = $ary_sn['long'];
            $data['c_sn_prefix'] = $ary_sn['prefix'];
            $data['c_sn_suffix'] = $ary_sn['suffix'];
            unset($data['ca_sn']);
        }
        if(!empty($data['ca_ggid'])){
            $ary_ggid = json_decode($data['ca_ggid'],1);
            $data['ary_ggid'] = $ary_ggid;
        }
        $ary_res = D('GoodsGroup')->getGoodsGroup();
        $this->assign('goodsgroup',$ary_res);
        $this->assign($data);
        $this->display();
    }

    /**
     * 新增，修改活动方法
     */
    public function doPostActivities(){
        $data = $this->_post();
        $CouponInfo = D('CouponActivities');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if(empty($data['ca_name'])) $this->error('优惠券活动名称不能为空');

        if($data['ca_type'] == 0){
            if(empty($data['ca_sn'])) $this->error('同号优惠券编码不能为空');
            unset($data['c_long']);
            unset($data['c_sn_prefix']);
            unset($data['c_sn_suffix']);
        }else{
            if(empty($data['c_long'])) $this->error('优惠券编码长度不能为空');
            $ary_casn = array(
                'long'=>$data['c_long'],
                'prefix'=>$data['c_sn_prefix'],
                'suffix'=>$data['c_sn_suffix']
            );
            $data['ca_sn'] = json_encode($ary_casn);
        }

        if (strtotime($data['ca_start_time']) > strtotime($data['ca_end_time'])) {
            $this->error('优惠券活动开始时间不能大于结束时间');
        }
        if (strtotime($data['c_start_time']) > strtotime($data['c_end_time'])) {
            $this->error('优惠券有效开始时间不能大于结束时间');
        }
        if ($data['c_condition'] == 'all') {
            $data['c_condition_money'] = 0;
        }
        $data['ca_ggid'] = json_encode($data['gg_name']);
        unset($data['gg_name']);
        unset($data['c_condition']);
        unset($data['m_name']);
        $data['ca_status'] = empty($data['ca_status']) ? 1 : 0;
        $data['ca_create_time'] = date("Y-m-d h:i:s");
        //新增或修改数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $caid = $data['ca_id'];
        if(empty($caid)){
            //判断优惠券名称是否存在
            $res_name = $CouponInfo->where(array('ca_name' => $data['ca_name']))->getField('ca_id');
            if(!empty($res_name)){
                $this->error('优惠券活动名称已存在');
            }
            if($data['ca_type'] == 0){
                $res_sn = $CouponInfo->where(array('ca_sn' => $data['ca_sn']))->getField('ca_id');
                if (!empty($res_sn)) {
                    $this->error('优惠券编码或规则已被使用');
                }
            }
            $resAdd = $CouponInfo->data($data)->add();
            if(!$resAdd) $this->error('生成优惠券活动失败！请重试');
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"新增优惠券活动成功",'优惠券活动名称为：'.$data['ca_name']));
            $actionname = '新增';
        }else{
            unset($data['ca_id']);
            $resUpdate = $CouponInfo->where(array('ca_id'=>$caid))->save($data);
            if(!$resUpdate) $this->error('生成优惠券活动失败！请重试');
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"修改优惠券活动成功",'优惠券活动名称为：'.$data['ca_name']));
            $actionname = '修改';
        }
        $this->success('优惠券活动'.$actionname.'成功', U('Admin/Coupon/pageActList'));
    }

    /*
     * 删除优惠券活动
     */
    public function delCouponAct() {
        $caid = $this->_get('ca_id');
        if (is_array($caid)) {
            //批量删除
            $where = array('ca_id' => array('IN',$caid));
        } else {
            //单个删除
            $where = array('ca_id' => $caid);
        }
        $res = M("CouponActivities")->where($where)->delete();
        if (false == $res) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

}
