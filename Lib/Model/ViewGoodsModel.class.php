<?php

/**
 * 商品视图相关模型层 Model
 * @package Model
 * @version 7.1
 * @author wangguibin
 * @date 2013-04-01
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ViewGoodsModel extends GyfxModel {

    /**
     * 构造方法
     * @author wangguibin
     * @date 2013-04-01
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取可用的商品品牌
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     */
    public function getBrands($tag) {
        $where = array('gb_status' => 1,'gb_display'=>1);
        if (isset($tag['bid']) && !empty($tag['bid'])) {
            $where["gb_id"] = $tag['bid'];
        }
        if(isset($tag['limit']) && !empty($tag['limit'])){
            $num = $tag['limit'];
        } else if (isset($tag['num']) && !empty($tag['num'])) {
            $num = $tag['num'];
        } else {
            $num = 100;
        }
        if (isset($tag['gbids']) && $tag['gbids']['status'] == 1) {
           if(!empty($tag['gbids']['data']))   $where["gb_id"] = array('in',$tag['gbids']['data']);
           else return array();
        }
        $ary_brand = D('Gyfx')->selectAllCache('goods_brand',$ary_field=null, $where, '`gb_order` DESC',$ary_group=null,$num);
		foreach($ary_brand as $key=>$brand){
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){//七牛图片显示
				$ary_brand[$key]['gb_logo'] = D('QnPic')->picToQn($brand['gb_logo']);
			}else{
				$ary_brand[$key]['gb_logo'] = $brand['gb_logo'];
			}
		}
		//		D('GoodsBrand')->where($where)->order(array('gb_order' => 'desc'))->limit($num)->select();
		return $ary_brand;
    }

    /**
     * 获取可用的商品分组
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-15
     */
    public function getGroups($tag = array()){
        $where = array('gg_status' => 1);
        return D('GoodsGroup')->where($where)->order(array('gg_order' => 'desc'))->limit($num)->select();
    }

    /**
     * 存储树状分级商品分类
     * @var array
     */
    private $goodCates = array();
    
    /**
     * 获取分类下面的商品品牌
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2012-12-14
     */
    public function getBrandsByCatId($cate_id,$is_cache=false) {
        if($is_cache == true){
			$ary_cate = D('Gyfx')->selectOneCache('goods_category',null,array('gc_id'=>$cate_id));
		}else{
			$ary_cate = D('GoodsCategory')->where(array('gc_id'=>$cate_id))->find();
		}
        if($ary_cate['gc_is_parent'] == 1){
            $this->getCateSons($cate_id,$is_cache);
            foreach ($this->goodCates as $cate_info){
                $gc_id .= $cate_info['gc_id'].',';
            }
            $gc_id = trim($gc_id,',');
			//包括当前类目
			$gc_id = $gc_id.','.$cate_id;
        }else{
            $gc_id = $cate_id;
        }
        $ary_where = array('gc_id'=>array('in',$gc_id));
		if($is_cache == true){
			 $ary_gids = D('Gyfx')->selectAllCache('related_goods_category','g_id',$ary_where);
		}else{
			 $ary_gids = D('RelatedGoodsCategory')->field('g_id')->where($ary_where)->select();
		}
		
        if($ary_gids) {
            $ary_gids_temp = array();
            foreach($ary_gids as $val) {
                $ary_gids_temp[] = $val['g_id'];
            }
			if($is_cache == true){
				$ary_gbids = D('Gyfx')->selectAllCache('goods','gb_id',array('g_id' => array('IN',$ary_gids_temp)));
			}else{
				$ary_gbids = D('Goods')->field('gb_id')->where(array('g_id' => array('IN',$ary_gids_temp)))->select();
				
			}
            if($ary_gbids) {
                $ary_gbids_temp = array();
                foreach($ary_gbids as $val) {
                    $ary_gbids_temp[] = $val['gb_id'];
                }
                return $ary_gbids_temp;
            }
            else return null;
        }
        else return null;
    }

    /**
     * 根据类目ID传回所有子类的ID组成的数组
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-09
     * @param int $cid 商品分类ID
     * @return array 返回仅由子分类ID组成的数组
     */
    public function getInfo($cid = null, $is_display = null, $gc_type = 0,$is_cache = false) {
        if($gc_type != 0){
            $array = array();
            $where = array('gc_status' => 1);
            if (empty($is_display)) {
                $where['gc_is_display'] = 1;
            }
            $where['gc_type'] = $gc_type;
			$data = D('Gyfx')->selectAllCache('goods_category','gc_id as cid,gc_parent_id as fid,gc_name as cname,gc_level as clevel,gc_parent_id,gc_is_display,gc_ad_type,gc_is_hot,gc_pic_url,gc_key', $where, array('gc_order' => 'asc'));
			//商品类目Url
            foreach ($data as &$c) {
				//楼层
				if($gc_type == '1'){
					$c['curl'] = U('Home/Products/Index', array('lid' => $c['cid']));
				}else{
					//店铺
					if($gc_type == '2'){
						$c['curl'] = U('Home/Products/Index', array('did' => $c['cid']));
					}else{
						$c['curl'] = U('Home/Products/Index', array('cid' => $c['cid']));
					}
				}
            }
            if (!$data || empty($data))
                return $array;

            //获取商品类目最多支持8级 
            $first = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="0";')));
            foreach ($first as &$cate) {
            	$second = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $cate["cid"] . '";')));
            	foreach ($second as &$subcat) {
            		$third = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $subcat['cid'] . '";')));
            		if ($third){$subcat['sub'] = $third;
	            		foreach ($subcat['sub'] as &$four_cat) {
	            			$four = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $four_cat['cid'] . '";')));
	            			$four_cat['sub'] = $four;
	            		}
            		}
            	}
            	if ($second){$cate['sub'] = $second;}
            }
            $array = $first;
            $this->info = $array;
            return $this->info;
        }
        if (null === $this->info) {
            // 为了提高性能，一次性从列表中读取所有信息
            // 然后程序进行1，2，3级排序
            $array = array();
            $where = array('gc_status' => 1);
            if (empty($is_display)) {
                $where['gc_is_display'] = 1;
            }
            if ($gc_type  === null) {
                $where['gc_type'] = 0;
            }
			/**
            $data = D('GoodsCategory')->where($where)
                            ->field('gc_id as cid,gc_parent_id as fid,gc_name as cname,gc_level as clevel,gc_parent_id,gc_is_display')
                            ->order(array('gc_order' => 'asc'))->select();					
			**/
			if($is_cache == true){
				$data = D('Gyfx')->selectAllCache('goods_category','gc_id as cid,gc_parent_id as fid,gc_name as cname,gc_level as clevel,gc_parent_id,gc_is_display,gc_ad_type,gc_is_hot,gc_pic_url,gc_key', $where, array('gc_order' => 'asc'));	
			}else{
				$data = D('Gyfx')->selectAll('goods_category','gc_id as cid,gc_parent_id as fid,gc_name as cname,gc_level as clevel,gc_parent_id,gc_is_display,gc_ad_type,gc_is_hot,gc_pic_url,gc_key', $where, array('gc_order' => 'asc'));				
			}
			//查询有没有类目促销信息
			foreach($data as $key=>&$cat_val){
				//获取品牌信息
				$brands = D('Gyfx')->selectAllCache('related_goodscategory_brand','gb_id', array('gc_id'=>$cat_val['cid']));
				if(!empty($brands)){
					$bids = array();
					foreach($brands as $brand){
						$bids[] = $brand['gb_id'];
					}
					if(!empty($bids)){
						$ary_brand = D('Gyfx')->selectAllCache('goods_brand','gb_logo,gb_id', array('gb_id'=>array('in',$bids)));
						if(!empty($ary_brand)){
							$cat_val['brand'] = $ary_brand;
						}
					}
				}
				//获取广告图片信息
				$ary_ads = D('Gyfx')->selectAllCache('related_goodscategory_ads','gc_id,ad_url,ad_pic_url,gc_key', array('gc_id'=>$cat_val['cid']),array('sort_order' => 'asc'));
				if(!empty($ary_ads)){
					foreach($ary_ads as &$sub){
						$sub['ad_pic_url'] = D('QnPic')->picToQn($sub['ad_pic_url']);
					}
					$cat_val['ads'] = $ary_ads;
				}
			}
			//商品类目Url
            foreach ($data as &$c) {
				//楼层
				if($gc_type == '1'){
					$c['curl'] = U('Home/Products/Index', array('lid' => $c['cid']));
				}else{
					//店铺
					if($gc_type == '2'){
						$c['curl'] = U('Home/Products/Index', array('did' => $c['cid']));
					}else{
						$c['curl'] = U('Home/Products/Index', array('cid' => $c['cid']));
					}
				}
            }
            if (!$data || empty($data))
                return $array;
            foreach($data as &$val){
                if($val['gc_parent_id']){
					if($is_cache == true){
						 $val['gnums'] = D('Gyfx')->getCountCache('related_goods_category',array('gc_id'=>$val['cid']));
					}else{
						 $val['gnums'] = D('RelatedGoodsCategory')->where(array('gc_id'=>$val['cid']))->count();
					}
                }

            }
            //获取商品类目最多支持8级 
            $first = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="0";')));
            foreach ($first as &$cate) {
            	$second = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $cate["cid"] . '";')));
            	foreach ($second as &$subcat) {
            		$third = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $subcat['cid'] . '";')));
            		if ($third){$subcat['sub'] = $third;
	            		foreach ($subcat['sub'] as &$four_cat) {
	            			$four = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $four_cat['cid'] . '";')));
	            			$four_cat['sub'] = $four;
	            			foreach ($four_cat['sub'] as &$five_cat) {
	            				$five = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $five_cat['cid'] . '";')));
	            				if ($five){$five_cat['sub'] = $five;
	            					foreach ($five_cat['sub'] as &$six_cat) {
	            						$six = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $six_cat['cid'] . '";')));
	            						if ($six){$six_cat['sub'] = $five;
	            							foreach ($six_cat['sub'] as &$seven_cat) {
	            								$seven = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $seven_cat['cid'] . '";')));
	            								if ($seven){$seven_cat['sub'] = $seven; 
	            									foreach ($seven_cat['sub'] as &$eight_cat) {
	            										$eight = array_values(array_filter($data, create_function('$val', 'return $val["gc_parent_id"]=="' . $eight_cat['cid'] . '";')));
	            										if ($eight){$eight_cat['sub'] = $eight;}
	            									}
	            								}
	            							}
	            						}
	            					}
	            				}
	            			}
	            		}
            		}
            	}
            	if ($second){$cate['sub'] = $second;}
            }
            $array = $first;
            $this->info = $array;
        }
        if (null === $cid) {
            return $this->info;
        } else {
            $cids = explode(',', $cid);

            $cate_info = array();
            if (!empty($cids)) {
                foreach ($cids as $key => $cateId) {
                    foreach ($this->info as $cat) {
                    	//获取商品类目最多支持8级 
                        if ($cat['cid'] == $cateId) {$cate_info[$key] = $cat;}
                        if (isset($cat['sub']))     {unset($subcat);foreach ($cat['sub'] as $subcat) {if ($subcat['cid'] == $cateId) {$cate_info[$key] = $subcat;}
                        if (isset($subcat['sub']))  {foreach ($subcat['sub'] as $thirdcat) {if ($thirdcat['cid'] == $cateId) {$cate_info[$key] = $thirdcat;}
                        if (isset($thirdcat['sub'])){foreach ($thirdcat['sub'] as $fourcat) {if ($fourcat['cid'] == $cateId) {$cate_info[$key] = $fourcat;}
                        if (isset($fourcat['sub'])) {foreach ($fourcat['sub'] as $fivecat) {if ($fivecat['cid'] == $cateId) {$cate_info[$key] = $fivecat;}
                        if (isset($fivecat['sub'])) {foreach ($fivecat['sub'] as $sixcat) {if ($sixcat['cid'] == $cateId) {$cate_info[$key] = $sixcat;}
                        if (isset($sixcat['sub']))  {foreach ($sixcat['sub'] as $sevencat) {if ($sevencat['cid'] == $cateId) {$cate_info[$key] = $sevencat;}
                        if (isset($sevencat['sub'])){foreach ($sevencat['sub'] as $eightcat) {if ($eightcat['cid'] == $cateId) {$cate_info[$key] = $eightcat;}}}}}}}}}}}}}}}}}
				return $cate_info;
            }
        }
    }

    /**
     * 获取父类ID
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-07-22
     */
    public function getParentCatesIds($cid, &$pids) {
        $result = D('GoodsCategory')->field('gc_parent_id')->where(array('gc_status' => 1, 'gc_id' => $cid))->find();
        if (!empty($result['gc_parent_id'])) {
            $pids[] = $result['gc_parent_id'];
            $this->getParentCatesIds($result['gc_parent_id'], $pids);
        }
    }

    /**
     * 根据父类传回所有子类的ID组成的数组
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-26
     * @param int $p_id 商品分类ID
     * @param boolean $has_parent 返回的数组中是否包含父类ID本身
     * @return array 返回仅由子分类ID组成的数组
     */
    public function getCatesIds($p_id = 0, $has_parent = true) {
        if ($has_parent && ($p_id > 0)) {
            $return = array();
            $return[] = $p_id;
        } else {
            $return = array();
        }

        $result = $this->getCates($p_id);
        foreach ($result as $v) {
            $return[] = $v['gc_id'];
        }

        return $return;
    }

    /**
     * 获取某分类下全部的商品分类，不传参则返回全部
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     * @param int $p_id 商品分类ID
     * @return array 返回已排序后的子分类数组
     */
    public function getCates($p_id = 0,$is_cache=0) {
        $this->goodCates = array();
        $this->getCateSons($p_id,$is_cache);
        return $this->goodCates;
    }

    /**
     * 根据父类找到全部子类，递归找到全部
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     * @param int $p_id 分类的父级分类ID
     * @return null 不返回任何值，类目树存在私有属性里面
     */
    private function getCateSons($p_id = 0,$is_cache=0) {
		if($is_cache == 1){
			$result = D('Gyfx')->selectAllCache('goods_category',null,array('gc_status' => 1, 'gc_parent_id' => $p_id),array('gc_order' => 'desc'));
		}else{
			$result = D('GoodsCategory')->where(array('gc_status' => 1, 'gc_parent_id' => $p_id))->order(array('gc_order' => 'desc'))->select();		
		}
        if (is_array($result)) {
            foreach ($result as $v) {
                array_push($this->goodCates, $v);
                $this->getCateSons($v['gc_id'],$is_cache);
            }
        }
        return;
    }
    
    /**
     * 根据字符串分类类型，查找当前分类集下分类
     * 
     * @param string 1,2,3,4 分类字符串
     * @return array 一位数组
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function getStringCateArray($string_cid = ''){
        $ary_return_cate_id = array();
        if(false === strpos($string_cid,',')){
            $string_cid = (int)$string_cid;
            $ary_return_cate_id = $this->getCatesIds($string_cid);
        }else{
            $ary_tmp_cid = explode(',',$string_cid);
            foreach ($ary_tmp_cid as $v){
                $ary_return_cate_id = array_merge($ary_return_cate_id,$this->getCatesIds($v));
            }
            $ary_return_cate_id = array_unique($ary_return_cate_id);
        }
        return $ary_return_cate_id;
    }

    /**
     * 获取全部的商品类型，不传参则返回全部
     * @author anguangzhi
     * @date 2012-12-19
     * @return array
     */
    public function getTypes($gt_id, $gt_type = 0) {
        if (isset($gt_id)) {
            $where['gt_id'] = $gt_id;
        }
        if (isset($gt_type)) {
            $where['gt_type'] = $gt_type;
        }
        $where['gt_status'] = 1;
        return D('GoodsType')->where($where)->select();
    }

    /**
     * 商品入库
     * @param  array $ary_goods 需要入库的商品关系表数组
     * $ary_goods_info 商品详细数组，$ary_products 入库货品数组
     * @aurthor listen
     * @date 2013-01-28
     * @return bool
     */
    public function goodsAdd($ary_goods = array(), $ary_goods_info = array(), $ary_products = array()) {
        //判断数组是否为空 如果为空返回false
        $bool_return = false;
        if (empty($ary_goods)) {
            return $bool_return;
            exit;
        }
        //开启事务
        $obj_goods = M('goods', C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_goods->startTrans();
        //商品关系表入库fx_goods
        $int_goods = $obj_goods->add($ary_goods);
        if ($int_goods <= 0) {
            $obj_goods->rollblack();
            return $bool_return;
            exit;
        } else {
            //商品详细表入库fx_goods_info
            if (empty($ary_goods_info)) {
                $obj_goods->rollblack();
                return $bool_return;
                exit;
            }
            $ary_goods_info['g_id'] = $int_goods;
            $bool_goods_info_res = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_goods_info);
            if (!$bool_goods_info_res) {
                $obj_goods->rollblack();
                return $bool_return;
                exit;
            }
        }
        //货品表入库
        if (empty($ary_products)) {
            $obj_goods->rollblack();
            return $bool_return;
            exit;
        }
        $bool_goods_products_res['g_id'] = $int_goods;
        $bool_goods_products_res = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->add($ary_products);
        if (!$bool_goods_products_res) {
            $obj_goods->rollblack();
            return $bool_return;
            exit;
        }
        $bool_return = true;
        $obj_goods->commit();
        return $bool_return;
    }

    /**
     * 根据条件查询商品信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     * @param name,gname,gid,cid,bid,hot,new,order,num,start,pagesize
     * @return array
     * @ modify wanghaoyu 添加商品收藏数
     * 商品ID	gid
     * 商品SN	gsn
     * 上架时间	onsale
     * 下架时间	offsale
     * 商品名称	gname
     * 商品价格	gprice
     * 市场价         gmprice
     * 商品库存	gstock
     * 商品单位	gunit
     * 商品描述	gdesc
     * 商品主图	gpic
     * 总销量	gsales(暂时未处理)
     * 新品标识	gnew
     * 热销标识	ghot
     * 详情页URL	gurl
     * 分页信息	pageinfo
     */
    public function goodList($tag) {
        //根据tag获取缓存key
		if(CI_SN=='yongzhuomaoyi'){//永卓
			$cache_key = json_encode($tag).$_SESSION['Members']['m_id'];
		}else{
			$cache_key = json_encode($tag);
		}
        if($ary_return = getCache($cache_key)){
            return $ary_return;
        }else{
            $data = array();
            $ary_where = array();
            $order_by = '';
            $limit = array();
            //商品ID
            if (!empty($tag['gid'])) {
                $ary_where['g.g_id'] = array('in', $tag['gid']);
            }

            //商品Guid
            if (!empty($tag['erpguid'])) {
                $ary_where['g.erp_guid'] = array('in', $tag['erpguid']);
            }
            
            //商品sn
            if(!empty($tag['gsn'])) {
                if(false === strpos($tag['gsn'],',')){
                    $ary_where['g.g_sn'] = $tag['gsn'];
                    
                }else{
                    $ary_where['g.g_sn'] = array('in',explode(',',$tag['gsn']));
                }
            }
            //商品类型 Add Terry<wanghui@guanyisoft.com> 2013-08-15
            if(!empty($tag['tid'])){
            	$ary_where['g.gt_id'] = $tag['tid'];
            }
            //商品名称
            if (!empty($tag['gname'])) {
            	$temp_arr = array();
				if(empty($tag['tid'])){
            		$goods_type = M('goods_type as `gt` ', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gt_name'=>$tag['gname']))->find();
            		if(!empty($goods_type) && is_array($goods_type)){
            			$temp_arr['g.gt_id'] = $goods_type['gt_id'];
            		}
            	}
                $temp_arr['gi.g_name'] =  array('like', "%" . trim($tag['gname']) . "%");
                $temp_arr['gi.g_keywords'] =  array('like', "%" . trim($tag['gname']) . "%");
				$temp_arr['g.g_sn'] =  trim($tag['gname']);
                $temp_arr['_logic'] = 'or';
                $ary_where['_complex'] = $temp_arr; //组合成 ((g_name like "%关键字%")  OR ( g_keywords like "%关键字"))查询条件
            }
            //分类ID
			
			$gc_ids = array();
            if (!empty($tag['cid'])) {
                $tag['cid'] = htmlspecialchars($tag['cid'],ENT_QUOTES);
				$gc_ids[] = array('exp','in('.implode(',',$this->getStringCateArray($tag['cid'])).')');
            }
			//楼层
            if (!empty($tag['lid'])) {
                $lid = htmlspecialchars($tag['lid'],ENT_QUOTES);
				$gc_ids[] = array('exp','in('.implode(',',$this->getStringCateArray($lid)).')');
            }
			//店铺
            if (!empty($tag['did'])) {
                $did = htmlspecialchars($tag['did'],ENT_QUOTES);
				$gc_ids[] = array('exp','in('.implode(',',$this->getStringCateArray($did)).')');
            }
			//查询分类时，查询当前分类下子分类 edit by Wangguibin
			if(!empty($gc_ids)){
				$ary_where['gc.gc_id'] = $gc_ids;
			}	
            if (!empty($tag['hcid'])) {
                $tag['hcid'] = htmlspecialchars($tag['hcid'],ENT_QUOTES);
                //查询分类时，查询当前分类下子分类 edit by Joe
                $ary_where['gc.gc_id'] = array('in', $this->getStringCateArray($tag['hcid']));
            }
            //品牌ID
//            dump($tag);die;
            if (!empty($tag['bid'])) {
                $tag['bid'] = htmlspecialchars($tag['bid'],ENT_QUOTES);
                $ary_where['g.gb_id'] = array('in', $tag['bid']);
            }
            //是否新品1是0不是
            if (!empty($tag['new'])) {
                $ary_where['g.g_new'] = $tag['new'];
            }
            //是否热销产品1是0不是
            if (!empty($tag['hot'])) {
                $ary_where['g.g_hot'] = $tag['hot'];
            }
            //是否猜您喜欢产品1是0不是
            if (!empty($tag['guess'])) {
                $ary_where['g.g_guess'] = $tag['guess'];
            }            
            //属性值搜索
            $array_related_spec_detail = '';
            if(!empty($tag['path'])){
                //893:0.42,939:124,940:48,941:63,942:风冷,944:3.5
                $arr_paths = explode(",", $tag['path']);
                $array_where_related_spec = array();
                foreach($arr_paths as $val){
                    $ary_goods_path = explode(":", $val);
                    $ary_rel_good_spec_gid = D('RelatedGoodsSpec')
                        ->where(array('gs_id'=>$ary_goods_path[0],'gsd_id'=>$ary_goods_path[1]))
                        ->group('g_id')->getField('g_id',true);
                    $array_where_related_spec[] = implode($ary_rel_good_spec_gid,',');
                }
                 
                $str_sql = "SELECT `g_id` FROM `fx_related_goods_spec` WHERE ";
                foreach ($array_where_related_spec as $key=>$val){
                    if(count($array_where_related_spec) == $key+1){
                        $str_sql .= "`g_id` IN ({$val}) ";
                    }else{
                        $str_sql .= "`g_id` IN ({$val}) AND ";
                    }
                }
                $str_sql .= "group by g_id";
                $array_related_spec_detail = M('')->query($str_sql);
            }
            //价格排序
            if (!empty($tag['startprice'])) {
                $ary_where['gi.g_price'][] = array('EGT', $tag['startprice']);
            }
            if (!empty($tag['endprice'])) {
                $ary_where['gi.g_price'][] = array('ELT', $tag['endprice']);
            }
            //组合商品除外
            $ary_where['g.g_is_combination_goods'] = 0;
            //是否为组合商品
            if (!empty($tag['cg'])) {
                $ary_where['g.g_is_combination_goods'] = $tag['cg'];
            }
            //排序默认时间逆序_new
            // $ary_where['order'] = empty($tag['new'])?'_new':$tag['order'];
            //dump($ary_where);die();
            $tag['order'] = isset($tag['order']) ? $tag['order'] : '';

            switch ($tag['order']) {
                case 'new':
                    $order_by = 'gi.`g_update_time` desc,g.g_order desc';
                    break;
                case '_new':
                    $order_by = 'gi.`g_update_time` asc,g.g_order desc';
                    break;
                case 'price':
                    $order_by = 'gi.`g_price` asc,g.g_order desc';
                    break;
                case '_price':
                    $order_by = 'gi.`g_price` desc,g.g_order desc';
                    break;
                case 'hot':
                    $order_by = 'gi.`g_salenum` asc,g.g_order desc';
                    break;
                case '_hot':
                    $order_by = 'gi.`g_salenum` desc,g.g_order desc';
                    break;
                case 'dis':
                    $order_by = 'gi.`g_discount` desc,g.g_order desc';
                    break;
                case '_dis':
                    $order_by = 'gi.`g_discount` asc,g.g_order desc';
                    break;
                case 'gcom':
                    $order_by = 'gi.`g_com_nums` asc,g.g_order desc';
                    break;
                case '_gcom':
                    $order_by = 'gi.`g_com_nums` desc,g.g_order desc';
                    break;
                default:
//                    $order_by = 'gi.`g_update_time` desc,gi.g_id desc,g.g_order asc';
					if(CI_SN=='yongzhuomaoyi'){
						$order_by=array('g_market_price'=>'desc','g_price'=>'desc');
					}else{
						$order_by = array('g.g_order'=>'desc','gi.`g_update_time`'=>'desc');
					}
            }
            //数量：如果数量不为空则偏移量和每页显示条数无效
            //dump($tag['num']);die();
            if (!empty($tag['num'])) {
                //$limit = '0,'.$tag['num'];
                $limit['start'] = 0;
                $limit['pagesize'] = $tag['num'];
            } else if (!empty($tag['hc_nums'])) {
                $limit['start'] = 0;
                $limit['pagesize'] = $tag['hc_nums'];
            } else {
                if (!empty($tag['start'])) {
                    //$limit = ($tag['start']-1)*$tag['pagesize'].',';
                    $limit['start'] = $tag['start'];
                }
                if (!empty($tag['pagesize'])) {
                    //$limit .= empty($tag['pagesize'])?'20':$tag['pagesize'];
                    $limit['pagesize'] = empty($tag['pagesize']) ? '20' : $tag['pagesize'];
                }
            }
            //偏移量
            //在架
            $ary_where['g_on_sale'] = 1;
            //有效
            $ary_where['g_status'] = 1;

            if (isset($tag['type']) && $tag['type'] == 1) {
                $ary_where['is_exchange'] = 1;
                $ary_where['point'] = array('GT', 0);
            }
            //echo "<pre>";print_r($tag);exit;
    //        //商品类型
    //        if (!empty($tag['tid'])) {
    //            $ary_where['g.gt_id'] = $tag['tid'];
    //        }

            //搜索商品分组
            if(!empty($tag['ggid']) && (int)$tag['ggid'] > 0){
                $ary_where['gg.gg_id'] = $tag['ggid'];
            }
           
            $tag['paged'] = isset($tag['paged']) ? $tag['paged'] : '';
            $data = $this->getGoodsList($ary_where, $order_by, $limit, $tag['paged'],$tag,$array_related_spec_detail);
		    $itemlist = $data['list'];
            $type = $data['type'];
            $spec = isset($data['spec']) ? $data['spec'] : '';
            $config = D('SysConfig')->getCfgByModule('GY_STOCK',1);
    //                echo "<pre>";print_r($config);
            //详情页URL	gurl
            foreach ($itemlist as &$item) {
                $item['gurl'] = U('Home/Products/detail', array('gid' => $item['gid']));
				if($_SESSION['OSS']['GY_OSS_ON'] == '1' || $_SESSION['OSS']['GY_OTHER_ON'] == '1'){
                	$item['gpic'] = '/' . $item['gpic'];
                }else{
                	$item['gpic'] = '/' . ltrim($item['gpic'], '/');
                }
                if(isset($tag['wap']) && $tag['wap']){
                    $item['gurl'] = U('Wap/Products/detail', array('gid' => $item['gid']));
                }
                $item['gpic'] = '/' . ltrim($item['gpic'], '/');
				$item['gpic'] = D('QnPic')->picToQn($item['gpic'],300,300);				
                $item['comment_nums'] = D('GoodsComments')->where(array('g_id'=>$item['gid'],'gcom_verify'=>1,'gcom_status'=>1,'u_id'=>0,'gcom_star_score'=>array('gt',0)))->count();
                $item['gpoint'] = $item['g_point'];
				$item['collect_nums'] = D('CollectGoods')->where(array('g_id'=>$item['gid']))->count();                //$item['gpic'] = '/Public/Uploads/' . CI_SN . '/'.$item['gpic'];
                if (GLOBAL_STOCK) {
                    $sql = "SELECT DISTINCT wt . * FROM `" . C('DB_PREFIX') . "warehouse_stock` AS wt INNER JOIN " . C('DB_PREFIX') . "warehouse_delivery_area AS wda ON wt.w_id = wda.w_id WHERE wt.g_id = '" . $item['gid'] . "' AND cr_id IN (SELECT cr_id FROM `" . C('DB_PREFIX') . "city_region` WHERE `cr_parent_id` = '" . $_SESSION['city']['cr_id'] . "')";
                    $ary_stock = M('', C('DB_PREFIX'), 'DB_CUSTOM')->query($sql);
                    $stock = 0;
                    if (!empty($ary_stock) && is_array($ary_stock)) {
                        foreach ($ary_stock as $val) {
                            $stock +=$val['pdt_stock'];
                        }
                    }
                    if (!empty($config) && is_array($config) && $config['OPEN_STOCK'] == '1') {
                        if ($stock > $config['STOCK_NUM']) {
                            $item['gstock'] = "库存充足";
                        } else if ($stock >= 1 && $stock < $config['STOCK_NUM']) {
                            $item['gstock'] = "库存紧张";
                        } else if ($stock <= 0) {
                            $item['gstock'] = "售罄";
                        }
                    } else {
                        $item['gstock'] = $stock;
                    }
                }
                if (isset($tag['show_name']) && $tag['show_name']) {
                    $item['gname'] = $this->csubstr($item['gname'], 0, $tag['g_nums'], $tag['g_instead']);
                }
				$item['gsn']=addslashes($item['gsn']);
            }
            $return = array('pageinfo' => $data['page'], 'list' => $itemlist,'spec'=>$spec,'type'=>$type, 'pagearr' => $data['pageinfo']);
            writeCache($cache_key,$return);
            return $return;
        }
    }

    /**
     * 截取中文字符串方法
     *
     * @param $str 字符串
     * @param $start 开始位置
     * @param $length 长度
     * @param $instead 超出部分以X代替
     * @charset 字符编码

     * @author Joe<qianyijun@guanyisoft.com>
     * @date 2013-07-27
     */
    public function csubstr($str, $start = 0, $length, $instead, $charset = "utf-8", $suffix = true) {
        if (function_exists("mb_substr")) {
            if (mb_strlen($str, $charset) <= $length) {
                return $str;
            }
            $slice = mb_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            if (count($match[0]) <= $length) {
                return $str;
            }
            $slice = join("", array_slice($match[0], $start, $length));
        }
        if ($suffix)
            return $slice . $instead;
        return $slice;
    }

    /**
     * 官网商品列表页
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-3-28
     */
    public function getGoodsList($ary_where, $order_by, $limit, $is_page,$tag,$array_related_spec_detail = array()) {
		$member = session('Members');
        $obj = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        //join查询
        $join_where = array();
        $join_where[] = '`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)';
        //查询字段
        $ary_fields = 'distinct(g.g_id) as gid,gi.`g_description` AS `gdescription`,g.gt_id,gi.`g_market_price` as `maprice`,g.`g_is_prescription_rugs` AS g_is_pres,g_sn as gsn,g_on_sale_time as onsale,g_off_sale_time as offsale
	                             ,g_name as gname,g_price as gprice,g_stock as gstock,g_unit as gunit
	                             ,g_picture as gpic,g_discount as gdiscount,g_new as gnew,g_hot as ghot,g_salenum as gsales,gi.`point` AS g_point,gi.`g_picture` as g_picture,gi.g_remark as remark,gi.g_custom_field_1 as field1,gi.g_custom_field_2 as field2,gi.g_custom_field_3 as field3,gi.g_custom_field_4 as field4,gi.g_custom_field_5 as field5';
        //品牌查询
        if (!empty($tag['bid'])) {
        	$join_where[] = '`fx_goods_brand` `gb` on(`g`.`gb_id` = `gb`.`gb_id`)';	
        }
        //属性值搜索
        $join_where[] = '`fx_goods_type` `gt` on(`g`.`gt_id` = `gt`.`gt_id`)';
        if(!empty($tag['path'])){
            if(is_array($array_related_spec_detail) && !empty($array_related_spec_detail)){
				$ary_rsd_tmp_id=array();
                foreach ($array_related_spec_detail as $rsd_id){
					$ary_rsd_tmp_id []= $rsd_id['g_id'];
                    $rsd_tmp_id .= $rsd_id['g_id'].',';
                    
                }
                $ary_where['g.g_id'] = array('in',trim($rsd_tmp_id,','));
            }else{
                if(empty($tag['tid'])){
                    if(!empty($goods_type) && is_array($goods_type)){
                        $data['type'] = M('goods_type as `gt` ', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gt_id'=>array('IN',  implode(",", $goods_type))))->select();
                    }
                
                }else{
                    //获取到商品类型用到的所有属性
                    $modules = new Model();
                    $ary_spec = $modules->query("SELECT * FROM ".C('DB_PREFIX')."goods_spec where gs_input_type='2' AND gs_is_search='1' AND gs_id in (select gs_id from ".C('DB_PREFIX')."related_goods_type_spec WHERE gt_id='".$tag['tid']."');");
                    //echo "<pre>";print_r($ary_spec);exit;
                    $spec = array();
                    if(!empty($ary_spec) && is_array($ary_spec)){
                        foreach($ary_spec as $keys=>$vals){
                            $array_tmp_cond = array("gs_id"=>$vals["gs_id"],'gs_is_sale_spec'=>0);
                            $array_tmp_value = D("RelatedGoodsSpec")->where($array_tmp_cond)->group('gsd_aliases')->order("gsd_aliases ASC")->select();
                         // echo "<pre>";print_r($array_tmp_value);exit;
                            $spec[$vals['gs_id']] = $vals;
                            $spec[$vals['gs_id']]['specs'] = $array_tmp_value;
                           // $spec[$vals['gs_id']]['specs'] = M('related_goods_spec', C('DB_PREFIX'), 'DB_CUSTOM')->Distinct(true)->field("gsd_aliases,gs_id,gsd_id")->where(array('gsd_id'=>array('NEQ','0'),'gs_id'=>$vals['gs_id'],'g_id'=>array('IN',  implode(",", $goods_id))))->order("gsd_aliases ASC")->select();
                          //  echo "<pre>";print_r(M('related_goods_spec', C('DB_PREFIX'), 'DB_CUSTOM')->getLastSql());
                            if(empty($spec[$vals['gs_id']]['specs'])){
                                unset($spec[$vals['gs_id']]);
                            }
                        }
                    }
                    $data['spec'] = $spec;
                }
                return $data;
            }
        	//
        	//$join_where[] = '`fx_related_goods_type_spec` `rgts` on(`rgts`.`gt_id` = `gt`.`gt_id`)';
            //    $join_where[] = '`fx_related_goods_spec` `rgs` on(`rgs`.`gs_id` = `rgts`.`gs_id`)';	
        }
        if(!empty($tag['cid']) || !empty($tag['hcid']) || !empty($tag['lid']) || !empty($tag['did'])){
        	$ary_fields .=',GROUP_CONCAT(gc.gc_id) as gc_id';
        	$join_where[] = '`fx_related_goods_category` `rgc` on(`g`.`g_id` = `rgc`.`g_id`)';
        	$join_where[] = '`fx_goods_category` `gc` on(`rgc`.`gc_id` = `gc`.`gc_id`)';
        }
        //关联商品分组查询
        if(!empty($tag['ggid'])){
        	$join_where[] = '`fx_related_goods_group` `rgg` on(`g`.`g_id` = `rgg`.`g_id`)';
        	$join_where[] = '`fx_goods_group` `gg` on(`gg`.`gg_id` = `rgg`.`gg_id`)';
        }
		//授权线判断
		if(CI_SN=='yongzhuomaoyi'){//永卓分销 商品授权线
			$ary_auth_ids= D('RelatedAuthorizeMember')->field('al_id')->where(array('m_id'=>$member['m_id']))->select();
		}
		if(!empty($ary_auth_ids)){
			foreach($ary_auth_ids as $key_auth=>$value_auth){
				$str_auth_ids .=$value_auth['al_id'].',';
			}
			$str_auth_ids = substr($str_auth_ids,0,strlen($str_auth_ids)-1);
			$ra_where['al_id']  = array('in',$str_auth_ids);
			$ary_auth_data= D('RelatedAuthorize')->field('ra_gb_id,ra_gc_id,ra_gp_id')->where($ra_where)->select();
			if(!empty($ary_auth_data)){
				$str_goods_brand_ids='';
				$str_goods_group_ids='';
				$str_goods_category_ids='';
				$str_goods_ids='';
				$ary_goods_ids=array();
				foreach($ary_auth_data as $key=>$val){
					if($val['ra_gb_id']>0){
						$str_goods_brand_ids .=$val['ra_gb_id'].',';
					}
					if($val['ra_gp_id']>0){
						$str_goods_group_ids .=$val['ra_gp_id'].',';
					}
					if($val['ra_gc_id']>0){
						$str_goods_category_ids .=$val['ra_gc_id'].',';
					}
				}
				$str_goods_brand_ids = trim($str_goods_brand_ids,',');
				$str_goods_group_ids = trim($str_goods_group_ids,',');
				$str_goods_category_ids = trim($str_goods_category_ids,',');
				
				if($str_goods_category_ids!=''){
					$tmp_cate_sql="select g_id from fx_related_goods_category where gc_id in(".$str_goods_category_ids.")";
				}
				if($str_goods_group_ids!=''){
					$tmp_group_sql="select g_id from fx_related_goods_group where gg_id in(".$str_goods_group_ids.")";
				}
				if($str_goods_brand_ids!=''){
					$tmp_brand_sql="select g_id from fx_goods where gb_id in(".$str_goods_brand_ids.")";
				}
				
				$sql ="select * from (";
				if($tmp_cate_sql!=''){
					$sql.=$tmp_cate_sql;
				}
				if($tmp_group_sql!='' && $tmp_cate_sql!=''){
					$sql.=' union '.$tmp_cate_sql;
				}else{
					$sql.=$tmp_group_sql;
				}
				
				if($tmp_brand_sql!='' && $tmp_group_sql!=''){
					$sql.=' union '.$tmp_brand_sql;
				}else{
					if($tmp_cate_sql!='' && $tmp_group_sql!=''){
						$sql.=' union '.$tmp_brand_sql;
					}else{
						$sql.=$tmp_brand_sql;
					}
				}
				$sql.=') as tmp group by g_id';
				$ary_res = M('', C('DB_PREFIX'), 'DB_CUSTOM')->query($sql);
				
				if(!empty($ary_res)){
					foreach($ary_res as $k=>$v){
						$ary_goods_ids[] =$v['g_id'];
					}
					if(!empty($ary_rsd_tmp_id)){
						$ary_goods_ids = array_intersect($ary_rsd_tmp_id,$ary_goods_ids);
					}
					$str_goods_ids = implode(",",$ary_goods_ids);
					$ary_where['g.g_id'] = array('in',$str_goods_ids);
				}
			}
		}
		//no_paged为1是不搜索分页信息
		if(empty($tag['no_paged'])){
	        //统计数量
			$count = $obj->join($join_where)->where($ary_where)->count('distinct(g.g_id)');     
			//echo $obj->getLastSql();exit;
			C('VAR_PAGE', 'start');
			$obj_page = new Pager($count, $limit['pagesize']);
			$data['page'] = $obj_page->show();
			$data['pageinfo'] = $obj_page->showArr();	
		}
		if(empty($limit['start'])){
			$limit['start'] = 1;
		}
		if(empty($limit['pagesize'])){
			$limit['pagesize'] = $obj_page->listRows;
		}
        $goodsSpec = D('GoodsSpec');
        $goods_type = array();
        $goods_id = array();
        if (empty($is_page)) {
            $data['list'] = $obj->where($ary_where)
                    ->field($ary_fields)
                    ->join($join_where)
                    ->order($order_by)
                    ->group('`gi`.g_id')
                    ->limit(($limit['start']-1)*$limit['pagesize'],$limit['pagesize'])            
                    ->select();//echo "<pre>";print_r(M('')->getLastSql());exit;
            //首页不搜索货品
            //if($_SERVER['REQUEST_URI'] !='/' && $_SERVER['REQUEST_URI'] !='/Home/Index/index'){
	            $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
	            $stock = new GoodsStockModel($_SESSION['Members']['m_id']);
	            if(!empty($data['list']) && is_array($data['list'])){
				           					                        
	                $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods');
	                
	                foreach($data['list'] as $keylist=>&$vallist){
						$vallist['gs_price'] = $vallist['gprice']; 
						//七牛图片显示
						$vallist['g_picture'] =D('QnPic')->picToQn($vallist['g_picture']);
						//no_spec为1是不搜索属性信息
						if(empty($tag['no_spec'])){
						   $ary_pdt = $products->field($ary_product_feild)->where(array("g_id"=>$vallist['gid'],"pdt_status"=>'1'))->select();
                            $skus = array();
							$goods_type[] = $data['list'][$keylist]['gt_id'];
							$goods_id[] = $data['list'][$keylist]['gid'];
							foreach ($ary_pdt as $keypdt => $valpdt) {
								$specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
								if (!empty($specInfo['color'])) {
									if (!empty($specInfo['color'][1])) {
										$skus[$specInfo['color'][0]][] = $specInfo['color'][1];
									}
								}
								//如果会员为登录状态，优先取一口价->会员等级价-
								if(isset($_SESSION['Members']['m_id'])){
									$vallist["products"]['pdt_sale_price'] = D("Price")->getItemPrice($valpdt['pdt_id']);
									//如果是单规格商品，把商品价格也一并替换掉
									if(isset($int_num) && $int_num ==1){
										$ary_goods['g_price'] = $ary_pdt[$keypdt]['pdt_sale_price'];
									}

								}
								if (!empty($specInfo['size'])) {
									if (!empty($specInfo['size'][1])) {
										$skus[$specInfo['size'][0]][] = $specInfo['size'][1];
									}
								}
                                $vallist["pdt_market_price"] = $valpdt['pdt_market_price'];
								if(empty($specInfo['spec_name'])){
									$vallist["pdt_id"] = $valpdt['pdt_id'];
                                                                        if(isset($_SESSION['Members']['m_id'])){
                                                                            $vallist["pdt_stock"] = $stock->getProductStockByPdtid($valpdt['pdt_id'],$_SESSION['Members']['m_id']);
                                                                        }else{
                                                                            $vallist["pdt_stock"] = $stock->getProductStockByPdtid($valpdt['pdt_id']);
                                                                        }
                                    if(isset($_SESSION['Members']['m_id'])){
                                        $vallist["gprice"] = D("Price")->getItemPrice($valpdt['pdt_id']);
                                    }
								}
								$vallist["products"]['specName'] = $specInfo['spec_name'];
								$vallist["products"]['skuName'] = $specInfo['sku_name'];
							}
							foreach ($skus as $key => &$sku) {
								$skus[$key] = array_unique($sku);
							}
							if (!empty($skus)) {
								$vallist['skuNames'] = $skus;
							}	
							//商品价格
							if($vallist["products"]['pdt_sale_price']){
								$vallist['gprice'] = $vallist["products"]['pdt_sale_price'];								
							}
						}
	                    //授权线判断是否允许购买
	                    $vallist['authorize'] = true;
	                    if (!empty($member['m_id'])) {
	                        $vallist['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $vallist['gid']);
							//未授权的商品不显示
							if(empty($vallist['authorize'])){
								unset($data['list'][$keylist]);
							}
	                    }
	                }
	            }           	
            //}
        }
		$goods_type = array_unique($goods_type);
        if(empty($tag['tid'])){
            if(!empty($goods_type) && is_array($goods_type)){
                $data['type'] = M('goods_type as `gt` ', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gt_id'=>array('IN',  implode(",", $goods_type))))->select();
            }
        }else{
            //获取到商品类型用到的所有属性
			//文本框和下拉搜索
            //$ary_spec = M('')->query("SELECT * FROM ".C('DB_PREFIX')."goods_spec where gs_input_type!='3' AND gs_is_search='1' AND gs_id in (select gs_id from ".C('DB_PREFIX')."related_goods_type_spec WHERE gt_id='".$tag['tid']."');");
			//只下拉
			$ary_spec = M('')->query("SELECT * FROM ".C('DB_PREFIX')."goods_spec where gs_input_type='2' AND gs_is_search='1' AND gs_id in (select gs_id from ".C('DB_PREFIX')."related_goods_type_spec WHERE gt_id='".$tag['tid']."');");
			$spec = array();
            if(!empty($ary_spec) && is_array($ary_spec)){
                foreach($ary_spec as $keys=>$vals){
                    $array_tmp_cond = array("gsd.gs_id"=>$vals["gs_id"],'rgs.gs_is_sale_spec'=>0,'gsd.gsd_status'=>1);
                    $array_tmp_value = M('goods_spec_detail as `gsd`', C('DB_PREFIX'), 'DB_CUSTOM')
                                       ->join(C('DB_PREFIX').'related_goods_spec as `rgs` on rgs.gsd_id=gsd.gsd_id')
                                       ->where($array_tmp_cond)
                                       ->group('gsd.gsd_value')
                                       ->order('gsd.gsd_value asc')
                                       ->select();
                    $spec[$vals['gs_id']] = $vals;
                    $spec[$vals['gs_id']]['specs'] = $array_tmp_value;
                    if(empty($spec[$vals['gs_id']]['specs'])){
                        unset($spec[$vals['gs_id']]);
                    }
                }
            }
            $data['spec'] = $spec;
        }
        return $data;
    }

    /**
     * 根据条件查询商品信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-03-28
     * @param name,gid
     * @return array
     * 商品ID	gid
     * 商品SN	gsn
     * 上架时间	onsale
     * 下架时间	offsale
     * 商品名称	gname
     * 商品价格	gprice
     * 商品库存	gstock
     * 商品单位	gunit
     * 商品描述	gdesc
     * 商品主图	gpic
     * 商品全部图片	gpics
     * 新品标识	gnew
     * 热销标识	ghot
     * 规格列表	gurl
     * 货品列表	skus
     * 商品分类ID	cid
     * 商品分类名称	cname
     * 商品品牌ID	bid
     * 商品品牌名称	bname
     */
    public function goodDetail($tag,$is_cache = false) {
        $data = array();
        $goods = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        $where = array();
		$condition = array();
        $where['g.g_id'] = array('eq', $tag['gid']);
        //$where['g.erp_guid'] = array('eq', $tag['erpguid']);
        //$where['_logic'] = 'or';
		//$condition['_complex'] = $where;
		//$condition['g.g_on_sale'] = array('neq', '2');
		$condition['g.g_id'] = array('eq', $tag['gid']);
		$condition['g.g_on_sale'] =array('eq',1);
		$where['g.g_on_sale'] = array('eq',1);
        $goodsSpec = D('GoodsSpec');
        
        //库存提示
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK',1);
        if ((!empty($stock_data['USER_TYPE']) || $stock_data['USER_TYPE'] == '0') && $stock_data['OPEN_STOCK'] == 1) {
            $stock_data['level'] = true;
        }
		$obj_query = $goods
							->where($condition)
							->field('g.g_id,g.gt_id,g.g_sn,g.g_on_sale_time,g_salenum,g.g_on_sale,
									`g`.`g_is_prescription_rugs` AS `g_is_pres`,
									`g`.g_off_sale_time,
									gi.g_name,gi.g_price,gi.g_stock,gi.g_unit,gi.g_tax_rate,
									gi.g_remark,gi.g_desc,gi.g_phone_desc,gi.g_auth,g_picture,gi.gifts_point,
									gi.g_custom_field_1 as field1,gi.g_custom_field_2 as field2,gi.g_custom_field_3 as field3,gi.g_custom_field_4 as field4,gi.g_custom_field_5 as field5,
									g_new,g_hot,`gc`.gc_id,gc_name,`g`.`gb_id` AS `gb_id`,gb_name')
							->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)')
							->join('`fx_goods_brand` `gb` on(`g`.`gb_id` = `gb`.`gb_id`)')
							->join('`fx_goods_type` `gt` on(`g`.`gt_id` = `gt`.`gt_id`)')
							->join('`fx_related_goods_category` `rgc` on(`g`.`g_id` = `rgc`.`g_id`)')
							->join('`fx_goods_category` `gc` on(`rgc`.`gc_id` = `gc`.`gc_id`)');
                            
        if($is_cache == true){
			$ary_goods = D('Gyfx')->queryCache($obj_query,'find',600);
		}else{
			$ary_goods = $obj_query->find();			
		}
        $ary_pic = array();
        if (!empty($ary_goods) && is_array($ary_goods)) {
			if(empty($tag['nosku'])){
				$ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods', 'pdt_min_num');
				$where = array();
				$where['g_id'] = $ary_goods['g_id'];
				$where['pdt_status'] = '1';
				//$count = $products->where($where)->count();
				if($is_cache == true){
					$count = D('Gyfx')->getCountCache('goods_products',$where);
				}else{
					$count = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->count();
				}			
				$obj_page = new Page($count, 10);
				$page = $obj_page->show();
				$ary_pdt = $products->field($ary_product_feild)->where($where)->limit($obj_page->firstRow . ',' . 150)->select();				
			}
            $int_num = count($ary_pdt);
            if (!empty($ary_pdt) && is_array($ary_pdt)) {
                $skus = array();
                $ary_zhgoods = array();
                $zhgoods = M('releted_combination_goods as `rcg` ', C('DB_PREFIX'), 'DB_CUSTOM');
                $stock_i = '';
				if($is_cache == 1){
					$obj_price = new PriceModel($_SESSION['Members']['m_id'],1);
				}else{
					$obj_price = new PriceModel($_SESSION['Members']['m_id']);
				}
                foreach ($ary_pdt as $keypdt => $valpdt) {
                    //通过pdt_id获取关联的组合商品信息 add by hcaijin 2013-07-25
					$zhgoods_query =  $zhgoods
                            ->where(array('rcg.releted_pdt_id' => $valpdt['pdt_id']))
                            ->field('`g`.`g_id`,rcg.pdt_id,`g`.`g_sn`,`gi`.`g_name`,`rcg`.`com_id`,`rcg`.`releted_pdt_sn`')
                            ->join('`fx_goods` `g` on(`g`.`g_id` = `rcg`.`g_id`)')
                            ->join('`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)');
					if($is_cache == true){
						$ary_zhgoods[] = D('Gyfx')->queryCache($zhgoods_query,'find',800);
					}else{
						$ary_zhgoods[] = $zhgoods_query->find();
					}	
                    $ary_pdt[$keypdt]['zhgoods'] = $ary_zhgoods;
                    //获取其他属性
                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id'],true);
                    if (!empty($specInfo['color'])) {
                        if (!empty($specInfo['color'][1])) {
                            $skus[$specInfo['color'][0]][] = $specInfo['color'][1];
                        }
                    }
                    //如果会员为登录状态，优先取一口价->会员等级价-
                    if(isset($_SESSION['Members']['m_id'])){
                        $ary_pdt[$keypdt]['pdt_sale_price'] = $obj_price->getItemPrice($valpdt['pdt_id']);
                        //如果是单规格商品，把商品价格也一并替换掉
                        if($int_num ==1){
                            $ary_goods['g_price'] = $ary_pdt[$keypdt]['pdt_sale_price'];
                        }
                        
                    }
                    
                    if (!empty($specInfo['size'])) {
                        if (!empty($specInfo['size'][1])) {
                            $skus[$specInfo['size'][0]][] = $specInfo['size'][1];
                        }
                    }
                    $ary_pdt[$keypdt]['specName'] = $specInfo['spec_name'];
                    $ary_pdt[$keypdt]['skuName'] = $specInfo['sku_name'];
                    $stock_i += $valpdt['pdt_stock'];
                    if($stock_data['level']) {
                        $ary_pdt[$keypdt]['stock_num'] = $stock_data['STOCK_NUM'];
                    }else{
                        $ary_pdt[$keypdt]['stock_num'] = 30;
                    }
                    
                }
            }
            $ary_goods['g_stock'] = $stock_i;
            
            foreach ($skus as $key => &$sku) {
                $skus[$key] = array_unique($sku);
            }
            if ("" != $ary_goods['g_picture']) {
                if($_SESSION['OSS']['GY_OSS_ON'] == '1' || $_SESSION['OSS']['GY_OTHER_ON'] == '1'){
            		$ary_pic[]['gp_picture'] = $ary_goods['g_picture'];
            	}
				else{
            		$ary_pic[]['gp_picture'] = getFullPictureWebPath($ary_goods['g_picture']);
            	}
            }
			if($is_cache == true){
				 $ary_picresult = D('Gyfx')->selectAllCache('goods_pictures',null,array('g_id' => $ary_goods['g_id']),null,null,null,600);
			}else{
				 $ary_picresult = D("GoodsPictures")->where(array('g_id' => $ary_goods['g_id']))->select();
			}
            if (!empty($ary_picresult) && is_array($ary_picresult)) {
                foreach ($ary_picresult as $kypic => $vlpic) {
                    $ary_pic[] = $vlpic;
                }
            }
            if (!empty($skus)) {
                $data['skuNames'] = $skus;
            }else{
                //第一步：验证是否存在规格存在商品
                $spec_combination = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM');
                if($is_cache == true){
                    $sc_id = D("Gyfx")->selectOneCache("releted_spec_combination","sc_id",array('rsc_rel_good_id'=>$ary_goods['g_id']));
                    $scg_status = D("Gyfx")->selectOneCache("spec_combination","scg_status",array('scg_id'=>$sc_id));
                }else{
                    $sc_id = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rsc_rel_good_id'=>$ary_goods['g_id']))->getField('sc_id');
                    $scg_status = M('spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('scg_id'=>$sc_id))->getField('scg_status');
                }
                if(isset($sc_id) && $scg_status == 1){
                    //获取相关商品id
                     if($is_cache == true){
                        $sp_com_g_id = D("Gyfx")->selectAllCache("releted_spec_combination","rsc_rel_good_id as gid",array('sc_id'=>$sc_id),null,"rsc_rel_good_id");
                    }else{
                        $sp_com_g_id = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->field('rsc_rel_good_id as gid')->where(array('sc_id'=>$sc_id))->group('rsc_rel_good_id')->select();
                    }                   
                    foreach ($sp_com_g_id as $val){
						if($is_cache == true){
                            $array_spec_val = D("Gyfx")->selectAllCache("releted_spec_combination","*",array('rsc_rel_good_id'=>$val['gid']));
                        }else{
                            $array_spec_val = M('releted_spec_combination',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rsc_rel_good_id'=>$val['gid']))->select();
                        }						
                        foreach ($array_spec_val as $sv_key){
                            if($val['gid'] == $ary_goods['g_id']){
                                $goods_com_spec[$sv_key['rsc_spec_name']] = $sv_key['rsc_spec_detail'];
                            }
                            $data_val .= $sv_key['rsc_spec_name'].":".$sv_key['rsc_spec_detail'].';';
                            if(!isset($skus[$sv_key['rsc_spec_name']])){
                                $skus[$sv_key['rsc_spec_name']] = array($sv_key['rsc_spec_detail']);
                            }else{
                                $skus[$sv_key['rsc_spec_name']] = array_merge($skus[$sv_key['rsc_spec_name']],array($sv_key['rsc_spec_detail']));
                            }
                            $skus[$sv_key['rsc_spec_name']] = array_unique($skus[$sv_key['rsc_spec_name']]);
                            sort($skus[$sv_key['rsc_spec_name']]);
                        }
                        $data_val = rtrim($data_val,';');
                        $goods_url[$data_val] = '/Home/Products/detail/gid/'.$val['gid'];
                        unset($data_val);
                    }
                    $data['goods_url'] = $goods_url;
                    $data['goods_spec_name'] = $goods_com_spec;
                    $data['specName'] = $skus;
                }//echo "<pre>";print_r($data);exit;
            
            }
        }
		
        if (!empty($ary_goods['gt_id']) && $tag['showunsale'] == 1) {
            //获取当前商品类型关联的所有属性ID，用于后面查询商品扩展和销售属性使用
            if($is_cache == true){
                $result_spec_ids = D("Gyfx")->selectAllCache("related_goods_type_spec",'gs_id',array("gt_id" => $ary_goods['gt_id']));
                if(!empty($result_spec_ids)) {
                    foreach ($result_spec_ids as $val){
                        $array_spec_ids[] = $val['gs_id'];
                    }
                }
            }else{
                $array_spec_ids = D("RelatedGoodsTypeSpec")->where(array("gt_id" => $ary_goods['gt_id']))->getField("gs_id", true);
            }			
            //获取商品资料的扩展属性资料，并传递到前台模板
            $array_fetch_unsale_cond = array("gs_id" => array("IN", $array_spec_ids), "gs_is_sale_spec" => 0);
            if($is_cache == true){
                $array_unsale_spec = D("Gyfx")->selectAllCache("goods_spec","*",$array_fetch_unsale_cond);
            }else{
                $array_unsale_spec = D("GoodsSpec")->where($array_fetch_unsale_cond)->select();
            }
            foreach ($array_unsale_spec as $key => $val) {
                $array_tmp_cond = array("gs_id" => $val["gs_id"], 'g_id' => $ary_goods['g_id'], 'gs_is_sale_spec' => 0);
                //$array_tmp_value = D("RelatedGoodsSpec")->where($array_tmp_cond)->find();
                if($is_cache == true){
                    $array_tmp_value = D("Gyfx")->selectOneCache("related_goods_spec","*",$array_tmp_cond);
                }else{
                    $array_tmp_value = D("RelatedGoodsSpec")->where($array_tmp_cond)->find();
                }				
                $array_unsale_spec[$key]["gsd_id"] = 0;
                $array_unsale_spec[$key]["gsd_aliases"] = "";
                if (is_array($array_tmp_value) && !empty($array_tmp_value)) {
                    $array_unsale_spec[$key]["gsd_id"] = $array_tmp_value["gsd_id"];
                    $array_unsale_spec[$key]["gsd_aliases"] = $array_tmp_value["gsd_aliases"];
                }
                //如果此属性是通过下拉选框方式取值，则这里需要获取这个属性的所有属性值
                if (2 == $val["gs_input_type"]) {
                    $array_unsale_spec[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id" => $val["gs_id"]))->order(array("gsd_order" => "asc"))->select();
                    if($is_cache == true){
                        $array_unsale_spec[$key]["spec_detail"] = D("Gyfx")->selectAllCache("goods_spec_detail","*",array("gs_id" => $val["gs_id"]),array("gsd_order" => "asc"));
                    }else{
                        $array_unsale_spec[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id" => $val["gs_id"]))->order(array("gsd_order" => "asc"))->select();
                    }					
                }
            }
            $data['unsaleSpec'] = $array_unsale_spec;
        }
        
        //自由推荐搭配
		if($tag['showcoolgoods'] == 1){
			//$combination = D("FreeCollocation");
			$now = date('Y-m-d H:i:00');
			if($is_cache){
				$ary_free_coll_1 = D("Gyfx")->selectAllCache("free_collocation","*",array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1));
			}else{
				$ary_free_coll_1 = D("FreeCollocation")->where(array('fc_start_time'=>'0000-00-00 00:00:00','fc_status'=>1))->select();
			}
			if(empty($array_gid)){
				//查找限制时间的
				$array_where['fc_start_time'] = array('egt',$now);
				$array_where['fc_end_time'] = array('elt',$now);
				$array_where['fc_status'] = 1;
				//$ary_free_coll_2 = $combination->where($array_where)->select();
				if($is_cache == true){
					$ary_free_coll_2 = D("Gyfx")->selectAllCache("free_collocation","*",$array_where,null,null,null,60);
				}else{
					$ary_free_coll_2 = D("FreeCollocation")->where($array_where)->select();
				}				
				if(!empty($ary_free_coll_2)){
					foreach ($ary_free_coll_2 as $key_2=>$val_2){
						$ary_tmp_g_id = explode(',',$val_2['fc_related_good_id']);
						if(in_array($ary_goods['g_id'],$ary_tmp_g_id)){
							$array_gid = $ary_tmp_g_id;
						}
					}
				}
			}
			if(!empty($array_gid)){
				foreach ($array_gid as $k=>$v){
					//获取商品基本信息
					$field = 'g_id as gid,g_name as gname,g_price as gprice,g_stock as gstock,g_picture as gpic,g_collocation_price as gcoll_price';
					//$coll_goods = D('GoodsInfo')->field($field)->where(array('g_id'=>$v))->find();
					if($is_cache){
						$coll_goods = D("Gyfx")->selectOneCache("goods_info",$field,array('g_id'=>$v));
					}else{
						$coll_goods = D('GoodsInfo')->field($field)->where(array('g_id'=>$v))->find();
					}					
					$coll_goods['gpic'] = D('QnPic')->picToQn($coll_goods['gpic']);
					$coll_goods['save_price'] = $coll_goods['gprice'] - $coll_goods['gcoll_price'];
					if($coll_goods['gid'] == $ary_goods['g_id']){
						$this_goods = $coll_goods;
					}else{
						$array_free_goods[$k] = $coll_goods;
					}
				}
				$data['this_coll'] = $this_goods;
				$data['coll_goods'] = $array_free_goods;
			}			
		}

        //授权线判断是否允许购买
        $data['authorize'] = true;
        $member = session('Members');
        if (!empty($member['m_id'])) {
            $data['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $ary_goods['g_id'],1);
			//未授权的商品不显示
			if(empty($data['authorize'])){
				return array();
			}
        }
        //放大镜是否开启
        $sys_set_data = D('SysConfig')->getCfgByModule('GY_IMAGEWATER',1);
        $data['magnifier_on'] = $sys_set_data['MAGNIFIER_ON'];
        if($data['magnifier_on']){
            $data['magnifier_pic_width'] =$sys_set_data['MAGNIFIER_PIC_WIDTH'];
            $data['magnifier_pic_height'] =$sys_set_data['MAGNIFIER_PIC_HEIGHT'];
        }
        $data['thumb_pic_width'] =$sys_set_data['THUMB_PIC_WIDTH'];
        $data['thumb_pic_height'] =$sys_set_data['THUMB_PIC_HEIGHT'];
        
		//获取当前商品收藏人数
		//if($tag['showcollectnum'] == 1){
			//$coll_info = M('collect_goods')->where(array('g_id'=>$ary_goods['g_id']))->field('count(*) as num')->find();
			$coll_info = D('Gyfx')->selectOneCache('collect_goods','count(*) as num', array('g_id'=>$ary_goods['g_id']), $ary_order=null,300);			
			$coll_nums = $coll_info['num'];
		//}
        $data['gid'] = $ary_goods['g_id'];
        $data['gsn'] = $ary_goods['g_sn'];
        $data['gonsale'] = $ary_goods['g_on_sale'];
        $data['onsale'] = $ary_goods['g_on_sale_time'];
        $data['offsale'] = $ary_goods['g_off_sale_time'];
        $data['gname'] = $ary_goods['g_name'];
        $data['gprice'] = $ary_goods['g_price'];
		$data['g_tax_rate'] = $ary_goods['g_tax_rate']*100;//跨境贸易
        $data['gsalenum'] = $ary_goods['g_salenum'];
        $data['gispres'] = $ary_goods['g_is_pres'];
        $mprice = D("Price")->getMarketPrice($data['gid'],'',1);
        //货品中最大价格
        $data['mprice'] = $mprice;
        $data['gstock'] = $ary_goods['g_stock'];
        $data['gunit'] = $ary_goods['g_unit'];
        $data['gdesc'] = $this->ReplaceItemDescPicDomain($ary_goods['g_desc']);
        $data['gphonedesc'] = $this->ReplaceItemDescPicDomain($ary_goods['g_phone_desc']);
        $data['g_phone_desc'] = $this->ReplaceItemDescPicDomain($ary_goods['g_phone_desc']);
        $data['gauth'] = $this->ReplaceItemDescPicDomain($ary_goods['g_auth']);
        $data['gpic'] = $ary_goods['g_picture'];
		$data['gpic'] = D('QnPic')->picToQn($data['gpic']);
		foreach($ary_pic as &$pic){
			$pic['gp_picture'] = D('QnPic')->picToQn($pic['gp_picture']);
		}
        $data['gpics'] = $ary_pic;
        $data['gnew'] = $ary_goods['g_new'];
        $data['ghot'] = $ary_goods['g_hot'];
        $data['gurl'] = U('Home/Products/ajaxGoodsProducts', array('gid' => $ary_goods['g_id']));
        $data['skus'] = $ary_pdt;
        $data['cid'] = $ary_goods['gc_id'];
        $data['cname'] = $ary_goods['gc_name'];
        $data['field1'] = $ary_goods['field1'];
        $data['field2'] = $ary_goods['field2'];
        $data['field3'] = $ary_goods['field3'];
        $data['field4'] = $ary_goods['field4'];
        $data['field5'] = $ary_goods['field5'];
        $data['bid'] = $ary_goods['gb_id'];
        $data['bname'] = $ary_goods['gb_name'];
        $data['gremark'] = $ary_goods['g_remark'];
        $data['coll_nums'] = intval($coll_nums);//echo "<pre>";print_r($data);exit;
        $data['comment_nums'] = D('GoodsComments')->where(array('g_id'=>$data['gid'],'gcom_verify'=>1,'gcom_status'=>1,'u_id'=>0,'gcom_star_score'=>array('gt',0)))->count();
        $data['gifts_point'] = $ary_goods['gifts_point'];
        
        if(!empty($data['skus'][0]['pdt_id'])){
        	$pdt_id = $data['skus'][0]['pdt_id'];
        }else{
        	$pdt_id = $data['gid'];
        }
        //添加促销信息
		if(!empty($_SESSION['Members']) && $tag['showoromotion'] == 1){
			$pro_datas = D('Promotion')->calShopCartPro($_SESSION['Members']['m_id'], array($pdt_id=>array('pdt_id'=>$pdt_id,'g_id'=>$data['gid'],'num'=>1,'type'=>'0')),1);
			unset($pro_datas['subtotal']);
			$promotion_name = '';
			foreach($pro_datas as $pro_data){
				if(!empty($pro_data['pmn_name'])){
					$promotion_name .='&nbsp;'.$pro_data['pmn_name'].'&nbsp;';
				}
				if(!empty($pro_data['products'][$pdt_id]['pmn_name'])){
					$promotion_name .='&nbsp;'.$pro_data['products'][$pdt_id]['pmn_name'].'&nbsp;';
				}
			}
			$data['promotion_name'] = $promotion_name;		
		}
		if($ary_goods==''){
			$data['gname']='商品不存在！';
			$data['mprice']=0;
		}
		if($data['skus'][0]['pdt_sale_price']){
			$data['gprice'] = $data['skus'][0]['pdt_sale_price'];
		}
        return array('pageinfo' => $page, 'list' => $data);
    }
	
    /**
     * 根据筛选条件查询组合分类
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2015-04-16
     * @param int $gc_type 商品分类ID, array $tag 筛选条件
     * @return array 返回仅由子分类ID组成的数组
     */
    public function getGoodCate($tag,$gc_type=0){
        //商品名称
        if (!empty($tag['gname'])) {
            $temp_arr = array();
            if(empty($tag['tid'])){
                $goods_type = M('goods_type as `gt` ', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gt_name'=>$tag['gname']))->find();
                if(!empty($goods_type) && is_array($goods_type)){
                    $temp_arr['g.gt_id'] = $goods_type['gt_id'];
                }
            }
            $temp_arr['gi.g_name'] =  array('like', "%" . trim($tag['gname']) . "%");
            $temp_arr['gi.g_keywords'] =  array('like', "%" . trim($tag['gname']) . "%");
            $temp_arr['_logic'] = 'or';
            $ary_where['_complex'] = $temp_arr; //组合成 ((g_name like "%关键字%")  OR ( g_keywords like "%关键字"))查询条件
        }

        //分类ID

        $gc_ids = array();
        if (!empty($tag['cid'])) {
            $tag['cid'] = htmlspecialchars($tag['cid'],ENT_QUOTES);
            $gc_ids[] = array('exp','in('.implode(',',$this->getStringCateArray($tag['cid'])).')');
        }

        //查询分类时，查询当前分类下子分类 edit by Wangguibin
        if(!empty($gc_ids)){
            $ary_where['gc.gc_id'] = $gc_ids;
        }

        if (!empty($tag['startprice'])) {
            $ary_where['gi.g_price'][] = array('EGT', $tag['startprice']);
        }
        if (!empty($tag['endprice'])) {
            $ary_where['gi.g_price'][] = array('ELT', $tag['endprice']);
        }
        //品牌ID
        if (!empty($tag['bid'])) {
            $tag['bid'] = htmlspecialchars($tag['bid'],ENT_QUOTES);
            $ary_where['g.gb_id'] = array('in', $tag['bid']);

        }
        if(empty($tag['bid'])){
            if(($tag['endprice'] == '' || $tag['startprice'] == '')){
                $ary_cate = $this->getInfo();
                return $ary_cate;
            }

        }
        //有效
        $ary_where['g_on_sale'] = 1;

        $ary_where['g_status'] = 1;
        $obj = M('goods as `g` ', C('DB_PREFIX'), 'DB_CUSTOM');
        //join查询
        $join_where = array();
        $join_where[] = '`fx_goods_info` `gi` on(`g`.`g_id` = `gi`.`g_id`)';
        $join_where[] = '`fx_goods_brand` `gb` on(`g`.`gb_id` = `gb`.`gb_id`)';
        $join_where[] = '`fx_related_goods_category` `rgc` on(`g`.`g_id` = `rgc`.`g_id`)';
        $join_where[] = '`fx_goods_category` `gc` on(`rgc`.`gc_id` = `gc`.`gc_id`)';
        $ary_goods_info = $obj->where($ary_where)
            ->field('distinct gc.gc_id as cid,gc_parent_id as fid,gc_name as cname,gc_level as clevel,gc_parent_id,gc_is_display,gc_ad_type,gc_is_hot,gc_pic_url')
            ->join($join_where)
            ->select();
        foreach($ary_goods_info as $key=>&$cat_val){
            //获取品牌信息
            $brands = D('Gyfx')->selectAllCache('related_goodscategory_brand','gb_id', array('gc_id'=>$cat_val['cid']));
            if(!empty($brands)){
                $bids = array();
                foreach($brands as $brand){
                    $bids[] = $brand['gb_id'];
                }
                if(!empty($bids)){
                    $ary_brand = D('Gyfx')->selectAllCache('goods_brand','gb_logo,gb_id', array('gb_id'=>array('in',$bids)));
                    if(!empty($ary_brand)){
                        $cat_val['brand'] = $ary_brand;
                    }
                }
            }
            //获取广告图片信息
            $ary_ads = D('Gyfx')->selectAllCache('related_goodscategory_ads','gc_id,ad_url,ad_pic_url', array('gc_id'=>$cat_val['cid']),array('sort_order' => 'asc'));
            if(!empty($ary_ads)){
                $cat_val['ads'] = $ary_ads;
            }
        }
        //商品类目Url
        foreach ($ary_goods_info as &$c) {
            //楼层
            if($gc_type == '1'){
                $c['curl'] = U('Home/Products/Index', array('lid' => $c['cid']));
            }else{
                //店铺
                if($gc_type == '2'){
                    $c['curl'] = U('Home/Products/Index', array('did' => $c['cid']));
                }else{
                    $c['curl'] = U('Home/Products/Index', array('cid' => $c['cid']));
                }
            }
        }
        if (!$ary_goods_info || empty($ary_goods_info))
            return $ary_goods_info;
        foreach($ary_goods_info as &$val){
            if($val['gc_parent_id']){
                $val['gnums'] = D('RelatedGoodsCategory')->where(array('gc_id'=>$val['cid']))->count();
            }

        }
        //获取商品类目最多支持8级
        $first = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="0";')));
        foreach ($first as &$cate) {
            $second = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $cate["cid"] . '";')));
            foreach ($second as &$subcat) {
                $third = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $subcat['cid'] . '";')));
                if ($third){$subcat['sub'] = $third;
                    foreach ($subcat['sub'] as &$four_cat) {
                        $four = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $four_cat['cid'] . '";')));
                        $four_cat['sub'] = $four;
                        foreach ($four_cat['sub'] as &$five_cat) {
                            $five = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $five_cat['cid'] . '";')));
                            if ($five){$five_cat['sub'] = $five;
                                foreach ($five_cat['sub'] as &$six_cat) {
                                    $six = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $six_cat['cid'] . '";')));
                                    if ($six){$six_cat['sub'] = $five;
                                        foreach ($six_cat['sub'] as &$seven_cat) {
                                            $seven = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $seven_cat['cid'] . '";')));
                                            if ($seven){$seven_cat['sub'] = $seven;
                                                foreach ($seven_cat['sub'] as &$eight_cat) {
                                                    $eight = array_values(array_filter($ary_goods_info, create_function('$val', 'return $val["gc_parent_id"]=="' . $eight_cat['cid'] . '";')));
                                                    if ($eight){$eight_cat['sub'] = $eight;}
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($second){$cate['sub'] = $second;}
        }
        return $ary_goods_info;
    }
	
	/**
     * 获取商品所关联的商品
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-05-04
     * @param string  gid
     * @return array rggoods
     */
	public function getRelegoods($ary_request) {
		//获取关联商品
		$relatedgoods = D("Goods")->where(array('g_id'=>$ary_request['gid']))->getField('g_related_goods_ids');
		/**
		$otherwhere = array();
		$otherwhere['_string'] = 'find_in_set('.$ary_request['gid'].',g_related_goods_ids)';
		$othergoods = D("Goods")->where($otherwhere)->field('g_id')->find();**/
		//$relatedgoods = trim($relatedgoods,",");
		//if(!empty($othergoods)) $relatedgoods .=','.implode(',',$othergoods);
        $where = array();
		$relatedgoods = trim($relatedgoods,",");
		if(!empty($relatedgoods)){
			$where['g_id'] = array('in',$relatedgoods);
			$rggoods = D("GoodsInfo")->field('g_id,g_name,g_price,g_picture')->where($where)->select();	
		}
		foreach($rggoods as &$rg_info){
			$rg_info['g_picture'] = D('QnPic')->picToQn($rg_info['g_picture'],300,300);
		}
		return $rggoods;
	}

	/**
     * 获取商品所关联的非销售属性
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-05-05
     * @param string  gid
     * @return array array_unsale_spec
     */
	public function getUnsalespecs($ary_request,$is_cache=false) {
        //获取此商品的扩展属性数据
        //$array_cond = array("g_id" => $ary_request['gid'], "gs_is_sale_spec" => 0);
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
            if($key==0){
                $spec_911 = $value['gsd_aliases'];
            }
            $spec_911.=','.$value['gsd_aliases'];
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
    //         if($val['gsd_id'] != 0){
				// if($is_cache == true){
				// 	$tmp_unsale_spec_detail = D('Gyfx')->selectOneCache('goods_spec_detail','gsd_value',array("gsd_id" => $val["gsd_id"]));
				// 	$array_unsale_spec[$key]["gsd_aliases"] = $tmp_unsale_spec_detail['gsd_value'];
				// }else{
    //                     $array_unsale_spec[$key]["gsd_aliases"] = D("GoodsSpecDetail")->where(array("gsd_id" => $val["gsd_id"]))->getField("gsd_value");					
				// }		
    //         }
        }
        $new_array = array();
        for($i=0;$i<count($array_unsale_spec);$i++){
            $new_array[]= $array_unsale_spec[$i]['gs_order'];
        }
        array_multisort($new_array,$array_unsale_spec);
		return $array_unsale_spec;
	}	
	
	/**
     * 替换图片原有域名及IP
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-11-26
     * @param string  str_desc
     * @return string new_str_desc
     */
	public function ReplaceItemDescPicDomain($str_desc = '') {
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			$preg = "/<img.*?src=\"(.+?)\".*?>/i";
			preg_match_all($preg, $str_desc, $match);
			$new_str_desc = $str_desc;
			if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
				$ary_replace_goal = array();
				foreach ($match[1] as $key => $val) {
					$ary_tmp_pic_url = explode("/Uploads/",$val, 2);

                    if(preg_match("/^http:\/\/img.*?/i",$ary_tmp_pic_url[0])){
                        if(!strpos($ary_tmp_pic_url[0],'/Public')){
                            continue;
                        }
                    }
                    if(in_array($ary_tmp_pic_url[0], $ary_replace_goal)) continue;
                    $ary_replace_goal[] = $ary_tmp_pic_url[0];

                    if(count($ary_tmp_pic_url) > 1) {
                        //writeLog(var_export($ary_tmp_pic_url[0],1),'replaceitem.log');
                        $new_str_desc = str_replace($ary_tmp_pic_url[0], C('DOMAIN_HOST').'/Public', $new_str_desc);
                    }elseif(count($ary_tmp_pic_url) == 1) {
						if(strpos($ary_tmp_pic_url[0],'/Public/')){
							$ary_tmp_pic_url2 = str_replace('/Public/','',$ary_tmp_pic_url[0]);
							$new_str_desc = str_replace($ary_tmp_pic_url[0], $ary_tmp_pic_url2, $new_str_desc);
						}else{
							$new_str_desc = str_replace($ary_tmp_pic_url[0], C('DOMAIN_HOST').$ary_tmp_pic_url[0], $new_str_desc);
						}
                    }
					//break;
				}
			}
			return $new_str_desc;
		}
		//是否启用七牛图片存储
		if($_SESSION['OSS']['GY_QN_ON'] == '1'){
			$preg = "/<img.*?src=\"(.+?)\".*?>/i";
			$str_desc = str_replace('/Public/Lib/ueditor/php/../../../Uploads/','/Public/Uploads/',$str_desc);
			preg_match_all($preg, $str_desc, $match);
			$new_str_desc = $str_desc;
			if (is_array($match) && isset($match[1]) && is_array($match[1]) && !empty($match[1])) {
				$ary_replace_goal = array();
				foreach ($match[1] as $key => $val) {
					//$val = str_replace('/Public/Lib/ueditor/php/../../../Uploads/','/Public/Uploads/',$val);
					$tmp_val = urldecode($val);
					$ary_tmp_pic_url = explode("/Public/Uploads/",$tmp_val);
					if(count($ary_tmp_pic_url)<=1){	
						continue;						
					}else{
						$tmp_keys = explode('?',$ary_tmp_pic_url[1]);
						$key = '/Public/Uploads/'.$tmp_keys[0];
						//获取七牛地址
						$tmp_pic_url = D('QnPic')->picToQn($key);
						$new_str_desc = str_replace($val, $tmp_pic_url, $new_str_desc);	//break;					
					}
				}
			}
			return $new_str_desc;			
		}
		return $str_desc;
	}
	
	/**
     * 报错原有描述信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-02-06
     * @param string  str_desc
     * @return string new_str_desc
     */
	public function ReplaceItemDescPicReal($str_desc = '') {
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(!empty($_SESSION['OSS']['GY_STATE_URL1'])){
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL1'] . '/', '/', $str_desc);
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL1'], '/', $new_str_desc);
			}
			if(!empty($_SESSION['OSS']['GY_STATE_URL2'])){
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL2'] . '/', '/', $new_str_desc);
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL2'], '/', $new_str_desc);
			}
			if(!empty($_SESSION['OSS']['GY_STATE_URL3'])){
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL3'] . '/', '/', $new_str_desc);
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL3'], '/', $new_str_desc);
			}
			if(!empty($_SESSION['OSS']['GY_STATE_URL4'])){
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL4'] . '/', '/', $new_str_desc);
				$new_str_desc = str_replace($_SESSION['OSS']['GY_STATE_URL4'], '/', $new_str_desc);
			}			
			if(!empty($new_str_desc)){
				return $new_str_desc;
			}
			return $str_desc;
		}
		return $str_desc;
	}
	/**
     * 报错原有图片信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2015-02-06
     * @param string  str_pic
     * @return string new_str_pic
     */
	public function ReplaceItemPicReal($str_pic = '') {
		if(!empty($_SESSION['OSS']['GY_OSS_PIC_URL']) || (!empty($_SESSION['OSS']['GY_OTHER_IP']) && !empty($_SESSION['OSS']['GY_OTHER_ON']) )){
			if(!empty($_SESSION['OSS']['GY_STATE_URL1'])){
				$str_url = $_SESSION['OSS']['GY_STATE_URL1'];
				$tmp_str_url =str_replace("//","/",$str_url);
				if($str_pic != ''){
					$new_str_pic = str_replace($str_url, '', $str_pic);			
					$new_str_pic = str_replace($tmp_str_url, '', $new_str_pic);		
					return $new_str_pic;
				}
				return $str_pic;
			}
			return $str_pic;
		}else{
			//是否启用七牛图片存储
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){
				$str_pic = urldecode($str_pic);
				if(strpos($str_pic,'http')!== false && strpos($str_pic,$_SESSION['OSS']['GY_QN_DOMAIN'])===false){
					$str_pic = '/'.ltrim($str_pic,"/");
				}else{
					$str_pic = '/'.ltrim(str_replace('Lib/ueditor/php/../../../','',$str_pic),'/');
					$str_pic = str_replace("http://".$_SESSION['OSS']['GY_QN_DOMAIN'].'/',"",$str_pic);
					$str_pics = explode('?',$str_pic);
					$str_pic = $str_pics[0];
					$str_pic = str_replace("//","/",$str_pic);
				}
			}else{
				if(!empty($str_pic)){
					if(strpos($str_pic,'http') !=false || strpos($str_pic,'https') !=false){
						
					}else{
						$str_pic = str_replace("//","/",'/'.ltrim(str_replace('Lib/ueditor/php/../../../','',$str_pic),'/'));
						//dump($str_pic);
					}
				}
			}
		}
		return $str_pic;
	}	
	
	/**
	 * 根据商品的id获取所有相关品牌
	 * @author wangguibin
	 * @param array $ary_gid <p>商品ID</p>
	 * @date 2015-11-16
	 * @return array
	 */
	public function getGoodsBrandsByGid($ary_gid,$is_cache=0) {
		if(!is_array($ary_gid) || empty($ary_gid)) {
			return array();
		}				
		if($is_cache == 1){
			return D('Gyfx')->selectAllCache('goods',array('gb_id','g_id'), array('g_id' => array('IN', $ary_gid)), $ary_order=null,'',$ary_limit=null,60);			
		}else{
			return D('Goods')->where(array('g_id' => array('IN', $ary_gid)))->field(array('gb_id','g_id'))->select();
		}
	}
}
