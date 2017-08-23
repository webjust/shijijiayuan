<?php

class BaowenAction extends HomeAction {

    public function AnalyzeRootDirectoryXML() {
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
    				$result["Status"] = $value;
    			}
    		}

    	}
    	$reader->close();

    	print_r(json_encode($result));
        die;
    }

    public function kj881101(){
        $time = date('YmdHis');
        $rand = rand(10000, 99999);
        $this->assign('MessageID','KJ881101_YUEQIAOMO_'.$time.$rand);
        $this->assign('SendTime',$time);
        $this->assign('DeclTime',$time);
        $this->assign('InputDate',$time);

        $this->assign('Seq','001');
        $goods_products = M('goods_products','fx_','mysql://qiaomoxuan:yvIo4CqmNykWluCt@10.46.99.172:3306/qiaomoxuan');

        //$sql = 'select gp.pdt_sn,gi.g_name,gp.pdt_cost_price,';

        $tpl = './Tpl/xml/KJ881101.html';
        $xml = $this->fetch($tpl);
        $file = './Baowen/KJ881101_YUEQIAOMO_'.$time.$rand.'.xml';
        file_put_contents($file,$xml);

        require_once './Lib/Common/ftp.class.php';
        
    }
}
