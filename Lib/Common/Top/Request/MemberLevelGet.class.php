<?php
/**
 * 得到会员等级信息
 *
 * API 参数
 *  - fields: 需要返回的会员对象等级字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_MemberLevelGet extends Top_Request
{
}
/**
 * 搜索会员等级信息
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'hyjbs' => array(
 *         'hyjb' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'jbdm' => '001',
 *                 'jbmc' => '普通会员',
 *                 'jbzk' => '0.9500',
 *                 'is_ty' => 1,
 *                 'xtmr' => 0,
 *                 'zq' => '0',
 *                 'jfbz' => '0.0000',
 *                 'isautoupgrad' => '1'
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_MemberLevelGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['hyjbs'])){
			if(isset($this->result['hyjbs']['hyjb']['guid'])){
				$this->result['hyjbs']['hyjb'] = array($this->result['hyjbs']['hyjb']);
			}
		}
	}
}
Top_ApiManager::add(
    'MemberLevelGet',
    array(
        'method' => 'ecerp.hyjb.get',
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