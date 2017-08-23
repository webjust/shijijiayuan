<?php

/**
 * 手机版前台展厅控制器基类
 * 前台控制器均需要继承此类
 *
 * @stage 7.5
 * @package Action
 * @subpackage Wap
 * @author Nick <shanguangkun@guanyisoft.com>
 * @date 2014-05-19
 * @license MIT
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class WapAction extends GyfxAction {

    protected  $dir = '';
	protected  $wap_theme_path = '';
	protected  $header_tpl = '';
    /**
     * 基类初始化操作
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     */
    public function _initialize() {
        $shop_close = D('SysConfig')->getCfgByModule('GY_SHOP',1);
        //$is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,1);
        $is_on = $shop_close['GY_SHOP_OPEN'];
	    if ($is_on == '0') {
		    if($_SESSION['Members']){
//			    header("location:" . U('Wap/Index/index'));
			    header("location:" . U('Wap/Index/index'));
			    exit;
		    }
		    //如果网站没启用，则直接引导到会员中心
		    header("location:" . U('Wap/User/Login'));
		    exit;
	    }

    	//导入分页类包
        import('ORG.Util.Page');
        
        //判断是否启用店铺
        $this->doCheckOn();
        //$this->getOnlineService();
        //将custom文件中的TPL常量和SESSION的定义搬到这里
        //降低系统对custom。php文件的依赖性，提高后台和会员中心的访问效率（减少一次数据库查询）
	    $this->dir = C('WAP_TPL_DIR');
	    if(!$this->dir) {
		    $this->error('WAP主题目录没有设置！');
	    }
	    if(!defined("WAP_TPL")) {
		    $array_config = D('Gyfx')->selectOneCache('sys_config','', array("sc_key" => 'GY_TEMPLATE_WAP_DEFAULT'), $ary_order=null,$time=3600);
			$array_config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
			//D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_WAP_DEFAULT'))->find();
		    if (is_array($array_config) && !empty($array_config['GY_TEMPLATE_WAP_DEFAULT'])) {
			    define('WAP_TPL', $array_config['GY_TEMPLATE_WAP_DEFAULT']);
			    $_SESSION['NOW_WAP_TPL'] = $array_config['GY_TEMPLATE_WAP_DEFAULT'];
		    } else {
			    define('WAP_TPL', 'default');
			    $_SESSION['NOW_WAP_TPL'] = 'default';
		    }
	    }

	    $wap_theme_path = '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/' . WAP_TPL .'/';
		$this->wap_theme_path = '.' . $wap_theme_path;
        //如果是预览，则根据预览算法规则重新定义预览模板所在目录
        $ary_request = $this->_request();
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $this->dir = 'preview_' . $ary_request['dir'];
        }
        $config = array(
            'tpl' => $wap_theme_path,
            'js' => $wap_theme_path . 'js/',
            'images' => $wap_theme_path . 'images/', // 客户模版images路径替换规则
            'css' => $wap_theme_path . 'css/', // 客户模版css路径替换规则
            'ucss' => $wap_theme_path . 'Ucenter/css/',
            'uimages' => $wap_theme_path . 'Ucenter/images/',
            'ujs'=>$wap_theme_path . 'Ucenter/js/',
        );
        C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
        C('TMPL_PARSE_STRING.__JS__', $config['js']);
        C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
        C('TMPL_PARSE_STRING.__CSS__', $config['css']);
        C('TMPL_PARSE_STRING.__UCSS__', $config['ucss']);
        C('TMPL_PARSE_STRING.__UIMAGES__', $config['uimages']);
        C('TMPL_PARSE_STRING.__UJS__', $config['ujs']);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $header_tpl = $this->wap_theme_path .'preview/header.html';
        } else {
            $header_tpl = $this->wap_theme_path . 'header.html';
        }
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $footer_tpl = $this->wap_theme_path .'preview/footer.html';
        } else {
            $footer_tpl = $this->wap_theme_path . 'footer.html';
        }
        $ary_pagecount = D('Gyfx')->selectOneCache('site_config',$ary_field=null, array('sc_module' => 'GY_COUNT'), $ary_order=null,$time=3600);
		//M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($ary_pagecount){
            $pagecount = base64_decode($ary_pagecount['sc_memo']);
            $this->assign("pageCount", $pagecount);
        }
		$this->header_tpl = $header_tpl;
        //$this->Getaddress($_SESSION['city']['cr_id']);

        //获取官网基本设置
        $shopInfo = D('SysConfig')->getCfgByModule('GY_SHOP',1);
        $this->assign("commonInfo", $shopInfo);

        $this->assign("headerTpl", $header_tpl);
        $this->assign("footerTpl", $footer_tpl);
        $this->assign("cisn", CI_SN);
        $this->assign("view", $this->dir);
    }

    /**
     * 获取当前登录用户客服信息
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     */
    private function getOnlineService() {
        $ary_online = M('online_cat',C('DB_PREFIX'),'DB_CUSTOM')->select();
        foreach ($ary_online as &$cat){
            $cat['server'] = M('online_service',C('DB_PREFIX'),'DB_CUSTOM')->where(array('oc_parent_id'=>$cat['oc_id'],'o_status'=>1))->select();
        }
        if(is_array($ary_online) && count($ary_online)>0){ 
            $this->assign('ary_online',$ary_online);
        }
    }
    
    /**
     * 设置每个二级模块的名称
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
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
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setKeywords($page_keywords = '') {
        $this->assign('page_keywords', $page_keywords);
    }
    
    /**
     * 判断网店是否启用
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     */
    private function doCheckOn() {
        $is_close = D('SysConfig')->getCfgByModule('GY_WEB_CONFIG',1);
        if ($is_close['STATUS'] == '1') {
            //网站暂停营业
            header("Content-Type:text/html;charset=utf-8;");
            echo $is_close['CONTENT'];
            exit;
        }
    }

    /**
     * 获取访问者真实IP区域
     * @author Nick<shanguangkun@guanyisoft.com>
     * @date 2014-05-19
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
            $arr_city = $action->where($where)->find();
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
                $city = $action->where($where)->find();
                if(!empty($city) && is_array($city)){
                    $_SESSION['city']['cr_id'] = $city['cr_id'];
                    $_SESSION['city']['cr_name'] = $city['cr_name'];
                }
            }
        }
    }

}
