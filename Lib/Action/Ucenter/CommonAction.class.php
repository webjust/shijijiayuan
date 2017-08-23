<?php

/**
 * 前台用户中心基类
 * 前台除注册、登录等无需判断权限的控制器，其他控制器均需要继承此类
 *
 * @stage 7.0
 * @package Action
 * @subpackage Ucenter
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-07
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class CommonAction extends GyfxAction {

    /**
     * 当前用户信息
     * @var array
     */
    protected $ary_member;
    protected $connect_ecerp_switch;

    /**
     * 初始化操作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-07
     */
    public function _initialize() {
        $this->getTitle();
        $this->getTop();
        $this->getMenus();
        $this->getFooter();
        $this->getOnlineService();
        import('ORG.Util.Page');
		$array_config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        if (is_array($array_config) && !empty($array_config)) {
            define('TPL', $array_config['GY_TEMPLATE_DEFAULT']);
            $_SESSION['NOW_TPL'] = $array_config['GY_TEMPLATE_DEFAULT'];
        } else {
            define('TPL', 'default');
            $_SESSION['NOW_TPL'] = 'default';
        }
       
        $this->dir = TPL;
       
        $config = array(
            'tpl' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/',
            'js' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/js/',
            'images' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/images/', // 客户模版images路径替换规则
            'css' => '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/css/', // 客户模版css路径替换规则
        );
        C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
        C('TMPL_PARSE_STRING.__JS__', $config['js']);
        C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
        C('TMPL_PARSE_STRING.__CSS__', $config['css']);
        
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);

        if($ary_api_conf['SWITCH']['sc_value'] == 1){
            $this->connect_ecerp_switch = true;
        }else{
            $this->connect_ecerp_switch = false;
        }
        
        ############################## edit by  hcaijin 07/10/14 把会员中心头部，底部MV到模板根目录，以便可以后台模板编辑。兼容其他客户没有拉出到模板根目录的问题。##############################
        //头部自定义加载 
		$str_custom_header_base = FXINC . '/Public/Tpl/' . CI_SN . '/' . $_SESSION['NOW_TPL'] . '/ucenterHeader.html';
		$str_custom_header = FXINC . '/Public/Tpl/' . CI_SN . '/' . $_SESSION['NOW_TPL'] . '/custom/ucenterHeader.html';
		$str_header_include_file = "Common:incUcenterHeader";
		if(file_exists($str_custom_header)){
			$str_header_include_file = $str_custom_header;
        }elseif(file_exists($str_custom_header_base)){
			$str_header_include_file = $str_custom_header_base;
        }
		$this->assign("str_header_include_file",$str_header_include_file);
		
		//底部自定义加载
		$str_custom_footer_base = FXINC . '/Public/Tpl/' . CI_SN . '/' . $_SESSION['NOW_TPL'] . '/ucenterFooter.html';
		$str_custom_footer = FXINC . '/Public/Tpl/' . CI_SN . '/' . $_SESSION['NOW_TPL'] . '/custom/ucenterFooter.html';
		$srt_inc_footer = "Common:incFooter";
		if(file_exists($str_custom_footer)){
			$srt_inc_footer = $str_custom_footer;
        }elseif(file_exists($str_custom_footer_base)){
			$srt_inc_footer = $str_custom_footer_base;
        }
        ############################## edit by  hcaijin 07/10/14 把会员中心头部，底部放到模板根目录，以便可以后台模板编辑。##############################
		$this->assign("srt_inc_footer",$srt_inc_footer);
        $pageCount = D('Gyfx')->selectOneCache('site_config','sc_value',array('sc_module' => 'GY_COUNT'));
		//M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($pageCount){
            $pageCount = base64_decode($pageCount['sc_memo']);
        }
        $this->assign('pageCount',$pageCount);
        
        //登录验证调整到这里，解决会员中心提示页头尾不一样的BUG
        $arr_get = $this->_get();
        if($arr_get['_URL_']['2'] != 'pageRead' && $arr_get['_URL_']['1'] != 'Notice'){
            $this->doCheckLogin();
        }
		//获取顶部广告信息
		//$ary_top_ads = $this->getTopAds();	
		//$this->assign("ary_top_ads",$ary_top_ads);
        // 会员中心皮肤设置
        // $array_ucenter_skin = D("SysConfig")->where(array("sc_module" => 'GY_UCENTER_SKIN'))->limit(0,3)->select();
        $array_ucenter_skin = D("SysConfig")->getCfgByModule('GY_UCENTER_SKIN',1);
		$array_ucenter_skin['ICON_PIC'] = D('QnPic')->picToQn($array_ucenter_skin['ICON_PIC']);
		$array_ucenter_skin['LEFT_PIC'] = D('QnPic')->picToQn($array_ucenter_skin['LEFT_PIC']);
		$array_ucenter_skin['NAVON_PIC'] = D('QnPic')->picToQn($array_ucenter_skin['NAVON_PIC']);		
        $this->assign('ucenter_skin',$array_ucenter_skin);

        

        //2017.3.2 lin
        $Cart = D('Cart');
        $ary_member = session("Members");
        if (!empty($ary_member['m_id'])) {
            //获取购物车信息
            $tmp_cart_data = $Cart->ReadMycart();
            //处理购物车信息
            $cart_data = $Cart->handleCart($tmp_cart_data);
            //获取促销后优惠信息
            $pro_datas = D('Promotion')->calShopCartPro($ary_member['m_id'], $cart_data,1);
            $subtotal = $pro_datas['subtotal']; //促销金额
            //剔除商品价格信息
            unset($pro_datas['subtotal']);
            //获取商品详细信息
            if (is_array($cart_data) && !empty($cart_data)) {
                $ary_cart_data = $Cart->getProductInfo($cart_data,$ary_member['m_id'],1);
            }
            //处理获取的商品信息
            $ary_cart = $Cart->handleCartProductsAuthorize($ary_cart_data,$ary_member['m_id']);
            //处理通过促销获取的优惠信息
            $tmp_pro_datas = $Cart->handleProdatas($pro_datas,$ary_cart);
//            dump($tmp_pro_datas);die;
            //处理pro_datas信息
            $pro_datas = $tmp_pro_datas['pro_datas'];
            //获取促销信息
            $pro_data = $tmp_pro_datas['pro_data'];
            //获取赠品信息
            $cart_gifts_data = $tmp_pro_datas['cart_gifts_data'];
            //获取订单总金额
            $ary_price_data = $Cart->getPriceData($tmp_pro_datas,$subtotal);
            unset($tmp_pro_datas);

            $this->assign("price_data", $ary_price_data);
        }

    }

    ### 保护方法 ###############################################################
    /**
     * 设置控制器在用户中心页面中的1级2级3级菜单哪些被选中
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-11
     * @param int $nav1 一级导航菜单位置
     * @param int $nav2 二级导航菜单位置
     * @param int $nav3 三级导航菜单位置
     */

    protected function getSubNav($nav1 = 0, $nav2 = 0, $nav3 = 0) {
        $this->assign('nav1', $nav1);
        $this->assign('nav2', $nav2);
        $this->assign('nav3', $nav3);
    }

    /**
     * 设置每个页面自己的SEO信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param string $page_title
     * @param string $page_keywords
     * @param string $page_desc
     */
    protected function getSeoInfo($page_title = '', $page_keywords = '', $page_desc = '') {
        $this->assign('page_title', $page_title);
        $this->assign('page_keywords', $page_keywords);
        $this->assign('page_desc', $page_desc);
    }

### 私有方法 ###############################################################

    /**
     * 判断是否登录
     * @author zuo <zuojianghua@guanyisoft>
     * @date 2012-12-07
     */
    private function doCheckLogin() {
		$is_close = D('SysConfig')->getCfgByModule('GY_WEB_CONFIG',1);
        if($is_close['STATUS'] == '1'){
        	//暂停营业的开关
			header("Content-Type:text/html;charset=utf-8;");
            echo $is_close['CONTENT'];
            exit;
        }
		
		/**
		$SESSION_TYPE = (ini_get('session.save_handler') == 'redis')?1:0;
		if(empty($SESSION_TYPE)){
			$session_mid = cookie('session_mid');
			if(!empty($session_mid)){
				$ary_member = getCache($session_mid);
				if(!empty($ary_member)){
					session('Members',$ary_member );
				}	
				
				if(!session('?Members')){
					$ary_member = getCache($session_mid);
					if(!empty($ary_member)){
						session('Members',$ary_member );
					}
				}
				
			}else{
				session('Members',null);
			}		
		}**/
        if (!session('?Members')) {
			//未登录用户引导到登录页面
			$int_port = "";
			if($_SERVER["SERVER_PORT"] != 80){
				$int_port = ':' . $_SERVER["SERVER_PORT"];
			}
			$string_request_uri = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
			//立刻购买URL拼接
			$post_data = $_REQUEST;
			if(!empty($post_data['pid'][0])){
				$string_request_uri .='?pid='.$post_data['pid'][0];
			}
			//$this->error(L('NO_LOGIN'), U('Ucenter/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri));
			$is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
			if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
                redirect(U('Ucenter/User/pageLogin'));
				//$this->error(L('NO_LOGIN'), array('确认' => U('Ucenter/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri)));
			} else {
				//redirect(U('Home/Index/index'));
                redirect(U('Home/User/Login'));
				//$this->error(L('NO_LOGIN'), array('确认' => U('Home/User/Login') . '?redirect_uri=' . urlencode($string_request_uri)));
			}
            //已登录用户将session放入到私有属性
            $this->ary_member = session('Members');
        }
    }

    /**
     * 获取页头meta基本数据
     * @author zuo <zuojianghua@gmail.com>
     * @date 2012-12-11
     */
    private function getTitle() {
        $ary_seo = D('SysConfig')->getCfgByModule('GY_SEO',1);

        $ary_data['common_title'] = empty($ary_seo['TITLE_INDEX']) ? '账户中心' : $ary_seo['TITLE_INDEX'] . '账户中心';
        $ary_data['common_keywords'] = $ary_seo['KEY_INDEX'];
        $ary_data['common_desc'] = $ary_seo['DESC_INDEX'];
		$info = D('SysConfig')->getCfgByModule('GY_SHOP',1);
		if(!empty($info['GY_SHOP_TITLE'])){
			$ary_data['site_name'] = $info['GY_SHOP_TITLE'];
		}else{
			$ary_data['site_name'] = $ary_seo['SITE_NAME'];
		}
        $this->assign($ary_data);
    }

//    /**
//     * 获取当前登录用户客服信息
//     * @author czy<chenzongyao@guanyisoft.com>
//     * @date 2013-3-19
//     */
//     private function getOnlineService() {
//        if(!empty($this->ary_member) && isset($this->ary_member['m_id']) && !empty($this->ary_member['m_id'])){
//            //业务员id
//            $mo_id = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$this->ary_member['m_id']))->getField('mo_id');
//            if($mo_id && !empty($mo_id)){
//                $obj_online = M('online_service',C('DB_PREFIX'),'DB_CUSTOM');
//                $ary_online = $obj_online->where(array('o_id'=>$mo_id,'o_status'=>1))->select();
//                if(is_array($ary_online) && count($ary_online)>0) $this->assign('ary_online',$ary_online);
//            }
//        }
//    }

    /**
     * 获取当前登录用户客服信息
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-12-23
     */
    private function getOnlineService() {
        //$ary_online = M('online_cat',C('DB_PREFIX'),'DB_CUSTOM')->select();
		$ary_online = D('Gyfx')->selectAllCache('online_cat');
        foreach ($ary_online as &$cat){
            //$cat['server'] = M('online_service',C('DB_PREFIX'),'DB_CUSTOM')->where(array('oc_parent_id'=>$cat['oc_id'],'o_status'=>1))->select();
			$cat['server'] = D('Gyfx')->selectAllCache('online_service','',array('oc_parent_id'=>$cat['oc_id'],'o_status'=>1),'o_order');
        }
		$ary_online_set = D('SysConfig')->getCfgByModule('GY_SHOP',1);
		$this->assign('online_start_time',$ary_online_set['GY_SHOP_ONLINE_START']);
		$this->assign('online_end_time',$ary_online_set['GY_SHOP_ONLINE_END']);
        if(is_array($ary_online) && count($ary_online)>0) $this->assign('ary_online',$ary_online);
    }
    /**
     * 获取顶部导航信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    private function getTop() {
        $tops = array();
        $member = session('Members');
        //print_r($member);
        if($member['is_supplier']){
            $tops[3] = array('name' => '供应商中心', 'class' => 'js', 'url' => U('Ucenter/My/index'),'type'=>'1,2,3'); //个人中心
        }
        elseif($member['is_warehouse']){
            $tops[3] = array('name' => '仓管中心', 'class' => 'js', 'url' => U('Ucenter/My/index'),'type'=>'1,2,3'); //个人中心
        }
        else{
            $tops[0] = array('name' => '选购中心', 'class' => 'xg', 'url' => U('Ucenter/Products/index'),'type'=>'1,2,3'); //选购中心
            $tops[1] = array('name' => '第三方平台', 'class' => 'dd', 'url' => U('Ucenter/Trdorders/index'),'type'=>'1,3'); //第三方平台
            $tops[2] = array('name' => '推广销售', 'class' => 'dp', 'url' => U('Ucenter/Promoting/index'),'type'=>'1,2,3'); //推广销售
            $tops[3] = array('name' => '个人中心', 'class' => 'js', 'url' => U('Ucenter/My/index'),'type'=>'1,2,3'); //个人中心
            $tops[4] = array('name' => '站点公告', 'class' => 'fx', 'url' => U('Ucenter/Notice/index'),'type'=>'1,2,3'); //站点公告
            //$tops[5] = array('name' => '商品管理', 'class' => 'fx', 'url' => U('Ucenter/Notice/index'),'type'=>'1,2,3'); //站点公告            
        }

        $this->tops = $tops;
        $this->assign('tops', $tops);
    }

    /**
     * 获取左侧菜单信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-07
     */
    private function getMenus() {
		$member = session('Members');
        $menus = array();
        if($member['admin_id']){
            $menus[0][30] = array('name' => '购物车', 'url' => U('Ucenter/Cart/pageList'),'type'=>'1,2,3'); //购物车
            $menus[0][40] = array('name' => '订单列表', 'url' => U('Ucenter/Orders/pageList'),'type'=>'1,2,3'); //订单列表
            $menus[0][60] = array('name' => '收藏列表', 'url' => U('Ucenter/Collect/pageList'),'type'=>'1,2,3'); //收藏列表
        }else{
            //为避免以后小菜单增加或修改导致需要修改控制器菜单位置的赋值
            //此处下标使用10/20/30/40这样的来定义，以后可以补9/11/12/13等等等
            //选购中心 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $menus[0][10] = array('name' => '快速订货', 'url' => U('Ucenter/Products/pageList'),'type'=>'1,3'); //快速订货
            $menus[0][20] = array('name' => L('MENU0_20'), 'url' => U('Ucenter/Products/pageQuick'),'type'=>'1,3'); //商品编号下单
            $menus[0][30] = array('name' => '购物车', 'url' => U('Ucenter/Cart/pageList'),'type'=>'1,2,3'); //购物车
            $menus[0][40] = array('name' => '订单列表', 'url' => U('Ucenter/Orders/pageList'),'type'=>'1,2,3'); //订单列表
            $menus[0][50] = array('name' => '售后列表', 'url' => U('Ucenter/Aftersale/pageList'),'type'=>'1,2,3'); //售后列表
            $menus[0][60] = array('name' => '收藏列表', 'url' => U('Ucenter/Collect/pageList'),'type'=>'1,2,3'); //收藏列表
			$menus[0][70] = array('name' => '订单导入', 'url' => U('Ucenter/Orders/importOrder'),'type'=>'1,2,3'); //订单导入
            //第三方平台 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

			$menus[1][90] = array('name' => '店铺授权', 'url' => U('Ucenter/Distribution/thdPageShops'),'type'=>'1,3');
            $menus[1][40] = array('name' => '淘宝订单下载', 'url' => U('Ucenter/Trdorders/pageTaobao'),'type'=>'1,3'); //淘宝订单下载
            $menus[1][100] = array('name' => '淘宝商品铺货', 'url' => U('Ucenter/Distribution/showGoodsList'),'type'=>'1,3');
            $menus[1][200] = array('name' => '淘宝库存上传', 'url' => U('Ucenter/UploadStock/showItemsTop'),'type'=>'1,3');			
			$menus[1][42] = array('name' => '京东订单下载', 'url' => U('Ucenter/Trdorders/pageJd'),'type'=>'1,3'); //京东订单下载
			$menus[1][43] = array('name' => '京东库存上传', 'url' => U('Ucenter/UploadStock/showItemsJd'),'type'=>'1,3');
            $menus[1][45] = array('name' => '拍拍订单下载', 'url' => U('Ucenter/Trdorders/pagePaipai'),'type'=>'1,3'); //拍拍订单下载*/
            $menus[1][50] = array('name' => '一键发货', 'url' => U('Ucenter/Trddeliver/pageList'),'type'=>'1,3'); //一键发货
            $menus[1][60] = array('name' => L('MENU1_60'), 'url' => U('Ucenter/Package/pageList'),'type'=>'1,3'); //数据包下载
			
            //$menus[1][10] = array('name' => L('MENU1_10'), 'url' => U('Ucenter/Distribution/pageShops'),'type'=>'1,2,3'); //店铺授权
            //$menus[1][20] = array('name' => L('MENU1_20'), 'url' => U('Ucenter/Distribution/pageList'),'type'=>'1,2,3'); //淘宝铺货
            //$menus[1][30] = array('name' => L('MENU1_30'), 'url' => U('Ucenter/Distribution/pageUpdate'),'type'=>'1,2,3'); //淘宝库存同步			
            //$menus[1][70] = array('name' => L('MENU1_70'), 'url' => U('Ucenter/Trdorders/thdOrderList')); //第三方平台订单下
            //$menus[1][80] = array('name' => '店铺授权' ,'url' => U('Ucenter/Trdorders/yunerpShop'),'type'=>'1,3'); //第三方平台订单下
            //$menus[1][100] = array('name' => L('MENU1_100'), 'url' => U('Ucenter/Distribution/showGoodsList'),'type'=>'1,2,3');
            //推广销售 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $menus[2][10] = array('name' => '我要推广', 'url' => U('Ucenter/Promoting/userSpread'),'type'=>'1,2,3'); //我要推广
            $menus[2][20] = array('name' => '成为合伙人', 'url' => U('Ucenter/Promoting/partnerIntro'),'type'=>'1,2,3');
            //$menus[2][20] = array('name' => '我的返利', 'url' => U('Ucenter/Promoting/payBack'),'type'=>'1,2,3'); //我的返利
            $menus[2][30] = array('name' => '我的返利', 'url' => U('Ucenter/Promoting/payBacks'),'type'=>'1,2,3'); //我的返利
            $menus[2][40] = array('name' => '我的分销商', 'url' => U('Ucenter/Promoting/myPromoting'),'type'=>'1,2,3'); // 我的分销商
			//个人中心 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $menus[3][10] = array('name' => '我的资料', 'url' => U('Ucenter/My/pageProfile'),'type'=>'1,2,3'); //我的资料
            $menus[3][20] = array('name' => '修改密码', 'url' => U('Ucenter/My/pageChangePass'),'type'=>'1,2,3'); //修改密码
            $menus[3][40] = array('name' => '收支明细', 'url' => U('Ucenter/Financial/pageDepositList'),'type'=>'1,2,3'); //消费记录
            $menus[3][50] = array('name' => '我的试用申请', 'url' => U('Ucenter/Apply/pageList'),'type'=>'1,2,3'); //试用申请
            $menus[3][60] = array('name' => '我的试用报告', 'url' => U('Ucenter/Report/pageList'),'type'=>'1,2,3'); //试用报告
            //海信暂时关闭在线充值功能，后台做成前台菜单可以按照权限配置的功能
            //$menus[3][40] = array('name' => L('MENU3_40'), 'url' => U('Ucenter/Financial/pageDepositOnline')); //在线充值
            //$menus[3][50] = array('name' => L('MENU3_50'), 'url' => U('Ucenter/Financial/pageDepositOffline')); //线下充值
            //$menus[3][60] = array('name' => L('MENU3_60'), 'url' => U('Ucenter/Financial/pageVerifyDeposit')); //线下充值审核
            $menus[3][70] = array('name' => L('我的收货地址'), 'url' => U('Ucenter/My/pageDeliver '),'type'=>'1,2,3'); //我的收货地址
            $menus[3][80] = array('name' => L('我的优惠券'), 'url' => U('Ucenter/MyCoupon/pageList '),'type'=>'1,2,3'); //我的优惠券
            $menus[3][82] = array('name' => L('我的充值卡'), 'url' => U('Ucenter/MyPrepaidCard/pageList '),'type'=>'1,2,3'); //我的充值卡
            $menus[3][85] = array('name' => L('我的积分'), 'url' => U('Ucenter/PointLog/pageList '),'type'=>'1,2,3'); //我的积分
            $menus[3][86] = array('name' => L('我的红包'), 'url' => U('Ucenter/Bonus/pageList '),'type'=>'1,2,3'); //我的红包
            //$menus[3][87] = array('name' => L('我的储值卡'), 'url' => U('Ucenter/Cards/pageList '),'type'=>'1,2,3'); //我的储值卡
            //$menus[3][88] = array('name' => L('我的金币'), 'url' => U('Ucenter/Jlb/pageList '),'type'=>'1,2,3'); //我的金币
            $menus[3][90] = array('name' => L('我的增值税发票'), 'url' => U('Ucenter/My/pageInvoice '),'type'=>'1,2,3'); //我的增值税发票
            $menus[3][100] = array('name' => L('MENU3_100'), 'url' => U('Ucenter/My/feedBackList '),'type'=>'1,2,3'); //买家留言
            $menus[3][110] = array('name' => L('线下充值列表'), 'url' => U('Ucenter/Financial/pageVerifyDeposit '),'type'=>'1,2,3'); //买家留言
            // if($member['is_supplier']){
            //     $menus[3][120] = array('name' => '发布商品', 'url' => U('Ucenter/My/goodsAdd'),'type'=>'1,2,3'); 
            // }
            // else{
            //     $menus[3][120] = array('name' => '成为供应商', 'url' => U('Ucenter/My/supplier'),'type'=>'1,2,3'); 
            // }
            
            
            //站点公告 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $menus[4][10] = array('name' => L('站点公告'), 'url' => U('Ucenter/Notice/pageList'),'type'=>'1,2,3'); //站点公告
            $menus[4][20] = array('name' => L('站内信'), 'url' => U('Ucenter/Message/pageMailBox'),'type'=>'1,2,3'); //站内信
            $menus[4][30] = array('name' => '违规记录公告', 'url' => U('Ucenter/Announcement/pageList'),'type'=>'1,2,3'); //违规记录下单
       
            //$menus[5][10] = array('name' => L('商品列表'), 'url' => U('Ucenter/Goods/pageList'),'type'=>'1,2,3'); //商品列表
            //$menus[5][20] = array('name' => L('销售订单列表'), 'url' => U('Ucenter/Orders/orderList'),'type'=>'1,2,3'); //销售订单列表
           
       
       }
        $menu = M('menus',C('DB_PREFIX'),'DB_CUSTOM');
        $this->saveMenus($menus);
        //兼容文件/数据库菜单一致
        $where = array();
        if($member['role']==0){
            $where['group'] = 'Ucenter';
            $where['mstatus'] = '1';
            $where['fid'] = '0';
        }
        else{
            if($member['role']==1){
                $where['group'] = 'Purchase';
            }
            elseif($member['role']==2){
                $where['group'] = 'Supplier';
            }
            elseif($member['role']==3){
                $where['group'] = 'Warehouse';
            }
        }
        
        // if($member['is_supplier']){
        //     $where['is_supplier'] = '1';
        // }
        // else{
        //     $where['is_supplier'] = '0';
        // }

        //var_dump($where);
        //$data = array();
        //$ary_menu = $menu->where($where)->select();
		$ary_menu = D('Gyfx')->selectAllCache('menus',$ary_field=null,$where); 
        //echo $member['role'];
        //var_dump($ary_menu);
        //if($member['role']==1){
        //    $data = $ary_menu;
        //}
        if(!empty($ary_menu) && is_array($ary_menu)){

            foreach($ary_menu as $keymu=>$valmu){
                //if($member['m_type'] != 0 && ($valmu['name'] == "第三方平台" || $valmu['name'] == "推广销售")){
                //    continue;
                //}
                if($member['is_supplier']){
                    $condition = array('group'=>'Ucenter','mstatus'=>'1','fid'=>$valmu['id'],'is_supplier'=>'1','is_warehouse'=>'0');
                }
                elseif($member['is_warehouse']){
                    $condition = array('group'=>'Ucenter','mstatus'=>'1','fid'=>$valmu['id'],'is_supplier'=>'0','is_warehouse'=>'1');
                }
                else{
                    $condition = array('group'=>'Ucenter','mstatus'=>'1','fid'=>$valmu['id'],'is_supplier'=>'0','is_warehouse'=>'0');
                }

                if($member['role']==1){
                    $condition = array('group'=>'Purchase');
                }
                elseif($member['role']==2){
                    $condition = array('group'=>'Supplier');
                }
                elseif($member['role']==3){
                    $condition = array('group'=>'Warehouse');
                }
                $arr_menu = D('Gyfx')->selectAllCache('menus',$ary_field=null, $condition, $ary_order='suborder asc',$ary_group=null,$ary_limit=null);
				//$menu->where(array('group'=>'Ucenter','mstatus'=>'1','fid'=>$valmu['id']))->select();

                if(!empty($arr_menu)){
                    foreach($arr_menu as $kymu=>$vlmu){
                        $ary_types = explode(',',$vlmu['type']);
                        if(in_array(C('CUSTOMER_TYPE'),$ary_types)){
                            $data[$valmu['toporder']][$vlmu['suborder']] = array('name'=>$vlmu['name'],'url'=>$vlmu['url']);
                            $arr_menus = D('Gyfx')->selectAllCache('menus',$ary_field=null, $condition, $ary_order=null,$ary_group=null,$ary_limit=null);
							//$menu->where(array('group'=>'Ucenter','mstatus'=>'1','fid'=>$vlmu['id']))->select();
                            if(!empty($arr_menus)){
                                
                                foreach($arr_menus as $ky=>$vl){
                                    $ary_type = explode(',',$vl['type']);
                                    if(in_array(C('CUSTOMER_TYPE'),$ary_type)){
                                        $data[$valmu['toporder']][$kymu][$vl['suborder']] = array('name'=>$vl['name'],'url'=>$vl['url']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //echo $member['role'];
        //print_r($data);
        $this->assign('menus', $data);
    }

    /**
     * 获取页脚信息
     * @author zuo <zuojianghua@gmail.com>
     * @date 2012-12-11
     */
    private function getFooter() {

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
        if(!empty($data) && is_array($data)){
            $menu->startTrans();
            foreach($data as $keym=>$valm){
                $ary_nav = D('Gyfx')->selectOneCache('menus',$ary_field=null, array('sn'=>'UNAV1_'.($keym),'group'=>'Ucenter'), $ary_order=null);
				//$menu->where(array('sn'=>'UNAV1_'.($keym),'group'=>'Ucenter'))->find();
                $ary_data = array();
                $ary_data['name'] = $this->tops[$keym]['name'];
                $ary_data['group'] = 'Ucenter';
                $ary_data['toporder'] = ($keym);
                $ary_data['fid'] = '0';
                $ary_data['url'] = $this->tops[$keym]['url'];
                $ary_data['sn'] = 'UNAV1_'.($keym);
                $ary_data['type'] = $this->tops[$keym]['type'];
                if(isset($this->tops[$keym]['status'])){
                    $ary_data['status'] = $this->tops[$keym]['status'];
                }
                if(!empty($ary_nav) && is_array($ary_nav)){
                    //$ary_result = $menu->where(array('id'=>$ary_nav['id']))->data($ary_data)->save();
                    $ary_result = $ary_nav['id'];
                }else{
                    $ary_result = $menu->add($ary_data);
                }
                if(FALSE !== $ary_result){
                    if(!empty($valm) && is_array($valm)){
                        foreach($valm as $kym=>$vlm){
                            //$arr_nav = $menu->where(array('sn'=>'UMENU'.($keym).'_'.($kym),'group'=>'Ucenter'))->find();
							$arr_nav = D('Gyfx')->selectOneCache('menus',null, array('sn'=>'UMENU'.($keym).'_'.($kym),'group'=>'Ucenter'));
                            $arr_data = array();
                            $arr_data['name'] = $vlm['name'];
                            $arr_data['group'] = 'Ucenter';
                            $arr_data['toporder'] = ($keym);
                            $arr_data['suborder'] = ($kym);
                            $arr_data['fid'] = $ary_result;
                            $arr_data['sn'] = 'UMENU'.($keym).'_'.($kym);
                            $arr_data['url'] = $vlm['url'];
                            $arr_data['type'] = $vlm['type'];
                            if(isset($this->tops[$keym]['status'])){
                                $arr_data['status'] = $this->tops[$keym]['status'];
                            }
                            if(!empty($arr_nav) && is_array($arr_nav)){
                                //$ary_res = $menu->where(array('id'=>$arr_nav['id']))->data($arr_data)->save();
                                $ary_res = $arr_nav['id'];
                            }else{				
                                $ary_res = $menu->add($arr_data);
                            }
                            if(FALSE !== $ary_res){
                                $menu->commit();
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
    }
	
	/**
     * 获得顶部广告图
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6 
     * @date 2014-06-10
     */
    public function getTopAds() {
		$ary_ads = D('SysConfig')->getConfigs('GY_SHOP_TOP_AD');
		$ary_ads_data = array();
		//大小两图
		if($ary_ads['STATE']['sc_value'] == '1'){
			$ary_ads_data['big_pic'] = $ary_ads['BIG_PIC']['sc_value'];
			$ary_ads_data['big_pic_url'] = $ary_ads['BIG_PIC_URL']['sc_value'];
			$ary_ads_data['small_pic'] = $ary_ads['SMALL_PIC']['sc_value'];
			$ary_ads_data['small_pic_url'] = $ary_ads['SMALL_PIC_URL']['sc_value'];
		}
		//只显示小图
		if($ary_ads['STATE']['sc_value'] == '2'){
			$ary_ads_data['small_pic'] = $ary_ads['SMALL_PIC']['sc_value'];
			$ary_ads_data['small_pic_url'] = $ary_ads['SMALL_PIC_URL']['sc_value'];		
		}	
		if(!empty($ary_ads['RIGHT_PIC']['sc_value'])){
			$ary_ads_data['right_pic'] = $ary_ads['RIGHT_PIC']['sc_value'];
			$ary_ads_data['right_pic_url'] = $ary_ads['RIGHT_PIC_URL']['sc_value'];			
		}
		return $ary_ads_data;
    }	
}
