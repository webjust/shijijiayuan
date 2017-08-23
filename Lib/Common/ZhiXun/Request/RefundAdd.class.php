<?php
/**
 * 新增退款单
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_RefundAdd extends Top_Request
{
}
/**
 * 新增退款单
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'djbh' => 'TKD00000096'
 * )
 * </code>
 */
class Top_Response_RefundAdd extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['trade_refund_response']['trade'])){
			$this->result = $this->result['trade_refund_response']['trade'];
		}
	}
}
Top_ApiManager::add(
    'RefundAdd',
    array(
        'method' => 'ecerp.trade.addtkd',
        'parameters' => array(
			'required' => array(
                'mail','outer_shop_code','outer_tid','pay_codes','pay_moneys','outer_refundid'
            ),
            'other' => array(
            	'bank_account','ywydm','trade_memo'
            )
        )
    )
);