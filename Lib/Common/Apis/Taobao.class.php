<?php

/**
 * 淘宝API接口
 *
 * @package Common
 * @subpackage Api
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class Taobao implements IApis {

    private $app_key = "";
    private $app_secret = "";
    private $app_session = "";
    private $url_center = '';
    private $url_redirect = '';
    private $url_api = '';

    /**
     * 构造函数
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param array $token 从中心化服务器获取来的token数据
     */
    public function __construct($ary_token = array()) {
        $this->app_session = $ary_token['access_token'];
        $this->url_api = C('TAOBAO_REQUEST_URL');
        
    }

    /**
     * 发送淘宝API请求
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     * @param string $str_url 请求的地址
     */
    public function requestAPI($str_method, $ary_data = array(), $str_url = '') {
        //系统级别参数
        $ary_data['format'] = 'json';
        $ary_data['method'] = $str_method;
        $ary_data['timestamp'] = date("Y-m-d H:i:s");
        $ary_data['v'] = '2.0';
        $ary_data['access_token'] = $this->app_session;

        if ('' == $str_url) {
            $str_url = $this->url_api;
        }
        return makeRequest($str_url, $ary_data, 'POST');
    }

    public function requestGYAPI($str_method, $ary_data = array(), $str_url = '') {
        //系统级别参数
        $ary_data['method'] = $str_method;
        $ary_data['ts'] = date("c");
        //MD5签名
        //$ary_data['sign'] = $this->generateSign($ary_data);

        if ('' == $str_url) {
            $str_url = $this->url_api;
        }
        
        return makeRequest($str_url, $ary_data, 'POST');
    }

    /**
     * 搜索当前会话用户作为卖家已卖出的交易数据[tradesSoldGet]
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-7
     * @referer http://api.taobao.com/apidoc/api.htm?path=cid:5-apiId:46
     * @return array 第三方订单信息
     */
    public function getOrdersList($str_create_time, &$int_total_nums, $int_page = 1, $int_size = 20,&$array_data =array()) {
  		$ary_time = explode('---',$str_create_time);      
        $ary_params = array(
            'fields' => 'seller_nick,buyer_nick,seller_flag,has_buyer_message,mark_desc,title,type,created,tid,seller_rate,buyer_rate,status,payment,discount_fee,adjust_fee,post_fee,total_fee,pay_time,end_time,modified,consign_time,buyer_obtain_point_fee,point_fee,real_point_fee,received_payment,commission_fee,pic_path,num_iid,num,price,cod_fee,cod_status,shipping_type,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_mobile,receiver_phone,orders',
            'status' => 'WAIT_SELLER_SEND_GOODS',
			//'use_has_next' =>'true',
            'start_created' => $ary_time[0],
			'end_created'=>$ary_time[1],
            'page_size' => $int_size,
            'page_no' => $int_page
        );
        $ary_res = $this->requestAPI("taobao.trades.sold.get", $ary_params);
        $array_result = json_decode($ary_res, true);
        if (isset($array_result["trades_sold_get_response"])) {
            //$array_data = array();
            $has_next = $array_result["trades_sold_get_response"]["has_next"];
			$total_results = $array_result["trades_sold_get_response"]["total_results"];
            if (!empty($array_result["trades_sold_get_response"]["trades"]["trade"])) {
                foreach ($array_result["trades_sold_get_response"]["trades"]["trade"] as  $array_trade) {
                    $tt_id = number_format($array_trade["tid"], 0, '', '');
                    $int_key=count($array_data);
                    $array_data[$int_key]["tt_id"] = $tt_id;
                    $array_data[$int_key]["tt_source"] = "1";
                    $array_data[$int_key]["buyer"] = $array_trade["buyer_nick"];
                    $array_data[$int_key]["created"] = $array_trade["created"];
                    $array_data[$int_key]["modified"] = $array_trade["modified"];
                    $array_data[$int_key]["pay_time"] = $array_trade["pay_time"];
                    $array_data[$int_key]["post_fee"] = $array_trade["post_fee"];
                    $array_data[$int_key]["payment"] = $array_trade["payment"];
                    $array_data[$int_key]["receiver_address"] = $array_trade["receiver_address"];
                    $array_data[$int_key]["receiver_city"] = (string) $array_trade["receiver_city"];
                    $array_data[$int_key]["receiver_district"] = (string) $array_trade["receiver_district"];
                    $array_data[$int_key]["receiver_mobile"] = (string) ($array_trade["receiver_mobile"]);
                    $array_data[$int_key]["receiver_phone"] = (string) ($array_trade["receiver_phone"]);
                    $array_data[$int_key]["receiver_name"] = (string) $array_trade["receiver_name"];
                    $array_data[$int_key]["receiver_state"] = (string) $array_trade["receiver_state"];
                    $array_data[$int_key]["receiver_zip"] = (string) $array_trade["receiver_zip"];
                    $array_data[$int_key]["seller_flag"] = (int) $array_trade["seller_flag"];
                    $array_data[$int_key]["title"] = (string) $array_trade["title"];
                    $array_data[$int_key]["thd_status"] = $array_trade['status'];
                    $ary_thd_trade_detial = $this->getThdTradeDetial(array('tid' => $tt_id));
                    $array_data[$int_key]["buyer_message"] = isset($ary_thd_trade_detial['data']['buyer_message']) ? $ary_thd_trade_detial['data']['buyer_message'] : '';
                    $array_data[$int_key]['seller_memo'] = isset($ary_thd_trade_detial['data']['seller_memo']) ? $ary_thd_trade_detial['data']['seller_memo'] : '';
                    $array_orders = array();
                    foreach ($array_trade["orders"]["order"] as $int_order_key => $array_order) {
                        $array_orders[$int_order_key]["to_id"] = number_format($array_order["oid"], 0, '', '');
                        $array_orders[$int_order_key]["adjust_fee"] = $array_order["adjust_fee"];
                        $array_orders[$int_order_key]["discount_fee"] = $array_order["discount_fee"];
                        $array_orders[$int_order_key]["num"] = $array_order["num"];
                        $array_orders[$int_order_key]["num_iid"] = $array_order["num_iid"];
                        $array_orders[$int_order_key]["price"] = $array_order["price"];
                        $array_orders[$int_order_key]["title"] = $array_order["title"];
                        $array_orders[$int_order_key]["outer_iid"] = isset($array_order["outer_iid"]) ? $array_order["outer_iid"] : "";
                        $array_orders[$int_order_key]["outer_sku_id"] = isset($array_order["outer_sku_id"]) ? $array_order["outer_sku_id"] : "";
                        $array_orders[$int_order_key]["sku_properties_name"] = isset($array_order["sku_properties_name"]) ? $array_order["sku_properties_name"] : "";
                        $array_orders[$int_order_key]["url"] = "http://trade.taobao.com/trade/detail/tradeSnap.htm?tradeID=" . $array_order["oid"];
                    }
                    $array_data[$int_key]["orders"] = $array_orders;
					$array_data[$int_key]["total_results"] = $total_results;
                }
            }
			if($has_next){
				$int_page +=1;
				$this->getOrdersList($str_create_time,$int_total_nums,$int_page,$int_size,$array_data);
			}
            writeLog(var_export($array_data,true), 'taobao.api.log' );
            return $array_data;
        }
        return false;
    }

    /**
     * 根据淘宝卖家昵称获取到相应店铺信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-20
     * @param array $ary_data 其中$ary_data['nick']为淘宝卖家昵称(主旺旺号)
     * @return string 返回json字符串
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.33.aqaxGZ&path=cid:9-apiId:68
     */
    public function getShopInfo($ary_data = array()) {
        $ary_param = array(
            'nick' => $ary_data['nick'],
            'fields' => 'sid,cid,title,nick,pic_path,created,modified,shop_score'
        );
        return $this->requestAPI('taobao.shop.get', $ary_param);
    }

    /**
     * 根据淘宝卖家授权获取到相应的卖家信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-20
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
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-7
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:5-apiId:54
     */
    public function getThdTradeDetial($ary_filter) {
        $ary_result = array('data' => array(), 'num' => 0, 'err_msg' => '', 'status' => true, 'err_code' => 0);
        $ary_filter['tid'] = $ary_filter['tid'];
        $ary_filter['fields'] = isset($ary_filter['fields']) ? $ary_filter['fields'] : 'buyer_memo,seller_memo,buyer_message,tid,status ';
        $arr_result = $this->requestAPI("taobao.trade.fullinfo.get", $ary_filter);
        $array_result = json_decode($arr_result, true);
        if (isset($array_result['trade_fullinfo_get_response']['trade'])) {
            $array_result['trade_fullinfo_get_response']['trade']["tid"] = number_format($array_result['trade_fullinfo_get_response']['trade']["tid"], 0, '', '');
            $ary_result['data'] = $array_result['trade_fullinfo_get_response']['trade'];
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
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-17
     * @param $int_tid 淘宝交易ID
     * @param $str_out_sid 运单号
     * @param $ary_company_code 物流公司信息:此处为数组，因为有的接口用汉字，有的用代码
     * @referer http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:10591
     */
    public function logisticsSend($int_tid, $str_out_sid, $ary_company_code, $str_delivery_type = 'offline') {
		$ary_result = array('sub_msg' => '','code'=>'' , 'status' => false);
        if (empty($ary_company_code)) {
			$ary_result['sub_msg']="没有物流公司";
            return $ary_result;
        }
        //writeLog('物流公司名称：'.$ary_company_code, 'Taobao.logisticsSend.log');
        $ary_code = $this->matchLogisticsCompanieCode($ary_company_code);
        $array_params["tid"] = $int_tid;
        $array_params["out_sid"] = $str_out_sid;
        $array_params["company_code"] = $ary_code['code'];
				//writeLog('请求淘宝接口参数：'. json_encode($array_params), 'Taobao.logisticsSend.log');
        $str_msg = '';
        if (empty($str_out_sid)) {
			$ary_result['sub_msg']="没有物流单号";
            return $ary_result;
        }
        if ($str_delivery_type == 'online') {
            $array_result = $this->requestAPI("taobao.logistics.online.send", $array_params);
        } else {
            $array_result = $this->requestAPI("taobao.logistics.offline.send", $array_params);
        }
				//writeLog('淘宝接口返回结果：'. $array_result, 'Taobao.logisticsSend.log');
        $array_result = json_decode($array_result,true);
        //echo "<pre>";print_r($array_result);exit;
        if ($array_result["logistics_offline_send_response"]["shipping"]["is_success"]==1 || $array_result["logistics_online_send_response"]["shipping"]["is_success"]==1) {
            $ary_result['status']="true";
            return $ary_result;
        }else{
			if ($array_result['error_response']['code'] == 15 ) {
				$ary_result['sub_msg']="订单状态不对该物流订单已经发货或者已关闭，不能重复发货";
				$ary_result['code']=15;
			}
			if($array_result['error_response']['code']==25){
				$ary_result['code']=25;
                $str_msg = "签名无效";
                $ary_result['sub_msg']=$str_msg;
            }
			if($array_result['error_response']['code']==53){
				$ary_result['code']=53;
                $str_msg = "请重新授权";
                $ary_result['sub_msg']=$str_msg;
            }else{
                $str_msg = $array_result['error_response']['sub_msg'];  
				$ary_result['sub_msg']=$str_msg;
            }
            return $ary_result;
		}
    }

    protected function matchLogisticsCompanieCode($str_name) {
        if(false !== stripos($str_name, '平邮')) {
            $str_code = 'POST';
            $str_name = '平邮';
        }elseif(false !== stripos($str_name, 'EMS')){
            $str_code = 'EMS';
            $str_name = '邮政EMS';
        }elseif(false !== stripos($str_name, '邮宝') || false !== stripos($str_name, 'e邮宝') || false !== stripos($str_name, 'E邮宝')){
            $str_code = 'EMS';
            $str_name = 'E邮宝';
        }elseif(false !== stripos($str_name, '申通')){
            $str_code = 'STO';
            $str_name = '申通快递';
        }elseif(false !== stripos($str_name, '圆通')){
            $str_code = 'YTO';
            $str_name = '圆通速递';
        }elseif(false !== stripos($str_name, '中通')){
            $str_code = 'ZTO';
            $str_name = '中通速递';
        }elseif(false !== stripos($str_name, '宅急送')){
            $str_code = 'ZJS';
            $str_name = '宅急送';
        }elseif(false !== stripos($str_name, '顺丰')){
            $str_code = 'SF';
            $str_name = '顺丰速运';
        }elseif(false !== stripos($str_name, '汇通')){
            $str_code = 'HTKY';
        }elseif(false !== stripos($str_name, '韵达')){
            $str_code = 'YUNDA';
            $str_name = '韵达快运';
        }elseif(false !== stripos($str_name, '天天')){
            $str_code = 'TTKDEX';
            $str_name = '天天快递';
        }elseif(false !== stripos($str_name, '联邦')){
            $str_code = 'FEDEX';
        }elseif(false !== stripos($str_name, '淘物流')){
            $str_code = 'TWL';
        }elseif(false !== stripos($str_name, '风火天地')){
            $str_code = 'FIREWIND';
        }elseif(false !== stripos($str_name, '华强')){
            $str_code = 'YUD';
        }elseif(false !== stripos($str_name, '烽火')){
            $str_code = 'DDS';
        }elseif(false !== stripos($str_name, '希伊艾斯')){
            $str_code = 'ZOC';
        }elseif(false !== stripos($str_name, '亚风')){
            $str_code = 'AIRFEX';
        }elseif(false !== stripos($str_name, '全一')){
            $str_code = 'APEX';
        }elseif(false !== stripos($str_name, '小红马')){
            $str_code = 'PONYEX';
        }elseif(false !== stripos($str_name, '龙邦')){
            $str_code = 'LB';
        }elseif(false !== stripos($str_name, '长宇')){
            $str_code = 'CYEXP';
        }elseif(false !== stripos($str_name, '大田')){
            $str_code = 'DTW';
        }elseif(false !== stripos($str_name, '长发')){
            $str_code = 'YUD';
        }elseif(false !== stripos($str_name, '特能')){
            $str_code = 'SHQ';
        }
        elseif(false !== stripos($str_name, '邮政小包')){
            $str_code = 'POSTB';
        }
        elseif(false !== stripos($str_name, '全峰快递')){
            $str_code = 'QFKD';
        }
        elseif(false !== stripos($str_name, '国通快递')){
            $str_code = 'GTO';
        }
        elseif(false !== stripos($str_name, '全峰快递')){
            $str_code = 'QFKD';
        }
        else{
            $str_code = 'OTHER';
        }
        return array('name'=>$str_name,'code'=>$str_code);
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
     * //分销是未发货，淘宝已发货时更新
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2014-04-09
     * @referer http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.7iYqCS&path=cid:5-apiId:47
     * @return array 
     */
    public function UpdataTaobaoOrdersStatus($str_create_time, &$int_total_nums, $int_page = 1, $int_size = 20,&$array_data = array()) {
        $ary_params = array(
            'fields' => 'tid,status',
			'use_has_next' =>'true',
            'start_created' => $str_create_time,
            'page_size' => $int_size,
            'page_no' => $int_page
        );
        $ary_res = $this->requestAPI("taobao.trades.sold.get", $ary_params);
        $array_result = json_decode($ary_res, true);
        if (isset($array_result["trades_sold_get_response"])) {
            //$array_data = array();
            $has_next = $array_result["trades_sold_get_response"]["has_next"];
            if (!empty($array_result["trades_sold_get_response"]["trades"]["trade"])) {
                foreach ($array_result["trades_sold_get_response"]["trades"]["trade"] as  $array_trade) {
                    $tt_id = number_format($array_trade["tid"], 0, '', '');
                    $int_key=count($array_data);
                    $array_data[$int_key]["tt_id"] = $tt_id;
                    $array_data[$int_key]["tt_source"] = "1";
                    $array_data[$int_key]["thd_status"] = $array_trade['status'];
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
}