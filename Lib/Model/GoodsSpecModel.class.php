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
class GoodsSpecModel extends GyfxModel {

    /**
     * 获取货品的销售规格属性
     * @param  int  pdt_id  货品的id
     * @param int $int_type <p>类型：1：属性名：属性值；，2：属性值  属性值
     * @author jiye
     * @data 2012-12-17
     * @modify 2013-8-15 haophper
     * @return  string
     */
    public function getProductsSpec($pdt_id = "", $int_type=1,$is_cache=0) {
       /*  $arr_id_data = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.g_id","gs_name","gsd_value"))
                ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))->select(); */
               // dump(D("RelatedGoodsSpec")->getLastSql());die();
             //  echo D("RelatedGoodsSpec")->getLastSql();exit;
        //获取属性名称时，读取RelatedGoodsSpec表中的自定义名称 -----By Joe modify 2013-08-21
		//$is_cache = 0;
		if($is_cache == 1){
			$cache_key = json_encode(array('goodsspec'=>'getProductsSpecs'.$pdt_id));
			if($arr_id_data = getCache($cache_key)){}else{
				$arr_id_data = D("RelatedGoodsSpec")
					->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
					->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
					->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.gsd_aliases as gsd_value","fx_related_goods_spec.g_id","gs_name","fx_related_goods_spec.gsd_picture"))
					->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))
					->order('fx_goods_spec.gs_order asc')->select();	
				 writeCache($cache_key,$arr_id_data,300);
			}
		}else{
	        $arr_id_data = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.gsd_aliases as gsd_value","fx_related_goods_spec.g_id","gs_name","fx_related_goods_spec.gsd_picture"))
                ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))
				->order('fx_goods_spec.gs_order asc')->select();	
		}
				
        $str_return = "";
        if (!empty($arr_id_data)) {
            foreach ($arr_id_data as $v) {
                if($int_type == 2) {
                    $str_return .= $v['gsd_value'].' ';
                } else  {
                    $str_return.=$v['gs_name'].':'.$v['gsd_value'].';';
                }
            }
        }else{
        	return '';
        }
        return substr($str_return, 0, -1);
    }
    
    /**
     * 获取货品的规格属性
     * @param  int  g_id  商品的id
     * @param int $int_type <p>类型：1：属性名：属性值；，2：属性值  属性值
     * @author wangguibin
     * @data 2013-09-03
     * @return  string
     */
    public function getGoodsSpec($g_id = "", $int_type=1) {
        $arr_id_data = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","pdt_id","gs_name","gsd_value"))
                ->where(array("g_id" => $g_id))->order('fx_goods_spec.gs_order asc')->select();
        $str_return = array();
        if (!empty($arr_id_data)) {
            foreach ($arr_id_data as $v) {
            	if(!empty($v['gsd_value'])){
            	    if($int_type == 2) {
	                    $str_return[]= array('gs_id'=>$v['gs_id'],'pdt_id'=>$v['pdt_id'],'gsd_value'=>$v['gsd_value']);
	                } else  {
	                    $str_return[]=$v['gs_name'].':'.$v['gsd_value'].';';
	                }           		
            	}
            }
        }else{
        	return $str_return;
        }
        return $str_return;
    }    

    /**
     * 获取货品的销售规格属性数组
     * @param  int  pdt_id  货品的id
     * @author jiye
     * @data 2012-12-17
     * @return  string
     */
    public function getProductsSpecs($pdt_id = "",$is_cache = false) {
		//$is_cache = false;
       /*  $arr_id_data = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.g_id","gs_name","gsd_value"))
                ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))->select(); */
               // dump(D("RelatedGoodsSpec")->getLastSql());die();
             //  echo D("RelatedGoodsSpec")->getLastSql();exit;
        //获取属性名称时，读取RelatedGoodsSpec表中的自定义名称 -----By Joe modify 2013-08-21
		$arr_id_data_query = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.gsd_aliases as gsd_value","fx_related_goods_spec.g_id","gs_name","fx_related_goods_spec.gsd_picture"))
                ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))
				->order('fx_goods_spec.gs_order desc')->group('gs_id');
		if($is_cache == true){
			$arr_id_data = $arr_id_data_query->queryCache($arr_id_data_query,'',100);
		}else{
			$arr_id_data = $arr_id_data_query->select();
		}
		/**
        $arr_id_data = D("RelatedGoodsSpec")
                ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
                ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
                ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.gsd_aliases as gsd_value","fx_related_goods_spec.g_id","gs_name","fx_related_goods_spec.gsd_picture"))
                ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))
				->order('fx_goods_spec.gs_order asc')->select();
		**/		
         //-------END-----------
        $str_return = "";
        $sku_name = "";
		$color = array();
		$size = array();
        $guigei = array();
        if ($arr_id_data) {
//        	if(count($arr_id_data)=='1'){
//        	    foreach ($arr_id_data as $v) {
//	                $str_return.=$v['gs_name'].':'.$v['gsd_value'].';';
//	                $sku_name.=$v['gsd_value'];
//	                $size = array($v['gs_name'],$v['gsd_value'],$v['gs_id'],$v['gsd_id']);
//	            }
//                $guigei[$pdt_id] = $v['gs_id'].':'.$v['gsd_id'];
//                    		
//        	}
//            if(count($arr_id_data)=='2'){
                $guigei_id = array();
        	    foreach ($arr_id_data as $key=>$v) {
        	    	if($key == '0'){
        	    		if(!empty($v['gsd_value'])){
							$gsdvalue = !empty($v['gsd_picture'])?$v['gsd_value']."|".$v['gsd_picture']:$v['gsd_value'];
        	    			$size = array($v['gs_name'],$gsdvalue,$v['gs_id'],$v['gsd_id']);
                            $guigei_id[] = $v['gs_id'].':'.$v['gsd_id'];
        	    		}
        	    	}
        	        if($key == '1'){
        	        	if(!empty($v['gsd_value'])){
                            //add by hcj   如果有颜色规格图片远程地址就显示图片，否则显示规格名称
                            $gsdvalue = !empty($v['gsd_picture'])?$v['gsd_value']."|".$v['gsd_picture']:$v['gsd_value'];
        	        		$color = array($v['gs_name'],$gsdvalue,$v['gs_id'],$v['gsd_id']);
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
//        }
        return array('size'=>$size,'color'=>$color,'spec_name'=>substr($str_return, 0, -1),'sku_name'=>substr($sku_name, 0, -1),'guigei'=>$guigei);
    }

    /**
     * 获取货品的销售规格属性数组
     * @param  int  pdt_id  货品的id
     * @param bool $is_cache 是否缓存
     * @param int $d 返回的规格维度,默认返回三维规格
     * @param bool $qiniu_pic
     * @author Nick
     * @data 2014-11-11
     * @return  array
     */
    public function getProductSpecs($pdt_id = -1, $is_cache = false, $d= 3, $qiniu_pic= false) {
        $pdt_id = intval($pdt_id);
        $d = intval($d);
        $arr_id_data_query = D("RelatedGoodsSpec")
            ->join("left join fx_goods_spec on fx_related_goods_spec.gs_id = fx_goods_spec.gs_id")
            ->join("left join fx_goods_spec_detail on fx_related_goods_spec.gsd_id = fx_goods_spec_detail.gsd_id")
            ->field(array("fx_related_goods_spec.gs_id","fx_related_goods_spec.gsd_id","fx_related_goods_spec.gsd_aliases as gsd_value","fx_related_goods_spec.g_id","gs_name","fx_related_goods_spec.gsd_picture"))
            ->where(array("pdt_id" => $pdt_id,'fx_related_goods_spec.gs_is_sale_spec'=>1))
            ->order('fx_goods_spec.gs_order desc')->group('gs_id');
        if($is_cache == true){
            $arr_id_data = $arr_id_data_query->queryCache($arr_id_data_query,'',100);
        }else{
            $arr_id_data = $arr_id_data_query->select();
        }
        /*@param $goods_spec= array(
         *  'pdt_spec_display' => '',   //gs_name.'：'.gsd_value.'；'.gs_name.'：'.gsd_value.'；'...
         *  'spec_list' =>  array(),    //规格列表
         *  'goods_properties' => array(), //商品属性详情
         * );
         */
        $goods_spec = $spec_list = $goods_property = array();
        $pdt_spec_display = $pdt_spec_detail_ids = '';
        if ($arr_id_data) {
            if($d <= 0) {
                $d = 5;
            }
            //循环获取所有的规格属性
            for($i=0; $i < $d; $i++) {
                if(isset($arr_id_data[$i]) && !empty($arr_id_data[$i])) {
                    $v = $arr_id_data[$i];
                    if($qiniu_pic == true ){//七牛图片显示
                        $v['gsd_picture'] = D('QnPic')->picToQn($v['gsd_picture']);
                    }
                    if(!empty($v['gsd_value'])){
                        $gsd_value = !empty($v['gsd_picture'])?$v['gsd_value']."|".$v['gsd_picture']:$v['gsd_value'];
                        //规格列表
                        $spec_list['_'.$v['gs_id']] = array(
                            'gs_id'   => $v['gs_id'],
                            'gs_name' => $v['gs_name'],
                            'gs_details' => array(
                                $v['gsd_id'] => array(
                                'gsd_id' => $v['gsd_id'],
                                'gsd_value' => $gsd_value
                                )
                            )
                        );
                        $v['str_spec_show'] = $v['gs_name'].'：'.$v['gsd_value'];
                        $pdt_spec_display .= '；' . $v['str_spec_show'];
                        $pdt_spec_detail_ids .= '_' . $v['gsd_id'];
                        $v['gsd_value'] = $gsd_value;
                        $goods_property[$v['gs_id']] = $v;
                    }
                }
            }
            $goods_spec['spec_list'] = $spec_list;
            $goods_spec['pdt_spec_display'] = ltrim($pdt_spec_display, '；');
            $goods_spec['pdt_spec_detail_ids'] = ltrim($pdt_spec_detail_ids, '_');
            $goods_spec['goods_properties'] = $goods_property;
        }
        return $goods_spec;
    }

    /**
     * 根据货品获取规格
     * @param  int  pdt_id  货品的id
     * @author jiye
     * @data 2012-12-17
     * @reture  array
     */
    private function getSpec($pdt_id = "") {
        if (isset($pdt_id)) {
            return D("GoodsSpec")->field(array("gs_name"))->where(array("gs_id" => $pdt_id))->find();
        }
    }

    /**
     * 根据货品获取属性
     * @param  int  pdt_id  货品的id
     * @author jiye
     * @data 2012-12-17
     * @reture  array
     */
    private function getPdtSpecDetail($gsd_id = "") {
        if (isset($gsd_id)) {
            return D("GoodsSpecDetail")->field(array("gsd_value"))->where(array("gsd_id" => $gsd_id))->find();
        }
    }

    public function getPdtSpecList($where){
         $res_products = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')
                                ->where($where)->select();
         return $res_products;       
    }

    public function getPdtSpec($where){
         $res_products = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')
                                ->where($where)->find();
         return $res_products;       
    }
}
