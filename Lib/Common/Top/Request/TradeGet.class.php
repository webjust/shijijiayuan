<?php

/**
 * 查询订单
 *
 * API 参数
 *  - fields: 需要返回的订单对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_TradeGet extends Top_Request {
    
}

/**
 * 查询订单
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'trades' => array(
 *         'trade' => array(
 *             array(
 *                 'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                 'djbh' => 'DD00007715',
 *                 'lydh' => '83412656374952',
 *                 'ddspmxs' => array(
 *                 		'ddspmx' => array()
 *                 ),
 *                 'ddzfmxs' => array(
 *                 		'ddzfmx' => array()
 *                 )
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_TradeGet extends Top_Response {

    protected function postParse() {
        if (isset($this->result['trades'])) {
            if (isset($this->result['trades']['trade']['guid'])) {
                $this->result['trades']['trade'] = array($this->result['trades']['trade']);
            }
            foreach ($this->result['trades']['trade'] as &$trade) {
                if (isset($trade['ddspmxs']['ddspmx']['guid'])) {
                    $trade['ddspmxs']['ddspmx'] = array($trade['ddspmxs']['ddspmx']);
                }
                if (isset($trade['ddzfmxs']['ddzfmx']['guid'])) {
                    $trade['ddzfmxs']['ddzfmx'] = array($trade['ddzfmxs']['ddzfmx']);
                }
            }
            unset($trade);
        }
    }

}

Top_ApiManager::add(
        'TradeGet', array(
    'method' => 'ecerp.trade.get',
    'parameters' => array(
        'required' => array(
            'fields'
        ),
        'other' => array(
            'condition',
            'page_no',
            'page_size',
            'orderby',
            'orderbytype'
        )
    ),
    'fields' => array(
        ':all' => array(
            '*'
        )
    )
        )
);