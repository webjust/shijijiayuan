<?php

/**
 * 后台商品控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-31
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ProductsAction extends AdminAction {

    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
        $this->setTitle(' - ' . L('MENU2_0'));
    }

    /**
     * 后台商品控制器默认页，需要重定向
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function index() {
        $this->redirect(U('Admin/Products/pageList'));
    }

    /**
     * 后台本地商品列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @modify Terry<wanghui@guanyisoft.com> 2013-3-19
     * @date 2012-12-31
     */
    public function pageList() {
        $ary_request = $this->_request();
		$currentPage = (int)$ary_request['p'];
		if(0 != $currentPage){
			session('page',$currentPage);
		}
        $ary_request['tabs'] = empty($ary_request['tabs']) ? "website" : $ary_request['tabs'];
        $data = array();
        $ary_where = array();
        if (!empty($ary_request['search']) && $ary_request['search'] == 'easy') {
            switch ($ary_request['field']) {
                case 'g_sn':
					if(!empty($ary_request['val'])){
						$ary_where['g_sn'] = trim($ary_request['val']);
						break;					
					}
                case 'g_name':
					$ary_request['val'] = urldecode(trim($ary_request['val']));
					
                    $ary_where['gi.g_name'] = array('like', "%" . trim($ary_request['val']) . "%");
                    break;
            }
            if(!empty($ary_request['gpid']) && isset($ary_request['gpid'])){
                $array_goods_id = M('related_goods_group',C('DB_PREFIX'), 'DB_CUSTOM')->distinct(true)->field('g_id')->where(array('gg_id'=>$ary_request['gpid']))->select();
                $ary_gid = array();
                foreach ($array_goods_id as $gid){
                    $ary_gid[] = $gid['g_id'];
                }

                $ary_where['gi.g_id'] = array('in',isset($ary_gid)?$ary_gid:'');
                
            }
            //品牌搜索
            if (!empty($ary_request['brand']) && isset($ary_request['brand'])) {
                $ary_where['gb_id'] = array('in',$ary_request['brand']);
            }
        } else {
            if (isset($ary_request['category']) && !empty($ary_request['category'])) {
                $ary_where["fx_goods.g_id"] = 0;
                //如果指定了商品分类进行检索，则先获取该商品分类下关联的商品ID
                $array_related_g_ids = D("RelatedGoodsCategory")->distinct(true)->where(array("gc_id" => array('in', $ary_request['category'])))->getField("g_id", true);
                //echo D("RelatedGoodsCategory")->getLastSql();exit;
                if (!empty($array_related_g_ids)) {
                    $ary_where["fx_goods.g_id"] = array("IN", $array_related_g_ids);
                }
            }
            //品牌搜索
            if (!empty($ary_request['brand']) && isset($ary_request['brand'])) {
                $ary_where['gb_id'] = array('in',$ary_request['brand']);
            }
            if (!empty($ary_request['status']) && isset($ary_request['status'])) {
                $ary_where['g_on_sale'] = $ary_request['status'];
            }
            if (!empty($ary_request['start_time']) && isset($ary_request['start_time'])) {
                if (!empty($ary_request['end_time']) && $ary_request['end_time'] > $ary_request['start_time']) {
                    $ary_request['end_time'] = trim($ary_request['end_time']) . " 23:59:59";
                } else {
                    $ary_request['end_time'] = date("Y-m-d H:i:s");
                }
                $ary_where['g_update_time'] = array("between", array($ary_request['start_time'] . " 00:00:00", $ary_request['end_time']));
            }
            if (!empty($ary_request['stockSymbol']) && !empty($ary_request['stock'])) {
                $ary_where['g_stock'] = array($ary_request['stockSymbol'], $ary_request['stock']);
            }
            if (!empty($ary_request['new']) && !empty($ary_request['new'])) {
                $ary_where['g_new'] = $ary_request['new'];
            }
            if (!empty($ary_request['hot']) && !empty($ary_request['hot'])) {
                $ary_where['g_hot'] = $ary_request['hot'];
            }
        }
        // 判断是否开启分配库存
        $inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_STOCK');
        $ary_request['inventory_stock'] = 0;
        if(isset($inventoryConfig['INVENTORY_STOCK']['sc_value']))
        {
            $ary_request['inventory_stock'] = $inventoryConfig['INVENTORY_STOCK']['sc_value'];
        }

        $ary_where['g_status'] = '1';
        $int_page_size = 10;
        //商品列表页页签处理
        $string_name = trim($ary_request['tabs']);
        $admin_left_menu = 30;
        switch ($string_name) {
            case "shelves":
                $ary_where['g_on_sale'] = '2';
                $admin_left_menu = 40;
                break;
            case "website":
                $ary_where['g_on_sale'] = '1';
                $admin_left_menu = 30;
                break;
            case "recycle":
                $ary_where['g_status'] = 2;
                $admin_left_menu = 45;
                break;
            case "new":
                $ary_where['g_on_sale'] = '1';
                $ary_where['g_new'] = '1';
                break;
            case "hot":
                $ary_where['g_on_sale'] = '1';
                $ary_where['g_hot'] = '1';
                break;
            default:
                $ary_where['g_on_sale'] = '1';
        }
        $ary_where['fx_goods.g_is_combination_goods'] = 0;
        if(!empty($ary_request['ggid']) && (int)$ary_request['ggid'] > 0){
            $ary_where['gg.gg_id'] = $ary_request['ggid'];
        }
        $this->getSubNav(3, 0, $admin_left_menu);
        //按修改时间排序（大到小 desc） 商品排序（越大越靠前 desc）
        $order_by = array('g_update_time'=>'desc');
        $data = $this->pageGoods($ary_where, $order_by, $int_page_size);
        $related_goods_category = D("RelatedGoodsCategory");
        $goods_products_table = D('GoodsProductsTable');
        //获取商品分类
        $array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
        foreach($array_category as $cat) {
            $all_cat[$cat['gc_id']] = $cat['gc_name'];
        }
        //获取商品品牌
        $array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->order('gb_order desc')->select();
        //$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
        $array_brand_key = array();
        foreach($array_brand as $key =>$value){
            $array_brand_key[$value['gb_id']]= $value['gb_name'];
        }
        // 计算库存
        if(!empty($data['list']) && is_array($data['list'])){
            foreach($data['list'] as &$goods){
                $goods['total_stock'] = $goods_products_table->where(array('g_id'=>$goods['g_id']))->sum('pdt_total_stock');
               
                //获取此商品关联的分类信息
                $goods['array_catid'] = $related_goods_category->where(array("g_id"=>$goods['g_id']))->getField("gc_id",true);
                $arr_cat = array();
                foreach($goods['array_catid'] as $cat_id ) {
                    $arr_cat[] = $all_cat[$cat_id];
                }
                $goods['cat_name'] = implode(",",$arr_cat);
                $goods['gb_name'] = '';
                if(array_key_exists($goods['gb_id'],$array_brand_key)){
                    $goods['gb_name'] = $array_brand_key[$goods['gb_id']];
                }
            }
        }

        $this->assign("array_brand",$array_brand);
        $this->assign("array_category",$array_category);
        $this->assign("filter", $ary_request);
        $this->assign("page", $data['page']);
        $this->assign("data", $data['list']);
		//获取所有的商品分组，提供给页面批量操作使用
		$this->assign("goodsgroups",D("GoodsGroup")->where(array("gg_status"=>1))->order(array("gg_order"=>"asc"))->select());
        $this->display();
    }

    /**
     * 后台官网商品列表页
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function pageGoods($array_condition = array(), $order_by, $int_page_size = 20) {
        $GoodsBaseModel = D("GoodsBase");
        $count = $GoodsBaseModel
                ->where($array_condition)
                ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id) ")
                ->join("fx_related_goods_group as rgg on(fx_goods.g_id=rgg.g_id)")
                ->join("fx_goods_group as gg on(gg.gg_id = rgg.gg_id)")
                ->count('distinct(fx_goods.`g_id`)');
        //echo "<pre>";print_r($GoodsBaseModel->getLastSql());exit;
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $GoodsBaseModel
                ->where($array_condition)
                ->field("
				distinct(fx_goods.`g_id`) AS `g_id`,
				fx_goods.`gb_id` AS `gb_id`,fx_goods.`gt_id` AS `gt_id`,fx_goods.`g_on_sale` AS `g_on_sale`,
				fx_goods.`g_status` AS `g_status`,fx_goods.`g_sn` AS `g_sn`,
				fx_goods.`g_off_sale_time` AS `g_off_sale_time`,fx_goods.`g_on_sale_time` AS `g_on_sale_time`,
				fx_goods.`g_new` AS `g_new`,fx_goods.`g_hot` AS `g_hot`,fx_goods.`g_retread_date` AS `g_retread_date`,
				fx_goods.`g_pre_sale_status` AS `g_pre_sale_status`,fx_goods.`g_gifts` AS `g_gifts`,
				`gi`.`ma_price` AS `ma_price`,
				`gi`.`mi_price` AS `mi_price`,`gi`.`g_name` AS `g_name`,`gi`.`g_price` AS `g_price`,
				`gi`.`g_unit` AS `g_unit`,`gi`.`g_desc` AS `g_desc`,`gi`.`g_picture` AS `g_picture`,
				`gi`.`g_no_stock` AS `g_no_stock`,`gi`.`g_create_time` AS `g_create_time`,
				`gi`.`g_update_time` AS `g_update_time`,`gi`.`g_red_num` AS `g_red_num`,
				`gi`.`g_source` AS `g_source`,`gi`.`g_stock` AS `g_stock`,
				`gi`.`g_salenum` AS `g_salenum`,`gi`.`point` AS `point`,
				`gi`.`is_exchange` AS `is_exchange`,group_concat(gg.gg_name) as group_name,
				`gi`.`g_discount` AS `g_discount`
                ")
                ->join("fx_goods_info as gi on(fx_goods.g_id=gi.g_id) ")
                ->join("fx_related_goods_group as rgg on(fx_goods.g_id=rgg.g_id)")
                ->join("fx_goods_group as gg on(gg.gg_id = rgg.gg_id)")
                ->order($order_by)
                ->group('fx_goods.g_id')
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
                //echo "<pre>";print_r($GoodsBaseModel->getLastSql());exit;
        return $data;
    }
    
    /**
     * 商品加入回收站
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-3-12
     */
    public function doGoodsisRecycle(){
        if(false === M('Goods')->where(array('g_id'=>$this->_post('gid')))->save(array('g_status'=>2,'g_on_sale'=>2))){
            $this->error('操作失败');
        }else{
			$g_name = M('GoodsInfo')->where(array('g_id'=>$this->_post('gid')))->getField('g_name');
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品加入回收站",'商品ID为：'.$this->_post('gid').'-'.$g_name));		
            $this->success('操作成功');
        }
    }

    /**
     * 删除商品操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function doGoodsisDel() {
        $ary_post = $this->_post();
        //验证是否指定要删除的商品ID
        if (!isset($ary_post['gid']) || empty($ary_post['gid'])) {
            $this->error("请指定要删除的商品ID。");
        }

		$tmp_sql = 'select group_concat(g_name) as g_name from fx_goods_info where g_id in('.$ary_post['gid'].')';
		$g_name = D('GoodsInfo')->query($tmp_sql);
        $ary_g_id = explode(',',$ary_post['gid']);
		//判断是否有未发货订单
        $ary_where = array(
            'oi_ship_status'=>'0',
            'g_id'=>array('in',$ary_g_id),
            'o.o_status'=>array('neq',2)
        );
		$field = 'distinct(g_sn),oi_g_name,o.o_pay_status,o.o_id,ors.or_finance_verify';
		$order = 'ors.or_create_time desc';
		$ary_where['_string'] =' (o.o_payment in(3,6) and o.o_pay_status=0) or (o.o_pay_status=1 ) ';
		$is_exist_order = D('OrdersItems')->GetOrderShipItem($ary_where,$field,$order);
		if(!empty($is_exist_order)){
			$this->error("部分商品有已支付未发货订单存在不允许删除。商品为：".$is_exist_order[0]['oi_g_name'].'('.$is_exist_order[0]['g_sn'].')');exit;
		}
        $goods = M('goods', C('DB_PREFIX'), 'DB_CUSTOM');
        $goods->startTrans();
        foreach ($ary_g_id as $g_id){
            //需要验证此商品是否存在
            $ary_result = D("Goods")->where(array('g_id' => $g_id))->find();
            if (empty($ary_result) && !is_array($ary_result)) {
                $this->error("该商品不存在,请重试...");
            }
            //需要验证此商品是否存在被组合商品占用
            if (!empty($ary_result['g_is_combination_goods']) && $ary_result['g_is_combination_goods'] == '1') {
                $this->error("该商品为组合商品,不允许删除...");
            }
            //需要验证此商品是否存在未发货订单
            
            //商品资料的删除是物理删除，并记录删除人ID，需要操作以下表：
            //商品基本资料表，商品详细资料表，规格表，商品分类关联表，商品属性关联表，商品图片表
            //规格-价格关联表


            $ary_goods_info = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_info) {
                $goods->rollback();
                $this->error("删除商品详细资料失败,请重试...");
                exit;
            }
            $ary_res = $goods->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_res) {
                $goods->rollback();
                $this->error("删除商品基本信息失败,请重试...");
                exit;
            }
            $ary_goods_pictures = M('goods_pictures', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_pictures) {
                $goods->rollback();
                $this->error("删除商品图片失败,请重试...");
                exit;
            }
            $ary_goods_products = M('goods_products', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_goods_products) {
                $goods->rollback();
                $this->error("删除规格商品失败,请重试...");
                exit;
            }
            $ary_related_goods_spec = M('related_goods_spec', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_related_goods_spec) {
                $goods->rollback();
                $this->error("删除商品属性失败,请重试...");
                exit;
            }
            $ary_related_goods_category = M('related_goods_category', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_related_goods_category) {
                $goods->rollback();
                $this->error("删除该商品分类失败,请重试...");
                exit;
            }
            //删除收藏表的商品
            $ary_related_goods_collect = M('collect_goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            if (FALSE === $ary_related_goods_collect) {
                $goods->rollback();
                $this->error("删除该被收藏的商品失败,请重试...");
                exit;
            }
			
			//删除商品分组
			M('related_goods_group', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $g_id))->delete();
            $array_goods_modify_log = array();
            $array_goods_modify_log["g_id"] = $g_id;
            $array_goods_modify_log["pdt_id"] = 0;
            $array_goods_modify_log["pdt_sn"] = '';
            $array_goods_modify_log["u_id"] = $_SESSION["Admin"];
            $array_goods_modify_log["gpml_desc"] = '删除商品，商品编码为' . $ary_result['g_sn'];
            $array_goods_modify_log["gpml_create_time"] = date("Y-m-d H:i:s");
            if (false === D("GoodsProductsModifyLog")->add($array_goods_modify_log)) {
                D("GoodsProductsModifyLog")->rollback();
                $this->errorMsg("记录删除规格日志失败。");
            }
            
            //删除分组里的该商品
            $delete = D('RelatedGoodsGroup')->where(array('g_id'=>$g_id))->delete();
			D('ThdTopItems')->where(array('g_id'=>$g_id))->delete();
			
        }
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除商品成功",'删除商品ID:'.$g_id.'-商品名称:'.$g_name[0]['g_name']));      
        $goods->commit();
        $this->success("删除商品成功");
    }

    /**
     * 商品批量操作
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function isBatGoods() {
        $ary_post = $this->_post();
        if (!empty($ary_post['gid']) && isset($ary_post['gid'])) {
            $where = array();
            $data = array();
            $data[$ary_post['field']] = $ary_post['val'];
            $where['g_id'] = array('in', $ary_post['gid']);
			//$gid = implode(',',$ary_post['gid']);
			$tmp_sql = 'select group_concat(g_name) as g_name from fx_goods_info where g_id in('.$ary_post['gid'].')';
			$g_name = D('GoodsInfo')->query($tmp_sql);
            $ary_result = D("ViewGoods")->where($where)->data($data)->save();
            if (FALSE !== $ary_result) {
				if($ary_post['val'] == 0){
					$str_val = '批量加入回收站';
				}else{
					$str_val = '批量移除回收站';
				}
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品批量操作",'商品批量操作'.$str_val.',操作商品'.':'.$ary_post['gid'].'-'.$g_name[0]['g_name']));     
                $this->success("操作成功");
            } else {
                $this->error("操作失败");
            }
        } else {
            $this->error("请选择需要操作的商品");
        }
    }

    /**
     * 高级搜索
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-20
     */
    public function getGoodsCategory() {
        $category = M("GoodsCategory", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_post = $this->_post();
        $ary_cy = $category->where("gc_status='1'")->select();
        $list = array();
        if (!empty($ary_cy) && is_array($ary_cy)) {
            $list = D("ErpProducts")->toFormatTree($ary_cy, 'gc_name', 'gc_id', 'gc_parent_id');
        }
        $brand = M("GoodsBrand", C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_bd = $brand->where("gb_status='1'")->select();
        $this->assign("category", $list);
        $this->assign("filter", $ary_post);
//        echo "<pre>";print_r($list);exit;
        $this->assign("brand", $ary_bd);
        $this->display();
    }

    /**
     * 商品详情页
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-20
     */
    public function pageDetail() {
        $this->getSubNav(3, 0, 30, "商品详情");
        $ary_erp = D('SysConfig')->getCfgByModule('GY_ERP_API');
        $this->setTitle(' - ' . L('MENU2_0'), 'getGoodsDetails');
        $ary_get = $this->_get();

        //验证商品ID参数传入的正确性
        if (!isset($ary_get['gid']) || !is_numeric($ary_get['gid'])) {
            $this->error("非法的商品ID参数传入。");
        }
        $int_g_id = $ary_get['gid'];

        //获取商品基本信息，并验证商品是否存在
        $array_cond = array("g_id" => $int_g_id);
        $array_goods = D("Goods")->where($array_cond)->find();
        if (!is_array($array_goods) || empty($array_goods)) {
            $this->error("商品资料不存在。");
        }

        //获取商品的详细信息
        $array_goods_info = D("GoodsInfo")->where($array_cond)->find();
        $ary_goods = array_merge($array_goods_info, $array_goods);

        //获取此商品的品牌数据
        $ary_goods["gb_name"] = "";
        if ($ary_goods["gb_id"] > 0) {
            $ary_goods["gb_name"] = D("GoodsBrand")->where(array("gb_id" => $ary_goods["gb_id"]))->getField("gb_name");
        }

        //获取此商品的类型及类型名称
        $ary_goods["gt_name"] = "";
        if ($ary_goods["gt_id"] > 0) {
            $ary_goods["gt_name"] = D("GoodsType")->where(array("gt_id" => $ary_goods["gt_id"]))->getField("gt_name");
        }

        //获取此商品的规格数据，这里去掉分页
        $array_pdt_cond["g_id"] = $int_g_id;
        $array_pdt_cond["pdt_status"] = 1;
        $ary_pdt = D("GoodsProductsTable")->where($array_cond)->order("pdt_id asc")->select();
        if (!empty($ary_pdt) && is_array($ary_pdt)) {
            //循环，处理SKU的销售属性数据
            foreach ($ary_pdt as $keypdt => $valpdt) {
                $ary_pdt[$keypdt]['specName'] = D('GoodsSpec')->getProductsSpec($valpdt['pdt_id']);
            }
        }

        //获取并处理商品资料图片
        $ary_pic = array();
        if ("" != $ary_goods['g_picture']) {
            $ary_pic[]['gp_picture'] = $ary_goods['g_picture'];
        }
        $ary_picresult = D("GoodsPictures")->where(array('g_id' => $int_g_id))->order(array("gp_order" => 'asc'))->getField('gp_id,gp_picture,gp_order');
        if (!empty($ary_picresult) && is_array($ary_picresult)) {
            $ary_pic = array_merge($ary_pic, $ary_picresult);
        }
        foreach ($ary_pic as $key => $val) {
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){
				$ary_pic[$key]['gp_picture'] = D('QnPic')->picToQn($val['gp_picture']);
			}else{
				if(strpos($val['gp_picture'],'http://')>=0){
					$ary_pic[$key]['gp_picture'] = $val['gp_picture'];
				}else{
					$ary_pic[$key]['gp_picture'] = '/' . ltrim($val['gp_picture'], '/');
				}
			}
        }

        //获取并处理商品的分类
        $array_gc_ids = D("RelatedGoodsCategory")->where(array("g_id" => $int_g_id))->getField("gc_id", true);
        $ary_goods['gc_name'] = "";
        if (is_array($array_gc_ids) && !empty($array_gc_ids)) {
            $array_cat_cond = array("gc_id" => array("in", $array_gc_ids));
            $array_catnames = D("GoodsCategory")->where($array_cat_cond)->order(array("gc_order" => "asc"))->getField("gc_id,gc_name");
            $ary_goods['gc_name'] = implode("、", $array_catnames);
        }

        //获取此商品的扩展属性数据
        $array_cond = array("g_id" => $int_g_id, "gs_is_sale_spec" => 0);
        $array_unsale_spec = D("RelatedGoodsSpec")->where($array_cond)->order(array("gs_id asc"))->select();
        foreach ($array_unsale_spec as $key => $val) {
            $array_unsale_spec[$key]["gs_name"] = D("GoodsSpec")->where(array("gs_id" => $val["gs_id"]))->getField("gs_name");
        }

        //第三方商品关联处理 - 这里是指淘宝商品
        if (!empty($ary_goods['g_sn'])) {
            $TopGoodsInfo = D('TopGoodsInfo');
            $ary_top = $TopGoodsInfo->where(array('outer_id' => $ary_goods['g_sn']))->find();
            $ary_goods['top_info'] = $ary_top;
        }

        //商品详情内容处理
        $ary_goods["g_desc"] = closeTags(htmlspecialchars_decode(stripslashes($ary_goods['g_desc'])));
		$ary_goods['g_desc']= D('ViewGoods')-> ReplaceItemDescPicDomain($ary_goods['g_desc']);

        $this->assign("pics", $ary_pic);
        $this->assign("array_unsale_spec", $array_unsale_spec);
        $this->assign("products", $ary_pdt);
        $this->assign("good", $ary_goods);
        $this->display();
    }

    /**
     *
     */
    public function doTrdPriceSet() {
        $pid = $this->_get('pid');
        $retail_price_low = $this->_get('retail_price_low');
        $retail_price_high = $this->_get('retail_price_high');

        $TopGoodsInfo = D('TopGoodsInfo');
        $data = array(
            'retail_price_low' => $retail_price_low,
            'retail_price_high' => $retail_price_high
        );
        //上传到淘宝供销平台
        if (empty($pid)) {
            $this->error('设置失败，商品ID为空');
        }

        $res = $TopGoodsInfo->update($pid, $data);
        //保存本地数据
        $TopGoodsInfo->where(array('pid' => $pid))->data($data)->save();
        if ($res) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * 用于某些后台页面的商品选择弹出框
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-1-11
     * @return 返回带查找和结果的DIV
     */
    public function getGoodsSelecter() {
        $Goods = D("ViewGoods");
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['gcid'] = (int) $this->_get('gs_gcid', 'htmlspecialchars', 0);
		$chose['gbid'] = (int) $this->_get('gs_gbid', 'htmlspecialchars', 0);
        $chose['gname'] = $this->_get('gs_gname', 'htmlspecialchars,trim', '');
        $chose['gsn'] = $this->_get('gs_gsn', 'htmlspecialchars,trim', '');
        $chose['g_gifts'] = $this->_get('g_gifts', 'htmlspecialchars,trim', '');
        $chose['g_is_combination_goods'] = $this->_get('g_combo', 'htmlspecialchars,trim', '0');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        //商品分类搜索
        if ($chose['gcid']) {
            $where['fx_goods_category.gc_id'] = array('in', $Goods->getCatesIds($chose['gcid']));
        }
		if($chose['gbid']){
			$where['fx_goods.gb_id'] = $chose['gbid'];
		}
        //商品名称查询
        if ($chose['gname']) {
            $where['fx_goods_info.g_name'] = array('LIKE', '%' . $chose['gname'] . '%');
        }
        //商品编码查询
        if ($chose['gsn']) {
            $where['fx_goods.g_sn'] = array('LIKE', '%' . $chose['gsn'] . '%');
        }
        //是否赠品
        if(empty($chose['g_gifts'])){
            $where['fx_goods.g_gifts'] =  array('neq',1);
        }else{
            $where['fx_goods.g_gifts'] = array('neq',0);
        }
        //组合商品
        $where['fx_goods.g_is_combination_goods'] = $chose['g_is_combination_goods'];
        //上架商品
        $where['fx_goods.g_on_sale'] = 1;

        //设置页面的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $search['cates'] = $Goods->getCates();
		//获得商品品牌
		$search['brands'] = D("GoodsBrand")->where(array("gb_status"=>1))->field('gb_id,gb_name')->order('gb_order asc')->select();
        $count = D("Goods")->GetGoodCount($where);
        $Page = new Page($count, 50);
        $data['page'] = $Page->show();

        $field = array('fx_goods.g_id', 'fx_goods_info.g_picture', 'fx_goods_info.g_name', 'fx_goods_category.gc_name');
        $group = 'fx_goods.g_id';
        $limit['start'] = $Page->firstRow;
        $limit['end'] = $Page->listRows;

        $data['gifts'] = $chose['g_gifts'];
        $data['list'] = D("Goods")->GetGoodList($where, $field, $group, $limit,true);
        // 设置分类等级颜色
        $color = array('blue','orange','red');
        $this->assign('color',$color);
        $this->assign('search', $search); //查询条件
        $this->assign('chose', $chose);  //当前已经选择的
        $this->assign($data);    //赋值数据集，和分页
        $this->display();
    }

    /**
     * 用于某些后台页面的赠品商品选择弹出框
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-1-11
     * @return 返回带查找和结果的DIV
     */
    public function getGoodsSelecterGift() {
        $Goods = D("ViewGoods");
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['gcid'] = (int) $this->_get('gs_gcid', 'htmlspecialchars', 0);
        $chose['gname'] = $this->_get('gs_gname', 'htmlspecialchars,trim', '');
        $chose['gsn'] = $this->_get('gs_gsn', 'htmlspecialchars,trim', '');
        $chose['g_gifts'] = $this->_get('g_gifts', 'htmlspecialchars,trim', '');
        $chose['g_is_combination_goods'] = $this->_get('g_combo', 'htmlspecialchars,trim', '0');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        //商品分类搜索
        if ($chose['gcid']) {
            $where['fx_goods_category.gc_id'] = array('in', $Goods->getCatesIds($chose['gcid']));
        }
        //商品名称查询
        if ($chose['gname']) {
            $where['fx_goods_info.g_name'] = array('LIKE', '%' . $chose['gname'] . '%');
        }
        //商品编码查询
        if ($chose['gsn']) {
            $where['fx_goods.g_sn'] = array('LIKE', '%' . $chose['gsn'] . '%');
        }
        //是否赠品
        if(empty($chose['g_gifts'])){
            $where['fx_goods.g_gifts'] =  array('neq',1);
        }else{
            $where['fx_goods.g_gifts'] = array('neq',0);
        }
        //组合商品
        $where['fx_goods.g_is_combination_goods'] = $chose['g_is_combination_goods'];
        //上架商品
        $where['fx_goods.g_on_sale'] = 1;

        //设置页面的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $search['cates'] = $Goods->getCates();
        $count = D("Goods")->GetGoodCount($where);
        $Page = new Page($count, 50);
        $data['page'] = $Page->show();

        $field = array('fx_goods.g_id', 'fx_goods_info.g_picture', 'fx_goods_info.g_name', 'fx_goods_category.gc_name');
        $group = 'fx_goods.g_id';
        $limit['start'] = $Page->firstRow;
        $limit['end'] = $Page->listRows;

        $data['gifts'] = $chose['g_gifts'];
        $data['list'] = D("Goods")->GetGoodList($where, $field, $group, $limit,true);
        $this->assign('search', $search); //查询条件
        $this->assign('chose', $chose);  //当前已经选择的
        $this->assign($data);    //赋值数据集，和分页
        $this->display();
    }
    
    /**
     * 设置商品积分
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-4-15
     */
    public function GoodPointUpdate() {
        $ary_post = $this->_post();

        if (isset($ary_post['g_id']) && !empty($ary_post['g_id'])) {
            if (!ctype_digit($ary_post['point']))
                $this->error("商品积分需填写正整数");
            $where = array();
            $data = array();
            $data['is_exchange'] = $ary_post['is_exchange'];
            $data['point'] = $ary_post['point'];
            $data['gifts_point'] = $ary_post['gifts_point'];
            $where['g_id'] = $ary_post['g_id'];
			if($data['is_exchange'] == 0){
				$data['gifts_point'] = 0;
			}
            $ary_result = M("GoodsInfo", C('DB_PREFIX'), 'DB_CUSTOM')->where($where)->data($data)->save();
            //echo M()->getLastSql();exit();
            if (FALSE !== $ary_result) {
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品积分设置成功",serialize($data)));
				D('Gyfx')->deleteOneCache('goods_info','point', array('g_id'=>$ary_post['g_id']), null,3600);
                $this->success("商品积分设置成功");
            } else {
                $this->error("商品积分设置失败");
            }
        } else {
            $this->error("缺少 g_id");
        }
    }

    /**
     * 商品积分设置ajax打开页面
     * @author czy<chenzongyao@guanyisoft.com>
     * @date 2013-4-15
     */
    public function setGoodPoint() {

        $g_id = intval($this->_post('g_id'));
        if (isset($g_id)) {
            $GoodsInfo = M("GoodsInfo", C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_data = $GoodsInfo->field('g_name,point,gifts_point,is_exchange')->where(array("g_id" => $g_id))->find();
        }
        $this->assign('ary_data', $ary_data);
        $this->display('setPoint');
    }

    /**
     * ajax异步修改商品销量
     *
     * 此方法为后台商品列表ajax修改商品销量调用方法，需要传入商品ID，修改后的销量
     *
     * @author Mithern
     * @date 2013-07-26
     * @version 1.0
     */
    public function setItemSaleNumbers() {
        //验证是否传入商品ID
        if (!isset($_POST["int_goods_id"]) && !is_numeric($_POST["int_goods_id"])) {
            echo json_encode(array("status" => false, "error_code" => "INVALID-GOODS-ID", "message" => "非法的参数传入：商品ID不合法。"));
            exit;
        }
        $int_goods_id = $_POST["int_goods_id"];

        //验证是否传入要修改的销量，并且验证是否是数字
        if (!isset($_POST["int_sale_num"]) || !is_numeric($_POST["int_sale_num"])) {
            echo json_encode(array("status" => false, "error_code" => "INVALID-GOODS-SALENUM", "message" => "非法的参数传入：商品销量参数不合法。"));
            exit;
        }
        $int_g_salenum = $_POST["int_sale_num"];

        //验证要修改的商品是否存在
        $array_update_cond = array("g_id" => $int_goods_id);
        $mixed_update_result = D("GoodsInfo")->where($array_update_cond)->save(array("g_salenum" => $int_g_salenum, "g_update_time" => date("Y-m-d H:i:s")));
        if (false === $mixed_update_result) {
            echo json_encode(array("status" => false, "error_code" => "UPDATE-SALENUM-ERROR", "message" => "商品销量修改失败"));
            exit;
        }
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品销量修改成功",serialize(array("g_salenum" => $int_g_salenum, "g_update_time" => date("Y-m-d H:i:s")))));
        //TODO:此处没有对影响的行数进行验证
        echo json_encode(array("status" => true, "error_code" => "SUCCESS", "message" => "商品销量修改成功"));
        exit;
    }

    /**
     * 商品上下架
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-19
     */
    public function doGoodsOnSale() {
        $ary_post = $this->_post();
        if (!empty($ary_post['spdm']) && isset($ary_post['spdm'])) {
            $val = '';
            if (!empty($ary_post['val']) && $ary_post['val'] == '1') {
                $val = '2';
                $str = '下架';
            } else {
                $val = '1';
                $str = '上架';
            }
            $ary_data = array();
            $ary_where = array();
            $ary_where['g_sn'] = $ary_post['spdm'];
            $ary_data['g_on_sale'] = $val;
            // $ary_stock = M('GoodsProducts',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
            // echo "<pre>";print_r($ary_stock);exit();
            $ary_result = M("goods", C('DB_PREFIX'), 'DB_CUSTOM')->where($ary_where)->data($ary_data)->save();
            if (FALSE !== $ary_result) {
                // if(isset($ary_stock['pdt_stock']) && $ary_stock['pdt_stock'] == 0){
                //     $this->success($str . "成功,该商品库存为0");
                // }else{
					$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"商品上下架",'商品代码'.$ary_post['spdm'].',状态:'.$str));
                    $this->success($str . "成功");
                // }
            } else {
                $this->success($str . "失败");
            }
        } else {
            $this->error("商品编码不能为空");
        }
    }

    /**
     * 判断库存是否为空
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-16
     */
    public function ajaxCheckStock(){
        $ary_post = $this->_post();
        if(isset($ary_post['spdm']) && !empty($ary_post['spdm'])){
            $ary_where = array(
                'g_sn' => $ary_post['spdm'],
                'g_id' => $ary_post['g_id']
                );
            $ary_stock = M('GoodsProducts',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
            if(empty($ary_stock)){
                $this->ajaxReturn(array('status'=>1,'info'=>'不存在该条数据'),'JSON');
            }
            if(isset($ary_stock['pdt_stock']) && !empty($ary_stock['pdt_stock'])){
                $this->ajaxReturn(array('status'=>2,'info'=>'有库存'),'JSON');
            }
            if(isset($ary_stock['pdt_stock']) && empty($ary_stock['pdt_stock'])){
                $this->ajaxReturn(array('status'=>3,'info'=>'无库存'),'JSON');
            }
        }else{
            $this->ajaxReturn(array('status'=>1,'info'=>'商品编码不能为空'),'JSON');
        }
    }
    /**
     * 批量判断库存是否为0
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-16
     */
    public function ajaxBatCheckStock(){
        $ary_post = $this->_post();
        if(!empty($ary_post['gid']) && !empty($ary_post['spdms'])){
            $ary_gid = explode(',',$ary_post['gid']);
            $ary_spdms = explode(',',$ary_post['spdms']);
            $total = max(count($ary_gid),count($ary_spdms));
            $tag = true;
            for($i=0;$i<$total;$i++){
                if(isset($ary_gid[$i]) && isset($ary_spdms[$i]) && !empty($ary_gid[$i]) && !empty($ary_spdms[$i])){
                    $ary_where = array('g_sn'=>$ary_spdms[$i],'g_id'=>$ary_gid[$i]);
                    $result = M('GoodsProducts',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->find();
                    if(isset($result['pdt_stock']) && $result['pdt_stock'] == 0){
                        $tag = false;break;
                    }
                }
            }
            if($tag){
                $this->ajaxReturn(array('status'=>2,'info'=>'有库存'),'JSON');
            }else{
                $this->ajaxReturn(array('status'=>3,'info'=>'无库存'),'JSON');
            }
        }else{
            $this->ajaxReturn(array('status'=>1,'info'=>'商品编码不能为空'),'JSON');
        }
    }

    /**
     * 批量修改商品价格
     */
    public function doBatchSetPrice(){
        $ary_post = $this->_post();
        $gid = $ary_post['setPriceGid'];
        if(!empty($gid)){
            if(!empty($ary_post['pdt_set_sale_price']) && is_numeric($ary_post['pdt_set_sale_price'])) $data['pdt_sale_price'] = $ary_post['pdt_set_sale_price'];
            if(!empty($ary_post['pdt_set_cost_price']) && is_numeric($ary_post['pdt_set_cost_price'])) $data['pdt_cost_price'] = $ary_post['pdt_set_cost_price'];
            if(!empty($ary_post['pdt_set_market_price']) && is_numeric($ary_post['pdt_set_market_price'])) $data['pdt_market_price'] = $ary_post['pdt_set_market_price'];
            if(!empty($ary_post['pdt_set_weight']) && is_numeric($ary_post['pdt_set_weight'])) $data['pdt_weight'] = $ary_post['pdt_set_weight'];
            if(!empty($ary_post['pdt_set_least']) && is_numeric($ary_post['pdt_set_least'])) $data['pdt_min_num'] = $ary_post['pdt_set_least'];
            $res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$gid))->save($data);
            if(!$res){
                $this->error("设置失败，请重试！");
            }else{
                if(!empty($data['pdt_sale_price']) && is_numeric($data['pdt_sale_price'])) $array_save_data['g_price'] = $data['pdt_sale_price'];
                if(!empty($data['pdt_market_price']) && is_numeric($data['pdt_market_price'])) $array_save_data['g_market_price'] = $data['pdt_market_price'];
                if(!empty($data['pdt_weight']) && is_numeric($data['pdt_weight'])) $array_save_data['g_weight'] = $data['pdt_weight'];
                if(!empty($array_save_data) && is_array($array_save_data)){
                    $result = D("GoodsInfo")->where(array("g_id"=>$gid))->save($array_save_data);
                    if(!$result){
                        $this->error("设置失败，请重试！");
                    }
                }
                $this->success("设置成功！",array('确定'=>U('Admin/Products/pageList')));
            }
        }else{
            $this->error("设置失败！");
        }
    }

    public function updateDiscount(){
        //验证是否选择商品
        if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
            echo json_encode(array("status"=>false,"msg"=>"请选择您要修改折扣率的商品。"));
            exit;
        }
        $array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
        $g_discount = $_POST["g_discount"];
        D("GoodsInfo")->startTrans();
        foreach($array_goods_ids as $key => $val){
            $array_where_data = array();
            $array_where_data["g_id"] = $val;
            if(false ===  D("GoodsInfo")->where($array_where_data)->setField('g_discount',$g_discount)){
                echo json_encode(array("status"=>false,"msg"=>"修改商品折扣率的时候出错。"));
                D("GoodsInfo")->rollback();
                exit;
            }
        }
        D("GoodsInfo")->commit();
        echo json_encode(array("status"=>true,"msg"=>"操作成功"));
        exit;
    }

}
