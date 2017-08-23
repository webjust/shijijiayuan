<?php

/**
 * @file ProPrice.class.php
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-09-09
 * @version 7.4
 * @copyright Copyright (C) 2013, Shanghai guanyusoft Co., Ltd.
 */
class ProPrice {

    //用户ID
    public $user_id = '';
    //用户等级ID
    public $level_id = '';
    //用户等级折扣
    public $level_discount = '';

    /**
     * @package 获取商品价格
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-09-09
     */
    public function __construct() {
        $this->user_id = $_SESSION['Members']['m_id'];
        $this->member = M("Members", C('DB_PREFIX'), 'DB_CUSTOM');
        //获取用户组ID及组的折扣率
		/**
        if ($this->user_id != null) {
            $where = array();
            $where[C('DB_PREFIX') . "members.m_id"] = $this->user_id;
            $field = array(
                C('DB_PREFIX') . "members.m_id",
                C('DB_PREFIX') . "members.m_name",
                C('DB_PREFIX') . "members_level.ml_id",
                C('DB_PREFIX') . "members_level.ml_discount"
            );
            $groupRow = $this->member->field($field)->join(" " . C('DB_PREFIX') . "members_level ON " . C('DB_PREFIX') . "members.ml_id=" . C('DB_PREFIX') . "members_level.ml_id")->where($where)->find();
            if ($groupRow) {
                $this->level_id = $groupRow['ml_id'];
                if ($groupRow['ml_discount'] == 0) {
                    $groupRow['ml_discount'] = 100;
                }
                $this->level_discount = $groupRow['ml_discount'] * 0.01;
            }
        }
		**/
    }

    /**
     * @package 获取最终价格
     * @param int $pdt_id 货品ID
     * @param int $int_mid 会员ID
     * @param int $type 商品类型：1.积分商品 2.赠品 3.组合商品 4.自由推荐 5.团购
     * @param array $ary_extra <p>额外的参数信息</p>
     * @author Terry<wanghui@guanyisoft.com>
     * @modify zhanghao
     * @date 2013-9-12
     * @return Array $price 
     */
    public function getPriceInfo($pdt_id, $int_mid = '0', $type = '0', $ary_extra=array(),$is_cache=0) {
        $ary_result = array();
        //获取商品销售价
		if($is_cache == 1){
			$ary_pdt_price = D('Gyfx')->selectOneCache('goods_products',array('pdt_sale_price','g_id'), array('pdt_id'=>$pdt_id), $ary_order=null,60);
		}else{
			$ary_pdt_price = D('GoodsProducts')->where(array('pdt_id'=>$pdt_id))->field(array('pdt_sale_price','g_id'))->find();
		}
        if($type == 0) {
            //获取会员信息
			if($is_cache == 1){
				$ary_mem_info = D('Gyfx')->selectOneCache('members',array('ml_id'), array('m_id'=>$int_mid), $ary_order=null,100);
			}else{
				$ary_mem_info = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$int_mid))->field(array('ml_id'))->find();
			}
            //等级信息
			if($is_cache == 1){
				$ary_level_info = D('Gyfx')->selectOneCache('members_level',array('ml_status','ml_discount'), array('ml_id'=>$ary_mem_info['ml_id']), $ary_order=null,3600);			
			}else{
				$ary_level_info = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_id'=>$ary_mem_info['ml_id']))->field(array('ml_status','ml_discount'))->find();
			}
            if($ary_level_info['ml_status'] != 1) {
                $ary_mem_info['ml_id'] = 0;
            }
            //分组信息
            $ary_group_id_info = D('RelatedMembersGroup')->getMemGroupsByMid($int_mid,$is_cache);
            $ary_group = array();
            if(is_array($ary_group_id_info) && !empty($ary_group_id_info)) {
                foreach ($ary_group_id_info as $ary_gp) {
                    $ary_group[] = $ary_gp['mg_id']; 
                }
            }
            //获取该会员所拥有的促销
            $ary_mem_pro = D('RelatedPromotionMembers')->getProByMemInfo($int_mid, $ary_mem_info['ml_id'], $ary_group, false, array('pmn_id'),$is_cache);
            $ary_mem_pro_id = array();
//            echo "<Pre>";print_r($ary_mem_pro);die;
            //如果该会员有促销则再次根据商品判断
            $ary_new_pro = array();
            if(is_array($ary_mem_pro) && !empty($ary_mem_pro)) {
                //后台指定的促销商品
				if($is_cache == 1){
					$ary_pdt_pro = D('Gyfx')->selectAllCache('related_promotion_goods',$ary_field=null, array('pdt_id'=>$pdt_id), $ary_order=null,$ary_group=null,$ary_limit=null,100);
				}else{
					$ary_pdt_pro = D('RelatedPromotionGoods')->where(array('pdt_id'=>$pdt_id))->select();
				}
                $ayy_pdt_pro_id = array();
                if(is_array($ary_pdt_pro) && !empty($ary_pdt_pro)) {
                    foreach($ary_pdt_pro as $ary_pp) {
                        $ayy_pdt_pro_id[$ary_pp['pmn_id']] = $ary_pp;
                    }
                    
                    foreach ($ary_mem_pro as $ary_mp) {
                        $ary_mem_pro_id[$ary_mp['pmn_id']] = $ary_mp['pmn_id']; 
                    }
                    $ary_pro = array_intersect_key($ary_mem_pro_id, $ayy_pdt_pro_id);
                    $str_now = date('Y-m-d H:i:00');
					if($is_cache == 1){
						$ary_new_pro = D('Gyfx')->selectOneCache('promotion',$ary_field=null, array('pmn_id'=>array('in',$ary_pro,'pmn_class'=>'PYIKOUJIA'),'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)), 'pmn_order desc',100);
					}else{
						$ary_new_pro = D('Promotion')->where(array('pmn_id'=>array('in',$ary_pro,'pmn_class'=>'PYIKOUJIA'),'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)))->order('pmn_order desc')->find();						
					}
                }
            }
            //判断一口价
            if(!empty($ary_new_pro)) {
                $ary_config = json_decode($ayy_pdt_pro_id[$ary_new_pro['pmn_id']]['g_price_config'], true);
                //一口价大于零时才返回一口价
                $bl_cfg_discount = sprintf("%0.3f", $ary_config['cfg_products']);
                if($bl_cfg_discount > 0) {
                    $ary_result['pdt_price'] = $bl_cfg_discount;
                    $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
                    $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $bl_cfg_discount;
                    $ary_result['code'] = 'getPriceInfo_001';
                    //添加一口价id和一口价名称
                    $ary_result['pmn_id'] = $ary_new_pro['pmn_id'];
                    $ary_result['pmn_name'] = $ary_new_pro['pmn_name'];
                    return $ary_result;
                }
            }
			//判断是否设商品折扣
			if($is_cache == 1){
				$good_info = D('Gyfx')->selectOneCache('goods_info',array('g_discount','g_id'), array('g_id'=>$ary_pdt_price['g_id']), $ary_order=null,600);	
				$g_discount = $good_info['g_discount'];
			}else{
				$g_discount = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $ary_pdt_price['g_id']))->getField('g_discount');				
			}			
            //判断会员等级固定价
            if($ary_mem_info['ml_id'] > 0) {
				if($is_cache == 1){
					$ary_pmlp_price = D('Gyfx')->selectOneCache('product_member_level_price','pmlp_price', array('ml_id'=>$ary_mem_info['ml_id'],'pdt_id'=>$pdt_id, 'pmlp_status'=>1), null,600);
				}else{
					$ary_pmlp_price = D('ProductMemberLevelPrice')->where(array('ml_id'=>$ary_mem_info['ml_id'],'pdt_id'=>$pdt_id, 'pmlp_status'=>1))->field('pmlp_price')->find();					
				}				
                $bl_pmlp_price = sprintf("%0.3f", $ary_pmlp_price['pmlp_price']);
                if($bl_pmlp_price > 0) {
					if(!empty($g_discount)){
						$bl_pmlp_price = sprintf("%0.3f", $ary_pmlp_price['pmlp_price']* $g_discount);
					}
                    $ary_result['pdt_price'] = $bl_pmlp_price;
                    $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
                    $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $bl_pmlp_price;
                    $ary_result['code'] = 'getPriceInfo_002';
                    return $ary_result;
                }
            }
            //dump($ary_level_info);die;
            //计算等级折扣价
            if(isset($ary_level_info['ml_discount']) && $ary_level_info['ml_discount'] > 0) {
				if(!empty($g_discount)){
					$ary_result['pdt_price'] = sprintf("%0.3f", ($ary_pdt_price['pdt_sale_price']*($ary_level_info['ml_discount']*0.01)*$g_discount));
				}else{
					$ary_result['pdt_price'] = sprintf("%0.3f", ($ary_pdt_price['pdt_sale_price']*($ary_level_info['ml_discount']*0.01)));					
				}	
                $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
                $ary_result['ml_discount'] = $ary_level_info['ml_discount'];
                $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $ary_result['pdt_price'];
                $ary_result['code'] = 'getPriceInfo_003';
                return $ary_result;
            }
            //以上方法没有命中则返回销售价
			if(!empty($g_discount)){
				$ary_result['pdt_price'] = sprintf("%0.3f",$ary_pdt_price['pdt_sale_price']*$g_discount);
			}else{
				$ary_result['pdt_price'] = $ary_pdt_price['pdt_sale_price'];
			}
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price']-$ary_result['pdt_price'];
            $ary_result['code'] = 'getPriceInfo_004';
            return $ary_result;
        }
        else if($type == 1) {
            //积分商品
            //获取商品的积分
			if($is_cache == 1){
				$ary_point = D('Gyfx')->selectOneCache('goods_info','point', array('g_id'=>$ary_pdt_price['g_id']), null,3600);
			}else{
				$ary_point = D('GoodsInfo')->where(array('g_id'=>$ary_pdt_price['g_id']))->field('point')->find();
			}			
            $ary_result['pdt_price'] = 0;
            $ary_result['pdt_point'] = $ary_point['point'];
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['code'] = 'getPriceInfo_005';
            return $ary_result;
        }
        else if($type == 2) {
            //赠品
            $ary_result['pdt_price'] = 0;
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['code'] = 'getPriceInfo_006';
            return $ary_result;
        }
        else if($type == 3) {
            //组合商品
            $ary_com = D('ReletedCombinationGoods')->getCombinationGoodsPrice($ary_pdt_price['g_id']);
            $ary_result['pdt_price'] = $ary_com['all_price'];
            $ary_result['pdt_sale_price'] = $ary_com['all_price'] + $ary_com['coupon_price'];
            $ary_result['thrift_price'] = $ary_com['coupon_price'];
            $ary_result['code'] = 'getPriceInfo_007';
            return $ary_result;
        }
        else if($type == 4) {
            //自由推荐商品
			if($is_cache == 1){
				$ary_price = D('Gyfx')->selectOneCache('goods_products','pdt_collocation_price', array('pdt_id'=>$pdt_id), null,3600);			
			}else{
				$ary_price = M('goods_products', C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$pdt_id))->field('pdt_collocation_price')->find();				
			}
            $bl_free_price = sprintf("%0.3f", $ary_price['pdt_collocation_price']);
            //如果自由推荐价格小于0,则直接返回销售价
            $ary_result['pdt_price'] = $bl_free_price > 0 ? $bl_free_price : $ary_pdt_price['pdt_sale_price'];
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $ary_result['pdt_price'];
            $ary_result['code'] = 'getPriceInfo_008';
            return $ary_result;
        }
        else if($type == 5) {
            //团购商品的信息放在$ary_extra中
            //$ary_extra
            if(isset($ary_extra['gp_id']) && $ary_extra['gp_id'] > 0) {
                $bl_rgp_price = 0;
                $bl_sign = false;
				if($is_cache == 1){
					$ary_gb = D('Gyfx')->selectOneCache('groupbuy',array('gp_deposit_price','gp_per_number','gp_now_number','gp_price'), array('gp_id'=>$ary_extra['gp_id']), null,3600);					
				}else{
					$ary_gb = D('Groupbuy')->where(array('gp_id'=>$ary_extra['gp_id']))->field(array('gp_deposit_price','gp_per_number','gp_now_number','gp_price'))->find();					
				}
                //计算当前阶梯
                $ary_ladder_price = D('RelatedGroupbuyPrice')->getLadderInfoById($ary_extra['gp_id'],$is_cache);
                //当前价格判断
                if(is_array($ary_ladder_price) && !empty($ary_ladder_price)) {
                    $int_now_num = $ary_gb['gp_pre_number'] + $ary_gb['gp_now_number'];
                    if($int_now_num > 0) {
                        foreach($ary_ladder_price as $ary_lp) {
                            //达到阶梯数量
                            if($int_now_num >= $ary_lp['rgp_num']) {
                                $bl_rgp_price = sprintf("%0.3f", $ary_lp['rgp_price']);
                                $bl_sign = true;
                                break;
                            }
                        }
                    }
                }
                if(!$bl_sign) {
                    //没有达到最低阶梯返回初始价格
                    $bl_rgp_price = sprintf("%0.3f", $ary_gb['gp_price']);
                }
                $ary_result['pdt_price'] = $bl_rgp_price;
                $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
                $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $bl_rgp_price;
                $ary_result['gp_deposit_price'] = sprintf("%0.3f", $ary_gb['gp_deposit_price']);
                $ary_result['code'] = 'getPriceInfo_009';
            } else {
                //如果没有接收到团购的id则按照原价返回
                $ary_result['pdt_price'] = $ary_pdt_price['pdt_sale_price'];
                $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
                $ary_result['thrift_price'] = 0;
                $ary_result['code'] = 'getPriceInfo_010';
            }
            return $ary_result;
        }
        else if($type == 6) {
            $fr_price = M('free_recommend', C('DB_PREFIX'),'DB_CUSTOM')->where(array('fr_goods_id'=>$ary_pdt_price['g_id']))->getField('fr_price');
            $bl_free_price = sprintf("%0.3f", $fr_price);
            //如果自由推荐价格小于0,则直接返回销售价
            $ary_result['pdt_price'] = $bl_free_price > 0 ? $bl_free_price : $ary_pdt_price['pdt_sale_price'];
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = $ary_pdt_price['pdt_sale_price'] - $ary_result['pdt_price'];
            $ary_result['code'] = 'getPriceInfo_0012';
            return $ary_result;
        }
        else {
            //位置类型商品
            $ary_result['pdt_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['pdt_sale_price'] = $ary_pdt_price['pdt_sale_price'];
            $ary_result['thrift_price'] = 0;
            $ary_result['code'] = 'getPriceInfo_011';
            return $ary_result;
        }
    }
}
