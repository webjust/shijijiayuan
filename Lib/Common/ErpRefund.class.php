<?php
/**
 * 同步退款单信息
 * @package Common
 * @subpackage ErpRefund
 * @author Terry
 * @since 7.0
 * @version 1.0
 * @date 2013-2-28
 */
class ErpRefund extends ErpApi{
    private $errMsg = '';           //存放错误信息
    private $errRemind = array(
        'paramErr'		=> '参数有误！',
        'OrderErr'		=> '退款单ID不能为空！'
    );
    public function __construct(){
        parent::__construct();
    }

    /**
     * 后台同步新增退款单到ERP
     * @param  int $orid退款单号
     * @param string $params 其它信息
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-31
     */
    public function addRefund($orid,$params = array()){
        $ary_res	= array('success'=>0,'msg'=>'', 'errCode'=>0, 'data'=>array());
        try{
            if(empty($orid)){
                $this->errMsg = $this->errRemind['OrderErr'];
            }
            //根据退款单ID获取到退款信息
            $ary_refund = $this->getOrderRefund($orid);
            if(empty($ary_refund) && !is_array($ary_refund)){
                $this->errMsg = "未获取到退款单信息！";
            }
            //再次根据会员邮箱校验
            $ary_member = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_email'=>$ary_refund['m_email']))->find();
            if(empty($ary_member)){
                throw new Exception('数据错误：会员（mail:'.$ary_refund['m_email'].'）数据丢失！', 2003);
            }
            $ary_reason = D('Orders')->getReason(1);
            if(isset($ary_reason[$params['ary_reason']])) $refund_reason = $ary_reason[$params['ary_reason']];
            else $refund_reason = '';
            $parameters = array(
                'mail'=>$ary_refund['m_email'],
                'outer_shop_code'=>$this->str_shop_code,
                'outer_tid'=>$ary_refund['o_id'],
                'pay_codes'=>$ary_refund['erp_payment_id'],
                'pay_moneys'=>isset($params['or_money']) ? $params['or_money'] : sprintf("%.2f",$ary_refund['or_money']),
                'outer_refundid'=>$ary_refund['or_id'],
                'bank_account'=>$ary_refund['or_account'],
                'ywydm'=>'',
                'trade_memo'=>'退款原因：' . $refund_reason . '退款说明：' . $ary_refund['or_buyer_memo'],
            );
            //Log::write(implode(',',array_values($parameters)));
            $top = Factory::getTopClient();
            $data = $top->RefundAdd($parameters);
            if($top->getLastResponse()->isError()){
                //错误处理$top->getLastResponse()->getErrorInfo()
                $ary_res['errCode']    = '1003';
                $ary_res['msg'] = $top->getLastResponse()->getErrorInfo();
                //Log::write(implode(',',array_values($ary_res)));
            }else{
                //数据处理
                $data = array(
                    'or_return_sn'   => $data['djbh']
                );
                $where = array(
                    'or_id' =>$orid
                );
                //更新订单状态
               $ary_result = M("OrdersRefunds",C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($data);
                if($ary_result){
                    $ary_res['success']    = '1';
                    $ary_res['msg']    = '同步成功';
                    $ary_res['info']    = $data['or_return_sn'];
                }else{
                    $ary_res['errCode']    = '1004';
                    $ary_res['success']    = '0';
                    $ary_res['msg']    = '同步失败';
                }
            }
        }  catch (Exception $e){
            $ary_res['msg']	= $e->getMessage();
            $ary_res['errCode']	= $e->getCode();
        }
        return $ary_res;
    }



    protected function getOrderRefund($orid){
        $refund = M("OrdersRefunds",C('DB_PREFIX'),'DB_CUSTOM');
        $ary_where = array();
        $ary_where['fx_orders_refunds.or_id'] = $orid;
        $ary_where['fx_orders_refunds.or_refund_type'] = '1';
        $ary_result = $refund->field('fx_orders_refunds.*,fx_payment_cfg.erp_payment_id,fx_members.m_email')
                             ->join(' fx_orders ON fx_orders.o_id=fx_orders_refunds.o_id')
                             ->join(' fx_payment_cfg ON fx_orders.o_payment=fx_payment_cfg.pc_id')
                             ->join(' fx_members ON fx_orders_refunds.m_id=fx_members.m_id')
                             ->where($ary_where)
                             ->find();
        //echo "<pre>";print_r($refund->getLastSql());exit;
        if(!empty($ary_result) && is_array($ary_result)){
            return $ary_result;
        }else{
            return array();
        }
    }
}
