<?php

/**
 * 销售统计后台模型层
 *
 * @package Model
 * @version 7.5
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2014-03-24
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class SalesStatisticsModel extends GyfxModel {
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
     */

    private $table;
	
	public function __construct() {
        parent::__construct();
		$this->ordersitems = D('OrdersItems');
		$this->orders = D('orders');
    }

	/**
     * 获得销售量记录集合
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
	 * 由于用到集合函数的结果为条件，框架sql不能满足复杂sql条件
     * @param array $ary_where
     * @return array $res
     */
    public function getSalesList($condition=array(),$limit) {
		$str_sql='select  sum(oi.oi_nums) as total_nums, oi.g_id,oi.g_sn,oi.oi_g_name as g_name, oi.pdt_id,oi.pdt_sn,oi.oi_price,g.gb_id  
			      from fx_orders as o ,fx_orders_items as oi ,fx_goods as g ';
		$str_sql .= ' where o.o_id=oi.o_id and oi.g_id=g.g_id and o.o_pay_status=1';
        if(!empty($condition['start_time'])){
			$str_sql .=' and o.o_create_time >= '."'".$condition['start_time']."'";
		}
        if(!empty($condition['end_time'])){
			$str_sql .=' and o.o_create_time <= '."'".$condition["end_time"]."'";
		}
		if(!empty($condition['g_sn'])){
			$str_sql .=' and oi.g_sn = '."'".$condition["g_sn"]."'";
		}
		if(!empty($condition['pdt_sn'])){
			$str_sql .=' and oi.pdt_sn = '."'".$condition["pdt_sn"]."'";
		}
		//去除退款/退货成功的订单
		$str_sql .=' and oi.oi_refund_status not in (4,5)';
		
	    $str_sql .= ' group by oi.pdt_id order by total_nums desc ';
	    $str_sql .= ' limit '.$limit ;
		$res=$this->ordersitems->query($str_sql);
        //print_r($res);die();
    	return $res;
    }
	
	/**
     * 获得销售量记录集合
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
     * @param array $condition
     * @return array $res
     */
    public function getSalesListCount($condition=array()) {
		$res = $this->orders
		         ->join(" " . C("DB_PREFIX") . "orders_items ON " . C("DB_PREFIX") . "orders_items.o_id=" . C("DB_PREFIX") . "orders.o_id")
                 ->where($condition)->group(C("DB_PREFIX").'orders_items.pdt_sn')->getField('fx_orders_items.pdt_id',true);
		//echo $this->orders->getLastsql();
		$count =count($res);
    	return $count;
    }
	
	/**
	 * 获取有过订单的会员数量
	 * 由于用到集合函数的结果为条件，框架sql不能满足复杂sql条件
	 * @return int 返回有过订单的会员数量（已完成订单）
	 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
	 * @param array $condition
     * @return array $res
	 */
	public function getCountOrderMembers($condition){
		$str_sql = "select count(m.`m_id`) as count from `fx_members` as m where `m_id` IN( select o.`m_id`";
		$str_sql .= " from `fx_orders` as o where `m_id`>0 and o.`o_status` <> 2 )";
		if(!empty($condition['start_time'])){
			$str_sql .=' and o.o_create_time >= '."'".$condition['start_time']."'";
		}
        if(!empty($condition['end_time'])){
			$str_sql .=' and o.o_create_time <= '."'".$condition["end_time"]."'";
		}
		if(!empty($condition['level'])){
			$str_sql .=' and m.ml_id = '.$condition["level"];
		}
		if(!empty($condition['name'])){
			$str_sql .= " and m.m_name LIKE '%" . $condition['name'] . "%' ";
		}
		$res=$this->orders->query($str_sql);
		return $res[0]['count'];
	}
	
	/**
     * 获得销售量记录集合
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
	 * 由于用到集合函数的结果为条件，框架sql不能满足复杂sql条件
     * @param array $ary_where
     * @return array $res
     */
    public function getMembersOrdersList($condition=array(),$limit) {
		$str_sql = "select m.m_id as m_id,m.m_name as m_name,m.m_real_name as 	m_real_name,";
		$str_sql .= "count(o.`o_id`) as count_orders,sum(o.`o_all_price`) as total_amount,sum(o.`o_cost_freight`)";
		$str_sql .= " as o_cost_freight, sum(o.`o_goods_all_price`) as goods_all_price from fx_members as m ,fx_orders as o  ";
		$str_sql .= " where m.m_id=o.m_id and o.m_id>0 and o.o_status <> 2 ";
		
		if(!empty($condition['start_time'])){
			$str_sql .=' and o.o_create_time >= '."'".$condition['start_time']."'";
		}
        if(!empty($condition['end_time'])){
			$str_sql .=' and o.o_create_time <= '."'".$condition["end_time"]."'";
		}
		if(!empty($condition['level'])){
			$str_sql .=' and m.ml_id = '.$condition["level"];
		}
		if(!empty($condition['name'])){
			$str_sql .= " and m.m_name LIKE '%" . $condition['name'] . "%' ";
		}
		
	    $str_sql .= ' group by o.m_id desc ';
		$str_sql .= ' limit '.$limit ;
		$res=$this->orders->query($str_sql);
    	return $res;
    }

    /**
     * 获得销售量求和
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @modify by Tom <helong@guanyisoft.com>
     * @date 2014-03-24
	 * 由于用到集合函数的结果为条件，框架sql不能满足复杂sql条件
     * @param array $ary_where
     * @return array $res
     */
    public function getSalesTotal($condition=array()) {
		$str_sql='select  sum(oi.oi_nums) as total_nums, oi.g_id,oi.g_sn,oi.oi_g_name as g_name, oi.pdt_id,oi.pdt_sn,oi.oi_price,g.gb_id  
			      from fx_orders as o ,fx_orders_items as oi ,fx_goods as g ';
		$str_sql .= ' where o.o_id=oi.o_id and oi.g_id=g.g_id and o.o_pay_status=1';
        if(!empty($condition['start_time'])){
			$str_sql .=' and o.o_create_time >= '."'".$condition['start_time']."'";
		}
        if(!empty($condition['end_time'])){
			$str_sql .=' and o.o_create_time <= '."'".$condition["end_time"]."'";
		}
		if(!empty($condition['g_sn'])){
			$str_sql .=' and oi.g_sn = '."'".$condition["g_sn"]."'";
		}
		if(!empty($condition['pdt_sn'])){
			$str_sql .=' and oi.pdt_sn = '."'".$condition["pdt_sn"]."'";
		}
		//去除退款/退货成功的订单
		$str_sql .=' and oi.oi_refund_status not in (4,5)';
		
	    $str_sql .= ' group by oi.pdt_id order by total_nums desc ';
	    $result_sql = 'select sum(tt.total_nums) as all_nums,sum(tt.oi_price) as pdt_price,sum(tt.total_nums*tt.oi_price) as total_price from ('.$str_sql.') as tt';
		$res=$this->ordersitems->query($result_sql);
        //print_r($res);die();
    	return empty($res) ? array() : $res[0];
    }
	
}