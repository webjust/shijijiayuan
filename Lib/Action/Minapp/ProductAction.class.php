<?php
class ProductAction extends Action {

    public function detail(){
    	$g_id = $this->_request('id');
    	$M = M('','fx_','mysql://qiaomoxuan:yvIo4CqmNykWluCt@10.46.99.172:3306/qiaomoxuan');
    	$data = $M->query('select g_id,g_name,g_price,g_picture from fx_goods_info where g_id='.$g_id);

    	if(empty($data)){
			$result["info"]   = "提交失败";
			$result["status"] = "10003";
			print_r(json_encode($result));
			die;
		}else{
			$data[0]['g_picture'] = 'http://www.caizhuangguoji.com'.$data[0]['g_picture'];
	    	$goodspics = $data[0]['g_picture'];
	    	$pic = $M->query('select gp_picture from fx_goods_pictures where g_id='.$g_id);
	    	foreach ($pic as $key => $value) {
	    		$goodspics = $goodspics.'#http://www.caizhuangguoji.com'.$value['gp_picture'];
	    	}
	    	$data[0]['goodspics'] = $goodspics;

	    	$color = $M->query('select gsd_aliases from fx_related_goods_spec where g_id='.$g_id.' and gs_id=888');

	    	foreach ($color as $key => $value) {
	    		if($key==0){
	    			$goodscolor = $value['gsd_aliases'];
	    		}
	    		else{
	    			$goodscolor = $goodscolor.'|'.$value['gsd_aliases'];
	    		}
	    	}
	    	$data[0]['goodscolor'] = $goodscolor;
	    	
			$result["info"]   = "提交成功";
			$result["status"] = "10000";
			$result["data"] = $data[0];
			print_r(json_encode($result));
			die;	
		}
    }
}