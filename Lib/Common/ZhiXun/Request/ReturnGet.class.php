<?php
/**
 * 查询退货单
 *
 * API 参数
 *  - fields: 需要返回的退货单对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_ReturnGet extends Top_Request
{
}
/**
 * 查询退货单
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'thds' => array(
 *         'thd' => array(
 *             array(
 *                 'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                 'djbh' => 'THD00000188',
 *                 'lydh' => 'DD00000754',
 *                 'hy_guid' => 'FD6A4729-9696-47B4-B1F5-BD2CE578A44D',
 *                 'thdmxs' => array(
 *                 		'thdmx' => array()
 *                 ),
 *                 'thdmx2s' => array(
 *                 		'thdmx2' => array()
 *                 ),
 *                 'thdzfmxs' => array(
 *                 		'thdzfmx' => array()
 *                 )
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_ReturnGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['thds'])){
			if(isset($this->result['thds']['thd']['guid'])){
				$this->result['thds']['thd'] = array($this->result['thds']['thd']);
			}
			foreach($this->result['thds']['thd'] as &$trade){
				if(isset($trade['thdmxs']['thdmx']['guid'])){
					$trade['thdmxs']['thdmx'] = array($trade['thdmxs']['thdmx']);
				}
				if(isset($trade['thdmx2s']['thdmx2']['guid'])){
					$trade['thdmx2s']['thdmx2'] = array($trade['thdmx2s']['thdmx2']);
				}
				if(isset($trade['thdzfmxs']['thdzfmx']['guid'])){
					$trade['thdzfmxs']['thdzfmx'] = array($trade['thdzfmxs']['thdzfmx']);
				}
			}
			unset($trade);
		}
	}
}
Top_ApiManager::add(
    'ReturnGet',
    array(
        'method' => 'ecerp.thd.get',
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