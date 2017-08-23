<?php
/**
 * 
 * 测试类
 * @author Jerry
 *
 */
class TestAction extends Action {
    /**
     * 
     * 商品测试方法
     */
    public function shangpin() {
        $obj_Erp = new Erp();
        $ary_get = array(
            'g_sn' => 'test0002',
            'g_name' => 'test002',
            'sj' => 0
        );
        $ary_res = $obj_Erp->request('getGoodsPage', $ary_get);
        //echo "<pre>";print_r($ary_res);die;
    }
    /**
     * 
     * 结余款测试方法
     */
    
    public function getMemberBalance() {
        $obj_Erp = new Erp();
        $ary_get = array(
            'hy_guid' => '6A1113BA-0793-409D-A3FF-FB99C1A4D22D'
        );
        //6A1113BA-0793-409D-A3FF-FB99C1A4D22D
        $ary_res = $obj_Erp->request('getMemberBalance', $ary_get);
        //echo "<pre>";print_r($ary_res);die;
    }
}