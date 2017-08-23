<?php
/**
 * OCS缓存引擎封装
 * @copyright Copyright (C) 2015, 上海管易软件科技有限公司.
 * @license: BSD
 * @author: wangguibin <wangguibin@guanyisoft.com>
 * @date: 2015-06-02
 * $Id$
 */
class CacheMemcacheds {
    private $obj_memcached = null;
    private $expire_time = null;
	
    public function __construct($expire = 3600){
        $this->expire_time = $expire;
    }
	public function setExpireTime($expire = 3600){
		$this->expire_time = $expire;
	}
	public function getExpireTime(){
		return $this->expire_time;
	}	
    public function connect() {
        $this->obj_memcached = Cache::getInstance();
    }
    
    public function set($str_key, $mixed_value) {
        return $this->obj_memcached->set($str_key, $mixed_value,$this->expire_time);
    }

    public function get($str_key) {
        return $this->obj_memcached->get($str_key);
    }

    public function delete($str_key) {
        return $this->obj_memcached->rm($str_key);
    }

    public function flush_all() {
        return $this->obj_memcached->clear();
    }
	
	public function __destruct(){
		//$this->obj_memcached->quit();
	}
}