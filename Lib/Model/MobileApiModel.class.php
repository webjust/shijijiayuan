<?php

// Api数据库访问抽离层

class MobileApiModel extends GyfxModel {

     //分类产品 ->limit("{$startindex},{$limit}"),$startindex,$limit,$ary_field='*'
    public function GetProductListByCategoryId($gc_id,$ary_field='*',$startindex,$limit){
        $productList = M('view_goods',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gc_id"=>$gc_id))->limit("{$startindex},{$limit}")->select();
        return $productList;
    }

    //商品评论列表 
    public function GetCommentListByProductId($g_id,$ary_field='*',$startindex,$limit){
        $productList = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id,"gcom_status"=>"1","gcom_verify"=>"1"))->limit("{$startindex},{$limit}")->select();
        return $productList;
    }

    //商品推荐列表 
    public function GetRecommendListByProductId($g_id,$ary_field='*',$startindex,$limit){
        $productList = M('goods_comments',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id,"gcom_status"=>"1","gcom_verify"=>"1"))->limit("{$startindex},{$limit}")->select();
        return $productList;
    }
//商品详情
    public function GetProductDetailByProductId($g_id,$ary_field='*'){
        $productDetail = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id))->find();
        return $productDetail;
    }

    //颜色列表
    public function GetColorListByProductId($g_id,$ary_field='*',$gs_id){
        $colorList = M('related_goods_spec',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id,"gs_id"=>$gs_id))->select();
        return $productList;
    }

    public function GetChildCityListByParentCityId($cr_parent_id,$ary_field='*'){
        $cityList = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("cr_parent_id"=>$cr_parent_id))->select();
        return $cityList;
    }

    public function GetProductInfoFromGoodsProducts($pdt_id,$ary_field='*'){
        $productInfo = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("pdt_id"=>$pdt_id))->find();
        return $productInfo;
    }

    public function GetProductInfoFromGoodsInfo($g_id,$ary_field='*'){
        $productInfo = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id))->find();
        return $productInfo;
    }

    public function GetProductInfoFromGoods($g_id,$ary_field='*'){
        $productInfo = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("g_id"=>$g_id))->find();
        return $productInfo;
    }

     public function GetProductInfoFromGoodsBrand($gb_id,$ary_field='*'){
        $productInfo = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gb_id"=>$gb_id))->find();
        return $productInfo;
    }

    public function GetChildCategoryListByParentCategoryId($gc_parent_id,$ary_field='*'){
        $categoryList = M('goods_category',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gc_parent_id"=>$gc_parent_id,"gc_is_display"=>"1"))->select();
        return $categoryList;
    }

    public function GetAdByAdname($n_name,$ary_field='*'){
        $field = "n_imgurl,n_type,n_aurl,n_length,n_height";
        $adList = M('adwap',C('DB_PREFIX'),'DB_CUSTOM')->field($field)->where(array("n_name"=>$n_name,"n_status"=>"1"))->select();

        foreach ($adList as $key => $value) {
            $adList[$key]['n_imgurl'] = 'http://www.caizhuangguoji.com'.$value['n_imgurl'];
        }
        return $adList;
    }

    public function GetGlobalGoodsList($pages,$pageSize){
        $page  = max(1, intval($pages));
        $startindex=($page-1)*$pageSize;
        // ->limit("{$startindex},{$limit}")
        $ary_field = "g_name,g_id,g_price,ma_price as g_market_price,g_picture";
        $globalList = M('view_goods',C('DB_PREFIX'),'DB_CUSTOM')->field($ary_field)->where(array("gc_id"=>"100","g_status"=>"1"))->limit("{$startindex},{$pageSize}")->select();
        return $globalList;
    }

    public function GetGoodsAttribute($g_id,$gs_id) {
        $sql = "select gsd_aliases from fx_related_goods_spec where gs_id=$gs_id and g_id=$g_id";
        $attribute = M('')->query($sql);
        return $attribute;
    }

    public function GuessULike() {

        $sql = "select fx_goods_info.g_id,fx_goods_info.g_name,fx_goods_info.g_price,fx_goods_info.g_market_price,fx_goods_info.g_picture from fx_goods_info,fx_goods where fx_goods_info.g_id=fx_goods.g_id and fx_goods.g_guess=1 and fx_goods.g_status=1 and fx_goods.g_on_sale=1";
        $glist = M('')->query($sql);
        foreach ($glist as $key => $value) {
            $glist[$key]['g_picture'] = 'http://www.caizhuangguoji.com'.$value['g_picture'];
        }
        return $glist;
    }

}