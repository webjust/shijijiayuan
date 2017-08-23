<?php

/**
 * 得到商品信息
 *
 * API 参数
 *  - fields: 需要返回的商品对象字段。
 *
 * @package tom
 * @copyright Copyright (c) 2013, guanyisfot. inc
 * @author liu feng
 * @license 
 * @version 1.0
 */
class Top_Request_ItemGet extends Top_Request {
    
}

/**
 * 搜索商品信息
 * 返回值示例：
 * <code>
 * array(
 *     'total_results' => '10',
 *     'shangpins' => array(
 *         'shangpin' => array(
 *             array(
 *                 'guid' => 'B18E7201-AB1A-49D0-BE35-FF88F45A3AF1',
 *                 'spdm' => '520520',
 *                 'spmc' => '商品名称'
 *             )
 *         )
 *     )
 * )
 * </code>
 */
class Top_Response_ItemGet extends Top_Response {

    protected function postParse() {
        if (isset($this->result['shangpins'])) {
            if (isset($this->result['shangpins']['shangpin']['guid'])) {
                $this->result['shangpins']['shangpin'] = array($this->result['shangpins']['shangpin']);
            }
            
            foreach ($this->result['shangpins']['shangpin'] as &$item) {
                if (isset($item['skus']['sku']['guid'])) {
                    $item['skus']['sku'] = array($item['skus']['sku']);
                }
            }
            unset($item);
        }
    }

}

Top_ApiManager::add(
        'ItemGet', array(
                            'method' => 'ecerp.shangpin.get',
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
            'guid', 'spdm', 'spmc', 'spjc', 'ty', 'qy', 'sj', 'cjsj', 'kth', 'xp', 'tuijian', 'zp', 'zl', 'bzjj',
            'bzsj', 'cbj', 'danwei', 'pfsj', 'fkcck', 'images', 'spsm', 'zhanghao', 'sl3', 'sl2', 'sl1', 'sl',
            'lb_guid', 'lb_code', 'lb_name', 'lb2_code', 'lb2_name','pp1mc','pp1dm','pp2dm','pp2mc','pp3dm','pp3mc'
        )
    )
        )
);