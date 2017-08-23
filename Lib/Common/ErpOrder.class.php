<?php
/**
 * 同步ERP订单
 * @package Common
 * @subpackage ErpOrder
 * @author Terry
 * @since 7.0
 * @version 1.0
 * @date 2013-1-30
 */
class ErpOrder{
    /**
     * 获取订单信息
     * @param int $oid 订单ID
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-30
     */
    public function getLocalOrdersItem($oid=''){
        $ary_res = array('success'=>'0','msg'=>'获取订单明细失败','errCode'=>'1000','data'=>array());
        $where = array();
        $order = M("orders",C('DB_PREFIX'),'DB_CUSTOM');
        if(!empty($oid) && intval($oid)){
            $where['fx_orders.o_id'] = $oid;
            $ary_res['data'] = $order->field('fx_orders.*,fx_payment_cfg.erp_payment_id,fx_members.m_id,fx_members.m_email')
                                     ->join(' fx_payment_cfg on fx_payment_cfg.pc_id=fx_orders.o_payment')
                                     ->join(' fx_members on fx_members.m_id=fx_orders.m_id')
                                     ->where($where)->find();
            //echo "<pre>";print_r($ary_res['data']);exit;
            if(!empty($ary_res['data']) && is_array($ary_res['data'])){
              
                if(($ary_res['data']['o_pay_status'] !=1&&$ary_res['data']['o_pay_status'] !=3) && ($ary_res['data']['o_payment']!=24 && $ary_res['data']['o_payment']!=20)){
                    $ary_res['success'] = '0';
                    $ary_res['msg'] = "该订单未支付！";
                }else{
                    if($ary_res['data']['o_status'] == '2' or $ary_res['data']['o_status'] == '4'){
                        $ary_data_msg  = array(
                            2 => '该订单为死单，不能同步到ERP！',
                           // 3 => '该订单已经暂停，目前不可同步！', //订单暂定状态订单锁定状态可以同步到erp
                            4 => '该订单已经完成，不能再此同步！'
                        );
                        $ary_res['success'] = '0';
                        $ary_res['msg'] = $ary_data_msg[$ary_res['data']['o_status']];
                    }else{
                        $ary_res['data']['item'] = M("OrdersItems",C('DB_PREFIX'),'DB_CUSTOM')
                                                ->field('fx_goods_products.g_sn,fx_goods_products.pdt_sn,fx_orders_items.oi_price,fx_orders_items.oi_nums,fx_goods_products.pdt_sale_price')
                                                ->join(" fx_goods_products on fx_orders_items.pdt_id=fx_goods_products.pdt_id")
                                                ->where(array('fx_orders_items.o_id'=>$oid))
                                                ->select();
                        
                        if(!empty($ary_res['data']['item']) && is_array($ary_res['data']['item'])){
                            $ary_res['msg'] = '获取成功';
                            $ary_res['success'] = '1';
                        }else{
                            $ary_res['msg'] = '获取失败';
                            $ary_res['success'] = '0';
                            $ary_res['errCode'] = '1002';
                        }
                    }
                }
            }else{
                $ary_res['msg'] = '商品已被删除或已不存在';
                $ary_res['errCode'] = '1001';
            }
        }else{
            //用于批量同步
            $ary_res['data'] = $order->where($where)->select();
        }
        return $ary_res;

    }

    protected function matchLogisticsCompanieCode($str_name) {
        if(false !== stripos($str_name, '平邮')) {
            $str_code = 'POST';
            $str_name = '平邮';
        }elseif(false !== stripos($str_name, 'EMS')){
            $str_code = 'EMS';
            $str_name = '邮政EMS';
        }elseif(false !== stripos($str_name, '邮宝') || false !== stripos($str_name, 'e邮宝') || false !== stripos($str_name, 'E邮宝')){
            $str_code = 'EMS';
            $str_name = 'E邮宝';
        }elseif(false !== stripos($str_name, '申通')){
            $str_code = 'STO';
            $str_name = '申通快递';
        }elseif(false !== stripos($str_name, '圆通')){
            $str_code = 'YTO';
            $str_name = '圆通速递';
        }elseif(false !== stripos($str_name, '中通')){
            $str_code = 'ZTO';
            $str_name = '中通速递';
        }elseif(false !== stripos($str_name, '宅急送')){
            $str_code = 'ZJS';
            $str_name = '宅急送';
        }elseif(false !== stripos($str_name, '顺丰')){
            $str_code = 'SF';
            $str_name = '顺丰速运';
        }elseif(false !== stripos($str_name, '汇通')){
            $str_code = 'HTKY';
        }elseif(false !== stripos($str_name, '韵达')){
            $str_code = 'YUNDA';
            $str_name = '韵达快运';
        }elseif(false !== stripos($str_name, '天天')){
            $str_code = 'TTKDEX';
            $str_name = '天天快递';
        }elseif(false !== stripos($str_name, '联邦')){
            $str_code = 'FEDEX';
        }elseif(false !== stripos($str_name, '淘物流')){
            $str_code = 'TWL';
        }elseif(false !== stripos($str_name, '风火天地')){
            $str_code = 'FIREWIND';
        }elseif(false !== stripos($str_name, '华强')){
            $str_code = 'YUD';
        }elseif(false !== stripos($str_name, '烽火')){
            $str_code = 'DDS';
        }elseif(false !== stripos($str_name, '希伊艾斯')){
            $str_code = 'ZOC';
        }elseif(false !== stripos($str_name, '亚风')){
            $str_code = 'AIRFEX';
        }elseif(false !== stripos($str_name, '全一')){
            $str_code = 'APEX';
        }elseif(false !== stripos($str_name, '小红马')){
            $str_code = 'PONYEX';
        }elseif(false !== stripos($str_name, '龙邦')){
            $str_code = 'LBEX';
        }elseif(false !== stripos($str_name, '长宇')){
            $str_code = 'CYEXP';
        }elseif(false !== stripos($str_name, '大田')){
            $str_code = 'DTW';
        }elseif(false !== stripos($str_name, '长发')){
            $str_code = 'YUD';
        }elseif(false !== stripos($str_name, '特能')){
            $str_code = 'SHQ';
        }else{
            $str_code = 'OTHER';
        }
        return array('name'=>$str_name,'code'=>$str_code);
    }
}