<?php

/**
 * 收藏商品模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-20
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CollectGoodsModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 查询收藏商品
     * @author Zhangjiasuo
     * @param int $mid 用户id
     * @return   array
     * @date 2013-04-20
     */
    public function GetCollectRecord($mid) {
        $ary_res=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('m_id'=>$mid))->select();
        if(!empty($ary_res)){
            $result=array();
            foreach($ary_res as $value){
                $g_id=$value['g_id'];
                $result[$g_id]=$g_id;
            }
            return $result;
        }
        return $ary_res;
    }
    
    /**
     * 查询收藏商品
     * @author Zhangjiasuo
     * @param int $mid 用户id
     * @return   array
     * @date 2013-04-20
     */
    public function GetCollectGood($mid) {
         $ary_collect=$this->field('g_id')->where(array('m_id'=>$mid))->select();
         foreach ($ary_collect as $key => $val) {
            $arr_temp=array();
            $field=array('fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_products.pdt_market_price,fx_goods_products.pdt_sn');
            $where=array('g_id'=>$val['g_id']);
            $arr_temp = D(GoodsProducts)->GetProductList($where,$field);
            $arr_data[$key] =$arr_temp[0];
            $arr_data[$key]['g_picture']=getFullPictureWebPath($arr_data[$key]['g_picture']);
            $arr_data[$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($val['g_id']);
            $arr_data[$key]['g_id'] = $val['g_id'];
         }
        return array('num'=>count($ary_collect),'data'=>$arr_data);
    }
    
    /**
     * 加入收藏夹
     * @author Zhangjiasuo
     * @param int $mid 用户id
     * @param int $pdt_id 货品号
     * @return   array
     * @date 2013-04-20
     */
    public function AddCollect($mid,$pdt_id,$is_cahce=0) {
		if($is_cache == 1){
			$ary_good = D('Gyfx')->selectOneCache('goods_products','g_id', array('pdt_id'=>$pdt_id), $ary_order=null,300);
			$g_id = $ary_good['g_id'];
		}else{
	        $g_id = M('goods_products', C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$pdt_id))->getField('g_id');		
		}
        $ary_res=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$mid,'g_id'=>$g_id))->find();
        if(empty($ary_res)){
            $data['m_id'] = $mid;
            $data['g_id'] = $g_id;
            $data['add_time']= date('Y-m-d H:i:s');
            if(false === M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->add($data)){
                return array('status' => false, 'message' => '添加收藏失败');
            }else{
				D('Gyfx')->deleteOneCache('collect_goods','count(*) as num', array('g_id'=>$ary_goods['g_id']), $ary_order=null,300);	
                return array('status' => true, 'message' => '收藏成功');
            } 
        }else{
            return array('status' => false, 'message' => '您已收藏');
        }
    }
    /**
     * 批量收藏
     * @author Zhangjiasuo
     * @param ary $pdt_id 货品号
     * @return   array
     * @date 2013-04-20
     */
    public function AddAllCollect($data) {
        $ary_res=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->addAll($data); 
    }
    /**
     * 删除收藏中的商品
     * @author Zhangjiasuo
     * @param ary $pdt_id 货品号
     * @return   bool
     * @date 2013-04-20
     */
    public function DelCollect($pdt_id,$mid) {
        $res=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$pdt_id,'m_id'=>$mid))->delete(); 
        return $res;
    }
}