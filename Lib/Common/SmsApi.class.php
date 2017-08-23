<?php

/**
 * 短信接口
 *
 * @package Common
 * @subpackage Api
 * @version 7.6.1
 * @author Wangguibin <Wangguibin@guanyisoft.com>
 * @date 2014-08-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SmsApi {

    private $userid = "";
	private $password = "";
    private $method = '';
    private $url_api = '';

    /**
     * 构造函数
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-16
     */
    public function __construct($ary_token = array()) {
        $config = D('SysConfig')->getSmsCfg();
        $this->userid = $config['GY_SMS_NAME'];
        $this->password = $config['GY_SMS_PASS'];
        $this->url_api = "http://sms.guanyisoft.com/openapi/rest/core";
	    //$this->url_api = "http://open.guanyierp.com/rest/core";
    }

    /**
     * 发送云ERP API请求
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2014-08-04
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     */
    public function request($str_method, $ary_data = array()) {
        $this->method = $str_method;
		$parameter = $ary_data;
        $parameter['method'] = $str_method;
        $parameter['timestamp'] = time();
        $parameter['userid'] = $this->userid;
		$parameter['password'] = $this->password;
        $parameter['password'] = md5($parameter['userid'].$parameter['timestamp'].md5($parameter['password']));
		$res = makeRequestUtf8($this->url_api, $parameter, 'POST');
		return json_decode($res);
    }
    
    /**
     * 删除已下载订单
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2014-08-05
     * @version 7.6.1
     * @param array 请求参数数组
     * @return array 返回结果
     */
    public function smsSend($ary_data = array()) {
        $parameter['mobile'] = trim($ary_data['mobile']);
		$parameter['content'] = trim($ary_data['content']);
		$parameter['type'] = 1;//1:通知类,2:营销类
        $res = $this->request('gy.sms.message.send', $parameter, 'POST');
		$return_res = array();
		if($res->success == 1){
			$return_res['code'] = '200';
			$return_res['msg'] = '发送成功';
		}else{
			$return_res['code'] = '发送失败';
			$return_res['msg'] = $res->error->message;
		}
        return $return_res;
    }
	
    /**
     * 返回短信总余额
     * @author Wangguibin <Wangguibin@guanyisoft.com>
     * @date 2014-08-05
     * @version 7.6.1
     * @param 
     * @return array 返回结果商品
     */
    public function getSmsBalance() {
        $res = $this->request('gy.sms.balance.get', $parameter, 'POST');
		$balance = object2array($res->balance);
		return $balance['qty'];
    }	
	
}