<?php
/**
 * @file DBLog.class.php
 * @package 数据库格式日志
 * @author Terry<wanghui@guanyisoft.com>
 * @class DBLog
 * @date 2013-08-28
 * @version 7.2
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class DBLog implements ILogs{
    //记录的数据表名
    private $tableName = '';
    
    /**
     * @package 构造函数
     * @param string 要记录的数据表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28
     */
    public function __construct($tableName = ''){
        $this->tableName = $tableName;
    }
    
    /**
     * @package 向数据库写入Log
     * @param array  log数据
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28 
     * @return bool  操作结果
     */
    public function write($logs = array()){
        if(!is_array($logs) || empty($logs)){
            throw new Exception('the $logs parms must be array');
        }
        if($this->tableName == ''){
            throw new Exception('the tableName is undefined');
        }
        
        $result = M($this->tableName,C('DB_PREFIX'),'DB_CUSTOM')->add($logs);
        if(FALSE !== $result){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @package  设置要写入的数据表名称
     * @param  String $tableName 要记录的数据表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28
     */
    public function setTableName($tableName){
        $this->tableName = $tableName;
    }
}