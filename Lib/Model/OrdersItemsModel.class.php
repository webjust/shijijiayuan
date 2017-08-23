<?php

/**
 * 订单详情模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersItemsModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-03
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 获得发货订单
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @param array $data
     * @return string
     */
    public function GetShipOrders() {
        $where['fx_orders_items.oi_ship_status']=2;
        $res_orders = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                     ->field('fx_orders_items.o_id,fx_orders.m_id,fx_orders.o_receiver_email')
                     ->join('fx_orders on fx_orders_items.o_id=fx_orders.o_id')
                     ->group('fx_orders_items.o_id')
                     ->where($where)->select();
        return $res_orders;
    }
    /**
     * 更新订单发货状态
     * @author Zhangjiasuo
     * @date 2013-04-03
     */
    public function UpdateOrderShipStatus($id) {
        $where['o_id'] = $id;
        $data['oi_ship_status']=2;
        $item_status=M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($data);
    }
    
    /**
     * 根据定的那ID获取订单明细信息
     * @author haophper
     * @param int $int_oid <p>订单ID</p>
     * @param array $ary_field<p>需要获取的字段，默认为所有</p>
     * @date 2013-8-15
     * @return array
     */
     public function getOrderItemsInfo($int_oid, $ary_field='*') {
         return $this->where(array('o_id' => $int_oid))->field($ary_field)->select();
     }
     
     /**
      * 更新商品价格
      * @author haophper
      * @author intger $int_o_id <p>需要更新的订单的订单id</p>
      * @author intger $int_oi_id <p>需要更新的明细id</p>
      * @param array $ary_update <p>需要更新的数据</p>
      * @date 2013-8-15
      * @return bool
      */
      public function updateOrderItemsCostPrice($int_o_id, $int_oi_id, $ary_update) {
          return $this->where(array('o_id' => $int_o_id, 'oi_id' => $int_oi_id))->save($ary_update);
      }
	  
	/**
     * 活动订单发货商品
     * @author Zhangjiasuo
     * @date 2015-05-06
     */
    public function GetOrderShipItem($where=array(),$field='*',$order='') {
		$data=array();
        $res = $this->field($field)
					->join('fx_orders as o on(fx_orders_items.o_id=o.o_id)')
					->join('fx_orders_refunds as ors on(fx_orders_items.o_id=ors.o_id)')
					->where($where)->order($order)
					->select();
		if($res!=''){
			//$res=array_unique($res);
			foreach($res as $key=>$value){//过来多次退款驳回最后退款成功
				if($value['or_finance_verify']!=1){
					$data[]=$res[$key];
				}
			}
		}
		return $data;
    }
	
	/**
     * 处理订单明细金额
	 * 明细金额等于订单商品总金额$ary_orders['o_goods_all_price'] 每个明细总金额$ary_good['']
	 * 红包、储值卡、金币、积分oi_bonus_money、oi_cards_money、oi_jlb_money、oi_point_money
	 * @param $pro_datas 商品促销详情
     * wangguibin@guanyisoft.com
	 * date 2014-09-16
     */		
	public function getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas){
		//如果优惠券存在计算每个明细优惠券金额
		//优惠券总金额为oi_coupon_menoy $ary_orders['o_coupon_menoy'] 使用优惠券的商品总金额、每个明细的商品总金额
		$int_coupon_total_money = 0;
		$int_pdt_total_price = 0;
		$int_coupon_num = 0;
		//商品总数量
		$int_good_total_num = 0;
		$ary_orders_gifts = array();
		foreach ($ary_orders_goods as $k => &$v) {
			//促销信息
			foreach ($pro_datas as $p_key=>$vals) {
				$int_promotion_count = count($vals['products']);
                $i = 0;
				//促销总金额
				$int_total_promotion_price = 0;
				foreach ($vals['products'] as $key => $val) {
					if (($val['type'] == $v['type']) && ($val['pdt_id'] == $v['pdt_id'])) {
						if (!empty($vals['pmn_name'])) {
							$v['pmn_name'][] = $vals['pmn_name'];
						}
						//购物车优惠优惠金额放到订单明细里拆分
						if($p_key != 0 && !empty($vals['pro_goods_discount'])){
							if($int_promotion_count == $i+1){
								$v['promotion_price'] = $vals['pro_goods_discount']-$int_total_promotion_price;
							}else{
								$v['promotion_price'] = sprintf("%.2f", ($val['f_price']*$val['pdt_nums']/$vals['goods_total_price'])*$vals['pro_goods_discount']);
								$int_total_promotion_price = $int_total_promotion_price+$v['promotion_price'];
							}
						}
							/**
						if(isset($vals['gifts'])) {
                            foreach($vals['gifts'] as $gift) {
                                $gift['pdt_nums'] = $gift['num'];
                                $ary_join = array(
                                    'fx_goods as g on gp.g_id = g.g_id',
                                    'fx_goods_info as gi on gi.g_id = gp.g_id'
                                );
                                $gift_detaile = D('GoodsProductsTable')->alias('gp')
                                    ->join($ary_join)
                                    ->where(array(
                                    'gp.pdt_id'    =>  $gift['pdt_id']
                                    ))->find();
                                //echo D('GoodsProductsTable')->getLastSql();die;
                                $gift = array_merge($gift_detaile, $gift);
                                $ary_orders_gifts[] = $gift;
                            }
						}**/
					}
                    $i ++;
				}

			}
            if(!empty($v['pmn_name'])) {
                $v['pmn_name'] = implode(' ',array_unique($v['pmn_name']));
            }
			if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
			}else{
				$v['pdt_total_price'] = 0;
			}
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						$int_good_total_num +=1;
						// 购买单价
						if (isset($v [0] ['type']) && $v [0] ['type'] == 4 && $item_info['fc_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} elseif (isset($v [0] ['type']) && $v [0] ['type'] == 6 && $item_info['fr_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} 
						$int_pdt_total_price +=$item_info['pdt_total_price'];
						if($ary_coupon['status'] == 'success'){
							$is_use_coupon = 0;
							foreach ($ary_coupon['msg'] as $coupon){
								//计算参与优惠券使用的商品
								if($coupon['gids'] == 'All'){
									$is_use_coupon = 1;
								}else{
									if(in_array($item_info['g_id'],$coupon['gids'])){
										$is_use_coupon = 1;
									}
								}
							}
							if($is_use_coupon == 1){
								$int_coupon_total_money += $item_info ['f_price'] * $item_info ['pdt_nums'];
								$item_info['is_use_coupon'] = 1;
								$int_coupon_num +=1;
							}
						}						
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				}
                else {
					$int_good_total_num +=1;
					// 商品积分
					if (isset($v ['type']) && $v ['type'] == 1) {
					} else {
						$v['pdt_total_price'] += $v ['f_price'] * $v ['pdt_nums']-$v['promotion_price'];
					}
					$int_pdt_total_price +=$v['pdt_total_price'];
					if($ary_coupon['status'] == 'success'){
						$is_use_coupon = 0;
						foreach ($ary_coupon['msg'] as $coupon){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$is_use_coupon = 1;
							}else{
								if(in_array($v['g_id'],$coupon['gids'])){
									$is_use_coupon = 1;
								}
							}
						}
						if($is_use_coupon == 1){
							$int_coupon_total_money += $v ['f_price'] * $v ['pdt_nums']-$v['promotion_price'];
							$v['is_use_coupon'] = 1;
							$int_coupon_num +=1;
						}
					}
				}
			}
		}
        $ary_orders_goods = array_merge($ary_orders_goods, $ary_orders_gifts);
		//当前已计算优惠券金额
		$int_exist_coupon_num = 0;
		$exist_coupon_money = 0;
		//红包
		$int_exist_bonus_num = 0;
		$exist_bonus_money = 0;
		//储值卡
		$int_exist_cards_num = 0;
		$exist_cards_money = 0;	
		//金币
		$int_exist_jlb_num = 0;
		$exist_jlb_money = 0;	
		//积分
		$int_exist_point_num = 0;
		$exist_point_money = 0;		
		foreach ($ary_orders_goods as $k => &$v) {
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						if($item_info['is_use_coupon'] == 1){
							if($int_exist_coupon_num+1 == $int_coupon_num){
								$item_info['oi_coupon_menoy'] = $ary_orders['o_coupon_menoy']-$exist_coupon_money;
							}else{
								$item_info['oi_coupon_menoy'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']); 
								$exist_coupon_money +=$item_info['oi_coupon_menoy'];
								$int_exist_coupon_num +=1;
							}						
						}
						//使用红包
						if(!empty($ary_orders['o_bonus_money'])){
							if($int_exist_bonus_num+1 == $int_good_total_num){
								$item_info['oi_bonus_money'] = $ary_orders['o_bonus_money']-$exist_bonus_money;
							}else{
								$item_info['oi_bonus_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
								$exist_bonus_money +=$item_info['oi_bonus_money'];
								$int_exist_bonus_num +=1;
							}								
						}
						/**
						//使用储值卡
						if(!empty($ary_orders['o_cards_money'])){
							if($int_exist_cards_num+1 == $int_good_total_num){
								$item_info['oi_cards_money'] = $ary_orders['o_cards_money']-$exist_cards_money;
							}else{
								$item_info['oi_cards_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_cards_money']); 
								$exist_cards_money +=$item_info['oi_cards_money'];
								$int_exist_cards_num +=1;
							}								
						}	
						//使用金币
						if(!empty($ary_orders['o_jlb_money'])){
							if($int_exist_jlb_num+1 == $int_good_total_num){
								$item_info['oi_jlb_money'] = $ary_orders['o_jlb_money']-$exist_jlb_money;
							}else{
								$item_info['oi_jlb_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
								$exist_jlb_money +=$item_info['oi_jlb_money'];
								$int_exist_jlb_num +=1;
							}								
						}**/
						//使用积分
						if(!empty($ary_orders['o_point_money'])){
							if($int_exist_point_num+1 == $int_good_total_num){
								$item_info['oi_point_money'] = $ary_orders['o_point_money']-$exist_point_money;
							}else{
								$item_info['oi_point_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
								$exist_point_money +=$item_info['oi_point_money'];
								$int_exist_point_num +=1;
							}								
						}							
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				} else {
					if($v['is_use_coupon'] == 1){
						if($int_exist_coupon_num+1 == $int_coupon_num){
							$v['oi_coupon_menoy'] = $ary_orders['o_coupon_menoy']-$exist_coupon_money;
						}else{
							$v['oi_coupon_menoy'] = sprintf("%.2f", ($v['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']);
                            $exist_coupon_money +=$v['oi_coupon_menoy'];
                            $int_exist_coupon_num +=1;
						}
					}
					//使用红包
					if(!empty($ary_orders['o_bonus_money'])){
						if($int_exist_bonus_num+1 == $int_good_total_num){
							$v['oi_bonus_money'] = $ary_orders['o_bonus_money']-$exist_bonus_money;
						}else{
							$v['oi_bonus_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
							$exist_bonus_money +=$v['oi_bonus_money'];
							$int_exist_bonus_num +=1;
						}								
					}
					/**
					//使用储值卡
					if(!empty($ary_orders['o_cards_money'])){
						if($int_exist_cards_num+1 == $int_good_total_num){
							$v['oi_cards_money'] = $ary_orders['o_cards_money']-$exist_cards_money;
						}else{
							$v['oi_cards_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_cards_money']); 
							$exist_cards_money +=$v['oi_cards_money'];
							$int_exist_cards_num +=1;
						}								
					}	
					//使用金币
					if(!empty($ary_orders['o_jlb_money'])){
						if($int_exist_jlb_num+1 == $int_good_total_num){
							$v['oi_jlb_money'] = $ary_orders['o_jlb_money']-$exist_jlb_money;
						}else{
							$v['oi_jlb_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
							$exist_jlb_money +=$v['oi_jlb_money'];
							$int_exist_jlb_num +=1;
						}								
					}**/
					//使用积分
					if(!empty($ary_orders['o_point_money'])){
						if($int_exist_point_num+1 == $int_good_total_num){
							$v['oi_point_money'] = $ary_orders['o_point_money']-$exist_point_money;
						}else{
							$v['oi_point_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
							$exist_point_money +=$v['oi_point_money'];
							$int_exist_point_num +=1;
						}								
					}					
				}
			}		
		}
		return $ary_orders_goods;
	}
}