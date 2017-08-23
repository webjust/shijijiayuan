<?php
/**
 * 作废订单
 *
 * API 参数
 *  - outer_tid: 外部订单号
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_TradeCancel extends Top_Request
{
}
/**
 * 作废订单
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'tid' => '287967484734'
 * )
 * </code>
 */
class Top_Response_TradeCancel extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['cancel_order_response']['trade'])){
			$this->result = $this->result['cancel_order_response']['trade'];
		}
	}
}
Top_ApiManager::add(
    'TradeCancel',
    array(
        'method' => 'ecerp.trade.cancel',
        'parameters' => array(
			'required' => array(
                'outer_tid'
            )
        )
    )
);