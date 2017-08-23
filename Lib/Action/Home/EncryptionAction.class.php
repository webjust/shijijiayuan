<?php 

class EncryptionAction extends HomeAction {
	
	public function bulkUpdate() {
		set_time_limit(0);
		$csv_file = FXINC. '/Public/upload/csv/gy_client_domain_name.csv';
		//echo $csv_file;die;
		$file = fopen($csv_file,"r");
		while(! feof($file))
		{
			$csv_line = fgetcsv($file);
			if($csv_line[0]){				
				$url = 'http://'.$csv_line[0].'/Home/Encryption/index';
				unset($csv_line);
				echo $url.'<br/>';
				//continue;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$result = curl_exec($ch);
				if(!curl_errno($ch)){
				  $info = curl_getinfo($ch);
				  writeLog($info, 'encryption.log');
				} else {					
					writeLog($url .': '. curl_error($ch), 'encryption.log');
				} 
				curl_close($ch);
			}
			ob_flush();
			flush();
		}
	}
	
	public function index() {
		set_time_limit(0);
		$this->encodeAllCustomerMobile();
		$this->encodeAllDeliveryMobile();
		$this->encodeAllOrdersMobile();
		$this->encodeAllReceiveAddressMobile();
		$this->encodeAllShippingAddressMobile();		
		$this->encodeAllThdOrdersMobile();		
		$this->encodeAllMembersVerifyMobile();		
		$this->encodeAllInvoiceCollectMobile();		
	}
	
	public function encodeAllCustomerMobile() {
		
		$error = false;
		$membersModel = D('Members');
		$members = $membersModel->field('m_id,m_mobile,m_telphone')->select();
		//dump($members);die;
		$membersModel->startTrans();
		foreach($members as $member) {
		
			$mobile = $member['m_mobile'];
			$telephone = $member['m_telphone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['m_mobile'] = encrypt($mobile);
				writeLog($member['m_id'].",".$mobile.','.$save_data['m_mobile'],'encodeAllCustomerMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['m_mobile']);
				writeLog($member['m_id'].",".$mobile_desc.','.$save_data['m_mobile'],'encodeAllCustomerMobile_3.log');
				*/
			}
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['m_telphone'] = encrypt($telephone);
				writeLog($member['m_id'].",".$telephone.','.$save_data['m_telphone'],'encodeAllCustomerMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['m_telphone']);
				writeLog($member['m_id'].",".$telephone_desc.','.$save_data['m_telphone'],'encodeAllCustomerMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $membersModel -> data($save_data) 
					-> where(array('m_id'=>$member['m_id'])) 
					-> save();
				if($update_res === false) {
					$membersModel->rollback();
					writeLog($member['m_id'].",". $membersModel ->getLastSql() .",".preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllCustomerMobile_error.log');
					$error = 1;
					break;
				}
			}
			
		}
		if(!$error)
			$membersModel->commit();
	}

	public function encodeAllDeliveryMobile() {
	
		$error = false;
		$ordersDeliveryModel = D('OrdersDelivery');
		$orders_delivery = $ordersDeliveryModel->field('od_id,o_id,od_receiver_mobile,od_receiver_telphone')->select();
		
		$ordersDeliveryModel->startTrans();
		foreach($orders_delivery as $od) {
			$mobile = $od['od_receiver_mobile'];
			$telephone = $od['od_receiver_telphone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['od_receiver_mobile'] = encrypt($mobile);
				writeLog($od['od_id'].",".$od['o_id'].",".$mobile.','.$save_data['od_receiver_mobile'],'encodeAllDeliveryMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['od_receiver_mobile']);
				writeLog($od['od_id'].",".$od['o_id'].",".$mobile_desc.','.$save_data['od_receiver_mobile'],'encodeAllDeliveryMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['od_receiver_telphone'] = encrypt($telephone);
				writeLog($od['od_id'].",".$od['o_id'].",".$mobile.','.$save_data['od_receiver_telphone'],'encodeAllDeliveryMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['od_receiver_telphone']);
				writeLog($od['od_id'].",".$od['o_id'].",".$telephone_desc.','.$save_data['od_receiver_telphone'],'encodeAllDeliveryMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $ordersDeliveryModel -> data($save_data) 
					-> where(array('od_id'=>$od['od_id'])) 
					-> save();
				if($update_res === false) {
					$ordersDeliveryModel->rollback();
					writeLog($od['od_id'].",".$od['o_id'].",".$ordersDeliveryModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllDeliveryMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$ordersDeliveryModel->commit();
	}
	
	public function encodeAllOrdersMobile() {
	
		$error = false;
		$ordersModel = D('Orders');
		$orders = $ordersModel->field('o_id,o_receiver_mobile,o_receiver_telphone')->select();
		
		$ordersModel->startTrans();
		foreach($orders as $order) {
			$mobile = $order['o_receiver_mobile'];
			$telephone = $order['o_receiver_telphone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['o_receiver_mobile'] = encrypt($mobile);
				writeLog($order['o_id'].",".$mobile.','.$save_data['o_receiver_mobile'],'encodeAllOrdersMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['o_receiver_mobile']);
				writeLog($order['o_id'].",".$mobile_desc.','.$save_data['o_receiver_mobile'],'encodeAllOrdersMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['o_receiver_telphone'] = encrypt($telephone);
				writeLog($order['o_id'].",".$mobile.','.$save_data['o_receiver_telphone'],'encodeAllOrdersMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['o_receiver_telphone']);
				writeLog($order['o_id'].",".$telephone_desc.','.$save_data['o_receiver_telphone'],'encodeAllOrdersMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $ordersModel -> data($save_data) 
					-> where(array('o_id'=>$order['o_id'])) 
					-> save();
				if($update_res === false) {
					$ordersModel->rollback();
					writeLog($order['o_id'].",".$ordersModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllOrdersMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$ordersModel->commit();
	}
	
	public function encodeAllReceiveAddressMobile() {
	
		$error = false;
		$receiveAddressModel = D('ReceiveAddress');
		$receiveAddress = $receiveAddressModel->field('ra_id,ra_mobile_phone,ra_phone')->select();
		
		$receiveAddressModel->startTrans();
		foreach($receiveAddress as $address) {
			$mobile = $address['ra_mobile_phone'];
			$telephone = $address['ra_phone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['ra_mobile_phone'] = encrypt($mobile);
				writeLog($address['ra_id'].",".$mobile.','.$save_data['ra_mobile_phone'],'encodeAllreceiveAddressMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['ra_mobile_phone']);
				writeLog($address['ra_id'].",".$mobile_desc.','.$save_data['ra_mobile_phone'],'encodeAllreceiveAddressMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['ra_phone'] = encrypt($telephone);
				writeLog($address['ra_id'].",".$mobile.','.$save_data['ra_phone'],'encodeAllreceiveAddressMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['ra_phone']);
				writeLog($address['ra_id'].",".$telephone_desc.','.$save_data['ra_phone'],'encodeAllreceiveAddressMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $receiveAddressModel -> data($save_data) 
					-> where(array('ra_id'=>$address['ra_id'])) 
					-> save();
				if($update_res === false) {
					$receiveAddressModel->rollback();
					writeLog($address['ra_id'].",".$receiveAddressModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllreceiveAddressMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$receiveAddressModel->commit();
	}
	
	public function encodeAllShippingAddressMobile() {
	
		$error = false;
		$shippingAddressModel = D('ShippingAddress');
		$hippingAddress = $shippingAddressModel->field('sh_id,sh_mobile_phone,sh_phone')->select();
		
		$shippingAddressModel->startTrans();
		foreach($hippingAddress as $address) {
			$mobile = $address['sh_mobile_phone'];
			$telephone = $address['sh_phone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['sh_mobile_phone'] = encrypt($mobile);
				writeLog($address['sh_id'].",".$mobile.','.$save_data['sh_mobile_phone'],'encodeAllreceiveAddressMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['sh_mobile_phone']);
				writeLog($address['sh_id'].",".$mobile_desc.','.$save_data['sh_mobile_phone'],'encodeAllreceiveAddressMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['sh_phone'] = encrypt($telephone);
				writeLog($address['sh_id'].",".$mobile.','.$save_data['sh_phone'],'encodeAllreceiveAddressMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['sh_phone']);
				writeLog($address['sh_id'].",".$telephone_desc.','.$save_data['sh_phone'],'encodeAllreceiveAddressMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $shippingAddressModel -> data($save_data) 
					-> where(array('sh_id'=>$address['sh_id'])) 
					-> save();
				if($update_res === false) {
					$shippingAddressModel->rollback();
					writeLog($address['sh_id'].",".$shippingAddressModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllreceiveAddressMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$shippingAddressModel->commit();
	}
	
	public function encodeAllThdOrdersMobile() {
	
		$error = false;
		$thdOrdersModel = D('ThdOrders');
		$thdOrders = $thdOrdersModel->field('to_id,to_receiver_mobile,to_receiver_phone')->select();
		
		$thdOrdersModel->startTrans();
		foreach($thdOrders as $order) {
			$mobile = $order['to_receiver_mobile'];
			$telephone = $order['to_receiver_phone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['to_receiver_mobile'] = encrypt($mobile);
				writeLog($order['to_id'].",".$mobile.','.$save_data['to_receiver_mobile'],'encodeAllThdOrdersMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['to_receiver_mobile']);
				writeLog($order['to_id'].",".$mobile_desc.','.$save_data['to_receiver_mobile'],'encodeAllThdOrdersMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['to_receiver_phone'] = encrypt($telephone);
				writeLog($order['to_id'].",".$mobile.','.$save_data['to_receiver_phone'],'encodeAllThdOrdersMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['to_receiver_phone']);
				writeLog($order['to_id'].",".$telephone_desc.','.$save_data['to_receiver_phone'],'encodeAllThdOrdersMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $thdOrdersModel -> data($save_data) 
					-> where(array('to_id'=>$order['to_id'])) 
					-> save();
				if($update_res === false) {
					$thdOrdersModel->rollback();
					writeLog($order['to_id'].",".$thdOrdersModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllThdOrdersMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$thdOrdersModel->commit();
	}
	
	public function encodeAllMembersVerifyMobile() {
	
		$error = false;
		$membersVerifyModel = D('MembersVerify');
		$membersVerify = $membersVerifyModel->field('m_id,m_mobile,m_telphone')->select();
		
		$membersVerifyModel->startTrans();
		foreach($membersVerify as $member) {
			$mobile = $member['m_mobile'];
			$telephone = $member['m_telphone'];
			$save_data = array();
			//加密存储手机
			if($mobile && !strpos($mobile, ':')) {
				$save_data['m_mobile'] = encrypt($mobile);
				writeLog($member['m_id'].",".$mobile.','.$save_data['m_mobile'],'encodeAllMembersVerifyMobile_1.log');
				/*
				$mobile_desc = decrypt($save_data['m_mobile']);
				writeLog($member['m_id'].",".$mobile_desc.','.$save_data['m_mobile'],'encodeAllMembersVerifyMobile_3.log');
				*/
			}
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['m_telphone'] = encrypt($telephone);
				writeLog($member['m_id'].",".$mobile.','.$save_data['m_telphone'],'encodeAllMembersVerifyMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['m_telphone']);
				writeLog($member['m_id'].",".$telephone_desc.','.$save_data['m_telphone'],'encodeAllMembersVerifyMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $membersVerifyModel -> data($save_data) 
					-> where(array('m_id'=>$member['m_id'])) 
					-> save();
				if($update_res === false) {
					$membersVerifyModel->rollback();
					writeLog($member['m_id'].",".$membersVerifyModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllMembersVerifyMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$membersVerifyModel->commit();
	}
	
	public function encodeAllInvoiceCollectMobile() {
	
		$error = false;
		$invoiceCollectModel = D('InvoiceCollect');
		$invoiceCollect = $invoiceCollectModel->field('id,invoice_phone')->select();
		
		$invoiceCollectModel->startTrans();
		foreach($invoiceCollect as $invoice) {
			$telephone = $invoice['invoice_phone'];
			$save_data = array();			
			
			//加密存储固话
			if($telephone && !strpos($telephone, ':')) {
				$save_data['invoice_phone'] = encrypt($telephone);
				writeLog($invoice['id'].','.$save_data['invoice_phone'],'encodeAllInvoiceCollectMobile_2.log');
				/*
				$telephone_desc = decrypt($save_data['invoice_phone']);
				writeLog($invoice['id'].",".$telephone_desc.','.$save_data['invoice_phone'],'encodeAllInvoiceCollectMobile_4.log');
				*/
			}
			
			//测试时，不更新数据库
			//continue;
			
			if(!empty($save_data)) {
				$update_res = $invoiceCollectModel -> data($save_data) 
					-> where(array('id'=>$invoice['id'])) 
					-> save();
				if($update_res === false) {
					$invoiceCollectModel->rollback();
					writeLog($invoice['id'].",".$invoiceCollectModel->getLastSql().','. preg_replace('/\r\n/is','',var_export($save_data, true)),'encodeAllInvoiceCollectMobile_error.log');
					$error = 1;
					break;
				}
			}
		}
		if(!$error)
			$invoiceCollectModel->commit();
	}

}