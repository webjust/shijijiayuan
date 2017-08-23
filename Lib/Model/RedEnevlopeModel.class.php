<?php
/**
 * 优惠券模型
 */

class RedEnevlopeModel extends GyfxModel{

	/**
	 * 可领取的优惠券列表
	 * @author Nick
	 * @date 2015-09-07
	 * @param $mid
	 * @return array $data
	 */
	public function availableCoupons($mid = 0) {

		$where = array(
			'rd_is_status' => 1,
			'rd_start_time' => array('lt',date('Y-m-d H:i:s')),
			'rd_end_time' => array('gt',date('Y-m-d H:i:s')),
		);
		$datalist = $this->selectAll('red_enevlope', '*', $where, array("rd_id"=>"desc"));

		$array_coupon_z = array();
		$array_coupon_x = array();
		foreach($datalist as &$val){
			//获取优惠券对应的活动名称
			$ary_related = $this->selectAll('related_coupon_red', '*', array('rd_id'=>$val['rd_id']));
			foreach($ary_related as $relVal){
				$cou_where['c_name'] = $relVal['c_name'];
				//获取优惠券列表
				$coupon = $this->selectAll('coupon', 'c_id,c_name,c_type,c_money,c_start_time,c_end_time,c_condition_money', $cou_where, null, 'c_name');
				foreach($coupon as $cVal){
					//获取优惠券适用的商品分组
					$ary_res_gg_id = $this->selectAll('related_coupon_goods_group', '*', array('c_id'=>$cVal['c_id']));
					//echo M('related_coupon_goods_group',C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql();
					//定义一个空数组
					$ary_gg_id = array();
					$coupons = array();
					foreach($ary_res_gg_id as $ggid){
						$ary_gg_id[] = $ggid['gg_id'];
					}
					//调用模型获取商品分组表数据
					$ary_goods = D('GoodsGroup')->getGoodsGroupByIds($ary_gg_id,'gg_name');
					//echo D('GoodsGroup')->getLastSql();
					$lists = '';
					if(is_array($ary_goods)){
						foreach($ary_goods as $goods){
							$lists .= $goods['gg_name'].",";
						}
					}
					if($mid != 0){
						$cou_where_id['c_user_id'] = $mid;
						$cou_where_id['c_name'] = $cVal['c_name'];
						//如果已登录，判断该优惠券是否已领取
						$res_coupon = D('Coupon')->where($cou_where_id)->find();
						if($res_coupon){
							$cVal['is_receive'] = '1';
						}else{
							$cVal['is_receive'] = '';
						}
					}
					$cVal['c_condition_money'] = $cVal['c_condition_money'] == '0'?'':$cVal['c_condition_money'];
					$cVal['group'] = rtrim($lists,",");
					$cVal['rd_id'] = $relVal['rd_id'];
					$coupons[] = $cVal;
					if($cVal['c_type'] == 1){
						$array_coupon_z = array_merge($array_coupon_z,$coupons);
					}
					if($cVal['c_type'] == 0){
						$array_coupon_x = array_merge($array_coupon_x,$coupons);
					}
				}
			}
		}
		$data = array();
		$data['typeList'] = $datalist;
 		$data['zkList'] = $array_coupon_z;
		$data['xjList'] = $array_coupon_x;

		return $data;
	}

	/**
	 * 领取优惠券
	 * @author Nick
	 * @date 2015-09-07
	 * @param $mid
	 * @param $cname
	 *
	 * @return array
	 */
	public function collectCoupon($mid, $cname) {

		if(($mid) && ($cname)) {
			$cou_where['c_name']    = $cname;
			$cou_where['c_user_id'] = $mid;
			$res_coupon             = $this->selectOne( 'coupon', '*', $cou_where );
			if ( $res_coupon ) {
				$result = array( 'status' => 2, 'message' => '您已有该优惠券，无需再次领取！' );
			} else {
				$save_where = array( 'c_name' => $cname, 'c_is_use' => '0', 'c_user_id' => '0' );
				$arr_csn    = $this->selectOne( 'coupon', 'c_sn', $save_where );
				if ( $arr_csn ) {
					$data = array( 'c_user_id' => $mid );
					$res  = $this->update( 'coupon', array( 'c_sn' => $arr_csn['c_sn'] ), $data );
					if ( $res ) {
						$result = array( 'status' => 1, 'message' => '领取成功！' );
					} else {
						$result = array( 'status' => 0, 'message' => '领取失败，请重试！' );
					}
				} else {
					$result = array( 'status' => 0, 'message' => '很抱歉！优惠券已领完！' );
				}
			}
		}
		else {
			$result = array('status'=>0,'message'=>'缺少必要参数！');
		}
		return $result;
	}
}
