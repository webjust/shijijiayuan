<?php

/**
 * 评论基类
 * 用户添加评论，及评论列表列表 控制器均需要继承此类
 *
 * @stage 7.1
 * @package Action
 * @subpackage Home
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-04-23
 * @license saas
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CommentAction extends HomeAction {

    /**
     * 控制器初始化
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-23
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * @param 添加商品评论
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.1
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
                    $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
                    $TelExp = '/^(\(((010)|(021)|(0\d{3,4}))\)( ?)([0-9]{7,8}))|((010|021|0\d{3,4}))([- ]{1,2})([0-9]{7,8})$/A';
                    if(preg_match($RegExp,$gcom_email) || preg_match($TelExp,$gcom_email)){
                        $gcom_email = encrypt($gcom_email);
                    }
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
                    $params['gcom_phone'] = encrypt($memberinfo['m_mobile']);
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
     * 加载商品评论页
     *
     */
    public function getCommentPage(){
        $int_g_id = (int)$this->_request('gid');
		$noticeObj = D('GoodsComments');
        $comment = D('SysConfig')->getCfgByModule('goods_comment_set',1);
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT',1);
        $where['gcom_status']    = '1';
        $where['g_id']  = $int_g_id;
        $where['gcom_parentid'] = 0;
        $where['u_id'] = 0;
        $where['gcom_verify'] = 1;
		$where['gcom_star_score'] = array('gt',0);

        $page_no = max(1,(int)$this->_request('p','',1));
        // var_dump($this->_get());
        // echo $_POST['p'];
        // echo "<br>";
        // $page_no = $this->_get('p')?$this->_get('p'):1;
        // echo $page_no;

        $page_size = 10;
        $is_type = (int)$this->_request('type');
        if(empty($is_type)){
            $is_type = 0;
        }
        if($is_type==1){//好评
            $where['gcom_star_score'] = 100;
        }elseif($is_type==2){//中评
            $where['gcom_star_score'] = array("in","60,80");
        }elseif($is_type==3){//差评
            $where['gcom_star_score'] = array("in","20,40");
        }
        $this->assign("type",$is_type);
        $obj_query = $noticeObj->alias('gc')
            ->field('gcom_id,gc.m_id,gcom_content,gcom_order_id,
        gcom_mbname,gcom_title,gcom_ip_address,gcom_verify,gcom_contacts,gcom_create_time,
        gcom_star_score,gcom_parentid,gcom_pics,m_head_img,cr_id')
            ->join('fx_members as m on m.m_id = gc.m_id')
            ->where($where)
            ->order('gcom_update_time desc')
            ->page($page_no,$page_size);
           // ->select();
		$data = D('Gyfx')->queryCache($obj_query,'',60);
			//dump($data);die();
		//echo $noticeObj->getLastSql();die;
        foreach ($data as $key=>$val){
			$data[$key]['gcom_mbname'] = csubstr($val['gcom_mbname'],8);
           // $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $val['gcom_id']))->find();
	        $parent_data = D("Gyfx")->selectOneCache("goods_comments","gcom_id,gcom_content,gcom_create_time,gcom_contacts",array("gcom_parentid" => $val['gcom_id']),null,null,null,60);		
            $data[$key]['reply'] = $parent_data;
			//再次评论
			$recomment_where = $where;
			unset($recomment_where['gcom_star_score']);
			$recomment_where['gcom_star_score'] = '';
			$recomment_where['m_id'] = $data[$key]['m_id'];
			$recomment_where['gcom_order_id'] = $data[$key]['gcom_order_id'];
			//$recomment_where['gcom_id'] = $data[$key]['gcom_id'];
           // $recomment_data = $noticeObj->field('gcom_id,gcom_order_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics')->where($recomment_where)->select();
            $recomment_data = D("Gyfx")->selectAllCache("goods_comments","gcom_id,gcom_content,gcom_create_time,gcom_contacts,gcom_pics",$recomment_where,null,null,null,60);			
            //追评回复
			if(!empty($recomment_data)){
				foreach($recomment_data as $sub_key=>$rdata){
					//$sub_parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $rdata['gcom_id']))->find();
                    $sub_parent_data = D("Gyfx")->selectOneCache("goods_comments","gcom_id,gcom_content,gcom_create_time,gcom_contacts",array("gcom_parentid" => $rdata['gcom_id']),null,null,null,60);					
					$recomment_data[$sub_key]['reply'] = $sub_parent_data;					
				}	
			}
			$data[$key]['recomment'] = $recomment_data;	
			//$score_total_str=$score_total_str+$val['gcom_star_score'];

            if($val['cr_id']) {
                $cr_path = D('CityRegion')->where(array('cr_id'=>$val['cr_id']))->getField('cr_path');
                $ary_cr_path = explode('|', $cr_path);
                $cr_name = D('CityRegion')->where(array('cr_id'=>$ary_cr_path['1']))->getField('cr_name');
                $data[$key]['cr_name'] = $cr_name;
            }
        }		
		//获取总评论和
		//$score_info = $noticeObj->where($where)->field('sum(gcom_star_score) as total_score')->find();
		$score_info = D('Gyfx')->selectOneCache('goods_comments','sum(gcom_star_score) as total_score', $where, $ary_order=null,60);
		$score_total_str = $score_info['total_score'];
        $count = $noticeObj->where($where)->count();
		$count = D('Gyfx')->getCountCache('goods_comments',$where,60);
        $positive_where = $where;
        $positive_where['gcom_star_score'] = array('egt', 60);
		//$good_count = $noticeObj->where($positive_where)->count();    //好评数
		$good_count = D('Gyfx')->getCountCache('goods_comments',$positive_where,60);
        $where['gcom_pics'] = array('neq', null);
		//$pic_count = $noticeObj->where($where)->count();      //晒单数
		$pic_count = D('Gyfx')->getCountCache('goods_comments',$where,60);
		$score_str = $score_total_str/$count;
		$score = sprintf("%.2f",($score_str/100)*5);
		//获取评论统计
       // $ary_gcs = M('goods_comment_statistics')->where(array(
         //   'g_id'=>$int_g_id
        //))->find();
		$ary_gcs = D('Gyfx')->selectOneCache('goods_comment_statistics','', array(
            'g_id'=>$int_g_id
        ), $ary_order=null,60);
        //dump(M('goods_comment_statistics')->getLastSql());die;
        $obj_page = new Pager($count, $page_size);
		$obj_good_page = new Pager($good_count, $page_size);
		$obj_pic_page = new Pager($pic_count, $page_size);
        $page = $obj_page->showArr();
		$good_page = $obj_good_page->showArr();
		$pic_page = $obj_pic_page->showArr();
        $tpl = "./Public/Tpl/" . CI_SN . "/" . $config['GY_TEMPLATE_DEFAULT'] . "/comment.html";
        $type = explode(',',$comment['comment_show_condition']);
        $comment['type'] = $type[0];
        $page['nowPage'] = (int)$this->_post('p');
		$good_page['nowPage'] = (int)$this->_post('p');
		$pic_page['nowPage'] = (int)$this->_post('p');
		$this->assign('g_id',$int_g_id);
		$this->assign('ary_gcs',$ary_gcs);
        $this->assign("comment",$comment);
        $this->assign("count",$count);
		$this->assign("score",$score);
		$this->assign("score_str",$score_str);
        $this->assign('data',$data);
		$this->assign('page',$page);
        $this->display($tpl);

    }

    /**
     * 单独获取评论总数
     */
    public function getCommentCount(){
        $int_g_id = $this->_post('gid');
        $noticeObj = D('GoodsComments');
        $where['gcom_status']    = '1';
        $where['g_id']  = $int_g_id;
        $where['gcom_parentid'] = 0;
        $where['u_id'] = 0;
        $where['gcom_verify'] = 1;
		$where['gcom_star_score'] = array('gt',0);
        $count = $noticeObj->where($where)->count();
		
        $this->ajaxReturn(array('status' => 1, 'count' => $count));
    }

}
