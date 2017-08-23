<?php
/**
 * 订单合并支付模型
 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
 * @date 2014-5-12
*/
class MergerPaymentModel extends GyfxModel {
	
	/**
	 * 获取订单合并数据
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	 * @params $mp_id 合并支付id
	*/
	public function getMergerOrders($mp_id, $field="*"){
		return $this->field($field)->where(array('mp_id'=>$mp_id))->select();
	}
}