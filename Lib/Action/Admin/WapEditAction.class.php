<?php
/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/1/4
 * Time: 18:18
 */

class WapEditAction extends AdminBaseAction{
	private $array_operation = array('0'=>'保存首页','1'=>'暂存首页','2'=>'初始化首页','3'=>'返回上次编辑');
	protected $wap_theme_path = '';

	public function _initialize() {
		$this->dir = C('WAP_TPL_DIR');
		if(!$this->dir) {
			$this->error('WAP主题目录没有设置！');
		}
		C('LAYOUT_NAME',"edit_wap_layout");
		$this->doCheckLogin();
		$this->getTitle();
		//$this->_name = $this->getActionName();
		if(!defined("WAP_TPL")) {
			$array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_WAP_DEFAULT'))->find();
			if (is_array($array_config) && !empty($array_config)) {
				define('WAP_TPL', $array_config['sc_value']);
				$_SESSION['NOW_WAP_TPL'] = $array_config['sc_value'];
			} else {
				define('WAP_TPL', 'default');
				$_SESSION['NOW_WAP_TPL'] = 'default';
			}
		}
		$wap_theme_path = '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/' . WAP_TPL .'/';
		$this->wap_theme_path = FXINC . $wap_theme_path;
		$config = array(
			'tpl' => $wap_theme_path,
			'js' => $wap_theme_path .'js/',
			'images' => $wap_theme_path . 'images/', // 客户模版images路径替换规则
			'css' => $wap_theme_path . 'css/', // 客户模版css路径替换规则
		);
		C('TMPL_PARSE_STRING.__TPL__', $config['tpl']);
		C('TMPL_PARSE_STRING.__JS__', $config['js']);
		C('TMPL_PARSE_STRING.__IMAGES__', $config['images']);
		C('TMPL_PARSE_STRING.__CSS__', $config['css']);

		import('ORG.Util.Session');
		import('ORG.Util.Page');
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
						$this->error("您无权访问此页");
						//die('您无权访问此页');
					}
				}
			}
		}
		//权限问题
		$this->assign('is_user_access',$INT_USER_ACCESS);
		$str_shop = D('SysConfig')->getConfigs('GY_SHOP');
		$this->assign($str_shop);
	}
	/**
	 * 可视化编辑首页模版
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
	 */
	public function edit() {
		$file = $this->_get('file');

		$this->setTitle('首页');
		$GY_SHOP_HOST = D('SysConfig')->getConfigValueBySckey('GY_SHOP_HOST', 'GY_SHOP');

		$tmplateEdit = new TemplateEdit;
		//本套模版可用的模块列表
		$tmplateInfo = $tmplateEdit->getModInfo(CI_SN.'/'. $this->dir, WAP_TPL);
        //echo "<pre>";print_r($tmplateInfo);exit;
		//各个模块的设置信息
		$modSet['newsList'] = $tmplateEdit->getModNewsList();
		$modSet['goodsList'] = $tmplateEdit->getModGoodsList();
		//dump($modSet);exit;
		$headerTpl = $this->wap_theme_path . 'indexheader.html';
		if(!file_exists($headerTpl)){
			$headerTpl = $this->wap_theme_path . 'header.html';

		}
		$footerTpl = $this->wap_theme_path . 'indexfooter.html';
		if(!file_exists($footerTpl)){
			$footerTpl = $this->wap_theme_path . 'footer.html';
		}
		$tpl = $this->wap_theme_path . $file;
        //dump($tpl);die;
		$brands = D('ViewGoods')->getBrands();
		$types = D('ViewGoods')->getTypes();
		$this->assign('base_url', $GY_SHOP_HOST);
		$this->assign("headerTpl",$headerTpl);
		$this->assign("footerTpl",$footerTpl);
		$this->assign('dir', WAP_TPL);
		$this->assign('tplInfo', $tmplateInfo);
		$this->assign('modSet', $modSet);
		$this->assign('brands', $brands);
		$this->assign('types', $types);

		$this->display($tpl);
		layout(FALSE);
		$this->display('./Tpl/Wap/Common/edit.html');
	}

	/**
	 * 获取未嵌套的演示模块的HTML
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
	 */
	public function getModHtml(){
		$mod = $this->_get('mod');
		$tpl = $this->wap_theme_path . 'widget/' . $mod . '.html';
		layout(FALSE);
		$this->display($tpl);
	}

	/**
	 * 当前模板头部展示页，此页面展示给会员中心专用
	 *
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
	 */
	public function showHomeHeader(){
		layout(false);
		$tpl = $this->wap_theme_path . 'header.html';
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
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
	 */
	public function showHomeFooter(){
		layout(false);
		$tpl = $this->wap_theme_path . 'footer.html';
		if(!file_exists($tpl)){
			header("content-type:text/html;charset=utf-8;");
			echo '<h1>请您安装模板。</h1>';
			exit;
		}
		$this->display($tpl);
	}

	/**
	 * 获取已经嵌套数据的模块html
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
	 */
	public function getModDataHtml(){
		// print_r($_GET);exit;
		$ary_request = $this->_get();
		$mod = htmlspecialchars(trim($this->_get('mod')));
		$data = $ary_request['dat'];
		//某些参数进行特殊处理，数组转字符串
		/* foreach($data as $key=>$val){
			if(is_array($val)){
				$data[$key] = implode(',', $val);
			}
		}  */
		$data = $this->arraythis($data);
		$tpl = $this->wap_theme_path . 'common/' . $mod . '.html';
		$this->assign($data);
		layout(FALSE);
		$this->display($tpl);
	}

	/**
	 * 某些参数进行特殊处理
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @param type $data
	 * @return type
	 * @version 7.5
	 */
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
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
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
		$tpl = $this->wap_theme_path . 'initial.html';
		layout(FALSE);
		// echo "<pre>";print_r($data);exit;
		$this->display($tpl);
	}

	/**
	 * 选择配送区域
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @version 7.5
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
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-19
	 * @param  $file_name 此参数 自定义导航时必须在后台把静态页面文件名添加到导航url
	 * @version 7.5
	 */
	public function getStaticPage() {
		$file_name = $this->_param(3) ? $this->_param(3) : $this->_param(2);
		if('member' == $file_name){
			$tpl = $this->wap_theme_path . 'static/member.html';
		}else if('knowyst' == $file_name){
			$tpl = $this->wap_theme_path . 'static/knowyst.html';
		}else if('newpro' == $file_name){
			$tpl = $this->wap_theme_path . 'static/newpro.html';
		}else{
			$this->error('此页面已不存在……');
		}
		//layout(FALSE);
		$this->display($tpl);
	}

	/**
	 * 保存模版信息
	 */
	public function save() {
		$meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
		$content = $this->_post('content', '');

		//找到header ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//找到footer ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//找到main ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$main = array();
		$reg = '`<div id="main">(.*?)</div><!--end of main-->`is';
		$dir = $this->_post('dir');
		preg_match($reg, $content, $main);
		// print_r($main);exit;
		$doc = new DOMDocument();
		$doc->loadHTML($meta . $main[0]);
		$nodes = $doc->getElementsByTagName('div');
		foreach ($nodes as $node) {

			$class = $node->getAttribute('class');

			if (preg_match('`block`is', $class)) {
				//此节点中含有block类，表明是一个可供修改的DIV
				//找到该节点的其他类属性，取第一个作为switch选择条件
				$class = str_replace('  ', ' ', trim($class)); //防止多敲一个空格
				$classes = explode(' ', $class);
				//模块的种类 例如index_category,index_pic_single之类
				$mode_class = $classes[0];
				//模块现在的内容
				//根据不同的模块去替换回ThinkPHP标签
				$replaceNode = $this->getModeNode($node, $mode_class, $dir);
				// print_r($replaceNode);exit;
				if (NULL != $replaceNode) {
					$node->parentNode->replaceChild($replaceNode, $node);
					//echo $replaceNode->ownerDocument->saveHTML($replaceNode);
					//dump($replaceNode);exit;
				}
				//echo $node->ownerDocument->saveHTML($node);
				//$node = $replaceNode;
				//exit;
			}
			//dump();
		}
		$output_str =  htmlspecialchars_decode($doc->saveHTML());
		$output_str = html_entity_decode($output_str);
		$output_str = preg_replace('`<!DOCTYPE.*?>`i', '', $output_str);
		$output_str = preg_replace('`<html>`i', '', $output_str);
		$output_str = preg_replace('`<head>`i', '', $output_str);
		$output_str = preg_replace('`<meta.*?>`i', '', $output_str);
		$output_str = preg_replace('`</head>`i', '', $output_str);
		$output_str = preg_replace('`<body>`i', '', $output_str);
		$output_str = preg_replace('`</body>`i', '', $output_str);
		$output_str = preg_replace('`</html>`i', '', $output_str);
		$output_str = preg_replace('`<!--.*?-->`i', '', $output_str);
		$output_str = preg_replace('`style=""`i', '', $output_str);
		$output_str = preg_replace('`^\s*|\s*$`i', '', $output_str);
		
		$reg_add = '<div class="add">在此添加新模块</div>';
		$content_add = ' ';
		$pos = strpos($output_str,$reg_add);
		if($pos){
			$output_str = str_replace($reg_add,$content_add,$output_str);
		}
		$output_file = $this->wap_theme_path . 'index.html';
		if($output_str == '' || !is_writable($output_file)){
			$this->ajaxReturn(false);
		}
		$output_str .= "<!--end of main-->";
		//七牛图片显示授权
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){
			$output_str = D('ViewGoods')->ReplaceItemDescPicDomain($output_str);
		}
		//  echo $output_str;exit;
		file_put_contents($output_file, $output_str);
		$this->ajaxReturn(true);
	}

	/**
	 * 暂存模版信息
	 */
	public function zancun(){
		$meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
		$content = $this->_post('content', '');
		//  echo $content;exit;
		//找到header ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//找到footer ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//找到main ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$main = array();
		$reg = '`<div id="main">(.*?)</div><!--end of main-->`is';
		$dir = $this->_post('dir');
		preg_match($reg, $content, $main);
		$doc = new DOMDocument();
		$doc->loadHTML($meta . $main[0]);
		$nodes = $doc->getElementsByTagName('div');
		foreach ($nodes as $node) {

			$class = $node->getAttribute('class');

			if (preg_match('`block`is', $class)) {
				//此节点中含有block类，表明是一个可供修改的DIV
				//找到该节点的其他类属性，取第一个作为switch选择条件
				$class = str_replace('  ', ' ', trim($class)); //防止多敲一个空格
				$classes = explode(' ', $class);

				//模块的种类 例如index_category,index_pic_single之类
				$mode_class = $classes[0];
				//模块现在的内容
				//根据不同的模块去替换回ThinkPHP标签
				$replaceNode = $this->getModeNode($node, $mode_class, $dir);

				if (NULL != $replaceNode) {
					$node->parentNode->replaceChild($replaceNode, $node);
					//echo $replaceNode->ownerDocument->saveHTML($replaceNode);
					//dump($replaceNode);exit;
				}
				//echo $node->ownerDocument->saveHTML($node);
				//$node = $replaceNode;
				//exit;
			}
			//dump();
		}
		$output_str =  htmlspecialchars_decode($doc->saveHTML());
		$output_str = html_entity_decode($output_str);
		$output_str = preg_replace('`<!DOCTYPE.*?>`i', '', $output_str);
		$output_str = preg_replace('`<html>`i', '', $output_str);
		$output_str = preg_replace('`<head>`i', '', $output_str);
		$output_str = preg_replace('`<meta.*?>`i', '', $output_str);
		$output_str = preg_replace('`</head>`i', '', $output_str);
		$output_str = preg_replace('`<body>`i', '', $output_str);
		$output_str = preg_replace('`</body>`i', '', $output_str);
		$output_str = preg_replace('`</html>`i', '', $output_str);
		$output_str = preg_replace('`<!--.*?-->`i', '', $output_str);
		$output_str = preg_replace('`style=""`i', '', $output_str);
		$output_file = $this->wap_theme_path .'preview/index.html';
		file_put_contents($output_file, $output_str);
		$this->ajaxReturn(true);
	}

	/**
	 * 根据模块的类名，找到相应的ThinkPHP代码片段，并将此转化成PHP DOM操作中的DOMNode
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $mode_class 模块类名
	 * @param string $dir 模版名（文件夹名）
	 */
	private function getModeNode($node, $mode_class, $dir = 'default') {
		//$meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
		$file_url = $this->wap_theme_path . 'common/' . $mode_class . '.html';
		$content = file_get_contents($file_url);
		$content = preg_replace('`<!--.*?-->`i', '', $content);
		//不需要更改的自定义标签
		if($mode_class == 'show_common_html'){
			$content = $this->replace_style_common_content($node, $content);
		}else{
			$content = $this->replace_style_content($node, $content);
		}
		$content = $this->$mode_class($node, $content);

		//$doc = new DOMDocument();
		$replaceNode = $node->ownerDocument->createElement('div', htmlentities($content));

		//echo($replaceNode->nodeValue);
		$classAttribute = new DOMAttr('class');             //$replaceNode->createAttribute('class');
		//print_r($mode_class);exit;
		$classAttribute->value = $mode_class . ' block';
		$styleAttribute = new DOMAttr('style');
		$styleAttribute->value = $node->getAttribute('style');
		$str_style = $node->getAttribute('style');

		if(!empty($str_style)){
			$replaceNode->appendChild($styleAttribute);
		}
		$blockAttribute = new DOMAttr('block-dat');
		$blockAttribute->value = $node->getAttribute('block-dat');
		$replaceNode->appendChild($blockAttribute);
		$replaceNode->appendChild($classAttribute);

		$idAttribute = new DOMAttr('id');
		$idAttribute->value = $node->getAttribute('id');
		if(!empty($idAttribute)){
			$replaceNode->appendChild($idAttribute);
		}
		//$doc = new DOMDocument();
		//$doc->loadXML($content);
		//$node = $doc->getElementsByTagName('body')->item(0);
		//echo $doc->saveXML();
		//exit;
		//$nodes = $replaceNode->childNodes->item(0);
		//echo $replaceNode->ownerDocument->saveHTML($replaceNode);
		return $replaceNode;
	}

	##### 与block类同名的模块替换处理方法 ########################################
	/**
	 * 根据textstyle-editable/imagestyle-editable和style-num替换掉相应样式和内容
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */

	private function replace_style_content($node, $content) {
		$childList = $node->getElementsByTagName('*');
		//替换自定义内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++
		$custom_content = $node->getAttribute('block-dat');
		$ary_custom_content = json_decode($custom_content, TRUE);
		foreach ($childList as $child) {
			//替换样式 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			if ($child->hasAttribute('textstyle-editable') || $child->hasAttribute('imagestyle-editable')) {
				$style = $child->getAttribute('style');
				$styleNum = $child->getAttribute('style-num');
				//在style-num后添加style字段 ------------------------------------
				//注：此处有坑。已有style的需要单独处理一下，或强制规定html内不能写style
				$content = preg_replace('`(style-num="' . $styleNum . '")`i', '${1} '.'style="' . $style . '"', $content);
			}

			//1) 文本内容 ------------------------------------------------------
			if ($child->hasAttribute('textcontent-editable')) {
				$contentNum = $child->getAttribute('content-num');
				$content = preg_replace('`(<.*?content-num="' . $contentNum . '".*?>)(.*?)(</.*?>)`i', '${1}' . $ary_custom_content['textcontent'][$contentNum] . '${3}', $content);
			}
			//2) 图片内容 ------------------------------------------------------
			if ($child->hasAttribute('imagecontent-editable')) {
				$contentNum = $child->getAttribute('content-num');
				$reg = '`(<img src=")(.*?)(".*?content-num="' . $contentNum . '")`i';
				//echo "\r\n";
				//echo $ary_custom_content['imagecontent'][$contentNum];
				//echo "\r\n";
				$content = preg_replace($reg, '${1}' . $ary_custom_content['imagecontent'][$contentNum] . '${3}', $content);
			}
			//3) 超链接内容 ----------------------------------------------------
			if ($child->hasAttribute('linkcontent-editable')) {
				$contentNum = $child->getAttribute('content-num');
				$content = preg_replace('`(<a.*?href=")(.*?)(".*?content-num="' . $contentNum . '".*?>)`i', '${1}' . $ary_custom_content['linkcontent'][$contentNum] . '${3}', $content);
			}
		}
		return $content;
	}

	/**
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 不需要更改的自定义标签
	 */

	private function replace_style_common_content($node, $content) {
		return '<div class="show_common_html block" >'.$content.'</div>';
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function ad_text($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function ad_code($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}
	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_category($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_pic_single($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * 仅编辑图片和连接
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_pic($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_lunbo($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_notice_list($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		//替换自定义内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++
		$custom_content = $node->getAttribute('block-dat');
		$ary_custom_content = json_decode($custom_content, TRUE);
		//print_r($ary_custom_content);exit;
		//替换gyfx:article中的$num
		$content = preg_replace('`(<gyfx:notice.*?num=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['num'] . '${3}', $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_news_list($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		//替换自定义内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++
		$custom_content = $node->getAttribute('block-dat');
		$ary_custom_content = json_decode($custom_content, TRUE);

		$cid = $hcid = $bid = '';
		//print_r($ary_custom_content);exit;
		if(!empty($ary_custom_content['cid'])){
			foreach ($ary_custom_content['cid'] as $c_v){
				$cid .= $c_v.",";
			}
			$cid = rtrim(trim($cid,','));
		}
		if(!empty($ary_custom_content['bid'])){
			foreach ($ary_custom_content['bid'] as $c_v){
				$bid .= $c_v.",";
			}
			$bid = rtrim(trim($bid,','));
		}
		if(!empty($ary_custom_content['hcid'])){
			foreach ($ary_custom_content['hcid'] as $c_v){
				$hcid .= $c_v.",";
			}
			$hcid = rtrim(trim($hcid,','));
		}//print_r($hcid);exit;
		//替换gyfx:article中的$cid/$num
		$content = preg_replace('`(<gyfx:goodslist.*?cid=")(\$.*?)(".*?>)`i', '${1}' . $cid . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?num=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['num'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?startprice=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['startprice'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?gname=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['gname'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?gsn=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['gsn'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?endprice=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['endprice'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?tid=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['tid'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?bid=")(\$.*?)(".*?>)`i', '${1}' . $bid . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?hot=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['hot'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?new=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['new'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?order=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['order'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?column=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['column'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?market_price=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['price']['market_price']['show'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?sale_price=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['price']['sale_price']['show'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?save_price=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['price']['save_price']['show'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?discount_price=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['discount_price']['show'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?a=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['a'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?show_name=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['show_name'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?show_pic=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['show_pic'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?g_nums=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['g_nums'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?g_instead=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['g_instead'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?hc_nums=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['hc_nums'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:goodslist.*?hcid=")(\$.*?)(".*?>)`i', '${1}' . $hcid . '${3}', $content);

		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_A($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_B($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_C($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_D($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_E($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_F($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_G($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_H($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_I($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_J($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_K($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_product_list_L($node, $content) {
		$content = $this->index_news_list($node, $content);
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function show_common_html($node, $content) {
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_article_list($node, $content) {
		//如本模块中有THINKPHP自定义标签则需要进行扩充替换
		//替换自定义内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++
		$custom_content = $node->getAttribute('block-dat');
		$ary_custom_content = json_decode($custom_content, TRUE);
		// print_r($content);exit;
		//替换gyfx:article中的$num
		$content = preg_replace('`(<gyfx:article.*?num=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['num'] . '${3}', $content);
		$content = preg_replace('`(<gyfx:article.*?cid=")(\$.*?)(".*?>)`i', '${1}' . $ary_custom_content['cid'] . '${3}', $content);

		return $content;
	}
	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_topmenu($node, $content){
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_hotlinks($node, $content){
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_hotlinks_2($node, $content){
		return $content;
	}

	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author Nick <shanguangkun@guanyisoft.com>
	 * @date 2014-05-20
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_hotlinks_3($node, $content){
		return $content;
	}
	
	/**
	 * 替换模块中的THINKPHP自定义标签当中的设置信息
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2015-08-18
	 * @param object $node 要替换的DOM文档节点
	 * @param string $content 要替换的THINKPHP模版片段
	 * @return string $content 替换后的字符串
	 */
	private function index_hotlinks_4($node, $content){
		return $content;
	}

    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-20
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_bottomnav($node, $content){
        return $content;
    }
}
