<?php
ob_start();
/**
 * 实现文件类缓存
 * @package Common
 * @stage 7.4
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-09-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class EsjCahe{
    
    private $cache_folder = null;
    private $wroot_dir = null;
    private $cacher_create_time = null;
    private $cacher_file_name = null;
    
    /**
     * 构造方法
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-09-24
     */
    public function __construct($cahe_foldername,$cahe_name = '',$cahe_time=100){
        ob_start();
        $this->wroot_dir = $_SERVER['DOCUMENT_ROOT'];
        $this->cache_folder = $_SERVER['DOCUMENT_ROOT'].$cahe_foldername;
        $this->cacher_create_time = $cahe_time;
        $this->cacher_file_name = $cahe_name;
    }
    
    /**
     * 读取缓存文件
     *
     */
    public function read_cache($aid){
        try{
            if($this->create_folder($this->cache_folder)){
                $this->get_cache($aid);
            }else{
                echo "缓存文件夹创建失败！";return false;
            }
        }catch(Exception $e){
            echo $e;return false;
        }
    }
    
    /**
     * 建立缓存文件
     * @parame $cache_content 文件内容
     * @return bool 创建是否成功
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function create_cache($cache_content = 'Hello Word!',$aid){
        $filename = $this->get_filename($aid);
        if($filename != ''){
            try{
                file_put_contents($filename,$cache_content);
                return true;
            }catch(Exception $e){
                return "文件缓存写入失败！".$e;
            }
        }
        return true;
    }
    
    /**
     * 取得缓存中所有文件
     *
     */
    public function list_file(){
        $path = $this->cache_folder;
        if($handle = opendir($path)){
            while(false !== ($file = readdir($handle))){
                if($file!= '.' && $file!= '..'){
                    $path_one = $path.'/'.$file;
                    if(file_exists($path_one)){
                        $result[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $result;
    }
    
    /**
     * 删除缓存中所有文件
     *
     */
    public function del_file(){
        $path = $this->cache_folder;
        if($handle = opendir($path)){
            while(false !== ($file = readdir($handle))){
                if($file != '.' && $file != '..'){
                    $path_one = $path.'/'.$file;
                    if(file_exists($path_one)){
                        unlink($path_one);
                    }
                }
            }
            closedir($handle);
        }
        return true;
    }
    
    //判断缓存文件夹是否存在
    private function exist_folder($folder){
        if(file_exists($this->wroot_dir.'/'.$folder.TPL)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 创建缓存文件夹
     *
     */
    private function create_folder($folder){
        if(!$this->exist_folder($folder)){
            try{
                mkdir($this->wroot_dir.'/'.$folder.TPL,0777);
                chmod($this->wroot_dir.'/'.$folder.TPL,0777);
                return true;
            }catch(Exception $e){
                $content = $this->get_cache();//输出缓存
            }
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * 读取缓存文件
     *
     */
    private function get_cache($aid){
        $file_name = $this->get_filename($aid);
        if(file_exists($file_name) && ((filemtime($file_name)+$this->cacher_create_time)>time())){
            $content = file_get_contents($file_name);
            if($content){
                echo $content;
                ob_end_flush();
                exit;
                
            }else{
                echo  "文件读取失败！";exit;
            }
        }
    }
    
    /**
     * 返回文件名
     *
     */
    private function get_filename($aid){
//        $filename = $file_name = $this->wroot_dir.'/'.$this->cache_folder.'/'.TPL.'/'.md5($this->cacher_file_name).'.html';
          $aid = !empty($aid) ? $aid.'.':'';
        $filename = $this->cache_folder.$aid.md5($this->cacher_file_name).'.html';
        return $filename;
    }
}