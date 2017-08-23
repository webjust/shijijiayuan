<?php
/**
 * 修改会员
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_MemberUpdate extends Top_Request
{
}
/**
 * 修改会员
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'hy_guid' => '287967484734'
 * )
 * </code>
 */
class Top_Response_MemberUpdate extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['huiyuan_adduser_response']['huiyuan'])){
			$this->result = $this->result['huiyuan_adduser_response']['huiyuan'];
		}
	}
}
Top_ApiManager::add(
    'MemberUpdate',
    array(
        'method' => 'ecerp.huiyuan.modify_user',
        'parameters' => array(
			'required' => array(
                'mail','shopcode'
            ),
            'other' => array(
            	'dzyx','receiver_zip','member_hyly','receiver_phone','receiver_mobile','receiver_name',
            	'receiver_address','receiver_state','receiver_city','receiver_district','birthday','sex',
            	'u_name','QQ','WW','reference','is_proxy','alipay','identity','shopsite','shopaddress',
            	'proxyshopname','sqbz','hyjb','hybz'
            )
        )
    )
);