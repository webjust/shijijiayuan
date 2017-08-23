<?php
/**
 * 购物车接口
 * @author wanghaijun
 * @date 2014-12-15
 */

Class ApiCartsModel extends GyfxModel{

	private $result;
    const sn = 'mycart';

	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10102', 		// 购物车错误初始码
			'sub_msg' => '购物车错误', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
	}

    /**
     * 获取用户购物车已存在的商品
     * @author wanghaijun
     * @data 2014-12-15
     * @return   array
     */
    function GetData($key){
        $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $key))->find();
        $data = unserialize(urldecode($row['value']));
        if(is_array($data)&& !empty($data)){
            return $data;
        }else{
            return $data=array();
        }
        
    }
    
    /**
     * 存储购物车商品
     * @author wanghaijun
     * @data 2014-12-15
     * @return   bool
     */
    function WriteMycart($data,$key)
    {
        $str = urlencode(serialize($data));
        $row = M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array("key" => $key))->find();
        
        $now = date('Y-m-d H:i:s');
        try {
            if ( $row ) {
                if ( $row['value'] != $str ) {
                    $update_data['value']=$str;
                    $update_data['modify_time']=$now; 
                    M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->where(array('key'=>$key))->save($update_data);
                }
            }
            else {
                $insert_data['key']=$key;
                $insert_data['value']=$str;
                $insert_data['create_time']=$now;
                $insert_data['modify_time']=$now;                         
                M('mycart',C('DB_PREFIX'),'DB_CUSTOM')->add($insert_data); 
            }
        } catch ( Exception $e ) {
            return false;
        }
        return true;
    }

	/**
	 * [获取购物车]
	 * @param [type] $params [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-4
	 */
	public function GetCarts($params){
		$result = $this->GetCartFormat($params);
		if(!$result){
			$this->result['sub_msg'] = '获取购物车失败!';
		}else{
			$this->result['status'] = true;
        	$this->result['code'] = 10105;
        	$this->result['sub_msg'] = '获取购物车成功!';
        	$this->result['info'] = $result;
		}
		return $this->result;
	}

	/**
     * 获取货品的信息
     * @author wanghaijun
     * @data 2014-12-15
     * @param return   array
     */
    public function getProductInfo($ary_data = array(),$mid) {
        $memberId = isset($mid) && $mid > 0 ? $mid:0;
        $price = new PriceModel($memberId);
        if (is_array($ary_data) && isset($ary_data)) {
            $i = 0;
            foreach ($ary_data as $k => $v) {
            		//如果是自由推荐商品
            		if($v['type'] == '4'){
            			$ary_where = array();
            			$pdt_ids = implode(',',$v['pdt_id']);
            			$ary_where['_string'] = 'pdt_id in('.$pdt_ids.')';
            			$arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
            			->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price')
            			->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
            			->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
            			->where($ary_where)->select();
            		}else if($v['type'] == '5'){
                        //如果是团购商品
                        $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')     
                         ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                         ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                         ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                         ->where(array("pdt_id" => $k))->find();
                       //  print_r($arr_newdata[$i]);exit;
                    }else if($v['type'] == '6'){
                        //如果是自由组合商品
                        $ary_where = array();
            			$pdt_ids = implode(',',$v['pdt_id']);
            			$ary_where['_string'] = 'pdt_id in('.$pdt_ids.')';
            			$arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
            			->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_name,fx_goods_info.g_picture,pdt_collocation_price,fr.fr_name,fr.fr_price,fr.fr_id')
            			->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
            			->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                        ->join('fx_free_recommend as fr on(fr.fr_goods_id = fx_goods.g_id)')
            			->where($ary_where)->select();
                    }else if($v['type'] == '7'){
                        //如果是秒杀商品
                        $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')     
                         ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                         ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                         ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                         ->where(array("pdt_id" => $k))->find();
                    }else if($v['type'] == '8'){
                        //如果是预售商品
                        $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')     
                         ->field('pdt_sale_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price')
                         ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
                         ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
                         ->where(array("pdt_id" => $k))->find();
                    }else{
                    	 //商品搜索
                    	 $ary_search_where = array();
                    	 //无pdt_id
                    	 if(empty($k)){
                    	 	$ary_search_where['fx_goods_products.g_id'] = $v['g_id'];
                    	 }else{
                    	 	$ary_search_where['pdt_id'] =$k;
                    	 }
						 $arr_newdata[$i] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')     
            			 ->field('pdt_sale_price,pdt_market_price,fx_goods.gt_id,fx_goods_products.g_sn,fx_goods_products.pdt_cost_price,pdt_id,pdt_sn,pdt_id,pdt_sn,fx_goods_products.pdt_stock,fx_goods_products.g_id,point as g_point,fx_goods_info.g_picture,fx_goods_info.g_name,pdt_collocation_price,fx_goods_info.gifts_point,is_exchange')
            			 ->join('fx_goods_info on(fx_goods_products.g_id=fx_goods_info.g_id)')
            			 ->join('fx_goods on(fx_goods_products.g_id=fx_goods.g_id)')
            			 ->where($ary_search_where)->find();
            			//dump(M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());die();
            		}
                    if(isset($v['type']) && $v['type'] == 1){
    					//积分商品
    				    $arr_newdata[$i]["pdt_sale_price"] =  intval($arr_newdata[$i]["g_point"]);
                        $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);
    					$arr_newdata[$i]["pdt_momery"] = intval($v['num'] * $arr_newdata[$i]["g_point"]);
    					$arr_newdata[$i]["pdt_price"] = sprintf("%0.2f",$arr_newdata[$i]["g_point"]);
    					$arr_newdata[$i]["pdt_nums"] = $v['num'];
    					$arr_newdata[$i]["type"] = 1;
    				}
    				else{
                        if($v['type'] == 2){//赠品
                            $arr_newdata[$i]["g_name"]="[赠品]".$arr_newdata[$i]["g_name"];
                            $arr_newdata[$i]["pdt_sale_price"]=0;
                            $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);
                            $arr_newdata[$i]['pdt_nums'] =1;
                            $arr_newdata[$i]['pdt_momery'] =0;
                            $arr_newdata[$i]["pdt_price"] = 0;
                            $arr_newdata[$i]["type"]=2;
                        }
                        elseif($v['type'] == 3){//组合商品
                            $arr_newdata[$i]["f_price"]=$price->getMemberPrice($k);;
                            $arr_newdata[$i]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i]["pdt_sale_price"] - $arr_newdata[$i]["f_price"]);
                            $arr_newdata[$i]["pre_price"]=sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["pdt_preferential"]);
                            $arr_newdata[$i]["pdt_rule_name"]='组合商品';
                            $arr_newdata[$i]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($k,$member['m_id']);
                            $arr_newdata[$i]["pdt_nums"] = $v['num'];
                            $arr_newdata[$i]["pdt_momery"] = sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["f_price"]);
                            $arr_newdata[$i]["pdt_price"] = sprintf("%0.2f",$arr_newdata[$i]["f_price"]);
                            $arr_newdata[$i]["type"]=3;
                        }
    				    elseif($v['type'] == 4){//自由推荐商品
    				    	$nums = $v['num'];
    				    	foreach($arr_newdata[$i] as $key=>$item_info){
    				    		$arr_newdata[$i][$key]['pdtId'] = $k;
    				    		$arr_newdata[$i][$key]["g_picture"] = getFullPictureWebPath($item_info["g_picture"]);
    				    		$arr_newdata[$i][$key]["g_name"]="[自由推荐]".$item_info["g_name"];
                              	$arr_newdata[$i][$key]["f_price"]=empty($item_info['pdt_collocation_price'])?$item_info['pdt_sale_price']:$item_info['pdt_collocation_price'];
	                            $arr_newdata[$i][$key]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i][$key]["pdt_sale_price"] - $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["pre_price"]=sprintf("%0.2f", $nums[$key] * $arr_newdata[$i][$key]["pdt_preferential"]);
	                            $arr_newdata[$i][$key]["pdt_rule_name"]='自由推荐';
	                            $arr_newdata[$i][$key]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($item_info['pdt_id'],$member['m_id']);
	                            $arr_newdata[$i][$key]["pdt_nums"] = $nums[$key];
	                            $arr_newdata[$i][$key]["pdt_momery"] = sprintf("%0.2f", $nums[$key] * $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["pdt_price"] = sprintf("%0.2f", $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["type"]=4;  	
	                            $arr_newdata[$i][$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($item_info['pdt_id']);	
	                            $arr_newdata[$i][$key]['fc_id'] = $v['fc_id'];				
    				    	}
                        }
                        elseif($v['type'] == 5){//团购商品
                            $arr_newdata[$i]["pdt_rule_name"]='团购商品';
                            $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);	
                            $arr_newdata[$i]['type'] = 5;	
                            $arr_newdata[$i]['pdt_nums'] = $v['num'];	
                            $arr_newdata[$i]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($k,$member['m_id']);
                            $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                            $arr_newdata[$i]["g_name"]="[团购商品]".$arr_newdata[$i]["g_name"];
                        }
                        elseif($v['type'] == 6){//自由搭配商品
                            $nums = $v['num'];
                         //  echo "<pre>";print_r($arr_newdata[$i]);exit;
    				    	foreach($arr_newdata[$i] as $key=>$item_info){
    				    		$arr_newdata[$i][$key]['pdtId'] = $k;
    				    		$arr_newdata[$i][$key]["g_picture"] = getFullPictureWebPath($item_info["g_picture"]);
    				    		$arr_newdata[$i][$key]["g_name"]= !empty($item_info['fr_id']) ? "[自由搭配]".$item_info["fr_name"] : $item_info['g_name'];
                              	$arr_newdata[$i][$key]["f_price"]=empty($item_info['fr_price'])?$item_info['pdt_sale_price']:$item_info['fr_price'];
	                            $arr_newdata[$i][$key]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i][$key]["pdt_sale_price"] - $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["pdt_nums"] = !empty($item_info['fr_id']) ? $nums[$item_info['fr_id']] : 1;
                                $arr_newdata[$i][$key]["pre_price"]=sprintf("%0.2f", $arr_newdata[$i][$key]["pdt_nums"] * $arr_newdata[$i][$key]["pdt_preferential"]);
	                            $arr_newdata[$i][$key]["pdt_rule_name"]=!empty($item_info['fr_id']) ? "自由搭配":"";
	                            $arr_newdata[$i][$key]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($item_info['pdt_id'],$member['m_id']);
	                            
	                            $arr_newdata[$i][$key]["pdt_momery"] = sprintf("%0.2f", $arr_newdata[$i][$key]["pdt_nums"] * $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["pdt_price"] = sprintf("%0.2f", $arr_newdata[$i][$key]["f_price"]);
	                            $arr_newdata[$i][$key]["type"]=6;  	
	                            $arr_newdata[$i][$key]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($item_info['pdt_id']);	
	                            $arr_newdata[$i][$key]['fr_id'] = $item_info['fr_id'];				
    				    	}
                        }else if($v['type'] == 7){
                            $g_stock = D("Spike")->field('sp_number')->where(array('g_id'=>$arr_newdata[$i]['g_id']))->find();
                            $arr_newdata[$i]["pdt_rule_name"]='秒杀商品';
                            $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);	
                            $arr_newdata[$i]['type'] = 7;	
                            $arr_newdata[$i]['pdt_nums'] = $v['num'];	
                            $arr_newdata[$i]['pdt_stock'] = $g_stock['sp_number'];
                            $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                            $arr_newdata[$i]["g_name"]="[秒杀商品]".$arr_newdata[$i]["g_name"];
                        }else if($v['type'] == 8){  //预售商品
                            $g_stock = D("Presale")->field('p_number')->where(array('g_id'=>$arr_newdata[$i]['g_id']))->find();
                            $arr_newdata[$i]["pdt_rule_name"]='预售商品';
                            $arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);	
                            $arr_newdata[$i]['type'] = 8;	
                            $arr_newdata[$i]['pdt_nums'] = $v['num'];	
                            $arr_newdata[$i]['pdt_stock'] = $g_stock['p_number'];
                            $arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
                            $arr_newdata[$i]["g_name"]="[预售商品]".$arr_newdata[$i]["g_name"];
                        }
                        else{//普通商品
                            $min = M("goods_products",C('DB_PREFIX'),'DB_CUSTOM')->field('pdt_min_num')->where(array('pdt_id'=>$v['pdt_id']))->find();
                            $arr_newdata[$i]["f_price"] = $price->getItemPrice($k,$arr_newdata[$i]['pdt_sale_price']);
        					$arr_newdata[$i]["pdt_preferential"]=sprintf("%0.2f",$arr_newdata[$i]["pdt_sale_price"] - $arr_newdata[$i]["f_price"]);                
        					$arr_newdata[$i]["pdt_rule_name"]=$price->getPriceRuleName();
                            $arr_newdata[$i]["rule_info"]=$price->getRuleinfo();
        					$arr_newdata[$i]["pdt_nums"] = $v['num'];
        					$arr_newdata[$i]["pdt_min_num"] = $min['pdt_min_num'];
        					$arr_newdata[$i]['pdt_spec'] = D("GoodsSpec")->getProductsSpec($k);
        					$arr_newdata[$i]["pdt_momery"] = sprintf("%0.2f", $v['num'] * $arr_newdata[$i]["f_price"]);
        					$arr_newdata[$i]["pdt_price"] = sprintf("%0.2f",$arr_newdata[$i]["f_price"]);
                            $arr_newdata[$i]["pdt_per_save_price"] = $arr_newdata[$i]["pdt_market_price"] - $arr_newdata[$i]["f_price"];
                            $arr_newdata[$i]["pdt_save_price"] = sprintf("%0.2f",($arr_newdata[$i]["pdt_market_price"] - $arr_newdata[$i]["f_price"]) * $v['num']);
                            $arr_newdata[$i]['pdt_stock'] = D("GoodsStock")->getProductStockByPdtid($v['pdt_id'],$member['m_id']);
        					$arr_newdata[$i]["type"] = 0;
                        }
    				}
    				if($v['type'] != 4 && $v['type'] != 6){
                    	$arr_newdata[$i]["g_picture"] = getFullPictureWebPath($arr_newdata[$i]["g_picture"]);
    				}
					if($_SESSION['OSS']['GY_QN_ON'] == '1' && $arr_newdata[$i]["g_picture"]!=""){//七牛图片显示
						$arr_newdata[$i]["g_picture"] = D('QnPic')->picToQn($arr_newdata[$i]["g_picture"]);
					}
                    $i++;
            }
            return $arr_newdata;
        }
    }
    

    
    
    
	/**
	 * [检验购物车] (Home/CartAction.class.php中的pageCartList方法)
	 * @param  [type] $cart_data [description]
	 * @return [type]            [description]
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-4
	 */
	private function checkCart($cart_data){
		$obj_shop = D('Shops');
        $obj_goods_spec = D('GoodsSpec');
        $obj_goods = D('Goods');
        $obj_goods_stock = D('GoodsStock');
        $obj_goods_product = D('Products');
        $ary_pic_config = D('SysConfig')->getCfgByModule('THD_FILE_SET');
        if($ary_pic_config['HTTP_FILE_OPEN'] == 1){
            $ary_pic_config['HTTP_FILE_HOST'] = json_decode($ary_pic_config['HTTP_FILE_HOST'],true);
            $host = $ary_pic_config['HTTP_FILE_HOST'][array_rand($ary_pic_config['HTTP_FILE_HOST'])];
        }
		foreach($cart_data as $shop_id=>&$shop) {
            $shop_status = $obj_shop->getStatus($shop_id);
            $shop_name = $obj_shop->getName($shop_id);
            $s_m_id = D('Shops')->where(array('shop_id'=>$shop_id))->getField('m_id');
            $shop_describe = D('MemberSellerInfo')->getInfoByWhere(array('m_id'=>$s_m_id),'msi_describe');
            $shop['shop_name'] = $shop_name;
            $shop['shop_describe'] = $shop_describe;
            foreach ($shop['data']['goods'] as $key=>&$goods) {
            	$goods['disable'] = 0;
                $goods['spec_name'] = $obj_goods_spec->getProductsSpec($goods['pdt_id'],1);
                $goodsBase = $obj_goods_product->getGoodsInfo($goods['pdt_id']);
                $goods['stock'] = 0;
                if($shop_status != 'active') {
                	unset($cart_data[$shop_id]);
                    $pdt['errMsg'] = '店铺已关闭';
                    break;
                } else {
                    if(empty($goodsBase)) {
                        $goods['errMsg'] = '商品不存在';
                    } else {
                        if($goodsBase['g_on_sale'] != 1) {
                            $goods['errMsg'] = '商品已下架';
                        } else if($goodsBase['g_status'] != 1) {
                            $goods['errMsg'] = '商品已删除';
                        } else {
                            //判断库存是否充足
                            $stock = $obj_goods_stock->getRegionStock($goods['g_id'],$goods['pdt_id']);
                            if($stock['ava']  <= 0) {
                                $goods['errMsg'] = '商品缺货';
                            } else {
                                $goods['stock'] = $stock['ava'] ;
                            }
                        }
                        //商品价格信息
                        $goods['sale_price'] = formatFloat($goodsBase['pdt_sale_price']);
                        $goods['market_price'] = formatFloat($goodsBase['pdt_market_price']);
                        //商品成交价
                        $obj_goods_price = new GoodsPrice($goods['g_id'], $goods['pdt_id'], $m_id);
                        $goods['trade_price'] = $obj_goods_price->getUnitPrice();
                        $goods['pdt_sn'] = $goodsBase['pdt_sn'];
                        $goods['g_sn'] = $goodsBase['g_sn'];
                        $goods['g_pic'] = $host.$obj_goods->getPic($goods['g_id']);
                        
                        $goods['g_name'] = $obj_goods->getName($goods['g_id']);
                        $goods['subtotal'] = formatFloat($goods['trade_price']*$goods['num']);
                    }
                }
                if(isset($goods['errMsg'])) {
                    //该项为1是，前台不可以进行勾选提交等操作
                    $goods['disable'] = 1;
                }
                
            }
        }
        return $cart_data;
	}

	/**
	 * [删除购物车商品]
	 * @param [type] $params [description]
	 * @example array(
	 *          'pdt_id' => 规格ID
	 *          'shop_id' => 店铺ID
	 *          'm_id' => 会员ID
	 * );
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-4
	 */
	public function RemoveCart($params){
		$result = D('Cart')->removeFromCart($params['pdt_id'],$params['shop_id'],$params['m_id']);
		if(!$result){
			$this->result['sub_msg'] = '移除购物车失败!';
		}else{
			$this->result['status'] = true;
        	$this->result['code'] = 10106;
        	$this->result['sub_msg'] = '移除购物车成功!';
        	$this->result['info'] = $this->GetCartFormat($params);
		}
		return $this->result;
	}

	/**
	 * [更新购物车商品数量]
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 * @example array(
	 *          'pdt_id' => 规格ID
	 *          'shop_id' => 店铺ID
	 *          'm_id' => 会员ID
	 *          'num' => 修改后的数量
	 * );
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2014-11-4
	 */
	public function updateCartNum($params){
		$result = D('Cart')->updateCartBuyNum($params['pdt_id'],$params['shop_id'],$params['num'],$params['m_id']);
		if(!$result){
			$this->result['sub_msg'] = '更新购物车失败!';
		}else{
			$this->result['status'] = true;
        	$this->result['code'] = 10107;
        	$this->result['sub_msg'] = '更新购物车成功!';
        	$this->result['info'] = $this->GetCartFormat($params);
		}
		return $this->result;
	}

	public function updateCartNumAll($condition,$params){
		$m_id = $condition['m_id'];
		$tag = true;
		foreach($params as $shopid=>$shopdata){
			if(is_array($shopdata['data']['goods']) && !empty($shopdata['data']['goods'])){
				foreach($shopdata['data']['goods'] as $gid=>$goods){
					$result = D('Cart')->updateCartBuyNum($goods['pdt_id'],$goods['shop_id'],$goods['num'],$m_id);
					if(!$result){
						$tag = $result;
						break;
					}
				}
			}
		}
		if(true !== $tag){
			$this->result['sub_msg'] = '修改购物车失败!';
		}else{
			$this->result['status'] = true;
        	$this->result['code'] = 10108;
        	$this->result['sub_msg'] = '修改购物车成功!';
        	$this->result['info'] = $this->GetCartFormat($condition);
		}
		return $this->result;
	}
}