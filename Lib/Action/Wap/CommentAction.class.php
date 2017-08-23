<?php

/**
 * 评论基类
 * 用户添加评论，及评论列表列表 控制器均需要继承此类
 *
 * @stage 7.5
 * @package Action
 * @subpackage Wap
 * @author Nick <shanguangkun@guanyisoft.com>
 * @date 2014-05-27
 * @license saas
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CommentAction extends WapAction {

    /**
     * 控制器初始化
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-27
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * @param 添加商品评论
     * @author Nick <shanguangkun@guanyisoft.com>
     * @version 7.5
     * @modify 2013-04-23
     * @return int 
     * @desc 暂时支持登录会员一个商品一个会员只能评论一次
     */
    public function addComment() {
        //后台评论设置
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
//        echo "<pre>";print_r($_GET);exit;
        $comment['comment_show_condition'] = explode(',', $comment['comment_show_condition']);
       // print_r($comment);die;
        $ary_res = array('success' => 0, 'msg' => L('COMMENT_ERROR'));
        $point = M('PointConfig', C('DB_PREFIX'), 'DB_CUSTOM');
        $point->startTrans();
        $m_id = $this->_post('m_id');
        if (empty($m_id)) {
            $member = session("Members");
            $memberinfo = D("Members")->where(array('m_id' => $member['m_id']))->find();
            $m_id = $member['m_id'];
        }
        if ($comment['comments_switch'] != '1') {
            $ary_res = array('success' => 0, 'msg' => L('COMMENT_SWITCH'));
        } else {
            if (empty($memberinfo) && ($comment['comments_revok'] != '1' )) {
                $ary_res = array('success' => 0, 'msg' => L('COMMENT_LOGIN'));
            } else {
                $params = $this->_request();
                if ($comment['comments_revok'] == '3') {
                    if (!empty($params['g_id'])) {
                        $list = M('orders')->where(array('fx_orders.m_id'=>$m_id,'fx_orders.o_pay_status'=>1,'fx_orders_items.g_id'=>$params['g_id']))->join('fx_orders_items on fx_orders_items.o_id=fx_orders.o_id')->count();
                        if (empty($list)) {
                            $this->ajaxReturn(array('msg' => L('COMMENT_BUG'), 'success' => '0'));
                            return;
                        }
                    }
                }
                if (!empty($memberinfo)) {
                    $params['m_id'] = $memberinfo['m_id'];
                    $params['gcom_mbname'] = $memberinfo['m_name'];
                    $str_m_email = D('Members')->where(array('m_id'=>$member['m_id']))->getField('m_email');
					$gcom_email = $this->_request('gcom_email');
					if(!empty($gcom_email)){
						$params['gcom_email'] = $gcom_email;
					}else{
						if(!empty($str_m_email)){
							$params['gcom_email'] = $memberinfo['m_email'];
						}else{
							$params['gcom_email'] = '暂无';
						}
					}
                    /*if(!empty($str_m_email)){
                        $params['gcom_email'] = $memberinfo['m_email'];
                    }else{
                        $params['gcom_email'] = $this->_request('gcom_email');
                    }*/
                    $params['gcom_phone'] = $memberinfo['m_mobile'];
                    $params['gcom_qq'] = $memberinfo['m_qq'];
                }
                    $params['gcom_ip_address'] = $this->getIp();
                    $params['gcom_create_time'] = date("Y-m-d H:i:s");
                    $params['gcom_update_time'] = date("Y-m-d H:i:s");
                $is_exist = D("GoodsComments")->where(array('g_id' => $params['g_id'], 'm_id' => $params['m_id']))->count();
                if ($is_exist > 0) {
                    $ary_res = array('success' => 0, 'msg' => L('COMMENT_HAVE'));
                } else {
                    if(!empty($comment['comment_show_condition'][0]) && $comment['comment_show_condition'][0] == '1'){
                        $params['gcom_verify'] = '1';
                    }
                    $res = D("GoodsComments")->data($params)->add();
//                    echo "<pre>";print_r($params);exit;
//                    dump(D("GoodsComments")->getLastSql());die();
                    if ($res) {
                        /* if(!empty($params['gcom_verify']) && $params['gcom_verify'] == '1' && !empty($params['m_id'])){
                            $ary_point = M('PointConfig', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('sc_id'=>'1'))->find();
                            $point_data = array(
                                'm_id'  =>  $params['m_id'],
                                'reward_point'  =>  $ary_point['recommend_points'],
                                'memo'  =>  '会员评论',
                            	'u_create' => date('Y-m-d H:i:s'),
                                'type'  => '3'
                            );
                            $ary_result = M('PointLog', C('DB_PREFIX'), 'DB_CUSTOM')->add($point_data);
                            if(FALSE !== $ary_result){
								$ary_toalPoint = M('members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$memberinfo['m_id']))->field('total_point')->find();
                            	$bool_res = M('members', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$memberinfo['m_id']))->data(array('total_point'=>($ary_toalPoint['total_point']+$ary_point['recommend_points']),'m_update_time'=>date('Y-m-d H:i:s')))->save();
                            	//dump(M('members', C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());die();
                            	if($bool_res){
                            		$_SESSION['Members']['total_point'] = $ary_toalPoint['total_point']+$ary_point['recommend_points'];
                            		$point->commit();
                            		$ary_res = array('success' => 1, 'msg' => L('COMMENT_SUCCESS') . $comment['comment_show_condition'][1]);
                            	}else{
                            		$point->rollback();	
                            		$ary_res = array('success' => 0, 'msg' => "更新会员积分失败");
                            	}
                            }else{
                                $point->rollback();
                                $ary_res = array('success' => 0, 'msg' => "积分获取失败");
                            }
                        }else{ */
                            $point->commit();
                            $ary_res = array('success' => 1, 'msg' => L('COMMENT_SUCCESS') . $comment['comment_show_condition'][1]);
                      //  }
                        
                    }else{
                        $point->rollback();
                        $ary_res = array('success' => 0, 'msg' => "评论失败");
                    }
                }
            }
        }
        $this->ajaxReturn(array('data' => $ary_res['data'], 'msg' => $ary_res['msg'], 'success' => $ary_res['success']));
    }

    /**
     * @param 得到用户留言时IP
     * @author Nick <shanguangkun@guanyisoft.com>
     * @version 7.5
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
     * 加载商品评论页
     *
     */
    public function getCommentPage(){
        $int_g_id = $this->_get('g_id');
		if(empty($int_g_id)){$int_g_id = $this->_request('gid');}
		
        if(!$int_g_id){
            $this->error("访问地址错误！");
            die;
        }
        $noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
//        dump($comment);die;
        $where['gcom_status']    = '1';
        $where['g_id']  = $int_g_id;
        $where['gcom_parentid'] = 0;
        $where['gcom_star_score']= array('neq','');//去掉追加评论 追加评论分数为0
        $where['u_id'] = 0;
        $where['gcom_verify'] = 1;
        $page_no = max(1,(int)$this->_request('p'));
        $page_size = $comment['list_page_size'];
        $data = $noticeObj->field('m.m_name,fx_goods_comments.*')  
                        ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
                        ->where($where)
                        ->order('gcom_update_time desc')
                        ->page($page_no,$page_size)
                        ->select();
        
        $good_count = 0;
        $normal_count = 0;
        $bad_count = 0;
        $all_count = 0;
        foreach ($data as $key=>$val){
            switch($val['gcom_star_score']){
                case 60:
                    $good_count++;
                    break;
                case 80:
                    $good_count++;
                    break;
                case 100:
                    $good_count++;
                    break;
                case 40:
                    $normal_count++;
                    break;
                case 20:
                    $bad_count++;
                    break;
            }
            $all_count++;
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')
                ->where(array("gcom_parentid" => $val['gcom_id']))->find();

            $data[$key]['reply'] = $parent_data;
        }
		
        //好评率
        $score_g = ($good_count/$all_count)*100;
        $score_n = ($normal_count/$all_count)*100;
        $score_b = ($bad_count/$all_count)*100;
        $score = '0';
        if($score_g == '100'){
            $score = '5';
        }else if($score_g >='80' && $score_g <'100'){
            $score = '4';
        }else if($score_g >='60' && $score_g <'80'){
            $score = '3';
        }else if($score_g >='40' && $score_g <'60'){
            $score = '2';
        }else if($score_g >='20' && $score_g <'40'){
            $score = '1';
        }

        $type = explode(',',$comment['comment_show_condition']);
        $comment['type'] = $type[0];
        $count = $noticeObj->field('m.m_name,fx_goods_comments.*')
            ->join("fx_members as m on m.m_id=fx_goods_comments.m_id")
            ->where($where)
            ->count();
        $obj_page = new Pager($count, $page_size);
        $page = $obj_page->showArr();
        $page['nowPage'] = $page_no;

		//遍历获取追加评论
		foreach ($data as $k=>$v){
			$data[$k]['m_name'] = csubstr($v['m_name'],6);
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $v['gcom_id']))->find();
            $data[$k]['reply'] = $parent_data;
			//再次评论
			$recomment_where = $where;
			unset($recomment_where['gcom_star_score']);
			$recomment_where['gcom_star_score'] = '';
			$recomment_where['m_id'] = $data[$k]['m_id'];
			$recomment_where['gcom_order_id'] = $data[$k]['gcom_order_id'];
            $recomment_data = $noticeObj->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();
			
            //追评回复
			if(!empty($recomment_data)){
				foreach($recomment_data as $sub_key=>$rdata){
					$sub_parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
					$recomment_data[$sub_key]['reply'] = $sub_parent_data;					
				}	
			}
			$data[$k]['recomment'] = $recomment_data;	

            if($v['cr_id']) {
                $cr_path = D('CityRegion')->where(array('cr_id'=>$v['cr_id']))->getField('cr_path');
                $ary_cr_path = explode('|', $cr_path);
                $cr_name = D('CityRegion')->where(array('cr_id'=>$ary_cr_path['1']))->getField('cr_name');
                $data[$k]['cr_name'] = $cr_name;
            }
        }
		
		$str_where['gcom_status']    = '1';
        $str_where['g_id']  = $int_g_id;
        $str_where['gcom_parentid'] = 0;
        $str_where['u_id'] = 0;
        $str_where['gcom_verify'] = 1;
		$str_where['gcom_star_score'] = array('gt',0);
		$score_total_str = $noticeObj->where($str_where)->sum('gcom_star_score');
        $str_count = $noticeObj->where($str_where)->count();
		$score_str = $score_total_str/$str_count;
		$str_score = round(($score_str/100)*5);
		$this->assign("str_score",$str_score);
        
        $tpl = $this->wap_theme_path . "comment.html";
		
		$this->assign('g_id',$int_g_id);
        $this->assign("comment",$comment);
        $this->assign("all_count",$all_count);
        $this->assign("good_count",$good_count);
        $this->assign("normal_count",$normal_count);
        $this->assign("bad_count",$bad_count);
        $this->assign("score_g",$score_g);
        $this->assign("score_n",$score_n);
        $this->assign("score_b",$score_b);
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->display($tpl);
    }

}