<?php

/**
 * 试用申请模型层 Model
 * @package Model
 * @version 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-10-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TryApplyModel extends GyfxModel {

    private $table_name = 'try_apply_records';
	/**
     * 构造方法
     * @author Tom <helong@guanyisoft.com>
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取申请记录
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
    public function GetTryApplyList($array_condition = array(),$field, $order_by, $int_page_size = 20){
        // $field = 'C("DB_PREFIX")."try_apply_records.*,try.*,goods.*,member.m_name,orders.o_status"';
        $TryModel = D($this->table_name);
        $count = $TryModel
                ->join(C("DB_PREFIX")."try as try on(try.g_id=".C('DB_PREFIX')."try_apply_records.g_id) ")
                ->join(C("DB_PREFIX")."goods_info as goods on(try.g_id=goods.g_id) ")
                ->join(C("DB_PREFIX")."members as member on(".C('DB_PREFIX')."try_apply_records.m_id=member.m_id) ")
                ->where($array_condition)
                ->count();
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $TryModel
                ->field($field)
                ->join(C("DB_PREFIX")."try as try on(try.g_id=".C('DB_PREFIX')."try_apply_records.g_id) ")
                ->join(C("DB_PREFIX")."goods_info as goods on(try.g_id=goods.g_id) ")
                ->join(C("DB_PREFIX")."members as member on(".C('DB_PREFIX')."try_apply_records.m_id=member.m_id) ")
                ->join(C("DB_PREFIX")."orders as orders on(".C('DB_PREFIX')."try_apply_records.try_oid=orders.o_id)")
                ->where($array_condition)
                ->order($order_by)
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
		//七牛图片显示
		foreach($data['list'] as $key =>$value ){
			$data['list'][$key]['g_picture'] =D('QnPic')->picToQn($value['g_picture']);
			$data['list'][$key]['try_picture'] =D('QnPic')->picToQn($value['try_picture']);
		}
        return $data;
    }

    /**
     * 查询单条记录详情
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function GetTryRecords($ary){
        if(isset($ary['oid']) && !empty($ary['oid'])){
            $ary['apply.try_oid'] = $ary['oid'];
        }
        $ary['try_report.tr_status'] = '1';
        $data = D($this->table_name)
            ->join(C("DB_PREFIX")."try as try on(try.g_id=".C('DB_PREFIX')."try_apply_records.g_id) ")
            ->join(C("DB_PREFIX")."try_report as try_report on(try.try_id=try_report.try_id)")
            ->join(C('DB_PREFIX')."try_apply_records as apply on(apply.g_id=try.g_id and try_report.m_id=apply.m_id)")
            ->where($ary)
            ->find();
        return $data;
    }

}