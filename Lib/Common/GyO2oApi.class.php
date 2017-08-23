<?php
/**
 * 商品推送线下系统API 接口
 * @package Common
 * @subpackage GyO2oApi
 * @author Zhangjiasuo 
 * @since 7.8.5
 * @version 1.0
 * @date 2015-07-10
 */
class GyO2oApi{
    public $str_api_url;        //api url
    public $str_api_key;        //APP_KEY
    public $str_api_secret;     //APP_SECRET
    
	/**
     * 
     * 构造函数
     */
    function __construct() {
        $this->str_api_url = C('API_URL');
		if(SAAS_ON == TRUE){//获取用户appkey
			$saas = new GyApi();
			$ary_param = array(
					'client_sn' => CI_SN
			);
			$ary_saas = $saas->getErpApiAppSecret($ary_param);
			$this->str_api_key    = $ary_saas['data']['app_key'];
			$this->str_api_secret = $ary_saas['data']['APP_SECRET'];
		}else{
			$this->str_api_key    = CI_SN;
			$this->str_api_secret = APP_SECRET;
		}
    }
    
    /**
     * 
     * 请求api的出口方法
     * @param string $str_method
     * @param string $ary_param
     */
    protected function requestApi($method, $ary_param=array()) {
		$file_name=date('Ymd').'pos_members.log';
		//array_push($ary_param,$this->str_api_key,$this->str_api_secret,$method);
		$ary_param['appkey'] = $this->str_api_key;
		$ary_param['sessionkey'] = $this->str_api_secret;
		$ary_param['method'] = $method;
		 //签名
		$sign = $this->createSign($ary_param,$this->str_api_secret);
		$ary_param['sign'] = $sign;
       
		$json_param = json_encode($ary_param);
        
		//writeLog($json_param,$file_name);
        $result = makeRequestJson($this->str_api_url, $json_param, 'post');
		
		$array_result = json_decode($result, true);
        writeLog($method.'---'.json_encode($result),$file_name);
        return $array_result;
    }
	
	/**
	 *
	 * Enter 签名函数
	 * @param  $paramArr
	 */

    protected function createSign ($paramArr,$app_secret) {
		$sign = $app_secret;
		ksort($paramArr);
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val != '') {
				$sign .= $key.$val;
			}
		}

		$sign.=$app_secret;
		//dump($sign);die();
		$sign = strtoupper(md5($sign));
		return $sign;
	}
	
	
	/**
     * 新增会员
     * @param   array $ary_param 需要的传递参数
     *          method          API请求的方法
     *          app_key         授权代码，详情可以点击 这里 查看；
     *          app_secret      授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          code       	    会员卡号
     *          name            会员名称
	 *          tel             手机号
	 *          sex             性别
	 *          code            模板的唯一SN号
	 *          phone           联系电话
	 *          birthday        生日
	 *          identity_card   身份证
	 *          qq            	QQ号
	 *          wangwang        旺旺号
	 *          status          状态
	 *          email           邮箱
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2015-07-10
     * @return array 模板数据
     */
	public function AddMember($ary_param){
		//会员基本信息
		$ary_params['data'][0]['refNo'] = $ary_param['m_id'];
		$ary_params['data'][0]['code'] = $ary_param['m_name'];
		$ary_params['data'][0]['shop_code'] = $ary_param['shop_code'];
		//$ary_params['data'][0]['code'] = $ary_param['shop_id'];
		//if($ary_param['m_real_name']!=''){
			$ary_params['data'][0]['name'] = $ary_param['m_name'];
		//}else{
		//	$ary_params['data'][0]['name'] = $ary_param['m_real_name'];
		//}
		$ary_params['data'][0]['tel']  = $ary_param['m_mobile'];
		$ary_params['data'][0]['sex']  = $ary_param['m_sex'];
		if(isset($ary_param['batch']) && $ary_param['batch']==true){
			$ary_params['data'][0]['pwd']  = $ary_param['m_password'];
		}else{
			$ary_params['data'][0]['pwd']  = md5($ary_param['m_password']);
		}
		$ary_params['data'][0]['phone']  = $ary_param['m_telphone'];
		$ary_params['data'][0]['birthday']  = $ary_param['m_birthday'];
		$ary_params['data'][0]['identity_card']  = $ary_param['m_id_card'];
		$ary_params['data'][0]['email']  = $ary_param['m_email'];
		$ary_params['data'][0]['qq']  = $ary_param['m_qq'];
		$ary_params['data'][0]['wangwang']  = $ary_param['m_wangwang'];
		$ary_params['data'][0]['status']  = $ary_param['m_status'];
		
		$ary_result = $this->requestApi('kj.pos.vip.add', $ary_params);
		
		if(!empty($ary_result)){
			//$file_name=date('Ymd').'test.log';
			//writeLog(json_encode($ary_result),$file_name);
			if($ary_result['success']==1){
				$where['m_id'] = $ary_result['refNo'];
				$up_data['shop_id'] = $ary_result['id'];
				$up_data['m_update_time'] = date('Y-m-d H:i:s');
				$ary_result = D('Members')->where($where)->save($up_data);
			}else{
				if($ary_result['refNo']!='' and $ary_result['id']!='' ){
					$where['m_id'] = $ary_result['refNo'];
					$up_data['shop_id'] = $ary_result['id'];
					$up_data['m_update_time'] = date('Y-m-d H:i:s');
					$ary_result = D('Members')->where($where)->save($up_data);
				}
			}
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
	}
	
	/**
     * 修改会员
     * @param   array $ary_param 需要的传递参数
     *          method          API请求的方法
     *          app_key         授权代码，详情可以点击 这里 查看；
     *          app_secret      授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          code       	    会员卡号
     *          name            会员名称
	 *          tel             手机号
	 *          sex             性别
	 *          code            模板的唯一SN号
	 *          phone           联系电话
	 *          birthday        生日
	 *          identity_card   身份证
	 *          qq            	QQ号
	 *          wangwang        旺旺号
	 *          status          状态
	 *          email           邮箱
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2015-07-10
     * @return array 模板数据
     */
	public function UpdateMember($ary_param){
		//会员基本信息
		$ary_params['data'][0]['refNo'] = $ary_param['m_id'];
		$ary_params['data'][0]['code'] = $ary_param['m_name'];
		$ary_params['data'][0]['shop_code'] = $ary_param['shop_code'];
		//$ary_params['data'][0]['code'] = $ary_param['shop_id'];
		//if($ary_param['m_real_name']!=''){
			$ary_params['data'][0]['name'] = $ary_param['m_name'];
		//}else{
		//	$ary_params['data'][0]['name'] = $ary_param['m_real_name'];
		//}
		$ary_params['data'][0]['tel']  = $ary_param['m_mobile'];
		$ary_params['data'][0]['sex']  = $ary_param['m_sex'];
		$ary_params['data'][0]['pwd']  = $ary_param['m_password'];
		$ary_params['data'][0]['phone']  = $ary_param['m_telphone'];
		$ary_params['data'][0]['birthday']  = $ary_param['m_birthday'];
		$ary_params['data'][0]['identity_card']  = $ary_param['m_id_card'];
		$ary_params['data'][0]['email']  = $ary_param['m_email'];
		$ary_params['data'][0]['qq']  = $ary_param['m_qq'];
		$ary_params['data'][0]['wangwang']  = $ary_param['m_wangwang'];
		$ary_params['data'][0]['status']  = $ary_param['m_status'];
		$ary_result = $this->requestApi('kj.pos.vip.update', $ary_params);
		$file_name=date('Ymd').'test.log';
		writeLog(json_encode($ary_result),$file_name);
		if(!empty($ary_result)){
            $ary_result = json_decode($ary_result,TRUE);
        }else{
            $ary_result['data'] = array();
            $ary_result['error_msg'] = "错误信息";
            $ary_result['status'] = false;
        }
        return $ary_result;
	}

	/**
     * 获得店铺信息
     * @param   array $ary_param 需要的传递参数
     *          method          API请求的方法
     *          app_key         授权代码，详情可以点击 这里 查看；
     *          app_secret      授权代码对应的加密串，32个字符，由app_key根据一定的逻辑规则产生
     *          cr_id           省市区编码
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2015-09-21
     * @return array 模板数据
     */
	public function GetShopInfo($ary_param){
		$ary_params['rowcount'] =10;//请求条数 默认2条
		$ary_params['province'] = $ary_param['province'];
		$ary_params['city'] = $ary_param['city'];
		$ary_params['district_'] = $ary_param['district_'];
		$ary_result = $this->requestApi('kj.pos.shop.query', $ary_params);
		return $ary_result;
	}    
}