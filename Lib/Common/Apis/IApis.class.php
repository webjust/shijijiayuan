<?php

/**
 * 第三方接口
 * @package Common
 * @subpackage Api
 * @author zuojianghua
 * @date 2012-12-19
 */
interface IApis {

    /**
     * 获取订单数据
     * @param string $str_create_time 订单起始时间
     * @param int $int_page 页码
     * @param int $int_size 每页显示多少条
     */
    public function getOrdersList($str_create_time, &$int_total_nums ,$int_page = 1, $int_size = 20);

    /**
     * 获取店铺基本信息
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     * @param array $array_data 请求参数
     */
    public function getShopInfo($ary_data = array());
    
    /**
     * 获取订单基本信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-16
     * @param array $ary_data 请求参数
     */
    public function getThdTradeDetial($ary_data);
    
    /**
     * 发货
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-17
     * @param $tt_id 淘宝交易ID
     * @param $str_out_sid 运单号
     * @param $ary_company_code 物流公司
     */
    public function logisticsSend($tt_id, $str_out_sid, $ary_company_code);
}