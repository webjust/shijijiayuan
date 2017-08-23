<?php
/**
 * 结余款调整单模型
 *
 * @package Model
 * @version 7.2
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-06-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BalanceInfoModel extends GyfxModel {

    public function getBalance($params = array()){
        $ary_where = array();
        if(!empty($params['bi_sns']) && isset($params['bi_sns'])){
            $data = explode(',',trim($params['bi_sns'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."balance_info.`bi_sn` IN (". trim($str,",") .")";
        }
        if(!empty($params['bi_id']) && isset($params['bi_id'])){
            $data = explode(',',trim($params['bi_id'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."balance_info.`bi_id` IN (". trim($str,",") .")";
        }
        $ary_data = M('BalanceInfo',C('DB_PREFIX'),'DB_CUSTOM')->field(" ".C("DB_PREFIX")."balance_info.*,".C("DB_PREFIX")."balance_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."balance_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."balance_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."balance_type ON ".C("DB_PREFIX")."balance_type.`bt_id`=".C("DB_PREFIX")."balance_info.`bt_id`")
                                   ->order(" ".C("DB_PREFIX")."balance_info.`bi_order` DESC")
                                   ->where($ary_where)
                                   ->select();
        if(!empty($ary_data) && is_array($ary_data)){
            return $ary_data;
        }  else {
            return array();
        }
    }

    /**
     * 获取单据
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-16
     */
    public function getBalanceByCondition($params = array()){
        $ary_data = M('BalanceInfo',C('DB_PREFIX'),'DB_CUSTOM')
                    ->field(" ".C("DB_PREFIX")."balance_info.*,".C("DB_PREFIX")."balance_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                    ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."balance_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                    ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."balance_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                    ->join(" ".C("DB_PREFIX")."balance_type ON ".C("DB_PREFIX")."balance_type.`bt_id`=".C("DB_PREFIX")."balance_info.`bt_id`")
                    ->order(" ".C("DB_PREFIX")."balance_info.`bi_order` DESC")
                    ->where($params)
                    ->select();
        if(!empty($ary_data) && is_array($ary_data)){
            return $ary_data;
        }  else {
            return array();
        }
    }
}