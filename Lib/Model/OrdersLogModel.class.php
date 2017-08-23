<?php

/**
 * 订单日志模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersLogModel extends GyfxModel {

    /**
     * 构造方法
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-03
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 插入订单日志
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @param array $data
     * @return string
     * @date 2013-09-03
     */
    public function addOrderLog($data) {
    	if(empty($data['ol_uname'])){
    		$data['ol_uname'] = $_SESSION['admin_name'];
    	}
    	$data['ol_create'] = date('Y-m-d H:i:s');
        $res = M('orders_log',C('DB_PREFIX'),'DB_CUSTOM')->data($data)->add();
        return $res;
    }
    
}