<?php

/**
 * 后台分类控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.2
 * @author wangguibin 
 * @date 2013-05-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
 
class GoodsCategoryAction extends AdminAction{
    //put your code here
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU2_1'));
    }
     /**
     * erp分类控制器默认页，需要重定向
     * @author listen
     * @date 2013-02-25
     */
    public function index() {
        $this->redirect(U('Admin/ErpCategory/pageList'));
    }
    
    /**
     * 
     * 已新增的分类列表
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-04-25
     */
    public function pageList(){
        $this->getSubNav(3, 1, 10);
        //分类数组
        $int_gc_id = trim($this->_get('gc_id'));
        $ary_category= array();
		$ary_category = D("ViewGoods")->getInfo(null,1);
		$this->cate_html = '';
		$this->getCateList($ary_category,0,0,null);
		//获取父类Id
		$ary_pids = array();
		D("ViewGoods")->getParentCatesIds($int_gc_id,$ary_pids);
		$this->assign('pids',$ary_pids);
		$this->assign('cate_html',$this->cate_html);
        $this->assign('categorys',$ary_category);
        $this->display();
    }
    
    /**
     *
     * 获取分类列表的HTML页面
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-04-25
     */
    public function getCateList($ary_category,$i=0,$is_sub=0,$ary_cate_id){
    	$str = '';
    	if($i>0){
	    	for ($n=1; $n<=$i; $n++) {
				$str.="&nbsp;&nbsp;&nbsp;&nbsp;";
			}
    	}
    	foreach($ary_category as $ary_cate){
    		if($is_sub == '1'){
    			$this->cate_html .=	'<tr style="display:none;" class="cate'.$ary_cate_id.'" id="remove_'.$ary_cate[cid].'" >';
    		}else{
    			$this->cate_html .=	'<tr id="remove_'.$ary_cate[cid].'" >';
    		}
            $this->cate_html .=	'<td><input type="checkbox" class="checkSon checkSon_'.$ary_cate_id.'"  name="gcid[]" value="'.$ary_cate['cid'].'" /></td>
                <td style="text-align:left;">    		
    		';
    		$this->cate_html .=$str;
    		if(!empty($ary_cate['sub'])){
    			$this->cate_html .='
                <span id="showSubCate'.$ary_cate['cid'].'"><a href="javascript:void(0);" class="showSubCate" name="cate'.$ary_cate['cid'].'" val="'.$ary_cate['cid'].'" ><img src="__PUBLIC__/Admin/images/u48_normal.png" /></a></span>
                <span id="hideSubCate'.$ary_cate['cid'].'" style="display:none;"><a href="javascript:void(0);" class="hideSubCate" name="cate'.$ary_cate['cid'].'" val="'.$ary_cate['cid'].'"　 ><img src="__PUBLIC__/Admin/images/u21_normal.png" /></a></span>    			
    			';
    		}else{
//     			$this->cate_html .='
//                <span ><a href="javascript:void(0);" class="hideSubCate" name="cate'.$ary_cate['cid'].'" val="'.$ary_cate['cid'].'"　 ><img src="__PUBLIC__/Admin/images/u21_normal.png" /></a></span>    			
//    			';   			
    		}
    		$this->cate_html .= $ary_cate['cname'].'</td>';
    		$this->cate_html .= '<td><a href="'.U('Admin/Products/pageList').'?category='.$ary_cate['cid'].'"'.'>浏览商品</a></td>';
    		$this->cate_html .='<td>';
    		if($ary_cate['gc_is_display'] == '1'){
    			$this->cate_html .='<a class="hideImg" id="hideImg'.$ary_cate[cid].'" cid="'.$ary_cate[cid].'" url="'.U("Admin/GoodsCategory/pageDisplay").'?display=0&gcid='.$ary_cate[cid].'"><img src="__PUBLIC__/Admin/images/span-true.jpg" /></a>';
    			$this->cate_html .='<a class="showImg" id="showImg'.$ary_cate[cid].'" cid="'.$ary_cate[cid].'" style="display:none;" url="'.U("Admin/GoodsCategory/pageDisplay").'?display=1&gcid='.$ary_cate[cid].'"><img src="__PUBLIC__/Admin/images/span-false.jpg" /></a>';
    		}else{
    			$this->cate_html .='<a class="showImg" id="showImg'.$ary_cate[cid].'" cid="'.$ary_cate[cid].'" url="'.U("Admin/GoodsCategory/pageDisplay").'?display=1&gcid='.$ary_cate[cid].'"><img src="__PUBLIC__/Admin/images/span-false.jpg" /></a>';
    			$this->cate_html .='<a class="hideImg" id="hideImg'.$ary_cate[cid].'" cid="'.$ary_cate[cid].'" style="display:none;" url="'.U("Admin/GoodsCategory/pageDisplay").'?display=0&gcid='.$ary_cate[cid].'"><img src="__PUBLIC__/Admin/images/span-true.jpg" /></a>';
    		}
    		$this->cate_html .='</td>';
    		$this->cate_html .="<td>
                    <a href='".U("Admin/GoodsCategory/pageEdit?gcid=$ary_cate[cid]")."'>编辑</a> 
                    <a data-uri='".U("Admin/GoodsCategory/doDel?gcid=$ary_cate[cid]")."' cid='".$ary_cate[cid]."' class='confirm doDelCate'>删除</a>
					<a href='".U("Admin/GoodsCategory/addCategoryPromotion?gcid=$ary_cate[cid]")."'>类目促销设置</a> 
                </td>     ";
           $this->cate_html .="
            <td><a href='".U("Admin/GoodsCategory/addCategory?gcid=$ary_cate[cid]")."'>添加子分类</a></td>
            </tr>
           ";      
           if(!empty($ary_cate['sub'])){
           	$i++;
           	$this->getCateList($ary_cate['sub'],$i,1,$ary_cate['cid']);
           }
    	}
    }
    
    /**
     * 
     * 显示与不显示
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-05-27
     */
    public function pageDisplay(){
       	$data = $this->_get();
    	if(!empty($data['gcid'])){
    		$cid = $data['gcid'];
    		$display = $data['display'];
    		$res = D('GoodsCategory')->where(array('gc_id'=>$cid))->save(array('gc_is_display'=>$display));
    		if(!$res){
    		$ary_res = array('success' => 0, 'errMsg' => '失败');
	    	}else{
	    		$ary_res = array('success' => 1, 'errMsg' => '成功');
	    	}
	        echo json_encode($ary_res);
	        exit;
    	}
    }
    /**
     * 
     * 展示分类
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-05-28
     */    
    public function showOptionHtml($ary_cates,$parent_id,$i=0){
        $str = '┣';
    	if($i>0){
	    	for ($n=1; $n<=$i; $n++) {
				$str.="━━";
			}
    	}
    	foreach($ary_cates as $ary_cate){
    		if($ary_cate['cid'] == $parent_id){
    			$this->option_html .='<option  selected="selected" value="'.$ary_cate['cid'].'">'.$str.$ary_cate['cname'].'</option>';
    		}else{
    			$this->option_html .='<option   value="'.$ary_cate['cid'].'">'.$str.$ary_cate['cname'].'</option>';
    		}
    		
    		if(!empty($ary_cate['sub'])){
            	$i++;
           		$this->showOptionHtml($ary_cate['sub'],$parent_id,$i);
    		}
    	}    	
    }
    
    /**
     * 
     * 新增商品分类
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-05-24
     */
    public function addCategory(){
    	$this->getSubNav(3, 1, 20);
    	$data = $this->_get();
    	if(!empty($data['gcid'])){
    		$cid = $data['gcid'];
    	}
    	$ary_category = D("ViewGoods")->getInfo(null,1);
    	$this->option_html = '';
    	$this->showOptionHtml($ary_category,$cid);
        //是否启用分类属性功能
        $data = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
        $this->assign($data);
        //
    	$this->assign('option_html',$this->option_html);
    	$this->display();
    }
    
    /**
     * 添加分类操作
     * @author wangguibin
     * @date 2013-05-24
     */
    public function doAdd(){
         $array_insert_data = array();
        
		//验证商品分类的名称是否输入
		if(!isset($_POST['gc_name']) || "" == $_POST['gc_name']){
             $this->error('分类名称不能为空');
        }
		
		//验证商品分类的父级分类是否合法
		if(isset($_POST["gc_parent_id"]) && (!is_numeric($_POST["gc_parent_id"]) || $_POST["gc_parent_id"] < 0)){
			$this->error("上级分类ID参数不合法。");
		}
		
		//验证商品分类名称是否已经存在{此处规则修改：同级不重复}
		$array_cond = array('gc_name'=>$_POST['gc_name'],"gc_parent_id"=>$_POST["gc_parent_id"]);
		$array_result =  D("GoodsCategory")->where($array_cond)->find();
		if(is_array($array_result) && !empty($array_result)){
			$this->error('已经存在同级的商品分类“' . $_POST['gc_name'] . '“！');
		}
		
		//验证商品分类排序字段的参数是否合法
		if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		
		//数据组装
        $array_insert_data['gc_name'] = trim($_POST['gc_name']);
        $array_insert_data['gc_parent_id'] = $_POST['gc_parent_id'];
        $array_insert_data['gc_order'] = $_POST['gc_order'];
        $array_insert_data['gc_key'] = $_POST['gc_key'];
        //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
		$array_insert_data['gc_level'] = 0;
		if($array_insert_data['gc_parent_id'] > 0){
			$array_parent_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
			$int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
			$array_insert_data['gc_level'] = $int_parent_gc_level + 1;
		}
        $array_insert_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_insert_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_insert_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_insert_data['gc_is_hot'] = $_POST['gc_is_hot'];
        $array_insert_data['gc_create_time'] = date("Y-m-d h:i:s");
        //是否启用分类属性功能
        $data = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
        if($data['GCTYPE'] == 1){
            $array_insert_data['gc_type'] = isset($_POST['gc_type'])?$_POST['gc_type']:'0';
        }
        //上传图片
        if(!empty($_POST["gc_pic_url"])){
			$array_modify_data['gc_pic_url'] = trim($_POST["gc_pic_url"]);
			$array_modify_data['gc_pic_url'] = D('ViewGoods')->ReplaceItemPicReal($array_modify_data['gc_pic_url']);			
		}
		
		//事务开始
		D("GoodsCategory")->startTrans();
        $mixed_result =  D("GoodsCategory")->add($array_insert_data);
        $cid = $mixed_result;
		if(false === $mixed_result){
			D("GoodsCategory")->rollback();
			$this->error("商品分类添加失败。");
		}
		
		//如果此分类是某个分类的子分类，则将那个父级分类的is_parent字段设置为1
		if($array_insert_data['gc_parent_id'] > 0){
			$array_modify_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
			$array_modify_data = array("gc_is_parent"=>1,"gc_update_time"=>date("Y-m-d H:i:s"));
			$mixed_result = D("GoodsCategory")->where($array_modify_cond)->save($array_modify_data);
			if(false === $mixed_result){
				D("GoodsCategory")->rollback();
				$this->error("商品分类添加失败：CODE:MODIFY-PARENT-ID-ERROR。");
			}
		}
		
		//事务提交
		D("GoodsCategory")->commit();
		
		//页面跳转
		$page_jump_url = U('Admin/GoodsCategory/pageList',array('gc_id'=>$cid));
		if(isset($_POST["page_jump"]) && 1 == $_POST["page_jump"]){
			$page_jump_url = U('Admin/GoodsCategory/addCategory',array('gc_id'=>$cid));
		}
		$this->success('分类添加成功', $page_jump_url);
    }

    /**
     * 分类编辑页面显示
     * @author wangguibin
     * @date 2013-05-24
     */
    public function pageEdit(){
        $this->getSubNav(3,1,20,'分类编辑');
        $gc_id=$this->_get('gcid');  
        if(isset($gc_id)){
	    	$cid = $gc_id;
	    	$ary_category = D("ViewGoods")->getInfo(null,1);
	    	$parent_id = D('GoodsCategory')->field('gc_parent_id')->where(array('gc_id'=>$cid))->find();
	    	$parent_id = intval($parent_id['gc_parent_id']);
	    	$this->option_html = '';
	    	$this->showOptionHtml($ary_category,$parent_id);
            $where =  array('gc_id'=>$gc_id);
            $ary_category =  M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->find();
			if($_SESSION['OSS']['GY_QN_ON'] == '1' ){//七牛图片显示
				$ary_category["gc_pic_url"] = D('QnPic')->picToQn($ary_category["gc_pic_url"]);
			}
            $this->assign('option_html',$this->option_html);
            $this->assign('category',$ary_category);
            //是否启用分类属性功能
            $data = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
            $this->assign($data);
            $this->display();
        }else {
            $this->error('参数错误');
        }
       
    }
    /**
     * 分类编辑操作
     * @author wangguibin
     * @date 2013-05-24
     */
    public function doEdit(){
		if(!isset($_POST["gc_id"]) || !is_numeric($_POST["gc_id"])){
			$this->error("商品分类编辑参数错误。");
		}
		$int_gc_id = $_POST["gc_id"];
		
		//商品分类标题是否输入
		if(!isset($_POST["gc_name"]) || $_POST["gc_name"] == ""){
			$this->error("商品分类名称不能为空。");
		}
		
		//验证上级商品分类的选择是否合法
		if($_POST['gc_parent_id'] == $int_gc_id){
			$this->error("商品分类的上级分类不能是分类本身。");
		}
		
		//验证父级分类是否是当前分类的子分类或者更下级的分类
		$array_child_catids = D("GoodsCategory")->getCategoryChildIds($int_gc_id);
		if(!empty($array_child_catids) && in_array($_POST['gc_parent_id'],$array_child_catids)){
			$this->error("商品分类的上级分类不能是当前分类的叶子分类。");
		}
		
		//验证商品分类名称在同级分类下是否重复
		$array_cond = array("gc_id"=>array("neq",$int_gc_id),"gc_parent_id"=>$_POST["gc_parent_id"],"gc_name"=>$_POST["gc_name"]);
		$mixed_check_result = D("GoodsCategory")->where($array_cond)->find();
		if(is_array($mixed_check_result) && !empty($mixed_check_result)){
			$this->error("已经存在同级的商品分类“" . $_POST["gc_name"] . "”。");
		}
		
		//验证商品分类排序字段是否是合法的数字
		if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		
		//数据拼装
		$array_modify_data = array();
		$array_modify_data["gc_name"] = trim($_POST["gc_name"]);
		$array_modify_data['gc_parent_id'] = $_POST['gc_parent_id'];
        $array_modify_data['gc_order'] = $_POST['gc_order'];
        $array_modify_data['gc_key'] = $_POST['gc_key'];
		$array_modify_data['gc_level'] = 0;
        //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
		if($array_modify_data['gc_parent_id'] > 0){
			$array_parent_cond = array("gc_id"=>$array_modify_data['gc_parent_id']);
			$int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
			$array_modify_data['gc_level'] = $int_parent_gc_level + 1;
		}
        $array_modify_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_modify_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_modify_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_modify_data['gc_is_hot'] = $_POST['gc_is_hot'];
        $array_modify_data['gc_update_time'] = date("Y-m-d h:i:s");
        //是否启用分类属性功能
        $data = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
        if($data['GCTYPE'] == 1){
            $array_modify_data['gc_type'] = isset($_POST['gc_type'])?$_POST['gc_type']:'0';
        }
        //上传图片
        if(!empty($_POST["gc_pic_url"])){
			$array_modify_data['gc_pic_url'] = trim($_POST["gc_pic_url"]);
		}
		if($_SESSION['OSS']['GY_QN_ON'] == '1' ){//七牛图片上传
			$array_modify_data["gc_pic_url"] = D('ViewGoods')->ReplaceItemPicReal($array_modify_data['gc_pic_url']);
		}
		
		//事务开始
		D("GoodsCategory")->startTrans();
		$modify_result = D("GoodsCategory")->where(array("gc_id"=>$int_gc_id))->save($array_modify_data);
		if(false === $modify_result){
			D("GoodsCategory")->rollback();
			$this->error("商品分类更新失败，数据没有更新。");
		}
		
		//更新当前商品分类的gc_is_parent 字段
		if($array_modify_data['gc_parent_id'] > 0){
			$array_modify_cond = array("gc_id"=>$array_modify_data['gc_parent_id']);
			$array_modify_data = array("gc_is_parent"=>1,"gc_update_time"=>date("Y-m-d H:i:s"));
			$mixed_result = D("GoodsCategory")->where($array_modify_cond)->save($array_modify_data);
			if(false === $mixed_result){
				D("GoodsCategory")->rollback();
				$this->error("商品分类修改失败：CODE:MODIFY-PARENT-ID-ERROR。");
			}
		}
		
		//当前分类的孩子节点 gc_level 字段更新
		if(!empty($array_child_catids)){
			foreach($array_child_catids as $child_gc_id){
				//获取当前子分类的上级分类ID
				$int_parent_id = D("GoodsCategory")->where(array("gc_id"=>$child_gc_id))->getField("gc_parent_id");
				$array_modify_data = array();
				$array_modify_data['gc_level'] = 0;
				if($int_parent_id > 0){
					$array_parent_cond = array("gc_id"=>$int_parent_id);
					$int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
					$array_modify_data['gc_level'] = $int_parent_gc_level + 1;
				}
				$array_modify_data['gc_update_time'] = date("Y-m-d H:i:s");
				$array_modify_data['gc_type'] = intval($_POST['gc_type']);
				$array_modify_data['gc_is_display'] = intval($_POST['gc_is_display']);
				$array_modify_data['gc_is_hot'] = intval($_POST['gc_is_hot']);
				$mixed_result = D("GoodsCategory")->where(array("gc_id"=>$child_gc_id))->save($array_modify_data);
				if(false === $mixed_result){
					D("GoodsCategory")->rollback();
					$this->error("商品分类修改失败。");
				}
			}
		}
		
		//事务提交
		D("GoodsCategory")->commit();
		$this->success('商品分类修改成功。', U('Admin/GoodsCategory/pageList?gc_id='.$gc_id));   
    }
    
    /**
     * 分类删除
     * @author wangguibin
     * @date 2013-05-24
     */
    public function doDel(){
        //判断当前分类id是否为数组
        if(is_array($_GET["gcid"])){
            $array_gcid = $_GET["gcid"];
            foreach ($array_gcid as $int_gc_id){
                $array_result = $this->doSetDelCategory($int_gc_id);
                if(!$array_result['status']){
                    $this->error($array_result['msg']);
                }
            }
            $where = array('gc_id'=>array('in',implode(',',$array_gcid)));
        }else{
            $int_gc_id = $_GET["gcid"];
            $array_result = $this->doSetDelCategory($int_gc_id);
            if(!$array_result['status']){
                $this->error($array_result['msg']);
            }
            $where = array('gc_id'=>array('in',implode(',',array($int_gc_id))));
        }
        //删除商品分类
        $mixed_delete = D("GoodsCategory")->where($where)->delete();
        if(false === $mixed_delete){
            $this->error("商品分类删除失败。");
        }
        //页面提示并跳转
        $this->success('删除成功', U('Admin/GoodsCategory/pageList'));
    }
    
    /**
     * 验证商品分类是否可以被删除
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-14
     */
    private function doSetDelCategory($int_gc_id){
        //验证是否指定要删除的商品分类ID
        if(!isset($int_gc_id) || !is_numeric($int_gc_id)){
            return array("status"=>false,"msg"=>"商品分类ID参数不合法。");
        }
        //验证此分类是否存在
        $array_check_result = D("GoodsCategory")->where(array("gc_id"=>$int_gc_id))->find();
        if(empty($array_check_result)){
            return array("status"=>false,"msg"=>"您要删除的商品分类不存在，可能已被其他管理员删除。");
        }
        
        //验证此分类下是否存在子分类
        $array_child_catids = D("GoodsCategory")->getCategoryChildIds($int_gc_id);
        if(!empty($array_child_catids)){
            return array("status"=>false,"msg"=>"当前分类下存在子分类，请先删除子分类。");
        }
        
        //验证当前商品分类是否被占用，如果被占用，则不允许删除
        $array_check_cond = array("gc_id"=>$int_gc_id);
        $array_check_result = D("RelatedGoodsCategory")->where($array_check_cond)->find();
        if(is_array($array_check_result) && !empty($array_check_result)){
            return array("status"=>false,"msg"=>"此商品分类下存在商品资料数据，请先删除相应的商品。");
        }
        return array("status"=>true);
    }
	
    /**
     * 
     * 新增商品分类促销区
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-06-09
     */
    public function addCategoryPromotion(){
    	$this->getSubNav(3, 1, 30);
    	$data = $this->_get();
		if(empty($data['gcid'])){
			$this->error('请选择类目');
		}
    	if(!empty($data['gcid'])){
    		$this->assign('cid',$data['gcid']);
    	}
		//获取类目广告促销信息
		$gc_ad_type = D('GoodsCategory')->where(array('gc_id'=>$data['gcid']))->getField('gc_ad_type');
		//关联品牌
		$RelatedGoodcategoryBrand = D('RelatedGoodscategoryBrand');
		//关联广告图片
		$RelatedGoodcategoryAds = D('RelatedGoodscategoryAds');
		$arr_other_data_info = $RelatedGoodcategoryBrand->where(array('gc_id'=>$data['gcid']))->select();
		$othercates = array();
		$otherbrands = array();
		foreach ($arr_other_data_info as $v) {
			if(!empty($v['gb_id'])){
				$otherbrands[] = $v['gb_id'];
			}
		}
		$this->assign('brands', implode(',',$otherbrands));
		//得到商品品牌
		$brandHtml = $this->get_brand(implode(',',$otherbrands));
        //取出类目关联商品图片
		$ary_ads = $RelatedGoodcategoryAds->where(array('gc_id'=>$data['gcid']))->order('sort_order asc')->select();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
		foreach($ary_ad_infos as &$ad){
			$ad['ad_pic_url'] = D('QnPic')->picToQn($ad['ad_pic_url']);
		}
		$this->assign('brandHtml', $brandHtml);
        $this->assign('ary_ads', $ary_ad_infos);
		$this->assign('gc_ad_type', $gc_ad_type);
    	$this->display();
    }
	
	
	/**
	 * 得到品牌
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-06-09
	 */
	public function get_brand($brand_id=''){
		if(!empty($brand_id)) $brand_ids = explode(',',$brand_id);
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
			$brand_html .=' value="'.$brand['gb_id'].'" ref="'.$brand['gb_name'].'"  name="shopBrand[]" class="brand-checkbox"  />';
			$brand_html .=' <label for="shopBrand__'.$brand['gb_id'].'" style="cursor:pointer;" ';
				
			$brand_html .='>'.$brand['gb_name'];
			$brand_html .='</label></li>';
		}
		return $brand_html;
	}
	
	/**
	 * 编辑促销区
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-06-09
	 */
	public function doEditCategoryPromotion() {
		$ary_data = $this->_request();
		if(empty($ary_data['gcid'])){
			$this->error('商品类目不存在');
		}
		//关联品牌
		$RelatedGoodcategoryBrand = D('RelatedGoodscategoryBrand');
		$brands = $this->_post('shopBrand');
		//已被其他子公司选择的类目品牌则不允许保存
		$arr_other_data_info = $RelatedGoodcategoryBrand->where(array('gc_id'=>intval($ary_data['gcid'])))->select();
		$otherbrands = array();
		foreach ($arr_other_data_info as $v) {
			if(!empty($v['gb_id'])){
				$otherbrands[] = $v['gb_id'];
			}
		}
		M('')->startTrans();
		//更新类目表图片广告类型
		$gc_ad_type = intval($ary_data['gc_ad_type']);
		D('GoodsCategory')->where(array('gc_id' => $ary_data['gcid']))->data(array('gc_ad_type'=>$gc_ad_type))->save();
		//先删除关联的分类和品牌信息
		$RelatedGoodcategoryBrand->where(array('gc_id' => $ary_data['gcid']))->delete();		
		//插入品牌信息
		if(!empty($brands)){
			foreach($brands as $brand){
				$ary_insert = array();
				$ary_insert = array(
					'gc_id' => intval($ary_data['gcid']),
					'gb_id'=>$brand
				);
				$res = $RelatedGoodcategoryBrand->data($ary_insert)->add();
				if(!$res){
					M('')->rollback();
					$this->error('类目管理品牌保存失败');
				}				
			}
		}
		//插入广告信息
		//关联广告图片
		$RelatedGoodcategoryAds = D('RelatedGoodscategoryAds');
		//先删除关联的分类和品牌信息
		$RelatedGoodcategoryAds->where(array('gc_id' => $ary_data['gcid']))->delete();
		//广告图片1
		for($i=0;$i<5;$i++){
			if(!empty($ary_data['GY_SHOP_TOP_AD_'.$i])){
				$ary_insert = array();
				$ary_insert = array(
					'gc_id' => intval($ary_data['gcid']),
					'ad_pic_url'=>D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i])),
					'sort_order'=>intval($ary_data['sort_order_'.$i]),
					'ad_url'=>$ary_data['GY_SHOP_TOP_AD_'.$i.'_URL']
				);		
				$res = $RelatedGoodcategoryAds->data($ary_insert)->add();
				if(!$res){
					M('')->rollback();
					$this->error('类目广告图片保存失败');
				}				
			}
		}
		M('')->commit();
		$this->success('类目促销区编辑成功');
	}
	
    /**
     * 分类设置菜单
     */
    function pageSet(){
        $this->getSubNav(3, 1, 40);
        $data = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
        $this->assign($data);
        $this->display();
    }

    public function doSet(){
        $data = $this->_post();
        $type = isset($data['GCTYPE']) && $data['GCTYPE'] == 1 ?'1':'0';
        $res = D('SysConfig')->setConfig('GY_GOODS_CATEGORY', 'GCTYPE', $type, '分类属性开启');
        if($res){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    /**
     * 删除类目图片
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2014-07-23
     */   
    public function delgcPic() {
    	$int_gc_id=$this->_get('gc_id');  
    	$bool_res = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gc_id'=>$int_gc_id))
    	->data(array('gc_pic_url'=>'','gc_update_time'=>date('Y-m-d H:i:s')))
    	->save();
    	if($bool_res){
    		$this->success('删除类目图片成功');
    	}else{
    		$this->error('删除类目图片失败');
    	}
    }

    public function addGoodsToCategory(){
		//验证是否制定分组ID
		if(!isset($_POST["gc_id"]) || !is_numeric($_POST["gc_id"])){
			echo json_encode(array("status"=>false,"msg"=>"参数错误：非法的商品分类ID参数传入。"));
			exit;
		}
		
		//验证是否选择商品
		if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
			echo json_encode(array("status"=>false,"msg"=>"请选择您要加入分类的商品。"));
			exit;
		}
		
		//验证分组是否存在
		$array_check_exists = D("GoodsCategory")->where(array("gc_id"=>$_POST["gc_id"]))->find();
		if(empty($array_check_exists) || !is_array($array_check_exists)){
			echo json_encode(array("status"=>false,"msg"=>"商品分类不存在。"));
			exit;
		}
		
		$array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
		foreach($array_goods_ids as $key => $val){
			$array_save_data = array();
			$array_save_data["gc_id"] = $_POST["gc_id"];
			$array_save_data["g_id"] = $val;
			//使用replace into 方式存入数据库
			if(false === D("RelatedGoodsCategory")->add($array_save_data,array(),true)){
				echo json_encode(array("status"=>false,"msg"=>"将商品加入分类时遇到错误。"));
				exit;
			}
		}
		echo json_encode(array("status"=>true,"msg"=>"操作成功"));
		exit;
	}
	
	public function removeGoodsToCategory(){
		//验证是否制定分组ID
		if(!isset($_POST["gc_id"]) || !is_numeric($_POST["gc_id"])){
			echo json_encode(array("status"=>false,"msg"=>"参数错误：非法的商品分类ID参数传入。"));
			exit;
		}
		
		//验证是否选择商品
		if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
			echo json_encode(array("status"=>false,"msg"=>"请选择您要移出分类的商品。"));
			exit;
		}
		
		//验证分组是否存在
		$array_check_exists = D("GoodsCategory")->where(array("gc_id"=>$_POST["gc_id"]))->find();
		if(empty($array_check_exists) || !is_array($array_check_exists)){
			echo json_encode(array("status"=>false,"msg"=>"商品分类不存在。"));
			exit;
		}
		
		$array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
		foreach($array_goods_ids as $key => $val){
			$array_save_data = array();
			$array_save_data["gc_id"] = $_POST["gc_id"];
			$array_save_data["g_id"] = $val;
			if(false === D("RelatedGoodsCategory")->where($array_save_data)->delete()){
				echo json_encode(array("status"=>false,"msg"=>"将商品从分类中移除时遇到错误。"));
				exit;
			}
		}
		echo json_encode(array("status"=>true,"msg"=>"操作成功"));
		exit;
	}
}

?>
