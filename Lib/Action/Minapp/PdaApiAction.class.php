<?php
// ************************************PDA接口*************************************************
// ********************************************************************************************
// ********************************************************************************************

class PdaApiAction extends GyfxAction {
	public function UserLogin() {
		///定义一个数组来储存数据
        $result   = array();

        $member     = D("Members");
        //$_POST['m_name'] = '6666';
        //$_POST['m_password'] = '123456';
        $m_name     = $this->_request("m_name");
        $m_password = md5($this->_request("m_password"));

        if(empty($m_name)){
            $result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
			die;
        }else{
            $member = M('members',C('DB_PREFIX'),'DB_CUSTOM')->field('m_id')->where(array("m_name"=>$m_name,"role"=>3))->find();
			if($member){
				$validmember = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_name"=>$m_name,"m_password"=>$m_password))->find();
				if($validmember){
					$result["info"] = "登录成功";
					$result["status"] = "10000";
					$result["m_id"] = $member["m_id"];
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]     = "密码错误";
					$result["status"]   = "10003";
					print_r(json_encode($result));
					die;
				}
				
			}else{

				$result["info"] = "帐号不存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;	
			}
        }
	}

	public function deliverybillList(){
		///定义一个数组来储存数据
        $result   = array();

		$m_id = $this->_request("m_id");
		if (empty($m_id)) {
			$result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
			die;
		}

        $sql = "select id,o_sn,d_sn,o_status,d_status,supplySkuNums from fx_in_goods_supplier_orders where d_status=0 and d_sn is not null";
        $billList = M('')->query($sql);   
        $last = M('')->getLastSql(); 
        $result["billList"] = $billList;     
        if(empty($billList)){
            $result["info"]   = "暂无数据";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }else{
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["billList"] = $billList;
            print_r(json_encode($result));
            die;
        }        
    }

    public function getReceiptSku(){
        $bar_code = trim($_REQUEST['bar_code']);
        $o_id = $_REQUEST['o_id'];
        if (empty($bar_code) || empty($o_id)) {
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $M = M('');
        $sql = 'select g_name,g_picture,g_length,g_width,g_height from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code=(select bar_code from fx_in_goods_supplier_orders_item where o_id='.$o_id.' and bar_code="'.$bar_code.'"))';
        $rs = $M->query($sql);
        if(empty($rs)){
            $result["info"]   = "暂无商品";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;
        }else{

            $sql = 'select * from fx_in_goods_supplier_orders_item where bar_code='.$bar_code.' and o_id='.$o_id;
            $rs2 = $M->query($sql);
            $rs[0]['nums'] = $rs2[0]['nums'];
            $rs[0]['supply_nums'] = $rs2[0]['supply_nums'];
            $rs[0]['receipt_nums'] = $rs2[0]['receipt_nums'];
            $rs[0]['g_picture'] = 'http://www.caizhuangguoji.com'.$rs[0]['g_picture'];

            $sql = 'select batch,nums from fx_in_goods_supplier_orders_item_batch where oi_id='.$rs2[0]['id'];
            $rs[0]['batchList'] = $M->query($sql);

            $sql = "select nums from fx_in_Tbox_item where bar_code='".$bar_code."' and T_id in (select id from fx_in_Tbox where o_id=$o_id)";
            $goodslist = $M->query($sql);
            foreach ($goodslist as $key => $value) {
                $boxTotalNums += $value["nums"];
            }

            $rs[0]['boxTotalNums'] = $boxTotalNums;


            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["rs"] = $rs[0];
            print_r(json_encode($result));
            die;
        }
    }

    public function ConfirmSupplyOrderItem(){
    	//清点接口
        $result = array();
        $o_id = $_REQUEST['o_id'];
        $bar_code = trim($_REQUEST['bar_code']);
        $nums = intval($_REQUEST['nums']);
        $Tbox_sn = $_REQUEST['Tbox_sn'];

        if (empty($o_id) || empty($bar_code) || empty($Tbox_sn)) {
        	$result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $sql = "select id from fx_in_Tbox where Tbox_sn='".$Tbox_sn."'";
        $tboxlist = M('')->query($sql);
        if (empty($tboxlist)) {
            $result["info"]   = "无此周转箱";
            $result["status"] = "10004";
            print_r(json_encode($result));
            die;      
        }
        $T_id = $tboxlist[0]["id"];

        $updateOrders = M('')->query("update fx_in_goods_supplier_orders set r_status=1 where id=$o_id");

        $sql = "update fx_in_Tbox set status=1,o_id=$o_id where Tbox_sn='".$Tbox_sn."'";
        $regs2 = M('')->query($sql);

        $time = time();
        $sql = "select id from fx_in_Tbox_item where T_id=$T_id and bar_code='".$bar_code."'";
        $regs3 = M('')->query($sql);
        if (empty($regs3)) {
            $sql = "insert into fx_in_Tbox_item (T_id,bar_code,nums,scan_time) values ($T_id,'".$bar_code."',$nums,$time)";
            $regs4 = M('')->query($sql);
        }else {
            $sql = "update fx_in_Tbox_item set nums=nums+$nums,scan_time=$time where T_id=$T_id and bar_code='".$bar_code."'";
            $regs4 = M('')->query($sql);
        }
        $result["regs4"] = $regs4;

        if (is_array($regs4)) {
        	$result["info"]   = "请求成功";
        	$result["status"] = "10000";
        	print_r(json_encode($result));
        	die;
        }else {
        	$result["info"]   = "请求失败";
        	$result["status"] = "10003";
        	print_r(json_encode($result));
        	die;
        }

    }

    public function GetTboxList() {
        //周转箱接口
        $result = array();
        $time = time();
        $sql = "select id, Tbox_sn from fx_in_Tbox where status=0";
        $tboxlist = M('')->query($sql);
        if (empty($tboxlist)) {
            $result["info"]   = "无空余周转箱可用";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }else {
            $result["info"]   = "请求成功";
            $result["status"] = "10000";
            $result["tboxlist"] = $tboxlist;
            print_r(json_encode($result));
            die;
        }
    }

    public function ValidTboxIsEnabled() {
        //周转箱接口
        $result = array();
        $Tbox_sn = $_REQUEST['Tbox_sn'];
        $o_id = $_REQUEST['o_id'];

        if (empty($Tbox_sn)) {
            $result["info"]   = "参数错误";
            $result["status"] = "10003";
            print_r(json_encode($result));
            die;
        }
        $sql = "select status,o_id from fx_in_Tbox where Tbox_sn='".$Tbox_sn."'";
        $tboxlist = M('')->query($sql);
        if (empty($tboxlist)) {
            $result["info"]   = "无此周转箱";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }else {
            $tbox = $tboxlist[0];
            if ($tbox["status"] == 0 || $tbox["o_id"] == $o_id) {
                $result["info"]   = "周转箱可用";
                $result["status"] = "10000";
                print_r(json_encode($result));
                die;   
            }else {
                $result["info"]   = "周转箱不可用";
                $result["status"] = "10002";
                print_r(json_encode($result));
                die; 
            }
        }
    }

    public function GetReceiptItem() {
        $result = array();
        $o_id = $_REQUEST['o_id'];
        $page = (intval($_REQUEST['page']) < 1)?1:intval($_REQUEST['page']);
        $pageSize = 100;
        $offset = ($page-1)*$pageSize;
        if (empty($o_id)) {
            $result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die; 
        }

        $items = M('')->query("select bar_code,nums,scan_time,Tbox_sn from fx_in_Tbox_item left join fx_in_Tbox on fx_in_Tbox_item.T_id=fx_in_Tbox.id where fx_in_Tbox.o_id=$o_id ORDER BY scan_time desc limit $offset,$pageSize");
        if (empty($items)) {
            $result["info"] = "暂无商品信息";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die; 
        }

        foreach ($items as $key => $item) {
            $goodslist = M('')->query("select g_name from fx_goods_info,fx_goods_products where fx_goods_info.g_id=fx_goods_products.g_id and fx_goods_products.pdt_bar_code='".$item["bar_code"]."'");
            $items[$key]["g_name"] = $goodslist[0]["g_name"];

            $orderItems = M('')->query("select supply_nums from fx_in_goods_supplier_orders_item where bar_code='".$item["bar_code"]."' and o_id=$o_id");
            $items[$key]["supply_nums"] = $orderItems[0]["supply_nums"];
        }

        $singleItems = M('')->query("select Tbox_sn from fx_in_Tbox_item left join fx_in_Tbox on fx_in_Tbox_item.T_id=fx_in_Tbox.id where fx_in_Tbox.o_id=$o_id ORDER BY scan_time desc limit 0,1");
    
        $result["defaultBox"] = $singleItems[0]["Tbox_sn"];

        $result["info"] = "请求成功";
        $result["status"] = "10000";
        $result["items"] = $items;
        print_r(json_encode($result));
        die; 
    }

    public function receiptInit() {
        $result = array();
        $o_id = $_REQUEST['o_id'];
        if (empty($o_id)) {
            $result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $tboxlist = M('')->query("select id from fx_in_Tbox where o_id=$o_id");
        if (empty($tboxlist)) {
            $result["info"] = "已初始化过，无需重复操作";
            $result["status"] = "10003";
            print_r(json_encode($result));
            die;
        }

        $regs1 = M('')->query("delete from fx_in_Tbox_item where T_id in (select id from fx_in_Tbox where o_id=$o_id)");
        if (is_array($regs1)) {
            $regs2 = M('')->query("update fx_in_Tbox set status=0,o_id=NULL where o_id=$o_id");
            if (is_array($regs2)) {
                $result["info"] = "初始化成功";
                $result["status"] = "10000";
                print_r(json_encode($result));
                die;
            }else {
                $result["info"] = "初始化失败";
                $result["status"] = "10002";
                print_r(json_encode($result));
                die;
            }
            
        }else {
            $result["info"] = "初始化失败";
            $result["status"] = "10004";
            print_r(json_encode($result));
            die; 
        }
    }

    public function GetGoodsLWH(){
        $bar_code = trim($_REQUEST['bar_code']);
        if(empty($bar_code)){
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $sql = 'select g_length,g_width,g_height from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code="'.$bar_code.'")';
        $goodslist = M('')->query($sql);
        if(empty($goodslist)){
            $result["info"]   = "查询不到该商品信息";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;
        }else{
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["attributes"] = $goodslist[0];
            print_r(json_encode($result));
            die;
        }
    }

    public function UpdateGoodsLWH(){
        $bar_code = trim($_REQUEST['bar_code']);
        $g_length = intval($_REQUEST['g_length']);
        $g_width = intval($_REQUEST['g_width']);
        $g_height = intval($_REQUEST['g_height']);
        if(empty($bar_code) || empty($g_length) || empty($g_width)|| empty($g_height)){
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $sql = 'select g_id from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code="'.$bar_code.'")';
        $goodslist = M('')->query($sql);
        if(empty($goodslist)){
            $result["info"]   = "查询不到该商品信息";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;
        }
        $g_id = $goodslist[0]["g_id"];

        $sql = "update fx_goods_info set g_length=$g_length,g_width=$g_width,g_height=$g_height where g_id=$g_id";
        $regs = M('')->query($sql);
        if (is_array($regs)) {
            $result["info"]   = "修改成功";
            $result["status"] = "10000";
            print_r(json_encode($result));
            die;
        }else {
            $result["info"]   = "修改失败";
            $result["status"] = "10003";
            print_r(json_encode($result));
            die;
        }
    }

    public function receiptFinish() {
        $result = array();
        $o_id = $_REQUEST["o_id"];
        if (empty($o_id)) {
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }
        $boxItems = M('')->query("select DISTINCT bar_code from fx_in_Tbox_item where T_id in (select id from fx_in_Tbox where o_id=$o_id)");

        if ($boxItems) {
            $receiptSkuMoney;
            $receiptSkuNums;
            foreach ($boxItems as $boxItemKey => $boxItem) {
                $bar_code = $boxItem["bar_code"];
                $updateOrderItem = M('')->query("update fx_in_goods_supplier_orders_item set receipt_nums=(select sum(nums) from fx_in_Tbox_item where bar_code='".$bar_code."' and T_id in (select id from fx_in_Tbox where o_id=$o_id)) where o_id=$o_id and bar_code='".$bar_code."'");

                $orderItem = M('')->query("select price_cn,receipt_nums from fx_in_goods_supplier_orders_item where o_id=$o_id and bar_code='".$bar_code."'");
                $receiptSkuMoney += $orderItem[0]["receipt_nums"] * $orderItem[0]["price_cn"];
                $receiptSkuNums += $orderItem[0]["receipt_nums"];
            }
            $r_sn = 'R' . date('mdHi') . rand(10, 99);
            $s_sn = 'S' . date('mdHi') . rand(10, 99);
            $updateOrders = M('')->query("update fx_in_goods_supplier_orders set r_sn='".$r_sn."',s_sn='".$s_sn."',receiptSkuMoney=$receiptSkuMoney,receiptSkuNums=$receiptSkuNums, o_status=1,r_status=2 where id=$o_id");
            if (is_array($updateOrders)) {
                $result["info"]   = "提交成功";
                $result["status"] = "10000";
                print_r(json_encode($result));
                die;
            }else {
                $result["info"]   = "提交失败";
                $result["status"] = "10003";
                print_r(json_encode($result));
                die;
            }
        }else {
            $result["info"]   = "提交失败";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;
        }
    }

    public function GetReceiptDiffNums() {
        $result = array();
        $o_id = $_REQUEST["o_id"];
        if (empty($o_id)) {
            $result["info"]   = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $boxItems = M('')->query("select sum(nums) from fx_in_Tbox_item where T_id in (select id from fx_in_Tbox where o_id=$o_id)");
        $orders = M('')->query("select supplySkuNums from fx_in_goods_supplier_orders where id=$o_id");
        $result["boxTotalNums"]   = $boxItems[0]["sum(nums)"];
        $result["supplySkuNums"]   = $orders[0]["supplySkuNums"];
        $result["diffNums"]   = $orders[0]["supplySkuNums"] - $boxItems[0]["sum(nums)"];
        $result["info"]   = "请求成功";
        $result["status"] = "10000";
        print_r(json_encode($result));
        die;
    }
    

    // public function PutReceiptOnShelf() {
    //     $sh_sn1;
    //     $sh_sn2;
    //     $result = array();
    //     $items = M('')->query("select sum(nums) as nums,bar_code from fx_in_Tbox_item where T_id in (select id from fx_in_Tbox where type=1) group by bar_code"); 
    //     foreach ($items as $key => $value) {
    //         $bar_code = $value["bar_code"];
    //         $leftPositions = M('')->query("select p_sn,remaining from fx_in_position where bar_code='".$bar_code."' and remaining>0");
    //         if($leftPositions[0]['remaining']){

    //             //$totalRemains = M('')->query("select sum(remaining) as totalRemaining from fx_in_position where bar_code='".$bar_code."' and remaining>0 group by bar_code");
    //             //$totalRemains[0]['']
    //         }
            
    //         $items[$key]["totalRemaining"] = $totalRemains[0]["totalRemaining"];
    //         $items[$key]["leftNums"] = $value["nums"] - $totalRemains[0]["totalRemaining"];
    //         $items[$key]["leftPositions"] = $leftPositions;
    //     }
    //     $result["items"] = $items;
    //     $result["info"]   = "请求成功";
    //     $result["status"] = "10000";
    //     print_r(json_encode($result));
    //     die;

    // }

}