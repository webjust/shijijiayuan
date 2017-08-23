<?php

/**
 * 文章资讯相关模型层 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ArticleModel extends GyfxModel {
     /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 资讯列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     * @param 
     * 返回数据变量名	name
	 * 文章分类	cid
	 * 数量	num
	 * 热门标记	hot
	 * 文章标题  atitle
	 * 文章ID   aid
     * @return array
	 * 文章ID	aid
	 * 文章标题	atitle
	 * 文章地址	aurl
	 * 文章分类名称	cname
	 * 文章分类ID	cid
	 * 文章简介	adesc
	 * 配图	apic
	 * 热门标记	hot
	 * 点击量	hits
	 * 发布时间	atime
     */
    public function pageList($tag){
        //实例化缓存
        //$memcaches = new Caches;
		//实例化缓存
		if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
			$memcaches = new Cacheds();
		}else{
			$memcaches = new Caches();
		}			
		//生成一个用来保存 namespace 的 key  
		if($memcaches->getStat()){
			$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
			//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
			if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
		}
        //根据tag获取缓存key
		$page = (isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:$tag['p'];		
        $cache_key = json_encode($tag).$page;
		$cache_key = $ns_key.$cache_key;
		$cache_key = md5($cache_key);
        if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key.CI_SN)){
            return json_decode($ary_return,true);
        }else{
            C('VAR_PAGE', 'p');
            $articleOjb = D('article');
            $title = $tag['atitle'];
            $cid = $tag['cid'];
            $num = $tag['num'];
            $hot = $tag['hot'];
            $aid = $tag['aid'];
            $page = (isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:$tag['p'];
            $pagesize = $tag['pagesize'];
            $where = array(
				'a_status' => 1,
				'a_is_display' => 1,
				'a_startime'=>array('lt',date('Y-m-d H:i:s')),
                'a_endtime'=>array('gt',date('Y-m-d H:i:s')),
			);
			
            if($title){
                $where['fx_article.a_title'] = array('LIKE', '%' . $title . '%');
            }
            if($cid){
                $where['fx_article.cat_id'] = $cid;
            }
            if($aid){
                $where['fx_article.a_id'] = $aid;
            }
            if($hot){
                $where['fx_article.hot'] = $hot;
            }

            //edit by Mithern 分页问题处理
            $page_no = empty($page)?1:$page;
            if($num){
                $page_size = $num;  		
            }else{
                $page_size = empty($pagesize)?20:$pagesize;   		
            }
            $list = $articleOjb->field('a_id,a_title,a_create_time,hot,a_desc,hits,a_is_display,fx_article.cat_id,a_link,fx_article_cat.cat_name,ul_image_path,a_startime,a_endtime')
                            ->join('fx_article_cat on fx_article.cat_id=fx_article_cat.cat_id')
                            ->where($where)
                            ->order('a_order desc,a_create_time desc')
                            ->limit(($page_no-1)*$page_size,$page_size)
                            ->select();
            $count = $articleOjb->where($where)->count();
            $obj_page = new Pager($count, $page_size);
            $page = $obj_page->show();
            $pagearr = $obj_page->showArr();
            //dump($pagearr);die();
            //详情页URL	aurl
            foreach($list as &$item){
                $item['aurl'] = U('Home/Article/articleDetail', array('aid' => $item['a_id']));
            }
            $article_info = array();
			//获取当前时间
			$time = date('Y-m-d H:i:s',time());
            foreach($list as $key=>$article){
                $article_info[$key]['aid'] = $article['a_id'];
                    if(!empty($tag['titlelen']) && isset($tag['titlelen'])){
                    	if(strlen($article['a_title'])>$tag['titlelen']*2){
                    		$article['a_title'] = mb_substr($article['a_title'], 0 ,$tag['titlelen'],"utf-8").'...';
                    	}else{
                    		$article['a_title'] = mb_substr($article['a_title'], 0 ,$tag['titlelen'],"utf-8");
                    	} 
                    }
				
                $article_info[$key]['atitle'] = $article['a_title'];
                $article_info[$key]['alink'] = $article['a_link'];
                $article_info[$key]['aurl'] = $article['aurl'];
                $article_info[$key]['cname'] = $article['cat_name'];
                $article_info[$key]['cid'] = $article['cat_id'];
                $article_info[$key]['adesc'] = $article['a_desc'];
				$article_info[$key]['adesc'] = D('ViewGoods')->ReplaceItemDescPicDomain($article_info[$key]['adesc']);
                $article_info[$key]['apic'] = $article['ul_image_path'];
				$article_info[$key]['apic'] = D('QnPic')->picToQn($article_info[$key]['apic']);				
                $article_info[$key]['hot'] = $article['hot'];
                $article_info[$key]['hits'] = $article['hits'];
                $article_info[$key]['display'] = $article['a_is_display'];
                $article_info[$key]['startime'] = $article['a_startime'];
                $article_info[$key]['endtime'] = $article['a_endtime'];
                $article_info[$key]['atime'] = date('Y年m月d日',strtotime($article['a_create_time']));
                $article_info[$key]['startime'] = date('Y年m月d日H时i分',strtotime($article['a_startime']));
                $article_info[$key]['endtime'] = date('Y年m月d日H时i分',strtotime($article['a_endtime']));
                $article_info[$key]['nowtime'] = date('Y年m月d日H时i分',strtotime($time));						
            }
            unset($list);
            $return = array('pageinfo'=>$page,'list'=>$article_info,'pagearr'=>$pagearr);//赋值分页输出 赋值数据集  
            
            if($memcaches->getStat()){
                $memcaches->C()->set($cache_key.CI_SN, json_encode($return));
            }
            return $return;
        }
        
    }
    
    /**
     * 文章资讯列表详情
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-02
     * @param 
     * 返回数据变量名	name
	 * 文章ID   aid
     * @return array
	 * 文章ID	aid
	 * 文章标题	atitle
	 * 文章地址	aurl
	 * 文章分类名称	cname
	 * 文章分类ID	cid
	 * 文章简介	adesc
	 * 配图	apic
	 * 热门标记	hot
	 * 点击量	hits
	 * 发布时间	atime
	 * 文章详情  acontent
     */
    public function getArticleInfo($tag){
    	$articleOjb = D('article');
    	$aid = $tag['aid'];
    	$where = array();
    	$where['a_status'] = 1;
    	if($aid){
    		$where['a_id'] = $aid;
    	}
		$list = $articleOjb->field('a_id,a_title,a_create_time,hot,ul_image_path,a_desc,hits,a_content,fx_article.cat_id,a_link,fx_article_cat.cat_name')
						->join('fx_article_cat on fx_article.cat_id=fx_article_cat.cat_id')
						->where($where)
						->find();
        //详情页URL	aurl
        $list['aurl'] = U('Home/Article/articleDetail', array('aid' => $list['a_id']));
        $article_info = array();
    	$article_info['aid'] = $list['a_id'];
    	$article_info['atitle'] = $list['a_title'];
    	$article_info['aurl'] = $list['aurl'];
    	$article_info['cname'] = $list['cat_name'];
    	$article_info['cid'] = $list['cat_id'];
    	$article_info['adesc'] = $list['a_desc'];
    	$article_info['apic'] = $list['a_link'];
		$article_info['adesc'] = D('ViewGoods')->ReplaceItemDescPicDomain($article_info['adesc']);
		$article_info['apic'] = D('QnPic')->picToQn($article_info['apic']);			
    	$article_info['hot'] = $list['hot'];
    	$article_info['hits'] = $list['hits'];
    	$article_info['ul_image_path'] = $list['ul_image_path'];
    	$article_info['atime'] = $list['a_create_time'];
    	$article_info['acontent'] = $list['a_content'];
		$article_info['acontent'] = D('ViewGoods')->ReplaceItemDescPicDomain($article_info['acontent']);		
        unset($list);
        return $article_info;//赋值分页输出 赋值数据集  
    }
    
    /**
     * 获取文章类目
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-21
     * @param int $cid 商品分类ID
     * @return array 返回仅由子分类ID组成的数组
     */
    public function getCateInfo($tag) {
		// 为了提高性能，一次性从列表中读取所有信息
		// 然后程序进行1，2，3级排序
		$array = array ();
		$ary_where = array();
		if(!empty($tag['num'])){
			$page_size = $tag['num'];
		}
		if(!empty($tag['recommend'])){
			$ary_where['is_recommend'] = 1;
		}	
		if(!empty($tag['caid'])){
			$ary_where['cat_id'] = $tag['caid'];
		}
		$data = D('Gyfx')->selectAllCache('article_cat',null, $ary_where, array('sort_order'=>'asc'),null,array('page_no'=>1,'page_size'=>$page_size));
		/**$data = D ( 'ArticleCat' )->where ( $ary_where )->order ( array (
				'sort_order' => 'asc'
		) )->limit($limit)->select ();**/
		// 商品类目Url
		foreach ( $data as &$c ) {
			$c ['curl'] = U ( 'Home/Article/articlelist', array (
					'cid' => $c ['cat_id'],
					'pid' => $c['parent_id'] 
			) );
			
			if(!empty($tag['is_show'])){
				$date_time = date('Y-m-d H:00:00');
				$c['list'] = D('Gyfx')->selectAllCache('article','a_id,a_title,a_create_time,hot,a_desc,hits,cat_id,ul_image_path,a_content,a_is_display',array('cat_id'=>$c ['cat_id'],'a_status'=>1,'a_is_display'=>1,'a_startime'=>array('lt',$date_time),
                'a_endtime'=>array('gt',$date_time)), array('a_order'=>'desc'));
				foreach($c['list'] as &$list){
					$list['aurl'] = U('Home/Article/articleDetail', array('aid' => $list['a_id']));
				}
			}
		}
		//dump($data);die();
		if (!$data || empty($data))
         return $array;
        //获取商品类目支持2级 
        $first = array_values(array_filter($data, create_function('$val', 'return $val["parent_id"]=="0";')));
        foreach ($first as &$cate) {
            $second = array_values(array_filter($data, create_function('$val', 'return $val["parent_id"]=="' . $cate["cat_id"] . '";')));
            if($second){$cate['sub'] = $second;}
        }
        return $first;
    }
      
}