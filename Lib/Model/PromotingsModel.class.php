<?php
/**
 * 推广销售数据库交互类
 * 用户中心推广销售相关（
 *
 * @stage Salespromotion
 * @package Action
 * @subpackage Admin
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-09-15
 * @license branches
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class PromotingsModel extends GyfxModel{
	
	private $member_payback;
	public $sub_m_arr = array();
	
	/**
	 * 构造方法
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function __construct() {
		$this->member_payback = M('member_payback',C('DB_PREFIX'),'DB_CUSTOM');//返利表
		$this->member_relation = M('member_relation',C('DB_PREFIX'),'DB_CUSTOM');//推荐位
		$this->goods_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');//商品货号表
		$this->goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');//商品表
		$this->members = M('members',C('DB_PREFIX'),'DB_CUSTOM');//会员表
		$this->members_level = M('members_level',C('DB_PREFIX'),'DB_CUSTOM');//会员等级
		$this->member_sales_set = M('member_sales_set',C('DB_PREFIX'),'DB_CUSTOM');//销售额设定表
		$this->member_payback_statistics = M('member_payback_statistics',C('DB_PREFIX'),'DB_CUSTOM');//分销商返利记录表
		$this->orders = M('orders',C('DB_PREFIX'),'DB_CUSTOM');//订单表
		$this->orders_items = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM');//订单明细表
		$this->mdprr = M('member_differ_price_rebates_record',C('DB_PREFIX'),'DB_CUSTOM');//差价返利记录表
		import('ORG.Util.Page');
	}
	
    public function getList($filter = array(),$start = 0,$limit,&$count = 0){
    	//echo "<pre>";print_r($filter);die();
        if(!isset($filter['m_name']) || empty($filter['m_name'])){
            return 'namenull';//名称为空
        }
        $minfo	= D('Salespromotion')->getMembersData($filter['m_name'],$filed='m_name,m_id');
        if(empty($minfo) || empty($minfo['m_id'])){return false;}
        $m_id = $minfo['m_id'];
        if(empty($m_id)){return 'nomember'; }//没有对应分销商     
        $members = $this->getSuperiorMem($m_id);
        $members	= rtrim($members,',');
        unset($filter['m_name']);
        //1,货品列表,gid pdtid,成本价,销售价
        //2,有销售权限的货品列表,取到当前会员的进货价列表,合并,与现有的返利列表合并,得到
        if(!$members) return 'nosupper';//无上级
        $list = array();
        try{
			$filter['mid']= $members;
            $pdt_info = $this->getPageList($filter ,$start ,$limit);
            $pdt_pageinfo = $pdt_info['pageinfo'];
            $pdt_arr = $pdt_info['goods_list'];
            if(empty($pdt_arr)){return 'nogoods';}//没有在架商品
            foreach($pdt_arr as $pdt){
                $g_id_arr[] = $pdt['g_id'];
            }
            $g_ids = implode(',',$g_id_arr);
            $goods_list = $this->getGoodsInfo($g_ids);
            $pdt_list = array();
            foreach($pdt_arr as $pdt){
                $pdt['g_name'] = $goods_list[$pdt['g_id']];
                $pdt_list[] = $pdt;
            }
            $m_info = $this->getMnameByMids($members);
            $m_o_id_arr = explode(',',$members);//有顺序
            //dump($m_o_id_arr);die();
            #krsort($m_o_id_arr);//从高到低#debug
            //会员等级折扣
            $mdis_list = $this->getMemberDiscount($members);
            $m_list = array();
            foreach($m_o_id_arr as $m_id){
                $m_list[$m_id] = $m_info[$m_id];
            }
            $mdis =  $this->getMemberDiscount($m_id);//本人折扣
            $list['m_info'] = $m_list;
            $list['pdt_info'] = $pdt_list;
            $list['mdis_list'] = $mdis_list;
            $list['mdis'] = $mdis[$m_id];
            $m_o_auth = array();
            //父id 所有授权商品列表
            foreach($m_o_id_arr as $m_o_id){
                $auth_g_list = array();
                $g_list = array();
                $auth_g_list = $this->getMemAuthGid($m_o_id);//授权商品列表
                if(!empty($auth_g_list) && $auth_g_list!='all')
                {
                    foreach($auth_g_list as $g_id)
                    {
                        $g_list[$g_id]['g_id'] = $g_id;
                    }
                    $m_o_auth[$m_o_id] = $g_list;
                }
                if($auth_g_list == 'all'){
                    foreach($pdt_list as $pdt){
                        $m_o_auth[$m_o_id][$pdt['g_id']]['g_id'] =$pdt['g_id'];
                    }
                }
            }
            $paybacks = $this->getPaybackList($m_id,$members);//已存私返利记录
           // echo "<pre>";print_r($paybacks);exit;
            if(!empty($paybacks))//现有返利
            {
                foreach($paybacks as $pay)
                {
                    if(!empty($m_o_auth[$pay['m_o_id']]))
                    {
                        $m_o_auth[$pay['m_o_id']][$pay['g_id']][$pay['pdt_id']][$pay['m_id']]['m_p_amount'] = $pay['m_p_amount'];
                        $m_o_auth[$pay['m_o_id']][$pay['g_id']][$pay['pdt_id']][$pay['m_id']]['m_p_id'] = $pay['m_p_id'];
						$m_o_auth[$pay['m_o_id']][$pay['g_id']][$pay['pdt_id']][$pay['m_id']]['m_id'] = $pay['m_id'];
                    }
                }
            }
            $list['m_o_auth'] = $m_o_auth;
            return array('pageinfo'=>$pdt_pageinfo,'list'=>$list);
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            return array();
        }
    }
    
    /**
	 * 获得返利信息
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-31
	 */    
    public function getPaybackList($m_id,$members)
    {
        $paybacks = $this->member_payback->where(array('m_o_id'=>array('in',$members)))->select();
        return $paybacks;
    }
    
    /**
	 * 获得授权商品列表
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-31
	 */  
    public function getMemAuthGid($m_o_id){
    	$AlSeting = $this->getCfgByModule('GY_AUTHORIZE_LINE');
		if (false == $AlSeting || empty($AlSeting) || $AlSeting['GLOBAL'] == 0) {
			//如果全局开关设置不存在，或者关闭状态，则全部返回true
			return 'all';
		}
		//$m_o_id = 16;
		$authorize = M('related_authorize_member',C('DB_PREFIX'),'DB_CUSTOM')->join("right join fx_related_authorize ON fx_related_authorize.al_id = fx_related_authorize_member.al_id ")->where(array('m_id' => $m_o_id))->select();
		//前台应用，没有授权线，允许购买所有商品
		if(empty($authorize)){
			return 'all';
		}
		$str_cates = '';
		$str_brands = '';
		foreach($authorize as $sub){
			if(!empty($sub['ra_gb_id'])){
				$str_brands[] = $sub['ra_gb_id'];
			}
			if(!empty($sub['ra_gc_id'])){
				$str_cates[] = $sub['ra_gc_id'];
			}
		}
		$str_cates = implode(',',$str_cates);
		if($str_cates){
			$goods_cate = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('gc_id'=>array('in',$str_cates)))->select();
		}
		$str_brands = implode(',',$str_brands);
		if($str_brands){
			$goods_brand = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array('gc_id'=>array('in',$str_brands)))->select();
		}	
		$g_ids = array();
		foreach($goods_cate as $g){
			$g_ids[] = $g['g_id'];
		}
		foreach($goods_brand as $b){
			$g_ids[] = $b['g_id'];
		}
		$g_ids = array_unique($g_ids);
		return $g_ids;
    }
    
    /**
     * 从系统配置表中取出模块相关配置
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-1-9
     * @return array
     * @example array('GY_SMTP_AUTH'=>1,'GY_SMTP_FROM=>'guanyitest@163.com');
     */
    public function getCfgByModule($module_name){
        $result = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM')->field(array('sc_key','sc_value'))->where(array('sc_module'=>$module_name))->select();
        $return = array();
        foreach($result as $v){
            $return[$v['sc_key']] = $v['sc_value'];
        }
        return $return;
    }
    
    /**
	 * 获得获取信息
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-31
	 */
    public function getMnameByMids($m_ids = '')
    {
        if(empty($m_ids)) {return array();}
        $m_info = $this->members->where(array('m_id'=>array('in',$m_ids)))->field('m_id,m_name')->select();
        $m_list = array();
        foreach($m_info as $m)
        {
            $m_list[$m['m_id']] = $m['m_name'];
        }
        return $m_list;
    }
    
    public function getMemberDiscount($m_ids = '')
    {
        if(empty($m_ids)) {return array();}
        $mldis = $this->members_level->field('ml_id,ml_discount')->where(array('ml_status'=>1))->select();
        $ml_list = array();
        foreach($mldis as $ml)
        {
            $ml_list[$ml['ml_id']] = $ml['ml_discount'];
        }
        $m_arr = $this->members->field('ml_id,m_id')->where(array('m_id'=>array('in',$m_ids)))->select();
        $mdis_list = array();
        foreach($m_arr as $m)
        {
            $mdis_list[$m['m_id']] = $ml_list[$m['ml_id']];
        }
        return $mdis_list;
    }
    
 	/**
	 * 获得商品信息
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-31
	 */
    public function getGoodsInfo($g_ids='')
    {
        $goods = $this->goods_info->field('g_id,g_name')->where(array('g_id'=>array('in',$g_ids)))->select();
        $goods_list = array();
        foreach($goods as $good)
        {
            $goods_list[$good['g_id']] = $good['g_name'];
        }
        return $goods_list;
    }
    
    /**
	 * 获得货品
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-31
	 */
    public function getPageList($filter = array(),$start = 0,$limit = 0,&$count=0){
    	//查询条件
    	$ary_where = array();
        $ary_where['g.g_on_sale'] = 1;
        $ary_where['g.g_status'] = 1;
        $ary_where['fx_goods_products.pdt_stock'] = array('gt',0);
        $ary_where['fx_goods_products.pdt_status'] = 1;
        if (!empty($filter['pdt_sn'])) {//货号,精确搜索
			$ary_where['fx_goods_products.pdt_sn'] = $filter['pdt_sn'];
		}
		if (!empty($filter['g_name'])) {//商品名称,模糊搜索
			$ary_where['gi.g_name'] = array('like','%'.$filter['g_name'].'%');;
		}
		$ary_tmp_num = $this->goods_products
				->join('fx_goods as g on g.g_id=fx_goods_products.g_id')
				->join('fx_goods_info as gi on gi.g_id=fx_goods_products.g_id')
			  	->where($ary_where)
			  	->count();
		if ($start > 0 && $limit > 0){
			$str_limit = (intval($start)-1)*intval($limit).','.intval($limit);
		}
		//var_dump($filter,$start,$limit);die();
		$obj_page = new Page($ary_tmp_num, $limit);
		$page = $obj_page->show();
		$ary_res['pageinfo'] = $page;
		$goods_list = $this->goods_products
						->join('fx_goods as g on g.g_id=fx_goods_products.g_id')
						->join('fx_goods_info as gi on gi.g_id=fx_goods_products.g_id')
					  	->where($ary_where)
					  	->limit($str_limit)
					  	->field('fx_goods_products.pdt_id,fx_goods_products.g_id,fx_goods_products.pdt_sn,fx_goods_products.pdt_sale_price,fx_goods_products.pdt_cost_price,gi.g_name')
					  	->select();
		$obj_price = new ProPrice();
		$member = session('Members');
		foreach($goods_list as $key=>$value){
			if(isset($filter['mid']) && $filter['mid'] >0){
				$ary_price = $obj_price->getPriceInfo($value['pdt_id'],$filter['mid']); 
				$goods_list[$key]['pdt_price'] = $ary_price['pdt_price'];
			}else{
				 $goods_list[$key]['pdt_price'] = $value['pdt_sale_price'];
			}
		}
		
        return array('pageinfo'=>$page,'goods_list'=>$goods_list);
    }
    
    /**
	 * 获得上级分销商
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-30
	 */
    public function getSuperiorMem($m_id = 0)
    {
        if(empty($m_id)) return false;
        $mr_path = $this->member_relation->where(array('m_id'=>$m_id))->getField('mr_path'); 
        //echo $this->member_relation->getLastSql();exit;
		if(empty($mr_path)){return false;}
        return $mr_path;
    }
    
    /**
     * 
     * 根据返利设定生成返利报表
     * @author Wangguibin
     * @param array $ary_post
     * @date 2014-01-03
     */
    public function getPBSList($filter = array(),$start = 0,$limit = 20,&$count=0)
    {
        if(!isset($filter['m_name']) || empty($filter['m_name']))
        {
            return 'namenull';//名称为空
        }
        $m_id = $this->getMidByMname($filter['m_name']);
        if(empty($m_id))
        {
            return 'nomember'; //没有对应分销商
        }
        unset($filter['m_name']);
        $filter['m_id'] = $m_id;
        $payback_statements = $this->getPBSListModel($filter,$start,$limit,$count);
        //echo "<pre>";print_r($payback_statements);exit;	#debug
        if(!$payback_statements){
        	return false;
        }
        return $payback_statements;
    }
    
    /**
     * 
     * 根据返利设定生成返利报表
     * @author Wangguibin
     * @param array $ary_post
     * @date 2014-01-03
     */
     public function getPBSListModel($filter = array(),$start = 0,$limit = 20,&$count=0){
        if(empty($filter['m_id'])){return array();}
        $this->getSubMem($filter['m_id']);//子 mid
        //$member_relation_mid = $this->getSubMem1($filter['m_id']);
        $m_id = $filter['m_id'];
        //得到下级分销商字符串格式,包括自己,eg: 3,4,5,6
		//$m_sub_ids_str = $this -> getSubRelationIdsByMid($m_id, FALSE);
		//$m_sub_ids_arr = $this -> getSubRelationIdsByMid($m_id, TRUE);
		//modify by zhangjiasuo 2015-09-17
		if(is_array($this->sub_m_arr) && $this->sub_m_arr!=''){
			$sub_id_strs = implode(",",$this->sub_m_arr[$filter['m_id']]);//不含自己的mid
		}
		$m_sub_ids_str = $filter['m_id'].','.$sub_id_strs;
		$m_sub_ids_arr = explode(",",$m_sub_ids_str);
		//modify by zhangjiasuo 2015-09-17
        $searchdate =array();
		// 根据年月,生成这一个月的起始时间eg:2012-04  => Array ( [firstday] => 1333209600 [lastday] => 1335801599 ) 2012-04-01 00:00:002012-04-30 23:59:59
       //dump($filter);die();
        if(!empty($filter['year']) && !empty($filter['month'])){
            $searchdate = $this->mFristAndLast($filter['year'],$filter['month']);
        }
        //订单报表,得到 分销商名称,订单总数量,订单总金额
        $order_info = $this->getOrdersReport2($searchdate, $m_sub_ids_str);
        #转换用户名

    	// 将m_id转换为m_name
    	$m_name_array = array();
		$m_name_ret = $this->members->where(array('m_id'=>array('in',$m_sub_ids_str)))->group('m_id')->field('m_name,m_id')->select();
		foreach ($m_name_ret as $item) {
			$m_name_array[$item['m_id']] = $item['m_name'];
		}
        //目标销售额
        $total_mss_sales = $this->getSalesReport2($filter, $m_sub_ids_str);	#debug
        //dump($total_mss_sales);
		// 退款总金额
		$return_order_amount = $this->getReturnOrderAmount1($searchdate, $m_sub_ids_str);
		//dump($return_order_amount);die();
        //返利
		$payback = $this->getPaybackReport3($searchdate, $m_sub_ids_str, $m_id);
        $report = array();
        foreach($m_sub_ids_arr as $m_p_id){
       		if ($m_p_id == $m_id) {continue;}
            $report[$m_p_id] = $order_info[$m_p_id];
            $report[$m_p_id]['m_id'] = $m_p_id;
            $report[$m_p_id]['total_mss_sales'] = $total_mss_sales[$m_p_id];//目标销售总金额
            $report[$m_p_id]['m_name'] = $m_name_array[$m_p_id];//分销商名称
			$report[$m_p_id]['return_order_amount'] = number_format($return_order_amount[$m_p_id], 3);//退款总金额
            $report[$m_p_id]['m_p_amount'] = number_format($payback[$m_p_id], 3);	#返利金额
        }
        //return $report;
        if (!is_array($payback)) {
        	$payback = array();
        }
        $total_return_amount = number_format(array_sum($payback), 3);
        return array('report' => $report, 'total_return_amount' => $total_return_amount);
    }
    
    /* 计算下线分销商返利给自己的金额
	 * wangguibin@guanyisoft.com
	 * @param $searchdate array search conditions
	 * @param $m_sub_ids_str string member_payback_statistics.m_o_id
	 * @param $m_id 自己的m_id,对应members.m_id,member_payback_statistics.m_o_id
	 * @return 如果SQL语句执行成功,则返回array(),否则返回false
	 */
	public function getPaybackReport3($searchdate, $m_sub_ids_str, $m_id) {
		$ary_data = array();
		$ary_where = array();
		$ary_where['fx_member_payback_statistics.m_id'] = array('in',$m_sub_ids_str);
		$ary_where['fx_member_payback_statistics.m_o_id'] = $m_id;
		if (!empty($searchdate)) {
			$ary_where['_string'] = ' UNIX_TIMESTAMP(o.o_create_time) BETWEEN '.'"'.$searchdate['firstday'].'"'.' AND '.'"'.$searchdate['lastday'].'"';
		}
		$ary_where['o.o_status'] = 4;
		$payback_arr = $this->member_payback_statistics
		->where($ary_where)
		->join('fx_orders_items oi ON oi.oi_id = fx_member_payback_statistics.oi_id ')
		->join('fx_orders o on o.o_id = oi.o_id')
		->field('fx_member_payback_statistics.m_o_id,fx_member_payback_statistics.m_id,SUM(fx_member_payback_statistics.mps_payback_amount) AS total_amount')->group('fx_member_payback_statistics.m_id')->select();
		//dump($this->member_payback_statistics->getLastSql());die();
		if ($payback_arr == false) { return false; }
		foreach ($payback_arr as $item) {
			$ary_data[$item['m_id']] = $item['total_amount'];
		}
		return $ary_data;
	}
	
    /**
	 * 得到退款总金额
	 * wangguibin@guanyisoft.com
	 * @param $searchdate array() 查询条件
	 * @param $m_sub_ids array() 需要查询的m_id数组
	 * @return array()
	 */
	function getReturnOrderAmount1($searchdate, $m_sub_ids_str){
		$ret_arr = array();
		$ary_where = array();
		$search_where['fx_orders_refunds.or_processing_status'] = 1;
		$ary_where['fx_orders_items.oi_refund_status'] = array('in','2,3,4,5');
		$ary_where['fx_orders.m_id'] = array('in',$m_sub_ids_str);
		if (!empty($searchdate)) {
			$ary_where['_string'] = ' UNIX_TIMESTAMP(fx_orders.o_create_time) BETWEEN '.'"'.$searchdate['firstday'].'"'.' AND '.'"'.$searchdate['lastday'].'"';
		}
		$ary_where['fx_orders.o_status'] = 4;
		$order_arr = $this->orders_items->join('fx_orders on fx_orders.o_id=fx_orders_items.o_id')
		->where($ary_where)->group('m_id')->order('fx_orders.o_id ASC')
		->field('fx_orders.m_id,SUM(fx_orders_items.oi_price*fx_orders_items.oi_nums) AS return_total_amount')->select();
		if ($order_arr == false) {return false;}
		foreach ($order_arr as $item) {
			$ret_arr[$item['m_id']] = $item['return_total_amount'];
		}
		return $ret_arr;
	}
	
   	/**
	 * 得到目标销售额
	 * wangguibin@guanyisoft.com
	 * @param $filter array() 查询条件
	 * @param $m_sub_ids_str array() 需要查询的m_id数组
	 * @return array()
	 */
	function getSalesReport2($filter, $m_sub_ids_str){
		//dump($filter);die();
		$ary_where = array();
		$ary_where['m_id'] = array('in',$m_sub_ids_str);
		if (!empty($filter)) {
			$times = $this->mFristAndLast($filter['year'],$filter['month']);
			$start_date = date('Y-m-d H:i:s',$times['firstday']);
			$end_date = date('Y-m-d H:i:s',$times['lastday']);
			$ary_where['mss_time_begin'] = array('egt',$start_date);
			$ary_where['mss_time_end'] = array('elt',$end_date);
		}
		$ret_arr = array();
		$mss_info = $this->member_sales_set->field('m_id,mss_sales')->where($ary_where)->group('m_id')->order('mss_id ASC')->select();
		//dump($this->member_sales_set->getLastSql());die();
		foreach ($mss_info as $item) {
			$ret_arr[$item['m_id']] = $item['mss_sales'];
		}
		return $ret_arr;
	}
		
	/**
	 * 得到分销商名称,订单总数,订单总金额  返回数组
	 * wangguibin@guanyisoft.com
	 * @param $searchdate array() 查询条件
	 * @param $m_sub_ids_str  需要查询的m_id字符串
	 * 
	 */
	function getOrdersReport2($searchdate, $m_sub_ids_str){
		$ret_arr = array();
		$ary_where = array();
		$ary_where['m_id'] = array('in',$m_sub_ids_str);
		//$ary_where['pay_status'] = '1';
		$ary_where['o_status'] = 4;
		if (!empty($searchdate)) {
			$ary_where['_string'] = ' UNIX_TIMESTAMP(o_create_time) BETWEEN '.'"'.$searchdate['firstday'].'"'.' AND '.'"'.$searchdate['lastday'].'"';
		}	
		$order_arr = $this->orders->field('m_id,COUNT(o_id) AS cnt,SUM(o_goods_all_price) AS total_goods_amount,SUM(o_all_price) AS total_amount')->where($ary_where)->group('m_id')->order('o_id desc')->select();
		//dump($order_arr);die();
		if ($order_arr == false) {return false;}
		foreach ($order_arr as $item) {
			$ret_arr[$item['m_id']]['order_num'] = $item['cnt'];//订单数量
			$ret_arr[$item['m_id']]['total_amount'] = $item['total_amount'];//订单总金额
			$ret_arr[$item['m_id']]['total_goods_amount'] = $item['total_goods_amount'];//订单总金额
		}
		return $ret_arr;
	}
	
    public function mFristAndLast($y="",$m=""){
         if($y=="") $y=date("Y");
         if($m=="") $m=date("m");
         $m=sprintf("%02d",intval($m));
         $y=str_pad(intval($y),4,"0",STR_PAD_RIGHT);
         
         $m>12||$m<1?$m=1:$m=$m;
         $firstday=strtotime($y.$m."01000000");
         $firstdaystr=date("Y-m-01",$firstday);
         $lastday = strtotime(date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day")));
         return array("firstday"=>$firstday,"lastday"=>$lastday);
    } 
    
   	/**
	 * 得到所有下级的m_id(member_relation.m_id)并返回字符串格式
	 * wangguibin@guanyisoft.com
	 * @param $m_id int member_relation.m_id
	 * @param $ret_array boolean 是否返回数组格式,默认为true
	 * @param $contain_self 返回的数据是否包括自己,默认true,包括自己
	 * @return 根据$ret_array 参数返回
	 * 如果$ret_array == true ,返回结果为:array([0] => 3,[1] => 4, [2] => 5, [3] => 6);
	 * 如果$ret_array == false,返回结果为 3,4,5,6
	 */
	public function getSubRelationIdsByMid($m_id, $ret_array = true, $contain_self = true) {
		$ary_where = array();
		if ($contain_self) {
			$ary_where['_string'] = ' mr_path like '.'"%'.$m_id.'%"'.' or m_id='.$m_id;
		}else{
			$ary_where['_string'] = ' mr_path like '.'"%'.$m_id.'%"';
		}
		$mid_str_arr = $this->member_relation->where($ary_where)->field('GROUP_CONCAT(m_id) AS mid_str')->find();
		if ($ret_array) {
			return explode(',', $mid_str_arr['mid_str']);
		}else {
			return $mid_str_arr['mid_str'];
		}
	}
	
    //下级分分销商 ,包含自己
    public function getSubMem($m_id)
    {
        $sub_m_list = $this->member_relation->field('m_id')->where(array('mr_p_id'=>$m_id))->select();
        if(is_array($sub_m_list) && !empty($sub_m_list))
        {
            foreach($sub_m_list as $sub_m)//同级
            {
                $this->sub_m_arr[$m_id][$sub_m['m_id']]=$sub_m['m_id'];//去重
                if(!empty($sub_m['mr_path']))
                {
                    $tmp_arr = explode(',',$sub_m['mr_path']);
                    foreach($tmp_arr as $tmp_m_id)
                    {
                        $this->sub_m_arr[$tmp_m_id][$sub_m['m_id']]=$sub_m['m_id'];
                    }
                }
                $this->getSubMem($sub_m['m_id']);//下级
            }
        }
    }
    
    /**
	 * 通过m_id 取member_relation表中关联的子 m_id
	 * @param $m_id int m_id 
	 * @return array
	 */
	public function getSubMem1($m_id) {
		$subMem = array();
		$sub_m_list = $this->member_relation->where(array('mr_p_id'=>$m_id))->field('m_id')->select();
		if (is_array($sub_m_list) && !empty($sub_m_list)) {
			foreach ($sub_m_list as $sub_m) {
				$subMem[$sub_m['m_id']] = $this->getSubMem1($sub_m['m_id']);
			}
		}
		return $subMem;
	}
	
    //获取会员ID信息
    public function getMidByMname($m_name = '')
    {
        if(empty($m_name)) return false;
        return $this->members->where(array('m_name'=>$m_name))->getField('m_id');
    }
    
    /**
	 * 获取返利报表
	 * @author Wangguibin@guanyisoft.com
	 * @param array $ary_filter
	 * @date 2014-01-03
	 */
	public function getDifferPriceRebatesReport($ary_filter) {
		$ary_result	= array('success'=>1,'err_msg'=>'','err_code'=>'','data'=>array());
		try{
			$ary_param = array();	
			if(!empty($ary_filter['mdprr_pm_id'])) {
				$ary_param['mdprr_pm_id']	= $ary_filter['mdprr_pm_id'];
			}
			if(!empty($ary_filter['g_sn'])) {
				$ary_param['g_sn']	= $ary_filter['g_sn'];
			}
			if(!empty($ary_filter['pdt_sn'])) {
				$ary_param['pdt_sn']	= $ary_filter['pdt_sn'];
			}
			if(!empty($ary_filter['mdprr_is_unusual'])) {
				$ary_param['mdprr_is_unusual']	= $ary_filter['mdprr_is_unusual'];
			}
			if(!empty($ary_filter['o_id'])) {
				$ary_param['o_id']	= $ary_filter['o_id'];
			}
			if(!empty($ary_filter['mdprr_start_time'])) {
				$ary_param['mdprr_start_time']	= $ary_filter['mdprr_start_time'];
			}
			if(!empty($ary_filter['mdprr_end_time'])) {
				$ary_param['mdprr_end_time']	= $ary_filter['mdprr_end_time'];
			}	
			//分页（报表展示页不适用分页）
			if(isset($ary_filter['is_limit']) && $ary_filter['is_limit'] == 1) {
				$ary_filter['page']	= $ary_filter['page'] ? (int)$ary_filter['page'] : 1;
				$ary_filter['pagesize']	= $ary_filter['pagesize'] ? (int)$ary_filter['pagesize'] : 20;
				$str_limit	= (($ary_filter['page'] - 1)*$ary_filter['pagesize']).','.$ary_filter['pagesize'];
			}		
			$ary_mdprr_res = $this->mdprr->where($ary_param)->order('mdprr_create_time desc')->limit($str_limit)->select();
			if(!$ary_mdprr_res) {
				throw new Exception('查询返利报表时遇到错误！', 86001);
			}
			$ary_result['data']	= $ary_mdprr_res;
		} catch (Exception $e) {
			$ary_result['success']	= 0;
			$ary_result['err_msg']	= $e->getMessage();
			$ary_result['err_code']	= $e->getCode();
		}
		return $ary_result;
	}
	
    
    /**
     * @商品返利设定
     * @return json
     * @author wangguibin@guanyisoft.com
     * @modify 2014-01-03
     */
    public function setGoodsPayback($ary_post){
    	$ary_data = array('m_id' => intval($ary_post['m_id']),
    					'm_o_id' => intval($ary_post['m_o_id']),
    					'g_id'   => intval($ary_post['g_id']),
    					'pdt_id' => intval($ary_post['pdt_id']),
    					'm_p_id' => intval($ary_post['m_p_id']),
    					'm_p_amount'=> floatval($ary_post['m_p_amount']));
    	$payback = false;
    	$payback = $this->setGoodsPaybackInfo($ary_data);
        if(!$payback){
        	return false;
        }
        return $payback;
    }
    
    /**
     * @商品返利设定
     * @return array
     * @author wangguibin@guanyisoft.com
     * @modify 2014-01-03
     */
    public function setGoodsPaybackInfo($ary_post){
    	#为空时返回 false
    	if(empty($ary_post)){
    		return false;
    	}
    	$new_arr = array();
		$ary_post['m_p_amount'] = empty($ary_post['m_p_amount'])?0:number_format($ary_post['m_p_amount'],3);
		#更新
		$rs_data = $this->getPaybackDataByArr($ary_post);
		$data = false;
		if($rs_data['m_p_id']){
			$data = $this->member_payback->where(array('m_p_id'=>$rs_data['m_p_id']))->data(array('m_p_amount'=>$ary_post['m_p_amount']))->save();
    	}else{
    		#新增
    		$data = $this->member_payback->where(array('m_p_id'=>$rs_data['m_p_id']))
    		->data(array('m_p_amount'=>$ary_post['m_p_amount'],'m_id'=>$ary_post['m_id'],'m_o_id'=>$ary_post['m_o_id'],'g_id'=>$ary_post['g_id'],'pdt_id'=>$ary_post['pdt_id']))
    		->add();
    	}
    	if($data){
    		return $ary_post;
    	}else{
    		return false;
    	}
    }
    
   	/**
     * @根据m_id,m_o_id,pdt_id获取商品返利设定单条记录信息
     * 
     * @author wangguibin@guanyisoft.com
     * @modify 2014-01-03
     */
    public function getPaybackDataByArr($ary_post){
    	$ary_data = false;
    	if(!empty($ary_post['m_id']) && !empty($ary_post['pdt_id'])){
    		$ary_data = $this->member_payback->where(array('m_id'=>$ary_post['m_id'],'g_id'=>$ary_post['g_id'],'m_o_id'=>$ary_post['m_o_id'],'pdt_id'=>$ary_post['pdt_id']))->find();
    	}
    	$ary_data = $this->member_payback->where(array('m_id'=>$ary_post['m_id'],'g_id'=>$ary_post['g_id'],'m_o_id'=>$ary_post['m_o_id'],'pdt_id'=>$ary_post['pdt_id']))->find();
    	if(false == $ary_data){
    		return false;
    	}
    	return $ary_data;
    }
    
    /**
     * @分销商层级关系管理 显示页
     * 
     * @return mixed array
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
    public function showMemberRelation(){
    	$ary_data	= array();
    	return $ary_data;
    }
    /**
     * @销售额设定 列表页
     * 
     * @return mixed array
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
	public function showSalesSetList(){
		$ary_data	= array();
		$m_name	= $_POST['m_name'];
		$ary_params	= array();
    	$page	= 0;
    	# 列表页每页要显示的条数
		$pagesize=20;
		
    	# 当前要展示的页面，根据post参数来，如果设置请求页码，则显示第一页
		if(isset($_POST['page'])){
			$page	= $_POST['page'];
		}else{
			$page	= 1;
		}
		
		# 开始查询的行
		$start	=($page-1)*$pagesize;
		# 排序
		$ary_params	= array('page'		=> $page,
							'pagesize'	=> $pagesize);
		# 处理查询条件
		# 如果设置了搜索条件，简单搜索
    	if($m_name){
			$ary_params['m_name']	= $m_name;
			$filter['m_name']	= $m_name;
		}
		#print_r($ary_params);exit;
		$data	= L('model.payback')->getSalesSet($ary_params);
		#echo "<pre>";print_r($ary_data);exit;
		
		return $ary_data	= array('list'	=> $data['list'],
									'ct'	=> $data['ct'],
									'params'	=> $ary_params,
									'fileter'	=> $filter);
		#$this->obj_view->showSalesSetList($ary_data,$ary_params,$filter);
	}
	/**
     * @销售额设定 显示添加页
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
	public function addSalesSetForm(){
		
	}
	
	/**
     * @销售额设定 保存添加
     * 
     * @return string 成功：success,失败：error
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
	public function saveSalesSet($postary){
		$msg		= '';
    	$members	= N('Member')->getMembersData($postary['m_name']);
    	$save		= false;
    	$m_id		= 0;
    	
    	if($members['m_id'] > 0){
    		$m_id = $members['m_id'];
    	}
    	$year	= $postary['mss_year'];
    	$month	= $postary['mss_month'];
    	#年份处理，截取大于4位数的
	    if(strlen($year) > 4){
	    	$year	= substr($year,0,4);
	    }
		if(!is_numeric($year)){
    		$year	= '0000';
		}
		#月份处理，一位数的前面补0
    	if(strlen($month) > 2){
    		$month	= substr($month,0,2);
    	}
    	$month	= intval($month);
    	if($month < 1 || $month > 12){
    		$month	= '00';
    	}elseif(strlen($month) == 1){
    		$month	= '0'.$month;
    	}
    	
    	$mss_time	= $year.'-'.$month;
    	
    	$ary_params	= array('m_id'		=> $m_id,
    						'mss_time'	=> $mss_time,
    						'mss_sales'	=> $postary['mss_sales']);
    	
    	$save	= L('model.payback')->saveSalesSet($ary_params);
    	if(false == $save){
    		$msg	= 'error';
    	}else{
    		$msg	= 'success';
    	}
    	return $msg;
	}
	/**
     * @销售额设定 显示编辑页
     * 
     * @return mixed array
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
	public function showSalesSetEditForm($mss_id){
		$ary_data	= array();
		if(!$mss_id){
			return $ary_data;
			exit;
		}
		$ary_data	= N('salescount')->getSalesSetOne($mss_id);
		$mss_time	= explode('-',$ary_data['mss_time']);
		
		$ary_data['year']	= $mss_time[0];
		$ary_data['month']	= $mss_time[1];
		return $ary_data;
	}
	/**
     * @销售额设定 保存编辑
     * 
     * @return mixed array
     * 	
     * @author Jimmy
     * @version code661
     * @since stage 1.0
     * @modify 2012-02-27
     */
	public function saveSalesSetEdit($postary){
		$mss_id		= intval($postary['mss_id']);
		if($mss_id < 1){
			return 'error';
			exit;
		}
    	$members	= N('Member')->getMembersData($postary['m_name']);
    	$save		= false;
    	$m_id		= 0;
    	
    	if($members['m_id'] > 0){
    		$m_id = $members['m_id'];
    	}
    	$year	= $postary['mss_year'];
    	$month	= $postary['mss_month'];
    	#年份处理，截取大于4位数的
	    if(strlen($year) > 4){
	    	$year	= substr($year,0,4);
	    }
		if(!is_numeric($year)){
    		$year	= '0000';
		}
		#月份处理，一位数的前面补0
    	if(strlen($month) > 2){
    		$month	= substr($month,0,2);
    	}
    	$month	= intval($month);
    	if($month < 1 || $month > 12){
    		$month	= '00';
    	}elseif(strlen($month) == 1){
    		$month	= '0'.$month;
    	}
    	
    	$mss_time	= $year.'-'.$month;
    	
    	$sql_ary_params	= array('mss_id'	=> $mss_id,
    							'm_id'		=> $m_id,
    							'mss_time'	=> $mss_time,
    							'mss_sales'	=> $postary['mss_sales']);
    	
    	$str_sql	= "UPDATE member_sales_set SET 
    	m_id = :m_id,
    	mss_time = :mss_time,
    	mss_sales = :mss_sales 
    	WHERE mss_id = :mss_id";
    	$save	= DB()->query($str_sql,$sql_ary_params);
    	$msg	= '';
    	if(false == $save){
    		$msg	= 'error';
    	}else{
    		$msg	= 'success';
    	}
    	return $msg;
	}
	
    /**
     * 差价返利数据处理
     * @author Jerry
     * @param array $ary_data 返利报表数据
     * @date 2012-10-13
     */
	public function createDifferPriceRebatesReport($ary_data) {
		$ary_rebates_data	= array(
    		'theory_rebates_total_amount'	=> 0.000,
    		'actual_rebates_total_amount'	=> 0.000,
    		'unusual_num'	=>	0
    	);
		if(is_array($ary_data) && !empty($ary_data)) {
    		//遍历返利记录去除不需要返利的记录（未支付，已退款，已退货，已作废）
    		foreach ($ary_data as &$ary_rebates) {
    			$ary_rebates['mdprr_memo']	= '';
    			//判断订单状态
    			$ary_order = $this->orders->where(array('o_id'=>$ary_rebates['o_id']))->field('`o_pay_status`,`o_ship_status`,`o_status`')->find();
    			if(is_array($ary_order) && !empty($ary_order)) {
	    			//订单作废或者订单暂停返利金额清零
	    			if($ary_order['o_status'] != 2 && $ary_order['o_status'] != 4) {
	    				//订单为支付未发货的返利清零
	    				if($ary_order['o_pay_status'] == 1) {
	    					//发货的订单才可参与返利
		    				if($ary_order['o_ship_status'] == 1 || $ary_order['o_ship_status'] == 5) {
		    					//判断订单明细售后
		    					$ary_oi_data	= $this->orders_items->field('oi_refund_status')->where(array('oi_id'=>$ary_rebates['oi_id']))->find();
		    					if(is_array($ary_oi_data) && !empty($ary_oi_data)) {
		    						//判断售后状态
		    						if($ary_oi_data['oi_refund_status'] != 1 && $ary_oi_data['oi_refund_status'] != 6) {
		    							$ary_rebates['mdprr_theory_rebates_amount']	= 0.000;
					    				$ary_rebates['mdprr_actual_rebates_amount']	= 0.000;
				    					$ary_rebates['mdprr_memo']	= '该明细已退款/退货无返利';
		    						} else {
		    							//可以参与返利计算的数据
						    			$ary_rebates_data['theory_rebates_total_amount']	+= $ary_rebates['mdprr_theory_rebates_amount'];
						    			$ary_rebates_data['actual_rebates_total_amount']	+= $ary_rebates['mdprr_actual_rebates_amount'];
		    						}
		    					} else {
		    						//订单明细丢失无返利
		    						$ary_rebates['mdprr_actual_rebates_amount']	= 0.000;
				    				$ary_rebates['mdprr_is_unusual']	= 20;
				    				$ary_rebates['mdprr_memo']	= '订单数据丢失无返利';
		    					}
		    				} else {
			    				$ary_rebates['mdprr_theory_rebates_amount']	= 0.000;
			    				$ary_rebates['mdprr_actual_rebates_amount']	= 0.000;
		    					$ary_rebates['mdprr_memo']	= '订单未发货无返利';
		    				}
	    				} else {
		    				$ary_rebates['mdprr_theory_rebates_amount']	= 0.000;
		    				$ary_rebates['mdprr_actual_rebates_amount']	= 0.000;
	    					$ary_rebates['mdprr_memo']	= '订单未支付无返利';
	    				}
	    			} else {
	    				$ary_rebates['mdprr_memo']	= '订单作废或者订单暂停无返利';
	    			}
	    			
    			} else {
    				//订单数据丢失返利清零处理
    				$ary_rebates['mdprr_actual_rebates_amount']	= 0.000;
    				$ary_rebates['mdprr_is_unusual']	= 20;
    				$ary_rebates['mdprr_memo']	= '订单数据丢失无返利';
    			}
    			
				$ary_rebates_data['unusual_num']	+= ($ary_rebates['mdprr_is_unusual'] == 20 ? 1 :0);
    		}
    	} else {
    		$ary_data = array();
    	}
    	return array('rebates_report'=>$ary_data, 'rebates'=>$ary_rebates_data);
	}
	
	/**
	 * 根据订单号生成该订单对应的返利记录
	 * @param int $int_o_id <p>订单号</p>
	 * @return array('success'=>1,'err_msg'=>'','err_code'=>'')
     * @author Wangguibin@guanyisoft.com
     * @date 2014-01-05
	 */
	public function createDifferPriceRebatesRecord($int_o_id) {
		$ary_result	= array('success'=>1,'err_msg'=>'','err_code'=>'');
		try{
			//判断是否重复返利
			if(!(int)$int_o_id){
				throw new Exception('返利时订单号有误！', 88001);
			}
			if($this->checkRepeatRebatesByOid($int_o_id)) {
				throw new Exception('订单（'.$int_o_id.'）已经返利不能重复返利！', 88002);
			}
			$ary_order_res = $this->orders->where(array('o_id'=>$int_o_id))->field('m_id,o_id')->find();
			if(empty($ary_order_res) || !is_array($ary_order_res)) {
				throw new Exception('订单数据丢失！', 88003);
			}
			//获取上级分销商
			$ary_mp_res	= $this->getMemberDirectlyParent($ary_order_res['m_id']);
			//var_dump($ary_mp_res);exit;
			if(!$ary_mp_res['success']) {
				throw new Exception($ary_mp_res['err_msg'], $ary_mp_res['err_code']);
			}
			//获取会员名称
			$ary_m_name	= N('Member')->getMnameById($ary_order_res['m_id']);
			//获取订单明细数据
			$ary_oi_res = $this->orders_items->where(array('o_id'=>$int_o_id))->field('oi_id,oi_nums,oi_pirce,g_sn,pdt_sn,pdt_id,g_id')->select();
			if(empty($ary_oi_res) || !is_array($ary_oi_res)) {
				throw new Exception('订单明细丢失！', 88004);
			}
			//如果会员不是顶级会员才会生成返利记录
			if(!empty($ary_mp_res['data'])) {
						
				$obj_goods_price = new ProPrice();
				//生成返利记录
				foreach ($ary_oi_res as $ary_oi) {
					$fl_is_unusual	= 10;
					$fl_pdt_price_info = $obj_goods_price->getPriceInfo($ary_oi['pdt_id'], $ary_mp_res['data']['pm_id'], 0);
					$fl_pdt_price = $fl_pdt_price_info['pdt_price'];
					//如果父级会员价小于等于零，父级会员价大于下级会员价或者下级会员价小于等于零均为异常，标上小红旗
					if($fl_pdt_price <= 0 || $fl_pdt_price > $ary_oi['oi_price'] || $ary_oi['oi_price'] <= 0) {
						$fl_is_unusual	= 20;
					}
					$ary_mdprr_data	= array(
						'mdprr_pm_id'	=> $ary_mp_res['data']['pm_id'],
						'mdprr_pm_name'	=> $ary_mp_res['data']['pm_name'],
						'm_id'	=> $ary_order_res['m_id'],
						'm_name'	=> $ary_m_name['m_name'],
						'o_id'	=> $ary_order_res['o_id'],
						'oi_id'	=> $ary_oi['oi_id'],
						'g_sn'	=> $ary_oi['g_sn'],
						'pdt_sn'	=> $ary_oi['pdt_sn'],
						'mdprr_nums'		=> $ary_oi['oi_nums'],
						'mdprr_pm_price'	=> $fl_pdt_price,
						'oi_price'			=> $ary_oi['oi_price'],
						'mdprr_differ_price'	=> sprintf("%.3f", ($ary_oi['oi_price']-$fl_pdt_price)),
						'mdprr_theory_rebates_amount'	=> sprintf("%.3f", (($ary_oi['oi_price']-$fl_pdt_price)*$ary_oi['oi_nums'])),
						'mdprr_actual_rebates_amount'	=> $fl_is_unusual == 20 ? 0.000 : sprintf("%.3f", (($ary_oi['oi_price']-$fl_pdt_price)*$ary_oi['oi_nums'])),
						'mdprr_is_unusual'	=> $fl_is_unusual,
						'mdprr_create_time'	=> date('Y-m-d H:i:s'),
						'mdprr_modify_time'	=> date('Y-m-d H:i:s')
					);
					$res = $this->mdprr->data($ary_mdprr_data)->add();
					if(!$res) {
						throw new Exception('生成返利记录时遇到错误！', 88005);
					}
				}
			}
		} catch (Exception $e) {
			$ary_result['success']	= 0;
			$ary_result['err_msg']	= $e->getMessage();
			$ary_result['err_msg']	= $e->getCode();
		}
		return $ary_result;
	}
	
	/**
	 * 根据订单号判断是否重复返利
	 * @param int $int_o_id
	 * @return bool 如果重复返回true，否则返回false
	 * @author Wangguibin@guanyisoft.com
     * @date 2014-01-05
	 */
	public function checkRepeatRebatesByOid($int_o_id) {
		$ary_res = $this->mdprr->where(array('o_id'=>$int_o_id))->field('mdprr_id')->find();
		if($ary_res['mdprr_id']) {
			return true;
		}
		return false;
	}
	
	/**
	 * 获取会员直属上级
	 * @param int $int_m_id
	 * @return int m_id
     * @author Wangguibin@guanyisoft.com
     * @date 2014-01-05
	 */
	public function getMemberDirectlyParent($int_m_id) {
		$ary_result	= array('success'=>1,'err_msg'=>'','err_code'=>'','data'=>array(),'msg'=>'');
		try{
			$ary_m_res = $this->member_relation->field('mr_p_id')->where(array('m_id'=>$int_m_id))->find();
			if($ary_m_res === false) {
				throw new Exception('获取会员直属上级时遇到错误！', 88501);
			}
			//会员有直属上级才处理
			if(isset($ary_m_res['mr_p_id']) && !empty($ary_m_res['mr_p_id'])) {
				$ary_ml_res = $this->members->where(array('m_id'=>$ary_m_res['mr_p_id']))->field('ml_id')->find();
				if(isset($ary_ml_res['ml_id']) && !empty($ary_ml_res['ml_id'])) {
					$ary_m_name	= $this->members->where(array('m_id'=>$ary_m_res['mr_p_id']))->field('m_name')->find();
					$ary_result['data']	= array(
						'pm_id'		=> $ary_m_res['mr_p_id'],
						'pml_id'	=> $ary_ml_res['ml_id'],
						'pm_name'	=> $ary_m_name['m_name']
					);
				} else {
					throw new Exception('获取上级会员等级时遇到错误！', 88502);
				}
			} else {
				//会员没有上级时只给一个提示信息
				$ary_result['msg']	= '会员没有上级！';
			}
		} catch (Exception $e) {
			$ary_result['success']	= 0;
			$ary_result['err_msg']	= $e->getMessage();
			$ary_result['err_code']	= $e->getCode();
		}
		return $ary_result;
	}
	
	/**
     * 订单完成触发返利MODEL
     * 
     * @return mixed array
     * 	
     * @author Wangguibin@guanyisoft
     * @version 7.6.1
     * @modify 2014-09-18
     */
    public function ajaxOrderPakback($ary_order) {
        if(empty($ary_order)){
			return false;
		}
		$ary_where = array();
		$ary_where['m.m_id'] = $ary_order['m_id'];
		$ary_where['fx_orders_items.o_id'] = $ary_order['o_id'];
		$orders_items = D('OrdersItems')
		->join('fx_member_payback as m on (fx_orders_items.pdt_id=m.pdt_id)')
		->field('fx_orders_items.oi_id,m.m_id,fx_orders_items.g_id,fx_orders_items.oi_price,fx_orders_items.pdt_id,fx_orders_items.oi_nums,m.m_o_id,m.m_p_amount')
		->where($ary_where)->select();
        if (!empty($orders_items) && is_array($orders_items)) {
			foreach ($orders_items as $items) {
				if($items['m_p_amount'] < $items['oi_price']){
				    $ary_data = array();
					$ary_data = array(
						'oi_id' => $items['oi_id'],
						'm_id' => $items['m_id'],
						'm_o_id' => $items['m_o_id'],
						'pdt_id' => $items['pdt_id'],
						'mps_payback_amount' => $items['m_p_amount'] * $items['oi_nums'],
						'mps_description' => "",
					);
					$return_status = $this->member_payback_statistics->data($ary_data)->add();
					if(!$return_status){
						return false;
					}
				}
			}
		}
		return true;
    }

    /**
     * 判断是否有返利
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-30
     */
    public function IsOrderPakback($ary){
        if(empty($ary)){
            return false;
        }
        $ary_where = array();
        $ary_where['m.m_id'] = $ary['m_id'];
        $ary_where['fx_orders_items.o_id'] = $ary['o_id'];
        $orders_items = D('OrdersItems')
            ->join('fx_member_payback as m on (fx_orders_items.pdt_id=m.pdt_id)')
            ->field('fx_orders_items.oi_price,m.m_p_amount')
            ->where($ary_where)
            ->find();
        if(!empty($orders_items['m_p_amount'])){
            return true;
        }else{
            return false;
        }
    }
  /*
  * 合伙人注册
  *
  */
    
    public function partnerSave($data){
        if(!empty($data)){
            $parter = M('Partner')->data($data)->add();
            if($parter){
                return true;
            }else{
                return false;
            }
        }
    }
	
}