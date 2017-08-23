<?php 
	/**
	 * 商品分组相关模型层 Model
	 * @package Model
	 * @version 7.4
	 * @author WangHaoYu
	 * @date 2013-08-27
	 * @license 
	 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
	 */
	class RelatedCouponGoodsGroupModel extends GyfxModel {
		public function getGoodsCoupon($ary_g_id = array()) {
			//通过接受的参数去商品组里面查询商品的`gg_id`
			$ary_gg_id = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>array('IN',$ary_g_id)))->field('gg_id')->select();
			//定义一个空数组放商品组id
			$gg_id = array();
			foreach($ary_gg_id as $v){
				//组装成一维数组
				$gg_id[] = $v['gg_id'];
			}
			//通过商品gg_id去商品促销关联表查 出促销id
			$ary_c_id = $this->where(array('gg_id'=>array('IN',$gg_id)))->field('c_id')->select();
			//定义一个空数组放促销id
			$c_id = array();
			foreach($ary_c_id as $c){
				$c_id[] = $c['c_id'];
			}
			//通过促销id 去`fx_coupon`表里查询 优惠编号 优惠名 
			$ary_coupon_info = M('coupon')->where(array('c_id'=>array('IN',$c_id)))->field('c_id,c_name,c_sn')->select();
			//返回一个二维数组
			return $ary_coupon_info;
		}
	}