<?php 
class MobileApiAction extends HomeAction {  
	
	//商品列表
	public function appGoodsList(){
		//定义一个数组来储存数据
		$result = array();
		//分类ID
		$cid   = $_POST["cid"];
		$pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);
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
			$field = 'g_id,g_name,g_picture,g_price,g_salenum';
			$glist = D('ViewGoods')->field($field)
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
		$gid       = $_POST["g_id"];
		$m_id      = $_POST["m_id"];
		
		//
		$ginfo     = M("GoodsInfo");
		$goods     = M("Goods");
		$gpicsinfo = M("GoodsPictures");
		$gcomment  = M("GoodsComments");
		$members   = M("MembersFieldsInfo");
		$colgoods  = M("CollectGoods");
		$rg        = M("RelatedGoodsSpec");
		$relgoods  = M();
		
		//商品信息
		$ginfofield = "g_id,g_name,g_price,g_market_price,g_desc,g_picture,g_salenum,g_stock,g_create_time";
		$gdinfo = $ginfo->field($ginfofield)->where(array("g_id"=>$gid))->find();
		$ginfoa = $goods->field()->where(array("g_id"=>$gid))->find();
		$gdinfo["goods_type"] = "0";
		//货品ID
		$pdt_id = M("GoodsProducts")->field("pdt_id")->where(array("g_id"=>$gid))->find();
		
		//商品销售属性，颜色分类
		$colorfield = "fx_related_goods_spec.pdt_id,fx_related_goods_spec.g_id,fx_related_goods_spec.gsd_id,fx_related_goods_spec.gsd_aliases,fx_related_goods_spec.gsd_picture,a.pdt_sale_price,a.pdt_market_price,a.pdt_stock";
		$colorcat   = $rg->field($colorfield)->join("fx_goods_products as a on a.pdt_id=fx_related_goods_spec.pdt_id")
							                 ->where("fx_related_goods_spec.g_id={$gid} AND fx_related_goods_spec.gs_id=888")
								             ->select();
		
		//推荐商品
		$glist   = M("Ad")->field()->where(array("n_position"=>"rcde"))->order("n_order asc")->select();
		foreach($glist as $keys=>$vals){
			$relgoodsinfo = M("GoodsInfo")->field("g_price,g_salenum")->where("g_id={$vals['n_gid']}")->find();
			$glist[$keys]["g_price"]   = $relgoodsinfo["g_price"];
			$glist[$keys]["g_salenum"] = $relgoodsinfo["g_salenum"];
		}
		
		//商品相册
		$gpicsdata    = $gpicsinfo->field("gp_id,gp_picture")->where(array("g_id"=>$gid))->select();
		
		//商品评论列表
		// $commentfield = 'gcom_content,u_pic,m_id,gcom_update_time,gcom_mbname,gcom_pics,gcom_star_score,gcom_title,g_id,gcom_id';
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
		    print_r(json_encode($result));
		    die;
		}
	}

	public function GetProvinces(){
		///定义一个数组来储存数据
        $result = array();
        $MobileApi     = D("MobileApi");

        $field = 'cr_id as ProvinceCode,cr_name as ProvinceName';
        $cr_parent_id = '1';
        $provinceList = $MobileApi->GetChildCityListByParentCityId($cr_parent_id,$field);
        
        for ($i=0; $i < count($provinceList); $i++) { 
            $field = 'cr_id as CityCode,cr_name as CityName';
        	$cr_parent_id = $provinceList[$i]["ProvinceCode"];
            $cityList = $MobileApi->GetChildCityListByParentCityId($cr_parent_id,$field);
            $provinceList[$i]["CityList"] = $cityList;

            for ($j=0; $j < count($cityList); $j++) { 
            	$field = 'cr_id as DistrictCode,cr_name as DistrictName';
            	$cr_parent_id = $cityList[$j]["CityCode"];
                $districtList = $MobileApi->GetChildCityListByParentCityId($cr_parent_id,$field);
                $provinceList[$i]["CityList"][$j]["DistrictList"] = $districtList;
            }
        }

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$body["provinces"] = $provinceList;
		$result["body"] = $body;
		print_r(json_encode($result));
		die;	

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
		$m_id = $_POST["m_id"];
		
		$city = D("CityRegion");
        //$ary_city = $city->getCurrLvItem(1);
		
		$addr = D("ReceiveAddress");
		$field = "fx_receive_address.ra_id,fx_receive_address.ra_name,fx_receive_address.ra_mobile_phone,fx_receive_address.cr_id,fx_receive_address.ra_detail,fx_receive_address.ra_is_default,fx_receive_address.ra_post_code,fx_receive_address.ra_id_card";
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
		
			//插入数据
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
		$ra_mobile_phone = $_POST["ra_mobile_phone"];
		$ra_detail       = $_POST["ra_detail"];
		$ra_post_code    = $_POST["ra_post_code"];
		$ra_id_card      = $_POST["ra_id_card"];
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
			$addrdata["ra_post_code"]    = $ra_post_code;
			$addrdata["ra_id_card"]      = $ra_id_card;
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
     * APP 会员中心 头像上传
	 * @param string $m_id  会员ID
	 * @param array $touxiang  头像信息
     * @time 2019-12-19
     */
	public function PageCategoryData() {

		$result = array();
        $MobileApi     = D("MobileApi");

        $field = 'gc_id,gc_name';
        $gc_parent_id = '0';
        $list_level0 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
        
        for ($i=0; $i < count($list_level0); $i++) { 
        	$gc_parent_id = $list_level0[$i]["gc_id"];
            $list_level1 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
            $list_level0[$i]["childList"] = $list_level1;

            for ($j=0; $j < count($list_level1); $j++) { 
            	$gc_parent_id = $list_level1[$j]["gc_id"];
                $list_level2 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
                $list_level0[$i]["childList"][$j]["childList"] = $list_level2;
            }
        }

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(12)->select();

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["catedata"] = $list_level0;
		$result["brandList"] = $BrandList;
		print_r(json_encode($result));
		die;	
	}

	public function GetBrandList() {

		$result = array();

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->select();

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["BrandList"] = $BrandList;
		print_r(json_encode($result));
		die;	
	}

	public function appWXUserLogin(){

		$result = array();
        $member = D('Members');

        $open_id = $_POST["open_id"];
        $unionid = $_POST["unionid"];
        $nickname = $_POST["nickname"];
        $open_token = $_POST["open_token"];
        $gender = $_POST["gender"];
        $sex = $_POST["sex"];
        $headimgurl = $_POST["headimgurl"];

        $ary_member = $member->getInfo('',$open_id,$unionid);

        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        if(!isset($ary_member['open_id'])){
            //默认等级
            $ml = D('MembersLevel')->getSelectedLevel();
            //新增用户
            $add_member = array();
            $add_member['m_name'] = 'WX_'.$nickname;
            $add_member['open_name'] = $nickname;
            $add_member['open_id'] = $open_id;
            $add_member['unionid'] = $unionid;
            $add_member['open_token'] = $open_token;
            $add_member['open_source'] = 'WX';
            $add_member['ml_id'] = $ml;
            $add_member['login_type'] = 1;
            $add_member['m_create_time'] = date('Y-m-d H:i:s');
			//$add_member['m_status'] = 1; //为启用
            $data = D('SysConfig')->getCfgByModule('MEMBER_SET');

            if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
                $add_member['m_verify'] = '2';

            }
            if($gender == '男' || $sex == '1'){

                $add_member['m_sex'] = 1;

            }else{
                $add_member['m_sex'] = 0;
            }
            if(isset($headimgurl)){
                $add_member['m_head_img'] = $headimgurl;
            }
            
            $success = $member->add($add_member);
            $lastSql = $member->getLastSql();
            if($success === false){
                M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $result["message"] = "参数错误";
            	$result["status"] = "10001";
            	$result["lastSql"] = $lastSql;

            	print_r(json_encode($result));
            	die; 
            }else{
                $ary_member = $member->getInfo('',$open_id,$unionid);
            }
        }else{
            $member->where(array('m_id'=>$ary_member['m_id']))->save(array('login_type'=>1));
            $ary_member['login_type'] = 1;
        }  
		$userId = $ary_member["m_id"];
		//$member->where(array('m_id'=>$userData['m_id']))->save($data);//
		$field = 'm_id as userId,ml_id as memberLevel,m_name as userName,m_head_img as userPic,m_sex as gender,m_mobile as mobile,m_birthday as birthday,m_qq as QQAccount,m_email as email,m_recommended as recommender,m_alipay_name as alipayAccount';
		$userInfo = $member->getAppUserInfo($userId,$field);

        $field = 'ra_id as iD,ra_name as accepterName,cr_id as cityCode,ra_detail as address,ra_post_code as postCode,ra_mobile_phone as mobile,ra_is_default as isDefault,ra_id_card as iDCard,ra_status as status';
		$defaultAddress = $member->getUserDefaultAddress($userId,$field);

		// $field_id = "20";
        $userInfo["userPic"] = $headimgurl;//$member->getUserFieldInfo($userId,$field_id);

        $field_id = "22";
        $userInfo["WXAccount"] = $member->getUserFieldInfo($userId,$field_id);

		$userInfo["defaultAddress"] = $defaultAddress;
        $body["userInfo"] = $userInfo;
        $result["body"] = $body;
        $result["message"]     = "登录成功";
		$result["status"]   = "10000";
		
		print_r(json_encode($result));
		die;      
    }

    public function PageBrandCenterData() {
        $result = array();

        $GoodsInfo  = M("GoodsInfo");

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->select();

        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(8)
					   ->select();

        $result["brandList"] = $BrandList;
        $result["hotList"] = $hotList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function PageHomeData() {
        $result = array();

        $GoodsInfo  = M("GoodsInfo");
		$field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_market_price,fx_goods_info.g_salenum";
		$lastestList  = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(6)
					   ->select();

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(10)->select();

        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(6)
					   ->select();

        $time   = time();
        $field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$specialList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(6)
						   ->select();


		// $MobileApi     = D("MobileApi");

		// $index_ad1 = $MobileApi->ad("index_ad1");
		// $index_ad2 = $MobileApi->ad("index_ad2");


        $result["brandList"] = $BrandList;
        $result["lastestList"] = $lastestList;
        $result["hotList"] = $hotList;
        $result["specialList"] = $specialList;
        $result["globalList"] = $hotList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function PageSpecialAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->select();

        $time   = time();
        $field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$specialList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->select();


		//时间戳
		$time   = time();
		
		//所有商品
		// $spfield = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		// $glist = M('Spike')->field($spfield)
		// 				   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
		// 				   ->order(array("sp_id"=>"desc"))
		// 				   ->select();

		// $seckillList = 
        $result["seckillList"] = $specialList;
        $result["hotList"] = $hotList;
        $result["tomorrowList"] = $specialList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    	/**
     * 处理APPA会员我的资料修改
     * @param array() $_POST 修改的数据信息
     * @tmie 2016-12-19
     */
    public function SetAccountBasicInformation() {
		///定义一个数组来储存数据
        $result   = array();
		$member = D("Members");
        $m_id     = $this->_post('m_id');
		
		if(empty($m_id) || $m_id=="0"){
			$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			$EditData = array();
			if (!empty($_POST["m_sex"])) {
				$EditData["m_sex"]	= $_POST["m_sex"];
			}
			if (!empty($_POST["m_nickname"])) {
				$EditData["m_nickname"]	= $_POST["m_nickname"];
			}
			if (!empty($_POST["m_birthday"])) {
				$EditData["m_birthday"]	= $_POST["m_birthday"];
			}
			$EditData["m_update_time"] = date("Y-m-d H:i:s",time());

			$success = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->data($EditData)->save();

			if($success){
				///会员扩展属性，微信号
		        $field = 'm_id as userId,ml_id as memberLevel,m_name as userName,m_nickname as nickname,m_head_img as userPic,m_sex as gender,m_mobile as mobile,m_birthday as birthday,m_qq as QQAccount,m_email as email,m_recommended as recommender,m_alipay_name as alipayAccount';
		        $userInfo = $member->getAppUserInfo($m_id,$field);

                $field = 'ra_id as iD,ra_name as accepterName,cr_id as cityCode,ra_detail as address,ra_post_code as postCode,ra_mobile_phone as mobile,ra_is_default as isDefault,ra_id_card as iDCard,ra_status as status';
		        $defaultAddress = $member->getUserDefaultAddress($m_id,$field);

		// $field_id = "20";
                $userInfo["userPic"] = $headimgurl;//$member->getUserFieldInfo($userId,$field_id);

                $field_id = "22";
                $userInfo["WXAccount"] = $member->getUserFieldInfo($m_id,$field_id);

		        $userInfo["defaultAddress"] = $defaultAddress;
                $body["userInfo"] = $userInfo;
                $result["body"] = $body;
                $result["message"]     = "登录成功";
		        $result["status"]   = "10000";
		
		        print_r(json_encode($result));
				die;
			}else{
				$result["message"] = "修改失败";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;
			}
		}
    }

    public function PageSiftMenuData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(5)
					   ->select();
		$lastestList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(5)
					   ->select();

        $time   = time();
        $field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time)";

		$specialList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(5)
						   ->select();


		
		$field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->select();


        $MobileApi     = D("MobileApi");

        $field = 'gc_id,gc_name';
        $gc_parent_id = '0';
        $list_level0 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);


        $field = 'gsd_id as country_id,gsd_value as country_name';
        $countryList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();

        $field = 'gsd_id as function_id,gsd_value as function_name';
        $functionList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"911"))->select();
        
        $price1["price_id"] = "1";
        $price2["price_id"] = "2";
        $price3["price_id"] = "3";
        $price4["price_id"] = "4";
        $price5["price_id"] = "5";

        $price1["title"] = "0-100";
        $price2["title"] = "100-200";
        $price3["title"] = "200-300";
        $price4["title"] = "300-400";
        $price5["title"] = "其他";

        $priceList = array($price1,$price2,$price3,$price4,$price5); 

        $result["priceList"] = $priceList;

		$result["functionList"] = $functionList;
		$result["countryList"] = $countryList;
		$result["brandList"] = $BrandList;
        $result["specialList"] = $specialList;
        $result["newList"] = $lastestList;
        $result["hotList"] = $hotList;
        $result["categoryList"] = $list_level0;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function GetHotSearchKeywords() {

		$result = array();

        $hotKeywords = M('hot_keywords',C('DB_PREFIX'),'DB_CUSTOM')->select();

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["hotKeywords"] = $hotKeywords;
		print_r(json_encode($result));
		die;	
	}

    public function GetOrderList() {
    	$result = array();
        $MobileApi = D('MobileApi');
        $m_id = "2226";//$_REQUEST["m_id"];

        $o_pay_status = $_POST["o_pay_status"];
        $oi_ship_status = $_POST["oi_ship_status"];

        $o_pay_status = "1";
        $oi_ship_status = "2";

        $o_status = $_POST["o_status"];
        $is_evaluate = $_POST["is_evaluate"];

        if(empty($m_id)){
			$result["info"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else {
			if($o_pay_status == "0") {
				//未付款
		        $field = 'o_id,o_all_price';
                $orderList = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_pay_status"=>$o_pay_status,"m_id"=>$m_id))->select();
                foreach ($orderList as $orderListkey => $order) {
            	    $field = 'oi_g_name,oi_nums,g_id';
            	    $items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$order["o_id"]))->select();
            	    foreach ($items as $itemskey => $item) {
            	    	$field = 'g_picture';
            	    	$g_picture = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("g_id"=>$item["g_id"]))->find();
            	    	$items[$itemskey]["g_picture"] = $g_picture["g_picture"];
            	    }
            	    $orderList[$orderListkey]["items"] = $items;
            	    
                }
                $result["message"]   = "请求成功";
		        $result["status"] = "10000";
		        $result["orderList"] = $orderList;
		        print_r(json_encode($result));
		        die;
			}else if ($o_pay_status ==  "1" && $oi_ship_status == "2") {
				//已付款未发货
		        // $field = 'o_id,o_all_price';
                // $orderList = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_pay_status"=>$o_pay_status,"m_id"=>$m_id))->select();


                $orderlist = M("Orders")->field()
										->join("fx_orders_items as a on a.o_id=fx_orders.o_id")
										->where("fx_orders.m_id={$m_id} AND fx_orders.o_pay_status={$o_pay_status} AND a.oi_ship_status={$oi_ship_status}")
										->order("a.o_id desc")
										->select();

				// foreach ($orderList as $orderListkey => $order) {
    //         	    $field = 'oi_g_name,oi_nums,g_id';
    //         	    $items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$order["o_id"]))->select();
    //         	    foreach ($items as $itemskey => $item) {
    //         	    	$field = 'g_picture';
    //         	    	$g_picture = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("g_id"=>$item["g_id"]))->find();
    //         	    	$items[$itemskey]["g_picture"] = $g_picture["g_picture"];
    //         	    }
    //         	    $orderList[$orderListkey]["items"] = $items;
            	    
    //             }


			 //    foreach ($orderlist as $key => $value) {
				// 	$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value['g_id']}")->find();
				// 	$orderlist[$key]["g_picture"] = $ginfo["g_picture"];
				// 	if ($oi_ship_status == "0") {
				// 	    $orderlist[$key]["showStatus"] = "2";
				//     }else {
				// 	    $orderlist[$key]["showStatus"] = "3";
				//     }
				// }							


                // foreach ($orderList as $orderListkey => $order) {
            	   //  $field = 'oi_g_name,oi_nums,g_id';
            	   //  $items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$order["o_id"]))->select();
            	   //  foreach ($items as $itemskey => $item) {
            	   //  	$field = 'g_picture';
            	   //  	$g_picture = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("g_id"=>$item["g_id"]))->find();
            	   //  	$items[$itemskey]["g_picture"] = $g_picture["g_picture"];
            	   //  }
            	   //  $orderList[$orderListkey]["items"] = $items;
            	    
                // }
                $result["message"]   = "请求成功";
		        $result["status"] = "10000";
		        $result["orderList"] = $orderList;
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
		$showStatus = $_REQUEST["showStatus"];	
		// $m_id = "2017";
		$OrdersField = "fx_orders.o_id,fx_orders.o_all_price,fx_orders.o_pay_status,fx_orders.o_status";
		$ItemsField = "fx_orders_items.oi_g_name,fx_orders_items.oi_nums,fx_orders_items.g_id,fx_orders_items.oi_price,fx_orders_items.g_sn,fx_orders_items.oi_ship_status";
		// /$m_id = "2174";
		// $o_pay_status   = "0";
		// $oi_ship_status = "0";
		// $o_pay_status   = $_REQUEST["o_pay_status"];
		// $oi_ship_status = $_REQUEST["oi_ship_status"];
		// $o_status = $_REQUEST["o_status"];
		// $is_evaluate = $_REQUEST["is_evaluate"];	
		
		// $showStatus = "3";	
		
		if(empty($m_id)){
			$result["message"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if($showStatus == "1"){
				//$sql = "SELECT * FROM fx_orders as a join fx_orders_items as b on a.o_id=b.o_id WHERE a.m_id={$m_id}";
				//$orderlist = M()->query($sql);
				//订单信息
				$o_pay_status = "0";
				// $orderlist = M("Orders")->field()
				// 						->join("fx_orders_items as a on a.o_id=fx_orders.o_id")
				// 						->where("fx_orders.m_id={$m_id} AND fx_orders.o_pay_status={$o_pay_status}")
				// 						->order("a.o_id desc")
				// 						->select();
                $o_status = "1";
				$orderlist = M("Orders")->field($OrdersField)
										->where("m_id={$m_id} AND o_pay_status={$o_pay_status} AND o_status={$o_status}")
										->order("o_create_time desc")
										->select();										
				foreach ($orderlist as $key1 => $value1) {

					$orderlist[$key1]["showStatus"] = "1";

					$items = M("OrdersItems")->field($ItemsField)
										->where("o_id={$value1['o_id']}")
										->order("oi_id asc")
										->select();	
					foreach ($items as $key2 => $value2) {
						$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value2['g_id']}")->find();
						$items[$key2]["g_picture"] = $ginfo["g_picture"];
					}
					$orderlist[$key1]["items"] = $items;

				}

				$result["message"]      = "请求成功";
				$result["status"]    = "10000";
				$result["orderlist"] = $orderlist;
				print_r(json_encode($result));
				die;

			}else if($showStatus == "2" || $showStatus == "3"){
				$payed = "1";
				if ($showStatus == "2") {
					$oi_ship_status = "0";
				}else {
                     $oi_ship_status = "2";
				}
				//待发货、待收货

				$sql = "select fx_orders.o_id,fx_orders.o_all_price from fx_orders where fx_orders.m_id=$m_id AND fx_orders.o_pay_status=$payed AND fx_orders.o_id in (select o_id from fx_orders_items where oi_ship_status=$oi_ship_status) order by o_create_time desc";
                // echo $sql;
				$orderlist = M('',C('DB_PREFIX'),'DB_CUSTOM')->query($sql);
				// $orderlist = M("Orders")->field("fx_orders.o_id,fx_orders.o_all_price")
				// 						->where("fx_orders.m_id={$m_id} AND fx_orders.o_pay_status={$payed}")
				// 						->order("o_create_time desc")
				// 						->select();
				
				foreach ($orderlist as $key1 => $value1) {
					if ($oi_ship_status == "0") {
					    $orderlist[$key1]["showStatus"] = "2";
				    }else {
					    $orderlist[$key1]["showStatus"] = "3";
				    }
					$items = M("OrdersItems")->field($ItemsField)
										->where("o_id={$value1['o_id']}")
										->order("oi_id asc")
										->select();
					foreach ($items as $key2 => $value2) {
						$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value2['g_id']}")->find();
						$items[$key2]["g_picture"] = $ginfo["g_picture"];
					}
					$orderlist[$key1]["items"] = $items;
				}


				$result["message"]      = "请求成功";
				$result["status"]    = "10000";
				$result["orderlist"] = $orderlist;
				print_r(json_encode($result));
				die;

			}else if($showStatus == "4"){
				//待评价
				$o_status = "5";
				//订单信息
				$orderlist = M("Orders")->field($OrdersField)
										->where("fx_orders.m_id={$m_id} AND fx_orders.o_status={$o_status}")
										->order("o_create_time desc")
										->select();
				foreach ($orderlist as $key1 => $value1) {
					$orderlist[$key]["showStatus"] = "4";
					$items = M("OrdersItems")->field($ItemsField)
										->where("o_id={$value1['o_id']}")
										->order("oi_id asc")
										->select();

					foreach ($items as $key2 => $value2) {
						$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value2['g_id']}")->find();
						$items[$key2]["g_picture"] = $ginfo["g_picture"];
					}
					$orderlist[$key1]["items"] = $items;
				}

				$result["message"]      = "请求成功";
				$result["status"]    = "10000";

				$result["orderlist"] = $orderlist;
				print_r(json_encode($result));
				die;

			}else{
				//$sql = "SELECT * FROM fx_orders as a join fx_orders_items as b on a.o_id=b.o_id WHERE a.m_id={$m_id}";
				//$orderlist = M()->query($sql);
				//订单信息
				$orderlist = M("Orders")->field($OrdersField)
										->where("fx_orders.m_id={$m_id}")
										->order("o_create_time desc")
										->select();

				foreach ($orderlist as $key1 => $order) {


					$items = M("OrdersItems")->field($ItemsField)
										->where("o_id={$order['o_id']}")
										->order("oi_id asc")
										->select();
					foreach ($items as $key2 => $value2) {
						$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value2['g_id']}")->find();
						$items[$key2]["g_picture"] = $ginfo["g_picture"];
					}
					$orderlist[$key1]["items"] = $items;

					$item0 = current($items);

					if ($order["o_pay_status"] == "0" && $order["o_status"] == "1") {
					    $orderlist[$key1]["showStatus"] = "1";//待付款
				    }else if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "0") {
					    $orderlist[$key1]["showStatus"] = "2";//待发货
				    }else if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "2") {
					    $orderlist[$key1]["showStatus"] = "3";//待收货
				    }else if ($order["o_status"] == "5") {
					    $orderlist[$key1]["showStatus"] = "4";//待评价
				    }else if ($order["o_status"] == "2") {
					    $orderlist[$key1]["showStatus"] = "5";//关闭
				    }else if ($order["o_status"] == "4") {
					    $orderlist[$key1]["showStatus"] = "6";//完成
				    }
				}

				$result["message"]      = "请求成功";
				$result["status"]    = "10000";
				$result["orderlist"] = $orderlist;
				print_r(json_encode($result));
					//echo "<pre>";
					//print_r($result);
				die;
			}
		}
	}

    public function Cardinfoold(){
	    $host = "http://cardinfo.market.alicloudapi.com";
	    $path = "/lianzhuo/querybankcard";
	    $method = "GET";
	    $appcode = "4c705f4fdb4343e586f00a247d2b9a7f";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "bankno=".$_POST['bankno'];
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, true);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    $rs = curl_exec($curl);
	    $rs = substr($rs, strpos($rs,'"bank_name":"')+13);
	    $rs = substr($rs, 0,strpos($rs,'"'));
		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["bank_name"] = $rs;
		print_r(json_encode($result));
		die;	
    }

    public function Cardinfo(){
	    $host = "http://ali-bankcard.showapi.com";
	    $path = "/bankcard";
	    $method = "GET";
	    $appcode = "4c705f4fdb4343e586f00a247d2b9a7f";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "kahao=".$_REQUEST['bankno'];
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, true);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    //var_dump(curl_exec($curl));
	    //exit();
	    $rs = curl_exec($curl);
	    $bank_name = substr($rs, strpos($rs,'"bankName":"')+12);
	    $bank_name = substr($bank_name, 0,strpos($bank_name,'"'));
	    $area = substr($rs, strpos($rs,'"area":"')+8);
	    $area = substr($area, 0,strpos($area,'"'));
		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["bank_name"] = $bank_name;
		$result["area"] = $area;
		print_r(json_encode($result));
		die;	
    }

    public function ocrCard(){
    	$host = "http://yhk.market.alicloudapi.com";
	    $path = "/rest/160601/ocr/ocr_bank_card.json";
	    $method = "POST";
	    $appcode = "4c705f4fdb4343e586f00a247d2b9a7f";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    //根据API的要求，定义相对应的Content-Type
	    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
	    $image_file = '/data/www/fx/Public/images/card.png';

	    $base64_image = '';
  		$image_info = getimagesize($image_file);
  		$image_data = fread(fopen($image_file, 'r'), filesize($image_file));
  		$base64_image = chunk_split(base64_encode($image_data));
	    $querys = "";
	    $bodys = "{
		    \"inputs\": [
		    {
		        \"image\": {
		            \"dataType\": 50,
		            \"dataValue\": \"{$base64_image}\"
		        }
		    }]
		}";
		//echo $bodys;
		//exit();
	    $url = $host . $path;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, true);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
	    var_dump(curl_exec($curl));
    }

    public function GetCountryList(){
	    $result = array();
        $field = 'gsd_id as country_id,gsd_value as country_name';
        $countryList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();
        $result["message"]      = "请求成功";
		$result["status"]    = "10000";
		$result["countryList"] = $countryList;

		print_r(json_encode($result));
		die;	
    }

    public function PageCountryAreaData(){
	    $result = array();
        $field = 'gsd_id as country_id,gsd_value as country_name';
        $countryList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();


        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(12)->select();

		$result["brandList"] = $BrandList;
        $result["message"]      = "请求成功";
		$result["status"]    = "10000";
		$result["countryList"] = $countryList;

		print_r(json_encode($result));
		die;	
    }

    public function GetFunctionList(){
	    $result = array();
        $field = 'gsd_id as function_id,gsd_value as function_name';
        $functionList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"911"))->select();
        $result["message"]      = "请求成功";
		$result["status"]    = "10000";
		$result["functionList"] = $functionList;

		print_r(json_encode($result));
		die;	
    }


    public function OrderAction(){
		///定义一个数组来储存数据  action_type  1 删除订单  2取消订单  3 确认收货
        $result   = array();
		$m_id = $this->_post("m_id");
		$o_id = $this->_post("o_id");
		$action_type = $this->_post("action_type");
		
		if(empty($m_id) || empty($o_id) || empty($action_type)){
			$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else{
			if ($action_type == "1") {
				$regs = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$m_id,"o_id"=>$o_id))->delete();
			    if(empty($regs)){
				    $result["message"] = "删除失败";
				    $result["status"] = "10002";
				    print_r(json_encode($result));
				    die;
			    }else{
				    $result["message"] = "删除成功";
				    $result["status"] = "10000";
				    print_r(json_encode($result));
				    die;
			    }
			}else if ($action_type == "2") {
				$saveData["o_status"] = "2";
				$regs = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$m_id,"o_id"=>$o_id))->data($saveData)->save();
				if(empty($regs)){
					$result["message"] = "取消失败";
					$result["status"] = "10002";
					print_r(json_encode($result));
					die;
				}else{
					$result["message"] = "取消成功";
					$result["status"] = "10000";
					print_r(json_encode($result));
					die;
				}
			}else if ($action_type == "3") {
				$saveData["o_status"] = "5";
				$regs = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$m_id,"o_id"=>$o_id))->data($saveData)->save();
				if(empty($regs)){
					$result["message"] = "确认失败";
					$result["status"] = "10002";
					print_r(json_encode($result));
					die;
				}else{
					$result["message"] = "收货成功";
					$result["status"] = "10000";
					print_r(json_encode($result));
					die;
				}
			}
			
		}
	}

	public function appGoodsComSearch(){
		//定义一个数组来储存数据
		$result = array();
		
		$country   = $_POST["country"];
		$price_id   = $_POST["price_id"];
		$category_id   = $_POST["category_id"];
		$brand_id = $_POST["brand_id"];
		$function = $_POST["function"];
		$keywords = $_POST["keywords"];
		$pages   = empty($_POST["page"])?"1":$_POST["page"];
		
		//分页
		$limit = empty($_POST["pageSize"])?6:$_POST["pageSize"];
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		$where = '1';

		$M = M('');
		$g_ids = array();

		if(!empty($brand_id)){
			$sql = 'select g_id from fx_goods where gb_id="'.$brand_id.'" and g_on_sale=1 and g_status=1';
		}
		else{
			$sql = 'select g_id from fx_goods where g_on_sale=1 and g_status=1';
		}
		$rs = $M->query($sql);
		foreach ($rs as $key => $value) {
			$g_ids[] = $value['g_id'];
		}


		if(!empty($country)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases="'.$country.'"';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$country_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$country_g_ids);
		}
		if(!empty($function)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases="'.$function.'"';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$function_g_ids[] = $value['g_id'];
			}			
			$g_ids = array_intersect($g_ids,$function_g_ids);
		}
		if(!empty($category_id)){
			$sql = 'select g_id from fx_related_goods_category where gc_id="'.$category_id.'"';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$category_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$category_g_ids);	
		}
		
		$gids = implode(",",$g_ids);
		$where.=' and g_id in ('.$gids.')';

		if(!empty($price_id)){
			if($price_id=="1"){
				$minPrice = 0;
				$maxPrice = 100;
			}else if($price_id=="2"){
				$minPrice = 100;
				$maxPrice = 200;
			}else if($price_id=="3"){
				$minPrice = 200;
				$maxPrice = 300;
			}else if($price_id=="4"){
				$minPrice = 300;
				$maxPrice = 400;
			}else{
				$minPrice = 400;
				$maxPrice = 1000;
			}
			$where.=' and g_price between '.$minPrice.' and '.$maxPrice;
		}

		if(!empty($keywords)){
			$where.=' and g_name like "%'.$keywords.'%"';
		}

		$sql = 'select g_id,g_name,g_picture,g_price,g_market_price from fx_goods_info where '.$where.' order by g_id desc limit '.$startindex.','.$limit;

		$glist = $M->query($sql);

		foreach ($glist as $key => $value) {
			$glist[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
		}

        $lastSql = $M->getLastSql();
        // $result["lastSql"] = $lastSql;
		$result["info"]   = "请求成功";
		$result["status"] = "10000";
		$result["glist"]  = $glist;
		print_r(json_encode($result));
		die;
	}


	public function PageNewAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$newList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(8)
					   ->select();

		$hotLatestList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(12)
					   ->select();

	    foreach ($newList as $key => $value) {
			$newList[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
		}

		foreach ($hotLatestList as $key => $value) {
			$hotLatestList[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
		}

        $result["newList"] = $newList;
        $result["hotLatestList"] = $hotLatestList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function GetOrderDetail() {
    	$result = array();
    	$o_id = $_REQUEST["o_id"];
    	// $m_id = "2226";
    	// $o_id = "201706051010572268";

    	if(empty($o_id)) {
    		$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
    	}else {
    		$OrdersField = "fx_orders.o_id,fx_orders.o_all_price,fx_orders.o_pay_status,fx_orders.o_status,fx_orders.o_create_time,fx_orders.o_receiver_mobile,fx_orders.o_receiver_state,fx_orders.o_receiver_city,fx_orders.o_receiver_county,fx_orders.o_receiver_address,fx_orders.o_goods_discount,fx_orders.o_receiver_name,fx_orders.o_tax_rate,fx_orders.o_cost_freight,fx_orders.o_goods_all_price";
		    $ItemsField = "fx_orders_items.oi_g_name,fx_orders_items.oi_nums,fx_orders_items.g_id,fx_orders_items.oi_price,fx_orders_items.g_sn,fx_orders_items.oi_ship_status";

    		$order = M("Orders")->field($OrdersField)
										->where("fx_orders.o_id={$o_id}")
										->order("o_create_time desc")
										->find();


			$items = M("OrdersItems")->field($ItemsField)
									->where("o_id={$o_id}")
									->order("oi_id asc")
									->select();
			foreach ($items as $key2 => $value2) {
				$ginfo = M("GoodsInfo")->field("g_picture")->where("g_id={$value2['g_id']}")->find();
				$items[$key2]["g_picture"] = $ginfo["g_picture"];
			}
			$order["items"] = $items;

			$item0 = current($items);

			if ($order["o_pay_status"] == "0" && $order["o_status"] == "1") {
				  $order["showStatus"] = "1";//待付款
			}else if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "0") {
				$order["showStatus"] = "2";//待发货
			}else if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "2") {
				$order["showStatus"] = "3";//待收货
			}else if ($order["o_status"] == "5") {
				$order["showStatus"] = "4";//待评价
			}else if ($order["o_status"] == "2") {
				$order["showStatus"] = "5";//关闭
			}else if ($order["o_status"] == "4") {
				$order["showStatus"] = "6";//完成
			}

		    $result["message"]      = "请求成功";
		    $result["status"]    = "10000";
			$result["orderDetail"] = $order;
			print_r(json_encode($result));
			die; 
    	}

    }


    public function GetCreateOrderCanUseCoupons() {
    	$result = array();
    	$all_goods_price = $_REQUEST["all_goods_price"];
    	$m_id = $_REQUEST["m_id"];
    	// $m_id = "0";
    	// $all_goods_price = "2018";
    	$date = date("Y-m-d H:i:s");
    	if (empty($m_id) || empty($all_goods_price)) {
    		$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
    	}else {
    		$field = "c_condition_money,c_id,c_memo,c_money,c_name";  
    		$couponList = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_end_time>'{$date}' AND c_is_use=0 AND c_user_id = {$m_id} AND c_type=0  AND c_start_time<'{$date}' AND c_condition_money<{$all_goods_price}")
										->select();
		    $getLastSql = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
			$result["message"]      = "请求成功";
		    $result["status"]    = "10000";
			$result["couponList"] = $couponList;
			// $result["getLastSql"] = $getLastSql;
			print_r(json_encode($result));
			die; 
    	}
    }

    public function GetMyCoupons() {
    	$result = array();
    	$m_id = $_REQUEST["m_id"];
    	$coupon_type = $_REQUEST["coupon_type"];

    	$date = date("Y-m-d H:i:s");
    	if (empty($m_id)) {
    		$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
    	}else {
    		$couponList = array();
    		$field = "c_condition_money,c_id,c_memo,c_money,c_name,c_end_time,c_type,c_start_time";
    		if ($coupon_type == "2") {
    			$couponList = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_end_time<'{$date}' AND c_is_use=0 AND c_user_id = {$m_id} AND c_type=0")
										->select();

    		}else {
			    $couponList = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_end_time>'{$date}' AND c_is_use=0 AND c_user_id = {$m_id} AND c_type=0  AND c_start_time<'{$date}'")
										->select();
				foreach ($couponList as $key => $value) {
					$d1 = strtotime($value["c_end_time"]);
                    $d2 = strtotime($date);
                    $leftdays = round(($d1-$d2)/3600/24);
                    $couponList[$key]["c_leftdays"] = $leftdays;
				}
    		}
    		$getLastSql = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
			$result["message"]      = "请求成功";
			$result["coupon_type"]      = $coupon_type;
		    $result["status"]    = "10000";
			$result["couponList"] = $couponList;
			 // $result["getLastSql"] = $getLastSql;
			print_r(json_encode($result));
			die; 
    		
    	}


    }

    public function PageHotAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->select();

        $result["hotList"] = $hotList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function ad(){
		$ad_name   = $_REQUEST["ad_name"];
		$M = M('');

		$sql = 'select n_name,n_aurl as n_id,n_imgurl,n_type,n_length,n_height from fx_adwap where n_status=1 and n_name like "'.$ad_name.'%" order by n_order asc';
		$rs = $M->query($sql);
		if($rs){
			$result["message"]      = "请求成功";
		    $result["status"]    = "10000";
			$result["adList"] = $rs;
			// $result["getLastSql"] = $getLastSql;
			print_r(json_encode($result));
			die; 
		}
		else{
    		$result["message"] = "查询不到数据";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;			
		}
    }



}