<?php
/**
 * 自由搭配模型层 Model
 * @package Model
 * @version 7.4.5
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-11-04
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class FreeRecommendModel extends GyfxModel{
    /**
     * 构造方法
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 计算自由搭配价格与优惠金额
     *
     * @param array $ary_collocation 搭配数组
     * @return array 搭配价，原价，优惠金额
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-06
     */
    public function getFreeCollotionPrice($ary_collocation){
        $goods_all_price = 0.000;
        $fr_all_price = 0.000;
        foreach ($ary_collocation as $key=>$val){
            $ary_collocation[$key]['pdt_sale_price'] = D('GoodsProducts')->where(array('pdt_id'=>$val['pdt_id']))->getField('pdt_sale_price');
            $goods_all_price += $ary_collocation[$key]['pdt_sale_price'];
            $fr_all_price += $val['fr_price'];
        }
        $save_price = $goods_all_price-$fr_all_price;
        return  array('goods_all_price'=>$goods_all_price,'fr_all_price'=>$fr_all_price,'save_price'=>$save_price);
    }
	
    /**
     * 自由推荐商品加入购物车
     *
     * @param array $ary_param 搭配数组
     * @return array 加入是否成功
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-12-01
     */
    public function addFreeCollocation($ary_parms){
		$return_res = array('status'=>false,'msg'=>'加入购物车失败','url'=>'');
		$ary_parms['m_id'] = trim($ary_parms['m_id']);
        $ary_parms['g_id'] = explode(',', $ary_parms['g_id']);
        $ary_parms['pdt_id'] = explode(',', $ary_parms['pdt_id']);
        $ary_parms['num'] = explode(',', $ary_parms['num']);
        //$ary_parms['fc_type'] = intval($ary_parms['fc_type']);
		if(!$ary_parms['fc_type']){
			$return_res['msg'] ='请输入加入购物车商品类型';
			return $return_res;
		}
        //商品类型4表示自由推荐商品
        $int_good_type = $ary_parms['fc_type'];
        //自由组合ID
        $int_fc_id = $ary_parms['fc_id'];
        //数组形式商品货号数组
        $ary_pdt_ids = $ary_parms['pdt_id'];
        $ary_g_ids = $ary_parms['g_id'];
        //数组形式商品数量数组
        $ary_nums = $ary_parms['num'];
        if (empty($int_fc_id)) {
			$return_res['msg'] ='自由推荐组合不存在！';
			return $return_res;			
        }
        //判断自由推荐组合是否在数据库里存在
        $fc_info = D("FreeCollocation")->field('fc_id,fc_related_good_id')->where(array('fc_id' => $int_fc_id, 'fc_status' => 1))->find();
        if (empty($fc_info)) {
			$return_res['msg'] ='此自由推荐组合不存在！';
			return $return_res;				
        }		
        if (count($ary_pdt_ids) < 2) {
			$return_res['msg'] ='至少选择两个商品做自由推荐！';
			return $return_res;				
        }
        if ((count($ary_pdt_ids) != count($ary_nums)) || (count($ary_pdt_ids) != count($ary_g_ids))) {
			$return_res['msg'] ='商品货号和数量要对应！';
			return $return_res;					
        }
        //自由组合含有的商品id
        sort($ary_g_ids);
        $str_g_ids = implode(',', $ary_g_ids);
        
        $str_fc_g_ids = explode(',', $fc_info['fc_related_good_id']);
        sort($str_fc_g_ids);
        $str_fc_pdt_ids = implode(',', $str_fc_g_ids);
        $tmparray = strpos($str_fc_pdt_ids, $str_g_ids);
        foreach ($ary_g_ids as $k=>$v){
            if (is_bool(strpos($str_fc_pdt_ids, $v))) {
				$return_res['msg'] ='商品不在自由组合里！';
				return $return_res;	
            }
        }
        //拼接加入购物车的数据
		if (!empty($ary_parms['m_id'])) {
			$car_key = base64_encode( 'mycart' . $ary_parms['m_id']);
			$ApiCarts = D('ApiCarts');
		}
        $ary_insert = array();
        $ary_insert['fc_id'] = $int_fc_id;
        $ary_insert['pdt_id'] = $ary_parms['pdt_id'];
        $ary_insert['num'] = $ary_parms['num'];
        $ary_insert['type'] = 4;
        $ary_db_carts = array();
        if (!empty($ary_parms['m_id'])) {
			$ary_db_carts = $ApiCarts->GetData($car_key);
        } else {
            $ary_session_carts = (session("?Cart")) ? session("Cart") : array();
        }
        //购物车数组对象
        $ary_cart = array();
        $ary_cart['free' . $int_fc_id] = $ary_insert;
        //拼接购物车数据
        foreach ($ary_cart as $key => $cart_info) {
            if (!empty($ary_parms['m_id'])) {//database
                //无需判断存不存在，自动替换,即如果此自由组合存在先删除后新增
                $ary_db_carts[$key] = $cart_info;
            } else {//session
                $ary_session_carts[$key] = $cart_info;
            }
        }
        if (!empty($ary_parms['m_id'])) {//保存到databese
			$Cart = $ApiCarts->WriteMycart($ary_db_carts,$car_key);
            if ($Cart == false) {
				$return_res['msg'] ='加入购物车失败';
				return $return_res;	
            } else {
                if ($ary_parms['skip'] != '') {
					$return_res['status'] = true;
					$return_res['msg'] ='加入购物车成功';
					$return_res['url'] =array(U('Ucenter/Orders/pageAdd'));
					return $return_res;	
                } else {
					$return_res['status'] = true;
					$return_res['msg'] ='加入购物车成功';
					return $return_res;						
                }
            }
        } else {//保存到session
            session("Cart", $ary_session_carts);
			$return_res['status'] = true;
			$return_res['msg'] ='加入购物车成功';
			return $return_res;		
        }
		return $return_res;
    }

    /**
     * 获取预售商品详情页sku信息
     * @param int $fr_id
     * @param array $members
     * @param int $pdt_id
     * @param bool $qiniu_pic
     * @return array
     * @author Nick
     * @date 2015-10-28
     */
    public function getDetails($fr_id, $members=array(), $pdt_id=0, $qiniu_pic=false) {
        //获取秒杀设置，秒杀初始价，总限售数，没人限购数，等
        $ary_free_recommend = $this->where(array(
            'fr_id'	=>	(int)$fr_id
        ))->find();
        $ary_free_recommend['buy_status'] = $ary_free_recommend['fr_status'];

//		dump($ary_free_recommend);die;
        $sp_per_number = 0;
        $sp_number = 0;
        $buy_nums = 0;

        $gid = $ary_free_recommend['fr_goods_id'];
        //获取该商品作为普通商品时的货品价格和库存
        $ary_goods_pdt = D('Goods')->getDetails($gid, $members, $pdt_id, $qiniu_pic);
        $pdt_detail = $ary_goods_pdt['page_detail'];
        //商品库存数
        $g_stock = $pdt_detail['gstock'];
        //如果秒杀限购数量为0或设置的限售数大于商品库存数，
        //则限购数等于商品库存数
        if($sp_number==0 || $sp_number > $g_stock) {
            $sp_number = $g_stock;
        }

        //剩余库存总数
        $avalible_stock = $sp_number-$buy_nums;
        //总库存
        $ary_goods_pdt['gstock'] = $avalible_stock;
        //可购买数
        $ary_goods_pdt['max_buy_number'] = $sp_per_number;

        foreach($pdt_detail['json_goods_pdts'] as $psd_id=>$goods_pdts) {
            $pdt_detail['json_goods_pdts'][$psd_id]['pdt_market_price'] = $goods_pdts['pdt_set_sale_price'];
            $pdt_final_price = $ary_free_recommend['fr_price'];
            $pdt_final_price <= 0 && $pdt_final_price = 0.00;
            $pdt_detail['json_goods_pdts'][$psd_id]['pdt_sale_price'] = $pdt_final_price;
        }

        if($qiniu_pic == true ){//七牛图片显示
            $ary_free_recommend['fr_goods_picture'] = D('QnPic')->picToQn($ary_free_recommend['fr_goods_picture']);
        }
        if(strtotime($ary_free_recommend['fr_statr_time']) > mktime()){
            $ary_free_recommend['buy_status'] = 3;
        }elseif(strtotime($ary_free_recommend['fr_end_time']) < mktime()){
            $ary_free_recommend['buy_status'] = 4;
        }
        if(0 == $pdt_id) {
            $pdt_detail['ary_goods_default_pdt'] = reset($pdt_detail['json_goods_pdts']);
        }else {
            foreach($pdt_detail['json_goods_pdts'] as $goods_pdt) {
                if($goods_pdt['pdt_id'] == $pdt_id) {
                    $pdt_detail['ary_goods_default_pdt'] = $goods_pdt;
                    break;
                }
            }
        }


        $ary_free_recommend['pdt_set_sale_price'] = $pdt_detail['ary_goods_default_pdt']['pdt_set_sale_price'];
        $ary_goods_pdt['page_detail'] = $pdt_detail;
        //dump($ary_free_recommend);die;
        $ary_goods_pdt = array_merge($ary_goods_pdt, $ary_free_recommend);
//        dump($ary_goods_pdt);die;
        return $ary_goods_pdt;
    }
}