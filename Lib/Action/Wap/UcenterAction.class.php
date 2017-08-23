<?php
/**
 * 会员中心控制器
 * author Nick <shanguangkun@guanyisoft.com>
 * date 2014-05-29
 */
class UcenterAction extends WapAction
{
    /**
     * 订单对象
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-17
     */
    private $orders;
    /**
     *运送方式
     */
    private $logistic;
    /**
     * 地址对象
     * 
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-17
     */
    private $cityRegion;
    /*
     * 我的购物车
     */
    private $cart;
    
    public function _initialize() {
		$is_weixin = is_weixin();
        $Member = session("Members");
		
        if(empty($Member) && !isset($Member['m_id'])){
			//微信商城自动注册登录会员
			if($_SESSION['no_wx'] !=1 && $is_weixin == 1){
				$this->doCheckLogin();
			}
            //$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
		//	$this->redirect(U('/Wap/User/Login')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);

            $this->redirect(U('Wap/User/Login').'?redirect_uri=' . urlencode( ltrim($_SERVER['REQUEST_URI'],'/')));
        }
		
        parent::_initialize();
        $this->orders = D('Orders');
        $this->cityRegion = D('CityRegion');
        $this->logistic = D('Logistic');
        $this->cart = D('Cart');
    }
	
    /**
     * 微信商城登陆判断
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-06-18
     */	
	public function doCheckLogin(){
		$this->redirect(U('/Wap/User/isWeiXin')/* . '?redirect_uri=' . urlencode($string_request_uri)*/);
	}
	
    /**
     * 用户登录后的默认页面
     * @author Nick <shanguangkun@guanyisoft.com>
     * @date 2014-05-29
     */
    public function index(){
        //获取数据
        $member = session('Members');
		$ary_extend_data=D('MembersFields')->displayFields($member['m_id']);
		foreach($ary_extend_data as $akey=>$extend){
			if($extend['fields_type'] == 'file'){
				$ary_extend_data[$akey]['content'] = D('QnPic')->picToQn($ary_extend_data[$akey]['content']);
			}
		}
        $this->assign('ary_extend_data', $ary_extend_data);
		$ary_members = D('Members')->where(array('m_id'=>$member['m_id']))->field('m_balance,total_point,m_head_img')->find();//用户余额
        $member['m_balance'] = $ary_members['m_balance'];
        $member['total_point'] = $ary_members['total_point'];
        $member['m_head_img'] = $ary_members['m_head_img'];
        //库存报警
        $stocks = D('SysConfig')->getCfgByModule('GY_STOCK');
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
		if($is_stock == '1'){
			$member['is_stock'] = 1;
			$goods = D("ViewGoods");
			$where = array();
	        //商品是否启用
	        $where['g_status'] = 1;
	        //上架
	        $where['g_on_sale'] = 1;
	        if(!empty($stocks['STOCK_NUM'])){
	        	$where['g_stock'] = array('ELT', $stocks['STOCK_NUM']);
	        }
            $count_tmp = $goods->where($where)->count();
            $obj_page = new Pager($count_tmp, 10);
			$count = $goods->where($where)->limit($obj_page->firstRow . ',' . $obj_page->listRows)->count();
			$member['stock_count'] = $count;
		}
		//订单总数
		$ordercount = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
		$member['order_count'] = $ordercount;
		//收藏总数
		$collect_count=M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
		$member['collect_count'] = $collect_count;
		//评论总数
        $gcom_count = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$member['m_id']))->count();
        $member['gcom_count'] = $gcom_count;
        //我的积分
        $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        //print_r($ary_point);exit;
        $valid_point = 0;//有用积分数
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
        $member['my_point'] = $valid_point;
       //echo'<pre>';print_r($member);die;
        //显示页面
        /*$this->assign('art',$article);
        $this->assign('articlelist',$articlelist);*/
        $this->assign('info',$member);
//        echo "<pre>";print_r($stocks);exit;
        $this->assign("stock",$stocks);


        $ary_where ['fx_orders.m_id'] = $_SESSION ['Members'] ['m_id'];
		$ary_where ['fx_orders.o_status'] = 1;
        $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
        $all_orders = $orders
            ->field("fx_orders_items.oi_ship_status,
                    fx_orders.o_pay_status,
                    fx_orders_refunds.or_refund_type,
                    fx_orders.o_create_time,
                    fx_orders.o_status,
                    fx_orders.is_evaluate")
            ->join('fx_orders_items on fx_orders.o_id=fx_orders_items.o_id')
            ->join("fx_orders_refunds on fx_orders_refunds.o_id = fx_orders.o_id ")
            ->group('fx_orders.o_id')
            ->where($ary_where)
            ->select();
        $order_count = array('os_1'=>0,'os_2'=>0,'os_3'=>0,'os_4'=>0,'os_5'=>0,'os_6'=>0,'os_7'=>0,'os_0'=>0,'os_9'=>0);
        foreach($all_orders as $o){
            if($o['oi_ship_status'] === '0' && $o['o_pay_status'] == 1){
                $order_count['os_1'] ++;
            }elseif($o['oi_ship_status'] == 2){
                $order_count['os_2'] ++;
            }
            if($o['o_pay_status'] == 0){
                $order_count['os_3'] ++;
            }
            if($o['or_refund_type'] == 1 || $o['or_refund_type'] == 2){
                $order_count['os_4'] ++;
            }
            $creat_time = strtotime($o['o_create_time']);
            $three_month_time = strtotime("-3 month");
            if($creat_time > $three_month_time){
                $order_count['os_5'] ++;
            }else{
                $order_count['os_6'] ++;
            }
            if($o['o_status'] == 5 && $o['is_evaluate'] == 0){
                $order_count['os_7'] ++;
            }
            if($o['o_status'] != 5 && $o['oi_ship_status'] == 2){
                $order_count['os_9'] ++;
            }
            $order_count['os_0'] ++;
        }
        $this->assign("status_count",$order_count);

		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    
    
    /**
     * 下单成功显示页面
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-24
     */
    public function OrderSuccess() {
        $int_o_id = $this->_get('oid');
        $where = array(
            'o_id' => $int_o_id,
            'm_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersInfo($where);	
        $ary_orders = $ary_orders_info [0];
        //echo "<pre>";print_r($ary_orders);exit;
        // 订单作废状态
        $ary_status = array(
            'o_status' => $ary_orders ['o_status']
        );
        $str_status = $this->orders->getOrderItmesStauts('o_status', $ary_status);
        $ary_orders ['str_status'] = $str_status;

        // 订单支付状态
        $ary_pay_status = array(
            'o_pay_status' => $ary_orders ['o_pay_status']
        );
        $str_pay_status = $this->orders->getOrderItmesStauts('o_pay_status', $ary_pay_status);
        $ary_orders ['str_pay_status'] = $str_pay_status;
        // 订单状态
        $ary_orders_status = $this->orders->getOrdersStatus($int_o_id);
        // 退款
        $ary_orders ['refund_status'] = $ary_orders_status ['refund_status'];
        // 退货
        $ary_orders ['refund_goods_status'] = $ary_orders_status ['refund_goods_status'];
        // 发货
        $ary_orders ['deliver_status'] = $ary_orders_status ['deliver_status'];
        // 退款、退货类型
        $ary_orders ['refund_type'] = $ary_orders_status ['refund_type'];
        // 支付方式
        $ary_payment_where = array(
            'pc_id' => $ary_orders ['o_payment']
        );
        $ary_payment = D('PaymentCfg')->getPayCfgId($ary_payment_where);
        // 获取支付方式列表
        $payment = D('PaymentCfg');
        $payment_list = $payment->getPayCfg();
        //下单成功页面，过滤货到付款的支付方式
        foreach($payment_list as $key=>$val){
            if(strtoupper($val['pc_abbreviation']) == 'DELIVERY') unset($payment_list[$key]);
        }

        $ary_orders ['payment_name'] = $ary_payment ['pc_custom_name'];
        $ary_orders ['pc_abbreviation'] = $ary_payment ['pc_abbreviation'];
        $ary_orders ['pc_id'] = $ary_payment ['pc_id'];

        // 当前订单类型 8:预售商品 5:团购商品，4:自由组合商品,3组合商品，2赠品， 1积分商品，0普通商品
        $oi_type = M('orders_items')->where(array(
                    'o_id' => $ary_orders ['o_id']
                ))->getField('oi_type');
        switch ($oi_type) {
            case '5' : // 团购商品
                // 1，判断团购商品是否开启定金支付
                $groupbuy_info = M('groupbuy')->field(array('gp_id,gp_overdue_start_time,gp_overdue_end_time,gp_deposit_price,is_deposit'))
                                              ->where(array('g_id' => $ary_orders ['g_id'],'deleted' => 0))
                                              ->find();
                // 2,当前订单是否已经支付过定金
                if ($groupbuy_info ['is_deposit'] == 1) {
                    $pay_type_count = M('payment_serial')->where(array('o_id' => $ary_orders ['o_id'],'ps_status' => array('neq',0)))->count();
                    // echo $pay_type_count;exit;
                    if (empty($pay_type_count)) {
                        $pay_status = 0; // 还未支付过，适用于定金支付和全额支付
                        // 获取定金
                        $ary_orders ['gp_deposit_price'] = sprintf("%0.3f", $groupbuy_info ['gp_deposit_price'] * $ary_orders ['oi_nums']);
                    } else {
                        $pay_status = 1; // 已经支付过定金支付，还需支付尾款金额
                    }
                } else {
                    $pay_status = 3; // 未开启定金支付
                }
                $this->assign('pay_status', $pay_status);
                break;
            case '8'://预售商品
                $presale_info = M('presale',C('DB_PREFIX'),'DB_CUSTOM')->field('p_id,p_overdue_start_time,p_overdue_end_time,p_deposit_price,is_deposit')
                                ->where(array('g_id'=>$ary_orders['g_id'],'deleted'=>0))
                                ->find();
                //判断预售商品是否开启定金支付
                if($presale_info['is_deposit'] == 1){
                    //判读当前订单是否已经支付过定金
                    $pay_type_count = M('payment_serial',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$ary_orders['o_id'],'ps_status'=>array('neq',0)))->count();
                    if(empty($pay_type_count)){
                        $pay_status = 0;//还未支付过，适用于定金支付和全额支付
                        //获取定金
                        $ary_orders['gp_deposit_price'] = sprintf('%.2f',$presale_info['p_deposit_price'] * $ary_orders['oi_nums']); 
                    }else{
                        $pay_status = 1;//已经支付过定金支付，还需支付尾款金额
                    }
                }else{
                    $pay_status = 3; //未开启定金支付
                }
                $this->assign('pay_status',$pay_status);
                break;
        }
        // 支付金额为：订单总金额-已支付金额
        //$ary_orders ['o_all_price'] = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign('ary_orders', $ary_orders);
        $this->assign('payment_list', $payment_list);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Ucenter/OrderSuccess.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Ucenter/OrderSuccess.html';
        }
        $this->display($tpl);
    }
    
    // 作废订单
    public function ajaxInvalidOrder() {
        $ary_post = $this->_post();
        $int_oid = $this->_post('oid');
        $cacel_type = $this->_post('cacel_type');
        if (isset($int_oid) && isset($cacel_type)) {
            M()->startTrans();
            //断订单是满足作废条件,只有未支付的订单才能作废
            $ary_where = array('o_id' => $int_oid, 'o_pay_status' => 0, 'o_status' => 1);
            $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_orders = $orders->where($ary_where)->find();
            if (empty($ary_orders)) {
                $this->error('此订单不能不存在', array('确定' => U('Ucenter/Orders/pageShow/', array('oid' => $int_oid))));
                exit;
            } else {
                $return_orders = $orders->where(array(
                            'o_id' => $int_oid
                        ))->save(array(
                    'o_status' => 2
                        ));
                // 订单日志记录
                if ($return_orders) {
                    $ary_orders_log = array(
                        'o_id' => $int_oid,
                        'ol_behavior' => '作废',
                        'ol_uname' => $_SESSION ['Members'] ['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                }else{
                    M()->rollback();
                    $this->error('更新订单状态失败', array(
                            '确定' => U('Ucenter/Orders/pageShow/', array(
                                'oid' => $int_oid
                            ))
                        ));
                }
                if (false != $return_orders) {
                    // 冻结积分释放掉
                    $res_status = $this->orders->releasePoint($int_oid);
                    if (!$res_status){
                        M()->rollback();
                        $this->error('作废订单释放冻结积分失败', array(
                            '确定' => U('Ucenter/Orders/pageShow/', array(
                                'oid' => $int_oid
                            ))
                        ));
                    }
                    $ary_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                'c_order_id' => $int_oid
                            ))->find();
                    if (!empty($ary_coupon) && is_array($ary_coupon)) {
                        $res_coupon = M('coupon', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                                    'c_order_id' => $int_oid
                                ))->save(array(
                            'c_used_id' => 0,
                            'c_order_id' => 0,
                            'c_is_use' => 0
                                ));
                        if (!$res_coupon) {
                            M()->rollback();
                            $this->error('优惠券还原失败', array(
                                '确定' => U('Ucenter/Orders/pageShow/', array(
                                    'oid' => $int_oid
                                ))
                            ));
                            exit();
                        }
                    }
                    //手动点击作废才会更新第三方订单  防止异步触发自动更新第三方订单
                    if("click" == $ary_post['trigger']){
                        if(empty($ary_orders['o_source_id']) && !isset($ary_orders['o_source_id'])){
                            M()->rollback();
                             $this->error('第三方来源单号不存在', array(
                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                                exit();
                        } 
                        $bool_result = M('thd_orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_source_id'=>$ary_orders['o_source_id']))->data(array('to_tt_status'=>'0'))->save();
                        if(false === $bool_result){
                            M()->rollback();
                             $this->error('更新第三方来源单号失败', array(
                                    '确定' => U('Ucenter/Orders/pageShow/', array(
                                        'oid' => $int_oid
                                    ))
                                ));
                                exit();
                        }
                    }
                    M()->commit();
                    $this->success('作废成功', array(
                        '确定' => U('Ucenter/Orders/pageList/')
                    ));
                    exit();
                } else {
                    M()->rollback();
                    $this->error('此订单不能作废', array(
                        '确定' => U('Ucenter/Orders/pageShow/', array(
                            'oid' => $int_oid
                        ))
                    ));
                    exit();
                }
            }
        }
    }

    /**
     * 订单支付
     * 
     * @param int $order_id
     *        	订单ID
     * @param ary $oreder
     *        	更新订单表数组
     * @return boolean
     */
    public function paymentPage() {
        $int_id = $this->_get('oid');
        $payment_id = $this->_post('new_payment_id');
        $pay_stat = $this->_post('typeStat');
        
        $where = array();
        $where ['fx_orders.o_id'] = $int_id;
        if ($pay_stat == 2) { // 如果当前是尾款支付
            $where ['fx_orders.o_pay_status'] = 3;
        } else {
            $where ['fx_orders.o_pay_status'] = 0;
        }
        $where ['fx_orders.o_status'] = array(array('eq','1'),array('eq','3'),'or');
        $search_field = array(
            'fx_orders.o_all_price',
            'fx_orders.o_payment',
            'fx_orders.o_pay',
            'fx_members.m_id',
            'fx_orders.o_pay_status',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders_items.pdt_id',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.fc_id',
            'fx_orders_items.g_id'
        );
        if (isset($int_id)) {
            $ary_orders_data = D('Orders')->getOrdersData($where, $search_field);

            if (empty($ary_orders_data) && count($ary_orders_data) <= 0) {
                $this->error('订单不存在或已支付'); // XXXXXXXXXXXXXXXXXXXXX
            }
            $ary_orders = $ary_orders_data [0];
        }
        M('', '', 'DB_CUSTOM')->startTrans();
        
        // 支付流程改造【团购】
        if ($ary_orders ['oi_type'] == '5') { // 团购订单
       
            $groupbuy = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'g_id' => $ary_orders ['g_id'],'deleted'=>0
                    ))->find();
            if ($pay_stat == 0) {
                // 团购全额支付
                //验证团购商品是否可以支付
                
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = $ary_orders ['o_all_price'];
				/**
                $gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'gp_id' => $groupbuy ['gp_id']
                        ))->getField('gp_now_number');
                M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'gp_id' => $groupbuy ['gp_id']
                ))->save(array(
                    'gp_now_number' => $gp_now_number + $ary_orders ['oi_nums']
                ));
				**/
            } elseif ($pay_stat == 1) {
                // 团购定金支付,获取定金
                $is_pay = D('Groupbuy')->checkBulkIsBuy($ary_orders['m_id'],$groupbuy['gp_id'],$int_id);
                if($is_pay['status'] == false){
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error($is_pay['msg'], U('Ucenter/Orders/pageShow/', array('oid' => $int_id)));
                }
                $o_pay = sprintf("%0.3f", $groupbuy ['gp_deposit_price'] * $ary_orders ['oi_nums']);
				/**
                $gp_now_number = M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'gp_id' => $groupbuy ['gp_id']
                        ))->getField('gp_now_number');
                M('groupbuy', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'gp_id' => $groupbuy ['gp_id']
                ))->save(array(
                    'gp_now_number' => $gp_now_number + $ary_orders ['oi_nums']
                ));
				**/
            } elseif ($pay_stat == 2) {
                // 尾款支付。检测当前时间是否在指定支付尾款时间内
                $gp_overdue_start_time = strtotime($groupbuy ['gp_overdue_start_time']);
                $gp_overdue_end_time = strtotime($groupbuy ['gp_overdue_end_time']);
                if ($gp_overdue_start_time > mktime()) {
                    // 还未到支付尾款时间
                    $this->error('请于' . date('Y年m月d日 H:i:s', $gp_overdue_start_time) . '后补交尾款');
                } elseif (($gp_overdue_start_time < mktime()) && ($gp_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    $this->error('补交尾款时间已过，请联系客服人员');
                }
                $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
            }
        }elseif ($ary_orders['oi_type'] == '8') {   //预售商品
            $presale = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'g_id' => $ary_orders ['g_id']
                    ))->find();
            if ($pay_stat == 0) {
                // 预售全额支付
                $o_pay = $ary_orders ['o_all_price'];
                $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'p_id' => $presale ['p_id']
                        ))->getField('p_now_number');
                M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'p_id' => $presale ['p_id']
                ))->save(array(
                    'p_now_number' => $p_now_number + $ary_orders ['oi_nums']
                ));
            } elseif ($pay_stat == 1) {
                // 预售定金支付,获取定金
                $o_pay = sprintf("%0.3f", $presale ['p_deposit_price'] * $ary_orders ['oi_nums']);
                $p_now_number = M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'p_id' => $presale ['p_id']
                        ))->getField('p_now_number');
                M('presale', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                    'p_id' => $presale ['p_id']
                ))->save(array(
                    'p_now_number' => $p_now_number + $ary_orders ['oi_nums']
                ));
            } elseif ($pay_stat == 2) {
                // 尾款支付。检测当前时间是否在指定支付尾款时间内
                $p_overdue_start_time = strtotime($presale ['p_overdue_start_time']);
                $p_overdue_end_time = strtotime($presale ['p_overdue_end_time']);
                if ($p_overdue_start_time > mktime()) {
                    // 还未到支付尾款时间
                    $this->error('请于' . date('Y年m月d日 H:i:s', $p_overdue_start_time) . '后补交尾款');
                } elseif (($p_overdue_start_time < mktime()) && ($p_overdue_end_time < mktime())) {
                    // 支付尾款时间已过
                    $this->error('补交尾款时间已过，请联系客服人员');
                }
                $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
            }
        }else {
            $o_pay = sprintf("%0.3f", $ary_orders ['o_all_price'] - $ary_orders ['o_pay']);
        }

        // # 使用支付模型 支付订单 ###############################################
        $Payment = D('PaymentCfg');
        if ($ary_orders ['o_payment'] != $payment_id && !empty($payment_id)) {
            $ary_orders ['o_payment'] = $payment_id;
            $update_payment_res = $this->orders->UpdateOrdersPayment($int_id, $payment_id);
            if ($update_payment_res == false) {
                M('', '', 'DB_CUSTOM')->rollback();
                exit();
            }
        }
        
        $info = $Payment->where(array(
                    'pc_id' => $ary_orders ['o_payment']
                ))->find();
        
        if (false == $info) {
            // 支付方式不存在 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
            M('', '', 'DB_CUSTOM')->rollback();
            $this->error('支付方式不存在，或不可用');
            exit();
        }
        
        // 线下支付进erp
        if ($info ['pc_abbreviation'] != 'OFFLINE' && $info ['pc_abbreviation'] != 'DELIVERY' && $ary_orders ['pc_abbreviation'] != 'DELIVERY') {
            $Pay = $Payment::factory($info ['pc_abbreviation'], json_decode($info ['pc_config'], true));
            $result = $Pay->pay($int_id, $ary_orders ['oi_type'], $o_pay, $pay_stat);
            writeLog($result, "order_pay.log");
            if (!$result ['result']) {
                // 支付失败了 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result ['message']);
            }
            // 支付成功了
            // die('@tudo 支付成功继续完成：1）流水账 2）更新订单状态 3）进入ERP');
            $ary_orders ['o_pay'] = $o_pay;
            if ($pay_stat == 1) {
                // 如果是定金支付，支付状态为部分支付
                $ary_orders ['o_pay_status'] = 3;
            } else {
                $ary_orders ['o_pay_status'] = 1;
            } // print_r($ary_orders);exit;
            //查询库存,如果库存数为负数则不再扣除库存
            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                           ->field('pdt_stock')
                                           ->where(array('o_id'=>$int_id))
                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                           ->find();
            if($ary_orders['oi_type'] ==5 || $ary_orders['oi_type'] ==8){
                //没有结果
            }else{
                if(0 >= $int_pdt_stock['pdt_stock']){
                    $this->error('该货品已售完！');
                }
            }
            
            $result_order = $this->orders->orderPayment($int_id, $ary_orders);
            
            if (!$result_order ['result']) {
                // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }else{
                if($ary_orders ['oi_type'] == '7'){
					/**
                    $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
                    if (!$result_spike) {
                        // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error("111");
                            exit();

                    }**/
                }
            }

            $ary_member = session("Members");
            $ary_balance_info = array(
                'bt_id' => '1',
                'bi_sn' => time(),
                'm_id' => $ary_member ['m_id'],
                'bi_money' => $o_pay,
                'bi_type' => '1',
                'bi_payment_time' => date("Y-m-d H:i:s"),
                'o_id' => $int_id,
                'bi_desc' => '订单支付',
                'single_type' => '2',
                'bi_verify_status' => '1',
                'bi_service_verify' => '1',
                'bi_finance_verify' => '1',
                'bi_create_time' => date("Y-m-d H:i:s")
            );
            $arr_res = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_balance_info);
            // echo "<pre>";print_r($ary_balance_info);exit;
            if ($arr_res === false) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error('生成支付明细失败，请重试...');
                exit();
            } else {
                $arr_data = array();
                $str_sn = str_pad($arr_res, 6, "0", STR_PAD_LEFT);
                $arr_data ['bi_sn'] = time() . $str_sn;
                $arr_result = M('BalanceInfo', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'bi_id' => $arr_res
                        ))->data($arr_data)->save();
                if (!$arr_result) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('更新支付明细失败，请重试...');
                    exit();
                }
                // 结余款调整单日志
                $add_balance_log ['u_id'] = 0;
                $add_balance_log ['bi_sn'] = $arr_data ['bi_sn'];
                $add_balance_log ['bvl_desc'] = '审核成功';
                $add_balance_log ['bvl_type'] = '2';
                $add_balance_log ['bvl_status'] = '2';
                $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
                if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成结余款调整单日志失败，请重试...');
                    exit();
                }
                $add_balance_log ['bvl_type'] = '3';
                if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成结余款调整单日志失败，请重试...');
                    exit();
                }
            }
            if ($info ['pc_abbreviation'] == 'DEPOSIT') {
                $add_payment_serial ['m_id'] = $_SESSION ['Members'] ['m_id'];
                $add_payment_serial ['pc_code'] = 'DEPOSIT';
                $add_payment_serial ['ps_money'] = $o_pay;
                $add_payment_serial ['ps_type'] = 0;
                $add_payment_serial ['o_id'] = $int_id;
                $add_payment_serial ['ps_status'] = 1;
                $add_payment_serial ['pay_type'] = $pay_stat;
                $add_payment_serial ['ps_create_time'] = date('Y-m-d H:i:s');
                $ary_result = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_payment_serial);
                if (false === $ary_result) {
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('生成支付明细失败，请重试...');
                    exit();
                }
            }
        } else {
            //查询库存,如果库存数为负数则不再扣除库存
            $int_pdt_stock = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                                           ->field('pdt_stock')
                                           ->where(array('o_id'=>$int_id))
                                           ->join(C('DB_PREFIX').'goods_products as gp on gp.pdt_id = '.C('DB_PREFIX').'orders_items.pdt_id')
                                           ->find();
            if(0 >= $int_pdt_stock['pdt_stock']){
                $this->error('该货品已售完！');
            }
            $ary_orders ['o_pay_status'] = 0;
            $result_order = $this->orders->orderPayment($int_id, $ary_orders);
            
            if (!$result_order ['result']) {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error($result_order ['message']);
                exit();
            }else{
                
                if($ary_orders ['oi_type'] == '7'){
					/**
                    $result_spike = D("Spike")->where(array('sp_id'=>$ary_orders['fc_id']))->data(array('sp_now_number'=>array('exp', 'sp_now_number + 1')))->save();
                    if (!$result_spike) {
                        // 后续工作失败 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        M('', '', 'DB_CUSTOM')->rollback();
                        $this->error($result_order ['message']);
                            exit();

                    }**/
                }
            }
        }
        M('', '', 'DB_CUSTOM')->commit();
        $url = U("Ucenter/Orders/PaymentSuccess", array(
            'oid' => $int_id
                ));
        redirect($url);
        exit();
    }

    /**
     * 支付成功显示页面
     * 
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-24
     */
    public function PaymentSuccess() {
        $this->getSubNav(1, 0, 30);
        $ary_member = session("Members");
        $int_o_id = $this->_get('oid');
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_orders.m_id' => $ary_member ['m_id']
        );
        $ary_field = array(
			'fx_orders.o_id',
            'fx_orders.o_pay',
            'fx_orders.o_all_price',
            'fx_orders.coupon_sn',
            'fx_orders_items.pdt_id',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type'
        );
        $ary_orders = D('Orders')->getOrdersData($where, $ary_field);
        // 本次消费金额=支付单最后一次消费记录
        $payment_serial = M('payment_serial')->where(array('o_id' => $int_o_id))->order('ps_create_time desc')->select();
        // echo "<pre>"; print_r($payment_serial);exit;
        $payment_price = $payment_serial [0] ['ps_money'];
        $all_price = $ary_orders[0]['o_all_price'];
        $coupon_sn = $ary_orders[0]['coupon_sn'];
        //获取优惠券节点
        $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
        //送优惠券
        if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '0') {
            D('Coupon')->setPoinGetCoupon($ary_orders, $ary_member['m_id']);
        }
        // 账户余额
        $ary_member_balance = D('Members')->GetBalance(array(
            'm_id' => $_SESSION ['Members'] ['m_id']
                ), array(
            'm_balance'
                ));
        $this->assign('o_id', $int_o_id);
        $this->assign('balance', $ary_member_balance ['m_balance']);
        $this->assign('payment_price', $payment_price);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
                    
    /**
     * 查询物流跟踪信息
     */
    public function getOrdersPostTrack() {
        if (!isset($_POST ["od_id"]) || "" == $_POST ["od_id"]) {
            echo "系统集成出错：非法的发货单ID参数传入。";
            exit();
        }

        // 获取订单的物流单信息
        $array_info = D("OrdersDelivery")->where(array(
                    "od_id" => $_POST ["od_id"]
                ))->find();
        if (!is_array($array_info) || empty($array_info)) {
            echo "发货单ID错误或者发货单不存在。";
            exit();
        }

        // 根据物流公司ID获取物流公司对应的快递100的物流公司代码
        $kuaidi100_code = D("LogisticCorp")->where(array(
                    "lc_id" => $array_info ["od_logi_id"]
                ))->getField("lc_kuaidi100_name");
        if (false === $kuaidi100_code || "" == $kuaidi100_code) {
            echo "快递100物流公司代码未设置。";
            exit();
        }

        // 调用快递100接口获取物流跟踪数据
        $kuaidi100_obj = new Kuaidi100 ();
        $result = $kuaidi100_obj->queryDeliveryTrack($kuaidi100_code, $array_info ["od_logi_no"]);
        if (true == $result ["status"] && !isset($result ["data"] ["data"])) {
            $result ["data"] ["data"] = array();
        }

        krsort($result ["data"] ["data"]);
        $this->assign("post_track_info", $result ["data"] ["data"]);
        $this->assign("delivery_info", $array_info);
        $this->display("post_track");
    }

    /**
     * 确认收货
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function Receive() {
        $ary_get = $this->_get();
        $ary_order = D("Orders")->join(" " . C('DB_PREFIX') . "payment_cfg ON " . C('DB_PREFIX') . "payment_cfg.pc_id=" . C('DB_PREFIX') . "orders.o_payment")->where(array(
                    "o_id" => $ary_get ['oid']
                ))->find();

        if (!empty($ary_order ['pc_abbreviation']) && $ary_order ['pc_abbreviation'] == 'ALIPAY') {

            $ary_gateway = M('payment_serial', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'pc_code' => $ary_order ['pc_abbreviation'],
                        'o_id' => $ary_order ['o_id'],
                        'ps_status_sn' => 'WAIT_SELLER_SEND_GOODS'
                    ))->find();

            if (!empty($ary_gateway) && is_array($ary_gateway)) {
                Header("Location: https://lab.alipay.com/consume/queryDetailFromPay.htm?trade_no=" . $ary_gateway ['ps_gateway_sn']);
                exit();
            } else {
                $this->error("无需确认收货……", 3, U("Wap/Ucenter/pageList"));
            }
        }
    }

    /**
     * 订单立即评价
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function addMemberEvaluate() {
        $int_o_id = $this->_get('oid');
        $field = array(
            'fx_orders.o_id',
            'fx_orders_items.oi_id',
            'fx_orders.o_status',
            'fx_orders.o_all_price',
            'fx_orders.o_create_time',
            'fx_orders.o_goods_all_price',
            'fx_orders.o_pay_status',
            'fx_orders.o_receiver_name',
            'fx_orders.o_receiver_state',
            'fx_orders.o_receiver_city',
            'fx_orders.o_receiver_county',
            'fx_orders.o_discount',
            'fx_orders.o_receiver_address',
            'fx_orders.o_receiver_telphone',
            'fx_orders.o_receiver_mobile',
            'fx_orders.o_receiver_email',
            'fx_orders.o_coupon_menoy',
            'fx_orders.o_buyer_comments',
            'fx_orders.o_payment',
            'fx_orders.lt_id',
            'fx_orders.ra_id',
            'fx_orders.o_reward_point',
            'fx_orders.o_freeze_point',
            'fx_orders.o_cost_freight',
            'fx_orders.o_receiver_time',
            'fx_orders_items.pdt_id',
            'fx_orders_items.g_id',
            'fx_orders_items.g_sn',
            'fx_orders_items.oi_g_name',
            'fx_orders_items.oi_score',
            'fx_orders_items.oi_nums',
            'fx_orders_items.oi_type',
            'fx_orders_items.oi_price',
            'fx_orders_items.pdt_sale_price',
            'fx_orders_items.pdt_sn',
            'fx_members.m_balance'
        );
        $where = array(
            'fx_orders.o_id' => $int_o_id,
            'fx_members.m_id' => $_SESSION ['Members'] ['m_id']
        );
        $ary_orders_info = D('Orders')->getOrdersData($where, $field);
        // echo "<pre>";print_r(D('Orders')->getLastSql());exit;
        // 订单详情商品
        if (!empty($ary_orders_info) && is_array($ary_orders_info)) {
            $combo_price_total = 0;
            foreach ($ary_orders_info as $k => $v) {
                $ary_orders_info [$k] ['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v ['pdt_id']);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'g_id' => $v ['g_id']
                        ))->field('g_picture')->find();

                $ary_orders_info [$k] ['g_picture'] = getFullPictureWebPath($ary_goods_pic ['g_picture']);
                // 订单商品退款、退货状态
                $ary_orders_info [$k] ['str_refund_status'] = $this->orders->getOrderItmesStauts('oi_refund_status', $v);
                // 订单商品发货

                $ary_orders_info [$k] ['str_ship_status'] = $this->orders->getOrderItmesStauts('oi_ship_status', $v);
                if ($v ['oi_type'] == 3) {
                    $combo_sn = $v ['pdt_sn'];
                    $tmp_ary = array(
                        'g_sn' => $ary_orders_info [$k] ['g_sn'],
                        'pdt_spec' => $ary_orders_info [$k] ['pdt_spec'],
                        'g_picture' => $ary_orders_info [$k] ['g_picture'],
                        'oi_g_name' => $ary_orders_info [$k] ['oi_g_name'],
                        'pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );

                    $combo_pdt_gid = D('GoodsProducts')->Search(array(
                        'pdt_sn' => $v ['pdt_sn']
                            ), array(
                        'g_id'
                            ));
                    $combo_where = array(
                        'g_id' => $combo_pdt_gid ['g_id'],
                        'releted_pdt_id' => $ary_orders_info [$k] ['pdt_id']
                    );
                    $combo_field = array(
                        'com_nums',
                        'com_price'
                    );
                    $combo_res = D('ReletedCombinationGoods')->getComboReletedList($combo_where, $combo_field);
                    $combo_num = $combo_res [0] ['com_nums'];
                    $combo_price_total += sprintf("%0.3f", $combo_res [0] ['com_nums'] * $combo_res [0] ['com_price']);

                    $ary_combo [$combo_sn] ['item'] [$k] = $tmp_ary;
                    $ary_combo [$combo_sn] ['num'] = $ary_orders_info [$k] ['oi_nums'] / $combo_num;
                    $ary_combo [$combo_sn] ['pdt_sale_price'] = $ary_orders_info [$k] ['pdt_sale_price'];
                    $ary_combo [$combo_sn] ['o_all_price'] = sprintf("%0.3f", $combo_price_total * $ary_combo [$combo_sn] ['num']);
                    $ary_combo [$combo_sn] ['str_ship_status'] = $ary_orders_info [$k] ['str_ship_status'];
                    $ary_combo [$combo_sn] ['str_refund_status'] = $ary_orders_info [$k] ['str_refund_status'];
                    unset($ary_orders_info [$k]);
                }
            }
        }
        $this->assign('orders_goods_info', $ary_orders_info);
        $this->assign("oid", $int_o_id);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * 添加会员评论
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-09
     */
    public function addComment() {
        $ary_post = $this->_post();
        // echo "<pre>";print_r($ary_post);exit;
        if (!empty($ary_post ['anony']) && $ary_post ['anony'] == '1') {
            $anony = '0';
            $gcom_contacts = '暂无';
			$gcom_mbname = '匿名';
        } else {
            $anony = $_SESSION ['Members'] ['m_id'];
            $gcom_contacts = $_SESSION ['Members'] ['m_email'];
            $gcom_mbname = $_SESSION['Members']['m_name'];
        }
        $module = M('goods_comments', C('DB_PREFIX'), 'DB_CUSTOM');
        $module->startTrans();
        if (!empty($ary_post ['goods']) && is_array($ary_post ['goods'])) {
            $msg_comments = true;
            $res = true;
            foreach ($ary_post ['goods'] as $keygoods => $valgoods) {
                if (!empty($valgoods ['comment'])) {
                    $data = array(
                        'm_id' => $anony,
                        'g_id' => $keygoods,
                        'gcom_title' => $valgoods ['g_name'],
                        'gcom_content' => $valgoods ['comment'],
                        'gcom_mbname'=>$gcom_mbname,
                        'gcom_email' => $gcom_contacts,
                        'gcom_star_score' => $valgoods ['gcom_star_score'],
                        'gcom_create_time'=>date('Y-m-d H:i:s'),
                        'gcom_ip_address' => $_SERVER['SERVER_ADDR']
                    );
                    
                    $arr_res = $module->add($data);
                    if (!$arr_res) {
                        $res = false;
                        $module->rollback();
                        $this->error('评论失败，请重试...');
                        exit();
                    }
                } else {
                    $msg_comments = false;
                }
            }
            if (FALSE === $msg_comments) {
                $this->error("评论内容显示为空，请重新输入...");
            }
            if ($res) {
                $ary_result = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                            'o_id' => $ary_post ['oid']
                        ))->data(array(
                            'is_evaluate' => '1'
                        ))->save();
                if (!$ary_result) {
                    $module->rollback();
                    $this->error("更新订单信息失败，请重试...");
                    exit();
                }
            }
            $module->commit();
            $this->success("评论成功", U("Wap/Ucenter/pageList", 3));
        } else {
            $this->error("评论内容不能为空");
        }
    }

    /**
     * 订单确认收货
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-12
     */
    public function OrderConfirmation() {
        $ary_get = $this->_get();
        $ary_orders_status = $this->orders->getOrdersStatus($ary_get['oid']);
        if ($ary_orders_status['deliver_status'] != '已发货') {
            $this->error("订单" . $ary_get['oid'] . "未发货");
        }
        $ary_order = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('o_id' => $ary_get ['oid']))->find();
        if (!empty($ary_order) && is_array($ary_order)) {
            M('', '', 'DB_CUSTOM')->startTrans();
            $ary_result = M('Orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
                        'o_id' => $ary_order ['o_id']
                    ))->data(array(
                        'o_status' => '5'
                    ))->save();
            if (FALSE !== $ary_result) {
                /*                 * ** 处理订单积分****start****By Joe***** */
                $array_point_config = D('PointConfig')->getConfigs();
                if ($array_point_config ['is_consumed'] == '1' && $array_point_config['cinsumed_channel'] == '1') {
                    // 确认收货后处理赠送积分
                    if ($ary_order['o_reward_point'] > 0) {
                        $ary_reward_result = D('PointConfig')->setMemberRewardPoint($ary_order['o_reward_point'], $ary_order ['m_id']);
                        if (!$ary_reward_result ['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($ary_reward_result['message']);
                            exit();
                        }
                    }

                    // 确认收货后处理消费积分
                    if ($ary_order ['o_freeze_point'] > 0) {
                        $ary_freeze_result = D('PointConfig')->setMemberFreezePoint($ary_order['o_reward_point'], $ary_order ['m_id']);
                        if (!$ary_freeze_result['result']) {
                            M('', '', 'DB_CUSTOM')->rollback();
                            $this->error($ary_freeze_result['message']);
                            exit();
                        }
                    }
                }
                /*                 * ** 处理订单积分****end********* */

                /*                 * * 订单发货后获取订单优惠券**star by Joe* */
                //获取优惠券节点
                $coupon_config = D('SysConfig')->getCfgByModule('GET_COUPON');
                $where = array('fx_orders.o_id' => $ary_get['oid']);
                $ary_field = array('fx_orders.o_pay', 'fx_orders.o_all_price', 'fx_orders.coupon_sn', 'fx_orders_items.pdt_id', 'fx_orders_items.oi_nums', 'fx_orders_items.oi_type');
                $ary_orders = D('Orders')->getOrdersData($where, $ary_field);
                // 本次消费金额=支付单最后一次消费记录
                $payment_serial = M('payment_serial')->where(array('o_id' => $ary_get['oid']))->order('ps_create_time desc')->select();
                $payment_price = $payment_serial[0]['ps_money'];
                $all_price = $ary_orders[0]['o_all_price'];
                $coupon_sn = $ary_orders[0]['coupon_sn'];
                if ($coupon_sn == "" && $coupon_config['GET_COUPON_SET'] == '2') {
                    D('Coupon')->setPoinGetCoupon($ary_orders, $ary_order['m_id']);
                }
                /*                 * * 订单发货后获取订单优惠券****end********* */

                M('', '', 'DB_CUSTOM')->commit();
                $this->success("确认收货成功,请对商品进行评价");
            } else {
                M('', '', 'DB_CUSTOM')->rollback();
                $this->error("订单 " . $ary_get ['oid'] . " 确认收货失败，请重试...");
            }
        } else {
            $this->error("订单 " . $ary_get ['oid'] . " 不存在");
        }
    }
    
    /**
     * 显示常用地址页面
     * 删除，添加 地址页面 这里显示
     */
    public function getAddressPage() {
        $bool_return = false;
        $ary_post_data = $this->_post();
        $m_id = $_SESSION ['Members'] ['m_id'];
        if (isset($ary_post_data ['del']) || $ary_post_data ['del'] == 'del') {
            // 删除
            $int_ra_id = $ary_post_data ['ra_id'];
            $bool_return = $this->cityRegion->doDelDeliver($int_ra_id);
        } else {
            // 添加
            if ($ary_post_data ['cr_id'] == 0) {
                if(empty($ary_post_data['region'])){
                   $ary_post_data['region']=$ary_post_data['city'];
                }
                $ary_post_data ['cr_id'] = $ary_post_data ['region'];
            }
            $ary_post_data ['ra_phone'] = $ary_post_data ['ra_phone_area'] . '-' . $ary_post_data ['ra_phone'] . '-' . $ary_post_data ['ra_phone_ext'];
            $bool_return = $this->cityRegion->addReceiveAddr($ary_post_data, $m_id);
            $int_ra_id = $bool_return ['data'] ['ra_id'];
        }

        if ($bool_return) {
            $ary_return ['status'] = 1;
            $ary_addr = $this->cityRegion->getReceivingAddress($_SESSION ['Members'] ['m_id']);
            if (!empty($ary_addr)) {
                if (isset($ary_post_data ['del']) || $ary_post_data ['del'] == 'del') {
                    $ary_return ['data'] = $ary_addr [0];
                    $ary_return ['msg'] = '删除成功';
                    $ary_return ['num'] = count($ary_addr);
                } else {
                    foreach ($ary_addr as $val) {
                        if ($val ['ra_id'] == $int_ra_id) {
                            $ary_return ['data'] = $val;
                            $ary_return ['msg'] = '添加成功';
                            $ary_return ['num'] = count($ary_addr);
                            break;
                        }
                    }
                }
            }
            $this->ajaxReturn($ary_return);
        }
    }

    /**
     * 选择物流公司
     *
     * @author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     * @date 2013-06-30
     */
    public function ChangeLogistic() {
        /*$promotion_price = 0;
        $combo_all_price = 0;
        $free_all_price = 0;*/
        $data = $this->_post();

        $ary_member = session("Members");
        $data['m_id'] = $m_id = (int)$ary_member["m_id"];
        $ary_res = D('Orders')->getAllOrderPrice($data);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $data ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions','fx_logistic_corp.lc_abbreviation_name'));
        $logistic_info['pc_position'] = 0;
        if($logistic_info['lc_cash_on_delivery'] == 1){
            //$ary_payment = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->find();
            $pay_where=array('pc_status'=>1);
            $pay_where["pc_source"] = array('neq','2');
            $pay_order=array('pc_position' => 'asc');
            $pay_field=array('pc_position,pc_abbreviation');
            $ary_payment = D('PaymentCfg')->getPayList($pay_where,$pay_field,$pay_order);

            if(!empty($ary_payment) && $ary_payment[0]['pc_abbreviation'] == 'DELIVERY'){
                $logistic_info['pc_position'] = 1;
            }
            //add by zhangjiasuo  2015-05-28 物流支持自提 但是自提支付方式没有开启过滤 start
            $is_open_delivery=false;
            if(!empty($ary_payment)){
                foreach($ary_payment as $payment_val){
                    if($payment_val['pc_abbreviation'] == 'DELIVERY'){
                        $is_open_delivery=true;
                    }
                }
            }
            $logistic_info['lc_cash_on_delivery'] =$is_open_delivery;
            //add by zhangjiasuo  2015-05-28 物流支持自提 但是自提支付方式没有开启过滤 end
        }
        $ary_res['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
        $ary_res['pc_position'] = $logistic_info['pc_position'];
        $ary_res ['lc_abbreviation_name'] = $logistic_info ['lc_abbreviation_name'];
        echo json_encode($ary_res);exit();

        $ary_tmp_cart = D('Cart')->ReadMycart();
        if($data['pids']){
            $goods_pids = $data['pids'];
            switch($goods_pids){
                case 'spike':
                    $ary_cart = array();
                    $ary_cart = $_SESSION ['spike_cart'];
                    break;
                case 'bulk':
                    $bulk_cart = $_SESSION['bulk_cart'];
                    $ary_member = session("Members");
                    $array_bulk = D('Groupbuy')->getBulkPrice($bulk_cart);
                    $ary_return['all_price'] = $array_bulk['data']['cust_price'] * $bulk_cart['num'];
                    $ary_return['all_goods_price'] = $array_bulk['data']['all_price'];
                    $ary_cart[$bulk_cart['pdt_id']] = array('pdt_id' => $bulk_cart['pdt_id'], 'num' => $bulk_cart['num'], 'type' => 5);
                    $logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart,$ary_member['m_id']);
                    $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
                    $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                    if(!empty($lt_expressions['logistics_configure']) && $ary_return ['all_goods_price'] >= $lt_expressions['logistics_configure']){
                        $logistic_price = $lt_expressions['logistics_configure'];
                    }
                    $ary_return['status'] = 1;
                    $ary_return['logistic_price'] = $logistic_price;
                    $ary_return['promotion_price'] = $ary_return['all_price'] - $ary_return['all_goods_price'];
                    $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
                    $this->ajaxReturn($ary_return);exit;
                    break;
                case 'presale':
                    $param_spike = $_SESSION ['spike_cart'];
                    $spike = M('spike', C('DB_PREFIX'), 'DB_CUSTOM');

                    $array_where = array(
                        'sp_status' => 1,
                        'sp_id' => $param_spike ['sp_id']
                    );
                    $data = $spike->where($array_where)->find();
                    if (empty($data)) {
                        $this->error('秒杀商品不存在！');
                    }
                    $data ['cust_price'] = sprintf("%0.2f",M('goods_info')->where(array('g_id' => $data ['g_id']))->getField('g_price')*$param_spike['num']);
                    $buy_nums = $data ['sp_now_number'];
                    $data ['sp_now_number'] = $buy_nums;
                    $ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 7);
                    $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $_POST ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
                    $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
                    if(!empty($lt_expressions['logistics_configure']) && $data ['sp_price'] >= $lt_expressions['logistics_configure']){
                        $logistic_price = 0;
                    }else{
                        $ary_cart[$param_spike['pdt_id']] = array('pdt_id' => $param_spike['pdt_id'], 'num' => $param_spike['num'], 'type' => 8);
                        $logistic_price = D('Logistic')->getLogisticPrice($_POST ['lt_id'], $ary_cart);
                    }
                    $ary_return['status'] = 1;
                    $ary_return['all_price'] = $data['cust_price'];
                    $ary_return['logistic_price'] = $logistic_price;
                    $ary_return['promotion_price'] = $data['cust_price'] - $data['sp_price'];
                    $ary_return['logistic_delivery'] = $logistic_info['lc_cash_on_delivery'];
                    $ary_return['sp_price'] = $data['sp_price'];
                    $this->ajaxReturn($ary_return);exit;
                    break;
                default:
                    $cart_data = D('Cart')->ReadMycart();
                    $ary_pid = explode(',',$goods_pids);
                    $ary_cart = array();
                    foreach ($cart_data as $key=>$cd){
                        foreach ($ary_pid as $pid){
                            if($pid == $key){
                                $ary_cart[$pid] = $cart_data[$key];
                            }
                        }
                    }
            }
        }else{
            $ary_cart = $ary_tmp_cart;
        }
        $ary_member = session("Members");
        if (!empty($ary_cart) && is_array($ary_cart)) {
            foreach ($ary_cart as $c_key=>$val) {
                if ($val ['type'] == 0) {
                    $ary_gid = M("goods_products", C('DB_PREFIX'), 'DB_CUSTOM')->field('g_id')->where(array(
                        'pdt_id' => $val ['pdt_id']
                    ))->find();
                    $ary_cart[$c_key]['g_id'] = $ary_gid ['g_id'];
                }
            }
        }
        $ary_tmp_cart = $ary_cart;
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);
        $subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);

        $promotion_total_price = '0';
        $promotion_price = '0';
        //赠品数组
        $cart_gifts = array();
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals['products'] as $key => $val) {
                $arr_products = $this->cart->getProductInfo(array($key => $val));
                if ($arr_products[0][0]['type'] == '4') {
                    foreach ($arr_products[0] as &$provals) {
                        $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                    }
                }
                $pro_datas[$keys]['products'][$key] = $arr_products[0];
                $pro_data[$key] = $val;
                $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
            }
            //赠品数组
            if(!empty($vals['gifts'])){
                foreach($vals['gifts'] as $gifts){
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gifts['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
                    $cart_gifts[$pdt_id['pdt_id']]=array('pdt_id'=>$pdt_id['pdt_id'],'num'=>1,'type'=>2);
                }
            }
            $promotion_total_price += $vals['goods_total_price'];     //商品总价
            if ($keys != '0') {
                $promotion_price += $vals['pro_goods_discount'];
            }
        }
        $ary_return ['all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $data ['lt_id']), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions','fx_logistic_corp.lc_abbreviation_name'));
        if($logistic_info['lc_cash_on_delivery'] == 1){
            //$ary_payment = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->find();
			$pay_where=array('pc_status'=>1);
			$pay_where["pc_source"] = array('neq','2');
			$pay_order=array('pc_position' => 'asc');
			$pay_field=array('pc_position,pc_abbreviation');
			$ary_payment = D('PaymentCfg')->getPayList($pay_where,$pay_field,$pay_order);
			
            if(!empty($ary_payment) && $ary_payment[0]['pc_abbreviation'] == 'DELIVERY'){
                $logistic_info['pc_position'] = 1;
            }
			//add by zhangjiasuo  2015-05-28 物流支持自提 但是自提支付方式没有开启过滤 start
			$is_open_delivery=false;
			if(!empty($ary_payment)){
				foreach($ary_payment as $payment_val){
					if($payment_val['pc_abbreviation'] == 'DELIVERY'){
						$is_open_delivery=true;
					}
				}
			}
			$logistic_info['lc_cash_on_delivery'] =$is_open_delivery;
			//add by zhangjiasuo  2015-05-28 物流支持自提 但是自提支付方式没有开启过滤 end
        }
		$lt_expressions = json_decode($logistic_info['lt_expressions'],true);
       //echo'<pre>';print_r($lt_expressions);die;
        if(!empty($lt_expressions['logistics_configure']) && $ary_return ['all_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
            //获取赠品信息
            if(!empty($cart_gifts)){
                $ary_tmp_cart = array_merge($ary_tmp_cart,$cart_gifts);
                foreach($ary_tmp_cart as $atck=>$atcv){
                    $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                    unset($ary_tmp_cart[$atck]);
                }
            }
            // 满足满包邮条件
            foreach ($pro_datas as $pro_data) {
                if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                    foreach($pro_data['products'] as $proDatK=>$proDatV){
                        unset($ary_tmp_cart[$proDatK]);
                    }
                }
            }
            if(empty($ary_tmp_cart)){
                $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
            }
            //echo '<pre>';print_r($ary_tmp_cart);die();
            // 购买货品的总价
            $logistic_price = D('Logistic')->getLogisticPrice($data ['lt_id'], $ary_tmp_cart,$ary_member['m_id']);
        }
        $ary_return ['status'] = 1;

        $ary_return ['goods_total_sale_price'] = $promotion_total_price;
        $ary_return ['logistic_price'] = $logistic_price;
        $ary_return ['promotion_price'] = sprintf("%0.2f", $promotion_price);
        $ary_return ['logistic_delivery'] = $logistic_info ['lc_cash_on_delivery'];
		$ary_return ['pc_position'] = $logistic_info ['pc_position'];
		$ary_return ['lc_abbreviation_name'] = $logistic_info ['lc_abbreviation_name'];
        //echo'<pre>';print_r($ary_return);die;
        $this->ajaxReturn($ary_return);
    }
    /**
     * 设置ajax打开页面
     *
     * @author wangguibin<wangguibin@guanyisoft.com>
     *  @date 2013-06-09
     */
    public function setPage() {
        $int_o_id = $this->_post('o_id');
        $this->assign('o_id', $int_o_id);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
    /**
     * 使用促销支付
     */
    public function doCoupon(){
        $type = $this->_post('type');
        $str_csn = $this->_post('csn');
        $bonus = $this->_post('bonus');
        $cards = $this->_post('cards');
        $jlb = $this->_post('jlb');
        $point = $this->_post('point');
        $lt_id = $this->_post('lt_id');
        //echo $lt_id;die;
        $data = $this->_post();
        $ary_tmp_cart = D('Cart')->ReadMycart();
        if($data['pids']){
            $ary_pid = explode(',',$data['pids']);
            foreach ($ary_tmp_cart as $key=>$cd){
                foreach ($ary_pid as $pid){
                    if($pid == $key){
                        $ary_cart[$pid] = $ary_tmp_cart[$key];
                    }
                }
            }
        }else{
            $ary_cart = $ary_tmp_cart;
        }
        $ary_tmp_cart = $ary_cart;
        $ary_member = session("Members");
        $pro_datas = D('Promotion')->calShopCartPro($ary_member ['m_id'], $ary_cart);

        $subtotal = $pro_datas ['subtotal'];
        unset($pro_datas ['subtotal']);
        $ary_data ['ary_product_data'] = $this->cart->getProductInfo($ary_cart);
        // 商品总重
        $ary_price_data ['all_weight'] = sprintf("%0.2f", D('Orders')->getGoodsAllWeight($ary_cart));
        $ary_promotion = array();
        $promotion_total_price = '0';
        $promotion_price = '0';
        //dump($pro_datas);die();
        foreach ($pro_datas as $keys => $vals) {
            foreach ($vals['products'] as $key => $val) {
                $arr_products = $this->cart->getProductInfo(array($key => $val));
                if ($arr_products[0][0]['type'] == '4') {
                    foreach ($arr_products[0] as &$provals) {
                        $provals['authorize'] = D('AuthorizeLine')->isAuthorize($ary_member['m_id'], $provals['g_id']);
                    }
                }
                $pro_datas[$keys]['products'][$key] = $arr_products[0];
                $pro_data[$key] = $val;
                $pro_data[$key]['pmn_name'] = $vals['pmn_name'];
            }
            //赠品数组
            if(!empty($vals['gifts'])){
                foreach($vals['gifts'] as $gifts){
                    //随机取一个pdt_id
                    $pdt_id = D("GoodsProducts")->Search(array('g_id'=>$gifts['g_id'],'pdt_stock'=>array('GT', 0)),'pdt_id');
                    $cart_gifts[$pdt_id['pdt_id']]=array('pdt_id'=>$pdt_id['pdt_id'],'num'=>1,'type'=>2);
                }
            }
            $promotion_total_price += $vals['goods_total_price'];     //商品总价
            if ($keys != '0') {
                $promotion_price += $vals['pro_goods_discount'];
            }
        }
        $ary_res['all_price'] = sprintf("%0.2f", $promotion_total_price - $promotion_price);
        $logistic_info = D('LogisticCorp')->getLogisticInfo(array('fx_logistic_type.lt_id' => $lt_id), array('fx_logistic_corp.lc_cash_on_delivery','fx_logistic_type.lt_expressions'));
        $lt_expressions = json_decode($logistic_info['lt_expressions'],true);
        //echo'<pre>';print_r($lt_expressions);die;
        if(!empty($lt_expressions['logistics_configure']) && $ary_res['all_price'] >= $lt_expressions['logistics_configure']){
            $logistic_price = 0;
        }else{
            //获取赠品信息
            if(!empty($cart_gifts)){
                $ary_tmp_cart = array_merge($ary_tmp_cart,$cart_gifts);
                foreach($ary_tmp_cart as $atck=>$atcv){
                    $ary_tmp_cart[$atcv['pdt_id']] = $atcv;
                    unset($ary_tmp_cart[$atck]);
                }
            }
            // 满足满包邮条件
            foreach ($pro_datas as $pro_data) {
                if ($pro_data ['pmn_class'] == 'MBAOYOU') {
                    foreach($pro_data['products'] as $proDatK=>$proDatV){
                        unset($ary_tmp_cart[$proDatK]);
                    }
                }
            }
            if(empty($ary_tmp_cart)){
                $ary_tmp_cart = array('pdt_id'=>'MBAOYOU');
            }
            $logistic_price = D('Logistic')->getLogisticPrice($lt_id, $ary_tmp_cart,$ary_member['m_id']);
        }
        //使用优惠券
        if (isset($str_csn)) {
            $ary_coupon = D('Coupon')->CheckCoupon($str_csn, $ary_data ['ary_product_data']);
            //echo'<pre>';print_r($ary_coupon);die;
            $date = date('Y-m-d H:i:s');
            if($ary_coupon['status'] == 'error'){
                if($type == 0){
                    $ary_res ['errMsg'] = $ary_coupon['msg'];
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['coupon_price'] = '0';
                }
            } else {
                foreach ($ary_coupon['msg'] as $coupon){
                    if ($coupon ['c_condition_money'] > 0 && $ary_res ['all_price'] < $coupon ['c_condition_money']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}优惠券不满足使用条件";
                            $ary_res ['success'] = 0;
                            break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($coupon ['c_is_use'] == 1 || $coupon ['c_used_id'] != 0) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}被使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($coupon ['c_start_time'] > $date) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}不能使用";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } elseif ($date > $coupon ['c_end_time']) {
                        if($type == 0){
                            $ary_res ['errMsg'] = "编号{$coupon['ci_sn']}活动已经结束";
                            $ary_res ['success'] = 0;break;
                        }else{
                            $ary_res ['coupon_price'] = '0';
                        }
                    } else {
                        if($type == 0){
                            $ary_res ['sucMsg'] = '可以使用';
                            $ary_res ['success'] = 1;
                        }
                        if($coupon['c_type'] == '1'){
                            //计算参与优惠券使用的商品
                            if($coupon['gids'] == 'All'){
                                $ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$ary_res['all_price']);
                            }else{
                                //计算可以使用优惠券总金额
                                $coupon_all_price = 0;
                                foreach ($pro_datas as $keys => $vals) {
                                    if($keys == 0){
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = $this->cart->getProductInfo(array($key => $val));
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $coupon_all_price += $provals['pdt_price']*$provals['num'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $coupon_all_price += $val['pdt_price']*$val['num'];     //商品总价
                                            }
                                        }
                                    }else{
                                        $other_total_price = 0;
                                        foreach ($vals['products'] as $key => $val) {
                                            $arr_products = $this->cart->getProductInfo(array($key => $val));
                                            if ($arr_products[0][0]['type'] == '4') {
                                                foreach ($arr_products[0] as $provals) {
                                                    if(in_array($vals['g_id'],$coupon['gids'])){
                                                        $other_total_price += $provals['pdt_price']*$provals['num'];     //商品总价
                                                    }
                                                }
                                            }
                                            if(in_array($val['g_id'],$coupon['gids'])){
                                                $other_total_price += $val['pdt_price']*$val['num'];     //商品总价
                                            }
                                        }
                                        if($other_total_price > $vals['goods_total_price']){
                                            $coupon_all_price += $vals['goods_total_price'];
                                        }else{
                                            $coupon_all_price += $other_total_price;
                                        }
                                    }
                                }
                                $ary_res ['coupon_price'] +=sprintf('%.2f',(1-$coupon ['c_money'])*$coupon_all_price);
                            }
                        }else{
                            $ary_res ['coupon_price'] += $coupon ['c_money'];
                        }
                    }
                }
            }
        }
        //红包支付
        if ($bonus > 0) {
            $arr_bonus = M('Members')->field("m_bonus")->where(array('m_id'=>$ary_member['m_id']))->find();
            if($bonus > $arr_bonus['m_bonus']){
                if($type == 1){
                    $ary_res ['errMsg'] = '红包金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['bonus_price'] = '0';
                }
            }elseif($ary_res ['all_price'] < $bonus) {
                if($type == 1){
                    $ary_res ['errMsg'] = '红包金额超过了商品总金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['bonus_price'] = '0';
                }
            }else{
                if($type == 1){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['bonus_price'] = $bonus;
                }else{
                    $ary_res ['bonus_price'] = $bonus;
                }
            }
        }else{
            if($type == 1){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['bonus_price'] = 0;
            }else{
                $ary_res ['bonus_price'] = 0;
            }
        }
        //储值卡支付
        if ($cards > 0) {
            $arr_cards = M('Members')->field("m_cards")->where(array('m_id'=>$ary_member['m_id']))->find();
            if($cards > $arr_cards['m_cards']){
                if($type == 2){
                    $ary_res ['errMsg'] = '储值卡金额不能大于用户可用金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['cards_price'] = 0;
                }
            }elseif($ary_res ['all_price'] < $cards) {
                if($type == 2){
                    $ary_res ['errMsg'] = '储值卡金额超过了商品总金额';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['cards_price'] = 0;
                }
            }else{
                if($type == 2){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                    $ary_res ['cards_price'] = $cards;
                }else{
                    $ary_res ['cards_price'] = $cards;
                }
            }
        }else{
            if($type == 2){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['cards_price'] = 0;
            }else{
                $ary_res ['cards_price'] = 0;
            }
        }
        /**
        //金币支付
        if ($jlb > 0) {
        $arr_jlb = M('Members')->field("m_jlb")->where(array('m_id'=>$ary_member['m_id']))->find();
        if($jlb > $arr_jlb['m_jlb']){
        if($type == 3){
        $ary_res ['errMsg'] = '金币金额不能大于用户可用金额';
        $ary_res ['success'] = 0;
        }else{
        $ary_res ['jlb_price'] = 0;
        }
        }elseif($ary_res ['all_price'] < $jlb) {
        if($type == 3){
        $ary_res ['errMsg'] = '金币金额超过了商品总金额';
        $ary_res ['success'] = 0;
        }else{
        $ary_res ['jlb_price'] = 0;
        }
        }else{
        if($type == 3){
        $ary_res ['sucMsg'] = '可以使用';
        $ary_res ['success'] = 1;
        $ary_res ['jlb_price'] = $jlb;
        }else{
        $ary_res ['jlb_price'] = $jlb;
        }
        }
        }else{
        if($type == 3){
        $ary_res ['sucMsg'] = '';
        $ary_res ['success'] = 1;
        $ary_res ['jlb_price'] = 0;
        }else{
        $ary_res ['jlb_price'] = 0;
        }
        }**/
        //积分支付
        if ($point > 0) {
            $pointCfg = D('PointConfig');
            // 计算订单可以使用的积分
            $is_use_point = $pointCfg->getIsUsePoint($ary_res['all_price'],$ary_member['m_id']);
            if($point <= $is_use_point){
                $ary_data = $pointCfg->getConfigs();
                $consumed_points = sprintf("%0.2f",$ary_data['consumed_points']);
                //积分抵扣的总金额
                $ary_res['point_price'] = (0.01/$consumed_points)*$point;
                $ary_res ['points'] = $point;
                if($type == 4){
                    $ary_res ['sucMsg'] = '可以使用';
                    $ary_res ['success'] = 1;
                }
            }else{
                if($type == 4){
                    $ary_res ['errMsg'] = '积分使用失败！不能大于可使用的积分';
                    $ary_res ['success'] = 0;
                }else{
                    $ary_res ['points'] = 0;
                    $ary_res ['point_price'] = 0;
                }
            }
        }else{
            if($type == 4){
                $ary_res ['sucMsg'] = '';
                $ary_res ['success'] = 1;
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
            }else{
                $ary_res ['points'] = 0;
                $ary_res ['point_price'] = 0;
            }
        }
        $ary_res ['logistic_price'] = $logistic_price;
        $ary_res ['promotion_price'] = sprintf("%0.2f", $promotion_price);
        $result_price = sprintf("%0.2f", $promotion_total_price - $ary_res['promotion_price'] - $ary_res['coupon_price'] - $ary_res['bonus_price'] - $ary_res['cards_price'] - $ary_res['jlb_price'] - $ary_res['point_price']);
        //echo $result_price;die;
        if($result_price<0){
            $ary_res ['errMsg'] = '使用抵扣总金额超过了商品总金额';
            $ary_res ['success'] = 2;
            $ary_res ['points'] = 0;
            $ary_res ['point_price'] = 0;
            $ary_res ['cards_price'] = 0;
            $ary_res ['bonus_price'] = 0;
            $ary_res ['coupon_price'] = '0';
            $ary_res['all_price'] = sprintf("%0.2f", $promotion_total_price - $ary_res['promotion_price'] + $ary_res['logistic_price']);
        }else{
            $ary_res['all_price'] = sprintf("%0.2f", $result_price + $ary_res['logistic_price']);
        }
        //echo $ary_res['logistic_price'];die;
        echo json_encode($ary_res);
        exit();
    }

    /**
     * 设置——用户自己的操作
     * 修改密码、修改资料、查看收货地址
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-01-08
     */
    public function mySelf(){
        $member_info = $_SESSION['Members'];
        $this->assign('member_info',$member_info);
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

    /**
     * 头像保存
     * 头像上传成功后，ajax直接保存
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-01-14
     */
    public function upLoadFile(){
        $ary_result = array();
        $img = $_FILES['headPortrait'];
        $tmp_name = $img['tmp_name'];
        $img_type = $img['type'];
        $img_size = $img['size'];
        if($img_size > 200*1024){ //验证上传头像的文件大小
            $ary_result['result'] = false;
            $ary_result['msg'] = '您上传的文件过大';
            exit("<script>parent.callback(".json_encode($ary_result).");</script>");
        }
        //exit("<script>parent.callback(".json_encode($img).");</script>");
        switch($img_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $img_fix = '.jpg';
                break;
            case 'image/png':
                $img_fix = '.png';
                break;
            case 'image/gif':
                $img_fix = '.gif';
                break;
            default:
                $ary_result['result'] = false;
                $ary_result['msg'] = '文件格式不正确';
                exit("<script>parent.callback(".json_encode($ary_result).");</script>");
                break;
        }
        $file_path = '/Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/';
        $member = session('Members');
        if(!is_dir(FXINC.$file_path)) {
            mkdir(FXINC.$file_path, 0755,true);
        }
        $img_real_path = $file_path . time(). $member['m_id'] . $img_fix;
        $mv_res = move_uploaded_file($tmp_name, FXINC . $img_real_path);
		//dump($member['m_id']);die;
        if($mv_res) {
			$u_id = D('MembersFieldsInfo')->where(array('u_id'=>$member['m_id']))->find();
			if($u_id){
				$result = D('MembersFieldsInfo')->where(array('u_id'=>$member['m_id']))->setField('content',$img_real_path);
			}else{
				$arr = array();
				$membs = D('MembersFieldsInfo')->doAdd($arr,$member['m_id'],2);
				$result = D('MembersFieldsInfo')->where(array('u_id'=>$member['m_id']))->setField('content',$img_real_path);
			}
            if($result){
                $ary_result['result'] = true;
                $ary_result['msg'] = '文件上传成功';
                $ary_result['img_src'] = $img_real_path;
                exit("<script>parent.callback(".json_encode($ary_result).");</script>");
            }else{
                $ary_result['result'] = false;
                $ary_result['msg'] = '保存文件失败';
                exit("<script>parent.callback(".json_encode($ary_result).");</script>");
            }
        }else{
            $ary_result['result'] = false;
            $ary_result['msg'] = '文件上传失败';
            exit("<script>parent.callback(".json_encode($ary_result).");</script>");
        }

    }

    public function getcommonInfo(){
        $m_id = $_SESSION['Members']['m_id'];
        $tpl = '';
         if(file_exists($this->wap_theme_path.'Ucenter/Tpl/common/common.html' )){
             //积分数
             $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
             //print_r($ary_point);exit;
             $valid_point = 0;//有用积分数
             if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
                 $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
             }
            //订单数
            $orders = M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('m_id'=>$m_id,'o_pay_status'=>0,'o_status'=>1))->count();
            //收藏数
            $collects = M('CollectGoods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id))->count();

            //评论数
            $comments = M('GoodsComments',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$m_id,'gcom_star_score'=>array('neq',0)))->count();
			//站内信数量统计
			$noticeObj = D('StationLetters');
			$ary_where = array();
			$m_id = $_SESSION['Members']['m_id'];
			$ary_where['fx_station_letters.sl_from_del_status'] = 1;
			$ary_where['fx_related_station_letters.rsl_to_m_id'] = $m_id;
			$messages = $noticeObj->join("fx_related_station_letters on fx_related_station_letters.sl_id = fx_station_letters.sl_id ")->where($ary_where)->count();
			
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/common/common.html'  ;
            $this->assign('valid_point',$valid_point);//积分总和
            $this->assign('orders',$orders);
            $this->assign('collects',$collects);
            $this->assign('comments',$comments);
            $this->assign('messages',$messages);
            $this->display($tpl);
        }else{
            return false;
        }

    }

}
