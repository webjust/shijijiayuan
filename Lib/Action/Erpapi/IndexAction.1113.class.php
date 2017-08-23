<?php
class IndexAction extends ApiAction{

	public function __construct(){
		$array_params = $_REQUEST;
		//暂时隐藏验证
		parent::__construct();
		//验证是否传入method方法
		if(!isset($array_params["method"]) || "" == $array_params["method"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数method');
		}
		//测试连接
 		//$str_db_info = 'mysql://root@localhost:3306/fx_76';
 		//C('DB_CUSTOM', $str_db_info);
 		//$_SESSION['HOST_URL'] = 'http://fx_76.com/';
	}

	/**
	 * APi路由功能
	 */
	public function index(){
		$array_params = $_REQUEST;
		$str_real_method = $this->getRealMethodName($array_params["method"]);
		$array_methods = get_class_methods($this);
		if(!in_array($str_real_method,$array_methods)){
			$this->errorResult(false,10005,array(),'无效的API方法' . $array_params["method"]);
		}
		//路由到相应的API方法
		$this->$str_real_method($array_params);
	}

	/**
	 * 跟据卖家设定的商品外部id获取商品
	 * request params
	 * @outer_id String 必须 	123456  支持批量，最多不超过40个。
	 * @fields Field List 必须 	num_iid,sku..
		$fileds = array(
		'detail_url',//商品url
		'num_iid',//商品数字id gid
		'title',//商品标题
		'nick',//卖家昵称
		'cid',//商品类型ID
		'seller_cids',//商品所属的店铺内卖家自定义类目列表
		'pic_url',//商品主图片地址  gpic
		'num',//商品数量  gstock
		'list_time',//上架时间（格式：yyyy-MM-dd HH:mm:ss）
		'delist_time',//下架时间（格式：yyyy-MM-dd HH:mm:ss）
		'stuff_status',//商品新旧程度(全新:new，闲置:unused，二手：second) g_new:'是否新品上架1 是,0 不是,2翻新'
		//'location',//商品所在地 分析系统暂时没有
		'price',//商品价格，格式：5.00；单位：元；精确到：分  gprice
		'modified',//商品修改时间（格式：yyyy-MM-dd HH:mm:ss）  g_update_time
		'approve_status',//商品上传后的状态。onsale出售中，instock库中  g_on_sale
		'item_img',//商品图片列表(包括主图)item_img.id、item_img.url、item_img.position   skus
		'prop_img',//prop_imgs商品属性图片列表。prop_img.id、prop_img.url、prop_img.properties、prop_img.position
		'sku',//Sku列表。fields中只设置sku可以返回Sku结构体中所有字段，sku.sku_id、sku.properties、sku.quantity   ==skus
		'outer_id',//商家外部编码(可与商家外部系统对接)   gsn
		//'is_virtual',//虚拟商品的状态字段  分销暂不支持
		'type',//分销默认为fixed， 商品类型(fixed:一口价;auction:拍卖)注：取消团购
		'desc'//商品描述, 字数要大于5个字符，小于25000个字符 gdesc
		//分销存在但是淘宝接口不存在，但是方便后面对接使用的字段
		* 	`g_retread_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '翻新日期',
		*	`g_pre_sale_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '预售状态( 0 否,1 是 )',
		*	`g_gifts` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否赠品( 0 否,1是 )',
		*	`g_weight` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '重量',
		*	`g_unit` varchar(50) NOT NULL DEFAULT '' COMMENT '单位',
		*	`g_desc` text COMMENT '产品介绍',
		*	`ma_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '商品最高价，此商品的所有SKU中的最高价格',
		*	`mi_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '商品最低价，此商品的所有SKU中的最低价格',
		*	`g_red_num` int(1) NOT NULL DEFAULT '0' COMMENT '商品警戒值',
		*	`is_exchange` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否积分兑换',
		*	`point` int(10) NOT NULL DEFAULT '0' COMMENT '商品兑换积分',
		*
		);
	 * reponse params
	 * @items
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemsCustomGet($array_params=array()){
		//验证是否传递outer_id参数
		if(!isset($array_params["outer_id"]) || "" == $array_params["outer_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品的商家编码outer_id');
		}

		//对商家编码的个数进行限制：最多不允许超过40个
		$array_outer_ids = explode(",",$array_params["outer_id"]);
		if(count($array_outer_ids) > 40){
			$this->errorResult(false,10002,array(),'最多不超过40个outer_id');
		}

		//需返回的字段列表 fields
		if(!isset($array_params["fields"]) || "" == $array_params["fields"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数需返回的字段列表fields');
		}

		//对字段进行限制：不允许下载非系统允许的字段
		$str_allow_fields = 'detail_url,num_iid,title,desc,skus,created,property_alias,outer_id,item_weight,seller_cids,props,input_pids,';
		$str_allow_fields .= 'pic_url,input_str,num,list_time,delist_time,price,modified,approve_status,item_imgs,prop_imgs,itemextraunsaleprop';
		$array_allow_fields = explode(',',$str_allow_fields);
		$array_client_get_fields = array();
		$array_tmp_client_fields = explode(",",$array_params["fields"]);
		foreach($array_tmp_client_fields as $val){
			if(!in_array($val,$array_allow_fields)){
				$this->errorResult(false,10002,array(),"字段名“{$val}”不是系统允许获取的字段。");
				exit;
			}
			$array_client_get_fields[] = $val;
		}
		//调用模型，获取商品资料信息
		$array_data = D('ApiGyfxGoods')->getGoodsDetailByOuterId($array_outer_ids,$array_client_get_fields);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空，请查看商品编码是否存在');
			exit;
		}

		//所有验证完毕，返回数据
		$options = array();
		$options['root_tag'] = 'items_custom_get_response';
		$this->result(true,10007,$array_data,"success",$options);
	}

	/**
	 * 根据商品ID列表获取商品sku信息
	 * request params
	 * @num_iids String 必须 	123456  支持批量，最多不超过40个。
	 * @fields Field List 必须 	num_iid,sku..
	 * $fileds = array(
	 'sku_id',//规格主键ID pdt_id
	 'num_iid',//商品ID g_id
	 'quantity',//可下单库存  pdt_stock
	 'price',//销售价  pdt_sale_price
	 'outer_id',//货号(规格编码) pdt_stock
	 'created',//记录创建时间  pdt_create_time
	 'modified',//记录最后更新时间  pdt_update_time
	 'status' //暂时统一为normal
		);
	 * reponse params
	 * @skus
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemSkusGet($array_params=array()){
		//验证是否传递num_iids参数
		if(!isset($array_params["num_iids"]) || "" == $array_params["num_iids"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数sku所属商品数字id:num_iids');
		}
		$num_iids = explode(",",$array_params["num_iids"]);
		if(count($num_iids)>40){
			$this->errorResult(false,10002,array(),'最多不超过40个商品数字id:num_iids');
		}
		//需返回的字段列表 fields
		if(!isset($array_params["fields"]) || "" == $array_params["fields"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数需返回的字段列表:fields');
		}
		//获得商品SKUS信息
		$array_data = D('ApiGoods')->goodSkus($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空，请查询商品数字id是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_skus_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 获取当前会话用户出售中的商品列表
	 * request params
	 * @q String 必须 	搜索字段。搜索商品的title
	 * @outer_id String 必须 	123456  支持批量，最多不超过40个。
	 * @order_by 排序方式。格式为column:asc/desc ，column可选值:list_time(上架时间),delist_time(下架时间),num(商品数量)，modified(最近修改时间); 默认上架时间降序(即最新上架排在前面)。如按照上架时间降序排序方式为list_time:desc
	 * @page_size 默认40 每页条数。取值范围:大于零的整数;最大值：200；默认值：40。用此接口获取数据时，当翻页获取的条数（page_no*page_size）超过 10万,为了保护后台搜索引擎，接口将报错。所以请大家尽可能的细化自己的搜索条件，例如根据修改时
	 * @start_modified 	Date 可选 	2000-01-01 00:00:00		起始的修改时间
	 * @end_modified  Date 可选 	2000-01-01 00:00:00	 结束的修改时间
	 * @page_no 页码
	 * @fields Field List 必须 	num_iid,sku..
	 * $fileds = array(
	 'num_iid',//商品数字id gid
	 'title',//商品标题
	 'nick',//卖家昵称
	 'cid',//商品类型ID
	 'pic_url',//商品主图片地址  gpic
	 'num',//商品数量  gstock
	 'list_time',//上架时间（格式：yyyy-MM-dd HH:mm:ss）
	 'delist_time',//下架时间（格式：yyyy-MM-dd HH:mm:ss）
	 //'location',//商品所在地 分析系统暂时没有
	 'price',//商品价格，格式：5.00；单位：元；精确到：分  gprice
	 'modified',//商品修改时间（格式：yyyy-MM-dd HH:mm:ss）  g_update_time
	 'approve_status',//商品上传后的状态。onsale出售中，instock库中  g_on_sale
	 'outer_id',//商家外部编码(可与商家外部系统对接)   gsn
	 //'is_virtual',//虚拟商品的状态字段  分销暂不支持
	 //'type',//分销默认为fixed， 商品类型(fixed:一口价;auction:拍卖)注：取消团购
	 //分销存在但是淘宝接口不存在，但是方便后面对接使用的字段
		);
	 * reponse params
	 * @items
	 * @total_results
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemsOnsaleGet($array_params=array()){
		//需返回的字段列表 fields
		if(!isset($array_params["fields"]) || "" == $array_params["fields"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数需返回的字段列表:fields');
		}
		if(!isset($array_params["page_size"]) || $array_params["page_size"]>200){
			$this->errorResult(false,10002,array(),'最大值：200');
		}
		//获得商品列表信息


		//对字段进行限制：不允许下载非系统允许的字段
		$str_allow_fields = 'num_iid,title,cid,seller_cids,pic_url,num,list_time,delist_time,stuff_status,price,approve_status,outer_id,modified';
		$array_allow_fields = explode(',',$str_allow_fields);
		$array_client_get_fields = array();
		$array_tmp_client_fields = explode(",",$array_params["fields"]);
		foreach($array_tmp_client_fields as $val){
			if(!in_array($val,$array_allow_fields)){
				$this->errorResult(false,10002,array(),"字段名“{$val}”不是系统允许获取的字段。");
				exit;
			}
			$array_client_get_fields[] = $val;
		}
		$array_params['fields'] = $array_client_get_fields;
		$array_data = D('ApiGoods')->goodList($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'items_onsale_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 商品下架
	 * request params
	 * @num_iid 	Number 必须 	1000231		商品数字ID，该参数必须
	 * reponse params
	 * @item 	返回商品更新信息：返回的结果是:num_iid和modified
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemUpdateDelisting($array_params=array()){
		//需返回的字段列表 num_iid
		if(!isset($array_params["num_iid"]) || "" == $array_params["num_iid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品数字ID:num_iid');
		}
		if(!is_numeric($array_params["num_iid"])){
			$this->errorResult(false,10002,array(),'商品ID必须为数字格式:num_iid');
		}
		//商品下架
		$array_data = D('ApiGoods')->itemUpdateDelisting($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'更新数据失败，请检查您的商品数字ID是否正确');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_update_delisting_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 一口价商品上架
	 * request params
	 * @num_iid String 必须 	商品数字ID
	 * @num Number 必须 需上架的商品的数量
	 * reponse params
	 * @item num_iid和modified
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemUpdateListing($array_params=array()){
		//需返回的字段列表 num_iid
		if(!isset($array_params["num_iid"]) || "" == $array_params["num_iid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品数字ID:num_iid');
		}
		//需返回的字段列表 num
		if(!isset($array_params["num"]) || "" == $array_params["num"] || !is_numeric($array_params["num"])){
			$this->errorResult(false,10002,array(),'缺少应用级参数需上架的商品的数量:num');
		}
		if(!is_numeric($array_params["num_iid"])){
			$this->errorResult(false,10002,array(),'商品ID必须为数字格式:num_iid');
		}
		//商品上架
		$array_data = D('ApiGoods')->itemUpdateListing($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'更新数据失败，请检查您的商品数字ID是否正确');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_update_listing_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 得到单个商品信息
	 * request params
	 * @num_iid Number 特殊可选 	1商品数字Id。
	 * @fields Field List 必须 	num_iid,sku..
	 * $fileds = array(
	 'num_iid',//商品数字id gid
	 'title',//商品标题
	 'cid',//商品类型ID
	 'seller_cids',//商品所属的店铺内卖家自定义类目列表
	 'pic_url',//商品主图片地址  gpic
	 'num',//商品数量  gstock
	 'list_time',//上架时间（格式：yyyy-MM-dd HH:mm:ss）
	 'delist_time',//下架时间（格式：yyyy-MM-dd HH:mm:ss）
	 'stuff_status',//商品新旧程度(全新:new，闲置:unused，二手：second) g_new:'是否新品上架1 是,0 不是,2翻新'
	 'price',//商品价格，格式：5.00；单位：元；精确到：分  gprice
	 'modified',//商品修改时间（格式：yyyy-MM-dd HH:mm:ss）  g_update_time
	 'approve_status',//商品上传后的状态。onsale出售中，instock库中  g_on_sale
	 'item_img',//商品图片列表(包括主图)item_img.id、item_img.url、item_img.position   skus
	 //'prop_img',//prop_imgs商品属性图片列表。prop_img.id、prop_img.url、prop_img.properties、prop_img.position
	 'sku',//Sku列表。fields中只设置sku可以返回Sku结构体中所有字段，sku.sku_id、sku.properties、sku.quantity   ==skus
	 'outer_id',//商家外部编码(可与商家外部系统对接)   gsn
	 'desc'//商品描述, 字数要大于5个字符，小于25000个字符 gdesc
		);
	 * reponse params
	 * @item	获取商品具体字段根据权限和设定的field决定
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemGet($array_params=array()){
		//需返回的字段列表 num_iid
		if(!isset($array_params["num_iid"]) || "" == $array_params["num_iid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品数字ID:num_iid');
		}
		if(!is_numeric($array_params["num_iid"])){
			$this->errorResult(false,10002,array(),'商品ID必须为数字格式:num_iid');
		}

		//需返回的字段列表 fields
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数需要返回的商品对象字段:fields');
		}

		//对字段进行限制：不允许下载非系统允许的字段
		$str_allow_fields = 'detail_url,num_iid,title,desc,skus,created,item_weight,property_alias,outer_id,seller_cids,props,input_pids,';
		$str_allow_fields .= 'pic_url,input_str,num,list_time,delist_time,price,modified,approve_status,item_imgs,prop_imgs,itemextraunsaleprop';
		$array_allow_fields = explode(',',$str_allow_fields);
		$array_client_get_fields = array();
		$array_tmp_client_fields = explode(",",$array_params["fields"]);
		foreach($array_tmp_client_fields as $val){
			if(!in_array($val,$array_allow_fields)){
				$this->errorResult(false,10002,array(),"字段名“{$val}”不是系统允许获取的字段。");
				exit;
			}
			$array_client_get_fields[] = $val;
		}

		//调用模型，获取商品资料信息
		$array_goods_ids = array($array_params["num_iid"]);
		$array_data = D('ApiGyfxGoods')->getGoodsDetailByGid($array_goods_ids,$array_client_get_fields);
		if(empty($array_data['items'])){
			$this->errorResult(false,10002,array(),'查询数据为空，请查看商品ID是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_get_response';
			$this->result(true,10007,$array_data['items'],"success",$options);
		}
	}

	/**
	 * 宝贝/SKU库存修改
	 * request params
	 * @num_iid 	Number 必须 	3838293428		商品数字ID，必填参数
	 * @sku_id 	Number 可选 	1230005		要操作的SKU的数字ID，可选。如果不填默认修改宝贝的库存，如果填上则修改该SKU的库存
	 * @outer_id String 可选 		SKU的商家编码，可选参数。如果不填则默认修改宝贝的库存，如果填了则按照商家编码搜索出对应的SKU并修改库存。当sku_id和本字段都填写时以sku_id为准搜索对应SKU
	 * @quantity quantity 	Number 必须 	0		库存修改值，必选。当全量更新库存时，quantity必须为大于等于0的正整数；当增量更新库存时，quantity为整数，可小于等于0。若增量更新 时传入的库存为负数，则负数与实际库存之和不能小于0。比如当前实际库存为1，传入增量更新quantity=-1，库存改为0
	 * @ type 	Number 可选 	1	1	库存更新方式，可选。1为全量更新，2为增量更新。如果不填，默认为全量更新
	 * reponse params
	 * @item 	Item  		iid、numIid、num和modified，skus中每个sku的skuId、quantity和modified
	 * 宝贝含有销售属性，不能直接修改商品数量
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxItemQuantityUpdate($array_params=array()){
		//需返回的字段列表 num_iid
		if(!isset($array_params["num_iid"]) || "" == $array_params["num_iid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品数字ID:num_iid');
		}
		//需返回的字段列表 quantity   库存修改值
		if(!isset($array_params["quantity"]) || "" == $array_params["quantity"] || !is_numeric($array_params["quantity"])){
			$this->errorResult(false,10002,array(),'缺少应用级参数库存修改值:quantity');
		}
		if(!is_numeric($array_params["num_iid"])){
			$this->errorResult(false,10002,array(),'商品ID必须为数字格式:num_iid');
		}
		if(!empty($array_params["on_way_quantity"])){
			if(!is_numeric($array_params["on_way_quantity"])){
				$this->errorResult(false,10002,array(),'在途数必须为数字格式:on_way_quantity');
			}			
		}
		//商品库存修改
		$array_data = D('ApiGoods')->itemQuantityUpdate($array_params);
		if(empty($array_data)){
			if(is_array($array_data)){
				$this->errorResult(false,10002,array(),'查询数据为空，请确认下输入的数据');
			}else{
				$this->errorResult(false,10002,array(),'更新数据失败');
			}
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_quantity_update_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}
	
	/**
	 * 商品新增
	 * request params
	 * @num_iid Number 特殊可选 	1商品数字Id。
	 * @Item[] item List 新增商品的一些字段
	 * reponse params
	 * @item	num_iid created
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-10-31
	 */
	private function fxItemAdd($array_params=array()){
		//需返回的字段列表 num_iid
		if(!isset($array_params["outer_id"]) || "" == $array_params["outer_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商家外部编码:outer_id');
		}
		if(!is_numeric($array_params["erp_guid"])){
			$this->errorResult(false,10002,array(),'缺少应用级参数第三方商品唯一标记:erp_guid');
		}
		if(!isset($array_params["price"]) || "" == $array_params["price"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数需要返回的商品价格:price');
		}
		if(!isset($array_params["title"]) || "" == $array_params["title"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品标题格:title');
		}	
		//调用模型
		$array_data = D('ApiGoods')->addGood($array_params);
		if($array_data['status'] != true){
			$this->errorResult(false,10002,array(),'新增商品出错,'.$array_data['err_msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'item_add_response';
			$this->result(true,10007,array('item'=>$array_data['item']),"success",$options);
		}
	}
	
	/**
	 * 修改物流公司和运单号
	 * detail:支持卖家发货后修改物流公司和运单号。支持订单类型支持在线下单和自己联系。 自己联系只能切换为自己联系的公司，在线下单也只能切换为在线下单的物流公司。 调用时订单状态是卖家已发货，自己联系在发货后24小时内在线下单未揽收成功才可使用
	 * request params
	 * @tid 	Number 必须 	123456		交易ID
	 * @sub_tid 	Number [] 	可选 	1,2,3 		拆单子订单列表
	 * @out_sid 	String 必须 	123456789		运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；
	 * @company_code 	String 必须 	POST		物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。 如果是货到付款订单，选择的物流公司必须支持货到付款发货方式
	 * reponse params
	 * @shipping 	Shipping  		返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxLogisticsConsignResend($array_params=array()){
		//交易ID tid
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}
		//运单号
		if(!isset($array_params["out_sid"]) || "" == $array_params["out_sid"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数运单号:out_sid');
		}
		//运单号
		if(!isset($array_params["company_code"]) || "" == $array_params["company_code"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数运单号:company_code');
		}
		$array_data = D('ApiOrders')->logisticsConsignResend($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'该订单不支持修改');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'logistics_consign_resend_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 无需物流（虚拟）发货处理
	 * detail:用户调用该接口可实现无需物流（虚拟）发货,使用该接口发货，交易订单状态会直接变成卖家已发货
	 * request params
	 * @tid 	Number 必须 	255582		交易ID
	 * reponse params
	 * @shipping 	Shipping  	返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxLogisticsDummySend($array_params=array()){
		//交易ID tid
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}

		//所有验证完毕，返回数据
		$this->result(true,10007,$array_data,"success");
	}

	/**
	 * 自己联系物流（线下物流）发货
	 * detail:用户调用该接口可实现自己联系发货（线下物流），使用该接口发货，交易订单状态会直接变成卖家已发货。不支持货到付款、在线下单类型的订单。
	 * request params
	 * @tid 	Number 必须 	255582		交易ID
	 * @company_code 	String 必须 	POST		物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。非淘宝官方物流合作公司，填写具体的物流公司名称，如“顺丰”。
	 * @out_sid 	String 必须 	F5257222		运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；若company_code中传入的代码非淘宝官方物流合作公司，此处运单号不校验。
	 * reponse params
	 * @shipping 	Shipping  	返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxLogisticsOfflineSend($array_params=array()){
		//交易ID tid
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}
		//运单号
		if(!isset($array_params["out_sid"]) || "" == $array_params["out_sid"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数运单号:out_sid');
		}
		//运单号
		if(!isset($array_params["company_code"]) || "" == $array_params["company_code"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数运单号:company_code');
		}
		//所有验证完毕，返回数据
		$this->result(true,10007,$array_data,"success");
	}

	/**
	 * 在线订单发货处理（支持货到付款）
	 * detail:用户调用该接口可实现在线订单发货（支持货到付款） 调用该接口实现在线下单发货，有两种情况：
	 *	如果不输入运单号的情况：交易状态不会改变，需要调用taobao.logistics.online.confirm确认发货后交易状态才会变成卖家已发货。
	 *	如果输入运单号的情况发货：交易订单状态会直接变成卖家已发货 。
	 * request params
	 * @tid 	Number 必须 	255582		交易ID
	 * @out_sid 	String 可选 	123456789		运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；
	 * @company_code 	String 必须 	POST		物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。 如果是货到付款订单，选择的物流公司必须支持货到付款发货方式
	 * reponse params
	 * @shipping 	Shipping  	返回发货是否成功is_success
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxLogisticsOnlineSend($array_params=array()){
		//交易ID tid
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}
		//运单号
		if(!isset($array_params["out_sid"]) || "" == $array_params["out_sid"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数运单号:out_sid');
		}
		//运单号
		if(!isset($array_params["company_code"]) || "" == $array_params["company_code"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数物流公司代码:company_code');
		}

		//商品库存修改
		$array_data = D('ApiOrders')->logisticsOnlineSend($array_params);
		if($array_data == 2){
			$this->errorResult(false,10002,array(),'此订单状态已为退换货状态或作废状态无法发货');exit;
		}
		if(empty($array_data)){
			if(is_array($array_data)){
				$this->errorResult(false,10002,array(),'查询数据为空，请确认下输入的数据');
			}else{
				$this->errorResult(false,10002,array(),'更新数据失败');
			}
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'logistics_online_send_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 获取单笔交易的详细信息
	 * detail:
	 * 获取单笔交易的详细信息
	 *	1. 只有在交易成功的状态下才能取到交易佣金，其它状态下取到的都是零或空值
	 *	2. 只有单笔订单的情况下Trade数据结构中才包含商品相关的信息
	 *	3. 获取到的Order中的payment字段在单笔子订单时包含物流费用，多笔子订单时不包含物流费用
	 *	4. 请按需获取字段，减少TOP系统的压力
	 * request params
	 * @tid 	Number 	必须 	123456798 		交易编号
	 * @fields Field List 必须
	 * 1.Trade中可以指定返回的fields：seller_nick, buyer_nick, title, type, created, tid, seller_rate,buyer_flag, buyer_rate, status, payment, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no,alipay_id,buyer_message, pic_path, num_iid, num, price, buyer_alipay_no, receiver_name, receiver_state, receiver_city, receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,seller_flag, seller_alipay_no, seller_mobile, seller_phone, seller_name, seller_email, available_confirm_fee, has_post_fee, timeout_action_time, snapshot_url, cod_fee, cod_status, shipping_type, trade_memo, is_3D,buyer_email,buyer_area, trade_from,is_lgtype,is_force_wlb,is_brand_sale,buyer_cod_fee,discount_fee,seller_cod_fee,express_agency_fee,invoice_name,service_orders,credit_cardfee,step_trade_status,step_paid_fee,mark_desc,has_yfx,yfx_fee,yfx_id,yfx_type,trade_source(注：当该授权用户为卖家时不能查看买家buyer_memo,buyer_flag),eticket_ext,send_time 2.Order中可以指定返回fields：orders.title, orders.pic_path, orders.price, orders.num, orders.num_iid, orders.sku_id, orders.refund_status, orders.status, orders.oid, orders.total_fee, orders.payment, orders.discount_fee, orders.adjust_fee, orders.snapshot_url, orders.timeout_action_time，orders.sku_properties_name, orders.item_meal_name, orders.item_meal_id，item_memo,orders.buyer_rate, orders.seller_rate, orders.outer_iid, orders.outer_sku_id, orders.refund_id, orders.seller_type, orders.is_oversold,orders.end_time,orders.order_from,orders.consign_time,orders.shipping_type,orders.logistics_company,orders.invice_no 3.fields：orders（返回Order的所有内容） 4.flelds：promotion_details(返回promotion_details所有内容，优惠详情),invoice_name(发票抬头)
		$fileds = array(
		//'seller_nick',//卖家昵称
		'buyer_nick',//买家昵称    m_name
		'title',//交易标题，以店铺名作为此标题的值。注:taobao.trades.get接口返回的Trade中的title是商品名称
		'type',//交易类型列表 分销默认fixed
		'created',//交易创建时间。格式:yyyy-MM-dd HH:mm:ss o_create_time
		'sid',//交易编号o_id
		'tid',//交易编号o_id
		'status',//交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
		'payment',//实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分 o_pay
		'discount_fee',//订单优惠金额 o_discount
		//'promotion',//交易促销详细信息
		//'adjust_fee',//手工调整金额.格式为:1.01;单位:元;精确到小数点后两位.
		'post_fee',//邮费。o_cost_freight
		'total_fee',//商品金额 o_goods_all_price
		'pay_time',//付款时间。格式:yyyy-MM-dd HH:mm:ss ps_update_time
		'end_time',//交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
		'modified',//交易修改时间。格式:yyyy-MM-dd HH:mm:ss   o_update_time
		'consign_time',//物流发货时间。格式:yyyy-MM-dd HH:mm:ss
		'buyer_obtain_point_fee',//买家获得积分,返点的积分。格式:100;单位:个 o_reward_point
		'point_fee',//买家使用积分。格式:100;单位:个.o_freeze_point
		'real_point_fee',//买家实际使用积分（扣除部分退款使用的积分）。格式:100;单位:个
		'received_payment',//卖家实际收到的支付宝打款金额 o_pay-coupon_value
		'commission_fee',//交易佣金。精确到2位小数;单位:元。如:200.07，表示:200元7分
		//'buyer_memo',//买家备注
		'seller_memo',//卖家备注 o_seller_comments
		'alipay_no',//第三方来源订单id o_source_id
		'buyer_message',//买家留言 o_buyer_comments
		'pic_path',//商品图片绝对途径
		'num_iid',//商品数字编号
		'num',//商品总数量
		'price',//商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
		'receiver_name',//收货人 o_receiver_name
		'receiver_state',//收货人省份o_receiver_state
		'receiver_city',//收货人城市 o_receiver_city
		'receiver_district',//地区第三级（文字）o_receiver_county
		'receiver_address',//收货人地址 o_receiver_address
		'receiver_zip',//收货人邮编 o_receiver_zipcode
		'receiver_mobile',//收货人手机 o_receiver_mobile
		'receiver_phone',//收货人电话 o_receiver_telphone
		'buyer_email',//买家邮箱,
		'freeze',//B2B冻结(延迟发货）,
		'unfreeze_time',//B2B冻结解除时间（延迟发货时间）,
		'is_presell',//预售订单( 0-否,1-是 ),
		'qc',//质检员,
		'is_coupon',//赠送优惠劵( 0-否,1-是 )
		'coupon_sn',//优惠劵编号
		'coupon_value',//优惠劵面额
		'coupon_start_date',//优惠劵有效开始日,
		'coupon_end_date',//优惠劵有效结束日,
		'shipping_type',//创建交易时的物流方式（交易完成前，物流方式有可能改变，但系统里的这个字段一直不变）。可选值：ems, express, post, free, virtual。
		'trade_memo',//交易备注，通过taobao.trade.add接口创建
		'orders',
		);
	 * reponse params
	 * @trade 	Trade 是 		搜索到的交易信息列表，返回的Trade和Order中包含的具体信息为入参fields请求的字段信息
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxTradeFullinfoGet($array_params=array()){
		//交易ID tid和tid的格式
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}
		if(!is_numeric($array_params["tid"])){
			$this->errorResult(false,10002,array(),'交易ID必须为数字格式:tid');
		}
		//订单审核状态
		$array_params["order_status"] = trim($array_params["order_status"]);
		if(($array_params["order_status"] != '1') && ($array_params["order_status"] != '0')){
			unset($array_params["order_status"]);
		} 
		//erp_id
		if(!empty($array_params['erp_id'])){
			$array_params['erp_id'] = trim($array_params['erp_id']);
		}
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数返回的订单信息:fields');
		}

		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'buyer_email,buyer_nick,created,discount_fee,modified,num,num_iid,payment,post_fee,price,';
		$string_allow_fields .= 'received_payment,receiver_address,receiver_city,receiver_district,receiver_mobile,';
		$string_allow_fields .= 'receiver_name,receiver_phone,receiver_state,receiver_zip,shipping_type,shipping_type_name,PayType,PayTypeName,sid,';
		$string_allow_fields .= 'tid,total_fee,status,invoice_name,cod_status,orders,buyer_message,seller_memo,invoice_type,';
		$string_allow_fields .= 'freeze,unfreeze_time,is_presell,qc,is_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date,store_code,pay_time,receiver_time,gateway_sn,';//czy 加
		$string_allow_fields .= 'discount_price,otherPayType,otherPayTypeName,otherPayTypeMoney,orders';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array(trim($field),$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		//获得订单信息
		$array_data = D('ApiOrders')->tradeFullinfoGet($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空，请查看交易ID(tid)是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'trade_fullinfo_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 获取单笔交易的部分信息(性能高)
	 * detail:
	 * request params
	 * @tid 	Number 	必须 	123456798 		交易编号
	 * @fields Field List 必须 	orders.buyer_rate
	 *	1. Trade中可以指定返回的fields:seller_nick, buyer_nick, title, type, created, tid, seller_rate, buyer_rate, status, payment, discount_fee, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment, commission_fee, buyer_memo, seller_memo, alipay_no, buyer_message, pic_path, num_iid, num, price, cod_fee, cod_status, shipping_type
	 *	2. Order中可以指定返回fields:orders.title, orders.pic_path, orders.price, orders.num, orders.num_iid, orders.sku_id, orders.refund_status, orders.status, orders.oid, orders.total_fee, orders.payment, orders.discount_fee, orders.adjust_fee, orders.sku_properties_name, orders.item_meal_name, orders.outer_sku_id, orders.outer_iid, orders.buyer_rate, orders.seller_rate
	 *	3. fields：orders（返回Order中的所有允许返回的字段）
		$fileds = array(
		//'seller_nick',//卖家昵称
		'buyer_nick',//买家昵称    m_name
		'title',//交易标题，以店铺名作为此标题的值。注:taobao.trades.get接口返回的Trade中的title是商品名称
		'type',//交易类型列表 分销默认fixed
		'created',//交易创建时间。格式:yyyy-MM-dd HH:mm:ss o_create_time
		'sid',//交易编号o_id
		'tid',//交易编号o_id
		'status',//交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
		'payment',//实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分 o_pay
		'discount_fee',//订单优惠金额 o_discount
		//'promotion',//交易促销详细信息
		'adjust_fee',//手工调整金额.格式为:1.01;单位:元;精确到小数点后两位.
		'post_fee',//邮费。o_cost_freight
		'total_fee',//商品金额 o_goods_all_price
		'pay_time',//付款时间。格式:yyyy-MM-dd HH:mm:ss ps_update_time
		'end_time',//交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
		'modified',//交易修改时间。格式:yyyy-MM-dd HH:mm:ss   o_update_time
		'consign_time',//物流发货时间。格式:yyyy-MM-dd HH:mm:ss
		'buyer_obtain_point_fee',//买家获得积分,返点的积分。格式:100;单位:个 o_reward_point
		'point_fee',//买家使用积分。格式:100;单位:个.o_freeze_point
		'real_point_fee',//买家实际使用积分（扣除部分退款使用的积分）。格式:100;单位:个
		'received_payment',//卖家实际收到的支付宝打款金额 o_pay-coupon_value
		//'buyer_memo',//买家备注
		'commission_fee',//交易佣金。精确到2位小数;单位:元。如:200.07，表示:200元7分
		'pic_path',
		'num_iid',//商品数字编号
		'num',//商品总数量
		'price',//商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
		'shipping_type',//创建交易时的物流方式（交易完成前，物流方式有可能改变，但系统里的这个字段一直不变）。可选值：ems, express, post, free, virtual。
		'trade_memo',//交易备注，通过taobao.trade.add接口创建
		'orders',
		'orders.title',//商品名称 oi_g_name
		'orders.pic_path',//商品图片
		'orders.price',//购买单价（单件商品成交价） oi_price
		'orders.num',//商品数量 oi_nums
		'orders.num_iid',//商品id g_id
		'orders.sku_id',//货品id pdt_id
		'orders.refund_status',//oi_refund_status1：正常订单，2:退款中，3退货中,4:退款成功,5退货成功，6：被驳回
		'orders.status',//
		'orders.oid',//订单详情id oi_id
		'orders.total_fee',//
		'orders.payment',
		'orders.discount_fee',
		'orders.adjust_fee',
		'orders.sku_properties_name'
		);
	 * reponse params
	 * @trade 	Trade 是 		搜索到的交易信息列表，返回的Trade和Order中包含的具体信息为入参fields请求的字段信息
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxTradeGet($array_params=array()){
		//交易ID tid
		if(!isset($array_params["tid"]) || "" == $array_params["tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数交易ID:tid');
		}
		//运单号
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数返回的订单信息:fields');
		}

		//所有验证完毕，返回数据
		$this->result(true,10007,$array_data,"success");
	}

	/**
	 * 查询卖家已卖出的增量交易数据（根据修改时间）
	 * detail:
	 * request params
	 * @start_modified 	Date 	必须 	2000-01-01 00:00:00 		查询修改开始时间(修改时间跨度不能大于一天)。格式:yyyy-MM-dd HH:mm:ss
	 * @end_modified 	Date 	必须 	2000-01-02 00:00:00 		查询修改结束时间，必须大于修改开始时间(修改时间跨度不能大于一天)，格式:yyyy-MM-dd HH:mm:ss。建议使用30分钟以内的时间跨度，能大大提高响应速度和成功率。
	 * @status 	String 	可选 	TRADE_NO_CREATE_PAY 		交易状态，默认查询所有交易状态的数据，除了默认值外每次只能查询一种状态。 可选值 TRADE_NO_CREATE_PAY(没有创建支付宝交易) WAIT_BUYER_PAY(等待买家付款) SELLER_CONSIGNED_PART（卖家部分发货） WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) TRADE_BUYER_SIGNED(买家已签收,货到付款专用) TRADE_FINISHED(交易成功) TRADE_CLOSED(交易关闭) TRADE_CLOSED_BY_TAOBAO(交易被淘宝关闭) ALL_WAIT_PAY(包含：WAIT_BUYER_PAY、TRADE_NO_CREATE_PAY) ALL_CLOSED(包含：TRADE_CLOSED、TRADE_CLOSED_BY_TAOBAO)
	 * @page_no 	Number 	可选 	1 		页码。取值范围:大于零的整数;默认值:1。注：必须采用倒序的分页方式（从最后一页往回取）才能避免漏单问题。
	 * @page_size 	Number 	可选 	40 		每页条数。取值范围：1~100，默认值：40。建议使用40~50，可以提高成功率，减少超时数量。
	 * @field Field List 必须
	 * 需要返回的字段。目前支持有： 1.Trade中可以指定返回的fields:seller_nick, buyer_nick, title, type, created, tid, seller_rate,seller_can_rate, buyer_rate,can_rate,status, payment, discount_fee, adjust_fee, post_fee, total_fee, pay_time, end_time, modified, consign_time, buyer_obtain_point_fee, point_fee, real_point_fee, received_payment,pic_path, num_iid, num, price, cod_fee, cod_status, shipping_type, receiver_name, receiver_state, receiver_city, receiver_district, receiver_address, receiver_zip, receiver_mobile, receiver_phone,alipay_id,alipay_no,is_lgtype,is_force_wlb,is_brand_sale,has_buyer_message,credit_card_fee,step_trade_status,step_paid_fee,mark_desc,send_time,,has_yfx,yfx_fee,yfx_id,yfx_type,trade_source,seller_flag 2.Order中可以指定返回fields： orders.title, orders.pic_path, orders.price, orders.num, orders.num_iid, orders.sku_id, orders.refund_status, orders.status, orders.oid, orders.total_fee, orders.payment, orders.discount_fee, orders.adjust_fee, orders.sku_properties_name, orders.item_meal_name, orders.buyer_rate, orders.seller_rate, orders.outer_iid, orders.outer_sku_id, orders.refund_id, orders.seller_type，orders.end_time, orders.order_from,orders.consign_time,orders.shipping_type,orders.logistics_company,orders.invice_no 3.fields：orders（返回Order的所有内容） 4.fields:service_orders(返回service_order中所有内容)
	 * $fileds = array(
	 //'seller_nick',//卖家昵称
	 'buyer_nick',//买家昵称    m_name
	 'title',//交易标题，以店铺名作为此标题的值。注:taobao.trades.get接口返回的Trade中的title是商品名称
	 'type',//交易类型列表 分销默认fixed
	 'created',//交易创建时间。格式:yyyy-MM-dd HH:mm:ss o_create_time
	 'sid',//交易编号o_id
	 'tid',//交易编号o_id
	 'status',//交易状态。可选值: * TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易)
	 'payment',//实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分 o_pay
	 'discount_fee',//订单优惠金额 o_discount
	 //'promotion',//交易促销详细信息
	 'adjust_fee',//手工调整金额.格式为:1.01;单位:元;精确到小数点后两位.
	 'post_fee',//邮费。o_cost_freight
	 'total_fee',//商品金额 o_goods_all_price
	 'pay_time',//付款时间。格式:yyyy-MM-dd HH:mm:ss ps_update_time
	 'end_time',//交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
	 'modified',//交易修改时间。格式:yyyy-MM-dd HH:mm:ss   o_update_time
	 'consign_time',//物流发货时间。格式:yyyy-MM-dd HH:mm:ss
	 'buyer_obtain_point_fee',//买家获得积分,返点的积分。格式:100;单位:个 o_reward_point
	 'point_fee',//买家使用积分。格式:100;单位:个.o_freeze_point
	 'real_point_fee',//买家实际使用积分（扣除部分退款使用的积分）。格式:100;单位:个
	 'received_payment',//卖家实际收到的支付宝打款金额 o_pay-coupon_value
	 //'buyer_memo',//买家备注
	 'commission_fee',//交易佣金。精确到2位小数;单位:元。如:200.07，表示:200元7分
	 'pic_path',
	 'num_iid',//商品数字编号
	 'num',//商品总数量
	 'price',//商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
	 'shipping_type',//创建交易时的物流方式（交易完成前，物流方式有可能改变，但系统里的这个字段一直不变）。可选值：ems, express, post, free, virtual。
	 'trade_memo',//交易备注，通过taobao.trade.add接口创建
	 'orders',
	 'orders.title',//商品名称 oi_g_name
	 'orders.pic_path',//商品图片
	 'orders.price',//购买单价（单件商品成交价） oi_price
	 'orders.num',//商品数量 oi_nums
	 'orders.num_iid',//商品id g_id
	 'orders.sku_id',//货品id pdt_id
	 'orders.refund_status',//oi_refund_status1：正常订单，2:退款中，3退货中,4:退款成功,5退货成功，6：被驳回
	 'orders.status',//
	 'orders.oid',//订单详情id oi_id
	 'orders.total_fee',//
	 'orders.payment',
	 'orders.discount_fee',
	 'orders.adjust_fee',
	 'orders.sku_properties_name'
		);
	 * reponse params
	 * @total_results 	Number 	否 	100 	搜索到的交易信息总数
	 * @trades 	Trade [] 	是 		搜索到的交易信息列表，返回的Trade和Order中包含的具体信息为入参fields请求的字段信息
	 * @has_next 	Boolean 	否 	true 	是否存在下一页
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-28
	 */
	private function fxTradesSoldIncrementGet($array_params=array()){
		//查询修改开始时间
		if(!isset($array_params["start_modified"]) || "" == $array_params["start_modified"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数修改开始时间:start_modified');
		}
		//查询修改结束时间
		if(!isset($array_params["end_modified"]) || "" == $array_params["end_modified"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数修改结束时间:end_modified');
		}
		//修改时间跨度不能大于一天
		if(strtotime($array_params["end_modified"])-strtotime($array_params["start_modified"])>86400){
			$this->errorResult(false,10002,array(),'修改时间跨度不能大于一天');
		}
		//订单信息
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数返回的订单信息:fields');
		}
		if($array_params["page_size"]>100){
			$this->errorResult(false,10002,array(),'每一页最多取100条数据');
		}
		//订单审核状态
		$array_params["order_status"] = trim($array_params["order_status"]);
		if(($array_params["order_status"] != '1') && ($array_params["order_status"] != '0')){
			unset($array_params["order_status"]);
		} 
		//erp_id
		if(!empty($array_params['erp_id'])){
			$array_params['erp_id'] = trim($array_params['erp_id']);
		}
		//获得订单信息
		$array_data = D('ApiOrders')->tradesSoldIncrementGet($array_params);

		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'trades_sold_increment_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 *
	 * 添加分销无敌接口供智讯开发
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-07-30
	 */
	private function fxAllPowerfulGet($array_params=array()){
		$array_params["sql"] = base64_decode($array_params["sql"]);
		//查询修改开始时间
		//订单信息
		if(!isset($array_params["sql"]) || "" == $array_params["sql"] || empty($array_params["sql"])){
			$this->errorResult(false,10002,array(),'请输入您要查询的sql语句');
		}
		//限制部分查询语句和部分表
		$array_not_allow = array(
			'drop'=>'不允许删除表或视图',//不允许删除表
			'admin'=>'表fx_admin不允许操作',//表fx_admin
			'role'=>'表fx_role，fx_role_access，fx_role_node不允许操作',//表fx_role，fx_role_access，fx_role_node
			'script_info'=>'表fx_script_info不允许操作',//表fx_script_info
			'sys_config'=>'表fx_sys_config不允许操作',//表fx_sys_config
			'_template'=>'表fx_template不允许操作',//表fx_template
			'create table'=>'不允许创建操作',//不允许创建操作
			'CREATE TABLE'=>'不允许创建操作',//不允许创建操作
			'view'=>'视图不允许操作'//视图不允许操作
		);
		//dump($array_params["sql"]);die();
		foreach($array_not_allow as $key=>$val){
			$is_exist = is_int(strpos($array_params["sql"],$key));
			if($is_exist){
				$this->errorResult(false,10002,array(),'部分查询语句和部分表不允许操作:'.$val);
			}
		}
		$Database = M('',C('DB_PREFIX'),'DB_CUSTOM');
		if(trim($array_params["sql"])){
			if(stripos(trim($array_params["sql"]),"insert") === 0 || stripos(trim($array_params["sql"]),"replace into") === 0 || stripos(trim($array_params["sql"]),"update") === 0 || stripos(trim($array_params["sql"]),"delete") === 0){
				$data = $Database->execute(trim($array_params["sql"]));
			}else{
				$data = $Database->query(trim($array_params["sql"]));
			}	
			if($data === false){
				$this->errorResult(false,10002,array(),'mysql语句报错，mysql语句为：'.$array_params["sql"]);
			}
		}
		//获得数据信息
		if(empty($data)){
			$this->errorResult(false,10002,array(),'查询数据为空');
		}else{
			//记录日志
			$ip=getenv("REMOTE_ADDR");
			$msg = date('Y-m-d H:i:s').'    '.$array_params["app_key"].'    '.$array_params["sql"].'    '.$ip."\r\n";
			$this->logs($array_params["app_key"],$msg);
			//dump($data);die();
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'data_response';
			if($data == 1){
				$this->result(true,10007,array('code'=>'200','msg'=>'新增、更新或删除数据成功'),"success",$options);
			}else{
				if(is_array($data[0])){
					$ary_data = array();
					foreach($data as $value){
						$ary_data['data_info'][] = $value;
					}
					unset($data);
					$data = $ary_data;
				}
				$this->result(true,10007,$data,"success",$options);
			}
		}
	}

	/**
	 * 记录错误日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-31
	 * @param string $code 错误日志
	 */
	function logs($code,$msg){
		$log_dir = APP_PATH . 'Runtime/Erpapilog/';
		if(!file_exists($log_dir)){
			mkdir($log_dir,0700);
		}
		$log_file = $log_dir . date('Ymd') .$code . '.log';
		$fp = fopen($log_file, 'a+');
		fwrite($fp, $msg);
		fclose($fp);
	}

	/**
	 * 仓库库存查询
	 * request params
	 * @sc_item_ids String 必须 	123456  支持批量，最多50个。
	 * @sc_item_codes String 可选 GLY201210120001^GLY23214141	   后端商品的商家编码列表，控制到50个
	 * @sc_item_codes String 可选  GLY001^GLY002	仓库列表
	 * reponse params
	 * @item_inventorys InventorySum []
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-07-30
	 */
	private function fxInventoryQuery($array_params=array()){
		//验证是否传递sc_item_ids参数
		if(!isset($array_params["sc_item_ids"]) || "" == $array_params["sc_item_ids"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数后端商品id:sc_item_ids');
		}
		//验证是否传递erp_ids参数
// 		if(!isset($array_params["erp_id"]) || "" == $array_params["erp_id"]){
// 			$this->errorResult(false,10002,array(),'缺少应用级参数后端商品id:erp_id');
// 		}
		//验证是否传递store_codes参数
// 		if(!isset($array_params["store_codes"]) || "" == $array_params["store_codes"]){
// 			$this->errorResult(false,10002,array(),'缺少应用级参数仓库列表:store_codes');
// 		}
		$sc_item_ids = explode("^",$array_params["sc_item_ids"]);
		if(count($sc_item_ids)>50){
			$this->errorResult(false,10002,array(),'最多不超过50个后端商品id:sc_item_ids');
		}


// 		if(isset($array_params["erp_id"])  && !empty($array_params["erp_id"])){
// 			$erp_ids = explode("^",$array_params["erp_id"]);
// 			if(count($erp_ids)>50){
// 				$this->errorResult(false,10002,array(),'最多不超过50个erp_id');
// 			}
// 		}
		
		if(isset($array_params["store_codes"])  && !empty($array_params["store_codes"])){
			$store_codes = explode("^",$array_params["store_codes"]);
			if(count($store_codes)>50){
				$this->errorResult(false,10002,array(),'最多不超过50个store_codes:store_codes');
			}
		}
		//字段要一一对应
		if(!empty($store_codes)){
			if((count($sc_item_ids) != count($store_codes))){
				$this->errorResult(false,10002,array(),'sc_item_ids、erp_id、store_codes要一一对应');
			}
		}
		//获得仓库商品信息(商品编码,仓库中的实际库存数)

		$array_data = D('ApiInventory')->InventoryGet($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空，请查询商品sc_item_ids 是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_query_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}



	/**
	 * 创建、更新或停用仓库接口(http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:21611))
	 * request params
	 * @operate_type  String 必须 	123456  支持批量，最多不超过40个。
	 * @store_code String 必须  商家的仓库编码，不允许重复，不允许更新:ABC0001
	 * @store_name  String 可选  商家的仓库名称，可更新:华北仓
	 * @alias_name  String 可选  仓库简称，可更新:京
	 * @address  String 可选  仓库的物理地址，可更新 :东大街000号
	 * @address_area_name   String 可选  仓库区域名，可更新:北京~北京市~崇文区
	 * @contact   String 可选  联系人，可更新:张三
	 * @phone   String 可选  联系电话，可更新 :13900000000
	 * @postcode   String 可选  邮编，可更新:100000
	 * reponse params
	 * @store
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxInventoryStoreManage($array_params=array()){
		//验证是否传递operate_type参数
		if(!isset($array_params["operate_type"]) || "" == $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:operate_type');
		}
		if("ADD" != $array_params["operate_type"] && "UPDATE" != $array_params["operate_type"] && "DELETE" != $array_params["operate_type"] && "MANAGE" != $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是ADD：新建; UPDATE：更新;DELETE：停用;MANAGE:新建或更新');
		}
		if(!isset($array_params["store_code"]) || "" == $array_params["store_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数仓库编码:store_code');
		}
		if(!isset($array_params["erp_id"]) || "" == $array_params["erp_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数ERP的ID:erp_id');
		}		
		//对仓库管理操作
		$array_data = D('ApiInventory')->addInventoryStore($array_params);
		//print_r($array_data);exit;
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_store_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}
	
	/**
	 * 创建或更新或停用仓库库存信息
	 * request params
	 * 参数列表						
	 * 参数名称	参数名	对应分销字段	是否必填	类型	描述
	 * 参数定义类型	operate_type 	(停用字段：pdt_status)	必须	String	ADD：新建; UPDATE：更新;DELETE:停用
	 * 商家的erp仓库编码	store_code 	erp_code:w_code=erp_code+erp_id	必须	String	商家的仓库编码，不允许重复，不允许更新:ABC0001（仓库代码+ERPID构成分销的仓库代码）
	 * ERP的ID	erp_id	erp_id	必须	String	卖家ERP的唯一标记（仓库代码+ERPID构成分销的仓库代码）
	 * 商品编码	outer_id	g_id	必须	String	商品编码，不可更新
	 * 商品规格编码	sku_outer_id	pdt_id	选填	String	商品规格编码，不可更新
	 * 商品或规格库存数	num	pdt_total_stock	必须	String	仓库的物理库存，可更新
	 * 状态	status	pdt_status	选填	String	数据记录状态，0为废弃，1为有效，2为进入回收站，可更新
	 * 返回值	名称	类型	是否必须	描述		
	 * storestock_response		是	返回结果							
	 * 简要描述	仓库对象(Store )										
	 * 名称	类型	是否隐私	示例值	描述	对应分销字段	
	 * store_code	String	否	ABC0001	商家的仓库编码，不允许重复	w_code	
	 * created	Date	否	创建时间	库存创建或修改时间	ws_update_time	
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-14
	 */
	private function fxInventoryStorestockManage($array_params=array()){
		//验证是否传递operate_type参数
		if(!isset($array_params["operate_type"]) || "" == $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:operate_type');
		}
		if("ADD" != $array_params["operate_type"] && "UPDATE" != $array_params["operate_type"] && "DELETE" != $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是ADD：新建; UPDATE：更新;DELETE：停用');
		}
		if(!isset($array_params["store_code"]) || "" == $array_params["store_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数仓库编码:store_code');
		}
		if(!isset($array_params["erp_id"]) || "" == $array_params["erp_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数ERP的ID:erp_id');
		}	
		if(!isset($array_params["outer_id"]) || "" == $array_params["outer_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品编码:outer_id');
		}
		if(!isset($array_params["num"]) || "" == $array_params["num"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品或规格库存数:num');
		}				
		
		//对仓库管理操作
		$array_data = D('ApiInventory')->addInventoryStorestock($array_params);
		//print_r($array_data);exit;
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_storestock_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}
	
	/**
	 * 仓库覆盖区域管理接口
	 * request params
	 * @sc_type String 必须   ADD：新建; DELETE:删除
	 * @store_code  必须 商家的仓库编码:ABC0001
	 * @area_code  必须 区域代码:110106
	 * reponse params
	 * @is_success
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxInventoryDeliveryareaManage($array_params=array()){
		//验证是否传递sc_type参数
		if(!isset($array_params["sc_type"]) || "" == $array_params["sc_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:sc_type');
		}
		if("ADD" != $array_params["sc_type"]  && "DELETE" != $array_params["sc_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是ADD：新建; DELETE：删除');
		}

		if(!isset($array_params["store_code"]) || "" == $array_params["store_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数仓库编码:store_code');
		}

		if(!isset($array_params["area_code"]) || "" == $array_params["area_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数仓库区域代码:area_code');
		}

		//对仓库覆盖区域管理操作
		$array_data = D('ApiInventory')->addInventoryDeliveryarea($array_params,$message);

		if(!$array_data){
			$this->errorResult(false,10002,array(),$message);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_deliveryarea_manage_response';

			$this->result(true,10007,array('is_success'=>'true'),"success",$options);
		}
	}

	/**
	 * 会员查询接口
	 * request params
	 * @fields Field List 必须  m_id,m_name..
	 * @condition String 选填
	 * @page_no Number 选填
	 * @page_size Number 选填
	 * @orderby String 选填
	 * @orderbytype String 选填
	 * reponse params
	 * @member_list
	 * @author zhuyuanjie@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxMemberGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数返回的会员信息:fields');
		}

		$string_allow_fields = 'id,guid,name,real_name,sex,birthday,email,mobile,address,province,city,district,grade,point,point_level,balance,security_deposit,create_time,update_time,status,qq,wangwang,website_url,recommended,alipay_name,is_proxy,zipcode,m_telphone,real_name,all_cost,is_verify,order_status,status,head_url';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array($field,$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		$member_info = D('ApiMember')->MemberGet($array_params);
		$member_list['member_list']['member'] = $member_info['items'];
		$member_list['total_results'] = $member_info['count'];
		$options = array();
		$options['root_tag'] = 'member_get_response';
		$this->result(true,10007,$member_list,"success",$options);
			
	}

	/**
	 * 会员新增接口
	 * request params
	 * @name String 必须  m_name
	 * @email String 必须
	 * @zipcode Number 选填
	 * @telphone Number 选填
	 * @mobile String 选填
	 * @address String 选填
	 * @area_name String 选填
	 * @birthday String 选填
	 * @sex String 选填
	 * @qq String 选填
	 * @wangwang String 选填
	 * @recommended String 选填
	 * @is_proxy String 选填
	 * @alipay_name String 选填
	 * @website_url String 选填
	 * @grade String 选填
	 * @is_verify String 选填
	 * @order_status String 选填
	 * @status String 选填
	 * @guid String 选填
	 * reponse params
	 * @is_success
	 * @author zhuyuanjie@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxMemberAdd($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["name"]) || "" == $array_params["name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if(!isset($array_params["password"]) || "" == $array_params["password"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:password');
		}
		if(!isset($array_params["rpassword"]) || "" == $array_params["rpassword"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:rpassword');
		}	
		if($array_params["password"] != $array_params["rpassword"]){
			$this->errorResult(false,10002,array(),'密码和确认密码一致');
		}
		if(!isset($array_params["email"]) || "" == $array_params["email"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:email');
		}
		
		$result =  D('ApiMember')->MemberAdd($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'member_add_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'新增用户失败');
		}
	}

	/**
	 * 会员修改接口
	 * request params
	 * @name String 必须  m_name
	 * @email String 选填
	 * @zipcode Number 选填
	 * @telphone Number 选填
	 * @mobile String 选填
	 * @address String 选填
	 * @area_name String 选填
	 * @birthday String 选填
	 * @sex String 选填
	 * @qq String 选填
	 * @wangwang String 选填
	 * @recommended String 选填
	 * @is_proxy String 选填
	 * @alipay_name String 选填
	 * @website_url String 选填
	 * @grade String 选填
	 * @is_verify String 选填
	 * @order_status String 选填
	 * @status String 选填
	 * @guid String 选填
	 * reponse params
	 * @is_success
	 * @author zhuyuanjie@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxMemberUpdate($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["name"]) || "" == $array_params["name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		$result =  D('ApiMember')->MemberUpdate($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'member_update_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'修改用户失败');
		}
	}


	/**
	 * 会员等级新增或修改接口
	 * request params
	 * @type String 必须    ADD：新建; UPDATE：更新;
	 * @code String 必须 ml_code 会员级别代码(不能修改，不能更新)
	 * @name Number 必须 ml_name 会员级别名称
	 * @discount Number 选填 ml_discount 折扣
	 * @status String 选填 ml_status 停用。( 0-非停用,1-停用 )
	 * @default String 选填 ml_default 是否是默认等级
	 * @created String 选填 ml_create_time 新增时间
	 * @erp_guid String 选填 ml_erp_guid erp_guid(不能修改，不能更新)
	 * reponse params
	 * @Date 创建时间
	 * @String 会员级别代码
	 * @author zhuyuanjie@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxMemberGradeManage($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["type"]) || "" == $array_params["type"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:type');
		}
		if(!isset($array_params["code"]) || "" == $array_params["code"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:code');
		}
		if(!isset($array_params["name"]) || "" == $array_params["name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if($array_params["type"] == 'ADD'){
			$mlresult = D('ApiMemberLevel')->AddMemberLevel($array_params);
		}else if($array_params["type"] == 'UPDATE'){
			$mlresult = D('ApiMemberLevel')->UpdateMemberLevel($array_params);
		}else{
			$this->errorResult(false,10002,array(),'type类型错误');
		}

		if($mlresult){
			$options = array();
			$options['root_tag'] = 'member_grade_manage_response';
			$this->result(true,10007,$mlresult,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'操作失败');
		}
	}

	/**
	 * 会员等级查询
	 * request params
	 * @type String 必须    ADD：新建; UPDATE：更新;
	 * @code String 必须 ml_code 会员级别代码(不能修改，不能更新)
	 * @name Number 必须 ml_name 会员级别名称
	 * @discount Number 选填 ml_discount 折扣
	 * @status String 选填 ml_status 停用。( 0-非停用,1-停用 )
	 * @default String 选填 ml_default 是否是默认等级
	 * @created String 选填 ml_create_time 新增时间
	 * @erp_guid String 选填 ml_erp_guid erp_guid(不能修改，不能更新)
	 * reponse params
	 * @Date 创建时间
	 * @String 会员级别代码
	 * @author zhuyuanjie@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function fxMemberGradeGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		/*
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
		$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'code,name,discount,status,default,created,erp_guid';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
		if(!in_array($field,$array_allow_field)){
		$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
		}
		}
		*/
		$ary_data['grade_lise']['grade'] = D('ApiMemberLevel')->MemberLevelGet($array_params);
		$ary_data['total_results'] = count($ary_data['grade_lise']['grade']);
		//print_r($ary_data);exit;
		$options = array();
		$options['root_tag'] = 'member_grade_manage_get';
		$this->result(true,10007,$ary_data,"success",$options);
	}


	/**
	 * 查询会员结余款
	 * request params
	 * @fields String 必须 需返回的字段列表。可选值：balance结构体中的所有字段；以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @balance_list 返回结果
	 * @total_results 搜索到符合条件的结果总数
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxBalanceGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'total,name,real_name,email,mobile,create_time,update_time';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array($field,$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		$ary_data = D('ApiBalance')->MemberBalanceGet($array_params);
		$options = array();
		$options['root_tag'] = 'balance_get_response';
		$this->result(true,10007,$ary_data,"success",$options);
	}

	/**
	 * 新增会员结余款调整单
	 * request params
	 * @hydm String 必须*会员代码
	 * @id 必须*外部来源单号
	 * @money 必须*调整金额。( 保留10位数,最大值可为 99999999.99 )
	 * @type_code 必须*调整类型：0为收入，1为支出，2为冻结
	 * @tradeno	o_id	String		test00000001
	 * @memo	bi_desc	String		testbz
	 * @bank	bi_accounts_bank	String		中国银行
	 * @bank_sn	bi_accounts_receivable	String		银行账号
	 * @payeec	bi_payeec	String		收款人
	 * @payment_time	bi_payment_time	String		付款时间
	 * @return_id	or_id	String		退款单号
	 * @sheetmaker	通过u_id处理	String		制单人
	 * @receive_id	ps_id	String		收款单号
	 * reponse params
	 * @created 创建时间
	 * @djbh 单据编号
	 * @id	             String		外部来源单号			
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxHuiyuanAddbalance($array_params=array()){
		//字段验证：需要验证是否必须输入
		if(!isset($array_params["name"]) || "" == $array_params["name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if(!isset($array_params["id"]) || "" == $array_params["id"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:id');
		}
		if(!isset($array_params["money"]) || "" == $array_params["money"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:money');
		}
		if(!isset($array_params["type_code"]) || "" == $array_params["type_code"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:type_code');
		}
		if(!isset($array_params["tradeno"]) || "" == $array_params["tradeno"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:tradeno');
		}						
		$ary_data = D('ApiBalance')->huiyuanAddbalance($array_params);
		//数据有误
		if($ary_data['status'] != '200'){
			$this->errorResult(false,10002,array(),$ary_data['msg']);
		}
		$options = array();
		$options['root_tag'] = 'huiyuan_addbalance_response';
		$this->result(true,200,$ary_data['data'],"success",$options);
	}
	
	/**
	 * 作废会员结余款调整单
	 * request params
	 * djbh	bi_sn	String	(分销单据编号和外部来源单号至少填一个)	171317132		分销单据编号
     * id	m_name	String	(分销单据编号和外部来源单号至少填一个)	171317132		外部来源单号
	 * reponse params
	 * @created 创建时间
	 * @djbh 单据编号
	 * @id	             String		外部来源单号			
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxHuiyuanCancelbalance($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(empty($array_params['djbh']) && empty($array_params['id'])){
			$this->errorResult(false,10002,array(),'分销单据编号和外部来源单号至少填一个');
		}
		$ary_data = D('ApiBalance')->huiyuanCancelbalance($array_params);
		//数据有误
		if($ary_data['status'] != '200'){
			$this->errorResult(false,10002,array(),$ary_data['msg']);
		}
		//print_r($ary_data);exit;
		$options = array();
		$options['root_tag'] = 'huiyuan_cancelbalance_response';
		$this->result(true,200,$ary_data['data'],"success",$options);
	}
	
	/**
	 * 修改会员结余款调整单
	 * request params
	 * @fields String 必须 需返回的字段列表。可选值：balance结构体中的所有字段；以半角逗号(,)分隔;
	 * @hydm	m_name	String	必须*	171317132		会员代码
	 * @id	bi_sn	String	必须*	jyktest000001		外部来源单号
	 * @money	bi_money	String		50		调整金额。( 保留10位数,最大值可为 99999999.99 )
	 * @type_code	bi_type	String		2	2	调整类型：0为收入，1为支出，2为冻结
	 * @tradeno	o_id	String		test00000001		交易号
	 * @memo	bi_desc	String		testbz		备注
	 * @bank	bi_accounts_bank	String		中国银行		银行
	 * @bank_sn	bi_accounts_receivable	String				银行账号
	 * @payeec	bi_payeec	String				收款人
	 * @payment_time	bi_payment_time	String				付款时间
	 * @return_id	or_id	String				退款单号
	 * @receive_id	ps_id	String				收款单号
	 * @verify_status	bi_verify_status	Number				作废状态：0未作废,2已作废
	 * @service_verify	bi_service_verify	Number				客审状态：0未审核，1已审核
	 * @finance_verify	bi_finance_verify	Number				财审状态：0未审核，1已审核
	 * @sheetmaker	通过u_id处理	String		制单人
	 * reponse params
	 * @created 创建时间
	 * @djbh 单据编号
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxHuiyuanModifybalance($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		$this->errorResult(false,10002,array(),"暂时不支持吆“{$field}”");
		/*
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
		$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'code,name,discount,status,default,created,erp_guid';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
		if(!in_array($field,$array_allow_field)){
		$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
		}
		}
		*/
		$ary_data['grade_lise']['grade'] = D('ApiMemberLevel')->MemberLevelGet($array_params);
		$ary_data['total_results'] = count($ary_data['grade_lise']['grade']);
		//print_r($ary_data);exit;
		$options = array();
		$options['root_tag'] = 'huiyuan_modifybalance_response';
		$this->result(true,10007,$ary_data,"success",$options);
	}

	/**
	 * 查询结余款流水账
	 * request params
	 * @fields String 必须 需返回的字段列表。以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @vjyktzdmx_list 返回结果
	 * return
	 *  hydm	m_name	String	必须*	171317132		会员代码
		id	bi_sn	String	必须*	jyktest000001		外部来源单号
		money	bi_money	String		50		调整金额。( 保留10位数,最大值可为 99999999.99 )
		type_code	bi_type	String		2	2	调整类型：0为收入，1为支出，2为冻结
		tradeno	o_id	String		test00000001		交易号
		memo	bi_desc	String		testbz		备注
		bank	bi_accounts_bank	String		中国银行		银行
		bank_sn	bi_accounts_receivable	String				银行账号
		payeec	bi_payeec	String				收款人
		payment_time	bi_payment_time	String				付款时间
		return_id	or_id	String				退款单号
		receive_id	ps_id	String				收款单号
		verify_status	bi_verify_status	Number				作废状态：0未作废,2已作废
		service_verify	bi_service_verify	Number				客审状态：0未审核，1已审核
		finance_verify	bi_finance_verify	Number				财审状态：0未审核，1已审核
		sheetmaker	通过u_id处理	String		制单人
		* @total_results 搜索到符合条件的结果总数
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxVjyktzdmxGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'name,djbh,id,money,type_code,tradeno,memo,bank,bank_sn,payeec,payment_time,sheetmaker,create_time,update_time';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array($field,$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		$ary_data = D('ApiBalance')->vjyktzdmxGet($array_params);
		//数据有误
		if($ary_data['status'] != '200'){
			$this->errorResult(false,10002,array(),$ary_data['msg']);
		}
		$options = array();
		$options['root_tag'] = 'vjyktzdmx_get_response';
		$this->result(true,10007,$ary_data['data'],"success",$options);
	}

	/**
	 * 查询结余款调整单
	 * request params
	 * @fields String 必须 需返回的字段列表。以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @vjyktzd_list 返回结果
	 * return
	 *  hydm	m_name	String	必须*	171317132		会员代码
		id	bi_sn	String	必须*	jyktest000001		外部来源单号
		money	bi_money	String		50		调整金额。( 保留10位数,最大值可为 99999999.99 )
		type_code	bi_type	String		2	2	调整类型：0为收入，1为支出，2为冻结
		tradeno	o_id	String		test00000001		交易号
		memo	bi_desc	String		testbz		备注
		bank	bi_accounts_bank	String		中国银行		银行
		bank_sn	bi_accounts_receivable	String				银行账号
		payeec	bi_payeec	String				收款人
		payment_time	bi_payment_time	String				付款时间
		return_id	or_id	String				退款单号
		receive_id	ps_id	String				收款单号
		verify_status	bi_verify_status	Number				作废状态：0未作废,2已作废
		service_verify	bi_service_verify	Number				客审状态：0未审核，1已审核
		finance_verify	bi_finance_verify	Number				财审状态：0未审核，1已审核
		sheetmaker	通过u_id处理	String		制单人
		* @total_results 搜索到符合条件的结果总数
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxVjyktzdGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'name,djbh,id,money,type_code,tradeno,tradeno,memo,bank,bank_sn,payeec,payment_time,sheetmaker,verify_status,service_verify,finance_verify,create_time,update_time';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array($field,$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		$ary_data = D('ApiBalance')->vjyktzdGet($array_params);
		//数据有误
		if($ary_data['status'] != '200'){
			$this->errorResult(false,10002,array(),$ary_data['msg']);
		}
		$options = array();
		$options = array();
		$options['root_tag'] = 'vjyktzd_get_response';
		$this->result(true,10007,$ary_data['data'],"success",$options);
	}

	/**
	 * 查询退换货单
	 * request params
	 * @operate_type  String 必须 	123456  支持批量，最多不超过40个。
	 * @store_code String 必须  商家的仓库编码，不允许重复，不允许更新:ABC0001
	 * @store_name  String 可选  商家的仓库名称，可更新:华北仓
	 * @alias_name  String 可选  仓库简称，可更新:京
	 * @address  String 可选  仓库的物理地址，可更新 :东大街000号
	 * @address_area_name   String 可选  仓库区域名，可更新:北京~北京市~崇文区
	 * @contact   String 可选  联系人，可更新:张三
	 * @phone   String 可选  联系电话，可更新 :13900000000
	 * @postcode   String 可选  邮编，可更新:100000
	 * reponse params
	 * @store
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-05
	 */
	private function fxTradeThdGet($array_params=array()){
	  
		//需返回的字段列表 fields
		if(!isset($array_params["fields"]) || "" == $array_params["fields"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数需返回的字段列表:fields');
		}
		if(!isset($array_params["page_size"]) || $array_params["page_size"]>200){
			$this->errorResult(false,10002,array(),'最大值：200');
		}
		
		//根据ERP_ID搜索
		if($array_params['erp_id']){
			$array_params['erp_id'] = trim($array_params['erp_id']);
		}
		//对字段进行限制：不允许下载非系统允许的字段
		$str_allow_fields = 'erp_id,or_id,m_id,outer_refundid,mail,refund_type,outer_tid,itemsns,nums,prices,pay_money';

		$str_ignore_fields = 'pay_codes,skusns,pay_moneys,pay_datatime,pay_account,buyer_memo,payee,logic_sn,seller_memo';
		$str_ignore_fields .= ',apply_name,apply_time,service_verify,finance_verify,finance_name,finance_time,status,refuse_reason';
		$str_ignore_fields .= ',bank,received_time,pay_type,created,modified,voucher';
		$array_client_get_fields = explode(',',$str_allow_fields);
		$array_ignore_client_get_fields = explode(',',$str_ignore_fields);


		if(isset($array_params["fields"]) && !empty($array_params["fields"])) {
			$array_tmp_client_fields = explode(",",$array_params["fields"]);
			$array_fields = array_merge($array_client_get_fields,$array_ignore_client_get_fields);
			foreach($array_tmp_client_fields as $val){
				if(!in_array($val,$array_fields)){
					$this->errorResult(false,10002,array(),"字段名“{$val}”不是系统允许获取的字段。");
					exit;
				}
				$array_client_get_fields[] = $val;
				$array_client_get_fields = array_unique($array_client_get_fields);
			}
		}


		//对退换货单管理操作
		$array_data = D('ApiTradeThd')->TradeThdGet($array_params,$array_client_get_fields);

		//所有验证完毕，返回数据
		$options = array();
		$options['root_tag'] = 'tradethd_response';
		$this->result(true,10007,$array_data,"success",$options);

	}


	/**
	 * 新增退换货单
	 * request params
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxTradeAddthd($array_params=array()){
		if(!isset($array_params["mail"]) || "" == $array_params["mail"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数会员代码定义:mail');
		}
		if(!isset($array_params["refund_type"]) || "" == $array_params["refund_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:refund_type');
		}
		if(1 != $array_params["refund_type"] && 2 != $array_params["refund_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是1,退款；2,退货;');
		}
		if(!isset($array_params["outer_tid"]) || "" == $array_params["outer_tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数订单来源单号定义:outer_tid');
		}
		if(!isset($array_params["outer_refundid"]) || "" == $array_params["outer_refundid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数网站退款/退货记录ID定义:outer_refundid');
		}

		if(2 == $array_params["refund_type"]) {
			if(!isset($array_params["itemsns"]) || "" == $array_params["itemsns"]){
				$this->errorResult(false,10002,array(),'缺少应用级参数退货商品代码定义:itemsns');
			}

			if(!isset($array_params["nums"]) || "" == $array_params["nums"]){
				$this->errorResult(false,10002,array(),'缺少应用级参数退货商品数量定义:nums');
			}

		}
		//对退换货单管理操作
		$array_data = D('ApiTradeThd')->addTradeThd($array_params,'ADD');
		//print_r($array_data);exit;
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'tradethd_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}

	/**
	 * 修改退换货单
	 * request params
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-06
	 */
	private function fxTradeModifythd($array_params=array()){
		if(!isset($array_params["mail"]) || "" == $array_params["mail"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数会员代码定义:mail');
		}
		if(!isset($array_params["refund_type"]) || "" == $array_params["refund_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:refund_type');
		}
		if(1 != $array_params["refund_type"] && 2 != $array_params["refund_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是1,退款；2,退货;');
		}
		if(!isset($array_params["outer_tid"]) || "" == $array_params["outer_tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数订单来源单号定义:outer_tid');
		}
		if(!isset($array_params["outer_refundid"]) || "" == $array_params["outer_refundid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数网站退款/退货记录ID定义:outer_refundid');
		}
		if(2 == $array_params["refund_type"]) {
			if(!isset($array_params["itemsns"]) || "" == $array_params["itemsns"]){
				$this->errorResult(false,10002,array(),'缺少应用级参数退货商品代码定义:itemsns');
			}
			if(!isset($array_params["nums"]) || "" == $array_params["nums"]){
				$this->errorResult(false,10002,array(),'缺少应用级参数退货商品数量定义:nums');
			}
		}

		if(!isset($array_params["pay_money"]) || "" == $array_params["pay_money"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数退款金额定义:pay_money');
		}

		//对退换货单管理操作
		$array_data = D('ApiTradeThd')->addTradeThd($array_params,'UPDATE');

		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'tradethd_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}

	}

    
    /**
	 * 修改订单接口
	 * request params
	 * @num_iid 	Number 必须 	3838293428		商品数字ID，必填参数
	 * @sku_id 	Number 可选 	1230005		要操作的SKU的数字ID，可选。如果不填默认修改宝贝的库存，如果填上则修改该SKU的库存
	 * @outer_id String 可选 		SKU的商家编码，可选参数。如果不填则默认修改宝贝的库存，如果填了则按照商家编码搜索出对应的SKU并修改库存。当sku_id和本字段都填写时以sku_id为准搜索对应SKU
	 * @quantity quantity 	Number 必须 	0		库存修改值，必选。当全量更新库存时，quantity必须为大于等于0的正整数；当增量更新库存时，quantity为整数，可小于等于0。若增量更新 时传入的库存为负数，则负数与实际库存之和不能小于0。比如当前实际库存为1，传入增量更新quantity=-1，库存改为0
	 * @ type 	Number 可选 	1	1	库存更新方式，可选。1为全量更新，2为增量更新。如果不填，默认为全量更新
	 * reponse params
	 * @item 	Item  		iid、numIid、num和modified，skus中每个sku的skuId、quantity和modified
	 * 宝贝含有销售属性，不能直接修改商品数量
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-07
	 */
	private function fxTradeModify($array_params=array()){
		//需要的字段列表 outer_tid
		if(!isset($array_params["outer_tid"]) || "" == $array_params["outer_tid"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数订单号:outer_tid');
		}
		
		if(!isset($array_params["itemsns"]) || "" == $array_params["itemsns"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品编码:itemsns');
		}
        if(!isset($array_params["prices"]) || "" == $array_params["prices"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品价格:prices');
		}
        if(!isset($array_params["nums"]) || "" == $array_params["nums"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品数量:nums');
		}
        if(!isset($array_params["combo_types"]) || "" == $array_params["combo_types"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品类型:combo_types');
		}
    
        if(!in_array($array_params["combo_types"],array(0,1))){
			$this->errorResult(false,10002,array(),'是否是组合商品必须是0或者1');
		}
        
        if(!isset($array_params["buyer_nick"]) || "" == $array_params["buyer_nick"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数买家账号:buyer_nick');
		}
        if(!isset($array_params["receiver_name"]) || "" == $array_params["receiver_name"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数收货人:receiver_name');
		}
        if(!isset($array_params["receiver_address"]) || "" == $array_params["receiver_address"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数收货地址:receiver_address');
		}
        if(!isset($array_params["receiver_state"]) || "" == $array_params["receiver_state"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数省:receiver_state');
		}
        
        if(!isset($array_params["receiver_city"]) || "" == $array_params["receiver_city"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数市:receiver_city');
		}
        if(!isset($array_params["receiver_district"]) || "" == $array_params["receiver_district"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数区:receiver_district');
		}
        if(isset($array_params["pre_sale"]) && !in_array($array_params["pre_sale"],array(0,1))) {
			$this->errorResult(false,10002,array(),'缺少应用级参数订单是否预售值必须是:0或者1');
		}
        if(isset($array_params["freeze"]) && !in_array($array_params["freeze"],array(0,1))) {
			$this->errorResult(false,10002,array(),'缺少应用级参数B2B冻结(延迟发货)必须是:0或者1');
		}
        if(isset($array_params["freeze"]) && $array_params["freeze"] ==1 && (!isset($array_params["unfreeze_time"]) || empty($array_params["unfreeze_time"]))) {
			$this->errorResult(false,10002,array(),'如果B2B冻结(延迟发货)字段freeze为1，那么这个时间参数unfreeze_time必须有值');
		}
		//订单数据修改
		$array_data = D('ApiOrders')->orderUpdate($array_params);
     
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}
	
	/**
	 * 库存初始化
	 * request params
	 * @erp_id			
	 * @store_code	String	必须	ABC0001
     * @items	String	必须	[{"scItemId":"12345","scItemCode":"GLY0001","inventoryType":"1","quantity":"111"}]
	 * reponse params
	 * @item_inventorys InventorySum []
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-10-17
	 */
	private function fxInventoryInitial($array_params=array()){
		//测试数据开始
// 		$item1 = array('scItemId'=>'222222',"scItemCode"=>"222222",'inventoryType'=>'1','quantity'=>'111');
// 		$item2 = array('scItemId'=>'2222222222',"scItemCode"=>"2222222222",'inventoryType'=>'1','quantity'=>'222');
// 		$items = array();
// 		$items[] = $item1;
// 		$items[] = $item2;
// 		$array_params['items'] = json_encode($items);
		//测试数据结束
		
		//验证是否传递erp_id参数
// 		if(!isset($array_params["erp_id"]) || "" == $array_params["erp_id"]){
// 			$this->errorResult(false,10002,array(),'缺少应用级参数后端商品id:erp_id');
// 		}
		
		//验证是否传递erp_ids参数
		if(!isset($array_params["store_code"]) || "" == $array_params["store_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数id:store_code');
		}
		
		//验证是否传递store_codes参数
		if(!isset($array_params["items"]) || "" == $array_params["items"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数id:items');
		}
	
		$array_data = D('ApiInventory')->addInventoryStorestocks($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败请判断商品库存信息是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_initial_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 非交易库存调整单 
	 * request params
	 * 业务操作时间	operate_time	g_sn或pdt_sn	必须	String	后端商品ID 列表，为商品规格编码和商品编码，控制到50个:1234^2456 
	 * 商家仓库编码	store_code	w_code	必须	String	GLY001
	 * 商品库存信息	items 		必须	String	商品初始库存信息： [{"scItemId":"商品编码或商品规格编码，如果有传scItemCode,参数可以为0","scItemCode":"商品商家编 码","inventoryType":"库存类型 1：正常,”direction”: 1: 盘盈 -1: 盘亏,参数可选,"quantity":"数量"}]
	 * reponse params
	 * @item_inventorys InventorySum []
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-10-17
	 */
	private function fxInventoryAdjustExternal($array_params=array()){
		//测试数据开始
// 				$item1 = array('scItemId'=>'222222',"scItemCode"=>"222222",'inventoryType'=>'1','direction'=>'-1','quantity'=>'11');
// 				$item2 = array('scItemId'=>'2222222222',"scItemCode"=>"2222222222",'inventoryType'=>'1','direction'=>'1','quantity'=>'9');
// 				$items = array();
// 				$items[] = $item1;
// 				$items[] = $item2;
// 				$array_params['items'] = json_encode($items);
		//测试数据结束
	
		//验证是否传递operate_time参数
		if(!isset($array_params["operate_time"]) || "" == $array_params["operate_time"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:operate_time');
		}
	
		//验证是否传递store_code参数
		if(!isset($array_params["store_code"]) || "" == $array_params["store_code"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数id:store_code');
		}
	
		//验证是否传递store_codes参数
		if(!isset($array_params["items"]) || "" == $array_params["items"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数id:items');
		}
	
		$array_data = D('ApiInventory')->modifyInventoryStorestocks($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败请判断商品库存信息是否存在');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_adjust_external_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}
	
	/**
	 * 创建或更新或删除区域价格信息
	 * request params
	 * @author wangguibin@guanyisoft.com
	 * @date 2014-05-14
	 */
	private function fxInventoryPriceManage($array_params=array()){
		//验证是否传递operate_type参数
		if(!isset($array_params["operate_type"]) || "" == $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数操作类型定义:operate_type');
		}
		if("UPDATE" != $array_params["operate_type"] && "DELETE" != $array_params["operate_type"]){
			$this->errorResult(false,10002,array(),'应用级参数操作类型必须是：UPDATE：新增或更新;DELETE：停用');
		}
		if($array_params["operate_type"] == 'UPDATE'){
			if(!isset($array_params["price"]) || "" == $array_params["price"] || empty($array_params["price"])){
				$this->errorResult(false,10002,array(),'缺少区域售价且区域售价金额不能为0');
			}		
		}
		if(!isset($array_params["cr_id"]) || "" == $array_params["cr_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数地址库ID:cr_id');
		}	
		if(!isset($array_params["outer_id"]) || "" == $array_params["outer_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品编码:outer_id');
		}	
		//对区域售价管理操作
		$array_data = D('ApiInventory')->addInventoryPrice($array_params);
		//print_r($array_data);exit;
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'inventory_price_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}
	
	/**
	 * 会员优惠券查询
	 * request params
	 * type 查询类型 1未使用 2已使用 3已过期
	 * member_name 会员名
	 * coupon_sn 优惠券编号
	 * coupon_end_time 优惠券到期时间
	 * reponse params
	 * @Date 创建时间
	 * @String 会员名
	 * @author wangguibin@guanyisoft.com
	 * @date 2014-07-09
	 */
	private function fxMemberCouponGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		/*
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
		$this->errorResult(false,10002,array(),'缺少应用级参数:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'code,name,discount,status,default,created,erp_guid';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
		if(!in_array($field,$array_allow_field)){
		$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
		}
		}
		*/
		$ary_data['coupon_list']['coupon'] = D('ApiMemberCoupon')->MemberCouponGet($array_params);
		$ary_data['total_results'] = count($ary_data['coupon_list']['coupon']);
		//print_r($ary_data);exit;
		$options = array();
		$options['root_tag'] = 'member_coupon_get';
		$this->result(true,10007,$ary_data,"success",$options);
	}

   /**
    * 会员批量新增,修改接口
    * request params
    * @members_list	String	必须    [{"name":"guanyisoft","mobile":"1380888888","card_no":"jlg072514","ali_card_no":"ali072514"}]
    * reponse params
    * @author Hcaijin
    * @date 2014-07-28
    */
	private function fxMembersBatchAdd($array_params=array()){
        #########测试数据开始###############
        /* $member1 = array('name'=>'hcaijin1',"ali_card_no"=>"ali1381653429143",'mobile'=>'13808888888','sex'=>'1');
        	$member2 = array('name'=>'hcaijin2',"ali_card_no"=>"ali1381653429144",'mobile'=>'13808888889','sex'=>'0');
        	$members = array();
        	$members[] = $member1;
        	$members[] = $member2;
            $array_params['members_list'] = json_encode($members); */
        #########测试数据结束###############
		//验证是否传递members_list参数
		if(!isset($array_params["members_list"]) || "" == $array_params["members_list"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:members_list');
		}
	
		$array_data = D('ApiJlg')->MemberBatchEdit($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'members_batch_edit_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 查询会员可用促销信息API:fx.promotions.get
    * @request params
    * name	String 会员代码	必须
    * @reponse params
    * point	Decimal 积分	
    * financial	Decimal 余额	
    * bonus	Decimal 红包	
    * jlb	Decimal 金币	
    * @author Hcaijin
    * @date 2014-08-01
    */
	private function fxPromotionsGet($array_params=array()){
		if(!isset($array_params["name"]) || "" == $array_params["name"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
	
		$array_data = D('ApiJlg')->PromotionsGet($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'member_promotions_get';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 查询会员可用优惠券信息API:fx.coupon.get
    * @request params
    * name	String 会员代码	必须
    * @reponse params
    * coupon_list coupon[] 优惠券	
    * total_results Number 优惠券总数	
    * @author Hcaijin
    * @date 2014-08-01
    */
	private function fxCouponGet($array_params=array()){
		if(!isset($array_params["name"]) || "" == $array_params["name"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
	
		$array_data = D('ApiJlg')->CouponGet($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'member_coupon_get';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 线下商品同步线上B2C操作API:fx.item.sync.online
    * @request params
    * outer_id	String 商品外部编码 必须
    * title	String 商品标题 必须
    * @reponse params
    * num_iid Number 商品数据ID
    * created Date 商品发布时间	
    * @author Hcaijin
    * @date 2014-08-01
    */
	private function fxItemSyncOnline($array_params=array()){
        if(!isset($array_params["outer_id"]) || "" == $array_params["outer_id"]){ 
            $this->errorResult(false,10002,array(),'缺少应用级参数:outer_id');
		}
		if(!isset($array_params["title"]) || "" == $array_params["title"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:title');
		}
	
		$array_data = D('ApiJlg')->addGood($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'item_add_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.use
    * @request params
    * name	String 会员代码 必须
    * item_list	Items[] 商品列表信息 必须
    * shopping_price	Decimal 商品总价 必须
    * type	String 判断是直接扣减还是查询优惠券使用(1,使用;默认为0,查询) 必须
    * point	Number 消费积分 不是必须
    * financial	Decimal 消费余额 不是必须
    * bonus	Decimal 消费红包 不是必须
    * jiulongbi	Decimal 消费金币 不是必须
    * @reponse params
    * order_money Decimal 生成的订单总金额
    * status Bool 返回是否成功状态	
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsUse($array_params=array()){
        if(!isset($array_params["name"]) || "" == $array_params["name"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
        if(!isset($array_params["item_list"]) || "" == $array_params["item_list"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:item_list');
		}
		if(!isset($array_params["shopping_price"]) || "" == $array_params["shopping_price"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:shopping_price');
		}
		$array_data = D('ApiJlg')->usePromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_use_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.doit
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsDoit($array_params=array()){
        if(!isset($array_params["trans_no"]) || "" == $array_params["trans_no"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:trans_no');
		}
        if(!isset($array_params["trans_money"]) || "" == $array_params["trans_money"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:trans_money');
		}
		$array_data = D('ApiJlg')->doitPromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_doit_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.cancel
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsCancel($array_params=array()){
        if(!isset($array_params["trans_no"]) || "" == $array_params["trans_no"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:trans_no');
		}
        if(!isset($array_params["trans_money"]) || "" == $array_params["trans_money"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:trans_money');
		}
		$array_data = D('ApiJlg')->cancelPromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_cancel_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.refund
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsRefund($array_params=array()){
        if(!isset($array_params["trans_no"]) || "" == $array_params["trans_no"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:trans_no');
		}
        if(!isset($array_params["name"]) || "" == $array_params["name"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
        if(!isset($array_params["item_list"]) || "" == $array_params["item_list"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:item_list');
		}
		$array_data = D('ApiJlg')->refundPromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_refund_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.refund.doit
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsRefundDoit($array_params=array()){
        if(!isset($array_params["refund_no"]) || "" == $array_params["refund_no"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:refund_no');
		}
		$array_data = D('ApiJlg')->refundDoitPromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_refund_doit_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 使用促销券额操作API:fx.promotions.refund.cancel
    * @author Hcaijin
    * @date 2014-08-05
    */
	private function fxPromotionsRefundCancel($array_params=array()){
        if(!isset($array_params["refund_no"]) || "" == $array_params["refund_no"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:refund_no');
		}
		$array_data = D('ApiJlg')->refundCancelPromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'批量更新失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_refund_cancel_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

   /**
    * 售价调整操作API:fx.update.price.api
    * @author Hcaijin
    * @date 2014-08-05
    */
	/**
	 * 更新商品价格信息
	 * request params
	 * @author Hcaijin
	 * @date 2014-08-21
	 */
	private function fxUpdatePriceApi($array_params=array()){
        if(!isset($array_params["price"]) || "" == $array_params["price"] || empty($array_params["price"])){
            $this->errorResult(false,10002,array(),'缺少售价且售价金额不能为空');
        }		
		if(!isset($array_params["sku_outer_id"]) || "" == $array_params["sku_outer_id"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数商品编码:sku_outer_id');
		}	
		//对区域售价管理操作
		$array_data = D('ApiJlg')->addUpdatePrice($array_params);
		//print_r($array_data);exit;
		if(isset($array_data['msg']) && !empty($array_data['msg'])){
			$this->errorResult(false,10002,array(),$array_data['msg']);
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'update_price_manage_response';
			$this->result(true,10007,$array_data['data'],"success",$options);
		}
	}

   /**
    * APP查询会员可用优惠券信息API:fx.coupon.app.get
    * @request params
    * name	String 会员代码	必须
    * @reponse params
    * coupon_list coupon[] 优惠券	
    * total_results Number 优惠券总数	
    * @author Hcaijin
    * @date 2014-08-01
    */
	private function fxCouponAppGet($array_params=array()){
		if(!isset($array_params["name"]) || "" == $array_params["name"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if(!isset($array_params["shopping_price"]) || "" == $array_params["shopping_price"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:shopping_price');
		}
		if(!isset($array_params["item_list"]) || "" == $array_params["item_list"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:item_list');
		}
	
		$array_data = D('ApiJlg')->appGetCoupon($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'获取优惠券失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'member_coupon_app_get';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	private function fxPromotionsAppUse($array_params=array()){
        if(!isset($array_params["name"]) || "" == $array_params["name"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
        if(!isset($array_params["goods_pids"]) || "" == $array_params["goods_pids"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:goods_pids');
		}
        if(!isset($array_params["ra_id"]) || "" == $array_params["ra_id"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:ra_id');
		}
        if(!isset($array_params["lt_id"]) || "" == $array_params["lt_id"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:lt_id');
		}
        if(!isset($array_params["cost_freight"]) || "" == $array_params["cost_freight"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:cost_freight');
		}
        if(!isset($array_params["cart_list"]) || "" == $array_params["cart_list"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:cart_list');
		}
		if(!isset($array_params["shopping_price"]) || "" == $array_params["shopping_price"]){
			$this->errorResult(false,10002,array(),'缺少应用级参数:shopping_price');
		}
		$array_data = D('ApiJlg')->appUsePromotions($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'生成订单失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'promotions_app_use_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	private function fxLogisticTypeGet($array_params=array()){
        if(!isset($array_params["name"]) || "" == $array_params["name"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
        if(!isset($array_params["ra_id"]) || "" == $array_params["ra_id"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:ra_id');
		}
        if(!isset($array_params["cr_id"]) || "" == $array_params["cr_id"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:cr_id');
		}
        if(!isset($array_params["cart_list"]) || "" == $array_params["cart_list"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数:cart_list');
		}
		$array_data = D('ApiJlg')->getLogisticList($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'生成订单失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'logistic_get_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 会员积分调整接口
	 */
	private function fxSyncPoint($array_params=array()){
		if(!isset($array_params["name"]) || "" == $array_params["name"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if(!isset($array_params["point"]) || "" == $array_params["point"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:point');
		}
		$array_data = D('ApiJlg')->syncPoint($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'积分调整失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'sync_point_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 对接APP会员积分调整接口
	 */
	private function fxSyncAppPoint($array_params=array()){
		if(!isset($array_params["consumers_id"]) || "" == $array_params["consumers_id"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:consumers_id');
		}
		if(!isset($array_params["merchant_id"]) || "" == $array_params["merchant_id"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:merchant_id');
		}
		if(!isset($array_params["start_time"]) || "" == $array_params["start_time"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:start_time');
		}
		if(!isset($array_params["end_time"]) || "" == $array_params["end_time"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:end_time');
		}
		$array_data = D('ApiJlg')->syncAppPoint($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'积分调整失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'sync_point_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 获取门店列表
	 */
	private function fxCategoryGet($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["fields"]) || "" == $array_params["fields"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数返回的订单信息:fields');
		}
		//验证要获取的字段是否是允许获取的字段中
		$string_allow_fields = 'gc_id,gc_name,gc_type,thd_catid,gc_description';
		$array_allow_field = explode(',',$string_allow_fields);
		$array_client_field = explode(',',$array_params["fields"]);
		foreach($array_client_field as $field){
			if(!in_array(trim($field),$array_allow_field)){
				$this->errorResult(false,10002,array(),"非法的字段数据获取“{$field}”");
			}
		}
		//获得门店信息
		$array_data = D('ApiJlg')->getCategory($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'查询数据为空');
		}else{
			//所有验证完毕，返回数据
			$options = array();
			$options['root_tag'] = 'category_get_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 查询可用找回密码方式列表
	 */
	private function fxSynReset($array_params=array()){
		if(!isset($array_params["forget"]) || "" == $array_params["forget"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:forget');
		}
		$array_data = D('ApiJlg')->synReset($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'接口调用失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'syn_reset_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}
	/**
	 * 找回密码接口
	 */
	private function fxFindPwd($array_params=array()){
		if(!isset($array_params["name"]) || "" == $array_params["name"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		if((!isset($array_params["email"]) || "" == $array_params["email"]) && (!isset($array_params["mobile"]) || "" == $array_params["mobile"])){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:email 或者 mobile ,至少填一个');
		}
		$array_data = D('ApiJlg')->findPassword($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'接口调用失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'find_password_response';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

	/**
	 * 手机验证码重置密码接口
	 */
	private function fxResetByMobile($array_params=array()){
		if(!isset($array_params["mobile_code"]) || "" == $array_params["mobile_code"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:mobile_code');
		}
		if(!isset($array_params["mobile"]) || "" == $array_params["mobile"]){
		    $this->errorResult(false,10002,array(),'缺少应用级参数:mobile');
		}
		$array_data = D('ApiJlg')->resetByMobile($array_params);
		if(empty($array_data)){
			$this->errorResult(false,10002,array(),'接口调用失败！');
		}else{
			$options = array();
			$options['root_tag'] = 'reset_by_mobile_reponse';
			$this->result(true,10007,$array_data,"success",$options);
		}
	}

    /**
     * 批量同步分类接口
     */
	private function fxItemCategoryBatchSyn($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["category_list"]) || "" == $array_params["category_list"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:category_list');
		}
		$result =  D('ApiJlg')->categoryBatchSyn($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'batch_syn_category_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'批量同步商品分类失败');
		}
	}

    /**
     * 同步分类接口
     */
	private function fxItemCategorySyn($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["cid"]) || "" == $array_params["cid"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:cid');
		}
		if(!isset($array_params["name"]) || "" == $array_params["name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:name');
		}
		
		$result =  D('ApiJlg')->categorySyn($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'syn_category_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'同步商品分类失败');
		}
	}

    /**
     * 同步商品品牌接口
     */
	private function fxItemBrandSyn($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["brand_sn"]) || "" == $array_params["brand_sn"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:brand_sn');
		}
		if(!isset($array_params["brand_name"]) || "" == $array_params["brand_name"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:brand_name');
		}
		
		$result =  D('ApiJlg')->brandSyn($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'syn_brand_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'同步商品品牌失败');
		}
	}

    /**
     * 批量同步分类接口
     */
	private function fxItemBrandBatchSyn($array_params=array()){
		//字段验证：需要验证是否指定要获取的字段和制定的字段是否合法
		if(!isset($array_params["brand_list"]) || "" == $array_params["brand_list"] ){
			$this->errorResult(false,10002,array(),'缺少应用级参数:brand_list');
		}
		$result =  D('ApiJlg')->brandBatchSyn($array_params);
		if($result){
			$options = array();
			$options['root_tag'] = 'batch_syn_brand_response';
			$this->result(true,10007,$result,"success",$options);
		}else{
			$this->errorResult(false,10002,array(),'批量同步商品品牌失败');
		}
	}
    
// +----------------------------------------------------------------------
// | 商品相关API START
// +----------------------------------------------------------------------
// | Author: wanghaijun
// +----------------------------------------------------------------------
 
    /**
	 * 添加商品评论
	 * @wanghaijun
	 * @param (array)$param                     = array(
	 *                        'm_id'            => 会员ID (必填)
	 *                        'g_id'            => 商品ID (必填)
	 *                        'o_id'            => 订单ID(必填)
	 *                        'gcom_title'      => 评论标题 (选填)
	 *                        'gcom_content'    => 评论内容 (必填)
	 *                        'gcom_star_score' => 评分 (选填)
	 * 				);
	 * @date 2014-12-12
	 */
	private function fxAddGoodsComment($params=null){
		writeLog("商品评论请求参数\t". json_encode($params), 'fxAddGoodsComment' . date('Y_m_d') . '.log');
		if(!isset($params['m_id']) || "" == $params['m_id']){
			$this->errorResult(false,10002,array(),'请填写会员ID');
		}

		if(!isset($params['g_id']) || "" == $params['g_id']){
			$this->errorResult(false,10002,array(),'请填写商品ID');
		}
		if(!isset($params['o_id']) || "" == $params['o_id']){
			$this->errorResult(false,10002,array(),'请填写订单ID');
		}
        if(!isset($params['gcom_content']) || empty($params['gcom_content'])){
			$this->errorResult(false,10002,array(),'请填写评论内容');
		}
		$ary_result = D('ApiComment')->InsertComment($params);
		writeLog("商品评论返回参数\t". json_encode($ary_result), 'fxAddGoodsComment' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('AddGoodsComment',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Comment_goods_add_response',
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 获取商品规格
	 * @param (array) $params
	 * @example $params = array(
	 *          (int)'g_id' => 商品ID
	 * );
	 * @author wanghaijun
	 * @date 2014-12-12
	 */
	private function fxGoodSpecGet($params=null){
		writeLog("获取规格请求参数\t". json_encode($params), 'fxGoodSpecGet' . date('Y_m_d') . '.log');
		$int_g_id = isset($params['g_id']) ? intval($params['g_id']) : 0;
		if(empty($int_g_id)){
			$this->errorResult(false,10301,array(),'请填写产品GID');
		}
		$condition = array(
			'g_id' => $int_g_id
			);
		$ary_result = D('ApiGoodsSpec')->getGoodsSpecByGid($condition);
		writeLog("获取规格返回数据\t". json_encode($ary_result), 'fxGoodSpecGet' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('GoodSpecApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Spec_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 获取规格信息
	 * @param (array) $params
	 * @example $params = array(
	 *          (int)'g_id' => 商品ID (必填)(12)
	 *          (string)'spec' => 规格属性 (必填) (属性名:属性值;属性名:属性值)(产地:上海;包装:纸袋)
	 * );
	 * @author wanghaijun
	 * @date 2014-12-12
	 */
	private function fxGetPdtDetail($params=null){
		writeLog("获取规格信息请求参数\t". json_encode($params), 'fxGetPdtDetail' . date('Y_m_d') . '.log');
		$int_g_id = isset($params['g_id']) ? intval($params['g_id']) : 0;
		if(empty($int_g_id)){
			$this->errorResult(false,10301,array(),'请填写产品ID');
		}
		if(!isset($params['spec']) || "" == $params['spec']){
			$this->errorResult(false,10301,array(),'请填写产品规格');
		}

		$condition = array(
			'g_id' => $int_g_id,
			'spec' => $params['spec']
			);
		$ary_result = D('ApiGoodsSpec')->GoodsSpecDetail($condition);
		writeLog("获取规格信息返回数据\t". json_encode($ary_result), 'fxGetPdtDetail' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('GetPdtDetailApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Spec_detail_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 获取商品分类
	 * @author wanghaijun
	 * @date 2014-12-12
	 */
	private function fxGetGoodsCat(){
        $goods_cat = D("ViewGoods")->getInfo();
        $this->result(true,10007,$goods_cat,"success");
    }
    
###################### 商品相关API END ###################################
    
// +----------------------------------------------------------------------
// | 会员相关API START
// +----------------------------------------------------------------------
// | Author: wanghaijun
// +----------------------------------------------------------------------

    /**
	 * [注册接口]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (string) 'm_mobile' => 手机号码 (必填)
	 *          (string) 'm_password' => 密码 (必填)
	 *          (string) 'm_nickname' => 用户名 (必填)
	 *          (string) 'm_email' => 邮箱 (必填)
	 * );
	 * @return [type]         [description]
     * @date 2014-12-13
	 */
	private function fxMemberRegister($params=null){
        writeLog("注册接口请求参数\t". json_encode($params), 'fxMemberRegister' . date('Y_m_d') . '.log');

        //验证用户名是否输入
		if (!isset($params['m_nickname']) || "" == $params['m_nickname']) {
            $this->errorResult(false,10002,array(),'用户名不能为空');
			exit;
		}

		//验证用户名的唯一性
		if (D('Members')->checkName($params['m_nickname'])) {
            $this->errorResult(false,10002,array(),'用户名已经存在');
			exit;
		}

		//验证是否输入密码和确认密码
		if (!isset($params["m_password"]) || "" == $params["m_password"]) {
            $this->errorResult(false,10002,array(),'请设置一个密码。');
			exit;
		}

		//验证是否输入会员邮箱
		if (!isset($params['m_email']) || "" == $params['m_email']) {
            $this->errorResult(false,10002,array(),'用户邮箱不能为空');
			exit;
		}

		//验证会员邮箱地址的合法性
		$email_preg = '/^[a-z0-9._%+-]+@(?:[a-z0-9-]+.)+[a-z]{2,4}$/i';
		if (false == preg_match($email_preg, $params['m_email'])) {
            $this->errorResult(false,10002,array(),'邮箱格式不合法。');
			exit;
		}

		//验证邮箱的唯一性
		if (D('Members')->checkEmail($params['m_email'])) {
            $this->errorResult(false,10002,array(),'用户邮箱已经存在');
			exit;
		}
        
        $member = D('Members');
        //获取默认配置的会员等级
        $ml = D('MembersLevel')->getSelectedLevel();
        //拼接数组
        $ary_member = array(
                'm_name' => trim($params['m_nickname']),
                'm_password' => md5($params['m_password']),
                'm_mobile' => $params['m_mobile'],
                'm_email' => $params['m_email'],
                'm_create_time' => date('Y-m-d H:i:s'),
                'ml_id' =>  $ml,
                'm_status' => '1'
            );
        $data = D('SysConfig')->getCfgByModule('MEMBER_SET');
        if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
            $ary_member['m_verify'] = '2';
        }
        //注册奖励积分
        $obj_point = D('PointConfig');
        $int_point = $obj_point->getConfigs('regist_points');
        if(null !== $int_point && is_numeric($int_point) && $int_point>0) {
            $ary_member['total_point'] = intval($int_point);
        }
        //扩展攻击
		foreach($ary_member as &$str_member){
			$str_member = htmlspecialchars($str_member);
			$str_member = RemoveXSS($str_member);
		}
        //会员基本资料入库
		$mixed_member_id = D("Members")->add($ary_member);
		if (false === $mixed_member_id) {
            $this->errorResult(false,10002,array(),'会员资料添加失败。');
			exit;
		}
        $ary_member = $member->getInfo(trim($params['m_nickname']));

        $options = array(
            'root_tag' => 'Member_register_response'
            );
        $this->result(true,'10007',$ary_member,"success",$options);
	}
    
    /**
	 * 会员登陆API
	 * @param (array) $param
	 * @example array(
	 *          'm_name' => 会员名称 (必填)
	 *          'm_password' => 密码 (必填)
	 * )
	 * @wanghaijun
	 * @date 2014-12-13
	 */
	private function fxDoLogin($params=null){
		writeLog("会员登陆请求参数\t". json_encode($params), 'fxDoLogin' . date('Y_m_d') . '.log');
		if(!isset($params['m_name']) || "" == $params['m_name']){
			$this->errorResult(false,10201,array(),'请填写用户名!');
		}
		if(!isset($params['m_password']) || "" == $params['m_password']){
			$this->errorResult(false,10201,array(),'请填写密码');
		}
		$ary_condition = array(
			'm_name' => $params['m_name'],
			'm_password' => $params['m_password']
			);
		$ary_result = D('ApiMember')->doLogin($ary_condition);
		writeLog("会员登陆返回参数\t". json_encode($ary_result), 'fxDoLogin' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('loginApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Login_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 获取用户可用收货地址
	 * @param (array) $params
	 * @example $params = array(
	 *          'm_id' => 用户ID (必填)(19)
	 * );
	 * @wanghaijun
	 * @date 2014-12-13
	 */
	private function fxMemberAddressGet($params=null){
		writeLog("获取用户可用收货地址请求参数\t". json_encode($params), 'fxMemberAddressGet' . date('Y_m_d') . '.log');
		$int_m_id = isset($params['m_id']) ? intval($params['m_id']) : 0;
		if(empty($int_m_id)){
			$this->errorResult(false,10401,array(),'请填写用户ID');
		}
		$condition = array(
			'm_id' => $int_m_id,
			);
		$ary_result = D('ApiMemberAddress')->getMemberAddress($condition);
		writeLog("获取用户可用收货地址返回数据\t". json_encode($ary_result), 'fxMemberAddressGet' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('MemberAddressApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Member_address_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}

    /**
	 * 删除收货地址
	 * @param (array) $params
	 * @example array(
	 *          'm_id' => 用户ID (必填)
	 *          'ra_id' => 地址ID (必填)
	 * );
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-6
	 */
	private function fxMemberAddressDel($params=null){
		writeLog("删除收货地址请求参数\t". json_encode($params), 'fxMemberAddressDel' . date('Y_m_d') . '.log');
		$int_m_id  = max(0,(int)$params['m_id']);
		$int_ra_id = max(0,(int)$params['ra_id']);
		if(empty($int_m_id)){
			$this->errorResult(false,10401,array(),'请填写用户ID');
		}
		if(empty($int_ra_id)){
			$this->errorResult(false,10401,array(),'请填写地址ID');
		}
		$condition = array(
			'm_id'  => $int_m_id,
			'ra_id' => $int_ra_id
			);
		$ary_result = D('ApiMemberAddress')->DeleteMemberAddress($condition);
		writeLog("删除收货地址返回数据\t". json_encode($ary_result), 'fxMemberAddressDel' . date('Y_m_d') . '.log');
		if($ary_result['status'] !== true){
			$this->logs('MemberAddressApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Member_address_delete_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 新增用户收货地址
	 * @param (array)$params = array(
	 *          (int) 'm_id'               => 会员ID(必填),
	 *          (string) 'ra_name'         => 收货人 (必填),
	 *          (int) 'province_id'        => 省级ID (选填),
	 *          (int) 'city_id'            => 市级ID (与区县必填一),
	 *          (int) 'district_id'        => 区县级ID (与城市必填一),
	 *          (string) 'ra_detail'       => 详细地址 (必填),
	 *          (string) 'ra_mobile_phone' => 手机号 (与电话号码必填一),
	 *          (string) 'ra_phone'        => 电话号码 (与手机号必填一)
	 *          (int) 'ra_post_code'       => 邮政编码 (选填)
	 *          (int) 'ra_is_default'      => 是否为默认地址 (选填)
	 * );
	 * @date 2014-12-13
	 */
	private function fxMemberAddressAdd($params=null){
		writeLog("新增用户收货地址请求参数\t". json_encode($params), 'fxMemberAddressAdd' . date('Y_m_d') . '.log');
		$condition = $this->checkaddress($params);
		$ary_result = D('ApiMemberAddress')->InsertMemberAddress($condition);
		writeLog("新增用户收货地址返回数据\t". json_encode($ary_result), 'fxMemberAddressAdd' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('MemberAddressAddApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Member_address_add_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 修改用户收货地址
	 * @param (array)$params = array(
	 *          (int) 'ra_id'              => 修改地址ID(必填)
	 *          (int) 'm_id'               => 会员ID(必填),
	 *          (string) 'ra_name'         => 收货人 (必填),
	 *          (int) 'province_id'        => 省级ID (选填),
	 *          (int) 'city_id'            => 市级ID (与区县必填一),
	 *          (int) 'district_id'        => 区县级ID (与城市必填一),
	 *          (string) 'ra_detail'       => 详细地址 (必填),
	 *          (string) 'ra_mobile_phone' => 手机号 (与电话号码必填一),
	 *          (string) 'ra_phone'        => 电话号码 (与手机号必填一)
	 *          (int) 'ra_post_code'       => 邮政编码 (选填)
	 *          (int) 'ra_is_default'      => 是否为默认地址 (选填)
	 * );
	 * @date 2014-12-13
	 */
	private function fxMemberAddressEdit($params=null){
		writeLog("修改用户收货地址请求参数\t". json_encode($params), 'fxMemberAddressEdit' . date('Y_m_d') . '.log');
		$int_ra_id = isset($params['ra_id']) ? (int)$params['ra_id'] : 0;
		if(empty($int_ra_id)){
			$this->errorResult(false,10401,array(),'请填写修改地址ID');
		}
		$condition = $this->checkaddress($params);
		$condition['ra_id'] = $int_ra_id;
		$ary_result = D('ApiMemberAddress')->EditMemberAddress($condition);
		writeLog("修改用户收货地址返回数据\t". json_encode($ary_result), 'fxMemberAddressEdit' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('MemberAddressEditApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Member_address_add_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * [修改密码]
	 * @param (array) $params
	 * @example array(
	 *          (int) 'm_id' => 用户ID (必填)
	 *          (string) 'original_password' => 原密码 (必填)
	 *          (string) 'new_password' => 新密码 (必填)
	 * );
	 * @return [type] [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-14
	 */
	private function fxEditMemberPassword($params = null){
		writeLog("修改密码请求参数\t". json_encode($params), 'fxEditMemberPassword' . date('Y_m_d') . '.log');
		$int_m_id              = max(0,$params['m_id']);
		$str_new_password      = $params['new_password'];
		$str_original_password = $params['original_password'];
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($str_original_password) || strlen($str_original_password) != 32){
			$this->errorResult(false,10101,array(),'请填写原密码!');
		}
		if(empty($str_new_password) || strlen($str_new_password) != 32){
			$this->errorResult(false,10101,array(),'请填写新密码!');
		}
		if($str_new_password == $str_original_password){
			$this->errorResult(false,10101,array(),'修改密码不能和原密码一致!');
		}
		// 数据库操作
		$condition = array(
			'm_id'              => $int_m_id,
			'new_password'      => $str_new_password,
			'original_password' => $str_original_password
			);
		$ary_result = D('ApiMemberAddress')->EditMemberPassword($condition);
		writeLog("修改密码返回数据\t". json_encode($ary_result), 'fxEditMemberPassword' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('EditMemberPasswordApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Member_password_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * 检验收货地址数据
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-3
	 */
	private function checkaddress($params=null){
		// 1.验证数据是否为空以及合法性
		$int_m_id            = isset($params['m_id'])            ? intval($params['m_id'])          : 0;
		$str_ra_name         = isset($params['ra_name'])         ? trim($params['ra_name'])         : '';
		$str_ra_detail       = isset($params['ra_detail'])       ? trim($params['ra_detail'])       : '';
		$str_ra_mobile_phone = isset($params['ra_mobile_phone']) ? trim($params['ra_mobile_phone']) : '';
		$str_ra_phone        = isset($params['ra_phone'])        ? trim($params['ra_phone'])        : '';
		$int_ra_post_code    = isset($params['ra_post_code'])    ? intval($params['ra_post_code'])  : 0;
		$int_ra_is_default   = isset($params['ra_is_default'])   ? intval($params['ra_is_default']) : 0;
		$int_cr_id           = $cr_id = isset($params['district_id']) &&  (int)$params['district_id'] ? (int)$params['district_id'] : (isset($params['city_id']) ? (int)$params['city_id'] : 0);
		if(empty($int_m_id)){
			$this->errorResult(false,10401,array(),'请填写用户ID');
		}
		if(empty($str_ra_name)){
			$this->errorResult(false,10401,array(),'请填写收货人');
		}
		if(empty($int_cr_id)){
			$this->errorResult(false,10401,array(),'请选择地址区域信息!');
		}
		if(empty($str_ra_detail)){
			$this->errorResult(false,10401,array(),'请输入街道信息');
		}
		if(empty($str_ra_mobile_phone) && empty($str_ra_phone)){
			$this->errorResult(false,10401,array(),'请填写手机或者电话号码!');
		}
		if(!empty($str_ra_mobile_phone) && !preg_match("/^((\+86)|(86))?(1)\d{10}$/",$str_ra_mobile_phone)){
			$this->errorResult(false,10401,array(),'请填写正确的手机号码!');
		}
		if(!empty($str_ra_phone) && !preg_match("/^(\d{3,4}-?)?\d{7,9}$/",$str_ra_phone)){
			$this->errorResult(false,10401,array(),'请填写正确的电话号码!');
		}
		if(!empty($int_ra_post_code) && !preg_match("/^\d{6}$/",$int_ra_post_code)){
			$this->errorResult(false,10401,array(),'请填写正确的邮政编码!');
		}
		$condition = array(
			'ra_name'         => $str_ra_name,
			'cr_id'           => $int_cr_id,
			'ra_detail'       => $str_ra_detail,
			'ra_phone'        => $str_ra_phone,
			'ra_mobile_phone' => $str_ra_mobile_phone,
			'ra_post_code'    => $int_ra_post_code,
			'm_id'            => $int_m_id,
			'ra_is_default'   => $int_ra_is_default
			);
		return $condition;
	}
    
########################## 会员相关API END #############################################

// +----------------------------------------------------------------------
// | 购物车相关API START
// +----------------------------------------------------------------------
// | Author: wanghaijun
// +----------------------------------------------------------------------
 
    /**
	 * 加入购物车
	 * @param (array)$param
	 * @example array(
	 *          (int)'m_id' => 会员ID (必填)
	 *          (int)'pdt_id' => 规格ID (必填)
	 *          (int)'num' => 数量 (必填)
	 * )
	 * @author wanghaijun
	 * @date 2014-12-15
	 */
	private function fxAddCart($params=null){
		writeLog("加入购物车请求参数\t". json_encode($params), 'fxAddCart' . date('Y_m_d') . '.log');
		$int_m_id   = isset($params['m_id'])   ? intval($params['m_id'])   : 0;
		$int_pdt_id = isset($params['pdt_id']) ? intval($params['pdt_id']) : 0;
		$int_num    = isset($params['num'])    ? intval($params['num'])    : 0;
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写会员ID');
		}
		if(empty($int_pdt_id)){
			$this->errorResult(false,10101,array(),'请填写规格ID');
		}
		if(empty($int_num)){
			$this->errorResult(false,10101,array(),'请填写购买数量');
		}
		$ary_cart = array(
            $int_pdt_id  => $int_num
		);
        //$type=item&num=1&pdt_id=111
        //过滤一遍数据，以防有小于0的或者不是数字的
        $ary_db_carts = array();
        $car_key = base64_encode( 'mycart' . $int_m_id);
        $ApiCarts = D('ApiCarts');
        $ary_db_carts = $ApiCarts->GetData($car_key);
        foreach ($ary_cart as $key => $int_num) {
            if ($int_num <= 0 || !is_int($int_num)) {
                unset($ary_cart[$key]);
            }
            $goods_info = D('GoodsProducts')->GetProductList(array('fx_goods_products.pdt_id' => $key), array('fx_goods.g_is_combination_goods', 'fx_goods.g_gifts', 'fx_goods.g_id'));

            if ($goods_info[0]['g_gifts'] == 1) {
                $this->errorResult(false,10102,array(),'赠品不能购买！');
                return false;
            }
            if (array_key_exists($key, $ary_db_carts)) {
                $ary_db_carts[$key]['num']+=$int_num;
            } else {
                $ary_db_carts[$key] = array('pdt_id' => $key, 'num' => $int_num, 'g_id' => $goods_info[0]['g_id']);
            }
        }

        $Cart = $ApiCarts->WriteMycart($ary_db_carts,$car_key);

		if($Cart !== true){
			$this->errorResult(false,10102,array(),'加入购物车失败!');
		}else{
			$options = array(
				'root_tag' => 'Cart_add_response',
				);
			$this->result(true,10103,array('success'),"",$options);
		}
	}
    
    /**
	 * 获取购物车商品
	 * @param  [array] $params [description]
	 * @example $params = array(
	 *          (int) 'm_id' => 会员ID (必填)
	 * );
	 */
	private function fxCartsGet($params=null){
		writeLog("获取购物车商品请求参数\t". json_encode($params), 'fxCartsGet' . date('Y_m_d') . '.log');
		$int_m_id = isset($params['m_id']) ? (int)$params['m_id'] : 0;

		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写会员ID!');
		}
        $ApiCarts = D('ApiCarts');
        $cart_data = $ApiCarts->GetData(base64_encode( 'mycart' . $int_m_id));
        if(!empty($cart_data) && is_array($cart_data)){
            foreach($cart_data as &$val){
                if($val['type'] == '0'){
                    $ary_gid = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('pdt_id'=>$val['pdt_id']))->find();
                    $val['g_id'] = $ary_gid['g_id'];
                }
            }
        }
        
        foreach ($cart_data as $key => $value) {
            if ($key == 'gifts') {
                unset($cart_data[$key]);
            } else {
                $pdt_id = $value['pdt_id'];
                $int_type = isset($value['type']) ? $value['type'] : 0;
                $ary_pdt[$key] = array('pdt_id' => $pdt_id, 'num' => $value['num'], 'type' => $int_type, 'fc_id' => $value['fc_id']);
            }
        }
        if (is_array($ary_pdt) && !empty($ary_pdt)) {
            $ary_cart_data = $ApiCarts->getProductInfo($ary_pdt, $ary_price_data['all_pdt_price']);
        }

		writeLog("获取购物车商品返回数据\t". json_encode($ary_cart_data), 'fxCartsGet' . date('Y_m_d') . '.log');
		// 返回结果
		if(!empty($ary_cart_data) && is_array($ary_cart_data)){
			$options = array(
				'root_tag' => 'Carts_response'
				);
			$this->result(true,10105,$ary_cart_data,"success",$options);
		}else{
            $this->logs('CartsGetApi','获取购物车失败!');
			$this->errorResult(false,10102,array(),'获取购物车失败!');
		}
	}
    
    /**
	 * [删除购物车商品]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'pdt_id' => 规格ID
	 *          (int) 'm_id' => 会员ID
	 * );
	 */
	private function fxRemoveCart($params=null){
		writeLog("删除购物车商品请求参数\t". json_encode($params), 'fxRemoveCart' . date('Y_m_d') . '.log');
		$int_pdt_id  = max(0,(int)$params['pdt_id']);
		$int_m_id    = max(0,(int)$params['m_id']);
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_pdt_id)){
			$this->errorResult(false,10101,array(),'请填写规格ID');
		}
        $ApiCarts = D('ApiCarts');
        $car_key = base64_encode( 'mycart' . $int_m_id);
        $cart_data = $ApiCarts->GetData($car_key);
        if (isset($cart_data[$int_pdt_id])) {
            unset($cart_data[$int_pdt_id]);
        }
        $Cart = $ApiCarts->WriteMycart($cart_data,$car_key);

		if($Cart !== true){
			$this->errorResult(false,10102,array(),'删除购物车失败!');
		}else{
			$options = array(
				'root_tag' => 'Cart_remove_response',
				);
			$this->result(true,10103,array("success"),"",$options);
		}
	}
    
    /**
	 * [修改购物车商品数量]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'pdt_id' => 规格ID
	 *          (int) 'm_id' => 会员ID
	 *          (int) 'num' => 修改后的数量
	 * );
	 */
	private function fxEditCart($params=null){
		writeLog("修改购物车商品数量请求参数\t". json_encode($params), 'fxEditCart' . date('Y_m_d') . '.log');
		$int_pdt_id  = max(0,(int)$params['pdt_id']);
		$int_m_id    = max(0,(int)$params['m_id']);
		$int_num     = max(0,(int)$params['num']);
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_pdt_id)){
			$this->errorResult(false,10101,array(),'请填写规格ID');
		}
		if(empty($int_num)){
			$this->errorResult(false,10101,array(),'请填写修改后的数量');
		}
        
        $ApiCarts = D('ApiCarts');
        $car_key = base64_encode( 'mycart' . $int_m_id);
        $cart_data = $ApiCarts->GetData($car_key);
        if (isset($cart_data[$int_pdt_id])) {
            $cart_data[$int_pdt_id]['num'] = (int) $int_num;
            $Cart = $ApiCarts->WriteMycart($cart_data,$car_key);
        }
        if($Cart !== true){
			$this->errorResult(false,10102,array(),'修改购物车失败!');
		}else{
			$options = array(
				'root_tag' => 'Cart_edit_response',
				);
			$this->result(true,10103,array("success"),"",$options);
		}
	}
    
########################## 购物车相关API END ############################################# 
   
// +----------------------------------------------------------------------
// | 订单相关API START
// +----------------------------------------------------------------------
// | Author: wanghaijun
// +----------------------------------------------------------------------

    /**
	 * [计算运费价格]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'lt_id' => 物流ID (必填)
	 *          (int) 'm_id' => 会员ID (必填) 
	 *          (int) 'sgp' => base64_encode(g_id,pdt_id(规格ID),num(数量);g_id,pdt_id(规格ID),num(数量))
	 * );
	 * @return [type]         [description]
	 */
	private function fxFreightTotal($params=null){
		writeLog("计算运费价格请求参数\t". json_encode($params), 'fxFreightTotal' . date('Y_m_d') . '.log');
		$int_m_id  = max(0,(int)$params['m_id']);
		$lt_id = max(0,(int)$params['lt_id']);
		$sgp = base64_decode($params['sgp']);
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($lt_id)){
			$this->errorResult(false,10101,array(),'请填写物流ID');
		}
        if(!$sgp){
			$this->errorResult(false,10101,array(),'请选择商品');
		}
        $arr = explode(";",$sgp);
        $arr_pdt = array();
        foreach($arr as $val) {
            $var_pdt = explode(",",$val);
			$arr_goods['g_id'] = $var_pdt[0];
			$arr_goods['pdt_id'] = $var_pdt[1];
			$arr_goods['num'] = $var_pdt[2];
            $arr_pdt[$var_pdt[1]] = $arr_goods;
        }

        $logistic_price = (string)D('Logistic')->getLogisticPrice($lt_id, $arr_pdt,$int_m_id);
        
        writeLog("计算运费价格返回数据\t". json_encode($logistic_price), 'fxFreightTotal' . date('Y_m_d') . '.log');
		// 返回结果
		if(empty($logistic_price)){
			$this->logs('FreightTotalApi','获取物流费用失败');
			$this->errorResult(false,10102,array(),'获取物流费用失败');
		}else{
			$options = array(
				'root_tag' => 'Freight_response'
				);
			$this->result(true,10103,$logistic_price,"success",$options);
		}
	}
    
    /**
	 * [获取物流]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'cr_id' => 城市区域ID (必填)
	 * );
	 * @return [type]         [description]
     * @lt_id 物流ID
     * @lc_name 物流名称
     * @lc_abbreviation_name 物流公司简称(字母)
	 */
    private function fxGetLogisticType($params=null){
        $cr_id = max(0,(int)$params['cr_id']);
        if(empty($cr_id)){
			$this->errorResult(false,10101,array(),'请填写城市区域ID');
		}
        $ary_where = array('cr_id' => $cr_id, 'lc_is_enable' => 1);
        $ary_tmp_logistic = D('ViewLogistic')->where($ary_where)
                                             ->field("lt_id,lc_name,lc_abbreviation_name")
                                             ->order('lc_ordernum ASC')
                                             ->group('lc_abbreviation_name')
                                             ->select();
        // 返回结果
		if(empty($ary_tmp_logistic)){
			$this->errorResult(false,10102,array(),'获取物流失败');
		}else{
			$options = array(
				'root_tag' => 'Logistic_type'
				);
			$this->result(true,10103,$ary_tmp_logistic,"success",$options);
		}
    }
    
    /**
	 * [生成订单]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'ra_id' => 地址ID (必填)
	 *          (int) 'm_id' => 会员ID (必填)
	 *          (int) 'pc_id' => 支付ID (必填)
	 *          (int) 'lt_id' => 物流ID (必填)
	 *          (string) 'sgp' => base64_encode(g_id,pdt_id(规格ID),num;g_id,pdt_id(规格ID),num)
	 * );
	 * @return [type]         [description]
	 */
	private function fxOrderConfirm($params=null){
		writeLog("生成订单请求参数\t". json_encode($params), 'fxOrderConfirm' . date('Y_m_d') . '.log');
		$int_m_id         = max(0,(int)$params['m_id']);
		$int_ra_id        = max(0,(int)$params['ra_id']);
		$int_lt_id        = max(0,(int)$params['lt_id']);
		$pdt_id          = base64_decode($params['sgp']);
		$int_pc_id        = max(0,(int)$params['pc_id']);

		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_ra_id)){
			$this->errorResult(false,10101,array(),'请填写地址ID');
		}
        if(empty($int_lt_id)){
			$this->errorResult(false,10101,array(),'请填写物流ID');
		}
		if(empty($pdt_id)){
			$this->errorResult(false,10101,array(),'请填写选择的商品');
		}
		if(empty($int_pc_id)){
			$this->errorResult(false,10101,array(),'请填写支付方式');
		}

        $now_tome = date('Y-m-d',time());

        if(empty($pdt_id) && !isset($pdt_id)){
            $this->errorResult(false,10101,array(),'没有要购买的商品，请重新选择商品'); 
            exit;            
        }
        $ApiCarts = D('ApiCarts');
        $car_key = base64_encode( 'mycart' . $int_m_id);
        $cart_data = $ApiCarts->GetData($car_key);
        $ary_pid = explode(';',$pdt_id);
        foreach($ary_pid as $val){
            $val_pdt = explode(",",$val);
            $arr_pdt[] = $val_pdt[1];
        }
        foreach ($cart_data as $key=>$cd){
            foreach ($arr_pdt as $pid){
                if($pid == $key){
                    $ary_cart[$pid] = $cart_data[$key];
                }
            }
        }
        
        // 商品总价
        $promotion_total_price = '0';
        $promotion_price = '0';
        
        foreach ($ary_cart as $ary) {
            $g_id = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_id' => $ary ['pdt_id']))->field('g_id,pdt_sale_price')->select();
            $is_authorize = D('AuthorizeLine')->isAuthorize($int_m_id, $g_id[0]['g_id']);
            if (empty($is_authorize)) {
                $this->errorResult(false,10101,array(),'部分商品已不允许购买,请先在购物车里删除这些商品');
                exit();
            }
            $promotion_total_price += $g_id[0]['pdt_sale_price'] * $ary['num'];
        }

        $ary_orders['ra_id'] = $int_ra_id;
        $ary_orders['lt_id'] = $int_lt_id;
        $ary_orders['o_payment'] = $int_pc_id;
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
            $ary_receive_address = D('CityRegion')->getReceivingAddress($int_m_id);
            foreach ($ary_receive_address as $ara_k=>$ara_v){
                if($ara_v['ra_id'] == $ary_orders['ra_id']){
                    $default_address ['default_addr'] = $ara_v;
                }
            }
            if (isset($default_address ['default_addr'] ['ra_id'])) {
                // 收货人
                $ary_orders ['o_receiver_name'] = $default_address ['default_addr'] ['ra_name'];
                // 收货人电话
                $ary_orders ['o_receiver_telphone'] = trim($default_address ['default_addr'] ['ra_phone']);
                // 收货人手机
                $ary_orders ['o_receiver_mobile'] = $default_address ['default_addr'] ['ra_mobile_phone'];
                // 收货人邮编
                $ary_orders ['o_receiver_zipcode'] = $default_address ['default_addr'] ['ra_post_code'];
                // 收货人地址
                $ary_orders ['o_receiver_address'] = $default_address ['default_addr'] ['ra_detail'];
                $ary_city_data = D('CityRegion')->getFullAddressId($default_address ['default_addr'] ['cr_id']);
                // 收货人省份
                $ary_orders ['o_receiver_state'] = D('CityRegion')->getAddressName($ary_city_data [1]);
                // 收货人城市
                $ary_orders ['o_receiver_city'] = D('CityRegion')->getAddressName($ary_city_data [2]);
                // 收货人地区
                $ary_orders ['o_receiver_county'] = D('CityRegion')->getAddressName($ary_city_data [3]);
            }
            
			if(empty($ary_orders['o_receiver_city'])){
				if(!empty($ary_orders['ra_id'])){
					$ary_address = D('CityRegion')->getReceivingAddress($int_m_id,$ary_orders['ra_id']);
					$ary_addr = explode(' ',$ary_address['address']);	
					if(!empty($ary_addr[1])){
						$ary_orders ['o_receiver_state'] = $ary_addr[0];
						$ary_orders ['o_receiver_city'] = $ary_addr[1];
						$ary_orders ['o_receiver_county'] = $ary_addr[2];
					}else{
                        $this->errorResult(false,10101,array(),'请检查您的收货地址是否正确');
						exit();	
					}
				}else{
                    $this->errorResult(false,10101,array(),'请检查您的收货地址是否正确');
					exit();					
				}
			}
          //  print_r($ary_orders);exit;
            // 会员id
            $ary_orders ['m_id'] = $int_m_id;
            // 订单id
            $ary_orders ['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
            // 物流费用
			$ary_goods = array();
            //普通订单商品
            $ary_tmp_cart = $ary_cart;

            $ary_orders ['o_goods_all_price'] = 0;
            if(empty($ary_tmp_cart)){
                $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
            }
            $logistic_price = D('Logistic')->getLogisticPrice($int_lt_id, $ary_tmp_cart,$int_m_id);
            //订单商品总价（销售价格带促销）
            $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
            //商品销售总价
            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $promotion_total_price);
        
            //判断会员等级是否包邮
            if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                $logistic_price = 0;
            }
            //物流公司设置包邮额度
            $lt_expressions = json_decode(M('logistic_type')->where(array('lt_id'=>$int_lt_id))->getField('lt_expressions'),true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_orders['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $logistic_price = 0;
            }
			//物流费用
            $ary_orders ['o_cost_freight'] = $logistic_price;
            // 订单总价
            $all_price = $ary_orders ['o_goods_all_price'];
            if ($all_price <= 0) {
                $all_price = 0;
            }
            // 订单应付总价 订单总价+运费
            $all_price += $ary_orders ['o_cost_freight'];
			if(empty($ary_orders ['o_goods_all_price'])){
                $this->errorResult(false,10101,array(),'没有要购买的商品，请重新选择商品');
                exit;
			}
            //当订单总价为0 且物流也为0时，订单状态为已支付
            if(0 == $all_price) {
                $ary_orders ['o_pay_status'] = 1;
                $ary_orders ['o_status'] = 1;
            }
            
            $ary_orders ['o_all_price'] = sprintf("%0.3f", $all_price);
            //$ary_orders ['o_buyer_comments'] = $ary_orders ['o_buyer_comments'];
            if (empty($ary_orders ['o_receiver_county'])) { // 没有区时
                unset($ary_orders ['o_receiver_county']);
            }
            if (!isset($ary_orders ['gp_id']) && !empty($promotion_price)) {
                //订单优惠金额
                $ary_orders ['o_discount'] = sprintf("%0.2f", $promotion_price);
            }
            // 发货备注
            if (!empty($ary_orders ['shipping_remarks'])) {
                $ary_orders ['o_shipping_remarks'] = $ary_orders ['shipping_remarks'];
                unset($ary_orders ['shipping_remarks']);
            }
            // 管理员操作者ID
            if ($ary_datas ['admin_id']) {
                $ary_orders ['o_addorder_id'] = $ary_datas ['admin_id'];
            }
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
                $ary_orders['o_audit'] = 1;
            }
         
			if(empty($ary_orders['o_goods_all_price'])){
                $orders->rollback();
                $this->errorResult(false,10101,array(),'商品金额为0，保存失败');
                exit();			
			}			
			//是否是匿名购买
			if($ary_orders['is_anonymous'] != '1'){
				unset($ary_orders['is_anonymous']);
			}

            $bool_orders = D('Orders')->doInsert($ary_orders);
			
            if (!$bool_orders) {
                $orders->rollback();
                $this->errorResult(false,10101,array(),'订单生成失败');
                exit();
            } else {
                $ary_orders_items = array();
                $ary_orders_goods = D('Cart')->getProductInfo($ary_cart);
                
                if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                    $total_consume_point = 0; // 消耗积分
                    $int_pdt_sale_price = 0; // 货品销售原价总和
                    $gifts_point_reward = '0'; //有设置购商品赠积分所获取的积分数
                    $gifts_point_goods_price  = '0'; //设置了购商品赠积分的商品的总价
					//获取明细分配的金额
					//$ary_orders_goods = $this->getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon,$pro_datas);
					foreach ($ary_orders_goods as $k => $v) {
                        $ary_orders_items = array();
                        if (!empty($v['rule_info']['name'])) {
                            $v['pmn_name'] = $v['rule_info']['name'];
                        }
                        // 订单id
                        $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                        // 商品id
                        $ary_orders_items ['g_id'] = $v ['g_id'];
                        // 货品id
                        $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                        // 类型id
                        $ary_orders_items ['gt_id'] = $v ['gt_id'];
                        // 商品sn
                        $ary_orders_items ['g_sn'] = $v ['g_sn'];
                        // o_sn
                        // $ary_orders_items['g_id'] = $v['g_id'];
                        // 货品sn
                        $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                        // 商品名字
                        $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                        // 成本价
                        $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                        // 货品销售原价
                        $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                        // 购买单价
                        $ary_orders_items ['oi_price'] = $v ['pdt_price'];
                        // 商品积分
                        if (isset($v ['type']) && $v ['type'] == 1) {
                            $ary_orders_items ['oi_score'] = $v ['pdt_sale_price'];
                            $total_consume_point += $v ['pdt_sale_price'] * $v ['pdt_nums'];
                            $ary_orders_items ['oi_type'] = 1;
                        } else {
                            if (isset($v ['type']) && $v ['type'] == 2) {
                                $ary_orders_items ['oi_type'] = 2;
                            }
                            $int_pdt_sale_price += $v ['pdt_sale_price'] * $v ['pdt_nums'];
                        }
                        if (isset($v['pmn_name'])) {
                            $ary_orders_items['promotion'] = $v['pmn_name'];
                        }
                        if (isset($v['promotion_price']) && !empty($v['promotion_price'])) {
                            $ary_orders_items['promotion_price'] = $v['promotion_price'];
                        }								
                        
                        // 商品数量
                        $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                        if(!empty($v['oi_coupon_menoy'])){
                            $ary_orders_items['oi_coupon_menoy'] = $v['oi_coupon_menoy'];
                        }
                        if(!empty($v['oi_bonus_money'])){
                            $ary_orders_items['oi_bonus_money'] = $v['oi_bonus_money'];
                        }
                        if(!empty($v['oi_cards_money'])){
                            $ary_orders_items['oi_cards_money'] = $v['oi_cards_money'];
                        }
                        if(!empty($v['oi_jlb_money'])){
                            $ary_orders_items['oi_jlb_money'] = $v['oi_jlb_money'];
                        }
                        if(!empty($v['oi_point_money'])){
                            $ary_orders_items['oi_point_money'] = $v['oi_point_money'];
                        }										
                        $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                        if (!$bool_orders_items) {
                            $orders->rollback();
                            $this->errorResult(false,10101,array(),'订单明细生成失败');
                            exit();
                        }
                        // 商品库存扣除
                        $ary_payment_where = array(
                            'pc_id' => $ary_orders ['o_payment']
                        );
                        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                        if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                            // by Mithern 扣除可下单库存生成库存调整单
                            $good_sale_status = D('Goods')->field(array('g_pre_sale_status'))->where(array('g_id' => $v ['g_id']))->find();
                            if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                //查询库存,如果库存数为负数则不再扣除库存
                                $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                               ->field('pdt_stock,pdt_min_num')
                                                               ->where(array('o_id'=>$ary_orders['o_id']))
                                                               ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                               ->find();
                                if(0 >= $int_pdt_stock['pdt_stock']){
                                    $this->errorResult(false,10101,array(),'该货品已售完！');
                                    die();
                                }
                                if($v['pdt_nums'] < $int_pdt_stock['pdt_min_num']){
                                    $this->errorResult(false,10101,array(),'该货品至少购买'.$int_pdt_stock['pdt_min_num']);
                                    die();
                                }
                                $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                                if (false == $array_result ["status"]) {
                                    $orders->rollback();
                                    $this->errorResult(false,10101,array(),$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                    die();
                                }
                            }
                        }
                        
                    }
                }
			}
            // 订单日志记录
            $ary_orders_log = array(
                'o_id' => $ary_orders ['o_id'],
                'ol_behavior' => '创建',
                'ol_uname' => $int_m_id,
                'ol_create' => date('Y-m-d H:i:s')
            );
            
            $res_orders_log = D('OrdersLog')->add($ary_orders_log);
            if (!$res_orders_log) {
                $this->errorResult(false,10101,array(),'订单日志记录失败');
                exit();
            }
            $orders->commit();

            foreach ($arr_pdt as $val) {
                if (isset($cart_data[$val])) {
                    unset($cart_data[$val]);
                }
            }
            $ApiCarts->WriteMycart($cart_data,$car_key);
            
            $options = array(
				'root_tag' => 'Order_confirm_response'
				);
            $response_arr['po_id'] = $order_id;
            $response_arr['total_sale_price'] = sprintf("%0.3f", $all_price);
            $response_arr['payment'] = '支付宝';
            $this->result(true,10102,$response_arr,"success",$options);
            exit();
        }
	}
    
    /**
	 * [订单列表信息]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'm_id' => 会员ID (必填)
	 *          (int) 'page' => 第几页 (选填 默认 0)
	 *          (int) 'pagesize' => 每页显示条数 (选填 默认 1条)
	 *          (int) 'status' => 订单状态(选填)(不填/0表示全部订单,1表示 已取消,2表示 待支付)
	 * );
	 * @return [type]         [description]
	 */
	private function fxOrderGet($params=null){
		writeLog("订单列表信息请求参数\t". json_encode($params), 'fxOrderGet' . date('Y_m_d') . '.log');
		$int_m_id     = max(0,(int)$params['m_id']);
		$int_status   = max(0,(int)$params['status']);
		$int_page     = max(0,(int)$params['page']);
		$int_pagesize = max(1,(int)$params['pagesize']);
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(!in_array($int_status,array(0,1,2))){
			$int_status = 0;
		}
		// 数据库操作
		$condition = array(
			'm_id'     => $int_m_id,
			'status'   => $int_status,
			'page'     => $int_page,
			'pagesize' => $int_pagesize
			);
		$ary_result = D('ApiOrdersNew')->getOrders($condition);
		writeLog("订单列表信息返回数据\t". json_encode($ary_result), 'fxOrderGet' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('OrderGetApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Order_list_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * [订单详细信息]
	 * @param  [array] $params [description]
	 * @example array(
	 *          (int) 'm_id' => 会员ID (必填) (19)
	 *          (int) 'o_id' => 订单ID (必填) (2014110811204731686)
	 * );
	 * @return [type]         [description]
	 */
	private function fxOrderDetailGet($params=null){
		writeLog("订单详细信息请求参数\t". json_encode($params), 'fxOrderDetailGet' . date('Y_m_d') . '.log');
		$int_m_id = max(0,(int)$params['m_id']);
		$int_o_id = is_numeric($params['o_id']) ? $params['o_id'] : 0;
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_o_id)){
			$this->errorResult(false,10101,array(),'请填写订单ID');
		}
		// 数据库操作
		$condition = array(
			'm_id' => $int_m_id,
			'o_id' => $int_o_id
			);
		$ary_result = D('ApiOrdersNew')->getOrderDetail($condition);
		writeLog("订单详细信息返回参数\t". json_encode($ary_result), 'fxOrderDetailGet' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('OrderDetailGetApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Order_detail_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * [取消订单]
	 * @param  [type] $params [description]
	 * @example array(
	 *          (int) 'm_id' => 用户ID (必填)
	 *          (int) 'o_id' => 订单ID (必填)
	 * );
	 * @return [type]         [description]
	 */
	private function fxOrderTradeClose($params = null){
		writeLog("取消订单请求参数\t". json_encode($params), 'fxOrderTradeClose' . date('Y_m_d') . '.log');
		$int_o_id = is_numeric($params['o_id']) ? $params['o_id'] : 0;
		$int_m_id = $params['m_id'];
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_o_id)){
			$this->errorResult(false,10101,array(),'请填写订单号');
		}
		// 数据库操作
		$condition = array(
			'o_id' => $int_o_id
			);
		$ary_result = D('ApiOrdersNew')->orderTradeClose($condition);
		writeLog("取消订单返回数据\t". json_encode($ary_result), 'fxOrderTradeClose' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] !== true){
			$this->logs('OrderTradeCloseApi',$ary_result['sub_msg']);
			$this->errorResult(false,$ary_result['code'],array(),$ary_result['sub_msg']);
		}else{
			$options = array(
				'root_tag' => 'Trade_close_response'
				);
			$this->result(true,$ary_result['code'],$ary_result['info'],"success",$options);
		}
	}
    
    /**
	 * [确认收货]
	 * @param  [type] $params [description]
	 * @example array(
	 *          (int) 'm_id' => 用户ID (必填)
	 *          (int) 'o_id' => 订单ID (必填)
	 * );
	 * @return [type]         [description]
	 */
	private function fxOrderReceipt($params = null){
		writeLog("确认收货请求参数\t". json_encode($params), 'fxOrderReceipt' . date('Y_m_d') . '.log');
		$int_o_id = is_numeric($params['o_id']) ? $params['o_id'] : 0;
		$int_m_id = $params['m_id'];
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		if(empty($int_o_id)){
			$this->errorResult(false,10101,array(),'请填写订单号');
		}

		$ary_result = D('ApiOrdersNew')->orderReceipt($int_o_id);
		writeLog("确认收货返回数据\t". json_encode($ary_result), 'fxOrderReceipt' . date('Y_m_d') . '.log');
		// 返回结果
		if($ary_result['status'] != true){
			$this->logs('OrderReceiptApi',$ary_result['message']);
			$this->errorResult(false,10101,array(),$ary_result['message']);
		}else{
			$options = array(
				'root_tag' => 'Order_receipt_response'
				);
			$this->result(true,10102,$ary_result['message'],"success",$options);
		}
	}

	/**
	 * [获取首页内容]
	 * @param  [type] $params [description]
	 * @example array(
	 *          (int) 'm_id' => 用户ID (必填)
	 *          (int) 'type' => 类型 (0 android;1 ios)
	 * );
	 * @return [json][base64_encode]         [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 
	 */
	private function fxMobileHomePage($params=null){
		writeLog("获取首页内容请求参数\t". json_encode($params), 'fxMobileHomePage' . date('Y_m_d') . '.log');
		$int_m_id = $params['m_id'];
		$int_type = max(0,(int)$params['type']);
		if(empty($int_m_id)){
			$this->errorResult(false,10101,array(),'请填写用户ID');
		}
		$type = $int_type == 0 ? 'android' : 'ios';
		$host = 'http://'.$_SERVER['HTTP_HOST'];
		$array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_DEFAULT'))->find();
        if (is_array($array_config) && !empty($array_config)) {
            define('TPL', $array_config['sc_value']);
        } else {
            define('TPL', 'default');
        }
        $this->dir = TPL;
        $config = array(
			'tpl'    => $host.'/Public/Tpl/' . CI_SN . '/' . $this->dir . '/mobile/' . $type . '/',
			'js'     => $host.'/Public/Tpl/' . CI_SN . '/' . $this->dir . '/mobile/' . $type . '/js/',
			'images' => $host.'/Public/Tpl/' . CI_SN . '/' . $this->dir . '/mobile/' . $type . '/images/', // 客户模版images路径替换规则
			'css'    => $host.'/Public/Tpl/' . CI_SN . '/' . $this->dir . '/mobile/' . $type . '/css/', // 客户模版css路径替换规则
        );
        C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
        C('TMPL_PARSE_STRING.__JS__', $config['js']);
        C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
        C('TMPL_PARSE_STRING.__CSS__', $config['css']);

        $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/mobile/' . $type . '/mobile_index.html';
        /**
         * 1.使用$this->fetch($tpl),渲染模版后返回字符串,最佳效果.上策
         * 2.先file_get_contents获取内容,再使用str_replace替换路径.替代模版渲染,中策
         * 3.直接用file_get_contents获取文件内容, 但文件中的路径需要写死,下策
         */
		$content = $this->fetch($tpl);
		$ary_result = base64_encode($content);
		writeLog("获取首页内容返回数据\t". json_encode($ary_result), 'fxMobileHomePage' . date('Y_m_d') . '.log');
		// 返回结果
		if(empty($ary_result)){
			$this->logs('MobileHomePageApi','不存在该页面:'.$tpl);
			$this->errorResult(false,'10101',array(),'不存在该页面');
		}else{
			$options = array(
				'root_tag' => 'Page_home_response'
				);
			$this->result(true,'10805',$ary_result,"success",$options);
		}
	}
}

