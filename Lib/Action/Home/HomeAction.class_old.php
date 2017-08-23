<?php

/**
 * 前台展厅控制器基类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-02-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class HomeAction extends GyfxAction {

    /**
     * 基类初始化操作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-03-12
     */
    public function _initialize() {
    	//如果是手机访问，自动跳转到手机页面
        if(check_wap()){
            header("location:" . U('Wap/Index/index'));exit;
        }
		$shop_close = D('SysConfig')->getCfgByModule('GY_SHOP');
		//店铺关闭调到会员中心
		if($shop_close['GY_SHOP_OPEN'] == 0 && MODULE_NAME !='Cart'){
		
			header("location:" . U('Ucenter/Index/index'));exit;
		}		
		//判断是否启用店铺
        $this->doCheckOn();
		$this->doCheckLogin();
		//必须登录跳到登录页
		if($shop_close['GY_MUST_LOGIN'] == 1 && !session('?Members')){
			redirect(U('Home/User/Login'));exit;
			//header("location:" . U('Home/User/login'));exit;
		}
        import('ORG.Util.Session');
        $home_access = D('SysConfig')->getCfgByModule('HOME_USER_ACCESS');
        $exitTime = intval($home_access['EXPIRED_TIME']);
        if ($exitTime > 0 && Session::isExpired()) {
            unset($_SESSION['Members']);
            //不能加session_destroy()，会影响后台管理员登陆状态.一定要单独清空__HTTP_Session_Expire_TS 不然，setExpire的时候无法添加session存活时间
            //session_destroy();
            unset($_SESSION['__HTTP_Session_Expire_TS']);
        }
        // if ($exitTime > 0) {
        //     Session::setExpire(time() + $exitTime * 60);
        // }
    	//导入分页类包
        import('ORG.Util.Page');
        $this->getOnlineService();
        //将custom文件中的TPL常量和SESSION的定义搬到这里
        //降低系统对custom。php文件的依赖性，提高后台和会员中心的访问效率（减少一次数据库查询）
        $array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_DEFAULT'))->find();
        if (is_array($array_config) && !empty($array_config)) {
            define('TPL', $array_config['sc_value']);
            $_SESSION['NOW_TPL'] = $array_config['sc_value'];
        } else {
            define('TPL', 'default');
            $_SESSION['NOW_TPL'] = 'default';
        }
        $this->dir = TPL;

        //如果是预览，则根据预览算法规则重新定义预览模板所在目录
        $ary_request = $this->_request();
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $this->dir = 'preview_' . $ary_request['dir'];
        }
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
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $header_tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/header.html';
        } else {
            $header_tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';
        }
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $footer_tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/footer.html';
        } else {
            $footer_tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/footer.html';
        }
        //$ary_pagecount = M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
		$ary_pagecount = D('Gyfx')->selectOneCache('site_config','sc_value',array('sc_module' => 'GY_COUNT'));
		//M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($ary_pagecount){
            $pagecount = base64_decode($ary_pagecount['sc_memo']);
            $this->assign("pageCount", $pagecount);
        }
		$this->header_tpl = $header_tpl;
		if(GLOBAL_STOCK == true){
			$this->Getaddress($_SESSION['city']['cr_id']);
		}        
        // $ary_city = $this->doCity();
        //获取统计代码
//        $str_shop_code = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_CODE');
//       	$str_shop_code = $str_shop_code['GY_SHOP_CODE']['sc_value'];
//       	$str_shop_code = htmlspecialchars_decode($str_shop_code);
        $str_shop_info = M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($str_shop_info){
            $str_shop_code = base64_decode($str_shop_info['sc_memo']);
            $this->assign("shop_code", $str_shop_code);
        }
        
        if(isset($parent)){
            $this->assign("parent", $parent);
        }
        $this->assign("headerTpl", $header_tpl);
        $this->assign("footerTpl", $footer_tpl);
        $this->assign("cisn", CI_SN);
        $this->assign("view", $this->dir);
    }
	protected function doCheckLogin() {
		$session_mid = cookie('session_mid');
		if(!empty($session_mid)){
			$ary_member = getCache($session_mid);
			if(!empty($ary_member)){
				session('Members',$ary_member );
			}	
			/**
			if(!session('?Members')){
				$ary_member = getCache($session_mid);
				if(!empty($ary_member)){
					session('Members',$ary_member );
				}
			}
			**/
		}else{
			session('Members',null );
		}
		/**
		$m_id = base64_decode(cookie('mid'));
		if(!empty($m_id)){
			$m_name = D('Members')->where(array('m_id'=>$m_id))->getField('m_name');
			$ary_member = D('Members')->getInfo($m_name);
			session('Members', $ary_member);
		}**/
	}
    /**
     * 获取当前登录用户客服信息
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-12-23
     */
    protected function getOnlineService() {
        $ary_online = D('Gyfx')->selectAllCache('online_cat');
		//M('online_cat',C('DB_PREFIX'),'DB_CUSTOM')->select();
        foreach ($ary_online as &$cat){
            $cat['server'] = D('Gyfx')->selectAllCache('online_service','',array('oc_parent_id'=>$cat['oc_id'],'o_status'=>1));
			//M('online_service',C('DB_PREFIX'),'DB_CUSTOM')->where(array('oc_parent_id'=>$cat['oc_id'],'o_status'=>1))->select();
        }
		$ary_online_set = D('SysConfig')->getCfgByModule('GY_SHOP',1);
		$this->assign('online_start_time',$ary_online_set['GY_SHOP_ONLINE_START']);
		$this->assign('online_end_time',$ary_online_set['GY_SHOP_ONLINE_END']);
        if(is_array($ary_online) && count($ary_online)>0) $this->assign('ary_online',$ary_online);
    }
    
    /**
     * 设置每个二级模块的名称
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-26
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setTitle($title = '',$page_title = '',$page_keywords = '',$page_description='') {
        $seo_title = D('SysConfig')->getConfigs('GY_SEO', $page_title,null,null,1);
        $seo_keywords = D('SysConfig')->getConfigs('GY_SEO',$page_keywords,null,null,1);
        $seo_description = D('SysConfig')->getConfigs('GY_SEO',$page_description,null,null,1);
        //page_title前面拼接店铺名称
        $str_shop_name = D('SysConfig')->getConfigs('GY_SHOP','GY_SHOP_TITLE',null,null,1);
        //首页SEO标题
        $str_title = '';
        if(isset($seo_title[$page_title]['sc_value']) && !empty($seo_title[$page_title]['sc_value'])){
        	$str_title = $str_shop_name['GY_SHOP_TITLE']['sc_value'].' - '.$seo_title[$page_title]['sc_value'];
        }else{
        	$str_title = $str_shop_name['GY_SHOP_TITLE']['sc_value'].' - '.$title;
        }
        $this->assign('page_title', $str_title);
        $this->assign('page_keywords', $str_shop_name['GY_SHOP_TITLE']['sc_value'].'-'.$seo_keywords[$page_keywords]['sc_value']);
        $this->assign('page_description', $str_shop_name['GY_SHOP_TITLE']['sc_value'].'-'.$seo_description[$page_description]['sc_value']);
    }
    
    /**
     * 设置每个二级模块的关键字
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-26
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setKeywords($page_keywords = '') {
        $this->assign('page_keywords', $page_keywords);
    }
    
    /**
     * 判断是否启用
     * @author wangguibin <wangguibin@guanyisoft>
     * @date 2013-05-07
     */
    protected function doCheckOn() {
        $is_close = D('SysConfig')->getCfgByModule('GY_WEB_CONFIG');
        if ($is_close['STATUS'] == '1') {
            //网站暂停营业
            header("Content-Type:text/html;charset=utf-8;");
            echo $is_close['CONTENT'];
            exit;
        }
    }

    /**
     * 获取访问者真实IP区域
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-23
     */
    private function Getaddress($cr_id = '') {
        import('ORG.Net.IpLocation');// 导入IpLocation类
        $Ip = new IpLocation();
        $location = $Ip->getlocation(); 
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
        if(!empty($cr_id) && $cr_id > 0){
            $where = array();
            $where['cr_id'] = $cr_id;
            $where['cr_status'] = '1';
            $arr_city = D('Gyfx')->selectOneCache('city_region',$ary_field=null, $where, $ary_order=null);
			//$action->where($where)->find();
            if(!empty($arr_city) && is_array($arr_city)){
                $_SESSION['city']['cr_id'] = $arr_city['cr_id'];
                $_SESSION['city']['cr_name'] = $arr_city['cr_name'];
            }
        }else{
            $ip = $location['ip'];
            if(empty($ip) || $ip=='127.0.0.1'){
                $ip = '58.246.161.63';
            }
            $Ips = new Ip();
//            echo "<pre>";print_r($ip);exit;
            $ary_city = $Ips->getIpInfo($ip);
            if(!empty($ary_city) && is_array($ary_city)){

                $where = array();
                $where['cr_name'] = $ary_city['city'];
                $where['cr_status'] = '1';
                $city = D('Gyfx')->selectOneCache('city_region',$ary_field=null, $where, $ary_order=null);
				//$action->where($where)->find();
                if(!empty($city) && is_array($city)){
                    $_SESSION['city']['cr_id'] = $city['cr_id'];
                    $_SESSION['city']['cr_name'] = $city['cr_name'];
                }
            }
        }
    }

}
