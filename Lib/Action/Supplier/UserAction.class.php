<?php

/**
 * 后台用户控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class UserAction extends GyfxAction {

    /**
     * 后台登录默认控制器，需要重定向到登录页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function index() {
       
        $this->redirect(U('Supplier/User/pageLogin'));
    }

    /**
     * 后台登录页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-04
     */
    public function pageLogin() {
        $redirect_url = U("Supplier/Index/index");
		if(isset($_GET["redirect_uri"]) && "" != $_GET["redirect_uri"]){
			$redirect_url = urldecode($_GET["redirect_uri"]);
		}
		$_SESSION['pwd_salt'] = rand();
		$redirect_url = htmlspecialchars($redirect_url);
		$redirect_url = RemoveXSS($redirect_url);
		$this->assign("redirect_url",$redirect_url);
        $this->display();
    }

    /**
     * 后台安全退出，销毁session
     * @author zuo  <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     * @modifiy Terry <wanghui@guanyisoft.com>
     */
    public function doLogout() {
        if(isset($_SESSION[C('USER_AUTH_KEY')])){
            unset($_SESSION[C('USER_AUTH_KEY')]);
            unset($_SESSION);
            session_destroy();
			cookie('session_uid',null);
            $this->success('登出成功！', U('Supplier/User/pageLogin'));
        }else{
            $this->error("已经登出！",U('Supplier/User/pageLogin'));
        }
//        session('Admin', NULL);
//        $this->success('用户退出成功', U('Supplier/User/pageLogin'));
    }

    /**
     * 登录操作
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-24
     */
    public function doLogin() {
        $ary_post = $this->_post();
		//防跨站攻击
		foreach($ary_post as $key=>$str_post){
			$ary_post[$key] = RemoveXSS($str_post);
		}
		/*
  		$password = base64_decode($ary_post['password']);
        $rand = $_SESSION['pwd_salt'];
        $salt_index = $this->sunday(strval($rand),strval($password));
        $m_password = substr($password,0,$salt_index);
        $m_password = !empty($m_password) ? $m_password : $ary_post['password'];
        $ary_post['password'] =  $m_password;
		*/
        if (empty($ary_post['username'])) {
            $this->error("请输入帐号！");
        } else if (empty($ary_post['password'])) {
            $this->error("请输入密码！");
        } else if (empty($ary_post['code']) || trim($ary_post['code']) == "验证码") {
            $this->error("请输入验证码！");
        }
        //生成认证条件
        $map = array();
        // 支持使用绑定帐号登录
        $map['u_name'] = $ary_post['username'];
        $map["u_status"] = array('gt', 0);
        $verify = session("av");
        if ($verify != md5($ary_post['code'])) {
            $this->error("验证码错误！");
        }
        
        $supplier_access = D('SysConfig')->getCfgByModule('SUPPLIER_ACCESS');
        $exitTime = $supplier_access['EXPIRED_TIME'];
        //import('ORG.Util.RBAC');
        
        $rbac = new Arbac();
        @import('ORG.Util.Session');
        
        //$auth_info = RBAC::authenticate($map);
        $auth_info = $rbac->authenticate($map,'supplier');
        //var_dump(M('supplier', C('DB_PREFIX'), 'DB_CUSTOM')->where($map)->find());
        //exit();
        //echo "<pre>";print_r($map);exit;
        if (empty($auth_info)) {
            $this->error('帐号不存在或已禁用！');
        } else {
            if ($auth_info['u_passwd'] != md5($ary_post['password'])) {
                $this->error('密码错误！');
            }
            Session::setExpire(time() + $exitTime * 60);

            $_SESSION['Supplier'] = $auth_info['u_id'];
            $_SESSION['supplier_name'] = $auth_info['u_name'];
            $_SESSION['last_time'] = $auth_info['u_lastlogin_time'];
            $_SESSION['login_count'] = $auth_info['u_login_count'];
            //$sysadmin = D('SysConfig')->getCfgByModule('SYS_ADMIN');
            if ($auth_info['u_name'] == $supplier_access['SYS_ADMIN']) {
                $_SESSION[C('SUPPLIER_AUTH_KEY')] = true;
            }

			$SESSION_TYPE = (ini_get('session.save_handler') == 'redis')?1:0;
			if(empty($SESSION_TYPE)){
				$uniqid = md5(uniqid(microtime(true)).mt_rand(11111,99999));
				$ary_info = array(
					'supplier_name' => $auth_info['u_name'],
					'last_time' => $auth_info['u_lastlogin_time'],
					'login_count' => $auth_info['u_login_count']
					);
				writeMemberCache($uniqid,$ary_info);
				cookie('session_uid',$uniqid,3600);			
			}
            //保存登录信息
            $supplier = M('Supplier',C('DB_PREFIX'),'DB_CUSTOM');
            $ip = get_client_ip();
            $time = date("Y-m-d H:i:s");
            $data = array();
            $data['u_lastlogin_time'] = $time;
            $data['u_login_count'] = array('exp', 'u_login_count + 1');
            $data['u_lastlogin_ip'] = $ip;
            $supplier->where(array('u_name'=>$ary_post['username']))->save($data);
            // 缓存访问权限
//            echo "<pre>";print_r($auth_info);exit;
            $rbac->saveAccessList();
            //RBAC::saveAccessList($auth_info['user_id']);
            //session('Admin',array('name'=>$auth_info['u_name']));
            $ary_data = array();
            $supplier_log = D("SupplierLog");
            $ary_data['id'] = $auth_info['user_id'];
            $ary_data['u_name'] = $auth_info['u_name'];
            $ary_data['log_ip'] = $ip;
            $ary_data['log_create'] = $time;
            $supplier_log->add($ary_data);
			make_fsockopen('/Script/Batch/ajaxAsynchronous');
            $this->success("登陆成功", U('Supplier/Index/index'));
            // echo "jia";
            // exit;
        }
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
     * 验证码
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-09
     */
    public function verify() {
        import('ORG.Util.Image');
        Image::buildImageVerify(4, 1, 'png', 120, 50, 'av');
    }
}
