<?php

/**
 * 退款退货属性模型
 *
 * @package Model
 * @version 7.3
 * @author czy <chenzongyao@guanyisoft.com>
 * @date 2013-08-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedRefundSpecModel extends GyfxModel {
    
    public function __construct() {
        parent::__construct();
        $this->related_refund_spec = M('related_refund_spec',C('DB_PREFIX'),'DB_CUSTOM');
    }
    /*得到与退单的关联自定义规格*/
    public function getRelatedRefundSpec($or_id='') {
            if(empty($or_id)) return null;
            $ary_gs_ids = array();
            $ary_spec_data = $this->related_refund_spec->where(array("or_id" => $or_id))->select();
            if(!empty($ary_spec_data)) {
                
                foreach($ary_spec_data as $val) {
                    if(!empty($val['gs_id']) && !empty($val['content'])) $ary_gs_ids[$val['gs_id']] = array('content'=>$val['content'],'or_id'=>$val['or_id']);
                }
               
                if($ary_gs_ids) {
                    $array_result = D("RefundsSpec")->where(array("gs_id"=>array("IN",array_keys($ary_gs_ids))))->select();
                    foreach($array_result as $key=>$val) {
                          if(isset($ary_gs_ids[$val['gs_id']])) {
                               $ary_gs_ids[$val['gs_id']]['gs_name'] = $val['gs_name'];
                               $ary_gs_ids[$val['gs_id']]['gs_input_type'] = $val['gs_input_type'];
                               
                          }
                          else unset($ary_gs_ids[$val['gs_id']]);
                    }
                }
               
			}
            return $ary_gs_ids;
            
     }
}