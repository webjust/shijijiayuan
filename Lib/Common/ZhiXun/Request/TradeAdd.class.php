<?php

/**
 * 新增订单
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_TradeAdd extends Top_Request {
    
}

/**
 * 新增订单
 * 返回值示例：
 * <code>
 * array(
 * 	'created' => '2012-8-13 10:44:47',
 * 	'tid' => '287967484734'
 * )
 * </code>
 */
class Top_Response_TradeAdd extends Top_Response {

    protected function postParse() {
        if (isset($this->result['trade_orders_response']['trade'])) {
            $this->result = $this->result['trade_orders_response']['trade'];
        }
    }

}

Top_ApiManager::add(
        'TradeAdd', array(
            'method' => 'ecerp.trade.add_order_new',
            'parameters' => array(
                'required' => array(
                    'mail', 'itemsns', 'prices', 'nums', 'receiver_name', 'receiver_address', 'receiver_state',
                    'receiver_city', 'receiver_district', 'logistics_type', 'outer_tid', 'outer_shop_code','zxyss'
                ),
                'other' => array(
                  'skusns', 'receiver_phone', 'receiver_mobile', 'outer_ddly', 'buyer_message', 'store_code',
                    'receiver_zip', 'logistics_fee', 'fptt', 'syfp', 'lxdm', 'invoice_content', 'ticket_no', 'pay_codes', 'pay_moneys',
                    'pay_datatimes', 'autosplit','trade_memo','yhqyxqsrq','yhjyxjsrq','zxbtwobjcrq','yhqbh','yhqyxjsrq',
                    'yhqje','zxys','zxbtwobdj','isuseyhq',''
                )
            )
        )
);