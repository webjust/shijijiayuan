<?php
/**
 * 查询订单状态
 *
 * API 参数
 *  - fields: 需要返回的商品对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_TradeStateGet extends Top_Request
{
}
/**
 * 查询订单状态
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'tradestates' => array(
 *         'tradestate' => array(
 *             array(
 *                 'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                 'djbh' => 'DD00007715',
 *                 'lydh' => '83412656374952',
 *                 'shenhe' => 1,
 *                 'shenherq' => '2011-12-22T14:57:56.983',
 *                 'cwsh' => 1,
 *                 'cwshrq' => '2011-12-22T14:57:56.983',
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_TradeStateGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['tradestates'])){
			if(isset($this->result['tradestates']['tradestate']['guid'])){
				$this->result['tradestates']['tradestate'] = array($this->result['tradestates']['tradestate']);
			}
		}
	}
}
Top_ApiManager::add(
    'TradeStateGet',
    array(
        'method' => 'ecerp.tradestate.get',
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