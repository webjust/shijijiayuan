<?php

/**
 * 商品SKU相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Mithern
 * @date 2013-06-04
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsProductsTableModel extends GyfxModel {
	/**
     * 此模型的表名为 fx_goods
     * @var string
     */
    protected $tableName = 'goods_products';
	
	/**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取多规格商品的最低价
     * @param $g_id
     * @return mixed
     */
    public function getGoodsMinPrice($g_id) {
        $goods_product = $this
            ->field('pdt_sale_price, pdt_cost_price, pdt_market_price')
            ->where(array(
                'g_id' => $g_id
            ))
            ->order('pdt_sale_price asc')
            ->find();
        //echo $this->getLastSql();die;
        return $goods_product;
    }
}