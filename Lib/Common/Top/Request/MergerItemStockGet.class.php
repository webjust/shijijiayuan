<?php
/**
 * 查询组合商品库存 
 *
 * API 参数
 *  - fields: 需要返回的商品库存对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author Zhang jiasuo <zhangjiasuo@guanyisoft.com>
 * @license 
 * @version 1.0
 */
class Top_Request_MergerItemStockGet extends Top_Request
{
}
/**
 * 查询组合商品库存 
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'zhsps' => array(
 *         'zhsp' => array(
 *             array(
 *				   'rowno' => 1
 *                 'guid' => 'DED34FB0-9975-48D1-B23B-09B8A39EFBC5',
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'spdm' => '520520',
 *                 'zhsl2' => 0,
 *				   'dp_guid' => '7F07F517-C4DA-4E99-8C86-10B1EE2176C1',
 *                 'zhspmxs' => array(
 *                 		'zhspmx' => array(
 *                 			'guid' => 'DED34FB0-9975-48D1-B23B-09B8A39EFBC5',
 *                 			'zh_guid' =>'D0698111-E7AB-4E2E-98A9-FF1EFBF63599',
 *							'sp_guid' =>'27245CC8-4636-4C81-B044-363D6CB524F5',
 *							'zhspsl' => 2,
 *							'zhsl2' => 0,
 * 							'dp_guid' =>'7F07F517-C4DA-4E99-8C86-10B1EE2176C1'
 *                 		)
 *                 )
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_MergerItemStockGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['zhsps'])){
			if(isset($this->result['zhsps']['zhsp']['guid'])){
				$this->result['zhsps']['zhsp'] = array($this->result['zhsps']['zhsp']);
			}
		}
	}
}
Top_ApiManager::add(
    'MergerItemStockGet',
    array(
        'method' => 'ecerp.zhsp.get',
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
                'id','guid','dp_guid','zhsl2'
            )
        )
    )
);