<?php

/**
 * 前台商品展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-03-28
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ProductsAction extends HomeAction {
	
	protected $history_expire = 86400;	//默认浏览商品历史保存24小时
	protected $max_num = 10;			//默认浏览商品最多保存10个
    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 列表页
     * @author wangguibin
     * @date 2012-04-11
     */
    public function index() {

        //显示页面
        $ary_request = $this->_get();
        unset($ary_request['_URL_']);
        //取消商品类型后
        if(empty($ary_request['tid'])) {
            foreach($ary_request as $name=>$value) {
                //规格筛选
                $spec_id = strstr($name, 'path_');
                if($spec_id) {
                    unset($ary_request[$name]);
                }
            }
        }

        if(isset($ary_request['keyword'])){
			$ary_request['keyword'] = urldecode(base64_decode($ary_request['keyword']));
		}

        if(!empty($ary_request['keyword'])){
            $encode = mb_detect_encoding($ary_request['keyword'], array("ASCII","UTF-8","GB2312","GBK","BIG5","MBCS"));
            if($encode == 'UTF-8'){
                $ary_request['keyword'] = $ary_request['keyword'];
            }else{
                if($encode != 'UTF-8'){
                    $ary_request['keyword'] = iconv($encode, 'UTF-8', $ary_request['keyword']);
                }
            }
            $ary_request['gname'] = $ary_request['keyword'];
        }

        $ary_request_new = $ary_request;
		//数据处理
        $ary_path = array();
        foreach($ary_request as $key => &$str_info){
            //规格筛选
            $spec_id = strstr($key, 'path_');
            if($spec_id) {
                $ary_path[] = urldecode($str_info);
                unset($ary_request[$key]);
                continue;
            }
            //价格区间
            if($key == 'price') {
                $ary_price = explode('-', $str_info);
                $ary_request['startprice'] = floatval($ary_price[0]);
                $ary_request['endprice'] = floatval($ary_price[1]);
            }
            //字符串
			if(is_string($str_info)){
				$str_info = htmlspecialchars($str_info);
				$str_info = RemoveXSS($str_info);				
			}
        }
        //如果同时存在属性id和类型id，判断该属性是否属于该类型，只保留该类型关联属性
        if((int)$ary_request['tid'] && !empty($ary_path)) {
            $ary_spec = D('RelatedGoodsTypeSpec')->where(array(
                'gt_id' => (int)$ary_request['tid'],
                'gs_id' => array('in', $ary_path)
            ))->select();
            $ary_path_new = array();
            $ary_spec || $ary_spec = array();
            foreach($ary_spec as $spec) {
                $ary_path_new[] = $spec['gs_id'];
            }
            foreach($ary_path as $k=>$spec) {
                $ary_spec_value = explode(':', $spec);
                if(!in_array($ary_spec_value[0], $ary_path_new)) {
                    unset($ary_path[$k]);
                    $sp = $ary_spec_value[0];
                    unset($ary_request_new['path_'.$sp]);
                }
            }

        }
        if(!empty($ary_path)) {
            $ary_request['path'] = implode(',', $ary_path);
        }
        //dump($ary_request);die;

		if(!empty($ary_request['cid'])){
			$gc_info = D('Gyfx')->selectOneCache('goods_category','gc_name,gc_title,gc_keyword,gc_description', array('gc_id'=>$ary_request['cid']));
			$gc_name = $gc_info['gc_name'];
		}
		//创建模板
		if(!empty($ary_request['bid'])){			
			$gb_info = D('Gyfx')->selectOneCache('goods_brand','gb_name,gb_banner,gb_tpl,gb_title,gb_keywords,gb_detail', array('gb_id'=>$ary_request['bid']));
			$gb_name = $gb_info['gb_name'];
			$this->assign('brand_data',$gb_info);
            if($gb_info['gb_tpl']){
                $tpl = './Public/Tpl/' . CI_SN . '/diy/'.$gb_info['gb_tpl'];
            }
            //var_dump($gb_info);
		}
        $breadcrumb = '全部商品';
        if(!empty($ary_request['tid'])) {
            $gt_info = D('Gyfx')->selectOneCache('goods_type','gt_name', array('gt_id'=>$ary_request['tid']));
            $gt_name = $gt_info['gt_name'];
            $breadcrumb = $gt_name;
            $this->assign('tid',$ary_request['tid']);
            $this->assign('gt_name',$gt_name);
        }

        if(empty($gc_name) && isset($ary_request['keyword'])){
            $this->setTitle($ary_request['keyword'],'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && empty($ary_request['bid'])){
            $this->setTitle("全部分类",'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && isset($ary_request['bid'])){
            $this->assign('page_title', $gb_info['gb_title']);
            $this->assign('page_keywords', $gb_info['gb_keywords']);
            $this->assign('page_description', $gb_info['gb_detail']);
        }elseif(empty($ary_request['bid']) && isset($ary_request['cid'])){
            //
            //$this->setTitle($gc_name,$gc_info['gc_title'],$gc_info['gc_description'],$gc_info['gc_keyword']);

            $this->assign('page_title', $gc_info['gc_title']);
            $this->assign('page_keywords', $gc_info['gc_keyword']);
            $this->assign('page_description', $gc_info['gc_description']);
        }elseif(isset($ary_request['bid']) && isset($ary_request['cid'])){
            $this->assign('page_title', $gb_info['gb_title']);
            $this->assign('page_keywords', $gb_info['gb_keywords']);
            $this->assign('page_description', $gb_info['gb_detail']);
        }
        if(TPL == 'purple' || TPL == 'bimai') {
			$ary_request['pagesize'] = 20;
            $ary_search_spec = array();
            $tid = (int)$ary_request['tid'];
            if ($tid > 0) {
                $ary_spec_list = D('GoodsSpecDetail')->getIsSearchSpec($tid);
//                dump($ary_spec_list);die;
                foreach ($ary_spec_list as $spec_detail) {
                    $ary_search_spec[$spec_detail['gs_id']][$spec_detail['gsd_id']] = array(
                        'gsd_value' => $spec_detail['gsd_value'],
                        'gs_name' => $spec_detail['gs_name'],
                    );
                }
            }
			$type_where = array(
                'gt_type' => 0
            );
            $ary_type_list = D('GoodsType')->getGoodsType($type_where);
            $this->assign('ary_type_list', $ary_type_list);

            $this->assign('ary_search_spec', $ary_search_spec);
            $ary_goods_list = D('ViewGoods')->goodList($ary_request);
            $ary_gid = array();
            foreach($ary_goods_list['list'] as $goods) {
                $ary_gid[] = $goods['gid'];
            }

            $ary_cs = array();
            if(!empty($ary_gid)) {
                //获取商品评论统计
                $ary_comment_statistics = M('goods_comment_statistics')->where(array(
                    'g_id' => array('in',$ary_gid)
                ))->select();
                //dump(M('goods_comment_statistics')->getLastSql());die;
                if(!empty($ary_comment_statistics)) {
                    //遍历商品评论
                    foreach ($ary_comment_statistics as $comment_statistics) {
                        $ary_cs[$comment_statistics['g_id']] = $comment_statistics;
                    }
                    //整合评论信息到商品信息中
                    foreach ($ary_goods_list['list'] as &$goods) {
                        $goods['comment_statistics'] = $ary_cs[$goods['gid']];
                    }
                }
            }
			if($ary_request['cid']!=""){//获取类目关联商品图片
                $field=array('ad_url,ad_pic_url');
                $rgc_where=array('gc_id'=>$ary_request['cid']);
                $rgc_order="sort_order desc";
                $ary_ads = D('RelatedGoodscategoryAds')->getListByCid($rgc_where,$field,$rgc_order);
                $this->assign('ary_ads', $ary_ads);
            }
            $this->assign('ary_goods_list', $ary_goods_list['list']);
            $this->assign('ary_page', $ary_goods_list['pagearr']);
            $this->assign("ary_request", json_encode($ary_request_new));
        }
        else {
            if(isset($ary_request['path'])){
                $ary_request['path'] = !empty($ary_request['path'])?$ary_request["path"]:'';
            }
            if(!empty($ary_request['path'])){
                $arr_path = explode(",", trim($ary_request['path'],","));
                if(!empty($arr_path) && is_array($arr_path)){
                    foreach($arr_path as $ky=>$vl){
                        $paths = explode(":",$vl );
                        $ary_request['paths'][$paths[0]] = $paths[1];
                    }
                }
            }
            if(empty($ary_request['cid'])){
                unset($ary_request['cid']);
            }
            //获取会员浏览历史(5条)
            $ary_history_data = $this->BrowsehistoryCount(5);
            if($ary_request['cid']!=""){//获取类目关联商品图片
                $field=array('ad_url,ad_pic_url');
                $rgc_where=array('gc_id'=>$ary_request['cid']);
                $rgc_order="sort_order desc";
                $ary_ads = D('RelatedGoodscategoryAds')->getListByCid($rgc_where,$field,$rgc_order);
                $this->assign('ary_ads', $ary_ads);
            }
            $this->assign('ary_history_data',$ary_history_data);
            $this->assign("ary_request", $ary_request);
        }
        $ary_return = array();
        if(isset($ary_request['price'])){
            $ary_return['price'][0] = $ary_request['price'];
            $ary_return['price'][1] = $ary_request['price_col'];
        }
        if(isset($ary_request['hot'])){
            $ary_return['hot'][0] = $ary_request['hot'];
            $ary_return['hot'][1] = $ary_request['hot_col'];
        }
        if(isset($ary_request['new'])){
            $ary_return['new'][0] = $ary_request['new'];
            $ary_return['new'][1] = $ary_request['new_col'];
        }
        if(isset($ary_request['discount'])){
            $ary_return['discount'][0] = $ary_request['discount'];
            $ary_return['discount'][1] = $ary_request['discount_col'];
        }
		$member = session('Members');
        $this->assign('member',$member);
        $this->assign('ret',$ary_return);
        $this->assign('breadcrumb', $breadcrumb);
        $this->assign('itemInfo', $ary_request);

        if(empty($tpl)){
             if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
                $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/productList.html';
            } else {
                $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/productList.html';
            }           
        }
        /*创建模板*/
        if($_GET['v']==2){
             if(!$gb_info['gb_tpl']){
                $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/productList-v2.html';
             }

            $ApiUtil = D("ApiUtil");

            $brandList = $ApiUtil->GetBrandList(ture);
            $functionList = $ApiUtil->GetFunctionList(ture);
            $categoryList = $ApiUtil->GetCategoryList(ture);
            $countryList = $ApiUtil->GetCountryList(ture);

            $this->assign("brandList",$brandList);
            $this->assign("functionList",$functionList);
            $this->assign("categoryList",$categoryList);
            $this->assign("countryList",$countryList);
             
             $this->assign("v",'-v2');
             $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
             $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
             $this->assign("headerTpl",$headerTpl);
             $this->assign("footerTpl",$footerTpl);
        }
        $this->display($tpl);
    }

    /**
     * 品牌搜索页
     * @author zhuwenwei
     * @date 2015-11-16
     */
    public function search() {
        //显示页面
        $ary_request = $this->_get();
        unset($ary_request['_URL_']);
		
		$this->setTitle('品牌搜索页');
		$this->assign("ary_request", $ary_request);

        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/productSearch.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/productSearch.html';
        }
        $this->display($tpl);
    }
	
    /**
     * 商品详情页
     * @params 商品ID:gid
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-16
	 * @modify wanghaoyu 添加 获取积分设置比率
     */
    public function detail() {
		$ary_request = $this->_request();
        //数据处理
        foreach($ary_request as &$str_info){
        	$str_info = htmlspecialchars($str_info);
        }
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,1);
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
		
		//模糊库存提示
		$member = session('Members');
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $member_level_id = $member['member_level']['ml_id'];
		if(empty($member)){
			$member_level_id = 0;
		}

        if((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK']==1 ){
            if($stock_data['USER_TYPE']=='all'){
                $stock_data['level'] =true;
            }else{
                $ary_user_level =explode(",",$stock_data['USER_TYPE']);
                $stock_data['level'] = in_array($member_level_id,$ary_user_level);
            }
        }		
	//dump($stock_data['level']);die();
		//echo "<pre>";print_r($stock_data['level']);die;
		$this->assign("stock_data", $stock_data);
		
        $ary_pointresule = D('PointConfig')->getConfigs(null,true);
        $skudata = D('SysConfig')->getCfgByModule('GY_STOCK',1);
        $this->assign("skudata",$skudata);
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
        if ($_SESSION['Members']['m_id']) {
            $m_id = $_SESSION['Members']['m_id'];
			//未授权的商品不显示
			$check_authorizeline=D('AuthorizeLine')->isAuthorize($m_id, $ary_request['gid'],1);
			if(empty($check_authorizeline)){
				$this->error('此商品没有权限购买！',U('Home/Products/Index'));
			}
        }
        if($_SESSION['city']['cr_id']){
            $cr_id = $_SESSION['city']['cr_id'];
        }
        $ary_city['city'] = D('Gyfx')->selectOneCache('city_region',$ary_field=null, array("cr_status"=>'1',"cr_id"=>$cr_id), '`cr_name` ASC');
		//$action->where(array("cr_status"=>'1',"cr_id"=>$cr_id))->order('`cr_name` ASC')->find();
        $ary_city['province'] = D('Gyfx')->selectOneCache('city_region',$ary_field=null,array("cr_status"=>'1',"cr_id"=>$ary_city['city']['cr_parent_id']), '`cr_name` ASC');
		//$action->where(array("cr_status"=>'1',"cr_id"=>$ary_city['city']['cr_parent_id']))->order('`cr_name` ASC')->find();
        $ary_city['country'] = D('Gyfx')->selectOneCache('city_region',$ary_field=null, array("cr_status"=>'1',"cr_id"=>$ary_city['province']['cr_parent_id']), '`cr_name` ASC');
		//$action->where(array("cr_status"=>'1',"cr_id"=>$ary_city['province']['cr_parent_id']))->order('`cr_name` ASC')->find();
        $ary_city['region'] = D('Gyfx')->selectAllCache('city_region',$ary_field=null, array("cr_status"=>'1',"cr_parent_id"=>$cr_id), '`cr_name` ASC');
		//$action->where(array("cr_status"=>'1',"cr_parent_id"=>$cr_id))->order('`cr_name` ASC')->select();
        //echo "<pre>";print_r($ary_city);exit;
        $country = D('Gyfx')->selectAllCache('city_region',$ary_field=null, array("cr_status"=>'1',"cr_parent_id"=>'1',"cr_type"=>"2"));
		//$action->where(array("cr_status"=>'1',"cr_parent_id"=>'1',"cr_type"=>"2"))->select();
        $city = D('Gyfx')->selectAllCache('city_region',$ary_field=null, array("cr_status"=>'1',"cr_parent_id"=>$ary_city['city']['cr_parent_id']),'`cr_name` ASC');
		//$action->where(array("cr_status"=>'1',"cr_parent_id"=>$ary_city['city']['cr_parent_id']))->order('`cr_name` ASC')->select();
        $this->assign("country",$country);
        $common = D('SysConfig')->getCfgByModule('goods_comment_set',1);
        //echo "<pre>";print_r($common);exit;
        $this->assign("city",$city);
        $this->assign("common",$common);
        $this->assign("citys",$ary_city);
        //显示页面
        
        //过滤url非法数据
		
        //$int_count = D("GoodsInfo")->where(array('g_id'=>$ary_request['gid']))->count();
		$int_count =D("Gyfx")->getCountCache('goods_info',array('g_id'=>$ary_request['gid']),60);
        if(0 >= $int_count){
            $this->error('此商品不存或已下架！',U('Home/Products/Index'));
        }
		
        $goods = D('Gyfx')->selectOneCache('goods_info','g_id,g_name,g_description,g_keywords',array('g_id'=>$ary_request['gid']));

        $goods_brand = D('Gyfx')->selectOneCache('goods','gb_id', array('g_id'=>$ary_request['gid']));
        $gb_info = D('Gyfx')->selectOneCache('goods_brand','gb_certificate,gb_name,gb_logo,gb_detail', array('gb_id'=>$goods_brand['gb_id']));
        $gb_certificate = $gb_info['gb_certificate'];

        $this->assign('gb_brand',$gb_info['gb_name']);
        $this->assign('gb_logo',$gb_info['gb_logo']);
        $this->assign('gb_detail',$gb_info['gb_detail']);
        $this->assign('gb_certificate',$gb_certificate);

		
		//D("GoodsInfo")->where(array('g_id'=>$ary_request['gid']))->field('g_id,g_name')->find();
        $this->setTitle($goods['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
	    $this->assign('page_description',$goods['g_description']);
		$this->setKeywords($goods['g_keywords']);

			
		$gy_goods_set = D('SysConfig')->getCfgByModule('GY_GOODS_SET',1);
        //所有会员总浏览次数
		if($gy_goods_set['IS_GOODS_KEYSTORE'] == 1){
			$pageView = $this->getPageView($ary_request['gid']);
		}
        //会员浏览历史单个会员浏览记录
        if(is_numeric($ary_request['gid'])){
        	$this->updcookie($ary_request['gid']);
        }
        $this->assign('pageView', $pageView);
        $this->assign('itemInfo', $ary_request);
        $this->assign('m_id', $m_id);
		$this->assign('ratio',$ary_pointresule['consumed_ratio']);

		
		//获取关联商品
		
		/**$relatedgoods = D("Goods")->where(array('g_id'=>$ary_request['gid']))->getField('g_related_goods_ids');
		$otherwhere = array();
		$otherwhere['_string'] = 'find_in_set('.$ary_request['gid'].',g_related_goods_ids)';
		$othergoods = D("Goods")->where($otherwhere)->field('g_id')->find();
		$relatedgoods = trim($relatedgoods,",");
		if(!empty($othergoods)) $relatedgoods .=','.implode(',',$othergoods);
        $where = array();
		$relatedgoods = trim($relatedgoods,",");
		if(!empty($relatedgoods)){
			$where['g_id'] = array('in',$relatedgoods);
			$rggoods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->select();
			$this->assign('rggoods',$rggoods);			
		}**/
		

        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
		$this->assign('warm_prompt',$warm_prompt['sc_value']);
		
		
        //获取此商品的扩展属性数据
		/**放在自定义标签里读取 _unsalespecs
        $array_cond = array("g_id" => $ary_request['gid'], "gs_is_sale_spec" => 0);
        $array_unsale_spec = D("RelatedGoodsSpec")->where($array_cond)->order(array("gs_id asc"))->select();
        foreach ($array_unsale_spec as $key => $val) {
            $array_unsale_spec[$key]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_name");
            if($val['gsd_id'] != 0){
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
            }
        }
		
		//echo "<pre>";print_r($array_unsale_spec);exit;
        $this->assign('array_unsale_spec', $array_unsale_spec);
		**/
		
        if (empty($ary_request['gid'])) {
            $this->error("只有商品详情页才能使用出价模块");
        } else {
            if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
                $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/productDetails.html';
            } else {
                $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/productDetails.html';
            }
			$good_category_set = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY',1);
			if($good_category_set['GCTYPE'] == 1){
				//获取商品所属门店
				$shop = $this->getCateParentShop($ary_request['gid'],1);
				//获取商品所属楼层
				$louchen = $this->getCateParentLou($ary_request['gid'],1);
				$this->assign('shop',$shop);
				$this->assign('lou',$louchen);					
			}
			//获取关联商品
			/**$ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
            if(strpos($ary_api_conf['GY_TEMPLATE_DEFAULT']['sc_value'],'tmall') || $ary_api_conf['GY_TEMPLATE_DEFAULT']['sc_value'] == 'tmall'){
                $int_g_id = $ary_request['gid'];
                if($int_g_id){
                    $ary_relatedgoods = D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
                    $ary_relatedgoods = trim($ary_relatedgoods,",");
                    $where = array();
                    $where['g_id'] = array('in',$ary_relatedgoods);
                    $ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->select();
                    $this->assign('likeglist',$ary_relate_goods);
                }
            }else{
                $pid = $this->getCateParent($ary_request['gid']);
                $ary_request['cid'] = $pid;
                $tag['cid'] = $pid;
                $tag['num'] = 12;
                $glists = D('ViewGoods')->goodList($tag);
                $glist = array();
                $glists = $glists['list'];
                $count = count($glists);
                for($i=0;$i<$count/3;$i++){
                    for($k=0;$k<3;$k++){
                        $glist[$i][$k]=$glists[$i*3+$k];
                    }
                }
                $this->assign('likeglist',$glist);
            }**/
            //获取商品所属类目
            /*$pid = $this->getCateParent($ary_request['gid']);
            $ary_request['cid'] = $pid;
            $tag['cid'] = $pid;
            $tag['num'] = 12;
            $glists = D('ViewGoods')->goodList($tag);
            $glist = array();
            $glists = $glists['list'];
            $count = count($glists);
            for($i=0;$i<$count/3;$i++){
                for($k=0;$k<3;$k++){
                    $glist[$i][$k]=$glists[$i*3+$k];
                }
            }*/
            //获取会员浏览历史(5条)
			//跨境贸易
			$is_foreign = D('SysConfig')->getCfgByModule('GY_SHOP',1);
			//$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
			$this->assign($is_foreign);
			
			//商城价显示最小价格
			$array_fetch_condition = array("g_id"=>$ary_request['gid'],"pdt_satus"=>1);
			$string_fields = "min(`pdt_market_price`) as `g_market_price`,min(`pdt_sale_price`) as `g_price`";
			//$array_result = D("GoodsProducts")->where($array_fetch_condition)->field($string_fields)->find();
			$array_result = D("Gyfx")->selectOneCache('goods_products',$string_fields, $array_fetch_condition, $ary_order=null,300);
			$this->assign('g_market_price',$array_result['g_market_price']);
			$this->assign('g_price',$array_result['g_price']);
			
            $ary_history_data = $this->BrowsehistoryCount(5);
            $this->assign('ary_history_data',$ary_history_data);
            $this->assign("ary_request", $ary_request);
//          $this->assign('likeglist',$glist);

            //获取评论统计
           // $ary_gcs = M('goods_comment_statistics')->where(array(
               // 'g_id'=>$ary_request['gid']
            //))->find();
			$ary_gcs = D('Gyfx')->selectOneCache('goods_comment_statistics',$ary_field=null, array(
                'g_id'=>$ary_request['gid']
            ), $ary_order=null,60);
			
            $this->assign('comment_statics', $ary_gcs);

            //生成当前商品所在wap站地址的二维码
            $wap_url = U('Wap/Products/detail', array('gid'=>$ary_request['gid']), '', '', true);
            $qc_url = createQcPic($wap_url, 'product_qc', 'wap_');
            $this->assign('qc_img', $qc_url);
            $sysSetting = D('SysConfig');
            $sys_config = $sysSetting->getConfigs('GY_GOODS');
            $is_on_mulitiple = empty($sys_config['IS_ON_MULTIPLE']['sc_value']) ? 2: $sys_config['IS_ON_MULTIPLE']['sc_value'];
            $this->assign('is_on_mulitiple', $is_on_mulitiple);

            if($_GET['v']==2){
                        $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/productDetails-v2.html';
                        $this->assign("v",'-v2');
                        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
                        $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
                        $this->assign("headerTpl",$headerTpl);
                        $this->assign("footerTpl",$footerTpl);
            }
            $csrf = md5(uniqid(rand(), TRUE));  //生成token  
            $_SESSION['csrf'] = $csrf;  
            $this->assign('csrf',$csrf);
            $this->display($tpl);
        }

    }

    /**
     * 获取商品门店
     * @author Hcaijin
     * @date 2014-09-11
     */
    public function getCateParentShop($gid,$is_cache=0){
    	$condition = array();
    	$condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
    	$condition[C('DB_PREFIX').'goods_category.gc_type'] = 2;
		if($is_cache == 1){
			$obj_query = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
			->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
			->where($condition)
			->field(C('DB_PREFIX').'goods_category.gc_name');	
			$result_info = D('Gyfx')->queryCache($obj_query,'find',600);
			$result = $result_info['gc_name'];
		}else{
			$result=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
			->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
			->where($condition)
			->getField(C('DB_PREFIX').'goods_category.gc_name');			
		}
    	return $result;
    }

    /**
     * 获取商品所在楼层
     * @author Hcaijin
     * @date 2014-09-11
     */
    public function getCateParentLou($gid,$is_cache=0){
    	$condition = array();
    	$condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
    	$condition[C('DB_PREFIX').'goods_category.gc_type'] = 1;
		if($is_cache == 1){
			$obj_query = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
			->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
			->where($condition)
			->field(C('DB_PREFIX').'goods_category.gc_name');	
			$result_info = D('Gyfx')->queryCache($obj_query,'find',600);
			$result = $result_info['gc_name'];
		}else{
			$result=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
			->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
			->where($condition)
			->getField(C('DB_PREFIX').'goods_category.gc_name');			
		}
    	return $result;
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
		$ary_res = array();
		$where['fx_goods.g_on_sale'] = 1;
		if(!empty($gids)){
			$where['fx_goods.g_id'] = array('in',$gids);
			$obj_query = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
			->field('fx_goods_info.g_name,fx_goods_info.g_id,fx_goods_info.g_picture,fx_goods_info.g_price')
			->join('fx_goods on fx_goods_info.g_id = fx_goods.g_id')
			->where($where)->limit($limit);	
			$ary_res = D('Gyfx')->queryCache($obj_query,null,60);
		}
    	return $ary_res;
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
     * 规格列表ajax请求方法,获取某个商品规格的库存和价格
     * @request
     * 商品ID:gid
     * 货号：pdt_sn
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     * modify by wanghaoyu 2013-10-09
     */
    public function ajaxGoodsProducts() {
        $products = M("view_products");
        $ary_request = $this->_request();
        $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price', 'pdt_on_way_stock');
        $where = array();
        if (empty($ary_request['gid'])) {
            $this->ajaxReturn("商品ID为空");
        } else {
            $where['g_id'] = $ary_request['gid'];
        }
        if (empty($ary_request['pdt_sn'])) {
            $this->ajaxReturn("商品编码为空");
        } else {
            $where['pdt_sn'] = $ary_request['pdt_sn'];
        }
        $where['pdt_status'] = 1;
        $ary_pdt = $products->field($ary_product_feild)->where($where)->find();
        if (empty($ary_pdt)) {
            $this->ajaxReturn("商品货号不存在");
        } else {
            $this->ajaxReturn($ary_pdt);
        }
    }

    public function getGoodsAdvice() {
        $ary_post = $this->_request();
        $ary_post['title'] = htmlspecialchars($ary_post['title'],ENT_QUOTES);
        $ary_post['gid'] = (int)$ary_post['gid'];
        $ary_post['page'] = (int)$ary_post['page'];
        $ary_post['gid'] = (int)$ary_post['gid'];
        $ary_post['gid'] = (int)$ary_post['gid'];


        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $ary_where = array();
        if (!empty($ary_post['title'])) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_question_title'] = array('LIKE', "%" . $ary_post['title'] . "%");
        }
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.g_id'] = $ary_post['gid'];
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = 1;
        $count = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->field(" " . C('DB_PREFIX') . "goods.g_name," . C('DB_PREFIX') . "purchase_consultation.*," . C('DB_PREFIX') . "members.m_name")
                ->join(' ' . C('DB_PREFIX') . "goods ON " . C('DB_PREFIX') . "purchase_consultation.g_id=" . C('DB_PREFIX') . "goods.g_id")
                ->join(' ' . C('DB_PREFIX') . "members ON " . C('DB_PREFIX') . "purchase_consultation.m_id=" . C('DB_PREFIX') . "members.m_id")
                ->where($ary_where)->order(C('DB_PREFIX') . "purchase_consultation.`pc_create_time` DESC")
                ->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->showArr();
        $ary_data = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->field(" " . C('DB_PREFIX') . "goods_info.g_name," . C('DB_PREFIX') . "purchase_consultation.*," . C('DB_PREFIX') . "members.m_name")
                        ->join(' ' . C('DB_PREFIX') . "goods_info ON " . C('DB_PREFIX') . "purchase_consultation.g_id=" . C('DB_PREFIX') . "goods_info.g_id")
                        ->join(' ' . C('DB_PREFIX') . "members ON " . C('DB_PREFIX') . "purchase_consultation.m_id=" . C('DB_PREFIX') . "members.m_id")
                        ->where($ary_where)->order(C('DB_PREFIX') . "purchase_consultation.`pc_create_time` DESC")
                        ->limit(($ary_post['page']-1)*10,10)
                        ->select();
//        echo "<pre>";print_r(M('PurchaseConsultation',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
        if (!empty($ary_data) && is_array($ary_data)) {
            foreach ($ary_data as $ky => $vl) {
                if ($vl['pc_type'] == '1') {
                    $ary_data[$ky]['new_mname'] = $vl['m_name'];
                } else {
                    $ary_data[$ky]['new_mname'] = str_replace(substr($vl['m_name'], 3, 2), "****", $vl['m_name']);
                }
                $ary_data[$ky]['pc_answer'] = strip_tags(htmlspecialchars_decode($vl['pc_answer']));
            }
        }
        $page['nowPage'] = $ary_post['page'];
        $this->assign("filter", $ary_post);
        $this->assign("data", $ary_data);
        $this->assign("page", $page);
        $this->assign("count", $count);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/advice.html";
        $this->display($tpl);
    }

    public function doGoodsAdvice() {
        $ary_post = $this->_post();
        if (isset($ary_post['verify'])) {
            if (empty($ary_post['verify'])) {
                $this->error("验证码不能为空");
                exit;
            }else{
                if (md5($ary_post['verify']) != $_COOKIE['verify']) {
                    $this->error("验证码错误");
                    exit;
                }
            }
        }

        $data = array(
            'pc_type' => (int)$ary_post['type'],
            'g_id' => (int)$ary_post['gid'],
            'pc_question_title' => htmlentities($ary_post['question_title'], ENT_QUOTES, 'UTF-8'),
            'pc_question_content' => htmlentities($ary_post['question_content'], ENT_QUOTES, 'UTF-8'),
            'pc_create_time' => date("Y-m-d H:i:s")
        );
        if (!empty($_SESSION['Members']['m_id'])) {
            $data['m_id'] = $_SESSION['Members']['m_id'];
        }
        $ary_res = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
        if (FALSE != $ary_res) {
            $this->success("咨询成功，待管理员回复后显示！");
            exit;
        } else {
            $this->error("咨询失败");
            exit;
        }
    }

    public function getAskedquestions() {
        $ary_post = $this->_request();
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $ary_where = array();
        if (!empty($ary_post['title'])) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_question_title'] = array('LIKE', "%" . $ary_post['title'] . "%");
        }
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.g_id'] = $ary_post['gid'];
        $count = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->field(" " . C('DB_PREFIX') . "goods.g_name,goods.g_sn,goods.g_picture,goods.g_id," . C('DB_PREFIX') . "purchase_consultation.*," . C('DB_PREFIX') . "members.m_name")
                ->join(' ' . C('DB_PREFIX') . "goods ON " . C('DB_PREFIX') . "purchase_consultation.g_id=" . C('DB_PREFIX') . "goods.g_id")
                ->join(' ' . C('DB_PREFIX') . "members ON " . C('DB_PREFIX') . "purchase_consultation.m_id=" . C('DB_PREFIX') . "members.m_id")
                ->where($ary_where)->order(C('DB_PREFIX') . "purchase_consultation.`pc_create_time` DESC")
                ->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->showArr();
        //$ary_data = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->field(" " . C('DB_PREFIX') . "goods_info.g_name,goods_info.g_sn,goods_info.g_picture,goods_info.g_price,goods_info.g_id," . C('DB_PREFIX') . "purchase_consultation.*," . C('DB_PREFIX') . "members.m_name")
        $ary_data = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->field(" " . C('DB_PREFIX') . "goods_info.g_name," . C('DB_PREFIX') . "purchase_consultation.*," . C('DB_PREFIX') . "members.m_name")
                        ->join(' ' . C('DB_PREFIX') . "goods_info ON " . C('DB_PREFIX') . "purchase_consultation.g_id=" . C('DB_PREFIX') . "goods_info.g_id")
                        ->join(' ' . C('DB_PREFIX') . "members ON " . C('DB_PREFIX') . "purchase_consultation.m_id=" . C('DB_PREFIX') . "members.m_id")
                        ->where($ary_where)->order(C('DB_PREFIX') . "purchase_consultation.`pc_create_time` DESC")
                        ->limit($obj_page->firstRow, $obj_page->listRows)->select();
                        //print_r($ary_data);exit;
//        echo "<pre>";print_r(M('PurchaseConsultation',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
        if (!empty($ary_data) && is_array($ary_data)) {
            foreach ($ary_data as $ky => $vl) {
                if ($vl['pc_type'] == '1') {
                    $ary_data[$ky]['new_mname'] = $vl['m_name'];
                } else {
                    $ary_data[$ky]['new_mname'] = str_replace(substr($vl['m_name'], 3, 2), "****", $vl['m_name']);
                }
                $ary_data[$ky]['pc_answer'] = htmlspecialchars_decode($vl['pc_answer']);
				$ary_data[$ky]['pc_answer'] = _ReplaceItemDescPicDomain($ary_data[$ky]['pc_answer']);
            }
        }
        $this->assign("count", $count);
//        echo "<pre>";print_r($ary_data);exit;
        $this->assign("filter", $ary_post);
        $this->assign("data", $ary_data);
        $this->assign("page", $page['linkPage']);
        $this->assign("count", $count);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/askedquestions.html";
        $this->display($tpl);
    }

    public function getBuyRecordPage(){
		$ary_post_data = $this->_request();
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
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $config_img = D('SysConfig')->getCfgByModule('ITEM_IMAGE_CONFIG',1);
		unset($ary_post_data['num']);
        $list = D('GoodsInfo')->getBuyRecords($ary_post_data);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/buyrecord.html";    
	   $list['page']['nowPage'] = empty($ary_post_data[p])?'1':($ary_post_data[p]);
        $this->assign('buynums',$list['buynums']);
        $this->assign('monthsales',$list['one_month_sales']);
        $this->assign('data',$list['data']);
        $this->assign('page',$list['page']);
        $this->assign('buy_price',$config_img['BUY_PRICE']);
        $this->display($tpl);
    }

    /**
     * 获取城市
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-25
     */
    public function selectCitys(){
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
        $ary_post = $this->_post();
        $stock = new GoodsStockModel();
        //获取货品价格等额外数据
        $member = session('Members');
        $price = new PriceModel($member['m_id']);
        $goodsSpec = D('GoodsSpec');
        layout(FALSE);
        if(!empty($ary_post['cr_id']) && isset($ary_post['cr_id'])){
            $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
            $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/selectCitys.html";
            $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
            $where = array();
            $where['cr_status'] = '1';
            $where['cr_parent_id'] = $ary_post['cr_id'];
            $ary_parent = $action->where($where)->order('`cr_name` ASC')->select();
            $ary_goods = $stock->getGoodsWarehouseStock($ary_post['cr_id'], $ary_post['g_id']);
            $skus = array();
            $skuName = array();
            $stock = '0';
            foreach($ary_goods as $keyg=>$valg){
                $products = D('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field("pdt_sale_price,pdt_market_price")->where(array("pdt_id"=>$valg['pdt_id']))->find();               
                if(!empty($member['m_id']) && isset($member['m_id'])){
                    $ary_goods[$keyg]['pdt_sale_price'] = $price->getMemberPrice($valg['pdt_id']);
                }else{
                    $ary_goods[$keyg]['pdt_sale_price'] = $products['pdt_sale_price'];
                }
                $ary_goods[$keyg]['pdt_market_price'] = $products['pdt_market_price'];
                $specInfo = $goodsSpec->getProductsSpecs($valg['pdt_id']);
                $ary_goods[$keyg]['specName']  = $specInfo['spec_name'];
		        $ary_goods[$keyg]['skuName']  = $specInfo['sku_name'];
                if(!empty($specInfo['sku_name'])){
                    $skuName[$keyg] = $specInfo['sku_name'];
                }else{
                    $stock = $valg['pdt_stock'];
                }
                $skus[$specInfo['sku_name']] = $ary_goods[$keyg]['pdt_id']."|".$ary_goods[$keyg]['pdt_stock']."|".$ary_goods[$keyg]['pdt_sale_price']."|".$ary_goods[$keyg]['pdt_market_price'];
            }
            if(empty($skuName)){
                $this->assign("stock",$stock);
            }
            
            $this->assign("skuName",$skuName);
            $this->assign("skus",json_encode($skus));
            $this->assign("city",$ary_parent);
            $this->display($tpl);
            
        }
    }
    
    /**
     * 电话订购
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-08-02
     */    
    public function addTelCallBack(){
    	$members = $_SESSION['Members'];
        $result=array();
        $data=array();
        $data['user_id'] = ($members['m_id'])?$members['m_id']:0;
        $data['user_name'] = ($members['m_name'])?$members['m_name']:'';
        $feeback = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
        $data['msg_type'] = 4;
        $data['user_mobile'] = trim($_POST['num']);
        $g_id = trim($_POST['pid']);
        $data['msg_title'] = '电话回拨';
        $content = trim($_POST['content']);
        $item_name = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$g_id))->getField('g_name');
        $content .='订购商品为：'.$item_name.';';
        $data['msg_content'] = $content;
        $data['msg_time'] = date('Y-m-d H:i:s');
        $count = M('feedback',C('DB_PREFIX'),'DB_CUSTOM')->where(array('user_mobile'=>$data['user_mobile'],'msg_title'=>'电话回拨','msg_type'=>'4','msg_status'=>0))->count('msg_id');
        if($count>0){
         $result['status'] = 200;
         $result['message'] = '添加成功';
         echo json_encode($result);
         exit;                 	
        }
        
        $teladdrs = $feeback->add($data);
        //dump($feeback->getLastSql());
        //dump($feeback);die();
        if($teladdrs){
            $result['status'] = 200;
            $result['message'] = '添加成功';
        }else{
            $result['status'] = 500;
            $result['message'] = '添加失败';
        }
        echo json_encode($result);
        exit;
    }
    
    /**
     * 电话订购信息
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-08-02
     */    
    public function addTelCallBackAll(){
    	$members = $_SESSION['Members'];
        $result=array();
        $data=array();
        $user_name = trim($_POST['tel_name']);
      	$data['user_id'] = ($members['m_id'])?$members['m_id']:0;
        $data['user_name'] = ($user_name)?$user_name:'';
        $feeback = M('feedback',C('DB_PREFIX'),'DB_CUSTOM');
        $data['msg_type'] = 4;
        $data['user_mobile'] = trim($_POST['tel']);
        $g_id = trim($_POST['pid']);
        $content = trim($_POST['content']);
        $num = intval($_POST['num']);
        $item_name = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$g_id))->getField('g_name');
        $content .=';订购商品为：'.$item_name.';订购数量为：'.$num;
        $data['msg_title'] = '电话回拨(全)';
        $data['msg_content'] = $content;
        $data['msg_time'] = date('Y-m-d H:i:s');
        $data['msg_address'] = trim($_POST['msg_address']);
        
        $count = M('feedback',C('DB_PREFIX'),'DB_CUSTOM')->where(array('user_mobile'=>$data['user_mobile'],'msg_title'=>'电话回拨(全)','msg_type'=>'4','msg_status'=>0))->count('msg_id');
        if($count>0){
         $result['status'] = 200;
         $result['message'] = '添加成功';
         echo json_encode($result);
         exit;                 	
        }
        $teladdrs = $feeback->add($data);
        if($teladdrs){
            $result['status'] = 200;
            $result['message'] = '添加成功';
        }else{
            $result['status'] = 500;
            $result['message'] = '添加失败';
        }
        echo json_encode($result);
        exit;
    }
    
    /**
     * 商品对比
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-12
     */
    public function addToCompare(){
        $compare = $_SESSION['Compare'];
        $ary_post = $this->_post();
        $ary_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_post['gid'],'g_status'=>'1'))->find();
        if(!empty($ary_goods) && is_array($ary_goods)){
            if(!empty($compare) && is_array($compare)){
                $msg = true;
                foreach($compare as $ky=>$vl){
                    $arr_commpare = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$vl,'g_status'=>'1'))->find();
                    if(!empty($arr_commpare) && is_array($arr_commpare)){   
                        if($arr_commpare['gt_id'] != $ary_goods['gt_id']){
                            $msg = false;
                        }
                    }else{
                        continue;
                    }
                }
                
                if(!empty($ary_post['check']) && $ary_post['check'] == '1'){
                    foreach($_SESSION['Compare'] as $key=>$val){
                        if($val == $ary_goods['g_id']){
                            unset($_SESSION['Compare'][$key]);
                        }
                    }
                    $this->success("取消对比成功");
                }else{
                    if(in_array($ary_goods['g_id'], $_SESSION['Compare'])){
                        $this->error("该商品已在对比栏中");
                    }else{
                        if(!$msg){
                            $this->error("不同类型商品无法对比");
                        }else{
                            if(count($compare) >= 4){
                                $this->error("对比栏已满，无法加入对比");
                            }else{
                                $_SESSION['Compare'][] = $ary_goods['g_id'];
                                $this->success("加入对比成功");
                            }
                        }
                    }
                }
            }else{
                $_SESSION['Compare'][] = $ary_goods['g_id'];
                $this->success("加入对比成功");
            }
        }else{
            $this->error("商品不存在，或者已经下架");
        }
    }
    
    /**
     * 商品列表页中的对比栏
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-13
     */
    public function getGoodsCompareList(){
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
        layout(false);
        $compare = $_SESSION['Compare'];
        if(!empty($compare) && is_array($compare)){
            $ary_goods = array();
            foreach($compare as $key=>$val){
                $ary_goods[] = M('goods',C('DB_PREFIX'),'DB_CUSTOM')
                                ->join(" ".C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."goods.g_id=".C('DB_PREFIX')."goods_info.g_id")
                                ->where(array(C('DB_PREFIX').'goods.g_id'=>$val,C('DB_PREFIX').'goods.g_status'=>'1'))->find();
            }
        }
        $this->assign("data",$ary_goods);
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/compare.html";
        $this->display($tpl);
    }
    
    /**
     * 商品对比列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-12
     */
    public function getToCompareList(){
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
        $compare = $_SESSION['Compare'];
        $data = array();
        $ary_spec = array();
        if(!empty($compare) && is_array($compare)){
            foreach($compare as $key=>$val){
               $arr_spec = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')
                                ->join(" ".C('DB_PREFIX')."goods_spec ON ".C('DB_PREFIX')."goods_spec.gs_id=".C('DB_PREFIX')."related_goods_spec.gs_id")
                                ->where(array(C('DB_PREFIX').'related_goods_spec.g_id'=>$val,C('DB_PREFIX').'related_goods_spec.gs_is_sale_spec'=>'0'))->select();
               $arr_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')
                            ->join(" ".C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."goods.g_id=".C('DB_PREFIX')."goods_info.g_id")
                            ->join(" ".C('DB_PREFIX')."goods_brand ON ".C('DB_PREFIX')."goods.gb_id=".C('DB_PREFIX')."goods_brand.gb_id")
                            ->where(array(C('DB_PREFIX').'goods.g_id'=>$val))
                            ->find();
               $goods_spec = M('goods',C('DB_PREFIX'),'DB_CUSTOM')
                                ->join(" ".C('DB_PREFIX')."related_goods_type_spec ON ".C('DB_PREFIX')."goods.gt_id=".C('DB_PREFIX')."related_goods_type_spec.gt_id")
                                ->field(" ".C('DB_PREFIX')."related_goods_type_spec.gs_id")
                                ->where(array(C('DB_PREFIX').'goods.g_id'=>$val))
                                ->select();
                //处理商品固定信息
               $data[$val]['g_id'] = $arr_goods['g_id'];
               $data[$val]['g_price'] = $arr_goods['g_price'];
               $data[$val]['g_picture'] = $arr_goods['g_picture'];
               $data[$val]['g_name'] = $arr_goods['g_name'];
               $data[$val]['gb_name'] = $arr_goods['gb_name'];
               //处理属性值别名
               if(!empty($arr_spec) && is_array($arr_spec)){
                   foreach($arr_spec as $keysp=>$valsp){
                        if(!empty($valsp['gsd_aliases'])) {
                            $data[$val]['spec'][$valsp['gs_id']] = $valsp['gsd_aliases'];
                        }
                   }
               }
            }
        }
        if(!empty($goods_spec) && is_array($goods_spec)){
            foreach($goods_spec as $valstrsp){
                $arr_sp[] = $valstrsp['gs_id'];
            }
            $str_spec = implode(",", $arr_sp);
            $where = array();
            $where['gs_status'] = '1';
            $where['gs_is_sale_spec'] = '0';
            $where['gs_id'] = array("IN",$str_spec);
            $ary_spec = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM')
                        ->where($where)
                        ->select();
        }
        $this->assign("spec",$ary_spec);
        $this->assign("data",$data);
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/comparelist.html";
        $this->display($tpl);
    }
    
    /**
     * 清空对比栏成功
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-14
     */
    public function clearToCompareList(){
        $ary_post = $this->_post();
        if(isset($ary_post['gid']) && (int)$ary_post['gid'] > 0){
            foreach($_SESSION['Compare'] as $key=>$val){
                if($val == (int)$ary_post['gid']){
                    unset($_SESSION['Compare'][$key]);
                }
            }
            $this->success("删除对比栏成功");
        }else if((int)$ary_post['gid'] == 0){
            unset($_SESSION['Compare']);
            $this->success("清空对比栏成功");
        }else{
            $this->error("删除错误");
        }
    }
    
    /**
     * 获取自由商品组合信息
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-14
     */
    public function getCollGoodsPage($is_cache=true){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,1);
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        $g_id = $this->_request('gid');
        //自由组合搭配
        $combination = D("FreeCollocation");
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $goodsSpec = D('GoodsSpec');
        $now = date('Y-m-d H:i:s');
        $fc_id=0;
		if($is_cache == true){
			$ary_free_coll_1 = D('Gyfx')->selectAllCache('free_collocation',null,array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1));
		}else{
			$ary_free_coll_1 = $combination->where(array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1))->select();
		}
        //时间不限制
        $array_gid = array();
        foreach ($ary_free_coll_1 as $key_1=>$val_1){
            $ary_tmp_g_id = explode(',',$val_1['fc_related_good_id']);
            if(in_array($g_id,$ary_tmp_g_id)){
                $fc_id = $val_1['fc_id'];
                $array_gid = $ary_tmp_g_id;
            }
        }
        if(empty($array_gid)){
            //查找限制时间的
            $array_where['fc_start_time'] = array('elt',$now);
            $array_where['fc_end_time'] = array('egt',$now);
            $array_where['fc_status'] = 1;
            //$ary_free_coll_2 = $combination->where($array_where)->select();
			$ary_free_coll_2 = D('Gyfx')->selectAllCache('free_collocation',null,$array_where);
            foreach ($ary_free_coll_2 as $key_2=>$val_2){
            $ary_tmp_g_id = explode(',',$val_2['fc_related_good_id']);
                if(in_array($g_id,$ary_tmp_g_id)){
                    $fc_id = $val_2['fc_id'];
                    $array_gid = $ary_tmp_g_id;
                }
            }
        }
        $i_d = 0;
        $view_goods = D('ViewGoods');
        if(!empty($array_gid)){
            foreach ($array_gid as $k=>$v){
                //获取商品基本信息
                $field = 'g.g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic,g_collocation_price as gcoll_price';
                $coll_goods = M("goods_info as gi", C("DB_PREFIX"), "DB_CUSTOM")->field($field)
											->join(C("DB_PREFIX").'goods as g on g.g_id = gi.g_id')
											->where(array('g.g_id'=>$v,'g_status'=>1,'g_on_sale'=>1))->find();
				$coll_goods['gpic'] = D('QnPic')->picToQn($coll_goods['gpic'],200,200);
                if(!empty($coll_goods)){
                    $coll_goods['save_price'] = $coll_goods['gprice'] - $coll_goods['gcoll_price'];
                    //授权线判断是否允许购买
                    $coll_goods['authorize'] = true;
                    if (!empty($coll_goods) && is_array($coll_goods)) {
                        $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods','pdt_collocation_price');
                        $where = array();
                        $where['g_id'] = $v;
                        $where['pdt_status'] = '1';
                        $ary_pdt = $products->field($ary_product_feild)->where($where)->limit()->select();
                        if(!empty($ary_pdt) && is_array($ary_pdt)){
                            $skus = array();
                            foreach($ary_pdt as $kypdt=>$valpdt){
                                $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                                if (!empty($specInfo['color'])) {
                                    if (!empty($specInfo['color'][1])) {
                                        $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                                    }
                                }
                                if (!empty($specInfo['size'])) {
                                    if (!empty($specInfo['size'][1])) {
                                        $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                                    }
                                }
            //                    $ary_pdt['skuName'][$kypdt] = $specInfo['sku_name'];
                                $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                                $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                            }
                            
                            foreach ($skus as $key => &$sku) {
                                $skus[$key] = array_unique($sku);
                            }
                            
                        }
                        if (!empty($skus)) {
                            $coll_goods['skuNames'] = $skus;
                        }else{
                            $coll_goods['pdt_id'] = $ary_pdt[0]['pdt_id'];
                        }
                    }
                    if($coll_goods['gid'] == $g_id){
                        $this_goods = $coll_goods;
                    }else{
                        $array_free_goods[$i_d] = $coll_goods;
                        $i_d++;
                    }
                }
            }
            $data['this_coll'] = $this_goods;
            $data['coll_goods'] = $array_free_goods;
            $data['fc_id'] = $fc_id;
        }
        $this->assign($data);
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/coll_goods.html";
        $this->display($tpl);
    }
    
    /**
     * 获取关联商品信息
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-28
     */
    public function getRelateGoodsPage($is_cache=true){
        $limit= (int)$this->_request('page_size');
    	$int_g_id = (int)$this->_request('gid');
    	if($int_g_id){
			if($is_cache == true){
				$tmp_relatedgoods = D('Gyfx')->selectOneCache('goods','g_related_goods_ids', array('g_id'=>$int_g_id));
				$relatedgoods = $tmp_relatedgoods['g_related_goods_ids'];
				//D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
				/**
				$otherwhere = array();
				$otherwhere['_string'] = 'find_in_set('.$int_g_id.',g_related_goods_ids)';
				$othergoods = D('Gyfx')->selectOneCache('goods','g_id', $otherwhere);
				//$othergoods = D("Goods")->where($otherwhere)->field('g_id')->find();**/
				$relatedgoods = trim($relatedgoods,",");
				//if(!empty($othergoods)) $relatedgoods .=','.implode(',',$othergoods);
				$where = array();
				$relatedgoods = trim($relatedgoods,",");
				$where = array();
				$where['g_id'] = array('in',$relatedgoods);
				//$ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->select();
                if($limit) {
                    $ary_limit = array(
                        'page_no' => 1,
                        'page_size' => $limit
                    );
                }
				$ary_relate_goods = D("Gyfx")->selectAllCache('goods_info','g_id,g_name,g_price,g_picture',$where,null,null, $ary_limit);
				foreach($ary_relate_goods as &$ary_relate){
					$ary_relate['g_picture'] = D('QnPic')->picToQn($ary_relate['g_picture'],200,200);
				}
				$this->assign('ary_relate_goods',$ary_relate_goods);				
			}else{
				$relatedgoods = D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
				/**
				$otherwhere = array();
				$otherwhere['_string'] = 'find_in_set('.$int_g_id.',g_related_goods_ids)';
				$othergoods = D("Goods")->where($otherwhere)->field('g_id')->find();**/
				$relatedgoods = trim($relatedgoods,",");
				//if(!empty($othergoods)) $relatedgoods .=','.implode(',',$othergoods);
				$where = array();
				$relatedgoods = trim($relatedgoods,",");
				$where = array();
				$where['g_id'] = array('in',$relatedgoods);
                $limit_option = '';
                if($limit) {
                    $limit_option = '0,'. $limit;
                }
				$ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->limit($limit_option)->select();
				foreach($ary_relate_goods as &$ary_relate){
					$ary_relate['g_picture'] = D('QnPic')->picToQn($ary_relate['g_picture'],200,200);
                    $comment_statistics = M('goods_comment_statistics')->where(array('g_id'=>$ary_relate['g_id']))->find();
                    $ary_relate['comment_statistics'] = $comment_statistics;
				}				
				$this->assign('ary_relate_goods',$ary_relate_goods);			
			}
    	}
    	$config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
    	$tpl = "Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/relate_goods.html";
    	$this->display($tpl);
    }
    
    /**
     * 将商品列表中的商品加入购物车（老版）
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getAddGoodsCart(){

        $ary_post = $this->_post();
        $data = array();
        $goods = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_where = array();
        $ary_where['g.g_id'] = array('eq', $ary_post['gid']);
        $goodsSpec = D('GoodsSpec');
        $ary_goods = $goods
                ->where($ary_where)
                ->field('g.g_id,g.g_sn,g.g_on_sale_time,g_salenum,`g`.`g_is_prescription_rugs` AS `g_is_pres`,g.g_off_sale_time,gi.g_name,gi.g_price,gi.g_stock,gi.g_unit,gi.g_remark,gi.g_desc,g_picture,g_new,g_hot,`g`.`gb_id` AS `gb_id`')
                ->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)')
                ->find();
//        echo "<pre>";print_r($goods->getLastSql());exit;
        if (!empty($ary_goods) && is_array($ary_goods)) {
            $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods','pdt_collocation_price');
            $where = array();
            $where['g_id'] = $ary_post['gid'];
            $where['pdt_status'] = '1';
            $ary_pdt = $products->field($ary_product_feild)->where($where)->limit()->select();
            
            if(!empty($ary_pdt) && is_array($ary_pdt)){
                $skus = array();
                foreach($ary_pdt as $kypdt=>$valpdt){
                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                    if (!empty($specInfo['color'])) {
                        if (!empty($specInfo['color'][1])) {
                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                        }
                    }
                    if (!empty($specInfo['size'])) {
                        if (!empty($specInfo['size'][1])) {
                            $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                        }
                    }
//                    $ary_pdt['skuName'][$kypdt] = $specInfo['sku_name'];
                    $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                    $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                }
                
                foreach ($skus as $key => &$sku) {
                    $skus[$key] = array_unique($sku);
                }
                
            }
            if (!empty($skus)) {
                $data['skuNames'] = $skus;
            }
        }
        $data['gid'] = $ary_goods['g_id'];
        $data['gsn'] = $ary_goods['g_sn'];
        $data['offsale'] = $ary_goods['g_off_sale_time'];
        $data['gname'] = $ary_goods['g_name'];
        $data['gprice'] = $ary_goods['g_price'];
        $mprice = D("Price")->getMarketPrice($data['gid']);
        //货品中最大价格
        $data['mprice'] = $mprice;
        $data['gstock'] = $ary_goods['g_stock'];
        $data['skus'] = $ary_pdt;
        if($ary_post['coll'] == '1'){
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/selectColl.html';
        }else{
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/selectCollGoods.html';
        }
		//模糊库存提示
        $member = session('Members');
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $member_level_id = $member['member_level']['ml_id'];
        if(empty($member)){
            $member_level_id = 0;
        }
        if((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK']==1 ){
            if($stock_data['USER_TYPE']=='all'){
                $stock_data['level'] =true;
            }else{
                $ary_user_level =explode(",",$stock_data['USER_TYPE']);
                $stock_data['level'] = in_array($member_level_id,$ary_user_level);
            }
        }
        //echo "<pre>";print_r($stock_data);die;
        $this->assign("stock_data", $stock_data);
        //echo "<pre>";print_r($data);exit;
        $this->assign("filter", $ary_post);
        $this->assign("data",$data);
        $this->display($tpl);
    }

    /**
     * 多规格商品加入购物车弹窗(新版)
     */
    public function getAddToCartDetail() {

        $ary_post = $this->_request();
        //销售类型（团购，预售，秒杀，正常购物，...）
        $item_type = $ary_post['item_type'];
        $item_id = $ary_post['item_id'];
        $members = session('Members');

        $tpl = C('TMPL_PARSE_STRING.__TPL__'). 'selectGoods.html';
        switch($item_type) {
            case 5:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'bulkSelectGoods.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
                $ary_goods_pdts = D('Groupbuy')->getDetails($item_id, $members);
                break;
            case 7:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'presaleSelectGoods.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
                $ary_goods_pdts = D('Presale')->getDetails($item_id, $members);
                break;
            case 8:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'spikeSelectGoods.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
                $ary_goods_pdts = D('Spike')->getDetails($item_id, $members);
                break;
            case 6:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'collocationSelectGoods.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
                $ary_goods_pdts = D('FreeRecommend')->getDetails($item_id, $members);
                break;
            case 1:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'pointSelectGoods.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
                $point = 0;
                if(!empty($members)) {
                    $point = $members['total_point'] - $members['freeze_point'];
                }
                $this->assign('point', $point);
            default:
                $ary_goods_pdts = D('Goods')->getDetails($item_id, $members);
                break;
        }
        $tpl = '.' . $tpl;
//        dump($ary_goods_pdts);die;
        $this->assign($ary_goods_pdts);
        $this->display($tpl);
    }
	/**
     * 商品详情页 根据不同区域获取运费
     * @param $cr_id  城市最后一级ID
     * @author WangHaoYu <why419163@163.com>
     * @ 支持一个配送公司
     * @version 7.4
     * @date 2013-11-6
     */
    public function ajaxgetPrice($cr_id) {
        $int_cr_id = $this->_post('cr_id');
        $ary_res = array(
            'data' => '',
            'status' => 0,
            'info' => ''
        );
        if(!isset($int_cr_id)){
            $ary_res = array('info'=>'请选择配送区域');
            echo json_encode($ary_res);
            exit;
        }
        $logistic = D('Logistic');
        if(isset($int_cr_id)){
            $ary_logistic = $logistic->getLogistic($cr_id);
            if(!empty($ary_logistic) && is_array($ary_logistic)){
            	$ary_first_logistic = array_shift($ary_logistic);
                if($ary_first_logistic['logistic_price'] == 0){
                    $ary_res['data'] = '商家承担费用';
                    $ary_res['status'] = 1;
                    echo json_encode($ary_res);
                    exit;
                }else{
                    $ary_res['data'] = $ary_first_logistic['logistic_price'];
                    $ary_res['status'] = 1;
                    echo json_encode($ary_res);
                    exit;
                } 
            }
            
        }

    }    /**
     * 自由搭配页
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function freeCollocation(){
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
        $ary_coll_column = $_SESSION['collocation']['data'];
        $get = $this->_get();
        unset($get['_URL_']);
        $recommend = D('FreeRecommend');
        $array_where = array('fr_status'=>1,
                             'fr_statr_time'=>array('lt',date('Y-m-d H:i:s')),
                             'fr_end_time'=>array('gt',date('Y-m-d H:i:s')));
        $count = $recommend->where($array_where)->count();
        $obj_page = new Pager($count, 12);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $array_collocation = $recommend->where($array_where)
                             ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                             ->select();
        $goods = M('goods_info as `gi` ', C('DB_PREFIX'), 'DB_CUSTOM');
        foreach ($array_collocation as $coll_key=>$coll){
			$array_collocation[$coll_key]['fr_goods_picture'] = D('QnPic')->picToQn($array_collocation[$coll_key]['fr_goods_picture'],300,300);
            $ary_where['gi.g_id'] = $coll['fr_goods_id'];
            $ary_where['g.g_on_sale'] = 1;
            $ary_where['g.g_status'] = 1;
            if (!empty($get['cid'])) {
                $get['cid'] = htmlspecialchars($get['cid'],ENT_QUOTES);
                //查询分类时，查询当前分类下子分类 
                $ary_where['gc.gc_id'] = array('in', D('ViewGoods')->getStringCateArray($get['cid']));
            }
            $field = 'gi.g_id as gid,gi.g_name as gname,gi.g_price as gprice,gi.g_stock as gstock,gi.g_salenum as gsalenum';
            $array_goods_info = $goods->where($ary_where)
                                      ->field($field)
                                      ->join(C('DB_PREFIX').'related_goods_category as gc on gc.g_id=gi.g_id')
                                      ->join(C('DB_PREFIX').'goods as g on g.g_id=gi.g_id')
                                      ->find();
            
            $i = 0;
            if(empty($array_goods_info)){
                unset($array_collocation[$coll_key]);
            }else{
                //验证价格
                if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($coll['fr_price'] < $get['startPrice'] || $coll['fr_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                if(!empty($get['startPrice']) && empty($get['endPrice'])){
                    if($coll['fr_price'] < $get['startPrice']){
                        $i++;
                    }
                }
                if(empty($get['startPrice']) && !empty($get['endPrice'])){
                    if($coll['fr_price'] > $get['endPrice']){
                        $i++;
                    }
                }
                
                if($i > 0){
                    //价格不或分类不满足条件，剔除
                    unset($array_collocation[$coll_key]);
                }else{
                    //授权线判断是否允许购买
                    $array_goods_info['authorize'] = true;
                    $array_collocation[$coll_key]['gsalenum'] = $array_goods_info['gsalenum'];
                    if (!empty($array_goods_info) && is_array($array_goods_info)) {
                        $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock');
                        $where = array();
                        $where['g_id'] = $coll['fr_goods_id'];
                        $where['pdt_status'] = '1';
                        $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
                        if(!empty($ary_pdt) && is_array($ary_pdt)){
                            $skus = array();
                            $ary_zhgoods = array();
                            foreach($ary_pdt as $kypdt=>$valpdt){
                                $specInfo = D('GoodsSpec')->getProductsSpecs($valpdt['pdt_id']);
                                if (!empty($specInfo['color'])) {
                                    if (!empty($specInfo['color'][1])) {
                                        $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                                    }
                                }
                                if (!empty($specInfo['size'])) {
                                    if (!empty($specInfo['size'][1])) {
                                        $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                                    }
                                }
                                $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                                $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                                $ary_pdt[$kypdt]['pdt_sale_price'] = sprintf('%.2f',$valpdt['pdt_sale_price']);
                                $ary_pdt[$kypdt]['pdt_market_price'] = sprintf('%.2f',$valpdt['pdt_market_price']);
                                 
                            }
                            foreach ($skus as $key => &$sku) {
                                $skus[$key] = array_unique($sku);
                            }
                            
                        }
                        if (!empty($skus)) {
                            $array_goods_info['skuNames'] = $skus;
                        }else{
                            $array_goods_info['pdt_id'] = $ary_pdt[0]['pdt_id'];
                            $array_goods_info['pdt_stock'] = $ary_pdt[0]['pdt_stock'];
                        }
                    }
                    //自由搭配列表默认选中
                    if(!empty($ary_coll_column) && isset($ary_coll_column)){
                        $k = 0;
                        foreach($ary_coll_column as $acc){
                            if($acc['fr_id'] == $coll['fr_id']){
                                $array_collocation[$coll_key]['is_invice'] = 1;
                                $k++;
                            }
                        }
                        if($k == 0){
                            $array_collocation[$coll_key]['is_invice'] = 0;
                        }
                    }else{
                        $array_collocation[$coll_key]['is_invice'] = 0;
                    }
                    $array_collocation[$coll_key] = array_merge($array_collocation[$coll_key],$array_goods_info);
                }
            }
            
        }
        $this->assign('data',$array_collocation);
        $this->assign('pagearr',$pageInfo);
        $this->assign('get',$get);
        $this->assign('is_show',$_SESSION['collocation']['is_show_coll']);
        $this->assign('itemInfo', $this->_request());
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/free_recommend.html';
        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE');
        $this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . ' - 自由搭配推荐');
        $this->display($tpl);
    }
    
    /**
     * 加入搭配栏
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function addCollocation(){
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
        $fr_id = $this->_post('fr_id');
        $pdt_id = $this->_post('pdt_id');
        //获取自由搭配信息
        $ary_coll_data = D('FreeRecommend')->where(array('fr_id'=>$fr_id))->find();
        if($_SESSION['collocation']['data']){
            $ary_collocation = $_SESSION['collocation']['data'];
        }else{
            $ary_collocation = $_SESSION['collocation']['data'] = array();
        }
		//七牛图片显示
		$ary_coll_data['fr_goods_picture'] =D('QnPic')->picToQn($ary_coll_data['fr_goods_picture']);
        $specInfo = D('GoodsSpec')->getProductsSpecs($pdt_id);
        $ary_coll_data['spec_name'] = $specInfo['spec_name'];
        $ary_coll_data['pdt_id'] = $pdt_id;
        
        $_SESSION['collocation']['is_show_coll'] = 1;
        array_push($ary_collocation,$ary_coll_data);
        $_SESSION['collocation']['data'] = $ary_collocation;
        //获取自由搭配价格
        $_SESSION['collocation']['price_da'] = D('FreeRecommend')->getFreeCollotionPrice($ary_collocation);
        $this->assign('data',$ary_collocation);
        $this->assign($_SESSION['collocation']['price_da']);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/collocation_column.html';
        $this->display($tpl);
    }
    
    /**
     * 移除搭配栏
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function removeCollocation(){
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
        $fr_id = $this->_post('fr_id');
        $ary_collocation = $_SESSION['collocation']['data'];
        foreach ($ary_collocation as $key=>$val){
            if($val['fr_id'] == $fr_id){
                unset($ary_collocation[$key]);
            }
        }
        if(empty($ary_collocation)){
            unset($_SESSION['collocation']['data']);
            $_SESSION['collocation']['is_show_coll'] = 0;
            echo '';exit;
        }else{
            $_SESSION['collocation']['is_show_coll'] = 1;
            //shuffle($ary_collocation);
            //获取自由搭配价格
            $_SESSION['collocation']['price_da'] = D('FreeRecommend')->getFreeCollotionPrice($ary_collocation);
            $_SESSION['collocation']['data'] = $ary_collocation;
            $this->assign('data',$ary_collocation);
            $this->assign($_SESSION['collocation']['price_da']);
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/collocation_column.html';
            $this->display($tpl);
        }
    }
    
    /**
     * 隐藏搭配栏
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function hidenCollColumn(){
        $_SESSION['collocation']['is_show_coll'] = 0;
        $this->ajaxReturn(true);
    }
    
    /**
     * 显示搭配栏
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function showCollColumn(){
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
        $this->assign('data',$_SESSION['collocation']['data']);
        $this->assign($_SESSION['collocation']['price_da']);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/collocation_column.html';
        $this->display($tpl);
    }
    
    /**
     * 自由搭配加入购物车
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-05
     */
    public function addFreeCollCart(){
        $cart_data = D('Cart')->ReadMycart();
        $ary_param = $_SESSION['collocation']['data'];
        $ary_coll_cart = array();
        $ary_pdt = array();
        $ary_num = array();
        $ary_fr_id = array();
        $type = 6;//表示自由搭配
        if (count($ary_param) < 2) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'至少选择两个商品做自由组合！'));
        }
        foreach ($ary_param as $key=>$coll){
            //检测自由搭配是否存在
            if(!D('FreeRecommend')->where(array('fr_id'=>$coll['fr_id'],'fr_status'=>1))->count()){
                $this->ajaxReturn(array('status'=>'error','msg'=>$coll['fr_name'].'不存在'));
            }
            $ary_pdt[$coll['fr_id']] = $coll['pdt_id'];
            $ary_num[$coll['fr_id']] = 1;
        }
        
        //购物车数组对象
        $ary_coll_cart['pdt_id'] = $ary_pdt;
        $ary_coll_cart['num'] = $ary_num;

        $ary_coll_cart['type'] = $type;
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
            $ary_db_carts = D('Cart')->ReadMycart();
            //无需判断存不存在，自动替换,即如果此自由组合存在先删除后新增
            $ary_db_carts['freerecommend'] = $ary_coll_cart;
            //写入购物车
            $Cart = D('Cart')->WriteMycart($ary_db_carts);
            if ($Cart === false) {
                $this->ajaxReturn(array('status'=>'error','msg'=>'加入购物车失败'));
            }else{
                unset($_SESSION['collocation']);
                $this->ajaxReturn(array('status'=>'success','msg'=>'加入购物车成功'));
            }
            
        } else {
            $ary_session_carts = (session("?Cart")) ? session("Cart") : array();
            $ary_session_carts['freerecommend'] = $ary_coll_cart;
            session("Cart", $ary_session_carts);
            unset($_SESSION['collocation']);
            $this->ajaxReturn(array('status'=>'success','msg'=>'加入购物车成功'));
        }
    }
	
	/**
     * 获取热销商品
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6
     * @date 2014-06-12
     *
     */
    public function getHotProducts() {
		$g_id = intval($this->_request('g_id'));
		//热销商品不存在去最热的商品
		if(empty($g_id)){
			$ary_good =  D('Goods')->join('fx_goods_info as gi on(fx_goods.g_id=gi.g_id)')
			->where(array('g_status'=>'1','g_on_sale'=>'1','g_hot'=>1,'g_is_combination_good'=>0))
			->field('fx_goods.g_id')
			->order('gi.`g_update_time` desc')
			->find();
			$g_id = $ary_good['g_id'];
		}
		$ary_request['gid'] = $g_id;
        $this->assign('ary_request',$ary_request);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/hotGoodsInfo.html';
        $this->display($tpl);
    }
	
	/**
     * 获取热销商品列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6
     * @date 2014-06-24
     *
     */
    public function getHotProductsList() {
		$ary_goods =  D('Goods')->join('fx_goods_info as gi on(fx_goods.g_id=gi.g_id)')
		->where(array('g_status'=>'1','g_hot'=>1,'g_on_sale'=>'1','g_is_combination_good'=>0))
		->field('gi.g_picture,fx_goods.g_id as gid,gi.g_name as gname')
		->order('gi.`g_salenum` desc')
		->limit(10)
		->select();
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/hotGoodsList.html';
		$this->assign('ary_goods',$ary_goods);
        $this->display($tpl);
    }
	
	/**
     * 将商品加入收藏夹
     * @author Wangguibin<wanghui@guanyisoft.com>
     * @date 2015-07-02
     */
    public function doAddGoodsCollect(){
        $ary_post = $this->_post();
		$ary_post['add_time'] = time();
        if(!empty($ary_post['gid']) && isset($ary_post['gid'])){
            $member = session("Members");
            if(!empty($member['m_id'])){
                //$ary_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array("g_id"=>$ary_post['gid']))->find();
				$ary_goods = D('Gyfx')->selectOneCache('goods','g_id', array('g_id'=>$ary_post['gid']), $ary_order=null,300);				
                if(!empty($ary_goods) && is_array($ary_goods)){
                    $arr_collect = M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$member['m_id'],"g_id"=>$ary_goods['g_id']))->find();
                    if(!empty($arr_collect) && is_array($arr_collect)){
                        $this->ajaxReturn(array('status'=>'0','info'=>"该商品已加入收藏"));
                    }else{
                        $arr_res = M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->add(array("m_id"=>$member['m_id'],"g_id"=>$ary_goods['g_id'],"add_time"=>date('Y-m-d H:i:s',$ary_post['add_time'])));
                        if(false !== $arr_res){
							D('Gyfx')->deleteOneCache('collect_goods','count(*) as num', array('g_id'=>$ary_goods['g_id']), $ary_order=null,300);							
                            $this->ajaxReturn(array('status'=>'1','info'=>"加入收藏成功"));
                        }else{
                            $this->ajaxReturn(array('status'=>'0','info'=>"加入失败"));
                        }
                    }
                }else{
                    $this->ajaxReturn(array('status'=>'0','info'=>"该商品不存在或者已经下架"));
                }
            }else{
                $this->ajaxReturn(array('status'=>'0','info'=>L('NO_LOGIN')));
            }
            
        }else{
            
        }
    }
    /**
     * 获取商品详情页sku信息（只支持2维规格）
     * request gid
     * return
     * pdt_id,pdt_stock,pdt_sale_price,pdt_market_price skuNames gid skus specName authorize
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-10-28
     */
    public function getDetailSkusCompatible() {
        $ary_post = $this->_post();
        if(empty($ary_post)){
            $ary_post = $this->_get();
            $ary_post['item_id'] && $ary_post['gid'] = $ary_post['item_id'];
        }
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_where = array();
        $ary_where['g_id'] = array('eq', intval($ary_post['gid']));
        $goodsSpec = D('GoodsSpec');
        $ary_goods = array();
        if (!empty($ary_post['gid'])) {
            $ary_product_feild = array('pdt_sn','pdt_stock','pdt_id', 'pdt_sale_price', 'pdt_market_price', 'g_id');
            $where = array();
            $where['g_id'] = intval($ary_post['gid']);
            $where['pdt_status'] = '1';
            $ary_pdt = $products->field($ary_product_feild)->where($where)->limit(150)->select();
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                if(isset($_SESSION['Members']['m_id'])){
                    $obj_price = new PriceModel($_SESSION['Members']['m_id'],1);
                }
                $int_num = count($ary_pdt);
                $stock_i = 0;
                foreach ($ary_pdt as $kypdt => $valpdt) {
                    //如果会员为登录状态，优先取一口价->会员等级价-
                    if(isset($_SESSION['Members']['m_id'])){
                        $ary_pdt[$kypdt]['pdt_sale_price'] = $obj_price->getItemPrice($valpdt['pdt_id']);
                        //如果是单规格商品，把商品价格也一并替换掉
                        if($int_num ==1){
                            $ary_goods['gprice'] = $ary_pdt[$kypdt]['pdt_sale_price'];
                        }

                    }
                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id'],1);
                    if (!empty($specInfo['color'])) {
                        if (!empty($specInfo['color'][1])) {
                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                        }
                    }
                    if (!empty($specInfo['size'])) {
                        if (!empty($specInfo['size'][1])) {
                            $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                        }
                    }
                    $ary_pdt[$kypdt]['specName'] = $specInfo['spec_name'];
                    $ary_pdt[$kypdt]['skuName'] = $specInfo['sku_name'];
                    $stock_i += $valpdt['pdt_stock'];
                }
                $ary_goods['gstock'] = $stock_i;

                foreach ($skus as $key => &$sku) {
                    $skus[$key] = array_unique($sku);
                }
            }
            if (!empty($skus)) {
                $ary_goods['skuNames'] = $skus;
            }
        }
        if(!empty($ary_pdt)){
            $ary_goods['gid'] = intval($ary_post['gid']);
            //授权线判断是否允许购买
            $ary_goods['authorize'] = true;
            if(isset($_SESSION['Members']['m_id'])){
                $ary_goods['authorize'] = D('AuthorizeLine')->isAuthorize($_SESSION['Members']['m_id'], $ary_goods['gid'],1);
            }
            $ary_goods['skus'] = $ary_pdt;
        }
        //模糊库存提示
        $member = session('Members');
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $member_level_id = $member['member_level']['ml_id'];
        if(empty($member)){
            $member_level_id = 0;
        }
        if((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK']==1 ){
            if($stock_data['USER_TYPE']=='all'){
                $stock_data['level'] =true;
            }else{
                $ary_user_level =explode(",",$stock_data['USER_TYPE']);
                $stock_data['level'] = in_array($member_level_id,$ary_user_level);
            }
        }
        //echo "<pre>";print_r($stock_data);die;
        $this->assign("stock_data", $stock_data);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/goodsDetailSku.html';
        $this->assign("detail", $ary_goods);
        // echo "<pre>";print_r($ary_goods);exit;
        $this->display($tpl);
    }
	/**
     * 获取商品详情页sku信息
	 * @request item_type,item_id,gid
     * @author Nick
     * @date 2015-11-20
     */
    public function getDetailSkus() {
        $ary_config = D('SysConfig')->getConfigs('GY_GOODS', 'VARIABLE_DEPTH');
        $depth = 2;
        if(!empty($ary_config)) {
            $variable_depth = reset($ary_config);
            $depth = $variable_depth["sc_value"];
        }
        $ary_post = $this->_request();
        //销售类型（团购，预售，秒杀，正常购物，...）
        $item_type = $ary_post['item_type'];
        $item_id = $ary_post['item_id'];
        $v = $ary_post['v'];
        //兼容老版本的做法
        if($depth == 2 && !in_array($item_type, array(5,7,8))) {
            $this->getDetailSkusCompatible();
            die;
        }

        $members = session('Members');
		$tpl = C('TMPL_PARSE_STRING.__TPL__'). 'goodsDetailSku'.$v.'.html';
		switch($item_type) {
			case 5:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'bulkDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Groupbuy')->getDetails($item_id, $members);
				break;
            case 7:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'presaleDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Presale')->getDetails($item_id, $members);
				break;
            case 8:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'spikeDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Spike')->getDetails($item_id, $members);
				break;
			case 11:
                $_tpl = C('TMPL_PARSE_STRING.__TPL__') . 'integralDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Integral')->getDetails($item_id, $members);
				break;
			default:
				$ary_goods_pdts = D('Goods')->getDetails($item_id, $members);
				break;
		}
        $tpl = '.' . $tpl;
		$this->assign($ary_goods_pdts);
        $this->display($tpl);

    }
}