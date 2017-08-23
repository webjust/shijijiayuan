<?php

/**
 * 京东API接口
 *
 * @package Common
 * @subpackage Api
 * @stage 7.8.2
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-03-10
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class Jd implements IApis {

    private $app_session = "";
	private $url_api = '';

    /**
     * 构造函数
     * @author wang <wangguibin@guanyisoft.com>
     * @date 2015-03-10
     * @param array $token 从中心化服务器获取来的token数据
     */
	public function __construct($str_access_token) {
        $this->app_session = $str_access_token;
		$this->url_api = C('JD_REQUEST_URL');
    }

    /**
     * 发送taobao API请求
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-03-10
     * @param string $str_method 请求的API方法
     * @param array $array_request 请求的参数数组
     */

	public function requestAPI($str_method, $array_request, $is_ajax = false) {
		if(!empty($array_request)){ ksort($array_request); 
			$array_request = json_encode($array_request); 
		}else{$array_request = '{}';}	
    	$array_params = $this->createRequestParams($str_method, array('360buy_param_json'=>$array_request));
		$top_obj=new Communications();
	    $array_result=$top_obj->httpPostRequest($this->url_api, $array_params, array(), false);
    	$array_result = preg_replace('/\:(\d+)([,}]+)/', ':"$1"$2', $array_result);
    	$array_result = json_decode($array_result, true);
    	return $array_result;
    }
	
	/**
     * 创建请求参数
     * @author Wangguibin <Wanguibin@guanyisoft.com>
     * @date 2015-03-10
     * @version 7.8.2
     * @param string $str_method 请求的API方法
     * @return array $array_params 请求的参数数组
     */

	public function createRequestParams($str_method, $array_params) {
        $array_params['method'] = $str_method;
		$array_params['app_key'] = C('JD_GY_APPKEY');
        $array_params['access_token'] = $this->app_session;
        $array_params['format'] = 'json';
        $array_params['v'] = '2.0';
        $array_params['timestamp'] = date("Y-m-d H:i:s");	
		$array_params['sign'] = $this->createSign($array_params,C('JD_GY_SECRET'));		
        return $array_params;
    }	
	/**
	 *
	 * Enter 签名函数
	 * @param  $paramArr
	 */

	protected function createSign ($paramArr,$app_secret) {
		$sign = $app_secret;
		ksort($paramArr);
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val != '') {
				$sign .= $key.$val;
			}
		}
		$sign.=$app_secret;
		//dump($sign);die();
		$sign = strtoupper(md5($sign));
		return $sign;
	}	
    /**
     * 京东供销平台对接，授权绑定请求发起页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-03-10
     */
	 public function topOauth($callback_url){
    	//构造上传参数
    	$array_params = array();
    	$array_params["callback"] = base64_encode($callback_url);
    	//新建授权验证字段
    	$array_params["act"] = 'create';
    	//301 跳转到收取管理中心
    	$center_url = C('FX_JD_CENTER');
    	header("location:" . $center_url . "?" . http_build_query($array_params));
    	exit;
    }
    /**
     * 京东供销平台对接，授权完成回跳页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-03-10
     */
    public function callback($array_data,$is_exist,$redirt_url){
		//测试数据
		/**
		$array_data = array(
			'type'=>'3',
			'access_token'=>'e2a261ad-d303-4884-9074-fc6ffeac4774',
			'code'=>'0',
			'expires_in'=>'86400',
			'refresh_token'=>'8d6e760d-77f1-4d4f-8724-09600f1bdd97',
			'time'=>'1426051152546',
			'token_type'=>'bearer',
			'uid'=>'2004612155',
			'user_nick'=>'庄煜炜1'
		);
		**/
    	if(!isset($array_data['user_nick'])){
    		$this->error("授权出错！",$redirt_url);
    		exit;
    	}
    	//处理数据
    	$access_data = array(
    			'top_user_id'=>$array_data['uid'],
    			'top_user_nick'=>urldecode($array_data['user_nick']),
    			'top_access_token'=>$array_data['access_token'],
    			'top_expires_in'=>$array_data['expires_in'],
    			'top_refresh_token'=>$array_data['refresh_token'],
    			//'top_refresh_expires_in'=>'',
				'top_type'=>'3',
				'token_type'=>$array_data['token_type'],
    			'top_oauth_time'=>date("Y-m-d H:i:s")
    	);
    	unset($array_data);
    	//初始化表
    	$access_obj = M('top_access_info', C('DB_PREFIX'), 'DB_CUSTOM');
    	//判断授权是否存在
    	$res = $access_obj->add($access_data,array(),true);
    	if(!$res){
    	//保存用户授权
    		$this->error("保存用户授权信息出错！",$redirt_url);
    		exit;
    	}
    	//存取店铺信息
    	if($res){
    		$taobao_obj = new Jd($access_data['top_access_token']);
    		$str_shop_info_json = $taobao_obj->getShopInfo(array('nick' => $access_data['top_user_nick']));
			//dump($str_shop_info_json);die();
    		$ts_sid = $str_shop_info_json['jingdong_vender_shop_query_responce']['shop_jos_result']['shop_id'];
    		$str_shop_info_json = json_encode($str_shop_info_json);
			$str_seller_info_json = json_encode($access_data);
    		//把店铺信息保存起来：taobao.shop.get
    		if($is_exist){
    			$int_uid = $_SESSION['Admin'];
    		}else{
    			$int_mid = $_SESSION['Members']['m_id'];
    		}
    		$res = D('ThdShops')->saveShop(array('access_token'=>$access_data['top_access_token']), $str_shop_info_json, $str_seller_info_json, $str_pf = 'jingdong', $int_mid, $int_uid);
    		if(!$res){
				// 新增店铺信息失败
				$this->error ( "保存店铺信息出错！", $redirt_url );
				exit ();
    		}
    	}
    	//301到授权成功页面
    	header("location:" . $redirt_url);
    	exit;
    }
    /**
     * 搜索当前会话用户作为卖家已卖出的交易数据[tradesSoldGet]
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2015-3-16
     * @http://jos.jd.com/api/detail.htm?apiName=360buy.order.search&id=393
	 * @
     * @return array 第三方订单信息
     */
    public function getOrdersList($str_create_time, &$int_total_nums, $int_page = 1, $int_size = 1,&$array_data =array()) {
		//$array_result = $this->requestAPI("360buy.order.get", array('order_id'=>'9109879920'));
		$ary_time = explode('---',$str_create_time);
        $ary_params = array(
            'start_date' => $ary_time[0],// 	WAIT_SELLER_STOCK_OUT 等待出库，则start_date可以为否（开始时间和结束时间均为空，默认返回前一个月的订单），order_state为其他值，则start_date必须为是（开始时间和结束时间，不得相差超过1个月。此时间仅针对订单状态及运单号修改的时间） 
			'end_date'=>$ary_time[1],
            'order_state' => 'WAIT_SELLER_STOCK_OUT',//多订单状态可以用英文逗号隔开 1）WAIT_SELLER_STOCK_OUT 等待出库 2）SEND_TO_DISTRIBUTION_CENER 发往配送中心（只适用于LBP，SOPL商家） 3）DISTRIBUTION_CENTER_RECEIVED 配送中心已收货（只适用于LBP，SOPL商家） 4）WAIT_GOODS_RECEIVE_CONFIRM 等待确认收货 5）RECEIPTS_CONFIRM 收款确认（服务完成）（只适用于LBP，SOPL商家） 6）WAIT_SELLER_DELIVERY等待发货（只适用于海外购商家，等待境内发货 标签下的订单） 7）FINISHED_L 完成 8）TRADE_CANCELED 取消 9）LOCKED 已锁定 
			'optional_fields'=>'order_id,order_source,vender_id,pay_type,order_total_price,order_seller_price,order_payment,freight_price,seller_discount,order_state,payment_confirm_time,order_state_remark,delivery_type,invoice_info,order_remark,order_start_time,order_end_time,modified,consignee_info,item_info_list,vender_remark,balance_used,vat_invoice_info,pin,return_order,order_type',//需返回的字段列表,暂时都要。可选值：orderInfo结构体中的所有字段；字段之间用,分隔  
			'page_size' => $int_size,
            'page' => $int_page,
			//'sortType' =>'' //排序方式，默认升序,1是降序,其它数字都是升序 
			//'dateType'=>'' // 查询时间类型，默认按修改时间查询。 1为按订单创建时间查询；其它数字为按订单（订单状态、修改运单号）修改时间  
        );
		//结束时间查询 暂时不处理
		if(!empty($str_end_time)){
			//$ary_params['end_date'] = $str_end_time;//WAIT_SELLER_STOCK_OUT 等待出库，则start_date可以为否（开始时间和结束时间均为空，默认返回前一个月的订单），order_state为其他值，则start_date必须为是（开始时间和结束时间，不得相差超过1个月。此时间仅针对订单状态及运单号修改的时间）  
		}
        $array_result = $this->requestAPI("360buy.order.search", $ary_params);
        if (isset($array_result["order_search_response"]['order_search'])) {
            //$array_data = array();
			$have_next = false;
            $order_total = $array_result["order_search_response"]['order_search']['order_total'];
			$now_count = ($int_page-1)*$int_size+count($array_result["order_search_response"]['order_search']['order_info_list']);
			if($now_count<$order_total){
				$has_next = true;
			}
            if (!empty($array_result["order_search_response"]['order_search']['order_info_list'])) {
                foreach ($array_result["order_search_response"]['order_search']['order_info_list'] as  $array_trade) {
                    $tt_id = number_format($array_trade["order_id"], 0, '', '');
                    $int_key=count($array_data);
                    $array_data[$int_key]["tt_id"] = $tt_id;
                    $array_data[$int_key]["tt_source"] = "3";
                    $array_data[$int_key]["buyer"] = $array_trade["pin"];
                    $array_data[$int_key]["created"] = $array_trade["order_start_time"];
                    $array_data[$int_key]["modified"] = $array_trade["modified"];
                    $array_data[$int_key]["pay_time"] = $array_trade["payment_confirm_time"];
					if($array_trade["pay_type"]){
						$array_data[$int_key]["to_pay_type"] = $array_trade["pay_type"];
					}
                    $array_data[$int_key]["post_fee"] = $array_trade["freight_price"];
                    $array_data[$int_key]["payment"] = $array_trade["order_payment"];
                    $array_data[$int_key]["receiver_address"] = $array_trade['consignee_info']["full_address"];
                    $array_data[$int_key]["receiver_city"] = (string) $array_trade['consignee_info']['city'];
                    $array_data[$int_key]["receiver_district"] = (string) $array_trade['consignee_info']['county'];
                    $array_data[$int_key]["receiver_mobile"] = (string) ($array_trade['consignee_info']['mobile']);
                    $array_data[$int_key]["receiver_phone"] = (string) ($array_trade['consignee_info']['telephone']);
                    $array_data[$int_key]["receiver_name"] = (string) $array_trade['consignee_info']['fullname'];
                    $array_data[$int_key]["receiver_state"] = (string) $array_trade['consignee_info']['province'];
					$array_data[$int_key]["receiver_address"] = str_replace($array_data[$int_key]["receiver_state"],'',$array_data[$int_key]["receiver_address"]);
					$array_data[$int_key]["receiver_address"] = str_replace($array_data[$int_key]["receiver_city"],'',$array_data[$int_key]["receiver_address"]);
					$array_data[$int_key]["receiver_address"] = str_replace($array_data[$int_key]["receiver_district"],'',$array_data[$int_key]["receiver_address"]);
					//注意京东接口直辖市只有两级北京 天津 上海 重庆
					if(in_array($array_data[$int_key]["receiver_state"],array('北京','天津','上海','重庆'))){
						$array_data[$int_key]["receiver_district"] = $array_data[$int_key]["receiver_city"];
						$array_data[$int_key]["receiver_city"] = $array_data[$int_key]["receiver_state"].'市';
					}
                    $array_data[$int_key]["receiver_zip"] = "";
                    $array_data[$int_key]["seller_flag"] = "";
                    $array_data[$int_key]["title"] = "";
                    $array_data[$int_key]["thd_status"] = $array_trade['order_state'];
                    //$ary_thd_trade_detial = $this->getThdTradeDetial(array('tid' => $tt_id));
                    //$array_data[$int_key]["buyer_message"] = isset($ary_thd_trade_detial['data']['buyer_message']) ? $ary_thd_trade_detial['data']['buyer_message'] : '';
                    //$array_data[$int_key]['seller_memo'] = isset($ary_thd_trade_detial['data']['seller_memo']) ? $ary_thd_trade_detial['data']['seller_memo'] : '';
					$array_data[$int_key]["buyer_message"] = isset($array_trade['order_remark']) ? $array_trade['order_remark'] : '';
					$array_data[$int_key]["seller_memo"] = isset($array_trade['vender_remark']) ? $array_trade['vender_remark'] : '';
                    $array_orders = array();
                    foreach ($array_trade["item_info_list"] as $int_order_key => $array_order) {
                        $array_orders[$int_order_key]["to_id"] = $tt_id.$int_order_key;
                        $array_orders[$int_order_key]["adjust_fee"] = 0;
                        $array_orders[$int_order_key]["discount_fee"] = 0;
                        $array_orders[$int_order_key]["num"] = $array_order["item_total"];
                        $array_orders[$int_order_key]["num_iid"] = $array_order["ware_id"];
                        $array_orders[$int_order_key]["price"] = $array_order["jd_price"];
                        $array_orders[$int_order_key]["title"] = $array_order["sku_name"];
						
						$array_orders[$int_order_key]["outer_iid"] = "";
						//获取商品外部编码
						if(!empty($array_order["outer_sku_id"])){
							$array_orders[$int_order_key]["outer_iid"] = D("GoodsProductsTable")->where(array('pdt_sn'=>$array_order["outer_sku_id"]))->getField('g_sn');
							if(empty($array_orders[$int_order_key]["outer_iid"])){
								$array_orders[$int_order_key]["outer_iid"] = D("ThdOrdersItems")->where(array('toi_outer_sku_id'=>$array_order["outer_sku_id"]))->getField('toi_outer_id');
							}
						}
						if(empty($array_orders[$int_order_key]["outer_iid"])){
							$outer_info = $this->requestAPI("360buy.ware.get", array('ware_id'=>$array_order["ware_id"],'fields'=>'title,item_num'));
							if(!empty($outer_info['ware_get_response']['ware'])){
								$array_orders[$int_order_key]["title"] = $outer_info['ware_get_response']['ware']['title'];
								$array_orders[$int_order_key]["outer_iid"] = $outer_info['ware_get_response']['ware']['item_num'];	
								if($int_order_key == 0){
									if(count($array_trade["item_info_list"]['item_info'])>1){
										$array_data[$int_key]["title"] = $outer_info['ware_get_response']['ware']['title'].'等';	
									}else{
										$array_data[$int_key]["title"] = $outer_info['ware_get_response']['ware']['title'];
									}								
								}	
							}								
						}			
                        $array_orders[$int_order_key]["outer_sku_id"] = isset($array_order["outer_sku_id"]) ? $array_order["outer_sku_id"] : "";
						if(empty($array_orders[$int_order_key]["outer_sku_id"])){
							$array_orders[$int_order_key]["outer_sku_id"] = $array_orders[$int_order_key]["outer_iid"];
						}
                        $array_orders[$int_order_key]["sku_properties_name"] = isset($array_order["sku_name"]) ? $array_order["sku_name"] : "";
						$array_orders[$int_order_key]["sku_properties_name"] = trim(str_replace($array_orders[$int_order_key]["title"],'',$array_orders[$int_order_key]["sku_properties_name"]));
						if(empty($array_orders[$int_order_key]["sku_properties_name"])){
							$array_orders[$int_order_key]["sku_properties_name"] = $array_orders[$int_order_key]["title"];
						}
						//暂时这样处理到时候看怎么截取
						 //$array_orders[$int_order_key]["product_no"] = isset($array_order["product_no"]) ? $array_order["product_no"] : "";//商品货号（极端情况下不保证返回，建议从商品接口获取）  
                    }
                    $array_data[$int_key]["orders"] = $array_orders;
					//dump($array_data[$int_key]["orders"]);die();
                }
            }
			if($has_next == true){
				$int_page +=1;
				$this->getOrdersList($str_create_time,$int_total_nums,$int_page,$int_size,$array_data);
			}
			
            writeLog(var_export($array_data,true), 'jd.api.log' );
            return $array_data;
        }
        return false;
    }

    /**
     * 根据京东卖家昵称获取到相应店铺信息
     * @author wang <wangguibin@guanyisoft.com>
     * @date 2015-03-11
     * @param array $ary_data 其中$ary_data['nick']为京东卖家昵称(主旺旺号)
     * @return string 返回json字符串
     * @link  jingdong.vender.shop.query 
     */
    public function getShopInfo($ary_data = array()) {
        $ary_param = array();
        return $this->requestAPI('jingdong.vender.shop.query', $ary_param);
    }

    /**
     * 根据京东卖家授权获取到相应的卖家信息
     * @author wang <wangguibin@guanyisoft.com>
     * @date 2015-03-11
     * @param array $ary_data 默认为空无需传入nick
     * @return string 返回json字符串
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.32.kuYFhq&path=cid:1-apiId:21349
     */
    public function getSellerInfo($ary_data = array()) {
        $ary_param = array(
            'fields' => 'seller_credit,type,sign_food_seller_promise,has_shop,is_golden_seller'
        );
        return $this->requestAPI('taobao.user.seller.get', $ary_param);
    }

    /**
     * 获取交易详情
     * 
     * @param array $ary_filter
     * @author Wang<wangguibin@guanyisoft.com>
     * @date 2015-03-16
     * @refer http://jos.jd.com/api/detail.htm?apiName=360buy.order.get&id=403
     */
    public function getThdTradeDetial($ary_filter) {
        $ary_result = array('data' => array(), 'num' => 0, 'err_msg' => '', 'status' => true, 'err_code' => 0);
		$ary_params = array();
        $ary_params['order_id'] = $ary_filter['tid'];
        $ary_params['optional_fields'] = isset($ary_filter['fields']) ? $ary_filter['fields'] : 'vender_remark,order_remark,order_id,order_state';
		unset($ary_filter);
        $array_result = $this->requestAPI("360buy.order.get", $ary_params);
        if (isset($array_result['order_get_response']['order']['orderInfo'])) {
            $array_result['order_get_response']['order']['orderInfo']["order_id"] = number_format($array_result['order_get_response']['order']['orderInfo']["order_id"], 0, '', '');
            $ary_result['data'] = $array_result['order_get_response']['order']['orderInfo'];
            $ary_result['num'] = 1;
        } else {
            $ary_result['status'] = false;
            $ary_result['err_msg'] = $array_result['error_response']['msg'];
            $ary_result['err_code'] = $array_result['error_response']['code'];
        }
        //echo "<pre>";print_r($ary_result);exit;
        return $ary_result;
    }

    /**
     * 发货
     * @author Wang<wangguibin@guanyisoft.com>
     * @date 2015-03-17
     * @param $order_id 京东交易ID
     * @param $waybill 运单号
     * @param $logistics_id 	物流公司ID(只可通过获取商家物流公司接口获得),多个物流公司以|分隔  
	 * @param 订单SOP出库 
     * @referer http://jos.jd.com/api/detail.htm?apiName=360buy.order.sop.outstorage&id=411
     */
    public function logisticsSend($int_tid, $str_out_sid, $ary_company_code, $str_delivery_type = 'offline') {
		$ary_result = array('sub_msg' => '','code'=>'' , 'status' => false);
        if (empty($ary_company_code['od_logi_name'])) {
			$ary_result['sub_msg']="没有物流公司";
            return $ary_result;
        }
        //writeLog('物流公司名称：'.$ary_company_code, 'Taobao.logisticsSend.log');
        $ary_code = $this->matchLogisticsCompanieCode($ary_company_code);
		if($ary_code['code'] == '202'){
			$ary_result['sub_msg']="没有获取到物流公司";
            return $ary_result;			
		}
		if(empty($ary_code['code'])){
			$ary_result['sub_msg']="没有获取到物流公司";
            return $ary_result;					
		}
        $array_params["order_id"] = $int_tid;
		if($ary_code['error'] == '0'){
			$array_params["waybill"] = $str_out_sid;
		}
        $array_params["logistics_id"] = $ary_code['code'];
		/**
		$array_params = array(
			'logistics_id'=>'467',
			'waybill'=>'589530115173',
			'order_id'=>'8796967639'
		);
		**/
		//writeLog('请求京东接口参数：'. json_encode($array_params), 'jd.logisticsSend.log');
        $str_msg = '';
        if (empty($str_out_sid)) {
			$ary_result['sub_msg']="没有物流单号";
            return $ary_result;
        }
		$array_result = $this->requestAPI("360buy.order.sop.outstorage", $array_params);
		//writeLog('请求京东接口参数：'. json_encode($array_result), 'jd.logisticsSend.log');
		//writeLog('请求京东接口参数：'. json_encode($array_result["order_sop_outstorage_response"]), 'jd.logisticsSend.log');
        if (empty($array_result["order_sop_outstorage_response"]["code"]) && empty($array_result["error_response"])) {
            $ary_result['status']="true";
            return $ary_result;
        }else{
			$ary_result['code'] = $array_result["error_response"]['code'];
			$str_msg = $array_result["error_response"]['zh_desc'];
			$ary_result['sub_msg'] = $str_msg;
            return $ary_result;
		}
    }
	
    /**
     * 获取物流公司
     * @author Wang<wangguibin@guanyisoft.com>
     * @date 2015-03-17
     * @return $id  物流公司ID
     * @return $name  物流公司名称
     * @return $description  	物流公司具体描述 
     * @referer http://jos.jd.com/api/detail.htm?id=582
     */
    protected function matchLogisticsCompanieCode($ary_logistic) {
		$str_name = $ary_logistic['od_logi_name'];
		if(!empty($ary_logistic['jd_name'])){
			$str_jd_name = $ary_logistic['jd_name'];
		}
		$str_code = '';
		$code = 0;
		$array_result = $this->requestAPI("360buy.delivery.logistics.get", $array_params);
		if (!empty($array_result["delivery_logistics_get_response"]['logistics_companies'])) {
			$company_infos = $array_result["delivery_logistics_get_response"]['logistics_companies']['logistics_list'];
		}else{
			return array('code'=>'202','sub_msg'=>'获取物流公司失败');
		}	 
		if(!empty($company_infos)){
			if(!empty($str_jd_name)){
				foreach($company_infos as $company_info){
					if(false !== stripos($str_jd_name, $company_info['logistics_name']) || false !== stripos($company_info['logistics_name'],$str_jd_name)) {
						$str_code = $company_info['logistics_id'];
						return array('name'=>$str_jd_name,'code'=>$str_code,'error'=>$code);
					}
				}			
			}
			foreach($company_infos as $company_info){
				if(false !== stripos($str_name, $company_info['logistics_name']) || false !== stripos($company_info['logistics_name'],$str_name)) {
					$str_code = $company_info['logistics_id'];
					return array('name'=>$str_name,'code'=>$str_code,'error'=>$code);
				}
			}
		}
		//随机取一个 暂时不处理
		if(empty($str_code)){
			//$str_code = $company_info[0]['logistics_id'];
			//$code = 'other';
		}
        return array('name'=>$str_name,'code'=>$str_code,'error'=>$code);
    }
	
	/**
	 * 修改第三方交易备注/旗帜
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-21
	 * @param $tid 交易编号
	 * @param $memo 交易备注
	 * @param $flag 交易备注旗帜
	 * @param $reset boole 
	 * @refer http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.TIAItk&path=cid:5-apiId:49
	*/
	public function updateMemo($ary_params = array()) {
		unset($ary_params['data']);
		unset($ary_params['ts_id']);
		$params = array();
		$params['memo'] = $ary_params['memo'];
		$params['flag'] =  $ary_params['seller_flag'];
		if(is_array($ary_params['to_oid'])){
			foreach($ary_params['to_oid'] as $v){
				$params['tid'] = $v;
				$ary_res = $this->requestAPI("taobao.trade.memo.update", $params);
			}
		}else{
			$params['tid'] = $ary_params['to_oid'];
			$ary_res = $this->requestAPI("taobao.trade.memo.update", $params);
		}
        $array_result = json_decode($ary_res, true);
		if(!empty($array_result['trade_memo_update_response']['trade'])){
			return true;
		}else{
			return false;
		}
	}
	
	/**
     * 分销是未发货，京东已发货时更新
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2014-04-09
     * @referer http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.7iYqCS&path=cid:5-apiId:47
     * @return array 
     */
    public function UpdataTaobaoOrdersStatus($str_create_time, &$int_total_nums, $int_page = 1, $int_size = 20,&$array_data = array()) {
        $ary_params = array(
            'start_date' => $str_create_time,
			'end_date'=>date('Y-m-d H:i:s'),
			'optional_fields'=>'order_id,order_state',
			'page_size' => $int_size,
			'order_state'=>'WAIT_GOODS_RECEIVE_CONFIRM,FINISHED_L,TRADE_CANCELED,LOCKED',
            'page' => $int_page
        );
        $array_result = $this->requestAPI("360buy.order.search", $ary_params);
        if (isset($array_result["order_search_response"]['order_search'])) {
			$have_next = false;
            $order_total = $array_result["order_search_response"]['order_search']['order_total'];
			$now_count = ($int_page-1)*$int_size+count($array_result["order_search_response"]['order_search']['order_info_list']);
			if($now_count<$order_total){
				$has_next = true;
			}
            if (!empty($array_result["order_search_response"]['order_search']['order_info_list'])) {
				foreach ($array_result["order_search_response"]['order_search']['order_info_list'] as  $array_trade){
                    $tt_id = number_format($array_trade["order_id"], 0, '', '');
                    $int_key=count($array_data);
                    $array_data[$int_key]["tt_id"] = $tt_id;
                    $array_data[$int_key]["tt_source"] = "3";
                    $array_data[$int_key]["thd_status"] = $array_trade['order_state'];
                }
            }
			if($has_next){
				$int_page +=1;
				$this->UpdataTaobaoOrdersStatus($str_create_time,$int_total_nums,$int_page,$int_size,$array_data);
			}
			
            return $array_data;
        }
        return false;
    }
	
    /**
     * 京东SKU库存修改接口
     * @author wangguibin <wangguibin@guanyisoft.com>
	 * @param array $ary_data 接口数据
     * @date 205-09-18
     * @version 7.8.7
	 * @return array
     * @refer http://jos.jd.com/api/detail.htm?apiName=360buy.sku.stock.update&id=117
     */
    public function updateQuantitySkus($ary_data) {
		
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>1, 'err_code'=>0);
        $ary_filter['outer_id']	= $ary_data['sku_id'];
        $ary_filter['quantity']	= $ary_data['pdt_stock'];
		//获取库存信息
		//$array_result	= $this->requestAPI("360buy.sku.custom.get", array('outer_id'=>$ary_data['sku_id']));
		//更新库存
        $array_result	= $this->requestAPI("360buy.sku.stock.update", $ary_filter);
        if(isset($array_result['ware_sku_stock_update_response'])) {
			$ary_result['data']	= $array_result['ware_sku_stock_update_response'];
            $ary_result['status']	= true;
			@writeLog(date('Y-m-d H:i:s').json_encode($array_result).' '.$_SESSION['Members']['m_id'].' '.json_encode($ary_filter), 'syn_jd_skus_num.log');			
		}else{
		   // writeLog('error:'.var_export($array_result,1),'updateQuantitySkus_error.log');
			$ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['zh_desc'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
			@writeLog(date('Y-m-d H:i:s').json_encode($array_result).' '.$array_result['error_response']['zh_desc'], 'syn_jd_error_skus_num.log');
		}
		return $ary_result;
    }	
	
}