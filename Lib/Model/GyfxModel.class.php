<?php

/**
 * 管易分销软件Model基类
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-27
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GyfxModel extends Model{
    protected $connection = 'DB_CUSTOM';
    
    /**
     * 处理字段映射
     * @access public
     * @param array $data 当前数据
     * @author wangguibin
     * @date 2013-05-30
     * @return array
     */
    public function parseFieldsMapToReal($data) {
        // 检查字段映射
        if(!empty($this->item_map)) {
            foreach ($data as $key=>$val){
				if(isset($this->item_map[$data[$key]])){
					$data[$key] = $this->item_map[$data[$key]];
				}
            }
        }
        return $data;
    }
    
    /**
     * 处理字段映射(condion条件变为真实数据)
     * @access public
     * @param string 当前where条件
     * @author wangguibin
     * @date 2013-08-07
     * @return string
     */
    public function getAryWhere($str) {
        // 检查字段映射
        if(!empty($this->item_map)) {
            foreach ($this->item_map as $key=>$val){
				$is_exist = is_int(strpos($str,$key));
				if($is_exist == true){
					$str = str_replace($key,$val,$str);
				}
            }
        }
        return $str;
    }
    
    /**
	 * 处理字段映射为字符串
     * @author chenzongyao@guanyisoft.com
     * @date 2013-08-09
     * return string
	 */
	protected function parseFieldsMaps($array_table_fields,$array_client_fields = array()){
		$aray_fetch_field = array();
        if(!empty($array_client_fields)) {
            foreach($array_table_fields as $field_name => $as_name){
                if(in_array($field_name,$array_client_fields)) {
			        $aray_fetch_field[] = '`' . $as_name . '` as `' . $field_name . '`';
                }
		    }
        }
        else{
            foreach($array_table_fields as $field_name => $as_name){
			     $aray_fetch_field[] = '`' . $as_name . '` as `' . $field_name . '`';
		    }
        }
		if(empty($aray_fetch_field)){
			return "";
		}
		return implode(',',$aray_fetch_field);
	}
	
	/**
	 * 返回查询的一条记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @return array 如果查询成功，返回一条记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function selectOne($table,$ary_field=null, $ary_where=null, $ary_order=null)
	{
		return M($table,C('DB_PREFIX'),'DB_CUSTOM')
				->where($ary_where)
				->field($ary_field)
				->order($ary_order)
				->find();
	}
	
	/**
	 * 返回查询的一条缓存记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @return array 如果查询成功，返回一条记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-11-22
	 */
	public function selectOneCache($table,$ary_field=null, $ary_where=null, $ary_order=null,$time=3600)
	{
		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->field($ary_field)->order($ary_order);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//echo $str_sql;exit;
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->field($ary_field)->order($ary_order)->find();
		}else{       	
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//生成一个用来保存 namespace 的 key  
			if($memcaches->getStat()){
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			$cache_key = md5($cache_key);
	        if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
	            return json_decode($ary_return,true);
	        }else{
	        	$ary_return_data = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->field($ary_field)->order($ary_order)->find();          
			   //写入缓存
	            if($memcaches->getStat()){
	                $memcaches->C()->set($cache_key, json_encode($ary_return_data));
	            }
	        	return $ary_return_data;
	        }
		}
	}
	
	/**
	 * 删除一条缓存记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @return array 如果查询成功，返回一条记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-04-15
	 */	
	public function deleteOneCache($table,$ary_field=null, $ary_where=null, $ary_order=null,$time=3600)
	{
		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->field($ary_field)->order($ary_order);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//echo $str_sql;exit;
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return false;
		}else{        
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//生成一个用来保存 namespace 的 key  
			if($memcaches->getStat()){
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			$cache_key = md5($cache_key);
	        if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
	            return  $memcaches->C()->delete($cache_key);
	        }else{
				return false;
	        }
		}
	}
	
	/**
	 * 返回查询的一条缓存记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @return array 如果查询成功，返回一条记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-11-22
	 */
	public function selectOneGyCache($table,$ary_field=null, $ary_where=null, $ary_order=null)
	{
		$obj_query = M($table,C('GY_PREFIX'), 'DB_CENTER')->where($ary_where)->field($ary_field)->order($ary_order);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if(!ini_get('memcache.allow_failover') || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0)){
			return M($table,C('GY_PREFIX'), 'DB_CENTER')->where($ary_where)->field($ary_field)->order($ary_order)->find();
		}else{        
			//实例化缓存
	        //$memcaches = new Caches;
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds();
			}else{
				$memcaches = new Caches();
			}				
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
	        if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return json_decode($ary_return,true);
	        }else{
	        	$ary_return_data = M($table,C('GY_PREFIX'), 'DB_CENTER')->where($ary_where)->field($ary_field)->order($ary_order)->find();
	            //写入缓存
	            if($memcaches->getStat()){
	                $memcaches->C()->set($cache_key, json_encode($ary_return_data));
	            }
	        	return $ary_return_data;
	        }
		}
	}
	
	/**
	 * 返回所有查询记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @param mixed $limit limit 页数：page、每页显示:page_no
	 * @return array 如果查询成功，返回所有记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function selectAll($table,$ary_field=null, $ary_where=null, $ary_order=null,$ary_group=null,$ary_limit=null)
	{
		return M($table,C('DB_PREFIX'),'DB_CUSTOM')
				->where($ary_where)
				->field($ary_field)
				->order($ary_order)
				->group($ary_group)
				->limit(($ary_limit['page_no']-1)*$ary_limit['page_size'],$ary_limit['page_size'])
				->select();
	}
	
	/**
	 * 返回所有查询缓存记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @param mixed $limit limit 页数：page、每页显示:page_no
	 * @return array 如果查询成功，返回所有记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-11-22
	 */
	public function selectAllCache($table,$ary_field=null, $ary_where=null, $ary_order=null,$ary_group=null,$ary_limit=null,$time=3600)
	{
		if(is_array($ary_limit)){
			if(isset($ary_limit['limit']) && !empty($ary_limit['limit'])){
				$limit = $ary_limit['limit'];
			}else{
				$limit = ($ary_limit['page_no']-1)*$ary_limit['page_size'].','.$ary_limit['page_size'];
			}
		}else{
			$limit = $ary_limit;
		}


		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')
					->where($ary_where)
					->field($ary_field)
					->order($ary_order)
					->group($ary_group)
					->limit($limit);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return $obj_query->query($str_sql);
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);	
			}	
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			$cache_key = md5($cache_key);
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return json_decode($ary_return,true);
			}else{
				$ary_return_data = $obj_query->query($str_sql);
				//写入缓存
				if($memcaches->getStat()){
					$memcaches->C()->set($cache_key, json_encode($ary_return_data));
				}
				return $ary_return_data;
			}
		}
	}

	/**
	 * 删除所有查询缓存记录
	 *
	 * @param mixed $fields 查询字段
	 * @param mixed $where 查询条件
	 * @param mixed $order 排序字段
	 * @param mixed $limit limit 页数：page、每页显示:page_no
	 * @return array 如果查询成功，返回所有记录; 否则返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-04-15
	 */
	public function deleteAllCache($table,$ary_field=null, $ary_where=null, $ary_order=null,$ary_group=null,$ary_limit=null,$time=3600)
	{
		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')
					->where($ary_where)
					->field($ary_field)
					->order($ary_order)
					->group($ary_group)
					->limit(($ary_limit['page_no']-1)*$ary_limit['page_size'],$ary_limit['page_size']);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return false;
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}	
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			$cache_key = md5($cache_key);
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return $memcaches->C()->delete($cache_key);
			}else{
				return false;
			}
		}
	}
	
	/**
	 * 插入1行或多行记录
	 *
	 * @param array $rows 可以是1行记录，也可以是多行记录 obj_replace表示是否执行replace into
	 * @return boolean 如果成功插入所有记录，返回 true; 如果记录格式不正确，返回 false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function insert($table,$ary_data,$obj_replace)
	{
		if($obj_replace == '1'){
			return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->add($ary_data,array(),true);
		}else{
			return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->data($ary_data)->add();
		}
	}
	
	/**
	 * 更新记录
	 *
	 * @param mixed $set 更新字段
	 * @param mixed $where 更新条件
	 * @return boolean
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function update($table,$ary_where,$ary_data)
	{
		return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->data($ary_data)->save();
	}
	
	/**
	 * 删除记录
	 *
	 * @param mixed $where 删除条件
	 * @return boolean
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function deleteInfo($table,$ary_where)
	{
		return  M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->delete();
	}
	
	/**
	 * 得到总数
	 *
	 * @param mixed $where 得到总数条件
	 * @return Number
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-10-28
	 */
	public function getCount($table,$ary_where)
	{
		return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
	}
	
	/**
	 * 得到总数缓存
	 *
	 * @param mixed $where 得到总数条件
	 * @return Number
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-11-22
	 */
	public function getCountCache($table,$ary_where,$time=3600)
	{
		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//根据tag获取缓存key
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return json_decode($ary_return,true);
			}else{
				$ary_return_count = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
				//写入缓存
				if($memcaches->getStat()){
					$memcaches->C()->set($cache_key, json_encode($ary_return_count));
				}
				return $ary_return_count;
			}
		}
	}
	//删除queryCountCache缓存
	public function deleteCountCache($table,$ary_where,$time=3600)
	{
		$obj_query = M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where);
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return M($table,C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//根据tag获取缓存key
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = $ns_key.CI_SN.$cache_key;
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return $memcaches->C()->delete($cache_key);
			}else{
				return false;
			}
		}
	}
	
	/**
	 * 删除query缓存
	 *
	 * @param mixed $where 得到总数条件
	 * @return Number
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-10-15
	 */	
	public function deleteQueryCache($obj_query,$type,$time)
	{
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return false;
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}	
			//根据tag获取缓存key
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = md5($ns_key.CI_SN.$cache_key);
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return $memcaches->C()->delete($cache_key);
			}else{
				return false;
			}
		}
	}	
	/**
	 * 得到query缓存
	 *
	 * @param mixed $where 得到总数条件
	 * @return Number
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-01-15
	 */
	public function queryCache($obj_query,$type,$time=3600)
	{
		//拼接sql作为memcache的key值
		$str_sql =  $obj_query->buildSql();
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
            if(empty($type)){
				return $obj_query->query($str_sql);
			}else{
				$tmp_ary_res = $obj_query->query($str_sql);
				if($type == 'find'){
					return $tmp_ary_res[0];
				}else{
					return $tmp_ary_res;
				}
			}
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//根据tag获取缓存key
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = md5($ns_key.CI_SN.$cache_key);
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return json_decode($ary_return,true);
			}else{
				if(empty($type)){
					$ary_res = $obj_query->query($str_sql);
				}else{
					$tmp_ary_res = $obj_query->query($str_sql);
					if($type == 'find'){
						$ary_res = $tmp_ary_res[0];
					}else{
						$ary_res = $tmp_ary_res;
					}
				}
//                echo'<pre>';print_r($ary_res);die;
				//写入缓存
				if($memcaches->getStat()){
					$memcaches->C()->set($cache_key, json_encode($ary_res));
				}
				return $ary_res;
			}
		}
	}	

	/**
	 * 得到sql缓存
	 *
	 * @param mixed $where 得到总数条件
	 * @return Number
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-010-14
	 */
	public function querySqlCache($str_sql,$time=3600)
	{
		//php插件里是否开启memcache
		if((!ini_get('memcache.allow_failover') && (C('MEMCACHED_OCS') != true)) || (isset($_SESSION['memcache_on']) && $_SESSION['memcache_on'] == 0) ){
			return  M("",C("DB_PREFIX"),"DB_CUSTOM")->query($str_sql);
		}else{
			//实例化缓存
			if(C('DATA_CACHE_TYPE') == 'MEMCACHED' && C('MEMCACHED_OCS') == true){
				$memcaches = new Cacheds($time);
			}else{
				$memcaches = new Caches($time);
			}
			//根据tag获取缓存key
			if($memcaches->getStat()){
				 //生成一个用来保存 namespace 的 key  
				$ns_key = $memcaches->C()->get(CI_SN."_namespace_key");  
				//如果 key 不存在，则创建，默认使用当前的时间戳作为标识
				if($ns_key===false) $memcaches->C()->set(CI_SN."_namespace_key",time());  
			}
	        //根据tag获取缓存key
	        $cache_key = json_encode($str_sql);
			$cache_key = md5($ns_key.CI_SN.$cache_key);
			if($memcaches->getStat() && $ary_return = $memcaches->C()->get($cache_key)){
				return json_decode($ary_return,true);
			}else{
				$ary_res = M("",C("DB_PREFIX"),"DB_CUSTOM")->query($str_sql);
//                echo'<pre>';print_r($ary_res);die;
				//写入缓存
				if($memcaches->getStat()){
					$memcaches->C()->set($cache_key, json_encode($ary_res));
				}
				return $ary_res;
			}
		}
	}
	
}