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
class SalespromotionModel extends GyfxModel{
	
	private $member_relation;
	private $sub_m_arr = array();
	private $ary_pid = 0;
	private $mr_path = array();
	
	/**
	 * 构造方法
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function __construct() {
		$this->member_relation = M('member_relation',C('DB_PREFIX'),'DB_CUSTOM');//推荐位
		$this->member= M('members',C('DB_PREFIX'),'DB_CUSTOM');//会员表
		$this->member_sales_set= M('member_sales_set',C('DB_PREFIX'),'DB_CUSTOM');//会员销售额设定
		$this->orders= M('orders',C('DB_PREFIX'),'DB_CUSTOM');//会员销售额设定
		import('ORG.Util.Page');
	}
	
	/**
	 * @获取查询用户
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getMembers($ary_filter){
        if($ary_filter['p'] == 1){
            if($ary_filter['m_name'] == ''){
                    return false;
            }
        }
		$ary_param	= array();
		$ary_res	= array('data'=>array(),'msg'=>'','err_msg'=>'','nums'=>0,'filter'=>$ary_filter);
		//搜索条件
		$ary_where = array();
		$str_as_sql	= "SELECT SQL_CALC_FOUND_ROWS m.m_id,m_name,ml_name FROM members AS m 
		LEFT JOIN member_level AS ml ON ml.ml_id=m.ml_id 
		WHERE m_verify = 2 AND m_name LIKE '%$ary_filter[m_name]%' 
		AND m.m_id NOT IN (SELECT m_id FROM member_relation) ";
		
		//审核通过
		$ary_where['m_verify'] = 2;
		$ary_where['m_name'] = array('like','%'.$ary_filter[m_name].'%');
		$ary_where['_string'] = ' fx_members.m_id not in(SELECT m_id FROM fx_member_relation)';

		//分页
		$str_limit = '';
		if( isset($ary_filter['p']) && intval($ary_filter['p']) > 0 &&
			isset($ary_filter['pagesize']) && intval($ary_filter['pagesize']) > 0) {
			$str_limit = intval($ary_filter['p']-1)*intval($ary_filter['pagesize']).','.intval($ary_filter['pagesize']);
		}

		try{
            //$ary_filter['pagesize']  每页要显示记录数
            //$ary_filter['p']   当前页
            //总记录数
			$ary_tmp_num = $this->member->join('fx_members_level as ml on ml.ml_id=fx_members.ml_id')->where($ary_where)->count();
            //总页数
            $pagenum = ceil($ary_tmp_num/$ary_filter['pagesize']);
            //显示的内容
            $ary_res['data'] = $this->member->join('fx_members_level as ml on ml.ml_id=fx_members.ml_id')
                ->field('fx_members.m_id,m_name,ml_name')->where($ary_where)->limit($str_limit)->select();
            //当前页小于1则为1
            $ary_filter['p'] = $ary_filter['p']<1?1:$ary_filter['p'];
            //当前页大于总页数 则为总页数
            $ary_filter['p'] = $ary_filter['p']>$pagenum ? $pagenum : $ary_filter['p'];
            $show = '<ul id="pageation">';
            $show .='&nbsp;&nbsp;共'.$ary_tmp_num.'条记录&nbsp;';
            $show .= '&nbsp;当前'.$ary_filter['p'].'/'.$pagenum.'页';
            if($ary_filter['p']>1){
                $prev = $ary_filter['p']-1;
                $show .= '<li style="width:auto;padding:0 3px;"><a title="上一页" href="javascript:void(0);" onclick="getMembers('.$prev.')"> << </a></li>';
            }
			$j=0;
            for($i=1;$i<=$pagenum;$i++){
                    if($i == $ary_filter['p']){
                        $show .='&nbsp;<li style="width:auto;padding:0 3px;"><a href="javascript:void(0);" style="font:bold 15px arial;" onclick="getMembers('.$i.')">'.$i.'</a></li>&nbsp;';
                    }else{
						$num=$pagenum - $ary_filter['p'];
						$right_start= $pagenum-5;
						if((($i > $ary_filter['p']) || ($right_start <=$i) )&& $j<=5){
							$j++;
							$show .='&nbsp;<li style="width:auto;padding:0 3px;"><a href="javascript:void(0);" onclick="getMembers('.$i.')">'.$i.'</a></li>&nbsp;';
						}
					}

            }
            if($ary_filter['p']< $pagenum ){
                $next = $ary_filter['p'] + 1;
                $show .='<li style="width:auto;padding:0 3px;"><a title="下一页" href="javascript:void(0);" onclick="getMembers('.$next.')"> >> </a></li>';
            }
            $show .= '</ul>';
            $ary_res['pageinfo'] = $show;
            $ary_res['nums'] = $ary_tmp_num;
		}catch (PDOException $e){
			if($this->debug) {
				$ary_res['err_msg']	= $e->getMessage();
			}
		}
//		echo "<pre>";print_r($ary_res);die();
		return $ary_res;
	}
	
	/**
	 * ajaxEditMemberRelation
	 * @编辑分销商关系
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function ajaxeditMemberRelation($post){
		if(intval($post['mr_p_id'])){
			$mr_path_sql = "SELECT `m_id`,`mr_path` FROM `member_relation` WHERE `m_id`={$post['mr_p_id']}";
			$mrs_path_sql = "SELECT `mr_id`,`m_id`,`mr_path`,`mr_p_id` FROM `member_relation`";
			$ary_result = DB()->fetchOne($mr_path_sql);
			//echo "<pre>";print_r($ary_result);
			$str_members = array();
			$str_members = DB()->fetchAll($mrs_path_sql);
			$ary_members = array();
			foreach ($str_members as $km=>$kv) {
				$str_members[$km]['mr_path'] = str_replace($ary_result['m_id'], $post['m_id'], $kv['mr_path']);
				$str_members[$km]['mr_p_id'] = str_replace($ary_result['m_id'], $post['m_id'], $kv['mr_p_id']);
			}
			//print_r($str_members);
//			echo "<pre>";print_r($ary_result);die;
			$m_result = DB()->fetchOne($mr_path_sql);
			if(!empty($m_result) && is_array($m_result)){
				$post['mr_path'] = $m_result['mr_path'];
			}else{
				$post['mr_path'] = '0';
			}
			if($post['m_id']){
				$ary_params	= array('m_id'	=> $post['m_id'],
								'mr_path'	=>	'',
								'mr_p_id'	=> $ary_result['m_id']);
				
				$str_sql	= "UPDATE member_relation SET 
							m_id =:m_id,
							mr_path =:mr_path
							WHERE m_id = :mr_p_id";
				$udpate	= DB()->query($str_sql,$ary_params);
			}
			if(!$udpate){
				return false;
			}else{
				//echo "<pre>";print_r($str_members);die;
				if(!empty($str_members) && is_array($str_members)){
					foreach ($str_members as $kst=>$vst) {
						if(!empty($vst['mr_path'])){
							$str_sql_list	= "UPDATE member_relation SET 
							mr_path ='{$vst['mr_path']}',
							mr_p_id ='{$vst['mr_p_id']}'
							WHERE mr_id = '{$vst['mr_id']}'";
							$update	= DB()->query($str_sql_list);
							
						}
					}
				}
				if(!$udpate){
					return false;
				}else{
					return true;
				}
			}
		}else{
			return false;
		}
	}
	
	/**
	 * @分销商层级关系 添加
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function addMemberRelation($post){
		$mr_path	= '';
		if(intval($post['mr_p_id'])){
			$this->getMemberRelationPath($post['mr_p_id']);
			$mr_path_arr = array();
			if(!empty($this->mr_path)){
				$mr_path_arr = $this->mr_path;
			}
			array_push($mr_path_arr, $post['mr_p_id']);
			$mr_path = implode(',', $mr_path_arr).',';
		}
		//echo $mr_path;exit;
		$ary_params	= array('m_id'	=> $post['m_id'],'mr_path'	=> $mr_path,'mr_p_id' => $post['mr_p_id']);
		$mr_id	= 0;
		$mr_id	= $this->member_relation->data($ary_params)->add();
		if($mr_id){
			//绑定现在的分销商授权关系
			$ary_params['m_id']=$post['m_id'];
			$ary_params['p_id']=$post['mr_p_id'];
			$add_bool_res=D('AuthorizeLine')->DistributorAutoAuthorize($ary_params);
			if($add_bool_res==false){
			   return false;
			}
			return $mr_id;
		}else{
			return false;
		}
	}

	/**
     * @编辑分销商引荐管理 
     * @return boolean
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function editMemberRelation($post){
		$post['mr_path']	= '';
		if(intval($post['mr_p_id'])){
			$this->getMemberRelationPath($post['mr_p_id']);
		  	//print_r($this->mr_path);exit;
			if(empty($this->mr_path)){
				$post['mr_path']	= $post['mr_p_id'].',';
			}else{
				$post['mr_path']	= $post['mr_p_id'].','.implode(',',$this->mr_path).',';
			}
		}
		if($post['m_id']){
			$ary_params	= array('m_id'	=> $post['m_id'],
								'mr_path'	=> $post['mr_path'],
								'mr_p_id'	=> $post['mr_p_id']);
			$update	= $this->updateMemberRelation($ary_params);
			$data_mids	= $this->getMemberIds($post['m_id']);
			$ary_mids	= $data_mids['ary_mids'];
			if(is_array($ary_mids)){
				foreach ($ary_mids as $val){
					$data2	= $this->member_relation->field('mr_p_id')->where(array('m_id'=>$val))->find();
					$p_path	= $this->member_relation->field('mr_path')->where(array('m_id'=>$data2['mr_p_id']))->find();
					$ary_params2	= array('m_id'	=> $val,'mr_path'	=> $data2['mr_p_id'].','.$p_path['mr_path']);
					$udpate2 = $this->member_relation->where(array('m_id'=>$ary_params2['m_id']))->data(array('mr_path'=>$ary_params2['mr_path']))->save();						
				}
			}
		}
		if(!$update && !$udpate2){
			return false;
		}else{
			return true;
		}
		
	}
	
	/**
     * @编辑分销商引荐管理 
     * @return boolean
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function updateMemberRelation($ary_params){
		$ary_params	= array('m_id'	=> $ary_params['m_id'],
							'mr_path'	=> $ary_params['mr_path'],
							'mr_p_id'	=> $ary_params['mr_p_id']);
		$mr = false;
		$mr = $this->member_relation->where(array('m_id'=>$ary_params['m_id']))->data($ary_params)->save();
		if(false === $mr){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * @销售额设定 列表
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getSalesSet($ary_params){
		$ary_data   = array();
		$ary_where	= array();
		$str_limit = '';
		$str_order = '';
		$ary_data['ct']	= 0;
		if(isset($ary_params['m_name'])){
			$ary_where['m.m_name'] = array('like','%'.$ary_params['m_name'].'%');
		}
		if(!isset($ary_params['page'])){
			$ary_params['page'] = 1;
		}
		if(!isset($ary_params['pagesize'])){
			$ary_params['pagesize'] = 20;
		}
		# 开始查询的行
		$start	= ($ary_params['page']-1) * $ary_params['pagesize'];
		$str_limit = $start . ',' . $ary_params['pagesize'];
		$str_order = 'mss_id DESC';
		$count = $this->member_sales_set->field('*,m.m_name')
				   	  ->join('fx_members as m on m.m_id=fx_member_sales_set.m_id')
				   	  ->where($ary_where)
				      ->count();
		$ary_data['list'] = $this->member_sales_set
		   	  ->join('fx_members as m on m.m_id=fx_member_sales_set.m_id')
		      ->order($str_order)
		      ->limit($str_limit)
		      ->where($ary_where)
		      ->select();		     
		$obj_page = new Page($count, $ary_params['pagesize']);
		$page = $obj_page->show();
		$ary_data['pageinfo'] = $page;
		if($count > 0){
			$ary_data['ct']	= $count;
            foreach($ary_data['list'] as $mkey=>$mval){
                $search_where = array();
                $search_where['fx_orders.m_id'] = $mval['m_id'];
                $search_where['fx_orders.o_status'] = 4;
                $search_where['_string'] = " fx_orders.`o_create_time` >='".$mval['mss_time_begin']."' AND fx_orders.`o_create_time`<='".$mval['mss_time_end']."'";
				$o_amount = $this->orders->join('inner join fx_orders_items as oi on fx_orders.o_id=oi.o_id')->field('SUM(oi.oi_price*oi_nums) as o_amount')->where($search_where)->find();
                $o_amount = !empty($o_amount['o_amount'])?$o_amount['o_amount']:'0.00';
				$search_where['fx_orders_refunds.or_processing_status'] = 1;
				$o_returns_amount = $this->orders->field('SUM(fx_orders_items.oi_price) as o_returns_amount')
									->join('inner join fx_orders_items on fx_orders_items.o_id=fx_orders.o_id')
									->join('inner join fx_orders_refunds on fx_orders_refunds.o_id = fx_orders_items.o_id')
									->join('inner join fx_orders_refunds_items on fx_orders_refunds_items.or_id=fx_orders_refunds.or_id')
									->where($search_where)
									->find();
                $o_returns_amount = !empty($o_returns_amount['o_returns_amount'])?$o_returns_amount['o_returns_amount']:'0.00';
                $money = $o_amount-$o_returns_amount;
                if(!empty($money)){
                    $ary_data['list'][$mkey]['m_amount'] = sprintf($money,'%.3f');
                }else{
                    $ary_data['list'][$mkey]['m_amount'] = '0.00';
                }
            }
		}
		return $ary_data;
	}
	
	/**
	 * @验证用户名
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function checkMname($mname){
		$count['m_id']	= 0;
		if(!$mname){
			return $count['m_id'];
			exit;
		}
		
		$str_sql	= 'SELECT m_id FROM members WHERE m_name = :m_name';
		$count	= DB()->fetchOne($str_sql,array('m_name'	=> $mname));
		//print_r($count);
		if($count['m_id']){
				$sql_mss	= 'SELECT m_id FROM member_sales_set WHERE m_id = :m_id';
				$data_mss	= DB()->fetchOne($sql_mss,array('m_id'	=> $count['m_id']));
				if($data_mss['m_id']){
					$count['m_id']	= 2;
				}else{
					$count['m_id']	= 1;
				}
		}
		
		return $count['m_id'];
	}
	
	/*
	 * @销售额设定  显示编辑
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getSalesSetOne($mss_id){
		$ary_data	= array();
		if(!$mss_id){
			return $ary_data;
			exit;
		}
		$ary_data = $this->member_sales_set->join('fx_members as m on m.m_id=fx_member_sales_set.m_id')
					->field('*,m.m_name')->where(array('mss_id'=>$mss_id))->find();
		//dump($this->member_sales_set->getLastSql());die();
		return $ary_data;
	}
	
	/**
	 * 根据用户名查询用户信息
	 * @param string $m_name  精确查询
	 * @return array 返回包含用户名信息的数组,一条记录，如果没有，则返回空数组
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getMembersData($m_name,$filed){
		$data	= '';
		if(!$m_name){
			return $data;
			exit;
		}
		return $this->member->where(array('m_name'=>$m_name))->field($filed)->find();
	}
	
	/**
	 * 通过m_name 查询销售额信息
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getSalesSetOneByMname($m_name){
		$ary_data	= array();
		$ary_data = $this->member->join('fx_member_sales_set as mss on fx_members.m_id=mss.m_id')
					->field('mss.*,fx_members.m_name,fx_members.m_id')->where(array('m_name'=>$m_name))->find();
		return $ary_data;
	}

	/**
	 * 通过给定的日期查询是否能insert成功
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-27
	 */
	public function getSalesSetOneByDate($param){
		$ary_data = array();
		$ary_where = array();
		if(!$param['m_id']){
			return $ary_data;
		}
		$ary_where['m_id'] = $param['m_id'];
		
		$ary_where['_string'] = "(('{$param[mss_time_begin]}' >=mss_time_begin
								 and '{$param[mss_time_begin]}'<=mss_time_end) OR (
								'{$param[mss_time_end]}'>=mss_time_begin
								and '{$param[mss_time_end]}'<=mss_time_end))";
	    if($param['method'] == 'edit'){
	    	$ary_where['mss_id'] = array('neq',$param['mss_id']);
	    }
		$ary_data = $this->member_sales_set->where($ary_where)->find();
		//dump($this->member_sales_set->getLastSql());die();
		return $ary_data;
	}	
	
	/**
	 * 判断是否设定销售额
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-27
	 */
	public function getSalesSetCount($m_id){
		return $this->member_sales_set->where(array('m_id'=>$m_id))->count();
	}	
	
	
	/**
	 * 执行添加操作,添加数据到member_sales_set表
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function doInsertMemberSaleSet($param) {
    	$sql_ary_params	= array(
    		'm_id' => $param['m_id'],
			'mss_time_begin' => $param['mss_time_begin'],
			'mss_time_end' => $param['mss_time_end'],
			'mss_sales'	=> $param['mss_sales']
		);
	    return $this->member_sales_set->data($sql_ary_params)->add();
	}
	
	/**
	 * 执行修改操作,编辑数据表member_sales_set
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function doEditMemberSaleSet($param) {
    	$sql_ary_params	= array(
    		'm_id' => $param['m_id'],
			'mss_time_begin' => $param['mss_time_begin'],
			'mss_time_end' => $param['mss_time_end'],
			'mss_sales'	=> $param['mss_sales']
			//'mss_id'	=> $param['mss_id']
		);
		$ary_where = array('mss_id'=>$param['mss_id']);
		$res =  $this->member_sales_set->where($ary_where)->data($sql_ary_params)->save();
		return $res;
	}

	/**
	 * 删除操作,编辑数据表member_sales_set
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-12-27
	 */
	public function doDeleteMemberSaleSet($str_mssids) {
		return $this->member_sales_set->where(array('mss_id'=>array('in',$str_mssids)))->delete();
	}
		
	/**
	 * @验证用户ID
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function checkMemberId($m_id){
		$count['ct']	= 0;
		if(!$m_id){
			return $count['ct'];
			exit;
		}
		return $this->member_relation->where(array('m_id'=>$m_id))->count();
	}
	
	/**
     * @分销商引荐管理 
     * @return mixed array
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function getMemberRelationAll($m_name){
		$sql_ary_params	= array();
		$ary_data	= array();
		$ary_where = array();
		if($m_name && $m_name != ''){
			$member	= $this->member->where(array('m_name'=>$m_name))->find();
			if($member['m_id']){
				$data_mids	= $this->getMemberIds($member['m_id']);
				$mids	= $data_mids['mids'];
				$ary_where['_string'] = " mr_p_id IN ('".$mids.$member['m_id']."') OR m.m_id = ".$member['m_id'];
			}else{
				return $ary_data;
			}
		}
		$data_all = $this->member_relation
		->field('mr_id,mr_p_id,mr_path,m.m_id,m.m_name,ml.ml_name ')
		->join('fx_members as m on(m.m_id=fx_member_relation.m_id)')
		->join('fx_members_level as ml on(ml.ml_id = m.ml_id)')
		->where($ary_where)
		->select();
		if(!empty($data_all)){
			return $data_all;
		}else{
			return $ary_data;
		}
	}
	
	/**
     * @下级分分销商 ,包含自己
     * @return mixed array
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
    public function getSubMem($m_id)
    {
        $sub_m_list = $this->member_relation->field('m_id,mr_path')->where(array('mr_p_id'=>$m_id))->select();
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
     * @获取用户ID() 
     * @return string 1','3',
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
    public function getMemberIds($m_id){
    	
    	$ary_mids	= array();
    	if(!$m_id){
    		return false;
    	}
    	$this->getSubMem($m_id);
		//echo "<pre>";print_r($this->sub_m_arr);die();
		foreach ($this->sub_m_arr as $key=>$ary_value) {
			foreach ($ary_value as $val){
				if($val){
					array_unique($ary_mids);
					if(!in_array($val,$ary_mids)){
						$mids	.= $val."','";
					}
					$ary_mids[]	= $val;
				}
			}
		}
		return array('mids'	=> $mids,
		'ary_mids'	=> $ary_mids);
    }
    
	/**
     * @删除分销商引荐管理 
     * @return boolean	
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function deleteRelationOne($m_id){
		if(!$m_id){
			return false;
		}
		$data_mids	= $this->getMemberIds($m_id);
		$mids	= $data_mids['mids'];
		$del = false;
		$str_del_mids = $mids.$m_id;
		$del = $this->member_relation->where(array('m_id'=>array('in',$str_del_mids)))->delete();
		if(false == $del){
			return false;
		}else{
			return true;
		}
	}
	
	/**
     * @获取用户名和等级
     * @return array
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function getMemberNameLevelName($m_id){
		$sql_ary_params	= array();
		$ary_data	= array();
		$ary_where = array();
		if(!$m_id){
			return $ary_data;
			exit;
		}else{
			$ary_data['m.m_id'] = $m_id;
			$ary_where	= array('m.m_id' => $m_id);
		}
		$data = $this->member_relation
		->join('fx_members as m on(m.m_id=fx_member_relation.m_id)')
		->join('fx_members_level as ml on(ml.ml_id=m.ml_id)')
		->field('mr_id,mr_p_id,m.m_id,m.m_name,ml.ml_name ')
		->where($ary_where)
		->find();
		if(!empty($data)){
			return $data;
		}else{
			return $ary_data;
		}
	}
	
	/**
     * @获取路径 递归查询获取父ID
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
     */
	public function getMemberRelationPath($mr_p_id){
		if($mr_p_id){
			$result	= $this->member_relation->where(array('m_id'=>$mr_p_id))->field('mr_p_id')->find();
			if(is_array($result) && $result['mr_p_id']){
				$this->mr_path[]	= $result['mr_p_id'];
				$this->getMemberRelationPath($result['mr_p_id']);
			}
		}
	}

	/**
	 * 通过m_id 得到member_relation信息
	 * @param $m_id 
	 * @return  如果成功,有m_id对应的数据,返回array,否则返回false
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-09-15
	 */
	public function getMemberRelationByMid($m_id) {
		return $this->member_relation->where(array('m_id'=>$m_id))->order('mr_id asc')->find();
	}
	
	
	
}
