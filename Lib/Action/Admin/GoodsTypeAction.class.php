<?php

/**
 * 后台商品类型控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author czy 
 * @date 2013-04-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsTypeAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 商品类型管理');
    }
    /**
     * 控制器默认方法，暂时重定向到品牌列表
     * @author czy
     * @date 2013-05-24
     
     */
    public function index(){
        $this->redirect(U('Admin/GoodsType/pageList'));
    }
    /**
     * 商品类型列表
     * @author czy
     * @date 2013-05-23
     */
    public function pageList(){
       //  echo 11;exit;
        $this->getSubNav(3,3,10);
        $where=array('gt_status'=>1);
        $count =  M('goods_type',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->count();
        
        $page_no = (int) $this->_get('p', '', 0);
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_type =  M('goods_type',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->limit($limit)->order(array('gt_create_time'))->select();
        $this->assign("page", $page);
        $this->assign('ary_type',$ary_type);
        $this->display();
    }
     
     /**
     * 添加
     * @author czy   
     * @date 2013-05-24
     */
    public function addGoodsType(){
        $this->getSubNav(3,3,20,'类型添加');
        $this->display();
    }
    
    /**
     *类型添加操作
     * @author czy   
     * @date 2013-05-24 
     */
    public function doAddType(){
        $ary_spec = $this->_post();
		if(!isset($_POST["gt_name"]) || "" == trim($_POST["gt_name"])){
			$this->error("商品类型名称必须输入。");
		}
        $array_add_type = array();
		$array_add_type['gt_name'] = trim($_POST["gt_name"]);
		$array_add_type['gt_type'] = intval($_POST["gt_type"]);
        $array_add_type['gt_create_time'] = date('Y-m-d h:i:s');
		//modify by Mithern 添加属性时默认增加一个与颜色属性的关联
		//事务开始
		D('GoodsType')->startTrans();
		//商品类型基本信息入库
		$int_goods_type_id = D('GoodsType')->add($ary_spec);
        if(false === $int_goods_type_id){
            D('GoodsType')->rollback();
			$this->error('商品属性添加失败。');
        }
		//增加一个与系统属性颜色的关联
		//这里888是系统内置属性颜色的ID，这个属性会随着系统安装部署初始化在系统表中
		if(false === D("RelatedGoodsTypeSpec")->add(array('gt_id'=>$int_goods_type_id,'gs_id'=>888))){
			D('GoodsType')->rollback();
			$this->error('商品属性添加失败:关联系统属性时遇到错误。');
		}
		//事务提交
		D('GoodsType')->commit();
		$str_jump_uri = U('Admin/GoodsType/pageList');
		if(isset($_POST["jump"]) && 1 == $_POST["jump"]){
			$str_jump_uri = U('Admin/GoodsProperty/addSpecPage',"gt_id={$int_goods_type_id}");
		}
        $this->success('商品类型添加成功', $str_jump_uri);
        
    }
    
    /**
     * 验证类型名称是否重复
     * @author czy
     * @date 2013-05-24
     */
    public function checkTypeName(){
        $ary_get = $this->_get();
		$array_cond = array('gt_name'=>$ary_get['gt_name']);
		if(isset($ary_get["gt_id"]) && is_numeric($ary_get["gt_id"])){
			$array_cond["gt_id"] = array("neq",$ary_get["gt_id"]);
		}
        $ary_data =  D("GoodsType")->where($array_cond)->find();
        if(!empty($ary_data) && is_array($ary_data)){
           $this->ajaxReturn("该商品属性名称已经存在");
       }else{
           $this->ajaxReturn(true);
       }
    }
    
    /*************************************/
    
    /**
     * 类型值列表
     * @author czy   
     * @date 2013-05-24
     */
    public function PropertyDetailPage(){
        $this->getSubNav(3,2,20);
        $gs_id = $this->_get('gsid');
        $where  = array();
        if(isset($gs_id)){
            $where['fx_goods_spec_detail.gs_id'] = $gs_id;
        }
        $count  =  D('GoodsTypeDetail')->where($where)->count();
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_spec_detail = D('GoodsTypeDetail')->join('fx_goods_spec on fx_goods_spec_detail.gs_id = fx_goods_spec.gs_id')->where($where)->limit($page_no,$page_size)->order('fx_goods_spec_detail.gsd_order desc')->select();
        //echo D('GoodsTypeDetail')->getLastSql();exit;
        //echo "<pre>";print_r($ary_spec_detail);exit;
        $this->assign('page',$page);
        $this->assign('ary_spec_detail',$ary_spec_detail);
        $this->display();
    }
    /**
     * 类型值删除
     * @authot czy
     * @date 2013-05-24
	 * modify by Mithern 2013-06-13 删除类型业务逻辑修改，增加删除类型前
	 * 验证此类型下是否关联属性，如果有属性关联，则类型不允许删除。
     */
    public function doDelTypeDetail(){
        if(!isset($_GET["gtid"]) || !is_numeric($_GET["gtid"])){
			$this->error("参宿错误：请指定要删除的商品类型ID。");
		}
		//获取商品类型ID
		$int_gt_id = $this->_get('gtid');
		
		//验证此类型是否关联商品
		$array_check_goods_result = D("Goods")->where(array('gt_id'=>$int_gt_id))->find();
		if(is_array($array_check_goods_result) && !empty($array_check_goods_result)){
			$this->error("商品类型删除失败：此类型已关联商品，请先删除关联的商品。");
		}
		
		//验证商品类型下是否包含商品属性，如果有商品属性，则提示无法删除。
		$array_result = D("RelatedGoodsTypeSpec")->where(array('gt_id'=>$int_gt_id,'gs_id'=>array('neq',888)))->select();
		if(is_array($array_result) && !empty($array_result)){
			$this->error("商品类型删除失败：此类型已关联商品属性，请先删除商品属性。");
		}
		
		//伤处商品类型
		$res = D('GoodsType')->where(array('gt_id'=>$int_gt_id))->delete();
		if(false === $res){
			$this->error("商品类型删除失败");
		}
		
		//提示用户商品类型删除成功，并页面跳转到类型列表页面
		$this->success('商品类型删除成功', U('Admin/GoodsType/pageList'));
		exit;
    }
    
    /**
     * 类型值编辑页面
     * @author czy   
     * @date 2013-05-24
     */
    public function eidtGoodsType(){
        
        $this->getSubNav(3,3,10);
        $int_gsd_id = $this->_get('gtid');
        if(isset($int_gsd_id)){
            $where = array('gt_id'=>$int_gsd_id);
            $ary_spec_detail = D('GoodsType')->where($where)->find();
            
            
            if(empty($ary_spec_detail)){
                $this->error('参数错误');
            }else {
                //$this->assign('ary_type',$ary_spec);
                $this->assign('ary_type',$ary_spec_detail);
                $this->display();
            }
        }else {
            $this->error('参数错误');
        }
    }
    /**
     * 类型值编辑
     * @author czy   
     * @date 2013-05-25
     */
    public function doEidtType(){
        $int_gsd_id = $this->_post('gtid');
        //$int_gsd_id = $this->_post('gtid');
        $ary_data = $this->_post();
        $where = array();
        if(isset($int_gsd_id)){
            $ary_data ['gt_update_time'] =  date('Y-m-d h:i:s');
            
            $where['gt_id'] = $int_gsd_id;
            $res = D('GoodsType')->where($where)->save($ary_data);
            if(!$res){
                $this->error('编辑失败');
            }else {
                 $this->success('类型值编辑成功', U('Admin/GoodsType/pageList'));
            }
        }else {
            $this->error('参数有误');
        }
    }
    
    /**
     * 批量删除
     * @author czy
     * @date 2013-05-24
     */
    public function doDelType(){
		if(!isset($_POST["gt_id"]) || empty($_POST["gt_id"])){
			$this->error("请选择您要删除的商品类型ID。");
		}
		
		//事务开始：
		D('GoodsType')->startTrans();
		//验证类型是否被占用
		foreach($_POST["gt_id"] as $int_gt_id){
			$str_gt_name = D('GoodsType')->where(array('gt_id'=>$int_gt_id))->getField("gt_name");
			//验证此类型是否关联商品
			$array_check_goods_result = D("Goods")->where(array('gt_id'=>$int_gt_id))->find();
			if(is_array($array_check_goods_result) && !empty($array_check_goods_result)){
				$this->error("商品类型删除失败：类型“{$str_gt_name}”已关联商品，请先删除关联的商品。");
			}
			
			//验证商品类型下是否包含商品属性，如果有商品属性，则提示无法删除。
			$array_result = D("RelatedGoodsTypeSpec")->where(array('gt_id'=>$int_gt_id,'gs_id'=>array('neq',888)))->select();
			if(is_array($array_result) && !empty($array_result)){
				//获取类型的名称
				D('GoodsType')->rollback();
				$this->error("商品类型删除失败：类型“{$str_gt_name}”已经关联商品属性，请先删除商品属性。");
			}
			
			//伤处商品类型
			$res = D('GoodsType')->where(array('gt_id'=>$int_gt_id))->delete();
			if(false === $res){
				D('GoodsType')->rollback();
				$this->error("商品类型删除失败");
			}
		}
		
		//提交事务
		D('GoodsType')->commit();
		
		$this->success("商品类型批量删除成功。");
    }
   
}

?>
