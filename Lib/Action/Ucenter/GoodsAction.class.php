<?php
/**
 * 后台商品资料控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsAction extends CommonAction {

    /**
     * 控制器初始化
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     */
    public function _initialize() {
        parent::_initialize();
    }
	
	/**
	 * 商品添加页面表单
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-28
	 */
	public function goodsAdd(){
		//获取商品类型并传递到模板
		$array_type = D("GoodsType")->where(array("gt_status"=>1))->select();
		if(!is_array($array_type) && empty($array_type)){
			$this->error("没有找到商品类型，您需要先添加商品类型以后才能继续操作。",U("Admin/GoodsType/pageList"));
		}
		$this->assign("array_type",$array_type);
		//获取商品分类并传递到模板
		$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
		$this->assign("array_category",$array_category);
		//获取商品品牌并传递到模板
		$this->assign("array_brand",D("GoodsBrand")->where(array("gb_status"=>1))->select());
		//获取所有的会员等级并传递到模板
		$this->assign("array_member_level",D("MembersLevel")->where(array("ml_status"=>1))->select());
		//行业特殊字段加载
		$array_industry_field = D("SysConfig")->where(array("sc_module"=>"GOODS_INDUSTRY_SPEC"))->select();
		$array_industry_config = array();
		foreach($array_industry_field as $key => $val){
			$array_industry_config[$val["sc_key"]] = json_decode($val["sc_value"],true);
		}
		$this->assign("industry_filed_config",$array_industry_config);
		$this->display();
	}
	
	/**
	 * error 提示方法代码重构
	 */
	public function errorMsg($str_message){
		//C("LAYOUT_ON",false);
		//header("content-type:text/html;charset=utf-8");
		//$this->assign("message",$str_message);
		//$this->display("error");
		$this->error($str_message);
		exit;
	}
	
	public function successMsg($str_message,$jump_url=""){
		/*
		C("LAYOUT_ON",false);
		header("content-type:text/html;charset=utf-8");
		$this->assign("message",$str_message);
		$this->assign("jump_url",$jump_url);
		$this->display("success");
		*/
		$this->success($str_message,$jump_url);
		exit;
	}
	
    /**
     * 验证商品编码是否唯一
     */
    public function checkGsn() {
    	$check_result = D("GoodsBase")->where(array('g_sn'=>$this->_get('g_sn')))->getField("g_id");
        if ($check_result && is_numeric($check_result)) {
            $this->ajaxReturn('商品编码已经被使用！');
        } else {
            $this->ajaxReturn(true);
        }
    }
    
	/**
	 * 商品添加逻辑处理页面
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-28
	 */
	public function doGoodsAdd(){
		//尊敬的开发者：debug参数为true的时候，输出提示消息时会输出最后执行的sql
		$debug = true;
		//TODO：这里需要有必要的数据验证
		/**
		 * 至少需要完成以下数据的验证：如果完善以下验证以后，请将TODO标记去除。
		 * 1. 商品名称是否输入。
		 * 2. 商品-商家编码是否唯一
		 * 3. 商品住图片是否录入
		 * 4. 商品类型是否选择
		 * 5. 商品价格是否录入
		 * 6. 如果启用规格，则验证货号是否输入并逐个验证唯一性
		 * 7. 如果启用规格，则验证商品价格是否输入
		 * 8. 如果启用规格，则验证商品重量是否输入
		 * 9. 商品分类至少选择一个。
		 * 商品资料涉及到的表：fx_goods,fx_goods_info,fx_goods_products,fx_goods_pictures
		 * fx_related_goods_category,fx_product_member_level_price,fx_related_goods_spec,
		 * 等等
		 **/
		//加入必要的数据验证 - 完成上述的10步数据验证
		//商品名称的输入验证
		if(!isset($_POST["goods_info"]['g_name']) || "" == $_POST["goods_info"]['g_name']){
			$this->errorMsg("商品名称必须输入！");
		}
		
		//商家编码的输入验证
		if(!isset($_POST["goods_info"]['g_sn']) || "" == $_POST["goods_info"]['g_sn']){
			$this->errorMsg("商家编码必须输入！");
		}
		if(preg_match("/[^a-zA-Z0-9\._-]+/",$_POST["goods_info"]['g_sn'])){
			$this->errorMsg("商品编码不符合要求(	字母、数字或“_”、“-”、“.”组成)！");
		}		
		//商家编码的唯一性验证
		$check_result = D("GoodsBase")->where(array('g_sn'=>$_POST["goods_info"]['g_sn']))->getField("g_id");
        if($check_result && is_numeric($check_result)){
			$string_g_name = D("GoodsInfo")->where(array("g_id"=>$array_result))->getField("g_name");
			$this->errorMsg("商品编码已经被“{$string_g_name}”占用！");
		}
		
		//验证商品主图是否录入
		if(!isset($_POST["goods_info"]['g_picture']) || "" == $_POST["goods_info"]['g_picture']){
			$this->errorMsg("请上传商品主图！");
		}
		
		//商品分类至少选择一个。
		if(!isset($_POST["related_goods_category"]) || empty($_POST["related_goods_category"])){
			$this->errorMsg("请至少选择一个商品分类。");
		}
		
		//验证是否选择商品类型
		/** 
		 * 此处验证先注释掉，允许不选择商品类型，如需修改，请打开这里的验证
		if(!isset($_POST["goods"]["gt_id"]) || !is_numeric($_POST["goods"]["gt_id"]) || $_POST["goods"]["gt_id"] <=0 ){
			$this->errorMsg("请选择商品类型");
		}*/
		
		//根据是否启用规格 对商品价格等基本数据做一个验证
		if(isset($_POST["goods_products"]["pdt_sn"]) && !empty($_POST["goods_products"]["pdt_sn"])){
			//如果启用了规格，则验证规格属性上的价格是否都输入了
			$product_sn = $_POST["goods_products"]["pdt_sn"];
			$pdt_stock = $_POST["goods_products"]["pdt_stock"];
			$pdt_cost_price = $_POST["goods_products"]["pdt_cost_price"];
			$pdt_market_price = $_POST["goods_products"]["pdt_market_price"];
			$pdt_sale_price = $_POST["goods_products"]["pdt_sale_price"];
			$pdt_member_level_price = $_POST["goods_products"]["member_level_price"];
			$pdt_weight = $_POST["goods_products"]["pdt_weight"];
			$pdt_min_num = $_POST["goods_products"]["pdt_min_num"];
			$str_spec_vids = $_POST["goods_products"]["spec_vids"];
			foreach($product_sn as $key => $pdt_sn){
				//验证货号是否输入
				if(!$pdt_sn || "" == $pdt_sn){
					$this->errorMsg("您至少有一个规格没有输入货号/商品编码");
				}
				//验证货号的唯一性
				$ary_pdtsn_check = D("GoodsProductsTable")->where(array("pdt_sn"=>$pdt_sn))->find();
				if(is_array($ary_pdtsn_check) && !empty($ary_pdtsn_check)){
					$this->errorMsg("SKU商品编码“{$pdt_sn}”已经被其他商品占用。");
				}
				//验证是否输入库存数量
				if(!isset($pdt_stock[$key]) || !is_numeric($pdt_stock[$key])){
					$this->errorMsg("SKU“{$pdt_sn}”的库存数量值不合法。");
				}
				
				//验证是否输入销售价格
				if(!isset($pdt_sale_price[$key]) || !is_numeric($pdt_sale_price[$key])){
					$this->errorMsg("SKU“{$pdt_sn}”的销售价格数值不合法。");
				}
			}
		}
		
		//系统时间定义
		$string_date_time = date("Y-m-d H:i:s");
		
		//商品基本信息表 fx_goods 写入数据
		$array_fx_goods = array();
		$ary_goods = $_POST["goods"];
		$ary_info = $_POST["goods_info"];
        //商品用户名ID
        $array_fx_goods["gm_id"] = $_SESSION['Members']['m_id'];
		//商品品牌ID
		$array_fx_goods["gb_id"] = (isset($ary_goods["gb_id"]) && is_numeric($ary_goods["gb_id"]))?$ary_goods["gb_id"]:0;
		//商品类型ID，如果等于0表是此商品不启用规格。注意：此值大于1不表示启用规格。
		$array_fx_goods["gt_id"] = (isset($ary_goods["gt_id"]) && is_numeric($ary_goods["gt_id"]))?$ary_goods["gt_id"]:0;
		$array_fx_goods["g_on_sale"] = (isset($ary_goods["g_on_sale"]) && is_numeric($ary_goods["g_on_sale"]))?$ary_goods["g_on_sale"]:2;
		$array_fx_goods["g_sn"] = (isset($ary_info["g_sn"]) && "" != trim($ary_info["g_sn"]))?trim($ary_info["g_sn"]):'';
		$array_fx_goods["g_new"] = (isset($ary_goods["g_new"]) && is_numeric($ary_goods["g_new"]))?$ary_goods["g_new"]:0;
		$array_fx_goods["g_hot"] = (isset($ary_goods["g_hot"]) && is_numeric($ary_goods["g_hot"]))?$ary_goods["g_hot"]:0;
		$array_fx_goods["g_gifts"] = (isset($ary_goods["g_gifts"]) && is_numeric($ary_goods["g_gifts"]))?$ary_goods["g_gifts"]:0;
		$array_fx_goods["g_pre_sale_status"] = (isset($ary_goods["g_pre_sale_status"]) && is_numeric($ary_goods["g_pre_sale_status"]))?$ary_goods["g_pre_sale_status"]:0;
		//edit by Mithern 增加是否赠品，是否处方药字段
		if(isset($ary_goods["g_is_prescription_rugs"]) && in_array($ary_goods["g_is_prescription_rugs"],array(0,1))){
			$array_fx_goods["g_is_prescription_rugs"] = $ary_goods["g_is_prescription_rugs"];
		}
		
		//edit by Mithern 增加关联商品字段，关联类型字段，其中1为单向关联，2为双向关联，0为不关联
		$array_fx_goods["g_related_type"] = (isset($ary_goods["g_related_type"]) && in_array($ary_goods["g_related_type"],array(1,2)))?$ary_goods["g_related_type"]:0;
		//关联商品的ID
		$array_fx_goods["g_related_goods_ids"] = (isset($ary_goods["g_related_goods_ids"]) && "" != $ary_goods["g_related_goods_ids"])?trim($ary_goods["g_related_goods_ids"],","):"";
		
		$array_fx_goods["g_create_time"] = $string_date_time;
		$array_fx_goods["g_update_time"] = $string_date_time;
		//事务开始，商品资料数据复杂，必须启用事务。
		D("GoodsBase")->startTrans();
		//修改D方法Goods模型对应的表名
		
		$int_goods_id = D("GoodsBase")->add($array_fx_goods);
		if(false === $int_goods_id){
			D("GoodsBase")->rollback();
			if(true === $debug){
				$this->errorMsg("1111" . D("GoodsBase")->getLastSql());
			}
			$this->errorMsg("商品资料添加失败。CODE:FX-GOODS;");
			exit;
		}
		
		//如果商品资料的关联关系是双向关联，则还要将关联数据更新到其他商品资料上
		if(2 == $array_fx_goods["g_related_type"] && "" != $array_fx_goods["g_related_goods_ids"]){
			$array_related_goods_ids = explode(',',$array_fx_goods["g_related_goods_ids"]);
			foreach($array_related_goods_ids as $related_goods_id){
				//获取关联的商品的关联商品ID情况
				$string_related_gids = explode(',',D("Goods")->where(array("g_id"=>$related_goods_id))->getField("g_related_goods_ids"));
				$string_related_gids = array_merge($string_related_gids,array($int_goods_id));
				if(false === D("Goods")->where(array("g_id"=>$related_goods_id))->save(array("g_related_goods_ids"=>implode(',',$string_related_gids)))){
					D("Goods")->rollback();
					$this->errorMsg("商品资料添加失败。CODE:FX-GOODS-RELATED-INFO-ERROR;");
				}
			}
		}
		
		
		//商品资料详细信息表 fx_goods_info
		$array_goods_info = array();
		$array_goods_info["g_id"] = $int_goods_id;
		$array_goods_info["g_name"] = (isset($ary_info["g_name"]) && "" != trim($ary_info["g_name"]))?trim($ary_info["g_name"]):'此商品无标题';
		$array_goods_info["g_keywords"] = (isset($ary_info["g_keywords"]) && "" != trim($ary_info["g_keywords"]))?trim($ary_info["g_keywords"]):'';
		$array_goods_info["g_description"] = (isset($ary_info["g_description"]) && "" != trim($ary_info["g_description"]))?trim($ary_info["g_description"]):'';
		$array_goods_info["g_sn"] = (isset($ary_info["g_sn"]) && "" != trim($ary_info["g_sn"]))?trim($ary_info["g_sn"]):'';
		$array_goods_info["g_price"] = 0.00;
		$array_goods_info["g_market_price"] = 0.00;
		//TODO 如果用户未输入库存数，则此处库存数为所有SKU库存数之和。
		$array_goods_info["g_stock"] = 0;
		//TODO 如果没有输入重量，则此处重量为所有SKU重量最大值
		$array_goods_info["g_weight"] = 0;
		$array_goods_info["g_unit"] = (isset($ary_info["g_unit"]) && "" != $ary_info["g_unit"])?$ary_info["g_unit"]:'';
		$array_goods_info["g_desc"] = $ary_info["g_desc"];
		$array_goods_info["g_remark"] = $ary_info["g_remark"];
		//TODO：此处可能需要将上传的图片处理成缩略图。
		$array_goods_info["g_picture"] = '/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$ary_info["g_picture"]),'/'));
		$array_goods_info["g_source"] = 'local';
		//商品自定义字段维护
		$array_goods_info["g_custom_field_1"] = (isset($ary_info["g_custom_field_1"]) && "" != $ary_info["g_custom_field_1"])?$ary_info["g_custom_field_1"]:'';
		$array_goods_info["g_custom_field_2"] = (isset($ary_info["g_custom_field_2"]) && "" != $ary_info["g_custom_field_2"])?$ary_info["g_custom_field_2"]:'';
		$array_goods_info["g_custom_field_3"] = (isset($ary_info["g_custom_field_3"]) && "" != $ary_info["g_custom_field_3"])?$ary_info["g_custom_field_3"]:'';
		$array_goods_info["g_custom_field_4"] = (isset($ary_info["g_custom_field_4"]) && "" != $ary_info["g_custom_field_4"])?$ary_info["g_custom_field_4"]:'';
		$array_goods_info["g_custom_field_5"] = (isset($ary_info["g_custom_field_5"]) && "" != $ary_info["g_custom_field_5"])?$ary_info["g_custom_field_5"]:'';
		$array_goods_info["is_exchange"] = (isset($ary_info["is_exchange"]) && is_numeric($ary_info["is_exchange"]))?$ary_info["is_exchange"]:'0';
		$array_goods_info["point"] = (isset($ary_info["point"]) && is_numeric($ary_info["point"]))?$ary_info["point"]:'0';
		$array_goods_info["g_create_time"] = $string_date_time;
		$array_goods_info["g_update_time"] = $string_date_time;
		if(false === D("GoodsInfo")->add($array_goods_info)){
			D("GoodsInfo")->rollback();
			if(true === $debug){
				$this->errorMsg("2222" . D("GoodsInfo")->getLastSql());
			}
			$this->errorMsg("商品资料录入失败。CODE:FX-GOODS-INFO;");
		}
		
		//商品图片数据录入
		if(isset($_POST["goods_pictures"]) && !empty($_POST["goods_pictures"])){
			$i = 0;
			foreach($_POST["goods_pictures"] as $picture){
				$i ++;
				//TODO 如果是本地图片，此处验证可能无法通过。
				if("" != $picture){
					$array_picture = array();
					$array_picture["g_id"] = $int_goods_id;
					$array_picture["gp_picture"] = '/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$picture),'/'));
					$array_picture["gp_order"] = $i;
					$array_picture["gp_status"] = 1;
					$array_picture["gp_create_time"] = $string_date_time;
					$array_picture["gp_update_time"] = $string_date_time;
					$int_goods_picture_id = D("GoodsPictures")->add($array_picture);
					if(false === $int_goods_picture_id){
						D("GoodsPictures")->rollback();
						if(true === $debug){
							$this->errorMsg("3333" . D("GoodsPictures")->getLastSql());
						}
						$this->errorMsg("商品资料添加失败。CODE:FX-GOODS-PICTURES-$i");
						exit;
					}
				}
			}
		}
		
		//商品分类数据保存到商品-分类关联表中
		if(isset($_POST["related_goods_category"]) && !empty($_POST["related_goods_category"])){
			foreach($_POST["related_goods_category"] as $cat){
				$array_cat = array();
				$array_cat["g_id"] = $int_goods_id;
				$array_cat["gc_id"] = $cat;
				$result = D("RelatedGoodsCategory")->add($array_cat);
				if(false === $result){
					D("RelatedGoodsCategory")->rollback();
					if(true === $debug){
						$this->errorMsg("4444" . D("RelatedGoodsCategory")->getLastSql());
					}
					$this->errorMsg("商品资料保存失败。CODE:FX-RELATED-GOODS-CAT");
				}
			}
		}
		
		//商品扩展属性存入数据库，如果有的话。
		if(isset($_POST["goods_unsales_spec"]) && !empty($_POST["goods_unsales_spec"])){
			foreach($_POST["goods_unsales_spec"] as $gs_id=>$spec_value){
				if("" != $spec_value){
					$array_tmp_spec_info = D("GoodsSpec")->where(array("gs_id"=>$gs_id))->find();
					$int_gsd_id = 0;
					$string_spec_value = $spec_value;
					if(2 == $array_tmp_spec_info["gs_input_type"]){
						if($spec_value <= 0){
							//如果是select类型的扩展属性，且值小于0，则说明未设置此属性的值
							continue;
						}
						$array_tmp_spec_detail = D("GoodsSpecDetail")->where(array("gsd_id"=>$spec_value))->find();
						$int_gsd_id = $spec_value;
						$string_spec_value = $array_tmp_spec_detail["gsd_value"];
					}
					$array_unsale_spec = array();
					$array_unsale_spec["gs_id"] = $gs_id;
					$array_unsale_spec["gsd_id"] = $int_gsd_id;
					$array_unsale_spec["pdt_id"] = 0;
					$array_unsale_spec["gs_is_sale_spec"] = 0;
					$array_unsale_spec["g_id"] = $int_goods_id;
					$array_unsale_spec["gsd_aliases"] = $string_spec_value;
					$array_unsale_spec["gsd_picture"] = "";
					$result = D("RelatedGoodsSpec")->add($array_unsale_spec);
					if(false === $result){
						D("RelatedGoodsSpec")->rollback();
						if(true === $debug){
							$this->errorMsg("5555" . D("RelatedGoodsSpec")->getLastSql());
						}
						$this->errorMsg("商品扩展属性信息录入失败。CODE:RELATED-GOODS-SPEC");
						exit;
					}
				}
			}
		}
		
		//商品SKU数据录入:商品SKU数据录入需要分为以下两种情况
		/**
		 * 情况一：不启用SKU
		 * 请框二：启用SKU
		 * 注意：按照既定规则，在不启用SKU的情况下，也要像products表中写入一条记录。
		 * 如果启用的规格包含规格别名，则将规格别名存入到别名表中
		 * 
		 **/
		if(isset($_POST["goods_products"]["pdt_sn"]) && !empty($_POST["goods_products"]["pdt_sn"])){
			foreach($_POST["goods_products"]["pdt_sn"] as $key => $pdt_sn){
				$array_products = array();
				$array_products["g_id"] = $int_goods_id;
				$array_products["g_sn"] = $array_goods_info["g_sn"];
				$array_products["pdt_sn"] = $pdt_sn;
				$array_products["pdt_sale_price"] = $pdt_sale_price[$key];
				$array_products["pdt_cost_price"] = (is_numeric($pdt_cost_price[$key]))?$pdt_cost_price[$key]:0.00;
				$array_products["pdt_market_price"] = (is_numeric($pdt_market_price[$key]))?$pdt_market_price[$key]:0.00;
				$array_products["pdt_weight"] = is_numeric($pdt_weight[$key])?$pdt_weight[$key]:0;
				$array_products["pdt_min_num"] = is_numeric($pdt_min_num[$key])?$pdt_min_num[$key]:0;
				//商品资料初始化，SKU总库存=可下单库存，冻结库存为0；
				$array_products["pdt_total_stock"] = (is_numeric($pdt_stock[$key]))?$pdt_stock[$key]:0;
				$array_products["pdt_stock"] = $array_products["pdt_total_stock"];
				$array_products["pdt_freeze_stock"] = 0;
				//TODO:此处需要产生一张初始化的库存调整单，并且自动审核通过
				//TODO:此处如果需要设置单次下单最大值，价格区间，备注，承诺到货日，承诺发货日等数据，可以在此处修改
				$array_products["pdt_create_time"] = $string_date_time;
				$array_products["pdt_update_time"] = $string_date_time;
				$int_products_id = D("GoodsProductsTable")->add($array_products);
				if(false === $int_products_id){
					D("GoodsProductsTable")->rollback();
					if(true === $debug){
						$this->errorMsg("6666" . D("GoodsProductsTable")->getLastSql());
					}
					$this->errorMsg("商品资料录入失败。CODE:FX-GOODS-PRODUCTS");
					exit;
				}
				//此SKU关联的属性存入商品-SKU-SPEC关联表
				/**
				 * 此处先将$str_spec_vids按照逗号分隔开来，然后获取规格值对应的规格ID
				 * 如果是系统颜色属性，如果设置了颜色图片，还要将图片存入数据库中
				 **/
				$array_vid = explode(';',trim($str_spec_vids[$key],";"));
				foreach($array_vid as $pidvid){
					$array_tmp_pidvid = explode(':',$pidvid);
					$int_pid = $array_tmp_pidvid[0];
					$int_vid = $array_tmp_pidvid[1];
					$array_related_spec = array();
					$array_related_spec["gs_id"] = $int_pid;
					$array_related_spec["gsd_id"] = $int_vid;
					$array_related_spec["pdt_id"] = $int_products_id;
					$array_related_spec["gs_is_sale_spec"] = 1;
					$array_related_spec["g_id"] = $int_goods_id;
					$array_related_spec["gsd_aliases"] = (isset($_POST["spec_value"][$int_vid]) && "" != $_POST["spec_value"][$int_vid])?$_POST["spec_value"][$int_vid]:'';
					$array_related_spec["gsd_picture"] = (isset($_POST["spec_image"][$int_vid]) && "" != $_POST["spec_image"][$int_vid])?$_POST["spec_image"][$int_vid]:'';
					$result = D("RelatedGoodsSpec")->add($array_related_spec);
					if(false === $result){
						D("RelatedGoodsSpec")->rollback();
						if(true === $debug){
							$this->errorMsg("7777" . D("RelatedGoodsSpec")->getLastSql());
						}
						$this->errorMsg("商品资料保存失败。CODE:FX-RELATED-GOODS-SPEC;");
						exit;
					}
				}
				
				//如果为此规格设置了会员等级固定价，则还要将会员等级固定价存入数据库。
				if("" == $pdt_member_level_price[$key]){
					continue;
				}
				$array_ml_price = explode(';',trim(trim($pdt_member_level_price[$key]),';'));
				foreach($array_ml_price as $ml_price){
					$array_tmp_info = explode(':',$ml_price);
					$int_ml_id = $array_tmp_info[0];
					$float_price = $array_tmp_info[1];
					if("" != $float_price){
						$array_ml_price = array();
						$array_ml_price["ml_id"] = $int_ml_id;
						$array_ml_price["pdt_id"] = $int_products_id;
						$array_ml_price["pmlp_price"] = $float_price;
						$array_ml_price["pmlp_status"] = 1;
						$array_ml_price["pmlp_create_time"] = $string_date_time;
						$array_ml_price["pmlp_update_time"] = $string_date_time;
						if(false === D("ProductMemberLevelPrice")->add($array_ml_price)){
							D("ProductMemberLevelPrice")->rollback();
							if(true === $debug){
								$this->errorMsg("8888" . D("ProductMemberLevelPrice")->getLastSql());
							}
							$this->errorMsg("商品资料保存失败。CODE:PRODUCT-MEMBER-LEVEL-PRICE;");
							exit;
						}
					}
				}
			}
		}else{
			//没有启用规格的时候，也要往规格表中插入一条记录的数据。
			//TODO:此处代码待补全。勿要忘记库存初始化的库存调整单数据插入。
			$array_product_info = array();
			$array_product_info["g_id"] = $int_goods_id;
			$array_product_info["g_sn"] = $array_goods_info["g_sn"];
			$array_product_info["pdt_sn"] = $array_goods_info["g_sn"];
			$array_product_info["pdt_sale_price"] = (isset($_POST["pdt_sale_price"]) && is_numeric($_POST["pdt_sale_price"]))?$_POST["pdt_sale_price"]:0.00;
			$array_product_info["pdt_cost_price"] = (isset($_POST["pdt_cost_price"]) && is_numeric($_POST["pdt_cost_price"]))?$_POST["pdt_cost_price"]:0.00;
			$array_product_info["pdt_market_price"] = (isset($_POST["pdt_market_price"]) && is_numeric($_POST["pdt_market_price"]))?$_POST["pdt_market_price"]:0.00;
			$array_product_info["pdt_weight"] = (isset($_POST["pdt_weight"]) && is_numeric($_POST["pdt_weight"]))?$_POST["pdt_weight"]:0;
			$array_product_info["pdt_min_num"] = (isset($_POST["pdt_min_num"]) && is_numeric($_POST["pdt_min_num"]))?$_POST["pdt_min_num"]:0;
			//TODO:此处需要产生一张初始化商品库存的单据
			$array_product_info["pdt_total_stock"] = (isset($_POST["pdt_total_stock"]) && is_numeric($_POST["pdt_total_stock"]))?$_POST["pdt_total_stock"]:0;
			$array_product_info["pdt_stock"] = $array_product_info["pdt_total_stock"];
			$array_product_info["pdt_freeze_stock"] = 0;
			$array_product_info["pdt_status"] = 1;
			$array_product_info["pdt_create_time"] = $string_date_time;
			$array_product_info["pdt_update_time"] = $string_date_time;
			$int_product_id = D("GoodsProductsTable")->add($array_product_info);
			if(false === $int_product_id){
				D("GoodsProductsTable")->rollback();
				if(true === $debug){
					$this->errorMsg("9999" . D("GoodsProductsTable")->getLastSql());
				}
				$this->errorMsg("商品资料保存失败。CODE:GOODS-PRODUCT-SIMPLE;");
				exit;
			}
			//此处需要将会员等级价数据存入数据库中，数据表暂缺
			foreach($_POST["product_member_level_price"] as $ml_id => $ml_price){
				if("" != $ml_price && is_numeric($ml_price)){
					if(floatval($ml_price)<=0 ){
						$ml_price = '';
					}
					$array_member_level_price = array();
					$array_member_level_price["ml_id"] = $ml_id;
					$array_member_level_price["pdt_id"] = $int_product_id;
					$array_member_level_price["pmlp_price"] = $ml_price;
					$array_member_level_price["pmlp_status"] = 1;
					$array_member_level_price["pmlp_create_time"] = $string_date_time;
					$array_member_level_price["pmlp_update_time"] = $string_date_time;
					if(false === D("ProductMemberLevelPrice")->add($array_member_level_price)){
						D("ProductMemberLevelPrice")->rollback();
						if(true === $debug){
							$this->errorMsg("10101010" . D("ProductMemberLevelPrice")->getLastSql());
						}
						$this->errorMsg("商品资料保存失败。CODE:PRODUCT-MEMBER-LEVEL-PRICE-SIMPLE;");
						exit;
					}
				}
			}
		}
		
		//更新商品信息表中的g_price为货品表中最高的价格：
		//需要先获取SKU中最高的市场价格；然后更新到goods_info表中；
		//更新商品表中的库存为货品表中库存之和：
		//需要获取SKU中的库存数量之和，然后更新到goods_info表。
		$array_fetch_condition = array("g_id"=>$int_goods_id,"pdt_satus"=>1);
		$string_fields = "g_id,max(`pdt_market_price`) as `g_market_price`,max(`pdt_sale_price`) as `g_price`,max(`pdt_weight`) as `g_weight`,sum(`pdt_total_stock`) as `g_stock`";
		$array_result = D("GoodsProductsTable")->where($array_fetch_condition)->getField($string_fields);
		if(false === $array_result){
			D("GoodsProductsTable")->rollback();
			$this->errorMsg("商品资料保存失败。CODE:GET-MAX-PRODUCT-MARKET-PRICE-FAILED;");
			exit;
		}
		$array_save_data = array();
		$array_save_data = $array_result[$int_goods_id];
		$result = D("GoodsInfo")->where(array("g_id"=>$int_goods_id))->save($array_save_data);
		if(false === $result){
			D("GoodsInfo")->rollback();
			$this->errorMsg("商品资料保存失败。CODE:UPDATE-MAX-MARKET-PRICE-FAILED;");
			exit;
		}
		
		//事务提交
		D("GoodsBase")->commit();
		//提示操作成功，根据按钮类型确定完成以后跳转到哪里。此处支持三种模式：
		/**
		 * 两种操作成功以后的跳转模式
		 * 1. 继续添加
		 * 2. 继续添加同类型的文件，这种需要商品添加表单页支持
		 **/
		$jump_type = 1;
		switch($jump_type){
			case 1:
				//继续添加商品资料
				$string_jump_url = U("Ucenter/Goods/pageList");
				break;
			case 2:
				//继续添加同类型的商品
				$string_jump_url = U("Ucenter/Goods/goodsAdd") . "?gt_id=" . $array_fx_goods["gt_id"];
				break;
		}
		
		//提示商品资料添加成功，并且跳转到目标页面。
		$this->successMsg("商品资料保存成功。",$string_jump_url);
		exit;
	}
	
	/**
	 * 商品编辑页面表单
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-28
	 */
	public function goodsEdit(){
		if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
			$this->error("商品编辑参数传递错误:非法的商品ID参数传入");
			exit;
		}
		$this->getSubNav(3, 0, 30);
		
		//获取商品基本资料
		$int_g_id = $_GET["id"];
		$array_goods = D("GoodsBase")->where(array("g_id"=>$int_g_id))->find();
		if(!is_array($array_goods) || empty($array_goods)){
			$this->error("您要编辑的商品资料不存在。");
			exit;
		}
		
		//获取商品详细资料
		$array_goods_info = D("GoodsInfo")->where(array("g_id"=>$int_g_id))->find();
		$this->assign("goods",$array_goods);
		$this->assign("goods_info",$array_goods_info);
		
		//获取商品分类并传递到模板
		$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
		$this->assign("array_category",$array_category);
		
		//获取此商品关联的分类信息
		$array_catid = D("RelatedGoodsCategory")->where(array("g_id"=>$int_g_id))->getField("gc_id",true);
		$this->assign("array_catid",$array_catid);
		//获取商品资料图片并传递到模板
		//首先处理商品主图，如果没有主图，则调用出默认的主图图片
		$array_images = array();
		$array_images[0] = array('gp_id'=>0,'gp_picture'=>"",'gp_order'=>0,"input_value"=>"");
		if("" == $array_goods_info["g_picture"] || !file_exists(APP_PATH . ltrim($array_goods_info["g_picture"],'/'))){
			$array_images[0]["g_picture"] = '/Public/Admin/images/product_image_index.png';
			$array_images[0]["input_value"] = '';
		}else{
			$array_images[0]["g_picture"] = '/' . ltrim(trim($array_goods_info["g_picture"]),'/');
			$array_images[0]["input_value"] =  '/' . ltrim(trim($array_goods_info["g_picture"]),'/');
		}
		//处理商品的细节图片，最多允许增加四张细节图片
		$array_tmp_images = D("GoodsPictures")->where(array("g_id"=>$int_g_id,"gp_status"=>1))->order(array("gp_order"=>'asc'))->getField('gp_id,gp_picture,gp_order');
		foreach($array_tmp_images as $key => $val){
			$val["g_picture"] =  trim($val["gp_picture"]);
			$val["input_value"] = trim($val["gp_picture"]);
			$array_images[] = $val;
		}
		//$array_images = array_merge($array_images,$array_tmp_images);
		unset($array_tmp_images);
		$image_nums = 10;
		for($i = count($array_images);$i<$image_nums;$i++){
			$array_images[] = array('gp_id'=>0,'g_picture'=>'/Public/Admin/images/product_image_desc.png','gp_order'=>$i,"input_value"=>"");
		}
		$this->assign("array_images",$array_images);
		
		//获取商品类型并传递到模板
		$array_goods_type = D("GoodsType")->where(array("gt_status"=>1))->select();
		$this->assign("array_type",$array_goods_type);
		
		//获取商品品牌并传递到模板
		$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
		$this->assign("array_brand",$array_brand);
		
		$int_type_id = $array_goods["gt_id"];
		//获取当前商品类型关联的所有属性ID，用于后面查询商品扩展和销售属性使用
		$array_spec_ids = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>$int_type_id))->getField("gs_id",true);
		$array_fetch_sale_cond = array("gs_id"=>array("IN",$array_spec_ids),"gs_is_sale_spec"=>1);
		
		//获取会员等级数据
		$array_member_level = D("MembersLevel")->where(array("ml_status"=>1))->select();
		
		//判断当前商品是否启用规格，这里的判断方式是：
		//根据sku的关联信息做判断，如果有关联的销售属性
		//则认为是启用了规格的商品。
		$bool_is_enabled_spec = "display:none;";
		$enable = 1;
		$disabled_pdt = array();
		$array_products = array();
		//获取当前商品所属类型下的全部商品销售属性ID和名称
		$array_sale_product = D("GoodsSpec")->where($array_fetch_sale_cond)->order(array("gs_order"=>"asc"))->getField("gs_id,gs_name,gs_order");
		$array_related_sale_spec = D("RelatedGoodsSpec")->where(array("g_id"=>$int_g_id,"gs_is_sale_spec"=>1))->select();
		//echo D("RelatedGoodsSpec")->getLastSql();exit;
		//定义一个数组，用于保存被当前商品用掉的商品销售属性ID
		$array_goods_used_spec_ids = array();
		if(empty($array_related_sale_spec)){
			//此商品没有启用规格
			$bool_is_enabled_spec = '';
			$enable = 0;
			$disabled_pdt = D("GoodsProductsTable")->where(array("g_id"=>$int_g_id,"pdt_status"=>1))->find();
			//获取商品的会员等级折扣数据
			$ml_price_info = D("ProductMemberLevelPrice")->where(array("pdt_id"=>$disabled_pdt["pdt_id"]))->getField("ml_id,pmlp_price");

			foreach($array_member_level as $key => $val){
				//dump($ml_price_info[$val["ml_id"]['pmlp_price']]);
				$array_member_level[$key]["ml_price"] = isset($ml_price_info[$val["ml_id"]])?$ml_price_info[$val["ml_id"]]:"";
				//这里先不计算折扣
				
				$array_member_level[$key]["ml_discont"] = sprintf('%.4f',$array_member_level[$key]["ml_price"]/$disabled_pdt['pdt_sale_price']);
			}
		}else{
			//获取商品SKU详细资料
			$array_products = D("GoodsProductsTable")->where(array("g_id"=>$int_g_id))->order("pdt_id asc")->select();
			foreach($array_products as $key => $val){
				$int_pdt_id = $val["pdt_id"];
				$array_spec = array();
				$spec_pidvid = "";
				//获取此SKU关联的所有的规格ID和规格值ID
				$array_info = D("RelatedGoodsSpec")->where(array("pdt_id"=>$int_pdt_id,"gs_is_sale_spec"=>1))->getField("gs_id,gsd_id,gsd_aliases");
				foreach($array_sale_product as $pid => $pname){
					foreach($array_info as $k_pid => $detail_info){
						//判断当前遍历的商品属性ID是否被使用
						if($k_pid == $pid){
							if(!in_array($array_goods_used_spec_ids)){
								$array_goods_used_spec_ids[] = $pid;
							}
							if(888 == $pid){
								$detail_values = D("GoodsSpecDetail")->where(array('gsd_id'=>$detail_info["gsd_id"]))->find();
								$detail_info = array_merge($detail_info,$detail_values);
							}
							$array_spec[] = $detail_info;
							$spec_pidvid .= $k_pid . ':' . $detail_info["gsd_id"] . ";";
						}
					}
				}
				//获取规格的会员等级固定价格
				$array_fixed_mlprice = D("ProductMemberLevelPrice")->where(array("pdt_id"=>$int_pdt_id))->getField("ml_id,pmlp_price");
				$array_products[$key]["member_level_price"] = "";
				if(!empty($array_fixed_mlprice)){
					foreach($array_fixed_mlprice as $ml_id=>$price){
						$array_products[$key]["member_level_price"] .= $ml_id . ":" . $price . ";";
					}
				}
				$array_products[$key]["spec_info"] = $array_spec;
				$array_products[$key]["spec_pidvid"] = $spec_pidvid;
			}
		}
		$this->assign("enabled_spec",$bool_is_enabled_spec);
		$this->assign("enable",$enable);
		$this->assign("disabled_pdt",$disabled_pdt);
		$this->assign("product_list",$array_products);
		$array_tmp_sale_product = $array_sale_product;
		foreach($array_tmp_sale_product as $key => $val){
			if(!in_array($key,$array_goods_used_spec_ids)){
				unset($array_sale_product[$key]);
			}
		}
		$this->assign("array_spec",$array_sale_product);
		//获取商品资料的扩展属性资料，并传递到前台模板
		$array_fetch_unsale_cond = array("gs_id"=>array("IN",$array_spec_ids),"gs_is_sale_spec"=>0,"gs_status"=>'1');
		$array_unsale_spec = D("GoodsSpec")->where($array_fetch_unsale_cond)->select();
		foreach($array_unsale_spec as $key => $val){
			$array_tmp_cond = array("gs_id"=>$val["gs_id"],'g_id'=>$int_g_id,'gs_is_sale_spec'=>0);
			$array_tmp_value = D("RelatedGoodsSpec")->where($array_tmp_cond)->find();
			$array_unsale_spec[$key]["gsd_id"] = 0;
			$array_unsale_spec[$key]["gsd_aliases"] = "";
			if(is_array($array_tmp_value) && !empty($array_tmp_value)){
				$array_unsale_spec[$key]["gsd_id"] = $array_tmp_value["gsd_id"];
				$array_unsale_spec[$key]["gsd_aliases"] = $array_tmp_value["gsd_aliases"];
			}
			//如果此属性是通过下拉选框方式取值，则这里需要获取这个属性的所有属性值
			if(2 == $val["gs_input_type"]){
				$array_unsale_spec[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id"=>$val["gs_id"],"gsd_status"=>array('neq','0')))->order(array("gsd_order"=>"asc"))->select();
			}
		}
		$this->assign("array_spec_info",$array_unsale_spec);
		
		//获取商品的销售属性数据，并传递到模板
		$array_sale_spec = D("GoodsSpec")->where($array_fetch_sale_cond)->select();
		foreach($array_sale_spec as $key => $val){
			//需要获取这个属性的所有属性值
			$array_spec_detail = D("GoodsSpecDetail")->where(array("gs_id"=>$val["gs_id"]))->order(array("gsd_order"=>"asc"))->select();
			$array_cond = array("gs_id"=>$val["gs_id"],"g_id"=>$int_g_id,"gs_is_sale_spec"=>1);
			foreach($array_spec_detail as $k => $v){
				$checked = "0";
				$gsd_aliases = $v["gsd_value"];
				$array_cond["gsd_id"] = $v["gsd_id"];
				$array_result = D("RelatedGoodsSpec")->where($array_cond)->find();
				if(is_array($array_result) && !empty($array_result)){
					$checked = 1;
					$gsd_aliases = $array_result["gsd_aliases"];
				}
				$array_spec_detail[$k]["checked"] = $checked;
				$array_spec_detail[$k]["gsd_value"] = $gsd_aliases;
			}
			$array_sale_spec[$key]["spec_detail"] = $array_spec_detail;
		}
		//print_r($array_sale_spec);exit;
		$this->assign("array_sale_spec",$array_sale_spec);
		//dump($array_member_level);die();
		$this->assign("array_member_level",$array_member_level);
		
		//行业特殊字段加载
		$array_industry_field = D("SysConfig")->where(array("sc_module"=>"GOODS_INDUSTRY_SPEC"))->select();
		$array_industry_config = array();
		foreach($array_industry_field as $key => $val){
			$array_industry_config[$val["sc_key"]] = json_decode($val["sc_value"],true);
		}
		$this->assign("industry_filed_config",$array_industry_config);
		
		//关联商品信息
		if($array_goods["g_related_goods_ids"] != ""){
			$related_goods_list = D("GoodsInfo")->where(array("g_id"=>array("IN",explode(',',trim($array_goods["g_related_goods_ids"],',')))))->select();
			$this->assign("related_goods_list",$related_goods_list);
		}
		
		//获取此商品类型下所有的商品属性
		$this->display();
	}
	
	/**
	 * 商品编辑逻辑处理页
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-28
	 */
	public function doGoodsEdit(){
    //echo "<pre>";print_r($_POST);exit;
		//商品资料的保存，首先第一步是数据验证
		$array_goods_base = $_POST["goods"];
		if(!isset($array_goods_base["g_id"]) || !is_numeric($array_goods_base["g_id"])){
			$this->errorMsg("参数错误：未指定要编辑的商品ID");
		}
		$int_g_id = $array_goods_base["g_id"];
		if(!isset($array_goods_base["g_on_sale"]) || !in_array($array_goods_base["g_on_sale"],array(1,2,3))){
			$this->errorMsg("商品上架/下架状态未选择");
		}
		$array_goods_info = $_POST["goods_info"];
		if(!isset($array_goods_info["g_name"]) || "" == $array_goods_info["g_name"]){
			$this->errorMsg("商品名称不能为空。");
		}
		//验证商品货号是否输入及其唯一性
		if(!isset($array_goods_info["g_sn"]) || "" == $array_goods_info["g_sn"]){
			$this->errorMsg("商品编号必须输入。");
		}
		
		//验证商品货号是否被除当前商品以外的其他商品占用
		$array_cond = array("g_id"=>array("neq",$int_g_id),"g_sn"=>trim($array_goods_info["g_sn"]));
		$array_result = D("GoodsBase")->where($array_cond)->getField("g_id");
		if($array_result && !empty($array_result)){
			$string_g_name = D("GoodsInfo")->where(array("g_id"=>$array_result))->getField("g_name");
			$this->errorMsg("该商品货号已经被“{$string_g_name}“占用。" . D("GoodsInfo")->getLastSql());
		}
		//验证商品分类是否选择
		if(!isset($_POST["related_goods_category"]) || empty($_POST["related_goods_category"])){
			$this->errorMsg("请选择商品分类。");
		}
		
		//TODO:验证SKU数据是否录入
		
		$string_datetime = date("Y-m-d H:i:s");
		//事务开始
		D("GoodsBase")->startTrans();
		//获取商品基本信息
		$array_goods = D("GoodsBase")->where(array("g_id"=>$int_g_id))->find();
		//edit by Mithern 先记录下来修改前的关联类型和关联的商品ID
		$old_g_related_type = $array_goods["g_related_type"];
		$old_g_related_goods_ids = $array_goods["g_related_goods_ids"];
		
		//商品基本资料更新进入数据库
		$array_goods = array_merge($array_goods,$array_goods_base);
		$array_goods["g_sn"] = trim($array_goods_info["g_sn"]);
		$array_goods["g_update_time"] = $string_datetime;
		$array_goods["g_new"] = (isset($array_goods_base["g_new"]) && is_numeric($array_goods_base["g_new"]))?$array_goods_base["g_new"]:0;
		$array_goods["g_hot"] = (isset($array_goods_base["g_hot"]) && is_numeric($array_goods_base["g_hot"]))?$array_goods_base["g_hot"]:0;
		$array_goods["g_gifts"] = (isset($array_goods_base["g_gifts"]) && is_numeric($array_goods_base["g_gifts"]))?$array_goods_base["g_gifts"]:0;
		$array_goods["g_pre_sale_status"] = (isset($array_goods_base["g_pre_sale_status"]) && is_numeric($array_goods_base["g_pre_sale_status"]))?$array_goods_base["g_pre_sale_status"]:0;
		//关联商品的关联类型
		$array_fx_goods["g_related_type"] = (isset($array_goods["g_related_type"]) && in_array($array_goods["g_related_type"],array(1,2)))?$array_goods["g_related_type"]:0;
		//关联商品的ID
		$array_fx_goods["g_related_goods_ids"] = (isset($array_goods["g_related_goods_ids"]) && "" != $array_goods["g_related_goods_ids"])?trim($array_goods["g_related_goods_ids"],","):"";
		if(false === D("GoodsBase")->where(array("g_id"=>$int_g_id))->save($array_goods)){
			D("GoodsBase")->rollback();
			$this->errorMsg("商品基本资料保存失败。");
		}
		//关联商品信息处理：分为以下几种情况
		/**
		 * 1. 无关联商品变成有关联商品，并且是双向关联的，需要将当前商品与被关联的商品建立关联关系；
		 * 2. 从单向关联变成双向关联，直接增加关联关系
		 * 3. 从双向关联变成单向关联，则去掉其他商品与此商品的关联关系
		 */
		//还要处理关联类型没有发生变化的情况
		if($old_g_related_type != $array_fx_goods["g_related_type"] && "" != $array_fx_goods["g_related_goods_ids"]){
			if((0 == $old_g_related_type || 1 == $old_g_related_type) && 2 == $array_fx_goods["g_related_type"]){
				//这里是上面描述的第一种和第二种情况，未被关联商品增加关联关系
				$array_related_goods = D("Goods")->where(array("g_id"=>array("IN",explode(',',$array_fx_goods["g_related_goods_ids"]))))->select();
				foreach($array_related_goods as $related_goods){
					$array_save = array("g_related_goods_ids"=>implode(',',array_merge(explode(',',$related_goods["g_related_goods_ids"]),array($int_g_id))));
					if(false === D("Goods")->where(array("g_id"=>$related_goods["g_id"]))->save($array_save)){
						D("GoodsBase")->rollback();
						$this->errorMsg("更新商品双向关联关系失败。");
					}
				}
			}else if(1 == $array_fx_goods["g_related_type"] && 2 == $old_g_related_type){
				//这里是上述的第三种情况，需要去掉当前商品与其他商品的主动关联关系
				$array_related_goods = D("Goods")->where(array("g_id"=>array("IN",explode(',',$array_fx_goods["g_related_goods_ids"]))))->select();
				foreach($array_related_goods as $related_goods){
					$array_g_related_goods_ids = explode(',',$related_goods["g_related_goods_ids"]);
					$ary_g_related_goods_ids = $array_g_related_goods_ids;
					foreach($array_g_related_goods_ids as $key=>$val){
						if($val == "" || $val == $int_g_id){
							unset($ary_g_related_goods_ids[$key]);
						}
					}
					$array_save =  array("g_related_goods_ids"=>implode(',',$ary_g_related_goods_ids));
					if(false === D("Goods")->where(array("g_id"=>$related_goods["g_id"]))->save($array_save)){
						D("GoodsBase")->rollback();
						$this->errorMsg("更新商品双向关联关系失败。");
					}
				}
			}
		}else{
			//如果关联类型没有发生变化，且是双向关联的，需要将当前商品与关联的商品建立主动关联关系
			//注意：无关联和单向关联不需要在这里处理，因为更新基本资料时已经处理好了
			if(2 == $array_fx_goods["g_related_type"] && $old_g_related_goods_ids != $array_fx_goods["g_related_goods_ids"]){
				//TODO:此处待补全
				
			}
		}
		
		//保存商品详细资料数据
		$ary_goods_info = D("GoodsInfo")->where(array("g_id"=>$int_g_id))->find();
		$ary_goods_info = array_merge($ary_goods_info,$array_goods_info);
		$ary_goods_info["g_update_time"] = $string_datetime;
		$pictures = '/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$ary_goods_info["g_picture"]),'/'));
		$ary_goods_info["g_picture"] = $pictures;
		if(false === D("GoodsInfo")->where(array("g_id"=>$int_g_id))->save($ary_goods_info)){
			D("GoodsBase")->rollback();
			$this->errorMsg("商品详细资料保存失败。");
		}
		//保存商品-分类关联关系数据 - 这里的处理办法是先删除所有的分类关联关系
		//然后重新添加
		if(false === D("RelatedGoodsCategory")->where(array("g_id"=>$int_g_id))->delete()){
			D("RelatedGoodsCategory")->rollback();
			$this->errorMsg("删除旧的商品关联关系数据失败。");
		}
		foreach($_POST["related_goods_category"] as $catid){
			$array_related = array();
			$array_related = array("g_id"=>$int_g_id,"gc_id"=>$catid);
			if(false === D("RelatedGoodsCategory")->add($array_related)){
				D("RelatedGoodsCategory")->rollback();
				$this->errorMsg("修改商品-分类关联关系失败。");
			}
		}

		/**
		 *
		 * 如果此商品存在扩展属性数据，则更新商品的扩展属性数据
		 *
		 * 商品编辑时扩展属性的处理逻辑规则：
		 * 首先需要判断这个属性的值录入方式，如果是select下拉选框的方式，需要特殊处理
		 * 其次要判断这个属性是否存在于商品-属性关联表中，如果存在，则更新
		 * 更新分为两种情况，一种是属性值发生改变  另外一种是属性值被清空表示删除此属性关联。
		 * 如果是select方式的属性，当值为0时表示是删除此属性关联。否则便是更新或则insert
		 * 警告：：此处逻辑比较复杂，建议修改者（如果需要修改的话）仔细阅读完此段代码并参考
		 * 代码段落中的注释，完全理解逻辑以后再做修改处理
		 * 
		 * by Mithern sunguangxu@guanyisoft.com 2013-06-29  ！！！慎重修改！！！
		 * 
		 */
		if(isset($_POST["goods_unsales_spec"]) && !empty($_POST["goods_unsales_spec"])){
			//由于编辑扩展属性需要验证属性是否已经存在，所以这里将统一的验证条件定义
			//验证的条件是商品-属性关联表中，商品ID等于当前商品的，且属性类型是扩展属性的
			//且属性ID等于本次遍历中的属性ID的（三个条件缺一不可，由于每次属性ID都不同，所以属性ID在循环体中定义）
			$array_cond = array("g_id"=>$int_g_id,"gs_is_sale_spec"=>'0');
			//对页面提交过来的属性进行遍历
			foreach($_POST["goods_unsales_spec"] as $pid => $values){
				//将属性ID加入验证条件中
				$array_cond["gs_id"] = $pid;
				$int_gsd_id = 0;
				$string_gsd_aliases = $values;
				//首先需要判断当前属性的属性值录入类型：有三种，即input，select，input
				$array_tmp_spec_info = D("GoodsSpec")->where(array("gs_id"=>$pid))->find();
				//当gs_input_type值等于2时表示是select方式录入方式
				if(2 == $array_tmp_spec_info["gs_input_type"]){
					//如果select类型属性的属性值小于等于0，则表示要删除此商品与此属性的关联关系
					if($values <= 0 ){
						$mixed_result = D("RelatedGoodsSpec")->where($array_cond)->delete();
						if(false === $mixed_result){
							D("RelatedGoodsSpec")->rollback();
							$this->errorMsg("商品扩展属性删除失败。");
						}
						//当前遍历的属性被删除，循环进入到下一轮
						continue;
					}
					$int_gsd_id = $values;
					$string_gsd_aliases = D("GoodsSpecDetail")->where(array("gsd_id"=>$values))->getField("gsd_value");
				}else{
					//另外一种情况就是input和textarea输入类型的属性值，如果值为空，表示删除此属性与商品关联关系
					if("" == trim(trim($values,"\n"))){
						$mixed_result = D("RelatedGoodsSpec")->where($array_cond)->delete();
						if(false === $mixed_result){
							D("RelatedGoodsSpec")->rollback();
							$this->errorMsg("商品扩展属性删除失败。");
						}
						//当前遍历的属性被删除，循环进入到下一轮
						continue;
					}
				}
				
				//然后判断此商品属性是否已经存在于扩展属性中，如果存在，则更新，否则新增一个属性关联关系
				$array_result = D("RelatedGoodsSpec")->where($array_cond)->find();
				//如果此属性已经存在，则更新此商品扩展属性的值
				if(is_array($array_result) && !empty($array_result)){
					$array_modify_data = array();
					//注意：属性值ID字段不一定为0，因为如果是select类型的属性，是由属性值的
					$array_modify_data["gsd_id"] = $int_gsd_id;
					$array_modify_data["gsd_aliases"] = $string_gsd_aliases;
					if(false === D("RelatedGoodsSpec")->where($array_cond)->save($array_modify_data)){
						D("RelatedGoodsSpec")->rollback();
						$this->errorMsg("修改商品扩展属性数据失败。");
					}
					//当前遍历的属性由于已经存在并且被更新掉，所以循环进入下一轮
					continue;
				}
				
				//如果此属性不存在，则新增一个商品与属性的关联记录
				$array_unsale_spec = array();
				$array_unsale_spec["gs_id"] = $pid;
				//注意：属性值ID字段不一定为0，因为如果是select类型的属性，是由属性值的
                //此处是个坑$string_gsd_aliases是规格属性值  $values是货品id。。。。。
				$array_unsale_spec["gsd_id"] = $int_gsd_id;
				//$array_unsale_spec["pdt_id"] = $string_gsd_aliases;
                $array_unsale_spec["pdt_id"] = $values;
                $array_unsale_spec["gsd_aliases"] = $string_gsd_aliases;
                //--------END by Joe
				$array_unsale_spec["gs_is_sale_spec"] = '0';
				$array_unsale_spec["g_id"] = $int_g_id;
				//$array_unsale_spec["gsd_aliases"] = $values;
				$array_unsale_spec["gsd_picture"] = "";
				if(false === D("RelatedGoodsSpec")->add($array_unsale_spec)){
					D("RelatedGoodsSpec")->rollback();
					$this->errorMsg("新增商品扩展属性数据失败。");
				}
			}
		}
		
		//处理商品资料图片
		if(isset($_POST["goods_pictures"]) && !empty($_POST["goods_pictures"])){
			//删除此商品已经存在的所有的图片
			if(false === D("GoodsPictures")->where(array("g_id"=>$int_g_id))->delete()){
				D("GoodsPictures")->rollback();
				$this->errorMsg("更新商品图片失败。CODE:001;");
			}
			
			foreach($_POST["goods_pictures"] as $key => $pictures){
				$pictures = str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$pictures),'/'));
				if("" == $pictures){
					continue;
				}
				if(0 == $key){
					//商品主图更新
					if(false === D("GoodsInfo")->where(array("g_id"=>$int_g_id))->save(array("g_picture"=>'/'.$pictures))){
						D("GoodsInfo")->rollback();
						$this->errorMsg("更新商品主图失败。CODE:0099;");
					}
					continue;
				}
				$array_images = array();
				$array_images["g_id"] = $int_g_id;
				$array_images["gp_picture"] = '/'.$pictures;
				$array_images["gp_order"] = $key;
				$array_images["gp_status"] = 1;
				$array_images["gp_create_time"] = $string_datetime;
				$array_images["gp_update_time"] = $string_datetime;
				if(false === D("GoodsPictures")->add($array_images)){
					D("GoodsPictures")->rollback();
					$this->errorMsg("更新商品图片失败。CODE:002;");
				}
			}
		}
		
		//edit by Mithern 增加一步操作：执行删除SKU的操作
		if(isset($_POST["modify_delete_skus"]) && "" != $_POST["modify_delete_skus"]){
			//如果存在需要被删除的SKU-ID，则先将这些SKU-ID对应的SKU删除
			//删除时，需要先验证要删除的SKU是否存在，并记录日志，如果不存在的，则跳过
			//删除时还要将与此SKU-ID关联的属性ID删除
			$array_del_pdt_ids = explode(',',trim($_POST["modify_delete_skus"],','));
			//print_r($array_del_pdt_ids);exit;
			foreach($array_del_pdt_ids as $del_pdt_id){
				//验证要删除的SKU-ID是否存在于当前商品下面
				$array_check_del_cond = array("g_id"=>$int_g_id,"pdt_id"=>$del_pdt_id);
				$array_mixed_result = D("GoodsProductsTable")->where($array_check_del_cond)->find();
				if(false === $array_mixed_result){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("远程服务器错误：无法验证要删除的规格是否存在。");
				}
				
				//如果要删除的规格在数据库中确实存在，则进一步删除规格，并删除规格关联关系，并记录操作日志
				if(is_array($array_mixed_result) && !empty($array_mixed_result)){
					//第一步：删除规格
					if(false === D("GoodsProductsTable")->where($array_check_del_cond)->delete()){
						D("GoodsProductsTable")->rollback();
						$this->errorMsg("远程服务器错误：删除规格时遇到错误。");
					}
					//第二步：删除销售属性的关联关系
					$array_del_related_cond = $array_check_del_cond;
					$array_del_related_cond["gs_is_sale_spec"] = 1;
					if(false === D("RelatedGoodsSpec")->where($array_del_related_cond)->delete()){
						D("RelatedGoodsSpec")->rollback();
						$this->errorMsg("远程服务器错误：删除属性关联关系时遇到错误。");
					}
					
					//删除与此规格关联的会员等级固定价格
					$array_fixed_mlprice_cond = array("pdt_id"=>$del_pdt_id);
					if(false === D("ProductMemberLevelPrice")->where($array_fixed_mlprice_cond)->delete()){
						D("RelatedGoodsSpec")->rollback();
						$this->errorMsg("远程服务器错误：删除会员等级固定价格时遇到错误。");
					}
					
					//第三步：记录SKU删除操作日志
					$array_goods_modify_log = array();
					$array_goods_modify_log["g_id"] = $int_g_id;
					$array_goods_modify_log["pdt_id"] = $del_pdt_id;
					$array_goods_modify_log["pdt_sn"] = $array_mixed_result["pdt_sn"];
					$array_goods_modify_log["u_id"] = $_SESSION["Admin"];
					$array_goods_modify_log["gpml_desc"] = '删除货号为“' . $array_mixed_result["pdt_sn"] . '”的规格。';
					$array_goods_modify_log["gpml_create_time"] = date("Y-m-d H:i:s");
					if(false === D("GoodsProductsModifyLog")->add($array_goods_modify_log)){
						D("GoodsProductsModifyLog")->rollback();
						$this->errorMsg("记录删除规格日志失败。");
					}
				}
			}
		}
		
		//处理商品SKU数据，判断是否启用规格，如果启用规格
		if(isset($_POST["goods_products"]) && !empty($_POST["goods_products"])){
			$array_pdt_sn = $_POST["goods_products"]["pdt_sn"];
			$array_spec_vids = $_POST["goods_products"]["spec_vids"];
			$array_pdt_sale_price = $_POST["goods_products"]["pdt_sale_price"];
			$array_member_level_price = $_POST["goods_products"]["member_level_price"];
			$array_pdt_cost_price = $_POST["goods_products"]["pdt_cost_price"];
			$array_pdt_stock = $_POST["goods_products"]["pdt_stock"];
			$array_pdt_market_price = $_POST["goods_products"]["pdt_market_price"];
			$array_pdt_weight = $_POST["goods_products"]["pdt_weight"];
			$array_pdt_min_num = $_POST["goods_products"]["pdt_min_num"];
			$array_modifyd_pdtids = array();
			foreach($array_pdt_sn as $pdt_id => $pdt_sn){
				$array_modify_info = array();
				if("" == $pdt_sn){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("您至少有一个货品没有填写货号。");
				}
				$array_modify_info["g_id"] = $int_g_id;
				$array_modify_info["g_sn"] = $ary_goods_info["g_sn"];
				$array_modify_info["pdt_sn"] = $pdt_sn;
				$array_modify_info["pdt_sale_price"] = (isset($array_pdt_sale_price[$pdt_id]) && is_numeric($array_pdt_sale_price[$pdt_id]))?$array_pdt_sale_price[$pdt_id]:0.00;
				$array_modify_info["pdt_cost_price"] = (isset($array_pdt_cost_price[$pdt_id]) && is_numeric($array_pdt_cost_price[$pdt_id]))?$array_pdt_cost_price[$pdt_id]:0.00;
				$array_modify_info["pdt_market_price"] = (isset($array_pdt_market_price[$pdt_id]) && is_numeric($array_pdt_market_price[$pdt_id]))?$array_pdt_market_price[$pdt_id]:0.00;
				$array_modify_info["pdt_weight"] = $array_pdt_weight[$pdt_id];
				$array_modify_info["pdt_min_num"] = $array_pdt_min_num[$pdt_id];
				$array_modify_info["pdt_status"] = 1;
				$array_modify_info["pdt_update_time"] = $string_datetime;
				$int_pdt_id = $pdt_id;
				//根据当前的ID查询，验证当前的pdt_id 在当前的SKU中是否存在，如果存在，则modify，否则新增
				$array_cond = array("pdt_id"=>$pdt_id,"g_id"=>$int_g_id);
				$mixed_result = D("GoodsProductsTable")->where($array_cond)->find();
				if(false === $mixed_result){
					//如果无法验证当前的id是否存在于当前商品资料下
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("无法验证SKU是否已经存在。");
				}
			
				if(is_array($mixed_result) && !empty($mixed_result)){
					//如果当前SKU已经存在于当前编辑的商品下，则认为是编辑
					if(false === D("GoodsProductsTable")->where($array_cond)->save($array_modify_info)){
						D("GoodsProductsTable")->rollback();
						$this->errorMsg("修改货号为“{$pdt_sn}”的货品数据更新失败。");
					}
				}else{
					//否则，认为是新增一个SKU
					//验证货号的唯一性
					$ary_pdtsn_check = D("GoodsProductsTable")->where(array("pdt_sn"=>$pdt_sn))->find();
					if(is_array($ary_pdtsn_check) && !empty($ary_pdtsn_check)){
						D("GoodsProductsTable")->rollback();
						$this->errorMsg("SKU商品编码“{$pdt_sn}”已经被其他商品占用。");
					}
					//验证是否输入销售价格
					if(!isset($array_pdt_sale_price[$pdt_id]) || !is_numeric($array_pdt_sale_price[$pdt_id])){
						D("GoodsProductsTable")->rollback();
						$this->errorMsg("SKU“{$pdt_sn}”的销售价格数值不合法。");
					}
					$array_modify_info["pdt_create_time"] = $string_datetime;
					$array_modify_info["pdt_update_time"] = $string_datetime;
					//商品资料编辑时新增的SKU，库存量一律为0（所有库存字段均为0）
					$array_modify_info["pdt_total_stock"] = 0;
					$array_modify_info["pdt_stock"] = 0;
					$array_modify_info["pdt_freeze_stock"] = 0;
					//例如：在一次操作中删除一个货号，然后新增了一个SKU，货号与删除的保持一致
					//TODO:这样的处理方法待评估。
					$int_pdt_id = D("GoodsProductsTable")->add($array_modify_info);
					if(false === $int_pdt_id){
						D("GoodsProductsTable")->rollback();
						$this->errorMsg("新增货号为“{$pdt_sn}”的货品数据更新失败。");
					}
				}
				
				//处理货品的销售属性数据，即pid:vid;pid:vid;数据
				if("" != $array_spec_vids){
					//删除此SKU关联的所有的销售属性数据
					$array_cond = array("g_id"=>$int_g_id,"pdt_id"=>$int_pdt_id,"gs_is_sale_spec"=>'1');
					if(false === D("RelatedGoodsSpec")->where($array_cond)->delete()){
						D("RelatedGoodsSpec")->rollback();
						$this->errorMsg("更新货号为“{$pdt_sn}”的货品的销售属性关联数据失败。CODE:001;");
					}
					$array_pidvid = explode(";",trim($array_spec_vids[$pdt_id],";"));
					foreach($array_pidvid as $pidvid){
						$array_tmp_pidvid = explode(":",$pidvid);
						$int_pid = $array_tmp_pidvid[0];
						$int_vid = $array_tmp_pidvid[1];
						$array_related_info = array();
						$array_related_info["gs_id"] = $int_pid;
						$array_related_info["gsd_id"] = $int_vid;
						$array_related_info["pdt_id"] = $int_pdt_id;
						$array_related_info["gs_is_sale_spec"] = 1;
						$array_related_info["g_id"] = $int_g_id;
						$array_related_info["gsd_aliases"] = (isset($_POST["spec_value"][$int_vid]) && "" != $_POST["spec_value"][$int_vid])?$_POST["spec_value"][$int_vid]:'';;
						$array_related_info["gsd_picture"] = (isset($_POST["spec_image"][$int_vid]) && "" != $_POST["spec_image"][$int_vid])?$_POST["spec_image"][$int_vid]:'';
						if(888 == $int_pid && $_POST["color_pic"]){
							//TODO 此处保存商品规格颜色图片
							
						}
						if(false === D("RelatedGoodsSpec")->add($array_related_info)){
							D("RelatedGoodsSpec")->rollback();
							$this->errorMsg("更新货号为“{$pdt_sn}”的货品的销售属性关联数据失败。CODE:002;");
						}
					}
				}
				
				//处理货品的SKU会员等级固定价格数据
				if("" != $array_member_level_price[$pdt_id]){
					$array_ml_prices = explode(";",trim($array_member_level_price[$pdt_id],";"));
					foreach($array_ml_prices as $ml_price){
						$array_tmp_1 = explode(":",$ml_price);
						$array_info = array();
						$array_info["pdt_id"] = $int_pdt_id;
						$array_info["ml_id"] = $array_tmp_1[0];
						$array_info["pmlp_price"] = $array_tmp_1[1];
						$array_info["pmlp_status"] = 1;
						$array_info["pmlp_create_time"] = $string_datetime;
						$array_info["pmlp_update_time"] = $string_datetime;
						if(false === D("ProductMemberLevelPrice")->add($array_info,array(),true)){
							D("ProductMemberLevelPrice")->rollback();
							$this->errorMsg("更新货号为“{$pdt_sn}”的货品的会员等级价格信息遇到错误。");
						}
					}
				}
				//对本次编辑和新增的SKU做保存处理，方便后续操作中删除SKU使用
				$array_modifyd_pdtids[] = $int_pdt_id;
			}
			
			//删除本次没有被处理到的货品
			if(!empty($array_modifyd_pdtids)){
				if(false === D("GoodsProductsTable")->where(array("g_id"=>$int_g_id,"pdt_id"=>array("not in",$array_modifyd_pdtids)))->delete()){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("删除货品失败。");
				}
				//TODO：此处为货品删除成功，可以增加对货品删除错做日志记录。
			}
		}else{
			//如果此商品没有启用规格，需要做判断处理：如果是之前启用了规格，此处需要删除以前的规格
			//以及以前的规格关联关系  包括关联的销售属性关系和会员等级折扣价格关系
			//如果下游没有及时同步，会导致商品下单以后无法发货等
			$bool_is_edit = true;
			$array_check_result = D("GoodsProductsTable")->where(array("g_id"=>$int_g_id))->getField("pdt_id",true);
			if(count($array_check_result) > 1){
				//先删除关联的商品销售属性情况
				if(false === D("RelatedGoodsSpec")->where(array("pdt_id"=>array("IN",$array_check_result)))->delete()){
					D("RelatedGoodsSpec")->rollback();
					$this->errorMsg("删除关联的货品销售属性失败。");
				}
				//删除关联的会员等级价格数据
				if(false === D("ProductMemberLevelPrice")->where(array("pdt_id"=>array("IN",$array_check_result)))->delete()){
					D("RelatedGoodsSpec")->rollback();
					$this->errorMsg("删除关联的会员等级折扣失败。");
				}
				//删除多余的SKU数据
				if(false === D("GoodsProductsTable")->where(array("g_id"=>$int_g_id))->delete()){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("删除多余的商品规格数据失败");
				}
				$bool_is_edit = false;
			}
			
			
			//将商品规格数据写入数据库
			$array_modify_data = array();
			$array_modify_data["g_id"] = $int_g_id;
			$array_modify_data["g_sn"] = $array_goods_info["g_sn"];
			$array_modify_data["pdt_sn"] = $array_goods_info["g_sn"];
			$array_modify_data["pdt_sale_price"] = (isset($_POST["pdt_sale_price"]) && is_numeric($_POST["pdt_sale_price"]))?$_POST["pdt_sale_price"]:0.00;
			$array_modify_data["pdt_cost_price"] = (isset($_POST["pdt_cost_price"]) && is_numeric($_POST["pdt_cost_price"]))?$_POST["pdt_cost_price"]:0.00;
			$array_modify_data["pdt_market_price"] = (isset($_POST["pdt_market_price"]) && is_numeric($_POST["pdt_market_price"]))?$_POST["pdt_market_price"]:0.00;
			$array_modify_data["pdt_weight"] = (isset($_POST["pdt_weight"]) && is_numeric($_POST["pdt_weight"]))?$_POST["pdt_weight"]:0;
			$array_modify_data["pdt_min_num"] = (isset($_POST["pdt_min_num"]) && is_numeric($_POST["pdt_min_num"]))?$_POST["pdt_min_num"]:0;
			$array_modify_data["pdt_status"] = 1;
			$array_modify_data["pdt_update_time"] = $string_datetime;
			if(false === $bool_is_edit){
				//这种情况下是由于商品之前启用规格，本次修改时取消了规格导致
				//需要重新生成SKU  一般不建议这样操作
				$array_modify_data["pdt_create_time"] = $string_datetime;
				$int_pdt_id = D("GoodsProductsTable")->add($array_modify_data);
				if(false === $int_pdt_id){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("商品规格数据存入失败。");
				}
			}else{
				//只是对未启用规格的商品的SKU数据做修改而已
				//需要获取唯一的SKU ID。
				$int_pdt_id = D("GoodsProductsTable")->where(array("g_id"=>$int_g_id))->getField("pdt_id");
				if(false === $int_pdt_id){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("商品规格数据存入失败。");
				}
				if(false === D("GoodsProductsTable")->where(array("pdt_id"=>$int_pdt_id))->save($array_modify_data)){
					D("GoodsProductsTable")->rollback();
					$this->errorMsg("商品规格数据存入失败。");
				}
			}
			
			
			//将商品的会员等级固定价格写入数据库
			if(isset($_POST["product_member_level_price"]) && is_array($_POST["product_member_level_price"])){
				foreach($_POST["product_member_level_price"] as $ml_id => $price){
					if(floatval($price)<=0 ){
						$price = '';
					}
					$array_info["pdt_id"] = $int_pdt_id;
					$array_info["ml_id"] = $ml_id;
					$array_info["pmlp_price"] = $price;
					$array_info["pmlp_status"] = 1;
					$array_info["pmlp_create_time"] = $string_datetime;
					$array_info["pmlp_update_time"] = $string_datetime;
					if(false === D("ProductMemberLevelPrice")->add($array_info,array(),true)){
						D("ProductMemberLevelPrice")->rollback();
						$this->errorMsg("更新会员等级价格信息遇到错误。");
					}
				}
			}
		}
		
		//更新商品信息表中的g_price为货品表中最高的价格：
		//需要先获取SKU中最高的市场价格；然后更新到goods_info表中；
		//更新商品表中的库存为货品表中库存之和：
		//需要获取SKU中的库存数量之和，然后更新到goods_info表。
		$array_fetch_condition = array("g_id"=>$int_g_id,"pdt_satus"=>1);
		$string_fields = "g_id,max(`pdt_market_price`) as `g_market_price`,max(`pdt_sale_price`) as `g_price`,max(`pdt_weight`) as `g_weight`,sum(`pdt_total_stock`) as `g_stock`";
		$array_result = D("GoodsProductsTable")->where($array_fetch_condition)->getField($string_fields);
		if(false === $array_result){
			D("GoodsProductsTable")->rollback();
			$this->errorMsg("商品资料保存失败。CODE:GET-MAX-PRODUCT-MARKET-PRICE-FAILED;");
			exit;
		}
		$array_save_data = array();
		$array_save_data = $array_result[$int_g_id];
		$result = D("GoodsInfo")->where(array("g_id"=>$int_g_id))->save($array_save_data);
		if(false === $result){
			D("GoodsInfo")->rollback();
			$this->errorMsg("商品资料保存失败。CODE:UPDATE-MAX-MARKET-PRICE-FAILED;");
			exit;
		}
		
		//记录商品资料修改日志记录
		$array_goods_modify_log = array();
		$array_goods_modify_log["g_id"] = $int_g_id;
		$array_goods_modify_log["pdt_id"] = 0;
		$array_goods_modify_log["pdt_sn"] = $array_goods["g_sn"];
		$array_goods_modify_log["u_id"] = $_SESSION["Admin"];
		$array_goods_modify_log["gpml_desc"] = '编辑了商品资料';
		$array_goods_modify_log["gpml_create_time"] = date("Y-m-d H:i:s");
		if(false === D("GoodsProductsModifyLog")->add($array_goods_modify_log)){
			D("GoodsProductsModifyLog")->rollback();
			$this->errorMsg("记录删除规格日志失败。");
		}
		
		D("GoodsBase")->commit();
		if(NULL !== session('page')){
			$previousPage = '/p/' .session('page');
		}
		$string_jump_url = U("Ucenter/Goods/pageList"). $previousPage;
		$this->successMsg("商品资料保存成功。",$string_jump_url);
		exit;
	}
	
	/**
	 * 添加编辑商品时异步加载商品非销售属性表单控制页
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-29
	 */
	public function ajaxLoadUnsaleSpec(){
		//关闭布局模式
		C("LAYOUT_ON",false);
		$int_type_id = $_POST["type_id"];
		//获取与此类型相关联的属性ID
		$array_related_info = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>$int_type_id))->select();
		$array_spec_info = array();
		//如果此类型下没有关联属性，则输出一个空字符到页面DOM中
		if(is_array($array_related_info) && !empty($array_related_info)){
			//对关联的商品ID做处理，用于生成查询商品属性的条件
			$array_spec_id = array();
			foreach($array_related_info as $val){
				$array_spec_id[] = $val["gs_id"];
			}
			//获取与此类型关联的商品扩展属性详情
			$array_cond = array("gs_id"=>array("IN",$array_spec_id),"gs_is_sale_spec"=>0,"gs_status"=>1);
			$array_spec_info = D("GoodsSpec")->where($array_cond)->order(array("gs_order"=>"asc"))->select();
			//对属性进行遍历处理，如果输入类型是下拉选框的属性，还需要将属性值获取出来
			foreach($array_spec_info as $key => $val){
				if(2 == $val["gs_input_type"]){
					$array_spec_info[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id"=>$val["gs_id"]))->select();
				}
			}
		}
		$this->assign("array_spec_info",$array_spec_info);
		$this->display();
	}
	
	/**
	 * 添加编辑商品时异步加载商品销售属性表单控制页
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @date 2013-05-29
	 */
	public function ajaxLoadSaleSpec(){
		//关闭布局模式
		C("LAYOUT_ON",false);
		$int_type_id = $_POST["type_id"];
		//获取与此类型相关联的属性ID
		$array_related_info = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>$int_type_id))->select();
		//如果此类型下没有关联属性，则输出一个空字符到页面DOM中
		$array_spec_info = array();
		if(is_array($array_related_info) && !empty($array_related_info)){
			//对关联的商品ID做处理，用于生成查询商品属性的条件
			$array_spec_id = array();
			foreach($array_related_info as $val){
				$array_spec_id[] = $val["gs_id"];
			}
			//获取与此类型关联的商品扩展属性详情
			$array_cond = array("gs_id"=>array("IN",$array_spec_id),"gs_is_sale_spec"=>1,"gs_status"=>1);
			$array_spec_info = D("GoodsSpec")->where($array_cond)->order(array("gs_order"=>"asc"))->select();
			//对属性进行遍历处理，如果输入类型是下拉选框的属性，还需要将属性值获取出来
			foreach($array_spec_info as $key => $val){
				if(2 == $val["gs_input_type"]){
					$array_spec_info[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id"=>$val["gs_id"]))->order(array("gsd_order"=>'asc'))->select();
				}
			}
		}
		//变量传递到模板并渲染输出
		$this->assign("array_sale_spec",$array_spec_info);
		$this->display();
	}
	
	
	public function ajaxSkuLists(){
		//关闭布局模式
		C("LAYOUT_ON",false);
		if(!isset($_POST["specinfo"]) && "" != $_POST["specinfo"]){
			echo "";
			exit;
		}
		$string_spec_info = trim($_POST["specinfo"]);
		$g_id = trim($_POST["g_id"]);
		//对页面交互的sku属性进行处理
		$array_sku_info = explode(';',trim($string_spec_info,";"));
        
		$array_pid = array();
		$array_vid = array();
		$array_value_cond = array();
		foreach($array_sku_info as $pidvid){
			$array_tmp = explode(":",$pidvid);
			$int_tmp_pid = $array_tmp[0];
			$int_tmp_vid = $array_tmp[1];
			if(!in_array($int_tmp_pid,$array_pid)){
				$array_pid[] = $int_tmp_pid;
			}
			$array_vid[$int_tmp_pid][] = $int_tmp_vid;
			$array_value_cond[] = $int_tmp_vid;
		}
		//获取商品属性的详细信息，并且遍历处理为属性ID=>属性详细信息的格式
		$array_cond = array("gs_id"=>array("IN",$array_pid));
		$string_fields = 'gs_id,gs_name,gs_is_system_spec';
		$array_spec = D("GoodsSpec")->where($array_cond)->order(array("gs_order"=>'asc'))->getField($string_fields);
        
		//获取商品属性值的详细信息，并遍历处理为属性值ID=>属性值详细信息的格式
		$array_cond = array("gsd_id"=>array("IN",$array_value_cond));
		$string_fields = 'gsd_id,gs_id,gsd_value,gsd_rgb_value';
		$array_spec_detail = D("GoodsSpecDetail")->where($array_cond)->getField($string_fields);
        
		//取出系统的颜色属性，要求录入时可以上传图片
		$array_system_color_spec = $array_vid[888];
		/**
		 * 对属性信息做处理，组合生成不同的SKU
		 *
		 * ***************警告******************警告******************
		 * 下述代码为PHP实现数组排列组合生成SKU之用，请不要随意修改.......
		 * 
		 * 除非你能看得懂.....
		 *
		 * 好吧，即使你看得懂你也不要改！还是不要改的好......
		 * 
		 * NB人物
		 *
		 */
		$array_combine = array();
		$i = 0;
		ksort($array_vid);
		foreach($array_vid as $key=>$val){
			if(0 == $i){
				$array_combine = $val;
				$i ++;
				continue;
			}
			$array_tmp_spec = array();
			foreach($array_combine as $v0){
				foreach($val as $v1){
					$array_tmp = $v0;
					if(!is_array($array_tmp)){
						$array_tmp = array($array_tmp);
					}
					$array_tmp[] = $v1;
					$array_tmp_spec[] = $array_tmp;
				}
			}
			$array_combine = $array_tmp_spec;
		}
		if(!empty($g_id)){
			//获取商品SKU详细资料
			$array_products = D("GoodsProductsTable")->where(array("g_id"=>$g_id))->field("pdt_id,pdt_sn,pdt_total_stock,pdt_cost_price,pdt_sale_price,pdt_market_price,pdt_weight")->select();	
			foreach($array_products as $key=>$array_product){
				$spec_pidvid = "";
				//获取此SKU关联的所有的规格ID和规格值ID
				$array_info = D("RelatedGoodsSpec")->where(array("pdt_id"=>$array_product['pdt_id'],"gs_is_sale_spec"=>1))->getField("gs_id,gsd_id,gsd_aliases");
				foreach($array_info as $k_pid => $detail_info){
					$spec_pidvid .= $k_pid . ':' . $detail_info["gsd_id"] . ";";
				}
				$array_products[$key]["spec_pidvid"] = $spec_pidvid;
			}	
		}
		//对组合成的SKU进行处理，获取其所属的属性信息，属性值信息
		foreach($array_combine as $key => $val){
			//如果此类型商品只有一个销售属性的时候
			if(!is_array($val)){
				$val = array($val);
			}
			$array_sku = array();
			$array_sku["spec"] = array();
			$array_sku["pdt_sn"] = "SN";
			$array_sku["spec_pidvid"] = "";
            foreach ($array_spec as $ask=>$asv){
                foreach ($array_spec_detail as $gd_val){
                    foreach ($val as $v){
                        if($ask == $gd_val['gs_id'] && $gd_val['gsd_id'] == $v){
                            $array_sku["spec"][] = $array_spec_detail[$v];
                            $array_sku["pdt_sn"] .= $v;
                            $array_sku["spec_pidvid"] .= $array_spec_detail[$v]["gs_id"] . ':' . $v . ";";
                        }
                    }
                }
                
            }
			foreach($array_products as $va){
				if($va["spec_pidvid"] == $array_sku["spec_pidvid"]){
					$array_sku['pdt_total_stock'] = $va['pdt_total_stock'];
					$array_sku['pdt_sale_price'] = $va['pdt_sale_price'];
					$array_sku['pdt_cost_price'] = $va['pdt_cost_price'];
					$array_sku['pdt_market_price'] = $va['pdt_market_price'];
					$array_sku['pdt_weight'] = $va['pdt_weight'];
					$array_sku['pdt_sn'] = $va['pdt_sn'];
				}
			}	
			$array_combine[$key] = $array_sku;
		}
		//对颜色属性进行处理
		foreach($array_system_color_spec as $key=>$val){
			$array_system_color_spec[$key] = $array_spec_detail[$val];
		}
		//数据传递到模板，渲染输出
       
		$this->assign("array_combine",$array_combine);
		$this->assign("array_system_color_spec",$array_system_color_spec);
		$this->assign("array_spec",$array_spec);
		$this->assign("is_edit_goods",(isset($_GET['is_edit']) && 1 == $_GET['is_edit'])?1:0);
		$this->display("sku-list");
	}
	
	/**
	 * 商品标记状态翻转
	 * 用于商品是否新品，热品翻转；
	 */
	public function ajaxSetGoodsFlag(){
		//验证是否制定操作类型
		if(!isset($_POST["dotype"]) || "" == $_POST["dotype"]){
			echo json_encode(array('status'=>false,'message'=>'请指定操作类型','error_code'=>'invilaed do type'));
			exit;
		}
	
		//判断是否设置商品ID
		if(!isset($_POST["g_id"]) || !is_numeric($_POST["g_id"])){
			echo json_encode(array('status'=>false,'message'=>'请指定商品ID。','error_code'=>'need goods id'));
			exit;
		}
		//判断是否商品是否存在
		$int_g_id = $_POST["g_id"];
		$array_result = D("GoodsBase")->where(array('g_id'=>$int_g_id))->getField('g_id');
		if(false === $array_result){
			echo json_encode(array('status'=>false,'message'=>'无法获取商品ID','error_code'=>'500'));
			exit;
		}
		if(null === $array_result){
			echo json_encode(array('status'=>false,'message'=>'商品资料不存在。','error_code'=>'goods not exists'));
			exit;
		}
		
		//对商品做标记处理
		$array_modify_data = array();
		$field_name = '';
		switch(trim($_POST["dotype"])){
			case 'hot':
				$field_name = 'g_hot';
				break;
			case 'new':
				$field_name = 'g_new';
				break;
		}
		if("" === $field_name){
			echo json_encode(array('status'=>false,'message'=>'操作类型不合法','error_code'=>'invilaed do type'));
			exit;
		}
		$array_modify_data[$field_name] = $_POST['value'];
		$array_modify_data["g_update_time"] = date("Y-m-d H:i:s");
		
		//更新的条件
		$array_modify_cond = array('g_id'=>$int_g_id);
		if(false === D("GoodsBase")->where($array_modify_cond)->save($array_modify_data)){
			echo json_encode(array('status'=>false,'message'=>'操作失败。','error_code'=>'update error'));
			exit;
		}
		
		//标记完成
		echo json_encode(array('status'=>true,'message'=>'操作成功','error_code'=>'success'));
		exit;
	}
	
	/**
	 * 商品添加时验证规格商家编码是否唯一的操作
	 */
	public function checkPdtSnUnique(){
		if(!isset($_POST["pdt_sns"]) || "" == $_POST["pdt_sns"]){
			echo json_encode(array("status"=>false,"message"=>"参数错误","code"=>'data-empty'));
			exit;
		}
		
		$array_pdt_sns = explode("||||",trim($_POST["pdt_sns"],'|+|-|=|'));
		$array_cond = array("pdt_sn"=>array("IN",$array_pdt_sns));
		$array_result = D("GoodsProductsTable")->where($array_cond)->getField("pdt_id,pdt_sn",true);
		if(false === $array_result){
			echo json_encode(array("status"=>false,"message"=>"查询数据库验证出错","code"=>'select-database-error'));
			exit;
		}
		
		if(empty($array_result)){
			echo json_encode(array("status"=>true,"message"=>"success","code"=>'success'));
			exit;
		}
		
		echo json_encode(array("status"=>false,"data"=>$array_result,"message"=>"有商品编码重复","code"=>'pdt_sn-not-unique'));
		exit;
	}
    
    /**
     * 组合商品列表
     *
     */
    public function combinationGoodsList(){
        $this->getSubNav(3, 5, 50);
        $goodBase = D("GoodsBase");
        $combination = D("ReletedCombinationGoods");
        $data = array();
        $array_where = array();
        
        $array_where['fx_goods.g_is_combination_goods'] = 1;
        
        if(isset($_POST['val']) && !empty($_POST['val'])){
            
            if($_POST['field'] == 1){
                //组合商品名称搜索
                $array_where['gi.g_name'] = array('LIKE',"%".$_POST['val']."%");
            }else{
                //组合商品相关pdt_sn搜索
                $pdt_sn = $_POST['val'];
                $pdt_id = D("GoodsProductsTable")->where(array('pdt_sn'=>$pdt_sn))->getField('pdt_id');
                $com_g_id = $combination->where(array('releted_pdt_id'=>$pdt_id))->getField('g_id');
                $array_where['fx_goods.g_id'] = $com_g_id;
            }
        }
        //时间搜索
        if(!empty($_POST['g_on_sale_time']) && !empty($_POST['g_off_sale_time'])){
            $array_where['fx_goods.g_on_sale_time'] = array('EGT',$_POST['g_on_sale_time']);
            $array_where['fx_goods.g_off_sale_time'] = array('ELT',$_POST['g_off_sale_time']);
            
        }
        $count = $goodBase->where($array_where)->count();
        $obj_page = new Page($count, 20);
        $data['page'] = $obj_page->show();
        $combination_goods = $goodBase->where($array_where)->field("
                            fx_goods.`g_id` AS `g_id`,
                            fx_goods.`g_on_sale` AS `g_on_sale`,
                            fx_goods.`g_status` AS `g_status`,
                            fx_goods.`g_off_sale_time` AS `g_off_sale_time`,
                            fx_goods.`g_on_sale_time` AS `g_on_sale_time`,
                            fx_goods.`g_sn` AS `g_sn`,
                            fx_goods.`g_create_time` AS `g_create_time`,
                            gi.`g_name` AS `g_name`")
                            ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id)")
                            ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                            ->select();
        
        foreach($combination_goods as $com_key=>$com_value){
            //获取套餐总价、优惠金额与套餐总数
            $array_all_price = $combination->getCombinationGoodsPrice($com_value['g_id']);
            $combination_goods[$com_key]['all_price'] = $array_all_price['all_price'];
            $combination_goods[$com_key]['coupon_price'] = $array_all_price['coupon_price'];
            $combination_goods[$com_key]['all_nums'] = $array_all_price['all_nums'];
            $content = array();
            $int_status = 1;
            $ary_product =  D("ReletedCombinationGoods")->where(array('g_id'=>$com_value['g_id']))->select();
            foreach ($ary_product as $pdt_key=>$pdt_val){
                $array_tmp_where['pdt.pdt_id'] = $pdt_val['releted_pdt_id'];
                $array_tmp_where['pdt.pdt_sn'] = $pdt_val['releted_pdt_sn'];
                $ary_goods = $goodBase->where($array_tmp_where)->field("
                                               fx_goods.g_on_sale,
                                               gi.g_name,
                                               pdt.pdt_sn,pdt.pdt_stock")
                                               ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id)")
                                               ->join("fx_goods_products as pdt on(fx_goods.g_id=pdt.g_id)")
                                               ->find();
                if($ary_goods['g_on_sale'] == 2){
                    $int_status = 2;
                    $content[$pdt_key] = $ary_goods['g_name'].' '.$ary_goods['pdt_sn'].' '.'已下架，库存剩余:'.$ary_goods['pdt_stock'];
                }else{
                    $content[$pdt_key] = $ary_goods['g_name'].' '.$ary_goods['pdt_sn'].' '.'在架，库存剩余:'.$ary_goods['pdt_stock'];
                }
                $combination_goods[$com_key]['effectiveness'] = $content;
                                               
            }
            $combination_goods[$com_key]['status'] = $int_status;
            
        }
        $data['list'] = $combination_goods;
        $this->assign('filter',$_POST);
        $this->assign($data);
        $this->display('com_list');
    }
    
    /**
     * 新增组合商品页面
     *
     *
     */
    public function addCombinationGoodPage(){
        
        $this->getSubNav(3,5,42);
        $search['cates'] =D("ViewGoods")->getCates();
        $this->assign("search",$search);
        $this->display('add_com');
    }
    
    /**
     * 编辑添加组合商品，异步查询货品信息
     *
     */
    public function searchPdtInfo(){
        $pdt_sn = $this->_post('pdt_sn');
        $ary_goods_info = D("GoodsProducts")->Search(array('pdt_sn'=>$pdt_sn,'pdt_status'=>1),array('g_id','g_sn','pdt_sn','pdt_id','pdt_sale_price','pdt_stock','pdt_sale_price'));
        if($ary_goods_info){
            $goods_name = M("goods_info")->where(array('g_id'=>$ary_goods_info['g_id']))->find();
            $ary_goods_info['g_name'] = $goods_name['g_name'];
            $specName = D('GoodsSpec')->getProductsSpec($ary_goods_info['pdt_id']);
            $ary_goods_info['specName'] = empty($specName) ? '无规格' : $specName;
            
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商品不存在'));
        }
        $this->assign("data",$ary_goods_info);
        $this->display('ajaxLoadAddComPdtLits');
    }
    
    /**
     * 订单编辑添加商品
     *
     */
    public function searchOrdersPdtInfo(){
        $pdt_sn = $this->_post('pdt_sn');
        $o_id = $this->_post('o_id');
        $ary_goods_info = D("GoodsProducts")->Search(array('pdt_sn'=>$pdt_sn,'pdt_status'=>1),array('g_id','g_sn','pdt_sn','pdt_id','pdt_sale_price','pdt_stock'));
        if($ary_goods_info){
        	$count = M("orders_items")->where(array('pdt_id'=>$ary_goods_info['pdt_id'],'o_id'=>$o_id,'oi_type'=>0))->count();
        	if($count>0){
        		$this->ajaxReturn(array('status'=>'error','msg'=>'该商品已存在，您只要编辑商品就好了'));
        	}
            $goods_name = M("goods_info")->where(array('g_id'=>$ary_goods_info['g_id']))->find();
            $ary_goods_info['g_name'] = $goods_name['g_name'];
            $ary_goods_info['g_picture'] = '/'.ltrim($goods_name['g_picture'],'/');
            $specName = D('GoodsSpec')->getProductsSpec($ary_goods_info['pdt_id']);
            $ary_goods_info['specName'] = empty($specName) ? '无规格' : $specName;
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商品不存在'));
        }
        $this->assign("data",$ary_goods_info);
        $this->display('ajaxLoadAddOrdersPdtLits');
    }   
    
    /**
     * 添加组合商品
     *
     */
    public function getProductsInfo(){
        $products = D("GoodsProductsTable");
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['g_name'] = $this->_get('g_name', 'htmlspecialchars,trim', '');
        $chose['pdt_sn'] = $this->_get('pdt_sn', 'htmlspecialchars,trim', '');
        $chose['gcid'] = $this->_get('gs_gcid', 'htmlspecialchars,trim', '');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        //商品分类搜索
        if ($chose['gcid']) {
            $where['rgc.gc_id'] = array('in', D("ViewGoods")->getCatesIds($chose['gcid']));
        }
        //商品名称查询
        if ($chose['g_name']) {
            $where['gi.g_name'] = array('LIKE', '%' . $chose['g_name'] . '%');
        }
        //商品编码查询
        if ($chose['pdt_sn']) {
            $where['fx_goods_products.pdt_sn'] = array('LIKE', '%' . $chose['pdt_sn'] . '%');
        }
        $where['fx_goods_products.pdt_stock'] = array('GT',0);
        $where['fx_goods_products.pdt_is_combination_goods'] = 0;
        
        //非处方药
        $where['g.g_is_prescription_rugs']  = 0;
        //print_r($where);exit;
        //设置页面的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $search['cates'] = D("ViewGoods")->getCates();
        if ($chose['gcid']) {
            $count = $products->distinct(true)->where($where)
                          ->join('fx_related_goods_category as rgc on(rgc.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods as g on(g.g_id=gi.g_id)')
                          ->count();               
        }else{
            $count = $products->distinct(true)->where($where)
                          ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods as g on(g.g_id=gi.g_id)')
                          ->count();
        }   
        $Page = new Page($count, 5);
        $data['page'] = $Page->show();
        
        $field=array('gi.g_id','gt.gt_name','gi.g_name','fx_goods_products.pdt_sn','gi.g_picture','fx_goods_products.g_sn','fx_goods_products.pdt_id');
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        if ($chose['gcid']) {
            $array_products = $products->distinct(true)->field($field)->where($where)
                                            ->join('fx_related_goods_category as rgc on(rgc.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods as g on(g.g_id=gi.g_id)')
                                            ->join('fx_goods_type as gt on(g.gt_id=gt.gt_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }else{
            $array_products = $products->distinct(true)->field($field)->where($where)
                                            ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods as g on(g.g_id=gi.g_id)')
                                            ->join('fx_goods_type as gt on(g.gt_id=gt.gt_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }
        foreach ($array_products as $key=>$val){
            $goodscate = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where['g.g_id'] = $val['g_id'];
            $category = $goodscate->where($array_where)
                            ->field(array('gc.gc_name'))
                            ->join('fx_goods as g on(g.g_id=fx_related_goods_category.g_id)')
                            ->join('fx_goods_category as gc on(gc.gc_id=fx_related_goods_category.gc_id)')
                            ->select();
            foreach($category as $c_v){
                $str_tmp_cate .= $c_v['gc_name'].",";
            }
            $array_products[$key]['gc_name'] = rtrim(trim($str_tmp_cate,','));
            $str_tmp_cate = '';
            $array_products[$key]['g_picture'] = '/'.ltrim($val['g_picture'],'/');
            $array_products[$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($val['pdt_id']);
        }
        $data['list'] = $array_products;
        $this->assign('search', $search); //查询条件

        $this->assign('chose', $chose);  //当前已经选择的
        $this->assign($data);    //赋值数据集，和分页
        $this->display();
    }
    
    /**
     * 执行添加组合商品
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function addCombinationGoods(){
		//print_r($_POST);exit;
        $Goods = D("GoodsBase");
        $ary_com_goods = $this->_post();
        //注：商品表上架时间与下架时间在组合商品中默认为有效时间段
        $g_on_sale_time = $this->_post('g_on_sale_time');
        $g_off_sale_time = $this->_post('g_off_sale_time');
        
        //验证组合商品名称是否唯一
        $check_result = M('goods_info')->where(array('g_name'=>$ary_com_goods['g_name'],'g_is_combination_goods'=>1))->getField("g_name");
        if(isset($check_result) && !empty($check_result)){
            return $this->ajaxReturn(array('status'=>'error','msg'=>'组合商品标题已存在！'));
        }
        
        //验证时间有效性
        if($g_on_sale_time!='0000-00-00 00:00:00' || $g_off_sale_time!='0000-00-00 00:00:00'){
            if(strtotime($g_on_sale_time) > strtotime($g_off_sale_time)){
                return $this->ajaxReturn(array('status'=>'error','msg'=>'开始时间不能小于结束时间'));
            }
        }
        $random_string = '';
        $str_letters = '1234567890';
        for ($i = 0; $i < 5; $i++) {
            $random_string .= $str_letters [mt_rand(0, strlen($str_letters) - 1)];
        }
        $time = date("Y-m-d H:i:s");
        $g_sn = mktime().date('His').$random_string;
        //组合商品基本信息入库
		//edit by Mithern 事务开始
        $Goods->startTrans();
		//向商品基本资料表中新增记录
        $ary_add_goods = array();
        $ary_add_goods['g_name'] = $ary_com_goods['g_name'];
        $ary_add_goods['g_on_sale'] = 1;
        $ary_add_goods['g_status'] = 1;
        $ary_add_goods['g_gifts'] = 0;
        $ary_add_goods['g_off_sale_time'] = $g_off_sale_time;
        $ary_add_goods['g_on_sale_time'] = $g_on_sale_time;
        $ary_add_goods['g_sn'] =  $g_sn;
        $ary_add_goods['g_create_time'] = $time;
        $ary_add_goods['g_is_combination_goods'] = 1;
        $int_combination_good_id = $Goods->add($ary_add_goods);
        if(false === $int_combination_good_id){
            $Goods->rollback();
            return $this->ajaxReturn(array('status'=>'error','msg'=>'添加组合商品基本信息失败','Sql'=>$Goods->getLastSql()));
        }
        
		//edit by Mithern  此处去掉多余的事务开启声明
        //D("GoodsInfo")->startTrans();
		//商品详细信息表数据存入
        $ary_add_goods_info = array();
        $ary_add_goods_info['g_id'] = $int_combination_good_id;
        $ary_add_goods_info['g_name'] = $ary_com_goods['g_name'];
        $ary_add_goods_info['g_source'] = 'local';
        $ary_add_goods_info['g_create_time'] = $time;
        if(!D("GoodsInfo")->add($ary_add_goods_info)){
            D("GoodsInfo")->rollback();
            return $this->ajaxReturn(array('status'=>'error','msg'=>'添加商品组合信息失败','Sql'=>D("GoodsInfo")->getLastSql()));
        }
		
		//SKU规格表资料录入
		//添加货品资料
		//edit by Mithern  此处去掉多余的事务开启声明
        //D("GoodsProductsTable")->startTrans();
        $array_product_add = array();
        $array_product_add['g_id'] = $int_combination_good_id;
        $array_product_add['g_sn'] = $g_sn;
        $array_product_add['pdt_sn'] = $g_sn;
        $array_product_add['pdt_max_num'] = isset($ary_com_goods['pdt_max_num']) ? $ary_com_goods['pdt_max_num']:0;
        $array_product_add['pdt_create_time'] = $time;
        $array_product_add['pdt_is_combination_goods'] = 1;
        $int_pdt_last_id = D("GoodsProductsTable")->add($array_product_add);
        if(false === $int_pdt_last_id){
            D("GoodsProductsTable")->rollback();
            return $this->ajaxReturn(array('status'=>'error','msg'=>'组合商品资料录入失败','Sql'=>D("GoodsProductsTable")->getLastSql()));
        }
		
		//定义一个变量，用于存放组合以后的商品的价格
		$decimal_pdt_price = 0;
        
        //组合商品相关入库
        $combinationGoods = D("ReletedCombinationGoods");
		//edit by Mithern  此处去掉多余的事务开启声明
        //$combinationGoods->startTrans();
        $array_insert = array();
        $combination_goods = $_POST['combination_goods'];
        foreach ($combination_goods as $com_key=>$com_val){
            $array_insert['g_id'] = $int_combination_good_id;
            $array_insert['pdt_id'] = $int_pdt_last_id;
            $array_insert['com_price'] = $com_val['com_price'];
            $array_insert['com_nums'] = $com_val['com_nums'];
            $array_insert['releted_pdt_id'] = $com_val['releted_pdt_id'];
            $array_insert['releted_pdt_sn'] = $com_val['releted_pdt_sn'];
            $array_insert['com_create_time'] = $time;
            $array_insert['com_status'] = 1;
            $int_last_id = $combinationGoods->add($array_insert);
			$decimal_pdt_price += $array_insert['com_nums'] * $array_insert['com_price'];
            if(false === $int_last_id){
                $combinationGoods->rollback();
                return $this->ajaxReturn(array('status'=>'error','msg'=>'添加组合商品失败','Sql'=>$combinationGoods->getLastSql()));
            }
        }
		
		//将价格更新到货品表和商品表中
		$mixed_result = D("GoodsProductsTable")->where(array("pdt_id"=>$int_pdt_last_id))->save(array("pdt_sale_price"=>$decimal_pdt_price));
        if(false === $mixed_result){
			 $combinationGoods->rollback();
             return $this->ajaxReturn(array('status'=>'error','msg'=>'添加组合商品失败','Sql'=>D("GoodsProductsTable")->getLastSql()));
		}
		
		//将销售价格更新到商品表中
		$mixed_result = D("GoodsInfo")->where(array("g_id"=>$int_combination_good_id))->save(array("g_price"=>$decimal_pdt_price));
        if(false === $mixed_result){
			 $combinationGoods->rollback();
             return $this->ajaxReturn(array('status'=>'error','msg'=>'添加组合商品失败','Sql'=>D("GoodsInfo")->getLastSql()));
		}
		
        //事物提交
        $Goods->commit();
        //edit by Mithern  事务提交一次就够了，孩纸。。。。
		/**
		D("GoodsInfo")->commit();
        D("GoodsProductsTable")->commit();
        $combinationGoods->commit();
		**/
        return $this->ajaxReturn(array('status'=>'success','组合商品添加成功','URL'=>'/Admin/Goods/combinationGoodsList'));
        
    }
    
    /**
     * 启用/关闭组合商品
     *
     */
    public function enableCombinationGoods(){
        $g_id = $this->_post('g_id');
        $g_on_sale = $this->_post('g_on_sale');
        $Goods = D("GoodsBase");
        if($g_on_sale == 1){
            $msg = '开启成功！';
        }else{
            $msg = '关闭成功！';
        }
        if($Goods->where(array('g_id'=>$g_id))->save(array('g_on_sale'=>$g_on_sale))){
            return $this->ajaxReturn(array('status'=>'success','Msg'=>$msg));
        }else{
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'失败'));
        }
    }
    
    /**
     * 显示编辑组合商品页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function editCombinationGoodsPage(){
        $this->getSubNav(3, 5, 50);
        $g_id = $this->_get('g_id');
        
        $goodBase = D("GoodsBase");
        $combination = D("ReletedCombinationGoods");
        $array_where['fx_goods.g_id'] = $g_id;
        $datas = $goodBase->where($array_where)->field("
                            fx_goods.`g_id` AS `g_id`,
                            fx_goods.`g_on_sale` AS `g_on_sale`,
                            fx_goods.`g_status` AS `g_status`,
                            fx_goods.`g_off_sale_time` AS `g_off_sale_time`,
                            fx_goods.`g_on_sale_time` AS `g_on_sale_time`,
                            fx_goods.`g_sn` AS `g_sn`,
                            fx_goods.`g_create_time` AS `g_create_time`,
                            gi.`g_name` AS `g_name`,
                            pdt.`pdt_max_num` As `pdt_max_num`")
                            ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id)")
                            ->join("fx_goods_products as pdt on(fx_goods.g_id=pdt.g_id)")
                            ->find();
        if(empty($datas)){
            $this->errorMsg("不存在的组合商品");exit;
        }        
        $datas['price'] = $combination->getCombinationGoodsPrice($datas['g_id']);
        
        $ary_reletedproduct = $combination->where(array('g_id'=>$datas['g_id']))->select();
        
        foreach ($ary_reletedproduct as $rel_k=>$rel_v){
            $product = D("GoodsProductsTable")->field("
                                                fx_goods_products.`pdt_stock` as `pdt_stock`,
                                                fx_goods_products.`pdt_sale_price` as `pdt_sale_price`,
                                                fx_goods_products.`pdt_sn` as `pdt_sn`,
                                                fx_goods_products.`pdt_id` as `pdt_id`,
                                                gi.`g_name` as g_name")
                                                ->join("fx_goods_info as gi on(fx_goods_products.g_id=gi.g_id)")
                                                ->where(array("pdt_sn"=>$rel_v['releted_pdt_sn']))
                                                ->find();
            $product['specName'] = D('GoodsSpec')->getProductsSpec($product['pdt_id']);
            
            $product['coupon_price'] = sprintf("%0.2f",$rel_v['com_nums']*$product['pdt_sale_price']-$rel_v['com_nums']*$rel_v['com_price']);
            $ary_reletedproduct[$rel_k] = array_merge($ary_reletedproduct[$rel_k],$product);
        }
        $datas['product'] = $ary_reletedproduct;
        
        $search['cates'] =D("ViewGoods")->getCates();
        $this->assign("search",$search);
        $this->assign('data',$datas);
        $this->display('edit_com');
    }
    
    /**
     * 执行编辑组合商品
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function editCombinationGoods(){
        $combinationGoods = D("ReletedCombinationGoods");
        $ary_edit_goods = $this->_post();
        $g_id = $ary_edit_goods['g_id'];
        
        if(!isset($g_id) && empty($g_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'参数有误！'));
        }
        
        //验证时间有效性
        if($ary_edit_goods['g_on_sale_time']!='0000-00-00 00:00:00' || $ary_edit_goods['g_off_sale_time']!='0000-00-00 00:00:00'){
            if(strtotime($ary_edit_goods['g_on_sale_time']) > strtotime($ary_edit_goods['g_off_sale_time'])){
                return $this->ajaxReturn(array('status'=>'error','Msg'=>'开始时间不能小于结束时间'));
            }
        }

        //验证组合商品名称是否唯一
        $check_result = M('goods_info')->where(array('g_name'=>$ary_edit_goods['g_name'],'g_id'=>array('neq',$g_id)))->getField("g_name");
        if(isset($check_result) && !empty($check_result)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'组合商品标题已存在！'));
        }
        
        //开启事物
        $combinationGoods->startTrans();
        
        //先跟新组合商品基本表
        $ary_gooods_edit['g_on_sale_time'] = $ary_edit_goods['g_on_sale_time'];
        $ary_gooods_edit['g_off_sale_time'] = $ary_edit_goods['g_off_sale_time']; 
        $ary_gooods_edit['g_gifts'] = 0;
        if(false === D("GoodsBase")->where(array('g_id'=>$g_id))->save($ary_gooods_edit)){
            D("GoodsBase")->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'更新组合商品基本信息失败，请检查有效时间段是否填写有误'));
        }
        
        ///更新组合商品货品信息
        
        //第一步，先删除取消的货品信息
        $del_g_id = rtrim(trim($ary_edit_goods['del_g_id'],','));
        if(isset($del_g_id) && !empty($del_g_id)){
            if(false === $combinationGoods->where(array('com_id'=>array('in',$del_g_id)))->delete()){
                $combinationGoods->rollback();
                return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除货品信息失败'));
            }
        }
        
        //更新（添加货品信息）
        $combination_goods = $ary_edit_goods['combination_goods'];
        //货品id
        $int_product_id = D('GoodsProductsTable')->where(array('g_id'=>$g_id))->getField('pdt_id');
        
        foreach ($combination_goods as $com_key=>$com_val){
            if(isset($com_val['com_id']) && !empty($com_val['com_id'])){
                //更新
                $com_id = $com_val['com_id'];
                $combinationGoods->where(array('com_id'=>$com_id))->save(array('com_price'=>$com_val['com_price'],'com_nums'=>$com_val['com_nums']));
            }else{
                //添加新组合商品货品信息
                $array_insert['g_id'] = $g_id;
                $array_insert['com_price'] = $com_val['com_price'];
                $array_insert['com_nums'] = $com_val['com_nums'];
                $array_insert['pdt_id'] = $int_product_id;
                $array_insert['releted_pdt_id'] = $com_val['releted_pdt_id'];
                $array_insert['releted_pdt_sn'] = $com_val['releted_pdt_sn'];
                $array_insert['com_create_time'] = date('Y-m-d H:i:s');
                $array_insert['com_status'] = 1;
                $int_last_id = $combinationGoods->add($array_insert);
                if(false === $int_last_id){
                    $combinationGoods->rollback();
                    return $this->ajaxReturn(array('status'=>'error','Msg'=>'添加组合商品失败','Sql'=>$combinationGoods->getLastSql()));
                }
            }
        }
        
        //获取组合商品套餐总价
        $array_decimal_pdt_price = $combinationGoods->getCombinationGoodsPrice($g_id);
        
        //跟新组合商品详情表（主要是改个标题）
        if(false === D("GoodsInfo")->where(array('g_id'=>$g_id))->save(array('g_name'=>$ary_edit_goods['g_name'],'g_price'=>$array_decimal_pdt_price['all_price']))){
            $combinationGoods->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'更新组合商品标题失败！'));
        }
        
        //接下来是货品表(修改最大下单数)
        if(false === D('GoodsProductsTable')->where(array('g_id'=>$g_id))->save(array('pdt_max_num'=>$ary_edit_goods['pdt_max_num'],'pdt_sale_price'=>$array_decimal_pdt_price['all_price']))){
            $combinationGoods->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'更新最大下单数失败！'));
        }
        
        //事物提交
        $combinationGoods->commit();

        return $this->ajaxReturn(array('status'=>'success','Msg'=>'组合商品修改成功','URL'=>'/Admin/Goods/combinationGoodsList'));
        
    }
    
    /**
     * 异步删除组合商品（物理删除）
     *
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function ajaxDelCombiantionGoods(){
        //组合商品id（1,2,3,4,5,）
        $str_g_id = $this->_post('g_id');
        
        if(empty($str_g_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'请选择要删除的组合商品！'));
        }
        $array_where['g_id'] = array('in',$str_g_id);
        //开启事物
        D("GoodsBase")->startTrans();
        //删除组合商品详情表
        if(false === D("GoodsInfo")->where($array_where)->delete()){
            D("GoodsBase")->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除商品详情信息失败'));
        }
        //删除组合商品基本表
        if(false === D("GoodsBase")->where($array_where)->delete()){
            D("GoodsBase")->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除商品基本信息失败'));
        }
        //删除货品表
        if(false === D("GoodsProductsTable")->where($array_where)->delete()){
            D("GoodsBase")->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除组合商品资料失败'));
        }
        //删除组合商品关联表
        if(false === D("ReletedCombinationGoods")->where($array_where)->delete()){
            D("GoodsBase")->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除组合商品相关货品失败'));
        }
        //事物提交
        D("GoodsBase")->commit();
        return $this->ajaxReturn(array('status'=>'success','Msg'=>'删除成功！'));
    }
	
	/**
	 * 商品资料行业属性字段配置
	 * 不同行业商品资料存在不同的属性，比如医药行业存在处方药非处方药
	 * 
	 * @author Mithern
	 * 
	 * @date 2013-07-16
	 * @version 1.0
	 */
	public function configIndustrySpec(){
		$this->getSubNav(3, 0, 80);
		//定义要配置的行业字段
		$array_industry_fields = array(
			//type相当于表单input的type属性，其中type是表单类型，label是项目名称，value是默认值，industry 所属行业
			//医药行业是否处方药，是否处方药，
			'g_is_prescription_rugs' => array(
				'type'=>'radio',
				'label'=>'是否处方药',
				'value'=>0,
				'required'=>'1',
				'industry'=>'医药行业',
				'options'=>array(
					array('label'=>'非处方药','value'=>0),
					array('label'=>'处方药','value'=>1)
				),
			),
			//其他类型行业字段请在此处添加	
			/*
			'g_xxx_001' => array('type'=>'input','label'=>'用户输入字段','value'=>'默认值','required'=>'1','industry'=>'其他行业'),
			'g_xxx_002' => array('type'=>'select','label'=>'用户选择字段','value'=>'-1','required'=>'0','industry'=>'其他行业','options'=>array(0=>'选项1','1'=>'选项2')),
			'g_xxx_003' => array('type'=>'textarea','label'=>'用户输入文本域','value'=>'请输入描述','required'=>'1','industry'=>'其他行业')
			*/
		);
		
		//保存用户配置
		if(isset($_POST["dosubmit"]) && 1 == $_POST["dosubmit"]){
			//首先将配置表中的关羽此项的配置全部删除
			//事务开始
			D("SysConfig")->startTrans();
			$array_result = D("SysConfig")->where(array("sc_module"=>"GOODS_INDUSTRY_SPEC"))->delete();
			if(false === $array_result){
				D("SysConfig")->rollback();
				$this->error("更新之前的配置失败。");
			}
			
			//将现有配置计入数据库中
			if(empty($_POST["GOODS_INDUSTRY_SPEC"])){
				//事务提交:没有提交配置数据表明删除所有的配置数据
				D("SysConfig")->commit();
				$this->success("配置保存成功。",U("Admin/Goods/configIndustrySpec"));
				exit;
			}
			foreach($_POST["GOODS_INDUSTRY_SPEC"] as $key => $val){
				$array_insert_data = array();
				$array_insert_data["sc_module"] = "GOODS_INDUSTRY_SPEC";
				$array_insert_data["sc_key"] = $key;
				$array_insert_data["sc_value"] = json_encode($array_industry_fields[$key]);
				$array_insert_data["sc_create_time"] = date("Y-m-d H:i:s");
				$array_insert_data["sc_update_time"] = date("Y-m-d H:i:s");
				if(false === D("SysConfig")->add($array_insert_data)){
					D("SysConfig")->rollback();
					$this->error("更新之前的配置失败。");
				}
			}
			D("SysConfig")->commit();
			$this->success("配置保存成功。",U("Admin/Goods/configIndustrySpec"));
			exit;
		}
		
		//显示用户表单
		
		//获取sys_config表中的记录
		$array_industry_spec = D("SysConfig")->where(array("sc_module"=>"GOODS_INDUSTRY_SPEC"))->select();
		//字段赋值
		if(!empty($array_industry_spec)){
			foreach($array_industry_spec as $config){
				if(isset($array_industry_fields[$config["sc_key"]])){
					$array_industry_fields[$config["sc_key"]]["checked"] = '1';
				}
			}
		}
		$this->assign("industry_fields",$array_industry_fields);
		$this->display();
	}
	
	/**
	 * 商品添加、编辑的关联商品搜索
	 *
	 */
	public function adminSearchGoods(){
		$array_search_cond = array();
		if(isset($_GET["search_cats"]) && $_GET["search_cats"] > 0){
                        $where = array();
                        $where['gc_id'] = $_GET["search_cats"];
                        $where['gc_parent_id'] = $_GET["search_cats"];
                        $where['_logic'] = 'or';
                        $ary_gcid = D("GoodsCategory")->where($where)->getField('gc_id',true);
//                        echo "<pre>";print_r($ary_gcid);exit;
                        $str_gcid = implode(",",$ary_gcid);
			//如果商品分类搜索，则先从商品关联表搜索出商品ID
			$array_goods_ids = D("RelatedGoodsCategory")->where(array("gc_id"=>array('IN',$str_gcid)))->getField("g_id",true);
//                        echo "<pre>";print_r($array_goods_ids);exit;
			$array_search_cond["g_id"] = array("IN",$array_goods_ids);
			if(empty($array_goods_ids)){
				$array_search_cond["g_id"] = array("IN",array(0));
			}
		}
		
		//如果根据商品品牌搜索
		if(isset($_GET["search_brand"]) && $_GET["search_brand"] > 0){
			$array_search_cond["gb_id"] = $_GET["search_brand"];
		}
		
		//如果根据商品名称进行搜索
		if(isset($_GET["keywords"]) && count($_GET["keywords"]) > 0){
//			$array_search_cond["g_sn"] = array('like','%' . $_GET["keywords"] . '%');
//			$array_search_cond["g_name"] = array('like','%' . $_GET["keywords"] . '%');
			$array_search_cond['_string'] = " g_sn like '%".$_GET["keywords"]."%' or g_name like '%".$_GET["keywords"]."%' ";
		}
        //限制赠品不能是搜索
        $array_search_cond['g_gifts'] = 0;
        //限制下架的商品不能搜索
        $array_search_cond['g_on_sale'] = array("NEQ",2);
 		//搜索结果
		$array_goods = D("UcenterQuickOrderGoodsView")->where($array_search_cond)->getField("g_id,g_name,g_sn,g_picture,g_price");
        foreach($array_goods as &$good){
			$good['g_picture'] = '/'.ltrim($good['g_picture'],'/');
		}
		//返回结果给页面
		echo json_encode($array_goods);
		exit;
	}
    
    /**
     * 规格组合商品列表
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-8
     */
    public function combinationPropertyGoodsList(){
        $this->getSubNav(3, 5, 43);
        $count = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->count();
        $obj_page = new Page($count, 20);
        $data['page'] = $obj_page->show();
        $data['list'] = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
        $this->assign($data);
        $this->display('cpg_list');
    }
    
    /**
     * 添加规格组合商品
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-08
     */
    public function addCombinationPropertyGoodsPage(){
        $this->getSubNav(3 ,5 ,44);
        
        $this->display('add_cpg');
    }
    
    /**
     * 执行添加规格组合商品
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-12
     */
    public function addCombinationPropertyGoods(){
        $array_data = $this->_post();
        //print_r($array_data);exit;
        $rel_spec_combination = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM');
        if(!isset($array_data['list']) && empty($array_data['list'])){
            $this->ajaxReturn(array('status'=>'error','msg'=>'请选择属性'));
        }
        //验证规格组合商品是否唯一
        $scg_name = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_name'=>$array_data['com_name'],'scg_status'=>$array_data['scg_status']))->getField('scg_name');
        if(isset($scg_name) && !empty($scg_name)){
            $this->ajaxReturn(array('status'=>'error','msg'=>'规格组合名已存在'));
        }
        //验证规格属性是否有重复
        $spec_v = $array_data['spec_v'];
        $spec_v_count_1 = count($spec_v);
        $spec_v = array_unique($spec_v);
        $spec_v_count_2 = count($spec_v);
        if($spec_v_count_1 > $spec_v_count_2){
            $this->ajaxReturn(array('status'=>'error','msg'=>'规格属性有重复'));
        }
        //验证商品编号是否有重复
        $spec_g = $array_data['spec_g'];
        $spec_g_count_1 = count($spec_g);
        $spec_g = array_unique($spec_g);
        $spec_g_count_2 = count($spec_g);
        if($spec_g_count_1 > $spec_g_count_2){
            $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号有重复'));
        }
        //开启事物
        $rel_spec_combination->startTrans();
        //添加规格组合商品基本信息
        $add_com_where = array('scg_name'=>$array_data['com_name'],'scg_create_time'=>date('Y-m-d H:i:s'));
        $sc_id = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->add($add_com_where);
        foreach ($array_data['list'] as $key=>$val){
            //验证g_sn是否为空
            if(empty($val['g_sn'])){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号不能为空'));
            }
            $count = D("GoodsProductsTable")->where(array('g_sn'=>$val['g_sn'],'pdt_is_combination_goods'=>'0'))->count();
            //验证g_sn该商品是否存在
            if($count == 0){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'不存在'));
            }
            //检测g_sn是否为单一规格商品
            if($count > 1){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'为多规格商品'));
            }
            //验证g_sn该商品是否已经被添加入规格组合
            $int_gsn_count = $rel_spec_combination->where(array('rsc_rel_good_sn'=>$val['g_sn']))->count();
            if($int_gsn_count > 0){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'已被添加过，请勿重复添加'));
            }
            //获取商品id
            $g_id = M('goods')->where(array('g_sn'=>$val['g_sn']))->getField('g_id');
            $ary_tmp_spec = explode(",",$val['spec_val']);
            
            foreach ($ary_tmp_spec as $sv){
                $sc_val = explode(':',$sv);
                if(count($sc_val) == 1){
                    //如果切割数组元素只有一个，表示有某项属性未选，返回错误
                    $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'规格属性未选'));
                }
                //防止循环操作，添加前先删除已有数据
                $array_where = array('sc_id'=>$sc_id,'rsc_spec_name'=>$sc_val[0],'rsc_spec_detail'=>$sc_val[1],'rsc_rel_good_id'=>$g_id,'rsc_rel_good_sn'=>$val['g_sn']);
                $rel_spec_combination->where($array_where)->delete();
                if (false === $rel_spec_combination->add($array_where)){
                    //写入日志
                    Log::write('添加规格组合商品失败 Sql:'.$rel_spec_combination->getLastSql());
                    $rel_spec_combination->rollback();
                    $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'添加失败'));
                }
            }
            
        }
        $rel_spec_combination->commit();
        $this->ajaxReturn(array('status'=>'success','msg'=>'操作成功'));
    }
    
    /**
     * 显示编辑页面
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-13
     */
    public function editCombinationPropertyGoodsPage(){
        $this->getSubNav(3 ,5 ,43);
        $spec_combination = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM');
        $scg_id = $this->_get('scg_id');
        $data['com_propetry'] = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_id'=>$scg_id))->find();
        //获取相关商品id
        $sp_com_g_id = $spec_combination->field('rsc_rel_good_id as gid,rsc_rel_good_sn as gsn')->where(array('sc_id'=>$scg_id))->group('rsc_rel_good_id')->select();
        
        $data_val = array();
        foreach ($sp_com_g_id as $key=>$val){
            $array_spec_val = $spec_combination->where(array('rsc_rel_good_id'=>$val['gid']))->select();
          // echo "<pre>";print_r($array_spec_val);exit;
            foreach ($array_spec_val as $sv_val){
                $data_val[$key][$sv_val['rsc_spec_name']] = $sv_val['rsc_spec_detail'];
                $data_val[$key]['g_sn'] = $sv_val['rsc_rel_good_sn'];
                if(!isset($skus[$sv_val['rsc_spec_name']])){
                    $skus[$sv_val['rsc_spec_name']] = array($sv_val['rsc_spec_detail']);
                }else{
                    $skus[$sv_val['rsc_spec_name']] = array_merge($skus[$sv_val['rsc_spec_name']],array($sv_val['rsc_spec_detail']));
                }
                $skus[$sv_val['rsc_spec_name']] = array_unique($skus[$sv_val['rsc_spec_name']]);
                sort($skus[$sv_val['rsc_spec_name']]);
                $data_val[$key]['skus'] = &$skus;
            }
            
        }
        $ary_sk = array();
        foreach ($skus as $key=>$val){
            foreach ($val as $v){
                $ary_sk[$key] .= $v ."\n";
            }
        }
        $data['sk'] = $ary_sk;
        $data['specName'] = $data_val;
        $data['skus'] = $skus;

      //  echo "<pre>";print_r($data);exit;
        $this->assign($data);
        $this->display('edit_com_pro');
    }
    
    /**
     * 执行编辑操作
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-14
     */
    public function editCombinationPropertyGoods(){
        $array_edit_com = $this->_post();
        $scg_id = $array_edit_com['scg_id'];
        $rel_spec_combination = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM');
        if(!isset($array_edit_com['list']) && empty($array_edit_com['list'])){
            $this->ajaxReturn(array('status'=>'error','msg'=>'请选择属性'));
        }
        //验证规格组合商品是否唯一
        $scg_name = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_name'=>$array_edit_com['com_name'],'scg_id'=>array('neq'=>$scg_id)))->getField('scg_name');
        if(isset($scg_name) && !empty($scg_name)){
            $this->ajaxReturn(array('status'=>'error','msg'=>'规格组合名已存在'));
        }
        //验证规格属性是否有重复
        $spec_v = $array_edit_com['spec_v'];
        $spec_v_count_1 = count($spec_v);
        $spec_v = array_unique($spec_v);
        $spec_v_count_2 = count($spec_v);
        if($spec_v_count_1 > $spec_v_count_2){
            $this->ajaxReturn(array('status'=>'error','msg'=>'规格属性有重复'));
        }
        //验证商品编号是否有重复
        $spec_g = $array_edit_com['spec_g'];
        $spec_g_count_1 = count($spec_g);
        $spec_g = array_unique($spec_g);
        $spec_g_count_2 = count($spec_g);
        if($spec_g_count_1 > $spec_g_count_2){
            $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号有重复'));
        }
        //开启事物
        $rel_spec_combination->startTrans();
        //更新基本信息
        $edit_com_where = array('scg_name'=>$array_edit_com['com_name'],'scg_status'=>$array_edit_com['scg_status'],'scg_update_time'=>date('Y-m-d H:i:s'));
        if(false === M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_id'=>$scg_id))->save($edit_com_where)){
            //写入错误日志
            Log::write('更新基本信息失败 Sql:'.M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());
            $rel_spec_combination->rollback();
            $this->ajaxReturn(array('status'=>'error','msg'=>'更新基本信息失败'));
        }
        //更新前，将关联所有数据先删除
        if(false === $rel_spec_combination->where(array('sc_id'=>$scg_id))->delete()){
            $rel_spec_combination->rollback();
            $this->ajaxReturn(array('status'=>'error','msg'=>'删除规格属性失败'));
        }
        //执行添加操作
        foreach ($array_edit_com['list'] as $key=>$val){
            //验证g_sn是否为空
            if(empty($val['g_sn'])){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号不能为空'));
            }
            $count = D("GoodsProductsTable")->where(array('g_sn'=>$val['g_sn'],'pdt_is_combination_goods'=>'0'))->count();
            //验证g_sn该商品是否存在
            if($count == 0){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'不存在'));
            }
            //检测g_sn是否为单一规格商品
            if($count > 1){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'为多规格商品'));
            }
            //验证g_sn该商品是否已经被添加入规格组合
            $int_gsn_count = $rel_spec_combination->where(array('rsc_rel_good_sn'=>$val['g_sn']))->count();
            if($int_gsn_count > 0){
                $rel_spec_combination->rollback();
                $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'已被添加过，请勿重复添加'));
            }
            //获取商品id
            $g_id = M('goods')->where(array('g_sn'=>$val['g_sn']))->getField('g_id');
            $ary_tmp_spec = explode(",",$val['spec_val']);
            
            foreach ($ary_tmp_spec as $sv){
                $sc_val = explode(':',$sv);
                if(count($sc_val) == 1){
                    //如果切割数组元素只有一个，表示有某项属性未选，返回错误
                    $rel_spec_combination->rollback();
                    $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'规格属性未选'));
                }
                //防止循环操作，添加前先删除已有数据
                $array_where = array('sc_id'=>$scg_id,'rsc_spec_name'=>$sc_val[0],'rsc_spec_detail'=>$sc_val[1],'rsc_rel_good_id'=>$g_id,'rsc_rel_good_sn'=>$val['g_sn']);
                $rel_spec_combination->where($array_where)->delete();
                if (false === $rel_spec_combination->add($array_where)){
                    //写入日志
                    Log::write('添加规格组合商品失败 Sql:'.$rel_spec_combination->getLastSql());
                    $rel_spec_combination->rollback();
                    $this->ajaxReturn(array('status'=>'error','msg'=>'商品编号：'.$val['g_sn'].'添加失败'));
                }
            }
            
        }
        //编辑成功，事物提交
        $rel_spec_combination->commit();
        $this->ajaxReturn(array('status'=>'success','msg'=>'操作成功'));
    }
    
    /**
     * 执行批量删除规格数组商品
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-14
     */
    public function ajaxDelCombinationPropertyGoods(){
        $str_scg_id = $this->_post('scg_id');
        if(empty($str_scg_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'请选择要删除的规格组合商品！'));
        }
        //开启事物
        M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        //删除主表
        if(false === M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_id'=>array('in',$str_scg_id)))->delete()){
            M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            $this->ajaxReturn(array('status'=>'error','Msg'=>'删除失败'));
        }
        //删除关联表
        if(false === M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_id'=>array('in',$str_scg_id)))->delete()){
            M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            $this->ajaxReturn(array('status'=>'error','Msg'=>'删除失败'));
        }
        M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        $this->ajaxReturn(array('status'=>'success','Msg'=>'删除成功'));
    }
    
    /**
     * 启用（停用）规格组合商品
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-14
     */
    public function enableCombinationPropertyGoods(){
        $scg_id = $this->_post('scg_id');
        $scg_status = $this->_post('scg_status');
        if($scg_status == 1){
            $msg = '开启成功！';
        }else{
            $msg = '关闭成功！';
        }
        if(false === M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_id'=>$scg_id))->save(array('scg_status'=>$scg_status))){
            $this->ajaxReturn(array('status'=>'error','Msg'=>'失败'));
        }else{
            $this->ajaxReturn(array('status'=>'success','Msg'=>$msg));
        }
    }
    
    /**
     * 批量添加商品页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-06
     */
    public function batchGoodsAdd(){
        $this->getSubNav(3,0,10);
        $this->display('pageBatchList');
    }
    
    /**
     * 执行批量添加商品
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-11
     */
    public function doBatchAddGoods(){
        header("Content-type: text/html;charset=utf-8");
        require_once FXINC . '/Lib/Common/' . 'PHPExcel/IOFactory.php';
        require_once FXINC . '/Lib/Common/' . 'PHPExcel.php';
        require_once FXINC . '/Lib/Common/' . 'Upfile.class.php';
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->saveRule  = date('YmdHis') ;// 设置附件上传大小
        $upload->allowExts  = array('xlsx','xls','csv');// 设置附件上传类型
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/'.date('Ymd').'/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $upload->savePath =  $filexcel;// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->errorMsg($upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        $str_upload_file = $info[0]['savepath'].$info[0]['savename'];
        $objCalc = PHPExcel_Calculation::getInstance();
        //读取Excel客户模板
        $objPHPExcel = PHPExcel_IOFactory::load($str_upload_file);
        $obj_Writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //读取第一个工作表(编号从 0 开始)
        $sheet = $objPHPExcel->getSheet(0);
        //取到有多少条记录 
        $highestRow = $sheet->getHighestRow();
        $array_goods_info = array();
        $i = 0;
        for($row=2; $row <= $highestRow; $row++){
            //商品名称的输入验证
            $array_goods_info[$i]['g_name'] = trim($objPHPExcel->getActiveSheet()->getCell('A' . $row)->getCalculatedValue());
            if(empty($array_goods_info[$i]['g_name'])){
                unlink($str_upload_file);
                $this->error('上传模板失败,请输入商品名称');
            }
            //商家编码的输入验证
            $array_goods_info[$i]['g_sn'] = trim($objPHPExcel->getActiveSheet()->getCell('B' . $row)->getCalculatedValue());
            if(empty($array_goods_info[$i]['g_sn'])){
                unlink($str_upload_file);
                $this->error('上传模板失败,请输入商品编码');
            }
            if(preg_match("/[^a-zA-Z0-9\._-]+/",$array_goods_info[$i]['g_sn'])){
                $this->error("上传模板失败,‘{$array_goods_info[$i]['g_sn']}’商品编码不符合要求(	字母、数字或“_”、“-”、“.”组成)！");
            }
            //商家编码的唯一性验证
            $check_result = D("GoodsBase")->where(array('g_sn'=>$array_goods_info[$i]['g_sn']))->getField("g_id");
            if($check_result && is_numeric($check_result)){
                $string_g_name = D("GoodsInfo")->where(array("g_id"=>$check_result))->getField("g_name");
                unlink($str_upload_file);
                $this->errorMsg("上传模板失败,‘{$array_goods_info[$i]['g_sn']}’商品编码已经被“{$string_g_name}”占用！");
            }
            
            $gb_name = trim($objPHPExcel->getActiveSheet()->getCell('C' . $row)->getCalculatedValue());
            if(empty($gb_name)){
                unlink($str_upload_file);;
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’请输入品牌");
            }
            //商品品牌验证
            $array_goods_info[$i]['gb_id'] = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_name'=>$gb_name,'gb_status'=>1))->getField('');
            if(empty($array_goods_info[$i]['gb_id'])){
                unlink($str_upload_file);
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’商品品牌‘{$gb_name}’不存在");
            }
            //商品上下架验证
            $g_on_sale = trim($objPHPExcel->getActiveSheet()->getCell('D' . $row)->getCalculatedValue());
            if(empty($g_on_sale)){
                unlink($str_upload_file);
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’请输入上下架");
            }
            $array_goods_info[$i]['g_on_sale'] = ($g_on_sale == '是') ? '1' : '2';
            //商品计量单位验证
            $array_goods_info[$i]['g_unit'] = trim($objPHPExcel->getActiveSheet()->getCell('E' . $row)->getCalculatedValue());
            if(empty($array_goods_info[$i]['g_unit'])){
                unlink($str_upload_file);
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’请输入计量单位");
            }
            //商品类型验证
            $gt_name = trim($objPHPExcel->getActiveSheet()->getCell('F' . $row)->getCalculatedValue());
            if(empty($gt_name)){
                unlink($str_upload_file);
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’请输入商品类型");
            }
            $array_goods_info[$i]['gt_id'] = D('GoodsType')->where(array('gt_name'=>$gt_name,'gt_status'=>1))->getField('gt_id');
            if(empty($array_goods_info[$i]['gt_id'])){
                unlink($str_upload_file);
                $this->error("上传模板失败,SKU‘{$array_goods_info[$i]['g_sn']}’商品类型‘{$gt_name}’不存在");
            }
            
            $str_good_spec = trim($objPHPExcel->getActiveSheet()->getCell('G' . $row)->getCalculatedValue());
            if(isset($str_good_spec) && !empty($str_good_spec)){
                $ary_good_spec = explode('|',$str_good_spec);
                $array_spec = array();
                foreach ($ary_good_spec as $key=>$val){
                    $ary_tmp_spec = explode(":",$val);
                    if(count($ary_tmp_spec) == 1){
                        unlink($str_upload_file);
                        $this->error('上传模板失败，'.$ary_tmp_spec[0].'参数有误');
                    }
                    //验证商品非销售属性有效性
                    /**
                     * 第一步，查询当前非销售属性名是否存在
                     * 第二步，查询当前非销售属性属于那种类型，如选择类型，查询当前val值是否存在
                     */
                    $goods_spec = D("GoodsSpec")->where(array('gs_name'=>$ary_tmp_spec[0],'gs_is_sale_spec'=>0,'gs_status'=>1))->find();
                    if(empty($goods_spec)){
                        unlink($str_upload_file);
                        $this->error("上传模板失败，商品类型‘{$gt_name}’，不存在‘{$ary_tmp_spec[0]}’此非销售属性！");
                    }
                    if($goods_spec['gs_show_type'] == 1 && $goods_spec['gs_input_type'] == 2){
                        $goods_spec_detail = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gs_id'=>$goods_spec['gs_id'],'gsd_value'=>$ary_tmp_spec[1],'gsd_status'=>1))->find();
                        if(empty($goods_spec_detail)){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，非销售属性名‘{$ary_tmp_spec[0]}’，不存在‘{$ary_tmp_spec[1]}’此非销售属性值！");
                        }
                    }
                    $array_spec[$ary_tmp_spec[0]] = $ary_tmp_spec[1];
                    
                }
                $array_goods_info[$i]['goods_unsales_spec'] = $array_spec;
            }
            
            $str_good_sale_spec = trim($objPHPExcel->getActiveSheet()->getCell('H' . $row)->getCalculatedValue());
            if(empty($str_good_sale_spec)){
                unlink($str_upload_file);
                $this->error("上传模板失败,请输入销售属性");
            }
            $ary_good_sale_spec = explode('|',$str_good_sale_spec);
            $goods_products = array();
            foreach ($ary_good_sale_spec as $k=>$gss){
                
                $ary_tmp_product = explode(',',$gss);

                foreach ($ary_tmp_product as $ampk=>$amp){
                    $ary_key = explode(':',$amp);
                    if(count($ary_key) == 1){
                        unlink($str_upload_file);
                        $this->error('上传模板失败，'.$ary_key[0].'参数有误');
                    }
                    //验证货号的唯一性
                    if($ary_key[0] == '编码'){
                        $ary_pdtsn_check = D("GoodsProductsTable")->where(array("pdt_sn"=>$ary_key[1]))->find();
                        if(is_array($ary_pdtsn_check) && !empty($ary_pdtsn_check)){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，SKU商品编码“{$ary_key[1]}”已经被其他商品占用。");
                        }
                        $goods_products[$k]['pdt_sn'] = $ary_key[1];
                    }
                    //如果无SKU，pdt_sn默认为商品g_sn
                    if($ampk == 1 && empty($goods_products[$k]['pdt_sn'])){
                        $goods_products[$k]['pdt_sn'] = $array_goods_info[$i]['g_sn'];
                    }
                    //验证是否输入库存数量
                    if($ary_key[0] == '库存'){
                        if(!isset($ary_key[1]) || !is_numeric($ary_key[1])){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，SKU“{$goods_products[$k]['pdt_sn']}”的库存数量值不合法。");
                        }
                        $goods_products[$k]['pdt_stock'] = $ary_key[1];
                    }
                    //验证是否输入销售价格
                    if($ary_key[0] == '销售价'){
                        if(!isset($ary_key[1]) || !is_numeric($ary_key[1])){
                            $this->error("上传模板失败，SKU“{$goods_products[$k]['pdt_sn']}”的销售价格数值不合法。");
                        }
                        $goods_products[$k]['pdt_sale_price'] = $ary_key[1];
                    }
                    //验证是否输入销售价格
                    if($ary_key[0] == '成本价'){
                        if(!isset($ary_key[1]) || !is_numeric($ary_key[1])){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，SKU“{$goods_products[$k]['pdt_sn']}”的成本价格数值不合法。");
                        }
                        $goods_products[$k]['pdt_cost_price'] = $ary_key[1];
                    }
                    if($ary_key[0] == '成本价'){
                        //验证是否输入销售价格
                        if(!isset($ary_key[1]) || !is_numeric($ary_key[1])){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，SKU“{$goods_products[$k]['pdt_sn']}”的成本价格数值不合法。");
                        }
                        $goods_products[$k]['pdt_cost_price'] = $ary_key[1];
                    }
                    if($ary_key[0] == '市场价'){
                        //验证是否输入市场价格
                        if(!isset($ary_key[1]) || !is_numeric($ary_key[1])){
                            unlink($str_upload_file);
                            $this->error("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’的市场价格数值不合法。");
                        }
                        $goods_products[$k]['pdt_market_price'] = $ary_key[1];
                    }
                    if($ary_key[0] == '重量'){
                        $goods_products[$k]['pdt_weight'] = $ary_key[1];
                    }
                    if($ary_key[0] == '属性'){
                        $ary_pdt_detail = explode(';',$ary_key[1]);
                        $ary_tmp_drt = array();
                        foreach ($ary_pdt_detail as $key=>$val){
                            $ary_tmp_sc = explode('[',$val);
                            $ary_tmp_sc[1] = rtrim($ary_tmp_sc[1],']');
                            $ary_tmp_drt[$ary_tmp_sc[0]] = $ary_tmp_sc[1];
                        }
                        $goods_products[$k]['products'] = $ary_tmp_drt;
                    }
                }
            }
            $array_goods_info[$i]['goods_products'] = $goods_products;
            
            $exchange = trim($objPHPExcel->getActiveSheet()->getCell('I' . $row)->getCalculatedValue());
            if(empty($exchange)){
                $this->errorMsg("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’请输入是否开启积分兑换");
            }
            $is_exchange = explode('|',$exchange);
            if($is_exchange[0] == '是' && !isset($is_exchange[1]) || !is_numeric($is_exchange[1])){
                unlink($str_upload_file);
                $this->error("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’的积分不合法");
            }
            $array_goods_info[$i]['is_exchange'] = ($exchange[0] == '是') ? 1 : 0;
            $array_goods_info[$i]['point'] = (isset($exchange[1]) && is_numeric($exchange[1]))?$exchange[1]:'0';
            
            $g_remark = trim($objPHPExcel->getActiveSheet()->getCell('J' . $row)->getCalculatedValue());
            if(isset($g_remark)){
                $array_goods_info[$i]['g_remark'] = $g_remark;
            }
            
            $goods_category = trim($objPHPExcel->getActiveSheet()->getCell('K' . $row)->getCalculatedValue());
            if(empty($goods_category)){
                unlink($str_upload_file);
                $this->error("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’请输入商品分类");
            }
            $ary_goods_category = explode(',',$goods_category);
            $array_gc = array();
            foreach ($ary_goods_category as $gc_key=>$gc_val){
                $array_gc[$gc_key] = D('GoodsCategory')->where(array('gc_name'=>$gc_val,'gc_status'=>1))->getField('gc_id');
                if(empty($array_gc[$gc_key])){
                    unlink($str_upload_file);
                    $this->error("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’商品分类‘{$gc_val}’不存在");
                }
            }
            $array_goods_info[$i]['gc_id'] = $array_gc;
            
            $good_picture = trim($objPHPExcel->getActiveSheet()->getCell('L' . $row)->getCalculatedValue());
            if(!isset($good_picture) && empty($good_picture)){
                $this->error("上传模板失败，SKU‘{$goods_products[$k]['pdt_sn']}’商品主图地址‘{$good_picture}’不存在");
            }
            $array_goods_info[$i]['good_picture'] = $good_picture;
            
            $array_picture_info = array();
            $picture_1 = trim($objPHPExcel->getActiveSheet()->getCell('M' . $row)->getCalculatedValue());
            if(isset($picture_1) && !empty($picture_1)){
                $array_picture_info[] = $picture_1;
            }
            
            $picture_2 = trim($objPHPExcel->getActiveSheet()->getCell('N' . $row)->getCalculatedValue());
            if(isset($picture_2) && !empty($picture_2)){
                $array_picture_info[] = $picture_2;
            }
            
            $picture_3 = trim($objPHPExcel->getActiveSheet()->getCell('O' . $row)->getCalculatedValue());
            if(isset($picture_3) && !empty($picture_3)){
                $array_picture_info[] = $picture_3;
            }
            
            $picture_4 = trim($objPHPExcel->getActiveSheet()->getCell('P' . $row)->getCalculatedValue());
            if(isset($picture_4) && !empty($picture_4)){
                $array_picture_info[] = $picture_4;
            }
            
            $picture_5 = trim($objPHPExcel->getActiveSheet()->getCell('Q' . $row)->getCalculatedValue());
            if(isset($picture_5) && !empty($picture_5)){
                $array_picture_info[] = $picture_1;
            }
            
            $picture_6 = trim($objPHPExcel->getActiveSheet()->getCell('R' . $row)->getCalculatedValue());
            if(isset($picture_6) && !empty($picture_6)){
                $array_picture_info[] = $picture_6;
            }
            
            $picture_7 = trim($objPHPExcel->getActiveSheet()->getCell('S' . $row)->getCalculatedValue());
            if(isset($picture_7) && !empty($picture_7)){
                $array_picture_info[] = $picture_7;
            }
            
            $picture_8 = trim($objPHPExcel->getActiveSheet()->getCell('T' . $row)->getCalculatedValue());
            if(isset($picture_8) && !empty($picture_8)){
                $array_picture_info[] = $picture_8;
            }
            
            $picture_9 = trim($objPHPExcel->getActiveSheet()->getCell('U' . $row)->getCalculatedValue());
            if(isset($picture_9) && !empty($picture_9)){
                $array_picture_info[] = $picture_9;
            }
            $array_goods_info[$i]['pics'] = $array_picture_info;
            
            $good_new = trim($objPHPExcel->getActiveSheet()->getCell('V' . $row)->getCalculatedValue());
            if(empty($good_new)){
                unlink($str_upload_file);
                $this->error('上传模板失败，请输入新品');
            }
            $array_goods_info[$i]['g_new'] = ($good_new == '是') ? 1 : 0;
            
            $good_hot = trim($objPHPExcel->getActiveSheet()->getCell('W' . $row)->getCalculatedValue());
            if(empty($good_hot)){
                unlink($str_upload_file);
                $this->error('上传模板失败，请输入热品');
            }
            $array_goods_info[$i]['g_hot'] = ($good_hot == '是') ? 1 : 0;
            
            $goods_gifts = trim($objPHPExcel->getActiveSheet()->getCell('X' . $row)->getCalculatedValue());
            if(empty($goods_gifts)){
                unlink($str_upload_file);
                $this->error('上传模板失败，请输入是否为不正常销售赠品');
            }
            $array_goods_info[$i]['goods_gifts'] = ($goods_gifts == '是') ? 1 : 0;
            
            $goods_gifts_2 = trim($objPHPExcel->getActiveSheet()->getCell('Y' . $row)->getCalculatedValue());
            if(empty($goods_gifts_2)){
                unlink($str_upload_file);
                $this->error('上传模板失败，请输入是否为正常销售赠品');
            }
            $array_goods_info[$i]['goods_gifts_2'] = ($goods_gifts_2 == '是') ? 2 : 0;
            
            $g_pre_sale_status = trim($objPHPExcel->getActiveSheet()->getCell('Z' . $row)->getCalculatedValue());
            if(empty($g_pre_sale_status)){
                unlink($str_upload_file);
                $this->error('上传模板失败，请输入是否为预售商品');
            }
            $array_goods_info[$i]['g_pre_sale_status'] = ($g_pre_sale_status == '是') ? 1 : 0;
            
            //商品资料自定义字段1-5（1为温馨提示）
            $g_custom_field_1 = trim($objPHPExcel->getActiveSheet()->getCell('AA' . $row)->getCalculatedValue());
            if(isset($g_custom_field_1)){
                $array_goods_info[$i]['g_custom_field_1']  = $g_custom_field_1;
            }
            
            $g_custom_field_2 = trim($objPHPExcel->getActiveSheet()->getCell('AB' . $row)->getCalculatedValue());
            if(isset($g_custom_field_2)){
                $array_goods_info[$i]['g_custom_field_2']  = $g_custom_field_2;
            }
            
            $g_custom_field_3 = trim($objPHPExcel->getActiveSheet()->getCell('AC' . $row)->getCalculatedValue());
            if(isset($g_custom_field_3)){
                $array_goods_info[$i]['g_custom_field_3']  = $g_custom_field_3;
            }    
            
            $g_custom_field_4 = trim($objPHPExcel->getActiveSheet()->getCell('AD' . $row)->getCalculatedValue());
            if(isset($g_custom_field_4)){
                $array_goods_info[$i]['g_custom_field_4']  = $g_custom_field_4;
            }
            
            $g_custom_field_5 = trim($objPHPExcel->getActiveSheet()->getCell('AE' . $row)->getCalculatedValue());
            if(isset($g_custom_field_5)){
                $array_goods_info[$i]['g_custom_field_5']  = $g_custom_field_5;
            }
            
            $g_keywords = trim($objPHPExcel->getActiveSheet()->getCell('AF' . $row)->getCalculatedValue());
            if(isset($g_keywords)){
                $array_goods_info[$i]['g_keywords']  = $g_keywords;
            }
            
            $g_description = trim($objPHPExcel->getActiveSheet()->getCell('AG' . $row)->getCalculatedValue());
            if(isset($g_description)){
                $array_goods_info[$i]['g_description']  = $g_description;
            }
            
            //商品详情资料
            $g_desc = trim($objPHPExcel->getActiveSheet()->getCell('AH' . $row)->getCalculatedValue());
            if(isset($g_desc)){
                $array_goods_info[$i]['g_desc']  = $g_desc;
            }
            $i++;
        }
        //echo "<pre>";print_r($array_goods_info);exit;
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        foreach ($array_goods_info as $good_key=>$good_val){
            //系统时间定义
            $string_date_time = date("Y-m-d H:i:s");
            //商品基本信息表 fx_goods 写入数据
            $array_fx_goods = array();
            $ary_goods = $good_val;
            //商品品牌ID
            $array_fx_goods["gb_id"] = (isset($ary_goods["gb_id"]) && is_numeric($ary_goods["gb_id"]))?$ary_goods["gb_id"]:0;
            //商品类型ID，如果等于0表是此商品不启用规格。注意：此值大于1不表示启用规格。
            $array_fx_goods["gt_id"] = (isset($ary_goods["gt_id"]) && is_numeric($ary_goods["gt_id"]))?$ary_goods["gt_id"]:0;
            $array_fx_goods["g_on_sale"] = (isset($ary_goods["g_on_sale"]) && is_numeric($ary_goods["g_on_sale"]))?$ary_goods["g_on_sale"]:2;
            $array_fx_goods["g_sn"] = (isset($ary_goods["g_sn"]) && "" != trim($ary_goods["g_sn"]))?trim($ary_goods["g_sn"]):'';
            $array_fx_goods["g_new"] = (isset($ary_goods["g_new"]) && is_numeric($ary_goods["g_new"]))?$ary_goods["g_new"]:0;
            $array_fx_goods["g_hot"] = (isset($ary_goods["g_hot"]) && is_numeric($ary_goods["g_hot"]))?$ary_goods["g_hot"]:0;
            $array_fx_goods["g_gifts"] = (isset($ary_goods["goods_gifts"]) && is_numeric($ary_goods["goods_gifts"]))?$ary_goods["goods_gifts"]:$ary_goods["goods_gifts_2"];
            $array_fx_goods["g_pre_sale_status"] = (isset($ary_goods["g_pre_sale_status"]) && is_numeric($ary_goods["g_pre_sale_status"]))?$ary_goods["g_pre_sale_status"]:0;
            $array_fx_goods["g_create_time"] = $string_date_time;
            $array_fx_goods["g_update_time"] = $string_date_time;
            
            //事务开始，商品资料数据复杂，必须启用事务。
            
            $int_goods_id = D("GoodsBase")->add($array_fx_goods);
            if(false === $int_goods_id){
                unlink($str_upload_file);
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->error("商品资料添加失败。CODE:FX-GOODS;");
            }
            
            //商品资料详细信息表 fx_goods_info
            $array_good_info = array();
            $array_good_info["g_id"] = $int_goods_id;
            $array_good_info["g_name"] = (isset($ary_goods["g_name"]) && "" != trim($ary_goods["g_name"]))?trim($ary_goods["g_name"]):'此商品无标题';
            $array_good_info["g_keywords"] = (isset($ary_goods["g_keywords"]) && "" != trim($ary_goods["g_keywords"]))?trim($ary_goods["g_keywords"]):'';
            $array_good_info["g_description"] = (isset($ary_goods["g_description"]) && "" != trim($ary_goods["g_description"]))?trim($ary_goods["g_description"]):'';
            $array_good_info["g_sn"] = (isset($ary_goods["g_sn"]) && "" != trim($ary_goods["g_sn"]))?trim($ary_goods["g_sn"]):'';
            $array_good_info["g_price"] = 0.00;
            $array_good_info["g_market_price"] = 0.00;
            //TODO 如果用户未输入库存数，则此处库存数为所有SKU库存数之和。
            $array_good_info["g_stock"] = 0;
            //TODO 如果没有输入重量，则此处重量为所有SKU重量最大值
            $array_good_info["g_weight"] = 0;
            $array_good_info["g_unit"] = (isset($ary_goods["g_unit"]) && "" != $ary_goods["g_unit"])?$ary_goods["g_unit"]:'';
            $array_good_info["g_remark"] = $ary_goods["g_remark"];
            //TODO：此处可能需要将上传的图片处理成缩略图。
            $array_good_info["g_picture"] = '/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$ary_goods["good_picture"]),'/'));
            $array_good_info["g_source"] = 'local';
            //商品自定义字段维护
            $array_good_info["g_custom_field_1"] = (isset($ary_goods["g_custom_field_1"]) && "" != $ary_goods["g_custom_field_1"])?$ary_goods["g_custom_field_1"]:'';
            $array_good_info["g_custom_field_2"] = (isset($ary_goods["g_custom_field_2"]) && "" != $ary_goods["g_custom_field_2"])?$ary_goods["g_custom_field_2"]:'';
            $array_good_info["g_custom_field_3"] = (isset($ary_goods["g_custom_field_3"]) && "" != $ary_goods["g_custom_field_3"])?$ary_goods["g_custom_field_3"]:'';
            $array_good_info["g_custom_field_4"] = (isset($ary_goods["g_custom_field_4"]) && "" != $ary_goods["g_custom_field_4"])?$ary_goods["g_custom_field_4"]:'';
            $array_good_info["g_custom_field_5"] = (isset($ary_goods["g_custom_field_5"]) && "" != $ary_goods["g_custom_field_5"])?$ary_goods["g_custom_field_5"]:'';
            $array_good_info["is_exchange"] = (isset($ary_goods["is_exchange"]) && is_numeric($ary_goods["is_exchange"]))?$ary_goods["is_exchange"]:'0';
            $array_good_info["point"] = $ary_goods["point"];
            $array_good_info["g_desc"] = $ary_goods["g_desc"];
            $array_good_info["g_create_time"] = $string_date_time;
            $array_good_info["g_update_time"] = $string_date_time;
            if(false === D("GoodsInfo")->add($array_good_info)){
                unlink($str_upload_file);
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->error("商品资料录入失败。CODE:FX-GOODS-INFO;");
            }
            
            //商品图片数据录入
            if(isset($ary_goods["pics"]) && !empty($ary_goods["pics"])){
                foreach($ary_goods["pics"] as $picture){
                    //TODO 如果是本地图片，此处验证可能无法通过。
                    if("" != $picture){
                        $i ++;
                        $array_picture = array();
                        $array_picture["g_id"] = $int_goods_id;
                        $array_picture["gp_picture"] = '/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$picture),'/'));
                        $array_picture["gp_order"] = $i;
                        $array_picture["gp_status"] = 1;
                        $array_picture["gp_create_time"] = $string_date_time;
                        $array_picture["gp_update_time"] = $string_date_time;
                        $int_goods_picture_id = D("GoodsPictures")->add($array_picture);
                        if(false === $int_goods_picture_id){
                            unlink($str_upload_file);
                            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                            $this->error("商品资料添加失败。CODE:FX-GOODS-PICTURES-$i");
                        }
                    }
                }
            }
        
            //商品分类数据保存到商品-分类关联表中
            if(isset($ary_goods["gc_id"]) && !empty($ary_goods["gc_id"])){
                foreach($ary_goods["gc_id"] as $cat){
                    $array_cat = array();
                    $array_cat["g_id"] = $int_goods_id;
                    $array_cat["gc_id"] = $cat;
                    $result = D("RelatedGoodsCategory")->add($array_cat);
                    if(false === $result){
                        unlink($str_upload_file);
                        M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                        $this->errorMsg("商品资料保存失败。CODE:FX-RELATED-GOODS-CAT");
                    }
                }
            }
        
            //商品扩展属性存入数据库，如果有的话。
            if(isset($ary_goods["goods_unsales_spec"]) && !empty($ary_goods["goods_unsales_spec"])){
                foreach($ary_goods["goods_unsales_spec"] as $gs_name=>$spec_value){
                    if("" != $spec_value){
                        $array_tmp_spec_info = D('GoodsSpec')->where(array('gs_name'=>$gs_name,'gs_status'=>1))->find();
                        $int_gsd_id = 0;
                        $string_spec_value = $spec_value;
                        if(2 == $array_tmp_spec_info["gs_input_type"]){

                            $array_tmp_spec_detail = D("GoodsSpecDetail")->where(array("gsd_value"=>$spec_value,"gs_id"=>$array_tmp_spec_info['gs_id']))->find();
                            $int_gsd_id = $array_tmp_spec_detail['gsd_id'];
                            $string_spec_value = $array_tmp_spec_detail["gsd_value"];
                            
                        }
                        $array_unsale_spec = array();
                        $array_unsale_spec["gs_id"] = $array_tmp_spec_info['gs_id'];
                        $array_unsale_spec["gsd_id"] = $int_gsd_id;
                        $array_unsale_spec["pdt_id"] = 0;
                        $array_unsale_spec["gs_is_sale_spec"] = 0;
                        $array_unsale_spec["g_id"] = $int_goods_id;
                        $array_unsale_spec["gsd_aliases"] = $string_spec_value;
                        $array_unsale_spec["gsd_picture"] = "";
                        
                        $result = D("RelatedGoodsSpec")->add($array_unsale_spec);
                        if(false === $result){
                            unlink($str_upload_file);
                            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                            $this->error("商品扩展属性信息录入失败。CODE:RELATED-GOODS-SPEC");
                            exit;
                        }
                    }
                }
            }
        
            //商品SKU数据录入:商品SKU数据录入需要分为以下两种情况
            /**
             * 情况一：不启用SKU
             * 请框二：启用SKU
             * 注意：按照既定规则，在不启用SKU的情况下，也要像products表中写入一条记录。
             * 如果启用的规格包含规格别名，则将规格别名存入到别名表中
             * 
             **/
            if(isset($ary_goods["goods_products"][0]["products"]) && !empty($ary_goods["goods_products"][0]["products"])){
                foreach($ary_goods["goods_products"] as $key => $products){
                    $array_products = array();
                    $array_products["g_id"] = $int_goods_id;
                    $array_products["g_sn"] = $good_val["g_sn"];
                    $array_products["pdt_sn"] = $products['pdt_sn'];
                    $array_products["pdt_sale_price"] = $products['pdt_sale_price'];
                    $array_products["pdt_cost_price"] = (is_numeric($products['pdt_cost_price']))?$products['pdt_cost_price']:0.00;
                    $array_products["pdt_market_price"] = (is_numeric($products['pdt_market_price']))?$products['pdt_market_price']:0.00;
                    $array_products["pdt_weight"] = is_numeric($products['pdt_weight'])?$products['pdt_weight']:0;
                    //商品资料初始化，SKU总库存=可下单库存，冻结库存为0；
                    $array_products["pdt_total_stock"] = (is_numeric($products['pdt_stock']))?$products['pdt_stock']:0;
                    $array_products["pdt_stock"] = $array_products["pdt_total_stock"];
                    $array_products["pdt_freeze_stock"] = 0;
                    //TODO:此处需要产生一张初始化的库存调整单，并且自动审核通过
                    //TODO:此处如果需要设置单次下单最大值，价格区间，备注，承诺到货日，承诺发货日等数据，可以在此处修改
                    $array_products["pdt_create_time"] = $string_date_time;
                    $array_products["pdt_update_time"] = $string_date_time;
                    $int_products_id = D("GoodsProductsTable")->add($array_products);
                    if(false === $int_products_id){
                        M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                        unlink($str_upload_file);
                        $this->error("商品资料录入失败。CODE:FX-GOODS-PRODUCTS");
                    }
                    //此SKU关联的属性存入商品-SKU-SPEC关联表
                    /**
                     * 此处先将$str_spec_vids按照逗号分隔开来，然后获取规格值对应的规格ID
                     * 如果是系统颜色属性，如果设置了颜色图片，还要将图片存入数据库中
                     **/
                    foreach($products['products'] as $key=>$val){
                        //获取gs_id
                        $array_goods_spec_id = D('RelatedGoodsTypeSpec')->where(array('gt_id'=>$array_fx_goods["gt_id"]))->getField('gs_id',true);
                        $good_spec = D('GoodsSpec')->where(array('gs_name'=>$key,'gs_status'=>1,'gs_id'=>array('in',$array_goods_spec_id)))->find();
                        if(empty($good_spec) && !isset($good_spec)){
                            unlink($str_upload_file);
                            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                            $this->error("‘{$array_products['g_sn']}’不存在‘{$key}’销售属性。CODE:FX-GOODS;");
                        }
                        $int_pid = $good_spec['gs_id'];
                        //匹配当前gs_id与其值对应的gsd_id
                        $good_spec_detail = D('GoodsSpecDetail')->where(array('gs_id'=>$int_pid,'gsd_value'=>$val))->find();
                        if(isset($good_spec_detail) && !empty($good_spec_detail)){
                            $int_vid = $good_spec_detail['gsd_id'];
                        }else{
                            $array_spec_detail = D('GoodsSpecDetail')->where(array('gs_id'=>$int_pid))->select();
                            if(empty($array_spec_detail) && !isset($array_spec_detail)){
                                unlink($str_upload_file);
                                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                $this->error("‘{$array_products['g_sn']}’不存在‘{$key}’销售属性名。CODE:FX-GOODS-SPEC;");
                            }else{
                                foreach ($array_spec_detail as $asdk=>$asdv){
                                    $count_spec_detail_v = D("RelatedGoodsSpec")->where(array('gs_id'=>$int_pid,'gsd_id'=>$asdv['gsd_id'],'gs_is_sale_spec'=>1,'g_id'=>$int_goods_id))->find();
                                    if(!isset($count_spec_detail_v) && empty($count_spec_detail_v)){
                                        $int_vid = $asdv['gsd_id'];
                                        break;
                                    }
                                }
                                if(empty($int_vid)){
                                    unlink($str_upload_file);
                                    M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                                    $this->error("‘{$array_products['g_sn']}’不存在‘{$val}’销售属性值。CODE:FX-GOODS-SPEC-DETAIL;");
                                }
                            }
                        }
                        $array_related_spec = array();
                        $array_related_spec["gs_id"] = $int_pid;
                        $array_related_spec["gsd_id"] = $int_vid;
                        $array_related_spec["pdt_id"] = $int_products_id;
                        $array_related_spec["gs_is_sale_spec"] = 1;
                        $array_related_spec["g_id"] = $int_goods_id;
                        $array_related_spec["gsd_aliases"] = (isset($val) && "" != $val)?$val:'';
                        $result = D("RelatedGoodsSpec")->add($array_related_spec);
                        if(false === $result){
                            unlink($str_upload_file);
                            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                            $this->errorMsg("商品资料保存失败。CODE:FX-RELATED-GOODS-SPEC;");
                            exit;
                        }
                    }

                }
            }else{
                //没有启用规格的时候，也要往规格表中插入一条记录的数据。
                $array_product_info = array();
                $array_product_info["g_id"] = $int_goods_id;
                $array_product_info["g_sn"] = $good_val["g_sn"];
                $array_product_info["pdt_sn"] = $good_val["g_sn"];
                $array_product_info["pdt_sale_price"] = (isset($ary_goods["goods_products"][0]["pdt_sale_price"]) && is_numeric($ary_goods["goods_products"][0]["pdt_sale_price"]))?$ary_goods["goods_products"][0]["pdt_sale_price"]:0.00;
                $array_product_info["pdt_cost_price"] = (isset($ary_goods["goods_products"][0]["pdt_cost_price"]) && is_numeric($ary_goods["goods_products"][0]["pdt_cost_price"]))?$ary_goods["goods_products"][0]["pdt_cost_price"]:0.00;
                $array_product_info["pdt_market_price"] = (isset($ary_goods["goods_products"][0]["pdt_market_price"]) && is_numeric($ary_goods["goods_products"][0]["pdt_market_price"]))?$ary_goods["goods_products"][0]["pdt_market_price"]:0.00;
                $array_product_info["pdt_weight"] = (isset($ary_goods["goods_products"][0]["pdt_weight"]) && is_numeric($ary_goods["goods_products"][0]["pdt_weight"]))?$ary_goods["goods_products"][0]["pdt_weight"]:0;
                //TODO:此处需要产生一张初始化商品库存的单据
                $array_product_info["pdt_total_stock"] = (isset($ary_goods["goods_products"][0]["pdt_stock"]) && is_numeric($ary_goods["goods_products"][0]["pdt_stock"]))?$ary_goods["goods_products"][0]["pdt_stock"]:0;
                $array_product_info["pdt_stock"] = $array_product_info["pdt_total_stock"];
                $array_product_info["pdt_freeze_stock"] = 0;
                $array_product_info["pdt_status"] = 1;
                $array_product_info["pdt_create_time"] = $string_date_time;
                $array_product_info["pdt_update_time"] = $string_date_time;
                $int_product_id = D("GoodsProductsTable")->add($array_product_info);
                if(false === $int_product_id){
                    M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                    unlink($str_upload_file);
                    $this->error("商品资料保存失败。CODE:GOODS-PRODUCT-SIMPLE;");
                }
               
            
            }
            
            //更新商品信息表中的g_price为货品表中最高的价格：
            //需要先获取SKU中最高的市场价格；然后更新到goods_info表中；
            //更新商品表中的库存为货品表中库存之和：
            //需要获取SKU中的库存数量之和，然后更新到goods_info表。
            $array_fetch_condition = array("g_id"=>$int_goods_id,"pdt_satus"=>1);
            $string_fields = "g_id,max(`pdt_market_price`) as `g_market_price`,max(`pdt_sale_price`) as `g_price`,max(`pdt_weight`) as `g_weight`,sum(`pdt_total_stock`) as `g_stock`";
            $array_result = D("GoodsProductsTable")->where($array_fetch_condition)->getField($string_fields);
            if(false === $array_result){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                unlink($str_upload_file);
                $this->errorMsg("商品资料保存失败。CODE:GET-MAX-PRODUCT-MARKET-PRICE-FAILED;");
                exit;
            }
            $array_save_data = array();
            $array_save_data = $array_result[$int_goods_id];
            $result = D("GoodsInfo")->where(array("g_id"=>$int_goods_id))->save($array_save_data);
            if(false === $result){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                unlink($str_upload_file);
                $this->errorMsg("商品资料保存失败。CODE:UPDATE-MAX-MARKET-PRICE-FAILED;");
                exit;
            }
        }
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        //提示商品资料添加成功，并且跳转到目标页面。
		$this->successMsg("商品资料保存成功。",U("Ucenter/Goods/pageList"));
        
    }
    
    /**
     * 前台下架商品列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @modify Terry<wanghui@guanyisoft.com> 2013-3-19
     * @date 2012-12-31
     */

     public function pageList() {
        $ary_member = $_SESSION['Members'];
        if($ary_member['m_type'] != 2){
            $this->error('请核对您的身份，只有供货商才能进入！');
            die;
        }
        
        $array = $this->_get();
        //echo '<pre>';print_r($array);die;
        $ary_request = $this->_request();
		$currentPage = (int)$ary_request['p'];
		if(0 != $currentPage){
			session('page',$currentPage);
		}
        //$ary_request['tabs'] = empty($ary_request['tabs']) ? "website" : $ary_request['tabs'];
        $data = array();
        $ary_where = array();
        if (!empty($ary_request['search']) && $ary_request['search'] == 'easy') {
            switch ($ary_request['field']) {
                case 'g_sn':
                    $ary_where['g_sn'] = trim($ary_request['val']);
                    break;
                case 'g_name':
                    $ary_where['gi.g_name'] = array('like', "%" . trim($ary_request['val']) . "%");
                    break;
            }
            if(!empty($ary_request['gpid']) && isset($ary_request['gpid'])){
                $array_goods_id = M('related_goods_group',C('DB_PREFIX'), 'DB_CUSTOM')->distinct(true)->field('g_id')->where(array('gg_id'=>$ary_request['gpid']))->select();
                $ary_gid = array();
                foreach ($array_goods_id as $gid){
                    $ary_gid[] = $gid['g_id'];
                }

                $ary_where['gi.g_id'] = array('in',isset($ary_gid)?$ary_gid:'');
                
            }
            
            if(!empty($ary_request['g_on_sale']) && isset($ary_request['g_on_sale'])){
                $array_goods_sale = M('Goods')->field('g_on_sale')->where(array('g_on_sale'=>$ary_request['g_on_sale']))->select();
                $ary_sale = array();
                foreach ($array_goods_sale as $v){
                    $ary_sale[] = $v['g_on_sale'];
                }
                $ary_where['g_on_sale'] = array('in',isset($ary_sale)?$ary_sale:'');
                //echo '<pre>';print_r($ary_where['g_on_sale']);die;
            }
        } else {
            if (isset($ary_request['category']) && !empty($ary_request['category'])) {
                $ary_where["fx_goods.g_id"] = 0;
                //如果指定了商品分类进行检索，则先获取该商品分类下关联的商品ID
                $array_related_g_ids = D("RelatedGoodsCategory")->distinct(true)->where(array("gc_id" => array('in', $ary_request['category'])))->getField("g_id", true);
                //echo D("RelatedGoodsCategory")->getLastSql();exit;
                if (!empty($array_related_g_ids)) {
                    $ary_where["fx_goods.g_id"] = array("IN", $array_related_g_ids);
                }
            }
            //品牌搜索
            if (!empty($ary_request['brand']) && isset($ary_request['brand'])) {
                $ary_where['gb_id'] = array('in',$ary_request['brand']);
            }
            if (!empty($ary_request['status']) && isset($ary_request['status'])) {
                $ary_where['g_on_sale'] = $ary_request['status'];
            }
            if (!empty($ary_request['start_time']) && isset($ary_request['start_time'])) {
                if (!empty($ary_request['end_time']) && $ary_request['end_time'] > $ary_request['start_time']) {
                    $ary_request['end_time'] = trim($ary_request['end_time']) . " 23:59:59";
                } else {
                    $ary_request['end_time'] = date("Y-m-d H:i:s");
                }
                $ary_where['g_update_time'] = array("between", array($ary_request['start_time'] . " 00:00:00", $ary_request['end_time']));
            }
            if (!empty($ary_request['stockSymbol']) && !empty($ary_request['stock'])) {
                $ary_where['g_stock'] = array($ary_request['stockSymbol'], $ary_request['stock']);
            }
            if (!empty($ary_request['new']) && !empty($ary_request['new'])) {
                $ary_where['g_new'] = $ary_request['new'];
            }
            if (!empty($ary_request['hot']) && !empty($ary_request['hot'])) {
                $ary_where['g_hot'] = $ary_request['hot'];
            }
        }
        $ary_where['g_status'] = '1';
        $int_page_size = 10;
        //商品列表页页签处理
        $string_name = trim($ary_request['tabs']);
        $ary_where['gm_id'] = $_SESSION['Members']['m_id'];
        
        $ary_where['fx_goods.g_is_combination_goods'] = 0;
        if(!empty($ary_request['ggid']) && (int)$ary_request['ggid'] > 0){
            $ary_where['gg.gg_id'] = $ary_request['ggid'];
        }
        $this->getSubNav(3, 0, $admin_left_menu);
        //按修改时间排序
        $order_by = 'g_update_time desc';
        
        $data = $this->pageGoods($ary_where, $order_by, $int_page_size);
        //echo '<pre>';print_r($data);die;
        $this->assign("filter", $ary_request);
        $this->assign("page", $data['page']);
        $this->assign("data", $data['list']);
		//获取所有的商品分组，提供给页面批量操作使用
		$this->assign("goodsgroups",D("GoodsGroup")->where(array("gg_status"=>1))->order(array("gg_order"=>"asc"))->select());
        $this->display();
    }
    
     /**
     * 后台官网商品列表页
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function pageGoods($array_condition = array(), $order_by, $int_page_size = 20) {
        $GoodsBaseModel = D("GoodsBase");
        $count = $GoodsBaseModel
                ->where($array_condition)
                ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id) ")
                ->join("fx_related_goods_group as rgg on(fx_goods.g_id=rgg.g_id)")
                ->join("fx_goods_group as gg on(gg.gg_id = rgg.gg_id)")
                ->count('distinct(fx_goods.`g_id`)');
        //echo "<pre>";print_r($GoodsBaseModel->getLastSql());exit;
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $GoodsBaseModel
                ->where($array_condition)
                ->field("
				distinct(fx_goods.`g_id`) AS `g_id`,
				fx_goods.`gb_id` AS `gb_id`,fx_goods.`gt_id` AS `gt_id`,fx_goods.`g_on_sale` AS `g_on_sale`,
				fx_goods.`g_status` AS `g_status`,fx_goods.`g_sn` AS `g_sn`,
				fx_goods.`g_off_sale_time` AS `g_off_sale_time`,fx_goods.`g_on_sale_time` AS `g_on_sale_time`,
				fx_goods.`g_new` AS `g_new`,fx_goods.`g_hot` AS `g_hot`,fx_goods.`g_retread_date` AS `g_retread_date`,
				fx_goods.`g_pre_sale_status` AS `g_pre_sale_status`,fx_goods.`g_gifts` AS `g_gifts`,
				`gi`.`ma_price` AS `ma_price`,
				`gi`.`mi_price` AS `mi_price`,`gi`.`g_name` AS `g_name`,`gi`.`g_price` AS `g_price`,
				`gi`.`g_unit` AS `g_unit`,`gi`.`g_desc` AS `g_desc`,`gi`.`g_picture` AS `g_picture`,
				`gi`.`g_no_stock` AS `g_no_stock`,`gi`.`g_create_time` AS `g_create_time`,
				`gi`.`g_update_time` AS `g_update_time`,`gi`.`g_red_num` AS `g_red_num`,
				`gi`.`g_source` AS `g_source`,`gi`.`g_stock` AS `g_stock`,
				`gi`.`g_salenum` AS `g_salenum`,`gi`.`point` AS `point`,
				`gi`.`is_exchange` AS `is_exchange`,group_concat(gg.gg_name) as group_name               
                ")
                ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id) ")
                ->join("fx_related_goods_group as rgg on(fx_goods.g_id=rgg.g_id)")
                ->join("fx_goods_group as gg on(gg.gg_id = rgg.gg_id)")
                ->order($order_by)
                ->group('fx_goods.g_id')
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
                //echo "<pre>";print_r($GoodsBaseModel->getLastSql());exit;
        return $data;
    }
    
    /**
     * 商品加入回收站
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-12
     */
    public function doGoodsisRecycle(){
        if(false === M('Goods')->where(array('g_id'=>$this->_post('gid')))->save(array('g_status'=>2))){
            $this->error('操作失败');
        }else{
            $this->success('操作成功');
        }
    }
    
    /**
     * 删除商品操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function doGoodsisDel() {
        $ary_post = $this->_post();
        //验证是否指定要删除的商品ID
        if (!isset($ary_post['gid']) || empty($ary_post['gid'])) {
            $this->error("请指定要删除的商品ID。");
        }
        $ary_g_id = explode(',',$ary_post['gid']);
        $goods = M('goods', C('DB_PREFIX'), 'DB_CUSTOM');
        $goods->startTrans();
        foreach ($ary_g_id as $g_id){
            //需要验证此商品是否存在
            $ary_result = D("Goods")->where(array('g_id' => $g_id))->find();
            //echo '<pre>';print_r($ary_result);die;
            if($ary_result['g_on_sale'] == 1){
                $this->error("您选中的商品中有上架状态，不能被删除！");
            } 
            if (empty($ary_result) && !is_array($ary_result)) {
                $this->error("该商品不存在,请重试...");
            }
            //需要验证此商品是否存在被组合商品占用
            if (!empty($ary_result['g_is_combination_goods']) && $ary_result['g_is_combination_goods'] == '1') {
                $this->error("该商品为组合商品,不允许删除...");
            }
            //需要验证此商品是否存在未发货订单
            
            //商品资料的删除是物理删除，并记录删除人ID，需要操作以下表：
            //商品基本资料表，商品详细资料表，规格表，商品分类关联表，商品属性关联表，商品图片表
            //规格-价格关联表


            $ary_goods_info = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_info) {
                $goods->rollback();
                $this->error("删除商品详细资料失败,请重试...");
                exit;
            }
            $ary_res = $goods->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_res) {
                $goods->rollback();
                $this->error("删除商品基本信息失败,请重试...");
                exit;
            }
            $ary_goods_pictures = M('goods_pictures', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_pictures) {
                $goods->rollback();
                $this->error("删除商品图片失败,请重试...");
                exit;
            }
            $ary_goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_products) {
                $goods->rollback();
                $this->error("删除规格商品失败,请重试...");
                exit;
            }
            $ary_related_goods_spec = M('related_goods_spec', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_related_goods_spec) {
                $goods->rollback();
                $this->error("删除商品属性失败,请重试...");
                exit;
            }
            $ary_related_goods_category = M('related_goods_category', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_related_goods_category) {
                $goods->rollback();
                $this->error("删除该商品分类失败,请重试...");
                exit;
            }
			
			//删除商品分组
			M('related_goods_group', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            $array_goods_modify_log = array();
            $array_goods_modify_log["g_id"] = $g_id;
            $array_goods_modify_log["pdt_id"] = 0;
            $array_goods_modify_log["pdt_sn"] = '';
            $array_goods_modify_log["u_id"] = $_SESSION["Admin"];
            $array_goods_modify_log["gpml_desc"] = '删除商品，商品编码为' . $ary_result['g_sn'];
            $array_goods_modify_log["gpml_create_time"] = date("Y-m-d H:i:s");
            if (false === D("GoodsProductsModifyLog")->add($array_goods_modify_log)) {
                D("GoodsProductsModifyLog")->rollback();
                $this->errorMsg("记录删除规格日志失败。");
            }
        }
        
        $goods->commit();
        $this->success("删除商品成功");
    }
    
    
    /**
     * 商品批量操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function isBatGoods() {
        $ary_post = $this->_post();
        if (!empty($ary_post['gid']) && isset($ary_post['gid'])) {
            $where = array();
            $data = array();
            $data[$ary_post['field']] = $ary_post['val'];
            $where['g_id'] = array('in', $ary_post['gid']);
            $ary_result = D("ViewGoods")->where($where)->data($data)->save();
            if (FALSE !== $ary_result) {
                $this->success("操作成功");
            } else {
                $this->error("操作失败");
            }
        } else {
            $this->error("请选择需要操作的商品");
        }
    }
}
