<?php
/**
 * 销货收款单管理
 *
 * @subpackage Voucher
 * @package Action
 * @stage 7.2
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-06-03
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class VoucherAction extends AdminAction{
    private $name;

    /**
     * 默认控制器
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-03
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU8_6'));
    }
    
    /**
     * 结余款类型列表
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-03
     */
    public function index(){
        $this->redirect(U('Admin/Voucher/pageList'));
    }
    
    /**
     * 销货收款单列表
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-03
     */
    public function pageList(){
        $ary_get = $this->_get();
        $ary_get['val'] = trim($ary_get['val']);
 		$this->getSubNav(7, 6, 10,'销货收款单列表');
        $ary_where = '';
        if(isset($ary_get['sr_type']) && $ary_get['sr_type'] != 'select'){
            $ary_where = C("DB_PREFIX").'sales_receipts.`sr_type`='.$ary_get['sr_type'] ." AND";
        }
        if(isset($ary_get['sr_verify_status']) && $ary_get['sr_verify_status'] != 'select'){
        	if($ary_get['sr_verify_status'] == '2'){
        		$ary_where = C("DB_PREFIX").'sales_receipts.`sr_status`=0' ." AND";
        	}else{
        		$ary_where = C("DB_PREFIX").'sales_receipts.`sr_verify_status`='.$ary_get['sr_verify_status'] ." AND";
        	}
        }
        //制单时间
        if(!empty($ary_get['starttime'])){
            if(!empty($ary_get['endtime'])){
                if($ary_get['endtime'] >= $ary_get['starttime']){
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_create_time` BETWEEN '". $ary_get['starttime'] . "' AND '".$ary_get['endtime']."' AND";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$ary_get['starttime']."'  AND";
                }
            }else{
                $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_create_time` >='". $ary_get['starttime']."'  AND";
            }
        }else{
            if(!empty($ary_get['endtime'])){
                $date = date("Y-m-d H:i:s");
                if($ary_get['endtime'] >= $date){
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_create_time` BETWEEN '". $date . "' AND '".$ary_get['endtime']."'  AND";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$date."'  AND";
                }
            }
        }
        if(!empty($ary_get['val']) && isset($ary_get['val'])){
            switch ($ary_get['field']){
                case 'm_name':
                    $ary_where .= " ".C("DB_PREFIX")."members.`m_name` LIKE '%".$ary_get['val']."%'";
                    break;
                case 'o_id':
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`o_id`='".$ary_get['val']."'";
                    break;
                case 'sr_id':
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_id`='".$ary_get['val']."'";
                    break;     
                case 'sr_bank_sn':
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_bank_sn`='".$ary_get['val']."'";
                    break;
                case 'sr_logistics_sn':
                    $ary_where .= " ".C("DB_PREFIX")."sales_receipts.`sr_logistics_sn`='".$ary_get['val']."'";
                    break;
            }
        }
        $count = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
                                ->field(" ".C("DB_PREFIX")."sales_receipts.*,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."sales_receipts.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."sales_receipts.`sr_create_uid`=".C("DB_PREFIX")."admin.`u_id`")
                                ->order(" ".C("DB_PREFIX")."sales_receipts.`sr_update_time` DESC")
                                ->where(trim($ary_where,"AND"))->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->show();
        $ary_data = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
	                                ->field(" ".C("DB_PREFIX")."sales_receipts.*,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
	                                ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."sales_receipts.`m_id`=".C("DB_PREFIX")."members.`m_id`")
	                                ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."sales_receipts.`sr_create_uid`=".C("DB_PREFIX")."admin.`u_id`")
	                                ->order(" ".C("DB_PREFIX")."sales_receipts.`sr_update_time` DESC")
                                    ->where(trim($ary_where,"AND"))
                                    ->limit($obj_page->firstRow, $obj_page->listRows)
                                    ->select();
		
		foreach($ary_data as $key=>$val){
			$ary_data[$key]['status']=D('Orders')->where(array('o_id'=>$val['o_id']))->getField('o_status');
		}
        $this->assign("page",$page);
        $this->assign("filter",$ary_get);
        $this->assign("data",  $ary_data);
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
		$this->assign('pay_name',$pay_name);		
        $this->display();
    }
    
    /**
     * 添加销货收款单
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-03
     */
    public function addVoucher(){
        $this->getSubNav(7, 6, 20,'新增销货收款单');
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
		$this->assign('pay_name',$pay_name);
        $this->display();
    }
    
	/**
     * 添加操作
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-06
     */
    public function doAdd(){
        $ary_data = $this->_post(); 
        $ary_data['o_id'] = trim($ary_data['o_id']);
        $ary_data['sr_bank_sn'] = trim($ary_data['sr_bank_sn']);
        $ary_data['sr_create_type'] = '0';
        $ary_data['sr_create_uid'] = $_SESSION['Admin'];
        $ary_data['sr_create_time'] = date("Y-m-d H:i:s");
        $ary_data['sr_update_time'] = date("Y-m-d H:i:s");
        $ary_data['sr_verify_status'] = '0';
        if($ary_data['sr_type'] == 'select'){
        	$this->error('销货类型必须选择');
        }
        if (!is_float($ary_data['to_post_balance']) && !is_numeric($ary_data['to_post_balance']) && ($ary_data['to_post_balance']<=0)) {
            $this->error('金额格式必须正确且大于0！');
        }    
        if($ary_data['sr_type'] == '0'){
        	unset($ary_data['sr_logistics_sn']);
            if( !($ary_data['o_id']) || !($ary_data['sr_remitter']) || !($ary_data['sr_bank']) || !($ary_data['to_post_balance']) || !($ary_data['sr_bank_sn']) || !($ary_data['sr_remit_time']) ) {
                $this->error('缺乏必要参数！');
            }
           //流水帐号在有效的单据中不可重复
	       $ary_count = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
		        ->where(array('sr_bank_sn'=>$ary_data['sr_bank_sn'],'sr_status'=>'1'))->count();
	       if($ary_count>0){
	        	$this->error('流水帐号已存在,不可重复！');
	            exit;
	       }   
        }   
        if($ary_data['sr_type'] == '1'){
        	unset($ary_data['sr_bank_sn']);
        	unset($ary_data['sr_bank']);
        	unset($ary_data['sr_remitter']);
            if(!($ary_data['o_id']) && !($ary_data['sr_logistics_sn']) ){
            	$this->error('订单和物流单至少填一个！');
            }
            $deli_where = array();
	        if($ary_data['o_id']){
	        	$deli_where['o_id'] = $ary_data['o_id'];
	        }   
	        if($ary_data['sr_logistics_sn']){
	        	$deli_where['od_logi_no'] = $ary_data['sr_logistics_sn'];
	        } 
	        //过滤掉子订单
	        $deli_where['initial_o_id'] = 0;
            $count =  M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where($deli_where)->count();
            if($count<=0) {
                $this->error('未找到发货单！');
            }        
        }    
        $result = $this->validateTrade(array('o_id'=>$ary_data['o_id']),$ary_data);
        if($result['status'] == '0'){
        	$this->error($result['msg']);
        	exit;
        }
        if($result['data']['o_all_price']>$ary_data['to_post_balance']){
        	$this->error('输入的金额小于订单应付金额，不能生成销货单');
        }
		if($result['data']['o_all_price']<$ary_data['to_post_balance']){
        	$this->error('输入的金额大于订单应付金额，不能生成销货单');
        }
        $ary_data['m_id'] = $result['data']['m_id'];
        M('', '', 'DB_CUSTOM')->startTrans();
        $res =  M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_data);
        if(!$res){
            M('', '', 'DB_CUSTOM')->rollback();
             $this->error(' 添加失败');
        }else {
	        $this->writeVoucherLog(array(
	        	'sr_id'=>$res,
	        	'srml_type'=>'0',
	        	'srml_uid'=>$ary_data['sr_create_uid'],
	        	'srml_change'=>json_encode($ary_data),
	        	'srml_create_time'=>date("Y-m-d H:i:s")
	        ));
            //订单日志记录
            $ary_orders_log = array(
                'o_id' => $ary_data['o_id'],
                'ol_behavior' => '支付成功',
                'ol_uname' => "管理员：" . $_SESSION['admin_name'],
                'ol_create' => date('Y-m-d H:i:s')
            );
            $obj_res_orders_log = D('OrdersLog')->add($ary_orders_log);
            if(FALSE === $obj_res_orders_log){
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error('更新订单日志失败！');
            }
            M('', '', 'DB_CUSTOM')->commit();
            $this->success('添加成功', U('Admin/Voucher/pageList'));
        }
    }

    
    /**
     * 选择会员
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-03
     */
    public function selectMembers(){
        $ary_post = $this->_post();
        $ary_where = array();
        $ary_where['o_id'] = trim($ary_post['tid']);
        $result = $this->validateTrade($ary_where,$ary_post);
        if($result){
        	$this->ajaxReturn(array('status'=>$result['status'],'msg'=>$result['msg'],'data'=>$result['data']));
        }
    }
    
    /**
     * 验证订单状态如何
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-07
     */
    public function validateTrade($ary_where,$ary_data,$type){
        if(strlen($ary_where['o_id'])<1 || strlen($ary_where['o_id'])>18){
        	$result = array('status'=>'0','msg'=>'不存在此订单号','data'=>'');
        	return $result;
        }
        $ary_data = M('Orders',C('DB_PREFIX'),'DB_CUSTOM')->field('m.m_name,fx_orders.m_id,fx_orders.o_pay_status,o_all_price,p.pc_pay_type,o_status,m.m_balance')
        ->join("fx_members as m on(m.m_id=fx_orders.m_id) ")
        ->join("fx_payment_cfg as p on(p.pc_id=fx_orders.o_payment) ")
        ->where($ary_where)->find();
        if(empty($ary_data)){
        	$result = array('status'=>'0','msg'=>'不存在此订单号','data'=>'');
        	return $result;
        }
        if($ary_data['o_pay_status'] != '0' && $ary_data['o_status'] != '1'){
        	$result = array('status'=>'0','msg'=>'订单必须是未支付的活动订单','data'=>'');
        	return $result;
        }       
        if(($ary_data['pc_pay_type'] == 'offline') && ($ary_data['sr_type'] == '1')){
        	$result = array('status'=>'0','msg'=>'此订单号对应的销货类型为线下支付','data'=>'');
        	return $result;        	
        }
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
        if(($ary_data['pc_pay_type'] == 'cash_delivery') && ($ary_data['sr_type'] == '0')){
        	$result = array('status'=>'0','msg'=>'此订单号对应的销货类型为'.$pay_name,'data'=>'');
        	return $result;        	
        }       
        if($ary_data['pc_pay_type'] != 'offline' && $ary_data['pc_pay_type'] != 'cash_delivery'){
        	$result = array('status'=>'0','msg'=>'您输入的订单号支付类型必须为线下支付或'.$pay_name,'data'=>'');
        	return $result;
        }
        $total_count = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        if($total_count>=1){
        	if($total_count==1){
        		if($type != 1){
        			$result = array('status'=>'0','msg'=>'已存在这个的销货单','data'=>'');
        			return $result;
        		}
        	}else{
 	        	$result = array('status'=>'0','msg'=>'已存在这个的销货单','data'=>'');  
 	        	return $result;      		
        	}     	
        }
        $result = array('status'=>'1','msg'=>'获取成功','data'=>$ary_data);
        return $result;
    }
    
    /**
     *编辑页面显示
     * @author wangguibin
     * @date 2013-06-06
     */
    public function pageEdit(){
        $this->getSubNav(7,6,20,'销货收款单编辑');
        $sr_id=$this->_get('sr_id');  
        if(isset($sr_id)){
            $ary_where =  array('sr_id'=>$sr_id);
            $ary_category =  M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
            $this->assign('data',$ary_category);
			$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
			$pay_name = '货到付款';
			if($is_zt['IS_ZT']['sc_value'] == 1){
				$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
			}
			$this->assign('pay_name',$pay_name);			
            $this->display();
        }else {
            $this->error('参数错误');
        }
    }
    /**
     * 分类编辑操作
     * @author wangguibin
     * @date 2013-06-06
     */
    public function doEdit(){
        $ary_data = $this->_post();
        $ary_data['o_id'] = trim($ary_data['o_id']);
        $ary_data['sr_bank_sn'] = trim($ary_data['sr_bank_sn']);
        $sr_id = $ary_data['sr_id'];
        $ary_data['sr_create_uid'] = $_SESSION['Admin'];
        $ary_data['sr_update_time'] = date("Y-m-d H:i:s");
        if($ary_data['sr_type'] == 'select'){
        	$this->error('销货类型必须选择');
        }
        if (!is_float($ary_data['to_post_balance']) && !is_numeric($ary_data['to_post_balance']) && ($ary_data['to_post_balance']<=0)) {
            $this->error('金额格式必须正确且大于0！');
        }    
        if($ary_data['sr_type'] == '0'){
        	unset($ary_data['sr_logistics_sn']);
            if( !($ary_data['o_id']) || !($ary_data['sr_remitter']) || !($ary_data['sr_bank']) || !($ary_data['to_post_balance']) || !($ary_data['sr_bank_sn']) || !($ary_data['sr_remit_time']) ) {
                $this->error('缺乏必要参数！');
            }
           //流水帐号在有效的单据中不可重复
	       $ary_count = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
		        ->where(array('sr_bank_sn'=>$ary_data['sr_bank_sn'],'sr_status'=>'1','_string'=>'sr_id !='.$sr_id))->count();
	       if($ary_count>0){
	        	$this->error('流水帐号已存在,不可重复！');
	            exit;
	       }  
        }   
        if($ary_data['sr_type'] == '1'){
        	unset($ary_data['sr_bank_sn']);
        	unset($ary_data['sr_bank']);
        	unset($ary_data['sr_remitter']);
            if(!($ary_data['o_id']) && !($ary_data['sr_logistics_sn']) ){
            	$this->error('订单和物流单至少填一个！');
            }
            $deli_where = array();
	        if($ary_data['o_id']){
	        	$deli_where['o_id'] = $ary_data['o_id'];
	        }   
	        if($ary_data['sr_logistics_sn']){
	        	$deli_where['od_logi_no'] = $ary_data['sr_logistics_sn'];
	        } 
	        //过滤掉子订单
	        $deli_where['initial_o_id'] = 0;
            $count =  M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->where($deli_where)->count();
            if($count<=0) {
                $this->error('未找到发货单！');
            }        
        }    
        $result = $this->validateTrade(array('o_id'=>$ary_data['o_id']),$ary_data,1);
        if($result['status'] == '0'){
        	$this->error($result['msg']);
        	exit;
        }
        if($result['data']['o_all_price']>$ary_data['to_post_balance']){
        	$this->error('输入的金额小于订单应付金额，不能生成销货单');
        }
		if($result['data']['o_all_price']<$ary_data['to_post_balance']){
        	$this->error('输入的金额大于订单应付金额，不能生成销货单');
        }
        $ary_data['m_id'] = $result['data']['m_id'];
        if(isset($sr_id)){
            $res =  M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sr_id'=>$sr_id))->save($ary_data);
	        if(!$res){
	             $this->error('修改失败');
	        }else {
		         $this->writeVoucherLog(array(
		        	'sr_id'=>$sr_id,
		        	'srml_type'=>'1',
		        	'srml_uid'=>$ary_data['sr_create_uid'],
		        	'srml_change'=>json_encode($ary_data),
		        	'srml_create_time'=>date("Y-m-d H:i:s")
		         ));
	             $this->success('修改成功', U('Admin/Voucher/pageList'));
	        }
        }else {
            $this->error('参数错误');
        }   
    }
    
    public function getVoucher($params = array()){
        if(!empty($params['bi_sns']) && isset($params['bi_sns'])){
            $ary_where['sr_id'] = array('in',$params['bi_sns']);
        }
        $ary_data = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
	                                ->field(" ".C("DB_PREFIX")."sales_receipts.*,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
	                                ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."sales_receipts.`m_id`=".C("DB_PREFIX")."members.`m_id`")
	                                ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."sales_receipts.`sr_create_uid`=".C("DB_PREFIX")."admin.`u_id`")
                    ->where(trim($ary_where,"AND"))
                    ->select();
        return $ary_data;
    }
     
    /**
     * 导出销货收款单
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-07
     * 
     */
    public function explortVoucher(){
        $ary_post = $this->_post();
        if(!empty($ary_post['bi_sns']) && isset($ary_post['bi_sns'])){
            $ary_data = $this->getVoucher($ary_post);
            $contents = array();
            $fields = array();
            $header = array('单据编号', '单据状态', '类型名称', '会员名', '金额', '制单人', '制单日期', '汇款时间','流水号','订单号','物流单号','是否已作废','备注');
            if(!empty($ary_data) && is_array($ary_data)){
				$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
				$pay_name = '货到付款';
				if($is_zt['IS_ZT']['sc_value'] == 1){
					$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
				}
                foreach($ary_data as $ky=>$vl){
                    $str = ($vl['sr_verify_status']) ? '已确认' : '未确认';
                    $status = ($vl['sr_status'] == '1') ? '正常' : '已作废';
                    $type = $vl['sr_type'] ==  '1' ? $pay_name : '线下支付';
                    $contents[] = array(
                        "" . $vl['sr_id'],
                        $str,
                        $type,
                        $vl['m_name'],
                        ''.sprintf('%.2f',$vl['to_post_balance']),
                        $vl['u_name'],
                        $vl['sr_create_time'],
                        $vl['sr_remit_time'],
                        $vl['sr_bank_sn'],
                        $vl['o_id'],
                        $vl['sr_logistics_sn'],
                        $status,
                        $vl['sr_remark']
                    );
                }
                $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M');
            }
            @mkdir('./Public/Uploads/' . CI_SN.'/excel/');
            $Export = new Export(date('YmdHis') . '.xls', 'Public/Uploads/'.CI_SN.'/excel/');
            $excel_file = $Export->exportExcel($header, $contents, $fields, $mix_sheet = '销货收款单信息', true);
            if (!empty($excel_file)) {
                $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
            } else {
                $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'请选择需要导出的单据编号'));
        }
    }
    
    /**
     * 处理审核状态
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-04
     */
    public function doStatus(){
        $ary_post = $this->_post();
        $sale_obj = M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM');
        if(!empty($ary_post['tid']) && isset($ary_post['tid'])){
            if($ary_post['type'] == 'del'){
                $ary_data =  $sale_obj->where(array('sr_id'=>$ary_post['tid'],'sr_verify_status'=>'1','sr_status'=>'1'))->find();
                if(!empty($ary_data) && is_array($ary_data)){
                    $this->ajaxReturn(array('status'=>'0','info'=>'该单据已确认不能作废'));
                    exit;
                }else{
                	M('', '', 'DB_CUSTOM')->startTrans();
                	$ary_data = array('sr_status'=>'0','sr_verify_uid'=>$_SESSION['Admin'],'sr_verify_date'=>date("Y-m-d H:i:s"),'sr_update_time'=>date("Y-m-d H:i:s"));
                    $ary_result = $sale_obj
                    ->where(array('sr_id'=>$ary_post['tid'],'sr_status'=>'1'))
                    ->data($ary_data)->save();
                    if(FALSE != $ary_result){
				      	$params = array(
			        	'sr_id'=>$res1,
			        	'srml_type'=>'3',
			        	'srml_uid'=>$_SESSION['Admin'],
			        	'srml_change'=>json_encode($ary_data),
			        	'srml_create_time'=>date("Y-m-d H:i:s")
                        );
                        $this->writeVoucherLog($params);
                        M('', '', 'DB_CUSTOM')->commit();
                        $this->ajaxReturn(array('status'=>'1','info'=>'作废成功'));
                        exit;
                    }else{
						M('', '', 'DB_CUSTOM')->rollback();
                        $this->ajaxReturn(array('status'=>'0','info'=>'作废失败'));
                        exit;
                    }
                    M('', '', 'DB_CUSTOM')->commit();
                }
            }else{
            	if($ary_post['type'] == 'conf'){
					M('', '', 'DB_CUSTOM')->startTrans();
					$ary_data1 =  $sale_obj->where(array('sr_id'=>$ary_post['tid'],'sr_verify_status'=>'1','sr_status'=>'1'))->find();
	                $ary_data = array('sr_verify_status'=>'1','sr_verify_uid'=>$_SESSION['Admin'],'sr_verify_date'=>date("Y-m-d H:i:s"),'sr_update_time'=>date("Y-m-d H:i:s"));
	                $ary_result = $sale_obj
	                    ->where(array('sr_id'=>$ary_post['tid'],'sr_status'=>'1'))
	                    ->data($ary_data)->save();
	                 if(FALSE != $ary_result){
	                    $ary_data =  $sale_obj->where(array('sr_id'=>$ary_post['tid'],'sr_verify_status'=>'1','sr_status'=>'1'))->find();
		                if(!empty($ary_data1) && is_array($ary_data1)){
		                    $this->ajaxReturn(array('status'=>'0','info'=>'该单据已确认'));
		                    exit;
		                }else{
		                    $order_obj = M('orders',C('DB_PREFIX'),'DB_CUSTOM');
		                    $order_info = $order_obj->field('o_pay,o_pay_status,o_all_price,o_status,m_id')->where(array('o_id'=>$ary_data['o_id']))->find();
		                    if(!empty($order_info)){
		                    	if($order_info['o_pay_status'] != 0 ){
		                    		M('', '', 'DB_CUSTOM')->rollback();
		                    		 $this->ajaxReturn(array('status'=>'0','info'=>'确认失败，此订单状态不是未付款状态'));
		                    		 exit;
		                    	}
		                    	if($order_info['o_status'] !='1'){
									if($order_info['o_status'] !='5'){
										M('', '', 'DB_CUSTOM')->rollback();
										$this->ajaxReturn(array('status'=>'0','info'=>'确认失败，此订单状态为非活动状态'));
										exit;										
									}
		                    	}
		                    	$res1 = $order_obj->where(array('o_id'=>$ary_data['o_id']))
		                    	->data(array('o_pay_status'=>'1','o_pay'=>$order_info['o_all_price'],'o_update_time'=>date("Y-m-d H:i:s")))
		                    	->save();
								//订单日志记录
								$ary_orders_log = array(
									'o_id' => $ary_data['o_id'],
									'ol_behavior' => '线下支付支付成功',
									'ol_uname' => $_SESSION['admin_name'],
									'ol_create' => date('Y-m-d H:i:s')
								);
								$res_orders_log = D('OrdersLog')->add($ary_orders_log);
		                    	if($res1){
                                    //送优惠券
                                    $ary_pdt_info=array();
                                    $orders_item_data = D('Orders')->getOrdersItem(array('o_id'=>$ary_data['o_id']),array('pdt_id','oi_nums','oi_type'));
                                    $item_info = array();
                                    foreach($orders_item_data as $value){
                                        $key=$value['pdt_id'];
                                        $item_info[$key]['pdt_id']=$value['pdt_id'];
                                        $item_info[$key]['num']=$value['oi_nums'];
                                        $item_info[$key]['type']=$value['oi_type'];
                                    }
                                    $item_pdt_info = D('Cart')->getProductInfo($item_info);
                                    $ary_pdt_info['rule_info']=array('pmn_id'=>null,'again_discount'=>null);
                                    foreach($item_pdt_info as &$info){
                                        if(!empty($info['rule_info']['pmn_id']) && empty($ary_pdt_info['rule_info']['pmn_id'])){
                                            $ary_pdt_info['rule_info']=$info['rule_info'];
                                        }
                                        $ary_pdt_info[$info['pdt_id']]=array('pdt_id'=>$info['pdt_id'],'num'=>$info['pdt_nums'],'type'=>$info['type'],'price'=>$info['f_price']);
                                    }
                                    $ary_param=array('action'=>'paymentPage','mid'=>$order_info['m_id'],'all_price'=>$order_info['o_all_price'],'ary_pdt'=>$ary_pdt_info);
                                    $promotion_result=D('Price')->getOrderPrice($ary_param);
                                    if (!empty($promotion_result['coupon_sn'])) {
                                        $ary_copon = D('Coupon')->where(array('cid' => $promotion_result['coupon_sn']))->find();
                                        //如果满足促销规则送优惠券 将送到
                                        $ary_copon_orders = array(
                                            'coupon_sn' => $ary_copon['c_sn'],
                                            'o_coupon' => 1,
                                            'coupon_value' => $ary_copon['c_money'],
                                            'coupon_start_date' => $ary_copon['c_start_time'],
                                            'coupon_end_date' => $ary_copon['c_end_time']
                                        );
                                        $res_orders_coupon = D('orders')->where(array('o_id' => $ary_data['o_id']))->save($ary_copon_orders);
                                        if (!$res_orders_coupon) {
                                            M('', '', 'DB_CUSTOM')->rollback();
                                            $this->ajaxReturn(array('status'=>'0','info'=>'确认失败，优惠券修改失败'));
                                            exit;
                                        }
                                    }
                                    $params = array(
                                    'sr_id'=>$res1,
                                    'srml_type'=>'2',
                                    'srml_uid'=>$_SESSION['Admin'],
                                    'srml_change'=>json_encode($ary_data),
                                    'srml_create_time'=>date("Y-m-d H:i:s")
                                    );
                                    $this->writeVoucherLog($params);
                                    //获取订单详情
                                    $array_orders_itme = D('OrdersItems')->where(array('o_id'=>$ary_data['o_id']))->find();
                                   
                                    if($array_orders_itme['oi_type'] == 5){
                                        //团购订单支付
										/**
                                        $gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id' => $array_orders_itme['fc_id']))->getField('gp_now_number');
                                        M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'gp_id' => $array_orders_itme['fc_id']
                                        ))->save(array(
                                            'gp_now_number' => $gp_now_number + $array_orders_itme ['oi_nums']
                                        ));**/
                                    }elseif($array_orders_itme['oi_type'] == 7){
                                        //秒杀订单支付
										/**
                                        $sp_now_number = M('Spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('sp_id' => $array_orders_itme['fc_id']))->getField('sp_now_number');
                                         
                                        M('Spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'sp_id' => $array_orders_itme['fc_id']
                                        ))->save(array(
                                            'sp_now_number' => $sp_now_number + 1
                                        ));**/
                                    }elseif($array_orders_itme['oi_type'] == 8){
                                        //预售订单支付
                                        $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' => $array_orders_itme['fc_id']))->getField('p_now_number');
                                        M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                            'p_id' => $array_orders_itme['fc_id']
                                        ))->save(array(
                                            'p_now_number' => $p_now_number + $array_orders_itme ['oi_nums']
                                        ));
                                    }
                                    M('', '', 'DB_CUSTOM')->commit();
                                    $this->ajaxReturn(array('status'=>'1','info'=>'确认成功'));
                                    exit;
		                    	}else{
		                    		M('', '', 'DB_CUSTOM')->rollback();
		                    		$this->ajaxReturn(array('status'=>'0','info'=>'作废失败'));
		                    		exit;
		                    	}
		                    }
						}                    
	                 }else{
	                     M('', '', 'DB_CUSTOM')->rollback();
	                    $this->ajaxReturn(array('status'=>'0','info'=>'确认失败'));
	                    exit;                	
	                 }
					M('', '', 'DB_CUSTOM')->commit();            		
            	}else{
                   $this->ajaxReturn(array('status'=>'0','info'=>'操作类型未知'));
                   exit;           		
            	}
			}
		}else{
            $this->error("参数有误,请重试...");
        }
    }
    
    /**
     * 记录审核日志
     * @author Wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-06-04
     */
    public function writeVoucherLog($params){
        M('sales_receipts_modify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
    

    /**
     * 销货收款单详情
     * @author wangguibin<wanghui@guanyisoft.com>
     * @date 2013-06-05
     */
    public function detailVoucher(){
        $ary_get = $this->_get();
        $this->getSubNav(7, 6, 20);
        $sr_id = $ary_get['sr_id'];
        if(!empty($sr_id) && isset($sr_id)){
            $ary_where =  array('sr_id'=>$sr_id);
            $ary_data =  M('sales_receipts',C('DB_PREFIX'),'DB_CUSTOM')
            ->field(" ".C("DB_PREFIX")."sales_receipts.*,".C("DB_PREFIX")."members.`m_name`,admin1.`u_name`,admin2.`u_name` as v_name ")
            ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."sales_receipts.`m_id`=".C("DB_PREFIX")."members.`m_id`")
            ->join(" ".C("DB_PREFIX")."admin as admin1 ON ".C("DB_PREFIX")."sales_receipts.`sr_create_uid`= admin1.`u_id`")
            ->join(" ".C("DB_PREFIX")."admin as admin2 ON ".C("DB_PREFIX")."sales_receipts.`sr_verify_uid`= admin2.`u_id`")
            ->where($ary_where)->find();
            $this->assign('data',$ary_data);
			$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
			$pay_name = '货到付款';
			if($is_zt['IS_ZT']['sc_value'] == 1){
				$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
			}
			$this->assign('pay_name',$pay_name);			
            $this->display();
        }else{
            $this->error("缺少有效参数");
        }
    }
    
    /**
     * 导出后台EXCEL信息数据
     * @author Terry
     * @since 7.2
     * @version 1.0
     * @date 2012-5-14
     */
    public function getExportFileDownList() {
        $ary_get = $this->_get();
        switch ($ary_get['type']) {
            case 'excel':
                header("Content-type:application/force-download;charset=utf-8");
                header("Content-Disposition:attachment;filename=" . $ary_get['file']);
                readfile('./Public/Uploads/'.CI_SN.'/'.$ary_get['type']."/" . $ary_get['file']);
                break;
        }
        exit;
    }
     
}