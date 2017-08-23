<?php

/**
 * 前台站点地图
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-02-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SitemapAction extends HomeAction {

    /**
     * 初始化操作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 站点地图默认控制器
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-13
     */
    public function index() {
        C('SHOW_PAGE_TRACE', false);
        //取出站点地图的各项设置
        $ary_mapsetting = D('SysConfig')->getCfgByModule('GY_SITEMAP');
        //设置缓存
        $cache_time = Days2Seconds($ary_mapsetting['MAP_FREQ'], 86400 * 7);
        $cache = cache(array('type' => 'file', 'expire' => $cache_time));
        //$cache = cache(array('type' => 'file', 'expire' => 1));
        $data = $cache->get('sitemap');
        if (false == $data) {
            //没有缓存或者已过期,从数据库取数据生成地图
            //需要首页、商品列表，分类（品牌）列表，商品详情页，资讯列表
            $data['refresh'] = date('c');
            $data['setting'] = $ary_mapsetting;
            //@todo 此处需要根据模版机制生成超链接地址
            $data['url'] = array(
                'index' => U('/', '', true, false, true),
                'goods' => U('/Products/detail', '', true, false, true),
                'cates' => U('/Products/index', '', true, false, true),
                'brands' => U('/Products/index', '', true, false, true),
            	'articles' => U('/Article/articleDetail', '', true, false, true),
            );
            //取出数据库内各项有效数据
            $data['goods'] = D("ViewGoods")->field(array('g_id','g_name','g_update_time'))->where(array('g_status'=>1))->select();
            $data['cates'] = D('GoodsCategory')->field(array('gc_id','gc_name','gc_update_time'))->where(array('gc_status'=>1))->select();
            $data['brands'] = D('GoodsBrand')->field(array('gb_id','gb_name','gb_update_time'))->where(array('gb_status'=>1))->select();
            $data['article'] = D('Article')->field(array('a_id','a_title','a_keywords','a_update_time'))->where(array('a_status'=>1))->select();
            //保存至缓存
            $cache->set('sitemap', $data);
        }
        $this->assign($data);
        //输出为XML格式的站点地图
        $this->display('', 'utf-8', 'text/xml');
    }

}