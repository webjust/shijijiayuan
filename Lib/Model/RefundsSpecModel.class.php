<?php

/**
 * 获取的退单属性
 * @package Model
 * @version 7.3
 * @author  czy<chenzongyao@guanyisoft.com> 
 * @date 2013-08-14
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RefundsSpecModel extends GyfxModel {

    public function __construct() {
        parent::__construct();
        $this->table = M('refunds_spec',C('DB_PREFIX'),'DB_CUSTOM');
    }


    /**
     * 根据id获取属性名
     * @param  int  gs_id  的id
     * @author czy<chenzongyao@guanyisoft.com> 
     * @data 2012-08-14
     * @reture  array
     */
    public function getSpec($gs_id = "") {
        if (!empty($gs_id)) {
            return $this->table->field(array("gs_name"))->where(array("gs_id" => $gs_id))->find();
        }
    }
    
    /**
     * 根据获退单类型取属性数据
     * @param  int  type
     * @author czy<chenzongyao@guanyisoft.com> 
     * @data 2013-08-14
     * @reture  array
     */
    public function getSpecByType($type = "") {
        if (!empty($type)) {
            return $this->table->field(array("gs_id,gs_name,gs_input_type"))->where(array("gs_show_type" => $type))->select();
        }
    }

     /**
     * 取属性数据
     * @param  int  type
     * @author czy<chenzongyao@guanyisoft.com> 
     * @data 2013-08-14
     * @reture  array
     */
    public function getSpecData() {
        return $this->table->field(array("gs_id,gs_name,gs_input_type"))->select();
       
    }
}