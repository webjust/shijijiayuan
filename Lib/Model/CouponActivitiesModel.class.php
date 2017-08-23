<?php
/**
 * 优惠券活动模型层 Model
 * @package Model
 * @version 7.8.9
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2015-11-17
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */

class CouponActivitiesModel extends GyfxModel{

    /**
     * 获取优惠券异号活动列表，区分为现金券和折扣券,主要用做前台抢券
	 * @author Hcaijin
	 * @date 2015-11-17
	 * @return array $data
     */
    function getOppSign(){
        $mid = $_SESSION['Members']['m_id'];
        $nowTime = date('Y-m-d H:i:s');
        $where = array(
            'ca_status'=> 0,
            'ca_type'=> 1,
			'ca_start_time' => array('lt', $nowTime),
			'ca_end_time' => array('gt', $nowTime)
        );
        $datalist = $this->where($where)->select();
		$array_coupon_z = array();
		$array_coupon_x = array();
        foreach($datalist as $list){
            if($list['ca_total'] > $list['ca_used_num']){
                //调用模型获取商品分组表数据
                $ary_ggid = json_decode($list['ca_ggid'],1);
                $ary_ggname = D('GoodsGroup')->getGoodsGroupByIds($ary_ggid,'gg_name');
                $str_ggname = '';
                foreach($ary_ggname as $val){
                    $str_ggname .= $val['gg_name'].",";
                }
                $list['group'] = rtrim($str_ggname,",");
                //如果已登录，判断该优惠券是否已领取
                if(!empty($mid)){
                    $cou_where['c_user_id'] = $mid;
                    $cou_where['ca_id'] = $list['ca_id'];
                    $countId = D('Coupon')->where($cou_where)->count('c_id');
                    if( $list['ca_limit_nums'] > 0 && $countId >= $list['ca_limit_nums']){
                        $list['is_receive'] = 1;
                    }else{
                        $list['is_receive'] = 0;
                    }
                }
                $list['c_condition_money'] = $list['c_condition_money'] == '0'?'':$list['c_condition_money'];
                //兼容所有模板 转化为现有模板的变量
                $list['c_name'] = $list['ca_name'];
                if($list['c_type'] == 1){
                    $array_coupon_z[] = $list;
                }
                if($list['c_type'] == 0){
                    $array_coupon_x[] = $list;
                }
            }
        }
		$data = array();
 		$data['zkList'] = $array_coupon_z;
		$data['xjList'] = $array_coupon_x;
        return $data;
    }

	/**
	 * 领取优惠券方法
	 * @author Hcaijin
	 * @date 2015-11-17
	 * @param $mid
	 * @param $cname
	 *
	 * @return array
	 */
	public function doCollectCoupon($mid, $cname) {
		if(($mid) && ($cname)) {
			$ary_coupon_act = $this->selectOne( 'coupon_activities', '*', array('ca_name'=>$cname) );
            if(($ary_coupon_act['ca_total'] - $ary_coupon_act['ca_used_num']) > 0){
                $cou_where['ca_id'] = $ary_coupon_act['ca_id'];
                $cou_where['c_user_id'] = $mid;
                $count_num = $this->getCount( 'coupon', $cou_where );
                if ($ary_coupon_act['ca_limit_nums'] > 0 && $count_num >= $ary_coupon_act['ca_limit_nums'] ) {
                    $result = array( 'status' => 2, 'message' => '您已领取完此优惠券，不可再次领取！' );
                } else {
                    $data = array();
                    $data['c_name'] = $ary_coupon_act['ca_name'];
                    $data['c_money'] = $ary_coupon_act['c_money'];
                    $data['c_memo'] = $ary_coupon_act['ca_memo'];
                    $data['c_start_time'] = $ary_coupon_act['c_start_time'];
                    $data['c_end_time'] = $ary_coupon_act['c_end_time'];
                    $data['c_condition_money'] = $ary_coupon_act['c_condition_money'];
                    $data['c_is_use'] = 0;
                    $data['c_create_time'] = date('Y-m-d h:i:s');
                    $data['c_user_id'] = $mid;
                    $data['c_type'] = $ary_coupon_act['c_type'];
                    $data['ca_id'] = $ary_coupon_act['ca_id'];
                    //生成序列号
                    $ary_sn = json_decode($ary_coupon_act['ca_sn'],1);
                    $int_long = $ary_sn['long'];
                    $str_prefix = $ary_sn['prefix'];
                    $str_suffix = $ary_sn['suffix'];
                    $sn = $str_prefix . randStr($int_long) . $str_suffix;
                    //判断是否唯一
                    //$res = $this->selectOne('coupon', 'c_id', array('c_sn' => $sn));
                    //未被使用可以用
                    $data['c_sn'] = $sn;
                    $this->startTrans();
                    $res  = $this->insert( 'coupon', $data );
                    if ( $res ) {
                        $ary_ggid = json_decode($ary_coupon_act['ca_ggid'],1);
                        foreach($ary_ggid as $int_gg_id){
                            $ary_insert = array('gg_id'=>(int)$int_gg_id,'c_id'=>$res);
                            $this->insert('related_coupon_goods_group', $ary_insert);
                        }
                        $res_inc = $this->where(array('ca_id'=>$ary_coupon_act['ca_id']))->setInc('ca_used_num',1);
                        if(!$res_inc){
                            $this->rollback();
                            $result = array( 'status' => 0, 'message' => '领取失败，请重试！' );
                        }else{
                            $this->commit();
                            $result = array( 'status' => 1, 'message' => '领取成功！' );
                        }
                    } else {
                        $this->rollback();
                        $result = array( 'status' => 0, 'message' => '领取失败，请重试！' );
                    }
                }
            } else {
                $result = array( 'status' => 0, 'message' => '很抱歉！优惠券已领完！' );
            }
		}
		else {
			$result = array('status'=>0,'message'=>'缺少必要参数！');
		}
		return $result;
	}

    /**
     * 注册成功送注册优惠券一张
     */
	public function doRegisterCoupon($mid) {
		if(!empty($mid)) {
            $nowTime = date('Y-m-d H:i:s');
            $where = array(
                'ca_status'=> 0,
                'ca_type'=> 2,
                'ca_start_time' => array('lt', $nowTime),
                'ca_end_time' => array('gt', $nowTime)
            );
			$ary_coupon_act = $this->selectAll( 'coupon_activities', '*' ,$where);
            foreach($ary_coupon_act as $act){
                if(($act['ca_total'] - $act['ca_used_num']) > 0){
                    $data = array();
                    $data['c_name'] = $act['ca_name'];
                    $data['c_money'] = $act['c_money'];
                    $data['c_memo'] = $act['ca_memo'];
                    $data['c_start_time'] = $act['c_start_time'];
                    $data['c_end_time'] = $act['c_end_time'];
                    $data['c_condition_money'] = $act['c_condition_money'];
                    $data['c_is_use'] = 0;
                    $data['c_create_time'] = $nowTime;
                    $data['c_user_id'] = $mid;
                    $data['c_type'] = $act['c_type'];
                    $data['ca_id'] = $act['ca_id'];
                    //生成序列号
                    $ary_sn = json_decode($act['ca_sn'],1);
                    $int_long = $ary_sn['long'];
                    $str_prefix = $ary_sn['prefix'];
                    $str_suffix = $ary_sn['suffix'];
                    $sn = $str_prefix . randStr($int_long) . $str_suffix;
                    //判断是否唯一
                    //$res = $this->selectOne('coupon', 'c_id', array('c_sn' => $sn));
                    //未被使用可以用
                    $data['c_sn'] = $sn;
                    $this->startTrans();
                    $res  = $this->insert( 'coupon', $data );
                    if ( $res ) {
                        $ary_ggid = json_decode($act['ca_ggid'],1);
                        foreach($ary_ggid as $int_gg_id){
                            $ary_insert = array('gg_id'=>(int)$int_gg_id,'c_id'=>$res);
                            $this->insert('related_coupon_goods_group', $ary_insert);
                        }
                        $res_inc = $this->where(array('ca_id'=>$act['ca_id']))->setInc('ca_used_num',1);
                        if(!$res_inc){
                            writeLog($nowTime."  注册送优惠券".$act['ca_name']."优惠券活动表领取数加1失败; The last sql:".$this->getlastsql(), 'doRegisterCoupon.log');
                            $this->rollback();
                        }else{
                            $this->commit();
                        }
                    } else {
                        writeLog($nowTime."  注册送优惠券".$act['ca_name']."写入数据库失败; The last sql:".$this->getlastsql(), 'doRegisterCoupon.log');
                        $this->rollback();
                    }
                } else {
                    writeLog($nowTime."  注册送优惠券活动".$act['ca_name']."失败,已超过可领取的数量; ca_total:".$act['ca_total'].", ca_used_num:".$act['ca_used_num'], 'doRegisterCoupon.log');
                }
            }
		}else{
            writeLog($nowTime."  缺少必要参数", 'doRegisterCoupon.log');
		}
	}

    /**
     * 判断是否可以使用同号券
     * @param $csn
     * @return boolean
     */
    function checkIsCoupon($mid, $csn){
        $nowDate = date('Y-m-d H:i:s');
        $act_where = array(
            'ca_status' => 0,
            'ca_type' => 0,
            'ca_sn' => $csn,
            'ca_start_time' => array('ELT', $nowDate),
            'ca_end_time' => array('EGT', $nowDate)
        );
        $ary_act = $this->selectOne('coupon_activities', '*', $act_where);
        if(($ary_act['ca_total'] - $ary_act['ca_used_num']) > 0){
            $cou_where['ca_id'] = $ary_act['ca_id'];
            $cou_where['c_user_id'] = $mid;
            $count_num = $this->getCount( 'coupon', $cou_where );
            if ($ary_act['ca_limit_nums'] > 0 && $count_num >= $ary_act['ca_limit_nums'] ) {
                return false;
            }else{
                return $ary_act;
            }
        }else{
            return false;
        }
    }

    /**
     * 同号券使用成功新增一条随机的券号
     * @param $oid          订单号
     * @param $ary_coupon   优惠券信息
     * @return boolean
     */
	public function doUseCoupon($oid, $ary_coupon) {
		if(!empty($oid) && !empty($ary_coupon) && is_array($ary_coupon)) {
            //生成序列号
            $int_long = strlen($ary_coupon['c_sn']);
            $ary_coupon['c_sn'] = randStr($int_long);
            $ary_coupon['c_is_use'] = 1;
            $ary_coupon['c_used_id'] = $_SESSION['Members']['m_id'];
            $ary_coupon['c_order_id'] = $oid;
            $this->startTrans();
            unset($ary_coupon['is_oppsn']);
            unset($ary_coupon['gids']);
            $res  = $this->insert( 'coupon', $ary_coupon );
            if ( $res ) {
                $res_inc = $this->where(array('ca_id'=>$ary_coupon['ca_id']))->setInc('ca_used_num',1);
                if(!$res_inc){
                    $this->rollback();
                    return false;
                }else{
                    $this->commit();
                    return true;
                }
            } else {
                $this->rollback();
                return false;
            }
		}else{
			return false;
		}
	}

    /**
     * 退款退货作废根据订单号删除优惠券或者还原优惠券
     * @param $oid          订单号
     * @return boolean
     */
	public function delCoupon($oid) {
        if(empty($oid)) return false;
        $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_order_id' => $oid, 'c_end_time'=>array('egt',date('Y-m-d H:i:s'))))->find();
		if(!empty($ary_coupon['ca_id'])) {
            $type = D("CouponActivities")->where(array('ca_id'=>$ary_coupon['ca_id']))->getField('ca_type');
            if($type == 0){
                $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('c_id' => $ary_coupon['c_id']))->delete();
                if($res_coupon){
                    $res_dec = $this->where(array('ca_id'=>$ary_coupon['ca_id']))->setDec('ca_used_num',1);
                    if($res_dec){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'c_id' => $ary_coupon['c_id'],
                            'c_end_time'=> array('egt',date('Y-m-d H:i:s'))
                        ))->save(array(
                    'c_used_id' => 0,
                    'c_order_id' => 0,
                    'c_is_use' => 0
                        ));
                if($res_coupon){
                    return true;
                }else{
                    return false;
                }
            }
		}elseif(!empty($ary_coupon) && is_array($ary_coupon) && empty($ary_coupon['ca_id'])){
            //兼容旧版本的优惠券还原使用的优惠券，如果没有查询到优惠券表有ca_id值,但有查询到相关优惠券就直接更新优惠券
            $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'c_id' => $ary_coupon['c_id'],
                        'c_end_time'=> array('egt',date('Y-m-d H:i:s'))
                    ))->save(array(
                'c_used_id' => 0,
                'c_order_id' => 0,
                'c_is_use' => 0
                    ));
            if($res_coupon){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
	}
}
