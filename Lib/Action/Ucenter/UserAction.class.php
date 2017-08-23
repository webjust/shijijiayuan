<?php

/**
 * 会员登录注册Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author Joe
 * @date 2012-11-30
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class UserAction extends GyfxAction {

    /**
     * 控制器初始化，获取基本信息
     *
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     */
    public function _initialize() {
        $is_close = D('SysConfig')->getCfgByModule('GY_WEB_CONFIG');
        if($is_close['STATUS'] == '1'){
        	header("Content-Type:text/html;charset=utf-8");
            echo $is_close['CONTENT'];
            exit;
        }
        $ary_data['common_title'] = 'EC-FX会员中心';
        $ary_data['common_keywords'] = '管易分销软件,';
        $ary_data['common_desc'] = '管易分销软件。';

        $this->assign($ary_data);
    }
	
	public function index(){
		 $this->redirect(U('Ucenter/User/pageLogin'));
	}

    /**
     * 会员登录页面
     *
     * @version 1.0
     * @author Joe
     * @date 2012-12-10
     */
    public function pageLogin() {
        //用户登录页面title
		$ary_data['page_title'] = ' - 用户登录';
        
		//验证用户是否已经登录，如果已经登录，则进入会员中心首页
		$member_info = session("Members");
        if(isset($member_info['m_id']) && is_numeric($member_info['m_id']) && $member_info['m_id'] > 0){
            $this->redirect(U('Ucenter/Index/index'));
			exit;
        }
		
		//用户登录成功以后跳转到的页面，默认是会员中心首页，如果指定，则到默认页面
		$int_port = "";
		if($_SERVER["SERVER_PORT"] != 80){
			$int_port = ':' . $_SERVER["SERVER_PORT"];
		}
		$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . U("Ucenter/Index/index");
		
		//如果指定了登录成功以后要跳转到的页面地址，则接收并解析输出到表单
		if(isset($_GET["redirect_uri"]) && "" != $_GET["redirect_uri"]){
			$string_request_uri = $_GET["redirect_uri"];
		}
		
		//变量传递到模板上，并渲染输出
		$this->assign("redirect_url",$string_request_uri);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 会员注册页
     *
     * @version 1.0
     * @author Joe
     * @date 2012-11-30
     */
    public function pageRegister() {
        $ary_data['page_title'] = ' - 用户注册';
        $data = D('SysConfig')->getCfgByModule('GY_REGISTER_CONFIG');
        $ary_data['content'] = '';
        if (!empty($data) && is_array($data)) {
            $ary_data['content'] = $data['REGISTER'];
        }
        //echo "<pre>";print_r($ary_data);exit;
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 验证码
     */
    public function verify() {
        import('ORG.Util.Image');
        Image::buildImageVerify();
    }

    /**
     * 处理用户登录
     * @author Joe
     * @date 2012-12-10
     */
    public function doLogin() {
        $member = D('Members');
        $ary_result = $member->doLogin($this->_post('m_name'), $this->_post('m_password'), $this->_post('verify'));
        if ($ary_result['status']) {
            $ary_member = $member->getInfo($this->_post('m_name'));
            
            //将会员信息存入session
            session('Members', $ary_member);
			/**
			把用户信息存在memcache里面去start
			**/
			$SESSION_TYPE = (ini_get('session.save_handler') == 'redis')?1:0;
			if(empty($SESSION_TYPE)){
				$uniqid = md5(uniqid(microtime()));
				writeMemberCache($uniqid,$ary_member);
				cookie('session_mid',$uniqid,3600);			
			}
			/**
			把用户信息存在memcache里面去end
			**/
            //会员登录清空session,并把数据加入购物车
            if(!empty($ary_member['m_id'])){
	            $session_carts = session("Cart");
	            $home_carts = D('Cart')->ReadMycart();
	            $ary_db_carts = array_merge($home_carts,$session_carts);
				if(!empty($ary_db_carts)){
	            $carts = array();
		            foreach($ary_db_carts as $key=>$val){
		            	if($val['type'] == '4'){
		            		$carts[$key] = $val;
		            	}else{
			            	$carts[$val['pdt_id']] = array(
			            		'pdt_id'=>$val['pdt_id'],
			            		'num'=>$val['num'],
			            		'type'=>$val['type']
			            	);		            		
		            	}
		            }
		            $Cart = D('Cart')->WriteMycart($carts);
		            session('Cart', NULL);					
				}
	        }
        }
        $this->ajaxReturn(array('result' => $ary_result['status'], 'msg' => $ary_result['msg']));
    }

    /**
     * 处理用户退出，退出后进入登录页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    public function doLogout() {
        session('Members', null);
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } else {
            $url = U('Ucenter/User/pageLogin');
        }
        //$this->success('用户退出成功', $url);
		$this->redirect($url);
    }

    /**
     * 处理用户注册
     *
     * @author Joe
     * @date 2012-12-11
     */
    public function doRegister() {
        //拼接数组
        $ary_member = array(
            'm_name' => $this->_post('m_name'),
            'm_password' => $this->_post('m_password'),
            'm_real_name' => $this->_post('m_real_name'),
            'm_mobile' => $this->_post('m_mobile'),
            'm_email' => $this->_post('m_email'),
            'm_wangwang' => $this->_post('m_wangwang'),
            'm_qq' => $this->_post('m_qq'),
            'm_website_url' => $this->_post('m_website_url'),
            'm_create_time' => date('Y-m-d H:i:s'),
            'm_recommended' => $this->_post('m_recommended'),
        	'm_status'=>1,
        	'm_alipay_name'	=>  $this->_post('m_alipay_name'));
        $data = D('SysConfig')->getCfgByModule('MEMBER_SET');
        if(!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1'){
            $ary_member['m_verify'] = '2';
        }
        //ml_id 默认会员等级
        $ml_id = D('MembersLevel')->getSelectedLevel();
        if(!empty($ml_id)){
        	$ary_member['ml_id'] =$ml_id;
        }
        //dump($ary_member);die();
        $member = D('Members');
        $ary_result = $member->doRegister($ary_member, $this->_post('verify'));
        if($ary_result['status'] == '1'){
			//注册成功之后 如果有推荐人，成为子分销商	
			$ary_member['m_id'] = $ary_result['data']['m_id'];
			D('Members')->addRecommended($ary_member);
            $this->success("恭喜您注册成功，5秒后跳转至登陆页","/Ucenter/User/pageLogin",5);
        }else{
            $this->error($ary_result['msg']);
        }
        
        //$this->ajaxReturn($ary_result);
    }

    /**
     * 验证验证码是否正确
     */
    public function checkVerify() {
        if ($_COOKIE['verify'] != md5($this->_get('verify'))) {
            $this->ajaxReturn('验证码有误！');
        } else {
            $this->ajaxReturn(true);
        }
    }

    /**
     * 验证用户是否唯一
     */
    public function checkName() {
        if (D('Members')->checkName($this->_get('m_name'))) {
            $this->ajaxReturn('该用户已存在！');
        } else {
            $this->ajaxReturn(true);
        }
    }

    /**
     * 验证邮箱是否唯一
     */
    public function checkEmail() {
        if (D('Members')->checkEmail($this->_get('m_email'))) {
            $this->ajaxReturn('该邮箱已被注册，请重新输入！');
        } else {
            $this->ajaxReturn(true);
        }
    }

    #### 忘记密码 ##############################################################
    /**
     * 忘记密码提交页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     */

    public function pageFoget() {
        $ary_data['page_title'] = ' - 找回密码';
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 向用户邮箱发送重置邮件
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     */
    public function synReset() {
        $member = D('Members');
        //接收页面数据
        //判断数据是否有效
        $result = $member->checkNameEmail($this->_post('username'), $this->_post('email'));
        if(empty($result)){
        	 $this->error('用户名或邮箱不正确!');
        }
        if (false === $result) {
            $this->error('用户名或邮箱不正确!');
        }

        if (session('verify') != md5($this->_post('verify'))) {
            $this->error('验证码出错!');
        }

        //生成邮件链接
        $code = authcode($result['m_password'], 'ENCODE', NULL, 600);
        $url = U('Ucenter/User/doReset', array('name' => $this->_post('username'), 'code' => base64_encode($code)), '', false, true);

        $ary_email_cfg = D('SysConfig')->getEmailCfg();

        $ary_option = array(
            'receiveMail' => $this->_post('email'),
            'subject' => '密码重置邮件',
            'message' => "请点击以下链接完成您的密码重置操作。如果不能直接点击，请将链接地址复制到浏览器访问。本链接有效期为10分钟，并且只能使用1次。<br><a href='$url'>$url</a>",
            'from' => $ary_email_cfg['GY_SMTP_FROM'],
            'fromName' => $ary_email_cfg['GY_SMTP_FROM_NAME'],
            'host' => $ary_email_cfg['GY_SMTP_HOST'],
            'port' => $ary_email_cfg['GY_SMTP_PORT'],
            'smtpAuth' => $ary_email_cfg['GY_SMTP_AUTH'],
            'username' => $ary_email_cfg['GY_SMTP_NAME'],
            'password' => $ary_email_cfg['GY_SMTP_PASS'],
            'isHtml' => true
        );

        //发送邮件
        $email = new Mail();
        if ($email->sendMail($ary_option)) {
            $this->success('重置密码邮件已经发送到您的注册邮箱', U('Ucenter/User/pageLogin'));
        } else {
            $this->error('重置密码邮件发送失败，请管理员检查邮件发送设置');
        }
    }

    /**
     * 执行重置密码命令
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     */
    public function doReset() {
        $member = D('Members');
        //解密连接代码
        $code = authcode(base64_decode($this->_get('code')), 'DECODE');
        //验证
        $result = $member->checkNamePassword($this->_get('name'), $code);
        if (FALSE == $code || FALSE == $result) {
            $this->error('密码已重置成功，请查看邮件或非法链接或者链接已经失效');
        }
        //生成新密码并重置
        $new_password = randStr();
        $save_result = $member->where(array('m_name' => $this->_get('name'), 'm_password' => $code))->data(array('m_password' => md5($new_password)))->save();
        if (false == $save_result) {
            $this->error('密码重置失败');
        }
        //生成邮件
        $ary_email_cfg = D('SysConfig')->getEmailCfg();

        $ary_option = array(
            'receiveMail' => $result['m_email'],
            'subject' => '您重置的新密码',
            'message' => "您的新密码是:$new_password",
            'from' => $ary_email_cfg['GY_SMTP_FROM'],
            'fromName' => $ary_email_cfg['GY_SMTP_FROM_NAME'],
            'host' => $ary_email_cfg['GY_SMTP_HOST'],
            'port' => $ary_email_cfg['GY_SMTP_PORT'],
            'smtpAuth' => $ary_email_cfg['GY_SMTP_AUTH'],
            'username' => $ary_email_cfg['GY_SMTP_NAME'],
            'password' => $ary_email_cfg['GY_SMTP_PASS'],
            'isHtml' => true
        );
        //发送邮件
        $email = new Mail();
        if ($email->sendMail($ary_option)) {
            $this->success('密码重置成功，您的新密码已经发送到邮箱，请尽快使用新密码登录或修改您的密码', U('Ucenter/User/pageLogin'));
        } else {
            $this->error('重置密码邮件发送失败，请管理员检查邮件发送设置');
        }
    }

    ### 测试语音 ##############################################################
    /**
     * 测试发音
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-24
     * @todo 目前仅支持UTF-8中文
     */

    public function say() {
        layout(false);
        $from = $this->_get('from', 'htmlspecialchars', 'zh');
        $to = $this->_get('to', 'htmlspecialchars', 'zh');
        $words = $this->_get('words');

        if ($from != $to) {
            $result = makeRequest('http://openapi.baidu.com/public/2.0/bmt/translate', array(
                'from' => $from,
                'to' => $to,
                'q' => $words,
                'client_id' => 'l15L33whEODVSRSF947scA48'
                    )
            );
            $ary_result = json_decode($result, true);
            $words = $ary_result['trans_result'][0]['dst'];
        }

        $words = urlencode(mb_substr($words, 0, 100));

        $file = md5($words);
        $file = __ROOT__ . 'Public/tts/' . $to . '/' . $file . '.mp3';
        if (!file_exists($file)) {
            $lang = ($to == 'jp') ? 'ja' : $to;
            $mp3 = file_get_contents('http://translate.google.cn/translate_tts?tl=' . $lang . '&ie=UTF-8&q=' . $words);
            file_put_contents($file, $mp3);
        }
        $this->ajaxReturn(U('/', '', true, false, true) . $file);
    }
    

}
