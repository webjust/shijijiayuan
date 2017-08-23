<?php

/**
 * 会员推广Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.4
 * @author zhangjiasuo
 * @date 2013-09-30
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PromotingAction extends CommonAction {

    /**
     * 控制器初始化
     *
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-30
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    public function index(){
         $this->redirect(U('Ucenter/Promoting/userSpread'));
    }

    /**
     * 我要推广页面
     *
     * @version 7.4
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-09-30
     */
    public function userSpread() {
        $this->getSubNav(3, 2, 10);
        //生成二维码图片
        $ary_data = D('SysConfig')->getCfgByModule('GY_SHOP');
        $ary_data['GY_SHOP_TG_LOGO'] = $this->doCreateQcPic();  
        
        $default_port=80;
        $member = session("Members");
        $domain_name=$_SERVER["HTTP_HOST"];
        if($default_port != $_SERVER["SERVER_PORT"]){
            $domain_name.= ':' . $_SERVER["SERVER_PORT"];
        }
        $this->assign($ary_data);
        $this->assign('domain_name',$domain_name);
        $this->assign('m_id',base64_encode($member['m_id']));
        $this->display();
    }
    
    public function doCreateQcPic() {
        //二维码图片不存在重新生成
         @mkdir('./Public/Uploads/' . CI_SN . '/images');
         $default_port=80;
        $member = session("Members");
        $domain_name=$_SERVER["HTTP_HOST"];
        if($default_port != $_SERVER["SERVER_PORT"]){
            $domain_name.= ':' . $_SERVER["SERVER_PORT"];
        }
        $m_id = base64_encode($member['m_id']);
         $file_name  = '/Public/Uploads/' . CI_SN . '/images/'.date('YmdHis').'.png';
         require_once './Public/Lib/phpqrcode/phpqrcode.php';
         $c = "http://" . $domain_name . '/'.'Wap'.'/'.'User'.'/'.'pageRegister'.'/'.'?'.'m_id='.$m_id;
         QRcode::png($c,FXINC.$file_name);
         //dump($c);
         D('SysConfig')->setConfig('GY_SHOP', 'GY_SHOP_TG_LOGO', $file_name, '推广二维码LOGO');      
         $pic_url = D('QnPic')->picToQn($file_name);        
        return $pic_url;
    }
    
    /**
     * 删除推广二维码
     * @author Zhuwenwei <zhuwenwei@guanyisoft.com>
     * @date 2015-10-13
     */
    public function delTgPic() {
        $bool_res = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_TG_LOGO'))
        ->data(array('sc_value'=>'','sc_update_time'=>date('Y-m-d H:i:s')))
        ->save();
        if($bool_res){
            $this->success('删除推广二维码成功');
        }else{
            $this->error('删除推广二维码失败');
        }
    }
    
    /**
     * 我的返利页面
     *
     * @version 7.4
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-09
     */
    public function payBack() {
        $this->getSubNav(3, 2, 20);
        $page_size = 20;
        $user_name = $this->_get('user_name');
        $page_no = max(1,(int)$this->_get('p','',1));
        $member = session("Members");
        $ary_where=array('m_id'=>$member['m_id']);
        if(!empty($user_name)){
           $ary_where['m_name']=$user_name;
        }
        if($user_name!=$member['m_name'] || empty($user_name)){
            $ary_where['m_recommended']=$member['m_name'];
        }
        $data=D("Orders")->getpayBackCount($ary_where);
        $obj_page = new Page($data['count'], $page_size);
        $page = $obj_page->show();
        $limit['start']=$obj_page->firstRow;
        $limit['end']=$obj_page->listRows;
        $ary_field=array('fx_members.m_name,
                         fx_orders_items.oi_type,
                         fx_orders_items.ml_rebate,
                         fx_orders.o_all_price,
                         fx_orders.promotion,
                         fx_orders.o_create_time'
                    );
        $res=D("Orders")->getpayBack(array('m_id'=>$data['m_id']),$ary_field,$limit);
        $total_price=0;
        
        foreach($res as &$val){
             $ary_promotion_info=unserialize($val['promotion']);
             $ary_promotion_data=array_shift($ary_promotion_info);
             if(isset($ary_promotion_data['pmn_class'])&&!empty($ary_promotion_data['pmn_class'])){//订单只要包含一个促销商品，整个订单为促销
                 $val['oi_type']=1;
                 $val['ml_rebate']=0;//促销订单返点显示为零
             }
             if(isset($ary_promotion_data['products'])&&!empty($ary_promotion_data['products']) &&!isset($ary_promotion_data['pmn_class'])){
                 $promotion_flg=false;
                 foreach($ary_promotion_data['products'] as $promotion_rule){
                     if(!empty($promotion_rule['rule_info']['name'])){
                         $promotion_flg=true;
                     }
                 }
                 if($promotion_flg){//订单只要包含一个促销商品，整个订单为促销
                     $val['oi_type']=1;
                     $val['ml_rebate']=0;//促销订单返点显示为零
                 }else{
                     $val['oi_type']=0;
                 }
             }
            $total_price +=sprintf("%0.3f", $val['o_all_price']*($val['ml_rebate']/ 100));
        }
        $this->assign('datalist',$res);
        $this->assign('price',$total_price);
        $this->assign('user_name',$user_name);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 我的返利页面
     *
     * @version 7.6.1
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-10-09
     */
    public function payBacks() {
        $this->getSubNav(3, 2, 30);
        $ary_post = $this->_request();
        $start = !empty($ary_post['p'])?$ary_post['p']:1;
        $filter = array();
        $filter['m_name'] = $_SESSION['Members']['m_name'];
        if(isset($ary_post['user_name']) && !empty($ary_post['user_name']))
        {
            $filter['user_name'] = $ary_post['user_name'];
        }
        if(isset($ary_post['year']) && !empty($ary_post['year']))
        {
            $filter['year'] = $ary_post['year'];
        }
        if(isset($ary_post['month']) && !empty($ary_post['month']))
        {
            $filter['month'] = $ary_post['month'];
        }
        $filter['year'] = empty($filter['year'])?date('Y'):$filter['year'];
        $filter['month'] = empty($filter['month'])?date('m'):$filter['month'];
        $pbs_list = array();
        $pbs_list = D('Promotings')->getPBSList($filter ,$start,20);//获取数据
        $pageList['filter'] = $filter;
        $pageList['start'] = $start;
        $pageList['limit'] = 20;
        $pageList['count'] = count($pbs_list);
        if(isset($ary_post['type']) && !empty($ary_post['type']))
        {
            $pageList['type'] = $ary_post['type'];
        }
        $obj_page = new Page($pageList['count'], 20);
        $page = $obj_page->show();
        $this->assign('page',$page);
        $this->assign('pageInfo',$pageList);
        $this->assign('pbs_list',$pbs_list);
        $this->assign('filter',$filter);
        $this->display();
    }

    /**
     * [我的分销商]
     * @return [type] [description]
     */
    public function myPromoting(){
        $this->getSubNav(3, 2, 40);
        $relationModel = D('MemberRelation');
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = 20;
        $m_id = $_SESSION['Members']['m_id'];
        $res_ary = $this->getLevelIds($m_id);
        if(!empty($res_ary)){
             $str_mids=$m_id.','.implode(",",$res_ary);
        }else{
            $str_mids=$m_id;
        }
        $where = array(
            'mr_p_id' => array('in',$str_mids)
            );
        $count = $relationModel->where($where)->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_member = $relationModel
                        ->join(C('DB_PREFIX').'members as member on member.m_id='.C('DB_PREFIX').'member_relation.m_id')
                        ->where($where)
                        ->order('mr_path DESC')
                        // ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                        ->select();
        $status = array('未审核','审核中','审核通过','审核未通过','待审核');
        $this->assign('status',$status);
        $this->assign('memberList',$ary_member);
        $this->assign('page', $page); // 赋值分页输出
        $this->display();
    }
    
    public function getLevelIds($mid,&$ary_result=array()){
        $res=D('MemberRelation')->where(array('mr_p_id'=>$mid))->field('m_id')->select();
        if(!empty($res)){
            foreach($res as $key=>$value){
                array_push($ary_result,$value['m_id']);
                $this->getLevelIds($value['m_id'],$ary_result);
            }
        }
        return $ary_result;
    }

    /**
     * 添加分销商
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-12
     */
    public function addMyPromoting(){
        $this->getSubNav(3, 2, 40);
        $m_id = $_SESSION['Members']['m_id'];
        /* 取出会员扩展属性项字段 start*/
        $ary_extend_data = D('MembersFields')->displayFields($m_id,'register');
        foreach($ary_extend_data as $key => $extend){
            if($extend['fields_content']=="m_name"){
                $recommended = $extend['content'];
            }
        }

        $this->assign('ary_extend_data', $ary_extend_data);
        $this->display();
    }

    /**
     * 编辑分销商资料 (Ucenter/MyAction.class.php文件中 pageProfile 方法改造得到)
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-13
     */
    public function editMyPromoting(){
        $this->getSubNav(3,2,40);
        $mid = max(0,(int)$this->_get('mid'));
        if(empty($mid)){
            $this->error('请选择要修改的用户!');
        }
        $member = D("Members");
        $relationModel = D("MemberRelation");
        $where = array(
            'mr_path' => array('like','%'.$_SESSION['Members']['m_id'].',%'),
            'member.m_id' => $mid,
            );
        $me = $relationModel
            ->join(C('DB_PREFIX').'members as member on member.m_id='.C('DB_PREFIX').'member_relation.m_id')
            ->where($where)
            ->order('mr_path DESC')
            ->find();
        if(empty($me)){
            $this->error('不存在该用户!');
        }
        if($me['open_id'] != '' && $me['login_type'] == 1){
            $mem = $member->getInfo('',$me['open_id']);
            $mem['m_name'] = $mem['open_name'];
            if($mem['m_status'] == 0){
                $this->error('您还未设置账户名，为了您的账号安全，请设置账号名称和密码','/Ucenter/My/setThdMembers/');
            }
            // session("Members", $mem);
        }else{
            $mem=D('MembersVerify')->where(array('m_id'=>$me['m_id']))->find();
            if(empty($mem) && !is_array($mem)){
                $mem=D('Members')->where(array('m_id'=>$me['m_id']))->find(); 
            }else{
                $sys_data=D('Members')->where(array('m_id'=>$me['m_id']))->find();
                $mem['ml_id']    =$sys_data['ml_id'];
                $mem['m_balance']=$sys_data['m_balance'];
                $mem['m_card_no']=$sys_data['m_card_no'];
                $mem['m_ali_card_no']=$sys_data['m_ali_card_no'];
            }
            // session("Members", $mem);
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
        $ary_res_meml = D('MembersLevel')->getMembersLevels($mem['ml_id']);     //会员升级提示 
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
        $memberedit = D('SysConfig')->getCfg('MEMBER_EDIT','MEMBER_EDIT_STATUS','1','是否开启会员编辑功能');
        $this->assign('m_balance', $mem['m_balance']);
        $this->assign("region", $city_region_data);
        $this->assign("member", $mem);
        $this->assign("meml",$ary_res_meml);
        $this->assign($memberedit);
        $this->display();
    }

    /**
     * 添加分销商
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-12
     */
    public function doAddMyPromoting(){
        //获取默认配置的会员等级
        $ml = D('MembersLevel')->getSelectedLevel();
        //拼接数组
        $ary_member = array(
            'm_name' => $this->_post('m_name'),
            'm_password' => $this->_post('m_password'),
            'm_password_c' => $this->_post('m_password_1'),
            'm_mobile' => $this->_post('m_mobile'),
            'm_wangwang' => ($this->_post('m_wangwang')) ? $this->_post('m_wangwang') : "",
            'm_qq' => ($this->_post('m_qq')) ? $this->_post('m_qq') : "",
            'm_id_card' => ($this->_post('m_id_card')) ? $this->_post('m_id_card') : "",
            'm_website_url' => ($this->_post('m_website_url')) ? $this->_post('m_website_url') : "",
            'm_create_time' => date('Y-m-d H:i:s'),
            'm_status' => '1',
            'ml_id' =>  $ml,
            // 'm_recommended' => $this->_post('m_recommended')
            );
        $memberInfo = session('Members');
        $ary_member['m_recommended'] = $memberInfo['m_name'];
        if (!empty($_POST['m_real_name']) && isset($_POST['m_real_name'])) {
            $ary_member['m_real_name'] = $this->_post('m_real_name');
        }
        if (!empty($_POST['m_email']) && isset($_POST['m_email'])) {
            $ary_member['m_email'] = $this->_post('m_email');
        }
        if (!empty($_POST['region1']) && isset($_POST['region1'])) {
            $ary_member['cr_id'] = $this->_post('region1');
        }
        if (!empty($_POST['m_address_detail']) && isset($_POST['m_address_detail'])) {
            $ary_member['m_address_detail'] = $this->_post('m_address_detail');
        }
        if (!empty($_POST['m_zipcode']) && isset($_POST['m_zipcode'])) {
            $ary_member['m_zipcode'] = $this->_post('m_zipcode');
        }
        if (!empty($_POST['m_telphone']) && isset($_POST['m_telphone'])) {
            $ary_member['m_telphone'] = $this->_post('m_telphone');
        }
        if (!empty($_POST['m_alipay_name']) && isset($_POST['m_alipay_name'])) {
            $ary_member['m_alipay_name'] = $this->_post('m_alipay_name');
        }
        if (!empty($_POST['m_balance_name']) && isset($_POST['m_balance_name'])) {
            $ary_member['m_balance_name'] = $this->_post('m_balance_name');
        }
        if (!empty($_POST['province']) && isset($_POST['province'])) {
            $area_data=D('AreaJurisdiction')->where(array('cr_id'=>$_POST['province']))->find();
            if(!empty($area_data['s_id'])){
                $ary_member['m_subcompany_id'] = $area_data['s_id'];
            }
        }
        
        $data = D('SysConfig')->getCfgByModule('MEMBER_SET');
        if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
            $ary_member['m_verify'] = '2';
        }
        $ary_result = $this->InsertMember($ary_member);

        $ary_result['data']['m_name'] = $this->_post('m_name');
        if ($ary_result['status'] == '1') {
            if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
                $verify = 2;
            }
            /*把新增加用户属性项信息插入数据库 start*/
            $int_extend_res = D('MembersFieldsInfo')->doAdd($_POST,$ary_result['data']['m_id'],$verify);
            if(!$int_extend_res['result']){
                $this->error('新增分销商失败!');
            }
            //注册成功判断是否开启积分
            $pointCfg = D('PointConfig')->getConfigs();
            if($pointCfg['is_consumed'] == '1' ){
                if($pointCfg['regist_points'] > 0){
                    $res_point = D('PointConfig')->setMemberRewardPoints($pointCfg['regist_points'],$ary_result['data']['m_id'],2);
                    //echo D()->getLastSql();exit();
                    if(!$res_point['result']){
                        $this->error($res_point['message']);
                    }
                }
                if($pointCfg['invites_points']>0 && !empty($ary_member['m_recommended'])){
                    $reMid = D('Members')->where(array('m_name'=>$ary_member['m_recommended']))->getField('m_id');
                    if($reMid){
                        $res_invites_point = D('PointConfig')->setMemberRewardPoints($pointCfg['invites_points'],$reMid,14);
                        if(!$res_invites_point['result']){
                            $this->error($res_invites_point['message']);
                        }
                    }
                }
            }
            /*把新增加用户属性项信息插入数据库 end*/
            //添加成功之后 成为子分销商  
            $ary_member['m_id'] = $ary_result['data']['m_id'];
            D('Members')->addRecommended($ary_member);
            $this->success("添加分销商成功!", "Ucenter/Promoting");
        } else {
            $this->error($ary_result['msg']);
        }
    }

    /**
     * 修改分销商资料
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-13
     */
    public function doEditMyPromoting(){
        $m_id = max(0,(int)$this->_post('mid'));
        if(empty($mid)){
            $this->error('请选择要修改的用户!');
        }
        $member = D("Members");
        $relationModel = D("MemberRelation");
        $where = array(
            'mr_path' => array('like','%'.$_SESSION['Members']['m_id'].',%'),
            'member.m_id' => $mid,
            );
        $me = $relationModel
            ->join(C('DB_PREFIX').'members as member on member.m_id='.C('DB_PREFIX').'member_relation.m_id')
            ->where($where)
            ->order('mr_path DESC')
            ->find();
        if(empty($me)){
            $this->error('不存在该用户!');
        }
        $ary_cfg = D("SysConfig")->getConfigs("MEMBER_SET", "MEMBER_STATUS");
        $ary_params = array(
            'm_id'             => $m_id,
            'm_name'           => $this->_post('m_name'),
            'm_id_card'        => $this->_post('m_id_card'),
            'm_real_name'      => $this->_post('m_real_name'),
            'm_birthday'       => $this->_post('m_birthday'),
            'm_sex'            => $this->_post('sex', 'htmlspecialchars', 2),
            'm_address_detail' => $this->_post('m_address_detail'),
            'm_email'          => $this->_post('m_email'),
            'm_password'       => $sys_data['m_password'],
            'm_status'         => $sys_data['m_status'],
            'm_subcompany_id'  => $sys_data['m_subcompany_id'],
            'm_website_url'    => $this->_post('m_website_url'),
            'm_alipay_name'    => $this->_post('m_alipay_name'),
            'm_balance_name'   => $this->_post('m_balance_name'),
            'm_zipcode'        => $this->_post('m_zipcode'),
            'm_mobile'         => $ary_post_data['m_mobile'],
            'm_telphone'       => $this->_post('m_telphone'),
            'm_qq'             => $this->_post('m_qq'),
            'm_verify'         => 1 == $ary_cfg['MEMBER_STATUS']['sc_value'] ? 2 : 4,
            'm_wangwang'       => $this->_post('m_wangwang'),
            'm_update_time'    => date('Y-m-d H:i:s'),
            'm_recommended'    => $this->_post('m_recommended')
        );
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
        
        $ary_result = D('Members')->where(array('m_id'=>$m_id))->data($ary_params)->save();         
        $ary_res=D('MembersFieldsInfo')->doAdd($this->_post(), $m_id,2);
        if ($ary_result || $ary_res['result']) {
            $this->success('修改成功', U('Ucenter/Promoting/myPromoting'));
        } else {
            $this->error("修改失败，请重试...");
        }
    }

    /**
     * [重置密码]
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-13
     */
    public function ResetCode(){
        $mid = max(0,(int)$this->_post('mid'));
        if(empty($mid)){
            $this->error('请选择要修改的用户!');
        }
        $member = D("Members");
        $relationModel = D("MemberRelation");
        $where = array(
            'mr_path' => array('like','%'.$_SESSION['Members']['m_id'].',%'),
            'member.m_id' => $mid,
            );
        $me = $relationModel
            ->join(C('DB_PREFIX').'members as member on member.m_id='.C('DB_PREFIX').'member_relation.m_id')
            ->where($where)
            ->order('mr_path DESC')
            ->find();
        if(empty($me)){
            $this->error('不存在该用户!');
        }
        $data = array(
            'm_password' => md5('123456')
            );
        $result = $member->where(array('m_id' => $mid))->data($data)->save();
        if($result !== false){
            $this->success('重置成功');
        }else{
            $this->error('重置失败');
        }
    }
    /**
     * [添加会员]
     * @return [type] [description]
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-13
     */
    private function InsertMember($ary_members){
        $memberModel = D('Members');
        $return = array('status'=>1,'errCode'=>'1000','msg'=>'添加成功！','data'=>'');
        if(isset($ary_members['m_password_c']) && $ary_members['m_password_c'] != $ary_members['m_password']){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'2次密码输入不一致','data'=>'');
            return $return;
        }
        if(empty($ary_members['m_name']) || empty($ary_members['m_password'])){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'用户名/密码不能为空','data'=>'');
            return $return;
        }
        if(isset($ary_members['m_real_name']) && empty($ary_members['m_real_name'])){
             $return = array('status'=>0,'errCode'=>'1004','msg'=>'真实姓名不能为空','data'=>'');
            return $return;
        }
        //检测用户名是否存在  By Joe
        if($memberModel->where(array('m_name'=>$ary_members['m_name']))->find()){
            $return = array('status'=>0,'errorCode'=>'1007','msg'=>'用户名已存在！','data'=>'');
            return $return;
        }
        //------------end
        
        //注册奖励积分
        $obj_point = D('PointConfig');
        $int_point = $obj_point->getConfigs('regist_points');
        if(null !== $int_point && is_numeric($int_point) && $int_point>0) {
            $memberModel->total_point = intval($int_point);
        }
        
        $memberModel->m_name = $ary_members['m_name'];
        $memberModel->m_password = md5($ary_members['m_password']);
        if(!empty($ary_members['m_real_name']) && isset($ary_members['m_real_name'])){
            $memberModel->m_real_name = $ary_members['m_real_name'];
        }
        if(!empty($ary_members['m_mobile']) && isset($ary_members['m_mobile'])){
            $memberModel->m_mobile = $ary_members['m_mobile'];
        }
        if(!empty($ary_members['m_email']) && isset($ary_members['m_email'])){
            $memberModel->m_email = $ary_members['m_email'];
        }
        if(!empty($ary_members['cr_id']) && isset($ary_members['cr_id'])){
            $memberModel->cr_id = $ary_members['cr_id'];
        }
        if(!empty($ary_members['m_address_detail']) && isset($ary_members['m_address_detail'])){
            $memberModel->m_address_detail = $ary_members['m_address_detail'];
        }
        if(!empty($ary_members['m_zipcode']) && isset($ary_members['m_zipcode'])){
            $memberModel->m_zipcode = $ary_members['m_zipcode'];
        }
        if(!empty($ary_members['m_telphone']) && isset($ary_members['m_telphone'])){
            $memberModel->m_telphone = $ary_members['m_telphone'];
        }
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $memberModel->m_alipay_name = $ary_members['m_alipay_name'];
        }
        if(!empty($ary_members['m_balance_name']) && isset($ary_members['m_balance_name'])){
            $memberModel->m_balance_name = $ary_members['m_balance_name'];
        }
        if(!empty($ary_members['m_subcompany_id']) && isset($ary_members['m_subcompany_id'])){
            $memberModel->m_subcompany_id = $ary_members['m_subcompany_id'];
        }
        if(!empty($ary_members['m_wangwang']) && isset($ary_members['m_wangwang'])){
            $memberModel->m_wangwang = $ary_members['m_wangwang'];
        }
        if(!empty($ary_members['m_qq']) && isset($ary_members['m_qq'])){
            $memberModel->m_qq = $ary_members['m_qq'];
        }
        if(!empty($ary_members['m_website_url']) && isset($ary_members['m_website_url'])){
            $memberModel->m_website_url = $ary_members['m_website_url'];
        }
        if(!empty($ary_members['m_create_time']) && isset($ary_members['m_create_time'])){
            $memberModel->m_create_time = $ary_members['m_create_time'];
        }
        if(!empty($ary_members['m_recommended']) && isset($ary_members['m_recommended'])){
            $memberModel->m_recommended = $ary_members['m_recommended'];
        }
        if(!empty($ary_members['m_type']) && isset($ary_members['m_type'])){
            $memberModel->m_type = $ary_members['m_type'];
        }     
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $memberModel->m_alipay_name = $ary_members['m_alipay_name'];
        }
        if(!empty($ary_members['ml_id']) && isset($ary_members['ml_id'])){
            $memberModel->ml_id = $ary_members['ml_id'];
        }
        if(!empty($ary_members['m_verify']) && isset($ary_members['m_verify'])){
            $memberModel->m_verify = $ary_members['m_verify'];
        }
        if(!empty($ary_members['m_id_card']) && isset($ary_members['m_id_card'])){
            $memberModel->m_id_card = $ary_members['m_id_card'];
        }       
        $memberModel->m_status = intval($ary_members['m_status']);
        $member_id = $memberModel->add();
        if($member_id>0 && $int_point>0) {
                //插入积分日志表
                $ary_temp = array(
                                'type'=>2,
                                'consume_point'=> 0,
                                'reward_point'=> intval($int_point),
                                );
                D('PointLog')->addPointLog($ary_temp,$member_id);
        }
        
        if($member_id>0) {
             //添加默认授权线
            $Authorize = D('AuthorizeLine');
            $ary_authorize = $Authorize->where(array('al_default'=>1,'al_valid'=>1))->find();
            
            if(!empty($ary_authorize) && isset($ary_authorize['al_id']) && !empty($ary_authorize['al_id'])) {
                   $obj_related_authorize = D('RelatedAuthorizeMember');
                   $ary_insert = array('al_id'=>(int)$ary_authorize['al_id'],'m_id'=>(int)$member_id);
                   $obj_related_authorize->data($ary_insert)->add();
            }
        }
        
        $return['data'] = array('m_id'=>$member_id);
        return $return;
    }

    public function partnerIntro(){//合伙人介绍
        //echo 'here';
        //exit();
        $this->display();
    }

    public function partnerReg(){//合伙人注册
        //取出省级数据
        $provice = M('CityRegion')->field('cr_id,cr_name')->where(array('cr_status'=>1,'cr_type'=>2))->select();
        $this->assign('citys',$provice);
          //获取一级城市
        $city = D("CityRegion");
        $ary_city = $city->getCurrLvItem(1);
        $this->assign("citys", $ary_city);
        $this->display();
    }

    public function dopartnerReg(){
        $data = array();
        if ($_POST) {
            //接收表单数据：
                 $me = session("Members");
                 $data['m_id'] = $me['m_id'];
                 $data['p_name']=$this->_post('p_name');
                 $data['p_detail']=$this->_post('p_detail');
                 $data['p_post_code']=$this->_post('p_post_code');
                 $data['p_phone']=$this->_post('p_phone');
                 $data['p_id_card']=$this->_post('p_id_card');
                 $data['p_open_name']=$this->_post('p_open_name');
                 $data['p_bank_number']=$this->_post('p_bank_number');
                 $data['p_open_bank']=$this->_post('p_open_bank');
                 $data['province']=$this->_post('province');
                 $data['city']=$this->_post('city');
                 $data['region']=$this->_post('region');
                 $data['bank_province']=$this->_post('bank_province');
                 $data['bank_city']=$this->_post('bank_city');
                 $data['p_status']=0;
                 $data['p_create_time']=date('Y-m-d H:i:s');
                 $data['p_update_time']=date('Y-m-d H:i:s');  

                 $tmp_files=$_FILES['p_id_card_photo'];
                 if(isset($tmp_files) && !empty($tmp_files['name']) && !empty($tmp_files['tmp_name'])){
                        $path = './Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/';
                        if(!file_exists($path)){
                            @mkdir('./Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/', 0777, true);
                        }
                        $upload = new UploadFile();// 实例化上传类
                        $upload->maxSize  = 3145728 ;// 设置附件上传大小
                        $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
                        //$upload->savePath =  $path;// 设置附件上传目录
                        if(!$upload->upload($path,$upfiles)) {// 上传错误提示错误信息
                            $this->error($message['msg']);
                        }else{// 上传成功 获取上传文件信息
                            $info =  $upload->getUploadFileInfo();
                            $tmp_files_url = './Public/Uploads/'.CI_SN.'/home/' .date('Ymd').'/'. $info[0]['savename'];
                            $data['p_id_card_photo'] = D('ViewGoods')->ReplaceItemPicReal($tmp_files_url);
                        }
                }
                 // $Promotings = D("Promotings");
                 // $Promotings->partnerSave($data);

                //file_put_contents('/wjw/debug.txt', var_export($data,true));
               
                 $result = D('Promotings')->partnerSave($data);

                 if ($result) {
                    $this->success('合法人注册成功');
                } else {
                    $this->error("合伙人注册信息失败");
                }
                //  print_r($data);

        }
    }

}