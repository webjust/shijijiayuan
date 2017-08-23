<?php

/**
 * 邮件模板相关模型层 Model
 * @package Model
 * @version 7.5
 * @author zhangjiasuo
 * @date 2013-12-06
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class EmailTemplatesModel extends GyfxModel {
     /**
     * 构造方法
     * @author zhangjiasuo
     * @date 2013-12-06
     */
    public function __construct() {
        parent::__construct();
    }
	
    /**
	  * 邮件模板发送
	  * @author zhangjiasuo
	  * @param ary $data 发送邮件模板信息
	  * @date 2013-12-06
	  */
    public function sendMail($data){
		$temp_res = $this->where(array('code' => $data['code']))->find();
		if(is_array($temp_res) && !empty($temp_res)){
			$email = new Mail();
			$url = "http://";
			$url .= $_SERVER["HTTP_HOST"];
			if(80 != $_SERVER["SERVER_PORT"]){
				$url .= ':' . $_SERVER["SERVER_PORT"];
			}
			$m_id=$data['m_id'];
			$url .= '/' . trim(U("Home/User/changeStatus?m_id=$m_id"),'/');
			$content=htmlspecialchars_decode($temp_res['content']);
			$content = D('ViewGoods')->ReplaceItemDescPicDomain($content);
			preg_match_all('/{.*}/Uis' ,$temp_res['content'],$matches); 
			$variable_replace = $matches[0];
			$replace_autor   = array($data['receive'],$url,$data['password'],date('Y-m-d H:i:s'));
			$email_content=str_replace($variable_replace, $replace_autor, $content); 
			$email_config = D('SysConfig')->getEmailCfg();
			$email_data['host']=$email_config['GY_SMTP_HOST'];
			$email_data['port']=$email_config['GY_SMTP_PORT'];
			$email_data['from']=$email_config['GY_SMTP_FROM'];
			$email_data['fromName']=$email_config['GY_SMTP_FROM_NAME'];
			$email_data['username']=$email_config['GY_SMTP_NAME'];
			$email_data['password']=$email_config['GY_SMTP_PASS'];
			$email_data['isHtml']=true;
			$email_data['smtpAuth']=true;
			$email_data['subject']=$temp_res['subject'];
			$email_data['message']=$email_content;
			$email_data['receiveMail']=$data['receive'];
			//$email_data['receiveMail']='zhangjiasuo@guanyisoft.com';
			$email->sendMail($email_data);
			return true;
		}else{
			return false;
		}
    }
	
	/**
	  * 邮件模板内容编辑
	  * @author zhangjiasuo
	  * @param ary $data 发送邮件模板信息
	  * @date 2013-12-06
	  */
    public function doEdit($data){
		$temp_id=$data['id'];
		unset($data['id']);
		$res = $this->where(array('id' => $temp_id))->save($data);
    }
	
	/**
	  * 获取邮件模板
	  * @author zhangjiasuo
	  * @param ary $data 发送邮件模板信息
	  * @date 2013-12-06
	  */
    public function getTemp($data){
		$res = $this->where(array('id' => $data['id']))->find();
		return $res;
    }
    
     /**
	  * 发送邮件
	  * @author wangguibin
	  * @param ary $data 发送邮件模板信息
	  * @date 2014-03-16
	  */
    public function sendByTemplate($params,$content){
		$search = array_keys($params);
		$replace = array_values($params);
		$tmp_content = explode('==',str_replace($search, $params, $content));
		$content = $tmp_content[0];
		$content = D('ViewGoods')->ReplaceItemDescPicDomain($content);
		return $content;
	}
     /**
	  * 发送邮件
	  * @author wangguibin
	  * @param ary $data 发送邮件模板信息
	  * @date 2014-08-07
	  */	
	public function sendForgotPasswordEmail($m_password,$m_name,$user_email){
		//生成邮件链接
        $code = authcode($m_password, 'ENCODE', NULL, 600);
        $reset_url = U('Home/User/doReset', array('name' => $m_name, 'code' => base64_encode($code)), '', false, true);
		$ary_email_cfg = D('SysConfig')->getEmailCfg();
		$send_res = D('EmailTemplates')->getTemp(array('id' => 1));
		$shop_info = D('SysConfig')->getCfgByModule('GY_SHOP');
		$params = array(
			'{$user_name}' => $m_name,
			'{$reset_email}' => $reset_url,
			'{$shop_name}' => $shop_info['GY_SHOP_TITLE'],
			'{$send_date}' => date('Y-m-d H:i'),
		);
		$send_content = $this->sendByTemplate($params,$send_res['content']);
        $ary_option = array(
            'receiveMail' => $user_email,
            'subject' => $send_res['subject'],
            'message' => htmlspecialchars_decode($send_content),
            'from' => $ary_email_cfg['GY_SMTP_FROM'],
            'fromName' => $ary_email_cfg['GY_SMTP_FROM_NAME'],
            'host' => $ary_email_cfg['GY_SMTP_HOST'],
            'port' => $ary_email_cfg['GY_SMTP_PORT'],
            'smtpAuth' => $ary_email_cfg['GY_SMTP_AUTH'],
            'username' => $ary_email_cfg['GY_SMTP_NAME'],
            'password' => $ary_email_cfg['GY_SMTP_PASS'],
            'isHtml' => true
        );
		return $ary_option;
	}
	
	 /**
	  * 发送邮件
	  * @author wangguibin
	  * @param ary $data 发送邮件模板信息
	  * @date 2014-08-11
	  */	
	public function sendValidateEmail($m_password,$m_name,$user_email,$email_url = null){
		//生成邮件链接
        $code = authcode($m_password, 'ENCODE', NULL, 600);
        $back_url = 'Ucenter/My/doEmailValidate/';
        if(isset($email_url)) $back_url = $email_url;
        $reset_url = U($back_url, array('name' => $m_name, 'code' => base64_encode($code).'?'), '', false, true);
		$ary_email_cfg = D('SysConfig')->getEmailCfg();
		$send_res = D('EmailTemplates')->getTemp(array('id' => 2));
		$shop_info = D('SysConfig')->getCfgByModule('GY_SHOP');
		$params = array(
			'{$user_name}' => $m_name,
			'{$reset_email}' => $reset_url,
			'{$shop_name}' => $shop_info['GY_SHOP_TITLE'],
			'{$send_date}' => date('Y-m-d H:i'),
		);
		$send_content = $this->sendByTemplate($params,$send_res['content']);
        $ary_option = array(
            'receiveMail' => $user_email,
            'subject' => $send_res['subject'],
            'message' => htmlspecialchars_decode($send_content),
            'from' => $ary_email_cfg['GY_SMTP_FROM'],
            'fromName' => '邮箱验证',
            'host' => $ary_email_cfg['GY_SMTP_HOST'],
            'port' => $ary_email_cfg['GY_SMTP_PORT'],
            'smtpAuth' => $ary_email_cfg['GY_SMTP_AUTH'],
            'username' => $ary_email_cfg['GY_SMTP_NAME'],
            'password' => $ary_email_cfg['GY_SMTP_PASS'],
            'isHtml' => true
        );
		return $ary_option;
	}
	
}