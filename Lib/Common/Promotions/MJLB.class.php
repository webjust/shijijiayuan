<?php

/**
 * 促销 - 满就送金币
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.6
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2014-08-12
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MJLB implements PromotionsOrder {

    /**
     * 该促销规则的说明
     * @var array
     */
    protected $type = array();

    /**
     * 该促销规则的基本配置信息
     * @var array
     */
    protected $config = array();

    /**
     * 该促销规则与商品的对应关系信息
     * @var array
     */
    protected $relation = array();
    
    /**
     * 该促销规则基本信息
     * @var array
     */
    protected $promotion = array();
    
    public $code = null;

    /**
     * 构造方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-10
     * @param array $array_type 该促销规则的说明
     * @param array $array_param 该方法的具体配置项
     */
    public function __construct($array_type, $array_param = array()) {
        $this->type = 'Order';
        $this->code = 'MJLB';
        $this->setCfg($array_param);
        $this->setRel($array_param);
    }

    /**
     * 获取该促销规则的说明
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-10
     */
    public function getType() {
        return $this->type;
    }

    /**
     * 获取该促销规则的配置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-10
     */
    public function getCfg() {
        return $this->config;
    }

    /**
     * 从提交的各种数据中过滤出本规则的基本项
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-10
     * @param array $array_param 未过滤前的数组
     */
    public function setCfg($array_param) {
        $data = array();
        $data['cfg_cart_start'] = (float) $array_param['cfg_cart_start'];
        $data['cfg_cart_end'] = (float) $array_param['cfg_cart_end'];
        $data['cfg_discount'] = $array_param['cfg_discount'];
        $data['cfg_goods_area'] = (int) $array_param['cfg_goods_area'];
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
     */
    public function assignHtml() {
        $Goods = D("ViewGoods");
        $data = array();
        $data['search']['cates'] = $Goods->getCates();
        return $data;
    }
    /**
     * 设置促销信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-06
     * @param array $ary_param
     * @return OBJ
     */
    public function setpromotion($ary_param) {
       $this->promotion = $ary_param;
       if($this->config['cfg_goods_area']==1){
           $ary_goods=D('RelatedPromotionGoods')->Getgoods($ary_param['pmn_id']);
           foreach($ary_goods as &$value){
               $price=json_decode($value['g_price_config'], true);
               $value['g_price_config'] = $price['cfg_discount'];
               $this->relation[$value['pdt_id']]=$value;
           }
       }
       return $this;
    }
    
    /**
     * 促销结果信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-06
     * @param array $ary_param
     * @param array $first_data
     * @return array promotion
     */
    public function result_promotion($ary_pdt,$rule_info,$action=null) {
        
        $status=false;
        $config=$this->getCfg();
        $relation=$this->getRel();
        
        if($rule_info['pmn_id']!="" && $rule_info['again_discount']==1){
            if($config['cfg_goods_area']==1){
                foreach($relation as $key=>$item){
                    if(array_key_exists($key,$ary_pdt)){
                        $all_discount_price += sprintf("%0.2f",$ary_pdt[$key]['price']*$ary_pdt[$key]['num']);
                        unset($ary_pdt[$key]);
                    }
                }
                
                foreach($ary_pdt as $key=>$item){
                    $all_undiscount_price += sprintf("%0.2f",$item['price']*$item['num']);
                }
                if($all_discount_price>0){
					$status=true;
                    $promotion_name=$this->promotion['pmn_name'];
                    
                    $flt_price =  sprintf("%0.2f",($all_discount_price-$config['cfg_discount'])+$all_undiscount_price);
                }else{
                    $flt_price =  sprintf("%0.2f",$all_undiscount_price);
                }
            }else{
                foreach($ary_pdt as $key=>$item){
                    $all_price += sprintf("%0.2f",$item['price']*$item['num']);
                }
                $flt_price = $all_price -$config['cfg_discount'];
                $promotion_name=$this->promotion['pmn_name'];
                $status=true;
            }
        }
        
        if($rule_info['pmn_id']!="" && $rule_info['again_discount']==0){
            $flt_price = sprintf("%0.2f",$ary_param['all_price']);
        }
        if($rule_info['pmn_id']==""){
            if($config['cfg_goods_area']==1){
                foreach($relation as $key=>$item){
                    if(array_key_exists($key,$ary_pdt)){
                        $all_discount_price += sprintf("%0.2f",$ary_pdt[$key]['price']*$ary_pdt[$key]['num']);
                        unset($ary_pdt[$key]);
                    }
                }
                foreach($ary_pdt as $key=>$item){
                    $all_undiscount_price += sprintf("%0.2f",$item['price']*$item['num']);
                }
                if($all_discount_price>0){
					$status=true;
                    $promotion_name=$this->promotion['pmn_name'];
                    $flt_price =  sprintf("%0.2f",($all_discount_price-$config['cfg_discount'])+$all_undiscount_price);
                }else{
                    $flt_price =  sprintf("%0.2f",$all_undiscount_price);
                }
            }else{
                foreach($ary_pdt as $key=>$item){
                    $all_price += sprintf("%0.2f",$item['price']*$item['num']);
                }
                $flt_price =$all_price- $config['cfg_discount'];
                $promotion_name=$this->promotion['pmn_name'];
                $status=true;
            }
        }
        
        return array('status'=>$status,'price'=>$flt_price,'code'=>$this->code,'name'=>$promotion_name);
    }
    
}
