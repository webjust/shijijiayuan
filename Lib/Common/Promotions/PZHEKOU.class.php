<?php

/**
 * 促销 - 指定商品直接打折
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-08
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PZHEKOU implements IPromotions {

    /**
     * 该促销规则的说明
     * @var array
     */
    private $type = array();

    /**
     * 该促销规则的基本配置信息
     * @var array
     */
    private $config = array();

    /**
     * 该促销规则与商品的对应关系信息
     * @var array
     */
    private $relation = array();

    /**
     * 构造方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @param array $array_type 该促销规则的说明
     * @param array $array_param 该方法的具体配置项
     */
    public function __construct($array_type, $array_param = array()) {
        $this->type = $array_type;
        $this->setCfg($array_param);
        $this->setRel($array_param);
    }

    /**
     * 获取该促销规则的说明
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getType() {
        return $this->type;
    }

    /**
     * 获取该促销规则的配置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getCfg() {
        return $this->config;
    }

    /**
     * 从提交的各种数据中过滤出本规则的基本项
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @param array $array_param 未过滤前的数组
     */
    public function setCfg($array_param) {
        $data = array();

        $data['cfg_cart_start'] = (float) $array_param['cfg_cart_start'];
        $data['cfg_cart_end'] = (float) $array_param['cfg_cart_end'];

        if ($data['cfg_cart_start'] and $data['cfg_cart_end'] and $data['cfg_cart_start'] > $data['cfg_cart_end']) {
            $tmp = $data['cfg_cart_start'];
            $data['cfg_cart_start'] = $data['cfg_cart_end'];
            $data['cfg_cart_end'] = $tmp;
        }

        $data['cfg_discount'] = $array_param['cfg_discount'];
        $this->config = $data;
    }

    /**
     * 获取该促销规则与商品的关系数据
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     * @return array 返回对应关系的数组
     */
    public function getRel() {
        return $this->relation;
    }

    /**
     * 从提交的各种数据中过滤出本规则与商品的关系数据
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     * @param array $array_param 未过滤前的数组
     */
    public function setRel($array_param) {
        $data = array();
        if(!empty($array_param['cfg_ptd_discounts'])){
            foreach($array_param['cfg_ptd_discounts'] as $k=>$v){
                //$data[]['g_price_config'] = json_encode($v);
                if(!empty($v)){
                    foreach($v as $k1=>$v1 ){
                        
                        $data[]=array(
                            'g_id'=>$k,
                            'pdt_id'=>$k1,
                            'g_price_config' => json_encode(array('cfg_discount'=>$v1))
                            );
                    }
                }
            }
        }
        /*
        foreach ($array_param['cfg_gids'] as $k => $v) {
            $data[$k] = array(
                'g_id' => $v,
                'g_price_config' => json_encode(array('cfg_discount' => $array_param['cfg_discounts'][$v]))
            );
        }
         * 
         */
        $this->relation = $data;
    }

    /**
     * 向HTML页面中设置额外的变量，例如待选择的商品数据
     * @auther zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     * @return array 返回本规则需要额外的数据数组
     */
    public function assignHtml() {
        $Goods = D("ViewGoods");
        $data = array();
        $data['search']['cates'] = $Goods->getCates();
        //$where = array();
        //$count = $Goods->where($where)->count();
        //$Page = new Page($count, 5);
        //$data['page'] = $Page->show();
        //$data['list'] = $Goods->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        return $data;
    }

    /**
     * 判断本促销规则是否生效
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $pmn_id 促销规则ID
     * @param int $int_cart 当前购物车价格总和 - 与购物车相关的促销规则用到
     * @param int $int_gid 商品ID - 与商品相关的促销规则用到
     * @param int $flt_price 商品价格 - 与商品相关的促销规则用到
     * @param int $int_mid 会员ID
     * @return array 返回应用促销规则的结果，$return['result'] = true/false; $return['data] = float; $return['message'] = string;
     */
    public function promotion($int_pmn_id, $ary_param) {
        if($ary_param['type'] !='1'||$ary_param['pid'] == 0){
            return array('result' => false, 'data' => $ary_param['price'], 'message' => '该商品ID不在促销规则范围内');
        }
        $res_cfg = D('RelatedPromotionGoods')->where(array('pmn_id' => $int_pmn_id, 'pdt_id' => $ary_param['pid']))->find();
       // echo "<pre>";print_r($res_cfg);exit;
        if (false == $res_cfg) {
            return array('result' => false, 'data' => $ary_param['price'], 'message' => '该商品ID不在促销规则范围内');
        } else {
            $cfg = json_decode($res_cfg['g_price_config'], true);
            //print_r($cfg);exit;
            $return_price = $ary_param['price'] * $cfg['cfg_discount'];;
            return array('result' => true, 'data' => $return_price, 'message' => '促销规则应用成功');
        }
    }

}