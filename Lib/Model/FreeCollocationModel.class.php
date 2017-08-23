<?php
/**
 * 自由推荐模型层 Model
 * @package Model
 * @version 7.8.9
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-11-25
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class FreeCollocationModel extends GyfxModel{
    
    /**
     * 计算自由推荐价格与优惠金额
     *
     * @param array $ary_collocation 搭配数组
     * @return array 搭配价，原价，优惠金额
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-11-25
     */
    public function getFreeCollocationByGid($g_id=0){
		$data = array();
  //自由组合搭配
        $combination = D("FreeCollocation");
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $goodsSpec = D('GoodsSpec');
        $now = date('Y-m-d H:i:s');
        $fc_id=0;
		if($is_cache == true){
			$ary_free_coll_1 = D('Gyfx')->selectAllCache('free_collocation',null,array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1));
		}else{
			$ary_free_coll_1 = $combination->where(array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1))->select();
		}
        //时间不限制
        $array_gid = array();
        foreach ($ary_free_coll_1 as $key_1=>$val_1){
            $ary_tmp_g_id = explode(',',$val_1['fc_related_good_id']);
            if(in_array($g_id,$ary_tmp_g_id)){
                $fc_id = $val_1['fc_id'];
                $array_gid = $ary_tmp_g_id;
            }
        }
        if(empty($array_gid)){
            //查找限制时间的
            $array_where['fc_start_time'] = array('elt',$now);
            $array_where['fc_end_time'] = array('egt',$now);
            $array_where['fc_status'] = 1;
            //$ary_free_coll_2 = $combination->where($array_where)->select();
			$ary_free_coll_2 = D('Gyfx')->selectAllCache('free_collocation',null,$array_where);
            foreach ($ary_free_coll_2 as $key_2=>$val_2){
            $ary_tmp_g_id = explode(',',$val_2['fc_related_good_id']);
                if(in_array($g_id,$ary_tmp_g_id)){
                    $fc_id = $val_2['fc_id'];
                    $array_gid = $ary_tmp_g_id;
                }
            }
        }
        $i_d = 0;
        $view_goods = D('ViewGoods');
        if(!empty($array_gid)){
            foreach ($array_gid as $k=>$v){
                //获取商品基本信息
                $field = 'g.g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic,g_collocation_price as gcoll_price';
                $coll_goods = M("goods_info as gi", C("DB_PREFIX"), "DB_CUSTOM")->field($field)
											->join(C("DB_PREFIX").'goods as g on g.g_id = gi.g_id')
											->where(array('g.g_id'=>$v,'g_status'=>1,'g_on_sale'=>1))->find();
				$coll_goods['gpic'] = D('QnPic')->picToQn($coll_goods['gpic'],200,200);
                if(!empty($coll_goods)){
                    $coll_goods['save_price'] = $coll_goods['gprice'] - $coll_goods['gcoll_price'];
                    //授权线判断是否允许购买
                    $coll_goods['authorize'] = true;
                    if (!empty($coll_goods) && is_array($coll_goods)) {
                        $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods','pdt_collocation_price');
                        $where = array();
                        $where['g_id'] = $v;
                        $where['pdt_status'] = '1';
                        $ary_pdt = $products->field($ary_product_feild)->where($where)->limit()->select();
                        if(!empty($ary_pdt) && is_array($ary_pdt)){
                            $skus = array();
                            foreach($ary_pdt as $kypdt=>$valpdt){
                                $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                                if (!empty($specInfo['color'])) {
                                    if (!empty($specInfo['color'][1])) {
                                        $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                                    }
                                }
                                if (!empty($specInfo['size'])) {
                                    if (!empty($specInfo['size'][1])) {
                                        $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                                    }
                                }
            //                    $ary_pdt['skuName'][$kypdt] = $specInfo['sku_name'];
                                $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                                $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                            }
                            
                            foreach ($skus as $key => &$sku) {
                                $skus[$key] = array_values(array_unique($sku));	
                            }
                            
                        }
                        if (!empty($skus)) {
                            $coll_goods['skuNames'] = $skus;
                        }else{
                            $coll_goods['pdt_id'] = $ary_pdt[0]['pdt_id'];
                        }
                    }
                    if($coll_goods['gid'] == $g_id){
                        $this_goods = $coll_goods;
                    }else{
                        $array_free_goods[$i_d] = $coll_goods;
                        $i_d++;
                    }
                }
            }
            $data['this_coll'] = $this_goods;
            $data['coll_goods'] = $array_free_goods;
            $data['fc_id'] = $fc_id;
        }
		return $data;
    }
}