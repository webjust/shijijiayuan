<?php

/**
 * 获取货品的规格属性
 * @package Model
 * @version 7.0
 * @author  jiye
 * @date 2012-12-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiGoodsSpecModel extends GyfxModel {

    private $result = array(
        'code'    => '10302',       // 会员错误初始码
        'sub_msg' => '会员API错误', // 错误信息
        'status'  => false,         // 返回状态 : false 错误,true 操作成功.
        'info'    => array(),       // 正确返回信息
        );

    /**
     * 获取货品的规格属性数组
     * @param  int  pdt_id  货品的id
     * @author jiye
     * @data 2012-12-17
     * @return  string
     */
    public function getProductsSpecs($pdt_id = "",$g_id = "") {
		$ary_where = array("pdt_id" => $pdt_id);
		if(!empty($g_id)){
			$ary_where['g_id'] = $g_id;
		}
        $arr_id_data = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.g_id","gs_name","gsd_value"))
                ->where($ary_where)->select();
        $str_return = "";
        $sku_name = "";
		$color = array();
		$size = array();
        $guigei = array();
        if ($arr_id_data) {
        	if(count($arr_id_data)=='1'){
        	    foreach ($arr_id_data as $v) {
	                $str_return.=$v['gs_name'].':'.$v['gsd_value'].';';
	                $sku_name.=$v['gsd_value'];
	                $size = array($v['gs_name'],$v['gsd_value'],$v['gs_id'],$v['gsd_id']);
	            }
                
                
                $guigei[$pdt_id] = $v['gs_id'].':'.$v['gsd_id'];
                    		
        	}
            if(count($arr_id_data)=='2'){
                $guigei_id = array();
        	    foreach ($arr_id_data as $key=>$v) {
        	    	if($key == '0'){
        	    		if(!empty($v['gsd_value'])){
        	    			$size = array($v['gs_name'],$v['gsd_value'],$v['gs_id'],$v['gsd_id']);
                            $guigei_id[] = $v['gs_id'].':'.$v['gsd_id'];
        	    		}
        	    	}
        	        if($key == '1'){
        	        	if(!empty($v['gsd_value'])){
        	        		$color = array($v['gs_name'],$v['gsd_value'],$v['gs_id'],$v['gsd_id']);
                            $guigei_id[] = $v['gs_id'].':'.$v['gsd_id'];
        	        	}
        	    	}
        	    	if(!empty($v['gsd_value'])){
        	    		$str_return.=$v['gs_name'].':'.$v['gsd_value'].';';
        	    	}
	                if(!empty($v['gsd_value'])){
	                	$sku_name.=$v['gsd_value'].';';
	                }
	            }
                if(count($guigei_id) == 1) {
                    $guigei[$pdt_id] = $guigei_id[0];
                }
                elseif(count($guigei_id) == 2){
                    $guigei[$pdt_id] = $guigei_id[0].';'.$guigei_id[1];
                }  		
        	}
        }
        return array('size'=>$size,'color'=>$color,'spec_name'=>substr($str_return, 0, -1),'sku_name'=>substr($sku_name, 0, -1),'guigei'=>$guigei);
    }
    
    /**
     * [获取商品规格]
     * @param  [type] $params [description]
     * @author wanghaijun
     * @return [type]         [description]
     */
    public function getGoodsSpecByGid($params){
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $goodsSpec = D('GoodsSpec');
            
        $where = array();
        $where['g_id'] = $params['g_id'];
        $where['pdt_status'] = '1';
        $ary_pdt = $products->field("pdt_id")->where($where)->select();

        if (!empty($ary_pdt) && is_array($ary_pdt)) {
            $skus = array();
            foreach ($ary_pdt as $keypdt => $valpdt) {
                //获取其他属性
                $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                if (!empty($specInfo['color'])) {
                    if (!empty($specInfo['color'][1])) {
                        $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                    }
                }
                
                if (!empty($specInfo['size'])) {
                    if (!empty($specInfo['size'][1])) {
                        $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                    }
                }
            }
        }
        foreach ($skus as $key => &$sku) {
            $skus[$key] = array_unique($sku);
        }
        
        if(!isset($skus) || empty($skus)){
            $this->result['sub_msg'] = '无规格数据';
        }else{
            $ary_res = array();
            foreach($skus as $key=>$vo){
                sort($vo);
                $ary_res[] = array(
                    'sku_name'  => $key,
                    'sku_value' => $vo
                    );
            }
            $this->result['code'] = 10303;
            $this->result['info'] = $ary_res;
            $this->result['status'] = true;
            $this->result['sub_msg'] = '获取成功';
        }
        return $this->result;
    }
    
    /**
     * [获取商品规格详情]
     * @param [array] $params [description]
     * @author wanghaijun
     * @return [array]
     */
    public function GoodsSpecDetail($params){
        $this->result['code'] = 10304;
        $spec = $params['spec'];
        $filer_spec = array();
        $ary_spec = explode(';',$spec);
        foreach($ary_spec as $specName){
            $specTmp = explode(':',$specName);
            $filer_spec[$specTmp[0]] = $specTmp[1];
        }
        $where = array('g_id'=>$params['g_id']);
        $result = D('GoodsProducts')->getPdtBySpec($where,$filer_spec);
        if(empty($result)){
            $this->result['sub_msg'] = '不存在该规格';
        }else{
            $this->result['code'] = 10305;
            $this->result['info'] = $result;
            $this->result['status'] = true;
            $this->result['sub_msg'] = '获取成功';
        }
        return $this->result;
    }
}