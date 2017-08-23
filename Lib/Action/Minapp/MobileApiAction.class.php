<?php 
class MobileApiAction extends GyfxAction {  
	
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

        $categoryinfo = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array("g_id"=>$gid))->find();

		$where = 1;
		if($categoryinfo["gc_id"]){
			$where.= " and fx_videos_info.v_category_id=".$categoryinfo["gc_id"];
		}

        $M = M("videos_info");

		$videoList = $M->field('fx_videos_info.v_id,fx_videos_info.v_name,fx_videos_info.v_code,fx_videos_info.v_picture,fx_videos_teacher.t_name,fx_videos_teacher.t_photo')
					   ->join('left join fx_videos_teacher on fx_videos_info.v_teacher_id = fx_videos_teacher.t_id')
					   ->limit(2)
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

		$attributes = array();

		$MobileApi     = D("MobileApi");
		//时间
		$createtime = $MobileApi->GetGoodsAttribute($gid,"895");
		$attributes["createtime"] = $createtime[0]["gsd_aliases"];

		//规格
		$spec = $MobileApi->GetGoodsAttribute($gid,"897");
		$attributes["spec"] = $spec[0]["gsd_aliases"];

		//功效
		$function = $MobileApi->GetGoodsAttribute($gid,"911");
		$attributes["function"] = $function[0]["gsd_aliases"];

		//适合人群
		$suitfor = $MobileApi->GetGoodsAttribute($gid,"898");
		$attributes["suitfor"] = $suitfor[0]["gsd_aliases"];

		//适合人群
		$specclass = $MobileApi->GetGoodsAttribute($gid,"891");
		$attributes["specclass"] = $specclass[0]["gsd_aliases"];

		// //名称
		// $attributes["goodsname"] = $gdinfo["g_name"];

		// //品牌
		// $attributes["brandname"] = $brandinfo["gb_name"];

		//产地
		$region = $MobileApi->GetGoodsAttribute($gid,"893");
		$attributes["region"] = $region[0]["gsd_aliases"];

		//颜色
		$colors = $MobileApi->GetGoodsAttribute($gid,"890");
		$attributes["colors"] = $colors[0]["gsd_aliases"];

		//保质期
		$shelflife = $MobileApi->GetGoodsAttribute($gid,"896");
		$attributes["shelflife"] = $shelflife[0]["gsd_aliases"];

		
		
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
		
		// //商品扩展属性
		// $sql="SELECT rg.*,sg.gs_name FROM fx_related_goods_spec as rg JOIN fx_goods_spec as sg ON rg.gs_id=sg.gs_id WHERE g_id={$gid}";
		// $relgoodsattr = $relgoods->query($sql);
		
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
			// $result["relgoodsattr"] = $relgoodsattr;
			$result["relgoodslist"] = $glist;
			$result["colorcate"]    = $colorcat;
			$result["brandinfo"]    = $brandinfo;
			$result["videoList"]    = $videoList;
			$result["attributes"] = $attributes;
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

			// $addressList = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->field("ra_is_default,cr_id")->where(array("m_id"=>$m_id))->select();

			// foreach ($addressList as $addressKey => $addressValue) {
			// 	if ($addressValue["cr_id"] != $cr_id) {
			// 		$addressValue["ra_is_default"] = 0;
			// 		M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->where(array("cr_id")=>$addressValue["cr_id"])->save($addressValue);
			// 		// $result[$addressKey] = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
			// 	}
			// }
		 //    $result["addressList"] = $addressList;
		
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
	public function GetChildCategoryList() {

		$result = array();
        $MobileApi     = D("MobileApi");

        $field = 'gc_id,gc_name,gc_pic_url';
        $gc_parent_id = empty($_REQUEST['gc_parent_id'])?"0":$_REQUEST['gc_parent_id'];
        $list_level = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
        if (!$list_level) {
        	$result["message"]   = "请求失败";
		    $result["status"] = "10001";
		    print_r(json_encode($result));
		    die;
        }

        foreach ($list_level as $key => $value) {
			$list_level[$key]['gc_pic_url'] = 'http://www.caizhuangguoji.com'.$value['gc_pic_url'];
		}

        $result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["categorys"] = $list_level;
		print_r(json_encode($result));
		die;	
	}	

	public function PageCategoryData() {

		$result = array();
        $MobileApi     = D("MobileApi");

        $field = 'gc_id,gc_name,gc_pic_url';
        $gc_parent_id = '0';
        $list_level0 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
        
        for ($i=0; $i < count($list_level0); $i++) { 
        	$gc_parent_id = $list_level0[$i]["gc_id"];
            $list_level1 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
            foreach ($list_level1 as $key => $value) {
			    $list_level1[$key]['gc_pic_url'] = 'http://www.caizhuangguoji.com'.$value['gc_pic_url'];
		    }
            $list_level0[$i]["childList"] = $list_level1;

            // for ($j=0; $j < count($list_level1); $j++) { 
            // 	$gc_parent_id = $list_level1[$j]["gc_id"];
            //     $list_level2 = $MobileApi->GetChildCategoryListByParentCategoryId($gc_parent_id,$field);
            //     $list_level0[$i]["childList"][$j]["childList"] = $list_level2;
            // }
        }

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(12)->select();

        $index_ad1 = D("MobileApi")->GetAdByAdname("category_ad1");

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["catedata"] = $list_level0;
		$result["index_ad1"] = $index_ad1;
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

        //M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
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
            //$lastSql = $member->getLastSql();
            if($success === false){
                //M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
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
		$result["lastSql"] = $lastSql;
        $result["success"] = $success;
		
		print_r(json_encode($result));
		die;      
    }


	public function appQQUserLogin(){

		$result = array();
        $member = D('Members');

        $open_id = $_POST["open_id"];
        $nickname = $_POST["nickname"];
        $gender = $_POST["gender"];
        $headimgurl = $_POST["headimgurl"];

        $ary_member = $member->getInfo('',$open_id);
        $resultary_member = $ary_member;
        $ary_memberlastSql = $member->getLastSql();

        //M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        if(!isset($ary_member['open_id'])){
            //默认等级
            $ml = D('MembersLevel')->getSelectedLevel();
            //新增用户
            $add_member = array();
            $add_member['m_name'] = 'QQ_'.$nickname;
            $add_member['open_name'] = $nickname;
            $add_member['open_id'] = $open_id;
            $add_member['open_source'] = 'QQ';
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
                //M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $result["message"] = "参数错误";
            	$result["status"] = "10001";
            	$result["lastSql"] = $lastSql;

            	print_r(json_encode($result));
            	die; 
            }else{
                $ary_member = $member->getInfo('',$open_id);
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
        $userInfo["QQAccount"] = $member->getUserFieldInfo($userId,$field_id);

		$userInfo["defaultAddress"] = $defaultAddress;
        $body["userInfo"] = $userInfo;
        $result["body"] = $body;
        $result["lastSql"] = $lastSql;
        $result["success"] = $success;
        $result["ary_memberlastSql"] = $ary_memberlastSql;
        $result["resultary_member"] = $resultary_member;
        $result["message"]     = "登录成功";
		$result["status"]   = "10000";
		
		print_r(json_encode($result));
		die;      
    }

	public function appWBUserLogin(){

		$result = array();
        $member = D('Members');

        $open_id = $_POST["open_id"];
        $nickname = $_POST["nickname"];
        $gender = $_POST["gender"];
        $headimgurl = $_POST["headimgurl"];

        $ary_member = $member->getInfo('',$open_id);

        //M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        if(!isset($ary_member['open_id'])){
            //默认等级
            $ml = D('MembersLevel')->getSelectedLevel();
            //新增用户
            $add_member = array();
            $add_member['m_name'] = 'WB_'.$nickname;
            $add_member['open_name'] = $nickname;
            $add_member['open_id'] = $open_id;
            $add_member['open_source'] = 'WB';
            $add_member['ml_id'] = $ml;
            $add_member['login_type'] = 1;
            $add_member['m_create_time'] = date('Y-m-d H:i:s');
			//$add_member['m_status'] = 1; //为启用
            $data = D('SysConfig')->getCfgByModule('MEMBER_SET');

            if (!empty($data['MEMBER_STATUS']) && $data['MEMBER_STATUS'] == '1') {
                $add_member['m_verify'] = '2';

            }
            if($gender == 'm' || $sex == '1'){

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
                //M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $result["message"] = "参数错误";
            	$result["status"] = "10001";
            	$result["lastSql"] = $lastSql;

            	print_r(json_encode($result));
            	die; 
            }else{
                $ary_member = $member->getInfo('',$open_id);
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
        $userInfo["WBAccount"] = $member->getUserFieldInfo($userId,$field_id);

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

        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		// ->limit("{$startindex},{$limit}")

        $GoodsInfo  = M("GoodsInfo");

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->select();

        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit("{$startindex},{$limit}")
					   ->select();
		
		$index_ad1 = D("MobileApi")->GetAdByAdname("brandcenter_ad1");
		// $index_ad2 = $MobileApi->GetAdByAdname("index_ad1",$field);

		$result["index_ad1"] = $index_ad1;
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
					   ->limit(16)
					   ->select();

        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(40)->select();

        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(12)
					   ->select();

        $time   = time();
         $field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time) as start_leftsec,UNIX_TIMESTAMP(sp_end_time) as end_leftsec";

		$todayList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND UNIX_TIMESTAMP(sp_end_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
		$tomorrowList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
        foreach ($todayList as $todayKey => $todayValue) {
        	$todayList[$todayKey]["end_leftsec"] = $todayValue["end_leftsec"] - $time;
        	$todayList[$todayKey]["start_leftsec"] = $todayValue["start_leftsec"] - $time;
        }

        foreach ($tomorrowList as $tomorrowKey => $tomorrowValue) {
        	$tomorrowList[$tomorrowKey]["end_leftsec"] = $tomorrowValue["end_leftsec"] - $time;
        	$tomorrowList[$tomorrowKey]["start_leftsec"] = $tomorrowValue["start_leftsec"] - $time;
        }


		$MobileApi = D("MobileApi");

	 //    $pages = empty($_POST["page"])?"1":$_POST["page"];
		// $limit = empty($_POST["pageSize"])?12:intval($_POST["pageSize"]);

		$globalList = $MobileApi->GetGlobalGoodsList("1",12);

		// $field = "n_imgurl,n_type,n_aurl,n_length,n_height";

		$index_ad1 = D("MobileApi")->GetAdByAdname("home_ad1");
		$index_ad2 = D("MobileApi")->GetAdByAdname("home_ad2");
		$index_ad3 = D("MobileApi")->GetAdByAdname("home_ad3");
		$index_ad4 = D("MobileApi")->GetAdByAdname("home_ad4");
		$index_ad5 = D("MobileApi")->GetAdByAdname("home_ad5");
		$index_ad6 = D("MobileApi")->GetAdByAdname("home_ad6");
		$index_ad7 = D("MobileApi")->GetAdByAdname("home_ad7");
		$index_ad8 = D("MobileApi")->GetAdByAdname("home_ad8");
        

        $result["index_ad1"] = $index_ad1;
        $result["index_ad2"] = $index_ad2;
        $result["index_ad3"] = $index_ad3;
        $result["index_ad4"] = $index_ad4;
        $result["index_ad5"] = $index_ad5;
        $result["index_ad6"] = $index_ad6;
        $result["index_ad7"] = $index_ad7;
        $result["index_ad8"] = $index_ad8;


        $result["brandList"] = $BrandList;
        $result["lastestList"] = $lastestList;
        $result["hotList"] = $hotList;
        $result["todayList"] = $todayList;
        $result["tomorrowList"] = $tomorrowList;
        $result["globalList"] = $globalList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }


    public function GetSpikeList() {
    	$spike_type = $_REQUEST["spike_type"];
    	$spikeList = array();

    	$field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time) as start_leftsec,UNIX_TIMESTAMP(sp_end_time) as end_leftsec";

        $time   = time();
        if ($spike_type == '1') {
        	$spikeList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND UNIX_TIMESTAMP(sp_end_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
        }else {
        	$spikeList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
        }

        foreach ($spikeList as $spikeKey => $spikeValue) {
        	$spikeList[$spikeKey]["end_leftsec"] = $spikeValue["end_leftsec"] - $time;
        	$spikeList[$spikeKey]["start_leftsec"] = $spikeValue["start_leftsec"] - $time;
        }
        
        $result["spikeList"] = $spikeList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function PageSpecialAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		// ->limit("{$startindex},{$limit}")

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit("{$startindex},{$limit}")
					   ->select();

        $time   = time();
        $field = "sp_id,sp_title,sp_picture,g_id,sp_now_number,sp_price,sp_status,sp_start_time,sp_end_time,sp_create_time,UNIX_TIMESTAMP(sp_start_time) as start_leftsec,UNIX_TIMESTAMP(sp_end_time) as end_leftsec";

		$todayList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)<{$time} AND UNIX_TIMESTAMP(sp_end_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
		$tomorrowList = M('Spike')->field($field)
						   ->where("UNIX_TIMESTAMP(sp_start_time)>{$time} AND sp_status=1")
						   ->order(array("sp_id"=>"desc"))
						   ->limit(14)
						   ->select();
        foreach ($todayList as $todayKey => $todayValue) {
        	$todayList[$todayKey]["end_leftsec"] = $todayValue["end_leftsec"] - $time;
        	$todayList[$todayKey]["start_leftsec"] = $todayValue["start_leftsec"] - $time;
        }

        foreach ($tomorrowList as $tomorrowKey => $tomorrowValue) {
        	$tomorrowList[$tomorrowKey]["end_leftsec"] = $tomorrowValue["end_leftsec"] - $time;
        	$tomorrowList[$tomorrowKey]["start_leftsec"] = $tomorrowValue["start_leftsec"] - $time;
        }

		$index_ad1 = D("MobileApi")->GetAdByAdname("specialarea_ad1");
		
        $result["index_ad1"] = $index_ad1;
        $result["todayList"] = $todayList;
        $result["hotList"] = $hotList;
        $result["time"] = $time;
        $result["tomorrowList"] = $tomorrowList;
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

    	    //上传图片
    		if($_FILES['upload_file']){
                $upload = new UploadFile();// 实例化上传类
		        $upload->maxSize  = 3145728 ;// 设置附件上传大小
		        $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');
		        $path = './Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/';
		        if(!file_exists($path)){
		    	     @mkdir('./Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/', 0777, true);
		        }

	    	  //import('ORG.Net.UploadFile');
			// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
			    $upload->savePath =  $path;// 设置附件上传目录
			    if(!$upload->upload()) {// 上传错误提示错误信息
				    $result["message"]   = $upload->getErrorMsg();
				    $result["status"] = "10001";
				    print_r(json_encode($result));
				    die;
			    }else{// 上传成功 获取上传文件信息
				    $info =  $upload->getUploadFileInfo();
				    $tmp_files_url = '/Public/Uploads/'.CI_SN.'/images/aftersale/' .date('Ymd').'/'. $info[0]['savename'];
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
			    }
		    }	


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

		        $field_id = "20";
                $members_fields_info = M('members_fields_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array("u_id"=>$m_id,"field_id"=>$field_id))->find();
                $userInfo["userPic"] = "http://www.caizhuangguoji.com".$members_fields_info["content"];


                $userInfo["defaultAddress"] = $defaultAddress;
                $body["userInfo"] = $userInfo;
                $result["body"] = $body;
                $result["message"]     = "修改成功";
                $result["status"]   = "10000";
                $result["_FILES"]   = $_FILES;

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
		$ItemsField = "fx_orders_items.oi_g_name,fx_orders_items.oi_nums,fx_orders_items.g_id,fx_orders_items.oi_price,fx_orders_items.g_sn,fx_orders_items.oi_ship_status,fx_orders_items.oi_refund_status";
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
				$o_pay_status = 0;
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
				$payed = 1;
				$o_status = 1;
				if ($showStatus == "2") {
					$oi_ship_status = 0;
				}else {
                     $oi_ship_status = 2;
				}
				//待发货、待收货

				$sql = "select fx_orders.o_id,fx_orders.o_all_price from fx_orders where fx_orders.m_id=$m_id AND fx_orders.o_pay_status=$payed AND fx_orders.o_status=$o_status AND fx_orders.o_id in (select o_id from fx_orders_items where oi_ship_status=$oi_ship_status) order by o_create_time desc";
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


				$o_status = 5;
				//订单信息
				$orderlist = M("Orders")->field($OrdersField)
										->where("fx_orders.m_id={$m_id} AND fx_orders.o_status={$o_status}")
										->order("o_create_time desc")
										->select();


				$lastSql = M("Orders")->getLastSql();
				foreach ($orderlist as $key1 => $value1) {
					$orderlist[$key1]["showStatus"] = "4";
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
				    }
				    if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "0") {
					    $orderlist[$key1]["showStatus"] = "2";//待发货
				    }
				    if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "2") {
					    $orderlist[$key1]["showStatus"] = "3";//待收货
				    }
				    if ($order["o_status"] == "5") {
					    $orderlist[$key1]["showStatus"] = "4";//待评价
				    }
				    if ($order["o_status"] == "2") {
					    $orderlist[$key1]["showStatus"] = "5";//关闭
				    }
				    if ($order["o_status"] == "4") {
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
        $field = 'gsd_id as country_id,gsd_value as country_name,gsd_pic as country_logo';
        $countryList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();
        foreach ($countryList as $key => $value) {
        	$countryList[$key]['country_logo'] = 'http://www.caizhuangguoji.com'.$value['country_logo'];
        }
        $result["message"]      = "请求成功";
		$result["status"]    = "10000";
		$result["countryList"] = $countryList;

		print_r(json_encode($result));
		die;	
    }

    public function PageCountryAreaData(){
	    $result = array();
        $field = 'gsd_id as country_id,gsd_value as country_name,gsd_pic as country_logo';
        $countryList = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();

        $pages = empty($_REQUEST["page"])?"1":$_REQUEST["page"];
		$limit = empty($_REQUEST["pageSize"])?10:intval($_REQUEST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		// ->limit("{$startindex},{$limit}")



        $field = 'gb_id,gb_name,gb_logo';
        $BrandList = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit("{$startindex},{$limit}")->select();

        foreach ($countryList as $key => $value) {
			$countryList[$key]['country_logo'] = 'http://www.caizhuangguoji.com'.$value['country_logo'];
		}

		foreach ($BrandList as $key => $value) {
			$BrandList[$key]['gb_logo'] = 'http://www.caizhuangguoji.com'.$value['gb_logo'];
		}

		$index_ad1 = D("MobileApi")->GetAdByAdname("countryarea_ad1");

		$result["brandList"] = $BrandList;
		$result["index_ad1"] = $index_ad1;
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
		
		$country   = $_REQUEST["country"];
		$ishot   = $_REQUEST["ishot"];
		$isnew   = $_REQUEST["isnew"];
		$price_id   = $_REQUEST["price_id"];
		$category_id   = $_REQUEST["category_id"];
		$brand_id = $_REQUEST["brand_id"];
		$function = $_REQUEST["function"];
		$keywords = $_REQUEST["keywords"];
		$pages   = empty($_REQUEST["page"])?"1":$_REQUEST["page"];
		
		//分页
		$limit = empty($_REQUEST["pageSize"])?6:$_REQUEST["pageSize"];
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

		if ($ishot == "1") {
			$sql = $sql.' and g_hot=1';
		}

		if ($isnew == "1") {
			$sql = $sql.' and g_new=1';
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
        if ($glist) {
        	$result["info"]   = "请求成功";
		    $result["status"] = "10000";
		    $result["glist"]  = $glist;
		    print_r(json_encode($result));
		    die;
        }
		$result["info"]   = "无数据";
		$result["status"] = "10001";
		print_r(json_encode($result));
		die;
	}


	public function PageNewAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";


        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		// ->limit("{$startindex},{$limit}")

		$newList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit(12)
					   ->select();

	    $hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit("{$startindex},{$limit}")
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

		$index_ad1 = D("MobileApi")->GetAdByAdname("latestarea_ad1");
		$index_ad2 = D("MobileApi")->GetAdByAdname("latestarea_ad2");
		$index_ad3 = D("MobileApi")->GetAdByAdname("latestarea_ad3");

		$result["index_ad1"] = $index_ad1;
        $result["index_ad2"] = $index_ad2;
        $result["index_ad3"] = $index_ad3;

        $result["newList"] = $newList;
        $result["hotList"] = $hotList;
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
    		$OrdersField = "fx_orders.o_id,fx_orders.o_pay,fx_orders.o_all_price,fx_orders.o_pay_status,fx_orders.o_status,fx_orders.o_create_time,fx_orders.o_receiver_mobile,fx_orders.o_receiver_state,fx_orders.o_receiver_city,fx_orders.o_receiver_county,fx_orders.o_receiver_address,fx_orders.o_goods_discount,fx_orders.o_receiver_name,fx_orders.o_tax_rate,fx_orders.o_cost_freight,fx_orders.o_goods_all_price";
		    $ItemsField = "fx_orders_items.oi_g_name,fx_orders_items.oi_nums,fx_orders_items.g_id,fx_orders_items.oi_price,fx_orders_items.g_sn,fx_orders_items.oi_ship_status,fx_orders_items.oi_refund_status";

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
			}
			if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "0") {
				$order["showStatus"] = "2";//待发货
			}
			if ($order["o_pay_status"] == "1" && $item0["oi_ship_status"] == "2") {
				$order["showStatus"] = "3";//待收货
			}
			if ($order["o_status"] == "5") {
				$order["showStatus"] = "4";//待评价
			}
			if ($order["o_status"] == "2") {
				$order["showStatus"] = "5";//关闭
			}
			if ($order["o_status"] == "4") {
				$order["showStatus"] = "6";//完成
			}

		    $MobileApi = D("MobileApi");
            $guesslist = $MobileApi->GuessULike();
		    $result["message"]      = "请求成功";
		    $result["status"]    = "10000";
		    $result["guesslist"]    = $guesslist;
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

    public function ReceiveNewRegisterCoupon() {
    	$result = array();
    	$m_id = $_REQUEST["m_id"];
    	if (empty($m_id)) {
    		$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
    	}else {
    		$couponList = array();
    		$field = "c_condition_money,c_memo,c_money,c_name,c_end_time,c_type,c_start_time,c_isregister,c_sn";

    		$coupon = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_user_id = {$m_id} AND c_isregister=1")
										->find();
			if ($coupon) {
				$result["message"]      = "您已领取过该优惠券";
			    $result["status"]      = "10001";
			    print_r(json_encode($result));
			    die; 
			}else {
				$c_user_id = 0;
				$RegisterCoupon = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->field($field)
										->where("c_user_id = {$c_user_id} AND c_isregister=1")
										->find();
				$RegisterCoupon["c_user_id"] = $m_id;
				$RegisterCoupon["c_sn"] = "123456789".$m_id;
				$reg = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->data($RegisterCoupon)->add();

				if ($reg == false) {
					$getLastSql = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
					$result["message"]      = "领取失败";
			        $result["status"]      = "10001";
			        $result["getLastSql"]      = $getLastSql;
			        print_r(json_encode($result));
			        die; 
				}else {
                    $result["message"]      = "领取成功";
			        $result["status"]      = "10001";
			        print_r(json_encode($result));
			        die; 
				}
			}
    		
    	}

    }

    public function PageHotAreaData() {
        $result = array();
        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;
		// ->limit("{$startindex},{$limit}")

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->limit("{$startindex},{$limit}")
					   ->select();

		$lastestList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_new"=>"1","g.g_on_sale"=>"1"))
					   ->order('g.g_order desc,g.g_id')
					   ->select();

		$index_ad1 = D("MobileApi")->GetAdByAdname("hotarea_ad1");
		$index_ad2 = D("MobileApi")->GetAdByAdname("hotarea_ad2");
		$index_ad2 = D("MobileApi")->GetAdByAdname("hotarea_ad3");
		$result["index_ad1"] = $index_ad1;
        $result["index_ad2"] = $index_ad2;
        $result["index_ad3"] = $index_ad3;

        $result["hotList"] = $hotList;
        $result["newList"] = $lastestList;
        $result["brandHotList"] = $hotList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function PageGlobalAreaData() {
        $result = array();
        $MobileApi = D("MobileApi");

        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);

		$globalList = $MobileApi->GetGlobalGoodsList($pages,$limit);
        
		$index_ad1 = D("MobileApi")->GetAdByAdname("globalarea_ad1");
		$index_ad2 = D("MobileApi")->GetAdByAdname("globalarea_ad2");
		$result["index_ad1"] = $index_ad1;
        $result["index_ad2"] = $index_ad2;
        $result["globalList"] = $globalList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function ad(){
		$ad_name   = $_REQUEST["ad_name"];
		$M = M('');

		$sql = 'select n_aurl as n_id,n_imgurl,n_type,n_length,n_height from fx_adwap where n_status=1 and n_name="'.$ad_name.'" order by n_order asc';
		$rs = $M->query($sql);
		if($rs){

			foreach ($rs as $key => $value) {
			    $rs[$key]['n_imgurl'] = 'http://www.caizhuangguoji.com'.$value['n_imgurl'];
		    }

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

    public function GetHotList() {
        $result = array();

        $pages = empty($_REQUEST["page"])?"1":$_REQUEST["page"];
		$limit = empty($_REQUEST["pageSize"])?10:intval($_REQUEST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

        $GoodsInfo  = M("GoodsInfo");
        $field = "g.g_id,g.g_order,fx_goods_info.g_name,fx_goods_info.g_picture,fx_goods_info.g_price,fx_goods_info.g_salenum";

		$hotList = $GoodsInfo->field($field)
					   ->join("fx_goods as g on g.g_id=fx_goods_info.g_id")
					   ->where(array("g.g_hot"=>"1","g.g_on_sale"=>"1"))
					   ->limit("{$startindex},{$limit}")
					   ->order('g.g_order desc,g.g_id')
					   ->select();

        $result["hotList"] = $hotList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function ReviewGoods() {
        $result = array();
        $g_id   = $_REQUEST["g_id"];
        $m_id = empty($_REQUEST["m_id"])?"0":$_REQUEST["m_id"];
        $gcom_star_score = $_REQUEST["gcom_star_score"];
        $gcom_content = $_REQUEST["gcom_content"];
        $gcom_pics = $_REQUEST["gcom_pics"];
        $gcom_title = $_REQUEST["gcom_title"];
        $gcom_mbname = $_REQUEST["gcom_mbname"];
        $gcom_order_id = $_REQUEST["gcom_order_id"];
        $gcom_phone = $_REQUEST["gcom_phone"];

        if (empty($g_id) || empty($gcom_star_score) || empty($gcom_content) || empty($gcom_order_id)) {
        	$result["message"] = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
        }

        $EditData = array();
        $EditData["gcom_star_score"] = $gcom_star_score;
        $EditData["gcom_content"] = $gcom_content;
        $EditData["m_id"] = $m_id;
        $EditData["gcom_order_id"] = $gcom_order_id;
        $EditData["gcom_create_time"]  = date("Y-m-d H:i:s",time());
        $EditData["g_id"] = $g_id;

        if (!empty($gcom_pics)) {
        	$EditData["gcom_pics"] = $gcom_pics;
        }

        if (!empty($gcom_title)) {
        	$EditData["gcom_title"] = $gcom_title;
        }

        if (!empty($gcom_mbname)) {
        	$EditData["gcom_mbname"] = $gcom_mbname;
        }

        if (!empty($gcom_phone)) {
        	$EditData["gcom_phone"] = $gcom_phone;
        }

        $success = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->data($EditData)->add();
  
        if ($success) {
        	$result["message"]   = "评论成功";
		    $result["status"] = "10000";
    	    print_r(json_encode($result));
		    die; 
        }else {
        	$lastSql = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        	$result["message"]   = "评论失败";
		    $result["status"] = "10002";
		    $result["lastSql"] = $lastSql;
		    $result["success"] = $success;
    	    print_r(json_encode($result));

        }
    }

	/**
	 * 申请退款/退货页面
	 * @author czy<chenzongyao@guanyisoft.com>
	 * @date 2013-03-22
	 */
	public function AftersaledoAdd() {
       $ary_data = $this->_request();

		//获取页面POST过来的数据
        $params = $ary_data;
		$ary_where = array('o_id' => $params['o_id']);
        //判断是退款/退货。1是退款，2是退货
        if ($params['type'] == 1) {
            $ary_where['oi_ship_status'] = array('NEQ', 2);
        }
        if ($params['type'] == 2) {
            $ary_where['oi_ship_status'] = 2;
        }
        $ary_where['oi_refund_status'] = array('in',array(1,6));
        //满足退款、退货的商品


        $ary_orders = M('view_orders',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->select();

        $getLastSql = M('view_orders',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();

     //    $result["status"] = "10001";
	    // $result["ary_orders"] = $ary_orders;
	    // $result["getLastSql"] = $getLastSql;
	    // print_r(json_encode($result));
	    // die;

        if(empty($ary_orders)){
        	$result["message"]   = "您已提交退换货单申请，请耐心等待！";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
            // $this->errorResult(false,10101,array(),'您已提交退换货单申请，请耐心等待！');exit;
        }
		
		// $o_cost_feight = $this->checkOrderRefunds($ary_data);
       $o_cost_feight = $ary_orders[0]['o_cost_freight'];





//          echo "<pre>";print_r($ary_data);exit;
		//数据操作模型初始化
		$obj_refunds = D('OrdersRefunds');
		$date = date('Y-m-d H:i:s');

        //判断是否提交过（只能申请一次）
        // $ary_refunds = $obj_refunds->where(array('o_id'=>$ary_data['o_id']))->select();
        // if($ary_data['o_id'] == $ary_refunds['o_id']){
        //     $this->error('您已申请过，请耐心等待处理！');
        // }
		//验证是否传递要退款/退货的订单号
		if (!isset($ary_data['o_id']) || empty($ary_data['o_id'])) {
			$result["message"]   = "缺少订单号";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}




		//验证请选择原因
		if (!isset($ary_data['ary_reason']) || empty($ary_data['ary_reason'])) {
			$result["message"]   = "请选择原因";
			$result["status"] = "10001";
			$result["ary_data"] = $ary_data;
			print_r(json_encode($result));
			die;
		}



      
        $item_where['o_id'] = $ary_data['o_id'];
        if(!empty($ary_data['checkSon'])){
            $item_where['oi_id'] = array('in',$ary_data['checkSon']);
        }
        $orders_items_info = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where($item_where)->select();
       // echo M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();die;
        $total_price = 0;
        if (!empty($orders_items_info) && is_array($orders_items_info)) {
			foreach ($orders_items_info as $k => $v) {
				$item_promotion['promotion_price'] = $v['oi_coupon_menoy']+$v['oi_bonus_money']+$v['oi_cards_money']+$v['oi_jlb_money']+$v['oi_point_money']+$v['promotion_price'];
				$total_price += $v['oi_price']*$v['oi_nums'] - $item_promotion['promotion_price'];
			}
		}
		
		//退款时退运费
		$result_price = $total_price;
		if($ary_data['or_refund_type']==1){
			$result_price = $result_price+$o_cost_feight;
		}else{

			// $result = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM')->field(array('sc_key','sc_value'))->where(array('sc_module'=>$module_name))->select();
   //          $return = array();
   //          foreach($result as $v){
   //              $return[$v['sc_key']] = $v['sc_value'];
   //          }
			$refund_delivery = D('SysConfig')->getCfgByModule('ALLOW_REFUND_DELIVERY');
			// $refund_delivery = $return;
			if(isset($refund_delivery['ALLOW_REFUND_DELIVERY']) && $refund_delivery['ALLOW_REFUND_DELIVERY'] == 1){
				$result_price = $result_price+$o_cost_feight;
			}		
		}
		if($ary_data['or_refund_type']==1){
			$result_price = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id']))->getField('o_pay');
		}




		if(sprintf("%.2f", $_POST['application_money'])-sprintf("%.2f", $result_price)>0) {
			$result["message"]   = "退款金额不合法";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}


			
		//erp退款退货标志  2退款:3  退货:
		$refund_type = 0;

		//售后单据基本信息
		$ary_refunds = array(
			'o_id' => $ary_data['o_id'],
			'm_id' => $ary_data['m_id'],
			'or_money' => sprintf('%.2f',$ary_data['application_money']),
			'or_refund_type' => $ary_data['or_refund_type'],
			'or_create_time' => $date,
			'm_name' => $ary_data['m_name'],
			'or_buyer_memo'=>$this->_post('or_buyer_memo','htmlspecialchars','')
		);
		//已经退货商品
		$ary_refunds_items = array();
		//要退货或者退款商品 - 获取此订单的订单明细数据
		$ary_orders_items = D('OrdersItems')->field('oi_id,o_id,pdt_id,oi_price,oi_nums,g_sn,oi_g_name,erp_id')->where(array('o_id'=>$ary_data['o_id']))->select();
		
		$ary_temp_items = array();
		foreach($ary_orders_items as $val){
			$ary_temp_items[$val['oi_id']] = $val;
		}

		//区分不同的售后逻辑进行退款
		if($ary_data['or_refund_type']==1){
			//退款时此订单未发货，则退款金额如果用户输入，以用户输入为准
			//如果用户没有输入  或者输入的退款金额不合法，则取订单的付款金额
			//TODO：前端需要对用户输入退款金额的数字进行验证
			$ary_refunds['or_refund_type'] = 1;
			//退款金额的处理：判断退款金额是否合法
			if(!isset($_POST["application_money"]) || !is_numeric($_POST["application_money"]) || $_POST["application_money"] < 0){
				$result["message"]   = "退款金额不合法：必须是一个大于等于0的数字。";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
			$ary_refunds['or_money'] = sprintf('%.2f',$_POST["application_money"]);
				
			$refund_type = 2;
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==0){
			//未收到货，产生退款单,退款金额由双方协商确定
			//此时售后类型为退款，退款金额由双方协商确定
			$ary_refunds['or_refund_type'] = 1;
			$refund_type = 2;
			//对用户申请的退款金额合法性进行判断
			if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$result["message"]   = "未收到货，产生退款单,请正确填写退款金额";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
			$ary_refunds['or_money'] = sprintf('%.2f',$ary_data['application_money']);
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==0){
			//已收到货，且无需退货，退款金额由双方协商确定，生成的单据为退款单
			//这种情况是考虑到买家买到货以后不满意，退部分金额，此时售后类型也是退款
			$ary_refunds['or_refund_type'] = 1;
			$refund_type = 2;
			//对用户申请的退款金额合法性进行判断
			if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$result["message"]   = "已收到货且无需退货，产生退款单,请正确填写退款金额";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
			$ary_refunds['or_money'] = sprintf('%.2f',$ary_data['application_money']);
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==1){
		     //已收到货，且需要换货，无须退款金额
             //$ary_refunds['or_money'] = 0.00;
             //此处改造成退货
            if(!is_numeric($ary_data['application_money']) || $ary_data['application_money']<0){
				$result["message"]   = "已收到货且需退货，产生退货单,请正确填写退货金额";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
             $ary_refunds['or_money'] = sprintf('%.2f',$ary_data['application_money']);
			//已收到货，需退货，退款金额由选择商品确定，生成的单据为退货单
			if(!isset($ary_data['checkSon']) || empty($ary_data['checkSon'])){
				$result["message"]   = "请选择您要退货的商品。";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
				
			//TODO:如果勾选退货的商品为积分商城商品，则不允许退货！
				
			//买家将商品寄回时的物流单号，可不填（不填是由于买家自提或者上门送回的情况）
			$ary_refunds["or_return_logic_sn"] = (isset($ary_data['od_logi_no']) && "" != trim($ary_data['od_logi_no']))?trim($ary_data['od_logi_no']):"";
			//售后申请操作类型为退货
			$ary_refunds['or_refund_type'] = 2;
			$refund_type = 3;
		}
      
		//对用户申请的售后请求进行验证，分以下几种情况
		if($ary_refunds['or_refund_type'] == 1){
			/**
			 * 第一种情况：退款：
			 * 如果未发货，应该是一次性退全款；
			 * 如果已发货，且退款时不需要退货（买家对商品不满意，双方达成一致需要补偿的）；
			 * 系统中有且仅有一张关于此订单的退款单
			 */
			$ary_where = array(
				'o_id'=>$ary_data['o_id'],
				'm_id' => $ary_data['m_id'],
				'or_processing_status'=>array('neq',2),
				'or_refund_type'=>array('eq',1)
			);
			$ary_refunds_orders = D('OrdersRefunds')->where($ary_where)->select();
			if(false === $ary_refunds_orders){
				$result["message"]   = "无法验证此订单是否已经存在退款单。";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
			if(is_array($ary_refunds_orders) && count($ary_refunds_orders)>0){
				$result["message"]   = "已存在此订单对应的退款单，不能重复退款";
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			
			}
		}
        elseif($ary_refunds['or_refund_type'] == 2 && $ary_data['sh_radio'] == 1 && $ary_data['th_radio'] == 1){
			/**
			 * 第二种情况：退货并且用户已收到货且需要退货
			 * 此时需要对退货的商品进行验证：退货数量不能超过（此SKU的购买量-为作废已申请退货数量）
			 * TODO:退款此处先走不通。。。。待调试
			 */
              
			//商品可能部分退掉进行商品数量判断
			foreach ($ary_data['checkSon'] as $v) {
				if(!empty($ary_data['inputNum'][$v]) && isset($ary_returns_temp[$v])) {
					if(!ctype_digit($ary_data['inputNum'][$v])){
						$result["message"]   = "退货数量填写需正整数";
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;
					}
					if($ary_data['inputNum'][$v]>$ary_returns_temp[$v]['nums']){
						$result["message"]   = "商品编号是{$ary_returns_temp[$v]['g_sn']}退货数量不能大于购买商量";
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;
					}
					if(($ary_data['inputNum'][$v] + $ary_returns_temp[$v]['num'] )> $ary_returns_temp[$v]['nums']){
						$str_th_sum = intval($ary_returns_temp[$v]['nums'] - $ary_returns_temp[$v]['num']);
						if($str_th_sum>0){
							$result["message"]   = "商品编号是{$ary_returns_temp[$v]['g_sn']}的退货数量只能退{$str_th_sum}件";
							$result["status"] = "10001";
							print_r(json_encode($result));
							die;
						}
						else{
							$result["message"]   = "商品编号是{$ary_returns_temp[$v]['g_sn']}的已经退过货，不能重复退货";
							$result["status"] = "10001";
							print_r(json_encode($result));
							die;
						}
					}
				}
			}
			$ary_where = array(
				'fx_orders_refunds.o_id'=>$ary_data['o_id'],
				'fx_orders_refunds.m_id' => $ary_data['m_id'],
				'fx_orders_refunds.or_processing_status'=>array('neq',2),
				'fx_orders_refunds.or_refund_type'=>2
			);
			$ary_returns_orders = D('OrdersRefunds')
			->field('fx_orders_items.pdt_id,fx_orders_items.oi_nums,fx_orders_refunds_items.ori_num,fx_orders_items.g_sn')
			->join('left join fx_orders_refunds_items on fx_orders_refunds.or_id = fx_orders_refunds_items.or_id')
			->join(" fx_orders_items ON fx_orders_refunds_items.oi_id=fx_orders_items.oi_id")
			->where($ary_where)
			->select();


			if($ary_returns_orders){
				//已经加入的退货单商品详情
				$ary_returns_temp = array();
				foreach($ary_returns_orders as $val) {

					if(!isset($ary_returns_temp[$val['pdt_id']])){
						$ary_returns_temp[$val['pdt_id']]['num'] = $val['ori_num'];//已退货的货号商品总数
						$ary_returns_temp[$val['pdt_id']]['nums'] = $val['oi_nums'];//此订单货号总数
						$ary_returns_temp[$val['pdt_id']]['g_sn'] = $val['g_sn'];
					}
					else{
						$ary_returns_temp[$val['pdt_id']]['num'] += $val['ori_num'];
					}
				}
					

			}
			else{
				foreach ($ary_data['checkSon'] as $v) {

					if(!empty($ary_data['inputNum'][$v]) && isset($ary_temp_items[$v])) {
						if(!ctype_digit($ary_data['inputNum'][$v])){
                            $result["message"]   = "退货数量填写需正整数";
							$result["status"] = "10001";
							print_r(json_encode($result));
							die;
                        }
						if($ary_data['inputNum'][$v]>$ary_temp_items[$v]['oi_nums']){
                            $result["message"]   = "商品编号是{$ary_temp_items[$v]['g_sn']}退货数量不能大于购买商量";
							$result["status"] = "10001";
							print_r(json_encode($result));
							die;
                        }
					}
				}
			}
         
		}
        //上传图片
        if($_FILES['upload_file_0']['name']){
			$path = './Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/';
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/', 0777, true);
			}
			
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
			$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$ary_refunds['or_picture'] = '/Public/Uploads/'.CI_SN.'/images/aftersale/' .date('Ymd').'/'. $info[0]['savename'];
				$ary_refunds['or_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_refunds['or_picture']);
			}
    	}

        $ary_refunds['or_reason'] = $ary_data['ary_reason'];
        $ary_refunds['or_return_sn'] = strtotime("now");
        //$this->assign('ary_extend_data', $ary_extend_data);
		//售后数据存入数据库  需要启用事务机制
		M('', '', 'DB_CUSTOM')->startTrans();
		$ary_refunds['or_update_time'] = date('Y-m-d H:i:s');
		//插入退款主表
		$int_or_id = D('OrdersRefunds')->add($ary_refunds);
		if (false === $int_or_id) {
		   // var_dump($int_or_id);exit;
			M('', '', 'DB_CUSTOM')->rollback();
			$result["message"]   = "售后申请提交失败。";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}
        
        /*附加属性组装数据 start*/
        $ary_extend_temp = array();
       
        $ary_extend_data=D('RefundsSpec')->getSpecByType($ary_data['or_refund_type']);
       
        foreach($ary_extend_data as $val) {
            switch($val['gs_input_type']){
                case 1://文本框
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        //echo $ary_data['extend_field_'.$val['gs_id']];var_dump(trim(htmlspecialchars($ary_data['extend_field_'.$val['gs_id']],'ENT_QUOTES')));exit;
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>trim($ary_data['extend_field_'.$val['gs_id']]));
                      }
                   break;
                case 2:
                    if($_FILES['upload_file_'.$val['gs_id']]['name']){
			            @mkdir('./Public/Uploads/' . CI_SN.'/images/'.date('Ymd').'/');
				    	//import('ORG.Net.UploadFile');
						$upload = new UploadFile();// 实例化上传类
						$upload->maxSize  = 3145728 ;// 设置附件上传大小
						$upload->allowExts = array('rar', 'zip');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
						$upload->savePath =  './Public/Uploads/'.CI_SN.'/images/'.date('Ymd').'/';// 设置附件上传目录
						if(!$upload->upload()) {// 上传错误提示错误信息
							$this->error($upload->getErrorMsg());
						}else{// 上传成功 获取上传文件信息
							$info =  $upload->getUploadFileInfo();
							$ary_data['extend_field_'.$val['gs_id']] = '/Public/Uploads/'.CI_SN.'/images/' .date('Ymd').'/'. $info[0]['savename'];
						}
			    	}
                   //附件
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        //$ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>'/'.str_replace("//","/",ltrim(str_replace('Lib/ueditor/php/../../../','',$ary_data['extend_field_'.$val['gs_id']]),'/')));
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>$ary_data['extend_field_'.$val['gs_id']]);
				      }
                   break;
                case 3://文本域
                      if(isset($ary_data['extend_field_'.$val['gs_id']]) && !empty($ary_data['extend_field_'.$val['gs_id']])) {
                        $ary_extend_temp[] = array('or_id'=>$int_or_id,'gs_id'=>$val['gs_id'],'content'=>trim($ary_data['extend_field_'.$val['gs_id']],'ENT_QUOTES'));
                      } 
                   break; 
                default:
                  break;
            }
        }
       
        if(count($ary_extend_temp)>0) {
                $int_return_refund_spec = D('RelatedRefundSpec')->addAll($ary_extend_temp);
                //var_dump($int_return_refund_spec);exit;
			     if (false == $int_return_refund_spec) {
				        M('', '', 'DB_CUSTOM')->rollback();
				        $result["message"]   = "批量插入自定义属性失败";
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;
			   }
        }
      
        /*附加属性组装数据 end*/

		//自动生成售后单据编号，单据编号的规则为20130628+8位单据ID（不足8位左侧以0补全）
		$int_tmp_or_id = $int_or_id;
		$or_return_sn = date("Ymd") . sprintf('%07s',$int_tmp_or_id);
		$array_modify_data = array("or_return_sn"=>$or_return_sn);
		$mixed_result = D('OrdersRefunds')->where(array("or_id"=>$int_or_id,'or_update_time'=>date('Y-m-d H:i:s')))->save($array_modify_data);
		if(false === $mixed_result){
			M('', '', 'DB_CUSTOM')->rollback();
			$result["message"]   = '售后申请提交失败。CODE:CREATE-REFUND-SN-ERROR.';
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}
		//插入明细表
		$ary_refunds_items = array();
		if($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==1){
			$or_money = 0;
			//商品可能部分退掉
			foreach ($ary_data['checkSon'] as $v) {
				if(!empty($ary_data['inputNum'][$v]) && isset($ary_temp_items[$v])) {
					$ary_refunds_items[] = array(
							'o_id' => $ary_temp_items[$v]['o_id'],
							'or_id' => $int_or_id,
							'oi_id' => $ary_temp_items[$v]['oi_id'],
							'ori_num' => $ary_data['inputNum'][$v],
							'erp_id' =>$ary_temp_items[$v]['erp_id']
					);
					$or_money +=  $ary_data['inputNum'][$v]*$ary_temp_items[$v]['oi_price']/$ary_temp_items[$v]['oi_nums'];

				}
			}
				
			//批量插明细表
			$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);

			if (false === $int_return_refunds_itmes) {
				M('', '', 'DB_CUSTOM')->rollback();
				$result["message"]   = '批量插入明细失败';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
            
            //更改订单详情表商品退货状态
            foreach ($ary_data['checkSon'] as $oi_id){
                if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id'],'oi_id'=>$oi_id))->data(array('oi_refund_status'=>3))->save()){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $result["message"]   = '更新退货状态失败';
					$result["status"] = "10001";
					print_r(json_encode($result));
					die;
                }
            }
		}elseif($ary_data['or_refund_type']==2 && $ary_data['sh_radio']==1 && $ary_data['th_radio']==0){
            $or_money = 0;
            foreach ($ary_temp_items as $v) {
                $ary_refunds_items[] = array(
                            'o_id' => $v['o_id'],
                            'or_id' => $int_or_id,
                            'oi_id' => $v['oi_id'],
                            'ori_num' => $v['oi_nums'],
                            'erp_id' =>$v['erp_id']
                );
                $or_money +=  $v['oi_price']*$v['oi_nums'];
            }
			//跨境贸易
			if(isset($orders_res['o_tax_rate']) && $orders_res['o_tax_rate']>0){
				$or_money += $orders_res['o_tax_rate'];
			}
            //print_r($ary_refunds_items);exit;
            //批量插明细表
            //获取物流费用
            $o_cost_freight = D('Orders')->where(array('o_id'=>$ary_data['o_id']))->getField('o_cost_freight');
			if(sprintf("%.2f",$or_money+$o_cost_freight)-sprintf("%.2f",$ary_data['application_money'])>=0) {
				if($ary_refunds_items){
					$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);
					if (!$int_return_refunds_itmes) {
						M('', '', 'DB_CUSTOM')->rollback();
						$result["message"]   = '批量插入明细失败';
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;
					}
				}
			}else {
				//暂时隐藏
				$result["message"]   = '输入退款金额必须小于订单商品总金额';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
            //更改订单详情表商品退款状态

            if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id']))->data(array('oi_refund_status'=>2))->save()){
                M('', '', 'DB_CUSTOM')->rollback();
                $result["message"]   = '更新退货状态失败';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
            }
            
        }elseif($ary_data['or_refund_type']==1){
			$or_money = 0;
			foreach ($ary_temp_items as $v) {
				$ary_refunds_items[] = array(
							'o_id' => $v['o_id'],
							'or_id' => $int_or_id,
							'oi_id' => $v['oi_id'],
							'ori_num' => $v['oi_nums'],
							'erp_id' =>$v['erp_id']
				);
				$or_money +=  $v['oi_price']*$v['oi_nums'];
			}
			//跨境贸易
			if(isset($orders_res['o_tax_rate']) && $orders_res['o_tax_rate']>0){
				$or_money += $orders_res['o_tax_rate'];
			}
			
			if($ary_data['or_refund_type']==1){
				//当商品有折扣，按照商品总价打折计算的订单四舍五入成小数位两位的总价大于按照商品单价打折后乘以件数计算总价时，申请全额退款会报错
				$or_money = $result_price;
			}
			//批量插明细表
            //获取物流费用
            $o_cost_freight = D('Orders')->where(array('o_id'=>$ary_data['o_id']))->getField('o_cost_freight');
			if(sprintf("%.2f",$or_money+$o_cost_freight)-sprintf("%.2f",$ary_data['application_money'])>=0) {
				if($ary_refunds_items){
					$int_return_refunds_itmes = D('OrdersRefundsItems')->addAll($ary_refunds_items);
					if (!$int_return_refunds_itmes) {
						M('', '', 'DB_CUSTOM')->rollback();
						$result["message"]   = '批量插入明细失败';
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;
					}
				}
			}else {
				//暂时隐藏
				$result["message"]   = '输入退款金额必须小于订单商品总金额';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
            //更改订单详情表商品退款状态
 
            if(false === M('orders_items',C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id'=>$ary_data['o_id']))->data(array('oi_refund_status'=>2))->save()){
                M('', '', 'DB_CUSTOM')->rollback();
                $result["message"]   = '更新退货状态失败';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
            }
            
		}

		//用户提示语定义
		$str_type = '售后';
		switch($refund_type){
			case 2:
				$str_type = '退款';
				break;
			case 3:
				$str_type = '退货';
				break;
		}
		//判读是否需要拆分
		$orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
		$order_info = $orders->where(array('o_id'=>$ary_data['o_id']))->find();
		$resdata1 = D('SysConfig')->getCfg('ORDERS_REMOVE','ORDERS_REMOVE','1','是否开启订单拆分');
		$resdata2 = D('SysConfig')->getCfg('ORDERS_REMOVETYPE','ORDERS_REMOVETYPE','1','订单拆分方式(1:自动拆分;0:手动拆分)');
		if(($resdata1['ORDERS_REMOVE']['sc_value'] == '1') && ($resdata2['ORDERS_REMOVETYPE']['sc_value'] == '1')){
			if($order_info['is_diff'] == '1'){
				//售后拆单$int_or_id
				$erp_ids = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->field('erp_id')->where(array('or_id'=>$int_or_id))->group('erp_id')->select();
				if(count($erp_ids) == '1'){
					$res_refund = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->data(array('or_update_time'=>date('Y-m-d H:i:s')))->save();
					if(false === $res_refund){
						M('', '', 'DB_CUSTOM')->rollback();
						$result["message"]   = '售后单更新失败';
						$result["status"] = "10001";
						print_r(json_encode($result));
						die;						
					}
				}else{
					foreach($erp_ids as $erp){
						$refund_data = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id))->find();
						$refund_data['initial_tid'] = $int_or_id;
						unset($refund_data['or_id']);
						$refund_data['or_money'] = 0;
						$refund_data['erp_id'] = $erp['erp_id'];
						$refund_data['or_update_time'] = date('Y-m-d H:i:s');
						$res_refund_id = M('orders_refunds', C('DB_PREFIX'), 'DB_CUSTOM')->data($refund_data)->add();
						if(!$res_refund_id){
							M('', '', 'DB_CUSTOM')->rollback();
							$result["message"]   = '售后单拆单失败';
							$result["status"] = "10001";
							print_r(json_encode($result));
							die;					
						}
						$refund_items_data = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('or_id'=>$int_or_id,'erp_id'=>$erp['erp_id']))->select();
						foreach ($refund_items_data as $refund_items){
							unset($refund_items['ori_id']);
							$refund_items['or_id'] = $res_refund_id;
							
							$refund_item_res = M('orders_refunds_items', C('DB_PREFIX'), 'DB_CUSTOM')->data($refund_items)->add();
							if(!$refund_item_res){
								M('', '', 'DB_CUSTOM')->rollback();
								$result["message"]   = '售后单新增失败';
								$result["status"] = "10001";
								print_r(json_encode($result));
								die;
							}
						}	
					}
				}
			}else{
				M('', '', 'DB_CUSTOM')->rollback();
				$result["message"]   = '此订单拆单之后才可进行售后操作';
				$result["status"] = "10001";
				print_r(json_encode($result));
				die;
			}
		}

		//单据id:$int_or_id;订单号：$ary_data['o_id'] 
		//更新日志表
		$ary_orders_log = array(
				'o_id'=>$ary_data['o_id'],
				'ol_behavior' => '会员新增售后申请',
				'ol_uname'=>$ary_data['m_name']
		);
		$res_orders_log = D('OrdersLog')->addOrderLog($ary_orders_log);
		if(!$res_orders_log){
			M('', '', 'DB_CUSTOM')->rollback();
			$result["message"]   = '会员新增售后申请日志失败';
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}
		$res_orders = D('Orders')
		->data(array('o_update_time'=>date('Y-m-d H:i:s')))
		->where(array('o_id'=>$ary_data['o_id']))->save();
		
		if(!$res_orders){
			M('', '', 'DB_CUSTOM')->rollback();
			$result["message"]   = '会员新增售后申请失败';
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}
		//事务提交
		M('', '', 'DB_CUSTOM')->commit();
        $result["message"]   = "{$str_type}请求提交成功。";
		$result["status"] = "10000";
		print_r(json_encode($result));
		die;
	}

	public function GetRefundsReasons() {
		$result = array();

        $field = 'rr_id,rr_name';
        $RefundsReasons = M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("rr_status"=>"1"))->select();

        if (!$RefundsReasons) {
        	$result["message"]   = "请求失败";
		    $result["status"] = "10001";
		    print_r(json_encode($result));
		    die;
        }

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["RefundsReasons"] = $RefundsReasons;
		print_r(json_encode($result));
		die;
	}

	public function GetRefundsGoodsDetail() {
		$result = array();
		$orderId = $_REQUEST['o_id'];
		$g_id = $_REQUEST['g_id'];

        $field = 'or_processing_status,or_money,or_refund_type,or_return_logic_sn,or_refunds_type,or_refuse_reason';
        $RefundsDetail = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$orderId))->find();

        if (!$RefundsDetail) {
        	$result["message"]   = "请求失败";
		    $result["status"] = "10001";
		    print_r(json_encode($result));
		    die;
        }

		$result["message"]   = "请求成功";
		$result["status"] = "10000";
		$result["RefundsDetail"] = $RefundsDetail;
		print_r(json_encode($result));
		die;
	}

    public function GetVideoCategory() {
        $result = array();

        $M  = M("videos_category");
        $field = "vc_id,vc_name";

		$videocategory = $M->field($field)
					   ->select();

        $result["videocategory"] = $videocategory;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }	

    public function GetVideoLesson() {
        $result = array();

        $M  = M("videos_lesson");
        $field = "*";

		$videolesson = $M->field($field)
					   ->select();

        $result["videolesson"] = $videolesson;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function GetVideoTeacher() {
        $result = array();

        $M  = M("videos_teacher");
        $field = "*";

		$videoteacher = $M->field($field)
					   ->select();
		foreach ($videoteacher as $key => $value) {
			if($value['t_photo']){
				$videoteacher[$key]['t_photo'] = 'http://www.caizhuangguoji.com/Public/Uploads/teacher/'.$value['t_photo'];
			}
		}			   
        $result["videoteacher"] = $videoteacher;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

	public function GetVideoList() {
        $result = array();

        $pages = empty($_REQUEST["page"])?"1":$_REQUEST["page"];
		$limit = empty($_REQUEST["pageSize"])?10:intval($_REQUEST["pageSize"]);
		$page  = max(1, intval($pages));
		$startindex=($page-1)*$limit;

		$where = 1;
		if($_REQUEST["v_category_id"]){
			$where.= " and fx_videos_info.v_category_id=".$_REQUEST["v_category_id"];
		}
		if($_REQUEST["v_lesson_id"]){
			$where.= " and fx_videos_info.v_lesson_id=".$_REQUEST["v_lesson_id"];
		}
		if($_REQUEST["v_teacher_id"]){
			$where.= " and fx_videos_info.v_teacher_id=".$_REQUEST["v_teacher_id"];
		}

        $M = M("videos_info");

		$videoList = $M->field('fx_videos_info.v_id,fx_videos_info.v_name,fx_videos_info.v_code,fx_videos_info.v_picture,fx_videos_teacher.t_name,fx_videos_teacher.t_photo')
					   ->join('left join fx_videos_teacher on fx_videos_info.v_teacher_id = fx_videos_teacher.t_id')
					   ->where($where)
					   ->limit("{$startindex},{$limit}")
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
        $result["videoList"] = $videoList;
    	$result["message"]   = "请求成功";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die; 
    }

    public function MemberVerify() {
    	
    	$result = array();

    	$m_id = $_REQUEST["m_id"];
    	$m_id_card = $_REQUEST["m_id_card"];
    	$m_real_name = $_REQUEST["m_real_name"];
    	$m_id_card_face_picture = $_REQUEST["m_id_card_face_picture"];
    	$m_id_card_opposite_picture = $_REQUEST["m_id_card_opposite_picture"];

    	if (empty($m_id) || empty($m_id_card) || empty($m_real_name) || empty($m_id_card_face_picture) || empty($m_id_card_opposite_picture)) {
    		$result["message"]   = "参数错误";
		    $result["status"] = "10001";
    	    print_r(json_encode($result));
    	    die;
    	}

    	$saveData = array();
    	$saveData["m_id"] = $m_id;
    	$saveData["m_id_card"] = $m_id_card;
    	$saveData["m_real_name"] = $m_real_name;
    	$saveData["m_id_card_face_picture"] = $m_id_card_face_picture;
    	$saveData["m_id_card_opposite_picture"] = $m_id_card_opposite_picture;

    	$reg = D('members_verify')->where(array('m_id'=>$m_id))->find();
    	if ($reg) {
    		$savesuccess = D("members_verify")->data($saveData)->save();
    		if ($savesuccess) {
    		    $result["message"]  = "认证申请提交成功";
		        $result["status"] = "10000";
    	        print_r(json_encode($result));
    	        die;
    	    }else {
                $result["message"]   = "认证申请提交失败";
		        $result["status"] = "10001";
    	        print_r(json_encode($result));
    	        die;
    	    }
    	}else {
    		$addsuccess = D("members_verify")->data($saveData)->add();
    		if ($addsuccess) {
    		    $result["message"]  = "认证申请提交成功";
		        $result["status"] = "10000";
    	        print_r(json_encode($result));
    	        die;
    	    }else {
                $result["message"]   = "认证申请提交失败";
		        $result["status"] = "10001";
    	        print_r(json_encode($result));
    	        die;
    	    }
    	}
    	
    }

    public function FileUpload(){
    	if (!$_FILES['upload_file']['name']) {
    		$result["message"]   = "没有选择文件";
		    $result["status"] = "10001";
    	    print_r(json_encode($result));
    	    die;
    	}
    	//上传图片
    	$upload = new UploadFile();// 实例化上传类
		$upload->maxSize  = 3145728 ;// 设置附件上传大小
		$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp'); 
        if($_FILES['upload_file']){
			$path = './Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/';
			if(!file_exists($path)){
				@mkdir('./Public/Uploads/' . CI_SN.'/images/aftersale/'.date('Ymd').'/', 0777, true);
			}
			
	    	//import('ORG.Net.UploadFile');
			// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
			$upload->savePath =  $path;// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$result["message"]   = $upload->getErrorMsg();
			    $result["status"] = "10001";
	    	    print_r(json_encode($result));
	    	    die;
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$result["path"] = '/Public/Uploads/'.CI_SN.'/images/aftersale/' .date('Ymd').'/'. $info[0]['savename'];
				$result["message"]   = "文件上传成功";
		    	$result["status"] = "10000";
    	    	print_r(json_encode($result));
    	    	die;
			}
    	}		
    }

    public function GetGlobalGoodsList() {
    	$result = array();
        $MobileApi = D("MobileApi");

        $pages = empty($_POST["page"])?"1":$_POST["page"];
		$limit = empty($_POST["pageSize"])?10:intval($_POST["pageSize"]);

		$globalList = $MobileApi->GetGlobalGoodsList($pages,$limit);
        if ($globalList) {
        	$result["globalList"] = $globalList;
    	    $result["message"]   = "请求成功";
		    $result["status"] = "10000";
    	    print_r(json_encode($result));
		    die;
        }
    	$result["message"]   = "暂无所需数据";
		$result["status"] = "10001";
    	print_r(json_encode($result));
		die;   
    }

    public function GetAppStoresVersion() {
    	$result = array();
    	$result["version"]   = "1.0.5";
		$result["status"] = "10000";
    	print_r(json_encode($result));
		die;   
    }

    public function GetGuessULikeGoodsList() {
    	$result = array();
		$MobileApi = D("MobileApi");
        $glist = $MobileApi->GuessULike();
        if ($glist) {
        	$result["status"] = "10000";
        	$result["message"] = "请求成功";
        	$result["glist"] = $glist;
        }else {
            $result["status"] = "10001";
        	$result["message"] = "无数据";
        }

    	print_r(json_encode($result));
		die;   
    }

    public function testdata() {
		$ApiUtil = D("ApiUtil");
        $glist = $ApiUtil->GetFilterSpecialGoodsList(null,null,null,null,null,null,null,null,null);

    	print_r(json_encode($glist));
		die;   
    }



}