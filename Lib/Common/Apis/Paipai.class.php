<?php
/**
 * 拍拍API接口
 *
 * @package Common
 * @subpackage Api
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class Paipai implements IApis{
    private $uin = "";
    private $token = "";
    private $spid = "";
    private $hostName="api.paipai.com";
    private $charset="utf-8";
    private $seckey = "";

    public function __construct($ary_token=array()) {
        $this->uin    = $ary_token['uin'];
        $this->token  = $ary_token['token'];
        $this->appoid   = $ary_token['spid'];
        $this->seckey = $ary_token['seckey'];
    }

    /**
     * 发送拍拍API请求
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2012-12-19
     * @param string $str_method 请求的API方法
     * @param array $ary_data 请求的参数数组
     * @param string $str_url 请求的地址
     */
    public function requestAPI($str_method_url, $array_request) {
        $array_request['uin']      = $this->uin;
        $array_request['format']   = 'json';
        $array_request['accessToken']    = $this->token;
        $array_request['appOAuthID']     = $this->appoid;
        $array_request['pureData'] = "1";
        $array_request['randomValue'] = (rand() * 100000+11229);
        $array_request['timeStamp'] = $this->getMillisecond();
        $array_request["sign"] = $this->generateSign($array_request, $str_method_url);
        $url = $this->hostName.$str_method_url."?charset=".$this->charset."&";
        $ary_result =  makeRequest($url, $array_request, 'POST');
        return json_decode($ary_result,true);
    }
    
    private function getMillisecond (){
        list($s1, $s2) = explode(' ', microtime());
        $ret =  (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000) . "";
        return $ret;
    }
    
    private function getCmdId($str_method_url) {
        $str_method_url = ltrim($str_method_url, "/");
        //$str_method_url = rtrim($str_method_url, ".xhtml");
        $str_method_url = substr($str_method_url, 0, strlen($str_method_url)-strlen(".xhtml"));
        $str_method_url = str_replace("/", ".", $str_method_url);
        return $str_method_url;
    }
    
    /**
     * 生成验证串
     *
     * @author Luis Pater
     * @date 2009-08-27
     * @param array 需要生成验证串的数据
     * @return string 验证串
     */
    public function generateSign($array_data, $str_method_url){
        // 第二步： 构造密钥。得到密钥的方式：在应用的appOAuthkey末尾加上一个字节的“&”，即appOAuthkey&
        $secret = $this->seckey . "&";
        $array_data["charset"] = $this->charset;
		//拼接源串
		$src_arr = $this->makeSource('post',$str_method_url,$array_data);
		//使用sha1 加密算法加密
		//注意：这里必须设置为true： When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
		$hash = hash_hmac("sha1", $src_arr, $secret, true);
		//将加密后的字符串用base64方式编码
		$sig = base64_encode($hash);
        return $sig;
    }
    
    private function encodeUrl($input) {
        try{
            $tmpUrl = urlencode($input);
            $tmpUrl = str_replace("+", "%20",$tmpUrl);
            $tmpUrl = str_replace("*", "%2A",$tmpUrl);
            return $tmpUrl;
        }catch (Exception $e){
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }
    
   /**
     * 构造原串
     * 源串是由3部分内容用“&”拼接起来的：   HTTP请求方式 & urlencode(uri) & urlencode(a=x&b=y&...)
     * @param $method  get | post
     * @param $urlPath if our url is http://api.paipai.com/deal/sellerSearchDealList.xhtml,then
     * $urlPath=/deal/sellerSearchDealList.xhtml
     */
    public function makeSource($method, $urlPath,$array_data){
        ksort($array_data);//按照关键码从小到大排序
        //先拼装  HTTP请求方式 & urlencode(uri) &
        $buffer = "" . strtoupper($method) . "&" . $this->encodeUrl($urlPath) . "&";
        //拼装 参数部分
        $buffer2 = "";
        foreach($array_data as $key => $value){
            $buffer2 .= $key . "=" . $value . "&";
        }
        $buffer2 = substr_replace($buffer2, '', -1, 1 );
        //组装成预期的“原串”
        $buffer .= $this->encodeUrl($buffer2);

        return $buffer;
    }
    
    /**
     * 获取拍拍店铺的信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2012-10-18
     * 
     * @param array $ary_filter 查询数组
     * @return array 返回拍拍接口的详细信息
     * @link http://pop.paipai.com/bin/view/Main/getShopInfo 
     */
    public function getShopInfo($ary_filter=array()){
        $array_result = $this->requestAPI("/shop/getShopInfo.xhtml", $ary_filter);
        return $array_result;
    }
    
    public function getOrdersList($str_create_time,&$int_total_nums, $int_page = 1, $int_size = 20){
        $array_params["sellerUin"]   = $this->uin;
        $array_params["dealState"]   = "DS_WAIT_SELLER_DELIVERY";// DS_WAIT_SELLER_DELIVERY(2, "买家已付款,等待卖家发货"),
        $array_params["listItem"]    = "1";
        $array_params["timeType"]    = "CREATE";
        $array_params["timeBegin"]   = $str_create_time;
        $array_params["pageIndex"]   = $int_page;
        $array_params["pageSize"]    = $int_size;
        // $array_params["historyDeal"] = "1"; //如果用了这个查询当前日期 不是三个月前的会查不到数据的 历史订单呀
        $array_result = $this->requestAPI("/deal/sellerSearchDealList.xhtml", $array_params);
        
        if (isset($array_result["dealList"])) {
            $array_data = array();
            $int_total_nums = $array_result["countTotal"];
            if ($int_total_nums) {
                foreach ($array_result["dealList"] as $int_key=>$array_trade) {
                    $array_data[$int_key]["tt_id"]             = $array_trade["dealCode"];
                    $array_data[$int_key]["tt_source"]         = "2";
                    $array_data[$int_key]["buyer"]             = $array_trade["buyerUin"];
                    $array_data[$int_key]["buyer_message"]     = $array_trade["buyerRemark"];  //buyerRemark 注意这里和淘宝不一样
                    $array_data[$int_key]["created"]           = $array_trade["createTime"];
                    $array_data[$int_key]["modified"]          = $array_trade["lastUpdateTime"];
                    $array_data[$int_key]["pay_time"]          = $array_trade["payTime"];
                    $array_data[$int_key]["post_fee"]          = $array_trade["freight"];
                    $array_data[$int_key]["payment"]           = $array_trade["totalCash"];
                    $array_data[$int_key]["receiver_mobile"]   = (string)$array_trade["receiverMobile"];
                    $array_data[$int_key]["receiver_phone"]   = (string)$array_trade["receiverPhone"];
                    $array_data[$int_key]["receiver_name"]     = (string)$array_trade["receiverName"];
                    $array_data[$int_key]["receiver_zip"]      = (string)$array_trade["receiverPostcode"];
                    $array_data[$int_key]["receiver_address"]  = "";
                    $array_data[$int_key]["receiver_district"] = "";
                    $array_data[$int_key]["receiver_city"]     = "";
                    $array_data[$int_key]["receiver_state"]    = "";
                    $array_data[$int_key]["title"]             = "";   //卖家店铺名称，此处暂时为空
                    $array_data[$int_key]["thd_status"]        = $array_trade["dealState"];
                    $array_data[$int_key]["seller_memo"]       = $array_trade["dealNote"];
                    
                    $array_receiver_address = explode(" ", $array_trade["receiverAddress"]);
                    
                    if (count($array_receiver_address)==3) {
                        $array_data[$int_key]["receiver_address"]  = (string)$array_receiver_address[2];
                        $array_data[$int_key]["receiver_district"] = (string)$array_receiver_address[1];
                        $array_data[$int_key]["receiver_city"]     = (string)(in_array($array_receiver_address[0],array('北京','天津','上海','重庆')))?$array_receiver_address[0].'市':$array_receiver_address[0];
                        $array_data[$int_key]["receiver_state"]    = (string)$array_receiver_address[0];
                    }
                    elseif (count($array_receiver_address)>=4) {
                        $str_type = iconv_substr($array_receiver_address[2], iconv_strlen($array_receiver_address[2], "UTF-8")-1, 1, "UTF-8");
                        $array_output[] = $str_type;
                        $array_data[$int_key]["receiver_state"]    = (string)$array_receiver_address[0];
                        $array_data[$int_key]["receiver_city"]     = (string)$array_receiver_address[1];
                        $array_data[$int_key]["receiver_district"] = (string)$array_receiver_address[2];
                        unset($array_receiver_address[0], $array_receiver_address[1], $array_receiver_address[2]);
                        $array_data[$int_key]["receiver_address"]  = (string)implode(" ", $array_receiver_address);
                    }
                    

                    $array_orders = array();
                    foreach ($array_trade["itemList"] as $int_order_key=>$array_order) {
                        $array_orders[$int_order_key]["to_id"]               = $array_order["dealSubCode"];
                        $array_orders[$int_order_key]["adjust_fee"]          = $array_order["itemAdjustPrice"];
                        $array_orders[$int_order_key]["discount_fee"]        = $array_order["itemDiscountFee"];
                        $array_orders[$int_order_key]["num"]                 = $array_order["itemDealCount"];
                        $array_orders[$int_order_key]["num_iid"]             = $array_order["itemCode"];
                        $array_orders[$int_order_key]["price"]               = $array_order["itemDealPrice"];
                        $array_orders[$int_order_key]["title"]               = $array_order["itemName"];
                        $array_orders[$int_order_key]["outer_iid"]           = isset($array_order["itemLocalCode"]) ? $array_order["itemLocalCode"] : "";
                        $array_orders[$int_order_key]["outer_sku_id"]        = !empty($array_order["stockLocalCode"]) ? $array_order["stockLocalCode"] : $array_order["itemLocalCode"];
                        $array_orders[$int_order_key]["sku_properties_name"] = isset($array_order["stockAttr"]) ? $array_order["stockAttr"] : "";
                        $array_orders[$int_order_key]["url"]                 = $array_order["itemDetailLink"];
                    }
                    $array_data[$int_key]["orders"] = $array_orders;
                }
            }
            //echo "<pre>";print_r($array_data);exit;
            return $array_data;
        }
        return false;
    }
    
    /**
     * 获取交易详情url
     * 
     * @param array $ary_filter
     * @author Terry
     * @date 2013-1-16
     */
    public function getThdTradeDetial($ary_filter) {
        $array_params["sellerUin"]   = $this->uin;
        //$array_params["dealState"]   = "DS_WAIT_SELLER_DELIVERY";// DS_WAIT_SELLER_DELIVERY(2, "买家已付款,等待卖家发货"),
        $array_params["listItem"]    = "1";
        $array_params["timeType"]    = "CREATE";
        $array_params["pageIndex"]   = 1;
        $array_params["dealCode"]    = $ary_filter['tt_id'];
        $array_result = $this->requestAPI("/deal/sellerSearchDealList.xhtml", $array_params);
        if(!empty($array_result) && is_array($array_result)){
            $array_data = array();
            foreach($array_result['dealList'] as $oval){
                $array_data = $oval;
            }
            return $array_data;
        }
        return false;
    }
    
    /**
     * 调用拍拍的发货接口，将拍拍订单物流状态修改成卖家已发货
     * 此接口需要中级权限
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-17
     * 
     * @param string $str_tid 拍拍订单编号 注：淘宝此处为int而拍拍为string
     * @param string $str_out_sid 物流单号
     * @param string $ary_company_code 物流公司信息:此处为数组，因为有的接口用汉字，有的用代码
     * @link http://api.paipai.com/deal/sellerConsignDealItem.xhtml
     */
    public function logisticsSend($str_tid, $str_out_sid, $ary_company_code,$type='') {
        $param_data = array();
        $param_data['sellerUin'] = $this->uin;
        $param_data['dealCode'] = $str_tid;
        $ary_code = $this->matchLogisticsCompanieCode($ary_company_code);
        //注意：此处物流公司名称写法与淘宝不同
        //淘宝是首字母缩写，而拍拍是汉字字符串. NO, 表示无需快递，同时不必再指定发货单号
        $param_data['logisticsName'] = $ary_code['name'];
        $param_data['logisticsCode'] = $str_out_sid;
        //预计几天后到货，只能在[1、2、3、4、5、7、10、15、20、30]中选择.此处默认为2
        $param_data['arriveDays'] = 2;
        $array_result = $this->requestAPI("/deal/sellerConsignDealItem.xhtml", $param_data); 
        
        writeLog('拍拍发货：'.json_encode($array_result), 'send_deliver.log');
        
        if($array_result['errorCode']==0){
            return true;
        }elseif(strpos($array_result['errorMessage'], '状态处于[买家已付款等待卖家发货')){
            return array('sub_msg'=>'该订单在第三方平台已经发货');
        }else{
            //此处将错误代码和错误提示的键名整的和taobao的一致，方便处理
            //但使用时注意，键值是不一致的
            return array('sub_msg'=>$array_result['errorMessage']);
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
            $str_code = 'LBEX';
        }elseif(false !== stripos($str_name, '长宇')){
            $str_code = 'CYEXP';
        }elseif(false !== stripos($str_name, '大田')){
            $str_code = 'DTW';
        }elseif(false !== stripos($str_name, '长发')){
            $str_code = 'YUD';
        }elseif(false !== stripos($str_name, '特能')){
            $str_code = 'SHQ';
        }else{
            $str_code = 'OTHER';
        }
        return array('name'=>$str_name,'code'=>$str_code);
    }
}