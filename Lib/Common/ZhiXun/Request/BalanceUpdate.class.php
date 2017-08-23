<?php
/**
 * 修改会员结余款
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_BalanceUpdate extends Top_Request
{
}
/**
 * 修改会员结余款
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'djbh' => 'JYKTZD00000518'
 * )
 * </code>
 */
class Top_Response_BalanceUpdate extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['huiyuan_addbalance_response'])){
			$this->result = $this->result['huiyuan_addbalance_response'];
		}
	}
}
Top_ApiManager::add(
    'BalanceUpdate',
    array(
        'method' => 'ecerp.huiyuan.modify_balance',
        'parameters' => array(
			'required' => array(
                'mail','id','shopcode','money'
            ),
            'other' => array(
            	'status','type_code','tradeno','memo','bank'
            )
        )
    )
);