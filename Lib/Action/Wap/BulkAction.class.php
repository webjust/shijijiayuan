<?php
/**
 * Wap团购类
 *
 * @author  <zhuwenwei@guanyisoft.com>
 * @date 2015-08-10
 */
class BulkAction extends WapAction{
    /**
     * 控制器初始化
     * @author  <zhuwenwei@guanyisoft.com>
	 * @date 2015-08-10
     */
    public function _initialize() {
        parent::_initialize();
    }
	
     /**
     * ajax动态获取商品列表信息
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @author zhuwenwei <zhuwenwei@guangyisoft.com>
     * @date 2015-08-17
      +----------------------------------------------------------
     */	
    public function ajaxBulkList() {
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            $result= array("html"=>'',"start"=>$this->_request('start'), "success"=> 0);
            $this->ajaxReturn($result,"无操作权限",0);
            die; 
        }
        //显示页面
        $ary_request = $get = $this->_request();
        $bulk = D('Groupbuy');
		$gp_city = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM');
        $array_where = array('is_active'=>1,'deleted'=>0,
                            // 'gp_start_time'=>array('lt',date('Y-m-d H:i:s')),
                             'gp_end_time'=>array('gt',date('Y-m-d H:i:s')));
							 
        if($get['startPrice']>=0 && isset($get['startPrice'])){
            if(!empty($get['startPrice']) && $get['startPrice'] >= '5000'){
                $array_where['gp_price'] = array("EGT",$get['startPrice']);
            }else{
                $array_where['gp_price'] = array("between",array($get['startPrice'],$get['endPrice']));
            }
        }
        if(!empty($get['scid'])){
            $array_where['gc_id'] = $get['scid'];
        }
        $count = $bulk->where($array_where)->count();
        $obj_page = new Pager($count, 6);
        $bulkList = $bulk->where($array_where)
                             ->limit(($get['start']-1)*6,6)
                             ->select();					 
        foreach ($bulkList as $ky=>$kv){
			//七牛图片显示
			$bulkList[$ky]['gp_picture'] = D('QnPic')->picToQn($kv['gp_picture']); 
            $goods_info = D('Goods')->where(array('g_id'=>$kv['g_id']))->count();
            if($goods_info == 0){
                unset($bulkList[$ky]);
            }
        }
        //验证区域
        if(!empty($bulkList) && is_array($bulkList)){
            foreach($bulkList as $key=>$val){
                if(!empty($get['cr_id'])){
                    $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count();
                    if($result_show == 0){
                        unset($bulkList[$key]);
                    }
                }
            }
        }
        //获取团购商品规格属性
        $goodsSpec = D('GoodsSpec');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        foreach($bulkList as $k=>$val){
            $tag['gid'] = $val['g_id'];
            $info = D('ViewGoods')->goodDetail($tag);
            if(!empty($info) && is_array($info)){
                $bulkList[$k]['detail'] = $info['list'];
            }
        }				 
        //数据处理
        foreach($ary_request as &$str_info){
        	htmlspecialchars($str_info);
        } 
        $tpl = $this->wap_theme_path."ajaxBulkList.html";
		//$tpl ='./Public/Tpl/mytest/wap/default/ajaxBulkList.html';
		//dump($tpl);die;
        $this->assign("ary_request", $ary_request);
        $this->assign('data',$bulkList);
        $this->assign('get',$get);	
        ob_start();
        $this->display($tpl);
        $html = ob_get_contents(); 
		
        ob_end_clean();
        $result= array("html"=>$html,"start"=>$ary_request['start']+5, "success"=> 1);
        $this->ajaxReturn($result,"获取成功",1);
    }   
    /**
     * 前台团购列表页
     *
	 * @author  <zhuwenwei@guanyisoft.com>
	 * @date 2015-08-10
     */
    public function Index(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Wap/Index/index'));exit;
            }
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
            exit;
        }
        $get = $this->_get();
        unset($get['_URL_']);
		//显示页面
        $ary_request = $this->_request();
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_city = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM');
        
        $array_where = array('is_active'=>1,'deleted'=>0,
                            // 'gp_start_time'=>array('lt',date('Y-m-d H:i:s')),
                             'gp_end_time'=>array('gt',date('Y-m-d H:i:s')));
        $count = $groupbuy->where($array_where)->count();
        $obj_page = new Pager($count, 12);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $bulkList = $groupbuy->where($array_where)
                             ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                             ->select();
		// echo "<pre>";
		// print_r($bulkList);
		// echo "</pre>";die;
        foreach ($bulkList as $key=>$val){
			//七牛图片显示
			$bulkList[$key]['gp_picture'] = D('QnPic')->picToQn($val['gp_picture']); 
            $bulkList[$key]['cust_price'] = M('goods_info')->where(array('g_id'=>$val['g_id']))->getField('g_price');
            //取出价格阶级
            $rel_bulk_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
			
            $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
            $bulkList[$key]['gp_now_number'] = $buy_nums;
            $array_f = array();
            $current_num_range=0;
            foreach ($rel_bulk_price as $rbp_k=>$rbp_v){
                if($buy_nums > $rbp_v['rgp_num'] && ($rbp_v['rgp_num'] > $current_num_range)){
                    $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
                    $current_num_range = $rbp_v['rgp_num'];
                }
            }
            if(!empty($array_f)){
                $array_max = new ArrayMax($array_f);
                $rgp_num = $array_max->arrayMax();
                $bulkList[$key]['gp_price'] = $gp_price->where(array('gp_id'=>$val['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
            }
            $bulkList[$key]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$val['g_id']))
                ->order('pdt_sale_price asc')
                ->getField('pdt_sale_price');
            switch($val['gp_tiered_pricing_type']) {
                case 2:
                    $bulkList[$key]['gp_price'] = $sale_price*$bulkList[$key]['gp_price'];
                    break;
                default:
                    $bulkList[$key]['gp_price'] = $sale_price - $bulkList[$key]['gp_price'];
                    break;
            }
            $bulkList[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
            //验证价格
            $i = 0;
            if(!empty($ary_request['startPrice']) && !empty($ary_request['endPrice'])){
                if($bulkList[$key]['gp_price'] < $ary_request['startPrice'] || $bulkList[$key]['gp_price'] > $ary_request['endPrice']){
                    $i++;
                }
            }
            if(!empty($ary_request['startPrice']) && empty($ary_request['endPrice'])){
                if($bulkList[$key]['gp_price'] < $ary_request['startPrice']){
                    $i++;
                }
            }
            if(empty($ary_request['startPrice']) && !empty($ary_request['endPrice'])){
                if($bulkList[$key]['gp_price'] > $ary_request['endPrice']){
                    $i++;
                }
            }
            if($i != 0){
                unset($bulkList[$key]);
            }else{
                //验证区域
                if(!empty($ary_request['cr_id'])){
                    $result_show = $gp_city->where(array('cr_id'=>$ary_request['cr_id'],'gp_id'=>$val['gp_id']))->count();
                    if($result_show == 0){
                        unset($bulkList[$key]);
                    }
                }
            }
			
			if(strtotime($val['gp_start_time']) > mktime()){
				//团购未开始
				$bulkList[$key]['stat_time'] = '1';
			}elseif((strtotime($val['gp_start_time']) < mktime()) && (strtotime($val['gp_end_time'])< mktime())){
				//团购已结束
				$bulkList[$key]['stat_time'] = '2';
			}else{
				$bulkList[$key]['stat_time'] = '3';//正在团购中
			}
			if(empty($bulkList[$key]['gp_picture'])){
				unset($bulkList[$key]);
			}

            
        }//echo "<pre>";print_r($bulkList);print_r($get);exit;
        if(!empty($ary_request['cr_id'])){
            $ary_request['cr_name'] = M('city_region')->where(array('cr_id'=>$ary_request['cr_id']))->getField('cr_name');
        }
        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE','','',1);
        $this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . '- 今日团购推荐');
        $groupbuySet = M('groupbuy_set',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gs_id'=>1))->find();
        $price_set = unserialize($groupbuySet['gs_related_price']);
        $city_id = array();
        foreach ($bulkList as $blK=>$blV){
			$bulkList[$blK]['gp_picture']=D('QnPic')->picToQn($bulkList[$blK]['gp_picture']);
			$bulkList[$blK]['gp_desc']=D('ViewGoods')->ReplaceItemDescPicDomain($bulkList[$blK]['gp_desc']);;
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
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . '/bulkList.html';
        } else {
            $tpl = $this->wap_theme_path.'bulkList.html';
        }
		// echo "<pre>";
		// print_r($bulkList);
		// echo "</pre>";die;
        $this->assign('data',$bulkList);
        $this->assign('priceSet',$price_set);
        $this->assign('gs_timeshow_status',$groupbuySet['gs_timeshow_status']);
        $this->assign('pagearr',$pageInfo);
        $this->assign('ary_request',$ary_request);
        $this->assign('city',$ary_city);
        $this->display($tpl);
    }
    
    /**
     * 团购详情页
	 * @author  <zhuwenwei@guanyisoft.com>
	 * @date 2015-08-10
     */
    public function detail_old(){
		$ary_request = $this->_request();
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
            exit;
        }
        $gp_id = $this->_get('gp_id');
        $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
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
            $this->error('此商品不存或已下架！',U('Wap/Products/Index'));
        }		
		//七牛图片显示
		$data['gp_picture'] = D('QnPic')->picToQn($data['gp_picture']); 
		$data['gp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($data['gp_desc']); 
        $data['cust_price'] = M('goods_info')->where(array('g_id'=>$data['g_id']))->getField('g_price');
        
        //取出价格阶级
        $rel_bulk_price = $gp_price->where(array('gp_id'=>$data['gp_id']))->select();
        
        $data['rel_bulk_price'] = $rel_bulk_price;
        
        //目前已参团人数
        $buy_nums = $data['gp_pre_number'] + $data['gp_now_number'];
        
        $array_f = array();
        foreach ($rel_bulk_price as $rbp_k=>$rbp_v){
            if($buy_nums >= $rbp_v['rgp_num']){
                $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
            }
        }
       
        if(!empty($array_f)){
            $array_max = new ArrayMax($array_f);
            $rgp_num = $array_max->arrayMax();
            
            $data['gp_price'] = $gp_price->where(array('gp_id'=>$data['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
        }//echo "<pre>";print_r($data);die();
        //获取商品基本信息
        $field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic,g_desc as gdesc';
        $goods_info = D('GoodsInfo')->field($field)->where(array('g_id'=>$data['g_id']))->find();
        $goods_info['gpic'] = '/'.ltrim($goods_info['gpic'],'/');
		$goods_info['gpic'] = D('QnPic')->picToQn($goods_info['gpic']);
		$goods_info['gdesc'] = D('ViewGoods')->ReplaceItemDescPicDomain($goods_info['gdesc']);;
        $goods_info['save_price'] = $goods_info['gprice'] - $data['gp_price'];
        //授权线判断是否允许购买
        $goods_info['authorize'] = true;
        if (!empty($goods_info) && is_array($goods_info)) {
            $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock');
            $where = array();
            $where['g_id'] = $data['g_id'];
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
                $goods_info['skuNames'] = $skus;
            }else{
                $goods_info['pdt_id'] = $ary_pdt[0]['pdt_id'];
            }
        }
        $data['gp_overdue_start_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_start_time']));
        $data['gp_overdue_end_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_end_time']));
        $goods_info['skus'] = $ary_pdt;
        $data['good_info'] = $goods_info;
        //判断当前团购数量是否达到上限（总限量）
        if($data['gp_now_number'] >= $data['gp_number']){
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
        //已参团人
        $data['gp_now_number'] = $buy_nums;
		$data['gp_picture'] = D('QnPic')->picToQn($data['gp_picture'],500,500);
		$data['gp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($data['gp_desc']);
        //echo "<pre>";print_r($data);exit;
        $ary_request = $this->_request();
        $this->assign('ary_request',$ary_request);

        $this->assign('data',$data);
        $this->assign('page_title', '【今日推荐】 '.$data['gp_title']);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . '/bulkDetail.html';
        } else {
            $tpl = $this->wap_theme_path.'bulkDetail.html';
        }
		
		//获取商品详情描述	
        $goods_desc = D("GoodsInfo")->where(array('g_id'=>$data['g_id']))->find();
        $this->assign("goods_desc",$goods_desc);
		
		//获取团购说明
		if($gp_id){
			$bulk_info = D("Groupbuy")->where(array('gp_id'=>$gp_id))->field('gp_goodshow_status,gp_mobile_desc')->find();
			$is_show_desc = 1;
			if($bulk_info['gp_goodshow_status'] == 0){
				$is_show_desc = 0;
			}
			$this->assign("is_show_desc",$is_show_desc);
			$gp_desc = $bulk_info['gp_mobile_desc'];
			//dump($bulk_info);
			if(!empty($gp_desc)){
				 $this->assign("gp_desc",$gp_desc);
			}
		}
		
        //获取评论数
        $comments_count = D("GoodsComments")->getGoodCommentsCount($ary_request['gid']);
        $comments_count = empty($comments_count) ? 0 : $comments_count;
        $this->assign("comments_count", $comments_count);
		
		//获取商品评论
		$int_g_id = $data['g_id'];
        if(!$int_g_id){
            $this->error("访问地址错误！");
            die;
        }
        $noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
        $comment_where['gcom_status']    = '1';
        $comment_where['g_id']  = $int_g_id;
        $comment_where['gcom_parentid'] = 0;
        $comment_where['gcom_star_score']= array('neq','');//去掉追加评论 追加评论分数为0
        $comment_where['u_id'] = 0;
        $comment_where['gcom_verify'] = 1;
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = $comment['list_page_size'];
        $comment_data = $noticeObj->field('m.m_name,fx_goods_comments.*')  
                        ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
                        ->where($comment_where)
                        ->order('gcom_update_time desc')
                        ->page($page_no,$page_size)
                        ->select();
        
        $good_count = 0;
        $normal_count = 0;
        $bad_count = 0;
        $all_count = 0;
        foreach ($comment_data as $key=>$val){
            switch($val['gcom_star_score']){
                case 60:
                    $good_count++;
                    break;
                case 80:
                    $good_count++;
                    break;
                case 100:
                    $good_count++;
                    break;
                case 40:
                    $normal_count++;
                    break;
                case 20:
                    $bad_count++;
                    break;
                
            }
            $all_count++;
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')
                ->where(array("gcom_parentid" => $val['gcom_id']))->find();

            $comment_data[$key]['reply'] = $parent_data;
        }
//        dump($data);die;
        //好评率

        $score_g = ($good_count/$all_count)*100;
        $score_n = ($normal_count/$all_count)*100;
        $score_b = ($bad_count/$all_count)*100;
        $score = '0';
        if($score_g == '100'){
            $score = '5';
        }else if($score_g >='80' && $score_g <'100'){
            $score = '4';
        }else if($score_g >='60' && $score_g <'80'){
            $score = '3';
        }else if($score_g >='40' && $score_g <'60'){
            $score = '2';
        }else if($score_g >='20' && $score_g <'40'){
            $score = '1';
        }
        $type = explode(',',$comment['comment_show_condition']);
        $comment['type'] = $type[0];
        $page['nowPage'] = $this->_get('p');
		
		//遍历获取追加评论
		foreach ($comment_data as $k=>$v){
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $v['gcom_id']))->find();
            $comment_data[$k]['reply'] = $parent_data;
			//再次评论
			$recomment_where = $where;
			unset($recomment_where['gcom_star_score']);
			$recomment_where['gcom_star_score'] = '';
			$recomment_where['m_id'] = $comment_data[$k]['m_id'];
			$recomment_where['gcom_order_id'] = $comment_data[$k]['gcom_order_id'];
            $recomment_data = $noticeObj->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();
			
            //追评回复
			if(!empty($recomment_data)){
				foreach($recomment_data as $sub_key=>$rdata){
					$sub_parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
					$recomment_data[$sub_key]['reply'] = $sub_parent_data;					
				}	
			}
			$comment_data[$k]['recomment'] = $recomment_data;	

            if($v['cr_id']) {
                $cr_path = D('CityRegion')->where(array('cr_id'=>$v['cr_id']))->getField('cr_path');
                $ary_cr_path = explode('|', $cr_path);
                $cr_name = D('CityRegion')->where(array('cr_id'=>$ary_cr_path['1']))->getField('cr_name');
                $comment_data[$k]['cr_name'] = $cr_name;
            }
        }
        $this->assign("comment",$comment);
        $this->assign("all_count",$all_count);
        $this->assign("good_count",$good_count);
        $this->assign("normal_count",$normal_count);
        $this->assign("bad_count",$bad_count);
        $this->assign("score_g",$score_g);
        $this->assign("score_n",$score_n);
        $this->assign("score_b",$score_b);
        $this->assign('comment_data',$comment_data);
        $this->assign('page',$page);
		
        //获取咨询总数
        $advice_where = array(
            'g_id'=>$ary_request['gid'],
            'pc_is_reply'=>1
        );
        $advice_count = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->where($advice_where)
            ->count();
        $this->assign('advice_count',$advice_count);
		
		//获取咨询内容
        $ary_where = array();
        if (!empty($ary_request['title'])) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_question_title'] = array('LIKE', "%" . $ary_request['title'] . "%");
        }
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.g_id'] = $ary_request['gid'];
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = 1;
        $ary_data = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods_info.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->join(' '.C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods_info.g_id")
            ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
            ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
            ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")->select();
		
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
		
        $page['nowPage'] = $ary_request['page'];
        $this->assign("filter", $ary_request);
        $this->assign("advice_data", $ary_data);
        $this->display($tpl);
    }

    public function detail(){
        $gp_id = $this->_get('gp_id');
        $members = session('Members');
        $ary_goods_pdts = D('Groupbuy')->getDetails($gp_id, $members);

        $this->assign($ary_goods_pdts);

        //获取评论数
        $comments_count = D("GoodsComments")->getGoodCommentsCount($ary_goods_pdts['g_id']);
        $comments_count = empty($comments_count) ? 0 : $comments_count;
        $this->assign("comments_count", $comments_count);

        //获取商品评论
        $int_g_id = $ary_goods_pdts['g_id'];
        if(!$int_g_id){
            $this->error("访问地址错误！");
            die;
        }
        $noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
        $comment_where['gcom_status']    = '1';
        $comment_where['g_id']  = $int_g_id;
        $comment_where['gcom_parentid'] = 0;
        $comment_where['gcom_star_score']= array('neq','');//去掉追加评论 追加评论分数为0
        $comment_where['u_id'] = 0;
        $comment_where['gcom_verify'] = 1;
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = $comment['list_page_size'];
        $comment_data = $noticeObj->field('m.m_name,fx_goods_comments.*')
            ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
            ->where($comment_where)
            ->order('gcom_update_time desc')
            ->page($page_no,$page_size)
            ->select();

        $good_count = 0;
        $normal_count = 0;
        $bad_count = 0;
        $all_count = 0;
        foreach ($comment_data as $key=>$val){
            switch($val['gcom_star_score']){
                case 60:
                    $good_count++;
                    break;
                case 80:
                    $good_count++;
                    break;
                case 100:
                    $good_count++;
                    break;
                case 40:
                    $normal_count++;
                    break;
                case 20:
                    $bad_count++;
                    break;

            }
            $all_count++;
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')
                ->where(array("gcom_parentid" => $val['gcom_id']))->find();

            $comment_data[$key]['reply'] = $parent_data;
        }
//        dump($data);die;
        //好评率

        $score_g = ($good_count/$all_count)*100;
        $score_n = ($normal_count/$all_count)*100;
        $score_b = ($bad_count/$all_count)*100;
        $score = '0';
        if($score_g == '100'){
            $score = '5';
        }else if($score_g >='80' && $score_g <'100'){
            $score = '4';
        }else if($score_g >='60' && $score_g <'80'){
            $score = '3';
        }else if($score_g >='40' && $score_g <'60'){
            $score = '2';
        }else if($score_g >='20' && $score_g <'40'){
            $score = '1';
        }
        $type = explode(',',$comment['comment_show_condition']);
        $comment['type'] = $type[0];
        $page['nowPage'] = $this->_get('p');

        $where = array();
        $where['g_id'] = $ary_goods_pdts['g_id'];
        $where['pdt_status'] = '1';
        //遍历获取追加评论
        foreach ($comment_data as $k=>$v){
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $v['gcom_id']))->find();
            $comment_data[$k]['reply'] = $parent_data;
            //再次评论

            $recomment_where = $where;
            unset($recomment_where['gcom_star_score']);
            $recomment_where['gcom_star_score'] = '';
            $recomment_where['m_id'] = $comment_data[$k]['m_id'];
            $recomment_where['gcom_order_id'] = $comment_data[$k]['gcom_order_id'];
            $recomment_data = $noticeObj->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();

            //追评回复
            if(!empty($recomment_data)){
                foreach($recomment_data as $sub_key=>$rdata){
                    $sub_parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
                    $recomment_data[$sub_key]['reply'] = $sub_parent_data;
                }
            }
            $comment_data[$k]['recomment'] = $recomment_data;

            if($v['cr_id']) {
                $cr_path = D('CityRegion')->where(array('cr_id'=>$v['cr_id']))->getField('cr_path');
                $ary_cr_path = explode('|', $cr_path);
                $cr_name = D('CityRegion')->where(array('cr_id'=>$ary_cr_path['1']))->getField('cr_name');
                $comment_data[$k]['cr_name'] = $cr_name;
            }
        }
        $this->assign("comment",$comment);
        $this->assign("all_count",$all_count);
        $this->assign("good_count",$good_count);
        $this->assign("normal_count",$normal_count);
        $this->assign("bad_count",$bad_count);
        $this->assign("score_g",$score_g);
        $this->assign("score_n",$score_n);
        $this->assign("score_b",$score_b);
        $this->assign('comment_data',$comment_data);
        $this->assign('page',$page);

        //获取咨询总数
        $advice_where = array(
            'g_id'=>$ary_goods_pdts['g_id'],
            'pc_is_reply'=>1
        );
        $advice_count = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->where($advice_where)
            ->count();
        $this->assign('advice_count',$advice_count);

        //获取咨询内容
        $ary_where = array();
        if (!empty($ary_request['title'])) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_question_title'] = array('LIKE', "%" . $ary_request['title'] . "%");
        }
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.g_id'] = $ary_request['gid'];
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = 1;
        $ary_data = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods_info.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->join(' '.C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods_info.g_id")
            ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
            ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
            ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")->select();

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