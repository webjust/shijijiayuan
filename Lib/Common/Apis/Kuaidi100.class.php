<?php

/**
 * 调用快递100接口获取订单物流跟踪信息
 *
 *
 * @package Common
 * @subpackage Api
 * @stage 7.3
 * @author mithern <sunguangxu@guanyisoft.com>
 * @date 2013-08-08
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class Kuaidi100{

	private $_query_uri = "http://www.kuaidi100.com/query";
	
	public function queryDeliveryTrack($type,$post_id){
		
		//验证是否传入合法的物流公司类型
		if(empty($type) || "" == $type){
			return array("status"=>false,"data"=>array(),"msg"=>"物流公司代码未指定。");
		}
		
		//验证是否传入合法的运单号
		if(empty($post_id) || "" == $post_id){
			return array("status"=>false,"data"=>array(),"msg"=>"运单号未指定。");
		}
		
		//传递参数拼接
		$array_params = array();
		$array_params["type"] = $type;
		$array_params["postid"] = $post_id;
		$array_params["id"] = 1;
		$array_params["valicode"] = "";
		$array_params["valicode"] = "";
		$array_params["temp"] = 0.34500524401664734 + rand(10000,999999)/100000000;
		$request_uri = $this->_query_uri . '?' .  http_build_query($array_params);
		$mixed_result = file_get_contents($request_uri);
		$array_result = json_decode($mixed_result,true);
		if(false === $array_result || NULL === $array_result){
			return array("status"=>false,"data"=>array(),"msg"=>"快递100没有返回有效信息。");
		}
		
		return array("status"=>true,"data"=>$array_result,"msg"=>"success");
	}
	
}