<?php
class WarehouseAction extends CommonAction {

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
        $this->display(FXINC . '/Tpl/Ucenter/Supplier/PendingList.html');
    }
    
    public function deliverybillList(){
        $M = M('');
        $condition = 'd_sn is not null';
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

    public function receiptList(){
        $this->display();
    }

    public function receiptScan(){
        $this->display();
    }

    public function getReceiptSku(){
        $bar_code = trim($_GET['bar_code']);
        $o_id = $_GET['o_id'];

        $M = M('');
        $sql = 'select g_name,g_picture from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code="'.$bar_code.'")';
        $rs = $M->query($sql);
        if(empty($rs)){
            $result["info"]   = "暂无商品";
            $result["status"] = "10001";
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


            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["rs"] = $rs[0];
            print_r(json_encode($result));
            die;
        }
    }

}