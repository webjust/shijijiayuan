<?php

/**
 * 前台使用商品展示类
 *
 * @package Action
 * @subpackage Home
 * @stage 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-09-18
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
Class TryAction extends HomeAction{

	private $tryModel;
	/**
     * 初始化操作
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-18
     */
	public function _initialize() {
		parent::_initialize();
		$this->tryModel = D('Try');
	}

	/**
	 * 首先运行
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-18
	 */
	protected function _autoload(){
		$is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
	}

	/**
	 * 试用中心(首页)控制器
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-18
	 */
	public function index(){
		$this->_autoload();
		$ary_request = $this->_request();
		$tryModel = $this->tryModel;
		// 获取广告
		$ary_ad_infos = $tryModel->GetTryAds();
		// 分页处理
		$currentPage = (int)$ary_request['p'];
		if(0 != $currentPage){
			session('page',$currentPage);
		}
		$int_page_size = 20;
		$ary_where = array('fx_try.try_status'=>array('neq',0));
		// 搜索条件处理
		$order_by = '';
		$int_active_status = $ary_request['active_status'];
		if(!isset($int_active_status) || !in_array(array(0,1,2),$int_active_status)) $int_active_status = 0;

		// 获取数据
		$data = $tryModel->GetTryPageList($ary_where,$order_by,$int_page_size,0);	// 正在试用的活动数据
		$data_future = $tryModel->GetTryPageList($ary_where,$order_by,$int_page_size,1); // 即将开始的试用活动数据
		$data_over = $tryModel->GetTryPageList($ary_where,$order_by,$int_page_size,2); // 即将开始的试用活动数据
		// 获取最近申请的试用
		$field = array(
			C("DB_PREFIX")."try_apply_records.try_status",
			C("DB_PREFIX")."try_apply_records.tar_id",
			C("DB_PREFIX")."try_apply_records.tar_create_time",
			C("DB_PREFIX")."try_apply_records.try_oid",
			"goods.g_picture",
			"goods.g_name",
			"goods.g_id",
			"try.try_id",
			"try.try_picture",
			"try.try_title",
			"orders.o_status",
			"member.m_name"
			);
		$apply_list = D('TryApply')->GetTryApplyList('',$field,'tar_create_time DESC',4);
		// 求试用总数
		$ary_request['total'] = $tryModel->sum('fx_try.try_now_num');
		$ary_request['active_status'] = $int_active_status;
		// 传值
		$this->assign("filter", $ary_request);	// 过滤条件
		$this->assign("page", $data['page']);	// 分页

		$this->assign("data_apply",$apply_list['list']);
        $this->assign("data", $data['list']);	// 正在进行中的试用数据
        $this->assign("data_future", $data_future['list']);	// 即将开始的试用数据
        $this->assign("data_over", $data_over['list']);	// 已经结束的试用数据

		$this->assign('ary_ads',$ary_ad_infos);	// 广告
		// 输出模版
		$this->setTitle('试用列表');
		$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/try.html';
        $this->display($tpl);
	}

	/**
	 * 试用详情页面
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-19
	 */
	public function detail(){
		$this->_autoload();
		$ary_request = $this->_request();
		$TryModel    = $this->tryModel;
		if(empty($ary_request['tryid'])){
			$this->error('不存在该商品！');
		}
		$ary_detail = $TryModel->GetTryGoodsDetailsByGid(array('int_try_id' => $ary_request['tryid']));
		if(empty($ary_detail)){
			$this->error('不存在该商品！');
		}
		// 猜你喜欢商品列表
		$glist = $TryModel->GuessUserLikeGoods($ary_detail['g_id']);

		// 获取申请流程图片
		$ary_ads = $TryModel->GetTryAds(true);

		// 获取申请数
		$apply_num = D('try_apply_records')->where(array('g_id'=>$ary_detail['g_id']))->count();
		$ary_detail['apply_num'] = empty($apply_num) ? 0 : $apply_num;

        //获取成功申请数
        $success_num =D('try_apply_records')->where(array('g_id'=>$ary_detail['g_id'],'try_status'=>1))->count();
        $ary_detail['success_num'] = empty($success_num)?0:$success_num;

		// 检查是否有申请
		$ary_apply = D('try_apply_records')->where(array('g_id'=>$ary_detail['g_id'],'m_id'=>$_SESSION['Members']['m_id']))->find();
		if(!empty($ary_apply)){
			$this->assign('apply_info',$ary_apply);
		}
		// 获取申请记录
		$order_by = '';
		$ary_where = array('try.g_id'=>$ary_detail['g_id'],C('DB_PREFIX').'try_apply_records.try_status'=>1);
		$field = array(
			C("DB_PREFIX")."try_apply_records.try_status",
			C("DB_PREFIX")."try_apply_records.tar_id",
			C("DB_PREFIX")."try_apply_records.tar_create_time",
			C("DB_PREFIX")."try_apply_records.try_oid",
			"goods.g_picture",
			"goods.g_name",
			"goods.g_id",
			"try.try_id",
			"try.try_picture",
			"try.try_title",
			"orders.o_status",
			"member.m_name"
			);
		$apply_list = D('TryApply')->GetTryApplyList($ary_where,$field, $order_by, 20);
		//$ary_detail['try_desc']= D('ViewGoods')-> ReplaceItemDescPicDomain($ary_detail['g_desc']);
		$this->assign('reportList',$apply_list['list']);
		$this->assign('ads_info',$ary_ads);
		$this->assign('data',$ary_detail);
		$this->assign('glist',$glist);
		// 输出模板
		$this->setTitle('试用详情');
		$tpl = './Public/Tpl/'. CI_SN .'/'. TPL .'/tryDetails.html';
		$this->display($tpl);
	}

	/**
	 * 试用报告列表
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-10-14
	 */
	public function ReportList(){
		$this->_autoload();
		$ary_request = $this->_request();
		$tryModel = D('Try');
		// 获取广告
		$ary_ad_infos = $tryModel->GetTryAds();
		foreach($ary_ad_infos as $key=>$ad){
			if($ad['rta_id'] == 2){
				$ad_infos = $ad;
			}
		}
		
		$field = array(
			C("DB_PREFIX")."try.g_id",
			C("DB_PREFIX")."try.try_id",
			C("DB_PREFIX")."try.try_picture",
			C("DB_PREFIX")."try.try_num",
			C("DB_PREFIX")."goods_info.g_name",
			C("DB_PREFIX")."goods_info.g_price"
		);
		
		$where = array(
			'try_status' => 1,
			'try_end_time' => array('LT',date('Y-m-d H:i:s'))
			);
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 20;
		$list = $tryModel->field($field)
				->join(C("DB_PREFIX")."goods_info as goods on(goods.g_id=".C('DB_PREFIX')."try.g_id) ")
				->where($where)
				->order('try_end_time asc,try_create_time desc')
				->page($page_no,$page_size)
				->select();
		//七牛图片显示
		foreach($list as $key =>$value ){
			$list[$key]['try_picture'] =D('QnPic')->picToQn($value['g_picture']);
		}
		$count = $tryModel->where($where)->count();
		$obj_page = new Page($count, $page_size);
        $page = $obj_page->show();

		// 传值
		$this->assign('page',$page);
		$this->assign('ary_ads',$ad_infos);	// 广告
		$this->assign('data',$list);
		// 输出模板
		$this->setTitle('报告列表');
		$tpl = './Public/Tpl/'. CI_SN .'/'. TPL .'/ReportList.html';
		$this->display($tpl);
	}

	/**
	 * 报告详情页面
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-19
	 */
	public function ReportDetail(){
		$this->_autoload();
		$oid      = $this->_request('oid');
		$tryid    = $this->_request('tryid');
//		if(empty($oid)){//当oid为空时 查数据库
//			$m_id = $_SESSION ['Members'] ['m_id'];
//			if(!empty($m_id)){
//				$applyInfo =  D('try_apply_records')
//					->where(array( 'try_id'=>$tryid,'m_id'=>$m_id))
//					->find();
//				$oid = $applyInfo['try_oid'];
//			}else{
//				$nowtime = date('Y-m-d H:i:s');
//				$try_info =  D('Try')->where(array("try_id"=>$tryid))->find();
//				if(!empty($try_info) && $try_info['try_status'] == 1 && $try_info['try_end_time'] < $nowtime ){
//					$applyInfo =  D('try_apply_records')
//						->join(C("DB_PREFIX")."try as try on(try.g_id= ".C('DB_PREFIX')."try_apply_records.g_id)")
//						->join(C("DB_PREFIX")."try_report as try_report on try_report.m_id=".C('DB_PREFIX')."try_apply_records.m_id")
//						->where(array( 'fx_try_apply_records.try_id'=>$try_info['try_id'],'try_report.try_id'=>$try_info['try_id'],'try_report.tr_status'=> 1,'fx_try_apply_records.try_oid'=>array("neq",0)))
//						->find();
//					$oid = $applyInfo['try_oid'];
//				}
//			}
//		}
		$TryModel = $this->tryModel;
		if(!empty($oid) || $oid == 0){
			$ary_where  = array('oid'=>$oid);
			$apply_info = D('TryApply')->GetTryRecords($ary_where);
			// 获取试用商品的报告
			$ary_where = array(
				'tar_id'          => $apply_info['tar_id'],
				'property_typeid' => $apply_info['property_typeid']
				);
			$ary_report = D('TryReport')->getReportAnswer($ary_where);

			if(empty($ary_report)){
				$this->error('没有报告!');
			}

			$where = array(
				'fx_try_report.property_typeid' => $apply_info['property_typeid'],
				'apply.try_oid'=>$apply_info['try_oid']
			);
			//$try_report_status = D('TryReport')->getReportStatus($where);
			$ary_data = D('try_report')
				->field(C('DB_PREFIX').'try_report.*,try.*,m.m_name,apply.*')
				->join(C('DB_PREFIX')."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id)")
				->join(C('DB_PREFIX')."try_apply_records as apply on(apply.g_id=try.g_id and ".C('DB_PREFIX')."try_report.m_id=apply.m_id)")
				->join(C('DB_PREFIX')."members as m on(m.m_id=apply.m_id)")
				->where($where)->find();

			if(empty($ary_data) || $ary_data['tr_status'] == 0){
				$this->error('报告尚未审核，暂不能查看!');
			}
		}

		if(!empty($tryid)){
			$ary_where = array('try_id'=>$tryid);
			$apply_info = D('Try')->where($ary_where)->find();
			// 获取全部商品的报告
			// 1.获取该试用的申请记录
			$apply_where = array(
				'g_id'       => $apply_info['g_id'],
				'try_status' => 1,
				'try_oid'    => array('neq',0)
				);
			$ary_apply = D('try_apply_records')->where($apply_where)->select();
			if(empty($ary_apply)){
				$this->error('没有报告!');
			}
			foreach($ary_apply as $apply){
				$tar_id .= $apply['tar_id'].',';
			}
			$tar_id = substr($tar_id,0,-1);
			$ary_where = array(
				'tar_id'          => $tar_id,
				'property_typeid' => $apply_info['property_typeid']
				);
			$ary_report = D('TryReport')->getReportAnswer($ary_where);
		}
		
		if(empty($apply_info)){
			$this->error('不存在该产品!');
		}

		// 获取商品详情
		$ary_detail = $TryModel->GetTryGoodsDetailsByGid(array('int_try_id' => $apply_info['try_id']));

		// 猜你喜欢商品列表
		$glist = $TryModel->GuessUserLikeGoods($apply_info['g_id']);

		// 获取申请流程图片
		$ary_ads = $TryModel->GetTryAds(true);

		// 求平均分值
		foreach($ary_report as $vo_report){
			$total += $vo_report['attr_value'];
		}
		$ary_total['report_avg'] = $total/count($ary_report);
		// 获取试用统计信息 1.多少人获取到试用品,2.多少份报告
		$ary_total['get_goods_num'] = D('Try')->getTryGoodsNum(array('try_id'=>$apply_info['try_id']));
		$ary_total['report_num'] = D('TryReport')->getTryReportNum(array('try_id'=>$apply_info['try_id']));
		$ary_total['report_num_checked'] = D('TryReport')->getTryReportNum(array('try_id'=>$apply_info['try_id'],'tr_status'=>'1'));

		// 试用报告详情
		$ary_where = array(
			'try.g_id'                                    => $ary_detail['g_id'],
			C('DB_PREFIX').'try_apply_records.try_status' => 1
			);
		$field = array(
			C("DB_PREFIX")."try_apply_records.try_status",
			C("DB_PREFIX")."try_apply_records.tar_id",
			C("DB_PREFIX")."try_apply_records.tar_create_time",
			C("DB_PREFIX")."try_apply_records.try_oid",
			"goods.g_picture",
			"goods.g_name",
			"goods.g_id",
			"try.try_id",
			"try.try_picture",
			"try.try_title",
			"orders.o_status",
			"member.m_name"
			);
		$apply_list = D('TryApply')->GetTryApplyList($ary_where,$field, $order_by, 20);

		$ary_total['color'] = array('blue','orange','green','red');


		if(empty($oid)){//当oid为空时 说明是从结束活动进入
			$this->assign('data',$ary_detail);
			$this->assign('total',$ary_total);
			$this->assign('reportList',$apply_list['list']);
		}else{
			$this->assign('reportList',$apply_list['list']);
			$this->assign('total',$ary_total);
			$this->assign('report',$ary_report);
			$this->assign('data',$ary_detail);
			$this->assign('glist',$glist);
			$this->assign('ads_info',$ary_ads);
		}
		// 输出模板
		$this->setTitle('报告详情');
		$tpl = './Public/Tpl/'. CI_SN .'/'. TPL .'/tryReport.html';
		$this->display($tpl);
	}

	/**
	 * 获取地址
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-22
	 */
	public function getAddress(){
		$ary_request = $this->_request();
		// 检查试用是否合法
		$checkResult = $this->tryModel->checkOrder(array('int_try_id'=>$ary_request['try_id'],'int_gid'=>$ary_request['g_id']));
		// 获取地址
		$ary_address = D('CityRegion')->getReceivingAddress($_SESSION ['Members'] ['m_id']);
		// 获取答题
		$ary_question = $this->tryModel->getFrontQuestion(array('int_try_id'=>$ary_request['try_id']));
		$this->assign('array_spec_info',$ary_question);

		$this->assign('checkStatus',$checkResult);
		$this->assign('addr',$ary_address);
		$tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/tryAddress.html';
		$this->display($tpl);
	}

	/**
	 * 生成申请
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-09-23
	 */
	public function doAddOrder(){
		$this->_autoload();
		if(IS_AJAX){
			$ary_request = $this->_request();
			$ary_member = session('Members');
			$result = $this->tryModel->checkOrder(array('int_try_id'=>$ary_request['try_id'],'int_gid'=>$ary_request['g_id']));
			if($result['status'] == 0) $this->ajaxReturn('c',$result['msg'],1);
			// 1.收货地址处理
			$ra_id = '';
			if(!isset($ary_request['old_id'])){	// 使用新创建的收货地址
				$ary_address = array(
					'cr_id'           => $ary_request['region'],
					'm_id'            => $ary_member['m_id'],
					'ra_name'         => $ary_request['name'],
					'ra_detail'       => $ary_request['detail'],
					'ra_post_code'    => $ary_request['zipcode'],
					'ra_phone'        => $ary_request['phone'],
					'ra_mobile_phone' => $ary_request['mobile'],
					'ra_create_time'  => date('Y-m-d H:i:s')
					);
				$ra_id = D('receive_address')->add($ary_address);
				if(empty($ra_id)) $this->ajaxReturn('','添加收货地址出错!请重新填写!',1);
			}else{
				$ra_id = $ary_request['old_id'];
			}
			$ary_apply_addr = D('CityRegion')->getReceivingAddress($ary_member['m_id'],$ra_id);
			if(empty($ary_apply_addr)) $this->ajaxReturn('','不存在该收货地址',1);
			// 获取省份名称
			$CityRegionModel = D('CityRegion');
			$ary_city_data = $CityRegionModel->getFullAddressId($ary_apply_addr['cr_id']);
			// 获取申请信息
			$ary_apply_info = $this->tryModel->GetTryGoodsDetailsByGid(array('int_try_id' => $ary_request['try_id']));
			// 2.3 申请信息
			$ary_apply = array(
				'm_id'                  => $ary_member['m_id'],
				'property_typeid'       => $ary_apply_info['property_typeid'],
				'property_typeid_front' => $ary_apply_info['property_typeid_front'],
				'g_id'                  => $ary_request['g_id'],
				'o_receiver_name'       => $ary_apply_addr['ra_name'],		// 收货人
				'o_receiver_mobile'     => $ary_apply_addr['ra_mobile_phone'],	// 收货人手机
				'o_receiver_telphone'   => $ary_apply_addr['ra_phone'],		// 收货人电话
				'o_receiver_state'      => $CityRegionModel->getAddressName($ary_city_data [1]),	// 收货人省份
				'o_receiver_city'       => $CityRegionModel->getAddressName($ary_city_data [2]),	// 收货人城市
				'o_receiver_county'     => $CityRegionModel->getAddressName($ary_city_data [3]),	// 地区第三级（文字)
				'o_receiver_address'    => $ary_apply_addr['ra_detail'],		// 收货人地址
				'o_receiver_zipcode'    => $ary_apply_addr['ra_post_code'],	// 收货人邮编
				'ra_id'                 => $ra_id,
				'tar_create_time'       => date('Y-m-d H:i:s'),
				'try_id'       => $ary_request['try_id']
				);
			// 2.4 操作表
			$applyModel    = D('try_apply_records');
			$tryModel      = D('Try');
			$questionModel = D("try_attribute");
			M()->startTrans();
			try{
				$tagApply = false;
				$tagTry   = false;
				$tagApply = $applyModel->add($ary_apply);			// 试用申请
				$tagTry   = $tryModel->where(array('try_id'=>$ary_request['try_id']))->setInc('try_now_num');	// 申请人数自加1

				//问题属性存入数据库，如果有的话。
				$tagQuestion = true;
				if(isset($_POST["question_spec"]) && !empty($_POST["question_spec"])){
					foreach($_POST["question_spec"] as $gs_id=>$spec_value){
						if("" != $spec_value){
							$array_tmp_spec_info = D("GoodsSpec")->where(array("gs_id"=>$gs_id))->find();
							$string_spec_value = $spec_value;
							if(2 == $array_tmp_spec_info["gs_input_type"]){
								if($spec_value <= 0){
									//如果是select类型的扩展属性，且值小于0，则说明未设置此属性的值
									continue;
								}
								$array_tmp_spec_detail = D("GoodsSpecDetail")->where(array("gsd_id"=>$spec_value))->find();
								$string_spec_value = $array_tmp_spec_detail["gsd_value"];
							}
							$question_spec = array();
							$question_spec["try_apply_id"]    = $tagApply;	// 试用申请ID
							$question_spec["property_typeid"] = $ary_apply_info['property_typeid'];	// 试用类型ID
							$question_spec["attr_id"]         = $gs_id;	// 属性ID
							$question_spec["attr_value"]      = $string_spec_value;	// 属性值
							$question_spec["attr_name"]       = $array_tmp_spec_info['gs_name'];	// 属性名称
							$result = $questionModel->add($question_spec);
							if(false === $result){
								$tagQuestion = false;
								break;
							}
						}
					}
				}
				if($tagApply && $tagTry !== false && $tagQuestion !== false){
					M()->commit();
					$this->ajaxReturn('b','恭喜您已经成功申请，请耐心等待系统审核！',2);
				}else{
					M()->rollback();
					$this->ajaxReturn('a','很遗憾!您申请失败！',1);
				}
			}catch(Exception $e){
				M()->rollback();
				$this->ajaxReturn($e,'申请失败',1);
			}
		}else{
			echo "error";exit();
		}
	}

}