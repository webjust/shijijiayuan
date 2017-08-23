<?php

/**
 * 后台会员中心控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-08
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersAction extends AdminAction {

	public function _initialize() {
		parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
		$this->setTitle(' - '.L('MENU5_0'));
	}

	/**
	 * 控制器默认方法，暂时重定向到授权线管理
	 * @author zuo <zuojianghua@guanyisoft.com>
	 * @date 2013-01-15
	 * @todo 需要重定向到会员列表页的
	 */
	public function index() {
		$this->redirect(U('Admin/Members/pageList'));
	}

	/**
	 * ajax根据用户名判断是否有该用户，没有返回错误信息
	 * @author zuo <zuojianghua@guanyisoft.com>
	 * @date 2013-01-08
	 */
	public function getCheckName() {
		$name = $this->_get('m_name');
		$Members = D('Members');
		$res = $Members->where(array('m_name' => $name))->find();
		if (false == $res) {
			$this->ajaxReturn('该用户不存在');
		} else {
			$this->ajaxReturn(true);
		}
	}

	/**
	 * 添加用户显示页面
	 * @author listen
	 * @date 2012-01-15
	 */
	public function memberAdd() {
		$this->getSubNav(6, 0, 20);
		$ary_members_level = M('members_level', C('DB_PREFIX'), 'DB_CUSTOM')->select();
		$ary_platform = D('SourcePlatform')->where(array('sp_stauts' => 1))->select();
		/* 取出会员扩展属性项字段 start*/
		$ary_extend_data = D('MembersFields')->displayFields();
		$this->assign('ary_extend_data', $ary_extend_data);
		/* 取出会员扩展属性项字段 end*/
		$this->assign('ary_platform', $ary_platform);
		$this->assign('members_level', $ary_members_level);
		$this->display();
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
			$html = '<select id="' . $item . '" name="' . $item . '" ' . $str . ' class="medium">';
			$html .= '<option value="0" selected="selected">请选择</option>';
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
		//echo "<pre>";print_r($html);exit;
		echo $html;
		exit;
	}

	/**
	 * 增加会员
	 * @author listen
	 * @date 2013-01-15
	 */
	public function doAdd() {
		//验证用户名是否输入
		if (!isset($_POST['m_name']) || "" == $_POST['m_name']) {
			$this->error('用户名不能为空');
		}

		//验证用户名的唯一性
		if (D('Members')->checkName($_POST['m_name'])) {
			$this->error('用户名已经存在');
		}
        $length_password =  strlen($_POST['m_password']);
        if($length_password < 6 && !empty($_POST['m_password'])){
            $this->error('密码的长度必须是6位');
        }
		//验证是否输入密码和确认密码
		if (!isset($_POST["m_password"]) || "" == $_POST["m_password"]) {
			$this->error("请设置一个密码。");
		}

		//验证两次密码是否相同
		if ($_POST['m_password'] != $_POST['m_password_1']) {
			$this->error('用户密码和确认密码必须相同');
		}
        //验证邮箱是否为必填项
        $ary_mem_field = D('MembersFields')->where(array('is_status'=>1))->select();
        if(!empty($ary_mem_field) || is_array($ary_mem_field)){
            foreach($ary_mem_field as $val){
                if($val['field_name'] == 'E-mail' && fields_content == 'm_email'){
                    $email_need = $val['is_need'];
                }
            }
        }
		//验证是否输入会员邮箱
        if($email_need == 1){
            if (!isset($_POST['m_email']) || "" == $_POST['m_email']) {
                $this->error('用户邮箱不能为空');
            }
            //验证会员邮箱地址的合法性
            $email_preg = '/^[a-z0-9._%+-]+@(?:[a-z0-9-]+.)+[a-z]{2,4}$/i';
            if (false == preg_match($email_preg, $_POST['m_email'])) {
                $this->error("邮箱格式不合法。");
            }
        }



		//验证邮箱的唯一性
		if (D('Members')->checkEmail($_POST['m_email'])) {
			$this->error('用户邮箱已经存在');
		}

		//验证是否输入会员所属的省市区
		if ($_POST['province'] == '-1' || $_POST['city'] == '-1') {
			$this->error('用户省份/城市/区不能为空');
		}

		//会员性别选择的验证
		if (!isset($_POST['m_sex']) || "" == $_POST['m_sex']) {
			$this->error('用户性别不能为空');
		}

		$array_insert_data = $this->_post();
		unset($array_insert_data['province']);
		//生成用户密码
		$array_insert_data['m_password'] = md5($array_insert_data['m_password']);
		$array_insert_data['m_create_time'] = date("Y-m-d H:i:s");
            $array_insert_data['cr_id'] = $_POST['city'];

		//三级升级行政区域的ID，如果没设置地三级，就取第二级，否则第一级
		$array_insert_data['cr_id'] = 0;
		if (isset($_POST['region1']) && $_POST['region1'] > 0) {
			$array_insert_data['cr_id'] = $_POST['region1'];
		} else if (isset($_POST['city']) && $_POST['city'] > 0) {
			$array_insert_data['cr_id'] = $_POST['city'];
		} else if (isset($_POST['province']) && $_POST['province'] > 0) {
			$array_insert_data['cr_id'] = $_POST['province'];
		}
		unset($array_insert_data['region1']);
		unset($array_insert_data['city']);
		unset($array_insert_data['province']);
		$array_insert_data['m_status'] = 1;
		
		//对电话做加密存储
		if($array_insert_data['m_mobile']) {			
			$array_insert_data['m_mobile'] = encrypt($array_insert_data['m_mobile']);
		}
		//对固话做加密存储
		if($array_insert_data['m_telphone']) {			
			$array_insert_data['m_telphone'] = encrypt($array_insert_data['m_telphone']);
		}
		
		//事务开始
		D("Members")->startTrans();
		//会员基本资料入库
		$mixed_member_id = D("Members")->add($array_insert_data);
		if (false === $mixed_member_id) {
			$this->error("会员资料添加失败。");
		}

		//会员来源平台数据入库保存
		if (!empty($_POST['platform'])) {
			foreach ($_POST['platform'] as $v) {
				$ary_data = array();
				$ary_data['m_id'] = $mixed_member_id;
				$ary_data['sp_id'] = $v;
				$res_platform = D('RelatedMembersSourcePlatform')->add($ary_data);
				if (false === $res_platform) {
					D("Members")->rollback();
					$this->error('会员添加失败');
				}
			}
		}
		/*把新增加用户属性项信息插入数据库 start*/
		$int_extend_res = D('MembersFieldsInfo')->doAdd($_POST,$mixed_member_id);
		if(!$int_extend_res['result']){
			D('MembersFieldsInfo')->rollback();
			$this->error('会员添加失败');
		}
		
		//会员推送到线下系统
		if($mixed_member_id >0 && C('AUTO_SNYC_MEMBER')==1){
			$array_insert_data['method'] ='AddMember';
			if(!isset($array_insert_data['id'])){
				$array_insert_data['m_id'] = $mixed_member_id;
			}
			if(isset($array_insert_data['m_mobile'])){
				$array_insert_data['m_mobile'] = decrypt($array_insert_data['m_mobile']);
			}
			$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
			$host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
			request_by_fsockopen($host_url.'/Script/Batch/snyc_send',$array_insert_data);			 
		}
		
		/*把新增加用户属性项信息插入数据库 end*/
		//会员资料添加成功，事务提交
		D("Members")->commit();

		//区分不同的操作按钮点击  进行页面跳转
		$string_jump_url = U('Admin/Members/pageList');
		if (isset($_POST["jump_type"]) && 1 == $_POST["jump_type"]) {
			$string_jump_url = U('Admin/Members/memberAdd');
		}
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"添加会员信息",'添加的会员信息为：'.$_POST['m_name']));
		$this->success('会员添加成功', $string_jump_url);
	}

	/**
	 * 会员列表页  
	 * @author listen
	 * @date 2013-01-15
	 */
	public function pageList() {
		$this->getSubNav(6, 0, 10);
		$where = array();
		unset($_GET["advance_search"]);
		
		if(!empty($_GET['filter'])){
			$_GET['filter'] = str_replace("\\",'',$_GET['filter']);
			$advance_search = json_decode(stripslashes($_GET['filter']),true);
			$ary_advance_search = array();
			foreach($advance_search as $search){
				$tmp_name = str_replace('advance_search[','',$search['name']);
				$tmp_name = str_replace(']','',$tmp_name);
				if(strpos($tmp_name,'[')>0){
					$tmp_name = str_replace('[','',$tmp_name);
					$ary_advance_search[$tmp_name] = array($search['value']);
				}else{
					$ary_advance_search[$tmp_name] = $search['value'];
				}
			}
			unset($advance_search);			
		}

		//会员高级搜索处理
		if(isset($ary_advance_search) && !empty($ary_advance_search)){
			$where = D("Members")->adminMemberAdvanceSearch($ary_advance_search);
		}
		$res_fields=D("MembersFields")->getList(array('list_display'=>1,'is_status'=>1),array('id,field_name'));
		$ary_data = $this->_get();
		if (isset($ary_data['level']) && $ary_data['level'] != 0 && $ary_data['level'] !='') {
			$where['ml_id'] = $ary_data['level'];
		}
		if ($ary_data['m_name_type'] == '1' && isset($ary_data['m_name'])  && !empty($ary_data['m_name'])) {
			$where['m_name'] = $ary_data['m_name'];
		}
		if ($ary_data['m_name_type'] == '2' && isset($ary_data['m_name']) && !empty($ary_data['m_name'])) {
			$where['m_real_name'] = $ary_data['m_name'];
		}
		if ($ary_data['m_name_type'] == '3' && isset($ary_data['m_name']) && !empty($ary_data['m_name'])) {
			$where['m_mobile'] = encrypt(trim($ary_data['m_name']));
		}
		$int_count = M('view_members', C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->count();
		$obj_page = new Page($int_count, 10);
		$page = $obj_page->show();
        $ary_members = D('Members')->membersInfo($obj_page->firstRow, $obj_page->listRows, $ary_where = $where, $ary_field = '', $ary_orders = array('m_create_time' => 'desc'));

		//edit Micle <yangkewei@guangyisoft.com> 保存筛选结果m_ids用于导出Excel
		$m_ids = M('view_members')->where($where)->field('m_id')->select();
		$filters = array();
		foreach($m_ids as $val){
			$filters[] = $val['m_id'];
		}
		$this->assign('filters',implode(',',$filters));  //edit Micle 保存筛选结果m_ids 结束
		$this->assign('filter',$ary_data);
        $ary_platform = D('SourcePlatform')->where(array('sp_stauts' => 1))->select();
        $shop_title = D('SysConfig')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_TITLE'))->getField('sc_value');
        foreach ($ary_members as &$member) {
            $menber_info=D('MembersVerify')->where(array('m_id'=>$member['m_id']))->find();
            if(!empty($menber_info) && is_array($menber_info)){
                if(isset($menber_info['m_verify'])){
                    $member['m_verify']=$menber_info['m_verify'];
                }
                if(!empty($menber_info['m_email']) && isset($menber_info['m_email'])){
                    $member['m_email']=$menber_info['m_email'];
                }
                if(isset($menber_info['m_sex'])){
                    $member['m_sex']=$menber_info['m_sex'];
                }
            }
            $ary_re_platform = D('RelatedMembersSourcePlatform')->where(array('m_id' => $member['m_id']))->select();
            if (!empty($ary_platform)) {
                if (!empty($ary_re_platform)) {
                    $source = '';
                    foreach ($ary_platform as $k => $v) {
                        foreach($ary_re_platform as $val){
		            	    if ($val['sp_id'] == $v['sp_id']) {
		                    	 $source .= $v['sp_name'] . ' ';
		                	}            		
		            	}
                    }
                    $member['m_source'] = $source;
                }
            }
            foreach ($res_fields as $k => $val) {
                $res_fields_info=D("MembersFieldsInfo")->getList(array('u_id'=>$member['m_id'],'field_id'=>$val['id']),array('content'));
                if(!empty($res_fields_info)){
					if(strpos($res_fields_info[0]['content'],'/Public/Uploads/')>=0){
						$res_fields_info[0]['content'] = D('QnPic')->picToQn($res_fields_info[0]['content']);
					}
					$member['fields'][$k] = $res_fields_info[0];
                }else{
                    $member['fields'][$k]=array('content'=>'');
                }
            }
            //会员所属地区
            $array_region = D("CityRegion")->getCityRegionInfoByLastCrId($member['cr_id']);
            $where['cr_id'] = array('in',array($array_region['province'],$array_region['city']));
            $ary_addr = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->field('cr_name')->where($where)->select();
            $str_addr = $ary_addr[0]['cr_name'] . ' ' . $ary_addr[1]['cr_name'];
            $member['cr_name'] = $str_addr;
            $member_info = D('Members')->where(array('m_id'=>$member['m_id']))->field('open_source,m_bonus')->find();
			$member['m_bonus'] = $member_info['m_bonus'];
			$open_source = $member_info['open_source'];
            switch ($open_source){
                case 'QQ':
                    $member['source'] = '腾讯授权注册用户';
                break;
                case 'Sina':
                    $member['source'] = '新浪微博授权注册用户';
                break;
                case 'RenRen':
                    $member['source'] = '人人网授权注册用户';
                break;
                case 'TaobaoO2O':
                    $member['source'] = '淘宝O2O注册用户';
                break;
                default:
                    $member['source'] = $shop_title.'注册用户';
                break;
            }
        }
        //echo "<pre>";print_r($ary_members);exit;
        $this->assign('ary_members', $ary_members);

        //分组数组
        $ary_members_group = D('MembersGroup')->where(array('mg_status' => '1'))->select();
        //等级数组
        $ary_members_level = D('MembersLevel')->where(array('ml_status' => '1'))->select();
        $this->assign('group', $ary_members_group);
        $this->assign('level', $ary_members_level);
        $this->assign("fields", $res_fields);
        $this->assign("page", $page);
        $this->display();
    }

	/**
	 * 会员删除
	 * @author listen
	 * @date 2013-01-16
	 */
	public function doDel() {
		$m_id = $this->_get('m_id');
		if (is_array($m_id)) {
			//批量删除
			$where = array('m_id' => array('IN', $m_id));
		} else {
			//单个删除
			$where = array('m_id' => $m_id);
		}
		$Members = D('Members');
		$props = $Members->where($where)->field('m_name')->select();
		$str_prop_name = '';
		foreach($props as $prop){
			$str_prop_name .=$prop['m_name'];
		}
		$str_prop_name = trim($str_prop_name,',');
		
		$res = $Members->where($where)->delete();
		if (false == $res) {
			$this->error('删除失败');
		} else {
			//删除会员时顺便删除会员其他相关表
			M("member_payback_statistics", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("member_payback", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("member_relation", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("member_sales_set", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			//M("members_fields", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("member_competence", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("member_differ_price_rebates_record", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("members_deposit_log", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("members_fields_info", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("related_members_group", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();
			M("related_members_source_platform", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->delete();		
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除会员信息",'删除会员ID为：'.$m_id.'-会员名称:'.$str_prop_name));
			$this->success('删除成功');
		}
	}

	/**
	 * 会员编辑显示页面
	 * @author listen
	 * @date 2013-01-15
	 *
	 */
	public function pageEdit() {
		$this->getSubNav(6, 0, 10, '编辑会员');
		//验证参数的合法性
		if (!isset($_GET["mid"]) || !is_numeric($_GET["mid"])) {
			$this->error("会员编辑参数错误。");
		}

		//获取会员的详细信息
		$m_id = $this->_get('mid');
		
        $ary_members=D('Members')->where(array('m_id'=>$m_id))->find();
        //验证被编辑的会员等级是否存在
		if (!is_array($ary_members) || empty($ary_members)) {
			$this->error("您要编辑的会员不存在。");
		}
		$ary_members_level = D("MembersLevel")->where(array("ml_status" => 1))->order(array("ml_order" => "desc"))->select();

		//验证是否存在有效的会员等级
		if (!is_array($ary_members_level) || empty($ary_members_level)) {
			$this->error("没有找到可用的会员等级：请先添加会员等级！");
		}

		//获取会员的地址（省市区行政区域ID）
		$array_region = D("CityRegion")->getCityRegionInfoByLastCrId($ary_members['cr_id']);

		//会员来源平台
		$ary_re_platform = D('RelatedMembersSourcePlatform')->where(array('m_id' => $m_id))->select();
		$ary_platform = D('SourcePlatform')->where(array('sp_stauts' => 1))->select();
		if (!empty($ary_platform)) {
			foreach ($ary_platform as $k => $v) {
				foreach($ary_re_platform as $val){
					if ($val['sp_id'] == $v['sp_id']) {
						$ary_platform[$k]['is_select'] = 1;
					}
				}
			}
		}
        //根据积分获取积分等级名称
        $points = $ary_members['total_point']-$ary_members['freeze_point'];
        //D("PointsLevel")->getPointsLevel($points);
        $plName = M('PointsLevel',C('DB_PREFIX'),'DB_CUSTOM')->field('pl_name')->where(array('pl_up_fee'=>array('elt',$points)))->order('pl_up_fee desc')->find();
        //dump($plName); echo M()->getLastSql();exit();
        if($plName['pl_name']){
            $this->assign('plname',$plName['pl_name']);
        }
		//变量传递到模板 - 视图渲染
		/* 取出会员扩展属性项字段 start*/
		$ary_extend_data=D('MembersFields')->displayFields($m_id);
        //dump($ary_extend_data);exit;
		$this->assign('ary_extend_data', $ary_extend_data);
		/* 取出会员扩展属性项字段 end*/
		$this->assign('ary_platform', $ary_platform);
		$this->assign('region', $array_region);
		$this->assign('members', $ary_members);
		$this->assign('members_level', $ary_members_level);
		$this->assign('tabs', 'pageEdit');
		$this->display();
	}


	/**
	 * 会员编辑操作
	 * @author listen
	 * @date 2013-01-15
	 */
	public function doEdit() {
		$data = $this->_post();
		$data['m_update_time'] = date("Y-m-d H:i:s");
        $ary_mem_field = D('MembersFields')->where(array('is_status'=>1))->select();
        if(!empty($ary_mem_field) || is_array($ary_mem_field)){
            foreach($ary_mem_field as $val){
                if($val['field_name'] == 'E-mail' && fields_content == 'm_email'){
                    $email_need = $val['is_need'];
                }
            }
        }
		if (!isset($data['m_email']) && $email_need == 1) {
			$this->error('用户邮箱不能为空');
		}
        $length_password =  strlen($data['m_password']);
        if($length_password < 6 && !empty($data['m_password'])){
            $this->error('密码的长度必须是6位');
        }
		if(isset($data['m_password'])&& isset($data['m_password_1']) && trim($data['m_password_1'])!='' && trim($data['m_password']) !='' && trim($data['m_password']) !=trim($data['m_password_1'])){
			$this->error('密码不一致');
		}
		if(isset($data['m_password'])&& isset($data['m_password_1']) && trim($data['m_password_1'])=='' && trim($data['m_password']) !=''){
			$this->error('密码不一致');
		}
		if (isset($data['m_password']) && !empty($data['m_password'])) {
			$data['m_password'] = md5($data['m_password']);
		} else {
			unset($data['m_password']);
		}
		if ($data['region1']) {
			$data['cr_id'] = $data['region1'];
		} else {
			$data['cr_id'] = $data['city'];
		}
        unset($data['province']);
        unset($data['city']);
        unset($data['region1']);

		if (!empty($data['province']) && isset($data['province'])) {
			$area_data=D('AreaJurisdiction')->where(array('cr_id'=>$data['province']))->find();
			if(!empty($area_data['s_id'])){
				$data['m_subcompany_id'] = $area_data['s_id'];
			}
		}

		if ($data['mStatus'] == '1') {
			$data['m_status'] = 0;
		} else {
			$data['m_status'] = 1;
		}
		if (isset($data['tz_point']) && is_numeric($data['tz_point']) && !empty($data['tz_point'])) {

			if ($data['tz_point'] > 0)
			$data['total_point'] = $data['total_point'] + intval($data['tz_point']);
			elseif ($data['tz_point'] < 0)
			$data['total_point'] = $data['total_point'] - intval(abs($data['tz_point']));
			if ($data['total_point'] < 0)
			$this->error('调整后积分不能为负数');
			//插入积分日志表
			$ary_temp = array(
                'type' => 5,
                'consume_point' => $data['tz_point'] < 0 ? intval(abs($data['tz_point'])) : 0,
                'reward_point' => $data['tz_point'] > 0 ? intval($data['tz_point']) : 0,
			);
			$ary_return = D('PointLog')->addPointLog($ary_temp, $data['m_id']);
			if ($ary_return['status'] !== 1)
			$this->error($ary_return['msg']);
		}
		//对电话做加密存储
		//如果电话号码里有*号存在，认为电话没有修改，不做更新
		if(strpos($data['m_mobile'], '*')) {
			unset($data['m_mobile']);
		}else{
			$data['m_mobile'] = encrypt($data['m_mobile']);
		}
		//对固话做加密存储
		if($data['m_telphone']) {			
			$data['m_telphone'] = encrypt($data['m_telphone']);
		}
		D()->startTrans();
		$res = M('members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id' => $data['m_id']))->save($data);
		//会员所属平台添加
		if (FALSE !== $res) {
			D('RelatedMembersSourcePlatform')->where(array('m_id' => $data['m_id']))->delete();
			if (!empty($data['platform'])) {
				foreach ($data['platform'] as $v) {
					$ary_data = array();
					$ary_data['m_id'] = $data['m_id'];
					$ary_data['sp_id'] = $v;
					$res_platform = D('RelatedMembersSourcePlatform')->add($ary_data);
					if (!$res_platform) {
						D()->rollback();
						$this->error('会员修改失败');
					}
				}
			}
		}
		/*把新增加用户属性项信息插入数据库 start*/
		$int_extend_res = D('MembersFieldsInfo')->doAdd($_POST,$data['m_id'],2);
		if(!$int_extend_res['result']){
			D('MembersFieldsInfo')->rollback();
			$this->error('会员修改失败');
		}
		/*把新增加用户属性项信息插入数据库 end*/
		
		if ($res) {
			//会员推送到线下系统
			if( C('AUTO_SNYC_MEMBER')==1){
				$tmp_data = D('Members')->field('m_id,m_name,m_mobile,m_sex,m_password,m_telphone,m_birthday,m_id_card,m_email,m_qq,m_wangwang,m_status,shop_code')->where(array('m_id'=>$data['m_id']))->find();
				$tmp_data['method'] ='UpdateMember';
				if(isset($tmp_data['m_id']) && $tmp_data['m_id']!=''){
					if($tmp_data['m_telphone']!=''){
						$tmp_data['m_telphone'] = decrypt($tmp_data['m_telphone']);
					}
					if($tmp_data['m_mobile']!=''){
						$tmp_data['m_mobile'] = decrypt($tmp_data['m_mobile']);
					}
					if($tmp_data['m_id_card']!=''){
						$tmp_data['m_id_card'] = decrypt($tmp_data['m_id_card']);
					}
					$str_requert_port = ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'];
					$host_url='http://' . $_SERVER['SERVER_NAME'] . $str_requert_port ;
					request_by_fsockopen($host_url.'/Script/Batch/snyc_send',$tmp_data);	
				}	 
			}
			
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"修改会员信息",'修改会员为：'.$data['m_id'].'-会员名称:'.$tmp_data['m_name']));
            D()->commit();
			//删除缓存
			D('Gyfx')->deleteOneCache('members','ml_id', array('m_id'=>$int_mid), $ary_order=null);
			$obj_query = D('Members')->field(array('m_id','m_name','fx_members.ml_id','ml_discount'))->join("fx_members_level on fx_members.ml_id = fx_members_level.ml_id")->where(array('m_id'=>$int_mid));	
			D('Gyfx')->deleteQueryCache($obj_query,'find',60);			
            $this->success('会员修改成功', U('Admin/Members/pageList'));
		} else {
			$this->error('会员修改失败');
		}
	}

	/**
	 * 会员日志
	 * @author  listen
	 * @date 2013-01-18
	 */
	public function logPageList() {

	}

	/**
	 * 批量删除会员信息
	 * @author Terry<wanghui@guanyisoft.com>
	 * @date 2013-3-5
	 */
	public function doBatDelMembers() {
		$ary_post = $this->_post();
		$member = M("Members", C('DB_PREFIX'), 'DB_CUSTOM');
		if (!empty($ary_post['m_ids']) && isset($ary_post['m_ids'])) {
			$where = array();
			$where['m_id'] = array('in', $ary_post['m_ids']);
			$ary_result = $member->where($where)->delete();
			if ($ary_result) {
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"批量删除会员信息",'批量删除会员为：'.$ary_post['m_ids']));
				$this->success("删除成功", "Members/pageList");
			} else {
				$this->error("删除失败，请重试！");
			}
		} else {
			$this->error("请选择需要删除的会员！");
		}
	}

	/**
	 * 模糊搜索会员
	 * @author listen
	 * @date 2013-3-19
	 * @return json 会员数组k
	 */
	public function ajaxMemberLike() {
		$str_member_name = $this->_post('member_name');

		$return_ary = array('result' => false, 'data' => NULL, 'message' => '没有会员');
		if (isset($str_member_name)) {
			$where = array('m_name' => array('LIKE', $str_member_name . "%"));
			$ary_members = D('Members')->field('m_id,m_name')->where($where)->order("m_id desc")->limit(0, 10)->select();
			if (!empty($ary_members)) {
				$return_ary['result'] = true;
				$return_ary['data'] = $ary_members;
			}
		}
		echo json_encode($return_ary);
		exit;
	}

	/**
	 * @param 积分历史页面
	 * @author czy  <chenzongyao@guanyisoft.com>
	 * @version 7.1
	 * @since stage 1.5
	 * @modify 2013-4-27
	 */
	public function pointList() {
		$this->getSubNav(6, 0, 10, '积分历史');
		$m_id = $this->_get('mid');
		$where = array('m_id' => $m_id);

		$page_no = max(1, (int) $this->_get('p', '', 1));
		$page_size = 20;
		$int_count = D('PointLog')->where($where)->count();
		$obj_page = new Page($int_count, $page_size);
		$page = $obj_page->show();

		$ary_pointlog = D('PointLog')->where($where)->order('u_create desc')->page($page_no, $page_size)->select();
		$this->assign('m_id', $m_id);
		$this->assign('tabs', 'pointList');

        $ary_member_fields=M('MembersFields',C('DB_PREFIX'),'DB_CUSTOM')->where(array('is_need'=>0,'is_display'=>1,'if_status'=>1,'filelds_point'=>array('neq',0)))->select();
        $this->assign('members_fields',$ary_member_fields);

		$this->assign('int_nstart', $int_count); //总页数
		$this->assign('page', $page);
		$this->assign('ary_pointlog', $ary_pointlog);
		$this->display();
	}

	/**
	 * 批量设置审核
	 */
	public function doBacthMembers() {
		$int_mid = $this->_post('m_id');
		if (isset($int_mid)) {
			//如果冻结的 改变状态为停用
			if ($this->_post('type') == 'freeze') {
				$ary_data = array('m_status' => '0');
			}
			if ($this->_post('type') == 'verify') {
				$ary_data = array('m_verify' => '2');
			}
			$ary_data['m_update_time'] = date('Y-m-d H:i:s');
			$res_members = D('Members')->where(array('m_id' => array('in', $int_mid)))->save($ary_data);

			if (!$res_members) {
				$this->ajaxReturn(false);
				exit;
			} else {
				$this->ajaxReturn(true);
				exit;
			}
			//echo  D('Members')->getLastSql();exit;
		}
	}

	/**
	 * @param 买家留言列表
	 * @author wangguibin  <wangguibin@guanyisoft.com>
	 * @version 7.2
	 * @datae 2013-6-27
	 */
	public function feedBackList() {
		$ary_get = $this->_get();
		$ary_get['val'] = trim($ary_get['val']);
		$this->getSubNav(2, 5, 90, '买家留言列表');
		$ary_where = '';
		$ary_where .= C("DB_PREFIX") . "feedback.`parent_id`=0 AND ";
		if (isset($ary_get['msg_type']) && $ary_get['msg_type'] != 'select') {
			$ary_where .= C("DB_PREFIX") . 'feedback.`msg_type`=' . $ary_get['msg_type'] . " AND ";
		}
		if (isset($ary_get['msg_status']) && $ary_get['msg_status'] != 'select') {
			$ary_where .= C("DB_PREFIX") . 'feedback.`msg_status`=' . $ary_get['msg_status'] . " AND ";
		}
		//留言时间
		if (!empty($ary_get['starttime'])) {
			if (!empty($ary_get['endtime'])) {
				if ($ary_get['endtime'] >= $ary_get['starttime']) {
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`msg_time` BETWEEN '" . $ary_get['starttime'] . "' AND '" . $ary_get['endtime'] . "' AND ";
				} else {
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`msg_time` BETWEEN '" . $ary_get['endtime'] . "' AND '" . $ary_get['starttime'] . "'  AND ";
				}
			} else {
				$ary_where .= " " . C("DB_PREFIX") . "feedback.`msg_time` >='" . $ary_get['starttime'] . "'  AND ";
			}
		} else {
			if (!empty($ary_get['endtime'])) {
				$date = date("Y-m-d H:i:s");
				if ($ary_get['endtime'] >= $date) {
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`msg_time` BETWEEN '" . $date . "' AND '" . $ary_get['endtime'] . "'  AND ";
				} else {
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`msg_time` BETWEEN '" . $ary_get['endtime'] . "' AND '" . $date . "'  AND ";
				}
			}
		}
		if (!empty($ary_get['val']) && isset($ary_get['val'])) {
			switch ($ary_get['field']) {
				case 'm_name':
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`user_name` LIKE '%" . $ary_get['val'] . "%'";
					break;
				case 'o_id':
					$ary_where .= " " . C("DB_PREFIX") . "feedback.`order_id`='" . $ary_get['val'] . "'";
					break;
			}
		}
		$int_count = M('feedback', C('DB_PREFIX'), 'DB_CUSTOM')
		->field(" " . C("DB_PREFIX") . "feedback.*")
		->order(" " . C("DB_PREFIX") . "feedback.`msg_time` DESC")
		->where(trim($ary_where, "AND"))->count();
		$obj_page = new Pager($int_count, 10);
		$page = $obj_page->show();
		$ary_data = M('feedback', C('DB_PREFIX'), 'DB_CUSTOM')
		->field(" " . C("DB_PREFIX") . "feedback.*")
		->order(" " . C("DB_PREFIX") . "feedback.`msg_time` DESC")
		->where(trim($ary_where, " AND"))
		->limit($obj_page->firstRow, $obj_page->listRows)
		->select();
		//        echo "<pre>";print_r(M('feedback', C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());exit;
		//        echo "<pre>";print_r($ary_where);exit;
		foreach ($ary_data as &$ary) {
			$ary['user_mobile'] = decrypt($ary['user_mobile']);
			switch ($ary['msg_type']) {
				case 1:
					$ary['msg_type_title'] = '投诉';
					break;
				case 2:
					$ary['msg_type_title'] = '询问';
					break;
				case 3:
					$ary['msg_type_title'] = '售后';
					break;
				case 4:
					$ary['msg_type_title'] = '求购';
					break;
				default:
					$ary['msg_type_title'] = '留言';
					break;
			}
			$ary['detail'] = D("Feedback")->getMsgDetail($ary['msg_id'], 'all');
			foreach($ary['detail'] as &$detail){
				if(!empty($detail['file_url'])){
					$detail['file_url'] = D('QnPic')->picToQn($detail['file_url']);
				}
			}
			if(!empty($ary['file_url'])){
				$ary['file_url'] = D('QnPic')->picToQn($ary['file_url']);
			}
		}
		$this->assign("page", $page);
		$this->assign("filter", $ary_get);
		$this->assign("data", $ary_data);
		$this->display();
	}

	/**
	 * 回复买家留言保存 by wangguibin
	 * @data 2013.06.27
	 */
	public function replyAjax() {
		$ary_post = $this->_post();
		$qa_obj = D("Feedback");
		$ary_data = $qa_obj->getMsgDetail($_POST['msg_id']);
		$initData = $ary_data[0];
		$save = array();
		$save['parent_id'] = $initData['msg_id'];
		$save['user_name'] = $_SESSION['admin_name'];
		$save['msg_title'] = '回复：' . $initData['msg_title'];
		$save['msg_content'] = trim($_POST['content']);
		if (empty($save['msg_content'])) {
			echo json_encode(array('msg' => '回复内容不能为空', 'success' => '0'));
			exit;
		}
		$save['msg_time'] = date('Y-m-d H:i:s');
		$save['order_id'] = $initData['order_id'];
		if ($qa_obj->saveReply($save)) {
			echo json_encode(array('msg' => '回复成功', 'success' => '1','URL'=>'/Admin/Members/feedBackList'));
			exit;
		} else {
			echo json_encode(array('msg' => '回复失败', 'success' => '0'));
			exit;
		}
	}

	public function getFeedBackList($params = array()) {
		if (!empty($params['bi_sns']) && isset($params['bi_sns'])) {
			$ary_where['msg_id'] = array('in', $params['bi_sns']);
		}
		$ary_data = M('feedback', C('DB_PREFIX'), 'DB_CUSTOM')
		->where($ary_where)
		->select();
		return $ary_data;
	}

	/**
	 * 导出留言
	 * @author Wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-06-27
	 *
	 */
	public function explortFeedBackList() {
		$ary_post = $this->_post();
		if (!empty($ary_post['bi_sns']) && isset($ary_post['bi_sns'])) {
			$ary_data = $this->getFeedBackList($ary_post);
			$contents = array();
			$fields = array();
			$header = array('会员名称', '手机号', '留言类型', '留言标题', '留言内容', '留言时间', '回复状态');

			if (!empty($ary_data) && is_array($ary_data)) {
				foreach ($ary_data as $ky => $vl) {
					$status = ($vl['msg_status'] == '1') ? '已回复' : '未回复';
					switch ($ary['msg_type']) {
						case 1:
							$type = '投诉';
							break;
						case 2:
							$type = '询问';
							break;
						case 3:
							$type = '售后';
							break;
						case 4:
							$type = '求购';
							break;
						default:
							$type = '留言';
							break;
					}
					$contents[] = array(
					$vl['user_name'],
					decrypt($vl['user_mobile']),
					$type,
					$vl['msg_title'],
					$vl['msg_content'],
					$vl['msg_time'],
					$status
					);
				}
				$fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
			}
			@mkdir('./Public/Uploads/' . CI_SN . '/excel/');
			$Export = new Export(date('YmdHis') . '.xls', 'Public/Uploads/' . CI_SN . '/excel/');
			$excel_file = $Export->exportExcel($header, $contents, $fields, $mix_sheet = '买家留言信息', true);
			if (!empty($excel_file)) {
				$this->ajaxReturn(array('status' => '1', 'info' => '导出成功', 'data' => $excel_file));
			} else {
				$this->ajaxReturn(array('status' => '0', 'info' => '导出失败'));
			}
		} else {
			$this->ajaxReturn(array('status' => '0', 'info' => '请选择需要导出的买家留言信息'));
		}
	}

	/**
	 * 导出后台EXCEL信息数据
	 * @author wangguibin
	 * @since 7.2
	 * @version 1.0
	 * @date 2013-6-27
	 */
	public function getExportFileDownList() {
		$ary_get = $this->_get();

		switch ($ary_get['type']) {
			case 'excel':
				header("Content-type:application/force-download;charset=utf-8");
				header("Content-Disposition:attachment;filename=" . $ary_get['file']);
				readfile('./Public/Uploads/' . CI_SN . '/' . $ary_get['type'] . "/" . $ary_get['file']);
				break;
		}
		exit;
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
		$array_result = D("CityRegion")->where(array("cr_parent_id" => $int_parent_id,'cr_status'=>'1'))->order(array("cr_order" => "asc"))->getField("cr_id,cr_name");
		if (false === $array_result) {
			echo json_encode(array("status" => false, "data" => array(), "message" => "无法获取区域数据"));
			exit;
		}
		echo json_encode(array("status" => true, "data" => $array_result, "message" => "success"));
		exit;
	}

	/**
	 * 会员基本设置
	 * @author Terry<wanghui@guanyisoft.com>
	 * @date 2013-07-11
	 * @modify by wanghaoyu 2013-10-15 //开启手机验证
	 */
	public function pageSet(){
		$this->getSubNav(6, 0, 80);
		$verification = D('SysConfig')->getCfg('VERIFICATION_SET','VERIFICATION_STATUS','1','会员登录是否验证');
		$member = D('SysConfig')->getCfg('MEMBER_SET','MEMBER_STATUS','0','自动审核');
		$phone = D('SysConfig')->getCfg('VERIFIPHONE_SET','VERIFIPHONE_STATUS','0','开启手机验证');
		$email = D('SysConfig')->getCfg('VERIFYEMAIL_SET','VERIFYEMAIL_STATUS','0','开启邮箱验证');
		$memberedit = D('SysConfig')->getCfg('MEMBER_EDIT','MEMBER_EDIT_STATUS','1','是否开启会员编辑功能');
        $exitTime = D('SysConfig')->getCfg('HOME_USER_ACCESS', 'EXPIRED_TIME');
		$memberType = D('SysConfig')->getCfg('MEMBER_SET','MEMBER_TYPE','0','会员默认类型');
		$data = array_merge($verification,$member,$phone,$email,$memberedit,$exitTime,$memberType);
		$upgrade = D('SysConfig')->getCfgByModule('MEMBERSUPGRADE_SET');
        $this->assign('upgrade',$upgrade);
		$this->assign($data);
        $this->display();
	}

	/**
	 * 处理会员基本设置
	 * @author Terry<wanghui@guanyisoft.com>
	 * @date 2013-07-11
	 * @ modify by wanghaoyu 2013-10-15  //开启手机验证
	 */
	public function doSet(){
		$ary_post = $this->_post();
		$SysSeting = D('SysConfig');
		foreach ($ary_post as $key=>$val){
			if($key == 'MEMBER'){
				if(false === $SysSeting->setConfig('MEMBER_SET', 'MEMBER_STATUS', $val, '自动审核')){
					$this->error('保存失败');
				}
			}elseif($key == 'MEMBER_TYPE'){
				if(false === $SysSeting->setConfig('MEMBER_SET', 'MEMBER_TYPE', $val, '会员默认类型')){
					$this->error('保存失败');
				}
			}elseif($key == 'VERIFICATION'){
				if(false === $SysSeting->setConfig('VERIFICATION_SET', 'VERIFICATION_STATUS',$val, '会员登录是否验证')){
					$this->error('保存失败');
				}
			}elseif($key == 'MEMBER_EDIT_STATUS'){
                if(false === $SysSeting->setConfig('MEMBER_EDIT', 'MEMBER_EDIT_STATUS',$val, '会员登录是否验证')){
					$this->error('保存失败');
				}
            }elseif($key == 'EXPIRED_TIME'){
                if(false === $SysSeting->setConfig('HOME_USER_ACCESS', 'EXPIRED_TIME',$val, '会员登录存活时间')){
					$this->error('保存失败');
				}
            }else{
				if(false === $SysSeting->setConfig('VERIFIPHONE_SET','VERIFIPHONE_STATUS',intval($val),'开启手机验证')) {
					$this->error('保存失败');
				}else{
					 if($key == 'MEMBERSUPGRADE'){
						if(false === $SysSeting->setConfig('MEMBERSUPGRADE_SET', 'MEMBERSUPGRADE_STATUS', $val, '是否自动晋升会员等级')){
							$this->error('保存失败');
						}
					}
				}
			}
			//是否开启手机验证
			if(false === $SysSeting->setConfig('VERIFIPHONE_SET','VERIFIPHONE_STATUS',intval($ary_post['VERIFIPHONE']),'开启手机验证')) {
				$this->error('是否开启手机验证保存失败');
			}
			//是否开启邮箱验证
			if(false === $SysSeting->setConfig('VERIFYEMAIL_SET','VERIFYEMAIL_STATUS',intval($ary_post['VERIFYEMAIL']),'开启邮箱验证')) {
				$this->error('是否开启邮箱验证保存失败');
			}
		}
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"会员设置",serialize($ary_post)));
		$this->success('保存成功');

	}

	/**
	 * 会员属性项列表
	 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
	 * @date 2013-08-02
	 */
	public function fieldsList(){
		$this->getSubNav(6, 0, 90);
		$int_count =D('MembersFields')->count();
		$obj_page = new Page($int_count, 10);
		$page = $obj_page->show();
		$limit['start']=$obj_page->firstRow;
		$limit['end']=$obj_page->listRows;
		$ary_data = D('MembersFields')->getList('','',$limit);
		$this->assign('ary_data',$ary_data);
		$this->assign("page", $page);
		$this->display();
	}

	/**
	 * 会员属性项添加/修改
	 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
	 * @date 2013-08-02
	 */
	public function fieldsAdd(){
		$this->getSubNav(6, 0, 90);
		$id = $this->_param('id');
		$ary_res=D('MembersFields')->getList(array('id'=>$id));
		if(!empty($ary_res) && !empty($ary_res[0]['fields_content'])){
			$ary_res[0]['fields_content']=explode(",",$ary_res[0]['fields_content']);
		}
		$this->assign('data',$ary_res[0]);
		$this->display();
	}

	/**
	 * 会员属性项添加
	 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
	 * @date 2013-08-02
	 */
	public function doFields(){
		$ary_post = $this->_post();
		if(!empty($ary_post['field_name'])){
			$ary_data['field_name']=$ary_post['field_name'];
		}
		if(!empty($ary_post['dis_order'])){
			$ary_data['dis_order']=$ary_post['dis_order'];
		}
		$ary_data['is_display']=$ary_post['is_display'];
		$ary_data['list_display']=$ary_post['list_display'];
		$ary_data['is_need']=$ary_post['is_need'];
		$ary_data['is_register']=$ary_post['is_register'];
		$ary_data['is_edit']=$ary_post['is_edit'];
		$ary_data['fields_point']=$ary_post['fields_point'];
		if(!empty($ary_post['ary_option'][0])){
			$ary_data['fields_content']=implode(",",$ary_post['ary_option']);
		}else{
			if(!$ary_post['type']){//非系统默认属性
				$ary_data['fields_content']="";
			}
		}
		if(!empty($ary_post['fields_type'])){
			$ary_data['fields_type']=$ary_post['fields_type'];
		}
		if(!empty($ary_post['id'])){
			D('MembersFields')->where(array('id'=>$ary_post['id']))->data($ary_data)->save();
		}else{
			D('MembersFields')->data($ary_data)->add();
		}
		$this->success('保存成功',U('fieldsList'));
	}

	/**
	 * 会员属性项删除
	 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
	 * @date 2013-08-02
	 */
	public function doFieldDel() {
		$ary_data = $this->_param('id');
		$ary_data = explode(",",$ary_data);
		$sys_data = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19);
		$ary_data = array_diff($ary_data,$sys_data);
		if (is_array($ary_data) and count($ary_data)>1) {//批量删除
			$where = array('id' => array('IN', $ary_data));
			$where_info =array('field_id'=> array('IN', $ary_data));
		} elseif(isset($ary_data[0]) && !empty($ary_data[0])) {//单个删除
			$where = array('id' => $ary_data[0]);
			$where_info = array('field_id' => $ary_data[0]);
		}else{
			$this->error('请选择要删除的记录!');
		}
		M('', '', 'DB_CUSTOM')->startTrans();
		$res = D('MembersFields')->where($where)->delete();
		$ary_field_info=D('MembersFieldsInfo')->getList($where_info);
		if(!empty($ary_field_info)){
			$int_res = D('MembersFieldsInfo')->where($where_info)->delete();
			if(!$int_res){
				D('MembersFieldsInfo')->rollback();
				$this->error('删除失败');
				return false;
			}
		}
		if (false == $res) {
			D('MembersFields')->rollback();
			$this->error('删除失败');
			return false;
		} else {
			M('', '', 'DB_CUSTOM')->commit();
			$this->success('删除成功');
		}
		
	}

	/**
	 * 替客户下单
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-09-05
	 */
	public function addOrder() {
		$m_id = $this->_param('m_id');
		if(empty($m_id)){
			$this->error('会员信息不存在');
			return false;
		}
		$ary_members = D('Members')->where(array('m_id'=>$m_id))->find();
		if(empty($ary_members)){
			$this->error('会员信息不存在');
			return false;
		}
		$ary_members['admin_id'] = $_SESSION['Admin'];
		//将会员信息存入session
		session('Members', $ary_members);
		//放入cookie
		/**
		把用户信息存在memcache里面去start
		**/
		//$uniqid = md5(uniqid(microtime()));
		writeMemberCache($uniqid,$ary_member);
		//cookie('session_mid',$uniqid,3600);
		/**
		把用户信息存在memcache里面去end
		**/	
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"替客户下单",'替客户下单:'.$ary_members['m_name'].'-'.$m_id));
		redirect('/Products');	
    }

    /**
     * 更改用户的保证金
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-28
     */
    public function exchangeDeposit(){
		$ary_request = $this->_request();
		if(IS_AJAX){
			$memberModel = D('Members');
			if(!isset($ary_request['mid']) || empty($ary_request['mid']) || !isset($ary_request['deposit']) || empty($ary_request['deposit'])){
				$this->ajaxReturn('','参数错误',0);
			}
			$result = $memberModel->where(array('m_id'=>$ary_request['mid']))->setInc('m_security_deposit',$ary_request['deposit']);
			if(false === $result){
				$this->ajaxReturn('','操作失败',1);
			}else{
				$this->ajaxReturn($ary_request['deposit'],'操作成功',2);
			}
		}else{
			echo "error";exit();
		}
    }

    /* *
     * 导出会员信息到Excel
     * @author Micle <yangkewei@guanyisoft.com>
     * @date 2014-09-01
     */
    public function explortMembersInfo(){
		@set_time_limit(0);  
        @ignore_user_abort(TRUE); 
		ini_set("memory_limit","50M");
        //获取需要导出的会员ID号
        $ary_post = $this->_post();
        if(isset($ary_post['m_ids']) && !empty($ary_post['m_ids'])){
            //获取数据库数据
            $where = array();
			if($ary_post['m_ids']==='ALL'){                           //获取所有用户数据
				$where = 1;
			}else{
				if($ary_post['type'] != 0){
					$where['m_id']    = array('IN',$ary_post['m_ids']);
				}
			}
			if($ary_post['type'] == 0){
				$pages = explode('-',$ary_post['m_ids']);
				if (!empty($ary_post[1]) && $ary_post[1] > 50) {
					$pages[1] = 50;
				}
				$int_start = ($pages[0]-1)*10;
				$int_end = ($pages[1]-$pages[0]+1)*10;
			}
            $ary_data = D('Members')->where($where)->limit($int_start,$int_end)->order(array('m_create_time' => 'desc'))->select();
            foreach($ary_data as &$val){
                if($val['m_mobile'] && strpos($val['m_mobile'],':')){
                    $val['m_mobile'] = decrypt($val['m_mobile']);
                }
                if($val['m_telphone'] && strpos($val['m_telphone'],':')){
                    $val['m_telphone'] = decrypt($val['m_telphone']);
                }
            }
            // print_r($ary_data);exit;
            //将数据转换成Excel输出格式
            if(!empty($ary_data) && is_array($ary_data)){
                $fileExcel  = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';                                       //文件存放目录
                if(!is_dir($fileExcel)) @mkdir($fileExcel,0777,1);
                // !is_dir($fileExcel) && @mkdir($fileExcel,0777,1);
                $export     = new Export(date('YmdHis') . '.xls', $fileExcel);                                  //创建报表对象
                $header     = $this->setExcelHeader();                                                          //报表字段
                $contents   = $this->setExcelContents($ary_data);                                               //报表内容(二维数组)
                $fields     = $this->setExcelFields();      
				$excel_file = 'exportMembers'. date('Y-m-d-H-i-s', time()).'.csv';
				$this->export_csv($contents, $header, $excel_file,$fileExcel);
				if(file_exists($fileExcel.$excel_file)){
					$this->ajaxReturn(array('status' => '1', 'info' => '导出成功', 'data' => $excel_file));
				}else{
					$this->ajaxReturn(array('status' => '0', 'info' => '导出失败'));
				}			
				/**
				//Excel显示列
                $excel_file = $export->exportExcel($header, $contents, $fields, $mix_sheet = '会员导出', true); //生成Excel文件

                if (!empty($excel_file)) {
                    $this->ajaxReturn(array('status'=>'1','info'=>'导出成功','data'=>$excel_file));
                } else {
                    $this->ajaxReturn(array('status'=>'0','info'=>'导出失败'));
                }
				**/
            }
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>'请选择需要导出的会员'));
        }
    }

 	//订单导出
 	function export_csv($data, $title_arr, $file_name = '',$filexcel) {
		$csv_data = '';
		/** 标题 */
		$nums = count($title_arr);
		for ($i = 0; $i < $nums - 1; ++$i) {
			$csv_data .= '"' . $title_arr[$i] . '",';
		}

		if ($nums > 0) {
		$csv_data .= '"' . $title_arr[$nums - 1] . "\"\r\n";
		}
		$file_name = empty($file_name) ? date('Y-m-d-H-i-s', time()) : $file_name;
		if(count($data)>300){
			file_put_contents($filexcel.$file_name, iconv('utf-8', 'GB2312', $csv_data)) ;
			foreach ($data as $k => $row) {
				$csv_data = "";
				for ($i = 0; $i < $nums - 1; ++$i) {
					/**
					if($i == 0){
						$row[$i] = str_replace("\"", "\"\"", $row[$i]);
						$csv_data .= '`'.trim($row[$i]). '`,';						
					}else{
						$row[$i] = str_replace("\"", "\"\"", trim($row[$i]));
						
						$csv_data .= '"' . $row[$i] . '",';					
					}**/
						$row[$i] = str_replace("\"", "\"\"", trim($row[$i]));
						
						$csv_data .= $row[$i] ."\t" . ',';		
				}
				$csv_data .= '"' . $row[$nums - 1] . "\"\r\n";
				unset($data[$k]);
				file_put_contents($filexcel.$file_name, iconv('utf-8', 'GB2312', $csv_data),FILE_APPEND) ;
			}			
		}else{
			foreach ($data as $k => $row) {
				for ($i = 0; $i < $nums - 1; ++$i) {
					if($i == 0){
						$row[$i] = str_replace("\"", "\"\"", $row[$i]);
						$csv_data .= '`'.trim($row[$i]). '`,';						
					}else{
						$row[$i] = str_replace("\"", "\"\"", trim($row[$i]));
						
						$csv_data .= '"' . $row[$i] . '",';					
					}
				}
				$csv_data .= '"' . $row[$nums - 1] . "\"\r\n";
				unset($data[$k]);
			}	
			file_put_contents($filexcel.$file_name, iconv('utf-8', 'GB2312', $csv_data)) ;
		}
	} 
    /* * 
     * 导出会员报表选项框
     * @author Micle <yangkewei@guanyisoft.com>
     * @date 2014-09-04
     */
    public function getMembersDialog(){
        $this->display();
    }

    /* *
     * 报表字段
     */
    private function setExcelHeader(){
        return array(
            '会员ID号','会员名','会员真实姓名','性别','所属地区',
            '详细地址','出生日期','邮政编码','手机号码','固定电话',
            '邮箱','会员等级名称','旺旺号','QQ号','网站地址',
            '会员状态','账户余额','第三方登录唯一标示ID','累计消费额','当前总积分',
            '当前冻结积分','账户创建日期','账户最后登录日期','账户最近更新日期','推荐人',
            '押金','支付宝账户','银行账户','是否代理下单审核','是否申请代理商',
            '登录方式','子公司ID','红包金额','会员类型'
        );
    }

    /* *
     * 生成报表对应列
     */
    private function setExcelFields(){
        return array(
            'A','B','C','D','E',
            'F','G','H','I','J',
            'K','L','M','N','O',
            'P','Q','R','S','T',
            'U','V','W','X','Y',
            'Z','AA','AB','AC','AD',
            'AE','AF','AG','AH'
        );
    }

    /* *
     * 处理获取的数据库数据
     */
    private function setExcelContents($ary_data){
        $contents = array();
        foreach($ary_data as $key=>$val){
			$val['m_sex']          = $this->showStrSex($val['m_sex']);                 //用户性别 '0'为女,'1'为男,'2'为保密
			$val['cr_id']          = $this->showStrAddress($val['cr_id']);             //用户所属地区
			$val['m_verify']       = $this->showStrStatus($val['m_verify']);           //用户审核状态
			$val['m_order_status'] = $val['m_order_status']? '是' : '否';              //用户是否代理下单审核
			$val['is_proxy']       = $val['is_proxy']? '是' : '否' ;                   //用户是否申请代理商
			$val['login_type']     = $val['login_type']? '第三方登录' : '普通登录';    //用户登录方式
			$val['m_type']         = $this->showStrType($val['m_type']);               //用户类型 1批发商,2供货商,0普通会员
			// 获取等级
			if($val['ml_id']!=""){
				$level_res = D('members_level')->field('ml_name')->where(array('ml_id'=>$val['ml_id']))->find();
				if(isset($level_res['ml_name']) && $level_res['ml_name']!=""){
					$level =$level_res['ml_name'];
				}else{
					$level ='';
				}
			}
            // $contents[] = $val;
            $contents[] = array(
				$val['m_id'],$val['m_name'],$val['m_real_name'],$val['m_sex'],$val['cr_id'],
				$val['m_address_detail'],$val['m_birthday'],$val['m_zipcode'],$val['m_mobile'],$val['m_telphone'],
				$val['m_email'],$level,$val['m_wangwang'],$val['m_qq'],$val['m_website_url'],
				$val['m_verify'],$val['m_balance'],$val['thd_guid'],$val['m_all_cost'],$val['total_point'],
				$val['freeze_point'],$val['m_create_time'],$val['m_last_login_time'],$val['m_update_time'],$val['m_recommended'],
				$val['m_security_deposit'],$val['m_alipay_name'],$val['m_balance_name'],$val['m_order_status'],$val['is_proxy'],
				$val['login_type'],$val['m_subcompany_id'],$val['m_bonus'],$val['m_type']
			);
        }
        return $contents;
    }

    /* *
     * 会员性别转换成相应字符串数据
     * @$data INT 保存于数据库的性别值0,1,2
     * @return String 返回相应性别取值 '女','男','保密'
     * @author Micle yangkewei@guanyisoft.com  
     * @date 2014-09-02
     */
    private function showStrSex($data){
        switch($data){
            case 0 : return '女';break;
            case 1 : return '男';break;
            case 2 : return '保密';break;
            default : return '保密';
        }
    }
    
    /* *
     * 会员所属地区转换成相应字符串数据
     * @$data INT 保存于数据库最后一级地区编号
     * @return String 返回相应所属地区省级+市级
     * @author Micle yangkewei@guanyisoft.com  
     * @date 2014-09-04
     */
    private function showStrAddress($data){
        $array_region = D('CityRegion')->getCityRegionInfoByLastCrId($data);
        $where['cr_id'] = array('IN',array($array_region['province'],$array_region['city']));
        $ary_addr = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->field('cr_name')->where($where)->select();
        $str_addr = $ary_addr[0]['cr_name'] . ' ' . $ary_addr[1]['cr_name'];
        return $str_addr;
    }
    
    /* *
     * 会员审核状态转换成相应字符串数据
     * @$data INT 保存于数据库的用户审核状态 0为未审核，1为审核中，2为审核通过，3为审核未通过,4待审核
     * @return String 返回相应字符串数据
     * @author Micle yangkewei@guanyisoft.com  
     * @date 2014-09-04
     */   
    private function showStrStatus($data){
        switch($data){
            case 0 : return '未审核';break;
            case 1 : return '审核中';break;
            case 2 : return '审核通过';break;
            case 3 : return '审核未通过';break;
            case 4 : return '待审核';break;
            default: return '未审核';
        }
    }
    /* *
     * 会员类型转换成相应字符串数据
     * @$data INT 保存于数据库的用户类型
     * @return String 返回相应字符串数据
     * @author Micle yangkewei@guanyisoft.com  
     * @date 2014-09-04
     */ 
    private function showStrType($data){
        switch($data){
            case 0 : return '普通会员';break;
            case 1 : return '批发商';break;
            case 2 : return '供货商';break;
            default: return '普通会员';
        }
    }

    /**
     * 显示完整的手机号
     * 买家查看订单详情中的手机号码
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-02-06
     */
    public function showMobile(){
        $m_id = $this->_post('mid');
        $resault['m_mobile'] = '';
        if(!empty($m_id) || is_numeric($m_id)){
            $m_mobile = D('Members')->where(array('m_id'=>$m_id))->getField('m_mobile');
            if(!empty($m_mobile) && strpos($m_mobile,':')){
                $resault['m_mobile'] = decrypt($m_mobile);
                $this->ajaxReturn($resault);
            }elseif(!empty($m_mobile)){
                $resault['m_mobile'] = $m_mobile;
                $this->ajaxReturn($resault);
            }
        }
    }

    /**
     * 批量设置会员类型
     * @author hcaijin
     * @date 2015-12-04
     */
    public function doBacthSetype(){
        $int_m_type = $this->_post('m_type');
        $int_m_id = $this->_post('m_id');
        if(!empty($int_m_id) && isset($int_m_type)){
            $res_mem = D('Members')->where(array('m_id'=>array('in',$int_m_id)))->save(array('m_type'=>$int_m_type));
            if(!$res_mem){
                $this->error('设置失败');
            }else{
                $this->success('设置成功');
            }
        }else{
            $this->error('设置失败');
        }
    }
    /**合伙人列表
    **/

    public function partnerList(){
    	$where = '';
    	$int_count = M('partner', C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->count();
    	//echo $int_count;
		$obj_page = new Page($int_count, 10);
		$page = $obj_page->show();
		$ary_partner = D('Members')->partnerInfo($obj_page->firstRow, $obj_page->listRows, $ary_where = $where, $ary_field = '', $ary_orders = array('p_id' => 'desc'));
		// 分配显示
		$this->assign('ary_partner',$ary_partner);
		$this->assign("page", $page);
   		echo $int_count;
		//print_r($ary_partners);
    	$this->display();
    }
    /*供应商列表
    */
    public function supplierList(){
    	$where = '';
    	$int_count = M('supplier', C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->count();
    	//echo $int_count;
		$obj_page = new Page($int_count, 10);
		$page = $obj_page->show();
		$ary_supplier = D('Members')->supplierInfo($obj_page->firstRow, $obj_page->listRows, $ary_where = $where, $ary_field = '', $ary_orders = array('s_id' => 'desc'));
		// 分配显示
		if ($supplier.s_business_photo==null) {
			
		}
		$this->assign('ary_supplier',$ary_supplier);
		$this->assign("page", $page);
   		echo $int_count;
    	$this->display();
    }
    public function doSupplierCheck() {
			$data = $this->_post();
			$data['s_update_time'] = date("Y-m-d H:i:s");
			unset($data['m_id']);
		if ($data['s_status'] == '1') {
			$data['s_status'] = 0;
		} else {
			$data['s_status'] = 1;
		}
        	$result = D('Members')->UpdateSupplier($this->_post('m_id'),$data);
        	if ($result) {
				echo 'success';
				} else {
				echo 'failure';
			}	


		}
}
