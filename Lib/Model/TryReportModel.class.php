<?php

/**
 * 试用报告模型层 Model
 * @package Model
 * @version 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-10-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TryReportModel extends GyfxModel {

    private $table_report   = 'try_report';
    private $table_question = 'try_report_attribute';

	/**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取试用报告列表
     * @param (array)$array_condition 搜索条件
     * @param (mix) $order_by 排序条件
     * @param (int) $int_page_size 查询多少条数据
     * @param (int) $int_active_status 试用状态
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
	public function GetTryReportPageList($array_condition = array(), $order_by, $int_page_size = 20){
		$TryModel = D($this->table_report);
        $count = $TryModel
                ->where($array_condition)
                ->join(C("DB_PREFIX")."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id) ")
                ->join(C("DB_PREFIX")."members as member on(".C('DB_PREFIX')."try_report.m_id=member.m_id) ")
                ->count();
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $TryModel
                ->where($array_condition)
                ->join(C('DB_PREFIX')."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id) ")
                ->join(C("DB_PREFIX")."members as member on(".C('DB_PREFIX')."try_report.m_id=member.m_id) ")
                ->order($order_by)
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
        return $data;
	}

    /**
     * 获取试用报告详情
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function GetTryReportDetail($ary,$order_by=''){
        $data = D($this->table_report)
                ->where($ary)
                ->join(C("DB_PREFIX")."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id) ")
                ->join(C("DB_PREFIX")."members as member on(".C('DB_PREFIX')."try_report.m_id=member.m_id) ")
                ->join(C("DB_PREFIX")."try_apply_records as apply on(apply.m_id=".C("DB_PREFIX")."try_report.m_id and apply.g_id=try.g_id)")
                ->order($order_by)
                ->find();
        return $data;
    }

    /**
     * 获取报告问题
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function getReportQuestion($ary){
        $data = array();
        if(isset($ary['tr_id']) && !empty($ary['tr_id'])){
            $report_info = D($this->table_report)->where(array('tr_id'=>$ary['tr_id']))->find();
            $report_info = $this->GetTryReportDetail(array('tr_id'=>$ary['tr_id']));
            if(!empty($report_info) && isset($report_info['property_typeid']) && !empty($report_info['property_typeid'])){
                $where = array(
                    'try_apply_id'    => $report_info['tar_id'],
                    'property_typeid' => $report_info['property_typeid']
                    );
                $ary_question = D($this->table_question)
                                ->join(C("DB_PREFIX")."goods_spec as goods_spec on goods_spec.gs_id=".C("DB_PREFIX").$this->table_question.".attr_id")
                                ->where($where)
                                ->select();
                if(!empty($ary_question)){
                    $data = $ary_question;
                }
            }
        }
        return $data;
    }

    /**
     * 获取报告总结
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function getReportAnswer($ary){
        $where = array(
            'spec.gs_input_type' => 4
            );
        if(isset($ary['tar_id']) && !empty($ary['tar_id'])){
            $where['try_apply_id'] = array('IN',$ary['tar_id']);
        }
        if(isset($ary['property_typeid']) && !empty($ary['property_typeid'])){
            $where['property_typeid'] = $ary['property_typeid'];
        }
        $data = D($this->table_question)
                ->field('attr_name,avg(attr_value) as attr_value')
                ->join(C("DB_PREFIX")."goods_spec as spec on(spec.gs_id=".C("DB_PREFIX").$this->table_question.".attr_id)")
                ->where($where)
                ->group('attr_id')
                ->select();

      
        return $data;
    }

    /**
     * 获取报告数
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function getTryReportNum($where){
        $total = D('try_report')
                ->where($where)
                ->count();
        return $total;
    }

	/**
     * 获取报审核状态
     * @author <zhuwenwei@guanyisoft.com>
     * @date 2015-8-6
     */
    public function getReportStatus($where){
        $try_report_status = D('try_report')->where($where)->field('tr_status')->find();
        return $try_report_status;
    }
    /**
     * 改变报告状态
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function changeReportStatus($where,$data){
        foreach($data as $key=>$vo){
            $where[$key] = array('neq',$vo);
        }
        return $this->saveTable('try_report',$where,$data);
    }

    /**
     * 修改表
     * @author Tom <helong@guanyisoft.com>
     * @param (string)$table 表名
     * @param (array) $where 修改条件
     * @param (array) $data  修改的数据
     * @date 2014-10-14
     */
    public function saveTable($table,$where,$data){
        $tag = D($table)->where($where)->save($data);
        if(false !== $tag){
            return true;
        }else{
            return false;
        }
    }
}