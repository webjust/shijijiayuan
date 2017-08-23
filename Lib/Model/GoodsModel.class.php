<?php

/**
 * 商品相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Joe
 * @date 2012-12-13
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsModel extends GyfxModel {
     /**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 根据查询条件统计符合条件的商品数
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-26
     * @param array $condition 查询条件
     */
     
    public function GetGoodCount($condition) {
        $res=$this->where($condition)->count();
        return $res;
    }
    
    /**
     * 查询条件结果集
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-26
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @param array $group 分组
     * @param array $limit 查询数量
     */
    public function GetGoodList($condition,$field = '',$group,$limit,$is_thumb = false) {
        $res=M('goods',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
             ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods.g_id')
             ->join('fx_related_goods_category ON fx_related_goods_category.g_id = fx_goods.g_id')
             ->join('fx_goods_category ON fx_goods_category.gc_id = fx_related_goods_category.gc_id')
             ->where($condition)->group($group)->limit($limit['start'],$limit['end'])->select();
		if($is_thumb == true){
			foreach($res as $rkey=>$val){
				if(isset($val['g_picture'])){
					$res[$rkey]['g_picture'] = D('QnPic')->picToQn($val['g_picture'],300,300);
				}
			}			
		}
        return $res;
    }
	
    /**
     * 获得授权线商品
     * @author zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-23
     * @param ary $ary_where
     * @return array $res
     */
     public function getAuthorizeLineGoodData($condition = array(),$field = '*',$group,$limit,$ary_join_where,$is_cache=false) {
		 if($is_cache == true){
			 $obj_query = $this->join($ary_join_where)
				->field($field)->where($condition)->order('fx_goods.g_create_time desc,fx_goods.g_update_time desc')->group($group)->limit($limit['start'],$limit['end']);
			 $res = D('Gyfx')->queryCache($obj_query,$type='',600);
		 }else{
			$res=$this->join($ary_join_where)
				->field($field)->where($condition)->order('fx_goods.g_create_time desc,fx_goods.g_update_time desc')->group($group)->limit($limit['start'],$limit['end'])->select();			
		 }
	  return $res;
     }
     
     /**
     * 获得授权线可铺货的商品
     * @author zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-23
     * @param ary $ary_where
     * @return array $res
     */
     public function getAuthorizeLineGoodCount($condition = array(),$group,$ary_join_where,$is_cache=false) {
		$res=$this->join($ary_join_where)->where($condition)->count('distinct '.C('DB_PREFIX').'goods.g_id');	
        return $res;
     }
     /**
	  *	获取商品规格信息
      * @param int $gid
      * @param array $members
      * @param int $default_pdt_id
      * @param bool $qiniu_pic
      * @return array
	  */
	 public function getDetails($gid=0, $members= array(), $default_pdt_id=0, $qiniu_pic=false) {
         $m_id = is_array($members) ? $members['m_id'] : 0;
		 $member_level_id = intval($members['member_level']['ml_id']);
         $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
         $ary_where = array();
         $ary_where['g_id'] = array('eq', $gid);
         $goods_info = D('GoodsInfo')->field('point')->where($ary_where)->find();
         $point = $goods_info['point'];
         $goodsSpec = D('GoodsSpec');
		 $GoodsProducts = D('GoodsProducts');
		 $ary_goods = array();
         $ary_goods['gid'] = $gid;
        if (!empty($gid)) {
            $ary_product_feild = array('pdt_sn','pdt_stock','pdt_id', 'pdt_sale_price', 'pdt_market_price', 'g_id', 'pdt_weight','pdt_min_num');
            $where = array();
            $where['g_id'] = $gid;
            $where['pdt_status'] = '1';
            $ary_pdt = $products->field($ary_product_feild)->where($where)->limit(150)->select();

            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $ary_goods_spec_list = $ary_goods_properties = $json_goods_pdts = array();
                $int_num = count($ary_pdt);
                $stock_i = 0;
				$inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK');
                foreach ($ary_pdt as $kypdt => $valpdt) {

                    $pdt_id = $valpdt['pdt_id'];
					$valpdt['pdt_set_sale_price'] = $valpdt['pdt_sale_price'];
                    //如果会员为登录状态，优先取一口价->会员等级价-
                    if($m_id){
					    $obj_price = new PriceModel($m_id,1);
                        $valpdt['pdt_sale_price'] = $obj_price->getItemPrice($pdt_id);
						if(isset($inventoryConfig['INVENTORY_STOCK']) && $inventoryConfig['INVENTORY_STOCK']['sc_value']==true){
							$array_inventory_where = array(
								'fx_inventory_pdt_lock.pdt_id' => array('eq',$pdt_id),
								'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR')//, // 过期时间处理
								//'inventory.m_id' => array('eq',$m_id)
							);
							$temp_stock =0;
							$current_user =false;
							$array_inventory_info = $GoodsProducts->getInventoryLockByCondition($array_inventory_where);
							if(!empty($array_inventory_info) ){//商库存分配
								foreach($array_inventory_info as $inventory_value){
									if($inventory_value['m_id']==$m_id){
										$current_user=true;
										$temp_stock=$inventory_value['ipl_num'];
										if(isset($inventoryConfig['INVENTORY_COMMON']) && $inventoryConfig['INVENTORY_COMMON']['sc_value']==true){//共享库存
											$temp_stock=$temp_stock + ($valpdt['pdt_stock'] - $temp_stock);
										}
										break;
									}else{
										$temp_stock +=$inventory_value['ipl_num'];
									}
								}

								if($temp_stock >0){
									if($current_user){
										$valpdt['pdt_stock'] = $temp_stock;
									}else{
										$valpdt['pdt_stock'] = $valpdt['pdt_stock'] - $temp_stock;
									}
								}
							}
						}
					}
                    //如果是单规格商品，把商品价格也一并替换掉
                    if($int_num ==1){
                        $ary_goods['gprice'] = $valpdt['pdt_sale_price'];

                        //货品规格值和货品id对应关系
                        $json_goods_pdts[] = array(
                            'g_id' => $gid,
                            'pdt_id' => $pdt_id,
                            'pdt_stock' => $valpdt['pdt_stock'],
                            'pdt_sale_price' => sprintf("%.2f", $valpdt['pdt_sale_price']),
                            'pdt_set_sale_price' => sprintf("%.2f", $valpdt['pdt_set_sale_price']),
                            'pdt_market_price' => sprintf("%.2f", $valpdt['pdt_market_price']),
                            'spd_ids' => '',
                            'pdt_sn' => $valpdt['pdt_sn'],
                            'pdt_weight' => $valpdt['pdt_weight'],
                            'pdt_min_num' =>$valpdt['pdt_min_num'],
                            'specName' => '',
                            'point' => $point,
							'ipl_num' => $valpdt['ipl_num'],
                        );
                    }
                    //多规格商品
                    else {
                        $ary_config = D('SysConfig')->getConfigs('GY_GOODS', 'VARIABLE_DEPTH');
                        $depth = 2;
                        if(!empty($ary_config)) {
                            $variable_depth = reset($ary_config);
                            $depth = (int)$variable_depth["sc_value"];
                        }
                        //获取商品规格信息
                        $specInfo = $goodsSpec->getProductSpecs($pdt_id, 1, $depth, $qiniu_pic);
                            //本商品所包含的规格(值)列表
                            if (empty($ary_goods_spec_list)) {
                                $ary_goods_spec_list = $specInfo['spec_list'];
                            } else {
                                foreach ($specInfo['spec_list'] as $sk => $spec) {
                                    foreach ($spec['gs_details'] as $skk => $spec_detail) {
                                        $ary_goods_spec_list[$sk]['gs_details'][$skk] = $spec_detail;
                                    }
                                }
                            }
                            //货品规格值和货品id对应关系
                            $json_goods_pdts[$specInfo['pdt_spec_detail_ids']] = array(
                                'g_id' => $gid,
                                'pdt_id' => $pdt_id,
                                'pdt_stock' => $valpdt['pdt_stock'],
                                'pdt_sale_price' => sprintf("%.2f", $valpdt['pdt_sale_price']),
                                'pdt_set_sale_price' => sprintf("%.2f", $valpdt['pdt_set_sale_price']),
                                'pdt_market_price' => sprintf("%.2f", $valpdt['pdt_market_price']),
                                'spd_ids' => $specInfo['pdt_spec_detail_ids'],
                                'pdt_sn' => $valpdt['pdt_sn'],
                                'pdt_weight' => $valpdt['pdt_weight'],
                                'specName' => $specInfo['pdt_spec_display'],
                                'pdt_min_num' =>$valpdt['pdt_min_num'],
                                'point' => $point,
								'ipl_num' => $valpdt['ipl_num'],
                            );
                    }
                    $stock_i += $valpdt['pdt_stock'];
                }
                if(0 == $default_pdt_id) {
                    $ary_goods['ary_goods_default_pdt'] = reset($json_goods_pdts);
                }else {
                    foreach($json_goods_pdts as $goods_pdt) {
                        if($goods_pdt['pdt_id'] == $default_pdt_id) {
                            $ary_goods['ary_goods_default_pdt'] = $goods_pdt;
                            break;
                        }
                    }
                }
                $ary_goods['json_goods_pdts'] = $json_goods_pdts;
                $ary_goods['ary_goods_spec_list'] = $ary_goods_spec_list;
                $ary_goods['gstock'] = $stock_i;
            }
        }

		if(!empty($ary_pdt)){
			//授权线判断是否允许购买
			$ary_goods['authorize'] = true;
			if($m_id){
				$ary_goods['authorize'] = D('AuthorizeLine')->isAuthorize($m_id, $ary_goods['gid'],1);
			}			
			//$ary_goods['skus'] = $ary_pdt;
		}
		//模糊库存提示
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        
        if((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK']==1 ){
            if($stock_data['USER_TYPE']=='all'){
                $stock_data['level'] =true;
            }else{
                $ary_user_level =explode(",",$stock_data['USER_TYPE']);
                $stock_data['level'] = in_array($member_level_id,$ary_user_level);
            }
        }
//        dump($ary_goods);die;
//		echo "<pre>";print_r($stock_data);die;
		return array(
			'stock_data'	=>	$stock_data,
			'page_detail'	=>	$ary_goods,
		);
	 }
}