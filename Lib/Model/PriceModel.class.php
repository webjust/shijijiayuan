<?php

/**
 * 商品价格模型
 * @package Model
 *
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-13
 */
class PriceModel extends GyfxModel {
    ### 私有变量 ##############################################################

    /**
     * 货品价格，数据库里的销售价
     * @var float
     */
    private $pdt_price = 0.000;

    /**
     * 货品的分销商代理价，考虑到分销商等级折扣
     * @var float
     */
    private $member_price = 0.000;

    /**
     * 货品的折扣价，考虑到促销活动的价格
     * @var float
     */
    private $discount_price = 0.000;

    /**
     * 折扣名称(促销规则名称)
     * @var string
     */
    private $price_rule_name;
    
    /**
     * 促销规则信息
     * @var int
     */
    private $rule_info;
    
    /**
     * 货品对象
     * @var obj
     */
    private $product;

    /**
     * 会员对象
     * @var obj
     */
    private $member;

    /**
     * 会员ID
     * @var int
     */
    private $memberId;
	
	/**
	 * 会员等级ID
	 * @var int
	 */
	private $memberLevelId;

    /**
     * 会员折扣
     * @var float
     */
    private $memberDiscount;
	
	private $is_cache;

    ### 公共方法 ##############################################################

    /**
     * 构造方法
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     */
    public function __construct($int_tmp_mid=0,$is_cache=0) {
        parent::__construct();
        $int_mid = $_SESSION['Members']['m_id'];
		if(isset($int_tmp_mid) && $int_tmp_mid !=0){
			$int_mid = $int_tmp_mid;
		}
        $this->member = D('Members');
        $this->product = D('GoodsProducts');
        $this->memberId = $int_mid;
        $this->memberDiscount = $this->member->getMemberDiscount($int_mid,$is_cache);
		if($is_cache == 1){
			$memberLevel = D('Gyfx')->selectOneCache('members','ml_id', array('m_id'=>$int_mid), $ary_order=null,600);
			$this->memberLevelId = $memberLevel['ml_id'];
		}else{
			$this->memberLevelId = $this->member->where(array("m_id"=>$int_mid))->getField("ml_id");
		}
		$this->is_cache = $is_cache;
    }

    /**
     * 根据货品ID和用户ID获取货品的详细价格
     * @author zuo <zuojianghua@guanyisoft.com>
     * @param int $int_pdt_id 货品ID
     * @param float $pdt_sale_price 购物车的价格总和
     * @return float 返回货品应用促销规则后的价格
     */

    public function getItemPrice($int_pdt_id,$pdt_sale_price = 0, $type = 0,$gp_id = 0) {
        $this->setItemPrice($int_pdt_id,$pdt_sale_price, $type,$gp_id);
        if($type == '5'){
            return array('pdt_price'=>sprintf("%0.3f", $this->pdt_price),'discount_price'=>sprintf("%0.3f", $this->discount_price));
        }else if($type == '7'){
            return array('pdt_price'=>sprintf("%0.3f", $this->pdt_price),'discount_price'=>sprintf("%0.3f", $this->discount_price));
        }else if($type == '8'){
            return array('pdt_price'=>sprintf("%0.3f", $this->pdt_price),'discount_price'=>sprintf("%0.3f", $this->discount_price));
        }else if($type == '11'){//积分+金额兑换
            return array('pdt_price'=>sprintf("%0.3f", $this->pdt_price),'discount_price'=>sprintf("%0.3f", $this->discount_price));
        }
        return sprintf("%0.3f", $this->discount_price);
    }
    
    public function getOrderPrice($ary_param) {
        return $Promotion_result=$this->setOrderPrice($ary_param);
    }

    /**
     * 根据货品ID和用户ID获取货品的促销规则名称
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return string 价格规则名
     */
    public function getPriceRuleName() {
        return $this->price_rule_name;
    }
    
    /**
     * 根据货品ID和用户ID获取货品的促销规则名称
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return string 价格规则名
     */
    public function getRuleinfo() {
        return $this->rule_info;
    }

    /**
     * 根据货品ID和用户ID获取货品的会员等级价格
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-22
     * @param int $int_pdt_id 货品ID
     * @param float $flt_cart 购物车的价格总和
     * @return float 返回货品的会员等级价格
     */
    public function getMemberPrice($int_pdt_id, $flt_cart = 0){
        $this->setItemPrice($int_pdt_id, $flt_cart);
        return sprintf("%0.3f", $this->member_price);
    }

    /**
     * 根据购物车中的总商品价格，获取优惠后本订单应该整单减去多少
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $int_pdt_id 货品ID
     * @param float $flt_cart
     */
    public function getOrdersDiscount($flt_cart) {
        $Promotion = D('Promotion');
        return $Promotion->promotionDiscount((int) $this->memberId, $flt_cart);
    }

    /**
     * 根据pdt_id和m_id，计算货品的各个价格
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     * @param int $int_pdt_id 货品ID
     * @param float $pdt_sale_price
     */
    private function setItemPrice($int_pdt_id, $pdt_sale_price = 0,$type = 0,$gp_id = 0) {
    	if($type == '4'){
    		$this->pdt_price= M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_id' => $int_pdt_id))->getField('pdt_collocation_price');
    		$this->member_price = $this->pdt_price;
    		$this->discount_price = $this->member_price; 	
    	}
        else if($type == '5'){
            $groupbuy = M('groupbuy',C('DB_PREFIX'),'DB_CUSTOM');
            $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where = array('is_active'=>1,'gp_id'=>$gp_id);
            $data = $groupbuy->where($array_where)->find();
            //取出价格阶级
            $rel_bulk_price = $gp_price->where(array('gp_id'=>$data['gp_id']))->select();
            $buy_nums = $data['gp_pre_number'] + $data['gp_now_number'];
             foreach ($rel_bulk_price as $rbp_k=>$rbp_v){
                if($buy_nums >= $rbp_v['rgp_num']){
                    $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
                }
            }
            if(!empty($array_f)){
                $array_max = new ArrayMax($array_f);
                $rgp_num = $array_max->arrayMax();
                $this->member_price = $gp_price->where(array('gp_id'=>$data['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
                
            }else{
                $this->member_price = $data['gp_price'];
            }
            $this->discount_price = $this->member_price;
            $ary_product_feild = array('pdt_sale_price','pdt_id');
            $where = array();
            $where['g_id'] = $data['g_id'];
            $where['pdt_status'] = '1';
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
            foreach($ary_pdt as $kypdt=>$valpdt){
                if($valpdt['pdt_id'] == $int_pdt_id){
                    $this->pdt_price = $valpdt['pdt_sale_price'];
                }
            }
        }
        else if($type == '7'){
            $spike = M('spike',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where = array('sp_status'=>1,'sp_id'=>$gp_id);
            $data = $spike->where($array_where)->find();
            $buy_nums = $data['sp_now_number'];
            $this->member_price = $data['sp_price'];
            $this->discount_price = $this->member_price;
            $ary_product_feild = array('pdt_sale_price','pdt_id');
            $where = array();
            $where['g_id'] = $data['g_id'];
            $where['pdt_status'] = '1';
            $where['pdt_id'] = $int_pdt_id;
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
//            echo "<pre>";print_r(M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());exit;
//            echo "<pre>";print_r($ary_pdt);exit;
            foreach($ary_pdt as $kypdt=>$valpdt){
                if($valpdt['pdt_id'] == $int_pdt_id){
                    $this->pdt_price = $valpdt['pdt_sale_price'];
                }
            }
        }
        else if($type == '8'){
            //预售商品
            $presale = M('presale',C('DB_PREFIX'),'DB_CUSTOM');
            $p_price = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where = array('is_active'=>1,'p_id'=>$gp_id);
            $data = $presale->where($array_where)->find();
            //取出价格阶级
            $rel_presale_price = $p_price->where(array('p_id'=>$data['p_id']))->select();
            $buy_nums = $data['p_pre_number'] + $data['p_now_number'];
             foreach ($rel_presale_price as $rbp_k=>$rbp_v){
                if($buy_nums > $rbp_v['rgp_num']){
                    $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
                }
            }
            if(!empty($array_f)){
                $array_max = new ArrayMax($array_f);
                $rgp_num = $array_max->arrayMax();
                $this->member_price = $p_price->where(array('p_id'=>$data['p_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
                
            }else{
                $this->member_price = $data['p_price'];
            }
            $this->discount_price = $this->member_price;
            $ary_product_feild = array('pdt_sale_price','pdt_id');
            $where = array();
            $where['g_id'] = $data['g_id'];
            $where['pdt_status'] = '1';
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
            foreach($ary_pdt as $kypdt=>$valpdt){
                if($valpdt['pdt_id'] == $int_pdt_id){
                    $this->pdt_price = $valpdt['pdt_sale_price'];
                }
            }
        }
        else if($type=='11'){
            $integral = M('integral',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where = array('integral_status'=>1,'integral_id'=>$gp_id);
            $data = $integral->where($array_where)->find();
            $buy_nums = $data['integral_now_number'];
            $this->member_price = $data['money_need_to_pay'];
            $this->discount_price = $this->member_price;
            $ary_product_feild = array('pdt_sale_price','pdt_id');
            $where = array();
            $where['g_id'] = $data['g_id'];
            $where['pdt_status'] = '1';
            $where['pdt_id'] = $int_pdt_id;
            $ary_pdt = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->field($ary_product_feild)->where($where)->limit()->select();
//            echo "<pre>";print_r(M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());exit;
//            echo "<pre>";print_r($ary_pdt);exit;
            foreach($ary_pdt as $kypdt=>$valpdt){
                if($valpdt['pdt_id'] == $int_pdt_id){
                    $this->pdt_price = $valpdt['pdt_sale_price'];
                }
            }
        }
        else{
			if($this->is_cache == 1){
				$price_info = D('Gyfx')->selectOneCache('goods_products',array('pdt_sale_price','g_id'), array('pdt_id'=>$int_pdt_id), $ary_order=null,600);
				$this->pdt_price = $price_info['pdt_sale_price'];				
			}else{
				$price_info = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('pdt_id' => $int_pdt_id))->Field('pdt_sale_price,g_id')->find();
				$this->pdt_price = $price_info['pdt_sale_price'];	
			}
	        //modify by Mithern 如果商品上设置了会员等级固定价，则member_price = 会员等级的固定价格
			$int_ml_id = $this->memberLevelId;
			//验证是否有会员等级固定价格
			if(isset($int_ml_id )){
				$array_fetch_fixed_cond = array("ml_id"=>$int_ml_id,"pdt_id"=>$int_pdt_id,"pmlp_status"=>1);
				if($this->is_cache == 1){
					$sub_price_info = D('Gyfx')->selectOneCache('product_member_level_price','pmlp_price', $array_fetch_fixed_cond, $ary_order=null,600);	
					$mixed_fiexd_ml_price = $sub_price_info['pmlp_price'];
				}else{
					$mixed_fiexd_ml_price = D("ProductMemberLevelPrice")->where($array_fetch_fixed_cond)->getField("pmlp_price");						
				}
			}
                        
			if((NULL !== $mixed_fiexd_ml_price) && ($mixed_fiexd_ml_price>0)){
				$this->member_price = $mixed_fiexd_ml_price;
			}else{
//                                echo "<pre>";print_R($this->memberDiscount);exit;
				if($this->memberDiscount <= 0){
					//如果等级折扣等于0 保持原价。
					$this->member_price = $this->pdt_price;
				}else{
					$this->member_price = $this->pdt_price * $this->memberDiscount;
				}
			}
            //判断是否设商品折扣
			if($this->is_cache == 1){
				$good_info = D('Gyfx')->selectOneCache('goods_info',array('g_discount','g_id'), array('g_id'=>$price_info['g_id']), $ary_order=null,600);	
				$g_discount = $good_info['g_discount'];
			}else{
				$g_discount = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $price_info['g_id']))->getField('g_discount');				
			}
			if(!empty($g_discount)){
				$this->pdt_price = $this->pdt_price * $g_discount;
				$this->member_price = $this->member_price * $g_discount;
			}
			
	        $Promotion = D('Promotion');
	        $Promotion_result = $Promotion->SetItemDiscount((int) $this->memberId, (int) $int_pdt_id, $this->member_price,0,$this->is_cache);
	        
	        $this->discount_price = $Promotion_result['price'];

	        $this->price_rule_name = isset($Promotion_result['rule_info']['name']) ? $Promotion_result['rule_info']['name'] : '';
	        $this->rule_info = $Promotion_result['rule_info'];    		
    	}
    }

    
    private function setOrderPrice($ary_param) {
        $Promotion = D('Promotion');
        $Promotion_result = $Promotion->SetOrderDiscount($ary_param);
        return $Promotion_result;
    
    }
    /**
     * 根据购物车中的总商品价格，是否包邮
     * @author listen
     * @date 2013-02-11
     * @param $frt_cart 商品价格
     */
    public function getPromotionLogistic($flt_cart){
        $Promotion = D('Promotion');
        $int_m_id = (int) $this->memberId;
        return $Promotion->promotionLogistic($int_m_id, $flt_cart);
    }
    
    /**
     * 获取货品的市场价格最大值
     * @author jiye
     * @data 2013-04-17
     * @param gid,pdt_id
     * @param return array()
     */
    public function getMarketPrice($gid,$pdt_id='',$is_cache=0) {
    	$where = array();
    	if($gid){
    		$where['g_id'] = $gid;
    	}
    	if($pdt_id){
    		$where['pdt_id'] = $pdt_id;
    	}
		if($is_cache == 1){
			$ary_good_info = D('Gyfx')->selectOneCache('goods_products','max(pdt_market_price) as pdt_market_price', $where, $ary_order=null,600);
			return $ary_good_info['pdt_market_price'];
		}else{
			return M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->where($where)->max('pdt_market_price');	
		}
    }
}
