<?php
/**
 * 后台会员等级控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author listen 
 * @date 2013-01-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MemberlevelAction extends AdminAction{
	
	public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU5_1'));
    }
    
   /**
     * 控制器默认方法，暂时重定向到会员等级列表
     * @author listen
     * @date 2013-01-16
     * @todo 需要重定向到会员等级列表页的
     */
    public function index(){
        $this->redirect(U('Admin/Memberlevel/pageList'));
    }
    /**
     * 会员等级列表
     * @author listen
     * @date 2013-01-16
     */
    public function pageList(){
        $this->getSubNav(6,1,10);
        //$ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $ary_members_level = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status'=>1))->select();
//        if($ary_erp['SWITCH'] == '1' && isset($ary_erp['SWITCH'])){
//            if(!empty($ary_members_level) && is_array($ary_members_level)){
//                foreach($ary_members_level as $mlkey=>$mlval){
//                    if(!empty($mlval['ml_code']) && isset($mlval['ml_code'])){
//                        $top = Factory::getTopClient();
//                        $data = $top->MemberLevelGet(array(
//                            'fields' => array(':all'),
//                            'condition' => "IS_TY='0' and jbdm='".$mlval['ml_code']."'",
//                            'page_size' => 1,
//                            'page_no' => 1
//                        ));
//                        if(!empty($data) && is_array($data)){
//                            $ary_members_level[$mlkey]['accessStatus'] = '0';
//                            $ary_members_level[$mlkey]['accessMsg'] = '';
//                            $arr_data = $data['hyjbs']['hyjb'][0];
//                            if($mlval['ml_code'] != $arr_data['jbdm']){
//                                $ary_members_level[$mlkey]['accessStatus'] = '1';
//                                $ary_members_level[$mlkey]['accessMsg'] = '等级代码';
//                            }
//                            if($mlval['ml_name'] != $arr_data['jbmc']){
//                                $ary_members_level[$mlkey]['accessStatus'] = '1';
//                                $ary_members_level[$mlkey]['accessMsg'] = '等级名称';
//                            }
//                            $ml_zk = sprintf("%.4f",($mlval['ml_discount']/100));
//////                            echo "<pre>";print_r($ml_zk);echo "<br />";
////                            echo "<pre>";print_r($arr_data['jbzk']);
//                            if($ml_zk != $arr_data['jbzk']){
//                                $ary_members_level[$mlkey]['accessStatus'] = '1';
//                                $ary_members_level[$mlkey]['accessMsg'] = '等级折扣';
//                            }
//                        }
//                    }
//                }
//            }
//        }
       // $this->assign("erp",$ary_erp);
        //echo "<pre>";print_r($ary_members_level);exit;
        $this->assign('members_level',$ary_members_level);
        $this->display();
    }
    /**
     * 会员等级添加页面显示
     * @author listen 
     * @date 2013-01-16
     * 
     */
    public function pageAdd(){
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $this->getSubNav(6,1,20);
        $this->assign("erp",$ary_erp);
        $this->display();
    }
    /**
     * 会员等级添加操作
     * @author listen
     * @date 2013-01-16
     */
    public function doAdd(){
        $data = $this->_post();
		$array_insert_data = array();
        
		//验证会员等级名称是否输入
		if(!isset($_POST['ml_name']) || "" == trim($_POST['ml_name'])){
            $this->error('会员等级名称不能为空');
            exit;
        }
    	//验证会员等级代码是否输入
		if(!isset($_POST['ml_code']) || "" == trim($_POST['ml_code'])){
            $this->error('会员等级代码不能为空');
            exit;
        }		
		//验证会员等级名称是否已经存在
		$mixed_array_result = D("MembersLevel")->where(array('ml_name'=>$_POST['ml_name']))->find();
		if(false === $mixed_array_result){
			$this->error("无法验证此会员等级是否存在。");
		}
		if(is_array($mixed_array_result) && !empty($mixed_array_result)){
			$this->error('会员等级名称已经被占用');
			exit;
		}
        $array_insert_data['ml_name'] = $_POST['ml_name'];
		//验证会员等级代码是否已经存在
		$mixed_array_code_result = D("MembersLevel")->where(array('ml_code'=>$_POST['ml_code']))->find();
		if(false === $mixed_array_code_result){
			$this->error("无法验证此会员等级代码是否存在。");
		}
		if(is_array($mixed_array_code_result) && !empty($mixed_array_code_result)){
			$this->error('会员等级代码已经被占用');
			exit;
		}
        $array_insert_data['ml_code'] = $_POST['ml_code'];		
		//验证会员等级折扣是否是合法的数字
		$array_insert_data['ml_discount'] = (isset($_POST["ml_discount"]) && is_numeric($_POST['ml_discount']) && $_POST['ml_discount'] > 0 && $_POST['ml_discount'] <= 100)?$_POST["ml_discount"]:100;
		
		//是否是默认等级
		$array_insert_data["ml_default"] = 0;
		if(isset($_POST["ml_default"]) && 1 == $_POST["ml_default"]){
			$array_insert_data["ml_default"] = 1;
		}
        
        //晋升条件
        if(is_numeric($_POST['ml_up_fee'])){
            $array_insert_data['ml_up_fee'] = $_POST['ml_up_fee'];
        }else{
            $this->error('晋升条件不能为空且只支持整数!');
            return false;
        }
        //返点比例
        if(is_numeric($_POST['ml_rebate']) && $_POST['ml_rebate'] <= 100){
            $array_insert_data['ml_rebate'] = $_POST['ml_rebate'];
        }else{
            $this->error('返点比例不能为空且小于100整数!');
            return false;
        }
        //是否包邮
        $array_insert_data["ml_free_shipping"] = 0;
		if($_POST["ml_free_shipping"]){
			$array_insert_data["ml_free_shipping"] = 1;
		}
		
		//其他字段的赋值
		$array_insert_data["ml_status"] = 1;
		$array_insert_data['ml_create_time'] = date("Y-m-d h:i:s");
		$array_insert_data['ml_update_time'] = date("Y-m-d h:i:s");
        
		//事务开始
		D("MembersLevel")->startTrans();
        //会员等级基本数据存入数据库
		$mixed_ml_id = D("MembersLevel")->add($array_insert_data);
        if(false === $mixed_ml_id){
			D("MembersLevel")->rollback();
			$this->error("会员等级添加失败。");
		}
		
		//如果当前会员等级是默认的会员等级，则将表中其他等级全部修改为非默认等级
		if(1 == $array_insert_data["ml_default"]){
			$array_cond = array("ml_id"=>array("neq",$mixed_ml_id));
			$mixed_modify_result = D("MembersLevel")->where($array_cond)->save(array("ml_default"=>0));
			if(false === $mixed_modify_result){
				D("MembersLevel")->rollback();
				$this->error("修改默认会员等级时遇到错误。");
			}
		}
		
		//事务提交
		D("MembersLevel")->commit();
		D('Gyfx')->deleteOneCache('members_level',$ary_field=null, array('ml_default'=>1));
		$this->success('会员等级添加加成功', U('Admin/Memberlevel/pageList'));
		exit;
    }
    /**
     * 会员等级编辑页面显示
     * @author listen   
     * @date 2013-01-16
     */
    public function pageEdit(){
        $this->getSubNav(6,1,10,'等级修改');
        $data = $this->_get();
        //验证会员等级ID传参是否正确
		if(!isset($data['mlid']) || !is_numeric($data['mlid'])){
            $this->error('等级参数错误');
        }
		
		//根据会员等级ID获取会员等级详细信息
		$ary_memberlevel = D("MembersLevel")->where(array('ml_id'=>$data['mlid']))->find();
		if(false === $ary_memberlevel){
			$this->error("获取会员等级详细信息时遇到错误。");
		}
		if(!is_array($ary_memberlevel) || empty($ary_memberlevel)){
			$this->error("您要编辑的会员等级信息不存在。");
		}
		
		//会员等级信息传递到模板并渲染输出
		$this->assign('level',$ary_memberlevel); 
		$this->display();
    }
	
    /**
     * 会员等级编辑操作
     * @author listen
     * @date 2013-01-16
     * 
     */
    public function doEdit(){
		//验证是否指定要修改的会员等级ID
		if(!isset($_POST["ml_id"]) || !is_numeric($_POST["ml_id"])){
			$this->error('会员等级ID未指定，会员等级修改失败。');
		}
		$int_ml_id = $_POST["ml_id"];
		
		//验证会员等级名称是否输入正确
        if(!isset($_POST['ml_name']) || "" == trim($_POST['ml_name'])){
            $this->error('会员等级名称不能为空');
        }
		//验证会员等级是否重复
		$array_cond = array("ml_id"=>array("neq",$int_ml_id),"ml_name"=>trim($_POST['ml_name']));
		$array_result = D("MembersLevel")->where($array_cond)->find();
		if(is_array($array_result) && count($array_result) > 0){
			$this->error('会员等级名称已经被占用。');
		}
		
		$array_modify_data = array();
		$array_modify_data["ml_name"] = trim($_POST['ml_name']);
		
		//验证会员等级代码是否重复
// 		$array_cond = array("ml_id"=>array("neq",$int_ml_id),"ml_code"=>trim($_POST['ml_code']));
// 		$array_result = D("MembersLevel")->where($array_cond)->find();
// 		if(is_array($array_result) && count($array_result) > 0){
// 			$this->error('会员等级代码已经被占用。');
// 		}
		
		$array_modify_data["ml_code"] = trim($_POST['ml_code']);
				
		//验证会员等级折扣是否输入正确
		$array_modify_data["ml_discount"] = (isset($_POST["ml_discount"]) && is_numeric($_POST["ml_discount"]) && $_POST["ml_discount"]>0 && $_POST["ml_discount"]<=100)?$_POST["ml_discount"]:100;
		
		//是否是默认等级
		if(isset($_POST["ml_default"]) && in_array($_POST["ml_default"],array(0,1))){
			$array_modify_data["ml_default"] = $_POST["ml_default"];
		}
		
		//晋升条件
        if(is_numeric($_POST['ml_up_fee'])){
            $array_modify_data['ml_up_fee'] = $_POST['ml_up_fee'];
        }else{
            $this->error('晋升条件不能为空且只支持整数!');
            return false;
        }
        //返点比例
        if(is_numeric($_POST['ml_rebate']) && $_POST['ml_rebate'] <= 100){
            $array_modify_data['ml_rebate'] = $_POST['ml_rebate'];
        }else{
            $this->error('返点比例不能为空且小于100整数!');
            return false;
        }
        //是否包邮
        $array_modify_data["ml_free_shipping"] = 0;
		if($_POST["ml_free_shipping"]){
			$array_modify_data["ml_free_shipping"] = 1;
		}
        //其他字段赋值
		$array_modify_data["ml_status"] = 1;
		$array_modify_data['ml_update_time'] = date('Y-m-d h:i:s');
        
		//数据更新保存到数据库中
		D("MembersLevel")->startTrans();
		$array_cond = array("ml_id"=>$int_ml_id);
		$mixed_result = D("MembersLevel")->where($array_cond)->save($array_modify_data);
		if(false === $mixed_result){
			D("MembersLevel")->rollback();
			$this->error("会员等级资料保存失败。");
		}
		
		//如果当前会员等级是默认等级，则需要将其他会员等级修改为非默认等级
		if(1 == $array_modify_data["ml_default"]){
			$array_cond = array("ml_id"=>array("neq",$int_ml_id));
			$mixed_result = D("MembersLevel")->where($array_cond)->save(array("ml_default"=>0));
			if(false === $mixed_result){
				D("MembersLevel")->rollback();
				$this->error("更新默认会员等级时遇到错误。");
			}
		}
		
		//事务提交
		D("MembersLevel")->commit();
		D('Gyfx')->deleteOneCache('members_level',array('ml_status','ml_discount'), array('ml_id'=>$int_ml_id), $ary_order=null,3600);
		D('Gyfx')->deleteOneCache('members_level',$ary_field=null, array('ml_default'=>1));
		$this->success('会员等级更新成功', U('Admin/Memberlevel/pageList'));
		exit;
    }
	
    /**
     * 删除等级
     * @author listen   
     * @date 2013-01-16
     */
    public function doDel(){
        $ml_id = $this->_get('ml_id');
        if (is_array($ml_id)) {
            //批量删除
            $where = array('ml_id' => array('IN',$ml_id));
        } else {
            //单个删除
            $where = array('ml_id' => $ml_id);
        }
        $ary_where = array();
        $ary_where['ml_id'] = $ml_id;
        $count = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        if($count > 0){
            $this->error("该会员等级已被使用，不可删除！");
        }else{
            $res = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->delete();
            if (false == $res) {
                $this->error('删除失败');
            } else {
				D('Gyfx')->deleteOneCache('members_level',$ary_field=null, array('ml_default'=>1));
                $this->success('删除成功');
            }
            
        }
    }
    
    /**
     * 同步会员等级
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    public function synMemberLevelOne(){
        $ary_post = $this->_post();
        $level = new ErpMembersLevel();
        if(isset($ary_post['guid']) && !empty($ary_post['guid'])) $guid = $ary_post['guid'];
        else   $guid = '';
        $ary_data = $level->synMemberLevelOne($ary_post['page_size'],$ary_post['page_no'],$guid);
        echo json_encode($ary_data);exit;
    }
    
    /**
     * 获取会员等级个数
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    public function showMemberLevelCount(){
        $level = new ErpMembersLevel();
        $ary_data = $level->getMembersLevelCount();
        echo $ary_data;
    }
    
    /**
     * 校验等级代码是否重复
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    public function checkMemberLevelEdit(){
        $ary_get = $this->_param();
        $level = D("MembersLevel");
        $where = array();
        $where[$ary_get['filed']]    = $ary_get[$ary_get['filed']];
        if(!empty($ary_get['type']) && $ary_get['type']=='edit'){
        	if(!empty($ary_get['ml_id'])){
        		$where['ml_id']    = array('neq',$ary_get['ml_id']);
        	}
            if(!empty($ary_get['ml_code'])){
        		$where['ml_code']    = array('eq',$ary_get['ml_id']);
        	}            
        }else{
            if(!empty($ary_get['ml_code'])){
        		$where['ml_code']    = array('eq',$ary_get['ml_code']);
        	}          	
        }
       
        $ary_data = $level->where($where)->find();
      
        if(!empty($ary_data)){
          
            $this->ajaxReturn("该".$ary_get['msg']."已经存在");
        }else{
           
            $this->ajaxReturn(true);
        }
    }
    
    /**
     * 设置会员等级默认值
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-4
     */
    public function doEditLevelDefault(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && intval($ary_post['id'])>0){
            $level = D("MembersLevel");
            $ary_data = array(
                'ml_default'    =>'0'
            );
            $where['ml_id'] = array('neq',$ary_post['id']);
            $res_update = $level->where($where)->data($ary_data)->save();
            $res_update1= $level->where('ml_id='.$ary_post['id'])->data(array('ml_default'=>'1'))->save();
            if(FALSE !==$res_update&&FALSE !==$res_update1){
				D('Gyfx')->deleteOneCache('members_level',$ary_field=null, array('ml_default'=>1));
                $this->success("会员等级设置成功");
            }else{
                $this->error("设置会员等级失败");
            }
        }else{
            $this->error("会员等级ID不能为空");
        }
    }
    /**
     * 批量设置会员等级
     * @author listen
     * @date 2013-05-27
     */
    public function doBacthLevel(){

        $int_ml_id = $this->_post('ml_id');
        $int_m_id = $this->_post('m_id');
        $ary_m_id = explode(',',$int_m_id);
        if(!empty($ary_m_id) && is_array($ary_m_id)){
            foreach($ary_m_id as $k=>$v){
                $ary_memners = D('Members')->where(array('m_id'=>$v))->save(array('ml_id'=>$int_ml_id));
                //echo D('Members')->getLastSql();exit;
                if(!$ary_memners){
                    $this->ajaxReturn(false);
                    exit;
                }
            }
        }
        $this->ajaxReturn(true);
    }
}
