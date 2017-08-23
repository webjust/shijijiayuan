<?php
/**
 * 得到商品属性信息
 *
 * API 参数
 *  - fields: 需要返回的商品属性对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author listen
 * @license 
 * @version 1.0
 */
class Top_Request_PropertyGet extends Top_Request
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
class Top_Response_PropertyGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['spskus'])){
			if(isset($this->result['spskus']['spsku']['Guid'])){
				$this->result['spskus']['spsku'] = array($this->result['spskus']['spsku']);
			}
		}
	}
}
Top_ApiManager::add(
    'PropertyGet',
    array(
        'method' => 'ecerp.spsku.get',
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
                'Id','Guid','SP_GUID','SKUDM','SKUMC','GG1MC','GG2MC','Is_Del',
                'zxxw','zxyw','zxjk','zxtw','zxyc','zxkc','zxqc','zxzd','zxxc','zxkk',
                'zxdtw','zxxk','zxbw','zxxkw','zxzdsj','zxptdlnhj'
            )
        )
    )
);