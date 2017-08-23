<?php
/**
 * 后台淘宝铺货设置
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.4.5
 * @author wanguibin <wangguibin@guanyisoft.com>
 * @date 2013-10-18
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class DistirbutionAction extends AdminAction{
	
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle(' - '.L('MENU5_4'));
    }
    
    /**
     * 店铺绑定
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function taobaoIndex(){
    	$this->getSubNav(6, 4, 10);
    	$ary_where = array();
    	$ary_where['u_id'] = array('neq','0');
    	$int_count =  M('thd_shops',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
    	$page_no = max(0, (int) $this->_get('p', '', 1));
    	$page_size = 20;
    	$obj_page = new Page($int_count, $page_size);
    	$page = $obj_page->show();
    	$ary_shops =  M('thd_shops',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->limit(($page_no-1)*$page_size,$page_size)->order('`ts_created` DESC')->select();
    	$this->assign("page", $page);
    	$this->assign('ary_shops',$ary_shops);
    	$this->display(); 
    }
    
    /**
     * 下载淘宝商品
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function taobaoSetSynRules(){
    	$this->getSubNav(6, 4, 20);
    	$ary_data = $this->_get();
    	//获取店铺列表
    	$ary_shops = D('ThdShops')->getThdShop(array('u_id'=>array('neq','0')),'ts_id,ts_title,ts_sid',array('ts_created'=>'desc'));
    	
        $this->assign('ary_shops',$ary_shops);
    	//搜索条件
    	$ary_where = array();
    	if(!empty($ary_data)){
    		$page_size = 10;
    		$page_no = max(0, (int) $this->_get('p', '', 1));
            // 分页条数
            if(isset($ary_data['page_num']) && !empty($ary_data['page_num']) && max(10,$ary_data['page_num'])>10){
                $page_size = intval($ary_data['page_num']);
            }
    		//查淘宝商品
    		if($ary_data['searchType'] == '1'){
    			//获取淘宝商品
    			//分页数组
    			$array_pageinfo = array();
    			$array_pageinfo['page_no'] = $page_no;
    			$array_pageinfo['page_size'] = $page_size;
    			//查询条件
    			$seach_condition = array();
    			$seach_condition['shop_source'] = trim($ary_data['shop_source']);
    			//商品名称
    			if(isset($ary_data['items_name']) && !empty($ary_data['items_name'])){
    				$seach_condition['items_name'] = $ary_data['items_name'];
    			}
    			//商品起始修改时间
    			if(isset($ary_data['update_starttime']) && ''!=trim($ary_data['update_starttime'])){
    				$seach_condition['start_modified'] = trim($ary_data['update_starttime']).' 00';
    			}
    			//商品结束修改时间
    			if(isset($ary_data['update_endtime']) && ''!=trim($ary_data['update_endtime'])){
    				$seach_condition['end_modified'] = trim($ary_data['update_endtime']).' 00';
    			}    			
    			//根据商品的上下架状态调用不同的接口抓取top商品
    			if(isset($ary_data['item_status']) && !empty($ary_data['item_status'])){
    				$seach_condition['sale_status'] = trim($ary_data['item_status']);
    			}
    			$ary_taobao_items = $this->searchTopItemList($seach_condition,$array_pageinfo);
    			if($ary_taobao_items['status'] === true){
    				$int_count = $ary_taobao_items['total_results'];
    				$ary_items = $ary_taobao_items['data'];
    				//处理商品信息
    				$ary_shop_items = array();
    				foreach($ary_items as $key=>$ary_item){
    					$ary_shop_items[$key] = array(
    						'thd_goods_id'=>$ary_item['num_iid'],
    						'thd_goods_sn'=>$ary_item['outer_id'],
    						'thd_goods_name'=>$ary_item['title'],
    						'thd_goods_picture'=>D('QnPic')->picToQn($ary_item['pic_url']),
    						'thd_goods_update_time'=>$ary_item['modified'],
    						'ts_id'=>$ary_data['shop_source'],
    						'approve_status_name'=>($ary_item['approve_status'] == 'onsale')?'上架':'下架'
    					);
    					//未有商家编码
    					if(empty($ary_item['outer_id'])){
    						$ary_shop_items[$key]['g_sn'] = '';
    						$ary_shop_items[$key]['no_down'] = 1;
    					}else{
    						//是否下载和匹配
    						$ary_where = array(C('DB_PREFIX').'thd_goods.thd_goods_sn'=>$ary_item['outer_id']);
    						$str_field = C('DB_PREFIX').'thd_goods.*,'.C('DB_PREFIX').'goods.g_sn';
    						$ary_taobao_item = D('ThdGoods')->getThdGoods($ary_where,$str_field);
    						if(!empty($ary_taobao_item[0])){
    							$ary_shop_items[$key]['g_sn'] = $ary_taobao_item[0]['g_sn'];
    							$ary_shop_items[$key]['thd_goods_create_time'] = $ary_taobao_item[0]['thd_goods_create_time'];
    						}else{
    							$ary_shop_items[$key]['g_sn'] = '';
    							$ary_shop_items[$key]['no_down'] = 1;
    						}
    					}
    				}
    			}
    		}else{
				//查分销商品
    			$ary_where['thd_source'] = 1;
    			//店铺代码
    			if(isset($ary_data['shop_source']) && !empty($ary_data['shop_source'])){
    				$ary_where[C('DB_PREFIX').'thd_goods.ts_id'] = $ary_data['shop_source'];
    			}
    			//商品状态
    			if(isset($ary_data['item_status']) && !empty($ary_data['item_status'])){
    				$ary_where['thd_goods_status'] = $ary_data['item_status'];
    			}
    			//商品名称
    			if(isset($ary_data['items_name']) && !empty($ary_data['items_name'])){
    				$ary_where['thd_goods_name'] = array('LIKE', '%' . $ary_data['items_name'] . '%');
    			}
    			//更新开始update_starttime
    			if($ary_data['update_starttime'] && !empty($ary_data['update_starttime'])){
    				$ary_where['thd_goods_create_time']  = array('EGT',$ary_data['update_starttime']);
    			}
    			//更新结束update_endtime
    			if($ary_data['update_endtime'] && !empty($ary_data['update_endtime'])){
    				$ary_where['thd_goods_update_time']  = array('ELT',$ary_data['update_endtime']);
    			}   
    			//获取已下载的商品信息
    			$int_count =  D('ThdGoods')->getThdGoodsCount($ary_where);
    			$str_field = C('DB_PREFIX').'thd_goods.*,'.C('DB_PREFIX').'goods.g_sn';
    			$ary_limit = array('page_no'=>$page_no,'page_size'=>$page_size);
    			$ary_shop_items = D('ThdGoods')->getThdGoods($ary_where,$str_field,null,$ary_limit);
    			foreach($ary_shop_items as &$shop_item){
    				$shop_item['approve_status_name']=($shop_item['thd_goods_status'] == '1')?'上架':'下架';
					$shop_item['thd_goods_picture']= D('QnPic')->picToQn($shop_item['thd_goods_picture']);
    			}
    		}
    	}
    	//把商品总数放数组里
    	$ary_data['item_count'] = $int_count;
    	$obj_page = new Page($int_count, $page_size);
    	$page = $obj_page->show();
    	$this->assign("page", $page);
    	$this->assign("ary_data", $ary_data);
    	$this->assign('ary_shop_items',$ary_shop_items);
    	$this->display();
    }
    
    /**
     * 根据条件搜索淘宝商品
     * @params $seach_condition = array(
     *		'start_modified'=>'Y-m-d H:i:s',
     *		'end_modified'=>'Y-m-d H:i:s',
     *		'sale_status'=>1|2,//其中1为在架，2为下架。其他值不接受，默认取在架
     *	)
     *	@params $array_pageinfo = array(
     *		'page_no'=>1,//当前第几页
     *		'page_size'=>40,//每页多少条记录，淘宝建议每次取40条！如果是第一次获取记录数，这里传入1效率会高一点。
     *	)
     *
     *	@return 返回一个数组，array('status'=>false,'total_results'=>0,'data'=>array(),'message'=>'');
     *	其中status是调用API的状态（成功还是失败），'total_results'是符合条件的记录数
     *	data是商品数据，message，是调试信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function searchTopItemList($seach_condition = array(),$array_pageinfo=array()){
    	$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$seach_condition['shop_source']),'ts_shop_token');
    	//access_token
    	$seach_condition['top_access_token'] = $access_token;
    	$ary_topapi_post = array();
    	//分页信息
    	$ary_topapi_post['page_no'] = $array_pageinfo['page_no'];
    	$ary_topapi_post['page_size'] = $array_pageinfo['page_size'];
    	
    	//搜索条件--按照开始和结束时间搜素
    	//增加根据淘宝标题关键词进行检索
    	if(isset($seach_condition['items_name']) && ''!=trim($seach_condition['items_name'])){
    		$ary_topapi_post['q'] = trim($seach_condition['items_name']);
    	}
    	//商品起始修改时间
    	if(isset($seach_condition['start_modified']) && ''!=trim($seach_condition['start_modified'])){
    		$ary_topapi_post['start_modified'] = trim($seach_condition['start_modified']);
    	}
    	//商品结束修改时间
    	if(isset($seach_condition['end_modified']) && ''!=trim($seach_condition['end_modified'])){
    		$ary_topapi_post['end_modified'] = trim($seach_condition['end_modified']);
    	}
    	//根据商品的上下架状态调用不同的接口抓取top商品
    	$int_sale_status = 1;
    	if(!empty($seach_condition['sale_status'])){
    		$int_sale_status = trim($seach_condition['sale_status']);
    	}
    	$str_method = '';
    	switch($int_sale_status){
    		case 1:
    			$str_method = 'getCurrentUserOnSaleGoods';
    			break;
    		case 2:
    			$str_method = 'getCurrentUserStorageGoods';
    			break;
    	}
    	//去淘宝搜索商品
    	$taobao_obj = new TaobaoApi($seach_condition['top_access_token']);
    	$array_result = $taobao_obj->$str_method($ary_topapi_post);
    	$array_return = array('status'=>false,'total_results'=>0,'data'=>array(),'message'=>'','code'=>'');
    	if($array_result['status']==true){
    		$array_return['status']	= true;
    		$array_return['total_results']	= $array_result['num'];
    		$array_return['data']		= $array_result['data'];
    		$array_return['message']	= $array_result['err_msg'];
    		$array_return['code']		= $array_result['err_code'];
    	} else {
    		$array_return['message']	= $array_result['err_msg'];
    		$array_return['code']		= $array_result['err_code'];
    	}
    	return $array_return;
    }
   
    /**
     * 淘宝供销平台对接，授权绑定请求发起页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function topOauth(){
    	//生成callback url
    	$callback_url = "http://";
    	$callback_url .= $_SERVER["HTTP_HOST"];
    	if(80 != $_SERVER["SERVER_PORT"]){
    		$callback_url .= ':' . $_SERVER["SERVER_PORT"];
    	}
    	$callback_url .= '/' . trim(U("Admin/Distirbution/callback"),'/');
		$taobao_obj = new TaobaoApi();
		$taobao_obj->topOauth($callback_url);
    }
    
    /**
     * 淘宝供销平台对接，授权完成回跳页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function callback(){
    	//判断是前台还是后台
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    	$is_exist = strpos($url,'dmin/Distirbution/callback');
		if($is_exist == false){
			$is_exist = strpos($_SERVER['PHP_SELF'],'dmin/Distirbution/callback');
		}
    	$redirt_url = U("Admin/Distirbution/taobaoIndex");
    	$array_data = $_GET;
    	$taobao_obj = new TaobaoApi();
    	$taobao_obj->callback($array_data,$is_exist,$redirt_url);
    }
    
  	/**
     * 绑定店铺删除
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-21 13:08:08
     */
    public function doDelShops(){
        $ary_data = $this->_get();
        $shop_obj = D("ThdShops");
        if(!isset($ary_data['ts_ids']) || empty($ary_data['ts_ids'])){
			$this->error("请选择您要删除的店铺。");
		}
		$ary_where = array();
		$ary_where['ts_id'] = array('in',$ary_data['ts_ids']);
		//验证商品品牌是否被商品资料占用
		$array_result = D("ThdGoods")->where($ary_where)->find();
		if(is_array($array_result) && !empty($array_result)){
			$this->error("已有第三方商品被下载，不允许删除店铺信息。");
		}
		$ary_result = $shop_obj->where($ary_where)->delete();
		if($ary_result){
			$this->success("删除成功",U("Admin/Distirbution/taobaoIndex"));
			exit;
		}
		//商品品牌删除失败。
		$this->error("删除失败，请重试！");
    }
    
    /**
     * 删除已下载的淘宝商品
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-23 13:08:08
     */
    public function doDelShopGoods(){
    	$ary_data = $this->_get();
    	if(!isset($ary_data['item_ids']) || empty($ary_data['item_ids'])){
    		$this->error("请选择您要删除的商品。");
    	}
    	$ary_where = array();
    	$ary_where['thd_goods_id'] = array('in',$ary_data['item_ids']);
    	$ary_result = D('GyFx')->deleteInfo('thd_goods',$ary_where);
    	if($ary_result){
    		$this->success("删除成功",U("Admin/Distirbution/taobaoSetSynRules"));
    		exit;
    	}
    	//删除失败。
    	$this->error("删除失败，请重试！");
    }   
    /**
     * 
     * 同步全部商品
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-24
     */
    public function downAllShopGoods() {
    	@set_time_limit(0);
    	@ignore_user_abort(TRUE); // 设置与客户机断开是否会终止脚本的执行
    	$ary_post = $this->_post();
    	if (empty($ary_post)) {
    		//分页数组
    		$array_pageinfo = array();
    		$array_pageinfo['page_no'] = 1;
    		$array_pageinfo['page_size'] = 1;
    		$ary_get = $this->_get();
    		$ary_get['sale_status'] = $ary_get['item_status'];
    		$taobao_items = $this->searchTopItemList($ary_get,$array_pageinfo);
    		$total = 0;
    		if(!empty($taobao_items['total_results'])){
    			$total = $taobao_items['total_results'];
    		}else{
    			//如果通过接口获得不了商品总数按GET过来的数据为准
    			$total = intval($ary_get['item_count']);
    		}
    		//获取分类总数
    		echo $total;exit;
    	} else {
    		//分页数组
    		$array_pageinfo = array();
    		$array_pageinfo['page_no'] = $ary_post['page_no'];
    		$array_pageinfo['page_size'] = $ary_post['page_size'];
    		$ary_post['sale_status'] = $ary_post['item_status'];
    		$taobao_items = $this->searchTopItemList($ary_post,$array_pageinfo);
            //print_r($taobao_items);exit;
    		$ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0,'updRows' => 0,'errData' => '');
    		if (!empty($taobao_items['data'])) {
    			//总共商品数
    			$total_num = count($taobao_items['data']);
    			//处理成功数据
    			$success_num = 0;
    			//处理失败条数
    			$fail_num = 0;
    			foreach ($taobao_items['data'] as $k => $taobao_item) {
    				//根据淘宝ID获取详情getTopItemDetailInfo
    				$ary_res = $this->getTopItemDetailInfo($taobao_item['num_iid'],$ary_post['shop_source'],$ary_post['is_down']);
    				if(!$ary_res['status']) {
    					$fail_num = $fail_num + 1;
    					$ary_res['errMsg'] .= $taobao_item['outer_id'].':'.$ary_res['err_msg'];
    					$this->writeLog(json_encode($ary_res), 'down_thd_goods_err_log');
    				}else{
    					$success_num = $success_num + 1;
    				}
    			}
    			if($success_num>0){
    				if($total_num != $success_num){
						$ary_res['succRows'] = $ary_res['succRows']+$success_num;
    				}else{
						$ary_res['errRows'] = $ary_res['errRows']+$fail_num;
						$ary_res['succRows'] = $ary_res['succRows']+$success_num;
						
    				}
    			}else{
    				$ary_res['errRows'] = $ary_res['errRows']+$fail_num;
    			}
    		}
    		echo json_encode($ary_res);
    		exit;
    	}
    }
    
    /**
     * 批量下载淘宝商品
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-24 
     */
    public function downShopGoods(){
    	//一直执行
    	@set_time_limit(0);
    	@ignore_user_abort(TRUE);
    	$ary_data = $this->_get();
		//商品id
    	$item_ids = explode(',',$ary_data['item_ids']);
    	//是否同步商品信息到正式表
    	$is_down = $ary_data['is_down'];
    	$good_obj = D("ThdGoods");
    	if(!isset($item_ids) || empty($item_ids)){
    		$this->error("请选择您要下载的商品。");
    		exit;
    	}
    	$is_success = 1;
    	$total_num = count($item_ids);
    	$success_num = 0;
    	foreach ($item_ids as $k => $num_iid) {
    		//根据淘宝ID获取详情getTopItemDetailInfo
    		$ary_res = $this->getTopItemDetailInfo($num_iid,$ary_data['shop_source'],$is_down);
    		if(!$ary_res['status']) {
    			$this->writeLog(json_encode($ary_res), 'down_thd_goods_err_log');
    		}else{
    			$success_num = $success_num + 1;
    		}
    	}
    	if($success_num>0){
    		if($total_num != $success_num){
    			$this->success(array('status'=>'1','info'=>'下载成功,自动过滤掉无商品编码商品，共下载'.$success_num.'条'));
    			exit;
    		}else{
    			$this->success(array('status'=>'1','info'=>'下载成功,共下载'.$success_num.'条'));
    			exit;
    		}
    	}
    	//删除失败。
    	$this->error("下载失败，请重试！".$ary_res['err_msg']);
    } 
    
    /**
     * 根据淘宝商品ID获取商品详细信息
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-24 13:08:08
     */
    public function getTopItemDetailInfo($int_top_itemid,$shop_source,$is_down){
    	$ary_result	= array('status'=>true,'err_msg'=>'','err_code'=>'');
    	try{
    		$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$shop_source),'ts_shop_token');
    		if(empty($access_token)){
    			throw new Exception('未获得店铺授权信息。', 80001);
    		}
    		$taobao_obj = new TaobaoApi($access_token);
    		$ary_top_info = $taobao_obj->getSingleGoodsInfo($int_top_itemid);
            //print_r($ary_top_info);exit;
    		if(!$ary_top_info['status']) {
    			throw new Exception($ary_top_info['err_msg'], $ary_top_info['err_code']);
    		}
    		if('' == trim($ary_top_info['data']['outer_id'])){
    			throw new Exception('该商品（num_iid:'.$int_top_itemid.'）缺少商家编码，系统自动过滤！', 80001);
    		}
    		//处理商品详情中的图片
    		$ary_top_info['data']['desc'] = $this->deal_withTopItemDesc($ary_top_info['data']['desc']);
    		$array_thd_data = array();
    		$array_thd_data['thd_source'] = 1;
    		$array_thd_data['thd_goods_id'] = $ary_top_info['data']['num_iid'];
    		$array_thd_data['thd_goods_sn'] = $ary_top_info['data']['outer_id'];
    		$array_thd_data['thd_goods_name'] = $ary_top_info['data']['title'];
    		$array_thd_data['thd_goods_data'] = json_encode($ary_top_info['data']);
    		$array_thd_data['thd_goods_picture'] = ltrim($this->downloadTopImageToLocal($ary_top_info['data']['pic_url'],0,'./Public/Uploads/' . CI_SN.'/goods/top'),'.');
    		$array_thd_data['thd_goods_create_time'] = date('Y-m-d H:i:s');
    		$array_thd_data['thd_goods_update_time'] = date('Y-m-d H:i:s');
    		$array_thd_data['ts_id'] = $shop_source;
    		$array_thd_data['thd_goods_status'] = ($ary_top_info['data']['approve_status'] == 'onsale')?1:2;
    		//判断当前商品在top商品表中是否存在
    		$array_check_cond = array('thd_source'=>1,'ts_id'=>$shop_source,'thd_goods_id'=>$array_thd_data['thd_goods_id']);
    		$array_result = D('ThdGoods')->getThdGoods($array_check_cond,$ary_field='thd_id');
    		try{
    			M('',C(''),'DB_CUSTOM')->startTrans();
    			//验证商品是否已经同步到本地
    			$ary_check_exist = array('g_sn' => $ary_top_info['data']['outer_id']);
    			$mixed_check_result = D('Goods')->where($ary_check_exist)->find();
    			if(is_array($array_result) && !empty($array_result)>0){
    				//本地找到thd_goods表中的记录
    				//更新thd_goods表中的记录
    				unset($array_thd_data['thd_goods_create_time']);
    				if(!D('ThdGoods')->updateThdGoods($array_check_cond,$array_thd_data)){
    					$this->writeLog(json_encode($array_thd_data), 'down_thd_goods.log');
    					throw new PDOException('更新地方放平台商品(num_iid:'.$int_top_itemid.')数据时遇到错误！', 8002);
    				}
    				//继续更新分销商品信息到本地
    				if($is_down == '1'){
    					//如果商品已同步到分销暂时不更新数据
    					if (is_array($mixed_check_result) && count($mixed_check_result) > 0) {
    						$taobao_fresh = D('SysConfig')->getCfg('TAOBAO_SET','TAOBAO_SET_Fresh','1','淘宝商品转换为系统商品是否启用更新');
    						if($taobao_fresh['TAOBAO_SET_FRESH']['sc_value'] == '1'){
    							//更新数据,后面如果启用更新使用下面的
    							//只同步图片和宝贝描述
    							$ary_add_goods['g_desc'] = $this->deal_withTopItemDesc($ary_top_info['data']['desc']);
    							$ary_add_goods['g_picture'] = trim($this->downloadTopImageToLocal($ary_top_info['data']['pic_url'],0,'./Public/Uploads/' . CI_SN.'/goods/top'));
    							//处理商品图片
    							$ary_picture_reault = $this->synItemImagesToLocal($ary_top_info['data']['item_imgs']['item_img'], $mixed_check_result['g_id'], $ary_top_info['data']['pic_url']);
    							if ($ary_picture_reault['status'] == false) {
    								return array('status' => false, 'err_msg'=>'更新商品图片失败！', 'err_code'=>8105);
    							}
    							$ary_add_goods['g_update_time'] = date('Y-m-d H:i:s');
    							$ary_deit_condit = array('g_id' => $mixed_check_result['g_id']);
    							//商品信息已经在本地存在，则更新之
    							$mixed_edit_result = D('GoodsInfo')->where($ary_deit_condit)->data($ary_add_goods)->save();
    							if(!$mixed_edit_result) {
    								return array('status' => false, 'err_code' =>8102, 'err_msg'=>'');
    							}
    						}
    						$ary_res['status'] = true;
    					}else{
    						$ary_res = $this->convertTopItemsToLocal($ary_top_info['data'],array(),$shop_source,$taobao_obj);
    						if(!$ary_res['status']) {
    							throw new PDOException($ary_res['err_msg'], $ary_res['err_code']);
    						}
    					}
    				}
    			}else{
    				if(!D('ThdGoods')->addThdGoods($array_thd_data)){
    					throw new PDOException('本地生成第三方平台商品时遇到错误！', 8003);
    				}
    				if($is_down == '1'){
    					//如果商品已同步到分销暂时不更新数据
    					if (is_array($mixed_check_result) && count($mixed_check_result) > 0) {
    						$taobao_fresh = D('SysConfig')->getCfg('TAOBAO_SET','TAOBAO_SET_Fresh','1','淘宝商品转换为系统商品是否启用更新');
    						if($taobao_fresh['TAOBAO_SET_Fresh']['sc_value'] == '1'){
    							//更新数据,后面如果启用更新使用下面的
    							//只同步图片和宝贝描述
    							$ary_add_goods['g_desc'] = $this->deal_withTopItemDesc($ary_top_items['desc']);
    							$ary_add_goods['g_picture'] = trim($this->downloadTopImageToLocal($ary_top_items['pic_url'],0,'./Public/Uploads/' . CI_SN.'/goods/top'));
      							//处理商品图片
    							$ary_picture_reault = $this->synItemImagesToLocal($ary_top_info['data']['item_imgs']['item_img'], $mixed_check_result['g_id'], $ary_top_items['pic_url']);
    							if ($ary_picture_reault['status'] == false) {
    								return array('status' => false, 'err_msg'=>'更新商品图片失败！', 'err_code'=>8105);
    							}
    							$ary_add_goods['g_update_time'] = date('Y-m-d H:i:s');
    							$ary_deit_condit = array('g_id' => $mixed_check_result['g_id']);
    							//商品信息已经在本地存在，则更新之
    							$mixed_edit_result = D('GoodsInfo')->where($ary_deit_condit)->data($ary_add_goods)->save();
    							if(!$mixed_edit_result) {
    								return array('status' => false, 'err_code' =>8102, 'err_msg'=>'');
    							}
    						}
    						$ary_res['status'] = true;
    					}else{
    						//转换本地商品
    						$ary_res = $this->convertTopItemsToLocal($ary_top_info['data'],array(),$shop_source,$taobao_obj);
    						if(!$ary_res['status']) {
    							throw new PDOException($ary_res['err_msg'], $ary_res['err_code']);
    						}	
    					}
    				}
    			}
    		} catch (PDOException $p) {
    			M('',C(''),'DB_CUSTOM')->rollback();
    			$this->writeLog($p->getMessage(), 'down_thd_goods.log');
    			throw new Exception($p->getMessage(), $p->getCode());
    		}
    		M('',C(''),'DB_CUSTOM')->commit();
    	} catch (Exception $e) {
    		$ary_result['err_msg']	= $e->getMessage();
    		$ary_result['err_code']	= $e->getCode();
    		$ary_result['status']	= false;
    	}
    	return $ary_result;
    }
    
    /**
     * 把淘宝商品数据转换成本地商品数据
     * $ary_top_items  淘宝商品详细信息
     * $convert_rules  淘宝商品转换本能规则
     * $str_thd_shopsid  第三方店铺Sid
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     * @param obj $top_obj 淘宝API的实例化对象
     */
    public function convertTopItemsToLocal($ary_top_items, $convert_rules, $str_thd_shopsid, $top_obj = NULL) {
    	$set_info = D('SysConfig')->getCfgByModule('TAOBAO_SET');
    	$convert_rules = array(
    			'item_cats' => array('value' => 0, 'comment' => '分销店铺分类'),
    			'used_top_cat' => array('value' => intval($set_info['TAOBAO_SET_CATEGORY']), 'comment' => '如果没有找到分销店铺分类，是否使用淘宝分类'),
    			'item_brand' => array('value' => 0, 'comment' => '分销店铺品牌'),
    			'used_top_brand' => array('value' => intval($set_info['TAOBAO_SET_BRAND']), 'comment' => '如果没有找到分销店铺品牌，是否使用淘宝品牌'),
    			'set_new' => array('value' => intval($set_info['TAOBAO_SET_NEW']), 'comment' => '设置新品，布尔值'),
    			'set_hot' => array('value' => intval($set_info['TAOBAO_SET_HOT']), 'comment' => '设置品牌热销，布尔值'),
    			'on_sales' => array('value' => intval($set_info['TAOBAO_SET_SALE']), 'comment' => '是否上架销售'),
				'on_presales' => array('value' => intval($set_info['TAOBAO_SET_PRESALE']), 'comment' => '是否预售'),
				'is_down_unsale' => array('value' => intval($set_info['TAOBAO_SET_DOWN_UNSALE']), 'comment' => '是否下载非销售属性'),
    	);
    	$bool_is_update_items = false;
    	$str_logfile = 'check.log';
    	//验证商品是否已经同步到本地
    	$ary_check_exist = array('g_sn' => $ary_top_items['outer_id']);
    	$mixed_check_result = D('Goods')->where($ary_check_exist)->find();
    	if (is_array($mixed_check_result) && count($mixed_check_result) > 0) {
    		//如果商品在本地有相同商检编码的商品，暂时则不执行任何操作！！！！
    		return array('status' => true, 'err_code' => 8101, 'err_msg' => '商品已被同步过');
    	} else {
    		$bool_is_update_items = false;
			// 商品基本信息处理
			$ary_add_goods = $this->itemSaveFields ( $ary_top_items, $convert_rules );
			$ary_add_goods ['g_desc'] = $this->deal_withTopItemDesc ( $ary_top_items ['desc'] );
            $ary_add_goods ['g_picture'] = trim ( $this->downloadTopImageToLocal ( $ary_top_items ['pic_url'],0,'./Public/Uploads/' . CI_SN.'/goods/top' ) );
			$ary_add_goods ['gt_id'] = $this->getDeafultTopType ();
			//新增商品信息（商品主表和商品明细表）
			$ary_goods = $ary_add_goods;
			$ary_goods_info = $ary_add_goods;
			$mixd_goods_id = D('GyFx')->insert('goods',$ary_goods);
			if (! $mixd_goods_id) {
				// 新增top商品到本地时失败了。。。。
				return array (
						'status' => false,
						'err_code' => 8103,
						'err_msg' => '第三方商品转化为本地商品时(主表)遇到错误！' 
				);
			}else{
				//新增商品明细表
				$ary_goods_info['g_id'] = $mixd_goods_id;
				$result_id = D('GyFx')->insert('goods_info',$ary_goods_info);
				if(!$result_id){
					return array (
							'status' => false,
							'err_code' => 8103,
							'err_msg' => '第三方商品转化为本地商品时(明细)遇到错误！'
					);					
				}
			}
    	}
    	//分类处理，关联本地分类，如果使用淘宝分类，还要将淘宝分类保存到本地；
    	if (false == $bool_is_update_items) {
    		//如果设置了将商品关联到指定分类下，则增加一个关联
    		$seller_cids = trim($ary_top_items['seller_cids'], ',');
    		$ary_cats_result = $this->convertTopItemCats($mixd_goods_id, $convert_rules, $seller_cids, $str_thd_shopsid);
    		if ($ary_cats_result['status'] == false) {
    			return array('status' => false, 'err_msg'=>'更新第三方平台分类关联关系失败！', 'err_code'=>8104);
    		}
    	}
    	//处理商品图片
    	$ary_picture_reault = $this->synItemImagesToLocal($ary_top_items['item_imgs']['item_img'], $mixd_goods_id, $ary_top_items['pic_url']);
    	if ($ary_picture_reault['status'] == false) {
    		return array('status' => false, 'err_msg'=>'更新商品图片失败！', 'err_code'=>8105);
    	}
    	// 默认全更新的未分类品牌
    	$mixed_result = $this->convertTopBrandTolocal ( null, $mixd_goods_id, $ary_top_items['cid'], $convert_rules, $str_thd_shopsid, $ary_top_items['props_name'] );
    	if ($mixed_result ['status'] != true) {
    		return array (
    				'status' => false,
    				'error_code' => '0x100000000000001',
    				'err_msg'=>'更新到未分类品牌失败！'
    		);
    	}
    	//商品非销售属性（扩展属性）处理，将淘宝商品属性增加到本地 并且增加关联
    	if (false == $bool_is_update_items) {
			//如果设置下载淘宝商品
			if(!empty($convert_rules['is_down_unsale']['value'])){
				$prov_info = $this->convertTopItemProv($ary_top_items['props'], $convert_rules, $mixd_goods_id, $ary_top_items['cid'],$ary_top_items['property_alias'],$str_thd_shopsid,$ary_top_items['props_name']);
				if (false == $prov_info['status']) {
					return array('status' => false, 'err_code' => 8106, 'err_msg' => '转化第三方平台商品属性失败！');
				}			
			}
    		//首先删除商品表里的属性
    		D('Gyfx')->deleteInfo('related_goods_spec',array('g_id'=>$mixd_goods_id));
    		//处理非销售属性
    		//更新商品属性关联表 related_goods_spec
    		$ary_unsale_data = $prov_info['usale_data'];
    		if(!empty($ary_unsale_data)){
    			$ary_unsale_data = explode(';',$ary_unsale_data);
    			foreach ($ary_unsale_data as $kkk => $vvv) {
    				$ary_tmp_2 = explode('|', $vvv);
    				if(count($ary_tmp_2) == '2'){
    					$ary_tmp_20 = explode(':',$ary_tmp_2[0]);
    					$ary_tmp_21 = explode(':',$ary_tmp_2[1]);
    					//淘宝属性1:分销属性1|淘宝属性值:分销属性值1;
    					if(!empty($ary_tmp_21[1])){
    						$memo2 = D('Gyfx')->selectOne('goods_spec_detail','gsd_value',array('gsd_id'=>$ary_tmp_21[1]));
    						$ary_rgs_data = array(
    								'gs_id'=>$ary_tmp_20[1],
    								'gsd_id'=>$ary_tmp_21[1],
    								'gs_is_sale_spec'=> '0',
    								'g_id'=>$mixd_goods_id,
    								'gsd_aliases'=>$memo2['gsd_value']
    						);
    						$obj_result = D('Gyfx')->insert('related_goods_spec',$ary_rgs_data,1);
    						if(!$obj_result){
    							return array('status' => false, 'err_code' => 8114, 'err_msg' =>'生成本地货品非销售属性关联表失败！');
    						}
    					}else{
    					    $memo2 = D('Gyfx')->selectOne('goods_spec_detail','gsd_value,gs_id,gsd_id',array('gs_id'=>$ary_tmp_20[1]));
                            if(isset($memo2) && !empty($memo2)){
                                $ary_rgs_data = array(
                                        'gs_id'=>$memo2['gs_id'],
                                        'gsd_id'=>$memo2['gsd_id'],
                                        'gs_is_sale_spec'=> '0',
                                        'g_id'=>$mixd_goods_id,
                                        'gsd_aliases'=>$memo2['gsd_value']
                                );
                                $obj_result = D('Gyfx')->insert('related_goods_spec',$ary_rgs_data,1);
                                if(!$obj_result){
                                    return array('status' => false, 'err_code' => 8114, 'err_msg' =>'生成本地货品非销售属性关联表失败！');
                                }
                            }
    					}
    				}
    			}	
    		}
    		//处理sku信息---转换成本地products
    		$ary_sku = $ary_top_items['skus']['sku'];
    		if (!is_array($ary_sku) || empty($ary_sku)) {
    			//没有SKU，则单商品的情况
    			$ary_sku_info = array();
    			$ary_sku_info['g_id'] = $mixd_goods_id;
    			$ary_sku_info['g_sn'] = $ary_add_goods['g_sn'];
    			$ary_sku_info['pdt_sn'] = $ary_add_goods['g_sn'];
    			$ary_sku_info['pdt_spec'] = '';
    			$ary_sku_info['pdt_sale_price'] = $ary_top_items['price'];
    			$ary_sku_info['pdt_market_price'] = $ary_top_items['price'];
    			$ary_sku_info['pdt_weight'] = isset($ary_top_items['item_weight'])?($ary_top_items['item_weight']*1000):0;
    			$ary_sku_info['pdt_total_stock'] = $ary_top_items['num'];
    			$ary_sku_info['pdt_stock'] = $ary_top_items['num'];
    			$ary_sku_info['pdt_create_time'] = date('Y-m-d H:i:s');
    			$ary_sku_info['pdt_update_time'] = date('Y-m-d H:i:s');
    			$ary_sku_info['thd_indentify'] = 1;
    			$ary_sku_info['thd_pdtid'] = $ary_top_items['num_iid'];
    			//验证该SKU在本地系统中是否已经存在，如过已经存在，则更新之
    			$ary_check_exist_con = array('thd_indentify' => 1, 'thd_pdtid' => $ary_top_items['num_iid']);
    			$mixed_exists = D('Gyfx')->selectOne('goods_products',null,$ary_check_exist_con);
    			if (is_array($mixed_exists) && count($mixed_exists) > 0) {
    				//更新货品
    				$ary_condition = array('pdt_id' => $mixed_exists['pdt_id']);
    				$mix_return = D('Gyfx')->update('goods_products',$ary_condition,$ary_sku_info);
    				if(!$mix_return) {
    					//处理单货品异常
    					return array('status' => false, 'err_code' => 8108, 'err_msg'=>'更新本地商品规格失败！');
    				}
    			} else {
    				//新增货品
    				$mix_return = D('Gyfx')->insert('goods_products',$ary_sku_info);
    				if(!$mix_return) {
    					//处理单货品异常
    					return array('status' => false, 'err_code' => 8109, 'err_msg'=>'生成本地商品规格失败！');
    				}
    			}
    		} else {
    			//$goods_spec = '';
    			foreach ($ary_sku as $key => $val) {
    				//处理货品SKU信息
    				$pdt_spec = '';
    				$pdt_memo = '';
    				if (isset($val['properties']) && trim($val['properties']) != '' ) {
    					//转换淘宝销售属性到本地
    					$mixed_sale_prov = $this->convertTopItemProv(trim($val['properties']), array(), 0, $ary_top_items['cid'],$ary_top_items['property_alias'],$str_thd_shopsid,$ary_top_items['props_name'],$val['properties_name']);
    					if ($mixed_sale_prov['status'] == false) {
    						return array('status' => false, 'err_code' => 8110, 'err_msg' => '销售属性转换失败!');
    					}
    					//解析商品属性和属性值，分别组成top_pid=>local_vid/top_vid=>local_vid
    					$ary_sale_pid = array();
    					$ary_sale_vid = array();
    					if ($mixed_sale_prov['data'] != '') {
    						$ary_tmp_1 = explode(';', $mixed_sale_prov['data']);
    						foreach ($ary_tmp_1 as $v) {
    							$ary_tmp_2 = explode('|', $v);
    							$ary_tmp_3 = explode(':', $ary_tmp_2[0]);
    							$ary_tmp_4 = explode(':', $ary_tmp_2[1]);
    							$ary_sale_pid[$ary_tmp_3[0]] = $ary_tmp_3[1];
    							$ary_sale_vid[$ary_tmp_4[0]] = $ary_tmp_4[1];
    						}
    					}
    					$ary_tmp_1 = explode(';', trim($val['properties']));
    					foreach ($ary_tmp_1 as $kkk => $vvv) {
    						$ary_tmp_2 = explode(':', $vvv);
    						$pdt_spec .= $ary_sale_pid[$ary_tmp_2[0]] . ':' . $ary_sale_vid[$ary_tmp_2[1]] . ';';
    					}
    				}
    				//直接新增到本地数据库
    				$ary_sku_info = array();
    				$ary_sku_info['g_id'] = $mixd_goods_id;
    				$ary_sku_info['g_sn'] = $ary_add_goods['g_sn'];
    				//$ary_sku_info['pdt_sn'] = $mixd_goods_id . str_replace(':', '', str_replace(';', '', $pdt_spec));
    				//此处将使用淘宝的sku['outer_id'] 存成本地products表中的pdt_sn
    				if (empty($val['outer_id']) || $val['outer_id'] == '') {
    					return array('status' => false, 'err_code' => 8111, 'err_msg' => '商家规格编码不存在!');
    				} else {
    					$ary_sku_info['pdt_sn'] = $val['outer_id'];
    				}
    				$ary_sku_info['pdt_sale_price'] = $val['price'];
    				$ary_sku_info['pdt_stock'] = $val['quantity'];
    				$ary_sku_info['pdt_create_time'] = date('Y-m-d H:i:s');
    				$ary_sku_info['pdt_update_time'] = date('Y-m-d H:i:s');
    				$ary_sku_info['pdt_market_price'] = $val['price'];
    				$ary_sku_info['pdt_weight'] = isset($ary_top_items['item_weight'])?($ary_top_items['item_weight']*1000):0;
    				$ary_sku_info['pdt_total_stock'] = $val['quantity'];
    				$ary_sku_info['thd_indentify'] = 1;
    				$ary_sku_info['thd_pdtid'] = $val['sku_id'];
    				//验证该SKU在本地系统中是否已经存在，如过已经存在，则更新之
    				$ary_check_exist_con = array('thd_indentify' => 1, 'thd_pdtid' => $val['sku_id']);
    				$ary_tmp_1 = explode(';', trim($pdt_spec));
    				//更新商品属性关联表 related_goods_spec
    				$ary_related_goods_spec = array();
    				foreach ($ary_tmp_1 as $kkk => $vvv) {
    					$ary_tmp_2 = explode(':', $vvv);
    					if(count($ary_tmp_2) == '2'){
    						//属性1:属性值1;属性2:属性值2;
    						$memo1 = D('Gyfx')->selectOne('goods_spec','gs_name',array('gs_id'=>$ary_tmp_2[0]));
    						$memo2 = D('Gyfx')->selectOne('goods_spec_detail','gsd_value',array('gsd_id'=>$ary_tmp_2[1]));
    						$pdt_memo .= $memo1['gs_name'] . ':' . $memo2['gsd_value'] . ';';
    						$ary_related_goods_spec[] = array(
    								'gs_id'=>$ary_tmp_2[0],
    								'gsd_id'=>$ary_tmp_2[1],
    								'gs_is_sale_spec'=> '1',
    								'g_id'=>$mixd_goods_id,
    								'gsd_aliases'=>$memo2['gsd_value']
    						);
    					}
    				}
    				//此处可能会由于客户的outer_id重复造成问题
    				$ary_sku_info['pdt_memo'] = $pdt_memo; //商品销售规格组合。。。
    				
    				$mixed_exists = D('Gyfx')->selectOne('goods_products','pdt_id',$ary_check_exist_con);
    				if (is_array($mixed_exists) && count($mixed_exists) > 0) {
    					//更新货品
    					$ary_condition = array('pdt_id' => $mixed_exists['pdt_id']);
    					$mix_return = D('Gyfx')->update('goods_products',$ary_condition,$ary_sku_info);
    					if(!$mix_return) {
    						//处理单货品异常
    						return array('status' => false, 'err_code' => 8112, 'err_msg'=>'更新货品失败！');
    					}
    				} else {
    					$mix_return = D('Gyfx')->insert('goods_products',$ary_sku_info);
    					if(!$mix_return) {
    						return array('status' => false, 'err_code' => 8113, 'err_msg' =>'生成本地货品失败！');
    					}
    				}
    				//更新商品销售属性关联表
    				foreach($ary_related_goods_spec as $ary_rgs_info){
    					$ary_rgs_info['pdt_id'] = $mix_return;
                        if(!empty($ary_rgs_info['gsd_id'])){
                            $obj_result = D('Gyfx')->insert('related_goods_spec',$ary_rgs_info,1);
                            if(!$obj_result){
                                return array('status' => false, 'err_code' => 8114, 'err_msg' =>'生成本地货品销售属性关联表失败！');
                            }
                        }
    				}
					//dump($ary_sku_info);
					unset($ary_sku_info);
    			}
    		}
    	}
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"淘宝铺货下载商品",'淘宝铺货下载商品:'.$ary_add_goods['g_sn'].'-商品ID'.$mixd_goods_id));		
    	//商品相关信息全部更新完成，提交事务，一条商品记录更新完毕...
    	return array('status' => true, 'err_code' => 0, 'err_msg' => '');
    }
    
    /**
     * 商品数据处理
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function itemSaveFields($ary_top_items,$convert_rules){
    	$ary_add_goods =array();
    	if(trim($ary_top_items['outer_id'])==''){
    		$ary_add_goods['g_sn'] = 'TAOBAO' . trim($ary_top_items['num_iid']);
    	}else{
    		$ary_add_goods['g_sn'] = trim($ary_top_items['outer_id']);
    	}
    	//品牌信息
    	if(isset($convert_rules['item_brand']['value']) && $convert_rules['item_brand']['value'] > 0){
    		$ary_add_goods['gb_id'] = $convert_rules['item_brand']['value'];
    	}
    	$ary_add_goods['g_on_sale'] = 2;
    	if($convert_rules['on_sales']['value']=='1'){
    		$ary_add_goods['g_on_sale'] = 1;
    	}
    	$ary_add_goods['g_name'] = trim($ary_top_items['title']);
    	$ary_add_goods['g_off_sale_time'] = trim($ary_top_items['delist_time']);
    	$ary_add_goods['g_price'] = trim($ary_top_items['price']);
    	$ary_add_goods['g_market_price'] = trim($ary_top_items['price']);
    	//淘宝默认重量单位是Kg,分销默认g
    	$ary_add_goods['g_weight'] = trim($ary_top_items['item_weight ']*1000);
    	$ary_add_goods['g_stock'] = trim($ary_top_items['num']);
    	$ary_add_goods['thd_gid'] = trim($ary_top_items['num_iid']);
    	$ary_add_goods['g_create_time'] =  date('Y-m-d H:i:s');  //创建时间
    	$ary_add_goods['g_update_time'] =  date('Y-m-d H:i:s');	 //更新时间
    	$ary_add_goods['thd_indentify'] = 1;
    	$ary_add_goods['g_status'] = 1;
    	//判断是否设置新品上架
    	$ary_add_goods['g_new'] = 0;
    	if($convert_rules['set_new']['value'] == '1'){
    		$ary_add_goods['g_new'] = 1;
    	}
    	//判断是否设置热卖
    	$ary_add_goods['g_hot'] = 0;
    	if($convert_rules['set_hot']['value'] == '1'){
    		$ary_add_goods['g_hot'] = 1;
    	}
		$ary_add_goods['g_pre_sale_status'] = 0;
		if($convert_rules['on_presales']['value'] == '1'){
    		$ary_add_goods['g_pre_sale_status'] = 1;
    	}
    	return $ary_add_goods;
    }
    
    /**
     * 下载的商品类型默认为淘宝类型，如果不存在则添加
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function getDeafultTopType() {
    	$ary_type_data = D('GoodsType')->getGoodsType(array('gt_name' => '淘宝未分类类型'),$ary_field='*',$ary_order);
    	if ($ary_type_data[0]['gt_id']) {
    		$gt_id = $ary_type_data[0]['gt_id'];
    	} else {
    		$gt_id = D('GoodsType')->addGoodsType(array(
    				'gt_name' => '淘宝未分类类型',
    				'gt_status' => 1
    		));
    	}
    	return $gt_id;
    }
    
    /**
     * 处理分类
     * @param $int_goods_id 商品ID
     * @param $ary_config 转换规则
     * @param $ary_top_seller_cats 淘宝商品分类（店铺分类）
     * @$str_shop_sid 淘宝店铺sid
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    protected function convertTopItemCats($int_gods_id, $ary_config, $ary_top_seller_cats, $str_shop_sid) {
		 if ($ary_config['used_top_cat']['value'] == '1') {
		 	//获取淘宝店铺ID
		 	$str_shop = D('Gyfx')->selectOne('thd_shops','ts_sid',array('ts_id'=>$str_shop_sid));
		 	$str_shop_sid = $str_shop['ts_sid'];
    		//用户配置了使用淘宝分类，这里还要处理多对多的情况
    		if ('' != trim($ary_top_seller_cats)) {
    			$ary_tmp_seller_cats = explode(',', trim($ary_top_seller_cats));
    			if (is_array($ary_tmp_seller_cats) && count($ary_tmp_seller_cats) > 0) {
    				foreach ($ary_tmp_seller_cats as $key => $val) {
    					//获取该分类在本地系统中的ID
    					$ary_condtion = array('tsi_indentify' => 1, 'ts_sid' => $str_shop_sid, 'cid' => $val);
    					try {
    						$mixed_result = D('Gyfx')->selectOne('thd_shop_itemcats',null, $ary_condtion);
    					} catch (PDOException $e) {
    						return array('status' => false, 'error_code' => 'convertTopItemCats_003', 'message' => '读取分类遇到错误');
    					}
    					if (is_array($mixed_result) && count($mixed_result) > 0) {
    						//验证该分类是否已经同步到本地系统中
    						$mixed_catinfo = $this->addTaobaoSellerCatsToLocal($mixed_result);
    						if ($mixed_catinfo['status'] == false) {
    							return array('status' => false, 'error_code' => $mixed_catinfo['error_code'], 'message' => '新增分类错误');
    						}
    						//新增一个关联规则
    						$mixed_related = $this->addItemCatsRelated($int_gods_id, $mixed_catinfo['cat_id']);
    						if ($mixed_related['status'] == false) {
    							return array('status' => false, 'error_code' => $mixed_related['error_code'], 'message' => '新增关联失败');
    						}
    					}
    					continue;
    				}
    				return array('status' => true, 'error_code' => '', 'message' => '');
    			}
    			return array('status' => false, 'error_code' => 'convertTopItemCats_002', 'message' => '店铺分类');
    		}
    		//异常情况：淘宝商品没有店铺分类，直接关联到我们系统的内部分类--分类名称=淘宝未分类商品
    		return $this->addItemCatsRelated($int_gods_id, 'default');
		 }else{
		 	return $this->addItemCatsRelated($int_gods_id, 'default');
		 }
    }
    
    /**
     * 将淘宝分类增加到本地分类类目中
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function addTaobaoSellerCatsToLocal($mixed_result) {
    	//验证当前淘宝店铺分类在本地系统分类中是否已经存在,按商品名搜索
    	$ary_exist_con = array('gc_name' => $mixed_result['name']);
    	try {
    		$mixed_esist = D('Gyfx')->selectOne('goods_category','',$ary_exist_con);
    	} catch (PDOException $e) {
    		//验证失败，返回错误信息
    		return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_001', 'message' => '验证类目错误');
    	}
    	if (is_array($mixed_esist) && !empty($mixed_esist) && count($mixed_esist) > 0) {
    		//该分类已经同步过来，验证状态是否还有效，如果已经被删除，则将其充值为有效,并更新淘宝分类ID
    		if (in_array($mixed_esist, array(0, 2))) {
    			$ary_edit = array('gc_status' => 1,'gc_update_time'=>date('Y-m-d H:i:s'),'thd_catid'=>$mixed_result['tsi_id'],'thd_indentify'=>'1','thd_cat_info'=>json_encode($mixed_result) );
    			$ary_cont = array('gc_id' => $mixed_esist['gc_id']);
    			try {
    				$mixed_result = D('Gyfx')->update('goods_category',$ary_cont,$ary_edit);
    			} catch (PDOException $e) {
    				return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_002', 'message' => '更新分类失败');
    			}
    		}
    		return array('status' => true, 'cat_id' => $mixed_esist['gc_id'], 'error_code' => '', 'message' => '');
    	} else {
    		//分类还未同步过来，直接新增一个分类
    		//如果该分类不是叶子节点，要找他的父分类，并新增-- ^-^ 还好，淘宝分类只有两级~~
    		$ary_cats_add['gc_parent_id'] = 0;
    		$ary_cats_add['gc_is_parent'] = 1;
    		$ary_cats_add['gc_name'] = $mixed_result['name'];
    		$ary_cats_add['gc_order'] = 0;
    		$ary_cats_add['thd_catid'] = $mixed_result['tsi_id'];
    		$ary_cats_add['thd_indentify'] = 1;
    		$ary_cats_add['gc_is_display'] = 1;
    		$ary_cats_add['gc_create_time'] = date('Y-m-d H:i:s');
    		$ary_cats_add['gc_update_time'] = date('Y-m-d H:i:s');
    		if ($mixed_result['parent_cid'] > 0) {
    			//找到他的父节点信息
    			$ary_where['tsi_indentify'] = 1;
    			$ary_where['is_parent'] = 'true';
    			$ary_where['cid'] = $mixed_result['parent_cid'];
    			try {
    				$mixed_parent = D('Gyfx')->selectOne('thd_shop_itemcats',null,$ary_where);
    			} catch (PDOException $e) {
    				//获取当前分类的父分类失败了
    				return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_003', 'message' => '');
    			}
    			if (!is_array($mixed_parent) || empty($mixed_parent)) {
    				return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_010', 'message' => '');
    			}
    			//将当前分类的父分类添加到本地
    			$ary_add_info = $this->addTaobaoSellerCatsToLocal($mixed_parent);
    			if ($ary_add_info['status'] == false) {
    				//添加当前分类的父分类的时候出错了
    				return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_009', 'message' => '');
    			}
    			$ary_cats_add['gc_is_parent'] = 0;
    			$ary_cats_add['gc_parent_id'] = $ary_add_info['cat_id'];
    		}
    		//创建分类本身
    		try {
    			$mixed_result = D('Gyfx')->insert('goods_category',$ary_cats_add);
    		} catch (PDOException $e) {
    			return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_004', 'message' => '');
    		}
    		return array('status' => true, 'cat_id' => $mixed_result, 'error_code' => '', 'message' => '');
    	}
    }
    
    /**
     * 添加一个商品与分类的关联
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    protected function addItemCatsRelated($int_goods_id, $int_cat_id) {
    	if (!is_numeric($int_cat_id) && $int_cat_id == 'default') {
    		//将分类增加到淘宝未分类下面
    		$mixed_ary_result = $this->getDefaultTopCategory();
    		if ($mixed_ary_result['status'] == false) {
    			return array('status' => false, 'error_code' => 'addItemCatsRelated_000', 'message' => '');
    		}
    		$int_cat_id = $mixed_ary_result['cat_id'];
    	}
    	//验证是否已经存在分类关联
    	$ary_exist_con = array('g_id' => $int_goods_id, 'gc_id' => $int_cat_id);
    	try {
    		$mied_result = D('Gyfx')->selectOne('related_goods_category',null,$ary_exist_con);
    	} catch (PDOException $e) {
    		return array('status' => false, 'error_code' => 'addItemCatsRelated_001', 'message' => $e->getMessage());
    	}
    	if (is_array($mied_result) && !empty($mied_result) && count($mied_result) > 0) {
    		//已经存在关联则直接返回ture
    		return array('status' => true, 'error_code' => '', 'message' => '');
    	}
    	//不存在关联，则新增一个关联
    	$ary_itemcats_realted_add = array();
    	$ary_itemcats_realted_add['g_id'] = $int_goods_id;
    	$ary_itemcats_realted_add['gc_id'] = $int_cat_id;
    	try {
    		D('Gyfx')->insert('related_goods_category',$ary_itemcats_realted_add);
    	} catch (PDOException $e) {
    		return array('status' => false, 'error_code' => 'addItemCatsRelated_003', 'message' => $e->getMessage());
    	}
    	return array('status' => true, 'error_code' => '', 'message' => '');
    }
    
    /**
     * 淘宝商品默认分类--淘宝未分类商品
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function getDefaultTopCategory() {
    	$ary_cond = array('gc_name' => '淘宝未分类商品');
    	$ary_mixed_result = D('Gyfx')->selectOne('goods_category',null, $ary_cond);
    	if (is_array($ary_mixed_result) && count($ary_mixed_result) > 0) {
    		return array('status' => true, 'cat_id' => $ary_mixed_result['gc_id'], 'message' => '');
    	}
    	//添加淘宝默认分类
    	$ary_add_parent['gc_parent_id'] = 0;
    	$ary_add_parent['gc_is_parent'] = 0;
    	$ary_add_parent['gc_name'] = '淘宝未分类商品';
    	$ary_add_parent['gc_order'] = 0;
    	$ary_add_parent['gc_is_display'] = 1;
    	$ary_add_parent['gt_id'] = 0;
    	$ary_add_parent['gc_description'] = '未分类的淘宝商品';
    	$ary_add_parent['gc_create_time'] = date('Y-m-d H:i:s');
    	$ary_add_parent['gc_update_time'] = date('Y-m-d H:i:s');
    	try {
    		$mixed_result = D('Gyfx')->insert('goods_category',$ary_add_parent);
    	} catch (PDOException $e) {
    		return array('status' => false, 'cat_id' => 0, 'message' => '新增淘宝商品默认分类失败!');
    	}
    	return array('status' => true, 'cat_id' => $mixed_result, 'message' => '');
    }
    
    /**
     * 淘宝商品默认品牌--淘宝未分品牌
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function getDefaultTopBrand() {
    	$ary_cond = array('gb_name' => '淘宝未分品牌商品');
    	$ary_mixed_result = D('Gyfx')->selectOne('goods_brand',null, $ary_cond);
    	if (is_array($ary_mixed_result) && count($ary_mixed_result) > 0) {
    		return array('status' => true, 'b_id' => $ary_mixed_result['gb_id'], 'message' => '');
    	}
    	//添加淘宝默认品牌
    	$ary_add_parent['gb_name'] = '淘宝未分品牌商品';
    	$ary_add_parent['gc_order'] = 0;
    	$ary_add_parent['gb_status'] = 1;
    	$ary_add_parent['gb_detail'] = '未分品牌的淘宝商品';
    	$ary_add_parent['gb_create_time'] = date('Y-m-d H:i:s');
    	$ary_add_parent['gb_update_time'] = date('Y-m-d H:i:s');
    	try {
    		$mixed_result = D('Gyfx')->insert('goods_brand',$ary_add_parent);
    	} catch (PDOException $e) {
    		return array('status' => false, 'b_id' => 0, 'message' => '新增淘宝商品默认分类失败!');
    	}
    	return array('status' => true, 'b_id' => $mixed_result, 'message' => '');
    }
    
    /**
     * 下载淘宝商品图片到本地系统中
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-29
     */
    public function synItemImagesToLocal($ary_images = array(), $int_goods_id = 0, $str_zhutu_url = '') {
    	//获取该商品本地数据库商品图片表中保存的所有图片
    	$ary_condition = array('g_id' => $int_goods_id);
    	$str_update_time = date('Y-m-d H:i:s');
    	try {
    		$mixed_result = D('Gyfx')->deleteInfo('goods_pictures',$ary_condition);
    	} catch (PDOException $e) {
    		return array('status' => false, 'data' => array(), 'msg' => '删除旧的商品图片失败！');
    	}
    	//将淘宝的商品图片读到本地并入库
    	foreach ($ary_images as $val) {
    		if (trim($val['url']) == trim($str_zhutu_url)) {
    			//主图，跳过
    			continue;
    		}
    		//其他图片读取到本地保存，并且入库做相应的关联
    		//先将淘宝图片下载到本地保存
    		$str_local_image_url = $this->downloadTopImageToLocal(trim($val['url']),0,'./Public/Uploads/' . CI_SN.'/goods/top');
    		//商品图片信息入库
    		$ary_item_images = array();
    		$ary_item_images['g_id'] = $int_goods_id;
    		$ary_item_images['gp_picture'] = $str_local_image_url;
    		$ary_item_images['gp_status'] = 1;
    		$ary_item_images['gp_order'] = intval($val['position']);
    		$ary_item_images['gp_create_time'] = $str_update_time;
    		$ary_item_images['gp_update_time'] = $str_update_time;
    		try {
    			$mixed_result = D('Gyfx')->insert('goods_pictures',$ary_item_images);
    		} catch (PDOException $e) {
    			return array('status' => false, 'data' => array(), 'msg' => '新增商品图片到商品图片表失败！');
    		}
    	}
    	return array('status' => true, 'data' => array(), 'msg' => 'normal');
    }
    
    /**
     * 转换淘宝商品显示（非销售）属性pid:vid;pid:vid
     * 返回top_pid:local_pid|top_vid:local_vid;top_pid:local_pid|top_vid:local_vid
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-29
     * @param int $int_top_cid
     * @param str $property_alias 淘宝属性的别名
     */
    public function convertTopItemProv($str_property = '', $convert_rules = array(), $int_goods_id = 0, $int_top_cid = 0 , $property_alias='',$str_thd_shopsid,$props_name = '',$properties_name) {
		$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$str_thd_shopsid),'ts_shop_token');
		//获得第三方店铺ID
		$taobao_obj = new TaobaoApi($access_token);
		//获得销售属性
		$ary_sale_props = array();
		$ary_property = explode(';',$property_alias);
		foreach($ary_property as $ary_tmp_prop){
			$ary_tmp_prop = explode(':',$ary_tmp_prop);
			$ary_sale_props[] = $ary_tmp_prop[0];
		}
		$ary_sale_props = array_unique($ary_sale_props);
		if ($str_property == '') {
    		//该商品没有属性
    		return array('status' => true, 'error_code' => '0x1000000001', 'data' => '');
    	}
    	//返回更新的属性
    	$ary_return = '';
    	//返回更新的非销售属性
    	$ary_unsale_return = '';
    	//是否是销售属性
    	$is_sale = 0;
    	$ary_tmp_pidvid = explode(';', trim($str_property));
    	if($property_alias!=''){
    		//淘宝piv:vid:别名的临时数组
    		$ary_tmp_pidvidalias = explode(';', trim($property_alias));
    	}else{
    		$ary_tmp_pidvidalias = false;
    	}
    	$int_tmp_index = 0;
    	foreach ($ary_tmp_pidvid as $pidvid) {
    		$local_prov_id = 0;
    		$ary_tmp_prov = explode(':', $pidvid);
    		//先处理可能存在的淘宝商品品牌
    		if (($ary_tmp_prov[0] == 20000) && ($convert_rules['used_top_brand']['value'] == '1')) {
    			//pid=20000的，是淘宝的商品品牌
    			//如果规则设置了使用淘宝品牌
    			$mixed_result = $this->convertTopBrandTolocal($ary_tmp_prov[1],$int_goods_id,$int_top_cid,$convert_rules,$str_thd_shopsid,$props_name);
    			if ($mixed_result['status'] == false) {
    				return array('status' => true, 'error_code' => '0x100000000000001');
    			}
    			continue;
    		}
    		/*             * *************处理商品属性，开始***************** */
			//删除本地商品属性
			
    		//检查属性在本地是否存在
    		$ary_check_pid_exist = array('thd_indentify' => 1, 'thd_gpid' => trim($ary_tmp_prov[0]));
    		try {
    			$exist_result = D('Gyfx')->selectOne('goods_spec','gs_id,gs_is_sale_spec,gs_name',$ary_check_pid_exist);
				//是销售属性
				if(!empty($exist_result)){
					if(in_array($ary_check_pid_exist['thd_gpid'],$ary_sale_props)){
						if($exist_result['gs_is_sale_spec'] != '1'){
							M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_check_pid_exist)->data(array('gs_is_sale_spec'=>1))->save();
						}
					}				
				}
    		} catch (PDOException $e) {
    			//检查商品属性是否存在时，失败了！
    			return array('status' => false, 'error_code' => '0x1000000002');
    		}
    		if (is_array($exist_result) && !empty($exist_result)) {
    			//属性已经在本地存在
    			$ary_return .= trim($ary_tmp_prov[0]) . ':' . $exist_result['gs_id'] . '|';
    			if($exist_result['gs_is_sale_spec'] != '1'){
    				$ary_unsale_return .= trim($ary_tmp_prov[0]) . ':' . $exist_result['gs_id'] . '|';
    				$is_sale = 0;
    			}else{
    				$is_sale = 1;
    			}
    			$local_prov_id = $exist_result['gs_id'];
    		} else {
    			//属性在本地不存在，则新增一个属性
    			//获取淘宝属性详细信息
    			$fetch_condition = array('pid' => trim($ary_tmp_prov[0]));
    			try {
    				$prov_info = D('Gyfx')->selectOne('top_itemprops',null,$fetch_condition);
    			} catch (PDOException $e) {
    				//获取淘宝属性详细信息时遇到了错误
    				return array('status' => false, 'error_code' => '0x1000000003');
    			}
    			/**
    			 * 如果$prov_info 从本地临时表没有取到
    			 * 需要从淘宝接口更新临时表相应属性信息
    			 */
    			// $ary_tmp_prov[0]; //商品属性的ID
    			// $int_top_cid; //淘宝商品分类的ID
    			if (empty($prov_info) || !$prov_info || $prov_info == array()) {
    				//如果没取到，则调淘宝API接口取出
					//没有获得属性取数据
					if(!empty($properties_name)){
						$res_itemProps_data = array();
						$ary_properties_name = explode(';',$properties_name);
						foreach($ary_properties_name as $ary_property_name){
							$ary_property_name = explode(':',$ary_property_name);
							if(!empty($ary_property_name)){
								if($ary_tmp_prov[0] == $ary_property_name[0]){
									$res_itemProps_data[0]['name'] = $ary_property_name[2];
									$res_itemProps_data[0]['is_enum_prop'] = 'true';
									$res_itemProps_data[0]['is_item_prop'] = 'true';
									$res_itemProps_data[0]['is_sale_prop'] = 'true';
									$res_itemProps_data[0]['status'] = 'normal';
								}	
							}
						}					
					}
    				//去淘宝搜索
					if(empty($res_itemProps_data)){
						/**
						$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$str_thd_shopsid),'ts_shop_token');
						//获得第三方店铺ID
						$taobao_obj = new TaobaoApi($access_token);
						**/
						$res_itemProps = $taobao_obj->getThdItemProps($int_top_cid, $ary_tmp_prov[0]);
						$res_itemProps_data = $res_itemProps['itemprops_get_response']['item_props']['item_prop'];						
					}
    				if(!empty($res_itemProps_data)){
    					//根据API接口返回值构造数据
    					$ary_insert = array(
    							'cid' => $int_top_cid,
    							'is_input_prop' => ($res_itemProps_data[0]['is_input_prop']) ? 'true' : 'false',
    							'pid' => $ary_tmp_prov[0],
    							'parent_pid' => intval($res_itemProps_data[0]['parent_pid']),
    							'parent_vid' => intval($res_itemProps_data[0]['parent_vid']),
    							'name' => $res_itemProps_data[0]['name'],
    							'is_key_prop' => ($res_itemProps_data[0]['is_key_prop']) ? 'true' : 'false',
    							'is_color_prop' => ($res_itemProps_data[0]['is_color_prop']) ? 'true' : 'false',
    							'is_enum_prop' => $res_itemProps_data[0]['is_enum_prop'],
    							'is_item_prop' => ($res_itemProps_data[0]['is_item_prop']) ? 'true' : 'false',
    							'must' => ($res_itemProps_data[0]['must']) ? 'true' : 'false',
    							'multi' => ($res_itemProps_data[0]['multi']) ? 'true' : 'false',
    							'status' => $res_itemProps_data[0]['status'],
    							'sort_order' => intval($res_itemProps_data[0]['sort_order']),
    							'child_template' => empty($res_itemProps_data[0]['child_template'])?'':$res_itemProps_data[0]['child_template'],
    							'is_allow_alias' => ($res_itemProps_data[0]['is_allow_alias']) ? 'true' : 'false',
    							'is_sale_prop' =>($res_itemProps_data[0]['is_sale_prop']) ? 'true' : 'false',
    							);
    							// 向top_itemprops表插入数据
    							try {
    								$res_insert = D ( 'Gyfx' )->insert ( 'top_itemprops', $ary_insert );
    							} catch ( PDOException $e ) {
    								// 属性已有 不做处理
    							}
    							$prov_info = $ary_insert;
                                unset($res_itemProps_data);
    				}
    			}
    			//如果数据空退出
    			if(empty($prov_info['name'])){
    				continue;
    				//return array('status' => false, 'error_code' => '0x1000000004');
    			}
    			$ary_prop_add = array();
    			$ary_prop_add['gs_name'] = $prov_info['name'];
    			$ary_prop_add['gs_is_sale_spec'] = ($prov_info['is_sale_prop'] == 'true') ? 1 : 0;
    			$ary_prop_add['gs_create_time'] = date('Y-m-d H:i:s');
    			$ary_prop_add['gs_update_time'] = date('Y-m-d H:i:s');
				$ary_prop_add['gs_input_type'] = 2;
    			$ary_prop_add['thd_indentify'] = 1;
    			$ary_prop_add['thd_gpid'] = trim($ary_tmp_prov[0]);
    			$ary_prop_add['top_cid'] = $prov_info['cid'];
    			try {
    				$mix_return = D('Gyfx')->insert('goods_spec',$ary_prop_add);
    			} catch (PDOException $e) {
    				//新增淘宝商品属性失败了
    				return array('status' => false, 'error_code' => '0x1000000004');
    			}
    			$ary_return .= trim($ary_tmp_prov[0]) . ':' . $mix_return . '|';
    			if($ary_prop_add['gs_is_sale_spec'] != '1'){
    				$ary_unsale_return .= trim($ary_tmp_prov[0]) . ':' . $mix_return . '|';
    				$is_sale = 0;
    			}else{
    				$is_sale = 1;
    			}
    			$local_prov_id = $mix_return;
    		}
    		/**
    		 * 淘宝下载的销售属性需要在本地的属性-类型关联表中建立关联
    		 */
    		//判断类型表中有没有一个叫淘宝未分类类型的,如果没有则插入
    		$gt_id = $this->getDeafultTopType();
    		//在关联表中插入相应数据。注：货号不属于扩展属性
    		@$ra_insert = D('Gyfx')->insert('related_goods_type_spec',array(
    				'gs_id' => $local_prov_id,
    				'gt_id' => $gt_id
    				//'rgst_is_sale' => $ary_prop_add['gs_is_sale_spec']    				
    		),1);
    		/***************处理商品属性，结束******************/
    		/***************处理商品属性值，开始******************/
    		//处理淘宝属性值的别名
    		//先检查该属性，是否有别名
    		if(false !== $ary_tmp_pidvidalias){
    			foreach($ary_tmp_pidvidalias as $al){
    				$str_alias = '';
    				$has_alias = strstr($al,$ary_tmp_prov[0].':'.$ary_tmp_prov[1].':');
    				if(false !== $has_alias){
    					//如果查找到了pid:vid组合，则说明本次pid:vid存在别名，跳出循环并返回查找到str_alias的别名值。无别名时$str_alias = ''
    					//示例为：1627207:28341:黑色
    					$str_alias = $al;
    					break;
    				}
    			}
    		}
    		//$has_alias 代表是否有别名，$ary_alias为别名数组
    		if($str_alias != ''){
    			$has_alias = true;
    			$ary_alias = explode(':', $str_alias);
    		}else{
    			$has_alias = false;
    		}
    		//end add +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    
    		//检查商品属性值在本地是否存在
    		$ary_check_vid_exist = array('thd_indentify' => 1, 'thd_gpid' => trim($ary_tmp_prov[0]));
    		$ary_check_vid_exist['thd_gpvid'] = trim($ary_tmp_prov[1]);
    		//如果有别名，则查找条件需要带上别名作为gsd_value进行查询
    		if($has_alias) $ary_check_vid_exist['gsd_value'] = $ary_alias[2];
    		try {
    			$exist_result = D('Gyfx')->selectOne('goods_spec_detail','gsd_id',$ary_check_vid_exist);
    		} catch (PDOException $e) {
    			//检查商品属性值是否存在时，失败了！
    			return array('status' => false, 'error_code' => '0x1000000005');
    		}
    		if (is_array($exist_result) && !empty($exist_result)) {
    			//属性值已经在本地存在
    			$ary_return .= trim($ary_tmp_prov[1]) . ':' . $exist_result['gsd_id'];
    			if($is_sale != '1'){
    				$ary_unsale_return .= trim($ary_tmp_prov[1]) . ':' . $exist_result['gsd_id'];
    				$is_sale = 0;
                }
    		} else {
    			//属性值在本地不存在，则需要获取淘宝商品属性值信息，然后新增一个属性值
    			//此处也要进行判断，无别名的情况下，属性值通过淘宝临时表获取，有别名的情况下，属性值直接返回为别名
    			//获取淘宝商品属性值
    			$get_top_val_con = array();
    			$get_top_val_con['pid'] = trim($ary_tmp_prov[0]);
    			$get_top_val_con['vid'] = trim($ary_tmp_prov[1]);
    			try {
    				$top_val_info = D('Gyfx')->selectOne('top_itemprop_values',null,$get_top_val_con);
    			} catch (PDOException $e) {
    				//获取淘宝商品属性值详细信息时，失败了
    				return array('status' => false, 'error_code' => '0x1000000006');
    			}
    			/**
    			 * 如果$prov_info 从本地临时表没有取到
    			 * 需要从淘宝接口更新临时表相应属性值信息
    			 */
    			if (empty($top_val_info) || !$top_val_info || $top_val_info == array()) {
    				//如果没取到，则调淘宝API接口取出
					//处理接口获取商品属性的情况    20000:106707564:品牌:密涅尔;
					//销售属性
					$ary_res_itemPropsValues_data = array();
					$ary_properties_name = explode ( ';', $properties_name );
					if(!empty($properties_name)){
						foreach ( $ary_properties_name as $ary_property_name ) {
							$ary_property_name = explode ( ':', $ary_property_name );
							if (($ary_property_name [0] == $get_top_val_con['pid']) && ($ary_property_name [1] == $get_top_val_con['vid'])) {
								$ary_res_itemPropsValues_data [0] ['prop_name'] = $ary_property_name [2];
								$ary_res_itemPropsValues_data [0] ['name'] = $ary_property_name [3];
								$ary_res_itemPropsValues_data [0] ['name_alias'] = $ary_property_name [3];
								$ary_res_itemPropsValues_data [0] ['status'] = 'normal';
								$ary_res_itemPropsValues_data [0] ['sort_order'] = '9999';
							}
						}
					}else{
						//非销售属性
						$ary_props_name = explode ( ';', $props_name );
						foreach ( $ary_props_name as $prop_names ) {
							$prop_name = explode ( ':', $prop_names );
							if (($prop_name [0] == $get_top_val_con['pid']) && ($prop_name [1] == $get_top_val_con['vid'])) {
								$ary_res_itemPropsValues_data [0] ['prop_name'] = $prop_name [2];
								$ary_res_itemPropsValues_data [0] ['name'] = $prop_name [3];
								$ary_res_itemPropsValues_data [0] ['name_alias'] = $prop_name [3];
								$ary_res_itemPropsValues_data [0] ['status'] = 'normal';
								$ary_res_itemPropsValues_data [0] ['sort_order'] = '9999';
							}
						}
					}
					if(empty($ary_res_itemPropsValues_data)){
					/**
						//去淘宝搜索
						$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$str_thd_shopsid),'ts_shop_token');
						//获得第三方店铺ID
						$taobao_obj = new TaobaoApi($access_token);
					**/	
						$res_itemPropsValues = $taobao_obj->getThdItemPropsValues($int_top_cid, $get_top_val_con['pid'] . ':' . $get_top_val_con['vid']);
						$ary_res_itemPropsValues_data = $res_itemPropsValues['itempropvalues_get_response']['prop_values']['prop_value'];					
					}
    				//如果实在获取不到属性，执行下一条
    				if(empty($ary_res_itemPropsValues_data)){
                        if ($int_tmp_index < count($ary_tmp_pidvid) - 1) {
                            $ary_return .= ';';
                            if($is_sale != '1'){
                                $ary_unsale_return .= ';';
                            }
                        }
    					continue;	
    				}
    				//根据API接口返回值构造数据
    				$ary_value_insert = array(
    						'cid' => $int_top_cid,
    						'pid' => $get_top_val_con['pid'],
    						'prop_name' => $ary_res_itemPropsValues_data[0]['prop_name'],
    						'vid' => $get_top_val_con['vid'],
    						'name' => $ary_res_itemPropsValues_data[0]['name'],
    						'name_alias' => $ary_res_itemPropsValues_data[0]['name_alias'],
    						'is_parent' => ($ary_res_itemPropsValues_data[0]['is_parent']) ? 'true' : 'false',
    						'status' => $ary_res_itemPropsValues_data[0]['status'],
    						'sort_order' => $ary_res_itemPropsValues_data[0]['sort_order']
    				);
    				//向top_itemprop_values表插入数据
    				try{
    					$res_value_insert = D('Gyfx')->insert('top_itemprop_values',$ary_value_insert);
    				}  catch (PDOException $e){
    					//可能该属性已经有了 此处不做
    				}
    				$top_val_info = $ary_value_insert;
    			}
    			$ary_value_add = array();
    			$ary_value_add['gs_id'] = $local_prov_id;
    			//edit插入新属性值的时候，此处判断如果存在别名，则将别名当作新属性值名称插入
    			$ary_value_add['gsd_value'] = $has_alias ? $ary_alias[2] :$top_val_info['name'];
    			$ary_value_add['gsd_order'] = $top_val_info['sort_order'];
    			$ary_value_add['gsd_create_time'] = date('Y-m-d H:i:s');
    			$ary_value_add['gsd_update_time'] = date('Y-m-d H:i:s');
    			$ary_value_add['thd_indentify'] = 1;
    			$ary_value_add['thd_gpid'] = $top_val_info['pid'];
    			$ary_value_add['thd_gpvid'] = $top_val_info['vid'];
    			
    			try {
    				$mixed_return = D('Gyfx')->insert('goods_spec_detail',$ary_value_add);
    			} catch (PDOException $e) {
    				//新增淘宝属性值入本地数据库，失败了
    				return array('status' => false, 'error_code' => '0x1000000007');
    			}
    			$ary_return .= trim($ary_tmp_prov[1]) . ':' . $mixed_return;
    			if($is_sale != '1'){
    				$ary_unsale_return .= trim($ary_tmp_prov[1]) . ':' . $mixed_return;
                    $is_sale = 0;
    			}
    		}
    		/*             * *************处理商品属性值，结束***************** */
    		if ($int_tmp_index < count($ary_tmp_pidvid) - 1) {
    			$ary_return .= ';';
    			if($is_sale != '1'){
    				$ary_unsale_return .= ';';
    			}
    		}
    		$int_tmp_index++;
    	}
    	return array('status' => true, 'error_code' => '0x1000000008', 'data' => $ary_return,'usale_data'=>$ary_unsale_return);
    }
    
	/**
     * 将淘宝品牌转换到本地
     *
     * @params $array_post_setdata 用户提交的设置数组
     * @return array('status'=>true|false,message=>'提示信息')
     * @author Mithern
     * @modify 2012-03-13
     * @version 1.0
     */
    public function convertTopBrandTolocal($int_toppropvid, $int_goods_id,$int_top_cid,$convert_rules,$str_thd_shopsid,$props_name) {
    	//配置了同步品牌
		if($convert_rules['used_top_brand']['value'] == '1' && $int_toppropvid != null){
			//获取本地缓存的本地淘宝品牌信息
			$ary_condition = array('vid' => $int_toppropvid);
			try {
				$mixed_cache_brand = D('Gyfx')->selectOne('top_itemprop_values',null,$ary_condition);
			} catch (PDOException $e) {
				return array('status' => false, 'data' => array(), 'message' => '获取缓存淘宝品牌信息时出错' . __FILE__ . __LINE__);
			}
			//如果淘宝品牌不存在。。。
			if (!is_array($mixed_cache_brand) || count($mixed_cache_brand) <= 0) {
				//如果品牌不存在，则向top_itemprop_values中插入一条数据
				//去淘宝搜索
				$access_token = D('ThdShops')->getAccessToken(array('ts_id'=>$str_thd_shopsid),'ts_shop_token');
				//获得第三方店铺ID
				$taobao_obj = new TaobaoApi($access_token);
				$res_itemPropsValues = $taobao_obj->getThdItemPropsValues($int_top_cid, '20000' . ':' . $int_toppropvid);
				$ary_res_itemPropsValues_data = $res_itemPropsValues['itempropvalues_get_response']['prop_values']['prop_value'];
				if(empty($ary_res_itemPropsValues_data)){
					//处理调用接口获得不了品牌的信息
					$props_name = explode(';',$props_name);
					foreach($props_name as $prop_name){
						$prop_name = explode(':',$prop_name);
						//20000:106707564:品牌:密涅尔;
						if($prop_name[0] == '20000'){
							$ary_res_itemPropsValues_data[0]['prop_name'] = $prop_name[2];
							$ary_res_itemPropsValues_data[0]['name'] = $prop_name[3];
							$ary_res_itemPropsValues_data[0]['name_alias'] = $prop_name[3];
							$ary_res_itemPropsValues_data[0]['status'] = 'normal';
							$ary_res_itemPropsValues_data[0]['sort_order'] = '9999';
							$int_toppropvid = $prop_name[1];
						}
					}
				}
				if(empty($ary_res_itemPropsValues_data)){
					$brand_info = $this->getDefaultTopBrand();
					if($brand_info['status'] == true){
						$int_brand_id = $brand_info['b_id'];
					}else{
						return array('status' => false, 'data' => array(), 'message' => '获取本地默认未分淘宝品牌商品报错');
					}
					
				}else{
					//根据API接口返回值构造数据
					$ary_value_insert = array(
							'cid' => $int_top_cid,
							'pid' => 20000,
							'prop_name' => $ary_res_itemPropsValues_data[0]['prop_name'],
							'vid' => $int_toppropvid,
							'name' => $ary_res_itemPropsValues_data[0]['name'],
							'name_alias' => $ary_res_itemPropsValues_data[0]['name_alias'],
							'is_parent' => ($ary_res_itemPropsValues_data[0]['is_parent']) ? 'true' : 'false',
							'status' => $ary_res_itemPropsValues_data[0]['status'],
							'sort_order' => $ary_res_itemPropsValues_data[0]['sort_order']
					);
					//向top_itemprop_values表插入数据
					@$res_value_insert = D('Gyfx')->insert('top_itemprop_values',$ary_value_insert);
					$mixed_cache_brand = $ary_value_insert;
					//edit end ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
					//验证品牌在本地是否在本地存在
					$ary_exist_cond = array('gb_name' =>$mixed_cache_brand['name']);
					try {
						$mixed_result = D('Gyfx')->selectOne('goods_brand','gb_id',$ary_exist_cond);
					} catch (PDOException $e) {
						return array('status' => false, 'data' => array(), 'message' => '验证品牌是否存在时出现错误' . __FILE__ . __LINE__);
					}
					//拼接品牌信息
					$ary_item_brand_info = array();
					$ary_item_brand_info['gb_name'] = $mixed_cache_brand['name'];
					$ary_item_brand_info['gb_order'] = $mixed_cache_brand['sort_order'];
					$ary_item_brand_info['gb_detail'] = $mixed_cache_brand['name_alias'];
					$ary_item_brand_info['gb_update_time'] = date('Y-m-d H:i:s');
					$ary_item_brand_info['thd_gbid'] = $int_toppropvid;
					$ary_item_brand_info['thd_indentify'] = 1;
					
					//如果存在，则判断品牌是否被删除，如果删除，则还原之，如果不存在，则新增一个品牌
					if (is_array($mixed_result) && count($mixed_result) > 0) {
						//淘宝品牌在本系统中已经存在，则更新之
						$ary_condition = array('gb_id' => $mixed_result['gb_id']);
						try {
							$mixed_edit_result = D('Gyfx')->update('goods_brand',$ary_condition,$ary_item_brand_info);
						} catch (PDOException $e) {
							return array('status' => false, 'data' => array(), 'message' => '更新品牌信息失败！');
						}
						$int_brand_id = $mixed_result['gb_id'];
					} else {
						$ary_item_brand_info['gb_create_time'] = date('Y-m-d H:i:s');
						try {
							$mixed_add_result = D('Gyfx')->insert('goods_brand',$ary_item_brand_info);
						} catch (PDOException $e) {
							return array('status' => false, 'data' => array(), 'message' => '把淘宝品牌保存到本系统失败！');
						}
						$int_brand_id = $mixed_add_result;
					}
				}
			}else{
			//验证品牌在本地是否在本地存在
					$ary_exist_cond = array('gb_name' =>$mixed_cache_brand['name']);
					try {
						$mixed_result = D('Gyfx')->selectOne('goods_brand','gb_id',$ary_exist_cond);
					} catch (PDOException $e) {
						return array('status' => false, 'data' => array(), 'message' => '验证品牌是否存在时出现错误' . __FILE__ . __LINE__);
					}
					//拼接品牌信息
					$ary_item_brand_info = array();
					$ary_item_brand_info['gb_name'] = $mixed_cache_brand['name'];
					$ary_item_brand_info['gb_order'] = $mixed_cache_brand['sort_order'];
					$ary_item_brand_info['gb_detail'] = $mixed_cache_brand['name_alias'];
					$ary_item_brand_info['gb_update_time'] = date('Y-m-d H:i:s');
					$ary_item_brand_info['thd_gbid'] = $int_toppropvid;
					$ary_item_brand_info['thd_indentify'] = 1;
					
					//如果存在，则判断品牌是否被删除，如果删除，则还原之，如果不存在，则新增一个品牌
					if (is_array($mixed_result) && count($mixed_result) > 0) {
						//淘宝品牌在本系统中已经存在，则更新之
						$ary_condition = array('gb_id' => $mixed_result['gb_id']);
						try {
							$mixed_edit_result = D('Gyfx')->update('goods_brand',$ary_condition,$ary_item_brand_info);
						} catch (PDOException $e) {
							return array('status' => false, 'data' => array(), 'message' => '更新品牌信息失败！');
						}
						$int_brand_id = $mixed_result['gb_id'];
					} else {
						$ary_item_brand_info['gb_create_time'] = date('Y-m-d H:i:s');
						try {
							$mixed_add_result = D('Gyfx')->insert('goods_brand',$ary_item_brand_info);
						} catch (PDOException $e) {
							return array('status' => false, 'data' => array(), 'message' => '把淘宝品牌保存到本系统失败！');
						}
						$int_brand_id = $mixed_add_result;
					}
			}
		}else{
			$brand_info = $this->getDefaultTopBrand();
			if($brand_info['status'] == true){
				$int_brand_id = $brand_info['b_id'];
			}else{
				return array('status' => false, 'data' => array(), 'message' => '获取本地默认未分淘宝品牌商品报错');
			}
		}
        //更新商品表，增加商品关联
        if(!empty($int_brand_id)){
        	$ary_goods_condition = array('g_id' => $int_goods_id);
        	$ary_fields = array('gb_id' => $int_brand_id,'g_update_time'=>date('Y-m-d H:i:s'));
        	try {
        		$mixed_result = D('Gyfx')->update('goods',$ary_goods_condition,$ary_fields);
        	} catch (PDOException $e) {
        		return array('status' => false, 'data' => array(), 'message' => '新增品牌和商品的关联失败！');
        	}	
        }
        return array('status' => true, 'data' => array(), 'message' => 'normal');
    }
    
    /**
     * 处理淘宝商品图片信息
     * 将淘宝图片下载到本地服务器
     * 并且把其中的路径替换成本地路径
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    protected function deal_withTopItemDesc($str_topitem_desc = '') {
    	$preg = "/<img.*?src=\"(.+?)\".*?>/i";
    	preg_match_all($preg, $str_topitem_desc, $match);
    	if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
    		$ary_replace_goal = array();
    		$ary_replace_to = array();
    		foreach ($match[1] as $key => $val) {
    			//判断图片来自哪里的，注释的是只下载淘宝的图片
//     			$ary_tmp_istop = explode('.taobaocdn.com', $val);
//     			if (count($ary_tmp_istop) < 2) {
//     				continue;
//     			}
    			$ary_replace_goal[] = $val;
    			$ary_replace_to[] = $this->downloadTopImageToLocal($val,1,'./Public/Uploads/' . CI_SN.'/desc/top');
    		}
    		$str_topitem_desc = str_replace($ary_replace_goal, $ary_replace_to, $str_topitem_desc);
    	}
    	return $str_topitem_desc;
    }
    
    /**
     * 下载图片并保存到本地服务器，需要接收完整的top图片地址
     * 返回下载完的本地图片完整路径
     * edit by wangguibin @2013-10-24
     * 增加一个默认值为0的变量$http_sign ，为1的时候，返回完整路径，用于宝贝描述
     */
    protected function downloadTopImageToLocal($str_top_img_url,$http_sign = 0,$str_path) {
    	//截取文件名，/分割去取最后
    	$ary_path = explode('/', $str_top_img_url);
    	$str_base_name = $ary_path[count($ary_path) - 1];
    	$base_serv_path = $str_path;
    	if (!is_dir($base_serv_path)) {
    		//如果目录不存在，则创建之
    		mkdir($base_serv_path, 0777, 1);
    	}
    	$str_filename = $str_base_name;
    	//拼接图片保存路径
    	$str_file_path = $base_serv_path . '/' . $str_filename;
    	//拼接图片url
    	//$str_url = WEB_ROOT . $str_path .'/' . $str_filename;
    	$str_url = $str_path . '/' . $str_filename;
    	//读取文件
    	$str_filecontent = @file_get_contents($str_top_img_url);
    	if (strlen($str_filecontent) > 20) {
    		if (file_put_contents($str_file_path, $str_filecontent)) {
    			//文件保存成功，返回本地服务器访问地址
    			if($http_sign){
    				$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
					$tmp_str_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port  . ltrim($str_url,'.');
					
					$tmp_str_url = str_replace('http://_','',$tmp_str_url);
					//七牛图片存储
					if($_SESSION['OSS']['GY_QN_ON'] == '1'){
						return D('ViewGoods')->ReplaceItemPicReal(ltrim($str_url,'.'));
						//return D('QnPic')->picToQn(ltrim($str_url,'.')); 
					}else{
						return $tmp_str_url;
					}
    			}else{
					$tmp_str_url = ltrim($str_url,'.');
					//七牛图片存储
					if($_SESSION['OSS']['GY_QN_ON'] == '1'){
						return D('ViewGoods')->ReplaceItemPicReal($tmp_str_url);
						//return D('QnPic')->picToQn(ltrim($str_url,'.')); 
					}else{
						return $tmp_str_url;
					}
    			}
    		}
    	}
    	//容错，返回原地址
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){//七牛图片存储
			return D('ViewGoods')->ReplaceItemPicReal($str_top_img_url);
		}else{
			return $str_top_img_url;
		}
    }
    
    /**
     * 淘宝商品下载失败记录错误日志
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-24 
     */
    function writeLog($ary_data,$name){
    	//淘宝下载日志路径
    	$log_dir = APP_PATH.'Runtime/'.CI_SN.'/Logs/TaobaoApilog/';
    	if(!file_exists($log_dir)){
    		mkdir($log_dir,0700);
    	}
    	$log_file = $log_dir .$name.date('Ymd') . '.log';
    	$fp = fopen($log_file, 'a+');
    	fwrite($fp, $ary_data);
    	fclose($fp);
    }
    
    /**
     * 下载物流模板
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-29
     */
    public function deliveryTemplateList(){
    	$this->getSubNav(6, 4, 50);
        $ary_data = $this->_get();
    	$ary_where = array();
    	if(!empty($ary_data['lt_shop_id'])){
    		$ary_where['lt_shop_id'] = $ary_data['lt_shop_id'];
    	}
    	$int_count =  D('Gyfx')->getCount('thd_logistic_template',$ary_where);
    	$page_no = max(0, (int) $this->_get('p', '', 1));
    	$page_size = 20;
    	$obj_page = new Page($int_count, $page_size);
    	$page = $obj_page->show();
    	$ary_template_data = D('Gyfx')->selectAll('thd_logistic_template','lt_name,lt_template_id,lt_shop_id,lt_address', $ary_where,null,null,array('page_no'=>$page_no,'page_size'=>$page_size));
    	$ary_condition = array();
    	$ary_condition['u_id'] = array('neq','0');
		$ary_shops =  D('Gyfx')->selectAll('thd_shops','ts_sid,ts_title',$ary_condition); 
    	$this->assign("page", $page);
    	$this->assign('ary_shops',$ary_shops);
    	$this->assign('ary_data',$ary_data);
    	$this->assign('ary_template_data',$ary_template_data);
    	$this->display();
    }
    
    /**
     * 下载物流模板
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2013-10-23 13:08:08
     */
    public function downDeliveryTemplate(){
    	$ary_data = $this->_get();
    	if(!isset($ary_data['shop_source']) || empty($ary_data['shop_source'])){
    		$this->error("请选择您要下载物流模板的店铺信息。");
    	}
    	//去淘宝搜索
    	$access_token = D('ThdShops')->getAccessToken(array('ts_sid'=>$ary_data['shop_source']),'ts_shop_token');
    	//获得第三方店铺ID
    	$taobao_obj = new TaobaoApi($access_token);
    	$array_result = $taobao_obj->getDeliveryTemplatesByUser();
    	//模板数据
    	$template_data = $array_result['delivery_templates_get_response']['delivery_templates']['delivery_template'];
    	if(empty($template_data)){
    		$this->error("未找到模板数据。");
    	}
    	$return_result = $this->saveDeliveryTemplates($ary_data['shop_source'], $template_data);
    	if($return_result == true){
    		$this->success("下载淘宝物流模板成功,共下载".$array_result['delivery_templates_get_response']['total_results']."条",U("Admin/Distirbution/taobaoSetSynRules"));
    		exit;
    	}
    	//删除失败。
    	$this->error("下载淘宝物流模板失败，请重试！");
    }
    
    /**
     * 保存物流模版信息入库
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-25
     * @param int $shop 淘宝店铺编号
     * @param array $ary_data 物流模版信息数组
     * @return
     */
    public function saveDeliveryTemplates($shop, $ary_data) {
    	$template_obj = D("ThdLogisticTemplate");
    	$template_obj->startTrans();
    	//先删除此店铺物流模板
    	$del_obj = $template_obj->where(array('lt_shop_id'=>$shop))->delete();
    	foreach ($ary_data as $k => $v) {
    		//edit by wangguibin@2012-10-25 ++++++++++++++++++++++++++++++++++++++++++++++
    		//此处增加一个判断，如果是支持货到付款的物流模版，或者是支持刷卡的物流模版，不进行保存
    		//否则再向第三方店铺铺货时，可能造成铺货不成功
    		if ($v['supports'] != '')
    			break;
    		//end of edit ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    		//处理物流模板信息
    		$ary_insert_info = array();
    		$ary_insert_info['lt_assumer'] = $v['assumer'];
    		$ary_insert_info['lt_fee_list'] = json_encode($v['fee_list']);
    		$ary_insert_info['lt_name'] = $v['name'];
    		$ary_insert_info['lt_supports'] = $v['supports'];
    		$ary_insert_info['lt_template_id'] = $v['template_id'];
    		$ary_insert_info['lt_valuation'] = $v['valuation'];
    		$ary_insert_info['lt_shop_id'] = $shop;
    		if($v['consign_area_id']){
    			$ary_insert_info['lt_consign_area_id'] = $v['consign_area_id'];
    		}
    		if($v['address']){
    			$ary_insert_info['lt_address'] = $v['address'];
    		}
    		$ary_insert_info['lt_json_data'] = json_encode($v);
    		$res = $template_obj->add($ary_insert_info);
    		if(!$res){
    			$template_obj->rollback();
    			return false;
    		}
    	}
    	$template_obj->commit();
    	return true;
    }
    
    /**
     * 淘宝铺货设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-29
     * @param int $shop 淘宝店铺编号
     * @param array $ary_data 物流模版信息数组
     * @return
     */
    public function taobaoSet() {
    	$this->getSubNav(6, 4, 60);
    	$ary_data = D('SysConfig')->getCfgByModule('TAOBAO_SET');
    	$this->assign($ary_data);
    	$this->display();
    }
    
    /**
     *处理淘宝铺货设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-29
     */
    public function doSet(){
    	$ary_post = $this->_post();
		$ary_post['FX_TAOBAO_KEY'] = '淘宝授权key'.'-'.$ary_post['FX_TAOBAO_KEY'];
		$ary_post['FX_TAOBAO_SECRET'] = '授权密码'.'-'.$ary_post['FX_TAOBAO_SECRET'];
		$ary_post['TAOBAO_REQUEST_URL'] = '淘宝无签名方式调用'.'-'.$ary_post['TAOBAO_REQUEST_URL'];
    	$SysSeting = D('SysConfig');
    	$SysSeting->startTrans();
		//echo "<pre>";print_r($ary_post);die;
    	//允许更新的数据
    	$ary_allow_fields = array (
				'TAOBAO_SET_CATEGORY',//是否使用淘宝分类
				'TAOBAO_SET_BRAND',//是否使用淘宝品牌
				'TAOBAO_SET_NEW',//是否新品
				'TAOBAO_SET_HOT',//是否热销
				'TAOBAO_SET_SALE',//是否上架销售
				'TAOBAO_SET_PRESALE',//是否预售
				'TAOBAO_SET_FRESH',//是否更新商品数据
				'TAOBAO_SET_DOWN_UNSALE',//是否下载非销售属性
				'TAOBAO_SET_DELIVER',//是否允许上传物流模版
				'TAOBAO_SET_DELIVERDEFAULT',//分销商铺货默认物流模板设置
				'TAOBAO_SET_PRICE',//是否允许分销商同步库存时额外同步价格
				'TAOBAO_SET_KEY',//是否允许淘宝key授权
				'FX_TAOBAO_KEY',//淘宝授权key
				'FX_TAOBAO_SECRET',//授权密码
				'TAOBAO_REQUEST_URL'//淘宝无签名方式调用
				
		);
    	foreach($ary_post as $key=>$value){
    		//判断是否是允许更新的数据
    		if(in_array($key,$ary_allow_fields)){
    			$set_info = explode('-',$value);
    			$res = $SysSeting->setConfig('TAOBAO_SET', $key, $set_info[1], $set_info[0]);
    			if(!$res){
    				$SysSeting->rollback();
    				$this->error('保存失败');
    			}
    			unset($set_info);
    		}
    	}
    	$SysSeting->commit();
    	$this->success('保存成功');
    }
    
    /**
     * 默认此店铺物流模板
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-11-20
     */
    public function doEditDistirbutionDefault(){
    	$ary_post = $this->_post();
    	if(!empty($ary_post['id']) && intval($ary_post['id'])>0){
    		$level = D("ThdShops");
    		$ary_data = array(
    				'ts_default'    =>'0'
    		);
    		$where['ts_id'] = array('neq',$ary_post['id']);
    		$res_update = $level->where($where)->data($ary_data)->save();
    		$res_update1= $level->where('ts_id='.$ary_post['id'])->data(array('ts_default'=>'1'))->save();
    		if(FALSE !==$res_update&&FALSE !==$res_update1){
    			$this->success("设置默认店铺物流模板成功");
    		}else{
    			$this->error("设置默认店铺物流模板失败");
    		}
    	}else{
    		$this->error("店铺ID不能为空");
    	}
    }
}
