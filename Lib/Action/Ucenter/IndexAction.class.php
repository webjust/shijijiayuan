<?php

/**
 * 前台用户中心默认控制器
 * @package Action
 * @subpackage Ucenter
 * @version 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-12
 */
class IndexAction extends CommonAction {

    /**
     * 用户登录后的默认页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-19
     */
    public function index(){
        $this->getSubNav(4, 4, 0);
        //获取数据
        $member = session('Members');
		$member['m_balance'] = $member['m_balance'];//用户余额
        //库存报警
        $stocks = D('SysConfig')->getCfgByModule('GY_STOCK',1);
//        dump($stocks);die();
        $is_stock = "0";
        $member['is_stock'] = 0;
        if($stocks['OPEN_STOCK'] == '1'){
        	if(!empty($stocks['USER_TYPE'])){
        		if($stocks['USER_TYPE'] == 'all'){
        			$is_stock = 1;
        		}else{
        		    if(strstr($stocks['USER_TYPE'],$member['ml_id']))
					{
						$is_stock = 1;
					}        			
        		}
        	}
        }
		//判断是否开启签到
		$point_config = D('Gyfx')->selectOneCache('point_config');
		$sign_points = $point_config['sign_points'];
		$this->assign('sign_points',$sign_points);
		
		//判断是否已签到
		$ary_where = array();
		$ary_where['u_create'] = array(between,array(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')));
		$ary_where['type'] = 10;
		$ary_where['m_id'] = $member['m_id'];
		$point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
		if(!empty($point_exsit)){
			$this->assign('v_type',1);
		}
		if($is_stock == '1'){
			$member['is_stock'] = 1;
			$goods = D("ViewGoods");
			$where = array();
	        //商品是否启用
	        $where['g_status'] = 1;
	        //上架
	        $where['g_on_sale'] = 1;
	        if(!empty($stocks['STOCK_NUM'])){
	        	$where['fx_goods_info.g_stock'] = array('ELT', $stocks['STOCK_NUM']);
	        }
			//去除赠品
			$where['g_gifts'] = array('neq','1');
			
			$where['_string'] =' (g_is_combination_goods=1 and g_off_sale_time>=now() and g_on_sale_time<=now()) or (g_is_combination_goods=0 and g_is_prescription_rugs = 0)';
			//$count = $goods->where($where)->order($order)->limit($obj_page->firstRow . ',' . $obj_page->listRows)->count();
			$goodsModel = D("Goods");
			//$count_info = $goodsModel->join('fx_goods_info on fx_goods.g_id=fx_goods_info.g_id')->where($where)->field('count(fx_goods.g_id) as count')->find();
			$obj_query = $goodsModel->join('fx_goods_info on fx_goods.g_id=fx_goods_info.g_id')->where($where)->field('count(fx_goods.g_id) as count');
			$count_info = D('Gyfx')->queryCache($obj_query,'find',60);
			$count = $count_info['count'];
			$member['stock_count'] = $count;
		}else{
			$stocks['OPEN_STOCK']=0;
		}
		//dump($member);die();
		//订单总数
		$ordercount = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
		$member['order_count'] = $ordercount;
		//收藏总数
		$collect_count=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
		$member['collect_count'] = $collect_count;
		//查询8条公告
		$noticeObj = D('PublicNotice');
		$m_id = $member['m_id'];
		$ml_id = $member['ml_id'];
		$groupObj = M('related_members_group',C('DB_PREFIX'),'DB_CUSTOM');
		$mGroups = $groupObj->where(array('m_id'=>$m_id))->select();
		$mGroups = implode(',', $mGroups);
		$where = '';
		if($ml_id){
			$where .= " or ml_id={$ml_id}";
		}
		if($mGroups){
			$where .= " or mg_id in ({$mGroups})";
		}
		$obj_query = $noticeObj->field('pn_id,pn_title,pn_create_time')
						->join("inner join (select mc_id from fx_member_competence where
								(m_id = -1 or m_id={$m_id}{$where}) and mc_type=1 group by mc_id
								) as t on(fx_public_notice.pn_id=t.mc_id)")
						->where('pn_status=1')
						->order('pn_is_top desc,pn_create_time desc')
						->page(1,8);
						//->select();
		$articlelist = D('Gyfx')->queryCache($obj_query);
		//查询广告图片
		$articleobj =  M('article',C('DB_PREFIX'),'DB_CUSTOM');	
		$where = array();
    	$where['a_status'] = 1;
    	$where['cat_id'] = 12;
		$obj_query = $articleobj->field('a_id,a_title,ul_image_path')
						->where($where)
						->order('a_order desc,a_create_time desc')
						->page(1,4);
						//->select();	
		$article = D('Gyfx')->queryCache($obj_query);				
        foreach($article as &$item){
            $item['aurl'] = U('Home/Article/articleDetail', array('aid' => $item['a_id']));
        }
        //dump($article);
		$ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        //print_r($ary_point);exit;
        $valid_point = 0;//有用积分
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $member['total_point'] = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
        //显示页面
        $this->assign('art',$article);
        $this->assign('articlelist',$articlelist);
        $this->assign('info',$member);
//        echo "<pre>";print_r($stocks);exit;
        $this->assign("stock",$stocks);
        $this->display();
    }
	
	/*会员签到
	 *add by <zhuwenwei@guanyisoft.com>
	 *date 2015-11-06
	*/
    public function getMemberInfo() {
		$ary_member = session('Members');
		if(!empty($ary_member['m_id'])){
			//判断是否已签到
			$ary_where = array();
			$ary_where['u_create'] = array(between,array(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')));
			$ary_where['type'] = 10;
			$ary_where['m_id'] = $ary_member['m_id'];
			$point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
			if(!empty($point_exsit)){
				$this->assign('v_type',1);
			}
			//余额积分重新获取一下
			$member_info = D('Gyfx')->selectOne('Members','m_balance,total_point,freeze_point', array('m_id'=>$ary_member['m_id']));
			$ary_member['m_balance'] = $member_info['m_balance'];
			$_SESSION['Members']['m_balance'] = $ary_member['m_balance'];
			$_SESSION['Members']['total_point'] = $ary_member['total_point'];
			$_SESSION['Members']['freeze_point'] = $ary_member['freeze_point'];
			$ary_member['total_point'] = $member_info['total_point']-$member_info['freeze_point'];
            if($ary_member['total_point']<0){
				$ary_member['total_point'] = 0;
			}
		}
        $this->assign('member',$ary_member);
        $this->display();
    }
	/**
     * 签到获取积分
     * @author <zhuwenwei@guanyisoft.com>
     * @date 2015-11-6
     */
	public function doSignOn(){
        $ary_post = $this->_post();
		$member = session("Members");
		if(!empty($member['m_id'])){
			//判断是否设置签到赠送积分
			$point_config = D('Gyfx')->selectOneCache('point_config');
			if(empty($point_config['sign_points'])){
				$this->ajaxReturn(array('status'=>'0','info'=>"未开启积分签到功能"));exit;
			}
			//判断是否已签到
			$ary_where = array();
			$ary_where['u_create'] = array(between,array(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')));
			$ary_where['type'] = 10;
			$ary_where['m_id'] = $member['m_id'];
			$point_exsit = D('Gyfx')->selectOne('point_log','log_id', $ary_where);
			if(!empty($point_exsit)){
				$this->ajaxReturn(array('status'=>'0','info'=>"已签到，欢迎明天再来!"));exit;
			}else{
				$ary_data = array();
				$ary_data['type'] = 10;
				$ary_data['reward_point'] = intval($point_config['sign_points']);
				$ary_data['memo'] = '会员签到';
				//事物开启
				D('')->startTrans();
				$point_res = D('PointLog')->addPointLog($ary_data, $member['m_id']);
				$add_points = $member['total_point']+$ary_data['reward_point'];
				if($point_res['status'] != '1'){
					M('')->rollback();
					$this->ajaxReturn(array('status'=>'0','info'=>"添加积分日志表失败"));exit;
				}else{
					$mem_res = D('Members')->where(array('m_id'=>$member['m_id']))->data(array('total_point'=>$add_points,'m_update_time'=>date('Y-m-d H:i:s')))->save();
					if(!$mem_res){
						D('')->rollback();
						$this->ajaxReturn(array('status'=>'0','info'=>"更新会员积分信息失败"));exit;
					}
				}
				//更新会员SESSION信息
				$_SESSION['Members']['total_point'] = $add_points;
				D('')->commit();
				$this->ajaxReturn(array('status'=>'1','info'=>"已签到成功送".$ary_data['reward_point']."积分"));
			}
		}else{
			$this->ajaxReturn(array('status'=>'0','info'=>L('NO_LOGIN')));
		}
    }
    ### 以下为测试方法，部署时请删除 #############################################

    public function test(){
        $price = D('Price')->getPrice(1,1);
        //dump($price);
        $this->display();
    }

    public function testData(){
        $m = new TestDataModel();
        $this->show('OK');
    }

    public function getCheck(){
        if($this->_get('email') == 'zuojianghua@gmail.com'){
            $this->ajaxReturn('这个Email已经被使用了');
        }else{
            $this->ajaxReturn(true);
        }
    }

    public function doTest(){
        $this->redirect('Index/test',array(), 3, '页面跳转中...');
    }

    public function sendmail(){
        $email = new Mail();
        if($email->testSendMail('28842136@qq.com')){
            $this->success('发送成功!',U('Ucenter/Index/index'));
        }else{
            $this->error('发送失败!');
        }
    }

    public function url(){
        $this->show(U('/','',true,false,true));
    }


    public function testImage(){
        //$str = 'http://localhost:8070/Public/images/201206251533591.jpg';
        //$data = urlencode(base64_encode(gzcompress($str)));

        //import('ORG.Util.Image');

        //$data = Image::thumb(APP_PATH.'/Public/images/201206251533591.jpg');
        //dump($data);exit;

        //$this->display();

        $str = '/Public/images/201206251533591.jpg';
        //$data = showImage($str,100,100);
        //dump($data);
        $this->assign('imagesrc',$str);
        $this->display();

    }

}