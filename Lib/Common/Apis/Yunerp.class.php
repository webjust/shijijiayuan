<?php

/**
 * 云ERP API接口
 *
 * @package Common
 * @subpackage Api
 * @version 7.4.5
 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
 * @date 2013-10-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class Yunerp {

    private $app_key = "";
    private $app_secret = "";
    private $session_key = "";
    private $tenant_id = '';
    private $method = '';
    private $url_api = '';

    /**
     * 构造函数
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     */
    public function __construct($ary_token = array()) {
        $config = D('SysConfig')->getCfgByModule('GY_YUNERP');
        $this->tenant_id=$config['TENANTID'];
        $this->app_key=$config['APPKEY'];
        $this->session_key=$config['SESSIONKEY'];
        $this->app_secret=$config['APPSECRET'];
        $this->url_api="http://opentest.guanyierp.com/rest/core";
    }

    /**
     * 发送云ERP API请求
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     */
    public function request($str_method, $ary_data = array()) {
        $this->method=$str_method;
        $ary_parameter['appkey']=$this->app_key;
        $ary_parameter['appsecret']=$this->app_secret;
        $ary_parameter['sessionkey']=$this->session_key;
        $ary_parameter['tenantid']=$this->tenant_id;
        $ary_parameter['method']=$this->method;
        $ary_parameter['sign']=$this->sign($ary_parameter);

        return makeRequest($this->url_api, $ary_parameter, 'POST');
    }
    
    /**
     * 删除已下载订单
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     * @version 7.4.5
     * @param array 请求参数数组
     * @return array 返回结果
     */
    public function tradeaccept($str_method,$sessionId) {
        $parameter['appkey']=$this->app_key;
        $parameter['sessionkey']=$this->session_key;
        $parameter['tenantid']=$this->tenant_id;
        $parameter['method']=$str_method;
        $parameter['sessionid']=$sessionId;
        $sign =$this->sign($parameter);
        $parameter['sign']=$sign;
        $res=makeRequest($this->url_api, $parameter, 'POST');
        return $res;
    }
    
    /**
     * 生存签名
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     * @version 7.4.5
     * @param array 要签名的数组
     * @return array 签名结果字符串
     */
    public function sign($parameter=array()){
        $parameter = $this->argSort($parameter);
        $str_param = $this->app_secret.$this->createLinkstringUrlencode($parameter).$this->app_secret;//把所有参数名和参数值串在一起
        $str_sign = md5($str_param);
        $sign = strtoupper($str_sign);
        return $sign;
    }
    
    /**
     * 对数组排序
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     * @version 7.4.5
     * @param array $para 排序前的数组
     * @return array 排序后的数组
     */
    protected function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    
    /**
     * 把数组所有元素，按照“参数 参数值”的模式用
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-16
     * @version 7.4.5
     * @param array $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    protected function createLinkstringUrlencode($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . $val ;
        }    
        return $arg;
    }
}