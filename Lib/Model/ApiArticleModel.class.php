<?php
/**
 * 文章模型层 Model
 * @package Model
 * @version 7.9
 * @author hcaijin
 * @date 2015-12-30
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiArticleModel extends GyfxModel {

    private $article;
    //private $articleCat;

	/**
	 * 构造方法
	 * @author hcaijin <huangcaijin@guanyisoft.com>
	 * @date 2015-12-30
	 */
    protected $item_map=array(
         'aid'=>'a_id',
         'cid'=>'fx_article.cat_id',
         'title'=>'a_title',
         'content'=>'a_content',
         'author'=>'a_author',
         'author_email'=>'a_author_email',
         'keywords'=>'a_keywords',
         'create_time'=>'a_create_time',
         'update_time'=>'a_update_time',
         'link'=>'a_link',
         'description'=>'a_description',
         'image_path'=>'ul_image_path',
         'pid'=>'p_id',
         'status'=>'a_status',
         'is_display'=>'a_is_display',
         'ishot'=>'hot',
         'hitsNums'=>'hits',
         'adesc'=>'a_desc',
         'aorder'=>'a_order',
         'start_time'=>'a_startime',
         'end_time'=>'a_endtime',
         'cname'=>'cat_name',
         'cdesc'=>'cat_desc',
         'corder'=>'sort_order',
         'parentId'=>'parent_id',
         'isRecommend'=>'is_recommend',
    );

    public function __construct() {
        $this->article = M('article', C('DB_PREFIX'), 'DB_CUSTOM');
        //$this->articleCat = M('article_cat', C('DB_PREFIX'), 'DB_CUSTOM');
    }

    /**
    * 查询文章信息
    * request params
    * @author hcaijin
    * @date 2015-12-30
    */
    public function getArticleList($array_params=array()) {
        $ary_fields = explode(',',$array_params['fields']);
        $fields = $this->parseFieldsMapToReal($ary_fields);
        $fields = implode(',',$fields);
        $ary_where = '';
        $ary_order = '';
        $ary_page_no = 1;
        $ary_page_size = 10;
        $ary_orderby = '';
        if (isset($array_params["condition"]) && !empty($array_params["condition"])) {
            $ary_where['_string'] = mb_convert_encoding($array_params["condition"],"utf-8","gb2312");
            foreach($this->item_map as $key=>$val){
                if(strstr($ary_where['_string'],$key))
                {
                     $ary_where['_string'] = str_replace($key,$val,$ary_where['_string']);
                }
            }
        }
        if (isset($array_params["page_no"]) && !empty($array_params["page_no"])) {
            $ary_page_no = $array_params["page_no"];
        }
        if (isset($array_params["page_size"]) && !empty($array_params["page_size"])) {
            $ary_page_size = $array_params["page_size"];
        }
        foreach($this->item_map as $key=>$val){
            if(strstr($val,$array_params["orderby"]))
            {
                 $array_params["orderby"] = str_replace($key,$val,$array_params["orderby"]);
            }
        }
        if (isset($array_params["orderby"]) && !empty($array_params["orderby"])) {
            if (isset($array_params["orderbytype"]) && !empty($array_params["orderbytype"])) {
                $ary_orderby = $array_params["orderby"].' '.$array_params["orderbytype"];
            }else{
                $ary_orderby = $array_params["orderby"].' ASC';
            }
        }
        //是否查询缓存 默认查缓存
        $isCache = $array_params['notCache'] == 1 ? 0 : 1;
        if($isCache == 1){
            $obj_query = $this->article
                ->field($fields)
                ->join('fx_article_cat on(fx_article_cat.cat_id = fx_article.cat_id)')
                ->where($ary_where)
                ->limit(($ary_page_no-1)*$ary_page_size,$ary_page_size)
                ->order($ary_orderby);
            $article_list = D("Gyfx")->queryCache($obj_query);
            $article_count = D("Gyfx")->getCountCache('article',$ary_where);
        }else{
            $article_list = $this->article
                ->field($fields)
                ->join('fx_article_cat on(fx_article_cat.cat_id = fx_article.cat_id)')
                ->where($ary_where)
                ->limit(($ary_page_no-1)*$ary_page_size,$ary_page_size)
                ->order($ary_orderby)
                ->select();
            $article_count = $this->article->where($ary_where)->count();
        }
        return array('count'=>$article_count,'items'=>$article_list);
    }

}
