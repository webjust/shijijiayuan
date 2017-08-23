<?php

/**
 * 推广销售类
 * 后台推广销售相关
 *
 * @stage Salespromotion
 * @package Action
 * @subpackage Ucenter
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-12-02
 * @license branches
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */

class PromotingsAction extends AdminAction {

	private $obj_server;
	private $sale_obj_server;
	public function _initialize(){
		//实例化要查询的类
		$this->obj_server = D('Promotings');
		$this->sale_obj_server = D('Salespromotion');
		parent::_initialize();
        $this->setTitle('- 推广销售管理');		
	}
	public function index(){
		$data = D('Menus')->find();
		$this->setTitle(' - '.'商品返利设定');
		$this->getSubNav(6,7,50);
		$ary_params = $this->_request();
        $start = !empty($ary_params['p'])?$ary_params['p']:1;
		$page_size = !empty($ary_params['page_size'])?$ary_params['page_size']:50;
        $filter = array();
		//被推荐分销商,精确搜索
        if(isset($ary_params['m_name']) && !empty($ary_params['m_name'])){
            $filter['m_name'] = $ary_params['m_name'];
        }
		//货号,精确搜索
        if(isset($ary_params['pdt_sn']) && !empty($ary_params['pdt_sn'])){
            $filter['pdt_sn'] = $ary_params['pdt_sn'];
        }
		//商品名称,模糊查询
        if(isset($ary_params['g_name']) && !empty($ary_params['g_name'])){
            $filter['g_name'] = $ary_params['g_name'];
        }
        if(isset($ary_params['page_size']) && !empty($ary_params['page_size'])){
            $filter['page_size'] = $ary_params['page_size'];
        }
		
        $member	= $this->sale_obj_server->getMembersData($filter['m_name'],$filed='m_name,m_id');
		if(intval($ary_params['mr_p_id'])){
        	$filter['m_id']	= intval($ary_params['mr_p_id']);
        }elseif($member['m_id']){
        	$filter['m_id']	= $member['m_id'];
        }
        $payback_list = array();
        $payback_info = $this->obj_server->getList($filter ,$start,$page_size);//获取数据
        $payback_list = $payback_info['list'];
        $pageinfo = $payback_info['pageinfo'];
		//echo "<pre>";print_r($payback_info);exit;
        $pageList['filter'] = $filter;
     	if(isset($ary_params['type']) && !empty($ary_params['type'])){
            $pageList['type'] = $ary_params['type'];
        }
    	$this->assign('pageInfo',$pageinfo);
    	$this->assign('payback_list',$payback_list);
    	$this->assign('filter',$filter);
	    //echo "<pre>";print_r($ary_post['filter']);exit;
    	$this->display("paybacklist");  
    }
    
    public function PBStatements()
    {
    	$ary_post = $this->_request();
    	$int_rebates_type = 0;
    	switch ($int_rebates_type) {
    		case 0:
    			return $this->rebatesReportBySetting($ary_post);
    		break;
    		case 1:
    			return $this->rebatesReportByDifferPrice($ary_post);
    		break;
    			
    	}
    }
    
    /**
     * 
     * 根据返利设定生成返利报表
     * @author Wangguibin
     * @param array $ary_post
     * @date 2014-01-03
     */
    public function rebatesReportBySetting($ary_post) {
    	$start = !empty($ary_post['p'])?$ary_post['p']:1;
        $filter = array();
        if(isset($ary_post['m_name']) && !empty($ary_post['m_name']))
        {
            $filter['m_name'] = $ary_post['m_name'];
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
        $pbs_list = $this->obj_server->getPBSList($filter ,$start,20);//获取数据
		//echo "<pre>";print_r($filter);exit;
        //print_r($pbs_list);	exit;
        $pageList['filter'] = $filter;
        $pageList['start'] = $start;
        $pageList['limit'] = 20;
        $pageList['count'] = count($pbs_list);
    	if(isset($ary_post['type']) && !empty($ary_post['type']))
        {
            $pageList['type'] = $ary_post['type'];
        }
        // 获取所有的分销商
        $ary_data	= array();
		$ary_data	= D('Salespromotion')->getMemberRelationAll();
		foreach ($ary_data as $ary_data_val) {
			$tmp_res[$ary_data_val['m_id']] =$ary_data_val;
			foreach ($ary_data as $key=>$value){
				if($value['mr_p_id']==$ary_data_val['m_id']){
					$tmp_res[$value['m_id']] = $value;
				}
			}
		}
		$this->assign('ary_member',$tmp_res);
		$obj_page = new Page($pageList['count'], 20);
		$page = $obj_page->show();
		$this->setTitle(' - '.'返利报表:根据返利设定生成返利报表');
		$this->getSubNav(6,7,60);
		$this->assign('page',$page);
    	$this->assign('pageInfo',$pageList);
    	$this->assign('pbs_list',$pbs_list);
    	$this->assign('filter',$filter);
	    //echo "<pre>";print_r($ary_post);exit;
    	$this->display("payback_statements_list");
    }
    
	/**
     * 
     * 根据商品差价设定生成返利报表
     * @author Jerry
     * @param array $ary_post
     * @date 2012-10-13
     */
    public function rebatesReportByDifferPrice($ary_post) {
    	$ary_rebates_report_data_res['data']	= array();
    	$ary_rebates_report_data_res['rebates']	= array(
    		'theory_rebates_total_amount'	=> 0.000,
    		'actual_rebates_total_amount'	=> 0.000,
    		'unusual_num'	=>	0
    	);
    	try{
	    	if(empty($ary_post)) {
	    		throw new Exception('参数有误！', 87001);
	    	}
	    	if(empty($ary_post['mdprr_pm_name'])) {
	    		throw new Exception('参数有误：推荐人名称不能为空！', 87002);
	    	}
	    	$ary_pmid	= $this->obj_server->getMidByMname(trim($ary_post['mdprr_pm_name']));
	    	if(empty($ary_pmid['m_id'])) {
	    		throw new Exception('没有找到指定的推荐人！', 87003);
	    	}
	    	if(trim($ary_post['m_name']) != '') {
		    	$ary_mid	= $this->obj_server->getMidByMname(trim($ary_post['m_name']));
	    		if(empty($ary_mid['m_id'])) {
		    		throw new Exception('没有找到指定的下级分销商！', 87004);
		    	}
	    	}
	    	$ary_filter	= array(
	    		'mdprr_pm_id'	=> $ary_pmid['m_id'],
	    		'm_id'			=> $ary_mid['m_id'] ? $ary_mid['m_id'] : '',
	    		'mdprr_start_time'	=> !empty($ary_post['mdprr_start_time']) ? $ary_post['mdprr_start_time']." 00:00:01" : '',
	    		'mdprr_end_time'	=> !empty($ary_post['mdprr_end_time']) ? $ary_post['mdprr_end_time']." 23:59:59" : '',
	    		'mdprr_is_unusual'	=> (int)$ary_post['mdprr_is_unusual'] ? (int)$ary_post['mdprr_is_unusual'] : '',
	    		'g_sn'		=> !empty($ary_post['g_sn']) ? trim($ary_post['g_sn']) : '',
	    		'pdt_sn'	=> !empty($ary_post['pdt_sn']) ? trim($ary_post['pdt_sn']) : '',
	    		'o_id'		=> !empty($ary_post['o_id']) ? trim($ary_post['o_id']) : '',
	    		'is_limit'	=> $ary_post['is_limit'] ? $ary_post['is_limit'] : 0,
	    		'page'		=> $ary_post['page'] ? $ary_post['is_limit'] : 1,
	    		'pagesize'	=> $ary_post['pagesize'] ? $ary_post['pagesize'] : 20
	    	);
	    	if((strtotime($ary_filter['mdprr_end_time']) - strtotime($ary_filter['mdprr_start_time'])) >= 93*24*3600) {
	    		throw new Exception('报表数据已超过三个月！', 87005);
	    	}
	    	$ary_rebates_report_res	= $this->obj_server->getDifferPriceRebatesReport($ary_filter);
	    	if(!$ary_rebates_report_res['success']) {
	    		throw new Exception($ary_rebates_report_res['err_msg'], $ary_rebates_report_res['err_code']);
	    	}
	    	$ary_rebates_report_data_res	= $this->obj_server->createDifferPriceRebatesReport($ary_rebates_report_res['data']);
	    	unset($ary_rebates_report_res);
    	} catch (Exception $e) {
    		$ary_rebates_report_data_res['err_msg']	= $e->getMessage();
    		$ary_rebates_report_data_res['err_code']	= $e->getCode();  
    	}
    	//echo "<pre>";print_r($ary_rebates_report_res);die();
    	$ary_rebates_report_data_res['num']	= count($ary_rebates_report_data_res['rebates_report']);
    	if($ary_post['is_ajax_report']) {
    		return $ary_rebates_report_data_res;
    	} else {
	    	//return result('DIFFERP_RICE_REBATES_REPORT', $ary_rebates_report_data_res,$ary_post);
    	}
    }
    public function exportDifferPriceRebatesReport() {
    	set_time_limit(0);
    	$ary_post	= $_POST;
    	$ary_post['is_ajax_report']	= 1;
    	//$ary_post['is_limit']	= 1;
    	$ary_post['page']		= $ary_post['page'] ? (int)$ary_post['page'] : 1;
    	$ary_post['pagesize']	= 100;
    	//处理返利数据生成返利报表
    	$ary_rebates_report_data_res	= $this->rebatesReportByDifferPrice($ary_post);
    	$ary_rebates_report_data_res['filter']	= $ary_post;
    	//echo "<Pre>";print_r($ary_rebates_report_data_res);die;
    	$str_excel_name	= N('ReaderExcel')->createDifferRebatesExcelReport($ary_rebates_report_data_res);
    	echo  json_encode(array('excel'=>$str_excel_name));
    	exit;
    }
    /**
     * @商品返利设定
     * 
     * @return json
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-03-07
     */
    public function ajaxSetGoodsPayback(){
    	$ary_params = $this->_request();
    	$set_payback	= false;
    	$set_payback = $this->obj_server->setGoodsPayback($ary_params);//获取数据
    	if(false === $set_payback){
    		echo json_encode(array('status' => 'error'));
    		exit;
    	}else{
    		echo json_encode(array('status' => 'success','m_p_id' => $set_payback['m_p_id'],'m_p_amount' => $set_payback['m_p_amount']));
    		exit;
    	}
    	
    }
    


    /**
	 * 批量设定商品返利 第一步 选择要配置返利的商品  优化
	 *
	 * @author Mithern
	 * @date 2013-03-19
	 * @version 1.0
	 * @branches 6.5
	 */
	public function bathConfigGoodsRebates(){
		//分页处理
		$int_page = 1;
		$int_page_size = 20;
		if(isset($_GET["page"]) && is_numeric($_GET["page"])){
			$int_page = $_GET["page"];
		}
		$array_goods_condition = array();
		$array_goods_condition['g_status']=1;
		//根据商品名称搜索
		$array_gid_cond = array('type'=>'IN','key'=>'g_id','value'=>array());
		$array_gid_cond_val = array();
		if(isset($_GET["g_name"]) && "" != trim($_GET["g_name"])){
			$str_sql = "select g_id from goods where g_name like '%" . trim($_GET["g_name"]) . "%';";
			$array_result = DB()->fetchAll($str_sql);
			$array_gid_cond_val = array(0);
			if(is_array($array_result) && !empty($array_result)){
				$array_gid_cond_val = array();
				foreach($array_result as $val){
					$array_gid_cond_val[] = $val["g_id"];
				}
			}
		}
		
		//根据商家编码搜索
		if(isset($_GET["g_sn"]) && "" != trim($_GET["g_sn"])){
			$array_goods_condition['g_sn']=trim($_GET["g_sn"]);
		}
		
		//根据 pdt_sn 搜索
		$array_pdtsn_cond_val_gid = array();
		if(isset($_GET["pdt_sn"]) && "" != trim($_GET["pdt_sn"])){
			$str_sql = "select g_id from products where pdt_sn like '%" . trim($_GET["pdt_sn"]) . "%' and pdt_status=1;";
			$array_pdt_result = DB()->fetchAll($str_sql);
			$array_pdtsn_cond_val_gid = array(0);
			if(is_array($array_pdt_result) && !empty($array_pdt_result)){
				$array_pdtsn_cond_val_gid = array();
				foreach($array_pdt_result as $pdt){
					$array_pdtsn_cond_val_gid[] = $pdt["g_id"];
				}
			}
		}
		
		//合并搜索条件
		$array_gid_cond["value"] = array_merge($array_gid_cond_val,$array_pdtsn_cond_val_gid);
		if(count($array_gid_cond["value"]) > 0){
			$array_goods_condition["sql_condition_extend"][] = $array_gid_cond;
		}
		$int_count_rows = L("model.table.table")->searchCount("goods",$array_cond,'g_id');
		$array_goods = L("model.table.table")->fetchAll('goods',$array_goods_condition,$int_page_size,$int_page,array(),array('g_id','g_name','g_sn'));
		$array_data_list = array();
		foreach($array_goods as $key=>$val){
			$int_goods_id = $val["g_id"];
			//获取所有的 product
			$array_product_cond = array("g_id"=>$int_goods_id,"pdt_status"=>1);
			$array_product_field = array('pdt_id','g_id','pdt_sn','pdt_sale_price','pdt_market_price','pdt_stock');
			$array_products = L("model.table.table")->fetchAll("products",$array_product_cond,1000,1,array(),$array_product_field);
			//print_r($array_product_cond);
			if(!is_array($array_products) || empty($array_products)){
				continue;
			}
			$val["rowspan"] = count($array_products)+1;
			$val["products"] = $array_products;
			$array_data_list[]=$val;
		}
		//echo "<pre>";print_r($array_data_list);exit;
		return result("BATH_CONFIG_SELECT_GOODS",$array_data_list,$int_page_size,$int_page,$int_count_rows);
	}
	
	/**
	 * 保存用户设置的sku 的pdt 对应的 返利金额
	 *
	 *
	 * 待优化
	 */
	public function saveBathConfigGoodsRebates(){
		//接收提交的返利金额设置数据
		$string_post_config_info = rtrim(trim($_POST["config_info"]),";");
		//解析提交的参数
		$array_config_info = explode(";",$string_post_config_info);
		//获取选择的会员
		//print_r($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"]);exit;
		$array_member_info = $_SESSION["ADMIN_BATCH_CHECKED_MEMBER"];
		//生成所有的返利记录数组
		$array_config_rebeate_array = array();
		foreach($array_config_info as $pdt_price){
			$_tmp = explode(":",$pdt_price);
			$int_pdt_id = $_tmp[0];
			$num_price = $_tmp[1];
			foreach($array_member_info as $m_id){
				$array_config_rebeate_array[] = array("m_id"=>$m_id,"pdt_id"=>$int_pdt_id,"m_p_amount"=>$num_price);
			}
		}
		//配置信息写入到数据库中
		$array_result = array("status"=>true,"message"=>"记录保存成功！","error_code"=>"SUCCESS");
		foreach($array_config_rebeate_array as $config_info){
			//根据pdtid获取gid
			$ary_gid = L("model.table.table")->fetchOne("products",array("pdt_id"=>$config_info["pdt_id"]));
			if(!is_array($ary_gid) || empty($ary_gid)){
				continue;
			}
			$int_g_id = $ary_gid["g_id"];
			$int_m_id = $config_info["m_id"];
			//获取上级推荐人ID
			$array_m_parent = L("model.table.table")->fetchOne("member_relation",array("m_id"=>$int_m_id),array("mr_p_id"));
			if(!isset($array_m_parent["mr_p_id"])){
				//如果没有上级推荐人，则跳过
				continue;
			}
			$int_m_o_id = $array_m_parent["mr_p_id"];
			$int_pdt_id = $config_info["pdt_id"];
			$m_p_amount = $config_info["m_p_amount"];
			$str_sql = "replace into `member_payback` set `m_id`={$int_m_id},`g_id`={$int_g_id},";
			$str_sql .= "`pdt_id`={$int_pdt_id},`m_o_id`={$int_m_o_id},`m_p_amount`={$m_p_amount};";
			if(!DB()->query($str_sql)){
				$array_result = array("status"=>false,"message"=>"记录保存失败！","error_code"=>"PDT_ID={$int_pdt_id}","sql"=>$str_sql);
				break;
			}
		}
		
		//删除已经选中的会员
		unset($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"]);
		
		//输出提示消息数据数组
		echo json_encode($array_result);
		exit;
	}
	
	/**
	 * 第一步  选择会员
	 *
	 *
	 * 待优化
	 */
	public function bathConfigGoodsRebatesCheckMember(){
		$this->getSubNav(6,7,30);
		//分页处理
		$int_page = 1;
		$int_page_size = 20;
		if(isset($_GET["page"]) && is_numeric($_GET["page"])){
			$int_page = $_GET["page"];
		}
		//搜索条件处理
		$array_cond = array("m_verify"=>2);
		$array_cond_mid = array();
		//根据会员名称搜索
		if(isset($_GET["m_name"]) && "" != trim($_GET["m_name"])){
			$array_cond['m_name'] = array('like','"%' . trim($_GET["m_name"]) . '%"');
		}
		//根据会员邮箱帐号搜索
		if(isset($_GET["m_email"]) && "" != trim($_GET["m_email"])){
			$array_cond['m_email'] = array('like','"%' . trim($_GET["m_email"]) . '%"');
		}
		//根据会员手机号码搜索
		if(isset($_GET["m_mobile"]) && "" != trim($_GET["m_mobile"])){
			$array_cond['m_mobile'] = array('like','"%' . trim($_GET["m_mobile"]) . '%"');
		}
		if("" != $str_tmp_cond){
			$str_tmp_sql .= $str_tmp_cond;
			$array_member_id_tmp = DB()->fetchAll($str_tmp_sql);
			$array_cond_mid = array(-1);
			if(is_array($array_member_id_tmp) && !empty($array_member_id_tmp)){
				$array_cond_mid = array();
				foreach($array_member_id_tmp as $val){
					$array_cond_mid[] = $val["m_id"];
				}
			}
		}
		//根据会员等级名称搜索
		if(isset($_GET["ml_name"]) && "" != trim($_GET["ml_name"])){
			$str_sql = "select `ml_id` from `member_level` where `ml_name` like '%" . trim($_GET["ml_name"]) . "%' and `ml_status`=1;";
			$array_ml_ids = DB()->fetchAll($str_sql);
			$array_mlid_cond = array('type'=>'IN','key'=>'ml_id','value'=>array(-1));
			if(is_array($array_ml_ids) && !empty($array_ml_ids)){
				$array_mlid_cond = array('type'=>'IN','key'=>'ml_id','value'=>array());
				foreach($array_ml_ids as $val){
					$array_mlid_cond['value'][] = $val["ml_id"];
				}
				$array_cond["sql_condition_extend"][] = $array_mlid_cond;
			}
		}
		
		//根据会员组名称搜索
		$array_group_mid = array();
		if(isset($_GET["mg_name"]) && "" != trim($_GET["mg_name"])){
			$str_sql = "select `ml_id` from `member_group` where `mg_name` like '%" . trim($_GET["mg_name"]) . "%' and `mg_status`=1;";
			$array_mg_ids = DB()->fetchAll($str_sql);
			$array_group_mid = array(-1);
			if(is_array($array_mg_ids) && !empty($array_mg_ids)){
				$array_mg_id = array();
				foreach($array_mg_ids as $val){
					$array_mg_id[] = $val["mg_id"];
				}
				//根据查询出的会员组ID查询关联表，获取所有的会员ID
				$str_sql = "select distinct `m_id` from `related_member_group_member` where `mg_id` in (" . implode(',',$array_mg_id) . ") and `rmgm_status`=1;";
				$array_mids = DB()->fetchAll($str_sql);
				if(is_array($array_mids) && !empty($array_mids)){
					$array_group_mid = array();
					foreach($array_mids as $val){
						$array_group_mid[] = $val["m_id"];
					}
				}
			}
		}
		
		//组装最终的会员ID搜索条件
		$array_cond_mid = array_merge($array_cond_mid,$array_group_mid);
		if(is_array($array_cond_mid) && !empty($array_cond_mid)){
			$array_cond["sql_condition_extend"][] = array("type"=>"IN","key"=>"m_id","value"=>$array_cond_mid);
		}
		//查询总数
		$int_count_rows = D('Gyfx')->getCount('members',$array_cond);
		$array_members = L("model.table.table")->fetchAll("members",$array_cond,$int_page_size,$int_page,array(),array('m_id','m_name','m_email','m_mobile'));
		//unset($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"]);
		//对数据进行处理  用于视图层判断是否自动选中
		$array_saved_memberid = array();
		if(isset($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"]) && !empty($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"])){
			$array_saved_memberid = $_SESSION["ADMIN_BATCH_CHECKED_MEMBER"];
		}
		foreach($array_members as $key=>$val){
			$checkd_tmp_var = '';
			if(in_array($val['m_id'],$array_saved_memberid)){
				$checkd_tmp_var = 'checked';
			}
			$array_members[$key]["checked"] = $checkd_tmp_var;
		}
		return result("BATH_CONFIG_SELECT_MEMBER",$array_members,$int_page_size,$int_page,$int_count_rows);
	}
	
	/**
	 * 保存用户选择的批量设置的会员ID
	 *
	 * 待优化
	 */
	public function saveCheckedMember(){
		$string_member_ids = rtrim(trim($_POST["string_checked_mid"]),",");
		//将数据存入SESSION
		$array_tmp_member_id = explode(',',$string_member_ids);
		if(!empty($array_tmp_member_id) && count($array_tmp_member_id) > 0){
			$array_saved_memberid = array();
			if(isset($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"]) && !empty($_SESSION["ADMIN_BATCH_CHECKED_MEMBER"])){
				//如果存在已经保存的会员ID
				$array_saved_memberid = $_SESSION["ADMIN_BATCH_CHECKED_MEMBER"];
			}
			$_SESSION["ADMIN_BATCH_CHECKED_MEMBER"] = array_merge($array_tmp_member_id,$array_saved_memberid);
			echo json_encode(array("status"=>true,"message"=>"会员数据保存成功","error_code"=>"SUCCESS"));
			exit;
		}
		echo json_encode(array("status"=>false,"message"=>"数据提交异常","error_code"=>"SUCCESS"));
		exit;
	}
	
}
    
    
