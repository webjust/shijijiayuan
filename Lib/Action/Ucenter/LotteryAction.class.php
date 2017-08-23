<?php
/**
 * 抽奖Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.6.1
 * @author Wang Guibin
 * @date 2014-07-15
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class LotteryAction extends CommonAction {

    /**
     * 控制器初始化
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-15
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 点击抽奖
	 * 每次前端页面的请求，PHP循环奖项设置数组， 
	 * 通过概率计算函数get_rand获取抽中的奖项id。 
	 * 将中奖奖品保存在数组$res['yes']中， 
	 * 而剩下的未中奖的信息保存在$res['no']中， 
	 * 最后输出json个数数据给前端页面。 
	 * $prize_arr
	 * 奖项数组 
	 * 是一个二维数组，记录了所有本次抽奖的奖项信息， 
	 * 其中id表示中奖等级，prize表示奖品，v表示中奖概率。 
	 * 注意其中的v必须为整数，你可以将对应的 奖项的v设置成0，即意味着该奖项抽中的几率是0， 
	 * 数组中v的总和（基数），基数越大越能体现概率的准确性。 
	 * 本例中v的总和为100， 
	 * 如果v的总和是10000，那中奖概率就是万分之一了。 
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-15
     */
	public function doLottery(){
		$m_id = $_SESSION['Members']['m_id']; 
		if(empty($m_id)){
			$this->error('会员需要登陆之后才能参与抽奖的',U('Home/User/login') . '?redirect_uri=' . urlencode($_SERVER['HTTP_REFERER']));exit;
		}
		$l_id = $this->_post('l_id');
		if(empty($l_id)){
			$this->error('抽奖活动类型不存在');exit;
		}
		//判断抽奖活动是否满足条件
		$lottery_obj = D('Lottery');
		$lottery_info = $lottery_obj->where(array('is_deleted'=>0,'l_status'=>1,'l_id'=>$l_id))->find();
		if(empty($lottery_info)){
			$this->error('抽奖活动类型不存在或结束');exit;
		}
		if($lottery_info['l_start_time']>date('Y-m-d H:i:s')){
			$this->error('此抽奖活动还未开始');exit;
		}
		if($lottery_info['l_end_time']<date('Y-m-d H:i:s')){
			$this->error('此抽奖活动已结束');exit;
		}		
		
		if(empty($lottery_info['l_detail'])){
			$this->error('此抽奖活动还未开始或已结束');exit;
		}
		$ary_detail = unserialize($lottery_info['l_detail']);
		//判断神秘大奖是否已被抽完
		//查看是否需要积分抽奖以及积分是否足够
		if($lottery_info['is_consume_pont'] == 1 && !empty($lottery_info['consume_point'])){
			$point_count = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->field('total_point,freeze_point')->find();
			if($lottery_info['consume_point']>$point_count['total_point']-$point_count['freeze_point']){
				$this->error('您的积分已不足,不能参与抽奖！');exit;
			}
		}
		//每天抽奖次数限定
		if(!empty($lottery_info['l_number'])){
			$lottery_count = M('lottery_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('l_id'=>$l_id,'m_id'=>$m_id,'ll_create_time'=>array('egt',date('Y-m-d 00:00:00'))))->count();
			//echo M('lottery_log',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
			if($lottery_count>=$lottery_info['l_number']){
				$this->error('今日已达抽奖次数,每天限抽'.$lottery_info['l_number'].'次');exit;
			}
		}
		$lottery_user_obj = D('LotteryUser');
		$str_total = 0;
		//神秘大奖有次数限制
		if(!empty($ary_detail['mystery']['type_num'])){
			$mystery_count = $lottery_user_obj->where(array('l_id'=>$l_id,'is_used'=>1,'ul_type'=>'2'))->count();
			$str_total = $str_total+1;
			if($mystery_count >=$ary_detail['mystery']['type_num']){
				$ary_detail['other']['type_ratio'] = $ary_detail['other']['type_ratio']+$ary_detail['mystery']['type_ratio'];
				unset($ary_detail['mystery']);
			}else{
				//unset($ary_detail['mystery']);
			}
		}
		//判断红包是否已被抽完
		if(!empty($ary_detail['bonus'])){
			$bonus_total_price = $ary_detail['total_price'];
			$str_total = $str_total+count($ary_detail['bonus']);
			//$str_total_bonus = count($ary_detail['bonus']);
			$sum_bonus = $lottery_user_obj->where(array('l_id'=>$l_id,'is_used'=>1,'ul_type'=>'1'))->sum('ul_bonus_money');
			if($sum_bonus>=$bonus_total_price){
				$ary_detail['other']['type_ratio'] = 100-$ary_detail['mystery']['type_ratio'];
				unset($ary_detail['bonus']);
			}
		}
		if(!empty($ary_detail['other']['again_sort'])){
			$str_total = $str_total+1;
		}
		if(!empty($ary_detail['other']['other_sort'])){
			$str_total = $str_total+1;
		}
		//抽奖数组
		$prize_arr = array();
		//红包
		foreach($ary_detail['bonus'] as $key=>$bonus){
			$prize_arr[$key] = array('id'=>$key+1,'prize'=>'红包'.$bonus['type_money'].'元','v'=>$bonus['type_ratio'],'type'=>1,'type_info'=>$bonus['type_money'],'type_sort'=>$bonus['type_sort']);
		}
		//神秘大奖
		if(!empty($ary_detail['mystery'])){
			$prize_arr[count($ary_detail['bonus'])] = array('id'=>count($ary_detail['bonus'])+1,'prize'=>'神秘大奖'.$ary_detail['mystery']['ul_title'],'v'=>$ary_detail['mystery']['type_ratio'],'type'=>2,'type_info'=>$ary_detail['mystery']['ul_title'],'type_sort'=>$ary_detail['mystery']['type_sort'],'type_num'=>$ary_detail['mystery']['type_num']);
		}
		//未中奖
		if(!empty($ary_detail['other'])){
			$arr = array($ary_detail['other']['again_sort'],$ary_detail['other']['other_sort'],);
			$key=array_rand($arr);   //随机获取数组的键
			$prize_arr[count($ary_detail['bonus'])+1] = array('id'=>count($ary_detail['bonus'])+2,'prize'=>'未中奖呀,下次没准就能中哦','v'=>$ary_detail['other']['type_ratio'],'type'=>0,'type_sort'=>$arr[$key]);
		}	
		foreach ($prize_arr as $key => $val) {   
			$arr[$val['id']] = $val['v'];   
		}  
		$rid = $this->get_rand($arr); //根据概率获取奖项id   
		$res['yes'] = $prize_arr[$rid-1]['prize']; //中奖项   
		$ary_lottery = $prize_arr[$rid-1];
		unset($prize_arr[$rid-1]); //将中奖项从数组中剔除，剩下未中奖项   
		shuffle($prize_arr); //打乱数组顺序   
		for($i=0;$i<count($prize_arr);$i++){   
			$pr[] = $prize_arr[$i]['prize'];   
		}   
		$res['no'] = $pr;  
		$type_sort = $ary_lottery['type_sort']; 
		//unset($ary_detail);
		//dump($ary_lottery);
		M('',C(''),'DB_CUSTOM')->startTrans();
		//未中奖
		$lottery_log = array(
			'll_create_time'=>date('Y-m-d H:i:s'),
			'm_id'=>$m_id,
			'l_id'=>$l_id,
			'll_desc'=>serialize($res['yes'])
		);		
		if($ary_lottery['type'] == 0){
			$lottery_log['ul_id'] = 0;
		}
		//红包
		if($ary_lottery['type'] == 1){
			//生成红包调整单，红包充值，返回
			$str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
			$ary_bonus_data = array(
				'bn_sn'=>time(),
				'bt_id'=>2,
				'm_id'=>$m_id,
				'bn_money'=>$ary_lottery['type_info'],
				'bn_type'=>1,
				'bn_service_verify'=>1,
				'bn_finance_verify'=>1,
				'bn_verify_status'=>1,
				'bn_desc'=>'抽奖红包:'.$ary_lottery['type_info'].'元',
				'bn_create_time'=>date("Y-m-d H:i:s"),
				'bn_update_time'=>date("Y-m-d H:i:s"),
				'single_type'=>'2'
			);
            $ary_result = M('bonus_info',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_bonus_data);
            if(FALSE != $ary_result){
                $ary_data = array();
                $str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
                $ary_data['bn_sn'] = time() . $str_sn;
                $result = M('bonus_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('bn_id'=>$ary_result))->data($ary_data)->save();
                $params = array(
                    'u_id'  =>$m_id,
                    'bn_sn' => $ary_data['bn_sn'],
                    'bvl_status'    =>'1',
                    'bvl_create_time'   =>date("Y-m-d H:i:s")
                );
				//自动客审
                //if(!empty($ary_post['bn_service_verify']) && $ary_post['bn_finance_verify'] == '1'){
                    $params['bvl_desc'] = '已客审成功';
                    $params['bvl_type'] = '2';
                    $service_res = $this->writeBonusInfoLog($params);
					if(!$service_res){
						M('',C(''),'DB_CUSTOM')->rollback();
						$this->error("发放红包失败失败");						
					}
                //}
				//自动财审
                //if(!empty($ary_post['bn_finance_verify']) && $ary_post['bn_finance_verify'] == '1'){
                    $params['bvl_desc'] = '已财审成功';
                    $params['bvl_type'] = '3';
                    $finance_res = $this->writeBonusInfoLog($params);
                //}       
                if(FALSE != $finance_res){
					$ary_data = D("Members")->field("m_bonus,total_point,freeze_point")->where(array("m_id"=>$m_id))->find();
					$m_bonus = '';
					$m_bonus = $ary_data['m_bonus'] + $ary_lottery['type_info'];
					$ary_mem_data = array();
					$ary_mem_data['m_bonus'] = $m_bonus;
					$ary_mem_data['m_update_time'] = date('Y-m-d H:i:s');
					$m_res = D("Members")->where(array('m_id'=>$m_id))->data($ary_mem_data)->save();
					if(!$m_res){
						M('',C(''),'DB_CUSTOM')->rollback();
						$this->error("操作失败-3");
					}
					//红包调整成功
					if($m_res){
						//新增中奖名单
						$ary_ul_data = array(
							'ul_bonus_money'=>1,
							'l_id'=>$l_id,
							'bn_id'=>$ary_result,
							'ul_bonus_money'=>$ary_lottery['type_info'],
							'is_used'=>1,
							'ul_type'=>1,
							'm_id'=>$m_id,
							'ul_create_time'=>date('Y-m-d H:i:s'),
							'ul_update_time'=>date('Y-m-d H:i:s'),
							'ul_confirm_time'=>date('Y-m-d H:i:s')
						);
						$ul_id = $lottery_user_obj->data($ary_ul_data)->add();
						if(!$ul_id){
							M('',C(''),'DB_CUSTOM')->rollback();
							$this->error('新增中奖名单失败');exit;
						}	
						$lottery_log['ul_id'] = $ul_id;						
					}
                }else{
                    M('',C(''),'DB_CUSTOM')->rollback();
                    $this->error("操作失败-1");
                }
            }else{
                M('',C(''),'DB_CUSTOM')->rollback();
                $this->error("操作失败-2");
            }	
			
		}
		//神秘大奖
		if($ary_lottery['type'] == 2){
			//新增中奖名单
			$ary_ul_data = array(
				'ul_type'=>2,
				'l_id'=>$l_id,
				'ul_title'=>$ary_lottery['type_info'],
				'is_used'=>1,
				'm_id'=>$m_id,
				'ul_create_time'=>date('Y-m-d H:i:s'),
				'ul_update_time'=>date('Y-m-d H:i:s'),
				'ul_confirm_time'=>date('Y-m-d H:i:s')
			);
			$ul_id = $lottery_user_obj->data($ary_ul_data)->add();
			if(!$ul_id){
				M('',C(''),'DB_CUSTOM')->rollback();
				$this->error('新增中奖名单失败');exit;
			}
			$lottery_log['ul_id'] = $ul_id;
		}
		//抽奖信息插入日志表
		$lot_log = M('lottery_log',C('DB_PREFIX'),'DB_CUSTOM')->add($lottery_log);
		if(!$lot_log){
			M('',C(''),'DB_CUSTOM')->rollback();
			$this->error('新增中奖名单失败-2');exit;		
		}
		if($lottery_info['is_consume_pont'] == 1 && !empty($lottery_info['consume_point'])){
			$adjust_point = 0;
			if(empty($ary_data)){
				$ary_data = D("Members")->field("total_point,freeze_point")->where(array("m_id"=>$m_id))->find();
			}
			if($lottery_info['consume_point']>$ary_data['total_point']-$ary_data['freeze_point']){
				M('',C(''),'DB_CUSTOM')->rollback();
				$this->error('您的积分已不足,不能参与抽奖！');exit;
			}else{
				$adjust_point = $lottery_info['consume_point'];
			}
			if(!empty($adjust_point)){
				$ary_point_data = array();
				$ary_point_data['type'] = 9;
				$ary_point_data['consume_point'] = intval($adjust_point);
				$ary_point_data['memo'] = '会员抽奖消耗积分';
				//事物开启
				$point_res = D('PointLog')->addPointLog($ary_point_data, $m_id);
				if($point_res['status'] != '1'){
					M('',C(''),'DB_CUSTOM')->rollback();
					$this->error('抽奖消耗积分失败！');exit;						
				}else{
					$m_point = $ary_data['total_point']-intval($adjust_point);
				}
				$ary_mem_data = array();
				$ary_mem_data['total_point'] = intval($m_point);
				$ary_mem_data['m_update_time'] = date('Y-m-d H:i:s');
				$m_res = D("Members")->where(array('m_id'=>$m_id))->data($ary_mem_data)->save();
				if(!$m_res){
					M('',C(''),'DB_CUSTOM')->rollback();
					$this->error("操作失败-5");
				}	
				$_SESSION['Members']['total_point'] = $m_point;
			}			
		}
		//神秘大奖有次数限制
		if($ary_lottery['type'] == 1){
			if(!empty($ary_detail['mystery']['type_num'])){
				$mystery_count = $lottery_user_obj->where(array('l_id'=>$l_id,'is_used'=>1,'ul_type'=>'2'))->count();
				if($mystery_count >$ary_detail['mystery']['type_num']){
					M('',C(''),'DB_CUSTOM')->rollback();
					//$this->error('新增中奖名单失败-3');exit;	
					$this->error('很遗憾您未中奖，神秘大奖有次数限制');exit;	
				}
			}
		}
		if($ary_lottery['type'] == 1){
			//判断红包是否已被抽完
			if(!empty($ary_detail['bonus'])){
				$bonus_total_price = $ary_detail['total_price'];
				$sum_bonus = $lottery_user_obj->where(array('l_id'=>$l_id,'is_used'=>1,'ul_type'=>'1'))->sum('ul_bonus_money');
				if($sum_bonus>$bonus_total_price){
					M('',C(''),'DB_CUSTOM')->rollback();
					//$this->error('新增中奖名单失败-4');exit;		
					$this->error('很遗憾您未中奖，红包已被抽完');exit;							
				}
			}
		}
		if(!empty($lottery_info['l_number'])){
			$lottery_count = M('lottery_log',C('DB_PREFIX'),'DB_CUSTOM')->where(array('l_id'=>$l_id,'m_id'=>$m_id,'ll_create_time'=>array('egt',date('Y-m-d 00:00:00'))))->count();
			if($lottery_count>$lottery_info['l_number']){
				M('',C(''),'DB_CUSTOM')->rollback();
				$this->error('今日已达抽奖次数,每天限抽'.$lottery_info['l_number'].'次');exit;
			}
		}
		M('',C(''),'DB_CUSTOM')->commit();
		$this->success($str_total.'|'.$type_sort.'|'.'抽奖完成,'.$res['yes']);
	}
	
	/**
     * 记录审核日志
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function writeBonusInfoLog($params){
        return M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
	
	/* 
	 * 经典的概率算法， 
	 * $proArr是一个预先设置的数组， 
	 * 假设数组为：array(100,200,300，400)， 
	 * 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，  
	 * 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间， 
	 * 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。 
	 * 这样 筛选到最终，总会有一个数满足要求。 
	 * 就相当于去一个箱子里摸东西， 
	 * 第一个不是，第二个不是，第三个还不是，那最后一个一定是。 
	 * 这个算法简单，而且效率非常 高， 
	 * 关键是这个算法已在我们以前的项目中有应用，尤其是大数据量的项目中效率非常棒。 
	 */  
	function get_rand($proArr) {   
		$result = '';    
		//概率数组的总概率精度   
		$proSum = array_sum($proArr);    
		//概率数组循环   
		foreach ($proArr as $key => $proCur) {   
			$randNum = mt_rand(1, $proSum);   
			if ($randNum <= $proCur) {   
				$result = $key;   
				break;   
			} else {   
				$proSum -= $proCur;   
			}         
		}   
		unset ($proArr);    
		return $result;   
	}   

}