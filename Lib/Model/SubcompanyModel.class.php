<?php

/**
 * 子公司模型
 *
 * @package Model
 * @stage 7.0
 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
 * @date 2013-07-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SubcompanyModel extends GyfxModel {
	
	/**
	 * 根据商品ID判断商品属于哪个子公司
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-25
	 * @param int $int_mid 会员ID
	 * @param int $int_gid 商品ID
	 * @return boolean 有返回子公司信息,无返回false
	 */
	public function getCompanyByGid($int_gid) {
		//获取此商品所属分类和品牌
		$brand = D("Goods")->where(array('g_id' => $int_gid))->getField('gb_id');
		$cate = D('RelatedGoodsCategory')->field('gc_id')->where(array('g_id' => $int_gid))->select();
 
		//获取子公司所属分类和品牌
		$RelatedGoodSubcompany = D('RelatedGoodSubcompany')
		->join("left join fx_subcompany ON fx_subcompany.s_id = fx_related_good_subcompany.s_id ")
		->order('fx_related_good_subcompany.ra_update_time desc')
		->field('fx_related_good_subcompany.*,fx_subcompany.s_name')
		->select();

		//返回最新更新的子公司ID
		$company_id = '';
		foreach ($RelatedGoodSubcompany as $v) {
			if ($brand != 0 && $v['ra_gb_id'] == $brand) {
				$company_id = $v['s_id'];
				return $company_id;
			}
            $cate_child_id = D('ViewGoods')->getCates($v['ra_gc_id']);
			foreach($cate as $cate_info){
				if (count($cate_info) != 0 && $v['ra_gc_id'] == $cate_info['gc_id']) {
					$company_id = $v['s_id'];
					return $company_id;
				}
                if(count($cate_child_id) > 0){
                    foreach ($cate_child_id as $cci){
                        if (count($cate_info) != 0 && $cci['gc_id'] == $cate_info['gc_id']) {
                            $company_id = $v['s_id'];
                            return $company_id;
                        }
                    }
                }
                
			}
            
            
		}
		return false;
	}

}