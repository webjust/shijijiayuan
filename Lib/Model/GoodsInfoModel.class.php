<?php

/**
 * 商品详细模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsInfoModel extends GyfxModel {

	/**
	 * 构造方法
	 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2013-04-03
	 */

	public function __construct() {
		parent::__construct();
	}
	/**
	 * 更新商品库存
	 * @author Zhangjiasuo
	 * @date 2013-04-07
	 */
	public function UpdateStock($erp_guid,$stock) {
		$ary_goods_stock['g_stock'] = $stock;
		$res_products = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
		->where(array('erp_guid' => $erp_guid))->save($ary_goods_stock);
	}
		
	/**
	 * 热销货品统计
	 * @author Zhangjiasuo
	 * @param timestamp $start_time 查询开始时间
	 * @param timestamp $end_time 查询结束时间
	 * @param int $num 查询数量
	 * @date 2013-04-11
	 */
	public function HotCount($start_time,$end_time,$limit,$g_id,$m_id,$cid) {
		$ary_where = array();
		$join_where = array();
		if(!empty($g_id)){
			$ary_where['fx_goods_info.g_id'] = $g_id;
		}
		//只显示上架的
		$ary_where['fx_goods.g_on_sale'] = 1;
		$join_where[] = ' fx_goods on(fx_goods.g_id=fx_goods_info.g_id)';
		if(!empty($cid)){
			$ary_where['rgc.gc_id'] = $cid;
			$join_where[] = ' fx_related_goods_category as rgc on(fx_goods_info.g_id=rgc.g_id)';
		}
		$ary_res = array();
		$obj_query = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field('fx_goods_info.g_id,g_name as oi_g_name,g_salenum as num,g_picture,g_price')->join($join_where)->where($ary_where)->order('`g_salenum` desc')->limit($limit);	
		$ary_res = D('Gyfx')->queryCache($obj_query,null,60);
		//七牛图片显示
		foreach($ary_res as $key=>$value){
			$ary_res[$key]['g_picture']=D('QnPic')->picToQn($value['g_picture']);
		}
		return $ary_res;
	}
	/**
	public function HotCount($start_time,$end_time,$limit,$g_id,$m_id,$cid) {
		$condition ['or_create_time']= array(array('EGT', $start_time),array('ELT', $end_time)) ;
		$ary_refund=M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')
		->field(array('fx_orders_refunds.o_id'))
		->join('fx_orders_items on fx_orders_refunds.o_id = fx_orders_items.o_id')
		->where($condition)->group ('fx_orders_items.g_id')->limit($limit)->select();

		$temp_id=array();
		foreach ($ary_refund as $val) {
			$temp_id[] = $val['o_id'];
		}
		$where['fx_orders.o_pay_status']=1;
		if(!empty($temp_id) && is_array($temp_id)){
			$where['fx_orders_items.o_id']= array('not in', implode(',', $temp_id));
		}
		if(!empty($start_time)){
			$where['fx_orders_items.oi_create_time']= array('EGT', $start_time) ;
		}
		if(!empty($end_time)){
			$where['fx_orders_items.oi_create_time']= array('ELT', $end_time) ;
		}
		if(!empty($g_id)){
			$where['fx_orders_items.g_id'] = $g_id;
		}
		if(!empty($m_id)){
			$where['fx_orders.m_id'] = $m_id;
		}
		if(!empty($cid)){
			$where['fx_related_goods_category.gc_id'] = $cid;
		}
		//商品未被删除
		$where['_string'] = "ifnull(fx_goods_info.g_id,'') != ''";
		$ary_res = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
		->field('fx_orders_items.g_id,fx_orders_items.oi_g_name,fx_goods_info.g_picture,fx_goods_info.g_price,SUM(fx_orders_items.oi_nums) as num')
		->join('fx_orders on fx_orders_items.o_id = fx_orders.o_id')
		->join('fx_goods_info on fx_orders_items.g_id = fx_goods_info.g_id')
		->join('fx_orders_refunds on fx_orders_items.o_id = fx_orders_refunds.o_id')
		->join('fx_related_goods_category on fx_orders_items.g_id = fx_related_goods_category.g_id')
		->group ('fx_orders_items.g_id')->where($where)->limit($limit)->select();
		return $ary_res;
	}
	**/

	/**
	 * 购买记录
	 * @author Wangguibin
	 * @gid商品ID
	 * @param int $num 查询数量
	 * @date 2013-07-25
	 */
	public function getBuyRecords($tag) {
		$where['fx_orders_items.g_id']= $tag['gid'] ;
		//默认获取最近两个月的销量
		if(empty($tag['stime']) && empty($tag['etime'])){
			$tag['stime'] = date("Y-m-d H:i:s",strtotime("-1 month"));
		}
		if(!empty($tag['stime'])){
			$where['fx_orders_items.oi_create_time']= array('EGT', $tag['stime']) ;
		}
		if(!empty($tag['etime'])){
			$where['fx_orders_items.oi_create_time']= array('ELT', $tag['etime']) ;
		}
		if(!empty($tag['gid'])){
			$where['fx_orders_items.g_id'] = $tag['gid'];
		}
		if($tag['num']){
			$page_no = 1;
			$page_size = $tag['num'];
		}else{
			$page_no = empty($tag['p'])?1:$tag['p'];
			$page_size = empty($tag['pagesize'])?20:$tag['pagesize'];
		}
		/*$ary_refund = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')
		->field(array('fx_orders_refunds.o_id'))
		->join('fx_orders_items on fx_orders_refunds.o_id = fx_orders_items.o_id')
		->where(array('fx_orders_items.g_id'=>$tag['gid']))
		->select();
		$temp_id=array();
		foreach ($ary_refund as $val) {
			$temp_id[] = $val['o_id'];
		}
		$where['fx_orders.o_pay_status']=1;
		if(!empty($temp_id) && is_array($temp_id)){
			$where['fx_orders_items.o_id']= array('not in', implode(',', $temp_id));
		}*/
		
		$where['fx_orders.o_pay_status']=1;
		$where['fx_orders_refunds.o_id ']  = array('exp',' is NULL');
		
		$ary_res = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
		->field('fx_orders.is_anonymous,fx_orders_items.g_id,fx_orders_items.oi_g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_orders_items.oi_price,fx_orders_items.oi_nums,fx_members.m_name,fx_orders_items.pdt_id,fx_orders_items.oi_score,fx_orders_items.oi_create_time')
		->join('fx_orders on fx_orders_items.o_id = fx_orders.o_id')
		->join('fx_members on fx_members.m_id = fx_orders.m_id')
		->join('fx_goods_info on fx_orders_items.g_id = fx_goods_info.g_id')
		->join('fx_orders_refunds on fx_orders_items.o_id = fx_orders_refunds.o_id')
		->order ('fx_orders_items.oi_create_time desc')
		->where($where)
		->page($page_no,$page_size)
		->select();
		//echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		if (!empty($ary_res) && is_array($ary_res)) {
			foreach ($ary_res as &$vl) {
				$spec_name = D('GoodsSpec')->getProductsSpec($vl['pdt_id']);
				if(!empty($spec_name)){
					$vl['oi_g_name'] = $vl['oi_g_name'].' '.$spec_name;
				}
				//匿名购买
				/**
				if($vl['is_anonymous'] == '1'){
					$vl['m_name'] = csubstr($vl['m_name'],4);
				}
				**/
				if(preg_match("/1[34578]{1}\d{9}$/",$vl['m_name'])){  
				   $mobile1 = mb_substr($vl['m_name'], 0, 3);
				   $mobile2 = mb_substr($vl['m_name'], strlen($vl['m_name'])-2);
				   $vl['m_name'] = $mobile1.'***'.$mobile2;
				}else{  
					if(strlen($vl['m_name'])<3){
						$vl['m_name'] = csubstr($vl['m_name'],1);
					}else{
					   $mobile1 = mb_substr($vl['m_name'], 0, 1,"utf-8");
					   $mobile2 =mb_substr($vl['m_name'],-1,1,"UTF-8");
					   $vl['m_name'] = $mobile1.'***'.$mobile2;
					}
				} 
					
				$v['g_picture'] = D('QnPic')->picToQn($v['g_picture'],300,300);
				//$vl['m_name'] = csubstr($vl['m_name'],4);
				//$vl['new_mname'] = str_replace(substr($vl['m_name'], 3, 2), "****", $vl['m_name']);
			}
		}
		$count = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
		->join('fx_orders on fx_orders_items.o_id = fx_orders.o_id')
		->join('fx_goods_info on fx_orders_items.g_id = fx_goods_info.g_id')
		->join('fx_orders_refunds on fx_orders_items.o_id = fx_orders_refunds.o_id')
		->where($where)
		->count();
		
		$int_sales_nums = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
		->join('fx_orders on fx_orders_items.o_id = fx_orders.o_id')
		->join('fx_goods_info on fx_orders_items.g_id = fx_goods_info.g_id')
		->join('fx_orders_refunds on fx_orders_items.o_id = fx_orders_refunds.o_id')
		->where($where)
		->sum('oi_nums');
		/*
		$int_sales_nums = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
						->join(C('DB_PREFIX').'orders on '.C('DB_PREFIX').'orders_items.o_id =' .C('DB_PREFIX'). 'orders.o_id')
						->join(C('DB_PREFIX').'goods_info on '.C('DB_PREFIX').'orders_items.g_id ='.C('DB_PREFIX'). 'goods_info.g_id')
						->join(C('DB_PREFIX').'orders_refunds on '.C('DB_PREFIX').'orders_items.o_id ='.C('DB_PREFIX').'orders_refunds.o_id')
						->where($where)
						->sum('oi_nums');
						//echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		*/			
						/**
		$sale_where = array();
		$str_one_month_ago = date('Y-m-d H:i:s', strtotime('-1 month'));
		$sale_where[C('DB_PREFIX').'orders_items.oi_create_time'] = array('egt',$str_one_month_ago);
		$sale_where[C('DB_PREFIX').'orders_items.g_id'] = $tag['gid'];
		$sale_where[C('DB_PREFIX').'orders.o_pay_status'] = 1;
		$ary_one_month_ago_sales = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
						->join(C('DB_PREFIX').'orders on fx_orders_items.o_id = fx_orders.o_id')
						->join(C('DB_PREFIX').'goods_info on '. C('DB_PREFIX').'orders_items.g_id ='. C('DB_PREFIX').'goods_info.g_id')
						->join(C('DB_PREFIX').'orders_refunds on '.C('DB_PREFIX').'orders_items.o_id ='.C('DB_PREFIX').'orders_refunds.o_id')
						->where($sale_where)
						->sum('oi_nums');
			**/			
		$obj_page = new Pager($count, $page_size);
        $page = $obj_page->showArr();
		$pagearr = $obj_page->show();
		return array('data'=>$ary_res,'page'=>$page,'pagearr'=>$pagearr,'buynums'=>$int_sales_nums,'one_month_sales'=>$int_sales_nums);
	}

	/**
	 * 商品浏览历史总的统计
	 * @author Wangguibin
	 * @param timestamp $start_time 查询开始时间
	 * @param timestamp $end_time 查询结束时间
	 * @param int $num 查询数量
	 * @date 2013-04-23
	 */
	public function BrowsehistoryCount($start_time,$end_time,$limit) {
		if(!empty($start_time)){
			$where['fx_keystore.modify_time']= array('EGT', $start_time) ;
		}
		if(!empty($end_time)){
			$where['fx_keystore.modify_time']= array('ELT', $end_time) ;
		}
		$where['fx_goods.g_on_sale'] = 1;
		$where['fx_keystore.g_id'] = array('EGT', 0) ;;
		$ary_res = M('keystore',C('DB_PREFIX'),'DB_CUSTOM')
		->field('fx_goods_info.g_name,fx_goods_info.g_id,fx_goods_info.g_picture,fx_goods_info.g_price,fx_keystore.value as num,fx_keystore.modify_time')
		->join('fx_goods_info on fx_keystore.g_id = fx_goods_info.g_id')
		->join('fx_goods on fx_keystore.g_id = fx_goods.g_id')
		->where($where)->order(array('fx_keystore.modify_time' => 'desc'))->limit($limit)->select();
		if (!empty($ary_res) && is_array($ary_res)) {
			foreach ($ary_res as &$vl) {
				$v['g_picture'] = D('QnPic')->picToQn($v['g_picture'],300,300);
			}
		}		
		//dump($ary_res);die();
		return $ary_res;
	}
		 
	/**
	 * 热销货品更新
	 * @author Zhangjiasuo
	 * @param timestamp $start_time 查询开始时间
	 * @param timestamp $end_time 查询结束时间
	 * @param int $num 查询数量
	 * @date 2013-04-11
	 */
	public function updateHotCount($start_time,$end_time,$limit) {
		$ary_res = $this->HotCount($start_time,$end_time,$limit);
		if(!empty($ary_res)){
			foreach($ary_res as $value){
				$option['g_id']=$value['g_id'];
				$data['g_salenum']=$value['num'];
				$ary_res1 = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
				->where($option)->save($data);
			}
		}
		if($ary_res1){
			return count($ary_res);
		}else{
			return $ary_res1;
		}
	}

	/**
	 * 查询指定商品id的商品信息
	 * @author Zhangjiasuo
	 * @param string 商品货号
	 * @date 2013-07-16
	 */
	public function Search($ary_where = array(), $ary_field = '') {
		$res = $this->field($ary_field)->where($ary_where)->find();
		return $res;
	}

}
