
<?php

// Api数据库访问抽离层

class ApiUtilModel extends GyfxModel {
	public function GetCategoryList($isAll){
		$returnArray = array();
        $field = 'gc_id,gc_name,gc_pic_url';
		if($isAll){
            $returnArray = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gc_level"=>"1","gc_is_display"=>"1"))->select();
		}else {
            $returnArray = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gc_level"=>"1","gc_is_display"=>"1"))->limit(16)->select();
		}
		foreach ($returnArray as $key => $value) {
			$returnArray[$key]['gc_pic_url'] = 'http://www.caizhuangguoji.com'.$value['gc_pic_url'];
		}

       return $returnArray;
    }

    public function GetCountryList($isAll){
	    $returnArray = array();
        $field = 'gsd_id as country_id,gsd_value as country_name,gsd_pic as country_logo';
        if($isAll){
            $returnArray = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->select();
		}else {
            $returnArray = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"893"))->limit(8)->select();
		}
		foreach ($returnArray as $key => $value) {
        	$returnArray[$key]['country_logo'] = 'http://www.caizhuangguoji.com'.$value['country_logo'];
        }

        return $returnArray;
    }

    public function GetBrandList($isAll){
	    $returnArray = array();
        $field = 'gb_id,gb_name,gb_logo';
        if($isAll){
            $returnArray = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->select();
		}else {
            $returnArray = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gb_display"=>"1"))->limit(16)->select();
		}
		foreach ($returnArray as $key => $value) {
        	$returnArray[$key]['gb_logo'] = 'http://www.caizhuangguoji.com'.$value['gb_logo'];
        }

        return $returnArray;
    }

    public function GetFunctionList($isAll){
	    $returnArray = array();
        $field = 'gsd_id as function_id,gsd_value as function_name';
        if($isAll){
            $returnArray = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"911"))->select();
		}else {
            $returnArray = M('goods_spec_detail',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("gs_id"=>"911"))->limit(16)->select();
		}

        return $returnArray;
    }

    public function GetFilterGoodsList($category_id,$country,$brand_id,$function,$minPrice,$maxPrice,$keywords,$ishot,$isnew){
		$where = '1';

		$M = M('');
		$g_ids = array();

		if(!empty($brand_id)){
			$sql = 'select g_id from fx_goods where gb_id in ('.$brand_id.') and g_on_sale=1 and g_status=1';
		}
		else{
			$sql = 'select g_id from fx_goods where g_on_sale=1 and g_status=1';
		}

		if ($ishot == "1") {
			$sql = $sql.' and g_hot=1';
		}

		if ($isnew == "1") {
			$sql = $sql.' and g_new=1';
		}

		$rs = $M->query($sql);
		foreach ($rs as $key => $value) {
			$g_ids[] = $value['g_id'];
		}


		if(!empty($country)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases in ('.$country.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$country_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$country_g_ids);
		}

		if(!empty($function)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases in ('.$function.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$function_g_ids[] = $value['g_id'];
			}			
			$g_ids = array_intersect($g_ids,$function_g_ids);
		}
		if(!empty($category_id)){
			$sql = 'select g_id from fx_related_goods_category where gc_id in ('.$category_id.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$category_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$category_g_ids);	
		}
		
		$gids = implode(",",$g_ids);
		$where.=' and g_id in ('.$gids.')';

		if(!empty($minPrice) && !empty($maxPrice)){
			$where.=' and g_price between '.$minPrice.' and '.$maxPrice;
		}

		if(!empty($keywords)){
			$where.=' and g_name like "%'.$keywords.'%"';
		}

		$sql = 'select g_id,g_name,g_picture,g_price,g_market_price,g_salenum from fx_goods_info where '.$where.' order by g_price ASC,g_update_time desc,g_salenum desc';

		$glist = $M->query($sql);

		foreach ($glist as $key => $value) {
			$glist[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
		}

        return $glist;
    }

     public function GetFilterBrandList($gb_letter,$gb_region,$keywords){
     	$where = 'gb_display = 1';
     	if (!empty($gb_letter)) {
     		$where .= ' and gb_letter = "'.$gb_letter.'"';
     	}

     	if (!empty($gb_region)) {
     		$where .= ' and gb_region = "'.$gb_region.'"';
     	}

     	if(!empty($keywords)){
			$where.=' and gb_name like "%'.$keywords.'%"';
		}

     	$sql = 'select gb_id,gb_name,gb_logo from fx_goods_brand  where '.$where.'';
		
		$brandList = M('')->query($sql);

		foreach ($brandList as $key => $value) {
        	$brandList[$key]['gb_logo'] = 'http://www.caizhuangguoji.com'.$value['gb_logo'];
        }

		return $brandList;
    }

    public function GetGlobalGoodsList($kewords){
        $where = 'gc_id = 100 and g_status = 1';

    	if(!empty($keywords)){
    		$where.=' and g_name like "%'.$keywords.'%"';
    	}

        $sql = 'select g_name,g_id,g_price,ma_price as g_market_price,g_picture from fx_view_goods  where '.$where.'';
        $glist = M('')->query($sql);

        foreach ($glist as $key => $value) {
        	$glist[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
        }
        $result["glist"] = $glist;
        $result["sql_2"] = M('')->getLastSql();
        return $glist;
    }

    public function GetFilterSpecialGoodsList($category_id,$country,$brand_id,$function,$minPrice,$maxPrice,$keywords,$ishot,$isnew){
        $where = '1';

		$M = M('');
		$g_ids = array();

		if(!empty($brand_id)){
			$sql = 'select g_id from fx_goods where gb_id in ('.$brand_id.') and g_on_sale=1 and g_status=1';
		}
		else{
			$sql = 'select g_id from fx_goods where g_on_sale=1 and g_status=1';
		}

		if ($ishot == "1") {
			$sql = $sql.' and g_hot=1';
		}

		if ($isnew == "1") {
			$sql = $sql.' and g_new=1';
		}

		$rs = $M->query($sql);
		foreach ($rs as $key => $value) {
			$g_ids[] = $value['g_id'];
		}


		if(!empty($country)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases in ('.$country.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$country_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$country_g_ids);
		}

		if(!empty($function)){
			$sql = 'select g_id from fx_related_goods_spec where gsd_aliases in ('.$function.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$function_g_ids[] = $value['g_id'];
			}			
			$g_ids = array_intersect($g_ids,$function_g_ids);
		}
		if(!empty($category_id)){
			$sql = 'select g_id from fx_related_goods_category where gc_id in ('.$category_id.')';
			$rs = $M->query($sql);
			foreach ($rs as $key => $value) {
				$category_g_ids[] = $value['g_id'];
			}
			$g_ids = array_intersect($g_ids,$category_g_ids);	
		}
		
		$gids = implode(",",$g_ids);
		$where.=' and g_id in ('.$gids.')';

		if(!empty($minPrice) && !empty($maxPrice)){
			$where.=' and g_price between '.$minPrice.' and '.$maxPrice;
		}

		if(!empty($keywords)){
			$where.=' and g_name like "%'.$keywords.'%"';
		}

		$sql = 'select g_id from fx_goods_info where '.$where.'';

		$goods_list = $M->query($sql);
		$goods_ids = array();

		foreach ($goods_list as $key => $value) {
			$goods_ids[] = $value['g_id'];
		}

		$sql = 'select g_id from fx_spike';

		$spile_list = $M->query($sql);
		$spike_ids = array();

		foreach ($spile_list as $key => $value) {
			$spike_ids[] = $value['g_id'];
		}

		$filter_ids = array_intersect($goods_ids,$spike_ids);

		$filters = implode(",",$filter_ids);

		$sql = "select g_id,g_name,g_picture,g_price,g_market_price from fx_goods_info where g_id in ($filters)";

		$glist = $M->query($sql);

		foreach ($glist as $key => $value) {
			$glist[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
		}

        return $glist;
    }
	
}