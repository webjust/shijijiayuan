<?php
/**
* 预售模型层 Model 
* @author WangHaoYu <wanghaoyu@guanyisoft.com>
* @version 7.4.5
* @date 2013-11-27
*/
class PresaleModel extends GyfxModel {
    
	/**
	 * 计算预售阶级价格
     * @param $ary_cart
	 * @author Nick
	 * @date 2015-11-23
     *
     * @return array
	*/
	public function getPresalePrice($ary_cart = array()) {
        $item_type_id = $ary_cart['p_id'];
        $pdt_id = $ary_cart['pdt_id'];
        $item_num = $ary_cart['num'];
        //获取预售设置详情
        $ary_presale_items = $this->where(array(
            'p_id' => $item_type_id,
            'is_active'=>1,
            'p_deleted'=>0,
        ))->find();

        $ary_presale_items['pdt_sale_price'] = M('goods_products')->where(array('pdt_id'=>$pdt_id))->getField('pdt_sale_price');
        $ary_presale_items['pdt_all_sale_price'] = $ary_presale_items['pdt_sale_price'] * $item_num;
        // 取出价格阶级
        $rel_presale_price = M('related_presale_price', C('DB_PREFIX'),'DB_CUSTOM')->where(array(
            'p_id'=>$item_type_id
        ))->select();
        /*去掉价格阶梯
        $buy_nums = $ary_presale_items['p_pre_number'] + $ary_presale_items['p_now_number'];
        $max_num = 0;
        foreach ( $rel_presale_price as $rpp_k => $rpp_v ) {
            if ($buy_nums >= $rpp_v ['rgp_num'] && $rpp_v ['rgp_num'] > $max_num) {
                $max_num = $rpp_v ['rgp_num'];
                $ary_presale_items['p_price'] = $rpp_v ['rgp_price'];
            }
        }
        */
        //团购价格
        switch($ary_presale_items['p_tiered_pricing_type']) {
            //直接减优惠金额
            case 1:
                $pdt_final_price = $ary_presale_items['pdt_sale_price'] - $ary_presale_items['p_price'];
                $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                $ary_presale_items['p_price'] = $pdt_final_price;
                break;
            //设置优惠折扣
            case 2:
                $pdt_final_price = $ary_presale_items['pdt_sale_price'] * $ary_presale_items['p_price'];
                $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                $ary_presale_items['p_price'] = $pdt_final_price;
                break;
        }
        $ary_presale_items['p_subtotal_price'] = $ary_presale_items['p_price']* $item_num;
        return array('status'=>'success','data'=>$ary_presale_items);
	}

    /**
     * 获取预售商品总金额
     * @param $ary_items
     */
    public function getDetailWithPrice($ary_items) {
        $item = current($ary_items);
        if(!empty($item['type_id'])) $item['p_id'] = $item['type_id'];
        $ary_res = $this->getPresalePrice($item);

        return $ary_res['data'];
    }

    /**
     * 获取预售商品详情页sku信息
     * @param int $p_id
     * @param array $members
     * @param int $pdt_id
     * @param bool $qiniu_pic
     * @return array
     * @author Nick
     * @date 2015-10-28
     */
    public function getDetails($p_id, $members=array(), $pdt_id=0, $qiniu_pic=false) {
        //获取团购设置，团购初始价，总限售数，没人限购数，等
        $ary_presale = $this->where(array(
            'p_id'	=>	(int)$p_id
        ))->find();
        $ary_presale['buy_status'] = ($ary_presale['is_active'] && !$ary_presale['p_deleted']) ? 1 : 0;

//		dump($ary_presale);die;
        $p_per_number = $ary_presale['p_per_number'];
        $p_number = $ary_presale['p_number'];

        //目前已参团人数，虚拟购买数+实际购买数
        $buy_nums = $ary_presale['p_pre_number'] + $ary_presale['p_now_number'];
        $p_price_model = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
        //取出价格阶级
        $rel_presale_price = $p_price_model->where(array('p_id'=>$ary_presale['p_id']))->select();
        //当前所在价格阶梯
        $current_num_range = 0;
        foreach($rel_presale_price as $rp_k=>$rp_v){
            if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                $current_num_range = $rp_v['rgp_num'];
            }
        }
        //如果满足某价格阶梯，阶梯价覆盖初始价
        //去掉预售阶梯的价格
//        if(!empty($current_num_range)){
//            $ary_presale['p_price'] = $p_price_model->where(array('p_id'=>$rp_v['p_id'],'rgp_num'=>$current_num_range))->getField('rgp_price');
//        }

        $gid = $ary_presale['g_id'];
        $m_id = $members['m_id'];
        //获取该商品作为普通商品时的货品价格和库存
        $ary_goods_pdt = D('Goods')->getDetails($gid, $members, $pdt_id, $qiniu_pic);

        $pdt_detail = $ary_goods_pdt['page_detail'];
        //商品库存数
        $g_stock = $pdt_detail['gstock'];
        //如果团购限购数量为0或设置的限售数大于商品库存数，
        //则限购数等于商品库存数
        if($p_number==0 || $p_number > $g_stock) {
            $p_number = $g_stock;
        }

        //如果团购会员限购数量为0
        //则会员限购数等于总限购数
        if($p_per_number ==0 ) {
            $p_per_number = $p_number;
        }
        //已满团
        if($buy_nums >= $p_number) {
            $ary_presale['buy_status'] = 5;
            //总库存
            $avalible_stock = 0;
            //可购买数
            $p_per_number = 0;
        }
        //未满团
        else{
            //团购剩余库存总数
            $avalible_stock = $p_number-$buy_nums;
            //判断当前团购数量是否达到上限（总限量）
            if($m_id){
                $member_buy_num = M('presale_log as pl',C('DB_PREFIX'),'DB_CUSTOM')->field('SUM(num) as buy_nums')
                    ->join(C('DB_PREFIX').'orders as o on pl.o_id=o.o_id')
                    ->where(array(
                        'pl.m_id'=>$m_id,
                        'pl.p_id'=>$ary_presale['p_id'],
                        'o.o_status'=>array('neq', 2)
                    ))
                    ->find();
                //本团购购买记录为空
                if(empty($member_buy_num)) {
                    $member_buy_num['buy_nums'] = 0;
                }

                //已购买总数小于会员限购数
                if ($member_buy_num['buy_nums'] < $p_per_number) {
                    //可购买数=会员限购总数-已购买总数
                    $p_per_number = $p_per_number - $member_buy_num['buy_nums'];
                    //二者取其小
                    $p_per_number = min($avalible_stock, $p_per_number);
                }
                //购买数超过会员限购数
                else {
                    $ary_presale['buy_status'] = 0;
                }

            }else{
                //提示登录
                $ary_presale['buy_status'] = 2;
            }

        }
        //总库存
        $ary_goods_pdt['gstock'] = $avalible_stock;
        //可购买数
        $ary_goods_pdt['max_buy_number'] = $p_per_number;
        //团购价格
        switch($ary_presale['p_tiered_pricing_type']) {
            //直接减优惠金额
            case 1:
                foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
                    $pdt_final_price = $goods_pdts['pdt_set_sale_price'] - $ary_presale['p_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
                }
                break;
            //设置优惠折扣
            case 2:
                foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
                    $ary_presale['p_price'] = min(1, $ary_presale['p_price']);
                    $pdt_final_price = $goods_pdts['pdt_set_sale_price'] * $ary_presale['p_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
                }
                break;
        }
        if($qiniu_pic == true ){//七牛图片显示
            $ary_presale['p_picture'] = D('QnPic')->picToQn($ary_presale['p_picture']);
            $ary_presale['p_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_presale['p_desc']);
        }
        if(strtotime($ary_presale['p_start_time']) > mktime()){
            $ary_presale['buy_status'] = 3;
        }elseif(strtotime($ary_presale['p_end_time']) < mktime()){
            $ary_presale['buy_status'] = 4;
        }
        if(0 == $pdt_id) {
            $pdt_detail['ary_goods_default_pdt'] = reset($pdt_detail['json_goods_pdts']);
        }else {
            foreach($pdt_detail['json_goods_pdts'] as $goods_pdt) {
                if($goods_pdt['pdt_id'] == $pdt_id) {
                    $pdt_detail['ary_goods_default_pdt'] = $goods_pdt;
                    break;
                }
            }
        }
        $ary_presale['p_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_sale_price'];
        $ary_presale['pdt_set_sale_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_set_sale_price'];

        $ary_goods_pdt['page_detail'] = $pdt_detail;
        $ary_goods_pdt['ary_range_price'] = $rel_presale_price;
//        dump($rel_presale_price);die;
        $ary_goods_pdt = array_merge($ary_goods_pdt, $ary_presale);
//		dump($ary_goods_pdt);die;
        return $ary_goods_pdt;
    }
}