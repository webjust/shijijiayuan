<?php

/**
 * 前台团购商品展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2014-07-07
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GroupbuyAction extends HomeAction {
	
    /**
     * 初始化操作
     * @author Hcaijin 
     * @date 2014-07-07
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 团购列表页展示 
     * @author Hcaijin
     * @date 2014-07-07
     */
    public function index(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $ary_request = $this->_request();
        //促销广告flash图片展示
		$ary_ads = D('RelatedGroupbuyAds')->order('sort_order asc')->select();
        //echo D('RelatedGroupbuyAds')->getLastSql();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			$ary_ad['ad_pic_url'] = D('QnPic')->picToQn($ary_ad['ad_pic_url']);
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        //品牌团
        $groupbuyBrand = M('groupbuy_brand',C('DB_PREFIX'),'DB_CUSTOM');
        $brandlist = $groupbuyBrand->where(array('gbb_is_hot'=>'1'))->limit(0,6)->order('gbb_order asc')->select();
        // 获取品牌团开始时间和结束时间
        foreach($brandlist as $key=>$brandDetail){
            $ary_where = array(
                'gbb_id'        => $brandDetail['gbb_id'],
                'gp_start_time' => array('neq',0),
                'gp_end_time'   => array('neq',0)
                );
            $ary_brand_time = D('groupbuy')->field('min(gp_start_time) as brand_start_time,max(gp_end_time) as brand_end_time')->where($ary_where)->find();
            $brandlist[$key]['brand_end_time'] = (isset($ary_brand_time['brand_end_time']) && !empty($ary_brand_time['brand_end_time'])) ? $ary_brand_time['brand_end_time'] : '';
			$brandlist[$key]['gbb_pic'] = D('QnPic')->picToQn($brandDetail['gbb_pic']);
        }
        //商品团
        $where =array('is_active'=>'1','deleted'=>'0');
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $g_list = $groupbuy
                    ->where($where)
                    ->limit(0,12)->order('gp_create_time desc')->select();
        foreach ($g_list as $key=>$val){
            //已预购数量 = 虚拟数量+实际销售量
            $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
            //取出价格关联表 价格阶级
            $ary_range_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
            //定义一个 达到购买量的数组(达到购买量可享受优惠价)
            $current_num_range = 0;
            foreach($ary_range_price as $rp_k=>$rp_v){
                if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                    //$current_range = $rp_v['rgp_num'];
                    $current_num_range = $rp_v['rgp_num'];
                }
            }
            //dump($ary_relbuy_num);die;
            if($current_num_range > 0) {
                $g_list[$key]['gp_price'] = $gp_price->where( array( 'gp_id'   => $val['gp_id'],
                    'rgp_num' => $current_num_range
                ) )->getField( 'rgp_price' );
            }
            $g_list[$key]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$val['g_id']))->order('pdt_sale_price asc')->getField('pdt_sale_price');

            switch($val['gp_tiered_pricing_type']) {
                case 2:
                    $g_list[$key]['gp_price'] = $sale_price*$g_list[$key]['gp_price'];
                    break;
                default:
                    $g_list[$key]['gp_price'] = $sale_price - $g_list[$key]['gp_price'];
                    break;
            }
            $g_list[$key]['gp_price'] || $g_list[$key]['gp_price'] = 0.00;

            $g_list[$key]['gp_now_number'] = $buy_nums;

            $g_list[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
            //验证价格
            $i = 0;
            if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] < $get['startPrice'] || $g_list[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if(!empty($get['startPrice']) && empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] < $get['startPrice']){
                    $i++;
                }
            }
            if(empty($get['startPrice']) && !empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if($i != 0){
                unset($g_list[$key]);
            }else{
                //验证区域
                if(!empty($get['cr_id'])){
                    $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count('related_area_id');
                    if($result_show == 0){
                        unset($g_list[$key]);
                    }
                }
            }
        }

        $gc_list = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1'))->select();
        $gb_list = D('GroupbuyBrand')->where(array('gbb_is_display'=>'1','gbb_status'=>'1'))->select();
        
        $this->assign('gclist',$gc_list);
        $this->assign('gblist',$gb_list);
        $this->assign('ary_ads', $ary_ad_infos);
        $this->assign('ary_brands', $brandlist);
        $this->assign('ary_glist', $g_list);
        $this->assign('ary_request', $ary_request);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/groupbuy.html';
        $this->display($tpl);
    }

    /**
     * 团购列表页展示 
     * @author Hcaijin
     * @date 2014-07-07
     */
    public function lists(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,'Y');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $get = $this->_get();
        unset($get['_URL_']);
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM');
        /* $ary_city = $gp_city->field('cr.cr_name,cr.cr_id')
                            ->join(C('DB_PREFIX').'city_region as cr on cr.cr_id='.C('DB_PREFIX').'related_groupbuy_area.cr_id')
                            ->join(C('DB_PREFIX').'groupbuy as gp on gp.gp_id='.C('DB_PREFIX').'related_groupbuy_area.gp_id')
                            ->where(array('gp.deleted'=>0))
                            ->group('cr_id')
                            ->select(); */
        $array_where = array('is_active'=>1,'deleted'=>0);
        $join_str = '';
        if(empty($get['gcid'])){
        	unset($get['gcid']);
        }else{
            $gc_id = $get['gcid'];
            $array_where['gc.gc_id'] = $gc_id;
            $join_str .= C('DB_PREFIX')."groupbuy_category as gc on(gc.gc_id=".C('DB_PREFIX')."groupbuy.gc_id)";
        }
        if(empty($get['gbid'])){
        	unset($get['gbid']);
        }else{
            $gb_id = $get['gbid'];
            $array_where['gbb_id'] = $gb_id;
            //$join_str .= C('DB_PREFIX')."groupbuy_brand as gb on(gb.gbb_id=".C('DB_PREFIX')."groupbuy.gbb_id)";
        }
        if($get['type']==1){
            $array_where['gp_start_time'] = array('gt',date('Y-m-d H:i:s',time()));
        }
        $count = $groupbuy->where($array_where)->count();
        $obj_page = new Pager($count, 6);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $bulkList = $groupbuy->join($join_str)
                             ->where($array_where)
                             ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                             ->order("gp_create_time desc")
                             ->select();
        //echo $groupbuy->getLastSql();exit();
        foreach ($bulkList as $key=>$val){
            //已预购数量 = 虚拟数量+实际销售量
            $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
            //取出价格关联表 价格阶级
            $ary_range_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
            //定义一个 达到购买量的数组(达到购买量可享受优惠价)
            $current_num_range = 0;
            foreach($ary_range_price as $rp_k=>$rp_v){
                if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                    //$current_range = $rp_v['rgp_num'];
                    $current_num_range = $rp_v['rgp_num'];
                }
            }
            //dump($ary_relbuy_num);die;
            if($current_num_range > 0) {
                $bulkList[$key]['gp_price'] = $gp_price->where( array( 'gp_id'   => $val['gp_id'],
                    'rgp_num' => $current_num_range
                ) )->getField( 'rgp_price' );
            }
            $bulkList[$key]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$val['g_id']))->order('pdt_sale_price asc')->getField('pdt_sale_price');

            switch($val['gp_tiered_pricing_type']) {
                case 2:
                    $bulkList[$key]['gp_price'] = $sale_price*$bulkList[$key]['gp_price'];
                    break;
                default:
                    $bulkList[$key]['gp_price'] = $sale_price - $bulkList[$key]['gp_price'];
                    break;
            }
            $bulkList[$key]['gp_price'] || $bulkList[$key]['gp_price'] = 0.00;

            $bulkList[$key]['gp_now_number'] = $buy_nums;

            $bulkList[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
            //验证价格
            $i = 0;
            if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                if($bulkList[$key]['gp_price'] < $get['startPrice'] || $bulkList[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if(!empty($get['startPrice']) && empty($get['endPrice'])){
                if($bulkList[$key]['gp_price'] < $get['startPrice']){
                    $i++;
                }
            }
            if(empty($get['startPrice']) && !empty($get['endPrice'])){
                if($bulkList[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if($i != 0){
                unset($bulkList[$key]);
            }else{
                //验证区域
                if(!empty($get['cr_id'])){
                    $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count('related_area_id');
                    if($result_show == 0){
                        unset($bulkList[$key]);
                    }
                }
            }
            
        }
        if(!empty($get['cr_id'])){
            $get['cr_name'] = M('city_region')->where(array('cr_id'=>$get['cr_id']))->getField('cr_name');
        }
        //设置SEO
        $shop = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE',null,null,'Y');
        $array_seo = D('SysConfig')->getCfgByModule('BULK_LIST_SEO','Y');
        $this->setTitle($array_seo['BULK_LIST_TITLE'].' - '.$shop['GY_SHOP_TITLE']['sc_value'],$array_seo['BULK_LIST_KEYWORDS'],$array_seo['BULK_LIST_DESCRIPTION']);
        
        $groupbuySet = M('groupbuy_set',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gs_id'=>1))->find();
        $price_set = unserialize($groupbuySet['gs_related_price']);
        $city_id = array();
        foreach ($bulkList as $blK=>$blV){
            $ary_citys = $gp_city->where(array('gp_id'=>$blV['gp_id']))->select();
            foreach ($ary_citys as $key=>$val){
                array_push($city_id,$val['cr_id']);
                
            }
        }
        $city_id = array_unique($city_id);
        sort($city_id);
        foreach ($city_id as $k=>$v){
            $ary_city[$k]['cr_id'] = $v;
            $ary_city[$k]['cr_name'] = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->where(array('cr_id'=>$v))->getField('cr_name');
            
        }
        $gc_list = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1'))->select();
        $gb_list = D('GroupbuyBrand')->where(array('gbb_is_display'=>'1','gbb_status'=>'1'))->select();
        $this->assign('gclist',$gc_list);
        $this->assign('gblist',$gb_list);
        $this->assign('data',$bulkList);
        $this->assign('priceSet',$price_set);
        $this->assign('gs_timeshow_status',$groupbuySet['gs_timeshow_status']);
        $this->assign('pagearr',$pageInfo);
        $this->assign('get',$get);
        $this->assign('city',$ary_city);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/groupbuyList.html';
        $this->display($tpl);
    }

    /**
     * 团购列表页展示 
     * @author Hcaijin
     * @date 2014-07-07
     */
    public function tuan(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $ary_request = $this->_request();
        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/bulkheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';

        }else{
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/bulkheader.html';
        }
//设置SEO
        $shop = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE',null,null,'Y');
        $array_seo = D('SysConfig')->getCfgByModule('BULK_LIST_SEO','Y');
        $this->setTitle('团购列表页'.' - '.$shop['GY_SHOP_TITLE']['sc_value'],$array_seo['BULK_LIST_KEYWORDS'],$array_seo['BULK_LIST_DESCRIPTION']);		
        //促销广告flash图片展示
		$ary_ads = D('RelatedGroupbuyAds')->order('sort_order asc')->select();
        //echo D('RelatedGroupbuyAds')->getLastSql();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        //品牌团
        $groupbuyBrand = M('groupbuy_brand',C('DB_PREFIX'),'DB_CUSTOM');
        $brandlist = $groupbuyBrand->where(array('gbb_is_hot'=>'1'))->limit(0,6)->order('gbb_order asc')->select();
        // 获取品牌团开始时间和结束时间
        foreach($brandlist as $key=>$brandDetail){
            $ary_where = array(
                'gbb_id'        => $brandDetail['gbb_id'],
                'gp_start_time' => array('neq',0),
                'gp_end_time'   => array('neq',0)
                );
            $ary_brand_time = D('groupbuy')->field('min(gp_start_time) as brand_start_time,max(gp_end_time) as brand_end_time')->order('gp_order asc')->where($ary_where)->find();
            $brandlist[$key]['brand_end_time'] = (isset($ary_brand_time['brand_end_time']) && !empty($ary_brand_time['brand_end_time'])) ? $ary_brand_time['brand_end_time'] : '';
        }
        //商品团
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');

        $groupList = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1','gc_parent_id'=>0))->order('gc_order asc')->select();
        $gc_list = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1','gc_parent_id'=>0))->order('gc_order asc')->select();
        foreach($gc_list as $k => $gc){
            $gc_id = $gc['gc_id'];
            $sub_gc_id = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1','gc_parent_id'=>$gc['gc_id']))->order('gc_order asc')->field('gc_id')->select();
            foreach($sub_gc_id as $ids){
                $gc_id .= ','.$ids['gc_id'];
            }
            $where =array('is_active'=>'1','deleted'=>'0','gc_id'=>array('in',$gc_id));
            $g_list = $groupbuy->where($where)->limit(0,6)->order('gp_order asc,gp_create_time desc')->select();
            foreach ($g_list as $key=>$val){
                $g_list[$key]['cust_price'] = M('goods_info')->where(array('g_id'=>$val['g_id']))->getField('g_price');
                //取出价格阶级
                $rel_bulk_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
                $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
                $g_list[$key]['gp_now_number'] = $buy_nums;
                $array_f = array();
                foreach ($rel_bulk_price as $rbp_k=>$rbp_v){
                    if($buy_nums >= $rbp_v['rgp_num']){
                        $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
                    }
                }
                if(!empty($array_f)){
                    $array_max = new ArrayMax($array_f);
                    $rgp_num = $array_max->arrayMax();
                    $g_list[$key]['gp_price'] = $gp_price->where(array('gp_id'=>$val['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
                }
                if($_SESSION['OSS']['GY_OSS_ON'] == '1'){
                    $g_list[$key]['gp_picture'] = $val['gp_picture'];
                }else{
                    $g_list[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
                }
                
                //验证价格
                $i = 0;
                if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($g_list[$key]['gp_price'] < $get['startPrice'] || $g_list[$key]['gp_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                if(!empty($get['startPrice']) && empty($get['endPrice'])){
                    if($g_list[$key]['gp_price'] < $get['startPrice']){
                        $i++;
                    }
                }
                if(empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($g_list[$key]['gp_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                if($i != 0){
                    unset($g_list[$key]);
                }else{
                    //验证区域
                    if(!empty($get['cr_id'])){
                        $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count();
                        if($result_show == 0){
                            unset($g_list[$key]);
                        }
                    }
                }
                //判断当前团购时间是否开始或已过期
                $now = mktime();
                if(strtotime($val['gp_start_time']) > mktime()){
                    //团购未开始
                    $g_list[$key]['stat_time'] = '1';
                }elseif((strtotime($val['gp_start_time']) < mktime()) && (strtotime($val['gp_end_time'])< mktime())){
                    //团购已结束
                    $g_list[$key]['stat_time'] = '2';
                }else{
                    if($val['gp_now_number'] >= $val['gp_number']){
                        $g_list[$key]['stat_time'] = '4';//卖光了
                    }else{
                        $g_list[$key]['stat_time'] = '3';//正在团购中
                    } 
                }
            }
            $gc_list[$k]['ary_glist'] = $g_list;
        }
        
        $this->assign('gclist',$groupList);
        $this->assign('gclists',$gc_list);
        $this->assign('gblist',$gb_list);
        $this->assign('ary_ads', $ary_ad_infos);
        $this->assign('ary_brands', $brandlist);
        $this->assign('ary_glist', $g_list);
        $this->assign('ary_request', $ary_request);
        $this->assign("headerTpl",$headerTpl);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/groupbuy.html';
        $this->display($tpl);
    }

    /**
     * 团购列表页展示 
     * @author Hcaijin
     * @date 2014-07-07
     */
    public function tlists(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,'Y');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/bulkheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';

        }else{
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/bulkheader.html';
        }
        $get = $this->_get();
        unset($get['_URL_']);
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM');
        $gc_promotion = M('related_groupbuycategory_ads',C('DB_PREFIX'),'DB_CUSTOM');
        /* $ary_city = $gp_city->field('cr.cr_name,cr.cr_id')
                            ->join(C('DB_PREFIX').'city_region as cr on cr.cr_id='.C('DB_PREFIX').'related_groupbuy_area.cr_id')
                            ->join(C('DB_PREFIX').'groupbuy as gp on gp.gp_id='.C('DB_PREFIX').'related_groupbuy_area.gp_id')
                            ->where(array('gp.deleted'=>0))
                            ->group('cr_id')
                            ->select(); */
        $array_where = array('is_active'=>1,'deleted'=>0);
        $join_str = '';
        if(empty($get['gcid'])){
            $this->error('团购专题不存在！');
        }
        $gc_id = $get['gcid'];
        $gcPromotion = $gc_promotion->where(array('gc_id'=>$gc_id))->select();
        $gc_ids = D('GroupbuyCategory')->where(array('gc_parent_id'=>$gc_id,'gc_is_display'=>'1','gc_status'=>'1'))->field('gc_id,gc_name')->order('gc_order asc')->select();
        foreach($gc_ids as $ids){
            $gc_id .= ','.$ids['gc_id'];
        }
        $gc_all_ids = array('gc_id'=>$gc_id,'gc_name'=>"全部");
        array_unshift($gc_ids,$gc_all_ids);
        $arr_data = array();
        foreach($gc_ids as $gck => $ids){
            $array_where['gc_id'] = array('in',$ids['gc_id']);
            //$join_str .= C('DB_PREFIX')."groupbuy_category as gc on(gc.gc_id=".C('DB_PREFIX')."groupbuy.gc_id)";
            if($get['type']==1){
                $array_where['gp_start_time'] = array('gt',date('Y-m-d H:i:s',time()));
            }
            $count = $groupbuy->where($array_where)->count();
            $obj_page = new Pager($count, 90);
            $page = $obj_page->show();
            $pageInfo = $obj_page->showArr();
            $bulkList = $groupbuy->where($array_where)
                                 ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                                 ->order("gp_order asc,gp_create_time desc")
                                 ->select();
            //echo $groupbuy->getLastSql();exit();
            foreach ($bulkList as $key=>$val){
                $bulkList[$key]['cust_price'] = M('goods_info')->where(array('g_id'=>$val['g_id']))->getField('g_price');
                //取出价格阶级
                $rel_bulk_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
                $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
                $bulkList[$key]['gp_now_number'] = $buy_nums;
                $array_f = array();
                foreach ($rel_bulk_price as $rbp_k=>$rbp_v){
                    if($buy_nums >= $rbp_v['rgp_num']){
                        $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
                    }
                }
                if(!empty($array_f)){
                    $array_max = new ArrayMax($array_f);
                    $rgp_num = $array_max->arrayMax();
                    $bulkList[$key]['gp_price'] = $gp_price->where(array('gp_id'=>$val['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
                }
                if($_SESSION['OSS']['GY_OSS_ON'] == '1'){
                    $bulkList[$key]['gp_picture'] = $val['gp_picture'];
                }else{
                    $bulkList[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
                }
                
                //验证价格
                $i = 0;
                if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($bulkList[$key]['gp_price'] < $get['startPrice'] || $bulkList[$key]['gp_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                if(!empty($get['startPrice']) && empty($get['endPrice'])){
                    if($bulkList[$key]['gp_price'] < $get['startPrice']){
                        $i++;
                    }
                }
                if(empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($bulkList[$key]['gp_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                if($i != 0){
                    unset($bulkList[$key]);
                }else{
                    //验证区域
                    if(!empty($get['cr_id'])){
                        $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count();
                        if($result_show == 0){
                            unset($bulkList[$key]);
                        }
                    }
                }
                //判断当前团购时间是否开始或已过期
                $now = mktime();
                if(strtotime($val['gp_start_time']) > mktime()){
                    //团购未开始
                    $bulkList[$key]['stat_time'] = '1';
                }elseif((strtotime($val['gp_start_time']) < mktime()) && (strtotime($val['gp_end_time'])< mktime())){
                    //团购已结束
                    $bulkList[$key]['stat_time'] = '2';
                }else{
                    if($val['gp_now_number'] >= $val['gp_number']){
                        $bulkList[$key]['stat_time'] = '4';//卖光了
                    }else{
                        $bulkList[$key]['stat_time'] = '3';//正在团购中
                    } 
                }
                
            }
            $arr_data[$gck] = $bulkList;
        }
        if(!empty($get['cr_id'])){
            $get['cr_name'] = M('city_region')->where(array('cr_id'=>$get['cr_id']))->getField('cr_name');
        }
        //设置SEO
        $shop = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE',null,null,'Y');
        $array_seo = D('SysConfig')->getCfgByModule('BULK_LIST_SEO','Y');
        $this->setTitle('团购列表页-'.$get['name'].' - '.$shop['GY_SHOP_TITLE']['sc_value'],$array_seo['BULK_LIST_KEYWORDS'],$array_seo['BULK_LIST_DESCRIPTION']);
        
        $groupbuySet = M('groupbuy_set',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gs_id'=>1))->find();
        $price_set = unserialize($groupbuySet['gs_related_price']);
        $city_id = array();
        foreach ($bulkList as $blK=>$blV){
            $ary_citys = $gp_city->where(array('gp_id'=>$blV['gp_id']))->select();
            foreach ($ary_citys as $key=>$val){
                array_push($city_id,$val['cr_id']);
                
            }
        }
        $city_id = array_unique($city_id);
        sort($city_id);
        foreach ($city_id as $k=>$v){
            $ary_city[$k]['cr_id'] = $v;
            $ary_city[$k]['cr_name'] = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->where(array('cr_id'=>$v))->getField('cr_name');
            
        }
        $gc_list = D('GroupbuyCategory')->where(array('gc_is_display'=>'1','gc_status'=>'1'))->order('gc_order asc')->select();
        $data = $gc_ids;
        $this->assign('gclist',$gc_ids);
        $this->assign('data',$arr_data);
        $this->assign('priceSet',$price_set);
        $this->assign('gs_timeshow_status',$groupbuySet['gs_timeshow_status']);
        $this->assign('pagearr',$pageInfo);
        $this->assign('get',$get);
        $this->assign('city',$ary_city);
        $this->assign("gcp",$gcPromotion);
        $this->assign("headerTpl",$headerTpl);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/groupbuyList.html';
        $this->display($tpl);
    }

    /**
     * 团购商品详情页
     * @params 团购商品ID:gid
     * @author Hcaijin <huangcaijin@guanyisoft.com>
     * @date 2013-07-07
     */
    public function detail_bak(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,'Y');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $gp_id = $this->_get('gpid');
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $groupbuy_cat = M('groupbuy_category',C('DB_PREFIX'),'DB_CUSTOM');
        
        $groupbuy_log = M('groupbuy_log',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM');
        $array_where = array('is_active'=>1,'gp_id'=>$gp_id,'deleted'=>0);
        $data = $groupbuy->where($array_where)->find();
        $data['buy_status'] = 1;
        if(empty($data)){
            $this->error('团购商品不存在！');
        }
	    $int_count = D("Goods")->where(array('g_id'=>$data['g_id'],'g_on_sale'=>1,'g_status'=>1))->count();
        if(0 >= $int_count){
            $this->error('此商品不存或已下架！',U('Home/Products/Index'));
        }
        $data['cust_price'] = M('goods_info')->where(array('g_id'=>$data['g_id']))->getField('g_price');

        //获取团购商品类目名称
        $group_info = $groupbuy_cat->where(array('gc_id'=>$data['gc_id']))->field('gc_name,gc_parent_id')->find();
		$data['gc_name'] = $group_info['gc_name'];
		if(!empty($group_info['gc_parent_id'])){
			$data['gc_id'] = $group_info['gc_parent_id'];
		}
        //获取同类目下的其他团购商品信息
        $likeglist = $groupbuy->where(array('gc_id'=>$data['gc_id'],'is_active'=>1,'deleted'=>0,'gp_id'=>array('neq',$data['gp_id'])))->limit(6)->order('gp_create_time desc')->select();
        $glist = array();
        $count = count($likeglist);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$likeglist[$i*3+$k];
            }
        }
        //dump($glist);exit();
        $this->assign('likeglist',$glist);

        //取出价格阶级
        $rel_bulk_price = $gp_price->where(array('gp_id'=>$data['gp_id']))->order("rgp_num asc")->select();
        $data['rel_bulk_price'] = $rel_bulk_price;
        //目前已参团人数
        $buy_nums = $data['gp_pre_number'] + $data['gp_now_number'];
        $array_f = array();
        $i = -1;
        foreach ($data['rel_bulk_price'] as $rbp_k=>$rbp_v){
            if($buy_nums >= $rbp_v['rgp_num']){
                $i++;
                $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
            }
        }
        if(!empty($data['rel_bulk_price']) && $i != -1){
            $data['rel_bulk_price'][$i]['is_on'] = 1;
        }
        if(!empty($array_f)){
            $array_max = new ArrayMax($array_f);
            $rgp_num = $array_max->arrayMax();
            $data['gp_price'] = $gp_price->where(array('gp_id'=>$data['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
        }
        $data['gp_overdue_start_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_start_time']));
        $data['gp_overdue_end_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_end_time']));
        //判断当前团购数量是否达到上限（总限量）
        if($data['gp_now_number'] == $data['gp_number'] || $data['gp_now_number'] > $data['gp_number']){
            $data['gp_number'] = 0;
            $data['buy_status'] = 0;
        }else{
            $m_id = $_SESSION['Members']['m_id'];
            if($m_id){
                //目前可以购买的数量
                $thisGpNums = $data['gp_number'] - $data['gp_now_number'];
                //当前会员已购买数量
                //$member_buy_num =  $groupbuy_log->field(array('SUM(num) as buy_nums'))->where(array('m_id'=>$m_id,'gp_id'=>$data['gp_id']))->find();
				$member_buy_num =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
				->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
				->where(array('fx_orders.m_id'=>$m_id,'fx_orders_items.fc_id'=>$data['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders.o_status'=>array('neq',2),'fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
				->find();
                //如果会员限购数量大于当前会员已购买数量
                if($data['gp_per_number'] > $member_buy_num['buy_nums']){
                    //当前会员最多可以购买的数量
                    $gp_number = $data['gp_per_number'] - $member_buy_num['buy_nums'];
                    //如果会员最多可以购买的数量大于目前库存，将库存赋予会员购买数量
                    if($gp_number > $thisGpNums){
                        $gp_number = $thisGpNums;
                    }
                    //将会员可以购买数量存入gp_number中
                    $data['gp_number'] = $gp_number;
                    $data['buy_status'] = 1;
                }else{
                    //卖光了或购买数量已达上限
                    $data['gp_number'] = 0;
                    $data['buy_status'] = 0;
                }
            }else{
                $data['gp_number'] = $data['gp_per_number'];
                $data['buy_status'] = 1;
            }
        }
        
        //判断当前团购时间是否开始或已过期
        $now = mktime();
        if(strtotime($data['gp_start_time']) > mktime()){
            //团购未开始
            $data['stat_time'] = '1';
        }elseif((strtotime($data['gp_start_time']) < mktime()) && (strtotime($data['gp_end_time'])< mktime())){
            //团购已结束
            $data['stat_time'] = '2';
        }else{
            $data['stat_time'] = '3';//正在团购中
        }
        if($data['is_deposit'] == 0){
            $data['gp_deposit_price'] = 0.00;
        }
        $ary_request = $this->_request();
        $ary_request['gid'] = $data['g_id'];
        $this->assign('ary_request',$ary_request);
        //dump($data);exit();
		$this->assign($data);
        $common = D('SysConfig')->getCfgByModule('goods_comment_set','1');
        //echo "<pre>";print_r($common);exit;
        $this->assign("city",$city);
        $this->assign("common",$common);
        $this->assign("citys",$ary_city);
        $shop = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE',null,null,'Y');
        $this->setTitle('【今日推荐】 '.$data['gp_title'].' - '.$shop['GY_SHOP_TITLE']['sc_value'],$data['gp_keywords'],$data['gp_description']);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/groupbuyDetails.html';
		$int_port = "";
		if($_SERVER["SERVER_PORT"] != 80){
			$int_port = ':' . $_SERVER["SERVER_PORT"];
		}
		$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
		$this->assign("redirect_url",urlencode($string_request_uri));
		$this->display($tpl);
    }

    /**
     * 团购详情页
     *
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function detail(){

        $gp_id = $this->_get('gpid');
        $members = session('Members');
        $ary_goods_pdts = D('Groupbuy')->getDetails($gp_id, $members);
//        dump($ary_goods_pdts);die;
		$groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
		$array_where = array('is_active'=>1,'gp_id'=>$gp_id,'deleted'=>0);
        $data = $groupbuy->where($array_where)->find();
		$ary_request = $this->_get();
		$ary_request['gid'] = $data['g_id'];
        $this->assign($ary_goods_pdts);
        $this->assign('page_title', '【今日推荐】 '.$ary_goods_pdts['gp_title']);
        $this->assign('ary_request', $ary_request);
        $goods_detail_include =  C('TMPL_PARSE_STRING.__TPL__') . 'bulkDetailSku.html';
        if(!file_exists(FXINC . $goods_detail_include)) {
            $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'goodsDetailSku.html';
        }
        $this->assign('goods_detail_include', '.'.$goods_detail_include);
        $tpl = '.'. C('TMPL_PARSE_STRING.__TPL__') . '/groupbuyDetails.html';
        $this->display($tpl);
    }
    /**
     * 团购验证码
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-31
     */
    public function verify() {
        import('ORG.Util.Image');
		// ob_end_clean();
        //Image::buildImageVerify();
		Image::GBVerify(4, $type='png',180, 50, $fontface='./Public/Uploads/simhei.ttf', $verifyName='code_verify');
    }
	/**
     * 验证验证码是否正确
     * @author wangguibin
     * @date 2013-04-11
     */
    public function checkVerify() {
       if ($_COOKIE['verify'] != md5($this->_post('verify'))) {
            $this->ajaxReturn(array('status'=>false));
        } else {
            $this->ajaxReturn(array('status'=>true));
        }
    }
}

