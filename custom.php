<?php
header("Content-type: text/html; charset=utf-8");
if(!defined('GYFX')){
    header('HTTP/1.1 404 Not Found');
	header("status: 404 Not Found");
	echo '<h1>404 Not Found</h1>';
	exit;
}
/**
 * 中心化获取客户编号作为常量
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-03-28
 */
#### 获取SAAS客户编号ci_sn #################################################
//加载系统配置文件，并解析配置文件中的数据库连接信息
$config_info = require_once("./Conf/database_config.php");

$array_center_config = explode('/',ltrim($config_info["DB_CENTER"],'mysql://'));
$array_hostinfo = explode("@",$array_center_config[0]);
$array_host_info = explode(":",$array_hostinfo[1]);
$array_userinfo = explode(":",$array_hostinfo[0]);
$array_userinfo[1] = (!isset($array_userinfo[1]))?"":$array_userinfo[1];
$string_conn = "mysql:host=" . $array_host_info[0] . ";dbname=" . $array_center_config[1];
if(3306 != $array_host_info[1]){
	$string_conn .= ";port=" . $array_host_info[1];
}

$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);


try {
	$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);
	$pdo_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$pdo_conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);//Display exception
} catch (PDOExceptsddttrtion $e) {//return PDOException
	//连接管易数据中心失败
	die("验证管易分销授权中心数据库失败。");
}
if (!$pdo_conn) {
	//连接管易数据中心失败
    die("验证管易分销授权中心数据库失败。");
}
$domain = $_SERVER['SERVER_NAME'];
$mem_config_info = require_once("./Conf/config.php");
if(ini_get('memcache.allow_failover')){	
	 if($mem_config_info['MEMCACHE_STAT'] == '1'){
		 $Memcache_host = $mem_config_info['MEMCCACHE_HOST'];
		 $Memcache_port = $mem_config_info['MEMCCACHE_PORT'];
	 }
	if(!empty($Memcache_host) && !empty($Memcache_port)){
		$info = memcache_connect($Memcache_host,$Memcache_port);
		$result_data = @memcache_get($info,$domain);
		if(!empty($result_data)){
			$data = json_decode($result_data,true);
		}else{
			$obj_stmt = $pdo_conn->prepare("select * from `gy_client_domain_name` where `cbi_domain_name`=? limit 1");
			$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
			if(!$obj_stmt->execute(array($domain))){
				die("无法获取此域名的用户授权信息。");
			}
			$data =  $obj_stmt->fetch();	
			$json_data = json_encode($data);
			@memcache_set($info, $domain, $json_data, MEMCACHE_COMPRESSED, 3600);
			memcache_close();				
		}	
	}else{
		$obj_stmt = $pdo_conn->prepare("select * from `gy_client_domain_name` where `cbi_domain_name`=? limit 1");
		$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
		if(!$obj_stmt->execute(array($domain))){
			die("无法获取此域名的用户授权信息。");
		}
		$data =  $obj_stmt->fetch();			
	}
}else{
		$obj_stmt = $pdo_conn->prepare("select * from `gy_client_domain_name` where `cbi_domain_name`=? limit 1");
		$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
		if(!$obj_stmt->execute(array($domain))){
			die("无法获取此域名的用户授权信息。");
		}
		$data =  $obj_stmt->fetch();	

}
//print_r($data);die();
#### 定义客户编号常量 ######################################################
if(!empty($data['ci_sn'])){
    $data['regional_restriction'] = '0';            //区域限购开关
    if(!empty($data['regional_restriction']) && $data['regional_restriction'] == '1'){
        define('GLOBAL_STOCK', TRUE);
    }else{
        define('GLOBAL_STOCK', FALSE);
    }
    define('CI_SN', $data['ci_sn']);
	//定义数据库常量
	if(ini_get('memcache.allow_failover')){	
		 if($mem_config_info['MEMCACHE_STAT'] == '1'){
			 $Memcache_host = $mem_config_info['MEMCCACHE_HOST'];
			 $Memcache_port = $mem_config_info['MEMCCACHE_PORT'];
		 }
		if(!empty($Memcache_host) && !empty($Memcache_port)){
			$info = memcache_connect($Memcache_host,$Memcache_port);
			$result_my_data = @memcache_get($info,$domain.'mysql_host');
			if(!empty($result_my_data)){
				$my_data = json_decode($result_my_data,true);
			}else{
				$obj_stmt = $pdo_conn->prepare("select rds.*,ci.ci_sn from `gy_rds_info` as rds left join `gy_client_info` as ci on(rds.rds_id=ci.rds_id) where ci_sn=? limit 1");
				$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
				if(!$obj_stmt->execute(array($data[ci_sn]))){
					die("无法获取此域名的用户授权信息。");
				}
				$my_data =  $obj_stmt->fetch();	
				$json_my_data = json_encode($my_data);
				@memcache_set($info, $domain.'mysql_host', $json_my_data, MEMCACHE_COMPRESSED, 3600);
				memcache_close();				
			}	
		}
	}else{
		$obj_stmt = $pdo_conn->prepare("select rds.*,ci.ci_sn from `gy_rds_info` as rds left join `gy_client_info` as ci on(rds.rds_id=ci.rds_id) where ci_sn=? limit 1");
		$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
		if(!$obj_stmt->execute($data[ci_sn])){
			die("无法获取此域名的用户授权信息。");
		}
		$my_data =  $obj_stmt->fetch();	
		$json_my_data = json_encode($my_data);
		@memcache_set($info, $domain.'mysql_host', $json_my_data, MEMCACHE_COMPRESSED, 3600);
		memcache_close();	
	}	
	if(!empty($my_data)){
		define('DB_HOST', $my_data['rds_host_name']);
		define('DB_USER', $my_data['rds_username']);
		if(isset($my_data['rds_password']) && !empty($my_data['rds_password'])){
			define('DB_PWD', $my_data['rds_password']);
		}else{define('DB_PWD', '');}
		if(isset($my_data['rds_port']) && $my_data['rds_port'] !='3306'){
			define('DB_PORT', $my_data['rds_port']);
		}else{define('DB_PORT', '');}
	}
    //同时将CI_SN写入到session，供第三方程序使用，例如ueditor
    $_SESSION['CI_SN'] = $data['ci_sn'];
}else{
    die('域名绑定错误或域名不存在!');
}
//关闭PDO数据库连接
$pdo_conn = null;
unset($obj_stmt);