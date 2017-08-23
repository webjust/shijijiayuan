<?php

/**
 * 后台第三方授权登录设置
 *
 * @subpackage Thdlogin
 * @package Action
 * @stage 7.0
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-8-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdloginAction extends AdminAction {
    /**
     * 控制器初始化
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-01-23
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('后台第三方授权登录设置'));
    }
    
    /**
     * 默认控制器
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-01-22
     */
    public function index() {
        $this->redirect(U('Admin/Thdlogin/pageSet'));
    }
    
    /**
     * 授权登录设置页面
     *
     */
    public function pageSet(){
        $this->getSubNav(6, 2, 40);
        $data = array();
        $config = D('SysConfig');
        $data = $config->getConfigs("THD_LOGIN");
        if(!empty($data) && is_array($data)){
            $status = array();
            $ary_data = array();
            if(!empty($data['QQ_ID']['sc_value']) || !empty($data['QQ_KEY']['sc_value'])){
                $status['qq'] = '1';
            }else{
                $status['qq'] = '0';
            }
            $ary_data['qqid'] = $data['QQ_ID']['sc_value'];
            $ary_data['qqkey'] = $data['QQ_KEY']['sc_value'];
            if(!empty($data['SINA_ID']['sc_value']) || !empty($data['SINA_KEY']['sc_value'])){
                $status['sina'] = '1';
            }else{
                $status['sina'] = '0';
            }
            $ary_data['sinaid'] = $data['SINA_ID']['sc_value'];
            $ary_data['sinakey'] = $data['SINA_KEY']['sc_value'];
            if(!empty($data['WANGWANG_ID']['sc_value']) || !empty($data['WANGWANG_KEY']['sc_value'])){
                $status['wangwang'] = '1';
            }else{
                $status['wangwang'] = '0';
            }
            $ary_data['wangwangid'] = $data['WANGWANG_ID']['sc_value'];
            $ary_data['wangwangkey'] = $data['WANGWANG_KEY']['sc_value'];
            if(!empty($data['RENREN_ID']['sc_value']) || !empty($data['RENREN_KEY']['sc_value'])){
                $status['renren'] = '1';
            }else{
                $status['renren'] = '0';
            }
            $ary_data['renrenid'] = $data['RENREN_ID']['sc_value'];
            $ary_data['renrenkey'] = $data['RENREN_KEY']['sc_value'];
            if(!empty($data['TQQ_ID']['sc_value']) || !empty($data['TQQ_KEY']['sc_value'])){
                $status['tqq'] = '1';
            }else{
                $status['tqq'] = '0';
            }
            $ary_data['tqqid'] = $data['TQQ_ID']['sc_value'];
            $ary_data['tqqkey'] = $data['TQQ_KEY']['sc_value'];
			//微信
            if(!empty($data['WX_ID']['sc_value']) || !empty($data['WX_KEY']['sc_value'])){
                $status['wx'] = '1';
            }else{
                $status['wx'] = '0';
            }
            $ary_data['wxid'] = $data['WX_ID']['sc_value'];
            $ary_data['wxkey'] = $data['WX_KEY']['sc_value'];
            $str_status = json_encode($status);
            $str_data = json_encode($ary_data);
            if($config->setConfig('THDLOGIN',"THDSTATUS",$str_status,"第三方登录开关") && $config->setConfig('THDLOGIN',"THDDATA",$str_data,"第三方登录KEY及Secret")){
                $config->where(array('sc_module'=>'THD_LOGIN'))->delete();
            }
        }
        $logindata = array_merge($config->getCfg('THDLOGIN','THDSTATUS','','第三方登录开关'),
                         $config->getCfg('THDLOGIN','THDDATA','','第三方登录KEY及Secret'));
        $ary_status = json_decode($logindata['THDSTATUS']['sc_value'],TRUE);
        $arr_data = json_decode($logindata['THDDATA']['sc_value'],TRUE);
        $this->assign($ary_status);
        $this->assign($arr_data);
        $this->assign($data);
        $this->display();
    }
    
    /**
     * 执行配置第三方授权登录信息
     */
    public function doAdd(){
        $config = D('SysConfig');
        $data = $this->_post();
        $str_status = json_encode($data['thdlogin']['status']);
        $str_data = json_encode($data['thdlogin']['data']);
        if(FALSE !== $config->setConfig('THDLOGIN',"THDSTATUS",$str_status,"第三方登录开关") && FALSE !== $config->setConfig('THDLOGIN',"THDDATA",$str_data,"第三方登录KEY及Secret")){
            $this->success('操作成功');
        }else{
            $this->error('数据有误，请重试');
        }
    }
    

}   