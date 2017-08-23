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
/***************************************************************************
 * 使用方法：
 *      $logobj = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
 *      $logobj->write('operation',array("管理员:".XXX,"动作",'内容'));
 *
 *
 ***************************************************************************/
class ILog{

    private $logType = 'file'; //默认日志类型
    private $log = null;
    private $logInfo = array(
        'operation' => array('table' => 'log_operation', 'cols' => array('author', 'action', 'content')),
    );

    public function __construct($logType = '') {
        
        if(!empty($logType)){
            $this->logType = $logType;
        }
        $this->log = LogFactory::factory($logType);
    }

    /**
     * @package 向数据库写入Log
     * @param $type string 
     * @param $logs array 日志内容
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28 
     * @return bool  操作结果
     */
    public function write($type,$logs = array()){
        
        $logInfo = $this->logInfo;
        if(!isset($logInfo[$type])){
            return false;
        }
        
        $className = get_class($this->log);
        
        switch($className){
            //文件日志
            case "FileLog":
                {
                    //设置路径
                    $path = RUNTIME_PATH."Logs/";
                    $fileName = rtrim($path,'\\/').'/'.$type.'/'.date('Y/m').'/'.date('d').'.log';
                    $this->log->setPath($fileName);
                    $logs     = array_merge(array($this->getDateTime()),$logs);
                    return $this->log->write($logs);
                }
                break;
            //数据库日志
            case "DBLog":
                {
                    $content['datetime'] = $this->getDateTime();
                    $tableName           = $logInfo[$type]['table'];
                    foreach($logInfo[$type]['cols'] as $key => $val){
                        $content[$val] = isset($logs[$val]) ? $logs[$val] : isset($logs[$key]) ? $logs[$key] : '';
                    }
                    
                    $this->log->setTableName($tableName);
                    
                    return $this->log->write($content);
                }
                break;
            default:
                return false;
                break;
        }
    }
    
    /**
     * @package  根据指定的格式输出时间
     * @param  String  $format 格式为年-月-日 时:分：秒,如‘Y-m-d H:i:s’
     * @param  String  $time   输入的时间
     * @author Terry<wanghui@guanyisoft.com>
     * @return String  $time   时间
     */
    public function getDateTime($format='',$time=''){
        $time   = !empty($time)  ? $time  : time();
        $format = !empty($format)? $format: 'Y-m-d H:i:s';
        return date($format,$time);
    }
}
