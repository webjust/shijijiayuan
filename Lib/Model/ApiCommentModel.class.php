<?php

/**
 * 商品评论
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-11-1
 */

class ApiCommentModel extends GyfxModel{

	private $result; 	// 返回结果
	// private $member; 	// 会员表

	// 自动执行
	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10010', 		// 评论错误初始码
			'sub_msg' => '评论错误', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
		// $this->member = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
	}
	//update by wangguibin 2015-09-01
	public function InsertComment($params){
		//echo '<pre>';print_r($params);die();
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '添加评论失败!';
		//后台评论设置
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set');
        $comment['comment_show_condition'] = explode(',', $comment['comment_show_condition']);
        $ary_res = array('success' => 0, 'msg' => L('COMMENT_ERROR'));
        $point = M('', C('DB_PREFIX'), 'DB_CUSTOM');
        $point->startTrans();
        $o_id = $params['o_id'];

        $memberinfo = D("Members")->where(array('m_id' => $params['m_id']))->find();
        $m_id = $memberinfo['m_id'];
        if ($comment['comments_switch'] != '1') {
        	$this->result['sub_msg'] = '未开启商品评论功能';
        } else {
            if (empty($memberinfo) && ($comment['comments_revok'] != '1' )) {
            	$this->result['sub_msg'] = '会员登录之后才可评论';
            }else {
				$insert_data = $params;
                if ($comment['comments_revok'] == '3') {
                    if (!empty($params['g_id'])) {
                        /*
                         * 评论条件过滤
                         * 1，商品在该订单里
                         * 2，订单已确认收货
                         * 3，订单属于该会员
                         */
                        $list = M('orders')->where(array(
                            'fx_orders.m_id'=>$m_id,
                            'fx_orders.o_id'=>$o_id,
                            'fx_orders.o_status'=>array('neq', 2),
                            'fx_orders_items.g_id'=>$params['g_id']
                        ))->join('fx_orders_items on fx_orders_items.o_id=fx_orders.o_id')
                        ->count();
                        if (empty($list)) {
                        	$this->result['sub_msg'] = '购买过此商品的会员才可评价';
                        	return $this->result;
                        }
                    }else{
                    	$this->result['sub_msg'] = '未知的商品!';
                        return $this->result;
                    }
                }
                if (!empty($memberinfo)) {
                    $insert_data['m_id'] = $m_id;
                    $insert_data['gcom_mbname'] = $memberinfo['m_name'];
                    $str_m_email = D('Members')->where(array('m_id'=>$m_id))
                        ->getField('m_email');
					$gcom_email = $params['gcom_email'];
					if(empty($gcom_email)){
						if(!empty($str_m_email)){
							$insert_data['gcom_email'] = $memberinfo['m_email'];
						}else{
							$insert_data['gcom_email'] = '暂无';
						}
					}
                    $insert_data['gcom_phone'] = $memberinfo['m_mobile'];
                    $insert_data['gcom_qq'] = $memberinfo['m_qq'];
                }

                $insert_data['gcom_ip_address'] = $this->getIp();
                $insert_data['gcom_create_time'] = date("Y-m-d H:i:s");
                $insert_data['gcom_update_time'] = date("Y-m-d H:i:s");
                $is_exist = D("GoodsComments")->where(array(
                    'g_id' => $insert_data['g_id'],
                    'm_id' => $insert_data['m_id'],
                    'gcom_order_id' => $insert_data['o_id']
                ))->count();
				$insert_data['gcom_order_id'] = $insert_data['o_id'];
				//是否是追加评论
				$is_zj = 0;
                if ($is_exist > 0) {
					if($comment['again_comments_switch'] != 1){
						$this->result['sub_msg'] = '您已评论过,不能再次评论';
						 return $this->result;
					}else{
						$is_zj = 1;
						unset($insert_data['gcom_star_score']);
						$insert_data['gcom_title'] = '[追加评论]'.$insert_data['gcom_title'];
					}
                } 
			   if(!empty($comment['comment_show_condition'][0]) && $comment['comment_show_condition'][0] == '1'){
					$insert_data['gcom_verify'] = '1';
				}
				$RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
				$TelExp = '/^(\(((010)|(021)|(0\d{3,4}))\)( ?)([0-9]{7,8}))|((010|021|0\d{3,4}))([- ]{1,2})([0-9]{7,8})$/A';
				if(preg_match($RegExp,$params['gcom_email']) || preg_match($TelExp,$params['gcom_email'])){
					$insert_data['gcom_email'] = encrypt($params['gcom_email']);
				}
				if(preg_match($RegExp,$params['gcom_phone']) || preg_match($TelExp,$params['gcom_phone'])){
					$insert_data['gcom_phone'] = encrypt($params['gcom_phone']);
				}
				$res = D("GoodsComments")->data($insert_data)->add();
				if ($res) {
					if($is_zj == 0){
						M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
							'o_id' => $insert_data['o_id']
						))->data(array(
							'is_evaluate' => '1'
						))->save();
						//添加到评论统计表
                        $insert_data['gcom_id'] = $res;
						$insert_data['gcom_status'] = 1;
                        $add_res = D('GoodsComments')->addGoodsCommentStatistics($insert_data);
                        if(!$add_res) {
                            $point->rollback();
                            $this->result['sub_msg'] = '发表评论失败，添加到评论统计表失败!';
							 return $this->result;
                        }					
					}
					$point->commit();
					if($is_zj == 0){
						//判断是否设置签到赠送积分					
						$point_config = D('Gyfx')->selectOneCache('point_config');
						if(!empty($point_config['recommend_points'])){
							$reward_point = intval($point_config['recommend_points']);
							$ary_reward_result = D('PointConfig')->setMemberRewardPoint($reward_point, $m_id,3);
							if (!$ary_reward_result ['result']) {
								 $this->result['sub_msg'] = $this->result['sub_msg'];
								 return $this->result;									 
							}
							//更新会员SESSION信息
							$_SESSION['Members']['total_point'] = $memberinfo['total_point']+$reward_point;
						}
					}				
					$this->result['sub_msg'] = (isset($comment['comment_show_condition'][1]) && !empty($comment['comment_show_condition'][1])) ? $comment['comment_show_condition'][1] : '评论成功!';
					$this->result['status']  = true;
					$this->result['code']    = '10011';
					$info = D('GoodsComments')->field('gcom_id,gcom_title,gcom_content,gcom_star_score')->where(array('gcom_id'=>$res))->find();
					$this->result['info']    = $info;
				}else{
					$point->rollback();
					$this->result['sub_msg'] = '发表评论失败!';
				}
                
            }
        }
        return $this->result;
	}
	
    /**
     * @param 得到用户留言时IP
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.1
     * @modify 2013-04-23
     * @return int 
     */
    public function getIp() {
        $ip = "";
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
	
	/**
     * @ 获得评论列表
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.8.6
     * @modify 2015-08-18
     * @return array()
     */
	public function getCommentList($where=array(),$field='*',$order,$limit){
        $data = D('GoodsComments')->field($field)->where($where)->order($order)->limit(($limit['no']-1)*$limit['size'],$limit['size'])->select();
		
        foreach ($data as $key=>$val){
            $parent_data = D('GoodsComments')->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $val['gcom_id']))->find();
            $data[$key]['reply'] = $parent_data;
			//再次评论
			$recomment_where = $where;
			unset($recomment_where['gcom_star_score']);
			$recomment_where['gcom_star_score'] = '';
			$recomment_where['m_id'] = $data[$key]['m_id'];
			$recomment_where['gcom_order_id'] = $data[$key]['gcom_order_id'];
            $recomment_data = D('GoodsComments')->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();
            //追评回复
			if(!empty($recomment_data)){
				foreach($recomment_data as $sub_key=>$rdata){
					$sub_parent_data = D('GoodsComments')->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
					$recomment_data[$sub_key]['reply'] = $sub_parent_data;					
				}	
			}
			$data[$key]['recomment'] = $recomment_data;				
        }
		 return $data;
	}
	
	/**
     * @ 商品评论星星数
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @version 7.8.6
     * @modify 2015-08-19
     * @return array()
     */
	public function getCommentStars($where=array()){
		//获取总评论和
		 $score_total_str =0;
		 $score_total_str = D('GoodsComments')->where($where)->sum('gcom_star_score');
         $count = D('GoodsComments')->where($where)->count();
		 $good_count = D('GoodsComments')->where($good_comments)->count();    //好评数
		 $pic_count = D('GoodsComments')->where($pic_comments)->count();      //晒单数
		 $score_str = $score_total_str/$count;
		 $data['score'] = ($score_str/100)*5;
		 $data['good_count'] = $good_count;
		 $data['pic_count'] = $pic_count;
		 return $data;
	}


}