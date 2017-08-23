<?php

/**
 * 淘宝供销平台采购单模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-05-02
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TopPurchaseOrderModel extends GyfxModel {

    /**
     * 从淘宝供销平台下载采购单数据，目前只同步
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-02
     * @param int $page 下载的页码
     */
    public function download($page = 1, $page_size = 30) {
        //获取分销平台API实例
        $Top = new Fenxiao();
        //只查询90天以内的订单
        $ary_param = array(
            'page_no' => $page,
            'page_size' => $page_size,
            'start_created' => date('Y-m-d H:i:s', mktime() - 86400 * 7),
            'end_created' => date('Y-m-d H:i:s'),
        );
        //从API取回的原始数据
        $ary_res = $Top->taobaoFenxiaoOrdersGet($ary_param);

        $ary_orders = $ary_res["data"]["fenxiao_orders_get_response"]["purchase_orders"]["purchase_order"];
        $SubOrder = D('TopSubpurchaseOrder');

        foreach ($ary_orders as $ary_order) {
            //循环replace插入订单数据
            $ary_replace_orders = array(
                'fenxiao_id' => $ary_order['fenxiao_id'],
                'pay_type' => $ary_order['pay_type'],
                'trade_type' => $ary_order['trade_type'],
                'distributor_from' => $ary_order['distributor_from'],
                'id' => $ary_order['id'],
                'status' => $ary_order['status'],
                'memo' => $ary_order['memo'],
                'shipping' => $ary_order['shipping'],
                'logistics_company_name' => (isset($ary_order['logistics_company_name']) && '' != $ary_order['logistics_company_name']) ? $ary_order['logistics_company_name'] : '',
                'logistics_id' => (isset($ary_order['logistics_id']) && '' != $ary_order['logistics_id']) ? $ary_order['logistics_id'] : 0,
                'order_messages' => $ary_order['order_messages'],
                'created' => $ary_order['created']
            );
            //$replace_result 更新的结果，非自增主键，1为成功
            $replace_result = $this->add($ary_replace_orders, array(), true);
            if (false === $replace_result) {
                return array('result' => false, 'message' => '采购单数据插入失败');
            }
            //插入或者更新成功
            //继续插入订单详情
            $ary_sub_orders = $ary_order['sub_purchase_orders']['sub_purchase_order'];
            foreach ($ary_sub_orders as $ary_sub_order) {
                $ary_replace_sub_order = array(
                    'fenxiao_id' => $ary_order['fenxiao_id'],
                    'status' => $ary_sub_order['status'],
                    'refund_fee' => $ary_sub_order['refund_fee'],
                    'item_id' => $ary_sub_order['item_id'],
                    'order_200_status' => $ary_sub_order['order_200_status'],
                    'auction_price' => $ary_sub_order['auction_price'],
                    'num' => $ary_sub_order['num'],
                    'title' => $ary_sub_order['title'],
                    'price' => $ary_sub_order['price'],
                    'total_fee' => $ary_sub_order['total_fee'],
                    'distributor_payment' => $ary_sub_order['distributor_payment'],
                    'buyer_payment' => $ary_sub_order['buyer_payment'],
                    'bill_fee' => $ary_sub_order['bill_fee'],
                    'sc_item_id' => $ary_sub_order['sc_item_id'],
                    'item_outer_id' => $ary_sub_order['item_outer_id'],
                    'sku_outer_id' => $ary_sub_order['sku_outer_id'],
                    'sku_properties' => $ary_sub_order['sku_properties'],
                    'created' => $ary_sub_order['created']
                );
                //订单详情插入的结果，返回自增主键
                $sub_replace_result = $SubOrder->add($ary_replace_sub_order, array(), true);
                if (false === $sub_replace_result) {
                    //echo $SubOrder->getLastSql();exit;
                    return array('result' => false, 'message' => '子采购单(订单详情)数据插入失败');
                }
            }
        }

        return array('result' => true, 'message' => "采购单第{$page}页数据下载成功", 'total_results' => $ary_res['total_results']);
    }

    /**
     * 获取总的记录数
     *
     * @author Mthern
     * @date 2013-05-02
     * @version 1.0
     */
    public function getPageInfo() {
        $array_params = array();
        //本次下载的页数，默认是第1页，以传入参数为准
        $array_params["page_no"] = 1;
        //每次下载多少条数据，如果需要修改，请在这里修改
        $array_params["page_size"] = 1;
        $array_params['start_created'] = date('Y-m-d H:i:s', mktime() - 86400 * 7);
        $array_params['end_created'] = date('Y-m-d H:i:s');
        //实例化供销平台对接API操作类
        $obj_fenxiao_api = new Fenxiao();

        //获取供销平台数据
        $array_result = $obj_fenxiao_api->taobaoFenxiaoOrdersGet($array_params);
        //对返回值进行判断
        if (false === $array_result["status"]) {
            //如果调用API出错，则返回需要的数据
            return $array_result;
        }

        $array_response_data = $array_result["data"]["fenxiao_orders_get_response"];
        //总页数处理
        if (isset($array_response_data["total_results"]) && is_numeric($array_response_data["total_results"])) {
            return $array_response_data["total_results"];
        }
        //可能存在的异常情况，也可能代码永远都执行不到这里
        return 0;
    }

    ##########################################################################
    /**
     * 混乱的零售价
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-08
     * @param string $fenxiao_id 供销平台采购单号
     * @return array 返回乱价查询结果，注意'result'键返回的是字符串error/true/false
     */

    public function priceWrong($fenxiao_id) {
        $SubOrder = D('TopSubPurchaseOrder');
        //$FenxiaoProduct = D('TopGoodsInfo');

        $array_result = $SubOrder
                ->where(array('fenxiao_id' => $fenxiao_id))
                ->join('left join fx_top_goods_info on fx_top_goods_info.pid = fx_top_subpurchase_order.item_id')
                ->select();
        if (false == $array_result) {
            //没查到结果
            //子采购单内商品有误，没有从供销平台下载下来，或没有即时更新
            return array('result' => 'error', 'message' => '子采购单内商品数据有误');
        } else {
            //寻找乱价的商品
            $return_data = array();

            foreach ($array_result as $v) {
                $price = $v['buyer_payment'] / $v['num'];
                if ($price < $v['retail_price_low'] || $price > $v['retail_price_high']) {
                    array_push($return_data, $v);
                }
            }

            if (empty($array_result)) {
                //不存在乱价
                return array('result' => 'false', 'message' => '子采购单正常');
            } else {
                //存在乱价商品
                return array('result' => 'true', 'message' => '本子采购单存在乱价商品', 'data' => $array_result);
            }
        }
    }

    /**
     * 判断是否窜货
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     * @param string $fenxiao_id 供销平台采购单号
     * @return array 返回窜货查询结果，注意'result'键返回的是字符串error/true/false
     */
    public function cuanHuo($fenxiao_id) {
        $SubOrder = D('TopSubPurchaseOrder');
        $array_result = $SubOrder->where(array('fenxiao_id' => $fenxiao_id))->select();

        if (false == $array_result) {
            //没查到结果
            //子采购单内商品有误，没有从供销平台下载下来，或没有即时更新
            return array('result' => 'error', 'message' => '子采购单内商品数据有误');
        } else {
            //寻找窜货的子采购单
            $return_data = array();

            foreach ($array_result as $v) {
                if ($v['order_200_status'] == 'TRADE_FINISHED' && $v['status'] == 'TRADE_CLOSED') {
                    array_push($return_data, $v);
                }
            }

            if (empty($array_result)) {
                //不存在乱价
                return array('result' => 'false', 'message' => '子采购单正常');
            } else {
                //存在乱价商品
                return array('result' => 'true', 'message' => '本子采购单可能存在乱价情况', 'data' => $array_result);
            }
        }
    }

}