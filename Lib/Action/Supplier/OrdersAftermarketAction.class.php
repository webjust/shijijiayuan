<?php

/**
 * 后台订单售后控制器
 *
 * @package OrdersAftermarket
 * @subpackage Admin
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-02-28
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrdersAftermarketAction extends AdminAction {
    
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU3_0'));
    }
    
    /**
     * 同步退款单
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-02-28
     */
    public function synErpRefunds(){
//        $ary_res	= array('success'=>0,'msg'=>'', 'errCode'=>0, 'data'=>array());
        $ary_post = $this->_post();
        if(empty($ary_post['val'])){
            $this->error("退款单据不存在！");
        }
        $refunds = new ErpRefund();
        $ary_result = $refunds->addRefund($ary_post['val']);
        if(!empty($ary_result) && $ary_result['success'] == '1'){
            $this->success("同步成功");
        }else{
            $this->error($ary_result['msg']);
        }
    }
    
    /**
     * 同步退货单
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-02-28
     */
    public function synErpReturn(){
        $ary_post = $this->_post();
        if(empty($ary_post['val'])){
            $this->error("退货单据不存在！");
        }
        $refunds = new ErpReturn();
        $ary_result = $refunds->addReturn($ary_post['val']);
        if(!empty($ary_result) && $ary_result['success'] == '1'){
            $this->success("同步成功");
        }else{
            $this->error($ary_result['msg']);
        }
    }
    
}