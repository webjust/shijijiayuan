<?php

/**
 * 后台基类
 *
 * @subpackage Supplier
 * @package Action
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class SupplierAction extends GyfxAction {
    protected $_name = '';      //获取当前模块的控制器
    
    /**
     * 顶部大栏目
     * @var array
     */
    private $tops = array();

    /**
     * 左侧各级菜单
     * @var array
     */
    private $menus = array();

    /**
     * 后台登陆后的管理员session信息
     * @var array
     */
    protected $supplier = array();

    /**
     * 基类初始化操作
     * @author zuo <zuojianghua@guanyisot.com>
     * @date 2012-12-31
     */
    public function _initialize() {        
        $this->doCheckLogin();     
        $this->_name = $this->getActionName();
        import('ORG.Util.Session');
        $this->assign('admin_logo',C('TMPL_LOGO'));
        //是否有权限访问，默认允许访问
        $INT_USER_ACCESS = 1;
        $supplier_access = D('SysConfig')->getCfgByModule('SUPPLIER_ACCESS');
      
        if (intval($admin_access['EXPIRED_TIME']) > 0 && Session::isExpired()) {
            unset($_SESSION['Supplier']);
            unset($_SESSION);
            session_destroy();
        }
        if (intval($supplier_access['EXPIRED_TIME']) > 0) {
            Session::setExpire(time() + $supplier_access['EXPIRED_TIME'] * 60);
        }
        $array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_DEFAULT'))->find();
        if (is_array($array_config) && !empty($array_config)) {
            define('TPL', $array_config['sc_value']);
        }
            
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            $rbac = new Arbac();
            if (!$rbac->AccessDecision()) {
                //检查认证识别号
                if (!$_SESSION ['Supplier']) {
                    //跳转到认证网关
                    redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    redirect(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->assign('jumpUrl', PHP_FILE . C('USER_AUTH_GATEWAY'));
                    }
                    if($this->isAjax()){//ajax 请求没有权限
                        //过滤掉升级公告
                        if(ACTION_NAME == 'showdisplay' || ACTION_NAME == 'readNotice'){                        
                        }else{
                            layout(false);
                            $this->error("您没有权限，请联系管理员！");
                            exit;   
                        }
                    }else{
                        $INT_USER_ACCESS = 0;
                        //权限问题
                        $this->assign('is_user_access',$INT_USER_ACCESS);      
                        $this->getTitle();
                        $this->getTop();
                        $this->getMenus();
                        $menu_url = '';
                        $action_name = '';
                        if(ACTION_NAME == 'index'){
                            $menu_url = MODULE_NAME.':pageList';
                            $action_name = 'pageList';
                        }else{
                            $menu_url = MODULE_NAME.':'.ACTION_NAME;
                            $action_name = ACTION_NAME;
                        }
                        $supplier_url = '/Supplier/'.MODULE_NAME.'/'.$action_name;
                        switch($supplier_url){
                            case '/Supplier/System/pageList':
                            $supplier_url = '/Supplier/System/index';
                            break;  
                            case '/Supplier/Role/pageList':
                            $supplier_url = '/Supplier/Role/index';
                            break;                              
                            default:
                            break;
                        }
                        $menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$supplier_url))->find();
                        //dump($menu_info);die();
                        if(empty($menu_info)){
                            $supplier_url = '/Supplier/'.MODULE_NAME.'/'.ACTION_NAME;
                            $menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$supplier_url))->find();
                            $sn = explode('_',substr($menu_info['sn'],3));
                            $this->getSubNav($sn[1],0,0);
                        }else{
                            $sn = explode('_',substr($menu_info['sn'],4));
                            $this->getSubNav($sn[0],$sn[1],$sn[2]);
                        }
                        $this->display('Index:index');
                        exit;
                        // 提示错误信息
                        //$this->error(L('_VALID_ACCESS_'));
                    }
                }
            }
        }    
        //权限问题
        $this->assign('is_user_access',$INT_USER_ACCESS);      
        $this->getTitle();
        $this->getTop();
        $this->getMenus();
        import('ORG.Util.Page');
        //是否升级脚本,脚本是否存在
        if(C('AUTO_SQL_UPDATE') == '1'){
            $this->updateSql(); 
        }   
        //未处理订单
        /**
        $orders = M('orders',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_where = array();       //订单搜索条件
        $ary_where['fx_orders.erp_sn'] = '';
        $ary_where['fx_orders.o_pay_status'] = '1';
        $ary_where['_string'] = "fx_orders.o_status not in(2,4)"; 
        $count1 = $orders->where($ary_where)->count();
        //dump($orders->getLastSql());die();
        $this->assign('wtrade_num',$count1); 
        **/
        $start_time=date("Y-m-d H:i:s",mktime(0,0,0,date("m")-1,date("d"),date("Y")));
        $ary_where = array ( 'o_status' => '1', 'fx_orders_items.oi_ship_status' => array ( 0 => 'neq', 1 => 2, ), '_string' => '(o_pay_status = 1 ) or (o_pay_status=0 and o_payment=6)','fx_orders_items.oi_refund_status'=>array('not in','4,5'),'fx_orders.o_create_time'=>array('EGT',$start_time));
        $count1 = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')
        ->join('fx_orders_items on(fx_orders.o_id=fx_orders_items.o_id)')
        ->where($ary_where)->count('distinct(fx_orders.o_id)');
        $this->assign('wtrade_num',$count1); 
    }
    
    ### 以下为保护方法 #########################################################
    /**
     * 设置控制器在用户中心页面中的1级2级3级菜单哪些被选中
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     * @param int $nav1 一级导航菜单位置
     * @param int $nav2 二级导航菜单位置
     * @param int $nav3 三级导航菜单位置
     */

    protected function getSubNav($nav1 = 0, $nav2 = 0, $nav3 = 0, $name = '') {
        //判断主导航和左侧导航的位置
        $this->assign('nav1', $nav1);
        $this->assign('nav2', $nav2);
        $this->assign('nav3', $nav3);
        //生成面包屑导航数据，默认全部从后台首页开始 ++++++++++++++++++++++++++++++++
        $nav['bread0'] = array('name' => '后台首页', 'url' => U('Supplier/Index/index'));
        //一级为顶部栏目名称
        $nav['bread1'] = $this->tops[$nav1 - 1];
        //二级为左侧模块名称
        $nav['bread2'] = $this->menus[$nav1][$nav2][0];
        //当前页面名称
        $nav['bread3'] = (empty($name)) ? ( (isset($this->menus[$nav1][$nav2][$nav3])) ? $this->menus[$nav1][$nav2][$nav3] : array('name' => '') ) : array('name' => $name);
        $this->assign($nav);
    }

    /**
     * 设置每个二级模块的名称
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function setTitle($page_title = '') {
        $this->assign('page_title', $page_title);
    }

    ### 以下为私有方法 #########################################################

    /**
     * 判断是否登录
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-31
     */
    private function doCheckLogin() {
        //var_dump($_SESSION);
        //echo session('Supplier');
        //echo "here";
        //todo 此处要做登录判断
        if (!session('Supplier')) {
            $int_port = "";
            if($_SERVER["SERVER_PORT"] != 80){
                $int_port = ':' . $_SERVER["SERVER_PORT"];
            }
            //$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			$string_request_uri = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
            $data = D('SysConfig')->getCfgByModule('Supplier_LOGIN_PROMPT');
            if($data['Supplier_LOGIN_PROMPT_SET'] == '1'){
                $this->error(L('NO_LOGIN'), U('Supplier/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri));
            }else{
                header("Location:".U('Supplier/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri)."");exit;
            }
        } else {
			$SESSION_TYPE = (ini_get('session.save_handler') == 'redis')?1:0;
			if(empty($SESSION_TYPE)){
				$session_uid = cookie('session_uid');
				if(!empty($session_uid)){
					$ary_aupplier = getCache($session_uid);
					if(!empty($ary_aupplier) && !empty($ary_aupplier['u_name'])){
						$_SESSION['aupplier_name']       = $ary_aupplier['u_name'];
						$_SESSION['login_count']      = $ary_aupplier['u_login_count'];
					}   
				}		
			}
            $this->aupplier = session('Supplier');
        }
    }

    /**
     * 获取页头meta基本数据
     * @author zuo <zuojianghua@gmail.com>
     * @date 2013-01-05
     */
    private function getTitle() {
        $ary_data['common_title'] = '管易分销系统后台管理中心';
        $ary_data['common_keywords'] = '管易分销软件,';
        $ary_data['common_desc'] = '管易分销软件。';

        $ary_data['site_name'] = 'XX公司';

        $this->assign($ary_data);
    }

    /**
     * 获取顶部导航信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    private function getTop() {
        $tops = array();
        $tops[0] = array('name' => L('NAV1_1'), 'url' => U('Supplier/Index/index') ,'sn' => 'NAV1_1','type'=>'1,2,3'); //首页
        $tops[2] = array('name' => L('NAV1_3'), 'url' => U('Supplier/Products/pageList') ,'sn' => 'NAV1_3','type'=>'1,2,3'); //商品管理
        $tops[3] = array('name' => L('NAV1_5'), 'url' => U('Supplier/Orders/pageList') ,'sn' => 'NAV1_4','type'=>'1,2,3'); //订单管理
        $this->tops = $tops;
        $this->assign('tops', $tops);
    }

    /**
     * 获取左侧菜单信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-04
     */
    private function getMenus() {
        $menus = array();
        //为避免以后小菜单增加或修改导致需要修改控制器菜单位置的赋值
        $menus[1][0][0] = array('name' => '管理首页', 'url' => U('Supplier/Index/index'),'type'=>'1,2,3');
        $menus[1][0][10] = array('name' => '欢迎界面', 'url' => U('Supplier/Index/index'),'type'=>'1,2,3');
//        $menus[1][0][20] = array('name' => '常用菜单', 'url' => U('Supplier/Index/Custom'));
//        
//        //此处数据为个人用自定义的菜单 Start
//        $suppliermenus = M('AupplierMenu',C('DB_PREFIX'),'DB_CUSTOM');
//        $ary_menus = $suppliermenus->where(array('u_id'=>$_SESSION[C('Aupplier_AUTH_KEY')]))->select();
//        if(!empty($ary_menus) && is_array($ary_menus)){
//            $inti = 30;
//            foreach($ary_menus as $keymenu=>$valmenu){
//                $menus[1][0][$inti] = array('name' => $valmenu['name'], 'url' => U('Supplier/Index/Menu',"id=".$valmenu['id']."&s=".$inti));
//                $inti ++;
//            }
//        }
        //此处数据为个人用自定义的菜单 End
        
        //此处下标使用10/20/30/40这样的来定义，以后可以补9/11/12/13等等等
        //官网运营 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

		//商品管理 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[3][0][0] = array('name' => L('MENU2_0'), 'url' => '#','type'=>'1,2,3');
        $menus[3][0][10] = array('name' => L('MENU2_0_10'), 'url' => U('Supplier/Goods/goodsAdd'),'type'=>'1,2,3'); //新增商品

        //$menus[3][0][20] = array('name' => L('MENU2_0_20'), 'url' => U('Supplier/ErpProducts/erpPageList'),'type'=>'1,2,3'); //ERP商品列表
        //$menus[3][0][25] = array('name' => L('MENU2_0_25'), 'url' => U('Supplier/ErpProducts/erpGoodsZhList'),'type'=>'1,2,3'); //ERP商品列表
        //$menus[3][0][80] = array('name' => L('erp赠品列表'), 'url' => U('Supplier/ErpProducts/GiftsPageList'),'type'=>'1,2,3'); //ERP赠品列表
        $menus[3][0][30] = array('name' => L('MENU2_0_30'), 'url' => U('Supplier/Products/pageList'),'type'=>'1,2,3'); //在架商品列表
        $menus[3][0][40] = array('name' => L('MENU2_0_40'), 'url' => U('Supplier/Products/pageList','tabs=shelves'),'type'=>'1,2,3'); //下架商品列表
        $menus[3][0][45] = array('name' => '回收站', 'url' => U('Supplier/Products/pageList','tabs=recycle'),'type'=>'1,2,3'); //回收站

		$menus[3][0][50] = array('name' => '库存调整单', 'url' => U('Supplier/Stock/pageList'),'type'=>'1,2,3'); //库存调整单列表
       // $menus[3][0][60] = array('name' => '新增库存调整单', 'url' => U('Supplier/Stock/pageAdd'),'type'=>'1,2,3'); //新增库存调整单
		$menus[3][0][55] = array('name' => '商品设置', 'url' => U('Supplier/Goods/pageSet'),'type'=>'1,2,3'); //商品设置
		$menus[3][0][70] = array('name' => L('MENU2_0_70'), 'url' => U('Supplier/Stock/pageSet'),'type'=>'1,2,3'); //库存设置
		$menus[3][0][80] = array('name' => '行业属性配置', 'url' => U('Supplier/Goods/configIndustrySpec'),'type'=>'1,2,3'); //商品行业属性设置


		$menus[3][3][0] = array('name' => '商品类型管理', 'url' => '#','type'=>'1,2,3');
		$menus[3][3][10] = array('name' => '商品类型列表', 'url' => U('Supplier//GoodsType/pageList'),'type'=>'1,2,3'); //类型列表
		$menus[3][3][20] = array('name' => '添加商品类型', 'url' => U('Supplier//GoodsType/addGoodsType'),'type'=>'1,2,3'); //添加类型

		$menus[3][2][0] = array('name' => '商品属性管理', 'url' => '#','type'=>'1,2,3');
        $menus[3][2][10] = array('name' => L('MENU2_2_10'), 'url' => U('Supplier/GoodsProperty/specListPage'),'type'=>'1,2,3'); //商品属性列表
        //$menus[3][2][20] = array('name' => L('MENU2_2_20'), 'url' => U('Supplier/GoodsProperty/addSpecPage'),'type'=>'1,2,3'); //添加商品属性
        //$menus[3][2][40] = array('name' => L('属性值添加'), 'url' => U('Supplier/GoodsProperty/addSpecDetailPage'),'type'=>'1,2,3'); //添加商品属性


		$menus[3][6][0] = array('name' => '商品分组管理', 'url' => '#','type'=>'1,2,3');
		$menus[3][6][10] = array('name' => '添加商品分组', 'url' => U('Supplier/GoodsGroup/addGroup'),'type'=>'1,2,3');
        $menus[3][6][20] = array('name' => '商品分组列表', 'url' => U('Supplier/GoodsGroup/pageList'),'type'=>'1,2,3');

        //订单管理 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[4][0][0] = array('name' => L('MENU3_0'), 'url' => '#','type'=>'1,2,3');
        $menus[4][0][10] = array('name' => L('MENU3_0_10'), 'url' => U('Supplier/Orders/pageList'),'type'=>'1,2,3'); //订单列表
        $menus[4][0][15] = array('name' => '待审核订单', 'url' => U('Supplier/Orders/pageToAuditOrderList'),'type'=>'1,2,3'); //待审核订单
        $menus[4][0][20] = array('name' => L('MENU3_0_20'), 'url' => U('Supplier/Orders/pageWaitPayOrdersList'),'type'=>'1,2,3'); //待付款订单
        $menus[4][0][30] = array('name' => L('MENU3_0_30'), 'url' => U('Supplier/Orders/pageWaitDeliverOrdersList'),'type'=>'1,2,3'); //待发货订单
        $menus[4][0][40] = array('name' => L('MENU3_0_40'), 'url' => U('Supplier/Orders/pageSet'),'type'=>'1,2,3'); //订单设置
		$menus[4][0][50] = array('name' => '合并支付订单列表', 'url' => U('Supplier/MergerPayment/pageList'),'type'=>'1,2,3'); //合并支付订单列表

        $menus[4][1][0] = array('name' => L('MENU3_1'), 'url' => '#','type'=>'1,2,3');
        $menus[4][1][10] = array('name' => L('MENU3_1_10'), 'url' => U('Supplier/Orders/setAftersale'),'type'=>'1,2,3'); //售后服务配置
//        $menus[4][1][20] = array('name' => L('MENU3_1_20'), 'url' => U('Supplier/Orders/pageAftersaleList'),'type'=>'1,2,3'); //售后申请列表
        $menus[4][1][30] = array('name' => L('MENU3_1_30'), 'url' => U('Supplier/RefundsProperty/specListPage'),'type'=>'1,2,3'); //退换货单自定义属性
        $menus[4][1][40] = array('name' => L('MENU3_1_40'), 'url' => U('Supplier/RefundsProperty/specAddPage'),'type'=>'1,2,3'); //添加退换货属性//
//        $menus[4][1][50] = array('name' => '自定义退货/退款理由', 'url'=>U('Supplier/RefundsProperty/returnReason'), 'type'=>'1,2,3');//自定义退货/退款理由
        $menus[4][1][50] = array('name' => '自定义退货/退款理由', 'url'=>U('Supplier/RefundsProperty/returnReasonList'), 'type'=>'1,2,3');//自定义退货/退款理由
        $menus[4][2][0] = array('name' => L('MENU3_2'), 'url' => '#','type'=>'1,2,3');
        $menus[4][2][10] = array('name' => L('MENU3_2_10'), 'url' => U('Supplier/Orders/pageOrdersProceedsList'),'type'=>'1,2,3'); //收款单
        $menus[4][2][20] = array('name' => L('MENU3_2_20'), 'url' => U('Supplier/Orders/pageOrdersRefundList'),'type'=>'1,2,3'); //退款单
        $menus[4][2][30] = array('name' => L('MENU3_2_30'), 'url' => U('Supplier/Orders/pageOrdersDeliverList'),'type'=>'1,2,3'); //发货单
        $menus[4][2][40] = array('name' => L('MENU3_2_40'), 'url' => U('Supplier/Orders/pageOrdersReturnList'),'type'=>'1,2,3'); //退货单
        $menus[4][2][50] = array('name' => L('MENU3_2_50'), 'url' => U('Supplier/Orders/pageOrdersRefundDeliverList'),'type'=>'1,2,3');
		$menus[4][2][60] = array('name' => L('MENU3_2_60'), 'url' => U('Supplier/Orders/pageAupplierOrdersPay'),'type'=>'1,2,3');

		//销售统计
		$menus[4][5][0] = array('name' => L('MENU3_5'), 'url' => U('Supplier/SalesStatistics/index'),'type'=>'1,2,3');//销售统计
		$menus[4][5][10] = array('name' => L('MENU3_5_10'), 'url' => U('Supplier/SalesStatistics/SalesRanking'),'type'=>'1,2,3'); //销售量排名
        $menus[4][5][20] = array('name' => L('MENU3_5_20'), 'url' => U('Supplier/SalesStatistics/MembersRanking'),'type'=>'1,2,3'); //购买量排名

		$menus[4][6][0] = array('name' => L('MENU6_2'), 'url' => '#','type'=>'1,2,3'); //发票设置
        $menus[4][6][10] = array('name' => L('MENU6_2_10'), 'url' => U('Supplier/Invoice/pageSet'),'type'=>'1,2,3'); //发票设置
        $menus[4][6][20] = array('name' => L('MENU6_2_20'), 'url' => U('Supplier/IncreaseInvoice/pageList'),'type'=>'1,2,3'); //增值税发票管理
        //$menus[7][1][30] = array('name' => L('MENU6_1_30'), 'url' => U('Supplier//')); //货到付款设置
         //本期海信上线先注释掉


        //红包管理 End
	   // echo "<pre>";print_r($menus);exit;
        $menu = M('menus',C('DB_PREFIX'),'DB_CUSTOM');
        foreach($this->tops as $key_t=>$val_v){
            $top_where['name'] = $val_v['name'];
            $top_where['group'] = 'Aupplier';
            $top_where['toporder'] = $key_t;
            $top_where['mstatus'] = '1';
            $top_where['url'] = $val_v['url'];
            $top_where['fid'] = '0';
            $top_where['sn'] = $val_v['sn'];
            $top_where['type'] = $val_v['type'];
            //$top_menus_area = $menu->where($top_where)->find();
			$top_menus_area = D('Gyfx')->selectOneCache('menus',null, $top_where);
            if(empty($top_menus_area)){
                $top_id = $menu->add($top_where);
                unset($top_where);
                $top_menus_area = $menu->where(array('id'=>$top_id))->find();
            }
            foreach($menus as $key_menus_top=>$val_menus_top){
                if($key_t+1 == $key_menus_top){
                    foreach ($val_menus_top as $key_menus_second=>$val_menus_second){
                        foreach ($val_menus_second as $kt=>$kv){
                            $sn = 'MENU'.$key_menus_top.'_'.$key_menus_second.'_'.$kt;
                            $second_where['name'] = $kv['name'];
                            $second_where['group'] = 'Aupplier';
                            $second_where['suborder'] = $key_menus_second;
                            $second_where['mstatus'] = '1';
                            $second_where['url'] = $kv['url'];
                            $second_where['fid'] = $top_menus_area['id'];
                            $second_where['sn'] = $sn;
                            $second_where['type'] = $kv['type'];
                            //$second_menus_area = $menu->where(array('sn'=>$sn))->find();
							$second_menus_area = D('Gyfx')->selectOneCache('menus',null, array('sn'=>$sn));

                            if($kt == 0 && empty($second_menus_area)){
                                $second_id = $menu->add($second_where);
                                $second_menus_area = $menu->where(array('id'=>$second_id))->find();
								D('Gyfx')->deleteOneCache('menus',null, array('sn'=>$sn));
                            }else{
                                if($kt != 0){
                                    $last_menus_sn = 'MENU'.$key_menus_top.'_'.$key_menus_second.'_0';
                                    //$last_menus = $menu->where(array('sn'=>$last_menus_sn))->find();
									$last_menus = D('Gyfx')->selectOneCache('menus',null, array('sn'=>$last_menus_sn));
									if(isset($last_menus['id'])){
										unset($second_where['suborder']);
										$second_where['fid'] = $last_menus['id'];
										$second_where['threeorder'] = $kt;
										//$third_menus = $menu->where($second_where)->find();
										$third_menus = D('Gyfx')->selectOneCache('menus',null, $second_where);
										if(empty($third_menus)){
											$second_id = $menu->add($second_where);
											D('Gyfx')->deleteOneCache('menus',null, $second_where);
										}
									}else{
										D('Gyfx')->deleteOneCache('menus',null, array('sn'=>$last_menus_sn));
									}
                                }
                            }
                            unset($second_where);
                        }
                    }
                }

            }

        }

        //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
       // echo C('CUSTOMER_TYPE');exit;
        //兼容文件/数据库菜单一致
        $where = array();
        $where['group'] = 'Aupplier';
        $where['mstatus'] = '1';
        $where['fid'] = '0';
        $data = array();
        //$ary_menu = $menu->where($where)->order('toporder asc')->select();
		$ary_menu = D('Gyfx')->selectAllCache('menus',null, $where, 'toporder asc');
       //echo "<pre>";print_r($ary_menu);exit;
        if(!empty($ary_menu) && is_array($ary_menu)){
            foreach($ary_menu as $keymu=>$valmu){
                //$arr_menu = $menu->where(array('group'=>'Aupplier','mstatus'=>'1','fid'=>$valmu['id']))->select();
				$arr_menu = D('Gyfx')->selectAllCache('menus',null, array('group'=>'Aupplier','mstatus'=>'1','fid'=>$valmu['id']));

                if(!empty($arr_menu)){
                    foreach($arr_menu as $kymu=>$vlmu){
                        $ary_types = explode(',',$vlmu['type']);
                        if(in_array(C('CUSTOMER_TYPE'),$ary_types)){
                            $data[$valmu['toporder'] + 1][$vlmu['suborder']][$valmu['suborder']] = array('name'=>$vlmu['name'],'url'=>$vlmu['url']);
                            //$arr_menus = $menu->where(array('group'=>'Aupplier','mstatus'=>'1','fid'=>$vlmu['id']))->select();
							$arr_menus = D('Gyfx')->selectAllCache('menus',null, array('group'=>'Aupplier','mstatus'=>'1','fid'=>$vlmu['id']));
                            if(!empty($arr_menus)){
                                foreach($arr_menus as $ky=>$vl){
                                    $ary_type = explode(',',$vl['type']);
                                    if(in_array(C('CUSTOMER_TYPE'),$ary_type)){
                                        $data[$valmu['toporder'] + 1][$vlmu['suborder']][$vl['threeorder']] = array('name'=>$vl['name'],'url'=>$vl['url']);
                                    }
                                }
                            }
                        }

                    }
                }
            }
        }else{
			D('Gyfx')->deleteAllCache('menus',null, $where, 'toporder asc');
		}
        $this->menus = $menus;
        $this->assign('menus', $data);
    }

    /**
     * 导出后台EXCEL信息数据
     * @author Terry
     * @since 7.2
     * @version 1.0
     * @date 2012-5-14
     */
    public function getExportFileDownList() {
        $ary_get = $this->_get();
        switch ($ary_get['type']) {
            case 'excel':
                header("Content-type:application/force-download;charset=utf-8");
                header("Content-Disposition:attachment;filename=" . $ary_get['file']);
                readfile(APP_PATH.'Public/Uploads/'.CI_SN.'/excel/' . $ary_get['file']);
                break;
        }
        exit;
    }

    /**
     * 将菜单信息存入表中
     * @author Terry
     * @since 7.4.5
     * @version 1.0
     * @date 2013-10-22
     * @return result
     */
    public function saveMenus($data){
        $menu = M('menus',C('DB_PREFIX'),'DB_CUSTOM');
        //后台菜单管理
        $menu->startTrans();
        if(!empty($data) && is_array($data)){
            foreach($data as $keym=>$valm){
                //$ary_nav = $menu->where(array('sn'=>'NAV1_'.($keym-1),'mstatus'=>'1','group'=>'Aupplier'))->find();
				$ary_nav = D('Gyfx')->selectOneCache('menus',$ary_field=null, array('sn'=>'NAV1_'.($keym-1),'mstatus'=>'1','group'=>'Aupplier'), $ary_order=null,$time=null);
                $ary_data = array();
                $ary_data['name'] = $this->tops[$keym-1]['name'];
                $ary_data['group'] = "Aupplier";
                $ary_data['toporder'] = ($keym-1);
                $ary_data['fid'] = '0';
                $ary_data['sn'] = 'NAV1_'.($keym-1);
                $ary_data['url'] = $this->tops[$keym-1]['url'];
                if(isset($this->tops[$keym-1]['status'])){
                    $ary_data['mstatus'] = $this->tops[$keym-1]['status'];
                }
                if(!empty($ary_nav) && is_array($ary_nav)){
					//暂时不更新
                    //$ary_result = $menu->where(array('id'=>$ary_nav['id']))->data($ary_data)->save();
                    $ary_result = $ary_nav['id'];
                }else{
                    $ary_result = $menu->add($ary_data);
                }
//                echo $ary_result;
                if(FALSE !== $ary_result){
                    if(!empty($valm) && is_array($valm)){
                        foreach($valm as $kym=>$vlm){
                            //$arr_nav = $menu->where(array('sn'=>'MENU'.($keym-1).'_'.($kym),'group'=>'Aupplier'))->find();
				$ary_nav = D('Gyfx')->selectOneCache('menus',$ary_field=null, array('sn'=>'MENU'.($keym-1).'_'.($kym),'group'=>'Aupplier'));
                            $arr_data = array();
                            $arr_data['name'] = $vlm[0]['name'];
                            $arr_data['group'] = "Aupplier";
                            $arr_data['toporder'] = ($keym-1);
                            $arr_data['fid'] = $ary_result;
                            $arr_data['suborder'] = '0';
                            $arr_data['sn'] = 'MENU'.($keym-1).'_'.($kym);
                            $arr_data['url'] = $vlm[0]['url'];
                            if(isset($vlm[0]['status'])){
                                $ary_data['mstatus'] = $vlm[0]['status'];
                            }
//                            echo "<pre>";print_r($arr_data);
                            if(!empty($arr_nav) && is_array($arr_nav)){
								//暂时不更新
                                //$ary_res = $menu->where(array('id'=>$arr_nav['id']))->data($arr_data)->save();
                                $ary_res = $arr_nav['id'];
                            }else{
                                $ary_res = $menu->add($arr_data);
                            }
//                            echo "------------------------此处分割-----------------------";
                            if(FALSE !== $ary_res){
                                unset($vlm[0]);
                                foreach($vlm as $km=>$vm){
                                    //$nav = $menu->where(array('sn'=>'MENU'.($keym-1).'_'.($kym).'_'.$km,'group'=>'Aupplier'))->find();
								$nav = D('Gyfx')->selectOneCache('menus',$ary_field=null, array('sn'=>'MENU'.($keym-1).'_'.($kym).'_'.$km,'group'=>'Aupplier'));
                                    $data = array();
                                    $data['name'] = $vm['name'];
                                    $data['group'] = "Aupplier";
                                    $data['toporder'] = ($keym-1);
                                    $data['fid'] = $ary_res;
                                    $data['suborder'] = $km;
                                    $data['sn'] = 'MENU'.($keym-1).'_'.($kym).'_'.$km;
                                    $data['url'] = $vm['url'];
                                    if(isset($vm['status'])){
                                        $ary_data['mstatus'] = $vm['status'];
                                    }
//                                    echo "<pre>";print_r($data);
                                    if(!empty($nav) && is_array($nav)){
                                        //$res = $menu->where(array('id'=>$nav['id']))->data($data)->save();
										$res = $nav['id'];
                                    }else{
                                        $res = $menu->add($data);
                                    }
                                    if(FALSE !== $res){
                                        $menu->commit();
                                    }else{
                                        $menu->rollback();
                                    }
                                }
                            }else{
                                $menu->rollback();
                            }
                        }
                    }
                }else{
                    $menu->rollback();
                }
            }
        }
//        exit;
    }

   	/**
     * 执行update数据库更新
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-01-17
     */
     public function updateSql(){
        $update_file = './Lib/Action/System/update.html';
        $update_sql_file = './Lib/Action/System/update.sql';
        $file_exist = file_exists($update_file);
        $file_sql_exist = file_exists($update_file);
        if($file_exist == true || $file_sql_exist == true){
        	if(ACTION_NAME == 'updateSql'){
        		if(MODULE_NAME == 'Index'){
   	        		if($file_exist == true){
	        			$file_content = file_get_contents($update_file);
	        		}else{
	        			$file_content = '脚本升级';
	        		}
	        		$this->assign('file_content',trim($file_content));
	        		$this->display('Index:updateSql');
		            exit;
        		}
        	}else{
        		//允许更新脚本
        		if(ACTION_NAME != 'doUpdateSql'){
        			$this->display('Index:update');
	            	exit;
        		}
        	}
        }
     }

}
