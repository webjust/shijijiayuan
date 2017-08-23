<?php
/**
 * API 请求对象
 *
 * 在 API 请求对象中包含了 api 请求的所有的参数信息。每个 TOP API 对应一个
 * Top_Request 的一个子类，子类的类名中包含了接口名信息，例如
 * Top_Request_UserGet 代表接口 'UserGet'，这样就能同 Top_ApiManager
 * 注册的接口定义信息关联。
 * 
 * @abstract
 * @package top
 * @copyright Copyright (c) 2013, guanyisoft. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
abstract class Top_Request
{
    /**
     * {@link Top_ApiManager} 中定义的接口名。
     * 如果未在类定义中指定，则使用类名中下划线('_') 后最后一个单词
     * 
     * @access protected
     * @var string
     */
    protected $api_name;
    protected $parameters = array(); /* API请求设置的参数 */
    protected $api_parameters;   /* api 定义的参数 */
    protected $http_method;      /* API请求的 HTTP 方法 */
    protected $error;            /* 本地参数检查时出错原因 */
    protected $no_check;         /* 是否需要检查参数 */
    protected $response;
    /**
     * 构造函数
     * 
     * @param array $args API 调用参数
     */
    public function __construct( $args = null)
    {
        if ( null === $this->api_name ) {
            $class = get_class($this);
            if ( ($pos = strrpos($class, '_')) > 0 ) {
                $this->api_name = substr($class, $pos+1);
            } else {
                throw new Exception("未定义的 api");
            }
        }
        $this->api_parameters = $this->getMetadata('parameters');
        if ( !empty($args) ) {
            foreach ( $args as $k => $v ) {
                $this->set($k, $v);
            }
        }
    }
    /**
     * 获得 API 定义项的值
     * 
     * @access protected
     * @param string $name 定义项名，如 parameters, fields 等
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getMetadata($name, $default=null)
    {
        $value = Top_ApiManager::get($this->api_name, $name);
        return isset($value) ? $value : $default;
    }
    /**
     * 获得接口访问的 HTTP 方法
     * 
     * @return string 'GET' 或 'POST'
     */
    public function getHttpMethod()
    {
        $method = isset($this->http_method)
            ? $this->http_method
            : $this->getMetadata('http_method', 'GET');
        return strtoupper($method);
    }
    /**
     * 设置接口访问的 HTTP 方法
     * 
     * @param string $method
     * @return Top_Request
     */
    public function setHttpMethod($method)
    {
        $this->http_method = $method;
        return $this;
    }
    /**
     * 设置接口是否不对参数作检查
     * 
     * @param boolean $no_check
     * @return Top_Request
     */
    public function setNoCheck($no_check=true)
    {
        $this->no_check = $no_check;
        return $this;
    }

    public function getNoCheck()
    {
        return $this->no_check;
    }
    /**
     * 获得 TOP API 名字
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->getMetadata('method');
    }
    /**
     * 获得接口定义名
     * 
     * @return string
     */
    public function getApiName()
    {
        return $this->api_name;
    }
    /**
     * 测试接口是否有某参数
     * 
     * @param string $name 参数名
     * @return boolean
     */
    public function has($name) 
    {
        return isset($this->api_parameters['all'][$name]);
    }
    /**
     * 测试参数是否是必须
     * 
     * @param string $name 参数名
     * @return boolean
     */
    public function isRequired($name)
    {
        return isset($this->api_parameters['required'][$name]);
    }
    /**
     * 获得参数值
     * 
     * @param string $name 参数名
     * @return mixed
     */
    public function get($name) 
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }
    /**
     * 设置参数值。
     * 普通参数的值一般是字符串。但是如果接口的参数是带 '.'，则值可以用完整形式设置或使用前一个
     * 单词作参数名，以关联数组形式设置。例如 taobao.item.add 接口有 location.city, 和
     * location.state 两个参数，则可以通过下面两种方式设置值：
     * <code>
     *  $request = new Top_Request_ItemAdd();
     *  // 直接设置
     *  $request->set('location.city', '北京');
     *  $request->set('location.state', '北京');
     *  // 通过关联数组设置
     *  $request->set('location' => array('city'=>'北京','state'=>'北京'));
     * </code>
     * 
     * @param string $name 参数名，接口定义中 parameters 的某个参数
     * @param mixed $val
     * @return boolean 成功设置返回 true; 参数不正确返回 false
     */
    public function set($name, $val) 
    {
        if ( isset($this->api_parameters['all'][$name]) ) {
            $type = $this->api_parameters['all'][$name];
            if ( isset($type['struct']) ) { // struct fields, such as location.city
                if ( is_array($val) ) {
                    foreach ( $val as $k => $v ) {
                        $k = $name.'.'.$k;
                        if ( isset($this->api_parameters['all'][$k]) ) {
                            $this->parameters[$k] = $v;
                        } else { // not such structed fields
                            return false;
                        }
                    }
                } else { // struct fields need an array value
                    return false;
                }
            } else {
                $this->parameters[$name] = $val;
            }
        } else { // no such query fields
            if ( $this->no_check ) {
                $this->parameters[$name] = $val;
            } else {
                return false;
            }
        }
        return true;
    }
    /**
     * 用于获取或设置参数值的魔术方法。如：
     * <code>
     * $req->iid('xxxxxxxxx'); // 等价于 $req->set('iid', 'xxxxxxxxx');
     * echo $req->iid();       // 等价于 $req->get('iid');
     * </code>
     * 
     * @param string $name 参数名
     * @param mixed $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if ( $this->has($name) ) {
            if ( empty($args) ) {
                return $this->get($name);
            }
            return $this->set($name, $args[0]);
        } else {
            throw new Exception("Unknown method '{$name}'\n");
        }
    }
    /**
     * 检查接口参数是否满足定义(必须或可选参数是否提供)
     * 
     * @return boolean
     */
    public function check()
    {
        if ( $this->no_check ) {
            return true;
        }
        $this->error = null;
        if ( isset($this->api_parameters['required']) ) {
            foreach ( $this->api_parameters['required'] as $name => $i ) {
                if ( !array_key_exists($name, $this->parameters) && $name != 'fields' ) {
                    $this->error = "Require parameter '{$name}'!";
                    return false;
                }
            }
        }
        if ( isset($this->api_parameters['optional']) ) {
            $valid = false;
            foreach ( $this->api_parameters['optional'] as $name => $i ) {
                if ( isset($this->parameters[$name]) ) {
                    $valid = true;
                }
            }
            if ( !$valid ) {
                $this->error = sprintf("These parameters '%s' should given at least one.", implode(', ', array_keys($this->api_parameters['optional'])));
                return false;
            }
        }
        return true;
    }
    /**
     * 接口检查出错的错误原因
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
    /**
     * 获得接口调用的参数
     * 
     * @return array
     */
    public function getParameters()
    {
        $query = array();
        if ( $this->no_check ) {
            foreach ( $this->parameters as $key => $val ) {
                if ( isset($val) && $val !== '' ) {
                    $query[$key] = (string)$val;
                }
            }
        }
        else {  
            foreach ( $this->api_parameters['all'] as $name => $type ) {
                if ( !isset($type['file']) && isset($this->parameters[$name]) && $this->parameters[$name] !== '' ) { 
                    $query[$name] = ($name=='status') ? $this->parameters[$name] :  (string)$this->parameters[$name];
                }
            }
        }
        if ( isset($this->api_parameters['all']['fields']) ) {
            $query['fields'] = $this->getFields();
        }
        return $query;
    }
    /**
     * 获得接口设置的 fields 参数值
     * 
     * @return string
     */
    public function getFields()
    {
        $fields = '';
        if ( isset($this->api_parameters['all']['fields']) ) {
            if ( empty($this->parameters['fields']) ) {
                $this->parameters['fields'] = array(':default');
            }
            if ( is_string($this->parameters['fields']) ) {
                $fields = $this->parameters['fields'];
            } elseif ( is_array($this->parameters['fields']) ) {
                $api_fields = $this->getMetadata('fields');
                $fields = array();
                foreach ( $this->parameters['fields'] as $n ) {
                    if ( $n{0} == ':' ) {
                        if ( array_key_exists($n, $api_fields) ) {
                            $fields = array_merge($fields, $api_fields[$n]);
                        }
                        // 如果 :default 未定义，则使用 :all 替代
                        elseif ( $n == ':default' ) {
                            $fields = array_merge($fields, $api_fields[':all']);
                        } else {
                            $fields = '*';
                        }
                    } else {
                        array_push($fields, $n);
                    }
                }
                $fields = implode(',', array_unique($fields));
            }
        }
        return $fields;
    }
    /**
     * 设置响应类对象。
     * 默认的响应类对象与请求类对象接口名相同，只是将 Request 换成 Response，例如
     * Tom_Top_Request_UserGet 的响应类为 Tom_Top_Response_UserGet
     * 
     * @param Tom_Top_Response $response 
     * @return Tom_Top_Request
     */
    public function setResponse($response=null)
    {
        if ( null === $response ) {
            $class = str_replace("Request", "Response", get_class($this));
            if ( class_exists($class) ) {
                $response = new $class($this);
            } else {
                throw new Exception("Can't find response class '{$class}'\n");
            }
        }
        $this->response = $response;
        return $this;
    }

    /**
     * 获得响应类对象
     * 
     * @return Tom_Top_Response
     */
    public function getResponse()
    {
        if ( null === $this->response ) {
            $this->setResponse();
        }
        return $this->response;
    }    
}
