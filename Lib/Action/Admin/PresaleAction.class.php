<?php

/**
 * 后台预售商品控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.4.5
 * @author WangHaoYu <wanghaoyu@guanyisoft.com>
 * @date 2013-11-27
 */
class PresaleAction extends AdminAction {

    /**
    * 后台预售控制器初始化
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function _initialize() {
        parent::_initialize();
        //$this->setTitle(' - ' . '预售商品管理');
    }
    
    /**
    * 后台默认控制器跳转到 预售列表页
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function index() {
        $this->redirect(U('Admin/Presale/pageList'));
    }
    
    /**
    * 后台预售列表页
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function pageList() {
        $this->getSubNav(5, 5, 10);
        $presale = D('Presale');
        $ary_data = $this->_get();
        //搜索条件处理
        $ary_cond = array();
        //根据预售商品的有效时间进行搜索
        if(!empty($ary_data['p_start_time']) && isset($ary_data['p_start_time'])){
            $ary_cond['p_start_time'] = array('egt',$ary_data['p_start_time']);
        }
        if(!empty($ary_data['p_end_time']) && isset($ary_data['p_end_time'])){
            $ary_cond['p_end_time'] = array('elt',$ary_data['p_end_time']);
        }
        //根据名称进行搜索
        switch ($ary_data['field']) {
            case 1:
                $ary_cond['fx_presale.p_title'] = array("LIKE","%" . $ary_data['val'] . "%");
                break;
            case 2:
                $ary_cond['gi.g_name'] = array("LIKE","%" . $ary_data['val'] . "%");
                break;
            default:
                break;
        }
        $ary_cond['p_deleted'] = 0;
        $int_count = $presale
                        ->join(C('DB_PREFIX').'goods_info gi on gi.g_id = '.C('DB_PREFIX').'presale.g_id' )                       
                        ->where($ary_cond)->count();
        $page = new Page($int_count,15);
        $order = array('p_order'=>"asc",'p_update_time'=>"desc");
        $limit = $page->firstRow . ',' . $page->listRows;
        $ary_datalist = $presale
                        ->field('gi.g_name,' .C('DB_PREFIX').'presale.*')
                        ->join(C('DB_PREFIX').'goods_info gi on gi.g_id = '.C('DB_PREFIX').'presale.g_id' )
                        ->where($ary_cond)
                        ->order($order)
                        ->limit($limit)
                        ->select();
        $ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $page->show();
        $this->assign('filter',$ary_data);
        $this->assign($ary_data);
        $this->display();
    }
    
    /**
    * 后台预售新增页面
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function pageAdd() {
        $this->getSubNav(5, 5, 20);
        //获取商品分类
        $ary_category = D('GoodsCategory')->getChildLevelCategoryById(0);
        //获取商品品牌
        $ary_brand = D('GoodsBrand')->where(array('gb_status'=>1))->select();
        $this->assign('ary_category',$ary_category);
        $this->assign('ary_brand',$ary_brand);
        $sysSetting = D('SysConfig');
        $sys_config = $sysSetting->getConfigs('GY_GOODS');
        $is_on_mulitiple = $sys_config['IS_ON_MULTIPLE']['sc_value'];
        $this->assign('is_on_mulitiple', $is_on_mulitiple);
        $this->display();
    }


    private function dataProcessing($ary_data) {
        !isset($ary_data['is_deposit']) && $ary_data['is_deposit'] = 0;
        !isset($ary_data['p_goodshow_status']) && $ary_data['p_goodshow_status'] = 0;
        !isset($ary_data['is_active']) && $ary_data['is_active'] = 0;
        //dump($ary_data);die;
        $presale = D('Presale');
        //预售商品数组
        $ary_data['p_title'] = trim($this->_post('p_title'));
        //验证数据有效性
        if(empty($ary_data['p_title'])){
            $this->error('预售商品名不能为空');
        }
        //预售标题不能超过90字符
        if(mb_strlen($ary_data['p_title'],'utf-8') > 90){
            $this->error('预售标题不能超过90个字');
        }
        $p_title_where = array('p_title' => $ary_data['p_title']);
        $p_goods_where = array('g_id'=>$ary_data['g_id']);
        //编辑
        if(isset($ary_data['p_id'])) {
            $p_title_where['p_id'] = array('neq', $ary_data['p_id']);
            $p_goods_where['p_id'] = array('neq', $ary_data['p_id']);
        }
        //验证预售商品名是否重复
        $p_title = $presale->where($p_title_where)->find();
        if (FALSE != $p_title) {
            $this->error('预售标题已经使用');
        }

        //检查商品是否已参加预售
        $presale_goods_id = $presale->where($p_goods_where)->count('p_id');
        if(0 != $presale_goods_id){
            $this->error('该商品已参加其它预售活动');
        }
        //验证商品ID是否存在
        if(empty($ary_data['g_id'])){
            $this->error('请选择预售商品');
        }
        $g_is_exists = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id']))->count('g_id');
        if(0 == $g_is_exists){
            $this->error('该商品已不存在');
        }

        if(!$ary_data['p_pic']) {
            $this->error('预售图片不能为空');
        }
        //验证上传文件
        if(0 == $_FILES['p_picture']['error']){
            $img_path = '/Public/Uploads/' . CI_SN . '/' . 'other/' . date('Ymd') . '/';
            if(!is_dir(APP_PATH.$img_path)){
                mkdir(APP_PATH.$img_path,0777,1);
            }
            //生成本地分销地址
            $image_url = $img_path . 'yushou' . date('YmdHis') . $ary_data['g_id'] . '.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $image_url;
            if(is_uploaded_file($_FILES['p_picture']['tmp_name'])){
                if(move_uploaded_file($_FILES['p_picture']['tmp_name'],$img_save_path)){
                    $ary_data['p_picture'] = $image_url;
                }
            }
        }
        if(!empty($ary_data['p_picture'])){
            $ary_data['p_picture'] = $ary_data['p_picture'];
        }else{
            $ary_data['p_picture'] = $ary_data['p_pic'];
        }
        //七牛图片存入
        $ary_data['p_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['p_picture']);

        if(!empty($ary_data['p_desc'])){
            $ary_data['p_desc'] = $ary_data['p_desc'];
        }
        //七牛图片存取
        $ary_data['p_desc'] = _ReplaceItemDescPicDomain($ary_data['p_desc']);

        //验证时间的有效性
        if($ary_data['p_start_time'] >= $ary_data['p_end_time']){
            $this->error('活动开始时间不能大于或等于活动结束时间！');
        }
        //启用定金
        if($ary_data['p_deposit'] == 1){
            $ary_data['p_deposit_price'] = floatval(trim($ary_data['p_deposit_price']));
            if(!is_numeric($ary_data['p_deposit_price'])){
                $this->error('启用保证金后定金必须输入,且格式为小于预售总金额的正数数字');
            }
            if(empty($ary_data['p_overdue_start_time'])){
                $this->error('请输入补交尾款开始时间');
            }else if(empty($ary_data['p_overdue_end_time'])){
                $this->error('请输入补交尾款结束时间');
            }
        }
        //预售总数
        $ary_data['p_number'] = (int)$ary_data['p_number'];
        //会员限购数
        $ary_data['p_per_number'] = (int)$ary_data['p_per_number'];
        //虚拟数量
        $ary_data['p_pre_number'] = (int)$ary_data['p_pre_number'];
        //显示顺序
        $ary_data['p_order'] = (int)$ary_data['p_order'];

        //是否显示商品详情
        if(!empty($ary_data['p_goodshow_status'])){
            $ary_data['p_goodshow_status'] = (int)$ary_data['p_goodshow_status'];
        }
        //是否启用预售
        if(!empty($ary_data['is_active'])){
            $ary_data['is_active'] = 1;
        }
        $goods_product = D('GoodsProductsTable')->getGoodsMinPrice($ary_data['g_id']);
        //dump($goods_product);die;
        $tiered_pricing_type = $ary_data['p_tiered_pricing_type'];

        switch($tiered_pricing_type) {
            case 1:
                //预售初始价
                $ary_data['p_price'] = floatval($ary_data['p_price']);
                $init_price = $goods_product['pdt_sale_price'] - $ary_data['p_price'];
                break;
            default:
                //预售初始折扣
                $ary_data['p_price'] = floatval($ary_data['p_price']);
                $init_price = $goods_product['pdt_sale_price']*$ary_data['p_price'];
                break;
        }
        if($ary_data['p_deposit_price'] > $init_price){
            $this->error('定金价不能大于预售初始价！');
        }

        if($ary_data['p_per_number'] > $ary_data['p_number'] && $ary_data['p_number'] > 0){
            $this->error('每个会员限购不能大于限购总数!');
        }

        $ary_data['p_update_time'] = date('Y-m-d H:i:s');
        $ary_data['pdt_sale_price'] = $goods_product['pdt_sale_price'];
        return $ary_data;
    }


    /**
    * 新增一条预售商品
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function doAdd() {
        $ary_data = $this->_post();

        $ary_data = $this->dataProcessing($ary_data);
        $ary_data['p_create_time'] = date('Y-m-d H:i:s');
        $pdt_sale_price = $ary_data['pdt_sale_price'];
        unset($ary_data['pdt_sale_price']);

        //关联价格
        $nums = $ary_data['nums'];
        $prices = $ary_data['prices'];
        if(!empty($nums)){
            if(count($nums) == 0){
                $this->error('至少选择一个预售价格');
            }
        }
        
        if(count($nums) != count($prices)){
            $this->error('预售数量要和享受价格一一对应！');
        }
        $ary_data['p_create_time'] = date('Y-m-d H:i:s');
        $ary_data['p_update_time'] = date('Y-m-d H:i:s');
        //插入数据
        $trans = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $trans->startTrans();
        $res_return = M('presale',C('DB_PREFIX'),'DB_CUSTOM')->data($ary_data)->add();
        if(!$res_return){
            $trans->rollback();
            $this->error('预售商品生成失败！');
        }else{
            //关联区域
            $g_related_goods_ids = $ary_data['goods']['g_related_goods_ids'];
            $g_related_goods_ids = substr($g_related_goods_ids,0,-1);
            $g_related_goods_ids = explode(',',$g_related_goods_ids);
            array_unique($g_related_goods_ids);
            $area_obj = M('related_presale_area',C('DB_PREFIX'),'DB_CUSTOM');
            foreach($g_related_goods_ids as $cr_id){
                if($cr_id){
                    $area_res = $area_obj->data(array('cr_id'=>$cr_id,'p_id'=>$res_return))->add();
                    if(!$area_res){
                        $trans->rollback();
                        $this->error('预售生成失败,更新关联区域时失败！');
                    }
                }
                
            }
            
            //关联预售价格
            $price_obj = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
            foreach($nums as $key=>$num){
                if($num && $prices[$key]){
                    switch($ary_data['p_tiered_pricing_type']) {
                        case 1:
                            if($prices[$key] > $pdt_sale_price) {
                                $this->error('预售价格阶梯优惠金额不能大于商品销售金额！');
                            }
                            break;
                        default:
                            if($prices[$key] > 1) {
                                $this->error('预售价格阶梯折扣率不能大于1！');
                            }
                            break;
                    }
                    $price_res = $price_obj->data(array('p_id'=>$res_return,'rgp_price'=>$prices[$key],'rgp_num'=>$num))->add();
                    if(!$price_res){
                        $trans->rollback();
                        $this->error('生成预售商品失败,更新价格时失败!');
                    }
                }
            }
            $trans->commit();
            $this->success('预售商品生成成功',U('Admin/Presale/pageList'));
        }
    }

    /**
     * 根据商品ID，获取商品信息Tr页面用于新增促销规则
     */
    public function getGoodsTr() {
        layout(false);
        //如果是赠品
        $g_id = $this->_post('g_id');
        if(empty($g_id)) {
            $this->ajaxReturn(false);
            return;
        }
        $where = array('fx_goods.g_id' => $g_id);
        $field=array('fx_goods.g_id','fx_goods.g_sn','fx_goods_info.g_name','fx_goods_info.g_price');
        $group='fx_goods.g_id';
        $limit = array(
            'start'=>0,
            'end' => 1
        );
        $data['rGoods'] = D("Goods")->GetGoodList($where,$field,$group,$limit);
        //dump($data['rGoods']);die;
        if (false == $data) {
            $this->ajaxReturn(false);
            exit;
        } else {
            $goodsSpec = D('GoodsSpec');
            $ary_product_feild =array('fx_goods_products.pdt_sn', 'fx_goods_products.pdt_id', 'fx_goods_products.pdt_sale_price');
            foreach($data['rGoods'] as $k =>$v){
                $ary_product = D("GoodsProducts")->GetProductList(array('fx_goods_products.g_id' => $v['g_id']),$ary_product_feild,$group,$limit);
                foreach ($ary_product as $k1 => $pdt) {
                    //获取其他属性
                    $ary_product[$k1]['specName'] = $goodsSpec->getProductsSpec($pdt['pdt_id']);
                    //删选掉不符合搜索条件的
                }
                $data['rGoods'][$k]['products']= $ary_product;
            }
            //dump($data);die;
            $this->assign($data);
            $content = $this->fetch();
            $this->ajaxReturn($content,'',1);
        }
    }

    /**
    * 预售商品价格区间设置页面
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-28
    */
    public function pageSet() {
        $this->getSubNav(5, 5, 30);
        $presaleset = M('presale_set',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_data = $presaleset->field('ps_price_range')->find();
        $ary_data['ps_price_range'] = unserialize($ary_data['ps_price_range']);
        $this->assign('ary_data',$ary_data);
        $this->display();
    }
    
    /**
    * 后台新增一条预售价格区间设置
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date 2013-11-28
    */
    public function doAddSet() {
        $ary_data = $this->_post();
        $presaleset = M('presale_set',C('DB_PREFIX'),'DB_CUSTOM');
        $price = array();
        $price['min_price'] = $ary_data['min_price'];
        $price['max_price'] = $ary_data['max_price'];
        $price_range = array();
        if(!empty($ary_data['prices_from']) && is_array($ary_data['prices_from'])){
            foreach($ary_data['prices_from'] as $key=>$p_from){
                $price_range[] = array(
                    'from'=>$p_from,
                    'to'=>$ary_data['prices_to'][$key]
                );
                //验证数据有效性
                if(empty($p_from) && empty($ary_data['prices_to'][$key]) && empty($price['min_price']) && empty($price['max_price'])){
                    $this->error('至少输入一个价格区间');
                }
                if((!is_numeric($p_from) || !is_numeric($ary_data['prices_to'][$key])) || (!empty($price['min_price']) && !is_numeric($price['min_price'])) || (!empty($price['max_price']) && !is_numeric($price['max_price']))){
                    $this->error('请输入合法数字');
                }
            }
        }
        if(empty($ary_data['prices_from'])){
            if(empty($price['min_price']) && empty($price['max_price'])){
                $this->error('请至少输入一个价格区间');
            }
        }
        $price['prices'] = $price_range;
        $ary_insert['ps_price_range'] = serialize($price);
        $int_ps_id = $presaleset->getField('ps_id');
        $trans = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $trans->startTrans();
        if(isset($int_ps_id)){
            $res_return = $presaleset->where(array('ps_id'=>$int_ps_id))->data($ary_insert)->save();
            if(!$res_return){
                $trans->rollback();
                $this->error('预售价格区间更新失败');
            }
        }else{
            $ary_insert['ps_create_time'] = date('Y-m-d H:i:s');
            $res_return = $presaleset->data($ary_insert)->add();
            if(!$res_return){
                $trans->rollback();
                $this->error('预售价格区间新增失败');
            }
        }
        $trans->commit();
        $this->success('新增预售价格区间成功');
    }
    
    /**
    * 编辑预售商品
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function pageEdit() {
        $this->getSubNav(5, 5, 10,'编辑预售商品');
        $int_p_id = $this->_get('p_id');
        $presale = D('Presale');
        $ary_data = $presale->field(C('DB_PREFIX').'presale.*,gi.g_name,gi.g_price,g.g_sn')
                        ->join(C('DB_PREFIX'). 'goods_info gi on gi.g_id = ' . C('DB_PREFIX'). 'presale.g_id')
                        ->join(C('DB_PREFIX').'goods as g on g.g_id = ' . C('DB_PREFIX').'presale.g_id')
                        ->where(array('p_id'=>$int_p_id))
                        ->find();
        if(FALSE == $ary_data){
            $this->error('预售参数错误');
        }else{
			//七牛图片显示
			$ary_data['p_picture'] = D('QnPic')->picToQn($ary_data['p_picture']); 
            //获取商品分类并赋值
            $ary_category = D("GoodsCategory")->getChildLevelCategoryById(0);
			$this->assign("ary_category",$ary_category);
            //获取商品品牌并赋值
            $ary_brand = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_status'=>1))->select();
            $this->assign('ary_brand',$ary_brand);
            //获取关联价格并赋值
            $ary_data['related_prices'] = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM')->where(array('p_id'=>$int_p_id))->select();
            //获取管理区域并赋值
            $ary_data['related_areas'] = M('related_presale_area',C('DB_PREFIX'),'DB_CUSTOM')
                            ->field(C('DB_PREFIX').'related_presale_area.*,' . C('DB_PREFIX').'city_region.cr_name')
                            ->join(C('DB_PREFIX').'city_region on ' . C('DB_PREFIX').'city_region.cr_id = ' . C('DB_PREFIX').'related_presale_area.cr_id')
                            ->where(array('p_id'=>$int_p_id))
                            ->select();
            $this->assign('info',$ary_data);

            $sysSetting = D('SysConfig');
            $sys_config = $sysSetting->getConfigs('GY_GOODS');
            $is_on_mulitiple = $sys_config['IS_ON_MULTIPLE']['sc_value'];
            $this->assign('is_on_mulitiple', $is_on_mulitiple);
            $this->display();
        }
        
    }
    
    /**
    * 处理编辑预售商品
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date    2013-11-27
    */
    public function doEdit() {
        $ary_data = $this->_post();
        $int_p_id = $ary_data['p_id'];
        $ary_data = $this->dataProcessing($ary_data);
        $ary_data['p_create_time'] = date('Y-m-d H:i:s');
        $pdt_sale_price = $ary_data['pdt_sale_price'];
        unset($ary_data['pdt_sale_price']);

        //关联价格
        $nums = $ary_data['nums'];
        $prices = $ary_data['prices'];
        if(!empty($nums)){
            if(count($nums) == 0){
                $this->error('至少选择一个预售价格');
            }
        }
        
        if(count($nums) != count($prices)){
        	$this->error('预售价格关联要一一对应');
        }
        $p_where = array(
            'p_id'  => $int_p_id
        );
        //更新数据
        $trans = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $trans->startTrans();
        $res_return = M('presale',C('DB_PREFIX'),'DB_CUSTOM')->data($ary_data)->where($p_where)->save();
        if(!$res_return){
            $trans->rollback();
            $this->error('预售商品修改失败！');
        }else{
            //关联区域
            $g_related_goods_ids = $ary_data['goods']['g_related_goods_ids'];
            $g_related_goods_ids = substr($g_related_goods_ids,0,-1);
            $g_related_goods_ids = explode(',',$g_related_goods_ids);
            array_unique($g_related_goods_ids);
            $area_obj = M('related_presale_area',C('DB_PREFIX'),'DB_CUSTOM');
            //删除关联区域
            if(0 < $area_obj->where($p_where)->count()){
                $area_result = $area_obj->where($p_where)->delete();
                if(!$area_result){
                    $trans->rollback();
                    $this->error('预售商品修改失败,删除关联区域失败！');
                }
            }
            foreach($g_related_goods_ids as $cr_id){
                if($cr_id){
                    $area_res = $area_obj->data(array('cr_id'=>$cr_id,'p_id'=>$int_p_id))->add();
                    if(!$area_res){
                        $trans->rollback();
                        $this->error('更新预售商品失败,更新关联区域时失败！');
                    }
                }
                
            }       
            //关联预售价格
            $price_obj = M('related_presale_price',C('DB_PREFIX'),'DB_CUSTOM');
            //删除预售价格
            if(0 < $price_obj->where($p_where)->count()){
                $price_result = $price_obj->where($p_where)->delete();
                if(!price_result){
                    $trans->rollback();
                    $this->error('更新预售商品失败,删除关联价格失败！');
                }
            }
            foreach($nums as $key=>$num){
                if($num && $prices[$key]){
                    switch($ary_data['p_tiered_pricing_type']) {
                        case 1:
                            if($prices[$key] > $pdt_sale_price) {
                                $this->error('预售价格阶梯优惠金额不能大于商品销售金额！');
                            }
                            break;
                        default:
                            if($prices[$key] > 1) {
                                $this->error('预售价格阶梯折扣率不能大于1！');
                            }
                            break;
                    }
                    $price_res = $price_obj->data(array('p_id'=>$int_p_id,'rgp_price'=>$prices[$key],'rgp_num'=>$num))->add();
                    if(!$price_res){
                        $trans->rollback();
                        $this->error('更新预售商品失败,更新价格时失败!');
                    }
                }
            }
            $trans->commit();
            $this->success('预售商品修改成功',U('Admin/Presale/pageList'));
        }
    }
    
    /**
    * 删除预售商品 get接收 c_id 可以是数组也可以是单个ID
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @version 7.4.5
    * @date 2013-11-28
    */
    public function doDel() {
        $mix_id = $this->_param('p_id');
        if(empty($mix_id)){
            $this->error('请先选择要删除的商品');
        }
        if(is_array($mix_id)){
            //批量删除
            $where = array('p_id'=>array('IN',$mix_id));
        }else{
            //单个删除
            $where = array('p_id'=>$mix_id);
        }
        $presale = D('Presale');
        $trans = M('',C('DB_PREFIX'),'DB_CUSTOM');
        $trans->startTrans();
        $res_return = $presale->where($where)->data(array('p_deleted'=>1))->save();
        if(!res_return){
            $trans->rollback();
            $this->error('删除预售商品失败');
        }else{
            $trans->commit();
            $this->success('删除预售商品成功');
        }
    }

}

    