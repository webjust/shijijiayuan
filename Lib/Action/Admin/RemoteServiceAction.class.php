<?php
/**
 * 远程服务对接
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Mithern <sunguangxu@guanyisoft.com>
 * @date 2013-07-05
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RemoteServiceAction extends AdminAction{
    
    public function _initialize() {
        parent::_initialize();
    }
	
	public function readNotice(){
		//验证是否制定通知ID
		if(!isset($_GET["notice_id"]) || !is_numeric($_GET["notice_id"])){
			echo "参数不合法：notice_id;";
			exit;
		}
		
		//调用远程接口获取公告
		$array_config_info = C("TMPL_PARSE_STRING");
		$string_tmp_url = trim(trim($array_config_info["__FXCENTER__"]),"/") . '/Api/Index/index/';
		$array_params = array();
		$array_params["method"] = "saas.announcementDetail.get";
		$array_params["app_key"] = C("SAAS_KEY");
		$array_params["app_secret"] = C("SAAS_SECRET");
		$array_params["client_sn"] = CI_SN;
		$array_params["ai_id"] = $_GET["notice_id"];
		$string_json = makeRequest($string_tmp_url,$array_params,"POST");
		$array_result = json_decode($string_json,true);
		$this->assign("notice",$array_result["data"]);
		$this->display();
	}
    /**
     * 后台公告弹窗提示不再提示
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-10-15
     */
	public function showdisplay(){
		$_SESSION['show_display'] = '1';
		//$this->success();
		//$this->ajaxReturn($res);
	}
	
}