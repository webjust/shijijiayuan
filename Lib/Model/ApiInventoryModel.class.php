<?php

/**
 * 仓库相关模型层 Model
 * @package Model
 * @version 7.2
 * @author czy
 * @date 2012-08-01
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiInventoryModel extends GyfxModel {

	/**
	 * 对象
	 * @var obj
	 */
	private $warehouse;

	private $warehouse_delivery_area;
	private $warehouse_stock;
	/**
	 * 构造方法
	 * @author czy <chenzongyao@guanyisoft.com>
	 * @date 2012-08-02
	 */
	public function __construct() {

		parent::__construct();
		$this->warehouse = M('warehouse', C('DB_PREFIX'), 'DB_CUSTOM');
		$this->warehouse_delivery_area = M('warehouse_delivery_area', C('DB_PREFIX'), 'DB_CUSTOM');
		$this->warehouse_stock = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM');
		$this->inventory_area_price = M('inventory_area_price', C('DB_PREFIX'), 'DB_CUSTOM');
	}

	/**
	 * 仓库数据获取
	 */
	private function getInventoryGetField(){
		$array_inventory_field = array(
			 'sc_item_id'=>'pdt_sn',
		     'sc_item_code'=>'pdt_sn',
		     'store_code'=>'w_code',
		     'quantity'=>'pdt_total_stock',
		 	 'occupy_quantity'=>'pdt_freeze_stock'
		);
		return $this->parseFieldsMaps($array_inventory_field,$array_client_fields);
	}

	/**
	 * 查询仓库商品数据（根据商品id)
	 * request params
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-01
	 */
	public function InventoryGet($array_params=array()) {
		//时间排序排序
		$ary_where = array();
		$ary_g_sn = array();
		$ary_sc_item_ids = explode('^',$array_params["sc_item_ids"]);
		$ary_store_codes = explode('^',$array_params["store_codes"]);
		
		if(!empty($ary_store_codes) && !empty($array_params["store_codes"])){
			//拼接where条件
			$str_where = '';
			foreach($ary_sc_item_ids as $key=>$item_id){
				if(empty($ary_store_codes[$key])){
					$str_where .= '(pdt_sn = "'.$item_id.'") or ';
				}else{
					$str_where .= '(pdt_sn = "'.$item_id.'" and w.w_code="'.$ary_store_codes[$key].'") or ';
				}
			}
			$str_where=substr($str_where,0,-3);
			$ary_where['_string'] = $str_where;
		}else{
			$ary_where['pdt_sn'] = array('in',$ary_sc_item_ids);
		}
		$str_inventory_fields = $this->getInventoryGetField();
		$array_inventory = $this->warehouse_stock
		->join('fx_warehouse as w on w.w_id = fx_warehouse_stock.w_id')
		->field($str_inventory_fields)->where($ary_where)->select();
		$inventorys = array();
		foreach($array_inventory as $key=>$val) {
			$inventorys['item_inventorys']['inventory_sum'][] = $val;
		}
		return $inventorys;
	}

	/**
	 * 新增仓库信息
	 * request params
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-01
	 */
	public function addInventoryStore($array_params) {
		//仓库存不存在，不存在新增
		//$newCode = trim($array_params['store_code']).trim($array_params['erp_id']);
		$newCode = trim($array_params['store_code']);
		$ary_lc = $this->warehouse->field('w_id,w_code')->where(array('w_code' =>$newCode))->select();
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		if (empty($ary_lc)) {
			switch ($array_params["operate_type"]) {
				case 'ADD':
					$save_data = $this->getInventoryField($array_params);
					$save_data['w_create_time'] = date("Y-m-d H:i:s");
					$save_data['w_update_time'] = date("Y-m-d H:i:s");
					//$save_data['w_code'] = $save_data['erp_code'].$save_data['erp_id'];
					$save_data['w_code'] = $save_data['w_code'];
					$save_data['erp_code'] = $save_data['w_code'];
					$w_id = $this->warehouse->add($save_data);
					if(!empty($w_id)) {
						$int_flag = true;
					}
					else $ary_data = array('msg'=>'新增仓库异常');
					break;
				case 'UPDATE':
					//返回错误信息
					$ary_data = array('msg'=>'此仓库编号对应仓库不存在，无法更新');
					break;
				case 'DELETE':
					//返回错误信息
					$ary_data = array('msg'=>'此仓库编号对应仓库不存在，无法停用');
					break;
				case 'MANAGE':
					$save_data = $this->getInventoryField($array_params);
					$save_data['w_create_time'] = date("Y-m-d H:i:s");
					$save_data['w_update_time'] = date("Y-m-d H:i:s");
					//更新状态后直接启用
					$save_data['w_out_use'] = 0;
					//$w_code = $array_params['store_code'].$array_params['erp_id'];
					$w_code = $array_params['store_code'];
					$res = $this->warehouse->where(array('w_code' => $w_code))
					->add($save_data,array(),true);
					if(false !== $res) $int_flag = true;
					else $ary_data = array('msg'=>'更新仓库信息出现异常');
					break;							
			}
		}
		else{
			switch ($array_params["operate_type"]) {
				case 'ADD':
					//返回错误信息
					$ary_data = array('msg'=>'此仓库编号对应仓库已存在，无法重复添加');
					break;
				case 'UPDATE':
					$save_data = $this->getInventoryField($array_params);

					if(isset($save_data['w_code']) && isset($save_data['erp_id'])){
						unset($save_data['w_code']);
						unset($save_data['erp_id']);
					}
					$save_data['w_update_time'] = date("Y-m-d H:i:s");
					//更新状态后直接启用
					$save_data['w_out_use'] = 0;
					//$w_code = $array_params['store_code'].$array_params['erp_id'];
					$w_code = $array_params['store_code'];
					$res = $this->warehouse->where(array('w_code' => $w_code))
					->data($save_data)
					->save();
					if(false !== $res) $int_flag = true;
					else $ary_data = array('msg'=>'更新仓库信息出现异常');
					break;
				case 'DELETE':
					$w_code = $array_params['store_code'];
					//$w_code = $array_params['store_code'].$array_params['erp_id'];
					$res = $this->warehouse->where(array('w_code' => $w_code))
					->data(array('w_out_use'=>1,'w_update_time'=>date("Y-m-d H:i:s")))
					->save();
					if(false !== $res) $int_flag = true;
					else $ary_data = array('msg'=>'停用仓库信息出现异常');
					break;
				case 'MANAGE':
					$save_data = $this->getInventoryField($array_params);
					$save_data['w_id'] = $ary_lc[0]['w_id'];
					$save_data['w_code'] = $ary_lc[0]['w_code'];
					$save_data['w_create_time'] = date("Y-m-d H:i:s");
					$save_data['w_update_time'] = date("Y-m-d H:i:s");
					//更新状态后直接启用
					$save_data['w_out_use'] = 0;
					//$w_code = $array_params['store_code'].$array_params['erp_id'];
					$w_code = $array_params['store_code'];
					$res = $this->warehouse->where(array('w_code' => $w_code))
					->add($save_data,array(),true);
					if(false !== $res) $int_flag = true;
					else $ary_data = array('msg'=>'更新仓库信息出现异常');
					break;				
			}
		}
		if($int_flag) {
			//$w_code = $array_params['store_code'].$array_params['erp_id'];
			$w_code = $array_params['store_code'];
			return $this->getInventoryResponse($w_code);
		}
		else return $ary_data;
	}

	/**
	 * 新增仓库库存信息
	 * request params
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-14
	 */
	public function addInventoryStorestock($array_params) {
		//仓库存不存在，不存在新增
		//$newCode = trim($array_params['store_code']).trim($array_params['erp_id']);
		$newCode = trim($array_params['store_code']);
		$ary_lc = $this->warehouse->field('w_id,w_code')->where(array('w_code' =>$newCode))->find();
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		if(empty($ary_lc)){
			return array('msg'=>'此仓库信息不存在请先新增仓库信息');
		}else{
			$w_id = $ary_lc['w_id'];
			$outer_id = trim($array_params["outer_id"]);
			$sku_outer_id = trim($array_params["sku_outer_id"]);
			//如果商品有规格不能只传商品编码信息
			$stock_where = array();
			$stock_where['w_id'] = $w_id;
			$stock_where['g_sn'] = $outer_id;
			if(empty($sku_outer_id)){
				$stock_where['pdt_sn'] = $outer_id;
				$sku_outer_id = $outer_id;
			}else{
				$stock_where['pdt_sn'] = $sku_outer_id;
			}
			$ary_lc_stock =  $this->warehouse_stock->field('ws_id,pdt_total_stock,pdt_stock,pdt_freeze_stock')->where($stock_where)->find();
			if (empty($ary_lc_stock)) {
				switch ($array_params["operate_type"]) {
					case 'ADD':
						$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
						$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
						if(empty($g_id) || empty($pdt_id)){
							$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
						}else{
							$save_data = $this->getInventorystockField($array_params);
							$save_data['w_id'] = $w_id;
							$save_data['pdt_sn'] = $sku_outer_id;
							$save_data['g_sn'] = $outer_id;
							$save_data['pdt_id'] = $pdt_id;
							$save_data['g_id'] = $g_id;
							$save_data['pdt_stock'] = intval($save_data['pdt_total_stock']);
							$save_data['ws_create_time'] = date("Y-m-d H:i:s");
							$save_data['ws_update_time'] = date("Y-m-d H:i:s");
							$ws_id = $this->warehouse_stock->add($save_data);
							if(!empty($ws_id)) {
								$int_flag = true;
							}
							else $ary_data = array('msg'=>'新增仓库库存异常');							
						}
						break;
					case 'UPDATE':					
						$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
						$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
						if(empty($g_id) || empty($pdt_id)){
							$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
						}else{
							M('', '', 'DB_CUSTOM')->startTrans();
							$save_data = $this->getInventorystockField($array_params);
							//库存变化1000-100  10-100
							$adjust_num = intval($save_data['pdt_total_stock'])-intval($ary_lc_stock['pdt_total_stock']);
							$ary_insert_data=array();
							$ary_insert_data['w_id'] = $w_id;
							$ary_insert_data['pdt_id'] = $pdt_id;
							$ary_insert_data['g_id'] = $g_id;
							$ary_insert_data['source'] = '1';
							$ary_insert_data['pdt_total_stock'] = intval($save_data['pdt_total_stock']);
							//冻结数量统计订单表中已付款的订单
							$ary_insert_data['pdt_freeze_stock'] = intval($ary_lc_stock['pdt_freeze_stock']);
							$ary_insert_data['create_time'] = date('Y-m-d H:i:s');
							$ary_insert_data['update_time'] = date('Y-m-d H:i:s');
							$ary_insert_data['descs'] = 'API调整库存仓库信息';
							//调整判断为新增
							if($adjust_num>0){
								$ary_insert_data['types'] = '1';
								$ary_insert_data['num'] = $adjust_num;
								$ary_insert_data['pdt_stock'] = $ary_lc_stock['pdt_stock']+$adjust_num;
							}
							//调整为减
							if($adjust_num<=0){
								$ary_insert_data['types'] = '2';
								$ary_insert_data['num'] = -$adjust_num;
								if($ary_lc_stock['pdt_stock']>$ary_insert_data['pdt_total_stock']){
									$ary_insert_data['pdt_stock'] = intval($ary_insert_data['pdt_total_stock']);
								}else{
									$ary_insert_data['pdt_stock'] = intval($ary_lc_stock['pdt_stock']);
								}
							}					
							$result = D('WarehouseStockReviseDetail')->CreateDetail($ary_insert_data); 
							//生成库存调整单
							if($result['status'] == false){
								$ary_data = array('msg'=>'生成库存调整单失败');
								return $ary_data;
							}	
							$save_data['w_id'] = $w_id;
							$save_data['pdt_sn'] = $sku_outer_id;
							$save_data['g_sn'] = $outer_id;
							$save_data['pdt_id'] = $pdt_id;
							$save_data['g_id'] = $g_id;
							$save_data['pdt_stock'] = intval($ary_insert_data['pdt_stock']);
							$save_data['ws_create_time'] = date("Y-m-d H:i:s");
							$save_data['ws_update_time'] = date("Y-m-d H:i:s");
							if(empty($save_data['pdt_stock'])){
								//M('', '', 'DB_CUSTOM')->rollback();
								$int_flag = true;
								M('', '', 'DB_CUSTOM')->commit();
								//$ary_data = array('msg'=>'库存为0不允许新增');
							}else{
								$ws_id = $this->warehouse_stock->add($save_data,array(),true);
								if(!empty($ws_id)) {
									$int_flag = true;
									M('', '', 'DB_CUSTOM')->commit();
								}
								else{
									M('', '', 'DB_CUSTOM')->rollback();
									$ary_data = array('msg'=>'新增仓库库存异常');
								}			
							} 							
						}
						break;
						//返回错误信息
//						$ary_data = array('msg'=>'此仓库库存编号对应仓库库存信息不存在，无法更新');
//						break;
					case 'DELETE':
						//返回错误信息
						$ary_data = array('msg'=>'此仓库库存编号对应仓库库存信息不存在，无法停用');
						break;
				}
			}
			else{
				switch ($array_params["operate_type"]) {
					case 'ADD':
						//返回错误信息
						$ary_data = array('msg'=>'此仓库库存编号对应仓库库存信息已存在，无法重复添加');
						break;
					case 'UPDATE':
						$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
						$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
						if(empty($g_id) || empty($pdt_id)){
							$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
						}else{
							M('', '', 'DB_CUSTOM')->startTrans();
							$save_data = $this->getInventorystockField($array_params);
							//库存变化1000-100  10-100
							$adjust_num = $save_data['pdt_total_stock']-$ary_lc_stock['pdt_total_stock'];
							$ary_insert_data=array();
							$ary_insert_data['w_id'] = $w_id;
							$ary_insert_data['pdt_id'] = $pdt_id;
							$ary_insert_data['g_id'] = $g_id;
							$ary_insert_data['source'] = '1';
							$ary_insert_data['pdt_total_stock'] = intval($save_data['pdt_total_stock']);
							//冻结数量统计订单表中已付款的订单
							$ary_insert_data['pdt_freeze_stock'] = intval($ary_lc_stock['pdt_freeze_stock']);
							
							$ary_insert_data['create_time'] = date('Y-m-d H:i:s');
							$ary_insert_data['update_time'] = date('Y-m-d H:i:s');
							$ary_insert_data['descs'] = 'API调整库存仓库信息';
							//调整判断为新增
							if($adjust_num>0){
								$ary_insert_data['types'] = '1';
								$ary_insert_data['num'] = $adjust_num;
								$ary_insert_data['pdt_stock'] = $ary_lc_stock['pdt_stock']+$adjust_num;
							}
							//调整为减
							if($adjust_num<=0){
								$ary_insert_data['types'] = '2';
								$ary_insert_data['num'] = -$adjust_num;
								if($ary_lc_stock['pdt_stock']>$ary_insert_data['pdt_total_stock']){
									$ary_insert_data['pdt_stock'] = intval($ary_insert_data['pdt_total_stock']);
								}else{
									$ary_insert_data['pdt_stock'] = intval($ary_lc_stock['pdt_stock']);
								}
							}					
							$result = D('WarehouseStockReviseDetail')->CreateDetail($ary_insert_data); 
							//生成库存调整单
							if($result['status'] == false){
								$ary_data = array('msg'=>'生成库存调整单失败');
								return $ary_data;
							}
							$save_data = array();
							$save_data['w_id'] = $w_id;
							$save_data['pdt_sn'] = $sku_outer_id;
							$save_data['g_sn'] = $outer_id;
							$save_data['pdt_id'] = $pdt_id;
							$save_data['g_id'] = $g_id;
							$save_data['pdt_total_stock'] = $array_params['num'];
							$save_data['pdt_stock'] = $ary_insert_data['pdt_stock'];
							$save_data['pdt_status'] = ($array_params['status'] == '')?1:$array_params['status'];
							$save_data['ws_update_time'] = date("Y-m-d H:i:s");
							//更新仓库库存表
							$res = $this->warehouse_stock->where($stock_where)
							->data($save_data)
							->save();
							if(false !== $res){
								$int_flag = true;M('', '', 'DB_CUSTOM')->commit();
							}else{
								M('', '', 'DB_CUSTOM')->rollback();
								$ary_data = array('msg'=>'更新仓库库存信息出现异常');
							} 
						}
						break;
					case 'DELETE':
						$res = $this->warehouse_stock->where($stock_where)
						->data(array('pdt_status'=>0,'ws_update_time'=>date('Y-m-d H:i:s')))
						->save();
						if(false !== $res) $int_flag = true;
						else $ary_data = array('msg'=>'停用仓库库存信息出现异常');
						break;
				}
			}			
		}
		if($int_flag) {
			$ary_inventorystock_response = array();
			$ary_inventorystock_response['storestock_list ']['storestock'] = array('store_code'=>$newCode,'created'=>date('Y-m-d H:i:s'));
			return array('msg'=>'','data'=>$ary_inventorystock_response);
		}
		else return $ary_data;
	}
	
	/**
	 * 新增、更新或删除区域售价信息
	 * request params
	 * @author wangguibin@guanyisoft.com
	 * @date 2014-05-14
	 */
	public function addInventoryPrice($array_params) {
		//仓库配送区域存不存在，不存在新增
		$cr_id = trim($array_params['cr_id']);
		$ary_lc = $this->warehouse_delivery_area->field('wa_id')->where(array('cr_id' =>$cr_id))->find();
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		M ( '', C ( '' ), 'DB_CUSTOM' )->startTrans ();
		if(empty($ary_lc)){
			return array('msg'=>'此仓库配送区域不存在请先新增仓库配送区域信息');
		}else{
			$outer_id = trim($array_params["outer_id"]);
			$sku_outer_id = trim($array_params["sku_outer_id"]);
			//如果商品有规格不能只传商品编码信息
			$stock_where = array();
			$stock_where['cr_id'] = $cr_id;
			$stock_where['g_sn'] = $outer_id;
			if(empty($sku_outer_id)){
				$stock_where['pdt_sn'] = $outer_id;
				$sku_outer_id = $outer_id;
			}else{
				$stock_where['pdt_sn'] = $sku_outer_id;
			}
			//全国售价调整
			if(trim($array_params['type']) == '1'){
				$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
				$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
				if(empty($g_id) || empty($pdt_id)){
					$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
				}else{
					if(empty($array_params['price'])){
						M('', '', 'DB_CUSTOM')->rollback();
						//$int_flag = true;
						//M('', '', 'DB_CUSTOM')->commit();
						$ary_data = array('msg'=>'商品全国售价为0不允许新增');
					}else{
						if($array_params["operate_type"] == 'UPDATE'){
							$gp_result = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')
							->data(array('pdt_update_time'=>date('Y-m-d H:i:s'),'pdt_sale_price'=>$array_params['price']))
							->where(array('g_id'=>$g_id,'pdt_id'=>$pdt_id))
							->save();
							if(!empty($gp_result)) {
								if($sku_outer_id == $outer_id){
									$good_obj = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')
									->data(array('g_price'=>$array_params['price'],'g_update_time'=>date('Y-m-d H:i:s')))
									->where(array('g_id'=>$g_id))
									->save();
									if(empty($good_obj)){
										M('', '', 'DB_CUSTOM')->rollback();
										$ary_data = array('msg'=>'更新全国售价主表异常');
									}
								}
								$int_flag = true;
								M('', '', 'DB_CUSTOM')->commit();	
							}
							else{
								M('', '', 'DB_CUSTOM')->rollback();
								$ary_data = array('msg'=>'更新全国售价异常');
							}							
						}else{
						
						}
		
					} 	
				}
			}else{
				$ary_lc_stock =  $this->inventory_area_price->where($stock_where)->find();
				if (empty($ary_lc_stock)) {
					switch ($array_params["operate_type"]) {
						case 'UPDATE':					
							$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
							$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
							if(empty($g_id) || empty($pdt_id)){
								$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
							}else{
								M('', '', 'DB_CUSTOM')->startTrans();
								//价格变化1000-100  10-100
								$adjust_price = $array_params['price']-$ary_lc_stock['price'];
								$ary_insert_data=array();
								$ary_insert_data['cr_id'] = $cr_id;
								$ary_insert_data['pdt_id'] = $pdt_id;
								$ary_insert_data['g_id'] = $g_id;							
								//$ary_insert_data['price'] = $ary_lc_stock['price'];
								//冻结数量统计订单表中已付款的订单
								$ary_insert_data['create_time'] = date('Y-m-d H:i:s');
								$ary_insert_data['update_time'] = date('Y-m-d H:i:s');
								//调整判断为新增
								if($adjust_price>0){
									$ary_insert_data['types'] = '1';
									$ary_insert_data['desc'] = 'API新增区域价格信息,金额为：'.$adjust_price;
								}
								$result = D('InventoryPriceReviseDetail')->data($ary_insert_data)->add(); 
								//生成库存调整单
								if($result == false){
									$ary_data = array('msg'=>'生成价格调整单失败');
									return $ary_data;
								}	
								$save_data = array();
								$save_data['cr_id'] = $cr_id;
								$save_data['pdt_sn'] = $sku_outer_id;
								$save_data['g_sn'] = $outer_id;
								$save_data['pdt_id'] = $pdt_id;
								$save_data['g_id'] = $g_id;
								$save_data['price'] = $array_params['price'];
								if(empty($save_data['price'])){
									M('', '', 'DB_CUSTOM')->rollback();
									//$int_flag = true;
									//M('', '', 'DB_CUSTOM')->commit();
									$ary_data = array('msg'=>'区域价格为0不允许新增');
								}else{
									$ap_id = $this->inventory_area_price->add($save_data,array(),true);
									if(!empty($ap_id)) {
										$int_flag = true;
										M('', '', 'DB_CUSTOM')->commit();
									}
									else{
										M('', '', 'DB_CUSTOM')->rollback();
										$ary_data = array('msg'=>'新增区域价格异常');
									}			
								} 							
							}
							break;
							//返回错误信息
	//						$ary_data = array('msg'=>'此仓库库存编号对应仓库库存信息不存在，无法更新');
	//						break;
						case 'DELETE':
							//返回错误信息
							$ary_data = array('msg'=>'此区域当前商品对应区域价格信息不存在，无法停用');
							break;
					}
				}
				else{
					switch ($array_params["operate_type"]) {
						case 'UPDATE':
							$g_id = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_sn'=>$outer_id,'g_status'=>1))->getField('g_id');
							$pdt_id = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->getField('pdt_id');
							if(empty($g_id) || empty($pdt_id)){
								$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
							}else{
								M('', '', 'DB_CUSTOM')->startTrans();
								//价格变化1000-100  10-100
								$adjust_price = $array_params['price']-$ary_lc_stock['price'];
								$ary_insert_data = array();
								$ary_insert_data['cr_id'] = $cr_id;
								$ary_insert_data['pdt_id'] = $pdt_id;
								$ary_insert_data['g_id'] = $g_id;							
								//$ary_insert_data['price'] = $ary_lc_stock['price'];
								//冻结数量统计订单表中已付款的订单
								$ary_insert_data['create_time'] = date('Y-m-d H:i:s');
								$ary_insert_data['update_time'] = date('Y-m-d H:i:s');
								
								//调整判断为新增
								if($adjust_num>0){
									$ary_insert_data['types'] = '1';
									$ary_insert_data['desc'] = 'API调整区域价格信息,调整金额为：'.$adjust_price;
								}
								//调整为减
								if($adjust_num<=0){
									$ary_insert_data['types'] = '2';
									$adjust_price = -$adjust_price;
									$ary_insert_data['desc'] = 'API调整区域价格信息,调整金额为：'.$adjust_price;
								}
								$result = D('InventoryPriceReviseDetail')->data($ary_insert_data)->add(); 
								//生成库存调整单
								if($result == false){
									$ary_data = array('msg'=>'生成价格调整单失败');
									return $ary_data;
								}
								
								$save_data = array();
								$save_data['cr_id'] = $cr_id;
								$save_data['pdt_sn'] = $sku_outer_id;
								$save_data['g_sn'] = $outer_id;
								$save_data['pdt_id'] = $pdt_id;
								$save_data['g_id'] = $g_id;
								$save_data['price'] = trim($array_params['price']);
								if(empty($save_data['price'])){
									M('', '', 'DB_CUSTOM')->rollback();
									//$int_flag = true;
									//M('', '', 'DB_CUSTOM')->commit();
									$ary_data = array('msg'=>'区域价格为0不允许新增');
								}else{
									$ap_id = $this->inventory_area_price->add($save_data,array(),true);
									if(!empty($ap_id)) {
										$int_flag = true;
										M('', '', 'DB_CUSTOM')->commit();
									}
									else{
										M('', '', 'DB_CUSTOM')->rollback();
										$ary_data = array('msg'=>'更新区域价格异常');
									}			
								} 	
							}
							break;
						case 'DELETE':
							$res = $this->inventory_area_price->where($stock_where)->delete();
							if(false !== $res){
								$int_flag = true;
								M('', '', 'DB_CUSTOM')->commit();
							}else{
								 $ary_data = array('msg'=>'删除区域价格信息出现异常');
								 M('', '', 'DB_CUSTOM')->rollback();
							}
							break;
					}
				}			
						
			}
		}
		if($int_flag) {
			$ary_inventorystock_response = array();
			$ary_inventorystock_response['inventory_price_list ']['inventory_price'] = array('cr_id'=>$cr_id,'update_time'=>date('Y-m-d H:i:s'),'outer_id'=>$stock_where['g_sn'],'sku_outer_id'=>$stock_where['pdt_sn']);
			return array('msg'=>'','data'=>$ary_inventorystock_response);
		}
		else return $ary_data;
	}
	
	
	/**
	 * 批量新增仓库库存信息
	 * request params
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-10-17
	 */
	public function addInventoryStorestocks($array_params) {
		//仓库存不存在，不存在新增
		//$newCode = trim($array_params['store_code']).trim($array_params['erp_id']);
		$newCode = trim($array_params['store_code']);
		$ary_lc = $this->warehouse->field('w_id,w_code,erp_id')->where(array('w_code' =>$newCode))->find();
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		if(empty($ary_lc)){
			return array('msg'=>'此仓库信息不存在请先新增仓库信息');
		}else{
			$items = json_decode($array_params['items'],1);
			if(empty($items)){
				return array('msg'=>'items字段有误');
			}
			$w_id = $ary_lc['w_id'];
			//事物开始
			$obj_trans = M ( '', C ( '' ), 'DB_CUSTOM' );
			$obj_trans->startTrans ();
			$ary_inventorystock = array();
			foreach($items as $item){
				$sku_outer_id = trim($item["scItemId"]);
				//如果商品有规格不能只传商品编码信息
				$stock_where = array();
				$stock_where['w_id'] = $w_id;
				$stock_where['pdt_sn'] = $sku_outer_id;
				$ary_lc_stock =  $this->warehouse_stock->field('ws_id')->where($stock_where)->find();
				if(!empty($ary_lc_stock)){
					//返回错误信息
					$ary_data = array('msg'=>'此仓库库存编号对应商品规格的库存信息已存在，无法重复添加，商品编号为'.$sku_outer_id);
					return $ary_data;
				}else{
					$item_info = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->field('g_id,pdt_id,g_sn')->find();
					$g_id = $item_info['g_id'];
					$pdt_id = $item_info['pdt_id'];
					$g_sn = $item_info['g_sn'];
					if(empty($g_id) || empty($pdt_id)){
						$ary_data = array('msg'=>'此商品或商品规格不存在,不能新增仓库信息');
					}else{
						if(intval($item['quantity'])>0){
							$save_data = array();
							$save_data['w_id'] = $w_id;
							$save_data['g_id'] = $g_id;
							$save_data['pdt_id'] = $pdt_id;
							$save_data['g_sn'] = $item_info['g_sn'];
							$save_data['pdt_sn'] = $sku_outer_id;
							$save_data['pdt_total_stock'] = $item['quantity'];
							$save_data['pdt_stock'] = $item['quantity'];
							$save_data['erp_id'] = $ary_lc['erp_id'];
							$save_data['erp_code'] = trim($array_params['store_code']);
							$save_data['ws_create_time'] = strtotime(date("Y-m-d H:i:s"));
							$save_data['ws_update_time'] = strtotime(date("Y-m-d H:i:s"));
							$ws_id = $this->warehouse_stock->add($save_data);
							if(!empty($ws_id)) {
								$ary_inventorystock[] = array('sc_item_id'=>$sku_outer_id,'info'=>'库存初始化成功');
								$int_flag = true;
							}
							else {
								$obj_trans->rollback ();$ary_data = array('msg'=>'新增仓库库存异常');return $ary_data;
							}		
						}
					}
				}
			}
		}
		$obj_trans->commit ();
		if($int_flag) {
			$ary_inventorystock_response = array();
			$ary_inventorystock_response['tip_infos ']['tip_info'] = $ary_inventorystock;
			return $ary_inventorystock_response;
		}
		else return $ary_data;
	}
	/**
	 * 批量调整仓库库存信息
	 * request params
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-10-17
	 */
	public function modifyInventoryStorestocks($array_params) {
		//仓库存不存在，不存在新增
		//$newCode = trim($array_params['store_code']).trim($array_params['erp_id']);
		$newCode = trim($array_params['store_code']);
		$ary_lc = $this->warehouse->field('w_id,w_code')->where(array('w_code' =>$newCode))->find();
		$int_flag = false;
		$ary_data = array('msg'=>'','data'=>array());
		if(empty($ary_lc)){
			return array('msg'=>'此仓库信息不存在请先新增仓库信息');
		}else{
			$items = json_decode($array_params['items'],1);
			$w_id = $ary_lc['w_id'];
			//事物开始
			$obj_trans = M ( '', C ( '' ), 'DB_CUSTOM' );
			$obj_trans->startTrans ();
			$ary_inventorystock = array();
			foreach($items as $item){
				$sku_outer_id = trim($item["scItemId"]);
				//如果商品有规格不能只传商品编码信息
				$stock_where = array();
				$stock_where['w_id'] = $w_id;
				$stock_where['pdt_sn'] = $sku_outer_id;
				$ary_lc_stock =  $this->warehouse_stock->field('ws_id,ws_id,pdt_total_stock,pdt_freeze_stock')->where($stock_where)->find();
				if(empty($ary_lc_stock)){
					//返回错误信息
					$ary_data = array('msg'=>'此商品仓库库存记录不存在，请先初始化库存，商品编号为'.$sku_outer_id);
					return $ary_data;
				}else{
					$item_info = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_sn'=>$sku_outer_id,'pdt_status'=>1))->field('g_id,pdt_id,g_sn')->find();
					$g_id = $item_info['g_id'];
					$pdt_id = $item_info['pdt_id'];
					$g_sn = $item_info['g_sn'];
					if(empty($g_id) || empty($pdt_id)){
						$ary_data = array('msg'=>'此商品或商品规格已不存在,不能修改仓库信息');
						return $ary_data;
					}else{
						if(empty($item['direction'])){
							$ary_data = array('msg'=>'缺少参数direction');
							return $ary_data;
						}
						$save_data = array();
						if($item['direction'] == '-1'){
							$save_data['pdt_total_stock'] = $ary_lc_stock['pdt_total_stock'] - $item['quantity'];
							$save_data['pdt_stock'] = $save_data['pdt_total_stock']-$ary_lc_stock['pdt_freeze_stock'];
						}else{
							$save_data['pdt_total_stock'] = $ary_lc_stock['pdt_total_stock'] + $item['quantity'];
							$save_data['pdt_stock'] = $save_data['pdt_total_stock']-$ary_lc_stock['pdt_freeze_stock'];
						}
						if($item['quantity'] == '0'){
							$ary_data = array('msg'=>'库存调整异常,您的库存调整数量不正确');
							return $ary_data;
						}
						$save_data['ws_update_time'] = strtotime($array_params['operate_time']);
						$ws_id = $this->warehouse_stock->data($save_data)->where(array('ws_id'=>$ary_lc_stock['ws_id']))->save();
						if(!empty($ws_id)) {
							$ary_inventorystock[] = array('sc_item_id'=>$sku_outer_id,'info'=>'库存调整成功');
							$int_flag = true;
						}
						else {
							$obj_trans->rollback ();
							$ary_data = array('msg'=>'库存调整异常');
							return $ary_data;
						}
					}
				}
			}
		}
		$obj_trans->commit ();
		if($int_flag) {
			$ary_inventorystock_response = array();
			$ary_inventorystock_response['tip_infos ']['tip_info'] = $ary_inventorystock;
			return $ary_inventorystock_response;
		}
		else return $ary_data;
	}
	
	/**
	 * 组装仓库数据
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-01
	 */
	private function getInventoryResponse($w_code) {
		$str_field = $this->getInventoryField('','str');
		$ary_response = $this->warehouse->field($str_field)->where(array('w_code' =>$w_code))->select();
		$ary_inventory_response = array();
		if(!empty($ary_response)) {
			$ary_inventory_response['storestock_response ']['store'] = $ary_response;
			return array('msg'=>'','data'=>$ary_inventory_response);
		}
		else return array('msg'=>'返回仓库信息出现异常');
	}

	/**
	 *仓库覆盖区域管理
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-02
	 */
	public function addInventoryDeliveryarea($array_params = array(),&$msg) {
		//仓库存不存在，不存在新增
		//获取仓库代码
		$ary_lc = $this->warehouse->field('w_id,w_code')->where(array('w_code' =>trim($array_params['store_code'])))->find();
		$int_flag = false;
		$ary_data = false;
		if (!empty($ary_lc)) {
			switch ($array_params["sc_type"]) {
				case 'ADD':
					$save_data = $this->getInventoryDeliveryareaField($array_params);
					$ary_lc_area = $this->warehouse_delivery_area->where(array('w_id'=>$ary_lc['w_id'],'cr_id'=>$save_data['cr_id']))->find();
					if(empty($ary_lc_area)){
						$save_data['erp_code'] = $ary_lc['w_code'];
						$save_data['w_id'] = $ary_lc['w_id'];
						$ws_id = $this->warehouse_delivery_area->add($save_data,array(),true);
						if(!empty($ws_id)) $ary_data = true;
						else $msg = '新增仓库覆盖区域异常';
					}else{
						$save_data['erp_code'] = $ary_lc['w_code'];
						$save_data['w_id'] = $ary_lc['w_id'];
						$ws_id = $this->warehouse_delivery_area->add($save_data,array(),true);
						//dump($this->warehouse_delivery_area->getLastSql());die();
						if(!empty($ws_id)) $ary_data = true;
						else $msg = '更新仓库覆盖区域异常';			
						//$msg = '此仓库覆盖区域已存在';
					}
					break;
				case 'DELETE': 
					$ary_where = array('w_id' => $ary_lc['w_id'],'cr_id'=>$array_params['area_code']);
					$res = $this->warehouse_delivery_area->where($ary_where)->delete();
					if($res>0) $ary_data = true;
					elseif($res == 0) $msg = '没有对应仓库覆盖区被删除';
					else $msg = '删除仓库覆盖区域异常';
					break;
			}
		}
		else $msg = '对应仓库编码不存在';
		return $ary_data;
	}

	/**
	 * 仓库数据映射获取
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-02
	 */
	private function getInventoryField($array_client_fields = array(),$type = 'ary'){
		$array_inventory_field = array(
			 'store_code'=>'w_code',
			 'erp_id'=>'erp_id',
			 'store_name'=>'w_name',
		     'alias_name'=>'w_alias_name',
		     'address'=>'w_address',
		     'address_area_name'=>'w_address_area_name',
			 'contact'=>'w_contact',
			 'phone'=>'w_phone',
             'postcode'=>'w_postcode'
             );
             switch ($type) {
             	case 'str':
             		return $this->parseFieldsMaps($array_inventory_field);
             		break;
             	case 'ary':
             		return $this->parseFields($array_inventory_field,$array_client_fields);
             		break;
             }
	}
	
	/**
	 * 仓库数据映射获取
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-14
	 */
	private function getInventorystockField($array_client_fields = array(),$type = 'ary'){
		$array_inventory_field = array(
			 'store_code'=>'erp_code',
			 'erp_id'=>'erp_id',
			 'outer_id'=>'g_sn',
		     'sku_outer_id'=>'pdt_sn',
		     'num'=>'pdt_total_stock',
		     'status'=>'pdt_status'
             );
             switch ($type) {
             	case 'str':
             		return $this->parseFieldsMaps($array_inventory_field);
             		break;
             	case 'ary':
             		return $this->parseFields($array_inventory_field,$array_client_fields);
             		break;
             }
	}
	
	/**
	 * 仓库数据映射获取
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-02
	 * return mixed
	 */
	private function getInventoryDeliveryareaField($array_client_fields = array(),$type = 'ary'){
		$array_inventory_field = array(
			 'store_code'=>'w_code',
			 'area_code'=>'cr_id'
			 );
			 switch ($type) {
			 	case 'str':
			 		return $this->parseFieldsMaps($array_inventory_field);
			 		break;
			 	case 'ary':
			 		return $this->parseFields($array_inventory_field,$array_client_fields);
			 		break;
			 }
	}



	/**
	 * 处理字段映射
	 * @author chenzongyao@guanyisoft.com
	 * @date 2013-08-02
	 * return string
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
	
}
