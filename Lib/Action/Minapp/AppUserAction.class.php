<?php
/*************************************APP接口*************************************************/
/*********************************************************************************************/
/*********************************************************************************************/
/*********************************************************************************************/

class AppUserAction extends GyfxAction {  
    
	public function sunday($patt,$text){

    	$patt_size = strlen($patt);
    	$text_size = strlen($text);
		$limit = $text_size - $patt_size;
		return $limit;
    	for ($i = 0;$i < $patt_size; $i++){
    		$shift[$patt[$i]] = $patt_size - $i;
    	}
    	$i = 0;
    	$limit = $text_size - $patt_size;
    	while ($i <= $limit){
    		$match_size = 0;
    		while ($text[$i + $match_size] == $patt[$match_size]) {
    			$match_size++;
    			if ($match_size == $patt_size) {
    				return $i;
    			}
    		}
    		$shift_index = $i + $patt_size;
    		if ($shift_index < $text_size && isset($shift[$text[$shift_index]])) {
    			$i += $shift[$text[$shift_index]];
    		}else{
    			$i += $patt_size;
    		}
    	}
    }
	
	/**
     * APP用户注册接口
     * @param $m_name 注册手机号码
     * @param $m_password 登录密码
     * @time 2016-12-19
     */
    public function doAppRegisterUser()
    {
        ///定义一个数组来储存数据
        $result = array();
		
		//实例化用户
		$member   = D('Members');
		
        //获取默认配置的会员等级
        $ml       = D('MembersLevel')->getSelectedLevel();
		
		///注册信息
        $phoneNumber   = $this->_post('phoneNumber');
        //$m_name   = "13480295506";
		$password = base64_decode($this->_post('password'));

		$validCode = $this->_post('validCode');

		$random = $this->_post('random');
		//$p = "123321";
		//$password = base64_decode($p);
        //$rand = $_SESSION['rand'];
		$rand = date("YmdH");
        $m_password = substr($password,0,$this->sunday(strval($rand),strval($password)));
        $m_password = !empty($m_password) ? $m_password : $this->_post('password');
//		$password_1 = base64_decode($this->_post('m_password_1'));
//        $m_password_1 = substr($password_1,0,$this->sunday(strval($rand),strval($password_1)));
//        $m_password_1 = !empty($m_password_1) ? $m_password_1 : $this->_post('m_password_1');
        if(empty($phoneNumber)||empty($password)||empty($validCode)){
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }else{
			////check 帐号是否已存在
			$now = time();
			$sql = 'select * from fx_validcodes where random="'.$random.'" and tel="'.$phoneNumber.'" and failtime>'.$now;
			//$result['sql'] = $sql;
			$M = M('');
			$rs = $M->query($sql);
        	if($validCode!=$rs[0]['validcode']||!$rs){
				$result["info"]   = $validCode."验证码不正确".session('validCode').'..';
				$result["status"] = "100020";
				//$result["validcode"] = $rs[0]['validcode'];
				print_r(json_encode($result));
				die;
        	}

			$row = $member->chkAppUserName(trim($phoneNumber));
			
			if(!empty($row) == "1"){
				$result["info"]   = "帐号已存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;	
			}else{
				//拼接数组
				$ary_member = array(
					'm_name'        => trim($phoneNumber),
					'm_password'    => $m_password,
					//'m_password_c' => $m_password_1,
					'm_mobile'      => $phoneNumber,//$this->_post('m_mobile'),
					'm_wangwang'    => ($this->_post('m_wangwang')) ? $this->_post('m_wangwang') : "",
					'm_qq'          => ($this->_post('m_qq')) ? $this->_post('m_qq') : "",
					'm_id_card'     => ($this->_post('m_id_card')) ? $this->_post('m_id_card') : "",
					'm_website_url' => ($this->_post('m_website_url')) ? $this->_post('m_website_url') : "",
					'm_create_time' => date('Y-m-d H:i:s'),
					'm_status'      => '1',
					'ml_id'         =>  $ml,
					'm_recommended' => $this->_post('m_recommended')
				);

				if(!empty($ary_member['m_recommended'])){
					$reMid = $member->where(array('m_name'=>$ary_member['m_recommended']))->getField('m_id');
					if(empty($reMid)){
						unset($ary_member['m_recommended']);
					}
				}
				if (!empty($_POST['m_real_name']) && isset($_POST['m_real_name'])) {
					$ary_member['m_real_name'] = $this->_post('m_real_name');
				}
				if (!empty($_POST['m_email']) && isset($_POST['m_email'])) {
					$ary_member['m_email'] = $this->_post('m_email');
				}
				if (!empty($_POST['region1']) && isset($_POST['region1'])) {
					$ary_member['cr_id'] = $this->_post('region1');
				}else{
					if(!empty($_POST['city']) && isset($_POST['city'])){
						$ary_member['cr_id'] = $this->_post('city');
					}else{
						$ary_member['cr_id'] = $this->_post('province');
					}
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

				//从系统配置表中取出模块相关配置，MEMBER_SET：自动审核
				$data = D('SysConfig')->getCfgByModule('MEMBER_SET');
				if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
					$ary_member['m_verify'] = '2';
				}
	
				//扩展攻击
				foreach($ary_member as &$str_member){
					$str_member = htmlspecialchars($str_member);
					$str_member = RemoveXSS($str_member);
				}
				
				///处理注册返回记录
				$ary_result = $member->doRegisterAppUser($ary_member);
				$ary_result['data']['m_name'] = $this->_post('phoneNumber');
				
				if(empty($ary_result['data']["m_id"])){
					$result["info"]   = "注册失败";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					if($ary_result["data"]['status'] == '1') {
						if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
							$verify = 2;
						}
						/*把新增加用户属性项信息插入数据库 start*/
						D('MembersFieldsInfo')->doAdd($_POST,$ary_result['data']['m_id'],$verify);
						//注册成功判断是否开启积分
						$pointCfg = D('PointConfig')->getConfigs();
						if($pointCfg['is_consumed'] == '1' ){
							if($pointCfg['regist_points'] > 0){
								D('PointConfig')->setMemberRewardPoints($pointCfg['regist_points'],$ary_result['m_id'],2);
							}
							if($pointCfg['invites_points']>0 && !empty($ary_member['m_recommended'])){
								//$reMid = D('Members')->where(array('m_name'=>$ary_member['m_recommended']))->getField('m_id');
								if($reMid){
									D('PointConfig')->setMemberRewardPoints($pointCfg['invites_points'],$reMid,14);
								}
							}
						}
						//注册成功之后 如果有推荐人，成为子分销商
						$ary_member['m_id'] = $ary_result['data']['m_id'];
						if($reMid){
							$member->addAppRecommended($ary_member);
						}
						//注册成功 送注册优惠券一张
						D('CouponActivities')->doRegisterCoupon($ary_member['m_id']);

						$result["info"]   = "注册成功";
						$result["status"] = "10000";
						print_r(json_encode($result));
						//echo "<pre>";
						//print_r($result);
						die;
					} else {
						$result["info"]   = "注册成功";
						$result["status"] = "10000";
						print_r(json_encode($result));
						//echo "<pre>";
						//print_r($result);
						die;
					}	
				}
			}
        }
    }
    /**
     * APP用户登录接口
     * @param $m_name 登录帐号
     * @param $m_password 登录密码
     * @time 2016-12-19
     */
    public function appUserLogin(){
        ///定义一个数组来储存数据
        $result   = array();

        $member     = D("Members");
        $m_name     = $this->_post("phoneNumber");
        $m_password = md5($this->_post("password"));
		//$m_name = "15113738847";
		//$p ="aaa222";
		//$m_password = md5($p);
		// $password = base64_decode($this->_post('m_password'));
        // $rand = date("YmdH");
		// if(strstr($password,$rand)){
			// $m_password = substr($password,0,$this->sunday(strval($rand),strval($password)));
			// $m_password = !empty($m_password) ? $m_password : $this->_post('m_password');			
		// }else{
			// $m_password = $this->_post('m_password');
		// }
        //$reg = $member->query("select * from fx_members where m_mobile=$m_tel");

        if(empty($m_name)){
            $result["message"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
			die;
        }else{
			////check 帐号是否已存在
			$row = $member->chkAppUserName($m_name);
			if(empty($row)){
				$result["message"] = "帐号不存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;				
			}else{
				$userData = $member->doAppUserLogin($m_name,$m_password);
				//$userData = $member->where(array("m_mobile"=>$m_tel,"password"=>$password))->field('m_id,m_mobile,m_name,m_status,m_create_time')->find();
				if(empty($userData)){
					$result["message"] = "帐号或密码错误";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$data["m_last_login_time"] = date("Y-m-d H:i:s",time());
					$userId = $userData["m_id"];
					//$member->where(array('m_id'=>$userData['m_id']))->save($data);//
					$field = 'm_id as userId,ml_id as memberLevel,m_name as userName,m_head_img as userPic,m_sex as gender,m_mobile as mobile,m_birthday as birthday,m_qq as QQAccount,m_email as email,m_recommended as recommender,m_alipay_name as alipayAccount';
					$userInfo = $member->getAppUserInfo($userId,$field);

                    $field = 'ra_id as iD,ra_name as accepterName,cr_id as cityCode,ra_detail as address,ra_post_code as postCode,ra_mobile_phone as mobile,ra_is_default as isDefault,ra_id_card as iDCard,ra_status as status';
					$defaultAddress = $member->getUserDefaultAddress($userId,$field);

					$field_id = "20";
                    $userInfo["userPic"] = $member->getUserFieldInfo($userId,$field_id);

                    $field_id = "22";
                    $userInfo["WXAccount"] = $member->getUserFieldInfo($userId,$field_id);

					$userInfo["defaultAddress"] = $defaultAddress;
                    $body["userInfo"] = $userInfo;
                    $result["body"] = $body;
                    $result["message"]     = "登录成功";
					$result["status"]   = "10000";
		
					print_r(json_encode($result));
					// echo "<pre>";
					// print_r($result);
					die;
				}	
			}
        }
    }
	
	/**
     * APP用户   修改信息
     * @param $m_id 用户ID
     * @time 2016-12-19
    */
	public function updAppUserInfo(){
		//用户ID 
        $m_id   = $this->_post("m_id");
		//$m_id    = "2054";
		///实例化用户
		$member = D("Members");
		
		///定义一个数组来储存数据
        $result = array();
		if(empty($m_id)){
			$result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
			die;
		}else{
			$m_mobile      = encrypt($_POST["m_mobile"]);
			if($_POST["m_telphone"]){
			   $m_telphone = encrypt($_POST["m_telphone"]);	
			}else{
			   $m_telphone = $m_mobile;
			}
			$m_birthday    = $_POST["m_birthday"];
			$m_qq          = $_POST["m_qq"];
			$m_email       = $_POST["m_email"];
			$m_recommended = $_POST["m_recommended"];
			$m_alipay_name = $_POST["m_alipay_name"];
			$m_real_name   = $_POST["m_real_name"];
			
			if($_POST["m_mobile"]){$updData["m_mobile"]           = $m_mobile;}
			if($_POST["m_sex"]=="0"){$updData["m_sex"]            = "0";}else{$updData["m_sex"] = $_POST["m_sex"];}
			if($_POST["m_birthday"]){$updData["m_birthday"]       = $m_birthday;}
			if($_POST["m_qq"]){$updData["m_qq"]                   = $m_qq;}
			if($_POST["m_email"]){$updData["m_email"]             = $m_email;}
			if($_POST["m_recommended"]){$updData["m_recommended"] = $m_recommended;}
			if($_POST["m_alipay_name"]){$updData["m_alipay_name"] = $m_alipay_name;}
			if($_POST["m_real_name"]){$updData["m_real_name"]     = $m_real_name;}
			if($_POST["m_telphone"]){$updData["m_telphone"]     = $m_telphone;}

			$updData["m_update_time"] = date("Y-m-d H:i:s",time());
			
			//执行修改会员信息
			$regs = $member->appUpdUserInfo($m_id,$updData);
			if(empty($regs)){
				$result["info"] = "修改失败";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"] = "修改成功";
				$result["status"] = "10000";
				$result["userData"] = $regs;
				print_r(json_encode($result));
				die;	
			}
		}
	}
	/**
     * APP用户   修改密码
     * @param $_POST string $m_id 用户ID  必须需要参数，参数错误
     * @param $_POST string $old_password 原始密码  必须需要参数，参数错误
     * @param $_POST string $news_password 新密码  必须需要参数，参数错误
     * @param $_POST string $comf_password 二次确认密码 必须需要参数，参数错误
     * @time 2016-12-19
    */
	public function updAppUserPassword(){
		///定义一个数组来储存数据
        $result = array();
		
		//用户ID 
        $m_id          = $this->_post("m_id");
		//$m_id    = "2054";
		//原始密码
		$m_password   = $this->_post('old_password');
		//$m_password   = "123456789";
		//新密码
		$upd_password = $this->_post('news_password');
		//$upd_password = "123456";

		$validCode = $this->_post('validCode');

		$random = $this->_post('random');
		
		///实例化用户
		$member   = D("Members");
		$userData = $member->getAppUserInfo($m_id);
		
		if(empty($m_id)||empty($m_password)||empty($upd_password)||empty($validCode)||empty($random)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(md5($m_password) != $userData["m_password"]){
				$result["info"]   = "原始密码不正确";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}

			$now = time();
			$sql = 'select * from fx_validcodes where random="'.$random.'" and tel="'.$userData['m_name'].'" and failtime>'.$now;
			//$result['sql'] = $sql;
			$M = M('');
			$rs = $M->query($sql);
        	if($validCode!=$rs[0]['validcode']||!$rs){
				$result["info"]   = $validCode."验证码不正确";
				$result["status"] = "100020";
				print_r(json_encode($result));
				die;
        	}

			$data["m_password"] = md5($upd_password);
			$regs = $member->appUpdUserInfo($m_id,$data);

			if($regs > 0){
    			$result["info"]   = "修改成功";
    			$result["status"] = "10000";
    			print_r(json_encode($result));
    			die;
    		}else{

    			$result["info"]   = "修改失败";
    			$result["status"] = "10004";
    			print_r(json_encode($result));
    			die;
    		}
		}
	}
	/**
     * APP用户   忘记密码
     * @param $_POST string $m_name 用户登录的手机号码  必须需要参数，参数错误
     * @time 2016-12-19
    */
	public function forgetAppUserPass(){
		///定义一个数组来储存数据
        $result = array();
		
		//用户ID 
        $m_name = $_POST["m_name"];
		//$m_name = "13480295506";
		
		if(empty($m_name)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$member = D("Members");
			$row = $member->chkAppUserName($m_name);
			if(empty($row)){
				$result["info"] = "帐号不存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;				
			}else{
				$userData = $member->getAppUserInfob($m_name);
				$udata    = array(
					"m_id"   => $userData["m_id"],
					"m_name" => $userData["m_name"],
				);
				//$userData = $member->where(array("m_mobile"=>$m_tel,"password"=>$password))->field('m_id,m_mobile,m_name,m_status,m_create_time')->find();
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["udata"]  = $udata;
				print_r(json_encode($result));
				die;				
			}
		}
	}
	/**
     * APP用户   忘记密码
     * @param $_POST string $m_tel 用户登录的手机号码  必须需要参数，参数错误
     * @time 2016-12-19
    */
	    public function doForgetUserPass(){
		///定义一个数组来储存数据
    	$result = array();

		//用户ID 
    	$m_name          = $this->_post("m_name");
		//新密码
    	$m_password    = $this->_post('m_password');

    	$validCode = $this->_post('validCode');

    	$random = $this->_post('random');


    	if(empty($m_name) || empty($m_password)||empty($validCode)||empty($random)){
    		$result["info"]   = "参数错误";
    		$result["status"] = "10001";
    		print_r(json_encode($result));
    		die;
    	}else{

    		$now = time();
    		$sql = 'select * from fx_validcodes where random="'.$random.'" and tel="'.$m_name.'" and failtime>'.$now;
    		$M = M('');
    		$rs = $M->query($sql);
    		if($validCode!=$rs[0]['validcode']||!$rs){
    			$result["info"]   = $validCode."验证码不正确";
    			$result["status"] = "100020";
    			print_r(json_encode($result));
    			die;
    		}

    		$sql = "update fx_members set m_password=md5('".$m_password."') where m_name='".$m_name."'";
    		$regs = M("")->query($sql);

    		if($regs > 0){
    			$result["info"]   = "修改成功";
    			$result["status"] = "10000";
    			print_r(json_encode($result));
    			die;
    		}else{

    			$result["info"]   = "修改失败";
    			$result["status"] = "10004";
    			print_r(json_encode($result));
    			die;
    		}
    	}
    }
	
	/**
     * APP用户   发送短信
     * @param $_POST string $phone 接受验证码的手机号码
     * @time 2017-02-17
    */
    public function Sendsms()
    {
        $phone = $_REQUEST['tel'];
        //$phone = "13480295506";
		
        $num = '';
        for($i=0; $i<6; $i++){
            $num .= rand(0,9);
        }
        $ip = $this->get_client_ip();
        date_default_timezone_set('PRC'); //设置默认时区为北京时间
        //短信接口用户名 $uid
        $uid = 'SANGPER005221';
        //短信接口密码 $passwd
        $passwd = 'sangper';
        //发送到的目标手机号码 $telphone
        $telphone = $phone;
        //短信内容 $message
        $message = $num . "（动态注册手机验证码）。客服人员不会向您索要，请勿向任何人泄露。【芥末商城】";
        $msg = rawurlencode(mb_convert_encoding($message, "gb2312", "utf-8"));
        //echo $message;
        //die();
        $gateway = "http://mb345.com:999/ws/batchSend.aspx?CorpID={$uid}&Pwd={$passwd}&Mobile={$telphone}&Content={$msg}&Cell=&SendTime=";
        $result = file_get_contents($gateway);
		$res=array();
		
        if($result == 0 || $result == 1){
			$sms_info = $ip . ',' . $num . ',' . $phone;
            $res['status'] = "10000";
            $res['code']   = $num;
            $res['info']   = "发送成功，请注意到手机查收！";
            //session(array('validCode'=>$num,'expire'=>100));
            session('validCode',$num);
            //echo session('validCode');
            print_r(json_encode($res));
            die;
        }else{
            $res['status'] = "10001";
            $res['info']   = "发送失败, 错误提示代码: ".$result;
            print_r(json_encode($res));
            die;
        }
    }

    public function aliRegisterSms(){         
		$TemplateCode = "SMS_70010363";
        $RecNum = $_REQUEST['tel'];
        $validcode = '';
        for($i=0; $i<6; $i++){
            $validcode .= rand(0,9);
        }
        $ParamString = "{\"validCode\":\"".$validcode."\"}";
        require_once './Lib/Common/aliyun-php-sdk-core/Config.php';     
		try {
		    $response = $client->getAcsResponse($request);

		    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; 
			$random = ''; 
			for($i = 0;$i<16;$i++){ 
				$random .= $chars[mt_rand(0,strlen($chars)-1)]; 
			}
			$failtime = time()+1200;
			$sql = 'insert into fx_validcodes(random,validcode,tel,failtime) values("'.$random.'","'.$validcode.'","'.$RecNum.'",'.$failtime.')';
			$M = M('');
			$M->query($sql);

		    $res['status'] = "10000";
            $res['random']   = $random;
            $res['info']   = "发送成功，请注意到手机查收！";
            //session(array('validCode'=>$num,'expire'=>100));
            //session('validCode',$num);
            //echo session('validCode');

            print_r(json_encode($res));
            die;

		}
		catch (ClientException  $e) {
		    //print_r($e->getErrorCode());   
		    //print_r($e->getErrorMessage());
		    $res['status'] = "10001";
            $res['info']   = "发送失败, 错误提示代码: ".$e->getErrorMessage();
            print_r(json_encode($res));
            die;   
		}
		catch (ServerException  $e) {      
		    //print_r($e->getErrorCode());   
		    //print_r($e->getErrorMessage());
		    $res['status'] = "10001";
            $res['info']   = "发送失败, 错误提示代码: ".$e->getErrorMessage();
            print_r(json_encode($res));
            die;
		}  	
    }
   
	
	/**
     * 我的资料
	 * @param string $m_id  会员ID
     * @time 2019-12-19
     */
	 public function appUserInfo(){
		///定义一个数组来储存数据
        $result   = array();
		 
		//用户ID 
        $m_id    = $_REQUEST["m_id"];
        //$m_id    = "4";
		
		$member   = D("Members");
		//$regs = $member->getAppUserInfo($m_id);
		$regs = $member->where(array("m_id"=>$m_id))->field('m_id,m_name,m_sex,m_birthday,m_email,m_qq,m_alipay_name,m_recommended')->find();
		
		////会员扩展属性
		$appUserAttr = D("MembersFieldsInfo")->where(array("u_id"=>$m_id))->field("m_field.field_name,m_field.id,fx_members_fields_info.*")->join("fx_members_fields as m_field on m_field.id=fx_members_fields_info.field_id")->select();
		
		$attr = array();
		///将二维数组转成成一维数组重新组成键=>值形式传送给客户端
		foreach($appUserAttr as $key=>$v){
			///重新规划扩展属性的键值
			if($v["id"]=="22"){
				$attrKey = "weixin";
			}
			if($v["id"]=="21"){
				$attrKey = "shouji";	
			}
			if($v["id"]=="20"){
				$attrKey = "touxiang";
			}
			if($v["id"]=="23"){
				$attrKey = "agreement";
			}
			$regs[$attrKey]=$v["content"];
		}
		
		/* 会员扩展属性 公司信息*/
        if(!empty($regs['m_subcompany_id'])){
            $subcompany_name=D('Subcompany')->where(array('s_id'=>$mem['m_subcompany_id']))->find();
            $attrs['subcompany_name']=$subcompany_name['s_name'];
        }
		
		//会员扩展属性 会员等级信息
		D('MembersLevel')->autoUpgrade($m_id);
        $ary_res_meml = D('MembersLevel')->getMembersLevels($regs['ml_id']);
		$attrs['members_level']=$ary_res_meml['ml_name'];		
		//会员升级提示 
        $grades = array();
		///会员等级信息
        $ary_men_list = D('MembersLevel')->getgradelist(array('ml_up_fee,ml_id'));
        foreach($ary_men_list as $grade){
            if($grade['ml_id']==($regs['ml_id']+1)){
                $next_level=$grade['ml_up_fee'].'元';
                break;
            }else{
                $next_level='已是最高等级！';
            }
		}
		$attrs['next_level'] = $next_level;
		
		//会员扩展属性 会员地区信息
		$region = array();
		$city  = D("CityRegion");


        $city_region_data = $city->getCityRegionInfoByLastCrId($regs['cr_id']);

		$proInfo  = $city->where(array("cr_id"=>$city_region_data["province"]))->field("cr_is_parent,cr_id,cr_name")->find();
		$cityInfo = $city->where(array("cr_id"=>$city_region_data["city"]))->field("cr_id,cr_name")->find();
		$country  = $city->where(array("cr_id"=>$proInfo["cr_is_parent"]))->field("cr_id,cr_name")->find();
		$region["country"]  = $country;
		$region["province"] = $proInfo;
		$region["city"]     = $cityInfo;
		
		//开启手机验证
		$mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
		$is_verifyMobile_on = array(
			"sc_module" => $mobile_set["VERIFIPHONE_STATUS"]["sc_module"],
			"sc_key"    => $mobile_set["VERIFIPHONE_STATUS"]["sc_key"],
			"sc_value"  => $mobile_set["VERIFIPHONE_STATUS"]["sc_value"],
		);
		
		if(empty($m_id)){
			$result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
			die;
		}else{
			if(empty($regs)){
				$result["info"] = "用户不存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]      = "请求成功";
				$result["status"]      = "10000";
				$result["udata"]     = $regs;
				$result["uattrdata"] = $attrs;
				$result["is_verifyMobile_on"]    = $is_verifyMobile_on;
				$result["regionInfo"]  = $region;
				$result["grades"]      = $ary_men_list;

	    // $result["info"] = $regs['cr_id'];
     //    $result["status"] = $m_id;
     //    $result["city_region_data"] = $city_region_data;
     //    print_r(json_encode($result));
	    // die;
				
				print_r(json_encode($result));
				  // echo "<pre>";
				  // print_r($result);
				die;
			}
		}
	}
	
	/**
     * 处理APPA会员我的资料修改
     * @param array() $_POST 修改的数据信息
     * @tmie 2016-12-19
     */
    public function doAppUserInfoEdit() {
		///定义一个数组来储存数据
        $result   = array();
		$member = D("Members");
		
		//$postData = $this->_post();
        $m_id     = $this->_post('m_id');
		//开启手机号验证
		$mobile_set = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
		
		if(empty($m_id) || $m_id=="0"){
			$result["info"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{		
			$sys_data = $member->where(array('m_id'=>$m_id))->find();
			//会员审核是否开启
			$ary_cfg  = D("SysConfig")->getConfigs("MEMBER_SET", "MEMBER_STATUS");
			//会员修改的信息
			$ary_params = array(
				'm_birthday'       => $this->_post('m_birthday'),
				'm_sex'            => $_POST["m_sex"],
				'm_email'          => $this->_post('m_email'),
				'm_alipay_name'    => $this->_post('m_alipay_name'),
				'm_mobile'         => encrypt($this->_post('m_mobile')),
				'm_telphone'       => encrypt($this->_post('m_telphone')),
				'm_qq'             => $this->_post('m_qq'),
				'm_verify'         => 1 == $ary_cfg['MEMBER_STATUS']['sc_value'] ? 2 : 4,
				'm_update_time'    => date('Y-m-d H:i:s',time()),
				'm_recommended'    => $this->_post('m_recommended')
			);
			
			if(!empty($ary_params['m_recommended'])){
				$reMid = D('Members')->where(array('m_name'=>$ary_params['m_recommended']))->getField('m_id');
				if(empty($reMid)){
					unset($ary_params['m_recommended']);
				}
			}
			if ($this->_post('province') >= 0) {
				$province=$this->_post('province');
				$ary_params['cr_id'] = $this->_post('province');
			}
			if ($this->_post('region1') > 0) {
				$ary_params['cr_id'] = $this->_post('region1');
			}
			elseif ($this->_post('city') > 0){
				$ary_params['cr_id'] = $this->_post('city');
			}
			
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
			
			$ary_result = D('Members')->where(array('m_id'=>$m_id))->data($ary_params)->save();
			if($ary_result == "1"){
				///会员扩展属性，微信号
				if($_POST["weixinhao"]){
					$weixinhao["u_id"]     = $m_id;
					$weixinhao["field_id"] = "22";
					$weixinhao["content"]  = $_POST["weixinhao"];
					$weixinhao["status"]   = "1";
					$regsul_wx = D('MembersFieldsInfo')->where(array('u_id'=>$m_id,"field_id"=>"22"))->find();
					if($regsul_wx){
						D('MembersFieldsInfo')->where(array('u_id'=>$mid,"field_id"=>"22"))->data($weixinhao)->save();
					}else{
						D('MembersFieldsInfo')->data($weixinhao)->add();
					}
				}
				///会员扩展属性，手机号码
				if($_POST["m_mobile"]){
					$shoujihao["u_id"]     = $m_id;
					$shoujihao["field_id"] = "21";
					$shoujihao["content"]  = $_POST["m_mobile"];
					$shoujihao["status"]   = "1";
					
					$regsul_mb = D('MembersFieldsInfo')->where(array('u_id'=>$m_id,"field_id"=>"21"))->find();
					
					if($regsul_mb){
						D('MembersFieldsInfo')->where(array('u_id'=>$m_id,"field_id"=>"21"))->data($shoujihao)->save();
					}else{
						D('MembersFieldsInfo')->data($shoujihao)->add();
					}
				}
				
				$result["info"] = "修改成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"] = "修改失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}
		}
    }
	/**
     * APP 会员中心 头像上传
	 * @param string $m_id  会员ID
	 * @param array $touxiang  头像信息
     * @time 2019-12-19
     */
	public function updAppUserHeaderImg(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id     = $this->_post('m_id');
		$file_extend_index = "touxiang";
		$tmp_files = $_FILES["touxiang"];
		if(!empty($tmp_files) && !empty($tmp_files['name']) && !empty($tmp_files['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/';
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_index] = $tmp_files;
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
			//$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_url = '/Public/Uploads/'.CI_SN.'/home/' .date('Ymd').'/'. $info[0]['savename'];
				$files_url = D('ViewGoods')->ReplaceItemPicReal($tmp_files_url);
				//会员扩展属性，头像
				$touxiang["u_id"]     = $m_id;
				$touxiang["field_id"] = "20";
				$touxiang["content"]  = $tmp_files_url;
				$touxiang["status"]   = "1";
				
				$regsul_mb = D('MembersFieldsInfo')->where(array('u_id'=>$m_id,"field_id"=>"20"))->find();
				if($regsul_mb){
					D('MembersFieldsInfo')->where(array('u_id'=>$m_id,"field_id"=>"20"))->data($touxiang)->save();
				}else{
					D('MembersFieldsInfo')->data($touxiang)->add();
				}
				unset($upfiles[$file_extend_index]);
				
				$userData = D('Members')->getAppUserInfo($m_id);
				$result["info"] = "上传成功";
				$result["status"] = "10000";
				$result["userData"] = $userData;
				print_r(json_encode($result));
				die;
			}
		}else{
			$result["info"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}
	}
	/**
     * APP 会员中心 手机验证是否已绑定用户
	 * @param string $m_id  会员ID
     * @time 2019-12-19
     */
	public function verifyMobile(){
		///定义一个数组来储存数据
        $result   = array();
		
		$m_mobile = $this->_post("m_mobile");
		$m_id = $this->_post("m_id");
		if(strpos($m_mobile, '*')) {
            unset($m_mobile);
        }else{
			if(empty($m_mobile) || empty($m_id)){
				$result["info"] = "参数错误";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$arr_members = D('Members')->field('m_mobile')->where(array('m_id'=>$m_id))->find();
				if(!empty($arr_members) && $m_mobile!= decrypt($arr_members['m_mobile'])){
					if (D('Members')->checkMobile(encrypt($m_mobile))) {
						$result["info"] = "该手机号已被注册，请重新输入";
						$result["status"] = "10002";
						print_r(json_encode($result));
						die;
					}else{
						$result["info"] = "该手机号可以注册";
						$result["status"] = "10000";
						print_r(json_encode($result));
						die;
					}
				}	
			}
        }
	}
	/**
     * APP 会员中心 我的优惠券
	 * @param string $m_id  会员ID
     * @time 2019-12-19
     */
	public function myCoupon(){
		///定义一个数组来储存数据
        $result   = array();
		//$m_id = $this->_post("m_id");
		$m_id = "2054";
		$mycoupon = D("coupon");
		
		if(empty($m_id)){
			$result["info"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$userCouponData = $mycoupon->where(array('c_user_id'=>$m_id))->select();
			if(empty($userCouponData)){
				$result["info"] = "当前用户无优惠券";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]           = "请求成功";
				$result["status"]           = "10000";
				$result["userCouponData"] = $userCouponData;
				print_r(json_encode($result));
				//echo "<pre>";
				//print_r($userCouponData);
				die;
			}
		}
	}
	/**
     * APP 会员中心 我的收藏
	 * @param string $m_id  会员ID
     * @time 2019-12-19
     */
	 public function myCollect(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id = $this->_post("m_id");
		//$m_id = "2113";
		//$collectgoods = D("CollectGoods");
		
		$module = M('CollectGoods',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array();			
        $where[C("DB_PREFIX").'collect_goods.m_id'] = $m_id;
		$count = $module->field(" ".C("DB_PREFIX")."collect_goods.m_id,".C("DB_PREFIX")."goods_info.*,".C("DB_PREFIX")."goods.*")->where($where)->count();
        // $obj_page = new Page($count, 10);
        // $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        // $page = $obj_page->show();
		$array_order = array(C("DB_PREFIX")."collect_goods.add_time" => 'desc');
        $ary_goods = $module
                ->field(" ".C("DB_PREFIX")."collect_goods.id,".C("DB_PREFIX")."collect_goods.m_id,".C("DB_PREFIX")."collect_goods.add_time,".C("DB_PREFIX")."goods_info.*,".C("DB_PREFIX")."goods.*")
                ->join(" ".C("DB_PREFIX")."goods_info ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."collect_goods.g_id")
                ->join(" ".C("DB_PREFIX")."goods ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."goods.g_id")
                ->where($where)->order($array_order)->select();

        if(!empty($ary_goods) && is_array($ary_goods)){
            foreach($ary_goods as $key=>&$val){
                $val['nums'] = $module->where(array("g_id"=>$val['g_id']))->count();
				// if($_SESSION['OSS']['GY_QN_ON'] == '1'){	
					// $val['g_picture'] = D('QnPic')->picToQn($val['g_picture']);
				// }else{
					$val['g_picture'] = '/'.ltrim($val['g_picture'],'/');
				//}
            }
        }
		
		if(empty($m_id)){
			$result["info"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(empty($ary_goods)){
				$result["info"] = "暂无收藏记录";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]             = "请求成功";
				$result["status"]             = "10000";
				$result["collectGoodsData"] = $ary_goods;
				print_r(json_encode($result));
				 // echo "<pre>";
				 // print_r($result);
				die;
			}
		}
	}
	
	/**
     * APP 会员中心 删除我的收藏
	 * @param string $m_id  会员ID
	 * @param string $id  收藏夹ID
     * @time 2019-12-19
     */
	public function delAppUserCollect(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id = $this->_post("m_id");
		$c_id = $this->_post("id");
		// $m_id = "2113";
		// $c_id = "200";
		
		if(empty($m_id) || empty($c_id)){
			$result["info"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$regs = M("CollectGoods")->where(array("m_id"=>$m_id,"id"=>$c_id))->delete();
			if(empty($regs)){
				$result["info"] = "删除失败";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"] = "删除成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}
		}
	}
	
	/**
     * APP 会员中心 站点公告
	 * @param string $m_id  会员ID 可传可不传
     * @time 2019-12-19
     */
	public function appUserNotice(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id = $this->_post("m_id");
		//$m_id = "2054";
		$userData = D("Members")->getAppUserInfo($m_id);
		
		$noticeObj = D('PublicNotice');
		
		if(empty($m_id)){
			$list = $noticeObj->field('pn_id,pn_title,pn_is_top,pn_create_time')
											->where('pn_status=1 and pn_is_all=1')
											->order('pn_is_top desc,pn_create_time desc')
											->select();
			//$count = $noticeObj->where('pn_status=1 and pn_is_all=1')->count();
			if(empty($list)){
				$result["info"] = "暂无公告";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]         = "请求成功";
				$result["status"]         = "10000";
				$result["publicNotice"] = $list;
				print_r(json_encode($result));
				die;
			}
		}else{
			$ml_id = $userData['ml_id'];
			$groupObj = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM');
			$mGroups = $groupObj->where(array('m_id'=>$m_id))->select();
			$group = array();
			if(!empty($mGroups) && is_array($mGroups)){
				foreach($mGroups as $vl){
					$group[] = $vl['mg_id'];
				}
			}
			$mGroups = implode(',', $group);
//                echo "<pre>";print_r($mGroups);exit;
			$where = '';
			if($ml_id){
					$where .= " or ml_id={$ml_id}";
			}
			if($mGroups){
					$where .= " or mg_id in ({$mGroups})";
			}
			$list = $noticeObj->field('pn_id,pn_title,pn_is_top,pn_create_time')
											->join("inner join (select mc_id from fx_member_competence where
															(m_id = -1 or m_id={$m_id}{$where}) and mc_type=1 group by mc_id
															) as t on(fx_public_notice.pn_id=t.mc_id)")
											->where('pn_status=1')
											->order('pn_is_top desc,pn_create_time desc')
											->select();
			
			if(empty($list)){
				$result["info"] = "暂无公告";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]         = "请求成功";
				$result["status"]         = "10000";
				$result["publicNotice"] = $list;
				print_r(json_encode($result));
				die;
			}			
			/*$count = $noticeObj->join("inner join (select mc_id from fx_member_competence where
															(m_id = -1 or m_id={$m_id}{$where}) and mc_type=1 group by mc_id
															) as t on(fx_public_notice.pn_id=t.mc_id)")->where('pn_status=1')->count();*/

		}
	}

	/**
     * APP 会员中心 站点公告详情
	 * @param string $pn_id  公告ID
     * @time 2019-12-19
     */
	public function appUserNoticeInfo(){
		///定义一个数组来储存数据
        $result   = array();
		//$pn_id = $this->_post("pn_id");
		$pn_id = "6";
		$publicnoticeinfo = D("PublicNotice")->where(array("pn_id"=>$pn_id))->find();
		if(empty($pn_id)){
			$result["info"]         = "参数错误";
			$result["status"]         = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]             = "请求成功";
			$result["status"]             = "10000";
			$result["PublicNoticeInfo"] = $publicnoticeinfo;
			print_r(json_encode($result));
			die;
		}
	}
	
	
	/**
     * APP 会员中心 关于我们
	 * @param string $pn_id  公告ID
     * @time 2019-12-19
     */
	public function appUserAbout(){
		///定义一个数组来储存数据
        $result   = array();
		//$pn_id = $this->_post("pn_id");
		$a_id = "4";
		$aboutData = D("Article")->where(array("a_id"=>$a_id))->find();
		if(empty($aboutData)){
			$result["info"]         = "暂无内容";
			$result["status"]         = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"]   = "10000";
			$result["abdata"] = $aboutData;
			print_r(json_encode($result));
			die;
		}
	}
	/**
     * APP 会员中心 收货地址
	 * @param string $m_id  会员ID
     * @time 2019-12-19
     */
	public function appUserAddress(){
		///定义一个数组来储存数据
        $result   = array();
		
		//
		$m_id = $_REQUEST["m_id"];
		
		$city = D("CityRegion");
        //$ary_city = $city->getCurrLvItem(1);
		
		$addr = D("ReceiveAddress");
		$field = "fx_receive_address.ra_id,fx_receive_address.ra_name,fx_receive_address.ra_mobile_phone,fx_receive_address.cr_id,fx_receive_address.ra_detail,fx_receive_address.ra_is_default";
		$ary_addr = $addr->field($field)->join("fx_city_region as r on r.cr_id=fx_receive_address.cr_id")->where(array("fx_receive_address.m_id"=>$m_id))->select();
		
		//查询出省市
		foreach($ary_addr as $k=>$v){
			//区
			$area     = $city->field("cr_id,cr_name,cr_parent_id")->where(array("cr_id"=>$v["cr_id"]))->find();
			//市
			$town     = $city->field("cr_id,cr_name,cr_parent_id")->where(array("cr_id"=>$area["cr_parent_id"]))->find();
			//省份
			$province = $city->field("cr_id,cr_name,cr_parent_id")->where(array("cr_id"=>$town["cr_parent_id"]))->find();

			$ary_addr[$k]["province"] = $province["cr_name"];
			$ary_addr[$k]["pro_id"]   = $province["cr_id"];
			$ary_addr[$k]["town"]     = $town["cr_name"];
			$ary_addr[$k]["town_id"]  = $town["cr_id"];
			$ary_addr[$k]["area"]     = $area["cr_name"];
			$ary_addr[$k]["ra_mobile_phone"] = decrypt($v["ra_mobile_phone"]);
		}
		
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(empty($ary_addr)){
				$result["info"]   = "暂无收货地址";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]     = "请求成功";
				$result["status"]   = "10000";
				$result["addrdata"] = $ary_addr;
				print_r(json_encode($result));
				die;
				// echo "<pre>";
				// print_r($result);
				// die;
			}
		}
	}
	/**
     * APP 会员中心 添加收货地址
	 * @param string $m_id  
     * @time 2019-12-19
     */
	public function addAppUserAddr(){
		///定义一个数组来储存数据
        $result = array();
		$m_id   = $_REQUEST["m_id"];
		//$m_id   = "2164";
		//返回地区信息
		$city = D("CityRegion");
        $ary_city = $city->getAppCurrLvItem(1);
		
		//地区联动，把所有地区循环出来
		foreach($ary_city as $k=>$v){
			$citylists = $city->field("cr_id,cr_name")->where(array("cr_parent_id"=>$v["cr_id"]))->order("cr_id asc")->select();
			$ary_city[$k]["citylists"] = $citylists;
			foreach($citylists as $key=>$vals){
				$townlists = $city->field("cr_id,cr_name")->where(array("cr_parent_id"=>$vals["cr_id"]))->select();
				$ary_city[$k]["citylists"][$key]["townlists"] = $townlists;
			}
		}
		
		//参数是否存在
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["redata"] = $ary_city;
			print_r(json_encode($result));
			die;	
		}
	}
	/**
     * APP 会员中心 添加地区，获取二级城市
	 * @param string $prent_id  地区父级ID
     * @time 2019-12-19
     */
	public function appSubRegion(){
		///定义一个数组来储存数据
        $result   = array();
		$prent_id = $this->_post("r_id");
		
		if(empty($prent_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$city = D("CityRegion");
			$sub_city = $city->getAppCurrLvItem($prent_id);
			if(empty($sub_city)){
				$result["info"]     = "该一级城市下没有二级城市";
				$result["status"]   = "10003";
				print_r(json_encode($result));
				die;	
			}else{
				$result["info"]     = "请求成功";
				$result["status"]   = "10000";
				$result["sub_city"] = $sub_city;
				print_r(json_encode($result));
				die;	
			}
		}
	}

	/**
     * APP 会员中心 添加地区，获取区级城市
	 * @param string $prent_id  地区父级ID
     * @time 2019-12-19
     */
	public function appTownRegion(){
		///定义一个数组来储存数据
        $result   = array();
		$prent_id = $this->_post("r_id");
		
		if(empty($prent_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$city = D("CityRegion");
			$town = $city->getAppCurrLvItem($prent_id);
		
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["town"]   = $town;
			print_r(json_encode($result));
			die;
			// echo "<pre>";
			// print_r($result);
			// die;	
		}
	}	
	
	/**
     * APP 会员中心 处理添加收货地址信息
	 * @param string $m_id  公告ID
     * @time 2019-12-19
     */
	public function doAddAppUserAddr(){
		///定义一个数组来储存数据
        $result          = array();
		$m_id            = $_POST["m_id"];
		$cr_id           = $_POST["cr_id"];
		$ra_name         = $_POST["ra_name"];
		$ra_detail       = $_POST["ra_detail"];
		// $ra_mobile_phone = encrypt($_POST["ra_mobile_phone"]);
		$ra_mobile_phone = $_POST["ra_mobile_phone"];
		//$m_id = "2054";
		
		$addr = D("ReceiveAddress");
		
		if(empty($m_id) || empty($cr_id) || empty($ra_name) || empty($ra_mobile_phone)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$addrdata["cr_id"]           = $cr_id;
			$addrdata["m_id"]            = $m_id;
			$addrdata["ra_name"]         = $ra_name;
			$addrdata["ra_detail"]       = empty($_POST["ra_detail"])?"":$_POST["ra_detail"];
			$addrdata["ra_post_code"]    = empty($_POST["ra_post_code"])?"":$_POST["ra_post_code"];
			$addrdata["ra_phone"]        = empty($_POST["ra_phone"])?"":$_POST["ra_phone"];
			$addrdata["ra_mobile_phone"] = $ra_mobile_phone;
			$addrdata["ra_is_default"]   = empty($_POST["ra_is_default"])?"0":$_POST["ra_is_default"];
			$addrdata["ra_id_card"]      = empty($_POST["ra_id_card"])?"":$_POST["ra_id_card"];
			$addrdata["ra_status"]       = empty($_POST["ra_status"])?"1":$_POST["ra_status"];
			$addrdata["ra_create_time"]  = date("Y-m-d H:i:s",time());


			// $addressList = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->field("ra_is_default,cr_id")->where(array("m_id"=>$m_id))->select();

			// foreach ($addressList as $addressKey => $addressValue) {
			// 	if ($addressValue["cr_id"] != $cr_id) {
			// 		$addressList[$addressKey]["ra_is_default"] = 0;
			// 		M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->where(array("cr_id")=>$addressValue["cr_id"])->save($addressList[$addressKey]);
			// 		$result[$addressKey] = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
			// 	}
			// }
		 //    $result["addressList"] = $addressList;
			//插入数据
			if ($addrdata["ra_is_default"] == "1") {
				$sql = "update fx_receive_address set ra_is_default='0' where m_id=$m_id";
				$reg = M('')->query($sql);
			}
			$regs = $addr->add($addrdata);			
			
			//返回添加记录
			if($regs>0){
				$result["info"]   = "添加成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "添加失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}
		}
	}
	/**
     * APP 会员中心 修改收货地址
	 * @param string $m_id  公告ID
     * @time 2019-12-19
     */
	public function editAppUserAddr(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id = $_POST["m_id"];
		$a_id = $_POST["ra_id"];
		//$m_id = "2164";
		//$a_id = "239";
		
		$city = D("CityRegion");
        // $ary_city = $city->getCurrLvItem(1);
		$addr = D("ReceiveAddress");
		
		// foreach($ary_city as $k=>$v){
		// 	$citylists = $city->field("cr_id,cr_name")->where(array("cr_parent_id"=>$v["cr_id"]))->order("cr_id asc")->select();
		// 	$ary_city[$k]["citylists"] = $citylists;
		// 	foreach($citylists as $key=>$vals){
		// 		$townlists = $city->field("cr_id,cr_name")->where(array("cr_parent_id"=>$vals["cr_id"]))->select();
		// 		$ary_city[$k]["citylists"][$key]["townlists"] = $townlists;
		// 	}
		// }
		
		if(empty($m_id) || empty($a_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$addrinfo = $addr->where(array("m_id"=>$m_id,"ra_id"=>$a_id))->find();
			$addrinfo["ra_mobile_phone"] = decrypt($addrinfo["ra_mobile_phone"]);
			//取得地区区信息
			$area = $city->field("cr_id,cr_name,cr_parent_id")->where("cr_id={$addrinfo['cr_id']}")->find();
			$town = $city->field("cr_id,cr_name,cr_parent_id")->where("cr_id={$area['cr_parent_id']}")->find();
			$prov = $city->field("cr_id,cr_name,cr_parent_id")->where("cr_id={$town['cr_parent_id']}")->find();
			$addrinfo["area"]    = $area["cr_name"];
			$addrinfo["town_id"] = $town["cr_id"];
			$addrinfo["town"]    = $town["cr_name"];
			$addrinfo["prov_id"] = $prov["cr_id"];
			$addrinfo["prov"]    = $prov["cr_name"];
			
			
			if(empty($addrinfo)){
				$result["info"]   = "信息有误";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]     = "请求成功";
				$result["status"]   = "10000";
				$result["addrinfo"] = $addrinfo;
				//$result["redata"]   = $ary_city;
				print_r(json_encode($result));
				die;
			}
		}
	}
	/**
     * APP 会员中心 处理收货地址
	 * @param string $m_id  公告ID
     * @time 2019-12-19
     */
	public function doEditAppUserAddr(){
		///定义一个数组来储存数据
        $result   = array();
		$m_id            = $_POST["m_id"];
		$ra_id           = $_POST["ra_id"];
		$cr_id           = $_POST["cr_id"];
		$ra_name         = $_POST["ra_name"];
		$ra_mobile_phone = encrypt($_POST["ra_mobile_phone"]);
		$ra_detail       = $_POST["ra_detail"];
		//$m_id = "2054";
		
		$addr = D("ReceiveAddress");
		
		if(empty($m_id) || empty($ra_id) || empty($cr_id) || empty($ra_name) || empty($ra_mobile_phone)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$addrdata["cr_id"]           = $cr_id;
			$addrdata["m_id"]            = $m_id;
			$addrdata["ra_name"]         = $ra_name;
			$addrdata["ra_detail"]       = empty($ra_detail)?"":$ra_detail;
			$addrdata["ra_mobile_phone"] = $ra_mobile_phone;
			$addrdata["ra_update_time"]  = date("Y-m-d H:i:s",time());
			
			//更新记录
			$regs = $addr->where("ra_id={$ra_id}")->save($addrdata);			
			
			//返回更新记录
			if($regs>0){
				$result["info"]   = "修改成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "修改失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}
		}
	} 
	/**
     * APP 会员中心 删除地址
	 * @param string $m_id  公告ID
     * @time 2019-12-19
     */
	public function appDelUserAddr(){
		///定义一个数组来储存数据
        $result   = array();
		
		$m_id = $_POST["m_id"];
		$a_id = $_POST["a_id"];
		// $m_id = "2054";
		// $a_id = "238";
		
		$addr = D("ReceiveAddress");
		
		if(empty($m_id) || empty($a_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$ary_res = $addr->where(array("m_id"=>$m_id,"ra_id"=>$a_id))->delete();
			//是否删除成功
			if(trim($ary_res)=="1"){
				$result["info"]   = "删除成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "删除失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}
		}
	} 
	
	//设置默认地址
	public function appSetAddrDefault(){
		///定义一个数组来储存数据
        $result   = array();
		//地址表ID
		$a_id = $_POST["a_id"];
		//$a_id = "244";
		// $m_id = "2054";
		// $a_id = "238";
		
		$addr = D("ReceiveAddress");
		$addressinfo = $addr->where(array("ra_id"=>$a_id))->find();
		$addreslists = $addr->where(array("m_id"=>$addressinfo["m_id"]))->select();
		
		if(empty($a_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$data["ra_is_default"] = "1";
			$dat["ra_is_default"]  = "0";
			$ary_res = $addr->where(array("ra_id"=>$a_id))->save($data);
			
			if($addreslists){
				foreach($addreslists as $v){
					if($a_id!=$v["ra_id"]){
						$addr->where(array("ra_id"=>$v["ra_id"]))->save($dat);	
					}
				}
			}
			//是否设置成功
			if($ary_res){
				$result["info"]   = "设置成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "设置失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}
		}
	}
	/**
     * 获取客户端IP地址
     * @return string
     */
    function get_client_ip() {
        if(getenv('HTTP_CLIENT_IP')){
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR')) {
            $client_ip = getenv('REMOTE_ADDR');
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }

	/** 首页头部分类
     ** @param 无参数 
     */
	public function appTopCate()
	{
		//定义一个数组来储存数据
		$result = array();
		
		//分类信息
		$cate   = D("GoodsCategory");
		$catedata = $cate->field("gc_id,gc_name")->where(array("gc_parent_id"=>"0","gc_is_display"=>"1"))->order(array('gc_id'=>'asc'))->select();
		
		//是否存在
		if(empty($catedata)){
			$result["info"]   = "暂无分类";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]     = "请求成功";
			$result["status"]   = "10000";
			$result["catedata"] = $catedata;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 轮播图
     * 
     */
	public function appBanner(){
		//定义一个数组来储存数据
		$result = array();
		$bann   = D("Ad");
		$banner = $bann->field()->where(array("n_position"=>"banner"))->order(array('n_id'=>'desc'))->select();
		
		if(empty($banner)){
			$result["info"]     = "暂无数据";
			$result["status"]   = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["banner"] = $banner;
			print_r(json_encode($result));
			die;
		}
		// echo "<pre>";
		// print_r($result);
		// die;
	} 
	
	/**
     * 首页品牌
     * 
     */
	public function appIndexBrand(){
		//定义一个数组来储存数据
		$result = array();
		
		//横幅
		$bann   = D("Ad");
		$ad     = $bann->field()->where(array("n_position"=>"ppzq"))->find();
		
		//品牌信息
		$brand = D("GoodsBrand");
		$branddata = $brand->field("gb_id,gb_logo,gb_name,gb_letter,gb_order")->where(array("gb_display"=>"1"))->order("gb_id desc")->limit(40)->select();
		
		//是否存在
		if(empty($branddata)){
			$result["info"]   = "暂无品牌";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["branddata"] = $branddata;
			$result["adv"]       = $ad;
			print_r(json_encode($result));
			die;
		}
	}
	/**
     ** 更多品牌
     ** 
     */
	public function appBrandInfonation(){
		//定义一个数组来储存数据
		$result = array();
		
		//品牌信息
		$brand      = D("GoodsBrand");

		//品牌中心头部展示的20个品牌
		$branddata  = $brand->field("gb_id,gb_logo,gb_name")->where(array("gb_display"=>"1"))->order(array('gb_id'=>'asc'))->limit(20)->select();
		
		//查询字母品牌
		$groupbrand = $brand->field("gb_letter")->where(array("gb_display"=>"1"))->order('gb_letter asc')->group("gb_letter")->select();
		
		//将首字母转大写
		// foreach($branddata as $k=>$v){
		// 	$sentence = ucfirst($v["gb_name"]);
		// 	$branddata[$k]["orderz"] = $sentence;
		// }

		//以字母排序，所属于该字母的所有品牌
		foreach($groupbrand as $key=>$val){
			//品牌信息
			$bdlist = $brand->field("gb_id,gb_logo,gb_name")->where(array("gb_display"=>"1","gb_letter"=>$val["gb_letter"]))->order('gb_id asc')->select();
			$groupbrand[$key]["bdlist"] = $bdlist;
		}

		//是否存在品牌
		if(empty($branddata)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]       = "请求成功";
			$result["status"]     = "10000";
			$result["branddata"]  = $branddata;
			$result["groupbrand"] = $groupbrand;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     ** 品牌商品列表
     ** @Explain 带*号的参数为必传参数，不能为空
     ** @param string $bid  品牌ID
     ** @param string $page 分页    *
    **/
	public function appBrandGoodsList(){
		//定义一个数组来储存数据
		$result = array();
		//品牌ID
		$bid   = $_POST["bid"];
		$pages = $_POST["page"];
		//$bid = 2;
		
		//分页
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		//$gnums = M("GoodsInfo")->where(array("g_on_sale"=>"1","gb_id"=>$bid))->count();
		
		//是否存在品牌ID	
		if(empty($bid)){//品牌ID不存在，则默认是全部商品
			//所有商品
			$bfield = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,a.gb_id";

			$glist = M("GoodsInfo")->field()
							   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							   ->where(array("a.g_on_sale"=>"1"))
							   ->limit("{$startindex},{$limit}")
							   ->order(array('fx_goods_info.g_id'=>'desc'))
							   ->select();	
			
			if(empty($glist)){
				$result["info"]         = "暂无商品";
				$result["status"]       = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]         = "请求成功";
				$result["status"]       = "10000";
				$result["goodslist"]    = $glist;
				print_r(json_encode($result));
				die;
			}
		}else{
			//所有商品
			$bfield = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,a.gb_id";

			$glist = M("GoodsInfo")->field($bfield)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->where(array("a.g_on_sale"=>"1","a.gb_id"=>$bid))
							       ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();	
			
			if(empty($glist)){
				$result["info"]         = "暂无商品";
				$result["status"]       = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]         = "请求成功";
				$result["status"]       = "10000";
				$result["goodslist"]    = $glist;
				print_r(json_encode($result));
				die;
			}	
		}
	}
	
	/**
     * 今日特惠
     * 
     */
	public function appTodaySpecial(){
		//定义一个数组来储存数据
		$result = array();
		
		//当前时间，用于判断数据
		$time   = time();

		//横幅广告
		$bann   = D("Ad");
		$ad     = $bann->field()->where(array("n_position"=>"tmzq"))->find();
		
		//所有商品 ---------UNIX_TIMESTAMP(sp_start_time)  将开始时间转为时间戳来判断时间
		$spfield = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$glist = M('Spike')->field($spfield)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(5)
						   ->select();
		
		//是否存在商品
		if(empty($glist)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["goodslist"] = $glist;
			$result["adv"]       = $ad;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 今日特惠内页列表
     * 
     */
	public function appTodaySpecialList(){
		//定义一个数组来储存数据
		$result = array();
		
		//当前时间，用于判断数据
		$time   = time();
		
		//所有商品
		$spfield = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$glist = M('Spike')->field($spfield)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->select();
		
		//是否存在秒杀数据				   
		if(empty($glist)){
			$result["info"]         = "暂无数据";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]         = "请求成功";
			$result["status"]       = "10000";
			$result["goodslist"]    = $glist;
			print_r(json_encode($result));
			die;
		}
	}
	/**
     ** 秒杀活动详情
     ** @Explain 带*号的参数为必传参数，不能为空
     ** @param string $sp_id 秒伤活动ID  * 
     ** @param string $m_id 秒伤活动ID  *
     */
	public function appSpecialDetail(){
		//定义一个数组来储存数据
		$result = array();
		
		//秒杀活动ID
		$sp_id = $_POST["sp_id"];
		$m_id  = $_POST["m_id"];
		//$sp_id = "2";
		
		//是否存在参数
		if(empty($sp_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{

			//秒杀活动详情
			$spikeinfo = M('Spike')->field("sp_id,sp_price,g_id,sp_start_time,sp_end_time")->where("sp_id={$sp_id}")->find();
			
			//商品类型，用于加入购物车判断是什么类型商品
			//11:积分+金额兑换 8:预售 7:秒杀商品 6:自由搭配商品 5:团购商品，4:自由推荐商品,3:组合商品，2:赠品，1:积分商品，0:普通商品
			$spikeinfo["goods_type"] = "7";

			//秒杀商品详情
			$ginfofield  = "a.gb_id,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_picture,fx_goods_info.g_desc,p.pdt_id";
			$spgoodsinfo = M("GoodsInfo")->field($ginfofield)
			                             ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
			                             ->join("fx_goods_products as p on p.g_id=fx_goods_info.g_id")
			                             ->where(array("fx_goods_info.g_id"=>$spikeinfo["g_id"]))
			                             ->find();

			//商品相册
			$gpicsdata = M("GoodsPictures")->field("gp_id,gp_picture,gp_status")->where(array("g_id"=>$spikeinfo["g_id"],"gp_status"=>"1"))->select();
			
			//商品评论列表
			$gcomfield = "m_id,gcom_content,gcom_create_time";
			$gcommentdata = M("GoodsComments")->field($gcomfield)
			                                  ->where(array("g_id"=>$spikeinfo["g_id"]))
			                                  ->select();
			
			//循环出评论用户的头像
			foreach($gcommentdata as $k=>$v){
				$userdata = M("MembersFieldsInfo")->field()->where(array("u_id"=>$v["m_id"],"field_id"=>"20"))->find();
				$gcommentdata[$k]["u_pic"] = $userdata["content"];
			}  
			//统计评论总条数
			$commentcount = M("GoodsComments")->where(array("g_id"=>$spikeinfo["g_id"]))->count();
			$spgoodsinfo["commentcount"] = $commentcount;

			//商品规格
			$gattr = M('RelatedGoodsSpec')->field()->where(array("g_id"=>$spikeinfo["g_id"],"gs_id"=>"888"))->select();
			
			//商品属性
			$relgoodsattr = M("RelatedGoodsSpec")->field("fx_related_goods_spec.*,sg.gs_name")
			                                     ->join("fx_goods_spec as sg on sg.gs_id=fx_related_goods_spec.gs_id")
			                                     ->where(array("fx_related_goods_spec.g_id"=>$spikeinfo['g_id']))
			                                     ->select();

			//收藏记录
			$collectregs  = M("CollectGoods")->field()->where(array("g_id"=>$spikeinfo["g_id"],"m_id"=>$m_id))->find();
			//返回客户端判断是否收藏
			if(!empty($collectregs)){
				$spgoodsinfo["is_collect"] = "1";
			}else{
				$spgoodsinfo["is_collect"] = "0";
			}

			//推荐商品
			$glist   = M("Ad")->field()->where(array("n_position"=>"rcde"))->order("n_order asc")->select();
			foreach($glist as $keys=>$vals){
				$relgoodsinfo = M("GoodsInfo")->field("g_price,g_salenum")->where("g_id={$vals['n_gid']}")->find();
				$glist[$keys]["g_price"]   = $relgoodsinfo["g_price"];
				$glist[$keys]["g_salenum"] = $relgoodsinfo["g_salenum"];
			}

			$result["info"]         = "请求成功";
			$result["status"]       = "10000";
			$result["spikeinfo"]    = $spikeinfo;
			$result["goodsinfo"]    = $spgoodsinfo;
			$result["colorattr"]    = $gattr;
			$result["gpicsdata"]    = $gpicsdata;
			$result["gcommentdata"] = $gcommentdata;
			$result["relgoodsattr"] = $relgoodsattr;
			$result["relgoods"]     = $glist;
			print_r(json_encode($result));
			die;
		}
	} 
	
	/**
     * 明日特惠
     * 
     */
	public function appTomorrowSpecial(){
		//定义一个数组来储存数据
		$result = array();
		
		//当前时间戳
		$time   = time();
		
		//横幅
		$bann   = D("Ad");
		$ad     = $bann->field()->where(array("n_position"=>"tmzq"))->find();

		//所有商品 ---------UNIX_TIMESTAMP(sp_start_time)  将开始时间转为时间戳来判断时间
		$spfield = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$glist = M('Spike')->field($spfield)
						   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(5)
						   ->select();
		
		//是否存在数据
		if(empty($glist)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["goodslist"] = $glist;
			$result["adv"]       = $ad;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 明日特惠列表
     * 
     */
	public function appTomorrowSpecialList(){
		//定义一个数组来储存数据
		$result = array();

		//时间戳
		$time   = time();
		
		//所有商品
		$spfield = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$glist = M('Spike')->field($spfield)
						   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->select();
		
		//是否存在数据
		if(empty($glist)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["goodslist"] = $glist;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 热卖榜
     * 
     */
	public function appHotGoods(){
		//定义一个数组来储存数据
		$result = array();
		
		//横幅
		$bann   = D("Ad");
		$ad     = $bann->field()->where(array("n_position"=>"rxzq"))->find();
		
		//商品信息
		$ginfo = M("GoodsInfo");

		$gfield = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$glist = $ginfo->field($gfield)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(5)
					   ->select();
		
		//是否存在数据
		if(empty($glist)){
			$result["info"]   = "暂无商品";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["glist"]  = $glist;
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}
	}
	/**
     * 更多热卖榜
     * @Explain   带*号参数为必传参数，不能为空
     * @param string $pages 分页 *
     */
	public function appHotGoodsList(){
		//定义一个数组来储存数据
		$result = array();

		//分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 2;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		//所有热销商品
		$ginfo = M("GoodsInfo");
		$glist = $ginfo->field("g.g_id,g.g_sn,g.g_order,fx_goods_info.g_name,g.g_hot,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum")
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->limit("{$startindex},{$limit}")
					   ->order('g.g_order desc,g.g_id')
					   ->select();
		
		if(empty($glist)){
			$result["info"]         = "暂无数据";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]         = "请求成功";
			$result["status"]       = "10000";
			$result["goodslist"]    = $glist;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 新品专区
     * 
     */
	public function appDiscountGoods(){
		//定义一个数组来储存数据
		$result = array();
		
		//横幅
		$bann   = D("Ad");
		$ad     = $bann->field()->where(array("n_position"=>"xpzq"))->find();
		
		//商品信息
		$ginfo  = M("GoodsInfo");
		$gfield = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_market_price,fx_goods_info.g_salenum";
		$glist  = $ginfo->field($gfield)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(8)
					   ->select();

	    //商品信息
		if(empty($glist)){
			$result["info"]   = "暂无商品";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["glist"]  = $glist;
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}
	}
	/**
     * 新品专区列表
     * @Explain   带*号参数为必传参数，不能为空
     * @param string $pages 分页 *
     */
	public function appDiscountGoodsList(){
		//定义一个数组来储存数据
		$result = array();
		
		//分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		
		//所有商品
		$ginfo = M("GoodsInfo");
		$glist = $ginfo->field("g.g_id,g.g_sn,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum")
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->limit("{$startindex},{$limit}")
					   ->order('g.g_order desc,g.g_id')
					   ->select();
		
		if(empty($glist)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["goodslist"] = $glist;
			print_r(json_encode($result));
			die;
		}
	}
	
	/**
     * 心意特区
     * 
     */
	public function appMindGoods(){
		//定义一个数组来储存数据
		$result = array();
		
		//横幅
		$bann   = D("Ad");
		$glist  = $bann->field()->where(array("n_position"=>"tribe"))->order("n_id desc")->limit(8)->select();
		$ad     = $bann->field()->where(array("n_position"=>"qwbl"))->find();
		
		if(empty($glist)){
			$result["info"]   = "暂无商品";
			$result["status"] = "10001";
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["glist"]  = $glist;
			$result["adv"]    = $ad;
			print_r(json_encode($result));
			die;
		}
		// echo "<pre>";
		// print_r($result);
		// die;
	}
	/**
     * 心意特区列表
     * 
     */
	public function appMinGoodsList(){
		//定义一个数组来储存数据
		$result = array();
		
		//分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;	

		//所有商品
		$bann   = D("Ad");
		$glist  = $bann->field()->where(array("n_position"=>"tribe"))->limit("{$startindex},{$limit}")->order("n_id desc")->select();
		
		if(empty($glist)){
			$result["info"]         = "暂无数据";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["goodslist"] = $glist;
			print_r(json_encode($result));
			die;
		}
	}
	
	//商品列表
	public function appGoodsList(){
		//定义一个数组来储存数据
		$result = array();
		//分类ID
		$cid   = $_POST["cid"];
		$pages = empty($_POST["page"])?"1":$_POST["page"];

		//分页
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		$gnums = D("ViewGoods")->where(array("g_on_sale"=>"1","gc_id"=>$cid,"g_status"=>"1"))->count();
		
		if(empty($cid)){
			//所有商品
			$glist = M("GoodsInfo")->field("a.g_sn,fx_goods_info.*")
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->where(array("a.g_on_sale"=>"1","g_status"=>"1"))
							       ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
			if(empty($glist)){
				$result["info"]         = "暂无商品";
				$result["status"]       = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]         = "请求成功";
				$result["status"]       = "10000";
				$result["goodslist"]    = $glist;
				print_r(json_encode($result));
				die;
			}
		}else{
			//对应分类商品
			$glist = D('ViewGoods')->field()
				                   ->where(array("g_on_sale"=>"1","gc_id"=>$cid,"g_status"=>"1"))
								   ->limit("{$startindex},{$limit}")
								   ->order(array("g_id"=>"desc"))
								   ->select();
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]      = "请求成功";
				$result["status"]    = "10000";
				$result["goodslist"] = $glist;
				print_r(json_encode($result));
				die;
			}	
		}
	}
	
	/**
     * 商品详情
     * 
     */
	public function appGoodsInfo(){		
		//定义一个数组来储存数据
		$result = array();

		//参数
		$gid       = $_REQUEST["g_id"];
		$m_id      = $_REQUEST["m_id"];
		
		//
		$ginfo     = M("GoodsInfo");
		$goods     = M("Goods");
		$gpicsinfo = M("GoodsPictures");
		$gcomment  = M("GoodsComments");
		$members   = M("MembersFieldsInfo");
		$colgoods  = M("CollectGoods");
		$rg        = M("RelatedGoodsSpec");
		$relgoods  = M();


		$categoryinfo = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array("g_id"=>$gid))->find();

		$where = 1;
		if($categoryinfo["gc_id"]){
			$where.= " and fx_videos_info.v_category_id=".$categoryinfo["gc_id"];
		}

        $M = M("videos_info");

		$videoList = $M->field('fx_videos_info.v_id,fx_videos_info.v_name,fx_videos_info.v_code,fx_videos_info.v_picture,fx_videos_teacher.t_name,fx_videos_teacher.t_photo')
					   ->join('left join fx_videos_teacher on fx_videos_info.v_teacher_id = fx_videos_teacher.t_id')
					   ->limit(6)
					   ->where($where)
					   ->select();
		foreach ($videoList as $key => $value) {
			if(!$value['v_picture']){
				$videoList[$key]['v_picture'] = 'http://cdn.dvr.aodianyun.com/pic/long-vod/u/30278/images/'.$value['v_code'].'/145/80';
			}
			if($value['t_photo']){
				$videoList[$key]['t_photo'] = 'http://www.caizhuangguoji.com/Public/Uploads/teacher/'.$value['t_photo'];
			}
			$videoList[$key]['v_url'] = 'http://30278.long-vod.cdn.aodianyun.com/u/30278/m3u8/adaptive/'.$value['v_code'].'.m3u8';	
		}
		
		//商品信息
		$ginfofield = "g_id,g_name,g_price,g_market_price,g_desc,g_picture,g_salenum,g_stock,g_create_time";
		$gdinfo = $ginfo->field($ginfofield)->where(array("g_id"=>$gid))->find();
		$ginfoa = $goods->field()->where(array("g_id"=>$gid))->find();
		$gdinfo["goods_type"] = "0";
		//货品ID
		$pdt_id = M("GoodsProducts")->field("pdt_id")->where(array("g_id"=>$gid))->find();

		$gb_id = $ginfoa["gb_id"];
		$field = 'gb_id,gb_name,gb_logo';
        $brandinfo = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_id"=>$gb_id))->find();
		
		//商品销售属性，颜色分类
		$colorfield = "fx_related_goods_spec.pdt_id,fx_related_goods_spec.g_id,fx_related_goods_spec.gsd_id,fx_related_goods_spec.gsd_aliases,fx_related_goods_spec.gsd_picture,a.pdt_sale_price,a.pdt_market_price,a.pdt_stock";
		$colorcat   = $rg->field($colorfield)->join("fx_goods_products as a on a.pdt_id=fx_related_goods_spec.pdt_id")
							                 ->where("fx_related_goods_spec.g_id={$gid} AND fx_related_goods_spec.gs_id=888")
								             ->select();
		$glist   = M("Ad")->field()->where(array("n_position"=>"rcde"))->order("n_order asc")->select();
		foreach($glist as $keys=>$vals){
			$relgoodsinfo = M("GoodsInfo")->field("g_price,g_salenum")->where("g_id={$vals['n_gid']}")->find();
			$glist[$keys]["g_price"]   = $relgoodsinfo["g_price"];
			$glist[$keys]["g_salenum"] = $relgoodsinfo["g_salenum"];
		}
		
		//商品相册
		$gpicsdata    = $gpicsinfo->field("gp_id,gp_picture")->where(array("g_id"=>$gid))->select();
		
		//商品评论列表
		$gcommentdata = $gcomment->field()->where(array("g_id"=>$gid))->select();
		
		//统计评论总条数
		$commentcount = $gcomment->where(array("g_id"=>$gid))->count();
		$gdinfo["commentcount"] = $commentcount;
		$gdinfo["pdt_id"]       = $pdt_id["pdt_id"];
		
		//收藏记录
		$collectregs  = $colgoods->field()->where(array("g_id"=>$gid,"m_id"=>$m_id))->find();
		
		//返回客户端判断是否收藏
		if(!empty($collectregs)){
			$gdinfo["is_collect"] = "1";
		}else{
			$gdinfo["is_collect"] = "0";
		}
		
		//商品扩展属性
		$sql="SELECT rg.*,sg.gs_name FROM fx_related_goods_spec as rg JOIN fx_goods_spec as sg ON rg.gs_id=sg.gs_id WHERE g_id={$gid}";
		$relgoodsattr = $relgoods->query($sql);
		
		foreach($gcommentdata as $k=>$v){
			$userdata = $members->field()->where(array("u_id"=>$v["m_id"],"field_id"=>"20"))->find();
			$gcommentdata[$k]["u_pic"] = $userdata["content"];
		}
		
		if(empty($gid)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]         = "请求成功";
			$result["status"]       = "10000";
			$result["goodsinfo"]    = $gdinfo;
			$result["gpicsdata"]    = $gpicsdata;
			$result["gcommentdata"] = $gcommentdata;
			$result["relgoodsattr"] = $relgoodsattr;
			$result["relgoodslist"] = $glist;
			$result["colorcate"]    = $colorcat;
			$result["brandinfo"]    = $brandinfo;
			$result["videoList"]    = $videoList;
		    print_r(json_encode($result));
		    die;
		}
	}
	/**
     * 分类中心
     * 
     */
	public function appCateInfonation(){
		//定义一个数组来储存数据
		$result = array();
		
		$gcate    = M("GoodsCategory");
		$catelist = $gcate->field("gc_id,gc_name,gc_pic_url")->where(array("gc_is_display"=>"1","gc_parent_id"=>"0","gc_level"=>"0"))->order(array("gc_id asc"))->select();
		
		if(empty($catelist)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			foreach($catelist as $k=>$v){
				$subcatelist                 = $gcate->field("gc_id,gc_name,gc_pic_url")->where(array("gc_is_display"=>"1","gc_parent_id"=>$v["gc_id"]))->order(array("gc_id asc"))->select();
				$catelist[$k]["subcatelist"] = $subcatelist;
			}
			$result["info"]     = "请求成功";
			$result["status"]   = "10000";
			$result["catelist"] = $catelist;
			print_r(json_encode($result));
			die;
		}
	}
		
	/**
     * 加入收藏
     * @Explain   带*号参数为必传参数，不能为空
     * @param string $m_id 用户ID *
     * @param string $g_id 商品ID *
     */
	public function appAddCollect(){
		//定义一个数组来储存数据
		$result = array();
		
		//参数 用户ID    商品ID
		$m_id = $_POST["m_id"];
		$g_id = $_POST["g_id"];
		
		//验证参数
		if(empty($m_id) || empty($g_id)){
			$result["info"]         = "参数错误";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$data["m_id"]     = $m_id;
			$data["g_id"]     = $g_id;
			$data["add_time"] = date("Y-m-d H:i:s",time());
			
			//是否收藏过该商品
			$reg = M("CollectGoods")->where(array("m_id"=>$m_id,"g_id"=>$g_id))->find();
			
			//是否有收藏记录
			if(!empty($reg)){
				$result["info"]   = "已收藏过";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}else{

				//添加收藏记录
				$colgoods = M("CollectGoods")->data($data)->add();
				
				//是否有返回添加记录
				if(!empty($colgoods)){
					$result["info"]   = "收藏成功";
					$result["status"] = "10000";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]   = "收藏失败";
					$result["status"] = "10004";
					print_r(json_encode($result));
					die;
				}
			}
		}
	}
	
	/** 加入购物车
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $m_id   用户ID  *
	 ** @param-----string  $g_id   商品ID  *
	 ** @param-----string  $pdt_id 货品ID *
	 ** @param-----string  $num    数量    
	 ** @param-----string  $type   商品类型：0-普通商品，1-积分商品，2-赠品，3-组合商品，4-自由推荐商品，5-团购商品，6-自由搭配商品，7-秒杀商品，8-预售商品，11-积分+金额兑换
	**/
	public function appAddCart(){
		//定义一个数组来储存数据
		$result = array();
		
		//传递的参数
		$m_id   = $_POST["m_id"];
		$g_id   = $_POST["g_id"];
		$pdt_id = $_POST["pdt_id"];
		$num    = !empty($_POST["num"])?$_POST["num"]:"1";
		$gtype  = !empty($_POST["type"])?$_POST["type"]:"0";
		//$m_id = "2";
		//$g_id = "117";
		
		//购物车关键字,用户的购物车唯一标识
		$key  = base64_encode("mycart".$m_id);
		
		//货品信息
		$gpfield = "pdt_id,g_id";
		$row     = M("GoodsProducts")->field($gpfield)->where(array("pdt_id" => $pdt_id))->find();
		
		//查询条件
		$where = array('fx_goods_products.pdt_id' => $pdt_id);
		
		//查询字段
		$field = array('fx_goods.g_is_combination_goods', 'fx_goods.g_gifts','fx_goods.g_id');
		
		//商品品信息
		$goods_info = D('GoodsProducts')->GetProductList($where, $field);
		
		//商品类型
		if($goods_info['g_is_combination_goods']=="1"){//组合商品
            $good_type = "3";
		}else{
			$good_type = "0";
		}

		//积分商品不能购买
		if($goods_info['g_gifts']=="1") {
			$result["info"]         = "赠品不能购买";
			$result["status"]       = "10003";
			print_r(json_encode($result));
			die;
		}

		//购物车记录
		$cartlog  = M("Mycart")->where(array("key" => $key))->find();
		
		//购物车商品信息
		$datas = unserialize(urldecode($cartlog['value']));
		
		//当前要加入购物车的商品信息
		$arr[$pdt_id]=array(
			"pdt_id" => $pdt_id,
			"g_id"   => $g_id,
			"type"   => $good_type,
			"num"    => $num,
		);

		//将商品处理
		$str = urlencode(serialize($arr));
		$now = date('Y-m-d H:i:s');
		
		//必传参数
		if(empty($m_id) || empty($g_id)){
			$result["info"]         = "参数错误";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//是否存在当前用户的购物车信息
			if(!empty($cartlog)){

				//是否购物车存在相同属性的商品
				if($arr[$pdt_id]["pdt_id"] == $datas[$pdt_id]["pdt_id"]){
					
					//更新原有相同属性商品的数量
					$datas[$pdt_id] = array(
						"pdt_id" => $datas[$row["pdt_id"]]["pdt_id"],
						"g_id"   => $datas[$row["pdt_id"]]["g_id"],
						"type"   => "0",
						"num"    => $datas[$row["pdt_id"]]["num"]+$num,
					);

					//将商品重新处理
					$str                        = urlencode(serialize($datas));
					$update_data['value']       = $str;
					$update_data['modify_time'] = $now;

					//更新购物车数据 
					$updLog = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
					
					//验证是否更新成功
					if(empty($updLog)){
						$result["info"]         = "加入购物车失败";
						$result["status"]       = "10003";
						print_r(json_encode($result));
						die;	
					}else{
						$result["info"]         = "加入购物车成功";
						$result["status"]       = "10000";
						print_r(json_encode($result));
						die;
					}
				}else{
					//遍历原有的购物车信息，向购物车新增商品信息
					foreach($datas as $vals){
						$arr[$vals["pdt_id"]] = array(
							"pdt_id" => $vals["pdt_id"],
							"g_id"	 =>	$vals["g_id"],
							"type"   => "0",
							"num"    => $vals["num"],
						);
					}
					//将数据处理
					$str = urlencode(serialize($arr));
					$update_data['value']=$str;
					$update_data['modify_time']=$now; 
					
					//更新数据
					$addLog = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
					
					//验证是否添加成功
					if(empty($addLog)){
						$result["info"]   = "加入购物车失败";
						$result["status"] = "10003";
						print_r(json_encode($result));
						die;	
					}else{
						$result["info"]   = "加入购物车成功";
						$result["status"] = "10000";
						print_r(json_encode($result));
						die;
					}
				}
			}else{

				//购物车数据
				$data["key"]   = $key;
				$data["value"] = $str;
				$data['create_time'] = $now;
				$data['modify_time'] = $now;
				
				//购物车无该用户记录，添加数据库
				$addLog = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
				
				//验证是否添加成功
				if(empty($addLog)){
					$result["info"]   = "加入购物车失败";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;	
				}else{
					$result["info"]   = "加入购物车成功";
					$result["status"] = "10000";
					print_r(json_encode($result));
					die;
				}
			}	
		}
	}
	/** 我的购物车
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $m_id   用户ID  *
	**/
	public function appMyCart(){
		//定义一个数组来储存数据
		$result = array();
		
		//用户ID
		//$m_id = "2174";
		$m_id = $_POST["m_id"];
		
		//购物车的关键词，用户购物车的唯一标识
		$key  = base64_encode("mycart".$m_id);
		
		//是否存在该用户的购物车信息
		$Cart = M('mycart');
		$row  = $Cart->where(array("key" => $key))->find();
		
		//购物车商品信息
		$data = unserialize(urldecode($row['value']));
	
		//参数是否存在
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//是否有购物车记录
			if($row){
				//重新定义一个数组来装处理过的购物车信息
				$arr = array();
				
				//客户端无法解析以pdt_id作为键值的json数据，重新改变键值
				$num = 0;
				
				//由于客户端转化不了json,进一步处理$data数据里的键值
				foreach($data as $k=>$v){
					/**
					 **购物车里的商品信息
					**/
					//查询字段
					$gfield = "fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,p.pdt_sale_price,c.gc_id,c.gc_name";
					
					//查询条件
					$where  = "p.g_id={$v['g_id']} AND p.pdt_id={$v['pdt_id']}";

					//商品信息
					$ginfo  = D('GoodsInfo')->field($gfield)
					                        ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
					                        ->join("fx_goods_products as p on p.g_id=fx_goods_info.g_id")
					                        ->join("fx_view_goods as c on c.g_id=p.g_id")
					                        ->where($where)
					                        ->order(array("a.g_id"=>"desc"))
					                        ->find();
					
					//商品的所属分类信息
					$cateinfo = M("GoodsCategory")->field("gc_id,gc_name")->where(array("gc_id"=>$ginfo["gc_id"]))->find();

					//查询字段
					$rgsfield = "fx_related_goods_spec.gsd_aliases,fx_related_goods_spec.pdt_id";
					
					//查询条件
					$rgswhere = array(
						"fx_related_goods_spec.g_id"   => $v["g_id"],
						"fx_related_goods_spec.pdt_id" => $v["pdt_id"],
						"fx_related_goods_spec.gs_id"  => "888",
					);

					//商品规格颜色属性
					$colorinfo = D('RelatedGoodsSpec')->field($rgsfield)
					                                  ->join("fx_goods_products as p on p.g_id=fx_related_goods_spec.g_id")
					                                  ->where($rgswhere)
					                                  ->find();
					
					//返回客户端数据
					$ProductInfo = D('GoodsProducts')->getProductInfo('',$v['pdt_id']);
                    $v['pdt_stock'] = $ProductInfo[0]['pdt_stock'];
                    
					$data[$k]["goodsinfo"]  = $ginfo;
					$data[$k]["total"]      = $v["num"]*$ginfo["pdt_sale_price"];
					$carttotal             += $data[$k]["total"];
					$arr[$num]              = $v;
					$arr[$num]["goodsinfo"] = $ginfo;
					$arr[$num]["goodsinfo"]["colorname"] = $colorinfo["gsd_aliases"];
					$arr[$num]["goodsinfo"]["num"]       = $v["num"];
					//$arr[$num]["gc_id"]     = $cateinfo["gc_id"];
					//$arr[$num]["gc_name"]   = $cateinfo["gc_name"];
					$num ++; 
				}
				
				if(!empty($arr)){
					$result["info"]      = "请求成功";
					$result["status"]    = "10000";
					$result["cartdata"]  = $arr;
					$result["carttotal"] = $carttotal;
					print_r(json_encode($result));
					die;	
				}else{
					$result["info"]      = "购物车为空";
					$result["status"]    = "10003";
					print_r(json_encode($result));
					die;	
				}
			}else{
				$result["info"]   = "购物车为空";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}	
		}
	}
	
	/** 删除购物车
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $m_id     用户ID  *
	 ** @param-----string  $pdt_id   货品ID  * 
	**/
	public function appDelCart(){
		//定义一个数组来储存数据
		$result = array();
		
		//参数
		//$m_id   = "2171";
		//$pdt_id = "139";
		$m_id   = $_POST["m_id"];
		$pdt_id = $_POST["pdt_id"];
		
		$Cart = M('mycart');
		$key  = base64_encode("mycart".$m_id);
		
		//是否存在该用户的购物车信息
		$row  = $Cart->where(array("key" => $key))->find();
		
		//参数是否存在
		if(empty($m_id) || empty($pdt_id)){
			$result["info"]         = "参数错误";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//是否有记录
			if($row){
				//购物车商品信息
				$data = unserialize(urldecode($row['value']));
				
				//根据传递的$pdt_id判断购物车商品信息里是否存在
				if(!empty($data[$pdt_id])){
					//从购物车的商品信息里面删除该商品信息
					unset($data[$pdt_id]);
					
					//将删除后的数据处理放回购物车
					$str = urlencode(serialize($data));
					$update_data["key"]   = $key;
					$update_data["value"] = $str;
					$update_data['modify_time'] = date('Y-m-d H:i:s');
					
					//返回删除记录
					$delLog = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
					
					//是否有删除记录
					if(empty($delLog)){
						$result["info"]   = "删除失败";
						$result["status"] = "10003";
						print_r(json_encode($result));
						die;	
					}else{
						$result["info"]   = "删除成功";
						$result["status"] = "10000";
						print_r(json_encode($result));
						die;
					}
				}else{
					$result["info"]   = "购物车无该商品信息";
					$result["status"] = "10004";
					print_r(json_encode($result));
					die;
				}
			}else{
				$result["info"]   = "删除失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}	
		}
	}

	/** 更新购物车中的商品数量
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $m_id     用户ID  *
	 ** @param-----string  $pdt_id   货品ID  * 
	 ** @param-----string  $num      数量    *   
	**/
	public function appUpdateCart(){
		//定义一个数组来储存数据
		$result = array();
		
		//参数
		$m_id   = $_POST["m_id"];
		$pdt_id = $_POST["pdt_id"];
		$num    = $_POST["num"];
		
		//购物车关键词
		$Cart = M('mycart');
		$key  = base64_encode("mycart".$m_id);
		
		//是否存在该用户的购物车信息
		$row  = $Cart->where(array("key" => $key))->find();
		
		//参数是否存在
		if(empty($m_id) || empty($pdt_id)){
			$result["info"]         = "参数错误";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//是否购物车有记录
			if($row){
				//购物车商品信息
				$data = unserialize(urldecode($row['value']));
				
				//验证传递过来的货品ID是否存在购物车
				if(!empty($data[$pdt_id])){
					$data[$pdt_id]["num"] = $num;
				}

				//将更新商品数量处理放回购物车
				$str = urlencode(serialize($data));
				$update_data["key"]   = $key;
				$update_data["value"] = $str;
				$update_data['modify_time'] = date('Y-m-d H:i:s');
				
				//更新购物车信息，返回记录
				$updLog = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
				
				//是否有返回记录
				if(empty($updLog)){
					$result["info"]         = "更新失败";
					$result["status"]       = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]         = "更新成功";
					$result["status"]       = "10000";
					print_r(json_encode($result));
					die;
				}
			}else{
				$result["info"]         = "更新失败";
				$result["status"]       = "10003";
				print_r(json_encode($result));
				die;
			}	
		}
	}
	
	/** 特惠活动内页(暂时未使用此接口)
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $page     分页 *   
	**/
	public function appPreferActivity(){
		//定义一个数组来储存数据
		$result = array();
		
		//分页
		$limit = 4;
		$pages = $_POST["page"];
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		//商品总数
		$gnums = D("ViewGoods")->where(array("g_on_sale"=>"1","gc_id"=>$cid,"g_status"=>"1"))->count();

		//所有商品
		$glist = M("GoodsInfo")->field("a.g_sn,fx_goods_info.*")
						       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
						       ->where(array("a.g_on_sale"=>"1","g_status"=>"1"))
						       ->order(array('fx_goods_info.g_id'=>'desc'))
						       ->select();
		if(empty($glist)){
			$result["info"]         = "暂无商品";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]         = "请求成功";
			$result["status"]       = "10000";
			$result["goodslist"]    = $glist;
			print_r(json_encode($result));
			die;
		}
	}

	/** 团购活动内页
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $page   分页 *   
	**/
	public function appGroupbuyActivity(){
		//定义一个数组来储存数据
		$result = array();
		
        //分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		//团购活动商品
		$gyfield  = "fx_groupbuy.gp_id,fx_groupbuy.gp_title,fx_groupbuy.g_id,fx_groupbuy.gp_picture,fx_groupbuy.gp_price,fx_groupbuy.gp_number,fx_groupbuy.gp_now_number,fx_groupbuy.gp_tiered_pricing_type,a.g_price";
		$groupbuy = M("Groupbuy")->field($gyfield)
								 ->join("fx_goods_info as a on a.g_id=fx_groupbuy.g_id")
						         ->where("is_active=1 AND deleted=0")
						         ->limit("{$startindex},{$limit}")
						         ->order(array('gp_id'=>'desc'))
						         ->select();

		//处理每个团购商品的团购价
		foreach($groupbuy as $k=>$v){
			if($v["gp_tiered_pricing_type"]=="1"){
				$groupbuy[$k]["gp_g_price"] = sprintf("%.3f",($v["g_price"] - $v["gp_price"]));	
			}else{
				$groupbuy[$k]["gp_g_price"] = sprintf("%.3f",($v["g_price"] * ($v["gp_price"]/100)));
			}
		}				         

		//团购商品信息				         
		if(empty($groupbuy)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]     = "请求成功";
			$result["status"]   = "10000";
			$result["groupbuy"] = $groupbuy;
			print_r(json_encode($result));
			die;
		}
	}
	
	/** 团购活动详情
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $gp_id   团购活动ID *   
	**/
	public function appGroupbuyActivityInfo(){
		//定义一个数组来储存数据
		$result = array();
		
		//团购表ID
		$gp_id = $_POST["gp_id"];
		//$gp_id = "2";
		
		//参数是否存在
		if(empty($gp_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//团购详情
			$grfield  = "gp_id,gp_title,g_id,gp_picture,gp_price,gp_number,gp_now_number,gp_tiered_pricing_type";
			$groupbuy = M("Groupbuy")->field($grfield)->where(array("gp_id"=>$gp_id))->find();
			

			//商品ID
			$gid      = $groupbuy["g_id"];
			
			//商品详情
			$gdfield = "a.g_id,a.gb_id,fx_goods_info.g_name,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_desc";
			$goods   = M("GoodsInfo")->field($gdfield)->join("fx_goods as a on a.g_id=fx_goods_info.g_id")->where(array("a.g_id"=>$gid))->find();
			$pdt_id  = M("GoodsProducts")->field("pdt_id")->where(array("g_id"=>$gid))->find();
			//$goodsinfo = M("ViewGoods")->field()->where(array("g_id"=>$gid))->find();
			//11:积分+金额兑换 8:预售 7:秒杀商品 6:自由搭配商品 5:团购商品，4:自由推荐商品,3:组合商品，2:赠品，1:积分商品，0:普通商品
			$goods["goods_type"] = "5";
			$goods["pdt_id"]     = $pdt_id["pdt_id"];

			//根据团购方式来计算团购价格
			if($groupbuy["gp_tiered_pricing_type"]=="1"){
				$groupbuy["gp_g_price"] = $goods["g_price"] - $groupbuy["gp_price"];
				$groupbuy["g_price"]    = $goods["g_price"];
			}else{
				$groupbuy["gp_g_price"] = $goods["g_price"] * ($groupbuy["gp_price"]/100);
				$groupbuy["g_price"]    = $goods["g_price"];
			}

			//推荐商品
			$goodslist = M("Ad")->field()->where(array("n_position"=>"rcde"))->order("n_order desc")->select();
			
			//商品评论
			$gcomment  = M("GoodsComments")->field()->where(array("g_id"=>$gid))->select();
			
			foreach($gcomment as $k=>$v){
				$userdata = M("MembersFieldsInfo")->field()->where(array("u_id"=>$v["m_id"],"field_id"=>"20"))->find();
				$gcomment[$k]["u_pic"] = $userdata["content"];
			}
			
			//统计评论总条数
			$comcounts = M("GoodsComments")->where(array("g_id"=>$gid))->count();
			$groupbuy["comcounts"] =$comcounts;
			
			//商品颜色分类
			$colorcat  = M("RelatedGoodsSpec")->field()->where(array("g_id"=>$gid,"gs_id"=>"888"))->select();
			
			//商品扩展属性
			$sql="SELECT rg.*,sg.gs_name FROM fx_related_goods_spec as rg JOIN fx_goods_spec as sg ON rg.gs_id=sg.gs_id WHERE g_id={$gid}";
			$relgoodsattr = M()->query($sql);
		
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["groupbuy"]  = $groupbuy;
			$result["goodsinfo"] = $goods;
			$result["goodslist"] = $goodslist;
			$result["gcomment"]  = $gcomment;
			$result["gcolorcat"] = $colorcat;
			$result["goodsattr"] = $relgoodsattr;
			//$result["goods"] = $goods;
			print_r(json_encode($result));
			die;	
		}
		// echo "<pre>";
		// print_r($result);
		// die;
	}
	/**
	 ** 抽奖活动内页
	 ** 
	 **
	**/
	public function appDrawActivity(){
		//定义一个数组来储存数据
		$result = array();
		
		//抽奖活动商品
		$draw = M("Lottery")->field()
						   ->where(array("l_status"=>"1","is_deleted"=>"0"))
						   ->order(array('l_id'=>'desc'))
						   ->select();
		if(empty($draw)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["draw"]   = $draw;
			print_r(json_encode($result));
			die;
		}
		// echo "<pre>";
		// print_r($result);
		// die;
	}
	
	/** 预售活动内页
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $page   分页 *   
	**/
	public function appPresaleActivity(){
		//定义一个数组来储存数据
		$result = array();
		
		//分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		//预售活动商品
		$pfield  = "fx_presale.p_id,fx_presale.p_title,fx_presale.g_id,fx_presale.p_picture,fx_presale.p_price,fx_presale.p_number,fx_presale.p_now_number,fx_presale.p_tiered_pricing_type,fx_presale.p_start_time,fx_presale.p_end_time,a.g_price";
		$presale = M("Presale")->field($pfield)
							   ->join("fx_goods_info as a on a.g_id=fx_presale.g_id")
						       ->where(array("is_active"=>"1","p_deleted"=>"0"))
						       ->limit("{$startindex},{$limit}")
						       ->order(array('p_id'=>'desc'))
						       ->select();
		
		//预售价格	
		foreach ($presale as $key => $value) {
			if($value["p_tiered_pricing_type"]=="1"){
				$presale[$key]["p_g_price"] = sprintf("%.3f",($value["g_price"] - $value["p_price"]));;
			}else{
				$presale[$key]["p_g_price"] = sprintf("%.3f",($value["g_price"] * ($value["p_price"]/100)));
			}
		}

		//是否存在
		if(empty($presale)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]    = "请求成功";
			$result["status"]  = "10000";
			$result["presale"] = $presale;
			print_r(json_encode($result));
			die;
		}
	}
	
	/** 预售活动详情
	 ** @Explain   带*号参数为必传参数，不能为空
	 ** @param-----string  $p_id   预售活动ID * 
	**/
	public function appPresaleActivityInfo(){
		//定义一个数组来储存数据
		$result = array();
		
		//预售表ID
		$p_id = $_POST["p_id"];
		//$gp_id = "2";
		
		if(empty($p_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//预售详情
			$pfield  = "p_id,p_title,g_id,p_picture,p_start_time,p_end_time,p_deposit_price,p_number,p_per_number,is_active,p_tiered_pricing_type,p_now_number,p_deleted,p_create_time,p_price"; 
			$presale = M("Presale")->field($pfield)->where(array("p_id"=>$p_id))->find();
			
			//商品ID
			$gid     = $presale["g_id"];
			
			//商品详情
			$gdfield = "a.g_id,a.gb_id,fx_goods_info.g_name,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_desc";
			$goods   = M("GoodsInfo")->field($gdfield)->join("fx_goods as a on a.g_id=fx_goods_info.g_id")->where(array("a.g_id"=>$gid))->find();
			//$goodsinfo = M("ViewGoods")->field()->where(array("g_id"=>$gid))->find();
			//11:积分+金额兑换 8:预售 7:秒杀商品 6:自由搭配商品 5:团购商品，4:自由推荐商品,3:组合商品，2:赠品，1:积分商品，0:普通商品
			$goods["goods_type"] = "8";

			//根据团购方式来计算团购价格
			if($presale["p_tiered_pricing_type"]=="1"){
				$presale["p_g_price"] = $goods["g_price"] - $presale["p_price"];
				$presale["g_price"]    = $goods["g_price"];
			}else{
				$presale["p_g_price"] = $goods["g_price"] * ($presale["p_price"]/100);
				$presale["g_price"]    = $goods["g_price"];
			}

			//推荐商品
			$goodslist = M("Ad")->field()->where(array("n_position"=>"rcde"))->order("n_order desc")->select();
			
			//商品评论
			$gcomfield = "gcom_id,m_id,g_id,gcom_title,gcom_content,gcom_mbname,gcom_order_id";
			$gcomment  = M("GoodsComments")->field($gcomfield)->where(array("g_id"=>$gid))->select();
			
			foreach($gcomment as $k=>$v){
				$userdata = M("MembersFieldsInfo")->field()->where(array("u_id"=>$v["m_id"],"field_id"=>"20"))->find();
				$gcomment[$k]["u_pic"] = $userdata["content"];
			}
			
			//统计评论总条数
			$comcounts = M("GoodsComments")->where(array("g_id"=>$gid))->count();
			$presale["comcounts"] =$comcounts;
			
			//商品颜色分类
			$colorcat  = M("RelatedGoodsSpec")->field()->where(array("g_id"=>$gid,"gs_id"=>"888"))->select();
			
			//商品扩展属性
			$sql="SELECT rg.*,sg.gs_name FROM fx_related_goods_spec as rg JOIN fx_goods_spec as sg ON rg.gs_id=sg.gs_id WHERE g_id={$gid}";
			$relgoodsattr = M()->query($sql);
		
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["presale"]   = $presale;
			$result["goodsinfo"] = $goods;
			$result["goodslist"] = $goodslist;
			$result["gcomment"]  = $gcomment;
			$result["gcolorcat"] = $colorcat;
			$result["goodsattr"] = $relgoodsattr;
			//$result["goods"] = $goods;
			print_r(json_encode($result));
			die;		
		}
	}
	
	/**
	 ** 秒杀活动内页
	 ** @Explain  带*号必传参数，不能为空
	 ** @param    $page   分页 *
	**/
	public function appSeckillActivity(){
		//定义一个数组来储存数据
		$result = array();
		
		//分页
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = 4;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		//秒杀活动商品
		$spfield = "fx_spike.sp_id,fx_spike.sp_title,fx_spike.sp_picture,fx_spike.g_id,fx_spike.sp_number,fx_spike.sp_now_number,fx_spike.sp_price,fx_spike.sp_start_time,fx_spike.sp_end_time,fx_spike.sp_tiered_pricing_type,fx_spike.sp_create_time,a.g_price";
		$spike   = M("Spike")->field($spfield)
							 ->join("fx_goods_info as a on a.g_id=fx_spike.g_id")
						     ->where(array("sp_status"=>"1"))
						     ->limit("{$startindex},{$limit}")
						     ->order(array('sp_id'=>'desc'))
						     ->select();
		
		//处理秒杀价格
		foreach ($spike as $key => $value) {
			if($value["sp_tiered_pricing_type"]=="1"){
				$spike[$key]["sp_g_price"] = sprintf("%.3f",($value["g_price"] - $value["sp_price"]));
			}else{
				$spike[$key]["sp_g_price"] = $value["g_price"] * ($value["sp_price"]/100);
			}
		}

		//数据是否存在
		if(empty($spike)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "请求成功";
			$result["status"] = "10000";
			$result["spike"]  = $spike;
			print_r(json_encode($result));
			die;
		}
	}
	
	//美妆知识
	public function appBeautyArt(){
		//定义一个数组来储存数据
		$result = array();
		$where  = array("a_status"=>"1");
		$cid    = $_POST["cid"];
		
		if(empty($cid)){
			$artList = M('Article')->field()->where($where)->where("cat_id!=0")->order(array("a_id"=>"desc"))->select();
		}else{
			$artList = M('Article')->field()->where(array("cat_id"=>$cid,"a_status"=>"1"))->order(array("a_id"=>"desc"))->select();
		}
		$artCatList = M('ArticleCat')->field()->order(array("cat_id"=>"asc"))->select();
			
		if(empty($artList)){
			$result["info"]   = "暂无数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]       = "请求成功";
			$result["status"]     = "10000";
			$result["artlist"]    = $artList;
			$result["artcatlist"] = $artCatList;
			print_r(json_encode($result));
			die;
		}
		// echo "<pre>";
		// print_r($artList);
		// die;
	}
	//美妆知识详情
	public function appBeautyArtInfo(){
		//定义一个数组来储存数据
		$result = array();
		$aid    = $_POST["aid"];
			
		if(empty($aid)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$artInfo = M('Article')->field()->where(array("a_id"=>$aid))->find();
			$result["info"]       = "暂无数据";
			$result["status"]     = "10002";
			$result["artinfo"]    = $artInfo;
			print_r(json_encode($result));
			die;
		}
		// echo "<pre>";
		// print_r($artList);
		// die;
	}
	
	/**
	 ** 商品排序
	 ** @param $q 排序条件
	 **
	**/
	public function appGoodsScreen(){
		//定义一个数组来储存数据
		$result = array();
		
		$order  = $_POST["q"];
		
		$groupbrand = M("GoodsBrand")->field("gb_letter")->where(array("gb_display"=>"1"))->order('gb_letter asc')->group("gb_letter")->select();
		foreach($groupbrand as $key=>$gb){
			$brandlist = M("GoodsBrand")->field()->where(array("gb_display"=>"1","gb_letter"=>$gb["gb_letter"]))->order('gb_id asc')->select();
			$groupbrand[$key]["brands"] = $brandlist;
		}
		
		$brandlist = M("GoodsBrand")->field()->where(array("gb_display"=>"1"))->group("")->select();
		$catelist   = M("GoodsCategory")->field()->where(array("gc_is_display"=>"1","gc_parent_id"=>"0","gc_status"=>"1"))->select();
		
		foreach($catelist as $k=>$v){
			$subcatelist = M("GoodsCategory")->field()->where(array("gc_is_display"=>"1","gc_parent_id"=>$v["gc_id"],"gc_status"=>"1"))->select();
			$catelist[$k]["subcatelist"] = $subcatelist;
		}
		
		if($order=="g_salenum"){
			$glist = M("GoodsInfo")->field()
							   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							   ->where(array("a.g_on_sale"=>"1"))
							   ->order(array('fx_goods_info.g_salenum'=>'desc'))
							   ->select();					
		}else if($order=="g_price"){
			$glist = M("GoodsInfo")->field()
							   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							   ->where(array("a.g_on_sale"=>"1"))
							   ->order(array('fx_goods_info.g_price'=>'asc'))
							   ->select();					
		}else{
			$glist = M("GoodsInfo")->field()
							   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							   ->where(array("a.g_on_sale"=>"1"))
							   ->order(array('a.g_id'=>'desc'))
							   ->select();
		}
		
		if(empty($glist)){
			$result["info"]   = "暂无商品";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]      = "请求成功";
			$result["status"]    = "10000";
			$result["glist"]     = $glist;
			$result["brandlist"] = $groupbrand;
			$result["catelist"]  = $catelist;
			print_r(json_encode($result));
			die;
		}		
		// echo "<pre>";
		// print_r($result);
		// die;
	}
	
	/**
	 ** 商品搜索框搜索
	 ** @param $keywords 商品标题关键词
	 **
	**/
	public function appGoodsSearch(){
		//定义一个数组来储存数据
		$result = array();
		
		$keywords = $_POST["keywords"];
		//$keywords = "SNP";
		
		if(empty($keywords)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//搜索条件
			$field = "b.g_id,b.g_name,b.g_picture,b.g_price,b.g_salenum,b.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//搜索SQL
			$sql   = "SELECT {$field} FROM fx_goods_info as b LEFT JOIN fx_goods as a ON a.g_id=b.g_id LEFT JOIN fx_view_goods as c ON c.g_id=a.g_id WHERE b.g_name LIKE '%{$keywords}%' AND a.g_on_sale=1 GROUP BY a.g_id";
			$glist = M()->query($sql);	
			
			if(empty($glist)){
				$result["info"]   = "暂无搜索结果";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}
	}
	/**
	 ** 商品筛选条件搜索
	 ** @param $gb_id 品牌ID
	 ** @param $gc_id 分类ID
	 ** @param $gx_id 功效ID
	 ** @param $g_price 价格区间
	 **
	**/	
	public function appGoodsSreenSearch(){
		//定义一个数组来储存数据
		$result = array();
		
		$gb_id   = $_POST["gb_id"];
		$gc_id   = $_POST["gc_id"];
		$gx_id   = $_POST["gx_id"];
		$g_price = $_POST["g_price"];
		$pages   = empty($_POST["page"])?"1":$_POST["page"];
		
		//分页
		$limit = 6;
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		
		//$gb_id   = "33";
		//$gc_id   = "46";
		// $gx_id   = "";
		// $g_price = "";
		if($g_price=="1"){
			$minPrice = 0;
			$maxPrice = 100;
		}else if($g_price=="2"){
			$minPrice = 100;
			$maxPrice = 200;
		}else if($g_price=="3"){
			$minPrice = 200;
			$maxPrice = 300;
		}else if($g_price=="4"){
			$minPrice = 300;
			$maxPrice = 400;
		}else{
			$minPrice = 400;
			$maxPrice = 1000;
		}
		
		if(!empty($gb_id) && empty($gc_id) && empty($gx_id) && empty($g_price)){//A
			
			//查询条件
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1 AND a.gb_id={$gb_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("fx_goods_info.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
						   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				//echo "<pre>";
				//print_r($result);
				die;
			}
			
		}else if(!empty($gb_id) && !empty($gc_id) && empty($gx_id) && empty($g_price)){//AB
			
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1 AND a.gb_id={$gb_id} AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				//echo "<pre>";
				//print_r($result);
				die;
			}
		}else if(!empty($gb_id) && empty($gc_id) && !empty($gx_id) && empty($g_price)){//AC
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("fx_goods_info.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
							   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(!empty($gb_id) && empty($gc_id) && empty($gx_id) && !empty($g_price)){//AD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.g_on_sale=1 AND a.gb_id={$gb_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
							   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				// echo "<pre>";
				// print_r($result);
				die;
			}
		}else if(!empty($gb_id) && !empty($gc_id) && !empty($gx_id) && empty($g_price)){//ABC
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1 AND a.gb_id={$gb_id} AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
							       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
							   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(!empty($gb_id) && !empty($gc_id) && empty($gx_id) && !empty($g_price)){//ABD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.gb_id={$gb_id} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();
			
			
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				//echo "<pre>";
				//print_r($result);
				die;
			}
		}else if(!empty($gb_id) && !empty($gc_id) && !empty($gx_id) && !empty($g_price)){//ABCD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.gb_id={$gb_id} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && !empty($gc_id) && empty($gx_id) && empty($g_price)){//B
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();    

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				// echo "<pre>";
				// print_r($result);
				die;
			}
		}else if(empty($gb_id) && !empty($gc_id) && !empty($gx_id) && empty($g_price)){//BC
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select(); 
			echo "9";
			die;				   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && !empty($gc_id) && empty($gx_id) && !empty($g_price)){//BD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();			   
			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && !empty($gc_id) && !empty($gx_id) && !empty($g_price)){//BCD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.gb_id={$gb_id} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && empty($gc_id) && !empty($gx_id) && empty($g_price)){//C
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.gb_id={$gb_id} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && empty($gc_id) && !empty($gx_id) && !empty($g_price)){//CD
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.gb_id={$gb_id} AND a.g_on_sale=1 AND c.gc_id={$gc_id}";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}else if(empty($gb_id) && empty($gc_id) && empty($gx_id) && !empty($g_price)){//D
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "fx_goods_info.g_price<={$maxPrice} AND fx_goods_info.g_price>={$minPrice} AND a.g_on_sale=1";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}	
		}else{
			//查询字段
			$field = "fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum,fx_goods_info.g_market_price,a.gb_id,c.gc_id,c.gc_name,c.gt_name,c.gb_name";
			//查询条件
			$where = "a.g_on_sale=1";
			//所有商品
			$glist = M("GoodsInfo")->field($field)
								   ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
							       ->join("fx_view_goods as c on c.g_id=fx_goods_info.g_id")
							       ->where($where)
							       ->group("c.g_id")
								   ->limit("{$startindex},{$limit}")
							       ->order(array('fx_goods_info.g_id'=>'desc'))
							       ->select();

			if(empty($glist)){
				$result["info"]   = "暂无商品";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "请求成功";
				$result["status"] = "10000";
				$result["glist"]  = $glist;
				print_r(json_encode($result));
				die;
			}
		}
	}
	/**
	 ** APP 我的订单
	 **
	 **
	**/
	public function appMyorderLists(){
		//定义一个数组来储存数据
		$result = array();
		
		/**
		 **参数：
		 **     会员ID   
		 **		订单支付状态 0.未支付，1.已支付，2.处理中，3部分支付
		 **		订单发货状态 0.待发货，1.仓库准备，2.已发货，3.缺货，4.退货
		**/
		
		$m_id = $_REQUEST["m_id"];
		///$m_id = "2174";
		// $o_pay_status   = "0";
		// $oi_ship_status = "0";
		$o_pay_status   = $_REQUEST["o_pay_status"];
		$oi_ship_status = $_REQUEST["oi_ship_status"];
		$is_evaluate = $_REQUEST["is_evaluate"];		
		
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(!empty($o_pay_status)){
				//$sql = "SELECT * FROM fx_orders as a join fx_orders_items as b on a.o_id=b.o_id WHERE a.m_id={$m_id}";
				//$orderlist = M()->query($sql);
				//订单信息
				$orderlist = M("Orders")->field()
										->join("fx_orders_items as a on a.o_id=fx_orders.o_id")
										->where("fx_orders.m_id={$m_id} AND fx_orders.o_pay_status={$o_pay_status}")
										->order("a.o_id desc")
										->select();
				foreach ($orderlist as $key => $value) {
					$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value['g_id']}")->find();
					$orderlist[$key]["goodsinfo"] = $ginfo;
				}
				
				if(empty($orderlist)){
					$result["info"]   = "暂无数据";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]      = "请求成功";
					$result["status"]    = "10000";
					$result["orderlist"] = $orderlist;
					print_r(json_encode($result));
					die;
				}
			}else if(!empty($oi_ship_status)){
				//$sql = "SELECT * FROM fx_orders as a join fx_orders_items as b on a.o_id=b.o_id WHERE a.m_id={$m_id}";
				//$orderlist = M()->query($sql);
				//订单信息
				$orderlist = M("Orders")->field()
										->join("fx_orders_items as a on a.o_id=fx_orders.o_id")
										->where("fx_orders.m_id={$m_id} AND a.oi_ship_status={$oi_ship_status}")
										->order("a.o_id desc")
										->select();
				
				foreach ($orderlist as $key => $value) {
					$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value['g_id']}")->find();
					$orderlist[$key]["goodsinfo"] = $ginfo;
				}

				if(empty($orderlist)){
					$result["info"]   = "暂无数据";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]      = "请求成功";
					$result["status"]    = "10000";
					$result["orderlist"] = $orderlist;
					print_r(json_encode($result));
					die;
				}
			}else{
				//$sql = "SELECT * FROM fx_orders as a join fx_orders_items as b on a.o_id=b.o_id WHERE a.m_id={$m_id}";
				//$orderlist = M()->query($sql);
				//订单信息
				$orderlist = M("Orders")->field()
										->join("fx_orders_items as a on a.o_id=fx_orders.o_id")
										->where("fx_orders.m_id={$m_id}")
										->order("a.o_id desc")
										->select();

				foreach ($orderlist as $key => $value) {
					$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value['g_id']}")->find();
					$orderlist[$key]["goodsinfo"] = $ginfo;
				}

				if(empty($orderlist)){
					$result["info"]   = "暂无数据";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]      = "请求成功";
					$result["status"]    = "10000";
					$result["orderlist"] = $orderlist;
					print_r(json_encode($result));
					//echo "<pre>";
					//print_r($result);
					die;
				}
			}
		}
	}
	
	/**
	 ** 商品评价
	 **
	 **
	**/
	public function appGoodsAddComment(){
		//定义一个数组来储存数据
		$result = array();
		
		/**
		 **参数：会员ID、商品ID、评论标题、评论内容
		**/
		$m_id         = $_POST["m_id"];
		$g_id         = $_POST["g_id"];
		$o_id         = $_POST["o_id"];
		$gcom_title   = $_POST["gcom_title"];
		$gcom_content = $_POST["gcom_content"];

		//会员信息
		$members = M("Members")->field("m_real_name,m_email,m_qq,m_mobile")->where("m_id={$m_id}")->find();
		
		$data["m_id"]             = $m_id;
		$data["g_id"]             = $g_id;
		$data["gcom_order_id"]    = $o_id;
		$data["gcom_title"]       = $gcom_title;
		$data["gcom_content"]     = $gcom_content;
		$data["gcom_mbname"]      = empty($members["m_real_name"])?"匿名":$members["m_real_name"];
		$data["gcom_email"]       = empty($members["m_email"])?"暂无":$members["m_email"];
		$data["gcom_phone"]       = empty($members["m_mobile"])?"":$members["m_mobile"];
		$data["gcom_qq"]          = empty($members["m_qq"])?"":$members["m_qq"];
		$data["gcom_ip_address"]  = $this->getIp();
		$data["gcom_create_time"] = date("Y-m-d H:i:s",time());

		$upload = new UploadFile();// 实例化上传类
		$upload->maxSize  = 3145728 ;// 设置附件上传大小
		$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM

		$file_extend_indexa = "gcom_picsa";
		$tmp_filesa = $_FILES["gcom_picsa"];
		if(!empty($tmp_filesa) && !empty($tmp_filesa['name']) && !empty($tmp_filesa['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd');
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_indexa] = $tmp_filesa;
			//$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10004";
				$result["filesa"] = $_FILES;
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_urla = '/Public/Uploads/'.CI_SN.'/comments/'.$m_id.'/'.date('Ymd'). $info[0]['savename'];
			}
		}

		$file_extend_indexb = "gcom_picsb";
		$tmp_filesb = $_FILES["gcom_picsb"];

		if(!empty($tmp_filesb) && !empty($tmp_filesb['name']) && !empty($tmp_filesb['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd');
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_indexb] = $tmp_filesb;

			//$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10004";
				$result["filesb"] = $_FILES;
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_urlb = '/Public/Uploads/'.CI_SN.'/comments/'.$m_id.'/'.date('Ymd'). $info[0]['savename'];
			}
		}
		

		
		$file_extend_indexc = "gcom_picsc";
		$tmp_filesc = $_FILES["gcom_picsc"];
		
		if(!empty($tmp_filesc) && !empty($tmp_filesc['name']) && !empty($tmp_filesc['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/';
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_indexc] = $tmp_filesc;
			//$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10004";
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_urlc = '/Public/Uploads/'.CI_SN.'/comments/'.$m_id.'/'.date('Ymd'). $info[0]['savename'];
			}
		}
		
		$file_extend_indexd = "gcom_picsd";
		$tmp_filesd = $_FILES["gcom_picsd"];
		
		if(!empty($tmp_filesd) && !empty($tmp_filesd['name']) && !empty($tmp_filesd['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd');
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_indexd] = $tmp_filesd;
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10004";
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_urld = '/Public/Uploads/'.CI_SN.'/comments/'.$m_id.'/'.date('Ymd'). $info[0]['savename'];
			}
		}
		
		$file_extend_indexe = "gcom_picse";
		$tmp_filese = $_FILES["gcom_picse"];
		
		if(!empty($tmp_filese) && !empty($tmp_filese['name']) && !empty($tmp_filese['tmp_name'])){
			$path = './Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd');
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/comments/'.$m_id.'/'.date('Ymd').'/', 0777, true);
			}
			$upfiles[$file_extend_indexe] = $tmp_filese;
			//$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload($path,$upfiles)) {
				$result["info"] = "上传失败";
				$result["status"] = "10004";
				print_r(json_encode($result));
				die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$tmp_files_urle = '/Public/Uploads/'.CI_SN.'/comments/'.$m_id.'/'.date('Ymd'). $info[0]['savename'];
			}
		}
		
		$data["gcom_pics"]     = $tmp_files_urla.",".$tmp_files_urlb.",".$tmp_files_urlc.",".$tmp_files_urld.",".$tmp_files_urle;
		
		if(empty($m_id) || empty($g_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$rows = M("GoodsComments")->data($data)->add();
			if(empty($rows)){
				$lastSql = M("GoodsComments")->getLastSql();
				$result["info"]   = "评论失败";
				$result["status"] = "10003";
				$result["lastSql"] = $lastSql;
				$result["files"] = $_FILES;
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "评论成功";
				$result["status"] = "10000";
				$result["files"] = $_FILES;
				print_r(json_encode($result));
				die;
			}
		}
	}
	
	 /**
     * @param 获取客户端IP地址 
     */
    public function getIp() {
        $ip = "";
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
	
	/**
	 ** 立即购买
	 **
	**/
	public function appDirectBuy(){
		//定义一个数组来储存数据
		$result = array();
		
		//参数 用户ID    商品ID  货品ID
		$m_id   = $_POST["m_id"];
		$g_id   = $_POST["g_id"];
		$pdt_id = $_POST["pdt_id"];
		$num    = $_POST["num"];
		// $m_id   = 2124;
		// $g_id   = 17;
		// $pdt_id = 40;
		// $num    = 1;
		//购物车关键字
		$key  = base64_encode("mycart".$m_id);
		//货品信息
		$row  = M("GoodsProducts")->where(array("pdt_id" => $pdt_id))->find();
		//商品品信息
		$where = array('fx_goods_products.pdt_id' => $pdt_id);
		$field = array('fx_goods.g_is_combination_goods', 'fx_goods.g_gifts','fx_goods.g_id');
		$goods_info = D('GoodsProducts')->GetProductList($where, $field);
		if($goods_info['g_is_combination_goods']=="1"){//组合商品
            $good_type = "3";
		}else{
			$good_type = "0";
		}
		if($goods_info['g_gifts']=="1") {
			$result["info"]         = "赠品不能购买";
			$result["status"]       = "10003";
			print_r(json_encode($result));
			die;
		}
		//购物车记录
		$cartlog  = M("Mycart")->where(array("key" => $key))->find();
		$datas = unserialize(urldecode($cartlog['value']));
		
		$arr[$pdt_id]=array(
			"pdt_id" => $pdt_id,
			"g_id"   => $g_id,
			"type"   => $good_type,
			"num"    => $num,
		);
		
		//客户端需要的数据
		$backdata = array(array(
			"pdt_id" => $pdt_id,
			"g_id"   => $g_id,
			"type"   => $good_type,
			"num"    => $num,
		));
		
		$str = urlencode(serialize($arr));
		$now = date('Y-m-d H:i:s');
		
		if(empty($m_id) || empty($g_id)){
			$result["info"]         = "参数错误";
			$result["status"]       = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(!empty($cartlog)){
				if($arr[$pdt_id]["pdt_id"] == $datas[$pdt_id]["pdt_id"]){
						$datas[$pdt_id] = array(
							"pdt_id" => $datas[$row["pdt_id"]]["pdt_id"],
							"g_id"   => $datas[$row["pdt_id"]]["g_id"],
							"type"   => "0",
							"num"    => $datas[$row["pdt_id"]]["num"]+1,
						);
					$str = urlencode(serialize($datas));
					
					$update_data['value']=$str;
					$update_data['modify_time']=$now; 
					$rows = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
										
					if(empty($rows)){
						$result["info"]         = "加入购物车失败";
						$result["status"]       = "10003";
						print_r(json_encode($result));
						die;
					}else{
						$result["info"]     = "加入购物车成功";
						$result["status"]   = "10000";
						$result["cartdata"] = $backdata;
						print_r(json_encode($result));
						die;	
					}
				}else{
					foreach($datas as $vals){
						$arr[$vals["pdt_id"]] = array(
							"pdt_id" => $vals["pdt_id"],
							"g_id"	 =>	$vals["g_id"],
							"type"   => "0",
							"num"    => $vals["num"],
						);
					}
					$str = urlencode(serialize($arr));
					
					$update_data['value']=$str;
					$update_data['modify_time']=$now; 
					$rows = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
					
					if(empty($rows)){
						$result["info"]     = "加入购物车失败";
						$result["status"]   = "10003";
						print_r(json_encode($result));
						die;
					}else{
						$result["info"]     = "加入购物车成功";
						$result["status"]   = "10000";
						$result["cartdata"] = $backdata;
						print_r(json_encode($result));
						die;	
					}
				}
			}else{
				$data["key"]   = $key;
				$data["value"] = $str;
				$data['create_time'] = $now;
				$data['modify_time'] = $now;
				
				$rows = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->add($data);

				if(empty($rows)){
					$result["info"]         = "加入购物车失败";
					$result["status"]       = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]         = "加入购物车成功";
					$result["status"]       = "10000";
					$result["cartdata"] = $backdata;
					print_r(json_encode($result));
					die;	
				}
			}	
		}
	}
	
	/**
	 ** 提交订单
	 **
	**/
	public function appOrderAdd(){
		//定义一个数组来储存数据
		$result = array();
		
		/**
		 ** 参数: 
		 ** @param string  $m_id 用户ID
		 ** @param string  $ra_id 地址ID
		 **	@param array() $cartdata 购物车的商品信息
		**/
		$m_id     = $_POST["m_id"];
		$ra_id    = $_POST["ra_id"];
		$c_id    = $_POST["c_id"];
		// $c_id    = "104";
		$pdt_ids   = explode(',', $_POST["pdt_id"]);

		//$m_id     = 1909;
		//$ra_id    = 751;
		//$pdt_ids   = explode(',', '24,87');
		// $g_id     = $_POST["g_id"];
		// $num      = $_POST["num"];
		// $type     = $_POST["type"];
		//$cartdata = $_POST["cartdata"];
		// $m_id  = "2124";
		// $ra_id = "496";


		//地址信息
		$addrinfo  = M("ReceiveAddress")->field()->where("ra_id={$ra_id}")->find();
		//区/县
		$area     = M("CityRegion")->where(array("cr_id"=>$addrinfo["cr_id"]))->find();
		//市
		$town     = M("CityRegion")->where(array("cr_id"=>$area["cr_parent_id"]))->find();
		//省份
		$province = M("CityRegion")->where(array("cr_id"=>$town["cr_parent_id"]))->find();	



		//购物车关键字
		$key  = base64_encode("mycart".$m_id);
		//购物车记录
		$cartlog  = M("Mycart")->where(array("key" => $key))->find();
		$datas = unserialize(urldecode($cartlog['value']));

		//var_dump($datas);

		//sort($datas);
		$data["o_goods_all_price"] = 0;
		$data["o_all_price"]       = 6;
		$data["o_goods_all_saleprice"] = 0;		
		foreach ($pdt_ids as $k => $value) {
			//商品信息
			$field     = "fx_goods_products.*,fx_goods_info.g_name,fx_goods.gt_id,fx_goods.g_sn";
			$joina     = "fx_goods_info on fx_goods_info.g_id=fx_goods_products.g_id";
			$joinb     = "fx_goods on fx_goods.g_id=fx_goods_products.g_id";
			$where     = "fx_goods_products.g_id={$datas[$value]['g_id']} AND fx_goods_products.pdt_id={$value}";
			$goodsinfo[$k] = M("GoodsProducts")->field($field)
										   ->join($joina)
										   ->join($joinb)
										   ->where($where)
										   ->find();			# code...

			$data["o_goods_all_price"] = $data["o_goods_all_price"]+$goodsinfo[$k]["pdt_sale_price"]*$datas[$value]["num"];
			$data["o_all_price"]       = $data["o_all_price"]+$goodsinfo[$k]["pdt_sale_price"]*$datas[$value]["num"];
			$data["o_goods_all_saleprice"] = $data["o_goods_all_saleprice"]+$goodsinfo[$k]["pdt_sale_price"]*$datas[$value]["num"];
		}
		if (!empty($c_id)) {
			$field = "c_money";
			$coupon = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_id = {$c_id}")
										->find();
            $data["o_all_price"] -= $coupon["c_money"];
            $data["o_goods_discount"] = $coupon["c_money"];
            $couponData["c_is_use"] = "1";
			$couponData["c_used_id"]  = $m_id;
			$success = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->where(array("c_id"=>$c_id))->save($couponData);
		}

		//订单信息
		$data["o_id"] = date('YmdHis') . rand(1000, 9999);
		$data["m_id"] = $m_id;
		$data["o_payment"] = "2";
		$data["o_cost_freight"]    = "6";
		$data["o_receiver_name"]   = $addrinfo["ra_name"];
		$data["o_receiver_mobile"]   = $addrinfo["ra_mobile_phone"];
		$data["o_receiver_telphone"] = $addrinfo["ra_phone"];
		$data["o_receiver_state"]    = $province["cr_name"];
		$data["o_receiver_city"]     = $town["cr_name"];
		$data["o_receiver_county"]   = $area["cr_name"];
		$data["o_receiver_address"]  = $addrinfo["ra_detail"];
		$data["ra_id"]               = $ra_id;
		$data["o_receiver_zipcode"]  = $addrinfo["ra_post_code"];
		$data["lt_id"] = "1";
		$data["o_create_time"]       = date("Y-m-d H:i:s");
		$data["o_update_time"]       = date("Y-m-d H:i:s");
		$data["o_source"]            = "APP";
		
		$reg = M("Orders")->data($data)->add();
		$sql = M("Orders")->getLastSql();
		if(empty($reg)){
			$result["info"]         = "订单创建失败";
			$result["sql"]         = $sql;
			$result["status"]       = "10003";
			print_r(json_encode($result));
			die;
		}else{
			//订单创建成功，订单详情插入订单详情表
			foreach ($pdt_ids as $k => $value) {
				$oidata["o_id"]   = $data["o_id"];
				$oidata["g_id"]   = $datas[$value]["g_id"];
				$oidata["pdt_id"] = $datas[$value]["pdt_id"];
				$oidata["gt_id"]  = $goodsinfo[$k]["gt_id"];
				$oidata["g_sn"]   = $goodsinfo[$k]["g_sn"];
				$oidata["oi_g_name"]      = $goodsinfo[$k]["g_name"];
				$oidata["oi_cost_price"]  = $goodsinfo[$k]["pdt_cost_price"];
				$oidata["pdt_sale_price"] = $goodsinfo[$k]["pdt_sale_price"];
				$oidata["oi_price"] = $goodsinfo[$k]["pdt_sale_price"];
				$oidata["oi_nums"]  = $datas[$value]["num"];
				$oidata["pdt_sn"]   = $goodsinfo[$k]["pdt_sn"];
				$oidata["oi_type"]  = $datas[$value]["type"];
				$oidata["oi_create_time"] = date("Y-m-d H:i:s");
				//var_dump($oidata);
				
			    $rows = M("OrdersItems")->data($oidata)->add();
				
				if(empty($rows)){
					$result["info"]         = "订单创建失败";
					$result["status"]       = "10001";
					print_r(json_encode($result));
					die;
				}
				//订单创建成功，删除购物车该商品
				if(!empty($value)){
					unset($value);
				}
				$str = urlencode(serialize($value));
				$update_data["key"]   = $key;
				$update_data["value"] = $str;
				$update_data['modify_time'] = date("Y-m-d H:i:s",time());
				
				//更新购物车
				$UpdCart = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
			}
			


				
				//返回订单信息给客户端
				$OrdersData = array(
					"o_id"              => $data["o_id"],
					"o_goods_all_price" => $data["o_goods_all_price"],
					"o_all_price"       => $data["o_all_price"],
				);

				$result["info"]       = "订单创建成功";
				$result["status"]     = "10000";
				$result["ordersdata"] = $OrdersData;
				print_r(json_encode($result));
				die;	
		}	


		exit();
		








		$cartdata = Array(
			"pdt_id" => $datas[$pdt_id]["pdt_id"],
			"g_id"   => $datas[$pdt_id]["g_id"],
			"type"   => $datas[$pdt_id]["type"],
			"num"    => $datas[$pdt_id]["num"],
		);

		
		//商品信息
		$field     = "fx_goods_products.*,fx_goods_info.g_name,fx_goods.gt_id,fx_goods.g_sn";
		$joina     = "fx_goods_info on fx_goods_info.g_id=fx_goods_products.g_id";
		$joinb     = "fx_goods on fx_goods.g_id=fx_goods_products.g_id";
		$where     = "fx_goods_products.g_id={$cartdata['g_id']} AND fx_goods_products.pdt_id={$cartdata['pdt_id']}";
		$goodsinfo = M("GoodsProducts")->field($field)
									   ->join($joina)
									   ->join($joinb)
									   ->where($where)
									   ->find();
		
		//订单信息
		$data["o_id"] = date('YmdHis') . rand(1000, 9999);
		$data["m_id"] = $m_id;
		$data["o_payment"] = "2";
		$data["o_goods_all_price"] = $goodsinfo["pdt_sale_price"];
		$data["o_all_price"]       = $goodsinfo["pdt_sale_price"]*$cartdata["num"]+"6";
		$data["o_cost_freight"]    = "6";
		$data["o_receiver_name"]   = $addrinfo["ra_name"];
		$data["o_receiver_mobile"]   = $addrinfo["ra_mobile_phone"];
		$data["o_receiver_telphone"] = $addrinfo["ra_phone"];
		$data["o_receiver_state"]    = $province["cr_name"];
		$data["o_receiver_city"]     = $town["cr_name"];
		$data["o_receiver_county"]   = $area["cr_name"];
		$data["o_receiver_address"]  = $addrinfo["ra_detail"];
		$data["ra_id"]               = $ra_id;
		$data["o_receiver_zipcode"]  = $addrinfo["ra_post_code"];
		$data["lt_id"] = "1";
		$data["o_goods_all_saleprice"] = $goodsinfo["pdt_sale_price"];
		$data["o_create_time"]       = date("Y-m-d H:i:s");
		$data["o_update_time"]       = date("Y-m-d H:i:s");
		$data["o_source"]            = "APP";
		
		$reg = M("Orders")->data($data)->add();
		if(empty($reg)){
			$result["info"]         = "订单创建失败";
			$result["status"]       = "10003";
			print_r(json_encode($result));
			die;
		}else{
			//订单创建成功，订单详情插入订单详情表
			$oidata["o_id"]   = $data["o_id"];
			$oidata["g_id"]   = $cartdata["g_id"];
			$oidata["pdt_id"] = $cartdata["pdt_id"];
			$oidata["gt_id"]  = $goodsinfo["gt_id"];
			$oidata["g_sn"]   = $goodsinfo["g_sn"];
			$oidata["oi_g_name"]      = $goodsinfo["g_name"];
			$oidata["oi_cost_price"]  = $goodsinfo["pdt_cost_price"];
			$oidata["pdt_sale_price"] = $goodsinfo["pdt_sale_price"];
			$oidata["oi_price"] = $goodsinfo["pdt_sale_price"];
			$oidata["oi_nums"]  = $cartdata["num"];
			$oidata["pdt_sn"]   = $goodsinfo["pdt_sn"];
			$oidata["oi_type"]  = $cartdata["type"];
			$oidata["oi_create_time"] = date("Y-m-d H:i:s");
			
		    $rows = M("OrdersItems")->data($oidata)->add();
			
			if(empty($rows)){
				$result["info"]         = "订单创建失败";
				$result["status"]       = "10001";
				print_r(json_encode($result));
				die;
			}else{

				//订单创建成功，删除购物车该商品
				if(!empty($datas[$pdt_id])){
					unset($datas[$pdt_id]);
				}
				$str = urlencode(serialize($datas));
				$update_data["key"]   = $key;
				$update_data["value"] = $str;
				$update_data['modify_time'] = date("Y-m-d H:i:s",time());
				
				//更新购物车
				$UpdCart = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
				
				//返回订单信息给客户端
				$OrdersData = array(
					"o_id"              => $data["o_id"],
					"o_goods_all_price" => $data["o_goods_all_price"],
					"o_all_price"       => $data["o_all_price"],
				);

				$result["info"]       = "订单创建成功";
				$result["status"]     = "10000";
				$result["ordersdata"] = $OrdersData;
				print_r(json_encode($result));
				die;	
			}
		}	
		// echo "<pre>";
		// print_r($result);
		// die;
	}
	
	/**
	 ** 购物车选中商品结算=》返回地址购买
	 **
	**/
	public function appGoodsBuy(){
		//定义一个数组来储存数据
		$result = array();
		
		//用户ID
		$m_id     = $_POST["m_id"];
		//$m_id     = 2164;
		//地址ID
		$ra_id    = $_POST["ra_id"];
		//$ra_id    = 591;
		//将选中的购物车商品pdt_id字符串转成数组
		$ary_pid = explode(",",$_POST["pdt_id"]);
		//$strs = "42";
		//$ary_pid = explode(",",$strs);
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if(empty($_POST["pdt_id"])){
				$result["info"]   = "请选择商品";
				$result["status"] = "10004";
				print_r(json_encode($result));
				die;	
			}else{
				//取出该用户的购物车信息
				$Cart = M('mycart');
				$key  = base64_encode("mycart".$m_id);
				$row  = $Cart->where(array("key" => $key))->find();
				//购物车商品信息
				$data = unserialize(urldecode($row['value']));

				//验证选中购物车的商品信息
				$ary_cart_check = array();
				if(!empty($ary_pid) && is_array($ary_pid)){
		            foreach($ary_pid as $k=>$pid){
		                foreach($data as $keys=>$carts){
		                    if($carts['pdt_id'] == $pid) $ary_cart_check[$k] = $data[$keys];
		                }
		            }
		            if(empty($ary_cart_check)) $ary_cart_check = $data;
		        }else{
		            $ary_cart_check = $data;
		        }

		        //地址信息
				$addrinfo = M("ReceiveAddress")->field()->where("ra_id={$ra_id}")->find();
				//区/县
				$area     = M("CityRegion")->where(array("cr_id"=>$addrinfo["cr_id"]))->find();
				//市
				$town     = M("CityRegion")->where(array("cr_id"=>$area["cr_parent_id"]))->find();
				//省份
				$province = M("CityRegion")->where(array("cr_id"=>$town["cr_parent_id"]))->find();
				

				//商品信息
				//商品类型：0-普通商品，1-积分商品，2-赠品，3-组合商品，4-自由推荐商品，5-团购商品，6-自由搭配商品，7-秒杀商品，8-预售商品，11-积分+金额兑换
				$gdarr = array();
				foreach ($ary_cart_check as $kk => $vv) {
					//商品信息

					if($vv["type"]=="5"){//团购商品

					}else if($vv["type"]=="7"){//秒杀商品

					}else if($vv["type"]=="8"){//预售商品

					}else{
						$field     = "fx_goods_products.pdt_id,fx_goods_products.g_id,fx_goods.gt_id,fx_goods.g_sn,fx_goods_info.g_name,fx_goods_products.pdt_cost_price,fx_goods_products.pdt_sale_price,fx_goods_info.g_price,fx_goods_products.pdt_sn";
						$joina     = "fx_goods_info on fx_goods_info.g_id=fx_goods_products.g_id";
						$joinb     = "fx_goods on fx_goods.g_id=fx_goods_products.g_id";
						$where     = "fx_goods_products.g_id={$vv['g_id']} AND fx_goods_products.pdt_id={$vv['pdt_id']}";
						$goodsinfo = M("GoodsProducts")->field($field)
													   ->join($joina)
													   ->join($joinb)
													   ->where($where)
													   ->find();
					}
					$gdarr[$kk]                         = $goodsinfo;
					$gdarr[$kk]["num"]        = $vv["num"];
					$gdarr[$kk]["goods_type"] = $vv["type"];
					$gdarr[$kk]["count_price"]	= $goodsinfo["pdt_sale_price"]*$vv["num"];		   
					$countprice +=$goodsinfo["pdt_sale_price"]*$vv["num"];
				}

				//订单信息
				$odata["o_id"] = date('YmdHis') . rand(1000, 9999);
				$odata["m_id"] = $m_id;
				$odata["o_payment"] = "2";
				$odata["o_goods_all_price"] = $countprice;
				$odata["o_all_price"]       = $countprice+"6";
				$odata["o_cost_freight"]    = "6";
				$odata["o_receiver_name"]   = $addrinfo["ra_name"];
				$odata["o_receiver_mobile"]   = $addrinfo["ra_mobile_phone"];
				$odata["o_receiver_telphone"] = $addrinfo["ra_phone"];
				$odata["o_receiver_state"]    = $province["cr_name"];
				$odata["o_receiver_city"]     = $town["cr_name"];
				$odata["o_receiver_county"]   = $area["cr_name"];
				$odata["o_receiver_address"]  = $addrinfo["ra_detail"];
				$odata["ra_id"]               = $ra_id;
				$odata["o_receiver_zipcode"]  = $addrinfo["ra_post_code"];
				$odata["lt_id"] = "1";
				$odata["o_goods_all_saleprice"] = $countprice;
				$odata["o_create_time"]       = date("Y-m-d H:i:s");
				$odata["o_update_time"]       = date("Y-m-d H:i:s");
				$odata["o_source"]            = "APP";

				//是否选择了地址
				if(empty($ra_id)){
		        	$result["info"]   = "请选择收货地址";
					$result["status"] = "10005";
					print_r(json_encode($result));
					die;	
		        }

				//插入订单
				$addLog = M("Orders")->add($odata);

				if(empty($addLog)){
					$result["info"]   = "订单创建失败";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					
					//多个商品，分别单个加入订单详情
					foreach ($gdarr as $gga) {
						//订单创建成功，订单详情插入订单详情表
						$oidata["o_id"]   = $odata["o_id"];
						$oidata["g_id"]   = $gga["g_id"];
						$oidata["pdt_id"] = $gga["pdt_id"];
						$oidata["gt_id"]  = $gga["gt_id"];
						$oidata["g_sn"]   = $gga["g_sn"];
						$oidata["oi_g_name"]      = $gga["g_name"];
						$oidata["oi_cost_price"]  = $gga["pdt_cost_price"];
						$oidata["pdt_sale_price"] = $gga["pdt_sale_price"];
						$oidata["oi_price"] = $gga["pdt_sale_price"];
						$oidata["oi_nums"]  = $gga["num"];
						$oidata["pdt_sn"]   = $gga["pdt_sn"];
						$oidata["oi_type"]  = $gga["goods_type"];
						$oidata["oi_create_time"] = date("Y-m-d H:i:s");

						//插入订单详情
						M("OrdersItems")->add($oidata);
					}

					//创建成功从购物车删除商品
					foreach ($ary_pid as $pdt_id) {
						if(!empty($data[$pdt_id])){
							unset($data[$pdt_id]);
						}
					}

					//重组购物车信息
					$str = urlencode(serialize($data));
					$update_data["key"]   = $key;
					$update_data["value"] = $str;
					$update_data['modify_time'] = date("Y-m-d H:i:s",time());
					
					//更新购物车
					$UpdCart = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);

					//
					$orderInfo = M("Orders")->where("o_id={$odata["o_id"]}")->find();
					//返回订单信息给客户端我
					$OrdersData = array(
						"o_id"              => $odata["o_id"],
						"o_goods_all_price" => $orderInfo["o_goods_all_price"],
						"o_all_price"       => $orderInfo["o_all_price"],
					);

					$result["info"]       = "订单创建成功";
					$result["status"]     = "10000";
					$result["ordersdata"] = $OrdersData;
					print_r(json_encode($result));
					die;
				}
			}
		}
	}


	/** 意见反馈
	 ** @param  string  $m_id     用户ID
	 ** @param  string  $msg_type 反馈类型,0留言；1投诉；2询问；3售后；4求购
	 ** @param  string  $comtent  留言内容
	**/
	public function appFeedback(){
		//定义一个数组来储存数据
		$result = array();
		
		//参数
		$m_id     = $_POST["m_id"];
		$msg_type = $_POST["msg_type"];
		//$title    = $_POST["msg_title"];
		$content  = $_POST["msg_content"];

		//提交会员信息
		$userinfo = M("members")->field("m_name,m_mobile")->where("m_id={$m_id}")->find();
		//用户ID是否存在
		if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//留言数据
			$data["user_id"]     = $m_id;
			$data["user_name"]   = $userinfo["m_name"];
			$data["user_mobile"] = $userinfo["m_mobile"];
			if($msg_type=="0"){
				$data["msg_title"]   = "留言";
			}else if($msg_type=="1"){
				$data["msg_title"]   = "投诉";
			}else if($msg_type=="2"){
				$data["msg_title"]   = "询问";
			}else if($msg_type=="3"){
				$data["msg_title"]   = "售后";
			}else{
				$data["msg_title"]   = "求购";	
			}
			$data["msg_type"]    = $msg_type;
			$data["msg_content"] = $content;
			$data["msg_time"]    = date("Y-m-d H:i:s",time());

			//插入数据
			$reg = M("Feedback")->add($data);

			//是否有返回记录
			if(empty($reg)){
				$result["info"]   = "提交失败";
				$result["status"] = "10003";
				print_r(json_encode($result));
				die;
			}else{
				$result["info"]   = "提交成功";
				$result["status"] = "10000";
				print_r(json_encode($result));
				die;	
			}
		}
	}
	/** 支付宝支付
	 ** @param string $o_id 本地订单ID  
	**/
	public function appAlipay(){
		//定义一个数组来储存数据
		$result = array();

		//参数，订单ID
		$o_id = $_POST["o_id"];

		//查询订单信息
		$OrdersInfo = M("Orders")->field("o_id,o_goods_all_price,o_all_price")->where(array('o_id'=>$o_id))->find();
		
		//参数是否存在
		if(empty($OrdersInfo)){
			$result["info"]   = "订单信息错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			//获取支付宝参数
			$AlipayParam = $this->appAlipayParamConfig();
			
			//订单数据
			$odata = array(
                "out_trade_no"       => $OrdersInfo["o_id"],
                "subject"            => "商品购买",
                "body"               => "商品购买",
                "total_fee"          => $OrdersInfo["o_all_price"],
                "private_key"        => $AlipayParam['private_key'],
                //"alipay_public_key"  => $AlipayParam['alipay_public_key'],
                "notify_url"         => $AlipayParam['notify_url'],
                //"do_return"          => $AlipayParam['do_return'],
                "partner"            => $AlipayParam['partner'],
                "seller_id"          => $AlipayParam['seller_id'],
                //"key"                => $AlipayParam['key'],
                "service"            => $AlipayParam['service'],
                "it_b_pay"           => $AlipayParam['it_b_pay'],
                "payment_type"       => $AlipayParam['payment_type'],
                "_input_charset"     => $AlipayParam['input_charset'],
                "sign_type"          => $AlipayParam['sign_type'],
            );
            $result["status"]    = "10000";
            $result["info"]      = "生成支付订单信息";
            $result["orderdata"] = $odata;
            print_r(json_encode($result));
            die();
		}
	}
	/** 
	 **  支付宝异步通知结果
	**/
	public function appAlipayNotify(){
		//支付完成，返回第三方订单信息
		$out_trade_no = $_POST['out_trade_no'];
        $trade_no     = $_POST['trade_no'];
        $trade_status = $_POST['trade_status'];

        ///返回状态，更新订单状态
        if($trade_status == 'TRADE_FINISHED') {
            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序
            $odata['o_pay_status'] = '1';
            $odata['o_thd_sn']     = $trade_no;;
            M("Orders")->where("o_id={$out_trade_no}")->save($odata);
          
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";
        }else if ($trade_status == 'TRADE_SUCCESS') {
            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序
            $odata['o_pay_status'] = '1';
            $odata['o_thd_sn']     = $trade_no;;
            M("Orders")->where("o_id={$out_trade_no}")->save($odata);
         
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";
        }
	}


	/** 支付宝配置参数
	 ** @param partner 合作身份者id，以2088开头的16位纯数字，必填
     ** @param seller_id 支付宝商家收款帐号，必填
     ** @param key     安全检验码，以数字和字母组成的32位字符，必填（APP支付不需要填写）
     ** @param private_key 商户的私钥，此处有两种商户的私钥，普通格式和pkcs_8 格式，必填 （APP支付填写pkcs_8）
     ** @param alipay_public_key 支付宝的公钥，必填（APP支付不需要填写，退货才需要用到）
     ** @param notify_url 异步通知接口，必填（支付成功，异步通知更新订单信息）
     ** @param do_return 页面跳转，选填（APP支付不需要填写）
     ** @param service 接口名称，必填
     ** @param it_b_pay 未付款交易的超时时间，选填（APP支付不需要填写）
     ** @param payment_type 支付类型，不需修改，必填（APP支付不需要填写）
     ** @param sign_type 签名方式，不需修改，必填（APP支付不需要填写）
     ** @param input_charset 字符编码格式 目前支持 gbk 或 utf-8，支付类型，不需修改，必填（APP支付不需要填写）
     ** @param cacert ca证书路径地址，用于curl中ssl校验,请保证cacert.pem文件在当前文件夹目录中，必填（APP支付不需要填写）
     ** @param transport 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http，不需修改
	**/
	public function appAlipayParamConfig(){
		$alipay_config['partner']           = '2088421758061413';
        $alipay_config['seller_id']		    = 'jiemoinpin@163.com';
        $alipay_config['key']               = 'f183c8d5487acbd9f375c7e1e6ab3e07';
        $alipay_config['private_key']	    = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCKTrdDxkSQaNRi99OGqiJZ3VHkuqhV06K270ijaParEyq9bM2LTl6XBg1f8E8MbHTFUaYBx6yYEIWrG5ERId6stHuAMtkTPBVvwBCodsIXuypezErUt4VGqze4+hqD/SRdAqQ7OadNHT/czQAeCQjby8PQTMYkljEB9fIlthW8JOpe79pvXfeqvLFBX1J6DA6SQvWCWNh2z8xi5DQYm82vBuBKrco56QU16dvPqocYK1dlC8vgi9AiyEdSCsq0yVBrZhLTLV65EevHn0itt+MyY/i4m4DCmwU1LojV2GHFY9kyDXATa4dlINw0JQhPKHl/9uNgg/r0qzQsruSOnNi9AgMBAAECggEAPxbvJDxh9FnNYCPaWphrOQDrJWI7/YKfu8DlKp1rv9frbCMgk8Y5Ab0iBrxw4qLqlUPMbQ1vXqJSxx25C86ea7uchnsraEnFIYfEUYRyvy6CgmHAVM4uPnFF5tw3kjO8Da1xyw5ekZ+hDRB6WDkY0GJfvTn4PKJCUrmlLqyjt4GXemNruiFkdXRgG4zKQpW8YZY6q7maIqNAjS9pY0Fw+ZxKlOSE9yLX8aEbNbvsJvpq2qyXLbVxLY0G4TyHmlqbencf1q59dV8kulQfjReW5wr7Y0uTdEXO8zGffggv/rDNBTe2u7F3wXt+2pXd78MTBsiCHQkmxxvOGfmFuz0I5QKBgQDGbmR3IQo7Yl3p1KAubLouXJlcMMs7lV5hppVFfacJiCLx19Fjgy1rruBo0Se15wcfko8ajOL9wGrv8kvDk98ln1vumDScArf8iDa0FDWseFP2W3H+5mDj8tBYU7MOsJ/iqsFL03tJwY9aIr6keKRm2YT+vO1vOdnIitqi2r5iYwKBgQCybuWcIspMRqaCTWxGfYzv7dhrvIpv4N/hx2XrbC9Pwk/jK1J9i+jL0NOULajQQmimG7VFjhcTSRwToKB5sICeCGWuPy6a/zPncUYfbRS9k4mHBAS4sUEQdUE2xWec46gb6m2znPW8DQX5hn86SI2oMUBhh7iGz+vuPdaw7vUyXwKBgQCv1PhvW7auYn7nigMawDvGg0VeHNqqBLTqgOt69VoDpz+X3+7qaD3iUscF+nhexsZYIs1t8HPf+RaJYsHiH3E37FtRNExCBmK0gps+vT3Gg6WbAvMjtU8cDniyHBDprncvKrI4F8EC3WLCs1ENet3rSUqBVDZtPe0OuIP+lSjpEwKBgQCsWR2DUL9iKaPPnIUHGWI2peAzQCvfoK/fX0B8w0R3n9KWNFQ7XBaCN7UGHYw/jMWUHJNdGcAEKsvp0W7v26KAAtBkHDciE3rJBMqctDoiWw2t0h3VX7De+sMA6nAIRIUuYaOQp/1bpKCuwlDFtSrO2LO9AOElplPXDPgAcu93tQKBgFykuAMPNbUBmWHVfjoUZNBeCPW3vBjRIj3p4MNwxQD40Wb56wWR2TH3EujuR8FdboWzIlTrY8U/hzJdVS38ggTHqqi39HuoJ4HDuUxeYE9hKbozHTw8uTcoAyFRwUpNsDUXVOeQxVV431lPukHmINzJfAlxB6F+zHiPsl6B0ljZ';
        $alipay_config['alipay_public_key'] = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAik63Q8ZEkGjUYvfThqoiWd1R5LqoVdOitu9Io2j2qxMqvWzNi05elwYNX/BPDGx0xVGmAcesmBCFqxuRESHerLR7gDLZEzwVb8AQqHbCF7sqXsxK1LeFRqs3uPoag/0kXQKkOzmnTR0/3M0AHgkI28vD0EzGJJYxAfXyJbYVvCTqXu/ab133qryxQV9SegwOkkL1gljYds/MYuQ0GJvNrwbgSq3KOekFNenbz6qHGCtXZQvL4IvQIshHUgrKtMlQa2YS0y1euRHrx59IrbfjMmP4uJuAwpsFNS6I1dhhxWPZMg1wE2uHZSDcNCUITyh5f/bjYIP69Ks0LK7kjpzYvQIDAQAB';
        $alipay_config['notify_url']        = "http://www.caizhuangguoji.com/Home/AppUser/appAlipayNotify";
        $alipay_config['service']           = 'mobile.securitypay.pay';
        $alipay_config['it_b_pay']          = "30m" ;
        $alipay_config['payment_type']      = 1;
        $alipay_config['sign_type']         = strtoupper('RSA');
        $alipay_config['input_charset']     = strtolower('utf-8');
        //$alipay_config['cacert'] = APPPATH.'third_party/alipay/cacert.pem';
        $alipay_config['transport']         = 'http';
        return $alipay_config;
	}

	public function appWxNotify(){
		// $debug = M('debug','fx_','mysql://qiaomoxuan:yvIo4CqmNykWluCt@10.46.99.172:3306/qiaomoxuan');
		$xml = file_get_contents('php://input');

         // $value['content'] = $xml;
		 // $debug->data($value)->add();
		//1:转换XML文档为数组   xml2array
		$resultArr = xml2array($xml);
		if($resultArr['result_code']=='SUCCESS'){
			$trade_no = $resultArr['out_trade_no'];
			$odata['o_pay_status'] = '1';
			$odata['o_id']     = $trade_no;
			M("Orders")->where("o_id={$trade_no}")->save($odata);
			$result = array();
			$result['return_code'] = '<![CDATA[SUCCESS]]>';
			$result['return_msg'] = '<![CDATA[OK]]>';
			$returnXML = toXml($result);
            // $value['content'] = $returnXML;
		    // $debug->data($value)->add();
            echo $returnXML;
		}
		
	}

	// public function ApiGoodsList(){
	// 	//定义一个数组来储存数据
	// 	$result = array();
	// 	//分类ID
	// 	$cid   = $_POST["categoryId"];
	// 	$pages = empty($_POST["page"])?"1":$_POST["page"];

	// 	//分页
	// 	$limit = empty($_POST["pageSize"])?20:$_POST["pageSize"];;
	// 	$page  = max(1, intval($pages));
	// 	$startindex=($page-1)*$limit;
	// 	$gnums = D("ViewGoods")->where(array("g_on_sale"=>"1","gc_id"=>$cid,"g_status"=>"1"))->count();

	// 	$member     = D("Members");
		
	// 	if(empty($cid)){
	// 		//所有商品
	// 		$glist = M("GoodsInfo")->field("a.g_sn,fx_goods_info.*")
	// 						       ->join("fx_goods as a on a.g_id=fx_goods_info.g_id")
	// 						       ->where(array("a.g_on_sale"=>"1","g_status"=>"1"))
	// 						       ->limit("{$startindex},{$limit}")
	// 						       ->order(array('fx_goods_info.g_id'=>'desc'))
	// 						       ->select();
	// 		if(empty($glist)){
	// 			$result["message"]         = "暂无商品";
	// 			$result["status"]       = "10001";
	// 			print_r(json_encode($result));
	// 			die;
	// 		}else{
	// 			$result["message"]         = "请求成功";
	// 			$result["status"]       = "10000";
	// 			$result["goodslist"]    = $glist;
	// 			print_r(json_encode($result));
	// 			die;
	// 		}
	// 	}else{
	// 		//对应分类商品
	// 		$field = 'gc_id as categoryId,g_name as productName,g_id as productId,g_picture as mainImageUrl,gc_name as categoryName,g_price as price';
	// 		$productList = $member->GetProductList($cid,$field,$startindex,$limit)

	// 		$result["message"]      = "请求成功";
	// 		$result["status"]    = "10000";
	// 		$body["goodslist"] = $glist;
	// 		$result["body"] = $body;
	// 		print_r(json_encode($result));
	// 		die;
	
	// 	}
	// }
}
