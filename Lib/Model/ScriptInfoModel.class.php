<?php

/**
 * 定时脚本模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ScriptInfoModel extends GyfxModel {
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-03
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 获得脚本执行
     * @author Zhangjiasuo
     * @return array()
     * @date 2013-04-03
     */
    public function GetScripts($code,$result) {
        $result =M('script_info',C('DB_PREFIX'),'DB_CUSTOM')->select();
        return $result;
    }
    /**
     * 更新脚本执行时间点
     * @author Zhangjiasuo
     * @date 2013-04-07
     */
    public function UpdateTime($code) {
        $res_insert['run_time'] = date("Y-m-d H:i:s");
        M('script_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('code' => $code))->save($res_insert);
    }
    /**
     * 更新脚本执行状态
     * @author Zhangjiasuo
     * @date 2013-04-03
     */
    public function UpdateStatus($code,$result) {
        $res_insert['result']=$result;
        M('script_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('code' => $code))->save($res_insert);
    }
}