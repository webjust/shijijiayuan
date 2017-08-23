<?php
/**
 * @file Log.class.php
 * @package 日志接口文件
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-08-28
 * @version 7.2
 * @class Log interface
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
interface ILogs{
    /**
     * @pagecke 实现日志的写操作接口
     * @param array  $logs 日志的内容
     * @date 2013-08-28
     * @author Terry<wanghui@guanyisoft.com>
     */
    public function write($logs = array());
}