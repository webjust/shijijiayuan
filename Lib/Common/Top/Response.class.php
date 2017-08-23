<?php
/**
 * 接口响应类
 *
 * 使用示例：
 * <code>
 * $response = $top->getLastResponse();
 * if ( $response->isError() ) {
 *     error_log(sprintf("ERROR: %s failed(#%d): %s\n",
 *               $response->getRequest()->getMethod(),
 *               $response->getCode(), $response->getMessage()));
 * } else {
 *     $result = $response->get();
 * }
 * </code>
 * 
 * @package top
 * @copyright Copyright (c) 2013, guanyisoft. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Response
{
    protected $rest_url;        /* 请求时的链接 */
    protected $parameters;      /* 请求时的参数 */
    protected $request;         /* 此次请求的 Top_Request 对象 */
    protected $http_response;   /* Util_Http_Response */
    protected $result;          /* 解析结果 */
    protected $error_info;      /* 错误信息 */
    /**
     * 构造函数
     * @param Top_Request $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }
    /**
     * 获得请求对象
     * 
     * @return Top_Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * 设置接口实际调用的 url
     * 
     * @param string $url
     * @return Top_Response
     */
    public function setRestUrl($url)
    {
        $this->rest_url = $url;
        return $this;
    }
    /**
     * 获得接口实际调用的 url
     * 
     * @return string 
     */
    public function getRestUrl()
    {
        return $this->rest_url;
    }
    /**
     * 设置接口实际使用参数
     * 
     * @param array $parameters
     * @return Top_Response
     */
    public function setParameters($parameters) 
    {
        $this->parameters = $parameters;
        return $this;
    }
    /**
     * 获得接口实际使用的参数
     * 
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    /**
     * 设置并解析接口调用 http 响应
     * 
     * @param Util_Http_Response $http_response
     */
    public function setHttpResponse($http_response)
    {
        $this->error_info = array();
        $this->result = array();
        $this->http_response = $http_response;
		if($http_response===false){
			$msg = date('Y-m-d H:i:s') .'    ' . $this->parameters['method'].'    ' . '连接api接口失败[' . $this->rest_url . ']' . "\r\n";
			self::log($msg);
			$this->error_info = '链接api接口失败,请检查接口';
        	return false;
        }
        $this->preParse();
        $this->parseXML();
        $this->postParse();
    }
    /**
     * 获得 http 响应对象
     * 
     * @return Util_Http_Response
     */
    public function getHttpResponse()
    {
        return $this->http_response;
    }
    /**
     * 请求响应预处理函数
     * 
     * @access protected
     */
    protected function preParse()
    {
    }
    /**
     * 解析响应后的回调函数 
     * 
     * @access protected
     */
    protected function postParse()
    {
    }
    /**
     * xml 格式响应的解析函数
     * 
     * @access protected
     */
    protected function parseXML ()
    {
        $content = $this->http_response;
        if ( substr($content, 0, 5) == '<?xml' ) {
            $xml = simplexml_load_string($content,'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            $this->result = self::xmlobjToArray($xml);
			if(empty($this->result)){
				$msg = date('Y-m-d H:i:s') .'    ' . $this->parameters['method'].'    ' . $content . "\r\n";
				self::log($msg);
				$this->error_info = 'api出现未知异常,请查看api日志';
				$this->result = array();
			}elseif(isset($this->result['error'])){
				$msg = date('Y-m-d H:i:s') .'    ' . $this->parameters['method'].'    ' . $this->result['error'] . "\r\n";
				self::log($msg);
				$this->error_info = $this->result['error'];
				$this->result = array();
			}elseif(isset($this->result['total_results']) && $this->result['total_results']!=intval($this->result['total_results'])){
				$msg = date('Y-m-d H:i:s') .'    ' . $this->parameters['method'].'    ' . $this->result['total_results'] . "\r\n";
				self::log($msg);
				$this->error_info = $this->result['total_results'];
				$this->result = array();
			}
        }
        else {
            $this->error_info = 'api出现未知异常,请查看api日志';
        }
    }
    protected static function xmlobjToArray($obj)
    {
        if ( is_object($obj) ) {
            $obj = get_object_vars($obj);
            $list = false;
            if ( isset($obj['@attributes']['list']) ) {
                unset($obj['@attributes']);
                $list = true;
            }
            foreach ( $obj as $key => $value ) {
		    	unset($obj[$key]);
		    	$key = strtolower($key);
                $res = self::xmlobjToArray($value);
                if ( $list ) {
                    if ( isset($res[0]) ) {
                        $obj[$key] = $res;
                    }
                    else {
                        $obj[$key] = array($res);
                    }
                }
                else {
                    $obj[$key] = (is_array($res)&&empty($res)) ? '' : $res;
                }
            }
            return $obj;
        } elseif ( is_array($obj) ) {
            return array_map(array(__CLASS__, 'xmlobjToArray'), $obj);
        } else {
            return $obj;
        }
    }
	/**
	 * 记api错误日志
	 */
	private static function log($msg){
		$tom_env = 'dev';
		if ($tom_env == 'dev') {
			$log_dir = APP_PATH . 'Runtime/Apilog/';
			if(!file_exists($log_dir)){
				mkdir($log_dir,0700);
			}
			$log_file = $log_dir . date('Ym') . '.log';
			$fp = fopen($log_file, 'a+');
			fwrite($fp, $msg);
			fclose($fp);
		}
	}
    /**
     * 判断请求是否出现错误
     *
     * @return boolean
     */
    public function isError()
    {
        return !empty($this->error_info);
    }
    /**
     * 设置出错信息。
     * 出错信息值为数组，按下标依次为：
     *  0. 错误类型，ERRTYPE_API, ERRTYPE_NETWORK, ERRTYPE_SERVICE, ERRTYPE_HTTP 中一种
     *  1. 错误代码，整数值，可以是接口返回的 code 值或预定义错误代码
     *  2. 错误信息
     * 
     * @param array $error_info
     * @return Top_Response
     */
    public function setErrorInfo($error_info)
    {
        $this->error_info = $error_info;
        return $this;
    }
    /**
     * 获得出错信息
     * 
     * @return array
     */
    public function getErrorInfo()
    {
        return $this->error_info;
    }
    /**
     * 获得接口返回结果
     * 
     * @param string $name 字段名
     * @param mixed $default 默认值
     * @return mixed 如果未指定字段，返回接口全部返回结果; 否则返回结果中指定字段
     */
    public function get($name=null, $default=null)
    {
        if ( null === $name ) {
            return $this->result;
        }
        return isset($this->result[$name]) ? $this->result[$name] : $default;
    }
}