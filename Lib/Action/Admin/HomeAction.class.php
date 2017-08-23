<?php

/**
 * 后台官网运营默认控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-01-06
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class HomeAction extends AdminAction {

    protected $dirpath = '';

    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件		
        $this->wapdir = C('WAP_TPL_DIR');
        $this->appdir = C('APP_TPL_DIR');
        $this->setTitle(' - '.L('MENU1_3'));
    }
    /**
     * 后台默认控制器默认页面
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index() {
        $this->redirect(U('Admin/Notice/index'));
    }

    /**
     * 官网基本信息设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-05-06
     */
    public function pageSetting() {
        $this->getSubNav(2, 3, 10);
        $ary_data = D('SysConfig')->getCfgByModule('GY_SHOP');
		$ary_data['GY_SHOP_LOGO'] = D('QnPic')->picToQn($ary_data['GY_SHOP_LOGO']);
		//生成二维码图片
		if(empty($ary_data['GY_SHOP_QC_LOGO'])){
			$ary_data['GY_SHOP_QC_LOGO'] = $this->doCreateQcPic();	
		}else{
			$ary_data['GY_SHOP_QC_LOGO'] = D('QnPic')->picToQn($ary_data['GY_SHOP_QC_LOGO']);
		}
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 生成二维码图片
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-29
     */
    public function doCreateQcPic() {
		//二维码图片不存在重新生成
		 @mkdir('./Public/Uploads/' . CI_SN . '/images');
		 $file_name  = '/Public/Uploads/' . CI_SN . '/images/'.date('YmdHis').'.png';
		 require_once './Public/Lib/phpqrcode/phpqrcode.php';
		 $c = "http://" . $_SERVER["SERVER_NAME"] . $int_port.'/';
		 QRcode::png($c,FXINC.$file_name);
		 D('SysConfig')->setConfig('GY_SHOP', 'GY_SHOP_QC_LOGO', $file_name, '店铺二维码LOGO');		
		 $pic_url = D('QnPic')->picToQn($file_name);		
		return $pic_url;
	}	
    /**
     * 修改官网基本信息设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-05-06
     */
    public function doSet(){
        $ary_data = $this->_post();
//        echo '<pre>';print_r($ary_data);die;
        if($ary_data['GY_SHOP_SERVER_PHONE']){
            $RegExp  = '/^(\(((010)|(021)|(0\d{3,4}))\)( ?)([0-9]{7,8}))|400(-\d{3,4}){2}|((010|021|0\d{3,4}))([- ]{1,2})([0-9]{7,8})$/A';
            if(!preg_match($RegExp,$ary_data['GY_SHOP_SERVER_PHONE'])){
                $this->error('请检查客服电话的格式');
            }
        }
		/**
	    if($_FILES['logopic']['error']==0){
			 $img_path = '/Public/Uploads/' . CI_SN . '/'.'other/'.date('Ymd').'/';
	         if (!is_dir(APP_PATH .$img_path)) {
            	//如果目录不存在，则创建之
             	mkdir(APP_PATH .$img_path, 0777, 1);
        	 }
             //生成本地分销地址
            $imge_url = $img_path . 'logo.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $imge_url;
            if(move_uploaded_file($_FILES['logopic']['tmp_name'],$img_save_path)){
				$ary_data['GY_SHOP_LOGO'] = $imge_url;
            }
		}**/
        $SysSeting = D('SysConfig');
        if(
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_OPEN', $ary_data['GY_SHOP_OPEN'], '店铺开关') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_MUST_LOGIN', $ary_data['GY_MUST_LOGIN'], '必须登录') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_TYPE', $ary_data['GY_SHOP_TYPE'], '店铺类型') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_TITLE', $ary_data['GY_SHOP_TITLE'], '店铺名称') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_HOST', $ary_data['GY_SHOP_HOST'], '店铺域名') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_ICP', $ary_data['GY_SHOP_ICP'], 'ICP备案') &&
            $SysSeting->setConfig('GY_SHOP', 'GY_SHOP_CODE', htmlspecialchars($ary_data['GY_SHOP_CODE']), '网站统计代码')&&
			$SysSeting->setConfig('GY_SHOP', 'GY_SHOP_ONLINE_START', trim($ary_data['GY_SHOP_ONLINE_START']), '客服在线时间开始')&&
			$SysSeting->setConfig('GY_SHOP', 'GY_SHOP_ONLINE_END', trim($ary_data['GY_SHOP_ONLINE_END']), '客服在线时间结束')&&
            $SysSeting->setConfig('GY_SHOP','GY_SHOP_SERVER_PHONE',$ary_data['GY_SHOP_SERVER_PHONE'],'APP客服电话') &&
            $SysSeting->setConfig('GY_SHOP','GY_IS_FOREIGN',$ary_data['GY_IS_FOREIGN'],'外汇支持开关')
		){
			$GY_SHOP_LOGO_0 = D('ViewGoods')->ReplaceItemPicReal($ary_data['GY_SHOP_LOGO_0']);
			$GY_SHOP_LOGO_1 = D('ViewGoods')->ReplaceItemPicReal($ary_data['GY_SHOP_LOGO_1']);
			$ary_data['GY_SHOP_LOGO_0'] = $GY_SHOP_LOGO_0;
			$ary_data['GY_SHOP_LOGO_1'] = $GY_SHOP_LOGO_1;
        	if(!empty($ary_data['GY_SHOP_LOGO_0'])){
        		$SysSeting->setConfig('GY_SHOP', 'GY_SHOP_LOGO', $ary_data['GY_SHOP_LOGO_0'], '店铺LOGO');
        	}
			if(!empty($ary_data['GY_SHOP_LOGO_1'])){
        		$SysSeting->setConfig('GY_SHOP', 'GY_WAP_LOGO', $ary_data['GY_SHOP_LOGO_1'], '微商城LOGO');
        	}
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"修改官网基本信息设置",serialize($ary_data)));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

     /**
     * 删除店铺LOGO
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2013-07-17
     */
    public function delLogoPic() {
    	$bool_res = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_LOGO'))
    	->data(array('sc_value'=>'','sc_update_time'=>date('Y-m-d H:i:s')))
    	->save();
    	if($bool_res){
    		$this->success('删除店铺LOGO成功');
    	}else{
    		$this->error('删除店铺LOGO失败');
    	}
    }
	
	 /**
     * 删除微商城LOGO
     * @author Wanguigin <zhuwenwei@guanyisoft.com>
     * @date 2015-10-08
     */
    public function delWapPic() {
    	$bool_res = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_WAP_LOGO'))
    	->data(array('sc_value'=>'','sc_update_time'=>date('Y-m-d H:i:s')))
    	->save();
    	if($bool_res){
    		$this->success('删除微商城LOGO成功');
    	}else{
    		$this->error('删除微商城LOGO失败');
    	}
    }
	/**
     * 删除店铺二维码LOGO
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2015-06-29
     */
    public function delQrPic() {
    	$bool_res = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_QC_LOGO'))
    	->data(array('sc_value'=>'','sc_update_time'=>date('Y-m-d H:i:s')))
    	->save();
    	if($bool_res){
    		$this->success('删除店铺二维码LOGO成功');
    	}else{
    		$this->error('删除店铺二维码LOGO失败');
    	}
    }
	

    /**
     * 后台注册协议
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-02-21
     */
    public function pageRegister() {
        $this->getSubNav(2, 3, 120);
        $ary_data = D('SysConfig')->getCfgByModule('GY_REGISTER_CONFIG');
        $ary_data['content'] = '';
        if (!empty($ary_data) && is_array($ary_data)) {
            $ary_data['content'] = $ary_data['REGISTER'];
        }
		$ary_data['content'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['content']);
        $this->assign('data', $ary_data);
        $this->display();
    }

    /**
     * 添加/编辑注册协议
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-21
     */
    public function doAddRegister() {
        $ary_post = $this->_post();
        $SysSeting = D('SysConfig');
        $module = "GY_REGISTER_CONFIG";
        $key = "REGISTER";
        $desc = "注册协议";
        if (!empty($ary_post) && is_array($ary_post)) {
            $ary_post['set_content'] = _ReplaceItemDescPicDomain($ary_post['set_content']);			
            $ary_res = $SysSeting->setConfig($module, $key, $ary_post['set_content'], $desc);
            if (FALSE !== $ary_res) {
                $this->success('操作成功', U('Admin/Home/pageRegister'));
            } else {
                $this->error('操作失败', U('Admin/Home/pageRegister'));
            }
        } else {
            $this->error('注册协议内容为空', U('Admin/Home/pageRegister'));
        }
    }

    /**
     * 添加/编辑网站暂停营业公告
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-27
     */
    public function pageClose() {
        $this->getSubNav(2, 3, 110);
        $ary_data = D('SysConfig')->getCfgByModule('GY_WEB_CONFIG');
		$ary_data['CONTENT'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['CONTENT']);
        $this->assign("data", $ary_data);
        $this->display();
    }

    /**
     * 处理暂停营业公告
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2012-2-27
     */
    public function doSetClose() {
        $ary_post = $this->_post();
        $SysSeting = D('SysConfig');
        if (FALSE !== $ary_post['status']) {
            $ary_post['set_content'] = _ReplaceItemDescPicDomain($ary_post['set_content']);			
            $status = $SysSeting->setConfig('GY_WEB_CONFIG', 'STATUS', $ary_post['status'], '暂停营业状态');
			if($ary_post['status'] == 0){
				$str_status = '正常营业';
			}else{
				$str_status = '暂停营业';
			}
            $content = $SysSeting->setConfig('GY_WEB_CONFIG', 'CONTENT', $ary_post['set_content'], '暂停营业内容');
            if (false !== $status && false !== $content) {
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"暂停营业设置",'暂停营业状态:'.$$ary_post['status'].'-'.$str_status));
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $this->error('请选择当前状态');
        }
    }

    /**
     * 模板管理
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-01
     * http://service.ecdrp.com/index.php?m=Template&a=download&ti_sn=xxxx&client_sn=xxxx&sign=md5(ci_sn+ti_sn+ci_id)
     */
    public function pageTpl() {
    
        $this->getSubNav(2, 3, 20);
        $ary_request = $this->_request();
        $ary_request['tabs'] = empty($ary_request['tabs']) ? "mytpl" : $ary_request['tabs'];
        $ary_data = array();
//        echo $ary_request['tabs'];die;
        switch ($ary_request['tabs']) {
            case 'pcfreetpl':
                if(SAAS_ON == true){
                    $ary_data = $this->getFreeTplList(0);
                }
                break;
            case 'wapfreetpl':
                if(SAAS_ON == true){
                    $ary_data = $this->getFreeTplList(1);
                }
                break;
            case 'mytpl':
                $ary_data = $this->getMyTplList();
                break;
            case 'drafts':
                $ary_data = $this->getDraftsList();
                break;
            case 'mywaptpl':
                $ary_data = $this->getMyWapTplList();
                break;
            case 'myapptpl':
                $ary_data = $this->getMyAppTplList();
                break;
            
        }
        //dump($ary_data);exit;
//       echo "<pre>";print_r($ary_data);exit;
        $this->assign("filter", $ary_request);
        $this->assign("data", $ary_data);
        $this->display($ary_request['tabs']);
    }
    /**
        *模板回收站
     * @author  Pooh<zhaozhicheng@guanyisoft.com>
     * @date 2015-10-20
     **/
    public function pageBinTpl(){
        $this->getSubNav(2, 3, 160);
        $ary_request = $this->_request();
        $ary_request['tabs'] = empty($ary_request['tabs']) ? "myBintpl" : $ary_request['tabs'];

        $ary_data = array();
//        echo $ary_request['tabs'];die;
        switch ($ary_request['tabs']) {
            case 'myBintpl':
                $ary_data = $this->getMyBinTplList();
                break;
            case 'myBinwaptpl':
                $ary_data = $this->getMyBinWapTplList();
                break;
            case 'myBinapptpl':
                $ary_data = $this->getMyBinAppTplList();
                break;
        }
        $this->assign("filter", $ary_request);
        $this->assign("data", $ary_data);

        $this->display($ary_request['tabs']);
    }

    /**
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-07
     */
    public function getMyTplList() {
        $path = FXINC . '/Public/Tpl/' . CI_SN . "/";
        $dirs = $this->scan_dir($path);
        $result = array();
        if (!empty($dirs) && is_array($dirs)) {
//            echo "<pre>";print_r($dirs);exit;
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(wap)$/i", $vald) && !preg_match("/^(backup_)/i", $vald) && !preg_match("/^(app)$/i", $vald)){
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' => $vald))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $ary_data['ti_name'];
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . "/".$vald.'/layout.jpg';
                    }else{
						//数据库里模板不存在
						$result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . "/".$vald.'/layout.jpg';
					}
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
//        echo "<pre>";print_r($result);exit;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_DEFAULT']);
        return $result;
    }

    /**
     *@author Pooh<zhaozhicheng@guanyisoft.com>
     *@date 2015-10-20
     **/
    public function getMyBinTplList(){
        $path = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/";
        $FileUtil = new FileUtil();
        $res= $FileUtil->createDir($path);
        if($res == false){
            $this->error("回收站文件创建失败");
        }
        $dirs = $this->scan_dirs($path);
        $result = array();
        if (!empty($dirs) && is_array($dirs)) {
//            echo "<pre>";print_r($dirs);exit;
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(wap)$/i", $vald) && !preg_match("/^(backup_)/i", $vald) && !preg_match("/^(app)$/i", $vald)){
                    $ary_vald = explode('_',$vald);
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' => $ary_vald[0]))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $vald;
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'.$vald.'/layout.jpg';
                    }else{
                        //数据库里模板不存在
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'.$vald.'/layout.jpg';
                    }
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_DEFAULT']);
        return $result;
    }

    /**
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     */
    public function getMyWapTplList() {
	    $wap = C('WAP_TPL_DIR');
		if(!$wap) {
			$this->error("WAP模板目录不能为空！");
		}
        $path = FXINC . '/Public/Tpl/' . CI_SN . '/'. $wap .'/';
        $dirs = $this->MyWap_dir($path);
//        echo "<pre>";print_r($dirs);exit;
        $result = array();
        if(empty($dirs) || !$dirs){
            $this->error('对不起，您还没有模板');
        }
        if (!empty($dirs) && is_array($dirs)) {
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(backup_)/i", $vald)){
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' => $vald))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $ary_data['ti_name'];
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/'. $wap. "/".$vald.'/layout.jpg';
                    }else{
						//数据库里模板不存在
						$result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/'. $wap. "/".$vald.'/layout.jpg';
					}
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
//        echo "<pre>";print_r($result);exit;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_WAP_DEFAULT']);
        return $result;
    }

    /**
        * @author Pooh<zhaozhicheng@guanyisoft.com>
        *@date  2015-10-20
    **/
    public function getMyBinWapTplList(){
        $wap = C('WAP_TPL_DIR');
        $path = FXINC . '/Public/Tpl/Temp/' . CI_SN . '/' . $wap .'/';
        $FileUtil = new FileUtil();
        $res= $FileUtil->createDir($path);
        if($res == false){
            $this->error("回收站文件创建失败");
        }
        $dirs = $this->MyWap_dir($path);
//        echo "<pre>";print_r($dirs);exit;
        $result = array();
        if (!empty($dirs) && is_array($dirs)) {
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(backup_)/i", $vald)){
                    $ary_vald = explode('_',$vald);
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' =>$ary_vald[0],'ti_type'=>1))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $vald;
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'. $wap. "/".$vald.'/layout.jpg';
                    }else{
                        //数据库里模板不存在
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'. $wap. "/".$vald.'/layout.jpg';
                    }
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
//        echo "<pre>";print_r($result);exit;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_WAP_DEFAULT']);
        return $result;
    }

    /**
     * @author huhaiwei<huhaiwei@guanyisoft.com>
     * @date 2015-01-26
     */
    public function getMyAppTplList(){
        $app = C('APP_TPL_DIR');
        if(!$app) {
            $this->error("APP模板目录不能为空！");
        }
        $path = FXINC . '/Public/Tpl/' . CI_SN . '/'. $app .'/';
        $dirs = $this->MyWap_dir($path);
        $result = array();
        if(empty($dirs) || !$dirs){
            $this->error('对不起，您还没有模板');
        }
        if (!empty($dirs) && is_array($dirs)) {
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(backup_)/i", $vald)){
                    $ary_vald = explode('_',$vald);
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' =>$ary_vald[0],'ti_type'=>2))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $vald;
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/'. $app. "/".$vald.'/layout.jpg';
                    }else{
						//数据库里模板不存在
						$result[$keyd]['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/'. $app . "/".$vald.'/layout.jpg';
					}
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_APP_DEFAULT']);
        return $result;
    }

    /**
        * @author Pooh<zhaozhicheng@guanyisoft.com>
        * @date  2015-10-20
     **/
    public function getMyBinAppTplList(){
        $app = C('APP_TPL_DIR');
        $path = FXINC . '/Public/Tpl/Temp/' . CI_SN . '/'. $app .'/';
        $FileUtil = new FileUtil();
        $res= $FileUtil->createDir($path);
        if($res == false){
            $this->error("回收站文件创建失败");
        }
        $dirs = $this->MyWap_dir($path);
        $result = array();
        if (!empty($dirs) && is_array($dirs)) {
            foreach ($dirs as $keyd => $vald) {
                if(!preg_match("/^(preview_)/i", $vald) && !preg_match("/^(.svn)/i", $vald) && !preg_match("/^(backup_)/i", $vald)){
                    $ary_data = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_dir' => $vald))->find();
                    if (!empty($ary_data) && is_array($ary_data)) {
                        $result[$keyd]['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                        $result[$keyd]['ti_name'] = $ary_data['ti_name'];
                        $result[$keyd]['ti_id'] = $ary_data['ti_id'];
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'. $app . "/".$vald.'/layout.jpg';
                    }else{
                        //数据库里模板不存在
                        $result[$keyd]['ti_thumbnail'] = '/Public/Tpl/Temp/'. CI_SN . '/'. $app . "/".$vald.'/layout.jpg';
                    }
                    $result[$keyd]['ti_dir'] = $vald;
                }

            }
        }
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $this->assign("template", $ary_api_conf['GY_TEMPLATE_APP_DEFAULT']);
        return $result;
    }


    /**
     * @param string $path    模板目录
     * @return array
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    function scan_dir($path = './') {
        $path = opendir($path);
        $array = array();
        while (false !== ($filename = readdir($path))) {
            if(file_exists('./Public/Tpl/'.CI_SN.'/'.$filename.'/index.html')){
                $filename != '.' && $filename != '..' && $array[] = $filename;
            }
        }
        closedir($path);
        return $array;
    }

    /**
     * @param string $path    回收站模板目录
     * @return array
     * @author Pooh
     * @date 2015-10-20
     */
    function scan_dirs($path = './') {
        $path = opendir($path);
        $array = array();
        while (false !== ($filename = readdir($path))) {
            if(file_exists('./Public/Tpl/Temp/'.CI_SN.'/'.$filename.'/index.html')){
                $filename != '.' && $filename != '..' && $array[] = $filename;
            }
        }
        closedir($path);
        return $array;
    }

    /**
     * 获取中心化模板列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-4-2
     * http://service.ecdrp.com/Api/Download/template/?ti_sn=xxxx&client_sn=xxxx&sign=md5(ci_sn+ti_sn)
     */
    public function getFreeTplList($ti_category = 0) {
        $freetplinfo = M('TemplateInfo', C("GY_PREFIX"), 'DB_CENTER');
        if($ti_category == 1){//如果点击是wap免费模板 并且没有开通免费模板 返回空数组
            $domain = $_SERVER['SERVER_NAME'];
            $client_info = M("client_info", C("GY_PREFIX"), 'DB_CENTER')->join('gy_client_domain_name on  gy_client_domain_name.ci_id = gy_client_info.ci_id')->where(array("gy_client_domain_name.cbi_domain_name"=>$domain,'gy_client_info.is_wap_template'=>1))->find();
            if(empty($client_info)){//手机端没有开通
                $this->assign("is_wap_lock", 1);
            }
        }
        //$config = C("TMPL_PARSE_STRING");
        $ary_where = array();
        $ary_where['ti_type'] = '0';
        $ary_where['ti_on_sale'] = '1';
        $ary_where['ti_category'] = $ti_category;
        $count = $freetplinfo->where($ary_where)->count();
//        echo "<pre>";print_r($freetplinfo->getLastSql());exit;
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $ary_data = $freetplinfo->where($ary_where)->limit($obj_page->firstRow, $obj_page->listRows)->select();
        if (!empty($ary_data) && is_array($ary_data)) {
            foreach ($ary_data as $keyda => $valda) {
                $ary_data[$keyda]['cisn'] = CI_SN;
                $ary_data[$keyda]['tisn'] = $valda['ti_sn'];
                $arrtpl = M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_sn' => $valda['ti_sn']))->find();
                if (!empty($arrtpl) && is_array($arrtpl)) {
                    $ary_data[$keyda]['local'] = '1';
                } else {
                    $ary_data[$keyda]['local'] = '0';
                }
                $ary_data[$keyda]['sign'] = md5(CI_SN . $valda['ti_sn']);
            }
        }
        $this->assign("page", $page);
        return $ary_data;
    }

    /**
     * 下载模板操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-02
     * http://service.ecdrp.com/Api/Download/template/?ti_sn=xxxx&client_sn=xxxx&sign=md5(ci_sn+ti_sn)
     */
    public function getDownloadTpl() {
        @set_time_limit(0);
        $ary_get = $this->_get();
        if (empty($ary_get['ti_sn'])) {
            $this->error("缺少参数 TI_SN");
        }
        if (empty($ary_get['client_sn'])) {
            $this->error("缺少参数 client_sn");
        }
        if (empty($ary_get['sign'])) {
            $this->error("缺少参数 sign");
        }
        $config = C("TMPL_PARSE_STRING");
        $saas = new GyApi();
        $ary_param = array(
            'client_sn' => $ary_get['client_sn'],
            'ti_sn' => $ary_get['ti_sn']
        );
        $ary_saas = $saas->getTemplateDownload($ary_param);
        if(FALSE === $ary_saas['status']){
            $this->error($ary_saas['error_msg']);exit;
        }
        $zipname = basename($ary_saas['data']['template_url']);
        $tmp_zip = FXINC . '/Public/Tpl/' . CI_SN . "/" . $zipname;
        //判断数pc模板下载 还是wap模板下载
        $ti_category = 0;
        if( empty($ary_get['ti_category'])|| $ary_get['ti_category']==0 ){
            $tmp_zip = FXINC . '/Public/Tpl/' . CI_SN . "/" . $zipname;
        }elseif($ary_get['ti_category'] == 1){//wap 路径
            $ti_category = 1;
            $wap = C('WAP_TPL_DIR');
            $tmp_zip = FXINC . '/Public/Tpl/' . CI_SN . "/".$wap.'/' . $zipname;
        }

        if (@file_put_contents($tmp_zip, @file_get_contents($ary_saas['data']['template_url']))) {
            $remplate = new PclZip($tmp_zip);
            $zipContent = $remplate->listContent();
            $this->mkDirs(trim($zipContent[0]['filename'],"/"),$ti_category);
            $ary_where = array();
            $ary_where['ti_type'] = '0';
            $ary_where['ti_on_sale'] = '1';
            $ary_where['ti_sn'] = $ary_get['ti_sn'];
            $ary_data = M('Template_info', C("GY_PREFIX"), 'DB_CENTER')->where($ary_where)->find();
            if( $ti_category == 0 ){
                $v_result_list = $remplate->extract(PCLZIP_OPT_PATH, FXINC . '/Public/Tpl/' . CI_SN . "/", PCLZIP_OPT_REPLACE_NEWER);//解压缩
            }elseif($ti_category==1){//wap 路径
                $wap = C('WAP_TPL_DIR');
                $v_result_list = $remplate->extract(PCLZIP_OPT_PATH, FXINC . '/Public/Tpl/' . CI_SN . "/".$wap.'/', PCLZIP_OPT_REPLACE_NEWER);//解压缩
            }
            if($remplate->extract() == 0){
                die("Error : ".$remplate->errorInfo(true));
            }
            @unlink($tmp_zip);
            
            if ($v_result_list == 0) {
                $this->error("下载失败");
            } else {
                $freetplinfo = M('Template_info', C("GY_PREFIX"), 'DB_CENTER');
                $saas->templateDownloadCallback($ary_param);
                $ary_where = array();
                $ary_where['ti_type'] = '0';
                $ary_where['ti_on_sale'] = '1';
                $ary_where['ti_sn'] = $ary_get['ti_sn'];
                $ary_data = $freetplinfo->where($ary_where)->find();
                if (!empty($ary_data) && is_array($ary_data)) {
                    $local = array();
                    $local['ti_type'] = $ary_data['ti_category'];//0-pc端  1-wap端  2-APP端
                    $local['ti_name'] = $ary_data['ti_name'];
                    $local['ti_sn'] = $ary_data['ti_sn'];
                    $local['ti_thumbnail'] = $ary_data['ti_thumbnail'];
                    $local['ti_other_pic'] = $ary_data['ti_other_pic'];
                    $local['ti_dir'] = $this->dirpath;
                    $local['ti_local'] = '0';
                    $local['ti_desc'] = $ary_data['ti_desc'];
                    $local['ti_create_time'] = date("Y-m-d H:i:s");
                    M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->add($local);
                }
                $this->success("下载成功");
            }
        } else {
            $this->error("模板不存在或数据有误");
        }
    }

    /**
     * 创建模板目录
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-4-7
     */
    public function mkDirs($filename,$ti_category=0) {
        $rootPath = FXINC . '/Public/Tpl/' . CI_SN . "/";
        if($ti_category == 1){
            $rootPath = FXINC . '/Public/Tpl/' . CI_SN . "/".C('WAP_TPL_DIR').'/';
        }
        if (!is_dir($rootPath . $filename)) {
            mkdir($rootPath  . $filename,0777);
            $this->dirpath = $filename;
        } else {
            $this->mkDirs($filename);
        }
    }

    /**
     * 模板使用
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function doTplStatus() {
        $ary_post = $this->_post();
        if (!empty($ary_post['tp']) && isset($ary_post['tp'])) {
            $SysSeting = D('SysConfig');
            if ($SysSeting->setConfig('GY_TEMPLATE_DEFAULT', 'GY_TEMPLATE_DEFAULT', $ary_post['tp'], '设置默认模板')) {
				D('SysConfig')->deleteCfgByModule('GY_TEMPLATE_DEFAULT',1);
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"设置默认模板",'设置默认模板为:'.$ary_post['tp']));
				//清楚缓存
				//清空runtime
				$runtime_url = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/~runtime.php';
				if(file_exists($runtime_url)){
					unlink($runtime_url);
				}
				//删除当前模板首页缓存
				$path_url1 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/')).'.html';
				$path_url2 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/Home/Index/index')).'.html';				
				if(file_exists($path_url1)){
					unlink($path_url1);
				}
				if(file_exists($path_url2)){
					unlink($path_url2);
				}		
				make_fsockopen('/Script/Batch/delFile');				
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error("模板不存在，请检查");
        }
    }

    /**
     * 获取模板里的HTML文件
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function doEditTpl() {
        $this->getSubNav(2, 3, 20);
        $ary_request = $this->_request();
		$ary_request['file'] = trim($ary_request['file']);
//		dump($ary_request);die();
        $ary_request['options'] = empty($ary_request['options']) ? "Set" : $ary_request['options'];
		if(!isset($ary_request['type'])){
			$ary_request['type'] =strtolower($ary_request['options']);
		}
        $str = "getEditTpl" . $ary_request['options'];
        $title['str'] = $ary_request['options'];
        $title['data'] = array(
            array('value' => "Set", 'name' => '设 置'),
            array('value' => "Temm", 'name' => '模板管理'),
            array('value' => "CSS", 'name' => 'CSS文件'),
            array('value' => "Images", 'name' => 'Images'),
            array('value' => "JS", 'name' => 'JS文件'),
            array('value' => "Define", 'name' => '自定义页面'),
            array('value' => "Backup", 'name' => '备份的模板')
        );
        if($ary_request['tabs'] == 'mywaptpl' ){
           $ary_request['new_dir'] = $this->wapdir.'/'.$ary_request['dir'];
        }
        if($ary_request['tabs'] == 'myapptpl' ){
            $ary_request['new_dir'] = $this->appdir.'/'.$ary_request['dir'];
        }
		//删除文件
		if($ary_request['change'] == 'delete' && $ary_request['type'] == 'define'){
			$file = "./Public/Tpl/" . CI_SN . "/" . $ary_request['dir'] .'/define/'.$ary_request['file'];
			if(file_exists($file)){
				@unlink($file);
				unset($ary_request['file']);
			}
		}
		
        $ary_data = $this->$str($ary_request);
		//获取页面信息
		$ary_data = $this->getTempData($ary_data);
///       echo'<pre>';print_r($title);exit;
        $this->assign("filter", $ary_request);
        $this->assign("title", $title);
        $this->assign("data", $ary_data);
		//dump($_SESSION);die();
//        echo $ary_request['options'];die;
		$sys_obj = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM');
		$host = $sys_obj->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
		$this->assign('hostUrl',$host['sc_value'].'Home/Index/shoHtml/html/');
				//dump($ary_data);die();
        $this->display($ary_request['options']);
    }

    /**
     * 获取当前模板页
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-12-22
     */
    public function getTempData($ary_data) {
		foreach($ary_data as $key=>$tmp_data){
			switch($tmp_data['value']){
				case 'index':
				$ary_data[$key]['desc'] = '首页';
				break;
				case '404':
				$ary_data[$key]['desc'] = '404页面';
				break;
				case 'ReportList':
				$ary_data[$key]['desc'] = '试用报告展示';
				break;
				case 'advice':
				$ary_data[$key]['desc'] = '商品详情购买咨询';
				break;
				case 'article_content':
				$ary_data[$key]['desc'] = '文章详情';
				break;
				case 'article_list':
				$ary_data[$key]['desc'] = '文章列表';
				break;
				case 'bulkDetail':
				$ary_data[$key]['desc'] = '团购详情';
				break;
				case 'bulkDetailSku':
				$ary_data[$key]['desc'] = '团购详情规格展示';
				break;
				case 'bulkList':
				$ary_data[$key]['desc'] = '团购列表';
				break;
				case 'coll_goods':
				$ary_data[$key]['desc'] = '自由推荐商品展示';
				break;
				case 'collocationSelectGoods':
				$ary_data[$key]['desc'] = '自由搭配多规格商品加入购物车弹窗';
				break;
				case 'collocation_column':
				$ary_data[$key]['desc'] = '自由搭配已选择商品展示';
				break;
				case 'comment':
				$ary_data[$key]['desc'] = '商品详情页商品评论';
				break;
				case 'coupon':
				$ary_data[$key]['desc'] = '抢优惠券页面';
				break;
				case 'customerCart':
				$ary_data[$key]['desc'] = '头部购物车商品展示';
				break;
				case 'doBulkLogin':
				$ary_data[$key]['desc'] = '购物车弹窗登陆';
				break;
				case 'findPwd':
				$ary_data[$key]['desc'] = '找回密码';
				break;
				case 'footer':
				$ary_data[$key]['desc'] = '全局页面底部';
				break;
				case 'free_recommend':
				$ary_data[$key]['desc'] = '自由搭配列表';
				break;
				case 'goodsCart':
				$ary_data[$key]['desc'] = '将商品列表中的商品加入购物车';
				break;
				case 'goodsDetailSku':
				$ary_data[$key]['desc'] = '商品规格展示';
				break;
				case 'header':
				$ary_data[$key]['desc'] = '全局页面头部';
				break;
				case 'integralDetail':
				$ary_data[$key]['desc'] = '积分兑换详情';
				break;
				case 'integralDetailSku':
				$ary_data[$key]['desc'] = '积分兑换详情规格展示';
				break;
				case 'integralList':
				$ary_data[$key]['desc'] = '积分兑换列表';
				break;
				case 'login':
				$ary_data[$key]['desc'] = '登录页';	
				break;
				case 'lottery':
				$ary_data[$key]['desc'] = '抽奖页';
				break;
				case 'lottery_list':
				$ary_data[$key]['desc'] = '中奖纪录列表';
				break;				
				case 'pageFoget':
				$ary_data[$key]['desc'] = '忘记密码';
				break;
				case 'point':
				$ary_data[$key]['desc'] = '积分兑换列表';	
				break;
				case 'pointSelectGoods':
				$ary_data[$key]['desc'] = '积分兑换页选择规格';
				break;
				case 'presaleDetail':
				$ary_data[$key]['desc'] = '预售详情';	
				break;
				case 'presaleDetailSku':
				$ary_data[$key]['desc'] = '预售详情规格展示';
				break;
				case 'presaleList':
				$ary_data[$key]['desc'] = '预售列表';	
				break;
				case 'productDetails':
				$ary_data[$key]['desc'] = '商品详情';
				break;
				case 'productList':
				$ary_data[$key]['desc'] = '商品列表';	
				break;
				case 'preview_index':
				$ary_data[$key]['desc'] = '上一次可视化编辑的首页备份数据';	
				break;
				case 'registeRagreement':
				$ary_data[$key]['desc'] = '注册协议';	
				break;
				case 'buyrecord':
				$ary_data[$key]['desc'] = '商品详情页购买记录';	
				break;		
				case 'compare':
				$ary_data[$key]['desc'] = '商品详情页对比';	
				break;					
				case 'comparelist':
				$ary_data[$key]['desc'] = '商品对比页';	
				break;					
				case 'register':
				$ary_data[$key]['desc'] = '注册页';	
				break;
				case 'relate_goods':
				$ary_data[$key]['desc'] = '商品详情页关联商品';	
				break;
				case 'selectColl':
				$ary_data[$key]['desc'] = '将商品列表中的商品加入购物车';	
				break;
				case 'selectCollGoods':
				$ary_data[$key]['desc'] = '将商品列表中的商品加入购物车';	
				break;
				case 'showMemberInfo':
				$ary_data[$key]['desc'] = '头部展示会员登录后的会员信息';	
				break;
				case 'spikeDetail':
				$ary_data[$key]['desc'] = '秒杀商品详情';	
				break;
				case 'spikeDetailSku':
				$ary_data[$key]['desc'] = '秒杀详情规格选择';
				break;				
				case 'spikeList':
				$ary_data[$key]['desc'] = '秒杀列表';
				break;				
				case 'try':
				$ary_data[$key]['desc'] = '试用首页';	
				break;
				case 'tryDetails':
				$ary_data[$key]['desc'] = '试用详情页';	
				break;				
				case 'tryAddress':
				$ary_data[$key]['desc'] = '试用收货地址页面';	
				break;
				case 'tryReport':
				$ary_data[$key]['desc'] = '填写试用报告';
				break;				
				case 'ucenterFooter':
				$ary_data[$key]['desc'] = '会员中心全局底部';
				break;
				case 'ucenterHeader':
				$ary_data[$key]['desc'] = '会员中心全局头部';	
				break;
				case 'groupbuy':
				$ary_data[$key]['desc'] = '团购首页';	
				break;
				case 'groupbuyList':
				$ary_data[$key]['desc'] = '团购列表';
				break;
				case 'groupbuyDetails':
				$ary_data[$key]['desc'] = '团购详情';	
				break;
				default:
				break;
			}
		}
		return $ary_data;
	}	
    /**
     * 获取当前模板
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function getEditTplTemm($filter) {
        $type = "html";
        if(isset($filter['wapdir']) && $filter['wapdir']){
            $path = "./Public/Tpl/" . CI_SN . "/wap/" . $filter['dir'] . "/";
        }elseif(isset($filter['appdir']) && $filter['appdir']){
            $path = "./Public/Tpl/" . CI_SN . "/app/" . $filter['dir'] . "/";
        }else{
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/";
        }
        $files = $this->dirList($path, $type);
        $templates = array();
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                $filename = basename($file);
                $templates[$key]['value'] = substr($filename, 0, strrpos($filename, '.'));
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = $file;
                $templates[$key]['filesize'] = $this->byteFormat(filesize($file));
                $templates[$key]['filemtime'] = date("Y-m-d H:i:s", filemtime($file));
                $templates[$key]['ext'] = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename)));
            }
        }
        return $templates;
    }

    /**
     * 获取当前CSS
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function getEditTplCss($filter) {
        $type = "css";
        $filter['dir'] = empty($filter['new_dir']) ? $filter['dir'] :$filter['new_dir'];
        $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] .'/'. $type;
        if($filter['tabs'] == 'mywaptpl' && ($filter['options'] == 'CSS' || $filter['type'] == $type)){
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] .'/'. $type;
        }
        if($filter['tabs'] == 'myapptpl' && ($filter['options'] == 'CSS' || $filter['type'] == $type)){
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] .'/'. $type;
        }
		$aimDir = $path.'/customer.css';
		if(!file_exists($aimDir)){
			$fileUtil = new FileUtil();	
			$fileUtil->createFile($aimDir);
			file_put_contents($aimDir,'@charset "utf-8";');
		}
		$files = $this->dirList($path, $type);
		/**
		if($filter['showType'] == '1'){
			$files = $this->dirList($path, $type);
		}else{
			$files = array($aimDir);		
		}**/
        $templates = array();
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                $filename = basename($file);
                $templates[$key]['value'] = substr($filename, 0, strrpos($filename, '.'));
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = $file;
                $templates[$key]['filesize'] = $this->byteFormat(filesize($file));
                $templates[$key]['filemtime'] = date("Y-m-d H:i:s", filemtime($file));
                $templates[$key]['ext'] = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename)));
            }
            if(!empty($filter['file']) && isset($filter['file'])){
                $files = $path."/".$filter['file'];
                if (file_exists($files)) {
                    $ary_file = explode(".",$filter['file']);
                    //首先判断文件夹是否存在
                    $newDir = "preview1_" . $filter['dir'];
                    $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/".$filter['options'];
                    if(!is_dir($aimDir)){
                        $files = $path .'/';
                        for($i=0; $i<count($ary_file);$i++){
                            $files .= $ary_file[$i].'.';
                        }
                        $files = rtrim($files,'.');
                        $content = htmlspecialchars(file_get_contents($files));
                    }else{
                        $files = $aimDir .'/';
                        for($i=0; $i<count($ary_file);$i++){
                            $files .= $ary_file[$i].'.';
                        }
                        $files = rtrim($files,'.');
                        $content = htmlspecialchars(file_get_contents($files));
                    }
                    $this->assign('filename', $filename);
                    $this->assign('file', $filter['file']);
                    $this->assign("content",$content);
                } else {
                    $this->error("文件名不存在");
                }
            }else{
//                $files = $path."/".$templates[0]['filename'];
                $ary_file = explode(".",$templates[0]['filename']);
                //首先判断文件夹是否存在
                $newDir = "preview1_" . $filter['dir'];
                $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
                if(!is_dir($aimDir)){
                    $files = $path ."/";
                    for($i=0; $i<count($ary_file);$i++){
                        $files .= $ary_file[$i].'.';
                    }
                    $content = htmlspecialchars(file_get_contents($files));
                }else{
                    $files = $aimDir ."/";
                    for($i=0; $i<count($ary_file);$i++){
                        $files .= $ary_file[$i].'.';
                    }
                    $content = htmlspecialchars(file_get_contents($files));
                }
                $this->assign("content",$content);
                $this->assign("file",$templates[0]['filename']);
            }
        }
        return $templates;
    }

    /**
     * 获取模板中所有图片文件
     */
    public function getEditTplImages($filter){
        $filter['dir'] = empty($filter['new_dir']) ? $filter['dir'] :$filter['new_dir'];
        $type = "images";
        if(!empty($filter['folder']) && isset($filter['folder'])){
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/" . $type ."/" .$filter['folder']."/";
        }else{
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/" . $type ."/";
        }
        if($filter['detele']){
            $file=$path.$filter['file'];
            if(file_exists($file)){
                    is_dir($file) ? dir_delete($file) : unlink($file);
                    $this->success("删除成功");exit;
            }else{
                    $this->error("文件未找到或不存在");exit;
            }
        }
        $files = glob($path.'*');
        $folders=array();
        $templates = array();
        foreach($files as $key => $file) {
            $filename = basename($file);
            if(is_dir($file)){
                    $folders[$key]['filename'] = $filename;
                    $folders[$key]['filepath'] = $file;
                    $folders[$key]['ext'] = 'folder';
            }else{
                    $templates[$key]['filename'] = $filename;
                    $templates[$key]['filepath'] = $file;
                    if(!empty($filter['folder']) && isset($filter['folder'])){
                        $templates[$key]['webpath'] = "Public/Tpl/".CI_SN."/".$filter['dir']."/".$type."/".$filter['folder']."/".$filename;
                    }else{
                        $templates[$key]['webpath'] = "Public/Tpl/".CI_SN."/".$filter['dir']."/".$type."/".$filename;;
                    }
                    $templates[$key]['ext'] = strtolower(substr($filename,strrpos($filename, '.')-strlen($filename)+1));
                    if(!in_array($templates[$key]['ext'],array('gif','jpg','png','bmp'))) $templates[$key]['ico'] =1;
            }
        }
//        echo "<pre>";print_r($_SERVER);exit;
        $this->assign ( 'path',$path);
        $this->assign ( 'folders',$folders );
        $this->assign ( 'files',$templates );
        return $templates;
    }

    /**
     * 获取当前JS
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function getEditTplJs($filter) {
        $type = "js";
        $filter['dir'] = empty($filter['new_dir']) ? $filter['dir'] :$filter['new_dir'];
        $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/" . $type;
        if($filter['tabs'] == 'mywaptpl' && ($filter['options'] == 'JS' || $filter['type'] == $type)){
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] .'/'. $type;
        }
        if($filter['tabs'] == 'myapptpl' && ($filter['options'] == 'JS' || $filter['type'] == $type)){
            $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . '/'.$type;
        }
		$aimDir = $path.'/customer.js';
		if(!file_exists($aimDir)){
			$fileUtil = new FileUtil();	
			$fileUtil->createFile($aimDir);
		}
        $files = $this->dirList($path, $type);
        $templates = array();
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                $filename = basename($file);
                $templates[$key]['value'] = substr($filename, 0, strrpos($filename, '.'));
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = $file;
                $templates[$key]['filesize'] = $this->byteFormat(filesize($file));
                $templates[$key]['filemtime'] = date("Y-m-d H:i:s", filemtime($file));
                $templates[$key]['ext'] = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename)));
            }
            if(!empty($filter['file']) && isset($filter['file'])){
                $files = $path."/".$filter['file'];
                if (file_exists($files)) {
                    $ary_file = explode(".",$filter['file']);
                    //首先判断文件夹是否存在
                    $newDir = "preview1_" . $filter['dir'];
                    $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/". $type;
                    if(!is_dir($aimDir)){
                        $files = $path ."/";
                        for($i=0; $i<count($ary_file);$i++){
                            $files .= $ary_file[$i].'.';
                        }
                        $files = rtrim($files,'.');
                        $content = htmlspecialchars(file_get_contents($files));
                    }else{
                        $files = $aimDir ."/";
                        for($i=0; $i<count($ary_file);$i++){
                            $files .= $ary_file[$i].'.';
                        }
                        $files = rtrim($files,'.');
                        $content = htmlspecialchars(file_get_contents($files));
                    }
                    $this->assign('filename', $filename);
                    $this->assign('file', $filter['file']);
                    $this->assign("content",$content);
                } else {
                    $this->error("文件名不存在");
                }
            }else{
                $files = $path."/".$templates[0]['filename'];

                $ary_file = explode(".",$templates[0]['filename']);
                //首先判断文件夹是否存在
                $newDir = "preview1_" . $filter['dir'];
                $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/". $type;
                if(!is_dir($aimDir)){
                    $files = $path ."/";
                    for($i=0; $i<count($ary_file);$i++){
                        $files .= $ary_file[$i].'.';
                    }
                    $files = rtrim($files,'.');
                    $content = htmlspecialchars(file_get_contents($files));
                }else{
                    $files = $aimDir .'/';
                    for($i=0; $i<count($ary_file);$i++){
                        $files .= $ary_file[$i].'.';
                    }
                    $files = rtrim($files,'.');
                    $content = htmlspecialchars(file_get_contents($files));
                }
                $this->assign("content",$content);
                $this->assign("file",$templates[0]['filename']);
            }
        }
        //dump($templates);exit;
        return $templates;
    }

    /**
     * 自定义HTML
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-19
     * 获取到模版内的自定义HTML信息
     * @edit zuojianghua <zuojianghua@guanyisoft.com>
     * @date 2013-07-17
     */
    public function getEditTplDefine($filter){
        $type = "html";
        $path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/define";
        $files = $this->dirList($path, $type);
		
        $templates = array();
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                $filename = basename($file);
                $templates[$key]['value'] = substr($filename, 0, strrpos($filename, '.'));
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = $file;
                $templates[$key]['filesize'] = $this->byteFormat(filesize($file));
                $templates[$key]['filemtime'] = date("Y-m-d H:i:s", filemtime($file));
                $templates[$key]['ext'] = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename)));
            }
            if(!empty($filter['file']) && isset($filter['file'])){
                $files = $path."/".$filter['file'];
                if (file_exists($files)) {
                    $ary_file = explode(".",$filter['file']);
                    //首先判断文件夹是否存在
                   // $newDir = "preview_" . $filter['dir'];
				    $newDir = $filter['dir'];
                    $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
                    if(!is_dir($aimDir)){
                        $files = $path ."define/". $ary_file[0].".".$ary_file[1];
                        $content = htmlspecialchars(file_get_contents($file));
                    }else{
                        $files = $aimDir ."define/". $ary_file[0].".".$ary_file[1];
                        $content = htmlspecialchars(file_get_contents($files));
                    }
                    $this->assign('filename', $filename);
                    $this->assign('file', $filter['file']);
                    $this->assign("content",$content);
                } else {
                    $this->error("文件夹不存在");
                }
            }else{
                $files = $path."/".$templates[0]['filename'];

                $ary_file = explode(".",$templates[0]['filename']);
                //首先判断文件夹是否存在
                //$newDir = "preview_" . $filter['dir'];
				$newDir = $filter['dir'];
                $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
                if(!is_dir($aimDir)){
                    $files = $path ."define/". $ary_file[0].".".$ary_file[1];
                    $content = htmlspecialchars(file_get_contents($file));
                }else{
                    $files = $aimDir ."define/". $ary_file[0].".".$ary_file[1];
                    $content = htmlspecialchars(file_get_contents($files));
                }
                $this->assign("content",$content);
                $this->assign("file",$templates[0]['filename']);
            }
        }
        //dump($templates);exit;
        return $templates;
    }


    /**
     * 获取文件中内容
     * @param int 文件字节数
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function pageEditTplTemm() {
        $fileUtil = new FileUtil();
        $this->getSubNav(2, 3, 20);
        $ary_get = $this->_request();
        if (empty($ary_get['file'])) {
            $this->error("文件名不能为空");
        }
        $title['data'] = array(
            array('value' => "Set", 'name' => '设 置'),
            array('value' => "Temm", 'name' => '模板管理'),
            array('value' => "CSS", 'name' => 'CSS文件'),
            array('value' => "Images", 'name' => 'Images'),
            array('value' => "JS", 'name' => 'JS文件'),
            array('value' => "Define", 'name' => '自定义页面'),
            array('value' => "Backup", 'name' => '备份的模板')
        );
        $filename = $ary_get['file'];
        if ($ary_get['type']) {
            $type = $ary_get['type'];
        } else {
            $type = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename) + 1));
        }
        if($ary_get['tabs'] && $ary_get['tabs'] == 'mytpl'){
            $pathStr = "./Public/Tpl/" . CI_SN . "/" . $ary_get['dir'] . "/";
            $path = $type == 'html' ? $pathStr : $pathStr . $type . '/';
            $file = $path . $filename;
        }

        $wap = C('WAP_TPL_DIR');
        if($ary_get['tabs'] == 'mywaptpl' && !empty($wap)){
            $pathStr = "./Public/Tpl/" . CI_SN . "/" . $wap."/".$ary_get['dir'] . "/";
            $path = $type == 'html' ? $pathStr : $pathStr . $type . '/';
            $file = $path . $filename;
        }
        $app = C('APP_TPL_DIR');
		if($ary_get['dir'] == 'backup_android' || $ary_get['dir'] == 'backup_ios'){
			$ary_get['appdir'] = 1;
		}
        if($ary_get['tabs'] == 'myapptpl' && !empty($app)){
            $pathStr = "./Public/Tpl/" . CI_SN . "/" . $app."/".$ary_get['dir'] . "/";
            $path = $type == 'html' ? $pathStr : $pathStr . $type . '/';
            $file = $path . $filename;
        }
		//自定义页面
		if($ary_get['type'] == 'define'){
			$type = $ary_get['type'];
			$pathStr = "./Public/Tpl/" . CI_SN ."/".$ary_get['dir'] . "/";
            $path = $pathStr . $ary_get['type'] . '/';
            $file = $path . $filename;
		}
		$ary_get['content'] = RemovePhp($ary_get['content']);
        if($ary_get['submit']){
            //首先判断文件夹是否存在
            $newDir = "preview_" . $ary_get['dir'];
            $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
            if(!is_dir($aimDir)){
                $fileUtil->copyDir($pathStr, $aimDir);
            }
            if(file_exists($file)){
                file_put_contents($file,htmlspecialchars_decode(stripslashes($ary_get['content'])));
                $ary_file = explode(".",$filename);
                $files = $aimDir . $type ."/". $filename;
                if(file_exists($files)){
                    file_put_contents($files,htmlspecialchars_decode(stripslashes($ary_get['content'])));
                }
				/********************************************
				 * 判断是否是负载均衡服务器
				 *********************************************/
				if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
					if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
						//模板文件
						$com_obj = new Communications();
						$ary_request_data = array();
						$ary_request_data['upfile']="@".trim($_SERVER['DOCUMENT_ROOT'],"/").trim($file,".") ;
						$ary_request_data['file_path'] = $file;
						$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);
					
					}
				}
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"编辑模板",'编辑的文件为为:'.$ary_get['dir'].'下的'.$ary_get['file']));
				//文件备份起来
				$this->backupTemp($ary_get,$filename,1);
                $this->success("编辑成功");
            }else{

                $this->error("文件不存在");
            }
        }else if($ary_get['temporary']){
            if(file_exists($file)){
                $ary_file = explode(".",$filename);
                //首先判断文件夹是否存在
                $newDir = "preview_" . $ary_get['dir'];
                $aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";

                if(!is_dir($aimDir)){
                    $fileUtil->copyDir($pathStr, $aimDir);
                }
                if($type == 'html'){
                    $files = $aimDir . $filename;
                }else{
                    $files = $aimDir . $type ."/". $filename;
                }
                if(file_exists($files)){
                    file_put_contents($files,htmlspecialchars_decode(stripslashes($ary_get['content'])));
                }
				if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
					if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
						//$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_OTHER_IP'];
						//模板文件
						$com_obj = new Communications();
						$ary_request_data = array();
						$ary_request_data['upfile']="@".trim($_SERVER['DOCUMENT_ROOT'],"/").trim($files,".") ;
						$ary_request_data['file_path'] = $files;
						//$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);		
						$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);						
					}
				}					
                $this->success("创建文件成功");
            }else{
                $this->error("文件不存在");
            }
        }else if($ary_get['backup']){
            if(file_exists($file)){
				$this->backupTemp($ary_get,$filename);
				exit;
            }else{
                $this->error("文件不存在");
            }
        }else{
            if (file_exists($file)) {
                $ary_file = explode(".",$filename);
                //首先判断文件夹是否存在
               // $newDir = "preview_" . $ary_get['dir'];
                $newDir = $ary_get['dir'];
                //$aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
				if($ary_get['wapdir'] == 1){
					$aimDir = "./Public/Tpl/" . CI_SN . "/wap/" . $newDir . "/";
				}else{
					if($ary_get['appdir'] == 1){
						$aimDir = "./Public/Tpl/" . CI_SN . "/app/" . $newDir . "/";
					}else{
						$aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/";
					}
				}			
                if(!is_dir($aimDir)){
                    $files = $path . $ary_file[0].".".$ary_file[1];
                    $content = htmlspecialchars(file_get_contents($file));
                }else{
                    $files = $aimDir . $ary_file[0].".".$ary_file[1];
                    $content = htmlspecialchars(file_get_contents($files));
                }
				if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
					if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
						//模板文件
						$com_obj = new Communications();
						$ary_request_data = array();
						$ary_request_data['upfile']="@".trim($_SERVER['DOCUMENT_ROOT']).trim($aimDir,".") ;
						$ary_request_data['file_path'] = $aimDir;
						$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);
					}
				}					
                $this->assign('title', $title);
                $this->assign('filter', $ary_get);
                $this->assign('filename', $filename);
                $this->assign('file', $file);
                //echo'<pre>';print_r($ary_get);die;
                $this->assign('ary_get',$ary_get);
                $this->display();
                echo '<textarea id="contentbox" style="display:none;" >' . $content . '</textarea><script>$("#content").val($("#contentbox").val());</script>';
            } else {
                $this->error("文件名不存在");
            }
        }
    }
	
    /**
     * 备份文件
     * @param int 文件字节数
     * @author Wangguibin<Wangguibin@guanyisoft.com>
     * @date 2015-12-23
     */
	function backupTemp($ary_get,$filename,$type=0){	
		$fileUtil = new FileUtil();	
		if($ary_get['wapdir'] == 1){
			if($ary_get['type'] != 'html'){
				$pathStr = "./Public/Tpl/" . CI_SN . "/wap/" . $ary_get['dir'] . "/".$ary_get['type'].'/'.$filename;						
			}else{
				$pathStr = "./Public/Tpl/" . CI_SN . "/wap/" . $ary_get['dir'] . "/". $filename;						
			}
		}else{
			if($ary_get['appdir'] == 1){
				if($ary_get['type'] != 'html'){
					$pathStr = "./Public/Tpl/" . CI_SN . "/app/" . $ary_get['dir'] . "/".$ary_get['type'].'/'.$filename;	
				}else{
					$pathStr = "./Public/Tpl/" . CI_SN . "/app/" . $ary_get['dir'] . "/". $filename;							
				}
			}else{
				if($ary_get['type'] != 'html'){
					$pathStr = "./Public/Tpl/" . CI_SN . "/" . $ary_get['dir'] . "/".$ary_get['type'].'/'. $filename;
				}else{
					$pathStr = "./Public/Tpl/" . CI_SN . "/" . $ary_get['dir'] . "/". $filename;							
				}
			}
		}
		$ary_file = explode(".",$filename);
		//首先判断文件夹是否存在
		if($type == '1'){
			$newFile = date('Ymd').'/'."backup_".$ary_get['dir'].'_'.time().'_'.$filename;
		}else{
			$newFile = "backup_".$ary_get['dir'].'_'.time().'_'.$filename;
		}
		
		$newDir = "backup_" . $ary_get['dir'];
		if($ary_get['wapdir'] == 1){
			$aimDir = "./Public/Tpl/" . CI_SN . "/wap/" . $newDir . "/" . $newFile;
		}else{
			if($ary_get['appdir'] == 1){
				$aimDir = "./Public/Tpl/" . CI_SN . "/app/" . $newDir . "/" . $newFile;
			}else{
				$aimDir = "./Public/Tpl/" . CI_SN . "/" . $newDir . "/" . $newFile;
			}
		}
		if(!is_dir($aimDir)){
			$fileUtil->copyFile($pathStr, $aimDir);
		}
		if(file_exists($aimDir)){
			file_put_contents($aimDir,htmlspecialchars_decode(stripslashes($ary_get['content'])));
		}
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
				//模板文件
				$com_obj = new Communications();
				$ary_request_data = array();
				$ary_request_data['upfile']="@".trim($_SERVER['DOCUMENT_ROOT'],"/").trim($aimDir,".") ;
				$ary_request_data['file_path'] = $aimDir;
				$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);
			}
		}					
		$this->success("创建文件成功");
	}
    /**
     * 获取文件大小
     * @param int 文件字节数
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    function byteFormat($input) {
        $prefix_arr = array("B", "K", "M", "G", "T");
        $value = round($input);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }
        $return_str = round($value) . $prefix_arr[$i];
        return $return_str;
    }

    /**
     * 设置
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
	 * @update by Wangguibin 数据库模板文件不存在读取模板文件
	 * @update time 2014-06-12
     */
    public function getEditTplSet($filter) {
        $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
        if (!empty($filter['tid']) && isset($filter['tid'])) {
            $ary_data = $template->where(array('ti_id' => $filter['tid']))->find();
        }else{
			//数据库里模板不存在
			$ary_data['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . "/".$filter['dir'].'/layout.jpg';
		}
        return $ary_data;
    }

    /**
     * 校验模板名称是否存在
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function checkEditTplName() {
        $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
        $ary_get = $this->_get();
        if (!empty($ary_get['ti_name']) && isset($ary_get['ti_name'])) {
            $where = array();
            $where['ti_name'] = $ary_get['ti_name'];
            if (!empty($ary_get['tiid']) && isset($ary_get['tiid'])) {
                $where['ti_id'] = array('neq', $ary_get['tiid']);
            }
            $ary_data = $template->where($where)->find();
            if (!empty($ary_data) && is_array($ary_data)) {
                $this->ajaxReturn("模板名称已存在！");
            } else {
                $this->ajaxReturn(true);
            }
        } else {
            $this->ajaxReturn("模板名称不能为空");
        }
    }

    /**
     * 处理设置
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    public function doAddTpl() {
        $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
        $ary_post = $this->_post();
//        dump($ary_post);die;
        $photo = $_FILES['ti_thumbnail']['name'];
        if (!empty($photo)) {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();     // 实例化上传类
            $upload->maxSize = 3145728; // 设置附件上传大小
            $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                $upload->savePath = './Public/Tpl/' . CI_SN . '/wap/' . $ary_post['ti_dir'] . "/"; // 设置附件上传目录
            }else{
                $upload->savePath = './Public/Tpl/' . CI_SN . '/' . $ary_post['ti_dir'] . "/"; // 设置附件上传目录
            }
            if (!$upload->upload()) {// 上传错误提示错误信息
                $this->error($upload->getErrorMsg());
            } else {// 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                    $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/wap/' . $ary_post['ti_dir'] . "/" . $info[0]['savename'];
                }else{
                    $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/' . $ary_post['ti_dir'] . "/" . $info[0]['savename'];
                }
                $ary_post['ti_local'] = '1';
            }
        }
        if (!empty($ary_post['ti_id']) && isset($ary_post['ti_id'])) {
            $where = array();
            $where['ti_id'] = $ary_post['ti_id'];
            unset($ary_post['ti_id']);
            $FileUtil = new FileUtil();
            $rootpath = FXINC . '/Public/Tpl/' . CI_SN . "/";
            $rootwappath = FXINC . '/Public/Tpl/' . CI_SN . "/wap/";
            if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                $oldDir = $rootwappath.$ary_post['ti_dir'];
                $aimDir = $rootwappath.$ary_post['ti_name'];
            }else{
                $oldDir = $rootpath.$ary_post['ti_dir'];
                $aimDir = $rootpath.$ary_post['ti_name'];
            }
            $result = $FileUtil->copyDir($oldDir,$aimDir);
			
			if($result==true && $oldDir!=$aimDir){
				$FileUtil->unlinkDir($oldDir);
			}
            $ary_post['ti_update_time'] = date("Y-m-d H:i:s");
            $ary_post['ti_dir'] = $ary_post['ti_name'];
            if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/wap/' . $ary_post['ti_name'].'/layout.jpg';
            }else{
                $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/' . $ary_post['ti_name'].'/layout.jpg';
            }
            $ary_result = $template->where($where)->data($ary_post)->save();
            if (FALSE !== $ary_result) {
                $this->success("设置成功");
            } else {
                $this->error("设置失败");
            }
        } else {
            unset($ary_post['ti_id']);
            $FileUtil = new FileUtil();
            $rootpath = FXINC . '/Public/Tpl/' . CI_SN . "/";
            $rootwappath = FXINC . '/Public/Tpl/' . CI_SN . "/wap/";
            if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                $oldDir = $rootwappath.$ary_post['ti_dir'];
                $aimDir = $rootwappath.$ary_post['ti_name'];
            }else{
                $oldDir = $rootpath.$ary_post['ti_dir'];
                $aimDir = $rootpath.$ary_post['ti_name'];
            }
            $result = $FileUtil->copyDir($oldDir,$aimDir);

            $FileUtil->unlinkDir($oldDir);

            $ary_post['ti_update_time'] = date("Y-m-d H:i:s");
            $ary_post['ti_dir'] = $ary_post['ti_name'];
            if(isset($ary_post['wapdir']) && $ary_post['wapdir']){
                $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/wap/' . $ary_post['ti_name'].'/layout.jpg';
            }else{
                $ary_post['ti_thumbnail'] = '/Public/Tpl/' . CI_SN . '/' . $ary_post['ti_name'].'/layout.jpg';
            }
            $ary_post['ti_create_time'] = date("Y-m-d H:i:s");
            $ary_result = $template->add($ary_post);
            if (FALSE !== $ary_result) {
                $this->success("设置成功");
            } else {
                $this->error("设置失败");
            }
        }
    }

    /**
     * 获取文件夹中所有文件
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-03-26
     * @param string $path 路径
     * @param string $exts 待连接的字符串
     * @param array $list 返回的数组
     * @return array $list 返回的数组
     */
    public function dirList($path, $exts = '', $list = array()) {
        $path = $this->dirPath($path);

        $files = glob($path . '*');

        foreach ($files as $v) {
            if (!$exts || (preg_match("/\.($exts)/i", $v) && !preg_match("/\.(bak)/i", $v))) {
                $list[] = $v;
                if (is_dir($v)) {
                    $list = $this->dirList($v, $exts, $list);
                }
            }
        }
        return $list;
    }

    /**
     * 获取文件夹中所有文件
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-03-26
     * @param string $path 路径
     * @param string $exts 待连接的字符串
     * @param array $list 返回的数组
     * @return string 返回处理的路径
     */
    public function dirPath($path) {
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) != '/')
            $path = $path . '/';
        return $path;
    }

    /**
     * 删除图片
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-05-17
     */
    public function doDelImages(){
        $ary_del = $this->_post('imagesDel');
        $filter = $this->_get();
        if(!empty($ary_del) && is_array($ary_del)){
            $FileUtil = new FileUtil();
            $bln_result = true;
            if(is_array($ary_del)){
                foreach ($ary_del as $v) {
                    $del_path = APP_PATH . "Public/Uploads/" . CI_SN . str_replace('@@', '/', $v);$path = "./Public/Tpl/" . CI_SN . "/" . $filter['dir'] . "/";

                    if(is_dir($del_path)){
                        if(FALSE == $FileUtil->unlinkDir($del_path)){
                            $bln_result = false;
                        }
                    }else{
                        $del_path = APP_PATH  . str_replace('@@', '/', $v);
                        if(FALSE == $FileUtil->unlinkFile($del_path)){
                            $bln_result = false;
                        }
                    }
                }
            }
            if($bln_result){
                $this->success('删除成功');
            }else{
                $this->error('删除操作执行完成，可能有部分文件没有被删除!');
            }
        }else{
            $this->error('请选择需要删除的内容');
        }
    }

    /**
     * 回收站里删除pc模板
     * @author Pooh<zhaozhicheng@guanyisoft.com>
     * @date 2015-10-20
     */
    public function TemplateDelete(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $rootPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/";
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
                if (file_exists($path)) {
                    $status = $FileUtil->unlinkDir($path);
                    if (FALSE !== $status) {
                        if(!empty($ary_request['tid']) && isset($ary_request['tid'])){
                            M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_id'=>$ary_request['tid']))->delete();
                        }
                        $this->success("删除成功！");
                    } else {
                        $this->error("删除失败，请检查模板文件权限是否设置为可写！");
                    }
                } else {
                    $this->error("需要删除的模板文件不存在！");
                }
        }else{
            $this->error("请选择需要删除的模板文件");
        }
    }

    /**
     *  pc模板进入回收站
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-19
     **/
    public function TemplateBin(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT');
        $rootPath = FXINC . '/Public/Tpl/' . CI_SN . "/";
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/";
        $result= $FileUtil->createDir($binPath);
        if($result){
            if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
                $path = $rootPath.$ary_request['dir'];
                //进入回收站下的模板文件路径
                //判断模板目录有删除时间的了就不再加时间了
                $ary_result = preg_match('/^[A-Za-z]+[\_]{1}[0-9]+$/',$ary_request['dir']);
                if($ary_result){
                    $binmodulepath = $binPath.$ary_request['dir'];
                }else{
                    $binmodulepath = $binPath.$ary_request['dir']."_".date('YmdHis');
                }
                if($config['GY_TEMPLATE_DEFAULT'] != $ary_request['dir']){
                    if (file_exists($path)) {
                        $status = $FileUtil->moveDir($path,$binmodulepath);
                        if (FALSE !== $status) {

                            $this->success("移进回收站成功！");
                        } else {
                            $this->error("移进回收站失败，请检查模板文件权限是否设置为可写！");
                        }
                    } else {
                        $this->error("需要移进回收站的模板文件不存在！");
                    }
                }else{
                    $this->error("该模板已经被使用不可移进回收站！");
                }

            }else{
                $this->error("请选择需要移进回收站的模板文件");
            }
        }else{
                $this->error("回收站Bin文件创建失败！");
        }
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"模板删除","模板整理删除"));
    }

    /**
     *  pc模板还原
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-20
     **/
    public function restoreTpl(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $rootPath = FXINC . '/Public/Tpl/' . CI_SN . "/";
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/";
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
            //进入回收站下的模板文件路径
            $binmodulepath = $binPath.$ary_request['dir'];
               if (file_exists($binmodulepath)) {
                    $status = $FileUtil->moveDir($binmodulepath,$path);
                    if (FALSE !== $status) {
                        $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
                        $data['ti_name'] = $ary_request['dir'];
                        $data['ti_dir'] = $ary_request['dir'];
                        $data['ti_create_time'] = date('Y-m-d H:i:s');
                        $data['ti_thumbnail'] = '/Public/Tpl/' . CI_SN .  "/".$ary_request['dir'].'/layout.jpg';
                        $ary_result = $template->add($data);
                        $this->success("还原成功！");
                    }
               } else {
                    $this->error("需要还原的模板文件不存在！");
               }
        }else{
            $this->error("请选择需要还原的模板文件");
        }
    }

    /**
     *  wap模板还原
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-20
     **/
    public function restorewapTpl(){
        $wap = C('WAP_TPL_DIR');
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $rootPath = FXINC . '/Public/Tpl/' . CI_SN . '/'. $wap .'/';
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/wap/";
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
            //进入回收站下的模板文件路径
            $binmodulepath = $binPath.$ary_request['dir'];
            if (file_exists($binmodulepath)) {
                $status = $FileUtil->moveDir($binmodulepath,$path);
                if (FALSE !== $status) {
                    $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
                    $data['ti_name'] = $ary_request['dir'];
                    $data['ti_dir'] = $ary_request['dir'];
                    $data['ti_create_time'] = date('Y-m-d H:i:s');
                    $data['ti_thumbnail'] = '/Public/Tpl/' . CI_SN .  '/'.$wap.'/'.$ary_request['dir'].'/layout.jpg';
                    $ary_result = $template->add($data);
                    $this->success("还原成功！");
                }
            } else {
                $this->error("需要还原的模板文件不存在！");
            }
        }else{
            $this->error("请选择需要还原的模板文件");
        }
    }

    /**
     *  app模板还原
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-20
     **/
    public function restoreappTpl(){
        $app = C('APP_TPL_DIR');
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $rootPath = FXINC . '/Public/Tpl/' . CI_SN . '/'. $app .'/';
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/app/";
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
            //进入回收站下的模板文件路径
            $binmodulepath = $binPath.$ary_request['dir'];
            if (file_exists($binmodulepath)) {
                $status = $FileUtil->moveDir($binmodulepath,$path);
                if (FALSE !== $status) {
                    $template = M("Template", C("DB_PREFIX"), 'DB_CUSTOM');
                    $data['ti_name'] = $ary_request['dir'];
                    $data['ti_dir'] = $ary_request['dir'];
                    $data['ti_create_time'] = date('Y-m-d H:i:s');
                    $data['ti_thumbnail'] = '/Public/Tpl/' . CI_SN .  "/".$app.'/'.$ary_request['dir'].'/layout.jpg';
                    $ary_result = $template->add($data);
                    $this->success("还原成功！");
                }
            } else {
                $this->error("需要还原的模板文件不存在！");
            }
        }else{
            $this->error("请选择需要还原的模板文件");
        }
    }


	/**
     * 用户中心自定义样式设置
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-08
     */
    public function pageUcenterSkin(){
        $this->getSubNav(2, 3, 140);
        $ary_data = D('SysConfig')->getCfgByModule('GY_UCENTER_SKIN');
		$ary_data['ICON_PIC'] = D('QnPic')->picToQn($ary_data['ICON_PIC']);
		$ary_data['LEFT_PIC'] = D('QnPic')->picToQn($ary_data['LEFT_PIC']);
		$ary_data['NAVON_PIC'] = D('QnPic')->picToQn($ary_data['NAVON_PIC']);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 保存用户中心自定义图片样式
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-08
     */
    public function doUcenterSkin(){
        $ary_data = $this->_request();
        $SysSeting = D('SysConfig');
        if($ary_data['GY_UCENTER_SKIN_0']){
            $big_pic = $SysSeting->setConfig('GY_UCENTER_SKIN', 'ICON_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_UCENTER_SKIN_0'])), '图标图片');
            if(FALSE == $big_pic){
                $this->error('保存图标图片失败');
            }
        }
        if($ary_data['GY_UCENTER_SKIN_1']){
            $big_pic = $SysSeting->setConfig('GY_UCENTER_SKIN', 'LEFT_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_UCENTER_SKIN_1'])), '背景图片');
            if(FALSE == $big_pic){
                $this->error('保存背景图片失败');
            }
        }
        if($ary_data['GY_UCENTER_SKIN_2']){
            $big_pic = $SysSeting->setConfig('GY_UCENTER_SKIN', 'NAVON_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_UCENTER_SKIN_2'])), '选中图片');
            if(FALSE == $big_pic){
                $this->error('保存选中图片失败');
            }
        }
        $this->success('保存成功');
    }
	
	/**
     * 官网模板头部广告图片设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-06-03
     */
    public function pageTopAd() {
        $this->getSubNav(2, 3, 130);
        $ary_data = D('SysConfig')->getCfgByModule('GY_SHOP_TOP_AD');
		$ary_data['RIGHT_PIC'] = D('QnPic')->picToQn($ary_data['RIGHT_PIC']);
		$ary_data['BIG_PIC'] = D('QnPic')->picToQn($ary_data['BIG_PIC']);
		$ary_data['BOTTOM_PIC'] = D('QnPic')->picToQn($ary_data['BOTTOM_PIC']);
		$ary_data['LOGIN_PIC'] = D('QnPic')->picToQn($ary_data['LOGIN_PIC']);
		$ary_data['REGISTER_PIC'] = D('QnPic')->picToQn($ary_data['REGISTER_PIC']);
		$ary_data['SMALL_PIC'] = D('QnPic')->picToQn($ary_data['SMALL_PIC']);
        $this->assign($ary_data);
        $this->display();
    }
	
	/**
     * 保存官网模板头部广告图片设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-06-03
     */
    public function doTopAdSet() {
		$ary_data = $this->_request();
		$SysSeting = D('SysConfig');
		$state = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'STATE', intval($ary_data['GY_SHOP_TOP_AD_STATE']), '店铺广告使用状态');
		if(FALSE == $state){
			$this->error('保存状态失败');
		}
	
		$big_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'BIG_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_0'])), '大广告图片');
		if(FALSE == $big_pic){
			$this->error('保存大图片失败');
		}
		
		$big_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'BIG_PIC_URL', $ary_data['GY_SHOP_TOP_AD_0_URL'], '大广告图片链接地址');
		if(FALSE == $big_pic_url){
			$this->error('保存大图片地址失败');
		}			

		$small_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'SMALL_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_1'])), '小广告图片');
		if(FALSE == $small_pic){
			$this->error('保存小图片失败');
		}		

		$small_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'SMALL_PIC_URL', $ary_data['GY_SHOP_TOP_AD_1_URL'], '小广告图片链接地址');
		if(FALSE == $small_pic_url){
			$this->error('保存小图片地址失败');
		}				

		$right_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'RIGHT_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_2'])), '搜索右侧图片');
		if(FALSE == $right_pic){
			$this->error('保存搜索右侧图片失败');
		}		

		$right_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'RIGHT_PIC_URL',$ary_data['GY_SHOP_TOP_AD_2_URL'],'搜索右侧图片链接地址');
		if(FALSE == $right_pic_url){
			$this->error('保存搜索右侧图片地址失败');
		}				

		$login_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'LOGIN_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_3'])), '登陆页广告图片');
		if(FALSE == $login_pic){
			$this->error('保存登陆页广告图片失败');
		}		

		$login_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'LOGIN_PIC_URL', $ary_data['GY_SHOP_TOP_AD_3_URL'], '登陆页图片链接地址');
		if(FALSE == $login_pic_url){
			$this->error('保存登陆页图片链接地址失败');
		}				

		$reget = str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_4']);
		$register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'REGISTER_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_4'])), '注册页广告图片');
		if(FALSE == $register_pic){
			$this->error('保存注册页广告图片失败');
		}		

		$register_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'REGISTER_PIC_URL', $ary_data['GY_SHOP_TOP_AD_4_URL'], '注册页图片链接地址');
		if(FALSE == $register_pic_url){
			$this->error('保存注册页图片链接地址失败');
		}				

		$reget = str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_5']);
		$register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'BOTTOM_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_5'])), '页面底部广告图片');
		if(FALSE == $register_pic){
			$this->error('保存页面底部广告图片失败');
		}		

		$register_pic_url = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'BOTTOM_PIC_URL', $ary_data['GY_SHOP_TOP_AD_5_URL'], '页面底部广告图片链接地址');
		if(FALSE == $register_pic_url){
			$this->error('保存页面底部广告图片链接地址失败');
		}				
	
		$this->success('保存成功');
	}
	/**
     * 删除广告图片
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-12-03
     */   
    public function doTopAdDel() {
    	$key=$this->_post('key');  
    	$bool_res = D('SysConfig')->where(array('sc_key'=>$key))->save(array('sc_value'=>''));
    	if($bool_res){
			$this->ajaxReturn(array('status'=>'1','info'=>"删除图片成功"));exit;
    	}else{
			$this->ajaxReturn(array('status'=>'0','info'=>"删除图片失败"));exit;
    	}
    }
    /**
     * 获取备份的文件列表
     */
    public function getEditTplBackup($filter) {
		//dump($filter);die();
        //$type = "html";
        if(isset($filter['wapdir']) && $filter['wapdir']){
            $path = "./Public/Tpl/" . CI_SN . "/wap/" . "backup_". $filter['dir'] . "/";
        }else{
			if(isset($filter['appdir']) && $filter['appdir']){
				$path = "./Public/Tpl/" . CI_SN . "/app/" . "backup_". $filter['dir'] . "/";
			}else{
				$path = "./Public/Tpl/" . CI_SN . "/" . "backup_". $filter['dir'] . "/";
			}
        }
		$files = array();
        $files_html = $this->dirList($path, 'html');
		$files_css = $this->dirList($path, 'css');
		$files_js = $this->dirList($path, 'js');
		$files = array_merge($files_html,$files_css,$files_js);
		//dump($files);die();
        $templates = array();
        if (!empty($files) && is_array($files)) {
            foreach ($files as $key => $file) {
                $filename = basename($file);
                $templates[$key]['value'] = substr($filename, 0, strrpos($filename, '.'));
                $templates[$key]['filename'] = $filename;
                $templates[$key]['filepath'] = $file;
                $templates[$key]['filesize'] = $this->byteFormat(filesize($file));
                $templates[$key]['filemtime'] = date("Y-m-d H:i:s", filemtime($file));
                $templates[$key]['ext'] = strtolower(substr($filename, strrpos($filename, '.') - strlen($filename)));
            }
        }
        return $templates;
    }

    public function MyWap_dir($path = './'){
        $path = opendir($path);
        $array = array();
        if(empty($path)){
            return '';
        }
        while (false !== ($filename = readdir($path))) {
            $filename != '.' && $filename != '..' && $array[] = $filename;
        }
        closedir($path);
        return $array;

    }

    /**
     * App图片管理
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-03-03
     */
    public function appPicManage() {
        $this->getSubNav(2, 3, 150);
        $ary_data = D('SysConfig')->getCfgByModule('GY_SHOP_TOP_AD');
		$ary_data['APP_ICO_PIC'] = D('QnPic')->picToQn($ary_data['APP_ICO_PIC']);
		$ary_data['APP_LOGIN_PIC'] = D('QnPic')->picToQn($ary_data['APP_LOGIN_PIC']);
		$ary_data['APP_LOGO_PIC'] = D('QnPic')->picToQn($ary_data['APP_LOGO_PIC']);
		$ary_data['APP_REGISTER_PIC'] = D('QnPic')->picToQn($ary_data['APP_REGISTER_PIC']);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 保存App图片设置
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-03-03
     */
    public function doAppPic(){
        $ary_data = $this->_request();
        $SysSeting = D('SysConfig');
        if($ary_data['GY_SHOP_TOP_AD_1']){
            $register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'APP_REGISTER_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_1'])), 'App注册页面图片');
            if(FALSE == $register_pic){
                $this->error('保存App注册页图片失败');
            }
        }
        if($ary_data['GY_SHOP_TOP_AD_2']){
            $register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'APP_LOGIN_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_2'])), 'App登录页面图片');
            if(FALSE == $register_pic){
                $this->error('保存App登录页图片失败');
            }
        }
        if($ary_data['GY_SHOP_TOP_AD_3']){
            $register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'APP_ICO_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_3'])), 'App图标');
            if(FALSE == $register_pic){
                $this->error('保存App图标失败');
            }
        }
		/*
        if($ary_data['GY_SHOP_TOP_AD_4']){
            $register_pic = $SysSeting->setConfig('GY_SHOP_TOP_AD', 'APP_LOGO_PIC', D('ViewGoods')->ReplaceItemPicReal(str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_4'])), 'App的LOGO');
            if(FALSE == $register_pic){
                $this->error('保存App的LOGO失败');
            }
        }
		*/
        $this->success('保存成功');
    }
	
	/**
     * 将商品加入收藏夹
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function doAddHtml(){
        $ary_request = $this->_request();
		$ary_request['show_html'] = trim($ary_request['show_html'] );
		if(empty($ary_request['show_html'])){
			$this->ajaxReturn(array('status'=>'0','info'=>"创建的文件名不能为空"));exit;
		}
		if(empty($ary_request['tabs']) && empty($ary_request['dir']) && empty($ary_request['options']) && empty($ary_request['type'])){
			$this->ajaxReturn(array('status'=>'0','info'=>"参数不全"));exit;
		}
		if($ary_request['change'] == 'addHtml' && $ary_request['type'] == 'define'){
			$file = "./Public/Tpl/" . CI_SN . "/" . $ary_request['dir'] .'/define/'.$ary_request['show_html'].'.html';
			if(file_exists($file)){
				$this->ajaxReturn(array('status'=>'0','info'=>"此文件名已存在,创建失败"));exit;
			}else{
				$fileUtil = new FileUtil();
				$path = "./Public/Tpl/" . CI_SN . "/" . $ary_request['dir'] .'/define/';
				if(!is_dir($path)){
					$fileUtil->createDir($path);
				}				
				$result = $fileUtil->createFile($file);
				if($result == true){
					$this->ajaxReturn(array('status'=>true,'info'=>"创建文件成功"));exit;
				}else{
					$this->ajaxReturn(array('status'=>'0','info'=>"创建文件失败"));exit;
				}
			}
		}
		$this->ajaxReturn(array('status'=>'0','info'=>"创建文件失败"));exit;
    }
	
}

