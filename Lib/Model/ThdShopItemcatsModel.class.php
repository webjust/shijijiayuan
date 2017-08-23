<?php

/**
 * 第三方铺货下载的商品模型
 *
 * @package Model
 * @stage 7.4.5
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-10-29
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdShopItemcatsModel extends GyfxModel {
    
    /**
     * 构造方法
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-29
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 传入一组淘宝店铺分类，将其存入本地第三方店铺分类表缓存
     * $ary_thd_shopcats  淘宝店铺分类一组
     * $int_thd_shopid  第三方店铺在本地店铺表中的ID
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-29
     */
    public function cacheThdShopCats($ary_thd_shopcats = array(), $int_thd_shopid = 0) {
    	$ary_undel = array();
    	foreach ($ary_thd_shopcats as $key => $val) {
    		$ary_undel[] = $val['cid'];
    		//验证当前分类是否已经缓存
    		$where = array('tsi_indentify' => 1, 'ts_sid' => $int_thd_shopid, 'cid' => $val['cid']);
    		$mixed_result = D('Gyfx')->selectOne('thd_shop_itemcats',$ary_field, $where, $ary_order);
    		if (is_array($mixed_result) && count($mixed_result) > 0) {
    			//该分类已缓存，则更新之
    			$ary_update['is_parent'] = (trim($val['parent_cid']) > 0) ? 'false' : 'true';
    			$ary_update['name'] = trim($val['name']);
    			$ary_update['parent_cid'] = trim($val['parent_cid']);
    			$ary_update['sort_order'] = trim($val['sort_order']);
    			$ary_update['cat_type'] = trim($val['cat_type']);
    			$ary_update['tsi_update_time'] = date('Y-m-d H:i:s');
    			try {
    				$res = D('Gyfx')->update('thd_shop_itemcats',array('tsi_id' => $mixed_result['tsi_id']),$ary_update);
    			} catch (PDOException $e) {
    				return false;
    			}
    		} else {
    			//该分类未缓存
    			$ary_add['tsi_indentify'] = 1;
    			$ary_add['ts_sid'] = $int_thd_shopid;
    			$ary_add['cid'] = $val['cid'];
    			$ary_add['is_parent'] = (trim($val['parent_cid']) > 0) ? 'false' : 'true';
    			$ary_add['name'] = trim($val['name']);
    			$ary_add['parent_cid'] = trim($val['parent_cid']);
    			$ary_update['sort_order'] = trim($val['sort_order']);
    			$ary_update['cat_type'] = trim($val['cat_type']);
    			$ary_add['tsi_create_time'] = date('Y-m-d H:i:s');
    			$ary_add['tsi_update_time'] = date('Y-m-d H:i:s');
    			try {
    				$res = D('Gyfx')->insert('thd_shop_itemcats',$ary_add);
    			} catch (PDOException $e) {
    				return false;
    			}
    		}
    	}
    	//删除淘宝上被删除的分类
    	if (count($ary_undel) > 0) {
			try {
    			D('Gyfx')->deleteInfo('thd_shop_itemcats',array('ts_sid'=>$int_thd_shopid,'cid'=>array('not in',implode(',', $ary_undel))));
    		} catch (PDOException $e) {
    			return false;
    		}
    	}
    	return true;
    }
}