<?php

/**
 * 短信模板相关模型层 Model
 * @package Model
 * @version 7.6.1
 * @author wangguibin
 * @date 2014-08-04
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class SmsTemplatesModel extends GyfxModel {

    /**
     * 构造方法
     * @author wangguibin
     * @date 2014-08-04
     */
    public function __construct() {
        parent::__construct();
    }
	
    /**
	  * 短信模板内容
	  * @author wangguibin
	  * @param ary $data 发送短信模板信息
	  * @date 2014-08-04
	  */
    public function sendSmsTemplates($data){
		$message=array('content'=>'','code'=>'','status'=>false);
		$temp_res = $this->where(array('code' => $data['code']))->find();
		$code=randStr();
		$ary_config = D('SysConfig')->getCfgByModule('GY_SHOP');
		$shop_name=$ary_config['GY_SHOP_TITLE'];
		if(is_array($temp_res) && !empty($temp_res)){
			$url .= '/' . trim(U("Home/User/changeStatus?m_id=$m_id"),'/');
			$content=htmlspecialchars_decode($temp_res['content']);
			preg_match_all('/{.*}/Uis' ,$temp_res['content'],$matches); 
			$variable_replace = $matches[0];
			$replace_autor   = array($code,$shop_name);
			$sms_content=str_replace($variable_replace, $replace_autor, $content); 
			$sms_content=strip_tags($sms_content);
			return array('content'=>$sms_content,'code'=>$code,'status'=>true);
		}
		return $message;
    }
	
	/**
	  * 门店提货短信提示
	  * @author wangguibin
	  * @param ary $data 发送短信模板信息
	  * @date 2015-05-18
	  */	
	public function sendSmsGetCode($ary_order){
		$temp_res = $this->where(array('code' => 'GET_CODE'))->find();
		$ary_config = D('SysConfig')->getCfgByModule('GY_SHOP');
		//120秒内只能发一次
		$int_count = D('SmsLog')->where(array('code'=>$ary_order['o_id'],'mobile'=>$ary_order['mobile'],'sms_type'=>6,'create_time'=>array('egt',date('Y-m-d H:i:s',strtotime('now')-120))))->count();
		if($int_count>0){
			return 2;
		}
		$shop_name=$ary_config['GY_SHOP_TITLE'];
		if(is_array($temp_res) && !empty($temp_res)){
			$content=htmlspecialchars_decode($temp_res['content']);
			preg_match_all('/{.*}/Uis' ,$temp_res['content'],$matches); 
			$variable_replace = $matches[0];
			$replace_autor = array($ary_order['o_id'],$ary_order['o_receiver_name'],$shop_name);
			$sms_content=str_replace($variable_replace, $replace_autor, $content); 
			$sms_content=strip_tags($sms_content);
		}
		if(!empty($sms_content)){
			$SmsApi_obj=new SmsApi();
			$array_params=array('mobile'=>$ary_order['mobile'],'','content'=>$sms_content);
			$res=$SmsApi_obj->smsSend($array_params);
			if($res['code'] == '200'){
				//日志记录下
				$ary_data = array();
				$ary_data['sms_type'] = 6;
				$ary_data['mobile'] = $ary_order['mobile'];
				$ary_data['content'] = $sms_content;
				$ary_data['code'] = $ary_order['o_id'];
				$sms_res = D('SmsLog')->addSms($ary_data);
				if(!$sms_res){
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
		}
		
	}
}