<?php
/**
 * 九龙港项目 长益 API接口
 * 需要开启以下两个php扩展
 * extension=mcrypt.so
 * extension=soap.so
 *
 * @package Common
 * @subpackage Api
 * @version 7.6
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2014-09-02
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CashCard {

    private $auth_header = array('UserId'=>'test','Password'=>'test');
    private $url_api = 'http://172.16.10.11:8080/poswebservice.asmx?wsdl';
    private $client = '';

	protected $cards_map = array(
             'cardsNo'=>'condValue',//长益卡号值
			 'cardsPwd'=>'password',//长益卡号密码
			 'cardsType'=>'condType',//长益卡号类型，固定值
			 'cardsStore'=>'storeCode'//长益门店代码，固定值
             );
    /**
     * 构造函数
     * @author Hcaijin 
     * @date 2014-07-28
     */
    public function __construct() {
        ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache  
        $this->client = new SoapClient($this->url_api);
        /**
         *SoapHeader参数说明如下所示:
         *'http://tempuri.org/'   namespace(命名空间可省略) 对应wsdl 文件定义中的targetNamespace
         *'Crmsoapheader'          SoapHeader头的类名
         *'array(...)'            存放标识身份的字符串参数
         *'true'                  是否必须处理该header
        */
        $u = new SoapHeader('http://tempuri.org/','CrmSoapHeader',$this->auth_header,true);

        //添加soapheader
        $this->client->__setSoapHeaders($u);
        $this->client->soap_defencoding = 'utf-8';
        $this->client->xml_encoding = 'utf-8';   

    }

    /**
     * 发送 API请求
     * @author Hcaijin 
     * @date 2014-07-29
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     */
    public function requestApi($str_method, $array_params = array()) {
        /* echo '<pre>';
        print_r($this->client->__getFunctions());//列出当前SOAP所有的方法，参数和数据类型，也可以说是获得web service的接口
        print_r($this->client->__getTypes());//列出每个web serice接口对应的结构体
         */
    }

    /**
     * 查询储值卡接口
     * @author Hcaijin 
     * @date 2014-09-02
     */
    public function findCardApi($ary_data = array()){
        $data = $this->parseFields($this->cards_map,$ary_data);
        $res_info = $this->client->GetCashCard($data);
        if($res_info->GetCashCardResult){
            $data = $res_info->cashCard;
            $resData = array(
                'cardId'=>$data->CardId,
                'cardCode'=>$data->CardCode,
                'cardType'=>$data->CardTypeId,
                'cardMoney'=>$data->Balance
            );
            $result = array('status'=>1,'data'=>$resData);
        }else{
            $result = array('status'=>0,'msg'=>$res_info->msg);
        }
        return $result;
    }

    /**
     * 批量新增会员接口
     * @author Hcaijin 
     * @date 2014-07-29
     */
    public function updateMemApi($ary_data = array()){
        $data = $this->parseFields($this->cards_map,$ary_data);
        return $this->requestApi('updateMem',$data);
    }

    /**
     * 批量新增会员接口
     * @author Hcaijin 
     * @date 2014-07-29
     */
    public function batchMemApi($ary_data = array()){
		$data = array();
		foreach($ary_data as $val){
			$tmp_data = $this->parseFields($this->cards_map,$val);
			if(!empty($tmp_data)){
				$data[] = $tmp_data;
			}
		}
        return $this->requestApi('batchAdd',$data,1);
    }

    private static function encrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB); 
        $input = Aeaicrypt::pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }
    
    private static function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    private function build_signature($mf_opera_name,$current_time, $key) {
        $input = $mf_opera_name.$current_time;
        $data = Aeaicrypt::encrypt($input,$key);
        return $data;
    }

     /**
      * 处理字段映射
      * return array
      */
     private function parseFields($array_table_fields,$array_client_fields){
        $aray_fetch_field = array();
        foreach($array_client_fields as $field_name => $as_name){
            if(isset($array_table_fields[$field_name]) && !empty($as_name)){
                $aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);

            }
        }
        if(empty($aray_fetch_field)){
            return null;
        }
        return $aray_fetch_field;
     }

}

