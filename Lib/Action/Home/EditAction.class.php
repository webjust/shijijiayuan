<?php

/**
 * 前台可视化编辑控制器
 *
 * @package Action
 * @subpackage Home
 * @stage 7.2
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-06-07
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class EditAction extends HomeAction {

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 获取本套模版可使用的模块信息
     */
    public function getModInfo() {

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
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/' . $dir . '/index.html';
        $output_str .= "<!--end of main-->";
         //echo $output_str;exit;
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
        $output_file = APP_PATH . 'Public/Tpl/' . CI_SN . '/preview_' . $dir . '/index.html';
        file_put_contents($output_file, $output_str);
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
    private function index_pic_single_B($node, $content) {
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
    
}