<?php
/**
 * 团购阶梯模型类
 * @author zhanghao
 * @version 7.4
 * @date 2013-9-13
 * 
 */
class RelatedGroupbuyPriceModel extends GyfxModel {
    /**
     * 根据团购活动ID获取团购的阶梯信息
     * @author zhanghao
     * @param intger $int_gp_id <p>团购活动ID</p>
     * @param array $ary_field <p>字段</p>
     * @date 2013-9-13
     * @return array
     */
    public function getLadderInfoById($int_gp_id, $ary_field="*",$is_cache) {
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('related_groupbuy_price',$ary_field, array('gp_id'=>$int_gp_id), $ary_order=null,'rgp_num desc',$ary_limit=null);
		}else{
			return $this->where(array('gp_id'=>$int_gp_id))->field($ary_field)->order('rgp_num desc')->select();
		}        
    }
}