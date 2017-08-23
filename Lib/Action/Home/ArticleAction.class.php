<?php

/**
 * 前台文章展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.1
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ArticleAction extends HomeAction{

    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function _initialize() {
        parent::_initialize();

    }

    /**
     * 文章详情页
     * @params 文章ID:aid
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function articleDetail() {

        $this->setTitle('文章详情页');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/article_content.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/article_content.html';
        }
        if(TPL == 'bimai') {
            $ary_request = $this->_request();
            //显示页面
            $this->assign('ary_request', $ary_request);
            $where = array(
                'cid' => $ary_request['cid']
            );
            $cat_where = array(
                'cat_id' => $ary_request['cid']
            );
            $cate = D('ArticleCat')->where($cat_where)->find();
            $this->assign('cate', $cate);
            $ary_article_list = D('Article')->pageList($where);
            //dump($ary_article_list);die;
            $this->assign('ary_article_list', $ary_article_list['list']);
        }
        else {
            $config = D('SysConfig');
            $cahe_data = $config->getConfigs("GY_CAHE");
            $aid = $this->_get('aid');
            if($cahe_data['File_cahe_stat']['sc_value'] && $this->_get('cache') != 't'){
                $EsjCahe = new EsjCahe($cahe_data['File_cahe_name']['sc_value'],'acticel'.$aid.CI_SN,$cahe_data['File_cahe_time']['sc_value']);
                if($EsjCahe->read_cache($aid.CI_SN)){
                }else{
//                $filecontent = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/Home/Article/articleDetail/aid/'.$this->_get('aid').'/cache/t');
                    $this->assign('aid',$aid);
                    $filecontent = $this->fetch($tpl);
                    $content = $EsjCahe->create_cache($filecontent,$aid.CI_SN);
                    $create_file = $EsjCahe->read_cache($aid.CI_SN);
                }
            }

            //获取文章类目
            $ary_article_category = D('Article')->getCateInfo();
            $tag = array(
                'name'=>'page3',
                'num' => 10
            );
            if(!empty($ary_article_category) && is_array($ary_article_category)){
                foreach($ary_article_category as &$value){
                    $tag['cid'] = $value['cat_id'];
                    $artilce_title = D('Article')->pageList($tag);
                    $value['list'] = $artilce_title['list'];
                }
            }
			$ary_article_info = D('Article')->where(array('a_id'=>$aid))->field('a_keywords,a_description')->select();
			$this->setKeywords($ary_article_info[0]['a_keywords']);
			$this->assign('page_description',$ary_article_info[0]['a_description']);
            $this->assign('ary_article_category',$ary_article_category);
            $this->assign('aid',$aid);
        }
        $this->display($tpl);
    }

    public function myarticleDetail(){
        $ary_article_info = D('Article')->where(array('a_id'=>3))->field('a_title,a_content')->select();
        $this->assign('aid','123456');
        //$this->assign('a_content',$ary_article_info[0]['a_content']);
        //echo $ary_article_info[0]['a_content'];
        $tpl = './Public/Tpl/' . CI_SN . '/diy/myarticle_content.html';
        file_put_contents($tpl, $ary_article_info[0]['a_content']);
        //echo $tpl;
        $this->display($tpl);
        //var_dump($ary_article_info);
    }

    /**
     * 文章列表页
     * @params 文章ID:cid
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function articleList() {

    	$this->setTitle('文章列表页');
        //显示页面
        $ary_request = $this->_request();
        $this->assign('articleInfo',$ary_request);
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/article_list.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/article_list.html';
        }
        $this->assign('ary_request',$ary_request);
        if(TPL == 'bimai') {
            $where = array(
                'cid' => $ary_request['cid']
            );
            $ary_article_list = D('Article')->pageList($where);
            $this->assign('ary_article_list', $ary_article_list['list']);
            $cat_where = array(
                'parent_id' => $ary_request['pid']
            );
            $ary_cate = D('ArticleCat')->where($cat_where)->select();
            $this->assign('ary_cate', $ary_cate);
            $cat_info_where = array(
                'c_id' => $ary_request['pid']
            );
            $parent_cate = D('ArticleCat')->where($cat_info_where)->find();
            $this->assign('parent_cate', $parent_cate);
        }
        else {
            $ary_article_category = D('Article')->getCateInfo();
//            $this->assign('article_category',$ary_article_category);
        }
		/*文章详情和列表在同一页面显示*/
		$aid='1';//默认显示文章ID
		$cate_name='';
		foreach($ary_article_category as $value){
			if($value['cat_id'] ==$ary_request['cid']){
				$cate_name =$value['cat_name'];
				foreach($value['list'] as $val){
					if($val['a_id']>0){
						$aid=$val['a_id'];
						continue 2;
					}
				}
			}
		}
		$this->assign('aid',$aid);
		$this->assign('cate_name',$cate_name);
		/*文章详情和列表在同一页面显示*/
		/*文章详情和列表在同一页面显示*/
		$aid='1';//默认显示文章ID
		$cate_name='';


		foreach($ary_article_category as  $key=>$value){
            $catawhere = array();
            array_push($catawhere,$value['cat_id']);
            if(!empty($value['sub'])){
                foreach($value['sub'] as $kk => $vv){
                    array_push($catawhere,$vv['cat_id']);
                }
            }
            $ary_article_category[$key]['list']= array();
            $list =D('article') ->field('a_id,a_title,a_create_time,hot,a_desc,hits,a_is_display,fx_article.cat_id,a_link,fx_article_cat.cat_name,ul_image_path,a_startime,a_endtime')
                ->join('fx_article_cat on fx_article.cat_id=fx_article_cat.cat_id')
                ->order('a_order desc,a_create_time desc')
                ->where(array('fx_article_cat.cat_id'=>array('in',$catawhere)))
                ->select();
            array_push($ary_article_category[$key]['list'],$list);
			if($value['cat_id'] ==$ary_request['cid']){
				$cate_name =$value['cat_name'];
				foreach($value['list'] as $val){
					if($val['a_id']>0){
						$aid=$val['a_id'];
						continue 2;
					}
				}
			}
		}

        $this->assign('article_category',$ary_article_category);
		$this->assign('aid',$aid);
		$this->assign('cate_name',$cate_name);
		/*文章详情和列表在同一页面显示*/
        //dump($ary_article_category);die();
        $this->display($tpl);
    }

}
