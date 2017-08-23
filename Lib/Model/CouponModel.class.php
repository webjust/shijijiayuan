<?php

/**
 * 优惠券模型层 Model
 * @package Model
 * @version 7.0
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-06
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CouponModel extends GyfxModel {

    /**
     * 自动完成操作
     * @var array
     */
    protected $_auto = array(
        array('c_create_time', 'date', 1, 'function', array("Y-m-d h:i:s")), // 对create_time字段在更新的时候写入当前时间戳
    );

    /**
     * 自动生成优惠券
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     *
     * @param string $str_name 优惠券名称
     * @param float $flt_money 优惠券金额
     * @param int $int_num 批量生成的数量，建议单批不要超过100
     * @param int $int_long 优惠券SN的长度，不含前后缀在内。避免大量重复的建议不要低于6位
     * @param string $str_memo 优惠券说明
     * @param string $str_prefix SN的前缀
     * @param string $str_suffix SN的后缀
     * @param string $str_start_time 有效期生效时间
     * @param string $str_end_time 有效期过期时间
     * @param float $flt_condition_money 订单满足多少金额方可使用
     * @param int $int_mid 所属的会员ID
     * @return array 返回生成的优惠券ID数组
     */
    public function autoAdd($str_name, $flt_money, $int_num = 100, $int_long = 6, $str_memo = '', $str_prefix = '', $str_suffix = '', $str_start_time = '', $str_end_time = '', $flt_condition_money = 0.00, $int_mid=0,$c_type=0) {

        $data = array();
        $data['c_name'] = $str_name;
        $data['c_money'] = $flt_money;
        $data['c_memo'] = $str_memo;
        $data['c_start_time'] = $str_start_time;
        $data['c_end_time'] = $str_end_time;
        $data['c_condition_money'] = $flt_condition_money;
        $data['c_is_use'] = 0;
        $data['c_create_time'] = date('Y-m-d h:i:s');
        $data['c_user_id'] = $int_mid;
		$data['c_type'] = $c_type;
        $i = 0;
        do {
            //生成序列号
            $sn = $str_prefix . randStr($int_long) . $str_suffix;
            //判断是否唯一
            $res = $this->where(array('c_sn' => $sn))->find();
            if (false == $res) {
                //未被使用可以用
                $data['c_sn'] = $sn;
                $insert = $this->data($data)->add();
                if (false != $insert) {
                    $return[] = $insert;
                } else {
                    break;
                }
                $i++;
            }
        } while ($i < (int) $int_num);

        return $return;
    }
 /**
  * 更新优惠券记录
  * @author listen
  * @param $ary_data 需要更新字段
  * @param $csn 优惠券sn
  * @date 2013-02-27
  */
    public function doCouponUpdate($csn,$ary_data){
        $bool_res = false;
        if(isset($csn)){
            $where =  array('c_sn'=>$csn);
            $res = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->save($ary_data);
            if(!$res){
                return $bool_res;
            }else {
                $bool_res = true;
            }
        }
        return $bool_res;
    }
 /**
  * 验证优惠券
  * @author zhangjiasuo
  * @param $csn 优惠券sn
  * @date 2013-05-15
  */
    public function _CheckCoupon($csn,$cart_data,$mid){
        $mid = empty($_SESSION['Members']['m_id'])?$mid:$_SESSION['Members']['m_id'];
        $ary_csn = explode(',',$csn);
        
		$where = array('c_is_use' => 0, 'c_sn' => array('in',$ary_csn));
        $where['c_end_time'] = array('EGT', date('Y-m-d H:i:s'));
        $where['c_start_time'] = array('ELT', date('Y-m-d H:i:s'));
		$ary_coupon =M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->select();	
	   if(empty($ary_coupon) && !isset($ary_coupon)){
            return array('status'=>'error','msg'=>'优惠券编号错误或已使用或不满足使用条件');
        }
        if(count($ary_csn) != count($ary_coupon)){
            foreach ($ary_csn as $key=>$csn){
                foreach($ary_coupon as $coupon){
                    if($csn == $coupon['c_sn']){
                        unset($ary_csn[$key]);
                    }
                }
            }
            return array('status'=>'error','msg'=>'优惠券编号：'.implode('，',$ary_csn).'编号错误或已使用或不满足使用条件');
        }
		
		//折扣券只能使用一张
		/**
		if(count($ary_coupon)>1){
			$discount = 0;
			$moneycount = 0;
			foreach($ary_coupon as $val){
				if($val['c_type'] == '1'){
					$discount++;
				}else{
					$moneycount++;
				}
			}
		}
		if($discount>0 && $moneycount>0){
			return array('status'=>'error','msg'=>'现金券和折扣券不能同时使用');
		}
		if($discount>1){
			return array('status'=>'error','msg'=>'折扣券只能使用一张');
		}
		**/
		//券只能使用一张
		if(count($ary_coupon)>1){
            return array('status'=>'error','msg'=>'优惠券只能使用一张');
		}
        $i = 0;
        foreach($ary_coupon as $coupon){
			//优惠券使用者
			if(!empty($coupon['c_user_id'])){
				if($coupon['c_user_id'] != $mid){
					return array('status'=>'error','msg'=>$coupon['c_sn'].'错误或已使用或不满足使用条件');
				}			
			}
            //判断商品是否允许使用优惠券
            $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
            ->where(array('c_id'=>$coupon['c_id']))
            ->field('group_concat(gg_id) as group_id')->group('gg_id')->select();
            if(!empty($group_data)){
                //查询团购管理商品
				$gids = array();
                foreach ($group_data as $gd){
                    $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                    ->where(array('gg_id'=>array('in',$gd['group_id'])))->field('g_id')->group('g_id')->select();
                    foreach($item_data as $item){
                        foreach($cart_data as $cart){
                            if($cart['g_id'] == $item['g_id']){
								$gids[] = $item['g_id'];
                                $array_return['msg'][$i] = $coupon;
                            }
                        }
                    }
                }
				$array_return['msg'][$i]['gids'] = $gids;
                if(empty($array_return['msg'][$i]['gids'])){
                    return array('status'=>'error','msg'=>'部分优惠券编号错误或已使用或不满足使用条件');
                }
            }else{
                $array_return['msg'][$i] = $coupon;
				$array_return['msg'][$i]['gids'] = 'All';
            }
            $i++;
        }
        $array_return['status'] = 'success';
		return $array_return;
    }

    /**
    * 检测优惠券号是否满足使用条件
    * @author hcaijin
    * @param $csn 优惠券sn
    * @date 2015-11-19
    */
    public function CheckCoupon($csn,$cart_data,$mid){
        $mid = empty($_SESSION['Members']['m_id'])?$mid:$_SESSION['Members']['m_id'];
        $nowDate = date('Y-m-d H:i:s');
        
		$where = array('c_is_use' => 0, 'c_sn' => $csn, 'c_user_id' => $mid);
        $where['c_end_time'] = array('EGT', $nowDate);
        $where['c_start_time'] = array('ELT', $nowDate);
		$ary_coupon = M('coupon',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->find();	
        //判断商品是否允许使用优惠券
        $group_data = M('related_coupon_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('c_id'=>$ary_coupon['c_id']))->field('group_concat(gg_id) as group_id')->group('gg_id')->select();

	    if(empty($ary_coupon) && !isset($ary_coupon)){
            $ary_act = D("CouponActivities")->checkIsCoupon($mid, $csn);
            if(empty($ary_act) || !is_array($ary_act)){
                return array('status'=>'error','msg'=>'优惠券编号错误或已使用或不满足使用条件');
            }else{
                $ary_coupon = array(
                    'c_name' => $ary_act['ca_name'],
                    'c_sn' => $ary_act['ca_sn'],
                    'c_name' => $ary_act['ca_name'],
                    'c_start_time' => $ary_act['ca_start_time'],
                    'c_end_time' => $ary_act['ca_end_time'],
                    'c_is_use' => 0,
                    'c_memo' => $ary_act['ca_memo'],
                    'c_money' => $ary_act['c_money'],
                    'c_condition_money' => $ary_act['c_condition_money'],
                    'c_user_id' => $mid,
                    'c_create_time' => $nowDate,
                    'c_type' => $ary_act['c_type'],
                    'ca_id' => $ary_act['ca_id'],
                    'is_oppsn' => 1
                );
                $group_data = json_decode($ary_act['ca_ggid'],1);
            }
        }
        $i = 0;
        if(!empty($group_data)){
            //查询团购管理商品
            $gids = array();
            foreach ($group_data as $gd){
                $item_data = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')
                ->where(array('gg_id'=>array('in',$gd)))->field('g_id')->group('g_id')->select();
                foreach($item_data as $item){
                    foreach($cart_data as $cart){
                        if($cart['g_id'] == $item['g_id']){
                            $gids[] = $item['g_id'];
                            $array_return['msg'][$i] = $ary_coupon;
                        }
                    }
                }
            }
            $array_return['msg'][$i]['gids'] = $gids;
            if(empty($array_return['msg'][$i]['gids'])){
                return array('status'=>'error','msg'=>'部分优惠券编号错误或已使用或不满足使用条件');
            }
        }else{
            $array_return['msg'][$i] = $ary_coupon;
            $array_return['msg'][$i]['gids'] = 'All';
        }
        $array_return['status'] = 'success';
		return $array_return;
    }
    
    /**
     * 订单满足促销后获取优惠券
     *
     * @param $ary_orders 订单详情
     * @param $m_id 用户id
     * @author <qianyijun@guanyisoft.com>
     * @date 2013-10-12
     */
    public function setPoinGetCoupon($ary_orders,$m_id){
    
        $ary_pdt_info = array();
        $item_info = array();
        foreach ($ary_orders as $value){
            $key = $value['pdt_id'];
            $item_info[$key]['pdt_id'] = $value['pdt_id'];
            $item_info[$key]['num'] = $value['oi_nums'];
            $item_info[$key]['type'] = $value['oi_type'];
        }
        $item_pdt_info = D('Cart')->getProductInfo($item_info);
        
        $ary_pdt_info['rule_info'] = array('pmn_id' => null,'again_discount'=>null);
        foreach ($item_pdt_info as &$info){
            if (!empty($info['rule_info']['pmn_id']) && empty($ary_pdt_info['rule_info']['pmn_id'])) {
                $ary_pdt_info['rule_info'] = $info ['rule_info'];
            }
            $ary_pdt_info [$info ['pdt_id']] = array('pdt_id'=>$info['pdt_id'],'num'=>$info['pdt_nums'],'type'=>$info['type'],'price'=>$info['f_price']);
            $goods_price += $info['f_price']*$info['pdt_nums'];
        }
        $ary_param = array('action' =>'paymentPage','mid'=>$m_id,'all_price'=>$ary_orders[0]['o_all_price'],'ary_pdt'=>$ary_pdt_info);
        $ary_param['goods_all_price'] = $goods_price;
        
        $promotion_result = D('Price')->getOrderPrice($ary_param);
        if (!empty($promotion_result['coupon_sn'])){
            $ary_copon = D('Coupon' )->where(array('c_id'=>$promotion_result['coupon_sn']))->find();
            // 如果满足促销规则送优惠券 将送到
            $ary_copon_orders = array(
                    'coupon_sn'=>$ary_copon['c_sn'],
                    'o_coupon'=> 1,
                    'coupon_value'=>$ary_copon['c_money'],
                    'coupon_start_date'=>$ary_copon['c_start_time'],
                    'coupon_end_date'=>$ary_copon['c_end_time']);
            D('Orders')->where(array('o_id'=>$ary_orders[0]['o_id']))->save($ary_copon_orders);
        }
        return true;
    }

    /**
     * 获取优惠券列表
     * @param array $ary_where
     *
     * @return array
     */
    public function couponList($ary_where=array()) {

        $date = date('Y-m-d');
        $where = array();
        if(isset($ary_where['m_id']) && $ary_where['m_id'] !='') {
            $where['c_user_id'] = $ary_where['m_id'] ;
        }
        if(isset($ary_where['c_sn']) && $ary_where['c_sn'] !=''){
            $where['c_sn'] = array('LIKE','%'.$ary_where['c_sn'].'%');
        }
        if(isset($ary_where['c_end_time']) && $ary_where['c_end_time'] !=''){
            $where['c_end_time'] = array('ELT',$ary_where['c_end_time']);
        }
        //优惠券使用状态
        $type = $ary_where['type'];
        if(isset($type)){
            switch($type){
                case '1':
                    $where['c_is_use']=0;
                    break;
                case '2':
                    $where['c_is_use'] = 1;
                    break;
                case '3':
                    $where['c_end_time'] =array('ELT',$date);
                    break;
                case '4':
                    $where['c_is_use'] = 4;
                    break;
                default:
                    $where;
            }
        }
        $page_no = max(1,(isset($ary_where['p']) ? (int)$ary_where['p'] : 1));
        $page_size = isset($ary_where['page_size']) ? (int)$ary_where['page_size'] : 5;
        $count =  $this->where($where)->count('c_id');
        $ary_return = array();
        $ary_return['pagination']['page_count'] = $count;
        $ary_return['pagination']['page_size'] = $page_size;
        $ary_return['pagination']['now_page'] = $page_no;

        $ary_coupon = $this->selectAll('coupon', '*', $where, 'c_create_time asc', null, array('page_no'=>$page_no,'page_size'=>$page_size));
        if(!empty($ary_coupon)){
            foreach($ary_coupon as $k=>$v){
                if($v['c_end_time']<$date && $v['c_end_time']!='0000-00-00 00:00:00'){
                    $ary_coupon[$k]['no'] = 1;
                }else {
                    $ary_coupon[$k]['no'] = 0;
                }
                $ary_res_gg_id = $this->selectOne('related_coupon_goods_group', 'gg_id', array('c_id'=>$v['c_id']));
                $ary_coupon[$k]['ggid'] = $ary_res_gg_id['gg_id'];
            }

        }

        $ary_return['list'] = $ary_coupon;

        return $ary_return;
    }

    /**
     * 可领取优惠券
     */
    public function canCollectCouponListPage($mid=0) {

        /**
         * 可领取的优惠券列表
         */
        $data = D('RedEnevlope')->availableCoupons($mid);
        //SEO关键词 及 描述设置
        $enevlope_where = array();
        $enevlope_where['rd_is_status'] = 1;
        //$enevlope = D('red_enevlope')->where($enevlope_where)->field('rd_keywords')->select();
        $enevlope = $data['typeList'];
        $keywords="";
        foreach ($enevlope as $v){
            $keywords .= $v['rd_keywords'].',';
        }
        $keywords=trim($keywords, ',');
        $title = '优惠券领取';
        $page_description = '优惠券领取';
        $result = array(
            'keywords' => $keywords,
            'title'    => $title,
            'page_description'    => $page_description,
            'data'     => $data,
        );

        return $result;
    }

}
