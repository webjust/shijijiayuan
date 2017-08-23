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
class ApiSpikeModel extends GyfxModel {
    
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
		 $this->spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');
	}
	
 	//获取秒杀列表
	public function getSpikeList($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '获取秒杀列表失败!';
		//$where = array('sp_status'=>1,
                            // 'sp_start_time'=>array('lt',date('Y-m-d H:i:s')),
                             //'sp_end_time'=>array('gt',date('Y-m-d H:i:s')));
		$where = array('sp_status'=>1);
		if(empty($params['type'])){
			$where['sp_end_time'] = array('gt',date('Y-m-d H:i:s'));
		}
		//还未开始
		if($params['type'] == 1){
			$where['sp_end_time'] = array('gt',date('Y-m-d H:i:s'));
			$where['sp_start_time'] = array('gt',date('Y-m-d H:i:s'));
		}
		//正在进行
		if($params['type'] == 2){
			$where['sp_end_time'] = array('gt',date('Y-m-d H:i:s'));
			$where['sp_start_time'] = array('lt',date('Y-m-d H:i:s'));			
		}
		//已结束
		if($params['type'] == 3){
			$where['sp_end_time'] = array('lt',date('Y-m-d H:i:s'));		
		}		
		$where['fx_goods.g_status'] = 1;
		$total_results = $this->spike->join('fx_goods on(fx_spike.g_id=fx_goods.g_id)')->where($where)->count();
		$page_start = ($params['page']-1)*$params['pagesize'];
        $spikeList = $this->spike->where($where)
							->join('fx_goods on(fx_spike.g_id=fx_goods.g_id)')
                             ->limit($page_start,$params['pagesize'])
							 ->order('sp_create_time desc')
                             ->select();
							 
							 
        foreach ($spikeList as $ky=>$kv){
			//七牛图片显示
			$spikeList[$ky]['sp_picture'] = D('QnPic')->picToQn($kv['sp_picture']); 
			$spikeList[$ky]['cust_price'] = $sale_price = M('goods_products')->where(array('g_id'=>$kv['g_id']))
                ->order('pdt_sale_price asc')->getField('pdt_sale_price');
            //秒杀价格
            switch($kv['sp_tiered_pricing_type']) {
                //直接减优惠金额
                case 1:
                    $pdt_final_price = $sale_price - $kv['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $spikeList[$ky]['sp_price'] = $pdt_final_price;
                    break;
                //设置优惠折扣
                case 2:
                    $pdt_final_price = $sale_price * $kv['sp_price'];
                    $pdt_final_price <= 0 && $pdt_final_price = 0.00;
                    $spikeList[$ky]['sp_price'] = $pdt_final_price;
                    break;
            }
        }					
		$this->result['status'] = true;
		$this->result['info']['lists']['list'] = $spikeList;
		$this->result['info']['total_results'] = $total_results;
        return $this->result;
	}
	
	//获取商品详情
	public function getSpikeDetail($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '获取秒杀详情失败!';
        $int_sp_id = (int)$params['sp_id'];
		//判断秒杀是否已结束
        $btween_time = $this->spike->where(array('sp_id'=>$int_sp_id))->field("sp_start_time,sp_end_time")->find();
        if(strtotime($btween_time['sp_start_time']) > mktime()){
           // $this->error('秒杀未开始',U('Home/Spike'));
            //exit;
        }
        if(strtotime($btween_time['sp_end_time']) < mktime()){
			$this->result['sub_msg'] = '秒杀已结束';
			return $this->result;
        }
        $ary_data = $this->spike->field('gi.g_name,g.g_sn,spc.gc_name,'.C('DB_PREFIX').'spike.*')
		->join(C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."spike.g_id)")
        ->join(C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."spike.g_id)")
        ->join(C('DB_PREFIX')."spike_category as spc on(spc.gc_id=".C('DB_PREFIX')."spike.gc_id)")
        ->where(array('sp_id'=>$int_sp_id))->find();
		if($_SESSION['OSS']['GY_QN_ON'] == '1' ){//七牛图片显示
			$ary_data['sp_picture'] = D('QnPic')->picToQn($ary_data['sp_picture']); 
			$ary_data['sp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['sp_desc']); 
			$ary_data['sp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['sp_mobile_desc']); 
		}
		$sale_price = $ary_data['pdt_sale_price'];
		switch($ary_data['sp_tiered_pricing_type']) {
			//直接减优惠金额
			case 1:
				$pdt_final_price = $sale_price - $ary_data['sp_price'];
				$pdt_final_price <= 0 && $pdt_final_price = 0.00;
				$ary_data['sp_price'] = $pdt_final_price;
				break;
			//设置优惠折扣
			case 2:
				$pdt_final_price = $sale_price * $kv['sp_price'];
				$pdt_final_price <= 0 && $pdt_final_price = 0.00;
				$ary_data['sp_price'] = $pdt_final_price;
				break;
		}
			
        //获取同类秒杀
		/**
        $where['gc_id'] = $ary_data['gc_id'];
        $where['sp_status'] = 1;
        $where['sp_id'] = array('neq',$int_sp_id);
        $likeglist = $this->spike->where($where)->limit(0,6)->order('sp_start_time desc')->select();
        $glist = array();
        $count = count($likeglist);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$likeglist[$i*3+$k];
            }
        }
		**/
        $goods_info = D('Goods')->where(array('g_id'=>$ary_data['g_id']))->count();
        if($goods_info == 0){
			$this->result['sub_msg'] = '该商品不存在';
			return $this->result;
        }
		$this->result['status'] = true;
		$this->result['info'] = $ary_data;
        return $this->result;		
	}
    
}
