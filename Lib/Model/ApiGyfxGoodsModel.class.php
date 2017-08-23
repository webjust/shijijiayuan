<?php

/**
 * 商品相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Mithern
 * @date 2013-07-10
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiGyfxGoodsModel extends GyfxModel {

	//商品资料可能要操作的表
	private $obj_goods;
	private $obj_goods_info;
	private $obj_goods_products;
	private $obj_goods_category;
	private $obj_goods_brand;
	private $obj_goods_spec;
	private $obj_goods_spec_detail;
	private $obj_related_goods_spec;
	private $obj_related_goods_category;
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		$this->obj_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_category = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_brand = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_spec = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_goods_spec_detail = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_related_goods_spec = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM');
		$this->obj_related_goods_category = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM');
	}
	
	/**
	 * 根据商家编码获取商品数据
	 * @params $string_outer_id 外部来源商品ID - 商家编码  字符串  必须参数
	 * @params $string_fields 要获取的字段  以逗号分隔
	 *
	 *
	 * @author Mithern
	 * @date 2013-07-10 
	 * @version 1.0
	 */
	public function getGoodsDetailByOuterId($array_outer_ids,$array_fields){
		$array_cond = array("g_sn"=>array("IN",$array_outer_ids));
		$array_goods_id = $this->obj_goods->where($array_cond)->getField("g_id",true);
		return $this->getGoodsDetailByGid($array_goods_id,$array_fields);
	}
	
	/**
	 * 根据管易商品ID获取商品数据
	 */
	public function getGoodsDetailByGid($array_goods_id,$array_fields){
		if(!in_array("num_iid",$array_fields)){
			$array_fields[] = "num_iid";
		}
		$array_cond = array("g_id"=>array("IN",$array_goods_id));
		//获取商品的基本信息和详细信息
		$str_goods_fields = $this->getGoodsField($array_fields);
		$array_goods = $this->obj_goods->field($str_goods_fields)->where($array_cond)->select();
		//获取商品资料的详细信息
		$string_goods_info_fields = $this->getGoodsInfoField($array_fields);
		if("" != $string_goods_info_fields){
			$string_goods_info_fields = $string_goods_info_fields.',g_discount';
			$array_goods_info = $this->obj_goods_info->field($string_goods_info_fields)->where($array_cond)->select();
			//将goods 和 goods_info 数据合并
			foreach($array_goods as $key => $val){
				htmlspecialchars($array_goods[$key]['desc']);
				foreach($array_goods_info as $v){
					if($val["num_iid"] == $v["num_iid"]){
						$array_goods[$key] = array_merge($val,$v);
						if($array_goods[$key]['price']>0){
							if($v['g_discount']>0 && $v['g_discount']<1){
								$array_goods[$key]['price'] = sprintf("%2f",$array_goods[$key]['price']*$v['g_discount']);
							}	
						}
						unset($array_goods[$key]['g_discount']);
					}
				}
				if(isset($array_goods[$key]['desc'])){
					htmlspecialchars($array_goods[$key]['desc']);
				}
			}
		}
		//TODO: 如果设置了要获取商品属性图：prop_imgs
		
		//TODD: 如果设置了要获取类目ID:
		
		//TODO: 商品明细页地址:detail_url

		//SKU数据获取
		if(in_array('skus',$array_fields)){
			$string_sku_field = $this->getGoodsProductsField();
			$array_products = $this->obj_goods_products->field($string_sku_field)->where($array_cond)->select();
			//获取所有的销售属性，变成属性ID=>属性值的形式
			$array_spec_info = $this->obj_goods_spec->where(array("gs_is_sale_spec"=>1))->getField("gs_id,gs_name",true);
			foreach($array_products as $pdt){
				//获取此SKU的规格数据
				$pdt["properties_detail"] = "";
				$pdt["properties_name"] = "";
				//验证此规格是否有关联的销售属性
				$array_spec_cond = array("g_id"=>$pdt["num_iid"],"pdt_id"=>$pdt["sku_id"],"gs_is_sale_spec"=>1);
				$array_tmp_result = $this->obj_related_goods_spec->where($array_spec_cond)->getField("gsd_id,gs_id,gsd_aliases");
				if(!empty($array_tmp_result)){
					$array_tmp_spec = array();
					$array_tmp_spec_2  = array();
					foreach($array_tmp_result as $vid => $info){
						$array_tmp_1 = array();
						$array_tmp_1[] = $info["gs_id"];
						$array_tmp_1[] = $vid;
						$array_tmp_1[] = $array_spec_info[$info["gs_id"]];
						$array_tmp_1[] = $info["gsd_aliases"];
						$array_tmp_spec[] = implode(':',$array_tmp_1);
						$array_tmp_spec_2[] = implode(':',array($array_tmp_1[2],$array_tmp_1[3]));
						unset($array_tmp_1);
					}
					$pdt["properties_detail"] = implode(';',$array_tmp_spec);
					$pdt["properties_name"] = implode(';',$array_tmp_spec_2);
					foreach($array_goods as $key => $v){
						if($pdt["num_iid"] == $v["num_iid"]){
							if(!isset($array_goods[$key]["skus"]["sku"])){
								$array_goods[$key]["skus"]["sku"] = array();
							}
							$array_goods[$key]["skus"]["sku"][] = $pdt;
						}
					}
				}
				//当前商品为无规格商品
				foreach($array_goods as $key => $v){
					if($pdt["num_iid"] == $v["num_iid"]){
						//将价格，库存等数据更新到商品资料上
						$array_goods[$key]["price"] = $pdt["price"];
						$array_goods[$key]["num"] = $pdt["quantity"];
						$array_goods[$key]["mprice"] = $pdt["mprice"];
						$array_goods[$key]["skuid"] = $pdt["sku_id"];
					}
				}
			}
		}
		//获取商品扩展属性
        if(in_array('itemextraunsaleprop',$array_fields)){
            foreach($array_goods as $key=>$val){
                $int_g_id=$val['num_iid'];
                $result = M("RelatedGoodsSpec rgs")->join("fx_goods_spec gs on rgs.gs_id = gs.gs_id")
                                                   ->join("fx_goods_spec_detail gsd on rgs.gs_id = gsd.gs_id")
                                                   ->field('gs_name,gsd_aliases')
                                                   ->where(array('g_id'=>$int_g_id,'gs.gs_is_sale_spec'=>0))->group('rgs.gs_id')->select();
                foreach($result as $k=>$v){
                    $result[$k] = implode(':',$v);
                }
                $result = implode(';',$result);
                $array_goods[$key]["itemextraunsaleprop"] = $result;
            }
        }
		//对返回的数据格式进行处理
		$return_items = array();
		$return_items["items"]["item"] = array();
		foreach($array_goods as $item){
		//是否上架
			if($item['approve_status'] == '1'){
				$item['approve_status'] = 'onsale';
			}else{
				$item['approve_status'] = 'instock';
			}
			//TODO：此处缺少处理商品主图的业务pic_url
			if($item['pic_url']){
				$item['pic_url'] = $_SESSION['HOST_URL'].str_replace('//', '/', $item['pic_url']);
			}
			//TODO: 如果设置了要获取商品描述图片item_imgs
			if(in_array('item_imgs',$array_fields)){
				$ary_picresult = M('goods_pictures',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$item['num_iid'],'gp_status'=>'1'))->select();
				if(!empty($ary_picresult) && is_array($ary_picresult)){
					$ary_pic = $ary_picresult;
					foreach($ary_pic as $pic){
						$item['item_imgs']['item_img'][] = array(
							'id'=>$pic['gp_id'],
							'posotion'=>$pic['gp_order'],
							'url'=>$_SESSION['HOST_URL'].str_replace('//','/',$pic['gp_picture'])
						);
					}
				}
			}
			$return_items["items"]["item"][] = $item;
		}
		return $return_items;
	}
	
	/**
	 * 商品表字段对应关系
	 *
	 *
	 */
	private function getGoodsField($array_client_fields){
		//定义商品表的字段映射关系
		$array_goods_fileds = array(
			'g_id'=>'num_iid',			
			'g_sn'=>'outer_id',
			'g_on_sale_time'=>'list_time',
			'g_off_sale_time'=>'delist_time',
			'g_create_time'=>'created',
			'g_update_time'=>'modified',
			'g_on_sale'=>'approve_status'
		);
		return $this->parseFieldsMaps($array_goods_fileds,$array_client_fields);
	}
	
	/**
	 * 商品详细信息表字段对应关系
	 */
	private function getGoodsInfoField($array_client_fields){
		$array_goodsinfo_fields = array(
			'g_id'	=> 'num_iid',
			'g_weight'=>'item_weight',
			'g_name' => 'title',
			'g_desc' => 'desc',
			'g_picture' => 'pic_url',
			'g_stock' => 'num',
			'mobile_show' => 'app',
			'g_phone_desc' => 'app_desc',
			'g_price' => 'price'
		);
		return $this->parseFieldsMaps($array_goodsinfo_fields,$array_client_fields);
	}
	
	/**
	 * SKU数据获取
	 */
	private function getGoodsProductsField(){
		$array_products_field = array(
			'pdt_id' => 'sku_id',
			'pdt_sn' => 'outer_id',
			'g_id' => 'num_iid',
			'pdt_stock' => 'quantity',
			'pdt_sale_price' => 'price',
			'pdt_market_price' => 'mprice',
			'pdt_collocation_price'=>'pdt_collocation_price',
			'pdt_weight'=>'item_weight',
			'pdt_create_time' => 'created',
			'pdt_update_time' => 'modified'
		);
		$aray_fetch_field = array();
		foreach($array_products_field as $field_name => $as_name){
			$aray_fetch_field[] = '`' . $field_name . '` as `' . $as_name . '`';
		}
		return implode(',',$aray_fetch_field);
	}
	
	/**
	 * 处理字段映射
	 */
	public function parseFieldsMaps($array_table_fields,$array_client_fields){
		$aray_fetch_field = array();
		foreach($array_table_fields as $field_name => $as_name){
			if(in_array($as_name,$array_client_fields)){
				$aray_fetch_field[] = '`' . $field_name . '` as `' . $as_name . '`';
			}
		}
		if(empty($aray_fetch_field)){
			return "";
		}
		return implode(',',$aray_fetch_field);
	}
    
    
}