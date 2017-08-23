<?php
class TestDataModel extends GyfxModel{

	private $array_type_name = array('上衣','裤子','鞋子');
	private $arrar_attr_name = array(
		array('袖长','胸围'),
		array('衣长','腰围'),
		array('尺码','底型','鞋带穿孔')
	);
	
	private $array_attr_value = array(
		array(array('ML','L','XL','2XL','3XL'),array('80CM','90CM','100CM','110CM','120CM','130CM','140CM')),
		array(array(90,100,110,120,130,140),array(28,29,30,31,32,33,34,35,36,37)),
		array(array(35,36,37,38,39,40,41,42,43,44,45),array('平板','滑雪齿','板鞋','防滑齿','耐磨型'),array(8,10,12,14,16,18,20))
	);
	
	private $array_brand_name = array('鸿兴尔克','耐克','森马服饰','劲霸男装','真维斯','李宁','乔丹');

	public function __construct(){
		ini_set("display_errors","on");
		$this->cateateGoodsCategory();
		$this->createGoodTypeBrandAttrData();
		$this->createGoodsData();
	}
	
	/**
	 * 产生一条商品数据
	 */
	private function createGoodsData(){
		//获取所有的商品分类
		$array_catinfo = $this->parseArray(M("goods_category",C('DB_PREFIX'),'DB_CUSTOM')->where(array('gc_level'=>2,'gc_status'=>1))->select(),"gc_id");
		//print_r($array_catinfo);exit;
		//获取所有的商品类型
		$array_typeinfo = $this->parseArray(M("goods_type",C('DB_PREFIX'),'DB_CUSTOM')->where(array('gt_status'=>1))->select(),"gt_id");
		//print_r($array_typeinfo);exit;
		//获取所有的商品属性ID
		$array_specinfo = $this->parseArray(M("goods_spec",C('DB_PREFIX'),'DB_CUSTOM')->where(array('gs_status'=>1))->select(),"gs_id");
		//print_r($array_specinfo);exit;
		//获取各个属性对应的属性值
		$array_spec_value = array();
		foreach($array_specinfo as $key=>$val){
			$array_spec_value[$val] = $this->parseArray(M("goods_spec_detail",C('DB_PREFIX'),'DB_CUSTOM')->where(array('gsd_status'=>1,"gs_id"=>$val))->select(),"gsd_id");
		}
		//print_r($array_spec_value);exit;
		for($i=1;$i < 1000;$i++){
			$array_goods_data = array();
			$array_goodsinfo_data = array();
			$array_product_data = array();
			$array_product_data = array();
			$int_gt_id = $array_typeinfo[rand(0,count($array_typeinfo)-1)];
			//获取关联的品牌
			$array_related_brand = $this->parseArray(M("related_brand_type",C('DB_PREFIX'),'DB_CUSTOM')->where(array('gt_id'=>$int_gt_id))->select(),"gb_id");
			//print_r($array_related_brand);exit;
			$array_goods_data['gt_id'] = $int_gt_id;
			$array_goods_data['gb_id'] = $array_related_brand[rand(0,count($array_related_brand)-1)];
			$array_goods_data['g_on_sale'] = rand(0,1);
			$array_goods_data['g_status'] = rand(0,1);
			$array_goods_data['g_sn'] = $int_gt_id . $array_goods_data['gb_id'] . date('YmdHis') . rand(100,1000000);
			$array_goods_data['g_off_sale_time'] = "0000-00-00 00:00:00";
			$array_goods_data['g_on_sale_time'] = "0000-00-00 00:00:00";
			$array_goods_data['g_new'] = rand(0,1);
			$array_goods_data['g_hot'] = rand(0,1);
			//echo "111";
			if(!$int_goods_id = M("goods",C('DB_PREFIX'),'DB_CUSTOM')->add($array_goods_data)){
				echo M("goods",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
				exit;
			}
			
			//print_r($array_goods_data);exit;
			//插入商品详细信息数据
			$array_goodsinfo_data = array();
			$array_goodsinfo_data["g_id"] = $int_goods_id;
			$array_goodsinfo_data["g_name"] = "商品名称" . $array_goods_data['g_sn'] . "[随机标题]";
			$array_goodsinfo_data["g_price"] = $float_goods_price;
			$array_goodsinfo_data["g_stock"] = $int_goods_id;
			$array_goodsinfo_data["g_desc"] = "this is goods desc!";
			$array_goodsinfo_data["g_picture"] = "upload/goods/images/2012/12/28/10000-" . rand(1,10) . ".png";
			$array_goodsinfo_data["g_no_stock"] = 0;
			
			//产生货品信息，并存入数据库
			//获取所有的属性ID，并取出随机个数，组合生成SKU
			if(1==rand(0,4)){
				//单规格商品，将商品SKU信息插入pdt表
				$array_product_info = array();
				$int_pdt_sale_price = rand(20,1000);
				$int_pdt_stock = rand(50,20000);
				$array_product_info["g_id"] = $int_goods_id;
				$array_product_info["g_sn"] = $array_goods_data['g_sn'];
				$array_product_info["pdt_sn"] = $array_goods_data['g_sn'];
				$array_product_info["pdt_sale_price"] = $int_pdt_sale_price;
				$array_product_info["pdt_cost_price"] = $int_pdt_sale_price - $int_pdt_sale_price/2;
				$array_product_info["pdt_market_price"] = $int_pdt_sale_price + $int_pdt_sale_price/2;
				$array_product_info["pdt_weight"] = rand(100,20000);
				$array_product_info["pdt_stock"] = $int_pdt_stock;
				$array_product_info["pdt_status"] = 1;
				$array_product_info["pdt_place"] = "PDT-PLACE" . rand(1,99999);
				//$array_product_info["pdt_red_num"] = 0;
				$array_product_info["pdt_memo"] = "PDT-MEMO" . $int_goods_id;
				//echo "333";
				if(!$int_pdt_id = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->add($array_product_info)){
					echo M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
					exit;
				}
			}else{
				//多个规格，随机抽取两个规格进行组合
				$int_pdt_stock = 0;
				$array_spec_id = array_rand($array_specinfo,2);
				//var_dump($array_spec_id);
				$int_spec_1 = $array_spec_id[0];
				$int_spec_2 = $array_spec_id[1];
				//var_dump($array_spec_value);exit;
				$array_spec_1 = $array_spec_value[$int_spec_1];
				$array_spec_2 = $array_spec_value[$int_spec_2];
				if(empty($array_spec_1) || empty($array_spec_2)){
					continue;
				}
				//echo count($array_spec_1);echo count($array_spec_2);exit;
				for($gs1=0;$gs1 < count($array_spec_1);$gs1++){
					for($gs2=0;$gs2 < count($array_spec_2);$gs2++){
						//echo 222;exit;
						$array_product_info = array();
						$int_pdt_sale_price = rand(20,1000);
						$array_product_info["g_id"] = $int_goods_id;
						$array_product_info["g_sn"] = $array_goods_data['g_sn'];
						$array_product_info["pdt_sn"] = $array_goods_data['g_sn'] . "-" . $int_spec_1 . $array_spec_1[$gs1] . $int_spec_2 . $array_spec_2[$gs2];
						$array_product_info["pdt_sale_price"] = $int_pdt_sale_price;
						$array_product_info["pdt_cost_price"] = $int_pdt_sale_price - $int_pdt_sale_price/2;
						$array_product_info["pdt_market_price"] = $int_pdt_sale_price + $int_pdt_sale_price/2;
						$array_product_info["pdt_weight"] = rand(100,20000);
						$array_product_info["pdt_stock"] = rand(50,20000);
						$array_product_info["pdt_status"] = 1;
						$array_product_info["pdt_place"] = "PDT-PLACE" . $int_goods_id;
						//$array_product_info["pdt_red_num"] = 0;
						$array_product_info["pdt_memo"] = "PDT-MEMO" . $int_goods_id . $array_product_info["pdt_sn"];
						$int_pdt_stock += $array_product_info["pdt_stock"];
						//var_dump($array_product_info);exit;
						//echo "888";
						if(!$int_pdt_id = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->add($array_product_info)){
							echo  M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
							exit;
						}
						//插入SKU关联关系
						$array_sku_relate_1 = array('gs_id'=>$array_spec_1,'gsd_id'=>$array_spec_1[$gs1],'pdt_id'=>$int_pdt_id,'gs_is_sale_spec'=>1,'g_id'=>$int_goods_id);
						$array_sku_relate_2 = array('gs_id'=>$array_spec_2,'gsd_id'=>$array_spec_2[$gs2],'pdt_id'=>$int_pdt_id,'gs_is_sale_spec'=>1,'g_id'=>$int_goods_id);
						//echo "444";
						if(!$t1 = M("related_goods_spec",C('DB_PREFIX'),'DB_CUSTOM')->add($array_sku_relate_1)){
							echo  M("related_goods_spec",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
							exit;
						}
						//echo "777";
						if(!$t2 = M("related_goods_spec",C('DB_PREFIX'),'DB_CUSTOM')->add($array_sku_relate_2)){
							echo  M("related_goods_spec",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
							exit;
						}
					}
				}
			}
			$array_goodsinfo_data['g_price'] = $int_pdt_sale_price;
			$array_goodsinfo_data['g_stock'] = $int_pdt_stock;
			//echo "555";
			if(!$xx = M("goods_info",C('DB_PREFIX'),'DB_CUSTOM')->add($array_goodsinfo_data)){
				echo  M("goods_info",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
				exit;
			}
			
		}
	}
	
	private function parseArray($array_result = array(),$string_field_name=""){
		$array_return = array();
		foreach($array_result as $val){
			$array_return[] = $val[$string_field_name];
		}
		return $array_return;
	}
	
	/**
	 * 产生商品类型数据，属性及品牌
	 */
	private function createGoodTypeBrandAttrData(){
		//echo "产生商品类型数据，属性及品牌...<br />";
		//将商品类型数据插入商品类型表
		$array_type = $this->array_type_name;
		$arrar_attr_name = $this->arrar_attr_name;
		$array_attr_value = $this->array_attr_value;
		$array_brand_name = $this->array_brand_name;
		$array_types_ids = array();
		foreach($array_type as $key=>$type){
			$array_type_tmp = array();
			$array_type_tmp['gt_name'] = $type;
			$array_type_tmp['gt_link_brand'] = 1;
			$array_type_tmp['gt_has_extend_attr'] = 0;
			$int_goods_type_id = M('goods_type',C('DB_PREFIX'),'DB_CUSTOM')->add($array_type_tmp);
			//将商品属性插入商品属性表
			foreach($arrar_attr_name[$key] as $spec_key => $attr){
				$array_spec_insert = array();
				$array_spec_insert['gs_name'] = $attr;
				//$array_spec_insert['gt_id'] = $int_goods_type_id;//备注，此处数据表缺少关联字段！
				$array_spec_insert['gs_is_sale_spec'] = 1;
				$int_spec_id = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->add($array_spec_insert);
				//将商品属性值插入数据库
				foreach($array_attr_value[$key][$spec_key] as $value_key => $value){
					$array_value_insert = array();
					$array_value_insert['gs_id'] = $int_spec_id;
					$array_value_insert['gsd_value'] = $value;
					M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->add($array_value_insert);
				}
			}
			$array_types_ids[] = $int_goods_type_id;
		}
		//将商品品牌数据插入数据库
		foreach($array_brand_name as $brand){
			$array_brand_insert = array();
			$array_brand_insert['gb_name'] = $brand;
			$int_brand_id = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->add($array_brand_insert);
			//增加品牌类型的关联关系
			foreach($array_types_ids as $type_id){
				if(1 == rand(0,1)){
					M('related_brand_type',C('DB_PREFIX'),'DB_CUSTOM')->add(array('gb_id'=>$int_brand_id,'gt_id'=>$type_id));
				}
			}
		}
		//echo "产生商品类型数据，属性及品牌...完成<br />";
	}
	
	/**
	 * 产生商品分类数据
	 */
	private function cateateGoodsCategory(){
		//echo "商品数据初始化开始...<br />";
		$array_cat_name=array('','图书印象','电子书刊','家居电器','服饰鞋帽','个护化妆','运动健康','汽车用品','电脑办公','手机数码','食品饮料');
		$array_category = array('gc_id'=>1,'gc_parent_id'=>0,'gc_level'=>0,'gc_name'=>'','gc_order'=>1,'gc_is_display'=>1);
		for($i=1;$i<=10;$i++){
			$array_this_cat = $array_category;
			unset($array_this_cat['gc_id']);
			$array_this_cat['gc_name'] = $array_cat_name[$i];
			$array_this_cat['gc_level'] = 0;
			$array_this_cat['gc_order'] = $i;
			$array_this_cat['gc_is_display'] = 1;
			$int_parent_catid1 = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($array_this_cat);
			//插入一级子分类
			for($j=1;$j<rand(1,9);$j++){
				$array_this_cat2 = $array_category;
				unset($array_this_cat2['gc_id']);
				$array_this_cat2['gc_name'] = $array_cat_name[$i] . $i . $j;
				$array_this_cat2['gc_parent_id'] = $int_parent_catid1;
				$array_this_cat2['gc_level'] = 1;
				$array_this_cat2['gc_is_display'] = rand(0,1);
				$array_this_cat2['gc_order'] = $i.$j;
				$int_parent_id2 = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($array_this_cat2);
				for($z=1;$z<rand(1,9);$z++){
					$array_this_cat3 = $array_category;
					unset($array_this_cat3['gc_id']);
					$array_this_cat3['gc_name'] = $array_cat_name[$i] . $i . $j . $z;
					$array_this_cat3['gc_parent_id'] = $int_parent_id2;
					$array_this_cat3['gc_level'] = 2;
					$array_this_cat3['gc_is_display'] = rand(0,1);
					$array_this_cat3['gc_order'] = $i . $j . $z;
					$int_parent_id3 = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($array_this_cat3);
				}
			}
		}
		//echo "商品数据初始化完成...<br />";
		return true;
	}
	
}