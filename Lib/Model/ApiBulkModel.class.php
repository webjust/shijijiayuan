<?php

/**
 * 商品相关模型层 Model
 * @package Model
 * @version 7.8.6
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-08-20
 * @license MIT
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiBulkModel extends GyfxModel {
    
	private $result; 	// 返回结果
    private $collect_obj; 	// 会员表

	// 自动执行
	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10010', 		// 收藏错误初始码
			'sub_msg' => '获取列表失败', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
		 $this->groupbuy = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM');
	}
	
 	//获取团购列表
	public function getBulkList($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '获取团购列表失败!';
		$where =array('is_active'=>'1','deleted'=>'0');
		if(empty($params['type'])){
			$where['gp_end_time'] = array('gt',date('Y-m-d H:i:s'));
		}
		//还未开始
		if($params['type'] == 1){
			$where['gp_end_time'] = array('gt',date('Y-m-d H:i:s'));
			$where['gp_start_time'] = array('gt',date('Y-m-d H:i:s'));
		}
		//正在进行
		if($params['type'] == 2){
			$where['gp_end_time'] = array('gt',date('Y-m-d H:i:s'));
			$where['gp_start_time'] = array('lt',date('Y-m-d H:i:s'));			
		}
		//已结束
		if($params['type'] == 3){
			$where['gp_end_time'] = array('lt',date('Y-m-d H:i:s'));		
		}
		$where['fx_goods.g_status'] = 1;
		$total_results = $this->groupbuy->join('fx_goods on(fx_groupbuy.g_id=fx_goods.g_id)')->where($where)->count();
		//echo  $this->groupbuy->getLastSql();exit;
		$page_start = ($params['page']-1)*$params['pagesize'];
        $g_list = $this->groupbuy->where($where)
							->join('fx_goods on(fx_groupbuy.g_id=fx_goods.g_id)')
                             ->limit($page_start,$params['pagesize'])
							 ->order('gp_create_time desc')
                             ->select();
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');							 
        foreach ($g_list as $key=>$val){
            //已预购数量 = 虚拟数量+实际销售量
            $buy_nums = $val['gp_pre_number'] + $val['gp_now_number'];
            //取出价格关联表 价格阶级
            $ary_range_price = $gp_price->where(array('gp_id'=>$val['gp_id']))->select();
            //定义一个 达到购买量的数组(达到购买量可享受优惠价)
            $current_num_range = 0;
            foreach($ary_range_price as $rp_k=>$rp_v){
                if(($buy_nums >= $rp_v['rgp_num']) && ($rp_v['rgp_num'] > $current_num_range)){
                    //$current_range = $rp_v['rgp_num'];
                    $current_num_range = $rp_v['rgp_num'];
                }
            }
            //dump($ary_relbuy_num);die;
            if($current_num_range > 0) {
                $g_list[$key]['gp_price'] = $gp_price->where( array( 'gp_id'   => $val['gp_id'],
                                                            'rgp_num' => $current_num_range
                ) )->getField( 'rgp_price' );
            }
            $g_list[$key]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$val['g_id']))->order('pdt_sale_price asc')->getField('pdt_sale_price');

            switch($val['gp_tiered_pricing_type']) {
                case 2:
                    $g_list[$key]['gp_price'] = $sale_price*$g_list[$key]['gp_price'];
                    break;
                default:
                    $g_list[$key]['gp_price'] = $sale_price - $g_list[$key]['gp_price'];
                    break;
            }
            $g_list[$key]['gp_price'] || $g_list[$key]['gp_price'] = 0.00;

            $g_list[$key]['gp_now_number'] = $buy_nums;

            $g_list[$key]['gp_picture'] = '/'.ltrim($val['gp_picture'],'/');
            //验证价格
            $i = 0;
            if(!empty($get['startPrice']) && !empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] < $get['startPrice'] || $g_list[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if(!empty($get['startPrice']) && empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] < $get['startPrice']){
                    $i++;
                }
            }
            if(empty($get['startPrice']) && !empty($get['endPrice'])){
                if($g_list[$key]['gp_price'] > $get['endPrice']){
                    $i++;
                }
            }
            if($i != 0){
                unset($g_list[$key]);
            }else{
                //验证区域
               // if(!empty($get['cr_id'])){
                   // $result_show = $gp_city->where(array('cr_id'=>$get['cr_id'],'gp_id'=>$val['gp_id']))->count();
                   // if($result_show == 0){
                      //  unset($g_list[$key]);
                   // }
               // }
            }
            //判断当前团购时间是否开始或已过期
            $now = mktime();
            if(strtotime($val['gp_start_time']) > mktime()){
                //团购未开始
                $g_list[$key]['stat_time'] = '1';
            }elseif((strtotime($val['gp_start_time']) < mktime()) && (strtotime($val['gp_end_time'])< mktime())){
                //团购已结束
                $g_list[$key]['stat_time'] = '2';
            }else{
                if($val['gp_now_number'] >= $val['gp_number']){
                    $g_list[$key]['stat_time'] = '4';//卖光了
                }else{
                    $g_list[$key]['stat_time'] = '3';//正在团购中
                } 
            }
        }				
		$this->result['status'] = true;
		$this->result['info']['lists']['list'] = $g_list;
		$this->result['info']['total_results'] = $total_results;
        return $this->result;
	}
	
	
	//获取商品详情
	public function getBulkDetail($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '获取团购详情失败!';
        $gp_id = $params['gp_id'];
        $groupbuy_cat = M('groupbuy_category',C('DB_PREFIX'),'DB_CUSTOM');
        $gp_price = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM');
        $array_where = array('is_active'=>1,'gp_id'=>$gp_id,'deleted'=>0);
        $data = $this->groupbuy->where($array_where)->find();
        $data['buy_status'] = 1;
        if(empty($data)){
			$this->result['sub_msg'] = '团购商品不存在！';
			return $this->result;
        }
		$data['gp_picture'] = D('QnPic')->picToQn($data['gp_picture']); 
		$data['gp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($data['gp_desc']); 
		$data['gp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($data['gp_mobile_desc']); 	
		
		//商品原价
        $data['cust_price'] = M('goods_info')->where(array('g_id'=>$data['g_id']))->getField('g_price');

        //获取同类目下的其他团购商品信息
		/**
        $likeglist = $groupbuy->where(array('gc_id'=>$data['gc_id'],'is_active'=>1,'deleted'=>0,'gp_id'=>array('neq',$data['gp_id'])))->limit(6)->order('gp_create_time desc')->select();
        $glist = array();
        $count = count($likeglist);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$likeglist[$i*3+$k];
            }
        }**/

        //取出价格阶级
        $rel_bulk_price = $gp_price->where(array('gp_id'=>$data['gp_id']))->order("rgp_num asc")->select();
        $data['rel_bulk_price'] = $rel_bulk_price;
        //目前已参团人数
        $buy_nums = $data['gp_pre_number'] + $data['gp_now_number'];
        $array_f = array();
        $i = -1;
        foreach ($data['rel_bulk_price'] as $rbp_k=>$rbp_v){
            if($buy_nums >= $rbp_v['rgp_num']){
                $i++;
                $array_f[$rbp_v['related_price_id']] = $rbp_v['rgp_num'];
            }
        }
        if(!empty($data['rel_bulk_price']) && $i != -1){
            $data['rel_bulk_price'][$i]['is_on'] = 1;
        }
        if(!empty($array_f)){
            $array_max = new ArrayMax($array_f);
            $rgp_num = $array_max->arrayMax();
            $data['gp_price'] = $gp_price->where(array('gp_id'=>$data['gp_id'],'rgp_num'=>$rgp_num))->getField('rgp_price');
        }
        $data['gp_overdue_start_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_start_time']));
        $data['gp_overdue_end_time'] =  date('Y年m月d号H:i',strtotime($data['gp_overdue_end_time']));
        //判断当前团购数量是否达到上限（总限量）
        if($data['gp_now_number'] == $data['gp_number'] || $data['gp_now_number'] > $data['gp_number']){
            $data['gp_number'] = 0;
            $data['buy_status'] = 0;
        }else{
            $m_id = $params['m_id'];
            if($m_id){
                //目前可以购买的数量
                $thisGpNums = $data['gp_number'] - $data['gp_now_number'];
                //当前会员已购买数量
                //$member_buy_num =  $groupbuy_log->field(array('SUM(num) as buy_nums'))->where(array('m_id'=>$m_id,'gp_id'=>$data['gp_id']))->find();
				$member_buy_num =  M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field(array('SUM(fx_orders_items.oi_nums) as buy_nums'))
				->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
				->where(array('fx_orders.m_id'=>$m_id,'fx_orders_items.fc_id'=>$data['gp_id'],'fx_orders_items.oi_type'=>'5','fx_orders.o_status'=>array('neq',2),'fx_orders_items.oi_refund_status'=>array('not in',array(4,5))))
				->find();
                //如果会员限购数量大于当前会员已购买数量
                if($data['gp_per_number'] > $member_buy_num['buy_nums']){
                    //当前会员最多可以购买的数量
                    $gp_number = $data['gp_per_number'] - $member_buy_num['buy_nums'];
                    //如果会员最多可以购买的数量大于目前库存，将库存赋予会员购买数量
                    if($gp_number > $thisGpNums){
                        $gp_number = $thisGpNums;
                    }
                    //将会员可以购买数量存入gp_number中
                    $data['gp_number'] = $gp_number;
                    $data['buy_status'] = 1;
                }else{
                    //卖光了或购买数量已达上限
                    $data['gp_number'] = 0;
                    $data['buy_status'] = 0;
                }
            }else{
                $data['gp_number'] = $data['gp_per_number'];
                $data['buy_status'] = 1;
            }
        }
        
        //判断当前团购时间是否开始或已过期
        $now = mktime();
        if(strtotime($data['gp_start_time']) > mktime()){
            //团购未开始
            $data['stat_time'] = '1';
        }elseif((strtotime($data['gp_start_time']) < mktime()) && (strtotime($data['gp_end_time'])< mktime())){
            //团购已结束
            $data['stat_time'] = '2';
        }else{
            $data['stat_time'] = '3';//正在团购中
        }
        if($data['is_deposit'] == 0){
            $data['gp_deposit_price'] = 0.00;
        }

		$this->result['status'] = true;
		$this->result['info'] = $data;
        return $this->result;		
	}
	
	
    
}
