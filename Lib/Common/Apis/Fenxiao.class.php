<?php

/**
 * 淘宝供销平台API接口
 *
 * 此类通过淘宝HTTPS免签名方式访问淘宝API，所以只需要token即可
 * 分销平台接口方法名称定义遵循规则：淘宝接口名称以点号分隔以后，
 * 第二个单词起首字母转换成大写，连接起来即为方法名
 *
 * @package Common
 * @subpackage Api
 * @stage 7.0
 * @author mithern <sunguangxu@guanyisoft.com>
 * @date 2013-04-27
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class Fenxiao{

	//淘宝用户授权token
	private $access_token;

	//错误提示，status=false表示初始化错误，code是错误代码，message是错误消息
	//实例化本类完毕以后需要对此属性进行判断，比如授权过期
	public $errorInfo = array('status'=>true,'code'=>'1000','message'=>'normal','data'=>array());

	/**
	 * 构造函数，用于初始化用户授权token
	 *
	 * 免参数传入
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function __construct(){
		$array_oauth_info = D("TopSupplierInfo")->find();
		//对读取的授权结果进行判断，如果不是数组，表示获取授权失败
		if(NULL === $array_oauth_info || !is_array($array_oauth_info)){
			$this->errorInfo = array('status'=>false,'code'=>'1001','message'=>'获取授权信息失败','data'=>array());
		}elseif(empty($array_oauth_info)){
			//如果获取的是空数组，表示用户没有授权
			$this->errorInfo = array('status'=>false,'code'=>'1002','message'=>'用户未授权','data'=>array());
		}elseif(time() > strtotime($array_oauth_info["top_expires_in"])){
			//对用户授权信息进行判断
			$this->errorInfo = array('status'=>false,'code'=>'1003','message'=>'用户授权已过期','data'=>array());
		}
		$this->access_token = isset($array_oauth_info["top_access_token"])?$array_oauth_info["top_access_token"]:"";
		//测试环境下授权token写死  临时做法
		$this->errorInfo = array('status'=>true,'code'=>'9999','message'=>'fuck','data'=>array());
        //$this->access_token = "6101f2820104b607d8b88947d225af063eac134f811df9f2076226627";
		$this->access_token = "6202608e199dd6eegi6dc52877d227fb1872a9784fc8603516318157";
	}

	/**
	 * request Api 淘宝供销平台接口调用统一使用方法
	 *
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	private function requestApi($array_data = array()){
		//系统级别输入参数定义，除method
		$array_system_params = array();
		$array_system_params["format"] = "json";
		$array_system_params["access_token"] = $this->access_token;
		$array_system_params["v"] = "2.0";
		//与应用级别输入参数合并
		//传入参数的数组中如果对系统级别的输入参数进行定义，则会覆盖掉系统级别参数定义
		$array_params = array_merge($array_system_params,$array_data);
		$string_request_https_url = "https://eco.taobao.com/router/rest";
        //$string_request_https_url = "https://gw.api.tbsandbox.com/router/rest";
		$array_result = makeRequest($string_request_https_url,$array_params,"POST");
		//如果要求服务器返回json
		if("json" == strtolower($array_params["format"])){
			$array_return = json_decode($array_result,true);
			if(NULL === $array_return){
				return array('status'=>false,'code'=>'1004','message'=>'淘宝API返回的JSON数据无法解析','data'=>array());
			}
			return array('status'=>true,'code'=>'1005','message'=>'SUCCESS','data'=>$array_return);
		}

		//如果要求服务器返回xml
		if("xml" == strtolower($array_params["format"])){
			$array_return = xml2array($array_result,true);
			return array('status'=>true,'code'=>'1005','message'=>'SUCCESS','data'=>$array_return);
		}
	}

	/**
	 * 获取淘宝供销平台商品列表
	 * 查询产品列表，参考文档参见以下URL
	 * @url http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:328
	 * 方法名：taobao.fenxiao.products.get
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function taobaoFenxiaoProductsGet($array_params = array()){
		//对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}
		$array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.products.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_sys_params["fields"] = "skus";
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
	}

	/**
	 * 供应商或分销商获取合作关系信息
	 *
	 * @url http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:10694
	 * 方法名：taobao.fenxiao.cooperation.get
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function taobaoFenxiaoCooperationGet($array_params = array()){
		//对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}
		$array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.cooperation.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
	}

	/**
	 * 获取分销商信息
	 *
	 * @url http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:10379
	 * 方法名：taobao.fenxiao.distributors.get
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function taobaoFenxiaoDistributorsGet($array_params = array()){
		//对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}
		$array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.distributors.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
	}

	/**
	 * 查询商品下载记录
	 *
	 * @url http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:10693
	 * 方法名： taobao.fenxiao.distributor.items.get
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function taobaoFenxiaoDistributorItemsGet($array_params = array()){
		//对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}
		$array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.distributor.items.get";
 		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
	}

	/**
	 * 分销（代销）商等级查询
	 *
	 * @url http://api.taobao.com/apidoc/api.htm?path=cid:15-apiId:10569
	 * 方法名： taobao.fenxiao.grades.get
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-04-27
	 * @version 1.0
	 */
	public function taobaoFenxiaoGradesGet($array_params = array()){
		//对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}
		$array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.grades.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
	}

    /**
     * 下载采购单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-03
     * @link URL http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.1br9dE&path=cid:15-apiId:180
     */
    public function taobaoFenxiaoOrdersGet($array_params = array()){
        //对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}

        $array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.orders.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		//print_r($array_request_params);exit;
		return $this->requestApi($array_request_params);
    }

    /**
     * 下载代销交易单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     * @link URL http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.hXC44C&path=cid:15-apiId:21391
     */
    public function taobaoFenxiaoTrademonitorGet($array_params = array()){
        //对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}

        $array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.trademonitor.get";
		$array_sys_params["page_no"] = 1;
		$array_sys_params["page_size"] = 20;
		$array_request_params = array_merge($array_sys_params,$array_params);
		//print_r($array_request_params);exit;
		return $this->requestApi($array_request_params);
    }

    /**
     * 更新分销平台商品
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-07
     * @param array $array_params 待更新的数据
     * @link URL http://api.taobao.com/apidoc/api.htm?spm=0.0.0.0.12cGpv&path=cid:15-apiId:326
     */
    public function taobaoFenxiaoProductUpdate($array_params = array()){
        //对对象实例化情况进行判断
		$error_info = $this->errorInfo;
		if(false === $error_info["status"]){
			return $this->errorInfo;
		}

        $array_sys_params = array();
		$array_sys_params["method"] = "taobao.fenxiao.product.update";
		$array_request_params = array_merge($array_sys_params,$array_params);
		return $this->requestApi($array_request_params);
    }



}