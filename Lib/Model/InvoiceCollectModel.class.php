<?php

/**
 * 发票模型
 *
 * @package Model
 * @stage 7.0
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-25
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class InvoiceCollectModel extends GyfxModel {
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 收藏发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function add($data) {
        $this->update($data['m_id']);
        M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
    }
    
    /**
     * 获得收藏发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function get($mid,$type='',$tag=false) {
       
        if(!empty($type) && in_array($type,array(1,2))) $ary_where = array('m_id'=>$mid,'invoice_type'=>$type);
        else $ary_where = array('m_id'=>$mid);
        if($tag) unset($ary_where['is_verify']);
        return M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->select();
        
    }
    
    /**
     * 获得收藏发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function update($mid,$id) {
        $up_data['is_default']=0;
        M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$mid))->save($up_data);
    }
    /**
     * 获得收藏发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function change($data) {
        $up_data['is_default']=1;
        $this->update($data['m_id']);
        M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where(array('id'=>$data['id'],'m_id'=>$data['m_id']))->save($up_data);
    }
    
    /**
     * 获得指定收藏发票
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-25
     */
    public function getid($id) {
        return M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where(array('id'=>$id))->find();
    }
}
