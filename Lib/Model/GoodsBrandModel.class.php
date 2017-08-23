<?php

/**
 * 商品品牌相关模型层 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsBrandModel extends GyfxModel {
     /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
	
    /**
     * 拼接sql
     * 
     * @param array $params prepare SQL 中的参数
     * @return boolean
     * @author wangguibin 
     * @date 2013-05-07
     */
    public function execute($sql,array $params=null)
    {
       $statement = explode('?', $sql);
       if ( count($params) != count($statement)-1 ) {
           $sql = $sql . ' with bind parameters: [' . implode(', ', $params) . ']';
       } else {
            $sql = '';
            foreach ( $params as $i => $bind ) {
              $sql .= $statement[$i]
               . (is_string($bind) ? "'".$bind."'" : $bind);
              }
              $sql .= $statement[count($params)];
            }
       return $sql;
    } 
    /**
     *  根据品牌代码与名称返回品牌ID  如果没有则返回0  创建品牌
     * @author wangguibin
     * @date 2013-04-26
	 * $erp_item['pp1dm'],$erp_item['pp1mc']
     */
	public function getBrandIdByCodeName($ary_goods){ 
		$brand_name = array();
		foreach($ary_goods as $erp_item){
			if(!empty($erp_item['pp1mc'])){
				$brand_name[] = $erp_item['pp1mc'];
			}
		}
		$brand_name = array_unique($brand_name);
		$sql1 = 'select gb_id,gb_name from fx_goods_brand where gb_name  in(%s)';
        $sql1 = sprintf($sql1,substr(str_repeat('?,', count($brand_name)),0,-1));
        $sql1 = $this->execute($sql1,$brand_name);
        $noexistBrands = array();
        $existBrands = array();
        $brands = M("goods_brand",C('DB_PREFIX'),'DB_CUSTOM')->query($sql1); 
	    //更新品牌
		foreach($brands as $exist){
			$existBrands[$exist['gb_name']] = $exist['gb_id'];
		}
		//新增品牌
		foreach($brand_name as $brand){
			if(!isset($existBrands[$brand])){
				$noexistBrands[] = $brand;
			}
		}
		$_binds = array();
		$_values = array();
		$tmp_brand = array();
		if(!empty($noexistBrands)){
			$time = date('Y-m-d H:i:s');
			foreach($noexistBrands as $obj){
				$tmp_brand = array(
					'gb_name' => $obj,
					'gb_create_time'=>$time,
					'gb_status'=>'1'	
				);	
				$_binds = array_merge($_binds,array_values($tmp_brand));
				$_value = substr(str_repeat('?,', count($tmp_brand)),0,-1);
				$_values[] = "({$_value})";				
			}
			$_columns = implode(',', array_keys($tmp_brand));
			$_values = implode(',', $_values);
			$sql = "replace into fx_goods_brand({$_columns}) values {$_values}";	
			$sql = $this->execute($sql,$_binds);
			$res = M("goods_brand",C('DB_PREFIX'),'DB_CUSTOM')->execute($sql);
		}
		$brands = M("goods_brand",C('DB_PREFIX'),'DB_CUSTOM')->query($sql1); 
		foreach($brands as $exist){
			$existBrands[$exist['gb_name']] = $exist['gb_id'];
		}
		foreach($ary_goods as &$val){
			$val['gb_id'] = !empty($existBrands[$val['pp1mc']])?$existBrands[$val['pp1mc']]:"";
		}
		return $ary_goods;
	}
	
	
}