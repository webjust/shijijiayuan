<?php
/**
 * 后台结余款管理
 *
 * @subpackage BalanceInfo
 * @package Action
 * @stage 7.2
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-06-03
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BalanceInfoAction extends AdminAction{
    private $name;
    
    /**
     * 默认控制器
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function _initialize() {
        parent::_initialize();
        $this->name = $this->_name;
        $this->setTitle(' - '.L('MENU6_4'));
    }
    
    /**
     * 结余款类型列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function index(){
        $this->redirect(U('Admin/BalanceInfo/pageList'));
    }
    
    public function pageList(){
        $ary_get = $this->_get();
        
        if(!empty($ary_get['st']) && $ary_get['st'] == 'pending'){
            $this->getSubNav(7, 4, 30);
        }elseif (!empty($ary_get['st']) && $ary_get['st'] == 'finance') {
            $this->getSubNav(7, 4, 40);  
        }else{
            $this->getSubNav(7, 4, 10);   
        }
        $ary_where = '';
        if(!empty($ary_get['bt_id']) && isset($ary_get['bt_id']) && $ary_get['bt_id'] != '0'){
            $ary_where .= C("DB_PREFIX").'balance_info.`bt_id`='.$ary_get['bt_id'] ." AND ";
        }
        if(!empty($ary_get['status']) && isset($ary_get['status']) && $ary_get['status'] == '2'){
            $ary_where .= C("DB_PREFIX")."balance_info.`bi_verify_status`!='2' AND ";
        }
        if(!empty($ary_get['st']) && isset($ary_get['st'])){
            if($ary_get['st'] == 'pending'){
                $ary_where .= C("DB_PREFIX")."balance_info.`bi_service_verify`!='1' AND ";
            }elseif($ary_get['st'] == 'finance'){
                $ary_where .= C("DB_PREFIX")."balance_info.`bi_finance_verify`!='1' AND ".C("DB_PREFIX")."balance_info.`bi_service_verify`='1' AND";
            }
            
        }
		$start_time = date("Y-m-d H:i:s",mktime(0,0,0,date("m")-1,date("d"),date("Y")));
		if (empty($ary_get['starttime'])) {
			$ary_get['starttime'] = $start_time;
		}
        //制单时间
        if(!empty($ary_get['starttime'])){
            if(!empty($ary_get['endtime'])){
                if($ary_get['endtime'] >= $ary_get['starttime']){
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $ary_get['starttime'] . "' AND '".$ary_get['endtime']."' AND ";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$ary_get['starttime']."'  AND ";
                }
            }else{
                $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` >='". $ary_get['starttime']."'  AND ";
            }
        }else{
            if(!empty($ary_get['endtime'])){
                $date = date("Y-m-d H:i");
                if($ary_get['endtime'] >= $date){
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $date . "' AND '".$ary_get['endtime']."'  AND";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$date."'  AND";
                }
            }
        }
        if(!empty($ary_get['val']) && isset($ary_get['val'])){
            switch ($ary_get['field']){
                case 'm_name':
                    $ary_where .= " ".C("DB_PREFIX")."members.`m_name` LIKE '%".$ary_get['val']."%'";
                    break;
                case 'bi_sn':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_sn`='".$ary_get['val']."'";
                    break;
                case 'bi_accounts_receivable':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_accounts_receivable`='".$ary_get['val']."'";
                    break;
                case 'o_id':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`o_id`='".$ary_get['val']."'";
                    break;
                case 'or_id':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`or_id`='".$ary_get['val']."'";
                    break;
                case 'pc_serial_number':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`pc_serial_number`='".$ary_get['val']."'";
                    break;
            }
        }
		if(empty($ary_where)){
			$start_time = date("Y-m-d H:i:s",strtotime("-1 month"));
			$ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` >='". $start_time."'  ";
			$count = D($this->_name)->where(rtrim($ary_where," AND"))->count();
		}else{
			$count = D($this->_name)
					->field(" ".C("DB_PREFIX")."balance_info.*,".C("DB_PREFIX")."balance_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
					->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."balance_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
					->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."balance_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
					->join(" ".C("DB_PREFIX")."balance_type ON ".C("DB_PREFIX")."balance_type.`bt_id`=".C("DB_PREFIX")."balance_info.`bt_id`")
				   // ->order(" ".C("DB_PREFIX")."balance_info.`bi_order` DESC")
					->where(rtrim($ary_where," AND"))->count();			
		}
        $obj_page = new Pager($count, 10);
        $page = $obj_page->showpage();

        $ary_data = D($this->_name)->field(" ".C("DB_PREFIX")."balance_info.*,".C("DB_PREFIX")."balance_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."balance_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."balance_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."balance_type ON ".C("DB_PREFIX")."balance_type.`bt_id`=".C("DB_PREFIX")."balance_info.`bt_id`")
                                   ->order(" ".C("DB_PREFIX")."balance_info.`bi_create_time` DESC")
                                   ->where(rtrim($ary_where," AND"))
                                   ->limit($obj_page->firstRow, $obj_page->listRows)
                                   ->select();
        foreach ($ary_data as $k=>$v){
            if($v['u_id'] == 0){
                $ary_data[$k]['u_name'] = 'system';
            }
        }
       // echo "<pre>";echo D($this->_name)->getLastSql();exit;
        $ary_type = D("BalanceType")->where('')->order('`bt_orderby` DESC')->select();
        $this->assign("type",$ary_type);
        $this->assign("page",$page);
        $this->assign("filter",$ary_get);
        $this->assign("data",  $ary_data);
        unset($ary_get['_URL_']);
        $this->assign("filterExcel",base64_encode(json_encode($ary_get)));
        $this->display();
    }

    /**
     * 搜索条件组装
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-16
     */
    public function getSearchCondition($params = array()){
        $ary_where = '';
        if(!empty($params['bt_id']) && isset($params['bt_id']) && $params['bt_id'] != '0'){
            $ary_where .= C("DB_PREFIX").'balance_info.`bt_id`='.$params['bt_id'] ." AND ";
        }
        if(!empty($params['status']) && isset($params['status']) && $params['status'] == '2'){
            $ary_where .= C("DB_PREFIX")."balance_info.`bi_verify_status`!='2' AND ";
        }
        if(!empty($params['st']) && isset($params['st'])){
            if($params['st'] == 'pending'){
                $ary_where .= C("DB_PREFIX")."balance_info.`bi_service_verify`!='1' AND ";
            }elseif($params['st'] == 'finance'){
                $ary_where .= C("DB_PREFIX")."balance_info.`bi_finance_verify`!='1' AND ".C("DB_PREFIX")."balance_info.`bi_service_verify`='1' AND";
            }
        }
        //制单时间
        if(!empty($params['starttime'])){
            if(!empty($params['endtime'])){
                if($params['endtime'] >= $params['starttime']){
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $params['starttime'] . "' AND '".$params['endtime']."' AND ";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $params['endtime'] . "' AND '".$params['starttime']."'  AND ";
                }
            }else{
                $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` >='". $params['starttime']."'  AND ";
            }
        }else{
            if(!empty($params['endtime'])){
                $date = date("Y-m-d H:i");
                if($params['endtime'] >= $date){
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $date . "' AND '".$params['endtime']."'  AND";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_create_time` BETWEEN '". $params['endtime'] . "' AND '".$date."'  AND";
                }
            }
        }
        if(!empty($params['val']) && isset($params['val'])){
            switch ($params['field']){
                case 'm_name':
                    $ary_where .= " ".C("DB_PREFIX")."members.`m_name` LIKE '%".$params['val']."%'";
                    break;
                case 'bi_sn':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_sn`='".$params['val']."'";
                    break;
                case 'bi_accounts_receivable':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`bi_accounts_receivable`='".$params['val']."'";
                    break;
                case 'o_id':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`o_id`='".$params['val']."'";
                    break;
                case 'or_id':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`or_id`='".$params['val']."'";
                    break;
                case 'pc_serial_number':
                    $ary_where .= " ".C("DB_PREFIX")."balance_info.`pc_serial_number`='".$params['val']."'";
                    break;
            }
        }
        return rtrim($ary_where," AND");
    }

    /**
     * 添加结余款调整单
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function addBalanceInfo(){
        $this->getSubNav(7, 4, 20);
        $ary_type = D("BalanceType")->where(array('bt_status'=>'1'))->order('`bt_orderby` DESC')->select();
        $this->assign("type",$ary_type);
        $this->display();
    }

    /**
     * 选择会员
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-03
     */
    public function selectMembers(){
        $ary_post = $this->_post();
        $where = array();
        $where['m_name'] = array('LIKE','%'.$ary_post['m_name'].'%');
        //$where['m_status'] = '1';
        $ary_data = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->select();
        if(!empty($ary_data) && is_array($ary_data)){
            foreach($ary_data as $ky=>$vl){
                $ary_data[$ky]['m_names'] = str_replace($ary_post['m_name'],"<font color='red'>".$ary_post['m_name']."</font>", $ary_data[$ky]['m_name']);
				if($vl['m_status']!='1'){
					$ary_data[$ky]['m_names'] .='(会员状态为冻结)';
				}
			}
        }
       // echo "<pre>";print_r($ary_data);exit;
        $this->assign("data",$ary_data);
        $this->assign("filter",$ary_post);
        $this->display();
    }

    /**
     * 导出结余款
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-04
     * 
     */
    public function explortBalanceInfo(){
        $ary_post = $this->_post();
        $ary_where = '';
        switch ($ary_post['type']) {
            case 3:     // 导出搜索
                $ary_post['bi_sns'] = json_decode(base64_decode($ary_post['bi_sns']),true);
                $ary_where = $this->getSearchCondition($ary_post['bi_sns']);
                break;
            case 1 :    // 导出选中的
                $data = explode(',',trim($ary_post['bi_sns'],","));
                $str = '';
                foreach($data as $ky=>$vl){
                    $str .= "'".$vl."',";
                }
                $ary_where = C("DB_PREFIX")."balance_info.`bi_sn` IN (". trim($str,",") .")";;
                break;
            default:    // 导出所有
                $ary_where = '';
                break;
        }
        if(!empty($ary_post['bi_sns']) && isset($ary_post['bi_sns'])){
            // $ary_data = D($this->_name)->getBalance($ary_post);
            $ary_data = D($this->_name)->getBalanceByCondition($ary_where);
           // echo "<pre>";print_r($ary_data);exit;
            if(!empty($ary_data) && is_array($ary_data)){
                $header = array('单据编号', '单据状态', '类型名称', '会员名', '调整金额', '制单人', '制单日期', '收款账号','订单号','退款单号','是否已作废','备注');
                $contents = array();
                foreach($ary_data as $vl){
                    $str = '';
                    if($vl['bi_service_verify'] == '1'){
                        $str .= '已客审  ';
                    }else{
                        $str .= '未客审  ';
                    }
                    if($vl['bi_finance_verify'] == '1'){
                        $str .= '已财审';
                    }else{
                        $str .= '未财审';
                    }
                    $status = '';
                    if($vl['bi_verify_status'] == '2'){
                        $status = '是';
                    }else{
                        $status = '否';
                    }
                    $type = '';
                    if($vl['bi_type'] != '0'){
                        $type = '-';
                    }
                    if(empty($vl['bi_accounts_receivable'])){
                        $vl['bi_accounts_receivable'] = '暂无';
                    }
                    if(empty($vl['o_id'])){
                        $vl['o_id'] = '暂无';
                    }
                    if(empty($vl['or_id'])){
                        $vl['or_id'] = '暂无';
                    }
                    if(empty($vl['bi_desc'])){
                        $vl['bi_desc'] = '暂无';
                    }
					if(empty($vl['u_name'])){
						$vl['u_name'] = $vl['m_name'];
					}
                    $contents[0][] = array(
                        "'" . $vl['bi_sn'],
                        $str,
                        $vl['bt_name'],
                        $vl['m_name'],
                        $type.sprintf('%.2f',$vl['bi_money']),
                        $vl['u_name'],
                        $vl['bi_create_time'],
                        $vl['bi_accounts_receivable'],
                        $vl['o_id']."\t",
                        $vl['or_id']."\t",
                        $status,
                        $vl['bi_desc']
                    );
//                    echo "<pre>";print_r($contents);exit;
                }
                $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L');
                $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
                if(!is_dir($filexcel)){
                        @mkdir($filexcel,0777,1);
                }
                $Export = new Export(date('YmdHis') . '.xls', $filexcel);
                $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '结余款信息', true);
                if (!empty($excel_file)) {
                    $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
                } else {
                    $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
                }
            }else{
                $this->ajaxReturn(array('status'=>'0','info'=>'没有需要导出单据'));
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'请选择需要导出的单据编号'));
        }
    }

    /**
     * 处理审核状态
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-04
     */
    public function doStatus(){
        $ary_post = $this->_request();
        D($this->_name)->startTrans();
        if(!empty($ary_post['field']) && isset($ary_post['field'])){
            $ary_data = D($this->_name)->where(array('bi_id'=>$ary_post['id'],'bi_service_verify'=>'1','bi_finance_verify'=>'1'))->find();
            
            if($ary_post['field'] == 'bi_verify_status'){
                
                if(!empty($ary_data) && is_array($ary_data)){
                    $params = array(
                        'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                        'bi_sn' => $ary_data['bi_sn'],
                        'bvl_desc'  => '该单据已客审财审',
                        'bvl_type'  =>'1',
                        'bvl_status'    =>'2',
                        'bvl_create_time'   =>date("Y-m-d H:i:s")
                    );
                    $this->writeBalanceInfoLog($params);
                    D($this->_name)->rollback();
                    $this->error("该单据已客审财审");
                }else{
                    $arr_data = D($this->_name)->where(array('bi_id'=>$ary_post['id']))->find();
                   // echo "<pre>";print_r($arr_data);exit;
                    $ary_result = D($this->_name)->where(array('bi_id'=>$ary_post['id']))->data(array($ary_post['field']=>$ary_post['val']))->save();
//                    echo "<pre>";print_r(D($this->_name)->getLastSql());exit;
                    if(FALSE != $ary_result){
                        $params = array(
                            'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                            'bi_sn' => $arr_data['bi_sn'],
                            'bvl_desc'  => '作废成功',
                            'bvl_type'  =>'1',
                            'bvl_status'    =>'1',
                            'bvl_create_time'   =>date("Y-m-d H:i:s")
                        );
//                        echo "<pre>";print_r($ary_result);exit;
                        $this->writeBalanceInfoLog($params);
                        D($this->_name)->commit();
                        $this->success("作废成功");
                    }else{
                        $params = array(
                            'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                            'bi_sn' => $ary_result['bi_sn'],
                            'bvl_desc'  => '作废失败',
                            'bvl_type'  =>'1',
                            'bvl_status'    =>'2',
                            'bvl_create_time'   =>date("Y-m-d H:i:s")
                        );
                        $this->writeBalanceInfoLog($params);
                        D($this->_name)->commit();
                        $this->error("作废失败");
                    }
                }
            }else{
                $arr_balaceinfo = D($this->_name)->where(array('bi_id'=>$ary_post['id']))->find();
                if($arr_balaceinfo[$ary_post['field']] == $ary_post['val']){
                    $this->error('单据已审核，请勿重复审核');
                }
                //echo "<pre>";print_r($arr_balaceinfo);exit;
                $ary_result = D($this->_name)->where(array('bi_id'=>$ary_post['id']))->data(array($ary_post['field']=>$ary_post['val'],'bi_update_time'=>date('Y-m-d H:i:s')))->save();
                $params = array(
                    'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                    'bi_sn' => $arr_balaceinfo['bi_sn']
                    
                );
                if($ary_post['field'] == 'bi_verify_status'){
                    $params['bvl_type'] = '1';
                }elseif($ary_post['field'] == 'bi_service_verify'){
                    $params['bvl_type'] = '2';
                }elseif($ary_post['field'] == 'bi_finance_verify'){
                    $params['bvl_type'] = '3';
                }
                if($ary_post['field'] == 'bi_service_verify' && $arr_balaceinfo['bt_id'] == 1){
                    $array_orders = D('Orders')->where(array('o_id'=>$arr_balaceinfo['o_id']))->find();
                    if(empty($array_orders) && !isset($array_orders)){
                        M()->rollback();
                        $this->error("订单不存在");
                    }
                    if($array_orders['o_status'] != 1){
                        M()->rollback();
                        $this->error("无效订单");
                    }
                    if($array_orders['o_pay_status'] == 1){
                        M()->rollback();
                        $this->error("订单已支付");
                    }
                    if($arr_balaceinfo['bi_money'] > ($array_orders['o_all_price'] - $array_orders['o_pay'])){
                        M()->rollback();
                        $this->error("支付金额超出订单金额");
                    }
                }elseif($ary_post['field'] == 'bi_finance_verify' && $arr_balaceinfo['bt_id'] == 1){
                    $array_orders = D('Orders')->where(array('o_id'=>$arr_balaceinfo['o_id']))->find();
                    if(empty($array_orders) && !isset($array_orders)){
                        M()->rollback();
                        $this->error("订单不存在");
                    }
                    if($array_orders['o_status'] != 1){
                        M()->rollback();
                        $this->error("无效订单");
                    }
                    if($array_orders['o_pay_status'] == 1){
                        M()->rollback();
                        $this->error("订单已支付");
                    }
                    $o_tmp_pay = $array_orders['o_all_price'] - $array_orders['o_pay'];
                    if($arr_balaceinfo['bi_money'] > $o_tmp_pay){
                        M()->rollback();
                        $this->error("支付金额超出订单金额");
                    }else if($arr_balaceinfo['bi_money'] < $o_tmp_pay){
                        $order_update['o_pay'] = $arr_balaceinfo['bi_money']+$array_orders['o_pay'];
                        $order_update['o_pay_status'] = 3;
                        if(false === D('Orders')->where(array('o_id'=>$arr_balaceinfo['o_id']))->save($order_update)){
                            M()->rollback();
                            $this->error("更新订单状态失败");
                        }else{
                            $ordersLog_add['o_id'] = $arr_balaceinfo['o_id'];
                            $ordersLog_add['ol_behavior'] = "结余款调整单部分支付成功，支付金额：￥{$arr_balaceinfo['bi_money']}";
                            $ordersLog_add['ol_uname'] = "管理员：".$_SESSION['admin_name'];
                            $ordersLog_add['ol_create'] = date('Y-m-d H:i:s');
                            if(false === D('OrdersLog')->add($ordersLog_add)){
                                M()->rollback();
                                $this->error("写入支付日志失败");
                            }
                        }
                    }elseif($arr_balaceinfo['bi_money'] == $o_tmp_pay){
                        $order_update['o_pay'] = $arr_balaceinfo['bi_money']+$array_orders['o_pay'];
                        $order_update['o_pay_status'] = 1;
                        //判断是否开启自动审核功能
                        $IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
                        if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1){
                            $order_update['o_audit'] = 1;
                        }
                        if(false === D('Orders')->where(array('o_id'=>$arr_balaceinfo['o_id']))->save($order_update)){
                            M()->rollback();
                            $this->error("更新订单状态失败");
                        }else{
                            $ordersLog_add['o_id'] = $arr_balaceinfo['o_id'];
                            $ordersLog_add['ol_behavior'] = "结余款调整单支付成功";
                            $ordersLog_add['ol_uname'] = "管理员：".$_SESSION['admin_name'];
                            $ordersLog_add['ol_create'] = date('Y-m-d H:i:s');
                            if(false === D('OrdersLog')->add($ordersLog_add)){
                                M()->rollback();
                                $this->error("写入支付日志失败");
                            }
                            //获取订单详情
                            $array_orders_itme = D('OrdersItems')->where(array('o_id'=>$arr_balaceinfo['o_id']))->find();
                           
                            if($array_orders_itme['oi_type'] == 5){
                                //团购订单支付
                                $gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id' => $array_orders_itme['fc_id']))->getField('gp_now_number');
                                M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'gp_id' => $array_orders_itme['fc_id']
                                ))->save(array(
                                    'gp_now_number' => $gp_now_number + $array_orders_itme ['oi_nums']
                                ));
                            }elseif($array_orders_itme['oi_type'] == 7){
                                //秒杀订单支付
                                $sp_now_number = M('Spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('sp_id' => $array_orders_itme['fc_id']))->getField('sp_now_number');
                                 
                                M('Spike', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'sp_id' => $array_orders_itme['fc_id']
                                ))->save(array(
                                    'sp_now_number' => $sp_now_number + 1
                                ));
                            }elseif($array_orders_itme['oi_type'] == 8){
                                //预售订单支付
                                $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id' => $array_orders_itme['fc_id']))->getField('p_now_number');
                                M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'p_id' => $array_orders_itme['fc_id']
                                ))->save(array(
                                    'p_now_number' => $p_now_number + $array_orders_itme ['oi_nums']
                                ));
                            }
                            
                        }
                    }
                    $paymentSerial_add['m_id'] = $array_orders['m_id'];
                    $paymentSerial_add['pc_code'] = 'DEPOSIT';
                    $paymentSerial_add['ps_money'] = $arr_balaceinfo['bi_money'];
                    $paymentSerial_add['ps_type'] = 0;
                    $paymentSerial_add['o_id'] = $array_orders['o_id'];
                    $paymentSerial_add['ps_status'] = 1;
                    $paymentSerial_add['pay_type'] = 0;
                    $paymentSerial_add['ps_create_time'] = date('Y-m-d H:i:s');
                    M('payment_serial')->add($paymentSerial_add);
                }
                if(FALSE != $ary_result){
                    $params['bvl_status'] = '1';
                    $params['bvl_desc'] = '审核成功';
                    $params['bvl_create_time'] = date("Y-m-d H:i:s");
                    
                    if(!empty($ary_post['field']) && $ary_post['field'] == 'bi_finance_verify'){
                        $data = D("Members")->field("m_balance")->where(array("m_id"=>$arr_balaceinfo['m_id']))->find();
                        $m_balance = '';
                        $running_acc['ra_payment_method'] = "预存款";
                        $running_acc['ra_before_money'] = $data['m_balance'];
                        $running_acc['m_id'] = $arr_balaceinfo['m_id'];
                        switch($arr_balaceinfo['bt_id']){
                            case "1":
                                $running_acc['ra_money'] = '-'.$arr_balaceinfo['bi_money'];
                                $running_acc['ra_type'] = 1;
                                $running_acc['ra_after_money'] = $data['m_balance'] - $arr_balaceinfo['bi_money'];
                                $running_acc['ra_memo'] = "结余款调整单支付";
                                break;
                            case "2":
                                $running_acc['ra_money'] = $arr_balaceinfo['bi_money'];
                                $running_acc['ra_type'] = 4;
                                $running_acc['ra_after_money'] = $data['m_balance'] + $arr_balaceinfo['bi_money'];
                                $running_acc['ra_memo'] = "结余款调整单退款";
                                break;
                            case "3":
                                $running_acc['ra_money'] = $arr_balaceinfo['bi_money'];
                                $running_acc['ra_type'] = 0;
                                $running_acc['ra_after_money'] = $data['m_balance'] + $arr_balaceinfo['bi_money'];
                                $running_acc['ra_memo'] = "结余款调整单充值";
                                break;
                            case "4":
                                $running_acc['ra_money'] = $arr_balaceinfo['bi_money'];
                                $running_acc['ra_type'] = 0;
                                $running_acc['ra_after_money'] = $data['m_balance'] + $arr_balaceinfo['bi_money'];
                                $running_acc['ra_memo'] = "结余款提现";
                                break;
                        }
                        $running_acc['ra_create_time'] = date('Y-m-d H:i:s');
                        switch($arr_balaceinfo['bi_type']){
                            case '0':
                                $m_balance = $data['m_balance'] + $arr_balaceinfo['bi_money'];
                                break;
                            case '1':
                                $m_balance = $data['m_balance'] - $arr_balaceinfo['bi_money'];
                                break;
                            case '2':
                                $m_balance = $data['m_balance'] - $arr_balaceinfo['bi_money'];
                                break;
                            default :
                                $m_balance = $data['m_balance'] + $arr_balaceinfo['bi_money'];
                                break;
                        }
                        M('running_account')->add($running_acc);
						if($m_balance < 0){
							$this->error("审核失败,余额已不足！！");
							D($this->_name)->rollback();
						}
                        $arr_res = D("Members")->where(array('m_id'=>$arr_balaceinfo['m_id']))->data(array('m_balance'=>$m_balance))->save();
                        //审核状态改变
                       	D($this->_name)->where(array('bi_id'=>$ary_post['id']))->data(array('bi_verify_status'=>1,'bi_update_time'=>date('Y-m-d H:i:s')))->save();
                        if(FALSE != $arr_res){
                            M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                            //$this->writeBalanceInfoLog($params);
                            D($this->_name)->commit();
                            $this->success("审核成功");
                        }else{
                            $this->error("审核失败");
                            D($this->_name)->rollback();
                        }
                    }else{
                        M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                        D($this->_name)->commit();
                        $this->success("审核成功");
                    }
                    
                }else{
                    $params['bvl_status'] = '0';
                    $params['bvl_desc'] = '审核失败';
                    $params['bvl_create_time'] = date("Y-m-d H:i:s");
                    M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                    //$this->writeBalanceInfoLog($params);
                    D($this->_name)->rollback();
                    $this->error("审核失败");
                }
                
            }
            
        }else{
            $this->error("参数有误,请重试...");
        }
    }
    
    /**
     * 记录审核日志
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-04
     */
    public function writeBalanceInfoLog($params){
        M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
    
    /**
     * 结余款详情
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-05
     */
    public function detailBalanceInfo(){
        $ary_get = $this->_get();
        if(!empty($ary_get['st']) && $ary_get['st'] == 'pending'){
            $this->getSubNav(7, 4, 30);
        }elseif (!empty($ary_get['st']) && $ary_get['st'] == 'finance') {
            $this->getSubNav(7, 4, 40);  
        }else{
            $this->getSubNav(7, 4, 10);   
        }
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $params = array('bi_id'=>$ary_get['id']);
            $ary_data = D($this->_name)->getBalance($params);
            $data = $ary_data[0];
            $ary_log = $this->getBalanceInfoLog(array('bi_sn'=>$data['bi_sn']));
            if(!empty($ary_log) && is_array($ary_log)){
                foreach($ary_log as $ky=>$vl){
                    if($vl['bvl_type'] == '2'){
                        $data['pending'] = isset($vl['u_name'])?$vl['u_name']:'system';
                        $data['pending_time'] = $vl['bvl_create_time'];
                    }else if($vl['bvl_type'] == '3'){
                        $data['finance'] = isset($vl['u_name'])?$vl['u_name']:'system';
                        $data['finance_time'] = $vl['bvl_create_time'];
                    }else if($vl['bvl_type'] == '1'){
                        $data['invalid'] = isset($vl['u_name'])?$vl['u_name']:'system';
                        $data['invalid_time'] = $vl['bvl_create_time'];
                    }
                }
            }
            if($data['u_id'] == '0'){
                $data['u_name'] = 'system';
            }
           // echo "<pre>";print_r($ary_log);exit;
            $this->assign("data",$data);
            $this->assign("filter",$ary_get);
            $this->display();
        }else{
            $this->error("缺少有效参数");
            
        }
    }
    
    public function getBalanceInfoLog($params = array()){
        if(!empty($params['bi_sn']) && isset($params['bi_sn'])){
            $ary_where = C("DB_PREFIX")."balance_verify_log.`bi_sn`='".$params['bi_sn']."' AND";
        }
        if(!empty($params['bvl_type']) && isset($params['bvl_type'])){
            $ary_where = C("DB_PREFIX")."balance_verify_log.`bvl_type`='".$params['bvl_type']."' AND";
        }
        $ary_data = M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field(C('DB_PREFIX')."balance_verify_log.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."admin.u_id")
                    ->join( C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."balance_verify_log.`u_id`=".C('DB_PREFIX')."admin.`u_id`")
                    ->where(trim($ary_where,"AND"))
                    ->select();
//        echo M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        return $ary_data;
    }
    
    /**
     * 财务设置
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-06
     */
    public function pageSet(){
        $this->getSubNav(7, 4, 50);
        $data = D('SysConfig')->getCfgByModule('BALANCE_SET');
        $this->assign($data);
        $this->display();
    }
    
    /**
     * 处理财务设置
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-06
     */
    public function doSet(){
        $ary_post = $this->_post();
        $SysSeting = D('SysConfig');
        if(
            $SysSeting->setConfig('BALANCE_SET', 'PENDING', $ary_post['PENDING'], '自动客审') &&
            $SysSeting->setConfig('BALANCE_SET', 'FINANCE', $ary_post['FINANCE'], '自动财审') &&
            $SysSeting->setConfig('BALANCE_SET', 'INVALID', $ary_post['INVALID'], '自动作废')
        ){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    
    public function addHtml(){
        $ary_post = $this->_post();
        $where = array();
        $where['bt_id'] = $ary_post['val'];
        $ary_data = D("BalanceType")->where($where)->find();
        $Pinyin = new Pinyin();
        $pinyin = $Pinyin->Pinyin($ary_data['bt_name']);
        $this->display($pinyin);
    }
    
    /**
     * 校验订单号是否存在
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-06
     */
    public function checkName(){
        $ary_get = $this->_get();
        if(!empty($ary_get['o_id']) && isset($ary_get['o_id'])){
            $where = array();
            $where['o_id'] = $ary_get['o_id'];
            $ary_data = D("Orders")->where($where)->find();
            //echo "<pre>";print_r($ary_data);exit;
            if(!empty($ary_data) && is_array($ary_data)){
                $this->ajaxReturn(true);
            }else{
                $this->ajaxReturn("该订单号不存在,请核实...");
            }
        }else{
            $this->ajaxReturn("订单号不能为空");
        }
    }
    
    /**
     * 校验退款号是否存在
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-06
     */
    public function checkOrid(){
        $ary_get = $this->_get();
        if(!empty($ary_get['or_id']) && isset($ary_get['or_id'])){
            $where = array();
            $where['or_return_sn'] = $ary_get['or_id'];
            $ary_data = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->find();
            if(!empty($ary_data) && is_array($ary_data)){
                $this->ajaxReturn(true);
            }else{
                $this->ajaxReturn("该退款号不存在,请核实...");
            }
        }else{
            $this->ajaxReturn("退款号不能为空");
        }
    }
    
    public function checkPstatusId(){
        //ps_status_sn
        $ary_get = $this->_get();
        if(!empty($ary_get['ps_id']) && isset($ary_get['ps_id'])){
            $where = array();
            $where['ps_status_sn'] = $ary_get['ps_id'];
            $ary_data = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->find();
            if(!empty($ary_data) && is_array($ary_data)){
                $this->ajaxReturn(true);
            }else{
                $this->ajaxReturn("该收款单不存在,请核实...");
            }
        }else{
            $this->ajaxReturn("收款单号不能为空");
        }
    }
    
    /**
     * 校验结余款
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-07
     */
    public function checkBalanceMoney(){
        $ary_get = $this->_get();
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $where = array();
            $where['m_id'] = $ary_get['id'];
            $ary_data = D("Members")->field("m_balance")->where($where)->find();
            if(!empty($ary_data) && is_array($ary_data)){
                if($ary_data['m_balance'] < $ary_get['bi_money'] || $ary_get['bi_money'] <= 0){
                    $this->error("余额不足");
                }else{
                    $this->success();
                }
            }else{
                $this->error("该用户不存在,请重新选择");
            }
        }else{
            $this->error("请选择对应客户");
        }
    }

    /**
     * 处理添加结余款
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-06
     */
    public function doAddBalanceInfo(){
        $ary_post = $this->_post();
        if(!empty($ary_post) && is_array($ary_post)){
            if(empty($ary_post['m_id'])){
                $this->error("请选择客户");
                exit;
            }
            if($ary_post['bt_id'] == 1){
                $where=array();
                $where['o_id']=$ary_post['o_id'];
                $where['m_id']=$ary_post['m_id'];
                $oresult = D('Orders')->where($where)->find();
                if(empty($oresult)||($ary_post['bi_money'] > $oresult['o_all_price'])){
                    $this->error("操作失败");
                }
            }
            
            if($ary_post['bt_id'] == 2){
                $where=array();
                $where['or_id']=$ary_post['or_id'];
                $where['bi_verify_status']=array('neq',2);
                $ofres=D($this->_name)->where($where)->find();
                if(isset($ofres)){
                    
                    $this->error("操作失败");
                }
                $where=array();
                $where['or_return_sn']=$ary_post['or_id'];
                $where['m_id']=$ary_post['m_id'];
                $oresult = D('OrdersRefunds')->where($where)->find();
                if(empty($oresult)||($ary_post['bi_money'] > $oresult['or_money'])){
                    $this->error("操作失败");
                }
            }
			
			if($ary_post['bt_id'] == 3 && (int)$ary_post['bi_money']>100000){
				$this->error("本次最多可充值100,000.00元");
			}

			if($ary_post['bt_id'] == 4){
                $where = array();
                $where['m_id'] = $ary_post['m_id'];
                $res_data = D("Members")->field("m_balance")->where($where)->find();
                if(!empty($res_data) && is_array($res_data)){
                    if($res_data['m_balance'] < $ary_post['bi_money'] || $ary_post['bi_money'] <= 0){
                        $this->error("操作失败");
                    }
                }
            }
            
            D($this->_name)->startTrans();
            //获取是否自动审核 客审:PENDING 财审:FINANCE 作废:INVALID
            $data = D('SysConfig')->getCfgByModule('BALANCE_SET');
            $ary_post['bi_sn'] = time();
            $ary_post['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
            $ary_post['bi_create_time'] = date("Y-m-d H:i:s");
            $ary_post['bi_finance_verify'] = !empty($data)&&$data['FINANCE'] ? '1' : '0';
            $ary_post['bi_service_verify'] = !empty($data)&&$data['PENDING'] ? '1' : '0';
            $ary_post['bi_verify_status'] = !empty($data)&&$data['INVALID'] ? '2' : '0';
            $bi_desc = $ary_post['bi_desc'];
			
			//记录ip
			$ip = $this->Getaddress($_SESSION['city']['cr_id']);
			$ary_post['local_ip'] = $ip;
			
			//dump($ip);die;
            //调整描述
            switch ($ary_post['bt_id']){
                case 1:
                  $ary_post['bi_desc'] = '购物消费'.$bi_desc;
                  break;  
                case 2:
                  $ary_post['bi_desc'] = '账户退款'.$bi_desc;
                  break;
                case 3:
                  $ary_post['bi_desc'] = '管理员添加'.$bi_desc;
                  break;
                case 4:
                  $ary_post['bi_desc'] = '结余款提现'.$bi_desc;
                  break;  
                default:
			}				
            //dump($ip);die();
            $ary_result = D($this->_name)->add($ary_post);
//            echo D($this->_name)->getLastSql();exit;
            if(FALSE != $ary_result){
                $ary_data = array();
                $str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
                $ary_data['bi_sn'] = time() . $str_sn;
                $result = D($this->_name)->where(array('bi_id'=>$ary_result))->data($ary_data)->save();
                $params = array(
                    'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                    'bi_sn' => $ary_data['bi_sn'],
                    'bvl_status'    =>'1',
                    'bvl_create_time'   =>date("Y-m-d H:i:s")
                );
                if(!empty($ary_post['bi_service_verify']) && $ary_post['bi_finance_verify'] == '1'){
                    $params['bvl_desc'] = '已客审成功';
                    $params['bvl_type'] = '2';
                    $this->writeBalanceInfoLog($params);
                }
                if(!empty($ary_post['bi_finance_verify']) && $ary_post['bi_finance_verify'] == '1'){
                    $params['bvl_desc'] = '已财审成功';
                    $params['bvl_type'] = '3';
                    $this->writeBalanceInfoLog($params);
                }       
                if(FALSE != $result){
//                    echo "<pre>";print_r($ary_post);exit;
                    if($ary_post['bi_finance_verify']){
                        $ary_data = D("Members")->field("m_balance")->where(array("m_id"=>$ary_post['m_id']))->find();
                        $m_balance = '';
                        switch($ary_post['bi_type']){
                            case '0':
                                $m_balance = $ary_data['m_balance'] + $ary_post['bi_money'];
                                break;
                            case '1':
                                $m_balance = $ary_data['m_balance'] - $ary_post['bi_money'];
                                break;
                            case '2':
                                $m_balance = $ary_data['m_balance'] - $ary_post['bi_money'];
                                break;
                            default :
                                $m_balance = $ary_data['m_balance'] + $ary_post['bi_money'];
                                break;
                        }
                        D("Members")->where(array('m_id'=>$ary_post['m_id']))->data(array('m_balance'=>$m_balance))->save();
                    }
                    D($this->_name)->commit();
                    $this->success("操作成功",U("Admin/BalanceInfo/pageList"),3);
                }else{
                    D($this->_name)->rollback();
                    $this->error("操作失败");
                }
            }else{
                D($this->_name)->rollback();
                $this->error("操作失败");
            }
        }else{
            $this->error("数据有误,请重新输入");
        }
    }


    /**
    * 检验流水单号是否存在
    * @author Terry<wanghui@guanyisoft.com>
    * @date 2013-07-22
    */
    public function checkPsid(){
        $ary_get = $this->_get();
        if(!empty($ary_get['ps_id']) && isset($ary_get['ps_id'])){
            $ary_result = D($this->_name)->where(array("ps_id"=>$ary_get['ps_id'],"bi_verify_status"=>array("not in","2")))->find();
            if(!empty($ary_result) && is_array($ary_result)){
                $this->ajaxReturn("流水单号已存在");
            }else{
                $this->ajaxReturn(true);
            }
        }else{
            $this->ajaxReturn("流水单号不能为空");
        }
    }
	    /**
     * 获取访问者真实IP区域
     * @author <zhuwenwei@guanyisoft.com>
     * @date 2015-11-19
     */
    private function Getaddress($cr_id = '') {
        import('ORG.Net.IpLocation');// 导入IpLocation类
        $Ip = new IpLocation();
        $location = $Ip->getlocation(); 
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
		$local_ip = $location['ip'];
		return $local_ip;die;
    }
}
