<?php

/**
 * 供销平台经销模式交易单监控模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-05-09
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TopTrademonitorModel extends GyfxModel {
    /**
     * 从淘宝供销平台下载采购单数据，目前只同步
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     * @param int $page 下载的页码
     */
    public function download($page = 1,$page_size = 30) {
        //获取分销平台API实例
        $Top = new Fenxiao();
        //只查询7天以内的订单
        $ary_param = array(
            'page_no' => $page,
            'page_size' => $page_size,
            'start_created' => date('Y-m-d H:i:s', mktime() - 86400 * 7),
            'end_created' => date('Y-m-d H:i:s'),
        );
        //从API取回的原始数据
        $ary_res = $Top->taobaoFenxiaoTrademonitorGet($ary_param);

        $ary_orders = $ary_res["data"]["fenxiao_orders_get_response"]["purchase_orders"]["purchase_order"];


        foreach ($ary_orders as $ary_order) {
            //循环replace插入订单数据
            $ary_replace_orders = array(
                'trade_monitor_id' => $ary_order['trade_monitor_id'],
                'distributor_nick' => $ary_order['distributor_nick'],
                'product_id' => $ary_order['product_id'],
                'product_title' => $ary_order['product_title'],
                'product_number' => $ary_order['product_number'],
                'tc_order_id' => $ary_order['tc_order_id'],
                'sub_tc_order_id' => $ary_order['sub_tc_order_id'],
                'status' => $ary_order['status'],
                'item_id' => $ary_order['item_id'],
                'item_title' => $ary_order['item_title'],
                'item_number' => $ary_order['status'],
                'item_sku_number' => $ary_order['item_sku_number'],
                'product_sku_number' => $ary_order['product_sku_number'],
                'item_price' => $ary_order['item_price'],
                'item_total_price' => $ary_order['item_total_price'],
                'buy_amount' => $ary_order['buy_amount'],
                'pay_time' => $ary_order['pay_time'],
                'buyer_nick' => $ary_order['buyer_nick'],
                'item_sku_name' => $ary_order['item_sku_name'],
                'retail_price_low' => $ary_order['retail_price_low'],
                'retail_price_high' => $ary_order['retail_price_high']
            );
            //$replace_result 更新的结果，非自增主键，1为成功
            $replace_result = $this->add($ary_replace_orders, array(), true);
            if (false === $replace_result) {
				return array('result' => false, 'message' => '监控交易单数据插入失败');
			}
        }
        return array('result' => true, 'message' => "监控交易单第{$page}页数据下载成功", 'total_results' => $ary_res['total_results']);

    }
}