<?php

/**
 * 后台基类
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class AdminBaseAction extends GyfxAction {

	protected $dir = '';

    protected $_name = '';      //获取当前模块的控制器

    /**
     * 后台登陆后的管理员session信息
     * @var array
     */
    protected $admin = array();

    /**
     * 基类初始化操作
     * @author zuo <zuojianghua@guanyisot.com>
     * @date 2012-12-31
     */
    public function _initialize() {
        C('LAYOUT_NAME',"edit_layout");
        $this->doCheckLogin();  
        $this->getTitle();        
        $this->_name = $this->getActionName();
        
        $array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_DEFAULT'))->find();
        if (is_array($array_config) && !empty($array_config)) {
            define('TPL', $array_config['sc_value']);
            $_SESSION['NOW_TPL'] = $array_config['sc_value'];
        } else {
            define('TPL', 'default');
            $_SESSION['NOW_TPL'] = 'default';
        }
        $this->dir = TPL;
        $config = array(
            'tpl' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/',
            'js' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/js/',
            'images' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/images/', // 客户模版images路径替换规则
            'css' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/css/', // 客户模版css路径替换规则
        );
        C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
        C('TMPL_PARSE_STRING.__JS__', $config['js']);
        C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
        C('TMPL_PARSE_STRING.__CSS__', $config['css']);
        
        import('ORG.Util.Session');
        import('ORG.Util.Page');
        $this->assign('admin_logo',C('TMPL_LOGO'));
        //是否有权限访问，默认允许访问
        $INT_USER_ACCESS = 1;
        $admin_access = D('SysConfig')->getCfgByModule('ADMIN_ACCESS');
     
        if (intval($admin_access['EXPIRED_TIME']) > 0 && Session::isExpired()) {
            unset($_SESSION[C('USER_AUTH_KEY')]);
            unset($_SESSION);
            session_destroy();
        }
        if (intval($admin_access['EXPIRED_TIME']) > 0) {
            Session::setExpire(time() + $admin_access['EXPIRED_TIME'] * 60);
        }
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            $rbac = new Arbac();
            if (!$rbac->AccessDecision()) {
                //检查认证识别号
                if (!$_SESSION [C('USER_AUTH_KEY')]) {
                    //跳转到认证网关
                    redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    redirect(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->assign('jumpUrl', PHP_FILE . C('USER_AUTH_GATEWAY'));
                    }
                    if($this->isAjax()){
                        layout(false);
                        echo L('_VALID_ACCESS_');exit;
                    }else{
                    	$INT_USER_ACCESS = 0;
                    	//权限问题
				        $this->assign('is_user_access',$INT_USER_ACCESS);
                        
				        $this->getTitle();
				        $menu_url = '';
				        $action_name = '';
				        if(ACTION_NAME == 'index'){
				        	$menu_url = MODULE_NAME.':pageList';
				        	$action_name = 'pageList';
				        }else{
				        	$menu_url = MODULE_NAME.':'.ACTION_NAME;
				        	$action_name = ACTION_NAME;
				        }
				        $admin_url = '/Admin/'.MODULE_NAME.'/'.$action_name;
				        $menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$admin_url))->find();
				        if(empty($menu_info)){
				        	$admin_url = '/Admin/'.MODULE_NAME.'/'.ACTION_NAME;
				        	$menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$admin_url))->find();
				        	$sn = explode('_',substr($menu_info['sn'],3));
				        	$this->getSubNav($sn[1],0,0);
				        }else{
				        	$sn = explode('_',substr($menu_info['sn'],4));
				        	$this->getSubNav($sn[0],$sn[1],$sn[2]);
				        }
                       die('您无权访问此页');
                    }
                }
            }
        }    
        //权限问题
        $this->assign('is_user_access',$INT_USER_ACCESS);
        $str_shop = D('SysConfig')->getConfigs('GY_SHOP');
        $this->assign($str_shop);
    }
    /**
     * 设置每个二级模块的名称
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setTitle($page_title = '') {
        $this->assign('page_title', $page_title);
    }
    
    ### 以下为保护方法 #########################################################
    /**
     * 设置控制器在用户中心页面中的1级2级3级菜单哪些被选中
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     * @param int $nav1 一级导航菜单位置
     * @param int $nav2 二级导航菜单位置
     * @param int $nav3 三级导航菜单位置
     */

    protected function getSubNav($nav1 = 0, $nav2 = 0, $nav3 = 0, $name = '') {
        //判断主导航和左侧导航的位置
        $this->assign('nav1', $nav1);
        $this->assign('nav2', $nav2);
        $this->assign('nav3', $nav3);
        //生成面包屑导航数据，默认全部从后台首页开始 ++++++++++++++++++++++++++++++++
        $nav['bread0'] = array('name' => '后台首页', 'url' => U('Admin/Index/index'));
        //一级为顶部栏目名称
        $nav['bread1'] = $this->tops[$nav1 - 1];
        //二级为左侧模块名称
        $nav['bread2'] = $this->menus[$nav1][$nav2][0];
        //当前页面名称
        $nav['bread3'] = (empty($name)) ? ( (isset($this->menus[$nav1][$nav2][$nav3])) ? $this->menus[$nav1][$nav2][$nav3] : array('name' => '') ) : array('name' => $name);
        $this->assign($nav);
    }

    ### 以下为私有方法 #########################################################

    /**
     * 判断是否登录
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-31
     */
    public function doCheckLogin() {
        //todo 此处要做登录判断
        if (!session(C('USER_AUTH_KEY'))) {
            $int_port = "";
            if($_SERVER["SERVER_PORT"] != 80){
                $int_port = ':' . $_SERVER["SERVER_PORT"];
            }
            $string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
            $data = D('SysConfig')->getCfgByModule('ADMIN_LOGIN_PROMPT');
            if($data['ADMIN_LOGIN_PROMPT_SET'] == '1'){
                $this->error(L('NO_LOGIN'), U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri));
            }else{
                header("Location:".U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri)."");exit;
            }
        } else {
            $this->admin = session(C('USER_AUTH_KEY'));
        }
    }

    /**
     * 获取页头meta基本数据
     * @author zuo <zuojianghua@gmail.com>
     * @date 2013-01-05
     */
    public function getTitle() {
        $ary_data['common_title'] = '管易分销系统后台管理中心';
        $ary_data['common_keywords'] = '管易分销软件,';
        $ary_data['common_desc'] = '管易分销软件。';

        $ary_data['site_name'] = 'XX公司';

        $this->assign($ary_data);

        $this->assign($ary_data);
    }

   
    /**
     * 导出后台EXCEL信息数据
     * @author Terry
     * @since 7.2
     * @version 1.0
     * @date 2012-5-14
     */
    public function getExportFileDownList() {
        $ary_get = $this->_get();
        switch ($ary_get['type']) {
            case 'excel':
                header("Content-type:application/force-download;charset=utf-8");
                header("Content-Disposition:attachment;filename=" . $ary_get['file']);
                readfile(APP_PATH.'Public/Uploads/'.CI_SN.'/excel/' . $ary_get['file']);
                break;
        }
        exit;
    }
     
   	/**
     * 执行update数据库更新
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-01-17
     */
     public function updateSql(){
        $update_file = './Lib/Action/System/update.html';
        $update_sql_file = './Lib/Action/System/update.sql';
        $file_exist = file_exists($update_file);
        $file_sql_exist = file_exists($update_file);
        if($file_exist == true || $file_sql_exist == true){
        	if(ACTION_NAME == 'updateSql'){
        		if(MODULE_NAME == 'Index'){
   	        		if($file_exist == true){
	        			$file_content = file_get_contents($update_file);
	        		}else{
	        			$file_content = '脚本升级';
	        		}
	        		$this->assign('file_content',trim($file_content));
	        		$this->display('Index:updateSql');
		            exit;      			
        		}
        	}else{
        		//允许更新脚本
        		if(ACTION_NAME != 'doUpdateSql'){
        			$this->display('Index:update');
	            	exit;  
        		}
        	}
        }  
     }
     
}
