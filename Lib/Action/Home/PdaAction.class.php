<?php
class PdaAction extends HomeAction {  

    public function UserLogin(){
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
			////check 帐号是否已存在
			$row = $member->chkAppUserName($m_name,'warehouse');
			if(empty($row)){
				$result["info"] = "帐号不存在";
				$result["status"] = "10002";
				print_r(json_encode($result));
				die;				
			}else{
				$userData = $member->doAppUserLogin($m_name,$m_password);
				if(empty($userData)){
					$result["info"] = "帐号或密码错误";
					$result["status"] = "10003";
					print_r(json_encode($result));
					die;
				}else{
					$result["info"]     = "登录成功";
					$result["status"]   = "10000";
					$result["m_id"] = $userData["m_id"];
					print_r(json_encode($result));
					die;
				}	
			}
        }
    }

    public function WarehousesList(){
    	$wlist = M('Warehouses')->field()->select();
        
        if(empty($wlist)){
            $result["info"]   = "暂无数据";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }else{
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["warehousesList"] = $wlist;
            print_r(json_encode($result));
            die;
        }
    }

    public function deliverybillList(){
        $condition['odb_status'] = 1;
        $limit['start'] =0;
        $limit['end'] =10000;        
        $billList = D('Orders')->GetDeliveryBillList($condition,$ary_field='',$group=array('odb_id' => 'desc' ),$limit);
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

    public function deliveryList(){
    	$odb_id = $this->_post("odb_id");
    	$odb_level = $this->_post("odb_level");
    	//$odb_id = 201704080948186210;
    	//$odb_level = 1;
    	if(empty($odb_id)||empty($odb_level)){
            $result["info"] = "参数错误";
            $result["status"] = "10001";
            //$result['odb_id'] = $odb_id;
            //$result['odb_level'] = $odb_level;
            print_r(json_encode($result));
			die;
        }
        else{
        	$sql = 'SELECT op.pdt_id,sum(op.odb_nums'.$odb_level.') as pdt_nums,op.osb_nums'.$odb_level.' as osb_nums,gp.pdt_bar_code FROM fx_orders_purchases op LEFT JOIN fx_goods_products gp on op.pdt_id=gp.pdt_id where odb_id'.$odb_level.'='.$odb_id.' GROUP BY op.pdt_id'; 
        	$deliveryList = D('Orders')->query($sql);
	        if(empty($deliveryList)){
	            $result["info"]   = "暂无数据";
	            $result["status"] = "10001";
	            print_r(json_encode($result));
	            die;
	        }else{
	            $result["info"]      = "请求成功";
	            $result["status"]    = "10000";
	            $result["deliveryList"] = $deliveryList;
	            print_r(json_encode($result));
	            die;
	        }         	

        }
    	//SELECT pdt_id,sum(op_nums) FROM `fx_orders_purchases` where odb_id1=201704071717426482 GROUP BY pdt_id;
    	//SELECT op.pdt_id,sum(op.op_nums),gp.pdt_bar_code FROM `fx_orders_purchases` op LEFT JOIN fx_goods_products gp on op.pdt_id=gp.pdt_id where odb_id1=201704071717426482 GROUP BY op.pdt_id;
    }

    public function Sku(){
        $pdt_bar_code = $this->_request("pdt_bar_code");

        if(empty($pdt_bar_code)){
            $result["info"] = "参数错误";
            $result["status"] = "10001";
            print_r(json_encode($result));
            die;
        }

        $sql = 'select gi.g_name,gi.g_picture,gi.g_weight,gi.g_unit,gi.g_length,gi.g_width,gi.g_height from fx_goods_info gi where gi.g_id=(select gp.g_id from fx_goods_products gp where gp.pdt_bar_code="'.$pdt_bar_code.'")';
        //echo $sql;
        $rs=D('Orders')->query($sql);

        if(empty($rs)){
            $result["info"]   = "暂无数据";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;           
        }else{
                $result["info"]      = "请求成功";
                $result["status"]    = "10000";
                $rs[0]['g_picture'] = 'http://www.caizhuangguoji.com'.$rs[0]['g_picture'];
                $result["sku"] = $rs[0];
                print_r(json_encode($result));
                die;
        }

    }

    public function SkuSize(){
        $pdt_bar_code = $this->_request("pdt_bar_code");
        $g_length = $this->_request("g_length");
        $g_width = $this->_request("g_width");
        $g_height = $this->_request("g_height");

        $Model = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $sql = 'update fx_goods_info set g_length='.$g_length.',g_width='.$g_width.',g_height='.$g_height.' where g_id=(select gp.g_id from fx_goods_products gp where gp.pdt_bar_code="'.$pdt_bar_code.'")';
        //echo $sql;
        $Model->query($sql);
        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        print_r(json_encode($result));
        die;        
    }


    public function doStorage(){
        $odb_id = $this->_post("odb_id");
        $odb_level = $this->_post("odb_level");
        $m_id = $this->_post("m_id");

        $pdt_id = $this->_post("pdt_id");//逗号隔开的字符串
        $nums = $this->_post("nums");//逗号隔开的字符串

        $status = $this->_post("status");
        if(empty($status)||empty($odb_id)||empty($odb_id)||empty($m_id)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }
        elseif($status=='-1'&&empty($pdt_id)&&empty($nums)){
            $result["info"] = "参数错误";
            $result["status"] = "100011";
            print_r(json_encode($result));
            die;
        }
        else{
            $Model = M('',C('DB_PREFIX'),'DB_CUSTOM');
            $sql = 'select w_id from fx_members where m_id='.$m_id;
            $rs = $Model->query($sql);
            $w_id = $rs[0]['w_id'];

            $osb_id = date('YmdHis') . rand(1000, 9999);
            $osb['osb_id'] = $osb_id;
            $osb['odb_id'] = $odb_id;
            $osb['osb_time'] = date('Y-m-d H:i:s');
            $osb['osb_status'] = 0;
            $osb['osb_level'] = $odb_level;
            
            $ost['ostb_id'] = date('YmdHis') . rand(1000, 9999);

            if($status=='-1'){//入库和送货不相符
                $pdt_id = str_replace('[', '', $pdt_id);
                $pdt_id = str_replace(']', '', $pdt_id);
                $nums = str_replace('[', '', $nums);
                $nums = str_replace(']', '', $nums);
                $pdt_id = explode(',', $pdt_id);
                $nums = explode(',', $nums);

                foreach ($pdt_id as $key => $value) {
                    $value = str_replace(' ', '', $value);
                    $nums[$key] = str_replace(' ', '', $nums[$key]);

                    $sql = 'select op_id,odb_nums'.$odb_level.' as pdt_nums from fx_orders_purchases where odb_id'.$odb_level.'='.$odb_id.' and pdt_id='.$value;
                    $row = $Model->query($sql);

                    $nums_key = $nums[$key];
                    foreach ($row as $k => $v) {
                        if($v['pdt_nums']>$nums_key||$v['pdt_nums']==$nums_key){
                            $sql = 'update fx_orders_purchases set osb_nums'.$odb_level.'=osb_nums'.$odb_level.'-'.$nums_key.' where op_id='.$v['op_id'];
                            $Model->query($sql);
                            break;
                        }
                        else{
                            $sql = 'update fx_orders_purchases set osb_nums'.$odb_level.'=0 where op_id='.$v['op_id'];
                            $Model->query($sql);
                            $nums_key = $nums_key-$v['pdt_nums'];
                        }
                    }
                }
            }
            else{
                $sql = 'update fx_orders_purchases set ostb_id='.$ost['ostb_id'].',osb_id'.$odb_level.'='.$osb_id.',osb_nums'.$odb_level.'=odb_nums'.$odb_level.' where odb_id'.$odb_level.'='.$odb_id;//入库数量等于送货数量

                $rs=$Model->query($sql);
            }

            M('orders_storage_bill')->data($osb)->add();
        }
        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        //$result["osb_id"]    = $osb_id;
        print_r(json_encode($result));
        die;
    }

    public function shelvesPlan(){//上架方案表，不真正上架
        $odb_id = $this->_request("odb_id");//送货单号
        $pdt_bar_code = $this->_request("pdt_bar_code");
        $m_id = $this->_request("m_id");


        $Model = M('',C('DB_PREFIX'),'DB_CUSTOM');

        $positionList = array();
        //先查询是否已分配过
        $sql = 'select * from fx_positions_storage where odb_id='.$odb_id;
        $rs = $Model->query($sql);
        if($rs){
            foreach ($rs as $key => $value){
                if($pdt_bar_code){
                    if($pdt_bar_code==$value['pdt_bar_code']){
                        $positionList[$key]['pdt_bar_code'] = $value['pdt_bar_code'];
                        $positionList[$key]['p_code'] = $value['p_code'];
                        $positionList[$key]['osb_nums'] = $value['pdt_nums'];                          
                    }                
                }
                else{
                    $positionList[$key]['pdt_bar_code'] = $value['pdt_bar_code'];
                    $positionList[$key]['p_code'] = $value['p_code'];
                    $positionList[$key]['osb_nums'] = $value['pdt_nums'];                    
                }
            }  
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["positionList"] = $positionList;
            print_r(json_encode($result));
            die;
        }
        if($pdt_bar_code){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;            
        }

        $sql = 'select w_id from fx_members where m_id='.$m_id;
        $rs = $Model->query($sql);
        $w_id = $rs[0]['w_id'];

        $sql = 'select odb_id,osb_level from fx_orders_storage_bill where odb_id='.$odb_id;
        $rs = $Model->query($sql);

        $odb_level = $rs[0]['osb_level'];
        $odb_id = $rs[0]['odb_id'];


        $sql = 'select g_id,pdt_id,osb_nums'.$odb_level.' osb_nums from fx_orders_purchases where odb_id'.$odb_level.'='.$odb_id.' and osb_nums'.$odb_level.'>0';
        $data = $Model->query($sql);

        //print_r($data);
        //exit();

        
        foreach ($data as $key => $value) {
            $sql = 'select pdt_bar_code from fx_goods_products where pdt_id='.$value['pdt_id'];
            $product = $Model->query($sql);

            //$positionList[$key]['pdt_bar_code'] = $product[0]['pdt_bar_code'];

            //$positionList[$key]['pdt_id'] = $value['pdt_id'];

            //print_r($positionList);
            //exit();           

            //分配仓位
            $sql = 'select * from fx_positions where w_id='.$w_id.' and pdt_id='.$value['pdt_id'];
            $position = $Model->query($sql);


            $new_position = true;

            $sql = 'select g_cell_nums from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_id='.$value['pdt_id'].')';
            $goods_info = $Model->query($sql);


            $left_osb_nums = $value['osb_nums'];

            foreach ($position as $key2 => $value2) {
                 //查看单元仓够不够放                
                if($goods_info[0]['g_cell_nums']>=$value2['pdt_nums']+$value2['temp_nums']+$left_osb_nums){//一个已有单元格可存放
                    $new_position = false;
                    $sql = 'update fx_positions set temp_nums=temp_nums+'.$left_osb_nums.' where p_id='.$value2['p_id'];//先临时存放
                    $Model->query($sql);
                    //$positionList[$key]['p_code'][] = $value2['p_code'];
                    //$positionList[$key]['pdt_nums'][] = $left_osb_nums;

                    $sql = 'insert into fx_positions_storage(w_id,p_code,odb_id,pdt_bar_code,pdt_nums) values('.$w_id.',"'.$value2['p_code'].'",'.$odb_id.',"'.$product[0]['pdt_bar_code'].'",'.$left_osb_nums.')';
                    $Model->query($sql);                   

                    break;
                }
                else{
                    $temp_nums = $goods_info[0]['g_cell_nums']-$value2['pdt_nums']-$value2['temp_nums'];
                    $sql = 'update fx_positions set temp_nums=temp_nums+'.$temp_nums.' where p_id='.$value2['p_id'];//先临时存放
                    $Model->query($sql);
                    //$positionList[$key]['p_code'][] = $value2['p_code'];
                    //$positionList[$key]['pdt_nums'][] = $temp_nums;
                    $left_osb_nums = $left_osb_nums-($goods_info[0]['g_cell_nums']-$value2['pdt_nums']-$value2['temp_nums']);

                    $sql = 'insert into fx_positions_storage(w_id,p_code,odb_id,pdt_bar_code,pdt_nums) values('.$w_id.',"'.$value2['p_code'].'",'.$odb_id.',"'.$product[0]['pdt_bar_code'].'",'.$temp_nums.')';
                    $Model->query($sql); 
                }
            }





            while($new_position){//寻找空仓
                $sql = 'select * from fx_positions where w_id='.$w_id.' and pdt_nums=0 and temp_nums=0 order by p_id asc';
                $nonposition = $Model->query($sql);
                if($nonposition[0]['p_id']){
                    if($goods_info[0]['g_cell_nums']>=$left_osb_nums){
                        $new_position = false;
                        $sql = 'update fx_positions set temp_nums='.$left_osb_nums.',pdt_id='.$value['pdt_id'].' where p_id='.$nonposition[0]['p_id'];
                        $Model->query($sql);
                        //$positionList[$key]['p_code'][] = $nonposition[0]['p_code'];
                        //$positionList[$key]['pdt_nums'][] = $left_osb_nums;

                        $sql = 'insert into fx_positions_storage(w_id,p_code,odb_id,pdt_bar_code,pdt_nums) values('.$w_id.',"'.$nonposition[0]['p_code'].'",'.$odb_id.',"'.$product[0]['pdt_bar_code'].'",'.$left_osb_nums.')';
                        $Model->query($sql);  

                    }
                    else{
                        $temp_nums = $goods_info[0]['g_cell_nums'];
                        $sql = 'update fx_positions set temp_nums=temp_nums+'.$temp_nums.',pdt_id='.$value['pdt_id'].' where p_id='.$nonposition[0]['p_id'];//先临时存放
                        $Model->query($sql);
                        //$positionList[$key]['p_code'][] = $nonposition[0]['p_code'];
                        //$positionList[$key]['pdt_nums'][] = $temp_nums;
                        $left_osb_nums = $left_osb_nums-$goods_info[0]['g_cell_nums'];

                        $sql = 'insert into fx_positions_storage(w_id,p_code,odb_id,pdt_bar_code,pdt_nums) values('.$w_id.',"'.$nonposition[0]['p_code'].'",'.$odb_id.',"'.$product[0]['pdt_bar_code'].'",'.$temp_nums.')';
                        $Model->query($sql);                         
                    }

                }              
            }
            //print_r($positionList);
            //exit();  
        }
        //查询分配结果
        $sql = 'select * from fx_positions_storage where odb_id='.$odb_id;
        $rs = $Model->query($sql);
        if($rs){
            foreach ($rs as $key => $value){
                $positionList[$key]['pdt_bar_code'] = $value['pdt_bar_code'];
                $positionList[$key]['p_code'] = $value['p_code'];
                $positionList[$key]['osb_nums'] = $value['pdt_nums'];
            }  
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["positionList"] = $positionList;
            print_r(json_encode($result));
            die;  

        }     
    }


    public function doShelves(){//上架操作
        $odb_id = $this->_request("odb_id");//送货单号
        $m_id = $this->_request("m_id");
        $p_code = $this->_request("p_code");
        $pdt_bar_code = $this->_request("pdt_bar_code");
        $pdt_nums = $this->_request("pdt_nums");

        $Model = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $sql = 'select w_id from fx_members where m_id='.$m_id;
        $rs = $Model->query($sql);
        $w_id = $rs[0]['w_id'];

        $sql = 'select * from fx_positions_storage where w_id='.$w_id.' and odb_id='.$odb_id.' and p_code="'.$p_code.'" and pdt_bar_code="'.$pdt_bar_code.'"';
        $rs = $Model->query($sql);

        if($rs[0]['pdt_nums']!=$pdt_nums){
            $result["info"] = "参数错误或商品数量不对";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;           
        }       
        else{
            $sql = 'delete from fx_positions_storage where w_id='.$w_id.' and odb_id='.$odb_id.' and p_code="'.$p_code.'" and pdt_bar_code="'.$pdt_bar_code.'"';
            $Model->query($sql);

            $sql = 'select pdt_id,g_id from fx_goods_products where pdt_bar_code="'.$pdt_bar_code.'"';
            $rs = $Model->query($sql);

            $sql = 'update fx_positions set pdt_nums=pdt_nums+'.$pdt_nums.',temp_nums=temp_nums-'.$pdt_nums.' where p_code="'.$p_code.'"';
            $Model->query($sql);


            $sql = 'update fx_goods_products set pdt_total_stock=pdt_total_stock+'.$pdt_nums.',pdt_stock=pdt_stock+'.$pdt_nums.' where pdt_id='.$rs[0]['pdt_id'];
            $Model->query($sql);
            $sql = 'update fx_goods_info set g_stock=g_stock+'.$pdt_nums.' where g_id='.$rs[0]['g_id'];
            $Model->query($sql);

            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            print_r(json_encode($result));           
        }


    }



    public function doStorageinit(){
        $odb_id = $this->_post("odb_id");
        $odb_level = $this->_post("odb_level");
        $w_id = $this->_post("w_id");

        $pdt_id = $this->_post("pdt_id");//逗号隔开的字符串
        $nums = $this->_post("nums");//逗号隔开的字符串

        $status = $this->_post("status");
        if(empty($status)||empty($odb_id)||empty($odb_id)||empty($w_id)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }
        elseif($status=='-1'&&empty($pdt_id)&&empty($nums)){
            $result["info"] = "参数错误";
            $result["status"] = "100011";
            print_r(json_encode($result));
            die;
        }
        else{
            $osb_id = date('YmdHis') . rand(1000, 9999);
            $osb['osb_id'] = $osb_id;
            $osb['odb_id'] = $odb_id;
            $osb['osb_time'] = date('Y-m-d H:i:s');
            $osb['osb_status'] = 0;
            $osb['osb_level'] = $odb_level;
            
            $ost['ostb_id'] = date('YmdHis') . rand(1000, 9999);
            
            $sql = 'update fx_orders_purchases set ostb_id='.$ost['ostb_id'].',osb_id'.$odb_level.'='.$osb_id.',osb_nums'.$odb_level.'=odb_nums'.$odb_level.' where odb_id'.$odb_level.'='.$odb_id;
            //$result["sql"] = $sql;
            $rs=D('Orders')->query($sql);

            //if($rs){
                M('orders_storage_bill')->data($osb)->add();

                
                $ost['osb_ids'] = $osb_id;
                $ost['ostb_time'] = date('Y-m-d H:i:s');
                $ost['ostb_status'] = 0;
                M('orders_statements_bill')->data($osb)->add();

            //}
            //else{
            //    $result["info"] = "系统出错".var_export($rs,true);
            //    $result["status"] = "10001";
            //    print_r(json_encode($result));
            //    die;               
            //}

            if($status=='-1'){//扫描和送货不相符
                $pdt_id = str_replace('[', '', $pdt_id);
                $pdt_id = str_replace(']', '', $pdt_id);
                $nums = str_replace('[', '', $nums);
                $nums = str_replace(']', '', $nums);
                $pdt_id = explode(',', $pdt_id);
                $nums = explode(',', $nums);

                foreach ($pdt_id as $key => $value) {
                    $value = str_replace(' ', '', $value);
                    $nums[$key] = str_replace(' ', '', $nums[$key]);
                    //$sql = 'update fx_orders_purchases set osb_nums'.$odb_level.'=osb_nums'.$odb_level.'-'.$nums[$key].' where odb_id'.$odb_level.'='.$odb_id.' and pdt_id='.$value;
                    //D('Orders')->query($sql);
                    //$result["sql".$key] = $sql;


                    $sql = 'select op_id,odb_nums'.$odb_level.' as pdt_nums from fx_orders_purchases where odb_id'.$odb_level.'='.$odb_id.' and pdt_id='.$value;
                    $row = D('Orders')->query($sql);

                    $nums_key = $nums[$key];
                    foreach ($row as $k => $v) {
                        if($v['pdt_nums']>$nums_key||$v['pdt_nums']==$nums_key){
                            $sql = 'update fx_orders_purchases set osb_nums'.$odb_level.'=osb_nums'.$odb_level.'-'.$nums_key.' where op_id='.$v['op_id'];
                            D('Orders')->query($sql);
                            break;
                        }
                        else{
                            $sql = 'update fx_orders_purchases set osb_nums'.$odb_level.'=0 where op_id='.$v['op_id'];
                            D('Orders')->query($sql);
                            $nums_key = $nums_key-$v['pdt_nums'];
                        }
                    }


                }
            }


            $sql = 'select g_id,pdt_id,osb_nums'.$odb_level.' osb_nums from fx_orders_purchases where odb_id'.$odb_level.'='.$odb_id.' and osb_nums'.$odb_level.'>0';
            $data = D('Orders')->query($sql);

            $positionList = array();
            foreach ($data as $key => $value) {
                $positionList[$key]['pdt_id'] = $value['pdt_id'];

                $sql = 'update fx_goods_products set pdt_total_stock=pdt_total_stock+'.$value['osb_nums'].',pdt_stock=pdt_stock+'.$value['osb_nums'].' where pdt_id='.$value['pdt_id'];
                D('Orders')->query($sql);
                $sql = 'update fx_goods_info set g_stock=g_stock+'.$value['osb_nums'].' where g_id='.$value['g_id'];
                D('Orders')->query($sql);

                //分配仓位
                $sql = 'select * from fx_positions where w_id='.$w_id.' and pdt_id='.$value['pdt_id'];
                $position = D('Orders')->query($sql);


                $new_position = true;

                if($position[0]['p_id']){
                    //$p_code = substr(strval($w_id+100),1,2).substr(strval($position[0]['p_code']+1000000),1,6);
                    //查看单元仓够不够放

                    $sql = 'select g_cell_nums from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_id='.$value['pdt_id'].')';
                    $goods_info = D('Orders')->query($sql);
                    if($goods_info[0]['g_cell_nums']>$position[0]['pdt_nums']+$value['osb_nums']){
                        $new_position = false;
                        $sql = 'update fx_positions set pdt_nums=pdt_nums+'.$value['osb_nums'].' where p_id='.$position[0]['p_id'];
                        D('Orders')->query($sql);
                    }
                    
                }
                if($new_position){//寻找空仓
                    $sql = 'select * from fx_positions where w_id='.$w_id.' and pdt_nums=0 order by p_id asc';
                    $nonposition = D('Orders')->query($sql);
                    if($nonposition[0]['p_id']){
                        //$p_code = substr(strval($w_id+100),1,2).substr(strval($nonposition[0]['p_code']+1000000),1,6);
                        $sql = 'update fx_positions set pdt_nums='.$value['osb_nums'].',pdt_id='.$value['pdt_id'].' where p_id='.$nonposition[0]['p_id'];
                        D('Orders')->query($sql);
                    }
                    else{
                        $result["info"] = "仓位不足";
                        $result["status"] = "100012";
                        print_r(json_encode($result));
                        die;                       
                    }
                    // else{
                    //     $sql = 'select max(p_code) as max_p_code from fx_positions where w_id='.$w_id;
                    //     $maxposition = D('Orders')->query($sql);
                    //     if($maxposition[0]['max_p_code']){
                    //         $sql = 'select p_cell from fx_positions where w_id='.$w_id.' and p_code='.$maxposition[0]['max_p_code'];
                    //         $cellposition = D('Orders')->query($sql);
                    //         $p_code_s = $maxposition[0]['max_p_code']+$cellposition[0]['p_cell'];
                    //         $p_code = substr(strval($w_id+100),1,2).substr(strval($p_code_s+1000000),1,6);
                    //     }
                    //     else{
                    //         $p_code_s = 1;
                    //         $p_code = substr(strval($w_id+100),1,2).'000001';
                    //     }
                    //     $sql = 'insert into fx_positions(w_id,p_code,pdt_id,pdt_nums) values('.$w_id.','.$p_code_s.','.$value['pdt_id'].','.$value['osb_nums'].')';
                    //     D('Orders')->query($sql);
                    // }
                    
                }

                $sql = 'select pdt_bar_code from fx_goods_products where pdt_id='.$value['pdt_id'];
                $product = D('Orders')->query($sql);

                $positionList[$key]['pdt_bar_code'] = $product[0]['pdt_bar_code'];
                $positionList[$key]['pdt_nums'] = $value['osb_nums'];
                $positionList[$key]['p_code'] = $p_code;
            }
        }
        //uasort($positionList,array($this, 'sort_by_p_code'));
        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        $result["positionList"] = $positionList;
        print_r(json_encode($result));
        die;
    }

    public function sort_by_p_code($x,$y){
        return strcasecmp($x['p_code'],$y['p_code']);
    }


    public function sortingbillList(){
        $condition['osob_status'] = 0;
        $limit['start'] =0;
        $limit['end'] =100;        
        $billList = D('Orders')->GetSortingBillList($condition,$ary_field='',$group=array('odb_id' => 'desc' ),$limit);
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

    public function sortingList(){
        $condition['osob_id'] = $this->_request("osob_id");
        //$condition['osob_id'] = 201704121137375;
        if(empty($condition['osob_id'])){
            $result["info"] = "参数错误";
            $result["status"] = "10001..";
            print_r(json_encode($result));
            die;
        }
        $limit['start'] =0;
        $limit['end'] =10000;   
        $sortingList = D('Orders')->GetSortingList($condition,$ary_field='os_id,osob_id,pdt_id,os_nums,g_name,g_bar_code,0 as osb_nums',$group=array('os_id' => 'desc' ),$limit);
        if(empty($sortingList)){
            $result["info"]   = "暂无数据";
            $result["status"] = "10002";
            print_r(json_encode($result));
            die;
        }else{
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            $result["sortingList"] = $sortingList;
            print_r(json_encode($result));
            die;
        }      
    }

    public function switchPosition(){
        $positionA = $this->_request("positionA");
        $positionB = $this->_request("positionB");

        //$positionA = '01000001';
        //$positionB = '01000002';

        if(empty($positionA)||empty($positionB)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }        
        $w_idA = intval(substr($positionA,0,2));
        $p_codeA = intval(substr($positionA,2,6));

        $w_idB = intval(substr($positionB,0,2));
        $p_codeB = intval(substr($positionB,2,6));

        if($w_idA!=$w_idB){
            $result["info"] = "两个仓位不属同一仓库，请检查";
            $result["status"] = "100011";
            print_r(json_encode($result));
            die;
        }

        $sqlA = 'select p_id,p_cell,pdt_id,pdt_nums from fx_positions where w_id='.$w_idA.' and p_code='.$p_codeA;
        $resultA = D('Orders')->query($sqlA);

        $sqlB = 'select p_id,p_cell,pdt_id,pdt_nums from fx_positions where w_id='.$w_idB.' and p_code='.$p_codeB;
        $resultB = D('Orders')->query($sqlB);

        if(empty($resultA[0]['p_id'])||empty($resultB[0]['p_id'])){
            $result["info"] = "查询不到对应仓位";
            $result["status"] = "100012";
            print_r(json_encode($result));
            die;            
        }

        if($resultA[0]['p_cell']!=$resultB[0]['p_cell']){
            $result["info"] = "两个仓位大小不一，不可以调换";
            $result["status"] = "100013";
            print_r(json_encode($result));
            die; 
        }

        $sqluA = 'update fx_positions set pdt_id='.$resultB[0]['pdt_id'].',pdt_nums='.$resultB[0]['pdt_nums'].' where w_id='.$w_idA.' and p_code='.$p_codeA;
        D('Orders')->query($sqluA);

        $sqluB = 'update fx_positions set pdt_id='.$resultA[0]['pdt_id'].',pdt_nums='.$resultA[0]['pdt_nums'].' where w_id='.$w_idB.' and p_code='.$p_codeB;
        D('Orders')->query($sqluB);

        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        print_r(json_encode($result));
    }

    public function upgradePosition(){
        $position = $this->_request("position");
        $cell = $this->_request("cell");
        if(empty($position)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }
        $w_id = intval(substr($position,0,2));
        $p_code = intval(substr($position,2,6));

        $sql = 'select p_id,p_cell,pdt_id,pdt_nums from fx_positions where w_id='.$w_id.' and p_code='.$p_code;
        $oldrow = D('Orders')->query($sql);         
        if(empty($oldrow[0]['p_id'])){
            $result["info"] = "查询不到对应仓位";
            $result["status"] = "100012";
            print_r(json_encode($result));
            die;            
        }
        if($oldrow[0]['p_cell']>$cell||$oldrow[0]['p_cell']==$cell){
            $result["info"] = "选择的单元格数量没有比现有的大，升级无效";
            $result["status"] = "100013";
            print_r(json_encode($result));
            die;
        }

        if($oldrow[0]['pdt_nums']==0){
            $result["info"] = "本仓位商品数量为0，升级无效";
            $result["status"] = "100014";
            print_r(json_encode($result));
            die;
        }

        $sql = 'select max(p_code) as max_p_code from fx_positions where w_id='.$w_id;
        $maxposition = D('Orders')->query($sql);

        $sql = 'select p_cell from fx_positions where w_id='.$w_id.' and p_code='.$maxposition[0]['max_p_code'];
        $cellposition = D('Orders')->query($sql);

        if($maxposition[0]['max_p_code']>$p_code){
            $p_code_s = $maxposition[0]['max_p_code']+$cellposition[0]['p_cell'];
            $to_p_code = substr(strval($w_id+100),1,2).substr(strval($p_code_s+1000000),1,6);


            $sql = 'insert into fx_positions(w_id,p_code,p_cell,pdt_id,pdt_nums) values('.$w_id.','.$p_code_s.','.$cell.','.$oldrow[0]['pdt_id'].','.$oldrow[0]['pdt_nums'].')';
            D('Orders')->query($sql);

            if($oldrow[0]['p_cell']==1){
                $sql = 'update fx_positions set pdt_id=0,pdt_nums=0 where w_id='.$w_id.' and p_code='.$p_code;
                D('Orders')->query($sql);
            }
            else{
                $sql = 'update fx_positions set p_cell=1,pdt_id=0,pdt_nums=0 where w_id='.$w_id.' and p_code='.$p_code;
                D('Orders')->query($sql);

                for($i=1;$i<$oldrow[0]['p_cell'];$i++){
                    $ex_p_code = $p_code+$i;
                    $sql = 'insert into fx_positions(w_id,p_code) values('.$w_id.','.$ex_p_code.')';
                    D('Orders')->query($sql);
                }
            }
        }
        else{//仓位已经是最后一个，直接更新cell
            $sql = 'update fx_positions set p_cell='.$cell.' where w_id='.$w_id.' and p_code='.$p_code;
            D('Orders')->query($sql);
            $to_p_code = substr(strval($w_id+100),1,2).substr(strval($p_code+1000000),1,6);
        }


        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        $result["from_position"]    = $position;
        $result["to_position"]    = $to_p_code;
        print_r(json_encode($result));
    }

    public function downgradePosition(){
        $position = $this->_request("position");
        $cell = $this->_request("cell");
        if(empty($position)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }
        $w_id = intval(substr($position,0,2));
        $p_code = intval(substr($position,2,6));

        $sql = 'select p_id,p_cell,pdt_id,pdt_nums from fx_positions where w_id='.$w_id.' and p_code='.$p_code;
        $oldrow = D('Orders')->query($sql);         
        if(empty($oldrow[0]['p_id'])){
            $result["info"] = "查询不到对应仓位";
            $result["status"] = "100012";
            print_r(json_encode($result));
            die;            
        }
        if($oldrow[0]['p_cell']<$cell||$oldrow[0]['p_cell']==$cell){
            $result["info"] = "选择的单元格数量没有比现有的小，降级无效";
            $result["status"] = "100013";
            print_r(json_encode($result));
            die;
        }

        $sql = 'update fx_positions set p_cell='.$cell.' where w_id='.$w_id.' and p_code='.$p_code;
        D('Orders')->query($sql);


        $sql = 'select max(p_code) as max_p_code from fx_positions where w_id='.$w_id;
        $maxposition = D('Orders')->query($sql);

        if($maxposition[0]['max_p_code']>$p_code){
             $c = $oldrow[0]['p_cell']-$cell+1;
            for($i=1;$i<$c;$i++){
                $ex_p_code = $p_code+$i;
                $sql = 'insert into fx_positions(w_id,p_code) values('.$w_id.','.$ex_p_code.')';
                D('Orders')->query($sql);
            }             
        }
        else{
            //仓位已经是最后一个，直接更新cell
            $sql = 'update fx_positions set p_cell='.$cell.' where w_id='.$w_id.' and p_code='.$p_code;
            D('Orders')->query($sql);          
        }
 
        $result["info"]      = "请求成功";
        $result["status"]    = "10000";
        print_r(json_encode($result));
    }

    
    public function barPosition(){
        $pdt_bar_code = $this->_request("pdt_bar_code");
        $w_id = $this->_request("w_id");
        if(empty($pdt_bar_code)||empty($w_id)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        }        
        $sql = 'select pdt_id from fx_goods_products where pdt_bar_code="'.$pdt_bar_code.'"';
        $product = D('Orders')->query($sql);
        if(empty($product[0]['pdt_id'])){
            $result["info"] = "查询不到此商品";
            $result["status"] = "100011";
            $result['sql'] = $sql;
            print_r(json_encode($result));
            die;           
        }
        $sql = 'select p_id,w_id,p_code from fx_positions where pdt_id="'.$product[0]['pdt_id'].'" and w_id='.$w_id;
        $position = D('Orders')->query($sql);
        if(empty($position[0]['p_id'])){
            $result["info"] = "查询不到仓位";
            $result["status"] = "100012";
            $result['sql'] = $sql;
            print_r(json_encode($result));
            die;            
        }
        else{
            $result["info"]      = "请求成功";
            $result["status"]    = "10000";
            //$result["p_code"]    = substr(strval($position[0]['w_id']+100),1,2).substr(strval($position[0]['p_code']+1000000),1,6);
            $result["position"]    = $position;
            print_r(json_encode($result));           
        }
    }

    public function CheckProduct(){
        $pdt_bar_code = $this->_request("pdt_bar_code");
         if(empty($pdt_bar_code)){
            $result["info"] = "参数错误";
            $result["status"] = "100010";
            print_r(json_encode($result));
            die;
        } 
        $sql = 'select pdt_id from fx_goods_products where pdt_bar_code="'.$pdt_bar_code.'"';
        $product = D('Orders')->query($sql);
        if(empty($product[0]['pdt_id'])){
            $result["info"] = "查询不到此商品";
            $result["status"] = "100011";
            print_r(json_encode($result));
            die;           
        }
        else{
            $result["info"]      = "有此商品";
            $result["status"]    = "10000";
            print_r(json_encode($result));            
        }      
    }





    public function OrderBox(){
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $box[0] = array(14,7,4);
        $box[1] = array(16,9,4);
        $box[2] = array(20,12,7);
        $box[3] = array(22,12,7);
        $box[4] = array(24,14,7);
        $box[5] = array(26,15,8);
        $box[6] = array(28,16,9);
        $box[7] = array(30,17,10);
        $box[8] = array(22,22,12);
        $box[9] = array(32,19,10);
        $box[10] = array(34,20,11);
        $box[11] = array(30,30,17);
        //从小到大


        // for($i=1;$i<31;$i++){
        //     for($j=1;$j<31;$j++){
        //         for($k=1;$k<31;$k++){
        //             if($i>=$j&&$j>=$k){
        //                 $box[] = array($i,$j,$k);
        //             }
        //         }
        //     }
        // }
        //print_r($box);
        //$products[0] = array(23,5,1);
        //$products[1] = array(16,5,1);
        //$products[2] = array(13,2,1);//从大到小

        if($_POST['p1_l']&&$_POST['p1_w']&&$_POST['p1_h']){
            $products[0] = array($_POST['p1_l'],$_POST['p1_w'],$_POST['p1_h']);

            echo '商品1：'.$_POST['p1_l'].','.$_POST['p1_w'].','.$_POST['p1_h'].'<br>';
        }
        if($_POST['p2_l']&&$_POST['p2_w']&&$_POST['p2_h']){
            $products[1] = array($_POST['p2_l'],$_POST['p2_w'],$_POST['p2_h']);
            echo '商品2：'.$_POST['p2_l'].','.$_POST['p2_w'].','.$_POST['p2_h'].'<br>';
        }
        if($_POST['p3_l']&&$_POST['p3_w']&&$_POST['p3_h']){
            $products[2] = array($_POST['p3_l'],$_POST['p3_w'],$_POST['p3_h']);
            echo '商品3：'.$_POST['p3_l'].','.$_POST['p3_w'].','.$_POST['p3_h'].'<br>';
        }
        if($_POST['p4_l']&&$_POST['p4_w']&&$_POST['p4_h']){
            $products[3] = array($_POST['p4_l'],$_POST['p4_w'],$_POST['p4_h']);
            echo '商品4：'.$_POST['p4_l'].','.$_POST['p4_w'].','.$_POST['p4_h'].'<br>';
        }
        if($_POST['p5_l']&&$_POST['p5_w']&&$_POST['p5_h']){
            $products[4] = array($_POST['p5_l'],$_POST['p5_w'],$_POST['p5_h']);
            echo '商品5：'.$_POST['p5_l'].','.$_POST['p5_w'].','.$_POST['p5_h'].'<br>';
        }

        $pc = count($products);
        $max_l = 0;
        $max_w = 0;
        $max_h = 0;
        for($p=0;$p<$pc;$p++){
            if($products[$p][0]>$max_l){
                $max_l = $products[$p][0];
            }
            if($products[$p][1]>$max_w){
                $max_w = $products[$p][1];
            }
            if($products[$p][2]>$max_h){
                $max_h = $products[$p][2];
            }
        }

        $_SESSION['bkey'] = -1;
        $this->CheckBox($box,$max_l,$max_w,$max_h,0,$products);

        $bkey = $_SESSION['bkey'];
        $nums = $bkey+1;
        echo '第'.$nums.'号箱,内尺寸：'.$box[$bkey][0].','.$box[$bkey][1].','.$box[$bkey][2];
        //print_r($box[$bkey]);
    }

    private function CheckBox($box,$max_l,$max_w,$max_h,$start,$products){
        $bkey = 0;
        for($i=$start;$i<4960;$i++){
            if($box[$i][0]>=$max_l&&$box[$i][1]>=$max_w&&$box[$i][2]>=$max_h){
                $bkey = $i;
                break;
            }
        }

        if($this->PushBox($box,$products,$bkey)){
            $_SESSION['bkey'] = $bkey;
            //return $bkey;
        }
        else{
            $start = $bkey+1;
            $this->CheckBox($box,$max_l,$max_w,$max_h,$start,$products);
        }    
    }

    private function PushBox_init($box,$products,$bkey){
        $main_box['l'] = $box[$bkey][0];
        $main_box['w'] = $box[$bkey][1];
        $main_box['h'] = $box[$bkey][2];

        $two_box['l'] = $main_box['l'];
        $two_box['w'] = $main_box['w'];
        $two_box['h'] = $main_box['h']-$products[0][2]-2.5;

        if($two_box['h']<$products[1][2]){
            return false;
        }

        $three_box['l'] = $main_box['l'];
        $three_box['w'] = $main_box['w'];
        $three_box['h'] = $two_box['h']-$products[1][2]-2.5;

        if($three_box['h']<$products[2][2]){
            return false;
        }
        return true;
    }

    private function PushBox($box,$products,$bkey){
        $pc = count($products);
        $h = 0;
        for($p=0;$p<$pc;$p++){
            if($p!=0){
                $h+=0.5+$products[$p][2];
            }
            else{
                $h=$products[$p][2];
            }
        }
        //echo 'h'.$h;
        if($box[$bkey][2]<$h){
            //全部层放放不下
            //for($bp=$pc-1;$bp>0;$bp--){
                
            //}
            //先比较体积
            $box_volume = $box[$bkey][0]*$box[$bkey][1]*$box[$bkey][2]*0.6;//算0.6利用率

            $products_volume = 0;
            for($p=0;$p<$pc;$p++){
               $products_volume+= $products[$p][0]*$products[$p][1]*$products[$p][2];
            }

            if($box_volume<$products_volume){
                return false;
            }
            else{
                //echo 'v'.$box_volume;
                //echo 'p'.$products_volume;
                //echo '<br>';
                return true; 
            }
            return false;
        }
        return true;
    }


    private function RefreshinBox($box,$bkey,$products,$pkey){
        $min = 100;
        $pc = count($products);
        for($p=$pkey+1;$p<$pc;$p++){//计算剩下商品下最小的边
            if($products[$p][2]<$min){
                $min = $products[$p][2];
            }
        }

        //$bc = count($box);
        if($pkey==0){//只有一个箱子
            //判断箱子长向没有空隙够用
            //if($box[$bkey][0]>$min+0.5+$products[$pkey][0]){
                //长向空隙可能可以利用
                $a = $box[$bkey][0]-$products[$pkey][0]-0.5;
                $b = $box[$bkey][1];
                $c = $box[$bkey][2];
                //长宽高按主箱对应，不按大中小排
                $inbox[] = array($a,$b,$c,'x');
            //}
            //判断箱子宽向没有空隙够用
            //if($box[$bkey][1]>$min+0.5+$products[$pkey][1]){
                //宽向空隙可能可以利用
                $a = $box[$bkey][0];
                $b = $box[$bkey][1]-$products[$pkey][1]-0.5;
                $c = $box[$bkey][2];
                //长宽高按主箱对应，不按大中小排
                $inbox[] = array($a,$b,$c,'y');
            //}
            //判断箱子高向没有空隙够用
            //if($box[$bkey][2]>$min+0.5+$products[$pkey][2]){
                //宽向空隙可能可以利用
                $a = $box[$bkey][0];
                $b = $box[$bkey][1];
                $c = $box[$bkey][2]-$products[$pkey][2]-0.5;
                //长宽高按主箱对应，不按大中小排
                $inbox[] = array($a,$b,$c,'z');
            //}
        }
        return $inbox;
    }

    //电子标签
    public function eleTagBack(){
        $data = file_get_contents('php://input', 'r');
        $debug = M('debug');
        $value['content'] = $data;
        $debug->data($value)->add();      
    }
}