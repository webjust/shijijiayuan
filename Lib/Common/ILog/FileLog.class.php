<?php
/**
 * @file FileLog.class.php
 * @package 数据库格式日志
 * @author Terry<wanghui@guanyisoft.com>
 * @class FileLog
 * @date 2013-08-28
 * @version 7.2
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class FileLog implements ILogs{
    //默认文件日志存放目录
    private $path = '';
    
    /**
     * @package 文件日志类的构造函数
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28
     */
    function __construct($path = ''){
        $this->path = $path;
    }
    
    /**
     * @package 向数据库写入Log
     * @param $logs loginfo数组
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28 
     * @return bool  操作结果
     */
    public function write($logs = array()){
        $path = RUNTIME_PATH."Logs/";
        if(!is_array($logs) || empty($logs)){
            throw new IException('the $logs parms must be array');
        }
        if($this->path == ''){
            throw new IException('the file path is undefined');
        }
        $content = join("\t",$logs)."\t\r\n";
        //判断是否存在logs目录，如果不存在则创建
        if(!is_dir($path)){
            @mkdir($path);
            @chmod($path, 0755);
        }
        //生成路径
        $fileName = $this->path;
        $result = error_log(date("c")."\t".$content."\n", 3, $path.$fileName);
        if(FALSE !== $result){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @package  设置路径
     * @param  String $path 设置日志文件路径
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28
     */
    public function setPath($path){
        $this->path = $path;
    }
}
