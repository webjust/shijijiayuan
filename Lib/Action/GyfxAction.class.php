<?php

/**
 * 管易分销软件Action基类
 *
 * @package Action
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-27
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class GyfxAction extends Action {

    /**
     * 客户编号，全局唯一。客户编号 = 数据库名 = 缓存目录 = 客户模版目录 = 客户上传文件目录
     * @var string
     */
    protected $ci_sn;

    /**
     * 管易分销前后台控制器基类初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-27
     */
    public function __construct() {
		C('TMPL_CACHE_ON',false);
	    //动态生成数据连接配置项
		$this->getCustomDB();
		$array_center_config = explode('/', ltrim(C("DB_CUSTOM"), 'mysql://'));
        $array_hostinfo = explode("@", $array_center_config[0]);
        $array_host_info = explode(":", $array_hostinfo[1]);
        $array_userinfo = explode(":", $array_hostinfo[0]);
        C("DB_HOST", $array_host_info['0']);
        C("DB_NAME", CI_SN);
        C("DB_USER", $array_userinfo['0']);
		if(isset($array_userinfo['1'])){
			C("DB_PWD", $array_userinfo['1']);
		}
        C("DB_PORT", $array_host_info['1']);
        Load('extend');
       // $this->__filter();
	   //判断是否开启阿里云OSS服务器
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
			$_SESSION['OSS']['GY_QN_PRIVATE'] = isset($driverConfig['driverConfig']['is_private'])?$driverConfig['driverConfig']['is_private']:false;
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
			$_SESSION['OSS']['GY_QN_PRIVATE'] = isset($driverConfig['driverConfig']['is_private'])?$driverConfig['driverConfig']['is_private']:false;		
			if(empty($_SESSION['OSS']['GY_QN_ACCESS_KEY']) && empty($_SESSION['OSS']['GY_QN_SECRECT_KEY'])){
				$_SESSION['OSS']['GY_QN_ON'] = false;
			}
		}	
         //判断是否是负载均衡
        if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			$oss_pic_url = D("SysConfig")->getConfigs('GY_OSS', 'GY_OSS_PIC_URL',null,null,'Y');
			if(!empty($oss_pic_url['GY_OSS_PIC_URL']['sc_value'])){
				$_SESSION['OSS']['GY_OSS_PIC_URL'] = $oss_pic_url['GY_OSS_PIC_URL']['sc_value'];
			}
			if(!empty($oss_pic_url['GY_OSS_PIC_URL']['sc_value'])){
				$_SESSION['OSS']['GY_OTHER_IP'] = $oss_pic_url['GY_OTHER_IP']['sc_value'];
			}
			$ary_static_urls = array();
			if(!empty($_SESSION['OSS']['GY_STATE_URL1'])){
				$ary_static_urls[] = $_SESSION['OSS']['GY_STATE_URL1'];
			}
			if(!empty($_SESSION['OSS']['GY_STATE_URL2'])){
				$ary_static_urls[] = $_SESSION['OSS']['GY_STATE_URL2'];
			}
			if(!empty($_SESSION['OSS']['GY_STATE_URL3'])){
				$ary_static_urls[] = $_SESSION['OSS']['GY_STATE_URL3'];
			}			
			
			if(!empty($ary_static_urls)){
				C('DOMAIN_HOST',$ary_static_urls[array_rand($ary_static_urls)]);
			}			
        }
         //静态资源域名
        if(!empty($_SESSION['OSS']['GY_OSS_CNAME_URL']) ){
			$oss_cname_url = D("SysConfig")->getConfigs('GY_OSS', 'GY_OSS_CNAME_URL',null,null,'Y');
			if(!empty($oss_cname_url['GY_OSS_CNAME_URL']['sc_value'])){
				$_SESSION['OSS']['GY_OSS_CNAME_URL'] = $oss_cname_url['GY_OSS_CNAME_URL']['sc_value'];
			}
        }
		//店铺来源
		$shopcode = $this->_request('shopcode');
		if($shopcode !='' ){
			$_SESSION['SHOPCODE'] = $shopcode;
		}
		parent::__construct();
    }
    
    /**
     * 防止XSS攻击，过滤参数
     * @author Joe <qianyijun@guanyisof.com>
     * @date 2013-10-30
     */
    private function __filter(){
        switch($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $input  =  $_POST;
                break;
            case 'PUT':
                parse_str(file_get_contents('php://input'), $input);
                break;
            default:
                $input  =  $_GET;
        }
        if(C('SITE_ATTACK_FILTER_ON')){
            foreach ($input as $ik=>$it){
                $data = $it;
                $filters = C('SITE_ATTACK_FILTER');
                if($filters){
                    $filters    =   explode(',',$filters);
                    foreach($filters as $filter){
                        if(function_exists($filter)) {
                            $data = $this->ick($data,$filter);
                        }
                    }
                }
                $input[$ik] = $data;
            }
        }
        
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = $input;
        }else if($_SERVER['REQUEST_METHOD'] == 'GET'){
            $_GET = $input;
        }
        return true;

    }
    
    /**
     * 递归验证请求参数
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-14
     */
    private function ick($data,$filter){
    
        if(!is_array($data)){
            return $filter($data);
        }else{
            foreach ($data as $dk=>$dv){
                if(is_array($dv)){
                    $data[$dk] = $this->ick($dv,$filter);
                }else{
                    $data[$dk] = $filter($dv);
                }
            }
        }
        return $data;
    }

    #### 以下为私有方法 ######################################################

    /**
     * 根据来访域名获取客户真正的数据库信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-27
     */
    private function getCustomDB() {
		$domain = $_SERVER['SERVER_NAME'];
    	if(SAAS_ON == TRUE){
    		 //连接中控数据库
	        $CenterDomain = M('client_domain_name', C('GY_PREFIX'), 'DB_CENTER');
	        //根据来访域名找到客户数据库
	        //$domain = $_SERVER['SERVER_NAME'];
	        //$info = $CenterDomain->where(array('cbi_domain_name' => $domain))->find();
            //$info = D('Gyfx')->selectOneGyCache('client_domain_name',null, array('cbi_domain_name' => $domain), $ary_order=null);
			//if (false == $info) {
	        //    $this->error('来访域名不存在...');
	        //    exit;
	        //}
	        //$CenterInfo = M('client_info',C('GY_PREFIX'), 'DB_CENTER');
			$customInfo = D('Gyfx')->selectOneGyCache('client_info',null, array('ci_sn'=>$info['ci_sn']), $ary_order=null);
			//$customInfo = $CenterInfo->where(array('ci_sn'=>$info['ci_sn']))->find();
			if (false == $customInfo) {
	            $this->error('来访客户不存在...');
	            exit;
	        }elseif ($customInfo['ci_system_endtime'] <= time()) {
	            $this->error('软件授权时间已过，或尚未授权...');
	            exit;
	        }
			if(!empty($customInfo['rds_id'])){
				$RdsInfo = M('rds_info',C('GY_PREFIX'), 'DB_CENTER');
				//$rds_info = $RdsInfo->where(array('rds_id'=>$customInfo['rds_id']))->find();
				$rds_info = D('Gyfx')->selectOneGyCache('rds_info',null, array('rds_id'=>$customInfo['rds_id']), $ary_order=null);
				if(empty($rds_info)){
					$this->error('数据库信息不存在...');
					exit;				
				}
				C("DB_HOST", $rds_info['rds_host_name']);
				C("DB_NAME", CI_SN);
				C("DB_USER", $rds_info['rds_username']);
				C("DB_PWD", $rds_info['rds_password']);
				C("DB_PORT", $rds_info['rds_port']);
				C("DB_CUSTOM", 'mysql://'.$rds_info['rds_username'].':'.$rds_info['rds_password'].'@'.$rds_info['rds_host_name'].':'.$rds_info['rds_port'].'/');	
			}else{
	            //$this->error('数据库链接不存在...');
	            //exit;			
			}
            if($customInfo['ci_type']){
                C('CUSTOMER_TYPE', $customInfo['ci_type']);
            }
            //$admin_logo = M('system_config',C('GY_PREFIX'), 'DB_CENTER')->where(array('sc_module'=>'BACKSTAGE','sc_key'=>'LOGO'))->getField('sc_value');
	        $admin_logo = D('Gyfx')->selectOneGyCache('system_config','sc_value', array('sc_module'=>'BACKSTAGE','sc_key'=>'LOGO'));
			if(!empty($admin_logo['sc_value']) && isset($admin_logo['sc_value'])){
                C('TMPL_LOGO',$admin_logo['sc_value']);
            }
			
            $this->ci_sn = $info['ci_sn'];
	        $str_db_info = C("DB_CUSTOM") . $info['ci_sn'];
    	}else{
    		$this->ci_sn = CI_SN;
    		$str_db_info = C("DB_CUSTOM") . CI_SN;
            //本地测试用到这个，不要提交SVN
            //C('DB_PWD', APP_SECRET);	 
    	}
        C('DB_CUSTOM', $str_db_info);
        if(isset($domain)){
            C('DOMAIN_NAME', $domain);
        }
		if(SAAS_ON != TRUE){
			//获取店铺类型
			$CUSTOMER_TYPE = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_TYPE'));	
			if(!empty($CUSTOMER_TYPE['sc_value'])){
				C('CUSTOMER_TYPE', intval($CUSTOMER_TYPE['sc_value']));	
			}	
		}
		$_SESSION['CI_SN'] = $this->ci_sn;
		$_SESSION['DB_CUSTOM'] = $str_db_info;
		$_SESSION['DOMAIN_NAME'] = $domain;
    }

}
