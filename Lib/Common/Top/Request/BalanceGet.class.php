<?php
/**
 * 得到会员结余款信息
 *
 * API 参数
 *  - fields: 需要返回的会员结余款对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_BalanceGet extends Top_Request
{
}
/**
 * 搜索会员结余款
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'balances' => array(
 *         'balance' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'total' => '100',
 *                 'frozen' => '0',
 *                 'hy_guid' => 'F991A034-7301-4035-A0A0-EF583F55D83D',
 *                 'hydm' => '10171'
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_BalanceGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['balances'])){
			if(isset($this->result['balances']['balance']['guid'])){
				$this->result['balances']['balance'] = array($this->result['balances']['balance']);
			}
		}
	}
}
Top_ApiManager::add(
    'BalanceGet',
    array(
        'method' => 'ecerp.balance.get',
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