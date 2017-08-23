<?php

/**
 * 商品相关模型层 Model
 * @package Model
 * @version 7.0
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2012-12-13
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiGoodsModel extends GyfxModel {
	
	//查询商品数据
	protected $item_map = array(
        'num_iid'=>'fx_goods.g_id', // 商品数字id gid
        'title'=>'g_name', // 商品标题 
		'cid'=>'gt_id', // 商品类型ID
		'seller_cids'=>'gc.gc_id', // 商品类目ID
		'pic_url'=>'g_picture', // 商品主图片地址 
		'num'=>'g_stock', // 商品数量  gstock 
		'list_time'=>'g_on_sale_time', //上架时间（格式：yyyy-MM-dd HH:mm:ss）
		'delist_time'=>'g_off_sale_time', // 下架时间（格式：yyyy-MM-dd HH:mm:ss）  
		'stuff_status'=>'g_new', // 商品新旧程度(全新:new，闲置:unused，二手：second) g_new:'是否新品上架1 是,0 不是,2翻新' 
		'price'=>'g_price', // 商品价格，格式：5.00；单位：元；精确到：分  gprice  
		'approve_status'=>'g_on_sale', // 商品上传后的状态。onsale出售中，instock库中  g_on_sale   g_status数据记录状态，0为废弃，1为有效，2为进入回收站
		'outer_id'=>'g_sn', // 商家外部编码
		'desc'=>'g_desc', // 商品描述  
		'modified' =>'fx_goods_info.g_update_time', // 商品描述  
        //'item_img',//商品图片列表(包括主图)item_img.id、item_img.url、item_img.position   skus
        //'prop_img',//prop_imgs商品属性图片列表。prop_img.id、prop_img.url、prop_img.properties、prop_img.position
        //'sku',//Sku列表。fields中只设置sku可以返回Sku结构体中所有字段，sku.sku_id、sku.properties、sku.quantity   ==skus
    );
    
    //查询出的数据转换时使用
    protected $item_map_info = array(
        'num_iid'=>'g_id', // 商品数字id gid
        'title'=>'g_name', // 商品标题 
		'cid'=>'gt_id', // 商品类型ID
		'seller_cids'=>'gc_id', // 商品类目ID
		'pic_url'=>'g_picture', // 商品主图片地址 
		'num'=>'g_stock', // 商品数量  gstock 
		'list_time'=>'g_on_sale_time', //上架时间（格式：yyyy-MM-dd HH:mm:ss）
		'delist_time'=>'g_off_sale_time', // 下架时间（格式：yyyy-MM-dd HH:mm:ss）  
		'stuff_status'=>'g_new', // 商品新旧程度(全新:new，闲置:unused，二手：second) g_new:'是否新品上架1 是,0 不是,2翻新' 
		'price'=>'g_price', // 商品价格，格式：5.00；单位：元；精确到：分  gprice  
		'approve_status'=>'g_on_sale', // 商品上传后的状态。onsale出售中，instock库中  g_on_sale   g_status数据记录状态，0为废弃，1为有效，2为进入回收站
		'outer_id'=>'g_sn', // 商家外部编码
		'desc'=>'g_desc', // 商品描述  
		'modified'=>'g_update_time', // 商品描述  
        //'item_img',//商品图片列表(包括主图)item_img.id、item_img.url、item_img.position   skus
        //'prop_img',//prop_imgs商品属性图片列表。prop_img.id、prop_img.url、prop_img.properties、prop_img.position
        //'sku',//Sku列表。fields中只设置sku可以返回Sku结构体中所有字段，sku.sku_id、sku.properties、sku.quantity   ==skus
    );
    
    protected $sku_map = array(
            'modified'=>'pdt_update_time',
            'outer_id'=>'pdt_sn',
            'price'=>'pdt_sale_price',
            //'properties_name'=>'specName',
            'quantity'=>'pdt_stock',
            'sku_id'=>'pdt_id'
    );
    
     /**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 根据条件查询商品信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     * @param name,gid
     * @return array
     * 商品ID	g_id
     * 商品SN	g_sn
     * 上架时间	g_on_sale_time
     * 下架时间	g_off_sale_time
     * 商品名称	g_name
     * 商品价格	g_price
     * 商品库存	g_stock
     * 商品单位	g_unit
     * 商品描述	g_desc
     * 商品主图	g_picture
     * 商品全部图片	gpics
     * 新品标识	g_new
     * 热销标识	g_hot
     * 货品列表	skus
     * 商品分类ID	gc_id
     * 商品分类名称	gc_name
     * 商品品牌ID	gb_id
     * 商品品牌名称	gb_name
     *         $ary_fileds = array(
                'detail_url',//商品url 
                //'num_iid',//商品数字id gid
                //'title',//商品标题 
                //'nick',//卖家昵称
                //'cid',//商品类型ID
                //'seller_cids',//商品所属的店铺内卖家自定义类目列表
                //'pic_url',//商品主图片地址  gpic
                //'num',//商品数量  gstock
                //'list_time',//上架时间（格式：yyyy-MM-dd HH:mm:ss）
                //'delist_time',//下架时间（格式：yyyy-MM-dd HH:mm:ss）
                //'stuff_status',//商品新旧程度(全新:new，闲置:unused，二手：second) g_new:'是否新品上架1 是,0 不是,2翻新'
                //'location',//商品所在地 分析系统暂时没有
                //'price',//商品价格，格式：5.00；单位：元；精确到：分  gprice
                //'modified',//商品修改时间（格式：yyyy-MM-dd HH:mm:ss）  g_update_time
                //'approve_status',//商品上传后的状态。onsale出售中，instock库中  g_on_sale
                'item_img',//商品图片列表(包括主图)item_img.id、item_img.url、item_img.position   skus
                'prop_img',//prop_imgs商品属性图片列表。prop_img.id、prop_img.url、prop_img.properties、prop_img.position
                'sku',//Sku列表。fields中只设置sku可以返回Sku结构体中所有字段，sku.sku_id、sku.properties、sku.quantity   ==skus
                //'outer_id',//商家外部编码(可与商家外部系统对接)   gsn
                //'is_virtual',//虚拟商品的状态字段  分销暂不支持
                'type',//分销默认为fixed， 商品类型(fixed:一口价;auction:拍卖)注：取消团购
                //'desc'//商品描述, 字数要大于5个字符，小于25000个字符 gdesc
		);
     */
    public function goodDetail($array_params) {
    	$ary_outer_ids = $array_params["outer_id"];
    	$ary_items = array();
    	$ary_data=array();
        $goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_where = array();
        //获取单个商品信息（g_id）

        if(empty($ary_outer_ids) && !empty($array_params["num_iid"])){
        	$ary_gids = array(array('g_id'=>$array_params["num_iid"]));
        }else{
        //获取商品信息（g_sn）
         $ary_where['g_sn'] = array('in',$ary_outer_ids);
         $ary_gids = $goods->field('g_id,g_sn')->where($ary_where)->select();       	
        }
        if(!empty($ary_gids)){
        	$post_fileds = explode(',',$array_params["fields"]);
		 $map_values = array_keys($this->item_map);
		 $ary_fields = array_intersect($post_fileds,$map_values);
		 $ary_filed = D('ApiGoods')->parseFieldsMapToReal($ary_fields);
		 $ary_filed = implode(',', $ary_filed);
		 

		 foreach($ary_gids as $ary_tag){
	        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
	        $goodsSpec = D('ApiGoodsSpec');
	        $ary_goods = $goods
	                    ->where(C('DB_PREFIX').'goods.g_id='.$ary_tag['g_id'])
	                    ->field($ary_filed)
	                    ->join(C('DB_PREFIX').'goods_info as gi on('.C('DB_PREFIX').'goods.g_id = gi.g_id)')
	                    ->join(C('DB_PREFIX').'related_goods_category as rgc on('.C('DB_PREFIX').'goods.g_id=rgc.g_id)')
	                    ->join(C('DB_PREFIX')."goods_category as gc on(rgc.gc_id=gc.gc_id)")
	                    ->find();
	        $this->_map = $this->item_map;

	        $ary_items_info = D('ApiGoods')->parseFieldsMap($ary_goods); 
	        if(in_array('num_iid',$post_fileds)){
       	
		        $ary_items_info['num_iid'] = $ary_items_info['g_id'];
		        unset($ary_items_info['g_id']);	        	
	        } 
			if(in_array('seller_cids',$post_fileds)){
		        $ary_items_info['seller_cids'] = $ary_items_info['gc_id'];
		        unset($ary_items_info['gc_id']);				
			}
	        if($ary_items_info['pic_url']){
	        	$ary_items_info['pic_url'] = $_SESSION['HOST_URL'].$ary_items_info['pic_url'];
	        	$ary_items_info['pic_url'] = str_replace('//', '/', $ary_items_info['pic_url']);
	        }
		 	if(empty($ary_items_info['g_desc']) && empty($ary_items_info['desc'])){
		        $ary_items_info['desc'] = null;
		        unset($ary_items_info['g_desc']);	
	        }
        	$item['type'] = 'fixed';
			switch($ary_items_info['stuff_status']){
				case 1:
				$item['stuff_status'] = 'new';	
				break;
				case 2:
				$ary_items_info['stuff_status'] = 'second';
				break;
				default:
				$ary_items_info['stuff_status'] = 'unused';		
			}

        	switch($ary_items_info['g_on_sale']){
				case 1:

				$ary_items_info['approve_status'] = 'onsale';	
				break;
				default:

				$ary_items_info['approve_status'] = 'instock';		
			}				
			if(in_array('sku',$post_fileds)){
				if(!empty($ary_goods) && is_array($ary_goods)){
					$ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_create_time', 'pdt_update_time', 'pdt_id', 'pdt_sale_price', 'pdt_on_way_stock');

					$ary_where = array();
					$ary_where['g_id']  = $ary_tag['g_id'];
					$ary_where['pdt_status'] = '1';
					$ary_pdt = $products->field($ary_product_feild)->where($ary_where)->limit(0 . ',' . 100)->select();
					if(!empty($ary_pdt) && is_array($ary_pdt)){
						$skus = array();
						foreach($ary_pdt as $keypdt=>$valpdt){
							//获取其他属性
							$specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
							if(!empty($specInfo['color'])){
								if(!empty($specInfo['color'][1])){
									$skus[$specInfo['color'][0]][] = $specInfo['color'][1];
								}
							}
							if(!empty($specInfo['size'])){
								if(!empty($specInfo['size'][1])){
								   $skus[$specInfo['size'][0]][] = $specInfo['size'][1]; 
								}
							}
							$ary_pdt[$keypdt]['specName']  = $specInfo['spec_name'];
						}
					}
					if(!empty($ary_pdt)){

						$ary_items_info['skus'] = array();
						foreach($ary_pdt as $ary){
							$ary_items_info['skus']['sku'][] = array(
								'created'=>$ary['pdt_create_time'],
								'modified'=>$ary['pdt_update_time'],
								'outer_id'=>$ary['pdt_sn'],
								'price'=>$ary['pdt_sale_price'],
								'properties_name'=>$ary['specName'],
								'quantity'=>$ary['pdt_stock'],
								'sku_id'=>$ary['pdt_id'],
								'status'=>'normal'
							);
						}
					}     	
			}    
			if(in_array('item_img',$post_fileds)){
				$ary_items_info['item_imgs'] = array();
				$ary_picresult = M('goods_pictures',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_goods['g_id'],'gp_status'=>'1'))->select();
				if(!empty($ary_picresult) && is_array($ary_picresult)){
					$ary_pic = $ary_picresult;
					foreach($ary_pic as $pic){
						$ary_items_info['item_imgs']['item_img'][] = array(
							'id'=>$pic['gp_id'],
							'posotion'=>$pic['gp_order'],
							'url'=>str_replace('//','/',$_SESSION['HOST_URL'].$pic['gp_picture'])
						);
					}
				}
			}     
		} 	

		$ary_items['items']['item'][]=$ary_items_info;
		} 	 
        }

		if(!empty($ary_items)){
			return $ary_items;
		}else{
			return array();
		}
    }

	/**
	 * 根据商品ID列表获取商品sku信息
	 * request params
	 * @num_iids String 必须 	123456  支持批量，最多不超过40个。
	 * @fields Field List 必须 	num_iid,sku..
	 * $ary_fileds = array(
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
	 * @date 2013-05-31
	 */
    public function goodSkus($array_params) {
    	$num_iids = $array_params["num_iids"];
    	$post_fileds = explode(",",$array_params['fields']);
    	$num_iids = explode(",",$num_iids);
    	$item_skus  = array();
    	 $map_values = array_keys($this->sku_map);

    	 $this->item_map = $this->sku_map;

		 $ary_fields = array_intersect($post_fileds,$map_values);
		 $ary_filed = D('ApiGoods')->parseFieldsMapToReal($ary_fields);
		 $ary_filed = implode(',', $ary_filed);
		 foreach($num_iids as $key=>$num_iid){
	        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
	        $goodsSpec = D('ApiGoodsSpec');
			            $ary_product_feild = $ary_filed;
			            $ary_where = array();
			            $ary_where['g_id']  = $num_iid;
			            $ary_where['pdt_status'] = '1';
			            $ary_pdt = $products->field($ary_product_feild)->where($ary_where)->limit(0 . ',' . 100)->select();
			            if(!empty($ary_pdt) && is_array($ary_pdt)){
							$skus = array();
			            	if(in_array('properties_name',$post_fileds)){
				                foreach($ary_pdt as $keypdt=>$valpdt){
				                    //获取其他属性
				                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
				                    if(!empty($specInfo['color'])){
				                        if(!empty($specInfo['color'][1])){
				                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
				                        }
				                    }
				                    if(!empty($specInfo['size'])){
				                        if(!empty($specInfo['size'][1])){
				                           $skus[$specInfo['size'][0]][] = $specInfo['size'][1]; 
				                        }
				                    }
				                    $ary_pdt[$keypdt]['properties_name']  = $specInfo['spec_name'];
				                }			            			
			            	}
			            } 			            
			            $this->_map = $this->sku_map;
	        			$ary_pdt = D('ApiGoods')->parseFieldsMap($ary_pdt);  
	        			//dump($ary_pdt);die();
			            if(!empty($ary_pdt)){
			            	foreach($ary_pdt as $ary){

			            	    if(in_array('itemextrasaleprop',$post_fileds)){
			            	        $int_g_id=$ary['g_id'];
                                    $result = M("RelatedGoodsSpec rgs")->join(C('DB_PREFIX').'goods_spec gs on rgs.gs_id = gs.gs_id')
                                                                       ->join(C('DB_PREFIX').'goods_spec_detail gsd on rgs.gs_id = gsd.gs_id')
                                                                       ->field('gs_name,gsd_aliases')
                                                                       ->where(array('g_id'=>$int_g_id,'gs.gs_is_sale_spec'=>1))->select();
                                    foreach($result as $k=>$v){
                                        $result[$k] = implode(':',$v);
                                    }
                                    $result = implode(';',$result);
                                    $ary["itemextrasaleprop"] = $result;
			            	        
			            	    }
			            		$item_skus['skus']['sku'][]=$ary;	
			            	}
			            }  
									
	        } 	 
		if(!empty($item_skus)){
			return $item_skus;
		}else{
			return array();
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
	 * $ary_fileds = array(
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
                'type',//分销默认为fixed， 商品类型(fixed:一口价;auction:拍卖)注：取消团购
                //分销存在但是淘宝接口不存在，但是方便后面对接使用的字段
		);
	 * reponse params
	 * @items	
	 * @total_results 
     * @param name,gname,gid,cid,bid,hot,new,order,num,start,pagesize
     * @return array
     * 商品ID	gid
     * 商品SN	gsn
     * 上架时间	onsale
     * 下架时间	offsale
     * 商品名称	gname
     * 商品价格	gprice
     * 市场价         gmprice
     * 商品库存	gstock
     * 商品单位	gunit
     * 商品描述	gdesc
     * 商品主图	gpic
     * 总销量	gsales(暂时未处理)
     * 新品标识	gnew
     * 热销标识	ghot
     * 详情页URL	gurl
     * 分页信息	pageinfo
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     */

    public function goodList($ary_tag) {
        $ary_data = array();
        $ary_where = array();
        $order_by = '';

        $ary_limit = array();
        //商品名称

        if(!empty($ary_tag['q'])){
            $ary_where['g_name'] = $ary_where['g_name'] = array('like',"%".trim($ary_tag['q'])."%");
        }
        
        //时间排序排序


        //时间排序排序
		if (!empty($ary_tag['end_modified']) && !empty($ary_tag['start_modified'])) {
			$ary_where[C('DB_PREFIX').'goods_info.g_update_time'] = array(between, array($ary_tag['start_modified'], $ary_tag['end_modified']));
		}else{
			if(!empty($ary_tag['start_modified'])){
        		$ary_where[C('DB_PREFIX').'goods_info.g_update_time'] = array('EGT',$ary_tag['start_modified']);
	        }
	        if(!empty($ary_tag['end_modified'])){
	        	$ary_where[C('DB_PREFIX').'goods_info.g_update_time'] = array('ELT',$ary_tag['end_modified']);
	        }
		}
        //如果需要排序
        //排序默认时间逆序_new* @order_by 排序方式。格式为column:asc/desc ，
        //column可选值:list_time(上架时间),delist_time(下架时间),num(商品数量)，modified(最近修改时间); 
        //默认上架时间降序(即最新上架排在前面)。如按照上架时间降序排序方式为list_time:desc
        //$ary_where['order_by'] = empty($ary_tag['order_by'])?'list_time:desc':$ary_tag['order_by'];
        switch ($ary_tag['order_by']) {
            case 'list_time:asc':
                $order_by = 'g_on_sale_time asc';
                break;
            case 'list_time:desc':
                $order_by = 'g_on_sale_time desc';
                break;
            case 'modified:asc':
                $order_by = 'g_update_time asc';
                break;
            case 'modified:desc':
                $order_by = 'g_update_time desc';
                break;
            case 'num:asc':
                $order_by = 'g_stock asc';
                break;
            case 'num:desc':
                $order_by = 'g_stock desc';
                break;  
            case 'delist_time:asc':
                $order_by = 'g_off_sale_time asc';
                break;
            case 'delist_time:desc':
                $order_by = 'g_off_sale_time desc';
                break;                                
            default:
            $order_by = 'g_on_sale_time desc';
        }
		$ary_limit['pagesize'] = empty($ary_tag['page_size'])?'40':$ary_tag['page_size'];
		$ary_limit['start'] = empty($ary_tag['page_no'])?'1':$ary_tag['page_no'];
        //在架
        $ary_where['g_on_sale'] = 1;      
        //有效
        $ary_where['g_status'] = 1;
        $ary_where['g_is_combination_goods'] = 0;
        $post_fileds = $ary_tag["fields"];
        //商品描述一定不取
        unset($post_fileds['desc']);
		$map_values = array_keys($this->item_map);
		$ary_fields = array_intersect($post_fileds,$map_values);
		$ary_filed = D('ApiGoods')->parseFieldsMapToReal($ary_fields);
		$ary_filed = implode(',', $ary_filed);
        $ary_data = $this->getGoodsList($ary_where,$order_by,$ary_limit,$ary_filed);
		$ary_itemlist = array();
        $ary_itemlist['items']['item'] = $ary_data['list'];
        $ary_itemlist['total_results'] = $ary_data['total_results'];
		if(empty($ary_itemlist)){
			return array();
		}else{
			return $ary_itemlist;
		}
    }
    
    /**
     * 官网商品列表页
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-3-28
     *    	["g_id"] => string(4) "1233"
		    ["g_name"] => string(4) "no.1"
		    ["gt_id"] => string(1) "0"
		    ["g_picture"] => string(82) "/Public/Uploads/fx093433/goods/20130522/25022470-E523-45CF-B079-1FE54176731E-1.jpg"
		    ["g_stock"] => string(3) "100"
		    ["g_on_sale_time"] => string(19) "2013-05-21 17:14:13"
		    ["g_off_sale_time"] => string(19) "0000-00-00 00:00:00"
		    ["g_price"] => string(7) "100.000"
		    ["g_update_time"] => string(19) "0000-00-00 00:00:00"
		    ["g_status"] => string(1) "1"
		    ["g_sn"] => string(6) "KT1121"
     */
    public function getGoodsList($ary_where,$order_by,$ary_limit,$ary_filed){
        $obj = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_filed = str_replace("gc.gc_id","GROUP_CONCAT(gc.gc_id) as gc_id",$ary_filed);
        $int_count = $obj->join(C('DB_PREFIX').'goods_info on '.C('DB_PREFIX').'goods_info.g_id='.C('DB_PREFIX').'goods.g_id')->where($ary_where)->count();
        $ary_data = $obj->join(C('DB_PREFIX').'goods_info on '.C('DB_PREFIX').'goods_info.g_id='.C('DB_PREFIX').'goods.g_id')
	                    ->join(C('DB_PREFIX').'related_goods_category as rgc on('.C('DB_PREFIX').'goods.g_id=rgc.g_id)')
	                    ->join(C('DB_PREFIX').'goods_category as gc on(rgc.gc_id=gc.gc_id)')
     					->where($ary_where)

                             ->field($ary_filed)
                             ->order($order_by)
                             ->group(C('DB_PREFIX').'goods.g_id')
                             ->limit(($ary_limit['start']-1)*$ary_limit['pagesize'],$ary_limit['pagesize'])
                             ->select();
                             //echo $obj->getLastSql();exit;
        $ary_items = array();
        $this->_map = $this->item_map_info; 
        foreach($ary_data as &$item){
        	$ary_items[] = D('ApiGoods')->parseFieldsMap($item);
        }    
        unset($ary_data);    
        foreach($ary_items as &$item){
        	if($item['pic_url']){
        		$item['pic_url'] = $_SESSION['HOST_URL'].$item['pic_url'];
        		$item['pic_url'] = str_replace('//', '/', $item['pic_url']);
        	}
			switch($item['stuff_status']){
				case 1:
				$item['stuff_status'] = 'new';	
				break;
				case 2:
				$item['stuff_status'] = 'second';
				break;
				default:
				$item['stuff_status'] = 'unused';		
			}
        	switch($item['g_on_sale']){
				case 1:
				$item['approve_status'] = 'onsale';	
				break;
				default:
				$item['approve_status'] = 'instock';		
			}
        }
        return array('total_results'=>$int_count,'list'=>$ary_items);
    }
    
 	/**
	 * 商品下架
	 * request params
	 * @num_iid 	Number 必须 	1000231		商品数字ID，该参数必须 
	 * reponse params
	 * @item 	返回商品更新信息：返回的结果是:num_iid和modified
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-05-31
	 */   
    public function itemUpdateDelisting($array_params){
    	//测试商品
    	//$array_params['num_iid'] = '1172';
    	$good_obj = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
		$res = $good_obj->where(array('g_id'=>$array_params['num_iid']))->save(array('g_on_sale'=>'2','g_off_sale_time'=>date('Y-m-d H:i:s')));
		if($res){
			$response = array();
			$response['item'] = array('modified'=>date('Y-m-d H:i:s'),'num_iid'=>$array_params['num_iid']);
			return $response;
		}else{
			return false;
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
    public function itemUpdateListing($array_params){
    	$good_obj = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
    	$goodinfo_obj = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
		$ary_where = array();
        $ary_where['g_id']  = $array_params['num_iid'];
        $ary_where['pdt_status'] = '1';
    	$pcount = $products->where($ary_where)->count();
    	$save_data = array();
    	if($pcount == '1'){
    		$save_data['g_update_time'] = date('Y-m-d H:i:s');
    		$save_data['g_stock'] = $array_params['num'];
    		$goodinfo_obj->where(array('g_id'=>$array_params['num_iid']))->save($save_data);
    		$products->where(array('g_id'=>$array_params['num_iid']))->save(array('pdt_stock'=>$array_params['num'],'pdt_update_time'=>date('Y-m-d H:i:s')));
    	}
		$res = $good_obj->where(array('g_id'=>$array_params['num_iid']))->save(array('g_on_sale'=>'1','g_on_sale_time'=>date('Y-m-d H:i:s')));
		if($res){
			$response = array();
			$response['item'] = array('modified'=>date('Y-m-d H:i:s'),'num_iid'=>$array_params['num_iid']);
			return $response;
		}else{
			return false;
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
    public function itemQuantityUpdate($array_params){
    	$good_obj = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
    	$goodinfo_obj = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
        if(empty($array_params['sku_id']) && empty($array_params['outer_id'])){
        	$goodinfo_obj->startTrans();
        	$ary_where = array();
	        $ary_where['g_id']  = $array_params['num_iid'];
	        $ary_where['pdt_status'] = '1';
	    	$p_infos = $products->field('pdt_id,g_id,pdt_total_stock,pdt_stock,pdt_freeze_stock')->where($ary_where)->select();
	    	$pcount = count($p_infos);
			if($pcount == 0){
				$goodinfo_obj->rollback();
				return array('error_status'=>true,'error_message'=>'商品ID已不存在:'.$array_params['num_iid']);
			}
	    	$save_data = array();
	    	if($pcount == '1'){
	    		//增量更新
	    		if($array_params['type'] == '2'){
	    			$num1 = $p_infos[0]['pdt_total_stock']+$array_params['quantity'];
					$free_num = $p_infos[0]['pdt_freeze_stock'];
					if($p_infos[0]['pdt_freeze_stock']>$num1){
						$free_num = $num1;
					}
					$num = $num1-$free_num;
					if($num<0){
						$num = 0;
					}
	    		}else{
	    			$num = $array_params['quantity'];
					$free_num=0;
					/*$free_num = $p_infos[0]['pdt_freeze_stock'];
					if($p_infos[0]['pdt_freeze_stock']>$num1){
						$free_num = $num1;
					}
					$num = $array_params['quantity']-$free_num;*/
					if($num<0){
						$num = 0;
					}					
	    		}
	    		$save_data['g_update_time'] = date('Y-m-d H:i:s');
	    		$save_data['g_stock'] = $num;
	    		$res = $goodinfo_obj->where(array('g_id'=>$array_params['num_iid']))->save($save_data);
	    		//if(!$res){
	    			//$goodinfo_obj->rollback();
					//return array('error_status'=>true,'error_message'=>'更新商品总库存失败:'.$goodinfo_obj->getLastSql());
                    //return false;
	    		//}
				$save_sku_data = array('pdt_update_time'=>date('Y-m-d H:i:s'),'pdt_total_stock'=>$num,'pdt_stock'=>$num,'pdt_freeze_stock'=>$free_num);
				if($free_num < 0){
					$save_sku_data['pdt_stock'] = $num1;
					$save_sku_data['pdt_freeze_stock'] = 0;
				}
	    		if(!empty($array_params['on_way_quantity'])){
					$save_sku_data['pdt_on_way_stock'] = intval($array_params['on_way_quantity']);
				}
				$res2 = $products->where(array('pdt_id'=>$p_infos[0]['pdt_id']))
	    		->save($save_sku_data);
	    		if(!$res2){
	    			$goodinfo_obj->rollback();
					return array('error_status'=>true,'error_message'=>'更新失败:'.$products->getLastSql());
                    //return false;
	    		}	    	
	    		$goodinfo_obj->commit();
	    	}        	
        }else{
        	$goodinfo_obj->startTrans();
        	$pdt_where = array();
        	$pdt_where['g_id'] = $array_params['num_iid'];
        	if(!empty($array_params['outer_id'])){
        		if(empty($array_params['sku_id'])){
        			$pdt_where['pdt_sn'] = $array_params['outer_id'];
        		}
        	}
        	if(!empty($array_params['sku_id'])){
        		$pdt_where['pdt_id'] = $array_params['sku_id'];
        	}	
        	$p_info = $products->field('pdt_id,g_id,pdt_stock,pdt_total_stock,pdt_freeze_stock')->where($pdt_where)->find();
			if(count($p_info) == 0){
				$goodinfo_obj->rollback();
				return array('error_status'=>true,'error_message'=>'商品已不存在,商品ID:'.$array_params['num_iid'].',规格ID:'.$array_params['sku_id']);
			}
        	if(!empty($p_info)){
        		if($array_params['type'] == '2'){
        			$pdtnum1 = $p_info['pdt_total_stock']+$array_params['quantity'];
					$free_num = $p_info['pdt_freeze_stock'];
					if($pdtnum<0){
						$pdtnum = 0;
					}
					if($free_num<0){
						$free_num = 0;
					}
					if($p_info['pdt_freeze_stock']>$num1){
						$free_num = $num1;
					}	
					$pdtnum = $pdtnum1-$free_num;	
	    		}else{
	    			$pdtnum1 = $array_params['quantity'];
					$free_num = $p_info['pdt_freeze_stock'];
					if($pdtnum<0){
						$pdtnum = 0;
					}
					if($free_num<0){
						$free_num = 0;
					}
					if($p_info['pdt_freeze_stock']>$num1){
						$free_num = $num1;
					}	
	    			$pdtnum = $pdtnum1-$free_num;					
	    		}    
				$save_sku_data = array('pdt_total_stock'=>$pdtnum1,'pdt_stock'=>$pdtnum,'pdt_freeze_stock'=>$free_num,'pdt_update_time'=>date('Y-m-d H:i:s'));
				if($free_num < 0){
					$save_sku_data['pdt_stock'] = $pdtnum1;
					$save_sku_data['pdt_freeze_stock'] = 0;
				}
	    		if(!empty($array_params['on_way_quantity'])){
					$save_sku_data['pdt_on_way_stock'] = intval($array_params['on_way_quantity']);
				}
	    		$res = $products->where($pdt_where)->save($save_sku_data);
        		if(!$res){
	    			$goodinfo_obj->rollback();
					return array('error_status'=>true,'error_message'=>'更新失败:'.$products->getLastSql());
                    //return false;
	    		}
	    		$total_num = $products->where(array('g_id'=>$array_params['num_iid'],'pdt_status'=>'1'))->sum('pdt_stock');
	    		$save_data = array();
		    	$save_data['g_update_time'] = date('Y-m-d H:i:s');
	    		$save_data['g_stock'] = $total_num;
	    		$res1 = $goodinfo_obj->where(array('g_id'=>$array_params['num_iid']))->save($save_data);   
        	   // if(!$res1){
	    			//$goodinfo_obj->rollback();
					//return array('error_status'=>true,'error_message'=>'更新商品总库存失败:'.$goodinfo_obj->getLastSql());
                    //return false;
	    		//} 		
        	}
        	$goodinfo_obj->commit();
        }
		
		$ary_data = $products->field('pdt_id,pdt_stock,pdt_update_time,g_id')
		->where(array('g_id'=>$array_params['num_iid'],'pdt_status'=>'1'))->select();
		$item = array();
		if(!empty($ary_data)){
			$totalNum = 0;
			foreach($ary_data as $val){
				$totalNum = $totalNum+$val['pdt_stock'];
				$item['item']['skus']['sku'][] = array(
					'modified'=>$val['pdt_update_time'],
					'quantity'=>$val['pdt_stock'],
					'sku_id'=>$val['pdt_id']
				);
			}
			$item['item']['iid'] = $ary_data[0]['g_id'];	
			$item['item']['num_iid'] = $ary_data[0]['g_id'];	
			$item['item']['num'] = $totalNum;	
			$item['item']['modified'] = date('Y-m-d H:i:s');			
		}
		return $item;
    } 
    
	/**
     * 新增商品
     * request params
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-31
     */
    public function addGood($array_params){
    	$ary_top_items = $array_params;
    	$convert_rules = array(
    			'item_cats' => array('value' => 0, 'comment' => '分销店铺分类'),
    			'used_top_cat' => array('value' => 1, 'comment' => '如果没有找到分销店铺分类，是否使用淘宝分类'),
    			'item_brand' => array('value' => 0, 'comment' => '分销店铺品牌'),
    			'used_top_brand' => array('value' => 1, 'comment' => '如果没有找到分销店铺品牌，是否使用淘宝品牌'),
    			'set_new' => array('value' => 0, 'comment' => '设置新品，布尔值'),
    			'set_hot' => array('value' => 0, 'comment' => '设置品牌热销，布尔值'),
    			'on_sales' => array('value' => 0, 'comment' => '是否上架销售')
    	);
    	M('',C(''),'DB_CUSTOM')->startTrans();
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
    		$ary_add_goods ['g_picture'] = trim ( $this->downloadTopImageToLocal ( $ary_top_items ['pic_url'],0,'./Public/Uploads/' . CI_SN.'/goods/erp' ) );
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
    		$ary_cats_result = $this->convertTopItemCats($mixd_goods_id, $convert_rules, $seller_cids);
    		if ($ary_cats_result['status'] == false) {
    			return array('status' => false, 'err_msg'=>'更新第三方平台分类关联关系失败！', 'err_code'=>8104);
    		}
    	}
    	//处理商品品牌
        $brand_name = $ary_top_items['brand_name'];
    	$ary_brand_reault = $this->convertTopBrandTolocal($int_goods_id,$brand_name,$convert_rules);
    	//处理商品图片
    	$ary_picture_reault = $this->synItemImagesToLocal($ary_top_items['item_imgs']['item_img'], $mixd_goods_id, $ary_top_items['pic_url']);
    	if ($ary_picture_reault['status'] == false) {
    		return array('status' => false, 'err_msg'=>'更新商品图片失败！', 'err_code'=>8105);
    	}
    	//判断类型表中有没有一个叫未分类类型的,如果没有则插入
    	$gt_id = $this->getDeafultTopType();
    	$props_name = $ary_top_items['props_name'];
    	if(!empty($props_name)){
    		$props_name = explode(';',$props_name);
    		foreach($props_name as $prop_name){
    			$prop_name = explode(':',$prop_name);
    			$is_prop_exist = D('Gyfx')->selectOne('goods_spec','gs_id',array('gs_name'=>$prop_name[2],'gs_status'=>'1'));
    			//属性ID
    			$local_prov_id = 0;
    			if(empty($is_prop_exist)){
    				//新增属性
    				$ary_prop_add = array();
    				$ary_prop_add['gs_name'] = $prop_name[2];
    				//暂时都为0
    				$ary_prop_add['gs_is_sale_spec'] = 0;
    				$ary_prop_add['gs_create_time'] = date('Y-m-d H:i:s');
    				$ary_prop_add['gs_update_time'] = date('Y-m-d H:i:s');
    				$ary_prop_add['thd_indentify'] = 9999;
    				$local_prov_id = D('Gyfx')->insert('goods_spec',$ary_prop_add);
    				if(!isset($local_prov_id)){
    					return array('status' => false, 'err_msg'=>'新增商品属性失败！', 'err_code'=>8105);
    				}
    			}else{
    				$local_prov_id = $is_prop_exist['gs_id'];
    			}
    			//更新属性类型关联表
    			//在关联表中插入相应数据。注：货号不属于扩展属性
    			@$ra_insert = D('Gyfx')->insert('related_goods_type_spec',array(
    					'gs_id' => $local_prov_id,
    					'gt_id' => $gt_id
    			),1);
    			//更新属性明细表
    			$is_exist_spec_detail = D('Gyfx')->selectOne('goods_spec_detail','gsd_id',array('gs_id'=>$local_prov_id,'gsd_value'=>$prop_name[3]));
    			$gsd_id = 0;
    			if(empty($is_exist_spec_detail)){
    				//新增属性值
    				$ary_value_add = array();
    				$ary_value_add['gs_id'] = $local_prov_id;
    				//edit插入新属性值的时候，此处判断如果存在别名，则将别名当作新属性值名称插入
    				$ary_value_add['gsd_value'] = $prop_name[3];
    				$ary_value_add['gsd_create_time'] = date('Y-m-d H:i:s');
    				$ary_value_add['gsd_update_time'] = date('Y-m-d H:i:s');
    				$ary_value_add['thd_indentify'] = 9999;
    				$gsd_id = D('Gyfx')->insert('goods_spec_detail',$ary_value_add);
    				if(!isset($gsd_id)){
    					return array('status' => false, 'err_msg'=>'新增商品属性值失败！', 'err_code'=>8105);
    				}
    			}else{
    				$gsd_id = $is_exist_spec_detail['gsd_id'];
    			}
				//更新关联表
				$is_exist_related_gs = D('Gyfx')->selectOne('related_goods_spec','',array('g_id'=>$mixd_goods_id,'gs_id'=>$local_prov_id,'gsd_id'=>$gsd_id));
				if(!isset($is_exist_related_gs)){
					$ary_related_goods_spec = array(
    	    			'gs_id'=>$local_prov_id,
    					'gsd_id'=>$gsd_id,
    					'g_id'=>$mixd_goods_id,
    				    'gsd_aliases'=>$prop_name[3],
						'gs_is_sale_spec'=>0
					);
					$return_related_gs = D('Gyfx')->insert('related_goods_spec',$ary_related_goods_spec);
					if(!isset($return_related_gs)){
						return array('status' => false, 'err_msg'=>'新增商品属性值关联表失败！', 'err_code'=>8105);
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
    			$ary_sku_info['pdt_sale_price'] = !empty($ary_top_items['price'])?($ary_top_items['price']):'';
    			$ary_sku_info['pdt_market_price'] = !empty($ary_top_items['price'])?($ary_top_items['price']):'';
    			$weight = $ary_top_items['item_weight']*1000;
    			$ary_sku_info['pdt_weight'] = !empty($weight)?($weight):0;
    			$ary_sku_info['pdt_total_stock'] = !empty($ary_top_items['num'])?($ary_top_items['num']):0;
    			$ary_sku_info['pdt_stock'] = !empty($ary_top_items['num'])?($ary_top_items['num']):0;
    			$ary_sku_info['pdt_create_time'] = date('Y-m-d H:i:s');
    			$ary_sku_info['pdt_update_time'] = date('Y-m-d H:i:s');
    			$ary_sku_info['thd_indentify'] = 9999;
    			$ary_sku_info['thd_pdtid'] = !empty($ary_top_items['num_iid'])?($ary_top_items['num_iid']):'';
    			//验证该SKU在本地系统中是否已经存在，如过已经存在，则更新之
    			$ary_check_exist_con = array('g_sn' => $ary_add_goods['g_sn'], 'pdt_sn' => $ary_add_goods['g_sn']);
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
    				//直接新增到本地数据库
    				$ary_sku_info = array();
    				$ary_sku_info['g_id'] = $mixd_goods_id;
    				$ary_sku_info['g_sn'] = $ary_add_goods['g_sn'];
    				//$ary_sku_info['pdt_sn'] = $mixd_goods_id . str_replace(':', '', str_replace(';', '', $pdt_spec));
    				//此处将使用淘宝的sku['outer_id'] 存成本地products表中的pdt_sn
    				if (empty($val['outer_id']) || $val['outer_id'] == '') {
    					return array('status' => false, 'err_code' => 8111, 'err_msg' => '商家编码不存在!');
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
    				$ary_sku_info['thd_indentify'] = 9999;
    				$ary_sku_info['thd_pdtid'] = $val['sku_id'];
    				//验证该SKU在本地系统中是否已经存在，如过已经存在，则更新之
    				$ary_check_exist_con = array('g_sn' => $ary_add_goods['g_sn'], 'pdt_sn' => $ary_sku_info['pdt_sn']);
    				//更新商品属性关联表 related_goods_spec
    				//更新商品销售属性关联表
    				$spec = explode(';',$val['properties_name']);
    				$pdt_memo = '';
    				//判断商品属性是否存在，不存在新增属性
    				foreach($spec as $spec_info){
    					$spec_name = explode(':',$spec_info);
    					$pdt_memo .=$spec_name[2].':'.$spec_name[3].';';
    				}
    				$pdt_memo = rtrim($pdt_memo,';');
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
    				foreach($spec as $spec_info){
    					$prop_name = explode(':',$spec_info);

    					$is_prop_exist = D('Gyfx')->selectOne('goods_spec','gs_id',array('gs_name'=>$prop_name[2],'gs_status'=>'1'));
    					//属性ID
    					$local_prov_id = 0;
    					if(empty($is_prop_exist)){
    						//新增属性
    						$ary_prop_add = array();
    						$ary_prop_add['gs_name'] = $prop_name[2];
    						//暂时都为0
    						$ary_prop_add['gs_is_sale_spec'] = 1;
    						$ary_prop_add['gs_create_time'] = date('Y-m-d H:i:s');
    						$ary_prop_add['gs_update_time'] = date('Y-m-d H:i:s');
    						$ary_prop_add['thd_indentify'] = 9999;
    						$local_prov_id = D('Gyfx')->insert('goods_spec',$ary_prop_add);
    						if(!isset($local_prov_id)){
    							return array('status' => false, 'err_msg'=>'新增商品属性失败！', 'err_code'=>8105);
    						}
    					}else
    						//修改属性
    						$ary_prop_update = array();
    					$ary_prop_update['gs_is_sale_spec'] = 1;
    					$ary_prop_update['gs_update_time'] = date('Y-m-d H:i:s');
    					$local_prov_id = D('Gyfx')->update('goods_spec',array('gs_name'=>$prop_name[2]),$ary_prop_update);
    					if(!isset($local_prov_id)){
    						return array('status' => false, 'err_msg'=>'新增商品属性失败！', 'err_code'=>8105);
    					}
    					
    					//更新属性类型关联表
    					//在关联表中插入相应数据。注：货号不属于扩展属性
    					@$ra_insert = D('Gyfx')->insert('related_goods_type_spec',array(
    							'gs_id' => $local_prov_id,
    							'gt_id' => $gt_id
    					),1);
    					//更新属性明细表
    					$is_exist_spec_detail = D('Gyfx')->selectOne('goods_spec_detail','gsd_id',array('gs_id'=>$local_prov_id,'gsd_value'=>$prop_name[3]));
    					$gsd_id = 0;
    					if(empty($is_exist_spec_detail)){
    						//新增属性值
    						$ary_value_add = array();
    						$ary_value_add['gs_id'] = $local_prov_id;
    						//edit插入新属性值的时候，此处判断如果存在别名，则将别名当作新属性值名称插入
    						$ary_value_add['gsd_value'] = $prop_name[3];
    						$ary_value_add['gsd_create_time'] = date('Y-m-d H:i:s');
    						$ary_value_add['gsd_update_time'] = date('Y-m-d H:i:s');
    						$ary_value_add['thd_indentify'] = 9999;
    						$gsd_id = D('Gyfx')->insert('goods_spec_detail',$ary_value_add);
    						if(!isset($gsd_id)){
    							return array('status' => false, 'err_msg'=>'新增商品属性值失败！', 'err_code'=>8105);
    						}
    					}else{
    						$gsd_id = $is_exist_spec_detail['gsd_id'];
    					}

    					//更新关联表
    					$is_exist_related_gs = D('Gyfx')->selectOne('related_goods_spec','',array('g_id'=>$mixd_goods_id,'gs_id'=>$local_prov_id,'gsd_id'=>$gsd_id));
    					if(!isset($is_exist_related_gs)){
    						$ary_related_goods_spec = array(
    								'gs_id'=>$local_prov_id,
    								'gsd_id'=>$gsd_id,
    								'g_id'=>$mixd_goods_id,
    								'gsd_aliases'=>$prop_name[3],
    								'gs_is_sale_spec'=>1
    						);
    						$return_related_gs = D('Gyfx')->insert('related_goods_spec',$ary_related_goods_spec);
    						if(!isset($return_related_gs)){
    							return array('status' => false, 'err_msg'=>'新增商品属性值关联表失败！', 'err_code'=>8105);
    						}
    					}else{
    						$ary_related_goods_spec = array(
    								'gs_id'=>$local_prov_id,
    								'gsd_id'=>$gsd_id,
    								'g_id'=>$mixd_goods_id
    						);
    						$return_related_gs = D('Gyfx')->update('related_goods_spec',$ary_related_goods_spec,array('gs_is_sale_spec'=>1));
    					}
    				}
    			}
   			}
    		M('',C(''),'DB_CUSTOM')->commit();
    	//商品相关信息全部更新完成，提交事务，一条商品记录更新完毕...
    	return array('status' => true, 'err_code' => 0, 'err_msg' => '','item'=>array('num_iid'=>$mixd_goods_id,'created'=>date('Y-m-d H:i:s')));
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
    	$ary_add_goods['erp_guid'] = trim($ary_top_items['erp_guid']);
    	$ary_add_goods['g_create_time'] =  date('Y-m-d H:i:s');  //创建时间
    	$ary_add_goods['g_update_time'] =  date('Y-m-d H:i:s');	 //更新时间
    	$ary_add_goods['thd_indentify'] = 8888;
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
    	return $ary_add_goods;
    }
    
    /**
     * 下载的商品类型默认为淘宝类型，如果不存在则添加
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-32
     */
    public function getDeafultTopType() {
    	$ary_type_data = D('GoodsType')->getGoodsType(array('gt_name' => '第三方未分类类型'),$ary_field='*',$ary_order);
    	if ($ary_type_data[0]['gt_id']) {
    		$gt_id = $ary_type_data[0]['gt_id'];
    	} else {
    		$gt_id = D('GoodsType')->addGoodsType(array(
    				'gt_name' => '第三方未分类类型',
    				'gt_status' => 1
    		));
    	}
    	return $gt_id;
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
//     			$ary_tmp_istop = explode('.taobaocdn.com', $val);
//     			if (count($ary_tmp_istop) < 2) {
//     				continue;
//     			}
    			$ary_replace_goal[] = $val;
    			$ary_replace_to[] = $this->downloadTopImageToLocal($val,1,'./Public/Uploads/' . CI_SN.'/desc/erp');
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
    				return 'http://' . $_SERVER['SERVER_NAME'] . $str_requert_port  . ltrim($str_url,'.');
    			}else{
    				return ltrim($str_url,'.');
    			}
    		}
    	}
    	//容错，返回原地址
    	return $str_top_img_url;
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
    protected function convertTopItemCats($int_gods_id, $ary_config, $ary_top_seller_cats) {
    	//$ary_top_seller_cats  castc:类目一,1234:类目二
    	if ($ary_config['used_top_cat']['value'] == '1') {
    		//获取淘宝店铺ID
    		//用户配置了使用淘宝分类，这里还要处理多对多的情况
    		if ('' != trim($ary_top_seller_cats)) {
    			
    			$ary_tmp_seller_cats = explode(';', trim($ary_top_seller_cats));
    			if (is_array($ary_tmp_seller_cats) && count($ary_tmp_seller_cats) > 0) {
    				foreach($ary_tmp_seller_cats as $cate_info){
    					$cate_info = explode(':',$cate_info);
    					//验证当前淘宝店铺分类在本地系统分类中是否已经存在,按商品名搜索
    					$ary_exist_con = array('gc_name' => $cate_info[1]);
    					try {
    						$mixed_esist = D('Gyfx')->selectOne('goods_category','',$ary_exist_con);
    					} catch (PDOException $e) {
    						//验证失败，返回错误信息
    						return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_001', 'message' => '验证类目错误');
    					}

    					if (is_array($mixed_esist) && !empty($mixed_esist) && count($mixed_esist) > 0) {
							// 该分类已经同步过来，验证状态是否还有效，如果已经被删除，则将其充值为有效,并更新淘宝分类ID
							$ary_edit = array (
									'gc_status' => 1,
									'gc_update_time' => date ( 'Y-m-d H:i:s' ) 
							);
							$ary_cont = array (
									'gc_id' => $mixed_esist ['gc_id'] 
							);
							try {
								$mixed_result = D ( 'Gyfx' )->update ( 'goods_category', $ary_cont, $ary_edit );
							} catch ( PDOException $e ) {
								return array (
										'status' => false,
										'error_code' => 'addTaobaoSellerCatsToLocal_002',
										'message' => '更新分类失败' 
								);
							}
    						return array('status' => true, 'cat_id' => $mixed_esist['gc_id'], 'error_code' => '', 'message' => '');
    					}else{
    						//分类还未同步过来，直接新增一个分类
    						//如果该分类不是叶子节点，要找他的父分类，并新增-- ^-^ 还好，淘宝分类只有两级~~
    						$ary_cats_add['gc_parent_id'] = 0;
    						$ary_cats_add['gc_is_parent'] = 0;
    						$ary_cats_add['gc_name'] = $cate_info[1];
    						$ary_cats_add['gc_order'] = 0;
    						$ary_cats_add['thd_catid'] = $cate_info[0];
    						$ary_cats_add['thd_indentify'] = 9999;
    						$ary_cats_add['gc_is_display'] = 1;
    						$ary_cats_add['gc_create_time'] = date('Y-m-d H:i:s');
    						$ary_cats_add['gc_update_time'] = date('Y-m-d H:i:s');
    						//创建分类本身
    						try {
    							$mixed_result = D('Gyfx')->insert('goods_category',$ary_cats_add);
    						} catch (PDOException $e) {
    							return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_004', 'message' => '');
    						}
    						return array('status' => true, 'cat_id' => $mixed_result, 'error_code' => '', 'message' => '');
    					}
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
     * 第三方未分类商品
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    public function getDefaultTopCategory() {
    	$ary_cond = array('gc_name' => '第三方未分类商品');
    	$ary_mixed_result = D('Gyfx')->selectOne('goods_category',null, $ary_cond);
    	if (is_array($ary_mixed_result) && count($ary_mixed_result) > 0) {
    		return array('status' => true, 'cat_id' => $ary_mixed_result['gc_id'], 'message' => '');
    	}
    	//添加淘宝默认分类
    	$ary_add_parent['gc_parent_id'] = 0;
    	$ary_add_parent['gc_is_parent'] = 0;
    	$ary_add_parent['gc_name'] = '第三方未分类商品';
    	$ary_add_parent['gc_order'] = 0;
    	$ary_add_parent['gc_is_display'] = 1;
    	$ary_add_parent['gt_id'] = 0;
    	$ary_add_parent['gc_description'] = '未分类的第三方商品';
    	$ary_add_parent['gc_create_time'] = date('Y-m-d H:i:s');
    	$ary_add_parent['gc_update_time'] = date('Y-m-d H:i:s');
    	try {
    		$mixed_result = D('Gyfx')->insert('goods_category',$ary_add_parent);
    	} catch (PDOException $e) {
    		return array('status' => false, 'cat_id' => 0, 'message' => '新增第三方商品默认分类失败!');
    	}
    	return array('status' => true, 'cat_id' => $mixed_result, 'message' => '');
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
    		$str_local_image_url = $this->downloadTopImageToLocal(trim($val['url']),0,'./Public/Uploads/' . CI_SN.'/goods/erp');
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
     * 将淘宝品牌转换到本地
     *
     * @params $array_post_setdata 用户提交的设置数组
     * @return array('status'=>true|false,message=>'提示信息')
     * @author Mithern
     * @modify 2012-03-13
     * @version 1.0
     */
    public function convertTopBrandTolocal($int_goods_id,$brand_name,$convert_rules) {
    	//配置了同步品牌
    		//如果淘宝品牌不存在。。。
    		if (!empty($brand_name) && $convert_rules['used_top_brand']['value'] == '1') {
    				//edit end ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    				//验证品牌在本地是否在本地存在
    				$ary_exist_cond = array('gb_name' =>$brand_name);
    				try {
    					$mixed_result = D('Gyfx')->selectOne('goods_brand','gb_id',$ary_exist_cond);
    				} catch (PDOException $e) {
    					return array('status' => false, 'data' => array(), 'message' => '验证品牌是否存在时出现错误' . __FILE__ . __LINE__);
    				}
    				//拼接品牌信息
    				$ary_item_brand_info = array();
    				$ary_item_brand_info['gb_name'] = $brand_name;
    				$ary_item_brand_info['gb_detail'] = '第三方品牌:'.$brand_name;
    				$ary_item_brand_info['gb_update_time'] = date('Y-m-d H:i:s');
    					
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
	    	}else{
	    		$brand_info = $this->getDefaultTopBrand();
	    		if($brand_info['status'] == true){
	    			$int_brand_id = $brand_info['b_id'];
	    		}else{
	    			return array('status' => false, 'data' => array(), 'message' => '获取本地默认未分淘宝品牌商品报错');
	    		}
	    	}
	    	//更新商品表，增加商品关联
	    	$ary_goods_condition = array('g_id' => $int_goods_id);
	    	$ary_fields = array('gb_id' => $int_brand_id);
	    	try {
	    		$mixed_result = D('Gyfx')->update('goods',$ary_goods_condition,$ary_fields);
	    	} catch (PDOException $e) {
	    		return array('status' => false, 'data' => array(), 'message' => '新增品牌和商品的关联失败！');
	    	}
	    	return array('status' => true, 'data' => array(), 'message' => 'normal');
    }
    
    /**
     * 第三方未分类品牌
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-31
     */
    public function getDefaultTopBrand() {
    	$ary_cond = array('gb_name' => '第三方未分类品牌');
    	$ary_mixed_result = D('Gyfx')->selectOne('goods_brand',null, $ary_cond);
    	if (is_array($ary_mixed_result) && count($ary_mixed_result) > 0) {
    		return array('status' => true, 'cat_id' => $ary_mixed_result['gc_id'], 'message' => '');
    	}
    	//添加淘宝默认品牌
    	$ary_add_parent['gb_name'] = '第三方未分类品牌';
    	$ary_add_parent['gc_order'] = 0;
    	$ary_add_parent['gb_status'] = 1;
    	$ary_add_parent['gb_detail'] = '第三方未分类品牌';
    	$ary_add_parent['gb_create_time'] = date('Y-m-d H:i:s');
    	$ary_add_parent['gb_update_time'] = date('Y-m-d H:i:s');
    	try {
    		$mixed_result = D('Gyfx')->insert('goods_brand',$ary_add_parent);
    	} catch (PDOException $e) {
    		return array('status' => false, 'b_id' => 0, 'message' => '新增淘第三方未分类品牌失败!');
    	}
    	return array('status' => true, 'b_id' => $mixed_result, 'message' => '');
    }

    /**
     * 获取商品关联信息
     */
    public function getRelatedGoods($array_params){
        //是否查询缓存 默认查缓存
        $is_cache = $array_params['notCache'] == 1 ? 0 : 1;
    	$int_g_id = (int)$array_params['gid'];
    	if($int_g_id){
            $limit = empty($array_params['limit']) ? 5 : $array_params['limit'];
            $ary_relate_goods = array();
			if($is_cache == 1){
				$tmp_relatedgoods = D('Gyfx')->selectOneCache('goods','g_related_goods_ids', array('g_id'=>$int_g_id));
				$relatedgoods = trim($tmp_relatedgoods['g_related_goods_ids'],",");
                $where = array();
				$where['g_id'] = array('in',$relatedgoods);
                $ary_limit = array(
                    'page_no' => 1,
                    'page_size' => $limit
                );
				$ary_relate_goods = D("Gyfx")->selectAllCache('goods_info','g_id,g_name,g_price,g_picture',$where,null,null, $ary_limit);
				foreach($ary_relate_goods as &$ary_relate){
					$ary_relate['g_picture'] = D('QnPic')->picToQn($ary_relate['g_picture'],200,200);
				}
			}else{
				$relatedgoods = D("Goods")->where(array('g_id'=>$int_g_id))->getField('g_related_goods_ids');
				$relatedgoods = trim($relatedgoods,",");
				$where = array();
				$where['g_id'] = array('in',$relatedgoods);
                $limit_option = '0,'. $limit;
				$ary_relate_goods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->limit($limit_option)->select();
				foreach($ary_relate_goods as &$ary_relate){
					$ary_relate['g_picture'] = D('QnPic')->picToQn($ary_relate['g_picture'],200,200);
                    //$comment_statistics = M('goods_comment_statistics')->where(array('g_id'=>$ary_relate['g_id']))->find();
                    //$ary_relate['comment_statistics'] = $comment_statistics;
				}
			}
			$return_items["items"]["item"] = $ary_relate_goods;
            return $return_items;
    	}
    }
    
}
