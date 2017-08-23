<?php

/**
 * 会员结余款相关 Model
 * @package Model
 * @version 7.3
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2013-08-07
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class ApiBalanceModel extends GyfxModel {
	
	//会员结余款
	protected $item_map = array(
        'name' =>'m_name', // 会员代码
		'total' =>'m_balance',//会员结余款
        'real_name' =>'m_real_name', // 会员真实姓名
		'email' =>'m_email', // 邮箱
		'mobile'  =>'m_mobile', // 手机号
		'create_time'  =>'m_create_time', // 生成时间
		'update_time'  =>'m_update_time', // 修改时间爱你
	);
	
	//会员结余款流水帐
	protected $vjyktzdmx_map = array(
        'name' =>'fx_members.m_name', // 会员代码
		'djbh' =>'bi_sn',//单号
        'id' =>'outside_id', // 外部来源单号
		'money' =>'bi_money', // 邮箱
		'type_code'  =>'bi_type', // 调整类型：1:购物消费；2：账户退款；3：账户充值；4:结余款体现
		'tradeno'=>'o_id,or_id,ps_id,bi_accounts_receivable',
		'bank'  =>'bi_accounts_bank', // 收款银行
		'bank_sn'  =>'bi_accounts_receivable', // 银行账号
		'payeec'  =>'bi_payeec', //收款人
		'payment_time'=>'bi_payment_time',//付款时间
		'sheetmaker'=>'sheetmaker',//制单人
		'verify_status'=>'bi_verify_status',//审核状态：0未审核，1已审核,2作废
		'service_verify'=>'bi_service_verify',//客审状态：0未审核，1已审核
		'finance_verify'=>'bi_finance_verify',//财审状态：0未审核，1已审核
		'create_time'  =>'bi_create_time', // 生成时间
		'update_time'  =>'bi_update_time', // 修改时间
		'memo'  =>'bi_desc', // 详情
	);
	
	/**
	 * 构造方法
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-08-07
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * 根据条件查询会员结余款
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-08-07
	 * @fields String 必须 需返回的字段列表。可选值：balance结构体中的所有字段；以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @balance_list 返回结果
	 * @total_results 搜索到符合条件的结果总数
	 *
	 */
	public function MemberBalanceGet($array_params) {
		//提交查询的字段
		$post_fileds = explode(',',$array_params["fields"]);
		//获得主键数组
		$map_values = array_keys($this->item_map);
		//获取$post_fileds里在map_values存在的数组
		$ary_fields = array_intersect($post_fileds,$map_values);
		//获取真实字段
		$ary_filed = D('ApiBalance')->parseFieldsMapToReal($ary_fields);
		//结余款传不传都要读出来;
		if(!in_array('m_balance', $ary_filed)){
			$ary_filed[] = 'm_balance';
		}
		$ary_filed = implode(',', $ary_filed);
		//拼凑where条件
		$ary_where = array();
		$ary_order = '';
		if(!empty($array_params['condition'])){
			$ary_where['_string'] = D('ApiBalance')->getAryWhere($array_params['condition']);
		}
		//排序
		if(!empty($array_params['orderby'])){
			if($array_params['orderby'] == 'create_time'){
				$ary_order = 'm_create_time '.trim($array_params['orderbytype']);
			}
			if($array_params['orderby'] == 'update_time'){
				$ary_order = 'm_update_time '.trim($array_params['orderbytype']);
			}
		}
		$ary_limit['page_size'] = empty($tag['page_size'])?'10':$tag['page_size'];
		$ary_limit['page_no'] = empty($tag['page_no'])?'1':$tag['page_no'];
		$ary_data = $this->getBalanceList($ary_where,$ary_order,$ary_limit,$ary_filed);
		$ary_itemlist = array();
		$ary_itemlist['banlance_list']['banlance'] = $ary_data['list'];
		$ary_itemlist['total_results'] = $ary_data['total_results'];
		if(empty($ary_itemlist)){
			return array();
		}else{
			return $ary_itemlist;
		}
	}

	/**
	 * 查询会员结余款方法
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-08-07
	 * @return total_results：总数;list:数据
	 */
	public function getBalanceList($ary_where,$ary_order,$ary_limit,$ary_filed){
		//查询总数
		$int_count = D('Gyfx')->getCount('members',$ary_where);
		//查询每页数据
		$ary_balance_data = D('Gyfx')->selectAll('members',$ary_filed, $ary_where, $ary_order,$ary_group=null,$ary_limit);
		$ary_balances = array();
		$this->_map = $this->item_map;
		foreach($ary_balance_data as &$ary_balance){
			//处理字段映射(返回的数据处理，字段一一对应)
			$ary_balances[] = D('ApiBalance')->parseFieldsMap($ary_balance);
		}
		unset($ary_balance_data);
		return array('total_results'=>$int_count,'list'=>$ary_balances);
	}

	/**
	 * 新增会员结余款调整单
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-08-07
	 * request params
	 * @hydm 					String 	必须*会员代码
	 * @id 						String	必须*外部来源单号
	 * @money 					String	必须*调整金额。( 保留10位数,最大值可为 99999999.99 )
	 * @type_code 				String	必须*调整类型：0为收入，1为支出，2为冻结
	 * @tradeno->o_id			String		test00000001
	 * @memo->bi_desc			String		testbz
	 * @bank->bi_accounts_bank	String		中国银行
	 * @bank_sn					String		银行账号
	 * @payeec->bi_payeec		String		收款人
	 * @payment_time			String		付款时间
	 * @return_id->or_id		String		退款单号
	 * @sheetmaker	通过u_id处理	String		制单人
	 * @receive_id	ps_id		String		收款单号
	 * reponse params
	 * @created 				Datetime	创建时间
	 * @djbh 					String		单据编号
	 * @id	             		String		外部来源单号
	 * return array 成功或失败
	 */
	public function huiyuanAddbalance($array_params) {
		$ary_data = array();
		//数据处理
		$ary_data['bi_money'] = $array_params['money'];
		$ary_data['outside_id'] = $array_params['id'];
		$ary_data['bt_id'] = $array_params['type_code'];
		$ary_data['bi_desc'] = $array_params['memo'];
		$ary_data['bi_accounts_bank'] = $array_params['bank'];
		$ary_data['bi_accounts_receivable'] = $array_params['bank_sn'];
		$ary_data['bi_payeec'] = $array_params['payeec'];
		$ary_data['bi_payment_time'] = $array_params['payment_time'];
		$ary_data['sheetmaker'] = $array_params['sheetmaker'];
		//提交过来数据判断下
		$str_member_id = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_name'=>$array_params['name']))->getField('m_id');
		if(!$str_member_id){
			return array('status'=>'202','msg'=>'会员在分销系统不存在,不能新增');
		}
		//外部来源单号是否存在
		$str_outresult = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('outside_id'=>$ary_data['outside_id']))->count();
		if($outresult>0){
			return array( 'status' => '202','msg' => '外部来源单号已存在' );
		}
		$ary_data['m_id'] = $str_member_id;
		switch ($array_params ['type_code']) {
			case 1 :
				$ary_data ['o_id'] = $array_params ['tradeno'];
				break;
			case 2 :
				$ary_data ['or_id'] = $array_params ['tradeno'];
				break;
			case 3 :
				$ary_data ['ps_id'] = $array_params ['tradeno'];
				break;
			case 4 :
				$ary_data ['bi_accounts_receivable'] = $array_params ['tradeno'];
				break;
			default :
				return array ( 'status' => '202','msg' => '调整类型不存在' );
		}
		if($array_params['type_code'] == 4){
			if(empty($array_params['bank']) && empty($array_params['bank_sn']) && empty($array_params['payeec']) && empty($array_params['payment_time'])){
				return array('status'=>'202','msg'=>'当调整类型为结余款提现时bank,bank_sn,payeec,payment_time不允许为空');
			}
		}
		//判断必输条件
		if($array_params['type_code'] == 1){
			$ary_balance_where=array();
			$ary_balance_where['o_id']=$ary_data['o_id'];
			$ary_balance_where['m_id']=$ary_data['m_id'];
			$ary_return_result = M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_balance_where)->field('o_all_price')->find();
			if(empty($ary_return_result)||($ary_data['bi_money'] > $ary_return_result['o_all_price'])){
				return array('status'=>'202','msg'=>'您输入的金额大于会员的可用余额');
			}
			$balance = D('Members')->where(array('m_id'=>$ary_data['m_id']))->getField('m_balance');
			if($balance < $ary_data['bi_money']){
				return array('status'=>'202','msg'=>'您输入的金额大于会员的可用余额');
			}
		}
		if($array_params['type_code'] == 2){
			$ary_balance_where=array();
			$ary_balance_where['or_id']=$ary_data['or_id'];
			$ary_balance_where['bi_verify_status']=array('neq',2);
			$ary_return_result = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_balance_where)->find();
			if(isset($ary_return_result)){
				return array('status'=>'202','msg'=>'已存在相同的退款单号');
			}
			$ary_balance_where = array();
			$ary_balance_where['or_return_sn']=$ary_data['or_id'];
			$ary_balance_where['m_id']=$ary_data['m_id'];
			$ary_return_result = M('orders_refunds',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_balance_where)->find();
			if(empty($ary_return_result)||($ary_data['bi_money'] > $ary_return_result['or_money'])){
				return array('status'=>'202','msg'=>'此退款单号存在或您输入的金额大于退款余额');
			}
		}
		//获取是否自动审核 客审:PENDING 财审:FINANCE 作废:INVALID
		//$balanceSet = D('SysConfig')->getCfgByModule('BALANCE_SET');
		$ary_data['bi_create_time'] = date("Y-m-d H:i:s");
		$ary_data['bi_update_time'] = date("Y-m-d H:i:s");
		$ary_data['bi_finance_verify'] = 1;//!empty($balanceSet)&&$balanceSet['FINANCE'] ? '1' : '0';
		$ary_data['bi_service_verify'] = 1;//!empty($balanceSet)&&$balanceSet['PENDING'] ? '1' : '0';
		$ary_data['bi_verify_status'] = 2;//!empty($balanceSet)&&$balanceSet['INVALID'] ? '2' : '0';
		M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
		$ary_result = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->add($ary_data);
		if(FALSE != $ary_result){
			$ary_balance_data = array();
			//结余款调整单单据编号
			$str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
			$ary_balance_data['bi_sn'] = time() . $str_sn;
			$boll_return_result = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('bi_id'=>$ary_result))->data($ary_balance_data)->save();
			if(FALSE != $boll_return_result){
				if($ary_data['bi_finance_verify']){
					$ary_member_data = M('members',C('DB_PREFIX'),'DB_CUSTOM')->field("m_balance")->where(array("m_id"=>$ary_data['m_id']))->find();
					$m_balance = '';
					switch($ary_data['bt_id']){
						case '1':
							$m_balance = $ary_member_data['m_balance'] - $ary_data['bi_money'];
							break;
						case '2':
							$m_balance = $ary_member_data['m_balance'] + $ary_data['bi_money'];
							break;
						case '3':
							$m_balance = $ary_member_data['m_balance'] + $ary_data['bi_money'];
							break;
						case '4':
							$m_balance = $ary_member_data['m_balance'] - $ary_data['bi_money'];
							break;
						default :
							$m_balance = $ary_member_data['m_balance'];
							break;
					}
					$res_balance = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$ary_data['m_id']))->data(array('m_balance'=>$m_balance))->save();
                    if($res_balance){
                        // 结余款调整单日志
                        $add_balance_log ['u_id'] = 0;
                        $add_balance_log ['bi_sn'] = $ary_balance_data['bi_sn'];
                        $add_balance_log ['bvl_desc'] = '审核成功';
                        $add_balance_log ['bvl_type'] = '2';
                        $add_balance_log ['bvl_status'] = '2';
                        $add_balance_log ['bvl_create_time'] = date('Y-m-d H:i:s');
                        if (false === M('balance_verify_log', C('DB_PREFIX'), 'DB_CUSTOM')->add($add_balance_log)) {
                            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                            return array('status'=>'202','msg'=>'生成结余款调整单日志失败!');
                        }
                    }
				}
                if($ary_data['o_id']){
                    // 订单日志记录
                    $ary_orders_log = array(
                        'o_id' => $ary_data['o_id'],
                        'ol_behavior' => '支付成功',
                        'ol_uname' => $ary_data['m_name'],
                        'ol_create' => date('Y-m-d H:i:s')
                    );
                    $res_orders_log = D('OrdersLog')->add($ary_orders_log);
                    if (!$res_orders_log) {
                        M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                        return array('status'=>'202','msg'=>'生成订单日志失败!');
                    }
                }
				M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
				$return_data = array(
                		'created'=>$ary_data['bi_create_time'],
                		'djbh'=>$ary_balance_data['bi_sn'],
                		'id'=>$ary_data['outside_id']
				);
				return array('status'=>'200','data'=>$return_data);
			}else{
				M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
				return array('status'=>'202','msg'=>'操作失败');
			}
		}else{
			return array('status'=>'202','msg'=>'数据有误,请重新输入');
		}
	}

	/**
	 * 记录审核日志
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-08-07
	 */
	public function writeBalanceInfoLog($params){
		 M('balance_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
	}

	/**
	 * 作废会员结余款调整单
	 * request params
	 * djbh	bi_sn	String	(分销单据编号和外部来源单号至少填一个)	171317132		分销单据编号
	 * id	m_name	String	(分销单据编号和外部来源单号至少填一个)	171317132		外部来源单号
	 * reponse params
	 * @created 		 String		创建时间
	 * @djbh 			 String		单据编号
	 * @id	             String		外部来源单号
	 * @response bollen作废成功还是失败
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-08
	 */
	public function huiyuanCancelbalance($array_params) {
		//拼where条件
		$ary_where = array();
		//单据编号
		if(!empty($array_params['djbh'])){
			$ary_where['bi_sn'] = $array_params['djbh'];
		}
		//外部单据编号
		if(!empty($array_params['id'])){
			$ary_where['outside_id'] = $array_params['id'];
		}
		//查询包括财审和客审的
		$ary_arr_where = $ary_where;
		//财审和客审的不允许作废
		$ary_where['bi_service_verify'] = 1;
		$ary_where['bi_finance_verify'] = 1;
		//提交过来数据判断下
		M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
		$ary_data = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
		if(!empty($ary_data) && is_array($ary_data)){
			$params = array(
                        'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                        'bi_sn' => $ary_data['bi_sn'],
                        'bvl_desc'  => '作废失败,该单据已客审财审',
                        'bvl_type'  =>'1',
                        'bvl_status'    =>'2',
                        'bvl_create_time'   =>date("Y-m-d H:i:s")
			);
			$this->writeBalanceInfoLog($params);
			M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
			return array('status'=>'202','msg'=>'作废失败,该单据已客审财审');
		}else{
			$arr_data = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_arr_where)->field('bi_sn,outside_id')->find();
			if(empty($arr_data)){
				M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
				return array('status'=>'202','msg'=>'作废失败,此调整单不存在');
			}else{
				//判断是否已作废
				$val_where = $ary_arr_where;
				$val_where['bi_verify_status'] = 2;
				$int_count_data = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($val_where)->count();
				if($int_count_data>0){
					return array('status'=>'202','msg'=>'此调整单已作废无需再次作废');
				}else{
					$ary_result = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_arr_where)->data(array('bi_verify_status'=>2,'bi_update_time'=>date('Y-m-d H:i:s')))->save();
					if(FALSE != $ary_result){
						$params = array(
		                            'u_id'  =>($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0,
		                            'bi_sn' => $arr_data['bi_sn'],
		                            'bvl_desc'  => 'ERP作废成功',
		                            'bvl_type'  =>'1',
		                            'bvl_status'    =>'1',
		                            'bvl_create_time'   =>date("Y-m-d H:i:s")
						);
						$this->writeBalanceInfoLog($params);
						M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->commit();
						return array('status'=>'200','msg'=>'作废成功','data'=>array('created'=>date('Y-m-d H:i:s'),'djbh'=>$arr_data['bi_sn'],'id'=>$arr_data['outside_id']));
					}else{
						$params = array(
		                            'u_id'  =>($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0,
		                            'bi_sn' => $ary_result['bi_sn'],
		                            'bvl_desc'  => 'ERP作废失败',
		                            'bvl_type'  =>'1',
		                            'bvl_status'    =>'2',
		                            'bvl_create_time'   =>date("Y-m-d H:i:s")
						);
						$this->writeBalanceInfoLog($params);
						M('balance_info',C('DB_PREFIX'),'DB_CUSTOM')->commit();
						return array('status'=>'202','msg'=>'作废失败');
					}
				}
			}
		}
	}

	/**
	 * 查询结余款流水账
	 * request params
	 * @fields String 必须 需返回的字段列表。以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @vjyktzdmx_list 返回结果
	 * return
	 *  hydm	m_name						String		必须*	171317132		会员代码
		id	bi_sn							String		必须*	jyktest000001		外部来源单号
		money	bi_money					String		50		调整金额。( 保留10位数,最大值可为 99999999.99 )
		type_code	bi_type					String		2	2	调整类型：0为收入，1为支出，2为冻结
		tradeno	o_id						String		test00000001		交易号
		memo	bi_desc						String		testbz		备注
		bank	bi_accounts_bank			String		中国银行		银行
		bank_sn	bi_accounts_receivable		String		银行账号
		payeec	bi_payeec					String		收款人
		payment_time	bi_payment_time		String		付款时间
		return_id	or_id					String		退款单号
		receive_id	ps_id					String		收款单号
		verify_status	bi_verify_status	Number		作废状态：0未作废,2已作废
		service_verify	bi_service_verify	Number		客审状态：0未审核，1已审核
		finance_verify	bi_finance_verify	Number		财审状态：0未审核，1已审核
		sheetmaker	通过u_id处理				String		制单人
		@total_results 						Number		搜索到符合条件的结果总数
	 *  @author wangguibin@guanyisoft.com
	 *  @date 2013-08-08
	 */
	public function vjyktzdmxGet($array_params) {
		//提交查询的字段
		$post_fileds = explode(',',$array_params["fields"]);
		//字段赋值
		$this->item_map = $this->vjyktzdmx_map;
		//获得主键数组
		$map_values = array_keys($this->item_map);
		//判断是否存在trademo
		$exist = in_array('tradeno',$map_values);
		//获取$post_fileds里在map_values存在的数组
		$ary_fields = array_intersect($post_fileds,$map_values);
		//获取真实字段
		$ary_filed = D('ApiBalance')->parseFieldsMapToReal($ary_fields);
		$ary_filed = implode(',', $ary_filed);
		//拼凑where条件
		$ary_where = array();
		$order_by = '';
		if(!empty($array_params['condition'])){
			//不支持tradeno搜索
			$is_exist = is_int(strpos($array_params['condition'],'tradeno'));
			if($is_exist == true){
				return array('status'=>'202','msg'=>'tradeno字段不支持搜索');
			}
			$ary_where['_string'] = D('ApiBalance')->getAryWhere($array_params['condition']);
		}
		//条件已财审已客审
		$ary_where['bi_service_verify'] = 1;
		$ary_where['bi_finance_verify'] = 1;
		//排序
		if(!empty($array_params['orderby'])){
			if($array_params['orderby'] == 'create_time'){
				$order_by = 'bi_create_time '.trim($array_params['orderbytype']);
			}
			if($array_params['orderby'] == 'update_time'){
				$order_by = 'bi_update_time '.trim($array_params['orderbytype']);
			}
		}
		$ary_limit['pagesize'] = empty($array_params['page_size'])?'10':$array_params['page_size'];
		$ary_limit['start'] = empty($array_params['page_no'])?'1':$array_params['page_no'];
		$ary_data = $this->getVjyktzdmxList($ary_where,$order_by,$ary_limit,$ary_filed,$exist);
		$ary_itemlist = array();
		$ary_itemlist['vjyktzdmx_list']['vjyktzdmx'] = $ary_data['list'];
		$ary_itemlist['total_results'] = $ary_data['total_results'];
		//dump($ary_itemlist);die();
		if(empty($ary_itemlist)){
			return array('status'=>'202','msg'=>'获取数据为空');
		}else{
			return array('status'=>'200','data'=>$ary_itemlist);
		}
	}

	/**
	 * 查询结余款调整单
	 * request params
	 * @fields String 必须 需返回的字段列表。以半角逗号(,)分隔;
	 * @page_size Number 每页条数.默认返回的数据为10条
	 * @page_no Number 页码.传入值为1代表第一页,传入值为2代表第二页.依次类推.默认返回的数据是从第一页开始
	 * @condition String 条件
	 * @orderby String 选填排序字段
	 * @orderbytype String 选填排序方式，默认ASC
	 * reponse params
	 * @vjyktzd_list 返回结果
	 * return
	 *  hydm	m_name						String				必须*	171317132		会员代码
		id	bi_sn							String				必须*	jyktest000001		外部来源单号
		money	bi_money					String				50		调整金额。( 保留10位数,最大值可为 99999999.99 )
		type_code	bi_type					String				2	2	调整类型：0为收入，1为支出，2为冻结
		tradeno	o_id						String				test00000001		交易号
		memo	bi_desc						String				testbz		备注
		bank	bi_accounts_bank			String				中国银行		银行
		bank_sn	bi_accounts_receivable		String				银行账号
		payeec	bi_payeec					String				收款人
		payment_time	bi_payment_time		String				付款时间
		return_id	or_id					String				退款单号
		receive_id	ps_id					String				收款单号
		verify_status	bi_verify_status	Number				作废状态：0未作废,2已作废
		service_verify	bi_service_verify	Number				客审状态：0未审核，1已审核
		finance_verify	bi_finance_verify	Number				财审状态：0未审核，1已审核
		sheetmaker	通过u_id处理				String				制单人
		total_results 						Number				搜索到符合条件的结果总数
	 * @author wangguibin@guanyisoft.com
	 * @date 2013-08-06
	 */
	public function vjyktzdGet($array_params) {
		//提交查询的字段
		$post_fileds = explode(',',$array_params["fields"]);
		//字段赋值
		$this->item_map = $this->vjyktzdmx_map;
		//获得主键数组
		$map_values = array_keys($this->item_map);
		//判断是否存在trademo
		$exist = in_array('tradeno',$map_values);
		//获取$post_fileds里在map_values存在的数组
		$ary_fields = array_intersect($post_fileds,$map_values);
		//获取真实字段
		$ary_filed = D('ApiBalance')->parseFieldsMapToReal($ary_fields);
		$ary_filed = implode(',', $ary_filed);
		//拼凑where条件
		$ary_where = array();
		$order_by = '';
		if(!empty($array_params['condition'])){
			//不支持tradeno搜索
			$is_exist = is_int(strpos($array_params['condition'],'tradeno'));
			if($is_exist == true){
				return array('status'=>'202','msg'=>'tradeno字段不支持搜索');
			}
			$ary_where['_string'] = D('ApiBalance')->getAryWhere($array_params['condition']);
		}
		//排序
		if(!empty($array_params['orderby'])){
			if($array_params['orderby'] == 'create_time'){
				$order_by = 'bi_create_time '.trim($array_params['orderbytype']);
			}
			if($array_params['orderby'] == 'update_time'){
				$order_by = 'bi_update_time '.trim($array_params['orderbytype']);
			}
		}
		$ary_limit['pagesize'] = empty($array_params['page_size'])?'10':$array_params['page_size'];
		$ary_limit['start'] = empty($array_params['page_no'])?'1':$array_params['page_no'];
		$ary_data = $this->getVjyktzdmxList ( $ary_where, $order_by, $ary_limit, $ary_filed, $exist );
		$ary_itemlist = array ();
		$ary_itemlist ['vjyktzd_list'] ['vjyktzd'] = $ary_data ['list'];
		$ary_itemlist ['total_results'] = $ary_data ['total_results'];
		if(empty($ary_itemlist)){
			return array('status'=>'202','msg'=>'获取数据为空');
		}else{
			return array('status'=>'200','data'=>$ary_itemlist);
		}
	}

	/**
	 * 查询会员结余款流水帐方法
	 * @author wangguibin<wangguibin@guanyisoft.com>
	 * @date 2013-08-08
	 */
	public function getVjyktzdmxList($ary_where,$order_by,$ary_limit,$ary_filed,$exist){
		$obj_balance = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM');
		$int_count = $obj_balance->join(C('DB_PREFIX').'members on('.C('DB_PREFIX').'balance_info.m_id='.C('DB_PREFIX').'members.m_id) ')->where($ary_where)->count();
		$ary_data = $obj_balance
					->join ( C('DB_PREFIX').'members on('.C('DB_PREFIX').'balance_info.m_id='.C('DB_PREFIX').'members.m_id) ' )
					->where ( $ary_where )
					->field ( $ary_filed )
					->order ( $order_by )
					->limit ( ($ary_limit ['start'] - 1) * $ary_limit ['pagesize'], $ary_limit ['pagesize'] )
					->select ();
		$ary_items = array();
		$this->_map = $this->vjyktzdmx_map;
		$this->_map['name'] = 'm_name';
		foreach($ary_data as $key=>&$item){
			foreach($item as &$val){
				if(!isset($val)){
					$val = ' ';
				}				
			}
		}
		foreach($ary_data as &$item){
			$ary_items[] = D('ApiBalance')->parseFieldsMap($item);
		}
		unset($ary_data);
		if($exist == true){
			foreach($ary_items as $key=>$item_info){
				$ary_items[$key]['tradeno'] = $item_info['o_id'].' '.$item_info['or_id'].' '.$item_info['ps_id'].' '.$item_info['bi_accounts_bank'];
				$ary_items[$key]['tradeno'] = trim($ary_items[$key]['tradeno']);
				unset($ary_items[$key]['o_id']);
				unset($ary_items[$key]['or_id']);
				unset($ary_items[$key]['ps_id']);
				unset($ary_items[$key]['bi_accounts_bank']);
			}
		}
		return array('total_results'=>$int_count,'list'=>$ary_items);
	}


}
