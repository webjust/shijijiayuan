<?php

/**
 * 后台erp商品控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author listen 
 * @date 2013-01-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ErpProductsAction extends AdminAction {

    private $category_array = array();

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 后台商品控制器默认页，需要重定向
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function index() {
        $this->redirect(U('Admin/ErpProducts/erpPageList'));
    }

    /**
     * 获取ERP商品分类的子分类
     * @parm $category array 要查找的数组
     * @parm $fguid 父分类的guid
     * 
     */
    public function getErpGoodsChildrenCategory($category, $fguid = null) {
        for ($int_tmp_index = 0; $int_tmp_index < count($category); $int_tmp_index++) {
            if (null == $fguid && !isset($category[$int_tmp_index]['fguid'])) {
                $this->category_array[] = $category[$int_tmp_index]['code'];
                if (isset($category[$int_tmp_index]['isleaf']) && trim($category[$int_tmp_index]['isleaf']) == '0') {
                    //不是叶子节点，调用自身
                    $this->getErpGoodsChildrenCategory($category, $category[$int_tmp_index]['guid']);
                }
            } else {
                if ($fguid == $category[$int_tmp_index]['fguid']) {
                    $this->category_array[] = $category[$int_tmp_index]['code'];
                    if (isset($category[$int_tmp_index]['isleaf']) && trim($category[$int_tmp_index]['isleaf']) == '0') {
                        //不是叶子节点，调用自身
                        $this->getErpGoodsChildrenCategory($category, $category[$int_tmp_index]['guid']);
                    }
                }
            }
        }
    }

    /**
     * ERP组合商品高级同步
     * @author Terry<wanghui@guanyisoft.com>   
     * @date 2013-03-18
     */
    public function synAdvancedGoods() {
        $ary_get = $this->_request();
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "'";
        }
        if(!empty($ary_get['QY_SPZH']) && $ary_get['QY_SPZH'] == '1'){
            $condition .= " and QY_SPZH='1'";
        }
        $categorys = array();
        if (!empty($ary_get['category']) && isset($ary_get['category'])) {
            $category = new ErpCategoryAction();
            $ary_erpCategory = $category->erpCategoryList('', '100');

            foreach ($ary_get['category'] as $gcval) {
                $categorys = json_decode($gcval, true);
                $this->category_array[] = $categorys['code'];
                $this->getErpGoodsChildrenCategory($ary_erpCategory['categorys']['category'], $categorys['guid']);
            }
            foreach ($this->category_array as $val) {
                $str_lbcode .= "'" . $val . "',";
            }
            $condition .= " and lb_code in(" . trim($str_lbcode, ",") . ")";
        }
        if (!empty($ary_get['sc'])) {
            if (FALSE !== $ary_get['sj'] && isset($ary_get['sj'])) {
                $condition .= " and SJ='" . $ary_get['sj'] . "'";
            }
        } else {
            if (!empty($ary_get['sj'])) {
                $condition .= " and SJ='" . $ary_get['sj'] . "'";
            }
        }
        //echo "<pre>";print_r($condition);exit;
        if (!empty($ary_get['stockSymbol']) && !empty($ary_get['stock'])) {
            $skey = '';
            switch ($ary_get['stockSymbol']) {
                case 'gt':
                    $skey = '>';
                    break;
                case 'lt':
                    $skey = '<';
                    break;
                case 'eq':
                    $skey = '=';
                    break;
                case 'egt':
                    $skey = '>=';
                    break;
                case 'elt':
                    $skey = '<=';
                    break;
            }
            $condition .= " and SL2" . $skey . "'" . $ary_get['stock'] . "'";
            //echo "<pre>";print_r($condition);exit;
        }
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $page_no = max(1, (int) $this->_get('p', '', 1));
        $page_size = 20;
        $ary_goods = $this->erpGoodsList($page_no, $page_size, $condition);
        $ary_syns = array();
        if (!empty($ary_goods['shangpins']['shangpin'])) {
            foreach ($ary_goods['shangpins']['shangpin'] as $k => $v) {
                if (FALSE !== strpos($k, "lb_name")) {
                    $ary_goods['shangpins']['shangpin'][$k]['lb_name'] = $v["lb_name"];
                }
                $ary_wheres = array();
                $ary_wheres['fx_goods_info.erp_guid'] = $v['guid'];
                //是否同步erp 商品
                $ary_syns = $ary_b2b_goods = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field("fx_goods.g_on_sale")->join(" fx_goods ON fx_goods.g_id=fx_goods_info.g_id")->where($ary_wheres)->find();

                if (!empty($ary_get['sc'])) {
                    if (isset($ary_get['syn']) && $ary_get['syn'] == '0') {

                        if (!empty($ary_b2b_goods)) {
                            unset($ary_goods['shangpins']['shangpin'][$k]);
                        } else {
                            if ($ary_get['status'] == "1" && isset($ary_get['status'])) {
                                unset($ary_goods['shangpins']['shangpin'][$k]);
                                $ary_goods['total_results']--;
                            } else {
                                //分销上下架状态
                                $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = 2;
                                //不同步
                                $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 0;
                            }
                        }
                    } else {
                        if (empty($ary_b2b_goods)) {
                            unset($ary_goods['shangpins']['shangpin'][$k]);
                        } else {
                            if ($ary_get['status'] != $ary_b2b_goods['g_on_sale'] && isset($ary_get['status'])) {
                                unset($ary_goods['shangpins']['shangpin'][$k]);
                                $ary_goods['total_results']--;
                            } else {
                                $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = $ary_b2b_goods['g_on_sale'];
                                //已同步
                                $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 1;
                            }
                        }
                    }
                }
                //exit;
                if (empty($ary_get['sc'])) {
                    if (empty($ary_b2b_goods)) {
                        //分销上下架状态
                        $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = 2;
                        //不同步
                        $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 0;
                    } else {
                        $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = $ary_b2b_goods['g_on_sale'];
                        //已同步
                        $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 1;
                    }
                }
            }
            $ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0, 'errData' => array());
            if(!empty($ary_goods['shangpins']['shangpin']) && isset($ary_goods['shangpins']['shangpin'])){
                $res_category = $this->doAddErpCombinationGoods($ary_goods['shangpins']['shangpin']);
                if (!$res_category['success']) {
                    $ary_res['success'] = $res_category['success'];
                    $ary_res['errMsg'] = $res_category['errMsg'];
                    $ary_res['errRows']++;
                } else {
                    $ary_res['errMsg'] = $res_category['errMsg'];
                    $ary_res['succRows']++;
                }

                if(!$ary_res['succRows']){
                    $this->success("同步成功");
                    
                }else{
                    $this->error("同步失败");
                }
            }else{
                $this->error("缺少有效数据");
            }
        }
        
    }

    /**
     * erp商品列表
     * @param int $page 当前页 
     * int  $size 页面输出数 
     * string $condition  where条件 （字符串形式） “商品id = ‘123321’”
     * ary   $ary_fields 搜寻的字段
     * @author listen   
     * @date 2013-01-28
     */
    public function erpGoodsList($page = 1, $size = 20, $condition = "", $ary_fields = array(':all')) {
        $top = Factory::getTopClient();
       // $condition="zhanghao='001' and QY_SPZH='1' and SPDM='520520'";
        //echo $condition;exit;
        
        $data = array(
            'fields' => $ary_fields,
            'condition' => "{$condition}",
            'page_size' => $size,
            'page_no' => $page
        );
        
        $ary_erpGoods = $top->ItemGet($data);
        if (empty($ary_erpGoods) || !is_array($ary_erpGoods)) {
//            throw new Exception('接口返回数据有误，请检查API是否正确！', 3002);
//            exit;
            return false;
        }
        return $ary_erpGoods;
    }

    /**
     * 同步erp ++++++++++废弃+++++++++++ listen 2012-04-11
     * @author listen  
     * @date 2013-01-31
    
    public function doAddERPGoods($ary_erp_goods) {
        $res_erp_goods = false;
        $time = date("Y-m-d H:i:s");
        $obj_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
        $obj_goods->startTrans();
        //获取商品详细表的数组
        $ary_goods_info = array();
        if (!empty($ary_erp_goods)) {
            foreach ($ary_erp_goods as $k => $v) {
                //获取品牌
                if (isset($v['pp1mc']) && '' != $v['pp1mc']) {
                    $ary_brand = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_name' => $v['pp1mc']))->find();
                    if (!$ary_brand && empty($ary_brand)) {
                        $ary_brand_insert = array();
                        //品牌名字
                        $ary_brand_insert['gb_name'] = $v['pp1mc'];
                        //创建时间
                        $ary_brand_insert['gb_create_time'] = $time;
                        $res_brand = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_brand_insert);
                        if (!$res_brand) {
                            $obj_goods->rollback();
                            //echo "同步品牌出错";
                            return $res_erp_goods;
                            exit;
                        }
                        $ary_goods['gb_id'] = $res_brand;
                    } else {
                        $ary_goods['gb_id'] = $ary_brand['gb_id'];
                    }
                }
                //同步商品关系表 fx_goods
                $ary_goods['g_status'] = isset($v['ty']) ? $v['ty'] : 1;
                $ary_goods['g_on_sale'] = isset($v['sj']) ? $v['sj'] : 1;
                //默认从ERP下载的商品都是有效商品 By Wangguibin 2013-03-28
                $ary_goods['g_status'] = 1;
                $ary_goods['g_sn'] = $v['spdm']; 
                
                $ary_goods['g_gifts'] = $v['zp'];//是否赠品
                if(isset($v['zxfx'])){
                    //是否翻新
                    $ary_goods['g_new'] =  $v['zxfx'] == '1' ? '2':'0';
                }
                //翻新日期
                if(isset($v['zxfxrq'])){
                    $ary_goods['g_retread_date'] = $v['zxfxrq'];
                }
                //是否预售
                if(isset($v['zxys'])){
                    $ary_goods['g_pre_sale_status'] = $v['zxys'];
                }
                //预上架
                if(isset($v['zxysj'])){
                    $ary_goods['g_on_sale'] = $v['zxysj']=='1'?'3':1;
                }
                //预上架时间
                if(isset($v['zxysjrq']) && $v['zxysj']=='1'){
                    $ary_goods['g_on_sale_time'] = $v['zxysjrq'];
                }
                $str_goods_data = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->field('g_sn,g_id')->where(array('g_sn' => $v['spdm']))->find();
                if(empty($str_goods_data) && $str_goods_data==""){
					$res_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_goods);
                    if (!$res_goods) {//echo "商品关系同步失败";
                        $obj_goods->rollback();
                        return $res_erp_goods;
                    } else {
                        $gid = $res_goods;
                    }
				}else{
					$res_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_sn' => $v['spdm']))->save($ary_goods);
					$gid = $str_goods_data['g_id'];
				}
                //获取分类
                if (isset($v['lb_code']) && '' != $v['lb_code']) {
                    $ary_gc = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('erp_code' => $v['lb_code']))->find();
                    if (empty($ary_gc)) {
                        $ary_gc_insert = array();
                        //是否有父级
                        if (isset($v['lb2_code']) && '' != $v['lb2_code']) {
                            $ary_f_gc = D('GoodsCategory')->field('gc_id')->where(array('erp_code' => $v['lb2_code']))->find();
                            //echo  D('goods_category')->getLastSql();exit;
                            if (!empty($ary_f_gc)) {
                                $ary_gc_insert['gc_parent_id'] = $ary_f_gc['gc_id'];
                            } else {
                                $ary_f_gc_insert = array();
                                $ary_f_gc_insert['erp_code'] = $v['lb2_code'];
                                $ary_f_gc_insert['gc_name'] = $v['lb2_name'];
                                $ary_f_gc_insert['gc_is_parent'] = 1;
                                $ary_f_gc_insert['gc_create_time'] = $time;
                                $res_f_gc = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_f_gc_insert);
                                if (!$res_f_gc) {
                                    $obj_goods->rollback();
                                    //echo "分类父类更新失败";
                                    return $res_erp_goods;
                                } else {
                                    $ary_gc_insert['gc_parent_id'] = $res_f_gc;
                                }
                            }
                        } else {
                            $ary_gc_insert['gc_is_parent'] = 1;
                            $gcid = 0;
                        }
                        //更新分类
                        $ary_gc_insert['erp_code'] = $v['lb_code'];
                        $ary_gc_insert['gc_name'] = $v['lb_name'];
                        $ary_gc_insert['gc_create_time'] = $time;
                        $res_gc = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_gc_insert);
                        if (!$res_gc) {
                            $obj_goods->rollback();
                            //echo "分类更新失败";
                            return $res_erp_goods;
                        } else {
                            $gcid = $res_gc;
                        }
                    } else {
                        $gcid = $ary_gc['gc_id'];
                    }
                    //先删除原来商品的关系 
                    M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id' => $gid))->delete();
                    //更新商品与分类的关系表
                    $ary_rgc_insert = array();
                    $ary_rgc_insert['g_id'] = $gid;
                    $ary_rgc_insert['gc_id'] = $gcid;
                    $res_rgc = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_rgc_insert);
                    //echo M('related_goods_category')->getLastSql();exit;
                    if (!$res_rgc) {
//                        echo "分类关系更新失败";exit;
                        $obj_goods->rollback();

                        return $res_erp_goods;
                    }
                }
                //商品id
                $ary_goods_info['g_id'] = $gid;
                //商品名称
                $ary_goods_info['g_name'] = $v['spmc'];
                //商品单价
                $ary_goods_info['g_price'] = isset($v['bzsj']) ? sprintf("%.3f", $v['bzsj']) : 0.000;
                //库存
                $ary_goods_info['g_stock'] = isset($v['sl2']) ? (int) $v['sl2'] : 0;
                //商品重量
                $ary_goods_info['g_weight'] = isset($v['zl']) ? sprintf("%.3f", $v['zl']) : 0.000;
                //商品单位
                //echo $v['danwei'];exit;
                $ary_goods_info['g_unit'] = isset($v['danwei']) ? $v['danwei'] : '';
                //产品介绍
                $ary_goods_info['g_desc'] = $v['spsm'];
                //商品图片
                if (isset($v['images']) && 1 == $v['images']) {
                    $ary_goods_info['g_picture'] = D('ErpProducts')->synEcErpGoodsImage($v['guid']);
                }
                //erp的GUID
                $ary_goods_info['erp_guid'] = $v['guid'];
                //记录创建时间
                $ary_goods_info['g_create_time'] = date("Y-m-d h:i:s");
                $str_goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field('erp_guid')->where(array('erp_guid' => $v['guid']))->find();
                
                if (empty($str_goods_info) && $str_goods_info == "") {
                    $res_goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_goods_info);
                } else {
					unset($ary_goods_info['g_id']);
                    $res_goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('erp_guid' => $v['guid']))->save($ary_goods_info);
                }
                //echo M('goods_info')->getLastSql();exit;
                if (!$res_goods_info) {
                    $obj_goods->rollback();
                    return $res_erp_goods;
                }
                if (!empty($v['spskus']['spsku']) && is_array($v['spskus']['spsku'])) {
                    //处理多规格货品
                    if (isset($v['spskus']['spsku']['guid'])) {
                        $v['spskus']['spsku'] = array($v['spskus']['spsku']);
                    }
                    $res_products = D('ErpProducts')->doSynProduct($gid, $v['spskus']['spsku']);
                    if (!$res_products) {
                        $obj_goods->rollback();
                        return $res_erp_goods;
                    }
                } else {
                    //处理单规格货品
                    $res_erp_porducts = D('ErpProducts')->doOneSynProduct($gid, $v);
                    if (!$res_erp_porducts) {
                        return $res_erp_goods;
                    }
                }
            }
            //D('ErpPorducts')->doSynProduct($gid,$ary_erp_goods);
        }
        $obj_goods->commit();
        $res_erp_goods = true;
        return $res_erp_goods;
    }
 */
    /**
     * ajax 同步全部商品
     * @author listen
     * @date 2013-02-22
     */
    public function ajaxSaveAllErpGoods() {
        //echo "123";exit;
        $ary_post = $this->_post();
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "'";
        }
        if (empty($ary_post)) {
            //获取商品总数
            $ary_category_count = $this->erpGoodsList(1, 1, $condition);
            $count = intval($ary_category_count['total_results']);
            echo $count;
            exit;
        } else {
            $ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0, 'errData' => array());
            $ary_erp_products = $this->erpGoodsList($ary_post['page_no'], $ary_post['page_size'], $condition);
            if (!empty($ary_erp_products['shangpins']['shangpin'])) {
                $res_category = $this->doAddErpCombinationGoods($ary_erp_products['shangpins']['shangpin']);
                if (!$res_category['success']) {
                    //$ary_res['errRows']++;
                    $ary_res['errRows'] = $ary_res['errRows']+count($ary_erp_products['shangpins']['shangpin']);
                } else {
                    //$ary_res['succRows']++;
                    $ary_res['succRows'] = $ary_res['succRows']+count($ary_erp_products['shangpins']['shangpin']);
                }
            }else{
            	$ary_res = array('success' => 0, 'errMsg' => 'ERP暂时连接失败，请点重试按钮,重新加载');
            }
            echo json_encode($ary_res);
            exit;
        }
    }

    /**
     * 商品部分同步 ++++++++废弃++++++++++ 2013-04-11 listen
     * @author listen 
     * @date 2013-02-26
   
    public function sysOneErpGoods() {
        $ary_goid = $this->_get('guid');

        if (!empty($ary_goid)) {
            foreach ($ary_goid as $guid) {
                $condition = "guid='" . $guid . "'";
                $ary_erp_products = $this->erpGoodsList(1, 1, $condition);

                $bool_goods = $this->doAddERPGoods($ary_erp_products['shangpins']['shangpin']);
            }
        }
        if ($bool_goods) {
            $this->success('同步成功');
        } else {
            $this->error('同步失败');
        }
    }
     * 
     */

    /**
     * 获取组合商品个数
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-3-12
     */
    public function getCombinationGoodsCount() {
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and QY_SPZH='1'";
        }
        $ary_count = $this->erpGoodsList('', '', $condition);
        $count = $ary_count['total_results'];
        echo $count;
        exit;
    }

    /**
     * 组合商品同步
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-3-12
     */
    public function doCombinationGoods() {
        $ary_post = $this->_post();
        $page_no = isset($ary_post['page_no']) ? $ary_post['page_no'] : 1;
        $page_size = isset($ary_post['page_size']) ? $ary_post['page_size'] : 1;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and QY_SPZH='1'";
        }
        $ary_result = $this->erpGoodsList($page_no, $page_size, $condition);
        $ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0, 'errData' => array());
        if (!empty($ary_result['shangpins']['shangpin']) && isset($ary_result['shangpins']['shangpin'])) {
            $res_category = $this->doAddErpCombinationGoods($ary_result['shangpins']['shangpin']);
            if (!$res_category['success']) {
                $ary_res['success'] = $res_category['success'];
                $ary_res['errMsg'] = $res_category['errMsg'];
                $ary_res['errRows']++;
            } else {
                $ary_res['errMsg'] = $res_category['errMsg'];
                $ary_res['succRows']++;
            }
        }
        echo json_encode($ary_res);
        exit;
    }
    
 	 /**
     * 拼接sql
     * 
     * @param array $params prepare SQL 中的参数
     * @return boolean
     * @author wangguibin 
     * @date 2013-04-25
     */
    public function execute($sql,array $params=null)
    {
       $statement = explode('?', $sql);
       if ( count($params) != count($statement)-1 ) {
           $sql = $sql . ' with bind parameters: [' . implode(', ', $params) . ']';
       } else {
            $sql = '';
            foreach ( $params as $i => $bind ) {
              $sql .= $statement[$i]
               . (is_string($bind) ? "'".$bind."'" : $bind);
              }
              $sql .= $statement[count($params)];
            }
       return $sql;
    }
    
 	/**
     * 批量更新数据
     * @author wangguibin 
     * @date 2013-04-26
     */
    public function batchUpdate($items,$table_name,$act = 'replace'){
    	if(empty($items)) return true;
		$_binds = array();
		$_values = array();
		foreach($items as $item){
			$_binds = array_merge($_binds,array_values($item));
			$_value = substr(str_repeat('?,', count($item)),0,-1);
			$_values[] = "({$_value})";
		}
		$_columns = implode(',', array_map(create_function('$a', 'return "`{$a}`";'),array_keys($item)));
		$_values = implode(',', $_values);
		$sql = "{$act} into {$table_name}({$_columns}) values {$_values}";
		$sql = $this->execute($sql,$_binds);
		$table = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
		$table->query($sql);
    }
    
    /**
     * 将ERP组合商品信息保存到分销本地
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-3-12
     */
    public function doAddErpCombinationGoods($ary_goods) {
    	$ary_res = array('success' => '1', 'errMsg' => '保存数据失败', 'errCode' => '1000', 'errData' => array());
    	//获取ERP数据报错返回False
    	//dump($ary_goods);die();
    	if(empty($ary_goods)){
    		$ary_res['errMsg'] = '获取ERP数据出错';
    		return $ary_res;
    	}
    	$goodsinfo = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
        $erp_guids = array();   
        foreach ($ary_goods as $erp_item){
        	$erp_guids[] = $erp_item['guid'];
        }
        $sql = 'select g_id,erp_guid from fx_goods_info where erp_guid  in(%s)';
        $sql = sprintf($sql,substr(str_repeat('?,', count($erp_guids)),0,-1));
        $sql = $this->execute($sql,$erp_guids);
        $existItems = $goodsinfo->query($sql); 
        $existItemGids = array();
        //更新商品
		foreach($existItems as $exist){
			$existItemGids[$exist['erp_guid']] = $exist['g_id'];
		}
    	$goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM');
		try {
            if (!empty($ary_goods) && is_array($ary_goods)) {
                $date = date("Y-m-d H:i:s");
                //获取商品品牌信息
				$b_obj = D("GoodsBrand");
				$brand_goods = $b_obj->getBrandIdByCodeName($ary_goods);//function 根据品牌代码与名称返回品牌ID  如果没有则返回0  创建品牌
				if(!isset($brand_goods) || empty($brand_goods)){
					$goods->rollback();
					throw new Exception('写入品牌数据失败！！', 10002);
				}else{
					$ary_goods = $brand_goods;
				}
				$goods->startTrans();
                $arr_brands = array();
                foreach ($ary_goods as $keygd => $valgd) {
                	//商品所属品牌ID
					$arr_brands['gb_id'] = $valgd['gb_id'];
                    //同步ERP上下架
                    $arr_brands['g_on_sale'] = $valgd['sj'] ? 1 : 2;
                    //同步ERP启用状态
                    $arr_brands['g_status'] = isset($valgd['qy']) ? $valgd['qy'] : '0';
                    //$array_good = $goods->where(array('g_sn' => $valgd['spdm']))->find();
                    if(isset($valgd['zxfx'])){
                    //是否翻新
                    $arr_brands['g_new'] =  $valgd['zxfx'] == '1' ? '2':'0';
                    }
                    //翻新日期
                    if(isset($valgd['zxfxrq'])){
                        $arr_brands['g_retread_date'] = $v['zxfxrq'];
                    }
                    //是否预售
                    if(isset($valgd['zxys'])){
                        $arr_brands['g_pre_sale_status'] = $valgd['zxys'];
                    }
                    //预上架
                    if(isset($valgd['zxysj'])){
                        $arr_brands['g_on_sale'] = $valgd['zxysj']=='1'?'3':1;
                    }
                    //预上架时间
                    if(isset($valgd['zxysjrq']) && $valgd['zxysj']=='1'){
                        $arr_brands['g_on_sale_time'] = $valgd['zxysjrq'];
                    }
                    //echo "<pre>";print_r($valgd);exit;
                    if (!empty($existItemGids[$valgd['guid']])) {
                        $agood = $goods->where(array('g_sn' => $valgd['spdm']))->data($arr_brands)->save();
                        if(FALSE !== $agood){
                            $agood = $existItemGids[$valgd['guid']];
                        }
                    } else {
                        $arr_brands['g_sn'] = $valgd['spdm'];
                        $agood = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->add($arr_brands);
                    }
                    if (FALSE === $agood) {
                        $goods->rollback();
                        throw new Exception('写入商品数据失败！！', 10003);
                    }
						
                    if (!empty($valgd['lb_code']) && isset($valgd['lb_code'])) {
                    	//类目
        				$c_obj = D("GoodsCategory");
			            //同步ERP商品分类
						$c_obj->addCidByCodeName($valgd['lb_code'],$valgd['lb_name'],$valgd['lb2_code'],$valgd['lb2_name'],$valgd['lb_guid'],$agood);//    
                    }
                    //goods_info表操作
                    $ary_info = array();
                    $ary_info['g_name'] = empty($valgd['spmc'])?$valgd['spdm']:$valgd['spmc'];
                    $ary_info['g_source'] = "erp";
                    $ary_info['g_price'] = isset($valgd['bzsj']) ? sprintf("%.3f", $valgd['bzsj']) : 0.000;
                    $ary_info['g_weight'] = isset($valgd['zl']) ? sprintf("%.3f", $valgd['zl']) : 0.000;
                    //$ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
                    //$condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and spdm='".$valgd['spdm']."'";
                    //$ary_stock = $this->MergerItemStockGet('', '', $condition);
                    //echo "<pre>";print_r($condition);exit;
                    $ary_info['g_stock'] = isset($valgd['sl2']) ? (int) $valgd['sl2'] : 0;
                    $ary_info['g_unit'] = isset($valgd['danwei']) ? $valgd['danwei'] : '';
                    $ary_info['g_desc'] = $valgd['spsm'];
					//商品图片
                    $ary_imgs = array();
                    //下载ERP图片
                    $ary_imgs['g_picture'] = D('ErpProducts')->synEcErpGoodsImage($valgd['guid']);
                    //echo "<pre>";print_r($ary_imgs);exit;
					//同步ERP商品图片信息到本地
        			$p_obj = D("GoodsPictures");
					$ary_info['g_picture'] = $p_obj->addGoodsPictures($ary_imgs,$agood);//  	
          			//更新商品明细表信息(goods_info)
                    $ary_info['g_create_time'] = $date;
                    $product = D("ErpProducts");
                    //商品最高和最低价插入
                    $ary_pdt_price = $product->getPriceRange($valgd['spskus']['spsku']);
                    $ary_info['mi_price'] = $ary_pdt_price['mi_price'];
                    $ary_info['ma_price'] = $ary_pdt_price['ma_price'];
                    
                    //$goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('erp_guid' => $valgd['guid']))->find();
                    if (!empty($existItemGids[$valgd['guid']])) {
                        $res_goods_info = $goodsinfo->where(array('erp_guid' => $valgd['guid']))->save($ary_info);
                    } else {
                        $ary_info['g_id'] = $agood;
                        $ary_info['erp_guid'] = $valgd['guid'];
                        $res_goods_info = $goodsinfo->add($ary_info);
                    }
                    if (FALSE === $res_goods_info) {
                        $goods->rollback();
                        //dump($goodsinfo->getLastSql());die();
                        throw new Exception('商品详细表更新失败', 10007);
                    }
                    //处理规格商品
                    $array_goods = array();
                    $array_goods['g_id'] = $agood;
                    $array_goods['g_sn'] = $valgd['spdm'];
                    if (!empty($valgd['spskus']['spsku']) && isset($valgd['spskus']['spsku'])) {
                        //判断是否为多规格商品
                        if (count($valgd['spskus']['spsku']) > 1 && isset($valgd['spskus']['spsku'][0])) {
                            $massage_flag = array();
                            foreach ($valgd['spskus']['spsku'] as $skuval) {
                                $arr_products = $product->saveGoodsByOne($array_goods, $skuval);
                                if (!empty($arr_products) && $arr_products['success'] == '1') {
                                    $massage_flag[] = $arr_products['success'];
                                }
                            }
                            if (count($valgd['spskus']['spsku']) != count($massage_flag)) {
                                $goods->rollback();
                                throw new Exception("多规格数据更新失败", 10009);
                            }
                        } else {
                            $arr_products = $product->saveGoodsByOne($array_goods, $valgd['spskus']['spsku']);
                            if (!empty($arr_products) && $arr_products['success'] == '0') {
                                $goods->rollback();
                                throw new Exception($arr_products['errMsg'], $arr_products['errCode']);
                            }
                        }
                    } else {
                        $ary_sps = array();
                        //无规格商品信息入库
                        $valgd['erp_sku_sn'] = $valgd['spdm'];
                        $valgd['erp_guid'] = $valgd['guid'];
                        $ary_sps['spskus']['spsku'] = $valgd;
                        $ary_sps['spskus']['spsku']['skudm'] = $valgd['spdm'];
                        $arr_products = $product->saveGoodsByOne($array_goods, $ary_sps['spskus']['spsku']);
                        if (!empty($arr_products) && $arr_products['success'] == '0') {
                            $goods->rollback();
                            throw new Exception($arr_products['errMsg'], $arr_products['errCode']);
                        }
                    }
                }
            } else {
                $goods->rollback();
                throw new Exception('缺少有效数据！！', 10001);
            }
        } catch (Exception $e) {
            $ary_res['success'] = 0;
            $ary_res['errMsg'] = $e->getMessage();
            $ary_res['errCode'] = $e->getCode();
        }
        $goods->commit();
        return $ary_res;
    }

    /**
     * 表单单个同步
     * @date 2013-3-13
     * @author  Terry<wanghui@guanyisoft.com>
     */
    public function synOneGoods() {
        
        $ary_res = array('success' => '1', 'errMsg' => '同步成功', 'errCode' => '1000', 'errData' => array());
        $ary_post = $this->_post();
        $page_no = isset($ary_post['page_no']) ? $ary_post['page_no'] : 1;
        $page_size = isset($ary_post['page_size']) ? $ary_post['page_size'] : 1;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value']."'";
        }
        if(!empty($ary_post['QY_SPZH']) && isset($ary_post['QY_SPZH'])){
            $condition .= " and QY_SPZH='1'";
        }
        //print_r($ary_post);exit;
        //赠品同步
        if(!empty($ary_post['ZP']) && isset($ary_post['ZP'])){
            $condition .= " and ZP='1'";
        }
        if (!empty($ary_post['spdm']) && isset($ary_post['spdm'])) {
          
            $condition .= " and SPDM='" . $ary_post['spdm'] . "'";
            //echo $condition;exit;
            $ary_result = $this->erpGoodsList($page_no, $page_size, $condition);
            //echo "<pre>";print_r($ary_result);exit;
            if (!empty($ary_result) && (int) $ary_result['total_results'] > 0) {
                $arr_res = $this->doAddErpCombinationGoods($ary_result['shangpins']['shangpin']);
                if (!$arr_res['success']) {
                    //echo $arr_res['errMsg'];exit;
                    $this->error($arr_res['errMsg']);
                } else {
                    $this->success("同步成功");
                }
            } else {
                $this->error("货号 " . $ary_post['spdm'] . " 不存在");
            }
        } else {
           
            $this->error("货号不能为空");
        }
    }

    /**
     * 批量同步商品
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-13
     */
    public function synBatGoods() {
        $ary_post = $this->_post();
        if (!empty($ary_post['guid']) && isset($ary_post['guid'])) {
            $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
            if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
                $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "'";
                if(!empty($ary_post['QY_SPZH']) && isset($ary_post['QY_SPZH'])){
                    $condition .= " and QY_SPZH='1'";
                }
                //赠品同步
                if(!empty($ary_post['ZP']) && isset($ary_post['ZP'])){
                    $condition .= " and ZP='1'";
                }
                $str_guid = '';
                $ary_guid = explode(",", $ary_post['guid']);
                foreach ($ary_guid as $val) {
                    $str_guid .= "'" . $val . "',";
                }
                $condition .= " and SPDM in(" . trim($str_guid, ",") . ")";
                //echo '<pre>';print_r($condition);
                $ary_result = $this->erpGoodsList('', '', $condition);
                //echo '<pre>';print_r($ary_result);exit;
                if (!empty($ary_result) && (int) $ary_result['total_results'] > 0) {
                    $arr_res = $this->doAddErpCombinationGoods($ary_result['shangpins']['shangpin']);
                    if (!$arr_res['success']) {
                        //echo $arr_res['errMsg'];exit;
                        $this->error($arr_res['errMsg']);
                    } else {
                        $this->success("同步成功");
                    }
                } else {
                    $this->error("未获取到相应数据");
                }
            } else {
                $this->error("ERP连接处于关闭状态");
            }
        } else {
            $this->error("请选择需要同步的商品");
        }
    }

    public function getErpGoodCategory() {
        $ary_request = $this->_request();
        $category = new ErpCategoryAction();
        $ary_erpCategory = $category->erpCategoryList('', '100');
        if (!empty($ary_erpCategory['categorys']['category']) && is_array($ary_erpCategory['categorys']['category'])) {
            if (isset($ary_erpCategory['categorys']['category']['guid'])) {
                $ary_erpCategory['categorys']['category'] = array($ary_erpCategory['categorys']['category']);
            }
            foreach ($ary_erpCategory['categorys']['category'] as $keycg => $valcg) {
                if (empty($valcg['fguid']) && !isset($valcg['fguid'])) {
                    $ary_erpCategory['categorys']['category'][$keycg]['fguid'] = '0';
                }
            }
            //$arr_category = $this->getBuildTree($ary_erpCategory['categorys']['category'], '0');
            $list = D("ErpProducts")->toFormatTree($ary_erpCategory['categorys']['category'], 'name', 'guid', 'fguid');
            foreach ($list as $keyl => $vall) {
                $list[$keyl]['gc_json'] = htmlspecialchars(json_encode($vall));
            }
        }
        $this->assign("filter", $ary_request);
        $this->assign("category", $list);
        $this->display();
    }
    
    /**
    * 赠品列表
    * @author listen
    * @date 2013-04-11
    */
   public function GiftsPageList(){
       $this->getSubNav(3, 0, 20,'赠品列表');
        $ary_get = $this->_request();
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and zp='1'" ;
        }
        if (!empty($ary_get['search']) && $ary_get['search'] == 'easy') {
            switch ($ary_get['field']) {
                case 'SPDM':
                    if(!empty($ary_get['val'])){
                        $condition .= " and SPDM='" . $ary_get['val'] . "'";
                    }
                    break;
                case 'SPMC':
                    if(!empty($ary_get['val'])){
                        $condition .= " and SPMC LIKE('%" . $ary_get['val'] . "%')";
                    }
                    break;
            }
        } else {
            $categorys = array();
            if (!empty($ary_get['category']) && isset($ary_get['category'])) {
                $category = new ErpCategoryAction();
                $ary_erpCategory = $category->erpCategoryList('', '100');

                foreach ($ary_get['category'] as $gcval) {
                    $categorys = json_decode($gcval, true);
                    $this->category_array[] = $categorys['code'];
                    $this->getErpGoodsChildrenCategory($ary_erpCategory['categorys']['category'], $categorys['guid']);
                }
                foreach ($this->category_array as $val) {
                    $str_lbcode .= "'" . $val . "',";
                }
                $condition .= " and lb_code in(" . trim($str_lbcode, ",") . ")";
            }
            if (!empty($ary_get['sc'])) {
                if (FALSE !== $ary_get['sj'] && isset($ary_get['sj'])) {
                    $condition .= " and SJ='" . $ary_get['sj'] . "'";
                }
            } else {
                if (!empty($ary_get['sj'])) {
                    $condition .= " and SJ='" . $ary_get['sj'] . "'";
                }
            }
            //echo "<pre>";print_r($condition);exit;
            if (!empty($ary_get['stockSymbol']) && !empty($ary_get['stock'])) {
                $skey = '';
                switch ($ary_get['stockSymbol']) {
                    case 'gt':
                        $skey = '>';
                        break;
                    case 'lt':
                        $skey = '<';
                        break;
                    case 'eq':
                        $skey = '=';
                        break;
                    case 'egt':
                        $skey = '>=';
                        break;
                    case 'elt':
                        $skey = '<=';
                        break;
                }
                $condition .= " and SL2" . $skey . "'" . $ary_get['stock'] . "'";
                //echo "<pre>";print_r($condition);exit;
            }
        }
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $page_no = max(1, (int) $this->_get('p', '', 1));
        $page_size = 20;
       // echo "<pre>";print_r($condition);exit;
        $ary_goods = $this->erpGoodsList($page_no, $page_size, $condition);
        $ary_syns = array();
        if (!empty($ary_goods['shangpins']['shangpin'])) {
            foreach ($ary_goods['shangpins']['shangpin'] as $k => $v) {
                if (FALSE !== strpos($k, "lb_name")) {
                    $ary_goods['shangpins']['shangpin'][$k]['lb_name'] = $v["lb_name"];
                }
                $ary_wheres = array();
                $ary_wheres['fx_goods_info.erp_guid'] = $v['guid'];
                //是否同步erp 商品
                $ary_syns = $ary_b2b_goods = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field("fx_goods.g_on_sale")->join(" fx_goods ON fx_goods.g_id=fx_goods_info.g_id")->where($ary_wheres)->find();
                
                if (!empty($ary_get['sc'])) {
                    if (isset($ary_get['syn']) && $ary_get['syn'] == '0') {
                        if (!empty($ary_b2b_goods)) {
                            unset($ary_goods['shangpins']['shangpin'][$k]);
                        } else {
                            if ($ary_get['status'] == "1" && isset($ary_get['status'])) {
                                unset($ary_goods['shangpins']['shangpin'][$k]);
                                $ary_goods['total_results']--;
                            } else {
                                //分销上下架状态
                                $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = 2;
                                //不同步
                                $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 0;
                            }
                        }                       
                    } else {
//                        echo "<pre>";print_r($ary_goods);
                        if (empty($ary_b2b_goods)) {
                            unset($ary_goods['shangpins']['shangpin'][$k]);
                        } else {
                            if ($ary_get['status'] != $ary_b2b_goods['g_on_sale'] && isset($ary_get['status'])) {
                                unset($ary_goods['shangpins']['shangpin'][$k]);
                                $ary_goods['total_results']--;
                            } else {
                                $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = $ary_b2b_goods['g_on_sale'];
                                //已同步
                                $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 1;
                            }
                        }
                    }
                }
//                exit;
                if (empty($ary_get['sc'])) {
                    if (empty($ary_b2b_goods)) {
                        //分销上下架状态
                        $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = 2;
                        //不同步
                        $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 0;
                    } else {
                        $ary_goods['shangpins']['shangpin'][$k]['is_fx'] = $ary_b2b_goods['g_on_sale'];
                        //已同步
                        $ary_goods['shangpins']['shangpin'][$k]['is_tp'] = 1;
                    }
                }
            }
        }
        //echo "<pre>";print_r($ary_goods);exit;
        $count = $ary_goods['total_results'];
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign("erp", $ary_erp);
        $this->assign('ary_goods', $ary_goods['shangpins']['shangpin']);
        $this->assign("page", $page);
        $this->assign("filter", $ary_get);
        $this->display();
   }
      /**
     * 获取赠品个数
     * @author listen
     * @date 2013-04-11
     */
    public function getGiftsCount() {
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and QY_SPZH='1'";
        }
        $ary_count = $this->erpGoodsList('', '', $condition);
        $count = $ary_count['total_results'];
        echo $count;
        exit;
    }

    /**
     * 赠品同步
     * @author listen
     * @date 2013-04-11
     */
    public function doGiftsGoods() {
        $ary_post = $this->_post();
        $page_no = isset($ary_post['page_no']) ? $ary_post['page_no'] : 1;
        $page_size = isset($ary_post['page_size']) ? $ary_post['page_size'] : 1;
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        if (!empty($ary_api_conf) && $ary_api_conf['SWITCH']['sc_value'] == '1') {
            $condition = "zhanghao='" . $ary_api_conf['SHOP_CODE']['sc_value'] . "' and QY_SPZH='1'";
        }
        $ary_result = $this->erpGoodsList($page_no, $page_size, $condition);
        $ary_res = array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0, 'errData' => array());
        if (!empty($ary_result['shangpins']['shangpin']) && isset($ary_result['shangpins']['shangpin'])) {
            $res_category = $this->doAddErpCombinationGoods($ary_result['shangpins']['shangpin']);
            if (!$res_category['success']) {
                $ary_res['success'] = $res_category['success'];
                $ary_res['errMsg'] = $res_category['errMsg'];
                $ary_res['errRows']++;
            } else {
                $ary_res['errMsg'] = $res_category['errMsg'];
                $ary_res['succRows']++;
            }
        }
        echo json_encode($ary_res);
        exit;
    }

}

?>
