<?php
/**
 * 九龙港接口 Model
 * @package Model
 * @version 7.6
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2014-07-28
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiJlgModel extends GyfxModel {

	/**
	 * 构造方法
	 * @author Hcaijin
	 * @date 2014-07-28
	 */

    protected $member_map=array(
         'id'=>'m_id',
         'guid'=>'thd_guid',
         'name'=>'m_name',
         'sex'=>'m_sex',
         'real_name'=>'m_real_name',
         'birthday'=>'m_birthday',
         'email'=>'m_email',
         'mobile'=>'m_mobile',
         'address'=>'m_address_detail',
         'grade'=>'fx_members_level.ml_code',
         'point'=>'total_point',
         'balance'=>'m_balance',
         'security_deposit'=>'m_security_deposit',
         'create_time'=>'m_create_time',
         'update_time'=>'m_update_time',
         'status'=>'m_status',
         'qq'=>'m_qq',
         'wangwang'=>'m_wangwang',
         'website_url'=>'m_website_url',
         'recommended'=>'m_recommended',
         'alipay_name'=>'m_alipay_name',
         'is_proxy'=>'is_proxy',
         'zipcode'=>'m_zipcode',
         'm_telphone'=>'m_telphone',
         'all_cost'=>'m_all_cost',
         'is_verify'=>'m_verify',
         'order_status'=>'m_order_status',
         'status'=>'m_status',
         'card_no'=>'m_card_no',
         'ali_card_no'=>'m_ali_card_no',
         'password'=>'m_password',
         'source'=>'open_source',
         'head_url'=>'m_head_url'
     );

    protected $item_category=array(
         'cid'=>'thd_catid',
         'name'=>'gc_name',
         'level_cid'=>'gc_level',
         //'parent_cid'=>'gc_parent_id',
         'is_parent'=>'gc_is_parent',
         'type'=>'gc_tye',
         'status'=>'gc_status',
         'ad_type'=>'gc_ad_type',
         'is_hot'=>'gc_is_hot',
         'pic_url'=>'gc_pic_url',
         'sort_order'=>'gc_order',
         'is_display'=>'gc_is_display',
         'keyword'=>'gc_keyword',
         'description'=>'gc_description'
     );

    protected $item_brand=array(
         'brand_sn'=>'gb_sn',
         'brand_name'=>'gb_name',
         'sort_order'=>'gb_order',
         'brand_url'=>'gb_url',
         'brand_logo'=>'gb_logo',
         'is_display'=>'gb_display',
         'status'=>'gb_status',
         'detail'=>'gb_detail'
     );

    public function __construct() {
        parent::__construct();
    }

    /**
    * 批量处理会员信息
    * request params
    * @author Hcaijin
    * @date 2014-07-28
    */
    public function MemberBatchEdit($array_params=array()){
        $int_flag = false;
        $ary_data = array('msg'=>'','data'=>array());
        $members = json_decode($array_params['members_list'],1);
        if(empty($members)){
            return array('msg'=>'members_list字段有误');
        }
        //事物开始
        $obj_trans = M ( '', C ( '' ), 'DB_CUSTOM' );
        $memberInfo = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $cityregion = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_trans->startTrans ();
        $ary_members = array();
        foreach($members as $member){
            $where = array();
            $data = $this->parseFields($this->member_map,$member);
            $where['m_name'] = $data['m_name'];
            //SNS日志记录
            if($data['open_source'] == 'SNS'){
                $m_where = array();
                $m_where['m_name'] = $data['m_name'];
                $m_where['m_mobile'] = array('neq',$data['m_mobile']);
                $m_rs = $memberInfo->where($m_where)->select();
                if($m_rs){
                    $msg = date('Y-m-d H:i:s').'      会员账号相同，手机号不同：'.$m_where."\r\n";
                    $this->logs('SnsToB2c',$msg);
                    continue;
                }
                $n_where = array();
                $n_where['m_name'] = array('neq',$data['m_name']);
                $n_where['m_mobile'] = $data['m_mobile'];
                $n_rs = $memberInfo->where($n_where)->select();
                if($n_rs){
                    $msg = date('Y-m-d H:i:s').'      会员账号不相同，手机号相同：'.$n_where."\r\n";
                    $this->logs('SnsToB2c',$msg);
                    continue;
                }
                $where['m_mobile'] = $data['m_mobile'];
                $data['m_sns'] = 1;
            }
            $data['m_verify'] = 2;
            $data['ml_id'] = 1;
            $rs = $memberInfo->where($where)->select();
            if(!empty($rs)){
                //$data['m_update_time'] = date('Y-m-d H:i:s');
                if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
                    $area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
                    $cwhere = array('cr_name'=>$area_name);
                    $cres = $cityregion->where($cwhere)->find();
                    $data['cr_id'] = $cres['cr_id'];
                }
                $data['m_mobile'] = strpos($data['m_mobile'],':') ? $data['m_mobile'] : encrypt($data['m_mobile']);
                $memberupdaters = $memberInfo->where($where)->save($data);
                if($memberupdaters !== false){
                    if($data['open_source'] == 'SNS'){
                        $insert_mid = $rs['m_id'];
                        $insert_data = array('u_id'=>$insert_mid,'field_id'=>19,'content'=>$data['m_head_url']);
                        $countInfo =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$insert_mid))->count();
                        if($countInfo){
                            $res_del =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$insert_mid))->delete();
                        }
                        D('MembersFieldsInfo')->add($insert_data);
                    }
                    $int_flag = true;
                    $ary_members[] = array('created'=>$data['m_update_time'],'name'=>$data['m_name']);
                }else{
                    $obj_trans->rollback ();
                    if($data['open_source'] == 'SNS'){
                        $msg = date('Y-m-d H:i:s').'      修改会员'.$rs['m_name'].'异常！\r\n';
                        $this->logs('SnsToB2c',$msg);
                    }else{
                        $ary_data = array('msg'=>'修改会员异常！');
                        return $ary_data;
                    }
                }
            }else{
                $data['m_create_time'] = date('Y-m-d H:i:s');
                if(!isset($data['open_source']) || $data['open_source'] == ''){
                    $data['open_source'] = 'TaobaoO2O';
                }
                if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
                    $area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
                    $cwhere = array('cr_name'=>$area_name);
                    $cres = $cityregion->where($cwhere)->find();
                    $data['cr_id'] = $cres['cr_id'];
                }
                $data['m_mobile'] = strpos($data['m_mobile'],':') ? $data['m_mobile'] : encrypt($data['m_mobile']);
                $insert_mid = $memberInfo->add($data);
                if(!empty($insert_mid)){
                    if($data['open_source'] == 'SNS'){
                        $insert_data = array('u_id'=>$insert_mid,'field_id'=>19,'content'=>$data['m_head_url']);
                        $countInfo =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$insert_mid))->count();
                        if($countInfo){
                            $res_del =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$insert_mid))->delete();
                        }
                        D('MembersFieldsInfo')->add($insert_data);
                    }
                    $int_flag = true;
                    $ary_members[] = array('created'=>$data['m_create_time'],'name'=>$data['m_name']);
                }else{
                    $obj_trans->rollback ();
                    if($data['open_source'] == 'SNS'){
                        $msg = date('Y-m-d H:i:s').'      新增会员'.$data['m_name'].'异常！\r\n';
                        $this->logs('SnsToB2c',$msg);
                    }else{
                        $ary_data = array('msg'=>'新增会员异常！');
                        return $ary_data;
                    }
                }
            }
        }
        $obj_trans->commit ();
        if($int_flag) {
            $ary_batch_edit_response = array();
            $ary_batch_edit_response['member_infos']['member_info'] = $ary_members;
            return $ary_batch_edit_response;
        }
        else return $ary_data;
    }

    /**
	 * 记录错误日志
     * @param string $code 同步脚本编号
     * @param string $msg 错误信息
	 */
	function logs($code,$msg){
	   $log_dir = APP_PATH . 'Runtime/Apilog/';
	   if(!file_exists($log_dir)){
           mkdir($log_dir,0700);
       }
       $log_file = $log_dir . date('Ym') .$code . '.log';
       $fp = fopen($log_file, 'a+');
       fwrite($fp, $msg);
       fclose($fp);
	}

    /**
     * 获取促销信息
     * $array_params['history_point'] 只用做SNS九龙金豆同步是记录历史九龙金豆数量
     */
    function PromotionsGet($array_params){
        $result = array('status'=>0,'msg'=>'');
        if($array_params['history_point']){
            $msg = date('Y-m-d H:i:s').'      记录当前会员同步的历史九龙金豆数量：'.$array_params['history_point']."\r\n";
            $this->logs('SNSPromotionsGet',$msg);
        }
        $data = $this->parseFields($this->member_map,$array_params);
        $where = array();
        $where['m_name'] = $data['m_name'];
        $memberInfo = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $res_data = $memberInfo->where($where)->find();
        if(!empty($res_data)){
            $point = $res_data['total_point']-$res_data['freeze_point'];
            if($point < 0){
                $point = 0;
            }
            $result = array('status'=>1,'point'=>$point,'financial'=>$res_data['m_balance'],'bonus'=>$res_data['m_bonus'],'jlb'=>$res_data['m_jlb']);
        }else{
            $result = array('status'=>0,'msg'=>'获取会员数据失败！');
        }
        return $result;
    }

    /**
     * 获取优惠券信息
     */
    function CouponGet($array_params){
        $result = array('status'=>0,'msg'=>'');
        $data = $this->parseFields($this->member_map,$array_params);
        $m_where['m_name'] = $data['m_name'];
        $memberInfo = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $res_data = $memberInfo->where($m_where)->find();
        if(!empty($res_data)){
            $where = array();
            $date = date('Y-m-d');
            $where['c_user_id']=$res_data['m_id'];
            $where['c_is_use']=0;
            $where['c_create_time']=array('elt',$date);
            //获取会员所有优惠券总数量
            $total =  D('Coupon')->where($where)->count();
            //获取会员所有优惠券列表
            $couponList = D('Coupon')->where($where)->order('c_create_time asc')->select();
            $coupons = array();
            foreach($couponList as $k => $coupon){
                $coupons[$k]['coupon_name']=$coupon['c_name'];
                //$coupons[$k]['coupon_type']=$coupon['c_type'] == 1?"折扣券":"现金券";
                $coupons[$k]['coupon_type']=$coupon['c_type']; 
                $coupons[$k]['sn']=$coupon['c_sn'];
                $coupons[$k]['money']=$coupon['c_money'];
                $coupons[$k]['condition_money']=$coupon['c_condition_money'];
                $coupons[$k]['memo']=$coupon['c_memo'];
            }
            $result = array('status'=>1,'total_results'=>$total,'coupon_list'=>$coupons);
        }else{
            $result = array('status'=>0,'msg'=>'获取会员数据失败！');
        }
        return $result;
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
    			'used_top_cat' => array('value' => 1, 'comment' => '如果没有找到分销店铺分类，是否使用九龙港分类'),
    			'used_top_brand' => array('value' => 1, 'comment' => '如果没有找到分销店铺品牌，是否使用九龙港品牌'),
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
    		//return array('status' => true, 'err_code' => 8101, 'err_msg' => '商品已被同步过');
    		$bool_is_update_items = false;
    		//修改商品直接删除商品分类关联
    		D('Gyfx')->deleteInfo('related_goods_category',array('g_id'=>$mixed_check_result['g_id']));
    		// 商品基本信息处理
    		$ary_add_goods = $this->itemSaveFields ( $ary_top_items, $convert_rules );
		if($mixed_check_result['g_on_sale'] == 1){
			unset($ary_add_goods['g_on_sale']);
		}
    		$ary_add_goods ['gt_id'] = $this->getDeafultTopType ();
    		//更新商品信息（商品主表和商品明细表）
    		$ary_goods = $ary_add_goods;
    		$ary_goods_info = $ary_add_goods;
		foreach($ary_goods as $k => $sku){
			if(empty($sku)){
				unset($ary_goods[$k]);
			}
		}
		unset($ary_goods['g_sn']);
    		$res_update_goods = D('GyFx')->update('goods',$ary_check_exist,$ary_goods);
    		if (! $res_update_goods) {
			return array (
					'status' => false,
    					'err_code' => 8102,
    					'err_msg' => '第三方商品更新本地商品时(主表)遇到错误！'
    			);
    		}else{
			foreach($ary_goods_info as $k => $sku){
				if(empty($sku)){
					unset($ary_goods[$k]);
				}
			}
    			$goods_where['g_id'] = $mixed_check_result['g_id'];
			unset($ary_goods_info['g_sn']);
    			$result_id = D('GyFx')->update('goods_info',$goods_where,$ary_goods_info);
			$mixd_goods_id = $mixed_check_result['g_id'];
    			if(!$result_id){
    				return array (
    						'status' => false,
    						'err_code' => 8102,
    						'err_msg' => '第三方商品更新本地商品时(明细)遇到错误！'
    				);
    			}
    		}
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
    	//分类处理，关联本地分类，如果使用九龙港分类，还要将九龙港分类保存到本地；
    	if (false == $bool_is_update_items) {
    		//如果设置了将商品关联到指定分类下，则增加一个关联
    		$seller_cids = trim($ary_top_items['seller_cids'], ';');
    		$ary_cats_result = $this->convertTopItemCats($mixd_goods_id, $convert_rules, $seller_cids);
    		if ($ary_cats_result['status'] == false) {
    			return array('status' => false, 'err_msg'=>'更新第三方平台分类关联关系失败！', 'err_code'=>8104);
    		}
    	}
    	//处理商品品牌
        $brand_name = $ary_top_items['brand_name'];
        $brand_code = $ary_top_items['brand_id'];
    	$ary_brand_reault = $this->convertTopBrandTolocal($mixd_goods_id,$brand_name,$brand_code);
	if(!empty($ary_top_items['item_imgs']['item_img']) || !empty($ary_top_items['pic_url'])){
		//处理商品图片
		$ary_picture_reault = $this->synItemImagesToLocal($ary_top_items['item_imgs']['item_img'], $mixd_goods_id, $ary_top_items['pic_url']);
		if ($ary_picture_reault['status'] == false) {
			return array('status' => false, 'err_msg'=>'更新商品图片失败！', 'err_code'=>8105);
		}
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
    				$ary_prop_add['thd_indentify'] = 3;
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
    				$ary_value_add['thd_indentify'] = 3;
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
    		$ary_sku = json_decode($ary_top_items['skus'],1);
		if(empty($ary_sku) && !empty($ary_top_items['skus'])){
			return array('status' => false, 'err_code' => 8108, 'err_msg'=>'skus数据有问题！');
		}
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
    			$ary_sku_info['thd_indentify'] = 3;
    			$ary_sku_info['thd_pdtid'] = !empty($ary_top_items['num_iid'])?($ary_top_items['num_iid']):'';
			foreach($ary_sku_info as $key => $sku){
				if(empty($sku)){
					unset($ary_sku_info[$key]);
				}
			}
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
    			foreach ($ary_sku as $key => $val) {
    				//直接新增到本地数据库
    				$ary_sku_info = array();
    				$ary_sku_info['g_id'] = $mixd_goods_id;
    				$ary_sku_info['g_sn'] = $ary_add_goods['g_sn'];
    				$ary_sku_info['taobao_sku_id'] = $val['taobao_sku_id'];
    				if (empty($val['sku_id']) || $val['sku_id'] == '') {
    					return array('status' => false, 'err_code' => 8111, 'err_msg' => '商家编码不存在!');
    				} else {
    					$ary_sku_info['pdt_sn'] = $val['sku_id'];
    				}
    				$ary_sku_info['pdt_sale_price'] = $val['price'];
    				$ary_sku_info['pdt_stock'] = $val['quantity'];
    				$ary_sku_info['pdt_create_time'] = date('Y-m-d H:i:s');
    				$ary_sku_info['pdt_update_time'] = date('Y-m-d H:i:s');
    				$ary_sku_info['pdt_market_price'] = $val['price'];
    				$ary_sku_info['pdt_weight'] = isset($ary_top_items['item_weight'])?($ary_top_items['item_weight']*1000):0;
    				$ary_sku_info['pdt_total_stock'] = $val['quantity'];
    				$ary_sku_info['thd_indentify'] = 3;
    				$ary_sku_info['thd_pdtid'] = $val['sku_id'];
				foreach($ary_sku_info as $key => $sku){
					if(empty($sku)){
						unset($ary_sku_info[$key]);
					}
				}
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
    					}else{
						$mix_return = $mixed_exists['pdt_id'];
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
    						//暂时都为1
    						$ary_prop_add['gs_is_sale_spec'] = 1;
    						$ary_prop_add['gs_create_time'] = date('Y-m-d H:i:s');
    						$ary_prop_add['gs_update_time'] = date('Y-m-d H:i:s');
    						$ary_prop_add['thd_indentify'] = 3;
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
    						$ary_value_add['thd_indentify'] = 3;
    						$gsd_id = D('Gyfx')->insert('goods_spec_detail',$ary_value_add);
    						if(!isset($gsd_id)){
    							return array('status' => false, 'err_msg'=>'新增商品属性值失败！', 'err_code'=>8105);
    						}
    					}else{
    						$gsd_id = $is_exist_spec_detail['gsd_id'];
    					}

    					//更新关联表
    					$is_exist_related_gs = D('Gyfx')->selectOne('related_goods_spec','',array('g_id'=>$mixd_goods_id,'pdt_id'=>$mix_return,'gs_id'=>$local_prov_id,'gsd_id'=>$gsd_id));
    					if(!isset($is_exist_related_gs)){
    						$ary_related_goods_spec = array(
    								'gs_id'=>$local_prov_id,
    								'gsd_id'=>$gsd_id,
    								'pdt_id'=>$mix_return,
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
    								'pdt_id'=>$mix_return,
    								'g_id'=>$mixd_goods_id
    						);
    						$return_related_gs = D('Gyfx')->update('related_goods_spec',$ary_related_goods_spec,array('gs_is_sale_spec'=>1));
    						if(!isset($return_related_gs)){
    							return array('status' => false, 'err_msg'=>'更新商品属性值关联表失败！', 'err_code'=>8105);
    						}
    					}
    				}
    			}
   			}
			$where_price = array('g_id'=>$mixd_goods_id);
			$g_price = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where($where_price)->min('pdt_sale_price');
			if(!empty($g_price)){
				D('Gyfx')->update('goods_info',$where_price,array('g_price'=>$g_price));
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
        //商品编码
        $ary_add_goods['g_sn'] = trim($ary_top_items['outer_id']);
        $ary_add_goods['g_art_no'] = trim($ary_top_items['art_no']);
    	$ary_add_goods['g_on_sale'] = 2;
    	if($convert_rules['on_sales']['value']=='1'){
    		$ary_add_goods['g_on_sale'] = 1;
    	}
    	$ary_add_goods['g_name'] = trim($ary_top_items['title']);
    	$ary_add_goods['g_off_sale_time'] = trim($ary_top_items['delist_time']);
    	$ary_add_goods['g_price'] = trim($ary_top_items['price']);
    	$ary_add_goods['g_market_price'] = trim($ary_top_items['price']);
    	//九龙港默认重量单位是Kg,分销默认g
    	$ary_add_goods['g_weight'] = trim($ary_top_items['item_weight ']*1000);
    	$ary_add_goods['g_stock'] = trim($ary_top_items['num']);
    	$ary_add_goods['thd_gid'] = trim($ary_top_items['num_iid']);
    	//$ary_add_goods['erp_guid'] = trim($ary_top_items['outer_id']);
    	$ary_add_goods['taobao_id'] = trim($ary_top_items['taobao_id']);
    	$ary_add_goods['g_create_time'] =  date('Y-m-d H:i:s');  //创建时间
    	$ary_add_goods['g_update_time'] =  date('Y-m-d H:i:s');	 //更新时间
    	$ary_add_goods['thd_indentify'] = 3;
    	$ary_add_goods['g_status'] = 1;
    	//判断是否设置新品上架
    	$ary_add_goods['g_new'] = 0;
    	if($convert_rules['set_new']['value'] == '1'){
    		$ary_add_goods['g_new'] = 1;
    	}
    	//判断是否设置热卖
    	$ary_add_goods['g_hot'] = 0;
    	/*if($convert_rules['set_hot']['value'] == '1'){
    		$ary_add_goods['g_hot'] = 1;
        }*/
		$ary_add_goods['g_pre_sale_status'] = 0;
    	return $ary_add_goods;
    }

    /**
     * 下载的商品类型默认为九龙港类型，如果不存在则添加
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
     * 处理九龙港商品图片信息
     * 将九龙港图片下载到本地服务器
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
     * @param $ary_top_seller_cats 九龙港商品分类（店铺分类）
     * @$str_shop_sid 九龙港店铺sid
     * @author wangguibin@guanyisoft.com
     * @date 2013-10-28
     */
    protected function convertTopItemCats($int_gods_id, $ary_config, $ary_top_seller_cats) {
    	//$ary_top_seller_cats  castc:类目一,1234:类目二
    	if ($ary_config['used_top_cat']['value'] == '1') {
    		//获取九龙港店铺ID
    		//用户配置了使用九龙港分类，这里还要处理多对多的情况
    		if ('' != trim($ary_top_seller_cats)) {

    			$ary_tmp_seller_cats = explode(';', trim($ary_top_seller_cats));
    			if (is_array($ary_tmp_seller_cats) && count($ary_tmp_seller_cats) > 0) {
				$array_cates = array();
				$countCats = count($ary_tmp_seller_cats);
    				foreach($ary_tmp_seller_cats as $x => $cate_info){
    					$cate_info = explode(':',$cate_info);
					if(($x+1) != $countCats){
						$ary_cate_code = explode('-',$cate_info[0]);
						$ary_cate_name = explode('-',$cate_info[1]);
						if(count($ary_cate_code) == count($ary_cate_name)){
							foreach($ary_cate_code as $i => $code){
								foreach($ary_cate_name as $j => $name){
									if($i == $j){
										$array_cates[] = $code.":".$name;
									}
								}
							}
						}
					}
					if(($x+1) == $countCats){
						$array_cates[] = $cate_info[0].":".$cate_info[1];
					}
				}
				$countList = count($array_cates);
				$parent_id = 0;
    				foreach($array_cates as $k => $cate_info){
    					$cate_info = explode(':',$cate_info);
    					//验证当前九龙港店铺分类在本地系统分类中是否已经存在,按商品名搜索
    					$ary_exist_con = array('gc_name' => $cate_info[1]);
    					try {
    						$mixed_esist = D('Gyfx')->selectOne('goods_category','',$ary_exist_con);
    					} catch (PDOException $e) {
    						//验证失败，返回错误信息
    						return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_001', 'message' => '验证类目错误');
    					}

    					if (is_array($mixed_esist) && !empty($mixed_esist) && count($mixed_esist) > 0) {
							// 该分类已经同步过来，验证状态是否还有效，如果已经被删除，则将其充值为有效,并更新九龙港分类ID
							$ary_edit = array (
									'gc_status' => 1,
									'gc_update_time' => date ( 'Y-m-d H:i:s' )
							);
							$ary_cont = array (
									'gc_id' => $mixed_esist ['gc_id']
							);
							try {
								D ( 'Gyfx' )->update ( 'goods_category', $ary_cont, $ary_edit );
								$parent_id = $mixed_esist['gc_id'];
							} catch ( PDOException $e ) {
								return array (
										'status' => false,
										'error_code' => 'addTaobaoSellerCatsToLocal_002',
										'message' => '更新分类失败'
								);
							}
						$rescate = $this->addItemCatsRelated($int_gods_id, $mixed_esist['gc_id']);
						if($rescate['status'] == false){
							return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_001', 'message' => '验证类目错误');
						}
    					}else{
    						//分类还未同步过来，直接新增一个分类
    						//如果该分类不是叶子节点，要找他的父分类，并新增
						$ary_cats_add['gc_parent_id'] = 0;
						if($parent_id > 0 ){
							$ary_cats_add['gc_parent_id'] = $parent_id;
						}
						if(($k+1) != $countList && isset($mixed_result)){
							$ary_cats_add['gc_parent_id'] = $mixed_result;
						}
    						$ary_cats_add['gc_level'] = $k;
						$ary_cats_add['gc_is_parent'] = 1;
						if($k == ($countList-2)){
							$ary_cats_add['gc_is_parent'] = 0;
						}
    						$ary_cats_add['gc_name'] = $cate_info[1];
    						$ary_cats_add['gc_order'] = 0;
    						$ary_cats_add['thd_catid'] = $cate_info[0];
    						$ary_cats_add['thd_indentify'] = 3;
    						$ary_cats_add['gc_is_display'] = 1;
						if(($k+1) == $countList){
							$ary_cats_add['gc_level'] = 0;
							$ary_cats_add['gc_type'] = 2;
							$ary_cats_add['gc_is_display'] = 0;
							$ary_cats_add['gc_parent_id'] = 0;
						}
    						$ary_cats_add['gc_create_time'] = date('Y-m-d H:i:s');
    						$ary_cats_add['gc_update_time'] = date('Y-m-d H:i:s');
    						//创建分类本身
    						try {
    							$mixed_result = D('Gyfx')->insert('goods_category',$ary_cats_add);
    						} catch (PDOException $e) {
    							return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_004', 'message' => '');
    						}
						$rescate = $this->addItemCatsRelated($int_gods_id, $mixed_result);
						if($rescate['status'] == false){
							return array('status' => false, 'error_code' => 'addTaobaoSellerCatsToLocal_001', 'message' => '验证类目错误');
						}
    					}
    				}
				return array('status' => true);
    			}
    		}
    		//异常情况：九龙港商品没有店铺分类，直接关联到我们系统的内部分类--分类名称=九龙港未分类商品
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
    		//将分类增加到九龙港未分类下面
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
    	//添加九龙港默认分类
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
     * 下载九龙港商品图片到本地系统中
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
    	//将九龙港的商品图片读到本地并入库
    	foreach ($ary_images as $val) {
    		if (trim($val['url']) == trim($str_zhutu_url)) {
    			//主图，跳过
    			continue;
    		}
    		//其他图片读取到本地保存，并且入库做相应的关联
    		//先将九龙港图片下载到本地保存
    		$str_local_image_url = $this->downloadTopImageToLocal(trim($val['url']),0,'./Public/Uploads/' . CI_SN.'/goods/jlg');
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
     * 将九龙港品牌转换到本地
     *
     * @params $array_post_setdata 用户提交的设置数组
     * @return array('status'=>true|false,message=>'提示信息')
     * @author Hcaijin
     * @version 1.0
     */
    public function convertTopBrandTolocal($int_goods_id,$brand_name,$brand_code) {
        if (!empty($brand_name) && !empty($brand_code)) {
                //验证品牌在本地是否在本地存在
                $ary_exist_cond = array('gb_name' =>$brand_name);
                $ary_exist_cond = array('gb_sn' =>$brand_code);
                try {
                    $mixed_result = D('Gyfx')->selectOne('goods_brand','gb_id',$ary_exist_cond);
                } catch (PDOException $e) {
                    return array('status' => false, 'data' => array(), 'message' => '验证品牌是否存在时出现错误' . __FILE__ . __LINE__);
                }
                //拼接品牌信息
                $ary_item_brand_info = array();
                $ary_item_brand_info['gb_name'] = $brand_name;
                $ary_item_brand_info['gb_sn'] = $brand_code;
                $ary_item_brand_info['gb_detail'] = '第三方品牌:'.$brand_name;
                $ary_item_brand_info['gb_update_time'] = date('Y-m-d H:i:s');

                //如果存在，则判断品牌是否被删除，如果删除，则还原之，如果不存在，则新增一个品牌
                if (is_array($mixed_result) && count($mixed_result) > 0) {
                    //九龙港品牌在本系统中已经存在，则更新之
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
                        return array('status' => false, 'data' => array(), 'message' => '把九龙港品牌保存到本系统失败！');
                    }
                    $int_brand_id = $mixed_add_result;
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
    * 使用促销券额操作API,生成订单信息
    * @author Hcaijin
    * @date 2014-08-06
     */
    public function usePromotions($array_params){
        $ary_orders = '';

        $ary_member = D('Members')->where(array('m_card_no'=>$array_params['name']))->find();
        if (!empty($ary_member ['m_id'])) {
            $goods_list = json_decode($array_params['item_list'],1);
            if(empty($goods_list) && !isset($goods_list)){
                return array('status'=>false,'msg'=>'商品信息不能为空','data'=>array());
            }else{
                $ary_cart = $goods_list;
            }
        } else {
            return array('status'=>false,'msg'=>'会员卡号不存在或未同步会员信息！','data'=>array());
        }
        //$User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
        if (!empty($array_params) && is_array($array_params)) {
            $ary_goods = array();
            if(is_array($ary_cart) && !empty($ary_cart)){
                foreach ($ary_cart as $key => $ary) {
                    $goods_products = D("GoodsProducts")->where(array(
                                'pdt_sn' => $ary ['outer_id']
                            ))->find();
                    if(!empty($goods_products) && is_array($goods_products)){
                        $ary_goods[$key]['g_id'] = $goods_products['g_id'];
                        $ary_goods[$key]['pdt_sn'] = $goods_products['pdt_sn'];
                        $ary_goods[$key]['g_name'] = $goods_products['g_name'];
                        $ary_goods[$key]['gb_id'] = $goods_products['gc_id'];
                        $ary_goods[$key]['gt_id'] = $goods_products['gt_id'];
                        $ary_goods[$key]['g_sn'] = $goods_products['g_sn'];
                        $ary_goods[$key]['pdt_id'] = $goods_products['pdt_id'];
                        $ary_goods[$key]['pdt_market_price'] = $goods_products['pdt_market_price'];
                        $ary_goods[$key]['pdt_cost_price'] = $goods_products['pdt_cost_price'];
                        $ary_goods[$key]['pdt_sale_price'] = $goods_products['pdt_sale_price'];
                        $ary_goods[$key]['pdt_price'] = $ary['price']/$ary['nums'];
                        //商品价
                        $ary_goods[$key]['price'] = $ary['price'];
                        //商品数量
                        $ary_goods[$key]['pdt_nums'] = $ary['nums'];
                        $ary_goods[$key]['spcode'] = $ary['SPCODE'];
                        $ary_goods[$key]['hth'] = $ary['HTH'];
                        $ary_goods[$key]['ghdw'] = $ary['GHDW'];
                        $ary_goods[$key]['oi_orders'] = $ary['orders'];
                    }else{
                        $ary_goods[$key]['g_id'] = $ary['gcno']; //o2o商品 线下传分类代码，品牌代码
                        $ary_goods[$key]['pdt_price'] = $ary['price']/$ary['nums'];
                        $ary_goods[$key]['pdt_sn'] = $ary['outer_id'];
                        //商品价
                        $ary_goods[$key]['price'] = $ary['price'];
                        //商品数量
                        $ary_goods[$key]['pdt_nums'] = $ary['nums'];
                        $ary_goods[$key]['spcode'] = $ary['SPCODE'];
                        $ary_goods[$key]['hth'] = $ary['HTH'];
                        $ary_goods[$key]['ghdw'] = $ary['GHDW'];
                        $ary_goods[$key]['oi_orders'] = $ary['orders']; //o2o商品大类码标识
                        //return array('status'=>false,'msg'=>'商品编码为'.$ary['outer_id'].'的商品信息不存在！','data'=>array());
                    }
                }
            }else{
                return array('status'=>false,'msg'=>'获取商品信息失败！','data'=>array());
            }
		// 商品总价
		$promotion_total_price = $array_params['shopping_price'];

            //判断接口类型，查询还是直接使用
            $bool_orders = (!isset($array_params['type']) || empty($array_params['type']))?"0":$array_params['type'];
            if ($bool_orders == 0) {
                $where = array();
                $date = date('Y-m-d H:i:s');
                $where['c_user_id']=$ary_member['m_id'];
                $where['c_is_use']=0;
                $where['c_start_time']=array('elt',$date);
                $where['c_end_time']=array('egt',$date);
                //获取会员所有优惠券总数量
                //$total =  D('Coupon')->where($where)->count();
                //获取会员所有优惠券列表
                $ary_coupon = D('Coupon')->where($where)->order('c_create_time asc')->select();
                $i = 0;
                foreach($ary_coupon as $coupon){
                    if($coupon['c_condition_money'] == '0.00' || $coupon['c_condition_money'] < $promotion_total_price){
                        //判断商品是否允许使用优惠券
                        $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                        ->where(array('c_id'=>$coupon['c_id']))
                        ->field('group_concat(gg_id) as group_id')->group('gg_id')->select();
                        if(!empty($group_data)){
                            $gids = array();
                            foreach ($group_data as $gd){
                                $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                                ->where(array('gg_id'=>array('in',$gd['group_id'])))->field('g_id')->group('g_id')->select();
                                foreach($item_data as $item){
                                    foreach($ary_goods as $cart){
                                        if($cart['g_id'] == $item['g_id']){
                                            $gids[] = $item['g_id'];
                                            $array_return[$i] = $coupon;
                                        }
                                    }
                                    $array_return[$i]['gids'] = $gids;
                                }
                            }
                        }else{
                            $array_return[$i]['gids'] = 'All';
                            $array_return[$i] = $coupon;
                        }
                    }
                    $i++;
                }
                $k = 0;
                foreach($array_return as $val){
                    if(count($val['gids']) > 0 || $val['gids'] == 'All'){
                        if($val['c_type'] == '1'){
                        if($val['gids'] == 'All'){
                            $array_coupon[$k]['coupon_price'] =sprintf('%.2f',(1-$val ['c_money'])*$promotion_total_price);
                            $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                            $array_coupon[$k]['ci_type'] = $val['c_type'];
                        }else{
                            $coupon_all_price = 0;
                            foreach ($ary_goods as $gval) {
                            if(in_array($gval['g_id'],$val['gids'])){
                                $coupon_all_price += $gval['price'];
                            }
                            }
                            $array_coupon[$k]['coupon_price'] =sprintf('%.2f',(1-$val ['c_money'])*$coupon_all_price);
                            $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                            $array_coupon[$k]['ci_type'] = $val['c_type'];
                        }
                        }else{
                        $array_coupon[$k]['coupon_price'] = sprintf('%.2f',$val ['c_money']);
                        $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                        $array_coupon[$k]['ci_type'] = $val['c_type'];
                        }
                        $k++;
                    }
                }
                //dump($promotion_total_price);exit();
                // 订单总价 商品会员折扣价-优惠券金额-红包金额-储值卡-九龙币- 结余款
                //$all_price = $promotion_total_price - $ary_res['coupon_price'] - $ary_res['bonus_price'] - $ary_res['cards_price'] - $ary_res['jlb_price'] - $array_params['balance'];

                $pointCfg = D('PointConfig');
                // 计算订单可以使用的九龙金豆
                $is_use_point = $pointCfg->getIsUsePoint($promotion_total_price,$ary_member['m_id']);
                if($is_use_point>0){
                    $ary_data = $pointCfg->getConfigs();
                    $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
                    //九龙金豆抵扣的总金额
                    $point_price = (0.01/$consumed_points)*$is_use_point;
                    $point = $is_use_point;
                }else{
                    $point_price = 0;
                    $point = 0;
                }
                
                $ary_res = array(
                    'point_price'=>sprintf('%.2f',$point_price),
                    'balance_price'=>sprintf('%.2f',$ary_member['m_balance']),
                    'bonus_price'=>sprintf('%.2f',$ary_member['m_bonus']),
                    'jlb_price'=>sprintf('%.2f',$ary_member['m_jlb']),
                    'coupon_list'=>$array_coupon
                );

                return array('status'=>true,'msg'=>'查询返回成功！','data'=>$ary_res);
            } elseif($bool_orders == 1){
                // 优惠券金额
                $str_csn = $array_params ['coupon'];
                if (isset($str_csn) && !empty($str_csn)) {
                    $ary_coupon = $this->CheckCoupon($str_csn, $ary_goods,$ary_member['m_id']);
                    $date = date('Y-m-d H:i:s');
                    if($ary_coupon['status'] == 'error'){
                        $ary_result ['msg'] = $ary_coupon['msg'];
                        $ary_result ['status'] = false;
                        return $ary_result;
                    } else {
                        foreach ($ary_coupon['msg'] as $coupon){
                            if ($coupon ['c_condition_money'] > 0 && $promotion_total_price < $coupon ['c_condition_money']) {
                                return array('status'=>false,'msg'=>"编号{$coupon['ci_sn']}优惠券不满足使用条件！");
                            } elseif ($coupon ['c_is_use'] == 1 || $coupon ['c_used_id'] != 0) {
                                return array('status'=>false,'msg'=>"编号{$coupon['ci_sn']}已被使用！");
                            } elseif ($coupon ['c_start_time'] > $date) {
                                return array('status'=>false,'msg'=>"编号{$coupon['ci_sn']}不能使用！");
                            } elseif ($date > $coupon ['c_end_time']) {
                                return array('status'=>false,'msg'=>"编号{$coupon['ci_sn']}活动已经结束！");
                            } else {
                                if($coupon['c_type'] == '1'){
                                    //计算参与优惠券使用的商品
                                    if($coupon['gids'] == 'All'){
                                        $ary_result ['coupon_total_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$promotion_total_price);
                                        foreach ($ary_goods as $j => $val) {
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                            $ary_goods[$j]['coupon_price'] = sprintf('%.2f'.($val['price']/$promotion_total_price)*$ary_result['coupon_total_price']);
                                            }else{
                                            $ary_goods[$j]['coupon_price'] = 0;
                                            }
                                        }
                                    }else{
                                        //计算可以使用优惠券总金额
                                        $coupon_all_price = 0;
					$countCoupon = 0;
                                        foreach ($ary_goods as $val) {
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $coupon_all_price += $val['price'];
						$countCoupon ++;
                                            }
                                        }
                                        $ary_result ['coupon_total_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$coupon_all_price);
                                        if($countCoupon > 0){
                                            foreach ($ary_goods as $j => $val) {
                                                if(in_array($val['g_id'],$coupon['gids'])){
                                                $ary_goods[$j]['coupon_price'] = sprintf('%.2f',($val['price']/$coupon_all_price)*$ary_result['coupon_total_price']);
                                                }else{
                                                    $ary_goods[$j]['coupon_price'] = 0;
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    $ary_result ['coupon_total_price'] += $coupon ['c_money'];
                                    foreach ($ary_goods as $j => $val) {
                                        if(in_array($val['g_id'],$coupon['gids'])){
                                            $ary_goods[$j]['coupon_price'] = sprintf('%.2f',($val['price']/$promotion_total_price)*$ary_result['coupon_total_price']);
                                        }else{
                                            $ary_goods[$j]['coupon_price'] = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }else{
                    $ary_result['coupon_total_price'] = 0;
                }
                //红包支付
                $bonus = $array_params['bonus'];
                if ($bonus > 0) {
                    $arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
                    if($bonus > $arr_bonus['m_bonus']){
                        return array('status'=>false,'msg'=>'红包金额不能大于用户可用金额！');
                    }elseif($promotion_total_price < $bonus) {
                        return array('status'=>false,'msg'=>'红包金额超过了商品总金额！');
                    }else{
                        $ary_result ['bonus_price'] = sprintf('%.2f',$bonus);
                    }
                }else{
                    $ary_result ['bonus_price'] = 0;
                }
                //九龙币支付
                $jlb = $array_params['jiulongbi'];
                if ($jlb > 0) {
                    $arr_jlb = M('Members')->field("m_jlb")->where(array('m_id'=>$ary_member['m_id']))->find();
                    if($jlb > $arr_jlb['m_jlb']){
                        return array('status'=>false,'msg'=>'九龙币金额不能大于用户可用金额！');
                    }elseif($promotion_total_price < $jlb) {
                        return array('status'=>false,'msg'=>'九龙币金额超过了商品总金额！');
                    }else{
                        $ary_result ['jlb_price'] = sprintf('%.2f',$jlb);
                    }
                }else{
                    $ary_result ['jlb_price'] = 0;
                }
                //九龙金豆支付
                $point = $array_params['point'];
                if ($point > 0) {
                    $pointCfg = D('PointConfig');
                    $ary_data = $pointCfg->getConfigs();
                    $consumed_points = intval($ary_data['consumed_points']);
                    // 计算订单可以使用的九龙金豆
                    $is_use_point = $pointCfg->getIsUsePoint($promotion_total_price,$ary_member['m_id']);
		    $use_point_price = (0.01/$consumed_points)*$is_use_point;
                    if($point <= $use_point_price){
                        //九龙金豆抵扣的总金额
                        $ary_result['point_price'] = sprintf('%.2f',$point);
                        $ary_result ['freeze_point'] = $point/(0.01/$consumed_points);
                    }else{
			return array('status'=>false,'msg'=>'此单最多可以使用'.$is_use_point.'九龙金豆');
                    }
                }else{
                    $ary_result ['freeze_point'] = 0;
                    $ary_result ['point_price'] = 0;
                }
                $promotion_total = $array_params['balance']+$ary_result['bonus_price']+$ary_result['jlb_price']+$ary_result['point_price']+$ary_result['coupon_total_price'];
                if($promotion_total == $ary_result['coupon_total_price']){
                    $promotion_total = $ary_result['coupon_total_price'];
                }else{
                    if($promotion_total > $promotion_total_price){
                        return array('status'=>false,'msg'=>'会员权益使用超过了订单应付总金额！');
                    }
                }

                if($promotion_total > 0){
                    D()->startTrans();
                    // 订单id
                    $ary_orders ['o_id'] = date('YmdHis') . rand(1000, 9999);
                    $ary_result['orders_price'] = sprintf("%.2f",$promotion_total_price - $promotion_total);
                    $o_pay = $array_params['balance'];
                    $ary_result['balance_price'] = sprintf('%.2f',$o_pay);
                    $bonus_price = $ary_result['bonus_price'];
                    $jlb_price = $ary_result['jlb_price'];
                    $point_price = $ary_result['point_price'];
                    $freeze_point = $ary_result['freeze_point'];
                    // 会员id
                    $ary_orders ['m_id'] = $ary_member ['m_id'];
                    $ary_orders ['o_goods_all_price'] = $promotion_total_price;
                    $ary_orders ['o_goods_all_saleprice'] = $promotion_total_price;
                    $ary_orders ['o_all_price'] = $promotion_total_price-$promotion_total+$array_params['balance'];
                    $ary_orders ['o_pay_status'] = 3;
                    $ary_orders ['o_status'] = 2;
                    $ary_orders ['o_source_type'] = 'offline';
                    $ary_orders ['o_seller_comments'] = '线下使用会员权益订单';
                    //第三方发货状态，在这里就是线下发货状态
                    //$ary_orders ['o_trd_delivery_status'] = 1;
                    if($o_pay){
                        $ary_orders ['o_pay'] = $o_pay;
                    }
                    if($ary_result['bonus_price']){
                        $ary_orders ['o_bonus_money'] = $ary_result['bonus_price'];
                    }
                    if($ary_result['jlb_price']){
                        $ary_orders ['o_jlb_money'] = $ary_result['jlb_price'];
                    }
                    if($ary_result['freeze_point']){
                        $ary_orders ['o_freeze_point'] = $ary_result['freeze_point'];
                    }
                    if($ary_result['point_price']){
                        $ary_orders ['o_point_money'] = $ary_result['point_price'];
                    }
                    if($ary_result['coupon_total_price']){
                        $ary_orders ['o_coupon_menoy'] = $ary_result['coupon_total_price'];
                    }
                        $ary_orders ['o_jlbh'] = $array_params['JLBH'];
                        $ary_orders ['o_sktno'] = $array_params['SKTNO'];

                    $bool_orders = D('Orders')->doInsert($ary_orders);
                    if (!$bool_orders) {
                        D()->rollback();
                        $ary_result ['msg'] = '生成订单失败！';
                        $ary_result ['status'] = false;
                        return $ary_result;
                    } else {
                        //获取明细分配的金额
                        $ary_goods = $this->getOrdersGoods($ary_goods,$ary_orders,$ary_coupon);
                        foreach($ary_goods as $k => $v){
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $v ['g_id'];
                                // 货品sn
                                $pdt_numsary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                                //数量
                                $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                                // 货品id
                                $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                                // 类型id
                                $ary_orders_items ['gt_id'] = $v ['gt_id'];
                                // 商品sn
                                $ary_orders_items ['g_sn'] = $v ['g_sn'];
                                // 商品规格sn
                                $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                                // 商品名字
                                $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                                // 成本价
                                $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                                // 货品销售原价
                                $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] = $v ['pdt_price'];
                                $ary_orders_items ['oi_spcode'] = $v['spcode'];
                                $ary_orders_items ['oi_hth'] = $v['hth'];
                                $ary_orders_items ['oi_ghdw'] = $v['ghdw'];
                                $ary_orders_items ['oi_orders'] = $v['oi_orders']; //o2o大类码商品标识
								if(!empty($v['oi_coupon_menoy'])){
									$ary_orders_items['oi_coupon_menoy'] = $v['oi_coupon_menoy'];
                                    $pdt_item_money += $ary_orders_items['oi_coupon_menoy'];
								}
								if(!empty($v['oi_bonus_money'])){
									$ary_orders_items['oi_bonus_money'] = $v['oi_bonus_money'];
                                    $ary_goods[$k]['pdt_item_money'] += $ary_orders_items['oi_bonus_money'];
								}
								if(!empty($v['oi_jlb_money'])){
									$ary_orders_items['oi_jlb_money'] = $v['oi_jlb_money'];
                                    $ary_goods[$k]['pdt_item_money'] += $ary_orders_items['oi_jlb_money'];
								}
								if(!empty($v['oi_point_money'])){
									$ary_orders_items['oi_point_money'] = $v['oi_point_money'];
                                    $ary_goods[$k]['pdt_item_money'] += $ary_orders_items['oi_point_money'];
								}				
								if(!empty($v['oi_balance_money'])){
									$ary_orders_items['oi_balance_money'] = $v['oi_balance_money'];
                                    //结余款不记商品的优惠金额
                                    //$ary_goods[$k]['pdt_item_money'] += $ary_orders_items['oi_balance_money'];
								}				
                            $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                            if (!$bool_orders_items) {
                                D()->rollback();
                                $ary_result ['msg'] = '生成订单明细失败！';
                                $ary_result ['status'] = false;
                                return $ary_result;
                            }
                        }
			    //更新红包使用
			    if(isset($bonus_price) && $bonus_price>0){
				$arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
				if($bonus_price > $arr_bonus['m_bonus']){
				    return array('status'=>false,'msg'=>'红包金额不能大于用户可用金额！');
				}elseif($promotion_total_price < $bonus_price) {
				    return array('status'=>false,'msg'=>'红包金额超过了商品总金额！');
				}
				$arr_bonus = array(
				    'bt_id' => '4',
				    'm_id'  => $ary_member['m_id'],
				    'bn_create_time'  => date("Y-m-d H:i:s"),
				    'bn_type' => '1',
				    'bn_money' => $bonus_price,
				    'bn_desc' => '线下支付使用'.$bonus_price."元",
				    'o_id' => $ary_orders['o_id'],
				    'bn_finance_verify' => '1',
				    'bn_service_verify' => '1',
				    'bn_verify_status' => '1',
				    'single_type' => '2'
				);
				$res_bonus = D('BonusInfo')->addBonus($arr_bonus);
				if (!$res_bonus) {
				    D('')->rollback();
				    return array('status'=>false,'msg'=>'新增红包调整单失败！','data'=>array());
				}
			    }
			    if(isset($jlb_price) && $jlb_price>0){
				//更新九龙币使用
				$arr_jlb = array(
				    'jt_id' => '2',
				    'm_id'  => $ary_member['m_id'],
				    'ji_create_time'  => date("Y-m-d H:i:s"),
				    'ji_type' => '1',
				    'ji_money' => $jlb_price,
				    'ji_desc' => '线下支付使用'.$jlb_price."元",
				    'o_id' => $ary_orders['o_id'],
				    'ji_finance_verify' => '1',
				    'ji_service_verify' => '1',
				    'ji_verify_status' => '1',
				    'single_type' => '2'
				);
				$res_jlb = D('JlbInfo')->addJlb($arr_jlb);
				if (!$res_jlb) {
				    D('')->rollback();
				    return array('status'=>false,'msg'=>'新增九龙币调整单失败！','data'=>array());
				}
			    }
			    // 更新优惠券使用
			    if(!empty($array_params['coupon'])){
				$couponSnList = explode(',',$array_params['coupon']);
				foreach ($couponSnList as $coupon){
				    $ary_data = array(
					'c_is_use' => 1,
					'c_used_id' => $ary_member['m_id'],
					'c_order_id' => $ary_orders ['o_id']
				    );
				    $res_coupon = D('Coupon')->doCouponUpdate($coupon, $ary_data);
				    if (!$res_coupon) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'使用优惠券失败！','data'=>array());
				    }
				}
			    }
			    // 有消耗九龙金豆
			    if ($freeze_point > 0 ) {
				$ary_freeze_result = D('Members')->where(array('m_id'=>$ary_member['m_id']))->setInc('freeze_point',$freeze_point);
				if(!$ary_freeze_result){
				    D('')->rollback();
				    return array('status'=>false,'msg'=>'九龙金豆使用失败！','data'=>array());
				}else{
					$ary_log = array(
						    'type'=>1,
						    'consume_point'=> $freeze_point,
						    'reward_point'=> 0,
						    );
					    $ary_info =D('PointLog')->addPointLog($ary_log,$ary_member['m_id']);
					if($ary_info['status']!=1){
					    return array('status' => false, 'message' => $ary_info['msg']);
					}
				}
			    }
			    if($o_pay > 0){
				//结余款使用
				$ary_balance_info = array(
				    'bt_id' => '1',
				    'bi_sn' => time(),
				    'm_id' => $ary_member ['m_id'],
				    'bi_money' => $o_pay,
				    'bi_type' => '1',
				    'bi_payment_time' => date("Y-m-d H:i:s"),
				    'o_id' => $ary_orders['o_id'],
				    'bi_desc' => '线下长益POS支付',
				    'single_type' => '2',
				    'bi_verify_status' => '1',
				    'bi_service_verify' => '1',
				    'bi_finance_verify' => '1',
				    'bi_create_time' => date("Y-m-d H:i:s")
				);
				$arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
				if (!$arr_res) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'生成支付明细失败!','data'=>array());
				} else {
					M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_member['m_id']))->setDec('m_balance',$o_pay);
				    $arr_data = array();
				    $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
				    $arr_data ['bi_sn'] = time() . $str_sn;
				    $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'bi_id' => $arr_res
					    ))->data($arr_data)->save();
				    if (!$arr_result) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'更新支付明细失败!','data'=>array());
				    }
				    // 结余款调整单日志
				    $add_balance_log ['u_id'] = 0;
				    $add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
				    $add_balance_log ['bvl_desc'] = '审核成功';
				    $add_balance_log ['bvl_type'] = '2';
				    $add_balance_log ['bvl_status'] = '2';
				    $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
				    if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'生成结余款调整单日志失败!','data'=>array());
				    }
				}
			    }
                    }
                    // 订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $ary_orders ['o_id'],
                        'ol_behavior' => '创建',
                        'ol_uname' => $ary_member['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    if (!$res_orders_log) {
                        $ary_result ['msg'] = '生成订单日志失败！';
                        $ary_result ['status'] = false;
                        return $ary_result;
                    }
                    D()->commit();
                    $ary_result['trans_no'] = $ary_orders['o_id'];
                    $ary_result['orders'] = $ary_goods;
                    return array('status'=>true,'msg'=>'使用成功！','data'=>$ary_result);
                }else{
                    return array('status'=>false,'msg'=>'需要使用会员权益！','data'=>array());
                }
            }
        }
        return array('status'=>false,'msg'=>'接口调用失败！','data'=>array());
    }

	/**
	 * 新增、更新售价信息
	 * request params
	 * @author Hcaijin
	 * @date 2014-08-21
	 */
	public function addUpdatePrice($array_params) {
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		M ( '', C ( '' ), 'DB_CUSTOM' )->startTrans ();
        $outer_id = trim($array_params["outer_id"]);
        $sku_outer_id = trim($array_params["sku_outer_id"]);
        $pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
        if(empty($pdt_id)){
            $ary_data = array('msg'=>'此商品或商品规格不存在,不能更新售价信息');
        }else{
            if(empty($array_params['price'])){
                $ary_data = array('msg'=>'商品售价为0不允许更新');
            }else{
                $gp_result = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')
                ->data(array('pdt_update_time'=>date('Y-m-d H:i:s'),'pdt_sale_price'=>$array_params['price']))
                ->where(array('pdt_id'=>$pdt_id))
                ->save();
                if(!empty($gp_result)) {
			$g_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('g_id');
			$where_price = array('g_id'=>$g_id);
			$g_price = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where($where_price)->min('pdt_sale_price');
			if(!empty($g_price)){
				D('Gyfx')->update('goods_info',$where_price,array('g_price'=>$g_price));
			}
                    $int_flag = true;
                    M('', '', 'DB_CUSTOM')->commit();
                }else{
                    M('', '', 'DB_CUSTOM')->rollback();
                    $ary_data = array('msg'=>'更新商品售价异常');
                }
				
            }
        }
		if($int_flag) {
			$ary_update_price_response = array();
			$ary_update_price_response['update_price_list ']['update_price'] = array('update_time'=>date('Y-m-d H:i:s'),'outer_id'=>$outer_id,'sku_outer_id'=>$sku_outer_id);
			return array('msg'=>'','data'=>$ary_update_price_response);
		}
		else return $ary_data;
	}

    /**
    * 使用促销券额确认完成操作API
    * @author Hcaijin
    * @date 2014-08-06
     */
    public function doitPromotions($array_params){
        $oid = $array_params['trans_no'];
        $arr_orders = D('Orders')->where(array('o_id'=>$oid))->select();
        if (!empty($arr_orders) && is_array($arr_orders)) {
            D()->startTrans();
            $data = array('o_pay_status'=>1);
            $res_orders_status = D('Orders')->where(array('o_id'=>$oid))->save($data);
            if($res_orders_status){
                $res = D('Orders')->where(array('o_id' => $oid))->data(array('o_audit' => '1', 'o_update_time' => date('Y-m-d H:i:s')))->save();
                if ($res){
                    //更新日志表
                    $ary_orders_log = array(
                        'o_id' => $oid,
                        'ol_behavior' => '订单审核',
                        'ol_text' => serialize($arr_orders),
                        'ol_uname' => 'admin',
                        'ol_behavior' => '订单审核',
                        //'ol_desc'=>file_get_contents($_SERVER['HTTP_REFERER'])
                    );
                    $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                    if (!$res_orders_log) {
                        D()->rollback();
                        return array('status'=>false,'msg'=>'写订单日志失败！','data'=>array());
                    }
                }else{
                    D()->rollback();
                    return array('status'=>false,'msg'=>'订单审核失败！','data'=>array());
                }
            }else{
                D()->rollback();
                return array('status'=>false,'msg'=>'订单审核失败！','data'=>array());
            }
            D()->commit();
            return array('status'=>true,'msg'=>'使用成功！');
        }
        return array('status'=>false,'msg'=>'无此订单数据！');
    }

    /**
    * 使用会员权益支付作废订单操作API
    * @author Hcaijin
    * @date 2014-09-01
     */
    public function cancelPromotions($array_params){
        $oid = $array_params['trans_no'];
        $ary_orders = D('Orders')->where(array('o_id'=>$oid))->find();
        if (!empty($ary_orders) && is_array($ary_orders)) {
            D()->startTrans();
            $ary_member['m_id'] = $ary_orders['m_id'];
            $o_pay = $ary_orders['o_pay'];
            $bonus_price = $ary_orders['o_bonus_money'];
            $jlb_price = $ary_orders['o_jlb_money'];
            $point_price = $ary_orders['o_point_money'];
            $freeze_point = $ary_orders['o_freeze_point'];
            if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                 $freePoint = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->getField('freeze_point');
                 if ($ary_member && $freeze_point > 0) {
                    //订单退款返还冻结九龙金豆日志
                    $ary_log = array(
                                'type'=>8,
                                'consume_point'=> 0,
                                'reward_point'=> $ary_orders['o_freeze_point']
                                );
                    $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                    if($ary_info['status'] == 1){
                         $ary_member_data['freeze_point'] = $freePoint - $ary_orders['o_freeze_point'];
                         $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_member_data);
                         if(!$res_member){
                             D()->rollback();
                             return array('status'=>false,'msg'=>'撤消线下支付返回冻结九龙金豆失败！');
                         }
                    }else{
                         D()->rollback();
                         return array('status'=>false,'msg'=>'撤消线下支付返回冻结九龙金豆写日志失败！');
                    }
                }else{
                     D()->rollback();
                     return array('status'=>false,'msg'=>'撤消线下支付操作没有找到要返回的九龙金豆冻结金额！');
                }
            }
            //还原,支出冻结红包金额
            $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_orders['o_id'],'bn_type'=>array('neq','0')))->find();
            if(!empty($ary_bonus) && is_array($ary_bonus)){
                $arr_bonus = array(
                    'bt_id' => '4',
                    'm_id'  => $ary_bonus['m_id'],
                    'bn_create_time'  => date("Y-m-d H:i:s"),
                    'bn_type' => '0',
                    'bn_money' => $ary_bonus['bn_money'],
                    'bn_desc' => '撤消线下支付返还红包：'.$ary_bonus['bn_money'].'元',
                    'o_id' => $ary_bonus['o_id'],
                    'bn_finance_verify' => '1',
                    'bn_service_verify' => '1',
                    'bn_verify_status' => '1',
                    'single_type' => '2'
                );
                $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                if(!$res_bonus){
                     D()->rollback();
                     return array('status'=>false,'msg'=>'撤消线下支付返还红包失败！');
                }
            }
            //还原,支出冻结九龙币金额
            $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_orders['o_id'],'ji_type'=>array('neq','0')))->find();
            if(!empty($ary_jlb) && is_array($ary_jlb)){
                $arr_jlb = array(
                    'jt_id' => '2',
                    'm_id'  => $ary_jlb['m_id'],
                    'ji_create_time'  => date("Y-m-d H:i:s"),
                    'ji_type' => '0',
                    'ji_money' => $ary_jlb['ji_money'],
                    'ji_desc' => '撤消线下支付成功返还九龙币：'.$ary_jlb['ji_money'],
                    'o_id' => $ary_jlb['o_id'],
                    'ji_finance_verify' => '1',
                    'ji_service_verify' => '1',
                    'ji_verify_status' => '1',
                    'single_type' => '2'
                );
                $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                if(!$res_jlb){
                     D()->rollback();
                     return array('status'=>false,'msg'=>'撤消线下支付返九龙币失败！');
                }
            }
            //消耗的优惠券还原
            $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'c_order_id' => $ary_orders['o_id'],'c_end_time'=>array('egt',date('Y-m-d H:i:s'))
                    ))->find();
            if (!empty($ary_coupon) && is_array($ary_coupon)) {
                $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'c_order_id' => $ary_orders['o_id'],'c_end_time'=>array('egt',date('Y-m-d H:i:s'))
                        ))->save(array(
                    'c_used_id' => 0,
                    'c_order_id' => 0,
                    'c_is_use' => 0
                        ));
                if (!$res_coupon) {
                     D()->rollback();
                     return array('status'=>false,'msg'=>'撤消线下支付返还优惠券失败！');
                }
            }
            //还原,支出冻结结余款金额
            $ary_balance = M('BalanceInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_orders['o_id'],'bi_type'=>array('neq','0')))->find();
		if (!empty($ary_balance) && is_array($ary_balance)) {
			$balancePay = $ary_balance['bi_money'];
		    if($balancePay > 0 && $balancePay == $o_pay){
			//结余款使用
			$ary_balance_info = array(
			    'bt_id' => '1',
			    'bi_sn' => time(),
			    'm_id' => $ary_member ['m_id'],
			    'bi_money' => $o_pay,
			    'bi_type' => '0',
			    'bi_payment_time' => date("Y-m-d H:i:s"),
			    'o_id' => $ary_orders['o_id'],
			    'bi_desc' => '撤消线下使用结余款支付',
			    'single_type' => '2',
			    'bi_verify_status' => '1',
			    'bi_service_verify' => '1',
			    'bi_finance_verify' => '1',
			    'bi_create_time' => date("Y-m-d H:i:s")
			);
			$arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
				if (!$arr_res) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'生成支付明细失败!');
				} else {
					M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_member['m_id']))->setInc('m_balance',$o_pay);
				    $arr_data = array();
				    $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
				    $arr_data ['bi_sn'] = time() . $str_sn;
				    $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
						'bi_id' => $arr_res
					    ))->data($arr_data)->save();
				    if (!$arr_result) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'更新支付明细失败!','data'=>array());
				    }
				    // 结余款调整单日志
				    $add_balance_log ['u_id'] = 0;
				    $add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
				    $add_balance_log ['bvl_desc'] = '审核成功';
				    $add_balance_log ['bvl_type'] = '2';
				    $add_balance_log ['bvl_status'] = '2';
				    $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
				    if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
					D('')->rollback();
					return array('status'=>false,'msg'=>'生成结余款调整单日志失败!');
				    }
				}
			    }
		    }
                $return_orders = D('Orders')->where(array(
                            'o_id' => $oid
                        ))->save(array(
                    'o_status' => 2
                        ));	
                // 订单日志记录
                if (!$return_orders) {
                    D()->rollback();
		     return array('status'=>false,'msg'=>'更新订单状态失败！');
                }
		$ary_orders_log = array(
		    'o_id' => $ary_orders['o_id'],
		    'ol_behavior' => '撤消线下支付成功，订单退款成功',
		    'ol_text' => serialize($ary_orders),
		    'ol_uname' => '管理员'
		    );
	    $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
	    if (!$res_orders_log) {
		 D()->rollback();
		 return array('status'=>false,'msg'=>'撤消线下支付写订单日志文件失败！');
	    }else{
		D('')->commit();
		return array('status'=>true,'msg'=>'撤消线下支付成功！');
	    }
        }
        return array('status'=>false,'msg'=>'撤消线下支付失败，订单号不存在！','data'=>array());
    }

    /**
    * 退货返还会员权益API(fx.promotions.refund)
    * @author Hcaijin
    * @date 2014-09-01
     */
	public function refundPromotions($array_params) {
		$ary_data = array();
		$ary_member = D('Members')->where(array('m_card_no'=>$array_params['name']))->find();
		if (!empty($ary_member ['m_id'])) {
		    $goods_list = json_decode($array_params['item_list'],1);
		    if(empty($goods_list) && !isset($goods_list)){
                return array('status'=>false,'msg'=>'商品信息不能为空');
		    }else{
                foreach ($goods_list as $ary) {
                    $ary_data['checkSon'][$ary['orders']] = $ary['outer_id'];
                    $ary_data['inputNum'][$ary['orders']] = $ary['nums'];
                }
		    }
		} else {
		    return array('status'=>false,'msg'=>'会员信息不存在！');
		}
		//数据操作模型初始化
		$obj_refunds = D('OrdersRefunds');
		$date = date('Y-m-d H:i:s');
		$ary_data['o_id'] = $array_params['trans_no'];
		$ary_data['application_money'] = $array_params['trans_money'];
		$ary_data['or_refund_type'] = 2;
		$ary_data['or_buyer_memo'] = '线下长益退货';
		$ary_data['ary_reason'] = '退货理由';
		//dump($ary_data);exit;
		//验证是否传递要退款/退货的订单号
        $count = D('Orders')->where(array('o_id'=>$ary_data['o_id']))->count();
		if (!isset($count) || empty($count) || $count == 0) {
		    return array('status'=>false,'msg'=>'无此订单信息！');
		}
		//判断是否提交过（只能申请一次）
		$ary_refunds = $obj_refunds->where(array('o_id'=>$ary_data['o_id']))->select();
		if($ary_data['o_id'] == $ary_refunds['o_id']){
		    return array('status'=>false,'msg'=>'您已申请过，请耐心等待处理！');
		}
		//erp退款退货标志  2退款:3  退货:
		$refund_type = 3;
		//售后单据基本信息
		$ary_refunds = array(
			'o_id' => $ary_data['o_id'],
			'm_id' => $ary_member['m_id'],
			'or_money' => sprintf('%.2f',$ary_data['application_money']),
			'or_refund_type' => $ary_data['or_refund_type'],
			'or_create_time' => $date,
			'm_name' => $ary_member['m_name'],
			'or_buyer_memo'=>$ary_data['or_buyer_memo']
		);
		//已经退货商品
		$ary_refunds_items = array();
		//要退货或者退款商品 - 获取此订单的订单明细数据
		$ary_orders_items = D('OrdersItems')->field('oi_id,o_id,pdt_sn,oi_price,oi_nums,g_sn,oi_g_name,erp_id,oi_orders')->where(array('o_id'=>$ary_refunds['o_id']))->select();
        //dump($ary_orders_items);exit;
		$ary_temp_items = array();
		foreach($ary_orders_items as $val){
			$ary_temp_items[$val['oi_orders']] = $val;
		}

		if($ary_refunds['or_refund_type'] == 2 ){
			$ary_where = array(
				'fx_orders_refunds.o_id'=>$ary_data['o_id'],
				'fx_orders_refunds.m_id' =>$ary_member['m_id'],
				'fx_orders_refunds.or_processing_status'=>array('neq',2),
				'fx_orders_refunds.or_refund_type'=>2
			);
			$ary_returns_orders = D('OrdersRefunds')
                ->field('fx_orders_items.pdt_sn,fx_orders_items.oi_nums,fx_orders_refunds_items.ori_num,fx_orders_items.g_sn,fx_orders_items.oi_orders')
                ->join('left join fx_orders_refunds_items on fx_orders_refunds.or_id = fx_orders_refunds_items.or_id')
                ->join(" fx_orders_items ON fx_orders_refunds_items.oi_id=fx_orders_items.oi_id")
                ->where($ary_where)
                ->select();
			if($ary_returns_orders){
				//已经加入的退货单商品详情
				$ary_returns_temp = array();
				foreach($ary_returns_orders as $val) {
					if(!isset($ary_returns_temp[$val['oi_orders']])){
						$ary_returns_temp[$val['oi_orders']]['num'] = $val['ori_num'];//已退货的货号商品总数
						$ary_returns_temp[$val['oi_orders']]['nums'] = $val['oi_nums'];//此订单货号总数
						$ary_returns_temp[$val['oi_orders']]['pdt_sn'] = $val['pdt_sn'];
					}
					else{
						$ary_returns_temp[$val['oi_orders']]['num'] += $val['ori_num'];
					}
				}
                //商品可能部分退掉进行商品数量判断
                foreach ($ary_data['checkSon'] as $k => $v) {
                    if(!empty($ary_data['inputNum'][$k]) && isset($ary_returns_temp[$k])) {
                        if(!ctype_digit($ary_data['inputNum'][$k])) return array('status'=>false,'msg'=>"退货数量填写需正整数");
                        if($ary_data['inputNum'][$k]>$ary_returns_temp[$k]['nums']) return array('status'=>false,'msg'=>"商品编号是{$ary_returns_temp[$k]['pdt_sn']},排序为{$k}的商品退货数量不能大于购买量");
                        if(($ary_data['inputNum'][$k] + $ary_returns_temp[$k]['num'] )> $ary_returns_temp[$k]['nums']){
                            $str_th_sum = intval($ary_returns_temp[$k]['nums'] - $ary_returns_temp[$k]['num']);
                            if($str_th_sum>0){
                                return array('status'=>false,'msg'=>"商品编号是{$ary_returns_temp[$k]['pdt_sn']},排序为{$k}的商品退货数量只能退{$str_th_sum}件");
                            }else{
                                return array('status'=>false,'msg'=>"商品编号是{$ary_returns_temp[$k]['pdt_sn']},排序为{$k}的商品已经退过货，不能重复退货");
                            }
                        }
                    }
                }
            }else{
				foreach ($ary_data['checkSon'] as $k => $v) {
					if(!empty($ary_data['inputNum'][$k]) && isset($ary_temp_items[$k])) {
						if(!ctype_digit($ary_data['inputNum'][$k])){
						    return array('status'=>false,'msg'=>'退货数量填写需正整数！');
						}
						if($ary_data['inputNum'][$k]>$ary_temp_items[$k]['oi_nums']){
						    return array('status'=>false,'msg'=>"商品编号是{$ary_temp_items[$k]['pdt_sn']},排序为{$k}的商品退货数量不能大于购买量");
						}
					}
				}
			}
		}
		$ary_refunds['or_reason'] = $ary_data['ary_reason'];
		$ary_refunds['or_return_sn'] = strtotime("now");
		//售后数据存入数据库  需要启用事务机制
		M('', '', 'DB_CUSTOM')->startTrans();
		$ary_refunds['or_update_time'] = date('Y-m-d H:i:s');

		//插入退款主表
		$int_or_id = D('OrdersRefunds')->add($ary_refunds);
		if (false === $int_or_id) {
			M('', '', 'DB_CUSTOM')->rollback();
		        return array('status'=>false,'msg'=>"售后申请提交失败");
		}
		//自动生成售后单据编号，单据编号的规则为20130628+8位单据ID（不足8位左侧以0补全）
		$int_tmp_or_id = $int_or_id;
		$or_return_sn = date("Ymd") . sprintf('%07s',$int_tmp_or_id);
		$array_modify_data = array("or_return_sn"=>$or_return_sn);
		$mixed_result = D('OrdersRefunds')->where(array("or_id"=>$int_or_id,'or_update_time'=>date('Y-m-d H:i:s')))->save($array_modify_data);
		if(false === $mixed_result){
			M('', '', 'DB_CUSTOM')->rollback();
		        return array('status'=>false,'msg'=>'售后申请提交失败。CODE:CREATE-REFUND-SN-ERROR.');
		}
		//插入明细表
		$ary_refunds_items = array();
		if($ary_data['or_refund_type']==2){
			//商品可能部分退掉
            //更改订单详情表商品退货状态
            $refund_coupon = 0;
            $refund_bonus = 0;
            $refund_jlb = 0;
            $refund_point = 0;
            $refund_balance = 0;
            $refund_ary = array();
            //dump($ary_data);
			foreach ($ary_data['checkSon'] as $k => $v) {
				if(!empty($ary_data['inputNum'][$k]) && isset($ary_temp_items[$k])) {
					$ary_refunds_items[] = array(
							'o_id' => $ary_temp_items[$k]['o_id'],
							'or_id' => $int_or_id,
							'oi_id' => $ary_temp_items[$k]['oi_id'],
							'ori_num' => $ary_data['inputNum'][$k],
							'erp_id' =>$ary_temp_items[$k]['erp_id']
					);
                    $ary_oi = M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'pdt_sn'=>$v,'oi_orders'=>$k))->find();
                    if($ary_data['inputNum'][$k] == $ary_oi['oi_nums']){
                        $refund_coupon += $ary_oi['oi_coupon_menoy'];
                        $refund_bonus += $ary_oi['oi_bonus_money'];
                        $refund_jlb += $ary_oi['oi_jlb_money'];
                        $refund_point += $ary_oi['oi_point_money'];
                        $refund_balance += $ary_oi['oi_balance_money'];
                        //结余款不记商品的优惠金额
                        $ary_oi['pdt_item_money'] = sprintf("%.2f",$ary_oi['oi_coupon_menoy']+$ary_oi['oi_bonus_money']+$ary_oi['oi_jlb_money']+$ary_oi['oi_point_money']);
                        //$ary_oi['pdt_item_money'] = $ary_oi['oi_coupon_menoy']+$ary_oi['oi_bonus_money']+$ary_oi['oi_jlb_money']+$ary_oi['oi_point_money']+$ary_oi['oi_balance_money'];
                        $refund_ary[] = $ary_oi;
                    }else{
                        $oi_coupon = $ary_oi['oi_coupon_menoy']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $oi_bonus = $ary_oi['oi_bonus_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $oi_jlb = $ary_oi['oi_jlb_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $oi_point = $ary_oi['oi_point_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $oi_balance = $ary_oi['oi_balance_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        //结余款不记商品的优惠金额
                        $ary_oi['pdt_item_money'] = sprintf("%.2f",$oi_coupon+$oi_bonus+$oi_jlb+$oi_point);
                        //$ary_oi['pdt_item_money'] = $oi_coupon+$oi_bonus+$oi_jlb+$oi_point+$oi_balance;
                        $refund_ary[] = $ary_oi;
                        $refund_coupon += $ary_oi['oi_coupon_menoy']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $refund_bonus += $ary_oi['oi_bonus_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $refund_jlb += $ary_oi['oi_jlb_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $refund_point += $ary_oi['oi_point_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                        $refund_balance += $ary_oi['oi_balance_money']*($ary_data['inputNum'][$k]/$ary_oi['oi_nums']);
                    }
				}
			}
			//批量插明细表
			$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);
			if (false === $int_return_refunds_itmes) {
				M('', '', 'DB_CUSTOM')->rollback();
				return array('status'=>false,'msg'=>'批量插入明细失败');
			}
            foreach ($ary_data['checkSon'] as $orders => $pdt_sn){
                $res_oi = M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'pdt_sn'=>$pdt_sn,'oi_orders'=>$orders))->data(array('oi_refund_status'=>3))->save();
                if($res_oi === false ){
                    M('', '', 'DB_CUSTOM')->rollback();
                    return array('status'=>false,'msg'=>'更新退货状态失败');
                }
            }
		}
        //结余款不记商品的优惠金额
        $refund_money = $refund_bonus+$refund_jlb+$refund_point+$refund_coupon;
        //$refund_money = $refund_bonus+$refund_balance+$refund_jlb+$refund_point+$refund_coupon;
		//用户提示语定义
		$str_type = '售后';
		switch($refund_type){
			case 2:
				$str_type = '退款';
				break;
			case 3:
				$str_type = '退货';
				break;
		}
		//单据id:$int_or_id;订单号：$ary_data['o_id'] 
		//更新日志表
		$ary_orders_log = array(
				'o_id'=>$ary_data['o_id'],
				'ol_behavior' => '会员新增售后申请',
				'ol_uname'=>$ary_member['m_id']
		);
		$res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
		if(!$res_orders_log){
			M('', '', 'DB_CUSTOM')->rollback();
		        return array('status'=>false,'msg'=>'会员新增售后申请日志失败');
		}
		//事务提交
		M('', '', 'DB_CUSTOM')->commit();
		return array('status'=>true,'msg'=>'','refund_no'=>$array_modify_data['or_return_sn'],'refund_money'=>sprintf('%.2f',$refund_money),'refund_bonus'=>sprintf('%.2f',$refund_bonus),'refund_balance'=>sprintf('%.2f',$refund_balance),'refund_jlb'=>sprintf('%.2f',$refund_jlb),'refund_point'=>sprintf('%.2f',$refund_point),'refund_coupon'=>sprintf('%.2f',$refund_coupon),'refund_data'=>$refund_ary);
	}

    /**
    * 使用促销券额确认完成操作API
    * @author Hcaijin
    * @date 2014-08-06
     */
    public function refundDoitPromotions($array_params){
        $refundNo = $array_params['refund_no'];
        $ary_res = D('OrdersRefunds')->where(array('or_return_sn'=>$refundNo))->find();
        if (!empty($ary_res) && is_array($ary_res)) {
            if($ary_res['or_processing_status'] != 0){
                return array('status'=>false,'msg'=>'此退货单已经完成了或已作废！');
            }
            D()->startTrans();
            $ary_order_data = array();
            $ary_order_data['or_seller_memo'] = '线下确认退货';

            $ary_order_data['or_finance_u_id'] = 0;
            $ary_order_data['or_finance_u_name'] = 'admin';
            $ary_order_data['or_finance_time'] = date('Y-m-d H:i:s');
            $ary_order_data['or_processing_status'] = 1;
            $ary_order_data['or_service_u_id'] = 0;
            $ary_order_data['or_service_u_name'] = 'admin';
            $ary_order_data['or_service_time'] = date('Y-m-d H:i:s');
            $ary_result = D('OrdersRefunds')->where(array('or_id' => $ary_res['or_id']))->data($ary_order_data)->save();
            if (FALSE != $ary_result) {
                //退货单
                if ($ary_res['or_refund_type'] == 2) {
                    $order_item['oi_refund_status'] = 5; //退货成功
                    $order_item['oi_update_time'] = date('Y-m-d H:i:s');
                    $ary_oi_id = M('orders_refunds_items')->field(array('oi_id,ori_num'))->where(array('or_id' => $ary_res['or_id']))->select();
                    $orderItems = M('orders_items')->field('oi_id,o_id,g_id,g_sn,pdt_sn,pdt_id,oi_bonus_money,oi_cards_money,oi_jlb_money,oi_point_money,oi_balance_money')->where(array('o_id'=>$ary_res['o_id']))->select();
                    foreach ($ary_oi_id as $key) {
                        if (false === M('orders_items')->where(array('oi_id' => $key['oi_id']))->save($order_item)) {
                            D()->rollback();
                            return array('status'=>false,'msg'=>'更新订单状态失败');
                        }
                    }
                    $refund_coupon = 0;
                    $refund_point = 0;
                    $refund_bonus = 0;
                    $refund_jlb = 0;
                    $refund_balance = 0;
                    if(count($ary_oi_id) == count($orderItems)){
                        // 冻结九龙金豆释放掉
                        $ary_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_pay,o_freeze_point,o_point_money,o_coupon_menoy')->where(array('o_id' => $ary_res['o_id']))->find();
                        if (isset($ary_orders['o_freeze_point']) && $ary_orders['o_freeze_point'] > 0 && $ary_orders['m_id'] > 0) {
                             $arrMembers = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $ary_orders['m_id']))->find();
                             if ($arrMembers && $arrMembers['freeze_point'] > 0) {
                                //订单退货返还冻结九龙金豆日志
                                $ary_log = array(
                                            'type'=>8,
                                            'consume_point'=> 0,
                                            'reward_point'=> $ary_orders['o_freeze_point']
                                            );
                                $ary_info =D('PointLog')->addPointLog($ary_log,$ary_orders['m_id']);
                                if($ary_info['status'] == 1){
                                     $ary_res_data['freeze_point'] = $arrMembers['freeze_point'] - $ary_orders['o_freeze_point'];
                                     $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_orders['m_id']))->save($ary_res_data);
                                     if(!$res_member){
                                         D()->rollback();
                                         return array('status'=>false,'msg'=>'退货返回冻结九龙金豆失败');
                                     }
                                }else{
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'退货返回冻结九龙金豆写日志失败');
                                }
                            }else{
                                 D()->rollback();
                                 return array('status'=>false,'msg'=>'退货返回冻结九龙金豆没有找到要返回的用户冻结金额');
                            }
                        }
                        $bonus_status = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
                        if($bonus_status['BONUS_AUTO_OPEN'] == 1){
                            //还原,支出冻结红包金额
                            $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'bn_type'=>array('neq','0')))->find();
                            if(!empty($ary_bonus) && is_array($ary_bonus)){
                                $arr_bonus = array(
                                    'bt_id' => '4',
                                    'm_id'  => $ary_bonus['m_id'],
                                    'bn_create_time'  => date("Y-m-d H:i:s"),
                                    'bn_type' => '0',
                                    'bn_money' => $ary_bonus['bn_money'],
                                    'bn_desc' => '退货申请成功返还红包金额：'.$ary_bonus['bn_money'].'元',
                                    'o_id' => $ary_bonus['o_id'],
                                    'bn_finance_verify' => '1',
                                    'bn_service_verify' => '1',
                                    'bn_verify_status' => '1',
                                    'single_type' => '2'
                                );
                                $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                if($res_bonus === true){
                                    $ary_orders_log = array(
                                            'o_id' => $ary_res['o_id'],
                                            'ol_behavior' => '退货返还红包成功,',
                                            'ol_text' => serialize($ary_bonus)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                }else{
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'退货返还红包失败');
                                }
                            }
                        }
                        $jlb_status = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
                        if($jlb_status['JIULONGBI_AUTO_OPEN'] == 1){
                                //还原,支出冻结九龙币金额
                            $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_res['o_id'],'ji_type'=>array('neq','0')))->find();
                            if(!empty($ary_jlb) && is_array($ary_jlb)){
                                $arr_jlb = array(
                                    'jt_id' => '2',
                                    'm_id'  => $ary_jlb['m_id'],
                                    'ji_create_time'  => date("Y-m-d H:i:s"),
                                    'ji_type' => '0',
                                    'ji_money' => $ary_jlb['ji_money'],
                                    'ji_desc' => '退货申请成功返还九龙币金额：'.$ary_jlb['ji_money'].'元',
                                    'o_id' => $ary_jlb['o_id'],
                                    'ji_finance_verify' => '1',
                                    'ji_service_verify' => '1',
                                    'ji_verify_status' => '1',
                                    'single_type' => '2'
                                );
                                $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                if($res_jlb === true){
                                    $ary_orders_log = array(
                                            'o_id' => $ary_res['o_id'],
                                            'ol_behavior' => '退货返还九龙币成功,',
                                            'ol_text' => serialize($ary_jlb)
                                    );
                                    D('OrdersLog')->addOrderLog($ary_orders_log);
                                }else{
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'退货返还九龙币失败');
                                }
                            }
                        }
                        //完全退货也不退优惠券,注释掉
                        /* $coupon_info = M('orders')->where(array('o_id'=>$ary_res['o_id']))->field('o_coupon,coupon_sn,coupon_value,coupon_start_date,coupon_end_date')->find();
                        if($coupon_info['o_coupon'] == '1' && $coupon_info['coupon_end_date'] >= date('Y-m-d H:i:s')){
                            //作废
                            $res = M('coupon')->where(array('c_sn'=>$coupon_info['coupon_sn'],'c_is_use'=>0))->data(array('c_is_use'=>'4'))->save();
                            if($res){
                                $ary_orders_log = array(
                                        'o_id' => $ary_res['o_id'],
                                        'ol_behavior' => '退优惠券成功,',
                                        'ol_text' => serialize($coupon_info)
                                );
                                D('OrdersLog')->addOrderLog($ary_orders_log);
                            }else{
                                $ary_orders_log = array(
                                        'o_id' => $ary_res['o_id'],
                                        'ol_behavior' => '退优惠券失败,优惠券已不存在或已被使用,优惠券为:'.$coupon_info['coupon_sn'],
                                        'ol_text' => serialize($coupon_info)
                                );
                                 D('OrdersLog')->addOrderLog($ary_orders_log);
                            }
                        }
                        //消耗的优惠券还原
                        $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'c_order_id' => $ary_res['o_id'],'c_end_time'=>array('egt',date('Y-m-d H:i:s'))
                                ))->find();
                        if (!empty($ary_coupon) && is_array($ary_coupon)) {
                            $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                        'c_order_id' => $ary_res['o_id'],'c_end_time'=>array('egt',date('Y-m-d H:i:s'))
                                    ))->save(array(
                                'c_used_id' => 0,
                                'c_order_id' => 0,
                                'c_is_use' => 0
                                    ));
                            if (!$res_coupon) {
                                $orders->rollback();
                                $this->error('优惠券还原失败');
                                exit();
                            }
                        }									 */
                        $refund_coupon = $ary_bonus['o_coupon_menoy'];
                        $refund_point = $ary_orders['o_point_money'];
                        $refund_bonus = $ary_bonus['bn_money'];
                        $refund_jlb = $ary_jlb['ji_money'];
                        $refund_balance = $ary_orders['o_pay'];
                    }else{
                        //部分退货优惠券不退，其他金额等比例退回
                        foreach($orderItems as $item){
                            foreach($ary_oi_id as $ary_oi_info){
                                if($item['oi_id'] == $ary_oi_info['oi_id']){
                                    $refund_coupon += $item['oi_coupon_menoy'];
                                    $refund_point = $refund_point+$item['oi_point_money'];
                                    $refund_bonus = $refund_bonus+$item['oi_bonus_money'];
                                    $refund_jlb = $refund_jlb+$item['oi_jlb_money'];											
                                    $refund_balance = $refund_balance+$item['oi_balance_money'];
                                }
                            }
                        }
                        if ($refund_point > 0) {
                            $pointCfg = D('PointConfig')->getConfigs();
                            $consumed_points = sprintf("%0.2f",$pointCfg['consumed_points']);
                            //九龙金豆抵扣的金额转化为九龙金豆数
                            $freeze_point = $refund_point/(0.01/$consumed_points);
                            $freePoint = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_res['m_id']))->getField('freeze_point');
                            $diff_point = $freePoint - $freeze_point;
                             if ($freeze_point > 0 &&  $diff_point >= 0) {
                                //订单退款返还冻结九龙金豆日志
                                $ary_log = array(
                                            'type'=>8,
                                            'consume_point'=> 0,
                                            'reward_point'=> $freeze_point
                                            );
                                $ary_info =D('PointLog')->addPointLog($ary_log,$ary_res['m_id']);
                                if($ary_info['status'] == 1){
                                     $ary_res_data['freeze_point'] = $diff_point;
                                     $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $ary_res['m_id']))->save($ary_res_data);
                                     if(!$res_member){
                                         D()->rollback();
                                         return array('status'=>false,'msg'=>'线下退货返回冻结九龙金豆失败！');
                                     }
                                }else{
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'线下退货返回冻结九龙金豆写日志失败！');
                                }
                            }else{
                                 D()->rollback();
                                 return array('status'=>false,'msg'=>'线下退货要返回的九龙金豆不能小于0！');
                            }
                        }
                        if($refund_bonus > 0){
                            //还原,支出冻结红包金额
                            $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'bn_type'=>array('neq','0')))->find();
                            if(!empty($ary_bonus) && is_array($ary_bonus) && $refund_bonus <= $ary_bonus['bn_money']){
                                $arr_bonus = array(
                                    'bt_id' => '4',
                                    'm_id'  => $ary_bonus['m_id'],
                                    'bn_create_time'  => date("Y-m-d H:i:s"),
                                    'bn_type' => '0',
                                    'bn_money' => $refund_bonus,
                                    'bn_desc' => '线下退货返还红包：'.$refund_bonus.'元',
                                    'o_id' => $ary_bonus['o_id'],
                                    'bn_finance_verify' => '1',
                                    'bn_service_verify' => '1',
                                    'bn_verify_status' => '1',
                                    'single_type' => '2'
                                );
                                $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                                if(!$res_bonus){
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'线下退货返还红包失败！');
                                }
                            }
                        }
                        if($refund_jlb > 0){
                            //还原,支出冻结九龙币金额
                            $ary_jlb = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'ji_type'=>array('neq','0')))->find();
                            if(!empty($ary_jlb) && is_array($ary_jlb) && $refund_jlb <= $ary_jlb['ji_money']){
                                $arr_jlb = array(
                                    'jt_id' => '2',
                                    'm_id'  => $ary_jlb['m_id'],
                                    'ji_create_time'  => date("Y-m-d H:i:s"),
                                    'ji_type' => '0',
                                    'ji_money' => $refund_jlb,
                                    'ji_desc' => '线下退货成功返还九龙币：'.$refund_jlb,
                                    'o_id' => $ary_jlb['o_id'],
                                    'ji_finance_verify' => '1',
                                    'ji_service_verify' => '1',
                                    'ji_verify_status' => '1',
                                    'single_type' => '2'
                                );
                                $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                                if(!$res_jlb){
                                     D()->rollback();
                                     return array('status'=>false,'msg'=>'线下退货返九龙币失败！');
                                }
                            }
                        }
                        if($refund_balance > 0){
                            //还原,支出冻结结余款金额
                            $ary_balance = M('BalanceInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'bi_type'=>array('neq','0')))->find();
                            if (!empty($ary_balance) && is_array($ary_balance) && $refund_balance <= $ary_balance['bi_money']) {
                                //结余款使用
                                $ary_balance_info = array(
                                    'bt_id' => '1',
                                    'bi_sn' => time(),
                                    'm_id' => $ary_res ['m_id'],
                                    'bi_money' => $refund_balance,
                                    'bi_type' => '0',
                                    'bi_payment_time' => date("Y-m-d H:i:s"),
                                    'o_id' => $ary_data['o_id'],
                                    'bi_desc' => '线下退货返还结余款使用金额',
                                    'single_type' => '2',
                                    'bi_verify_status' => '1',
                                    'bi_service_verify' => '1',
                                    'bi_finance_verify' => '1',
                                    'bi_create_time' => date("Y-m-d H:i:s")
                                );
                                $arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
                                if (!$arr_res) {
                                    D('')->rollback();
                                    return array('status'=>false,'msg'=>'生成支付明细失败!');
                                } else {
                                    M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_res['m_id']))->setInc('m_balance',$refund_balance);
                                    $arr_data = array();
                                    $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
                                    $arr_data ['bi_sn'] = time() . $str_sn;
                                    $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                        'bi_id' => $arr_res
                                        ))->data($arr_data)->save();
                                    if (!$arr_result) {
                                        D('')->rollback();
                                        return array('status'=>false,'msg'=>'更新支付明细失败!','data'=>array());
                                    }
                                    // 结余款调整单日志
                                    $add_balance_log ['u_id'] = 0;
                                    $add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
                                    $add_balance_log ['bvl_desc'] = '审核成功';
                                    $add_balance_log ['bvl_type'] = '2';
                                    $add_balance_log ['bvl_status'] = '2';
                                    $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
                                    if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                                        D('')->rollback();
                                        return array('status'=>false,'msg'=>'生成结余款调整单日志失败!');
                                    }
                                }
                            }
                        }
                    }
                    $refund_money = $refund_coupon+$refund_point+$refund_bonus+$refund_jlb+$refund_balance;
                    $ary_orders_log = array(
                            'o_id' => $ary_res['o_id'],
                            'ol_behavior' => '订单退货成功',
                            'ol_text' => serialize($ary_res),
                            'ol_uname' => 'admin'
                    );
                    $res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
                    if (!$res_orders_log) {
                        D()->rollback();
                        return array('status'=>false,'msg'=>'生成退货单日志失败!');
                    }
                    D()->commit();
                    return array('status'=>true,'msg'=>'','refund_money'=>sprintf('%.2f',$refund_money));
                }
            }
        }
        return array('status'=>false,'msg'=>'无此退货单据！');
    }

    /**
    * 查询会员可用优惠券API
    * @author Hcaijin
    * @date 2014-09-05
    */
    Public function appGetCoupon($array_params){
        $ary_orders = '';

        $ary_member = D('Members')->where(array('m_name'=>$array_params['name']))->find();
        if (!empty($ary_member ['m_id'])) {
            $goods_list = json_decode($array_params['item_list'],1);
            if(empty($goods_list) && !isset($goods_list)){
                return array('status'=>false,'msg'=>'商品信息不能为空','data'=>array());
            }else{
                $ary_cart = $goods_list;
            }
        } else {
            return array('status'=>false,'msg'=>'会员信息不存在！','data'=>array());
        }
        //$User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
        if (!empty($array_params) && is_array($array_params)) {
            $ary_goods = array();
            if(is_array($ary_cart) && !empty($ary_cart)){
                foreach ($ary_cart as $key => $ary) {
                    $g_id = D("GoodsProducts")->where(array(
                                'pdt_sn' => $ary ['outer_id']
                            ))->getField('g_id');
                    if(!$g_id){
                        return array('status'=>false,'msg'=>'商品编码为'.$ary['outer_id'].'的商品信息不存在！','data'=>array());
                    }else{
                        $ary_goods[$key]['g_id'] = $g_id;
                        $ary_goods[$key]['pdt_sn'] = $ary['outer_id'];
                        $ary_goods[$key]['price'] = $ary['price'];
                    }
                }
            }else{
                return array('status'=>false,'msg'=>'获取商品信息失败！','data'=>array());
            }
            // 商品总价
            $promotion_total_price = $array_params['shopping_price'];

            $where = array();
            $date = date('Y-m-d H:i:s');
            $where['c_user_id']=$ary_member['m_id'];
            $where['c_is_use']=0;
            $where['c_start_time']=array('elt',$date);
            $where['c_end_time']=array('egt',$date);
            //获取会员所有优惠券总数量
            //$total =  D('Coupon')->where($where)->count();
            //获取会员所有优惠券列表
            $ary_coupon = D('Coupon')->where($where)->order('c_create_time asc')->select();
            $i = 0;
            foreach($ary_coupon as $coupon){
                if($coupon['c_condition_money'] == '0.00' || $coupon['c_condition_money'] < $promotion_total_price){
                    //判断商品是否允许使用优惠券
                    $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                    ->where(array('c_id'=>$coupon['c_id']))
                    ->field('group_concat(gg_id) as group_id')->group('gg_id')->select();
                    if(!empty($group_data)){
                        $gids = array();
                        foreach ($group_data as $gd){
                            $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                            ->where(array('gg_id'=>array('in',$gd['group_id'])))->field('g_id')->group('g_id')->select();
                            foreach($item_data as $item){
                                foreach($ary_goods as $cart){
                                    if($cart['g_id'] == $item['g_id']){
                                        $gids[] = $item['g_id'];
                                        $array_return[$i] = $coupon;
                                    }
                                }
                                $array_return[$i]['gids'] = $gids;
                            }
                        }
                    }else{
                        $array_return[$i]['gids'] = 'All';
                        $array_return[$i] = $coupon;
                    }
                }
                $i++;
            }
            $k = 0;
            foreach($array_return as $val){
                if($val['c_type'] == '1'){
                    if($val['gids'] == 'All'){
                        $array_coupon[$k]['coupon_price'] =sprintf('%.2f',(1-$val ['c_money'])*$promotion_total_price);
                        $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                        $array_coupon[$k]['ci_type'] = $val['c_type'];
                    }else{
                        $coupon_all_price = 0;
                        foreach ($ary_goods as $gval) {
                            if(in_array($gval['g_id'],$val['gids'])){
                                $coupon_all_price += $gval['price'];
                            }
                        }
                        $array_coupon[$k]['coupon_price'] =sprintf('%.2f',(1-$val ['c_money'])*$coupon_all_price);
                        $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                        $array_coupon[$k]['ci_type'] = $val['c_type'];
                    }
                }else{
                    $array_coupon[$k]['coupon_price'] = $val ['c_money'];
                    $array_coupon[$k]['ci_sn'] = $val['c_sn'];
                    $array_coupon[$k]['ci_type'] = $val['c_type'];
                }
                $k++;
            }
            return array('status'=>true,'msg'=>'查询返回成功！','data'=>$array_coupon);
        }
    }

    /**
    * 使用促销券额操作API,生成订单信息
    * @author Hcaijin
    * @date 2014-09-06
    */
    public function appUsePromotions($array_params){
        $return_orders = false;
        $combo_all_price = 0;
        $free_all_price = 0;
        $ary_orders = $array_params;
        $ary_member = D('Members')->where(array('m_name'=>$array_params['name']))->find();
        if (!empty($ary_member ['m_id'])) {
            $goods_list = json_decode($array_params['cart_list'],1);
            if(empty($goods_list) && !isset($goods_list)){
                return array('status'=>false,'msg'=>'商品信息不能为空');
            }
            $ary_cart = array();
            foreach($goods_list as $goods){
                if ($goods['type'] == 5) {
                    $ary_orders['gp_id'] = $goods['g_id'];
                    $ary_orders['num'] = $goods['num'];
                    // 团购商品
                    $ary_cart [$goods['pdt_id']] = array(
                        'pdt_id' => $goods ['pdt_id'],
                        'num' => $goods ['num'],
                        'gp_id' => $goods ['g_id'],
                        'type' => 5
                    );
                } else if($goods['sp_id'] == 7){
                    $ary_orders['sp_id'] = $goods['g_id'];
                    $ary_orders['num'] = $goods['num'];
                    // 秒杀商品
                    $ary_cart [$goods['pdt_id']] = array(
                        'pdt_id' => $goods['pdt_id'],
                        'num' => $goods['num'],
                        'sp_id' => $goods['g_id'],
                        'type' => 7
                    );
                } else if($goods['p_id'] == 8){
                    $ary_orders['p_id'] = $goods['g_id'];
                    $ary_orders['num'] = $goods['num'];
                    //预售商品
                    $ary_cart [$goods['pdt_id']] = array(
                        'pdt_id'=>$goods['pdt_id'],
                        'num'=>$goods['num'],
                        'p_id'=>$goods['g_id'],
                        'type'=> 8
                    );
                }else {
                    $ary_cart[$goods['pdt_id']] = $goods;
                }
            }
        } else {
            return array('status'=>false,'msg'=>'会员信息不存在！');
        }
        
        foreach ($ary_cart as $ary) {
            // 自由推荐商品
            if ($ary ['type'] == 4 || $ary ['type'] == 6) {
                foreach ($ary ['pdt_id'] as $pdtId) {
                    $g_id = D("GoodsProducts")->where(array(
                                'pdt_id' => $pdtId
                            ))->getField('g_id');
                    $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $g_id);
                    if (empty($is_authorize)) {
			    return array('status'=>false,'msg'=>'部分商品已不允许购买,请先在购物车里删除这些商品！');
                    }
                    //是否开启区域限售
                    if(GLOBAL_STOCK == TRUE){
                        //临时收货地址
                        
	                    $return_stock = D('GoodsStock')->getProductsWarehouseStock($cr_id, $pdtId);
	                    if(intval($return_stock['pdt_total_stock'])<=2){
	                    	if(!$return_stock['pdt_sn']){
	                    		$pdt_sn = D("GoodsProducts")->where(array(
	                                'pdt_id' => $pdtId
	                            ))->getField('pdt_sn');
	                    	}else{
	                    		$pdt_sn = $return_stock['pdt_sn'];
	                    	}
				    return array('status'=>false,'msg'=>'货品编码为'.$pdt_sn.'的商品库存已不足,请先在购物车里删除这些商品！');
	                    }                    	
                    }
                }
            } else {
                $g_id = D("GoodsProducts")->where(array(
                            'pdt_id' => $ary ['pdt_id']
                        ))->getField('g_id');
                $is_authorize = D('AuthorizeLine')->isAuthorize($ary_member ['m_id'], $g_id);
                if (empty($is_authorize)) {
		    return array('status'=>false,'msg'=>'部分商品已不允许购买,请先在购物车里删除这些商品！');
                }
                //是否开启区域限售
                if(GLOBAL_STOCK == TRUE){
                    if($ary_orders['ra_id'] != 'other') {
					   $ary_receive_address = $this->cityRegion->getReceivingAddress($ary_member ['m_id']);
					   foreach ($ary_receive_address as $ara_k=>$ara_v){
						  if($ara_v['ra_id'] == $ary_orders['ra_id']){
							 $cr_id= $ara_v['cr_id'];
						  }
					   }
                    }
                    else{
                        if(isset($ary_orders['region']) && !empty($ary_orders['region'])){$cr_id = $ary_orders['region'];}
                        elseif(isset($ary_orders['city']) && !empty($ary_orders['city'])){ $cr_id = $ary_orders['city'];}
                        elseif(isset($ary_orders['province']) && !empty($ary_orders['province'])){ $cr_id = $ary_orders['province'];}
                    }
                    
                    $return_stock = D('GoodsStock')->getProductsWarehouseStock($cr_id, $ary ['pdt_id']);
					if(0 == $return_stock){
						$pdt_sn = D("GoodsProducts")->where(array(
                                'pdt_id' => $ary ['pdt_id']
                            ))->getField('pdt_sn');
					    return array('status'=>false,'msg'=>'货品编码为'.$pdt_sn.'的商品不在限购区域内,请先重新选择所在区域！');
					}
					if(!$return_stock['pdt_sn']){
						$pdt_sn = D("GoodsProducts")->where(array(
							'pdt_id' => $ary ['pdt_id']
						))->getField('pdt_sn');
					}else{
						$pdt_sn = $return_stock['pdt_sn'];
					}
                    if(intval($return_stock['pdt_total_stock'])<=2){
			    return array('status'=>false,'msg'=>'货品编码为'.$pdt_sn.'的商品库存已不足,请先在购物车里删除这件商品！');
                    }                    	
                }
            }
        }
        $User_Grade = D('MembersLevel')->getMembersLevels($ary_member['ml_id']); //会员等级信息
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $orders->startTrans();
        if (!empty($ary_orders) && is_array($ary_orders)) {
            if (!empty($ary_orders ['invoices_val']) && $ary_orders ['invoices_val'] == "1") {
                if (isset($ary_orders ['invoice_type']) && isset($ary_orders ['invoice_head'])) {
                    $ary_orders ['is_invoice'] = 1;
                    if ($ary_orders ['invoice_type'] == 2) {
                        // 如果为增值税发票，发票抬头默认为单位
                        $ary_orders ['invoice_head'] = 2;
                    } else {
                        if ($ary_orders ['invoice_head'] == 2) {
                            // 如果发票类型为普通发票，并且发票抬头为单位，将个人姓名删除
                            unset($ary_orders ['invoice_people']);
                        }
                        if ($ary_orders ['invoice_head'] == 1) {
                            // 如果发票类型为普通发票，并且发票抬头为个人，将单位删除
                            unset($ary_orders ['invoice_name']);
                        }
                    }
                    if (empty($ary_orders ['invoice_name'])) {
                        $ary_orders ['invoice_name'] = '个人';
                    } else {
                        $ary_orders ['invoice_name'] = $ary_orders ['invoice_name'];
                    }
                    if (isset($ary_orders ['invoice_content'])) {
                        $ary_orders ['invoice_content'] = $ary_orders ['invoice_content'];
                    }
                } else {
                    if (isset($ary_orders ['is_default']) && !empty($ary_orders ['is_default']) && !isset($ary_orders ['in_id'])) {
                        $res_invoice = D('InvoiceCollect')->getid($ary_orders ['is_default']);

                        if (!empty($res_invoice)) {
                            $ary_orders ['is_invoice'] = 1;
                            $ary_orders ['invoice_type'] = $res_invoice ['invoice_type'];
                            $ary_orders ['invoice_head'] = $res_invoice ['invoice_head'];
                            $ary_orders ['invoice_people'] = $res_invoice ['invoice_people'];
                            if (empty($res_invoice ['invoice_name'])) {
                                $ary_orders ['invoice_name'] = '个人';
                            } else {
                                $ary_orders ['invoice_name'] = $res_invoice ['invoice_name'];
                            }
                            $ary_orders ['invoice_content'] = $res_invoice ['invoice_content'];
                            // 如果是增值税发票，添加增值税发票信息

                            if ($ary_orders ['invoice_type'] == 2) {
                                // 纳税人识别号
                                $ary_orders ['invoice_identification_number'] = $res_invoice ['invoice_identification_number'];
                                // 注册地址
                                $ary_orders ['invoice_address'] = $res_invoice ['invoice_address'];
                                // 注册电话
                                $ary_orders ['invoice_phone'] = $res_invoice ['invoice_phone'];
                                // 开户银行
                                $ary_orders ['invoice_bank'] = $res_invoice ['invoice_bank'];
                                // 银行帐户
                                $ary_orders ['invoice_account'] = $res_invoice ['invoice_account'];
                            }
                        }
                    }
                    // 添加增值税发票
                    if (!empty($ary_orders ['in_id'])) {
                        $ary_res = M('InvoiceCollect', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    "id" => $ary_orders ['in_id']
                                ))->find();
                        $ary_orders ['invoice_type'] = $ary_res ['invoice_type'];
                        $ary_orders ['invoice_head'] = $ary_res ['invoice_head'];
                        // echo "<pre>";print_r($ary_res);exit;
                        $ary_orders ['is_invoice'] = 1;
                        if (empty($ary_res ['invoice_name'])) {
                            $ary_orders ['invoice_name'] = '个人';
                        } else {
                            $ary_orders ['invoice_name'] = $ary_orders ['invoice_name'];
                        }
                        // 个人姓名
                        $ary_orders ['invoice_people'] = $ary_orders ['invoice_people'];
                        // 纳税人识别号
                        $ary_orders ['invoice_identification_number'] = $ary_orders ['invoice_identification_number'];
                        // 注册地址
                        $ary_orders ['invoice_address'] = $ary_orders ['invoice_address'];
                        // 注册电话
                        $ary_orders ['invoice_phone'] = $ary_orders ['invoice_phone'];
                        // 开户银行
                        $ary_orders ['invoice_bank'] = $ary_orders ['invoice_bank'];
                        // 银行帐户
                        $ary_orders ['invoice_account'] = $ary_orders ['invoice_account'];
                        $ary_orders ['invoice_content'] = $ary_res ['invoice_content'];
                    }
                }
            } else {
                unset($ary_orders ['invoice_type']);
                unset($ary_orders ['invoice_head']);
                unset($ary_orders ['invoice_people']);
                unset($ary_orders ['invoice_name']);
                unset($ary_orders ['invoice_content']);
                unset($ary_orders ['invoices_val']);
            }
            $ary_receive_address = D('CityRegion')->getReceivingAddress($ary_member ['m_id']);
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
            elseif(!isset($default_address ['default_addr'] ['ra_id']) && $ary_orders['ra_id'] == 'other') {
                //使用临时收货地址
                 // 收货人
                $ary_orders ['o_receiver_name'] = $ary_orders['ra_name'];
                // 收货人电话
               // $ary_orders ['o_receiver_telphone'] = trim($default_address ['default_addr'] ['ra_phone']);
               $ary_receiver_telphone = array();
               if(!empty($ary_orders['ra_phone_area'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone_area']);
               if(!empty($ary_orders['ra_phone'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone']);
               if(!empty($ary_orders['ra_phone_ext'])) array_push($ary_receiver_telphone,$ary_orders['ra_phone_ext']);
               $ary_orders ['ra_id'] = 0;
               $ary_orders ['o_receiver_telphone'] = !empty($ary_receiver_telphone) ? implode('-',$ary_receiver_telphone): '';
                // 收货人手机
                $ary_orders ['o_receiver_mobile'] = trim($ary_orders['ra_mobile_phone']);
                // 收货人邮编
               
               $ary_orders ['o_receiver_zipcode'] = trim($ary_orders['ra_post_code']);
               
                // 收货人地址
                
                $ary_orders ['o_receiver_address'] = trim($ary_orders['ra_detail']);
               

                // 收货人省份
                $ary_orders ['o_receiver_state'] = $this->cityRegion->getAddressName($ary_orders['province']);

                // 收货人城市
                $ary_orders ['o_receiver_city'] = $this->cityRegion->getAddressName($ary_orders['city']);

                // 收货人地区
                $ary_orders ['o_receiver_county'] = $this->cityRegion->getAddressName($ary_orders['region']);
                
            }
			if(empty($ary_orders['o_receiver_city'])){
				if(!empty($ary_orders['ra_id'])){
					$ary_address = D('CityRegion')->getReceivingAddress($ary_member ['m_id'],$ary_orders['ra_id']);
					$ary_addr = explode(' ',$ary_address['address']);	
					if(!empty($ary_addr[1])){
						$ary_orders ['o_receiver_state'] = $ary_addr[0];
						$ary_orders ['o_receiver_city'] = $ary_addr[1];
						$ary_orders ['o_receiver_county'] = $ary_addr[2];
					}else{
					    return array('status'=>false,'msg'=>'请检查您的收货地址是否正确');
					}
				}else{
				    return array('status'=>false,'msg'=>'请检查您的收货地址是否正确');
				}
			}
            // 会员id
            $ary_orders ['m_id'] = $ary_member ['m_id'];
            // 订单id
            $ary_orders ['o_id'] = $order_id = date('YmdHis') . rand(1000, 9999);
            // 物流费用
            
            $ary_goods = $ary_cart;
            if (empty($ary_orders ['lt_id'])) {
		    return array('status'=>false,'msg'=>L('SELECT_LOGISTIC'));
            }
            //获取团购商品金额方式和普通商品不同
            $ary_orders ['o_goods_all_price'] = 0;
            $m_id =  $ary_member['m_id'];
            //团购商品
            if (isset($ary_orders ['gp_id'])) {
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 5) {
                            // 获取团购价与商品原价
                            $array_all_price = $price->getItemPrice($v['pdt_id'], 0, 5, $ary_orders ['gp_id']);
                           $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                          
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                        }
                    }
                    $logistic_price = $ary_orders ['cost_freight'];
                }
            } else if(isset($ary_orders ['sp_id'])){
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {

                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 7) {
                            // 获取秒杀价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 7, $ary_orders ['sp_id']);
//                            echo "<pre>";print_r($array_all_price);exit;
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
                        }
                    }
                    $logistic_price = $ary_orders ['cost_freight'];
                }
            }else if(isset($ary_orders ['p_id'])){
                $price = new PriceModel($m_id);
                if (!empty($ary_cart) && is_array($ary_cart)) {

                    foreach ($ary_cart as $k => $v) {
                        if ($v ['type'] == 8) {
                            // 获取预售价与商品原价
                            $array_all_price = $price->getItemPrice($ary_orders ['pdt_id'], 0, 8, $ary_orders ['p_id']);
//                            echo "<pre>";print_r($array_all_price);exit;
                            $o_all_price = sprintf("%0.3f", $v ['num'] * $array_all_price ['discount_price']);
                            //商品销售总价
                            $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.3f", $v ['num'] * $array_all_price ['pdt_price']);
                            $ary_orders ['o_discount'] = sprintf("%0.3f", $ary_orders ['o_goods_all_saleprice'] - $o_all_price);
                            $ary_orders ['o_goods_all_price'] = $o_all_price;
//                            echo "<pre>";print_r($ary_orders);exit;
                        }
                    }
                    $logistic_price = $ary_orders ['cost_freight'];
                }
            }else {
                if (!empty($ary_cart) && is_array($ary_cart)) {
                    foreach ($ary_cart as $key => $val) {
                        if ($val['type'] == '0') {
                            $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                            $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                        }
                    }
                }
                $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
                $subtotal = $pro_datas ['subtotal'];
                unset($pro_datas ['subtotal']);
                
                // 商品总价
                $promotion_total_price = '0';
                $promotion_price = '0';
                //赠品数组
                $gifts_cart = array();
                foreach ($pro_datas as $keys => $vals) {
                    foreach ($vals['products'] as $key => $val) {
                        $arr_products = D('Cart')->getProductInfo(array($key => $val),$ary_member['m_id']);

                        if ($arr_products[0][0]['type'] == '4' || $arr_products[0][0]['type'] == '6') {
                            foreach ($arr_products[0] as &$provals) {
                                $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                            }
                        }
                        $pro_datas[$keys]['products'][$key] = $arr_products[0];
                        $pro_data[$key] = $val;
                        $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
                    }
                    //赠品数组
                    if (!empty($vals['gifts'])) {
                        foreach ($vals['gifts'] as $gifts) {
                            //随机取一个pdt_id
                            $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                            $gifts_cart[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2);
                        }
                    }
                    $promotion_total_price += $vals['goods_total_price'];     //商品总价
                    if ($keys != '0') {
                        $promotion_price += $vals['pro_goods_discount'];
                    }
                }
                if(!empty($gifts_cart)){
                    $ary_tmp_cart = array_merge($ary_goods,$gifts_cart);
                    foreach($ary_tmp_cart as $atck=>$atcv){
                        $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                        unset($ary_tmp_cart[$atck]);
                    }
                }else{
                    $ary_tmp_cart = $ary_goods;
                }
                foreach ($pro_datas as $pro_data) {
                    if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                        foreach($pro_data['products'] as $proDatK=>$proDatV){
                            unset($ary_tmp_cart[$proDatK]);
                        }
                    }
                    if (!empty($pro_data ['pmn_class'])) {//订单只要包含一个促销商品，整个订单为促销，不返点
                        $User_Grade['ml_rebate'] = 0;
                    }
                }
                if(empty($ary_tmp_cart)){
                    $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
                }
                $logistic_price = D('Logistic')->getLogisticPrice($ary_orders['lt_id'], $ary_tmp_cart);
                //订单商品总价（销售价格带促销）
                $ary_orders ['o_goods_all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
                //商品销售总价
                $ary_orders ['o_goods_all_saleprice'] = sprintf("%0.2f", $promotion_total_price);
                $ary_data ['ary_product_data'] = D('Cart')->getProductInfo($ary_cart,$ary_member['m_id']);
            }
            
            //判断会员等级是否包邮
            if(isset($User_Grade['ml_free_shipping']) && $User_Grade['ml_free_shipping'] == 1){
                $logistic_price = 0;
            }
            //物流公司设置包邮额度
            $lt_expressions = json_decode(M('logistic_type')->where(array('lt_id'=>$ary_orders['lt_id']))->getField('lt_expressions'),true);
            if(!empty($lt_expressions['logistics_configure']) && $ary_orders['o_goods_all_price'] >= $lt_expressions['logistics_configure']){
                $logistic_price = 0;
            }
			//物流费用
            $ary_orders ['o_cost_freight'] = $logistic_price;
            // 优惠券金额
            if (isset($ary_orders ['coupon'])) {
                $str_csn = $ary_orders ['coupon'];
                
                $ary_coupon = $this->CheckCoupon($str_csn, $ary_data ['ary_product_data'],$ary_member['m_id']);
                
                if($ary_coupon['status'] == 'success'){
                    foreach ($ary_coupon['msg'] as $coupon){
						if($coupon['c_type'] == '1'){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$ary_orders ['o_goods_all_price']);
							}else{
								//计算可以使用优惠券总金额
								$coupon_all_price = 0;
								foreach ($pro_datas as $keys => $vals) {
									//是否可以使用优惠券
									$is_exsit_coupon = 0;
									foreach ($vals['products'] as $key => $val) {
										$arr_products = $this->cart->getProductInfo(array($key => $val),$ary_member['m_id']);
										if ($arr_products[0][0]['type'] == '4') {
											foreach ($arr_products[0] as $provals) {
												if(in_array($vals['g_id'],$coupon['gids'])){
													$is_exsit_coupon = 1;break;
												}
											}
										}
										if(in_array($val['g_id'],$coupon['gids'])){
											$is_exsit_coupon = 1;break;
										}
									}
									if($is_exsit_coupon == 1){
										$coupon_all_price += $vals['goods_total_price'];     //商品总价
									}
								}
							}
							$o_coupon_menoy +=sprintf('%.2f',(1-$coupon['c_money'])*$coupon_all_price);
						}else{
							$o_coupon_menoy += $coupon['c_money'];
						} 
                    }
                    $ary_orders ['o_coupon_menoy'] = $o_coupon_menoy;
                }
            }
		//红包支付
		$bonus = $ary_orders['bonus'];
		if ($bonus > 0) {
		    $arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
		    if($bonus > $arr_bonus['m_bonus']){
			return array('status'=>false,'msg'=>'红包金额不能大于用户可用金额！');
		    }elseif($promotion_total_price < $bonus) {
			return array('status'=>false,'msg'=>'红包金额超过了商品总金额！');
		    }else{
			$ary_orders['o_bonus_money'] = $bonus;
		    }
		}else{
			$ary_orders['o_bonus_money'] = 0;
		}
		//九龙币支付
		$jlb = $ary_orders['jiulongbi'];
		if ($jlb > 0) {
		    $arr_jlb = M('Members')->field("m_jlb")->where(array('m_id'=>$ary_member['m_id']))->find();
		    if($jlb > $arr_jlb['m_jlb']){
			return array('status'=>false,'msg'=>'九龙币金额不能大于用户可用金额！');
		    }elseif($promotion_total_price < $jlb) {
			return array('status'=>false,'msg'=>'九龙币金额超过了商品总金额！');
		    }else{
			$ary_orders['o_jlb_money'] = $jlb;
		    }
		}else{
			$ary_orders['o_jlb_money'] = 0;
		}
		//九龙金豆支付
		$point = $ary_orders['point'];
		if ($point > 0) {
		    $pointCfg = D('PointConfig');
		    // 计算订单可以使用的九龙金豆
		    $is_use_point = $pointCfg->getIsUsePoint($promotion_total_price,$ary_member['m_id']);
		    if($point <= $is_use_point){
			    $ary_data = $pointCfg->getConfigs();
			    $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
			//九龙金豆抵扣的总金额
			$ary_orders['o_point_money'] = sprintf("%0.2f", (0.01/$consumed_points)*$point);
			$ary_orders['freeze_point'] = $point;
		    }else{
			return array('status'=>false,'msg'=>'九龙金豆使用失败！不能大于订单可使用的九龙金豆！');
		    }
		}else{
		    $ary_orders ['o_point_money'] = 0;
		    $ary_orders ['freeze_point'] = 0;
		}
	    $freeze_point = $ary_orders['freeze_point'];
            // 订单总价 商品会员折扣价-优惠券金额-红包金额-储值卡金额-九龙币-九龙金豆抵扣金额
            if (!isset($ary_orders ['gp_id']) && !isset($ary_orders['p_id']) && !isset($ary_orders['sp_id'])) {
                $all_price = $ary_orders ['o_goods_all_price'] - $ary_orders ['o_coupon_menoy'] - $ary_orders['o_bonus_money'] - $ary_orders['o_jlb_money'] - $ary_orders['o_point_money'];
            }else{
                $all_price = $o_all_price;
            }
            if ($all_price <= 0) {
                $all_price = 0;
            }
            // 订单应付总价 订单总价+运费
            $all_price += $ary_orders ['o_cost_freight'];
            $ary_orders ['o_all_price'] = sprintf("%0.3f", $all_price);
	    if($ary_orders['buyer_comments']){
		    $ary_orders ['o_buyer_comments'] = $ary_orders ['buyer_comments'];
	    }
            // 是否预售单
            if (isset($ary_orders ['g_pre_sale_status']) && $ary_orders ['g_pre_sale_status'] == 1) {
                $ary_orders ['o_pre_sale'] = 1;
            }
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
            if ($ary_member ['admin_id']) {
                $ary_orders ['o_addorder_id'] = $ary_member ['admin_id'];
            }
            //判断是否开启自动审核功能
            $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
            if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 && $ary_orders['o_payment'] == 6){
                $ary_orders['o_audit'] = 1;
            }
            //促销信息存起来暂时隐藏
            //$ary_orders['promotion'] = serialize($pro_datas);
            if(empty($ary_orders['o_goods_all_price'])){
                $orders->rollback();
	        return array('status'=>false,'msg'=>'订单商品总金额不能为空');
			}
			//是否是匿名购买
			if($ary_orders['is_anonymous'] != '1'){
				unset($ary_orders['is_anonymous']);
			}
            $bool_orders = D('Orders')->doInsert($ary_orders);
            // $bool_orders = true;
            if (!$bool_orders) {
                $orders->rollback();
	        return array('status'=>false,'msg'=>'生成订单主表失败！');
            } else {
                $ary_orders_items = array();

                $ary_orders_goods = D('Cart')->getProductInfo($ary_cart,$ary_member['m_id']);
                if (!empty($gifts_cart)) {
                    $ary_gifts_goods = D('Cart')->getProductInfo($gifts_cart,$ary_member['m_id']);
                    if (!empty($ary_gifts_goods)) {
                        foreach ($ary_gifts_goods as $gift) {
                            array_push($ary_orders_goods, $gift);
                        }
                    }
                }
                if (!empty($ary_orders_goods) && is_array($ary_orders_goods)) {
                    $total_consume_point = 0; // 消耗九龙金豆
                    $int_pdt_sale_price = 0; // 货品销售原价总和
                    $gifts_point_reward = '0'; //有设置购商品赠九龙金豆所获取的九龙金豆数
                    $gifts_point_goods_price  = '0'; //设置了购商品赠九龙金豆的商品的总价
                    foreach ($ary_orders_goods as $k => $v) {
                        $ary_orders_items = array();
                        
                        if ($v ['type'] == 3) {
                            $combo_list = D('ReletedCombinationGoods')->getComboList($v ['pdt_id']);
                            if (!empty($combo_list)) {
                                foreach ($combo_list as $combo) {
                                    // 订单id
                                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                    // 商品id
                                    $combo_item_data = D('GoodsProducts')->Search(array(
                                        'pdt_id' => $combo ['releted_pdt_id']
                                            ), array(
                                        'g_sn',
                                        'g_id'
                                            ));
                                    $ary_orders_items ['g_id'] = $combo_item_data ['g_id'];
                                    // 组合商品ID
                                    $ary_orders_items ['fc_id'] = $v ['pdt_id'];
                                    // 货品id
                                    $ary_orders_items ['pdt_id'] = $combo ['releted_pdt_id'];
                                    // 类型id
                                    $ary_orders_items ['gt_id'] = $combo ['gt_id'];
                                    // 商品sn
                                    $ary_orders_items ['g_sn'] = $combo_item_data ['g_sn'];
                                    // 货品sn
                                    $ary_orders_items ['pdt_sn'] = $combo ['pdt_sn'];
                                    // 商品名字
                                    $combo_good_data = D('GoodsInfo')->Search(array(
                                        'g_id' => $combo_item_data ['g_id']
                                            ), array(
                                        'g_name'
                                            ));
                                    $ary_orders_items ['oi_g_name'] = $combo_good_data ['g_name'];
                                    // 成本价
                                    $ary_orders_items ['oi_cost_price'] = $combo ['pdt_cost_price'];
                                    // 货品销售原价
                                    $ary_orders_items ['pdt_sale_price'] = $combo ['pdt_sale_price'];
                                    // 购买单价
                                    $ary_orders_items ['oi_price'] = $combo ['com_price'];
                                    // 组合商品
                                    $ary_orders_items ['oi_type'] = 3;

                                    $int_pdt_sale_price += $combo ['com_price'] * $combo ['com_nums'];

                                    // 商品数量
                                    $ary_orders_items ['oi_nums'] = $combo ['com_nums'] * $v ['pdt_nums'];
                                    //返点比例
                                    if (!empty($User_Grade['ml_rebate'])) {
                                        $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                    }
                                    //等级折扣
                                    if (!empty($User_Grade['ml_discount'])) {
                                        $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                    }
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
					return array('status'=>false,'msg'=>'生成订单明细表失败！');
                                    }
                                    // 商品库存扣除
                                    $ary_payment_where = array(
                                        'pc_id' => $ary_orders ['o_payment']
                                    );
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                        // by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array(
                                                    'g_pre_sale_status'
                                                ))->where(array(
                                                    'g_id' => $ary_orders_items ['g_id']
                                                ))->find();
                                        if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                            //查询库存,如果库存数为负数则不再扣除库存
                                            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                           ->field('pdt_stock')
                                                                           ->where(array('o_id'=>$ary_orders['o_id']))
                                                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                           ->find();
                                            if(0 >= $int_pdt_stock['pdt_stock']){
						return array('status'=>false,'msg'=>'该货品已售完！');
                                            }
                                            $array_result = D('GoodsProducts')->UpdateStock($combo ['releted_pdt_id'], $ary_orders_items ['oi_nums']);
                                            if (false == $array_result ["status"]) {
                                                $orders->rollback();
						return array('status'=>false,'msg'=>$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
            
                            // 自由推荐商品
                            if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {

                                foreach ($v as $key => $item_info) {
                                    // 订单id
                                    $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                    // 商品id
                                    $ary_orders_items ['g_id'] = $item_info ['g_id'];
                                    // 货品id
                                    $ary_orders_items ['pdt_id'] = $item_info ['pdt_id'];
                                    // 类型id
                                    $ary_orders_items ['gt_id'] = $item_info ['gt_id'];
                                    // 商品sn
                                    $ary_orders_items ['g_sn'] = $item_info ['g_sn'];
                                    // o_sn
                                    // $ary_orders_items['g_id'] = $v['g_id'];
                                    // 货品sn
                                    $ary_orders_items ['pdt_sn'] = $item_info ['pdt_sn'];
                                    // 商品名字
                                    $ary_orders_items ['oi_g_name'] = $item_info ['g_name'];
                                    // 成本价
                                    $ary_orders_items ['oi_cost_price'] = $item_info ['pdt_cost_price'];
                                    // 货品销售原价
                                    $ary_orders_items ['pdt_sale_price'] = $item_info ['pdt_sale_price'];
                                    // 购买单价
                                    $ary_orders_items ['oi_price'] = $item_info ['pdt_momery'];
                                    $ary_orders_items['promotion'] = $item_info['pdt_rule_name'];
                                    // 自由组合ID
                                    $ary_orders_items ['fc_id'] = isset($item_info ['fc_id']) ? $item_info ['fc_id'] : $item_info ['fr_id'];
                                    // 商品九龙金豆
                                    if (isset($v [0] ['type']) && $v [0] ['type'] == 4 && $item_info['fc_id'] != '') {
                                        $ary_orders_items ['oi_type'] = 4;
                                        $int_pdt_sale_price += $item_info ['pdt_sale_price'] * $item_info ['pdt_nums'];
                                    } elseif (isset($v [0] ['type']) && $v [0] ['type'] == 6 && $item_info['fr_id'] != '') {
                                        $ary_orders_items ['oi_type'] = 6;
                                        $int_pdt_sale_price += $item_info ['pdt_sale_price'] * $item_info ['pdt_nums'];
                                    } else {
                                        unset($ary_orders_items['fc_id']);
                                        unset($ary_orders_items['promotion']);
                                        $ary_orders_items ['oi_type'] = 0;
                                    }
                                    // 商品数量
                                    $ary_orders_items ['oi_nums'] = $item_info ['pdt_nums'];
                                    //返点比例
                                    if (!empty($User_Grade['ml_rebate'])) {
                                        $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                    }
                                    //等级折扣
                                    if (!empty($User_Grade['ml_discount'])) {
                                        $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                    }
                                    $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                    if (!$bool_orders_items) {
                                        $orders->rollback();
					return array('status'=>false,'msg'=>'生成订单明细表失败！');
                                    }
                                    // 商品库存扣除
                                    $ary_payment_where = array(
                                        'pc_id' => $ary_orders ['o_payment']
                                    );
                                    $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                    if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                        // by Mithern 扣除可下单库存生成库存调整单
                                        $good_sale_status = D('Goods')->field(array(
                                                    'g_pre_sale_status'
                                                ))->where(array(
                                                    'g_id' => $item_info ['g_id']
                                                ))->find();
                                        if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                            //查询库存,如果库存数为负数则不再扣除库存
                                            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                           ->field('pdt_stock')
                                                                           ->where(array('o_id'=>$ary_orders['o_id']))
                                                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                           ->find();
                                            if(0 >= $int_pdt_stock['pdt_stock']){
						return array('status'=>false,'msg'=>'该货品已售完！');
                                            }
                                            $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $item_info ['pdt_nums']);
                                            if (false == $array_result ["status"]) {
                                                $orders->rollback();
						return array('status'=>false,'msg'=>$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                            }
                                        }
                                    }
                                }
                            }elseif($v ['type'] == '7'){
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $v ['g_id'];
                                // 秒杀商品ID,取一下
								/**
                                $fc_id = M('spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'g_id' => $v ['g_id'],
                                            'sp_status' => '1'
                                        ))->getField('sp_id');
								**/
								$ary_orders_items ['fc_id'] = $ary_orders['sp_id'];
                                // 货品id
                                $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                                // 类型id
                                $ary_orders_items ['gt_id'] = $v ['gt_id'];
                                // 商品sn
                                $ary_orders_items ['g_sn'] = $v ['g_sn'];
                                // 货品sn
                                $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                                // 商品名字
                                $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                                // 成本价
                                $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                                // 货品销售原价
                                $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                                // 秒杀商品
                                $ary_orders_items ['oi_type'] = $v ['type'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] =  $array_all_price ['discount_price'];
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                                //返点比例
                                if (!empty($User_Grade['ml_rebate'])) {
                                    $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                }
//                                echo "<pre>";print_R($v);exit;
                                //等级折扣
                                if (!empty($User_Grade['ml_discount'])) {
                                    $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                }
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'生成订单明细失败！');
                                }
                                $retun_buy_nums=D("Spike")->where(array('sp_id' => $ary_orders_items['fc_id']))->setInc("sp_now_number",$ary_orders['num']);
                                if (!$retun_buy_nums) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'更新秒杀量失败！');
                                }

                            } elseif ($v ['type'] == '5') { // 团购商品
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $v ['g_id'];
                                // 团购商品ID,取一下
								/**
                                $fc_id = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'g_id' => $v ['g_id'],
                                            'deleted' => '0',
                                            'is_active' => '1'
                                        ))->getField('gp_id');
                                $ary_orders_items ['fc_id'] = $fc_id;
								**/
                                $ary_orders_items ['fc_id'] = $ary_orders['gp_id'];
                                
                                // 货品id
                                $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                                // 类型id
                                $ary_orders_items ['gt_id'] = $v ['gt_id'];
                                // 商品sn
                                $ary_orders_items ['g_sn'] = $v ['g_sn'];
                                // 货品sn
                                $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                                // 商品名字
                                $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                                // 成本价
                                $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                                // 货品销售原价
                                $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                                // 团购商品
                                $ary_orders_items ['oi_type'] = $v ['type'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $array_all_price ['discount_price'];
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                                //返点比例
                                if (!empty($User_Grade['ml_rebate'])) {
                                    $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                }
                                //等级折扣
                                if (!empty($User_Grade['ml_discount'])) {
                                    $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                }
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'生成订单明细失败！');
                                }
                                $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $ary_orders_items['fc_id']))->setInc("gp_now_number",$ary_orders['num']);
                                if (!$retun_buy_nums) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'更新团购量失败！');
                                }

                                // 生成团购日志
                                $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
                                $ary_gb_log ['gp_id'] = $ary_orders ['gp_id'];
                                $ary_gb_log ['m_id'] = $ary_member['m_id'];
                                $ary_gb_log ['g_id'] = $v ['g_id'];
                                $ary_gb_log ['num'] = $ary_orders ['num'];
                                if (false === M('groupbuy_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'生成团购日志失败！');
                                }

                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array(
                                                'g_pre_sale_status'
                                            ))->where(array(
                                                'g_id' => $v ['g_id']
                                            ))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        //查询库存,如果库存数为负数则不再扣除库存
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
					    return array('status'=>false,'msg'=>'该货品已售完！');
                                        }
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                        if (false == $array_result ["status"]) {
                                            $orders->rollback();
					    return array('status'=>false,'msg'=>$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            }elseif($v ['type'] == '8'){
                                // 订单id
                                $ary_orders_items ['o_id'] = $ary_orders ['o_id'];
                                // 商品id
                                $ary_orders_items ['g_id'] = $v ['g_id'];
                                // 预售商品ID
                                $fc_id = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'g_id' => $v ['g_id'],
                                            'deleted' => '0',
                                            'is_active' => '1'
                                        ))->getField('p_id');
                                $ary_orders_items ['fc_id'] = $fc_id;
                                // 货品id
                                $ary_orders_items ['pdt_id'] = $v ['pdt_id'];
                                // 类型id
                                $ary_orders_items ['gt_id'] = $v ['gt_id'];
                                // 商品sn
                                $ary_orders_items ['g_sn'] = $v ['g_sn'];
                                // 货品sn
                                $ary_orders_items ['pdt_sn'] = $v ['pdt_sn'];
                                // 商品名字
                                $ary_orders_items ['oi_g_name'] = $v ['g_name'];
                                // 成本价
                                $ary_orders_items ['oi_cost_price'] = $v ['pdt_cost_price'];
                                // 货品销售原价
                                $ary_orders_items ['pdt_sale_price'] = $v ['pdt_sale_price'];
                                // 预售商品
                                $ary_orders_items ['oi_type'] = $v ['type'];
                                // 购买单价
                                $ary_orders_items ['oi_price'] = $int_pdt_sale_price = $array_all_price ['discount_price'];
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $ary_orders ['num'];
                                //返点比例
                                if (!empty($User_Grade['ml_rebate'])) {
                                    $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                }
                                //等级折扣
                                if (!empty($User_Grade['ml_discount'])) {
                                    $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                }
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'新增订单明细失败！');
                                }
                                // 生成预售日志
                                $ary_gb_log ['o_id'] = $ary_orders ['o_id'];
                                $ary_gb_log ['p_id'] = $ary_orders ['p_id'];
                                $ary_gb_log ['m_id'] = $_SESSION ['Members'] ['m_id'];
                                $ary_gb_log ['g_id'] = $v ['g_id'];
                                $ary_gb_log ['num'] = $ary_orders ['num'];
                                if (false === M('presale_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_gb_log)) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'生成预售日志失败！');
                                }

                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array(
                                                'g_pre_sale_status'
                                            ))->where(array(
                                                'g_id' => $v ['g_id']
                                            ))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        //查询库存,如果库存数为负数则不再扣除库存
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
					    return array('status'=>false,'msg'=>'该货品已售完！');
                                        }
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $ary_orders ['num']);
                                        if (false == $array_result ["status"]) {
                                            $orders->rollback();
					    return array('status'=>false,'msg'=>$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            } else {
                                if (!empty($v['rule_info']['name'])) {
                                    $v['pmn_name'] = $v['rule_info']['name'];
                                }
                                //促销信息
                                foreach ($pro_datas as $vals) {
                                    foreach ($vals['products'] as $key => $val) {
                                        if (($val['type'] == $v['type']) && ($val['pdt_id'] == $v['pdt_id'])) {
                                            if (!empty($vals['pmn_name'])) {
                                                $v['pmn_name'] .= ' ' . $vals['pmn_name'];
                                            }
                                        }
                                    }
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
                                //返点比例
                                if (!empty($User_Grade['ml_rebate'])) {
                                    $ary_orders_items['ml_rebate'] = $User_Grade['ml_rebate'];
                                }
                                //等级折扣
                                if (!empty($User_Grade['ml_discount'])) {
                                    $ary_orders_items['ml_discount'] = $User_Grade['ml_discount'];
                                }
                                // 商品九龙金豆
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
                                if($v['gifts_point']>0 && isset($v['gifts_point']) && isset($v['is_exchange'])){
                                    $gifts_point_reward += $v['gifts_point']*$v['pdt_nums'];
                                    $gifts_point_goods_price += $v['pdt_sale_price']*$v['pdt_nums'];
                                }
                                if (isset($v['pmn_name'])) {
                                    $ary_orders_items['promotion'] = $v['pmn_name'];
                                }
                                // 商品数量
                                $ary_orders_items ['oi_nums'] = $v ['pdt_nums'];
                                $bool_orders_items = D('Orders')->doInsertOrdersItems($ary_orders_items);
                                if (!$bool_orders_items) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'生成订单明细失败！');
                                }
                                // 商品库存扣除
                                $ary_payment_where = array(
                                    'pc_id' => $ary_orders ['o_payment']
                                );
                                $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
                                if ($ary_payment ['pc_abbreviation'] == 'DELIVERY' || $ary_payment ['pc_abbreviation'] == 'OFFLINE') {
                                    // by Mithern 扣除可下单库存生成库存调整单
                                    $good_sale_status = D('Goods')->field(array(
                                                'g_pre_sale_status'
                                            ))->where(array(
                                                'g_id' => $v ['g_id']
                                            ))->find();
                                    if ($good_sale_status ['g_pre_sale_status'] != 1) { // 如果是预售商品不扣库存
                                        //查询库存,如果库存数为负数则不再扣除库存
                                        $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                                                       ->field('pdt_stock,pdt_min_num')
                                                                       ->where(array('o_id'=>$ary_orders['o_id']))
                                                                       ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                                                       ->find();
                                        if(0 >= $int_pdt_stock['pdt_stock']){
					    return array('status'=>false,'msg'=>'该货品已售完！');
                                        }
                                        if($v['pdt_nums'] < $int_pdt_stock['pdt_min_num']){
					    return array('status'=>false,'msg'=>'该货品至少购买'.$int_pdt_stock['pdt_min_num']);
                                        }
                                        $array_result = D('GoodsProducts')->UpdateStock($ary_orders_items ['pdt_id'], $v ['pdt_nums']);
                                        if (false == $array_result ["status"]) {
						$orders->rollback();
					    return array('status'=>false,'msg'=>$array_result ['msg'] . ',CODE:' . $array_result ["code"]);
                                        }
                                    }
                                }
                            }
                        }
                        // 产品销量
                        if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
                            foreach ($v as $good) {
                                $ary_goods_num = M("goods_info")->where(array(
                                            'g_id' => $good ['g_id']
                                        ))->data(array(
                                            'g_salenum' => array(
                                                'exp',
                                                'g_salenum + '.$good['pdt_nums']
                                            )
                                        ))->save();
                                if (!$ary_goods_num) {
                                    $orders->rollback();
				    return array('status'=>false,'msg'=>'更新产品销量失败');
                                }
                            }
                        } else {
                            $ary_goods_num = M("goods_info")->where(array(
                                        'g_id' => $v ['g_id']
                                    ))->data(array(
                                        'g_salenum' => array(
                                            'exp',
                                            'g_salenum + '.$v['pdt_nums']
                                        )
                                    ))->save();
                            if (!$ary_goods_num) {
                                $orders->rollback();
				return array('status'=>false,'msg'=>'更新产品销量失败');
                            }
                        }
                    }

                    // 商品下单获得总九龙金豆
                    $other_all_price = $int_pdt_sale_price-$gifts_point_goods_price;
                    $total_reward_point = D('PointConfig')->getrRewardPoint($other_all_price);
                    $total_reward_point += $gifts_point_reward;

                    $total_consume_point += $freeze_point;
                    // 有消耗九龙金豆或者获得九龙金豆，消耗九龙金豆插入订单表进行冻结操作
                    if ($total_consume_point > 0 || $total_reward_point > 0) {
                        $ary_freeze_point = array(
                            'o_id' => $ary_orders ['o_id'],
                            'm_id' => $ary_member['m_id'],
                            'freeze_point' => $total_consume_point,
                            'reward_point' => $total_reward_point
                        );
                        $res_point = D('Orders')->updateFreezePoint($ary_freeze_point);
                        if (!$res_point) {
                            $orders->rollback();
			    return array('status'=>false,'msg'=>'更新冻结九龙金豆失败!');
                        }
                    }

		    //更新红包使用
		    $bonus_price = $ary_orders['o_bonus_money'];
		    if(isset($bonus_price) && $bonus_price>0){
			$arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
			if($bonus_price > $arr_bonus['m_bonus']){
			    return array('status'=>false,'msg'=>'红包金额不能大于用户可用金额！');
			}elseif($promotion_total_price < $bonus_price) {
			    return array('status'=>false,'msg'=>'红包金额超过了商品总金额！');
			}
			$arr_bonus = array(
			    'bt_id' => '4',
			    'm_id'  => $ary_member['m_id'],
			    'bn_create_time'  => date("Y-m-d H:i:s"),
			    'bn_type' => '1',
			    'bn_money' => $bonus_price,
			    'bn_desc' => 'APP支付使用'.$bonus_price."元",
			    'o_id' => $ary_orders['o_id'],
			    'bn_finance_verify' => '1',
			    'bn_service_verify' => '1',
			    'bn_verify_status' => '1',
			    'single_type' => '2'
			);
			$res_bonus = D('BonusInfo')->addBonus($arr_bonus);
			if (!$res_bonus) {
			    $orders->rollback();
			    return array('status'=>false,'msg'=>'新增红包调整单失败！');
			}
		    }
		    //更新九龙币使用
		    $jlb_price = $ary_orders['o_jlb_money'];
		    if(isset($jlb_price) && $jlb_price>0){
			$arr_jlb = array(
			    'jt_id' => '2',
			    'm_id'  => $ary_member['m_id'],
			    'ji_create_time'  => date("Y-m-d H:i:s"),
			    'ji_type' => '1',
			    'ji_money' => $jlb_price,
			    'ji_desc' => 'APP支付使用'.$jlb_price."元",
			    'o_id' => $ary_orders['o_id'],
			    'ji_finance_verify' => '1',
			    'ji_service_verify' => '1',
			    'ji_verify_status' => '1',
			    'single_type' => '2'
			);
			$res_jlb = D('JlbInfo')->addJlb($arr_jlb);
			if (!$res_jlb) {
			    D('')->rollback();
			    return array('status'=>false,'msg'=>'新增九龙币调整单失败！');
			}
		    }
		    // 更新优惠券使用
			if($ary_coupon['status'] == 'success'){
			    foreach ($ary_coupon['msg'] as $coupon){
				// 更新优惠券使用
				$ary_data = array(
				    'c_is_use' => 1,
				    'c_used_id' => $ary_member['m_id'],
				    'c_order_id' => $ary_orders ['o_id']
				);
				$res_coupon = D('Coupon')->doCouponUpdate($coupon ['c_sn'], $ary_data);
				if (!$res_coupon) {
					return array('status'=>false,'msg'=>'使用优惠券生成日志失败！');
				}
			    }
			}
                }
            }
            // 订单日志记录
            $ary_orders_log = array(
                'o_id' => $ary_orders ['o_id'],
                'ol_behavior' => '创建',
                'ol_uname' => $ary_member['m_name'],
                'ol_create' => date('Y-m-d H:i:s')
            );
            
            $res_orders_log = D('OrdersLog')->add($ary_orders_log);
            if (!$res_orders_log) {
	    	$orders->rollBack();
		return array('status'=>false,'msg'=>'更新订单日志表失败!');
            }
            $orders->commit();
            if (!empty($ary_member['m_id'])) {
                $mix_pdt_id = array();
                $mix_pdt_type = array();
                foreach ($ary_cart as $key=>$val){
                    $mix_pdt_id[] = $key;
                    $mix_pdt_type[] = $val['type'];
                }
                D('Cart')->doUpadteOrdersCart($mix_pdt_id,$mix_pdt_type);
            } 
	    return array('status'=>true,'msg'=>'','trans_no'=>$ary_orders['o_id'],'orders_price'=>$ary_orders['o_all_price']);
        }
    }

    /**
     * 配送方式列表获取
     */
    public function getLogisticList($array_params) {
        $ary_member = D('Members')->where(array('m_name'=>$array_params['name']))->find();
        if (!empty($ary_member ['m_id'])) {
            $goods_list = json_decode($array_params['cart_list'],1);
            if(empty($goods_list) && !isset($goods_list)){
                return array('status'=>false,'msg'=>'商品信息不能为空','data'=>array());
            }
            $ary_cart = array();
            foreach($goods_list as $goods){
                $ary_cart[$goods['pdt_id']] = $goods;
            }
        } else {
            return array('status'=>false,'msg'=>'会员信息不存在！','data'=>array());
        }
        $cr_id = $array_params['cr_id'];
        $ary_tmp_cart = $ary_cart;
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as $key => $val) {
                if ($val['type'] == '0') {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
                    $ary_cart[$key]['g_id'] = $ary_gid['g_id'];
                }
            }
        }
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
        //赠品数组
        $gifts_cart = array();
        foreach ($pro_datas as $keys => $vals) {
            //赠品数组
            if (!empty($vals['gifts'])) {
                foreach ($vals['gifts'] as $gifts) {
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id' => $gifts['g_id'], 'pdt_stock' => array('GT', 0)), 'pdt_id');
                    $gifts_cart[$pdt_id['pdt_id']] = array('pdt_id' => $pdt_id['pdt_id'], 'num' => 1, 'type' => 2,'g_id' => $gifts['g_id']);
                }
            }
        }
        if(!empty($gifts_cart)){
            $ary_tmp_cart = array_merge($ary_cart,$gifts_cart);
            foreach($ary_tmp_cart as $atck=>$atcv){
                $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                unset($ary_tmp_cart[$atck]);
            }
        }
        foreach ($pro_datas as $pro_data) {
            if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                foreach($pro_data['products'] as $proDatK=>$proDatV){
                    unset($ary_tmp_cart[$proDatK]);
                }
            }
        }
        if(empty($ary_tmp_cart)){
            $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
        }
        $ary_logistic = D('Logistic')->getLogistic($cr_id,$ary_tmp_cart);
        //判断当前物流公司是否设置包邮额度
        foreach($ary_logistic as &$logistic_v){
            $lt_expressions = json_decode($logistic_v['lt_expressions'],true);
            if(!empty($lt_expressions['logistics_configure']) && $pro_datas['subtotal']['goods_total_price'] >= $lt_expressions['logistics_configure']){
                $logistic_v['logistic_price'] = 0;
            }
        }
        return array('status'=>true,'data'=>$ary_logistic);
    }

    /**
     * 会员九龙金豆调整接口
     */
    public function syncPoint($array_params){
        $ary_member = D('Members')->where(array('m_name'=>$array_params['name']))->find();
        if (!empty($ary_member ['m_id'])) {
            $freeze_point = $array_params['point'];
            if($freeze_point == 0){
                return array('status'=>false,'msg'=>'九龙金豆无增减！');
            }
            $type = $freeze_point > 0 ? 22:23;
            $total_points = $ary_member['total_point']+$freeze_point;
            $points = $total_points-$ary_member['freeze_point'];
            if($points < 0 && $type == 23){
                return array('status'=>false,'msg'=>'九龙金豆调整失败,您的九龙金豆已不足！');
            }
            $data = array('total_point'=>$total_points);
            $ary_freeze_result = D('Members')->where(array('m_id'=>$ary_member['m_id']))->save($data);
            if(!$ary_freeze_result){
                return array('status'=>false,'msg'=>'九龙金豆调整失败！');
            }else{
                $reward_point = 0;
                $consume_point = 0;
                if($type == 22){
                    $reward_point = $freeze_point;
                }elseif($type == 23){
                    $consume_point = abs($freeze_point);
                }
                $ary_log = array(
                        'type'=>$type,
                        'consume_point'=> $consume_point,
                        'reward_point'=> $reward_point,
                        );
                $ary_info =D('PointLog')->addPointLog($ary_log,$ary_member['m_id']);
                if($ary_info['status']!=1){
                    return array('status' => false, 'msg' => $ary_info['msg']);
                }
                return array('created' => date('Y-m-d H:i:s'), 'name' => $ary_member['m_name'],'points'=>$points);
            }
        } else {
            return array('status'=>false,'msg'=>'会员信息不存在或未同步会员信息！');
        }
    }

    /**
     * 会员九龙金豆调整接口
     */
    public function syncAppPoint($array_params){
        $arr_consumer = D('Members')->where(array('m_id'=>$array_params['consumers_id'],'m_status'=>1))->find();
        if(empty($arr_consumer)){
            return array('status'=>false,'msg'=>'消费者ID不存在或不可用！');
        }
        $in_shop = $array_params['start_time'];
        $out_shop = $array_params['end_time'];
        $time_diff = strtotime($out_shop)-strtotime($in_shop);
        if($time_diff<0){
            return array('status'=>false,'msg'=>'进店时间不能大于离店时间！');
        }
        $array_cond["pa_start_time"] = array("elt",$in_shop);
		$array_cond["pa_status"] = array('eq',1);
		$array_cond["gc_id"] = $array_params['merchant_id'];
        $arr_merchant = D('PointActivity')->where($array_cond)->find();
        if($arr_consumer['m_id'] == $arr_merchant['m_id']){
            return array('status'=>false,'msg'=>'商户自己不能使用赠九龙金豆活动！');
        }
        if(empty($arr_merchant)){
            return array('status'=>false,'msg'=>'商户没有参加赠九龙金豆活动或消费者没有在活动期进店！');
        }
        //echo D()->getlastsql();exit;
        $how_time = $arr_merchant['pa_how_time']*60;
        if($time_diff<$how_time){
            return array('status'=>false,'msg'=>'停留时间不足'.$arr_merchant['pa_how_time'].'分钟');
        }
        //计算赠送的次数
        $nums = intval($time_diff/$how_time);
        //计算一共赠送的九龙金豆
        $total_point = $nums*$arr_merchant['pa_times_num'];
        if($total_point > 0){
            $res_point = D('PointConfig')->setMemberConsumePoints($total_point,$arr_merchant['m_id'],21);
            if(!$res_point['result']){
                return array('status'=>false,'msg'=>$res_point['message']);
            }else{
                $res_point = D('PointConfig')->setMemberRewardPoints($total_point,$arr_consumer['m_id'],20);
                if(!$res_point['result']){
                    return array('status'=>false,'msg'=>$res_point['message']);
                }
            }
            return array('status'=>true,'msg'=>'','point'=>$total_point);
        }
    }

    /**
     * 获取门店列表信息
     */
    public function getCategory($array_params){
        $ary_where['gc_status'] = 1;
        $ary_where['gc_name'] = array('neq','店铺');
        $ary_where['gc_type'] = isset($array_params['type'])?$array_params['type']:2;
		$limit['pagesize'] = empty($array_params['page_size']) ? '20' : $array_params['page_size'];
		$limit['start'] = empty($array_params['page_no']) ? '1' : $array_params['page_no'];
		$ary_cate = M('GoodsCategory', C('DB_PREFIX'), 'DB_CUSTOM')
		->field($array_params['fields'])->where($ary_where)
		->limit(($limit['start'] - 1) * $limit['pagesize'], $limit['pagesize'])
		->select();
		$count = M('GoodsCategory', C('DB_PREFIX'), 'DB_CUSTOM')->where($ary_where)->count();
		return array('total_results' => $count, 'cates' => $ary_cate);
    }

    /**
    * 处理字段映射
    * return array
    */
    private function parseFields($array_table_fields,$array_client_fields){
        $aray_fetch_field = array();
        foreach($array_client_fields as $field_name => $as_name){
            if(isset($array_table_fields[$field_name]) && !empty($as_name)){
                $aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);

            }
        }
        if(empty($aray_fetch_field)){
            return null;
        }
        return $aray_fetch_field;
    }

	/**
     * 处理订单明细金额
	 * 明细金额等于订单商品总金额$ary_orders['o_goods_all_price'] 每个明细总金额$ary_good['']
	 * 红包、储值卡、九龙币、九龙金豆oi_bonus_money、oi_cards_money、oi_jlb_money、oi_point_money
     * wangguibin@guanyisoft.com
	 * date 2014-09-16
     */		
	public function getOrdersGoods($ary_orders_goods,$ary_orders,$ary_coupon){
		//如果优惠券存在计算每个明细优惠券金额
		//优惠券总金额为oi_coupon_menoy $ary_orders['o_coupon_menoy'] 使用优惠券的商品总金额、每个明细的商品总金额
		$int_coupon_total_money = 0;
		$int_pdt_total_price = 0;
		$int_coupon_num = 0;
		//商品总数量
		$int_good_total_num = 0;
		foreach ($ary_orders_goods as $k => &$v) {
			if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
			}else{
				$v['pdt_total_price'] = 0;
			}
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						$int_good_total_num +=1;
						// 购买单价
						if (isset($v [0] ['type']) && $v [0] ['type'] == 4 && $item_info['fc_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} elseif (isset($v [0] ['type']) && $v [0] ['type'] == 6 && $item_info['fr_id'] != '') {
							$item_info['pdt_total_price'] += $item_info ['f_price'] * $item_info ['pdt_nums'];
						} 
						$int_pdt_total_price +=$item_info['pdt_total_price'];
						if($ary_coupon['status'] == 'success'){
							$is_use_coupon = 0;
							foreach ($ary_coupon['msg'] as $coupon){
								//计算参与优惠券使用的商品
								if($coupon['gids'] == 'All'){
									$is_use_coupon = 1;
								}else{
									if(in_array($item_info['g_id'],$coupon['gids'])){
										$is_use_coupon = 1;
									}
								}
							}
							if($is_use_coupon == 1){
								$int_coupon_total_money += $item_info ['f_price'] * $item_info ['pdt_nums'];
								$item_info['is_use_coupon'] = 1;
								$int_coupon_num +=1;
							}
						}						
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				} else {
					$int_good_total_num +=1;
					// 商品九龙金豆
                    $v['pdt_total_price'] += $v['price'];
					$int_pdt_total_price +=$v['pdt_total_price'];
					if($ary_coupon['status'] == 'success'){
						$is_use_coupon = 0;
						foreach ($ary_coupon['msg'] as $coupon){
							//计算参与优惠券使用的商品
							if($coupon['gids'] == 'All'){
								$is_use_coupon = 1;
							}else{
								if(in_array($v['g_id'],$coupon['gids'])){
									$is_use_coupon = 1;
								}
							}
						}
						if($is_use_coupon == 1){
							$int_coupon_total_money += $v ['pdt_price'] * $v ['pdt_nums'];
							$v['is_use_coupon'] = 1;
							$int_coupon_num +=1;
						}
					}
				}
			}
		}
		//当前已计算优惠券金额
		$int_exist_coupon_num = 0;
		$exist_coupon_money = 0;
		//红包
		$int_exist_bonus_num = 0;
		$exist_bonus_money = 0;
		//九龙币
		$int_exist_jlb_num = 0;
		$exist_jlb_money = 0;	
		//九龙金豆
		$int_exist_point_num = 0;
		$exist_point_money = 0;			
		//结余款
		$int_exist_balance_num = 0;
		$exist_balance_money = 0;			
		foreach ($ary_orders_goods as $k => &$v) {
			if ($v ['type'] == 3) {
				//组合商品暂时不处理
			} else {
				// 自由推荐商品
				if ($v [0] ['type'] == '4' || $v [0] ['type'] == '6') {
					foreach ($v as $key => &$item_info) {
						if($item_info['is_use_coupon'] == 1){
							if($int_exist_coupon_num+1 == $int_coupon_num){
								$item_info['oi_coupon_menoy'] = $ary_orders['o_coupon_menoy']-$exist_coupon_money;
							}else{
								$item_info['oi_coupon_menoy'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']); 
								$exist_coupon_money +=$item_info['oi_coupon_menoy'];
								$int_exist_coupon_num +=1;
							}						
						}
						//使用红包
						if(!empty($ary_orders['o_bonus_money'])){
							if($int_exist_bonus_num+1 == $int_good_total_num){
								$item_info['oi_bonus_money'] = sprintf("%.2f",$ary_orders['o_bonus_money']-$exist_bonus_money);
							}else{
								$item_info['oi_bonus_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
								$exist_bonus_money +=$item_info['oi_bonus_money'];
								$int_exist_bonus_num +=1;
							}								
						}
						//使用九龙币
						if(!empty($ary_orders['o_jlb_money'])){
							if($int_exist_jlb_num+1 == $int_good_total_num){
								$item_info['oi_jlb_money'] = sprintf("%.2f",$ary_orders['o_jlb_money']-$exist_jlb_money);
							}else{
								$item_info['oi_jlb_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
								$exist_jlb_money +=$item_info['oi_jlb_money'];
								$int_exist_jlb_num +=1;
							}								
						}
						//使用九龙金豆
						if(!empty($ary_orders['o_point_money'])){
							if($int_exist_point_num+1 == $int_good_total_num){
								$item_info['oi_point_money'] = sprintf("%.2f",$ary_orders['o_point_money']-$exist_point_money);
							}else{
								$item_info['oi_point_money'] = sprintf("%.2f", ($item_info['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
								$exist_point_money +=$item_info['oi_point_money'];
								$int_exist_point_num +=1;
							}								
						}							
					}
				}elseif($v ['type'] == '7'){
				//秒杀
				} elseif ($v ['type'] == '5') { // 团购商品
				//团购
				}elseif($v ['type'] == '8'){
				//预售
				} else {
					if($v['is_use_coupon'] == 1){
						if($int_exist_coupon_num+1 == $int_coupon_num){
							$v['oi_coupon_menoy'] = sprintf("%.2f",$ary_orders['o_coupon_menoy']-$exist_coupon_money);
						}else{
							$v['oi_coupon_menoy'] = sprintf("%.2f", ($v['pdt_total_price']/$int_coupon_total_money)*$ary_orders['o_coupon_menoy']); 
                            $exist_coupon_money +=$v['oi_coupon_menoy'];
							$int_exist_coupon_num +=1;
						}
					}
					//使用红包
					if(!empty($ary_orders['o_bonus_money'])){
						if($int_exist_bonus_num+1 == $int_good_total_num){
							$v['oi_bonus_money'] = sprintf("%.2f",$ary_orders['o_bonus_money']-$exist_bonus_money);
						}else{
							$v['oi_bonus_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_bonus_money']); 
							$exist_bonus_money +=$v['oi_bonus_money'];
							$int_exist_bonus_num +=1;
						}								
					}
					//使用九龙币
					if(!empty($ary_orders['o_jlb_money'])){
						if($int_exist_jlb_num+1 == $int_good_total_num){
							$v['oi_jlb_money'] = sprintf("%.2f",$ary_orders['o_jlb_money']-$exist_jlb_money);
						}else{
							$v['oi_jlb_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_jlb_money']); 
							$exist_jlb_money +=$v['oi_jlb_money'];
							$int_exist_jlb_num +=1;
						}								
					}
					//使用九龙金豆
					if(!empty($ary_orders['o_point_money'])){
						if($int_exist_point_num+1 == $int_good_total_num){
							$v['oi_point_money'] = sprintf("%.2f",$ary_orders['o_point_money']-$exist_point_money);
						}else{
							$v['oi_point_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_point_money']); 
							$exist_point_money +=$v['oi_point_money'];
							$int_exist_point_num +=1;
						}								
					}					
					//使用结余款
					if(!empty($ary_orders['o_pay'])){
						if($int_exist_balance_num+1 == $int_good_total_num){
							$v['oi_balance_money'] = sprintf("%.2f",$ary_orders['o_pay']-$exist_balance_money);
						}else{
							$v['oi_balance_money'] = sprintf("%.2f", ($v['pdt_total_price']/$int_pdt_total_price)*$ary_orders['o_pay']); 
							$exist_balance_money +=$v['oi_balance_money'];
							$int_exist_balance_num +=1;
						}								
					}					
				}
			}		
		}
		return $ary_orders_goods;
	}

    /**
    * 验证使用优惠券
    */
    public function CheckCoupon($csn,$cart_data,$mid){
        $mid = empty($_SESSION['Members']['m_id'])?$mid:$_SESSION['Members']['m_id'];
        $ary_csn = explode(',',$csn);
        
		$where = array('c_is_use' => 0, 'c_sn' => array('in',$ary_csn));
        $where['c_end_time'] = array('EGT', date('Y-m-d H:i:s'));
        $where['c_start_time'] = array('ELT', date('Y-m-d H:i:s'));
		$ary_coupon =M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->select();	
	   if(empty($ary_coupon) && !isset($ary_coupon)){
            return array('status'=>'error','msg'=>'优惠券编号错误或已使用或不满足使用条件');
        }
        if(count($ary_csn) != count($ary_coupon)){
            foreach ($ary_csn as $key=>$csn){
                foreach($ary_coupon as $coupon){
                    if($csn == $coupon['c_sn']){
                        unset($ary_csn[$key]);
                    }
                }
            }
            return array('status'=>'error','msg'=>'优惠券编号：'.implode('，',$ary_csn).'编号错误或已使用或不满足使用条件');
        }
		//折扣券只能使用一张
		if(count($ary_coupon)>1){
			$discount = 0;
			$moneycount = 0;
			foreach($ary_coupon as $val){
				if($val['c_type'] == '1'){
					$discount++;
				}else{
					$moneycount++;
				}
			}
		}
		if($discount>0 && $moneycount>0){
			return array('status'=>'error','msg'=>'现金券和折扣券不能同时使用');
		}
		if($discount>1){
			return array('status'=>'error','msg'=>'折扣券只能使用一张');
		}
        $i = 0;
        foreach($ary_coupon as $coupon){
			//优惠券使用者
			if(!empty($coupon['c_user_id'])){
				if($coupon['c_user_id'] != $mid){
					return array('status'=>'error','msg'=>$coupon['c_sn'].'错误或已使用或不满足使用条件');
				}			
			}
            //判断商品是否允许使用优惠券
            $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
            ->where(array('c_id'=>$coupon['c_id']))
            ->field('group_concat(gg_id) as group_id')->group('gg_id')->select();
            if(!empty($group_data)){
                //查询团购管理商品
				$gids = array();
                foreach ($group_data as $gd){
                    $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                    ->where(array('gg_id'=>array('in',$gd['group_id'])))->field('g_id')->group('g_id')->select();
                    foreach($item_data as $item){
                        foreach($cart_data as $cart){
                            if($cart['g_id'] == $item['g_id']){
								$gids[] = $item['g_id'];
                                $array_return['msg'][$i] = $coupon;
                            }
                        }
                    }
                }
				$array_return['msg'][$i]['gids'] = $gids;
                if(empty($array_return['msg'][$i])){
                    return array('status'=>'error','msg'=>'部分优惠券编号错误或已使用或不满足使用条件');
                }
            }else{
				$array_return['msg'][$i]['gids'] = 'All';
                $array_return['msg'][$i] = $coupon;
            }
            $i++;
        }
        $array_return['status'] = 'success';
		return $array_return;
    }

    /**
     * 找回密码接口
     */
    public function synReset($array_params) {
		$is_mobile = 0;
        //接收页面数据
        //判断数据是否有效
        $forget = $array_params['forget'];
		$result=D('Members')->where(array('m_email'=>$forget))->find();
		if(false == $result){
			$result=D('Members')->where(array('m_name'=>$forget))->find();
		}
		//判断是否是手机号
		if(false == $result || $result['m_email'] == ''){
			$result=D('Members')->where(array('m_mobile'=>$forget))->find();
			if(!empty($result)){
				$is_mobile = 1;
			}
		}
		if(false == $result){
			return array('status'=>FALSE,'msg'=>'用户名或邮箱或手机号不正确!');
		}
        $resMobile=D('Members')->where(array('m_mobile'=>$forget))->getField('m_mobile');
        $resName=D('Members')->where(array('m_name'=>$forget))->getField('m_name');
        if(($result['m_name'] == $resMobile || $result['m_mobile'] == $resName) && ($result['m_name'] != $result['m_mobile'])) {
			return array('status'=>FALSE,'msg'=>'尊敬的会员，您好，此手机号在两个会员中出现，请到客服中处理!');
        }
		//如果是手机验证
        $name = $result['m_name'];
        if(isset($name)){
            $ary_member['name'] = $name;
        }
        $email = $result['m_email'];
        if(isset($email)){
            $ary_member['email'] = $email;
        }
        $mobile = $result['m_mobile'];
        if(isset($mobile)){
            $ary_member['mobile'] = $mobile;
        }
        return array('status'=>TRUE,'member'=>$ary_member);
    }

    /**
     * 找回密码接口
     */
    public function findPassword($array_params = array()) {
		$is_mobile = 0;
        //判断数据是否有效
        $name = $array_params['name'];
        $email = $array_params['email'];
        $mobile = $array_params['mobile'];
		$result=M('Members')->where(array('m_email'=>$email,'m_name'=>$name))->find();
		/*if(false == $result){
			$result=D('Members')->where(array('m_name'=>$forget))->find();
        }*/
		//判断是否是手机号
		if(false == $result || $result['m_email'] == ''){
			$result=D('Members')->where(array('m_mobile'=>$mobile,'m_name'=>$name))->find();
			if(!empty($result)){
				$is_mobile = 1;
			}
		}
		//如果是手机验证
		if($is_mobile == 1){
            $m_mobile =  $result['m_mobile'];
            if(empty($m_mobile)){
                return array('status'=>FALSE,'msg'=>'手机号不能为空！');
            }
            //判读是不是手机格式
            if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){			
                return array('status'=>FALSE,'msg'=>'请输入正确的手机号格式！！');
            }
            $res_mobile = D('Members')->checkMobile($m_mobile);
            if(!$res_mobile){
                return array('status'=>FALSE,'msg'=>'手机号不存在！');
            }
            //判断手机号是否在90秒内已发送短信验证码
            $ary_sms_where = array();
            $ary_sms_where['check_status'] = array('neq',2);
            $ary_sms_where['status'] = 1;
            $ary_sms_where['sms_type'] = 1;
            $ary_sms_where['mobile'] = $m_mobile;
            $ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
            $sms_log_count = D('SmsLog')->getCount($ary_sms_where);
            if($sms_log_count>0){
                return array('status'=>FALSE,'msg'=>'90秒后才允许重新获取验证码！');
            }
            $SmsApi_obj=new SmsApi();
            //获取注册发送验证码模板
            $template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'FORGET_PASSWORD'));
            $send_content = '';
            if($template_info['status'] == true){
                $send_content = $template_info['content'];
            }
            if(empty($send_content)){
                return array('status'=>FALSE,'msg'=>'短信发送失败！');
            }
            $ary_params=array('mobile'=>$m_mobile,'','content'=>$send_content);
            $res=$SmsApi_obj->smsSend($ary_params);
             if($res['code'] == '200'){
                //日志记录下
                $ary_data = array();
                $ary_data['sms_type'] = 1;
                $ary_data['mobile'] = $result['m_mobile'];
                $ary_data['content'] = $send_content;
                $ary_data['code'] = $template_info['code'];
                $sms_res = D('SmsLog')->addSms($ary_data);
                if(!$sms_res){
                    writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
                }
                return array('status'=>TRUE,'msg'=>'短信发送成功！');
            }else{
                return array('status'=>FALSE,'msg'=>'短信发送失败，'.$res['msg']);
            }
        }	
		if(false == $result){
			return array('status'=>FALSE,'msg'=>'用户名或邮箱或手机号不正确');
		}
        if($result['m_email'] == ''){
			return array('status'=>FALSE,'msg'=>'用户尚未绑定邮箱，请尝试使用手机找回密码！');
        }

		$ary_option = D('EmailTemplates')->sendForgotPasswordEmail($result['m_password'],$result['m_name'],$result['m_email']);
		//发送邮件
        $email = new Mail();
        if ($email->sendMail($ary_option)) {
			//日志记录下
			$ary_data = array();
			$ary_data['email_type'] = 1;
			$ary_data['email'] = $result['m_email'];
			$ary_data['content'] = $ary_option['message'];
			$sms_res = D('EmailLog')->addEmail($ary_data);
			if(!$sms_res){
				writeLog(json_encode($ary_data),date('Y-m-d')."send_email.log");
			}
			return array('status'=>TRUE,'msg'=>'邮件已经发送到您的邮箱');
        } else {
			return array('status'=>FALSE,'msg'=>'重置密码邮件发送失败，请管理员检查邮件发送设置!');
        }
    }

    /**
     * 手机短信验证发送密码接口
     */
    public function resetByMobile($array_params = array()) {
		$m_mobile_code = trim($array_params['mobile_code']);
		$m_mobile = trim($array_params['mobile']);
        //判读是不是手机格式
        if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){			
            return array('status'=>FALSE,'msg'=>'输入的手机号格式不正确！！');
        }
		//判断手机号是否在90秒内已发送短信验证码
		$ary_sms_where = array();
		$ary_sms_where['check_status'] = 0;
		$ary_sms_where['status'] = 1;
		$ary_sms_where['sms_type'] = 1;
		$ary_sms_where['mobile'] = $m_mobile;
		$ary_sms_where['code'] = $m_mobile_code;
		//$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
		$sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);	
		if($sms_log['code'] != $m_mobile_code){
			return array('status'=>FALSE,'msg'=>'验证码不存在或已过期!');
		}else{
			//判断手机号是否在90秒内已重置过
			$ary_sms_where = array();
			$ary_sms_where['check_status'] = 0;
			$ary_sms_where['status'] = 1;
			$ary_sms_where['sms_type'] = 3;
			$ary_sms_where['mobile'] = $m_mobile;
			$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
			$sms_count = D('SmsLog')->getCount($ary_sms_where);
			if($sms_count>0){
                return array('status'=>FALSE,'msg'=>'您已经重置过密码了!');
			}
			M('')->startTrans(); 
			//更新验证码使用状态
			$up_res = D('SmsLog')->updateSms(array('id'=>$sms_log['id']),array('check_status'=>1));
			if(!$up_res){
				M('')->rollback();
                return array('status'=>FALSE,'msg'=>'更新验证码状态失败!');
			}
			//发送重置密码到手机
			$SmsApi_obj=new SmsApi();
			//获取注册发送验证码模板
			$template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'SEND_PASSWORD'));
			$send_content = '';
			if($template_info['status'] == true){
				$send_content = $template_info['content'];
			}
			if(empty($send_content)){
				M('')->rollback();
                return array('status'=>FALSE,'msg'=>'短信发送失败!');
			}
			$array_params=array('mobile'=>$m_mobile,'','content'=>$send_content);
			$res=$SmsApi_obj->smsSend($array_params);
			if($res['code'] == '200'){
				//日志记录下
				$ary_data = array();
				$ary_data['sms_type'] = 3;
				$ary_data['mobile'] = $m_mobile;
				$ary_data['content'] = $send_content;
				$ary_data['code'] = $template_info['code'];
				$sms_res = D('SmsLog')->addSms($ary_data);
				if(!$sms_res){
					M('')->rollback();
					//writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
                    return array('status'=>FALSE,'msg'=>'短信发送失败!');
				}
			}else{
				M('')->rollback();
                return array('status'=>FALSE,'msg'=>'短信发送失败！'.$array_result['msg']);
			}
			//重置密码之后发送短信成功后更改会员表密码信息
			$m_res = D('Members')->where(array('m_mobile'=>$m_mobile))->data(array('m_password'=>md5($template_info['code']),'m_create_time'=>date('Y-m-d H:i:s')))->save();
			if(!$m_res){
				M('')->rollback();
                return array('status'=>FALSE,'msg'=>'重置密码失败!');
			}
			//设置其他已发送验证码无效
			D('SmsLog')->updateSms(array('sms_type'=>3,'check_status'=>0,'mobile'=>$ary_member['m_mobile']),array('check_status'=>2));
			M('')->commit();
            return array('status'=>TRUE,'msg'=>'密码重置成功，您的新密码已经发送到您的手机，请尽快使用新密码登录或修改您的密码');
		}
    }


    /**
    * 同步商品分类
    * request params
    * @author Hcaijin
    * @date 2014-10-30
    */
    public function categorySyn($array_params=array()){
        $data = $this->parseFields($this->item_category,$array_params);
        if(isset($array_params['parent_cid'])){
            $parent_id = D('GoodsCategory')->where(array('thd_catid'=>$array_params['parent_cid']))->getField('gc_id');
            if(!$parent_id){
                return array('status'=>false,'msg'=>'父级分类不存在，请先新增父级分类');
            }
        }else{
            $parent_id = 0;
        }
        $data['gc_order'] = isset($data['gc_order'])?$data['gc_order']:0;
        $data['gc_is_display'] = isset($data['gc_is_display'])?$data['gc_is_display']:0;
        $data['gc_is_hot'] = isset($data['gc_is_hot'])?$data['gc_is_hot']:0;
        //验证商品分类排序字段的参数是否合法
        if (!is_numeric(trim($data['gc_order'])) || $data['gc_order'] < 0 || $data['gc_order'] % 1 != 0) {
            return array('status'=>false,'msg'=>'排序字段必须输入正整数!');
        }
        $where = array();
        $where['thd_catid'] = $data['thd_catid'];
        $rs = D('GoodsCategory')->where($where)->find();
        if(empty($rs)){
            //验证商品分类名称是否已经存在{此处规则修改：同级不重复}
            if($data['gc_type'] == "0"){
                $array_cond = array('gc_name'=>$data['gc_name'],'gc_parent_id'=>$parent_id);
                $array_result =  D("GoodsCategory")->where($array_cond)->find();
                if(is_array($array_result) && !empty($array_result)){
                    return array('status'=>false,'msg'=>'已经存在同级的商品分类' . $data['gc_name']);
                }
            }
            //数据组装
            $array_insert_data['gc_name'] = trim($data['gc_name']);
            $array_insert_data['gc_parent_id'] = $parent_id;
            $array_insert_data['gc_order'] = $data['gc_order'];
            //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
            $array_insert_data['gc_level'] = 0;
            if($array_insert_data['gc_parent_id'] > 0){
                $array_parent_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
                $int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
                $array_insert_data['gc_level'] = $int_parent_gc_level + 1;
            }
            $array_insert_data['gc_keyword'] = (isset($data['gc_keyword']) && "" != $data['gc_keyword'])?$data['gc_keyword']:"";
            $array_insert_data['gc_description'] = (isset($data['gc_description']) && "" != $data['gc_description'])?$data['gc_description']:"";
            $array_insert_data['gc_is_display'] = $data['gc_is_display'];
            $array_insert_data['gc_is_hot'] = $data['gc_is_hot'];
            $array_insert_data['gc_create_time'] = date("Y-m-d h:i:s");
            $array_insert_data['thd_catid'] = $data['thd_catid'];
            //是否启用分类属性功能
            $cateCfg = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
            if($cateCfg['GCTYPE'] == 1){
                $array_insert_data['gc_type'] = isset($data['gc_type'])?$data['gc_type']:'0';
            }
            //事务开始
            D("GoodsCategory")->startTrans();
            $mixed_result =  D("GoodsCategory")->add($array_insert_data);
            //echo D()->getlastsql();exit;
            $cid = $mixed_result;
            if(false === $mixed_result){
                D("GoodsCategory")->rollback();
                return array('status'=>false,'msg'=>'商品分类添加失败!');
            }
            //如果此分类是某个分类的子分类，则将那个父级分类的is_parent字段设置为1
            if($array_insert_data['gc_parent_id'] > 0){
                $array_modify_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
                $array_modify_data = array("gc_is_parent"=>1,"gc_update_time"=>date("Y-m-d H:i:s"));
                $mixed_result = D("GoodsCategory")->where($array_modify_cond)->save($array_modify_data);
                if(false === $mixed_result){
                    D("GoodsCategory")->rollback();
                    return array('status'=>false,'msg'=>'修改商品分类is_parent失败!');
                }
            }
            //事务提交
            D("GoodsCategory")->commit();
            return array('status'=>true,'id'=>$cid);
        }else{
            //验证上级商品分类的选择是否合法
            $int_gc_id = $rs['gc_id'];
            if($parent_id == $int_gc_id){
                return array('status'=>false,'msg'=>'商品分类的上级分类不能是分类本身!');
            }
            //验证父级分类是否是当前分类的子分类或者更下级的分类
            $array_child_catids = D("GoodsCategory")->getCategoryChildIds($int_gc_id);
            if(!empty($array_child_catids) && in_array($data['gc_parent_id'],$array_child_catids)){
                return array('status'=>false,'msg'=>'商品分类的上级分类不能是当前分类的叶子分类!');
            }
            //数据拼装
            $array_modify_data = array();
            $array_modify_data["gc_name"] = trim($data["gc_name"]);
            $array_modify_data['gc_parent_id'] = $parent_id;
            $array_modify_data['gc_order'] = $data['gc_order'];
            $array_modify_data['gc_level'] = 0;
            //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
            if($array_modify_data['gc_parent_id'] > 0){
                $array_parent_cond = array("gc_id"=>$array_modify_data['gc_parent_id']);
                $int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
                $array_modify_data['gc_level'] = $int_parent_gc_level + 1;
            }
            $array_modify_data['gc_keyword'] = (isset($data['gc_keyword']) && "" != $data['gc_keyword'])?$data['gc_keyword']:"";
            $array_modify_data['gc_description'] = (isset($data['gc_description']) && "" != $data['gc_description'])?$data['gc_description']:"";
            $array_modify_data['gc_is_display'] = $data['gc_is_display'];
            $array_modify_data['gc_is_hot'] = $data['gc_is_hot'];
            $array_modify_data['gc_update_time'] = date("Y-m-d h:i:s");
            //是否启用分类属性功能
            $cateCfg = D('SysConfig')->getCfgByModule('GY_GOODS_CATEGORY');
            if($cateCfg['GCTYPE'] == 1){
                $array_modify_data['gc_type'] = isset($data['gc_type'])?$data['gc_type']:'0';
            }
            
            //事务开始
            D("GoodsCategory")->startTrans();
            $modify_result = D("GoodsCategory")->where(array("gc_id"=>$int_gc_id))->save($array_modify_data);
            if(false === $modify_result){
                D("GoodsCategory")->rollback();
                return array('status'=>false,'msg'=>'商品分类更新失败，数据没有更新!');
            }
            //更新当前商品分类的gc_is_parent 字段
            if($array_modify_data['gc_parent_id'] > 0){
                $array_modify_cond = array("gc_id"=>$array_modify_data['gc_parent_id']);
                $array_modify_data = array("gc_is_parent"=>1,"gc_update_time"=>date("Y-m-d H:i:s"));
                $mixed_result = D("GoodsCategory")->where($array_modify_cond)->save($array_modify_data);
                if(false === $mixed_result){
                    D("GoodsCategory")->rollback();
                    return array('status'=>false,'msg'=>'修改商品分类is_parent失败!');
                }
            }
            //当前分类的孩子节点 gc_level 字段更新
            if(!empty($array_child_catids)){
                foreach($array_child_catids as $child_gc_id){
                    //获取当前子分类的上级分类ID
                    $int_parent_id = D("GoodsCategory")->where(array("gc_id"=>$child_gc_id))->getField("gc_parent_id");
                    $array_modify_data = array();
                    $array_modify_data['gc_level'] = 0;
                    if($int_parent_id > 0){
                        $array_parent_cond = array("gc_id"=>$int_parent_id);
                        $int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
                        $array_modify_data['gc_level'] = $int_parent_gc_level + 1;
                    }
                    $array_modify_data['gc_update_time'] = date("Y-m-d H:i:s");
                    $array_modify_data['gc_type'] = intval($data['gc_type']);
                    $array_modify_data['gc_is_display'] = intval($data['gc_is_display']);
                    $array_modify_data['gc_is_hot'] = intval($data['gc_is_hot']);
                    $mixed_result = D("GoodsCategory")->where(array("gc_id"=>$child_gc_id))->save($array_modify_data);
                    if(false === $mixed_result){
                        D("GoodsCategory")->rollback();
                        return array('status'=>false,'msg'=>'修改商品子分类节点失败!');
                    }
                }
            }
            //事务提交
            D("GoodsCategory")->commit();
            return array('status'=>true,'id'=>$int_gc_id);
        }
        return array('status'=>false);
    }

    /**
    * 同步商品品牌
    * request params
    * @author Hcaijin
    * @date 2014-10-30
    */
    public function brandSyn($array_params=array()){
        $data = $this->parseFields($this->item_brand,$array_params);
        $where = array();
        $where['gb_sn'] = $data['gb_sn'];
        $rs = D('GoodsBrand')->where($where)->find();
        if(empty($rs)){
            $data['gb_create_time'] = date("Y-m-d h:i:s");
            $resAdd = D('GoodsBrand')->add($data);
            if(!empty($resAdd)){
                return array('status'=>true,'bid'=>$resAdd);
            }else{
                return array('status'=>false,'msg'=>'新增品牌失败');
            }
        }else{
           $data['gb_update_time'] = date("Y-m-d H:i:s");
            $resSave = D('GoodsBrand')->where(array('gb_sn'=>$rs['gb_sn']))->save($data);
            if(!empty($resSave)){
                return array('status'=>true,'bid'=>$rs['gb_id']);
            }else{
                return array('status'=>false,'msg'=>'修改品牌失败');
            }
        }
        return array('status'=>false);
    }

    /**
    * 批量同步商品品牌
    * request params
    * @author Hcaijin
    * @date 2014-11-06
    */
    public function brandBatchSyn($array_params=array()){
        $brands = json_decode($array_params['brand_list'],1);
        if(empty($brands)){
            return array('status'=>false,'msg'=>'brand_list字段有误');
        }
        $return = array();
        foreach($brands as $bd){
            $res = $this->brandSyn($bd);
            if($res['status'] === true){
                $return[$bd['brand_sn']] = $res['bid'];
            }else{
                $msg = date('Y-m-d H:i:s').'       同步品牌代码为：'.$bd['brand_sn'].'的品牌失败，原因：'.$res['msg']."\r\n";
                $this->logs('brandBatchSyn',$msg);
            }
        }
        return array('status'=>true,'data'=>$return);
    }

    /**
    * 批量同步商品分类
    * request params
    * @author Hcaijin
    * @date 2014-11-06
    */
    public function categoryBatchSyn($array_params=array()){
        $category = json_decode($array_params['category_list'],1);
        if(empty($category)){
            return array('status'=>false,'msg'=>'category_list字段有误');
        }
        $return = array();
        foreach($category as $cat){
            $res = $this->categorySyn($cat);
            if($res['status'] === true){
                $return[$cat['cid']] = $res['id'];
            }else{
                $msg = date('Y-m-d H:i:s').'       同步分类代码为：'.$cat['cid'].'的分类失败，原因：'.$res['msg']."\r\n";
                $this->logs('categoryBatchSyn',$msg);
            }
        }
        return array('status'=>true,'data'=>$return);
    }
}

