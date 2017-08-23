<?php

/**
 * 货品相关模型层 Model
 * 前台购物车
 * @package Model
 * @version 7.0
 * @author  jiye
 * @date 2012-12-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CartModel extends GyfxModel {

    const sn = 'mycart';

    private $m_id='';
    /**
     * 获取用户id
     * @author Nick
     * @data 2015-09-09
     * @param int $m_id
     * @return   int
     */
    function setMid($m_id)
    {
        $this->m_id = (int)$m_id;
    }

    /**
     * 获取用户id
     * @author Zhangjiasuo
     * @data 2013-04-17
     * @return   int
     */
    function GetMid()
    {
        if($this->m_id) {
            return $this->m_id;
        }
        $member = session("Members");
        return $member['m_id'];
    }
    /**
     * 获取用户购物车已存在的商品
     * @author Zhangjiasuo
     * @data 2013-04-17
     * @return   array
     */
    function GetData(){
        $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $this->GetKey()))->find();
        $data = unserialize(urldecode($row['value']));
        if(is_array($data)&& !empty($data)){
            return $data;
        }else{
            return $data=array();
        }   
    }
    /**
     * 获取用户对应的购物车key
     * @author Zhangjiasuo
     * @data 2013-04-17
     * @return   string
     */
    protected function GetKey()
    {
        return base64_encode( self::sn . $this->getMid());
    }
    /**
     * 读取购物车商品
     * @author Zhangjiasuo
     * @data 2013-04-17
     * @return   bool
     */
    function ReadMycart()
    {
        try {
            $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $this->GetKey()))->find();
            if ( $row ) {
                $data = unserialize(urldecode($row['value']));
                if ( isset($data) && is_array($data)) {
                    return $data;
                } else{
                    //Tom_Log::error('购物车数据已经损坏');
                    return array();
                }
            }
            return array();
        } catch ( Exception $e ) {
            //Tom_Log::error($e->getMessage());
            return array();
        }
    }
    /**
     * 存储购物车商品
     * @param  array('pdt_id' =>001，'num'=>1);
     * @author Zhangjiasuo
     * @data 2013-04-17
     * @return   bool
     */
    function WriteMycart($data)
    {
        $str = urlencode(serialize($data));
        $key = $this->getKey();
        $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $key))->find();
        
        $now = date('Y-m-d H:i:s');
        try {
            if ( $row ) {
                if ( $row['value'] != $str ) {
                    $update_data['value']=$str;
                    $update_data['modify_time']=$now; 
                    M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
                }
            }
            else {
                $insert_data['key']=$key;
                $insert_data['value']=$str;
                $insert_data['create_time']=$now;
                $insert_data['modify_time']=$now;                         
                M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->add($insert_data); 
            }
        } catch ( Exception $e ) {
            return false;
        }
        return true;
    }
    /**
     * 清空购物车
     * @author Zhangjiasuo
     * @data 2013-04-23
     * @return   bool
     */
    function DelMycart()
    {
        $key = $this->getKey();
        $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $key))->find();
        try {
            if ( $row ) {
                M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->delete();
            }
            else {
                return false;
            }
        } catch ( Exception $e ) {
            return false;
        }
        return true;
    }
    
    /**
     * 加入购物车
     * @param  array([pdt_id] =>pdt_nums);
     * @author jiye
     * @data 2012-12-17
     * @return   bool
     */
    public function doAdd($arr_pdt) {
        //判断session中是否存在 cart
        if (!session('?cart')) {
            session('cart', $arr_pdt);
        } else {
            $arr_cart = session("cart");

            foreach ($arr_pdt as $k => $v) {

                if ($arr_cart[$k] == $k) {
                    $arr_cart[$k] = $v;
                } else {
                    $arr_cart[$k] = $arr_pdt[$k];
                }
            }
            session("cart", $arr_cart);
            return true;
        }
    }

    /**
     * 获取货品的信息
     * @author Nick
     * @data 2015-09-16
     * @param $ary_data
     * @param $mid
     *
     * @return array
     */
    public function getProductInfo($ary_data = array(), $mid=0,$is_cache=0) {
	    $memberId = (isset($mid) && $mid > 0) ? (int)$mid : 0;
        $member = M('Members')->where(array(
            'm_id'  =>  $memberId
        ))->find();
        $price = new PriceModel($memberId,$is_cache);
        $arr_newdata = array();
        if (is_array($ary_data) && count($ary_data)) {
            $i = 0;
            foreach ($ary_data as $k => $v) {
                $v['type'] = isset($v['type']) ? $v['type'] : $v['item_type'];
                /*=============================== 读取商品信息 ================================*/
                //如果是自由推荐商品
                if($v['type'] == '4'){
                    $ary_where = array();
                    $pdt_ids = implode(',',$v['pdt_id']);
                    $ary_where['_string'] = 'pdt_id in('.$pdt_ids.')';
					 if($is_cache == 1){
						 $obj_query = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
						->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						->where($ary_where);
						 $arr_newdata[$i] = D('Gyfx')->queryCache($obj_query,'',60);
						 /**
						 foreach($arr_newdata[$i] as &$sub){
							 $sub['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$sub['pdt_id']))->getField('pdt_stock');
						 }**/
					 }else{
						$arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
						->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						->where($ary_where)->select();
					 }					 
                }
                //团购商品
                else if($v['type'] == '5'){
					if($is_cache == 1){
						 $obj_query= M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
						->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						->where(array("pdt_id" => $k));
						 $arr_newdata[$i] = D('Gyfx')->queryCache($obj_query,'find',60);
						 //$arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->getField('pdt_stock');						
					}else{
						$arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
						->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						->where(array("pdt_id" => $k))->find();						
					}
                   //  print_r($arr_newdata[$i]);exit;
                }
                //自由组合商品
                else if($v['type'] == '6'){
                    $ary_where = array();
                    $pdt_ids = implode(',',$v['pdt_id']);
                    $ary_where['_string'] = 'pdt_id in('.$pdt_ids.')';
                    $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,fx_goods_products.pdt_weight,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price,fr.fr_name,fr.fr_price,fr.fr_id')
                    ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                    ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                    ->join('fx_free_recommend as fr on(fr.fr_goods_id = fx_goods.g_id)')
                    ->where($ary_where)->select();
                    //echo M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();die;
                }
                //秒杀商品
                else if($v['type'] == '7'){
					if($is_cache == 1){
						$obj_query = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						 ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
						 ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						 ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						 ->where(array("pdt_id" => $k));	
						 $arr_newdata[$i] = D('Gyfx')->queryCache($obj_query,'find',60);
						 //$arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->getField('pdt_stock');							 
					}else{
						$arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						 ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
						 ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						 ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						 ->where(array("pdt_id" => $k))->find();						
					}					 
                }
                //预售商品
                else if($v['type'] == '8'){
                    $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                     ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                     ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                     ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                     ->where(array("pdt_id" => $k))->find();
                }
                else if($v['type'] == '11'){
                    if($is_cache == 1){
                        $obj_query = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                            ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                            ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                            ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                            ->where(array("pdt_id" => $k));
                        $arr_newdata[$i] = D('Gyfx')->queryCache($obj_query,'find',60);
                        //$arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array("pdt_id" => $k))->getField('pdt_stock');
                    }else{
                        $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                            ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                            ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                            ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                            ->where(array("pdt_id" => $k))->find();
                    }
                }
                 //普通商品
                else{
                     $ary_search_where = array();
                     //无pdt_id
                     if(empty($k)){
                        $ary_search_where['fx_goods_products.g_id'] = $v['g_id'];
                     }else{
                        $ary_search_where['pdt_id'] =$k;
                     }
					 if($is_cache == 1){
						 $obj_query = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						 ->field('pdt_sale_price,pdt_market_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price,fx_goods_info.gifts_point,fx_goods_info.g_tax_rate,is_exchange')
						 ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						 ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						 ->where($ary_search_where);
						 $arr_newdata[$i] = D('Gyfx')->queryCache($obj_query,'find',60);
						// $arr_newdata[$i]['pdt_stock'] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_search_where)->getField('pdt_stock');
					 }else{
						 $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						 ->field('pdt_sale_price,pdt_market_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.pdt_weight,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price,fx_goods_info.gifts_point,fx_goods_info.g_tax_rate,is_exchange')
						 ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
						 ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
						 ->where($ary_search_where)->find();						 
					 }					 
                    //dump(M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());die();
                }
                /*=============================== 数据整理 ================================*/
                //积分商品
                if($v['type'] == 1){
                    $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k,1,$is_cache);
                    $arr_newdata[$i]["pdt_momery"] = 0;
                    $arr_newdata[$i]["pdt_price"] = 0;
                    $arr_newdata[$i]["pdt_nums"] = $v['num'];
                    $arr_newdata[$i]["type"] = 1;
                    $arr_newdata[$i]["pdt_per_save_price"] = $arr_newdata[$i]["pdt_sale_price"];
                    $arr_newdata[$i]["pdt_save_price"] =  sprintf("%0.2f",($arr_newdata[$i]["pdt_sale_price"]) * $v['num']);
                    $arr_newdata[$i]["pdt_sale_price"] =  0;
                }
                //赠品
                elseif($v['type'] == 2){
                    $arr_newdata[$i]["g_name"]="[赠品]".$arr_newdata[$i]["g_name"];
                    $arr_newdata[$i]["pdt_sale_price"]=0;
                    $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k,1,$is_cache);
                    $arr_newdata[$i]['pdt_nums'] =1;
                    $arr_newdata[$i]['pdt_momery'] =0;
                    $arr_newdata[$i]["pdt_price"] = 0;
                    $arr_newdata[$i]["type"]=2;
                }
                //组合商品
                elseif($v['type'] == 3){
                    $arr_newdata[$i]["f_price"]=$price->getMemberPrice($k);;
                    $arr_newdata[$i]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i]["pdt_sale_price"] - $arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["pre_price"]=sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["pdt_preferential"]);
                    $arr_newdata[$i]["pdt_rule_name"]='组合商品';
                    $arr_newdata[$i]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($k,$member['m_id'],0,$is_cache);
                    $arr_newdata[$i]["pdt_nums"] = $v['num'];
                    $arr_newdata[$i]["pdt_momery"] = sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["pdt_price"] = sprintf("%0.2f",$arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["type"]=3;
                }
                //自由推荐商品
                elseif($v['type'] == 4){
                    $nums = $v['num'];
                    foreach($arr_newdata[$i] as $key=>$item_info){
                        $arr_newdata[$i][$key]['pdtId'] = $k;
                        $arr_newdata[$i][$key]["g_picture"] = getFullPictureWebPath($item_info["g_picture"]);
                        $arr_newdata[$i][$key]["g_picture"] = D('QnPic')->picToQn($arr_newdata[$i][$key]["g_picture"],200,200);
                        $arr_newdata[$i][$key]["g_name"]="[自由推荐]".$item_info["g_name"];
                        $arr_newdata[$i][$key]["f_price"]=empty($item_info['pdt_collocation_price'])?$item_info['pdt_sale_price']:$item_info['pdt_collocation_price'];
                        $arr_newdata[$i][$key]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i][$key]["pdt_sale_price"] - $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["pre_price"]=sprintf("%0.2f", $nums[$key] * $arr_newdata[$i][$key]["pdt_preferential"]);
                        $arr_newdata[$i][$key]["pdt_rule_name"]='自由推荐';
                        $arr_newdata[$i][$key]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($item_info['pdt_id'],$member['m_id'],0,$is_cache);
                        $arr_newdata[$i][$key]["pdt_nums"] = $nums[$key];
                        $arr_newdata[$i][$key]["pdt_momery"] = sprintf("%0.2f", $nums[$key] * $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["pdt_price"] = sprintf("%0.2f", $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["type"]=4;
                        $arr_newdata[$i][$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($item_info['pdt_id'],1,$is_cache);
                        $arr_newdata[$i][$key]['fc_id'] = $v['fc_id'];
                    }
                }
                //团购商品
                elseif($v['type'] == 5){
                    $v['type_id'] || $v['type_id'] = $v['gp_id'];
                    $ary_detail = D('Groupbuy')->getDetails($v['type_id'], $member, $v['pdt_id']);
                    $arr_newdata[$i]["pdt_rule_name"]='团购商品';
                    $arr_newdata[$i]['pdt_spec'] = $ary_detail['page_detail']['ary_goods_default_pdt']['specName'];
                    $arr_newdata[$i]['type'] = 5;
                    $arr_newdata[$i]['type_id'] = $v['type_id'];
                    // 货品id
                    $arr_newdata[$i]['pdt_id'] = $v['pdt_id'];
                    $arr_newdata[$i]['pdt_nums'] = $v['num'];
                    $arr_newdata[$i]['pdt_price'] = $ary_detail['gp_price'];
                    $arr_newdata[$i]['pdt_stock'] = $ary_detail['page_detail']['ary_goods_default_pdt']['pdt_stock'];
                    $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                    $arr_newdata[$i]["g_name"]= "[团购商品]".$ary_detail['gp_title'];
                }
                //自由搭配商品
                elseif($v['type'] == 6){
                    $nums = $v['num'];
//                      echo "<pre>";print_r($arr_newdata[$i]);exit;
                    foreach($arr_newdata[$i] as $key=>$item_info){
                        $arr_newdata[$i][$key]['pdtId'] = $k;
                        $arr_newdata[$i][$key]["g_picture"] = getFullPictureWebPath($item_info["g_picture"]);
                        $arr_newdata[$i][$key]["g_picture"] = D('QnPic')->picToQn($arr_newdata[$i][$key]["g_picture"],200,200);
                        $arr_newdata[$i][$key]["g_name"]= !empty($item_info['fr_id']) ? "[自由搭配]".$item_info["fr_name"] : $item_info['g_name'];
                        $arr_newdata[$i][$key]["f_price"]=empty($item_info['fr_price'])?$item_info['pdt_sale_price']:$item_info['fr_price'];
                        $arr_newdata[$i][$key]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i][$key]["pdt_sale_price"] - $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["pdt_nums"] = !empty($item_info['fr_id']) ? $nums[$item_info['fr_id']] : 1;
                        $arr_newdata[$i][$key]["pre_price"]=sprintf("%0.2f", $arr_newdata[$i][$key]["pdt_nums"] * $arr_newdata[$i][$key]["pdt_preferential"]);
                        $arr_newdata[$i][$key]["pdt_rule_name"]=!empty($item_info['fr_id']) ? "自由搭配":"";
                        $arr_newdata[$i][$key]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($item_info['pdt_id'],$member['m_id'],0,$is_cache);

                        $arr_newdata[$i][$key]["pdt_momery"] = sprintf("%0.2f", $arr_newdata[$i][$key]["pdt_nums"] * $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["pdt_price"] = sprintf("%0.2f", $arr_newdata[$i][$key]["f_price"]);
                        $arr_newdata[$i][$key]["type"]=6;
                        $arr_newdata[$i][$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($item_info['pdt_id'],1,$is_cache);
                        $arr_newdata[$i][$key]['fr_id'] = $item_info['fr_id'];
                    }
                }
                //秒杀商品
                else if($v['type'] == 7){
                    $v['type_id'] || $v['type_id'] = $v['sp_id'];
                    $ary_detail = D('Spike')->getDetails($v['type_id'], $member, $v['pdt_id']);
                    $arr_newdata[$i]["pdt_rule_name"]='秒杀商品';
                    $arr_newdata[$i]['pdt_spec'] = $ary_detail['page_detail']['ary_goods_default_pdt']['specName'];
                    $arr_newdata[$i]['type'] = 7;
                    $arr_newdata[$i]['type_id'] = $v['type_id'];
                    $arr_newdata[$i]['pdt_nums'] = $v['num'];
                    $arr_newdata[$i]['pdt_price'] = $ary_detail['sp_price'];
                    $arr_newdata[$i]['pdt_stock'] = $ary_detail['page_detail']['ary_goods_default_pdt']['pdt_stock'];
                    $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                    $arr_newdata[$i]["g_name"]= "[秒杀商品]".$ary_detail['sp_title'];
                }
                //预售商品
                else if($v['type'] == 8){
                    $v['type_id'] || $v['type_id'] = $v['p_id'];
                    $ary_detail = D('Presale')->getDetails($v['type_id'], $member, $v['pdt_id']);
                    $arr_newdata[$i]["pdt_rule_name"]='预售商品';
                    $arr_newdata[$i]['pdt_spec'] = $ary_detail['page_detail']['ary_goods_default_pdt']['specName'];
                    $arr_newdata[$i]['type'] = 8;
                    $arr_newdata[$i]['type_id'] = $v['type_id'];
                    $arr_newdata[$i]['pdt_nums'] = $v['num'];
                    $arr_newdata[$i]['pdt_price'] = $ary_detail['p_price'];
                    $arr_newdata[$i]['pdt_stock'] = $ary_detail['page_detail']['ary_goods_default_pdt']['pdt_stock'];
                    $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                    $arr_newdata[$i]["g_name"]= "[预售商品]".$ary_detail['p_title'];
                }
                else if($v['type'] == 11){
                    $g_stock = D("Integral")->field('integral_num,integral_need,money_need_to_pay')->where(array('g_id'=>$arr_newdata[$i]['g_id']))->find();
                    $arr_newdata[$i]["pdt_rule_name"]='积分兑换商品';
                    $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k,1,$is_cache);
                    $arr_newdata[$i]['type'] = 11;
                    $arr_newdata[$i]['pdt_nums'] = $v['num'];
                    $arr_newdata[$i]['pdt_stock'] = $g_stock['integral_num'];
                    $arr_newdata[$i]['pdt_price'] = $g_stock['money_need_to_pay'];
                    $arr_newdata[$i]['g_point'] = $g_stock['integral_need'];
                    $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                    $arr_newdata[$i]["g_name"]="[积分兑换商品]".$arr_newdata[$i]["g_name"];
                }
                //普通商品
                else{
					if($is_cache == 1){
						$min = D('Gyfx')->selectOneCache('goods_products','pdt_min_num', array('pdt_id'=>$v['pdt_id']), $ary_order=null,600);
					}else{
						$min = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('pdt_min_num')->where(array('pdt_id'=>$v['pdt_id']))->find();						
					}
                    //$arr_newdata[$i] = $v;
                    $arr_newdata[$i]["f_price"] = $price->getItemPrice($k,$arr_newdata[$i]['pdt_sale_price']);
                    $arr_newdata[$i]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i]["pdt_sale_price"] - $arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["pdt_rule_name"]=$price->getPriceRuleName();
                    $arr_newdata[$i]["rule_info"]=$price->getRuleinfo();
                    $arr_newdata[$i]["pdt_nums"] = $v['num'];
                    $arr_newdata[$i]["pdt_min_num"] = $min['pdt_min_num'];
                    $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k,1,$is_cache);
                    $arr_newdata[$i]["pdt_momery"] = sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["pdt_price"] = sprintf("%0.2f",$arr_newdata[$i]["f_price"]);
                    $arr_newdata[$i]["pdt_per_save_price"] = $arr_newdata[$i]["pdt_market_price"] - $arr_newdata[$i]["f_price"];
                    $arr_newdata[$i]["pdt_save_price"] = sprintf("%0.2f",($arr_newdata[$i]["pdt_market_price"] - $arr_newdata[$i]["f_price"]) * $v['num']);
                    $arr_newdata[$i]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($v['pdt_id'],$member['m_id'],0,$is_cache);
                    $arr_newdata[$i]["type"] = 0;
                }
                /*=============================== 商品图片绝对地址 ================================*/
                if($v['type'] != 4 && $v['type'] != 6){
                    $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                    $arr_newdata[$i]["g_picture"] = D('QnPic')->picToQn($arr_newdata[$i]["g_picture"],200,200);
                }
                   
                $i++;
            }
        }
        return $arr_newdata;

    }

    /**
     * 获取货品的原先的价格
     * @author jiye
     * @data 2012-12-17
     * @param array([pdt_id]=>pdt_nums)
     * @param return double
     */
    public function getAllPrice($ary_data = array()) {
        $double_pirce = 0;
        if (isset($ary_data)) {
            foreach ($ary_data as $k => $v) {
                if(isset($v['type']) && $v['type'] == 1) continue;
                //自由推荐价格单算
                if(isset($v['type']) && $v['type'] == 4){
                	$pdt_ids = $v['pdt_id'];
                	$nums = $v['num'];
                	foreach($pdt_ids as $key=>$val){
                   		$double_all_price = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_collocation_price,pdt_sale_price"))->where(array("pdt_id" => $val))->find();
                   		if(empty($double_all_price['pdt_collocation_price'])){
                   			$double_pirce += ($double_all_price['pdt_sale_price'] * $nums[$key]);   
                   		}else{
                   			$double_pirce += ($double_all_price['pdt_collocation_price'] * $nums[$key]);   	
                   		}         		
                	}
                }elseif(isset($v['type']) && $v['type'] == 6){
                    //自由搭配价格计算
                    $pdt_ids = $v['pdt_id'];
                	$nums = $v['num'];
                    foreach ($pdt_ids as $key=>$val){
                        $double_all_price = M("free_recommend",C('DB_PREFIX'),'DB_CUSTOM')->where(array('fr_id'=>$key))->getField('fr_price');
                        if(empty($double_all_price)){
                   			$double_all_price = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$val))->getField('pdt_sale_price');
                   		}
                        $double_pirce += ($double_all_price * $nums[$key]);   
                    }
                    
                }else{
                  $double_all_price = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field(array("pdt_sale_price"))->where(array("pdt_id" => $k))->find();
                	$double_pirce += ($double_all_price['pdt_sale_price'] * $v['num']);              	
                }
            } 
            return $double_pirce;
        }
    }
    
    /**
     * 会员是否能添加积分商品判断
     * @author czy  <chenzongyao@guanyisoft.com>
     * @data 2013-4-23
     * @param int 会员m_id
     * @param array 购物车相关信息
     * @param string 访问提示信息，引用返回
     * @param return boolen
     */
    public function enablePoint($m_id,$ary_cart_data = array(),&$info) {
        $ary_member = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->field(array("total_point,freeze_point"))->where(array("m_id" => $m_id))->find();
        if(!$ary_member){
            $info = '此会员不存在';
            return false;
        }
        else{
            $valid_point = ($ary_member['total_point'] >= $ary_member['freeze_point']) ?($ary_member['total_point'] - $ary_member['freeze_point']) :0;
            $consume_point = 0;//要消耗的总积分
            if(!empty($ary_cart_data)){
                   $ary_cart = array();
                   foreach($ary_cart_data as $key=>$value){
                        if(!empty($value['pdt_id']) && !empty($value['num']) && isset($value['type']) && $value['type']==1){
                             
                             $ary_goods = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field('fx_goods_info.point')
                                                        ->join('left join fx_goods_info on fx_goods_info.g_id = fx_goods_products.g_id')
                                                        ->where(array('fx_goods_products.pdt_id'=>$value['pdt_id']))
                                                        ->find();
                             $consume_point += $ary_goods['point'] * $value['num'];
                        }
                    }
             }
			 if($valid_point >= $ary_goods['point'] && $valid_point < $consume_point){
				 $info = "您当前积分不足，请检查购物车中是否有该商品！";
				 return false;
			 }
             if($valid_point>=$consume_point){
				 return true;
		     }else{
                $info = "现在您的有效积分数不够，只有{$valid_point}个积分";
                return false;
             }
         }
        
    }
    
    /**
     * 下单成功后更新购物车商品
     * 将购买成功的商品移除购物车
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-02-27
     */
    public function doUpadteOrdersCart($mix_pdt_id,$mix_pdt_type){
        $ary_db_carts = $this->ReadMycart();
        if(!empty($ary_db_carts) && is_array($ary_db_carts)){
            foreach($ary_db_carts as &$cart_val){
                if($cart_val['type'] == '0'){
                    $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$cart_val['pdt_id']))->find();
                    if(NULL === $ary_gid){
                        $mix_pdt_id = $cart_val['pdt_id'];
                    }
                }
            }
        }
        foreach ($mix_pdt_id as $key => $val) {
            if ($mix_pdt_type[$key] == 2) {

                if (isset($ary_db_carts['gifts'][$val]) && $ary_db_carts['gifts'][$val]['type'] == $mix_pdt_type[$key]) {
                    if (count($ary_db_carts['gifts']) < 2) {
                        unset($ary_db_carts['gifts']);
                    } else {
                        unset($ary_db_carts['gifts'][$val]);
                    }
                }
            } else {
                if (isset($ary_db_carts[$val]) && $ary_db_carts[$val]['type'] == $mix_pdt_type[$key]) {
                    unset($ary_db_carts[$val]);
                }
                if(!empty($ary_db_carts['free1']) && $ary_db_carts['free1']['type'] == '4' || !empty($ary_db_carts['free1']) && $ary_db_carts['free1']['type'] == '6'){
                    unset($ary_db_carts['free1']);
                }
            }
        }
        return $this->WriteMycart($ary_db_carts);
    }

    /**
     * 获取指定货品的购物车信息
     * @param $pids
     * @param $m_id
     * @param $gift_except
     *
     * @return array
     */
    public function getCartItems($pids, $m_id, $gift_except=false) {
        $this->setMid($m_id);
        $ary_tmp_cart = $this->ReadMycart();
        if($pids){
			if(!is_array($pids)){
				$ary_pid = explode(',', $pids);
			}else{
				$ary_pid = $pids;
			}
            $ary_cart = array();
            foreach ($ary_tmp_cart as $key=>$cd){
                if(in_array($key, $ary_pid)){
                    $ary_cart[$key] = $ary_tmp_cart[$key];
                }
            }
        }else{
            $ary_cart = $ary_tmp_cart;
        }

        if ($gift_except && isset($ary_cart ['gifts'])) {
            unset($ary_cart ['gifts']);
        }
        return $ary_cart;
    }

    /**
     * 处理购物车信息
     * @param $cart_data
     * @return array
	 * @date 2015-10-13
	 * @author wangguibin
     */	
	public function handleCart($cart_data){
		if(!empty($cart_data) && is_array($cart_data)){
			foreach($cart_data as $key=>&$val){
				if ($key == 'gifts') {
                    unset($cart_data[$key]);
                } else {
					$val['type'] = isset($val['type']) ? $val['type'] : 0;
					if($val['type'] == '0'){
						if(!isset($val['g_id'])){
							$ary_gid = D('Gyfx')->selectOneCache('goods_products','g_id', array('pdt_id'=>$val['pdt_id']), $ary_order=null,$time=null);
							$val['g_id'] = $ary_gid['g_id'];					
						}
						//判断商品是否存在不存在删除
						if(isset($val['g_id'])){
							$gid_count = D('Gyfx')->getCountCache('goods',array('g_id'=>$val['g_id'],'g_status'=>1),600);
							if($gid_count == 0){
								unset($cart_data[$key]);
								$this->WriteMycart($cart_data);
							}							
						}
					}				
                }
			}
		}else{
			return array();
		}	
		return $cart_data;
	}
    
    /**
     * 处理购物车商品信息信息
     * @param $ary_cart_data
     * @return array
	 * @date 2015-10-13
	 * @author wangguibin
     */	
	public function handleCartProductsAuthorize($ary_cart_data,$int_m_id){
		$ary_cart = array();
		foreach ($ary_cart_data as $key=>$info) {
			if (isset($info['pdt_id'])) {
				$ary_cart[$info['pdt_id']] = $info;
				//添加产品是否允许购买
				$ary_cart[$info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($int_m_id, $info['g_id'],1);
			} else {
				//自由推荐权限判断
				if ($info[0]['type'] == 4 ) {
					foreach ($info as $subkey=>$sub_info) {
						$ary_cart['free'.$sub_info['fc_id']][$sub_info['pdt_id']] = $sub_info;
						//添加产品是否允许购买
						$ary_cart['free'.$sub_info['fc_id']][$sub_info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($int_m_id, $sub_info['g_id'],1);
					}
				}
				//自由组合
				if($info[0]['type'] == 6){
					foreach ($info as $subkey=>$sub_info) {
						$ary_cart['free'][$sub_info['pdt_id']] = $sub_info;
						//添加产品是否允许购买
						$ary_cart['free'][$sub_info['pdt_id']]['authorize'] = D('AuthorizeLine')->isAuthorize($int_m_id, $sub_info['g_id'],1);
					}					
				}
			}
		}
		unset($ary_cart_data);
		return $ary_cart;
	}		
	

    /**
     * 处理通过促销获取的优惠信息
     * @param $pro_datas
	 * @param $ary_cart
     * @return array
	 * @date 2015-10-13
	 * @author wangguibin
     */	
	public function handleProdatas($pro_datas,$ary_cart){
		//处理获取购物车信息金额
		$promotion_total_price = '0';
		$promotion_price = '0';
        $save_price = 0;
		$promotion_jlb = 0;
		//赠品数组
		$cart_gifts = array();
		//促销信息整理
		$pro_data = array();
		//跨境贸易
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
		$total_tax_rate = $int_total_promotion_price =0;
		$promotion_name = '';
		$i=0;
        //dump($pro_datas);die;
		foreach($pro_datas as $keys=>$vals){
			$int_promotion_count = count($vals['products']);
            //总优惠金额
            $pro_goods_total_discount = isset($vals['pro_goods_discount']) ? $vals['pro_goods_discount'] : 0;
            //优惠商品的总金额
            $goods_total_price = $vals['goods_total_price'];

            $pro_datas[$keys]['goods_all_discount'] = $pro_goods_total_discount;
            $product_count = count($vals['products']);
            $i = 0;
            $goods_discount = 0;
			foreach($vals['products'] as $key=>$val){
                $i++;
                if(!empty($val['fc_id'])){
					$pro_datas[$keys]['products'][$key] = array_values($ary_cart['free'.$val['fc_id']]);
				}
                //自由组合
                elseif($key == 'free'){
                    $pro_datas[$keys]['products']['free'] = array_values($ary_cart['free']);
                }
                else{
                    //商品原销售金额
                    $pdt_sale_price = $val['pdt_sale_price'];
                    //商品购买数
                    $pdt_num = $val['num'];
                    //最后一个商品接手所有剩下的优惠金额
                    if($i == $product_count) {
                        $per_goods_discount = round(($pro_goods_total_discount - $goods_discount)/$pdt_num, 2);
                    }else {
                        //(本商品原销售金额*商品购买数/优惠商品的总金额)*总优惠金额
                        $the_goods_discount = $pdt_sale_price * $pdt_num * $pro_goods_total_discount / $goods_total_price;
                        $goods_discount += $the_goods_discount;
                        $per_goods_discount = round($the_goods_discount/$pdt_num, 2);
                    }
                    $pro_datas[$keys]['products'][$key] =  $ary_cart[$key];
                    $pro_datas[$keys]['products'][$key]['f_price'] -= $per_goods_discount;
                    $val['pdt_price'] -= $per_goods_discount;
                    $pro_datas[$keys]['products'][$key]['pdt_price'] -= $per_goods_discount;
                    //dump($val);
                }

				$pro_data[$key] = $val;
				$pro_data[$key]['pmn_name'] = $vals['pmn_name'];
				$pro_data[$key]['pmn_id'] = intval($vals['pmn_id']);
				//购物车优惠优惠金额放到订单明细里拆分

				if($keys != 0 && !empty($vals['pro_goods_discount'])){
					if($int_promotion_count == $i+1){
						$pro_datas [$keys] ['products'] [$key]['promotion_price'] = $vals['pro_goods_discount']-$int_total_promotion_price;
					}else{
						$pro_datas[$keys]['products'][$key]['promotion_price'] = sprintf("%.2f", ($val['f_price']*$val['pdt_nums']/$vals['goods_total_price'])*$vals['pro_goods_discount']);
						$int_total_promotion_price = $int_total_promotion_price+$pro_datas[$keys]['products'][$key]['promotion_price'];
					}
				}
				//跨境贸易
				if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
					//自由推荐暂时不加入跨境贸易
					//是自由推荐
					if(isset($pro_datas[$keys]['products'][$key][0]['g_tax_rate']) && !empty($pro_datas[$keys]['products'][$key][0]['g_tax_rate'])){

					}else{
						//普通商品
						if($pro_datas[$keys]['products'][$key]['g_tax_rate']){
							if($pro_data[$key]['pmn_name']){
								$total_tax_rate += $pro_datas[$keys]['products'][$key]['f_price']*$pro_datas[$keys]['products'][$key]['pdt_nums']*$pro_datas[$keys]['products'][$key]['g_tax_rate'];
							}else{
								$total_tax_rate += $pro_datas[$keys]['products'][$key]['pdt_momery']*$pro_datas[$keys]['products'][$key]['g_tax_rate'];
							}
						}
					}
				}
			}
			//die();
			//赠品数组
			if(!empty($vals['gifts'])){
				$ary_gift_gids = array();
				foreach($vals['gifts'] as $gifts){
					if(!in_array($gifts['g_id'],$ary_gift_gids)){
						//随机取一个pdt_id
						$pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gifts['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
						$cart_gifts[$pdt_id['pdt_id']]=array('pdt_id'=>$pdt_id['pdt_id'],'g_id'=>$gifts['g_id'],'num'=>1,'type'=>2);	
						$ary_gift_gids[] = $gifts['g_id'];
					}	
				}
				unset($ary_gift_gids);
			}
			$promotion_total_price += (isset($vals['pro_goods_total_price']) ? $vals['pro_goods_total_price'] : $vals['goods_total_price']);     //商品总价
//			if($keys != '0'){
				$promotion_price += isset($vals['pro_goods_discount']) ? $vals['pro_goods_discount'] : $vals['goods_all_discount'];
				$promotion_jlb += $vals ['pro_goods_mjlb'];
				if(!empty($vals['pmn_name'])){
					$promotion_name .=','.$vals['pmn_name'];
				}
//			}
			$i++;
		}

		$promotion_name = trim($promotion_name,',');
		//$total_tax_rate = sprintf("%.2f", $total_tax_rate*(($promotion_price)/$promotion_total_price+$promotion_price));
		//跨境贸易税额起征点
		if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
			$foreign_info=D('SysConfig')->getForeignOrderCfg();
			if( !empty($foreign_info['IS_AUTO_TAX_THRESHOLD']) && $foreign_info['TAX_THRESHOLD'] >= $total_tax_rate){
				$total_tax_rate=0;
			}
		}		
		//获取赠品信息
		if(!empty($cart_gifts)){
			$cart_gifts_data = array();
			$cart_gifts_data = $this->getProductInfo($cart_gifts);
		}
		return array(
            'pro_datas'=>$pro_datas,
            'cart_gifts_data'=>$cart_gifts_data,
            'pro_data'=>$pro_data,
            'promotion_total_price'=>$promotion_total_price+$promotion_price,
            'promotion_price'=>$promotion_price,
            'promotion_jlb'=>$promotion_jlb,
            'total_tax_rate'=>$total_tax_rate,
            'promotion_name'=>$promotion_name
        );
	}
	
    /**
     * 获取订单总金额
     * @param $pro_datas
	 * @param $ary_cart
	 * @param $np表示不读积分
     * @return array
	 * @date 2015-10-13
	 * @author wangguibin
     */	
	public function getPriceData($tmp_pro_datas,$subtotal,$cart_gifts_data,$np=0){	
		$ary_price_data = array();
		//获取总金额
		$promotion_total_price = $tmp_pro_datas['promotion_total_price'];
		//获取总优惠金额
		$promotion_price = $tmp_pro_datas['promotion_price'];	
		$ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
		$ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
		$ary_price_data['all_price'] = $preferential_price = (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) > 0 ? (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) : '0.00';
		//加上税费
		if($tmp_pro_datas['total_tax_rate']){
			$ary_price_data['all_price'] +=$tmp_pro_datas['total_tax_rate'];
		}
		$ary_price_data['total_price'] = $ary_price_data['all_price'];
		// 获得赠送积分
		$gifts_point_reward = 0;
		$gifts_point_goods_price  = 0;
		$total_price = 0;
		$pro_datas = $tmp_pro_datas['pro_datas'];
		//购物车商品总数
		$ary_price_data['all_nums'] = 0;
		$ary_price_data['all_weight'] = 0;
		
		foreach($pro_datas as $pro){
			 foreach($pro['products'] as $key=>$val){
				if($val[0]['type'] == '4' || $val[0]['type'] == '6'){
					foreach($val as $tmp_val){
						 $ary_price_data['all_nums'] += $tmp_val['pdt_nums'];
						 $ary_price_data['all_weight'] += $tmp_val['pdt_nums']*$tmp_val['pdt_weight'];
						 $total_price +=$tmp_val['pdt_momery'];
					}
					
				}else{
					$ary_price_data['all_nums'] += $val['pdt_nums'];
					$ary_price_data['all_weight'] += $val['pdt_nums']*$val['pdt_weight'];
					$total_price +=$val['pdt_momery'];
				}

				if($val['type'] != 1 && $val['gifts_point']>0 && isset($val['gifts_point']) && isset($val['is_exchange'])){
					$gifts_point_reward += $val['gifts_point']*$val['pdt_nums'];
					//$gifts_point_goods_price += $val['f_price']*$val['pdt_nums'];
					$gifts_point_goods_price +=$val['pdt_momery'];
				}
			 }
		}

		//计算赠品总重量
		if(!empty($cart_gifts_data)){
			foreach($cart_gifts_data as $cart_gift){
				$ary_price_data['all_weight'] += $cart_gift['pdt_nums']*$cart_gift['pdt_weight'];
			}
		}
		$ary_price_data['all_weight'] = sprintf("%0.2f", $ary_price_data['all_weight']);
		if($np != 1){
			$other_all_price = $total_price-$gifts_point_goods_price;
			$other_point_reward = D('PointConfig')->getrRewardPoint($other_all_price);
			$other_point_reward = ceil(($ary_price_data['all_price']/$total_price)*$other_point_reward);
			$ary_price_data ['reward_point'] = $gifts_point_reward+$other_point_reward;
			//需消耗总积分
			$ary_price_data['consume_point'] = intval($subtotal['goods_all_point']);	
			// 计算订单可以使用的积分
			$ary_price_data ['is_use_point'] = D('PointConfig')->getIsUsePoint($ary_price_data ['total_price'],$subtotal['m_id']);				
		}
		return $ary_price_data;
	}
	
    /**
     * 获取订单总金额未登陆
     * @param $pro_datas
	 * @param $ary_cart
     * @return array
	 * @date 2015-10-15
	 * @author wangguibin
     */	
	public function getPriceDataUnlogin($ary_cart_data){	
		$ary_price_data = array();
		if (!empty($ary_cart_data) && is_array($ary_cart_data)) {
			foreach ($ary_cart_data as $key => $val) {
				$promition_rule_name = $val['pdt_rule_name'];
				//应付的价格（不包括运费
				if($val['type'] == 1){
					$ary_price_data['consume_point'] += intval($val['pdt_momery']); //消耗总积分
				}else{
					//自由组合商品
					if($val[0]['type'] == '4' || $val[0]['type'] == '6'){
						foreach($val as $ary_sub_val){
							$ary_price_data['all_pdt_price'] +=$ary_sub_val['pdt_sale_price']*$ary_sub_val['pdt_nums'];
							$ary_price_data['all_price'] += $ary_sub_val['pdt_momery'];
							$ary_price_data['all_nums'] += $ary_sub_val['pdt_nums'];
						}
					}else{
						$ary_price_data['all_pdt_price'] +=$val['pdt_sale_price']*$val['pdt_nums'];
						$ary_price_data['all_price'] += $val['pdt_momery'];
						$ary_price_data['all_nums'] += $val['pdt_nums'];
					}
				} 
			}
		}
		
		//商品总价
		$promotion_total_price = $ary_price_data['all_pdt_price'];
		//优惠价
		$promotion_price = sprintf("%0.2f", $ary_price_data['all_pdt_price'] - $ary_price_data['all_price']);
		//赠送积分
		$ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price']);
		$ary_price_data['all_pdt_price'] = sprintf("%0.2f", $promotion_total_price);
		$ary_price_data['pre_price'] = sprintf("%0.2f", $promotion_price);
		$ary_price_data['all_price'] = $preferential_price = (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) > 0 ? (  sprintf("%0.2f", $promotion_total_price - $promotion_price) ) : '0.00';
		$ary_price_data['reward_point'] = D('PointConfig')->getrRewardPoint($ary_price_data['all_pdt_price'] - $free_all_price);
		return $ary_price_data;
	}	

    /**
     * 获取订单总金额未登陆
     * @param $ary_tmp_cart 购物车信息判断
	 * @param $np 不显示图片
     * @return array
	 * @date 2015-10-19
	 * @author wangguibin
     */		
	public function checkOrder($ary_tmp_cart,$np=0,$m_id=0){
		$return_res = array('status'=>0,'ary_cart'=>array(),'message'=>'购物车信息有误','pdt_id'=>0);
		if($m_id !=0){
			$this->setMid($m_id);
			$ary_member['m_id'] = $m_id;
		}else{
			$ary_member = session("Members");
		}
        $date = date('Y-m-d H:i:s');
        if (!empty($ary_member ['m_id'])) {
            if($ary_tmp_cart){
                $ary_cart = $ary_tmp_cart;
            }else{
                $ary_cart = $this->ReadMycart();
            }
            // 自由组合商品搭配分开
            $ary_product_ids = array();
            foreach ($ary_cart as $k => $ary_sub) {
                if ($ary_sub ['type'] == '4') {
                    $fc_id = $ary_sub ['fc_id'];
                    // 判断自由组合商品是否存在或是否在有效期
                    $fc_data = M('free_collocation', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'fc_id' => $fc_id,
                                'fc_status' => 1
                            ))->find();
                    if (empty($fc_data)) {
                        $ary_db_carts = $this->ReadMycart();
                        unset($ary_db_carts [$k]);
                        $this->WriteMycart($ary_db_carts);
						$return_res['message'] = L('自由推荐组合已不存在');
                        return $return_res;
                    }
                    if ($fc_data ['fc_start_time'] != '0000-00-00 00:00:00' && $date < $fc_data ['fc_start_time']) {
						$return_res['message'] = L('自由推荐组合活动还没有开始');
                        return $return_res;						
                    }
                    if ($date > $fc_data ['fc_end_time']) {
						$return_res['message'] = L($ary_sub ['g_sn'] . '自由推荐组合活动已结束');
                        return $return_res;							
                    }
                    // 判断自由组合商品
                    foreach ($ary_sub ['pdt_id'] as $pid) {
                        $ary_product_ids [] = $pid;
                    }
                } else {
                    $ary_product_ids [] = $k;
                }
            }
            $ary_product_ids = array_unique($ary_product_ids);
            $field = array(
                'fx_goods_products.pdt_stock',
                'fx_goods_products.pdt_id',
                'fx_goods.g_on_sale',
                'fx_goods.g_sn',
                'fx_goods.g_gifts',
                'fx_goods.g_is_combination_goods',
                'fx_goods.g_pre_sale_status',
                'fx_goods_info.is_exchange',
                'fx_goods_info.g_name',
                'fx_goods_info.g_id',
                'fx_goods_products.pdt_sale_price',
                'fx_goods_products.pdt_max_num',
				'fx_goods_products.pdt_min_num',
                'fx_goods.g_on_sale_time',
                'fx_goods.g_off_sale_time'
            );
            $where = array(
                'fx_goods_products.pdt_id' => array(
                    'IN',
                    $ary_product_ids
                )
            );
            $data = D("GoodsProducts")->GetProductList($where, $field, $group, $limit);
            foreach ($data as $key => $value) {
                if ($value ['g_on_sale'] != 1) { // 上架
						$return_res['message'] = $value ['g_sn'] . '下架商品';
						$return_res['pdt_id'] = $value['pdt_id'];
                        return $return_res;	
                }
				if($value['g_gifts'] == 1){
					$return_res['message'] = $value ['g_sn'] . '为非销售赠品';
					$return_res['pdt_id'] = $value['pdt_id'];
					return $return_res;						
				}
                $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value ['pdt_id'],$ary_member['m_id']);
                if ($ary_cart [$value ['pdt_id']] ['num'] > $tmp_stock && !$value ['g_is_combination_goods'] && !$value ['g_pre_sale_status']) { // 购买数量
					$return_res['message'] = $value ['g_sn'] . '商品库存不足';
					$return_res['pdt_id'] = $value['pdt_id'];
					return $return_res;					
                }
				//限购判断
				if(isset($ary_cart [$value['pdt_id']] ['num'])){
					if($ary_cart [$value['pdt_id']] ['num'] < $value['pdt_min_num']){
						$return_res['message'] = L('商品'.$pro['pdt_sn'].'没有达到限购数量！');
						$return_res['pdt_id'] = $value['pdt_id'];
						return $return_res;	
					}					
				}else{
					if(isset($value['type']) && ($value['type'] == 4 || $value['type'] == 6)){
						//暂时不处理
					}
				}
                if ($value ['g_is_combination_goods']) {
                    // $tmp_stock = D("GoodsStock")->getProductStockByPdtid($value ['pdt_id'],$ary_member['m_id']);
                    if ($ary_cart [$value ['pdt_id']] ['num'] > $tmp_stock) {
						$return_res['message'] = $value ['g_sn'] . '组合商品库存不足';
						$return_res['pdt_id'] = $value['pdt_id'];
						return $return_res;							
                    }
                    if ($ary_cart [$value ['pdt_id']] ['num'] > $value ['pdt_max_num'] && $value ['pdt_max_num'] > 0) {
                        // edit by Joe 组合商品数量超出最大下单数时，当前组合商品购物车情空
                        $ary_db_carts = $this->ReadMycart();
                        unset($ary_db_carts [$value ['pdt_id']]);
                        $this->WriteMycart($ary_db_carts);
						$return_res['message'] = $value ['g_sn'] . '组合商品购买数不能最大于最大下单数';
						$return_res['pdt_id'] = $value['pdt_id'];
						return $return_res;								
                    }
                    if ($value ['g_on_sale_time'] != '0000-00-00 00:00:00' && $date < $value ['g_on_sale_time']) {
						$return_res['message'] = $value ['g_sn'] . '组合商品活动还没有开始';
						$return_res['pdt_id'] = $value['pdt_id'];
						return $return_res;								
                    }
                    if ($value ['g_off_sale_time'] != '0000-00-00 00:00:00' && $date > $value ['g_off_sale_time']) {
						$return_res['message'] = $value ['g_sn'] . '组合商品活动结束';
						$return_res['pdt_id'] = $value['pdt_id'];
						return $return_res;							
                    }
                }
                if ($value ['pdt_sale_price'] <= 0 && $ary_cart [$value ['pdt_id']] ['type'] != 1 && $value ['g_gifts'] != 1) { // 价格
					$return_res['message'] = $value ['g_sn'] . '商品价格不正确';
					$return_res['pdt_id'] = $value['pdt_id'];
					return $return_res;					
                }
                if ($value ['pdt_sale_price'] < 0 && $ary_cart [$value ['pdt_id']] ['type'] == 1 && $value ['g_gifts'] == 1) {
					$return_res['message'] = $value ['g_sn'] . '商品价格不正确';
					$return_res['pdt_id'] = $value['pdt_id'];
					return $return_res;						
                }
                $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $value ['g_id'],1);
                if (empty($is_authorize)) {
					$return_res['message'] = $value ['g_sn'] . '已不允许购买,请先删除';
					$return_res['pdt_id'] = $value['pdt_id'];
					return $return_res;						
                }
            }
        } else {
            $ary_cart = (session("?Cart")) ? session("Cart") : array();
        }
		if($np == 1){
			if (count($ary_cart) > 300) {
				$return_res['message'] = "购物车已经超过300件";
				return $return_res;
			}		
		}else{
			if (count($ary_cart) > 50) {
				$return_res['message'] = L('CART_MAX_NUM');
				return $return_res;				
			}				
		}

        if (empty($ary_cart)) {
			$return_res['message'] = L('购物车商品信息已不存在请检测');
			return $return_res;					
        }
		$return_res['status'] = 1;
		$return_res['ary_cart'] = $ary_cart;
        return $return_res;		
	}
	
    /**
     * 获取会员默认物流方式
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-19
     */	
	public function getMembersLogistic($m_id=0,$ary_tmp_cart,$pro_datas,$ary_price_data,$ra_id=0){
		if($m_id ==0){
			$ary_member = session("Members");
			$m_id = $ary_member['m_id'];
		}
		//是否开启自提功能
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		if($is_zt['IS_ZT']['sc_value'] == 1 ){
			$zt_logistic_where = array('lc_abbreviation_name' => 'ZT');
			$zt_field = array('fx_logistic_type.lt_id');
			$ary_zt_res= D('LogisticCorp')->getLogisticInfo($zt_logistic_where, $zt_field);	
			$zt_logistic = $ary_zt_res['lt_id'];
		}
        //是否开启外汇功能
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        if($is_foreign['GY_IS_FOREIGN']['sc_value'] == 1){
			$total_tax_rate=0;
			$ary_addr = D('CityRegion')->getReceivingAddressPage($m_id,array(),$ra_id);
			if($ra_id !=0){
				 if (count($ary_addr) > 0) {
					if(isset($ary_addr['addr'][0]) && $ary_addr['addr'][0]!=''){
						$default_addr = $ary_addr['addr'][0];
						unset($ary_addr['addr'][0]);
					}else{
						$ary_addr ['ra_is_default'] = 1;//临时默认收货地址
						$default_addr = $ary_addr['addr'];
					}
				}
			}else{
				$count_addr = count($ary_addr['addr']);
				if ( $count_addr > 0) {
					$default_addr = $ary_addr['addr'][0];
				}
				$ary_addr = $ary_addr['addr'];				
			}
        }else{
            // 获取常用收货地址
            $ary_addr = D('CityRegion')->getReceivingAddress($m_id,$ra_id);
			if($ra_id !=0){
				 if (count($ary_addr) > 0) {
					if(isset($ary_addr [0]) && $ary_addr [0]!=''){
						$default_addr = $ary_addr[0];
						unset($ary_addr [0]);
					}else{
						$ary_addr ['ra_is_default'] = 1;//临时默认收货地址
						$default_addr = $ary_addr;
					}
				}
			}else{
				if (count($ary_addr) > 0) {
					$default_addr = $ary_addr [0];
					unset($ary_addr [0]);
				}				
			}
        }		
		// 满足满包邮条件
        foreach ($pro_datas as $pro_data) {
            if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                foreach($pro_data['products'] as $proDatK=>$proDatV){
                    unset($ary_tmp_cart[$proDatK]);
                }
            }
        }
        if(empty($ary_tmp_cart)){
            $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
        }
        if (!empty($default_addr) && is_array($default_addr)) {
            $ra_is_default = $default_addr['ra_is_default'];
            if ($ra_is_default == 1) {
                $cr_id = $default_addr ['cr_id'];
                $ary_logistic = D('Logistic')->getLogistic($cr_id,$ary_tmp_cart);
            }else{//无默认收货地址时 系统默认为第一个收货地址为临时地址
				$cr_id = $default_addr ['cr_id'];
                $ary_logistic = D('Logistic')->getLogistic($cr_id,$ary_tmp_cart);
			}
        }
		//判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as $key=>$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_price_data ['total_price'] >= $lt_expressions['logistics_configure']){
                $ary_logistic[$key]['logistic_price'] = 0;
            }
        }
        if(is_array($default_addr)){
            $default_addr['ra_mobile_phone'] = empty($default_addr['ra_mobile_phone']) ? '' : vagueMobile($default_addr['ra_mobile_phone']);
			$default_addr['ra_phone'] = empty($default_addr['ra_phone']) ? '' : vagueMobile($default_addr['ra_phone']);
        }
        foreach($ary_addr as &$val){
            $val['ra_mobile_phone'] = empty($val['ra_mobile_phone']) ? '' : vagueMobile($val['ra_mobile_phone']);
        }		
		return array('ary_logistic'=>$ary_logistic,'zt_logistic'=>$zt_logistic,'ary_addr'=>$ary_addr,'default_addr'=>$default_addr,'count_addr'=>$count_addr);
	}
	
    /**
     * 获取会员支付方式
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-19
     */	
	public function getMembersPaymentCfg($m_id=0,$ary_logistic,$type = 0){
		if($m_id ==0){
			$ary_member = session("Members");
			$m_id = $ary_member['m_id'];
		}
        // 获取支付方式
        $payment = D('PaymentCfg');
        //$payment_cfg = $payment->getPayCfg();
		$pay_where=array('pc_status'=>1);
        if($type == 0){
            //默认所有不属于2的
            $pay_where["pc_source"] = array('neq','2');
        }elseif($type == 1){
            //pc端显示的支付方式
            $pay_where["pc_source"] = array('not in','2,4,6');
        }elseif($type == 2){
            //wap端显示的支付方式
            $pay_where["pc_source"] = array('not in','1,2,3');
        }elseif($type == 3){
            //app端显示的支付方式
            $pay_where["pc_source"] = array('not in','1,2,4,5');
        }
		$pay_order=array('pc_position' => 'asc');
		$pay_field=array('pc_abbreviation,pc_id,pc_custom_name,pc_memo,pc_fee,pc_pay_type,pc_status');
		$payment_cfg = D('PaymentCfg')->getPayList($pay_where,$pay_field,$pay_order);
		 // 支付方式
        $ary_paymentcfg = array();
//        echo'<pre>';print_r($ary_data['payment_cfg']);die;
        $i=0;
        foreach($payment_cfg as $k => $paymentcfg){
			if($ary_logistic=='' && $paymentcfg['pc_abbreviation']=='DELIVERY'){
				continue;
			}
			if($ary_logistic!=''){
				$first_logistic=reset($ary_logistic); 
				if($first_logistic['lc_cash_on_delivery']=='0' && $paymentcfg['pc_abbreviation']=='DELIVERY'){
					continue;
				}
			}
            $ary_paymentcfg[$i]['pc_id'] = $paymentcfg['pc_id'];
            $ary_paymentcfg[$i]['pc_custom_name'] = $paymentcfg['pc_custom_name'];
            $ary_paymentcfg[$i]['pc_memo'] = $paymentcfg['pc_memo'];
            $ary_paymentcfg[$i]['pc_fee'] = ($paymentcfg['pc_fee'] == "0.000")?'':(float)$paymentcfg['pc_fee']."元";
            if($paymentcfg['pc_pay_type'] == "alipay" && $paymentcfg['pc_fee'] != "0.000"){
                $ary_paymentcfg[$i]['pc_fee'] = (float)$paymentcfg['pc_fee']."%";
            }
			$i++;
        }
		
		return array('payment_cfg'=>$payment_cfg,'ary_paymentcfg'=>$ary_paymentcfg);
	}
	
    /**
     * 获取会员支付方式
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-20
     */	
	public function getMembersPaymentCfgWap($m_id=0,$ary_logistic){	
	    // 获取支付方式
        $payment = D('PaymentCfg');
        //$payment_cfg = $payment->getPayCfg(1);
		$payment_cfg = $payment->getPaymentList('wap');
        // 支付方式
		foreach($payment_cfg as $k=>$cfg){
			if($ary_logistic=='' && $cfg['pc_abbreviation']=='DELIVERY'){
				unset($payment_cfg[$k]);
				continue;
			}
			if($ary_logistic!=''){
				$first_logistic=reset($ary_logistic); 
				if($first_logistic['lc_cash_on_delivery']=='0' && $cfg['pc_abbreviation']=='DELIVERY'){
					unset($payment_cfg[$k]);
					continue;
				}
			}
			if($cfg['pc_abbreviation'] == 'WEIXIN'){
				$is_weixin = is_weixin();
				if($is_weixin != true){
					unset($payment_cfg[$k]);
				}
			}
		}
		
		return $payment_cfg;
	}
	
	
    /**
     * 获取会员发票信息
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-19
     */	
	public function getInvoiceData($m_id=0){
		if($m_id ==0){
			$ary_member = session("Members");
			$m_id = $ary_member['m_id'];
		}
        // 发票信息
        $p_invoice = D('Invoice')->get();
        $invoice_type = explode(",", $p_invoice ['invoice_type']);
        $invoice_head = explode(",", $p_invoice ['invoice_head']);
        $invoice_content = explode(",", $p_invoice ['invoice_content']);

        $invoice_info ['invoice_comom'] = $invoice_type [0];
        $invoice_info ['invoice_special'] = $invoice_type [1];

        $invoice_info ['invoice_personal'] = $invoice_head [0];
        $invoice_info ['invoice_unit'] = $invoice_head [1];
        $invoice_info ['is_invoice'] = $p_invoice ['is_invoice'];
        $invoice_info ['is_auto_verify'] = $p_invoice ['is_auto_verify'];
        // 发票收藏列表
        $invoice_list = D('InvoiceCollect')->get($m_id);
		return array('invoice_content'=>$invoice_content,'invoice_info'=>$invoice_info,'invoice_list'=>$invoice_list);
	}
	
    /**
     * 判断是否允许购买
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-19
     */	
	 public function isCartAuthorize($ary_cart_info){
		 $is_authorize = true;
		 foreach($ary_cart_info as $ary_cart){
			 if(isset($ary_cart['authorize'])){
				 if($ary_cart['authorize'] != true){
					 return false;
				 }
			 }else{
				 foreach($ary_cart as $sub_cart){
					 if($sub_cart['authorize'] != true){
						return false; 
					 }
				 }
			 }
		 }
		return  $is_authorize;
	 }
	 
	/**
     * 获取商品促销名称
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2013-10-21
     */	
	 public function handleCartName($pro_data,$ary_cart){
		foreach($ary_cart as  $sub_key=>$sub_cart){
			//暂时只处理普通商品
			if(isset($sub_cart['pdt_id'])){
				$ary_cart[$sub_key]['pdt_rule_name'] .= $pro_data[$sub_cart['pdt_id']]['pmn_name'].','.$sub_cart['rule_info']['name'];
				$ary_cart[$sub_key]['pdt_rule_name'] = trim($ary_cart[$sub_key]['pdt_rule_name'],',');
			}
		}
		return  $ary_cart;
	 } 
	 
	/**
     * 根据购物车信息获取sgp
     *
     * @author wangguibin <Wangguibin@guanyisoft.com>
     * @date 2015-12-03
     */	
		
	 public function getSgp($str_pid,$cart_data){
		$ary_pid = explode(',', $str_pid);
		foreach ($cart_data as $key=>$item){
			if(in_array($key, $ary_pid)) {
				$item['type'] || $item['type'] = 0;
				$item['fc_id'] || $item['fc_id'] = 0;
				//$key = 'freerecommend','free1' 这时$item是二维数组
				if(!is_numeric($key)) {
					$key = preg_replace('/[0-9]/','', $key);
					foreach($item['pdt_id'] as $k=>$pdt_id) {
						$g_id = M('goods_products')->where(array('pdt_id'=>$pdt_id))->getField('g_id');
						$ary_sgp[] = array(
							$g_id,$pdt_id,$item['num'][$k],$key,$item['fc_id']
						);
					}
				}else {
					$item['type'] == 1 && $item['type'] = 'point';
					$item['type'] == 0 && $item['type'] = 'item';
					//如果购物车没有存储g_id
					if (empty($item['g_id'])) {
						$item['g_id'] = M('goods_products')->where(array('pdt_id' => $item['pdt_id']))->getField('g_id');
					}
					$ary_sgp[] = array(
						$item['g_id'], $item['pdt_id'], $item['num'], $item['type'], '0'
					);
				}
			}
		}
		return $ary_sgp;
	 }
}
