<?php
/**
 * 查询退款单
 *
 * API 参数
 *  - fields: 需要返回的退款单对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_RefundGet extends Top_Request
{
}
/**
 * 查询退款单
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'tkds' => array(
 *         'tkd' => array(
 *             array(
 *                 'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                 'djbh' => 'TKD00000303',
 *                 'ydjh' => 'DD00000329',
 *                 'lydh' => 'THD00000329',
 *                 'zffs_guid' => 'FD6A4729-9696-47B4-B1F5-BD2CE578A44D',
 *                 'tkje' => '500.0000',
 *                 'zh' => '1',
 *                 'tkrq' => '2012-12-14T15:24:06.890',
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_RefundGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['tkds'])){
			if(isset($this->result['tkds']['tkd']['guid'])){
				$this->result['trades']['tkd'] = array($this->result['tkds']['tkd']);
			}
		}
	}
}
Top_ApiManager::add(
    'RefundGet',
    array(
        'method' => 'ecerp.tkd.get',
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