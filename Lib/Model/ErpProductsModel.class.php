<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 分销商用户模型
 * @package Model
 * @version 7.0
 * @author listen
 * @date 2013-01-31
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class ErpProductsModel extends GyfxModel {

    private $formatTree; //用于树型数组完成递归格式的全局变量

    /**
     * 构造方法
     * @author listen 
     * @date 2012-12-14
     */

    public function __construct() {
        parent::__construct();
    }

    /**
     * erp 商品图片处理
     * @author listen
     * @param type $str_ecerp_guid
     * @return string
     */
    public function synEcErpGoodsImage($str_ecerp_guid = '') {
       
        if (!$str_ecerp_guid || '' == $str_ecerp_guid) {
            return '';
        }
        
        $img_path = '/Public/Uploads/' . CI_SN . '/'.'goods/'.date('Ymd').'/';
        if (!is_dir(APP_PATH .$img_path)) {
            //如果目录不存在，则创建之
            mkdir(APP_PATH .$img_path, 0777, 1);
        }
        //获取商品图片并保存
        //$str_erp_img_url = D('SysConfig')->getConfigs('GY_ERP_API');
		$str_erp_img_url = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);
        $ary_img = array();
        for($i=1;$i<=5;$i++){
            //生成本地分销地址
            $imge_url = $img_path . $str_ecerp_guid ."-".$i . '.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $imge_url;
            //dump($img_save_path);die();
            $str_ecerp_image_api = $str_erp_img_url['IMG_URL']['sc_value'] . "ProductImgHandler.ashx?PID=" . $str_ecerp_guid ."&image=".$i;
            $img_content = file_get_contents($str_ecerp_image_api);
            if($img_content != '暂无图片'){
                $int_result = file_put_contents($img_save_path, file_get_contents($str_ecerp_image_api));
                if ($int_result != 12) {
                    $ary_img[$i] = $imge_url;
                }
            }
        }
        return $ary_img;
    }

    /**
     * erp商品分类处理
     * @param $erp_lb_code erp类型代码
     * @param $g_id 商品id
     * @author listen
     * @date 2013-01-31
     * @return bool $return_rgc
     */
    public function doErpCategory($g_id, $erp_lb_code) {
        $return_rgc = false;
    }

    /**
     * erp多规格商品处理 ++++++++++++ 废弃 +++++++++++++++++
     * @author listen
     * @date 2013-02-04
    
    public function doSynProduct($g_id, $ary_products) {
        $ary_products_insert = array();
        $bool_products = false;

        if (!empty($ary_products)) {
            foreach ($ary_products as $ary_temp_products) {
                $ary_products_insert['g_id'] = $g_id;
                $ary_products_insert['pdt_sn'] = $ary_temp_products['skudm'];
                $ary_products_insert['pdt_sale_price'] = isset($ary_temp_products['bzsj']) ? sprintf("%.3f", $ary_temp_products['bzsj']) : 0.000;
                $ary_products_insert['pdt_cost_price'] = isset($ary_temp_products['bzjj']) ? sprintf("%.3f", $ary_temp_products['bzjj']) : 0.000;
                $ary_products_insert['pdt_market_price'] = 0.000;
                $ary_products_insert['pdt_weight'] = isset($ary_temp_products['zl']) ? $ary_temp_products['zl'] : 0;
                $ary_products_insert['pdt_stock'] = isset($ary_temp_products['sl2']) ? $ary_temp_products['sl2'] : 0;
                $ary_products_insert['erp_guid'] = (string) $ary_temp_products['guid'];
                $ary_products_insert['pdt_status'] = (int) $ary_temp_products['is_del'] ? 0 : 1;
                $ary_products_insert['erp_sku_sn'] = isset($ary_temp_products['skudm']) ? $ary_temp_products['skudm'] : '';
                $ary_products_insert['erp_agency_price'] = isset($ary_temp_products['dlsj']) ? sprintf("%.3f", $ary_temp_products['dlsj']) : 0.000;
                $ary_products_insert['erp_sku_memo'] = mysql_escape_string($ary_temp_products['ggbz']);
                $ary_products_insert['pdt_on_way_stock'] = isset($ary_temp_products['sl1']) ? $ary_temp_products['sl1'] : 0;
                $ary_products_insert['pdt_create_time'] = date('Y-m-d H:i:s');
                $ary_products_insert['g_sn'] = $ary_temp_products['spdm']; 
                if(isset($ary_temp_products['zxcjdhs'])){
                    $ary_products_insert['factory_arrival_start_date'] = $ary_temp_products['zxcjdhs'];//厂家计划最早到货日
                }
                if(isset($ary_temp_products['zxcjdhe'])){
                    $ary_products_insert['factory_arrival_end_date'] = $ary_temp_products['zxcjdhe'];//厂家计划最晚到货日
                }
                if(isset($ary_temp_products['zxcnfws'])){
                    $ary_products_insert['promise_send_start_date'] = $ary_temp_products['zxcnfws'];//承诺最早发货日
                }
                if(isset($ary_temp_products['zxcnfwe'])){
                    $ary_products_insert['promise_send_end_date'] = $ary_temp_products['zxcnfwe'];//承诺最晚发货日
                }

                if (isset($ary_temp_products['skudm'])) {
                    $ary_products_find = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $ary_temp_products['skudm']))->find();
                    if (empty($ary_products_find)) {
                        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_products_insert);
                        $pdt_id = $res_products;
                    } else {
                        $pdt_id = $ary_products_find['pdt_id'];
                        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $ary_temp_products['skudm']))->save($ary_products_insert);
                    }
                    if (isset($ary_temp_products['skumc']) || isset($ary_temp_products['gg1mc'])) {
                        $str_spec = $ary_temp_products['skumc'] . ',' . $ary_temp_products['gg2mc'];
                        $ary_spec = explode(',', $str_spec);
                        $res_spec = $this->doSynSaleProvValue($pdt_id, $ary_spec);
                        if (!$res_spec) {
                            return $bool_products;
                            exit;
                        }
                    }
                    if ($res_products) {
                        $bool_products = true;
                    }
                }
            }
        }
        return $bool_products;
    }
 */
    /** 
     * 处理单规格货品        ++++++ 废弃 +++++++++++==
     * @author listen   
     * @param $gid 商品id
     * @param array $ary_products  一维数组
     * @date 2013-02-19
   
    public function doOneSynProduct($gid, $ary_products) {
        //echo "<pre>";print_r($ary_products);exit;
        $ary_products_insert = array();
        $bool_products = false;
        if (!isset($gid)) {
            return $bool_products;
        }
        if (!empty($ary_products)) {
            $ary_products_insert['g_id'] = $gid;
            $ary_products_insert['pdt_sn'] = $ary_products['spdm'];
            $ary_products_insert['pdt_sale_price'] = isset($ary_products['bzsj']) ? sprintf("%.3f", $ary_products['bzsj']) : 0.000;
            $ary_products_insert['pdt_cost_price'] = isset($ary_products['bzjj']) ? sprintf("%.3f", $ary_products['bzjj']) : 0.000;
            $ary_products_insert['pdt_market_price'] = 0.000;
            $ary_products_insert['pdt_weight'] = isset($ary_products['zl']) ? $ary_products['zl'] : 0;
            $ary_products_insert['pdt_stock'] = isset($ary_products['sl2']) ? $ary_products['sl2'] : 0;
            $ary_products_insert['erp_guid'] = (string) $ary_products['guid'];
            $ary_products_insert['pdt_status'] = (int) $ary_products['is_del'] ? 0 : 1;
            $ary_products_insert['erp_sku_sn'] = ''; //isset($ary_products['skudm']) ? $ary_products['skudm'] : '';
            $ary_products_insert['erp_agency_price'] = isset($ary_products['dlsj']) ? sprintf("%.3f", $ary_products['dlsj']) : 0.000;
            $ary_products_insert['erp_sku_memo'] = mysql_escape_string($ary_products['ggbz']);
            $ary_products_insert['pdt_on_way_stock'] = isset($ary_products['sl1']) ? $ary_products['sl1'] : 0;
            $ary_products_insert['pdt_create_time'] = date('Y-m-d H:i:s');
            $ary_products_insert['g_sn'] = $ary_products['spdm']; 
            if(isset($ary_products['zxcjdhs'])){
                    $ary_products_insert['factory_arrival_start_date'] = $ary_products['zxcjdhs'];//厂家计划最早到货日
                }
                if(isset($ary_products['zxcjdhe'])){
                    $ary_products_insert['factory_arrival_end_date'] = $ary_products['zxcjdhe'];//厂家计划最晚到货日
                }
                if(isset($ary_products['zxcnfws'])){
                    $ary_products_insert['promise_send_start_date'] = $ary_products['zxcnfws'];//承诺最早发货日
                }
                if(isset($ary_products['zxcnfwe'])){
                    $ary_products_insert['promise_send_end_date'] = $ary_products['zxcnfwe'];//承诺最晚发货日
                }
            if (isset($ary_products['spdm'])) {
                $ary_products_find = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $ary_products['spdm']))->find();
                if (empty($ary_products_find)) {

                    $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_products_insert);
                    //echo M('goods_products')->getLastSql();
                } else {
                    $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $ary_products['spdm']))->save($ary_products_insert);
                }
                if ($res_products) {
                    $bool_products = true;
                }
            }
        } else {
            return $bool_products;
        }

        return $bool_products;
    }
  */
    /**
     * 规格属性处理
     * @author listen   
     * @param ary $ary_detail 需要的属性值的数组
     * @date 2013-02-04
     */
    public function doSynSaleProvValue($pdt_id, $ary_detail) {
        //在分销设置规格属性
        //先获取erp 属性
        //通过属性值找到对应的关系属性
        //没有找到找对应的关系时候添加默认属性 颜色尺码
        $bool_spec = false;
        if (!empty($ary_detail) && is_array($ary_detail)) {
            //先将货品与规格对应关系清理
            M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id' => $pdt_id))->delete();
            $ary_goods_spec = array(0 => '颜色', 1 => '尺码');
            $ary_gs_id = array();
			//时间格式有误 Update By Wangguibin
            $date = date('Y-m-d H:i:s');
            foreach ($ary_goods_spec as $k => $ary_temp_goods_spec) {
                $res_goods_spec = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->field('gs_id')->where(array('gs_name' => $ary_temp_goods_spec))->find();
                if (!empty($res_goods_spec)) {
                    $ary_gs_id[$k] = $res_goods_spec['gs_id'];
                } else {
                    $res_gs_id = M('goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->add(array('gs_name' => $ary_temp_goods_spec, 'gs_create_time' => $date));
                    $ary_gs_id[$k] = $res_gs_id;
                }
            }
            foreach ($ary_detail as $k1 => $ary_temp_detail) {
                $where = array('gsd_value' => $ary_temp_detail);
                $ary_spec_detail_res = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field(array('gs_id', 'gsd_id'))->where($where)->find();
                if (empty($ary_spec_detail_res)) {
                    $ary_spec_detail = array('gsd_value' => $ary_temp_detail, 'gs_id' => $ary_gs_id[$k1], 'gsd_create_time' => $date);
                    $res_rgs = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_spec_detail);
                    $gsd_id = $res_rgs;
                    $gs_id = $ary_gs_id[$k1];
                } else {
                    $gsd_id = $ary_spec_detail_res['gsd_id'];
                    $gs_id = $ary_spec_detail_res['gs_id'];
                }
                if (isset($pdt_id)) {
                    $ary_related_goods_spec = array('gsd_id' => $gsd_id, 'gs_id' => $gs_id, 'pdt_id' => $pdt_id);
                    $res_rgs = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_related_goods_spec);
                    if ($res_rgs) {
                        $bool_spec = true;
                    }
                }
            }
        }
        return $bool_spec;
    }

    /**
     * 处理规格商品
     * @param int $arr_goods 商品信息
     * @param array $arr_products 货品信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-3-12
     */
    public function saveGoodsByOne($arr_goods, $arr_product) {
        $ary_res = array('success' => '1', 'errMsg' => '保存数据成功', 'errCode' => '1000', 'errData' => array());
//        echo "<pre>";print_r($arr_product);
//        exit;
        try {
            if (empty($arr_goods)) {
                throw new Exception('缺少商品信息！', 20001);
            }
            if (!empty($arr_product) && is_array($arr_product)) {
                $ary_product = array();
                $ary_product['g_id'] = $arr_goods['g_id'];
                $ary_product['g_sn'] = $arr_goods['g_sn'];
                $ary_product['pdt_sale_price'] = isset($arr_product['bzsj']) ? sprintf("%.3f", $arr_product['bzsj']) : 0.000;
                $ary_product['pdt_cost_price'] = isset($arr_product['cbj']) ? sprintf("%.3f", $arr_product['cbj']) : 0.000;
                $ary_product['pdt_market_price'] = isset($arr_product['bzsj']) ? sprintf("%.3f", $arr_product['bzsj']) : 0.000;
                $ary_product['pdt_weight'] = isset($arr_product['zl']) ? $arr_product['zl'] : 0;
                $ary_product['pdt_stock'] = isset($arr_product['sl2']) ? $arr_product['sl2'] : 0;
                $ary_product['pdt_status'] = (int) $arr_product['is_del'] ? 0 : 1;
                $ary_product['erp_guid'] = (string) $arr_product['guid'];
                $ary_product['erp_sku_sn'] = isset($arr_product['skudm']) ? $arr_product['skudm'] : '';
                $ary_product['pdt_on_way_stock'] = isset($arr_product['sl1']) ? $arr_product['sl1'] : 0;
                $ary_product['pdt_create_time'] = date("Y-m-d H:i:s");
                if(isset($arr_product['zxcjdhs'])){
                    $ary_product['factory_arrival_start_date'] = $arr_product['zxcjdhs'];//厂家计划最早到货日
                }
                if(isset($arr_product['zxcjdhe'])){
                    $ary_product['factory_arrival_end_date'] = $arr_product['zxcjdhe'];//厂家计划最晚到货日
                }
                if(isset($arr_product['zxcnfws'])){
                    $ary_product['promise_send_start_date'] = $arr_product['zxcnfws'];//承诺最早发货日
                }
                if(isset($arr_product['zxcnfwe'])){
                    $ary_product['promise_send_end_date'] = $arr_product['zxcnfwe'];//承诺最晚发货日
                }

                if (!empty($arr_product['skudm']) && isset($arr_product['skudm'])) {
                    $product = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $arr_product['skudm']))->find();
                    if (empty($product)) {
                        $ary_product['pdt_sn'] = $arr_product['skudm'];
                        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_product);
                        if (!$res_products) {
                            throw new Exception('添加规格商品失败', 20004);
                        }
                        $pdt_id = $res_products;
                    } else {
                        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn' => $product['pdt_sn']))->save($ary_product);
                        if (!$res_products) {
                            throw new Exception('更新规格商品失败', 20005);
                        }
                         $pdt_id = $product['pdt_id'];
                    }
                    if (isset($arr_product['skumc'])) {
                        $str_spec =  $arr_product['gg1mc']. ',' . $arr_product['gg2mc'];
                        $ary_spec = explode(',', $str_spec);
                        
                        if(empty($ary_spec)){
                            $ary_spec = explode(' ', $arr_product['skumc']);
                        }
                        $res_spec = $this->doSynSaleProvValue($pdt_id, $ary_spec);
                        if (!$res_spec) {
                             throw new Exception('更新规格属性失败', 20005);
                            exit;
                        }
                    }
                } else {
                    throw new Exception('规格商品数据数据问题', 20003);
                }
            } else {
                throw new Exception('规格商品数据有误', 20002);
            }
        } catch (Exception $e) {
            $ary_res['success'] = 0;
            $ary_res['errMsg'] = $e->getMessage();
            $ary_res['errCode'] = $e->getCode();
        }
        return $ary_res;
    }
    
    /**
     * 获得规格商品最低和最高价
     * @param array $arr_products 货品信息
     * @author czy <chengzongyao@guanyisoft.com>
     * @date 2013-5-13
     */
    public function getPriceRange($arr_product = array()) {
        $ary_price = array();
        if (!empty($arr_product) && is_array($arr_product) && count($arr_product)>0) {
            foreach($arr_product as $val) {
                $ary_price[] = isset($val['bzsj']) ? sprintf("%.3f", $val['bzsj']) : 0.000;
            }
            return array('mi_price'=>min($ary_price) ,'ma_price'=>max($ary_price) );
        }
        else return array('mi_price'=>0 ,'ma_price'=>0 );
    }
    

    public function toFormatTree($list, $title = 'title', $pk = 'id', $pid = 'pid',$str='') {
        $list = $this->toTree($list, $pk, $pid);
        $this->formatTree = array();
        $this->_toFormatTree($list, 0, $title,$str);
        return $this->formatTree;
    }

    private function _toFormatTree($list, $level = 0, $title = 'title',$str='') {
        foreach ($list as $key => $val) {
            $tmp_str = str_repeat("&nbsp;&nbsp;", $level * 2);
            $tmp_str.=$str;
            $val['level'] = $level;
            $val['title_show'] = $tmp_str . $val[$title];
            if (!array_key_exists('_child', $val)) {
                array_push($this->formatTree, $val);
            } else {
                $tmp_ary = $val['_child'];
                unset($val['_child']);
                array_push($this->formatTree, $val);
                $this->_toFormatTree($tmp_ary, $level + 1, $title); //进行下一层递归
            }
        }
        return;
    }

    public function toTree($list = null, $pk = 'id', $pid = 'pid', $child = '_child') {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();

            foreach ($list as $key => $data) {
                $_key = is_object($data) ? $data->$pk : $data[$pk];
                $refer[$_key] = & $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = is_object($data) ? $data->$pid : $data[$pid];
                $is_exist_pid = false;
                foreach ($refer as $k => $v) {
                    if ($parentId == $k) {
                        $is_exist_pid = true;
                        break;
                    }
                }
                if ($is_exist_pid) {
                    if (isset($refer[$parentId])) {
                        $parent = & $refer[$parentId];
                        $parent[$child][] = & $list[$key];
                    }
                } else {
                    $tree[] = & $list[$key];
                }
            }
        }
        return $tree;
    }

}

?>
