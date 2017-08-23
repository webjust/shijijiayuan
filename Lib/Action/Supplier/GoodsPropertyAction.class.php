<?php

/**
 * 后台商品属性控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author listen 
 * @date 2013-04-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsPropertyAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 商品属性管理');
    }
    
    /**
     * 控制器默认方法，暂时重定向到品牌列表
     * @author listen
     * @date 2013-02-26
     
     */
    public function index(){
        $this->redirect(U('Admin/GoodsProPerty/specListPage'));
    }
	
    /**
     * 商品属性列表
     * @author Mithern
     * @date 2013-05-27
	 * @version 2.0
     */
    public function specListPage(){
        $this->getSubNav(3,2,10);
		$array_cond = array("gs_status"=>1);
		//如果是从商品类型列表点击过来，则需要处理商品类型的搜索
		$int_search_type_id = 0;
		if($this->_get('gt_id') && is_numeric($this->_get('gt_id'))){
			//获取此类型关联的属性ID
			$int_type_id = $this->_get('gt_id');
			$int_search_type_id = $int_type_id;
			$array_related_spec = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>$int_type_id))->select();
			$array_spec_ids = array(-1);
			if(is_array($array_related_spec) && !empty($array_related_spec)){
				$array_spec_ids = array();
				foreach($array_related_spec as $val){
					$array_spec_ids[] = $val["gs_id"];
				}
			}
			$array_cond["gs_id"] = array("IN",$array_spec_ids);
		}
		//统计符合条件的总商品属性的数量，并处理分页信息
        $count = D('GoodsSpec')->where($array_cond)->count();
        $page_size = 20;
		$obj_page = new Page($count, $page_size);
		//获取数据并将获取到的数据传递到模板上
        $ary_spec = D('GoodsSpec')->where($array_cond)->limit($obj_page->firstRow,$obj_page->listRows)->select();
		//处理数据，获取商品类型的名称，这里的做法是先获取所有的商品类型
		//然后遍历商品属性数组，获取此属性关联的商品类型ID，
		//最后根据商品类型ID将商品类型名称插入数组中，最终渲染到视图层展示给用户。
		$array_type_info = D("GoodsType")->where(array("gt_status"=>1))->getField("gt_id,gt_type,gt_name");
		foreach($ary_spec as $key => $val){
			//获取当前属性关联的类型ID
			$array_type_ids = D("RelatedGoodsTypeSpec")->where(array("gs_id"=>$val["gs_id"]))->getField("gt_id",true);
			$array_type_names = array();
			$gt_type = 0;
			foreach($array_type_ids as $int_type_id){
				foreach($array_type_info as $val){
					if($val['gt_id'] == $int_type_id){
						$array_type_names[] = $val['gt_name'];
						$gt_type = $val['gt_type'];
					}
				}
			}
			$ary_spec[$key]["gt_name"] = implode(' | ',$array_type_names);
			$ary_spec[$key]["gt_id"] = $int_type_id;
			$ary_spec[$key]["gt_type"] = $gt_type;
			
		}
        $this->assign('ary_spec',$ary_spec);
		$this->assign('page',$obj_page->show());
		//获取所有的商品类型用于搜索
		$this->assign("array_type_info",$array_type_info);
		//dump($array_type_info);die();
		$this->assign('int_type_id',$int_search_type_id);
		$int_gt_type_id = intval($this->_get('gt_type'));
		foreach($array_type_info as $val){
			if($val['gt_id'] == $int_search_type_id){
				$int_gt_type_id = $val['gt_type'];
			}
		}
		$this->assign('int_gt_type_id',$int_gt_type_id);
        //渲染视图
        $this->display();
    }
	
    /**
     * 属性添加
     * @author listen   
     * @date 2013-04-24
     */
    public function addSpecPage(){
		//商品类型参数传递验证
		if(!isset($_GET["gt_id"]) || !is_numeric($_GET["gt_id"])){
			//此处由于产品方确定添加属性一定要选择类型导致
			$this->error('参数有误，没有指定商品类型或者商品类型参数值不合法！');
		}
		//获取商品类型ID
		$int_gt_id = $_GET["gt_id"];
		$int_gt_type = intval($_GET["gt_type"]);
		$ary_where = array("gt_status"=>1);
		if($int_gt_type == 1){
			$ary_where['gt_type'] = 1;
		}else{
			$ary_where['gt_type'] = 0;
		}
		//获取商品类型详细信息
		$array_type_info = D("GoodsType")->where($ary_where)->select();
		//判断商品类型的合法性
		if(!$array_type_info || empty($array_type_info)){
			$this->error('商品类型不存在！如果您还没有添加商品类型，请先添加商品类型！');
		}
		
        $this->getSubNav(3,2,20,'属性添加');
		$this->assign("array_type_info",$array_type_info);
		$this->assign("int_gt_id",$int_gt_id);
		$this->assign("int_gt_type",$int_gt_type);
        $this->display();
    }
	
    /**
     * 属性添加操作，将属性信息保存进入数据库
     * @author Mithern   
     * @date 2013-05-24 
     */
    public function doAddSpec(){
		if(!isset($_POST["dosubmit"]) || 1 != $_POST["dosubmit"]){
			$this->error("表单提交参数错误");
		}
		
		//对属性名称的输入情况进行验证，必填参数
		$post_spec_info = $_POST["spec"];
		if(!isset($post_spec_info["gs_name"]) || "" == $post_spec_info["gs_name"]){
			$this->error("属性名称必须输入");
		}
		
		//验证是否选择商品类型
		if(!isset($_POST["gt_id"]) || empty($_POST["gt_id"])){
			$this->error("请选择商品类型");
		}
		$int_goods_type_id = $_POST["gt_id"][0];
		if(!empty($post_spec_info["gs_input_type"])){
			$int_goods_gt_type = 1;
		}else{
			$int_goods_gt_type = 0;
		}
		//页面跳转url处理
		$str_jump_uri = U('Admin/GoodsProperty/specListPage',"gt_id={$int_goods_type_id}&gt_type={$int_goods_gt_type}");
		if(isset($_POST["jump"]) && 1 == $_POST["jump"]){
			$str_jump_uri = U('Admin/GoodsProperty/addSpecPage',"gt_id={$int_goods_type_id}&gt_type={$int_goods_gt_type}");
		}
		
		//验证属性名称的唯一性，算法是先根据商品类型ID找到已关联的商品属性ID，
		//然后根据属性ID找到属性名称，遍历完成比对验证
		$array_related_spec_ids = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>array("IN",$_POST["gt_id"])))->select();
		if(is_array($array_related_spec_ids) && !empty($array_related_spec_ids)){
			//取出所有的商品属性ID和商品类型ID
			$ary_spec_ids = array();
			$ary_type_ids = array();
			foreach($array_related_spec_ids as $spec_info){
				$ary_spec_ids[] = $spec_info["gs_id"];
				$ary_type_ids[$spec_info["gs_id"]] = $spec_info["gt_id"];
			}
			//根据获得的商品属性ID获取商品属性名称信息，用于比对
			$array_result = D("GoodsSpec")->where(array("gs_id"=>array("IN",$ary_spec_ids)))->select();
			foreach($array_result as $check_spec){
				//如果属性名称出现了重复，则获取是哪个类型下面出现了重复的属性
				if($check_spec["gs_name"] == $post_spec_info["gs_name"]){
					$this->error("所属的商品类型中已经存在名称为“" . $post_spec_info["gs_name"] . "“的商品属性。");
				}
			}
		}
        //验证和提示完毕，处理数据资料入库，由于涉及操作三张表，所以需要启用事务机制
		//事务开始，由于事务是针对到数据库的，所以在任意一个实例化MODEL上开启事务
		D("GoodsSpec")->startTrans();
		$array_spec_info = array();
		$array_spec_info["gs_name"] = $post_spec_info["gs_name"];
		$array_spec_info["gs_input_type"] = $post_spec_info["gs_input_type"];
		$array_spec_info["gs_is_sale_spec"] = (isset($post_spec_info["gs_is_sale_spec"]) && 1 == $post_spec_info["gs_is_sale_spec"])?1:0;
		$array_spec_info["gs_status"] = 1;
		$array_spec_info["gs_order"] = $post_spec_info["gs_order"];
		$array_spec_info["gs_create_time"] = date("Y-m-d H:i:s");
		$int_spec_id = D("GoodsSpec")->add($array_spec_info);
		if(false === $int_spec_id){
			//事务回滚，商品属性基本信息插入失败
			D("GoodsSpec")->rollback();
			$this->error("商品属性基本信息存入失败。");
		}
		//保存商品类型，属性关联关系，这里可以使用replace into 模式处理
		foreach($_POST["gt_id"] as $int_gt_id){
			$array_related_info = array();
			$array_related_info["gt_id"] = $int_gt_id;
			$int_goods_type_id = $array_related_info["gt_id"];
			$array_related_info["gs_id"] = $int_spec_id;
			if(false === D("RelatedGoodsTypeSpec")->add($array_related_info,array(),true)){
				D("RelatedGoodsTypeSpec")->rollback();
				$this->error("商品属性-类型关联关系保存失败");
			}
		}
		//如果当前属性的值录入方式是select下拉框的方式，则还要将属性值信息存入数据库
		if(2 != $array_spec_info["gs_input_type"]){
			//提交事务，并跳转到商品属性列表页面
			D("RelatedGoodsTypeSpec")->commit();
			$this->success('商品属性添加成功', $str_jump_uri);
			exit;
		}
		
		//对可能存在的属性值进行处理
		$array_spec_value = explode("\n",$_POST["spec_values"]);
		if(!is_array($array_spec_value) || empty($array_spec_value)){
			D("RelatedGoodsTypeSpec")->commit();
			$this->success('商品属性添加成功', $str_jump_uri);
			exit;
		}
		$array_values = array();
		$i = 0;
		foreach($array_spec_value as $val){
			if("" == trim($val) || in_array($val,$array_values)){
				continue;
			}
			$i++;
			$array_values[] = trim($val);
			$array_spec_detail = array();
			$array_spec_detail["gs_id"] = $int_spec_id;
			$array_spec_detail["gsd_value"] = trim($val);
			$array_spec_detail["gsd_order"] = $i;
			$array_spec_detail["gsd_status"] = 1;
			$array_spec_detail["gsd_create_time"] = date("Y-m-d H:i:s");
			if(false === D("GoodsSpecDetail")->add($array_spec_detail)){
				D("GoodsSpecDetail")->rollback();
				$this->error('商品属性值“' . trim($val) . '“保存失败');
			}
		}
		//所有需要保存的数据保存完毕，事务提交
		D("RelatedGoodsTypeSpec")->commit();
		$this->success('商品属性添加成功', $str_jump_uri);
		exit;
    }
	
    /**
     * 属性编辑页面
     * @author listen   
     * @date 2013-04-24
     */
    public function specEditPage(){
        $this->getSubNav(3,2,10);
        //对属性的参数信息进行判断
		$int_gs_id = $this->_get("gsid");
		$int_gt_type = $this->_get("gt_type");
		if(!isset($int_gs_id) || !is_numeric($int_gs_id)){
			$this->error('参数错误：非法的属性ID参数传入。');
		}
		//获取属性的详细信息
		$ary_spec = D('GoodsSpec')->where(array('gs_id'=>$int_gs_id))->find();
		if(!is_array($ary_spec) || empty($ary_spec)){
			$this->error("您要编辑的商品属性不存在！");
		}
		//获取此属性关联的类型
		$array_realted_info = D("RelatedGoodsTypeSpec")->where(array("gs_id"=>$int_gs_id))->getField("gt_id",true);
		if(!is_array($array_realted_info) || empty($array_realted_info)){
			$this->error("数据异常：此属性没有关联类型！");
		}
		//获取类型的详细信息
		$ary_where = array('gt_status'=>1);
		if(!empty($int_gt_type)){
			$ary_where['gt_type'] = 1;
		}else{
			$ary_where['gt_type'] = 0;
		}
		$array_type_info = D("GoodsType")->where($ary_where)->select();
		if(!is_array($array_type_info) || empty($array_type_info)){
			$this->error("数据异常：关联的商品类型数据不存在，可能已经被删除！");
		}
		//如果属性值的输入类型是input方式输入，则还需要将属性值读取出来
		$select_value = "";
		if(2 == $ary_spec["gs_input_type"]){
			$array_details = D("GoodsSpecDetail")->where(array("gs_id"=>$int_gs_id,"gsd_status"=>1))->order(array("gsd_order"=>"asc"))->select();
			if(is_array($array_details) && !empty($array_details)){
				foreach($array_details as $val){
					$select_value .= $val["gsd_value"] . "\n";
				}
			} 
		}
		//变量传递到模板
		$this->assign('spec',$ary_spec);
		$this->assign('select_value',$select_value);
		$this->assign('array_type_info',$array_type_info);
		$this->assign('array_check_gtid',$array_realted_info);
		$this->assign('int_gt_type',$int_gt_type);
		//渲染试图
		$this->display();
    }
    
	/**
     * 编辑属性
     * @author listen 
     * @date 2013-04-24
     */
    public function doEditSpec(){
	
        if(!isset($_POST["dosubmit"]) || 1 != $_POST["dosubmit"]){
			$this->error("表单提交参数错误");
		}
		//对属性名称的输入情况进行验证，必填参数
		$post_spec_info = $_POST["spec"];
		if(!isset($post_spec_info["gs_name"]) || "" == $post_spec_info["gs_name"]){
			$this->error("属性名称必须输入");
		}
		//验证是否选择商品类型
		if(!isset($_POST["gt_id"]) || empty($_POST["gt_id"])){
			$this->error("请先选择商品类型");
		}
		$int_gs_id = $post_spec_info["gs_id"];
		//验证属性名称的唯一性，算法是先根据商品类型ID找到已关联的商品属性ID，
		//然后根据属性ID找到属性名称，遍历完成比对验证
		$array_related_spec_ids = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>array("IN",$_POST["gt_id"]),"gs_id"=>array("neq",$int_gs_id)))->select();
		
		if(is_array($array_related_spec_ids) && !empty($array_related_spec_ids)){
			//取出所有的商品属性ID和商品类型ID
			$ary_spec_ids = array();
			$ary_type_ids = array();
			foreach($array_related_spec_ids as $spec_info){
				$ary_spec_ids[] = $spec_info["gs_id"];
				$ary_type_ids[$spec_info["gs_id"]] = $spec_info["gt_id"];
			}
			//根据获得的商品属性ID获取商品属性名称信息，用于比对
			$array_result = D("GoodsSpec")->where(array("gs_id"=>array("IN",$ary_spec_ids)))->select();
			foreach($array_result as $check_spec){
				//如果属性名称出现了重复，则获取是哪个类型下面出现了重复的属性
				if($check_spec["gs_name"] == $post_spec_info["gs_name"]){
					$this->error("所属的商品类型中已经存在名称为“" . $post_spec_info["gs_name"] . "“的商品属性。");
				}
			}
		}
        //验证和提示完毕，处理数据资料入库，由于涉及操作三张表，所以需要启用事务机制
		//事务开始，由于事务是针对到数据库的，所以在任意一个实例化MODEL上开启事务
		D("GoodsSpec")->startTrans();
		$array_spec_info = array();
		$array_spec_info["gs_name"] = $post_spec_info["gs_name"];
		$array_spec_info["gs_input_type"] = $post_spec_info["gs_input_type"];
		$array_spec_info["gs_is_sale_spec"] = (isset($post_spec_info["gs_is_sale_spec"]) && 1 == $post_spec_info["gs_is_sale_spec"])?1:0;
		$array_spec_info["gs_status"] = 1;
		$array_spec_info["gs_order"] = $post_spec_info["gs_order"];
		$array_spec_info["gs_create_time"] = date("Y-m-d H:i:s");
		$mixd_save_result = D("GoodsSpec")->where(array("gs_id"=>$int_gs_id))->save($array_spec_info);
		if(false === $mixd_save_result){
			//事务回滚，商品属性基本信息插入失败
			D("GoodsSpec")->rollback();
			$this->error("商品属性基本信息存入失败。");
		}
		$int_spec_id = $int_gs_id;
		
		//保存商品类型，属性关联关系，这里可以使用replace into 模式处理
		//首先需要删除所有与此属性有关联的商品类型关联关系
		if(false === D("RelatedGoodsTypeSpec")->where(array("gs_id"=>$int_spec_id))->delete()){
			D("RelatedGoodsTypeSpec")->rollback();
			$this->error("商品属性-类型关联关系保存失败。");
		}
		foreach($_POST["gt_id"] as $int_gt_id){
			$array_related_info = array();
			$array_related_info["gt_id"] = $int_gt_id;
			$array_related_info["gs_id"] = $int_spec_id;
			if(false === D("RelatedGoodsTypeSpec")->add($array_related_info,array(),true)){
				D("RelatedGoodsTypeSpec")->rollback();
				$this->error("商品属性-类型关联关系保存失败");
			}
		}
		
		//如果当前属性的值录入方式是select下拉框的方式，则还要将属性值信息存入数据库
		if(2 != $array_spec_info["gs_input_type"]){
			//提交事务，并跳转到商品属性列表页面
			D("RelatedGoodsTypeSpec")->commit();
			$this->success('商品属性修改成功', U('Admin/GoodsProperty/specListPage'));
		}
		
        //对可能存在的属性值进行处理
        $array_spec_value = explode("\n",$_POST["spec_values"]);
        if(!is_array($array_spec_value) || empty($array_spec_value)){
            D("RelatedGoodsTypeSpec")->commit();
            $this->success('商品属性修改成功', U('Admin/GoodsProperty/specListPage','gt_id=' . $_POST["gt_id"]));
        }
        $array_values = array();
        
        //把本次删除的属性值从数据库中删除掉，这里是逻辑删除
        if(false === D("GoodsSpecDetail")->where(array("gs_id"=>$int_spec_id))->save(array("gsd_status"=>0))){
            D("GoodsSpecDetail")->rollback();
            $this->error('删除商品属性值失败。');
        }
        $array_last_spec_detail = D("GoodsSpecDetail")->where(array('gs_id'=>$int_spec_id))->select();
        //echo "<pre>";print_r($_POST);
       // echo "<pre>";print_r($array_last_spec_detail);exit;
        $i = 0;
        foreach($array_spec_value as $val){
            if("" == trim($val) || in_array($val,$array_values)){
                continue;
            }
            $k = 0;
            $i++;
            foreach ($array_last_spec_detail as $lsd_val){
                if($lsd_val['gsd_value'] == trim($val)){
                    if($lsd_val['gsd_status'] == 0){
                        if(false === D("GoodsSpecDetail")->where(array('gsd_id'=>$lsd_val['gsd_id']))->data(array('gsd_status'=>1,'gsd_order'=>$i))->save()){
                            D("GoodsSpecDetail")->rollback();
                            $this->error('商品属性值“' . trim($val) . '“保存失败');
                        }
                    }
                    $k++;
                }
            }
            if($k != 0){
                continue;
            }
            $array_values[] = trim($val);
            $array_spec_detail = array();
            $array_spec_detail["gs_id"] = $int_spec_id;
            $array_spec_detail["gsd_value"] = trim($val);
            $array_spec_detail["gsd_order"] = $i;
            $array_spec_detail["gsd_status"] = 1;
            $array_spec_detail["gsd_create_time"] = date("Y-m-d H:i:s");
            if(false === D("GoodsSpecDetail")->add($array_spec_detail,array(),true)){
                D("GoodsSpecDetail")->rollback();
                $this->error('商品属性值“' . trim($val) . '“保存失败');
            }
           // echo D("GoodsSpecDetail")->getLastSql();exit;
        }
        
       
        //所有需要保存的数据保存完毕，事务提交
        D("RelatedGoodsTypeSpec")->commit();
        $this->success('商品属性修改成功', U('Admin/GoodsProperty/specListPage','gt_id=' . $_POST["gt_id"][0]));
    }
	
	/**
	 * 删除商品属性，支持批量和单个删除商品属性
	 *
	 * @author Mithern
	 * @date 2013-05-27
	 * @version 1.0
	 */
	public function doDelSpec(){
		//获取要删除的商品属性ID
		$array_delete_ids = array();
		$post_delete_ids = array();
		$get_delete_ids = array();
		
		//先处理需要批量删除的商品ID
		if(isset($_POST["gs_id"]) && !empty($_POST["gs_id"])){
			$post_delete_ids = $_POST["gs_id"];
		}
		
		//单个商品属性的删除
		if($this->_get("gsid") && is_numeric($this->_get("gsid"))){
			$get_delete_ids = array($this->_get("gsid"));
		}
		
		//组装要删除的商品属性ID
		$array_delete_ids = array_merge($post_delete_ids,$get_delete_ids);
		if(!$array_delete_ids || empty($array_delete_ids)){
			$this->error("抱歉，您需要先选择要删除的商品属性。");
		}
		
		//对要删除的商品属性进行验证，验证是否有商品使用此属性
		//根据属性ID去查询商品/货品-属性关联表查询是否有记录，如果有记录，则此属性不允许被删除
		$check_used_result = true;
		foreach($array_delete_ids as $spec_id){
			$array_result = D("RelatedGoodsSpec")->where(array("gs_id"=>$spec_id))->find();
			if(is_array($array_result) && !empty($array_result)){
				$check_used_result = false;
				break;
			}
		}
		if(false === $check_used_result){
			$array_spec_info = D("GoodsSpec")->field('gs_name')->where(array("gs_id"=>$array_result["gs_id"]))->find();
			$this->error("商品属性“" . implode(',',$array_spec_info) . "“被商品占用，不允许删除！");
		}
		
		//删除商品属性，这里做逻辑删除
		//by Mithern 属性删除修改为物理删除
		if(false === D("GoodsSpec")->where(array("gs_id"=>array("IN",$array_delete_ids)))->delete()){
			$this->error("商品属性删除失败。");
		}
		//删除商品属性的同时删除商品属性详情
		if(false === D("GoodsSpecDetail")->where(array("gs_id"=>array("IN",$array_delete_ids)))->delete()){
			$this->error("商品属性删除失败。");
		}	
		//删除商品关联属性
		if(false === D("RelatedGoodsSpec")->where(array("gs_id"=>array("IN",$array_delete_ids)))->delete()){
			$this->error("商品关联属性删除失败。");
		}	
		//删除商品关联属性
		if(false === D("RelatedGoodsTypeSpec")->where(array("gs_id"=>array("IN",$array_delete_ids)))->delete()){
			$this->error("商品关联属性删除失败。");
		}	
		$this->success("商品属性删除成功。",U('Admin/GoodsProperty/specListPage'));
	}
        
    /**
     * 启用/停用结余款类型
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-15
     */
    public function doPropertyIsSearch(){
        $ary_post = $this->_post();
        if(!empty($ary_post['gs_id']) && isset($ary_post['gs_id'])){
            $ary_post['gs_is_search'] = ($ary_post['gs_is_search']) ? '1' : '0';
            $ary_result = D("GoodsSpec")->where(array('gs_id'=>$ary_post['gs_id']))->data(array('gs_is_search'=>$ary_post['gs_is_search']))->save();
            $str = $ary_post['gs_is_search'] ? '启用' : '停用';
            if(FALSE != $ary_result){
                $this->success($str ."成功");
            }else{
                $this->error($str ."失败");
            }
        }else{
            $this->error("该属性不存在，请重试...");
        }
    }
}
