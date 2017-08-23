<?php
/**
 * 积分配置模型
 * @package Model
 * @version 7.1
 * @author czy<chenzongyao@guanyisoft.com>
 * @date 2012-04-16
 */
class PointConfigModel extends GyfxModel{
    /**
     * 保存配置
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-4-16
     * @param string $cfg 配置项分组
     
     */
    public function setConfig($cfg){
    	$ary_data = $this->find();
    	if($ary_data){
    		$data = array(
    			'is_consumed' => $cfg['is_consumed'],
    			'cinsumed_channel' => $cfg['cinsumed_channel'],
    			'consumed_ratio' => $cfg['consumed_ratio'],
    			'regist_points' => $cfg['regist_points'],
    			'recommend_points' => $cfg['recommend_points'],
    			'invites_points' => $cfg['invites_points'],
				'is_buy_consumed' => $cfg['is_buy_consumed'],
				'consumed_buy_ratio' => $cfg['consumed_buy_ratio'],
				'consumed_points' => $cfg['consumed_points'],
				'again_recommend_points' => $cfg['again_recommend_points'],
				'show_recommend_points' => $cfg['show_recommend_points'],
				'login_points' => $cfg['login_points'],
				'sign_points' => $cfg['sign_points']				
    		);
            return M("point_config",C('DB_PREFIX'),'DB_CUSTOM')->where($ary_data['sc_id'])->data($data)->save();
    	}else{
    		$data = array(
    			'is_consumed' => $cfg['is_consumed'],
    			'cinsumed_channel' => $cfg['cinsumed_channel'],
    			'consumed_ratio' => $cfg['consumed_ratio'],
    			'regist_points' => $cfg['regist_points'],
    			'recommend_points' => $cfg['recommend_points'],
    			'invites_points' => $cfg['invites_points'],
				'is_buy_consumed' => $cfg['is_buy_consumed'],
				'consumed_buy_ratio' => $cfg['consumed_buy_ratio'],
				'consumed_points' => $cfg['consumed_points'],
				'again_recommend_points' => $cfg['again_recommend_points'],
				'show_recommend_points' => $cfg['show_recommend_points'],
				'login_points' => $cfg['login_points'],
				'sign_points' => $cfg['sign_points']				
    		);
    		return M("point_config",C('DB_PREFIX'),'DB_CUSTOM')->add($data);
    	}
    }
	
    /**
     * 获取数据中的配置项
     * @author czy<chenzongyao@guanyisoft.com>
     * @param string $str_key 键值
     */
    public function getConfigs($str_key=null) {
        $ary_result = array();
        $ary_result = $this->find();
        if( null!==$str_key && is_string($str_key) && !empty($ary_result)) {
            if(isset($ary_result[$str_key])) return $ary_result[$str_key];
            else return null;
        }
        else return $ary_result;
    }
    
    /**
     * 获得积分总优惠金额
     * @author czy <czy@guanyisoft.com>
	 * @param string $str_key 总商品金额
	 * @return  int $points 下单获得的积分
     * @date 2013-4-16
     */
    public function getrRewardPoint($goods_fee = 0){
	   $discount_fee = 0;//积分总优惠金额
       $ary_data = $this->getConfigs();
	   //启用积分
	   if(isset($ary_data['is_consumed']) && (1==$ary_data['is_consumed']) && is_numeric($ary_data['consumed_ratio']) && $ary_data['consumed_ratio']>0){
	        $points =  round($ary_data['consumed_ratio']*$goods_fee);
			return $points;
	   }
	   return 0;
    }
    
    /** 
     * 根据订单赠送积分更改会员总积分
     * @param $o_reward_point 赠送的积分
     * @param $m_id 会员id
     */
    public function setMemberRewardPoint($o_reward_point = 0,$m_id){
        $total_point = D('Members')->where(array('m_id' => $m_id))->getField('total_point');
 
        $ary_log = array(
                    'type'=>0,
                    'consume_point'=> 0,
                    'reward_point'=> $o_reward_point,
        );
        M('', '', 'DB_CUSTOM')->startTrans();
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('', '', 'DB_CUSTOM')->rollback();
             return array('result' => false, 'message' => $ary_info['msg']);
        }
        else{
            $ary_member_data['total_point'] = $total_point + $o_reward_point;
        }
        //更新消费总记录
        $ary_member_where = array('m_id' => $m_id);
        $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
        if (false === $int_return_members) {
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => '更新会员消费记录出错');
        }
        M('', '', 'DB_CUSTOM')->commit();
        return array('result'=>true, 'message'=>'成功');
    }
    
    /**
     * 根据订单冻结积分扣除会员总积分
     * @param $o_reward_point 冻结的积分
     * @param $m_id 会员id
     */
    public function setMemberFreezePoint($o_freeze_point = 0,$m_id){
        $ary_member = D('Members')->where(array('m_id' => $m_id))->find();
        $ary_log = array(
                    'type'=>1,
                    'consume_point'=> $o_freeze_point,
                    'reward_point'=> 0,
                    );
        M('', '', 'DB_CUSTOM')->startTrans();
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => $ary_info['msg']);
        }
        else{
            $int_freeze_point = ($ary_member['freeze_point'] > $o_freeze_point) ? ($ary_member['freeze_point'] - $o_freeze_point):0;//当前冻结积分=会员总冻结积分-单笔订单冻结积分
            $ary_member_data['freeze_point'] = $int_freeze_point;//当前要更新的会员冻结积分
            $ary_member_data['total_point'] = $ary_member['total_point'] - $o_freeze_point;//当前要更新的会员总积分
        }
        //更新消费总记录
        $ary_member_where = array('m_id' => $m_id);
        $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
        if (false === $int_return_members) {
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => '更新会员消费记录出错');
        }
        M('', '', 'DB_CUSTOM')->commit();
        return array('result'=>true, 'message'=>'成功');
    }

    /**
     * 获得订单可用积分
	 * @param string $goods_price 总商品金额
	 * @return  int $points 下单获得的积分
     * @author hcaijin 
     * @date 2014-7-23
     */
    public function getIsUsePoint($goods_price = 0,$m_id){
        $point = 0;
        $ary_data = $this->getConfigs();
        //启用积分
		//isset($ary_data['is_consumed']) && (1==$ary_data['is_consumed'])
        if(isset($ary_data['is_buy_consumed']) && (1==$ary_data['is_buy_consumed'])
           && is_numeric($ary_data['consumed_buy_ratio']) && $ary_data['consumed_buy_ratio']>0 
           && is_numeric($ary_data['consumed_points']) && $ary_data['consumed_points']>0){
            $points_arr = D('Members')->where(array('m_id' => $m_id))->field('total_point,freeze_point')->find();
			$usePoint = $points_arr['total_point']-$points_arr['freeze_point'];
            $point =  round($ary_data['consumed_buy_ratio']*$goods_price*$ary_data['consumed_points']);
			if($point >= $usePoint){
                $point = $usePoint;
            }
        }
        return $point;
    }

    /**
     * 兑换积分生成积分调整单
     * @param $o_reward_point 冻结的积分
     * @param $m_id 会员id
     * @author hcaijin 
     * @date 2014-8-12
     */
    public function setMemberJlbToPoint($o_freeze_point = 0,$m_id){
        $ary_member = D('Members')->where(array('m_id' => $m_id))->find();
        $ary_log = array(
                    'type'=>11,
                    'consume_point'=> 0,
                    'reward_point'=> $o_freeze_point,
                    );
        M('', '', 'DB_CUSTOM')->startTrans();
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => $ary_info['msg']);
        }
        else{
            $ary_member_data['total_point'] = $ary_member['total_point'] + $o_freeze_point;//当前要更新的会员总积分
        }
        $ary_member_where = array('m_id' => $m_id);
        $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
        if (false === $int_return_members) {
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => '更新会员兑换积分记录出错');
        }
        M('', '', 'DB_CUSTOM')->commit();
        return array('result'=>true, 'message'=>'成功');
    }

    /**
     * 兑换积分生成积分调整单
     * @param $o_reward_point 冻结的积分
     * @param $m_id 会员id
     * @author hcaijin 
     * @date 2014-8-12
     */
    public function setMemberPointToJlb($o_freeze_point = 0,$m_id){
        $ary_member = D('Members')->where(array('m_id' => $m_id))->find();
        $ary_log = array(
                    'type'=>12,
                    'consume_point'=> $o_freeze_point,
                    'reward_point'=> 0,
                    );
        M('', '', 'DB_CUSTOM')->startTrans();
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => $ary_info['msg']);
        }
        else{
            $ary_member_data['total_point'] = $ary_member['total_point'] - $o_freeze_point;//当前要更新的会员总积分
        }
        $ary_member_where = array('m_id' => $m_id);
        $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
        if (false === $int_return_members) {
            M('', '', 'DB_CUSTOM')->rollback();
            return array('result' => false, 'message' => '更新会员兑换金币记录出错');
        }
        M('', '', 'DB_CUSTOM')->commit();
        return array('result'=>true, 'message'=>'成功');
    }

    /** 
     * 赠送积分更改会员总积分
     * @param $reward_point 赠送的积分
     * @param $m_id 会员id
     */
    public function setMemberRewardPoints($reward_point = 0,$m_id,$type=0){
        if(empty($m_id)){
            return array('result' => false, 'message' => '用户不能为空');
        }
        $total_point = D('Members')->where(array('m_id' => $m_id))->getField('total_point');
        $ary_log = array(
                    'type'=>$type,
                    'consume_point'=> 0,
                    'reward_point'=> $reward_point,
        );
        M('')->startTrans(); 
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('')->rollback();
            return array('result' => false, 'message' => $ary_info['msg']);
        }else{
            $ary_member_data['total_point'] = $total_point + $reward_point;
            $ary_member_where = array('m_id' => $m_id);
            $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
            if (false === $int_return_members) {
                M('')->rollback();
                return array('result' => false, 'message' => '更新会员积分失败！');
            }else{
				M('')->commit();
                return array('result'=>true, 'message'=>'成功');
            }
        }
    }

    /** 
     * 扣减积分更改会员总积分
     * @param $consume_point 消费的积分
     * @param $m_id 会员id
     */
    public function setMemberConsumePoints($consume_point = 0,$m_id,$type=0){
        $total_point = D('Members')->where(array('m_id' => $m_id))->getField('total_point');
 
        $ary_log = array(
                    'type'=>$type,
                    'consume_point'=> $consume_point,
                    'reward_point'=> 0,
        );
        M('')->startTrans(); 
        $ary_info =D('PointLog')->addPointLog($ary_log,$m_id);
        if($ary_info['status']!=1){
            M('')->rollback();
            return array('result' => false, 'message' => $ary_info['msg']);
        }else{
            $ary_member_data['total_point'] = $total_point - $consume_point;
            if($ary_member_data['total_point'] < 0){
                M('')->rollback();
                return array('result' => false, 'message' => '商户金豆数量不足');
            }
            //更新消费总记录
            $ary_member_where = array('m_id' => $m_id);
            $int_return_members = D('Members')->where($ary_member_where)->save($ary_member_data);
            if (false === $int_return_members) {
                M('')->rollback();
                return array('result' => false, 'message' => '更新会员积分失败！');
            }else{
				M('')->commit();
                return array('result'=>true, 'message'=>'成功');
            }
        }
    }
	
	/**
     * 获取会员积分等级信息
     * @author hcaijin <huangcaijin@guanyisoft.com>
     * @param int $u_id 会员id
     * @date 2014-10-28
     */
    public function getPointLevel($u_id){
        $ary_members = D('Members')->where(array('m_id'=>$u_id))->find();
        //根据九龙金豆获取九龙金豆等级名称
        $points = $ary_members['total_point']-$ary_members['freeze_point'];
        $arr_point_level = M('PointsLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pl_up_fee'=>array('elt',$points)))->order('pl_up_fee desc')->find();
        if(empty($arr_point_level) && !is_array($arr_point_level)){
            $arr_point_level = M('PointsLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pl_default'=>1))->find();
        }
        return $arr_point_level;
    }
}
