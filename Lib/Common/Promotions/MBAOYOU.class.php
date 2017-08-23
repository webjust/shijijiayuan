<?php
/**
 * 促销 - 满就包邮
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-10
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MBAOYOU implements PromotionsOrder{
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
     * @date 2013-01-08
     * @param array $array_type 该促销规则的说明
     * @param array $array_param 该方法的具体配置项
     */
    public function __construct($array_type, $array_param = array()) {
        $this->type = 'Order';
        $this->code = 'MBAOYOU';
        $this->setCfg($array_param);
        $this->setRel($array_param);
    }

    /**
     * 获取该促销规则的说明
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getType(){
        return $this->type;
    }

    /**
     * 获取该促销规则的配置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getCfg(){
        return $this->config;
    }

    /**
     * 从提交的各种数据中过滤出本规则的基本项
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @param array $array_param 未过滤前的数组
     */
    public function setCfg($array_param){
        $data = array();
        $data['cfg_cart_start'] = (float) $array_param['cfg_cart_start'];
        $data['cfg_cart_end'] = (float) $array_param['cfg_cart_end'];
        //包邮额度
        $data['cfg_logistic_money'] = (float) $array_param['cfg_logistic_money'];
        $data['cfg_goods_area'] = (int) $array_param['cfg_goods_area'];
        $this->config = $data;
    }

    /**
     * 获取该促销规则与商品的关系数据
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     * @return array 返回对应关系的数组
     */
    public function getRel(){
      
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
    public function assignHtml(){
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
           if(!empty($ary_goods) && is_array($ary_goods)){
               foreach($ary_goods as &$value){
                   $price=json_decode($value['g_price_config'], true);
                   $value['g_price_config'] = $price['cfg_discount'];
                   $this->relation[$value['pdt_id']]=$value;
               }
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
    public function result_promotion($ary_pdt,$rule_info,$action=null,$pmn_id = 0) {
        $relation=$this->getRel();
        $config=$this->getCfg();
        $status=false;
        $ggp_name = M('related_promotion_goods_group', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pmn_id'=>$rule_info['pmn_id']))->field('gg_id')->select();
        $flg = true;
        foreach($ary_pdt as $key=>$item){
            $gp_id = M('goods_products as `gp` ', C('DB_PREFIX'), 'DB_CUSTOM')
                ->where(array('gp.pdt_id'=>$key))
                ->field('rgp.gg_id')
                ->join(C('DB_PREFIX').'related_goods_group as rgp on rgp.g_id=gp.g_id')->select();
            foreach ($gp_id as $key=>$val){
                if(!in_array($val['gg_id'],$ggp_name)){//订单只要有属于后台设定商品
                    $flg=false;
                    break;
                }
            }
            if(!$flg) break;
        }
        if($rule_info['pmn_id']!="" && $rule_info['again_discount']==1){
            $status=true;
            $logistic_price =$config['cfg_logistic_money'];
        }
        if($rule_info['pmn_id']==""){
            $status=true;
            $logistic_price =$config['cfg_logistic_money'];
        }
        if(!$flg) $status = false;
        return array('status'=>$status,'price'=>$logistic_price,'code'=>$this->code,'name'=>$this->promotion['pmn_name']);
    }
}