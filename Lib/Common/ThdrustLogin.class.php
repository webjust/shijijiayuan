<?php
/**
 * 第三方信任登录相关
 *
 * @package Action 
 * @since stage 1.0
 * @author Joe <qianyijun@guanyusoft.com>
 * @date 2013-07-29
 * @copyright Copyright (C) 2013, Shanghai guanyusoft Co., Ltd.
 */
class ThdrustLogin{
    private $qq_api_id;
    private $qq_aip_key;
    private $sina_api_id;
    private $sina_api_key;
    private $renren_api_id;
    private $renren_api_key;
    private $wx_api_id;
    private $wx_api_key;    
    /**
     * 构造方法
     * @author Joe <qianyijun@piaoxiao2.com>
     * @date 2013-01-10
     */
    public function  __construct(){
        $config = D('SysConfig');
        $data = $config->getConfigs("THD_LOGIN");
        if(!empty($data) && is_array($data)){
            $this->qq_api_id = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'QQ_ID'))->getField('sc_value');
            $this->qq_aip_key = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'QQ_KEY'))->getField('sc_value');
            $this->sina_api_id = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'SINA_ID'))->getField('sc_value');
            $this->sina_api_key = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'SINA_KEY'))->getField('sc_value');
            $this->renren_api_id = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'RENREN_ID'))->getField('sc_value');
            $this->renren_api_key = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'RENREN_KEY'))->getField('sc_value');
            $this->wx_api_id = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'WX_ID'))->getField('sc_value');
            $this->wx_api_key = $config->where(array('sc_module'=>'THD_LOGIN','sc_key'=>'WX_KEY'))->getField('sc_value');
        			
        }else{
            $type = strtolower($_GET['type']);
            $logindata = $config->getConfigs("THDLOGIN",null,null,null,1);
            $ary_status = json_decode($logindata['THDSTATUS']['sc_value'],TRUE);
            $arr_data = json_decode($logindata['THDDATA']['sc_value'],TRUE);
            $msg_data = array(
                'sina'  => '新浪',
                'qq'    =>'QQ',
                'tqq'   =>   '腾讯微博',
                'renren'  => '人人网',
                'wx'  => '微信'
            );

            $this->qq_api_id = $arr_data['qqid'];
            $this->qq_aip_key = $arr_data['qqkey'];
            $this->sina_api_id = $arr_data['sinaid'];
            $this->sina_api_key = $arr_data['sinakey'];
            $this->renren_api_id = $arr_data['renrenid'];
            $this->renren_api_key = $arr_data['renrenkey'];
            $this->tqq_api_id = $arr_data['tqqid'];
            $this->tqq_api_key = $arr_data['tqqkey'];
            $this->wx_api_id = $arr_data['wxid'];
            $this->wx_api_key = $arr_data['wxkey'];
            /*if(!empty($ary_status[$type]) && $ary_status[$type] == '1'){
                $id = $type."_api_id";
                $key = $type."_api_key";
                $this->$id = $arr_data[$type.'id'];
                $this->$key = $arr_data[$type.'key'];
            }else{
                die($msg_data[$type]."授权登录已被停用,或已不存在,请联系管理员");
            }*/
        }
    }
    
    /**
     * 获取第三方信任登录地址页面
     *
     * @parme string str_type 请求类型 QQ,RenRen,Sina
     * @since 2013-07-29
     * @author Joe <qianyijun@guanyusoft.com>
     * @date 2013-1-16
     */
    public function getThdCodeUrl($str_type,$status=true){
        if($str_type == 'QQ' || $str_type == 'Sina' || $str_type == 'RenRen' || $str_type == 'WX'){
            $_SESSION['str_type'] = $str_type;
            switch($str_type){
                //请求腾讯code地址
                case 'QQ':
                    $str_return = 'https://graph.qq.com/oauth2.0/authorize';
                    $str_return.= '?client_id='.$this->qq_api_id;
                break;
                //请求新浪code地址
                case 'Sina':
                    $str_return = 'https://api.weibo.com/oauth2/authorize';
                    $str_return.= '?client_id='.$this->sina_api_id;
                break;
                //请求人人网code地址
                case 'RenRen':
                    $str_return = 'https://graph.renren.com/oauth/authorize';
                    $str_return.= '?client_id='.$this->renren_api_id;
                break;
                case 'WX':
                    $_SESSION['wx_rand'] = rand();
                    $str_return = 'https://open.weixin.qq.com/connect/qrconnect';
                    $str_return.= '?appid='.$this->wx_api_id.'&scope=snsapi_login&state='.$_SESSION['wx_rand'];
                break;				
            }
            $str_return.= '&response_type=code';
            $url = '/Home/User/getToken';

            if($status === false){
                $url = '/Wap/User/getToken/';
            }
			$ary_shop_data = D('SysConfig')->getCfgByModule('GY_SHOP',1);
			$tmp_url = 'http://'.$_SERVER['HTTP_HOST'].$url;
			if(!empty($ary_shop_data['GY_SHOP_HOST'])){
				$url = trim($ary_shop_data['GY_SHOP_HOST'],'/').$url;
			}
            $redirect_uri = urlencode($url);
            $str_return.= '&redirect_uri='.$redirect_uri;
        
        }else{
            $str_return = '/'.WEB_ENTRY;
        }
        return $str_return;
    }
    
    /**
     * 获取第三方token数组 获取第三方信息
     * 
     * @parme string code 第三方请求密匙
     * @since stage 1.0
     * @author Joe <qianyijun@guanyusoft.com>
     * @date 2013-1-16
     */
    public function getThdRequestUrl($code=''){
        $Communications = new Communications();
        $ary_result['grant_type'] = 'authorization_code';
		$ary_shop_data = D('SysConfig')->getCfgByModule('GY_SHOP',1);
		$tmp_url = 'http://'.$_SERVER['HTTP_HOST'].'/Home/User/getToken/';
		if(!empty($ary_shop_data['GY_SHOP_HOST'])){
			$url = trim($ary_shop_data['GY_SHOP_HOST'],'/').'/Home/User/getToken/';
		}
        $ary_result['redirect_uri'] = urlencode($url);
        $ary_result['code'] = $code;
        switch($_SESSION['str_type']){
            //根据返回的腾讯code请求token地址
            case 'QQ':
                $str_url = 'https://graph.qq.com/oauth2.0/token';
                $ary_result['client_id'] = $this->qq_api_id;
                $ary_result['client_secret'] = $this->qq_aip_key;
                $str_url .= '?1=1';
                foreach($ary_result as $key=>$value){
                    $str_url .= '&'.$key.'='.$value;
                }
                $return_token = $Communications->httpGetRequest($str_url,$ary_result);
                $ary_return = $this->getThdQQ($return_token);
                
            break;
            //根据返回的新浪code请求token地址
            case 'Sina':
                $str_url = 'https://api.weibo.com/oauth2/access_token';
                $ary_result['client_id'] = $this->sina_api_id;
                $ary_result['client_secret'] = $this->sina_api_key;
                $str_url .= '?1=1';
                foreach($ary_result as $key=>$value){
                    $str_url .= '&'.$key.'='.$value;
                }
                $return_token = $Communications->httpPostRequest($str_url,$ary_result);
               
                $ary_return = $this->getThdSina($return_token);
            break;
            //根据返回的人人code请求token地址
            case 'RenRen':
                $ary_result['url'] = 'https://graph.renren.com/oauth/token';
                $ary_result['client_id'] = $this->renren_api_id;
                $ary_result['client_secret'] = $this->renren_api_key;
                $ary_result['redirect_uri'] = 'http://'.$_SERVER['HTTP_HOST'].'/thdtrust_get_token.html';
            break;
            //根据返回的微信code请求token地址
            case 'WX':
                $str_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
                $ary_result['appid'] = $this->wx_api_id;
                $ary_result['secret'] = $this->wx_api_key;
                $str_url .= '?1=1';
                foreach($ary_result as $key=>$value){
                    $str_url .= '&'.$key.'='.$value;
                }
                $return_token = $Communications->httpGetRequest($str_url,$ary_result);
                $ary_return = $this->getThdWX($return_token);
                
            break;			
        }
        return $ary_return;
    }

    /**
     * 根据腾讯返回的token信息进行解析
     * 返回openid 腾讯用户信息
     * 
     * @parme $str_token
     * @since stage 1.0
     * @author Joe <qianyijun@piaoxiao2.com>
     * @date 2013-1-17
     */
    public function getThdQQ($str_token){
        $ary_tmp_token = explode('&',$str_token);
        $str_token = substr($ary_tmp_token[0],13);
        $ary_return['open_token'] = $str_token;
        //获取appid
        $Communications = new Communications();
        $str_tmp_openid = $Communications->httpGetRequest('https://graph.qq.com/oauth2.0/me?access_token='.$str_token);//,array('access_token'=>$str_token)
        $ary_openid = json_decode(substr($str_tmp_openid,10,-3),true);
        
        //第三方用户ID
        $openid = $ary_openid['openid'];
        $ary_return['open_id'] = $openid;
        //拼接数组，获取第三方用户信息
        $str_thd_user_url = 'https://graph.qq.com/user/get_user_info';
        $ary_thd_user['access_token'] = $str_token;
        $ary_thd_user['oauth_consumer_key'] = $this->qq_api_id;
        $ary_thd_user['openid'] = $openid;
        $ary_thd_user['format'] = 'json';
        $str_param_ary = array();
        foreach($ary_thd_user as $key=>$value){
            $tmp_ary = $key.'='.$value;
            $str_param_ary[] = $tmp_ary;
        }
        $str_thd_user_url .= '?'.implode('&', $str_param_ary);
        $ary_thd_info = json_decode($Communications->httpGetRequest($str_thd_user_url,$ary_thd_user),true);
        $ary_return['user_info'] = $ary_thd_info;
        return $ary_return;
    }
    
    /**
     * 根据第三方新浪返回的token获取新浪用户信息
     *
     * @parme $str_token
     * @since stage 1.0
     * @author Joe <qianyijun@piaoxiao2.com>
     * @date 2013-1-17
     */
    public function getThdSina($str_token){
        $ary_thd_sina = json_decode($str_token,true);
        
        //根据返回的token和openid获取新浪用户基本信息
        $ary_thd_user['access_token'] = $ary_thd_sina['access_token'];
        $ary_thd_user['uid'] = $ary_thd_sina['uid'];
        $ary_url = 'https://api.weibo.com/2/users/show.json?access_token='.$ary_thd_sina['access_token'].'&uid='.$ary_thd_sina['uid'];
        $ary_sina_user_info = json_decode(file_get_contents($ary_url),true);
        $ary_return['open_token'] = $ary_thd_sina['access_token'];
        $ary_return['open_id'] = $ary_thd_sina['uid'];
        if($ary_sina_user_info['gender'] == 'm'){
            $gender = '男';
        }else{
            $gender = '女';
        }
        $ary_return['user_info'] = array('nickname'=>$ary_sina_user_info['screen_name'],
                                         'gender'=>$gender);
        return $ary_return;
    }
    
    /**
     * 根据第三方人人网返回的token获取新浪用户信息
     *
     * @parme $str_token
     * @since stage 1.0
     * @author Joe <qianyijun@piaoxiao2.com>
     * @date 2013-1-17
     */
    public function getThdRenRen($str_token){
        $ary_thd_renren = json_decode($str_token,true);
        $ary_return['open_token'] = $ary_thd_renren['access_token'];
        $ary_return['open_id'] = $ary_thd_renren['user']['id'];
        $ary_return['user_info'] = array('nickname'=>$ary_thd_renren['user']['name']);
        return $ary_return;
    }

    /**
     * 根据腾讯返回的token信息进行解析
     * 返回openid 腾讯用户信息
     * 
     * @parme $str_token = { 
     * "access_token":"ACCESS_TOKEN", 
     * "expires_in":7200, 
     * "refresh_token":"REFRESH_TOKEN",
     * "openid":"OPENID", 
     * "scope":"SCOPE" 
     *  }
     * @since stage 2.0
     * @author Hcaijin 
     * @date 2015-05-27
     */
    public function getThdWX($str_token){
        $ary_tmp_token = json_decode($str_token,true);
        $Communications = new Communications();
        //检验授权凭证（access_token）是否有效 https://api.weixin.qq.com/sns/auth?access_token=ACCESS_TOKEN&openid=OPENID
        $res_auth = $Communications->httpGetRequest('https://api.weixin.qq.com/sns/auth?access_token='.$ary_tmp_token['access_token'].'&openid='.$ary_tmp_token['openid']);
        $ary_auth = json_decode($res_auth,true);
        if($ary_auth['errmsg']!='ok' && $ary_auth['errcode']!='0'){
            //检验失败，刷新或续期access_token使用
            $res_refresh = $Communications->httpGetRequest('https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$this->wx_api_id.'&grant_type=refresh_token&refresh_token='.$ary_tmp_token['refresh_token']);
            $ary_tmp_token = json_decode($res_refresh,true);
        }

        //第三方用户ID
        $openid = $ary_tmp_token['openid'];
        //拼接数组，获取第三方用户信息
        $str_thd_user_url = 'https://api.weixin.qq.com/sns/userinfo';
        $ary_thd_user['access_token'] = $ary_tmp_token['access_token'];
        $ary_thd_user['openid'] = $openid;
        $str_param_ary = array();
        foreach($ary_thd_user as $key=>$value){
            $tmp_ary = $key.'='.$value;
            $str_param_ary[] = $tmp_ary;
        }
        $str_thd_user_url .= '?'.implode('&', $str_param_ary);
        $ary_thd_info = json_decode($Communications->httpGetRequest($str_thd_user_url,$ary_thd_user),true);
        $ary_return['user_info'] = $ary_thd_info;
        $ary_return['open_token'] = $ary_tmp_token['access_token'];
        $ary_return['open_id'] = $openid;
        return $ary_return;
    }
	
}