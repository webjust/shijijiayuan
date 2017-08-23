<?php

/**
 * 支付序列模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PaymentSerialModel extends GyfxModel {

	/**
     * 构造方法
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2015-11-24
     */
    public function __construct() {
        parent::__construct();
    }
	
	/**
     * 获得订单的支付信息
     * @parme $ary_where 查询条件
	 * @parme $ary_field 返回字段
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return array $return_data 
     * @date 2015-11-24
     */
    public function getDataInfo($ary_where = array(),$ary_field = '*') {
        $return_data = $this->field($ary_field)->where($ary_where)->find();
		if(!empty($return_data)){
			if($return_data['pc_code']=='DEPOSIT'){
				$return_data['pc_name']='预存款';
			}
			if($return_data['pc_code']=='ALIPAY'){
				$return_data['pc_name']='支付宝';
			}
			if($return_data['pc_code']=='OFFLINE'){
				$return_data['pc_name']='线下支付';
			}
			if($return_data['pc_code']=='TENPAY'){
				$return_data['pc_name']='财付通';
			}
			if($return_data['pc_code']=='CHINABANK'){
				$return_data['pc_name']='网银在线';
			}
			if($return_data['pc_code']=='DELIVERY'){
				$return_data['pc_name']='货到付款';
			}
			if($return_data['pc_code']=='CHINAPAY'){
				$return_data['pc_name']='银联在线';
			}
			if($return_data['pc_code']=='CHINAUNIONPAY'){
				$return_data['pc_name']='中国银联';
			}
			if($return_data['pc_code']=='WAPALIPAY'){
				$return_data['pc_name']='WAP支付宝';
			}
			if($return_data['pc_code']=='CHINAPAYV5'){
				$return_data['pc_name']='银联在线v5.0.0';
			}
			if($return_data['pc_code']=='WEIXIN'){
				$return_data['pc_name']='微信支付';
			}
			return $return_data;
        }
		return false;
    }
}