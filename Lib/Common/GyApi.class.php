<?php
/**
 * 分销连接中心化客户信息
 * @package Common
 * @subpackage GyApi
 * @author Terry 
 * @since 7.0
 * @version 1.0
 * @date 2013-04-15
 */
class GyApi{
    private $str_api_url;        //api地址
    private $str_api_key;        //对接api的appkey
    public  $str_api_secret;     //店铺代码
    
    function __construct() {
        $sasa = C("TMPL_PARSE_STRING");
        $this->str_api_url = $sasa['__FXCENTER__']."/Api/Index/index";
        $this->str_api_key = C('SAAS_KEY');
        $this->str_api_secret = C('SAAS_SECRET');
    }
    
    /**
     * 
     * 请求api的出口方法
     * @param string $str_method
     * @param string $ary_param
     */
    protected function requestApi($method, $ary_param=array()) {
        $ary_param['app_key']   = $this->str_api_key;
        $ary_param['app_secret']    = $this->str_api_secret;
        $ary_param['method'] = $method;
        //echo "<pre>";print_r($this->str_api_url);exit;
        $result = makeRequest($this->str_api_url, $ary_param, 'get');
        //把key全部变为大写
        return $result;
    }
    
    /**
     * 获取模板数据
     * @param array $ary_param 需要的传递参数
     *          method          API请求的方法
     *          app_key         管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret      管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn       此客户的唯一SN号
     *          ti_sn           模板的唯一SN号
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-14
     * @return array 模板数据
     */
    public function getTemplateDownload($ary_param){
        $ary_result = $this->requestApi('saas.templateDownload.get', $ary_param);
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
    
    /**
     * 通知中心化模板下载成功
     * @param array $ary_param          需要的传递参数
     *          method                  API请求的方法
     *          app_key                 管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret              管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn               此客户的唯一SN号
     *          ti_sn                   模板的唯一SN号
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-15
     * @return array 提示信息
     */
    public function templateDownloadCallback($ary_param){
        $ary_result = $this->requestApi('saas.templateDownloadCallback.get', $ary_param);
//        echo "<pre>";print_r($ary_result);exit;
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
    
    /**
     * 获取公告列表
     * @param array $ary_param          需要的传递参数
     *          method                  API请求的方法
     *          app_key                 管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret              管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn               此客户的唯一SN号
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-17
     * @return array 提示信息
     */
    public function getAnnouncementList($ary_param){
        $ary_result = $this->requestApi('saas.announcementList.get', $ary_param);
//        echo "<pre>";print_r($ary_result);exit;
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
    
    /**
     * 获取公告详情
     * @param array $ary_param          需要的传递参数
     *          method                  API请求的方法
     *          app_key                 管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret              管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn               此客户的唯一SN号
     *          ai_id                   公告ID
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-17
     * @return array 提示信息
     */
    public function getAnnouncementDetail($ary_param){
        $ary_result = $this->requestApi('saas.announcementDetail.get', $ary_param);
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
    
    /**
     * 公告已读回执
     * @param array $ary_param          需要的传递参数
     *          method                  API请求的方法
     *          app_key                 管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret              管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn               此客户的唯一SN号
     *          ai_id                   公告ID
     *          username                读取此公告的客户管理员用户名
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-17
     * @return array 提示信息
     */
    public function getAnnouncementReadCallback($ary_param){
        $ary_result = $this->requestApi('saas.announcementReadCallback.get', $ary_param);
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
    
    /**
     * 获取ERP调用分销API的密钥
     * @param array $ary_param          需要的传递参数
     *          method                  API请求的方法
     *          app_key                 管理中心颁发的授权代码，详情可以点击 这里 查看；
     *          app_secret              管理中心颁发的授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          client_sn               此客户的唯一SN号
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-05-28
     * @return array 密钥数据
     */
    public function getErpApiAppSecret($ary_param){
        $ary_result = $this->requestApi('saas.erpApiAppSecret.get', $ary_param);
        if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
    }
       
}