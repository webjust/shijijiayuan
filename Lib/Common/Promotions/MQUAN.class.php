<?php

/**
 * 促销 - 满就送优惠券
 *
 * @package Common
 * @subpackage Promotion
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-10
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MQUAN implements PromotionsOrder {

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
    protected $relations = array();
    
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
        $this->code = 'MQUAN';
        $this->setCfg($array_param);
        $this->setRel($array_param);
        $this->setAutoCou($array_param);
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
     * 获取批量添加优惠券分组信息
     * @author Joe  <qianyijun@guanyisoft.com>
     * @date 2013-11-13
     */
     public function getGroup() {
        return $this->relations;
    }

    /**
     * 从提交的各种数据中过滤出本规则的基本项
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-10
     * @param array $array_param 未过滤前的数组
     */
    public function setCfg($array_param) {
        $data = array();
        //优惠的满足条件
        $data['cfg_cart_start'] = (float) $array_param['cfg_cart_start'];
        $data['cfg_cart_end'] = (float) $array_param['cfg_cart_end'];
        // 是否有使用条件
        $data['cfg_condition'] = (string) $array_param['cfg_condition'];
        // 条件金额
        $data['cfg_condition_money'] = (float) $array_param['cfg_condition_money'];
        //优惠券面值
        $data['cfg_coupon_money'] = (float) $array_param['cfg_coupon_money'];
        //优惠券有效期（此处为一个时间长度，从下单起多久内有效，正整数型，单位为天数）
        $data['cfg_coupon_date'] = (int) $array_param['cfg_coupon_date'];
        //优惠券前缀，后缀，编码长度 - 方便自动生成
        $data['cfg_coupon_prefix'] = (string) $array_param['cfg_coupon_prefix'];
        $data['cfg_coupon_suffix'] = (string) $array_param['cfg_coupon_suffix'];
        $data['cfg_coupon_long'] = (int) $array_param['cfg_coupon_long'];
        $data['cfg_goods_area'] = (int) $array_param['cfg_goods_area'];
        //$data['cfg_coupon_group'] = $array_param['ggp_name'];
		$data['cfg_coupon_group'] = $array_param['coupon_group'];
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
        $data['ggp_name'] = isset($array_param['gp_group']) ? $array_param['gp_group'] : '';
        $this->relation = $data;
    }
    
    public function setAutoCou($array_param){
        $data = array();
        $data['ggp_name'] = $array_param['ggp_name'];
		$data['gc_name'] = $array_param['gc_name'];
		$data['gb_name'] = $array_param['gb_name'];
        $this->relations = $data;
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
     * @date 2013-06-19
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
     * @date 2013-06-19
     * @param array $ary_pdt
     * @param array $rule_info
     * @return array promotion
     */
    public function result_promotion($ary_pdt,$rule_info,$action=null,$m_id = 0) {
        $status=false;
        $config=$this->getCfg();
        $relation=$this->getGroup();
        $Coupon = D('Coupon');
        if($m_id == 0){
        	return array('status'=>false);
        }else{
        	$member['m_id'] = $m_id;
        }
        if(($rule_info['pmn_id']!="" && $rule_info['again_discount']==1) || empty($rule_info['pmn_id'])){
            if($config['cfg_condition'] == 'all') $config['cfg_condition_money'] = 0;
            if($config['cfg_goods_area']==1){
                $flg=false;
                foreach($ary_pdt as $key=>$item){
						$gp_id = M('goods_products as `gp` ', C('DB_PREFIX'), 'DB_CUSTOM')
                        ->where(array('gp.pdt_id'=>$key))
                        ->field('rgp.gg_id')
                        ->join(C('DB_PREFIX').'related_goods_group as rgp on rgp.g_id=gp.g_id')->select();
                        foreach ($gp_id as $val){
                            if(in_array($val['gg_id'],$relation['ggp_name']) && $item['type']==0){//订单只要有属于后台设定商品
                                $flg=true;
                                $promotion_name=$this->promotion['pmn_name'];
                                break;
                            }
                        }
						//获取商品类目
						$gc_id = M('goods_products as `gp` ', C('DB_PREFIX'), 'DB_CUSTOM')
                        ->where(array('gp.pdt_id'=>$key))
                        ->field('rgp.gc_id')
                        ->join(C('DB_PREFIX').'related_goods_category as rgp on rgp.g_id=gp.g_id')->select();
                        foreach ($gc_id as $val){
                            if(in_array($val['gc_id'],$relation['gc_name']) && $item['type']==0){//订单只要有属于后台设定商品
                                $flg=true;
                                $promotion_name=$this->promotion['pmn_name'];
                                break;
                            }
                        }						
						//获取商品所属品牌
						$gb_id = M('goods_products as `gp` ', C('DB_PREFIX'), 'DB_CUSTOM')
                        ->where(array('gp.pdt_id'=>$key))
                        ->field('rgp.gb_id')
                        ->join(C('DB_PREFIX').'goods as rgp on rgp.g_id=gp.g_id')->find();
						if(in_array($gb_id['gb_id'],$relation['gb_name']) && $item['type']==0){//订单只要有属于后台设定商品
							$flg=true;
							$promotion_name=$this->promotion['pmn_name'];
							break;
						}						
                }
                if($action=='paymentPage' && $flg && $rule_info['pmn_id']!="" && $rule_info['again_discount']==1){
                    $coupon_ids = $Coupon->autoAdd('促销活动赠送优惠券', $config['cfg_coupon_money'], 1, $config['cfg_coupon_long'], '促销活动赠送优惠券，系统自动生成', $config['cfg_coupon_prefix'], $config['cfg_coupon_suffix'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s', mktime() + 86400 * $config['cfg_coupon_date']), $config['cfg_condition_money'], $member['m_id']);
                }
                if($action=='paymentPage' && $flg && empty($rule_info['pmn_id']) ){
                    $coupon_ids = $Coupon->autoAdd(empty($promotion_name)? "促销活动赠送优惠券" : $promotion_name, $config['cfg_coupon_money'], 1, $config['cfg_coupon_long'], '促销活动赠送优惠券，系统自动生成', $config['cfg_coupon_prefix'], $config['cfg_coupon_suffix'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s', mktime() + 86400 * $config['cfg_coupon_date']), $config['cfg_condition_money'], $member['m_id']);
                    
                }
                foreach ($coupon_ids as $civ){
                    foreach ($config['cfg_coupon_group'] as $cv){
                        if(false === M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->data(array('c_id'=>$civ,'gg_id'=>$cv))->add()){
                            $status=false;
                            break;
                        }
                    }
                }
                $status=true;
            }else{
                if($action=='paymentPage' && $rule_info['pmn_id']!="" && $rule_info['again_discount']==1){
                    $coupon_ids = $Coupon->autoAdd('促销活动赠送优惠券', $config['cfg_coupon_money'], 1, $config['cfg_coupon_long'], '促销活动赠送优惠券，系统自动生成', $config['cfg_coupon_prefix'], $config['cfg_coupon_suffix'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s', mktime() + 86400 * $config['cfg_coupon_date']), $config['cfg_condition_money'], $member['m_id']);
                }
                if($action=='paymentPage' && empty($rule_info['pmn_id']) ){
                    $coupon_ids = $Coupon->autoAdd('促销活动赠送优惠券', $config['cfg_coupon_money'], 1, $config['cfg_coupon_long'], '促销活动赠送优惠券，系统自动生成', $config['cfg_coupon_prefix'], $config['cfg_coupon_suffix'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s', mktime() + 86400 * $config['cfg_coupon_date']), $config['cfg_condition_money'], $member['m_id']);
                    //新增优惠券成功后，添加优惠券与商品分组之间的关联关系 获取优惠券id
                    foreach ($coupon_ids as $civ){
                        foreach ($config['cfg_coupon_group'] as $cv){
                            if(false === M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->data(array('c_id'=>$civ,'gg_id'=>$cv))->add()){
                                $status=false;
                                break;
                            }
                        }
                    } 
                }
                $promotion_name=$this->promotion['pmn_name'];
                $status=true;
            }
        }
        return array('status'=>$status,'price'=>$flt_price,'code'=>$this->code,'coupon_sn'=>$coupon_ids[0],'name'=>$promotion_name);
    }
}