<?php

/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/10/26
 * Time: 11:38
 */
class SpikeModel extends GyfxModel
{
    /**
     * @param $ary_cart
     * @author Nick
     * @date 2015-11-23
     * @return array
     */
    public function getSpikePrice($ary_cart) {
        $pdt_id =  $ary_cart['pdt_id'];
        $item_num = $ary_cart['num'];
        $item_type_id =  $ary_cart['sp_id'];
        //��ȡ��ɱ��������
        $ary_spike_items = $this->where(array(
            'sp_id' => $item_type_id,
            'sp_status'=>1,
        ))->find();

        $ary_spike_items['pdt_sale_price'] = M('goods_products')->where(array('pdt_id'=>$pdt_id))->getField('pdt_sale_price');
        $ary_spike_items['pdt_all_sale_price'] = $ary_spike_items['pdt_sale_price']* $item_num;
        //秒杀价格
        switch($ary_spike_items['sp_tiered_pricing_type']) {
            //直接减优惠金额
            case 1:
                $pdt_final_price = $ary_spike_items['pdt_sale_price'] - $ary_spike_items['sp_price'];
                $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                $ary_spike_items['sp_price'] = $pdt_final_price;
                break;
            //设置优惠折扣
            case 2:
                $pdt_final_price = $ary_spike_items['pdt_sale_price'] * $ary_spike_items['sp_price'];
                $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                $ary_spike_items['sp_price'] = $pdt_final_price;
                break;
        }
        $ary_spike_items['sp_subtotal_price'] = $ary_spike_items['sp_price']* $item_num;

        return array('status'=>'success','data'=>$ary_spike_items);
    }
    /**
     * ��ȡ��ɱ��Ʒ�ܽ��
     * @param $ary_items
     */
    public function getDetailWithPrice($ary_items) {
        $item = current($ary_items);
        if(!empty($item['type_id'])) $item['sp_id']  = $item['type_id'];
        $ary_res = $this->getSpikePrice($item);
        return $ary_res['data'];
    }

    /**
     * 获取预售商品详情页sku信息
     * @param int $sp_id
     * @param array $members
     * @param int $pdt_id
     * @param bool $qiniu_pic
     * @return array
     * @author Nick
     * @date 2015-10-28
     */
    public function getDetails($sp_id, $members=array(), $pdt_id=0, $qiniu_pic=false) {
        //获取秒杀设置，秒杀初始价，总限售数，没人限购数，等
        $ary_spike = $this->where(array(
            'sp_id'	=>	(int)$sp_id
        ))->find();
        $ary_spike['buy_status'] = $ary_spike['sp_status'];

//		dump($ary_spike);die;
        $sp_per_number = 0;
        $sp_number = $ary_spike['sp_number'];
        $buy_nums = $ary_spike['sp_now_number'];

        $gid = $ary_spike['g_id'];
        $m_id = $members['m_id'];
        //获取该商品作为普通商品时的货品价格和库存
        $ary_goods_pdt = D('Goods')->getDetails($gid, $members, $pdt_id, $qiniu_pic);
        $pdt_detail = $ary_goods_pdt['page_detail'];
        //商品库存数
        $g_stock = $pdt_detail['gstock'];
        //如果秒杀限购数量为0或设置的限售数大于商品库存数，
        //则限购数等于商品库存数
        if($sp_number==0 || $sp_number > $g_stock) {
            $sp_number = $g_stock;
        }

        //如果秒杀会员限购数量为0
        //则会员限购数等于总限购数
        if($sp_per_number ==0 ) {
            $sp_per_number = $sp_number;
        }
        //已抢完
        if($buy_nums >= $sp_number) {
            $ary_spike['buy_status'] = 5;
            //总库存
            $avalible_stock = 0;
            //可购买数
            $sp_per_number = 0;
        }
        //未抢完
        else{
            //秒杀剩余库存总数
            $avalible_stock = $sp_number-$buy_nums;

        }
        //总库存
        $ary_goods_pdt['gstock'] = $avalible_stock;
        //可购买数
        $ary_goods_pdt['max_buy_number'] = $sp_per_number;
        //秒杀价格
        switch($ary_spike['sp_tiered_pricing_type']) {
            //直接减优惠金额
            case 1:
                foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
                    $pdt_final_price = $goods_pdts['pdt_set_sale_price'] - $ary_spike['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
                }
                break;
            //设置优惠折扣
            case 2:
                foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
                    $ary_spike['sp_price'] = min(1, $ary_spike['sp_price']);
                    $pdt_final_price = $goods_pdts['pdt_set_sale_price'] * $ary_spike['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
                }
                break;
        }
        if($qiniu_pic == true ){//七牛图片显示
            $ary_spike['sp_picture'] = D('QnPic')->picToQn($ary_spike['sp_picture']);
            $ary_spike['sp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_spike['sp_desc']);
            $ary_spike['sp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_spike['sp_mobile_desc']);
        }
        if(strtotime($ary_spike['sp_start_time']) > mktime()){
            $ary_spike['buy_status'] = 3;
        }elseif(strtotime($ary_spike['sp_end_time']) < mktime()){
            $ary_spike['buy_status'] = 4;
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
		//查询秒杀详情页是否显示商品详情
		$sp_goods_desc_status = M('Spike')->where(array('g_id'=>$gid))->getField('sp_goods_desc_status');
		$pdt_detail['ary_goods_default_pdt']['sp_goods_desc_status'] = $sp_goods_desc_status;
		$ary_spike['sp_goods_desc_status'] = $pdt_detail['ary_goods_default_pdt']['sp_goods_desc_status'];
		
        $ary_spike['sp_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_sale_price'];
        $ary_spike['pdt_set_sale_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_set_sale_price'];
        $ary_goods_pdt['page_detail'] = $pdt_detail;
        //判断该登陆会员是否已秒杀过该商品
        $ary_where = array();
        $ary_where["sl.m_id"] = $members['m_id'];
        $ary_where["sl.sp_id"] = $sp_id;
        $ary_where["o.o_status"] = array('neq', 2);
        $ary_where["r.or_processing_status"] = array('neq', 1);
        $count_spike_log =D('spike_log')
            ->alias('sl')
            ->join(C('DB_PREFIX').'orders as o on o.o_id = sl.o_id')
            ->join(C('DB_PREFIX').'orders_refunds as r on r.o_id = sl.o_id')
            ->where($ary_where)
            ->count('sl.pl_id');
        //echo D('spike_log')->getLastSql();die;
        //dump($count_spike_log);die;
        if($count_spike_log) {
            $ary_spike['buy_status'] = 0;
        }
        //dump($ary_spike);die;
        $ary_goods_pdt = array_merge($ary_goods_pdt, $ary_spike);
		//dump($ary_goods_pdt);die;
        return $ary_goods_pdt;
    }
}