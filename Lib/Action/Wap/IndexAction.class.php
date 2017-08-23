<?php

/**
 * 前台模版首页
 *
 * @package Action
 * @subpackage Wap
 * @stage 7.0
 * @author Nick <shanguangkun@guanyisoft.com>
 * @date 2014-05-19
 * @license MIT
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class IndexAction extends WapAction {

    protected $dir = '';

    public function _initialize() {
        parent::_initialize();
        $this->wap_theme_path = './Public/Tpl/qiaomoxuan/wap_v2/lxkj/';
    }

    /**
     * 客户模版默认首页
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-19
     * @version 7.5
     */
    public function index() {

        $ary_request = $this->_request();
		$ary_request['index']=1;//判断是否为首页
        $this->setTitle('首页','TITLE_INDEX','DESC_INDEX','KEY_INDEX');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            //$tpl = $this->wap_theme_path . 'preview/index.html';
			$tpl = $this->wap_theme_path . 'index.html';
        } else {
            $tpl = $this->wap_theme_path . 'index.html';
        }

        $headerTpl = $this->wap_theme_path . 'indexheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = $this->wap_theme_path . 'header.html';

        }else{
            $headerTpl = $this->wap_theme_path . 'indexheader.html';
        }

        $footerTpl = $this->wap_theme_path . 'indexfooter.html';
        if(!file_exists($footerTpl)){
            $footerTpl = $this->wap_theme_path . 'footer.html';

        }else{
            $footerTpl = $this->wap_theme_path . 'indexfooter.html';
        }
		
		//七牛图片显示授权
		$index_update_time=filemtime($tpl);
		if(time()-$index_update_time > 86400 && $index_update_time!=false && $_SESSION['OSS']['GY_QN_ON'] == '1'){
			$index_content = htmlspecialchars_decode(file_get_contents($tpl));
			$replace_index_content=D('ViewGoods')->ReplaceItemDescPicDomain($index_content);
			file_put_contents($tpl, $replace_index_content);
		}
		
		//$warm_prompt = D('SysConfig')->where(array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'))->getField('sc_value');
        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
		$this->assign('warm_prompt',$warm_prompt['sc_value']);
        $this->assign("ary_request",$ary_request);
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $domain = $_SERVER['SERVER_NAME'];
        $this->assign("domain",$domain);
        $this->display($tpl);
    }

	/**
     * 关于必迈
     * @author zhaozhicheng
     * @date 2015-09-25
     */
    public function regard() {
        $data = $this->_request();
        $a_id = $data['a_id'];
        $articleinfo= M('article', C('DB_PREFIX'), 'DB_CUSTOM')->where('a_id='.$a_id)->find();
        $this->setTitle('关于必迈');
        $this->assign('articleinfo',$articleinfo);
        $tpl = $this->wap_theme_path . 'regard.html';
        $this->display($tpl);
    }
	/**
     * 线下门店
     * @author Terry<zhuwenwei@guanyisoft.com>
     * @date 2015-09-18
     */
    public function lineShop() {
		$ary_params = $this->_post();
		$o2o_api =  new GyO2oApi();
		$res=$o2o_api->GetShopInfo($ary_params);
		$num = count($res['data']);
		//dump($num);
		$ary_city = D('CityRegion')->getAllCitys(1);
		//读取文章内容
		$data = $this->_request();
        $a_id = $data['a_id'];
        $articleinfo= M('article', C('DB_PREFIX'), 'DB_CUSTOM')->where('a_id='.$a_id)->find();
		$this->assign('articleinfo',$articleinfo);
		$this->assign("citys",$ary_city);
		$this->assign("o2o_image_url",C('API_URL_IMAGE'));
		$this->assign('addr', $res);
		$this->assign('num', $num);
    	$this->setTitle('线下门店');
		$tpl = $this->wap_theme_path . 'lineShop.html';
        $this->display($tpl);
    } 
	
	/**
     * 联系我们
     * @author Terry<zhuwenwei@guanyisoft.com>
     * @date 2015-09-18
     */
    public function contactUs() {
    	$this->setTitle('联系我们');
		$tpl = $this->wap_theme_path . 'contactUs.html';
        $this->display($tpl);
    } 
    
    /**
     * 搜索页面
     * @author Terry<zhuwenwei@guanyisoft.com>
     * @date 2015-09-18
     */
    public function search() {
        $this->setTitle('搜索');
        $tpl = $this->wap_theme_path . 'search.html';
        $this->display($tpl);
    } 
	
	
}
