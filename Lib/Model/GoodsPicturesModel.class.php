<?php

/**
 * 商品图片相关模型层 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsPicturesModel extends GyfxModel {
     /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
    
     /**
     * 添加商品图片
     * @author wangguibin
     * @date 2013-04-26
     */    
    public function addGoodsPictures($ary_imgs,$agood){
		if(!empty($ary_imgs['g_picture']) && is_array($ary_imgs['g_picture'])){
                if(!empty($ary_imgs['g_picture']) && isset($ary_imgs['g_picture'][1])){
                   $g_picture = $ary_imgs['g_picture'][1];
                   if(count($ary_imgs['g_picture']) > 1){
	                  M("goods_pictures",C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id' => $agood))->delete();
	                  $ary_picdata = array();
	                  foreach($ary_imgs['g_picture'] as $keypic=>$valpic){
	                     if($keypic != '1'){
		                   $ary_picdata['g_id'] = $agood;
		                   $ary_picdata['gp_picture'] = $valpic;
		                   $ary_picdata['gp_create_time'] = date("Y-m-d H:i:s");
		                   $ary_picresult = M("goods_pictures",C('DB_PREFIX'),'DB_CUSTOM')->add($ary_picdata);
	                	}                
	         		}
                   }
				}
            }else{
                M("goods_pictures",C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id' => $agood))->delete();
                $g_picture = '';
            }
            //将图片信息写入分销数据库    	
            return $g_picture;
    }
	
	/**
     * 获取商品图片
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
	 * @param array $ary_field
     * @param array $ary_where
     * @return array $res
     */
    public function getItemPicture($ary_where = array(),$ary_field='*') {
    	$res=$this->where($ary_where)->field($ary_field)->select();
    	return $res;
    }
       
}