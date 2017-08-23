<?php
/**
 * 日志记录类
 * @package Common
 * @subpackage Log
 * @author Terry
 * @since 7.2
 * @version 1.0
 * @date 2013-08-28
 */
class LogFactory{
    private static $log      = null;         //日志对象
    private static $logClass = array('file' => 'FileLog' , 'db' => 'DBLog');
    
    private function __construct(){}
    /**
     * 静态工厂方法，获取相应的LOG接口类
     * @param string $log_type
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-29
     * @return object
     */
    public static function factory($logType) {
        $className = isset(self::$logClass[$logType]) ? self::$logClass[$logType] : '';
        if(!class_exists($className)){
            throw new Exception('the Log Class is not exists',403);
    	}
        if(!self::$log instanceof ILogs){
            self::$log = new $className;
    	}
    	return self::$log;
    }
}
