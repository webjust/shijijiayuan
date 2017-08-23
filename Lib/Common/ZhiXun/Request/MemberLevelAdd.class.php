<?php
/**
 * 添加会员等级
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author Terry
 * @license 
 * @version 1.0
 */
class Top_Request_MemberLevelAdd extends Top_Request
{
}
/**
 * 修改会员
 * 返回值示例：
 * <code>
 * array(
 *	'created' => '2012-8-13 10:44:47',
 *	'tid' => '001'
 * )
 * </code>
 */
class Top_Response_MemberLevelAdd extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['hyjb_response']['hyjb'])){
			$this->result = $this->result['hyjb_response']['hyjb'];
		}
	}
}
Top_ApiManager::add(
    'MemberLevelAdd',
    array(
        'method' => 'ecerp.member.addlevel',
        'parameters' => array(
			'required' => array(
                'levelcode','levelname'
            ),
            'other' => array(
            	'discount','accountperiod','discontinue','standard','autoupgrade','memos'
            )
        )
    )
);