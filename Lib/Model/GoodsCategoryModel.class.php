<?php

/**
 * 商品分类相关模型层 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsCategoryModel extends GyfxModel {
     /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
     
    /**
     *  根据分类代码与名称新增类目
     * @author wangguibin
     * @date 2013-04-26
     */
	public function addCidByCodeName($cum_code,$cum_name,$p_code,$p_name,$erp_guid,$agood){ 
		$goodscate = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM');
		$goodscate->startTrans();
		if (!empty($cum_code) && isset($cum_code)) {
  			$ary_category = $goodscate->where(array('erp_code' => $cum_code))->find();
			if (!empty($ary_category) && is_array($ary_category)) {
				$gcid =  $ary_category['gc_id'];
			} else {
				$arr_cg = array();
				if (!empty($p_code) && isset($p_code)) {
					$ary_pcategory = $goodscate->field('erp_guid')->where(array('erp_code' => $p_code))->find();
					if(!empty($ary_pcategory['erp_guid'])){
						$arr_cg['gc_parent_id'] = $ary_pcategory['erp_guid'];
					}
				}
				//更新分类
				$arr_cg['erp_guid'] = $erp_guid;
				$arr_cg['erp_code'] = $cum_code;
				$arr_cg['gc_name'] = $cum_name;
				$arr_cg['gc_create_time'] = $date;
				$res_gc = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($arr_cg);
				if (!$res_gc) {
					$goodscate->rollback();
					throw new Exception('添加商品分类信息失败', 10005);
				} else {
				  $gcid = $res_gc;
				}
			}
            //将删除原先分类与商品的关联关系
            M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id' => $agood))->delete();
            $ary_rgc_insert = array();
            $ary_rgc_insert['g_id'] = $agood;
            $ary_rgc_insert['gc_id'] = $gcid;
            $res_rgc = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_rgc_insert);
			if (!$res_rgc) {
				$goodscate->rollback();
				throw new Exception('更新分类失败', 10006);
            }
			$goodscate->commit();   
		}
	}
	
	/**
	 * 获取具有子父级关系的分类数组
	 * 通过递归的方式遍历分类，优点是一次查询数据库即可
	 *
	 * @params $int_parent_catid int 可选，默认值 0 表示获取全部分类的子分类
	 *
	 * @author Mithern
	 * @date 2013-05-28
	 * @version 1.0
	 */
	public function getChildLevelCategoryById($int_parent_catid = 0){
		//获取指定的商品类型
		$array_cond = array("gc_status"=>1);
		$array_category = $this->where($array_cond)->order(array("gc_order"=>"ASC"))->select();
		//对数据进行递归处理，将分类数组组合为一级分类
		return $this->aiderChildLevelCatMethod($array_category,$int_parent_catid,array());
	}
	
	/**
	 * getChildLevelCategoryById 的辅助方法
	 * 作用是从一个分类数组中获取到指定的分类，返回即可
	 *
	 * @params $array_categorys array 必选，规定要遍历的数组
	 * @params $int_parent_id int 可选 默认0  获取指定父类目ID为$int_parent_id的分类
	 *
	 * @author Mithern
	 * @date 2013-05-28
	 * @version 1.0
	 */
	private function aiderChildLevelCatMethod($array_categorys,$int_parent_id=0,$array_return=array()){
		foreach($array_categorys as $key=>$val){
			//判断当前分类是否是要找到分类，如果是则存入数组中
			if($int_parent_id == $val["gc_parent_id"]){
				$array_return[] = $val;
				//判断当前分类是否是叶子节点，如果不是叶子节点，则调用方法本身，获取子分类
				if(1 == $val["gc_is_parent"]){
					$array_return = $this->aiderChildLevelCatMethod($array_categorys,$val["gc_id"],$array_return);
				}
			}
		}
		return $array_return;
	}
	
	/**
	 * 获取一个商品分类的子分类ID
	 * 
	 * $int_parent_id 要获取子分类ID的父分类ID  必选参数
	 * 
	 * @return  返回一个数组，包含所有的子分类ID
	 */
	public function getCategoryChildIds($int_parent_id){
		$array_categorys = $this->getChildLevelCategoryById($int_parent_id);
		$array_return = array();
		foreach($array_categorys as $val){
			$array_return[] = $val["gc_id"];
		}
		return $array_return;
	}
}