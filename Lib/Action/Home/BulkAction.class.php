<?php
/**
 * 前台团购类
 *
 * @stage 7.4
 * @package Action
 * @subpackage Home
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-08-28
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class BulkAction extends HomeAction{
    /**
     * 控制器初始化
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-28
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 前台团购列表页
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-29
     */
    public function Index(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN','','',1);
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
        $gp_ads = M('related_groupbuy_ads',C('DB_PREFIX'),'DB_CUSTOM');  //团购广告图
        $adslist = $gp_ads->select();
        $this->assign('adslist',$adslist);
        //所有团购城市id
         $ary_city = $gp_city->field('cr.cr_name,cr.cr_id')
                            ->join(C('DB_PREFIX').'city_region as cr on cr.cr_id='.C('DB_PREFIX').'related_groupbuy_area.cr_id')
                            ->join(C('DB_PREFIX').'groupbuy as gp on gp.gp_id='.C('DB_PREFIX').'related_groupbuy_area.gp_id')
                            ->where(array('gp.deleted'=>0))
                            ->group('cr_id')
                            ->select(); 
        $array_where = array('is_active'=>1,
                             'deleted'=>0,
                             'gp_start_time'=>array('lt',date('Y-m-d H:i:s')),
                             'gp_end_time'=>array('gt',date('Y-m-d H:i:s')));
        $count = $groupbuy->where($array_where)->count('gp_id');
        $obj_page = new Pager($count, 12);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $tag['order'] = isset($get['order']) ? $get['order'] : '';
        switch ($tag['order']) {
            case 'new':
                $order = '`gp_update_time` asc';
                break;
            case '_new':
                $order = '`gp_update_time` desc';
                break;
           /* case 'price':
                $order = '`gp_price` asc';
                break;
            case '_price':
                $order = '`gp_price` desc';
                break;*/
            case 'hot':
                $order = '`gp_now_number` asc';
                break;
            case '_hot':
                $order = '`gp_now_number` desc';
                break;
            default:
                $order = '`gp_order` desc';
        }
        $bulkList = $groupbuy->where($array_where)
                             ->order($order)
                             ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                             ->select();
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
            
        }//echo "<pre>";print_r($bulkList);print_r($get);exit;
        if(!empty($get['cr_id'])){
            $get['cr_name'] = M('city_region')->where(array('cr_id'=>$get['cr_id']))->getField('cr_name');
        }
        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE','','',1);
        $this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . '- 今日团购推荐');
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
        
        $this->assign('data',$bulkList);
        $this->assign('priceSet',$price_set);
        $this->assign('gs_timeshow_status',$groupbuySet['gs_timeshow_status']);
        $this->assign('pagearr',$pageInfo);
        $this->assign('get',$get);
        $this->assign('city',$ary_city);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/bulkList.html';
        $this->display($tpl);
    }
    
    /**
     * 团购详情页
     *
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function detail(){

        $gp_id = $this->_get('gp_id');
        $members = session('Members');
        $ary_goods_pdts = D('Groupbuy')->getDetails($gp_id, $members);
//        dump($ary_goods_pdts);die;
        $this->assign($ary_goods_pdts);
        $this->assign('page_title', '【今日推荐】 '.$ary_goods_pdts['gp_title']);
        $this->assign('ary_request', $this->_get());
        $goods_detail_include =  C('TMPL_PARSE_STRING.__TPL__') . 'bulkDetailSku.html';
        if(!file_exists(FXINC . $goods_detail_include)) {
            $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'goodsDetailSku.html';
        }
        $this->assign('goods_detail_include', '.'.$goods_detail_include);
        $tpl = '.'. C('TMPL_PARSE_STRING.__TPL__') . '/bulkDetail.html';
        $this->display($tpl);
    }
	
}
