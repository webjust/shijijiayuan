<?php

/**
 * 选购中心控制器
 *
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-13
 * @package Action
 * @subpackage Ucenter
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ProductsAction extends CommonAction {

    /**
     * 本控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 选购中心默认页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    public function index() {
        $this->redirect(U('Ucenter/Products/pageList'));
    }

    /**
     * 用户中心快速订货页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-14
     */
    public function pageList() {
        $this->getSubNav(1, 0, 10);
        $this->getSeoInfo('-' . L('TAKE_ORDERS'));
        $goods = D('ViewGoods');

        //接收搜索信息 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //当前已选择的条件
        //全部待择条件 ---------------------------------------------------------
        //gtid => 类型ID; gcid=>分类ID; gbid=>品牌ID;
        //gname=> 商品名称关键词; gsn=>商品编码关键词;
        //price1=> 价格区间 price2=>价格区间
        //onum => 排序销量 oprice=>排序价格 otime=>排序上架时间 多个排序仅同时存在一种生效
        //stock => 仅显示有库存
        // -------------------------------------------------------------------
        //gtid=$chose[gtid]&gcid=$chose[gcid]&gbid=$chose[gbid]&gname=$chose[gname]&gsn=$chose[gsn]&price1=$chose[price1]&price2=$chose[price2]&onum=$chose[onum]&oprice=$chose[oprice]&otime=$chose[otime]&stock=$chose[stock]

        $chose = array();
        $chose['gtid'] = (int) $this->_get('gtid', 'htmlspecialchars', 0);
        $chose['gcid'] = (int) $this->_get('gcid', 'htmlspecialchars', 0);
        $chose['gbid'] = (int) $this->_get('gbid', 'htmlspecialchars', 0);
        $chose['gname'] = $this->_get('gname', 'trim', '');
        $chose['gsn'] = $this->_get('gsn', 'htmlspecialchars,trim', '');
        $chose['price1'] = $this->_get('price1', 'htmlspecialchars', '');
        $chose['price2'] = $this->_get('price2', 'htmlspecialchars', '');
        $chose['onum'] = $this->_get('onum', 'htmlspecialchars', '');
        $chose['oprice'] = $this->_get('oprice', 'htmlspecialchars', '');
        $chose['otime'] = $this->_get('otime', 'htmlspecialchars', '');
        $chose['stock'] = $this->_get('stock', 'htmlspecialchars', 0);
		$chose['stock_num'] = $this->_get('stock_num', 'htmlspecialchars', 0);
		$chose['more'] = $this->_get('more', 'htmlspecialchars', '0');
		$chose['ajax'] = $this->_get('ajax', 'htmlspecialchars', '0');
		$chose['combo'] = $this->_get('combo', 'htmlspecialchars', '0');
        $where = array();
        $order = array();
        $pdt_order = array();
        //获取搜索数据
        $search['brands'] = $goods->getBrands();
        $search['cates'] = $goods->getCates();
        $search['types'] = $goods->getTypes();

        //拼接查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //商品是否启用
        $where['g_status'] = 1;
        
        //上架
        $where['g_on_sale'] = 1;
		//去除赠品
        $where['g_gifts'] = array('neq','1');
        
        //显示非处方药

        //商品类型搜索
        if ($chose['gtid']) {
            $where['gt_id'] = $chose['gtid'];
        }
        //商品分类搜索
        if ($chose['gcid']) {
            $where['gc_id'] = array('in', $goods->getCatesIds($chose['gcid']));
        }
        //商品品牌搜索
        if ($chose['gbid']) {
            $where['gb_id'] = $chose['gbid'];
        }
        //商品名称查询
        if ($chose['gname']) {
            $where['g_name'] = array('LIKE', '%' . $chose['gname'] . '%');
        }
        //商品编码查询
        if ($chose['gsn']) {//编码规则/  \允许斜杠字符存在
            $where['g_sn'] = array('LIKE', '%' . addslashes($chose['gsn']) . '%');
        }
        //价格查询
        if ('' == $chose['price1'] && '' == $chose['price2']) {
            //啥都没输入
        }
        elseif ('' !== $chose['price2'] && '' == $chose['price1']) {
                //只输入了最高价
                $where['goods_info.ma_price'] = array('ELT',$chose['price2']);
        }
        elseif ('' !== $chose['price1'] && '' == $chose['price2']) {
                //只输入了最低价
                $where['goods_info.mi_price'] = array('EGT',$chose['price1']);
        }
        else {
            //输入了价格区间
            //调整一下价格区间，保证第一个是小值第二个是大值
            if ($chose['price1'] > $chose['price2']) {
                $tmp = $chose['price2'];
                $chose['price2'] = $chose['price1'];
                $chose['price1'] = $tmp;
            }
            //拼接条件...
            $where['_string'] = " ( goods_info.mi_price >= '{$chose['price1']}' and goods_info.ma_price <= '{$chose['price2']}')";
        }
      	$nowTime = date('Y-m-d H:i:s');
        //	是否是组合商品
        if($chose['combo'] == '1'){
        	$where['g_is_combination_goods'] = 1;
  			$where['g_off_sale_time'] = array('EGT', $nowTime);
			$where['g_on_sale_time'] = array('ELT', $nowTime);      	
        }else{
            if(empty($where['_string'])){
                $where['_string']  ='(g_is_combination_goods=1 and g_off_sale_time>=now() and g_on_sale_time<=now()) or (g_is_combination_goods=0 and g_is_prescription_rugs = 0)';
            }else{
                $where['_string']  .='and ((g_is_combination_goods=1 and g_off_sale_time>=now() and g_on_sale_time<=now()) or (g_is_combination_goods=0 and g_is_prescription_rugs = 0))';
            }

        }

        //是否有货
        if (1 == $chose['stock']) {
			$where['g_stock'] = array('GT', 0);
        }
        
        //库存数量小于
        if(!empty($chose['stock_num'])){
			$stocks = D('SysConfig')->getCfgByModule('GY_STOCK');
        	//$where['g_stock'] = array('ELT', $chose['stock_num']);
			$where['g_stock'] = array('ELT', $stocks['STOCK_NUM']);
        }
        
        //拼接排序条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //销量排序
        if ('up' == $chose['onum']) {
			$order['g_salenum'] = 'asc';
        } elseif ('down' == $chose['onum']) {
			$order['g_salenum'] = 'desc';
        }
        //价格排序
        if ('up' == $chose['oprice']) {
            $order['g_price'] = 'asc';
            $pdt_order['pdt_sale_price'] = 'asc';
        } elseif ('down' == $chose['oprice']) {
            $order['g_price'] = 'desc';
            $pdt_order['pdt_sale_price'] = 'desc';
        }
        //上架时间排序
        if ('up' == $chose['otime']) {
            $order['g_on_sale_time'] = 'asc';
        } elseif ('down' == $chose['otime']) {
            $order['g_on_sale_time'] = 'desc';
        }
		
		//对商品的搜索条件进行特殊化处理，用于祛除mysql视图导致的查询问题
		// edit by Mithern 2013-06-18
		if(isset($where["gc_id"]) && !empty($where["gc_id"])){
			//如果是根据商品分类进行搜索，则在上面处理完商品分类搜索条件以后
			//根据搜索条件，先获取这些商品分类关联的商品ID，将关联的商品ID查出
			//然后使用IN方法对商品视图模型做进一步查询操作
			//TODO：ThinkPHP视图模型性能待评估（其使用连表查询方式）
			$arr_where = array();
			$gcids = implode(",",$where["gc_id"][1]);
			$arr_where['gc_id'] = array($where["gc_id"][0],$gcids);
			$array_gids = D("RelatedGoodsCategory")->distinct(true)->where($arr_where)->getField("g_id",true);
			if(is_array($array_gids) && count($array_gids) > 0){
				$where["g_id"] = array("IN",$array_gids);
			}else{
				//表示找不到商品数据
				$where["g_id"] = array("IN",array(-1));
			}
			unset($where["gc_id"]);
		}
		
		$goodsModel = D("UcenterQuickOrderGoodsView");


        //获取商品 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $count = $goodsModel->where($where)->count('goods.g_id');
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $list = $goodsModel->where($where)->order($order)->limit($obj_page->firstRow . ',' . $obj_page->listRows)->select();
        //echo $goodsModel->getLastSql();exit;
		//获取货品
        //$products = M("view_products",C('DB_PREFIX'),'DB_CUSTOM');
        $products = D("GoodsProductsTable");
        $goodsSpec = D('GoodsSpec');
        //$price = D('Price');
        //获取货品价格等额外数据
        $member = session('Members');
        
        $obj_price = new ProPrice();
        foreach ($list as $key => $data) {
			if($_SESSION['OSS']['GY_QN_ON'] == '1'){
				$list[$key]['g_picture'] = D('QnPic')->picToQn($data['g_picture']);
			}else{
				$list[$key]['g_picture'] = '/' . ltrim($data['g_picture'],'/');
			}
            $list[$key]['authorize'] = D('AuthorizeLine')->isAuthorize($member['m_id'], $data['g_id']);
            //预上架商品
            if($data['g_on_sale'] == 3 && $data['g_on_sale_time']>date('Y-m-d')){
                $list[$key]['PrepOnsale'] = 1;
            }else {
                $list[$key]['PrepOnsale'] = 0;
            }
            $ary_pdt = $products->order($pdt_order)->where(array('g_id' => $data['g_id']))->select();
		   foreach ($ary_pdt as $k => $pdt) {
                //获取其他属性
               $ary_pdt[$k]['specName'] = $goodsSpec->getProductsSpec($pdt['pdt_id']);
			  
			   $ary_price = $obj_price->getPriceInfo($pdt['pdt_id'],$member['m_id']);        
			   $ary_pdt[$k]['gPrice'] = $ary_price['pdt_price'];
			   //货品的库存获取
			   $ary_pdt[$k]["pdt_stock"] = D("GoodsStock")->getProductStockByPdtid($pdt["pdt_id"],$member['m_id']);
               if($ary_pdt[$k]["pdt_stock"]<0){
					$ary_pdt[$k]["pdt_stock"] = 0;
				}
				//删选掉不符合搜索条件的
                //1）价格条件: 设置了任意一个价格区间，并且当前货品价格大于最高价或小于最低价，则从结果中过滤掉本货品
                if (($pdt['pdt_sale_price'] < $chose['price1'] && '' != $chose['price1']) || ($pdt['pdt_sale_price'] > $chose['price2'] && '' != $chose['price2'])) {
                    unset($ary_pdt[$k]);
                }
			}
            $list[$key]['products'] = $ary_pdt;
			if(empty($ary_pdt)){
				unset($list[$key]);
			}
        }
        //库存提示
        $stock_data = D('SysConfig')->getCfgByModule('GY_STOCK');
        $member_level_id = $member['member_level']['ml_id'];
        if(!empty($stock_data['USER_TYPE']) && $stock_data['OPEN_STOCK']==1 ){
            if($stock_data['USER_TYPE']=='all'){
                $stock_data['level'] =true;
            }else{
                $ary_user_level =explode(",",$stock_data['USER_TYPE']);
                $stock_data['level'] = in_array($member_level_id,$ary_user_level);
            }
        }
		//dump($list);die();

        $sys_config = D('SysConfig')->getConfigs('GY_GOODS');
        $is_on_mulitiple = ($sys_config['IS_ON_MULTIPLE']['sc_value'])?$sys_config['IS_ON_MULTIPLE']['sc_value']:2;
		
        $this->assign('is_on_mulitiple', $is_on_mulitiple);


        //显示页面 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		if($chose['more']){
			$this->ajaxReturn(array(
				'result' => true,
				'brands' => $search['brands']
			));
		}
		else if ($chose['ajax']){
			$this->ajaxReturn(array(
				'result' => true
			));
		}
		else{
			$this->assign('search', $search); //查询条件
			$this->assign('chose', $chose);  //当前已经选择的
			$this->assign('list', $list);    //赋值数据集
			$this->assign('page', $page);    //赋值分页输出
			$this->assign("stock_data", $stock_data);
			$this->display();
		}
    }

    /**
     * 按商家编码（货号）快速下单页面
     * @author zuo <zuojianghua@gmail.com>
     * @date 2012-12-14
     */
    public function pageQuick() {
        $this->getSubNav(1, 0, 20);
        $this->getSeoInfo('按商品编码快速下单');
        $sysSetting = D('SysConfig');
        $sys_config = $sysSetting->getConfigs('GY_GOODS');
        $is_on_mulitiple = empty($sys_config['IS_ON_MULTIPLE']['sc_value']) ? 2: $sys_config['IS_ON_MULTIPLE']['sc_value'];
        $this->assign('is_on_mulitiple', $is_on_mulitiple);
        $this->display();
    }

    /**
     * 商品展示页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-25
     * @todo 此处应该是跳转到展厅相应页面
     */
    public function pageView() {
        $this->error('待开发');
        //$this->redirect(U(''));
    }

    

    /**
     * 判断商品编码是否存在，以及该用户是否有权限购买
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-28
     */
    public function doCheckPdtsn() {
        layout(FALSE);
        $pdtsn = $this->_get('pdtsn');
        //查询商家编码符合，并且状态启用，并且库存大于0的
        $ary_products = D('ViewProducts')
                ->where(array('pdt_sn' => $pdtsn, 'pdt_status' => 1, 'g_status' => 1, 'pdt_stock' => array('GT', 0)))
                ->find();
        $pdt_min_num = D("GoodsProducts")->field('pdt_min_num')->where(array('pdt_sn' => $pdtsn, 'pdt_status' => 1, 'g_status' => 1, 'pdt_stock' => array('GT', 0)))->find();

        if ($ary_products['g_on_sale'] == 2) {
            $this->ajaxReturn(array('result'=>false,'msg'=>'<label class="error">该货品已下架</label>'));
            exit;
        }
        if (false == $ary_products) {
            $this->ajaxReturn(array('result' => false, 'msg' => '<label class="error">编码错误或无库存</label>'));
            exit;
        }

        //检查授权线
        $authorLine = D('AuthorizeLine')->isAuthorize($_SESSION['Members']['m_id'], $ary_products['g_id']);

        if (false == $authorLine) {
            $this->ajaxReturn(array('result' => false, 'msg' => '<label class="error">你无权购买该商品</label>'));
            exit;
        }
        $this->ajaxReturn(array(
            'result' => true,
            'data' => $ary_products['pdt_id'],
            'max' => $ary_products['pdt_stock'],
            'pdt_min_num' => ($pdt_min_num['pdt_min_num'] > 0 ? $pdt_min_num['pdt_min_num']:0 ),
            'msg' => "<div>商品名称：{$ary_products['g_name']}。 实时库存：{$ary_products['pdt_stock']}</div>"
        ));
    }

    /**
     * 判断商品编码是否存在，以及该用户是否有权限购买
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-31
     */
    public function getCheckPdtsn(){
        layout(FALSE);
        $pdtsn = $this->_get('pdtsn');

        //查询商家编码符合，并且状态启用，并且库存大于0的
        $ary_products = D('ViewProducts')
                ->where(array('pdt_sn' => $pdtsn, 'pdt_status' => 1, 'g_status' => 1, 'pdt_stock' => array('GT', 0)))
                ->find();
        if ($ary_products['g_on_sale'] == 2) {
            $this->ajaxReturn('该货品已下架');
            exit;
        }
        if (false == $ary_products) {
            $this->ajaxReturn('编码错误或无库存');
            exit;
        }
        //检查授权线
        $authorLine = D('AuthorizeLine')->isAuthorize($_SESSION['Members']['m_id'], $ary_products['g_id']);

        if (false == $authorLine) {
            $this->ajaxReturn('你无权购买该商品');
            exit;
        }
        $this->ajaxReturn(true);
    }

 
    /**
     * @param 加入收藏选择规格页面
     * @author wangguibin  <wangguibin@guanyisoft.com>
     * @version 7.1
     * @modify 2013-4-27
     * @return mixed array
     */
    public function doCollect(){
        $g_id =  $this->_post("g_id");
        $products = D("GoodsProducts");
        $ary_product_feild = array('pdt_id','pdt_stock');
        $where = array('g_id'=>$g_id);
        
        $ary_pdt = $products->field($ary_product_feild)->where($where)->select();
        $goodsSpec = D('GoodsSpec');
        $skus = array();
        $guigeis = array();
        
        if(is_array($ary_pdt) && !empty($ary_pdt)){
    			if(count($ary_pdt) == '1'){
    				$pdtInfo = $ary_pdt[0];
    			}else{
    			        foreach($ary_pdt as $keypdt=>$valpdt){
	                    //获取其他属性
	                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
	                    if(!empty($specInfo['color'])){
	                        if(!empty($specInfo['color'][1])){
	                            $skus[$specInfo['color'][0]][] = array(
	                                                                    'info'=>$specInfo['color'][1],
	                                                                    'gs_id'=>$specInfo['color'][2],
	                                                                    'gsd_id'=>$specInfo['color'][3],
	                                                                  );
	                        }
	                    }
	                    if(!empty($specInfo['size'])){
	                        if(!empty($specInfo['size'][1])){
	                           $skus[$specInfo['size'][0]][] = array(
	                                                                   'info'=>$specInfo['size'][1],
	                                                                   'gs_id'=>$specInfo['size'][2],
	                                                                   'gsd_id'=>$specInfo['size'][3],
	                                                                );
	                        }
	                    }
	                    if(isset($specInfo['guigei'][$valpdt['pdt_id']]) && !empty($specInfo['guigei'][$valpdt['pdt_id']])) {
	                        $guigeis[$valpdt['pdt_id']] = $specInfo['guigei'][$valpdt['pdt_id']];
	                    }
	                    $ary_pdt[$keypdt]['specName']  = $specInfo['spec_name'];
	                    $ary_pdt[$keypdt]['skuName']  = $specInfo['sku_name'];
	                }   				
    			}
            $ary_picresult = D("GoodsPictures")->where(array('g_id'=>$ary_goods['g_id']))->select();
            if(!empty($ary_picresult) && is_array($ary_picresult)){
                $ary_pic = $ary_picresult;
            }
          
          }
          else $ary_pdt = array();
          
       // print_r(array_flip($guigeis));exit;
        $point = 0;
        $member = session("Members");
       
         if(!empty($member['m_id'])){
            $ary_point = M("members",C('DB_PREFIX'),'DB_CUSTOM')->field('total_point,freeze_point')->where(array('m_id'=>$member['m_id']))->find();
            $point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
         }
         
         $this->assign("g_id", $g_id);
         $this->assign("ary_pdt", $ary_pdt);
		 if(empty($skus) && count($ary_pdt)>1){
			$pdtInfo = $ary_pdt[0];
		 }
         $this->assign("pdtInfo", $pdtInfo);
         $this->assign("guigei",array_flip($guigeis)); 
         $this->assign("point", $point); 
         $this->assign("skus", $skus);
         $this->display();
    }
    
    /**
     * 商品详情页
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-3-20
     */
    public function pageDetail() {
        $this->getSubNav(3, 0, 30, "商品详情");
        $ary_erp = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);
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
            $ary_pic[$key]['gp_picture'] = '/' . ltrim($val['gp_picture'], '/');
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

        $this->assign("pics", $ary_pic);
        $this->assign("array_unsale_spec", $array_unsale_spec);
        $this->assign("products", $ary_pdt);
        $this->assign("good", $ary_goods);
        $this->display();
    }
    
}
