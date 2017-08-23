<?php
/**
 * 后台商品自由推荐资料控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.3
 * @author lf <wangguibin@guanyisoft.com>
 * @date 2013-08-09
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsFreeCollocationAction extends AdminAction{

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 商品自由推荐');
    }
    
	/**
	 * error 提示方法代码重构
	 */
	public function errorMsg($str_message){
		//C("LAYOUT_ON",false);
		//header("content-type:text/html;charset=utf-8");
		//$this->assign("message",$str_message);
		//$this->display("error");
		$this->error($str_message);
		exit;
	}
	
	/**
	 * success 提示方法代码重构
	 */	
	public function successMsg($str_message,$jump_url=""){
		/*
		C("LAYOUT_ON",false);
		header("content-type:text/html;charset=utf-8");
		$this->assign("message",$str_message);
		$this->assign("jump_url",$jump_url);
		$this->display("success");
		*/
		$this->success($str_message,$jump_url);
		exit;
	}
    
	/**
	 * 商品自由推荐列表
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function freeCollocationList(){
        $this->getSubNav(3, 5, 47);
        $combination = D("FreeCollocation");
        $ary_data = array();
        $array_where = array();
        if(isset($_POST['val']) && !empty($_POST['val'])){ 
            if($_POST['field'] == 1){
                //组合商品名称搜索
                $array_where['fc_title'] = array('LIKE',"%".$_POST['val']."%");
            }else{
                //组合商品相关g_sn搜索
                $str_g_sn = $_POST['val'];
                (int)$int_g_id = D("Goods")->where(array('g_sn'=>$str_g_sn))->getField('g_id');
                $array_where['_string'] = ' FIND_IN_SET('.$int_g_id.',fc_related_good_id)';
            }
        }
        //时间搜索
        if(!empty($_POST['g_on_sale_time']) && !empty($_POST['g_off_sale_time'])){
            $array_where['fc_start_time'] = array('EGT',$_POST['g_on_sale_time']);
            $array_where['fc_end_time'] = array('ELT',$_POST['g_off_sale_time']);
            
        }
        $count = $combination->where($array_where)->count();
        $obj_page = new Page($count, 20);
        $ary_data['page'] = $obj_page->show();
        $combination_goods = $combination->where($array_where)
                            ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                            ->select();
        foreach($combination_goods as $key=>$combination){
        	$content = '';
        	$com_where = array();
        	$com_where[C('DB_PREFIX').'goods_info.g_id'] = array('in',$combination[C('DB_PREFIX').'related_good_id']);
        	$ary_goods = D("GoodsInfo")
        	->where($com_where)
        	->join(C('DB_PREFIX').'goods on('.C('DB_PREFIX').'goods.g_id='.C('DB_PREFIX').'goods_info.g_id)')
        	->field('g_name,g_collocation_price,g_stock,g_sn,g_on_sale')->select();
        	//$combination_goods[$key]['goods'] = $ary_good;
        	foreach($ary_goods as $ary_good){
        	    if($ary_good['g_on_sale'] == 2){
                    $content.= $ary_good['g_name'].' '.$ary_good['g_sn'].' ¥'.$ary_good['g_collocation_price'].'已下架，库存剩余:'.$ary_good['g_stock'].'<br />';
                }else{
                    $content.= $ary_good['g_name'].' '.$ary_goods['g_sn'].' ¥'.$ary_good['g_collocation_price'].'在架，库存剩余:'.$ary_good['g_stock'].'<br />';
                }       		
        	}
			$combination_goods[$key]['effectiveness'] = $content;
        }                       
        $ary_data['list'] = $combination_goods;
        $this->assign('filter',$_POST);
        $this->assign($ary_data);
        $this->display('com_list');
    }
    
	/**
	 * 新增自由推荐
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function addFreeCollocationPage(){
        $this->getSubNav(3,5,48);
        $search['cates'] =D("ViewGoods")->getCates();
        $this->assign("search",$search);
        $this->display('add_com');
    }
    
	/**
	 * 异步查询商品和货号信息
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function searchPdtInfo(){
    	//商品编码
        $str_g_sn = trim($this->_post('g_sn'));
      	//查询商品信息
        $ary_goods =  M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
        ->join(C('DB_PREFIX').'goods on('.C('DB_PREFIX').'goods.g_id='.C('DB_PREFIX').'goods_products.g_id) ')
        ->join(C('DB_PREFIX').'goods_info on('.C('DB_PREFIX').'goods_info.g_id = '.C('DB_PREFIX').'goods_products.g_id) ')
        ->where(array(C('DB_PREFIX').'goods.g_status'=>'1',C('DB_PREFIX').'goods_products.pdt_status'=>'1','pdt_is_combination_goods'=>'0',C('DB_PREFIX').'goods_products.g_sn'=>$str_g_sn))
        ->field(C('DB_PREFIX').'goods_info.g_id,'.C('DB_PREFIX').'goods_info.g_name,'.C('DB_PREFIX').'goods.g_sn,'.C('DB_PREFIX').'goods_info.g_price,'.C('DB_PREFIX').'goods_info.g_collocation_price,'.C('DB_PREFIX').'goods_products.pdt_collocation_price,'.C('DB_PREFIX').'goods_products.pdt_sn,'.C('DB_PREFIX').'goods_info.g_stock,'.C('DB_PREFIX').'goods_products.pdt_id,'.C('DB_PREFIX').'goods_products.pdt_stock,'.C('DB_PREFIX').'goods_products.pdt_sale_price')
        ->select();
		if(empty($ary_goods)){
			$this->ajaxReturn(array('status'=>'error','msg'=>'该商品不存在'));
		}else{
			//判断此商品是否在其他自由推荐商品里
			(int)$gid = $ary_goods[0]['g_id'];
			$exist_where = array();
			$exist_where['_string'] = 'FIND_IN_SET('.$gid.',fc_related_good_id) ';
			$ary_exist =  M('free_collocation',C('DB_PREFIX'),'DB_CUSTOM')
			->where($exist_where)->field('fc_title')->find();
			//查询商品货号信息
			if($ary_exist['fc_title']){
				$this->ajaxReturn(array('status'=>'error','msg'=>'商品'.$ary_goods[0]['g_name'].'已在'.$ary_exist['fc_title'].'自由推荐组合里'));
			}else{
				//商品详情
				$ary_goods_info = array();
				$ary_goods_info['g_name'] = $ary_goods[0]['g_name'];
				$ary_goods_info['g_sn'] = $ary_goods[0]['g_sn'];
				$ary_goods_info['g_id'] = $ary_goods[0]['g_id'];
				$ary_goods_info['g_stock'] = $ary_goods[0]['g_stock'];
				$ary_goods_info['g_price'] = $ary_goods[0]['g_price'];
				$ary_goods_info['g_collocation_price'] = empty($ary_goods[0]['g_collocation_price'])?$ary_goods[0]['g_price']:$ary_goods[0]['g_collocation_price'];
				//判断此商品是否有规格
				if(count($ary_goods)>1){
					//商品多规格
					$ary_goods_info['products'] = $ary_goods;
					foreach($ary_goods_info['products'] as $key=>$ary_product){
						$specName = D('GoodsSpec')->getProductsSpec($ary_product['pdt_id']);
            			$ary_goods_info['products'][$key]['specName'] = empty($specName) ? '无规格' : $specName;
					}
				}else{
					//商品无规格
					$ary_goods_info['g_stock'] = $ary_goods[0]['pdt_stock'];
					$ary_goods_info['g_price'] = $ary_goods[0]['pdt_sale_price'];
					$ary_goods_info['pdt_sn'] = $ary_goods[0]['pdt_sn'];
					$ary_goods_info['pdt_id'] = $ary_goods[0]['pdt_id'];
					$ary_goods_info['g_collocation_price'] = empty($ary_goods[0]['g_collocation_price'])?$ary_goods[0]['g_price']:$ary_goods[0]['g_collocation_price'];
				}
				unset($ary_goods);
			}
		}
        $this->assign("data",$ary_goods_info);
        $this->display('ajaxLoadAddComPdtLits');
    }
    
	/**
	 * 添加自由推荐
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function getProductsInfo(){
        //$products = D("GoodsProductsTable");
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['g_name'] = $this->_get('g_name', 'htmlspecialchars,trim', '');
        $chose['pdt_sn'] = $this->_get('pdt_sn', 'htmlspecialchars,trim', '');
        $chose['gcid'] = $this->_get('gs_gcid', 'htmlspecialchars,trim', '');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        //商品分类搜索
        if ($chose['gcid']) {
            $where['rgc.gc_id'] = array('in', D("ViewGoods")->getCatesIds($chose['gcid']));
        }
        //商品名称查询
        if ($chose['g_name']) {
            $where['gi.g_name'] = array('LIKE', '%' . $chose['g_name'] . '%');
        }
        //商品编码查询
        if ($chose['pdt_sn']) {
            $where[C('DB_PREFIX').'goods.g_sn'] = array('LIKE', '%' . $chose['pdt_sn'] . '%');
        }
        //$where['gi.g_stock'] = array('GT',0);
        $where[C('DB_PREFIX').'goods.g_is_combination_goods'] = 0;
        $where[C('DB_PREFIX').'goods.g_status'] = 1;
        
        //非处方药
        //$where['fx_goods.g_is_prescription_rugs']  = 0;
        //print_r($where);exit;
        //设置页面的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $search['cates'] = D("ViewGoods")->getCates();
        if ($chose['gcid']) {
            $count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->Distinct(true)->field(array('gi.g_id'))
                          ->join(C('DB_PREFIX').'related_goods_category as rgc on(rgc.g_id='.C('DB_PREFIX').'goods.g_id)')
                          ->join(C('DB_PREFIX').'goods_info as gi on(gi.g_id='.C('DB_PREFIX').'goods.g_id)')
                          ->select();               
        }else{
            $count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->Distinct(true)->field(array('gi.g_id'))
                          ->join(C('DB_PREFIX').'goods_info as gi on(gi.g_id='.C('DB_PREFIX').'goods.g_id)')
                          ->select();
        }   
        $str_count = count($count);
        $Page = new Page($str_count, 5);
        $ary_data['page'] = $Page->show();
        
        $field=array('gi.g_id','gt.gt_name','gi.g_name','gi.g_picture',C('DB_PREFIX').'goods.g_sn');
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        if ($chose['gcid']) {
            $array_products = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->Distinct(true)->field($field)->where($where)
                                            ->join(C('DB_PREFIX').'related_goods_category as rgc on(rgc.g_id='.C('DB_PREFIX').'goods.g_id)')
                                            ->join(C('DB_PREFIX').'goods_info as gi on(gi.g_id='.C('DB_PREFIX').'goods.g_id)')
                                            ->join(C('DB_PREFIX').'goods_type as gt on('.C('DB_PREFIX').'goods.gt_id=gt.gt_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }else{
            $array_products = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->Distinct(true)->field($field)->where($where)
                                            ->join(C('DB_PREFIX').'goods_info as gi on(gi.g_id='.C('DB_PREFIX').'goods.g_id)')
                                            ->join(C('DB_PREFIX').'goods_type as gt on('.C('DB_PREFIX').'goods.gt_id=gt.gt_id)')
                                            ->limit($limit['start'],$limit['end'])->select();
        }
        foreach ($array_products as $key=>$val){
            $goodscate = M('related_goods_category',C('DB_PREFIX'),'DB_CUSTOM');
            $array_where['g.g_id'] = $val['g_id'];
            $category = $goodscate->where($array_where)
                    ->Distinct(true)
                            ->field(array('gc.gc_name'))
                            ->join(C('DB_PREFIX').'goods as g on(g.g_id='.C('DB_PREFIX').'related_goods_category.g_id)')
                            ->join(C('DB_PREFIX').'goods_category as gc on(gc.gc_id='.C('DB_PREFIX').'related_goods_category.gc_id)')
                            ->select();
            foreach($category as $c_v){
                $str_tmp_cate .= $c_v['gc_name'].",";
            }
            $array_products[$key]['gc_name'] = rtrim(trim($str_tmp_cate,','));
            $str_tmp_cate = '';
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){
				$array_products[$key]['g_picture'] = D('ViewGoods')->ReplaceItemPicReal($val['g_picture']);
			}else{
				$array_products[$key]['g_picture'] = '/'.ltrim($val['g_picture'],'/');
			}
        }
        $ary_data['list'] = $array_products;
        $this->assign('search', $search); //查询条件
        $this->assign('chose', $chose);  //当前已经选择的
        $this->assign($ary_data);    //赋值数据集，和分页
        $this->display();
    }
    
    /**
	 * 执行添加自由推荐
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function addFreeCollocation(){
		//print_r($_POST);exit;
        $Goods = D("FreeCollocation");
        $ary_com_goods = $this->_post();
        //注：商品表开始时间和结束时间在自由推荐商品中默认为有效时间段
        $g_on_sale_time = $this->_post('g_on_sale_time');
        $g_off_sale_time = $this->_post('g_off_sale_time');
        
        //验证自由推荐商品名称是否唯一
        $check_result = M('free_collocation')->where(array('fc_title'=>$ary_com_goods['g_name']))->getField("fc_title");
        if(isset($check_result) && !empty($check_result)){
            return $this->ajaxReturn(array('status'=>'error','msg'=>'自由推荐商品标题已存在！'));
        }
        
        //验证时间有效性
        if($g_on_sale_time!='0000-00-00 00:00:00' || $g_off_sale_time!='0000-00-00 00:00:00'){
            if(strtotime($g_on_sale_time) > strtotime($g_off_sale_time)){
                return $this->ajaxReturn(array('status'=>'error','msg'=>'开始时间不能小于结束时间'));
            }
        }
        //自由推荐商品基本信息入库
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
		//向商品基本资料表中新增记录
        $ary_add_goods = array();
		$ary_add_goods['fc_title'] = $ary_com_goods['g_name'];
		$ary_add_goods['fc_create_time'] = date('Y-m-d H:i:s');
		$ary_add_goods['fc_update_time'] = date('Y-m-d H:i:s');
		$ary_add_goods['fc_start_time'] = $g_on_sale_time;
		$ary_add_goods['fc_end_time'] = $g_off_sale_time;
		//商品IDfc_related_good_id
		$related_good_id = '';
		//统计数量
		$i = 0;
		foreach($ary_com_goods['combination_goods'] as $good){
			if($good['releted_have_sku'] == '1' || empty($good['releted_pdt_id'])){
				$related_good_id .=$good['g_id'].',';
				$i++;
			}
			//商品主表和规格表添加优惠价格
			if(empty($good['releted_pdt_id'])){
				if($good['g_id']){
					$obj_res = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
					->where(array('g_id'=>$good['g_id']))->save(array('g_collocation_price'=>$good['com_price'],'g_update_time'=>date('Y-m-d H:i:s')));
					if(!obj_res){
						M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
					}
					//判断商品是否是单规格或无规格数据，如果是则更新规格表pdt_collocation_price
					$int_result_count = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$good['g_id']))->count();
					if($int_result_count == '1'){
						//更新规格表
						$boll_product_res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->where(array('g_id'=>$good['g_id']))->save(array('pdt_collocation_price'=>$good['com_price'],'pdt_update_time'=>date('Y-m-d H:i:s')));
						if(!$boll_product_res){
							M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
						}
					}
				}
			}else{
				if($good['releted_have_sku'] == '1'){
					$obj_res = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
					->where(array('g_id'=>$good['g_id']))->save(array('g_collocation_price'=>$good['com_price'],'g_update_time'=>date('Y-m-d H:i:s')));
					if(!obj_res){
						M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
					}					
				}
				//更新规格表
				$obj_product_res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
				->where(array('g_id'=>$good['g_id'],'pdt_id'=>$good['releted_pdt_id']))->save(array('pdt_collocation_price'=>$good['com_price'],'pdt_update_time'=>date('Y-m-d H:i:s')));
				if(!$obj_product_res){
					M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
				}					
			}
			
		}
		//自由推荐关联商品
		$related_good_id = substr($related_good_id, 0, -1);
		if($i<2){
			return $this->ajaxReturn(array('status'=>'error','msg'=>'至少选择两件商品'));
		}
		$ary_add_goods['fc_related_good_id'] = $related_good_id;
        $int_combination_good_id = $Goods->add($ary_add_goods);
        if(false === $int_combination_good_id){
            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            return $this->ajaxReturn(array('status'=>'error','msg'=>'添加自由推荐组合基本信息失败','Sql'=>$Goods->getLastSql()));
        }
        //事物提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        return $this->ajaxReturn(array('status'=>'success','自由推荐组合商品添加成功','URL'=>'/Admin/GoodsFreeCollocation/freeCollocationList'));
        
    }
    
    /**
	 * 启用/关闭组合商品
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function enableFreeCollocation(){
        $fc_id = $this->_post('fc_id');
        $fc_status = $this->_post('fc_status');
        $Goods = D("FreeCollocation");
        if($fc_status == 1){
            $msg = '开启成功！';
        }else{
            $msg = '关闭成功！';
        }
        if($Goods->where(array('fc_id'=>$fc_id))->save(array('fc_status'=>$fc_status,'fc_update_time'=>date('Y-m-d H:i:s')))){
            return $this->ajaxReturn(array('status'=>'success','Msg'=>$msg));
        }else{
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'失败'));
        }
    }
    
    /**
	 * 显示编辑自由推荐页面
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function editFreeCollocationPage(){
        $this->getSubNav(3,5,48);
        $fc_id = $this->_get('fc_id');
       	$combination = D("FreeCollocation");
		$array_where['fc_id'] =  $fc_id;
        $combination_data = $combination->where($array_where)->find();
        if(empty($combination_data)){
            $this->errorMsg("不存在的自由推荐商品");exit;
        }          
		//获取自由推荐关联的商品信息
        $com_where = array();
        $com_where[C('DB_PREFIX').'goods.g_status'] = 1;
        $com_where[C('DB_PREFIX').'goods_info.g_id'] = array('in',$combination_data['fc_related_good_id']);
    	//查询商品信息
        $ary_goods =  M('goods',C('DB_PREFIX'),'DB_CUSTOM')
        ->join(C('DB_PREFIX').'goods_info on('.C('DB_PREFIX').'goods_info.g_id = '.C('DB_PREFIX').'goods.g_id) ')
        ->where($com_where)
        ->field(C('DB_PREFIX').'goods_info.g_id,'.C('DB_PREFIX').'goods_info.g_name,'.C('DB_PREFIX').'goods.g_sn,'.C('DB_PREFIX').'goods_info.g_price,'.C('DB_PREFIX').'goods_info.g_collocation_price,'.C('DB_PREFIX').'goods_info.g_stock')
        ->select();
		if(empty($ary_goods)){
			$this->errorMsg("不存在自由推荐商品");exit;
		}else{
			//商品详情
			foreach($ary_goods as &$ary_good){
				$products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
				->join(C('DB_PREFIX').'goods on('.C('DB_PREFIX').'goods.g_id='.C('DB_PREFIX').'goods_products.g_id) ')
				->field(C('DB_PREFIX').'goods.g_sn,'.C('DB_PREFIX').'goods_products.g_id,'.C('DB_PREFIX').'goods_products.pdt_collocation_price,'.C('DB_PREFIX').'goods_products.pdt_sn,'.C('DB_PREFIX').'goods_products.pdt_id,'.C('DB_PREFIX').'goods_products.pdt_stock,'.C('DB_PREFIX').'goods_products.pdt_sale_price')
				->where(array(C('DB_PREFIX').'goods.g_id'=>$ary_good['g_id'],'pdt_is_combination_goods'=>0,C('DB_PREFIX').'goods_products.pdt_status'=>1))
				->select();
				if(count($products)>1){
					//商品多规格
					$ary_good['products'] = $products;
					foreach($ary_good['products'] as $key=>$ary_product){
						$specName = D('GoodsSpec')->getProductsSpec($ary_product['pdt_id']);
						$ary_good['products'][$key]['specName'] = empty($specName) ? '无规格' : $specName;
					}					
				}
			}
		}
		$combination_data['goods_data'] = $ary_goods;
        $search['cates'] =D("ViewGoods")->getCates();
        $this->assign("search",$search);
        $this->assign('data',$combination_data);
        $this->display('edit_com');
    }
    
    /**
	 * 执行编辑自由推荐页面
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function editFreeCollocation(){
        $ary_edit_goods = $this->_post();
        $fc_id = $ary_edit_goods['fc_id'];
        if(!isset($fc_id) && empty($fc_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'参数有误！'));
        }
        //注：商品表开始时间和结束时间在自由推荐商品中默认为有效时间段
        $g_on_sale_time = $this->_post('g_on_sale_time');
        $g_off_sale_time = $this->_post('g_off_sale_time');
        
        //验证自由推荐商品名称是否唯一
        $check_where = array();
        $check_where['fc_title'] = $ary_edit_goods['g_name'];
        $check_where['fc_id'] = array('neq'=>$fc_id);
        $check_result = M('free_collocation')->where($check_where)->getField("fc_title");
        if(isset($check_result) && !empty($check_result)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'自由推荐商品标题已存在！'));
        }
        
        //验证时间有效性
        if($g_on_sale_time!='0000-00-00 00:00:00' || $g_off_sale_time!='0000-00-00 00:00:00'){
            if(strtotime($g_on_sale_time) > strtotime($g_off_sale_time)){
                return $this->ajaxReturn(array('status'=>'error','Msg'=>'开始时间不能小于结束时间'));
            }
        }
        $ary_como_data = M('free_collocation')->field('fc_related_good_id,fc_id')->where(array('fc_id'=>$fc_id))->find();
        if(empty($ary_como_data)){
        	return $this->ajaxReturn(array('status'=>'error','Msg'=>'未查询到此自由推荐！'));
        }
        //自由推荐商品基本信息入库
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        $combo_where = array();
        $combo_where['g_id'] = array('in',$ary_como_data['fc_related_good_id']);
        //删除商品基本表
        $good_data = array(
        	'g_collocation_price'=>null,
        	'g_update_time'=>date('Y-m-d H:i:s')
        );
        if(false ===  M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where($combo_where)->save($good_data)){
            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除商品基本信息失败'));
        }
        //删除货品表
        if(false === D("GoodsProductsTable")->where($combo_where)->save(array('pdt_collocation_price'=>null,'pdt_update_time'=>date('Y-m-d H:i:s')))){
            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除自由推荐商品资料失败'));
        }         
		//向商品基本资料表中新增记录
        $ary_add_goods = array();
		$ary_add_goods['fc_title'] = $ary_edit_goods['g_name'];
		$ary_add_goods['fc_update_time'] = date('Y-m-d H:i:s');
		$ary_add_goods['fc_start_time'] = $g_on_sale_time;
		$ary_add_goods['fc_end_time'] = $g_off_sale_time;
		//商品IDfc_related_good_id
		$related_good_id = '';
		//统计数量
		$i = 0;
		foreach($ary_edit_goods['combination_goods'] as $good){
			if($good['releted_have_sku'] == '1' || empty($good['releted_pdt_id'])){
				$related_good_id .=$good['g_id'].',';
				$i++;
			}
			//商品主表和规格表添加优惠价格
			if(empty($good['releted_pdt_id'])){
				if($good['g_id']){
					$obj_res = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
					->where(array('g_id'=>$good['g_id']))->save(array('g_collocation_price'=>$good['com_price'],'g_update_time'=>date('Y-m-d H:i:s')));
					if(!obj_res){
						M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
						return $this->ajaxReturn(array('status'=>'error','Msg'=>'添加商品主表资料失败'));
					}
					//判断商品是否是单规格或无规格数据，如果是则更新规格表pdt_collocation_price
					$int_result_count = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$good['g_id']))->count();
					if($int_result_count == '1'){
						//更新规格表
						$boll_product_res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
						->where(array('g_id'=>$good['g_id']))->save(array('pdt_collocation_price'=>$good['com_price'],'pdt_update_time'=>date('Y-m-d H:i:s')));
						if(!$boll_product_res){
							M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
						}
					}
				}
			}else{
				if($good['releted_have_sku'] == '1'){
					$obj_res = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
					->where(array('g_id'=>$good['g_id']))->save(array('g_collocation_price'=>$good['com_price'],'g_update_time'=>date('Y-m-d H:i:s')));
					if(!obj_res){
						M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
						return $this->ajaxReturn(array('status'=>'error','Msg'=>'添加商品主表资料失败'));
					}					
				}
				//更新规格表
				$obj_product_res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
				->where(array('g_id'=>$good['g_id'],'pdt_id'=>$good['releted_pdt_id']))->save(array('pdt_collocation_price'=>$good['com_price'],'pdt_update_time'=>date('Y-m-d H:i:s')));
				if(!$obj_product_res){
					M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
					return $this->ajaxReturn(array('status'=>'error','Msg'=>'添加商品明细表资料失败'));
				}					
			}
			
		}
		//自由推荐关联商品
		$related_good_id = substr($related_good_id, 0, -1);
		if($i<2){
			return $this->ajaxReturn(array('status'=>'error','Msg'=>'至少选择两件商品'));
		}
		$ary_add_goods['fc_related_good_id'] = $related_good_id;
        $int_combination_good_id = M('free_collocation',C('DB_PREFIX'),'DB_CUSTOM')->where(array('fc_id'=>$fc_id))->save($ary_add_goods);
        if(false === $int_combination_good_id){
            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'修改自由推荐组合基本信息失败','Sql'=>$Goods->getLastSql()));
        }
        //事物提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        return $this->ajaxReturn(array('status'=>'success','Msg'=>'自由推荐商品修改成功','URL'=>'/Admin/GoodsFreeCollocation/freeCollocationList'));
        
    }
    
    /**
	 * 异步删除自由推荐页面
	 * 
	 * @author Wangguibin@guanyisoft.com
	 * @version 7.3
	 * @date 2013-08-09
	 */
    public function ajaxDelFreeCollocation(){
        //组合商品id（1,2,3,4,5,）
        $str_fc_id = $this->_post('fc_id');
        if(empty($str_fc_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'请选择要删除的自由推荐商品！'));
        }
        $array_where['fc_id'] = array('in',$str_fc_id);
        $combinationGoods = D("FreeCollocation")->field('fc_id,fc_related_good_id')->where($array_where)->select();
        //开启事物
        M('',C('DB_PREFIX'),'DB_CUSTOM')->startTrans();
        foreach($combinationGoods as $combo){
        	$combo_where['g_id'] = array('in',$combo['fc_related_good_id']);
	        //删除商品基本表
	        $good_data = array(
	        	'g_collocation_price'=>null,
	        	'g_update_time'=>date('Y-m-d H:i:s')
	        );
	        if(false ===  M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where($combo_where)->save($good_data)){
	            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
	            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除商品基本信息失败'));
	        }
	        //删除货品表
	        if(false === D("GoodsProductsTable")->where($combo_where)->save(array('pdt_collocation_price'=>null,'pdt_update_time'=>date('Y-m-d H:i:s')))){
	            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
	            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除自由推荐商品资料失败'));
	        }       	
        }
        //删除自由推荐主表
        if(false === D("FreeCollocation")->where($array_where)->delete()){
            M('',C('DB_PREFIX'),'DB_CUSTOM')->rollback();
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'删除自由推荐主表失败'));
        }
        //事物提交
        M('',C('DB_PREFIX'),'DB_CUSTOM')->commit();
        return $this->ajaxReturn(array('status'=>'success','Msg'=>'删除成功！'));
    }
    
}