<?php
/**
 * 前台秒杀类
 *
 * @stage 7.4
 * @package Action
 * @subpackage Home
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-11-21
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class SpikeAction extends HomeAction{
    
    /**
     * 控制器初始化
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-21
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 前台秒杀列表页
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-11-21
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
        $get = $this->_get();
        $mod = $this->getActionName();
        $spike = D($mod);
        $sp_city = M('related_spike_area',C('DB_PREFIX'),'DB_CUSTOM');
        //所有秒杀城市id
        $ary_city = $sp_city->field('cr.cr_name,cr.cr_id')
                            ->join(C('DB_PREFIX').'city_region as cr on cr.cr_id='.C('DB_PREFIX').'related_spike_area.cr_id')
                            ->group('cr_id')
                            ->select();
        $array_where = array('sp_status'=>1,
                            // 'sp_start_time'=>array('lt',date('Y-m-d H:i:s')),
                             'sp_end_time'=>array('gt',date('Y-m-d H:i:s')));
        if($get['startPrice']>=0 && isset($get['startPrice'])){
            if(!empty($get['startPrice']) && $get['startPrice'] >= '5000'){
                $array_where['sp_price'] = array("EGT",$get['startPrice']);
            }else{
                $array_where['sp_price'] = array("between",array($get['startPrice'],$get['endPrice']));
            }
        }
        if(!empty($get['scid'])){
            $array_where['gc_id'] = $get['scid'];
        }
        $count = $spike->where($array_where)->count();
        $obj_page = new Pager($count, 12);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $spikeList = $spike->where($array_where)
                             ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                             ->select();
        //echo $spike->getLastSql();exit();
        foreach ($spikeList as $ky=>$kv){
			//七牛图片显示
			$spikeList[$ky]['sp_picture'] = D('QnPic')->picToQn($kv['sp_picture']); 
            $goods_info = D('Goods')->where(array('g_id'=>$kv['g_id']))->count();
            if($goods_info == 0){
                unset($spikeList[$ky]);
            }
            $spikeList[$ky]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$kv['g_id']))
                ->order('pdt_sale_price asc')->getField('pdt_sale_price');
            //秒杀价格
            switch($kv['sp_tiered_pricing_type']) {
                //直接减优惠金额
                case 1:
                    $pdt_final_price = $sale_price - $kv['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $spikeList[$ky]['sp_price'] = $pdt_final_price;
                    break;
                //设置优惠折扣
                case 2:
                    $pdt_final_price = $sale_price * $kv['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $spikeList[$ky]['sp_price'] = $pdt_final_price;
                    break;
            }
        }
        //验证区域
        if(!empty($spikeList) && is_array($spikeList)){
            foreach($spikeList as $key=>$val){
                if(!empty($get['cr_id'])){
                    $result_show = $sp_city->where(array('cr_id'=>$get['cr_id'],'sp_id'=>$val['sp_id']))->count();
                    if($result_show == 0){
                        unset($spikeList[$key]);
                    }
                }
            }
        }
        //获取秒杀商品规格属性
        $goodsSpec = D('GoodsSpec');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        foreach($spikeList as $k=>$val){
            $tag['gid'] = $val['g_id'];
            $info = D('ViewGoods')->goodDetail($tag);
            if(!empty($info) && is_array($info)){
                $spikeList[$k]['detail'] = $info['list'];
            }
        }
        //秒杀类目获取
        $spikeCat = M('spike_category',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_cat_sp = $spikeCat->field('gc_name,gc_id')->where(array('gc_is_display'=>1,'gc_status'=>1))->limit('0,10')->order('gc_order asc')->select();
        $this->assign('sp_cat',$ary_cat_sp);
		//取出关联广告图片
		$ary_ads = D('RelatedSpikeAds')->order('sort_order asc')->select();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			//七牛图片显示
			$ary_ad['ad_pic_url'] = D('QnPic')->picToQn($ary_ad['ad_pic_url']); 
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        $this->assign('ary_ads', $ary_ad_infos);

        $this->assign('city',$ary_city);
        $this->assign('pagearr',$pageInfo);
        //dump($spikeList);exit();
        $this->assign('data',$spikeList);
        $this->assign('get',$get);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/spikeList.html';
		$title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE','','',1);
		$this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . '- 今日秒杀推荐');
        $this->display($tpl);
    }
    
    /**
     * 前台秒杀详情页
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-11-28
     */
    public function detail(){
        $ary_request = $this->_request();
        $int_sp_id = (int)$ary_request['sp_id'];
        $ary_members = session('Members');
        $spikeModel = D("Spike");

        $ary_details = $spikeModel -> getDetails($int_sp_id, $ary_members);
        

        $g_id = $ary_details['g_id'];
        $where = array('fx_goods_info.g_id' => $g_id);
        $field=array('fx_goods_info.g_picture','fx_goods_info.g_desc');
        $ary_details['rGoods'] = D("GoodsInfo")->Search($where);
        //dump($ary_details);
        //die;
        $this->assign($ary_details);
        $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'spikeDetailSku.html';
        if(!file_exists(FXINC. $goods_detail_include)) {
            $goods_detail_include = C('TMPL_PARSE_STRING.__TPL__') . 'goodsDetailSku.html';
        }
        $this->assign('goods_detail_include', '.'. $goods_detail_include);
        $this->assign("ary_request", $ary_request);


        //获取同类秒杀
        $where['gc_id'] = $ary_details['gc_id'];
        $where['sp_status'] = 1;
        $where['sp_id'] = array('neq',$ary_details['sp_id']);
        $likeglist = $spikeModel->where($where)->limit(0,6)->order('sp_start_time desc')->select();
        $glist = array();
        $count = count($likeglist);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$likeglist[$i*3+$k];
            }
        }
        $this->assign("likeglist",$glist);
		

//		echo "<pre>";print_r($ary_details);die;
		if($ary_details['buy_status'] == 0){
			$is_spike = 1;
		}else{
			$is_spike = 0;
		}
		$this->assign('is_spike',$is_spike);
		
        //温馨提示
        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
        $this->assign('warm_prompt',$warm_prompt['sc_value']);

        $this->setTitle('商品秒杀页'.$ary_details['sp_title'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
        $tpl = '.'. C('TMPL_PARSE_STRING.__TPL__') .'/spikeDetail.html';
        $this->display($tpl);
    }
    
    /**
     * 获取访问量信息
     *
     * @param boolean $increase 是否将访问量增加1
     * @return integer
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-23
     */
    function getPageView($gid) {
        //$key_obj = M('keystore',C('DB_PREFIX'),'DB_CUSTOM');
        if (!empty($gid)) {
            try {
                $row = M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->where(array("g_id" => $gid))->find();
                if ($row) {
                    $update_data['value'] = $row['value'] + 1;
                    $update_data['modify_time'] = date("Y-m-d H:i:s");
                    M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $gid))->save($update_data);
                } else {
                    $insert_data['g_id'] = $gid;
                    $insert_data['value'] = 1;
                    $insert_data['create_time'] = date("Y-m-d H:i:s");
                    $insert_data['modify_time'] = date("Y-m-d H:i:s");
                    M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->add($insert_data);
                }
            } catch (Exception $e) {
                //
            }
            return (int) ($row['value'] + 1);
        }
    }
    
    /**
     * 记录会员访问历史
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-18
     */
    public function updcookie($gid) {
		$expire = time() + $this->history_expire;
		//达到最大显示数目时就更新现有数据
		$ary_cookie_items = cookie('HistoryItems');
		if(!empty($ary_cookie_items)){
			$count = count($ary_cookie_items);
			if($count>=$this->max_num){
				$first_iid = array_keys(array_splice(cookie('HistoryItems'),1));
				cookie("HistoryItems[$first_iid[0]]",null);
			}
		}
 		cookie("HistoryItems[$gid]", $gid, $expire);
    }
    
    /**
     * 删除浏览历史
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-19
     */
    public function deleteBrowsehistory(){
    	$gid = $this->_post('gid');
    	cookie($gid,null);
    }
    
    /**
     * 每个会员商品浏览历史的统计
     * @author Wangguibin
     * @date 2013-11-18
     */
    public function BrowsehistoryCount($limit) {
    	$gids = array();
    	if(isset($_COOKIE['HistoryItems']))
    	{
    		foreach($_COOKIE['HistoryItems'] as $key=>$iid){
    			$gids[] = $key;
    		}
    	}
    	$where['fx_goods.g_id'] = array('in',$gids);
    	$where['fx_goods.g_on_sale'] = 1;
    	$ary_res = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
    	->field('fx_goods_info.g_name,fx_goods_info.g_id,fx_goods_info.g_picture,fx_goods_info.g_price')
    	->join('fx_goods on fx_goods_info.g_id = fx_goods.g_id')
    	->where($where)->limit($limit)->select();
    	return $ary_res;
    }
    
    /**
     * 获取商品类目
     * @author Wangguibin
     * @date 2013-11-21
     */
    public function getCateParent($gid){
    	$condition = array();
    	$condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
    	$ary_goods=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
    	->field(array(C('DB_PREFIX').'goods_category.gc_id',C('DB_PREFIX').'goods_category.gc_parent_id'))
    	->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
    	->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
    	->where($condition)->find();
    	if($ary_goods['gc_parent_id']){
    		return $ary_goods['gc_parent_id'];
    	}else{
    		return $ary_goods['gc_id'];;
    	}
    }
	
	/**
     * 首页广告秒杀商品
     *
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-01-10
     */
    public function indexSpike(){
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
		$array_where = array(
			'sp_status'=>1,
            'sp_start_time'=>array('lt',date('Y-m-d H:i:s')),
            'sp_end_time'=>array('gt',date('Y-m-d H:i:s'))
		);
        if(!empty($get['startPrice'])){
            if(!empty($get['startPrice']) && $get['startPrice'] >= '5000'){
                $array_where['sp_price'] = array("EGT",$get['startPrice']);
            }else{
                $array_where['sp_price'] = array("between",array($get['startPrice'],$get['endPrice']));
            }
        }

        $spikeList = D("Spike")->where($array_where)->limit(5)->select();
		$this->assign('data',$spikeList);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/spike.html';
        $this->display($tpl);
	}
}
