<?php
/**
 * 分销商用户模型
 * @package Model
 * @version 7.0
 * @author Joe
 * @date 2012-12-12
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-22
     */
    private $table;
    
    public function __construct() {
        parent::__construct();
        $this->table = M('members',C('DB_PREFIX'),'DB_CUSTOM');
    }
    /**
     * 获取会员信息
     *
     * @author Joe
     * @date 2012-12-11
     */
    public function getInfo($m_name,$open_id='',$unionid=''){
        // var_dump($ary_member);
        $ary_return = array();
        if($unionid != ''){
            $ary_member = $this->where("unionid='$unionid'")->find();
        }
        elseif($open_id != ''){
            $ary_member = $this->where("open_id='$open_id'")->find();
        }else{
            $ary_member = $this->where("m_name='$m_name'")->find();
        }
        unset($ary_member['m_password']);
        $ary_member_level =array('ml_id'=>0,'ml_name'=>'','ml_discount'=>'');
        if(isset($ary_member['ml_id']) && !empty($ary_member['ml_id'])) {
                $ary_member_level = D('MembersLevel')->where(array('ml_id'=>$ary_member['ml_id']))->find();
        }
        $ary_member['member_level'] = array('ml_id'=>$ary_member_level['ml_id'],'ml_name'=>$ary_member_level['ml_name'],'ml_discount'=>$ary_member_level['ml_discount']);
        if(empty($ary_member['member_level']['ml_id'])){
        	$ary_member['member_level']['ml_name'] = '暂无';
        	$ary_member['member_level']['ml_discount'] = '10';
        }
        
        return $ary_member;
    }
    

    /**
     * 执行用户登录
     *
     * @param array $ary_members 注册用户基本信息
     * @param string $verify 验证码
     * @since 1.0
     * @return array
     */
     public function doRegister($ary_members,$verify){
        $return = array('status'=>1,'errCode'=>'1000','msg'=>'登录成功！','data'=>'');
        if($_COOKIE['verify'] != md5($verify) && $ary_members['m_mobile_code'] ==''){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'验证码有误！','data'=>'');
            //$return = array('status'=>0,'errCode'=>'1004','msg'=>$verify,'data'=>'');
            return $return;
        }
		if(isset($ary_members['m_mobile_code'])){
			unset($ary_members['m_mobile_code']);
		}
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
        if($this->where(array('m_name'=>$ary_members['m_name']))->find()){
            $return = array('status'=>0,'errorCode'=>'1007','msg'=>'用户名已存在！','data'=>'');
            return $return;
        }
        //------------end
        
        //注册奖励积分
        $obj_point = D('PointConfig');
        $int_point = $obj_point->getConfigs('regist_points');
        if(null !== $int_point && is_numeric($int_point) && $int_point>0) {
            $this->total_point = intval($int_point);
        }
        
        $this->m_name = $ary_members['m_name'];
        $this->m_password = md5($ary_members['m_password']);
        if(!empty($ary_members['m_real_name']) && isset($ary_members['m_real_name'])){
            $this->m_real_name = $ary_members['m_real_name'];
        }
        if(!empty($ary_members['m_mobile']) && isset($ary_members['m_mobile'])){
            $this->m_mobile = encrypt($ary_members['m_mobile']);
        }
        if(!empty($ary_members['m_email']) && isset($ary_members['m_email'])){
            $this->m_email = $ary_members['m_email'];
        }
        if(!empty($ary_members['cr_id']) && isset($ary_members['cr_id'])){
            $this->cr_id = $ary_members['cr_id'];
        }
        if(!empty($ary_members['m_address_detail']) && isset($ary_members['m_address_detail'])){
            $this->m_address_detail = $ary_members['m_address_detail'];
        }
        if(!empty($ary_members['m_zipcode']) && isset($ary_members['m_zipcode'])){
            $this->m_zipcode = $ary_members['m_zipcode'];
        }
        if(!empty($ary_members['m_telphone']) && isset($ary_members['m_telphone'])){
            $this->m_telphone = encrypt($ary_members['m_telphone']);
        }
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $this->m_alipay_name = $ary_members['m_alipay_name'];
        }
        if(!empty($ary_members['m_balance_name']) && isset($ary_members['m_balance_name'])){
            $this->m_balance_name = $ary_members['m_balance_name'];
        }
        if(!empty($ary_members['m_subcompany_id']) && isset($ary_members['m_subcompany_id'])){
            $this->m_subcompany_id = $ary_members['m_subcompany_id'];
        }
        if(!empty($ary_members['m_wangwang']) && isset($ary_members['m_wangwang'])){
            $this->m_wangwang = $ary_members['m_wangwang'];
        }
        if(!empty($ary_members['m_qq']) && isset($ary_members['m_qq'])){
            $this->m_qq = $ary_members['m_qq'];
        }
        if(!empty($ary_members['m_website_url']) && isset($ary_members['m_website_url'])){
            $this->m_website_url = $ary_members['m_website_url'];
        }
        if(!empty($ary_members['m_create_time']) && isset($ary_members['m_create_time'])){
            $this->m_create_time = $ary_members['m_create_time'];
        }
        if(!empty($ary_members['m_recommended']) && isset($ary_members['m_recommended'])){
            $this->m_recommended = $ary_members['m_recommended'];
        }
		$memberType = D('SysConfig')->getCfg('MEMBER_SET','MEMBER_TYPE','0','会员默认类型');
        $ary_members['m_type'] = $memberType['MEMBER_TYPE']['sc_value'] == '1' ? '1' : '0';
        if(!empty($ary_members['m_type']) && isset($ary_members['m_type'])){
            $this->m_type = $ary_members['m_type'];
        }     
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $this->m_alipay_name = $ary_members['m_alipay_name'];
        }
        if(!empty($ary_members['ml_id']) && isset($ary_members['ml_id'])){
            $this->ml_id = $ary_members['ml_id'];
        }
        if(!empty($ary_members['m_verify']) && isset($ary_members['m_verify'])){
            $this->m_verify = $ary_members['m_verify'];
        }
        if(!empty($ary_members['m_id_card']) && isset($ary_members['m_id_card'])){
            $this->m_id_card = $ary_members['m_id_card'];
        }
        //店铺来源	
        if($_SESSION['SHOPCODE']!=''){
			$ary_members['shop_code'] = $_SESSION['SHOPCODE'];
			$this->shop_code = $_SESSION['SHOPCODE'];
			unset($_SESSION["SHOPCODE"]);
		}		
        $this->m_status = intval($ary_members['m_status']);
        $member_id = $this->add();
//        echo "<pre>";print_r($this->getLastSql());exit;
        /*if($member_id>0 && $int_point>0) {
                //插入积分日志表
                $ary_temp = array(
                                'type'=>2,
                                'consume_point'=> 0,
                                'reward_point'=> intval($int_point),
                                );
                D('PointLog')->addPointLog($ary_temp,$member_id);
        }*/
        
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
		//会员推送到线下系统
		if($member_id >0 && C('AUTO_SNYC_MEMBER')==1){
			$ary_members['method'] ='AddMember';
			if(!isset($ary_members['id'])){
				$ary_members['m_id'] = $member_id;
			}
			$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
			$host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
			request_by_fsockopen($host_url.'/Script/Batch/snyc_send',$ary_members);			 
		}
        
        $return['data'] = array('m_id'=>$member_id);
        return $return;

     }
	 


    /**
     * 验证用户名和密码
     *
     * @param string $m_name 用户名
     * @param string $m_password 用户密码
     * @param type $verify 验证码
     * @return array Description
     * @author Joe  <qianyijun@guanyisoft.com>
     * @date 2012-12-12
     */
    public function doLogin($m_name,$m_password,$verify){
        $return = array('status'=>1,'errCode'=>'1000','msg'=>'登录成功！','data'=>'');

        if( empty($m_name) || empty($m_password) || empty($verify) ){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'该项不能为空','data'=>'');
            return $return;
        }
        
        //if($_COOKIE['verify'] != md5($verify)){
		if($_COOKIE['verify'] != md5($verify)){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'验证码有误！','data'=>'');
            return $return;
        }
        $password = md5($m_password);
        $ary_member = $this->checkNamePassword($m_name,$password);


        if($ary_member['m_verify'] == 0){
            $return = array('status'=>0,'errCode'=>'1005','msg'=>'用户未激活，请联系管理员！','data'=>'');
        }
        //用户被停用不能登录
        if($ary_member['m_status'] == 0){
            $return = array('status'=>0,'errCode'=>'1005','msg'=>'用户未激活，请联系管理员！','data'=>'');
        }
        if(!$ary_member){
            $return = array('status'=>0,'errCode'=>'1003','msg'=>'用户名或密码错误！','data'=>'');
        }        
        if($ary_member['login_forbid']>time()){
            $return = array('status'=>0,'errCode'=>'1003','msg'=>'您已多次密码错误，请2小时后再试！','data'=>'');
            //return $return;
        }
        return $return;
    }
    
    /**
     * Home验证用户名和密码
     *
     * @param string $m_name 用户名
     * @param string $m_password 用户密码
     * @return array Description
     * @author wangguibin  <wangguibin@guanyisoft.com>
     * @date 2013-04-11
     */
    public function doLoginApi($m_name,$m_password,$verify){
        $return = array('status'=>1,'errCode'=>'1000','msg'=>'登录成功！','data'=>'');

        if( empty($m_name) || empty($m_password) ){
            $return = array('status'=>0,'errCode'=>'1004','msg'=>'该项不能为空','data'=>'');
            return $return;
        }
        // if($verify){
        // 	$VERIFICATION_SET = D('SysConfig')->getCfgByModule('VERIFICATION_SET');
        // 	if($VERIFICATION_SET['VERIFICATION_STATUS'] == 1){
        // 		if($_COOKIE['verify'] != md5($verify)){
        // 			$return = array('status'=>0,'errCode'=>'1004','msg'=>'验证码有误！','data'=>'');
        // 			return $return;
        // 		}
        // 	}
        // }
        $password = md5($m_password);
		/* $verify_data=D('MembersVerify')->where(array('m_name'=>$m_name,'m_password'=>$password))->find();
        if(empty($verify_data)){
            $ary_member = $this->checkNamePassword($m_name,$password);
        }else{
            $ary_member = $verify_data;
        } */
        $ary_member = $this->checkNamePassword($m_name,$password);
        if(!$ary_member){
            $return = array('status'=>0,'errCode'=>'1003','msg'=>'用户名或密码错误！','data'=>'');
        }
        if(!empty($ary_member) && $ary_member['m_status'] == '0'){
            $return = array('status'=>0,'errCode'=>'1005','msg'=>'帐号未激活，请联系管理员！','data'=>'');
        }else if(!empty($ary_member) && $ary_member['m_verify'] == 4){
            $return = array('status'=>0,'errCode'=>'1005','msg'=>'帐号待审核，请联系管理员！','data'=>'');
        }
        if(!empty($ary_member) && $ary_member['m_verify'] == '0'){
            $return = array('status'=>0,'errCode'=>'1006','msg'=>'管理没有审核，请稍后登入！','data'=>'');
        }
        if($ary_member['login_forbid']>time()){
            $return = array('status'=>0,'errCode'=>'1003','msg'=>'您已多次密码错误，请2小时后再试！','data'=>'');
            //return $return;
        }
        return $return;
    }
    /**
     * 验证该用户是否存在
     *
     * @param string $m_name
     * @return bool
     * @author Joe
     * @date 2012-12-11
     */
    public function checkName($m_name){
        $reslut = $this->where(array('m_name'=>$m_name))->find();
        if($reslut){
            return true;
        }else{
            return false;
        }
    }

     /**
     * 验证该用户是否存在
     *
     * @param string $m_name
     * @return bool
     * @author Joe
     * @date 2012-12-11
     */
    public function checkEmail($m_email){
        $reslut = $this->where(array('m_email'=>$m_email))->find();
        if($reslut){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 验证手机号是否存在
     *
     * @param string $m_name
     * @return bool
     * @author Joe
     * @date 2012-12-11
     */
    public function checkMobile($m_mobile){
		$reslut = $this->where(array('m_mobile'=>$m_mobile))->find();;
        if($reslut){
            return true;
        }else{
            return false;
        }
    }
	
    /**
     * 根据用户名和邮箱判断用户是否存在
     * @author  zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-12
     * @param string $m_name 用户名
     * @param string $m_email 用户邮箱
     * @return mix 成功返回用户数据，失败返回false
     */
    public function checkNameEmail($m_name,$m_email){
        $reslut = $this->where(array('m_email'=>$m_email,'m_name'=>$m_name))->find();
        return $reslut;
    }

    /**
     * 根据用户名和密码判断用户
     * @author zuo <zuojianghua@gmail.com>
     * @date 2012-12-12
     * @param string $m_name 用户名
     * @param string $m_password 加密后的密码
     * @return mix 成功返回用户数据，失败返回false
     */
    public function checkNamePassword($m_name,$m_password){
        $reslut = $this->where(array('m_password'=>$m_password,'m_name'=>$m_name))->find();
        //echo $this->getLastSql();
        $now = time();
        $Model = M('',C('DB_PREFIX'),'DB_CUSTOM');
        if(!$reslut){
            $reslut2 = $this->where(array('m_name'=>$m_name))->find();
            if($reslut2){//用户名存在
                
                
                $two_hour = $now+7200;
                if($reslut2['login_forbid']<$now){
                    if($reslut2['login_error']>=2){
                        $sql = 'update fx_members set login_error=login_error+1,login_forbid='.$two_hour.' where m_id='.$reslut2['m_id'];
                        $Model->query($sql);
                        $reslut['login_forbid'] = $two_hour;
                    }
                    else{
                        $sql = 'update fx_members set login_error=login_error+1 where m_id='.$reslut2['m_id'];
                        $Model->query($sql);
                    }
                }
                else{
                    $sql = 'update fx_members set login_forbid='.$two_hour.' where m_id='.$reslut2['m_id'];
                    $Model->query($sql);
                    $reslut['login_forbid'] = $two_hour;
                    //return $reslut2;
                }
            }
        }
        else{
            if($reslut['login_forbid']<$now){
                $sql = 'update fx_members set login_error=0,login_forbid=0 where m_id='.$reslut['m_id'];
                $Model->query($sql);
            }
        }
        return $reslut;
    }

    /**
     * 根据会员ID获取到会员的等级折扣率
     *
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     * @param int $m_id
     * @return float 存在会员等级折扣则返回，会员不存在或会员等级折扣未设置则返回1.000
     */
    public function getMemberDiscount($m_id,$is_cache=0){
		//一秒钟缓存
        //获取默认的会员等级折扣
		if($is_cache == 1){
			$ary_default_level = D('Gyfx')->selectOneCache('members_level',$ary_field=null, array('ml_default'=>1));
		}else{
			$ary_default_level = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_default'=>1))->find();
		}
        $int_default_discount = ($ary_default_level['ml_discount'])?$ary_default_level['ml_discount']:100;

        //获取当前会员的会员等级折扣
		if($is_cache == 1){
			$obj_query = $this->field(array('m_id','m_name','fx_members.ml_id','ml_discount'))->join("fx_members_level on fx_members.ml_id = fx_members_level.ml_id")->where(array('m_id'=>$m_id));	
			$ary_members_info = D('Gyfx')->queryCache($obj_query,'find',60);
		}else{
			$ary_members_info = $this->field(array('m_id','m_name','fx_members.ml_id','ml_discount'))->join("fx_members_level on fx_members.ml_id = fx_members_level.ml_id")->where(array('m_id'=>$m_id))->find();			
		}		
        return (($ary_members_info['ml_discount']) ? $ary_members_info['ml_discount'] : $int_default_discount) / 100;

    }
    
    /**
     * 校验会员的密码是否正确
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2012-12-26
     * @param int $m_id (string)$pwd
     * @return array 根据会员ID和密码校验用户密码是否正确，如果正确返回数组，否则返回空
     */
    public function doChange($m_id,$pwd,$m_name){
        $where['m_id'] = $m_id;
        $data['m_password'] = md5($pwd);
        $ary_result = $this->where($where)->save($data);
		if(!empty($m_name)){
			$ary_where['m_name'] = $m_name;
			M('members_verify',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->save($data);	
		}	
        return $ary_result;
    }

    /**
     * 处理会员修改个人资料信息
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2012-12-27
     * @param int $m_id (array)$params
     * @return flaot 如果更新成功则为true,失败则返回false
     */
    public function doEdit($params,$m_id){
        $where['m_id'] = $m_id;
        $data = array(
            'm_real_name'  =>$params['m_real_name'],
            'm_sex'  =>$params['m_sex'],
            'cr_id'  =>$params['region'],
            'm_birthday'  =>$params['m_birthday'],
            'm_address_detail'  =>$params['m_address_detail'],
            'm_zipcode'  =>$params['m_zipcode'],
            'm_mobile'  =>$params['m_mobile'],
            'm_wangwang'  =>$params['m_wangwang'],
            'm_qq'  =>$params['m_qq'],
            'm_update_time'  =>date("Y-m-d H:i:s"),
            'm_telphone'  =>$params['m_telphone'],
            'm_verify'=>$params['m_verify'],
        );
        $ary_result = $this->where($where)->save($data);
        if($ary_result){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 会员信息
     * @author listen 
     * @param array $ary_where 查询订单where条件
     * @param  $ary_field = array('字段') 查询的字段 默认等于空是全部 
     * @date 2013-01-15
     * @return array()
     */
    public function membersInfo($page_no = '1',$page_size = '10',$ary_where = array(),$ary_field = '',$ary_orders=array()){
		$ary_members = M('view_members',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->limit($page_no,$page_size)->order($ary_orders)->select();
		//echo M('view_members',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		return $ary_members;
    } 
    

    /**
     * 根据用户id更新账户金额
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-08
     * @param int $id 充值id
     */
    public function UpdateBalance($mid,$money) {
        $data['m_balance']=$money;
        $result = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$mid))->save($data);
        return $result;
    }
    
    /**
     * 获取会员所属信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-05-23
     */
    public function getByNameLevel($ary_where = array(), $ary_field = ''){
        return $data =  $this->table->field($ary_field)->where($ary_where)
                        ->join('left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id')->find();
    }
    /**
     * 获取会员平台统计
     * @author listen
     * @date 2013-06-04
     */
    public function countPlatformMembers(){
        $ary_where = array();
        //找出所有的平台
        $ary_platform = D('SourcePlatform')->where(array('sp_stauts'=>1))->select();
        if(!empty($ary_platform)){
           foreach($ary_platform as $k=>$v){
               
           } 
        }
        //找出每个平台分销商占有的比例
        $this->where($ary_where)->select();
    }
    
    /**
     * 获得用户与存款
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-06-14
     * @param array $ary_where 
     */
    public function GetBalance($ary_where = array(), $ary_field = '') {
        $result = M('members',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->find();
        return $result;
    }
	
	/**
	 * 会员列表高级搜索
	 *
	 * 传入一个数组（后台高级搜索的条件数组）
	 *
	 * 返回所有符合条件的会员ID
	 *
	 * @author Mithern<sunguangxu@guanyisoft.com>
	 * @date 2013-07-30
	 * @version 1.0
	 */
	public function adminMemberAdvanceSearch($array_search_fields = array()){
		$array_member_cond = array();
		//如果指定了根据会员名称进行搜索
		if(isset($array_search_fields["m_name"]) && "" != $array_search_fields["m_name"]){
			$array_member_cond["m_name"] = array("like","%" . $array_search_fields["m_name"] . "%");
		}
		
		//如果指定了会员真实姓名进行搜索
		if(isset($array_search_fields["m_real_name"]) && "" != $array_search_fields["m_real_name"]){
			$array_member_cond["m_real_name"] = array("like","%" . $array_search_fields["m_real_name"] . "%");
		}
		
		//如果指定了会员审核状态进行搜索
		if(isset($array_search_fields["m_verify"]) && !empty($array_search_fields["m_verify"])){
			$array_member_cond["m_verify"] = array("IN",$array_search_fields["m_verify"]);
		}
		
		//如果指定了会员审核状态已冻结搜索
		if(isset($array_search_fields["m_status"]) && !empty($array_search_fields["m_status"])){
			$array_member_cond["m_status"] = array("eq",'0');
		}
		//如果指定了会员等级ID进行搜索
		if(isset($array_search_fields["ml_id"]) && !empty($array_search_fields["ml_id"])){
			$array_member_cond["ml_id"] = array("IN",$array_search_fields["ml_id"]);
		}
		
		//根据会员结余款余额进行搜索
		if(isset($array_search_fields["m_balance_min"]) && is_numeric($array_search_fields["m_balance_min"]) && isset($array_search_fields["m_balance_max"]) && is_numeric($array_search_fields["m_balance_max"])){
			$array_member_cond["m_balance"] = array("BETWEEN",array($array_search_fields["m_balance_min"],$array_search_fields["m_balance_max"]));
		}else if(isset($array_search_fields["m_balance_min"]) && is_numeric($array_search_fields["m_balance_min"]) && (!isset($array_search_fields["m_balance_max"]) || !is_numeric($array_search_fields["m_balance_max"]))){
			$array_member_cond["m_balance"] = array("egt",$array_search_fields["m_balance_min"]);
		}else if((!isset($array_search_fields["m_balance_min"]) || !is_numeric($array_search_fields["m_balance_min"])) && isset($array_search_fields["m_balance_max"]) && is_numeric($array_search_fields["m_balance_max"])){
			$array_member_cond["m_balance"] = array("elt",$array_search_fields["m_balance_max"]);
		}
		
		//根据会员积分余额进行搜索
		if(isset($array_search_fields["total_point_min"]) && is_numeric($array_search_fields["total_point_min"]) && isset($array_search_fields["total_point_max"]) && is_numeric($array_search_fields["total_point_max"])){
			$array_member_cond["total_point"] = array("BETWEEN",array($array_search_fields["total_point_min"],$array_search_fields["total_point_max"]));
		}else if(isset($array_search_fields["m_balance_min"]) && is_numeric($array_search_fields["total_point_min"]) && (!isset($array_search_fields["total_point_max"]) || !is_numeric($array_search_fields["total_point_max"]))){
			$array_member_cond["total_point"] = array("egt",$array_search_fields["total_point_min"]);
		}else if((!isset($array_search_fields["total_point_min"]) || !is_numeric($array_search_fields["total_point_min"])) && isset($array_search_fields["total_point_max"]) && is_numeric($array_search_fields["total_point_max"])){
			$array_member_cond["total_point"] = array("elt",$array_search_fields["total_point_max"]);
		}
		
		//根据会员性别进行搜索
		if(isset($array_search_fields["m_sex"]) && !empty($array_search_fields["m_sex"])){
			$array_member_cond["m_sex"] = array("IN",$array_search_fields["m_sex"]);
		}
		
		//根据会员注册时间进行搜索
		if(isset($array_search_fields["m_create_time_start"]) && !empty($array_search_fields["m_create_time_start"]) && isset($array_search_fields["m_create_time_end"]) && !empty($array_search_fields["m_create_time_end"])){
			$array_member_cond["m_create_time"] = array("BETWEEN",array($array_search_fields["m_create_time_start"],$array_search_fields["m_create_time_end"]));
		}else if(isset($array_search_fields["m_create_time_start"]) && !empty($array_search_fields["m_create_time_start"]) && (!isset($array_search_fields["m_create_time_end"]) || empty($array_search_fields["m_create_time_end"]))){
			$array_member_cond["m_create_time"] = array("egt",$array_search_fields["m_create_time_start"]);
		}else if(isset($array_search_fields["m_create_time_end"]) && !empty($array_search_fields["m_create_time_end"]) && (!isset($array_search_fields["m_create_time_start"]) || empty($array_search_fields["m_create_time_start"]))){
			$array_member_cond["m_create_time"] = array("elt",$array_search_fields["m_create_time_end"]);
		}
		
		$array_related_platform_mid = array();
		//根据会员来源平台进行搜索
		if(isset($array_search_fields["sp_id"]) && !empty($array_search_fields["sp_id"])){
			$array_related_platform_mid = D("RelatedMembersSourcePlatform")->where(array("IN",$array_search_fields["sp_id"]))->getField("m_id",true);
			$array_member_cond["m_id"] = array("IN",$array_related_platform_mid);
		}
		
		//根据会员所属会员组进行搜索
		$array_related_groups_mid = array();
		if(isset($array_search_fields["mg_id"]) && !empty($array_search_fields["mg_id"])){
			$array_related_groups_mid = D("RelatedMembersGroup")->where(array("IN",$array_search_fields["mg_id"]))->getField("m_id",true);
			$array_member_cond["m_id"] = array("IN",$array_related_groups_mid);
		}
		
		//如果即指定了会员组，又指定了会员所属平台，则组合条件取并集
		if(!empty($array_related_platform_mid) && !empty($array_related_groups_mid)){
			$array_member_cond["m_id"] = array("IN",array_merge($array_related_platform_mid,$array_related_groups_mid));
		}
		//返回查询条件
		return $array_member_cond;
	}
    /**
     * 根据用户id更新会员等级
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-09-30
     * @param int $id 充值id
     */
    public function UpdateUserLevel($mid,$ml_id) {
        $data['ml_id']=$ml_id;
        $result = $this->table->where(array('m_id'=>$mid))->save($data);
        return $result;
    }
    
    /**
     * 获得分销商推荐人
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-09
     * @param ary $ary_where
     */
    public function getRecommended($ary_where = array(), $ary_field = '') {
        $result = $this->table->field($ary_field)->where($ary_where)->select();
        return $result;
    }
	
    /**
     * 如果有推荐人，成为子分销商
     * @author  Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-09-17
     * @param ary $ary_where
     */
    public function addRecommended($ary_member) {
		if (!empty($ary_member['m_recommended'])) {
			$m_path = '';
			$int_m_id = $ary_member['m_id'];
			$return_m_p_id = D('Members')->where(array('m_name'=>$ary_member['m_recommended']))->field('m_id')->find();
			if (!$return_m_p_id['m_id']) {
			return;
			//throw new PDOException('没有找到推荐人（' . $ary_member['m_name'] . '）', 7005);
			}
			if (!empty($int_m_id) && !empty($return_m_p_id)) {
				if (isset($return_m_p_id['m_id'])) {
					$return_mr_mid = D('MemberRelation')->where(array('m_id'=>$return_m_p_id['m_id']))->find();
				}
				if (empty($return_mr_mid) || !is_array($return_mr_mid)) {
					$p_result = D('MemberRelation')->data(array('m_id'=>$return_m_p_id['m_id'],'mr_p_id'=>0,'mr_order'=>0,'mr_child_count'=>0,'mr_depth'=>0))->add();
					if (!$p_result) {
					return;
						//throw new PDOException('生成分销商推荐关系时遇到错误！', 7006);
					}
					$m_path = $return_m_p_id['m_id'] . ',';
				} else {
					if (!isset($return_mr_mid['mr_path'])) {
						$m_path = $return_mr_mid['m_id'] . ',';
					} else {
						$m_path = $return_mr_mid['mr_path'] . $return_mr_mid['m_id'] . ',';
					}
				}
				$result = D('MemberRelation')->data(array('m_id'=>$int_m_id,'mr_path'=>$m_path,'mr_p_id'=>$return_m_p_id['m_id'],'mr_order'=>0,'mr_child_count'=>0,'mr_depth'=>0))->add();
				if (!$result) {
				return;
					//throw new PDOException('生成分销商推荐关系时遇到错误！', 7007);
				}
				//绑定现在的分销商授权关系
				$ary_params['m_id']=$int_m_id;
				$ary_params['p_id']=$return_m_p_id['m_id'];
				$add_bool_res=D('AuthorizeLine')->DistributorAutoAuthorize($ary_params);
				if($add_bool_res==false){
				   return false;
				}
			}
		}
    }	

    public function partnerInfo($page_no = '1',$page_size = '10',$ary_where = array(),$ary_field = '',$ary_orders=array()){
        $ary_members = M('partner',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->limit($page_no,$page_size)->order($ary_orders)->select();
        return $ary_members;
    } 


    
    public function supplierInfo($page_no = '1',$page_size = '10',$ary_where = array(),$ary_field = '',$ary_orders=array()){
        $ary_members = M('supplier',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where($ary_where)->limit($page_no,$page_size)->order($ary_orders)->select();
        return $ary_members;
    } 

    public function UpdateSupplier($mid,$data) {
        $result = M('supplier',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$mid))->save($data);
        return $result;
    }

    /******************************** APP 接口Model *****************************************************/
    
    /** APP会员注册
     ** @param array $ary_members 注册用户基本信息
     ** @time 2016-12-19
    **/
    public function doRegisterAppUser($ary_members){
        //注册奖励积分
        $obj_point = D('PointConfig');
        $int_point = $obj_point->getConfigs('regist_points');

        if(null !== $int_point && is_numeric($int_point) && $int_point>0) {
            $this->total_point = intval($int_point);
        }
        
        $this->m_name = $ary_members['m_name'];
        $this->m_password = md5($ary_members['m_password']);
        
        if(!empty($ary_members['m_real_name']) && isset($ary_members['m_real_name'])){
            $this->m_real_name = $ary_members['m_real_name'];
        }
        
        if(!empty($ary_members['m_mobile']) && isset($ary_members['m_mobile'])){
            $this->m_mobile = encrypt($ary_members['m_mobile']);
        }
        
        if(!empty($ary_members['m_email']) && isset($ary_members['m_email'])){
            $this->m_email = $ary_members['m_email'];
        }
        
        if(!empty($ary_members['cr_id']) && isset($ary_members['cr_id'])){
            $this->cr_id = $ary_members['cr_id'];
        }
        
        if(!empty($ary_members['m_address_detail']) && isset($ary_members['m_address_detail'])){
            $this->m_address_detail = $ary_members['m_address_detail'];
        }
        
        if(!empty($ary_members['m_zipcode']) && isset($ary_members['m_zipcode'])){
            $this->m_zipcode = $ary_members['m_zipcode'];
        }
        
        if(!empty($ary_members['m_telphone']) && isset($ary_members['m_telphone'])){
            $this->m_telphone = encrypt($ary_members['m_telphone']);
        }
        
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $this->m_alipay_name = $ary_members['m_alipay_name'];
        }
        
        if(!empty($ary_members['m_balance_name']) && isset($ary_members['m_balance_name'])){
            $this->m_balance_name = $ary_members['m_balance_name'];
        }
        
        if(!empty($ary_members['m_subcompany_id']) && isset($ary_members['m_subcompany_id'])){
            $this->m_subcompany_id = $ary_members['m_subcompany_id'];
        }
        
        if(!empty($ary_members['m_wangwang']) && isset($ary_members['m_wangwang'])){
            $this->m_wangwang = $ary_members['m_wangwang'];
        }
        
        if(!empty($ary_members['m_qq']) && isset($ary_members['m_qq'])){
            $this->m_qq = $ary_members['m_qq'];
        }
        
        if(!empty($ary_members['m_website_url']) && isset($ary_members['m_website_url'])){
            $this->m_website_url = $ary_members['m_website_url'];
        }
        
        if(!empty($ary_members['m_create_time']) && isset($ary_members['m_create_time'])){
            $this->m_create_time = $ary_members['m_create_time'];
        }
        
        if(!empty($ary_members['m_recommended']) && isset($ary_members['m_recommended'])){
            $this->m_recommended = $ary_members['m_recommended'];
        }
        
        $memberType = D('SysConfig')->getCfg('MEMBER_SET','MEMBER_TYPE','0','会员默认类型');
        $ary_members['m_type'] = $memberType['MEMBER_TYPE']['sc_value'] == '1' ? '1' : '0';
        
        if(!empty($ary_members['m_type']) && isset($ary_members['m_type'])){
            $this->m_type = $ary_members['m_type'];
        }
        
        if(!empty($ary_members['m_alipay_name']) && isset($ary_members['m_alipay_name'])){
            $this->m_alipay_name = $ary_members['m_alipay_name'];
        }
        
        if(!empty($ary_members['ml_id']) && isset($ary_members['ml_id'])){
            $this->ml_id = $ary_members['ml_id'];
        }
        
        if(!empty($ary_members['m_verify']) && isset($ary_members['m_verify'])){
            $this->m_verify = $ary_members['m_verify'];
        }
        
        if(!empty($ary_members['m_id_card']) && isset($ary_members['m_id_card'])){
            $this->m_id_card = $ary_members['m_id_card'];
        }

        //店铺来源  
//        if($_SESSION['SHOPCODE']!=''){
//          $ary_members['shop_code'] = $_SESSION['SHOPCODE'];
//          $this->shop_code = $_SESSION['SHOPCODE'];
//          unset($_SESSION["SHOPCODE"]);
//      }
        $this->m_status = intval($ary_members['m_status']);

        $member_id = $this->add();

//        echo "<pre>";print_r($this->getLastSql());exit;
        /*if($member_id>0 && $int_point>0) {
                //插入积分日志表
                $ary_temp = array(
                                'type'=>2,
                                'consume_point'=> 0,
                                'reward_point'=> intval($int_point),
                                );
                D('PointLog')->addPointLog($ary_temp,$member_id);
        }*/
        
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
        //会员推送到线下系统
        if($member_id >0 && C('AUTO_SNYC_MEMBER')==1){
            $ary_members['method'] ='AddMember';
            if(!isset($ary_members['id'])){
                $ary_members['m_id'] = $member_id;
            }
            $str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
            $host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
            request_by_fsockopen($host_url.'/Script/Batch/snyc_send',$ary_members);
        }
        
        $return['data'] = array('m_id'=>$member_id,"status"=>"1");
        return $return;
    }

    /**
     ** 如果有推荐人，成为子分销商
     ** @time 2016-12-19
     */
    public function addAppRecommended($ary_member) {
        if (!empty($ary_member['m_recommended'])) {
            $m_path = '';
            $int_m_id = $ary_member['m_id'];
            $return_m_p_id = D('Members')->where(array('m_name'=>$ary_member['m_recommended']))->field('m_id')->find();
            if (!$return_m_p_id['m_id']) {
                return;
                //throw new PDOException('没有找到推荐人（' . $ary_member['m_name'] . '）', 7005);
            }
            if (!empty($int_m_id) && !empty($return_m_p_id)) {
                if (isset($return_m_p_id['m_id'])) {
                    $return_mr_mid = D('MemberRelation')->where(array('m_id'=>$return_m_p_id['m_id']))->find();
                }
                if (empty($return_mr_mid) || !is_array($return_mr_mid)) {
                    $p_result = D('MemberRelation')->data(array('m_id'=>$return_m_p_id['m_id'],'mr_p_id'=>0,'mr_order'=>0,'mr_child_count'=>0,'mr_depth'=>0))->add();
                    if (!$p_result) {
                        return;
                        //throw new PDOException('生成分销商推荐关系时遇到错误！', 7006);
                    }
                    $m_path = $return_m_p_id['m_id'] . ',';
                } else {
                    if (!isset($return_mr_mid['mr_path'])) {
                        $m_path = $return_mr_mid['m_id'] . ',';
                    } else {
                        $m_path = $return_mr_mid['mr_path'] . $return_mr_mid['m_id'] . ',';
                    }
                }
                $result = D('MemberRelation')->data(array('m_id'=>$int_m_id,'mr_path'=>$m_path,'mr_p_id'=>$return_m_p_id['m_id'],'mr_order'=>0,'mr_child_count'=>0,'mr_depth'=>0))->add();
                if (!$result) {
                    return;
                    //throw new PDOException('生成分销商推荐关系时遇到错误！', 7007);
                }
                //绑定现在的分销商授权关系
                $ary_params['m_id']=$int_m_id;
                $ary_params['p_id']=$return_m_p_id['m_id'];
                $add_bool_res=D('AuthorizeLine')->DistributorAutoAuthorize($ary_params);
                if($add_bool_res==false){
                    return false;
                }
            }
        }
    }


    /** 
     ** 验证帐号是否存在
     ** @param $m_name 注册帐号
     ** @time 2016-12-19
     */
    public function chkAppUserName($m_name,$role=''){
        if($role=='warehouse'){
            $reslut = $this->where(array('m_name'=>$m_name,'is_warehouse'=>1))->find();
        }
        else{
            $reslut = $this->where(array('m_name'=>$m_name))->find();
        }
        if($reslut){
            return true;
        }else{
            return false;
        }
    } 
    /** 
     ** APP 会员信息
     ** @param $m_id 会员ID
     ** @time 2016-12-19
     */
    public function getAppUserInfo($m_id,$ary_field='*'){
        //$userinfo = $this->where(array("m_id"=>$m_id))->field('m_id,m_mobile,m_name,m_sex,m_birthday,m_email,m_qq,m_alipay_name,login_type,m_recommended,m_head_img,m_status,m_create_time,m_last_login_time')->find();
        $userinfo = $this->field($ary_field)->where(array("m_id"=>$m_id))->find();
        return $userinfo;
    }
    //用户默认地址
    public function getUserDefaultAddress($m_id,$ary_field='*'){
        $defaultAddress = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("m_id"=>$m_id))->find();
        return $defaultAddress;
    }
    //用户头像、微信号等信息
    public function getUserFieldInfo($m_id,$field_id){
        $fieldInfo = M('members_fields_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array("u_id"=>$m_id,"field_id"=>$field_id))->find();
        return $fieldInfo;
    }
    /** 
     ** 注册用户登录
     ** @param $m_name 登录帐号
     ** @param $m_password 用户密码
     ** @time 2016-12-19
     */
    public function doAppUserLogin($m_name,$m_password){
        $condition = array(
            "m_password" => $m_password,
            "m_name"   => $m_name
        );
        //$userinfo = $this->where($condition)->field('m_id,m_mobile,m_name,m_sex,m_birthday,m_email,m_qq,m_alipay_name,login_type,m_recommended,m_head_img,m_status,m_create_time,m_last_login_time')->find();
        $userinfo = $this->where($condition)->find();
        return $userinfo;
    }
    /** 
     ** 更新会员信息
     ** @param $m_id 用户ID
     ** @param $data 更新的数据
     */
    public function appUpdUserInfo($m_id,$data,$ary_field='*'){
        $regs = $this->where(array('m_id'=>$m_id))->save($data);
        
        if($regs>0){
            //$userinfo = $this->where(array('m_id'=>$m_id))->field('m_id,m_mobile,m_name,m_sex,m_birthday,m_email,m_qq,m_alipay_name,login_type,m_recommended,m_head_img,m_status,m_create_time,m_last_login_time')->find();
            $userinfo = $this->field($ary_field)->where(array('m_id'=>$m_id))->find();
            return $userinfo;   
        }else{
            return $regs;   
        }
    }
    /**
     ** APP 会员信息
     ** @param $m_name 会员注册时的账号
     ** @time 2016-12-19
     */
    public function getAppUserInfob($m_name){
        //$userinfo = $this->where(array("m_id"=>$m_id))->field('m_id,m_mobile,m_name,m_sex,m_birthday,m_email,m_qq,m_alipay_name,login_type,m_recommended,m_head_img,m_status,m_create_time,m_last_login_time')->find();
        $userinfo = $this->where(array("m_name"=>$m_name))->find();
        return $userinfo;
    }
	/**
    **会员注册
    **/

}
