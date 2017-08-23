<?php
/**
 * 九龙港项目 金蹀 API接口
 * 需要开启以下两个php扩展
 * extension=mcrypt.so
 * extension=soap.so
 *
 * @package Common
 * @subpackage Api
 * @version 7.6
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2014-07-28
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class Aeaicrypt {

    private $signkey = '0123456789abcdef';
    private $url_api = '';
    private $client = '';

	protected $member_map = array(
             'card_type'=>'card_type',//卡类型,可不传
			 'm_id'=>'id',//会员卡ID
             'm_card_name'=>'name',//会员卡名称,可不传
             'm_mobile'=>'phone',//手机
             'm_card_no'=>'card_num',//会员卡号,新增可不传，修改是必填
             'm_wangwang'=>'user_nick',//会员淘宝Nick
             //'m_card_bind_time'=>'bind_time',//绑定卡号时间 
             //'m_merchant_id'=>'merchant_id', //发卡商户ID，默认1
             'm_points'=>'points',//九龙金豆，可不传
             //'m_card_status'=>'status',//卡状态，默认1,有效
             'm_id_card'=>'id_card',//身份证号
             'm_real_name'=>'user_name',//用户真实姓名
             'm_sex'=>'gender', //性别，m表示男，f表示女，空表示保密
             //'m_card_apply_time'=>'apply_time',//申领时间 
             'm_email'=>'email',//邮箱地址
             'm_birthday'=>'birthday',//生日
             'm_address_detail'=>'address'//地址
             );
    //同步sns用
	protected $snsmem_map = array(
             'm_name'=>'user',//会员卡名称,可不传
             'm_password'=>'password',//会员密码
             'm_mobile'=>'phone',//手机
             'm_status'=>'effective',//会员淘宝Nick
             'm_sex'=>'sex', //性别，m表示男，f表示女，空表示保密
             'm_birthday'=>'birthday',//生日
             'm_head_url'=>'headurl'//头像url
             );
    /**
     * 构造函数
     * @author Hcaijin 
     * @date 2014-07-28
     * $type 空，同步线下长益会员卡号数据;  1,B2C->SNS会员同步
     */
    public function __construct($type) {
        //外网IP: 61.161.160.234
        //内网IP: 172.16.10.12   测试用
        $this->url_api = 'http://61.161.160.234:9090/ESB4jlgOffLine/services/MemBaseInfo?wsdl';
        if($type == 1){
            $this->url_api = 'http://61.161.160.234:9090/ESB4jlgOffLineExt/services/SnsAndB2c?wsdl';
        }
        ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache  
        $this->client = new SoapClient($this->url_api);
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
        $time = date('YmdHis');
        $array_params['signature']=$this->build_signature($str_method,$time,$this->signkey);
	//$array_params = array_merge($ary_parameter,$data);
        /**echo '<pre>';
        print_r($this->client->__getFunctions());//列出当前SOAP所有的方法，参数和数据类型，也可以说是获得web service的接口
        print_r($this->client->__getTypes());//列出每个web serice接口对应的结构体
        dump($array_params);exit();
        **/
        $res_info = $this->client->$str_method($array_params);
        //上面的调用方式也可以写做 $this->client->__Call($str_method, $array_params );
        return $res_info;
    }

    /**
     * 新增会员接口
     * @author Hcaijin 
     * @date 2014-07-29
     */
    public function addMemApi($ary_data = array()){
        $datas = array();
        $data = $this->parseFields($this->member_map,$ary_data);
        $data['merchant_id'] = 1;
        $data['status'] = 1;
        $data['bind_time'] = date('Y-m-d H:i:s');
        $data['apply_time'] = date('Y-m-d H:i:s');
        $array_params['member']=$data;
        $res_info = $this->requestApi('addMem',$data);
        if(!$res_info->return->state){
            $result = array('status'=>0,'msg'=>$res_info->return->message);
        }else{
            $datas = $res_info->return->cardRef;
            $result = array('status'=>1,'data'=>$datas);
        }
        return $result;
    }

    /**
     * 批量新增会员接口
     * @author Hcaijin 
     * @date 2014-07-29
     */
    public function updateMemApi($ary_data = array()){
        $datas = array();
        $data = $this->parseFields($this->member_map,$ary_data);
        $data['merchant_id'] = 1;
        $data['status'] = 1;
        $data['bind_time'] = date('Y-m-d H:i:s');
        $data['apply_time'] = date('Y-m-d H:i:s');
        $array_params['member']=$data;
        $res_info = $this->requestApi('updateMem',$array_params);
        if(!$res_info->return->state){
            $result = array('status'=>0,'msg'=>$res_info->return->message);
        }else{
            $datas = $res_info->return->cardRef;
            $result = array('status'=>1,'data'=>$datas);
        }
        return $result;
    }

    /**
     * 批量新增会员接口
     * @author Hcaijin 
     * @date 2014-07-29
     */
    public function batchMemApi($ary_data = array()){
        $data = array();
        $datas = array();
        foreach($ary_data as $val){
            $tmp_data = $this->parseFields($this->member_map,$val);
            if(!empty($tmp_data)){
                $data[] = $tmp_data;
            }
        }
        foreach($data as &$str_data){
            $str_data['merchant_id'] = 1;
            $str_data['status'] = 1;
            $str_data['bind_time'] = date('Y-m-d H:i:s');
            $str_data['apply_time'] = date('Y-m-d H:i:s');
        }
        $array_params['members']=$data;
        $res_info = $this->requestApi('batchAdd',$array_params,1);
        if(!$res_info->return->state){
            $result = array('status'=>0,'msg'=>$res_info->return->message);
        }else{
            $datas = $res_info->return->cardRef;
            if(count($datas) == 1){
                $datas = array($datas);
            }
            $result = array('status'=>1,'data'=>$datas);
        }
        return $result;
    }

    /**
     * 调用同步会员到SNS接口
     * @author Hcaijin 
     * @date 2014-10-11
     */
    public function syncMemToSns($ary_data = array()){
        $data = array();
        $datas = array();
        foreach($ary_data as $val){
            $tmp_data = $this->parseFields($this->snsmem_map,$val);
            if(!empty($tmp_data)){
                $data[] = $tmp_data;
            }
        }
        $array_params['members']=$data;
        $res_info = $this->requestApi('syncMemToSns',$array_params,1);
        if(!$res_info->return->state){
            $result = array('status'=>0,'msg'=>$res_info->return->errorMsg);
        }else{
            $datas['add'] = $res_info->return->add;
            $datas['save'] = $res_info->return->save;
            $datas['logid'] = $res_info->return->logid;
            $datas['sucdata'] = $res_info->return->sucdata;
            $result = array('status'=>1,'data'=>$datas);
        }
        return $result;
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
            if(isset($array_table_fields[$field_name]) && $as_name !== NULL){
                $aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);
            }
        }
        if(empty($aray_fetch_field)){
            return null;
        }
        return $aray_fetch_field;
     }

}

