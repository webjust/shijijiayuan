<?php
/**
 * 销售统计后台模块控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.5
 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
 * @date 2014-03-24
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class SalesStatisticsAction extends AdminAction{
	
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU5_4'));
    }
    
    /**
     * 销量排名
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
     */
    public function SalesRanking(){
    	$this->getSubNav(4, 5, 10);
		$ary_get = $this->_get();
		
		//如果根据名称进行搜索
		switch ($ary_get['field']){
			case 1:
				$ary_count_condition[C("DB_PREFIX") ."orders_items.g_sn"] = $ary_get['val'];
				$ary_condition["g_sn"] = $ary_get['val'];
				break;
			case 2:
				$ary_count_condition[C("DB_PREFIX") ."orders_items.pdt_sn"] = $ary_get['val'];
				$ary_condition["pdt_sn"] = $ary_get['val'];
				break;
			default:
				break;
		}

		$ary_condition["start_time"] = $ary_get['start_time'];
		$ary_condition["end_time"] = $ary_get['end_time'];
		if(!empty($ary_get["start_time"]) && !empty($ary_get["end_time"])){
			$ary_count_condition[C("DB_PREFIX") .'orders.o_create_time']=array(array("egt",$ary_get["start_time"]),array("elt",$ary_get["end_time"]));
		}
		//去除退款/退货成功的订单
		$ary_count_condition[C("DB_PREFIX") ."orders_items.oi_refund_status"]  = array('not in','4,5');
		
		$count=D('SalesStatistics')->getSalesListCount($ary_count_condition);
		$Page = new Page($count, 20);
		
	    $limit=$Page->firstRow . ',' . $Page->listRows;
		
    	$ary_res=D('SalesStatistics')->getSalesList($ary_condition,$limit);
		foreach($ary_res as &$val){
			$val['gb_name']='';
			if($val['gb_id']){
				$val['gb_name'] = D('GoodsBrand')->where(array('gb_id'=>$val['gb_id']))->getField("gb_name");
			}
			$val['total_price']=$val['total_nums']*$val['oi_price'];
		}
		$ary_total = D('SalesStatistics')->getSalesTotal($ary_condition);

		$this->assign('total',$ary_total);
		$this->assign("page", $Page->show());
    	$this->assign('list',$ary_res);
		$this->assign("filter",$ary_get);
    	$this->display(); 
    }
	
	/**
     * 购买量排名
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2014-03-24
     */
    public function MembersRanking(){
    	$this->getSubNav(4, 5, 20);
		$ary_get = $this->_get();
		$ary_condition =array();
		if(isset($ary_get['field']) && $ary_get['field']!=''){
			$ary_condition["level"] = $ary_get['field'];
		}
		if(isset($ary_get['val']) && $ary_get['val']!=''){
			$ary_condition["name"] = $ary_get['val'];
		}
		if(isset($ary_get['start_time']) && $ary_get['start_time']!=''){
			$ary_condition["start_time"] = $ary_get['start_time'];
		}
		if(isset($ary_get['end_time']) && $ary_get['end_time']!=''){
			$ary_condition["end_time"] = $ary_get['end_time'];
		}
		$level_list=D('MembersLevel')->getMembersLevels();
		//print_r($ary_get);
		
		$count=D('SalesStatistics')->getCountOrderMembers($ary_condition);
		$Page = new Page($count, 20);
		
	    $limit=$Page->firstRow . ',' . $Page->listRows;
		
    	$ary_res=D('SalesStatistics')->getMembersOrdersList($ary_condition,$limit);
		foreach($ary_res as &$val){
			$val['total_price']=$val['total_nums']*$val['oi_price'];
		}
		
		$this->assign("page", $Page->show());
    	$this->assign('list',$ary_res);
		$this->assign('level_list',$level_list);
		$this->assign("filter",$ary_get);
    	$this->display(); 
    }
}