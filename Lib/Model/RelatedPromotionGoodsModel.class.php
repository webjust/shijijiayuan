<?php

/**
 * 促销商品关系模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedPromotionGoodsModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-30
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 获得参与促销商品
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return array()
     * @date 2013-05-30
     */
    public function Getgoods($pmn_id) {
		//10秒缓存	
       //$result =M('RelatedPromotionGoods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$pmn_id))->select();	
	  $result = D("Gyfx")->selectAllCache("related_promotion_goods","*",array('pmn_id'=>$pmn_id),null,null,null,10);     
	   return $result;
    }
}