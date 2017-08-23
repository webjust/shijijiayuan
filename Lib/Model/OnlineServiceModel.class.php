<?php

/**
 * 在线客服模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OnlineServiceModel extends GyfxModel {
    /**
     * 构造方法
     * @author Joe
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 获取客服信息
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-10-28
     */
    public function getOnlineService($tag = ''){
        $array_return = array();
        if(isset($tag['ocid']) && !empty($tag['ocid'])){
            $array_where['oc_id'] = $tag['ocid'];
        }
        $orderby = 'oc_order ';
        $orderby .= isset($tag['order']) ? $tag['order'] : 'desc';
        $array_info = M('online_cat', C('DB_PREFIX'), 'DB_CUSTOM')->where($array_where)->order($orderby)->select();
        if(!empty($array_info)){
            foreach ($array_info as $ok=>$ov){
                $array_return[$ok][$ov['oc_name']] = $this->where(array('oc_parent_id'=>$ov['oc_id'],'o_status'=>'1'))->select();
            }
        }
        return $array_return;
    }

}