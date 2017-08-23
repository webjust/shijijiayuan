<?php
/**
 * 后台留言评论管理控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-9
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GuestbookAction extends AdminAction{
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
        $this->setTitle(' - '.L('MENU1_5'));
    }
    /**
     * 商品评论列表
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-09
     */
    public function pageProductsList(){
    	$noticeObj = D('GoodsComments');
    	$content = trim($this->_post('content'));
		$title = trim($this->_post('title'));
		$mbname = trim($this->_post('mbname'));
		$verify = trim($this->_post('verify'));
    	$where = array();
    	if($content){
    		$where['gcom_content'] = array('LIKE', '%' . $content . '%');
    	}
    	if($title){
    		$where['gcom_title'] = array('LIKE', '%' . $title . '%');
    	}	
    	if($mbname){
    		$where['gcom_mbname'] = array('LIKE', '%' . $mbname . '%');
    	}	
    	if(isset($verify) && $verify !='All' && !empty($verify)){
    		$where['gcom_verify'] = intval($verify);
    	}{
			$verify = 'All';
		}
        //当天的
        if (!empty($ary_get['today']) && ($ary_get['today'] == '1')) {
        	//$ary_where['gcom_create_time'] = array('egt',date('Y-m-d 00:00:00'));
        }
    	$where['gcom_status']    = '1';
        $where['gcom_parentid'] = 0;
        $where['u_id'] = 0;
		//$where['gcom_star_score'] = array('gt',0);
    	$where['g_id']  = array('neq',0);
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $noticeObj->field('gcom_id,m_id,gcom_mbname,gcom_title,gcom_ip_address,gcom_verify,gcom_email,gcom_content,gcom_create_time,gcom_pics')
						->where($where)
						->order('gcom_update_time desc')
						->page($page_no,$page_size)
						->select();
						//dump($list);die();
		foreach ($list as $key=>$val){
            $parent_data = $noticeObj->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $val['gcom_id']))->find();
            $list[$key]['reply'] = $parent_data;
           // $list[$key]['gcom_email'] = decrypt($val['gcom_email']);
            $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
            if(preg_match($RegExp,$list[$key]['gcom_email'])){
                 $list[$key]['gcom_email'] = vagueMobile($list[$key]['gcom_email']);
            }
        }
		$count = $noticeObj->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('list', $list);    //赋值数据集
        $this->assign('page', $page);    //赋值分页输出
        $this->assign('content',$content);
		$this->assign('mbname',$mbname);
		$this->assign('title',$title);
		$this->assign('verify',$verify);
        $this->getSubNav(2,5,20);
        $this->display();
    }
    /**
     * 商品评论设置
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-09
     */
    public function pageProductsSetting(){
    	$cfg = D('SysConfig')->getCfgByModule('goods_comment_set');
    	$cfg['comment_show_condition'] = explode(',',$cfg['comment_show_condition']);
    	$this->assign('cfg',$cfg);
    	$this->getSubNav(2,5,10);
		$this->display();
    }

    public function doPageProductsSetting(){
		$data = $this->_post();
		$data['comment_show_condition'] = $data['comment_show_condition_radio'] . ',' . $data['comment_show_condition_text'];
    	$cfgObj = D('SysConfig');
		$cfgObj->setConfig('goods_comment_set','show_nums',$data['show_nums'],'商品评论页共显示多少条');
		$cfgObj->setConfig('goods_comment_set','list_page_size',$data['list_page_size'],'评论列表页每页显示多少条');
		$cfgObj->setConfig('goods_comment_set','comments_switch',$data['comments_switch'],'是否开启商品评论功能，0不开启；1，开启');
		$cfgObj->setConfig('goods_comment_set','comments_revok',$data['comments_revok'],'发表评论权限，1：所有；2：会员；3：购买过此商品的会员；');
		$cfgObj->setConfig('goods_comment_set','comments_default_content',$data['comments_default_content'],'默认评论内容');
		$cfgObj->setConfig('goods_comment_set','comment_need_verify_code',$data['comment_need_verify_code'],'评论时是否需要输入验证码');
		$cfgObj->setConfig('goods_comment_set','comment_show_condition',$data['comment_show_condition'],'显示条件和提示用语。');
		$cfgObj->setConfig('goods_comment_set','again_comments_switch',$data['again_comments_switch'],'是否开启追加评论');
		$cfgObj->setConfig('goods_comment_set','comments_showpic_switch',$data['comments_showpic_switch'],'是否开启晒单');
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品评论设置",serialize($data)));
		$this->success('操作成功');
    }
    /**
     * 商品评论删除
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-09
     */
    public function doProductsDel(){
		$gcid = intval($this->_get('gcid'));
		$commentObj = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM');
		$commentObj->where('gcom_id='.$gcid)->setField(array('gcom_status'=>0,'gcom_update_time'=>date('Y-m-d H:i:s')));
		$this->success('操作成功',U('Admin/Guestbook/pageProductsList'));
    }
    /**
     * 商品评论审核
     * @author lf <liufeng@guanyisoft.com>
     * @date 2013-01-17
     */
    public function doProductsAudit(){
		$gcid = intval($this->_get('gcid'));
		$status = intval($this->_get('verify'));
        $m_id = intval($this->_get('m_id'));
		$commentObj = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM');
		$commentObj->where('gcom_id='.$gcid)->setField(array('gcom_verify'=>$status,'gcom_update_time'=>date('Y-m-d H:i:s')));
        if($m_id>0) {
            //判断是否开启积分
            $obj_point = D('PointConfig');
            if(null !== $int_point) {
                //评论审核送积分
                $int_point = $obj_point->getConfigs('recommend_points');
                //晒单审核送积分
                $show_point = $obj_point->getConfigs('show_recommend_points');

                if(is_numeric($int_point) && $int_point>0){
                    $int_flag = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->setInc('total_point',intval($int_point));
                    if($int_flag) {
                        //插入积分日志表
                        $ary_temp = array(
                                    'type'=>3,
                                    'consume_point'=> 0,
                                    'reward_point'=> intval($int_point),
                                    );
                        D('PointLog')->addPointLog($ary_temp,$m_id);
                    }
                }

                $ary_data = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gcom_id'=>$gcid))->field('gcom_pics')->find();
                if(is_numeric($show_point) && $show_point>0 && !empty($ary_data['gcom_pics'])){
                    $res_show_point = D('PointConfig')->setMemberRewardPoints($show_point,$m_id,15);
                    if(!$res_show_point['result']){
                        $this->error($res_show_point['message']);
                    }
                }
            }
        }
        $this->success('操作成功',U('Admin/Guestbook/pageProductsList'));
    }
    
    /**
     * 商品评论设置ajax打开页面
     * @author WangHaoYu
     * @date 2013-08-31
     */
    public function setGoodComment() {
        $gcid = intval($this->_post('gcid'));
		$comments_info = D('GoodsComments')->where(array('gcom_id'=>$gcid))->field('gcom_order_id,gcom_content,g_id')->find();
        if(isset($gcid)){
			$where['gcom_star_score'] = array('gt',0);
			$where['g_id'] = array('eq',$comments_info['g_id']);
			$where['gcom_order_id'] = array('eq',$comments_info['gcom_order_id']);
            $ary_data = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->field('gcom_id,u_id,g_id,gcom_content,gcom_pics,gcom_star_score')->find();
			if(!empty($ary_data)){
				$ary_data['gcom_content'] = $comments_info['gcom_content'];
			}
			$reply_data = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->field('gcom_id,gcom_content,gcom_create_time,gcom_contacts ')->where(array("gcom_parentid" => $gcid))->find();
			if(!empty($ary_data['g_id'])){
				$good_data = D('Gyfx')->selectOneCache('goods_info','g_id,g_name,g_picture',array('g_id'=>$ary_data['g_id']), $ary_order=null);
			}
			if(!empty($ary_data['gcom_pics'])){
				$ary_data['gcom_pics'] = explode(',',$ary_data['gcom_pics']);
				foreach($ary_data['gcom_pics'] as &$pic){
					$pic = D('QnPic')->picToQn($pic);
				}
			}
		}
		//评论星星数
		$ary_data['star'] = $ary_data['gcom_star_score']/20;
		$good_data['g_picture'] = D('QnPic')->picToQn($good_data['g_picture'],60,60);
		$this->assign('good_data', $good_data);
		$this->assign('reply_data', $reply_data);
        $this->assign('ary_data', $ary_data);
        $this->display('setComment');
    }

    /**
     * 评论作废
     * @author WangHaoYu <why419163@163.com>
     * @version 7.2
     * @date 2013-08-31
     */ 
    public function cancelGoodsComment(){
        $gcid = intval($this->_get('gcid'));
        $commentObj = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where('gcom_id='.$gcid)->setField(array('gcom_verify'=>0,'gcom_update_time'=>date('Y-m-d H:i:s')));;
        $this->success('操作成功',U('Admin/Guestbook/pageProductsList'));
    }
    
    /**
     * 审核商品评论
     * @author WangHaoYu
     * @date 2013-08-31
     */
    public function doGoodsComment() {
        $ary_post = $this->_post();
        $int_uid = $this->_session('Admin');

        //判断管理员是否登录
        if(empty($int_uid)){
            echo '保存失败,您还没有登录！';
            exit;
        }
        //处理回复内容
        $str_content = htmlspecialchars(trim($ary_post['gcom_content']));
        if(empty($str_content)){
            echo '保存失败,回复内容不能为空';
            exit;
        }

        //判断参数是否合法
        $int_gcom_id = (int)$ary_post['gcom_id'];
        if($int_gcom_id <= 0){
            echo '保存失败,参数有误！';
            exit;
        }

        //判断回复是否有效
        $ary_coment = D('GoodsComments')->getGoodsComments($int_gcom_id);
        if(!is_array($ary_coment) || empty($ary_coment)) {
            echo '记录已被删除或者作废！';
            die;
        }

        //判断记录状态
        if($ary_coment['gcom_status']==0) {
            echo '评论已被删除，不可以回复！';
            die;
        }

        //判断记录是否已被作废
        if($ary_coment['gcom_verify']==0) {
            echo '评论已被作废，不可以回复！';
            die;
        }

		$parent_data = D('GoodsComments')->field('gcom_id')->where(array("gcom_parentid" => $int_gcom_id))->find();
        //存在更新
		if(!empty($parent_data['gcom_id'])){
			//往数据库添加内容 
			$ary_data = array(
				'gcom_id' =>$parent_data['gcom_id'],
				'gcom_verify' => 1,// 默认是审核通过 
				'u_id' => $int_uid,
				'gcom_content' => $str_content,
				'gcom_update_time' => date('Y-m-d H:i:s')  
			);		
			$bl_res = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gcom_id'=>$parent_data['gcom_id']))->data($ary_data)->save();			
		}else{
			//往数据库添加内容 
			$ary_data = array(
				'g_id' => $ary_post['g_id'],
				'gcom_parentid' => $int_gcom_id,
				'gcom_verify' => 1,// 默认是审核通过 
				'u_id' => $int_uid,
				'gcom_content' => $str_content,
				'gcom_create_time' => date('Y-m-d H:i:s'),
				'gcom_update_time' => date('Y-m-d H:i:s')  
			);
			$bl_res = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_data);
		}
        if(!$bl_res){
            echo '保存回复失败！';
            exit;
        }else{
			M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gcom_id'=>$int_gcom_id))->setField(array('gcom_verify'=>1));
            echo '保存或更新成功！';
        }
    }
    
}
