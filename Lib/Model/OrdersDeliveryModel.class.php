<?php

/**
 * 发货单模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersDeliveryModel extends GyfxModel {

    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-03
     */

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 发货单处理
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @param array $data
     * @return string
     */
    public function doSynOrders($data) {
        if(empty($data)){
            return false;
        }else{
            M('','','DB_CUSTOM')->startTrans();
            $ary_result = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->field(array(o_id))->where(array('o_id' => $data['o_id']))->find();
            if(empty($ary_result)){
                $id = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
                if(!empty($id)){
                    foreach ($data['ddspmxs'] as $key=>$val){
                        $where=array(
                            'pdt_sn'=>$val['skudm'],
                            'o_id'=>$data['o_id']
                        );
                        $res_orders = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field('oi_id')->where($where)->find();
                        $delivery_items['od_id']=$id;
                        $delivery_items['o_id']=$data['o_id'];
                        $delivery_items['oi_id']=$res_orders['oi_id'];
                        $delivery_items['odi_num']=$val['sl'];
                        $res = M('orders_delivery_items',C('DB_PREFIX'),'DB_CUSTOM')->add($delivery_items);
                        if(empty($res)){
                            M('','','DB_CUSTOM')->rollback();
                            $this->error('发货单数据同步失败');
                        }else{
                            M('','','DB_CUSTOM')->commit();
                        }
                    }
                }else{
                     M('','','DB_CUSTOM')->rollback();
                     $this->error('发货单数据同步失败');
                }
                return $res;
            }
        }
    }
}