<?php
/**
 * Memcache缓存引擎封装
 * @copyright Copyright (C) 2013, 上海管易软件科技有限公司.
 * @license: BSD
 * @author: Joe <qianyijun@guanyisoft.com>
 * @date: 2013-11-12
 * $Id$
 */
class CacheMemcache {
    private $str_host = "";
    private $int_port = 0;
    private $obj_memcache = null;
    private $expire_time = null;
    
    public function __construct($expire = 3600){
        $this->expire_time = $expire;
    }

    public function setHost($str_host) {
        $this->str_host = $str_host;
    }
	
	public function setExpireTime($expire = 3600){
		$this->expire_time = $expire;
	}
	
	public function getExpireTime(){
		return $this->expire_time;
	}
	
    public function setPort($int_port) {
        $this->int_port = $int_port;
    }

    public function connect() {
        $this->obj_memcache = new Memcache;
        $this->obj_memcache->addServer($this->str_host, $this->int_port);
    }
    
    public function add($str_key, $mixed_value,$flag=0){
        return $this->obj_memcache->add($str_key, $mixed_value,$flag,$this->expire_time);
    }

    public function set($str_key, $mixed_value,$flag=0) {
        return $this->obj_memcache->set($str_key, $mixed_value,$flag,$this->expire_time);
    }

    public function get($str_key) {
        return $this->obj_memcache->get($str_key);
    }

    public function delete($str_key) {
        return $this->obj_memcache->delete($str_key);
    }

    public function flush_all() {
        return $this->obj_memcache->flush();
    }
}