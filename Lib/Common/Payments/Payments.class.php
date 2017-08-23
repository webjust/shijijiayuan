<?php

/**
 * 第三方支付接口抽象类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-17
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
abstract class Payments extends Action {

    /**
     * 支付方式唯一代码
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @var string
     */
    protected $code;

    /**
     * 支付平台配置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @var array
     */
    protected $config;

    /**
     * 构造方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param string $code 本支付方式的唯一代码
     * @param array $param 支付方式的配置数组，从数据库读取或者从页面传递，需要过滤
     */
    public function __construct($code, $param = array()) {
        $this->code = $code;
        $this->setCfg($param);
    }

    /**
     * 设置支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $this->config = $param;
    }

    /**
     * 获取本支付方式的配置信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-17
     * @return array
     */
    public function getCfg() {
        return $this->config;
    }

    /**
     * 支付方法，支付某张订单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param string $str_oid 订单编号
     * @param array $ary_param 修改订单的信息
     * @return array 返回订单信息
     */
    public function pay($str_oid) {
        $ary_order = D('Orders')->where(array('o_id' => $str_oid))->field('o_id,m_id,o_all_price,o_pay,o_create_time')->find();
        if (false == $ary_order) {
            $this->error('订单信息不存在');
        } else {
            return $ary_order;
        }
    }

    /**
     * 生成一张支付序列单
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-23
     * @param int $int_type 0代表支付订单 1代表预存款充值
     * @param array $ary_order 订单信息
     * @param $pay_type 付款类型 0全额支付 1定金支付 2尾款支付
     * @return int 返回支付单序列号，用于第三方平台支付
     */
    protected function addPaymentSerial($int_type = 0, $ary_order = array(),$pay_type = 0,$m_id = 0) {
        //订单支付时，确定支付宝商户订单号 Add Terry 2013-08-26
		if($pay_type==''){ //add by zhangjiasuo 2015-05-29
			$pay_type =0;
		}
        $ary_ps = D('PaymentSerial')->where(array('o_id'=>$ary_order['o_id']))->find();
        if(!empty($ary_ps) && $ary_ps['ps_money'] == $ary_order['o_all_price']){
            return $ary_ps['ps_id'];
        }else{
            $data = array(
                'm_id' => !empty($m_id) ? $m_id : $_SESSION['Members']['m_id'],
                'pc_code' => $this->code,
                'ps_money' => $ary_order['o_all_price'],
                'ps_type' => $int_type,
                'o_id' => $ary_order['o_id'],
                'ps_status' => 0,
				'pay_type' => $pay_type,
                'ps_create_time' => date('Y-m-d H:i:s'),
                'ps_update_time' => date('Y-m-d H:i:s')
            );
            $int_ps_id = D('PaymentSerial')->data($data)->add();
            if (false == $int_ps_id) {
                $this->error('生成支付序列号失败');
            } else {
                return $int_ps_id;
            }
        }

        
    }

    /**
     * 更新第三方流水单状态
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-24
     * @param int $int_ps_id 流水单号
     * @param int $ps_status 第三方支付状态
     * @param string $ps_status_sn 第三方支付状态代码
     * @param string $ps_gateway_sn 第三方流水单号
     * @return
     */
    protected function updataPaymentSerial($int_ps_id, $ps_status, $ps_status_sn, $ps_gateway_sn) {
        $data = array(
            'ps_status'=>$ps_status,
            'ps_status_sn'=>$ps_status_sn,
            'ps_gateway_sn'=>$ps_gateway_sn,
            'ps_update_time'=>date('Y-m-d H:i:s')
        );
        $result = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->data($data)->save();
        if(false == $result){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 根据支付序列号返回用户ID
     * 异步请求没有登录需要查找
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-25
     * @param int $int_ps_id 支付序列号ID
     * @return int 会员ID
     */
    protected function getMemberIdByPsid($int_ps_id){
        $ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
        if(false==$ary_paymentSerial){
            $this->error('支付序列号错误');
        }else{
            return $ary_paymentSerial['m_id'];
        }
    }

    /**
     * 根据支付序列号返回订单ID
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-02-01
     * @param int $int_ps_id 支付序列号ID
     * @return string 订单ID
     */
    protected function getOidByPsid($int_ps_id){
        $ary_paymentSerial = D('PaymentSerial')->where(array('ps_id'=>$int_ps_id))->find();
        if(false==$ary_paymentSerial){
            $this->error('支付序列号错误');
        }else{
            return $ary_paymentSerial['o_id'];
        }
    }
    
    /**
     * 根据订单信息获取商户私有域数据
     *
     */
    protected function getVprivInfo($orders){
        if($orders['o_promotion_price'] > 0){
            //获取子公司信息
            $subcompany = D('Subcompany');
            $array_subcompany = M('subcompany')->where(array('is_open'=>0))->order(array('s_sort'=>'asc'))->select();
            $order = D('Orders');
            //订单原价
            $orders_price = sprintf("%0.2f",$orders['o_all_price'] + $orders['o_promotion_price']);
            //满减优惠金额
            $o_promotion_price = $orders['o_promotion_price'];
            $f = array();
            $int_nums = 0;
            //获取订单详情
            $o_items = $order->getOrdersData(array('fx_orders.o_id'=>$orders['o_id']),array('fx_orders.o_id','fx_orders.o_all_price','fx_orders_items.oi_id','fx_orders_items.g_id','fx_orders_items.pdt_id','fx_orders_items.oi_price','fx_orders_items.oi_cost_price','fx_orders_items.pdt_sale_price','fx_orders_items.oi_nums'));
            //判断订单详情里的商品是否属于子公司，并将子公司id返回
            foreach ($o_items as $key=>$val){
                $f[$val['pdt_id']]['s_id'] = $subcompany->getCompanyByGid($val['g_id']);
            }
            $array = array();
            foreach ($array_subcompany as $sb_k=>$sb_v){
                $array['sid'.$sb_v['s_id']] = 0;
                foreach ($f as $fk=>$fv){
                    if($fv['s_id'] != '' && $sb_v['s_id'] == $fv['s_id']){
                        //获取商品原价
                        $sale_price = $this->getOrdersSalePrice($o_items,$fk);
                        $array['sid'.$sb_v['s_id']] += sprintf("%0.2f",$sale_price/$orders_price*$o_promotion_price);
                    }
                }
            }
            $return = '';
            foreach ($array as $k){
                $return .= $k.';';
            }
            $return = rtrim($return,';');
            return $return;
        }else{
            return '';
        }
    }
    
    private function getOrdersSalePrice($orders,$pdt_id){
        foreach($orders as $key=>$val){
            if($val['pdt_id'] == $pdt_id){
                return $val['pdt_sale_price'];
            }
        }
        return 0.00;
    }

}