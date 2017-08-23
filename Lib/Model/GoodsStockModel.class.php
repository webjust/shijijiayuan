<?php

/**
 * 商品库存读取公共类
 * @package Model
 * @version 7.0
 * @author  jiye
 * @date 2012-12-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsStockModel extends GyfxModel {

    /**
     * 根据货品ID获取可下单库存
     *
     * 此方法提供给前台、后台调用获取某个用户的可下单库存（为后续可能存在的库存分配功能做准备）
     *
     * @author Mithern
     * @date 2013-07-04
     * @version 1.0
     */
    public function getProductStockByPdtid($int_pdt_id, $int_member_id = 0, $cr_id = '0',$is_cache=0) {
        if ($cr_id == '0') {
            //获取当前SKU的详细信息
            $array_pdt_info = D("GoodsProductsTable")->where(array("pdt_id" => $int_pdt_id))->field('g_id,pdt_stock,pdt_id,pdt_is_combination_goods')->find();
            //如果指定的规格不存在，则返回数字0
            if (!is_array($array_pdt_info) || empty($array_pdt_info)) {
                return 0;
            }
            /**
             * 分销商库存处理
             * @author Tom <helong@guanyisoft.com>
             * @date 2014-09-12
             */
            $inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_STOCK',null,null,$is_cache);
            $inventoryCommon = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_COMMON',null,null,$is_cache);   // 开启共享库存
            if(isset($inventoryConfig['INVENTORY_STOCK']['sc_value']) && $inventoryConfig['INVENTORY_STOCK']['sc_value'] == 1){
                $array_inventory_where = array(
                    'fx_inventory_pdt_lock.pdt_id' => array('eq',$int_pdt_id),
                    'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'), // 过期时间处理
                    'inventory.m_id' => array('neq',$int_member_id)
                    );
                if(empty($int_member_id)) unset($array_inventory_where['inventory.m_id']);
                $lock_stock = $this->getInventoryStockByCondition($array_inventory_where);
                if(!empty($lock_stock)){
                    $array_pdt_info["pdt_stock"] -= $lock_stock;
                }
                // 关闭共享库存
                if(!isset($inventoryCommon['INVENTORY_COMMON']['sc_value']) || $inventoryCommon['INVENTORY_COMMON']['sc_value'] == 0){
                    $array_inventory_common_where = array(
                        'fx_inventory_pdt_lock.pdt_id' => array('eq',$int_pdt_id),
                        'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'), // 过期时间处理
                        'inventory.m_id' => array('eq',$int_member_id)
                        );
                    $lock_stock_common = $this->getInventoryStockByCondition($array_inventory_common_where);
                    if(!empty($lock_stock_common) && !empty($int_member_id)){
                        $array_pdt_info["pdt_stock"] = $lock_stock_common;
                    }
                }

            }
            //判断是否是组合商品，如果是组合商品，则需要计算组合商品的库存
            if (1 == $array_pdt_info["pdt_is_combination_goods"]) {
                /**
                 * 组合商品可下单库存的算法说明
                 *  
                 * 我们假设以下变量A，B，C，D，其中A为一件组合商品，由3件B，1件C和5件D组成；
                 * 在仓库中，B、C、D的库存分别是60、30和50件；
                 *
                 * 那么A的可下单库存应该是（C的实际库存数）除以（组合商品A中的C的数量）= 50/5=10件
                 * 
                 * @by Mithern 2013-07-05
                 * 
                 */
                //获取此组合商品关联的所有的PDT_ID和构成数量
                $array_related_pdt_info = D("ReletedCombinationGoods")->where(array("pdt_id" => $int_pdt_id))->getField("releted_pdt_id,com_nums", true);
                //print_r($array_pdt_ids);exit;
                //如果此组合商品没有规格组成数组，返回0
                if (!is_array($array_related_pdt_info) || empty($array_related_pdt_info)) {
                    return 0;
                }
                $array_pdt_ids = array();
                foreach ($array_related_pdt_info as $key => $val) {
                    $array_pdt_ids[] = $key;
                }
                //获取组成这个组合商品的真实库存数量
                $decimal_stock = D("GoodsProductsTable")->where(array("pdt_id" => array("IN", $array_pdt_ids)))->getField("pdt_id,pdt_stock", true);
                //初始值为组合商品第一个明细的实际库存除以组成数量并向下取整
                $first_pft_id = $array_pdt_ids[0];
                $min_pdt_stock = floor($decimal_stock[$first_pft_id] / $array_related_pdt_info[$first_pft_id]);
                //对组成组和商品的明细进行遍历，取出最小值
                foreach ($array_related_pdt_info as $key => $val) {
                    $tmp_stock = floor($decimal_stock[$key] / $val);
                    if ($tmp_stock < $min_pdt_stock) {
                        $min_pdt_stock = $tmp_stock;
                    }
                }
                return $min_pdt_stock;
            }
        } else {
            $array_pdt_info = $this->getProductsWarehouseStock('310107', $int_pdt_id);
        }
        //普通的SKU，返回可下单库存即可
        return $array_pdt_info["pdt_stock"];
    }

    /**
     * 获取分销商分配库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-17
     */
    public function getInventoryStockByCondition($ary){
        $lock_stock = 0;
        $array_inventory_info = D('inventory_pdt_lock')
                    ->where($ary)
                    // ->field("inventory.`iny_num` as lock_stock") // 该方法不能排除过期问题
                    ->field("sum(fx_inventory_pdt_lock.`ipl_num`) as lock_stock")
                    ->join("fx_inventory_lock as inventory on(fx_inventory_pdt_lock.iny_id=inventory.iny_id)")
                    ->find();
        // echo D('inventory_pdt_lock')->getLastSql();echo "<br />";
        if(!empty($array_inventory_info) && isset($array_inventory_info)) $lock_stock = $array_inventory_info['lock_stock'];
        return $lock_stock;
    }

    /**
     * 获取区域商品库存
     * @author Jerry(zhanghao)
     * @param int $g_id 商品id
     * @param int $pdt_id 货品id
     * @param int $cr_id 区域id
     */
    public function getRegionStock($g_id,$pdt_id=0,$cr_id=0) {
        $warehouses = 0;
        if($cr_id) {
            //如果区域id存在，则通过区域id获取该区域的所有可用仓库
            $cr_path = D('CityRegion')->getPath($cr_id);
            $ary_path = explode('|',trim($cr_path,'|'));
            $w_ids = D('Warehouse')->getWarehouseByRegion($ary_path);
            if(is_array($w_ids)) {
                $warehouses_tmp = array();
                foreach ($w_ids as $w) {
                    $warehouses_tmp[] = $w['w_id'];
                }
                $warehouses = $warehouses_tmp;
            }
        }
        return $this->getWarehouseStock($g_id,$pdt_id,$warehouses);
        
    }
    /**
     * 获取仓库中商品库存
     * @author Jerry(zhanghao)
     * @param int $g_id 商品id
     * @param int $pdt_id 货品id
     * @param array $warehouses 仓库
     * 
     */
    public function getWarehouseStock($g_id,$pdt_id=0,$warehouses=0) {
        $stock = array(
            'total' => 0,
            'ava' => 0,
            'freeze' =>0
        );
        $where = array(
            'g_id' => $g_id
        );
        if($pdt_id) {
            //如果没有货品id则获取商品下所有货品库存的总和
            $where['pdt_id'] = $pdt_id;
        }
        if(is_array($warehouses) && !empty($warehouses)) {
             $where['w_id'] = array('IN',$warehouses);   
        } else if(is_int($warehouses) && $warehouses > 0){
            $where['w_id'] = $warehouses;
        }
        $data = D('WarehouseStock')->where($where)->field('pdt_total_stock,pdt_stock,pdt_freeze_stock')->select();
        if(is_array($data)) {
            foreach ($data as $s) {
                $stock['total'] += (int)$s['pdt_total_stock'];
                $stock['ava'] += (int)$s['pdt_stock'];
                $stock['freeze'] += (int)$s['pdt_freeze_stock'];
            }
        }
        return $stock;
    }
    
    /**
     * 分仓库存
     * @param int $cr_id    配送区域ID
     * @param int $int_pdt_id 货品ID
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-26
     * 
     */
    public function getProductsWarehouseStock($cr_id = '310107', $int_pdt_id = '') {
        //判断当前商品是否为预售
        $goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_id'=>$int_pdt_id))->find();
        $goods = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id'=>$goods_products['g_id']))->find();
        if($goods['g_pre_sale_status'] == 1){
            $goods_products['pdt_total_stock'] = 9999;
            $goods_products['pdt_stock'] = 9999;
            return $goods_products;
        }
    
        $model = M('warehouse_delivery_area as `wda`', C('DB_PREFIX'), 'DB_CUSTOM');
        $where = array();
        $where['cr_id'] = $cr_id;
        //$where['pdt_id'] = $int_pdt_id;
        $ary_result = $model->where($where)->find();
        if(empty($ary_result)){
        	$cr_info = D('CityRegion')->getFullAddressId($cr_id);
		    $cr_id = $cr_info[2];
		    $where['cr_id'] = $cr_id;
        	$ary_result = $model->where($where)->find();
			if(empty($ary_result)){//省
				$cr_id = $cr_info[1];
				$where['cr_id'] = $cr_id;
				$ary_result = $model->where($where)->find();
			}			
        }
        if (!empty($ary_result) && is_array($ary_result)) {
            $arr_warehouse = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM')->where(array("w_id" => $ary_result['w_id'], "pdt_id" => $int_pdt_id))->find();
            if (!empty($arr_warehouse) && is_array($arr_warehouse)) {
                return $arr_warehouse;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * 分仓库存
     * @param int $cr_id    配送区域ID
     * @param int $int_pdt_id 货品ID
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-26
     * 
     */
    public function getGoodsWarehouseStock($cr_id = '310107', $g_id = '') {
        //判断当前商品是否为预售
        $goods = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id'=>$g_id))->find();
        if($goods['g_pre_sale_status'] == 1){
            $array_goods_product = M('goods_products')->where(array('g_id'=>$g_id))->select();
            foreach($array_goods_product as &$value){
                $value['pdt_stock'] = 9999;
            }
            return $array_goods_product;
        }
        
        $model = M('warehouse_delivery_area as `wda`', C('DB_PREFIX'), 'DB_CUSTOM');
        $where = array();
        $where['cr_id'] = $cr_id;
        //$where['pdt_id'] = $int_pdt_id;
        $ary_result = $model->where($where)->find();
        if(empty($ary_result)){
        	$cr_info = D('CityRegion')->getFullAddressId($cr_id);
		    $cr_id = $cr_info[2];
		    $where['cr_id'] = $cr_id;
        	$ary_result = $model->where($where)->find();
			if(empty($ary_result)){//省
				$cr_id = $cr_info[1];
				$where['cr_id'] = $cr_id;
				$ary_result = $model->where($where)->find();
			}
        }
        if (!empty($ary_result) && is_array($ary_result)) {
            $arr_warehouse = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM')->where(array("w_id" => $ary_result['w_id'], "g_id" => $g_id))->select();
            if (!empty($arr_warehouse) && is_array($arr_warehouse)) {
                return $arr_warehouse;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

}