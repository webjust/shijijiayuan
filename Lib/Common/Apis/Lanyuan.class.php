<?php
/**
 * 蓝源项目专用API接口
 * 需要开启以下两个php扩展
 * extension=mcrypt.so
 * extension=soap.so
 *
 * @package Common
 * @subpackage Api
 * @version 7.6。1
 * @author Wangguibin <Wangguibin@guanyisoft.com>
 * @date 2014-08-23
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
require_once("nusoap.php");
class Lanyuan {
	//签名KEY
    private $signkey = 'live800';
	//公司ID
	private $company_id = '8088';
    private $url_api = 'http://care3.live800.com/live800/services/ICollectOrder?wsdl';
    private $client = '';
	
    /**
     * 构造函数
     * @author Hcaijin 
     * @date 2014-08-23
     */
    public function __construct() {
		$this->client=new nusoap_client($this->url_api,'wsdl');
		$this->client->soap_defencoding = 'utf-8';
		$this->client->decode_utf8 = false;
		$this->client->xml_encoding = 'utf-8';
    } 
	
	/**
     * 发送 API请求
     * @author Wangguibin 
     * @date 2014-08-23
     */
    public function collectOrder($param=array()) {
		$timestamp = getTimestamp(13);
		$hashcode = strtoupper(md5(strtoupper(urlencode($this->company_id.$param['userId'].$param['orderId'].$param['totalPrice'].$timestamp.$this->signkey))));
		$aryPara = array('in0'=>$this->company_id,'in1'=>json_encode($param),'in2'=>$timestamp,'in3'=>$hashcode);
		// 调用远程函数
		$aryResult = $this->client->call('collectOrder',$aryPara);
		//echo "<pre>";print_r($aryResult);die();
        return $aryResult['out'];
    }

}

