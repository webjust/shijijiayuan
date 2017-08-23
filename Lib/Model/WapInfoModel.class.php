<?php

/**
 *  必迈   wap端用户中心 公共调用类
 * @package  Model
 * @author   zhaozhicheng
 * @time     2015-09-21
 **/

    class WapInfoModel  extends GyfxModel{

        public function __construct() {
            parent::__construct();
        }

        public function memberInfo(){
            $member = session('Members');
            //订单总数
            $ordercount = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
            $member['order_count'] = $ordercount;
            //收藏总数
            $collect_count=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
            $member['collect_count'] = $collect_count;
            //评论总数
            $gcom_count = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
            $member['gcom_count'] = $gcom_count;
            //我的积分
            $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
            //print_r($ary_point);exit;
            $valid_point = 0;//有用积分数
            if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
                $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
            }
            $member['my_point'] = $valid_point;

            return $member;
        }
    }