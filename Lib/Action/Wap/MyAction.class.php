<?php

/**
 * 我的资料Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.0
 * @author Terry
 * @date 2012-12-24
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MyAction extends WapAction {

    /**
     * 控制器初始化方法
     * @author zuo <wanghui@guanyisoft.com>
     * @date 2012-12-19
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 个人中心默认控制器，默认跳转到我的资料页面
     * @author zuo <wanghui@guanyisoft.com>
     * @date 2012-12-24
     */
    public function index() {
        $me = session("Members");
        $m_id = $me['m_id'];
        if(empty($m_id)){
            $this->redirect('Wap/User/login');
        }else {
            $this->redirect(U('Wap/Ucenter/mySelf'));
        }
    }

    /**
     * 修改我的资料信息
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-24
     */
    public function pageProfile() {
        $member = D("Members");
        $me = session("Members");
        if($me['open_id'] != '' && $me['login_type'] == 1){
            $mem = $member->getInfo('',$me['open_id']);
            $mem['m_name'] = $mem['open_name'];
            if($mem['m_status'] == 0){
                $this->error('您还未设置账户名，为了您的账号安全，请设置账号名称和密码','/Wap/My/setThdMembers/');
            }
            session("Members", $mem);
        }else{
            $mem=D('MembersVerify')->where(array('m_id'=>$me['m_id']))->find();
            if(empty($mem) && !is_array($mem)){
                $mem=D('Members')->where(array('m_id'=>$me['m_id']))->find();
            }else{
                $sys_data=D('Members')->where(array('m_id'=>$me['m_id']))->find();
                $mem['ml_id']	 =$sys_data['ml_id'];
                $mem['m_balance']=$sys_data['m_balance'];
                $mem['m_card_no']=$sys_data['m_card_no'];
                $mem['m_ali_card_no']=$sys_data['m_ali_card_no'];
            }
            session("Members", $mem);
        }
        $city = D("CityRegion");
        $city_region_data = $city->getCityRegionInfoByLastCrId($mem['cr_id']);
        /* 取出会员扩展属性项字段 start*/
        $ary_extend_data=D('MembersFields')->displayFields($me['m_id']);
        $this->assign('ary_extend_data', $ary_extend_data);
        /* 取出会员扩展属性项字段 end*/
        if(!empty($mem['m_subcompany_id'])){
            $subcompany_name=D('Subcompany')->where(array('s_id'=>$mem['m_subcompany_id']))->find();
            $mem['subcompany_name']=$subcompany_name['s_name'];
        }
        //取出会员id
        $m_id = $me['ml_id'];
        //取出会员等级
        D('MembersLevel')->autoUpgrade($me['m_id']);
        $ary_res_meml = D('MembersLevel')->getMembersLevels($mem['ml_id']);		//会员升级提示
        $grades = array();
        $ary_men_list = D('MembersLevel')->getgradelist(array('ml_up_fee,ml_id'));
        foreach($ary_men_list as $grade){
            if($grade['ml_id']==($m_id+1)){
                $next_level=$grade['ml_up_fee'].'元';
                break;
            }else{
                $next_level='已是最高等级！';
            }
        }
        $this->assign('next_level', $next_level);
        //是否允许会员编辑
        $memberedit = D('SysConfig')->getCfg('MEMBER_EDIT','MEMBER_EDIT_STATUS','1','是否开启会员编辑功能');        $this->assign('m_balance', $mem['m_balance']);
        $this->assign("region", $city_region_data);
        $this->assign("member", $mem);
        $this->assign("meml",$ary_res_meml);
        $this->assign($memberedit);
        //开启手机验证
        $mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
        $this->assign('is_mobile_validate',intval($mobile_set['VERIFIPHONE_STATUS']['sc_value']));
        if(!empty($mem['m_email'])){
            //是否开启邮箱验证
            $email_set = D('SysConfig')->getCfg('VERIFYEMAIL_SET','VERIFYEMAIL_STATUS','0','开启邮箱验证');
            $this->assign('is_email_validate',intval($email_set['VERIFYEMAIL_STATUS']['sc_value']));
            //判断是否已经验证
            $email_validate_info = D('email_log')->where(array('email'=>$mem['m_email'],'email_type'=>2,'status'=>1))->find();
            if($email_validate_info['check_status'] != '1'){
                $this->assign("check_status", 1);
            }else{
                $this->assign("is_checked_email", 1);
            }
        }
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
    /**
     * 设置第三方授权登录第一次修改用户名与密码
     * author Joe <qianyijun@guanyisoft.com>
     */
    public function setThdMembers(){
        $me = session("Members");
        if(isset($_POST) && !empty($_POST)){
            if($me['open_id'] == '' || $me['m_status'] != 0){
                $this->error('数据有误','/Wap/My/');
            }else{
                $member = D("Members");
                $ary_update_member = array();
                $ary_update_member['m_name'] = $this->_post('m_name');
                $ary_update_member['m_email'] = $this->_post('m_email');
                $ary_update_member['m_status'] = 1;
                $ary_update_member['m_password'] = md5($this->_post('new_m_password'));
                if(false === $member->where(array('m_id'=>$this->_post('m_id')))->save($ary_update_member)){
                    $this->error('数据有误','/Wap/My/');exit;
                }
                $this->success('修改成功！','/Wap/My/');exit;
            }
        }
        $this->assign('mid',$me['m_id']);
        $this->display('setname');
    }

    /**
     * 处理我的资料信息
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-25
     */
    public function doEdit() {
        $ary_post_data = $this->_post();
        $m_id = $this->_post('m_id');
        //开启手机号验证
        $mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
        if($mobile_set['VERIFIPHONE_STATUS']['sc_value'] == '1'){
            if(!empty($ary_post_data['r_m_mobile'])){
                if(empty($ary_post_data['m_mobile_code'])){
                    $this->error("更换手机号需要手机验证");exit;
                }else{
                    $m_mobile_code = $ary_post_data['m_mobile_code'];
                    //验证手机码是否正确
                    $ary_sms_where = array();
                    $ary_sms_where['check_status'] = 0;
                    $ary_sms_where['status'] = 1;
                    $ary_sms_where['sms_type'] = 4;
                    $ary_sms_where['code'] = $m_mobile_code;
                    //$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
                    $sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);
                    if($sms_log['code'] != $m_mobile_code){
                        $this->error('验证码不存在或已过期');exit;
                    }else{
                        //更新验证码使用状态
                        $up_res = D('SmsLog')->updateSms(array('id'=>$sms_log['id']),array('check_status'=>1));
                        if(!$up_res){
                            $this->error('修改会员信息失败,更新验证码状态失败');exit;
                        }
                        //设置其他已发送验证码无效
                        D('SmsLog')->updateSms(array('sms_type'=>4,'check_status'=>0,'mobile'=>$ary_post_data['r_m_mobile']),array('check_status'=>2));
                        $ary_post_data['m_mobile'] = $ary_post_data['r_m_mobile'];
                    }
                }
            }
        }
        $sys_data=D('Members')->where(array('m_id'=>$m_id))->find();
        $ary_cfg = D("SysConfig")->getConfigs("MEMBER_SET", "MEMBER_STATUS");
        /**
        if(1 != $ary_cfg){
        session("Members", null);
        }
         **/
        $ary_params = array(
            'm_id'=>$m_id,
            'm_name' => $this->_post('m_name'),
            'm_id_card' => $this->_post('m_id_card'),
            'm_real_name' => $this->_post('m_real_name'),
            'm_birthday' => $this->_post('m_birthday'),
            'm_sex' => $this->_post('sex', 'htmlspecialchars', 2),
            'm_address_detail' => $this->_post('m_address_detail'),
            'm_email' => $this->_post('m_email'),
            'm_password' => $sys_data['m_password'],
            'm_status'=>$sys_data['m_status'],
            'm_subcompany_id'=>$sys_data['m_subcompany_id'],
            'm_website_url' => $this->_post('m_website_url'),
            'm_alipay_name' => $this->_post('m_alipay_name'),
            'm_balance_name' => $this->_post('m_balance_name'),
            'm_zipcode' => $this->_post('m_zipcode'),
            'm_mobile' => $ary_post_data['m_mobile'],
            'm_telphone' => $this->_post('m_telphone'),
            'm_qq' => $this->_post('m_qq'),
            'm_verify'  => 1 == $ary_cfg['MEMBER_STATUS']['sc_value'] ? 2 : 4,
            'm_wangwang' => $this->_post('m_wangwang'),
            'm_update_time' => date('Y-m-d H:i:s'),
            'm_recommended' => $this->_post('m_recommended')
        );
        if(strpos($ary_params['m_mobile'], '*')) {
            unset($ary_params['m_mobile']);
        }else{
			$arr_members = D('Members')->field('m_mobile')->where(array('m_id'=>$m_id))->find();
			if(!empty($arr_members) && $ary_params['m_mobile']!= decrypt($arr_members['m_mobile'])){
				if (D('Members')->checkMobile(encrypt($ary_params['m_mobile']))) {
					$this->error("该手机号已被注册，请重新输入");exit;
				}
			}
            $ary_params['m_mobile'] = encrypt($ary_params['m_mobile']);
        }		
        if($ary_params['m_telphone']){
            $ary_params['m_telphone'] = encrypt($ary_params['m_telphone']);
        }
        if(!empty($ary_params['m_recommended'])){
            $reMid = D('Members')->where(array('m_name'=>$ary_params['m_recommended']))->getField('m_id');
            if(empty($reMid)){
                unset($ary_params['m_recommended']);
            }
        }
        if ($this->_post('region1') > 0) {
            $ary_params['cr_id'] = $this->_post('region1');
        }
        elseif ($this->_post('city') > 0){
            $ary_params['cr_id'] = $this->_post('city');
        }
        $province=$this->_post('province');
        if (!empty( $province) && isset($province)) {
            $area_data=D('AreaJurisdiction')->where(array('cr_id'=>$province))->find();
            if(!empty($area_data['s_id'])){
                $ary_params['m_subcompany_id'] = $area_data['s_id'];
            }
        }
        foreach($ary_params as $key=>$vuale){
            if(!isset($vuale)){
                unset($ary_params[$key]);
            }
        }
        $member = D("Members");
        if (empty($m_id) && (int) $m_id == 0) {
            $this->error("参数错误");
        }

        //$arr_members = D('MembersVerify')->where(array('m_id'=>$m_id))->find();
        //if(!empty($arr_members) && is_array($arr_members)){
        //$ary_result = D('Members')->where(array('m_id'=>$m_id))->data($ary_params)->save();
        //$ary_result = D('MembersVerify')->where(array('m_id'=>$m_id))->data($ary_params)->save();
        //$ary_result = D('MembersVerify')->where(array('m_id'=>$m_id))->delete();
        //}else{
        //$ary_result = D('MembersVerify')->add($ary_params);
        //}
        $ary_result = D('Members')->where(array('m_id'=>$m_id))->data($ary_params)->save();
		//会员推送到线下系统
		if( C('AUTO_SNYC_MEMBER')==1){
			if(isset($ary_params['m_id']) && $ary_params['m_id']!=''){
				$ary_params['method'] ='UpdateMember';
				$ary_params['m_mobile'] = $ary_post_data['m_mobile'];
				$ary_params['m_telphone'] = $this->_post('m_telphone');
				$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
				$host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
				request_by_fsockopen($host_url.'/Script/Batch/snyc_send',$ary_params);
                unset($ary_params['method']);				
			}	 
		}
//        $ary_result = D('MembersVerify')->add($ary_params);
//        echo "<pre>";print_r(D('MembersVerify')->getLastSql());exit;
        // $res_del =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$m_id))->delete();
        // if($res_del){
        $ary_res=D('MembersFieldsInfo')->doAdd($this->_post(), $m_id,2);
        //}
        if ($ary_result || $ary_res['result']) {
            //用户资料日志记录
            $ary_members_log = array(
                'm_id' => $m_id,
                'update_time' => date('Y-m-d H:i:s')
            );
            $res_members_log = D('MembersLog')->add($ary_members_log);
            $ary_member_data=D('Members')->where(array('m_id'=>$m_id))->find();
            $PointCfg = D('PointConfig')->getConfigs();
            if($PointCfg['is_consumed'] == 1){
                //搜索出会员属性字段有设置返回积分项
                $ary_member_fields=M('MembersFields',C('DB_PREFIX'),'DB_CUSTOM')->where(array('is_need'=>0,'is_display'=>1,'if_status'=>1,'fields_content'=>array('neq',''),'filelds_point'=>array('neq',0)))->select();
                foreach($ary_member_data as $key => $data){
                    foreach($ary_member_fields as $field){
                        if(!empty($data) && !empty($field['fields_content']) && $key == $field['fields_content'] && $field['fields_point'] != 0){
                            $type_id = $field['id']+100;
                            //判断是否已获赠过积分一次
                            $ary_where = array();
                            $ary_where['type'] = $type_id;
                            $ary_where['m_id'] = $m_id;
                            $point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
                            if(empty($point_exsit)){
                                $res_point = D('PointConfig')->setMemberRewardPoints($field['fields_point'],$m_id,$type_id);
                                if(!$res_point['result']){
                                    $this->error($res_point['message']);
                                }
                            }
                        }
                    }
                }
                //搜索出会员其他属性项有设置积分
                $ary_point_fields=M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')
                    ->join(C('DB_PREFIX').'members_fields on(id = field_id)')
                    ->where(array('u_id'=>$m_id,'is_need'=>0,'is_display'=>1,'is_edit'=>1,'is_status'=>1,'filelds_point'=>array('neq','0')))
                    ->select();
                foreach($ary_point_fields as $field){
                    $type_id = $field['id']+100;
                    //判断是否已获赠过积分一次
                    $ary_where = array();
                    $ary_where['type'] = $type_id;
                    $ary_where['m_id'] = $m_id;
                    $point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
                    if(empty($point_exsit)){
                        $res_point = D('PointConfig')->setMemberRewardPoints($field['fields_point'],$m_id,$type_id);
                        if(!$res_point['result']){
                            $this->error($res_point['message']);
                        }
                    }
                }
            }
            //推荐人
            if(empty($sys_data['m_recommended']) && !empty($ary_params['m_recommended'])){
                $sys_data['m_recommended'] = $ary_params['m_recommended'];
                D('Members')->addRecommended($sys_data);
            }
            session("Members", $ary_member_data);
            $this->success('修改成功', U('Wap/My/pageProfile'));
        } else {
            $this->error("修改失败，请重试...");
        }
    }

    /**
     * 异步获取 区域数据
     */
    public function cityRegionOptions() {
        if (!isset($_POST["parent_id"]) || !is_numeric($_POST["parent_id"]) || $_POST["parent_id"] <= 0) {
            echo json_encode(array("status" => false, "data" => array(), "message" => "父级区域ID不合法"));
            exit;
        }
        $int_parent_id = $_POST["parent_id"];
        $array_tmp_result = D("Gyfx")->selectAllCache('city_region','cr_id,cr_name', array("cr_parent_id" => $int_parent_id,'cr_status'=>'1'), array("cr_order" => "asc"));
        $array_result = array();
        foreach($array_tmp_result as $tmp_result){
            $array_result[$tmp_result['cr_id']] = $tmp_result['cr_name'];
        }
        unset($array_tmp_result);
        //$array_result = D("CityRegion")->where(array("cr_parent_id" => $int_parent_id,'cr_status'=>'1'))->order(array("cr_order" => "asc"))->getField("cr_id,cr_name");
        if (false === $array_result) {
            echo json_encode(array("status" => false, "data" => array(), "message" => "无法获取区域数据"));
            exit;
        }
        echo json_encode(array("status" => true, "data" => $array_result, "message" => "success"));
        exit;
    }

    /**
     * 获取地区信息
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-25
     */
    public function getCityRegion() {
        $parent = $this->_post('parent');
        $item = $this->_post('item');
        $val = $this->_post('val');
        $city = D("CityRegion");
        $ary_city = $city->getCurrLvItem($parent);
        //echo "<pre>";print_r($ary_city);exit;
        if (!empty($ary_city) && is_array($ary_city)) {
            $str = '';
            if ($item == 'city') {
                $str = "onchange=\"selectCityRegion(this, 'region','')\";";
            }
            if ($item == 'province') {
                $str = "onchange=\"selectCityRegion(this, 'city','')\";";
            }
            $html = "<select id='" . $item . "' name='" . $item . "' {$str}>";
            $html .= '<option value="0">请选择</option>';
            if (count($ary_city) > 0) {
                foreach ($ary_city as $item) {
                    if ($item['cr_id'] == $val) {
                        $html .= "<option value='{$item['cr_id']}' item='1' selected='selected'>{$item['cr_name']}</option>";
                    } else {
                        $html .= "<option value='{$item['cr_id']}' >{$item['cr_name']}</option>";
                    }
                }
            }
            $html .= "</select>";
        } else {
            $html = '';
        }
//        echo "<pre>";print_r($html);exit;
        echo $html;
        exit;
    }

    /**
     * 修改密码操作
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-26
     */
    public function pageChangePass() {
        $member = D("Members");
        $me = session("Members");
        if(empty($me)){
            $this->redirect(U('Wap/User/login'));
        }
		if(strpos($me['m_name'],'oh2s_'>=0)){
			$this->error('您还未设置账户名，为了您的账号安全，请设置账号名称和密码','/Wap/My/setThdMembers/');
		}
        if($me['open_id'] != '' && $me['login_type'] == 1){
            $mem = $member->getInfo('',$me['open_id']);
            if($mem['m_status'] == 0){
                $this->error('您还未设置账户名，为了您的账号安全，请设置账号名称和密码','/Wap/My/setThdMembers/');
            }
        }
        $mem = $member->getInfo($me['m_name']);
        //echo "<pre>";print_r($mem);exit;
        $this->assign('member', $mem);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * 处理修改密码操作
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-27
     */
    public function doChange() {
        $ary_res = array('success' => 0, 'errCode' => '1000', 'msg' => '修改失败', 'data' => '');
        $mem = session("Members");
		$mem['m_name'] = D('Members')->where(array('m_id'=>$mem['m_id']))->getField('m_name');
        $params = array(
            'm_password' => $this->_post('m_password'),
            'm_name' => $mem['m_name'],
            'm_id' => $mem['m_id'],
            'new_m_password' => $this->_post('new_m_password'),
            'confirmPassword' => $this->_post('confirmPassword'),
        );

        if (empty($params['m_password']) || empty($params['new_m_password']) || empty($params['confirmPassword'])) {
            $ary_res['msg'] = '必填项不能为空...';
            $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
        }
        $member = D("Members");
        $ary_result = $member->checkNamePassword($params['m_name'], md5($params['m_password']));
        if (empty($ary_result) && !is_array($ary_result)) {
            $ary_res['msg'] = '当前登录密码错误';
            $ary_res['errCode'] = '1002';
            $this->ajaxReturn($ary_res);
        }
        if ($params['new_m_password'] != $params['confirmPassword']) {
            $ary_res['msg'] = '新密码和确认密码必须相同...';
            $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
        }
        if (strlen($params['new_m_password']) < 4 || strlen($params['new_m_password']) > 16) {
            $ary_res['msg'] = '密码至少4位且不能大于16位...';
            $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
        }
        if (empty($ary_res) && !is_array($ary_res)) {
            $ary_res['msg'] = '操作错误，请重试...';
        } else {
            if ($params['m_password'] == $params['new_m_password']) {
                $ary_res['msg'] = '新密码不能与原密码相同';
                $ary_res['errCode'] = '1001';
            } else {
                $ary_save = $member->doChange($params['m_id'], $params['new_m_password'],$params['m_name']);
                if ($ary_save) {
                    $ary_res['msg'] = '修改成功';
                    $ary_res['success'] = '1';
                } else {
                    $ary_res['errCode'] = '1003';
                }
            }
        }
        $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
    }

    /**
     * @param 我的收货地址页面
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-27
     * @return mixed array
     */
    public function pageDeliver() {
        //获取会员信息
        $member = session("Members");
        if (empty($member) && !isset($member['m_id'])) {
            $this->redirect('Wap/User/login');
        }
        //获取一级城市
        $city = D("CityRegion");
		$pids=$this->_get('pids');
		//自提
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		$zt=$this->_get('zt');
		if($zt==true && $is_zt['IS_ZT']['sc_value']==1){
			$this->assign("zt", $zt);
		}

        //获取会员对应的收货地址
        $ary_deliver = $city->getReceivingAddress($member['m_id']);
        foreach($ary_deliver as &$val){
         $val['ra_mobile_phone'] = !strpos($val['ra_mobile_phone'],':') && !strpos($val['ra_mobile_phone'],'*') ? vagueMobile($val['ra_mobile_phone']) : $val['ra_mobile_phone'];
        }
        $empty = '';
        if (empty($ary_deliver) && !is_array($ary_deliver)) {
            $empty .= "<tr><td colspan='6'>暂无数据</td></tr>";
        }
        $this->assign("empty", $empty);
        $this->assign("member", $member);
        $this->assign("deliver", $ary_deliver);
		$this->assign("pids", $pids);
        //echo "<pre>";print_r($ary_deliver);exit;
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * @param 删除收货地址
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-28
     * @return int
     */
    public function doDelDeliver() {
        $ary_res = array('success' => 0, 'errCode' => '1000', 'msg' => '删除失败', 'data' => '');
        $ra_id = $this->_post("ra_id");
		$pids = $this->_post("pids");
        if (empty($ary_res) && (int) $ra_id <= 0) {
            $ary_res['msg'] = "参数错误，请重试";
        } else {
            $city = D("CityRegion");
            $ary_result = $city->doDelDeliver($ra_id);
            if ($ary_result) {
				$zt = $this->_post("zt");
				$pids = $this->_post("pids");
				if($zt=='1'){
					$this->success('删除成功', array('确认' => U('Wap/My/pageDeliver',array('zt' => 1,'pids'=>$pids))));
				}else{
					$this->success('删除成功', array('确认' => U('Wap/My/pageDeliver',array('pids'=>$pids))));
				}
                //$ary_res['msg'] = "删除成功";
                //$ary_res['success'] = "1";
                //$ary_res['data'] = $ra_id;
            } else {
                $this->error('删除失败');
                //$ary_res['errCode'] = '1001';
            }
        }

        $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
    }

    /**
     * @param 添加收货地址
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-28
     * @return int
     */
    public function doAddDeliver() {
        $ary_res = array('success' => 0, 'errCode' => '1000', 'msg' => '添加失败', 'data' => '');
        $m_id = $this->_post('m_id');
        if (empty($m_id)) {
            $member = session("Members");
            $m_id = $member['m_id'];
        }

        $str_mobile = '';
        $isMobile = $this->_post('isMobile');
        if (!empty($isMobile)) {
            $isMobile_2 = $this->_post('isMobile_2');
            $str_mobile .=$isMobile;
            if (!empty($isMobile_2)) {
                $str_mobile .= "-" . $isMobile_2;
                $isMobile_3 = $this->_post('isMobile_3');
                if (!empty($isMobile_3)) {
                    $str_mobile .= "-" . $isMobile_3;
                }
            }
        }
        $params = array(
            'ra_name' => $this->_post('ra_name'),
            'ra_detail' => $this->_post('ra_detail'),
            'ra_mobile_phone' => $this->_post('ra_mobile_phone'),
            'ra_phone' => $str_mobile,
            'ra_post_code' => $this->_post('ra_post_code'),
            'ra_is_default' =>intval($this->_post('ra_is_default'))
        );
        $city = D("CityRegion");
        $raid = $this->_post('raid');
        $str_region = $this->_post('region');
        if(empty($str_region)){
            $str_region = $this->_post('city');
        }
        if(empty($params['ra_name']) || empty($params['ra_mobile_phone'])){
            $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => '修改失败,联系人的电话或名字不能为空'));exit;
        }
        if(empty($params['ra_detail'])){
             $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => '修改失败,详细地址必填'));exit;
        }
        if (!empty($raid) && (int) $raid > 0) {
            $params['ra_id'] = $raid;
            $params['cr_id'] = $str_region;
            if(empty($params['cr_id'])){
                $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => '修改失败,请先选择省市区信息'));exit;
            }
            $edit_city = $city->updateAddr($params,$m_id);
        } else {
            $params['cr_id'] = $str_region;
            if(empty($params['cr_id'])){
                $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => '添加失败,请先选择省市区信息'));exit;
            }
            $edit_city = $city->addReceiveAddr($params, $m_id);
        }
        if (!empty($edit_city) && $edit_city['status'] == '1') {
            $ary_res['success'] = $edit_city['status'];
            $ary_res['msg'] = $edit_city['msg'];
            $ary_res['data'] = $edit_city['data']['ra_id'];
        } else {
            $ary_res['msg'] = $edit_city['msg'];
        }
        $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
    }

    /**
     * 收货地址编辑页面
     * @date 2015-01-13
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     */
    public function editDeliver (){
        $city = D("CityRegion");
        $ary_city = $city->getCurrLvItem(1);
        $raid = $this->_get('raid');
        $me = session("Members");
        $m_id = $me['m_id'];
        if(empty($m_id)){
            $this->redirect('Wap/User/login');
        }
		//自提
		$zt=$this->_get('zt');
		if($zt==true){
			$this->assign("zt", $zt);
		}
        if (!empty($raid) && (int) $raid > 0) {
            //根据收获ID，获取对应相关数据
            $ary_racity = $city->getReceivingAddress($m_id, $raid);
            //echo'<pre>';print_r($raid);die;
            if (!empty($ary_racity) && is_array($ary_racity)) {
                $city_region_data = $city->getFullAddressId($ary_racity['cr_id']);
                $js_city = "<script>
                                selectCityRegion('1','province','{$city_region_data[1]}');
                                selectCityRegion('{$city_region_data[1]}','city','{$city_region_data[2]}');
                                selectCityRegion('{$city_region_data[2]}','region','{$city_region_data[3]}');
                               </script>";
                $this->assign("js_city", $js_city);
                $this->assign('raid', $raid);
                $ary_racity['mobile'] = explode('-', $ary_racity['ra_phone']);
                //echo "<pre>";print_r($ary_racity);exit;
                $this->assign("edit_city", $ary_racity);
            }
        }else{
            $this->error('请选择您要编辑的收货地址',U('Wap/My/pageDeliver'));
        }
		$pids=$this->_get('pids');
		$this->assign("pids", $pids);
        $this->assign("citys", $ary_city);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
    /**
     * 获取当前用户留言信息 by wangguibin
     * @data 2013.06.25
     */
    public function feedBackList() {
        $fb_obj = M('feedback', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_get = $this->_get();
        $ary_where = array();       //留言搜索条件
        $member = session("Members");
        $u_id = $member['m_id'];
        $pageSize = 3;
        $data = D("Feedback")->getMsgByUser($u_id, $pageSize);
        $lists = $data['data'];
//        echo "<pre>";print_r($lists);exit;
        foreach ($lists as &$list) {
            switch ($list['msg_type']) {
                case 1:
                    $list['msg_type_title'] = '投诉';
                    break;
                case 2:
                    $list['msg_type_title'] = '咨询';
                    break;
                case 3:
                    $list['msg_type_title'] = '售后';
                    break;
                case 4:
                    $list['msg_type_title'] = '求购';
                    break;
                default:
                    $list['msg_type_title'] = '留言';
                    break;
            }
            if (!$list['msg_status'] && !$list['parent_id'])
                $msg_0_0 = 1; //客户有留言 卖家未回复
            if ($list['msg_status'] && !$list['parent_id'])
                $msg_0_0 = 2; //客户有留言 卖家已回复
            switch ($msg_0_0) {
                case 1:
                    $list['reply_info'] = '未回复';
                    break;
                case 2:
                    $list['reply_info'] = '已回复&nbsp;<a href="javascript:void(0);" rel="' . $list['msg_id'] . '" class="sgan_reply_a" >';
                    if (empty($list['readed'])) {
                        $list['reply_info'] .='<strong id="show' . $list['msg_id'] . '"  >查看(未阅读)</strong>';
                    } else {
                        $list['reply_info'] .='查看(已阅读)';
                    }
                    break;
                default:
                    break;
            }
            $list['mdata'] = D("Feedback")->getReplyData($list['msg_id']);
        }
        $this->assign("page", $data['page']);
        $this->assign("u_id", $u_id);
        $this->assign("id", $ary_get['tid']);
        $this->assign("data", $lists);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * 保存订单留言 add by wangguibin
     * @data 2013-06-26
     */
    public function msgSave() {
        $post_data = $this->_post();
        $member = session("Members");
        $u_id = $member['m_id'];
        if (empty($u_id)) {
            $this->error("请您先登录后再留言！");
        }
        $fb_obj = M('feedback', C('DB_PREFIX'), 'DB_CUSTOM');
        if ($post_data['type'] == 'reply') {
            $replyData = D("Feedback")->getMsgListById($post_data['msg_id']);
            $data['user_id'] = $u_id;
            $data['user_name'] = $member['m_name'];
            $data['user_mobile'] = $member['m_mobile'];
            $data['msg_title'] = '回复' . $replyData[0]['msg_title'];
            $data['parent_id'] = $post_data['msg_id'];
            $data['msg_content'] = $post_data['content'];
            $data['msg_time'] = date('Y-m-d H:i:s');
            $data['order_id'] = $post_data['tid'];
            $type = 'reply';
        } else {
            $data['user_id'] = $u_id;
            $data['user_name'] = $member['m_name'];
            $data['user_mobile'] = $member['m_mobile'];
            $data['msg_title'] = $post_data['msgTitle'];
            $data['msg_type'] = $post_data['msg_type'];
            $data['msg_content'] = $post_data['msg_content'];
            $data['msg_time'] = date('Y-m-d H:i:s');
            $data['order_id'] = $post_data['tid'] ? $post_data['tid'] : '非订单留言';
            $type = 'init';
            $num = D("Feedback")->validateMsg($data);
            if ($num > 0) {
                $this->error("您已留言成功,无需再次留言！");
            }
            try {
                //上传图片
                if ($_FILES['filename']['name']) {
                    @mkdir('./Public/Uploads/' . CI_SN . '/feedback/');
                    import('ORG.Net.UploadFile');
                    $upload = new UploadFile(); // 实例化上传类
                    $upload->maxSize = 3145728; // 设置附件上传大小
                    $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                    $upload->savePath = './Public/Uploads/' . CI_SN . '/feedback/'; // 设置附件上传目录
                    if (!$upload->upload()) {// 上传错误提示错误信息
                        $this->error($upload->getErrorMsg());
                    } else {// 上传成功 获取上传文件信息
                        $info = $upload->getUploadFileInfo();
                        $data['file_url'] = '/Public/Uploads/' . CI_SN . '/feedback/' . $info[0]['savename'];
                    }
                }
            } catch (Exception $e) {
                $this->error("上传附件失败，抱歉，您的留言未成功！！");
            }
        }
        if ($res = D("Feedback")->saveOrderMsg($data, $type)) {
            if ($res == '1') {
                $this->success("恭喜，您的留言已成功！");
            } else {
                $this->error("您已留言成功,无需再次留言！");
            }
        } else {
            $this->error("抱歉，您的留言未成功！");
        }
    }

    /**
     * 保存订单留言 add by wangguibin
     * @data 2013-06-27
     */
    public function msgReadsave() {
        $msg_id = intval($this->_post('msg_id'));
        if (!empty($msg_id)) {
            $result = D("Feedback")->field('readed')->where(array('msg_id' => $msg_id))->find();
            if ($result['readed'] == '1') {
                $res = 2;
            } else {
                $res = D("Feedback")->where(array('parent_id' => $msg_id))->data(array('readed' => '1'))->save();
                $res = D("Feedback")->where(array('msg_id' => $msg_id))->data(array('readed' => '1'))->save();
                $res = 1;
            }
        }
        if ($res) {
            if ($res == '2') {
                $ret['status'] = '202';
                $ret['message'] = '回复的留言已读';
                die(json_encode($ret));
            } else {
                $ret['status'] = '200';
                $ret['message'] = '回复的留言已读';
                die(json_encode($ret));
            }
        } else {
            $ret['status'] = '300';
            $ret['message'] = '回复的留言读取失败';
            die(json_encode($ret));
        }
    }

    /**
     * 删除一条订单留言信息 by wangguibin
     * @data 2011.09.14
     */
    public function delFeedBack() {
        $mid = trim($_POST['Mid']);
        if ($mid) {
            $where['msg_id'] = $mid;
            $count = D("Feedback")->where($where)->count();
            if ($count == '1') {
                if (D("Feedback")->where(array('msg_id' => $mid))->delete()) {
                    echo 'succ';
                    exit;
                } else {
                    echo 'false';
                    exit;
                }
            } else {
                echo 'false';
                exit;
            }
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * @param 我的增值税发票页面
     * @author czy <chenzongyao@guanyisoft.com>
     * @version 7.3
     * @since stage 1.5
     * @modify 2013-08-11
     * @return mixed array
     */
    public function pageInvoice() {
        //获取会员信息
        $member = session("Members");
        if (empty($member) && !isset($member['m_id'])) {
            $this->redirect('Wap/User/login');
        }

        //获取发票收藏里面增值税发票
        $obj_invoice_collect = D("InvoiceCollect");
        $ary_invoice_collect = $obj_invoice_collect->get($member['m_id'],2,true);

        $this->assign("invoices", $ary_invoice_collect);
        $this->assign("member", $member);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * @param 添加增值税发票
     * @author czy <chenzongyao@guanyisoft.com>
     * @version 7.3
     * @since stage 1.5
     * @modify 2013-08-11
     * @return int
     */
    public function doAddInvoice() {
        $ary_res = array('success' => 0, 'errCode' => '1000', 'msg' => '添加失败', 'data' => '');

        $m_id = $this->_post('m_id');

        if (empty($m_id)) {
            $member = session("Members");
            $m_id = $member['m_id'];
        }

        $params = array(
            'is_invoice' => 1,
            'invoice_type' => 2,
            'invoice_head' => 2,
            'invoice_name' => $this->_post('invoice_name',''),
            'm_id' => $m_id,
            'invoice_identification_number' => $this->_post('invoice_identification_number',''),
            'invoice_address' => $this->_post('invoice_address',''),
            'invoice_phone' => $this->_post('invoice_phone',''),
            'invoice_bank' => $this->_post('invoice_bank',''),
            'invoice_account' => $this->_post('invoice_account',''),
            'create_time'=>date('Y-m-d H:i:s')
        );
        $int_is_auto_verify = D("InvoiceConfig")->getField("is_auto_verify");
        if($int_is_auto_verify){
            $params['is_verify'] = 1;
        }
        $obj_invoice = D("Invoice");
        $ary_invoice = $obj_invoice->get();
        $ary_invoice_content = isset($ary_invoice['invoice_content']) && !empty($ary_invoice['invoice_content']) ? explode(',',$ary_invoice['invoice_content']) : '';
        if(isset($ary_invoice_content[intval($this->_post('invoice_content',1))-1])){
            $params['invoice_content'] = $ary_invoice_content[intval($this->_post('invoice_content',1))-1];
        }
        else {
            $params['invoice_content'] = '';
        }
        $verify = M('invoice_config',C('DB_PREFIX'),'DB_CUSTOM')->field('is_auto_verify')->find();
        if($verify['is_auto_verify'] == '1'){
            $params['is_verify'] = 1;
        }
        $int_or_id = M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->add($params);

        if ($int_or_id>0) {
            $ary_res['success'] = 1;
            $ary_res['msg'] = '新赠增值税发票成功';
            $ary_res['data'] = $int_or_id;
        } else {
            $ary_res['msg'] = '新赠增值税发票出现异常';
        }
        $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
    }

    /**
     * 手机更换发送短信
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-08-07
     */
    public function sendMobileCode() {
        //开启手机验证
        $mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
        if($mobile_set['VERIFIPHONE_STATUS']['sc_value'] != 1){
            $this->ajaxReturn(array('status'=>0,'msg'=>'未开启手机验证'));
        }
        $ary_post =  $this->_post();
        if($ary_post['r_m_mobile'] == $ary_post['m_mobile']){
            $this->ajaxReturn(array('status'=>0,'msg'=>'更换的手机号不能和原手机号相同'));
        }
        if(empty($ary_post['r_m_mobile'])){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请先输入需要更换的手机号'));
        }
        //判读是不是手机格式
        $m_mobile = $ary_post['r_m_mobile'];
        if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请输入正确的手机号格式！'));
        }
        if (D('Members')->checkMobile($m_mobile)) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'该手机号已被注册，请重新输入！'));
        }
        //判断手机号是否在90秒内已发送短信验证码
        $ary_sms_where = array();
        $ary_sms_where['check_status'] = array('neq',2);
        $ary_sms_where['status'] = 1;
        $ary_sms_where['sms_type'] = 4;
        $ary_sms_where['mobile'] = $m_mobile;
        $ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
        $sms_log_count = D('SmsLog')->getCount($ary_sms_where);
        if($sms_log_count>0){
            $this->ajaxReturn(array('status'=>0,'msg'=>'90秒后才允许重新获取验证码！'));
        }
        $SmsApi_obj=new SmsApi();
        //获取注册发送验证码模板
        $template_info = D('SmsTemplates')->sendSmsTemplates(array('code'=>'MODIFY_MOBILE'));
        $send_content = '';
        if($template_info['status'] == true){
            $send_content = $template_info['content'];
        }
        if(empty($send_content)){
            $this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败！'));
        }
        $array_params=array('mobile'=>$ary_post['r_m_mobile'],'','content'=>$send_content);
        $res=$SmsApi_obj->smsSend($array_params);
        if($res['code'] == '200'){
            //日志记录下
            $ary_data = array();
            $ary_data['sms_type'] = 4;
            $ary_data['mobile'] = $ary_post['r_m_mobile'];
            $ary_data['content'] = $send_content;
            $ary_data['code'] = $template_info['code'];
            $sms_res = D('SmsLog')->addSms($ary_data);
            if(!$sms_res){
                writeLog('短信发送失败', 'SMS/'.date('Y-m-d').txt);
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>'短信发送成功！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'短信发送失败，'.$res['msg']));
        }
    }

    /**
     * 是否开启手邮箱
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-08-11
     */
    public function sendEmailCode() {
        //开启邮箱验证
        $email_set = D('SysConfig')->getCfg('VERIFYEMAIL_SET','VERIFYEMAIL_STATUS','0','开启手机验证');
        if($email_set['VERIFYEMAIL_STATUS']['sc_value'] != 1){
            $this->ajaxReturn(array('status'=>0,'msg'=>'未开启邮箱验证'));
        }
        $ary_post =  $this->_post();
        $member_count = D('Members')->where(array('m_email'=>$ary_post['m_email'],'m_id'=>array('neq',$_SESSION['Members']['m_id'])))->count();
        if($member_count>0) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'该邮箱已经注册！'));
        }
        //判断邮箱是否已验证
        $ary_sms_where = array();
        $ary_sms_where['check_status'] = 1;
        $ary_sms_where['status'] = 1;
        $ary_sms_where['email_type'] = 2;
        $ary_sms_where['email'] = $ary_post['m_email'];
        $email_log_count = D('EmailLog')->getCount($ary_sms_where);
        if($email_log_count>0){
            $this->ajaxReturn(array('status'=>0,'msg'=>'该邮箱已经验证无需再次验证！'));
        }
        $SmsApi_obj=new SmsApi();
        //获取注册发送验证码模板
        $ary_option = D('EmailTemplates')->sendValidateEmail($_SESSION['Members']['m_password'],$_SESSION['Members']['m_name'],$ary_post['m_email']);
        if(empty($ary_option['message'])){
            $this->ajaxReturn(array('status'=>0,'msg'=>'获取邮件模板失败！'));
        }
        //发送邮件
        $email = new Mail();
        if ($email->sendMail($ary_option)) {
            //日志记录下
            $ary_data = array();
            $ary_data['email_type'] = 2;
            $ary_data['email'] = $ary_post['m_email'];
            $ary_data['content'] = $ary_option['message'];
            $sms_res = D('EmailLog')->addEmail($ary_data);
            if(!$sms_res){
                writeLog(json_encode($ary_data),date('Y-m-d')."send_email.log");
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>'邮箱验证邮件已经发送到您的邮箱'));
        } else {
            $this->ajaxReturn(array('status'=>0,'msg'=>'验证邮件发送失败，请管理员检查邮件发送设置'));
        }
    }

    /**
     * 验证手机验证码是否存在
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-08-05
     */
    public function checkMobileCode() {
        //判读是不是手机格式
        $m_mobile_code = $this->_get('m_mobile_code');
        if(empty($m_mobile_code)){
            $this->ajaxReturn('手机验证码不正确');
        }
        //判断手机号是否在90秒内已发送短信验证码
        $ary_sms_where = array();
        $ary_sms_where['check_status'] = 0;
        $ary_sms_where['status'] = 1;
        $ary_sms_where['sms_type'] = 4;
        $ary_sms_where['code'] = $m_mobile_code;
        //$ary_sms_where['create_time'] = array('egt',date("Y-m-d H:i:s", strtotime(" -90 second")));
        $sms_log = D('SmsLog')->getSmsInfo($ary_sms_where);
        if($sms_log['code'] != $m_mobile_code){
            $this->ajaxReturn('验证码不存在或已过期');exit;
        }else{
            $this->ajaxReturn(true);exit;
        }
        $this->ajaxReturn('验证码不存在或已过期');exit;
    }
    /**
     * 验证手机号是否唯一及格式验证
     */
    public function checkMobile() {
        //判读是不是手机格式
        $m_mobile = $this->_get('r_m_mobile');
        if(!preg_match("/^1[0-9]{1}[0-9]{1}[0-9]{8}$/",$m_mobile)){
            $this->ajaxReturn('请输入正确的手机号格式！');
        }
        if (D('Members')->checkMobile($m_mobile)) {
            $this->ajaxReturn('该手机号已被注册，请重新输入！');
        } else {
            $this->ajaxReturn(true);
        }
    }

    /**
     * 验证邮箱是否唯一
     */
    public function checkEmail() {
        $m_id = $_SESSION['Members']['m_id'];
        $m_email = $this->_get('m_email');
        $m_count = D('Members')->where(array('m_email'=>$m_email,'m_id'=>array('neq',$m_id)))->count();
        if ($m_count>0) {
            $this->ajaxReturn('该邮箱已被注册，请重新输入！');
        } else {
            $this->ajaxReturn(true);
        }
    }

    /**
     * 执行重置密码命令
     * @author wangguibin wangguibin@guanyisoft.com
     * @date 2013-04-11
     */
    public function doEmailValidate() {
        $member = D('Members');
        //解密连接代码
        $code = authcode(base64_decode($this->_get('code')), 'DECODE');
        //验证
        $result = $member->checkNamePassword($this->_get('name'), $code);
        if (FALSE == $code || FALSE == $result) {
            $this->error('非法链接或者链接已经失效');
        }
        //更新验证码使用状态
        $up_res = D('EmailLog')->updateEmail(array('email'=>$result['m_email'],'email_type'=>2,'check_status'=>0),array('check_status'=>1));
        if($up_res){
            $this->success('邮箱验证成功', U('Wap/My/pageProfile'));
        }else{
            $this->error('邮箱验证失败', U('Wap/My/pageProfile'));
        }
    }


}

