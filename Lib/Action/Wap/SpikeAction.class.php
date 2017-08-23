<?php
/**
 * 前台秒杀类
 *
 * @stage 7.8.6
 * @package Action
 * @subpackage Home
 * @author wangguibin<wangguibin@guanyisoft.com>
 * @date 2015-08-14
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class SpikeAction extends WapAction{
    
    /**
     * 控制器初始化
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-08-14
     */
    public function _initialize() {
        parent::_initialize();
    }
	
     /**
     * ajax动态获取商品列表信息
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @author Wangguibin <wangguibin@guangyisoft.com>
     * @date 2015-08-17
      +----------------------------------------------------------
     */
    public function ajaxSpikeList() {
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            $result= array("html"=>'',"start"=>$this->_request('start'), "success"=> 0);
            $this->ajaxReturn($result,"无操作权限",0);
            die; 
        }
        //显示页面
        $get = $this->_request();
        $mod = $this->getActionName();
        $spike = D($mod);
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
        $obj_page = new Pager($count, 4);
        $spikeList = $spike->where($array_where)
                             ->limit(($get['start']-1)*4,4)
                             ->select();	
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
        //数据处理
        foreach($ary_request as &$str_info){
        	htmlspecialchars($str_info);
        } 
		if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . 'Spike/ajaxSpikeList.html';
        } else {
            $tpl = $this->wap_theme_path.'Spike/ajaxSpikeList.html';
        }
        $this->assign("ary_request", $ary_request);
        $this->assign('data',$spikeList);
        $this->assign('get',$get);	
        ob_start();
        $this->display($tpl);
        $html = ob_get_contents(); 
        ob_end_clean();
        $result= array("html"=>$html,"start"=>$ary_request['start']+5, "success"=> 1);
        $this->ajaxReturn($result,"获取成功",1);
    }
	
    /**
     * 前台秒杀列表页
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-08-14
     */
    public function index(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Wap/Ucenter/index'));exit;
            }
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/Index/index'));
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
        $obj_page = new Pager($count, 4);
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
        $this->assign('page',$pageInfo);
        //dump($spikeList);exit();
        $this->assign('data',$spikeList);
        $this->assign('get',$get);

		if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . 'Spike/spikeList.html';
        } else {
            $tpl = $this->wap_theme_path.'Spike/spikeList.html';
        }
		$title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE','','',1);
		$this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . '- 今日秒杀推荐');
        $this->display($tpl);
    }
    
    /**
     * 前台秒杀详情页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-28
     */
    public function detail(){
        $ary_request = $this->_request();
        $int_sp_id = (int)$ary_request['sp_id'];
        $ary_members = session('Members');
        $spikeModel = D("Spike");

        $ary_details = $spikeModel -> getDetails($int_sp_id, $ary_members);
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
		
		//判断该登陆会员是否已秒杀过该商品
		$ary_where = array();
		$ary_where[C('DB_PREFIX')."spike_log.m_id"] = $ary_members['m_id'];
        $ary_where[C('DB_PREFIX')."spike_log.sp_id"] = $int_sp_id;
		$ary_spike=D('spike_log')->where($ary_where)->find();
		//echo "<pre>";print_r($ary_spike);die;
		if(!empty($ary_spike) && is_array($ary_spike)){
			$is_spike = 1;
		}else{
			$is_spike = 0;
		}
		$this->assign('is_spike',$is_spike);
		
        //温馨提示
        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
        $this->assign('warm_prompt',$warm_prompt['sc_value']);
        $pid = $this->getCateParent($ary_data['g_id']);
        $ary_request['cid'] = $pid; 		
        //dump($ary_data);exit();
        $common = D('SysConfig')->getCfgByModule('goods_comment_set','1');
        $this->assign("common",$common);
        $this->assign("ary_request", $ary_request);
        //获取评论数
        $comments_count = D("GoodsComments")->getGoodCommentsCount($ary_request['gid']);
        $comments_count = empty($comments_count) ? 0 : $comments_count;
        $this->assign("comments_count", $comments_count);

        $int_g_id = $ary_request['gid'];
        $noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
//        dump($comment);die;
        $comment_where['gcom_status']    = '1';
        $comment_where['g_id']  = $int_g_id;
        $comment_where['gcom_parentid'] = 0;
        $comment_where['gcom_star_score']= array('neq','');//去掉追加评论 追加评论分数为0
        $comment_where['u_id'] = 0;
        $comment_where['gcom_verify'] = 1;
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = $comment['list_page_size'];
        $data = $noticeObj->field('m.m_name,fx_goods_comments.*')
            ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
            ->where($comment_where)
            ->order('gcom_update_time desc')
            ->page($page_no,$page_size)
            ->select();

        $good_count = 0;
        $normal_count = 0;
        $bad_count = 0;
        $all_count = 0;
        foreach ($data as $key=>$val){
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

            $data[$key]['reply'] = $parent_data;
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
        foreach ($data as $k=>$v){
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $v['gcom_id']))->find();
            $data[$k]['reply'] = $parent_data;
            //再次评论
            $recomment_where = $where;
            unset($recomment_where['gcom_star_score']);
            $recomment_where['gcom_star_score'] = '';
            $recomment_where['m_id'] = $data[$k]['m_id'];
            $recomment_where['gcom_order_id'] = $data[$k]['gcom_order_id'];
            $recomment_data = $noticeObj->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();

            //追评回复
            if(!empty($recomment_data)){
                foreach($recomment_data as $sub_key=>$rdata){
                    $sub_parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
                    $recomment_data[$sub_key]['reply'] = $sub_parent_data;
                }
            }
            $data[$k]['recomment'] = $recomment_data;

            if($v['cr_id']) {
                $cr_path = D('CityRegion')->where(array('cr_id'=>$v['cr_id']))->getField('cr_path');
                $ary_cr_path = explode('|', $cr_path);
                $cr_name = D('CityRegion')->where(array('cr_id'=>$ary_cr_path['1']))->getField('cr_name');
                $data[$k]['cr_name'] = $cr_name;
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
        $this->assign('comment_data',$data);
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
		
        $this->setTitle('商品秒杀页'.$ary_data['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
		if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . '/Spike/spikeDetail.html';
        } else {
            $tpl = $this->wap_theme_path.'Spike/spikeDetail.html';
        }
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
     * @date 2015-08-14
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
