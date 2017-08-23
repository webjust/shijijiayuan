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
class HotListAction extends HomeAction{

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
        $this->setTitle('热销专区');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/hotlist.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/hotlist.html';
        }
        if($_GET['v']==2){

            $ApiUtil = D("ApiUtil");

            $brandList = $ApiUtil->GetBrandList(ture);
            $functionList = $ApiUtil->GetFunctionList(ture);
            $categoryList = $ApiUtil->GetCategoryList(ture);
            $countryList = $ApiUtil->GetCountryList(ture);

            $this->assign("brandList",$brandList);
            $this->assign("functionList",$functionList);
            $this->assign("categoryList",$categoryList);
            $this->assign("countryList",$countryList);

            $goodslist = $ApiUtil->GetFilterGoodsList($_REQUEST['cid'],$_REQUEST['cname'],$_REQUEST['bid'],$_REQUEST['func'],$_REQUEST['minPrice'],$_REQUEST['maxPrice'],$_REQUEST['keywords'],"1",$_REQUEST['isnew']);
            $this->assign("goodslist",$goodslist);

            $this->assign("cid",$_REQUEST['cid']);
            $this->assign("cname",$_REQUEST['cname']);
            $this->assign("bid",$_REQUEST['bid']);
            $this->assign("func",$_REQUEST['func']);
            $this->assign("minPrice",$_REQUEST['minPrice']);
            $this->assign("maxPrice",$_REQUEST['maxPrice']);
            $this->assign("keywords",$_REQUEST['keywords']);
            $this->assign("ishot",$_REQUEST['ishot']);
            $this->assign("isnew",$_REQUEST['isnew']);

            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/hotlist-v2.html';
            $this->assign("v",'-v2');
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
            $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
            $this->assign("headerTpl",$headerTpl);
            $this->assign("footerTpl",$footerTpl);
            $v='-v2';
        }
        $this->display($tpl);
    }
}
