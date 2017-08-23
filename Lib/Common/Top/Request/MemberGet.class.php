<?php
/**
 * 得到会员信息
 *
 * API 参数
 *  - fields: 需要返回的会员对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_MemberGet extends Top_Request
{
}
/**
 * 搜索会员信息
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'huiyuans' => array(
 *         'huiyuan' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'fguid' => '878885F1-60CD-4BD4-84B4-B073530B5CF0',
 *                 'hydm' => '00030006',
 *                 'hymc' => '溜冰鞋',
 *                 'hyly' => 2,
 *                 'xb' => 1,
 *                 'shouji' => '18727803403'
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_MemberGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['huiyuans'])){
			if(isset($this->result['huiyuans']['huiyuan']['guid'])){
				$this->result['huiyuans']['huiyuan'] = array($this->result['huiyuans']['huiyuan']);
			}
		}
	}
}
Top_ApiManager::add(
    'MemberGet',
    array(
        'method' => 'ecerp.huiyuan.get',
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