<?php

/**
 * 会员登录注册Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-11
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class UserAction extends WapAction {
    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-11
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 会员登陆
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-28
     */
    public function login() {
    	$this->setTitle('会员登录');
        $config = D('SysConfig');
        $ary_request = $this->_request();
        if ($_SESSION['Members']['m_id']) {
            $this->redirect(U('Wap/Index/index'));
        }
        
		$tpl = $this->wap_theme_path . 'login.html';
        $logindata = $config->getConfigs("THDLOGIN",null,null,null,1);
		$_SESSION['rand'] = rand();
        $ary_status = json_decode($logindata['THDSTATUS']['sc_value'],TRUE);        
		$this->assign($ary_status);
        $jumpUrl = urldecode($ary_request['redirect_uri']);
		if(ltrim($jumpUrl,'Wap')==''){
			$ary_redirect = explode("redirect_uri",$_SERVER['REQUEST_URI']);
			if($ary_redirect[1]!=''){
				$string_redirect =$ary_redirect[1];
				$jumpUrl = $string_redirect;
			}
		}else{
			$jumpUrl = $_SERVER['HTTP_REFERER'];
		}
        $this->assign('jumpUrl',U($jumpUrl));
        $this->display($tpl);
    }
 
    /**
     * 会员注册条款
     * @author Terry<zhuwenwei@guanyisoft.com>
     * @date 2015-09-15
     */
    public function reg_term() {
    	$this->setTitle('注册条款');
		//获得注册协议
        $data = D('SysConfig')->getCfgByModule('GY_REGISTER_CONFIG');
        $ary_data['content'] = '';
        if (!empty($data) && is_array($data)) {
            $ary_data['content'] = $data['REGISTER'];
            $this->assign('content',$ary_data['content']);
        }
		$tpl = $this->wap_theme_path . 'reg_term.html';
        $this->display($tpl);
    }
 
	public function sunday($patt,$text){
   
    	$patt_size = strlen($patt);
    	$text_size = strlen($text);
    
    	for ($i = 0;$i < $patt_size; $i++){
    		$shift[$patt[$i]] = $patt_size - $i;
    	}
    	$i = 0;
    	$limit = $text_size - $patt_size;
    	while ($i <= $limit){
    		$match_size = 0;
    		while ($text[$i + $match_size] == $patt[$match_size]) {
    			$match_size++;
    			if ($match_size == $patt_size) {
    				return $i;
    			}
    		}
    		$shift_index = $i + $patt_size;
    		if ($shift_index < $text_size && isset($shift[$text[$shift_index]])) {
    			$i += $shift[$text[$shift_index]];
    		}else{
    			$i += $patt_size;
    		}
    	}
    } 
    /**
     * 会员注册页
     *
     * @version 7.1
     * @author wangguibin
     * @date 2012-04-11
     */
    public function pageRegister() {
        $this->setTitle('用户注册');
        $ary_request = $this->_request();
        //获得注册协议
        $data = D('SysConfig')->getCfgByModule('GY_REGISTER_CONFIG');
        $ary_data['content'] = '';
        if (!empty($data) && is_array($data)) {
            $ary_data['content'] = $data['REGISTER'];
            $this->assign('content',$ary_data['content']);
        }
        //获取推荐人设置
        if (isset($_GET['m_id'])) {
             if (is_numeric(base64_decode($_GET['m_id']))) {
                $m_id=base64_decode($_GET['m_id']);
             }
        }
        $resgister_field_extend = array();
        /* 取出会员扩展属性项字段 start*/
        $ary_extend_data = D('MembersFields')->displayFields($m_id,'register');
        if(!empty($ary_extend_data)) {
            foreach($ary_extend_data as $field_data) {
                //是否开启推荐注册
                if($field_data['fields_content'] == 'm_recommended') {
                    $resgister_field_extend[] = $field_data;
                }
            }
        }
        //dump($resgister_field_extend);die;
        $this->assign('resgister_field_extend', $resgister_field_extend);
        /* 取出会员扩展属性项字段 end*/
        if ($_SESSION['Members']['m_id']) {
            $this->redirect(U('Wap/Index/index'));
        }
        $this->assign($ary_data);
        
		$tpl = $this->wap_theme_path . 'register.html';
        $config = D('SysConfig');
        $logindata = $config->getConfigs("THDLOGIN",null,null,null,1);
        $ary_status = json_decode($logindata['THDSTATUS']['sc_value'],TRUE);
        $this->assign($ary_status);
        $jumpUrl = urldecode($ary_request['jumpUrl']);
        if(preg_match('/\/wap\/(login)|(pageRegister)/i', $jumpUrl)){
            $jumpUrl = urldecode(U('Wap/Index/index'));
        }
        $this->assign('jumpUrl',$jumpUrl);
        $this->display($tpl);
    }

    /**
     * 验证码
     */
    public function verify() {
        import('ORG.Util.Image');
        Image::buildImageVerify($length=4, $mode=1, $type='png', $width=60, $height=24);
    }

    /**
     * Home处理用户登录
     * @author wangguibin
     * @date 2013-04-11
     */
    public function doLogin() {
        $member = D('Members');
		$password = base64_decode($this->_post('m_password'));
        $rand = $_SESSION['rand'];
        $m_password = substr($password,0,$this->sunday(strval($rand),strval($password)));
        $m_password = !empty($m_password) ? $m_password : $this->_post('m_password');
        $ary_result = $member->doLoginApi($this->_post('m_name'), $m_password, $this->_post("verify"));
        if ($ary_result['status']) {
            $ary_member = $member->getInfo($this->_post('m_name'));
            //同步erp会员查询接口
            //将会员信息存入session
            session('Members', $ary_member);
            /**
            把用户信息存在memcache里面去start
             **/
            $uniqid = md5(uniqid(microtime()));
            writeMemberCache($uniqid,$ary_member);
            cookie('session_mid',$uniqid,3600);
            /**
            把用户信息存在memcache里面去end
             **/
            $pointCfg = D('PointConfig')->getConfigs();
           // echo $pointCfg['login_points'];die;
            if($pointCfg['is_consumed'] == '1' && $pointCfg['login_points'] > 0){
                //判断今天是否已登陆一次
                $ary_where = array();
                $ary_where['u_create'] = array(between,array(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')));
                $ary_where['type'] = 13;
                $ary_where['m_id'] = $ary_member['m_id'];
                $point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
                if(empty($point_exsit)){
                    $res_point = D('PointConfig')->setMemberRewardPoints($pointCfg['login_points'],$ary_member['m_id'],13);
                }
            }
            $ary_cart = array();
            if (!empty($ary_member['m_id'])) {
                $update_member['m_last_login_time']=date('Y-m-d H:i:s');
                D('Members')->where(array('m_id'=>$ary_member['m_id']))->save($update_member);
             	$session_carts = session("Cart");
               // echo "<pre>";print_r($session_carts);exit;
                $ary_db_carts = D('Cart')->ReadMycart();
                foreach ($session_carts as $str_pdt => $int_num) {
                	if($int_num['type'] == '4' || $int_num['type'] == '6'){
                		$ary_cart[$str_pdt] = $int_num;
                	}else{
                	    $tmp_int_num = (int) $int_num['num'];
	                    //大于0的新插入/更新. 小于等于0的不作处理
	                    if ($tmp_int_num > 0) {
	                        $ary_cart[$str_pdt] = $int_num;
	                    }                		
                	}
                }

                foreach ($ary_cart as $key => $int_num) {
                    $good_type = $int_num['type'];
                    if (!empty($ary_member['m_id'])) {//database
                    	if($good_type == '4' ||$good_type == '6'){
                    		$ary_db_carts[$key] = $int_num;
                    	}else{
                    	    if (array_key_exists($key, $ary_db_carts) && isset($ary_db_carts[$key]['type']) && ($good_type == $ary_db_carts[$key]['type'])) {
                                 if($session_carts[$key]['buy_now']==1){
                                     $ary_db_carts[$key]['num']=$int_num['num'];
                                 }else{
                                     $ary_db_carts[$key]['num']+=$int_num['num'];
                                 }
	                        } else {
	                            $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num['num'], 'type' => $good_type);
	                        }                 		
                    	} 
                    }
                }
                $Cart = D('Cart')->WriteMycart($ary_db_carts);
                session('Cart', NULL);
				//更新会员等级
				D('MembersLevel')->autoUpgrade($ary_member['m_id']);
		       	if (!empty($_POST['jumpUrl'])) {
		            $url = $_POST['jumpUrl'];
		        } else {
		            $url = U('Wap/Index/index');
		        }
                $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
                if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0'){
                    $url = U('Ucenter/Index/index');
                }
                $this->ajaxReturn(array('status' => $ary_result['status'], 'msg' => $ary_result['msg'],'url'=>$url));
            }
        } else {
            $this->ajaxReturn(array('status' => $ary_result['status'], 'msg' => $ary_result['msg'],'url'=>'/Wap/User/Login'));
        }
    }

    /**
     * 处理用户退出，退出后进入登录页
     * @author wangguibin
     * @date 2013-04-11
     */
    public function doLogout() {
        D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->save(array('login_type'=>0));
        session('Members', null);
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } else {
            $url = U('Wap/User/login');
        }
        $this->redirect($url);
    }

    /**
     * 处理用户注册
     *
     * @author wangguibin
     * @date 2013-04-11
     */
    public function doRegister() {
        //获取默认配置的会员等级
        $ml = D('MembersLevel')->getSelectedLevel();
        
        //拼接数组
        $ary_member = array(
            'm_name' => $this->_post('m_name'),
            'm_password' => $this->_post('m_password'),
            'm_password_c' => $this->_post('m_password_confirm'),
            'm_recommended' => $this->_post('m_recommended','',''),
            'm_email' => $this->_post('m_email'),
            'm_create_time' => date('Y-m-d H:i:s'),
            'm_status' => '1',
            'ml_id' =>  $ml,
        );

        if(!empty($ary_member['m_recommended'])){
            $reMid = D('Members')->where(array('m_name'=>$ary_member['m_recommended']))->getField('m_id');
            if(empty($reMid)){
                unset($ary_member['m_recommended']);
            }
        }

		//开启手机验证,验证验证码是否正确
		$mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
		if($mobile_set['VERIFIPHONE_STATUS']['sc_value'] == 1 && isset($_POST['m_mobile_code'])){
			$m_mobile_code = $this->_post('m_mobile_code');
			$m_mobile      = $this->_post('m_mobile');
			if(empty($m_mobile_code) || empty($m_mobile)){
				$this->error('已开启手机验证，请输入验证码');exit;
			}else{
				//判断手机号是否在90秒内已发送短信验证码
				$ary_sms_where = array();
				$ary_sms_where['check_status'] = 0;
				$ary_sms_where['status'] = 1;
				$ary_sms_where['sms_type'] = 1;
				$ary_sms_where['code'] = $m_mobile_code;
				//$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
				$sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);
				if($sms_log['code'] != $m_mobile_code){
					$this->error('验证码不存在或已过期');exit;
				}else{
					//更新验证码使用状态
					$up_res = D('SmsLog')->updateSms(array('id'=>$sms_log['id']),array('check_status'=>1));
					if(!$up_res){
						$this->error('注册失败,更新验证码状态失败');exit;
					}
					//设置其他已发送验证码无效
					D('SmsLog')->updateSms(array('sms_type'=>2,'check_status'=>0,'mobile'=>$ary_member['m_mobile']),array('check_status'=>2));
				}
			}
			$ary_member['m_mobile_code'] = $m_mobile_code;
			$ary_member['m_mobile'] = $m_mobile;
		}
        
        $data = D('SysConfig')->getCfgByModule('MEMBER_SET');
        if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
            $ary_member['m_verify'] = '2';
        }
        $member = D('Members');
        $ary_result = $member->doRegister($ary_member, $this->_post('verify'));
//        dump($ary_result);die;
        $_SESSION['last_member_id']= $ary_result['data']['m_id'];
        
        $ary_result['data']['m_name'] = $this->_post('m_name');
        
        //print_r($ary_result);exit;
        if ($ary_result['status'] == '1') {
            /*把新增加用户属性项信息插入数据库 start*/
            $pointCfg = D('PointConfig')->getConfigs();
            //echo'<pre>';print_r($pointCfg);die;
            $reMid = $ary_result['data']['m_id'];
            if($pointCfg['regist_points'] > 0 && $pointCfg['is_consumed'] == 1){
                $_POST['regist_points'] = $pointCfg['regist_points'];
                $int_extend_res = D('MembersFieldsInfo')->doAdd($_POST,$reMid);
            }else{
                $int_extend_res = D('MembersFieldsInfo')->doAdd($_POST,$reMid);
            }

            if(!$int_extend_res['result']){
                $this->error('您注册失败');
            }
            $ary_member_info = $member->getInfo($this->_post('m_name'));

            //注册成功判断是否开启积分
            //$pointCfg = D('PointConfig')->getConfigs();
            if($pointCfg['is_consumed'] == '1' ){
                if($pointCfg['regist_points'] > 0){
                    D('PointConfig')->setMemberRewardPoints($pointCfg['regist_points'],$ary_member_info['m_id'],2);
                }
                if($pointCfg['invites_points']>0 && !empty($ary_member_info['m_recommended'])){
                    if($reMid){
                        D('PointConfig')->setMemberRewardPoints($pointCfg['invites_points'],$reMid,14);
                    }
                }
            }

            //注册成功之后 如果有推荐人，成为子分销商
            $ary_member['m_id'] = $ary_result['data']['m_id'];
            if($reMid){
                D('Members')->addRecommended($ary_member);
            }
            //注册成功 送注册优惠券一张
            D('CouponActivities')->doRegisterCoupon($ary_member['m_id']);

            //dump($ary_member_info);die;
            //同步erp会员查询接口
            //将会员信息存入session
            session('Members', $ary_member_info);
            /*把新增加用户属性项信息插入数据库 end*/

			if(IS_AJAX){
                $this->ajaxReturn($ary_result);
            }else{
                if (!empty($_POST['jumpUrl'])) {
		            $url = $_POST['jumpUrl'];
		        } else {
		            $url = U('Wap/Index/index');
		        }
                $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
                if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0'){
                    $url = U('Ucenter/Index/index');
                }
                $this->success("恭喜您注册成功", $url, 3);
            }        
            
        } else {
            $this->error($ary_result['msg']);
        }
        //$this->ajaxReturn($ary_result);
    }

    /**
     * 验证验证码是否正确
     * @author wangguibin
     * @date 2013-04-11
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
	
	/**
     * 验证手机号是否唯一及格式验证
     */
    public function checkMobile() {
		//判读是不是手机格式
		$m_mobile = $this->_get('m_mobile');
		if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){
			$this->ajaxReturn('请输入正确的手机号格式！');
		}
        if (D('Members')->checkMobile($m_mobile)) {
            $this->ajaxReturn('该手机号已被注册，请重新输入！');
        } else {
            $this->ajaxReturn(true);
        }
    }
	
	/**
     * 验证手机验证码是否存在
	 * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-08-05
     */
    public function checkMobilePwdCode() {
		//判读是不是手机格式
		$m_mobile_code = $this->_get('m_mobile_code');
		if(empty($m_mobile_code)){
			$this->ajaxReturn('手机验证码不正确');
		}
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = 0;
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 1;
		$ary_sms_where['code'] = $m_mobile_code;
		//$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);
		if($sms_log['code'] != $m_mobile_code){
			$this->ajaxReturn('验证码不存在或已过期');exit;
		}else{
			$this->ajaxReturn(true);exit;
		}
		$this->ajaxReturn('验证码不存在或已过期');exit;
    }
	
	/**
     * 发送注册短信
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-06-16
     */
    public function sendMobileCode() {
		//开启手机验证
		$mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
		if($mobile_set['VERIFIPHONE_STATUS']['sc_value'] != 1){
			$this->ajaxReturn(array('status'=>0,'msg'=>'未开启手机验证'));
		}
		$ary_post =  $this->_post();
		if(empty($ary_post['m_mobile'])){
			$this->ajaxReturn(array('status'=>0,'msg'=>'请先输入手机号'));
		}
		//判读是不是手机格式
		$m_mobile = $ary_post['m_mobile'];
		if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'请输入正确的手机号格式！'));
		}
        if (D('Members')->checkMobile($m_mobile)) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'该手机号已被注册，请重新输入！'));
        }
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = array('neq',2);
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 2;
		$ary_sms_where['mobile'] = $ary_post['m_mobile'];
		$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log_count = D('SmsLog')->getCount($ary_sms_where);
		if($sms_log_count>0){
			$this->ajaxReturn(array('status'=>0,'msg'=>'90秒后才允许重新获取验证码！'));
		}
		$SmsApi_obj=new SmsApi();
		//获取注册发送验证码模板
		$template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'REGISTER_CODE'));
		$send_content = '';
		if($template_info['status'] == true){
			$send_content = $template_info['content'];
		}
		if(empty($send_content)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败！'));
		}
		$array_params=array('mobile'=>$ary_post['m_mobile'],'','content'=>$send_content);
		$res=$SmsApi_obj->smsSend($array_params);
         if($res['code'] == '200'){
			//日志记录下
			$ary_data = array();
			$ary_data['sms_type'] = 1;
			$ary_data['mobile'] = $ary_post['m_mobile'];
			$ary_data['content'] = $send_content;
			$ary_data['code'] = $template_info['code'];
			$sms_res = D('SmsLog')->addSms($ary_data);
            if(!$sms_res){
				writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
			}
			$this->ajaxReturn(array('status'=>1,'msg'=>'短信发送成功！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败，'.$res['msg']));
        }
    }

    #### 忘记密码 ##############################################################
    /**
     * 忘记密码提交页面
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-04-11
     */

    public function pageFoget() {
        $ary_data['page_title'] = ' - 找回密码';
        $this->assign($ary_data);
		$tpl = $this->wap_theme_path. 'pageFoget.html';
        $this->display($tpl);
    }
	
	/**
     * 发送忘记密码短信
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-08-06
     */
    public function sendMobilePwdCode() {
		$ary_post =  $this->_post();
		if(empty($ary_post['m_mobile'])){
			$this->ajaxReturn(array('status'=>0,'msg'=>'请先输入手机号'));
		}
		//判读是不是手机格式
		$ary_post['m_mobile'] = trim($ary_post['m_mobile']);
		$m_mobile = strpos($ary_post['m_mobile'],':') ? decrypt($ary_post['m_mobile']) : $ary_post['m_mobile'];
		if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'请输入正确的手机号格式！'));
		}
        if (D('Members')->checkMobile($ary_post['m_mobile'])) {
        }else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'手机号不存在！'));
		}
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = array('neq',2);
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 1;
		$ary_sms_where['mobile'] = $m_mobile;
		$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log_count = D('SmsLog')->getCount($ary_sms_where);
		if($sms_log_count>0){
			$this->ajaxReturn(array('status'=>0,'msg'=>'90秒后才允许重新获取验证码！'));
		}
		$SmsApi_obj=new SmsApi();
		//获取注册发送验证码模板
		$template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'FORGET_PASSWORD'));
		$send_content = '';
		if($template_info['status'] == true){
			$send_content = $template_info['content'];
		}
		if(empty($send_content)){
			$this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败！'));
		}
		$array_params=array('mobile'=>$m_mobile,'','content'=>$send_content);
		$res=$SmsApi_obj->smsSend($array_params);
         if($res['code'] == '200'){
			//日志记录下
			$ary_data = array();
			$ary_data['sms_type'] = 1;
			$ary_data['mobile'] = $ary_post['m_mobile'];
			$ary_data['content'] = $send_content;
			$ary_data['code'] = $template_info['code'];
			$sms_res = D('SmsLog')->addSms($ary_data);
            if(!$sms_res){
				writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
			}
			$this->ajaxReturn(array('status'=>1,'msg'=>'短信发送成功！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败，'.$res['msg']));
        }
    }
	
	/**
     * 向用户重置密码并发送密码到用户手机
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-08-06
     */
    public function synResetByMobile() {
		$ary_data = $this->_request();
		$m_mobile_code = trim($ary_data['m_mobile_code']);
		$m_mobile = trim($ary_data['m_mobile']);
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = 0;
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 1;
		$ary_sms_where['mobile'] = $m_mobile;
		$ary_sms_where['code'] = $m_mobile_code;
		//$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);
		if($sms_log['code'] != $m_mobile_code){
			$this->error('验证码不存在或已过期');exit;
		}else{
			//判断手机号是否在90秒内已重置过
			$ary_sms_where = array();
			$ary_sms_where['check_status'] = 0;
			$ary_sms_where['status'] = 1;
			$ary_sms_where['sms_type'] = 3;
			$ary_sms_where['mobile'] = $m_mobile;
			$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
			$sms_count = D('SmsLog')->getCount($ary_sms_where);
			if($sms_count>0){
				$this->error('您已经重置过密码了。');exit;
			}
			M('')->startTrans();
			//更新验证码使用状态
			$up_res = D('SmsLog')->updateSms(array('id'=>$sms_log['id']),array('check_status'=>1));
			if(!$up_res){
				M('')->rollback();
				$this->error('更新验证码状态失败');exit;
			}
			//发送重置密码到手机
			$SmsApi_obj=new SmsApi();
			//获取注册发送验证码模板
			$template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'SEND_PASSWORD'));
			$send_content = '';
			if($template_info['status'] == true){
				$send_content = $template_info['content'];
			}
			if(empty($send_content)){
				M('')->rollback();
				$this->error('短信发送失败！');exit;
			}
			//设置其他已发送验证码无效
			D('SmsLog')->updateSms(array('sms_type'=>3,'check_status'=>0,'mobile'=>$m_mobile),array('check_status'=>2));
			$array_params=array('mobile'=>decrypt($m_mobile),'','content'=>$send_content);
			$res=$SmsApi_obj->smsSend($array_params);
			if($res['code'] == '200'){
				//日志记录下
				$ary_data = array();
				$ary_data['sms_type'] = 3;
				$ary_data['mobile'] = $m_mobile;
				$ary_data['content'] = $send_content;
				$ary_data['code'] = $template_info['code'];
				$sms_res = D('SmsLog')->addSms($ary_data);
				if(!$sms_res){
					M('')->rollback();
					$this->error('短信发送失败！');exit;
					//writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
				}
			}else{
				M('')->rollback();
				$this->error('短信发送失败！'.$res['msg']);exit;
			}
			//重置密码之后发送短信成功后更改会员表密码信息
			$m_res = D('Members')->where(array('m_mobile'=>encrypt($m_mobile)))->data(array('m_password'=>md5($template_info['code']),'m_update_time'=>date('Y-m-d H:i:s')))->save();
			if($m_res === false) {
				M('')->rollback();
				$this->error('重置密码失败！');exit;
			}
			M('')->commit();
			$this->success('密码重置成功，您的新密码已经发送到您的手机，请尽快使用新密码登录或修改您的密码',"/Wap/User/login",3);exit;
		}
    }

    /**
     * 向用户邮箱发送重置邮件
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-04-11
     */
    public function synReset() {
        $member = D('Members');
        //接收页面数据
        //判断数据是否有效
		$result=D('Members')->where(array('m_email'=>$this->_post('user_email')))->find();
        if (false == $result) {
            $this->error('用户邮箱不正确!');
        }

        if (session('verify') != md5($this->_post('verify'))) {
            $this->error('验证码出错!');
        }

        //生成邮件链接
        $code = authcode($result['m_password'], 'ENCODE', NULL, 600);
        $url = U('Wap/User/doReset', array('name' => $result['m_name'], 'code' => base64_encode($code)), '', false, true);

        $ary_email_cfg = D('SysConfig')->getEmailCfg();

        $ary_option = array(
            'receiveMail' => $this->_post('user_email'),
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
			//日志记录下
			$ary_data = array();
			$ary_data['email_type'] = 1;
			$ary_data['email'] = $ary_option['receiveMail'];
			$ary_data['content'] = $ary_option['message'];
			$email_res = D('EmailLog')->addEmail($ary_data);
			if(!$email_res){
				writeLog(json_encode($ary_data),date('Y-m-d')."send_email.log");
			}
            $this->success('邮件已经发送到您的邮箱', U('Wap/User/login'),3);
        } else {
            $this->error('重置密码邮件发送失败，请管理员检查邮件发送设置');
        }
    }

    /**
     * 执行重置密码命令
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-04-11
     */
    public function doReset() {
        $member = D('Members');
        //解密连接代码
		$code = authcode(base64_decode($this->_get('code')), 'DECODE', NULL, 600);
        //验证
        $result = $member->checkNamePassword($this->_get('name'), $code);
        if (FALSE == $code || FALSE == $result) {
            $this->error('非法链接或者链接已经失效');
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
			'receiveMail' =>$result['m_email'],
            'subject' => '密码修改',
            'message' => "密码为:$new_password",
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
			//日志记录下
			$ary_data = array();
			$ary_data['email_type'] = 1;
			$ary_data['email'] = $ary_option['receiveMail'];
			$ary_data['content'] = $ary_option['message'];
			$email_res = D('EmailLog')->addEmail($ary_data);
			if(!$email_res){
				writeLog(json_encode($ary_data),date('Y-m-d')."send_email.log");
			}
            $this->success('密码重置成功，您的新密码已经发送到邮箱，请尽快使用新密码登录或修改您的密码', U('Wap/User/login'),3);
        } else {
            $this->error('重置密码邮件发送失败，请管理员检查邮件发送设置');
        }
    }

    /**
     * 会员登录操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-30
     */
    public function doUserLogin() {
        $member = D('Members');
        $ary_result = $member->doLoginApi($this->_post('m_name'), $this->_post('m_password'), $this->_post("verify"));
//        echo "<pre>";print_r($ary_result);exit;
        if ($ary_result['status']) {
            $ary_member = $member->getInfo($this->_post('m_name'));
            //同步erp会员查询接口
            //将会员信息存入session
            session('Members', $ary_member);
            $ary_cart = array();
            if (!empty($ary_member['m_id'])) {
                $session_carts = session("Cart");
                $ary_db_carts = D('Cart')->ReadMycart();
                foreach ($session_carts as $str_pdt => $int_num) {
                    if($int_num['type'] == '4'){
                		$ary_cart[$str_pdt] = $int_num;
                	}else{
                	    $tmp_int_num = (int) $int_num['num'];
	                    //大于0的新插入/更新. 小于等于0的不作处理
	                    if ($tmp_int_num > 0) {
	                        $ary_cart[$str_pdt] = $int_num;
	                    }                		
                	}
                }
                foreach ($ary_cart as $key => $int_num) {
                    $good_type = $int_num['type'];
                    if (!empty($ary_member['m_id'])) {//database
                    	if($good_type == '4'){
                    		$ary_db_carts[$key] = $int_num;
                    	}else{
                    	    if (array_key_exists($key, $ary_db_carts) && isset($ary_db_carts[$key]['type']) && ($good_type == $ary_db_carts[$key]['type'])) {
	                            $ary_db_carts[$key]['num']+=$int_num['num'];
	                        } else {
	                            $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num['num'], 'type' => $good_type);
	                        }                 		
                    	} 
                    }
                }
                $Cart = D('Cart')->WriteMycart($ary_db_carts);
                session('Cart', NULL);
                $redirect_uri = $_GET['redirect_uri'];
                if(empty($redirect_uri)){
                	$redirect_uri = '/Wap/Index/index';
                }
                $this->success("登录成功", $redirect_uri);
            }
        } else {
            $this->error($ary_result['msg']);
        }

//        $this->success($ary_result['msg']);
//        $this->ajaxReturn(array('result' => $ary_result['status'], 'msg' => $ary_result['msg']));
    }

    /**
     * 接收第三方支付时的异步通知
     * 注：异步通知无需登录
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-23
     */

    public function synPayNotify() {
        $data = $_REQUEST;
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);
        unset($data['code']);
        writeLog(json_encode($data),"order_status.log");
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
        if (false === $ary_pay) {
            $this->error('不存在的支付方式');
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);
        M('','','DB_CUSTOM')->startTrans();
        $result['o_id'] = trim($result['o_id'],"订单编号:");
        $ary_member = D('Members')->where(array('m_id'=>$result['m_id']))->find();
        if ($result['result']) {
            //检查请求签名正确
            $Order = D('Orders');
            $ary_order = $Order->where(array('o_id' => $result['o_id']))->find();
            writeLog(json_encode($result),"order_status.log");
            writeLog(json_encode($ary_order),"order_status.log");
            if ($result['int_status'] == 1) {
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }else if($result['int_status'] == 2){
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                    
                }
            }elseif($result['int_status'] == 3){
		//在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }
            else if($result['int_status'] == 5){//创建交易
          
                //订单日志记录
                if(D('OrdersLog')->where(array('o_id'=>$result['o_id'],'ol_behavior'=>'创建'.$ary_pay['pc_custom_name'].'交易','ol_uname'=>$ary_member['m_name']))->count() == 0){
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '创建'.$ary_pay['pc_custom_name'].'交易',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }   else{
                echo D('OrdersLog')->getLastSql();exit;
                    M('','','DB_CUSTOM')->commit();
                    echo "success";
                    die();
                }
                
                
            }
//            elseif($result['int_status'] == 4){
//                //订单日志记录
//                $ary_orders_log = array(
//                    'o_id' => $result['o_id'],
//                    'ol_behavior' => '支付已在第三方创建--等待支付',
//                    'ol_uname' => $ary_member['m_name'],
//                    'ol_create' => date('Y-m-d H:i:s')
//                );
//                $res_orders_log = D('OrdersLog')->add($ary_orders_log);
//                writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
//                if (!$res_orders_log) {
//                    M('','','DB_CUSTOM')->rollback();
//                    $this->error('订单日志记录失败');
//                    exit;
//                }else{
//                    M('','','DB_CUSTOM')->commit();
//                }
//            }
            else{
                /* $arr_result = D('Orders')->where(array('o_id'=>$result['o_id']))->data(array('o_status'=>'5'))->save();
                if (!$arr_result) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error("确认收货失败");
                } else {
                    M('','','DB_CUSTOM')->commit();
                    echo "success";
                    die();
                } */
            }
        }else{
            $this->error('支付失败');
        }
    }
    /**
     * 线上充值接收第三方支付时的异步通知
     * 注：异步通知无需登录
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-24
     */

    public function synChargeNotify() {
        $data = $_REQUEST;
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);
        unset($data['code']);
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();

        if (false == $ary_pay) {
            $this->error('不存在的支付方式');
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($data);
        
        M('','','DB_CUSTOM')->startTrans();
        if ($result['result']) {
            //检查请求签名正确
            //获取会员信息
            $ary_member = D('Members')->where(array('m_id' => $result['m_id']))->find();

            //已经充值过的不能重复充值
            $where = array('ra_payment_sn'=>$result['gw_code'],'ra_payment_method'=>$ary_pay['pc_custom_name']);
            $int_running_find = D('RunningAccount')->where($where)->find();
            if(false != $int_running_find){
                //已经存在相同流水号的
                M('','','DB_CUSTOM')->rollback();
                $this->error('已经入账',U('Ucenter/Financial/pageDepositList'));
            }
            if($result['int_status'] == 1 || $result['int_status'] == 3){
                //直接付款成功，交易成功
                $ary_where_account = array(
                    'm_id' => $result['m_id'],
                    'ra_money' => $result['total_fee'],
                    'ra_type' => 0, //充值
                    'ra_payment_method' => $ary_pay['pc_custom_name'],
                    'ra_before_money' => (float) $ary_member['m_balance'],
                    'ra_after_money' => (float) $ary_member['m_balance'] + (float) $result['total_fee'],
                    'ra_payment_sn' => $result['gw_code']
                );
                $RunningAccount_info = D('RunningAccount')->where($ary_where_account)->find();
                if(!isset($RunningAccount_info) && empty($RunningAccount_info)){
                    $ary_where_account['ra_create_time'] = date('Y-m-d h:i:s');
                    $RunningAccount_info = D('RunningAccount')->add($ary_where_account);
                }
                

                if (false === $RunningAccount_info) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error('充值流水账添加错误');
                } else {
                    $ary_where_balance=array(
                        'm_id'=>$result['m_id'],
                        'bi_money'=>$result['total_fee'],
                        'bi_sn'=>$result['gw_code'],
                        'bt_id'=>3,
                        'bi_verify_status'=>1,
                        'bi_service_verify'=>1,
                        'bi_finance_verify'=>1,
                        'bi_desc'=>$ary_pay['pc_custom_name'].'线上充值'
                    );
                    $BalanceInfo_info = D('BalanceInfo')->where($ary_where_balance)->find();
                    if(!isset($BalanceInfo_info) && empty($BalanceInfo_info)){
                        $ary_where_balance['bi_create_time'] = date('Y-m-d H:i:s');
                        $ary_where_balance['bi_update_time'] = date('Y-m-d H:i:s');
                        $BalanceInfo_info = D('BalanceInfo')->add($ary_where_balance);
                    }
                    if(false === $BalanceInfo_info){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error('结余款调整单添加失败！');exit;
                    }
                    //更新用户预存款
                    $updata_data['m_balance']= $ary_where_account['ra_after_money'];
                    M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_where_account['m_id']))->save($updata_data);
                    M('','','DB_CUSTOM')->commit();
                    echo "success";die();
                   // $this->success('支付成功', U('Ucenter/Financial/pageDepositList'));exit;
                }
            }else if($result['int_status'] == 2){
                //担保交易，等待管理员付款（生成结余款调整单）
                $ary_where_balance=array(
                    'm_id'=>$result['m_id'],
                    'bi_money'=>$result['total_fee'],
                    'bi_sn'=>$result['gw_code'],
                    'bt_id'=>3,
                    'bi_verify_status'=>0,
                    'bi_service_verify'=>0,
                    'bi_finance_verify'=>0,
                    'bi_desc'=>$ary_pay['pc_custom_name'].'线上充值'
                );
                $BalanceInfo_info = D('BalanceInfo')->where($ary_where_balance)->find();
                if(!isset($BalanceInfo_info) && empty($BalanceInfo_info)){
                    $ary_where_balance['bi_create_time'] = date('Y-m-d H:i:s');
                    $ary_where_balance['bi_update_time'] = date('Y-m-d H:i:s');
                    $BalanceInfo_info = D('BalanceInfo')->add($ary_where_balance);
                }
                if(false === $BalanceInfo_info){
                    M('','','DB_CUSTOM')->rollback();
                    $this->error('结余款调整单添加失败！');
                }
                M('','','DB_CUSTOM')->commit();
                echo "success";die();
               // $this->success('付款成功，等待发货', U('Ucenter/Financial/pageDepositList'));
            }
        } else {
            M('','','DB_CUSTOM')->rollback();
            $this->error('错误访问');
        }
    }
    
    /**
     * 第三方登录请求地址
     * @date 2013-07-29
     */
    public function thdLoginUr(){
        $config = D('SysConfig');
        $common = new ThdrustLogin();
        $type = strtolower($_GET['type']);
        $logindata = $config->getConfigs("THDLOGIN",null,null,null,1);
        $ary_status = json_decode($logindata['THDSTATUS']['sc_value'],TRUE);
        $msg_data = array(
            'sina'  => '新浪',
            'qq'    =>'QQ',
            'tqq'   =>   '腾讯微博',
            'renren'  => '人人网'
        );
        if(!empty($ary_status[$type]) && $ary_status[$type] == '1'){
            echo $str_url = $common->getThdCodeUrl($this->_get('type'),false);
            header('location:'.$str_url);
        }else{
            $this->error($msg_data[$type]."授权登录已被停用,或已不存在,请联系管理员");
            // die($msg_data[$type]."授权登录已被停用,或已不存在,请联系管理员");
        }
    
    }
    
    /**
     * 请求完成code回调入口，再次请求token
     * 当所有请求完成后返回数据进行解析判断当前请求的用户在网站内是否存在
     * 
     * @since stage 1.0
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-1-16
     */
    public function getToken(){
        if(!isset($_GET['code']) && empty($_GET['code'])){
            header('location:'."/Wap/User/Login/");
        }
        $common = new ThdrustLogin();
        $member = D('Members');
        //获取第三方接口信息
        $ary_result = $common->getThdRequestUrl($_GET['code']);
        //判断当前用户是否存在
        $ary_member = $member->getInfo('',$ary_result['open_id']);
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        if(!isset($ary_member['open_id'])){
            //默认等级
            $ml = D('MembersLevel')->getSelectedLevel();
            //新增用户
            $add_member = array();
            $add_member['m_name'] = $_SESSION['str_type'].'_'.$ary_result['user_info']['nickname'];
            $add_member['open_name'] = $ary_result['user_info']['nickname'];
            $add_member['open_id'] = $ary_result['open_id'];
            $add_member['open_token'] = $ary_result['open_token'];
            $add_member['open_source'] = $_SESSION['str_type'];
            $add_member['ml_id'] = $ml;
            $add_member['login_type'] = 1;
			$data = D('SysConfig')->getCfgByModule('MEMBER_SET');
            if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
                $add_member['m_verify'] = '2';
            }
            if($ary_result['user_info']['gender'] == '男'){
                $add_member['m_sex'] = 1;
            }else{
                $add_member['m_sex'] = 0;
            }
            $add_member['m_create_time'] = date('Y-m-d H:i:s');
            $result = $member->add($add_member);
            if($result === false){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->error("登录失败", '/Wap/Index/index');
            }else{
                $ary_member = $member->getInfo('',$ary_result['open_id']);
            }
        }else{
            $member->where(array('m_id'=>$ary_member['m_id']))->save(array('login_type'=>1));
            $ary_member['login_type'] = 1;
        }
        $ary_member['m_name'] = $ary_member['open_name'];
        session('Members', $ary_member);
        $ary_cart = array();
        if (!empty($ary_member['m_id'])) {
            $session_carts = session("Cart");
            
            $ary_db_carts = D('Cart')->ReadMycart();
            foreach ($session_carts as $str_pdt => $int_num) {
                if($int_num['type'] == '4' || $int_num['type'] == '6'){
                    $ary_cart[$str_pdt] = $int_num;
                }else{
                    $tmp_int_num = (int) $int_num['num'];
                    //大于0的新插入/更新. 小于等于0的不作处理
                    if ($tmp_int_num > 0) {
                        $ary_cart[$str_pdt] = $int_num;
                    }                		
                }
            }
            foreach ($ary_cart as $key => $int_num) {
                $good_type = $int_num['type'];
                if (!empty($ary_member['m_id'])) {//database
                    if($good_type == '4' ||$good_type == '6'){
                        $ary_db_carts[$key] = $int_num;
                    }else{
                        if (array_key_exists($key, $ary_db_carts) && isset($ary_db_carts[$key]['type']) && ($good_type == $ary_db_carts[$key]['type'])) {
                            $ary_db_carts[$key]['num']+=$int_num['num'];
                        } else {
                            $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num['num'], 'type' => $good_type);
                        }                 		
                    } 
                }
            }
            $source_id = M('source_platform',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sp_code'=>$_SESSION['str_type'],'sp_stauts'=>1))->getField('sp_id');
            if(!D('RelatedMembersSourcePlatform')->where(array('sp_id'=>$source_id,'m_id'=>$ary_member['m_id']))->count()){
                if(false === D('RelatedMembersSourcePlatform')->add(array('sp_id'=>$source_id,'m_id'=>$ary_member['m_id']))){
                    M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                    $this->error('平台不存在，请联系管理员');
                }
            }
            
            $Cart = D('Cart')->WriteMycart($ary_db_carts);
            session('Cart', NULL);
            $redirect_uri = $_GET['redirect_uri'];
            if(empty($redirect_uri)){
                $redirect_uri = '/Wap/Index/index';
            }
            M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
			$this->redirect($redirect_uri);
            //$this->success("登录成功", $redirect_uri);
        }else{
            $this->error("登录失败");
        }
    }
    
    /**
     * 团购详情页，异步请求用户登录页面
     *
     */
    public function doBulkLogin(){
        $tpl = $this->wap_theme_path . 'doBulkLogin.html';
        $this->display($tpl);
    }

    /**
     * 注册成功页面
     * @author WangHaoYu <why419163@163.com>
     * @version 7.4
     * @date 
     *
     */
    public function registerSuccess() {
        $ary_member = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$_SESSION['last_member_id']))->field('m_email,m_name')->find();
        unset($_SESSION['last_member_id']);
        $this->assign('member',$ary_member);
        $tpl = $this->wap_theme_path . 'registerSuccess.html';
        $this->display($tpl);
    }
    
    /**
     * 注册协议页面
     *
     */
    public function agreement(){
        $html = D('SysConfig')->getCfgByModule('GY_REGISTER_CONFIG');
        $this->assign('html',$html['REGISTER']);
        $tpl =$this->wap_theme_path . 'registeRagreement.html';
        $this->display($tpl);
    }
    
    /**
	 * 异步获取 区域数据
	 */
	public function cityRegionOptions() {
		if (!isset($_POST["parent_id"]) || !is_numeric($_POST["parent_id"]) || $_POST["parent_id"] <= 0) {
			echo json_encode(array("status" => false, "data" => array(), "message" => "父级区域ID不合法"));
			exit;
		}
		$int_parent_id = $_POST["parent_id"];
		$array_result = D("CityRegion")->where(array("cr_parent_id" => $int_parent_id,'cr_status'=>'1'))->order(array("cr_order" => "asc"))->getField("cr_id,cr_name");
		if (false === $array_result) {
			echo json_encode(array("status" => false, "data" => array(), "message" => "无法获取区域数据"));
			exit;
		}
		echo json_encode(array("status" => true, "data" => $array_result, "message" => "success"));
		exit;
	}
	
	/**
     * 接收微信支付时的异步通知
     * 注：异步通知无需登录
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-04-02
     */
    public function synPayWeixinNotify() {
		$data = $_REQUEST;
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		writeLog("REQUEST: ".$xml,"order_pay_weixin".date('Ymd').CI_SN.".log");
        $code = $data['code'];
        //过滤掉thinkphp自带的参数，和非回调参数
        unset($data['_URL_']);
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);
        unset($data[4]);        
		unset($data['code']);
        //获取支付类型信息
        $Payment = D('PaymentCfg');
        $ary_pay = $Payment->where(array('pc_abbreviation' => $code))->find();
        if (false === $ary_pay) {
            $this->error('不存在的支付方式');
			die;
        }
        $Pay = $Payment::factory($ary_pay['pc_abbreviation'], json_decode($ary_pay['pc_config'], true));
        $result = $Pay->respond($xml);
        M('','','DB_CUSTOM')->startTrans();
        $result['o_id'] = trim($result['o_id'],"订单编号:");
        $ary_member = D('Members')->where(array('m_id'=>$result['m_id']))->find();
        if ($result['result']) {
			if(empty($result['m_id'])){
                //已经存在相同流水号的
				M('','','DB_CUSTOM')->commit();
				echo "success";
				die();
			}
            //检查请求签名正确
            $Order = D('Orders');
            $ary_order = $Order->where(array('o_id' => $result['o_id']))->find();
           // writeLog(json_encode($result),"order_status.log");
            //writeLog(json_encode($ary_order),"order_status.log");
			if($ary_order['o_status'] != 1) {
				writeLog("当前订单状态【o_status={$ary_order['o_status']}】不允许做此操作","order_status.log");	
				die;				
			}elseif($ary_order['o_pay_status'] == 1) {
				writeLog("订单为已支付，丢弃此次通知【{$ary_order['o_id']}】","order_status.log");	
				die;				
			}
            if ($result['int_status'] == 1) {
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }else if($result['int_status'] == 2){
                //在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {
                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }

                }
            }elseif($result['int_status'] == 3){
		//在线支付即时交易成功情况
                $ary_order['o_pay'] = $result['total_fee'];
                $ary_order['o_pay_status'] = 1;
                $result_order = $Order->orderPayment($result['o_id'], $ary_order, $int_type = 5);

                if (!$result_order['result']) {
                    M('','','DB_CUSTOM')->rollback();
                    $this->error($result_order['message']);
                } else {

                    //订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }
            }
            else if($result['int_status'] == 5){//创建交易

                //订单日志记录
                if(D('OrdersLog')->where(array('o_id'=>$result['o_id'],'ol_behavior'=>'创建'.$ary_pay['pc_custom_name'].'交易','ol_uname'=>$ary_member['m_name']))->count() == 0){
                    $ary_orders_log = array(
                        'o_id' => $result['o_id'],
                        'ol_behavior' => '创建'.$ary_pay['pc_custom_name'].'交易',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    writeLog(D('OrdersLog')->getLastSql(),"order_status.log");
                    if(!$res_orders_log){
                        M('','','DB_CUSTOM')->rollback();
                        $this->error("创建订单日志失败");
                    }else{
                        M('','','DB_CUSTOM')->commit();
                        echo "success";
                        die();
                    }
                }   else{
                //echo D('OrdersLog')->getLastSql();exit;
                    M('','','DB_CUSTOM')->commit();
                    echo "success";
                    die();
                }
            }
            else{

            }
        }else{
            $this->error('支付失败');
        }
    }
	
	/**
     * 判断是否是微2商
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-18
     */
	public function isWeiXin(){
		$weixinapi = new WeixinApi();
		$weixinapi->wxSign();
		exit;
	}

	/**
     * 判断是否是微2商
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-18
		subscribe 	用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息。
		openid 	用户的标识，对当前公众号唯一
		nickname 	用户的昵称
		sex 	用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
		city 	用户所在城市
		country 	用户所在国家
		province 	用户所在省份
		language 	用户的语言，简体中文为zh_CN
		headimgurl 	用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
		subscribe_time 	用户关注时间，为时间戳。如果用户曾多次关注，则取最后关注时间
		unionid 	只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。详见：获取用户个人信息（UnionID机制）
		remark 	公众号运营者对粉丝的备注，公众号运营者可在微信公众平台用户管理界面对粉丝添加备注
		groupid 	用户所在的分组ID 
     */
	public function getSignId(){
		$code = $_GET["code"]; 
		if(empty($code)){
			$_SESSION['no_wx'] = 1;
			$this->redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);exit;
		}
		$weixinapi = new WeixinApi();
		$open_id = $weixinapi->getSignId($code);	
		if(empty($open_id)){
			$_SESSION['no_wx'] = 1;
			$this->redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);exit;
		}
		//查询会员信息是否存在
		$ary_member = D('Members')->getInfo(null,$open_id);
		if(!empty($ary_member['m_id'])){
			$_SESSION['Members'] = $ary_member;
			if(!empty($ary_member['open_name'])){
				$_SESSION['Members']['m_name'] = $ary_member['open_name'];
			}
			if($_SESSION['is_product'] == 1){
				if(!empty($_SESSION['REQUEST_URI'])){
					$_SESSION['is_product'] = 0;
					$redirect_url = $_SESSION['REQUEST_URI'];
					unset($_SESSION['REQUEST_URI']);
					$this->redirect($redirect_url);exit;
					//$this->success('登陆成功',$redirect_url);exit;					
				}else{
					$_SESSION['is_product'] = 0;
					$this->redirect(U('/Wap/Products/index'));exit;
					//$this->success('登陆成功','/Wap/Products/index');exit;
				}
			}else{
				if($_SESSION['is_cart'] == 1){
					$_SESSION['is_cart'] = 0;
					$this->redirect(U('/Wap/Cart/pageCartList'));exit;
					//$this->success('登陆成功','/Wap/Cart/pageCartList');exit;					
				}else{
					$this->redirect(U('/Wap/Ucenter/index'));exit;
					//$this->success('登陆成功',U('/Wap/Ucenter/index'));exit;
				}	
			}
		}
		$access_tocken = $weixinapi->getAccessTocken();
		if(empty($access_tocken)){
			$_SESSION['no_wx'] = 1;
			$this->redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);exit;
		}
		if(!empty($open_id) && !empty($access_tocken)){
			$user_info = $weixinapi->getUserInfo($open_id,$access_tocken);
			writeLog(json_encode($user_info),"zbx.log");
			if(!empty($user_info)){
				//默认等级
				$ml = D('MembersLevel')->getSelectedLevel();
				//新增用户
				$add_member = array();
				$add_member['m_name'] = $user_info['openid'];
				$add_member['open_name'] = $user_info['nickname'];
				$add_member['open_id'] = $user_info['openid'];
				if(empty($add_member['m_name'])){
					$add_member['m_name'] = $open_id;
					$add_member['open_name'] = '微信用户';
					$add_member['open_id'] = $open_id;
				}
				//$add_member['open_token'] = $access_tocken;
				$add_member['open_source'] = '微信';
				if(!empty($ml)){
					$add_member['ml_id'] = $ml;
				}
				$add_member['login_type'] = 1;
				$add_member['m_create_time'] = date('Y-m-d H:i:s');
				$add_member['m_update_time'] = date('Y-m-d H:i:s');
				$cr_id = D('CityRegion')->getAvailableLogisticsList($user_info['province'], $user_info['city'],$user_info['city']);
				if(!empty($cr_id)){
					$add_member['cr_id'] = $cr_id;
				}
				$data = D('SysConfig')->getCfgByModule('MEMBER_SET');
				if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
					$add_member['m_verify'] = '2';
				}
				$add_member['m_sex'] = '2';
				if($user_info['sex'] == '1'){
					$add_member['m_sex'] = 1;
				}
				if($user_info['sex'] == '2'){
					$add_member['m_sex'] = 0;
				}		
				if(isset($user_info['headimgurl'])){
					$add_member['m_head_img'] = $user_info['headimgurl'];
				}
				$result = M('members',C('DB_PREFIX'),'DB_CUSTOM')->add($add_member);
				if($result === false){
					$_SESSION['no_wx'] = 1;
					$this->error("登录失败", '/Wap/Ucenter/index');exit;
				}else{
					$ary_member = D('Members')->getInfo('',$open_id);
					$_SESSION['Members'] = $ary_member;
					$_SESSION['Members']['m_name'] = $ary_member['open_name'];
					if($_SESSION['is_product'] == 1){
						if(!empty($_SESSION['REQUEST_URI'])){
							$_SESSION['is_product'] = 0;
							$redirect_url = $_SESSION['REQUEST_URI'];
							unset($_SESSION['REQUEST_URI']);
							$this->redirect($redirect_url);exit;
							//$this->success('登陆成功',$redirect_url);exit;					
						}else{
							$_SESSION['is_product'] = 0;
							$this->redirect(U('/Wap/Products/index'));exit;
							//$this->success('登陆成功','/Wap/Products/index');exit;
						}
					}else{
						if($_SESSION['is_cart'] == 1){
							$_SESSION['is_cart'] = 0;
							$this->redirect(U('/Wap/Cart/pageCartList'));exit;
							//$this->success('登陆成功','/Wap/Cart/pageCartList');exit;					
						}else{
							$this->redirect(U('/Wap/Ucenter/index'));exit;
							//$this->success('登陆成功',U('/Wap/Ucenter/index'));exit;
						}	
					}
				}
			}
		}
		$_SESSION['no_wx'] = 1;
		if($_SESSION['is_product'] == 1){
			if(!empty($_SESSION['REQUEST_URI'])){
				$_SESSION['is_product'] = 0;
				$redirect_url = $_SESSION['REQUEST_URI'];
				unset($_SESSION['REQUEST_URI']);
				$this->redirect($redirect_url);exit;
				//$this->success('登陆成功',$redirect_url);exit;					
			}else{
				$_SESSION['is_product'] = 0;
				$this->redirect(U('/Wap/Products/index'));exit;
				//$this->success('登陆成功','/Wap/Products/index');exit;
			}
		}else{
			if($_SESSION['is_cart'] == 1){
				$_SESSION['is_cart'] = 0;
				$this->redirect(U('/Wap/Cart/pageCartList'));exit;
				//$this->success('登陆成功','/Wap/Cart/pageCartList');exit;					
			}else{
				$this->redirect(U('/Wap/Ucenter/index'));exit;
				//$this->success('登陆成功',U('/Wap/Ucenter/index'));exit;
			}	
			//$this->redirect(U('/Wap/Ucenter/index')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);exit;
		}	
	}
	
}
