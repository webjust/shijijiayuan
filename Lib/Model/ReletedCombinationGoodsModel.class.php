<?php
/**
 * 组合商品模型
 *
 * @package Model
 * @version 7.2
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-07-2
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ReletedCombinationGoodsModel extends GyfxModel{
     /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-22
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('releted_combination_goods',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 根据组合商品id获取该组合商品总价、优惠金额
     *
     * @param int g_id 组合商品id
     * @param int buy_nums 购买数量
     *
     * @return array all_price 套餐总价 coupon_price 优惠金额 all_nums 套餐总数
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-07-3
     */
    public function getCombinationGoodsPrice($g_id,$buy_nums = 1){
        $all_price = 0.00;//套餐总监
        $coupon_price = 0.00;//优惠金额
        $combination = D("ReletedCombinationGoods");
        $all_nums = 0;//套餐总数
        $array_related_combination = $this->where(array('g_id'=>$g_id))->select();
        foreach($array_related_combination as $rel_k=>$rel_v){
            //获取单件商品原价
            $pdt_price = D("GoodsProductsTable")->where(array('pdt_id'=>$rel_v['releted_pdt_id']))->getField('pdt_sale_price');
            $all_nums +=  $rel_v['com_nums'];
            $all_price = $rel_v['com_nums']*$rel_v['com_price']+$all_price;
            $pdt_all_price = $rel_v['com_nums']*$pdt_price;
            $coupon_price = ($pdt_all_price-$rel_v['com_nums']*$rel_v['com_price'])+$coupon_price;
        }
        return array('all_price'=>sprintf("%0.2f",($all_price*$buy_nums)),'coupon_price'=>sprintf("%0.2f",($coupon_price*$buy_nums)),'all_nums'=>$all_nums*$buy_nums);
    }
    
    /**
     * 根据组合商品id获取该组合重量
     *
     * @param  $combo_id 组合商品id
     * @return int weight 组合商品总价
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-07-5
     */
    public function getComboWeight($combo_id){
        $combo_weight=0;
        $ary_related_combo = $this->field(array("releted_pdt_id"))->where(array('pdt_id'=>$combo_id))->select();
        if(!empty($ary_related_combo)){
            foreach ($ary_related_combo as $value) {
                $ary_pdt_id[] = $value['releted_pdt_id'];
            }
            $str_pdt_id = implode(',', $ary_pdt_id);
            $where['pdt_id']= array('in', $str_pdt_id);
            $result=D("GoodsProducts")->field(array("pdt_weight"))->where($where)->select();
            foreach ($result as $value) {
                $combo_weight +=$value['pdt_weight'];
            }
        }
        return $combo_weight;
    }
    
    /**
     * 根据组合商品id获取该组合list
     *
     * @param  $combo_id 组合商品id
     * @return array result 组合商品总价
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-07-5
     */
    public function getComboList($combo_id){
        $result=array();
        $where['fx_releted_combination_goods.pdt_id']= $combo_id;
        $field=array('fx_goods.gt_id','fx_goods.g_sn','fx_goods_info.g_name','fx_goods_products.pdt_id',
                     'fx_goods_products.pdt_sn','fx_goods_products.pdt_cost_price','fx_goods_products.pdt_sale_price',
                     'fx_releted_combination_goods.com_nums','fx_releted_combination_goods.com_price','fx_releted_combination_goods.releted_pdt_id',
                     'fx_releted_combination_goods.g_id');
        $result=M('releted_combination_goods',C('DB_PREFIX'),'DB_CUSTOM')
                ->join('fx_goods on fx_goods.g_id = fx_releted_combination_goods.g_id')
                ->join('fx_goods_info on fx_goods_info.g_id = fx_releted_combination_goods.g_id')
                ->join('fx_goods_products on fx_goods_products.pdt_id = fx_releted_combination_goods.pdt_id')
                ->field($field)->where($where)->select();
        return $result;
    }
    
    /**
     * 根据组合商品id获取该组合货品id
     *
     * @param  $combo_id 组合商品id
     * @return array result 组合商品总价
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-07-08
     */
    public function getComboReletedList($where,$field){
        $ary_related_combo = $this->field($field)->where($where)->select();
        return $ary_related_combo;
    }

}