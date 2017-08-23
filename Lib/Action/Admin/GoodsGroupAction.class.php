<?php
/**
 * 后台资讯控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author Mithern <sunguangxu@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsGroupAction extends AdminAction{
	public function index(){
		$this->redirect(U('Admin/GoodsGroup/pageList'));
	}
	
	public function pageList(){
		$this->getSubNav(3,6,20);
		//获取所有的商品列表
		$array_cond = array("gg_status"=>1);
		$count = D("GoodsGroup")->where($array_cond)->count();
		$page = new Page($count,20);
		$array_datalist = D("GoodsGroup")->where($array_cond)->order(array("gg_order"=>"asc"))->limit(implode(',',array($page->firstRow,$page->listRows)))->select();
		
		//统计每个分组下的商品数量
		$array_ggids = array();
		foreach($array_datalist as $val){
			$array_ggids[] = $val["gg_id"];
		}
		
		//统计每个分组下面包含的商品个数
		$array_group_gnums = D("RelatedGoodsGroup")->where(array("gg_id"=>array("IN",$array_ggids)))->group('gg_id')->getField("gg_id,count(`g_id`) as `count`",true);
		
		$this->assign("page",$page->show());
		$this->assign("datalist",$array_datalist);
		$this->assign("group_goods_nums",$array_group_gnums);
		$this->display();
	}
	
	public function addGroup(){
		if(isset($_POST["dosubmit"]) && 1 == $_POST["dosubmit"]){
			//商品分组名称
			if(!isset($_POST["gg_name"]) || "" == trim($_POST["gg_name"])){
				$this->error("商品分组名称不能为空。");
			}
			
			//验证商品分组名称的唯一性
			$array_check_result = D("GoodsGroup")->where(array("gg_name"=>trim($_POST["gg_name"])))->find();
			if(is_array($array_check_result) && !empty($array_check_result) && isset($array_check_result["gg_id"])){
				$this->error("已经存在相同名称的商品分组，为便于管理，请勿重复添加。");
			}
			
			//数据入库
			$array_insert_data = array();
			$array_insert_data["gg_name"] = trim($_POST["gg_name"]);
			$array_insert_data["gg_desc"] = (isset($_POST["gg_desc"]) && trim($_POST["gg_desc"]) != "")?trim($_POST["gg_desc"]):"";
			$array_insert_data["gg_status"] = 1;
			$array_insert_data["gg_order"] = (isset($_POST["gg_order"]) && is_numeric(trim($_POST["gg_order"])))?trim($_POST["gg_order"]):0;
			$array_insert_data["gg_create_time"] = date("Y-m-d H:i:s");
			$array_insert_data["gg_update_time"] = date("Y-m-d H:i:s");
			
			if(false === D("GoodsGroup")->add($array_insert_data)){
				$this->error("商品分组名称不能为空。");
			}
			
			$this->success("商品分组添加成功。",U("Admin/GoodsGroup/pageList"));
			exit;
		}
		$this->getSubNav(3,6,10);
		$this->display();
	}
	
	public function pageEdit(){
		if(isset($_POST["dosubmit"]) && 1 == $_POST["dosubmit"]){
                    
                        //促销存在不允许删除分组
                        $Promotion = D('Promotion');
                        $ary_promotion = $Promotion->where(array("pmn_group"=>array("IN",$_POST["gg_id"])))->find();
                        if(!empty($ary_promotion) && is_array($ary_promotion)){
                            $this->error("该分组已被促销方案使用，不可编辑");
                        }
			//商品分组ID
			if(!isset($_POST["gg_id"]) || !is_numeric($_POST["gg_id"])){
				$this->error("参数错误：缺少商品分组ID传入。");
			}
			
			//商品分组名称
			if(!isset($_POST["gg_name"]) || "" == trim($_POST["gg_name"])){
				$this->error("商品分组名称不能为空。");
			}
			
			//验证商品分组名称的唯一性
			$array_check_cond = array("gg_id"=>array("neq",$_POST["gg_id"]),"gg_name"=>trim($_POST["gg_name"]));
			$array_check_result = D("GoodsGroup")->where($array_check_cond)->find();
			if(is_array($array_check_result) && !empty($array_check_result) && isset($array_check_result["gg_id"])){
				$this->error("已经存在相同名称的商品分组，为便于管理，请勿重复添加。");
			}
			
			//数据入库
			$array_insert_data = array();
			$array_insert_data["gg_name"] = trim($_POST["gg_name"]);
			$array_insert_data["gg_desc"] = (isset($_POST["gg_desc"]) && trim($_POST["gg_desc"]) != "")?trim($_POST["gg_desc"]):"";
			$array_insert_data["gg_status"] = 1;
			$array_insert_data["gg_order"] = (isset($_POST["gg_order"]) && is_numeric(trim($_POST["gg_order"])))?trim($_POST["gg_order"]):0;
			$array_insert_data["gg_update_time"] = date("Y-m-d H:i:s");
			
			if(false === D("GoodsGroup")->where(array("gg_id"=>$_POST["gg_id"]))->save($array_insert_data)){
				$this->error("商品分组名称不能为空。");
			}
			
			$this->success("商品分组修改成功。",U("Admin/GoodsGroup/pageList"));
			exit;
		}
		
		//商品分组编辑的表单初始化
		if(!isset($_GET["ggid"]) || !is_numeric($_GET["ggid"])){
			$this->error("参数错误：非法的商品分组ID参数传入。");
		}
		
		//获取商品分组信息
		$array_group_info = D("GoodsGroup")->where(array("gg_id"=>$_GET["ggid"]))->find();
		if(!is_array($array_group_info) || empty($array_group_info)){
			$this->error("您要编辑的商品分组不存在，可能已被其他管理员删除。");
		}
		
		
		$this->assign("goods_group",$array_group_info);
		$this->assign("is_edit",1);
		$this->getSubNav(3,6,20);
		$this->display("addGroup");
	}
	
	public function changeOrder(){
		if(!isset($_POST["gg_id"]) || !is_numeric($_POST["gg_id"])){
			echo json_encode(array('status'=>false,'msg'=>"参数错误：非法的商品分组ID。"));
			exit;
		}
		
		if(!isset($_POST["gg_order"]) || !is_numeric($_POST["gg_order"])){
			echo json_encode(array('status'=>false,'msg'=>"参数错误：非法的商品分组排序数字。"));
			exit;
		}
		
		$array_cond = array("gg_id"=>$_POST["gg_id"]);
		$array_modify = array("gg_order"=>$_POST["gg_order"],"gg_update_time"=>date('Y-m-d H:i:s'));
		if(false === D('GoodsGroup')->where($array_cond)->save($array_modify)){
			echo json_encode(array('status'=>false,'msg'=>"系统错误：商品分组排序修改失败。"));
			exit;
		}
		
		echo json_encode(array('status'=>true,'msg'=>"商品分组排序修改成功。"));
		exit;
	}
	
	public function doDel(){
		if(!isset($_GET["ggid"]) || empty($_GET["ggid"])){
			$this->error("请选择您要删除的商品分组。");
		}
		
                //促销存在不允许删除分组
                $Promotion = D('Promotion');
                $ary_promotion = $Promotion->where(array("pmn_group"=>array("IN",$_GET["ggid"])))->find();
                if(!empty($ary_promotion) && is_array($ary_promotion)){
                    $this->error("该分组已被促销方案使用，不可删除");
                }
		//如果当前分组中存在商品，则不允许删除
		$array_check_cond = array("gg_id"=>array("IN",$_GET["ggid"]));
		$array_check_result = D("RelatedGoodsGroup")->where($array_check_cond)->find();
		if(is_array($array_check_result) && !empty($array_check_result)){
			$this->error("您选中的分组中存在商品，可能已被使用，不允许删除。");
		}
		
		//删除分组
		if(false === D("GoodsGroup")->where($array_check_cond)->delete()){
			$this->error("商品分组删除失败。");
		}
		
		$this->success("商品分组删除成功。",U("Admin/GoodsGroup/pageList"));
	}
	
	
	public function addGoodsToGroup(){
		//验证是否制定分组ID
		if(!isset($_POST["gg_id"]) || !is_numeric($_POST["gg_id"])){
			echo json_encode(array("status"=>false,"msg"=>"参数错误：非法的商品分组ID参数传入。"));
			exit;
		}
		
		//验证是否选择商品
		if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
			echo json_encode(array("status"=>false,"msg"=>"请选择您要加入分组的商品。"));
			exit;
		}
		
		//验证分组是否存在
		$array_check_exists = D("GoodsGroup")->where(array("gg_id"=>$_POST["gg_id"]))->find();
		if(empty($array_check_exists) || !is_array($array_check_exists)){
			echo json_encode(array("status"=>false,"msg"=>"商品分组不存在。"));
			exit;
		}
		
		$array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
		foreach($array_goods_ids as $key => $val){
			$array_save_data = array();
			$array_save_data["gg_id"] = $_POST["gg_id"];
			$array_save_data["g_id"] = $val;
			//使用replace into 方式存入数据库
			if(false === D("RelatedGoodsGroup")->add($array_save_data,array(),true)){
				echo json_encode(array("status"=>false,"msg"=>"将商品加入分组时遇到错误。"));
				exit;
			}
		}
		echo json_encode(array("status"=>true,"msg"=>"操作成功"));
		exit;
	}
	
	public function removeGoodsToGroup(){
		//验证是否制定分组ID
		if(!isset($_POST["gg_id"]) || !is_numeric($_POST["gg_id"])){
			echo json_encode(array("status"=>false,"msg"=>"参数错误：非法的商品分组ID参数传入。"));
			exit;
		}
		
		//验证是否选择商品
		if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
			echo json_encode(array("status"=>false,"msg"=>"请选择您要移出分组的商品。"));
			exit;
		}
		
		//验证分组是否存在
		$array_check_exists = D("GoodsGroup")->where(array("gg_id"=>$_POST["gg_id"]))->find();
		if(empty($array_check_exists) || !is_array($array_check_exists)){
			echo json_encode(array("status"=>false,"msg"=>"商品分组不存在。"));
			exit;
		}
		
		$array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
		foreach($array_goods_ids as $key => $val){
			$array_save_data = array();
			$array_save_data["gg_id"] = $_POST["gg_id"];
			$array_save_data["g_id"] = $val;
			if(false === D("RelatedGoodsGroup")->where($array_save_data)->delete()){
				echo json_encode(array("status"=>false,"msg"=>"将商品从分组中移除时遇到错误。"));
				exit;
			}
		}
		echo json_encode(array("status"=>true,"msg"=>"操作成功"));
		exit;
	}
    
    /**
     * 打开快速归组页面，ajax异步请求
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-10-17
     */
    public function ajaxOpenFastGoodGroups(){
        $this->assign("goodsgroups",D("GoodsGroup")->where(array("gg_status"=>1))->order(array("gg_order"=>"asc"))->select());
        //得到商品分类
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌
		$brandHtml = $this->get_brand($brands,$otherbrands);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);
		$this->display();
    }
	
    /**
     * 打开快速归组页面，ajax异步请求
     *
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-11
     */
    public function ajaxOpenFastGoods(){
        //得到商品分类
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌
		$brandHtml = $this->get_brand($brands,$otherbrands);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);
		$this->display();
    }
	
	/**
	 * 得到类目
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-26
	 */
	public function get_cate($cate_id='',$othercates){
		if(!empty($cate_id)) $cate_ids = explode(',',$cate_id);
		else $cate_ids = array();
		$cate_html = '';
		//获取商品分类并传递到模板
		$cateList = D("GoodsCategory")->getChildLevelCategoryById(0);
		foreach($cateList as $cat){
			$cate_html .='<li class="cat_list_li" id="li_catid_'.$cat['gc_id'].'" is_parent="'.$cat['gc_is_parent'].'" parent_id="'.$cat['gc_parent_id'].'" style="margin-left:'.intval($cat[gc_level]*3).'em;" >';
			$cate_html .='<input type="checkbox"  id="shopCat__'.$cat['gc_id'].'" ';
			//展示已选择的商品类目
			if(!empty($cate_ids)){
				if(in_array($cat['gc_id'],$cate_ids)){
					$cate_html .=' checked="checked" ';
				}
			}
			//已被其他公司选择的商品分类和品牌不允许重复选择使用
			if(!empty($othercates)){
				if(in_array($cat[gc_id],$othercates)){
					$cate_html .=' disabled="true"  ';
				}
			}
			$cate_html .=' value="'.$cat['gc_id'].'" ref="'.$cat['gc_name'].'"  name="shopCat" class="cat-checkbox" pid="'.$cat['gc_parent_id'].'" />';
			$cate_html .=' <label for="shopCat__'.$cat['gc_id'].'" style="cursor:pointer;" ';
				
			if(in_array($cat[gc_id],$othercates)){
				$cate_html .=' disabled="true"  title="此分类已被其他子公司使用不能再次使用"';
			}
			$cate_html .='>'.$cat['gc_name'];
			if($cat['gc_is_display'] != '1'){
				$cate_html .='<span style="color:#ff0000;">[前台不显示]</span>';
			}
			$cate_html .='</label></li>';
		}
		return $cate_html;
	}

	/**
	 * 得到品牌
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-26
	 */
	public function get_brand($brand_id='',$otherbrands){
		if(!empty($brand_id)) $$brand_ids = explode(',',$brand_id);
		else $brand_ids = array();
		$brand_html = '';
		$brandList = D("GoodsBrand")->where(array("gb_status"=>1))->field('gb_id,gb_name')->order('gb_order asc')->select();
		foreach($brandList as $brand){
			$brand_html .='<li class="brand_list_li" >';
			$brand_html .='<input type="checkbox"  id="shopBrand__'.$brand['gb_id'].'" ';
			if(!empty($brand_ids)){
				if(in_array($brand[gb_id],$brand_ids)){
					$brand_html .=' checked="checked" ';
				}
			}
			if(!empty($otherbrands)){
				if(in_array($brand[gb_id],$otherbrands)){
					$brand_html .=' disabled="true"  ';
				}
			}
			$brand_html .=' value="'.$brand['gb_id'].'" ref="'.$brand['gb_name'].'"  name="shopBrand" class="brand-checkbox"  />';
			$brand_html .=' <label for="shopBrand__'.$brand['gb_id'].'" style="cursor:pointer;" ';
				
			if(in_array($brand[gb_id],$otherbrands)){
				$brand_html .=' disabled="true"  title="此品牌已被其他子公司使用不能再次使用"';
			}
			$brand_html .='>'.$brand['gb_name'];
			$brand_html .='</label></li>';
		}
		return $brand_html;
	}
 
    /**
     * 执行快速归组操作
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-10-18
     * @modify by wanghaoyu 批量归组
     */
    public function doAddFastGroupGoods(){
        $ary_request = $this->_post();
        //如果是选择加入现有分组的
        if($ary_request['skip'] == '1'){
            $gg_id = $ary_request['gg_id'];
            if(empty($gg_id) && !isset($gg_id)){
                $this->ajaxReturn(array("status"=>false,"msg"=>"请选择分组id"));
            }
            //检测当前分组的有效性
            $array_check_exists = D("GoodsGroup")->where(array("gg_id"=>$gg_id))->find();
            if(empty($array_check_exists) || !is_array($array_check_exists)){
                $this->ajaxReturn(array("status"=>false,"msg"=>"商品分组不存在。"));
            }
        }
        
        //验证传入的商品SN是否有效
        $ary_tmp_gid = array();
        $ary_gsn_where = array();
        $array_goods_sn = $ary_request['g_sn'];
		if(!empty($array_goods_sn)){
			foreach ($array_goods_sn as $gsn){
				$ary_gsn = explode("\n",$gsn);
				$str_gsn = implode(',',$ary_gsn);
				$ary_gsn_where['g_sn'] = array('IN',$str_gsn);
				$ary_tmp_gid = D('Goods')->where($ary_gsn_where)->getField('g_id',true);
				if(empty($ary_tmp_gid)){
					$this->ajaxReturn(array("status"=>false,"msg"=>$gsn."不存在！"));
				}
			}		
		}
		//根据商品类目查询商品ID
		$array_goods_cate = $ary_request['cat_ids'];
		$ary_tmp_cat_gid = array();
		if(!empty($array_goods_cate)){
			$ary_tmp_cat_gid = D('RelatedGoodsCategory')->where(array('gc_id'=>array('in',$array_goods_cate)))->getField('g_id',true);
		}
		$ary_tmp_gid = array_merge($ary_tmp_gid,$ary_tmp_cat_gid);
		//根据商品品牌查商品ID
		$array_goods_brand = $ary_request['brand_ids'];
		$ary_tmp_brand_gid = array();
		if(!empty($array_goods_brand)){
			$ary_tmp_brand_gid = $ary_tmp_cat_gid = D('Goods')->where(array('gb_id'=>array('in',$array_goods_brand)))->getField('g_id',true);
		}
		$ary_tmp_gid = array_merge($ary_tmp_gid,$ary_tmp_brand_gid);
		//合并去重
		$ary_tmp_gid = array_unique($ary_tmp_gid);
		if(empty($ary_tmp_gid)){
			$this->ajaxReturn(array("status"=>false,"msg"=>"商品信息不存在！"));
		}
        //开启事物
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        //选择新建分组，执行添加新分组操作
        if($ary_request['skip'] == '0'){
            //商品分组名称
            if(!isset($ary_request["new_gg_name"]) || "" == trim($_POST["new_gg_name"])){
                $this->ajaxReturn(array("status"=>false,"msg"=>"商品分组名称不能为空。"));
            }
            
            //验证商品分组名称的唯一性
            $array_check_result = D("GoodsGroup")->where(array("gg_name"=>trim($ary_request["new_gg_name"])))->find();
            if(is_array($array_check_result) && !empty($array_check_result) && isset($array_check_result["gg_id"])){
                $this->ajaxReturn(array("status"=>false,"msg"=>"已经存在相同名称的商品分组，为便于管理，请勿重复添加。"));
            }
            
            //数据入库
            $array_insert_data = array();
            $array_insert_data["gg_name"] = trim($ary_request["new_gg_name"]);
            $array_insert_data["gg_desc"] = "";
            $array_insert_data["gg_status"] = 1;
            $array_insert_data["gg_order"] = 0;
            $array_insert_data["gg_create_time"] = date("Y-m-d H:i:s");
            $array_insert_data["gg_update_time"] = date("Y-m-d H:i:s");
            $gg_id = D("GoodsGroup")->add($array_insert_data);
            if(false === $gg_id){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->ajaxReturn(array("status"=>false,"msg"=>"商品分组名称不能为空。"));
            }
        }
        //入库
        foreach ($ary_tmp_gid as $gid){
            $array_save_data = array();
            $array_save_data["gg_id"] = $gg_id;
            $array_save_data["g_id"] = $gid;
            //使用replace into 方式存入数据库
            if(false === D("RelatedGoodsGroup")->add($array_save_data,array(),true)){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->ajaxReturn(array("status"=>false,"msg"=>"将商品加入分组时遇到错误。"));
            } 
        }
        //事物提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->ajaxReturn(array("status"=>true,"msg"=>"操作成功！"));
    }
	
	/**
     * 执行更改前缀或后缀操作
     *
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-12
     */
    public function doAddFastGoods(){
        $ary_request = $this->_post();
		$item_type = $ary_request['item_type'];
        $item_title = $ary_request['item_title'];
		if(empty($item_type) || empty($item_title)){
			$this->ajaxReturn(array("status"=>false,"msg"=>"请选择要添加前缀或后缀名称"));
		}
        //验证传入的商品SN是否有效
        $ary_tmp_gid = array();
        $ary_gsn_where = array();
        $array_goods_sn = $ary_request['g_sn'];
		if(!empty($array_goods_sn)){
			foreach ($array_goods_sn as $gsn){
				$ary_gsn = explode("\n",$gsn);
				$str_gsn = implode(',',$ary_gsn);
				$ary_gsn_where['g_sn'] = array('IN',$str_gsn);
				$ary_tmp_gid = D('Goods')->where($ary_gsn_where)->getField('g_id',true);
				if(empty($ary_tmp_gid)){
					$this->ajaxReturn(array("status"=>false,"msg"=>$gsn."不存在！"));
				}
			}		
		}
		//根据商品类目查询商品ID
		$array_goods_cate = $ary_request['cat_ids'];
		$ary_tmp_cat_gid = array();
		if(!empty($array_goods_cate)){
			$ary_tmp_cat_gid = D('RelatedGoodsCategory')->where(array('gc_id'=>array('in',$array_goods_cate)))->getField('g_id',true);
		}
		$ary_tmp_gid = array_merge($ary_tmp_gid,$ary_tmp_cat_gid);
		//根据商品品牌查商品ID
		$array_goods_brand = $ary_request['brand_ids'];
		$ary_tmp_brand_gid = array();
		if(!empty($array_goods_brand)){
			$ary_tmp_brand_gid = $ary_tmp_cat_gid = D('Goods')->where(array('gb_id'=>array('in',$array_goods_brand)))->getField('g_id',true);
		}
		$ary_tmp_gid = array_merge($ary_tmp_gid,$ary_tmp_brand_gid);
		//合并去重
		$ary_tmp_gid = array_unique($ary_tmp_gid);
		if(empty($ary_tmp_gid)){
			$this->ajaxReturn(array("status"=>false,"msg"=>"商品信息不存在！"));
		}
		//前缀
		if($item_type == 1){
			$tmp_sql = "update fx_goods_info set g_name=concat('".$item_title."',g_name) where g_id in(".implode(',',$ary_tmp_gid).")";
		}
		//后缀
		if($item_type == 2){
			$tmp_sql = "update fx_goods_info set g_name=concat(g_name,'".$item_title."') where g_id in(".implode(',',$ary_tmp_gid).")";
		}
		$res = M('',C('DB_PREFIX'),'DB_CUSTOM')->execute($tmp_sql);
		if($res){
			 $this->ajaxReturn(array("status"=>true,"msg"=>"操作成功！"));
		}else{
			$this->ajaxReturn(array("status"=>false,"msg"=>"操作失败！"));
		}
    }
	
}