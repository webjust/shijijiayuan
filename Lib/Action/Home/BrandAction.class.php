<?php

/**
 * 前台文章展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.1
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BrandAction extends HomeAction{

    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function _initialize() {
        parent::_initialize();
    }

    public function index()
    {
        $this->setTitle('品牌专区');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/brand_list.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/brand_list.html';
        }
        if($_GET['v']==2){

            $ApiUtil = D("ApiUtil");

            $brandList = $ApiUtil->GetFilterBrandList($_REQUEST["gb_letter"],$_REQUEST["gb_region"],$_REQUEST["keywords"]);

            $countryList = $ApiUtil->GetCountryList(ture);
            $letters = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","#其他");

            $goodslist = M('')->query("SELECT g_id,g_name,gb_id,g_picture,g_price FROM fx_view_products GROUP BY gb_id");

            $this->assign("brandList",$brandList);
            $this->assign("countryList",$countryList);
            $this->assign("letters",$letters);

            $this->assign("gb_letter",$_REQUEST["gb_letter"]);
            $this->assign("gb_region",$_REQUEST["gb_region"]);
            $this->assign("keywords",$_REQUEST["keywords"]);

            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/brand_list-v2.html';
            $this->assign("v",'-v2');
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
            $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
            $this->assign("headerTpl",$headerTpl);
            $this->assign("footerTpl",$footerTpl);
            $this->assign("gb_letter",$_REQUEST["gb_letter"]);
            $this->assign("gb_region",$_REQUEST["gb_region"]);
            $v='-v2';
        }
        $this->display($tpl);
    }
}
