<?php

/**
 * 商品相关模型层 Model
 * @package Model
 * @version 7.0
 * @author Mithern
 * @date 2013-06-04
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsBaseModel extends GyfxModel {
	/**
     * 此模型的表名为 fx_goods
     * @var string
     */
    protected $tableName = 'goods';
	
	/**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }
}