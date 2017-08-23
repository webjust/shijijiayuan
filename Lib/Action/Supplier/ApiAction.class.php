<?php
/**
 * 后台资讯控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.2
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-6-03
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiAction extends AdminAction{
	
	public function _initialize() {
		parent::_initialize();
		$this->setTitle(' - '.L('MENU7_7'));
	}

	public function pageSet(){
		$this->getSubNav(8, 7, 10);
		//开启SAAS
		if(SAAS_ON == TRUE){
			//获取用户appkey
			$saas = new GyApi();
			$ary_param = array(
					'client_sn' => CI_SN
			);
			$ary_saas = $saas->getErpApiAppSecret($ary_param);
		}else{
			$ary_saas['data'] = array(
				'app_secret' => APP_SECRET,
				'app_key'	=> CI_SN
			);
		}
		$sys_obj = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM');
		$host = $sys_obj->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
		$this->assign('appInfo',$ary_saas['data']);
		$this->assign('hostUrl',$host['sc_value'].'Erpapi/Index/index');
		$this->display('pageSetting');
	}

	/**
     * 授权添加页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function yunerppageSet(){
		$this->getSubNav(8, 7, 20);
        $ary_data = D('SysConfig')->getCfgByModule('GY_YUNERP');
        $this->assign('data',$ary_data);
		$this->display();
	}
	
    /**
     * 将提交的授权写入数据库
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
     */
    public function DoSetyunerp(){
		$ary_data = $this->_post();
        if(!empty($ary_data['user_code'])){
            $str_url="http://opentest.guanyierp.com/rest/core";
            $ary_parameter['method']='gy.download.user.bind';
            $ary_parameter['tenantcode']=$ary_data['user_code'];
            $ary_parameter['username']=$ary_data['user_name'];
            $ary_parameter['pwd']=$ary_data['user_pwd'];
            $res=makeRequest($str_url, $ary_parameter, 'POST');
            $ary_data=json_decode($res, true);
            if(!$ary_data['status']){
                D('SysConfig')->setConfig('GY_YUNERP', 'USER_CODE', $ary_data['user_code']);
                D('SysConfig')->setConfig('GY_YUNERP', 'USER_NAME', $ary_data['user_name']);
                D('SysConfig')->setConfig('GY_YUNERP', 'USER_PWD', $ary_data['user_pwd']);
                D('SysConfig')->setConfig('GY_YUNERP', 'TENANTID', $ary_data['tenantId']);
                D('SysConfig')->setConfig('GY_YUNERP', 'APPKEY', $ary_data['appKey']);
                D('SysConfig')->setConfig('GY_YUNERP', 'APPSECRET', $ary_data['appSecret']);
                D('SysConfig')->setConfig('GY_YUNERP', 'SESSIONKEY', $ary_data['sessionKey']);
                $this->success('店铺授权成功', U('Admin/Api/yunerppageSet'));
            }else{
                $this->error('店铺授权失败', U('Admin/Api/yunerppageSet'));
                return false;
            }
        }
	}
	
	/**
	 * 
	 * 数据迁移(老的b2cTo新fx)
	 * 
	 * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
	 */
	public function b2cToFx(){
		echo '数据迁移中...让数据飞一会吧...<br />';
		echo '会员信息迁移中...<br />';
		//会员主表
		$mem_obj = M('members',C('DB_PREFIX'),'DB_CUSTOM');
		$m_sql1 = "truncate table fx_members";
		$mres1 = $mem_obj->execute($m_sql1);
		echo '会员主表已清空,等待迁移会员主表数据...<br />';
		$m_sql2="insert into fx_members(m_id,m_status,m_name,m_real_name,m_password,m_email,
    	cr_id,m_address_detail,m_zipcode,m_birthday,m_sex,m_telphone,m_mobile,ml_id,m_create_time,total_point) 
		select u_id,status,u_name,u_realname,u_pwd,u_email,
		(select cr_id from fx_city_region where cr_name=(select old_area.area_name from yunkai_new.cbd_areainfo as old_area 
		where old_area.area_code=old_member.u_district) and cr_parent_id=(select cr_id from fx_city_region where cr_name=(select old_area.area_name from yunkai_new.cbd_areainfo as old_area 
		where old_area.area_code=old_member.u_city)) limit 1) as cr_id ,
		u_address,u_postcode,u_birthday,if(old_member.u_gender='f','0','1') as u_gender,u_phone,u_mobile,ifnull(old_member.grade,3) as grade,create_time,point_total 
    	from yunkai_new.cbd_member as old_member";
		$res2 = $mem_obj->execute($m_sql2);
		if($res2){
			echo '<font style="color:blue;">会员主表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">会员主表数据迁移失败！！！</font><br />';
		}
		echo '-------------------------------------------------------------<br />';
		echo '会员等级迁移中...<br />';
		$memlevel_obj = M('members_level',C('DB_PREFIX'),'DB_CUSTOM');
		$ml_sql1 = "truncate table fx_members_level";
		$mres1 = $memlevel_obj->execute($ml_sql1);
		echo '会员等级表已清空,等待迁移会员等级表数据...<br />';
		$ml_sql2 = "
		insert into fx_members_level(ml_id,ml_name,ml_default,ml_order,ml_create_time,ml_update_time,ml_discount) 
		select IFNULL(grade,3),name,if(grade=0,'1','0'),position,create_time,modified,(discount*10) as discount	
		from yunkai_new.cbd_membergrade ";
		$mlres2 = $memlevel_obj->execute($ml_sql2);
		if($mlres2){
			echo '<font style="color:blue;">会员等级表数据迁移成功，共迁移'.$mlres2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">会员等级表表数据迁移失败！！！</font><br />';
		}
		echo '-------------------------------------------------------------<br />';
		echo '会员分组迁移中...<br />';
		$mg_obj = M('members_group',C('DB_PREFIX'),'DB_CUSTOM');
		$mg_sql3 = "select count(*) as num from yunkai_new.cbd_membergroup";
		$mgres3 = $mg_obj->query($mg_sql3);
		if($mgres3[0]['num'] == '0'){
			echo '会员分组数组为空，不需要迁移<br />';
		}else{
			$mg_sql1 = "truncate table fx_members_group";
			$mres1 = $mg_obj->execute($mg_sql1);
			echo '会员分组已清空,等待迁移会员分组数据...<br />';
			$mg_sql2 = "
			insert into fx_members_group(mg_id,mg_name,mg_info,mg_create_time,mg_update_time) 
			select id,name,description,create_time,modified 	
			from yunkai_new.cbd_membergroup ";
			$mgres2 = $mg_obj->execute($mg_sql2);
			if($mgres2){
				echo '<font style="color:blue;">会员分组表数据迁移成功，共迁移'.$mgres2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">会员分组表数据迁移失败！！！</font><br />';
			}
		}
		echo '-------------------------------------------------------------<br />';
		echo '会员收货地址迁移中...<br />';
		$ma_obj = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM');
		$ma_sql3 = "select count(*) as num from yunkai_new.cbd_member_address";
		$mares3 = $ma_obj->query($ma_sql3);
		if($mares3[0]['num'] == '0'){
			echo '会员收货地址为空，不需要迁移<br />';
		}else{
			$ma_sql1 = "truncate table fx_receive_address";
			$mares1 = $ma_obj->execute($ma_sql1);
			echo '会员收货地址已清空,等待迁移会员收货地址数据...<br />';
			$ma_sql2 = "
			insert into fx_receive_address(ra_id,m_id,ra_is_default,ra_name,cr_id,ra_detail,ra_post_code,ra_phone,ra_mobile_phone) 
			select a_id,u_id,is_default,name,
			(select cr_id from fx_city_region where cr_name=(select old_area.area_name from yunkai_new.cbd_areainfo as old_area 
			where old_area.area_code=ma.u_district) and cr_parent_id=(select cr_id from fx_city_region where cr_name=(select old_area.area_name from yunkai_new.cbd_areainfo as old_area 
			where old_area.area_code=ma.u_city)) limit 1) as cr_id ,
			u_address,u_postcode,u_phone,u_mobile 
			from yunkai_new.cbd_member_address as ma ";
			$mares2 = $ma_obj->execute($ma_sql2);
			if($mares2){
				echo '<font style="color:blue;">会员收货地址表数据迁移成功，共迁移'.$mares2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">会员收货地址表数据迁移失败！！！</font><br />';
			}
		}
		echo '-------------------------------------------------------------<br />';
		echo '会员分组关系表迁移中...<br />';
		$mal_obj = M('receive_address',C('DB_PREFIX'),'DB_CUSTOM');
		$mal_sql3 = "select count(*) as num from yunkai_new.cbd_member_group";
		$malres3 = $mal_obj->query($mal_sql3);
		if($malres3[0]['num'] == '0'){
			echo '会员分组关系表为空，不需要迁移<br />';
		}else{
			$mal_sql1 = "truncate table fx_related_members_group";
			$malres1 = $mal_obj->execute($mal_sql1);
			echo '会员分组关系表已清空,等待迁移会员分组关系数据...<br />';
			$mal_sql2 = "
			insert into fx_related_members_group(m_id,mg_id) 
			select u_id,group_id 
			from yunkai_new.cbd_member_group";
			$malres2 = $mal_obj->execute($mal_sql2);
			if($malres2){
				echo '<font style="color:blue;">会员分组关系表数据迁移成功，共迁移'.$malres2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">会员分组关系表数据迁移失败！！！</font><br />';
			}
		}
		echo '-------------------------------------------------------------<br />';
		echo '商品主表迁移中...<br />';
		$obj = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_top_itemextra";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '商品主表为空，不需要迁移<br />';
		}else{
			$sql4= "ALTER TABLE fx_goods_info DROP FOREIGN KEY fx_goods_info_ibfk_1";
			$res4 = $obj->execute($sql4);
			$sql1 = "truncate table fx_goods";
			$res1 = $obj->execute($sql1);
			echo '商品主表已清空,等待迁移商品主表数据...<br />';
			$sql2 = "
			insert into fx_goods(g_id,gb_id,g_on_sale,g_sn,g_on_sale_time,g_off_sale_time,g_create_time,g_update_time,erp_guid,g_is_prescription_rugs,gt_id,g_gifts) 
			select ext.Id,gyex.brand_id,if(ext.approve_status='onsale','1','2') as approve_status,item.outer_id,ext.list_time,
			ext.delist_time,ext.created,ext.modified,ext.num_iid,if(ext.management='处方药','1','0') as is_pres,1,0   
			from yunkai_new.cbd_top_itemextra as ext inner join  yunkai_new.cbd_top_item as item on(ext.num_iid=item.num_iid)
			inner join yunkai_new.cbd_gy_itemextra as gyex on(ext.num_iid=gyex.num_iid) where ext.approve_status='onsale' 
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">商品主表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品主表数据迁移失败！！！</font><br />';
			}
			$sql5= "ALTER TABLE fx_goods_info add CONSTRAINT `fx_goods_info_ibfk_1` FOREIGN KEY (`g_id`) REFERENCES `fx_goods` (`g_id`)";
			$res5 = $obj->execute($sql5);
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '商品详情表迁移中...<br />';
		$obj = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_top_itemextra";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '商品详情表为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_goods_info";
			$res1 = $obj->execute($sql1);
			echo '商品详情表已清空,等待迁移商品详情表数据...<br />';
			$path = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/fx093433/goods/move/';
			if (!file_exists($path)){
				mkdir ($path);
			}
			$sql2 = "
			insert into fx_goods_info(g_id,g_name,g_keywords,g_description,g_price,g_market_price,g_stock,g_weight,g_desc,g_remark,g_picture,g_create_time,g_update_time) 
			select ext.Id,ext.title,gyex.seo_keywords,gyex.seo_description,ext.reserve_price,
			gyex.market_price,item.num,gyex.market_weight,REPLACE(ext.desc, '/upload/goods/', '/Public/Uploads/yunkai_new/movearticle/'),gyex.item_brief,REPLACE(ext.pic_url, 'upload/images/', '/Public/Uploads/fx093433/goods/move/'),ext.created,ext.modified
			from yunkai_new.cbd_top_itemextra as ext inner join  yunkai_new.cbd_top_item as item on(ext.num_iid=item.num_iid)
			inner join yunkai_new.cbd_gy_itemextra as gyex on(ext.num_iid=gyex.num_iid) where ext.approve_status='onsale' 
			";
			$res2 = $obj->execute($sql2);//REPLACE('www.k686.com', 'www', 'http://www')
			if($res2){
				echo '<font style="color:blue;">商品详情表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品详情表数据迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);
		////云开没有暂时不处理
		////echo '-------------------------------------------------------------<br />';
		////echo '商品图片迁移中...<br />';
		echo '-------------------------------------------------------------<br />';
		echo '商品图片表迁移中...<br />';
		$obj = M('goods_pictures',C('DB_PREFIX'),'DB_CUSTOM');
		$sql1 = "truncate table fx_goods_pictures";
		$res1 = $obj->execute($sql1);
		echo '商品图片表已清空,等待迁移商品图片表数据...<br />';
		$sql2 = "
		INSERT INTO `fx_goods_pictures`(g_id,gp_picture,gp_order) 
		select fx_goods.g_id,REPLACE(item.url, 'upload/images/', '/Public/Uploads/yunkai_new/goods/move/'),item.position
		 from yunkai.cbd_top_itemimg as item left join fx_goods on(item.num_iid=fx_goods.erp_guid) where item.position !=0 and ifnull(g_id,'') != ''
		";
		$res2 = $obj->execute($sql2);
		if($res2){
			echo '<font style="color:blue;">商品图片表迁移成功，共迁移'.$res2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">图片表数据迁移失败！！！</font><br />';
		}

		
		echo '-------------------------------------------------------------<br />';
		echo '商品类型表迁移中...<br />';
		$obj = M('goods_type',C('DB_PREFIX'),'DB_CUSTOM');
		$sql1 = "truncate table fx_goods_type";
		$res1 = $obj->execute($sql1);
		echo '商品类型表已清空,等待迁移商品类型表数据...<br />';
		$sql2 = "
		INSERT INTO `fx_goods_type` VALUES ('1', '药品', '1', '2013-07-04 10:34:10', '2013-07-04 10:34:10')
		";
		$res2 = $obj->execute($sql2);
		if($res2){
			echo '<font style="color:blue;">商品类型表迁移成功，共迁移'.$res2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">商品类型表数据迁移失败！！！</font><br />';
		}
		echo '-------------------------------------------------------------<br />';
		echo '商品规格表迁移中...<br />';
		$obj = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM');
		$sql1 = "truncate table fx_goods_spec";
		$res1 = $obj->execute($sql1);
		echo '商品规格表已清空,等待迁移商品规格表数据...<br />';
		$sql1 = "truncate table fx_goods_spec_detail";
		$res1 = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->execute($sql1);
		echo '商品规格明细表已清空,等待迁移商品规格明细表数据...<br />';
		$sql2 = "
		INSERT INTO `fx_goods_spec` VALUES 
		('888', '颜色', '', '', '1', '2', '1', '1', '1', '1', '2013-05-29 12:12:12', '0000-00-00 00:00:00'),
		('896', '通用名', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:17:59', '0000-00-00 00:00:00'),
		('897', '生产厂家', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:18:30', '0000-00-00 00:00:00'),
		('898', '批准文号', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:18:51', '0000-00-00 00:00:00'),
		('899', '剂型', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:19:20', '0000-00-00 00:00:00'),
		('900', '注册商标', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:19:35', '0000-00-00 00:00:00'),
		('901', '产地', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:19:48', '0000-00-00 00:00:00'),
		('902', '给药途径', '', '', '1', '1', '0', '0', '0', '1', '2013-07-02 09:20:08', '0000-00-00 00:00:00'),
		('903', '药品须知', '', '', '1', '3', '0', '0', '0', '1', '2013-07-02 09:20:24', '0000-00-00 00:00:00'),
		('904', '规格', '', '', '1', '1', '0', '0', '0', '1', '2013-07-04 10:43:54', '2013-07-04 10:43:54'),
		('905', '限购数量', '', '', '1', '1', '0', '0', '0', '1', '2013-07-04 10:43:54', '2013-07-04 10:43:54')
		";
		$res2 = $obj->execute($sql2);
		$spec_sql = "
                insert into `fx_goods_spec_detail` (`gs_id`,`gsd_value`,`gsd_rgb_value`,`gsd_order`,`gsd_status`,`gsd_create_time`) values 
                (888,'军绿色','5d762a',1,1,'2013-05-29 12:12:12'),
                (888,'天蓝色','1eddff',1,1,'2013-05-29 12:12:12'),
                (888,'巧克力色','d2691e',1,1,'2013-05-29 12:12:12'),
                (888,'桔色','ffa500',1,1,'2013-05-29 12:12:12'),
                (888,'浅灰色','e4e4e4',1,1,'2013-05-29 12:12:12'),
                (888,'浅绿色','98fb98',1,1,'2013-05-29 12:12:12'),
                (888,'浅黄色','ffffb1',1,1,'2013-05-29 12:12:12'),
                (888,'深卡其布色','bdb76b',1,1,'2013-05-29 12:12:12'),
                (888,'深灰色','666666',1,1,'2013-05-29 12:12:12'),
                (888,'深紫色','4b0082',1,1,'2013-05-29 12:12:12'),
                (888,'深蓝色','041690',1,1,'2013-05-29 12:12:12'),
                (888,'白色','ffffff',1,1,'2013-05-29 12:12:12'),
                (888,'粉红色','ffb6c1',1,1,'2013-05-29 12:12:12'),
                (888,'紫罗兰','dda0dd',1,1,'2013-05-29 12:12:12'),
                (888,'紫色','800080',1,1,'2013-05-29 12:12:12'),
                (888,'红色','ff0000',1,1,'2013-05-29 12:12:12'),
                (888,'绿色','008000',1,1,'2013-05-29 12:12:12'),
                (888,'蓝色','0000ff',1,1,'2013-05-29 12:12:12'),
                (888,'褐色','855b00',1,1,'2013-05-29 12:12:12'),
                (888,'酒红色','990000',1,1,'2013-05-29 12:12:12'),
                (888,'黄色','ffff00',1,1,'2013-05-29 12:12:12'),
                (888,'黑色','000000',1,1,'2013-05-29 12:12:12');  
            ";
		$spec_res = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->execute($spec_sql);
		if($res2){
			echo '<font style="color:blue;">商品规格明细表迁移成功，共迁移'.$res2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">商品规格明细表数据迁移失败！！！</font><br />';
		}
		echo '-------------------------------------------------------------<br />';
		echo '商品类型属性关联表迁移中...<br />';
		$obj = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM');
		$sql1 = "truncate table fx_related_goods_spec";
		$res1 = $obj->execute($sql1);
		$sql1 = "truncate table fx_related_goods_type_spec";
		$res1 = $obj->execute($sql1);
		echo '商品类型属性关联表已清空,等待迁移商品类型属性关联表数据...<br />';
		$sql2 = "
		INSERT INTO `fx_related_goods_type_spec` VALUES 
		('1', '888'),('1', '896'),('1', '897'),('1', '898'),('1', '899'),('1', '900'),('1', '901'),('1', '902'),('1', '903'),('1', '904'),('1', '905')
		";
		$res2 = $obj->execute($sql2);
		if($res2){
			echo '<font style="color:blue;">商品类型属性关联表迁移成功，共迁移'.$res2.'条数据<br /></font>';
		}else{
			echo '<font style="color:red;">商品类型属性关联表数据迁移失败！！！</font><br />';
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);
		 
		echo '-------------------------------------------------------------<br />';
		echo '货品表迁移中...<br />';
		$obj = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_top_skuextra";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '货品表为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_goods_products";
			$res1 = $obj->execute($sql1);
			echo '货品表已清空,等待迁移货品表数据...<br />';
			$sql2 = "
			insert into fx_goods_products(g_id,g_sn,pdt_sn,pdt_sale_price,pdt_weight,pdt_total_stock,pdt_stock,pdt_create_time,pdt_update_time,pdt_memo) 
			select ext.Id,item.outer_id,sku.outer_id,sku.price,sku.weight,sku.quantity,sku.quantity,sku.created,sku.modified,sku.memo
			from yunkai_new.cbd_top_skuextra as sku left join  yunkai_new.cbd_top_item as item on(sku.num_iid=item.num_iid)
			left join yunkai_new.cbd_top_itemextra as ext on(ext.num_iid=sku.num_iid) where ext.approve_status='onsale' 
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">货品表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">货品表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);
		 
		echo '-------------------------------------------------------------<br />';
		echo '商品分类表迁移中...<br />';
		$obj = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_category_info";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '商品分类为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_goods_category";
			$res1 = $obj->execute($sql1);
			echo '商品分类表已清空,等待迁移商品分类表数据...<br />';
			$sql2 = "
			insert into fx_goods_category(gc_id,gc_name,gc_order,gc_parent_id,gc_create_time,gc_update_time,gc_keyword,gc_description,gc_is_display,gc_level,gc_is_parent) 
			select cate_id,title,position,parent_id,create_time,modify_time,seo_keywords,seo_description,is_show,if(parent_id=0,0,1),if(parent_id=0,1,0) 
			from yunkai_new.cbd_category_info
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">商品分类表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品分类表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '商品分类关联表迁移中...<br />';
		$obj = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select Id,seller_cids as cids from yunkai_new.cbd_top_itemextra where approve_status='onsale' ";
		$res3 = $obj->query($sql3);
		if(count($res3) == '0'){
			echo '商品分类关联为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_related_goods_category";
			$res1 = $obj->execute($sql1);
			echo '商品分类关联表已清空,等待迁移商品分类关联表数据...<br />';
			$_values = '';
			foreach($res3 as $tmp_item){
				$cids = $tmp_item['cids'];
				$cids = explode(',',$cids);
				foreach ($cids as $cid){
					if(!empty($cid)){
						$_values.= "({$tmp_item['Id']},{$cid}),";
					}
				}
			}
			$_values = substr($_values,0,-1);
			$sql2 = "insert into fx_related_goods_category values {$_values}";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">商品分类关联表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品分类关联表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '商品扩展属性迁移中...<br />';
		$obj = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select ext.Id,normal_title,factory,dose,code,trademark,place,way,limit_num,sku.memo from yunkai_new.cbd_top_itemextra as ext left join yunkai_new.cbd_top_skuextra as sku on(ext.num_iid=sku.num_iid) where approve_status='onsale' group by sku.num_iid ";
		$res3 = $obj->query($sql3);
		if(count($res3) == '0'){
			echo '商品扩展属性为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_related_goods_spec";
			$res1 = $obj->execute($sql1);
			echo '商品扩展属性已清空,等待迁移商品扩展属性关联表数据...<br />';
			$_values = '';
			foreach($res3 as $tmp_item){
				if($tmp_item['normal_title']){
					$_values.= "(896,{$tmp_item['Id']},'{$tmp_item['normal_title']}'),";
				}
				if($tmp_item['factory']){
					$_values.= "(897,{$tmp_item['Id']},'{$tmp_item['factory']}'),";
				}
				if($tmp_item['code']){
					$_values.= "(898,{$tmp_item['Id']},'{$tmp_item['code']}'),";
				}
				if($tmp_item['dose']){
					$_values.= "(899,{$tmp_item['Id']},'{$tmp_item['dose']}'),";
				}
				if($tmp_item['trademark']){
					$_values.= "(900,{$tmp_item['Id']},'{$tmp_item['trademark']}'),";
				}
				if($tmp_item['place']){
					$_values.= "(901,{$tmp_item['Id']},'{$tmp_item['place']}'),";
				}
				if($tmp_item['way']){
					$_values.= "(902,{$tmp_item['Id']},'{$tmp_item['way']}'),";
				}
				if($tmp_item['memo']){
					$_values.= "(904,{$tmp_item['Id']},'{$tmp_item['memo']}'),";
				}
				if($tmp_item['limit_num']){
					$_values.= "(905,{$tmp_item['Id']},'{$tmp_item['limit_num']}'),";
				}
			}
			$_values = substr($_values,0,-1);
			$sql2 = "insert into fx_related_goods_spec(gs_id,g_id,gsd_aliases) values {$_values}";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">商品扩展属性数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品扩展属性迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '商品品牌迁移中...<br />';
		$obj = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_gy_item_brand";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '商品品牌为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_goods_brand";
			$res1 = $obj->execute($sql1);
			echo '商品品牌表已清空,等待迁移商品品牌表数据...<br />';
			$sql2 = "
			insert into fx_goods_brand(gb_id,gb_name,gb_url,gb_logo,gb_detail,gb_order,gb_display,gb_create_time) 
			select brand_id,`name`,url,logo,`desc`,sort_order,is_show,created 
			from yunkai_new.cbd_gy_item_brand
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">商品品牌表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">商品品牌表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '文章信息迁移中...<br />';
		$obj = M('article',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_article";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '文章信息为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_article";
			$res1 = $obj->execute($sql1);
			echo '文章信息表已清空,等待迁移文章信息表数据...<br />';
			$path = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/fx093433/movearticle/';
			if (!file_exists($path)){
				mkdir ($path);
			}
			$sql2 = "
			insert into fx_article(a_id,a_title,cat_id,a_content,a_status,a_order,a_author,a_create_time) 
			select article_id,`title`,category_id,REPLACE(content, 'upload/goods/', 'Public/Uploads/fx093433/movearticle/'),`status`,is_top,author,create_time 
			from yunkai_new.cbd_article
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">文章信息表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">文章信息表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '文章类目迁移中...<br />';
		$obj = M('article_cat',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_article_category";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '文章类目为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_article_cat";
			$res1 = $obj->execute($sql1);
			echo '文章类目表已清空,等待迁移文章类目表数据...<br />';
			$sql2 = "
			insert into fx_article_cat(cat_id,cat_name) 
			select category_id,`name` 
			from yunkai_new.cbd_article_category
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">文章类目数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">文章类目迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '自定义导航迁移中...<br />';
		$obj = M('nav',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_gy_nav";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '自定义导航为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_nav";
			$res1 = $obj->execute($sql1);
			echo '自定义导航表已清空,等待迁移自定义导航数据...<br />';
			$sql2 = "
			insert into fx_nav(n_id,n_category,n_name,n_status,n_order,n_target,n_url,n_position) 
			select id,`cid`,`name`,ifshow,vieworder,if(opennew=1,'_blank',''),url,type 
			from yunkai_new.cbd_gy_nav
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">自定义导航数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">自定义导航迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		 
		echo '-------------------------------------------------------------<br />';
		echo '订单主表迁移中...<br />';
		$obj = M('orders',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_top_trade";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '订单主表为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_orders";
			$res1 = $obj->execute($sql1);
			echo '订单主表已清空,等待迁移订单主表数据...<br />';
			$sql2 = "
			insert into fx_orders(o_id,m_id,o_pay_status,o_goods_all_price,o_all_price,o_discount,
			o_pay,o_cost_payment,
			o_cost_freight,o_payment,o_receiver_name,o_receiver_mobile,o_receiver_telphone,
			o_receiver_state,o_receiver_city,
			o_receiver_county,o_receiver_address,o_receiver_zipcode,o_create_time,o_update_time,
			o_status,o_receiver_email,
			lt_id,o_buyer_comments,o_seller_comments,erp_sn,
			invoice_head,invoice_content) 
			select tid,`u_id`,`pay_status`,total_fee,payment,discount_fee+adjust_fee+commission_fee,
			received_payment,pay_fee,post_fee, CASE pay_type WHEN 'alipay' THEN '2' WHEN 'balance' THEN '1' WHEN 'chinabank' THEN '6' ELSE '' END,
			receiver_name,receiver_mobile,receiver_phone,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,
			created,modified,CASE order_status WHEN '0' THEN '1' WHEN '1' THEN '1' ELSE '2' END,buyer_email,1,buyer_memo,seller_memo,
			erp_tid,invoice_name,invoice_content 
			from yunkai_new.cbd_top_trade
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">订单主表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">订单主表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);

		echo '-------------------------------------------------------------<br />';
		echo '订单明细表迁移中...<br />';
		$obj = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM');
		$sql3 = "select count(*) as num from yunkai_new.cbd_top_trade_order";
		$res3 = $obj->query($sql3);
		if($res3[0]['num'] == '0'){
			echo '订单明细表为空，不需要迁移<br />';
		}else{
			$sql1 = "truncate table fx_orders_items";
			$res1 = $obj->execute($sql1);
			echo '订单明细表已清空,等待迁移订单明细表数据...<br />';
			//oi_ship_status 发货状态 0.待发货，1.仓库准备，2.已发货，3.缺货，4.退货
			$sql2 = "
			insert into fx_orders_items(oi_id,o_id,g_id,pdt_id,gt_id,g_sn,
			oi_g_name,pdt_sale_price,oi_price,oi_nums,pdt_sn,oi_ship_status,
			oi_single_allowance,oi_create_time,oi_update_time) 
			select order1.Id,order1.tid,sku.Id,ext.Id,1,order1.outer_iid,order1.title,sku.price,order1.price,
			order1.num,order1.outer_sku_id,if(trade.shipping_status=1,'2','0'),order1.discount_fee,order1.created,order1.created 
			from yunkai_new.cbd_top_trade_order  as order1  
			left join yunkai_new.cbd_top_trade as trade on(order1.tid=trade.tid) 
			left join yunkai_new.cbd_top_skuextra as sku on(order1.sku_id=sku.sku_id) 
			left join yunkai_new.cbd_top_itemextra as ext on(order1.num_iid=ext.num_iid) 
			";
			$res2 = $obj->execute($sql2);
			if($res2){
				echo '<font style="color:blue;">订单明细表数据迁移成功，共迁移'.$res2.'条数据<br /></font>';
			}else{
				echo '<font style="color:red;">订单明细表迁移失败！！！</font><br />';
			}
		}
		unset($sql1);unset($sql2);unset($sql3);unset($res1);unset($res2);unset($res3);
		echo '<strong style="font-color:blue;font-size:30px;">数据同步成功了，不一般的牛</srong>';
		exit;
	}
	
	/**
	 * 
	 * 数据迁移(老的b2cTo新fx)
	 * 
	 * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.3
     * @date 2013-8-26
	 */
	public function fxToQn(){
		@set_time_limit(0);  
		@ignore_user_abort(TRUE); 	
        if(empty($_SESSION['OSS']['GY_OSS_ON']) || empty($_SESSION['OSS']['GY_OTHER_ON']) || empty($_SESSION['OSS']['GY_QN_ON'])){
        	$oss_config = D("SysConfig")->getCfgByModule('GY_OSS',1);
			if(!empty($oss_config)){
				if($oss_config['GY_OSS_ON'] == '1' || $oss_config['GY_OTHER_ON'] == '1' || $oss_config['GY_QN_ON'] == '1'){
					$_SESSION['OSS'] = $oss_config;
				}				
			}
        }
		if(empty($_SESSION['OSS']['GY_QN_ON']) && C('UPLOAD_SITEIMG_QINIU.GY_QN_ON') == 1){
			$driverConfig = C('UPLOAD_SITEIMG_QINIU');
			$_SESSION['OSS']['GY_QN_ON'] = $driverConfig['GY_QN_ON'];
			$_SESSION['OSS']['GY_QN_ACCESS_KEY'] = $driverConfig['driverConfig']['accessKey'];
			$_SESSION['OSS']['GY_QN_BUCKET_NAME'] = $driverConfig['driverConfig']['bucket'];
			$_SESSION['OSS']['GY_QN_DOMAIN'] = $driverConfig['driverConfig']['domain'];
			$_SESSION['OSS']['GY_QN_SECRECT_KEY'] = $driverConfig['driverConfig']['secrectKey'];
		}
		if($_SESSION['OSS']['GY_QN_ON'] == 1 && empty($_SESSION['OSS']['GY_QN_ACCESS_KEY']) && empty($_SESSION['OSS']['GY_QN_SECRECT_KEY'])){
			$driverConfig = C('UPLOAD_SITEIMG_QINIU');
			if(!empty($driverConfig['driverConfig']['accessKey'])){
				$_SESSION['OSS']['GY_QN_ACCESS_KEY'] = $driverConfig['driverConfig']['accessKey'];
			}
			if(!empty($driverConfig['driverConfig']['bucket'])){
				$_SESSION['OSS']['GY_QN_BUCKET_NAME'] = $driverConfig['driverConfig']['bucket'];
			}		
			if(!empty($driverConfig['driverConfig']['domain'])){
				$_SESSION['OSS']['GY_QN_DOMAIN'] = $driverConfig['driverConfig']['domain'];
			}	
			if(!empty($driverConfig['driverConfig']['secrectKey'])){
				$_SESSION['OSS']['GY_QN_SECRECT_KEY'] = $driverConfig['driverConfig']['secrectKey'];
			}		
			if(empty($_SESSION['OSS']['GY_QN_ACCESS_KEY']) && empty($_SESSION['OSS']['GY_QN_SECRECT_KEY'])){
				$_SESSION['OSS']['GY_QN_ON'] = false;
			}
		}			
		$ary_pictures = array();
		$goods = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field('g_picture,g_desc')->select();
		foreach($goods as $good){
			D('ViewGoods')->ReplaceItemDescPicDomain($good['g_desc']);//描述显示
			D('QnPic')->picToQn($good['g_picture']);//图片显示
		}
		$goods_pictures = M('goods_pictures',C('DB_PREFIX'),'DB_CUSTOM')->field('gp_picture')->select();
		foreach($goods_pictures as $goods_picture){
			D('QnPic')->picToQn($goods_picture['g_picture']);//图片显示
		}
		
	}
	
}