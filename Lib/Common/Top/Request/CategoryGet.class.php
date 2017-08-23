<?php
/**
 * 得到分类信息
 *
 * API 参数
 *  - fields: 需要返回的分类对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_CategoryGet extends Top_Request
{
}
/**
 * 搜索分类信息
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'categorys' => array(
 *         'category' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'fguid' => '878885F1-60CD-4BD4-84B4-B073530B5CF0',
 *                 'code' => '00030006',
 *                 'name' => '溜冰鞋',
 *                 'level' => 2,
 *                 'isleaf' => 1
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_CategoryGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['categorys'])){
			if(isset($this->result['categorys']['category']['guid'])){
				$this->result['categorys']['category'] = array($this->result['categorys']['category']);
			}
		}
	}
}
Top_ApiManager::add(
    'CategoryGet',
    array(
        'method' => 'ecerp.category.get',
        'parameters' => array(
            'required' => array(
                'fields'
            ),
            'other' => array(
                'page_no',
                'page_size',
                'orderby',
            	'orderbytype'
            ),
            'spguid'=>''
        ),
        'fields' => array(
            ':all' => array(
                'guid','fguid','code','name','level','isleaf','sort'
            )
        )
    )
);