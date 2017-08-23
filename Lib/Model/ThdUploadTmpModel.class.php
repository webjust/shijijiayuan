<?php

/**
 * 第三方店铺商品上传记录模型
 *
 * @package Model
 * @version 7.4.5
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-10-25
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdUploadTmpModel extends GyfxModel {
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     */

    public function __construct() {
        parent::__construct();
    }
	/**
     * 添加商品上传记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $data
     * @return array $res
     */
    public function addItemRecord($data) {
    	$res=$this->data($data)->add();
    	return $res;
    }
	
	/**
     * 更新商品上传记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $data
     * @return array $res
     */
    public function updateItemRecord($ary_where = array(),$data) {
    	$res=$this->where($ary_where)->save($data); 
    	return $res;
    }
	
	/**
     * 删除商品铺货历史记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-29
	 * @param array $ary_field
     * @param array $condition
     * @return array $res
     */
    public function delItemRecord($condition = array()) {
		$res = $this->where($condition)->delete();
    	return $res;
    }
	
	/**
     * 获取商品上传记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
	 * @param array $ary_field
     * @param array $ary_where
     * @return array $res
     */
    public function getItemRecord($ary_where = array(),$ary_field='*') {
    	$res=$this->where($ary_where)->field($ary_field)->find();
    	return $res;
    }
	
	/**
     * 获取商品铺货历史记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-29
	 * @param array $ary_field
     * @param array $condition
     * @return array $result
     */
    public function getHistroyRecord($condition = array(),$ary_field='*',$group,$order,$limit) {
		$result = $this->join('fx_thd_shops ON fx_thd_shops.ts_sid = fx_thd_upload_tmp.thd_shop_sid')
                    ->join('fx_goods ON fx_goods.thd_gid = fx_thd_upload_tmp.thd_item_id')
					->join('fx_goods_info ON fx_goods_info.g_id = fx_goods.g_id')
					->join('fx_goods_products ON fx_goods_products.g_id = fx_goods.g_id')
                    ->field($ary_field)->where($condition)->group($group)->order($order)
					->limit($limit['start'],$limit['end'])->select();
    	return $result;
    }
	
	/**
     * 统计商品铺货历史记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-29
	 * @param array $ary_field
     * @param array $condition
     * @return array $res
     */
    public function getHistroyRecordCount($condition = array()) {
		$num = $this->join('fx_thd_shops ON fx_thd_shops.ts_sid = fx_thd_upload_tmp.thd_shop_sid')
                    ->join('fx_goods ON fx_goods.thd_gid = fx_thd_upload_tmp.thd_item_id')
                    ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods.g_id')
                    ->where($condition)->count();
    	return $num;
    }
	
	/**
     * 获取商品铺货店铺记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-30
	 * @param array $ary_field
     * @param array $ary_where
     * @return array $res
     */
    public function getItemShopRecord($ary_where = array(),$ary_field='*') {
    	$res=$this->where($ary_where)->field($ary_field)->select();
    	return $res;
    }
}