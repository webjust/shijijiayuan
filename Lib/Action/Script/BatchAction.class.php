<?php
 /**
 * 商品库存更新计划任务
 *
 * @package Action
 * @stage 7.0
 * @author Zhangjiasuo
 * @date 2013-03-14
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class BatchAction extends GyfxAction{ 
    /**
     * ajax 异步请求处理定时脚本
     * @author Zhangjiasuo
     * @date 2013-03-15
     */
    public function ajaxAsynchronous() {
        $ary_data = D('ScriptInfo')->GetScripts();
        if(!empty($ary_data)){
            @set_time_limit(0);  
            @ignore_user_abort(TRUE); 
			$this->logs('ajaxAsynchronous','开始处理定时脚本'.date('Y-m-d H:i:s'));				
            foreach($ary_data as $value){
                //echo $next_run = date("Y-m-d H:i:s",mktime(date("H"),date("i")+$ary_data['interval'],date("s"),date("m"),date("d"),date("Y")));
                $year =substr($value['run_time'],0,4);
                $month = substr($value['run_time'],5,2);
                $date =substr($value['run_time'],8,2);
                $hour =substr($value['run_time'],11,2);
                $minute =substr($value['run_time'],14,2)+$value['interval'];
                $second =substr($value['run_time'],17,2);
                $next_run=mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
                $pre_run=mktime($hour,$minute,$second,$month,$date,$year);
				if($next_run >$pre_run){
					if($value['code'] != 'updateMember' || $value['code'] != 'syncMemToSns'){
						$ary_data = D('ScriptInfo')->UpdateTime($value['code']);
					}
					//makeRequest('http://'.$_SERVER['HTTP_HOST'].'/'.$value['url'], array());
					if(method_exists($this,$value['url'])){
						$this->$value['url']();
					}
					//$result = makeRequest($value['url'], array());
					//$ary_res = array('success' => 'ok', 'status' => '200', 'err' => '');
					//echo json_encode($ary_res);
					//exit();					
                }
            }
        }
    }
    /**
	 * 库存同步
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-20
	 */
	public function Stock(){  
		$i = 0;
		$page_no = 1;
        $res_insert=array();
		$top = Factory::getTopClient();
		$ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
		$condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value']."'";
		$data = array(
            'fields' => 'guid,sl2,zhanghao',
			'condition' => $condition,
            'page_size' => 10
        );
        
		while( true ){
			$data['page_no'] = $page_no;
			$ary_erpGoods = $top->StockGet($data);
            if(is_array($ary_erpGoods) && !empty($ary_erpGoods)){
                $ary_erp_Goods_data=$ary_erpGoods['stocks']['stock'];
                foreach($ary_erp_Goods_data as $value){
				    //商品库存更新
    				$ary_goods_stock['g_stock'] = isset($value['sl2']) ? (int) $value['sl2'] : 0;
    				$ary_goods_guid  = $value['guid'];
                    $tmp_trade = D('GoodsInfo')->UpdateStock($value['guid'],$ary_goods_stock['g_stock']);
    				//货品库存更新
    				if (!empty($value['spskus']['spsku']) && is_array($value['spskus']['spsku'])) {
    					if (isset($value['spskus']['spsku']['guid'])) {
                            $value['spskus']['spsku'] = array($value['spskus']['spsku']);
                        }
    					foreach($value['spskus']['spsku'] as $info){
    						$ary_products_stock['pdt_stock'] = isset($info['sl2']) ? (int) $info['sl2'] : 0;
    						//$ary_goods_guid  = $info['guid'];
                            $res_products = D('GoodsProducts')->UpdateStock( $info['guid'],$ary_products_stock['pdt_stock']);
    					}
    				}
				    $i += 1; 
                }
                if ($i >= intval($ary_erpGoods['total_results'])){
                     $result=1;
                     $code ='stock';
                     D('ScriptInfo')->UpdateStatus($code,$result);
       	             break;
                }else{
                     $result=0;
                     $code ='stock';
                     D('ScriptInfo')->UpdateStatus($code,$result);
                }
                $page_no += 1;
            }else{
                $code="stock";
                $msg = date('Y-m-d H:i:s') .'    ' . $trade_info['o_id'].'    ' . '连接api[StockGet]接口失败' . "\r\n";
                $this->logs($code,$msg);
            }	
		}
	}
    /**
	 * 订单状态同步（定时作废）
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-20
	 */
    public function OrderStatus(){
        $trade_data=array();
        $tmp_trade = D('Orders')->GetOrderPayStatus($status=0);
        
        $count=count($tmp_trade);
        $res_insert=array();
        foreach($tmp_trade as $key=>$trade_info){
            $tmp_trade = D('Orders')->UpdateOrderStatus($trade_info['o_id'],$status=2);
            if(($key+1)==$count){
                $result=1;
                $code ='orderstatus';
                D('ScriptInfo')->UpdateStatus($code,$result);
            }else{
                $result=0;
                $code ='orderstatus';
                D('ScriptInfo')->UpdateStatus($code,$result);
            }
        }
    }
    /**
	 * 发货状态同步
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-20
	 */
    public function ShipStatus(){
        $flg =false;
        $order_data=array();
        $top = Factory::getTopClient();
        $tmp_trade = D('Orders')->GetOrderPayStatus($status=1);
        $count=count($tmp_trade);
        foreach($tmp_trade as $key=>$trade_info){
            $condition="";
            $ary_ErpOrderdata=array();
            $condition = "lydh='" . $trade_info['o_id']."'";
            $data = array(
                'fields' => 'guid,djbh,fh,fhrq',
    			'condition' => $condition
            );
            $ary_ErpOrderdata = $top->TradeStateGet($data);
            if(is_array($ary_ErpOrderdata) && !empty($ary_ErpOrderdata)){
                $order_data=$ary_ErpOrderdata['tradestates']['tradestate'];
                if($order_data[0]['fh']){
                    D('OrdersItems')->UpdateOrderShipStatus($trade_info['o_id']);
                }
            }else{//同步失败
                $flg = true;
                $code="shipstatus";
                $msg = date('Y-m-d H:i:s') .'    ' . $trade_info['o_id'].'    ' . '连接api[TradeStateGet]接口失败' . "\r\n";
                $this->logs($code,$msg);
            }
            //更新同步结果状态
            if(($key+1)==$count){
                if($flg){
                    $result=0;
                    $code ='shipstatus';
                    D('ScriptInfo')->UpdateStatus($code,$result);
                }else{
                    $result=1;
                    $code ='shipstatus';
                    D('ScriptInfo')->UpdateStatus($code,$result);
                }
            }
        }
    }
    /**
	 * 售后状态接口(退款退货的  进ERP后  ERP有没有审核)
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-21
	 */
    public function AfterSaleStatus(){
        //$order_num="201301071514495346";
        $flg =false;
        $top = Factory::getTopClient();
        $tmp_refunds = D('OrdersRefunds')->GetRefundsOrderStatus();
        //echo M('orders_refunds')->getLastsql();die();
        if(is_array($tmp_refunds)&& !empty($tmp_refunds)){
            $data = array(
                'fields' => 'guid,djbh,ydjh,sh,tkje',
                'page_size' => 10
            );
            $count=count($tmp_refunds);
            foreach($tmp_refunds as $key=>$info){
                $condition="";
                $condition = "ydjh='" . $info['o_id']."'";
                $data['condition']=$condition ;
                $ary_ErpRefunddata = $top->RefundGet($data);
                if(is_array($ary_ErpRefunddata)&&!empty($ary_ErpRefunddata)){
                    $ary_Refunddata=$ary_ErpRefunddata['tkds']['tkd'];
                    foreach($ary_Refunddata as $key=>$vaule){
                        if($vaule['sh']){
                            $data['or_processing_status']=1;
                            $data['or_money']=$vaule['tkje'];
                            $data['or_return_sn']=$vaule['djbh'];
                            D('OrdersRefunds')->UpdateRefundsOrder($info['o_id'],$data);
                        }
                    }
                }else{//同步失败
                    $flg = true;
                    $code="aftersalestatus";
                    $msg = date('Y-m-d H:i:s') .'    ' . $info['o_id'].'    ' . '连接api[RefundGet]接口失败' . "\r\n";
                    $this->logs($code,$msg);
                }
                //更新同步结果状态
                if(($key+1)==$count){
                    if($flg){
                        $result=0;
                        $code ='aftersalestatus';
                        D('ScriptInfo')->UpdateStatus($code,$result);
                    }else{
                        $result=1;
                        $code ='aftersalestatus';
                        D('ScriptInfo')->UpdateStatus($code,$result);
                    }
                }
            }
        } 
    }
    /**
	 * 结余款审核状态(线下充值)
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-22
	 */
    public function RechargeStatus(){
         //线下充值
         $flg =false;
         $top = Factory::getTopClient();
         $tmp_details =D('RechargeExamine')->GetRechargeDetails();
         if(is_array($tmp_details)&& !empty($tmp_details)){
            $count=count($tmp_refunds);
            foreach($tmp_details as $key=>$val){
                $status=false;
                $money=0;
                $condition = "djbh='" . $val['re_payment_sn']."' and hydm='" . $val['m_email']."'";
                $data = array(
                    'fields' => 'guid,djbh,tzje,bz,sh,zf',
			        'condition' => $condition,
                    'page_size' => 10
                );
                $ary_ErpDetails = $top->BalanceDetailGet($data);
                if(is_array($ary_ErpDetails) && !empty($ary_ErpDetails)){
                    $ary_details=$ary_ErpDetails['vjyktzdmxs']['vjyktzdmx'];
                    $verify=$ary_details[0]['sh'];
                    $invalid=$ary_details[0]['zf'];
                    if($verify){
                        $status=1;
                    }
                    if($invalid){
                        $status=2;
                    }
                    if($status!=false){
                        $money=$ary_details[0]['tzje']+$val['m_balance'];
                        D('RechargeExamine')->UpdateRechargeStatus($val['re_id'],$val['m_id'],$status,$money);
                    }
                }else{//同步失败
                    $flg = true;
                    $code="rechargestatus";
                    $msg = date('Y-m-d H:i:s') .'    ' . $val['re_payment_sn'].'    ' . '连接api[BalanceDetailGet]接口失败' . "\r\n";
                    $this->logs($code,$msg);
                }
                //更新同步结果状态
                if(($key+1)==$count){
                    if($flg){
                        $result=0;
                        $code ='rechargestatus';
                        D('ScriptInfo')->UpdateStatus($code,$result);
                    }else{
                        $result=1;
                        $code ='rechargestatus';
                        D('ScriptInfo')->UpdateStatus($code,$result);
                    }
                }
            }
         }
    }
    
    /**
	 * 结余款调整单明细同步
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-22
	 */
    public function BalanceDetail(){
        $i = 0;
        $page_no = 1;
        $top = Factory::getTopClient();
		$condition = "hydm='" . $_SESSION['Members']['m_email']."'";
        $data = array(
                'fields' => 'guid,lxmc,hymc,hydm,djbh,tzje,tzqje,tzhje,bz',
    			'condition' => $condition,
                'page_size' => 10
        );
        while( true ){
            $data['page_no'] = $page_no;
            $ary_ErpBalancedata = $top->BalanceDetailGet($data);
            if(is_array($ary_ErpBalancedata)&&!empty($ary_ErpBalancedata)){
                $ary_Balancedata = $ary_ErpBalancedata['vjyktzdmxs']['vjyktzdmx'];
            }
            //echo "<pre>";print_r($ary_Balancedata);
            foreach($ary_Balancedata as $key=>$vaule){
                $insert_data ['m_id']=$_SESSION['Members']['m_id'];
                $insert_data ['ra_money']=$vaule['tzje'];
                if($vaule['lxmc']=="帐户充值"){
                    $insert_data ['ra_type']=0;
                }
                elseif($vaule['lxmc']=="购物消费"){
                    $insert_data ['ra_type']=1;
                }
                $insert_data ['ra_before_money']=$vaule['tzqje'];
                $insert_data ['ra_after_money']=$vaule['tzhje'];
                $insert_data ['ra_payment_method']='预存款';
                $insert_data ['ra_payment_sn']=$vaule['djbh'];
                $insert_data ['ra_memo']= $vaule['bz'];
                $res=M('running_account')->where(array('ra_payment_sn'=>$vaule['djbh']))->find();
                if(empty($res)){
                    $insert_data ['ra_create_time']= date('Y-m-d H:i:s');
                    M('running_account')->add($insert_data);
                }else{
                    M('running_account')->save($insert_data);
                }
                
                $i += 1; 
            }
            if ($i >= intval($ary_ErpBalancedata['total_results'])){
                break;
            }
            $page_no += 1;
        }
        
    }
    
    /**
	 * 发货单同步 (订单状态为已发货时同步该订单发货单信息)
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-03
	 */
	public function TradeDelivery(){
	   $res_orders = D('OrdersItems')->GetShipOrders();
       if(is_array($res_orders) && !empty($res_orders)){
            $top = Factory::getTopClient();
            $option = array(
                'fields' => 'id,hy_guid,guid,djbh,lydh,wldh,wlgsdm,wlgsmc,wlfy,fhrq,shouhuor,shrgddh,shrsj,shdz,shyb,shengmc,shimc,qumc,fhr,bz'
            );
            foreach($res_orders as $key=>$value){
                $condition = "lydh='" . $value['o_id']."'";
                $fx_menber_id=$value['m_id'];
                $fx_email=$value['o_receiver_email'];
                //$fx_logi_id='1';
                $option['condition']=$condition;
                $ary_erpTrade = $top->TradeDelivery($option);

                if(is_array($ary_erpTrade)&&!empty($ary_erpTrade)){
                    $ary_Trade=$ary_erpTrade['sendorders']['sendorder'];
                    $where['o_id']=$ary_Trade['lydh'];
                    $tmp_trade = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->field('o_id')->where($where)->select();
                    if(empty($tmp_trade)){
                        $data['o_id']=$ary_Trade['lydh'];
                        $data['od_created']=$ary_Trade['fhrq'];
                        //$data['od_delivery']=$ary_Trade['id'];
                        $data['m_id']=$fx_menber_id;
                        $data['od_money']=$ary_Trade['wlfy'];
                        //$data['od_is_protect']=$ary_Trade['wlfy'];
                        $data['od_logi_id']=$fx_logi_id;
                        $data['od_logi_name']=$ary_Trade['wlgsmc'];
                        $data['od_logi_no']=$ary_Trade['wldh'];
                        $data['u_name']='ERP';
                        //$data['u_id']='';
                        $data['od_memo']=$ary_Trade['bz'];
                        $data['od_receiver_name']=$ary_Trade['shouhuor'];
                        $data['od_receiver_mobile']=$ary_Trade['shrsj'];
                        $data['od_receiver_telphone']=$ary_Trade['shrgddh'];
                        
                        $data['od_receiver_area']=$ary_Trade['qumc'];
                        $data['od_receiver_address']=$ary_Trade['shdz'];
                        $data['od_receiver_zipcode']=$ary_Trade['shyb'];
                        $data['od_receiver_email']=$fx_email;
                        $data['od_outer_odid']=$ary_Trade['id'];
                        $data['od_receiver_city']=$ary_Trade['shimc'];
                        $data['od_receiver_province']=$ary_Trade['shengmc'];
                        $data['ddspmxs']=$ary_Trade['ddspmxs']['ddspmx'];
                        foreach ($data as $key=>$val){//过滤为空的字段
                            if(is_null($val) && !is_array($val)){
                                unset($data[$key]);
                            }
                        }
                        $res_products = D('OrdersDelivery')->doSynOrders($data);
                    }
                }
            }
       }
	}
	
    /**
	 * 更新会员等级
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
	 */
	function autoUpgrade(){
	   D('MembersLevel')->autoUpgrade();
	}
    /**
	 * 记录错误日志
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-21
     * @param string $code 同步脚本编号
     * @param string $msg 错误信息
	 */
	function logs($code,$msg){
	   //$log_dir = APP_PATH . 'Runtime/Apilog/';
	   $log_dir = RUNTIME_PATH."Logs/";
	   if(!file_exists($log_dir)){
           mkdir($log_dir,0700);
       }
       $log_file = $log_dir . date('Ymd') .$code . '.log';
       $fp = fopen($log_file, 'a+');
       fwrite($fp, $msg);
       fclose($fp);
	}
	
	/**
	 * 更新自动确认收货
	 * 查询设置自动确认天数之前7天内的订单
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-24
	 */
	function ConfirmOrderstatus(){
	   //判断是否开启及设置的自动确认天数
	    $is_open = D('SysConfig')->getCfg('IS_AUTO_CONFIRM_ORDER','IS_AUTO_CONFIRM_ORDER','0','开启订单自动确认收货');
		if($is_open['IS_AUTO_CONFIRM_ORDER']['sc_value'] == 1){
			$this->logs('ConfirmOrderstatus','开始订单确认收货'.date('Y-m-d H:i:s'));			
			$ary_open_date = D('SysConfig')->getCfg('CONFIRM_ORDER_DAY','CONFIRM_ORDER_DAY','7','设置发货后多少天后自动确认收货');		
			$open_date = intval($ary_open_date['CONFIRM_ORDER_DAY']['sc_value']);
			if(empty($open_date)){
				$open_date = 7;
			}
			//查询条件
			//fx_orders_delivery o_id od_created
			//fx_orders o_id  o_status=1
			$start_time = strtotime('-'.$open_date.' day',time());  
			$beginTime = date('Y-m-d H:i:s', $start_time); 
			$end_date = $open_date+7;
			$end_time = strtotime('-'.$end_date.' day',time()); 
			$endTime = date('Y-m-d H:i:s', $end_time); 
			$ary_where = array();
			$ary_where["od.od_created"] = array("between",array($endTime, $beginTime));
			$ary_where['o.o_status'] = 1;
			$ary_order_ids = D('OrdersDelivery')->alias('od')
                ->field('o.o_id')
                ->join('join fx_orders as o on o.o_id=od.o_id')
                ->where($ary_where)
                ->select();
            //echo D('OrdersDelivery')->getLastSql();die;
			foreach($ary_order_ids as $order_info){
				$res = D('Orders')->orderConfirm($order_info['o_id']);
				if(empty($res['status'])){
					$this->logs('syncConfirmOrder','订单'.$order_info['o_id'].'确认收货失败：:'.$res['message']);
				}else{
					//echo $order_info['o_id'].'<br />';
					$this->logs('ConfirmOrderstatus','订单'.$order_info['o_id'].'确认收货成功');
				}
			}
		}
	}
	
	/**
	 * 更新自动完结
	 * 查询设置自动确认天数之前7天内的订单
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-07-16
	 */
	function FinishOrderstatus(){
	   //判断是否开启及设置的自动确认天数
	    $is_open = D('SysConfig')->getCfg('IS_AUTO_FINISH_ORDER','IS_AUTO_FINISH_ORDER','0','是否开启订单自动完结');
		if($is_open['IS_AUTO_FINISH_ORDER']['sc_value'] == 1){
			$this->logs('FinishOrderstatus','开始订单完结'.date('Y-m-d H:i:s'));	
			$ary_open_date = D('SysConfig')->getCfg('FINISH_ORDER_DAY','FINISH_ORDER_DAY','7','设置收货后多少天后自动完结');		
			$open_date = intval($ary_open_date['FINISH_ORDER_DAY']['sc_value']);
			if(empty($open_date)){
				$open_date = 7;
			}
			//查询条件
			$seven_day = mktime(date("H"),date("i"),date("s"),date("m"),date("d")-$open_date,date("Y"));
			$beginTime = date('Y-m-d H:i:s',$seven_day);
			$ary_where = array();
			$ary_where['fx_orders.o_pay_status'] = 1;
			$ary_where['fx_orders.o_status'] = 5;
			$ary_where['fx_orders.o_update_time'] = array('LT', $beginTime);//7天前收货的订单
			$ary_order_ids = D('Orders')->where($ary_where)->join('fx_orders_refunds  on(fx_orders.o_id=fx_orders_refunds.o_id)')
			                            ->field('fx_orders.o_id,fx_orders.o_status,fx_orders_refunds.or_refund_type,fx_orders_refunds.or_processing_status')->select();
			foreach($ary_order_ids as $order_info){
				$res = D('Orders')->FinishOrderstatus($order_info['o_id']);
				if($res['status']==true){
					$this->logs('FinishOrderstatus','订单'.$order_info['o_id'].'订单完结:'.$res['message']);
				}else{
					//echo $order_info['o_id'].'<br />';
					$this->logs('syncFinishOrder','订单'.$order_info['o_id'].'订单完结失败:'.$res['message']);					
				}
			}
		}
	}

	/**
	 * 自动更新获取结余款利息金币
     * @author Hcaijin 
     * @date 2014-08-18
	 */
	function AutoInterestRates(){
	    //判断是否开启金币利息设置
        D('SysConfig')->getCfg('JIULONGBI_MONEY_SET','JIULONGBI_AUTO_OPEN','1','是否启用九龙币功能');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] == 1 && $ary_jlb_data['interest_rates']>0){
            $ary_members=D('Members')->field('m_id,m_balance')->select();
            foreach($ary_members as $val){
                $jlb=sprintf('%.2f',$val['m_balance']*$ary_jlb_data['interest_rates']);
                if($jlb>0 && $val['m_balance']>$ary_jlb_data['min_balance']){
                    //更新金币
                    $arr_jlb = array(
                        'jt_id' => '1',
                        'm_id'  => $val['m_id'],
                        'ji_create_time'  => date("Y-m-d H:i:s"),
                        'ji_type' => '0',
                        'ji_money' => $jlb,
                        'ji_desc' => "结余款生成利息金币".$jlb."个",
                        'o_id' => '',
                        'ji_finance_verify' => '1',
                        'ji_service_verify' => '1',
                        'ji_verify_status' => '1',
                        'single_type' => '2'
                    );
                    $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                    if(!$res){
                        $this->logs('AutoInterestRates','自动更新获取结余款利息金币失败');
                    }
                }
            }
        }
	}
	
    /**
	 * 九龙港客户专用
     * 会员自动同步新增
	 * @author Huangcaijin 
     * @date 2014-08-23	 
     */
    public function addMember(){
        $code = "addMember";
        $flg =false;
        $script_info = D('ScriptInfo')->where(array('code'=>'addMember'))->find();
        if(isset($script_info)){
            $where['m_update_time'] = array('gt',$script_info['run_time']);
        }
        $where['m_status'] = 1;
        $where['m_mobile'] = array('neq','');
		$where['_string'] = "ifnull(m_card_no,'') = ''";
        $arr_members = D('Members')->where($where)->select();
        //dump(D('Members')->getLastSql());exit();
        $count = count($arr_members);
        if(is_array($arr_members) && isset($arr_members)){
            try{
                $crond_obj = new Aeaicrypt();
                foreach($arr_members as $k => $member){
                    $res_data = $crond_obj->addMemApi($member);
                    //dump($res_data);exit();
                    if($res_data['status'] == 1){
                        $card_data = $res_data['data'];
                        $save_data = array('m_card_no'=>$card_data->card_num);
                        $save_where = array('m_id'=>$card_data->card_ref);
                        $save_res = D('Members')->where($save_where)->save($save_data);
                        //echo D('Members')->getLastSql();exit();
                        if($save_res){
                            $msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$res_data['msg']."\r\n";
                            $this->logs($code,$msg);
                        }else{
                            $flg = true;
                            $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg']."\r\n";
                            $this->logs($code,$msg);
                        }
                    }else{
                        $flg = true;
                        $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg']."\r\n";
                        $this->logs($code,$msg);
                    }
                    if(($k+1)==$count){
                        if($flg){
                            D('ScriptInfo')->UpdateStatus($code,0);
                        }else{
                            D('ScriptInfo')->UpdateStatus($code,1);
                        }
                    }
                }
            }catch(exception $e){
                $code = "addMember";
                $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage()."\r\n";
                $this->logs($code,$msg);
            }
        }
    }

	/**
	 * 九龙港客户专用
     * 会员自动同步新增
	 * @author Huangcaijin 
     * @date 2014-08-23
     */
    public function addMembers(){
        $code = "batchAdd";
        $flg =false;
        $where['m_status'] = 1;
        $where['m_mobile'] = array('neq','');
        $where['_string'] = "ifnull(m_card_no,'') = ''";
        $countMem = D('Members')->where($where)->count();
        $page_size = 100;
        $page_nums = ceil($countMem/$page_size);
        if($countMem>0){
            for($i=0;$i<$page_nums;$i++){
                $arr_members = D('Members')->where($where)->limit($page_size)->select();
                $count = count($arr_members);
                if(is_array($arr_members) && isset($arr_members)){
                    try{
                        $crond_obj = new Aeaicrypt();
                        $res_data = $crond_obj->batchMemApi($arr_members);
                        if($res_data['status'] == 1){
                            foreach($res_data['data'] as $k=>$card_data){
                                $save_data = array('m_card_no'=>$card_data->card_num);
                                $save_where = array('m_id'=>$card_data->card_ref);
                                $save_res = D('Members')->where($save_where)->save($save_data);
                                //echo D('Members')->getLastSql();exit();
                                if($save_res){
                                    $msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$save_data['m_card_no']."\r\n";
                                    $this->logs($code,$msg);
                                }else{
                                    $flg = true;
                                    $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$save_data['m_card_no']."\r\n";
                                    $this->logs($code,$msg);
                                }					
                                if(($k+1)==$count){
                                    if($flg){
                                        D('ScriptInfo')->UpdateStatus($code,0);
                                    }else{
                                        D('ScriptInfo')->UpdateStatus($code,1);
                                    }
                                }
                            }
                        }else{
                            D('ScriptInfo')->UpdateStatus($code,0);
                            $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg']."\r\n";
                            $this->logs($code,$msg);
                        }
                    }catch(exception $e){
                        $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage()."\r\n";
                        $this->logs($code,$msg);
                    }			
                }
            }
        }
    }
	
    /**
	 * 九龙港客户专用
     * 会员自动同步更新接口
	 * @author Huangcaijin 
     * @date 2014-08-23	 
     */
    public function updateMember(){
        $code = "updateMember";
        $flg =false;
        $script_info = D('ScriptInfo')->where(array('code'=>'updateMember'))->find();
        if(isset($script_info)){
            $where['m_update_time'] = array('gt',$script_info['run_time']);
        }
        $where['m_status'] = 1;
        $where['m_mobile'] = array('neq','');
        //$where['_string'] = "ifnull(m_card_no,'') != ''";
        $where['m_card_no'] = array('neq','');
        $arr_members = D('Members')->where($where)->select();
        //dump(D('Members')->getLastSql());exit();
        $count = count($arr_members);
        if(is_array($arr_members) && isset($arr_members)){
            try{
                $crond_obj = new Aeaicrypt();
                foreach($arr_members as $k => $member){
                    $res_data = $crond_obj->updateMemApi($member);
                    if($res_data['status'] == 1){
                        D('ScriptInfo')->UpdateStatus($code,1);
                        $msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$res_data['msg']."\r\n";
                        $this->logs($code,$msg);
                    }else{
                        $flg = true;
                        $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg']."\r\n";
                        $this->logs($code,$msg);
                    }
                    if(($k+1)==$count){
                        if($flg){
                            D('ScriptInfo')->UpdateStatus($code,0);
                        }else{
                            D('ScriptInfo')->UpdateStatus($code,1);
                        }
                    }
                }
            }catch(exception $e){
                $code = "updateMember";
                $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage()."\r\n";
                $this->logs($code,$msg);
            }
        }
        D('ScriptInfo')->UpdateTime($code);
    }
	
	/**
	 * 蓝源使用
	 * 同步订单信息到客服
     * @author Wangguibin 
     * @date 2014-08-22
	 */
	function AutoSyncTrade(){
	    //获取未同步订单信息并同步
		$code = "syncTrade";
		$ary_where = array();
		$ary_where['_string'] = "ifnull(o.o_id,'') = ''";
		$ary_trades = D('Orders')->field('fx_orders.o_id as o_id1,fx_orders.o_all_price,fx_orders.m_id,o.o_id')->join('fx_related_sync_order as o on(fx_orders.o_id=o.o_id)')
		->where($ary_where)->select();
		$lanyuan_obj = new Lanyuan();
		if(empty($ary_trades)){
		   //上线以后去掉这个else
            $this->error('没有要更新的订单数据！');
            exit;
		}else{
			foreach($ary_trades as $val){
				$param = array();
				$param['userId'] = $val['m_id'];
				$param['orderId'] = $val['o_id1'];
				$param['totalPrice'] = $val['o_all_price'];
				$return_result = $lanyuan_obj->collectOrder($param);
				if($return_result == 0 || $return_result == 3){
					$save_res = D('RelatedSyncOrder')->data(array('o_id'=>$val['o_id1'],'status'=>1,'time'=>date('Y-m-d H:i:s')))->add();
					 if($save_res){
						$msg = date('Y-m-d H:i:s').'同步订单信息成功,订单号：'.$val['o_id1']."\r\n";
						$this->logs($code,$msg);
					}else{
						$flg = true;
						$msg = date('Y-m-d H:i:s').'同步订单信息失败,订单号：'.$val['o_id1']."\r\n";
						$this->logs($code,$msg);
					}
				}
			}		
		}
	}
	
	/**
	 * 九龙港客户专用
     * 会员同步新增至SNS
	 * @author Hcaijin 
     * @date 2014-10-11
     */
    public function syncMemToSns(){
        $flg = false;
        $code = "syncMemToSns";
        //$where['m_mobile'] = array('neq','');
        //$where['m_password'] = array('neq','');
        //$where['m_sns'] = array('eq','0');
        //更新的SNS会员信息
        while(true){
            $script_info = D('ScriptInfo')->where(array('code'=>'syncMemToSns'))->find();
            $where['_string'] = "m_mobile != '' and m_password != '' and m_sns = 0 or (m_update_time > '".$script_info['run_time']."' and m_sns = 1 )";
            $arr_members = D('Members')->where($where)->limit(100)->select();
            if(count($arr_members)<1){
                break;
            }
            $int_port = "";
            if($_SERVER["SERVER_PORT"] != 80){
                $int_port = ':' . $_SERVER["SERVER_PORT"];
            }
            foreach($arr_members as $k => $mem){
                $head_url =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$mem['m_id'],'field_id'=>19))->getField('content');
                if($head_url){
                    if(strpos($head_url,'http://115.29.109.194') !== false){
                        $head_url = $head_url;
                    }else{
                        $head_url = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $head_url;
                    }
                    $arr_members[$k]['m_head_url'] = $head_url;
                }else{
                    $arr_members[$k]['m_head_url'] = '';
                }
            }
            if(is_array($arr_members) && isset($arr_members)){
                try{
                    $crond_obj = new Aeaicrypt(1);
                    $res_data = $crond_obj->syncMemToSns($arr_members);
                    //dump($res_data);exit;
                    if($res_data['status'] == 1){
                        $resData = explode(',',$res_data['data']['sucdata']);
                        $count = count($resData);
                        foreach($resData as $x =>$val){
                            $res_Mem = D('Members')->where(array('m_name'=>$val))->getField('m_sns');
                            if($res_Mem == 0){
                                $save_res = D('Members')->where(array('m_name'=>$val))->save(array('m_sns'=>1));
                                if($save_res){
                                    $msg = date('Y-m-d H:i:s').'      更新会员成功：'.$val."\r\n";
                                    $this->logs($code,$msg);
                                }else{
                                    $flg = true;
                                    $msg = date('Y-m-d H:i:s').'      更新会员失败：'.$val."\r\n";
                                    $this->logs($code,$msg);
                                    break;
                                }					
                            }else{
                                $update_time = D('ScriptInfo')->UpdateTime($code);
                                if(!$update_time){
                                    break;
                                }	
                            }
                            if(($x+1)==$count){
                                if($flg){
                                    D('ScriptInfo')->UpdateStatus($code,0);
                                    break;
                                }else{
                                    D('ScriptInfo')->UpdateStatus($code,1);
                                }
                            }
                        }
                    }else{
                        D('ScriptInfo')->UpdateStatus($code,0);
                        $msg = date('Y-m-d H:i:s').'      同步会员到SNS失败：'.$res_data['msg']."\r\n";
                        $this->logs($code,$msg);
                        break;
                    }
                }catch(exception $e){
                    $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage()."\r\n";
                    $this->logs($code,$msg);
                    break;
                }			
            }else{
                break;
            }
        }
        $update_time = D('ScriptInfo')->UpdateTime($code);
    }

    /**
     * 普通商品24小时自动作废
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-29
     */
    public function AutoInvalidOrderCommon(){
        $ary_where = array(
            'fx_orders.o_pay_status' => 0,
            'fx_orders.o_status' => 1,
            'items.oi_type' => 0,
			'fx_orders.o_payment'=>array('neq',6),
            'items.oi_create_time' => array('lt',date('Y-m-d H:i:s',strtotime('-24 hours')))
            );
        $this->InvalidOrderByCondition($ary_where);
    }

    /**
     * 团购商品1小时自动作废
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-25
     */
    public function AutoInvalidOrderGroupon(){
        $ary_where = array(
            'fx_orders.o_pay_status' => 0,
            'fx_orders.o_status' => 1,
            'items.oi_type' => 5,
			'fx_orders.o_payment'=>array('neq',6),
            'items.oi_create_time' => array('lt',date('Y-m-d H:i:s',strtotime('-1 hours')))
            );
        $this->InvalidOrderByCondition($ary_where);
    }

    /**
     * 自动作废
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-29
     */
    public function InvalidOrderByCondition($ary_where){
        $orders = M('orders',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field('items.o_id as o_id')
                    ->join('fx_orders_items as items on items.o_id=fx_orders.o_id')
                    ->where($ary_where)
                    ->group('items.o_id')
                    ->select();
        if(empty($orders)){
            $this->error('没有要作废的订单数据！');
            exit;
        }else{
			@set_time_limit(0);  
			@ignore_user_abort(TRUE); 
            foreach($orders as $vo){
                $this->InvalidOrder($vo['o_id']);
            }
        }
    }

    /**
     * 后台订单作废
     * @author listen  
     * @date 2013-02-27
     */
    public function InvalidOrder($int_oid) {
        $orders_comments = '自动任务';
        $cacel_type = '5';
        if (isset($int_oid)) {
            //断订单是满足作废条件,没有同步到erp的订单
            $ary_where = array('o_id' => $int_oid, 'o_status' => 1);
            $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_orders = $orders->where($ary_where)->find();
            if ($ary_orders['erp_sn'] != '') {
                return false;
            } else {
                if($ary_orders['o_pay_status'] != 0){
                    // $this->ajaxReturn('存在支付信息订单不能作废!');
                    return false;
                }
                $ary_order_data = array('o_status' => 2, 'o_seller_comments' => trim($orders_comments), 'cacel_type' => $cacel_type, 'o_update_time' => date('Y-m-d H:i:s'));
                $resdata = D('SysConfig')->getCfg('ORDERS_OPERATOR', 'ORDERS_OPERATOR', '1', '只记录第一次操作人');
                //查询订单是否存在操作者ID
                $ary_order_data['admin_id'] = 1;
                $return_orders = $orders->where(array('o_id' => $int_oid))->save($ary_order_data);
                if ($return_orders > 0) {
                    // 冻结积分释放掉
                    $point_orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('m_id,o_freeze_point')->where(array('o_id' => $int_oid))->find();
                    if (isset($point_orders['o_freeze_point']) && $point_orders['o_freeze_point'] > 0 && $point_orders['m_id'] > 0) {
                        $ary_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->field('freeze_point')->where(array('m_id' => $point_orders['m_id']))->find();
                        if ($ary_member && $ary_member['freeze_point'] > 0) {
                            //作废订单返还冻结积分日志
                            $ary_log = array(
                                        'type'=>8,
                                        'consume_point'=> 0,
                                        'reward_point'=> $point_orders['o_freeze_point']
                                        );
                            $ary_info =D('PointLog')->addPointLog($ary_log,$point_orders['m_id']);
                            if($ary_info['status'] == 1){
                                 $ary_member_data['freeze_point'] = $ary_member['freeze_point'] - $point_orders['o_freeze_point'];
                                 $res_member = M('Members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $point_orders['m_id']))->save($ary_member_data);
                                 if(!$res_member){
                                     // $this->ajaxReturn('作废订单返回冻结积分失败');
                                     return false;
                                 }
                            }else{
                                 // $this->ajaxReturn('作废订单返回冻结积分写日志失败');
                                 return false;
                            }
                        }else{
                            // $this->ajaxReturn('作废订单返回冻结积分没有找到要返回的用户冻结金额');
                            return false;
                        }
                    }
                    //还原,支出冻结优惠券
                    $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_oid))->find();
                    if (!empty($ary_coupon) && is_array($ary_coupon)) {
                        $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $int_oid))->save(array('c_used_id' => 0, 'c_order_id' => 0, 'c_is_use' => 0));
                        if (!$res_coupon) {
                            // $this->ajaxReturn(false);
                            return false;
                        }
                    }
                    //还原,支出冻结红包金额
                    $ary_bonus = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'bn_type'=>array('neq','0')))->find();
                    if(!empty($ary_bonus) && is_array($ary_bonus)){
                        $arr_bonus = array(
                            'bt_id' => '4',
                            'm_id'  => $ary_bonus['m_id'],
                            'bn_create_time'  => date("Y-m-d H:i:s"),
                            'bn_type' => '0',
                            'bn_money' => $ary_bonus['bn_money'],
                            'bn_desc' => '后台强制作废成功,返还红包金额：'.$ary_bonus['bn_money'].'元',
                            'o_id' => $ary_bonus['o_id'],
                            'bn_finance_verify' => '1',
                            'bn_service_verify' => '1',
                            'bn_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_bonus = D('BonusInfo')->addBonus($arr_bonus);
                        if(!$res_bonus){
                            // $this->ajaxReturn(false);
                            return false;
                        }
                    }
                    //还原,支出冻结储值卡金额
                    $ary_cards = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$int_oid,'ci_type'=>array('neq','0')))->find();
                    if(!empty($ary_cards) && is_array($ary_cards)){
                        $arr_cards = array(
                            'ct_id' => '2',
                            'm_id'  => $ary_cards['m_id'],
                            'ci_create_time'  => date("Y-m-d H:i:s"),
                            'ci_type' => '0',
                            'ci_money' => $ary_cards['ci_money'],
                            'ci_desc' => '后台强制作废成功,返还储值卡金额：'.$ary_cards['ci_money'].'元',
                            'o_id' => $ary_cards['o_id'],
                            'ci_finance_verify' => '1',
                            'ci_service_verify' => '1',
                            'ci_verify_status' => '1',
                            'single_type' => '2'
                        );
                        $res_cards = D('CardsInfo')->addCards($arr_cards);
                        if(!$res_cards){
                            // $this->ajaxReturn(false);
                            return false;exit();
                        }
                    }
                    //销量返回
                    $return_orders_items = M('orders_items')->field('oi_id,o_id,g_id,g_sn,oi_type,pdt_sn,pdt_id,fc_id,oi_nums')->where(array('o_id'=>$int_oid))->select();
                    $tag = true;
                    foreach($return_orders_items as $v){
                        //$stock_res = M('goods_products')->where(array('g_sn'=>$v['g_sn'],'pdt_sn'=>$v['pdt_sn'],'pdt_freeze_stock'=>array('elt',-$v['oi_nums'])))
                        //->data(array('pdt_update_time'=>date('Y-m-d H:i:s'),'pdt_freeze_stock'=>array('exp',"pdt_freeze_stock-".$v['oi_nums']),'pdt_stock'=>array('exp',"pdt_stock+".$v['oi_nums'])))
                        //->save();
                        /*****@author Tom 分销商库存返回 start *****/
						/**
                        $ary_inventory = array(
                            'pdt_id' => $v['pdt_id'],
                            'm_id' => $ary_orders['m_id'],
                            'num' => $v['oi_nums'],
                            'u_id' => $ary_order_data['admin_id'],
                            'srr_id' => 0
                            );
							**/
                        //$stock_inventory_res = D('GoodsProducts')->BackInventoryLockStock($ary_inventory);
                        /*****@author Tom 分销商库存返回 end *****/
                        // if(false === $stock_res){
                        //     $tag = false;break;
                            //$orders->rollback();
                            //$this->error("库存返回失败");exit;
                        // }

                        if($v['oi_type']==5 && !empty($v['fc_id'])){
                            $retun_buy_nums=D("Groupbuy")->where(array('gp_id' => $v ['fc_id']))->setDec("gp_now_number",$v['oi_nums']);
                            if (!$retun_buy_nums) {
                                // $this->ajaxReturn(false);
                                $tag = false;
                                break;
                            }
                        }elseif($v['oi_type']==7 && !empty($v['fc_id'])){
                            $retun_spike_nums=D("Spike")->where(array('sp_id' => $v['fc_id']))->setDec("sp_now_number",$v['oi_nums']);
                            if(!$retun_spike_nums){
                                // $this->ajaxReturn(false);
                                $tag = false;
                                break;
                            }
                        }
                        $ary_goods_num = M("goods_info")->where(array(
                                    'g_id' => $v ['g_id']
                                ))->data(array(
                                    'g_salenum' => array(
                                        'exp',
                                        'g_salenum - '.$v['oi_nums']
                                    )
                                ))->save();
                                
                        if (!$ary_goods_num) {
                            // $this->ajaxReturn(false);
                            $tag = false;
                            break;
                        }   
                    }
                    if(!$tag){
                        return false;
                    }
                    //更新日志表
                    $ary_orders_log = array(
                        'o_id' => $int_oid,
                        'ol_behavior' => '订单作废:' . $orders_comments,
                        'ol_uname' => '管理员自动任务',
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->where(array('o_id' => $int_oid))->add($ary_orders_log);
                    if (!$res_orders_log) {
                        // $this->ajaxReturn(false);
                        return false;
                    }
                    //判断此作废订单是否是第三方平台订单
                    $int_o_source_id = $orders->where(array("o_id"=>$int_oid,"o_status"=>2))->getField("o_source_id");
                    if(0 != $int_o_source_id) {
                        $return = D("ThdOrders")->where(array('to_oid'=>$int_o_source_id))->find();
                        if($return){
                            D("ThdOrders")->where(array('to_oid'=>$int_o_source_id))->save(array('to_tt_status'=>0));
                        }
                    }
                    // $this->ajaxReturn(true);
                    return true;
                } else {
					$code="EROOR";
					$msg = date('Y-m-d H:i:s') .'  订单作废失败' . $int_oid."\r\n";
					$this->logs($code,$msg);
                    // $this->ajaxReturn(false);
                    return false;
                }
            }
        }
    }

    /**
     * 商品库存自动上下架
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-03-18
     */
    public function autoUpDownFrame(){
        $ary_goods_info = D('GoodsInfo')->where(array('g_stock'=>array('elt',0)))->select();
        if(!empty($ary_goods_info) && is_array($ary_goods_info)){
            foreach($ary_goods_info as $val){
                $data = array('g_on_sale'=>2);
                if(false === D('Goods')->where(array('g_id'=>$val['g_id'],'g_on_sale'=>1))->setField($data)){
                    continue;
                }
            }
        }
        $ary_goods_info = D('GoodsInfo')->where(array('g_stock'=>array('gt',0)))->select();
        if(!empty($ary_goods_info) && is_array($ary_goods_info)){
            foreach($ary_goods_info as $val){
                $data = array('g_on_sale'=>1);
                if(false === D('Goods')->where(array('g_id'=>$val['g_id'],'g_on_sale'=>2))->setField($data)){
                    continue;
                }
            }
        }
    }
	
	/**
	 * 会员号定时同步pso 系统
	 * @author add by zhangjiasuo 
	 * @date 2015-08-10
	 */
	public function snyc_member_send($int_start =0,$int_end =100 ,$current=0){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$ci_sn_key = md5(CI_SN);
		$where['open_id'] ='';
		$where['shop_id'] ='0';
		$num =D('Members')->where($where)->count();
		$ary_members = D('Members')->field('m_id,m_name,shop_code,m_name,m_mobile,m_sex,m_password,m_telphone,m_birthday,m_id_card,m_email,m_qq,m_wangwang,m_status')
		                           ->where($where)->limit($int_start, $int_end)->select();
		
		foreach($ary_members as $value){
			$value['m_mobile']=decrypt($value['m_mobile']);
			if($value['m_telphone']!=''){
				$value['m_telphone']=decrypt($value['m_telphone']);
			}
			$value['batch']=true;
			$o2o_api =  new GyO2oApi();
			$o2o_api->AddMember($value);
			$current ++;
		}
		if($num > $current){
			$int_start += $current ;
			$int_end += $current;
			$this->snyc_member_send($int_start,$int_end,$current);
		}
	}
	
	public function snyc_send($ary_data=array()){
		//解码已编码的URL字符串
		@set_time_limit(0);  
        @ignore_user_abort(TRUE); 
        $data = array_map('urldecode',$_POST);
		$file_name=date('Ymd').'test.log';
		$o2o_api =  new GyO2oApi();
		if($data['method'] == 'AddMember'){ // 新增
			unset($data['method']);
			$o2o_api->AddMember($data);
		}
		if($data['method'] == 'UpdateMember'){// 修改
			unset($data['method']);
			$o2o_api->UpdateMember($data);
		}
		
	}
	
	/**
	 * 老加密转换新加密方式
	 * @author add by zhangjiasuo 
	 * @date 2015-08-10
	 */
	public function member_decrypt($int_start =0,$int_end =100 ,$current=0){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$ci_sn_key = md5(CI_SN);
		$where['m_mobile'] =array( array('notlike','%:'.$ci_sn_key .'%' ),array('neq',''),'and');
		$num =D('Members')->where($where)->count();
		$ary_members = D('Members')->field('m_id,m_mobile,m_telphone')->where($where)->limit($int_start, $int_end)->select();
		//echo  D('Members')->getLastsql();die();
		foreach($ary_members as $value){
			$tmp_mobile_string ='';
			$tmp_telphone_string ='';
			//手机号
			$tmp_mobile_string=decrypt($value['m_mobile']);
			if(is_numeric($tmp_mobile_string) && $tmp_mobile_string!='' && $tmp_mobile_string >0){
				$new_tmp_string =encrypt($tmp_mobile_string);
				$data['m_mobile'] =$new_tmp_string;
				$res = D('Members')->where(array('m_id' =>$value['m_id']))->save($data);
				if($res ==true){
					$code='member_oldencrypt_tonewencrypt';
					$msg ="m_id=".$value['m_id']."  m_mobile老加密原始数据".$value['m_mobile']."老的解密原始数据" .$tmp_mobile_string."\r\n";
					$this->logs($code,$msg);
				}
			}
			//固定电话
			/*$tmp_telphone_string=decrypt2($value['m_telphone']);
			$ary_telphone = explode('-',$tmp_telphone_string);
			if(count($ary_telphone)>1){
				$check_telphone_string =$ary_telphone[0].$ary_telphone[1];
			}else{
				$check_telphone_string =$ary_telphone[0];
			}
			if(is_numeric($check_telphone_string) && $tmp_telphone_string!=''){
				$new_tmp_string =encrypt($tmp_telphone_string);
				$data['m_telphone'] =$new_tmp_string;
				$res = D('Members')->where(array('m_id' =>$value['m_id']))->save($data);
				if($res ==true){
					$code='member_oldencrypt_tonewencrypt';
					$msg ="m_id=".$value['m_id']."  m_telphone老加密原始数据".$value['m_telphone']."老的解密原始数据" .$tmp_telphone_string."\r\n";
					$this->logs($code,$msg);
				}
			}*/
			$current ++;
		}
		if($num > $current){
			$int_start += $current ;
			$int_end += $current;
			$this->member_decrypt($int_start,$int_end,$current);
		}
	}
	
	/**
	 * 老加密转换新加密方式
	 * @author add by zhangjiasuo 
	 * @date 2015-08-10
	 */
	public function orders_decrypt($int_start =0,$int_end =100 ,$current=0){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$ci_sn_key = md5(CI_SN);
		$where['o_receiver_mobile'] =array( array('notlike','%:'.$ci_sn_key .'%' ),array('neq',''),'and');
		$num =D('Orders')->where($where)->count();
		$ary_orders = D('Orders')->field('o_id,o_receiver_mobile,o_receiver_telphone')->where($where)->limit($int_start, $int_end)->select();
		foreach($ary_orders as $value){
			$tmp_mobile_string ='';
			$tmp_telphone_string ='';
			//手机号
			$tmp_mobile_string=decrypt($value['o_receiver_mobile']);
			if(is_numeric($tmp_mobile_string) && $tmp_mobile_string!=''){
				$new_tmp_string =encrypt($tmp_mobile_string);
				$data['o_receiver_mobile'] =$new_tmp_string;
				$res = D('Orders')->where(array('o_id' =>$value['o_id']))->save($data);
				if($res ==true){
					$code='orders_oldencrypt_tonewencrypt';
					$msg ="o_id=".$value['o_id']."  o_receiver_mobile".$value['o_receiver_mobile']."老的解密原始数据" .$tmp_mobile_string."\r\n";
					$this->logs($code,$msg);
				}
			}
			//固定电话
			/*$tmp_telphone_string=decrypt2($value['o_receiver_telphone']);
			$ary_telphone = explode('-',$tmp_telphone_string);
			if(count($ary_telphone)>1){
				$check_telphone_string =$ary_telphone[0].$ary_telphone[1];
			}else{
				$check_telphone_string =$ary_telphone[0];
			}
			if(is_numeric($check_telphone_string) && $tmp_telphone_string!=''){
				$new_tmp_string =encrypt($tmp_telphone_string);
				$data['o_receiver_telphone'] =$new_tmp_string;
				$res = D('Orders')->where(array('o_id' =>$value['o_id']))->save($data);
				if($res ==true){
					$code='orders_oldencrypt_tonewencrypt';
					$msg ="o_id=".$value['o_id']."  o_receiver_telphone".$value['o_receiver_telphone']."老的解密原始数据" .$tmp_telphone_string."\r\n";
					$this->logs($code,$msg);
				}
			}*/
			$current ++;
		}
		if($num > $current){
			$int_start += $current ;
			$int_end += $current;
			$this->orders_decrypt($int_start,$int_end,$current);
		}
		
	}
	
	/**
	 * 老加密转换新加密方式
	 * @author add by zhangjiasuo 
	 * @date 2015-08-10
	 */
	public function orders_delivery_decrypt($int_start =0,$int_end =100 ,$current=0){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$ci_sn_key = md5(CI_SN);
		$where['od_receiver_mobile'] =array( array('notlike','%:'.$ci_sn_key .'%' ),array('neq',''),'and');
		$num =D('OrdersDelivery')->where($where)->count();
		$ary_orders_delivery = D('OrdersDelivery')->field('od_id,od_receiver_mobile,od_receiver_telphone')->where($where)->limit($int_start, $int_end)->select();
		foreach($ary_orders_delivery as $value){
			$tmp_mobile_string ='';
			$tmp_telphone_string ='';
			//手机号
			$tmp_mobile_string=decrypt($value['od_receiver_mobile']);
			if(is_numeric($tmp_mobile_string) && $tmp_mobile_string!=''){
				$new_tmp_string =encrypt($tmp_mobile_string);
				$data['od_receiver_mobile'] =$new_tmp_string;
				$res = D('OrdersDelivery')->where(array('od_id' =>$value['od_id']))->save($data);
				if($res ==true){
					$code='orders_delivery_oldencrypt_tonewencrypt';
					$msg ="od_id=".$value['od_id']."  od_receiver_mobile".$value['od_receiver_mobile']."老的解密原始数据" .$tmp_mobile_string."\r\n";
					$this->logs($code,$msg);
				}
			}
			//固定电话
			/*$tmp_telphone_string=decrypt2($value['od_receiver_telphone']);
			$ary_telphone = explode('-',$tmp_telphone_string);
			if(count($ary_telphone)>1){
				$check_telphone_string =$ary_telphone[0].$ary_telphone[1];
			}else{
				$check_telphone_string =$ary_telphone[0];
			}
			if(is_numeric($check_telphone_string) && $tmp_telphone_string!=''){
				$new_tmp_string =encrypt($tmp_telphone_string);
				$data['od_receiver_telphone'] =$new_tmp_string;
				$res = D('OrdersDelivery')->where(array('od_id' =>$value['od_id']))->save($data);
				if($res ==true){
					$code='orders_delivery_oldencrypt_tonewencrypt';
					$msg ="od_id=".$value['od_id']."  od_receiver_telphone".$value['od_receiver_telphone']."老的解密原始数据" .$tmp_telphone_string."\r\n";
					$this->logs($code,$msg);
				}
			}*/
			$current ++;
		}
		if($num > $current){
			$int_start += $current ;
			$int_end += $current;
			$this->orders_delivery_decrypt($int_start,$int_end,$current);
		}
		
	}
	
	/**
	 * 老加密转换新加密方式
	 * @author add by zhangjiasuo 
	 * @date 2015-08-10
	 */
	public function receive_address_decrypt($int_start =0,$int_end =100 ,$current=0){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$ci_sn_key = md5(CI_SN);
		$where['ra_mobile_phone'] =array( array('notlike','%:'.$ci_sn_key .'%' ),array('neq',''),'and');
		$num =D('ReceiveAddress')->where($where)->count();
		$ary_receive_address = D('ReceiveAddress')->field('ra_id,ra_mobile_phone,ra_phone')->where($where)->limit($int_start, $int_end)->select();
		foreach($ary_receive_address as $value){
			$tmp_mobile_string ='';
			$tmp_telphone_string ='';
			//手机号
			$tmp_mobile_string=decrypt($value['ra_mobile_phone']);
			if(is_numeric($tmp_mobile_string) && $tmp_mobile_string!=''){
				$new_tmp_string =encrypt($tmp_mobile_string);
				$data['ra_mobile_phone'] =$new_tmp_string;
				$res = D('ReceiveAddress')->where(array('ra_id' =>$value['ra_id']))->save($data);
				if($res ==true){
					$code='orders_delivery_oldencrypt_tonewencrypt';
					$msg ="ra_id=".$value['ra_id']."  ra_mobile_phone".$value['ra_mobile_phone']."老的解密原始数据" .$tmp_mobile_string."\r\n";
					$this->logs($code,$msg);
				}
			}
			//固定电话
			/*$tmp_telphone_string=decrypt2($value['ra_phone']);
			$ary_telphone = explode('-',$tmp_telphone_string);
			if(count($ary_telphone)>1){
				$check_telphone_string =$ary_telphone[0].$ary_telphone[1];
			}else{
				$check_telphone_string =$ary_telphone[0];
			}
			if(is_numeric($check_telphone_string) && $tmp_telphone_string!=''){
				$new_tmp_string =encrypt($tmp_telphone_string);
				$data['ra_phone'] =$new_tmp_string;
				$res = D('ReceiveAddress')->where(array('ra_id' =>$value['ra_id']))->save($data);
				if($res ==true){
					$code='orders_delivery_oldencrypt_tonewencrypt';
					$msg ="ra_id=".$value['ra_id']."  ra_phone".$value['ra_phone']."老的解密原始数据" .$tmp_telphone_string."\r\n";
					$this->logs($code,$msg);
				}
			}*/
			$current ++;
		}
		if($num > $current){
			$int_start += $current ;
			$int_end += $current;
			$this->receive_address_decrypt($int_start,$int_end,$current);
		}
		
	}
	
	/**
	 * 微信用户绑定商城用户积分迁移
	 * @author add by piupiu@126.com 
	 * @date 2016-08-10
	 */
	public function Wxtob2cmemberpoint(){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$where['open_source'] =array('eq','微信');
		$where['open_id'] =array('neq','');
		$where['m_parent'] =array('neq','0');
		$ary_wx_members=D('Members')->field('m_id,m_parent,total_point,freeze_point,open_source,open_id')->where($where)->select();
		if(!empty($ary_wx_members)){
			//积分明细
			foreach($ary_wx_members as $key=>$value){
				$point_res=D('PointLog')->field('log_id')->where(array('m_id'=>$value['m_id']))->find();
				if(!empty($point_res)){
					$code='update_point_log_res';
					$update_point_log_res = D('PointLog')->where(array('log_id'=>$point_res['log_id']))->save(array('m_id'=>$value['m_parent']));
					if($update_point_log_res==true){
						$msg ="积分明细 微信会员 m_id=".$value['m_id']."  商城会员 m_id".$value['m_parent']."\r\n";
						$this->logs($code,$msg);
					}
				}
			
				//总的积分
				$ary_members=D('Members')->field('m_id,total_point,freeze_point')->where(array('m_id'=>$value['m_parent']))->find();
				if(!empty($ary_members)){
					$total_point  = $ary_members['total_point'] + $value['total_point'];
					$freeze_point = $ary_members['freeze_point'] + $value['freeze_point'];
					if($total_point>0 ||  $freeze_point>0 ){
						$update_point_res = D('Members')->where(array('m_id'=>$value['m_parent']))->save(array('total_point'=>$total_point,'freeze_point'=>$freeze_point));
						$code='update_point_res';
						if($update_point_res==true){
							$msg =" 商城会员 m_id=".$value['m_parent']."  可用积分 ".$ary_members['total_point']."  冻结积分 ".$ary_members['freeze_point']."\r\n";
							$this->logs($code,$msg);
						}
						$update_point_wx_res =D('Members')->where(array('m_id'=>$value['m_id']))->save(array('total_point'=>0,'freeze_point'=>0));
						if($update_point_wx_res==true){
							$msg =" 微信会员 m_id=".$value['m_id']."  可用积分 ".$value['total_point']."  冻结积分 ".$value['freeze_point']."\r\n";
							$this->logs($code,$msg);
						}
					}
				}
			}
		}
	}
	
	/**
	 * 微信用户绑定商城用户优惠券迁移
	 * @author add by piupiu@126.com 
	 * @date 2016-08-10
	 */
	public function Wxtob2cmembercoupon(){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$where['open_source'] =array('eq','微信');
		$where['open_id'] =array('neq','');
		$where['m_parent'] =array('neq','0');
		$ary_wx_members=D('Members')->field('m_id,m_parent,open_source,open_id')->where($where)->select();
		if(!empty($ary_wx_members)){
			//积分明细
			foreach($ary_wx_members as $key=>$value){
				$coupon_res=D('Coupon')->field('c_id,c_user_id,c_used_id')->where(array('c_user_id'=>$value['m_id']))->select();
				if(!empty($coupon_res)){
					foreach($coupon_res as $k=>$val){
						if(!empty($val)){
							$code='update_coupon_log_res';
							if($val['c_user_id']!=''){
								$ary_update['c_user_id'] = $value['m_parent'];
							}
							if($val['c_used_id']!=''){
								$ary_update['c_used_id'] = $value['m_parent'];
							}
							$update_coupon_log_res = D('Coupon')->where(array('c_id'=>$val['c_id']))->save($ary_update);
							if($update_coupon_log_res==true){
								$msg ="优惠券 微信会员 m_id=".$value['m_id']."优惠券 c_id ".$val['c_id']."  商城会员 m_id".$value['m_parent']."\r\n";
								$this->logs($code,$msg);
							}
						}
					}
				}
			}
		}
	}
	/**
	 * 会员补偿积分脚本
	 * @author add by piupiu@126.com 
	 * @date 2016-08-21
	 */
	public function systomemberspoint(){
		@set_time_limit(0);
        @ignore_user_abort(TRUE);
		$where['o_status'] =array('eq','4');
		$ary_orders=D('Orders')->field('o_id,o_status,m_id,o_freeze_point,o_reward_point')->where($where)->select();
		if(!empty($ary_orders)){
			foreach($ary_orders as $key=>$value){
				$code='update_point_members_log_res';
				$ref_where['o_id']=$value['o_id'];
				$ref_res = D('OrdersRefunds')->where($ref_where)->order('or_create_time desc')->find();
				if($ref_res==''){//无退款完结订单
					$check_res = D('PointLog')->where(array('o_id'=>$value['o_id']))->find();
					if($check_res=='' && $value['o_reward_point'] >0){//是否已送
						M('', '', 'DB_CUSTOM')->startTrans();
						$ary_reward_result = D('PointConfig')->setMemberRewardPoint($value['o_reward_point'],$value['m_id'],$value['o_id']);
						if($ary_reward_result['result']){
							$msg ="会员 m_id=".$value['m_id']."订单 o_id ".$value['o_id']."  调整积分数".$value['o_reward_point']."\r\n";
							$this->logs($code,$msg);
						}
					}
				}
			}
		}
	}
	
	/**
	 * 会员补偿积分重复脚本
	 * @author add by piupiu@126.com 
	 * @date 2016-08-21
	 */
	 public function systreducememberspoint($int_start =1,$length =1000 ,$current=1){
		 @set_time_limit(0);
		 @ignore_user_abort(TRUE);
		 $where['type']='0';
		 $where['u_create']=array('gt','2015-11-21 19:00:00');
		 $where['reward_point']=array('gt','0');
		 $num = D('PointLog')->where($where)->count();
		 $mid_res= D('PointLog')->field('log_id,m_id,reward_point')->where($where)->limit($int_start, $length)->select();
		 $code='reduce_point_members_log_res';
		 foreach($mid_res as $key=>$value){
			if($value['m_id']!=''){
				$total_point = D('Members')->where(array('m_id' => $value['m_id']))->getField('total_point');
				$tmp_total_point = $total_point - $value['reward_point'];
				$int_return_members = D('Members')->where(array('m_id' => $value['m_id']))->save(array('total_point'=>$tmp_total_point));
				if($int_return_members==true){
					$total_point = D('PointLog')->where(array('log_id'=>$value['log_id']))->delete();
					$msg ="会员 m_id=".$value['m_id']."  调整前积分数".$total_point ."  积分数".$value['reward_point']."\r\n";
					$this->logs($code,$msg);
				}
			 }
			 $current++;
		 }
		 if($num > $current){
			$int_start += $current ;
			$length = 1000;
			$this->systreducememberspoint($int_start,$length,$current);
		}else{
			echo "ok";
		}
	 }
	 
	 //清空模板缓存
	 public function delFile(){
		 del_file(CI_SN);
	 }
}
?>
