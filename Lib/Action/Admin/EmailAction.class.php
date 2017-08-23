<?php

/**
 * 后台邮件控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class EmailAction extends AdminAction{

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');		
        $this->setTitle(' - 邮件设置');
    }

    /**
     * 默认控制器
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function index(){
        $this->redirect(U('Admin/Email/pageSmtp'));
    }

    /**
     * 后台邮件SMTP设置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function pageSmtp(){
        $this->getSubNav(2, 8, 10);
        $info = D('SysConfig')->getEmailCfg();
        $this->assign($info);
        $this->display();
    }

    /**
     * 保存SMTP配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function doSetSmtp(){
        $data = $this->_post();
        $SysSeting = D('SysConfig');
        if(
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_AUTH', $data['GY_SMTP_AUTH'], 'SMTP是否需要密码验证') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_FROM', $data['GY_SMTP_FROM'], '发件人地址') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_FROM_NAME', $data['GY_SMTP_FROM_NAME'], '发件人姓名') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_HOST', $data['GY_SMTP_HOST'], 'SMTP主机地址') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_NAME', $data['GY_SMTP_NAME'], 'SMTP账户名') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_PASS', $data['GY_SMTP_PASS'], 'SMTP密码') &&
            $SysSeting->setConfig('GY_SMTP', 'GY_SMTP_PORT', $data['GY_SMTP_PORT'], 'SMTP端口号')
        ){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"保存SMTP配置信息",'保存SMTP配置信息:'.$data['GY_SMTP_NAME']));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    /**
     * 发送测试邮件
     * @author zuo <zuojianghua@guanysoft.com>
     * @date 2013-01-16
     */
    public function doTestSmtp(){
        $data = $this->_get();
        $ary_option = array(
            'receiveMail' => $data['testEmail'],
            'subject' => '这是一封测试邮件',
            'message' => '这是一封测试邮件',
            'from' => $data['GY_SMTP_FROM'],
            'fromName' => $data['GY_SMTP_FROM_NAME'],
            'host' => $data['GY_SMTP_HOST'],
            'port' => $data['GY_SMTP_PORT'],
            'smtpAuth' => $data['GY_SMTP_AUTH'],
            'username' => $data['GY_SMTP_NAME'],
            'password' => $data['GY_SMTP_PASS'],
            'isHtml' => true
        );
        //发送测试邮件
        $email = new Mail();
        if($email->sendMail($ary_option)){
            $this->success('测试邮件发送成功，SMTP设置正确');
        }else{
            $this->error('测试邮件发送失败，SMTP设置有问题');
        }
    }

	/**
     * 后台邮件模板设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-07
     */
    public function pageTemp(){
        $this->getSubNav(2, 8, 20);
        $str_method = explode('::',__METHOD__);$this->display($str_method[1]);
    }
	
	/**
     * 后台获得邮件模板内容
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-07
     */
	public function getTemp() {
		$temp_id = $this->_post('temp_id');
		if (isset($temp_id)) {
			$res = D('EmailTemplates')->getTemp(array('id' => $temp_id));
			if ($res) {
				//dump($res['content']);die();
				$content=htmlspecialchars_decode($res['content']);
				$content = D('ViewGoods')->ReplaceItemDescPicDomain($content);
				$this->ajaxReturn(array('status' => '1', 'msg' => '模板成功！', 'info' => $content));
				exit;
			} else {
				$this->ajaxReturn(array('status' => '0', 'msg' => '模板加载失败！', 'info' => ''));
				exit;
			}
		}
	}
	
	/**
     * 后台邮件模板编辑
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-07
     */
	public function doEdit() {
		$temp_id = $this->_post('temp_id');
		if(!empty($temp_id)){
			$data['id']=$temp_id;
			$data['content']=trim($_POST['temp_content']);
			$data['content']=_ReplaceItemDescPicDomain($data['content']);
			$data['last_modify']=date('Y-m-d H:i:s');
			$res = D('EmailTemplates')->doEdit($data);
		}
		$this->success('保存成功');
	}
	
	
	/**
     * 已发送列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-06
     */
    public function pageList(){
        $this->getSubNav(2,8,30);
		$ary_request = $this->_request();
        $ary_where = array();
		$ary_where['status'] = 1;
		//手机号搜索
		$str_email = trim($this->_post('email'));
		if(isset($str_email) && ($str_email != '')){
			$ary_where['mobile'] = $str_email;
		}
		//认证状态
		$check_status = trim($this->_post('check_status'));
		if($check_status == ''){
			$ary_request['check_status'] = -1;
		}
		if($check_status != '' && $check_status!= '-1'){
			$ary_where['check_status'] = $check_status;
		}
		//验证码
		$code = trim($this->_post('code'));
		if(isset($code) && $code != ''){
			$ary_where['code'] = $code;
		}
		//发送类型
		$email_type = trim($this->_post('email_type'));
		if($email_type == ''){
			$ary_request['email_type'] = -1;
		}
		if($email_type != '-1' && $email_type != ''){
			$ary_where['email_type'] = $email_type;
		}	
		//时间搜索
		if (!empty($ary_request['start_time']) && isset($ary_request['start_time'])) {
			if (!empty($ary_request['end_time']) && $ary_request['end_time'] > $ary_request['start_time']) {
				$ary_request['end_time'] = trim($ary_request['end_time']) . " 23:59:59";
			} else {
				$ary_request['end_time'] = date("Y-m-d H:i:s");
			}
			$ary_where['update_time'] = array("between", array($ary_request['start_time'] . " 00:00:00", $ary_request['end_time']));
		}
        $count =  M('email_log',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_email =  M('email_log',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->limit($limit)->order('`update_time` DESC')->select();
		//echo M('sms_log',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		$this->assign("page", $page);
        $this->assign('ary_email',$ary_email);
		$this->assign('ary_request',$ary_request);
        $this->display();
    }
	
	
}