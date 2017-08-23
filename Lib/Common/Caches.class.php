<?php
define('MEMCACHE_ROOT', dirname(__FILE__) . '/');
/**
 * Memcaches 缓存类
 * @package Common
 * @date 2013-09-18
 */
class Caches{
    private $Memcache_stat = 0;
    private $Memcache_time = 3600;
    private $Memcache_host = '127.0.0.1';
    private $Memcache_port = '11211';
    public function __construct($set_time=''){
        $cahe_data = $this->getCacheConfig($set_time);
    }
    /**
     * 实例化Caches类，调用C方法开启Memcache缓存
     *
     */
    public function C() {
        //判断当前是否开启Memcahe缓存
        if (!isset($GLOBALS["__CacheObject"]) && $this->Memcache_stat && ini_get('memcache.allow_failover')) {
			
            $GLOBALS["__CacheObject"] = null;
            $str_cache_type = ucfirst('memcache');
            $str_class_file = MEMCACHE_ROOT."caches/Cache".$str_cache_type.".class.php";
            $str_class = "Cache".$str_cache_type;
            if (file_exists($str_class_file)) {
                include_once($str_class_file);
                $GLOBALS["__CacheObject"] = new $str_class($this->Memcache_time);
                switch ($str_cache_type) {
                    case "Memcache":
                        $GLOBALS["__CacheObject"]->setHost($this->Memcache_host);
                        $GLOBALS["__CacheObject"]->setPort($this->Memcache_port);
                        break;
                    default:
                        break;
                }
                $GLOBALS["__CacheObject"]->connect();
                return $GLOBALS["__CacheObject"];
            }
            else {
                return false;
            }
        }else{
			if (isset($GLOBALS["__CacheObject"]) && $this->Memcache_stat && ini_get('memcache.allow_failover')) {
				//$GLOBALS["__CacheObject"]->setExpireTime($this->Memcache_time);
				$expire_time = $GLOBALS["__CacheObject"]->getExpireTime();
				if($expire_time != $this->Memcache_time){
					$GLOBALS["__CacheObject"]->setExpireTime($this->Memcache_time);
				}				
			}
		}
        return $GLOBALS["__CacheObject"];
    }
    
    /**
     * 返回Memcache
     *
     */
    public function getStat(){
        return $this->Memcache_stat;
    }
    
    /**
     * 获取Memcache缓存基本配置
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-04-18
     */
    private function getCacheConfig($set_time){
        if(C('MEMCACHE_STAT') == '1'){
            $this->Memcache_stat = C('MEMCACHE_STAT');
            $this->Memcache_host = C('MEMCCACHE_HOST');
            $this->Memcache_port = C('MEMCCACHE_PORT');
            $this->Memcache_time = C('MEMCCACHE_TIME');
        }else{
			$ary_tmp_result = D('SysConfig')->getCfgByModule("GY_CAHE");
            C('MEMCACHE_STAT',$ary_tmp_result['Memcache_stat']);
            C('MEMCCACHE_HOST',$ary_tmp_result['Memcache_host']);
            C('MEMCCACHE_PORT',$ary_tmp_result['Memcache_port']);
            C('MEMCCACHE_TIME',$ary_tmp_result['Memcache_time']);
            $this->Memcache_stat = C('MEMCACHE_STAT');
            $this->Memcache_host = C('MEMCCACHE_HOST');
            $this->Memcache_port = C('MEMCCACHE_PORT');
            $this->Memcache_time = C('MEMCCACHE_TIME');
        }
		if(isset($set_time) && !empty($set_time)){
			C('MEMCCACHE_TIME',$set_time);
			$this->Memcache_time = $set_time;
		}
        return true;
    }

}