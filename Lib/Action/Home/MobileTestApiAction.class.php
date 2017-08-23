<?php
class MobileTestApiAction extends HomeAction {
		//商品列表
	public function GetCategoryProductList(){
		//定义一个数组来储存数据
		$result = array();
		//分类ID
		$cid   = $_POST["categoryId"];

        if (empty($cid)) {
        	$result["message"]      = "参数错误";
        	$result["status"]    = "10001";
        	print_r(json_encode($result));
        	die;
        }else {
        			//分页
        	$limit = empty($_POST["pageSize"])?20:$_POST["pageSize"];
        	$page  = max(1, intval($pages));
        	$startindex=($page-1)*$limit;

        	$MobileApi     = D("MobileApi");
        	$field = 'g_id,gc_id,g_name,g_picture,gc_name,g_price,g_salenum,g_market_price';
		     // $field = 'gc_id as categoryId,g_name as productName';
        	$productList = $MobileApi->GetProductListByCategoryId($cid,$field,$startindex,$limit);

        	$result["message"]      = "请求成功";
        	$result["status"]    = "10000";
        	$body["productList"] = $productList;
        	$result["body"] = $body;
        	print_r(json_encode($result));
        	die;
        }
		
	}

	public function GetProductRecommendList(){		
		//定义一个数组来储存数据
		$result = array();
		$gid       = $_POST["productId"];

		if (empty($gid)) {
			$result["message"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else {
			$MobileApi     = D("MobileApi");

			$limit = empty($_POST["pageSize"])?20:$_POST["pageSize"];;
        	$page  = max(1, intval($pages));
        	$startindex=($page-1)*$limit;

        	$field = 'gcom_id as commentId,m_id as userId,g_id as productId,gcom_title as title,gcom_content as content,gcom_mbname as nickName,gcom_star_score as score,gcom_create_time as createTime,gcom_pics as picture,gcom_order_id as orderId';
		// $field = 'gc_id as categoryId,g_name as productName';
        	$commentList = $MobileApi->GetRecommendListByProductId($gid,$field,$startindex,$limit);

        	$result["message"]      = "请求成功";
        	$result["status"]    = "10000";
        	$body["commentList"] = $commentList;
        	$result["body"] = $body;
        	print_r(json_encode($result));
        	die;
		}
	}

	public function GetProductDetail(){	
		//定义一个数组来储存数据
		$result = array();
		$gid       = $_POST["productId"];

		if (empty($gid)) {
			$result["message"]   = "参数错误";
			$result["status"] = "10001";
			print_r(json_encode($result));
			die;
		}else {
	        $MobileApi     = D("MobileApi");

        	// $field = ',g_market_price as marketPrice,g_discount as discount';
        	$field = 'g_id as productId,g_name as productName,g_picture as mainImage,g_stock as stock,g_discount as discount,g_price as price,g_market_price as marketPrice,g_desc';
        	$productDetail = $MobileApi->GetProductDetailByProductId($gid,$field);

        	// $gsid = "888";
        	// $field = 'pdt_id as colorId,gsd_aliases as colorName,gsd_picture as picture';
        	// $colorList = array();
        	// $colorList = $MobileApi->GetColorListByProductId($gid,$field,$gsid);
        	// for ($i= 0;$i< count($colorList); $i++){ 
        	// 	$color = $colorList[$i]; 
        	// 	$color["stock"] = $productDetail["stock"];
        	// } 

        	$result["message"]      = "请求成功";
        	$result["status"]    = "10000";
        	// $productDetail["colorList"] = $colorList;
        	$body["productDetail"] = $productDetail;
        	$result["body"] = $body;
        	print_r(json_encode($result));
        	die;
		}
	}

}