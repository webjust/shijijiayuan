<?php

/**
 * 充值模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RechargeExamineModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-08
     */

    public function __construct() {
        parent::__construct();
    }
    /**
     * 获得一段时间内退款订单
     * @return ary 返回充值信息
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-03
     */
    public function GetRechargeDetails() {
        $oneday = mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y"));//一天未付款订单自动取消
	    $create_time = date('Y-m-d H:i:s',$oneday);
        $where=array(
            'fx_recharge_examine.re_verify'=>0,
            'fx_recharge_examine.re_status'=>1,
            'fx_recharge_examine.re_update_time'=>array('EGT', $create_time)
        );
        $result = M('recharge_examine',C('DB_PREFIX'),'DB_CUSTOM')
                  ->join('fx_members ON fx_members.m_id = fx_recharge_examine.m_id')
                  ->field('fx_recharge_examine.re_payment_sn,fx_recharge_examine.re_id,fx_members.m_email,fx_members.m_id,fx_members.m_balance')
                  ->where($where)->select();
        return $result;
    }
    /**
     * 根据充值id更新充值状态
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-08
     * @param int $id 充值id
     * @param int $mid 用户id
     * @param tinyint $status 审核状态
     * @param decimal $money 结余款
     */
    public function UpdateRechargeStatus($id,$mid,$status,$money) {
        $data['re_verify']=$status;
        M('','','DB_CUSTOM')->startTrans();
        $result=M('recharge_examine',C('DB_PREFIX'),'DB_CUSTOM')->where(array('re_id'=>$id))->save($data);
        if($result==false){
            M('','','DB_CUSTOM')->rollback();
        }
        if($status==1){//审核通过时
            $tmp_result=D('Members')->UpdateBalance($mid,$money);
            if($tmp_result==false){
                M('','','DB_CUSTOM')->rollback();
            }
        }
        M('','','DB_CUSTOM')->commit();
    }
    
    /**
     * 充值审核列表
     * @author  Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-05-08
     * @param array $where 查询条件
     * @param int $page_no 查询开始
     * @param int $page_size 查询数量
     */
    public function pageListVerify($where=array(),$page_no,$page_size) {
        $ary_res = M('recharge_examine',C('DB_PREFIX'),'DB_CUSTOM')
                       ->join('fx_members on fx_recharge_examine.m_id=fx_members.m_id')
                       ->order('re_create_time desc')
                       ->where($where)                       
                       ->page($page_no,$page_size)
                       ->select();
                       
        $count = M('recharge_examine',C('DB_PREFIX'),'DB_CUSTOM')
                 ->join('fx_members on fx_recharge_examine.m_id=fx_members.m_id')
                 ->where($where)->count();
                 
        return array('data'=>$ary_res,'count'=>$count);
    }
}