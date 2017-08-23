<?php

/**
 * 淘宝分销合作关系模型
 *
 * @package Model
 * @version 7.1
 * @author Mithern <sunguangxu@guanyisoft.com>
 * @date 2013-05-02
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TopGoodsInfoModel extends GyfxModel {

	/**
	 *  下载淘宝分销商品资料数据
	 * @prams $int_page_no 下载的页数，默认第一页
	 *
	 * @author Mthern
	 * @date 2013-05-02
	 * @version 1.0
	 */
	public function download($int_page_no=1,$int_page_size=30){
		$array_params = array();
		//本次下载的页数，默认是第1页，以传入参数为准
		$array_params["page_no"] = $int_page_no;
		//每次下载多少条数据，如果需要修改，请在这里修改
		$array_params["page_size"] = $int_page_size;

		//需要下载的数据总页数
		$int_total_page = 0;

		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();

		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoProductsGet($array_params);

		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}

		$array_response_data = $array_result["data"]["fenxiao_products_get_response"];

		//判断是否有符合条件的数据
		if(!isset($array_response_data["products"]["fenxiao_product"]) || empty($array_response_data["products"]["fenxiao_product"])){
			return array("status"=>true,"code"=>"Does not meet the conditions of data","message"=>"没有符合条件的数据");
		}
		//总页数处理
		if(isset($array_response_data["total_results"]) && is_numeric($array_response_data["total_results"])){
			$int_total_result = $array_response_data["total_results"];
			$int_total_page = ceil($int_total_result/$array_params["page_size"]);
		}

		//将下载回来的数据存入数据库
		$array_product = $array_response_data["products"]["fenxiao_product"];
		//定义一个变量用于保存下载失败的产品商家编码
		$str_error_product = "";
		foreach($array_product as $product){
			$array_data = array();
			$array_data["pid"] = $product['pid'];
			$array_data["trade_type"] = $product['trade_type'];
			$array_data["is_authz"] = $product['is_authz'];
			$array_data["name"] = $product['name'];
			$array_data["outer_id"] = $product['outer_id'];
			$array_data["desc_path"] = $product['desc_path'];
			$array_data["items_count"] = $product['items_count'];
			$array_data["orders_count"] = $product['orders_count'];
			$array_data["standard_price"] = $product['standard_price'];
			$array_data["dealer_cost_price"] = $product['dealer_cost_price'];
			$array_data["upshelf_time"] = $product['upshelf_time'];
			//将数据写入数据库，通过replace into的方式插入数据，将主键判断交给mysql系统完成
			if(false === $this->add($array_data,array(),true)){
				//将下载保存失败的商家编码存入字符串
				$str_error_product .= $array_data["name"] . ":" . $array_data["outer_id"] . ";";
				continue;
			}

			//保存SKU数据，首先判断此产品是否有SKU

			if(!isset($product["skus"]["fenxiao_sku"]) || empty($product["skus"]["fenxiao_sku"])){
				//如果商品没有SKU，则跳过保存SKU数据
				continue;
			}

			foreach($product["skus"]["fenxiao_sku"] as $sku){
				$array_sku = array();
				$array_sku["id"] = $sku["id"];
				$array_sku["pid"] = $product['pid'];
				$array_sku["standard_price"] = $sku["standard_price"];
				$array_sku["properties"] = $sku["properties"];
				$array_sku["cost_price"] = $sku["cost_price"];
				$array_sku["dealer_cost_price"] = $sku["dealer_cost_price"];
				$array_sku["scitem_id"] = (isset($sku["scitem_id"]) && "" != $sku["scitem_id"])?$sku["scitem_id"]:"";
				$array_sku["name"] = $sku["name"];
				$array_sku["outer_id"] = (isset($sku["outer_id"]) && "" != $sku["outer_id"])?$sku["outer_id"]:"";
				$array_sku["tgs_update_time"] = date("Y-m-d H:i:s");
				if(false === D("TopGoodsSku")->add($array_sku,array(),true)){
					$str_error_product .= $array_sku["scitem_id"];
					continue;
				}
			}
		}

		//对整体下载情况进行处理
		$string_msg = "本次所有商品下载成功";
		if("" != $str_error_product){
			$string_msg = "以下产品下载保存失败（商品名称:商品编码;）：" . $str_error_product;
		}

		//数据返回
		return array("status"=>true,"code"=>"success","message"=>$string_msg);
	}

	/**
	 * 获取总的记录数
	 * @prams $int_page_no 下载的页数，默认第一页
	 *
	 * @author Mthern
	 * @date 2013-05-02
	 * @version 1.0
	 */
	public function getPageInfo(){
		$array_params = array();
		//本次下载的页数，默认是第1页，以传入参数为准
		$array_params["page_no"] = 1;
		//每次下载多少条数据，如果需要修改，请在这里修改
		$array_params["page_size"] = 1;
		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();

		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoProductsGet($array_params);
		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}

		$array_response_data = $array_result["data"]["fenxiao_products_get_response"];
		//总页数处理
		if(isset($array_response_data["total_results"]) && is_numeric($array_response_data["total_results"])){
			return  $array_response_data["total_results"];
		}
		//可能存在的异常情况，也可能代码永远都执行不到这里
		return 0;
	}


    /**
     * 根据out_id及商家编码，跟新供销平台上的商品信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-07
     * @param string $pid 产品ID
     * @param array $ary_data 商品数据
     */
    public function update($pid,$ary_data){
        //实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();
        //pid为必填项
        $ary_data['pid'] = $pid;
        $array_result = $obj_fenxiao_api->taobaoFenxiaoProductUpdate($ary_data);
        return $array_result['status'];
    }
}