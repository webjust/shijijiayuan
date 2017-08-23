<?php

/**
 * 手机APP版前台展厅控制器基类
 * 前台控制器均需要继承此类
 *
 * @stage 7.8.5
 * @package Action
 * @subpackage App
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2015-07-23
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class AppAction extends GyfxAction {

    protected  $dir = '';
	protected  $app_theme_path = '';
	protected  $header_tpl = '';
    /**
     * 基类初始化操作
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-07-23
     */
    public function _initialize() {
		$ary_get = $this->_request();
	    $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
	    if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
		    if($_SESSION['Members']){
			    header("location:" . U('Wap/Index/index'));
			    exit;
		    }
		    //如果网站没启用，则直接引导到会员中心
		    header("location:" . U('Wap/User/Login'));
		    exit;
	    }

    	//导入分页类包
        import('ORG.Util.Page');
        
        //将custom文件中的TPL常量和SESSION的定义搬到这里
        //降低系统对custom。php文件的依赖性，提高后台和会员中心的访问效率（减少一次数据库查询）
	    $this->dir = C('APP_TPL_DIR');
	    if(!$this->dir) {
		    $this->error('WAP主题目录没有设置！');
	    }
	    if(!defined("APP_TPL")) {
            if (is_array($ary_get) && !empty($ary_get)) {
                define('APP_TPL', $ary_get['dir']);
                $_SESSION['NOW_APP_TPL'] = $ary_get['dir'];
            } else {
                define('APP_TPL', 'default');
                $_SESSION['NOW_APP_TPL'] = 'default';
            }
        }
		
	    $app_theme_path = '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/' . APP_TPL .'/';
		$this->app_theme_path = '.' . $app_theme_path;
        //如果是预览，则根据预览算法规则重新定义预览模板所在目录
        $ary_request = $this->_request();
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $this->dir = 'preview_' . $ary_request['dir'];
        }
        $config = array(
            'tpl' => $app_theme_path,
            'js' => $app_theme_path . 'js/',
            'images' => $app_theme_path . 'images/', // 客户模版images路径替换规则
            'css' => $app_theme_path . 'css/', // 客户模版css路径替换规则
        );
        C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
        C('TMPL_PARSE_STRING.__JS__', $config['js']);
        C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
        C('TMPL_PARSE_STRING.__CSS__', $config['css']);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $header_tpl = $this->app_theme_path .'preview/header.html';
        } else {
            $header_tpl = $this->app_theme_path . 'header.html';
        }
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $footer_tpl = $this->app_theme_path .'preview/footer.html';
        } else {
            $footer_tpl = $this->app_theme_path . 'footer.html';
        }
        $ary_pagecount = M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($ary_pagecount){
            $pagecount = base64_decode($ary_pagecount['sc_memo']);
            $this->assign("pageCount", $pagecount);
        }
		$this->header_tpl = $header_tpl;

        //获取官网基本设置
        $shopInfo = D('SysConfig')->getCfgByModule('GY_SHOP');
        $this->assign("commonInfo", $shopInfo);

        $this->assign("headerTpl", $header_tpl);
        $this->assign("footerTpl", $footer_tpl);
        $this->assign("cisn", CI_SN);
        $this->assign("view", $this->dir);
    }

    /**
     * 设置每个二级模块的名称
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2015-07-23
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setTitle($title = '',$page_title = '',$page_keywords = '',$page_description='') {
        $seo_title = D('SysConfig')->getConfigs('GY_SEO', $page_title);
        $seo_keywords = D('SysConfig')->getConfigs('GY_SEO',$page_keywords);
        $seo_description = D('SysConfig')->getConfigs('GY_SEO',$page_description);
        //page_title前面拼接店铺名称
        $str_shop_name = D('SysConfig')->getConfigs('GY_SHOP','GY_SHOP_TITLE');
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
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2015-07-23
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setKeywords($page_keywords = '') {
        $this->assign('page_keywords', $page_keywords);
    }

}
