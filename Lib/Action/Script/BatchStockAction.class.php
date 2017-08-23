<?php
 /**
 * 商品库存更新计划任务
 *
 * @package Action
 * @stage 7.0
 * @author Zhangjiasuo
 * @date 2013-03-14
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class BatchStockAction extends Action{ 
	public function batch(){
		$i = 0;
		$page_no = 1;
		$top = Factory::getTopClient();
		$ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
		$condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value']."'";
		$data = array(
            'fields' => 'guid,sl2,zhanghao',
			'condition' => $condition,
            'page_size' => 10
        );
		while( true ){
			$data['page_no'] = $page_no;
			$ary_erpGoods = $top->StockGet($data);
			$ary_erp_Goods_data=$ary_erpGoods['stocks']['stock'];
			foreach($ary_erp_Goods_data as $value){
				//商品库存更新
				$ary_goods_stock['g_stock'] = isset($value['sl2']) ? (int) $value['sl2'] : 0;
				$ary_goods_guid  = $value['guid'];
				$res_products = M('goods_info')->where(array('erp_guid' => $ary_goods_guid))->save($ary_goods_stock);
				//货品库存更新
				if (!empty($value['spskus']['spsku']) && is_array($value['spskus']['spsku'])) {
					if (isset($value['spskus']['spsku']['guid'])) {
                        $value['spskus']['spsku'] = array($value['spskus']['spsku']);
                    }
					foreach($value['spskus']['spsku'] as $info){
						$ary_products_stock['pdt_stock'] = isset($info['sl2']) ? (int) $info['sl2'] : 0;
						$ary_goods_guid  = $info['guid'];
						$res_products = M('goods_products')->where(array('erp_guid' => $ary_goods_guid))->save($ary_products_stock);
					}
				}
				$i += 1; 
			}
			if ($i >= intval($ary_erpGoods['total_results'])){
				break;
			}
			$page_no += 1;
		}
	}
}
?>