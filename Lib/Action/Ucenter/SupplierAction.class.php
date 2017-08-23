<?php
class SupplierAction extends CommonAction {

    private function skin(){
        $str_header_include_file = FXINC . '/Tpl/Ucenter/Common/oHeader.html';

        $this->assign("srt_inc_footer",'');
        $this->assign("str_header_include_file",$str_header_include_file);        
    }
    public function PendingList(){
        $M = M('');
        $member = session("Members");

        $condition = 'status=0 and to_m_id='.$member['m_id'];
        
        $count = M('in_pending')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $List = M('in_pending')
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        $this->assign('List',$List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function GoodsList(){
        $M = M('');
        $member = session("Members");
        $condition = 'supplier_m_id='.$member['m_id'];
        
        $count = M('in_goods_supplier')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $List = M('in_goods_supplier')
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();
        $this->assign('List',$List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function addGood(){
        $this->skin();
        $this->display();
    }

    public function doAddGood(){
        $data = $_POST['goods_info'];
        $member = session("Members");
        $data['supplier_m_id'] = $member['m_id'];
        $data['supplier'] = $member['m_name'];
        $in_goods_supplier = M('in_goods_supplier','fx_');
        $int_goods_id = $in_goods_supplier->data($data)->add();
        if(false === $int_goods_id){
            $this->error("商品资料添加失败。CODE:FX-GOODS;");
        }
        else{
            $this->success("商品资料保存成功。",U("Ucenter/Supplier/GoodsList"));
        }
    }

    public function excelGood(){
        $this->skin();
        $this->display();
    }

    public function doExcelGoods(){
        header("Content-type: text/html;charset=utf-8");
        require_once FXINC . '/Lib/Common/' . 'PHPExcel/IOFactory.php';
        require_once FXINC . '/Lib/Common/' . 'PHPExcel.php';
        require_once FXINC . '/Lib/Common/' . 'Upfile.class.php';
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->saveRule  = date('YmdHis') ;// 设置附件上传大小
        $upload->allowExts  = array('xlsx','xls','csv');// 设置附件上传类型
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/'.date('Ymd').'/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $upload->savePath =  $filexcel;// 设置附件上传目录
        if(!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        $str_upload_file = $info[0]['savepath'].$info[0]['savename'];
        $objCalc = PHPExcel_Calculation::getInstance();
        //读取Excel客户模板
        $objPHPExcel = PHPExcel_IOFactory::load($str_upload_file);
        $obj_Writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //读取第一个工作表(编号从 0 开始)
        $sheet = $objPHPExcel->getSheet(0);
        //取到有多少条记录 
        $highestRow = $sheet->getHighestRow();
        $goods = array();
        $i = 0;
        $member = session("Members");
        for($row=3; $row <= $highestRow; $row++){
            if(empty(trim($objPHPExcel->getActiveSheet()->getCell('C' . $row)->getCalculatedValue()))){
                break;
            }
            $goods[$i]['language_country'] = trim($objPHPExcel->getActiveSheet()->getCell('B' . $row)->getCalculatedValue());
            $goods[$i]['name_cn'] = trim($objPHPExcel->getActiveSheet()->getCell('C' . $row)->getCalculatedValue());
            $goods[$i]['name_en'] = trim($objPHPExcel->getActiveSheet()->getCell('D' . $row)->getCalculatedValue());
            $goods[$i]['brand_country'] = trim($objPHPExcel->getActiveSheet()->getCell('E' . $row)->getCalculatedValue());
            $goods[$i]['origin'] = trim($objPHPExcel->getActiveSheet()->getCell('F' . $row)->getCalculatedValue());
            $goods[$i]['brand_ori'] = trim($objPHPExcel->getActiveSheet()->getCell('G' . $row)->getCalculatedValue());
            $goods[$i]['brand_en'] = trim($objPHPExcel->getActiveSheet()->getCell('H' . $row)->getCalculatedValue());
            $goods[$i]['brand_cn'] = trim($objPHPExcel->getActiveSheet()->getCell('I' . $row)->getCalculatedValue());
            $goods[$i]['unit'] = trim($objPHPExcel->getActiveSheet()->getCell('J' . $row)->getCalculatedValue());
            $goods[$i]['package'] = trim($objPHPExcel->getActiveSheet()->getCell('K' . $row)->getCalculatedValue());
            $goods[$i]['partused'] = trim($objPHPExcel->getActiveSheet()->getCell('L' . $row)->getCalculatedValue());
            $goods[$i]['goods_sn'] = trim($objPHPExcel->getActiveSheet()->getCell('M' . $row)->getCalculatedValue());
            $goods[$i]['bar_code'] = trim($objPHPExcel->getActiveSheet()->getCell('N' . $row)->getCalculatedValue());
            if(empty($goods[$i]['bar_code'])){
                $this->error("条形码不能为空");
                exit();
            }
            $goods[$i]['place'] = trim($objPHPExcel->getActiveSheet()->getCell('O' . $row)->getCalculatedValue());
            $goods[$i]['newRetail'] = strtoupper(trim($objPHPExcel->getActiveSheet()->getCell('P' . $row)->getCalculatedValue()));
            if($goods[$i]['newRetail']!='Y'&&$goods[$i]['newRetail']!='N'){
                $this->error("数据格式有误");
                exit();
            }
            $goods[$i]['currency'] = trim($objPHPExcel->getActiveSheet()->getCell('Q' . $row)->getCalculatedValue());
            $goods[$i]['supply_price'] = trim($objPHPExcel->getActiveSheet()->getCell('R' . $row)->getCalculatedValue());
            $goods[$i]['retail_price'] = trim($objPHPExcel->getActiveSheet()->getCell('S' . $row)->getCalculatedValue());
            $goods[$i]['discount'] = trim($objPHPExcel->getActiveSheet()->getCell('T' . $row)->getCalculatedValue());
            $goods[$i]['MOQ'] = trim($objPHPExcel->getActiveSheet()->getCell('U' . $row)->getCalculatedValue());
            $goods[$i]['all_stock'] = trim($objPHPExcel->getActiveSheet()->getCell('V' . $row)->getCalculatedValue());
            $goods[$i]['base_stock'] = trim($objPHPExcel->getActiveSheet()->getCell('W' . $row)->getCalculatedValue());
            $goods[$i]['website'] = trim($objPHPExcel->getActiveSheet()->getCell('X' . $row)->getCalculatedValue());
            $goods[$i]['others'] = trim($objPHPExcel->getActiveSheet()->getCell('Y' . $row)->getCalculatedValue());
            $goods[$i]['supplier'] = $member['m_name'];
            $goods[$i]['supplier_m_id'] = $member['m_id'];
            $i++;
        }
        //M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        $in_goods_supplier = M('in_goods_supplier','fx_');
        foreach ($goods as $good_key=>$good_val){
            $int_goods_id = $in_goods_supplier->add($good_val);
            //echo $in_goods_supplier->getLastSql();
            //echo '<br>';
            if(false === $int_goods_id){
                //M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
                $this->error("商品资料添加失败。CODE:FX-GOODS;");
            }
        }
        $this->success("商品资料保存成功。",U("/Ucenter/Supplier/GoodsList"));
    }

    public function orderBillList(){
        $this->BillList('o_sn');
    }

    public function orderList(){
        $this->detailList();
    }

    public function getBatchDialog(){
        //var_dump($_POST);
        //echo 'here';
        $this->assign("id", $_POST['id']);
        $this->display();
    }

    public function doBatch(){
        //var_dump($_POST);
        $M = M('');
        $val['oi_id'] = $_POST['id'];
        $sql = 'delete from fx_in_goods_supplier_orders_item_batch where oi_id='.$val['oi_id'];
        $rs = $M->query($sql);
        $in_goods_supplier_orders_item_batch = M('in_goods_supplier_orders_item_batch','fx_');
        
        $supply_nums = 0;
        for($i=0;$i<4;$i++){
            if($_POST['batch'][$i]&&$_POST['batch_nums'][$i]){
                $val['batch'] = $_POST['batch'][$i];
                $val['nums'] = $_POST['batch_nums'][$i];
                $supply_nums+=$_POST['batch_nums'][$i];
                $in_goods_supplier_orders_item_batch->add($val);
                //echo $in_goods_supplier_orders_item_batch->getLastSql();
                //echo '<br>';
            }
        }
        
        $sql = 'select o_id from fx_in_goods_supplier_orders_item where id='.$val['oi_id'];
        $rs = $M->query($sql);
        //var_dump($rs);
        //echo 'select o_id from fx_in_goods_supplier_orders_item where id='.$val['oi_id'];
        //echo '<br>';
        //echo U("/Ucenter/Supplier/orderList/o_id/".$rs[0]['o_id']);
        $sql = 'update fx_in_goods_supplier_orders_item set supply_nums='.$supply_nums.' where id='.$val['oi_id'];
        $M->query($sql);
        $this->success("保存成功。",U("/Ucenter/Supplier/orderList/o_id/".$rs[0]['o_id']));
    }

    public function deliveryBillList(){
        $this->BillList('d_sn');
    }

    public function deliveryList(){
        $this->detailList();    
    }


    public function receiptBillList(){
        $this->BillList('r_sn');
    }

    public function receiptList(){
        $this->detailList();    
    }

    public function settlementBillList(){
        $this->BillList('s_sn');
    }

    public function settlementList(){
        $this->detailList();    
    }

    private function BillList($sn){
        $M = M('');
        $member = session("Members");
        $condition = 'supplier_m_id='.$member['m_id'].' and '.$sn.' is not null';
        $name = trim($_GET['name']);
        if($name){
            $condition.= ' and id in (select o_id from fx_in_goods_supplier_orders_item where bar_code in (select bar_code from fx_in_goods_supplier where (language_country like "%'.$name.'%" or name_cn like "%'.$name.'%" or name_en like "%'.$name.'%" or bar_code="'.$name.'") and supplier_m_id='.$member['m_id'].'))';
        }

        $o_create_time_1 = trim($_GET['o_create_time_1']);
        $o_create_time_2 = trim($_GET['o_create_time_2']);
        if($o_create_time_1&&$o_create_time_2){
            $condition.= ' and o_create_time between "'.$o_create_time_1.'" and "'.$o_create_time_2.'"';
        }

        $o_reply_time_1 = trim($_GET['o_reply_time_1']);
        $o_reply_time_2 = trim($_GET['o_reply_time_2']);
        if($o_reply_time_1&&$o_reply_time_2){
            $condition.= ' and o_reply_time between "'.$o_reply_time_1.'" and "'.$o_reply_time_2.'"';
        }

        $o_status = trim($_GET['o_status']);
        if($o_status){
            $condition.= ' and o_status="'.$o_status.'"';
        }
       
        $count = M('in_goods_supplier_orders')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $List = M('in_goods_supplier_orders')
                    ->where($condition)->limit($limit['start'],$limit['end'])->select();

        $this->assign('List',$List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    private function detailList(){
        $o_id = trim($_GET['o_id']);
        $M = M('');
        $member = session("Members");
        $sql = 'select * from fx_in_goods_supplier_orders where supplier_m_id='.$member['m_id'].' and id='.$o_id;
        $rs = $M->query($sql);
        if($rs){
            $o_id = $rs[0]['id'];
            $where = 'gs.bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id='.$o_id.')';
            $condition = 'bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id='.$o_id.')';
            $count = M('in_goods_supplier')->where($condition)->count();
            $obj_page = new Page($count, 200);
            $page = $obj_page->show();
            $limit['start'] =$obj_page->firstRow;
            $limit['end'] =$obj_page->listRows;

            $M = M('');
            $fileds = 'gs.id,gs.in_id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
            $fileds.= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,gs.supply_price_cn,gs.retail_price_cn,';
            $fileds.= 'gs.discount_cn,gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.sale_price,gs.profit,gs.profit_base,gs.tmall_price,gs.jumei_price,gs.tmall_url,gs.jumei_url,gs.newRetail,gs.mayPurchase,gs.origin,gs.currency,gs.supply_price,gs.retail_price,gs.discount,gs.MOQ';
            //$fileds.= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url,';
            //$fileds.= 'gc.korean_direct,gc.HK_direct,gc.domestic_shipment,gc.profit_notsole,gc.profit_sole,gc.person_normal,gc.person_notnormal,gc.BC_normal,gc.BC_notnormal';
            //$sql = 'select '.$fileds.' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id left join fx_goods_category gc on gc.gc_id=gs.gc_id where '.$where.' limit '.$limit['start'].','.$limit['end'];
            $sql = 'select '.$fileds.' from fx_in_goods_supplier gs where '.$where.' limit '.$limit['start'].','.$limit['end'];

            $List = $M->query($sql);

            foreach($List as $key => $value) {
                $bar_code = $value['bar_code'];
                $sql = 'select g_picture from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code="'.$bar_code.'")';
                $rs = $M->query($sql);
                $List[$key]['g_picture'] = $rs[0]['g_picture'];

                $sql = 'select * from fx_in_goods_supplier_orders_item where o_id='.$o_id.' and bar_code="'.$bar_code.'"';
                $rs = $M->query($sql);
                $List[$key]['nums'] = $rs[0]['nums'];
                $List[$key]['supply_nums'] = $rs[0]['supply_nums'];
                $List[$key]['receipt_nums'] = $rs[0]['receipt_nums'];
                $List[$key]['item_id'] = $rs[0]['id'];
                $List[$key]['purchase_price_cn'] = $rs[0]['price_cn'];

                $sql = 'select * from fx_in_goods_supplier_orders_item_batch where oi_id='.$rs[0]['id'];
                $rs = $M->query($sql);
                if($rs){
                    //echo $sql.'<br>';
                    foreach ($rs as $key => $value) {
                        if($key==0){
                            $List[$key]['batch'] = $value['batch'];
                        }
                        else{
                            $List[$key]['batch'].='<br>'.$value['batch'];
                        }                    
                    }                    
                }
            }
            $this->assign('List',$List);
            $this->assign("page", $page);
            $this->assign("o_id", $o_id);
            $this->skin();
            $this->display(); 
        }        
    }
    public function makeDelivery(){
        $M = M('');
        $d_sn = 'D'.date('mdHi') . rand(10, 99);
        $o_reply_time = date("Y-m-d h:i:s");
        $o_status = 2;
        $id = $_GET['o_id'];

        $sql = 'select * from fx_in_goods_supplier_orders_item where o_id='.$id;
        $rs = $M->query($sql);

        $supplySkuNums = 0;
        $supplySkuMoney = 0;
        foreach ($rs as $key => $value) {
            $supplySkuNums+=$value['supply_nums'];
            $supplySkuMoney+=$value['supply_nums']*$value['price_cn'];
        }

        $sql = 'update fx_in_goods_supplier_orders set d_sn="'.$d_sn.'",o_reply_time="'.$o_reply_time.'",o_status="'.$o_status.'",supplySkuNums="'.$supplySkuNums.'",supplySkuMoney="'.$supplySkuMoney.'" where id='.$id;

        $M->query($sql);

        //$name = '送货单-'.$data['d_sn'];
        //$url = '/Ucenter/Supplier/orderList/o_id/'.$o_id;
        //$member = session("Members");
        //$from_m_id = $member['m_id'];
        //$to_m_id = $ary_data[0]['supplier_m_id'];
        //$status = 0;
        //$create_time = date("Y-m-d h:i:s");
        //$sql = "INSERT INTO `fx_in_pending` (`name`,`url`,`from_m_id`,`to_m_id`,`status`,`create_time`) VALUES ('".$name."','".$url."','".$from_m_id."','".$to_m_id."','".$status."','".$create_time."')";
        //$M->query($sql);

        $this->success("成功生成送货单。",U("Ucenter/Supplier/deliveryList/o_id/".$id));
    }


    public function goodsListlast(){
       	$count = D('GoodsProducts')->GetProductCount($condition='');//179
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $goodsList = D('GoodsProducts')->GetProductList($condition='',$ary_field='',$group=array('g_id' => 'desc' ),$limit);
        foreach($goodsList as $key => $value) {
            $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
            $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
            $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
            $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
            $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");
        }
        $this->assign('goodsList',$goodsList);
        $this->assign("page", $page);
        $this->display();
    }

    // public function orderList(){
    //     $count = D('GoodsProducts')->GetOrderProductCount($condition='');//179
    //     $obj_page = new Page($count, 10);
    //     $page = $obj_page->show();
    //     $limit['start'] =$obj_page->firstRow;
    //     $limit['end'] =$obj_page->listRows;
    //     $goodsList = D('GoodsProducts')->GetOrderProductList($condition='',$ary_field='',$group=array('oi_id' => 'desc' ),$limit);
    //     //echo D('GoodsProducts')->getLastSql();
    //     foreach($goodsList as $key => $value) {
    //         $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
    //         $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
    //         $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
    //         $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
    //         $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");
    //     }
    //     $this->assign('goodsList',$goodsList);
    //     $this->assign("page", $page);
    //     $this->display();
    // }

    public function purchasebillList(){
        $count = D('Orders')->GetPurchaseBillCount();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $billList = D('Orders')->GetPurchaseBillList($condition,$ary_field='',$order=array('opb_time' => 'desc' ),$limit);
        $this->assign('billList',$billList);
        $this->assign("page", $page);
        $this->display();
    }

    public function purchaseList(){
        if($_GET['opb_id']){
            $condition = 'opb_id="'.$_GET['opb_id'].'"';
        }
        else{
            $condition = 1;
        }
        $count = D('Orders')->GetPurchaseCount($condition);//179
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $goodsList = D('Orders')->GetPurchaseList($condition,$ary_field='',$order=array('op_id' => 'desc' ),$limit);
        //print_r($goodsList);
        //echo D('Orders')->getLastSql();
        foreach($goodsList as $key => $value) {
            $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
            $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
            $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
            $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
            $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");
        }
        $this->assign('goodsList',$goodsList);
        //print_r($goodsList);
        $this->assign("page", $page);
        $this->assign("opb_id", $_GET['opb_id']);
        $this->assign("billList", D('Orders')->GetPurchaseBill());
        if($_GET['print']!=1){
          $this->display();
        }
        else{
          $this->display('print_purchaseList');
        }
    }


    public function deliverybillListlast(){
        if($_GET['opb_id']){
            $condition['opb_id'] = $_GET['opb_id'];
        }
        $count = D('Orders')->GetDeliveryBillCount($condition);
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $billList = D('Orders')->GetDeliveryBillList($condition,$ary_field='',$group=array('odb_id' => 'desc' ),$limit);
        $this->assign('billList',$billList);
        $this->assign("page", $page);
        $this->display();
    }

    public function deliveryListlast(){
        if(!empty($_GET['odb_id'])){
            $delivery_bill = D('Orders')->GetDeliveryOne($_GET['odb_id']);
            //print_r($delivery_bill);
            $condition = 'odb_id'.$delivery_bill['odb_level'].'='.$_GET['odb_id'];
        }
        //echo $condition;
        $count = D('Orders')->GetPurchaseCount($condition);//179
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $goodsList = D('Orders')->GetPurchaseList($condition,$ary_field='',$group=array('op_id' => 'desc' ),$limit);
        //print_r($goodsList);
        //echo D('Orders')->getLastSql();
        foreach($goodsList as $key => $value) {
            $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
            $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
            $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
            $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
            $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");

            if($delivery_bill['odb_level']==1){
                $goodsList[$key]['finish_nums']=0;
                $goodsList[$key]['left_nums']=$goodsList[$key]['odb_nums1'];
            }
            elseif($delivery_bill['odb_level']==2){
                $goodsList[$key]['finish_nums']=$goodsList[$key]['odb_nums1'];
                $goodsList[$key]['left_nums']=$goodsList[$key]['odb_nums2'];
            }
            elseif($delivery_bill['odb_level']==3){
                $goodsList[$key]['finish_nums']=$goodsList[$key]['odb_nums1']+$goodsList[$key]['odb_nums2'];
                $goodsList[$key]['left_nums']=$goodsList[$key]['odb_nums3'];
            }
            elseif($delivery_bill['odb_level']==4){
                $goodsList[$key]['finish_nums']=$goodsList[$key]['odb_nums1']+$goodsList[$key]['odb_nums2']+$goodsList[$key]['odb_nums3'];
                $goodsList[$key]['left_nums']=$goodsList[$key]['odb_nums4'];
            }
            elseif($delivery_bill['odb_level']==5){
                $goodsList[$key]['finish_nums']=$goodsList[$key]['odb_nums1']+$goodsList[$key]['odb_nums2']+$goodsList[$key]['odb_nums3']+$goodsList[$key]['odb_nums4'];
                $goodsList[$key]['left_nums']=$goodsList[$key]['odb_nums5'];
            }
            $goodsList[$key]['difference'] = $goodsList[$key]['op_nums']-($goodsList[$key]['odb_nums1']+$goodsList[$key]['odb_nums2']+$goodsList[$key]['odb_nums3']+$goodsList[$key]['odb_nums4']+$goodsList[$key]['odb_nums5']);
        }
        $this->assign('goodsList',$goodsList);
        //print_r($goodsList);
        $this->assign("page", $page);
        $this->assign("odb_id", $_GET['odb_id']);
        $this->assign("odb_level", $delivery_bill['odb_level']);
        $this->assign("odb_status", $delivery_bill['odb_status']);
        //echo $delivery_bill['odb_status'];
        if($_GET['print']!=1){
            $this->display();
        }
        else{
            $this->display('print_deliveryList');
        }

    }

    public function pToD(){
        if(empty($_GET['opb_id'])){
            $this->error('请选择商品采购单');
            exit;
        }
        else{
            //检查有没送货单未保存
            $sql = 'select count(odb_id) as cd from fx_orders_delivery_bill where opb_id='.$_GET['opb_id'].' and odb_status=0';
            $result = D('Orders')->query($sql);
            if($result[0]['cd']){
                $this->error('您有送货单没保存，不能再生成新的送货单');
                exit;
            }

            $odb_id = date('YmdHis') . rand(1000, 9999); 
            $odb['odb_id'] = $odb_id;
            $odb['odb_time'] = date('Y-m-d H:i:s');
            $odb['odb_status'] = 0;

            $condition['opb_id'] =$_GET['opb_id'];
            $limit['start'] =0;
            $limit['end'] =1;
            $goodsList = D('Orders')->GetPurchaseList($condition,$ary_field='',$group=array('op_id' => 'asc' ),$limit);
            
            $good = $goodsList[0];

            $where ='opb_id="'.$_GET['opb_id'].'" and op_nums>(odb_nums1+odb_nums2+odb_nums3+odb_nums4+odb_nums5)';

            if(empty($good['odb_id1'])){
                $sql = 'update fx_orders_purchases set odb_id1='.$odb_id.',odb_nums1=op_nums where '.$where;
                $odb['odb_level'] = 1;
            }
            elseif(empty($good['odb_id2'])){
                $sql = 'update fx_orders_purchases set odb_id2='.$odb_id.',odb_nums2=op_nums-odb_nums1 where '.$where;
                $odb['odb_level'] = 2;
            }
            elseif(empty($good['odb_id3'])){
                $sql = 'update fx_orders_purchases set odb_id3='.$odb_id.',odb_nums3=op_nums-odb_nums1-odb_nums2 where '.$where;
                $odb['odb_level'] = 3;
            }
            elseif(empty($good['odb_id4'])){
                $sql = 'update fx_orders_purchases set odb_id4='.$odb_id.',odb_nums4=op_nums-odb_nums1-odb_nums2-odb_nums3 where '.$where;
                $odb['odb_level'] = 4;
            }
            elseif(empty($good['odb_id5'])){
                $sql = 'update fx_orders_purchases set odb_id5='.$odb_id.',odb_nums5=op_nums-odb_nums1-odb_nums2-odb_nums3-odb_nums4 where '.$where;
                $odb['odb_level'] = 5;
            }
            else{
                $this->error('一张采购单最多只可以生成五张送货单');
                exit;               
            }
            //echo $sql;
            //exit();
            $result=D('Orders')->query($sql);
            //var_dump($result);
            //exit();
            D('Orders')->query('update fx_orders_purchases_bill set opb_status=1 where opb_id='.$_GET['opb_id']);
            $odb['opb_id'] = $_GET['opb_id'];
            $orders_delivery_bill = M('orders_delivery_bill',C('DB_PREFIX'),'DB_CUSTOM');
            $orders_delivery_bill->data($odb)->add();
            $this->success('操作成功','/Ucenter/Supplier/deliveryList/odb_id/'.$odb_id);
        }
    }

    public function ajaxOdbNums(){
        //var_dump($_POST);
        $sql = 'update fx_orders_purchases set odb_nums'.$_POST['odb_level'].'='.$_POST['left'].' where op_id='.$_POST['op_id'];
        D('Orders')->query($sql);
        echo 1;
    }

    public function ajaxDeliverySave(){
        //var_dump($_POST);
        //$_POST['odb_id']='201704051420296510';
        $sql = 'update fx_orders_delivery_bill set odb_status=1 where odb_id='.$_POST['odb_id'];
        D('Orders')->query($sql);

        //统计多批送货单是否满足采购单
        $sql = 'select count(op_id) as c from fx_orders_purchases where (op_nums!=odb_nums1+odb_nums2+odb_nums3+odb_nums4+odb_nums5) and (odb_id1='.$_POST['odb_id'].' or odb_id2='.$_POST['odb_id'].' or odb_id3='.$_POST['odb_id'].' or odb_id4='.$_POST['odb_id'].' or odb_id5='.$_POST['odb_id'].')';
        //echo $sql;
        $result = D('Orders')->query($sql);
        //echo $result[0]['c'];
        if($result[0]['c']==0){//无需继续生成送货单
           $sql = 'update fx_orders_purchases_bill set opb_status=2 where opb_id=(select opb_id from fx_orders_delivery_bill where odb_id='.$_POST['odb_id'].')'; 
           $result = D('Orders')->query($sql);
        }
        echo 1;
    }

    public function pToDinit(){
        $str_op_time = $this->_post('op_time');
        if($str_op_time){
            M('', '', 'DB_CUSTOM')->startTrans();
            $array_purchase = explode(',',$str_op_time);
            $data['is_delivery'] = 1;
            foreach($array_purchase as $op_time){
                  D('Orders')->UpdatePurchase($data,'',$op_time);
              }
              M('', '', 'DB_CUSTOM')->commit();
              $this->success('创建成功');
            }        
        else {
            $this->error('创建失败');
            exit;
        }
    }
    public function statementsbillList(){
        $count = D('Orders')->GetStatementsBillCount($condition);
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $billList = D('Orders')->GetStatementsBillList($condition,$ary_field='',$group=array('odb_id' => 'desc' ),$limit);
        $this->assign('billList',$billList);
        $this->assign("page", $page);
        $this->display();

    }

    public function statementsList(){
        if($_GET['ostb_id']){
            $condition = 'ostb_id="'.$_GET['ostb_id'].'"';
        }
        else{
            $condition = 1;
        }
        $count = D('Orders')->GetStatementsCount($condition);//179
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $limit['start'] =$obj_page->firstRow;
        $limit['end'] =$obj_page->listRows;
        $goodsList = D('Orders')->GetStatementsList($condition,$ary_field='',$order=array('op_id' => 'desc' ),$limit);
        //print_r($goodsList);
        //echo D('Orders')->getLastSql();
        foreach($goodsList as $key => $value) {
            $goodsList[$key]['Spec'] = D('GoodsSpec')->getPdtSpecList("pdt_id=".$value['pdt_id']);
            $goodsList[$key]['Spec'] = $goodsList[$key]['Spec'][0];
            $goodsList[$key]['place'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=893");
            $goodsList[$key]['guige'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=891");
            $goodsList[$key]['content'] = D('GoodsSpec')->getPdtSpec("g_id=".$value['g_id']." and gs_id=897");
            $goodsList[$key]['osb_num'] = $goodsList[$key]['osb_num1']+$goodsList[$key]['osb_num2']+$goodsList[$key]['osb_num3']+$goodsList[$key]['osb_num4']+$goodsList[$key]['osb_num5'];
        }
        $this->assign('goodsList',$goodsList);
        //print_r($goodsList);
        $this->assign("page", $page);
        $this->assign("ostb_id", $_GET['ostb_id']);
        $this->display();

    }
    public function backList(){
        $this->display();
    }
        /**计划商品表
    **/
        public function planList(){
          $user = session("Members");
          $condition = "m_id=".$user['m_id'];
          $count = D('GoodsPlan')->where($condition)->count();
          $obj_page = new Page($count, 10);
          $page = $obj_page->show();
          $limit['start'] =$obj_page->firstRow;
          $limit['end'] =$obj_page->listRows;

          $planList = D('GoodsPlan')->where($condition)->limit($limit['start'],$limit['end'])->order('pl_id desc')->select();
          
          $this->assign('planList',$planList);
          $this->assign("page", $page);
          $this->display();
        }

        //增加计算商品
        public function doPlanGoodsAdd(){
          // $debug = false;

          $data = array();
          $user = session("Members");
          $p_name = $_POST["p_name"];
          $p_url = $_POST["p_url"];
          $data['m_id'] = $user['m_id'];
          $data['m_name'] = $user['m_name'];
          $data["pl_good_name"] = $p_name;
          $data["pl_website_url"] = $p_url;
          // D("GoodsPlan")->startTrans();
          
          if(!empty($data)){
            $supplier = M('GoodsPlan')->data($data)->add();
            if($supplier){
              echo 1;
            }else{
              echo 0;
            }
          } 
        }

}