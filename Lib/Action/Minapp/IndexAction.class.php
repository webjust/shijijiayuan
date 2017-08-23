<?php
class IndexAction extends GyfxAction {
	public function admin(){
		header("location:../Admin/Index/index");
		exit();
	}
    public function goods_choiceList(){
    	$M = M('','fx_','mysql://qiaomoxuan:yvIo4CqmNykWluCt@10.46.99.172:3306/qiaomoxuan');
    	$data = $M->query('select g_id,g_name,g_price,g_picture from fx_goods_info order by g_id desc limit 8');
    	foreach ($data as $key => $value) {
    		$data[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
    	}
    	if(empty($data)){
			$result["info"]   = "提交失败";
			$result["status"] = "10003";
			print_r(json_encode($result));
			die;
		}else{
			$result["info"]   = "提交成功";
			$result["status"] = "10000";
			$result["dataList"] = $data;
			print_r(json_encode($result));
			die;	
		}
    }

    public function test(){
        //获取此商品的扩展属性数据
        //$array_cond = array("g_id" => $ary_request['gid'], "gs_is_sale_spec" => 0);
        $ary_request['gid'] = 2914;
        $array_cond = "g_id=".$ary_request['gid']." and gs_is_sale_spec=0 and gs_id!=911";
		if($is_cache == true){
			$array_unsale_spec = D('Gyfx')->selectAllCache('related_goods_spec',$ary_field=null, $array_cond, array("gs_id asc"),'gsd_aliases');
		}else{
			$array_unsale_spec = D("RelatedGoodsSpec")->where($array_cond)->order(array("gs_id asc"))->group('gsd_aliases')->select();
		}    
        $c = count($array_unsale_spec);

        $array_cond = "g_id=".$ary_request['gid']." and gs_is_sale_spec=0 and gs_id=911";
        $array_unsale_spec_911 = D("RelatedGoodsSpec")->where($array_cond)->order(array("gs_id asc"))->group('gsd_aliases')->select();
        $spec_911 = '';
        foreach ($array_unsale_spec_911 as $key => $value) {
            $spec_911.=$value['gsd_aliases'].'　';
        }
        $array_unsale_spec[$c]["gs_id"] = $array_unsale_spec_911[0]['gs_id'];
        $array_unsale_spec[$c]["gsd_id"] = $array_unsale_spec_911[0]['gsd_id'];
        $array_unsale_spec[$c]["pdt_id"] = $array_unsale_spec_911[0]['pdt_id'];
        $array_unsale_spec[$c]["gs_is_sale_spec"] = $array_unsale_spec_911[0]['gs_is_sale_spec'];
        $array_unsale_spec[$c]["g_id"] = $array_unsale_spec_911[0]['g_id'];
        $array_unsale_spec[$c]["gsd_aliases"] = $spec_911;
        $array_unsale_spec[$c]["gsd_picture"] = $array_unsale_spec_911[0]['gsd_picture'];
        $array_unsale_spec[$c]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => 911))->getField("gs_name");
        $array_unsale_spec[$c]["gs_order"] = D("GoodsSpec")->where(array("gs_id" => 911))->getField("gs_order");
        

        foreach ($array_unsale_spec as $key => $val) {
			if($is_cache == true){
				$tmp_unsale_spec = D('Gyfx')->selectOneCache('goods_spec','gs_name', array("gs_id" => $val["gs_id"]));
                $array_unsale_spec[$key]["gs_name"] = $tmp_unsale_spec['gs_name'];
			}else{
				$array_unsale_spec[$key]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_name");

			}
            if($is_cache == true){
                $tmps_unsale_spec = D('Gyfx')->selectOneCache('goods_spec','gs_order', array("gs_id" => $val["gs_id"]));
                $array_unsale_spec[$key]["gs_order"] = $tmps_unsale_spec['gs_order'];
            }else{
                $array_unsale_spec[$key]["gs_order"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_order");

            }
            if($val['gsd_id'] != 0){
				if($is_cache == true){
					$tmp_unsale_spec_detail = D('Gyfx')->selectOneCache('goods_spec_detail','gsd_value',array("gsd_id" => $val["gsd_id"]));
					$array_unsale_spec[$key]["gsd_aliases"] = $tmp_unsale_spec_detail['gsd_value'];
				}else{
                     //$array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");					
				}		
            }
        }
        $new_array = array();
        for($i=0;$i<count($array_unsale_spec);$i++){
            $new_array[]= $array_unsale_spec[$i]['gs_order'];
        }
        array_multisort($new_array,$array_unsale_spec);
        echo $spec_911;
        print_r($array_unsale_spec);
    }
}