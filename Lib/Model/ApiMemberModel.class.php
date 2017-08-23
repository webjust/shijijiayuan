<?php

/**
 * 会员模型层 Model
 * @package Model
 * @version 7.2
 * @author czy
 * @date 2012-12-13
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiMemberModel extends GyfxModel {

    /**
    * 对象
    * @var obj
    */
    private $member;

	//private $warehouse_delivery_area;
	//private $warehouse_stock;
	/**
	 * 构造方法
	 * @author czy <chenzongyao@guanyisoft.com>
	 * @date 2012-12-14
	 */
	 
    protected $item_map=array(
         'id'=>'m_id',
         'guid'=>'thd_guid',
         'name'=>'m_name',
         'sex'=>'m_sex',
         'real_name'=>'m_real_name',
         'birthday'=>'m_birthday',
         'email'=>'m_email',
         'mobile'=>'m_mobile',
         'address'=>'m_address_detail',
         'grade'=>'fx_members_level.ml_code',
         'point'=>'total_point',
         'balance'=>'m_balance',
         'security_deposit'=>'m_security_deposit',
         'create_time'=>'m_create_time',
         'update_time'=>'m_update_time',
         'status'=>'m_status',
         'qq'=>'m_qq',
         'wangwang'=>'m_wangwang',
         'website_url'=>'m_website_url',
         'recommended'=>'m_recommended',
         'alipay_name'=>'m_alipay_name',
         'is_proxy'=>'is_proxy',
         'zipcode'=>'m_zipcode',
         'm_telphone'=>'m_telphone',
         'all_cost'=>'m_all_cost',
         'is_verify'=>'m_verify',
         'order_status'=>'m_order_status',
         'status'=>'m_status',
         'card_no'=>'m_card_no',
         'ali_card_no'=>'m_ali_card_no',
         'password'=>'m_password',
		 'shop_code'=>'shop_code',
         'head_url'=>'head_url'
     );

    protected $item_map_info=array(
        'id'=>'m_id',
        'guid'=>'thd_guid',
        'name'=>'m_name',
        'sex'=>'m_sex',
        'real_name'=>'m_real_name',
        'birthday'=>'m_birthday',
        'email'=>'m_email',
        'mobile'=>'m_mobile',
        'address'=>'m_address_detail',
        'province'=>'sheng_cr_id',
        'city'=>'shi_cr_id',
        'district'=>'qu_cr_id',
        'grade'=>'fx_members_level.ml_code',
        'point'=>'total_point',
        'balance'=>'m_balance',
        'security_deposit'=>'m_security_deposit',
        'create_time'=>'m_create_time',
        'update_time'=>'m_update_time',
        'status'=>'m_status',
        'qq'=>'m_qq',
        'wangwang'=>'m_wangwang',
        'website_url'=>'m_website_url',
        'recommended'=>'m_recommended',
        'alipay_name'=>'m_alipay_name',
        'is_proxy'=>'is_proxy',
        'zipcode'=>'m_zipcode',
        'm_telphone'=>'m_telphone',
        'all_cost'=>'m_all_cost',
        'is_verify'=>'m_verify',
        'order_status'=>'m_order_status',
        'status'=>'m_status',
        'card_no'=>'m_card_no',
        'ali_card_no'=>'m_ali_card_no',
    );

    private $result = array(
        'code'    => '10202',       // 会员错误初始码
        'sub_msg' => '会员API错误', // 错误信息
        'status'  => false,         // 返回状态 : false 错误,true 操作成功.
        'info'    => array(),       // 正确返回信息
        );
        
    public function __construct() {

        parent::__construct();
        $this->member = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $this->cityregion = M('city_region', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->warehouse_delivery_area = M('warehouse_delivery_area', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->warehouse_stock = M('warehouse_stock', C('DB_PREFIX'), 'DB_CUSTOM');
    }

    /**
    * 查询会员信息
    * detail:
    * request params
    * @start_modified 	Date 	必须 	2000-01-01 00:00:00
    * @author zhuyuanjie@guanyisoft.com
    * @date 2013-08-02
    */
    public function MemberGet($array_params=array()) {
        $ary_fields = $array_params['fields'];
        $real_fields = $ary_fields = explode(',',$ary_fields);
        $keys_city = array_keys($ary_fields,'city');
        $keys_province = array_keys($ary_fields,'province');
        $keys_district = array_keys($ary_fields,'district');
        $keys_cr = array_merge($keys_city,$keys_province,$keys_district);
        foreach($keys_cr as $k){
            unset($ary_fields[$k]);
        }
        $fields = $this->parseFieldsMapToReal($ary_fields);
        foreach($fields as $i => $val){
            if($val == 'head_url'){
                unset($fields[$i]);
                $head_url = $val;
            }
        }
        $fields = implode(',',$fields);
        $fields .=',cr_id,m_id';
        //print_r($this->item_map);exit;
        $ary_where = '';
        $ary_order = '';
        $ary_page_no = 1;
        $ary_page_size = 10;
        $ary_orderby = '';
        if (isset($array_params["condition"]) && !empty($array_params["condition"])) {
            $ary_where['_string'] = mb_convert_encoding($array_params["condition"],"utf-8","gb2312");
            foreach($this->item_map as $key=>$val){
                if(strstr($ary_where['_string'],$key))
                {
                     $ary_where['_string'] = str_replace($key,$val,$ary_where['_string']);
                }	
            }
        }
        if (isset($array_params["page_no"]) && !empty($array_params["page_no"])) {

            $ary_page_no = $array_params["page_no"];
        }
        if (isset($array_params["page_size"]) && !empty($array_params["page_size"])) {

            $ary_page_size = $array_params["page_size"];
        }
        foreach($this->item_map as $key=>$val){
                if(strstr($val,$array_params["orderby"]))
                {
                     $array_params["orderby"] = str_replace($key,$val,$array_params["orderby"]);
                }	
        }
        //dump($array_params["orderby"]);die();
        if (isset($array_params["orderby"]) && !empty($array_params["orderby"])) {

            if (isset($array_params["orderbytype"]) && !empty($array_params["orderbytype"])) {

                $ary_orderby = $array_params["orderby"].' '.$array_params["orderbytype"];
            }else{
        $ary_orderby = $array_params["orderby"].' ASC';
            }
        }
        $member_list=$this->member
        ->field($fields)
        ->join('fx_members_level on(fx_members_level.ml_id=fx_members.ml_id)')
        ->where($ary_where)
        ->limit(($ary_page_no-1)*$ary_page_size,$ary_page_size)
        ->order($ary_orderby)
        ->select();
        $member_count=$this->member
        ->join('fx_members_level on(fx_members_level.ml_id=fx_members.ml_id)')
        ->where($ary_where)
        ->count();

        $items=array();
        $this->_map = $this->item_map_info;
        $cityregion = D('CityRegion');

        foreach($member_list as $key => &$item){
            $items[] = D('ApiMember')->parseFieldsMap($item);
            $cityinfo = $cityregion->getCityRegionInfoByLastCrId($item['cr_id']);
            if(array_keys($real_fields,'province'))
            $items[$key]['province'] = $cityinfo['province'];
            if(array_keys($real_fields,'city'))
            $items[$key]['city'] = $cityinfo['city'];
            if(array_keys($real_fields,'district'))
            $items[$key]['district'] = $cityinfo['region'];
            unset($items[$key]['cr_id']);
            if($head_url == 'head_url'){
                $items[$key]['head_url'] =M('MembersFieldsInfo',C('DB_PREFIX'),'DB_CUSTOM')->where(array('u_id'=>$item['m_id'],'field_id'=>19))->getField('content');
            }
            $items[$key]['m_mobile'] = strpos($items[$key]['m_mobile'],':') ? decrypt($items[$key]['m_mobile']) : $items[$key]['m_mobile'];
            $items[$key]['m_telphone'] = strpos($items[$key]['m_telphone'],':') ? decrypt($items[$key]['m_telphone']) : $items[$key]['m_telphone'];
            if(array_keys($real_fields,'grade'))
            $items[$key]['grade'] = $item['ml_code'];
            unset($items[$key]['ml_code']);
            if(array_keys($real_fields,'is_proxy'))
            $items[$key]['is_proxy'] = $item['is_proxy'];			
        }
        return array('count'=>$member_count,'items'=>$items);
    }

    /**
    * 添加会员信息
    * detail:
    * request params
    * @start_modified 	Date 	必须 	2000-01-01 00:00:00
    * @author zhuyuanjie@guanyisoft.com
    * @date 2013-08-02
    */
    public function MemberAdd($array_params=array()) {
        $data = $this->parseFields($this->item_map,$array_params);
        $where = array();
        $where['_logic'] = 'or';
        $where['m_name'] = $data['m_name'];
        $where['m_email'] = $data['m_email'];
        $rs = $this->member->where($where)->select();
        if(empty($rs)){
            $data['m_create_time'] = date('Y-m-d H:i:s');
            if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
				$area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
				$cwhere = array('cr_name'=>$area_name);
				$cres = $this->cityregion->where($cwhere)->find();
				$data['cr_id'] = $cres['cr_id'];
            }
			$data['m_telphone'] = !empty($data['m_telphone']) ? encrypt($data['m_telphone']) : '';
			$data['m_mobile'] = !empty($data['m_mobile']) ? encrypt($data['m_mobile']) : '';
            $memberaddrs = $this->member->add($data);
            if(!empty($memberaddrs)){
				return array('created'=>$data['m_create_time'],'name'=>$data['m_name']);
            }
        }
        return false;
    }

    /**
    * 修改会员信息
    * detail:
    * request params
    * @start_modified 	Date 	必须 	2000-01-01 00:00:00
    * @author zhuyuanjie@guanyisoft.com
    * @date 2013-08-02
    */
    public function MemberUpdate($array_params=array()) {
        $data = $this->parseFields($this->item_map,$array_params);
        $where = array();
        $where['m_name'] = $data['m_name'];
        $rs = $this->member->where($where)->select();
        if(!empty($rs)){
            $data['m_update_time'] = date('Y-m-d H:i:s');
            if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
				$area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
				$cwhere = array('cr_name'=>$area_name);
				$cres = $this->cityregion->where($cwhere)->find();
				$data['cr_id'] = $cres['cr_id'];
            }
			$data['m_mobile'] = empty($data['m_mobile']) ? '' : encrypt($data['m_mobile']);
            $data['m_telphone'] = empty($data['m_telphone']) ? '' : encrypt($data['m_telphone']);
            $memberupdaters = $this->member->where($where)->save($data);
            if($memberupdaters !== false){
				return array('created'=>$data['m_update_time'],'name'=>$data['m_name']);
            }
        }
        return false;
    }

    /**
    * 批量处理会员信息
    * request params
    * @author Hcaijin
    * @date 2014-07-28
    */
    public function MemberBatchEdit($array_params=array()){
        $int_flag = false;
        $ary_data = array('msg'=>'','data'=>array());
        $members = json_decode($array_params['members_list'],1);
        if(empty($members)){
            return array('msg'=>'members_list字段有误');
        }
        //事物开始
        $obj_trans = M ( '', C ( '' ), 'DB_CUSTOM' );
        $obj_trans->startTrans ();
        $ary_members = array();
        foreach($members as $member){
            $data = $this->parseFields($this->item_map,$member);
            $where = array();
            $where['m_name'] = $data['m_name'];
            $rs = $this->member->where($where)->select();
            if(!empty($rs)){
                $data['m_update_time'] = date('Y-m-d h:i:s');
                if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
                    $area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
                    $cwhere = array('cr_name'=>$area_name);
                    $cres = $this->cityregion->where($cwhere)->find();
                    $data['cr_id'] = $cres['cr_id'];
                }
                $memberupdaters = $this->member->where($where)->save($data);
                //echo $this->member->getLastSql()."<br/>";
                if($memberupdaters !== false){
                    $int_flag = true;
                    $ary_members[] = array('created'=>$data['m_update_time'],'name'=>$data['m_name']);
                }else{
                    $obj_trans->rollback ();
                    $ary_data = array('msg'=>'批量修改会员异常！');
                    return $ary_data;
                }
            }else{
                $data['m_create_time'] = date('Y-m-d h:i:s');
                if(isset($array_params["area_name"]) && !empty($array_params["area_name"])){
                    $area_name = mb_convert_encoding($array_params['area_name'],"utf-8","gb2312");
                    $cwhere = array('cr_name'=>$area_name);
                    $cres = $this->cityregion->where($cwhere)->find();
                    $data['cr_id'] = $cres['cr_id'];
                }
                $memberaddrs = $this->member->add($data);
                if(!empty($memberaddrs)){
                    $int_flag = true;
                    $ary_members[] = array('created'=>$data['m_create_time'],'name'=>$data['m_name']);
                }else{
                    $obj_trans->rollback ();
                    $ary_data = array('msg'=>'批量新增会员异常！');
                    return $ary_data;
                }
            }
        }
        $obj_trans->commit ();
        if($int_flag) {
            $ary_batch_edit_response = array();
            $ary_batch_edit_response['member_infos']['member_info'] = $ary_members;
            return $ary_batch_edit_response;
        }
        else return $ary_data;
    }

    /**
     * 获取促销信息
     */
    function PromotionsGet($array_params){
        $result = array('status'=>0,'msg'=>'');
        $data = $this->parseFields($this->item_map,$array_params);
        $where = array();
        $where['m_name'] = $data['m_name'];
        $res_data = $this->member->where($where)->select();
        if(!empty($res_data)){
            $result = array('status'=>1,'point'=>$data['total_point'],'financial'=>$res_data['m_balance'],'bonus'=>$res_data['m_bonus'],'jlb'=>$res_data['m_jlb']);
        }else{
            $result = array('status'=>0,'msg'=>'获取会员数据失败！');
        }
        return $result;
    }

    /**
     * 获取优惠券信息
     */
    function CouponGet($array_params){
        $result = array('status'=>0,'msg'=>'');
        $data = $this->parseFields($this->item_map,$array_params);
        $m_where['m_name'] = $data['m_name'];
        $res_data = $this->member->where($m_where)->select();
        if(!empty($res_data)){
            $where = array();
            $date = date('Y-m-d');
            $where['c_user_id']=$res_data['m_id'];
            $where['c_is_use']=0;
            $where['c_create_time']=array('elt',$date);
            //获取会员所有优惠券总数量
            $total =  D('Coupon')->where($where)->count();
            //获取会员所有优惠券列表
            $couponList = D('Coupon')->where($where)->order('c_create_time asc')->select();

            $result = array('status'=>1,'total_results'=>$total,'coupon_list'=>$couponList);
        }else{
            $result = array('status'=>0,'msg'=>'获取会员数据失败！');
        }
        return $result;
    }

    /**
    * 处理字段映射
    * return array
    */
    private function parseFields($array_table_fields,$array_client_fields){
        $aray_fetch_field = array();
        foreach($array_client_fields as $field_name => $as_name){
            if(isset($array_table_fields[$field_name]) && !empty($as_name)){
                $aray_fetch_field[$array_table_fields[$field_name]] = trim($as_name);

            }
        }
        if(empty($aray_fetch_field)){
            return null;
        }
        return $aray_fetch_field;
    }
    
    /**
     * 会员登录API
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-2
     */
    public function doLogin($params){
        $memberModel = D('Members');
        $ary_member=$memberModel->where(array('m_name'=>$params['m_name'],'m_password'=>$params['m_password']))->find();
		if(!$ary_member){
			//手机号
			if($params['login_type'] == '1'){
				$ary_member=$memberModel->where(array('m_mobile'=>encrypt($params['m_name']),'m_password'=>$params['m_password']))->find();
			}
			//邮箱
			if($params['login_type'] == '2'){
				$ary_member=$memberModel->where(array('m_email'=>$params['m_name'],'m_password'=>$params['m_password']))->find();
			}		
			//手机号或邮箱
			if($params['login_type'] == '3'){
				$ary_member=$memberModel->where(array('m_mobile'=>encrypt($params['m_name']),'m_password'=>$params['m_password']))->find();
				if(!$ary_member){
					$ary_member=$memberModel->where(array('m_email'=>$params['m_name'],'m_password'=>$params['m_password']))->find();
				}
			}				
		}
        if(!$ary_member){
            $this->result['sub_msg'] = '账号或密码错误！';
            return $this->result;
        }
        if(!empty($ary_member) && $ary_member['m_status'] == '0'){
            $this->result['sub_msg'] = '帐号未激活，请联系管理员！';
            return $this->result;
        }
        if(!empty($ary_member) && $ary_member['m_verify'] == '0'){
            $this->result['sub_msg'] = '管理没有审核，请稍后登入！';
            return $this->result;
        }
        //登陆成功判断是否开启积分
        $pointCfg = D('PointConfig')->getConfigs();
        //echo $pointCfg['login_points'];die;
        if($pointCfg['is_consumed'] == '1' && $pointCfg['login_points'] > 0){
            //判断今天是否已登陆一次
            $ary_where = array();
            $ary_where['u_create'] = array(between,array(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')));
            $ary_where['type'] = 13;
            $ary_where['m_id'] = $ary_member['m_id'];
            $point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
            if(empty($point_exsit)){
                D('PointConfig')->setMemberRewardPoints($pointCfg['login_points'],$ary_member['m_id'],13);
            }
        }
        // $ary_member['order_num'] = D('Orders')->getOrdersNumByMid($ary_member['m_id']);
        $ary_member['order_num'] = D('Orders')->where(array('m_id'=>$ary_member['m_id']))->count();
        //查询会员头像
        $ary_members_fields = D("Gyfx")->selectAllCache("members_fields","id,fields_type,field_name",array('is_display'=>1));
        foreach($ary_members_fields as $field){
            if($field['fields_type']=='file' && strstr($field['field_name'],'头像')){
                $fields_where = array('u_id'=>$ary_member['m_id'],'field_id'=>$field['id']);
                $ary_member['headPic'] = D("Gyfx")->selectOneCache("members_fields_info","content",$fields_where);
                break;
            }
        }
        $this->result['code'] = 10203;
        $this->result['sub_msg'] = '登录成功';
        $this->result['status'] = true;
        $this->result['info'] = $ary_member;
        return $this->result;
    }

    /**
     * 执行上传图片到远程服务器方法 这里主要APP上传头像用
     */
    function doUploadPic($ary_data){
        $res_result = false;
        $save_data = array(
            'status'=>1
        );
        $where = array(
            'm_id' => $ary_data['mid'],
            'm_status' => 1
        );
        $res_mid = D("Gyfx")->selectOneCache("members","m_id",$where);
        if(!$res_mid['m_id']){
            $this->result['sub_msg'] = '会员不存在或已被停用!';
            return $this->result;
        }
        $save_data['content'] = ltrim($this->ActionBuild($ary_data['upPic']),'.');
        if($save_data['content'] == false){
            $this->result['sub_msg'] = '上传头像失败!';
            return $this->result;
        }
        $ary_members_fields = D("Gyfx")->selectAllCache("members_fields","id,fields_type,field_name",array('is_display'=>1));
        foreach($ary_members_fields as $field){
            if($field['fields_type']=='file' && strstr($field['field_name'],'头像')){
                $fields_where = array('u_id'=>$res_mid['m_id'],'field_id'=>$field['id']);
                $res_info= D("Gyfx")->selectOneCache("members_fields_info","content",$fields_where);
                //已存在就更新，否则新增
                if($res_info){
                    $res_result = D("MembersFieldsInfo")->where($fields_where)->save($save_data);
                }else{
                    $save_data['u_id'] = $ary_data['mid'];
                    $save_data['field_id'] = $field['id'];
                    $res_result = D("MembersFieldsInfo")->data($save_data)->add();
                }
                //删除获取头像缓存
                $obj_query = M('members_fields_info',C('DB_PREFIX'),'DB_CUSTOM')->where($fields_where)->field('content');
                D("Gyfx")->deleteQueryCache($obj_query,'find');
                break;
            }
        }
        if($res_result){
            $this->result['sub_msg'] = '修改头像成功。';
            $this->result['status'] = true;
            $this->result['code'] = '10403';
            $this->result['info'] = array('result'=>'SUCCESS');
        }else{
            $this->result['sub_msg'] = '上传头像失败!';
        }
        return $this->result;
    }

    public function ActionBuild($img_data){
        // 获取文件类型(正则)
        $type=preg_replace("/^data:image\/([^;]*);base64,.*/is","\$1",$img_data);

        $pic_content=preg_replace("/data:image\/[^;]*;base64,/is","",$img_data);
        $s = base64_decode($pic_content);
        if(empty($s)) return false;
        $allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
        $type = $type == 'jpeg' ? 'jpg' : $type;
        if(!in_array($type,$allowExts)) return false;

        $file_name = mt_rand(1111111111,9999999999).'.'.$type;
        $path = './Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/';
        if(!file_exists($path)){
            @mkdir('./Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/', 0777, true);
        }
        $real_dir = $path.$file_name;
        file_put_contents($real_dir,$s);
        return $real_dir;
    }
}

