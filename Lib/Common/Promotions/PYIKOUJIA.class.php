<?php


/**
 * 促销 - 指定商品一口价
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.0
 * @author listen 
 * @date 2013-03-12
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PYIKOUJIA implements PromotionsItem{
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
     * 促销信息
     *
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
        $this->type = 'Item';
        $this->code = 'PYIKOUJIA';
        $this->setCfg($array_param);
        if($array_param['cfg_goods_area']==1){
            $this->setRel($array_param);
        }else{
            $this->setRel($array_param=array());
        }
        
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
     * @ modify by wanghaoyu 2013-09-26
     */
    public function setCfg($array_param) {
        $data = array();
        $data['cfg_goods_area'] = (int) $array_param['cfg_goods_area'];
        $data['cfg_use_again_discount'] = (int) $array_param['cfg_use_again_discount'];
        $data['cfg_discount_all'] = (int) $array_param['cfg_discounts_all'];
        //$data['cfg_discounts_system_all'] = (int) $array_param['cfg_discounts_system_all'];
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
        if(!empty($array_param['cfg_products'])){
            foreach($array_param['cfg_products'] as $k=>$v){
                if(!empty($v)){
                    foreach($v as $k1=>$v1 ){
                        $data[]=array(
                            'g_id'=>$k,
                            'pdt_id'=>$k1,
                            'g_price_config' => json_encode(array('cfg_products'=>$v1))
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
               $value['g_price_config'] = $price['cfg_products'];
               $this->relation[$value['pdt_id']]=$value;
           }
       }else{
            $this->relation['all']=array('pmn_id'=>$ary_param['pmn_id'],'g_price_config'=>$this->config['cfg_discounts_system_all']);
       }
       return $this;
    }
    
    /**
     * 促销结果信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-14
     * @param array $ary_param
     * @param array $first_data
     * @return array promotion
     */
    public function result_promotion($int_gid) {
        $status=false;
        $relation=$this->getRel();
        $config=$this->getCfg();
        if($config['cfg_goods_area']==1){
            if(array_key_exists($int_gid,$relation)){
                $flt_price = $relation[$int_gid]['g_price_config'];
                $pmn_id=$relation[$int_gid]['pmn_id'];
                $again_discount=$config['cfg_use_again_discount'];
                $promotion_name=$this->promotion['pmn_name'];
                $status=true;
            }
        }else{
            $flt_price = $config['cfg_discounts_system_all'];
            $pmn_id=$relation['all']['pmn_id'];
            $again_discount=$config['cfg_use_again_discount'];
            $promotion_name=$this->promotion['pmn_name'];
            $status=true;
        }
        return array('status'=>$status,'price'=>$flt_price,'pmn_id'=>$pmn_id,'name'=>$promotion_name,'again_discount'=>$again_discount,'code'=>$this->code);
    }
}

?>
