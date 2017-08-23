<?php
if($_SERVER['SERVER_NAME']=='csftbbgy764.caizhuangguoji.com'&&strpos($_SERVER['REQUEST_URI'],'Admin')==FALSE){
	print_r($_SERVER);
	header("location:http://www.caizhuangguoji.com");
	exit();
}
error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE ^ E_WARNING);
ini_set("session.cookie_httponly", 1);
//session_start();
define('GYFX', TRUE);
//配置是否连接SAAS化中心数据库
define('SAAS_ON', FALSE);
//取得客户编号
if(SAAS_ON == TRUE){
	require 'custom.php';
}else{
	$config_info = require_once("./Conf/database_config.php");
	//取得客户编号，此处是管易授予用户的唯一识别码，也是数据库名称，请勿修改
	//修改可能会导致模板文件不能加载或者会员中心错乱或者数据库连接出错
	define('CI_SN', $config_info['CI_SN']);
	//同时将CI_SN写入到session，供第三方程序使用，例如ueditor
	$_SESSION['CI_SN'] = $config_info['CI_SN'];
	//定义密钥
	define('APP_SECRET', $config_info['APP_SECRET']);
}
define('APP_DEBUG', TRUE);
define('FXINC',str_replace('\\','/',substr(dirname(__FILE__),0)));
//定义客户runtime目录
define('RUNTIME_PATH', './Runtime/' . CI_SN . '/');
//区域限购开关
define('GLOBAL_STOCK',FALSE);
//创建客户上传目录
@mkdir('./Public/Uploads/' . CI_SN);
//创建客户模版目录
@mkdir('./Public/Tpl/' . CI_SN);
require '../ThinkPHP/ThinkPHP.php';
//echo $_SERVER['PHP_SELF'];
//echo $_SERVER["QUERY_STRING"];
