<?php

/**
 * 模版可视化编辑器操作类
 *
 * @package Common
 * @stage 7.2
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-06-07
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TemplateEdit {

    /**
     * 获取本套模版可使用的模块信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-07
     * @param string $ci_sn 客户编号
     * @param string $tpl 模版目录名称
     * @return array
     */
    public function getModInfo($ci_sn, $tpl) {
        //未嵌套前的模块文件
        $dir = APP_PATH . 'Public/Tpl/' . $ci_sn . '/' . $tpl . '/widget';
        //嵌套THINKPHP标签后的模块文件
        $dir2 = APP_PATH . 'Public/Tpl/' . $ci_sn . '/' . $tpl . '/common';
        $return = array();
        $widget = scandir($dir);
        foreach ($widget as $v) {
            if ($v != '.' && $v != '..' && false !== strstr($v, '.html')) {
                $className = substr($v, 0, -5);
                $file1 = file_get_contents($dir . '/' . $v);
                $file2 = file_get_contents($dir2 . '/' . $v);
                $reg = '|<\!\-\-(.*?)\-\->|Ui';
                $match = array();
                preg_match_all($reg, $file1, $match);

                foreach ($match[1] as $v) {
                    if (false !== strstr($v, '@title')) {
                        $titles = explode(' ', trim($v), 2);
                    }
                    if (false !== strstr($v, '@desc')) {
                        $descs = explode(' ', trim($v), 2);
                    }
                }

                $data = array(
                    'class' => $className,
                    'title' => $titles[1],
                    'desc' => $descs[1],
                    'demo' => $file1,
                    'code' => $file2,
                );
                $return[$className] = $data;
            }
        }
        return $return;
    }

    ##########################################################################
    ################### 各个模块可用的设置信息 ##################################
    ##########################################################################
    ##########################################################################
    ##########################################################################

    /**
     * 获取新闻列表设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-17
     * @return array 返回可用的文章分类树
     */
    public function getModNewsList() {
        $this->getArticleCat();
        return $this->articleCat;
    }

    private function getArticleCat($pid = 0) {
        $M = D('ArticleCat');
        $data = $M->where(array('parent_id' => $pid))->select();
        if (false !== $data && is_array($data)) {
            foreach ($data as $v) {
                array_push($this->articleCat, $v);
                $this->getArticleCat($v['cat_id']);
            }
        }
    }

    private $articleCat = array();


    /**
     * 获取商品列表设置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-06-17
     * @return array 返回可用的商品分类树
     */
    public function getModGoodsList(){
        return D('ViewGoods')->getCates();
    }


}