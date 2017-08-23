<?php

/**
 * 同步退货单信息
 * @package Common
 * @subpackage ErpReturn
 * @author Terry
 * @since 7.0
 * @version 1.0
 * @date 2013-2-28
 */
class ErpReturn extends ErpApi {

    private $errMsg = '';           //存放错误信息
    private $errRemind = array(
        'paramErr' => '参数有误！',
        'OrderErr' => '退货单ID不能为空！'
    );

    public function __construct() {
        parent::__construct();
    }

    /**
     * 后台同步新增退货单到ERP
     * @param  int $orid退货单号
     * @param string $params 其它信息
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-31
     */
    public function addReturn($orid,$params = array()) {
        $ary_res = array('success' => 0, 'msg' => '', 'errCode' => 0, 'data' => array());
        if (empty($orid)) {
            $this->errMsg = $this->errRemind['OrderErr'];
        }

        //根据退款单ID获取到退款信息
        $ary_refund = $this->getOrderReturn($orid,$params);
        
        if (empty($ary_refund['data']) && !is_array($ary_refund['data'])) {
            $this->errMsg = $ary_refund['msg'];
        }
        //再次根据会员邮箱校验
        $ary_member = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_email' => $ary_refund['data']['mail']))->find();
        
        if (empty($ary_member)) {
            throw new Exception('数据错误：会员（mail:' . $ary_refund['data']['mail'] . '）数据丢失！', 2003);
        }
        $top = Factory::getTopClient();
        $parameters = $ary_refund['data'];
        $data = $top->ReturnAdd($parameters);
        if ($top->getLastResponse()->isError()) {
           // Log::write('&&&&&&&&&&&&&&&&&&&&');
            //错误处理$top->getLastResponse()->getErrorInfo()
            $ary_res['errCode'] = '1003';
            $ary_res['msg'] = $top->getLastResponse()->getErrorInfo();
        } else {
            //数据处理
            $data = array(
                'or_return_sn' => $data['rid']
            );
            $where = array(
                'or_id' => $orid
            );
            //更新订单状态
            $ary_result = M("OrdersRefunds",C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($data);
            if ($ary_result) {
                $ary_res['success'] = '1';
                $ary_res['msg'] = '同步成功';
                $ary_res['info']    = $data['or_return_sn'];
            } else {
                $ary_res['errCode'] = '1004';
                $ary_res['success'] = '0';
                $ary_res['msg'] = '同步失败';
            }
        }
        return $ary_res;
    }



    protected function getOrderReturn($orid , $extra_param = array()) {
        
        $ary_res = array('success' => 0, 'msg' => '', 'errCode' => 0, 'data' => array());
        $ary_data = array();
        $return = D("ViewRefunds")->where(array("or_id" => $orid))->find(); // or_id orders_refunds 退款单号
        if (!empty($return) && is_array($return)) {
            $ary_member = D("ViewMembers")->where(array("m_id" => $return['m_id']))->find();
            if (!empty($ary_member) && is_array($ary_member)) {
                $ary_orders = D("ViewOrders")
                        ->field("fx_view_orders.*,fx_payment_cfg.erp_payment_id")
                        ->join('fx_payment_cfg ON fx_payment_cfg.pc_id=fx_view_orders.o_payment')
                        ->where(array("fx_view_orders.oi_id" => $return['oi_id']))
                        ->find();//oi_id  order_items 订单详情id | $return['oi_id'] orders_refunds_items 订单详情id

                $ary_log = D("Logistic")->getLogisticInfo(array('lt_id' => $ary_orders['lt_id']));
                
                if (!empty($ary_orders) && is_array($ary_orders)) {

                   $ary_refunds_items = D("OrdersRefundsItems")->join(" fx_orders_items ON fx_orders_refunds_items.oi_id=fx_orders_items.oi_id")->where(array("or_id" => $orid))->select();
				   
                   $ary_refunds_temp = array();
                   if($ary_refunds_items){
                        $ary_pdt_ids = array();
                        $ary_pdt_info = array();
                        foreach($ary_refunds_items as $val){
                             $ary_pdt_ids[] = $val['pdt_id'];
                             $ary_pdt_info[$val['pdt_id']] = $val;
                        }
                       
                        $where['pdt_id'] = array('in',$ary_pdt_ids);
                        $ary_product = M("GoodsProducts",C('DB_PREFIX'),'DB_CUSTOM')->field('fx_goods.g_sn,fx_goods_products.pdt_id,fx_goods_products.pdt_id,fx_goods_products.erp_sku_sn')
                                                        ->join("fx_goods ON fx_goods_products.g_id=fx_goods.g_id")
                                                         ->join("fx_goods_info ON fx_goods.g_id=fx_goods_info.g_id")
                                                        ->where($where)->select();
                         
                        //组装货品数据
                        $ary_product_items = array();
                       
                        if($ary_product){
                            foreach($ary_product as $val){
                                 $ary_product_items[$val['pdt_id']] = $val;
                            }

                            foreach($ary_product_items as $k=>$v){
                                if(!empty($v['g_sn']) && !empty($v['erp_sku_sn']) && isset($ary_pdt_info[$k])){
                                    $ary_refunds_temp['itemsns'][] =  $v['g_sn'];
                                    $ary_refunds_temp['skusns'][] =  $v['erp_sku_sn'];
                                    $ary_refunds_temp['nums'][] =  $ary_pdt_info[$k]['ori_num'];
                                    $ary_refunds_temp['prices'][] =  sprintf("%.2f",$ary_pdt_info[$k]['pdt_sale_price']);
                                    $ary_refunds_temp['pay_codes'][] =  $ary_orders['erp_payment_id'];
                                    $ary_refunds_temp['pay_moneys'][] =  $ary_pdt_info[$k]['ori_num']*$ary_pdt_info[$k]['oi_price']/$ary_pdt_info[$k]['oi_nums'];
                                    $ary_refunds_temp['pay_datatimes'][] = date("Y-m-d");
                                    if(!empty($return['or_account'])) $ary_refunds_temp['pay_accounts'][] = $return['or_account'];
                                    if(!empty($return['or_buyer_memo'])) $ary_refunds_temp['pay_memos'][] = $return['or_buyer_memo'];
                                }
                           }
                        }
                   }
                    
                   if (!empty($ary_refunds_temp) && is_array($ary_refunds_temp)) {
                        $ary_reason = D('Orders')->getReason(2);
                        if(isset($ary_reason[$extra_param['ary_reason']])) $refund_reason = $ary_reason[$extra_param['ary_reason']];
                        else $refund_reason = '';
                        $ary_data = array(
                            'mail' => $ary_member['m_email'],
                            'outer_shop_code' => $this->str_shop_code,
                            'outer_tid' => $ary_orders['o_id'],
                            'outer_refundid' => $orid,
                            'itemsns' => implode(',',$ary_refunds_temp['itemsns']),
                            'nums' => implode(',',$ary_refunds_temp['nums']),
                            'prices' => implode(',',$ary_refunds_temp['prices']),
                            'pay_codes' => implode(',',$ary_refunds_temp['pay_codes']),
                            'skusns' => implode(',',$ary_refunds_temp['skusns']),
                            'pay_moneys' =>implode(',',$ary_refunds_temp['pay_moneys']),
                            'pay_datatimes' => implode(',',$ary_refunds_temp['pay_datatimes']),
                            'logistics_type' => $ary_log[0]['erp_delivery_guid'],
                            'logistics_fee' => '0',
                            'trade_memo' =>'退货原因：' . $refund_reason . '退货说明：' . $return['or_buyer_memo']
                        );
                        if(isset($ary_refunds_temp['pay_accounts']) && !empty($ary_refunds_temp['pay_accounts'])){
                            $ary_data['pay_accounts'] = implode(',',$ary_refunds_temp['pay_accounts']);
                        }
                        if(isset($ary_refunds_temp['pay_memos']) && !empty($ary_refunds_temp['pay_memos'])){
                            $ary_data['pay_memos'] = implode(',',$ary_refunds_temp['pay_memos']);
                        }
                        /*if(isset($extra_param['trade_memo']) && !empty($extra_param['trade_memo'])){
                            $ary_data['trade_memo'] = $extra_param['trade_memo'];
                        }*/
                        if(isset($extra_param['invoice_no']) && !empty($extra_param['invoice_no'])){
                            $ary_data['invoice_no'] = $extra_param['invoice_no'];
                        }
                        unset($ary_refunds_temp);



                        $ary_res['data'] = $ary_data;
                        $ary_res['msg'] = '获取数据成功！';
                        $ary_res['success'] = '1';
                    } else {
                        $ary_res['success'] = '0';
                        $ary_res['errCode'] = '9003';
                        $ary_res['msg'] = '商品数据有误或者不存在！';
                    }
                } else {
                    $ary_res['success'] = '0';
                    $ary_res['errCode'] = '9002';
                    $ary_res['msg'] = '订单信息有误！';
                }
            } else {
                $ary_res['success'] = '0';
                $ary_res['errCode'] = '9001';
                $ary_res['msg'] = '会员不存在或已被删除！';
            }
        } else {
            $ary_res['success'] = '0';
            $ary_res['errCode'] = '9000';
            $ary_res['msg'] = '退货数据有误！';
        }
        return $ary_res;
    }

}
