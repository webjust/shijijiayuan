<?php

/**
 * 后台库存设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
 * @date 2013-04-23
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class StockAction extends AdminAction {

    /**
     * 控制器初始化
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle('库存设置');
    }

    /**
     * 默认控制器
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function index() {
        $this->redirect(U('Admin/Stock/pageSet'));
    }

    /**
     * 库存设置页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function pageSet() {
        $this->getSubNav(3, 0, 7);
        $data = D('SysConfig')->getCfgByModule('GY_STOCK');
        if(!empty($data['USER_TYPE']) || $data['USER_TYPE'] == '0'){
            if($data['USER_TYPE']!='all'){

				$ary_user_level = explode(",",$data['USER_TYPE']);
            }
        }
        if(!empty($ary_user_level)){
            $check_user_level=array_flip($ary_user_level);
        }
        $user_level=D('MembersLevel')->getMembersLevels();
//        echo "<pre>";print_r($user_level);exit;
        if(!empty($user_level)){
            //游客id入栈
            array_push($user_level,array('ml_id'=>'0'));
            foreach($user_level as $key=>$val){
                if(array_key_exists($val['ml_id'],$check_user_level)){
                    if($val['ml_id']!=0){
                        $user_level[$key]['check']=true;
                    }else{
                        $user_level_0['check']=true;
                    }
                }else{
                    $user_level[$key]['check']=false;
                }
            }
        }
        array_splice($user_level,-1);
        $this->assign('data',$data);
       // echo "<pre>";print_r($data);exit;
        $this->assign('user_level',$user_level);
        $this->assign('vo_0',$user_level_0);
        $this->display();
    }

    /**
     * 修改ERP连接设置
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-01-16
     */
    public function doSet(){
        $data = $this->_post();
        $stock_num=$data['stock_num'];
        if(isset($data['Open'])){
            $open=1;
        }else{
            $open=0;
        }
        if($data['user']==1){
            $level="all";
        }else{
            $level=implode(",",$data['level']);
        }
        
        if(isset($data['wat_stock']) && $data['wat_stock'] != '0'){
            $stock_num=$data['stock_num'];
            if(empty($stock_num)){
                $this->error("库存数不能为空");
            }
            $wat_stock="1";
        }else{
            $wat_stock="0";
        }

        if(isset($data['inventory_stock'])){
        	$temp_inventory_stock = max($data['inventory_stock'],0);
        	$inventory_stock = $temp_inventory_stock == 0 ? $temp_inventory_stock : 1;
        }
        if(isset($data['inventory_common'])){
        	$temp_inventory_common = max($data['inventory_common'],0);
        	$inventory_common = $temp_inventory_common == 0 ? $temp_inventory_common : 1;
        }
       // echo "<pre>";print_r($data);exit;
        $SysSeting = D('SysConfig');
        
        if( $SysSeting->setConfig('GY_STOCK', 'OPEN_STOCK', $open, '模糊库存开启') &&
            $SysSeting->setConfig('GY_STOCK', 'STOCK_NUM', $stock_num, '警戒库存') && 
            $SysSeting->setConfig('GY_STOCK', 'USER_TYPE', $level, '用户等级控制') &&
            $SysSeting->setConfig('GY_STOCK', 'WAT_STOCK', $wat_stock, '是否开启预警库存') &&
            $SysSeting->setConfig('GY_STOCK', 'INVENTORY_STOCK',  $inventory_stock, '是否开启分销商库存分配') &&
            $SysSeting->setConfig('GY_STOCK', 'INVENTORY_COMMON',  $inventory_common, '是否开启共享库存')
        ){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"库存设置",serialize($data)));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
	
	/**
     * 库存调整单列表
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-01-16
     */
	public function pageAdd(){
		$this->getSubNav(3, 0, 60);
		$array_products = array(array('pdt_sn'=>'','g_sn'=>"","g_name"=>""));
		if(isset($_GET["g_id"]) && "" != trim($_GET["g_id"])){
			//如果指定了商品ID，则获取商品下的规格
			$array_gid_cond = array('g_id'=>array('in',explode('_',trim(trim($_GET["g_id"]),'_'))));
			//获取商品名称数据
			$array_goods_info = D("GoodsInfo")->where($array_gid_cond)->getField("g_id,g_name");
			$array_products = D("GoodsProductsTable")->where($array_gid_cond)->getField("pdt_id,g_id,g_sn,pdt_sn");
			foreach($array_products as $key => $val){
				$array_products[$key]["g_name"] = $array_goods_info[$val["g_id"]];
			}
		}
		$this->assign("array_products",$array_products);
		$this->display();
	}
	
	public function doAdd(){
		if(!isset($_POST["detail"]["pdt_sn"]) || empty($_POST["detail"]["pdt_sn"])){
			$this->error("缺少库存调整单明细。");
		}
		//事务开始，新建一张库存调整单
		D("StockReviseReceipt")->startTrans();
		$array_insert = array();
		//这是一张手工单据
		$array_insert["srr_type"] = 1;  
		$array_insert["srr_desc"] = (isset($_POST["desc"]) && "" != trim($_POST["desc"]))?$_POST["desc"]:"没有备注";  
		//初始单据状态为未审核
		$array_insert["srr_verify"] = 0;
		$array_insert["srr_create_id"] = $_SESSION["Admin"];
		$array_insert["srr_create_time"] = date("Y-m-d H:i:s");
		$array_insert["srr_update_time"] = date("Y-m-d H:i:s");
		$int_result_id = D("StockReviseReceipt")->add($array_insert);
		if(false === $int_result_id){
			D("StockReviseReceipt")->rollback();
			$this->error("库存调整单录入失败");
		}
		//写入库存调整单明细，首先获取所有的货号对应的货品详情
		$array_pdt_sn = $_POST["detail"]["pdt_sn"];
		$array_type = $_POST["detail"]["srrd_type"];
		$array_num = $_POST["detail"]["srrd_num"];
		//$array_pdtinfo = D("GoodsProductsTable")->where(array("pdt_sn"=>array("IN",$array_pdt_sn)))->getField("pdt_id,pdt_sn");
		//$array_pdtinfo = array_flip($array_pdtinfo);
		foreach($array_pdt_sn as $key=>$val){
			if("" == trim($val) || $array_type[$key] == -1 || !is_numeric($array_num[$key])){
				D("StockReviseReceiptDetail")->rollback();
				$this->error("库存调整单添加失败：至少有一条明细没有输入货号、调整类型或者调整数量。");
				continue;
			}
			//获取PDT_id
			$int_pdt_id = D("GoodsProductsTable")->where(array("pdt_sn"=>$val,"pdt_is_combination_goods"=>'0'))->getField("pdt_id");
			if(false === $int_pdt_id){
				D("StockReviseReceipt")->rollback();
				$this->error("无法获取规格ID.");
			}
			if(null === $int_pdt_id){
				D("StockReviseReceipt")->rollback();
				$this->error("库存调整单添加失败：至少有一个货号无效。");
			}
			$array_detail_insert = array();
			$array_detail_insert["srr_id"] = $int_result_id;
			$array_detail_insert["pdt_id"] = $int_pdt_id;
			$array_detail_insert["srrd_type"] = $array_type[$key];
			$array_detail_insert["srrd_num"] = $array_num[$key];
			$array_detail_insert["srrd_status"] = 1;
			$array_detail_insert["srrd_create_time"] = date("Y-m-d H:i:s");
			$array_detail_insert["srrd_update_time"] = date("Y-m-d H:i:s");
			if(false === D("StockReviseReceiptDetail")->add($array_detail_insert)){
				D("StockReviseReceiptDetail")->rollback();
				$this->error("库存调整单明细添加失败");
			}
		}
		D("StockReviseReceiptDetail")->commit();
		$this->success("库存调整单添加成功。",U("Admin/Stock/pageList"));
	}
		
	/**
     * 新增库存调整单
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-01-16
     */
	public function pageList(){
		$this->getSubNav(3, 0, 50);
		$int_page_size = 20;
		$array_condition = array();
		//搜索条件处理
		$count = D("StockReviseReceipt")->where($array_condition)->count();
		$obj_page = new Page($count, $int_page_size);
		$limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        //获取数据列表
		$array_datalist = D("StockReviseReceipt")->where($array_condition)->order(array("srr_id"=>"DESC"))->limit($limit)->select();
		$array_user = D("Admin")->getField("u_id,u_name");
		foreach($array_datalist as $key => $val){
			$array_datalist[$key]["create_username"] = $array_user[$val["srr_create_id"]];
			$array_datalist[$key]["verify_username"] = "";
			if($val["srr_verify_id"] > 0){
				$array_datalist[$key]["verify_username"] = $array_user[$val["srr_verify_id"]];
			}
		}
		$this->assign("datalist",$array_datalist);
		$this->assign("page",$obj_page->show());
		$this->display();
	}
	
	/**
	 * 异步回去sku sn，用于用户输入货号时的提示使用
	 */
	public function getProductSn(){
		C("LAYOUT_ON",false);
		$keyword = $_GET["q"];
		if(!isset($_GET["q"]) || "" == trim($_GET["q"])){
			echo "please input outer_id!";
			exit;
		}
		//查询products表，获取数据
		$result_data = D("GoodsProductsTable")->where(array("pdt_sn"=>array('like','%' . $_GET["q"] . '%'),"pdt_is_combination_goods"=>'0'))->getField("pdt_sn",true);
		foreach($result_data as $val){
			echo $val . "\n";
		}
		exit;
	}
	
	/**
	 * 编辑库存调整单
	 */
	public function pageEdit(){
		$this->getSubNav(3, 0, 50);
		if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
			$this->error("参数错误：无法找到要修改的库存调整单。");
			exit;
		}
		//获取库存调整单，及其明细
		$array_condition = array("srr_id"=>$_GET["id"]);
		$array_receipt_info = D("StockReviseReceipt")->where($array_condition)->find();
		if(!is_array($array_receipt_info) || empty($array_receipt_info)){
			$this->error("对不起，系统无法找到您要修改的库存调整单。");
			exit;
		}
		//验证库存调整单，如果库存调整单已经被审核或者已作废，则不允许修改
		if(0 != $array_receipt_info['srr_verify']){
			$string_message = "该库存调整单已经被审核，不允许修改。";
			switch($array_receipt_info['srr_verify']){
				case 1:
					$string_message = "该库存调整单已经被审核通过，不允许修改。";
					break;
				case 2:
					$string_message = "该库存调整单已经被作废，不允许修改。";
					break;
			}
			$this->error($string_message);
			exit;
		}
		
		//获取库存调整单详情
		$array_detail_list = D("StockReviseReceiptDetail")->where($array_condition)->select();
		$array_goods_info = array();
		foreach($array_detail_list as $key => $val){
			$array_tmp_info = D("GoodsProductsTable")->where(array("pdt_id"=>$val["pdt_id"]))->getField("pdt_id,pdt_sn,g_id,g_sn");
			$array_tmp_info = $array_tmp_info[$val["pdt_id"]];
			$array_detail_list[$key]["pdt_sn"] = $array_tmp_info["pdt_sn"];
			$array_detail_list[$key]["g_sn"] = $array_tmp_info["g_sn"];
			if(!isset($array_goods_info[$array_tmp_info["g_id"]])){
				$array_goods_info[$array_tmp_info["g_id"]] = D("GoodsInfo")->where(array("g_id"=>$array_tmp_info["g_id"]))->getField("g_name");
			}
			$array_detail_list[$key]["g_name"] = $array_goods_info[$array_tmp_info["g_id"]];
		}
		$this->assign("receipt_info",$array_receipt_info);
		$this->assign("array_products",$array_detail_list);
		$this->display();
	}
	
	/**
	 * 处理提交的库存调整单信息 - 编辑
	 */
	public function doEdit(){
    
		//验证是否制定要修改的库存调整单ID
		if(!isset($_POST["srr_id"]) || !is_numeric($_POST["srr_id"])){
			$this->error("参数错误：无法找到您要修改的库存调整单。");
			exit;
		}
		
		//获取库存调整单详细信息
		$int_srr_id = $_POST["srr_id"];
		$array_condition = array("srr_id"=>$int_srr_id);
		$array_receipt_info = D("StockReviseReceipt")->where($array_condition)->find();
		if(!is_array($array_receipt_info) || empty($array_receipt_info)){
			$this->error("对不起，系统无法找到您要修改的库存调整单。");
			exit;
		}
		//验证库存调整单，如果库存调整单已经被审核或者已作废，则不允许修改
		if(0 != $array_receipt_info['srr_verify']){
			$string_message = "该库存调整单已经被审核，不允许修改。";
			switch($array_receipt_info['srr_verify']){
				case 1:
					$string_message = "该库存调整单已经被审核通过，不允许修改。";
					break;
				case 2:
					$string_message = "该库存调整单已经被作废，不允许修改。";
					break;
			}
			$this->error($string_message);
			exit;
		}
		
		//保存库存调整单基本信息，主要是描述和更新时间
		D("StockReviseReceipt")->startTrans();
		$array_modify = array();
		$array_modify["srr_desc"] = $_POST["desc"];
		$array_modify["srr_update_time"] = date("Y-m-d H:i:s");
		$array_modify["srr_update_time"] = date("Y-m-d H:i:s");
		if(false === D("StockReviseReceipt")->where($array_condition)->save($array_modify)){
			$this->error("更新库存调整单基本信息遇到错误。");
			exit;
		}
		
		/**
		 * 实现思路如下：
		 * 获取此库存调整单的明细，对其中的明细进行处理
		 * 这里的实现思路时，获取当前库存调整单的明细数据，与页面提交过来的数据进行比对
		 * 如果货号在提交过来的数据中存在，则认为是修改操作；如果不存在，则认为是删除明细操作；
		 * 对库存调整单的明细处理完成以后，将货号保存在一个数组中，完成以后对post数据进行再处理
		 * 如果货号已被处理，则跳过，否则对明细执行新增入库操作。
		 */
		$array_exists_pdtsns = array();
		$array_detail_list = D("StockReviseReceiptDetail")->where($array_condition)->getField('srrd_id,pdt_id,srrd_type,srrd_num,srrd_status');
        $array_pdt_sn = $_POST["detail"]["pdt_sn"];
		$array_type = $_POST["detail"]["srrd_type"];
		$array_num = $_POST["detail"]["srrd_num"];
		foreach($array_detail_list as $key => $val){
			$array_modify_cond = array("srrd_id"=>$val["srrd_id"]);
			$string_pdt_sn = D("GoodsProductsTable")->where(array("pdt_id"=>$val["pdt_id"]))->getField("pdt_sn");
			$array_exists_pdtsns[] = $string_pdt_sn;
			if(in_array($string_pdt_sn,$array_pdt_sn)){
				//如果当前的货号在用户提交的货号中存在，则认为是对已存在明细的修改操作
				//获取当前的货号在POST提交数组之中的位置
				$ini_pdtsn_key_id = 0;
				foreach($array_pdt_sn as $product_sn_key => $product_sn){
					if($string_pdt_sn == $product_sn){
						$ini_pdtsn_key_id = $product_sn_key;
					}
				}
				//保存明细数据入库
				$array_modify_detail = array();
				if(in_array($array_type[$ini_pdtsn_key_id],array(0,1))){
					$array_modify_detail["srrd_type"] = $array_type[$ini_pdtsn_key_id];
				}
				if(is_numeric($array_num[$ini_pdtsn_key_id])){
					$array_modify_detail["srrd_num"] = $array_num[$ini_pdtsn_key_id];
				}
				if(empty($array_modify_detail)){
					//此中情况是由于货号对应的调整类型和调整数量均不合法，所以自动跳过
					
					continue;
				}
				//生成明细操作的操作日志记录
				$array_modify_detail["srrd_update_time"] = date("Y-m-d H:i:s");
				if(false === D("StockReviseReceiptDetail")->where($array_modify_cond)->save($array_modify_detail)){
					D("StockReviseReceiptDetail")->rollback();
					$this->error("库存调整单明细保存失败。CODE:DETAIL-MODIFY-001;");
					exit;
				}
				//记录库存调整单更新日志，操作类型为明细操作，且是修改明细
				$array_log_info = array();
				$array_log_info["srr_id"] = $int_srr_id;
				$array_log_info["srrml_type"] = 4;
				$array_log_info["srrml_detail_type"] = 1;
				$array_log_info["srrd_id"] = $val["srrd_id"];
				$array_log_info["u_id"] = $_SESSION["Admin"];
				$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
				if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
					D("StockReviseReceiptModifyLog")->rollback();
					$this->error("记录库存调整单明细操作失败。CODE:DETAIL-MODIFY-LOG-001;");
					exit;
				}
				continue;
			}
			//如果不存在，则认为是删除一条明细记录，则删除明细记录
			if(false === D("StockReviseReceiptDetail")->where($array_modify_cond)->delete()){
				D("StockReviseReceiptModifyLog")->rollback();
				$this->error("记录库存调整单明细操作失败。CODE:DETAIL-DELETE-001;");
				exit;
			}
			//记录删除库存调整单明细的日志，明细操作，删除明细
			$array_log_info = array();
			$array_log_info["srr_id"] = $int_srr_id;
			$array_log_info["srrml_type"] = 4;
			$array_log_info["srrml_detail_type"] = 2;
			$array_log_info["srrd_id"] = $val["srrd_id"];
			$array_log_info["u_id"] = $_SESSION["Admin"];
			$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
			if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
				D("StockReviseReceiptModifyLog")->rollback();
				$this->error("记录库存调整单明细操作失败。CODE:DETAIL-MODIFY-LOG-002;");
				exit;
			}
			continue;
		}
		//对库存调整单中不存在的明细进行处理，执行新增明细入库操作，并记录操作日志。
		foreach($array_pdt_sn as $key => $val){
			if(in_array($val,$array_exists_pdtsns)){
				continue;
			}
			//新增库存调整单明细入库，并记录操作日志
			$array_insert_data = array();
			$array_insert_data["srr_id"] = $int_srr_id;

			$array_insert_data["pdt_id"] = D("GoodsProductsTable")->where(array("pdt_sn"=>$val,"pdt_is_combination_goods"=>0))->getField("pdt_id");
			if(!$array_insert_data["pdt_id"] || !is_numeric($array_insert_data["pdt_id"])){
				$this->error("至少有一件货品不存在。CODE:DETAIL-ADD-003;");
				exit;
			}
			if(in_array($array_type[$key],array(1,2)) || !is_numeric($array_num[$key])){
				continue;
			}
			$array_insert_data["srrd_type"] = $array_type[$key];
			$array_insert_data["srrd_num"] = $array_num[$key];
			$array_insert_data["srrd_status"] = 1;
			$array_insert_data["srrd_create_time"] = date("Y-m-d H:i:s");
			$array_insert_data["srrd_update_time"] = date("Y-m-d H:i:s");	
			$int_srrd_id = D("StockReviseReceiptDetail")->add($array_insert_data);
			if(false === $int_srrd_id){
				D("StockReviseReceiptDetail")->rollback();
				$this->error("记录库存调整单明细操作失败。CODE:DETAIL-ADD-001;");
				exit;
			}
			
			//记录结余款调整单明细操作日志
			$array_log_info = array();
			$array_log_info["srr_id"] = $int_srr_id;
			$array_log_info["srrml_type"] = 4;
			$array_log_info["srrml_detail_type"] = 0;
			$array_log_info["srrd_id"] = $int_srrd_id;
			$array_log_info["u_id"] = $_SESSION["Admin"];
			$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
			if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
				D("StockReviseReceiptModifyLog")->rollback();
				$this->error("记录库存调整单明细操作失败。CODE:DETAIL-MODIFY-LOG-002;");
				exit;
			}
		}
		//提交事务，提示用户操作成功。
		D("StockReviseReceiptModifyLog")->commit();
		$this->success("库存调整单修改成功。",U("Admin/Stock/pageList"));
		exit;
	}
	
	public function pageDetail(){
		$this->getSubNav(3, 0, 50);
		if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
			$this->error("参数错误：无法找到库存调整单。");
			exit;
		}
		//获取库存调整单，及其明细
		$array_condition = array("srr_id"=>$_GET["id"]);
		$array_receipt_info = D("StockReviseReceipt")->where($array_condition)->find();
		if(!is_array($array_receipt_info) || empty($array_receipt_info)){
			$this->error("对不起，库存调整单不存在。");
			exit;
		}
		
		//获取库存调整单详情
		$array_detail_list = D("StockReviseReceiptDetail")->where($array_condition)->select();
		$array_goods_info = array();
		foreach($array_detail_list as $key => $val){
			$array_tmp_info = D("GoodsProductsTable")->where(array("pdt_id"=>$val["pdt_id"]))->getField("pdt_id,pdt_sn,g_id,g_sn");
			$array_tmp_info = $array_tmp_info[$val["pdt_id"]];
			$array_detail_list[$key]["pdt_sn"] = $array_tmp_info["pdt_sn"];
			$array_detail_list[$key]["g_sn"] = $array_tmp_info["g_sn"];
			if(!isset($array_goods_info[$array_tmp_info["g_id"]])){
				$array_goods_info[$array_tmp_info["g_id"]] = D("GoodsInfo")->where(array("g_id"=>$array_tmp_info["g_id"]))->getField("g_name");
			}
			$array_detail_list[$key]["g_name"] = $array_goods_info[$array_tmp_info["g_id"]];
		}
		$array_receipt_info["srr_create_name"] = D("Admin")->where(array("u_id"=>$array_receipt_info["srr_create_id"]))->getField("u_name");
		$array_receipt_info["srr_verify_name"] = "";
		if($array_receipt_info["srr_verify_id"] > 0){
			$array_receipt_info["srr_verify_name"] = D("Admin")->where(array("u_id"=>$array_receipt_info["srr_verify_id"]))->getField("u_name");
		}
		$this->assign("receipt_info",$array_receipt_info);
		$this->assign("array_products",$array_detail_list);
		$this->display();
	}
	
	/**
	 * 库存调整单作废
	 */
	public function pageInvalid(){
		//验证是否制定要修改的库存调整单ID
		if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
			$this->error("参数错误：无法找到您要修改的库存调整单。");
			exit;
		}
		
		//获取库存调整单详细信息
		$int_srr_id = $_GET["id"];
		$array_condition = array("srr_id"=>$int_srr_id);
		$array_receipt_info = D("StockReviseReceipt")->where($array_condition)->find();
		if(!is_array($array_receipt_info) || empty($array_receipt_info)){
			$this->error("对不起，系统无法找到您要修改的库存调整单。");
			exit;
		}
		//验证库存调整单，如果库存调整单已经被审核或者已作废，则不允许修改
		if(0 != $array_receipt_info['srr_verify']){
			$string_message = "该库存调整单已经被审核，不允许修改。";
			switch($array_receipt_info['srr_verify']){
				case 1:
					$string_message = "该库存调整单已经被审核通过，不允许作废。";
					break;
				case 2:
					$string_message = "该库存调整单已经被作废，不允许作废。";
					break;
			}
			$this->error($string_message);
			exit;
		}
		
		//将此库存调整单状态修改为作废
		D("StockReviseReceipt")->startTrans();
		if(false === D("StockReviseReceipt")->where($array_condition)->save(array("srr_verify"=>2))){
			D("StockReviseReceipt")->rollback();
			$this->error("库存调整单作废失败。");
			exit;
		}
		//产生一条作废库存调整单的日志
		//记录结余款调整单明细操作日志
		$array_log_info = array();
		$array_log_info["srr_id"] = $int_srr_id;
		$array_log_info["srrml_type"] = 3;
		$array_log_info["srrml_detail_type"] = 0;
		$array_log_info["srrd_id"] = 0;
		$array_log_info["u_id"] = $_SESSION["Admin"];
		$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
		if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
			D("StockReviseReceiptModifyLog")->rollback();
			$this->error("记录库存调整单明细操作失败。CODE:DETAIL-MODIFY-LOG-002;");
			exit;
		}
		D("StockReviseReceiptModifyLog")->commit();
		$this->success("操作已成功。",U("Admin/Stock/pageList"));
		exit;
	}
	
	/**
	 * 库存调整单审核通过
	 */
	public function pageVerify(){
		//验证是否制定要修改的库存调整单ID
		if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
			$this->error("参数错误：无法找到您要修改的库存调整单。");
			exit;
		}
		
		//获取库存调整单详细信息
		$int_srr_id = $_GET["id"];
		$array_condition = array("srr_id"=>$int_srr_id);
		$array_receipt_info = D("StockReviseReceipt")->where($array_condition)->find();
		if(!is_array($array_receipt_info) || empty($array_receipt_info)){
			$this->error("对不起，系统无法找到您要修改的库存调整单。");
			exit;
		}
		//验证库存调整单，如果库存调整单已经被审核或者已作废，则不允许修改
		if(0 != $array_receipt_info['srr_verify']){
			$string_message = "该库存调整单已经被审核，不允许修改。";
			switch($array_receipt_info['srr_verify']){
				case 1:
					$string_message = "该库存调整单已经被审核通过，不允许审核。";
					break;
				case 2:
					$string_message = "该库存调整单已经被作废，不允许作废。";
					break;
			}
			$this->error($string_message);
			exit;
		}
		
		//获取库存调整单详细信息
		$array_detail_list = D("StockReviseReceiptDetail")->where($array_condition)->select();
		
		//将此库存调整单状态修改为作废
		D("StockReviseReceipt")->startTrans();
		
		$array_pdt_ids = array();
		
		foreach($array_detail_list as $detail){
			if(1 != $detail["srrd_status"]){
				continue;
			}
			//将库存调整单中此单据中的明细更新到商品资料中
			//更新库存是将库存更新到总库存和可用库存，冻结数不变
			$function_name = 'setInc';
			if(1 == $detail["srrd_type"]){
				$function_name = 'setDec';
			}
			//首先更新总库存数
			if(false === D("GoodsProductsTable")->where(array("pdt_id"=>$detail["pdt_id"]))->$function_name("pdt_total_stock",$detail["srrd_num"])){
				D("StockReviseReceipt")->callback();
				$this->error("库存调整单审核失败。CODE:VERIFY-ERROR-001;");
				exit;
			}
			//接下来更新可下单库存数
			if(false === D("GoodsProductsTable")->where(array("pdt_id"=>$detail["pdt_id"]))->$function_name("pdt_stock",$detail["srrd_num"])){
				D("StockReviseReceipt")->callback();
				$this->error("库存调整单审核失败。CODE:VERIFY-DETAIL-ERROR-002;");
				exit;
			}
			
			$array_pdt_ids[] = $detail["pdt_id"];
			
			//产生一张库存调整单明细操作日志：明细操作，并且操作为审核通过
			$array_log_info = array();
			$array_log_info["srr_id"] = $int_srr_id;
			$array_log_info["srrml_type"] = 4;
			$array_log_info["srrml_detail_type"] = 3;
			$array_log_info["srrd_id"] = $detail["srrd_id"];
			$array_log_info["u_id"] = $_SESSION["Admin"];
			$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
			if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
				D("StockReviseReceiptModifyLog")->rollback();
				$this->error("记录库存调整单明细操作失败。CODE:VERIFY-DETAIL-ERROR-LOG-002;");
				exit;
			}
		}
		
		//库存调整单明细全部审核完成，增加一步更新商品表总库存数量的操作
		$array_update_gids = D("GoodsProductsTable")->where(array("pdt_id"=>array("IN",$array_pdt_ids)))->getField("g_id",true);
		foreach($array_update_gids as $int_goods_id){
			$array_fetch_condition = array("g_id"=>$int_goods_id,"pdt_satus"=>1);
			$string_fields = "g_id,sum(`pdt_stock`) as `g_stock`";
			$array_result = D("GoodsProductsTable")->where($array_fetch_condition)->getField($string_fields);
			if(false === $array_result){
				D("GoodsProductsTable")->rollback();
				$this->error("库存调整单审核失败。");
				exit;
			}
			$array_save_data = array();
			$array_save_data["g_stock"] = $array_result[$int_goods_id];
			$result = D("GoodsInfo")->where(array("g_id"=>$int_goods_id))->save($array_save_data);
			if(false === $result){
				D("GoodsInfo")->rollback();
				$this->error("库存调整单审核失败。");
				exit;
			}
		}
		
		//产生一张库存调整单审核操作日志。
		$array_log_info = array();
		$array_log_info["srr_id"] = $int_srr_id;
		$array_log_info["srrml_type"] = 2;
		$array_log_info["srrml_detail_type"] = 0;
		$array_log_info["srrd_id"] = 0;
		$array_log_info["u_id"] = $_SESSION["Admin"];
		$array_log_info["srrml_create_time"] = date("Y-m-d H:i:s");
		if(false === D("StockReviseReceiptModifyLog")->add($array_log_info)){
			D("StockReviseReceiptModifyLog")->rollback();
			$this->error("记录库存调整单审核失败。CODE:VERIFY-ERROR-003;");
			exit;
		}
		//将当前库存调整单标记为已审核
		$array_modify = array("srr_verify"=>1,"srr_verify_time"=>date("Y-m-d H:i:s"),'srr_verify_id'=>$_SESSION["Admin"]);
		if(false === D("StockReviseReceipt")->where($array_condition)->save($array_modify)){
			D("StockReviseReceipt")->rollback();
			$this->error("将库存调整单标记为已审核时遇到错误。CODE:VERIFY-ERROR-004;");
			exit;
		}
		
		//事务提交，提示用户操作成功
		D("StockReviseReceiptModifyLog")->commit();
		$this->success("库存调整单审核成功。",U("Admin/Stock/pageList"));
		exit;
	}
}
