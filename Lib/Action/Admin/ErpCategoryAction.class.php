<?php

/**
 * 后台erp分类控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author listen 
 * @date 2013-02-25
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
 
class ErpCategoryAction extends AdminAction{
    //put your code here
     public function _initialize() {
        parent::_initialize();
    }
     /**
     * erp分类控制器默认页，需要重定向
     * @author listen
     * @date 2013-02-25
     */
    public function index() {
        $this->redirect(U('Admin/ErpCategory/pageList'));
    }
    
    /**
     * 
     * 已下载ERP的分类列表
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-04-25
     */
    public function pageList(){
        $this->getSubNav(3, 1, 30);
        //分类数组
        $ary_category= array();
		$ary_erpCategory = D("ViewGoods")->getInfo();
		//dump($ary_erpCategory);die();
        $this->assign('erpCategory',$ary_erpCategory);
        $this->display();
    }
       
    /**
     * erp分类列表
     * @author listen
     * @date 2013-02-25
     */
    public function erpPageList(){
        $this->getSubNav(3, 1, 30);
        //分类数组
        $ary_category= array();
        $ary_count = $this->erpCategoryList();
        $count = $ary_count['total_results'];
        $page_no = max(1, (int) $this->_get('p', '', 1));
        $page_size = 10;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $ary_erpCategory = $this->erpCategoryList($page_no,$page_size);
        //echo "<pre>";print_r($ary_erpCategory);exit;
        if(!empty($ary_erpCategory['categorys']['category'])){
            foreach($ary_erpCategory['categorys']['category'] as $k => $ary_temp_category){
                //if($ary_temp_category['isleaf'] == 0 && !isset($ary_temp_category['fguid'])){
                    $ary_where = array('erp_code'=>$ary_temp_category['code']);
                    $ary_cat = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
                    if(empty($ary_cat)){
                        //是否同步0不同步
                        $ary_temp_category['is_tp'] = 0;
                    }else{
                        //1同步
                        $ary_temp_category['is_tp'] = 1;
                    }
                    $ary_category[] = $ary_temp_category;
                  
                //}
            }
        }
        //echo "<pre>";print_r($ary_category);exit;
        $this->assign('page',$page);
        $this->assign('erpCategory',$ary_category);
        $this->display();
    }
    /**
     * 获取erp子分类
     * @author liste 
     * @param $ary_category 分类数组
     * @param $fguid 父分类guid
     * @date 2013-02-25
     * @return array $ary_categoryChildern 
     */
    public function getErpCategoryChildren($guid){
        //由于erp 分类条件只能guid 子分类是查找 同步以后本地数据库
        $ary_categoryChildern = array();
        //$condition = "guid='BFD62BF5-7A3F-46C6-A9F2-EA12E4ADAE41'";
        //$condition = "guid='". $fguid . "'";   
        //$ary_categoryChildern = $this->erpCategoryList($page=1, $size=200, $condition);
    }
    
    
    /**
     * 子分类显示页面
     * @author listen
     * @date 2013-02-25
     */
    public function erpChildrenList(){
        
        $guid = $this->_post('guid');
        //$ary_categoryChildern = $this->getErpCategoryChildren($guid);
        //echo $guid;exit;
        if(isset($guid)){
          //由于erp 分类条件只能guid 子分类是查找 同步以后本地数据库
          $ary_childern_list = D("ViewGoods")->getCates($guid);
        }
        $this->assign('childern_list',$ary_childern_list);
        $this->display();
    }
    
   /**
    * 同步分类
    * @author listen 
    * @date 2013-02-26
    * &update 2013-04-24
    * @update @author wangguibin
    */
    public function addErpCategory($ary_category){
    	$cate_obj = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM');
        if(empty($ary_category)){
           return false;
        }else {
	        $_binds = array();
			$_values = array();
			$_binds1 = array();
			$_values1 = array();
			$_cids = array();
			$tmp_category = array();
			$add_category = array();
			$upd_category = array();
			foreach($ary_category as  $k =>$v){
				$_codes[] = $v['code'];
			}
			$where = substr(str_repeat('?,', count($_codes)),0,-1);
			$t_sql = "select gc_id,erp_code from fx_goods_category WHERE erp_code in({$where})";
			$t_sql = $this->execute($t_sql,$_codes);
			$res_cids = $cate_obj->query($t_sql);
			foreach ($res_cids as $cid){
				$_cids[$cid['erp_code']] = !empty($cid['gc_id'])?$cid['gc_id']:"";
			}
			unset($res_cids);
			foreach($ary_category as  $k =>$v){
		        if($v['isleaf'] == 0){
	                    $is_parent = 1;
	                }else {
	                    $is_parent = 0;
	            }
	            $date = date("Y-m-d h:i:s");
				$tmp_category = array(
					'erp_guid' => $v['guid'],
					'gc_is_parent'=>$is_parent,
					'gc_parent_id'=>isset($v['fguid']) ? $v['fguid'] : 0 ,
	                'gc_level'=>empty($v['level'])?0:$v['level'],
	                'gc_name'=>$v['name'],
	                'gc_is_display'=>0,
	                'erp_code'=>$v['code'],
	                'gc_update_time'=>$date,
					'gc_create_time'=>$date,			
				);
				//update
				if(!empty($_cids[$v['code']])){
					$tmp_category['gc_id'] = $_cids[$v['code']];
					$upd_category = $tmp_category;
					$_binds1 = array_merge($_binds1,array_values($upd_category));
					$_value1 = substr(str_repeat('?,', count($upd_category)),0,-1);
					$_values1[] = "({$_value1})";	
				}else{
					//add
					$add_category = $tmp_category;
					$_binds = array_merge($_binds,array_values($add_category));
					$_value = substr(str_repeat('?,', count($add_category)),0,-1);
					$_values[] = "({$_value})";					
				}
			}
			unset($tmp_category);
			$true1 = 1;
			if(!empty($upd_category)){
				$_columns1 = implode(',', array_keys($upd_category));
				$_values1 = implode(',', $_values1);
				$sql1 = "replace into fx_goods_category({$_columns1}) values {$_values1}";	
				$sql1 = $this->execute($sql1,$_binds1);
				$res1 = $cate_obj->execute($sql1);	
				if(!res1){
					$true1 = false;
					//dump($cate_obj->getLastSql());
				}
			}
			$true2 = 1;
			if(!empty($add_category)){
				$_columns = implode(',', array_keys($add_category));
				$_values = implode(',', $_values);
				$sql2 = "replace into fx_goods_category({$_columns}) values {$_values}";	
				$sql2 = $this->execute($sql2,$_binds);
				$res2 = $cate_obj->execute($sql2);	
				if(!$res2){
					//dump($cate_obj->getLastSql());
					$true2 = false;
				}
			}
			if($true1 == false || $true2 == false){
				return false;
			}else{
				return true;
			}
        }
    }
    
    /**
     * 拼接sql
     * 
     * @param array $params prepare SQL 中的参数
     * @return boolean
     * @author wangguibin 
     * @date 2013-04-24
     */
    public function execute($sql,array $params=null)
    {
       $statement = explode('?', $sql);
       if ( count($params) != count($statement)-1 ) {
           $sql = $sql . ' with bind parameters: [' . implode(', ', $params) . ']';
       } else {
            $sql = '';
            foreach ( $params as $i => $bind ) {
              $sql .= $statement[$i]
               . (is_string($bind) ? "'".$bind."'" : $bind);
              }
              $sql .= $statement[count($params)];
            }
       return $sql;
    }
    
    /**
     * 同步全部分类
     * @author listen   
     * @date 2013-02-26
     */
    public function saveAllCategory() {
        //echo "123";exit;
        $ary_post = $this->_post();
        if (empty($ary_post)) {
            //获取分类总数
            $ary_category_count = $this->erpCategoryList(1, 1);
            $count = $ary_category_count['total_results'];
            echo $count;
            exit;
        } else {
            $ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0,'updRows' => 0,'errData' => array());
            $ary_erp_category = $this->erpCategoryList($ary_post['page_no'],$ary_post['page_size']);
            if (!empty($ary_erp_category['categorys']['category'])) {
                $res_category = $this->addErpCategory($ary_erp_category['categorys']['category']); 
                if (!$res_category) {
                	//dump($ary_erp_category['categorys']['category']);die();
                    $ary_res['errRows'] = $ary_res['errRows']+count($ary_erp_category['categorys']['category']);
                } else {
                	$ary_res['succRows'] = $ary_res['succRows']+count($ary_erp_category['categorys']['category']);
                }
            }
            echo json_encode($ary_res);
            exit;
        }
    }
}

?>
