<?php

/**
 * 后台开放存储服务 OSS控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.4.5.1
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2014-04-02
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class TopOssAction extends AdminAction{

    /**
     * 控制器初始化
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-02
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 开放存储服务设置');
    }

    /**
     * 默认控制器
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-02
     */
    public function index(){
        $this->redirect(U('Admin/TopOss/pageSet'));
    }

    /**
     * 后台开放存储服务设置
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-02
     */
    public function pageSet(){
		$this->getSubNav(8, 1, 40);
        $info = D('SysConfig')->getOssCfg();
        $this->assign($info);
        $str_method = explode('::',__METHOD__);$this->display($str_method[1]);
    }

    /**
     * 保存开放存储服务配置信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-02
     */
    public function doSet(){
        $data = $this->_get();
        /*
        if(empty($data['GY_OSS_BUCKET_NAME']) && empty($data['GY_OSS_ACCESS_ID']) && empty($data['GY_OSS_ACCESS_KEY'])){
        	$this->error('必填信息不能为空');
        }*/
        $SysSeting = D('SysConfig');
        if(
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_ON', $data['GY_OSS_ON'], '是否开启OSS上传') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_AUTO_ON', $data['GY_OSS_AUTO_ON'], '是否开启自动上传') &&
			$SysSeting->setConfig('GY_OSS', 'GY_OSS_THUMB_ON', $data['GY_OSS_THUMB_ON'], '是否上传缩略图') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_BUCKET_NAME', $data['GY_OSS_BUCKET_NAME'], 'OSS NAME') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_ACCESS_ID', $data['GY_OSS_ACCESS_ID'], 'OSS Access ID') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_PIC_URL', $data['GY_OSS_PIC_URL'], 'OSS PIC URL') &&
			$SysSeting->setConfig('GY_OSS', 'GY_OSS_CNAME_URL', $data['GY_OSS_CNAME_URL'], 'OSS CNAME URL') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OSS_ACCESS_KEY', $data['GY_OSS_ACCESS_KEY'], 'OSS Access Key') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OTHER_ON', $data['GY_OTHER_ON'], '是否开启其他上传') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OTHER_IP', $data['GY_OTHER_IP'], '其他服务器IP地址') &&
            $SysSeting->setConfig('GY_OSS', 'GY_OTHER_DOMAIN', $data['GY_OTHER_DOMAIN'], '服务器域名') &&
            $SysSeting->setConfig('GY_OSS', 'GY_TPL_IP', $data['GY_TPL_IP'], '模板地址') && 
			$SysSeting->setConfig('GY_OSS', 'GY_STATE_URL1', $data['GY_STATE_URL1'], '静态资源域名1') &&
			$SysSeting->setConfig('GY_OSS', 'GY_STATE_URL2', $data['GY_STATE_URL2'], '静态资源域名2') &&
			$SysSeting->setConfig('GY_OSS', 'GY_STATE_URL3', $data['GY_STATE_URL3'], '静态资源域名3') &&		$SysSeting->setConfig('GY_OSS', 'GY_STATE_URL4', $data['GY_STATE_URL4'], '静态资源域名4') &&	$SysSeting->setConfig('GY_OSS', 'GY_QN_ON', $data['GY_QN_ON'], '是否开启七牛上传') && 
			$SysSeting->setConfig('GY_OSS', 'GY_QN_BUCKET_NAME', $data['GY_QN_BUCKET_NAME'], '空间名称') && 
			$SysSeting->setConfig('GY_OSS', 'GY_QN_ACCESS_KEY', $data['GY_QN_ACCESS_KEY'], '七牛AK') && 
			$SysSeting->setConfig('GY_OSS', 'GY_QN_SECRECT_KEY', $data['GY_QN_SECRECT_KEY'], '七牛SK') && 		
			$SysSeting->setConfig('GY_OSS', 'GY_QN_DOMAIN', $data['GY_QN_DOMAIN'], '七牛域名')
        ){	
			D("SysConfig")->deleteCfgByModule('GY_OSS');
			$_SESSION['OSS'] = NULL;
            $this->success('保存成功');
			
        }else{
            $this->error('保存失败');
        }
    }
}