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
class ThdtrustAction extends HomeAction {

    private $qq_api_id = '101364477';
    private $qq_aip_key = '6635a27b01beb0ba2ca348e6d40ea0b7';
    private $sina_api_id = '1965392831';
    private $sina_api_key = '13b7f313c04162af728c5cff9cf7a0c9';
    private $renre_api_id = 'c12d7e8b57d547f48cd9c3af194d20eb';
    private $renre_api_key = 'ec294548f8b045f1872351b7d0cc4828';
    
    /**
     * 获取第三方信任登录地址页面
     *
     * @parme string str_type 请求类型 QQ,RenRen,Sina
     * @since 2013-07-29
     * @author Joe <qianyijun@guanyusoft.com>
     * @date 2013-1-16
     */
    public function getThdCodeUrl($str_type){
        if($str_type == 'QQ' || $str_type == 'Sina' || $str_type == 'RenRen'){
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
                    $str_return.= '?client_id='.$this->renre_api_id;
                break;
            }
            $str_return.= '&response_type=code';
            $str_return.= '&redirect_uri='.urlencode('http://'.$_SERVER['HTTP_HOST'].'/thdtrust_getToken.html');
        
        }else{
            $str_return = '/'.WEB_ENTRY;
        }
        return $str_return;
    }
    
    /**
     * 获取第三方token数组
     * 
     * @parme string type 请求类型
     * @parme string key 第三方请求密匙
     * @since stage 1.0
     * @author Joe <qianyijun@piaoxiao2.com>
     * @date 2013-1-16
     */
    public function getThdRequestUrl($str_type,$key=''){
        switch($str_type){
            //根据返回的腾讯code请求token地址
            case 'QQ':
                $return['url'] = 'https://graph.qq.com/oauth2.0/token';
                $return['client_id'] = $this->qq_api_id;
                $return['client_secret'] = $this->qq_aip_key;
                $return['redirect_uri'] = 'http://'.$_SERVER['HTTP_HOST'];
            break;
            //根据返回的新浪code请求token地址
            case 'Sina':
                $return['url'] = 'https://api.weibo.com/oauth2/access_token';
                $return['client_id'] = $this->sina_api_id;
                $return['client_secret'] = $this->sina_api_key;
                $return['redirect_uri'] = 'http://'.$_SERVER['HTTP_HOST'];
            break;
            //根据返回的人人code请求token地址
            case 'RenRen':
                $return['url'] = 'https://graph.renren.com/oauth/token';
                $return['client_id'] = $this->renre_api_id;
                $return['client_secret'] = $this->renre_api_key;
                $return['redirect_uri'] = 'http://'.$_SERVER['HTTP_HOST'].'/thdtrust_get_token.html';
            break;
        }
        $return['code'] = $key;
        $return['grant_type'] = 'authorization_code';
        return $return;
    }
    
    /**
     * 获取第三方信息
     * 
     * @parme code 
     * @since stage 1.0
     * @author Joe <qianyijun@piaoxiao2.com>
     * @return 用户token，openid，第三方用户基本信息
     * @date 2013-1-16
     */
    public function getThdUserInfo($code){
        $ary_result = $this->getThdRequestUrl($_SESSION['str_type'],$code);
        $str_url = $ary_result['url'];
        unset($ary_result['url']);
        $str_url .= '?1=1';
        foreach($ary_result as $key=>$value){
            $str_url .= '&'.$key.'='.$value;
        }
        $str_token = makeRequest($str_url,$ary_result);
        switch($_SESSION['str_type']){
            case 'QQ':
                $ary_return = $this->getThdQQ($str_token);
            break;
            case 'Sina':
                $ary_return = $this->getThdSina($str_token);
            break;
            case 'RenRen':
                $ary_return = $this->getThdRenRen($str_token);
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
        $str_tmp_openid = makeRequest('https://graph.qq.com/oauth2.0/me?access_token='.$str_token);//,array('access_token'=>$str_token)
        $ary_openid = json_decode(substr($str_tmp_openid,10,-3),true);
        //第三方用户ID
        $openid = $ary_openid['openid'];
        $ary_return['open_id'] = $openid;
        //拼接数组，获取第三方用户信息
        $str_thd_user_url = 'https://graph.qq.com/user/get_user_info';
        $ary_thd_user['access_token'] = $str_token;
        $ary_thd_user['oauth_consumer_key'] = $this->qq_api_id;
        $ary_thd_user['openid'] = $openid;
        $str_param_ary = array();
        foreach($ary_thd_user as $key=>$value){
            $tmp_ary = $key.'='.$value;
            $str_param_ary[] = $tmp_ary;
        }
        $str_thd_user_url .= '?'.implode('&', $str_param_ary);
        $ary_thd_info = json_decode(makeRequest($str_thd_user_url,$ary_thd_user),true);
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
}