<?php

class BaowenAction extends HomeAction {

    public function AnalyzeRootDirectoryXML() {
        // $dir = './down';
        // $files = array();
        // $dirpath = realpath($dir);
        // $filenames = scandir($dir);

        // foreach ($filenames as $filename) 
        // {
        //     if ($filename=='.' || $filename=='..')
        //     {
        //         continue;
        //     }

        //     $file = $dirpath . DIRECTORY_SEPARATOR . $filename;

        //     if (is_dir($file))
        //     {
        //         $files = array_merge($files, self::treeDirectory($file));
        //     }
        //     else
        //     {
        //         $files[] = $file;
        //     }
        // }
        // echo (json_encode($files));exit;

    	$xml_url = 'http://www.caizhuangguoji.com/KJDOCREC_KJGGPT2017042711195726526.xml';
    	$result = array();

    	$reader = new XMLReader();
    	$reader->open($xml_url);
    	while ($reader->read()) {

    		if ($reader->name == "MessageID" && $reader->nodeType == XMLReader::ELEMENT) {
    			while($reader->read() && $reader->name != "MessageID") {
    				$name = $reader->name;
    				$value = $reader->value;
    				$result["MessageID"] = $value;
    			}
    		}

    		if ($reader->name == "OrgMessageID" && $reader->nodeType == XMLReader::ELEMENT) {
    			while($reader->read() && $reader->name != "OrgMessageID") {
    				$name = $reader->name;
    				$value = $reader->value;
    				$result["OrgMessageID"] = $value;
    			}
    		}

    		if ($reader->name == "Status" && $reader->nodeType == XMLReader::ELEMENT) {
    			while($reader->read() && $reader->name != "Status") {
    				$name = $reader->name;
    				$value = $reader->value;
    				$result["BaowenStatus"] = $value;
    			}
    		}

    	}
    	$reader->close();

        M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('MessageID'=>$result["MessageID"]))->data($result)->save();
    }

    public function kj881101(){
        $time = date('YmdHis');
        $rand = rand(10000, 99999);
        $MessageID = 'KJ881101_YUEQIAOMO_'.$time.$rand;
        $this->assign('MessageID',$MessageID);
        $this->assign('SendTime',$time);
        $this->assign('DeclTime',$time);
        $this->assign('InputDate',$time);

        $pdtIDArray = array();
        $pdtIDArray['0'] = "63";
        $pdtIDArray['1'] = "71";
        // $pdtIDArray['2'] = "3";

        $products = array();
        $EditData['MessageID'] = $MessageID;

        $Seq = 1;
        foreach ($pdtIDArray as $key => $value) {

            M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$value))->data($EditData)->save();

            $field = 'g_sn,pdt_bar_code,pdt_sn,g_id,pdt_sale_price,pdt_weight';
            $GoodsProducts_Info = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("pdt_id"=>$value))->find();
            $g_id = $GoodsProducts_Info['g_id'];

            $field = 'g_name,g_unit,g_description';
            $GoodsInfo_Info =M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("g_id"=>$g_id))->find();

            $field = 'gb_id';
            $Goods_Info = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("g_id"=>$g_id))->find();

            $field = 'gb_name,gb_region';
            $gb_id = $Goods_Info['gb_id'];
            $GoodsBrand_Info = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_id"=>$gb_id))->find();

            $product['Seq'] = $Seq;
            $product['EntGoodsNo']   = $GoodsProducts_Info['g_sn'];
            $product['ShelfGName'] = $GoodsInfo_Info['g_name'];
            $product['NcadCode']   = '09020100';
            $product['HSCode'] = '3307900000';
            $product['BarCode']   = $GoodsProducts_Info['pdt_bar_code'];
            $product['GoodsName'] = $GoodsInfo_Info['g_name'];
            $product['GoodsStyle']   = $GoodsProducts_Info['pdt_sn'];
            $product['Brand'] = str_replace("&","&amp;",$GoodsBrand_Info['gb_name']);

            $product['GUnit']   = '007';
            $product['StdUnit'] = '007';
            $product['RegPrice']   = $GoodsProducts_Info['pdt_sale_price'];
            $product['GiftFlag'] = '1';
            $product['OriginCountry']  = "133";
            $product['Quality'] = $GoodsInfo_Info['g_name'];
            $product['Manufactory']   = $GoodsBrand_Info['gb_region'];
            $product['NetWt'] = $GoodsProducts_Info['pdt_weight'];
            $product['GrossWt'] = $GoodsProducts_Info['pdt_weight'];

            $products[$value] = $product;
            $Seq++;
        }

        $GoodsContent = '';

        foreach ($products as $productKey => $product) {
            $GoodsContent.="\n<GoodsContent>\n";
            foreach ($product as $key => $value) {
                $GoodsContent.="<".$key.">".$value."</".$key.">\n";
            }
            $GoodsContent.="</GoodsContent>\n";
        }

        $this->assign('GoodsContent',$GoodsContent);


        //$sql = 'select gp.pdt_sn,gi.g_name,gp.pdt_cost_price,';

        $tpl = './Tpl/xml/KJ881101.html';
        $xml = $this->fetch($tpl);
        $file = './Baowen/KJ881101_YUEQIAOMO_'.$time.$rand.'.xml';
        file_put_contents($file,$xml);
        echo $xml;
    }

    public function kj881111(){
        //orderStatus(0/1/2) 状态不一致，身份证有无，手机号加密乱码，收货人地区6位数代号， InvoiceAmount


        $time = date('YmdHis');
        $rand = rand(10000, 99999);
        $MessageID = 'KJ881101_YUEQIAOMO_'.$time.$rand;
        $this->assign('MessageID',$MessageID);
        $this->assign('SendTime',$time);
        $this->assign('DeclTime',$time);

        $orderIDArray = array();
        $orderIDArray['0'] = "201609081958122625";

        $EditData['MessageID'] = $MessageID;

        $OrderDetail = '';
        $OrderIDString = '';

        foreach ($orderIDArray as $orderIDKey => $orderID) {

            $OrderDetail.="\n<OrderDetail>\n";

            M('orders',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$orderID))->data($EditData)->save();

            $field = 'o_status,o_pay_status,o_goods_all_price,o_cost_freight,o_tax_rate,o_goods_discount,o_pay,m_id,o_create_time';
            $Order_Info =M('orders',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$orderID))->find();

            $field = 'm_name,m_real_name,m_id_card,m_mobile';
            $Members_Info =M('members',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("m_id"=>$Order_Info['m_id']))->find();


            $field = 'od_receiver_name,od_receiver_address,od_receiver_mobile';
            $OrdersDelivery_Info = M('orders_delivery',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$orderID))->find();

            $order['EntOrderNo'] = $orderID;
            $order['OrderStatus']   = $Order_Info['o_status'];
            $order['PayStatus'] = $Order_Info['o_pay_status'];
            $order['OrderGoodTotal']   = strval($Order_Info['o_goods_all_price']);
            $order['OrderGoodTotalCurr'] = '142';

            $order['Freight']   = strval($Order_Info['o_cost_freight']);
            $order['Tax'] = strval($Order_Info['o_tax_rate']);
            $order['OtherPayment']   = '0';
            $order['OtherPayNotes']   = '';
            $order['OtherCharges']   = '';

            $order['ActualAmountPaid'] = strval($Order_Info['o_pay']);

            $order['RecipientName']   = $OrdersDelivery_Info['od_receiver_name'];
            $order['RecipientAddr'] = $OrdersDelivery_Info['od_receiver_address'];
            $order['RecipientTel']  = $OrdersDelivery_Info['od_receiver_mobile'];
            $order['RecipientCountry'] = '142';
            $order['RecipientProvincesCode'] = 0001010;

            $order['OrderDocAcount'] = $Members_Info['m_name'];
            $order['OrderDocName'] = $Members_Info['m_real_name'];
            $order['OrderDocType'] = '01';
            $order['OrderDocId'] = $Members_Info['m_id_card'];

            $order['OrderDocTel'] = $Members_Info['m_mobile'];
            $order['OrderDate'] = $time;//substr($Order_Info['o_create_time'],0,14);
            $order['BatchNumbers'] = '';
            $order['InvoiceType'] = '';
            $order['InvoiceNo'] = '';
            $order['InvoiceTitle'] = '';
            $order['InvoiceIdentifyID'] = '';
            $order['InvoiceDesc'] = '';

            $order['InvoiceAmount'] = 0;
            $order['Notes'] = '';

            foreach ($order as $key => $value) {
                $OrderDetail.="<".$key.">".$value."</".$key.">\n";
            }

            $field = 'oi_g_name,g_sn,oi_nums,oi_price';
            $Orders_items =M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("o_id"=>$orderID))->select(); 
            $OrderIDString = $orderID;
            $OrderDetail.="\n<GoodsList>\n";
            $Seq = 1;

            foreach ($Orders_items as $itemKey => $item) {
               $OrderDetail.="\n<OrderGoodsList>\n";
               $goods['Seq'] = $Seq;
               $goods['EntGoodsNo'] = $orderID;
               $goods['CIQGoodsNo']   = 'CIQGoodsNo';
               $goods['CusGoodsNo'] = '3307900000';
               $goods['HSCode']   = '3307900000';
               $goods['GoodsName'] = $item['oi_g_name'];

               $goods['GoodsStyle']   = $item['g_sn'];
               $goods['OriginCountry']   = '133';

               $goods['Qty'] = $item['oi_nums'];
               $goods['Unit']  = '007';
               $goods['Price'] = strval($item['oi_price']);

               $goods['Total'] = strval($item['oi_price']*$item['oi_nums']);
               $goods['CurrCode'] = '142';

               foreach ($goods as $key => $value) {
                   $OrderDetail.="<".$key.">".$value."</".$key.">\n";
               }

               $OrderDetail.="</OrderGoodsList>\n";
               $Seq++;

            }
            $OrderDetail.="</GoodsList>\n";
            $OrderDetail.="</OrderDetail>\n";
        }



        $this->assign('OrderDetail',$OrderDetail);


        //$sql = 'select gp.pdt_sn,gi.g_name,gp.pdt_cost_price,';

        $tpl = './Tpl/xml/KJ881111.html';
        $xml = $this->fetch($tpl);
        $file = './Baowen/KJ881111_YUEQIAOMO_'.$time.$rand.'.xml';
        file_put_contents($file,$xml);
        echo $xml;
    }

}

