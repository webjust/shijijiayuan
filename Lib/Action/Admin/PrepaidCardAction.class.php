<?php
/**
 * 充值卡相关Action控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.5
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-3-7
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class PrepaidCardAction extends AdminAction{
    /**
     * 控制器初始化
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 充值卡添加');
    }
    
    /**
     * 后台商品控制器默认页，需要重定向
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function index() {
        $this->redirect(U('Admin/PrepaidCard/pageList'));
    }
    
    /**
     * 充值卡列表
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pageList(){
        $this->getSubNav(7, 7, 10);
        $ary_get = $this->_get();
        $filter = array();
        $filter['field'] = $ary_get["field"];
        $filter['val'] = $ary_get["val"];
        
        //搜索条件
		if(isset($ary_get["field"]) && $ary_get["field"] != 'pc_money' && $ary_get["val"] != ""){
			$array_cond[$ary_get["field"]] = array("LIKE","%" . $ary_get["val"] . "%");
		}
        
        if(isset($ary_get["field"]) && $ary_get["field"] == 'pc_money' && $ary_get["val"] != ""){
			$array_cond[$ary_get["field"]] = $ary_get["val"];
		}
        
        //如果根据充值卡的有效期进行搜索
        if(isset($ary_get["starttime"]) && "" != $ary_get["starttime"]){
            $array_cond["pc_start_time"] = array("egt",$ary_get["starttime"]);
            $filter['starttime'] = $ary_get["starttime"];
        }else{
            $array_cond["pc_start_time"] = array("egt",date('Y-m').'-1 00:00:00');
            $filter['starttime'] = date('Y-m').'-1 00:00:00';
        }
        
        if(isset($ary_get["endtime"]) && "" != $ary_get["endtime"]){
            $array_cond["pc_end_time"] = array("elt",$ary_get["endtime"]);
            $filter['endtime'] = $ary_get["endtime"];
        }else{
            $array_cond["pc_end_time"] = array("elt",date('Y-m').'-'.Date('t',time()).' 23:59:59');
            $filter['endtime'] = date('Y-m').'-'.Date('t',time()).' 23:59:59';
        }
        
        //如果根据使用状态搜索
        if(isset($ary_get['use_type']) && !empty($ary_get['use_type'])){
            if($ary_get['use_type'] == 1){
                $array_cond["m_id"] = array('neq','0');
            }elseif($ary_get['use_type'] == 2){
                $array_cond["m_id"] = array('eq','0');
            }
            $filter['use_type'] = $ary_get['use_type'];
        }
        
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $count = $prepaidCard->where($array_cond)->count();
        $obj_page = new Pager($count, 10);
        $array_data['page'] = $obj_page->show();
        $array_data['data'] = $prepaidCard->where($array_cond)->limit($obj_page->firstRow, $obj_page->listRows)->select();
        
        
		//edit Micle <yangkewei@guangyisoft.com> 保存筛选结果pc_ids用于导出Excel 2014-09-05
		$pc_ids      = $prepaidCard->where($array_cond)->field('pc_id')->select();
		$filterExcel = array();
		foreach($pc_ids as $val){
			$filterExcel[] = $val['pc_id'];
		}
		$this->assign('filterExcel',implode(',',$filterExcel));  //edit Micle 保存筛选结果pc_ids 结束 
        
        $this->assign($array_data);
        $this->assign('filter',$filter);
        $this->display();
    }
    
    /**
     * 新增充值卡页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pageAdd(){
        //获取会员等级
        $array_data['level'] = D('MembersLevel')->getMembersLevels();
        $array_data['group'] = D('MembersGroup')->where(array('mg_status'=>1))->select();
        $this->assign($array_data);
        $this->getSubNav(7, 7, 20);
        $this->display();
    }
    
    /**
     * 执行添加单个充值卡
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function doAdd(){
        $ary_data = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['pc_name'])) {
            $this->error('充值卡名称不存在');
        }

        if (empty($ary_data['pc_card_number'])) {
            $this->error('充值卡卡号不存在');
        }
        if (empty($ary_data['pc_password'])) {
            $this->error('充值卡密码不存在');
        }
        if (false != $prepaidCard->where(array('pc_card_number' => $ary_data['pc_card_number']))->find()) {
            $this->error('充值卡卡号已被使用');
        }

        if ((!empty($ary_data['pc_start_time']) && !empty($ary_data['pc_end_time'])) && (strtotime($ary_data['pc_start_time']) > strtotime($ary_data['pc_end_time']) )) {
            $tmp = $ary_data['pc_start_time'];
            $ary_data['pc_start_time'] = $ary_data['pc_end_time'];
            $ary_data['pc_end_time'] = $ary_data;
        }
        if(!empty($ary_data['pc_member_group'])){
            $ary_data['pc_member_group'] = implode(',',$ary_data['pc_member_group']);
        }
        if(!empty($ary_data['pc_member_level'])){
            $ary_data['pc_member_level'] = implode(',',$ary_data['pc_member_level']);
        }
        $ary_data['pc_create_time'] = date('Y-m-d H:i:s');
        M('')->startTrans();
        $pc_id = $prepaidCard->data($ary_data)->add();
        if(false === $pc_id){
            $this->error('充值卡生成失败');
            M('')->rollback();
        }else{
            $pc_serial_number = date('YmdHis').createSn($pc_id);
            $prepaidCard->where(array('pc_id'=>$pc_id))->save(array('pc_serial_number'=>$pc_serial_number));
            M('')->commit();
            $this->success('充值卡生成成功', U('Admin/PrepaidCard/pageList'));
        }
    }
    
    /**
     * 批量新增充值卡页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pageAuto(){
        //获取会员等级
        $array_data['level'] = D('MembersLevel')->getMembersLevels();
        $array_data['group'] = D('MembersGroup')->where(array('mg_status'=>1))->select();
        $this->assign($array_data);
        $this->getSubNav(7, 7, 30);
        $this->display();
    }
    
    /**
     * 执行批量添加充值卡
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function doAuto(){
        $ary_post = $this->_post();
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_post['pc_name'])) {
            $this->error('充值卡名称不存在');
        }
        $array_add['pc_name'] = $ary_post['pc_name'];
        if(!empty($ary_post['pc_member_group'])){
            $array_add['pc_member_group'] = implode(',',$ary_post['pc_member_group']);
        }
        if(!empty($ary_post['pc_member_level'])){
            $array_add['pc_member_level'] = implode(',',$ary_post['pc_member_level']);
        }
        $array_add['is_open'] = $ary_post['is_open'];
        $array_add['pc_start_time'] = $ary_post['pc_start_time'];
        $array_add['pc_end_time'] = $ary_post['pc_end_time'];
        $array_add['pc_meno'] = $ary_post['pc_meno'];
        $array_add['pc_money'] = $ary_post['pc_money'];
        M('')->startTrans();
        $i = 0;
        do{
            $array_add['pc_card_number'] = $ary_post['pc_sn_prefix'].randStr($ary_post['pc_code_long']).$ary_post['pc_sn_suffix'];
            $password = '';
            for ( $k = 0; $k < $ary_post['pc_pwd_long']; $k++ ){
                $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
            }
            $array_add['pc_password'] = $password;
            $pc_id = $prepaidCard->add($array_add);
            if(false === $pc_id){
                M('')->rollback();
                $this->error('批量添加充值卡失败');
            }
            $pc_serial_number = date('YmdHis').createSn($pc_id);
            
            $prepaidCard->where(array('pc_id'=>$pc_id))->save(array('pc_serial_number'=>$pc_serial_number));
            
            $i++;
        }while($i < (int) $ary_post['pc_num']);
        if($i != $ary_post['pc_num']){
            M('')->rollback();
            $this->error('批量添加充值卡失败');
        }
        M('')->commit();
        $this->success('充值卡生成成功', U('Admin/PrepaidCard/pageList'));
    }
    
    /**
     * 编辑充值卡页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pageEdit(){
        $this->getSubNav(7, 7, 35,'编辑充值卡');
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $pc_id = $this->_get('pc_id');
        $array_prepaid_card = $prepaidCard->where(array('pc_id' => $pc_id))->find();
        if(empty($array_prepaid_card) && !isset($array_prepaid_card)){
            $this->error('充值卡不存在');
        }
        $array_prepaid_card['pc_member_group'] = explode(',',$array_prepaid_card['pc_member_group']);
        $array_prepaid_card['pc_member_level'] = explode(',',$array_prepaid_card['pc_member_level']);
        $array_data['level'] = D('MembersLevel')->getMembersLevels();
        $array_data['group'] = D('MembersGroup')->where(array('mg_status'=>1))->select();
        $this->assign('list',$array_prepaid_card);
        $this->assign($array_data);
        $this->display();
    }
    
    /**
     * 执行编辑充值卡
     * @auhtor Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-20
     */
    public function doEdit(){
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_data = $this->_post();
        $pc_id = $ary_data['pc_id'];
        unset($ary_data['pc_id']);
        $ary_tmp_card = $prepaidCard->where(array('pc_id'=>$pc_id))->find();
        if(empty($ary_tmp_card) && !isset($ary_tmp_card)){
            $this->error('充值卡不存在');
        }
        if ((!empty($ary_data['pc_start_time']) && !empty($ary_data['pc_end_time'])) && (strtotime($ary_data['pc_start_time']) > strtotime($ary_data['pc_end_time']) )) {
            $tmp = $ary_data['pc_start_time'];
            $ary_data['pc_start_time'] = $ary_data['pc_end_time'];
            $ary_data['pc_end_time'] = $ary_data;
        }
        if(!empty($ary_data['pc_member_group'])){
            $ary_data['pc_member_group'] = implode(',',$ary_data['pc_member_group']);
        }else{
            $ary_data['pc_member_group'] = '';
        }
        if(!empty($ary_data['pc_member_level'])){
            $ary_data['pc_member_level'] = implode(',',$ary_data['pc_member_level']);
        }else{
            $ary_data['pc_member_level'] = '';
        }
		if(isset($ary_data['is_open'])){
			$ary_data['is_open'] = '1';
		}else{
			$ary_data['is_open'] = '0';
		}
        $ary_data['pc_update_time'] = date('Y-m-d H:i:s');
        if(false === $prepaidCard->where(array('pc_id'=>$pc_id))->save($ary_data)){
            $this->error('操作失败');
        }else{
            $this->success('充值卡编辑成功', U('Admin/PrepaidCard/pageList'));
        }
    }
    
    /**
     * 判断充值卡卡号是否已经存在
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function getCheck() {
        $card = $this->_get('pc_card_number');
        if (empty($card)) {
            $this->ajaxReturn('请输入充值卡卡号');
        } else {
            $ary_result = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pc_card_number' => $card))->find();
            if (false == $ary_result) {
                //SN未被使用，返回true
                $this->ajaxReturn(true);
            } else {
                $this->ajaxReturn("该卡号已被’{$ary_result['pc_name']}‘占用");
            }
        }
    }

    /**
     * 充值卡开启、关闭
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function isOpen(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $array_card = $prepaidCard->where(array('pc_status'=>1,'pc_id'=>$ary_post['pc_id']))->find();
        //验证数据有效性
        if(false === $array_card){
            $this->error('充值卡不存在');
        }
        if($array_card['m_name'] != ''){
            $this->error('充值卡已被使用，无法停用');
        }
        $filed = $ary_post['field'];
        //启用、停用
        if(false === $prepaidCard->where(array('pc_id'=>$ary_post['pc_id']))->save(array($filed=>$ary_post['val']))){
            $this->error('操作失败');
        }else{
            $this->success('操作成功');
        }
    }
    
    /**
     * 充值卡客审
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pcServiceVerify(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $array_card = $prepaidCard->where(array('pc_status'=>1,'pc_id'=>$ary_post['pc_id']))->find();
        //验证数据有效性
        if(false === $array_card){
            $this->error('充值卡不存在');
        }
        //判断当前充值卡是否已被使用
        if($array_card['m_id'] == 0){
            $this->error("{$array_card['pc_serial_number']}未使用，无法审核");
        }
        //判断当前充值卡是否已经被客审
        if($array_card['pc_service_verify'] == 1){
            $this->error("{$array_card['pc_serial_number']}已客审");
        }
        //判断当前充值卡是否已经被驳回
        if($array_card['pc_processing_status'] == 2){
            $this->error("{$array_card['pc_serial_number']}已驳回");
        }
        $array_save['pc_service_verify'] = 1;
        $array_save['pc_service_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
        $array_save['pc_service_u_name'] = $_SESSION['admin_name'];
        $array_save['pc_service_time'] = date('Y-m-d H:i:s');
        if(false === $prepaidCard->where(array('pc_id'=>$ary_post['pc_id']))->save($array_save)){
            $this->error("操作失败");
        }else{
            $this->success('操作成功');
        }
        
    }
    
    /**
     * 充值卡财审
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pcFinanceVerify(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $array_card = $prepaidCard->where(array('pc_status'=>1,'pc_id'=>$ary_post['pc_id']))->find();
        //验证数据有效性
        if(false === $array_card){
            $this->error('充值卡不存在');
        }
        //判断当前充值卡是否已被使用
        if($array_card['m_id'] == 0){
            $this->error("{$array_card['pc_serial_number']}未使用，无法审核");
        }
        //判断当前充值卡是否已经被客审
        if($array_card['pc_service_verify'] == 0){
            $this->error("{$array_card['pc_serial_number']}未客审");
        }
        //判断当前充值卡是否已经被财审
        if($array_card['pc_finance_verify'] == 1){
             $this->error("{$array_card['pc_serial_number']}已财审");
        }
        //当前充值卡是否已驳回
        if($array_card['pc_processing_status'] == 2){
            $this->error("{$array_card['pc_serial_number']}已驳回，无法审核");
        }
        //当前充值卡是否已审核通过
        if($array_card['pc_processing_status'] == 1 && $array_card['pc_finance_verify'] == 1){
             $this->error("{$array_card['pc_serial_number']}已审核通过，无须再次审核");
        }
        $array_save['pc_finance_verify'] = 1;
        $array_save['pc_processing_status'] = 1;
        $array_save['pc_finance_u_id'] = $_SESSION[C('USER_AUTH_KEY')];
        $array_save['pc_finance_u_name'] = $_SESSION['admin_name'];
        $array_save['pc_finance_time'] = date('Y-m-d H:i:s');
        //开启事物
        M('')->startTrans();
        if(false === $prepaidCard->where(array('pc_id'=>$ary_post['pc_id']))->save($array_save)){
            M('')->rollback();
            $this->error("操作失败");
        }else{
            //财审成功，生成结余款调整单
            $arr_balance_data = array();
            $array_card['pc_money'] = sprintf("%.2f",$array_card['pc_money']);
            $arr_balance_data['pc_serial_number'] = $array_card['pc_serial_number'];
            $arr_balance_data['bt_id'] = 3;//账户充值
            $arr_balance_data['m_id'] = $array_card['m_id'];
            $arr_balance_data['bi_money'] = $array_card['pc_money'];
            $arr_balance_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
            $arr_balance_data['bi_type'] = 0;//收入
            $arr_balance_data['bi_verify_status'] = 1;
            $arr_balance_data['bi_service_verify'] = 1;
            $arr_balance_data['bi_finance_verify'] = 1;
            $arr_balance_data['bi_payment_time'] = date("Y-m-d H:i:s");
            $arr_balance_data['bi_desc'] = "流水号 {$array_card['pc_serial_number']} <br/>充值金额{$array_card['pc_money']}元";
            $arr_balance_data['bi_create_time'] = date('Y-m-d H:i:s');
            $arr_balance_data['bi_update_time'] = date('Y-m-d H:i:s');
            $arr_balance_data['single_type'] = 1;
            
            $balance = new Balance();
            $ary_rest = $balance->addBalanceInfo($arr_balance_data);
            
            //获取结余款调整单基本表
            $balance_info = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($arr_balance_data)->find();
            //写入客审结余款调整单日志
            $balance_server_log['u_id'] = $array_card['pc_service_u_id'];
            $balance_server_log['u_name'] = $array_card['pc_service_u_name'];
            $balance_server_log['bi_sn'] = $balance_info['bi_sn'];
            $balance_server_log['bvl_desc'] = "流水号 {$array_card['pc_serial_number']} 充值{$array_card['pc_money']}元客审成功";
            $balance_server_log['bvl_type'] = '2';
            $balance_server_log['bvl_status'] = '1';
            $balance_server_log['bvl_create_time'] = $array_card['pc_service_time'];
            if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_server_log)){
                M('')->rollback();
                $this->error("生成客审结余款调整单日志失败");
            }
            
             //写入财审结余款调整单日志
            $balance_finance_log['u_id'] = $array_save['pc_finance_u_id'];
            $balance_finance_log['u_name'] = $array_save['pc_finance_u_name'];
            $balance_finance_log['bi_sn'] = $balance_info['bi_sn'];
            $balance_finance_log['bvl_desc'] = "流水号 {$array_card['pc_serial_number']} 充值{$array_card['pc_money']}元财审成功";
            $balance_finance_log['bvl_type'] = '3';
            $balance_finance_log['bvl_status'] = '1';
            $balance_finance_log['bvl_create_time'] = $array_save['pc_finance_time'];
            if(false === M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($balance_finance_log)){
                M('')->rollback();
                $this->error("生成财审结余款调整单日志失败");
            }
            M('')->commit();
            $this->success('财审成功');
            
        }
    }
    
    /**
     * 充值卡驳回
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function pcProcessingError(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $array_card = $prepaidCard->where(array('pc_status'=>1,'pc_id'=>$ary_post['pc_id']))->find();
        //验证数据有效性
        if(false === $array_card){
            $this->error('充值卡不存在');
        }
        //当前充值卡是否已被使用
        if($array_card['m_id'] == 0){
            $this->error("{$array_card['pc_serial_number']}未使用，无法驳回核");
        }
        //当前充值卡是否已完结
        if($array_card['pc_processing_status'] == 1){
            $this->error("{$array_card['pc_serial_number']}已财审通过，无法驳回");
        }
        //当前充值卡是否已驳回
        if($array_card['pc_processing_status'] == 2){
            $this->error("{$array_card['pc_serial_number']}已驳回，请勿重复操作");
        }
        if(false === $prepaidCard->where(array('pc_id'=>$ary_post['pc_id']))->save(array('pc_processing_status'=>2))){
            $this->error("操作失败");
        }else{
            $this->success("操作成功");
        }
    }
    
    /**
     * 充值卡删除
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-7
     */
    public function deleteCard(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        if(!isset($ary_post['pc_id']) && empty($ary_post['pc_id'])){
            $this->error('请选择要删除的充值卡');
        }
        $array_pc_id = explode(',',$ary_post['pc_id']);
        //开启事物
        M('')->startTrans();
        foreach ($array_pc_id as $pc_id){
            $array_card = $prepaidCard->where(array('pc_status'=>1,'pc_id'=>$pc_id))->find();
            //当前充值卡是否已被使用
            if($array_card['m_id'] == 1){
                M('')->rollback();
                $this->error("删除失败！流水号{$array_card['pc_serial_number']}已被使用");
            }
            if(false === $prepaidCard->where(array('pc_id'=>$pc_id))->delete()){
                M('')->rollback();
                $this->error("操作失败");
            }
        }
        //事物提交
        M('')->commit();
        $this->success('操作成功');
        
        
        
        
    }
    
    /**
     * 充值卡配置页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-19
     */
    public function pageSet(){
        $this->getSubNav(7, 7, 40);
        D('SysConfig')->getCfg('PREPAID_CARD_SET','PREPAID_OPEN','1','是否启用充值卡功能');
        D('SysConfig')->getCfg('PREPAID_CARD_SET','IS_SERVER','1','是否自动客审充值卡');
        D('SysConfig')->getCfg('PREPAID_CARD_SET','IS_FINANCE','1','是否自动财审充值卡');
        $ary_prepaid_data = D('SysConfig')->getCfgByModule('PREPAID_CARD_SET');
        $this->assign($ary_prepaid_data);
        $this->display();
    }
    
    /**
     * 保存充值卡配置
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-19
     */
    public function doSet(){
        $ary_post = $this->_post();
        foreach ($ary_post as $name=>$set_val){
            D('SysConfig')->setConfig('PREPAID_CARD_SET',$name,$set_val);
        }
        $this->success('保存成功');
    }
    
    /* *
     * 弹出充值卡导出对话框
     * @author Micle <yangkewei@guanyisoft.com>
     * 2014-09-05
     */
    public function getExcelDialog(){
        $this->display();
    }
    
    
    /**
     * 充值卡导出
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-20
     */
    public function doExcel(){
        $ary_post = $this->_post();
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        
        if(isset($ary_post['pc_id']) && $ary_post['pc_id']==='ALL'){
            $ary_prepaid_card = $prepaidCard->where(array('pc_status'=>array('neq','2')))->select();                 //添加导出所有充值卡edit Micle <yangkewei@guanyisoft.com> 2014-09-05
        }elseif(isset($ary_post['pc_id']) && !empty($ary_post['pc_id'])){
            $ary_prepaid_card = $prepaidCard->where(array('pc_id'=>array('in',$ary_post['pc_id'])))->select();
        }
        // print_r($ary_prepaid_card);exit;
        if(!empty($ary_prepaid_card) && is_array($ary_prepaid_card)){
            $header = array('流水号', '充值卡名称', '充值卡卡号', '充值卡密码', '面值', '指定会员组', '指定会员等级', '开始时间','结束时间','使用者','使用时间','是否启用','客审状态','财审状态','客审人','财审人','客服确认时间','财务确认时间','充值卡备注');
            $contents = array();
            foreach($ary_prepaid_card as $vl){
                $pc_member_group = '暂无';
                if($vl['pc_member_group']){
                    $ary_group = M('members_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_id'=>array('in',$vl['pc_member_group'])))->getField('mg_name',true);
                    $pc_member_group = implode(',',$ary_group);
                }
                $pc_member_level = '暂无';
                if($vl['pc_member_level']){
                    $ary_level = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_id'=>array('in',$vl['pc_member_level'])))->getField('ml_name',true);
                    $pc_member_level = implode(',',$ary_level);
                }
                if($vl['pc_service_verify'] == 0){
                    $pc_service_verify = '待审核';
                }else{
                    $pc_service_verify = '审核通过';
                }
                if($vl['pc_finance_verify'] == 0){
                    $pc_finance_verify = '待审核';
                }else{
                    $pc_finance_verify = '审核通过';
                }
                $m_name = '暂无';
                if($vl['m_name']){
                    $m_name = $vl['m_name'];
                }
                $is_open = '停用';
                if($vl['is_open'] == 1){
                    $is_open = '启用';
                }
                $pc_service_u_name = '暂无';
                if($vl['pc_service_u_name']){
                    $pc_service_u_name = $vl['pc_service_u_name'];
                }
                $pc_finance_u_name = '暂无';
                if($vl['pc_finance_u_name']){
                    $pc_finance_u_name = $vl['pc_finance_u_name'];
                }
                $contents[0][] = array(
                            "'" . $vl['pc_serial_number'],
                            $vl['pc_name'],
                            $vl['pc_card_number'],
                            $vl['pc_password'],
                            $vl['pc_money'],
                            $pc_member_group,
                            $pc_member_level,
                            $vl['pc_start_time'],
                            $vl['pc_end_time'],
                            $m_name,
                            $vl['pc_use_time'],
                            $is_open,
                            $pc_service_verify,
                            $pc_finance_verify,
                            $pc_service_u_name,
                            $pc_finance_u_name,
                            $vl['pc_service_time'],
                            $vl['pc_finance_time'],
                            $vl['pc_meno']
                        );
                
            }
            $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S');
            $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
            if(!is_dir($filexcel)){
                    @mkdir($filexcel,0777,1);
            }
            $Export = new Export(date('YmdHis') . '.xls', $filexcel);
            $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '充值卡信息', true);
            if (!empty($excel_file)) {
                $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
            } else {
                $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
            }
        }else{
            $this->error('没有要导出的充值卡');
        }
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

}