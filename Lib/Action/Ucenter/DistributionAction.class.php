<?php

/**
 * 铺货中心控制器
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class DistributionAction extends CommonAction {

    /**
     * 控制器初始化方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 控制器默认页，默认跳转到店铺管理页面
     * @auther zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function index() {
        $this->redirect(U('Ucenter/Distribution/thdPageShops'));
    }
	
	/**
     * 第三方店铺管理页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     */
    public function thdPageShops() {
        $this->getSubNav(2, 1, 90);
        $page_size = 20;
        $member = session("Members");
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $this->getSeoInfo('-店铺授权列表');
        $ary_where=array('m_id'=>$member['m_id']);
        $ary_order=array('ts_modified'=>'desc');
        $data = D("ThdShops")->getThdShop($ary_where,'',$ary_order);
        $count = D("ThdShops")->getThdShopCount($ary_where);
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign("shops",$data);
        $this->assign("page", $page);
        $this->display();
    }
	
	/**
     * 店铺申请授权
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-21
	 * @author wang <wangguibin@guanyisoft.com>
	 * @update 京东平台对接
	 * @date 2015-03-10
     */
    public function AddThdShop() {
		$shop_type = $this->_request('type');
    	$callback_url = "http://";
    	$callback_url .= $_SERVER["HTTP_HOST"];
    	if(80 != $_SERVER["SERVER_PORT"]){
    		$callback_url .= ':' . $_SERVER["SERVER_PORT"];
    	}
    	$callback_url .= '/' . trim(U("Ucenter/Distribution/callback",array('type'=>$shop_type)),'/');
		switch($shop_type){
			case 3:
			$taobao_obj = new Jd();
			break;
			default:
			$taobao_obj = new TaobaoApi();
			break;
		}
		$taobao_obj->topOauth($callback_url);
    }
	
    /**
     * 淘宝供销平台对接，授权完成回跳页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-21
     */
    public function callback(){
    	//判断是前台还是后台
   	    $is_exist = strpos($_SERVER['PHP_SELF'],'dmin/Distirbution/callback');
    	$redirt_url = U("Ucenter/Distribution/thdPageShops");
    	$array_data = $_GET;
		switch($array_data['type']){
			case 3:
			$taobao_obj = new Jd();
			break;
			default:
			$taobao_obj = new TaobaoApi();
		}
    	$taobao_obj->callback($array_data,$is_exist,$redirt_url);
    }
    
    /**
     * 删除授权店铺
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-21
     */
    public function doDelThdShop(){
        $int_ts_id = $this->_get('id','htmlspecialchars',0);
        if(!$int_ts_id){
            $this->error('授权店铺参数错误');
        }  else {
            //删除店铺
            $ary_where=array('ts_id'=>(int)$int_ts_id);
            $int_result = D('ThdShops')->delThdShop($ary_where);
            if(false===$int_result){
                $this->error('删除店铺授权失败');
            }else{
                $this->redirect(U('Ucenter/Distribution/thdPageShops'));
            }
        }
    }
    
    /**
     * 铺货商品列表页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     */
    public function showGoodsList() {
        $this->getSubNav(2, 1, 100);
        $this->getSeoInfo('-淘宝店铺授权商品列表');
		$goods = D('ViewGoods');
        $page_size=10;
        $member = session("Members");
        $params=$this->_param();
        $p = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        //获取授权线分类及品牌
        $ary_where=array('m_id'=>$member['m_id']);
        $res = D("RelatedAuthorizeMember")->GetAuthorizeData($ary_where,'',$group);
		$brand=array();
        $category=array();
        foreach($res as $val){
            if($val['ra_gb_id']>0 && !array_search($val['ra_gb_id'],$brand)){
                array_push($brand,$val['ra_gb_id']);
            }
            if($val['ra_gc_id']>0 && !array_search($val['ra_gb_id'],$category)){
                array_push($category,$val['ra_gc_id']);
            }
        }
		$search['cates'] = $goods->getCates(0,$is_cache=1);
        //获取可铺货商品
        $group='fx_goods.g_id';
        $ary_field='fx_thd_goods.thd_goods_sn,fx_thd_goods.thd_goods_id,fx_goods.g_id,fx_goods.g_sn,fx_goods_info.g_name,
			 fx_goods_info.g_picture';
		//关联查询
		$ary_join_where = array();
		$ary_join_where[] = 'fx_goods_info ON fx_goods_info.g_id = fx_goods.g_id';
		$ary_join_where[] = 'fx_thd_goods ON fx_thd_goods.thd_goods_sn = fx_goods.g_sn';
		//上架商品
		$ary_condition['fx_goods.g_on_sale']=array('eq',1);
		//关联第三方表
		$ary_condition['fx_thd_goods.thd_goods_sn']=array('exp',' is not null');
		//授权条件
        if(!empty($brand) && !empty($category)){//授权线品牌和分类是or 条件
			$ary_condition['fx_goods.gb_id|fx_related_goods_category.gc_id']=array(array('in',$brand),array('in',$category),'_multi'=>true);
        }else{
			if(!empty($brand)){
				$ary_condition['fx_goods.gb_id']=array('in',$brand);
			}
			if(!empty($category)){
				$ary_condition['fx_related_goods_category.gc_id']=array('in',$category);
			}
		}
		if(!empty($brand)){
			$ary_join_where[] = ' fx_goods_brand ON fx_goods_brand.gb_id = fx_goods.gb_id';
		}
		if(!empty($category) || !empty($params['search_gcid'])){
			$ary_join_where[] = ' fx_related_goods_category ON fx_related_goods_category.g_id = fx_goods.g_id';
			$ary_join_where[] = ' fx_goods_category ON fx_goods_category.gc_id = fx_related_goods_category.gc_id';
		}
		//条件筛选条件
        if(!empty($params['g_name'])){
            $ary_condition['fx_goods_info.g_name']=array('like','%'.trim($params['g_name']).'%' );
        }
        if(!empty($params['search_g_sn'])){
            $ary_condition['fx_goods.g_sn']=array('eq',trim($params['search_g_sn']) );
        }
		if(!empty($params['search_gcid'])){
			$ary_condition['fx_goods_category.gc_id']=array('eq',$params['search_gcid']);
		}
		if($params['status']==1){//已铺货
			$ary_condition['fx_thd_upload_tmp.thd_item_id']=array('exp',' is not null');
		}
        if($params['status']==2){//未铺货
			$ary_condition['fx_thd_upload_tmp.thd_item_id']=array('exp',' is null');
		}
        $count  = D("Goods")->getAuthorizeLineGoodCount($ary_condition,$group,$ary_join_where);
        $obj_page = new Page($count, $page_size);
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        //$ary_join_where[] = ' fx_thd_upload_tmp ON fx_thd_upload_tmp.thd_item_id = fx_thd_goods.thd_goods_id';
        $ary_field .=',fx_thd_goods.thd_goods_id as thd_item_id';//,fx_thd_upload_tmp.tut_create_time

        $result = D("Goods")->getAuthorizeLineGoodData($ary_condition,$ary_field,$group,$limit,$ary_join_where,true);
		foreach($result as $key=>&$value){
			if(!empty($value['thd_item_id'])){
				$shop_history_where=array('thd_item_id'=>$value['thd_item_id']);
				$shop_history_field=array('thd_shop_sid,last_upload_time');
				$value['history']=D("ThdUploadTmp")->getItemShopRecord($shop_history_where,$shop_history_field);
			}
            $str_field = 'max(pdt_price_up) as pdt_price_up,min(pdt_price_down) as pdt_price_down';
           // $array_range = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field($str_field)->where(array('g_id'=>$value['g_id']))->find();
			$array_range = D('Gyfx')->selectOneCache('goods_products',$str_field,array('g_id'=>$value['g_id']));
            $result[$key]['price_down'] = (isset($array_range['pdt_price_down']) && !empty($array_range['pdt_price_down'])) ? $array_range['pdt_price_down'] : '';
            $result[$key]['price_up']  = (isset($array_range['pdt_price_up']) && !empty($array_range['pdt_price_up'])) ? $array_range['pdt_price_up'] : '';
		}
		$ary_shop_where=array('m_id'=>$member['m_id'],'ts_source'=>array('neq',3));
        $ary_shop_field=array('ts_sid,ts_title');
		$ary_shop_order=array('ts_modified'=>'desc');
        $ary_shop_res = D("ThdShops")->getThdShop($ary_shop_where,$ary_shop_field,$ary_shop_order);
		
        $page = $obj_page->show();
        $this->assign('params',$params);
		$this->assign('search', $search);
        $this->assign('datas',$result);
		$this->assign('shops',$ary_shop_res);
        $this->assign('page', $page);
        $this->display();
    }
    
    /**
     * 淘宝商品铺货上传
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
     */
    public function ajaxUploadItem() {
		@set_time_limit(0);  
        @ignore_user_abort(TRUE); 
	    $member = session("Members");
        $params=$this->_param();
        if (!isset($params['shop']) || $params['shop'] == '') {
            $this->error(L('Please select shop'));
            return false;
        }
        if (!isset($params['thd_item_id']) || $params['thd_item_id'] == '' ) {
            $this->error(L('Please select goods'));
            return false;
        }
        
        $has_invoice = ($params['has_invoice']=='1')?true:false;
        $rebate_point = (int)$params['rebate_point']; //0为没有返点 1为按供货商配置 2为0.5%
        $token_data=D('ThdShops')->getThdShop(array('ts_sid'=>$params['shop'],'m_id'=>$member['m_id']),array('ts_shop_token'));
		
        if(empty($token_data)){
            $this->error(L('用户没有登入或超时,请再次登入'));
            return false;
        }else{
			$ary_token=array_shift($token_data);
			$ary_access_token=json_decode($ary_token['ts_shop_token'],true);
		}
        //上传全部的物流模版
		$deliver_config = D('SysConfig')->getCfgByModule('TAOBAO_SET');
		if($deliver_config['TAOBAO_SET_DELIVER']){
			$res_template=D('ThdLogisticTemplate')->uploadDeliveryTemplates($ary_access_token['access_token'],$params['shop']);
			
			if(!$res_template['status'] && empty($res_template['status'])){
				$file_name=date('Ymd').'logistictemplate.log';
				writeLog($res_template['message'],$file_name);
			}
		}
		$has_freight = (int)$deliver_config['TAOBAO_SET_DELIVERDEFAULT'];  //0卖家包邮  1采用供货商配置  2采用淘宝默认平邮/快递/EMS
        //上传选择的商品
        $thd_item_id = urldecode($params['thd_item_id']);
        $thd_item_id = substr($thd_item_id, 0, strlen($thd_item_id) - 1);
        $ary_thd_item_id = explode(',', $thd_item_id);
		$str_res = '<table class="result_Tb" border="0" cellspacing="0" cellpadding="0">
						<tr><th width="225px">商品名称</th><th width="225px">状态</th></tr>';
        foreach ($ary_thd_item_id as $key => $val) {
            $ary_datas=array(
				'has_freight'=>$has_freight,
				'has_invoice'=>$has_invoice,
				'rebate_point'=>$rebate_point,
				'access_token'=>$ary_access_token['access_token'],
				'shop_code'=>$params['shop']
            );
            $res = D('ThdGoods')->uploadTopItem($val,$ary_datas);
			$str_res = $str_res . '<tr><td style="text-align:center;width=225px;">' . $res['g_name'] . '</td>
										<td style="text-align:center;width=225px;">' . $res['msg'] . '</td><td></tr>';
        }
        $message = $str_res . '</table>';
        echo json_encode(array('result' => true, 'message' => $message));
        exit;
    }
    
    /**
     * 铺货历史记录页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
     */
    public function HistroyList() {
		$member = session("Members");
		$params=$this->_param();
        //echo'<pre>';print_r($params);die;
		$page_size = 20;
		$p = isset($params['p']) ? (int) $params['p'] : 1;
		$ary_field=array(
			'fx_thd_shops.ts_title,fx_thd_upload_tmp.tut_create_time,
			 fx_thd_upload_tmp.tut_update_time,fx_goods_products.g_sn,
			 fx_thd_upload_tmp.thd_item_id,fx_goods_info.g_name'
		);
        //条件筛选条件
        if(!empty($params['g_name'])){
            $ary_where['fx_goods_info.g_name']=array('like','%'.trim($params['g_name']).'%' );
        }
        if(!empty($params['search_g_sn'])){
            $ary_where['fx_goods.g_sn']=array('eq',trim($params['search_g_sn']) );
        }
		$ary_where['fx_thd_shops.m_id']=$member['m_id'];
		$count = D('ThdUploadTmp')->getHistroyRecordCount($ary_where);
		$obj_page = new Page($count, $page_size);
		$limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
		$group='fx_thd_upload_tmp.thd_item_id,fx_thd_shops.ts_sid';
		$order=array('fx_thd_upload_tmp.tut_update_time'=>'asc');
        //echo'<pre>';print_r($ary_where);die;
		$result = D('ThdUploadTmp')->getHistroyRecord($ary_where,$ary_field,$group,$order,$limit);
		
		$page = $obj_page->show();
		$this->assign('datas',$result);
		$this->assign('params',$params);
        $this->assign('page', $page);
        $this->display();
    }
	
	/**
     * 删除铺货历史记录
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-29
     */
    public function ajaxDelHistroy() {
		$str_ids=$this->_param('ids');
        $str_ids = rtrim($str_ids, ',');
		$ary_where['thd_item_id']  = array('in',$str_ids);
		$result = D('ThdUploadTmp')->delItemRecord($ary_where);
        
        if ($result) {
            echo json_encode(array('result' => true));
        } else {
            echo json_encode(array('result' => false));
        }
        exit;
    }

    /**
     * 店铺管理页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function pageShops() {
        $this->getSubNav(2, 2, 10);
        $this->getSeoInfo('-店铺授权列表');
        //获取店铺列表
        $shops = D("ThdShops");
        $data['shops'] = $shops->order(array('ts_modified'=>'desc'))->select();
        //显示页面
        $this->assign($data);
        $this->display();
    }

    /**
     * 向中心化服务器申请授权:1为淘宝2为拍拍
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function synAddShop() {
        //淘宝与拍拍的流程不一样
        //淘宝是直接向中心化服务器请求授权，拍拍是生成添加授权的ajax弹框
        $platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        switch ($platform) {
            //拍拍处理流程
            case 'paipai':
                $this->redirect(U('Ucenter/Distribution/pageAddPaipai'));
                break;
            case 'jd':
				$callback_url = "http://";
				$callback_url .= $_SERVER["HTTP_HOST"];
				if(80 != $_SERVER["SERVER_PORT"]){
					$callback_url .= ':' . $_SERVER["SERVER_PORT"];
				}
				$callback_url .= '/' . trim(U("Ucenter/Distribution/doAddShopJd",array('type'=>'3')),'/');
				$jd_obj = new Jd();
				$jd_obj->topOauth($callback_url);
				break;
            //默认淘宝处理流程
            default:
                $url = C('FX_TAOBAO_CENTER');
                $url .= '?act=create';
                $url .= '&callback=';
                $url .= rawurlencode(U('Ucenter/Distribution/doAddShop', array('pf' => 'taobao'), '', false, true));
                redirect($url);
                break;
        }
    }

    /**
     *
     */
    public function pageAddPaipai(){
        $this->getSubNav(2, 2, 10);
        $ary_get = $this->_get();
        $this->display();
    }

    /**
     * 授权页，授权后进入授权列表(京东授权)
     * @return mixed array
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.8.2
     * @add 2015-03-17
     */
    public function doAuthJd() {
    	$redirt_url = U("Ucenter/Trdorders/yunerpShop");
    	$array_data = $_GET;
		$jd_obj = new Jd();
    	$jd_obj->callback($array_data,$is_exist=false,$redirt_url);
    }
	
    /**
     * 新增店铺授权，保存店铺授权
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function doAddShop() {
        $obj_shops = D("ThdShops");
        //根据店铺授权请求店铺基本信息
        $str_platform = $this->_get('pf', 'htmlspecialchars', 'taobao');
        $ary_token = $this->_get();
        $obj_api = Apis::factory($str_platform,$ary_token);
        $str_shop_result = $obj_api->getShopInfo(array('nick'=>  rawurldecode($ary_token['taobao_user_nick'])));
        $str_seller_result = $obj_api->getSellerInfo();
        //保存店铺的基本信息及授权信息
        $ary_member = session('Members');
        $bln_result = $obj_shops->saveShop($ary_token, $str_shop_result, $str_seller_result, $str_platform, $ary_member['m_id']);
        //echo "<pre>";print_r($bln_result);exit;
        if($bln_result){
           // echo $str_platform;exit;
            //$str_platform 作为来源平台的 code 请保持唯一性。
            $ary_sp = D('SourcePlatform')->where(array('sp_code'=>$str_platform))->find();
            
            //echo D('SourcePlatform')->getLastSql();exit;
            if(!empty($ary_sp)){
                $ary_rsp = D('RelatedMembersSourcePlatform')->where(array('sp_id'=>$ary_sp['sp_id'],'m_id'=>$ary_member['m_id']))->find();
                if(empty($ary_rsp)){
                    $res_rsp=D('RelatedMembersSourcePlatform')->add(array('sp_id'=>$ary_sp['sp_id'],'m_id'=>$ary_member['m_id']));
                    if(!$res_rsp){
                        $this->error('店铺授权失败', U('Ucenter/Distribution/pageShops'));
                    }
                }
            }
            $this->success('店铺授权成功', U('Ucenter/Trdorders/yunerpShop'));
        }else{
            //$this->show('hello');
            $this->error('店铺授权失败', U('Ucenter/Distribution/pageShops'));
        }

    }

    /**
     * 选择店铺页面，铺货前必须选择页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-20
     */
    public function pageSelect(){
        $this->getSubNav(2, 2, 20);
        $this->getSeoInfo('-选择店铺');
        //获取数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //获取回调地址
        $str_back = $this->_get('back','htmlspecialchars','pageList');
        $data['back_url'] = U('Ucenter/Distribution/'.$str_back);
        //拿到该用户淘宝的授权店铺（暂只支持淘宝铺货）
        $data['shops'] = D('ThdShops')->where(array('ts_source'=>1,'m_id'=>$this->ary_member['m_id']))->select();

        //显示页面 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 铺货商品列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function pageList() {
        $this->getSubNav(2, 2, 20);
        $this->getSeoInfo('-铺货页面');
        //获取数据
        //如果没有选择店铺 则引导到选择页面 +++++++++++++++++++++++++++++++++++++++
        $int_sid = $this->_get('sid','htmlspecialchars',0);
        if(!$int_sid){
            $this->redirect(U('Ucenter/Distribution/pageSelect',array('back'=>'pageList')));
        }

        $obj_goods = D('ThdGoods');
        $data['list'] = $obj_goods->select();
        //显示页面
        $this->assign($data);
        $this->display();
    }

    /**
     * 铺货历史页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function pageHistory() {

    }

    /**
     * 待更新的淘宝货品列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function pageUpdate() {

    }

    /**
     * 删除店铺授权
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-20
     */
    public function doDelShop(){
        $int_ts_id = $this->_get('id','htmlspecialchars',0);
        if(!$int_ts_id){
            $this->error('店铺参数错误');
        }  else {
            //删除店铺
            $obj_shop = D('thdShops');
            $mix_result = $obj_shop->where(array('ts_id'=>(int)$int_ts_id))->delete();
            if(false===$mix_result){
                $this->error('删除店铺授权失败');
            }else{
                $this->redirect(U('Ucenter/Distribution/pageShops'));
            }
        }
    }
    
    /**
     * 拍拍授权添加帮助
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-21
     */
    public function pagePaipaiHelp(){
        $this->getSubNav(2, 2, 10);
        $this->display();
    }
    
    /**********************************************************************/
    /**
     * 显示单个更新商品的列表，此处提供一个列表让分销商有选择的去更新
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-10-12
     */
    public function doSynList() {
        
        //实例化淘宝接口类
        $shopID = $this->_post('shopID','trim',0);
        //var_dump($shopID);exit;
        /************************************************/
         $obj_thd_shops = D("ThdShops");
        //$m_id = D("ThdTopItems")->getMemberId();
      
        $ary_return = array();
        $ary_where = array('ts_id'=>$shopID,'ts_source'=>1);
		$access_token = $obj_thd_shops->getAccessToken($ary_where,'ts_shop_token,ts_nick',$ary_return);//返回淘宝api对象
	    layout(false);
        if(empty($access_token)) {
            
            echo '请先去授权';exit;
        }
		$app = new TaobaoApi($access_token);//api对象
        /***********************************************/
        
        $res_data = array();
        ######根据用户信息获取用户店铺内的在架商品##################################
        $page_no = 1;
        $list = array();
        do {
            $app_data = $app->getCurrentUserOnSaleGoods(array('page_no' => $page_no, 'order_by' => 'num:asc'));
            //print_r($app_data);exit;
            $page_no++;
            $list = array_merge($list, $app_data['data']);
        } while ($page_no <= ceil($app_data['num'] / 40));
      
        $data['data'] = $list;
        
        //这里一次只能获取40条，需要根据页数，分批获取

        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['info'] = $app->getSingleGoodsInfo($v['num_iid']);
            $data['data'][$k]['info']['data']['desc'] = '';
        }
        // print_r($data['data']);exit;
       $obj_goods_products = D("GoodsProducts");

        ######根据用户的在架商品在本地进行查找,匹配到有商家编码的#####################
        foreach ($data['data'] as $k => $v) {
            if (!empty($v['outer_id']) && !isset($v['info']['data']['skus'])) {
                //如果商品本身有商家编码的，并且没有货品sku信息的。判断为单规格商品
                //根据商家编码去货品表查找价格和库存
                list($status, $price, $store, $g_id, $g_sn, $pdt_sn) = $obj_goods_products->getProductInfo($v['outer_id']);
               // if ($status) {
                    $res_data[] = array(
                        'hasSku' => false,
                        'title' => $v['title'],
                        'num_iid' => $v['num_iid'],
                        'price_old' => $v['price'],
                        'price_new' => $price,
                        'store_old' => $v['num'],
                        'store_new' => $store,
                        'g_id' => $g_id,
                        'g_sn' => $g_sn,
                        'pdt_sn' => $pdt_sn
                        
                    );
               // } 
            } elseif (is_array($v['info']['data']['skus'])) {
                //如果有sku信息，根据sku的数组再做判断
                foreach ($v['info']['data']['skus']['sku'] as $sku) {
                    //有商家编码的货品，去本地根据商家编码查找价格和库存
                    if (!empty($sku['outer_id'])) {
                        list($status, $price, $store, $g_id, $g_sn, $pdt_sn) = $obj_goods_products->getProductInfo($sku['outer_id']);
                        
                            $res_data[] = array(
                                'hasSku' => true,
                                'title' => $v['title'],
                                'subTitle' => $obj_goods_products->filterSubTitle($sku['properties_name']),
                                'properties' => $sku['properties'],
                                'num_iid' => $v['num_iid'],
                                'sku_id' => $sku['sku_id'],
                                'price_old' => $sku['price'],
                                'price_new' => $price,
                                'store_old' => $sku['quantity'],
                                'store_new' => $store,
                                'g_id' => $g_id,
                                'g_sn' => $g_sn,
                                'pdt_sn' => $pdt_sn
                            );
                     
                    } else {
                        //没有商家编码的货品，不做处理
                    }
                }
            }
        }
        //按本地库存数量升序排序
       // $res_data = array_sort($res_data, 'store_new');
        //print_r($res_data);exit;
        
        
        $this->assign('shopID',$shopID);
        $this->assign('goods_list',$res_data);
        
		echo $this->fetch('getProductsInfo');exit;
        
    }
    
    
    /**
     * 向第三方接口更新一件商品或者货品的价格个库存
     * 如果是商品，需要淘宝商品ID、库存、价格
     * 如果是货品，需要淘宝商品ID、淘宝货品属性值字串、库存、价格
     */
    public function doSynOne(){
        $obj_thd_shops = D("ThdShops");
        //是否为多规格商品
        $hasSku = $this->_post('hasSku','intval',0);
        //同步库存时是否需要包含价格
        $hasPrice = $this->_post('hasPrice');
        $hasStore = $this->_post('hasStore');
        
        $ary_where = array('ts_id'=>$this->_post('shopID'),'ts_source'=>1);
		$access_token = $obj_thd_shops->getAccessToken($ary_where,'ts_shop_token,ts_nick');//返回淘宝api对象
	    if(empty($access_token)) {
            echo json_encode(array('status'=>false,'err_code'=>53));
            exit;
           
        }
		$app = new TaobaoApi($access_token);//api对象
        
        
        if(!$hasSku){
            ##################更新单商品########################################
            $data = array(
                'num_iid'=>$this->_post('num_iid')
            );
            
            if($hasStore) {
                $data['num'] = $this->_post('store');
            }
            
            if($hasPrice){
                $data['price'] = sprintf('%.2f',$this->_post('price'));
            }
            $res = $app->updateItem($data);
            echo json_encode($res);
            exit;
        }else{
            ##################更新单货品########################################
            $data = array(
                'num_iid'=>$this->_post('num_iid'),
                'properties'=>$this->_post('properties')
                //'quantity'=>$this->_post('store')
            );
            
            if($hasPrice){
                $data['quantity'] = $this->_post('store');
            }
            
            if($hasPrice){
                $data['price'] = sprintf('%.2f',$this->_post('price'));
                $data['item_price'] = sprintf('%.2f',$this->_post('price'));
            }
            $res = $app->updateSkuToThd($data);
            echo json_encode($res);
            exit;
        }
    }

}
