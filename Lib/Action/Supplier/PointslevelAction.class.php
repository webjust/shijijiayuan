<?php
/**
 * 后台积分等级控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Hcaijin <huangcaijin@guanyisoft.com> 
 * @date 2014-08-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PointslevelAction extends AdminAction{
	
	public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU7_6'));
    }
    
   /**
     * 控制器默认方法，暂时重定向到积分等级列表
     * @author Hcaijin
     * @date 2014-08-14
     * @todo 需要重定向到积分等级列表页的
     */
    public function index(){
        $this->redirect(U('Admin/Pointslevel/pageList'));
    }
    /**
     * 积分等级列表
     * @author Hcaijin
     * @date 2014-08-14
     */
    public function pageList(){
        $this->getSubNav(8,6,20);
        $ary_points_level = M('points_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pl_status'=>1))->select();
        $this->assign('points_level',$ary_points_level);
        $this->display();
    }
    /**
     * 积分等级添加页面显示
     * @author Hcaijin 
     * @date 2014-08-14
     * 
     */
    public function pageAdd(){
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $this->getSubNav(8,6,30);
        $this->assign("erp",$ary_erp);
        $this->display();
    }
    /**
     * 积分等级添加操作
     * @author Hcaijin
     * @date 2014-08-14
     */
    public function doAdd(){
        $data = $this->_post();
		$array_insert_data = array();
        
		//验证积分等级名称是否输入
		if(!isset($_POST['pl_name']) || "" == trim($_POST['pl_name'])){
            $this->error('积分等级名称不能为空');
            exit;
        }
    	//验证积分等级代码是否输入
		if(!isset($_POST['pl_code']) || "" == trim($_POST['pl_code'])){
            $this->error('积分等级代码不能为空');
            exit;
        }		
		//验证积分等级名称是否已经存在
		$mixed_array_result = D("PointsLevel")->where(array('pl_name'=>$_POST['pl_name']))->find();
		if(false === $mixed_array_result){
			$this->error("无法验证此积分等级是否存在。");
		}
		if(is_array($mixed_array_result) && !empty($mixed_array_result)){
			$this->error('积分等级名称已经被占用');
			exit;
		}
        $array_insert_data['pl_name'] = $_POST['pl_name'];
		//验证积分等级代码是否已经存在
		$mixed_array_code_result = D("PointsLevel")->where(array('pl_code'=>$_POST['pl_code']))->find();
		if(false === $mixed_array_code_result){
			$this->error("无法验证此积分等级代码是否存在。");
		}
		if(is_array($mixed_array_code_result) && !empty($mixed_array_code_result)){
			$this->error('积分等级代码已经被占用');
			exit;
		}
        $array_insert_data['pl_code'] = $_POST['pl_code'];		
		//验证积分等级折扣是否是合法的数字
		$array_insert_data['pl_discount'] = (isset($_POST["pl_discount"]) && is_numeric($_POST['pl_discount']) && $_POST['pl_discount'] > 0 && $_POST['pl_discount'] <= 100)?$_POST["pl_discount"]:100;
		
		//是否是默认等级
		$array_insert_data["pl_default"] = 0;
		if(isset($_POST["pl_default"]) && 1 == $_POST["pl_default"]){
			$array_insert_data["pl_default"] = 1;
		}
        
        //晋升条件
        if(is_numeric($_POST['pl_up_fee'])){
            $array_insert_data['pl_up_fee'] = $_POST['pl_up_fee'];
        }else{
            $this->error('晋升条件不能为空且只支持整数!');
            return false;
        }
		//其他字段的赋值
		$array_insert_data["pl_status"] = 1;
		$array_insert_data['pl_create_time'] = date("Y-m-d h:i:s");
		$array_insert_data['pl_update_time'] = date("Y-m-d h:i:s");
        
		//事务开始
		D("PointsLevel")->startTrans();
        //积分等级基本数据存入数据库
		$mixed_pl_id = D("PointsLevel")->add($array_insert_data);
        //echo D()->getLastSql();exit();
        if(false === $mixed_pl_id){
			D("PointsLevel")->rollback();
			$this->error("积分等级添加失败。");
		}
		
		//如果当前积分等级是默认的积分等级，则将表中其他等级全部修改为非默认等级
		if(1 == $array_insert_data["pl_default"]){
			$array_cond = array("pl_id"=>array("neq",$mixed_pl_id));
			$mixed_modify_result = D("PointsLevel")->where($array_cond)->save(array("pl_default"=>0));
			if(false === $mixed_modify_result){
				D("PointsLevel")->rollback();
				$this->error("修改默认积分等级时遇到错误。");
			}
		}
		
		//事务提交
		D("PointsLevel")->commit();
		$this->success('积分等级添加加成功', U('Admin/Pointslevel/pageList'));
		exit;
    }
    /**
     * 积分等级编辑页面显示
     * @author Hcaijin   
     * @date 2014-08-14
     */
    public function pageEdit(){
        $this->getSubNav(8,6,30,'等级修改');
        $data = $this->_get();
        //验证积分等级ID传参是否正确
		if(!isset($data['mlid']) || !is_numeric($data['mlid'])){
            $this->error('等级参数错误');
        }
		
		//根据积分等级ID获取积分等级详细信息
		$ary_memberlevel = D("PointsLevel")->where(array('pl_id'=>$data['mlid']))->find();
		if(false === $ary_memberlevel){
			$this->error("获取积分等级详细信息时遇到错误。");
		}
		if(!is_array($ary_memberlevel) || empty($ary_memberlevel)){
			$this->error("您要编辑的积分等级信息不存在。");
		}
		
		//积分等级信息传递到模板并渲染输出
		$this->assign('level',$ary_memberlevel); 
		$this->display();
    }
	
    /**
     * 积分等级编辑操作
     * @author Hcaijin
     * @date 2014-08-14
     * 
     */
    public function doEdit(){
		//验证是否指定要修改的积分等级ID
		if(!isset($_POST["pl_id"]) || !is_numeric($_POST["pl_id"])){
			$this->error('积分等级ID未指定，积分等级修改失败。');
		}
		$int_pl_id = $_POST["pl_id"];
		
		//验证积分等级名称是否输入正确
        if(!isset($_POST['pl_name']) || "" == trim($_POST['pl_name'])){
            $this->error('积分等级名称不能为空');
        }
		//验证积分等级是否重复
		$array_cond = array("pl_id"=>array("neq",$int_pl_id),"pl_name"=>trim($_POST['pl_name']));
		$array_result = D("PointsLevel")->where($array_cond)->find();
		if(is_array($array_result) && count($array_result) > 0){
			$this->error('积分等级名称已经被占用。');
		}
		
		$array_modify_data = array();
		$array_modify_data["pl_name"] = trim($_POST['pl_name']);
		
		//验证积分等级代码是否重复
// 		$array_cond = array("pl_id"=>array("neq",$int_pl_id),"pl_code"=>trim($_POST['pl_code']));
// 		$array_result = D("PointsLevel")->where($array_cond)->find();
// 		if(is_array($array_result) && count($array_result) > 0){
// 			$this->error('积分等级代码已经被占用。');
// 		}
		
		$array_modify_data["pl_code"] = trim($_POST['pl_code']);
				
		//验证积分等级折扣是否输入正确
		$array_modify_data["pl_discount"] = (isset($_POST["pl_discount"]) && is_numeric($_POST["pl_discount"]) && $_POST["pl_discount"]>0 && $_POST["pl_discount"]<=100)?$_POST["pl_discount"]:100;
		
		//是否是默认等级
		if(isset($_POST["pl_default"]) && in_array($_POST["pl_default"],array(0,1))){
			$array_modify_data["pl_default"] = $_POST["pl_default"];
		}
		
		//晋升条件
        if(is_numeric($_POST['pl_up_fee'])){
            $array_modify_data['pl_up_fee'] = $_POST['pl_up_fee'];
        }else{
            $this->error('晋升条件不能为空且只支持整数!');
            return false;
        }
        //其他字段赋值
		$array_modify_data["pl_status"] = 1;
		$array_modify_data['pl_update_time'] = date('Y-m-d h:i:s');
        
		//数据更新保存到数据库中
		D("PointsLevel")->startTrans();
		$array_cond = array("pl_id"=>$int_pl_id);
		$mixed_result = D("PointsLevel")->where($array_cond)->save($array_modify_data);
		if(false === $mixed_result){
			D("PointsLevel")->rollback();
			$this->error("积分等级资料保存失败。");
		}
		
		//如果当前积分等级是默认等级，则需要将其他积分等级修改为非默认等级
		if(1 == $array_modify_data["pl_default"]){
			$array_cond = array("pl_id"=>array("neq",$int_pl_id));
			$mixed_result = D("PointsLevel")->where($array_cond)->save(array("pl_default"=>0));
			if(false === $mixed_result){
				D("PointsLevel")->rollback();
				$this->error("更新默认积分等级时遇到错误。");
			}
		}
		
		//事务提交
		D("PointsLevel")->commit();
		$this->success('积分等级更新成功', U('Admin/Pointslevel/pageList'));
		exit;
    }
	
    /**
     * 删除等级
     * @author Hcaijin   
     * @date 2014-08-14
     */
    public function doDel(){
        $pl_id = $this->_get('pl_id');
        if (is_array($pl_id)) {
            //批量删除
            $where = array('pl_id' => array('IN',$pl_id));
        } else {
            //单个删除
            $where = array('pl_id' => $pl_id);
        }
        $ary_where = array();
        $ary_where['pl_id'] = $pl_id;
        $count = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        if($count > 0){
            $this->error("该积分等级已被使用，不可删除！");
        }else{
            $res = M('points_level',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->delete();
            if (false == $res) {
                $this->error('删除失败');
            } else {
                $this->success('删除成功');
            }
            
        }
    }
    
    /**
     * 同步积分等级
     * @author Hcaijin
     * @date 2014-08-14
     */
    public function synMemberLevelOne(){
        $ary_post = $this->_post();
        $level = new ErpPointsLevel();
        if(isset($ary_post['guid']) && !empty($ary_post['guid'])) $guid = $ary_post['guid'];
        else   $guid = '';
        $ary_data = $level->synMemberLevelOne($ary_post['page_size'],$ary_post['page_no'],$guid);
        echo json_encode($ary_data);exit;
    }
    
    /**
     * 获取积分等级个数
     * @author Hcaijin
     * @date 2014-08-14
     */
    public function showMemberLevelCount(){
        $level = new ErpPointsLevel();
        $ary_data = $level->getPointsLevelCount();
        echo $ary_data;
    }
    
    /**
     * 校验等级代码是否重复
     * @author Hcaijin
     * @date 2014-08-14
     */
    public function checkMemberLevelEdit(){
        $ary_get = $this->_param();
        $level = D("PointsLevel");
        $where = array();
        $where[$ary_get['filed']]    = $ary_get[$ary_get['filed']];
        if(!empty($ary_get['type']) && $ary_get['type']=='edit'){
        	if(!empty($ary_get['pl_id'])){
        		$where['pl_id']    = array('neq',$ary_get['pl_id']);
        	}
            if(!empty($ary_get['pl_code'])){
        		$where['pl_code']    = array('eq',$ary_get['pl_id']);
        	}            
        }else{
            if(!empty($ary_get['pl_code'])){
        		$where['pl_code']    = array('eq',$ary_get['pl_code']);
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
     * 设置积分等级默认值
     * @author Hcaijin
     * @date 2018-08-14
     */
    public function doEditLevelDefault(){
        $ary_post = $this->_post();
        if(!empty($ary_post['id']) && intval($ary_post['id'])>0){
            $level = D("PointsLevel");
            $ary_data = array(
                'pl_default'    =>'0'
            );
            $where['pl_id'] = array('neq',$ary_post['id']);
            $res_update = $level->where($where)->data($ary_data)->save();
            $res_update1= $level->where('pl_id='.$ary_post['id'])->data(array('pl_default'=>'1'))->save();
            if(FALSE !==$res_update&&FALSE !==$res_update1){
                $this->success("积分等级设置成功");
            }else{
                $this->error("设置积分等级失败");
            }
        }else{
            $this->error("积分等级ID不能为空");
        }
    }
    /**
     * 批量设置积分等级
     * @author Hcaijin
     * @date 2013-05-27
     */
    public function doBacthLevel(){

        $int_pl_id = $this->_post('pl_id');
        $int_m_id = $this->_post('m_id');
        $ary_m_id = explode(',',$int_m_id);
        if(!empty($ary_m_id) && is_array($ary_m_id)){
            foreach($ary_m_id as $k=>$v){
                $ary_memners = D('Members')->where(array('m_id'=>$v))->save(array('pl_id'=>$int_pl_id));
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
