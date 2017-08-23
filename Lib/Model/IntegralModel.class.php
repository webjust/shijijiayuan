<?php

/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/11/30
 * Time: 17:41
 */
class IntegralModel extends GyfxModel
{

    /**
     * @param $ary_cart
     * @author Nick
     * @date 2015-11-23
     * @return array
     */
    public function getIntegralPrice($ary_cart) {
        $pdt_id =  $ary_cart['pdt_id'];
        $item_num = $ary_cart['num'];
        $item_type_id =  $ary_cart['integral_id'];
        //获取积分兑换配置详情
        $ary_integral_items = $this->where(array(
            'integral_id' => $item_type_id,
            'integral_status'=>1,
        ))->find();

        $ary_integral_items['pdt_sale_price'] = M('goods_products')->where(array('pdt_id'=>$pdt_id))->getField('pdt_sale_price');
        $ary_integral_items['pdt_all_sale_price'] = $ary_integral_items['pdt_sale_price']* $item_num;

        $ary_integral_items['money_need_to_pay'] = $ary_integral_items['money_need_to_pay']* $item_num;
        $ary_integral_items['integral_need'] =  $ary_integral_items['integral_need']* $item_num;
        return array('status'=>'success','data'=>$ary_integral_items);
    }
    /**
     * ��ȡ��ɱ��Ʒ�ܽ��
     * @param $ary_items
     */
    public function getDetailWithPrice($ary_items) {
        $item = current($ary_items);
        $item['integral_id']  = $item['type_id'];
        $ary_res = $this->getIntegralPrice($item);
        return $ary_res['data'];
    }

    public function getDetails($item_id, $members, $pdt_id=0,$qiniu_pic=false) {
        //获取积分兑换设置，积分兑换初始价，总限售数，没人限购数，等
        $ary_integral = $this->where(array(
            'integral_id'	=>	(int)$item_id
        ))->find();
        $ary_integral['buy_status'] = $ary_integral['integral_status'];
//		dump($ary_integral);die;
        $integral_per_number = 1;
        $integral_number = $ary_integral['integral_num'];

        //实际购买数
        $buy_nums = $ary_integral['integral_now_number'];


        $gid = $ary_integral['g_id'];
        $m_id = $members['m_id'];
        if($ary_integral['integral_goods_desc_status'] == 1) {
            $good_info = D('GoodsInfo')->where(array('g_id' => $gid))->find();
            $ary_integral['g_desc'] = $good_info['g_desc'];
        }
        //获取该商品作为普通商品时的货品价格和库存
        $ary_goods_pdt = D('Goods')->getDetails($gid, $members, $pdt_id, $qiniu_pic);
        $pdt_detail = $ary_goods_pdt['page_detail'];
        //商品库存数
        $g_stock = $pdt_detail['gstock'];
        //如果积分兑换限购数量为0或设置的限售数大于商品库存数，
        //则限购数等于商品库存数
        if($integral_number==0 || $integral_number > $g_stock) {
            $integral_number = $g_stock;
        }

        //已满
        if($buy_nums >= $integral_number) {
            $ary_integral['buy_status'] = 5;
            //总库存
            $avalible_stock = 0;
            //可购买数
            $integral_per_number = 0;
        }
        //未满团
        else{
            //积分兑换剩余库存总数
            $avalible_stock = $integral_number-$buy_nums;
            //判断当前积分兑换数量是否达到上限（总限量）
            if($m_id){
                $member_buy_num = M('orders_items as oi',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field('SUM(oi_nums) as buy_nums')
                    ->join(C('DB_PREFIX').'orders as o on oi.o_id=o.o_id')
                    ->where(array(
                        'oi.m_id'=>$m_id,
                        'oi.oi_type'=>11,
                        'oi.fc_id'=>$ary_integral['integral_id'],
                        'o.o_status'=>array('neq', 2)
                    ))
                    ->find();
                //本积分兑换购买记录为空
                if(empty($member_buy_num)) {
                    $member_buy_num['buy_nums'] = 0;
                }

                //已购买总数小于会员限购数
                if ($member_buy_num['buy_nums'] < $integral_per_number) {
                    //可购买数=会员限购总数-已购买总数
                    $integral_per_number = $integral_per_number - $member_buy_num['buy_nums'];
                    //二者取其小
                    $integral_per_number = min($avalible_stock, $integral_per_number);
                }
                //购买数超过会员限购数
                else {
                    $ary_integral['buy_status'] = 0;
                }

            }else{
                //提示登录
                $ary_integral['buy_status'] = 2;
            }

        }
        //总库存
        $ary_goods_pdt['gstock'] = $avalible_stock;
        //可购买数
        $ary_goods_pdt['max_buy_number'] = $integral_per_number;
        foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
            $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
            $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $ary_integral['money_need_to_pay'];
            $pdt_detail['json_goods_pdts'][$psd_id]['integral_need'] = $ary_integral['integral_need'];
        }
        if($qiniu_pic == true ){//七牛图片显示
            $ary_integral['integral_picture'] = D('QnPic')->picToQn($ary_integral['integral_picture']);
            $ary_integral['integral_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_integral['integral_desc']);
            $ary_integral['integral_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_integral['integral_mobile_desc']);
        }
        if(strtotime($ary_integral['integral_start_time']) > mktime()){
            $ary_integral['buy_status'] = 3;
        }elseif(strtotime($ary_integral['integral_end_time']) < mktime()){
            $ary_integral['buy_status'] = 4;
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
        $ary_integral['pdt_set_sale_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_set_sale_price'];

        $ary_goods_pdt['page_detail'] = $pdt_detail;
        $ary_goods_pdt = array_merge($ary_goods_pdt, $ary_integral);
//		dump($ary_goods_pdt);die;
        return $ary_goods_pdt;
    }
}