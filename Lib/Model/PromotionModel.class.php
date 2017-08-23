<?php

/**
 * 促销规则模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-07
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PromotionModel extends GyfxModel {

    /**
     * 促销的种类
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @var array
     * code:促销种类代码
     * memo:促销种类说明
     * type:1为与购物车相关 0为与购物车无关，商品直接优惠
     * status:1为启用 0为不启用或者待开发
     */
    public static $types = array(
        'MZHEKOU' => array('code' => 'MZHEKOU', 'type' => 1, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，用户就可得到指定折扣'),
        'MJIAN' => array('code' => 'MJIAN', 'type' => 1, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，就可立减某金额'),
        'MZENPIN' => array('code' => 'MZENPIN', 'type' => 0, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，赠送某个赠品'),
        'MBAOYOU' => array('code' => 'MBAOYOU', 'type' => 1, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，免运费'),
        'MQUAN' => array('code' => 'MQUAN', 'type' => 1, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，用户可获得优惠劵'),
        'PYIKOUJIA' => array('code' => 'PYIKOUJIA', 'type' => 0, 'status' => 1, 'memo' => '商品一口价，可以对商品直接设置价格'),
        //'MJLB' => array('code' => 'MJLB', 'type' => 1, 'status' => 1, 'memo' => '购物车中商品总金额大于指定金额，用户可获得金币'),
    );

    /**
     * 获取促销规则的优先级
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-07
     * @param int $num 一共可以设置多少种优先级
     * @return array 返回全部优先级，以及是否被用掉
     */
    public function getOrders($num = 50) {
        $count = $this->field(array('pmn_order'))->group('pmn_order')->count();
        //如果设置的最大数比已用掉的还少，则修改为最大数加20
        if ($num < $count) {
            $num = $count + 20;
        }
        //生成优先级，已被使用的优先级标记出来
        $ary_used = $this->field(array('pmn_order', 'pmn_activity_name', 'pmn_name', 'pmn_id'))->group('pmn_order')->select();
        $return = array();
        for ($i = 1; $i <= $num; $i++) {
            $return[$i] = array('num' => $i, 'pmn_activity_name' => '', 'pmn_name' => '', 'pmn_id' => 0);
            foreach ($ary_used as $v) {
                if ($i == $v['pmn_order']) {
                    $return[$i]['pmn_activity_name'] = $v['pmn_activity_name'];
                    $return[$i]['pmn_name'] = $v['pmn_name'];
                    $return[$i]['pmn_id'] = $v['pmn_id'];
                }
            }
        }
        return $return;
    }

    /**
     * 返回促销的种类
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @return array
     */
    public function getTypes() {
        return self::$types;
    }

    /**
     * 静态工厂方法，根据CODE获取相应的促销模型对象
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     * @param string $str_code
     * @param array $array_params
     * @return object 返回实例化后的促销对象
     */
    public static function factory($str_code, $array_params = array()) {
        $return = false;
        foreach (self::$types as $tp) {
            if ($tp['code'] == $str_code) {
                $return = new $str_code($tp, $array_params);
            }
        }
        
        if ($return instanceof PromotionsOrder) {
            return $return;
        }
        elseif ($return instanceof PromotionsItem) {
            return $return;
        } 
        return false;
    }
    /**
     * 判断是否可以应用促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $int_mid 会员ID
     * @param float $flt_cart 当前购物车价格总和
     * @param int $int_pid 货品id
     * @param float $flt_price 货品价格
     * @param int $int_pnm_id 如果已经应用了促销规则，此处传入促销规则ID
     * @return array array('result' => false/true, 'data' => mix, 'message' => '促销规则应用的提示');
     * @param array $ary_param();  
     */
    public function promotion($ary_param=array(),$is_cache=0) {
        if(!empty($ary_param)){
            $Member = D('Members');
            $int_mid=$ary_param['mid'];
            //取得会员ID，会员组ID和会员等级ID ++++++++++++++++++++++++++++++++++++++++
			if($is_cache == 1){
				$ml_info = D('Gyfx')->selectOneCache('members','ml_id', array('m_id'=>$ary_param['mid']), $ary_order=null,600);
				$int_ml_id = $ml_info['ml_id'];			
				$res_mg_id = D('Gyfx')->selectAllCache('related_members_group','mg_id', array('m_id'=>$int_mid), $ary_order=null,$ary_group=null,$ary_limit=null,600);				
			}else{
				$int_ml_id = $Member->where(array('m_id' =>$ary_param['mid']))->getField('ml_id');		
				$res_mg_id = D('RelatedMembersGroup')->field('mg_id')->where(array('m_id' => $int_mid))->select();
			}								
            $ary_mg_id = array();
            if(!empty($res_mg_id) && is_array($res_mg_id)){
                foreach ($res_mg_id as $v) {
                    $ary_mg_id[] = $v['mg_id'];
                }
            }
            $str_mg_id = implode(',', $ary_mg_id);
            if ($str_mg_id == '') {
                $str_mg_id = -1;
            }
            //判断是否有符合此用户的促销规则,时间/会员关系 ++++++++++++++++++++++++++++++
            //1) 符合会员关系的
			if($is_cache == 1){
				$obj_query = D('RelatedPromotionMembers')->where("`m_id` = $int_mid or `m_id` = '-1' or `mg_id` in ($str_mg_id) or `ml_id` = $int_ml_id")
                          ->group('pmn_id');	
				$res_pmn_id = D('Gyfx')->queryCache($obj_query,'',10);
			}else{
				$res_pmn_id = D('RelatedPromotionMembers')->where("`m_id` = $int_mid or `m_id` = '-1' or `mg_id` in ($str_mg_id) or `ml_id` = $int_ml_id")
                          ->group('pmn_id')->select();							  
			}						  
            $ary_pmn_id = array();
            foreach ($res_pmn_id as $v) {
                $ary_pmn_id[] = $v['pmn_id'];
            }
            $str_pmn_id = implode(',', $ary_pmn_id);
            if ($str_pmn_id != '') {
                $where_pmn_id = " `pmn_id` in ($str_pmn_id) ";
            } else {
                //$where_pmn_id = " 1 ";
                //没有符合用户条件关系的促销
                $where_pmn_id = " `pmn_id` =null ";
            }
            //2) 符合时间关系的
            $now = date('Y-m-d H:i:00');
            $where_time = " (`pmn_start_time`<='$now' and `pmn_end_time`>='$now') ";
            $where = " `pmn_enable` = 1 and $where_pmn_id  and ( $where_time) ";
			if($is_cache == 1){
				$promotions = D('Gyfx')->selectAllCache('promotion',$ary_field=null, $where, array('pmn_order' => 'desc'),$ary_group=null,$ary_limit=null,60);
			}else{
				$promotions = $this->where($where)->order(array('pmn_order' => 'desc'))->select();
			}
            //3） 查找到符合关系的全部促销规则，进一步查找详细规则，符合促销规则返回应用后的结果，不符合返回false
            foreach ($promotions as $key=>$value) {
                //加个促销规则code 判断不同的促销规则调用
                $pmn_config = json_decode($value['pmn_config'], true);
				$pmn_config['coupon_group'] = isset($pmn_config['ggp_name']) ? $pmn_config['ggp_name'] : null;
                if(isset($pmn_config['ggp_name'])){
					if($is_cache == 1){
						$rgp = D('Gyfx')->selectAllCache('related_promotion_goods_group','gg_id', array('pmn_id'=>$value['pmn_id']), null,$ary_group=null,$ary_limit=null,600);
					}else{
						$rgp = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$value['pmn_id']))->field('gg_id')->select();
					}	
                    foreach ($rgp as $k){
                        $ggp_name[] = $k['gg_id'];
                    }
                    $pmn_config['ggp_name'] = $ggp_name;					
					//促销关联商品分类
					$gc_id = array();
					if($is_cache == 1){
						$rgc = D('Gyfx')->selectAllCache('related_promotion_goods_category','gc_id', array('pmn_id'=>$value['pmn_id']), null,$ary_group=null,$ary_limit=null,600);
					}else{
						$rgc = M('related_promotion_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$value['pmn_id']))->field('gc_id')->select();
					}	
                    foreach ($rgc as $kc){
                        $gc_id[] = $kc['gc_id'];
                    }
                    $pmn_config['gc_name'] = $gc_id;						
					//促销关联商品品牌
					$gb_id = array();
					if($is_cache == 1){
						$rgb = D('Gyfx')->selectAllCache('related_promotion_goods_brand','brand_id', array('pmn_id'=>$value['pmn_id']), null,$ary_group=null,$ary_limit=null,600);
					}else{
						$rgb = M('related_promotion_goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$value['pmn_id']))->field('brand_id')->select();
					}	
                    foreach ($rgb as $kb){
                        $gb_id[] = $kb['brand_id'];
                    }
                    $pmn_config['gb_name'] = $gb_id;						

                }
                $PromotionRule = self::factory($value['pmn_class'], $pmn_config);
                $promotion_result[] = $PromotionRule->setpromotion($value);
            }
            return $promotion_result;
        }
        return array('result' => false, 'data' => NULL, 'message' => '没有任何促销规则可以使用');
    }
    ### 促销规则的应用 ###########################################################

    /**
     * 获取商品的促销价，此方法应用与商品列表，购物车之内的价格显示
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $int_mid 会员ID
     * @param int $int_pdt_id 商品ID
     * @param float $flt_price 商品的会员等级价
     * @param float $flt_cart 购物车总价格
     * @return float 返回应用了促销规则以后的价格，如果无促销规则应用，则返回原价
     */
    public function SetItemDiscount($int_mid, $int_pdt_id, $flt_price, $flt_cart = 0,$is_cache=0) {
        $ary_param = array(
            'mid'=>$int_mid,
        );
        $promotion_result = $this->promotion($ary_param,$is_cache);
        foreach($promotion_result as $obj){
            $obj_type=$obj->getType();
            if($obj_type=='Item'){
                $res=$obj->result_promotion($int_pdt_id);
                if($res['status']){
                    $flt_price=$res['price'];
                    $pmn_id=$res['pmn_id'];
                    $name=$res['name'];
                    $again_discount=$res['again_discount'];
                    break;
                }
            }
        }
        $result=array('price'=>$flt_price,'rule_info'=>array(
            'pmn_id'=>isset($pmn_id) ? $pmn_id : '',
            'name'=>isset($name) ? $name : '',
            'again_discount'=>isset($again_discount) ? $again_discount : ''
            ));
        return $result;
    }
    
    public function SetOrderDiscount($ary_param) {
        $ary_pdt=$ary_param['ary_pdt'];
        if(isset($ary_pdt['rule_info'])){
            $rule_info = $ary_pdt['rule_info'];  
            unset( $ary_pdt['rule_info']);
        }
        
        $promotion_result = $this->promotion($ary_param);
        foreach($promotion_result as $obj){
            $obj_type=$obj->getType();
            $config=$obj->getCfg();
            $result=array();
            if($obj_type=='Order'
                && ($ary_param['goods_all_price'] >= $config['cfg_cart_start'])
                && ($ary_param['goods_all_price'] <= $config['cfg_cart_end'])){
				$res=$obj->result_promotion($ary_pdt,$rule_info,$ary_param['action'],$ary_param['mid']);
                if($res['status']){
                    $result['code'] = $res['code'];
                    $result['name'] = $res['name'];
                    if($ary_param['action']=='cart' && ($obj->code=='MBAOYOU' || $obj->code=='MZENPIN' || $obj->code=='MQUAN')){
                        $result['price'] = sprintf("%0.2f",$ary_param['all_price']);
                        $result['all_price'] = sprintf("%0.2f",$ary_param['all_price']);
                        $result['gifts_pdt'] = $res['gifts_pdt'];
                    }else{
                        $result['price'] = sprintf("%0.2f",$res['price']);
                        $result['all_price'] = sprintf("%0.2f",$ary_param['all_price']);
                        if($ary_param['action']=='paymentPage'){
                            $result['coupon_sn'] = $res['coupon_sn'];
                        }
                    }
                    break;
                }
            }
            if($obj_type=='Order'
                && ($ary_param['goods_all_price'] < $config['cfg_cart_start']
                    || $ary_param['goods_all_price'] > $config['cfg_cart_end']) ){
                $result['price'] = sprintf("%0.2f",$ary_param['all_price']);
                $result['all_price'] = sprintf("%0.2f",$ary_param['all_price']);
                //break;
            }
        }
        return $result;
    }

    /**
     * 根据购物车总价格，取得整单应该免掉的费用
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $int_mid 会员ID
     * @param float $flt_cart 购物车总价
     * @return float 返回应该免掉的金额
     */
    public function promotionDiscount($int_mid, $flt_cart) {
        $ary_param = array(
            'type'=>2,//商品1，订单2，邮费3
            'mid'=>$int_mid,
            'flt_cart'=>$flt_cart
        );
        $result = $this->promotion($ary_param);
        if ($result['result']) {
            return $result['data'];
        }
    }

    /**
     * 根据购物车总价格，判断是否包邮
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-13
     * @param int $int_mid 会员ID
     * @param float $flt_cart 购物车总价
     * @return boolean 返回是否包邮，true为包邮，false为不包邮
     */
    public function promotionLogistic($int_mid, $flt_cart) {
        $ary_param = array(
            'type'=>3,//商品1，订单2，邮费3
            'mid'=>$int_mid,
            'flt_cart'=>$flt_cart
        );
        $result = $this->promotion($ary_param);
        if ($result['result']) {
            return $result['data'];
        } 
    }
    
    /**
     * 计算购物车促销
     * @author zhanghao
     * @param int $int_mid <p>会员ID</p>
     * @param array $ary_pdts <p>需要计算购物车促销的商品信息</p>
     * @param int $is_cache default=0
     * @date 2013/9/9
     * @return array 返回值中下标为0的代表没有参与促销的商品
     */
    public function calShopCartPro($int_mid, $ary_pdts,$is_cache=0) {
        $ary_result = array(
            'success' => 0,
            'code' => 0,
            'msg' => '',
            'data' => array()
        );
        if($int_mid <= 0 || !is_array($ary_pdts) || empty($ary_pdts)) {
            $ary_result['code'] = 'pro_calShopCartPro_001';
            $ary_result['msg'] = '参数有误！';
            return $ary_result;
        }
		
		foreach ($ary_pdts as $c_key=>$val) {
			if ($val['type'] == '0' && empty($val['g_id']) ) {
				if($is_cache == 1){
					$ary_gid = D('Gyfx')->selectOneCache('goods_products','g_id', array('pdt_id'=>$val['pdt_id']), $ary_order=null,$time=null);
				}else{
					$ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array('pdt_id' => $val['pdt_id']))->find();
				}
				$ary_pdts[$c_key]['g_id'] = $ary_gid['g_id'];
			}
		}
        //处理会员信息，此处只要会员存在即可，不在显示会员状态，避免下单之后会员状态被改变导致后台订单编辑无法使用促销的问题出现
		if($is_cache == 1){
			$ary_mem_info = D('Gyfx')->selectOneCache('members','ml_id', array('m_id'=>$int_mid), $ary_order=null,100);			
		}else{
			$ary_mem_info = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$int_mid))->field(array('ml_id'))->find();
		}
        if(!is_array($ary_mem_info) || empty($ary_mem_info)) {
            $ary_result['code'] = 'pro_calShopCartPro_002';
            $ary_result['msg'] = '会员不存在！';
            return $ary_result;
        }
        //判断会员等级
		if($is_cache == 1){
			$ary_level_info = D('Gyfx')->selectOneCache('members_level','ml_status', array('ml_id'=>$ary_mem_info['ml_id']), $ary_order=null,3600);			
		}else{
		    $ary_level_info = M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_id'=>$ary_mem_info['ml_id']))->field(array('ml_status'))->find();
		}
        if($ary_level_info['ml_status'] != 1) {
            $ary_mem_info['ml_id'] = 0;
        } 
        //判断会员分组信息
        $ary_group_id_info = D('RelatedMembersGroup')->getMemGroupsByMid($int_mid,$is_cache);
        $obj_mem_group = D('MembersGroup');
        $ary_mem_group = array();
        if(is_array($ary_group_id_info) && !empty($ary_group_id_info)) {
            foreach ($ary_group_id_info as $ary_mg) {
                $ary_mg_info = $obj_mem_group->getMemGroupInfoById($ary_mg['mg_id'], array('mg_status'),$is_cache);
                if($ary_mg_info['mg_status'] == 1) {
                    $ary_mem_group[] = $ary_mg['mg_id'];
                }
            }
        }
        unset($ary_group_id_info);
        unset($ary_mg);
        $ary_pdts_pro_related = array(
            'subtotal'=>array(
                'goods_total_price' => 0,
                'goods_total_sale_price' => 0,
                'goods_all_discount' => 0
            )
        );
        $this->recPro($ary_pdts, $int_mid,$ary_mem_info['ml_id'], $ary_mem_group, $ary_pdts_pro_related,$is_cache);
		$ary_pdts_pro_related['subtotal']['m_id'] = $int_mid;
		//dump($ary_pdts_pro_related);die();
        return $ary_pdts_pro_related;
        /*
                           返回值说明，下标为0的代表未参与促销的商品，反之为参与促销的，goods_total_price商品最终价格总和，goods_total_sale_price销售价总和，goods_all_discount商品价格优惠
         */
    }
    
    /**
     * 递归计算促销
     * @author zhanghao
     * @param array $ary_pdts <p>货品信息</p>
     * @param intger $int_mid <p>会员ID<p>
     * @param intger $int_ml_id <p>会员等级</p>
     * @param array $ary_mem_group <p>会员分组</p>
     * @param array $ary_pdts_pro_related<p>地址信用，结果集</p>
     * @date 2013-9-9
     *
     * @return array
     */
    private function recPro($ary_pdts, $int_mid, $int_ml_id, $ary_mem_group, &$ary_pdts_pro_related,$is_cache=0) {        
        $ary_result = array(
            'success' => 0,
            'code' => 0,
            'msg' => '',
            'data' => array()
        );
        $proObj = new ProPrice();	//实例化价格对象,用来计算商品折扣后的价格
        //处理商品信息
        $ary_new_pdts = array();    //存放处理之后的商品信息，数组的键值为货品ID
        $ary_g_ids = array();        //存放商品id
        $ary_no_pro_pdts = array();    //存放除去赠品意外的，不参与促销的商品
        $ary_format_pdts = array();    //直接存放二维的货品信息
        //1:积分商品，2：赠品，3：组合商品，4：自由推荐商品
        foreach($ary_pdts as $ary_p) {
            //只有普通商品参与促销
            if($ary_p['type'] == 0) {
                //根据货品id查找后台指定的促销信息（只有指定某个确切商品的促销才能被查到）
                $ary_data = $proObj->getPriceInfo($ary_p['pdt_id'], $int_mid, $ary_p['type'],array(),$is_cache);
            	$str_now = date('Y-m-d H:i:00');
            	//一口价判断是否是折上折
            	$int_is_discount = 0;
            	if(isset($ary_data['pmn_name']) && !empty($ary_data['pmn_name'])){
					if($is_cache == 1){
						$ary_new_pro = D('Gyfx')->selectOneCache('promotion','pmn_config', array('pmn_id'=>$ary_data['pmn_id'],'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)), 'pmn_order desc',600);					
					}else{
						$ary_new_pro = $this->where(array('pmn_id'=>$ary_data['pmn_id'],'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)))->order('pmn_order desc')->field('pmn_config')->find();						
					}					
            		$ary_conf = json_decode($ary_new_pro['pmn_config'], true);
            		if($ary_conf['cfg_use_again_discount'] == '1'){
            			$int_is_discount = 1;
            		}

            		//如果是折上折的话计算订单促销
            		if($int_is_discount == '1'){
            			$ary_new_pdts[$ary_p['g_id']][$ary_p['pdt_id']] = $ary_p;
            			$ary_g_ids[] = $ary_p['g_id'];
            			$ary_format_pdts[$ary_p['pdt_id']] = $ary_p;
            		}else{
            			$ary_no_pro_pdts[] = $ary_p;
            		}
            	} else {
            		$ary_new_pdts[$ary_p['g_id']][$ary_p['pdt_id']] = $ary_p;
            		$ary_g_ids[] = $ary_p['g_id'];
            		$ary_format_pdts[$ary_p['pdt_id']] = $ary_p;
            	}
            }
            else {
                //赠品，积分商品，自由推荐，不参与促销
                if($ary_p['type'] != 2) {
                    //剔除赠品，每次计算重新匹配赠品
                    $ary_no_pro_pdts[] = $ary_p;
                }
            }
        }
        unset($ary_p);
        if(!empty($ary_no_pro_pdts)) {
            $this->noProGoodsHandle($int_mid, $ary_no_pro_pdts, $ary_pdts_pro_related,$is_cache);
        }
        //取出所有商品分组
        $ary_pdt_ggid = D('RelatedGoodsGroup')->getGoodsGroupByGid($ary_g_ids,$is_cache);
		//取出所有商品分类
        $ary_cat_pdt_ggid = D('RelatedGoodsCategory')->getGoodsCatesByGid($ary_g_ids,$is_cache);
		//取出所有商品品牌
        $ary_brand_pdt_ggid = D('ViewGoods')->getGoodsBrandsByGid($ary_g_ids,$is_cache);		
        unset($ary_g_ids);
        //如果购物车中商品没有归组,分类和品牌，则不可能有购物车促销
        if(empty($ary_pdt_ggid) && empty($ary_cat_pdt_ggid) && empty($ary_brand_pdt_ggid)){
            $ary_result['success'] = 1;
            $ary_result['msg'] = '没有可用的促销规则！';
            $ary_result['code'] = 'pro_recPro_001';
            $this->noProGoodsHandle($int_mid, $ary_format_pdts, $ary_pdts_pro_related,$is_cache);
            return $ary_result;
        }
        $obj_goods_group = D('GoodsGroup');
        //去除无效的商品分组
        $ary_new_pdt_ggid = array();
        foreach($ary_pdt_ggid as $ary_gg_id) {
            $ary_status = $obj_goods_group->getGoodsGroupById($ary_gg_id['gg_id'], array('gg_status'),$is_cache);
			//去除已无效的分组信息
            if($ary_status['gg_status'] == 1) {
                $ary_new_pdt_ggid[$ary_gg_id['gg_id']][$ary_gg_id['g_id']] = $ary_new_pdts[$ary_gg_id['g_id']];
            }
        }
        unset($obj_goods_group);
		//获取商品分类
        $ary_new_pdt_catid = array();		
		foreach($ary_cat_pdt_ggid as $ary_cat_id){
			$ary_new_pdt_catid[$ary_cat_id['gc_id']][$ary_cat_id['g_id']] = $ary_new_pdts[$ary_cat_id['g_id']];
		}
		//获取商品品牌
        $ary_new_pdt_brandid = array();		
		foreach($ary_brand_pdt_ggid as $ary_brand_id){
			$ary_new_pdt_brandid[$ary_brand_id['gb_id']][$ary_brand_id['g_id']] = $ary_new_pdts[$ary_brand_id['g_id']];
		}		
        //根据分组取出所有可用的购物车促销
		//根据商品分组查找促销ID
        $ary_group_pro_id = D('RelatedPromotionGoodsGroup')->getProByGoodsGroups(array_keys($ary_new_pdt_ggid),false, array('pmn_id','gg_id'),$is_cache);  
		//根据商品分类查找促销ID
        $ary_cate_pro_id = D('RelatedPromotionGoodsCategory')->getProByGoodsCates(array_keys($ary_new_pdt_catid),false, array('pmn_id','gc_id'),$is_cache); 	
		//根据商品品牌查找促销ID
        $ary_brand_pro_id = D('RelatedPromotionGoodsBrand')->getProByGoodsBrands(array_keys($ary_new_pdt_brandid),false, array('pmn_id','brand_id'),$is_cache);  
        //三个条件都没有查到促销活动
        if(empty($ary_group_pro_id) && empty($ary_cate_pro_id) && empty($ary_brand_pro_id)){
            $ary_result['code'] = 'pro_recPro_002';
            $ary_result['success'] = 1;
            $ary_result['msg'] = '没有可用的促销规则！';
            $this->noProGoodsHandle($int_mid, $ary_format_pdts, $ary_pdts_pro_related,$is_cache);
            return $ary_result;
        }
        $ary_group_pmnid = array();
        $ary_pro_group_related = array();    //存放促销和分组的对应关系
        foreach ($ary_group_pro_id as $ary_gpi) {
            $ary_group_pmnid[$ary_gpi['pmn_id']] = $ary_gpi['pmn_id'];
            $ary_pro_group_related[$ary_gpi['pmn_id']][$ary_gpi['gg_id']] = $ary_gpi['gg_id'];
        }
        unset($ary_group_pro_id);
		//存放促销和分类品牌相关对应关系
        $ary_category_pmnid = array();
        $ary_pro_cate_related = array();    //存放促销和分组的对应关系
        foreach ($ary_cate_pro_id as $ary_gci) {
            $ary_category_pmnid[$ary_gci['pmn_id']] = $ary_gci['pmn_id'];
            $ary_pro_cate_related[$ary_gci['pmn_id']][$ary_gci['gc_id']] = $ary_gci['gc_id'];
        }
        $ary_brand_pmnid = array();
        $ary_pro_brand_related = array();    //存放促销和分组的对应关系
        foreach ($ary_brand_pro_id as $ary_gbi) {
            $ary_brand_pmnid[$ary_gbi['pmn_id']] = $ary_gbi['pmn_id'];
            $ary_pro_brand_related[$ary_gbi['pmn_id']][$ary_gbi['brand_id']] = $ary_gbi['brand_id'];
        }
        //通过促销对象范围过滤促销规则
        $ary_mem_pro_id = D('RelatedPromotionMembers')->getProByMemInfo($int_mid, $int_ml_id, $ary_mem_group, false, array('pmn_id'),$is_cache);
        if(!is_array($ary_mem_pro_id) || empty($ary_mem_pro_id)) {
            $ary_result['code'] = 'pro_recPro_003';
            $ary_result['success'] = 1;
            $ary_result['msg'] = '没有可用的促销规则！';
            $this->noProGoodsHandle($int_mid, $ary_format_pdts, $ary_pdts_pro_related,$is_cache);
            return $ary_result;
        }
        $ary_mem_pmnid = array();
        foreach ($ary_mem_pro_id as $ary_mpi) {
            $ary_mem_pmnid[$ary_mpi['pmn_id']] = $ary_mpi['pmn_id'];
        }
        unset($ary_mem_pro_id);
        //促销规格对应的促销列表
		$ary_tmp_pmnids = $ary_group_pmnid;
		if(!empty($ary_brand_pmnid)){
			foreach($ary_brand_pmnid as $bkey=>$bval){
				$ary_tmp_pmnids[$bkey]=$bval;
			}
			unset($ary_brand_pmnid);
		}
		if(!empty($ary_category_pmnid)){
			foreach($ary_category_pmnid as $ckey=>$cval){
				$ary_tmp_pmnids[$ckey]=$cval;
			}
			unset($ary_category_pmnid);			
		}
        //合并分组相关促销和会员相关促销，取两者交集
        $ary_pro = array_intersect_key($ary_tmp_pmnids, $ary_mem_pmnid);
		//过滤可用促销规则，只去一个优先级最高且满足条件的促销
        $obj_pro = D('Promotion');
        $str_now = date('Y-m-d H:i:00');
		if($is_cache == 1){
			$ary_new_pro = D('Gyfx')->selectAllCache('promotion','', array('pmn_id'=>array('in',$ary_pro),'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)), 'pmn_order desc',$ary_group=null,$ary_limit=null,600);				
		}else{
			$ary_new_pro = $this->where(array('pmn_id'=>array('in',$ary_pro),'pmn_enable'=>1,'pmn_start_time'=>array('ELT',$str_now),'pmn_end_time'=>array('EGT',$str_now)))->order('pmn_order desc')->select();	
		}

        //促销标记，防止无合适促销导致死循环
        $bl_pro_sign = false;
        foreach ($ary_new_pro as $ary_pro_i) {  
            $bl_pro_all_price = 0.000;
            //存放该促销规则下面的所有货品
            $ary_goods_pro_related = array();
			
            //计算该促销下面所有分组中商品的价格
            if(isset($ary_pro_group_related[$ary_pro_i['pmn_id']])
                && is_array($ary_pro_group_related[$ary_pro_i['pmn_id']])
                && !empty($ary_pro_group_related[$ary_pro_i['pmn_id']])) {
                //遍历改促销下面的所有分组
                foreach ($ary_pro_group_related[$ary_pro_i['pmn_id']] as $int_pgi) {
                    //商品分组
                    if(isset($ary_new_pdt_ggid[$int_pgi])
                        && is_array($ary_new_pdt_ggid[$int_pgi])
                        && !empty($ary_new_pdt_ggid[$int_pgi])) {
                        //遍历该分组下面的所有商品
                        foreach ($ary_new_pdt_ggid[$int_pgi] as $int_gid=>$ary_g_i) {
								//遍历该商品下面的所有货品
								foreach($ary_g_i as $int_pdt_id=>$ary_pdt_i) {
									if(!isset($ary_goods_pro_related['pdt_ids'][$int_pdt_id])) {
										$ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, $ary_pdt_i['type'],array(),$is_cache);
										if(isset($ary_pdt_i['oi_price']) && $ary_pdt_i['oi_price'] > 0) {
	                                    	//如果oi_price大于0，则说明有价格修改，直接使用该价格为最终价格,使用一个不存在的类型来获取其销售价
		                                    $ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, 'notype',array(),$is_cache);
		                                    $ary_data['pdt_price'] = sprintf("%0.3f", $ary_pdt_i['oi_price']);
		                                }
		                                $bl_pro_all_price += ($ary_data['pdt_price']*$ary_pdt_i['num']);
		                                $ary_goods_pro_related['pro']['products'][$int_pdt_id] = array_merge($ary_pdt_i,$ary_data);
										//参与促销的商品的销售价总和
										$ary_goods_pro_related['pro']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_pdt_i['num']);
										//参与促销的商品的商品优惠金额(购物车促销优惠的金额不在其中)
										$ary_goods_pro_related['pro']['goods_all_discount'] += (($ary_data['pdt_sale_price'] - $ary_data['pdt_price'])*$ary_pdt_i['num']);
										//将参与该促销的商品记录下来
										$ary_goods_pro_related['pdt_ids'][$int_pdt_id] = $int_pdt_id;
									}
								}
                        }
                    }
                }
            }

            //计算该促销下面所有分类中商品的价格
            if(isset($ary_pro_cate_related[$ary_pro_i['pmn_id']])
                && is_array($ary_pro_cate_related[$ary_pro_i['pmn_id']])
                && !empty($ary_pro_cate_related[$ary_pro_i['pmn_id']])) {
                //遍历改促销下面的所有分组
                foreach ($ary_pro_cate_related[$ary_pro_i['pmn_id']] as $int_pgi) {
                    if(isset($ary_new_pdt_catid[$int_pgi]) && is_array($ary_new_pdt_catid[$int_pgi]) && !empty($ary_new_pdt_catid[$int_pgi])) {
                        //遍历该分组下面的所有商品
                        foreach ($ary_new_pdt_catid[$int_pgi] as $int_gid=>$ary_g_i) {
								//遍历该商品下面的所有货品
								foreach($ary_g_i as $int_pdt_id=>$ary_pdt_i) {
									if(!isset($ary_goods_pro_related['pdt_ids'][$int_pdt_id])) {
										$ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, $ary_pdt_i['type'],array(),$is_cache);
										if(isset($ary_pdt_i['oi_price']) && $ary_pdt_i['oi_price'] > 0) {
	                                    	//如果oi_price大于0，则说明有价格修改，直接使用该价格为最终价格,使用一个不存在的类型来获取其销售价
		                                    $ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, 'notype',array(),$is_cache);
		                                    $ary_data['pdt_price'] = sprintf("%0.3f", $ary_pdt_i['oi_price']);
		                                }
		                                $bl_pro_all_price += ($ary_data['pdt_price']*$ary_pdt_i['num']);
		                                $ary_goods_pro_related['pro']['products'][$int_pdt_id] = array_merge($ary_pdt_i,$ary_data);
										//参与促销的商品的销售价总和
										$ary_goods_pro_related['pro']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_pdt_i['num']);
										//参与促销的商品的商品优惠金额(购物车促销优惠的金额不在其中)
										$ary_goods_pro_related['pro']['goods_all_discount'] += (($ary_data['pdt_sale_price'] - $ary_data['pdt_price'])*$ary_pdt_i['num']);
										//将参与该促销的商品记录下来
										$ary_goods_pro_related['pdt_ids'][$int_pdt_id] = $int_pdt_id;
									}
								}
                        }
                    }
                }
            }

            //计算该促销下面所有品牌中商品的价格
            if(isset($ary_pro_brand_related[$ary_pro_i['pmn_id']])
                && is_array($ary_pro_brand_related[$ary_pro_i['pmn_id']])
                && !empty($ary_pro_brand_related[$ary_pro_i['pmn_id']])) {
                //遍历改促销下面的所有分组
                foreach ($ary_pro_brand_related[$ary_pro_i['pmn_id']] as $int_pgi) {
                    if(isset($ary_new_pdt_brandid[$int_pgi]) && is_array($ary_new_pdt_brandid[$int_pgi]) && !empty($ary_new_pdt_brandid[$int_pgi])) {
                        //遍历该分组下面的所有商品
                        foreach ($ary_new_pdt_brandid[$int_pgi] as $int_gid=>$ary_g_i) {
								//遍历该商品下面的所有货品
								foreach($ary_g_i as $int_pdt_id=>$ary_pdt_i) {
									if(!isset($ary_goods_pro_related['pdt_ids'][$int_pdt_id])) {
										$ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, $ary_pdt_i['type'],array(),$is_cache);
										if(isset($ary_pdt_i['oi_price']) && $ary_pdt_i['oi_price'] > 0) {
	                                    	//如果oi_price大于0，则说明有价格修改，直接使用该价格为最终价格,使用一个不存在的类型来获取其销售价
		                                    $ary_data = $proObj->getPriceInfo($ary_pdt_i['pdt_id'], $int_mid, 'notype',array(),$is_cache);
		                                    $ary_data['pdt_price'] = sprintf("%0.3f", $ary_pdt_i['oi_price']);
		                                }
		                                $bl_pro_all_price += ($ary_data['pdt_price']*$ary_pdt_i['num']);
		                                $ary_goods_pro_related['pro']['products'][$int_pdt_id] = array_merge($ary_pdt_i,$ary_data);
										//参与促销的商品的销售价总和
										$ary_goods_pro_related['pro']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_pdt_i['num']);
										//参与促销的商品的商品优惠金额(购物车促销优惠的金额不在其中)
										$ary_goods_pro_related['pro']['goods_all_discount'] += (($ary_data['pdt_sale_price'] - $ary_data['pdt_price'])*$ary_pdt_i['num']);
										//将参与该促销的商品记录下来
										$ary_goods_pro_related['pdt_ids'][$int_pdt_id] = $int_pdt_id;
									}
								}
                        }
                    }
                }
            }			
            $ary_conf = json_decode($ary_pro_i['pmn_config'], true);

            //如果该促销下面的所有商品的总价格在最高和最低区间之内则促销可用
            if($ary_conf['cfg_cart_start'] <= $bl_pro_all_price
                && $ary_conf['cfg_cart_end'] >= $bl_pro_all_price) {
                $bl_pro_sign = true;
                $ary_pro_info = $this->calProPricInfo($ary_pro_i, $bl_pro_all_price);
                //将匹配到的货品加入结果集中
                $ary_pdts_pro_related[$ary_pro_i['pmn_id']] = array_merge($ary_pro_info,$ary_goods_pro_related['pro']);
                //放入总计中
                $ary_pdts_pro_related['subtotal']['goods_total_price'] += $bl_pro_all_price;
                $ary_pdts_pro_related['subtotal']['goods_total_sale_price'] += $ary_goods_pro_related['pro']['goods_total_sale_price'];
                $ary_pdts_pro_related['subtotal']['goods_all_discount'] += $ary_goods_pro_related['pro']['goods_all_discount'];
                unset($ary_pro_info);
                $ary_result['code'] = 'pro_calShopCartPro_006';
                $ary_result['success'] = 1;
                //剔除参与促销的商品
                foreach($ary_goods_pro_related['pdt_ids'] as $v_pdt_id) {
                    unset($ary_format_pdts[$v_pdt_id]);
                }
                if(!empty($ary_format_pdts)) {
                    $this->recPro($ary_format_pdts, $int_mid, $int_ml_id, $ary_mem_group, $ary_pdts_pro_related,$is_cache);
                    return $ary_result;
                }
                break;
            }

        }

        //所有商品都没有可用促销规则
        if(!$bl_pro_sign) {
            $ary_result['code'] = 'pro_recPro_007';
            $ary_result['success'] = 1;
            $ary_result['msg'] = '没有可用的促销规则！';
            $this->noProGoodsHandle($int_mid, $ary_format_pdts, $ary_pdts_pro_related,$is_cache);
        }
        unset($ary_format_pdts);
        return $ary_result;
    }
    
    /**
     * 计算每个购物车促销的促销价格
     * @author zhanghao
     * @param array $ary_pro <p>促销信息</p>
     * @param bool $bl_goods_total<p>参与该促销的商品总价格</p>
     * @date 2013-9-10
     * @return 返回该促销的最终价格信息
     */
    private function calProPricInfo($ary_pro, $bl_goods_total, &$ary_pdts_pro_related) {
        $ary_config = json_decode($ary_pro['pmn_config'], true);    //配置信息
        $ary_pro['goods_total_price'] = $bl_goods_total;        
        switch (strtoupper($ary_pro['pmn_class'])) {
            case 'MJLB':
                $ary_pro['pro_goods_total_price'] = $bl_goods_total;
                $ary_pro['pro_goods_discount'] = 0;
                $ary_pro['pro_goods_mjlb'] = $ary_config['cfg_discount'] > 0 ? $ary_config['cfg_discount'] : 0;
            break;
            case 'MJIAN':
                //应用促销之后的商品总价,如果优惠之后的价格小于0，则优惠之后的价格为0
                $ary_pro['pro_goods_total_price'] = ($bl_goods_total - $ary_config['cfg_discount']) > 0 ? sprintf("%0.3f", ($bl_goods_total - $ary_config['cfg_discount'])) : 0;
                //促销优惠价格
                $ary_pro['pro_goods_discount'] = $ary_config['cfg_discount'] > 0 ? sprintf("%0.3f", $ary_config['cfg_discount']) : 0;
				if($ary_pro['pro_goods_discount']>$ary_pro['goods_total_price']){
					$ary_pro['pro_goods_discount'] = $ary_pro['goods_total_price'];
				}
            break;
            case 'MZHEKOU':
                //应用促销之后的商品总价，如果折扣为负数或者为0，则使用原价
                $ary_pro['pro_goods_total_price'] = $ary_config > 0 ? sprintf("%0.3f", ($bl_goods_total*$ary_config['cfg_discount'])) : $bl_goods_total;
                //促销优惠价格
                $ary_pro['pro_goods_discount'] = $bl_goods_total - $ary_pro['pro_goods_total_price'];
            break;
            case 'MZENPIN':
                //将赠品加入商品中
                $ary_config = json_decode($ary_pro['pmn_config'], true);
                if(is_array($ary_config['cfg_products']) && !empty($ary_config['cfg_products'])) {
                    //去除赠品数据，生成赠品数据
                    foreach($ary_config['cfg_products'] as $k_g=>$ary_g) {
                        //将赠品放入返回的数据集中(相同商品只赠送一次)
                        if(is_array($ary_g) && !empty($ary_g)) {
                            foreach ($ary_g as $k_id=>$v_p) {
                                $ary_pro['gifts'][$k_id] = array(
                                    'pdt_id' => $k_id,
                                    'num' => 1,
                                    'type' => 2,    //赠品类型为2
                                    'g_id' => $k_g,
                                    'pmn_id' => $ary_pro['pmn_id']
                                );
                            }
                        }
                    }
                }
            break;
            default:
                //除去满减，满折扣之外的促销商品上无优惠
                $ary_pro['pro_goods_total_price'] = $bl_goods_total;
                $ary_pro['pro_goods_discount'] = 0;
            
        }
        return $ary_pro;
    }
    
    /**
     * 没有应用到购物车促销的商品处理
     * @author zhanghao
     * @param intger $int_mid <p>会员ID</p>
     * @param array $ary_pdts <p>需要处理的商品</p>
     * @param array $ary_pdts_pro_related <p>存放促销返回值</p>
     * @date 2013-9-10
     * @return 
     */
    private function noProGoodsHandle($int_mid, $ary_pdts, &$ary_pdts_pro_related,$is_cache) {
        $ary_result = array(
            'success' => 0,
            'code' => 0,
            'msg' => '',
            'data' => array()
        );
        $proObj = new ProPrice();
        foreach($ary_pdts as $ary_p) {
            //普通商品
            if($ary_p['type'] == 0){
                //$ary_data = $proObj->getPriceInfo($ary_p['pdt_id'], $int_mid, $ary_p['type'],array(),$is_cache);
                //如果商品金额不为空按传过来的商品金额为准
                //if(!empty($ary_p['oi_price'])){
               //    $ary_data['pdt_price'] = $ary_p['oi_price'];
               // }
                if(isset($ary_p['oi_price']) && $ary_p['oi_price'] > 0) {
                    //如果oi_price大于0，则说明有价格修改，直接使用该价格为最终价格,使用一个不存在的类型来获取其销售价
                    $ary_data = $proObj->getPriceInfo($ary_p['pdt_id'], $int_mid, 'notype',array(),$is_cache);
                    $ary_data['pdt_price'] = sprintf("%0.3f", $ary_p['oi_price']);
                } else {
                    $ary_data = $proObj->getPriceInfo($ary_p['pdt_id'], $int_mid, $ary_p['type'],array(),$is_cache);
                }

                if($ary_data['pdt_price'] >0 ){
                    $ary_pdts_pro_related['subtotal']['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num']);
                    $ary_pdts_pro_related['subtotal']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num']);
                    $ary_pdts_pro_related['subtotal']['goods_all_discount'] += ($ary_data['pdt_sale_price']-$ary_data['pdt_price']);
                    $ary_pdts_pro_related[0]['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num']);
                    $ary_pdts_pro_related[0]['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num']);
                    $ary_pdts_pro_related[0]['goods_all_discount'] += (($ary_data['pdt_sale_price']-$ary_data['pdt_price'])*$ary_p['num']);
                }
                //将普通商品加入返回队列中
                $ary_pdts_pro_related[0]['products'][$ary_p['pdt_id']] = array_merge($ary_p,$ary_data);
            } else if($ary_p['type'] == 4) {
                foreach($ary_p['pdt_id'] as $k=>$v_p) {
                    //自由推荐商品
                    //商品自由推荐价格异常,按照普通商品价格返回
                    $ary_data = $proObj->getPriceInfo($v_p, $int_mid, $ary_p['type'],array(),$is_cache);
                    $ary_result['msg'] = '商品自由推荐价格计算异常';
                    $ary_result['code'] = 'pro_noProGoodsHandle_001';  
                    $ary_pdts_pro_related['subtotal']['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related['subtotal']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related['subtotal']['goods_all_discount'] += (($ary_data['pdt_sale_price']-$ary_data['pdt_price'])*$ary_p['num'][$k]);
                    
                    $ary_pdts_pro_related[0]['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related[0]['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related[0]['goods_all_discount'] += (($ary_data['pdt_sale_price']-$ary_data['pdt_price'])*$ary_p['num'][$k]);
                    
                    $ary_p['price'][$k] = $ary_data;
                }
                //将自由推荐商品加入返回数据中
                $ary_pdts_pro_related[0]['products']['free'.$ary_p['fc_id']] = $ary_p;
            } else if($ary_p['type'] == 3) {
                //组合商品暂不处理
                
            } else if($ary_p['type'] == 1) {
                //积分商品不处理
                $ary_data = $proObj->getPriceInfo($ary_p['pdt_id'], $int_mid, $ary_p['type'],array(),$is_cache);
                //将积分商品加入队列
                $ary_pdts_pro_related[0]['products'][$ary_p['pdt_id']] = $ary_p;
                $ary_pdts_pro_related['subtotal']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num']);
                $ary_pdts_pro_related['subtotal']['goods_all_discount'] += ($ary_data['pdt_sale_price']*$ary_p['num']);
                //总积分
                $ary_pdts_pro_related['subtotal']['goods_all_point'] += ($ary_data['pdt_point']*$ary_p['num']);
                $ary_pdts_pro_related[0]['goods_total_sale_price'] += ($ary_data['pdt_point']*$ary_p['num']);
                $ary_pdts_pro_related[0]['goods_all_discount'] += ($ary_data['pdt_price']*$ary_p['num']);
            } else if($ary_p['type'] == 6){
                //自由搭配商品处理
                foreach($ary_p['pdt_id'] as $k=>$v_p) {
                    //自由搭配商品
                    //商品自由推荐价格异常,按照普通商品价格返回
                    $ary_data = $proObj->getPriceInfo($v_p, $int_mid, $ary_p['type'],array(),$is_cache);
                    
                    $ary_result['msg'] = '商品自由推荐价格计算异常';
                    $ary_result['code'] = 'pro_noProGoodsHandle_002';  
                    $ary_pdts_pro_related['subtotal']['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related['subtotal']['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related['subtotal']['goods_all_discount'] += (($ary_data['pdt_sale_price']-$ary_data['pdt_price'])*$ary_p['num'][$k]);
                    
                    $ary_pdts_pro_related[0]['goods_total_price'] += ($ary_data['pdt_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related[0]['goods_total_sale_price'] += ($ary_data['pdt_sale_price']*$ary_p['num'][$k]);
                    $ary_pdts_pro_related[0]['goods_all_discount'] += (($ary_data['pdt_sale_price']-$ary_data['pdt_price'])*$ary_p['num'][$k]);
                    
                    $ary_p['price'][$k] = $ary_data;
                }
                //将自由推荐商品加入返回数据中
                $ary_pdts_pro_related[0]['products']['free'.$ary_p['fc_id']] = $ary_p;
            }
        }
        $ary_pdts_pro_related[0]['goods_total_price'] = sprintf("%0.3f", $ary_pdts_pro_related[0]['goods_total_price']);
        return $ary_result;
    } 
}
