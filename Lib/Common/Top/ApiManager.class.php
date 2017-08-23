<?php
/**
 * TOP 接口定义信息管理类
 * 
 * API 需要定义以下信息：
 *  - method: TOP API 名字，如 'ecerp.user.get'
 *  - parameters: 请求参数名
 *  - fields: 返回字段
 *  - http_method: http 调用的方法，如 'GET', 'POST'，默认为 GET
 *
 * parameters 可分成四种类型：
 *  - required 必须参数，如果调用时未提供时将报错
 *  - other 非必须参数，可以不提供
 *
 * fields 指定接口调用时 fields 参数可选的字段。接口定义时 fields 值是一个
 * 关联数组，key 是字段分组名，值是组中的字段值。必须提供名为 :all 的分组。
 * 在提供分组之后，在调用接口时可以使用分组名设置接口调用时的 fields 值。如
 * Top_Request_UserGet 中定义了 :public 分组，则可以如下调用：
 * <code>
 * $result = $top->userGet(array(
 *    // 获得 :public 中的字段和 type 字段，等价于：
 *    // fields => 'user_id,nick,sex,buyer_credit,seller_credit,location,created,last_visit,type'
 *    'fields' => array(':public', 'type'), 
 *    'nick' => 'alipublic01'
 *    ));
 * </code>
 *
 * 如果接口需要 fields 参数，但是接口调用时未指定 fields 的值，将使用 :default 分组
 * 作为 fields 的值，如果未定义 :default 分组，则使用 :all 分组。
 * 
 * @package top
 * @copyright Copyright (c) 2013, guanyisoft. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_ApiManager
{
    static protected $apis;
    /**
     * 新增接口定义
     * 
     * @static
     * @param string $api_name 接口名。这里接口名产生规则是将 api 名的第一个 taobao 去除后，其余单词首字母大写连接生成。如 taobao.user.get 的接口名为 UserGet
     * @param array $metadata 接口定义信息
     * @return void
     */
    public static function add($api_name, $metadata)
    {
        if ( empty($metadata['method']) ) {
            throw new Exception("API 'method' 参数未指定");
        }
        $parameters = array();
        if ( !isset($metadata['parameters']['other']) ) {
            $metadata['parameters']['other'] = array();
        }
        /* add system parameter */
        $metadata['parameters']['other'][] = 'shopcode';
        
        foreach ( array('required', 'other') as $type ) {
            if ( isset($metadata['parameters'][$type]) ) {
                foreach ( $metadata['parameters'][$type] as $name ) {
                    if ( !isset($parameters[$name]) ) {
                        $parameters[$name] = array();
                    }
                    if ( ($pos=strpos($name, ".")) !== false ) {
                        $parameters[substr($name, 0, $pos)]['struct'] = true;
                    }
                    $parameters[$name][$type] = true;
                }
                $metadata['parameters'][$type] = array_flip($metadata['parameters'][$type]);
            }
        }
        
        $metadata['parameters']['all'] = $parameters;
        if ( isset($metadata['parameters']['all']['fields']) && !isset($metadata['fields'][':all'])) {
            throw new Exception("API fields :all 参数未指定");
        }
        self::$apis[$api_name] = $metadata;
    }
    /**
     * 测试接口是否定义
     * 
     * @static
     * @param string $api_name 接口名
     * @return boolean
     */
    public static function has($api_name)
    {
        return isset(self::$apis[$api_name]);
    }
    /**
     * 获得接口定义信息
     * 
     * @static
     * @param string $api_name 接口名
     * @param string $key 接口定义项，如 method, fields
     * @return mixed 如果未提供 $key，返回接口所有定义信息; 否则返回对应的定义信息
     */
    public static function get($api_name, $key=null)
    {
        if ( isset(self::$apis[$api_name]) ) {
            if ( null === $key ) {
                return self::$apis[$api_name];
            }
            elseif ( isset(self::$apis[$api_name][$key]) ) {
                return self::$apis[$api_name][$key];
            }
        }
        return null;
    }
    /**
     * 获得所有接口定义信息
     * 
     * @static
     * @return array
     */
    public static function getApis()
    {
        return self::$apis;
    }
}