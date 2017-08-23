<?php

/**
 * 淘宝上传库存控制器
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.4.5
 * @author czy <chenzongyao@guanyisoft.com>
 * @date 2012-10-30
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class UploadStockAction extends CommonAction {

    /**
     * 控制器初始化方法
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 控制器默认页，默认跳转到淘宝库存上传页面
     * @auther czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     */
    public function index() {
        $this->redirect(U('Ucenter/UploadStock/showItemsTop'));
    }
	
    
	/**
     * 淘宝库存上传管理页面
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     */
	public function showItemsTop() {
       $this->getSubNav(2, 1,200);
	   $m_id = D("ThdTopItems")->getMemberId();
       $ary_where=array('m_id'=>$m_id,'ts_source'=>1);
       $ary_shop = D("ThdShops")->getThdShop($ary_where,'ts_id,ts_title',$ary_order);
       $this->assign('shops',$ary_shop);
       $this->display();
	}

	/**
     * 京东库存上传管理页面
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-09-17
     */
	public function showItemsJd() {
       $this->getSubNav(2, 1,43);
	   $m_id = D("ThdTopItems")->getMemberId();
       $ary_where=array('m_id'=>$m_id,'ts_source'=>3);
       $ary_shop = D("ThdShops")->getThdShop($ary_where,'ts_id,ts_title',$ary_order);
       $this->assign('shops',$ary_shop);
       $this->display();
	}
 
    /**
     * 下载京东商品ajax
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-09-17
     */
	public function showItemsJdAjax() {
		@set_time_limit(0);  
        @ignore_user_abort(TRUE);
        $ary_post = $this->_post();
		$obj_thd_order_items = D();
        $obj_top_items = D("ThdTopItems");
		$obj_shops = D("ThdShops");
		$obj_goodsSpec = D("GoodsSpec");
		$arr_where = array();
		$arr_where['ts_source'] = '3';
		$arr_where['ts_id'] = $ary_post['shopID'];
		$arr_result = $obj_shops->where($arr_where)->field('ts_nick')->find();
		if(!empty($arr_result)){
			$m_id = D("ThdTopItems")->getMemberId();
			$page_no = isset($_POST['p']) ? (int) $_POST['p'] : 1;
			$ary_goods_data = array();
			$ary_where = array('it_nick'=>$arr_result['ts_nick'],'g_id'=>array('neq',0));
			$int_count = D("ThdTopItems")->where($ary_where)->count();
			$page_size = 100;
			$obj_page = new Page($int_count, $page_size);
			$page = ceil($int_count/$page_size)+1;
			$this->assign('page', $page);
			$this->assign('p', $page_no);
			$tmp_list = D("ThdTopItems")->where($ary_where)->order('num_iid')->limit(($page_no-1)*$page_size . ',' . $page_size)->select();
			$sku_list = array();
			foreach($tmp_list as $sub_tmp){
				$sku_list[$sub_tmp['g_id']][] = $sub_tmp;
			}
			unset($tmp_list);
			$list = array();
			######根据用户的在架商品在本地进行查找,匹配到有商家编码的#####################
			foreach ($sku_list as $k => $v) {
				$list[$k]['base'] = D('GoodsInfo')->where(array('g_id'=>$v[0]['g_id']))->field('g_name as title,g_picture as g_picture')->find();
				$list[$k]['base']['outer_id'] = $v[0]['num_iid'];
				$list[$k]['base']['num_iid'] = $v[0]['num_iid'];
				$list[$k]['base']['g_picture'] = D('QnPic')->picToQn($list[$k]['base']['g_picture']);
				foreach($v as $sub_v){
					$res_data = array(
						'hasSku'=>1,
						'hasRelated'=>1,
						'num_iid'=>$sub_v['num_iid'],
						'sku_id'=>$sub_v['sku_id'],
						'pdt_sn'=>$sub_v['sku_id'],
						'pdt_spec'=>$obj_goodsSpec->getProductsSpec($sub_v['pdt_id']),
						'outer_id'=>$sub_v['sku_id'],
						'subTitle'=>$sub_v['spec_name']
					); 
				   $res_data["pdt_stock"] = D("GoodsStock")->getProductStockByPdtid($sub_v["pdt_id"],$m_id);
				   if($res_data["pdt_stock"]<0){
						$res_data["pdt_stock"] = 0;
					}
					$list[$k]['list'][] =  $res_data;
				}
			 }
		  }
		$this->assign('list',$list);
		$search['cates'] =D("ViewGoods")->getCates();//弹出框分类选择
		$this->assign("search",$search);
		layout(false);
		echo $this->fetch('jd_page_ajax');
		exit;
    }
	
    /**
     * 下载淘宝商品ajax
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     */
	public function showItemsTopAjax() {
		@set_time_limit(0);  
        @ignore_user_abort(TRUE);
        $ary_post = $this->_post();
      
        $obj_top_items = D("ThdTopItems");
        $obj_thd_shops = D("ThdShops");
        $m_id = D("ThdTopItems")->getMemberId();
      
        $ary_return = array();
        $ary_where = array('ts_id'=>$ary_post['shopID'],'ts_source'=>1);
		$access_token = $obj_thd_shops->getAccessToken($ary_where,'ts_shop_token,ts_nick',$ary_return);//返回淘宝api对象
	
        if(empty($access_token)) {
            return false;
        }
		$app = new TaobaoApi($access_token);//api对象
    
        $res_data = array();
        ######根据用户信息获取用户店铺内的在架商品##################################
        $page_no = 1;
        $list = array();
        //do{
			$page_no = isset($_POST['p']) ? (int) $_POST['p'] : 1;
            $app_data = $app->getCurrentUserOnSaleGoods(array('page_no'=>$page_no,'fields'=>'num_iid,title,outer_id,num'));
            //$page_no ++ ;
			
            //$list = array_merge($list,$app_data['data']);
			$list = $app_data['data'];
			
			
			//这里一次只能获取40条，需要根据页数，分批获取
			foreach ($list as $k => $v) {
				$list[$k]['info'] = $app->getSingleGoodsInfo($v['num_iid'],'num_iid,title,sku');
				
			}
		   // writeLog(var_export($list,1),'thd_top2.log');
			$goods_data = array();
			$goods_data['total_num'] = intval($app_data['num']);//总记录数
			$page_size =40;
			$obj_page = new Page($goods_data['total_num'], $page_size);
			$page = ceil($app_data['num']/40)+1;
			$this->assign('page', $page);
			$this->assign('p', $page_no);
			
			$obj_goodsSpec = D("GoodsSpec");
        
			######根据用户的在架商品在本地进行查找,匹配到有商家编码的#####################
			foreach ($list as $k => $v) {
				$int_good_id = '';
				if (!empty($v['outer_id']) && !isset($v['info']['data']['skus'])) {
					//continue;//无规格商品，跳出本次循环
					$is_exists = $obj_top_items->where(array('it_nick'=>$ary_return['ts_nick'],'num_iid'=>$v['num_iid']))->find();
              
					//如果商品本身有商家编码的，并且没有货品sku信息的。判断为单规格商品
					//根据商家编码去货品表查找价格和库存
					if(isset($is_exists['g_id'])  && !empty($is_exists['g_id']))  {
						list($status, $store,$g_id,$pdt_id,$pdt_sn) = $obj_top_items->findByGId($is_exists['g_id']);
					}else {
						list($status, $store,$g_id,$pdt_id,$pdt_sn) = $obj_top_items->findByOuterId($v['outer_id']);
					}

					$res_base = array(
                        'hasSku'=>false,
                        'title'=>$v['title'],
                        'num_iid'=>$v['num_iid'],
                        'outer_id'=>$v['outer_id'],
                    );
					
					$res_data = array(
						'hasSku'=>0,
						'hasRelated'=>1,
						'num_iid'=>$v['num_iid'],
						'sku_id'=>0,
						'pdt_sn'=>'',
						'pdt_spec'=>'',
						'outer_id'=>$v['outer_id'],
						'subTitle'=>''
					 );
              
					if ($status) {
						$res_data['hasRelated'] = 1;
						$res_data['pdt_sn'] = $pdt_sn;
						$res_data['pdt_spec'] = $obj_goodsSpec->getProductsSpec($pdt_id);
						//获取图片路径
						if($g_id) {
							$res_base['g_picture'] = $obj_top_items->findImgByGId($g_id);
						}
					} else {
						//分销系统并未找到该件商品，不做处理
						$res_data['hasRelated'] = 0;
					}
					if(empty($is_exists)) {
						$ary_good_data = array();
						$ary_good_data['it_nick'] = $ary_return['ts_nick'];
						$ary_good_data['g_id'] = !empty($g_id) ? $g_id: 0;
						$ary_good_data['num_iid'] = $v['num_iid'];
						$ary_good_data['pdt_id'] = !empty($pdt_id) ? $pdt_id: 0;
						$ary_good_data['sku_id'] = 0;
						$mixed_result = $obj_top_items->add($ary_good_data); 
						if(!$mixed_result){
							//插入记录未成功
				            @writeLog(date('Y-m-d').json_encode($ary_good_data).'执行未成功','item_top.log');
                            continue;
						}
                
					}else{
						$ary_good_data = array();
						$ary_good_data['g_id'] = !empty($g_id) ? $g_id: 0;
						$ary_good_data['pdt_id'] = !empty($pdt_id) ? $pdt_id: 0;
                    
						$mixed_result = $obj_top_items->where(array('it_id'=>$is_exists['it_id']))->data($ary_good_data)->save(); 
						//echo $obj_top_items->getLastSql();exit;
						if($mixed_result === false){
							//更新记录未成功
				            @writeLog(date('Y-m-d').json_encode($ary_good_data).'执行未成功','item_top.log');
                            continue;
						}  
					}
                
					$goods_data['data'][$k]['base'] = $res_base; 
					$goods_data['data'][$k]['list'][] = $res_data;
				} elseif (is_array($v['info']['data']['skus'])) {
					$res_base = array(
                        'hasSku'=>1,
                        'title'=>$v['title'],
                        'num_iid'=>$v['num_iid'],
                        'outer_id'=>$v['outer_id']
                      
                    );
					$goods_data['data'][$k]['base'] =  $res_base;
					$res_data = array();
               
					//如果有sku信息，根据sku的数组再做判断
					foreach ($v['info']['data']['skus']['sku'] as $sku) {
						//有商家编码的货品，去本地根据商家编码查找价格和库存
						if (!empty($sku['outer_id'])) {
							$ary_sku_data = array();
							$ary_sku_data['it_nick'] = $ary_return['ts_nick'];
							$ary_sku_data['num_iid'] = $v['num_iid'];
							$ary_sku_data['sku_id'] = $sku['sku_id'];
							$is_exists = $obj_top_items->where($ary_sku_data)->find();
                     
                        
							if(isset($is_exists['pdt_id'])  && !empty($is_exists['pdt_id'])){
								list($status, $store,$g_id,$pdt_id,$pdt_sn) = $obj_top_items->findByPdtId($is_exists['pdt_id']);
							}else{
								list($status, $store,$g_id,$pdt_id,$pdt_sn) = $obj_top_items->findByOuterId($v['outer_id'],$sku['outer_id']);
							} 
							if ($status) {
								$res_data[] = array(
									'hasSku'=>1,
									'hasRelated'=>1,
									'title'=>$v['title'],
									'outer_id'=>$sku['outer_id'],
									'subTitle'=>$obj_top_items->filterSubTitle($sku['properties_name']),//淘宝规格号
                               
									'num_iid'=>$v['num_iid'],//淘宝numiid
									'sku_id'=>$sku['sku_id'],//淘宝skuid
									'pdt_sn'=>$pdt_sn,//
									'pdt_spec'=> $obj_goodsSpec->getProductsSpec($pdt_id)
                               
								);
								$int_good_id = $g_id;
							}else {
								$res_data[] = array(
									'hasSku'=>1,
									'hasRelated'=>0,
									'title'=>$v['title'],
									'outer_id'=>$sku['outer_id'],
									'subTitle'=>$obj_top_items->filterSubTitle($sku['properties_name']),
									'num_iid'=>$v['num_iid'],//淘宝numiid
									'sku_id'=>$sku['sku_id'],//淘宝skuid
									'pdt_sn'=>'',//
									'pdt_spec'=>'',// 
								);
								//分销系统并未找到该件商品，不做处理
							}
							if(empty($is_exists)) {
								//插入第三方表
								$ary_insert_data = array();
								$ary_insert_data['it_nick'] = $ary_return['ts_nick'];
                            
								$ary_insert_data['g_id'] = !empty($g_id) ? $g_id:0;
								$ary_insert_data['num_iid'] = $v['num_iid'];
                          
								$ary_insert_data['pdt_id'] = !empty($pdt_id) ? $pdt_id:0;
								$ary_insert_data['sku_id'] = $sku['sku_id'];
		                  
								$mixed_result = $obj_top_items->add($ary_insert_data); 
								if(!$mixed_result){
									//插入记录未成功
									@writeLog(date('Y-m-d').json_encode($ary_insert_data).'执行未成功','item_top.log');
									continue;
								}
							}else{
								$ary_update_data = array();
								$ary_update_data['g_id'] = !empty($g_id) ? $g_id: 0;
								$ary_update_data['pdt_id'] = !empty($pdt_id) ? $pdt_id: 0;
                    
								$mixed_result = $obj_top_items->where(array('it_id'=>$is_exists['it_id']))->data($ary_update_data)->save(); 
                   
								if($mixed_result == false){
									//更新记录未成功
									@writeLog(date('Y-m-d').json_encode($ary_update_data).'执行未成功','item_top.log');
									continue;
								}  
							}
                        
						}else{
							//没有商家编码的货品，不做处理
						}
					}
                
                
					if($res_data) {
						$goods_data['data'][$k]['list'] = $res_data;
						//获取图片路径
						if($int_good_id) {
							$goods_data['data'][$k]['base']['g_picture'] = $obj_top_items->findImgByGId($int_good_id);
						}
					}
				}
			}
        
			unset($list);
			//unset($goods_data);
			$this->assign('list',$goods_data);
			$search['cates'] =D("ViewGoods")->getCates();//弹出框分类选择
			$this->assign("search",$search);
			layout(false);
			echo $this->fetch('top_page_ajax');
        //}while ($page_no <= ceil($app_data['num']/40));
		exit;
    }
    
    /**
     * 同步全部sku或者单个sku京东库存
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-09-18
    */
    public function doSynStockJd() {
		@set_time_limit(0);  
        @ignore_user_abort(TRUE);
        $obj_top_items = D("ThdTopItems");
        $obj_thd_shops = D("ThdShops");
        $ary_post = $this->_post();
        $ary_return = array();
        $ary_where = array('ts_id'=>$ary_post['shopID'],'ts_source'=>3);
		$arr_result = $obj_thd_shops->where($ary_where)->find();
        $ary_token = json_decode($arr_result['ts_shop_token'], true);
        $obj_api = Apis::factory('jd', $ary_token);
        $ary_where = array();
		$ary_where['g_id'] = array('neq',0);
		if(isset($ary_post['type']) && $ary_post['type'] == 'single') {
            
            if(!empty($ary_post['sku_id'])) $ary_where = array('sku_id'=>$ary_post['sku_id']); 
            $ary_where['it_nick'] = $arr_result['ts_nick'];
            $ary_where['num_iid'] = $ary_post['num_iid'];
            
        }
        else $ary_where['it_nick'] = $arr_result['ts_nick'];
		
		if($ary_post['num_iids'] != "" ){
			$ary_where['num_iid']= array('in',$ary_post['num_iids']);
		}
        $item_top_data = $obj_top_items->where($ary_where)->select();
        $ary_pdt_ids = array();
        $ary_pdt_ids_info = array();
        foreach($item_top_data as $val) {
           if(!empty($val['pdt_id']))  $ary_pdt_ids[] = $val['pdt_id'];
        }
        if(empty($ary_pdt_ids)) {
            echo json_encode(array('msg'=>'商品没有关联分销规格', 'status'=>0));exit;
        }

        $obj_goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_pdt_ids_info = $obj_goods_products->field('g_id,pdt_id,g_sn,pdt_stock')->where(array('pdt_id'=>array('in',implode(",", $ary_pdt_ids))))->select();
        $ary_pdt_ids_temp = array();
        foreach($ary_pdt_ids_info as $val){
            if($val['pdt_stock']>=0){
                $ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']] = array('g_sn'=>$val['g_sn'],'pdt_stock'=>intval($val['pdt_stock']));
            }
        }
        //$app = new TaobaoApi($access_token);//淘宝接口api对象
        $success_num = 0;$fail_num = 0;$success_sku_num = 0;$fail_sku_num = 0;
        $error_info = array();
        foreach($item_top_data as $tkey=>$val) {
            if(!empty($val['g_id']) && !empty($val['pdt_id']) && isset($ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']])) {
				$item_top_data[$tkey]['pdt_stock'] = $ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']]['pdt_stock'];				
			}
       }
      if(!empty($item_top_data)){
          foreach($item_top_data as $k=>$v) {
			$res = $obj_api->updateQuantitySkus($v);
			if($res['status']) {
				$success_num++;
				$success_sku_num ++;
				unset($ary_skuid[$k]);
			}
			else  {
				$fail_num++;
				$error_info[] = "商品编码{$v['num_iid']},规格编码是{$v['sku_id']}".$res['err_msg'];
			}
          }
      }
      unset($item_top_data);
      if($fail_num>0) $msg = "原因是".implode('   ',$error_info);
      else $msg = '京东库存上传成功';
      echo json_encode(array('status'=>1,'success'=>$success_num,'fail'=>$fail_num,'success_sku_num'=>$success_sku_num,'msg'=>$msg));exit;
    }    
    
 
    /**
     * 同步全部sku或者单个sku淘宝库存
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
    */
    public function doSynStock() {
		@set_time_limit(0);  
        @ignore_user_abort(TRUE);
        $obj_top_items = D("ThdTopItems");
        $obj_thd_shops = D("ThdShops");
        $ary_post = $this->_post();
        $ary_return = array();
        $ary_where = array('ts_id'=>$ary_post['shopID'],'ts_source'=>1);
        $access_token = $obj_thd_shops->getAccessToken($ary_where,'ts_shop_token,ts_nick',$ary_return);//返回淘宝用户访问token
       
        $ary_where = array();
		if(isset($ary_post['type']) && $ary_post['type'] == 'single') {
            
            if(!empty($ary_post['sku_id'])) $ary_where = array('sku_id'=>$ary_post['sku_id']); 
            $ary_where['it_nick'] = $ary_return['ts_nick'];
            $ary_where['num_iid'] = $ary_post['num_iid'];
            
        }
        else $ary_where['it_nick'] = $ary_return['ts_nick'];
		
		if($ary_post['num_iids'] != "" ){
			$ary_where['num_iid']= array('in',$ary_post['num_iids']);
		}
        
        $item_top_data = $obj_top_items->where($ary_where)->select();
        $ary_pdt_ids = array();
        $ary_pdt_ids_info = array();
        foreach($item_top_data as $val) {
           if(!empty($val['pdt_id']))  $ary_pdt_ids[] = $val['pdt_id'];
        }
      
        if(empty($ary_pdt_ids)) {
            echo json_encode(array('msg'=>'商品没有关联分销规格', 'status'=>0));exit;
        }
        
      
        $obj_goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_pdt_ids_info = $obj_goods_products->field('g_id,pdt_id,g_sn,pdt_stock')->where(array('pdt_id'=>array('in',implode(",", $ary_pdt_ids))))->select();
       
        $ary_pdt_ids_temp = array();
        foreach($ary_pdt_ids_info as $val){
            if($val['pdt_stock']>=0){
                $ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']] = array('g_sn'=>$val['g_sn'],'pdt_stock'=>intval($val['pdt_stock']));
            }
        }
        
     
        $app = new TaobaoApi($access_token);//淘宝接口api对象
        $success_num = 0;$fail_num = 0;$success_sku_num = 0;$fail_sku_num = 0;
        $error_info = array();
        $ary_skuid = array();
        $ary_skuid_goods = array();
      
        foreach($item_top_data as $tkey=>$val) {
            if(!empty($val['g_id']) && !empty($val['pdt_id']) && isset($ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']])) {
            //没有sku
            if(empty($val['sku_id'])){
                ##################更新单商品无规格########################################
                $data = array(
                    'num_iid'=>$val['num_iid'],
                    'num'=>$ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']]['pdt_stock'] //货品库存数量
                );
                
                $res = $app->updateItem($data);//我改
				
                if($res['status']) {
                     $success_num++;
                     $success_sku_num++;
					 unset($item_top_data[$tkey]);
                }
                else  {
                    $fail_num++;
                    $error_info[] = $res['err_msg'];
                    @writeLog(date('Y-m-d H:i:s').json_encode($data).' '.$res['err_msg'], 'syn_skus_num.log');
                }
              
            }else{
                $ary_skuid[$val['num_iid']][] = $val['sku_id'].':'.$ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']]['pdt_stock'];
                $ary_skuid_goods[$val['num_iid']] = $ary_pdt_ids_temp[$val['g_id']][$val['pdt_id']]['g_sn'];
            }
       }
      }
     
      if(!empty($ary_skuid)){
          foreach($ary_skuid as $k=>$v) {
                 if(count($v)<=20){
                    $data = array(
                        'num_iid'=>$k,
                        'type'=>1,
                        'skuid_quantities'=>trim(implode(';',$v),';')
                    
                    );
                    $res = $app->updateQuantitySkus($data);
                    if($res['status']) {
                        $success_num++;
                  
                        if(isset($res['data']['skus']['sku']))   $success_sku_num +=intval(count($res['data']['skus']['sku']));
						unset($ary_skuid[$k]);
                    }
                    else  {
                        $fail_num++;
                        $error_info[] = "商品编码是{$ary_skuid_goods[$k]}".$res['err_msg'];
                    }
                 }
                 else {
                   $ary_split_s = array_chunk($v,20);
				   $j=0;
                   foreach($ary_split_s as $val) {
					   $j++;
                       $data = array(
                          'num_iid'=>$k,
                          'type'=>1,
                          'skuid_quantities'=>trim(implode(';',$val),';')
                    
                       );
                       $res = $app->updateQuantitySkus($data);
                       if($res['status']) {
						   if($j== 1 ){
							    $success_num++;
						   }
                          if(isset($res['data']['skus']['sku']))   $success_sku_num +=intval(count($res['data']['skus']['sku']));
						  unset($ary_skuid[$k]);
                       }
                       else  {
                           $fail_num++;
                           $error_info[] = "商品编码是{$ary_skuid_goods[$k]}".$res['err_msg'];
                       } 
                   }
                    
                 }
          }
        
      }
      unset($ary_skuid);unset($ary_skuid_goods);
      if($fail_num>0) $msg = "原因是".implode('   ',$error_info);
      else $msg = '淘宝库存上传成功';
      echo json_encode(array('status'=>1,'success'=>$success_num,'fail'=>$fail_num,'success_sku_num'=>$success_sku_num,'msg'=>$msg));exit;
    }  
    
    
    
    /**
     * ajax关联商品规格
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
    */
    public function relateGoodsAjax(){
         layout(false);
         $ary_post = $this->_post();
         $g_id = $ary_post['g_id']; 
         $pdt_id = $ary_post['pdt_id'];
         $num_iid = $ary_post['num_iid']; 
         $sku_id = $ary_post['sku_id'];  
         
         $ary_sku_data = array();
         $ary_sku_data['g_id'] = $g_id;
         $ary_sku_data['pdt_id'] = $pdt_id;
      
         $obj_goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM');
         $is_exists = $obj_goods_products->where(array('g_id'=>$g_id,'pdt_id'=>$pdt_id))->field('pdt_id,pdt_sn')->find();
         $obj_top_items = D("ThdTopItems");
         if(!$is_exists) {
            echo json_encode(array('status'=>0,'msg'=>'没有找到此规格'));exit;
         }
         else {
            $obj_goodsSpec	= D('GoodsSpec');
            $g_picture = $obj_top_items->findImgByGId($g_id);
            $ary_return = array('pdt_sn'=>$is_exists['pdt_sn'],'pdt_spec'=> $obj_goodsSpec->getProductsSpec($is_exists['pdt_id']),'g_picture'=>$g_picture);
         }
         
         /**********************************************/
    
         /*
         $count = $obj_top_items->where(array('g_id'=>$g_id,'pdt_id'=>$pdt_id))->count();
        
         if(($result = intval($count))>0) {
                echo json_encode(array('status'=>0,'msg'=>'此规格已于其它淘宝编号关联，请选择其它规格'));exit;
         }*/
         
         /**********************************************/
        
         $ary_update_where = array();
         if(!empty($sku_id)) {
            $ary_update_where = array("num_iid"=>$num_iid, "sku_id"=>$sku_id);
         }
         else {
          
            $ary_update_where = array("num_iid"=>$num_iid);
         }
        
        $ary_res = $obj_top_items->where($ary_update_where)->data(array("g_id"=>$g_id, "pdt_id"=>$pdt_id))->save();
        if($ary_res === false){
             @writeLog(date('Y-m-d').json_encode($ary_update_data).'|'.$e->getMessage(),'item_top.log');
             echo json_encode(array('status'=>0,'msg'=>'更新失败'));exit;             
        }
        else {
             echo json_encode(array('status'=>1,'msg'=>'更新成功','info'=>$ary_return));exit;
		}
    }
    
    /**
     * 商品弹出窗
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     */
    public function getProductsInfo(){
        $products = D("GoodsProductsTable");
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['g_name'] = $this->_get('g_name', 'htmlspecialchars,trim', '');
        $chose['pdt_sn'] = $this->_get('pdt_sn', 'htmlspecialchars,trim', '');
        $chose['g_sn'] = $this->_get('g_sn', 'htmlspecialchars,trim', '');
        $chose['gcid'] = $this->_get('gs_gcid', 'htmlspecialchars,trim', '');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        //商品分类搜索
        if ($chose['gcid']) {
            $where['rgc.gc_id'] = array('in', D("ViewGoods")->getCatesIds($chose['gcid']));
        }
        //商品名称查询
        if ($chose['g_name']) {
            $where['gi.g_name'] = array('LIKE', '%' . $chose['g_name'] . '%');
        }
        //货品编码查询
        if ($chose['pdt_sn']) {
            $where['fx_goods_products.pdt_sn'] = array('LIKE', '%' . $chose['pdt_sn'] . '%');
        }
        
        //商品编码查询
        if ($chose['g_sn']) {
            $where['fx_goods_products.g_sn'] = array('LIKE', '%' . $chose['g_sn'] . '%');
        }
        
        //$where['fx_goods_products.pdt_stock'] = array('GT',0);
        $where['fx_goods_products.pdt_is_combination_goods'] = 0;
        
        
       
        //设置页面的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $search['cates'] = D("ViewGoods")->getCates();
        if ($chose['gcid']) {
            $count = $products->where($where)
                          ->join('fx_related_goods_category as rgc on(rgc.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods as g on(g.g_id=gi.g_id)')
                          ->count();               
        }else{
            $count = $products->where($where)
                          ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                          ->join('fx_goods as g on(g.g_id=gi.g_id)')
                          ->count();
        }   
        $Page = new Page($count, 20);
        $data['page'] = $Page->show();
        
        $field=array('gi.g_id','gi.g_name','fx_goods_products.pdt_sn','fx_goods_products.g_sn','fx_goods_products.pdt_id');
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        if ($chose['gcid']) {
            $array_products = $products->field($field)->where($where)
                                            ->join('fx_related_goods_category as rgc on(rgc.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods as g on(g.g_id=gi.g_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }else{
            $array_products = $products->field($field)->where($where)
                                            ->join('fx_goods_info as gi on(gi.g_id=fx_goods_products.g_id)')
                                            ->join('fx_goods as g on(g.g_id=gi.g_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }
        
        $obj_GoodsSpec = D("GoodsSpec");
        
        foreach ($array_products as $key=>$val){
            $goodscate = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where['g.g_id'] = $val['g_id'];
            $category = $goodscate->where($array_where)
                            ->field(array('gc.gc_name'))
                            ->join('fx_goods as g on(g.g_id=fx_related_goods_category.g_id)')
                            ->join('fx_goods_category as gc on(gc.gc_id=fx_related_goods_category.gc_id)')
                            ->select();
            foreach($category as $c_v){
                $str_tmp_cate .= $c_v['gc_name'].",";
            }
            $array_products[$key]['gc_name'] = rtrim(trim($str_tmp_cate,','));
            $array_products[$key]['pdt_spec'] = $obj_GoodsSpec->getProductsSpec($val['pdt_id']);
        }
        
        $data['goods_list'] = $array_products;
        $this->assign('search', $search); //查询条件
        $this->assign('chose', $chose);  //当前已经选择的
        $this->assign($data);    //赋值数据集，和分页
        $this->display();
    }
   

    /**
     * 一键清除关联关系
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-11-11
     */
    public function  clearItem(){
	   
		$obj_thd_shops = D("ThdShops");
		$obj_top_items = D("ThdTopItems");
		$ary_post = $this->_post();
        $ary_return = array();
        $ary_where = array('ts_id'=>$ary_post['shopID'],'ts_source'=>1);
		$obj_thd_shops->getAccessToken($ary_where,'ts_shop_token,ts_nick',$ary_return);//返回淘宝用户访问token
		if(!isset($ary_return['ts_nick']) || empty($ary_return['ts_nick'])){
		     echo json_encode(array('status'=>0,'msg'=>'没有此店铺对应的淘宝账号'));exit;
		}
		
		$ary_update_where = array("g_id"=>0, "pdt_id"=>0);
		$ary_res = $obj_top_items->where(array("it_nick"=>$ary_return['ts_nick']))->data($ary_update_where)->save();
        //echo $obj_top_items->getLastSql();exit;
        if($ary_res === false){
             @writeLog(date('Y-m-d').var_export($ary_res,1).'|清除失败','item_top_clear.log');
             echo json_encode(array('status'=>0,'msg'=>'清除出现异常'));exit;             
        }
        else {
             echo json_encode(array('status'=>1,'msg'=>'清除成功,总共清除了'.intval($ary_res).'条记录'));exit;
		}
	}
}
