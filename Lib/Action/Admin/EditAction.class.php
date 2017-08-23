<?php
/**
 * 可视化编辑后台控制器
 *
 * @subpackage Edit
 * @package Action
 * @stage baozhen
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-9-16
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class EditAction extends AdminBaseAction{
    private $array_operation = array('0'=>'保存首页','1'=>'暂存首页','2'=>'初始化首页','3'=>'返回上次编辑');
    
    public function _initialize() {
        parent::_initialize();
		$this->setTitle(' - 可视化编辑');
    }
    
    /**
     * 可视化编辑首页模版
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-9-16
     */
    public function edit() {
        $this->setTitle(' - 可视化模板编辑');
        $dir = $this->_get('dir');
        $file = $this->_get('file');
        $tmplateEdit = new TemplateEdit;
        //本套模版可用的模块列表
        $tmplateInfo = $tmplateEdit->getModInfo(CI_SN, $dir);
        $modSet['newsList'] = $tmplateEdit->getModNewsList();
        $modSet['goodsList'] = $tmplateEdit->getModGoodsList();
        $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/header.html';

        }else{
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexheader.html';
        }
        $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexfooter.html';
        if(!file_exists($footerTpl)){
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/footer.html';
        }else{
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexfooter.html';
        }
        $tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/' . $file;
        $brands = D('ViewGoods')->getBrands();
        $types = D('ViewGoods')->getTypes();
        $this->assign('dir',$dir);
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->assign('files',array('dir'=>$dir,'file'=>'index.html','val'=>'index','type'=>'html'));
        $this->assign('tplInfo', $tmplateInfo);
        $this->assign('modSet', $modSet);
        $this->assign('brands', $brands);
        $this->assign('types', $types);
        $this->assign('is_home_edit','1');
        $this->display($tpl);
        layout(FALSE);
        $this->display();
    }
    
    /**
     * 保存模版信息
     */
    public function save() {
        $meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $content = $this->_post('content', '');
        //$content = stripslashes($content);
		
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
				if($mode_class!='index_show_html'){
					$replaceNode = $this->getModeNode($node, $mode_class, $dir);
				   // print_r($replaceNode);exit;
					if (NULL != $replaceNode) {
						$node->parentNode->replaceChild($replaceNode, $node);
						//echo $replaceNode->ownerDocument->saveHTML($replaceNode);
						//dump($replaceNode);exit;
					}
				}
                //echo $node->ownerDocument->saveHTML($node);
                //$node = $replaceNode;
                //exit;
            }
            //dump();
        }
        //echo $doc->saveHTML();exit;
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
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $dir . '/index.html';
        //$output_str .= "<!--end of main-->";
		
	    $reg_add = '<div class="add">在此添加新模块</div>';
		$content_add = ' ';
		$pos = strpos($output_str,$reg_add);
		if($pos){
			$output_str = str_replace($reg_add,$content_add,$output_str);
		}
		
        //echo $output_str;exit;
        if($output_str == '' || !is_writable($output_file)){
			$this->ajaxReturn(false);
        }else{
            //$this->ajaxReturn('文件不可写,请检查文件的可写权限!');
            $output_str .= "<!--end of main-->";
            $old_content = file_get_contents($output_file);
            file_put_contents(APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $dir . '/init/index_old.html', $old_content);
			$output_str = $this->ReplacPicDomain($output_str);
            file_put_contents($output_file, $output_str);
			/********************************************
			 * 判断是否是负载均衡服务器
			 * modify by zhangjiasuo 
			 * data 2014-11-22
			 *********************************************/
			if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
				if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
					//$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_TPL_IP'];
				}
				if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
					$com_obj = new Communications();
					$ary_request_data['upfile']="@".$output_file;
					$ary_request_data['file_path']='/Public/Tpl/'.CI_SN. '/' . $dir . '/index.html';
					$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);			
				}
			}
            $ary_log['tl_operation'] = $this->array_operation[0];
            $ary_log['u_name'] = $_SESSION['admin_name'];
            $ary_log['u_id'] = $_SESSION['Admin'];
            $ary_log['u_real_name'] = D('Admin')->where(array('u_id'=>$_SESSION['Admin']))->getField('u_real_name');
            $ary_log['tl_operation_time'] = date('Y-m-d H:i:s');
            $ary_log['tl_model'] = $dir.':首页';
            D('TemplateOperationLog')->add($ary_log);
			//删除当前模板首页缓存
			
			$path_url1 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/')).'.html';
			$path_url2 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/Home/Index/index')).'.html';				
			if(file_exists($path_url1)){
				unlink($path_url1);
			}
			if(file_exists($path_url2)){
				unlink($path_url2);
			}	
			make_fsockopen('/Script/Batch/delFile');			
            $this->ajaxReturn(true);
        }
        
    }
    
    /**
     * 暂存模版信息
     */
    public function zancun(){
        $meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $content = $this->_post('content', '');
        //$content = stripcslashes(htmlspecialchars_decode($content));
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
               // print_r($replaceNode);exit;
                if (NULL != $replaceNode) {
                    $node->parentNode->replaceChild($replaceNode, $node);
                }
            }
        }
        $output_str =  $doc->saveHTML();
        //dump($output_str);die;
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
        $output_str = preg_replace('`/\\n`i', '', $output_str);
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . TPL . '/preview_index.html';
        if($output_str == ''){
            $this->ajaxReturn(false);
        }else{
            $output_str .= "<!--end of main-->";
			$output_str = $this->ReplacPicDomain($output_str);
            file_put_contents($output_file, $output_str);
			/********************************************
			 * 判断是否是负载均衡服务器
			 * modify by zhangjiasuo 
			 * data 2014-11-22
			 *********************************************/
			if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
				if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
					//$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_TPL_IP'];
				}
				if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
					$com_obj = new Communications();
					$ary_request_data['upfile']="@".$output_file;
					$ary_request_data['file_path']='/Public/Tpl/'.CI_SN. '/' . $dir . '/index.html';
					$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);			
				}
			}
            $ary_log['tl_operation'] = $this->array_operation[1];
            $ary_log['u_name'] = $_SESSION['admin_name'];
            $ary_log['u_id'] = $_SESSION['Admin'];
            $ary_log['u_real_name'] = D('Admin')->where(array('u_id'=>$_SESSION['Admin']))->getField('u_real_name');
            $ary_log['tl_operation_time'] = date('Y-m-d H:i:s');
            $ary_log['tl_model'] = '首页';
            M('template_operation_log',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_log);
            $this->ajaxReturn(true);
        }
    }
    
    /**
     * 初始化首页
     * @author Joe <qianyijun@guanyisoft@guanyisoft.com>
     * @date 2013-07-03
     * @param object $node 要替换的DOM文档节点
     * @param string $mode_class 模块类名
     * @param string $dir 模版名（文件夹名）
     */
    public function huifu(){
        $file_url = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/init/index.html';
        $content = file_get_contents($file_url);
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/index.html';
        $old_content = file_get_contents($output_file);
        file_put_contents(APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/init/index_old.html', $old_content);
		$output_str = $this->ReplacPicDomain($output_str);
        file_put_contents($output_file, $content);
		/********************************************
		 * 判断是否是负载均衡服务器
		 * modify by zhangjiasuo 
		 * data 2014-11-22
		 *********************************************/
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
				//$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_TPL_IP'];
			}
			if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
				$com_obj = new Communications();
				$ary_request_data['upfile']="@".$output_file;
				$ary_request_data['file_path']='/Public/Tpl/'.CI_SN. '/' . $dir . '/index.html';
				$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);		
			}
		}
        $ary_log['tl_operation'] = $this->array_operation[2];
        $ary_log['u_name'] = $_SESSION['admin_name'];
        $ary_log['u_id'] = $_SESSION['Admin'];
        $ary_log['u_real_name'] = D('Admin')->where(array('u_id'=>$_SESSION['Admin']))->getField('u_real_name');
        $ary_log['tl_operation_time'] = date('Y-m-d H:i:s');
        $ary_log['tl_model'] = '首页';
        M('template_operation_log',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_log);
        $this->ajaxReturn(true);
    }
    
    /**
     * 恢复上次编辑的页面
     * @author Joe <qianyijun@guanyisoft@guanyisoft.com>
     * @date 2013-07-03
     * @param object $node 要替换的DOM文档节点
     * @param string $mode_class 模块类名
     * @param string $dir 模版名（文件夹名）
     */
    public function huanyuan(){
        $file_url = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/init/index_old.html';
        $content = file_get_contents($file_url);
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/index.html';
        $old_content = file_get_contents($output_file);
		$old_output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $_POST['dir'] . '/init/index_old.html';
		file_put_contents($old_output_file, $old_content);
		$output_str = $this->ReplacPicDomain($output_str);
        file_put_contents($output_file, $content);
		/********************************************
		 * 判断是否是负载均衡服务器
		 * modify by zhangjiasuo 
		 * data 2014-11-22
		 *********************************************/
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_TPL_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(empty($_SESSION['OSS']['GY_OSS_PIC_URL'])){
				//$_SESSION['OSS']['GY_OSS_PIC_URL']=$_SESSION['OSS']['GY_TPL_IP'];
			}
			if(!empty($_SESSION['OSS']['GY_TPL_IP'])){
				$com_obj = new Communications();
				$ary_request_data['upfile']="@".$output_file;
				$ary_request_data['file_path']='/Public/Tpl/'.CI_SN. '/' . $dir . '/index.html';
				$res = $com_obj->httpPostRequest('http://'.$_SESSION['OSS']['GY_TPL_IP'].'/Home/Image/doImage', $ary_request_data, array(), false);		
			}
		}
        $ary_log['tl_operation'] = $this->array_operation[3];
        $ary_log['u_name'] = $_SESSION['admin_name'];
        $ary_log['u_id'] = $_SESSION['Admin'];
        $ary_log['u_real_name'] = D('Admin')->where(array('u_id'=>$_SESSION['Admin']))->getField('u_real_name');
        $ary_log['tl_operation_time'] = date('Y-m-d H:i:s');
        $ary_log['tl_model'] = '首页';
        M('template_operation_log',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_log);
        $this->ajaxReturn(true);
    }
    
    /**
     * 根据模块的类名，找到相应的ThinkPHP代码片段，并将此转化成PHP DOM操作中的DOMNode
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-03
     * @param object $node 要替换的DOM文档节点
     * @param string $mode_class 模块类名
     * @param string $dir 模版名（文件夹名）
     */
    private function getModeNode($node, $mode_class, $dir = 'default') {
        //$meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $file_url = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $dir . '/common/' . $mode_class . '.html';
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-03
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
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-12-24
     * @param object $node 不需要更改的自定义标签
     */

    private function replace_style_common_content($node, $content) {
        return '<div class="show_common_html block" >'.$content.'</div>';
    }
    
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-10
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-10
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_A($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_B($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_C($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_D($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
		
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_E($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_F($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_G($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_H($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_I($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_J($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
		
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_K($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_L($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }	
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_M($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_N($node, $content){
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_O($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	
	/**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_single_Q($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }	
	
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * 仅编辑图片和连接
     * @author wanghaoyu
     * @date 2013-11-14
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_pic_ads($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	
	private function index_pic_ads_A($node, $content) {
        return $content;
    }
	
	private function index_pic_ads_B($node, $content) {
        return $content;
    }
	
	private function index_pic_ads_C($node, $content) {
        return $content;
    }
	
	private function index_pic_ads_D($node, $content) {
        return $content;
    }
	
	private function index_pic_ads_E($node, $content) {
        return $content;
    }
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-15
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
	
	private function index_on_sale($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
	private function index_banner($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }

    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_news_list($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        //替换自定义内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $custom_content = $node->getAttribute('block-dat');
        $ary_custom_content = json_decode($custom_content, TRUE);
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
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
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_product_list_M($node, $content) {
        $content = $this->index_news_list($node, $content);
        return $content;
    }
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-07-04
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_product_list_N($node, $content) {
        $content = $this->index_news_list($node, $content);
        return $content;
    }
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-12-23
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function show_common_html($node, $content) {
        return $content;
    }    
    
    /**
     * 替换模块中的THINKPHP自定义标签当中的设置信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-12-23
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
	private function index_article_list_A($node, $content) {
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
	private function index_article_list_B($node, $content) {
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
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-06-14
     * @param object $node 要替换的DOM文档节点
     * @param string $content 要替换的THINKPHP模版片段
     * @return string $content 替换后的字符串
     */
    private function index_ad_list_A($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_B($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_C($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_D($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_E($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_F($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_G($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_H($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    }
    private function index_ad_list_I($node, $content) {
        //如本模块中有THINKPHP自定义标签则需要进行扩充替换
        return $content;
    } 	
    /**
     * 客户模版默认首页
     * @author
     * @date
     */
    public function index() {

        $dir = $this->_get('dir');
        $file = $this->_get('file');
        $tmplateEdit = new TemplateEdit;
        //本套模版可用的模块列表
        $tmplateInfo = $tmplateEdit->getModInfo(CI_SN, $dir);
        $modSet['newsList'] = $tmplateEdit->getModNewsList();
        $modSet['goodsList'] = $tmplateEdit->getModGoodsList();
        $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexheader.html';
        if(!file_exists($headerTpl)){
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/header.html';

        }else{
            $headerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexheader.html';
        }
        $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexfooter.html';
        if(!file_exists($footerTpl)){
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/footer.html';
        }else{
            $footerTpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/indexfooter.html';
        }
        $tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/preview_' . $file;
        
        
        $this->setTitle(' - 首页预览','TITLE_INDEX','DESC_INDEX','KEY_INDEX');
		$ary_request['index']=1;//判断是否为首页
        $this->assign("ary_request",$ary_request);
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->assign('is_home_edit','1');
       
         $this->display($tpl);
        
    }
    
    /**
     * 获取未嵌套的演示模块的HTML
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-14
     */
    public function getModHtml(){
        $dir = $this->_get('dir');
        $mod = $this->_get('mod');
        if(empty($mod)){
            $this->ajaxReturn(array('status'=>false,'info'=>'请选择您要添加的模块'));
        }
        $tpl = './Public/Tpl/' . CI_SN . '/' . $dir . '/widget/' . $mod . '.html';
        layout(FALSE);
        $this->display($tpl);
    }
    
    /**
     * 获取已经嵌套数据的模块html
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-15
     */
    public function getModDataHtml(){
        $ary_request = $this->_get();
        $dir = htmlspecialchars(trim($this->_get('dir')));
        $mod = htmlspecialchars(trim($this->_get('mod')));
        $data = $ary_request['dat'];
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
     * 查看编辑日志
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-9-24
     */
    public function searchEditLog(){
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = 5;
        if(isset($_GET['u_name']) && !empty($_GET['u_name'])){
            $array_where['u_name'] = $_GET['u_name'];
        }
        if(isset($_GET['u_real_name']) && !empty($_GET['u_real_name'])){
            $array_where['u_real_name'] = $_GET['u_real_name'];
        }
        $timesSearch = 5;
        if(isset($_GET['timesSearch']) && !empty($_GET['timesSearch'])){
            $timesSearch = $_GET['timesSearch'];
        }else{
            $_GET['timesSearch'] = $timesSearch;
        }
        
        
        switch($timesSearch){
            case "2":
                //当日
                $array_where['tl_operation_time'] = array('between',array(date('Y-m-d 00:00:00'),date('Y-m-d H:i:s')));
                break;
            case "3":
                //一天以前
                $prev_day = date('Y-m-d H:i:s',strtotime(date('Y-m-d 00:00:00')) - 86400);
                $array_where['tl_operation_time'] = array('between',array($prev_day,date('Y-m-d H:i:s')));
                break;
            case "4":
                //三天以前
                $three_day = date('Y-m-d H:i:s',strtotime(date('Y-m-d 00:00:00')) - 86400*3);
                $array_where['tl_operation_time'] = array('between',array($three_day,date('Y-m-d H:i:s')));
                break;
            case "5":
                //一周以前
                $one_zhou_day = date('Y-m-d H:i:s',strtotime(date('Y-m-d 00:00:00')) - 86400*7);
                $array_where['tl_operation_time'] = array('between',array($one_zhou_day,date('Y-m-d H:i:s')));
                break;
            case "6":
                //三周以前
                $three_zhou_day = date('Y-m-d H:i:s',strtotime(date('Y-m-d 00:00:00')) - 86400*7*3);
                $array_where['tl_operation_time'] = array('between',array($three_zhou_day,date('Y-m-d H:i:s')));
                break;
            case "7":
                //一个月以前
                $one_mouse_day = date('Y-m-01 00:00:00',strtotime(date('Y',strtotime(date('Y-m-d H:i:s'))).'-'.(date('m',strtotime(date('Y-m-d H:i:s')))-1).'-01'));
                $array_where['tl_operation_time'] = array('between',array($one_mouse_day,date('Y-m-d H:i:s')));
                break;
            case "8":
                //三个月以前
                $three_mouse_day = date('Y-m-01 00:00:00',strtotime(date('Y',strtotime(date('Y-m-d H:i:s'))).'-'.(date('m',strtotime(date('Y-m-d H:i:s')))-3).'-01'));
                $array_where['tl_operation_time'] = array('between',array($three_mouse_day,date('Y-m-d H:i:s')));
                break;
            case "9":
                //半年以前
                $year_day = date('Y-m-01 00:00:00',strtotime(date('Y',strtotime(date('Y-m-d H:i:s'))).'-'.(date('m',strtotime(date('Y-m-d H:i:s')))-6).'-01'));
                $array_where['tl_operation_time'] = array('between',array($year_day,date('Y-m-d H:i:s')));
                break;
            default:
                break;
        }
        $count =  M('template_operation_log',C('DB_PREFIX'),'DB_CUSTOM')->where($array_where)->count();
        $obj_page = new Pager($count, $page_size);
        
        $array_log['page'] = $obj_page->showArr();
        $array_log['list'] = M('template_operation_log',C('DB_PREFIX'),'DB_CUSTOM')->where($array_where)->page($page_no,$page_size)->order('tl_operation_time desc')->select();
        $this->assign($array_log);
        $this->assign('chose',$_GET);
        $this->display('edit_log');
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
    
    private function index_topmenu_A($node, $content){
        return $content;
    }

	private function index_topmenu_B($node, $content){
        return $content;
    }
	
	private function index_topmenu_C($node, $content){
        return $content;
    }
	
	private function index_topmenu_D($node, $content){
        return $content;
    }
	
	private function index_topmenu_E($node, $content){
        return $content;
    }
	
	private function index_topmenu_F($node, $content){
        return $content;
    }
	
	private function index_topmenu_G($node, $content){
        return $content;
    }
	
	private function index_topmenu_H($node, $content){
        return $content;
    }
	
	private function index_topmenu_I($node, $content){
        return $content;
    }
	/**
     * 替换图片原有域名及IP
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-12-01
     * @param string  str_desc
     * @return string str_desc
     */
	function ReplacPicDomain($str_desc = '') {
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){
			$str_desc = D('ViewGoods')->ReplaceItemDescPicDomain($str_desc);
		}else{
			$preg = "/<img.*?src=\"(.+?)\".*?>/i";
			preg_match_all($preg, $str_desc, $match);
			if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
				$match[1]=array_flip(array_flip($match[1]));
				foreach ($match[1] as $key => $val) {
					$ary_tmp_pic_url = explode("/Public/",$val);
					if(count($ary_tmp_pic_url)>1 && empty($ary_tmp_pic_url[0])){
						$str_desc = str_replace($val, C('DOMAIN_HOST').$val, $str_desc);
					}
					if(count($ary_tmp_pic_url)>1 && !empty($ary_tmp_pic_url[0])){
						$str_desc = str_replace($val, C('DOMAIN_HOST').'/Public/'.$ary_tmp_pic_url[1], $str_desc);
					}
				}
			}
		}
    	return $str_desc;
    }
}