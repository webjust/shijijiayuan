<?php
/**
 * 支付方式模型层 Model
 * @package Model
 * @version 7.0
 * @author Joe
 * @date 2012-12-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
 class PaymentCfgModel extends GyfxModel {
    /**
     * 获取支付方式表
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @since 1.0
     * @return array
     * @date 2012-12-13
     */
     public function getPayCfg($type){
		 //默认不显示手机端
		 $ary_where = array('pc_status'=>1,'pc_source'=>array('neq',2));
		 //所有
		 if($type == 2){
			$ary_where = array('pc_status'=>1);
		 }
		 //手机端
		if($type == 1){
			$ary_where = array('pc_status'=>1,'pc_source'=>array('neq',1));
		}	
		$ary_payment = D('PaymentCfg')->where($ary_where)->order('pc_position asc')->select();	
        return $ary_payment;
     }

     /**
      * 获取满足条件的支付方式列表
      * @param $type
      * 1：PC前台，2：APP，4：WAP
      * @return array
      */
     public function getPaymentList($type='') {
         switch($type) {
             case 'app':
                 $ary_where = array(
                     'pc_status' => 1,
                     'pc_source' => array('in', array(2,3,6,7)),
                 );
                 break;
             case 'wap':
                 $ary_where = array(
                     'pc_status' => 1,
                     'pc_source' => array('in', array(4,5,6,7)),
                 );
                 break;
             case 'pc':
                 $ary_where = array(
                     'pc_status' => 1,
                     'pc_source' => array('in', array(1,3,5,7)),
                 );
                 break;
             default:
                $ary_where = array();
                 break;
         }
         $ary_payment = D('PaymentCfg')->where($ary_where)->order('pc_position asc')->select();
         return $ary_payment;
     }

    /**
    * 根据条件获取支付方式
    *
    * @author listen
    * @param $ary_where 查询的条件
    * @since 1.0
    * @return array
    * @date 2013-01-07
    */
    public function getPayCfgId($ary_where){
        $ary_payment = D('PaymentCfg')->where($ary_where)->find();
        return $ary_payment;
    }
	
	/**
    * 根据条件获取支付方式列表
    *
    * @author zhangjiasuo
    * @param $ary_where 查询的条件
	* @param $field 查询字段
	* @param $orders 排序方式
    * @since 7.8.3.2
    * @return array
    * @date 2015-05-26
    */
    public function getPayList($ary_where,$field="*",$orders){
        $res = D('PaymentCfg')->field($field)->where($ary_where)->order($orders)->select();
        return $res;
    }

    ##########################################################################

    /**
     * 在线支付的种类
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @var array
     * code:促销种类代码
     * type:1为需要第三方配置 0为无需配置（例如预存款、线下、货到付款）
     * status:1为启用 0为不启用或者待开发
     */
    public static $types = array(
        'DEPOSIT' => array('code' => 'DEPOSIT', 'type' => 0, 'status' => 1, 'memo' => '预存款支付'),
        'ALIPAY' => array('code' => 'ALIPAY', 'type' => 1, 'status' => 1, 'memo' => '支付宝'),
        'WAPALIPAY' => array('code' => 'WAPALIPAY', 'type' => 1, 'status' => 1, 'memo' => '支付宝WAP支付'),
        'APPALIPAY' => array('code' => 'APPALIPAY', 'type' => 1, 'status' => 1, 'memo' => '支付宝APP支付'),
        'OFFLINE' => array('code' => 'OFFLINE', 'type' => 0, 'status' => 1, 'memo' => '线下支付'),
        'TENPAY' => array('code' => 'TENPAY', 'type' => 1, 'status' => 1, 'memo' => '财付通'),
        'CHINABANK' => array('code' => 'CHINABANK', 'type' => 1, 'status' => 1, 'memo' => '网银在线'),
        'DELIVERY' => array('code' => 'DELIVERY', 'type' => 1, 'status' => 1, 'memo' => '货到付款'),
        'KUAIQIAN' => array('code' => 'KUAIQIAN', 'type' => 1, 'status' => 1, 'memo' => '快钱支付'),
        'CHINAPAY' => array('code' =>'CHINAPAY','type'=>1,'status'=>1,'memo' =>'银联在线'),
        'CHINAPAYV5' => array('code' =>'CHINAPAYV5','type'=>1,'status'=>1,'memo' =>'银联在线v5.0.0'),
        'CHINAUNIONPAY' => array('code' =>'CHINAUNIONPAY','type'=>1,'status'=>1,'memo' =>'中国银联在线支付'),
		'YOUHAOPAY' => array('code' => 'YOUHAOPAY', 'type' => 1, 'status' => 1, 'memo' => '全世达网银支付'),
		'YUFUKA' => array('code' => 'YUFUKA', 'type' => 1, 'status' => 1, 'memo' => '全世达预付卡支付'),
		'ICBC' => array('code' => 'ICBC', 'type' => 1, 'status' => 1, 'memo' => '中国工商银行'),
		'WEIXIN' => array('code' => 'WEIXIN', 'type' => 1, 'status' => 1, 'memo' => '微信支付'),
        'BOCOMPAY' => array('code' => 'BOCOMPAY', 'type' => 1, 'status' => 1, 'memo' => '交通银行支付')
    );

    /**
     * 返回支付的种类
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @return array
     */
    public function getTypes() {
        return self::$types;
    }

    /**
     * 静态工厂方法，根据CODE获取相应的支付对象
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param string $str_code 支付代码
     * @param array $array_params 支付配置
     * @return object 返回实例化后的促销对象
     */
    public static function factory($str_code, $array_params = array()) {
        $return = false;

        foreach (self::$types as $tp) {
            if ($tp['code'] == $str_code) {
                $return = new $str_code($str_code, $array_params);
            }
        }

        if ($return instanceof IPayments) {
            return $return;
        } else {
            return false;
        }
    }

 }
