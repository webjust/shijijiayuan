<?php

/**
 * 淘宝API请求基类
 *
 * @package Common
 * @subpackage Api
 * @version 7.4.5
 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
 * @date 2013-10-18
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
use Top\schema\factory;
use Top\schema\field\InputField;
use Top\schema\field\MultiInputField;
use Top\schema\field\SingleCheckField;
use Top\schema\field\MultiCheckField;
use Top\schema\field\ComplexField;
use Top\schema\value\ComplexValue;
use Top\schema\field\MultiComplexField;
use Top\schema\field\LabelField;

require_once FXINC.'/Public/Lib/Top/schema/factory/SchemaFactory.php';
require_once FXINC.'/Public/Lib/Top/schema/factory/SchemaReader.php';
require_once FXINC.'/Public/Lib/Top/schema/factory/SchemaWriter.php';
require_once FXINC.'/Public/Lib/Top/schema/enums/FieldType.php';
require_once FXINC.'/Public/Lib/Top/schema/enums/RuleType.php';
require_once FXINC.'/Public/Lib/Top/schema/property/Property.php';
require_once FXINC.'/Public/Lib/Top/schema/option/Option.php';
require_once FXINC.'/Public/Lib/Top/schema/label/Label.php';
require_once FXINC.'/Public/Lib/Top/schema/label/LabelGroup.php';
require_once FXINC.'/Public/Lib/Top/schema/depend/DependExpress.php';
require_once FXINC.'/Public/Lib/Top/schema/depend/DependGroup.php';
require_once FXINC.'/Public/Lib/Top/schema/value/ComplexValue.php';

require_once FXINC.'/Public/Lib/Top/schema/enums/TopSchemaErrorCode.php';
require_once FXINC.'/Public/Lib/Top/schema/exception/TopSchemaException.php';

require_once FXINC.'/Public/Lib/Top/schema/util/StringUtil.php';

require_once FXINC.'/Public/Lib/Top/schema/field/Field.php';
require_once FXINC.'/Public/Lib/Top/schema/field/InputField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/MultiInputField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/SingleCheckField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/MultiCheckField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/ComplexField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/MultiComplexField.php';
require_once FXINC.'/Public/Lib/Top/schema/field/LabelField.php';


require_once FXINC.'/Public/Lib/Top/schema/rule/DefaultRule.php';
require_once FXINC.'/Public/Lib/Top/schema/rule/Rule.php';
require_once FXINC.'/Public/Lib/Top/schema/rule/RuleInterface.php';

class TaobaoApi extends GyfxAction{

    private $app_session = "";
	private $url_api = '';

    /**
     * 构造函数
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     */

	public function __construct($str_access_token) {
        $this->app_session = $str_access_token;
		$this->url_api = C('TAOBAO_REQUEST_URL');
    }

    /**
     * 发送taobao API请求
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param string $str_method 请求的API方法
     * @param array $array_request 请求的参数数组
     */

	public function request($str_method, $array_request, $is_ajax = false) {
    	$array_params = $this->createRequestParams($str_method, $array_request);
		$top_obj=new Communications();
	    $array_result=$top_obj->httpPostRequest($this->url_api, $array_params, array(), false);
    	$array_result = preg_replace('/\:(\d+)([,}]+)/', ':"$1"$2', $array_result);
    	$array_result = json_decode($array_result, true);
    	return $array_result;
    }

	/**
     * 创建请求参数
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @version 7.4.5
     * @param string $str_method 请求的API方法
     * @return array $array_params 请求的参数数组
     */

	public function createRequestParams($str_method, $array_params) {
        $array_params['method'] = $str_method;
        $array_params['format'] = 'json';
        $array_params['v'] = '2.0';
        $array_params['timestamp'] = date("Y-m-d H:i:s");
        $array_params['access_token'] = $this->app_session;
        return $array_params;
    }

    /**
     * 新增运费模板
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?path=cid:7-apiId:10918
     */

    public function addDeliveryTemplate($data){
        $result=$this->request('taobao.delivery.template.add', $data);
		return $result;
    }

	/**
     * 上传单张图片
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.yw7yYH&path=cid:10122-apiId:140
     */

    public function uploadPicture($data){
        $result=$this->request('taobao.picture.upload', $data);
		return $result;
    }

	/**
     * 获取图片信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.nh0uY4&path=cid:10122-apiId:138
     */

    public function getPicture($data){
        $result=$this->request('taobao.picture.get', $data);
		return $result;
    }

	/**
     * 更新商品信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.9L4rgd&path=cid:4-apiId:21
     */

    public function updateItem($data){
        $result=$this->request('taobao.item.update', $data);

		if(isset($result['item_update_response']['item'])) {
			$ary_result['status']= true;
    		$ary_result['data']	 = $result['item_update_response']['item'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $result['error_response']['sub_msg'];
    		$ary_result['err_code']	= $result['error_response']['code'];
            writeLog('request:'.var_export($data,1).'response:'.var_export($ary_result,1),'taobao_updateItem_error.log');
    	}
    	return $ary_result;
    }

    /**
     * 更新商品sku信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.9L4rgd&path=cid:4-apiId:21
     */

    public function updateSkuToThd($data){
        $result=$this->request('taobao.item.sku.update', $data);
        writeLog(var_export($result,1),'updateSkuToThd.log');
		if(isset($result['item_sku_update_response']['sku'])) {
			$ary_result['status']= true;
    		$ary_result['data']	 = $result['item_sku_update_response']['sku'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $result['error_response']['sub_msg'];
    		$ary_result['err_code']	= $result['error_response']['code'];
    	}
    	return $ary_result;
    }


	/**
     * 添加一个商品
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.sg9D1L&path=cid:4-apiId:22
     */

    public function addItem($data){
        $result=$this->request('taobao.item.add', $data);
		return $result;
    }

    /**
     * 删除商品图片
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:24
     */

    public function deleteItemImg($data){
        $result=$this->request('taobao.item.img.delete', $data);
		return $result;
    }

    /**
     * 添加商品图片
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-18
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.P4eos5&path=cid:4-apiId:23
     */

    public function uploadItemImg($data){
        $result=$this->request('taobao.item.img.upload', $data);
		return $result;
    }

	/**
     * 替换图片（替换一张图片，只替换图片数据，图片名称，图片分类等保持不变。）
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?path=cid:10122-apiId:10910
     */

    public function replacePicture($data){
        $result=$this->request('taobao.picture.replace', $data);
		if(isset($result['picture_replace_response']['done'])) {
			$ary_result['status']= true;
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $result['error_response']['msg'];
    		$ary_result['err_code']	= $result['error_response']['code'];
    	}
    	return $ary_result;
    }

	/**
     * 商品下架
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-31
     * @param array $data 应用级输入参数
     * @return array $result
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.k6CUT3&path=cid:4-apiId:31
     */

    public function delistingUpdateItem($data){
        $result=$this->request('taobao.item.update.delisting', $data);
		if(isset($result['item_update_delisting_response']['item'])) {
			$ary_result['status']= true;
    		$ary_result['data']	 = $result['item_update_delisting_response']['item'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $result['error_response']['msg'];
    		$ary_result['err_code']	= $result['error_response']['code'];
    	}
    	return $ary_result;
    }

    /**
     * 淘宝供销平台对接，授权绑定请求发起页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function topOauth($callback_url){
    	//构造上传参数
    	$array_params = array();
    	$array_params["callback"] = $callback_url;
    	//新建授权验证字段
    	$array_params["act"] = 'create';
    	$array_params["newfx"] = 1;
    	//301 跳转到收取管理中心
    	$center_url = C('FX_TAOBAO_CENTER');
    	header("location:" . $center_url . "?" . http_build_query($array_params));
    	exit;
    }

    /**
     * 淘宝供销平台对接，授权完成回跳页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-18
     */
    public function callback($array_data,$is_exist,$redirt_url){
    	if(!isset($array_data['taobao_user_id'])){
    		$this->error("授权出错！",$redirt_url);
    		exit;
    	}
    	//处理数据
    	$access_data = array(
    			'top_user_id'=>$array_data['taobao_user_id'],
    			'top_user_nick'=>urldecode($array_data['taobao_user_nick']),
    			'top_access_token'=>$array_data['access_token'],
    			'top_expires_in'=>$array_data['expires_in'],
    			'top_refresh_token'=>$array_data['refresh_token'],
    			//'top_refresh_expires_in'=>'',
    			'top_w1_expires_in'=>$array_data['w1_expires_in'],
    			'top_w2_expires_in'=>$array_data['w2_expires_in'],
    			'top_r1_expires_in'=>$array_data['r1_expires_in'],
    			'top_r2_expires_in'=>$array_data['r2_expires_in'],
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
    		$taobao_obj = new TaobaoApi($access_data['top_access_token']);
    		$str_shop_info_json = $taobao_obj->getShop(array('nick' => $access_data['top_user_nick']));
			if(isset($str_shop_info_json['error_response'])){
				$this->error($str_shop_info_json['error_response']['sub_msg'],$redirt_url);
				exit;
			}
    		$ts_sid = $str_shop_info_json['shop_get_response']['shop']['sid'];
    		$str_shop_info_json = json_encode($str_shop_info_json);
    		$str_seller_info_json =  $taobao_obj->getSellerUser(array('nick' => $access_data['top_user_nick']));
    		$str_seller_info_json = json_encode($str_seller_info_json);
    		//把店铺信息保存起来：taobao.shop.get
    		if($is_exist){
    			$int_uid = $_SESSION['Admin'];
    		}else{
    			$int_mid = $_SESSION['Members']['m_id'];
    		}
    		$res = D('ThdShops')->saveShop(array('access_token'=>$access_data['top_access_token']), $str_shop_info_json, $str_seller_info_json, $str_pf = 'taobao', $int_mid, $int_uid);
    		if(!$res){
				// 新增店铺信息失败
				$this->error ( "保存店铺信息出错！", $redirt_url );
				exit ();
    		}
    		//写入店铺分类信息
    		if($is_exist){
    			$ary_thd_shop_cats = $taobao_obj->getThdSellerCats(array('nick' => $access_data['top_user_nick']));
    			D('ThdShopItemcats')->cacheThdShopCats($ary_thd_shop_cats['data'], $ts_sid);
    		}
    	}
    	//301到授权成功页面
    	header("location:" . $redirt_url);
    	exit;
    }

    /**
     * 获取淘宝的店铺的类目
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-25
     * @date 2012-3-14
     * @since 5.2.29
     * @version 1.0
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:9-apiId:65
     * @return array
     */
    public function getThdSellerCats($ary_filter) {
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	$ary_filter['fields']	= 'type ,cid ,parent_cid ,name ,pic_url ,sort_order';
    	$array_result	= $this->request("taobao.sellercats.list.get", $ary_filter);
    	if(isset($array_result['sellercats_list_get_response']['seller_cats']['seller_cat'])) {
    		$ary_result['data']	= $array_result['sellercats_list_get_response']['seller_cats']['seller_cat'];
    		$ary_result['num']	= $array_result['sellercats_list_get_response']['total_results'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $array_result['error_response']['msg'];
    		$ary_result['err_code']	= $array_result['error_response']['code'];
    	}
    	return $ary_result;
    }

    /**
     * 根据淘宝卖家昵称获取到相应店铺信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-20
     * @param array $ary_data 其中$ary_data['nick']为淘宝卖家昵称(主旺旺号)
     * @return string 返回json字符串
     */
    public function getShop($ary_data = array()) {
    	$ary_param = array(
    			'nick' => $ary_data['nick'],
    			'fields' => 'sid,cid,title,nick,pic_path,created,modified,shop_score'
    	);
    	return $this->request('taobao.shop.get', $ary_param);
    }

    /**
     * 根据淘宝卖家授权获取到相应的卖家信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-12-21
     * @param array $ary_data 默认为空无需传入nick
     * @return string 返回json字符串
     * @link http://api.taobao.com/apidoc/api.htm?spm=0.0.0.32.kuYFhq&path=cid:1-apiId:21349
     */
    public function getSellerUser($ary_data = array()) {
    	$ary_param = array(
    			'fields' => 'seller_credit,type,sign_food_seller_promise,has_shop,is_golden_seller'
    	);
    	return $this->request('taobao.user.seller.get', $ary_param);
    }

    /**
     * 获取当前会话用户的在架商品
     *
     * @param array $ary_filter
     * @author wangguibin
     * @date 2013-10-23
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:162
     * @return Array
     */
    public function getCurrentUserOnSaleGoods($ary_filter=array()) {
		$product_params = array(
            'category_id'=>'50016605',
        );

		
		//$addProXml = $this->getXmlData($xmls,$ary_goods);
		//print_r($addProXml);die();
		$xmls=$array_result['tmall_product_match_schema_get_response']['match_result'];
		$product_params = array(
            'category_id'=>'50016604',
            'propvalues'=>$xmls
        );
		//$array_result2 = $this->request("tmall.product.schema.match", $product_params);
		
		//print_r($array_result2);die();

    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	$ary_filter['fields']	= isset($ary_filter['fields']) ? $ary_filter['fields'] : 'approve_status,num_iid,title,nick,type,cid,pic_url,num,props,valid_thru,list_time,price,has_discount,has_invoice,has_warranty,has_showcase,modified,delist_time,postage_id,seller_cids,outer_id';
    	$array_result = $this->request("taobao.items.onsale.get", $ary_filter);
		//echo "<pre>";print_r($array_result);die();
    	if(isset($array_result['items_onsale_get_response']['items']['item'])) {
    		$ary_result['data']	= $array_result['items_onsale_get_response']['items']['item'];
    		$ary_result['num']	= $array_result['items_onsale_get_response']['total_results'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $array_result['error_response']['msg'];
    		$ary_result['err_code']	= $array_result['error_response']['code'];
    	}
    	return $ary_result;
    }

    /**
     * 得到当前会话用户库存中的商品列表
     *
     * @param array $ary_filter
     * @author wangguibin
     * @date 2013-10-23
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:162
     * @return Array
     */
    public function getCurrentUserStorageGoods($ary_filter=array()) {
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	$ary_filter['fields']	= isset($ary_filter['fields']) ? $ary_filter['fields'] : 'approve_status,num_iid,title,nick,type,cid,pic_url,num,props,valid_thru, list_time,price,has_discount,has_invoice,has_warranty,has_showcase, modified,delist_time,postage_id,seller_cids,outer_id';
    	$array_result = $this->request("taobao.items.inventory.get", $ary_filter);
    	if(isset($array_result['items_inventory_get_response']['items']['item'])) {
    		$ary_result['data']	= $array_result['items_inventory_get_response']['items']['item'];
    		$ary_result['num']	= $array_result['items_inventory_get_response']['total_results'];
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $array_result['error_response']['msg'];
    		$ary_result['err_code']	= $array_result['error_response']['code'];
    	}
    	return $ary_result;
    }

    /**
     * 得到单个商品信息
     *
     * @param int $int_num_iid
     * @author wangguibin
     * @date 2013-10-24
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:20
     * @return Array
     */
    public function getSingleGoodsInfo($int_num_iid, $str_fileds='') {
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	if(empty($str_fileds)){
            $array_params["fields"] = "detail_url,num_iid,title,nick,type,desc,sku,props_name,created,s_lightning_consignment,is_fenxiao,auction_point,property_alias,template_id,after_sale_id,is_xinpin,sub_stock,cid,seller_cids,props,input_pids,input_str,pic_url,num,valid_thru,list_time,stuff_status,location,price,post_fee,express_fee,ems_fee,has_discount,freight_payer,has_invoice,has_warranty,has_showcase,modified,increment,approve_status,postage_id,product_id,item_img,prop_imgs,outer_id,is_virtual,is_taobao,is_ex,is_timing,videos,is_3D,one_station,second_kill,auto_fill,violation,wap_desc,wap_detail_url,cod_postage_id,sell_promise,food_security,item_weight";
    	}else{
    	   $array_params['fields']	= $str_fileds;
    	}
        $array_params["num_iid"] = $int_num_iid;
    	$array_result = $this->request("taobao.item.seller.get", $array_params);
    	if(isset($array_result['item_seller_get_response']['item'])) {
    		$ary_result['data']	= $array_result['item_seller_get_response']['item'];
    		$ary_result['num']	= 1;
    	}else{
    		$ary_result['status']	= false;
    		$ary_result['err_msg']	= $array_result['error_response']['sub_code'];
    		$ary_result['err_code']	= $array_result['error_response']['code'];
    	}
    	return $ary_result;
    }

    /**
     * 根据商品的sku_id获取淘宝商品的sku
     *
     * @param array $ary_filter numm_iid和nick至少要有一个
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-25
     * @version 7.4.5
     * @refer http://api.taobao.com/apidoc/api.htm?path=cid:4-apiId:28
     * @return array
     */
    public function getSkuBySkuId($ary_filter) {
    	//numm_iid和nick至少要有一个
    	$ary_filter['fields']	= 'properties_name,sku_id,iid,num_iid,properties,quantity,price,outer_id,created,modified,status';
    	$array_result	= $this->request("taobao.item.sku.get", $ary_filter);
    	return isset($array_result['item_sku_get_response']['sku']) ? $array_result['item_sku_get_response']['sku'] : array();
    }

    /**
     * 返回该卖家店铺内的所有物流模版,需授权的
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-10-25
     * @return array
     * @link http://api.taobao.com/apidoc/api.htm?path=cid:7-apiId:10916
     */
    public function getDeliveryTemplatesByUser(){
    	return $this->request('taobao.delivery.templates.get', array('fields'=>'template_id,template_name,supports,assumer,valuation,query_express,query_ems,query_cod,query_post,consign_area_id,fee_list,address'));
    }

    /**
     * 上传商品到淘宝店铺
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param array $data 应用级输入参数
     * @return array $result
     */
    public function addItemTop($ary_goods) {
    	$array_result	= $this->addItem($ary_goods);
    	if(isset($array_result['item_add_response']['item'])) {
			$ary_result['status']	= true;
			$ary_result['data']	= $array_result['item_add_response']['item'];
		}else{
			$ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
		}
		return $ary_result;
    }

    /**
     * 删除淘宝商品图片
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date  2013-10-25
     * @param string $str_num_iid
     * @param int  $pic_id
     * @return array $ary_result
     */
    public function delPictureTop($str_num_iid, $pic_id) {
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	$ary_data['id']	= $pic_id;
    	$ary_data['num_iid']	= $str_num_iid;
   	    $array_result	= $this->deleteItemImg($ary_data);
		if(isset($array_result['item_img_delete_response']['item_img']['id'])) {
			$ary_result['data']	= $array_result['item_img_delete_response']['item_img'];
		}else{
			$ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
		}
		return $ary_result;
    }

    /**
     * 根据商品属性的cid和pid获取到该商品的属性详细值
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-29
     *
     * @param int $cid 商品属性的分类ID
     * @param int $pid 商品属性的ID
     * @return array
     * @referer http://api.taobao.com/apidoc/api.htm?path=cid:3-apiId:121
     *
     */
    public function getThdItemProps($cid,$pid){
    	return $this->request('taobao.itemprops.get', array('cid'=>$cid,'pid'=>$pid,'fields'=>'name, must, multi,parent_pid,parent_vid,is_key_prop,is_sale_prop,is_color_prop,is_enum_prop,is_input_prop,is_item_prop,status,sort_order,child_template,is_allow_alias'));
    }


    /**
     * 根据商品属性的cid和pid获取到该商品的属性值详细值
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-29
     *
     * @param int $cid 商品属性的分类ID
     * @param string $pid 商品属性的ID 可以是pid:vid的字符串
     * @return array
     * @referer http://api.taobao.com/apidoc/api.htm?path=cid:3-apiId:121
     *
     */
    public function getThdItemPropsValues($cid,$pid){
    	return $this->request('taobao.itempropvalues.get', array('cid'=>$cid,'pvs'=>$pid,'fields'=>'prop_name, vid, name,name_alias,is_parent,status,sort_order'));
    }

    /**
     * 淘宝商品SKU库存修改接口
     * @author czy <chenzongyao@guanyisoft.com>
	 * @param array $ary_data 接口数据
     * @date 2013-10-28
     * @version 7.4.5
	 * @return array
     * @refer http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.2nJe9t&path=cid:4-apiId:21169
     */
    public function updateQuantitySkus($ary_data) {
    	$ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>1, 'err_code'=>0);
        $ary_filter['skuid_quantities']	= $ary_data['skuid_quantities'];
        $ary_filter['type']	= 1;
    	$ary_filter['num_iid']	= $ary_data['num_iid'];

        $array_result	= $this->request("taobao.skus.quantity.update", $ary_filter);

        if(isset($array_result['skus_quantity_update_response']['item'])) {
			$ary_result['data']	= $array_result['skus_quantity_update_response']['item'];
            $ary_result['status']	= true;
		}else{
		   // writeLog('error:'.var_export($array_result,1),'updateQuantitySkus_error.log');
			$ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['msg'].$array_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
			@writeLog(date('Y-m-d H:i:s').json_encode($array_result).' '.$ary_result['err_msg'], 'syn_skus_num.log');
		}
		return $ary_result;
    }

    /**
     * 返回单笔订单数据状态
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2014-4-25
     * @return array
     * @link http://api.taobao.com/apidoc/api.htm?path=cid:5-apiId:47
     */
    public function getTaobaoTradeGet($ary_filter){
        $ary_result	= array('data'=>array(), 'num'=>0, 'err_msg'=>'', 'status'=>true, 'err_code'=>0);
    	$ary_data['tid']	= $ary_filter['tid'];
    	$ary_data['fields']	= 'tid,status';
        $array_result	= $this->request("taobao.trade.get", $ary_data);
        if(isset($array_result['trade_get_response'])) {
			$ary_result['data']	= $array_result['trade_get_response'];
		}else{
			$ary_result['status']	= false;
			$ary_result['err_msg']	= $array_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $array_result['error_response']['code'];
		}
		return $ary_result;

    }

    /**
     * 初始化天猫铺货商品
     * @author hcaijin 
     * @date 2015-01-08
     * @link http://open.taobao.com/doc/api_cat_detail.htm?spm=0.0.0.0.99OccH&scope_id=11430&category_id=102
     */
    function initTmall($ary_goods){
		//数据处理
		$ary_goods['prop_13021751'] = $ary_goods['outer_id'];
		$props = explode(';',$ary_goods['props']);
		$sku_tmp_props = explode(',',$ary_goods['sku_properties']);
		$sku_props = array();
		foreach($sku_tmp_props as $tmp_prop){
			$tmp_prop = explode(';',$tmp_prop);
			$sku_props = array_merge($sku_props,$tmp_prop);
		}
		$sku_props = array_unique($sku_props);
		$prop_other_sku = array();
		
		foreach($props as $prop){
			$prop_info = explode(':',$prop);
			if($prop_info[0] == '20000'){
				$ary_goods['prop_20000'] = $prop_info[1];
			}else{
				if($prop_info[0] != '1627207' || $prop_info[0] != '20509' || $prop_info[0] != '20549' || $prop_info[0] !='5741395'){
					if(!in_array($prop,$sku_props)){
						$prop_other_sku['prop_'.$prop_info[0]] = $prop_info[1];
					}	
				}			
			}
		}
		unset($props);unset($sku_props);
		$ary_goods['prop_other_skus'] = $prop_other_sku;
        //Mapping初始化 start
        $product_params = array(
            'category_id'=>$ary_goods['cid'],
            //'brand_id'=>''
        );
        //Mapping初始化 end
        //产品匹配 start
        $match_rule = $this->request("tmall.product.match.schema.get",$product_params);  //获取产品匹配元素与规则
		if($match_rule['error_response']['code']=='44'){
			$ary_result['status'] = false;
			$ary_result['err_msg'] = '授权已过期，请重新授权！';
			$ary_result['err_code']	= '44';
			return array('product_id'=>'','ary_goods'=>$ary_result);
		}
        $str_match_rule = $match_rule['tmall_product_match_schema_get_response']['match_result'];
        //根据规则获取xml_data
        $addXml = $this->getXmlData($str_match_rule,$ary_goods);
        if($addXml === null){
            //生成xml失败
        }else{
            $match_params = array(
                'category_id'=>$ary_goods['cid'],
                'propvalues'=>$addXml
            );
            $match_result = $this->request("tmall.product.schema.match",$match_params); //匹配产品 , 返回匹配产品ID
            //产品编号
            $product_id = $match_result['tmall_product_schema_match_response']['match_result'];
			//print_r($match_params);print_r($match_result);die();echo $product_id;die();
            if(empty($product_id) || !isset($product_id)){
                //当用户未匹配到有效的产品时，需要进行产品发布
                $product_params = array(
                    'category_id'=>$ary_goods['cid'],
                    //'brand_id'=>''
                );
                $add_product_rule = $this->request("tmall.product.add.schema.get",$product_params);
				if(empty($add_product_rule)){
					return;
				}
                $str_add_product_rule = $add_product_rule['tmall_product_add_schema_get_response']['add_product_rule'];
                $addProXml = $this->getXmlData($str_add_product_rule,$ary_goods);
                //新增产品
                $add_product_params = array(
                    'category_id'=>$ary_goods['cid'],
                    //'brand_id'=>'',
                    'xml_data'=>$addProXml
                );
                $add_product_result = $this->request("tmall.product.schema.add",$add_product_params); //返回发布成功的产品ID
                $product_id = $add_product_result['tmall_product_schema_add_response']['product_id'];
            }
            //print_r($product_id);exit("000000000000000000");
            if(!empty($product_id)){
                $get_product_result = $this->request("tmall.product.schema.get",array('product_id'=>$product_id));
                if(empty($get_product_result['tmall_product_schema_get_response']['get_product_result'])){
                    //返回 无权限发布商品
					return false;
                }
            }
        }
        //产品匹配 end
		return array('product_id'=>$product_id,'ary_goods'=>$ary_goods);
    }
	
	/**
     * 天猫铺货商品数据解析
     * @author zhangjiasuo 
     * @date 2015-03-14
     * @link http://open.taobao.com/doc/api_cat_detail.htm?spm=0.0.0.0.99OccH&scope_id=11430&category_id=102
     */
    function getXmlData($item_rule,$ary_goods,$item_type){
        $schemaReader = new Top\schema\factory\SchemaReader();
        $fileList = $schemaReader->readXmlForList($item_rule);
        //对fileList进行各种修改操作数据组装
        $fieldList = $this->getTmpFieldList($fileList,$ary_goods,$item_type);
        $schemaWriter = new Top\schema\factory\SchemaWriter();
		if($item_type == 'itemRule'){
			$itemParam = $schemaWriter->writeRuleXml($fieldList);        //生成itemRule 规则xml文件数据
		}else{
			$itemParam = $schemaWriter->writeParamXml($fieldList);        //生成itemParam xml文件数据
		}
        return $itemParam;
    }
	
	/**
     * 天猫铺货商品数据组装
     * @author zhangjiasuo 
     * @date 2015-03-14
     * @link http://open.taobao.com/doc/api_cat_detail.htm?spm=0.0.0.0.99OccH&scope_id=11430&category_id=102
     */
    function getTmpFieldList($fileList,$ary_goods,$item_type){
        foreach($fileList as $k => $file){
			$id = $file->getId();
            if($file->getType() == "input"){//输入框
				$str_value = '';
				switch($id){
					case 'prop_13021751'://商品货号
					$str_value = $ary_goods['prop_13021751'];
					break;
					case 'title'://商品标题
					$str_value = $ary_goods['title'];
					break;
					case 'description'://商品描述
					$str_value = $ary_goods['desc'];
					break;			
					case 'outer_id'://商家外部编码
					$str_value = $ary_goods['outer_id'];
					break;			
					case 'auction_point'://返点比例
					if(!empty($ary_goods['auction_point'])){
						//$str_value = $ary_goods['auction_point']/10;
						$str_value = 0.5;
					}else{
						$str_value = 0.5;
					}
					break;	
					case 'quantity'://商品数量
					$str_value = $ary_goods['num'];
					break;					
					case 'price'://商品价格
					$str_value = $ary_goods['price'];
					break;	
					case 'postage_id'://运费模板ID
					if($ary_goods['freight_payer'] == 'buyer' && !empty($ary_goods['postage_id'])){
						$str_value = $ary_goods['postage_id'];
					}
					break;	
					case 'service_version'://服务版本
					$str_value = "11100";
					break;
					default:
					if(!empty($ary_goods['prop_other_skus'][$id])){
						$str_value = $ary_goods['prop_other_skus'][$id];
					}
					break;
				}
				if(!empty($str_value)){
					$file->setDefaultValue($str_value);
					$file->setValue($str_value);						
				}			
            }
            if($file->getType() == "singleCheck"){//下拉框
				$str_value = '';
				switch($id){
					case 'prop_20000'://品牌
						$str_value = $ary_goods['prop_20000'];	
					break;
					case 'item_type'://商品类型(fixed:一口价)
					$str_value = 'b';
					break;	
					case 'stuff_status'://商品新旧程度(商品新旧程度(全新:new，闲置:unused，二手：second)对应stuff_status=5 表示全新)
					$str_value = '5';
					break;	
					case 'item_status'://(对应item_status=出售中:0;定时上架;仓库中:2 )
					$str_value = '2';
					break;	
					case 'has_invoice':////是否有发票,true/false   singleCheck
					$str_value = "true";//天猫系统不能为空
					break;		
					case 'freight_payer'://seller（卖家承担），buyer(买家承担）freight_payer seller:2  买家承担运费:1
					$str_value = ($ary_goods['freight_payer']=='buyer')?1:2;
					break;		
					case 'freight_by_buyer'://freight_by_buyer 选择哪种运费方式 使用运费模板：postage 设置运费：freight_details
					if($ary_goods['freight_payer']=='buyer'){
						$str_value = (!empty($ary_goods['postage_id']))?'postage':'freight_details';
					}
					break;						
					default:
					if(!empty($ary_goods['prop_other_skus'][$id])){
						$str_value = $ary_goods['prop_other_skus'][$id];
					}					
					break;
				}
				if(!empty($str_value)){
					$file->setDefaultValue($str_value);
					$file->setValue($str_value);						
				}					
            }
            if($file->getType() == "multiInput"){//多行输入
			//dump($file->getId());echo $file->getType();echo $file->getName();echo '----';
			/**
                $field3 = new MultiInputField();
                $field3->setId($file->getId());
				$default_value=$file->getDefaultValues();
				if(!empty($default_value)){
					$field3->setValue($file->getDefaultValues());
				}else{
					$field3->setValue($file->getValues());
				}
                $fieldList[] = $field3;**/
            }
            if($file->getType() == "multiCheck"){//多选
				$str_value = '';
				switch($id){
					case 'delivery_way'://提取方式
						$str_value=2; //1:电子交易凭证 2:邮寄
					break;
				}
				if(!empty($str_value)){
					$file->addDefaultValues($str_value);
					$file->addValues($str_value);						
				}
            }
            if($file->getType() == "complex"){//复杂类型
			
				$str_value  = '';
				$complexValue = new ComplexValue();
				switch($id){
					case 'freight'://选择设置运费
					foreach($file->getFieldList() as $sub_file){
						$sub_id = $sub_file->getId();
						switch($sub_id){
							case 'express_fee':
							$str_value = (empty($ary_goods['express_fee']))?'0.00':$ary_goods['express_fee'];
							break;	
							case 'ems_fee':
							$str_value = (empty($ary_goods['ems_fee']))?'0.00':$ary_goods['ems_fee'];
							break;
							case 'post_fee':
							$str_value = (empty($ary_goods['post_fee']))?'0.00':$ary_goods['post_fee'];
							break;					
						}
						$sub_file->setValue($str_value);
						$sub_file->setDefaultValue($str_value);
						$complexValue->put($sub_file);					
					}
					break;		
					case 'product_images'://产品图片
					foreach($file->getFieldList() as $sub_file){
						$sub_id = $sub_file->getId();
						switch($sub_id){
							case 'product_image_0':
							$str_value = $ary_goods['g_picture'];
							break;	
							case 'product_image_1':
							$str_value = $ary_goods['g_picture'];
							break;
							case 'product_image_2':
							$str_value = $ary_goods['g_picture'];
							break;
							case 'product_image_3':
							$str_value = $ary_goods['g_picture'];
							break;
							case 'product_image_4':
							$str_value = $ary_goods['g_picture'];
							break;							
						}
						$sub_file->setValue($str_value);
						$sub_file->setDefaultValue($str_value);
						$complexValue->put($sub_file);					
					}
					break;
					case 'location'://所在地
						foreach($file->getFieldList() as $sub_file){
							$sub_id = $sub_file->getId();
							switch($sub_id){
								case 'prov':
								$str_value = $ary_goods['location.state'];
								break;	
								case 'city':
								$str_value = $ary_goods['location.city'];
								break;						
							}
							$sub_file->setValue($str_value);
							$sub_file->setDefaultValue($str_value);
							$complexValue->put($sub_file);
						}
					break;
					case 'description'://商品描述
					//writeLog(var_export($file->getFieldList()),'taobao_updateItem_error1.log');
						foreach($file->getFieldList() as $sub_file){
							$sub_id = $sub_file->getId();
							switch($sub_id){
								case 'desc_module_25_cat_mod':
								foreach($sub_file->getFieldList() as $tmp_key=>$tmp_sub_file){
									if($tmp_key == 'desc_module_25_cat_mod_content'){
										$tmp_id = $tmp_sub_file->getId();
										if($tmp_sub_file->getType() == "input"){//输入框
											$str_value = '';
											switch($tmp_id){
												case 'desc_module_25_cat_mod_content'://商品描述
												$tmp_str_value = $ary_goods['desc'];
												if(!empty($tmp_str_value)){
													$tmp_sub_file->setDefaultValue($tmp_str_value);
													$tmp_sub_file->setValue($tmp_str_value);
													$complexValue->put($tmp_sub_file);													
												}												
											}
										}
									}	
								}					
							}
							$complexValue->put($sub_file);
							//dump($sub_file);
						}				
					break;	
					default:				
					break;
				}
				$file->setComplexValue($complexValue);
				$file->setDefaultComplexValue($complexValue);				
            }
            if($file->getType() == "multiComplex"){//多行复杂
				switch($id){
					case 'sku'://规格
						$outer_id = $ary_goods['outer_id'];
						$sku_properties = explode(',',$ary_goods['sku_properties']);
						$sku_quantities = explode(',',$ary_goods['sku_quantities']);
						$sku_prices = explode(',',$ary_goods['sku_prices']);
						$sku_outer_ids = explode(',',$ary_goods['sku_outer_ids']);
						if(!empty($sku_properties)){
							$multiComplexvalues = new MultiComplexField();
							foreach($sku_properties as $sku_prop_key=>$sku_prop){
								$complexValues = new ComplexValue();
								$sku_prop_infos = explode(';',$sku_prop);
								foreach($sku_prop_infos  as $sk=>$sku_prop_info){
									$sku_prop_info = explode(':',$sku_prop_info);
									if($sk == 0){
										$prop_01 = $sku_prop_info[0];
										$prop_0 = $sku_prop_info[1];
									}
									if($sk == 1){
										$prop_02 = $sku_prop_info[0];
										$prop_1 = $sku_prop_info[1];
									}
								}
								foreach($file->getFieldList() as $sub_file){
									$str_value = '';
									$sub_id = $sub_file->getId();
									$tmp_sub_id = explode('prop_',$sub_id);
									$is_sale_prop = 0;
									$tmp_prop_value = '';
									if($prop_01==$tmp_sub_id[1]){
										$tmp_prop_value = $prop_0;
										$is_sale_prop = 1;
									}
									if($prop_02==$tmp_sub_id[1]){
										$is_sale_prop = 1;
										$tmp_prop_value = $prop_1;
									}
									switch($sub_id){
										case 'prop_1627207':
										$str_value = $prop_0;
										break;											
										case 'prop_20509':
										$str_value = $prop_1;
										break;
										case 'sku_quantity':
										$str_value = $sku_quantities[$sku_prop_key];
										break;
										case 'sku_price':
										$str_value = $sku_prices[$sku_prop_key];
										break;
										case 'sku_outerId':
										$str_value = $sku_outer_ids[$sku_prop_key];
										break;
										case 'sku_id':
										$str_value = $outer_id;
										break;		
										default:
										if($is_sale_prop == 1){
											if($tmp_prop_value !=''){
												$str_value = $tmp_prop_value;
											}
										}
										break;
									}
									$sub_file->setValue($str_value);
									$sub_file->setDefaultValue($str_value);
									$complexValues->put($sub_file);	
								}			
								$multiComplexvalues->addComplexValues(serialize($complexValues));
								$multiComplexvalues->addDefaultComplexValues(serialize($complexValues));
							}
						}
					break;					
					default:				
					break;
				}
				$file->setComplexValues($multiComplexvalues);
				$file->setDefaultComplexValues($multiComplexvalues);	
            }
            if($file->getType() == "label"){
				/**
                $field7 = new LabelField();
                $labelGroup = new LabelGroup();
                $label = new Label();
                $label.setDesc("label描述");
                $labelGroup.add($label);
                $field7.setLabelGroup($labelGroup);

                label label类型做提示用，不需要提交 
                $fieldList[$k] = $field7;
				**/
            }
        }
        return $fileList;
    }
	
    /**
     * 上传商品到天猫店铺
     * @author Hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-01-07
     * @param array $data 应用级输入参数
     * @return array $result
     */
    public function addItemTmall($ary_goods){				
        $res = $this->initTmall($ary_goods);
		$product_id=$res['product_id'];
		$ary_goods=$res['ary_goods'];
        if(empty($product_id)){
			if(!empty($ary_goods['err_msg'])){
				return $ary_goods;
			}else{
				//return '返回发布产品失败！';
				$ary_result['status'] = false;
				$ary_result['err_msg'] = '铺货B店商家，发布产品失败！';
				$ary_result['err_code']	= '00000013243';
				return $ary_result;
			}
        }
        //商品发布 start
        $add_item_params = array(
            'category_id'=>$ary_goods['cid'],
            'product_id'=>$product_id,
            'type'=>'b',
            //'isv_init'=>false
        );
        $add_item_rule = $this->request("tmall.item.add.schema.get",$add_item_params);
        if(!empty($add_item_rule['error_response'])){
			$ary_result['status'] = false;
			$ary_result['err_msg'] = $add_item_rule['error_response']['sub_msg'];
			$ary_result['err_code']	= $add_item_rule['error_response']['code'];
            return $ary_result;
        }
		$add_item_rule = $add_item_rule['tmall_item_add_schema_get_response']['add_item_result'];
		$addXml = $this->getXmlData($add_item_rule,$ary_goods,'add');		
        $add_item_params_xml = array(
            'category_id'=>$ary_goods['cid'],
            'product_id'=>$product_id,
            'xml_data'=>$addXml
        );
		
        $add_item_result = $this->request("tmall.item.schema.add",$add_item_params_xml);
        //商品发布 end
    	if(isset($add_item_result['tmall_item_schema_add_response']['add_item_result']) && !empty($add_item_result['tmall_item_schema_add_response']['add_item_result'])) {
			$ary_result['status'] = true;
			$ary_result['data']['num_iid']	= $add_item_result['tmall_item_schema_add_response']['add_item_result'];
		}else{
			$ary_result['status'] = false;
			$ary_result['err_msg'] = $add_item_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $add_item_result['error_response']['code'];
		}
		return $ary_result;
    }

    /**
     * 更新商品到天猫店铺
     * @author Hcaijin <huangcaijin@guanyisoft.com>
     * @date 2015-01-07
     * @param array $data 应用级输入参数
     * @return array $result
     */
    public function updateItemTmall($ary_goods){
        $res = $this->initTmall($ary_goods);
		$product_id=$res['product_id'];
		$ary_goods=$res['ary_goods'];
        if(empty($product_id)){
			if(!empty($ary_goods['err_msg'])){
				return $ary_goods;
			}else{
				$ary_result['status'] = false;
				$ary_result['err_msg'] = '铺货B店商家，更新产品失败！';
				$ary_result['err_code']	= '00000013243';
				return $ary_result;
			}
        }
        //商品更新发布 start
        $update_item_params = array(
            'item_id'=>$ary_goods['num_iid'],
            'category_id'=>$ary_goods['cid'],
            'product_id'=>$product_id
        );
        $update_item_rule_result = $this->request("tmall.item.update.schema.get",$update_item_params);
		$update_item_rule = $update_item_rule_result['tmall_item_update_schema_get_response']['update_item_result'];
		$updateXml = $this->getXmlData($update_item_rule,$ary_goods,'update');		
        $update_item_params['xml_data'] = $updateXml;
        $update_item_result = $this->request("tmall.item.schema.update",$update_item_params);
        //商品更新发布 end
    	if(isset($update_item_result['tmall_item_schema_update_response']['update_item_result']) && !empty($update_item_result['tmall_item_schema_update_response']['update_item_result'])) {
			$ary_result['status'] = true;
			$ary_result['data']['num_iid']	= $update_item_result['tmall_item_schema_update_response']['update_item_result'];
		}else{
			$ary_result['status'] = false;
			$ary_result['err_msg'] = $update_item_result['error_response']['sub_msg'];
			$ary_result['err_code']	= $update_item_result['error_response']['code'];
		}
		return $ary_result;
    }

}
