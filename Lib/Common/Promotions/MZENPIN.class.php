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
class MZENPIN implements PromotionsOrder{
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
        $this->code = 'MZENPIN';
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
	 * @modified by wanghaoyu 2013-09-22
     */
    public function setRel($array_param) {
        $data = array();
        $data['cfg_products'] = $array_param['cfg_products'];
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
        $data['gifts'] = 1;
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
       return $this;
    }
    
    /**
     * 促销结果信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-17
     * @param array $ary_param
     * @param array $first_data
     * @return array promotion
     */
    public function result_promotion($ary_pdt,$rule_info,$action=null) {
        $status=false;
        if($rule_info['pmn_id']!="" && $rule_info['again_discount']==1){
            $res = D('RelatedPromotionGoods')->join('fx_goods_products on fx_goods_products.pdt_id = fx_related_promotion_goods.pdt_id')
                ->field(array('fx_related_promotion_goods.pmn_id','fx_goods_products.pdt_id'))
                ->where(array('fx_related_promotion_goods.pmn_id'=>$this->promotion['pmn_id'],'fx_goods_products.pdt_stock'=>array('GT', 0)))
                ->select();
            $status=true;
        }
        if($rule_info['pmn_id']==""){
            $res = D('RelatedPromotionGoods')->join('fx_goods_products on fx_goods_products.pdt_id = fx_related_promotion_goods.pdt_id')
                ->field(array('fx_related_promotion_goods.pmn_id','fx_goods_products.pdt_id'))
                ->where(array('fx_related_promotion_goods.pmn_id'=>$this->promotion['pmn_id'],'fx_goods_products.pdt_stock'=>array('GT', 0)))
                ->select();
            $status=true;
        }
        $ggp_name = M('related_promotion_goods_group', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pmn_id'=>$rule_info['pmn_id']))->field('gg_id')->select();
        foreach($ary_pdt as $key=>$item){
            $gp_id = M('goods_products as `gp` ', C('DB_PREFIX'), 'DB_CUSTOM')
                ->where(array('gp.pdt_id'=>$key))
                ->field('rgp.gg_id')
                ->join(C('DB_PREFIX').'related_goods_group as rgp on rgp.g_id=gp.g_id')->select();
            foreach ($gp_id as $key=>$val){
                if(!in_array($val['gg_id'],$ggp_name)){//订单只要有属于后台设定商品
                    $status=false;
                    break;
                }
            }
            if(!$status) break;
        }
        return array('status'=>$status,'gifts_pdt'=>$res,'code'=>$this->code,'name'=>$this->promotion['pmn_name']);
    }
}
