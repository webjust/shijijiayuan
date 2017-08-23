<?php
/**
 * 得到会员结余款明细信息
 *
 * API 参数
 *  - fields: 需要返回的会员结余款对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_BalanceDetailGet extends Top_Request
{
}
/**
 * 搜索会员结余款明细
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'vjyktzdmxs' => array(
 *         'vjyktzdmx' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'lydh' => '100',
 *                 'zdr' => '系统自动',
 *                 'zdrq' => '2012-12-05T16:48:23.203',
 *                 'shr' => '系统自动',
 *                 'shrq' => '2012-12-05T16:48:23.203',
 *                 'lxmc' => '历史转结',
 *                 'hy_guid' => '01432110-4CBB-4E8E-991B-5CD0FDDDDFC4',
 *                 'hydm' => '544272356@qq.com',
 *                 'hymc' => '小米',
 *                 'tzje' => '18.0000',
 *                 'sh' => '1',
 *                 'zf' => '0'
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_BalanceDetailGet extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['vjyktzdmxs'])){
			if(isset($this->result['vjyktzdmxs']['vjyktzdmx']['guid'])){
				$this->result['vjyktzdmxs']['vjyktzdmx'] = array($this->result['vjyktzdmxs']['vjyktzdmx']);
			}
		}
	}
}
Top_ApiManager::add(
    'BalanceDetailGet',
    array(
        'method' => 'ecerp.vjyktzdmx.get',
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