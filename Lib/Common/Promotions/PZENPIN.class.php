<?php


/**
 * 促销 - 指定赠品
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.0
 * @author listen 
 * @date 2013-03-12
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PZENPIN implements IPromotions{
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
        if($array_param['condition']==2){
            $data['cfg_cart_nums'] = $array_param['cfg_cart_nums'];
        }else{
            $data['cfg_cart_start'] = (float) $array_param['cfg_cart_start'];
            $data['cfg_cart_end'] = (float) $array_param['cfg_cart_end'];
            if (!empty($data['cfg_cart_start']) && !empty($data['cfg_cart_end']) && ($data['cfg_cart_start'] > $data['cfg_cart_end'])) {
                $tmp = $data['cfg_cart_start'];
                $data['cfg_cart_start'] = $data['cfg_cart_end'];
                $data['cfg_cart_end'] = $tmp;
            }
        }
        $data['condition'] = $array_param['condition'];
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
        //以货品的id 为主 一个货品id 一条数据
        //echo "<pre>";print_r($array_param['cfg_ptd_discounts']);exit;
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
     * @param int $int_cart 当前购物车数量总和 - 与购物车相关的促销规则用到 
     * @param int $flt_price 商品价格 - 与商品相关的促销规则用到
     * @return array 返回应用促销规则的结果，$return['result'] = true/false; $return['data] = float; $return['message'] = string;
     */
      public function promotion($int_pmn_id, $ary_param) {
       
       // $res_cfg = D('RelatedPromotionGoods')->where(array('pmn_id' => $int_pmn_id, 'pdt_id' => $int_pid))->find();
        //echo "<pre>";print_r($res_cfg);
        if($ary_param['type']!='5'){
            return array('result' => false, 'data' => '', 'message' => '促销规则应用失败');
        }
        $cfg = $this->config;
        if ($ary_param['car_nums'] == 0  && $ary_param['flt_cart'] == 0) {
            return array('result' => false, 'data' => $int_pmn_id, 'message' => '该货品ID不在促销规则范围内');
        } else {
            if($ary_param['car_nums'] != 0 && $cfg['cfg_discount']<=$ary_param['car_nums']){
               // $return_price =true;
                return array('result' => true, 'data' => $int_pmn_id, 'message' => '促销规则应用成功');
            }else if($ary_param['flt_cart'] != 0 && ($ary_param['flt_cart'] >=$cfg['cfg_cart_start'] && $ary_param['flt_cart']<=$cfg['cfg_cart_end'])  ){
                 return array('result' => true, 'data' => $int_pmn_id, 'message' => '促销规则应用成功');
            }   
        }
    }
}

?>
