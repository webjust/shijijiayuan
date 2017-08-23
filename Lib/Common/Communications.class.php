<?php
/**
 * 通讯控制基类
 * @copyright Copyright (C) 2011, 上海包孜网络科技有限公司.
 * @license: BSD
 * @author: Luis Pater
 * @date: 2009-07-29
 * $Id$
 */
class Communications {
    protected $_errorInfo = "";
    protected $_requestInfo = "";

    public function __construct() {}

    /**
     * 获得错误出错信息
     *
     * @author Luis Pater
     * @date 2009-08-27
     * @return string 返回错误信息
     */
    public function errorInfo() {
        return $this->_errorInfo;
    }

    /**
     * 获得请求信息
     *
     * @author Luis Pater
     * @date 2009-08-27
     * @return string 返回请求信息
     */
    public function requestInfo() {
        return $this->_requestInfo;
    }

    /**
     * 获得主机的IP地址
     *
     * @author Luis Pater
     * @date 2009-07-29
     * @param string 主机名称
     * @return string 主机名称对应的IP
     */
    private function getIpByHostName($str_host) {
        /*
        if (N("Config")->CACHE_TYPE=="memcache") {
            if ($res_memcache = memcache_pconnect(N("Config")->MC_HOST, N("Config")->MC_PORT)) {
                if ($str_ip = memcache_get($res_memcache, $str_host)) {
                    return $str_ip;
                }
                else {
                    $str_ip = gethostbyname($str_host);
                    memcache_set($res_memcache, $str_host, $str_ip, 0, 3600);
                    return $str_ip;
                }
            }
        }
        */
        return gethostbyname($str_host);
    }

    /**
     * Http请求访问
     *
     * @author Luis Pater
     * @date 2009-08-27
     * @param string 目标URL
     * @param array 请求信息
     * @return mixed 如果成功则返回对应的信息，如果失败则返回false
     */
    private function httpRequest($str_url, $array_request, $array_settings, $bln_is_post, $bln_need_header = true) {
        if (!isset($array_settings[CURLOPT_HTTPHEADER])) {
            $array_settings[CURLOPT_HTTPHEADER] = array();
        }

        $array_params = array();
        $bln_post_multipart = false;
        foreach ($array_request as $str_key=>$str_value) {
            if($str_value[0]=="@") {
                $bln_post_multipart = true;
                break;
            }
            $array_params[] = $str_key."=".rawurlencode($str_value);
        }

        $this->_requestInfo = array( "url"=>$str_url, "request"=>implode("&", $array_params) );
        $obj_ch = curl_init();
        $array_parsed_url = parse_url($str_url);
        if ($str_ip = $this->getIpByHostName($array_parsed_url["host"])) {
            //使用缓存
            $str_prefix = $array_parsed_url["scheme"]."://".$array_parsed_url["host"];
            $str_url = $array_parsed_url["scheme"]."://".$str_ip.substr($str_url, strpos($str_url, $str_prefix)+strlen($str_prefix), strlen($str_url));
            curl_setopt($obj_ch, CURLOPT_URL, $str_url);
            curl_setopt($obj_ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($obj_ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            if (!isset($array_settings[CURLOPT_HTTPHEADER]["Host"])) {
                $array_settings[CURLOPT_HTTPHEADER]["Host"] = $array_parsed_url["host"];
            }
        }
        else {
            // 未使用缓存机制
            curl_setopt($obj_ch, CURLOPT_URL, $str_url);
        }
        if (!isset($array_settings[CURLOPT_HTTPHEADER]["Expect"])) {
            $array_settings[CURLOPT_HTTPHEADER]["Expect"] = "";
        }

        if($bln_is_post) {
            curl_setopt($obj_ch, CURLOPT_POST, $bln_is_post);
            if ($bln_post_multipart) {
                curl_setopt($obj_ch, CURLOPT_POSTFIELDS, $array_request);
            }
            else {
                curl_setopt($obj_ch, CURLOPT_POSTFIELDS, implode("&", $array_params));
            }
        }

        curl_setopt($obj_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($obj_ch, CURLOPT_HEADER, $bln_need_header);

        $array_httpheader = $array_settings[CURLOPT_HTTPHEADER];
        $array_httpheader_output = array();
        foreach ($array_httpheader as $str_key=>$str_value) {
            $array_httpheader_output[] = $str_key.": ".$str_value;
        }
        $array_settings[CURLOPT_HTTPHEADER] = $array_httpheader_output;

        if (count($array_settings)>0) {
            curl_setopt_array($obj_ch, $array_settings);
        }

        $str_result = curl_exec($obj_ch);
        if (curl_errno($obj_ch)) {
            $str_result = false;
            $this->_errorInfo = curl_error($obj_ch);
        }
        else {
            $this->_requestInfo["result"] = $str_result;
        }
        curl_close($obj_ch);
        if ($bln_need_header) {
            return @http_parse_message($str_result);
        }
        return $str_result;
    }

    public function httpPostRequest($str_url, $array_request = array(), $array_settings = array(), $bln_need_header = false) {
        return $this->httpRequest($str_url, $array_request, $array_settings, true, $bln_need_header);
    }

    public function httpGetRequest($str_url, $array_request = array(), $array_settings = array(), $bln_need_header = false) {
        return $this->httpRequest($str_url, $array_request, $array_settings, false, $bln_need_header);
    }

}
