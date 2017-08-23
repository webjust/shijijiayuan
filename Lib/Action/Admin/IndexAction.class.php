<?php
/**
 * 后台默认控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class IndexAction extends AdminAction{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 后台默认控制器默认页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-04
     */
    public function index(){
        echo $_SESSION['admin_name'];
        if($_SESSION['admin_name']=='edi-admin'){
            header("Location:".U('Admin/Index/word'));exit;
        }
    	$this->getSubNav(1,0,10);
		//统计在架商品，下架商品，新品，热品
		$array_goods_info = array();
		//系统中在架商品的商品数量
		$array_goods_info["onsale_num"] = D("GoodsBase")->where(array("g_on_sale"=>1,"g_status"=>1))->count();
		//系统中下架商品的商品数量
		$array_goods_info["unsale_num"] = D("GoodsBase")->where(array("g_on_sale"=>2,"g_status"=>1))->count();
		//系统中新品的数量
		$array_goods_info["news_num"] = D("GoodsBase")->where(array("g_on_sale"=>1,"g_new"=>1,"g_status"=>1))->count();
		//系统中的热卖商品的数量
		$array_goods_info["hot_num"] = D("GoodsBase")->where(array("g_on_sale"=>1,"g_hot"=>1,"g_status"=>1))->count();
        
		//订单量的统计：需要统计今日订单量、未付款订单、代发货订单
		$array_orders_info = array();
		$array_cond["o_create_time"] = array("between",array(date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")));
		$array_cond["o_status"] = array("neq",2);
		//今日有效订单
		$array_orders_info["today_nums"] = D("Orders")->where($array_cond)->count();
		//今日未付款订单
		$array_cond["o_pay_status"] = array("neq",1);
		$array_orders_info["un_pay_nums"] = D("Orders")->where($array_cond)->count();
		//今日已付款订单
		$array_cond["o_pay_status"] = array("eq",1);
		$array_orders_info["pay_nums"] = D("Orders")->where($array_cond)->count();
		
		//待订单，不含线下付款和货到付款的订单
		//未发货订单列表，需要从订单明细表中查询所有没发货的订单ID
		$ary_item_cond = array();
		$ary_item_cond['oi.oi_create_time'] = array("between",array(date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")));
        $ary_item_cond['oi.oi_ship_status'] = array("neq",2);
		$ary_item_cond["o_pay_status"] = array("eq",1);
		$array_orders_info["un_delivery_nums"] = D("Orders")->join('fx_orders_items as oi on oi.o_id=fx_orders.o_id')->where($ary_item_cond)->count();
		//远程服务公告获取
		$array_config_info = C("TMPL_PARSE_STRING");
		$string_tmp_url = trim(trim($array_config_info["__FXCENTER__"]),"/") . '/Api/Index/index/';
		$array_params = array();
		$array_params["method"] = "saas.announcementList.get";
		$array_params["app_key"] = C("SAAS_KEY");
		$array_params["app_secret"] = C("SAAS_SECRET");
		$array_params["client_sn"] = CI_SN;
		$string_json = makeRequest($string_tmp_url,$array_params,"POST");
		$array_result = json_decode($string_json,true);
		$array_notices = array();
		if(true === $array_result["status"]){
			$array_notices = $array_result["data"];
		}
		$now_time = date('Y-m-d H:i:s');
		foreach($array_notices as $key=>$array_notice){
			if($array_notice['ai_start_showtime'] !='0000-00-00 00:00:00' && $array_notice['ai_end_showtime'] == '0000-00-00 00:00:00'){
				if($now_time<$array_notice['ai_start_showtime']){
					unset($array_notices[$key]);
				}
			}
			if($array_notice['ai_end_showtime'] !='0000-00-00 00:00:00' && $array_notice['ai_start_showtime'] == '0000-00-00 00:00:00'){
				if($now_time>$array_notice['ai_end_showtime']){
					unset($array_notices[$key]);
				}
			}
			if($array_notice['ai_end_showtime'] !='0000-00-00 00:00:00' && $array_notice['ai_start_showtime'] != '0000-00-00 00:00:00'){
				if($now_time<$array_notice['ai_start_showtime'] || $now_time>$array_notice['ai_end_showtime']){
					unset($array_notices[$key]);
				}
			}
		}
		$this->assign("array_notices",$array_notices);
		$this->assign("goods_info",$array_goods_info);
		$this->assign("order_info",$array_orders_info);
        $session_mid = cookie('session_mid');
		$this->display();
    }

    /**
     * 后台列表例子
     * @author zuo <zuojianghua@guanyisot.com>
     * @date 2013-01-07
     */
    public function testList(){
        $this->display();
    }

    /**
     * 后台表单例子
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-07
     */
    public function testForm(){
        $this->display();
    }
    
    
    /**
     * 后台地图
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-04
     */
    public function getMap(){
        $top = $this->tops;
        $menus = $this->menus;
        $data = array();
        if(!empty($menus) && is_array($menus)){
            $i = 0;
            foreach($menus as $key=>$val){
                $data[$i] = $val;
                $i++;
            }
        }
        if(!empty($data) && is_array($data)){
            foreach($data as $dky=>$dvl){
                foreach($dvl as $kv=>$ve){
                    $data[$dky][$kv]['name'] = $ve[0]['name'];
                    $arr_ve = $ve;
                    unset($data[$dky][$kv]);
                    $data[$dky][$kv]['name'] = $arr_ve[0]['name'];
                    unset($arr_ve[0]);
                    $data[$dky][$kv]['sub'] = $arr_ve;
                    
                }
            }
        }
        if(!empty($top) && is_array($top)){
            foreach($top as $tkey=>$tval){
                $top[$tkey]['sub'] = $data[$tkey];
            }
        }
//        echo "<pre>";print_r($top);exit;
        $this->assign("data",$top);
        $this->display();
    }
    
    /**
     * 常用菜单管理
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-19
     */
    public function Custom(){
        $this->getSubNav(1,0,20);
        $this->display();
    }
    
    /**
     * 常用菜单管理
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-19
     */
    public function Menu(){
        $ary_request = $this->_request();
        $adminmenus = M('AdminMenu',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_menus = $adminmenus->where(array('id'=>$ary_request['id'],'u_id'=>$_SESSION[C('ADMIN_AUTH_KEY')]))->find();
        $this->redirect($ary_menus['url']);
        $this->getSubNav(1,0,$ary_request['s']);
    }
    
    /**
     * 左侧菜单显示
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-19
     */
    public function getLeftMenu(){
        layout(false);
        $ary_post = $this->_post();
        if(!empty($ary_post['nav_id']) && intval($ary_post['nav_id']) > 0){
            $menus[$ary_post['nav_id']] = $this->menus[$ary_post['nav_id']];
            $this->assign('menus', $menus);
            $this->assign("nav1",$ary_post['nav_id']);
            $this->display("Common:incMenus");
        }
    }
    
    /**
     * 执行update数据库更新
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-01-17
     */
     public function doUpdateSql(){
     	$db_obj = new UpdateSql();
     	$file = './Lib/Action/System/update.sql';
		$res = $db_obj->createFromFile($file,null,'');	
	   if($res){
    		$root_url = substr($_SERVER['SCRIPT_FILENAME'], 0, -9).'Lib/Action/System/';
			rename($root_url.'update.sql',$root_url.'update'.date('YmdHis').'.sql'); 
			rename($root_url.'update.html',$root_url.'update'.date('YmdHis').'.html'); 
			//提示和跳转
			$this->success("系统更新成功。",U('Admin/Index/index'));
    	}else{
    		$this->error("系统更新失败。",U('Admin/Index/index'));
    	}
     }

     public function word(){
        $this->getSubNav(1,0,20);
        $condition = "";
        $count = D('Words')->where($condition)->count();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;

        $wordList = D('Words')->where($condition)->limit($limit['start'],$limit['end'])->select();
        
        $this->assign('wordList',$wordList);
        $this->assign("page", $page);        
        $this->display();
     }
 

     public function userlogs(){
        $this->getSubNav(1,0,30);
        $condition = "";
        $count = D('User_log')->where($condition)->count();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;

        $User_log = D('User_log')->where($condition)->limit($limit['start'],$limit['end'])->order(array('log_create'=>'asc'))->select();
        
        $this->assign('User_log',$User_log);
        $this->assign("page", $page);        
        $this->display();
     }   
}