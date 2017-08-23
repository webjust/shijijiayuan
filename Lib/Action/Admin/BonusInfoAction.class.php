<?php
/**
 * 后台红包管理
 *
 * @subpackage BonusInfo
 * @package Action
 * @stage 7.6
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2014-07-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BonusInfoAction extends AdminAction{
    /**
     * 默认控制器
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU7_8'));
    }

    /**
     * 红包配置页面
     * @author Hcaijin 
     * @date 2014-08-07
     */
    public function pageSet(){
        $this->getSubNav(7, 8, 50);
        D('SysConfig')->getCfg('BONUS_MONEY_SET','BONUS_AUTO_OPEN','0','是否启用红包功能');
        D('SysConfig')->getCfg('BONUS_MONEY_SET','IS_SERVER','0','是否自动客审红包');
        D('SysConfig')->getCfg('BONUS_MONEY_SET','IS_FINANCE','0','是否自动财审红包');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('BONUS_MONEY_SET');
        $this->assign($ary_jlb_data);
        $this->display();
    }
    
    /**
     * 保存红包配置
     * @author Hcaijin 
     * @date 2014-08-07
     */
    public function doSet(){
        $ary_post = $this->_post();
        foreach ($ary_post as $name=>$set_val){
            D('SysConfig')->setConfig('BONUS_MONEY_SET',$name,$set_val);
        }
        $this->success('保存成功');
    }

    /**
     * 红包列表
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function index(){
        $this->redirect(U('Admin/BonusInfo/pageList'));
    }
    
    public function pageList(){
        $ary_get = $this->_get();
        
        if(!empty($ary_get['st']) && $ary_get['st'] == 'pending'){
            $this->getSubNav(7, 8, 30);
        }elseif (!empty($ary_get['st']) && $ary_get['st'] == 'finance') {
            $this->getSubNav(7, 8, 40);  
        }else{
            $this->getSubNav(7, 8, 10);   
        }
        $ary_where = '';
        if(!empty($ary_get['bt_id']) && isset($ary_get['bt_id']) && $ary_get['bt_id'] != '0'){
            $ary_where .= C("DB_PREFIX").'bonus_info.`bt_id`='.$ary_get['bt_id'] ." AND ";
        }
        if(!empty($ary_get['status']) && isset($ary_get['status']) && $ary_get['status'] == '2'){
            $ary_where .= C("DB_PREFIX")."bonus_info.`bn_verify_status`!='2' AND ";
        }
        if(!empty($ary_get['st']) && isset($ary_get['st'])){
            if($ary_get['st'] == 'pending'){
                $ary_where .= C("DB_PREFIX")."bonus_info.`bn_service_verify`!='1' AND ";
            }elseif($ary_get['st'] == 'finance'){
                $ary_where .= C("DB_PREFIX")."bonus_info.`bn_finance_verify`!='1' AND ".C("DB_PREFIX")."bonus_info.`bn_service_verify`='1' AND";
            }
            
        }
        //制单时间
        if(!empty($ary_get['starttime'])){
            if(!empty($ary_get['endtime'])){
                if($ary_get['endtime'] >= $ary_get['starttime']){
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_create_time` BETWEEN '". $ary_get['starttime'] . "' AND '".$ary_get['endtime']."' AND ";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$ary_get['starttime']."'  AND ";
                }
            }else{
                $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_create_time` >='". $ary_get['starttime']."'  AND ";
            }
        }else{
            if(!empty($ary_get['endtime'])){
                $date = date("Y-m-d H:i");
                if($ary_get['endtime'] >= $date){
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_create_time` BETWEEN '". $date . "' AND '".$ary_get['endtime']."'  AND";
                }else{
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_create_time` BETWEEN '". $ary_get['endtime'] . "' AND '".$date."'  AND";
                }
            }
        }
        if(!empty($ary_get['val']) && isset($ary_get['val'])){
            switch ($ary_get['field']){
                case 'm_name':
                    $ary_where .= " ".C("DB_PREFIX")."members.`m_name` LIKE '%".$ary_get['val']."%'";
                    break;
                case 'bn_sn':
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_sn`='".$ary_get['val']."'";
                    break;
                case 'bn_accounts_receivable':
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`bn_accounts_receivable`='".$ary_get['val']."'";
                    break;
                case 'o_id':
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`o_id`='".$ary_get['val']."'";
                    break;
                case 'or_id':
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`or_id`='".$ary_get['val']."'";
                    break;
                case 'pc_serial_number':
                    $ary_where .= " ".C("DB_PREFIX")."bonus_info.`pc_serial_number`='".$ary_get['val']."'";
                    break;
            }
        }
        $count = D($this->_name)
                                ->field(" ".C("DB_PREFIX")."bonus_info.*,".C("DB_PREFIX")."bonus_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."bonus_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."bonus_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                ->join(" ".C("DB_PREFIX")."bonus_type ON ".C("DB_PREFIX")."bonus_type.`bt_id`=".C("DB_PREFIX")."bonus_info.`bt_id`")
                                ->order(" ".C("DB_PREFIX")."bonus_info.`bn_order` DESC")
                                ->where(rtrim($ary_where," AND"))->count();
        $obj_page = new Pager($count, 10);
        $page = $obj_page->show();
        
        $ary_data = D($this->_name)->field(" ".C("DB_PREFIX")."bonus_info.*,".C("DB_PREFIX")."bonus_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."bonus_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."bonus_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."bonus_type ON ".C("DB_PREFIX")."bonus_type.`bt_id`=".C("DB_PREFIX")."bonus_info.`bt_id`")
                                   ->order(" ".C("DB_PREFIX")."bonus_info.`bn_create_time` DESC")
                                   ->where(rtrim($ary_where," AND"))
                                   ->limit($obj_page->firstRow, $obj_page->listRows)
                                   ->select();
        foreach ($ary_data as $k=>$v){
            if($v['u_id'] == 0){
                $ary_data[$k]['u_name'] = 'system';
            }
        }
        $ary_type = D("BonusType")->where()->order('`bt_orderby` DESC')->select();
        $this->assign("type",$ary_type);
        $this->assign("page",$page);
        $this->assign("filter",$ary_get);
        $this->assign("data",  $ary_data);
        $this->display();
    }
    
    /**
     * 添加红包调整单
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function addBonusInfo(){
        $this->getSubNav(7, 8, 20);
        $ary_type = D("BonusType")->where(array('bt_status'=>'1'))->order('`bt_orderby` DESC')->select();
        $this->assign("type",$ary_type);
        $this->display();
    }
    
    /**
     * 选择会员
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function selectMembers(){
        $ary_post = $this->_post();
        $where = array();
        $where['m_name'] = array('LIKE','%'.$ary_post['m_name'].'%');
        $where['m_status'] = '1';
        $ary_data = M('Members',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->select();
        if(!empty($ary_data) && is_array($ary_data)){
            foreach($ary_data as $ky=>$vl){
                $ary_data[$ky]['m_names'] = str_replace($ary_post['m_name'],"<font color='red'>".$ary_post['m_name']."</font>", $ary_data[$ky]['m_name']);
            }
        }
        $this->assign("data",$ary_data);
        $this->assign("filter",$ary_post);
        $this->display();
    }
    
    /**
     * 导出红包
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function explortBonusInfo(){
        $ary_post = $this->_post();
        if(!empty($ary_post['bn_sns']) && isset($ary_post['bn_sns'])){
            $ary_data = D($this->_name)->getBonus($ary_post);
            if(!empty($ary_data) && is_array($ary_data)){
                $header = array('单据编号', '单据状态', '类型名称', '会员名', '调整金额', '制单人', '制单日期', '收款账号','订单号','退款单号','是否已作废','备注');
                $contents = array();
                foreach($ary_data as $vl){
                    $str = '';
                    if($vl['bn_service_verify'] == '1'){
                        $str .= '已客审  ';
                    }else{
                        $str .= '未客审  ';
                    }
                    if($vl['bn_finance_verify'] == '1'){
                        $str .= '已财审';
                    }else{
                        $str .= '未财审';
                    }
                    $status = '';
                    if($vl['bn_verify_status'] == '2'){
                        $status = '是';
                    }else{
                        $status = '否';
                    }
                    $type = '';
                    if($vl['bn_type'] != '0'){
                        $type = '-';
                    }
                    if(empty($vl['bn_accounts_receivable'])){
                        $vl['bn_accounts_receivable'] = '暂无';
                    }
                    if(empty($vl['o_id'])){
                        $vl['o_id'] = '暂无';
                    }
                    if(empty($vl['or_id'])){
                        $vl['or_id'] = '暂无';
                    }
                    if(empty($vl['desc'])){
                        $vl['desc'] = '暂无';
                    }
                    $contents[0][] = array(
                        "'" . $vl['bn_sn'],
                        $str,
                        $vl['bt_name'],
                        $vl['m_name'],
                        $type.sprintf('%.2f',$vl['bn_money']),
                        $vl['u_name'],
                        $vl['bn_create_time'],
                        $vl['bn_accounts_receivable'],
                        $vl['o_id'],
                        $vl['or_id'],
                        $status,
                        $vl['desc']
                    );
                }
                $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L');
                $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
                if(!is_dir($filexcel)){
                        @mkdir($filexcel,0777,1);
                }
                $Export = new Export(date('YmdHis') . '.xls', $filexcel);
                $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '红包信息', true);
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
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doStatus(){
        $ary_post = $this->_request();
        D($this->_name)->startTrans();
        if(!empty($ary_post['field']) && isset($ary_post['field'])){
            $ary_data = D($this->_name)->where(array('bn_id'=>$ary_post['id'],'bn_service_verify'=>'1','bn_finance_verify'=>'1'))->find();
            
            if($ary_post['field'] == 'bn_verify_status'){
                
                if(!empty($ary_data) && is_array($ary_data)){
                    $params = array(
                        'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                        'bn_sn' => $ary_data['bn_sn'],
                        'bvl_desc'  => '该单据已客审财审',
                        'bvl_type'  =>'1',
                        'bvl_status'    =>'2',
                        'bvl_create_time'   =>date("Y-m-d H:i:s")
                    );
                    D($this->_name)->writeBonusInfoLog($params);
                    D($this->_name)->rollback();
                    $this->error("该单据已客审财审");
                }else{
                    $arr_data = D($this->_name)->where(array('bn_id'=>$ary_post['id']))->find();
                    $ary_result = D($this->_name)->where(array('bn_id'=>$ary_post['id']))->data(array($ary_post['field']=>$ary_post['val']))->save();
                    if(FALSE != $ary_result){
                        $params = array(
                            'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                            'bn_sn' => $arr_data['bn_sn'],
                            'bvl_desc'  => '作废成功',
                            'bvl_type'  =>'1',
                            'bvl_status'    =>'1',
                            'bvl_create_time'   =>date("Y-m-d H:i:s")
                        );
                        D($this->_name)->writeBonusInfoLog($params);
                        D($this->_name)->commit();
                        $this->success("作废成功");
                    }else{
                        $params = array(
                            'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                            'bn_sn' => $ary_result['bn_sn'],
                            'bvl_desc'  => '作废失败',
                            'bvl_type'  =>'1',
                            'bvl_status'    =>'2',
                            'bvl_create_time'   =>date("Y-m-d H:i:s")
                        );
                        D($this->_name)->writeBonusInfoLog($params);
                        D($this->_name)->commit();
                        $this->error("作废失败");
                    }
                }
            }else{
                $arr_bonusinfo = D($this->_name)->where(array('bn_id'=>$ary_post['id']))->find();
                if($arr_bonusinfo[$ary_post['field']] == $ary_post['val']){
                    $this->error('单据已审核，请勿重复审核');
                }
                $ary_result = D($this->_name)->where(array('bn_id'=>$ary_post['id']))->data(array($ary_post['field']=>$ary_post['val'],'bn_update_time'=>date('Y-m-d H:i:s')))->save();
                $params = array(
                    'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                    'bn_sn' => $arr_bonusinfo['bn_sn']
                    
                );
                if($ary_post['field'] == 'bn_verify_status'){
                    $params['bvl_type'] = '1';
                }elseif($ary_post['field'] == 'bn_service_verify'){
                    $params['bvl_type'] = '2';
                }elseif($ary_post['field'] == 'bn_finance_verify'){
                    $params['bvl_type'] = '3';
                }
                if(FALSE != $ary_result){
                    $params['bvl_status'] = '1';
                    $params['bvl_desc'] = '审核成功';
                    $params['bvl_create_time'] = date("Y-m-d H:i:s");
                    
                    if(!empty($ary_post['field']) && $ary_post['field'] == 'bn_finance_verify'){
                        $data = D("Members")->field("m_bonus")->where(array("m_id"=>$arr_bonusinfo['m_id']))->find();
                        $m_bonus = '';
                        $running_acc['ra_payment_method'] = "预存款";
                        $running_acc['ra_before_money'] = $data['m_bonus'];
                        $running_acc['m_id'] = $arr_bonusinfo['m_id'];
                        switch($arr_bonusinfo['bt_id']){
                            case "1":
                                $running_acc['ra_money'] = '-'.$arr_bonusinfo['bn_money'];
                                $running_acc['ra_type'] = 1;
                                $running_acc['ra_after_money'] = $data['m_bonus'] - $arr_bonusinfo['bn_money'];
                                $running_acc['ra_memo'] = "红包调整单支付";
                                break;
                            case "2":
                                $running_acc['ra_money'] = $arr_bonusinfo['bn_money'];
                                $running_acc['ra_type'] = 4;
                                $running_acc['ra_after_money'] = $data['m_bonus'] + $arr_bonusinfo['bn_money'];
                                $running_acc['ra_memo'] = "红包调整单退款";
                                break;
                            case "3":
                                $running_acc['ra_money'] = $arr_bonusinfo['bn_money'];
                                $running_acc['ra_type'] = 0;
                                $running_acc['ra_after_money'] = $data['m_bonus'] + $arr_bonusinfo['bn_money'];
                                $running_acc['ra_memo'] = "红包调整单充值";
                                break;
                            case "4":
                                $running_acc['ra_money'] = $arr_bonusinfo['bn_money'];
                                $running_acc['ra_type'] = 0;
                                $running_acc['ra_after_money'] = $data['m_bonus'] + $arr_bonusinfo['bn_money'];
                                $running_acc['ra_memo'] = "红包提现";
                                break;
                        }
                        $running_acc['ra_create_time'] = date('Y-m-d H:i:s');
                        switch($arr_bonusinfo['bn_type']){
                            case '0':
                                $m_bonus = $data['m_bonus'] + $arr_bonusinfo['bn_money'];
                                break;
                            case '1':
                                $m_bonus = $data['m_bonus'] - $arr_bonusinfo['bn_money'];
                                break;
                            case '2':
                                $m_bonus = $data['m_bonus'] - $arr_bonusinfo['bn_money'];
                                break;
                            default :
                                $m_bonus = $data['m_bonus'] + $arr_bonusinfo['bn_money'];
                                break;
                        }
                        M('running_account')->add($running_acc);
                        $arr_res = D("Members")->where(array('m_id'=>$arr_bonusinfo['m_id']))->data(array('m_bonus'=>$m_bonus))->save();
                        //审核状态改变
                       	D($this->_name)->where(array('bn_id'=>$ary_post['id']))->data(array('bn_verify_status'=>1,'bn_update_time'=>date('Y-m-d H:i:s')))->save();
                        if(FALSE != $arr_res){
                            M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                            D($this->_name)->commit();
                            $this->success("审核成功");
                        }else{
                            $this->error("审核失败");
                            D($this->_name)->rollback();
                        }
                    }else{
                        M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                        D($this->_name)->commit();
                        $this->success("审核成功");
                    }
                    
                }else{
                    $params['bvl_status'] = '0';
                    $params['bvl_desc'] = '审核失败';
                    $params['bvl_create_time'] = date("Y-m-d H:i:s");
                    M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
                    D($this->_name)->rollback();
                    $this->error("审核失败");
                }
                
            }
            
        }else{
            $this->error("参数有误,请重试...");
        }
    }
    
    /**
     * 红包详情
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function detailBonusInfo(){
        $ary_get = $this->_get();
        if(!empty($ary_get['st']) && $ary_get['st'] == 'pending'){
            $this->getSubNav(7, 8, 30);
        }elseif (!empty($ary_get['st']) && $ary_get['st'] == 'finance') {
            $this->getSubNav(7, 8, 40);  
        }else{
            $this->getSubNav(7, 8, 10);   
        }
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $params = array('bn_id'=>$ary_get['id']);
            $ary_data = D($this->_name)->getBonus($params);
            $data = $ary_data[0];
            $ary_log = $this->getBonusInfoLog(array('bn_sn'=>$data['bn_sn']));
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
            $this->assign("data",$data);
            $this->assign("filter",$ary_get);
            $this->display();
        }else{
            $this->error("缺少有效参数");
            
        }
    }
    
    public function getBonusInfoLog($params = array()){
        if(!empty($params['bn_sn']) && isset($params['bn_sn'])){
            $ary_where = C("DB_PREFIX")."bonus_verify_log.`bn_sn`='".$params['bn_sn']."' AND";
        }
        if(!empty($params['bvl_type']) && isset($params['bvl_type'])){
            $ary_where = C("DB_PREFIX")."bonus_verify_log.`bvl_type`='".$params['bvl_type']."' AND";
        }
        $ary_data = M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field(C('DB_PREFIX')."bonus_verify_log.*,".C('DB_PREFIX')."admin.u_name,".C('DB_PREFIX')."admin.u_id")
                    ->join( C('DB_PREFIX')."admin ON ".C('DB_PREFIX')."bonus_verify_log.`u_id`=".C('DB_PREFIX')."admin.`u_id`")
                    ->where(trim($ary_where,"AND"))
                    ->select();
        return $ary_data;
    }
    
    public function addHtml(){
        $ary_post = $this->_post();
        $where = array();
        $where['bt_id'] = $ary_post['val'];
        $ary_data = D("BonusType")->where($where)->find();
        $Pinyin = new Pinyin();
        $pinyin = $Pinyin->Pinyin($ary_data['bt_name']);
        $this->display($pinyin);
    }
    
    /**
     * 校验订单号是否存在
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
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
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
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
     * 校验红包
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function checkBonusMoney(){
        $ary_get = $this->_get();
        if(!empty($ary_get['id']) && isset($ary_get['id'])){
            $where = array();
            $where['m_id'] = $ary_get['id'];
            $ary_data = D("Members")->field("m_bonus")->where($where)->find();
            if(!empty($ary_data) && is_array($ary_data)){
                if($ary_data['m_bonus'] < $ary_get['bn_money'] || $ary_get['bn_money'] <= 0){
                    $this->ajaxReturn("余额不足");
                }else{
                    $this->ajaxReturn(true);
                }
            }else{
                $this->ajaxReturn("该用户不存在,请重新选择");
            }
        }else{
            $this->ajaxReturn("请选择对应客户");
        }
    }

    /**
     * 处理添加红包
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function doAddBonusInfo(){
        $ary_post = $this->_post();
        if(!empty($ary_post) && is_array($ary_post)){
            if(empty($ary_post['m_id'])){
                $this->error("请选择客户");
                exit;
            }
            $ary_post['bn_sn'] = time();
            $ary_post['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
            $ary_post['bn_create_time'] = date("Y-m-d H:i:s");
            $ary_post['pc_serial_number'] = !empty($ary_post['ps_id'])?$ary_post['ps_id']:"";
            $res = D($this->_name)->addBonus($ary_post);
            if($res === true){
                $this->success("操作成功",U("Admin/BonusInfo/index"),3);
            }else{
                $this->error("操作失败！");
            }
        }else{
            $this->error("数据有误,请重新输入");
        }
    }


    /**
    * 检验流水单号是否存在
    * @author Hcaijin<huangcaijin@guanyisoft.com>
    * @date 2014-07-14
    */
    public function checkPsid(){
        $ary_get = $this->_get();
        if(!empty($ary_get['ps_id']) && isset($ary_get['ps_id'])){
            $ary_result = D($this->_name)->where(array("pc_serial_number"=>$ary_get['ps_id'],"bn_verify_status"=>array("eq","2")))->find();
            if(!empty($ary_result) && is_array($ary_result)){
                $this->ajaxReturn("流水单号已存在");
            }else{
                $this->ajaxReturn(true);
            }
        }else{
            $this->ajaxReturn("流水单号不能为空");
        }
    }
}
