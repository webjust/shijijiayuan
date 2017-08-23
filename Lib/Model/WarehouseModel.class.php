<?php

class WarehouseModel extends GyfxModel {

    public function __construct() {
    }

    public function GetPositionCount($condition='') {
        $res=M('positions')->where($condition)->count();
        return $res;
    }

    public function GetPositionList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_products = M('positions',C('DB_PREFIX'),'DB_CUSTOM')
                    ->join('fx_goods_products ON fx_goods_products.pdt_id = fx_positions.pdt_id')
                    ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                    ->field($ary_field)
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        //echo M('position',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();
        return $res_products;        
    }
}
