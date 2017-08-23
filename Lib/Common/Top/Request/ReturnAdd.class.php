<?php
/**
 * 新增退货单
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_ReturnAdd extends Top_Request
{
}
/**
 * 新增退货单
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'djbh' => 'THD00000098'
 * )
 * </code>
 */
class Top_Response_ReturnAdd extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['trade_addthd_response']['trade'])){
			$this->result = $this->result['trade_addthd_response']['trade'];
		}
	}
}
Top_ApiManager::add(
    'ReturnAdd',
    array(
        'method' => 'ecerp.trade.addthd',
        'parameters' => array(
			'required' => array(
                'mail','outer_shop_code','outer_tid','outer_refundid','itemsns','nums','prices'
            ),
            'other' => array(
            	'pay_codes','skusns','pay_moneys','pay_datatimes','pay_accounts','pay_memos',
            	'logistics_type','logistics_fee','invoice_no','trade_memo'
            )
        )
    )
);