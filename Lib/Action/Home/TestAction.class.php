<?php

/**
 * 前台展厅测试类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-19
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TestAction extends HomeAction{

    public function _initialize() {
        parent::_initialize();

    }

    public function index(){
/**
	echo ini_get('memcache.allow_failover');
	$memcaches = new Caches;
	$ary_return_data = $_SESSION['Members'];
	$cache_key = 'test';
	echo CI_SN;
	 //$memcaches->C()->set($cache_key, json_encode($ary_return_data));
	 writeCache('test_member_1',$ary_return_data);
	//$ary_return = $memcaches->C()->get('test_member');
	$ary_data = getCache('test_member_1');
	//dump($ary_return);
	dump($ary_data);
	die();
**/
    }

    public function testCustomTag(){
        $this->assign('abc','111111111');
        $this->display();
    }

    /**
     * 标签库:测试商品列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-03-26
     */
    public function testGoodsList(){
        $this->assign('title','标签库:测试商品列表');
        $this->setTitle('标签库:测试商品列表');
        $this->display();
    }

    /**
     * 标签库:测试商品详情
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-03-26
     */
    public function testGoodsInfo(){
        $this->assign('title','标签库:测试商品详情');
        $this->setTitle('标签库:测试商品详情');
        $this->display();
    }

    /**
     * 标签库:测试商品分类
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-03-26
     */
    public function testGoodsCate(){
        $this->assign('title','标签库:测试商品分类');
        $this->setTitle('标签库:测试商品分类');
        $this->display();
    }

     /**
     * 标签库:测试商品品牌
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-03-26
     */
    public function testGoodsBrand(){
        $this->assign('title','标签库:测试商品品牌');
        $this->setTitle('标签库:测试商品品牌');
        $this->display();
    }

     /**
     * 标签库:测试公告
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-04-10
     */
    public function testNotice(){
        $this->assign('title','标签库:测试公告');
        $this->setTitle('标签库:测试公告');
        $this->display();
    }

    /**
     * 标签库:测试文章资讯
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-03-26
     */
    public function testArticle(){
        $this->assign('title','标签库:测试文章资讯');
        $this->setTitle('标签库:测试文章资讯');
        $this->display();
    }

    /**
     * 标签库:测试文章资讯详情
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-04-02
     */
    public function testArticleInfo(){
        $this->assign('title','标签库:测试文章资讯详情');
        $this->setTitle('标签库:测试文章资讯详情');
        $this->display();
    }

     /**
     * 标签库:测试商品类型
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2012-04-02
     */
    public function testGoodsType(){
        $this->assign('title','标签库:测试商品类型');
        $this->setTitle('标签库:测试商品类型');
        $this->display();
    }
	
	public function testOcs(){
		$memcaches = new Cacheds(3600);
		for($i=0;$i<=1000;$i++){
			$memcaches->C()->set('t1est'.$i, '11111-'.$i);
		}
	    for($i=0;$i<=1000;$i++){
			echo  $memcaches->C()->get('t1est'.$i);
		}
	}
	public function testMem(){
		$memcaches = new Caches(3600);
		for($i=0;$i<=1000;$i++){
			$memcaches->C()->set('t1est'.$i, '11111-'.$i);
		}
	    for($i=0;$i<=1000;$i++){
			echo  $memcaches->C()->get('t1est'.$i);
		}
	}
	
	public function testOcsNew(){
		$cache = Cache::getInstance();
		for($i=0;$i<=1000;$i++){
			$cache->set('hello'.$i,'world'.$i,3600);
		}
	    for($i=0;$i<=1000;$i++){
			echo $cache->get('hello'.$i);
		}		
		
	}


    public function test(){
        $url ='https://www.baidu.com/';
        if (function_exists('file_get_contents')) {//判断是否支持file_get_contents
            $file_contents = @file_get_contents($url);
            echo $file_contents;
        }
        if ($file_contents == '') {//判断$file_contents是否为空
            echo'wwwjjjjjjjjjjjjjjjjjjjjjj';
            $ch = curl_init();
            $timeout = 30;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            
            curl_close($ch);
        }
    }
	
}