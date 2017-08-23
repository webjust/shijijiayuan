<?php
/**
 * 订单发货
 *
 * API 参数
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author 	Zhangjiasuo
 * @date   2013-03-29
 * @license 
 * @version 1.0
 */
class Top_Request_TradeDelivery extends Top_Request 
{
    
}


/**
 * 修改订单
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'trades' => array(
 *         'trade' => array(
 *             array(
 *                 'id' =>2592
 *                 'hy_guid'=>'146C7CFD-026B-44D9-8AD9-EAF165C36E20',
 *                 'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                 'djbh' => 'DD00007715',
 *                 'lydh' => '83412656374952',
 *                 'wldh'=> 'DD00008069',
 *                 'wlgsdm' => 'YTO',
 *                 'wlgsmc'=> '圆通',
 *                 'wlfy'=> '5.0000',
 *                 'fhrq'=> '2012-08-03 12:00:38',
 *                 'shouhuor'=> '张三',  //收货人
 *                 'shrgddh'=> '021-12345678',   //收货人固定电话
 *                 'shrsj'=> '15800574430', 
 *                 'shdz'=>'上海市宝山区泰和路2038',
 *                 'shyb'=>'200000',
 *                 'shengmc'=>'江西省',
 *                 'shimc'=>'九江市',
 *                 'qumc'=>'永修县',
 *                 'fhr'=>'007员工',
 *                 'bz'=>'准时发货',
 *                 'ddspmxs' => array(
 *                 		'ddspmx' => array(
 *                         'id'=>4308,
 *                         'guid' => '95FF447C-9235-48EA-83F4-FFFFF7BABE47',
 *                         'fguid' => '1A222DF8-DB33-4377-8E53-D9AA6B46BD35',
 *                         'sp_guid' =>'D3A26F5D-3A01-42A1-AF1C-E51812FDC395',
 *                         'spdm'=>'QW002',
 *                         'SPMC'=>'试用商品2（空规格）',
 *                         'spdm2'=>'试2（空）', 
 *                         'skudm'=>'001',
 *                         'skumc'=>'M',
 *                         'SJJE'=>30.0000
 *                      )
 *                 )
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_TradeDelivery extends Top_Response
{
	protected function postParse(){
		if(isset($this->result['sendorder_get_response']['sendorders'])){
			$this->result = $this->result['sendorder_get_response']['sendorders'];
		}
	}
}

Top_ApiManager::add(
    'TradeDelivery',
    array(
        'method' => 'ecerp.sendorder.get',
        'parameters' => array(
            'required' => array(
                'fields'
            ),
            'other' => array(
                'condition',
                'page_no',
                'page_size',
                'orderby',
                'orderbytype'
            )
        ),
        'fields' => array(
            ':all' => array(
                '*'
            )
        )
    )
);

?>