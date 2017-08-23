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
class TopFenxiaoCooperationModel extends GyfxModel {

	/**
	 *  下载淘宝分销合作关系数据
	 * @prams $int_page_no 下载的页数，默认第一页
	 * 
	 * @author Mthern
	 * @date 2013-05-02
	 * @version 1.0
	 */
	public function download($int_page_no =1,$int_page_size=30){
		$array_params = array();
		//本次下载的页数，默认是第1页，以传入参数为准
		$array_params["page_no"] = $int_page_no;
		//每次下载多少条数据，如果需要修改，请在这里修改
		$array_params["page_size"] = $int_page_size;
		$array_params["format"] = 'xml';
		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();
		
		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoCooperationGet($array_params);
		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}
		
		//将获取到的数据存入数据库
		$array_cooperation  = $array_result["data"]["cooperations"]["cooperation"];
		if(!is_array($array_cooperation) || empty($array_cooperation)){
			return array("status"=>true,'code'=>'Does not meet the conditions of data','message'=>'没有符合条件的数据');
		}
		
		//将获取到的数据存入数据库
		$cooperate_ids = '';
		foreach($array_cooperation as $cooperation){
			$array_insert = array();
			$array_insert["cooperate_id"] = $cooperation["cooperate_id"];
			$array_insert["distributor_id"] = $cooperation["distributor_id"];
			$array_insert["distributor_nick"] = $cooperation["distributor_nick"];
			$array_insert["product_line"] = trim(trim($cooperation["product_line"]),',');
			$array_insert["product_line_name"] = $cooperation["product_line_name"]["string"];
			$array_insert["grade_id"] = isset($cooperation["grade_id"])?$cooperation["grade_id"]:0;
			$array_insert["trade_type"] = $cooperation["trade_type"];
			$array_insert["auth_payway"] = $cooperation["auth_payway"]["string"];
			$array_insert["supplier_id"] = $cooperation["supplier_id"];
			$array_insert["supplier_nick"] = $cooperation["supplier_nick"];
			$array_insert["start_date"] = $cooperation["start_date"];
			$array_insert["end_date"] = (isset($cooperation["end_date"]) && '' != $cooperation["end_date"])?$cooperation["end_date"]:'0000-00-00 00:00:00';
			$array_insert["status"] = $cooperation["status"];
			$array_insert["product_line_name"] = trim(trim($cooperation["product_line_name"]["string"]),',');
			if(false === $this->add($array_insert,array(),true)){
				$cooperate_ids .= $cooperation["cooperate_id"];
				continue;
			}
		}
		
		$string_msg = "本次所有数据下载成功";
		if("" != $cooperate_ids){
			$string_msg = "以下合作关系信息保存失败（合作关系ID）：" . $str_error_product;
		}
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
		$array_params["page_size"] = 2;
		$array_params["format"] = 'xml';
		//实例化供销平台对接API操作类
		$obj_fenxiao_api = new Fenxiao();
		
		//获取供销平台数据
		$array_result = $obj_fenxiao_api->taobaoFenxiaoCooperationGet($array_params);
		//对返回值进行判断
		if(false === $array_result["status"]){
			//如果调用API出错，则返回需要的数据
			return $array_result;
		}
		$array_response_data = $array_result["data"];
		//总页数处理
		if(isset($array_response_data["total_results"]) && is_numeric($array_response_data["total_results"])){
			return  $array_response_data["total_results"];
		}
		//可能存在的异常情况，也可能代码永远都执行不到这里
		return 0;
	}
}