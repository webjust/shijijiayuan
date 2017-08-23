<?php

/**
 * 前台商品展示类
 *
 * @package Action
 * @subpackage Wap
 * @stage 7.5
 * @author Nick <shanguangkun@guangyisoft.com>
 * @date 2014-05-22
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ProductsAction extends WapAction {
	
	protected $history_expire = 86400;	//默认浏览商品历史保存24小时
	protected $max_num = 10;			//默认浏览商品最多保存10个
    /**
     * 初始化操作
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
     */
    public function _initialize() {
		$is_weixin = is_weixin();
        $Member = session("Members");

        if(empty($Member) && !isset($Member['m_id'])){
			//微信商城自动注册登录会员
            if($_SESSION['no_wx'] !=1 && $is_weixin == 1){
				$this->doCheckLogin();
			}
            //$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			//$this->redirect(U('/Wap/User/Login')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
        }
        parent::_initialize();
        $this->wap_theme_path = './Public/Tpl/qiaomoxuan/wap_v2/lxkj/';
    }
	
    /**
     * 微信商城登陆判断
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-18
     */	
	public function doCheckLogin(){
		$_SESSION['is_product'] = 1;
		$_SESSION['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		$this->redirect(U('/Wap/User/isWeiXin')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
	}
	
    /**
     * 商品列表页
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
     */
    public function index() {
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN',null,null,1);
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Wap/Index/index'));exit;
            }
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
            exit;
        }
        //显示页面
        $ary_request = $this->_request();
        //数据处理
        foreach($ary_request as &$str_info){
        	htmlspecialchars($str_info);
        }
        //分类id
        $ary_request['cid'] = htmlspecialchars($ary_request['cid'],ENT_QUOTES);
		if($ary_request['cid']){
			//$gc_name = D('GoodsCategory')->where(array('gc_id'=>$ary_request['cid']))->getField('gc_name');
			$gc_info = D('Gyfx')->selectOneCache('goods_category','gc_name', array('gc_id'=>$ary_request['cid']), $ary_order=null,$time=3600);
			$gc_info = $gc_info['gc_name'];
		}
        //品牌id
		if($ary_request['bid']){
			//$gb_name = D('GoodsBrand')->where(array('gb_id'=>$ary_request['bid']))->getField('gb_name');
			$gb_info = D('Gyfx')->selectOneCache('goods_brand','gb_name', array('gb_id'=>$ary_request['bid']), $ary_order=null,$time=3600);
			$gb_name = $gb_info['gb_name'];
		}

        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE',null,null,1);
        //seo
        if(empty($gc_name) && isset($ary_request['keyword'])){
            $this->setTitle($ary_request['keyword'],'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && empty($ary_request['bid'])){
            $this->setTitle("全部分类",'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && isset($ary_request['bid'])){
            $this->setTitle($gb_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['bid']) && isset($ary_request['cid'])){
            $this->setTitle($gc_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(isset($ary_request['bid']) && isset($ary_request['cid'])){
            $this->setTitle($gc_name.$gb_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }
        $this->assign('itemInfo', $ary_request);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = $this->wap_theme_path.'preview_' . $ary_request['dir'] . '/productList.html';
        } else {
            $tpl = $this->wap_theme_path.'productList.html';
        }
        $ary_return = array();
        if($_GET['price']){
            $ary_return['price'][0] = $_GET['price'];
            $ary_return['price'][1] = $_GET['price_col'];
        }
        if($_GET['hot']){
            $ary_return['hot'][0] = $_GET['hot'];
            $ary_return['hot'][1] = $_GET['hot_col'];
        }
        if($_GET['new']){
            $ary_return['new'][0] = $_GET['new'];
            $ary_return['new'][1] = $_GET['new_col'];
        }
        $ary_request['path'] = !empty($ary_request['path'])?$ary_request["path"]:'';
        if(!empty($ary_request['path'])){
            $arr_path = explode(",", trim($ary_request['path'],","));
            if(!empty($arr_path) && is_array($arr_path)){
                foreach($arr_path as $ky=>$vl){
                    $paths = explode(":",$vl );
                    $ary_request['paths'][$paths[0]] = $paths[1];
                }
            }
        }
                
        switch($ary_request['order']){
            case "hot":
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
            case "_hot":
                $ary_request['_order']['hot'] = 'hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
            case "new":
                $ary_request['_order']['new'] = '_new';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                break;
            case "_new":
                $ary_request['_order']['new'] = 'new';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                break;
            case "price":
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['new'] = '_new';
                break;
            case "_price":
                $ary_request['_order']['price'] = 'price';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['new'] = '_new';
                break;
            default :
                $ary_request['order'] = '_hot';
                $ary_request['_order']['hot'] = 'hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
        }
		
		//获取类目关联商品图片
        if($ary_request['cid']!=""){
			$field=array('ad_url,ad_pic_url');
			$rgc_where=array('gc_id'=>$ary_request['cid']);
			$rgc_order="sort_order asc";
			$ary_ads = D('RelatedGoodscategoryAds')->getListByCid($rgc_where,$field,$rgc_order,1);
			$this->assign('ary_ads', $ary_ads);
		}
		
        //获取会员浏览历史(5条)
//        $ary_history_data = $this->BrowsehistoryCount(5);
//        $this->assign('ary_history_data',$ary_history_data);
        $this->assign('ret',$ary_return);
//        dump($ary_request);die;
        $this->assign("ary_request", $ary_request);
        $this->display($tpl);
    }

    /**
     * 商品详情页
     * @params 商品ID:gid
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
     */
    public function detail() {
		$ary_request = $this->_request();
        $ary_pointresule = D('PointConfig')->getConfigs(null,true);
        $skudata = D('SysConfig')->getCfgByModule('GY_STOCK',1);
        $this->assign("skudata",$skudata);
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
        if ($_SESSION['Members']['m_id']) {
            $m_id = $_SESSION['Members']['m_id'];
			//未授权的商品不显示
			$check_authorizeline=D('AuthorizeLine')->isAuthorize($m_id, $ary_request['gid'],1);
			if(empty($check_authorizeline)){
				$this->error('此商品没有权限购买！',U('Wap/Products/Index'));
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
		
		//商城价显示最小价格
		$array_fetch_condition = array("g_id"=>$ary_request['gid'],"pdt_satus"=>1);
		$string_fields = "min(`pdt_market_price`) as `g_market_price`,min(`pdt_sale_price`) as `g_price`";
		//$array_result = D("GoodsProducts")->where($array_fetch_condition)->field($string_fields)->find();
		$array_result = D("Gyfx")->selectOneCache('goods_products',$string_fields, $array_fetch_condition, $ary_order=null,300);
		$this->assign('g_market_price',$array_result['g_market_price']);
		$this->assign('g_price',$array_result['g_price']);
		
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
		//echo "<pre>";print_r($stock_data['level']);die;
		$this->assign("stock_data", $stock_data);
		
		//获取商品评论
		
		$int_g_id = $ary_request['gid'];
        if(!$int_g_id){
            $this->error("访问地址错误！");
            die;
        }
	
        $noticeObj = D('GoodsComments');
		$comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
		$str_where['gcom_status'] = '1';
        $str_where['g_id']  = $int_g_id;
        $str_where['gcom_parentid'] = 0;
        $str_where['u_id'] = 0;
        $str_where['gcom_verify'] = 1;
		$str_where['gcom_star_score'] = array('gt',0);
		$score_total_str = $noticeObj->where($str_where)->sum('gcom_star_score');
        $str_count = $noticeObj->where($str_where)->count();
		$score_str = $score_total_str/$str_count;
		$str_score = round(($score_str/100)*5);
		
		$data_where['gcom_status'] = '1';
        $data_where['g_id']  = $int_g_id;
        $data_where['gcom_parentid'] = 0;
        $data_where['u_id'] = 0;
        $data_where['gcom_verify'] = 1;
		$data_where['gcom_star_score']= array('neq','');
		$data = $noticeObj->field('m.m_name,fx_goods_comments.*')  
                        ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
                        ->where($data_where)
                        ->order('gcom_update_time desc')
                        ->select();
        
		$all_count = 0;
        foreach ($data as $key=>$val){
            $all_count++;
        }
		$this->assign("all_count",$all_count);
		$this->assign("str_score",$str_score);
		
        //获取咨询内容
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
		
		
		
        //显示页面

        //过滤url非法数据
		$int_count =D("Gyfx")->getCountCache('goods_info',array('g_id'=>$ary_request['gid']),60);
        if(0 >= $int_count){
            $this->error('此商品不存或已下架！',U('Home/Products/Index'));
        }	
        $_goods = D("Goods")->where(array('g_id'=>$ary_request['gid']))->find();
        $_goods = D('Gyfx')->selectOneCache('goods_info','g_phone_desc,g_id,g_name,g_description,g_keywords',array('g_id'=>$ary_request['gid']));
				
        $this->assign('goods_type', $_goods['gt_id']);
        $goods = D("GoodsInfo")->where(array('g_id'=>$ary_request['gid']))->find();
        $goods = D('Gyfx')->selectOneCache('goods_info','g_phone_desc,g_id,g_name,g_description,g_keywords',array('g_id'=>$ary_request['gid']));
        $this->assign("goods_desc",$goods);		
        $this->setTitle($goods['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
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
        //相关商品推荐
		$ary_request['gid'] = intval($ary_request['gid']);
        /*$relatedgoods = D("Goods")->where(array('g_id'=>$ary_request['gid']))->getField('g_related_goods_ids');
		$otherwhere = array();
		$otherwhere['_string'] = 'find_in_set('.$ary_request['gid'].',g_related_goods_ids)';
		$othergoods = D("Goods")->where($otherwhere)->field('g_id')->find();
		$relatedgoods = trim($relatedgoods,",");
		$relatedgoods .=','.implode(',',$othergoods);
//        dump($relatedgoods);die;
        $where = array();
		$relatedgoods = trim($relatedgoods,",");
		
        $where['g_id'] = array('in',$relatedgoods);
        $rggoods = D("GoodsInfo")->where($where)->select();
	
        $this->assign('rggoods',$rggoods);*/
        
        
		//$warm_prompt = D('SysConfig')->where(array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'))->getField('sc_value');
        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
		$this->assign('warm_prompt',$warm_prompt['sc_value']);
        //获取此商品的扩展属性数据
        $array_cond = array("g_id" => $ary_request['gid'], "gs_is_sale_spec" => 0);
        $array_unsale_spec = D("RelatedGoodsSpec")->where($array_cond)->order(array("gs_id asc"))->select();
        
        foreach ($array_unsale_spec as $key => $val) {
            $array_unsale_spec[$key]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_name");
            if($val['gsd_id'] != 0){
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
            }
        }//echo "<pre>";print_r($array_unsale_spec);exit;
        $int_g_id = $ary_request['gid'];
        if($int_g_id){
            $ary_relatedgoods = D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
            $ary_relatedgoods = trim($ary_relatedgoods,",");
            $where = array();
            $where['g_id'] = array('in',$ary_relatedgoods);
            $ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture,g_salenum,g_salenum')->where($where)->select();
            $this->assign('ary_relate_goods',$ary_relate_goods);
        }
        $this->assign('array_unsale_spec', $array_unsale_spec);
        if (empty($ary_request['gid'])) {
            $this->error("只有商品详情页才能使用出价模块");
        } else {
            if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
                $tpl = $this->wap_theme_path .'preview_' . $ary_request['dir'] . '/productDetails.html';
            } else {
                $tpl = $this->wap_theme_path. 'productDetails.html';
            }
            //获取商品所属类目
            $pid = $this->getCateParent($ary_request['gid'],1);
            $ary_request['cid'] = $pid;
            //获取会员浏览历史(5条)
//            $ary_history_data = $this->BrowsehistoryCount(5);
//            $this->assign('ary_history_data',$ary_history_data);
            $this->assign("ary_request", $ary_request);
            $this->assign('page_description',$goods['g_description']);
            $this->setKeywords($goods['g_keywords']);


            $noticeObj = D('GoodsComments');
            $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
    //        dump($comment);die;
            $where['gcom_status']    = '1';
            $where['g_id']  = $int_g_id;
            $where['gcom_parentid'] = 0;
            $where['gcom_star_score']= array('neq','');//去掉追加评论 追加评论分数为0
            $where['u_id'] = 0;
            $where['gcom_verify'] = 1;
            $page_no = max(1,(int)$this->_request('p'));
            $page_size = $comment['list_page_size'];
            $data = $noticeObj->field('m.m_name,fx_goods_comments.*')  
                            ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
                            ->where($where)
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
            $count = $noticeObj->field('m.m_name,fx_goods_comments.*')
                ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
                ->where($where)
                ->count();
            $obj_page = new Pager($count, $page_size);
            $page = $obj_page->showArr();
            $page['nowPage'] = $page_no;

            //遍历获取追加评论
            foreach ($data as $k=>$v){
                $data[$k]['m_name'] = csubstr($v['m_name'],6);
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
            $this->assign('comm_data',$data);
        
            $ajax = $this->_get('ajax');
            if($ajax){
                $this->ajaxReturn($data,'JSON');
            }else{
                $this->display($tpl);
            }
        }

    }

	
	
	
	
    /**
     * 商品规格列表
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-30
     */
    public function specifications(){
        
        $ary_request = (int)$this->_get("g_id");
        if(!$ary_request){
            $this->error("参数错误！");
            exit;
        }
        $goods = D("GoodsInfo")->where(array('g_id'=>$ary_request))->find();
//        dump($goods);die;
        $this->setTitle($goods['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
        $this->setKeywords($goods['g_keywords']);
        $array_cond = array("fx_related_goods_spec.g_id" => $ary_request, "fx_related_goods_spec.gs_is_sale_spec" => 0);
        $array_unsale_spec = D("RelatedGoodsSpec")
            ->field("fx_related_goods_spec.*,fx_goods_spec.gs_name")
            ->join("fx_goods_spec ON fx_goods_spec.gs_id = fx_related_goods_spec.gs_id")
            ->where($array_cond)->order(array("fx_related_goods_spec.gs_id asc"))->select();
//        dump($array_unsale_spec);die;
        foreach ($array_unsale_spec as $key => $val) {
            $array_unsale_spec[$key]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_name");
            if($val['gsd_id'] != 0){
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
                $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");
            }
        }
//        echo "<pre>";print_r($array_unsale_spec);exit;
        $this->assign('array_unsale_spec', $array_unsale_spec);
        $tpl = $this->wap_theme_path."Products/specifications.html";
        $this->display($tpl);
    }
    
    /**
     * 商品描述页
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-30
     */
    public function description(){
        
        $ary_request = (int)$this->_get("g_id");
        if(!$ary_request){
            $this->error("参数错误！");
            exit;
        }
		//获取秒杀说明
		$sp_id = (int)$this->_get("sp_id");
		if($sp_id){
			$spike_info = D("Spike")->where(array('sp_id'=>$sp_id))->field('sp_goods_desc_status,sp_mobile_desc')->find();
			$is_show_desc = 1;
			if($spike_info['sp_goods_desc_status'] == 0){
				$is_show_desc = 0;
			}
			$this->assign("is_show_desc",$is_show_desc);
			$sp_desc = $spike_info['sp_mobile_desc'];
			if(!empty($sp_desc)){
				 $this->assign("sp_desc",$sp_desc);
			}
		}
		//获取团购说明
		$gp_id = (int)$this->_get("gp_id");
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
        $goods = D("GoodsInfo")->where(array('g_id'=>$ary_request))->find();
        //dump($goods);die;
        $this->setTitle($goods['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
        $this->setKeywords($goods['g_keywords']);
        $this->assign("goods",$goods);
        $tpl = $this->wap_theme_path ."Products/description.html";
        $this->display($tpl);
    }

    /**
     * 获取商品类目
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
     * 获取访问量信息
     *
     * @param boolean $increase 是否将访问量增加1
     * @return integer
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-22
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
    
    /**
     * ajax动态获取商品列表信息
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @author Nick <shanguangkun@guangyisoft.com>
     * @date 2014-05-26
      +----------------------------------------------------------
     */
    public function ajaxProductList() {
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            $result= array("html"=>'',"start"=>$this->_request('start'), "success"=> 0);
            $this->ajaxReturn($result,"无操作权限",0);
            die;
            
        }
        //显示页面
        $ary_request = $this->_request();
        //数据处理
        foreach($ary_request as &$str_info){
        	htmlspecialchars($str_info);
        }
        //分类id
        $ary_request['cid'] = htmlspecialchars($ary_request['cid'],ENT_QUOTES);
        $gc_name = D('GoodsCategory')->where(array('gc_id'=>$ary_request['cid']))->getField('gc_name');
        //品牌id
        $gb_name = D('GoodsBrand')->where(array('gb_id'=>$ary_request['bid']))->getField('gb_name');
        
        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE');
        //seo
        if(empty($gc_name) && isset($ary_request['keyword'])){
            $this->setTitle($ary_request['keyword'],'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && empty($ary_request['bid'])){
            $this->setTitle("全部分类",'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['cid']) && isset($ary_request['bid'])){
            $this->setTitle($gb_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(empty($ary_request['bid']) && isset($ary_request['cid'])){
            $this->setTitle($gc_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }elseif(isset($ary_request['bid']) && isset($ary_request['cid'])){
            $this->setTitle($gc_name.$gb_name,'TITLE_CATEGORY','DESC_CATEGORY','KEY_CATEGORY');
        }
        $this->assign('itemInfo', $ary_request);
        
        $ary_return = array();
        if($_GET['price']){
            $ary_return['price'][0] = $_GET['price'];
            $ary_return['price'][1] = $_GET['price_col'];
        }
        if($_GET['hot']){
            $ary_return['hot'][0] = $_GET['hot'];
            $ary_return['hot'][1] = $_GET['hot_col'];
        }
        if($_GET['new']){
            $ary_return['new'][0] = $_GET['new'];
            $ary_return['new'][1] = $_GET['new_col'];
        }
        $ary_request['path'] = !empty($ary_request['path'])?$ary_request["path"]:'';
        if(!empty($ary_request['path'])){
            $arr_path = explode(",", trim($ary_request['path'],","));
            if(!empty($arr_path) && is_array($arr_path)){
                foreach($arr_path as $ky=>$vl){
                    $paths = explode(":",$vl );
                    $ary_request['paths'][$paths[0]] = $paths[1];
                }
            }
        }
                
        switch($ary_request['order']){
            case "hot":
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
            case "_hot":
                $ary_request['_order']['hot'] = 'hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
            case "new":
                $ary_request['_order']['new'] = '_new';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                break;
            case "_new":
                $ary_request['_order']['new'] = 'new';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['price'] = '_price';
                break;
            case "price":
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['new'] = '_new';
                break;
            case "_price":
                $ary_request['_order']['price'] = 'price';
                $ary_request['_order']['hot'] = '_hot';
                $ary_request['_order']['new'] = '_new';
                break;
            default :
                $ary_request['order'] = '_hot';
                $ary_request['_order']['hot'] = 'hot';
                $ary_request['_order']['price'] = '_price';
                $ary_request['_order']['new'] = '_new';
                break;
        }
        $tpl = $this->wap_theme_path."Products/ajaxProductList.html";
        $this->assign('ret',$ary_return);
//        dump($ary_request);die;
        $this->assign("ary_request", $ary_request);
        ob_start();
        $this->display($tpl);
        $html = ob_get_contents(); 
        ob_end_clean();
        $result= array("html"=>$html,"start"=>$ary_request['start']+5, "success"=> 1);
//        echo json_encode($result);
        $this->ajaxReturn($result,"获取成功",1);
    }
    
    /**
     * 
     */
    public function getGoodsAdvice() {
        $ary_post = $this->_request();
        $ary_where = array();
        if (!empty($ary_post['title'])) {
            $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_question_title'] = array('LIKE', "%" . $ary_post['title'] . "%");
        }
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.g_id'] = $ary_post['g_id'];
        $ary_where[C('DB_PREFIX') . 'purchase_consultation.pc_is_reply'] = 1;
        /*$count = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->join(' '.C('DB_PREFIX')."goods ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods.g_id")
            ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
            ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
            ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")
            ->count();*/
        //$obj_page = new Pager($count, 10);
        //$page = $obj_page->showArr();
        $ary_data = D("PurchaseConsultation")->field(" ".C('DB_PREFIX')."goods_info.g_name,".C('DB_PREFIX')."purchase_consultation.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."members.m_name")
            ->join(' '.C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."purchase_consultation.g_id=".C('DB_PREFIX')."goods_info.g_id")
            ->join(' '.C('DB_PREFIX')."members ON ".C('DB_PREFIX')."purchase_consultation.m_id=".C('DB_PREFIX')."members.m_id")
            ->join(' '.C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."purchase_consultation.u_id=".C('DB_PREFIX')."admin.u_id")
            ->where($ary_where)->order(C('DB_PREFIX')."purchase_consultation.`pc_create_time` DESC")
           /* ->limit($obj_page->firstRow, $obj_page->listRows)*/->select();
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
       // $this->assign("page", $page);
        $this->assign("count", $count);
        $tpl = $this->wap_theme_path . "advice.html";
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
            'pc_type' => $ary_post['type'],
            'g_id' => $ary_post['gid'],
            'pc_question_title' => $ary_post['question_title'],
            'pc_question_content' => $ary_post['question_content'],
            'pc_create_time' => date("Y-m-d H:i:s")
        );
        if (!empty($_SESSION['Members']['m_id'])) {
            $data['m_id'] = $_SESSION['Members']['m_id'];
        }
        $ary_res = M('PurchaseConsultation', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
        if (FALSE != $ary_res) {
            $this->success("咨询成功");
            exit;
        } else {
            $this->error("咨询失败");
            exit;
        }
    }

    public function getAskedquestions() {
        $ary_post = $this->_request();
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
            }
        }
        $this->assign("count", $count);
//        echo "<pre>";print_r($ary_data);exit;
        $this->assign("filter", $ary_post);
        $this->assign("data", $ary_data);
        $this->assign("page", $page['linkPage']);
        $this->assign("count", $count);
        $tpl = $this->wap_theme_path . "askedquestions.html";
        $this->display($tpl);
    }

    public function getBuyRecordPage(){
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
        $list = D('GoodsInfo')->getBuyRecords($_REQUEST);
        $tpl = $this->wap_theme_path  . "buyrecord.html";
        $list['page']['nowPage'] = $this->_request('p');
        $this->assign('buynums',$list['buynums']);
        $this->assign('monthsales',$list['one_month_sales']);
        $this->assign('data',$list['data']);
        $this->assign('page',$list['page']);
        //$this->assign('count',$nums);
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
            $tpl = $this->wap_theme_path . "selectCitys.html";
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
        $tpl = $this->wap_theme_path . "compare.html";
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
        $tpl = $this->wap_theme_path . "comparelist.html";
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
    public function getCollGoodsPage(){
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
        $g_id = $this->_request('gid');
		$data = array();
		if($g_id){
			$data = D('FreeCollocation')->getFreeCollocationByGid($g_id);
		}
        $this->assign($data);
        $tpl = $this->wap_theme_path . "Products/coll_goods.html";
        $this->display($tpl);
    }
    
    /**
     * 获取自由推荐信息
     *
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-11-25
     */
    public function collGoodsPage(){
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
        $g_id = $this->_request('g_id');
		$data = array();
		if($g_id){
			$data = D('FreeCollocation')->getFreeCollocationByGid($g_id);
		}
		$this->assign('gid',$g_id);
        $this->assign($data);
        $tpl = $this->wap_theme_path . "Products/set_coll_goods.html";
        $this->display($tpl);
    }	
	
	
    /**
     * 获取关联商品信息
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-28
     */
    public function getRelateGoodsPage(){
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
    	$int_g_id = $this->_request('gid');
    	if($int_g_id){
    		$ary_relatedgoods = D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
    		$ary_relatedgoods = trim($ary_relatedgoods,",");
    		$where = array();
    		$where['g_id'] = array('in',$ary_relatedgoods);
    		$ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->select();
    		$this->assign('ary_relate_goods',$ary_relate_goods);
    	}
    	$tpl = $this->wap_theme_path . "relate_goods.html";
    	$this->display($tpl);
    }
    
    /**
     * 将商品列表中的商品加入购物车
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function getAddGoodsCart(){
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
		$data['gpic'] = $ary_goods['g_picture'];
		$data['gpic'] = D('QnPic')->picToQn($data['gpic'],200,200);
        $mprice = D("Price")->getMarketPrice($data['gid']);
        //货品中最大价格
        $data['mprice'] = $mprice;
        $data['gstock'] = $ary_goods['g_stock'];
        $data['skus'] = $ary_pdt;
        if($ary_post['coll'] == '1'){
            $tpl =$this->wap_theme_path . 'selectColl.html';
        }else{
            $tpl = $this->wap_theme_path . 'selectCollGoods.html';
        }
        //echo "<pre>";print_r($data);exit;
        $this->assign("filter", $ary_post);
        $this->assign("data",$data);
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
        
      // echo "<pre>";print_r($array_collocation);exit;
        $this->assign('data',$array_collocation);
        $this->assign('pagearr',$pageInfo);
        $this->assign('get',$get);
        $this->assign('is_show',$_SESSION['collocation']['is_show_coll']);
        $this->assign('itemInfo', $this->_request());
        $tpl = $this->wap_theme_path . 'free_recommend.html';
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
        $tpl = $this->wap_theme_path . 'collocation_column.html';
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
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
            $tpl = $this->wap_theme_path . 'collocation_column.html';
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
                header("location:" . U('Wap/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Wap/User/Login'));
            exit;
        }
        $this->assign('data',$_SESSION['collocation']['data']);
        $this->assign($_SESSION['collocation']['price_da']);
        $tpl = $this->wap_theme_path . 'collocation_column.html';
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


    function ajaxGetSku(){
        $ary_post = $this->_post();
        //销售类型（团购，预售，秒杀，正常购物，...）
        $item_type = $ary_post['item_type'];
        $item_id = $ary_post['item_id'];
        $members = session('Members');
        $tpl = C('TMPL_PARSE_STRING.__TPL__'). 'ajaxShowSkus.html';
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
            default:
                $ary_goods_pdts = D('Goods')->getDetails($item_id, $members);
                break;
        }
        $tpl = '.' . $tpl;

        $this->assign($ary_goods_pdts);
        $this->display($tpl);
    }

    /**
     * ajax获取商品sku信息
     */
    function ajaxGetSku1(){
        layout(FALSE);
        $ary_goods = array();
        $ary_goods['g_id'] = $this->_request('gid');
        if (!empty($ary_goods['g_id'])) {
            $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods', 'pdt_min_num');
            $where = array();
            $where['g_id'] = $ary_goods['g_id'];
            $where['pdt_status'] = '1';

            //这里获取商品库存信息，不应该放到缓存里
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->select();

            $int_num = count($ary_pdt);
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                $stock_i = 0;
                $obj_price = new PriceModel($_SESSION['Members']['m_id'],1);
                foreach ($ary_pdt as $keypdt => $valpdt) {
                    //获取其他属性
                    $specInfo = D('GoodsSpec')->getProductsSpecs($valpdt['pdt_id'],true);
                    if (!empty($specInfo['color'])) {
                        if (!empty($specInfo['color'][1])) {
                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                        }
                    }
                    //如果会员为登录状态，优先取一口价->会员等级价-
                    if(isset($_SESSION['Members']['m_id'])){
                        $ary_pdt[$keypdt]['pdt_sale_price'] = $obj_price->getItemPrice($valpdt['pdt_id']);
                        //如果是单规格商品，把商品价格也一并替换掉
                        if($int_num ==1){
                            $ary_goods['g_price'] = $ary_pdt[$keypdt]['pdt_sale_price'];
                        }

                    }

                    if (!empty($specInfo['size'])) {
                        if (!empty($specInfo['size'][1])) {
                            $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                        }
                    }
                    $ary_pdt[$keypdt]['specName'] = $specInfo['spec_name'];
                    $ary_pdt[$keypdt]['skuName'] = $specInfo['sku_name'];
                    $stock_i += $valpdt['pdt_stock'];
                    if($stock_data['level']) {
                        $ary_pdt[$keypdt]['stock_num'] = $stock_data['STOCK_NUM'];
                    }else{
                        $ary_pdt[$keypdt]['stock_num'] = 30;
                    }

                }
            }
            $ary_goods['g_stock'] = $stock_i;

            foreach ($skus as $key => &$sku) {
                $skus[$key] = array_unique($sku);
            }
            if (!empty($skus)) {
                $data['skuNames'] = $skus;
            }else{
                //第一步：验证是否存在规格存在商品
                $res_sc_id = D("Gyfx")->selectOneCache("releted_spec_combination","sc_id",array('rsc_rel_good_id'=>$ary_goods['g_id']));
                $res_scg_status = D("Gyfx")->selectOneCache("spec_combination","scg_status",array('scg_id'=>$res_sc_id['sc_id']));
                $scg_status = $res_scg_status['scg_status'];
                if(isset($sc_id) && $scg_status == 1){
                    //获取相关商品id
                    $sp_com_g_id = D("Gyfx")->selectAllCache("releted_spec_combination","rsc_rel_good_id as gid",array('sc_id'=>$sc_id),null,"rsc_rel_good_id");

                    foreach ($sp_com_g_id as $val){
                        $array_spec_val = D("Gyfx")->selectAllCache("releted_spec_combination","*",array('rsc_rel_good_id'=>$val['gid']));
                        foreach ($array_spec_val as $sv_key){
                            if($val['gid'] == $ary_goods['g_id']){
                                $goods_com_spec[$sv_key['rsc_spec_name']] = $sv_key['rsc_spec_detail'];
                            }
                            $data_val .= $sv_key['rsc_spec_name'].":".$sv_key['rsc_spec_detail'].';';
                            if(!isset($skus[$sv_key['rsc_spec_name']])){
                                $skus[$sv_key['rsc_spec_name']] = array($sv_key['rsc_spec_detail']);
                            }else{
                                $skus[$sv_key['rsc_spec_name']] = array_merge($skus[$sv_key['rsc_spec_name']],array($sv_key['rsc_spec_detail']));
                            }
                            $skus[$sv_key['rsc_spec_name']] = array_unique($skus[$sv_key['rsc_spec_name']]);
                            sort($skus[$sv_key['rsc_spec_name']]);
                        }
                        $data_val = rtrim($data_val,';');
                        $goods_url[$data_val] = '/Home/Products/detail/gid/'.$val['gid'];
                        unset($data_val);
                    }
                    $data['goods_url'] = $goods_url;
                    $data['goods_spec_name'] = $goods_com_spec;
                    $data['specName'] = $skus;
                }
            }
        }
        $data['gstock'] = $ary_goods['g_stock'];
        $data['skus'] = $ary_pdt;
        $data['gid'] = $this->_request('gid');
        $this->assign('detail',$data);
        $tpl = $this->wap_theme_path . 'ajaxShowSkus.html';
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
        //兼容老版本的做法
        $ary_post = $this->_request();
        //销售类型（团购，预售，秒杀，正常购物，...）
        $item_type = $ary_post['item_type'];
        $item_id = $ary_post['item_id'];
        //兼容老版本的做法
        if($depth == 2 && !in_array($item_type, array(5,7,8))) {
            $this->getDetailSkusCompatible();
            die;
        }
        $members = session('Members');
		$tpl = $this->wap_theme_path . 'goodsDetailSku.html';
		$this->wap_theme_path = trim($this->wap_theme_path,'.');
		switch($item_type) {
			case 5:
                $_tpl = $this->wap_theme_path  . 'bulkDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Groupbuy')->getDetails($item_id, $members);
				break;
            case 7:
                $_tpl = $this->wap_theme_path  . 'presaleDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Presale')->getDetails($item_id, $members);
				break;
            case 8:
                $_tpl = $this->wap_theme_path  . 'Spike/spikeDetailSku.html';
                if(file_exists(FXINC.$_tpl)) {
                    $tpl = $_tpl;
                }
				$ary_goods_pdts = D('Spike')->getDetails($item_id, $members);
				break;
			default:
				$ary_goods_pdts = D('Goods')->getDetails($item_id, $members);
				break;
		}
        $tpl = '.' . trim($tpl,'.');
		$this->assign($ary_goods_pdts);
        $this->display($tpl);

    }
	

    /***********商品分类页面**********/
    public function productsCategory(){
        $where =  array(
            'gc_status'=>1,//有效
            'gc_is_display'=>1,//在前台显示
            'gc_level'=>0//一级分类
        );
        $cate_info = D('GoodsCategory')->where($where)->select();
        foreach($cate_info as $k => $cate){
            $tag = array(
                'cid'=>$cate['gc_id'],
                'wap'=>1
            );
            $subCate = D('ViewGoods')->goodList($tag);
            $cate_info[$k]['sub'] = $subCate['list'];
        }
        $this->assign('category',$cate_info);
        $tpl = $this->wap_theme_path . 'productsCategory.html';
        $this->display($tpl);
    }


    public function test(){
        $_SESSION['no_wx'] = 0;
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
}
