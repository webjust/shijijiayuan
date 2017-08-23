<?php
    @session_start();//设置session.auto_start=on后图片不能上传的问题
    if (!ini_get('session.auto_start')) {
    	ini_set('session.auto_start', 'on');
    }
    /**
     * Created by JetBrains PhpStorm.
     * User: taoqili
     * Date: 12-7-18
     * Time: 上午10:42
     */
    header("Content-Type: text/html; charset=utf-8");
    error_reporting( E_ERROR | E_WARNING );
    include "Uploader.class.php";
	if($_SESSION['OSS']['GY_OSS_ON'] == '1'){
    	require_once '../../../Lib/oss/sdk.class.php';
    }
    //上传配置
    $config = array(
        "savePath" => "../../../Uploads/" . $_SESSION['CI_SN'] . '/' ,
        "maxSize" => 3000 , //单位KB
        "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp"  )
    );
    //自定义上传目录 add by zuo @2013-03-26 ++++++++++++++++++++++++++++++++++
    if($_POST['path'] != ''){
    	//模板图片路径
    	if($_POST['path'] == 'templateimages'){
    		$config["savePath"] = "../../../Tpl/" . $_SESSION['CI_SN'] . '/'.$_SESSION['NOW_TPL'].'/images/';
    	}else{
    		$config["savePath"] = $config["savePath"] . $_POST['path'] . '/';
    	}
    }else{
        $config["savePath"] = $config["savePath"] . 'other/';
    }
    //end of add +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //上传图片框中的描述表单名称，
    $title = htmlspecialchars( $_POST[ 'pictitle' ] , ENT_QUOTES );
    //生成上传实例对象并完成上传
    $up = new Uploader( "upfile" , $config );

    /**
     * 得到上传文件所对应的各个参数,数组结构
     * array(
     *     "originalName" => "",   //原始文件名
     *     "name" => "",           //新文件名
     *     "url" => "",            //返回的地址
     *     "size" => "",           //文件大小
     *     "type" => "" ,          //文件类型
     *     "state" => ""           //上传状态，上传成功时必须返回"SUCCESS"
     * )
     */
    $info = $up->getFileInfo();
	//判断是否是负载均衡服务器
	if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
		
		if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
			$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_OTHER_IP'];
		}
		if($info["state"] == 'SUCCESS'){
			$ary_request_data = $_POST;
			require_once '../../../../Lib/Common/Communications.class.php';
			$com_obj = new Communications();
			$file_paths = explode('Uploads/',$info["url"]); 
     		$file_path = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'. $file_paths[1];
     		$ary_request_data['upfile'] = '@'.$file_path;
     		$ary_request_data['file_path'] = '/Public/Uploads/'.$file_paths[1];
     		$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_OSS_PIC_URL'].'/Home/Images/doImage', $ary_request_data, array(), false);
			if(!$res){
                $info['state'] = '上传图片服务器失败';
            }else{
				$info["url"]  = $_SESSION['OSS']['GY_STATE_URL1'].str_replace('../../../','Public/',$info["url"]);
			}
		}
	}
	//add by zhangjiasuo 七牛图片上传机制改造 update by wangguibin date@2015-06-05
	if($_SESSION['OSS']['GY_QN_ON'] == '1'){
		require_once '../../../../Lib/Common/Qiniu.class.php';		
		$file_paths = explode('Uploads/',$info["url"]); 
		$object = $file_paths[1];
		$file_path = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$object;
		$file['tmp_name'] = $file_path;
		$key = '/Public/Uploads/'.$object;
		$setting['key'] = $key;
		$Upload = new Qiniu($setting);
		$tmp_info = $Upload->upload($file);
     	//数据库操作
        //$config_info = require_once '../../../../Conf/database_config.php';
		//$array_center_config = explode('/',ltrim($config_info["DB_CUSTOM"],'mysql://'));
		$config_info = $_SESSION['DB_CUSTOM'];
 		$array_center_config = explode('/',ltrim($config_info,'mysql://'));
		$array_hostinfo = explode("@",$array_center_config[0]);
		$array_host_info = explode(":",$array_hostinfo[1]);
		$array_userinfo = explode(":",$array_hostinfo[0]);
		$array_userinfo[1] = (!isset($array_userinfo[1]))?"":$array_userinfo[1];
		$string_conn = "mysql:host=" . $array_host_info[0] . ";dbname=" . $_SESSION['CI_SN'];
		if(3306 != $array_host_info[1]){
			$string_conn .= ";port=" . $array_host_info[1];
		}
		$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);
     	if($tmp_info['state'] != 'SUCCESS'){
     		unlink($file_path);
     	}else{
			$time = date("Y-m-d H:i:s",time()+86000);
     		$tmp_sql = 'replace into fx_qn_pic(pic_url, pic_qn_url,sign_end_time) values('.'"'.$info["url"].'",'.'"'.$tmp_info['prviate_url'].'",'.'"'.$time.'"'.')';
     		$obj_stmt = $pdo_conn->prepare($tmp_sql);
			$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
			if(!$obj_stmt->execute()){
				echo "{'url':'" . $info['url'] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'error'}";exit;						
			}else{

				echo "{'url':'" .  $tmp_info['prviate_url'] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "'}";exit;				
			}
     	}		
	}
	
    /**
     * 向浏览器返回数据json数据
     * {
     *   'url'      :'a.jpg',   //保存后的文件路径
     *   'title'    :'hello',   //文件描述，对图片来说在前端会添加到title属性上
     *   'original' :'b.jpg',   //原始文件名
     *   'state'    :'SUCCESS'  //上传状态，成功时返回SUCCESS,其他任何值将原样返回至图片上传框中
     * }
     */
    //是否上传的阿里云OSS
     if($_SESSION['OSS']['GY_OSS_ON'] == '1'){
		$tmp_url = explode('Uploads/',$info["url"]); 
		$info["url"] = '/Public/Uploads/'. $tmp_url[1];			 
     	if($info["state"] == 'SUCCESS'){
     		$file_paths = explode('Uploads/',$info["url"]); 
     		$bucket = $_SESSION['OSS']['GY_OSS_BUCKET_NAME'];
     		$object = $file_paths[1];
     		$file_path = $_SERVER['DOCUMENT_ROOT'].'/Public/Uploads/'.$object;
			$oss_sdk_service = new ALIOSS();
			//设置是否打开curl调试模式
			$oss_sdk_service->set_debug_mode(FALSE);
			//设置开启三级域名，三级域名需要注意，域名不支持一些特殊符号，所以在创建bucket的时候若想使用三级域名，最好不要使用特殊字符
			$oss_sdk_service->set_enable_domain_style(TRUE);
			$response = $oss_sdk_service->is_object_exist($bucket,$object);
			//上传图片到服务器
			if($response->status != '200'){
				$response1 = $oss_sdk_service->upload_file_by_file($bucket,$object,$file_path);
				if($response1->status == 200){
					$response_url = $oss_sdk_service->get_sign_url($bucket,$object,$timeout=3600*24*365*10);
				}
			}else{
				$response_url = $oss_sdk_service->get_sign_url($bucket,$object,$timeout=3600*24*365*10);
			}		
			if(!empty($_SESSION['OSS']['GY_OSS_CNAME_URL'])){
				$response_url = explode('aliyuncs.com/',$response_url);
				$response_url = str_replace($response_url[0].'aliyuncs.com/',$_SESSION['OSS']['GY_OSS_CNAME_URL'],implode('aliyuncs.com/',$response_url));
			}
     	}
     	//数据库操作
        $config_info = require_once '../../../../Conf/database_config.php';
 		$array_center_config = explode('/',ltrim($config_info["DB_CUSTOM"],'mysql://'));
		$array_hostinfo = explode("@",$array_center_config[0]);
		$array_host_info = explode(":",$array_hostinfo[1]);
		$array_userinfo = explode(":",$array_hostinfo[0]);
		$array_userinfo[1] = (!isset($array_userinfo[1]))?"":$array_userinfo[1];
		$string_conn = "mysql:host=" . $array_host_info[0] . ";dbname=" . $_SESSION['CI_SN'];
		if(3306 != $array_host_info[1]){
			$string_conn .= ";port=" . $array_host_info[1];
		}
		$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);
     	if(empty($response_url)){
     		unlink($file_path);
     	}else{
     		$tmp_sql = 'replace into fx_oss_pic(pic_url, pic_oss_url) values('.'"'.$info["url"].'",'.'"'.$response_url.'"'.')';
     		$obj_stmt = $pdo_conn->prepare($tmp_sql);
			$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
			if(!$obj_stmt->execute()){
				echo "{'url':'" . $response_url . "','sign_url':'" . $info["url"] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'error'}";
			}else{
				echo "{'url':'" . $response_url . "','sign_url':'" . $info["url"] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "'}";
			}
     	}
     }else{
     	echo "{'url':'" . $info["url"] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "'}";
     }
