<?php

/**
 * 后台团购控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.4
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-08-22
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class GroupbuyAction extends AdminAction {

    /**
     * 后台团购控制器初始化
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
        $this->setTitle(' - ' . L('MENU4_2'));
    }

    /**
     * 后台默认控制器，重定向到列表页
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function index() {
        $this->redirect(U('Admin/Groupbuy/pageList'));
    }
    
    /**
     * 后台添加团购
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function pageSet() {
    	$GroupbuySet = M('groupbuy_set',C('DB_PREFIX'),'DB_CUSTOM');
    	$ary_data = $GroupbuySet->find();
    	$ary_city_where = array();
    	$ary_city_where['_string'] = 'find_in_set(cr_id,'.'"'.$ary_data['gs_related_city'].'"'.')';
    	$ary_city = M('city_region',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_city_where)->field('cr_id,cr_name')->select();
    	$ary_data['gs_related_price'] = unserialize($ary_data['gs_related_price']);
    	$ary_data['citys'] = $ary_city;
    	$this->assign("ary_data",$ary_data);
        $this->getSubNav(5, 2, 30);
		//取出关联广告图片
		$ary_ads = D('RelatedGroupbuyAds')->order('sort_order asc')->select();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			$ary_ad['ad_pic_url'] = D('QnPic')->picToQn($ary_ad['ad_pic_url']);
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        $this->assign('ary_ads', $ary_ad_infos);
        $this->display();
    }  
    
	/**
     * 新增一条团购配置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-23
     */
    public function doAddSet() {
        $ary_data = $this->_post();
        $GroupbuySet = M('groupbuy_set',C('DB_PREFIX'),'DB_CUSTOM');
		$ary_data['gs_related_city'] = trim($ary_data['goods']['g_related_goods_ids'], ",");
		$price = array();
		$price['min_price'] = $ary_data['min_price'];
		$price['max_price'] = $ary_data['max_price'];
		$price_info = array();
		foreach($ary_data['prices_from'] as $key=>$val){
			$price_info[] = array(
				'from'=>$val,
				'to'=>$ary_data['prices_to'][$key]
			);
		}
		$price['price'] = $price_info;
		$ary_data['gs_related_price'] = serialize($price);
		$ary_data['gs_timeshow_status'] = $ary_data['gs_timeshow_status'];
		$ary_data['gs_update_time'] = date('Y-m-d H:i:s');
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $int_gs_id = $GroupbuySet->getField('gs_id');
        if($int_gs_id){
        	 $res_return = $GroupbuySet->where(array('gs_id'=>$int_gs_id))->data($ary_data)->save();
        }else{
        	 $ary_data['gs_create_time'] = date('Y-m-d H:i:s');
        	 $res_return = $GroupbuySet->data($ary_data)->add();
        }
		M('')->startTrans();
		//插入广告信息
		//关联广告图片
		$RelatedGroupbuyAds = D('RelatedGroupbuyAds');
		//先删除关联的分类和品牌信息
		M()->query('TRUNCATE table `fx_related_groupbuy_ads`');
		//广告图片1
		for($i=0;$i<5;$i++){
			if(!empty($ary_data['GY_SHOP_TOP_AD_'.$i])){
				$ary_insert = array();
				$ary_insert = array(
					'sort_order'=>intval($ary_data['sort_order_'.$i]),
					'ad_url'=>$ary_data['GY_SHOP_TOP_AD_'.$i.'_URL']
				);		
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){
					$ary_insert['ad_pic_url'] = $ary_data['GY_SHOP_TOP_AD_'.$i];
				}else{
					$ary_insert['ad_pic_url'] = str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i]));
				}
				$ary_insert['ad_pic_url'] = D('ViewGoods')->ReplaceItemPicReal($ary_insert['ad_pic_url']);
				$res = $RelatedGroupbuyAds->data($ary_insert)->add();
				if(!$res){
					M('')->rollback();
					$this->error('团购广告图片保存失败');
				}				
			}
		}
		M('')->commit();
        if (!$res_return) {
            $this->error('团购设置生成失败');
        } else {
            $this->success('团购设置生成成功', U('Admin/Groupbuy/pageSet'));
        }
    }
    
    /**
     * 后台团购列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function pageList() {
        $this->getSubNav(5, 2, 10);
        $Groupbuy = D('Groupbuy');
        $ary_data = $this->_get();
		//搜索条件处理
		$array_cond = array();
		//如果根据名称进行搜索
		switch ($ary_data['field']){
			case 1:
				$array_cond["fx_groupbuy.gp_title"] = array("LIKE","%" . $ary_data['val'] . "%");
			break;
			case 2:
                if(!empty($ary_data['val'])){
                    $array_cond["gi.g_name"] = array("LIKE","%" . $ary_data['val'] . "%");
                }
			break;
			case 3:
                if(!empty($ary_data['val'])){
                    $array_cond["g.g_sn"] = $ary_data['val'];
                }
			break;
			default:
			break;
		}
		//类目搜索
		if(!empty($ary_data['gcid'])){
			$gc_ids = array();
			$gc_ids[] = intval($ary_data['gcid']);
			//判断有没有二级类目
			$sub_cids = D('Gyfx')->selectAll('groupbuy_category','gc_id', array('gc_parent_id'=>$ary_data['gcid']),$ary_group=null,$ary_limit=null);
			if(!empty($sub_cids)){
				foreach($sub_cids as $sub_cid){
					$gc_ids[] = $sub_cid['gc_id'];
				}
			}
			//dump($gc_ids);die();
			$array_cond["fx_groupbuy.gc_id"] = array('in',$gc_ids);
		}
		//品牌搜索
		if(!empty($ary_data['gbbid'])){
			$array_cond["fx_groupbuy.gbb_id"] = intval($ary_data['gbbid']);
		}		
		//如果根据团购的有效期进行搜索
		if(isset($ary_data["gp_start_time"]) && "" != $ary_data["gp_start_time"]){
			$array_cond["gp_start_time"] = array("elt",$ary_data["gp_start_time"]);
		}
		if(isset($ary_data["gp_end_time"]) && "" != $ary_data["gp_end_time"]){
			$array_cond["gp_end_time"] = array("egt",$ary_data["gp_end_time"]);
		}
		$array_cond["deleted"] = 0;
        $count = $Groupbuy
        ->join("fx_goods as g on(g.g_id=fx_groupbuy.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_groupbuy.g_id)")
        ->where($array_cond)->count();
		//echo $Groupbuy->getLastSql();exit;
        $Page = new Page($count, 15);
		$ary_datalist = $Groupbuy->field('gi.g_name,g.g_sn,fx_groupbuy.*')
		->join("fx_goods as g on(g.g_id=fx_groupbuy.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_groupbuy.g_id)")
		->where($array_cond)
		->order(array("gp_order"=>"desc",'gp_update_time'=>'desc'))
		->limit($Page->firstRow . ',' . $Page->listRows)
		->select();//echo M()->getLastSql();die();
		$ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
		$this->assign("filter",$ary_data);
        $this->assign($ary_data);
		//$cates = D('Gyfx')->selectAll('groupbuy_category');
		$this->assign("gcid",intval($ary_data['gcid']));
		//$this->assign("cates",$cates);
		$ary_parent_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>0), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
		foreach($ary_parent_data as &$sub_data){
			$ary_sub_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>$sub_data['gc_id']), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
			if(!empty($ary_sub_data)){
				$sub_data['sub'] = $ary_sub_data;
			}
		}
		$this->assign("cates",$ary_parent_data);
		$brands = D('Gyfx')->selectAll('groupbuy_brand');
		$this->assign("gbbid",intval($ary_data['gbbid']));
		$this->assign("brands",$brands);	
		//团购详情页
		$is_bulk = 0;
		if(file_exists(APP_PATH.'/Public/Tpl/'.CI_SN.'/'.TPL.'/groupbuyDetails.html')){
			$is_bulk = 1;
		}	
		$this->assign("is_bulk",$is_bulk);
        $this->display();
    }

    /**
     * 后台添加团购
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function pageAdd() {
        $this->getSubNav(5, 2, 20);
        //获取商品品牌并传递到模板
		$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
		$this->assign("array_brand",$array_brand);
		//获取商品分类并传递到模板
		$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
		$this->assign("array_category",$array_category);
		$ary_parent_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>0), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
		foreach($ary_parent_data as &$sub_data){
			$ary_sub_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>$sub_data['gc_id']), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
			if(!empty($ary_sub_data)){
				$sub_data['sub'] = $ary_sub_data;
			}
		}
		$this->assign("cates",$ary_parent_data);	
	 	$cates = D('Gyfx')->selectAll('groupbuy_brand');
		$this->assign("brands",$cates);		
        $this->display();
    }

    /**
     * 处理新增/编辑表单数据
     * @param $ary_data
     * @return mixed
     */
    private function dataProcessing($ary_data) {

        !isset($ary_data['is_deposit']) && $ary_data['is_deposit'] = 0;
        !isset($ary_data['gp_goodshow_status']) && $ary_data['gp_goodshow_status'] = 0;
        !isset($ary_data['gp_is_baoyou']) && $ary_data['gp_is_baoyou'] = 0;
        !isset($ary_data['gp_start_code']) && $ary_data['gp_start_code'] = 0;
        !isset($ary_data['is_active']) && $ary_data['is_active'] = 0;
        $Groupbuy = D('Groupbuy');
        //团购数组
        $ary_data['gp_title'] = trim($ary_data['gp_title']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['gp_title'])) {
            $this->error('团购名称不存在');
        }
        //团购标题必须输入且不能大约250个字符
        if(strlen($ary_data['gp_title'])>250){
            $this->error('团购名称不能大与250个字符');
        }
        $gp_title_where = array('gp_title' => $ary_data['gp_title']);
        $gp_goods_where = array(
            'g_id'=>$ary_data['g_id'],
            'gp_end_time'=>array('gt', date('Y-m-d H:i:s')),
            'deleted'   => 0,
            'is_active' => 1,
        );
        //编辑
        if(isset($ary_data['gp_id'])) {
            $gp_title_where['gp_id'] = array('neq', $ary_data['gp_id']);
            $gp_goods_where['gp_id'] = array('neq', $ary_data['gp_id']);
        }
        //验证团购名称是否重复
        if (false != $Groupbuy->where($gp_title_where)->find()) {
            $this->error('团购名称已被使用');
        }
        //检查商品是否已参加团购
        $bulk_goods_id = $Groupbuy->where($gp_goods_where)->count('gp_id');
        if(0 != $bulk_goods_id){
            $this->error('该商品已参加其它团购活动');
        }

        $ary_data['g_id'] = trim($ary_data['g_id']);
        //验证商品ID是否存在
        if(empty($ary_data['g_id'])){
            $this->error('请先选择商品信息');
        }
        $goods_count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id']))->count('g_id');
        if($goods_count == 0){
            $this->error('商品信息不存在');
        }

        if($_FILES['gp_picture']['error']==0){
            $img_path = '/Public/Uploads/' . CI_SN . '/'.'other/'.date('Ymd').'/';
            if (!is_dir(APP_PATH .$img_path)) {
                //如果目录不存在，则创建之
                mkdir(APP_PATH .$img_path, 0777, 1);
            }
            //生成本地分销地址
            $imge_url = $img_path . 'tuan'.date('YmdHis').$ary_data['g_id'].'.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $imge_url;
            if(move_uploaded_file($_FILES['gp_picture']['tmp_name'],$img_save_path)){
                $ary_data['gp_picture'] = $imge_url;
            }
        }
        if($ary_data['gp_picture']){
            $ary_data['gp_picture'] = $ary_data['gp_picture'];
        }else{
            $ary_data['gp_picture'] = $ary_data['gp_pic'];
        }
		$ary_data['gp_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['gp_picture']);
        if($ary_data['gp_start_time']){
            $ary_data['gp_start_time'] = $ary_data['gp_start_time'];
        }
        if($ary_data['gp_end_time']){
            $ary_data['gp_end_time'] = $ary_data['gp_end_time'];
        }
        if($ary_data['gp_start_time']>$ary_data['gp_end_time']){
            $this->error('活动开始时间大于活动实效时间时间！');
        }
        if($ary_data['is_deposit'] == '1'){
            $ary_data['is_deposit'] = $ary_data['is_deposit'];
            $ary_data['gp_deposit_price'] = floatval(trim($ary_data['gp_deposit_price']));
            if(!is_numeric($ary_data['gp_deposit_price'])){
                $this->error('启用定金后定金必须输入且格式为数字格式！');
            }
            if(empty($ary_data['gp_overdue_start_time'])){
                $this->error('请输入补交尾款开始时间');
            }else if(empty($ary_data['gp_overdue_end_time'])){
                $this->error('请输入补交尾款结束时间');
            }
        }
        //限购数量
        $ary_data['gp_number'] = (int)$ary_data['gp_number'];

        //会员限购数量
        $ary_data['gp_per_number'] = (int)$ary_data['gp_per_number'];


        if($ary_data['gp_send_point']){
            $ary_data['gp_send_point'] = $ary_data['gp_send_point'];
        }
        //虚拟购买数量
        $ary_data['gp_pre_number'] = (int)$ary_data['gp_pre_number'];
        //显示顺序
        $ary_data['gp_order'] = $ary_data['gp_order'];

        if($ary_data['gp_goodshow_status']){
            $ary_data['gp_goodshow_status'] = $ary_data['gp_goodshow_status'];
        }
        //是否启用预售
        if(!empty($ary_data['is_active'])){
            $ary_data['is_active'] = 1;
        }

        $goods_product = D('GoodsProductsTable')->getGoodsMinPrice($ary_data['g_id']);
        //dump($goods_product);die;
        $tiered_pricing_type = $ary_data['gp_tiered_pricing_type'];

        switch($tiered_pricing_type) {
            case 1:
                //团购初始价

                $ary_data['gp_price'] = floatval($ary_data['gp_price']);

                $init_price = $goods_product['pdt_sale_price'] - $ary_data['gp_price'];
                break;
            default:
                //团购初始折扣
                $ary_data['gp_price'] = floatval($ary_data['gp_price']);
                $init_price = $goods_product['pdt_sale_price']*$ary_data['gp_price'];
                break;
        }

        if($ary_data['gp_deposit_price']>$init_price){
            $this->error('定金不能大于团购初始价格！');
        }
        if($ary_data['gp_per_number'] > $ary_data['gp_number'] && $ary_data['gp_number'] > 0){
            $this->error('每个会员限购不能大于限购总数!');
        }
        //七牛图片存取
        $ary_data['gp_desc'] = _ReplaceItemDescPicDomain($ary_data['gp_desc']);
        if(isset($ary_data['gp_mobile_desc'])){
            $ary_data['gp_mobile_desc'] = _ReplaceItemDescPicDomain($ary_data['gp_mobile_desc']);
        }
        $ary_data['gc_id'] = intval($ary_data['gcid']);
        $ary_data['gbb_id'] = intval($ary_data['gbbid']);

        //是否包邮
        $ary_data['gp_is_baoyou'] = intval($ary_data['gp_is_baoyou']);
        $ary_data['gp_start_code'] = intval($ary_data['gp_start_code']);
        $ary_data['gp_update_time'] = date("Y-m-d H:i:s");
        $ary_data['pdt_sale_price'] = $goods_product['pdt_sale_price'];
        $ary_data['gp_remark'] = empty($ary_data['gp_remark'])?'':trim($ary_data['gp_remark']);
        return $ary_data;
    }

    /**
     * 执行新增一条团购
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function doAdd() {
        $ary_data = $this->_post();
        //dump($ary_data);die;
        $ary_data = $this->dataProcessing($ary_data);
        $Groupbuy = D('Groupbuy');
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $Groupbuy->data($ary_data)->add();
        if (!$res_return) {
        	$trans->rollback();
            $this->error('团购生成失败');
        } else {
	        //关联区域
	        $g_related_goods_ids = $ary_data['goods']['g_related_goods_ids'];
	        $g_related_goods_ids = substr($g_related_goods_ids,0 ,-1);
	        $g_related_goods_ids = explode(',',$g_related_goods_ids);
	        array_unique($g_related_goods_ids);
	        $area_obj = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM');
	        foreach($g_related_goods_ids as $cr_id){
	        	if($cr_id){
	        		$area_res = $area_obj->data(array('cr_id'=>$cr_id,'gp_id'=>$res_return))->add();
	        		if(!$area_res){
	        			$trans->rollback();
	        			$this->error('团购生成失败，更新关联区域时失败');
	        		}
	        	}
	        }
            $pdt_sale_price = $ary_data['pdt_sale_price'];
	        //关联团购价格
	        $price_obj = M('related_groupbuy_price', C('DB_PREFIX'), 'DB_CUSTOM');
            //阶梯价
            if(isset($ary_data['nums'])) {
                $nums = $ary_data['nums'];
                $prices = $ary_data['prices'];
                if (count($nums) == 0) {
                    $this->error('至少选择一个团购价格');
                }
                if(count($nums) != count($prices)){
                    $this->error('团购数量要和享受价格一一对应！');
                }
                foreach($nums as $key=>$num){
                    if($num && $prices[$key]){
                        switch($ary_data['gp_tiered_pricing_type']) {
                            case 2:
                                if($prices[$key] > 1) {
                                    $this->error('团购价格阶梯折扣率不能大于1！');
                                }
                                break;
                            default:
                                if($prices[$key] > $pdt_sale_price) {
                                    $this->error('团购价格阶梯优惠金额不能大于商品销售金额！');
                                }
                                break;
                        }
                        $price_res = $price_obj->data(array(
                            'gp_id'=> $res_return,
                            'rgp_price'=>$prices[$key],
                            'rgp_num'=>$num
                        ))->add();
                        if(!$price_res){
                            $trans->rollback();
                            $this->error('生成团购商品失败,更新价格时失败!');
                        }
                    }
                }
            }
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"团购生成成功",'团购为：'.$ary_data['gp_title']));
        	$trans->commit();
            $this->success('团购生成成功', U('Admin/Groupbuy/pageList'));
        }
    }
    /**
     * 删除团购，get接收c_id，可以是数组也可以是单个id
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function doDel() {
        $gp_id = $this->_param('gp_id');
        if(empty($gp_id)){
        	$this->error('请先选择要删除的商品');
        }
        $mix_id = explode(',',$gp_id);
        if (is_array($mix_id)) {
            //批量删除
            $where = array('gp_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('gp_id' => $mix_id);
        }
		$Groupbuy = D('Groupbuy');
		$props = $Groupbuy->where($where)->field('gp_title')->select();
		$str_prop_name = '';
		foreach($props as $prop){
			$str_prop_name .=$prop['gp_title'];
		}
		$str_prop_name = trim($str_prop_name,',');
		
		$tmp_mix_id = implode(',',$mix_id);
        
        $res_return = $Groupbuy->where($where)->data(array('deleted'=>1,'gp_update_time'=>date('Y-m-d H:i:s')))->save();
        if (false == $res_return) {
            $this->error('删除失败');
        } else {
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"团购删除",'团购为：'.$tmp_mix_id.'-'.'团购名称'.$str_prop_name ));
            $this->success('删除成功');
        }
    }

    /**
     * 编辑团购
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function pageEdit() {
        $this->getSubNav(5, 2, 10, '编辑团购');
        $int_gp_id = $this->_get('gp_id');
        $Groupbuy = D('Groupbuy');
        $ary_data = $Groupbuy->field('gi.g_name,g.g_sn,fx_groupbuy.*')
		->join("fx_goods as g on(g.g_id=fx_groupbuy.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_groupbuy.g_id)")
        ->where(array('gp_id'=>$int_gp_id))->find();
		$ary_data['gp_picture'] = D('QnPic')->picToQn($ary_data['gp_picture']);
		$ary_data['gp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['gp_desc']);
		$ary_data['gp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['gp_mobile_desc']);
        if(false == $ary_data){
            $this->error('团购参数错误');
        }else{
			$ary_data['gp_picture'] = D('QnPic')->picToQn($ary_data['gp_picture']);
			$ary_data['gp_desc'] = D('QnPic')->picToQn($ary_data['gp_desc']);
			$ary_data['gp_mobile_desc'] = D('QnPic')->picToQn($ary_data['gp_mobile_desc']);
        	//获取商品品牌并传递到模板
			$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
			$this->assign("array_brand",$array_brand);
			//获取商品分类并传递到模板
			$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
			$this->assign("array_category",$array_category);
        	//获取关联价格表
	        $ary_data['related_prices'] = M('related_groupbuy_price',C('DB_PREFIX'),'DB_CUSTOM')
	        ->where(array('gp_id'=>$int_gp_id))->select();
        	//获取关联区域表
 	        $ary_data['related_areas'] = M('related_groupbuy_area',C('DB_PREFIX'),'DB_CUSTOM')
 	        ->join("fx_city_region on(fx_city_region.cr_id=fx_related_groupbuy_area.cr_id)")
 	        ->field('fx_related_groupbuy_area.*,fx_city_region.cr_name')
	        ->where(array('gp_id'=>$int_gp_id))->select();    
            $ary_data['g_price'] = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id']))->getField('g_price');      
            $this->assign('info',$ary_data);
			$ary_parent_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>0), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
			foreach($ary_parent_data as &$sub_data){
				$ary_sub_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>$sub_data['gc_id']), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
				if(!empty($ary_sub_data)){
					$sub_data['sub'] = $ary_sub_data;
				}
			}
			$this->assign("cates",$ary_parent_data);		
            $brands = D('Gyfx')->selectAll('groupbuy_brand');
			$this->assign("brands",$brands);				
            $this->display();
        }

    }

    /**
     * 执行修改团购
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function doEdit(){
     	$ary_data = $this->_post();
        $ary_data = $this->dataProcessing($ary_data);
        $pdt_sale_price = $ary_data['pdt_sale_price'];

        unset($ary_data['pdt_sale_price']);
        $gp_where = array(
            'gp_id' => $ary_data['gp_id']
        );

     	$int_gp_id = $ary_data['gp_id'];
        $Groupbuy = D('Groupbuy');

        //关联价格
        $nums = $ary_data['nums'];
        $prices = $ary_data['prices'];
        if(empty($ary_data['gp_price'])){
          if(count($nums) == 0){
        	$this->error('至少选择一个团购价格');
          }
        }
        if(count($nums) != count($prices)){
        	$this->error('团购价格关联要一一对应');
        } 
		$ary_data['gp_remark'] = empty($ary_data['gp_remark'])?'':trim($ary_data['gp_remark']);
        //$ary_data['gp_create_time'] = date("Y-m-d H:i:s");
        $ary_data['gp_update_time'] = date("Y-m-d H:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $Groupbuy->data($ary_data)->where($gp_where)->save();
		if (!$res_return) {
        	 $trans->rollback();
            $this->error('团购修改失败');
        } else {
	        //关联区域
	        $g_related_goods_ids = $ary_data['goods']['g_related_goods_ids'];
	        $g_related_goods_ids = substr($g_related_goods_ids,0 ,-1);
	        $g_related_goods_ids = explode(',',$g_related_goods_ids);    
	        array_unique($g_related_goods_ids);
	        $area_obj = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM');
	        //删除关联区域
	        if($area_obj->where($gp_where)->count()>0){
	            $area_result =  $area_obj->where($gp_where)->delete();
	        	if(!$area_result){
	          		$trans->rollback();
	        		$this->error('团购修改失败，删除区域时失败');      		
	        	}	        	
	        }
	        foreach($g_related_goods_ids as $cr_id){
	        	if($cr_id){
	        		$area_res = $area_obj->data(array('cr_id'=>$cr_id,'gp_id'=>$int_gp_id))->add();
	        		if(!$area_res){
	        			$trans->rollback();
	        			$this->error('团购修改失败，更新关联区域时失败');
	        		}
	        	}
	        }  
	        //关联团购价格
	        $price_obj = M('related_groupbuy_price', C('DB_PREFIX'), 'DB_CUSTOM');
        	//删除团购价格
        	if($price_obj->where($gp_where)->count()>0){
        	    $price_result =  $price_obj->where($gp_where)->delete();
	        	if(!$price_result){
	          		$trans->rollback();
	        		$this->error('团购修改失败，删除团购价格失败');      		
	        	}        		
        	}
	        foreach($nums as $key=>$num){
	        	if($num && $prices[$key]){
                    switch($ary_data['gp_tiered_pricing_type']) {
                        case 2:
                            if($prices[$key] > 1) {
                                $this->error('团购价格阶梯折扣率不能大于1！');
                            }
                            break;
                        default:
                            if($prices[$key] > $pdt_sale_price) {
                                $this->error('团购价格阶梯优惠金额不能大于商品销售金额！');
                            }
                            break;
                    }
		        	$price_res = $price_obj->data(array('rgp_price'=>$prices[$key],'gp_id'=>$int_gp_id,'rgp_num'=>$num))->add();
	        		if(!$price_res){
	        			$trans->rollback();
	        			$this->error('团购修改失败，更新团购价格时失败');
	        		}		        		
	        	}        	
	        } 	
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"团购修改成功",'团购为：'.$ary_data['gp_title']));
        	$trans->commit();
			D('Gyfx')->deleteOneCache('groupbuy',array('gp_deposit_price','gp_per_number','gp_now_number','gp_price'), array('gp_id'=>$int_gp_id), null,3600);
			D('Gyfx')->deleteAllCache('related_groupbuy_price',"*", array('gp_id'=>$int_gp_id), $ary_order=null,'rgp_num desc',$ary_limit=null);
            $this->success('团购修改成功', U('Admin/Groupbuy/pageList'));
        }
    }
    
    /**
     * 异步获取 区域数据
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-08-22
     */
    public function cityRegionOptions() {
        //区域限购开关
        /* if(defined("GLOBAL_STOCK")){
            if(!empty($_POST["g_id"])){
            	$ary_data = D("CityRegion")
            	->field('fx_city_region.cr_id,fx_city_region.cr_name')
            	->join("fx_warehouse_delivery_area as wda on(wda.cr_id=fx_city_region.cr_id)")
        		->join('fx_warehouse_stock as ws on(ws.w_id=wda.w_id)')
        		->where(array('ws.g_id'=>trim($_POST["g_id"])))
        		->group('wda.cr_id')
        		->select();
		        // dump($array_result);die();
		        echo json_encode(array("status" => true, "data" => $ary_data, "message" => "success"));
		        exit;
        	}       	
        } */
        if (!isset($_POST["parent_id"]) || !is_numeric($_POST["parent_id"]) || $_POST["parent_id"] <= 0) {
            echo json_encode(array("status" => false, "data" => array(), "message" => "父级区域ID不合法"));
            exit;
        }
        $int_parent_id = $_POST["parent_id"];
        $array_result = D("CityRegion")->where(array("cr_parent_id" => $int_parent_id,'cr_status'=>'1'))->order(array("cr_order" => "asc"))->getField("cr_id,cr_name");
        if (false === $array_result) {
            echo json_encode(array("status" => false, "data" => array(), "message" => "无法获取区域数据"));
            exit;
        }
       // dump($array_result);die();
        echo json_encode(array("status" => true, "data" => $array_result, "message" => "success"));
        exit;
    }
	
	/**
     * 后台团购分类列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateList() {
        $this->getSubNav(5, 2, 40);
		$ary_parent_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>0), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
		foreach($ary_parent_data as &$sub_data){
			$ary_sub_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null, array('gc_parent_id'=>$sub_data['gc_id']), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
			if(!empty($ary_sub_data)){
				$sub_data['sub'] = $ary_sub_data;
			}
		}
		//dump($ary_parent_data);die();
		$this->assign('ary_cates',$ary_parent_data);
        $this->display();
    }
	
 	/**
     * 后台团购品牌列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageBrandList() {
        $this->getSubNav(5, 2, 50);
		$ary_data = D('Gyfx')->selectAll('groupbuy_brand',$ary_field=null, $ary_where=null, array('gbb_order'=>'asc'),$ary_group=null,$ary_limit=null);
		$this->assign('ary_brands',$ary_data);
        $this->display();
    }
	
    /**
     * 后台团购分类添加
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function addCategory() {
		//获取一级类目
		$ary_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null,array('gc_parent_id'=>0), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
		$this->assign('ary_cates',$ary_data);
        $this->getSubNav(5, 2, 40);
    	$this->display();
    }
	
     /**
     * 后台团购品牌添加
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function addBrand() {
        $this->getSubNav(5, 2, 50);
    	$this->display();
    } 
	
	/**
     * 添加品牌操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date  2014-07-07
     */
    public function doAddBrand(){
         $array_insert_data = array();
        
		//验证商品分类的名称是否输入
		if(!isset($_POST['gbb_name']) || "" == $_POST['gbb_name']){
             $this->error('品牌名称不能为空');
        }
		
		
		//验证商品分类名称是否已经存在{此处规则修改：同级不重复}
		$array_cond = array('gbb_name'=>$_POST['gbb_name']);
		$array_result =  D('Gyfx')->selectOne('groupbuy_brand',$ary_field=null, $array_cond);
		if(is_array($array_result) && !empty($array_result)){
			$this->error('已经存在同级的团购品牌“' . $_POST['gbb_name'] . '“！');
		}
		
		//验证商品分类排序字段的参数是否合法
		if (!is_numeric(trim($_POST['gbb_order'])) || $_POST['gbb_order'] < 0 || $_POST['gbb_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		
		//数据组装
        $array_insert_data['gbb_name'] = trim($_POST['gbb_name']);
        //$array_insert_data['gc_parent_id'] = intval($_POST['gc_parent_id']);
        $array_insert_data['gbb_order'] = $_POST['gbb_order'];
        $array_insert_data['gbb_keyword'] = (isset($_POST['gbb_keyword']) && "" != $_POST['gbb_keyword'])?$_POST['gbb_keyword']:"";
        $array_insert_data['gbb_description'] = (isset($_POST['gbb_description']) && "" != $_POST['gbb_description'])?$_POST['gbb_description']:"";
        $array_insert_data['gbb_is_hot'] = $_POST['gbb_is_hot'];
        $array_insert_data['gbb_is_display'] = $_POST['gbb_is_display'];
        $array_insert_data['gbb_create_time'] = date("Y-m-d h:i:s");
		 //上传图片
        if($_FILES['gp_picture']['name']){
            @mkdir('./Public/Uploads/' . CI_SN.'/groupbuy/');
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/groupbuy/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_insert_data['gbb_pic'] = '/Public/Uploads/'.CI_SN.'/groupbuy/' . $info[0]['savename'];
			}
			if(!empty($array_insert_data['gbb_pic'])){
				$array_insert_data['gbb_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_insert_data['gbb_pic']);
			}
    	}
		//事务开始
 		$mixed_result =  D('Gyfx')->insert('groupbuy_brand',$array_insert_data);
		if(false === $mixed_result){
			$this->error("团购品牌添加失败。");
		}
		
		//页面跳转
		$page_jump_url = U('Admin/Groupbuy/pageBrandList');
		if(isset($_POST["page_jump"]) && 1 == $_POST["page_jump"]){
			$page_jump_url = U('Admin/Groupbuy/addBrand');
		}
		$this->success('品牌添加成功', $page_jump_url);
    }
	
    /**
     * 添加分类操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date  2014-07-07
     */
    public function doAddCategory(){
         $array_insert_data = array();
        
		//验证商品分类的名称是否输入
		if(!isset($_POST['gc_name']) || "" == $_POST['gc_name']){
             $this->error('分类名称不能为空');
        }
		
		//验证商品分类的父级分类是否合法
		if(isset($_POST["gc_parent_id"]) && (!is_numeric($_POST["gc_parent_id"]) || $_POST["gc_parent_id"] < 0)){
			$this->error("上级分类ID参数不合法。");
		}
		
		//验证商品分类名称是否已经存在{此处规则修改：同级不重复}
		//$array_cond = array('gc_name'=>$_POST['gc_name'],"gc_parent_id"=>$_POST["gc_parent_id"]);
		$array_cond = array('gc_name'=>$_POST['gc_name']);
		$array_result =  D('Gyfx')->selectOne('groupbuy_category',$ary_field=null, $array_cond);
		if(is_array($array_result) && !empty($array_result)){
			$this->error('已经存在同级的团购分类“' . $_POST['gc_name'] . '“！');
		}
		
		//验证商品分类排序字段的参数是否合法
		if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		
		//数据组装
        $array_insert_data['gc_name'] = trim($_POST['gc_name']);
        $array_insert_data['gc_parent_id'] = intval($_POST['gc_parent_id']);
        $array_insert_data['gc_order'] = $_POST['gc_order'];
        //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
		$array_insert_data['gc_level'] = 0;
//		if($array_insert_data['gc_parent_id'] > 0){
//			$array_parent_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
//			$int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
//			$array_insert_data['gc_level'] = $int_parent_gc_level + 1;
//		}
        $array_insert_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_insert_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_insert_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_insert_data['gc_create_time'] = date("Y-m-d h:i:s");
		 //上传图片
        if($_FILES['gp_picture']['name']){
            @mkdir('./Public/Uploads/' . CI_SN.'/groupbuy/');
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/groupbuy/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_insert_data['gc_pic'] = '/Public/Uploads/'.CI_SN.'/groupbuy/' . $info[0]['savename'];
			}
			if(!empty($array_insert_data['gc_pic'])){
				$array_insert_data['gc_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_insert_data['gc_pic']);
			}
    	}
		//事务开始
 		$mixed_result =  D('Gyfx')->insert('groupbuy_category',$array_insert_data);
		if(false === $mixed_result){
			$this->error("团购分类添加失败。");
		}
		
		//页面跳转
		$page_jump_url = U('Admin/Groupbuy/pageCateList',array('gc_id'=>$cid));
		if(isset($_POST["page_jump"]) && 1 == $_POST["page_jump"]){
			$page_jump_url = U('Admin/Groupbuy/addCategory');
		}
		$this->success('分类添加成功', $page_jump_url);
    }
    
    /**
     * 类目启用/停用
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doStatus(){
        $ary_request = $this->_request();
        if(!empty($ary_request) && is_array($ary_request)){
            $action = D('Gyfx');
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '显示';
            }else{
                $str_msg = '不显示';
            }
            $ary_data[$ary_request['field']]    = $ary_request['val'];
            //保存当前数据对象
            $ary_result = $action->update('groupbuy_category',array('gc_id'=>$ary_request['id']),$ary_data);
            if(FALSE !== $ary_result){
                 $this->success($str_msg."成功");
            }else{
                 $this->error($str_msg."失败");
            }
        }else{
            $this->error("编辑失败");
        }
    }
	
	/**
     * 品牌启用/停用
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doBrandStatus(){
        $ary_request = $this->_request();
        if(!empty($ary_request) && is_array($ary_request)){
            $action = D('Gyfx');
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '显示';
            }else{
                $str_msg = '不显示';
            }
            $ary_data[$ary_request['field']]    = $ary_request['val'];
            //保存当前数据对象
            $ary_result = $action->update('groupbuy_brand',array('gbb_id'=>$ary_request['id']),$ary_data);
            if(FALSE !== $ary_result){
                 $this->success($str_msg."成功");
            }else{
                 $this->error($str_msg."失败");
            }
        }else{
            $this->error("编辑失败");
        }
    }
     
     /**
     * 团购类目编辑页面显示
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateEdit(){
    	$this->getSubNav(5, 2, 40,'团购类目编辑');
        $gc_id=$this->_get('gcid'); 	
        if(isset($gc_id)){	
            $ary_data = D('Gyfx')->selectOne('groupbuy_category','', array('gc_id'=>$gc_id));
			$ary_data['gc_pic'] = D('QnPic')->picToQn($ary_data['gc_pic']);
            $this->assign('category',$ary_data);
			$ary_data = D('Gyfx')->selectAll('groupbuy_category',$ary_field=null,array('gc_parent_id'=>0,'gc_id'=>array('neq',$gc_id)), array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
			$this->assign('ary_cates',$ary_data);	
            $this->display();
        }else {
            $this->error('参数错误');
        }
       
    }
	
	/**
     * 团购品牌编辑页面显示
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageBrandEdit(){
    	$this->getSubNav(5, 2, 50,'团购品牌编辑');
        $gbb_id=$this->_get('gbbid');  
        if(isset($gbb_id)){	
            $ary_data = D('Gyfx')->selectOne('groupbuy_brand','', array('gbb_id'=>$gbb_id));
			$ary_data['gbb_pic'] = D('QnPic')->picToQn($ary_data['gbb_pic']);
            $this->assign('brand',$ary_data);
            $this->display();
        }else {
            $this->error('参数错误');
        } 
    }
	
     /**
     * 分类编辑操作
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doBrandEdit(){
		if(!isset($_POST["gbb_id"]) || !is_numeric($_POST["gbb_id"])){
			$this->error("商品品牌编辑参数错误。");
		}
		$int_gbb_id = $_POST["gbb_id"];
		
		//商品分类标题是否输入
		if(!isset($_POST["gbb_name"]) || $_POST["gbb_name"] == ""){
			$this->error("商品品牌名称不能为空。");
		}
		
		//验证商品分类名称在同级分类下是否重复
		$array_cond = array("gbb_id"=>array("neq",$int_gbb_id),"gbb_name"=>trim($_POST["gbb_name"]));
		//$array_cond = array("gc_id"=>array("neq",$int_gbb_id),"gc_parent_id"=>$_POST["gc_parent_id"],"gc_name"=>$_POST["gc_name"]);
		$mixed_check_result = D("Gyfx")->selectOne('groupbuy_brand','',$array_cond);
		
		if(is_array($mixed_check_result) && !empty($mixed_check_result)){
			$this->error("已经存在同级的商品品牌“" . $_POST["gbb_name"] . "”。");
		}
		
		//验证商品分类排序字段是否是合法的数字
		if (!is_numeric(trim($_POST['gbb_order'])) || $_POST['gbb_order'] < 0 || $_POST['gbb_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		//数据拼装
		$array_modify_data = array();
		//上传图片
        if(!empty($_FILES['gbb_pic']['name'])){
            @mkdir('./Public/Uploads/' . CI_SN.'/groupbuy/');
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/groupbuy/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_modify_data['gbb_pic'] = '/Public/Uploads/'.CI_SN.'/groupbuy/' . $info[0]['savename'];
			}
			if(!empty($array_modify_data['gbb_pic'])){
				$array_modify_data['gbb_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_modify_data['gbb_pic']);
			}
    	}		
		$array_modify_data["gbb_name"] = trim($_POST["gbb_name"]);
		//$array_modify_data['gbb_parent_id'] = $_POST['gc_parent_id'];
        $array_modify_data['gbb_order'] = $_POST['gbb_order'];
        $array_modify_data['gbb_keyword'] = (isset($_POST['gbb_keyword']) && "" != $_POST['gbb_keyword'])?$_POST['gbb_keyword']:"";
        $array_modify_data['gbb_description'] = (isset($_POST['gbb_description']) && "" != $_POST['gbb_description'])?$_POST['gbb_description']:"";
        $array_modify_data['gbb_is_hot'] = $_POST['gbb_is_hot'];
        $array_modify_data['gbb_is_display'] = $_POST['gbb_is_display'];
        $array_modify_data['gbb_update_time'] = date("Y-m-d h:i:s");

		//事务开始
		$modify_result = D('Gyfx')->update('groupbuy_brand',array("gbb_id"=>$int_gbb_id),$array_modify_data);
		if(false === $modify_result){
			$this->error("团购品牌更新失败，数据没有更新。");
		}
		$this->success('团购品牌修改成功。', U('Admin/Groupbuy/pageBrandList'));   
    }
	
     /**
     * 分类编辑操作
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doCateEdit(){
		if(!isset($_POST["gc_id"]) || !is_numeric($_POST["gc_id"])){
			$this->error("商品分类编辑参数错误。");
		}
		$int_gc_id = $_POST["gc_id"];
		
		//商品分类标题是否输入
		if(!isset($_POST["gc_name"]) || $_POST["gc_name"] == ""){
			$this->error("商品分类名称不能为空。");
		}
		
		//验证商品分类名称在同级分类下是否重复
		$array_cond = array("gc_id"=>array("neq",$int_gc_id),"gc_name"=>$_POST["gc_name"]);
		//$array_cond = array("gc_id"=>array("neq",$int_gc_id),"gc_parent_id"=>$_POST["gc_parent_id"],"gc_name"=>$_POST["gc_name"]);
		$mixed_check_result = D("Gyfx")->selectOne('groupbuy_category','',$array_cond);
		
		if(is_array($mixed_check_result) && !empty($mixed_check_result)){
			$this->error("已经存在同级的商品分类“" . $_POST["gc_name"] . "”。");
		}
		
		//验证商品分类排序字段是否是合法的数字
		if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
		//数据拼装
		$array_modify_data = array();
		//上传图片
        if(!empty($_FILES['gc_pic']['name'])){
            @mkdir('./Public/Uploads/' . CI_SN.'/groupbuy/');
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/groupbuy/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_modify_data['gc_pic'] = '/Public/Uploads/'.CI_SN.'/groupbuy/' . $info[0]['savename'];
			}
			if(!empty($array_modify_data['gc_pic'])){
				$array_modify_data['gc_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_modify_data['gc_pic']);
			}
    	}		
		$array_modify_data["gc_name"] = trim($_POST["gc_name"]);
		$array_modify_data['gc_parent_id'] = $_POST['gc_parent_id'];
        $array_modify_data['gc_order'] = $_POST['gc_order'];
		$array_modify_data['gc_level'] = 0;
        $array_modify_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_modify_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_modify_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_modify_data['gc_update_time'] = date("Y-m-d h:i:s");

		//事务开始
		$modify_result = D('Gyfx')->update('groupbuy_category',array("gc_id"=>$int_gc_id),$array_modify_data);
		if(false === $modify_result){
			$this->error("团购分类更新失败，数据没有更新。");
		}
		$this->success('团购分类修改成功。', U('Admin/Groupbuy/pageCateList'));   
    }
    
    /**
     * 删除LOGO
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */   
    public function delCatePic() {
    	$int_gc_id=$this->_get('gc_id');  
    	if(empty($int_gc_id)){
    		$this->error('删除分类图片失败');
    	}
    	$bool_res = D('Gyfx')->update('groupbuy_category',array('gc_id'=>$int_gc_id),array('gc_pic'=>'','gc_update_time'=>date('Y-m-d H:i:s')));
    	if($bool_res){
    		$this->success('删除分类图片成功');
    	}else{
    		$this->error('删除分类图片失败');
    	}
    } 
	
     /**
     * 删除LOGO
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */   
    public function delBrandPic() {
    	$int_gbb_id=$this->_get('gbb_id');  
    	if(empty($int_gbb_id)){
    		$this->error('删除分类图片失败');
    	}
    	$bool_res = D('Gyfx')->update('groupbuy_brand',array('gbb_id'=>$int_gbb_id),array('gbb_pic'=>'','gbb_update_time'=>date('Y-m-d H:i:s')));
    	if($bool_res){
    		$this->success('删除品牌图片成功');
    	}else{
    		$this->error('删除品牌图片失败');
    	}
    } 
	
    /**
     * 分类删除
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doDelCate(){
        //判断当前分类id是否为数组
        if(!empty($_POST["gc_ids"])){
            $where = array('gc_id'=>array('in',$_POST["gc_ids"]));
			$sub_where = array('gc_parent_id'=>array('in',$_POST["gc_ids"]));
        }else{
            $int_gc_id = $_GET["gcid"];
            $where = array('gc_id'=>$int_gc_id);
			$sub_where = array('gc_parent_id'=>$int_gc_id);
        }
		
        //删除商品分类
        $mixed_delete = D("Gyfx")->deleteInfo('groupbuy_category',$where);
        if(false === $mixed_delete){
            $this->error("团购分类删除失败。");
        }
		D("Gyfx")->deleteInfo('groupbuy_category',$sub_where);
		//同时更新团购商品
		D("Gyfx")->update('groupbuy',$where,array('gc_id'=>'0','gbb_update_time'=>date('Y-m-d H:i:s')));
		D("Gyfx")->update('groupbuy',$sub_where,array('gc_id'=>'0','gbb_update_time'=>date('Y-m-d H:i:s')));
        //页面提示并跳转
        $this->success('删除成功', U('Admin/Groupbuy/pageCateList'));
    }    
  
    /**
     * 分类删除
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doDelBrand(){
        //判断当前分类id是否为数组
        if(!empty($_POST["gbb_ids"])){
            $where = array('gbb_id'=>array('in',$_POST["gbb_ids"]));
        }else{
            $int_gbb_id = $_GET["gbbid"];
            $where = array('gbb_id'=>$int_gbb_id);
        }
        //删除商品品牌
        $mixed_delete = D("Gyfx")->deleteInfo('groupbuy_brand',$where);
        if(false === $mixed_delete){
            $this->error("团购品牌删除失败。");
        }
		//同时更新团购商品
		D("Gyfx")->update('groupbuy',$where,array('gbb_id'=>'0','gbb_update_time'=>date('Y-m-d H:i:s')));
        //页面提示并跳转
        $this->success('删除成功', U('Admin/Groupbuy/pageBrandList'));
    } 
	
    /**
     * 
     * 新增商品分类促销区
     * @author wangguibin<wangguibin@guanyisoft.com> 
     * @date 2013-06-09
     */
    public function addCategoryPromotion(){
		$this->getSubNav(5, 2, 40);
    	$data = $this->_get();
		if(empty($data['gcid'])){
			$this->error('请选择类目');
		}
    	if(!empty($data['gcid'])){
    		$this->assign('cid',$data['gcid']);
    	}
		//关联广告图片
		$RelatedGroupbuycategoryAds = D('RelatedGroupbuycategoryAds');
		$ary_ads = $RelatedGroupbuycategoryAds->where(array('gc_id'=>$data['gcid']))->order('sort_order asc')->select();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			$ary_ad['ad_pic_url'] = D('QnPic')->picToQn($ary_ad['ad_pic_url']);
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        $this->assign('ary_ads', $ary_ad_infos);
    	$this->display();
    }
	
	/**
	 * 编辑促销区
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2014-06-09
	 */
	public function doEditCategoryPromotion() {
		$ary_data = $this->_request();
		if(empty($ary_data['gcid'])){
			$this->error('商品类目不存在');
		}
		//关联品牌
		$RelatedGroupbuycategoryAds = D('RelatedGroupbuycategoryAds');
		M('')->startTrans();
		//先删除关联的分类和品牌信息
		$RelatedGroupbuycategoryAds->where(array('gc_id' => $ary_data['gcid']))->delete();
		//广告图片1
		for($i=0;$i<5;$i++){
			if(!empty($ary_data['GY_SHOP_TOP_AD_'.$i])){
				$ary_insert = array();
				$ary_insert = array(
					'gc_id' => intval($ary_data['gcid']),
					'sort_order'=>intval($ary_data['sort_order_'.$i]),
					'ad_url'=>$ary_data['GY_SHOP_TOP_AD_'.$i.'_URL']
				);		
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){
					$ary_insert['ad_pic_url'] = $ary_data['GY_SHOP_TOP_AD_'.$i];
				}else{
					$ary_insert['ad_pic_url'] = str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i]));
				}
				$ary_insert['ad_pic_url'] = D('ViewGoods')->ReplaceItemPicReal($ary_insert['ad_pic_url']);
				$res = $RelatedGroupbuycategoryAds->data($ary_insert)->add();
				if(!$res){
					M('')->rollback();
					$this->error('类目广告图片保存失败');
				}				
			}
		}
		M('')->commit();
		$this->success('类目促销区编辑成功');
	}
	
}
