<?php

/**
 * 退款单模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersRefundsModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-07
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 获得一段时间内退款订单
     * @return ary 返回订单信息
     * @author Zhangjiasuo
     * @date 2013-04-03
     */
    public function GetRefundsOrderStatus() {
        $oneday = mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y"));//一天未付款订单自动取消
	    $create_time = date('Y-m-d H:i:s',$oneday);
        $where['or_processing_status'] = 0;
        $where['or_create_time'] = array('EGT', $create_time);
        $result = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->field('o_id')->where($where)->select();
        return $result;
    }
    /**
     * 更新退款订单
     * @author Zhangjiasuo
     * @date 2013-04-07
     */
    public function UpdateRefundsOrder($id,$data) {
        $where['o_id'] = $id;
        $data['or_update_time'] = date('Y-m-d H:i:s');
        M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($data);
    }
	
	/**
     * 退款订单查询结果集
     * @author Zhangjiasuo
     * @date 2015-04-17
     */
    public function GetRefundsOrders($ary_where = array(),$ary_field = '*',$group,$orders,$limit){
		$res_datas = $this->field($ary_field)
                          ->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                          ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")
                          ->where($ary_where)
                          ->order($orders)
						  ->group($group)
                          ->limit($limit)
                          ->select();
		return $res_datas;
    }
	
	/**
     * 退款订单统计
     * @author Zhangjiasuo
     * @date 2015-04-17
     */
    public function GetRefundsOrdersCount($ary_where = array()){
		$count = $this->join(" fx_members ON fx_orders_refunds.m_id=fx_members.m_id")
                      ->join(" fx_orders ON fx_orders_refunds.o_id=fx_orders.o_id")
					  ->where($ary_where)->count();
		return $count;
    }
}