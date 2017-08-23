<?php
define('Memcached_ROOT', dirname(__FILE__) . '/');
/**
 * Memcacheds 缓存类
 * @package Common
 * @date 2015-06-02
 */
class Cacheds{
    private $Memcached_stat = 0;
    private $Memcached_time = 3600;
    public function __construct($set_time=''){
        $cahe_data = $this->getCacheConfig($set_time);
    }
    /**
     * 实例化Caches类，调用C方法开启Memcached缓存
     *
     */
    public function C() {
        //判断当前是否开启Memcahe缓存
        if (!isset($GLOBALS["__CachedObject"]) && $this->Memcached_stat) {
            $GLOBALS["__CachedObject"] = null;
            $str_cache_type = ucfirst('Memcacheds');
            $str_class_file = Memcached_ROOT."caches/Cache".$str_cache_type.".class.php";
            $str_class = "Cache".$str_cache_type;
            if (file_exists($str_class_file)) {
                include_once($str_class_file);
                $GLOBALS["__CachedObject"] = new $str_class($this->Memcached_time);
                $GLOBALS["__CachedObject"]->connect();
                return $GLOBALS["__CachedObject"];
            }
            else {
                return false;
            }
        }
        return $GLOBALS["__CachedObject"];
    }
    
    /**
     * 返回Memcached
     *
     */
    public function getStat(){
        return $this->Memcached_stat;
    }
    
    /**
     * 获取Memcached缓存基本配置
     * @author wanggubin <wanggubin@guanyisoft.com>
     * @date 2015-06-02
     */
    private function getCacheConfig($set_time){
        if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
            $this->Memcached_stat = 1;
			$this->Memcached_time = C('DATA_CACHE_TIME');
			if(!empty($set_time)){
				$this->Memcached_time = $set_time;
			}
        }
        return true;
    }

}