<?php
/**
 * 得到结余款类型
 *
 * API 参数
 *  - fields: 需要返回的会员结余款对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author Zhangjiasuo
 * @data 2013-04-15 
 * @version 1.0
 */
class Top_Request_BalanceTypeGet extends Top_Request
{
    
}
/**
 * 结余款类型
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'jyktzlxs' => array(
 *         'jyktzlx' => array(
 *             array(
 *                 'id'=> 3,
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'lxdm' => '002',
 *                 'lxmc' => '帐户充值',
 *                 'xtmr' => ' 1',
 *                 'bz' => '系统默认：只能修改不能删除',
 *                 'is_ty' => 0
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_BalanceTypeGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['jyktzlxs'])){
			if(isset($this->result['jyktzlxs']['jyktzlx']['guid'])){
				$this->result['jyktzlxs']['jyktzlx'] = array($this->result['jyktzlxs']['jyktzlx']);
			}
		}
	}
}
Top_ApiManager::add(
    'BalanceTypeGet',
    array(
        'method' => 'ecerp.jyktzlx.get',
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