<?php

/**
 * 淘宝供销平台铺货记录模型
 *
 * @package Model
 * @stage 7.0
 * @author Mithern <sunguangxu@guanyisoft.com>
 * @date 2013-05-02
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TopDistributorItemsModel extends GyfxModel {
	
	/**
     * 下载淘宝商品铺货记录数据下载
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-05-02
     * @param int $page 下载的页码
     */
	public function download($int_pid,$page = 1,$page_size = 50) {
		$array_params = array();
		//本次下载的页数，默认是第1页，以传入参数为准
		$array_params["page_no"] = $page;
		//每次下载多少条数据，如果需要修改，请在这里修改
		$array_params["page_size"] = $page_size;
		$array_params["product_id"] = $int_pid;
		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();

		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoDistributorItemsGet($array_params);
		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}
		//将下载回来的数据存入数据库
		$array_response_data = $array_result["data"]["fenxiao_distributor_items_get_response"]["records"];
		if(isset($array_response_data["fenxiao_item_record"]) && is_array($array_response_data["fenxiao_item_record"])){
			foreach($array_response_data["fenxiao_item_record"] as $record){
				$array_insert_data = array();
				$array_insert_data["distributor_id"] = $record["distributor_id"];
				$array_insert_data["item_id"] = (isset($record["item_id"]) && is_numeric($record["item_id"]))?$record["item_id"]:0;
				$array_insert_data["product_id"] = $record["product_id"];
				$array_insert_data["created"] = $record["created"];
				$array_insert_data["trade_type"] = $record["trade_type"];
				if(!$this->add($array_insert_data,array(),true)){
					echo "error:" . $record["distributor_id"];
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * 获取总的记录数
	 *
	 * @author Mthern
	 * @date 2013-05-02
	 * @version 1.0
	 */
	public function getPageInfo($int_pid){
		$array_params = array();
		//本次下载的页数，默认是第1页，以传入参数为准
		$array_params["page_no"] = 1;
		//每次下载多少条数据，如果需要修改，请在这里修改
		$array_params["page_size"] = 1;
		$array_params["product_id"] = $int_pid;
		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();

		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoDistributorItemsGet($array_params);
		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}
		
		$array_response_data = $array_result["data"]["fenxiao_distributor_items_get_response"];
		//总页数处理
		if(isset($array_response_data["total_results"]) && is_numeric($array_response_data["total_results"])){
			return  $array_response_data["total_results"];
		}
		//可能存在的异常情况，也可能代码永远都执行不到这里
		return 0;
	}
}