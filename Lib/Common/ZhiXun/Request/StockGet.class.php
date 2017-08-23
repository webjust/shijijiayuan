<?php
/**
 * 查询商品库存
 *
 * API 参数
 *  - fields: 需要返回的商品库存对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_StockGet extends Top_Request
{
}
/**
 * 查询商品库存
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'stocks' => array(
 *         'stock' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'spdm' => '520520',
 *                 'ty' => 0,
 *                 'spskus' => array(
 *                 		'spsku' => array(
 *                 			'skudm' => 'EWI0005BW02L',
 *                 			'sl' => 10
 *                 		)
 *                 )
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_StockGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['stocks'])){
			if(isset($this->result['stocks']['stock']['guid'])){
				$this->result['stocks']['stock'] = array($this->result['stocks']['stock']);
			}
			foreach($this->result['stocks']['stock'] as &$item_stock){
				if(isset($item_stock['skus']['sku']['guid'])){
					$item_stock['stocks']['stock'] = array($item_stock['stocks']['stock']);
				}
			}
			unset($item_stock);
		}
	}
}
Top_ApiManager::add(
    'StockGet',
    array(
        'method' => 'ecerp.stock.get',
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
                'guid','spdm','ty','sl3','sl2','sl1','sl'
            )
        )
    )
);