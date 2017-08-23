<?php

/**
 * 前台模版首页生成
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class IndexAction extends HomeAction {

    protected $dir = '';

    public function _initialize() {
        parent::_initialize();
        
    }

    /**
     * 客户模版默认首页
     * @author
     * @date
     */
    public function index() {
        if($_SERVER["QUERY_STRING"]=='s=.DS_Store'||$_SERVER["QUERY_STRING"]=='s=.gitignore'||$_SERVER["QUERY_STRING"]=='s=.htaccess'||$_SERVER["QUERY_STRING"]=='s=.htpasswd'||$_SERVER["QUERY_STRING"]=='s=.listing'||$_SERVER["QUERY_STRING"]=='s=.passwd'||$_SERVER["QUERY_STRING"]=='s=.viminfo'){
            header("location:" . U('Wrong'));exit;
        }
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN','','',1);
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login/v/2'));
            exit;
        }
        
        $ary_request = $this->_request();
		$ary_request['index']=1;//判断是否为首页
        $member = session('Members');
        //$this->setTitle('首页','TITLE_INDEX','KEY_INDEX','DESC_INDEX');
        $this->assign('page_title', '彩妆国季-专卖全球最靓的化妆品');
        $this->assign('page_keywords', '彩妆国季官网，彩妆国季，化妆品直邮，品牌护肤品，品牌化妆品，化妆品购物商城，海外购物,品牌护肤化妆品');
        $this->assign('page_description', '彩妆国季是广州悦荞茉萱贸易有限公司旗下知名品牌护肤化妆品海淘购物商城，集韩国、日本、欧美等国家数万个正品品牌化妆品大全。方便、快捷的为国内外用户提供海外彩妆、美体、护肤等时尚品牌化妆品直邮购物服务！');

        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/index.html';
        } else {
            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/index.html';
        }

        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/indexheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';

        }else{
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/indexheader.html';
        }

        $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/indexfooter.html';
        if(!file_exists($footerTpl)){
            $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer.html';

        }else{
            $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/indexfooter.html';
        }
		
		//七牛图片显示授权
		$index_update_time=filemtime($tpl);
		if(time()-$index_update_time > 86400 && $index_update_time!=false && $_SESSION['OSS']['GY_QN_ON'] == '1'){
			$index_content = htmlspecialchars_decode(file_get_contents($tpl));
			$replace_index_content=D('ViewGoods')->ReplaceItemDescPicDomain($index_content);
			file_put_contents($tpl, $replace_index_content);
		}
		
        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
		//D('SysConfig')->where()->getField('sc_value');
		$this->assign('warm_prompt',$warm_prompt['sc_value']);
		//获取顶部广告信息
		//$ary_top_ads = $this->getTopAds();	
		//$this->assign("ary_top_ads",$ary_top_ads);
		
		//数据处理
		$_SESSION['rand'] = rand();
		$requset_url = $_SERVER['HTTP_REFERER'];
		$this->assign('requset_url',$requset_url);
        $this->assign("ary_request",$ary_request);
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->assign("navindex",1);
        $domain = $_SERVER['SERVER_NAME'];
        $this->assign("domain",$domain);
        //echo "<pre>";print_r($tpl);exit;
        //var_dump($_SESSION['Members']);
        if($_GET['v']==2){
            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/index-v2.html';
            $this->assign("v",'-v2');
            $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
            $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
            $this->assign("headerTpl",$headerTpl);
            $this->assign("footerTpl",$footerTpl);
            $v='-v2';
        }

        $this->display($tpl);
    }

    /**
     * 可视化编辑首页模版
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-03
     */
    public function edit() {
        $member = session('Members');
        $dir = $this->_get('dir');
        $file = $this->_get('file');
        $HTTP_REFERER = "http://{$_SERVER['HTTP_HOST']}/Admin/Home/doEditTpl/tabs/mytpl/options/Temm/dir/".$dir;
        if(isset($_GET['tid']) && !empty($_GET['tid'])){
            $HTTP_REFERER .= "/tid/".$_GET['tid'];
        }
        if ($_SERVER['HTTP_REFERER'] != $HTTP_REFERER) {
            $int_port = "";
            if($_SERVER["SERVER_PORT"] != 80){
                $int_port = ':' . $_SERVER["SERVER_PORT"];
            }
            $string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
            $data = D('SysConfig')->getCfgByModule('ADMIN_LOGIN_PROMPT');
            
            if($data['ADMIN_LOGIN_PROMPT_SET'] == '1'){
                $this->error(L('NO_LOGIN'), U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri));
            }else{
                header("Location:".U('Admin/User/pageLogin') . '?redirect_uri=' . urlencode($string_request_uri)."");exit;
            }
        }
        $this->setTitle('首页');


        $tmplateEdit = new TemplateEdit;
        //本套模版可用的模块列表
        $tmplateInfo = $tmplateEdit->getModInfo(CI_SN, $dir);
        //echo "<pre>";print_r($tmplateInfo);exit;
        //各个模块的设置信息
        $modSet['newsList'] = $tmplateEdit->getModNewsList();
        $modSet['goodsList'] = $tmplateEdit->getModGoodsList();
        //dump($modSet);exit;
        $headerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/indexheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';

        }else{
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/indexheader.html';
        }
        $footerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/indexfooter.html';
        if(!file_exists($footerTpl)){
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/footer.html';
        }else{
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/indexfooter.html';
        }
        $tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/' . $file;
        $brands = D('ViewGoods')->getBrands();
        $types = D('ViewGoods')->getTypes();
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->assign('dir',$dir);
        $this->assign('tplInfo', $tmplateInfo);
        $this->assign('modSet', $modSet);
        $this->assign('brands', $brands);
        $this->assign('types', $types);

        $this->display($tpl);
        layout(FALSE);
        $this->display('./Tpl/Home/Common/edit.html');
    }

    /**
     * 获取未嵌套的演示模块的HTML
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-14
     */
    public function getModHtml(){
        $dir = $this->_get('dir');
        $mod = $this->_get('mod');		
		$tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/widget/' . $mod . '.html';
        if(!file_exists($tpl)){
			$file_url = './Public/Tpl/' . CI_SN . '/' . $dir . '/widget/';
			$file_dir = scandir('./Public/Tpl/' . CI_SN . '/' . $dir . '/widget/');
			//PHP遍历文件夹下所有文件
			$ary_files = array();
			foreach($file_dir as $file){
				if($file != '.' && $file !='..'){
					if(file_exists($file_url.$file)){
						$ary_files[] = $file;
					}				
				}
			}
		}
		if(count($ary_files) == '1'){
			$tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/widget/' . $ary_files[0];
		}
		layout(FALSE);
        $this->display($tpl);
    }

	/**
	 * 当前模板头部展示页，此页面展示给会员中心专用
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-07-03
	 * @version 1.0
	 */
	public function showHomeHeader(){
		layout(false);
		$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/header.html';
		if(!file_exists($tpl)){
			header("content-type:text/html;charset=utf-8;");
			echo '<h1>请您安装模板。</h1>';
			exit;
		}
		$this->display($tpl);
	}

	/**
	 * 当前模板的尾部展示
	 *
	 * @author Mithern <sunguangxu@guanyisoft.com>
	 * @date 2013-07-03
	 * @version 1.0
	 */
	public function showHomeFooter(){
		layout(false);
		$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/footer.html';
		if(!file_exists($tpl)){
			header("content-type:text/html;charset=utf-8;");
			echo '<h1>请您安装模板。</h1>';
			exit;
		}
		$this->display($tpl);
	}

    /**
     * 获取已经嵌套数据的模块html
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-15
     */
    public function getModDataHtml(){
  // print_r($_GET);exit;
        $ary_request = $this->_get();
        $dir = htmlspecialchars(trim($this->_get('dir')));
        $mod = htmlspecialchars(trim($this->_get('mod')));
        $data = $ary_request['dat'];
        //某些参数进行特殊处理，数组转字符串
         /* foreach($data as $key=>$val){
            if(is_array($val)){
                $data[$key] = implode(',', $val);
            }
        }  */
        $data = $this->arraythis($data);
        $tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/common/' . $mod . '.html';
        $this->assign($data);
        layout(FALSE);
        $this->display($tpl);
    }

    public function arraythis($data){
        foreach($data as $key=>$data_val){
            $i = 0;
            if(is_array($data_val)){
                foreach ($data_val as $k=>$v){
                    if(is_array($v)){
                        $i = 1;
                    }
                }
                if($i == 1){
                    $t_v = $this->arraythis($data_val);
                    $data[$key] = $t_v;
                }else{
                    $data[$key] = implode(',', $data_val);
                }
            }
            $i = 0;
        }
        return $data;
    }

    /**
    * 获取地址库信息
    * @author Terry<wanghui@guanyisoft.com>
    * @date 2013-07-23
    */
    public function doCity(){
        $PinYin = new Pinyin();
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array();
        $where['cr_status'] = '1';
        $where['cr_path'] = array("EQ","1");
        $where['cr_is_parent'] = '1';
        $ary_parent = $action->where($where)->order('`cr_name` ASC')->select();
        if(!empty($ary_parent) && is_array($ary_parent)){
            foreach($ary_parent as $keyp=>$valp){
                $where = array();
                $where['cr_status'] = '1';
                $where['cr_parent_id'] = $valp['cr_id'];
                $ary_parent[$keyp]['city'] = $action->where($where)->select();
            }
        }

        if(!empty($ary_parent) && is_array($ary_parent)){
            $initials = array();
            $data = array();
            foreach($ary_parent as $keycity=>$valcity){
                $ary_parent[$keycity]['cr_name'] = strtr($valcity['cr_name'], array("省"=>"","维吾尔"=>"","壮族"=>"","回族"=>"","区"=>"","自治"=>"","特别行政"=>""));
                //$initials[] = substr($PinYin->StringPy($valcity['cr_name']),0,0);
                $cr_name = strtoupper($PinYin->Pinyin($valcity['cr_name']));
                if($cr_name == "ZHONGQING"){
                    $cr_name = "CHONGQING";
                }else{
                    $cr_name = $PinYin->StringPy($valcity['cr_name']);
                }
                $ary_parent[$keycity]['initials'] = substr($cr_name,0,1);
                $initials[] = substr($cr_name,0,1);
            }
            // echo "<pre>";print_r($initials);exit;
            if(!empty($initials) && is_array($initials)){
                $ary_initials = array_unique($initials);
                sort($ary_initials,SORT_STRING);
            }

            foreach($ary_parent as $keyp=>$valp){
                if($valp['initials'] == $ary_parent[$keyp]['initials']){
                    $data[$valp['initials']]['id'] = $valp['initials'];
                    $data[$valp['initials']]['name'] = $valp['initials'];
                    $data[$valp['initials']]['city'][] = $valp;
                }
            }
            array_multisort($data,SORT_ASC);
        }
        $this->assign("count",count($ary_initials));
        $this->assign("data",$data);
        $this->assign("initial",$ary_initials);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/initial.html';
        layout(FALSE);
        // echo "<pre>";print_r($data);exit;
        $this->display($tpl);
    }

    /**
     * 选择配送区域
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-23
     */
    public function doSelectedCity(){
        $ary_post = $this->_post();
        if(empty($ary_post['cr_id']) || $ary_post['cr_id'] <= 0){
            $this->error("配送区域不存在,请重试...");
        }
        $action = M('CityRegion',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array();
        $where['cr_id'] = $ary_post['cr_id'];
        $where['cr_status'] = '1';
        $ary_city = $action->where($where)->find();
        if(!empty($ary_city) && is_array($ary_city)){
            $_SESSION['city']['cr_id'] = $ary_city['cr_id'];
            $_SESSION['city']['cr_name'] = $ary_city['cr_name'];
            $this->success("选择配送区域成功");
        }else{
            $this->error("获取区域失败，请重试...");
        }
    }

    /**
     * 后台自定义导航 仅指向静态页面
     * @author WangHaoYu <why419163@163.com>
     * @param  $file_name 此参数 自定义导航时必须在后台把静态页面文件名添加到导航url
     * @version 7.4 
     * @date 2013-11-9
     */
    public function getStaticPage() {
        $file_name = $this->_param(3) ? $this->_param(3) : $this->_param(2);
        if('member' == $file_name){
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/static/member.html';
        }else if('knowyst' == $file_name){
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/static/knowyst.html';
        }else if('newpro' == $file_name){
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/static/newpro.html';
        }else{
            $this->error('此页面已不存在……');
        }
        //layout(FALSE);
        $this->display($tpl);
    }
	
    /**
     * 获得顶部广告图
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6 
     * @date 2014-06-10
     */
    public function getTopAds() {
	
		$ary_ads = D('SysConfig')->getConfigs('GY_SHOP_TOP_AD','','','',1);
		$ary_ads_data = array();
		//大小两图
		if(isset($ary_ads['STATE']['sc_value']) && $ary_ads['STATE']['sc_value'] == '1'){
			$ary_ads_data['big_pic'] = $ary_ads['BIG_PIC']['sc_value'];
			$ary_ads_data['big_pic_url'] = $ary_ads['BIG_PIC_URL']['sc_value'];
			$ary_ads_data['small_pic'] = $ary_ads['SMALL_PIC']['sc_value'];
			$ary_ads_data['small_pic_url'] = $ary_ads['SMALL_PIC_URL']['sc_value'];
		}
		//只显示小图
		if(isset($ary_ads['STATE']['sc_value']) && $ary_ads['STATE']['sc_value'] == '2'){
			$ary_ads_data['small_pic'] = $ary_ads['SMALL_PIC']['sc_value'];
			$ary_ads_data['small_pic_url'] = $ary_ads['SMALL_PIC_URL']['sc_value'];		
		}	
		if(isset($ary_ads['RIGHT_PIC']['sc_value']) && !empty($ary_ads['RIGHT_PIC']['sc_value'])){
			$ary_ads_data['right_pic'] = $ary_ads['RIGHT_PIC']['sc_value'];
			if(isset($ary_ads['RIGHT_PIC_URL']['sc_value'])){
                $ary_ads_data['right_pic_url'] = $ary_ads['RIGHT_PIC_URL']['sc_value'];
            }
		}
		return $ary_ads_data;
    }
	
	public function showThisHtml(){
	    $file_name = $this->_param('html');
		if(!empty($file_name)){
			$tpl = FXINC.'/Public/Tpl/' . CI_SN . '/' . TPL . '/'.$file_name.'.html';
			//layout(FALSE);
			$this->display($tpl);
		}else{
			$this->error('此页面不存在……');
		}
	}
	public function shoHtml(){
		$ary_info = $this->_request();
	    $file_name = $this->_param('html');
		if(!empty($file_name)){
			$tpl = FXINC.'/Public/Tpl/' . CI_SN . '/' . TPL . '/define/'.$file_name.'.html';
			layout(FALSE);
			$this->display($tpl);
		}else{
			$this->error('此页面不存在……','/Home/Index/index');
		}
	}
    /**
     * 自定义九龙港app下载页 仅指向静态页面
     * @version 7.6 
     * @date 2014-08-15
     */
    public function downloadApp() {
        //layout(FALSE);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/xiazai.html';
        $this->display($tpl);
    }

    public function getComment(){
        // $int_g_id = $this->_request();
        $noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $where['gcom_status']    = '1';
        // $where['g_id']  = $int_g_id;
        $where['gcom_parentid'] = 0;
        $where['u_id'] = 0;
        $where['gcom_verify'] = 1;
        $where['gcom_star_score'] = array('gt',0);
        $data = $noticeObj->join('fx_members on fx_members.m_id=fx_goods_comments.m_id')
            ->join('fx_goods_info on fx_goods_info.g_id=fx_goods_comments.g_id')
            ->where($where)
            ->order('gcom_update_time desc')
            ->limit('3')
            ->select();
        $tpl ='./Public/Tpl/' . CI_SN . '/' . TPL . '/comment_index.html';
        $type = explode(',',$comment['comment_show_condition']);
        $comment['type'] = $type[0];
        // $this->assign('g_id',$int_g_id);
        $this->assign("comment",$comment);
        $this->assign('data',$data);
        $this->display($tpl);
    }
	
	public function getCityRegion() {
        $parent = $this->_post('parent');
        $item = $this->_post('item');
        $val = $this->_post('val');
        $ary_city = D("CityRegion")->getAllCitys($parent);
        if (!empty($ary_city) && is_array($ary_city)) {
            $str = '';
            if ($item == 'city') {
                $str = "onchange=\"selectCityRegion(this, 'region','')\";";
            }
			if($item == 'region'){
				$str = "onchange=\"selectCityRegion(this, '','')\";";
			}
            $html = "<select id='" . $item . "' name='" . $item . "' {$str}>";
            $html .= '<option value="0" selected="selected">请选择</option>';
            if (count($ary_city) > 0) {
                foreach ($ary_city as $item) {
                    if ($item['cr_id'] == $val) {
                        $html .= "<option value='{$item['cr_id']}' item='1' selected='selected'>{$item['cr_name']}</option>";
                    } else {
                        $html .= "<option id='option_add_{$item['cr_id']}' value='{$item['cr_id']}' >{$item['cr_name']}</option>";
                    }
                }
            }
            $html .= "</select>";
        } else {
            $html = '';
        }
        echo $html;
        exit;
    }
	/**
     * 线下门店
     * @author Terry<zhuwenwei@guanyisoft.com>
     * @date 2015-09-18
     */
    public function lineShop() {
		$ary_params = $this->_post();
		$o2o_api =  new GyO2oApi();
		$res=$o2o_api->GetShopInfo($ary_params);
		$num = count($res['data']);
		$ary_city = D('CityRegion')->getAllCitys(1);
		//读取文章内容
		$data = $this->_request();
        $a_id = $data['a_id'];
        $articleinfo= M('article', C('DB_PREFIX'), 'DB_CUSTOM')->where('a_id='.$a_id)->find();
		$this->assign('articleinfo',$articleinfo);
		$this->assign("citys",$ary_city);
		$this->assign("o2o_image_url",C('API_URL_IMAGE'));
		$this->assign('addr', $res);
		$this->assign('num', $num);
    	$this->setTitle('线下门店');
		
		//显示页面
		$this->assign('ary_request', $data);
		if($data['cid']==''){
			$data['cid']=1;//默认值
		}
		$where = array('cid' => $data['cid']);
		$cat_where = array('cat_id' => $data['cid']);
		$cate = D('ArticleCat')->where($cat_where)->find();
		$this->assign('cate', $cate);
		$ary_article_list = D('Article')->pageList($where);
		$this->assign('ary_article_list', $ary_article_list['list']);
		
		$tpl ='./Public/Tpl/' . CI_SN . '/' . TPL . '/lineShop.html';
        $this->display($tpl);
    }
	
}


