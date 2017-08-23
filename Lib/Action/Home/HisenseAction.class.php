<?php
class HisenseAction extends HomeAction{
    public function index(){
        $Goods = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        $where['g.g_status'] = 1;
        $where['g.g_on_sale'] = 1;
        $where['g.g_is_combination_goods'] = 0;
        $key_word = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
        $encode = mb_detect_encoding($key_word, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
        if($encode == 'UTF-8'){
            $key_word = $key_word;
        }else{
            if($encode != 'UTF-8'){
                $key_word = iconv($encode, 'UTF-8', $key_word);
            }
        }
        if($key_word){
           // $where['gi.g_name'] = array('like','%'.$key_word.'%');
			$temp_arr = array();
			$temp_arr['gi.g_name'] =  array('like', "%" . $key_word . "%");
			$temp_arr['gi.g_keywords'] =  array('like', "%" . $key_word . "%");
			$temp_arr['g.g_sn'] =  $key_word;
			$temp_arr['_logic'] = 'or';
			$ary_where['_complex'] = $temp_arr;
            $count = $Goods->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)')->where($where)->count();
        }else{
            $count = 0;
        }
        if($count!=1){
            if($count==0){
                $c_id = $_REQUEST['cid'];
                header("Location:".U("/Home/Products/index/cid/".$c_id));exit;
            }
            $tpl = U('/Home/Products/index')."/keyword/".base64_encode(urlencode($key_word));
            header("Location:".$tpl);exit;
        }else{
            $g_id = $Goods->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)')->where($where)->getField('g.g_id');
            header("Location:".U('/Home/Products/detail/gid/'.$g_id.'/')."?keyword=".$key_word);exit;
        }
    }
}