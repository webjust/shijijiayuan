<?php

/**
 * 退款单明细模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersRefundsItemsModel extends GyfxModel {

	public function addAll($insert_data) {
		$tag = true;
		foreach($insert_data as $insert){
			$tag = M('OrdersRefundsItems',C('DB_PREFIX'),'DB_CUSTOM')->data($insert)->add();
			if(!$tag){
				break;
			}
		}
		return $tag;
    }
}