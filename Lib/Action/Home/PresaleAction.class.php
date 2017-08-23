<?php
/**
* 前台预售类
* @package Action
* @subpackage Home
* @author WangHaoYu <wanghaoyu@guanyisoft.com>
* @date 2013-11-29 
* @version 7.4.5
*/
class PresaleAction extends HomeAction {
    /**
    * 控制器初始化
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @date 2013-11-29 
    * @version 7.4.5
    */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
    * 预售商品列表页
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @date 2013-11-29 
    * @version 7.4.5
    */
    public function index() {
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
        $ary_get = $this->_get();
        unset($ary_get['_URL_']);
        $presale = M('presale',C('DB_PREFIX'),'DB_CUSTOM');
        $p_price_model = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
        $presale_city = M('related_presale_area',C('DB_PREFIX'),'DB_CUSTOM');
        $presale_price = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
        //获取所有预售的城市
        $ary_city = $presale_city->field('cr.cr_name,cr.cr_id')
                        ->join(C('DB_PREFIX'). 'city_region as cr on cr.cr_id = ' . C('DB_PREFIX').'related_presale_area.cr_id')
                        ->join(C('DB_PREFIX').'presale as p on p.p_id='.C('DB_PREFIX').'related_presale_area.p_id')
                        ->where(array('p.p_deleted'=>0))
                        ->group('cr_id')
                        ->select();
        $ary_where = array(
            'p_deleted'=>0,
            'is_active'=>1,
            'p_start_time'=>array('lt',date('Y-m-d H:i:s')),
            'p_end_time'=>array('gt',date('Y-m-d H:i:s'))
        );
        $int_count = $presale->where($ary_where)->count();
        $obj_page = new Pager($int_count,15);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $order = array('p_order'=>"asc",'p_update_time'=>"desc");
        $limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $presaleList = $presale
                        ->where($ary_where)
                        ->order($order)
                        ->limit($limit)
                        ->select();
        foreach($presaleList as $key=>$val) {
			//七牛图片显示
			$presaleList[$key]['p_picture'] = D('QnPic')->picToQn($val['p_picture']); 
            $presaleList[$key]['cost_price'] = M('goods_info')->where(array('g_id'=>$val['g_id']))->getField('g_price');
            //已预购数量 = 虚拟数量+实际销售量
            $buy_nums = $val['p_pre_number'] + $val['p_now_number'];
            //取出价格关联表 价格阶级
            $ary_range_price = $p_price_model->where(array('p_id'=>$val['p_id']))->select();
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
                $presaleList[$key]['p_price'] = $p_price_model->where( array( 'p_id'   => $val['p_id'],
                    'rgp_num' => $current_num_range
                ) )->getField( 'rgp_price' );
            }
            $presaleList[$key]['p_now_number'] = $buy_nums;
            $presaleList[$key]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$val['g_id']))->order('pdt_sale_price asc')->getField('pdt_sale_price');
            switch($val['p_tiered_pricing_type']) {
                case 2:
                    $presaleList[$key]['p_price'] = $sale_price*$presaleList[$key]['p_price'];
                    break;
                default:
                    $presaleList[$key]['p_price'] = $sale_price - $presaleList[$key]['p_price'];
                    break;
            }
            //验证价格 $i为标识符
            $i = 0;
            if(!empty($ary_get['startPrice']) && !empty($ary_get['endPrice'])){
                if($presaleList[$key]['p_price'] < $ary_get['startPrice'] || $presaleList[$key]['p_price'] > $ary_get['endPrice']){
                    $i++;
                }
            }
            if(!empty($ary_get['startPrice']) && empty($ary_get['endPrice'])){
                if($presaleList[$key]['p_price'] < $ary_get['startPrice']){
                    $i++;
                }
            }
            if(empty($ary_get['startPrice']) && !empty($ary_get['endPrice'])){
                if($presaleList[$key]['p_price'] > $ary_get['endPrice']){
                    $i++;
                }
            }
            if(0 != $i){
                unset($presaleList[$key]);
            }else{
                //验证区域
                if(!empty($ary_get['cr_id'])){
                    $ary_return = $presale_city->where(array('cr_id'=>$ary_get['cr_id'],'p_id'=>$val['p_id']))->count();
                    if(0 == $ary_return){
                        unset($presaleList[$key]);
                    }
                }
            }
            
        }
        if(!empty($ary_get['cr_id'])){
            $ary_get['cr_name'] = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->where(array('cr_id'=>$ary_get['cr_id']))->getField('cr_name');
        }
        $presaleSet = M('presale_set',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ps_id'=>1))->find();
        $price_set = unserialize($presaleSet['ps_price_range']);
        $this->assign('page_title', '预售推荐列表页');
        $this->assign('city',$ary_city);
        $this->assign('priceSet',$price_set);
        $this->assign('data',$presaleList);
        $this->assign('get',$ary_get);
        $this->assign('page',$pageInfo);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/presaleList.html';
        $this->display($tpl);
    }
    
    /**
    * 预售商品详情页
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @date 2013-11-29 
    * @version 7.4.5
    */
    public function detail() {
        $p_id = $this->_get('p_id');
        $members = session('Members');
        $ary_goods_pdts = D('Presale')->getDetails($p_id, $members);

        $this->assign($ary_goods_pdts);
        $this->assign('page_title', '【今日推荐】 '.$ary_goods_pdts['p_title']);
        $this->assign('ary_request', $this->_get());
        $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'presaleDetailSku.html';
        if(!file_exists(FXINC. $goods_detail_include)) {
            $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'goodsDetailSku.html';
        }
        $this->assign('goods_detail_include', '.'. $goods_detail_include);
        $sysSetting = D('SysConfig');
        $sys_config = $sysSetting->getConfigs('GY_GOODS');
        $is_on_mulitiple = empty($sys_config['IS_ON_MULTIPLE']['sc_value']) ? 2: $sys_config['IS_ON_MULTIPLE']['sc_value'];
        $this->assign('is_on_mulitiple', $is_on_mulitiple);
        $tpl = '.'. C('TMPL_PARSE_STRING.__TPL__') . '/presaleDetail.html';
		//echo "<pre>";print_r($ary_goods_pdts);die;
        $this->display($tpl);

    }

}