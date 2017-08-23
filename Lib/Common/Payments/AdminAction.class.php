<?php

/**
 * 后台基类
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class AdminAction extends GyfxAction {
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
    protected $admin = array();

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
        $admin_access = D('SysConfig')->getCfgByModule('ADMIN_ACCESS');
      
        if (intval($admin_access['EXPIRED_TIME']) > 0 && Session::isExpired()) {
            unset($_SESSION[C('USER_AUTH_KEY')]);
            unset($_SESSION);
            session_destroy();
        }
        if (intval($admin_access['EXPIRED_TIME']) > 0) {
            Session::setExpire(time() + $admin_access['EXPIRED_TIME'] * 60);
        }
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            $rbac = new Arbac();
            if (!$rbac->AccessDecision()) {
                //检查认证识别号
                if (!$_SESSION [C('USER_AUTH_KEY')]) {
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
                    if($this->isAjax()){
                        layout(false);
                        echo L('_VALID_ACCESS_');exit;
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
				        $admin_url = '/Admin/'.MODULE_NAME.'/'.$action_name;
				        $menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$admin_url))->find();
				        if(empty($menu_info)){
				        	$admin_url = '/Admin/'.MODULE_NAME.'/'.ACTION_NAME;
				        	$menu_info = M('menus',C('DB_PREFIX'),'DB_CUSTOM')->field('sn')->where(array('url'=>$admin_url))->find();
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
        $orders = M('orders',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_where = array();       //订单搜索条件
        $ary_where['fx_orders.erp_sn'] = '';
        $ary_where['fx_orders.o_pay_status'] = '1';
        $ary_where['_string'] = "fx_orders.o_status not in(2,4)"; 
        $count1 = $orders->where($ary_where)->count();
        //dump($orders->getLastSql());die();
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
        $nav['bread0'] = array('name' => '后台首页', 'url' => U('Admin/Index/index'));
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
        //todo 此处要做登录判断
        if (!session(C('USER_AUTH_KEY'))) {
            $int_port = "";
            if($_SERVER["SERVER_PORT"] != 80){
                $int_port = ':' . $_SERVER["SERVER_PORT"];
            }
            //$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			$string_request_uri = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
            $data = D('SysConfig')->getCfgByModule('ADMIN_LOGIN_PROMPT');
            if($data['ADMIN_LOGIN_PROMPT_SET'] == '1'){
                $this->error(L('NO_LOGIN'), U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri));
            }else{
                header("Location:".U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri)."");exit;
            }
        } else {
            $this->admin = session(C('USER_AUTH_KEY'));
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
        $tops[0] = array('name' => L('NAV1_1'), 'url' => U('Admin/Index/index') ,'sn' => 'NAV1_1','type'=>'1,2,3'); //首页
        $tops[1] = array('name' => L('NAV1_2'), 'url' => U('Admin/Home/index') ,'sn' => 'NAV1_2','type'=>'1,2,3'); //官网运营
        $tops[2] = array('name' => L('NAV1_3'), 'url' => U('Admin/Products/index') ,'sn' => 'NAV1_3','type'=>'1,2,3'); //商品管理
        $tops[3] = array('name' => L('NAV1_5'), 'url' => U('Admin/Orders/index') ,'sn' => 'NAV1_4','type'=>'1,2,3'); //订单管理
        $tops[4] = array('name' => L('NAV1_4'), 'url' => U('Admin/Promotions/index') ,'sn' => 'NAV1_5','type'=>'1,2,3'); //价格促销
        $tops[5] = array('name' => L('NAV1_6'), 'url' => U('Admin/Members/index') ,'sn' => 'NAV1_6','type'=>'1,2,3'); //渠道管控
        $tops[6] = array('name' => L('NAV1_7'), 'url' => U('Admin/Financial/index') ,'sn' => 'NAV1_7','type'=>'1,2,3'); //财务管理
        $tops[7] = array('name' => L('NAV1_8'), 'url' => U('Admin/System/pageList') ,'sn' => 'NAV1_8','type'=>'1,2,3'); //基础设置
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
        $menus[1][0][0] = array('name' => '管理首页', 'url' => U('Admin/Index/index'),'type'=>'1,2,3');
        $menus[1][0][10] = array('name' => '欢迎界面', 'url' => U('Admin/Index/index'),'type'=>'1,2,3');
//        $menus[1][0][20] = array('name' => '常用菜单', 'url' => U('Admin/Index/Custom'));
//        
//        //此处数据为个人用自定义的菜单 Start
//        $adminmenus = M('AdminMenu',C('DB_PREFIX'),'DB_CUSTOM');
//        $ary_menus = $adminmenus->where(array('u_id'=>$_SESSION[C('ADMIN_AUTH_KEY')]))->select();
//        if(!empty($ary_menus) && is_array($ary_menus)){
//            $inti = 30;
//            foreach($ary_menus as $keymenu=>$valmenu){
//                $menus[1][0][$inti] = array('name' => $valmenu['name'], 'url' => U('Admin/Index/Menu',"id=".$valmenu['id']."&s=".$inti));
//                $inti ++;
//            }
//        }
        //此处数据为个人用自定义的菜单 End
        
        //此处下标使用10/20/30/40这样的来定义，以后可以补9/11/12/13等等等
        //官网运营 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[2][0][0] = array('name' => L('MENU1_0'), 'url' => U('Admin/Notice/index'),'type'=>'1,2,3');
        $menus[2][0][10] = array('name' => L('MENU1_0_10'), 'url' => U('Admin/Notice/pageAdd'),'type'=>'1,2,3'); //发布公告
        $menus[2][0][20] = array('name' => L('MENU1_0_20'), 'url' => U('Admin/Notice/pageList'),'type'=>'1,2,3'); //公告列表
        $menus[2][0][30] = array('name' => '违规记录公告', 'url' => U('Admin/Announcement/pageList'),'type'=>'1,2,3'); //违规记录公告

        $menus[2][1][0] = array('name' => L('MENU1_1'), 'url' => U('Admin/Online/index'),'type'=>'1,2,3');
        $menus[2][1][10] = array('name' => L('MENU1_1_20'), 'url' => U('Admin/Online/pageAdd'),'type'=>'1,2,3'); //新增在线客服
		$menus[2][1][20] = array('name' => L('MENU1_1_10'), 'url' => U('Admin/Online/pageList'),'type'=>'1,2,3'); //在线客服列表
        $menus[2][1][30] = array('name' => L('MENU1_1_30'), 'url' => U('Admin/Online/pageListCate'),'type'=>'1,2,3'); //在线客服分类
        $menus[2][1][40] = array('name' => L('MENU1_1_40'), 'url' => U('Admin/Online/pageAddCate'),'type'=>'1,2,3'); //新增客服分类

        $menus[2][2][0] = array('name' => L('MENU1_2'), 'url' => U('Admin/Article/index'),'type'=>'1,2,3');
        $menus[2][2][10] = array('name' => L('MENU1_2_10'), 'url' => U('Admin/Article/pageAdd'),'type'=>'1,2,3'); //新增官网资讯
        $menus[2][2][20] = array('name' => L('MENU1_2_20'), 'url' => U('Admin/Article/pageList'),'type'=>'1,2,3'); //官网资讯列表
        $menus[2][2][30] = array('name' => L('MENU1_2_30'), 'url' => U('Admin/Article/pageListCate'),'type'=>'1,2,3'); //官网资讯分类
        $menus[2][2][40] = array('name' => L('MENU1_2_40'), 'url' => U('Admin/Article/pageAddCate'),'type'=>'1,2,3'); //新增资讯分类

        $menus[2][3][0] = array('name' => L('MENU1_3'), 'url' => U('Admin/Home/index'),'type'=>'1,2,3');
        $menus[2][3][10] = array('name' => L('MENU1_3_10'), 'url' => U('Admin/Home/pageSetting'),'type'=>'1,2,3'); //官网基本信息设置
        $menus[2][3][20] = array('name' => L('MENU1_3_20'), 'url' => U('Admin/Home/pageTpl'),'type'=>'1,2,3'); //选择官网模板管理
		$menus[2][3][25] = array('name' => '商品详情页显示设置', 'url' => U('Admin/Images/itemImageConfig'),'type'=>'1,2,3'); //商品详情页显示设置
//        $menus[2][3][30] = array('name' => L('MENU1_3_30'), 'url' => U('Admin/Home/pageIndex')); //官网首页设置
//        $menus[2][3][40] = array('name' => L('MENU1_3_40'), 'url' => U('Admin/Home/pageList')); //商品列表页设置
//        $menus[2][3][50] = array('name' => L('MENU1_3_50'), 'url' => U('Admin/Home/pageList')); //品牌列表页设置
//        $menus[2][3][60] = array('name' => L('MENU1_3_60'), 'url' => U('Admin/Home/pageDetail')); //商品详情页设置
//        $menus[2][3][70] = array('name' => L('MENU1_3_70'), 'url' => U('Admin/Home/pageMenus')); //网站栏目设置
//        $menus[2][3][80] = array('name' => L('MENU1_3_80'), 'url' => U('Admin/Home/pageCustomList')); //自定义页面列表
//        $menus[2][3][90] = array('name' => L('MENU1_3_90'), 'url' => U('Admin/Home/pageCustomAdd')); //新增自定义页面
        //$menus[2][3][100] = array('name' => L('MENU1_3_100'), 'url' => U('Admin/Home/pageWatermark')); //官网图片水印设置
        $menus[2][3][110] = array('name' => L('MENU1_3_110'), 'url' => U('Admin/Home/pageClose'),'type'=>'1,2,3'); //暂停营业公告
        $menus[2][3][120] = array('name' => L('MENU1_3_120'), 'url' => U('Admin/Home/pageRegister'),'type'=>'1,2,3'); //注册协议设置
		$menus[2][3][130] = array('name' => '首页头部广告图片管理', 'url' => U('Admin/Home/pageTopAd'),'type'=>'1,2,3'); //首页头部广告图片管理
        $menus[2][3][140] = array('name' => '会员中心皮肤管理', 'url' => U('Admin/Home/pageUcenterSkin'),'type'=>'1,2,3'); //会员中心皮肤管理
        $menus[2][4][0] = array('name' => L('MENU1_4'), 'url' => U('Admin/Seo/index'),'type'=>'1,2,3');
        $menus[2][4][10] = array('name' => L('MENU1_4_10'), 'url' => U('Admin/Seo/pageList'),'type'=>'1,2,3'); //SEO列表
        //$menus[2][4][20] = array('name' => L('MENU1_4_20'), 'url' => U('Admin/Seo/pageEdit'),'type'=>'1,2,3'); //SEO设置页
        $menus[2][4][30] = array('name' => L('MENU1_4_30'), 'url' => U('Admin/Seo/pageMap'),'type'=>'1,2,3'); //站点地图生成
        $menus[2][4][40] = array('name' => L('MENU1_4_40'), 'url' => U('Admin/Seo/pageCount'),'type'=>'1,2,3'); //统计脚本设置
        $menus[2][4][50] = array('name' => '缓存设置', 'url' => U('Admin/Seo/pageCach'),'type'=>'1,2,3'); //统计脚本设置

        $menus[2][5][0] = array('name' => L('MENU1_5'), 'url' => U('Admin/Guestbook/index'),'type'=>'1,2,3');
        $menus[2][5][10] = array('name' => L('MENU1_5_10'), 'url' => U('Admin/Guestbook/pageProductsSetting'),'type'=>'1,2,3'); //商品评论设置
        $menus[2][5][20] = array('name' => L('MENU1_5_20'), 'url' => U('Admin/Guestbook/pageProductsList'),'type'=>'1,2,3'); //商品评论列表
        $menus[2][5][30] = array('name' => L('MENU1_5_30'), 'url' => U('Admin/Message/pageMailBox'),'type'=>'1,2,3'); //站内信列表
        //$menus[2][5][40] = array('name' => L('MENU1_5_40'), 'url' => U('Admin/Message/pageSend'),'type'=>'1,2,3'); //发送站内信
        //$menus[2][5][50] = array('name' => L('MENU1_5_50'), 'url' => U('Admin/Bbs/pageDiscuz'),'type'=>'1,2,3'); //论坛内容设置
        //$menus[2][5][60] = array('name' => L('MENU1_5_60'), 'url' => U('Admin/Bbs/pageDiscuzRegister'),'type'=>'1,2,3'); //论坛同步注册
        //$menus[2][5][70] = array('name' => "投诉建议列表", 'url' => U('Admin/Suggestions/pageList'),'type'=>'1,2,3'); //投诉建议列表
        $menus[2][5][80] = array('name' => "购买咨询列表", 'url' => U('Admin/Consultation/pageList'),'type'=>'1,2,3'); //购买咨询列表
		$menus[2][5][90] = array('name' => L('买家留言列表'), 'url' => U('Admin/Members/feedBackList'),'type'=>'1,2,3'); //买家留言
			    
		$menus[2][6][0] = array('name' => L('MENU1_6'), 'url' => U('Admin/Links/index'),'type'=>'1,2,3');
        $menus[2][6][10] = array('name' => L('MENU1_6_10'), 'url' => U('Admin/Links/pageList'),'type'=>'1,2,3'); //友情链接列表
        $menus[2][6][20] = array('name' => L('MENU1_6_20'), 'url' => U('Admin/Links/pageAdd'),'type'=>'1,2,3'); //新增友情链接

        $menus[2][7][0] = array('name' => L('MENU1_7'), 'url' => U('Admin/Nav/index'),'type'=>'1,2,3');
        $menus[2][7][10] = array('name' => L('MENU1_7_10'), 'url' => U('Admin/Nav/pageList'),'type'=>'1,2,3'); //自定义导航列表
        $menus[2][7][20] = array('name' => L('MENU1_7_20'), 'url' => U('Admin/Nav/pageAdd'),'type'=>'1,2,3'); //新增自定义导航
        	
		$menus[2][8][0] = array('name' => L('MENU7_2'), 'url' => U('Admin/Email/index'),'type'=>'1,2,3');
        $menus[2][8][10] = array('name' => L('MENU7_2_10'), 'url' => U('Admin/Email/pageSmtp'),'type'=>'1,2,3'); //SMTP设置
		$menus[2][8][20] = array('name' => L('MENU7_2_20'), 'url' => U('Admin/Email/pageTemp'),'type'=>'1,2,3'); //邮件模版
		$menus[2][8][30] = array('name' => '已发送列表', 'url' => U('Admin/Email/pageList'),'type'=>'1,2,3'); //已发送列表
		
	   	$menus[2][9][0] = array('name' => 'SMS管理', 'url' => U('Admin/Sms/index'),'type'=>'1,2,3');
	    $menus[2][9][10] = array('name' => 'SMS设置', 'url' => U('Admin/Sms/pageSms'),'type'=>'1,2,3'); //SMS设置
	    $menus[2][9][20] = array('name' => '短信模板', 'url' => U('Admin/Sms/pageTemp'),'type'=>'1,2,3'); //短信模版
	    $menus[2][9][30] = array('name' => '已发送列表', 'url' => U('Admin/Sms/pageList'),'type'=>'1,2,3'); //已发送列表
		       
		
		//商品管理 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[3][0][0] = array('name' => L('MENU2_0'), 'url' => '#','type'=>'1,2,3');
        $menus[3][0][10] = array('name' => L('MENU2_0_10'), 'url' => U('Admin/Goods/goodsAdd'),'type'=>'1,2,3'); //新增商品
        
        //$menus[3][0][20] = array('name' => L('MENU2_0_20'), 'url' => U('Admin/ErpProducts/erpPageList'),'type'=>'1,2,3'); //ERP商品列表
        //$menus[3][0][25] = array('name' => L('MENU2_0_25'), 'url' => U('Admin/ErpProducts/erpGoodsZhList'),'type'=>'1,2,3'); //ERP商品列表
        //$menus[3][0][80] = array('name' => L('erp赠品列表'), 'url' => U('Admin/ErpProducts/GiftsPageList'),'type'=>'1,2,3'); //ERP赠品列表
        $menus[3][0][30] = array('name' => L('MENU2_0_30'), 'url' => U('Admin/Products/pageList'),'type'=>'1,2,3'); //在架商品列表
        $menus[3][0][40] = array('name' => L('MENU2_0_40'), 'url' => U('Admin/Products/pageList','tabs=shelves'),'type'=>'1,2,3'); //下架商品列表  
        $menus[3][0][45] = array('name' => '回收站', 'url' => U('Admin/Products/pageList','tabs=recycle'),'type'=>'1,2,3'); //回收站  

		$menus[3][0][50] = array('name' => '库存调整单', 'url' => U('Admin/Stock/pageList'),'type'=>'1,2,3'); //库存调整单列表
       // $menus[3][0][60] = array('name' => '新增库存调整单', 'url' => U('Admin/Stock/pageAdd'),'type'=>'1,2,3'); //新增库存调整单
		$menus[3][0][70] = array('name' => L('MENU2_0_70'), 'url' => U('Admin/Stock/pageSet'),'type'=>'1,2,3'); //库存设置
		$menus[3][0][80] = array('name' => '行业属性配置', 'url' => U('Admin/Goods/configIndustrySpec'),'type'=>'1,2,3'); //商品行业属性设置

        $menus[3][1][0] = array('name' => L('MENU2_1'), 'url' => '#','type'=>'1,2,3');
        $menus[3][1][10] = array('name' => L('MENU2_1_10'), 'url' => U('Admin/GoodsCategory/pageList'),'type'=>'1,2,3'); //商品分类列表
        $menus[3][1][20] = array('name' => L('MENU2_1_20'), 'url' => U('Admin/GoodsCategory/addCategory'),'type'=>'1,2,3'); //添加商品分类
        $menus[3][1][30] = array('name' => L('MENU2_1_40'), 'url' => U('Admin/GoodsCategory/pageSet'),'type'=>'1,2,3'); //商品分类设置

		$menus[3][3][0] = array('name' => '商品类型管理', 'url' => '#','type'=>'1,2,3');
		$menus[3][3][10] = array('name' => '商品类型列表', 'url' => U('Admin//GoodsType/pageList'),'type'=>'1,2,3'); //类型列表
		$menus[3][3][20] = array('name' => '添加商品类型', 'url' => U('Admin//GoodsType/addGoodsType'),'type'=>'1,2,3'); //添加类型
		
		$menus[3][2][0] = array('name' => '商品属性管理', 'url' => '#','type'=>'1,2,3');
        $menus[3][2][10] = array('name' => L('MENU2_2_10'), 'url' => U('Admin/GoodsProperty/specListPage'),'type'=>'1,2,3'); //商品属性列表
        //$menus[3][2][20] = array('name' => L('MENU2_2_20'), 'url' => U('Admin/GoodsProperty/addSpecPage'),'type'=>'1,2,3'); //添加商品属性
        //$menus[3][2][40] = array('name' => L('属性值添加'), 'url' => U('Admin/GoodsProperty/addSpecDetailPage'),'type'=>'1,2,3'); //添加商品属性

        $menus[3][3][0] = array('name' => L('MENU2_3'), 'url' => '#','type'=>'1,2,3');
        $menus[3][3][10] = array('name' => L('MENU2_3_10'), 'url' => U('Admin/GoodsType/pageList'),'type'=>'1,2,3'); //类型列表
        $menus[3][3][20] = array('name' => L('MENU2_3_20'), 'url' => U('Admin/GoodsType/addGoodsType'),'type'=>'1,2,3'); //添加类型
        

        $menus[3][4][0] = array('name' => L('MENU2_4'), 'url' => '#','type'=>'1,2,3');
        $menus[3][4][10] = array('name' => L('MENU2_4_10'), 'url' => U('Admin/GoodsBrand/pageList'),'type'=>'1,2,3'); //品牌列表
        $menus[3][4][20] = array('name' => L('MENU2_4_20'), 'url' => U('Admin/GoodsBrand/addBrand'),'type'=>'1,2,3'); //添加品牌
		
        //城建同步的数据显示 （暂时隐藏）++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[3][5][0] = array('name' => '商品促销管理', 'url' => '#','type'=>'1,2,3');
// 		$menus[3][5][10] = array('name' => L('MENU2_5_10'), 'url' => U('Admin/Recources/recourcesList'),'type'=>'1,2,3'); //再生资源列表
//         $menus[3][5][20] = array('name' => L('MENU2_5_20'), 'url' => U('Admin/Recources/recoveryUnitList'),'type'=>'1,2,3'); //回收单位列表
//         $menus[3][5][30] = array('name' => L('MENU2_5_30'), 'url' => U('Admin/Recources/weighingMachineList'),'type'=>'1,2,3'); //称重地磅列表
//         $menus[3][5][40] = array('name' => L('MENU2_5_40'), 'url' => U('Admin/Recources/carriersList'),'type'=>'1,2,3'); //运输公司信息列表
//         $menus[3][5][50] = array('name' => L('MENU2_5_50'), 'url' => U('Admin/Recources/agreementList'),'type'=>'1,2,3'); //回收合同列表
        //$menus[3][5][42] = array('name' => '新增组合商品', 'url' => U('Admin/Goods/addCombinationGoodPage'),'type'=>'1,2,3');
        $menus[3][5][50] = array('name' => '组合商品列表', 'url' => U('Admin/Goods/combinationGoodsList'),'type'=>'1,2,3');
        //$menus[3][5][44] = array('name' => '新增组合规格商品', 'url' => U('Admin/Goods/addCombinationPropertyGoodsPage'),'type'=>'1,2,3');
        $menus[3][5][43] = array('name' => '规格组合商品列表', 'url' => U('Admin/Goods/combinationPropertyGoodsList'),'type'=>'1,2,3');
        $menus[3][5][47] = array('name' => '自由推荐列表', 'url' => U('Admin/GoodsFreeCollocation/freeCollocationList'),'type'=>'1,2,3'); 
        //$menus[3][5][48] = array('name' => '新增自由推荐', 'url' => U('Admin/GoodsFreeCollocation/addFreeCollocationPage'),'type'=>'1,2,3');
        
		
		$menus[3][6][0] = array('name' => '商品分组管理', 'url' => '#','type'=>'1,2,3');
		$menus[3][6][10] = array('name' => '添加商品分组', 'url' => U('Admin/GoodsGroup/addGroup'),'type'=>'1,2,3');
        $menus[3][6][20] = array('name' => '商品分组列表', 'url' => U('Admin/GoodsGroup/pageList'),'type'=>'1,2,3');
		
        //订单管理 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[4][0][0] = array('name' => L('MENU3_0'), 'url' => '#','type'=>'1,2,3');
        $menus[4][0][10] = array('name' => L('MENU3_0_10'), 'url' => U('Admin/Orders/pageList'),'type'=>'1,2,3'); //订单列表
        $menus[4][0][15] = array('name' => '待审核订单', 'url' => U('Admin/Orders/pageToAuditOrderList'),'type'=>'1,2,3'); //待审核订单
        $menus[4][0][20] = array('name' => L('MENU3_0_20'), 'url' => U('Admin/Orders/pageWaitPayOrdersList'),'type'=>'1,2,3'); //待付款订单
        $menus[4][0][30] = array('name' => L('MENU3_0_30'), 'url' => U('Admin/Orders/pageWaitDeliverOrdersList'),'type'=>'1,2,3'); //待发货订单
        $menus[4][0][40] = array('name' => L('MENU3_0_40'), 'url' => U('Admin/Orders/pageSet'),'type'=>'1,2,3'); //订单设置
		$menus[4][0][50] = array('name' => '合并支付订单列表', 'url' => U('Admin/MergerPayment/pageList'),'type'=>'1,2,3'); //合并支付订单列表

        $menus[4][1][0] = array('name' => L('MENU3_1'), 'url' => '#','type'=>'1,2,3');
        $menus[4][1][10] = array('name' => L('MENU3_1_10'), 'url' => U('Admin/Orders/setAftersale'),'type'=>'1,2,3'); //售后服务配置
//        $menus[4][1][20] = array('name' => L('MENU3_1_20'), 'url' => U('Admin/Orders/pageAftersaleList'),'type'=>'1,2,3'); //售后申请列表
        $menus[4][1][30] = array('name' => L('MENU3_1_30'), 'url' => U('Admin/RefundsProperty/specListPage'),'type'=>'1,2,3'); //退换货单自定义属性
        $menus[4][1][40] = array('name' => L('MENU3_1_40'), 'url' => U('Admin/RefundsProperty/specAddPage'),'type'=>'1,2,3'); //添加退换货属性
        $menus[4][1][50] = array('name' => '自定义退货/退款理由', 'url'=>U('Admin/RefundsProperty/returnReason'), 'type'=>'1,2,3');//自定义退货/退款理由
        $menus[4][2][0] = array('name' => L('MENU3_2'), 'url' => '#','type'=>'1,2,3');
        $menus[4][2][10] = array('name' => L('MENU3_2_10'), 'url' => U('Admin/Orders/pageOrdersProceedsList'),'type'=>'1,2,3'); //收款单
        $menus[4][2][20] = array('name' => L('MENU3_2_20'), 'url' => U('Admin/Orders/pageOrdersRefundList'),'type'=>'1,2,3'); //退款单
        $menus[4][2][30] = array('name' => L('MENU3_2_30'), 'url' => U('Admin/Orders/pageOrdersDeliverList'),'type'=>'1,2,3'); //发货单
        $menus[4][2][40] = array('name' => L('MENU3_2_40'), 'url' => U('Admin/Orders/pageOrdersReturnList'),'type'=>'1,2,3'); //退货单
        //$menus[4][3][0] = array('name' => L('MENU3_3'), 'url' => '#');
        //$menus[4][3][10] = array('name' => L('MENU3_3_10'), 'url' => U('Admin//')); //销售额总览
        //$menus[4][3][20] = array('name' => L('MENU3_3_20'), 'url' => U('Admin//')); //分组销售统计
        //$menus[4][3][30] = array('name' => L('MENU3_3_30'), 'url' => U('Admin//')); //会员消费统计
        //$menus[4][3][40] = array('name' => L('MENU3_3_40'), 'url' => U('Admin//')); //销售指标分析
        //$menus[4][3][50] = array('name' => L('MENU3_3_50'), 'url' => U('Admin//')); //销售量排名
        //$menus[4][3][60] = array('name' => L('MENU3_3_60'), 'url' => U('Admin//')); //购买量排名

        $menus[4][4][0] = array('name' => L('MENU3_4'), 'url' => U('Admin/Delivery/index'),'type'=>'1,2,3');
        $menus[4][4][10] = array('name' => L('MENU3_4_10'), 'url' => U('Admin/Delivery/pageList'),'type'=>'1,2,3'); //配送公司列表
        $menus[4][4][20] = array('name' => L('MENU3_4_20'), 'url' => U('Admin/Delivery/pageAdd'),'type'=>'1,2,3'); //配送公司添加
        $menus[4][4][30] = array('name' => L('MENU3_4_30'), 'url' => U('Admin/Delivery/pageAddress'),'type'=>'1,2,3'); //地址库管理
        //$menus[4][4][40] = array('name' => L('MENU3_4_40'), 'url' => U('Admin/Delivery/pageAddArea'),'type'=>'1,2,3'); //配送区域添加
		//销售统计
		$menus[4][5][0] = array('name' => L('MENU3_5'), 'url' => U('Admin/SalesStatistics/index'),'type'=>'1,2,3');//销售统计
		$menus[4][5][10] = array('name' => L('MENU3_5_10'), 'url' => U('Admin/SalesStatistics/SalesRanking'),'type'=>'1,2,3'); //销售量排名
        $menus[4][5][20] = array('name' => L('MENU3_5_20'), 'url' => U('Admin/SalesStatistics/MembersRanking'),'type'=>'1,2,3'); //购买量排名
	   
		$menus[4][6][0] = array('name' => L('MENU6_2'), 'url' => '#','type'=>'1,2,3'); //发票设置
        $menus[4][6][10] = array('name' => L('MENU6_2_10'), 'url' => U('Admin/Invoice/pageSet'),'type'=>'1,2,3'); //发票设置
        $menus[4][6][20] = array('name' => L('MENU6_2_20'), 'url' => U('Admin/IncreaseInvoice/pageList'),'type'=>'1,2,3'); //增值税发票管理
        //$menus[7][1][30] = array('name' => L('MENU6_1_30'), 'url' => U('Admin//')); //货到付款设置
        
        //促销活动 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[5][0][0] = array('name' => L('MENU4_0'), 'url' => U('Admin/Promotions/index'),'type'=>'1,2,3');
//        $menus[5][0][10] = array('name' => L('MENU4_0_10'), 'url' => U('Admin/Promotions/pageList'),'type'=>'1,2,3'); //促销活动列表
//        $menus[5][0][20] = array('name' => L('MENU4_0_20'), 'url' => U('Admin/Promotions/pageAdd'),'type'=>'1,2,3'); //新建促销活动
        $menus[5][0][40] = array('name' => L('促销活动列表'), 'url' => U('Admin/Promotions/pageProList'),'type'=>'1,2,3'); //促销活动列表
        $menus[5][0][30] = array('name' => L('促销活动新增'), 'url' => U('Admin/Promotions/pageProAdd'),'type'=>'1,2,3'); //促销活动新增
        $menus[5][1][0] = array('name' => L('MENU4_1'), 'url' => U('Admin/Coupon/index'),'type'=>'1,2,3');
        $menus[5][1][10] = array('name' => L('MENU4_1_10'), 'url' => U('Admin/Coupon/pageList'),'type'=>'1,2,3'); //红包列表
        $menus[5][1][20] = array('name' => L('MENU4_1_20'), 'url' => U('Admin/Coupon/pageAdd'),'type'=>'1,2,3'); //新增优惠劵
        $menus[5][1][30] = array('name' => L('MENU4_1_30'), 'url' => U('Admin/Coupon/pageAuto'),'type'=>'1,2,3'); //批量新增
        $menus[5][1][32] = array('name' => L('MENU4_1_32'), 'url' => U('Admin/Coupon/pageRulesList'),'type'=>'1,2,3');//抢优惠券规则列表
        $menus[5][1][35] = array('name' => L('MENU4_1_35'), 'url' => U('Admin/Coupon/pageRulesAdd'),'type'=>'1,2,3');//新增抢优惠券规则
		$menus[5][1][40] = array('name' => L('优惠券获取节点'), 'url' => U('Admin/Coupon/pageSet'),'type'=>'1,2,3');
        //团购活动
        $menus[5][2][0] = array('name' => L('MENU4_2'), 'url' => U('Admin/Groupbuy/index'),'type'=>'1,2,3');
        $menus[5][2][10] = array('name' => L('MENU4_2_10'), 'url' => U('Admin/Groupbuy/pageList'),'type'=>'1,2,3'); //团购列表
        $menus[5][2][20] = array('name' => L('MENU4_2_20'), 'url' => U('Admin/Groupbuy/pageAdd'),'type'=>'1,2,3'); //新增团购
        $menus[5][2][30] = array('name' => L('MENU4_2_30'), 'url' => U('Admin/Groupbuy/pageSet'),'type'=>'1,2,3'); //团购设置
        $menus[5][2][40] = array('name' => L('MENU4_2_40'), 'url' => U('Admin/Groupbuy/pageCateList'),'type'=>'1,2,3'); //团购分类列表
		$menus[5][2][50] = array('name' => L('MENU4_2_50'), 'url' => U('Admin/Groupbuy/pageBrandList'),'type'=>'1,2,3'); //团购品牌列表
				
        //秒杀活动
        $menus[5][4][0] = array('name' => "秒杀活动", 'url' => U('Admin/Spike/index'),'type'=>'1,2,3');
        $menus[5][4][10] = array('name' => "秒杀列表", 'url' => U('Admin/Spike/pageList'),'type'=>'1,2,3'); //秒杀列表
        $menus[5][4][20] = array('name' => "新增秒杀", 'url' => U('Admin/Spike/add'),'type'=>'1,2,3'); //新增秒杀活动
        $menus[5][4][30] = array('name' => "秒杀设置", 'url' => U('Admin/Spike/pageSet'),'type'=>'1,2,3'); //秒杀设置
        $menus[5][4][40] = array('name' => '秒杀类目列表', 'url' => U('Admin/Spike/pageCateList'),'type'=>'1,2,3'); //秒杀分类列表
		  
        //自由搭配
        $menus[5][3][0] = array('name' => '自由搭配', 'url' => U('Admin/GoodsFreeRecommend/freeRecommendList'),'type'=>'1,2,3');
        $menus[5][3][10] = array('name' => '自由搭配列表', 'url' => U('Admin/GoodsFreeRecommend/freeRecommendList'),'type'=>'1,2,3');
        $menus[5][3][20] = array('name' => '新增自由搭配', 'url' => U('Admin/GoodsFreeRecommend/addFreeRecommendPage'),'type'=>'1,2,3');
        
        //预售活动
        $menus[5][5][0] = array('name' => '预售活动', 'url' => U('Admin/Presale/index'),'type'=>'1,2,3');
        $menus[5][5][10] = array('name' => '预售商品列表', 'url' => U('Admin/Presale/pageList'),'type'=>'1,2,3'); //预售列表
        $menus[5][5][20] = array('name' => '新增预售商品', 'url' => U('Admin/Presale/pageAdd'),'type'=>'1,2,3'); //预售新增
        $menus[5][5][30] = array('name' => '预售商品设置', 'url' => U('Admin/Presale/pageSet'),'type'=>'1,2,3'); //预售设置
        
		//抽奖
        $menus[5][6][0] = array('name' => '抽奖活动', 'url' => U('Admin/Lottery/index'),'type'=>'1,2,3');
        $menus[5][6][10] = array('name' => '抽奖活动列表', 'url' => U('Admin/Lottery/index'),'type'=>'1,2,3');
		$menus[5][6][20] = array('name' => '奖品列表', 'url' => U('Admin/Lottery/userList'),'type'=>'1,2,3'); //奖品列表
		
		//试用
        $menus[5][7][0] = array('name' => '试用活动', 'url' => U('Admin/Try/index'),'type'=>'1,2,3');
        $menus[5][7][10] = array('name' => '试用活动列表', 'url' => U('Admin/Try/index'),'type'=>'1,2,3');
		$menus[5][7][20] = array('name' => '试用活动申请列表', 'url' => U('Admin/Try/apply_index'),'type'=>'1,2,3');
        $menus[5][7][30] = array('name' => '试用报告列表', 'url' => U('Admin/Try/report'),'type'=>'1,2,3');
        $menus[5][7][40] = array('name' => '试用活动设置', 'url' => U('Admin/Try/pageSet'),'type'=>'1,2,3');
		
        //渠道管控 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[6][0][0] = array('name' => L('MENU5_0'), 'url' => '#','type'=>'1,2,3');
        $menus[6][0][10] = array('name' => L('MENU5_0_10'), 'url' => U('Admin/Members/pageList'),'type'=>'1,2,3'); //会员列表
        $menus[6][0][20] = array('name' => L('MENU5_0_20'), 'url' => U('Admin/Members/memberAdd'),'type'=>'1,2,3'); //会员添加
        $menus[6][0][30] = array('name' => L('所属平台列表'), 'url' => U('Admin/Sourceplatform/pageList'),'type'=>'1,2,3'); //所属平台列表
        $menus[6][0][40] = array('name' => L('所属平台列表添加'), 'url' => U('Admin/Sourceplatform/pageAdd'),'type'=>'1,2,3'); //所属平台列表添加
        $menus[6][0][50] = array('name' => L('会员平台分布'), 'url' => U('Admin/MembersDistributed/platformPie'),'type'=>'1,2,3'); //会员平台分布
        $menus[6][0][60] = array('name' => L('会员区域分布'), 'url' => U('Admin/MembersDistributed/membersAreaPie'),'type'=>'1,2,3'); //会员区域分布
        $menus[6][0][65] = array('name' => L('第三方授权平台分布'), 'url' => U('Admin/MembersDistributed/memberThdPic'),'type'=>'1,2,3'); //第三方授权平台分布
        $menus[6][0][80] = array('name' => L('会员设置'), 'url' => U('Admin/Members/pageSet'),'type'=>'1,2,3'); //会员区域分布
		$menus[6][0][90] = array('name' => L('会员属性项列表'), 'url' => U('Admin/Members/fieldsList'),'type'=>'1,2,3'); //会员属性项列表
        
        $menus[6][1][0] = array('name' => L('MENU5_1'), 'url' => '#','type'=>'1,2,3');
        $menus[6][1][10] = array('name' => L('MENU5_1_10'), 'url' => U('Admin/Memberlevel/pageList'),'type'=>'1,2,3'); //等级列表
        $menus[6][1][20] = array('name' => L('MENU5_1_20'), 'url' => U('Admin/Memberlevel/pageAdd'),'type'=>'1,2,3'); //等级添加
        //$menus[6][1][30] = array('name' => L('MENU5_1_30'), 'url' => U('Admin//'),'type'=>'1,2,3'); //ERP会员等级同步
        $menus[6][1][40] = array('name' => L('MENU5_1_40'), 'url' => U('Admin/Membergroup/pageList'),'type'=>'1,2,3'); //分组列表
        $menus[6][1][50] = array('name' => L('MENU5_1_50'), 'url' => U('Admin/Membergroup/pageAdd'),'type'=>'1,2,3'); //添加分组
        $menus[6][1][60] = array('name' => L('MENU5_1_60'), 'url' => U('Admin/Membergroup/groupingPage'),'type'=>'1,2,3'); //会员归组
		
        $menus[6][2][0] = array('name' => L('MENU5_2'), 'url' => U('Admin/Authorize/index'),'type'=>'1,2,3');
        $menus[6][2][10] = array('name' => L('MENU5_2_10'), 'url' => U('Admin/Authorize/pageList'),'type'=>'1,2,3'); //授权线列表
        $menus[6][2][20] = array('name' => L('MENU5_2_20'), 'url' => U('Admin/Authorize/pageAdd'),'type'=>'1,2,3'); //授权线添加
        $menus[6][2][30] = array('name' => L('MENU5_2_30'), 'url' => U('Admin/Authorize/pageSet'),'type'=>'1,2,3'); //会员授权
        $menus[6][2][40] = array('name' => L('第三方授权登录设置'), 'url'=>U('Admin/Thdlogin/pageSet'),'type'=>'1,2,3');//第三方授权登录设置
         //本期海信上线先注释掉
        //$menus[6][3][0] = array('name' => '子公司管理', 'url' => '#','type'=>'1,3');
        //$menus[6][3][10] = array('name' => '子公司列表', 'url' => U('Admin/Subcompany/pageList'),'type'=>'1,3'); //子公司列表
        //$menus[6][3][20] = array('name' => '子公司添加', 'url' => U('Admin/Subcompany/pageAdd'),'type'=>'1,3'); //子公司添加
        
//         $menus[6][3][0] = array('name' => L('MENU5_3'), 'url' => '#','type'=>'1,2,3');
//         $menus[6][3][10] = array('name' => L('MENU5_3_10'), 'url' => U('Admin//'),'type'=>'1,2,3'); //设置虚拟库存
        $menus[6][4][0] = array('name' => L('MENU5_4'), 'url' => '#','type'=>'1,3');
        $menus[6][4][10] = array('name' => L('MENU5_4_10'), 'url' => U('Admin/Distirbution/taobaoIndex'),'type'=>'1,3'); //店铺绑定
        $menus[6][4][20] = array('name' => L('MENU5_4_20'), 'url' => U('Admin/Distirbution/taobaoSetSynRules'),'type'=>'1,3'); //下载淘宝商品
        //$menus[6][4][30] = array('name' => L('MENU5_4_30'), 'url' => U('Admin/Distirbution/taobaoCount'),'type'=>'1,2,3'); //会员铺货统计
        //$menus[6][4][40] = array('name' => L('MENU5_4_40'), 'url' => U('Admin/Distirbution/taobaoUploadset'),'type'=>'1,2,3'); //铺货设置
        $menus[6][4][50] = array('name' => L('MENU5_4_50'), 'url' => U('Admin/Distirbution/deliveryTemplateList'),'type'=>'1,3'); //物流模板展示
        $menus[6][4][60] = array('name' => L('MENU5_4_60'), 'url' => U('Admin/Distirbution/taobaoSet'),'type'=>'1,3'); //淘宝铺货设置
        //本期海信上线先注释掉
        $menus[6][5][0] = array('name' => L('MENU5_5'), 'url' => '#','type'=>'1,3');
        $menus[6][5][10] = array('name' => L('MENU5_5_10'), 'url' => U('Admin/Package/pageList'),'type'=>'1,3'); //数据包列表
        $menus[6][5][20] = array('name' => L('MENU5_5_20'), 'url' => U('Admin/Package/pageAdd'),'type'=>'1,3'); //发布数据包
		
        //淘宝供销平台菜单
		/* 本次发版先注释掉，发版之前请勿开启
        $menus[6][6][0] = array('name' => L('MENU6_6'), 'url' => '#');
        $menus[6][6][10] = array('name' => L('MENU6_6_10'), 'url' => U('Admin/Fenxiao/bindOauth')); //绑定授权
        $menus[6][6][20] = array('name' => L('MENU6_6_20'), 'url' => U('Admin/Fenxiao/downloadData')); //数据下载
        $menus[6][6][30] = array('name' => L('MENU6_6_30'), 'url' => U('Admin/Fenxiao/fenxiaoCount')); //铺货统计
        $menus[6][6][40] = array('name' => L('MENU6_6_40'), 'url' => U('Admin/Fenxiao/distributorManger')); //分（代）销商管理
        $menus[6][6][50] = array('name' => '采购单列表', 'url' => U('Admin/Fenxiao/pageOrder')); //采购单列表
        $menus[6][6][60] = array('name' => '乱价窜货查询', 'url' => U('Admin/Fenxiao/pagePriceDaixiao')); //默认代销乱价列表
        $menus[6][6][70] = array('name' => '后端商品管理', 'url' => U('Admin/Fenxiao/pageGoodsList')); //默认代销乱价列表
        */
        //$menus[6][6][0] = array('name' => L('MENU5_6'), 'url' => '#','type'=>'1,2,3');
		//$menus[6][6][30] = array('name' => L('MENU5_6_30'), 'url' => U('Admin/Promoting/payBack'),'type'=>'1,2,3'); //返利列表
 		
		$menus[6][7][0] = array('name' => L('推广销售管理'), 'url' => U('Admin/Salespromotion/index'),'type'=>'1,2,3');
		$menus[6][7][10] = array('name' => L('分销商引荐管理'), 'url' => U('Admin/Salespromotion/index'),'type'=>'1,2,3'); 
		$menus[6][7][20] = array('name' => L('销售额设定'), 'url' => U('Admin/Salespromotion/showSalesSetList'),'type'=>'1,2,3'); 
		//$menus[6][7][30] = array('name' => L('批量设置商品返利'), 'url' => U('Admin/Promotings/bathConfigGoodsRebatesCheckMember'),'type'=>'1,2,3'); 
		$menus[6][7][50] = array('name' => L('商品返利设定'), 'url' => U('Admin/Promotings/index'),'type'=>'1,2,3'); 
		$menus[6][7][60] = array('name' => L('返利报表'), 'url' => U('Admin/Promotings/PBStatements'),'type'=>'1,2,3'); 
        //结余款管理
        $menus[7][4][0] = array('name' => L('MENU6_4'), 'url' => '#','type'=>'1,2,3');
        $menus[7][4][10] = array('name' => L('MENU6_4_10'), 'url' => U('Admin/BalanceInfo/index'),'type'=>'1,2,3'); //结余款调整单列表
        $menus[7][4][20] = array('name' => L('MENU6_4_20'), 'url' => U('Admin/BalanceInfo/addBalanceInfo'),'type'=>'1,2,3'); //新增结余款调整单
        $menus[7][4][30] = array('name' => '待客审结余款调整单列表', 'url' => U('Admin/BalanceInfo/pageList',"st=pending&status=2"),'type'=>'1,2,3');//待客审结余款调整单列表
        $menus[7][4][40] = array('name' => '待财审结余款调整单列表', 'url' => U('Admin/BalanceInfo/pageList',"st=finance&status=2"),'type'=>'1,2,3');//待财审结余款调整单列表
		//$menus[7][4][50] = array('name' => '财务设置', 'url' => U('Admin/BalanceInfo/pageSet'));//待财审结余款调整单列表
        $menus[7][4][60] = array('name' => L('MENU6_0_30'), 'url' => U('Admin/Financial/pageListVerify'),'type'=>'1,2,3'); //线下充值审核
        
        //结余款类型管理
        $menus[7][5][0] = array('name' => L('MENU6_5'), 'url' => '#','type'=>'1,2,3');
        $menus[7][5][10] = array('name' => L('MENU6_5_10'), 'url' => U('Admin/BalanceType/index'),'type'=>'1,2,3'); //结余款类型列表
        
        
        //销货收款单管理
        $menus[7][6][0] = array('name' => L('MENU8_6'), 'url' => '#','type'=>'1,2,3');
        $menus[7][6][10] = array('name' => L('MENU8_6_10'), 'url' => U('Admin/Voucher/pageList'),'type'=>'1,2,3'); //销货收款单列表
        $menus[7][6][20] = array('name' => L('MENU8_6_20'), 'url' => U('Admin/Voucher/addVoucher'),'type'=>'1,2,3'); //新增销货收款单
        
        //预存款管理 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //$menus[7][0][0] = array('name' => L('MENU6_0'), 'url' => '#');
        //$menus[7][0][10] = array('name' => L('MENU6_0_10'), 'url' => U('Admin/Financial/pageListDeposits')); //预存款充值/扣款
        //$menus[7][0][20] = array('name' => L('MENU6_0_20'), 'url' => U('Admin/Financial/pageListDetails')); //预存款流水明细
        //$menus[7][0][30] = array('name' => L('MENU6_0_30'), 'url' => U('Admin/Financial/pageListVerify')); //线下充值审核
        
        
        
        
        $menus[7][1][0] = array('name' => L('MENU6_1'), 'url' => U('Admin/Financial/index'),'type'=>'1,2,3');
        $menus[7][1][10] = array('name' => L('MENU6_1_10'), 'url' => U('Admin/Financial/pageListOnline'),'type'=>'1,2,3'); //线上支付设置
        $menus[7][1][20] = array('name' => L('MENU6_1_20'), 'url' => U('Admin/Financial/pageListOffline'),'type'=>'1,2,3'); //线下支付设置
        //充值卡管理
        $menus[7][7][0] = array('name'=>'充值卡管理','url'=>U('Admin/PrepaidCard/index'),'type'=>'1,2,3');
        $menus[7][7][10] = array('name'=>'充值卡列表','url'=>U('Admin/PrepaidCard/pageList'),'type'=>'1,2,3');//充值卡列表
        $menus[7][7][20] = array('name'=>'充值卡添加','url'=>U('Admin/PrepaidCard/pageAdd'),'type'=>'1,2,3');//充值卡添加
        $menus[7][7][30] = array('name'=>'批量新增充值卡','url'=>U('Admin/PrepaidCard/pageAuto'),'type'=>'1,2,3');//批量添加充值卡
        $menus[7][7][40] = array('name'=>'充值卡配置','url'=>U('Admin/PrepaidCard/pageSet'),'type'=>'1,2,3');//充值卡配置
        //红包管理 Start
        $menus[7][8][0] = array('name' => L('MENU7_8'), 'url' => '#','type'=>'1,2,3');
        $menus[7][8][10] = array('name' => L('MENU7_8_10'), 'url' => U('Admin/BonusInfo/index'),'type'=>'1,2,3');
        $menus[7][8][20] = array('name' => L('MENU7_8_20'), 'url' => U('Admin/BonusInfo/addBonusInfo'),'type'=>'1,2,3');
        $menus[7][8][30] = array('name' => L('MENU7_8_30'), 'url' => U('Admin/BonusInfo/pageList',"st=pending&status=2"),'type'=>'1,2,3');
        $menus[7][8][40] = array('name' => L('MENU7_8_40'), 'url' => U('Admin/BonusInfo/pageList',"st=finance&status=2"),'type'=>'1,2,3');
        $menus[7][8][50] = array('name' => L('MENU7_8_50'), 'url' => U('Admin/BonusInfo/pageSet'),'type'=>'1,2,3');
        //红包管理 End
        //储值卡管理 Start
		/**
        $menus[7][9][0] = array('name' => L('MENU7_9'), 'url' => '#','type'=>'1,2,3');
        $menus[7][9][10] = array('name' => L('MENU7_9_10'), 'url' => U('Admin/CardsInfo/index'),'type'=>'1,2,3');
        //$menus[7][9][20] = array('name' => L('MENU7_9_20'), 'url' => U('Admin/CardsInfo/addCardsInfo'),'type'=>'1,2,3');
        $menus[7][9][30] = array('name' => L('MENU7_9_30'), 'url' => U('Admin/CardsInfo/pageList',"st=pending&status=2"),'type'=>'1,2,3');
        $menus[7][9][40] = array('name' => L('MENU7_9_40'), 'url' => U('Admin/CardsInfo/pageList',"st=finance&status=2"),'type'=>'1,2,3');
        $menus[7][9][50] = array('name' => L('MENU7_9_50'), 'url' => U('Admin/CardsInfo/pageSet'),'type'=>'1,2,3');
        //储值卡管理 End
        //金币管理 Start
        $menus[7][10][0] = array('name' => L('MENU7_10'), 'url' => '#','type'=>'1,2,3');
        $menus[7][10][10] = array('name' => L('MENU7_10_10'), 'url' => U('Admin/JlbInfo/index'),'type'=>'1,2,3');
        //$menus[7][10][20] = array('name' => L('MENU7_10_20'), 'url' => U('Admin/JlbInfo/addJlbInfo'),'type'=>'1,2,3');
        $menus[7][10][30] = array('name' => L('MENU7_10_30'), 'url' => U('Admin/JlbInfo/pageList',"st=pending&status=2"),'type'=>'1,2,3');
        $menus[7][10][40] = array('name' => L('MENU7_10_40'), 'url' => U('Admin/JlbInfo/pageList',"st=finance&status=2"),'type'=>'1,2,3');
        $menus[7][10][50] = array('name' => L('MENU7_10_50'), 'url' => U('Admin/JlbInfo/pageSet'),'type'=>'1,2,3');
        //金币管理 End
		**/
        //基础设置 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $menus[8][0][0] = array('name' => L('MENU7_0'), 'url' => '#','type'=>'1,2,3');
        $menus[8][0][10] = array('name' => L('MENU7_0_10'), 'url' => U('Admin/System/index'),'type'=>'1,2,3'); //管理员列表
        $menus[8][0][20] = array('name' => L('MENU7_0_20'), 'url' => U('Admin/System/pageAdd'),'type'=>'1,2,3'); //添加管理员帐号
        //$menus[8][0][30] = array('name' => L('MENU7_0_30'), 'url' => U('Admin//')); //管理角色列表
        //$menus[8][0][40] = array('name' => L('MENU7_0_40'), 'url' => U('Admin//')); //添加角色
        $menus[8][0][50] = array('name' => L('MENU7_0_50'), 'url' => U('Admin/System/pageAdminLog'),'type'=>'1,2,3'); //管理员登录日志
        $menus[8][0][70] = array('name' => '管理员登录提示', 'url' => U('Admin/System/pageAdminLoginSet'),'type'=>'1,2,3'); //管理员登录提示

        $menus[8][1][0] = array('name' => L('MENU7_1'), 'url' => U('Admin/Images/index'),'type'=>'1,2,3');
        $menus[8][1][10] = array('name' => L('MENU7_1_10'), 'url' => U('Admin/Images/pageList'),'type'=>'1,2,3'); //图片列表
        //$menus[8][1][20] = array('name' => L('MENU7_1_20'), 'url' => U('Admin/Images/pageAdd')); //图片添加
        $menus[8][1][30] = array('name' => L('MENU7_1_30'), 'url' => U('Admin/Images/pageSet'),'type'=>'1,2,3'); //水印设置
        
        //$menus[8][2][30] = array('name' => L('MENU7_2_30'), 'url' => U('Admin/Email/pageSend')); //邮件向导
        //$menus[8][2][40] = array('name' => L('MENU7_2_40'), 'url' => U('Admin/Email/pageStatus')); //发送状态查询

        $menus[8][4][0] = array('name' => L('MENU7_4_0'), 'url' => '#','type'=>'1,2,3'); //权限组管理
        $menus[8][4][10] = array('name' => L('MENU7_4_10'), 'url' => U('Admin/Role/index'),'type'=>'1,2,3'); //角色列表
        $menus[8][4][20] = array('name' => L('MENU7_4_20'), 'url' => U('Admin/Role/pageAdd'),'type'=>'1,2,3'); //添加角色

        $menus[8][5][0] = array('name' => L('MENU7_5_0'), 'url' => U('#'),'type'=>'1,2,3'); //权限节点管理
        $menus[8][5][10] = array('name' => L('MENU7_5_10'), 'url' => U('Admin/RoleNode/index'),'type'=>'1,2,3'); //节点列表
        $menus[8][5][20] = array('name' => L('MENU7_5_20'), 'url' => U('Admin/RoleNode/pageAdd'),'type'=>'1,2,3'); //添加节点

        $menus[8][6][0] = array('name' => L('MENU7_6'), 'url' => U('Admin/Point/index'),'type'=>'1,2,3'); //积分设置
        $menus[8][6][10] = array('name' => L('MENU7_6_10'), 'url' => U('Admin/Point/pageSet'),'type'=>'1,2,3'); //积分设置
		/**
        $menus[8][6][20] = array('name' => L('MENU7_6_20'), 'url' => U('Admin/Pointslevel/index'),'type'=>'1,2,3'); //积分等级列表
        $menus[8][6][30] = array('name' => L('MENU7_6_30'), 'url' => U('Admin/Pointslevel/pageAdd'),'type'=>'1,2,3'); //新增积分等级
        $menus[8][6][40] = array('name' => L('MENU7_6_40'), 'url' => U('Admin/Point/pageList'),'type'=>'1,2,3'); //九龙港项目金豆赠送活动列表
        **/
        $menus[8][7][0] = array('name' => L('MENU7_7'), 'url' => U('Admin/Api/index'),'type'=>'1,3'); //分销开放平台设置
        $menus[8][7][10] = array('name' => L('MENU7_7_10'), 'url' => U('Admin/Api/pageSet'),'type'=>'1,3'); //分销开放平台
        //$menus[8][7][20] = array('name' => L('MENU7_7_20'), 'url' => U('Admin/Api/yunerppageSet'),'type'=>'1,3'); //云ERP开放平台
	   
		$menus[8][8][0] = array('name' => "系统菜单管理", 'url' => U('Admin/Menus/index'),'type'=>'1,2,3'); //分销开放平台设置
        $menus[8][8][10] = array('name' => "后台菜单", 'url' => U('Admin/Menus/index'),'type'=>'1,2,3'); //分销开放平台设置
        $menus[8][8][20] = array('name' => "会员中心菜单", 'url' => U('Admin/Menus/getUcenterMenus'),'type'=>'1,2,3'); //分销开放平台设置

	    $menus[8][9][0] = array('name' => "日志操作记录", 'url' => '#','type'=>'1,2,3');
        $menus[8][9][10] = array('name' => "后台操作记录列表", 'url' => U('Admin/Operation/pageList'),'type'=>'1,2,3'); //后台操作记录列表
 		       	
	   // echo "<pre>";print_r($menus);exit;
        $menu = M('menus',C('DB_PREFIX'),'DB_CUSTOM');
        foreach($this->tops as $key_t=>$val_v){
            $top_where['name'] = $val_v['name'];
            $top_where['group'] = 'Admin';
            $top_where['toporder'] = $key_t;
            $top_where['mstatus'] = '1';
            $top_where['url'] = $val_v['url'];
            $top_where['fid'] = '0';
            $top_where['sn'] = $val_v['sn'];
            $top_where['type'] = $val_v['type'];
            $top_menus_area = $menu->where($top_where)->find();
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
                            $second_where['group'] = 'Admin';
                            $second_where['suborder'] = $key_menus_second;
                            $second_where['mstatus'] = '1';
                            $second_where['url'] = $kv['url'];
                            $second_where['fid'] = $top_menus_area['id'];
                            $second_where['sn'] = $sn;
                            $second_where['type'] = $kv['type'];
                            $second_menus_area = $menu->where(array('sn'=>$sn))->find();
                            if($kt == 0 && empty($second_menus_area)){
                                $second_id = $menu->add($second_where);
                                $second_menus_area = $menu->where(array('id'=>$second_id))->find();
                            }else{
                                if($kt != 0){
                                    $last_menus_sn = 'MENU'.$key_menus_top.'_'.$key_menus_second.'_0';
                                    $last_menus = $menu->where(array('sn'=>$last_menus_sn))->find();
                                    unset($second_where['suborder']);
                                    $second_where['fid'] = $last_menus['id'];
                                    $second_where['threeorder'] = $kt;
                                    $third_menus = $menu->where($second_where)->find();
                                    if(empty($third_menus)){
                                        $second_id = $menu->add($second_where);
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
        $where['group'] = 'Admin';
        $where['mstatus'] = '1';
        $where['fid'] = '0';
        $data = array();
        $ary_menu = $menu->where($where)->order('toporder asc')->select();
        //echo "<pre>";print_r($ary_menu);exit;
        if(!empty($ary_menu) && is_array($ary_menu)){
            foreach($ary_menu as $keymu=>$valmu){
                $arr_menu = $menu->where(array('group'=>'Admin','mstatus'=>'1','fid'=>$valmu['id']))->select();
                if(!empty($arr_menu)){
                    foreach($arr_menu as $kymu=>$vlmu){
                        $ary_types = explode(',',$vlmu['type']);
                        if(in_array(C('CUSTOMER_TYPE'),$ary_types)){
                            $data[$valmu['toporder'] + 1][$vlmu['suborder']][$valmu['suborder']] = array('name'=>$vlmu['name'],'url'=>$vlmu['url']);
                            $arr_menus = $menu->where(array('group'=>'Admin','mstatus'=>'1','fid'=>$vlmu['id']))->select();
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
                $ary_nav = $menu->where(array('sn'=>'NAV1_'.($keym-1),'mstatus'=>'1','group'=>'Admin'))->find();
                $ary_data = array();
                $ary_data['name'] = $this->tops[$keym-1]['name'];
                $ary_data['group'] = "Admin";
                $ary_data['toporder'] = ($keym-1);
                $ary_data['fid'] = '0';
                $ary_data['sn'] = 'NAV1_'.($keym-1);
                $ary_data['url'] = $this->tops[$keym-1]['url'];
                if(isset($this->tops[$keym-1]['status'])){
                    $ary_data['mstatus'] = $this->tops[$keym-1]['status'];
                }
                if(!empty($ary_nav) && is_array($ary_nav)){
                    $ary_result = $menu->where(array('id'=>$ary_nav['id']))->data($ary_data)->save();
                    $ary_result = $ary_nav['id'];
                }else{
                    $ary_result = $menu->add($ary_data);
                }
//                echo $ary_result;
                if(FALSE !== $ary_result){
                    if(!empty($valm) && is_array($valm)){
                        foreach($valm as $kym=>$vlm){
                            $arr_nav = $menu->where(array('sn'=>'MENU'.($keym-1).'_'.($kym),'group'=>'Admin'))->find();
                            $arr_data = array();
                            $arr_data['name'] = $vlm[0]['name'];
                            $arr_data['group'] = "Admin";
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
                                $ary_res = $menu->where(array('id'=>$arr_nav['id']))->data($arr_data)->save();
                                $ary_res = $arr_nav['id'];
                            }else{
                                $ary_res = $menu->add($arr_data);
                            }
//                            echo "------------------------此处分割-----------------------";
                            if(FALSE !== $ary_res){
                                unset($vlm[0]);
                                foreach($vlm as $km=>$vm){
                                    $nav = $menu->where(array('sn'=>'MENU'.($keym-1).'_'.($kym).'_'.$km,'group'=>'Admin'))->find();
                                    $data = array();
                                    $data['name'] = $vm['name'];
                                    $data['group'] = "Admin";
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
                                        $res = $menu->where(array('id'=>$nav['id']))->data($data)->save();
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
