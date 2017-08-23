<?php

/**
 * 前台模版首页生成
 *
 * @package Action
 * @subpackage Home
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class OrderAction extends HomeAction {

    protected $dir = '';

    public function _initialize() {
        parent::_initialize();
        
    }
	
	/**
    * 获取订单详情页API:fx.trade.detail.get
    * @request params
	* tid 订单ID
    * html 返回是html
    * @author Wangguibin
    * @date 2014-08-20
    */
	public function fxTradeDetailGet(){
		$array_params = $this->_request();
		
        if(!isset($array_params["oid"]) || "" == $array_params["oid"]){
            $this->errorResult(false,10002,array(),'缺少应用级参数订单号:oid');
		}
		if (!isset($array_params["oid"]) || !is_numeric($array_params["oid"])) {
            $this->error("参数订单ID不合法。");
        }
		//验证是否传入app_key 参数
		if(!isset($array_params["app_key"]) || "" == $array_params["app_key"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数app_key');
		}

		//验证app_secret 合法性
		$str_app_key = $array_params["app_key"];
		/**
		if(SAAS_ON == TRUE){
			//API放在本地调用
			$center = str_replace('mysql://','',C("DB_CENTER"));
			$array_center_config = explode('/',$center);
			$array_hostinfo = explode("@",$array_center_config[0]);
			$array_host_info = explode(":",$array_hostinfo[1]);
			$array_userinfo = explode(":",$array_hostinfo[0]);
			$array_userinfo[1] = (!isset($array_userinfo[1]))?"":$array_userinfo[1];
			$string_conn = "mysql:host=" . $array_host_info[0] . ";dbname=" . $array_center_config[1];
			if(3306 != $array_host_info[1]){
				$string_conn .= ";port=" . $array_host_info[1];
			}
			$pdo_conn = new PDO($string_conn,$array_userinfo[0],$array_userinfo[1]);
			if (!$pdo_conn) {
				//连接管易数据中心失败
				$this->errorResult(false,10001,array(),'验证管易分销授权中心数据库失败。');
			}
			$obj_stmt = $pdo_conn->prepare("select ci_app_secret from `gy_client_info` where `ci_sn`='$str_app_key' limit 1");
			$obj_stmt->setFetchMode(PDO::FETCH_ASSOC);
			if(!$obj_stmt->execute()){
				die("无法获取此域名的用户授权信息。");
			}
			$array_authz =  $obj_stmt->fetch();
		}else{
			if($str_app_key != CI_SN){
				$this->errorResult(false,10001,array(),'错误的app_key');
			}
			$array_authz = array('ci_app_secret'=>APP_SECRET);
		}
		

		if(!is_array($array_authz) || empty($array_authz)){
			$this->errorResult(false,10001,array(),'错误的app_key');
		}else{
			$app_secret = $array_authz['ci_app_secret'];
			$this->ci_sn = $str_app_key;
			$str_db_info = 'mysql://'.C('DB_USER').':'.C('DB_PWD').'@'.C('DB_HOST').'/' . $str_app_key;
			C('DB_CUSTOM', $str_db_info);
			$sys_obj = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM');
			$host = $sys_obj->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
			$_SESSION['HOST_URL'] = $host['sc_value'];
		}
		**/
		//验证是否传递timestamp参数
		if(!isset($array_params["timestamp"]) || "" == $array_params["timestamp"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数timestamp');
		}

		//验证是否传递sign参数
		if(!isset($array_params["sign"]) || "" == $array_params["sign"]){
			$this->errorResult(false,10001,array(),'缺少系统级参数sign');
		}

		//验证是否传入app_secret参数 放在签名里验证
		//生成签名
		//签名时，根据参数名称，将除签名（sign）和图片外所有请求参数按照字母先后顺序排序:key + value .... key + value
		$paramArr = $array_params;
		unset($paramArr['_URL_']);
		unset($paramArr['sign']);
		//dump($paramArr);die();
		$sign = $this->createSign($paramArr,$array_authz['ci_app_secret']);
		if($sign != $array_params['sign']){
			$this->errorResult(false,10001,array(),'数据签名不正确');
		}
		
        $int_oid = $this->_get('oid');
        $where = array('o_id' => $int_oid);
        $ary_orders = D('Orders')->where($where)->find();
        //echo "<pre>";var_dump(D('Orders'));die;
        //订单会员信息
        if (!empty($ary_orders['m_id']) && isset($ary_orders['m_id'])) {
            $ary_members = D('Members')->where(array('m_id' => $ary_orders['m_id']))->find();
        }
        //支付方式
        if (isset($ary_orders['o_payment'])) {
            $payment = D('PaymentCfg')->field('pc_custom_name')->where(array('pc_id' => $ary_orders['o_payment']))->find();
            $ary_orders['payment_name'] = $payment['pc_custom_name'];
        }
        //会员地址
        $ary_city = D('CityRegion')->getFullAddressId($ary_members['cr_id']);
        if (!empty($ary_city) && is_array($ary_city)) {
            //会员省   
            $province = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[1]))->find();
            $ary_members['province'] = $province['cr_name'];
            //会员市
            $city = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[2]))->find();
            $ary_members['city'] = $city['cr_name'];
            //会员区
            $area = D('CityRegion')->field('cr_name')->where(array('cr_id' => $ary_city[3]))->find();
            $ary_members['area'] = $area['cr_name'];
        }
        //echo D('CityRegion')->getLastSql();exit;
        $ary_orders_info = D('OrdersItems')->where($where)->select();
        if (!empty($ary_orders_info)) {
            foreach ($ary_orders_info as $k => $v) {
                //获取商品的规格，类型为2时，只是拼接销售属性的值
                $ary_orders_info[$k]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($v['pdt_id'], 2);

                $ary_goods_pic = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $v['g_id']))->field('g_picture')->find();

                $ary_orders_info[$k]['g_picture'] = getFullPictureWebPath($ary_goods_pic['g_picture']);
                //订单商品退款、退货状态
                $ary_orders_info[$k]['str_refund_status'] = D('Orders')->getOrderItmesStauts('oi_refund_status', $v);
                //订单商品发货
                $ary_orders_info[$k]['str_ship_status'] = D('Orders')->getOrderItmesStauts('oi_ship_status', $v);
                //商品小计
                $ary_orders_info[$k]['subtotal'] = $v['oi_nums'] * $v['oi_price'];
                /* //组合商品当作普通商品显示
                  if($v['oi_type']==3){
                  $combo_sn=$v['g_sn'];
                  $tmp_ary=array('g_sn'=>$ary_orders_info[$k]['g_sn'],'pdt_spec'=>$ary_orders_info[$k]['pdt_spec'],'g_picture'=>$ary_orders_info[$k]['g_picture'],
                  'oi_g_name'=>$ary_orders_info[$k]['oi_g_name'],'pdt_id'=>$ary_orders_info[$k]['pdt_id']);

                  $combo_where = array('g_id' => $ary_orders_info[$k]['g_id'],'releted_pdt_id'=>$ary_orders_info[$k]['pdt_id']);
                  $combo_field = array('com_nums');
                  $combo_res=D('ReletedCombinationGoods')->getComboReletedList($combo_where,$combo_field);
                  $combo_num=$combo_res[0]['com_nums'];

                  $ary_combo[$combo_sn]['item'][$k]=$tmp_ary;
                  $ary_combo[$combo_sn]['num']=$ary_orders_info[$k]['oi_nums']/$combo_num;
                  $ary_combo[$combo_sn]['pdt_sale_price']=$ary_orders_info[$k]['pdt_sale_price'];
                  $ary_combo[$combo_sn]['o_all_price']=$ary_orders_info[$k]['o_all_price'];
                  $ary_combo[$combo_sn]['str_ship_status']=$ary_orders_info[$k]['str_ship_status'];
                  $ary_combo[$combo_sn]['str_refund_status']=$ary_orders_info[$k]['str_refund_status'];
                  unset($ary_orders_info[$k]);
                  }
                 */
            }
        }
        //订单状态
        //付款状态
        $ary_orders['str_pay_status'] = D('Orders')->getOrderItmesStauts('o_pay_status', $ary_orders['o_pay_status']);
        $ary_orders['str_status'] = D('Orders')->getOrderItmesStauts('o_status', $ary_orders['o_status']);
        //订单状态
        $ary_orders_status = D('Orders')->getOrdersStatus($ary_orders['o_id']);
        //echo "<pre>";print_r($ary_orders_status);exit;
        //退款
        $ary_orders['refund_status'] = $ary_orders_status['refund_status'];
        //退货
        $ary_orders['refund_goods_status'] = $ary_orders_status['refund_goods_status'];
        //发货
        $ary_orders['deliver_status'] = $ary_orders_status['deliver_status'];
        //配送方式
        $ary_logistic_where = array('lt_id' => $ary_orders['lt_id']);
        $ary_field = array('lc_name');
        $ary_logistic_info = D('logistic')->getLogisticInfo($ary_logistic_where, $ary_field);
        $ary_orders['str_logistic'] = $ary_logistic_info[0]['lc_name'];

        //echo "<pre>";print_r($ary_orders);exit;
        //物流信息 
        $ary_delivery = D('Orders')->ordersLogistic($int_oid);
        //处理作废		作废类型（1：用户不想要了;2：商品无货;3:重新下单;4:其他原因）
        switch ($ary_orders['cacel_type']) {
            case 1:
                $ary_orders['cacel_title'] = '用户不想要了';
                break;
            case 2:
                $ary_orders['cacel_title'] = '商品无货';
                break;
            case 3:
                $ary_orders['cacel_title'] = '重新下单';
                break;
            case 4:
                $ary_orders['cacel_title'] = '其他原因';
                break;
            default:
                break;
        }
        if ($ary_orders['admin_id']) {
            $ary_orders['admin_name'] = D('Orders')->where(array('u_id' => $ary_orders['admin_id']))->getField('u_name');
        }
//        echo "<pre>";print_r($ary_members);exit;
        $this->assign('members', $ary_members);
        $this->assign('ary_delivery', $ary_delivery);
        $this->assign('ary_orders_info', $ary_orders_info);
        $this->assign('ary_orders', $ary_orders);
        $this->display("Ucenter:Orders:order_detail");
	}
	public function errorResult($status=false,$error_code="",$ary_data = array(),$str_msg="",$msg='Remote service error'){
		$array_data = array(
		'code'=>$error_code,
		'msg'=>$msg,
		'sub_code'=>$ary_data,
		'sub_msg'=>$str_msg
		);
		if($this->format == 'json'){
			$response = array();
			$response['error_response'] = $array_data;
			unset($array_data);
			echo json_encode($response);
			exit;
		}else{
			$xmlData = toXml($array_data);
			echo $xmlData;
			exit;
		}
	}  

	/**
	 *
	 * Enter 签名函数
	 * @param  $paramArr
	 */

	public function createSign ($paramArr,$app_secret) {

		$sign = $app_secret;

		ksort($paramArr);

		foreach ($paramArr as $key => $val) {

			if ($key != '' && $val != '') {

				$sign .= $key.$val;

			}

		}

		$sign.=$app_secret;
		//dump($sign);die();
		$sign = strtoupper(md5($sign));

		return $sign;

	}
		
}
