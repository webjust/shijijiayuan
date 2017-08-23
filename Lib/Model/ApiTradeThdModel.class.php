<?php

/**
 * 订单退换货单Model
 * @package Model
 * @version 7.2
 * @author czy
 * @date 2012-08-01
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiTradeThdModel extends GyfxModel {

    /**
     * 对象
     * @var obj
     */
    //private $orders_refunds;
	
	private $orders_refunds;
    private $orders_refunds_items;
    
    private $array_inventory_field = array(
             'or_id'=>'or_id',
             'pay_codes'=>'o_id',
             'm_id'=>'m_id',
			 'outer_refundid'=>'or_return_sn',
			 
		     'refund_type'=>'or_refund_type',
		     'outer_tid'=>'o_id',
             
             'pay_datatime'=>'or_t_sent',
		     'pay_account'=>'or_account',
             
             'buyer_memo'=>'or_buyer_memo',
		     'pay_money'=>'or_money',
             'payee'=>'or_payee',
		     'logic_sn'=>'or_return_logic_sn',
             
             'seller_memo'=>'or_seller_memo',
		  
             'apply_time'=>'or_create_time',
		     'service_verify'=>'or_service_verify',
             
             'finance_verify'=>'or_finance_verify',
		     'finance_name'=>'or_finance_u_name',
             'finance_time'=>'or_finance_time',
		     'status'=>'or_processing_status',
             
             'refuse_reason'=>'or_refuse_reason',
		     'bank'=>'or_bank',
             'received_time'=>'or_t_received',
		     'pay_type'=>'or_pay_type_id',
             
             'created'=>'or_create_time',
		     'modified'=>'or_update_time',
             'voucher'=>'or_picture',
		 );
    
    /*插入主表数据*/
    private $ary_add_field = array(
             'm_id'=>'m_id',
             'refund_type'=>'or_refund_type',
             'outer_tid'=>'o_id',
			 'outer_refundid'=>'or_return_sn',
			 'outer_tid'=>'o_id',
             
             'pay_datatime'=>'or_t_sent',
		     'pay_account'=>'or_account',
             
             'buyer_memo'=>'or_buyer_memo',
		     'pay_money'=>'or_money',
             'payee'=>'or_payee',
		     'logic_sn'=>'or_return_logic_sn',
             'seller_memo'=>'or_seller_memo',
		     'apply_time'=>'or_create_time',
         );
         
         private $ary_update_field = array(
             'outer_refundid'=>'or_return_sn',
             'm_id'=>'m_id',
             'refund_type'=>'or_refund_type',
             'outer_tid'=>'o_id',
			 
			 'outer_tid'=>'o_id',
             
             'pay_datatime'=>'or_t_sent',
		     'pay_account'=>'or_account',
             
             'buyer_memo'=>'or_buyer_memo',
		     'pay_money'=>'or_money',
             'payee'=>'or_payee',
		     'logic_sn'=>'or_return_logic_sn',
             
             'seller_memo'=>'or_seller_memo',
		     
             'apply_time'=>'or_update_time',
             
		     'service_verify'=>'or_service_verify',
             'service_name'=>'u_name',
             'service_time'=>'or_service_time',
             'finance_verify'=>'or_finance_verify',
		     'finance_name'=>'or_finance_u_name',
             'finance_time'=>'or_finance_time',
		     'status'=>'or_processing_status',
             
             'refuse_reason'=>'or_refuse_reason',
		     'bank'=>'or_bank',
             'received_time'=>'or_t_received',
		     'pay_type'=>'or_pay_type_id',
             
             
         );
    /**
     * 构造方法
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2012-08-02
     */
    public function __construct() {
	
        parent::__construct();
		$this->orders_refunds = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM');
        $this->orders_refunds_items = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->orders_refunds = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM');
        
    }
    
	/**
	 * 退换货单数据获取
	 */
	private function getTradeThdGetField($array_client_fields = array()){
		
        return $this->parseFieldsMaps($this->array_inventory_field,$array_client_fields);
    }
    
    /**
     * 查询退换货单商品数据（根据商品id)
     * request params
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-01
     */
    public function TradeThdGet($array_params=array(),$_field) {
	    //print_r($_field);exit;
        //测试数据
        //时间排序排序
		$ary_where = array();
		$ary_g_sn = array();
        $ary_orderby = '';
        if (!isset($array_params["page_no"]) || (!empty($array_params["page_no"]) && !is_numeric($array_params["page_no"]))) {
            $array_params["page_no"] = 1;
		}
		
		if (!isset($array_params["page_size"]) || (!empty($array_params["page_no"]) && !is_numeric($array_params["page_size"]))) {
		    $array_params["page_size"] = 20;
        }
        
        if (isset($array_params["orderby"]) && !empty($array_params["orderby"]) && in_array(strtolower($array_params["orderby"]),array('created','modified'))) {
            
			if (isset($array_params["orderbytype"]) && !empty($array_params["orderbytype"]) && in_array(strtolower($array_params["orderbytype"]),array('asc','desc'))) {
            
		        $ary_orderby = $this->array_inventory_field[$array_params["orderby"]].' '.$array_params["orderbytype"];
            }else{
                $ary_orderby = $this->array_inventory_field[$array_params["orderby"]].' ASC';
            }
        }
        else {
                $ary_orderby = 'or_id';
        }
        
		//退款单退货单搜索
		if(!empty($array_params['erp_id'])){
			$ary_where['erp_id'] = $array_params['erp_id'];
		}
		$str_inventory_fields = $this->getTradeThdGetField($_field);
        
		
        $array_inventory = $this->orders_refunds
                          ->field($str_inventory_fields)
		                  //->join('fx_members as m on m.m_id = fx_orders_refunds.m_id')
						  ->where($ary_where)
                          ->limit(($array_params["page_no"] - 1) * $array_params["page_size"], $array_params["page_size"])
                          ->order($ary_orderby)
                          ->select();

       $count = $this->orders_refunds
                        ->field($str_inventory_fields)
		                  ->join('fx_members as m on m.m_id = fx_orders_refunds.m_id')
						  ->where($ary_where)
                          ->count();
        //print_r($array_inventory);exit;   
        /*得到退换货单明细数据*/
        $ary_orids = array();
        $ary_mids = array();
        $ary_oids = array();
        foreach ($array_inventory as $inventory) {
		       $ary_orids[] = $inventory['or_id'];
               $ary_mids[] = $inventory['m_id'];
               $ary_oids[] = $inventory['pay_codes'];
        }
        
        $ary_refundmx_temp = array();
        $ary_refund_temp = array();
        $ary_returns_temp = array();
        $ary_members_temp = array();
        
        if(count($ary_mids)>0) {
             $ary_where = array("m_id"=>array("IN",array_filter(array_unique($ary_mids))));
             $ary_member_str = 'm_id,m_email';
             if(isset($array_params['fields']) &&  in_array('apply_name',$_field)) $ary_member_str.= ',m_name';
	         $ary_refunds_members = D('Members')->field($ary_member_str)->where($ary_where)->select();
             foreach($ary_refunds_members as $val){
                  $ary_members_temp[$val['m_id']]['m_email'] = $val['m_email'];
			      if(isset($val['m_name'])) $ary_members_temp[$val['m_id']]['m_name'] = $val['m_name'];
                  
                 
             }
        }
        
        $ary_order_temp = array();
        if(in_array('pay_codes',$_field) && count($ary_oids)>0) {
             
             $ary_where = array("o_id"=>array("IN",array_filter(array_unique($ary_oids))));
            
             $ary_order_str = 'o_id,o_payment'; 
	         $ary_refunds_orders = D('Orders')->field($ary_order_str)->where($ary_where)->select();
             $ary_order_temp = array();
             foreach($ary_refunds_orders as $val){
                   $ary_order_temp[$val['o_id']] = $val['o_payment'];
             }
            
             $ary_payment_cfg = D('PaymentCfg')->field('pc_id,pc_pay_type')->select();
             $ary_pay_temp = array();
             foreach($ary_payment_cfg as $val){
                   $ary_pay_temp[$val['pc_id']] = $val['pc_pay_type'];
             }
          
             foreach($ary_order_temp as $k=>$v) {
                if(isset($ary_pay_temp[$v])) $ary_order_temp[$k] = $ary_pay_temp[$v];
                else $ary_order_temp[$k] = '';
             }
            
             
        }
      
        
		if(count($ary_orids)>0) {
		      $ary_where = array("or_id"=>array("IN",array_filter(array_unique($ary_orids))));
		      $ary_refunds_items = D('OrdersRefundsItems')->field('*')->where($ary_where)->select();
              
              $ary_str = 'oi_id,g_sn,pdt_sale_price,oi_price';
              if(in_array('skusns',$_field)) $ary_str.= ',pdt_sn';
			  foreach($ary_refunds_items as $key=>$val){
			      $ary_refundmx_temp[$val['or_id']][$key]['nums'] = $val['ori_num'];
                  $oi_id = $val['oi_id'];
                
                  if($oi_id>0) {
		              $ary_where = array("oi_id"=>$oi_id);
                      
		              $ary_goods_items = D('OrdersItems')->field($ary_str)->where($ary_where)->find();
			          
                      if(!empty($ary_goods_items)) {
			             $ary_refundmx_temp[$val['or_id']][$key]['itemsns'] = $ary_goods_items['g_sn'];
                         if(isset($ary_goods_items['pdt_sn']) && trim($ary_goods_items['g_sn']) != trim($ary_goods_items['pdt_sn']))  $ary_refundmx_temp[$val['or_id']][$key]['skusns'] = $ary_goods_items['pdt_sn'];
                         $ary_refundmx_temp[$val['or_id']][$key]['pay_moneys'] = $ary_goods_items['oi_price'];
                         $ary_refundmx_temp[$val['or_id']][$key]['prices'] = $ary_goods_items['pdt_sale_price'];
			          }
			       }
                  
			  }
		}
      
        /*******************************************************/
       
        $inventorys = array();
        foreach($array_inventory as &$val) {
		          
		            if(isset($ary_refundmx_temp[$val['or_id']])){
                        $items_info = array();
		                foreach($ary_refundmx_temp[$val['or_id']] as $v) {
		                  $val['items']['item'][] = $v;
		                }
		            
					}
                  
                    
                    if(in_array('pay_codes',$_field)) {
                        if(isset($ary_order_temp[$val['pay_codes']])) $val['pay_codes'] = $ary_order_temp[$val['pay_codes']];
                        else $val['pay_codes'] = '';
                    }
                   
                    
                    if(isset($ary_members_temp[$val['m_id']])) {
                        $val['mail'] = $ary_members_temp[$val['m_id']]['m_email'];
                        if(isset($ary_members_temp[$val['m_id']]['m_name'])) $val['apply_name'] = $ary_members_temp[$val['m_id']]['m_name'];
                        else $val['apply_name'] = '';
                    }
                    else $val['mail'] = '';
                    unset($val['or_id']);unset($val['m_id']);
         }
        
        $inventorys['refund_list']['refund'] = $array_inventory;
        $inventorys['total_results'] = $count;
        unset($ary_returns_temp);unset($array_inventory);unset($ary_refundmx_temp);unset($ary_members_temp);
		return $inventorys;
    }
    
    /**/
    public function addTradeThd($array_params,$operate_type) {
        
        $str_flag =  $this->_validate($array_params,$return_data);
        if(!$str_flag) return array('msg'=>$return_data['msg']);//返回错误信息
        //退换货单存不存在，不存在新增
		$lc = $this->orders_refunds->field('or_id')->where(array('or_return_sn' =>trim($array_params['outer_refundid'])))->find();
	
        $flag = false;
		$data = array('msg'=>'','data'=>array());
        if (empty($lc)) {
                switch ($operate_type) {
                case 'ADD':
                    $array_params['m_id'] = $return_data['extra_data']['m_id'];
                     
                    if(array_key_exists('pay_money',$array_params) && is_numeric($array_params['pay_money']) && $array_params['pay_money']<$return_data['extra_data']['or_money']) 
                    {
                        $array_params['pay_money'] = sprintf('%.2f',$array_params['pay_money']);
                    }
                    else $array_params['pay_money'] = sprintf('%.2f',$return_data['extra_data']['or_money']);
                    $array_params['apply_time'] = date('Y-m-d H:i:s');
                    $save_data = $this->getAddThdField($array_params,$operate_type);
				
                    $this->orders_refunds->startTrans();
					$or_id = $this->orders_refunds->add($save_data);
					if(!empty($or_id)) {
                          
                          if(isset($return_data['data']) && !empty($return_data['data'])){
                                foreach($return_data['data'] as &$val) {
                                     //$ary_refunds_items[] = 
                                     $val['or_id'] = $or_id;
                                }
                                //批量插明细表
                                $int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($return_data['data']);
                                if(false == $int_return_refunds_itmes) {
                                    $this->orders_refunds->rollback();
                                    
                                }
                                else {
                                    $this->orders_refunds->commit();
                                    $flag = true;
                                }
                                
                          }
                          else {
                               $this->orders_refunds->commit();
                               $flag = true;
                          }
					   
                    }
					else $data = array('msg'=>'新增退换货单异常');
					break;
                case 'UPDATE':
                    //返回错误信息
					$data = array('msg'=>'此退换货单编号对应退换货单不存在，无法更新');
                    break;
				
               }
           
            
            
        }
        else{
           switch ($operate_type) {
		      case 'ADD':
                    //返回错误信息
					$data = array('msg'=>'此退换货单编号对应退换货单已存在，无法重复添加');
                    break; 
              case 'UPDATE':
               $array_params['m_id'] = $return_data['extra_data']['m_id'];
                
               if(array_key_exists('pay_money',$array_params) && is_numeric($array_params['pay_money'])) 
               {
                    $array_params['pay_money'] = sprintf('%.2f',$array_params['pay_money']);
               }
              
               $array_params['apply_time'] = date('Y-m-d H:i:s');
              
               $save_data = $this->getAddThdField($array_params,$operate_type);
			   //print_r($lc);exit; 
			   $this->orders_refunds->startTrans();
               $res = $this->orders_refunds->where(array('or_return_sn' => $array_params['outer_refundid']))
                    ->data($save_data)
                    ->save();
				     if(false != $res){
				         if(isset($return_data['data']) && !empty($return_data['data'])){
                                foreach($return_data['data'] as &$val) {
                                     //$ary_refunds_items[] = 
                                     $val['or_id'] = $lc['or_id'];
                                }
                              
                                $res_del = D('OrdersRefundsItems')->where(array('or_id' => $lc['or_id']))->delete();
                                //批量插明细表
                                $int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($return_data['data']);
                             
                                if(false == $int_return_refunds_itmes) {
                                    $this->orders_refunds->rollback();
                                    
                                }
                                else {
                                    $this->orders_refunds->commit();
                                    $flag = true;
                                }
                                
                          }
                          else {
                               $this->orders_refunds->commit();
                               $flag = true;
                          }
                        
                     }
					 else $data = array('msg'=>'更新退换货单信息出现异常');
				     break;
              
           } 
        }
       
		if($flag) {
		    return $this->getTradeThdResponse($array_params['outer_refundid'],$operate_type);
		}
		else return $data;
	    
    }
    /*验证会员和订单信息*/
    private function _validate($array_params,&$message = array()) {
            $ary_items = array();
            $or_money = 0;//退款退货总金额
            $condition['m_name'] = trim($array_params['mail']);
            //$condition['m_email'] = trim($array_params['mail']);
           // $condition['_logic'] = 'OR';
            $member_lc = D('Members')->field('m_id')->where($condition)->find();
           
            if(isset($member_lc['m_id']) && !empty($member_lc['m_id'])) {
               
                   $ary_where = array(
                                'o_id' =>trim($array_params['outer_tid']),
                                'm_id' =>$member_lc['m_id'],
                              );
                   $order_lc = D('Orders')->field('o_id')->where($ary_where)->limit(1)->find();
                
                if(isset($order_lc['o_id']) && !empty($order_lc['o_id'])) {
                     //退货
                    
                     if($array_params['refund_type']==2) {
                     // $orderitems_lc = D('OrdersItems')->field('o_id')->where(array('o_sn' =>trim($array_params['outer_tid'])))->limit(1)->find();
                        $ary_orders_items = D('OrdersItems')->field('oi_id,o_id,pdt_id,oi_price,oi_nums,g_sn')->where(array('o_id'=>$order_lc['o_id']))->select();
                        $ary_temp_items = array();
                        $ary_itemsns = explode(',',$array_params['itemsns']);
                        $ary_nums = explode(',',$array_params['nums']);
                        
                        if(count($ary_itemsns) != count($ary_nums)) {
                            $message['msg'] = '退货时商品编号和商品数量对应的个数不一致';
                            return false;
                        }
                        if(array_key_exists('skusns',$array_params) && !empty($array_params['skusns'])) {
                            $ary_skusns = explode(',',$array_params['skusns']);
                            if(count($ary_itemsns) != count($ary_skusns)) {
                                $message['msg'] = '退货时商品编号和商品规格编号对应的个数不一致';
                                return false;
                            }
                        }
                        
                        if(array_key_exists('skusns',$array_params) && !empty($array_params['skusns'])) {
                            
		                    foreach($ary_orders_items as $val){
			                   $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_id'] = $val['oi_id'];
                               $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_nums'] = intval($val['oi_nums']);
                               $ary_temp_items[$val['g_sn']][$val['pdt_sn']]['oi_price'] = $val['oi_price'];
                            }
                            
                            $ary_skusns = explode(',',$array_params['skusns']);
                            
                            foreach($ary_itemsns as $key=>$val) {
                                if(isset($ary_temp_items[$val][$ary_skusns[$key]])) {
                                    if(intval($ary_nums[$key]>$ary_temp_items[$val][$ary_skusns[$key]]['oi_nums']))  $str_num = $ary_temp_items[$val][$ary_skusns[$key]]['oi_nums'];
                                    else  $str_num = $ary_nums[$key];
                                    
                                         $ary_items[] = array(
                                                         'oi_id'=>$ary_temp_items[$val][$ary_skusns[$key]]['oi_id'],
                                                         'ori_num'=>$str_num,
                                                         'o_id'=>$order_lc['o_id']
                                                         );
                                    $or_money +=  $str_num*$ary_temp_items[$val][$ary_skusns[$key]]['oi_price']/$ary_temp_items[$val][$ary_skusns[$key]]['oi_nums'];     
                                }
                                else {
                                    $message['msg'] = "要退货的订单中没有此商品代码{$val},规格代码{$ary_skusns[$key]}对应的商品";
                                    return false;
                                }
                            }
                        }
                        else {
                            foreach($ary_orders_items as $val){
			                   $ary_temp_items[$val['g_sn']]['oi_id'] = $val['oi_id'];
                               $ary_temp_items[$val['g_sn']]['oi_nums'] = $val['oi_nums'];
                               $ary_temp_items[$val['g_sn']]['oi_price'] = $val['oi_price'];
		                    }
                           $ary_skusns = explode(',',$array_params['skusns']);
                            
                            foreach($ary_itemsns as $key=>$val) {
                                if(isset($ary_temp_items[$val])) {
                                     if(intval($ary_nums[$key]>$ary_temp_items[$val]['oi_nums']))  $str_num = $ary_temp_items[$val]['oi_nums'];
                                     else  $str_num = $ary_nums[$key];
                                    $ary_items[] = array(
                                                         'oi_id'=>$ary_temp_items[$val]['oi_id'],
                                                         'ori_num'=>$str_num,
                                                         'o_id'=>$order_lc['o_id']
                                                      );
                                    $or_money +=  $str_num*$ary_temp_items[$val]['oi_price']/$ary_temp_items[$val]['oi_nums'];     
                                }
                                else {
                                    $message['msg'] = "要退货的订单中没有此商品代码{$val}对应的商品";
                                    return false;
                                }
                            }
                            
                        }
                    }
                     //退款
                     else {
                        $ary_orders_items = D('OrdersItems')->field('oi_id,o_id,pdt_id,oi_price,oi_nums,g_sn')->where(array('o_id'=>$order_lc['o_id']))->select();
                        foreach($ary_orders_items as $val) {
                            $ary_items[] = array(
                                                         'oi_id'=>$val['oi_id'],
                                                         'ori_num'=>$val['oi_nums'],
                                                         'o_id'=>$order_lc['o_id']
                                              );
                               $or_money +=  $val['oi_price'];
                        }
                        
                     }
                }
                else {
                    $message['msg'] = '参数订单号和会员代码对应系统订单不存在';
                    return false;
                }
            }
            else {
                $message['msg'] = '此会员不存在';
                return false;
            }
            $message['data'] = $ary_items;
            $message['extra_data'] = array('m_id'=>$member_lc['m_id'],'or_money'=>$or_money);
            //print_r($message);exit;
            return true;
    }
    
    private function afterUpdate($int_or_id) {
        //自动生成售后单据编号，单据编号的规则为20130628+8位单据ID（不足8位左侧以0补全）
		$int_tmp_or_id = $int_or_id;
		$or_return_sn = date("Ymd") . sprintf('%07s',$int_tmp_or_id);
        $array_modify_data = array("or_return_sn"=>$or_return_sn);
		$mixed_result = D('OrdersRefunds')->where(array("or_id"=>$int_or_id,'or_update_time'=>date('Y-m-d H:i:s')))->save($array_modify_data);
        return $mixed_result;
    }
    
     /**
      * 组装退换货单数据
      * @author chenzongyao@guanyisoft.com
      * @date 2013-08-01
     */
    private function getTradeThdResponse($or_return_sn,$operate_type) {
	    $str = 'or_create_time,or_update_time,or_return_sn';       
		$ary_response = $this->orders_refunds->field($str)->where(array('or_return_sn' =>$or_return_sn))->find();
		
		$ary_inventory_response = array();
		if(!empty($ary_response)) {
		   switch ($operate_type) {
		      case 'ADD':
                  $ary_inventory_response = array('created'=>$ary_response['or_create_time'],'rid'=>$ary_response['or_return_sn']);
                  break;
              case 'UPDATE':
              $ary_inventory_response = array('created'=>$ary_response['or_update_time'],'rid'=>$ary_response['or_return_sn']);
               break;
         } 
		  
          
		  return array('msg'=>'','data'=>$ary_inventory_response);
		}
		else return array('msg'=>'返回退换货单信息出现异常');
		
	}
    
    
    
    
    
    /**
	 * 退换货单数据映射获取
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-02
     * return mixed
	 */
     /*
	/**
	 * 仓库数据映射获取
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-02
	 */
	private function getAddThdField($array_client_fields = array(),$type = 'ADD'){
		switch ($type) {
                case 'ADD':
                    return $this->parseFields($this->ary_add_field,$array_client_fields);
                    break;
                case 'UPDATE':
                    return $this->parseFields($this->ary_update_field,$array_client_fields);
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
