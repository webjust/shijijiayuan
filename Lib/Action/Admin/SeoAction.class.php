<?php

/**
 * 后台SEO优化控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-12
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SeoAction extends AdminAction {

    /**
     * 初始化控制器
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU1_4'));
    }

    /**
     * 默认控制器，重定向到SEO列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function index() {
        $this->redirect('Admin/Seo/pageList');
    }

    /**
     * SEO列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function pageList() {
        $this->getSubNav(2, 4, 10);

        $data['list'] = D('SysConfig')->where(array('sc_module' => 'GY_SEO'))->select();
        $this->assign($data);
        $this->display();
    }

    /**
     * 修改SEO项
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function pageEdit() {
        $this->getSubNav(2, 4, 10);
        $key = $this->_get('skey');
        $data['info'] = D('SysConfig')->where(array('sc_module' => 'GY_SEO', 'sc_key' => $key))->find();
        $this->assign($data);
        $this->display();
    }

    /**
     * 保存SEO的修改
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function doEdit() {
        $this->getSubNav(2, 4, 10);
        $key = $this->_post('sc_key');
        $value = $this->_post('sc_value');

        $res = D('SysConfig')->setConfig('GY_SEO', $key, $value);
        if ($res) {
            $this->success('保存成功', U('Admin/Seo/pageList'));
        } else {
            $this->error('保存失败或您未做任何修改');
        }
    }

    /**
     * 生成站点地图缓存
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function pageMap() {
        $this->getSubNav(2, 4, 30);
        $data['info'] = D('SysConfig')->getCfgByModule('GY_SITEMAP');
        $this->assign($data);
        $this->display();
    }

    /**
     * 保存站点地图生成的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-12
     */
    public function doMapSave() {
        $data = $this->_post();
        D('SysConfig')->setConfig('GY_SITEMAP', 'INDEX_FREQ', $data['index_freq']);
        D('SysConfig')->setConfig('GY_SITEMAP', 'CATE_FREQ', $data['cate_freq']);
        D('SysConfig')->setConfig('GY_SITEMAP', 'GOODS_FREQ', $data['goods_freq']);
        D('SysConfig')->setConfig('GY_SITEMAP', 'ARTI_FREQ', $data['arti_freq']);
        D('SysConfig')->setConfig('GY_SITEMAP', 'MAP_FREQ', $data['map_freq']);
        //+++ 清除原有的前台缓存设置 +++++++++++++++++++++++++++++++++++++
        S('sitemap',NULL);
        //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->success('保存成功！点击生成/预览按钮刷新站点地图');
    }

    /**
     * 刷新站点地图缓存，例如设置为永不过期，必须通过此处刷新才能生成
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-21
     */
    public function doMapRefresh(){
        S('sitemap',NULL);
        $this->redirect(U('/Sitemap','','xml'));
    }

    /**
     * 第三方统计脚本设置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-18
     */
    public function pageCount() {
        $this->getSubNav(2, 4, 40);
        $data['info'] = M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM')->where(array('sc_module' => 'GY_COUNT'))->find();
        if($data['info']){
            $data['info']['sc_memo'] = base64_decode($data['info']['sc_memo']);
        }

        $this->assign($data);
        $this->display();
    }

    /**
     * 保存第三方统计脚本
     * @auhtor zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-18
     */
    public function doCount() {
        $M = M('siteConfig',C('DB_PREFIX'),'DB_CUSTOM');
        //此处为避免保存的数据有乱码，尽心base64编码
        $data['sc_memo'] = $this->_post('sc_memo','base64_encode','');

        $data['sc_module'] = 'GY_COUNT';
        $data['sc_title'] = '第三方统计脚本';
        $data['sc_update_time'] = date('Y-m-d h:i:s');

        $find = $M->where(array('sc_module' => 'GY_COUNT'))->find();
        if($find){
            $res = $M->where(array('sc_module' => 'GY_COUNT'))->data($data)->save();

        }else{
            $data['sc_create_time'] = date('Y-m-d h:i:s');
            $res = $M->data($data)->add();

        }

        if($res){
            $this->success('保存成功~');
        }else{
            $this->error('保存失败');
        }
    }
    
    /**
     * 数据与文件缓存设置
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @data 2013-09-25
     */
    public function pageCach(){
        $this->getSubNav(2, 4, 50);
        $config = D('SysConfig');
        $cahe_data = $config->getConfigs("GY_CAHE");
        $path = FXINC . '/Public/Tpl/' . CI_SN . "/";
        $dirs = $this->scan_dir($path);
        if(!empty($cahe_data['File_cahe_name'])){
            $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
            foreach($cahe_data as &$val){
                if($val['sc_key'] == 'File_cahe_name'){
//                    $val['sc_value'] = rtrim(ltrim($val['sc_value'],'./Public/Tpl/'.CI_SN.'/'.TPL.'/'),'/'.$ary_api_conf['GY_TEMPLATE_DEFAULT']['sc_value']);
                    for($i=0;$i<count($dirs); $i++){
                            $str_replace[$i] = '';
                    }
                    foreach($dirs as &$v){
                        $v = '/'.$v.'/';
                    }
                    $val['sc_value'] = ltrim($val['sc_value'],'./Public/Tpl/'.CI_SN.'/'.TPL.'/');
                    $val['sc_value'] = rtrim(preg_replace($dirs,$str_replace,$val['sc_value']),'/');
                }
            }
        }
        $this->assign($cahe_data);
        $this->display();
    }
    
    /**
     * 执行保存缓存设置
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @data 2013-09-25
     */
    public function cachAdd(){
        $data = $this->_post();
        $error_file_name = array('admin','Common','Conf','Lang','Lib','Public','Runtime','Tpl','ucenter');
        $path = FXINC . '/Public/Tpl/' . CI_SN . "/";
        $dirs = $this->scan_dir($path);
        $this_file_name = array($data['File_cahe_name']);
        $check = array_intersect($error_file_name,$this_file_name);
        $new_check = array_intersect($dirs,$this_file_name);
        if(!empty($check)){
            $this->error($this_file_name[0].'为系统文件夹，请重新输入');
        }
        if(!empty($new_check)){
            $this->error($this_file_name[0].'为模板文件夹，请重新输入');
        }
        $config = D('SysConfig');
        $ary_api_conf = D('SysConfig')->getConfigs('GY_TEMPLATE_DEFAULT');
        $url = './Public/Tpl/' . CI_SN . "/";
		//dump($data);
		//dump($ary_api_conf);die();
        foreach ($data as $key=>$val){
            if($key == 'File_cahe_name' && empty($check)){
                $val = strpos($val,$url) ? $val : $url.$val.'/'.$ary_api_conf['GY_TEMPLATE_DEFAULT']['sc_value'].'/';
                if(!is_dir($val)){
                    @mkdir($val,0755,true);
                }
            }
		   $result = $config->setConfig('GY_CAHE',$key,$val);
			if($result === false){
				$this->error('数据有误，请重试');
			}
        }
        $this->success('保存成功！');
    }
    
    /**
     * 删除缓存数据
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @data 2013-09-25
     */
    public function deleteCacheDir(){
        if($_POST['i'] == '1'){
            //清除Memcache缓存
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcache = new Cacheds();
			}else{
				$memcache = new Caches();
			}		
            if($memcache->getStat() == '1'){
                $memcache->C()->flush_all();
                $this->ajaxReturn(array('status'=>true,'msg'=>'清除Memcache缓存成功'));
            }else{
                $this->ajaxReturn(array('status'=>false,'msg'=>'请开启Memcache后再清除缓存'));
            }
        }else{
            //清除文件缓存
            //获取缓存文件目录
            $File_cahe_name = D('SysConfig')->where(array('sc_module'=>'GY_CAHE','sc_key'=>'File_cahe_name'))->getField('sc_value');
            if(file_exists($_SERVER['DOCUMENT_ROOT']. $File_cahe_name.'/')){
                $EsjCahe = new EsjCahe($File_cahe_name);
                $EsjCahe->del_file();
                $this->ajaxReturn(array('status'=>true,'msg'=>'清除文件缓存成功'));
            }else{
                $this->ajaxReturn(array('status'=>false,'msg'=>'文件目录不存在'));
            }
        }
    }

	/**
     * 删除缓存数据
     * @authoe Wangguibin <wangguibin@guanyisoft.com>
     * @data 2015-02-26
     */
    public function deleteMemcache(){
		//清除Memcache缓存
		//实例化缓存
		if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
			$memcache = new Cacheds();
		}else{
			$memcache = new Caches();
		}		
		if($memcache->getStat() == '1'){
			//$memcache->C()->flush_all();
			 //将ns_key的值改变，则以后在访问缓存时，以前时间的将永远不会别访问到，以此来实现批量删除缓存 
            $memcache->C()->set(CI_SN."_namespace_key",time());
			//清空静态文件
			//del_file(CI_SN);
			make_fsockopen('/Script/Batch/delFile');
			//清空runtime
			$runtime_url = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/~runtime.php';
			if(file_exists($runtime_url)){
				unlink($runtime_url);
			}
			$this->success('清除Memcache缓存和静态缓存成功');
		}else{
			$this->error('请开启Memcache后再清除缓存');
		}
		//清空静态文件) "E:/www/fenxiao/branches/7.8.8/Public/TmpHtml/v785/"
		$path_one = $_SERVER['DOCUMENT_ROOT'].'/Public/TmpHtml/' . CI_SN.'/';
		if(file_exists($path_one)){
			unlink($path_one);
		}
    }

    /**
     * @param string $path    模板目录
     * @return array
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-04-08
     */
    function scan_dir($path = './') {
        $path = opendir($path);
        $array = array();
        while (false !== ($filename = readdir($path))) {
            if(file_exists('./Public/Tpl/'.CI_SN.'/'.$filename.'/index.html')){
                $filename != '.' && $filename != '..' && $array[] = $filename;
            }
        }
        closedir($path);
        return $array;
    }
}
