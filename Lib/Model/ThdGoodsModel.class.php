<?php

/**
 * 第三方铺货下载的商品模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-20
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdGoodsModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 查询条件结果集
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @param array $group 分组
     * @param array $limit 查询数量
     */
    public function GetProductList($condition = array(), $ary_field = '',$group= '',$limit= '') {
        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                        ->join('fx_goods ON fx_goods.g_id = fx_goods_products.g_id')
                        ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                        ->join('fx_related_goods_category ON fx_related_goods_category.g_id = fx_goods.g_id')
                        ->join('fx_goods_category ON fx_goods_category.gc_id = fx_related_goods_category.gc_id')
                        ->field($ary_field)
                        ->where($condition)->group($group)->limit($limit['start'],$limit['end'])->select();
        return $res_products;
    }
    
    /**
     * 查询条件结果集
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-10
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @param array $group 分组
     * @param array $limit 查询数量
     */
    public function GetProductCount($condition = array(),$group= '') {
        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                        ->join('fx_goods ON fx_goods.g_id = fx_goods_products.g_id')
                        ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                        ->join('fx_related_goods_category ON fx_related_goods_category.g_id = fx_goods.g_id')
                        ->join('fx_goods_category ON fx_goods_category.gc_id = fx_related_goods_category.gc_id')
                        ->where($condition)->count('DISTINCT fx_goods.g_id');
        return $res_products;
    }
    
    /**
     * 获取相应第三方商品信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-22
     * @param array $ary_where
     * @return array $res
     */
    public function getThdGoods($ary_where = array(),$ary_field='*',$ary_order,$ary_limit) {
    	return $this->join('fx_goods on(fx_thd_goods.thd_goods_sn = fx_goods.g_sn)')
    	->where($ary_where)
    	->field($ary_field)
    	->order($ary_order)
    	->limit(($ary_limit['page_no']-1)*$ary_limit['page_size'],$ary_limit['page_size'])
    	->select();
    }
    
    /**
     * 获取相应第三方商品信息总数
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-22
     * @param array $ary_where
     * @return int $res
     */
    public function getThdGoodsCount($ary_where = array()) {
    	return $this->where($ary_where)->count();
    }
    
    /**
     * 更新数据
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-24
     * @param array $ary_where
     * @return int $res
     */
    public function updateThdGoods($ary_where,$data) {
    	return $this->where($ary_where)->data($data)->save();
    }  
     
    /**
     * 新增数据
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-24
     * @param array $ary_where
     * @return int $res
     */
    public function addThdGoods($data) {
    	return $this->data($data)->add();
    }    
    
    /**
     * 淘宝商品数据上传
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
     * @param array $ary_data
     * @return int $res 返回处理过后的淘宝API返回值
     */
    public function uploadTopItem($thd_item_id,$ary_data = array()) {
        /*
           这个新增的sku要去掉不然铺货不了淘宝店 122216507:3226292:厚薄:常规;   
        */
		$tmall_catIds=array(
			'16',        //女装/女士精品
			'30',        //男装 
			'1625',      //女士内衣/男士内衣/家居服
			'50011740',  //流行男鞋
			'50006843',  //女鞋
			'50012029',  //运动鞋new
			'50013886'   //户外/登山/野营/旅行用品
		);
	    //日志文件
	    $file_name=date('Ymd').'uploadtopitem.log';
		//获取淘宝商品的详细信息
        $ary_item_datas = $this->where(array('thd_goods_id' => $thd_item_id))->find();
        $ary_detail_data = json_decode($ary_item_datas['thd_goods_data'], true);
		//分销商品信息
		$local_itme_where=array('fx_goods.g_sn'=>$ary_item_datas['thd_goods_sn']);
		$local_itme_field=array('fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_desc,fx_goods_info.g_picture');
		$array_local_item_datas = D('Goods')->GetGoodList($local_itme_where,$local_itme_field);
		$array_local_item_datas=array_shift($array_local_item_datas);
    	$ary_upload_data = array();
		$ary_desc_pic= array();
		$ary_upload_data['type'] = 'fixed';
        $ary_upload_data['stuff_status'] = 'new';
        $ary_upload_data['approve_status'] = 'instock';
		$top = new TaobaoApi($ary_data['access_token']);
		$ary_upload_data['g_picture'] = $array_local_item_datas['g_picture'];
		$ary_upload_data['title'] = $array_local_item_datas['g_name'];
		if(empty($array_local_item_datas['g_desc'])){
			$ary_upload_data['desc'] = '请填写商品描述！';
		}else{
			$ary_upload_data['desc'] = htmlspecialchars_decode($array_local_item_datas['g_desc']);
			preg_match_all('|<img.*src="(.*)".*>|isU',$ary_upload_data['desc'],$ary_desc_pic);
			$ary_desc_pics_url=$ary_desc_pic[1];
			$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
    		$local_desc_pic_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port;
			if(!empty($ary_desc_pics_url) && is_array($ary_desc_pics_url)){
				foreach($ary_desc_pics_url as $key => $pic_url){
					if(!empty($pic_url) && $pic_url !='undefined' && $pic_url !='/Public/Uploads/'){
						$new_pic_url=str_replace($local_desc_pic_url,"",$pic_url);
						$new_pic_url=str_replace('//','/',$new_pic_url);
						$is_upload_where=array('ecfx_picture_path'=>$new_pic_url,'top_shop_code'=>$ary_data['shop_code']);
						$is_upload_field=array(
							'ecfx_picture_path,
							 top_picture_id,
							 top_picture_category_id,
							 top_created,top_modified'
						);
						$is_upload_res = D('ThdAgentsPictures')->getPicturesRecord($is_upload_where,$is_upload_field);
						if($is_upload_res != false && !empty($is_upload_res)){
							
							/**
							//替换图片 线上替换图片报错 暂时这样处理 下次升级
							$replace_pic_data=array(
								'picture_path'=>$is_upload_res['ecfx_picture_path'],
								'picture_id'=>$is_upload_res['top_picture_id']
							);
							$desc_replace_pic_res=D('ThdAgentsPictures')->replaceDetailPictureTop($replace_pic_data,$top);
							if($desc_replace_pic_res['status']){
								$desc_replace_pic_where=array('ecfx_picture_path'=>$new_pic_url);
								$desc_replace_pic_field=array('top_picture_path');
								$desc_replace_pic_url=D('ThdAgentsPictures')->getPicturesRecord($desc_replace_pic_where,$desc_replace_pic_field);
								$ary_upload_data['desc']=str_replace($pic_url,$desc_replace_pic_url['top_picture_path'],$ary_upload_data['desc']);
							}else{
								$ary_upload_data['desc']=str_replace($pic_url,'',$ary_upload_data['desc']);
								writeLog($desc_replace_pic_res['err_msg'],$file_name);
							}**/
							D('ThdAgentsPictures')->where($is_upload_where)->delete();
							$upload_pic_data=array('picture_path'=>$new_pic_url,'shop_code'=>$ary_data['shop_code']);
							$desc_upload_pic_res=D('ThdAgentsPictures')->uploadDetailPictureTop($upload_pic_data,$top);
					        $ary_upload_data['desc']=str_replace($pic_url,$desc_upload_pic_res['data']['top_picture_path'],$ary_upload_data['desc']);
						}else{//上传图片
							$upload_pic_data=array('picture_path'=>$new_pic_url,'shop_code'=>$ary_data['shop_code']);
							$desc_upload_pic_res=D('ThdAgentsPictures')->uploadDetailPictureTop($upload_pic_data,$top);
					        $ary_upload_data['desc']=str_replace($pic_url,$desc_upload_pic_res['data']['top_picture_path'],$ary_upload_data['desc']);
					}
					}
				}
			}
		}
		//上传商品主图片
        if (trim($array_local_item_datas['g_picture']) != '') {
			$str_major_path = $array_local_item_datas['g_picture'];
        }
		
		//商品所在城市和地区
        $ary_upload_data['location.state'] = $ary_detail_data['location']['state'];
        $ary_upload_data['location.city']  = $ary_detail_data['location']['city'];
        //属性和类目
        $ary_upload_data['props']          = $ary_detail_data['props'];
        $ary_upload_data['cid']            = $ary_detail_data['cid'];
        $ary_upload_data['property_alias'] = $ary_detail_data['property_alias'];
        $ary_upload_data['outer_id']       = $ary_detail_data['outer_id'];
        //返点和发票
		if($ary_data['has_invoice']){
			$ary_upload_data['has_invoice']='true';
		}else{
			$ary_upload_data['has_invoice']='false';
		}
        
        if ($ary_data['rebate_point'] == 1) {
            $ary_upload_data['auction_point'] = $ary_detail_data['auction_point'];
        }
		elseif($ary_data['rebate_point'] == 2){
            $ary_upload_data['auction_point'] = 5;
        }
		$items_price =0;
		$items_stock =0;
		if (isset($ary_detail_data['skus']['sku']) && !empty($ary_detail_data['skus']['sku'])) {
            $ary_skus_data = $ary_detail_data['skus']['sku'];
            //SKU的销售属性串，多个sku之间使用逗号分隔
            $sku_properties = '';
            //SKU的数量串，与sku依次对应，多个数量之间使用逗号分隔
            $sku_quantities = '';
            //SKU的价格串，多个价格之间使用逗号分隔，价格精确到两位小数
            $sku_prices = '';
            //SKU 的外部ID串，多个ID之间使用逗号分隔
            $sku_outer_ids = '';
            foreach ($ary_skus_data as $key => $sku_data) {
                $arr_properties = '';
                $ary_properties = explode(";",$sku_data['properties']);
				$sku_prop_names = explode(";",$sku_data['properties_name']);
                foreach($ary_properties as $prop_key => $prop_val){
					if($prop_key == 2 || $prop_key == 3){
						//过滤掉自定义属性
					}else{
						$arr_properties .= $prop_val.';';
					}
					
                }			
                $sku_properties  .= rtrim($arr_properties,';') . ',';
                $sku_outer_ids   .= $sku_data['outer_id'] . ',';
                $items_price  = $sku_data['price'];
                $sku_prices      .= round($sku_data['price'], 2) . ',';
                $items_stock += $sku_data['quantity'];
                $sku_quantities  .= $sku_data['quantity'] . ',';
            }
            $ary_upload_data['sku_properties'] = rtrim($sku_properties, ',');
            $ary_upload_data['sku_quantities'] = rtrim($sku_quantities, ',');
            $ary_upload_data['sku_prices']     = rtrim($sku_prices, ',');
            $ary_upload_data['sku_outer_ids']  = rtrim($sku_outer_ids, ',');
        } else {
            $items_price = $ary_detail_data['price'];
            $items_stock   = $ary_detail_data['num'];
        }
		/*xxx属性出错:类目属性在标准属性中不存在 xxx*/
		$ary_props_name=explode(';',$ary_detail_data['props_name']);
		$str_props_name='';
		$str_props_value='';
		foreach ($ary_props_name as $key => $props_data) {
			if(strpos($ary_upload_data['sku_properties'],$props_data) != false){
				
			}else{
				$ary_props_data=explode(':',$props_data);
				if($ary_props_data!='' && $ary_props_data[0]!='1627207' && $ary_props_data[0]!='20509' && $ary_props_data[0]!='20549'){
					if(strpos($ary_detail_data['input_pids'],$ary_props_data[0]) === false && strpos($ary_detail_data['input_str'],$ary_props_data[3]) === false){
						if($ary_props_data[3] !='其他' && $ary_props_data[3] !='其它' && $ary_props_data[3] !='其她'){
							$str_props_name  .=','.$ary_props_data[0];
							$str_props_value .=','.$ary_props_data[3];							
						}					
					}
				}				
			}
		}
		$ary_upload_data['num'] = $items_stock;
        $ary_upload_data['price'] = $items_price;
		$ary_upload_data['input_pids'] = rtrim($ary_detail_data['input_pids'], ',').$str_props_name;
        $ary_upload_data['input_str']  = rtrim($ary_detail_data['input_str'], ',').$str_props_value;
		//对尺码做下处理
		if(strpos($ary_upload_data['input_str'],'尺码;') > 0){     //使用绝对等于
			$ary_upload_data['input_str'] = str_replace("尺码;","",$ary_upload_data['input_str']);
		}
		if(strpos($ary_upload_data['input_str'],'颜色分类;') > 0){     //使用绝对等于
			$ary_upload_data['input_str'] = str_replace("颜色分类;","",$ary_upload_data['input_str']);
		}
		switch ($ary_data['has_freight']) {
            case 1:  //采用供货商设置
                $ary_upload_data['freight_payer'] = $ary_detail_data['freight_payer'];
                //物流费用非模版部分
                $ary_upload_data['post_fee']      = $ary_detail_data['post_fee'];
                $ary_upload_data['express_fee']   = $ary_detail_data['express_fee'];
                $ary_upload_data['ems_fee']       = $ary_detail_data['ems_fee'];
                //物流费用模版部分
				$new_postage_id=0;
				if(!empty($ary_detail_data['postage_id'])){
					$new_postage_id=$ary_detail_data['postage_id'];
				}
				$ary_logistic_data=array(
					'nick'       =>$ary_detail_data['nick'],
					'shop_code'  =>$ary_data['shop_code'],
					'postage_id' =>$new_postage_id
				);
				$ary_upload_data['postage_id'] = D('ThdLogisticTemplate')->getLogisticTemplateId($ary_logistic_data);
                break;
            case 2:   //采用淘宝默认平邮/快递/EMS 
                $ary_upload_data['freight_payer'] = 'buyer';//买家承担
                $ary_upload_data['post_fee'] = 10.00;
                $ary_upload_data['express_fee'] = 15.00;
                $ary_upload_data['ems_fee'] = 20.00;
                break;
        }
		//此处加上食品行业（如果存在则添加）
        if(is_array($ary_detail_data['food_security']) && !empty($ary_detail_data['food_security'])){
            $ary_upload_data['food_security.prd_license_no']    =$ary_detail_data['food_security']['prd_license_no'];
            $ary_upload_data['food_security.design_code']       =$ary_detail_data['food_security']['design_code'];
            $ary_upload_data['food_security.factory']           =$ary_detail_data['food_security']['factory'];
            $ary_upload_data['food_security.factory_site']      =$ary_detail_data['food_security']['factory_site'];
            $ary_upload_data['food_security.contact']           =$ary_detail_data['food_security']['contact'];
            $ary_upload_data['food_security.mix']               =$ary_detail_data['food_security']['mix'];
            $ary_upload_data['food_security.plan_storage']      =$ary_detail_data['food_security']['plan_storage'];
            $ary_upload_data['food_security.period']            =$ary_detail_data['food_security']['period'];
            $ary_upload_data['food_security.food_additive']     =$ary_detail_data['food_security']['food_additive'];
            $ary_upload_data['food_security.supplier']          =$ary_detail_data['food_security']['supplier'];
            $ary_upload_data['food_security.product_date_start']=$ary_detail_data['food_security']['product_date_start'];
            $ary_upload_data['food_security.product_date_end']  =$ary_detail_data['food_security']['product_date_end'];
            $ary_upload_data['food_security.stock_date_start']  =$ary_detail_data['food_security']['stock_date_start'];
            $ary_upload_data['food_security.stock_date_end']    =$ary_detail_data['food_security']['stock_date_end'];
        }
		//判断是上传还是更新商品
        $ary_check_item = array();
        $ary_check_item['thd_indentify'] = 1;
        $ary_check_item['thd_shop_sid']  = $ary_data['shop_code'];
        $ary_check_item['thd_item_id']   = $thd_item_id;
		$ary_check_field=array('thd_shop_item_iid');
		$ary_check_result = D('ThdUploadTmp')->getItemRecord($ary_check_item,$ary_check_field);
		$local_pics_where=array(
			'g_id'=>$array_local_item_datas['g_id']
		);
        $ts_seller_info_json = D('ThdShops')->where(array('ts_sid'=>$ary_data['shop_code']))->getField('ts_seller_info_json');
        $ts_seller_info = json_decode($ts_seller_info_json,1);
        $type = $ts_seller_info['user_seller_get_response']['user']['type'];
        if (is_array($ary_check_result) && count($ary_check_result) > 0) {
            $ary_upload_data['num_iid'] = $ary_check_result['thd_shop_item_iid'];
            //如果是对于天猫商家
            if($type == "B"){
                //$update_item_res = $top->updateItemTmall($ary_upload_data);
				$update_item_res = $top->updateItem($ary_upload_data);
				
            }else{
                $update_item_res = $top->updateItem($ary_upload_data);
            }
            
            if ($update_item_res['status'] == true) {
                $ary_update_data['last_upload_time'] = date('Y-m-d H:i:s');
                $ary_update_data['tut_update_time']  = date('Y-m-d H:i:s');
				D('ThdUploadTmp')->updateItemRecord($ary_check_item,$ary_update_data);
				
				$local_pics_res=D('GoodsPictures')->getItemPicture($local_pics_where,array('gp_picture,gp_order'));
				if(count($local_pics_res)>0){
					$key_index=count($local_pics_res);
					$local_pics_res[$key_index]= array('gp_picture'=>$str_major_path,'is_major'=>true);
				}else{
					$local_pics_res[]=array('gp_picture'=>$str_major_path,'is_major'=>true);
				}
				if(empty($local_pics_res)){
					writeLog($g_sn.'该商品没有图片！',$file_name);
				}else{
					$top_pic_res=$this->uploadPictureTop($update_item_res['data']['num_iid'],$top,$local_pics_res);
					if($top_pic_res['status']){
						return array('status'=>true,'g_name'=>$ary_upload_data['title'],'msg'=>'商品铺货更新成功！');
					}else{
						writeLog($update_item_res['err_msg'],$file_name);
						return array('status'=>false,'g_name'=>$ary_upload_data['title'],'msg'=>$top_pic_res['err_msg']);
					}
				}
				//商品下架
				/*$update_item_status_res = $top->delistingUpdateItem($ary_check_result['thd_shop_item_iid']);
				if(!$update_item_status_res['status']){
					return array('status'=>false, 'msg'=>$update_item_status_res['err_msg'],'err_code'=>$update_item_status_res['err_code']);
				}*/
            } else {
				writeLog($update_item_res['err_msg'],$file_name);
				if($update_item_res['err_code']==53){
					$update_item_res['err_msg']='授权过期,重新授权！';
					return array('status'=>false, 'msg'=>$update_item_res['err_msg'],'err_code'=>$update_item_res['err_code']);
				}
                return array('status'=>false,'g_name'=>$ary_upload_data['title'], 'msg'=>$update_item_res['err_msg']);
            }
        } else {
            //如果是对于天猫商家
            if($type == "B"){
                $res_item_upload = $top->addItemTmall($ary_upload_data);
            }else{
				//$ary_upload_data['input_pids'] = '13021751,20000,20677,20518,20518,122216347,-1,-1,-2,-2';
				//$ary_upload_data['input_str'] = '5410040156,23区,高腰,145/52A,150/56A,2016年春季,中裤,长裤,格子,条纹';				
                $res_item_upload = $top->addItemTop($ary_upload_data);
            }
			
            if ($res_item_upload['status'] == true) {
                $ary_insert_data = array();
                $ary_insert_data['thd_indentify'] = 1;
                $ary_insert_data['thd_shop_sid']      = $ary_data['shop_code'];
                $ary_insert_data['thd_item_id']       = $ary_item_datas['thd_goods_id'];
                $ary_insert_data['thd_shop_item_iid'] = $res_item_upload['data']['num_iid'];
                $ary_insert_data['last_upload_time']  = date('Y-m-d H:i:s');
                $ary_insert_data['tut_create_time']   = date('Y-m-d H:i:s');
                $ary_insert_data['tut_update_time']   = date('Y-m-d H:i:s');
				D('ThdUploadTmp')->addItemRecord($ary_insert_data);
				$local_pics_res=D('GoodsPictures')->getItemPicture($local_pics_where,array('gp_picture,gp_order'));
				if(count($local_pics_res)>0){
					$is_have = 0;
					foreach($local_pics_res as $local_key=>$res){
						if($str_major_path == $res['gp_picture']){
							$is_have = 1;
							$local_pics_res[$local_key]['is_major'] = true;
						}
					}
					if($is_have == 0){
						$key_index=count($local_pics_res);
						$local_pics_res[$key_index]= array('gp_picture'=>$str_major_path,'is_major'=>true);					
					}
				}else{
					$local_pics_res[]=array('gp_picture'=>$str_major_path,'is_major'=>true);
				}
				
				if(empty($local_pics_res)){
				    writeLog($g_sn.'该商品没有图片！',$file_name);
				}else{
					$top_pic_res=$this->uploadPictureTop($res_item_upload['data']['num_iid'],$top,$local_pics_res);
					if($top_pic_res['status']){
						return array('status'=>true,'g_name'=>$ary_upload_data['title'],'msg'=>'商品铺货成功！');
					}else{
				        writeLog($top_pic_res['err_msg'],$file_name);
						return array('status'=>false,'g_name'=>$ary_upload_data['title'],'msg'=>$top_pic_res['err_msg']);
					}
				}
                return array('status'=>true,'g_name'=>$ary_upload_data['title'],'msg'=>'商品铺货成功！');
            } else {
			    writeLog($res_item_upload['err_msg'],$file_name);
                return array('status'=>false,'g_name'=>$ary_upload_data['title'],'msg'=>$res_item_upload['err_msg']);
            }
        }
        return false;
    }
	/**
     * 上传商品图片到淘宝店铺
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param string $num_iid 
     * @param array  $ary_data 
     * @return array $ary_result
     */
    public function uploadPictureTop($num_iid,$top_obj,$ary_data) {
		$ary_result	= array('status'=>true,'err_msg'=>'','err_code'=>0);
		if(empty($num_iid)){
            $ary_result['err_code'] = 'pic 001';
            $ary_result['err_msg'] = '缺少参数：第三方平台商品ID!';
            return $ary_result;
        }
		
		$ary_res_good_pic	= $top_obj->getSingleGoodsInfo($num_iid);
        if(!$ary_res_good_pic['status']) {
            $ary_result['err_msg']	=$ary_res_good_pic['err_msg'];
    		$ary_result['err_code']	=$ary_res_good_pic['err_code'];
            return $ary_result;
		}else{
		    if(!empty($ary_res_good_pic['data']['item_imgs']) && is_array($ary_res_good_pic['data']['item_imgs'])) {
				foreach ($ary_res_good_pic['data']['item_imgs']['item_img'] as $ary_img) {
					$ary_res_del_gp	= $top_obj->delPictureTop($num_iid, $ary_img['id']);
				}
			}
		}
		
		//淘宝的图片最多只能是5张
		if(is_array($ary_data) && !empty($ary_data)) {
			foreach($ary_data as $key=>$ary_pic) {
                if($key>4){
                    break;
                }
				//是否启用七牛图片存储
				$img =  APP_PATH . '/' . ltrim($ary_pic['gp_picture'],'/');
				$img = str_replace('//','/',$img);
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){
					if(!file_exists($img)){
						@mkdir(dirname($img));
						$qn_pic_content = file_get_contents(D('QnPic')->picToQn('/' . ltrim($ary_pic['gp_picture'],'/')));
						if(!empty($qn_pic_content)){
							file_put_contents($img, $qn_pic_content);
							unset($qn_pic_content);							
						}
					}
				}
				$ary_filter	= array(
					'num_iid'	=> $num_iid,
					'image'		=> '@' . $img,
					'position'	=> $ary_pic['gp_order']
				);
				
				if(isset($ary_pic['is_major']) &&!empty($ary_pic['is_major'])){
					$ary_filter['is_major']='true';
					$ary_filter['position']='0';
				}

            	$array_result	= $top_obj->uploadItemImg($ary_filter);
        		if(isset($array_result['item_img_upload_response']['item_img'])) {
        			$ary_result['data']	= $array_result['item_img_upload_response']['item_img'];
        		}else{
        			$ary_result['status']	= false;
        			$ary_result['err_msg']	= $array_result['error_response']['msg'];
        			$ary_result['err_code']	= $array_result['error_response']['code'];
        		}
			}
		}
		return $ary_result;
	}
}
