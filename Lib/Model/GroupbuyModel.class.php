<?php

/**
 * 团购模型层 Model
 * @package Model
 * @version 7.4
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-08-22
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GroupbuyModel extends GyfxModel {
    /**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 检测当前团购是否可以购买
     * @param gp_id 团购ID   m_id会员id
     * @return array('status'=>bool,'msg'=>'')
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-12-08
     */
    public function checkBulkIsBuy($m_id,$gp_id,$oid,$is_pay){
        $groupbuy_log = M('groupbuy_log',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $nums = 0;
        if($is_pay == 1){
            $orders = M('OrdersItems',C('DB_PREFIX'),'DB_CUSTOM');
            $nums = $orders->where(array('o_id'=>$oid,'oi_type'=>5))->getField('oi_nums');
            if($nums < 1){
                return array('status'=>false,'msg'=>'团购订单不存在！');
            }
        }
        $ary_bulk_info = $this->where(array('is_active'=>1,'gp_id'=>$gp_id,'deleted'=>0))->find();
        //验证团购是否存在（已删除）
        if(empty($ary_bulk_info)){
            return array('status'=>false,'msg'=>'团购商品不存在或已过期！');
        }
        //验证团购是否已过期
        if(strtotime($ary_bulk_info['gp_start_time']) > mktime()){
            //团购未开始
            return array('status'=>false,'msg'=>'团购未开始');
        }elseif((strtotime($ary_bulk_info['gp_start_time']) < mktime()) && (strtotime($ary_bulk_info['gp_end_time'])< mktime())){
            //团购已结束
            return array('status'=>false,'msg'=>'团购已结束');
        }
        //参团数量是否达到上限
		
        if($ary_bulk_info['gp_now_number']-$nums >= $ary_bulk_info['gp_number']){
            return array('status'=>false,'msg'=>'团购数量已达上限');
        } 
		
        //个人参团购买数量是否达上限
        //$buy_nums =  $groupbuy_log->field(array('SUM(num) as buy_nums'))->where(array('m_id'=>$m_id,'gp_id'=>$gp_id))->find();
		$buy_nums =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
		->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
		->where(array('fx_orders.m_id'=>$m_id,'fx_orders_items.fc_id'=>$gp_id,'fx_orders_items.oi_type'=>'5','fx_orders.o_status'=>array('neq',2),'fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
		->find();
		if($ary_bulk_info['gp_per_number'] < $buy_nums['buy_nums']){
            return array('status'=>false,'msg'=>'您购买数量已达上限，请勿重复购买');
        }
        
        return array('status'=>true);
        
    }
    
    /*
     * 获取团购价格
     *
     */
    public function getBulkPrice($ary_cart = array()){

        $array_where = array('is_active'=>1,'gp_id'=>$ary_cart['gp_id']);
        $ary_groupbuy = $this->where($array_where)->find();
        if(empty($ary_groupbuy)){
            return array('status'=>'error','data'=>'团购商品不存在！');
        }
        $ary_groupbuy['pdt_sale_price'] = M('goods_products')->where(array('pdt_id'=>$ary_cart['pdt_id']))->getField('pdt_sale_price');
        $ary_groupbuy['pdt_all_sale_price'] = $ary_groupbuy['pdt_sale_price'] * $ary_cart['num'];

        //目前已参团人数，虚拟购买数+实际购买数
        $buy_nums = $ary_groupbuy['gp_pre_number'] + $ary_groupbuy['gp_now_number'];
        $gp_price_model = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        //取出价格阶级
        $rel_bulk_price = $gp_price_model->where(array('gp_id'=>$ary_groupbuy['gp_id']))->select();
        //当前所在价格阶梯
        $current_num_range = 0;
        foreach($rel_bulk_price as $rp_k=>$rp_v){
            if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                $current_num_range = $rp_v['rgp_num'];
            }
        }
        //如果满足某价格阶梯，阶梯价覆盖初始价
        if(!empty($current_num_range)){
            $ary_groupbuy['gp_price'] = $gp_price_model->where(array('gp_id'=>$ary_groupbuy['gp_id'],'rgp_num'=>$current_num_range))->getField('rgp_price');
        }
        //团购价格
        switch($ary_groupbuy['gp_tiered_pricing_type']) {
            //直接减优惠金额
            case 1:
                $ary_groupbuy['gp_price'] = $ary_groupbuy['pdt_sale_price']-$ary_groupbuy['gp_price'];
                break;
            //设置优惠折扣
            case 2:
                $ary_groupbuy['gp_price'] = round($ary_groupbuy['pdt_sale_price']*$ary_groupbuy['gp_price'], 2);
                break;
        }
        $ary_groupbuy['gp_subtotal_price'] = $ary_groupbuy['gp_price']* $ary_cart ['num'];
        return array('status'=>'success','data'=>$ary_groupbuy);
    }

    /**
     * 获取团购商品总金额
     * @param $ary_items
     */
    public function getDetailWithPrice($ary_items) {
        $item = current($ary_items);
        if(!empty($item['type_id'])) $item['gp_id'] = $item['type_id'];
        $ary_group_buy = $this->getBulkPrice($item);
        return $ary_group_buy['data'];

    }
	
	
	/**
     * 获取商品详情页sku信息
	 * @param int $gp_id
	 * @param array $members
	 * @param int $pdt_id
	 * @param bool $qiniu_pic
	 * @return array
     * @author Nick
     * @date 2015-10-28
     */
    public function getDetails($gp_id, $members=array(), $pdt_id=0,$qiniu_pic=false) {
        //获取团购设置，团购初始价，总限售数，没人限购数，等
		$ary_groupbuy = $this->where(array(
			'gp_id'	=>	(int)$gp_id
		))->find();
        $ary_groupbuy['buy_status'] = $ary_groupbuy['is_active'];
//		dump($ary_groupbuy);die;
		$gp_per_number = $ary_groupbuy['gp_per_number'];
		$gp_number = $ary_groupbuy['gp_number'];

		//目前已参团人数，虚拟购买数+实际购买数
        $buy_nums = $ary_groupbuy['gp_pre_number'] + $ary_groupbuy['gp_now_number'];
		$gp_price_model = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
		//取出价格阶级
        $rel_bulk_price = $gp_price_model->where(array('gp_id'=>$ary_groupbuy['gp_id']))->select();
        //当前所在价格阶梯
		$current_num_range = 0;
        foreach($rel_bulk_price as $rp_k=>$rp_v){
            if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                $current_num_range = $rp_v['rgp_num'];
            }
        }
		//如果满足某价格阶梯，阶梯价覆盖初始价
        if(!empty($current_num_range)){
            $ary_groupbuy['gp_price'] = $gp_price_model->where(array('gp_id'=>$ary_groupbuy['gp_id'],'rgp_num'=>$current_num_range))->getField('rgp_price');
        }
		
		$gid = $ary_groupbuy['g_id'];
		$m_id = $members['m_id'];
        $good_info = D('GoodsInfo')->where(array('g_id'=>$gid))->find();
        $ary_groupbuy['g_desc'] = $good_info['g_desc'];
		//获取该商品作为普通商品时的货品价格和库存
		$ary_goods_pdt = D('Goods')->getDetails($gid, $members, $pdt_id, $qiniu_pic);
		$pdt_detail = $ary_goods_pdt['page_detail'];
		//商品库存数
		$g_stock = $pdt_detail['gstock'];
		//如果团购限购数量为0或设置的限售数大于商品库存数，
		//则限购数等于商品库存数
        if($gp_number==0 || $gp_number > $g_stock) {
            $gp_number = $g_stock;
        }

		//如果团购会员限购数量为0 
		//则会员限购数等于总限购数
		if($gp_per_number ==0 ) {
			$gp_per_number = $gp_number;
		}
		//已满团
        if($buy_nums >= $gp_number) {
            $ary_groupbuy['buy_status'] = 5;
			//总库存
			$avalible_stock = 0;
			//可购买数
			$gp_per_number = 0;
        }
		//未满团
		else{
			//团购剩余库存总数
			$avalible_stock = $gp_number-$buy_nums;
			//判断当前团购数量是否达到上限（总限量）
			if($m_id){
				$member_buy_num = M('groupbuy_log as gpl',C('DB_PREFIX'),'DB_CUSTOM')->field('SUM(num) as buy_nums')
				->join(C('DB_PREFIX').'orders as o on gpl.o_id=o.o_id')
				->where(array(
					'gpl.m_id'=>$m_id,
					'gpl.gp_id'=>$ary_groupbuy['gp_id'],
					'o.o_status'=>array('neq', 2)
				))
				->find();
				//本团购购买记录为空
				if(empty($member_buy_num)) {
					$member_buy_num['buy_nums'] = 0;
				}

				//已购买总数小于会员限购数
				if ($member_buy_num['buy_nums'] < $gp_per_number) {
					//可购买数=会员限购总数-已购买总数
					$gp_per_number = $gp_per_number - $member_buy_num['buy_nums'];
					//二者取其小
					$gp_per_number = min($avalible_stock, $gp_per_number);
				}				
				//购买数超过会员限购数
				else {
                    $ary_groupbuy['buy_status'] = 0;
				}

			}else{
				//提示登录
                $ary_groupbuy['buy_status'] = 2;
			}
			
		}
		//总库存
		$ary_goods_pdt['gstock'] = $avalible_stock;
		//可购买数
		$ary_goods_pdt['max_buy_number'] = $gp_per_number;
		//团购价格
		switch($ary_groupbuy['gp_tiered_pricing_type']) {
			//直接减优惠金额
			case 1:
				foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
					$pdt_final_price = $goods_pdts['pdt_set_sale_price'] - $ary_groupbuy['gp_price'];
					$pdt_final_price <= 0 && $pdt_final_price = 0.00;
					$pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
				}
				break;
			//设置优惠折扣
			case 2:
				foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
                    $ary_groupbuy['gp_price'] = min(1, $ary_groupbuy['gp_price']);
					$pdt_final_price = $goods_pdts['pdt_set_sale_price'] * $ary_groupbuy['gp_price'];
					$pdt_final_price <= 0 && $pdt_final_price = 0.00;
					$pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
				}
				break;
		}
        if($qiniu_pic == true ){//七牛图片显示
            $ary_groupbuy['gp_picture'] = D('QnPic')->picToQn($ary_groupbuy['gp_picture']);
            $ary_groupbuy['gp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_groupbuy['gp_desc']);
            $ary_groupbuy['gp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_groupbuy['gp_mobile_desc']);
        }
        if(strtotime($ary_groupbuy['gp_start_time']) > mktime()){
            $ary_groupbuy['buy_status'] = 3;
        }else{
			if($ary_groupbuy['gp_end_time'] == '2999-00-00 00:00:00'){
				$ary_groupbuy['gp_end_time'] = date('Y-m-d H:i:s',strtotime('next month'));
			}
			if(strtotime($ary_groupbuy['gp_end_time']) < mktime()){
				$ary_groupbuy['buy_status'] = 4;
			}			
		}
        if(0 == $pdt_id) {
		    $pdt_detail['ary_goods_default_pdt'] = reset($pdt_detail['json_goods_pdts']);
        }else {
            foreach($pdt_detail['json_goods_pdts'] as $goods_pdt) {
                if($goods_pdt['pdt_id'] == $pdt_id) {
                    $pdt_detail['ary_goods_default_pdt'] = $goods_pdt;
                    break;
                }
            }
        }
        $ary_groupbuy['gp_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_sale_price'];
        $ary_groupbuy['pdt_set_sale_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_set_sale_price'];

		$ary_goods_pdt['page_detail'] = $pdt_detail;
		$ary_goods_pdt['ary_range_price'] = $rel_bulk_price;
        $ary_goods_pdt = array_merge($ary_goods_pdt, $ary_groupbuy);
//		dump($ary_goods_pdt);die;
		return $ary_goods_pdt;
    }

}
