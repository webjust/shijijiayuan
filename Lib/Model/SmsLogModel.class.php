<?php
/**
 * 短信日志模型
 * @author wangguibin <wangguibin@guanyisoft.com>
*/
class SmsLogModel extends GyfxModel {

	 /**
	 * 构造方法
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
    */
    public function __construct() {
        parent::__construct();
    }
	
	/**
	 * 验证短信码
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function getCount($where=array()) {
		$total =$this->where($where)->count();
		return $total;
	}
	
	/**
	 * 插入短信日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function insert($data=array()) {
		$res_int=$this->data($data)->add();
		return $res_int;
	}

	/**
	 * 插入短信日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function addSms($ary_data=array()) {
		$ary_data['create_time'] = date('Y-m-d H:i:s');
		$ary_data['update_time'] = date('Y-m-d H:i:s');
		$ary_data['status'] = 1;
		$res_int=$this->data($ary_data)->add();
		return $res_int;
	}
	/**
	 * 插入短信日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function updateSms($ary_where,$ary_data=array()) {
		$ary_data['update_time'] = date('Y-m-d H:i:s');
		$res_int=$this->data($ary_data)->where($ary_where)->save();
		return $res_int;
	}	
	/**
	 * 检测手机号
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function checkMobile($where=array()) {
		$res_int=D('Members')->where($where)->count();
		return $res_int;
	}
	
	/**
	 * 获取短信日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	*/
	public function getSmsInfo($where=array()) {
		$res=D('SmsLog')->where($where)->order('create_time desc')->find();
		return $res;
	}
}