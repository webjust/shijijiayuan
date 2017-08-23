<?php
/**
 * 物流公司相关模型
 * @package Model
 * @version 7.0
 * @author listen
 * @date 2013-1-4
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class LogisticModel extends GyfxModel{
    
    /**
     * 会员等级是否包邮
     * @var int
     */
    private $int_ml_free_shipping;
    //初始化操作
    public function __construct() {
	/**
        if(isset($_SESSION['Members']['ml_id']) && $_SESSION['Members']['ml_id']>0){
              $ary_membersLevel = D('MembersLevel')->getMembersLevels($_SESSION['Members']['ml_id']); //会员等级信息
              $this->int_ml_free_shipping = isset($ary_membersLevel['ml_free_shipping']) ? $ary_membersLevel['ml_free_shipping'] : 0;//是否包邮
        }
	**/
    }
    
    /**
     * 根据地址最后一级id获得配送公司和邮费
     * @author listen 
     * @param $cr_id 城市最后一级id
     * @param $bool_pro_lic 是否包邮 默认不包邮
     * @param $ary_goods 存储商品的pdt_id g_id num type
	 * @param $m_id 会员id todo要是后台调用的话这个值需要传
     * @date 2013-1-4
     * modify by wanghaoyu
     */
    public function getLogistic($cr_id,$ary_goods=array(),$m_id=0,$type=0){
        $ary_address = D('CityRegion')->getFullAddressId($cr_id);
		if(empty($m_id)){
			$member = session("Members");
			$User_Grade =D('MembersLevel')->getMembersLevels($member['ml_id']);//会员等级
		}
		else{
		    $ary_mlid = D('Members')->field('ml_id')->where(array('m_id'=>$m_id))->find();
		    $User_Grade =D('MembersLevel')->getMembersLevels($ary_mlid['ml_id']);//会员等级
		}
        arsort($ary_address);
        $ary_logistic = array();
        if (!empty($ary_address) && is_array($ary_address)) {
            foreach ($ary_address as $k => $v) {
                $ary_where = array('cr_id' => $v, 'lc_is_enable' => 1);
                $ary_tmp_logistic = D('ViewLogistic')->where($ary_where)->order('lc_ordernum ASC')->group('lc_abbreviation_name')->select();
                if (!empty($ary_tmp_logistic) && is_array($ary_tmp_logistic)) {
                        $ary_cart = !empty($ary_goods) ? $ary_goods : D('Cart')->ReadMycart();
                        foreach ($ary_tmp_logistic as $k1 => $v1) {
                            $key=$ary_tmp_logistic[$k1]['lc_abbreviation_name'];
                            if(!array_key_exists($key,$ary_logistic)){
                                if((isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1) || $ary_goods['pdt_id'] == 'MBAOYOU'){//会员等级是否包邮
									$ary_logistic_price=0;  
                                }else{
									$is_bulk = 0;
									foreach($ary_goods as $ary_good){
										if(isset($ary_good['type_code']) && ($ary_good['type_code'] == 'bulk') && isset($ary_good['type_id'])){
											$ary_where = array('gp_is_baoyou'=>1,'is_active'=>1,'gp_id'=>$ary_good['type_id'],'deleted'=>0);
											$is_by = D('Groupbuy')->where($ary_where)->getField('gp_is_baoyou');
											if(intval($is_by) == 1){
												$is_bulk = 1;
											}
										}
									}
									if($is_bulk == 1){
										$ary_logistic_price=0; 
									}else{
										$ary_logistic_price = $this->getLogisticPrice($v1['lt_id'], $ary_cart,$m_id,$type); 
									}
                                }
                                $ary_tmp_logistic[$k1]['logistic_price'] = $ary_logistic_price;
                                $ary_logistic[$key] = $ary_tmp_logistic[$k1];
                            }
                            
                        }
                    
                }
            }
        }
        return $ary_logistic;
    }

	/**
	 * 获取满足条件的快递公司列表
	 * @param $cr_id
	 * @param $ary_items
	 * @param $m_id
	 * @param $type
	 *
	 * @return array
	 */
	public function getShippingList($cr_id, $ary_items, $m_id, $type=0) {
		//获取地址全路径
		$ary_address = D('CityRegion')->getFullAddressId($cr_id);
		//会员等级
		$ml_id = D('Members')->where(array('m_id'=>$m_id))->getField('ml_id');
		//获取会员所在会员等级配置
		$user_grade =D('MembersLevel')->getMembersLevels($ml_id);
		arsort($ary_address);
		$ary_return = array();
		if (!empty($ary_address) && is_array($ary_address)) {
			foreach ($ary_address as $k => $v) {
				$ary_where = array('cr_id' => $v, 'lc_is_enable' => 1);
				$ary_logistic = D('ViewLogistic')->where($ary_where)->order('lc_ordernum ASC')->group('lc_abbreviation_name')->select();
				if (!empty($ary_logistic) && is_array($ary_logistic)) {
					foreach ($ary_logistic as $lk => $logistic) {
						$lc_name= $logistic['lc_abbreviation_name'];
						if(!array_key_exists($lc_name,$ary_return)){
							//会员等级包邮
							if((isset($user_grade['ml_free_shipping']) && $user_grade['ml_free_shipping'] == 1) || $user_grade['pdt_id'] == 'MBAOYOU'){
								$logistic_price=0;
							}
							else{
								$logistic_price = $this->getShippingPrice($logistic['lt_id'], $ary_items,$m_id,$type);
							}
							$ary_logistic[$lk]['logistic_price'] = $logistic_price;
							unset($ary_logistic[$lk]['lt_expressions']);
							$ary_return[$lc_name] = $ary_logistic[$lk];
						}
					}
				}
			}
		}

		return $ary_return;
	}

	/**
	 * 获取物流运费
	 * @param $lt_id
	 * @param $ary_items
	 * @param $m_id
	 * @param int $type
	 * @param array $ary_products
	 * @return float|int
	 */
	public function getShippingPrice($lt_id, $ary_items, $m_id, $type=0, $ary_products) {

		//物流费用
		$logistic_price = 0;
		$product_subtotal = 0;
		$free_shipping = 0;
		//获取订单商品总金额
		switch($type) {
			//团购订单
			case '5':
				$price_detail = D('Groupbuy')->getDetailWithPrice($ary_items);
				if(!$price_detail['gp_is_baoyou']) {
					$product_subtotal = $price_detail['gp_subtotal_price'];
				}else {
					$free_shipping = 1;
				}
				break;
			//预售订单
			case '8':
				$price_detail = D('Presale')->getDetailWithPrice($ary_items);
				$product_subtotal = $price_detail['p_subtotal_price'];
				break;
			//秒杀订单
			case '7':
				$price_detail = D('Spike')->getDetailWithPrice($ary_items);
				$product_subtotal = $price_detail['sp_subtotal_price'];
				break;
			//普通订单
			case '0':
                if(!empty($ary_products)) {
                    $mbaoyou_price = array();
                    $mbaoyou_condition = array();
                    foreach($ary_products as $promotion) {
                        if($promotion['pmn_class'] == 'MBAOYOU') {
                            $pmn_id = $promotion['pmn_id'];
                            $mbaoyou_condition[$pmn_id] = json_decode($promotion['pmn_config'], true);
                            foreach($promotion['products'] as $product) {
                                if(!isset($mbaoyou_price[$pmn_id])){
                                    $mbaoyou_price[$pmn_id] = 0;
                                }
                                $mbaoyou_price[$pmn_id] += $product['f_price']*$product['pdt_nums'];
                            }
                        }
                    }
                    //满包邮
                    foreach($mbaoyou_condition as $pmn_id=>$promotion_config) {
                        if($mbaoyou_price[$pmn_id] >= $promotion_config['cfg_cart_start']) {
                            $free_shipping = 1;
                        }
                    }
                }
				$price_detail = D('Orders')->getDetailWithPrice($ary_items, $m_id);
				$product_subtotal = $price_detail['product_subtotal_price'];
				break;
		}
		//不满足包邮条件
		if($free_shipping == 0) {
			//商品重量
			$int_goods_weight = D('Orders')->getGoodsAllWeight($ary_items);
			$ary_logistic = D('ViewLogistic')->where(array(
				'lt_id' => $lt_id,
				'lt_status' => 1,
				'lt_minprice' <= $product_subtotal
			))->select();
			if (!empty($ary_logistic) && is_array($ary_logistic)) {

				foreach ($ary_logistic as $key => $logisticv) {
					$lt_expressions = json_decode($logisticv['lt_expressions']);
					$logistics_first_weight = $lt_expressions->logistics_first_weight;//首重
					$logistics_first_money = $lt_expressions->logistics_first_money;//首重费用
					$logistics_add_weight = $lt_expressions->logistics_add_weight;//续重
					$logistics_add_money = $lt_expressions->logistics_add_money; //续重费用
					$free_shipping_price = $lt_expressions->logistics_configure;
					//如果商品总金额大于等于包邮额度，则运费为0
					$logistic_price_tmp = 0;
					//如果首重大于商品总重,物流费等于首重费费用
					if ($logistics_first_weight >= $int_goods_weight) {
						$logistic_price_tmp = $logistics_first_money;
					}
					//超过首重费用计算
					else if ($logistics_first_weight < $int_goods_weight) {
						$logistic_price_tmp = (ceil((($int_goods_weight - $logistics_first_weight) / $logistics_add_weight)) * $logistics_add_money) + $logistics_first_money;
					}
					if(!empty($free_shipping_price)){
						if($product_subtotal >= $free_shipping_price) {
							$logistic_price_tmp = 0;
						}						
					}					
					//如果同一个物流公司有多种配送方案适用，取最小运费
					//if($logistic_price == 0 || $logistic_price_tmp < $logistic_price) {
						//$logistic_price = $logistic_price_tmp;
					//}
					$logistic_price = $logistic_price_tmp;
				}
			   
			}
		}

		return $logistic_price;
	}

	/**
     * 根据货品的id 和数量 算出邮费
     * @param $lt_id  配送方式id
     * @param array $ary_products 购买商品的数量id
     * @param int $m_id 会员id
     * @param int $type 商品类型 0:普通商品，
     * @author listen Doe <lixin@guanyisoft.com>
     * @date 2013-1-4
     * @return int_$logistic_price 物流费用
     */
    public function getLogisticPrice($lt_id,$ary_products = array(), $m_id = 0, $type=0){
        if($ary_products['pdt_id'] == 'MBAOYOU'){
            return 0;
        }
		if(empty($m_id)){
			$member = session("Members");
			$User_Grade =D('MembersLevel')->getMembersLevels($member['ml_id']);//会员等级
		}
		else{
		    $ary_mlid = D('Members')->field('ml_id')->where(array('m_id'=>$m_id))->find();
		    $User_Grade =D('MembersLevel')->getMembersLevels($ary_mlid['ml_id']);//会员等级
		}
		if($type == 0 && (isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1)){//会员等级是否包邮
			return 0;
		}
        //商品重量
        $int_goods_weight = 0;
        //物流费用
        $int_logistic_price = 0;
		
		$free_shipping = 0;
		foreach($ary_products as $ary_good){
			if($ary_good['type'] == 5 ){
                $ary_good['type_id'] || $ary_good['type_id'] = $ary_good['gp_id'];
				$ary_where = array('gp_is_baoyou'=>1,'is_active'=>1,'gp_id'=>$ary_good['type_id'],'deleted'=>0);
				$is_by = D('Groupbuy')->where($ary_where)->getField('gp_is_baoyou');
				if(intval($is_by) == 1){
                    $free_shipping = 1;
				}
			}
		}
		if($free_shipping == 1){
			$int_logistic_price = 0;
		}else{
			if(!empty($ary_products) && is_array($ary_products)){
				$int_goods_weight = D('Orders')->getGoodsAllWeight($ary_products);
			}

			//1000g/1=8.5 续重500g/1 = 1.5
			//商品重量300g * 5件
			//if 1000g> 300g * 5 $int_logistic_price = 8.5
			//else $int_logistic_price = (((1500 - 1000)/500)*  1.5)+8.5
			if(isset($lt_id)){
			   $ary_logistic = D('ViewLogistic')->where(array('lt_id'=>$lt_id,'lt_status'=>1))->select();
			   if(!empty($ary_logistic) && is_array($ary_logistic)){
				   foreach($ary_logistic as $k=>$v){
					  $lt_expressions = json_decode($v['lt_expressions']);
					  $logistics_first_weight = $lt_expressions->logistics_first_weight;//首重
					  $logistics_first_money = $lt_expressions->logistics_first_money;//首重费用
					  $logistics_add_weight = $lt_expressions->logistics_add_weight;//续重
					  $logistics_add_money = $lt_expressions->logistics_add_money; //续重费用
					  //如果首重大于商品总重物流费等于首重费
					  if($logistics_first_weight>=$int_goods_weight){
						  $int_logistic_price = $logistics_first_money;
					  }
					  else {
						  $int_logistic_price = (ceil((($int_goods_weight-$logistics_first_weight)/$logistics_add_weight))*$logistics_add_money)+$logistics_first_money;
					  }
				   }
			   }
			}			
		}

        //判断会员等级是否包邮
		/**
        if((isset($ary_member['ml_free_shipping']) && $ary_member['ml_free_shipping'] == 1)){
			if($this->int_ml_free_shipping == 1 && $type != 1){
				$int_logistic_price = 0;
			}
        }
        **/
        return $int_logistic_price;
    }
     /**
     * 根据条件找出物流信息
     * @param $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部 
     * @author listen Doe <lixin@guanyisoft.com>
     * @return array 
     * @date 2013-01-07
     */
    public function getLogisticInfo($ary_where=array(),$ary_field=''){
        $ary_logistic = D('ViewLogistic')->field($ary_field)->where($ary_where)->select();
        return $ary_logistic;
    }
}
?>
