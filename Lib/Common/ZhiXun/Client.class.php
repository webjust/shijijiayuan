<?php
/**
 * Top_Client 实现将 TOP API 的远程调用封装成函数调用的形式。
 * 
 * 使用示例：
 * <code>
 * $top = new Top_Client($service_url, $app_key, $shop_code);
 * $result = $top->userGet(array('nick'=>$nick));
 * if ( $result !== false ) {
 *     echo $result['nick'], "\n";
 * } else {
 *     $response = $top->getLastResponse();
 *     error_log(sprintf("ERROR: %s failed(#%d): %s\n",
 *               $response->getRequest()->getMethod(),
 *               $response->getCode(), $response->getMessage()));
 * }
 * </code>
 *
 * @package top
 * @copyright Copyright (c) 2013, guanyisoft. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Client
{
    protected $service_url;
    protected $app_key;
    protected $shop_code;
    protected $last_response;
    /**
     * 构造函数。如果不传参数，则使用常量作对应参数的默认值，常量名为：
     *  - {@link TOP_SERVICE_URL}
     *  - {@link TOP_APP_KEY}
     *  - {@link TOP_ShopCode}
     * 
     * @param string $service_url TOP 回调 URL
     * @param string $app_key 应用 app key
     * @param string $shopcode 应用 shopcode
     */
    public function __construct($service_url, $app_key, $shop_code)
    {
        $this->service_url = $service_url;
        $this->app_key = $app_key;
        $this->shop_code = $shop_code;
    }
    /**
     * 获得 TOP 回调 URL
     * 
     * @return string
     */
    public function getServiceUrl ()
    {
        return $this->service_url;
    }
    /**
     * 设置 TOP 回调 URL
     * 
     * @param string $service_url
     * @return Top_Client
     */
    public function setServiceUrl($service_url)
    {
        $this->service_url = $service_url;
        return $this;
    }
    /**
     * 获得应用 APP KEY
     * 
     * @return string
     */
    public function getAppKey ()
    {
        return $this->app_key;
    }
    /**
     * 设置应用 APP KEY
     * 
     * @param string $app_key
     * @return Top_Client
     */
    public function setAppKey($app_key)
    {
        $this->app_key = $app_key;
        return $this;
    }
    /**
     * 获得应用 shopcode
     * 
     * @return string
     */
    public function getShopCode()
    {
        return $this->shopcode;
    }
    /**
     * 设置应用 APP KEY
     * 
     * @param string $shopcode
     * @return Top_Client
     */
    public function setShopCode($shop_code)
    {
        $this->shop_code = $shop_code;
        return $this;
    }
    /**
     * 完成 API 调用
     * 
     * @param Top_Request $request 请求对象
     * @return mixed 根据配置中的 return_mode 值返回相应值，{@see Top_Client::setConfig}
     * @throws Top_Exception 在设置 ERRMODE_EXCEPTION 时，异常的错误类型及错误可能原因如下：
     *   - Top_Response::ERRTYPE_API: 请求中缺少必要参数
     *   - Top_Response::ERRTYPE_NETWORK: 网络连接失败
     *   - Top_Response::ERRTYPE_SERVICE 或 Top_Response::ERRTYPE_HTTP: 服务器异常
     *   - Top_Response::ERRTYPE_API: API接口返回错误
     */
    public function request ($request)   //参数是实例实request
    {
    	$this->last_response = $response = $request->getResponse();
    	if($request->check()){
    		$query = $this->getParameters($request);
    		$query['method'] = $request->getMethod();
			$http_response = self::api($this->service_url, $query, 'POST', false);
			$response->setParameters($query);
			$response->setRestUrl($this->service_url);
			$response->setHttpResponse($http_response);
    	}else{
            $response->setErrorInfo("不合法的请求: " . $request->getError());
    	}
		if($response->isError()){
            return false;
        }
        else {
            return $response->get();
        }
    }
    protected function getParameters($request)
    {
        $query = $request->getParameters();
        $query['method'] = $request->getMethod();
        if(preg_match('/^ecerp/i', $query['method'])){
	        foreach ($query as $key=>&$value){
	        	if($key=='condition'){
	        		$value = urlencode($value);
	        	}
	        }
        }
        $query['appkey'] = $this->app_key;
        $query['zhanghao'] = $this->shop_code;
        return $query;
    }
    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL 
     * @param $params 接口参数   array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型    GET|POST
     * @param $multi 图片信息
     * @param $extheaders 扩展的包头信息
     * @return string
     */
    private static function api( $url , $params = array(), $method = 'GET' , $multi = false, $extheaders = array())
    {
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, 3);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                        foreach($multi as $key => $file)
                        {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }
        $response = curl_exec($ci);
        curl_close ($ci);
        return $response;
    }
    /**
     * 获得最后一次接口调用的 response 对象
     * 
     * @return Tom_Top_Response
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
    /**
     * 使用魔术方法自动将接口与函数名关联，完成接口调用。
     * 
     * 在这个函数中，选根据调用的函数名创建相应的 Top_Request 对象，再调用 request 函数
     * 例如：
     * <code>$top->itemGet(array('iid' => $iid, 'nick' => $nick\/\*, ...\*\/));</code>
     * 实际上等价于：
     * <code>
     *  $request = new Top_Request_ItemGet(array('iid' => $iid, 'nick' => $nick\/\*, ...\*\/));
     *  $top->request($request);
     * </code>
     *
     * @param array $args API 调用参数
     * @return Net_Top_Response 请求响应
     */
    public function __call($name, $args)
    {
        $str_api_version = '';
        //根据后台配置的api版本来加载
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        if($ary_erp['API_VERSION'] == '' || !$ary_erp['API_VERSION']){
            $str_api_version = 'top';
        }else {
            $str_api_version = $ary_erp['API_VERSION'];
        }
        if($str_api_version == ''){
            $str_api_version = 'top';
        }
        //echo $name;exit;
    	import('@.Common.'.$str_api_version.'.Request.' . $name);
        
        $class = 'Top_Request_'.ucfirst($name);
        //dump(class_exists($class));exit;
        if ( class_exists($class) ) {
       		return $this->request( new $class(isset($args[0]) ? $args[0] : null) );
        } else {
            throw new Exception("Unknown api {$name}");
        }
    }
}