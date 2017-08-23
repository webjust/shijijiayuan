<?php
/**
 * 后台试用控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.6.1
 * @author Wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-09-03
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class TryAction extends AdminAction{
	public function index(){
		$this->redirect(U('Admin/Try/pageList'));
	}
    /**
     * 后台试用列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function pageList() {
        $this->getSubNav(5, 7, 10);
        $Try = D('Try');
        $ary_data = $this->_get();
		//搜索条件处理
		$array_cond = array();
		//如果根据名称进行搜索
		switch ($ary_data['field']){
			case 1:
				$array_cond["fx_try.try_title"] = array("LIKE","%" . $ary_data['val'] . "%");
			break;
			case 2:
                if(!empty($ary_data['val'])){
                    $array_cond["fx_try.g_sn"] = $ary_data['val'];
                }
			break;
			default:
			break;
		}

		//如果根据试用的有效期进行搜索
		if(isset($ary_data["try_start_time"]) && "" != $ary_data["try_start_time"]){
			$array_cond["try_start_time"] = array("elt",$ary_data["try_start_time"]);
		}
		if(isset($ary_data["try_end_time"]) && "" != $ary_data["try_end_time"]){
			$array_cond["try_end_time"] = array("egt",$ary_data["try_end_time"]);
		}
		$array_cond["try_status"] = array('neq',2);
        $count = $Try
		->join("fx_goods as g on(g.g_id=fx_try.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_try.g_id)")
        ->where($array_cond)->count();
        $Page = new Page($count, 20);
		$ary_datalist = $Try->field('gi.g_name,g.g_sn,fx_try.*')
		->join("fx_goods as g on(g.g_id=fx_try.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_try.g_id)")
		->where($array_cond)
		->order(array("try_order"=>"asc",'try_update_time'=>'desc'))
		->limit($Page->firstRow . ',' . $Page->listRows)
		->select();//echo M()->getLastSql();die();
		//七牛图片显示
		foreach($ary_datalist as $key =>$value ){
			$ary_datalist[$key]['try_picture'] =D('QnPic')->picToQn($value['g_picture']);
		}
		$ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
		$this->assign("filter",$ary_data);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 后台添加试用
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function pageAdd() {
        $this->getSubNav(5, 7, 10);
        //获取商品品牌并传递到模板
		$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
		$this->assign("array_brand",$array_brand);
		//获取商品分类并传递到模板
		$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
		$this->assign("array_category",$array_category);
		//商品类型
        $ary_type =  M('goods_type',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gt_status'=>1,'gt_type'=>1))->select();
        $this->assign('ary_type',$ary_type);
        $this->display();
    }

    /**
     * 执行新增一条试用
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function doAdd() {
        $ary_data = $this->_post();
        $Try = D('Try');
        //试用数组
        $ary_data['try_title'] = trim($ary_data['try_title']);
        unset($ary_data['search_cats']);
        unset($ary_data['search_brand']);
        unset($ary_data['keywords']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['try_title'])) {
            $this->error('试用名称不存在');
        }
		if(empty($ary_data['property_typeid'])){
			$this->error('试用报告类型不能为空');
		}
        //试用标题必须输入且不能大约250个字符
        if(strlen($ary_data['try_title'])>250){
            $this->error('试用名称不能大与250个字符');
        }
        //验证试用名称是否重复
        if (false != $Try ->where(array('try_title' => $ary_data['try_title']))->find()) {
            $this->error('试用名称已被使用');
        }
        $ary_data['g_id'] = trim($ary_data['g_id']);
        //验证商品ID是否存在
        if(empty($ary_data['g_id'])){
             $this->error('请先选择商品信息');
        }
        $goods_count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id']))->count();
        if($goods_count == 0){
            $this->error('商品信息不存在');
        }

        $where_sp = "1=1";
        $where_sp .= " and g_id=".$ary_data['g_id'];
        $where_sp .= " and try_end_time > current_timestamp()";
        $where_sp .= " and try_status != 2";
        $spike_count = $Try->where($where_sp)->count();

        if ($spike_count != 0) {
            $this->error('此商品已被其他试用使用',U('/Admin/Try/pageList'));
        }
		$ary_data['try_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['gp_pic']);
        unset($ary_data['gp_pic']);
        if($ary_data['try_start_time']){
            $ary_data['try_start_time'] = $ary_data['try_start_time'];
        }
        if($ary_data['try_end_time']){
            $ary_data['try_end_time'] = $ary_data['try_end_time'];
        }
        if($ary_data['try_start_time']>$ary_data['try_end_time']){
            $this->error('活动开始时间大于活动实效时间时间！');
        }
        if($ary_data['try_num']){
            $ary_data['try_num'] = intval($ary_data['try_num']);
        }else{
            $this->error('试用数量必须输入！');
        }
		$total_stock = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id'],'pdt_status'=>1))->sum('pdt_stock');
		if($ary_data['try_num']>$total_stock){
			$ary_data['try_num'] = $total_stock;
		}
		if($ary_data['try_order']){
            if(is_numeric($ary_data['try_order'])){
                $ary_data['try_order'] = $ary_data['try_order'];
            }
        }
        if($ary_data['try_is_show_detail']){
            $ary_data['try_is_show_detail'] = $ary_data['try_is_show_detail'];
        }
        if($ary_data['try_status']){
            $ary_data['try_status'] = intval($ary_data['try_status']);
        }
		
        if($ary_data['try_desc']){
            $ary_data['try_desc'] = _ReplaceItemDescPicDomain($ary_data['try_desc']);
        }
        // 试用前答题
        if($ary_data['property_typeid_front']){
            $ary_data['property_typeid_front'] = $ary_data['property_typeid_front'];
        }
        $ary_data['try_create_time'] = date("Y-m-d H:i:s");
        $ary_data['try_update_time'] = date("Y-m-d H:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $Try->data($ary_data)->add();
        if (!$res_return) {
            $trans->rollback();
            $this->error('试用生成失败');
        } else {
			$trans->commit();
            $this->success('试用生成成功', U('Admin/Try/pageList'));
        }
    }
    /**
     * 删除试用，get接收c_id，可以是数组也可以是单个id
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function doDel() {
        $try_id = $this->_param('try_id');
        if(empty($try_id)){
            $this->error('请先选择要删除的试用商品');
        }
        $mix_id = explode(',',$try_id);
        if (is_array($mix_id)) {
            //批量删除
            $where = array('try_id' => array('IN',$mix_id));
        } else {
            //单个删除
            $where = array('try_id' => $mix_id);
        }
        $Try = D('Try');
        $res_return = $Try->where($where)->data(array('try_status'=>2,'try_update_time'=>date('Y-m-d H:i:s')))->save();
        if (false == $res_return) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

    /**
     * 编辑试用
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function pageEdit() {
        $this->getSubNav(5, 7, 10, '编辑试用');
        $int_try_id = $this->_get('try_id');
        $Try = D('Try');
        $ary_data = $Try->field('gi.g_name,g.g_sn,fx_try.*')
		->join("fx_goods as g on(g.g_id=fx_try.g_id)")
        ->join("fx_goods_info as gi on(gi.g_id=fx_try.g_id)")
        ->where(array('try_id'=>$int_try_id))->find();
		//dump($ary_data);die();
        if(false == $ary_data){
            $this->error('试用参数错误');
        }else{
			$ary_data['try_picture'] = D('QnPic')->picToQn($ary_data['try_picture']);
			$ary_data['try_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['try_desc']);
            //获取商品品牌并传递到模板
			$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
			$this->assign("array_brand",$array_brand);
			//获取商品分类并传递到模板
			$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
			$this->assign("array_category",$array_category);
			//商品类型
			$ary_type =  M('goods_type',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gt_status'=>1,'gt_type'=>1))->select();
			$this->assign('ary_type',$ary_type);
			//dump($ary_data);die();
			$this->assign('info',$ary_data);
            $this->display();
        }

    }

    /**
     * 执行修改试用
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-11
     */
    public function doEdit(){
        $Try = D('Try');
		$ary_data = $this->_post();
		$int_try_id = $ary_data['try_id'];
        //试用数组
        $ary_data['try_title'] = trim($ary_data['try_title']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $try_count = $Try->where(array('try_id'=>$int_try_id))->count();
        if ($try_count == 0) {
            $this->error('试用不存在或已删除');
        } 
        //更新条件
        $try_where = array();
        $try_where['try_id'] =  $int_try_id; 
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($ary_data['try_title'])) {
            $this->error('试用名称不存在');
        }
		if(empty($ary_data['property_typeid'])){
			$this->error('试用报告类型不能为空');
		}
        //试用标题必须输入且不能大约250个字符
        if(strlen($ary_data['try_title'])>250){
            $this->error('试用名称不能大与250个字符');
        }
        //验证试用名称是否重复
		$where = array('try_title' => $ary_data['try_title']);
        $where['try_id'] = array('neq',$int_try_id);
        if (false != $Try ->where($where)->find()) {
            $this->error('试用名称已被使用');
        }
        $ary_data['g_id'] = trim($ary_data['g_id']);
        //验证商品ID是否存在
        if(empty($ary_data['g_id'])){
            $this->error('请先选择商品信息');
        }
        $goods_count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id']))->count();
        if($goods_count == 0){
            $this->error('商品信息不存在');
        }
        $good_where = array('g_id'=>$ary_data['g_id'],'try_status'=>array('neq',2));
        $good_where['try_id'] = array('neq',$int_try_id);
        $Try_count = $Try->where($good_where)->count();
        if($Try_count != 0){
            $this->error('此商品已被其他试用使用');
        }
		$ary_data['try_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['gp_pic']);

        if($ary_data['try_start_time']){
            $ary_data['try_start_time'] = $ary_data['try_start_time'];
        }
        if($ary_data['try_end_time']){
            $ary_data['try_end_time'] = $ary_data['try_end_time'];
        }
        if($ary_data['try_start_time']>$ary_data['try_start_time']){
            $this->error('活动开始时间大于活动实效时间时间！');
        }
		if($ary_data['try_num']){
            $ary_data['try_num'] = intval($ary_data['try_num']);
        }else{
            $this->error('试用数量必须输入！');
        }
        // 试用前答题
        if($ary_data['property_typeid_front']){
            $ary_data['property_typeid_front'] = $ary_data['property_typeid_front'];
        }
		$total_stock = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_data['g_id'],'pdt_status'=>1))->sum('pdt_stock');
		if($ary_data['try_num']>$total_stock){
			$ary_data['try_num'] = $total_stock;
		}
		if($ary_data['try_order']){
            if(is_numeric($ary_data['try_order'])){
                $ary_data['try_order'] = $ary_data['try_order'];
            }
        }
		$ary_data['try_status'] = $ary_data['try_status'] ? $ary_data['try_status'] : '0';
        $ary_data['try_is_show_detail'] = $ary_data['try_is_show_detail'] ? $ary_data['try_is_show_detail'] : '0';
        if($ary_data['try_desc']){
            $ary_data['try_desc'] = _ReplaceItemDescPicDomain($ary_data['try_desc']);
        }
		
        $ary_data['try_update_time'] = date("Y-m-d H:i:s");
        //插入数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$trans->startTrans();
        $res_return = $Try->data($ary_data)->where($try_where)->save();
        if (!$res_return) {
            $trans->rollback();
            $this->error('试用修改失败');
        }else{
            $trans->commit();
            $this->success('试用修改成功', U('Admin/Try/pageList'));
        }
    }

    /**
     * 试用设置
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-18
     */
    public function pageSet(){
        $this->getSubNav(5, 7, 40);
        //取出关联广告图片
        $ary_ads = D('RelatedTryAds')->order('sort_order asc')->select();
        $ary_ad_infos = array();
        foreach($ary_ads as $ary_ad){
			$ary_ad['ad_pic_url']=D('QnPic')->picToQn($ary_ad['ad_pic_url']);
            $ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
        }
        unset($ary_ads);
        $this->assign('ary_ads', $ary_ad_infos);
        $this->display();
    }

    /**
     * 添加试用设置
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-18
     */
    public function doAddSet(){
        $ary_data = $this->_post();
        M('')->startTrans();
        //关联广告图片
        $RelatedTryAds = D('RelatedTryAds');
        //先删除关联图片信息
        M()->query('TRUNCATE table `fx_related_try_ads`');
        $res = true;
        for($i=0;$i<5;$i++){
            if(!empty($ary_data['GY_SHOP_TOP_AD_'.$i])){
                $ary_insert = array();
                $ary_insert = array(
                    //'ad_pic_url'=>str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i])),
                    'sort_order'=>intval($ary_data['sort_order_'.$i]),
                    'ad_url'=>$ary_data['GY_SHOP_TOP_AD_'.$i.'_URL']
                );
				//七牛图片存入
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){
					$ary_insert['ad_pic_url']=$ary_data['GY_SHOP_TOP_AD_'.$i];
				}else{
					$ary_insert['ad_pic_url']=str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i]));
				}
				$ary_insert['ad_pic_url'] = D('ViewGoods')->ReplaceItemPicReal($ary_insert['ad_pic_url']);
                $res = $RelatedTryAds->data($ary_insert)->add();
                if(!$res){
                    M('')->rollback();
                    break;
                    // $this->error('试用广告图片保存失败');
                }
            }
        }
        M('')->commit();
        if (!$res) {
            $this->error('试用设置生成失败');
        } else {
            $this->success('试用设置生成成功', U('Admin/Try/pageSet'));
        }
    }

    /**
     * 申请试用列表
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-10
     */
    public function apply_index(){
        $this->getSubNav(5, 7, 20, '试用活动申请列表');
        $ApplyModel = D('try_apply_records');
        // 条件组装
        $ary_cond = array();
        $ary_data = $this->_request();
        if(isset($ary_data['field']) && !empty($ary_data['field']) && isset($ary_data['val']) && !empty($ary_data['val'])){
            switch ($ary_data['field']) {
                case 1: // 试用标题搜索
                    $ary_cond['t.try_title'] = array('like','%'.$ary_data['val'].'%');
                    break;
                case 2: // 商品名称搜索
                    $ary_cond['g.g_name'] = array('like','%'.$ary_data['val'].'%');
                    break;
                default:
                    break;
            }
        }
        if(isset($ary_data['applyStatus'])){    // 试用申请状态搜索
            $ary_cond[C('DB_PREFIX').'try_apply_records.try_status'] = $ary_data['applyStatus'];
        }
        // 查询数据
        $count = $ApplyModel
        ->join(C('DB_PREFIX')."goods_info as g on(g.g_id=".C('DB_PREFIX')."try_apply_records.g_id)")
        ->join(C('DB_PREFIX')."members as m on(m.m_id=".C('DB_PREFIX')."try_apply_records.m_id)")
        ->join(C('DB_PREFIX')."try as t on(t.g_id=".C('DB_PREFIX')."try_apply_records.g_id)")
        ->where($ary_cond)
        ->count('distinct(fx_try_apply_records.tar_id)');
	
        $Page = new Page($count, 20);
        $ary_datalist = $ApplyModel
        ->field(C('DB_PREFIX').'try_apply_records.*,m.m_name,g.g_name,t.try_title')
        ->join(C('DB_PREFIX')."goods_info as g on(g.g_id=".C('DB_PREFIX')."try_apply_records.g_id)")
        ->join(C('DB_PREFIX')."members as m on(m.m_id=".C('DB_PREFIX')."try_apply_records.m_id)")
        ->join(C('DB_PREFIX')."try as t on(t.g_id=".C('DB_PREFIX')."try_apply_records.g_id)")
        ->where($ary_cond)
        ->order(array("tar_create_time"=>"desc"))
        ->limit($Page->firstRow . ',' . $Page->listRows)
		->group('fx_try_apply_records.tar_id')
        ->select();
        $ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
        // 数据输出
        $this->assign("filter",$ary_data);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 拒绝申请
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-11
     */
    public function doRefuseStatus(){
        if(IS_AJAX){
            $tar_id = intval($this->_post('tar_id'));
            if(empty($tar_id)){
                $this->ajaxReturn('error','请选择要拒绝的记录',1);
            }
            $TryModel = D('Try');
            if($TryModel->ChangeStatus(array('tar_id'=>$tar_id,'try_status'=>0))){
                $this->ajaxReturn('success','操作成功',2);
            }else{
                $this->ajaxReturn('failed','操作失败',3);
            }
        }else{
            $this->ajaxReturn('error_message','非AJAX操作',0);
        }
    }

    /**
     * 查看问题
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-11
     */
    public function ShowApplyQuestion(){
        if(IS_AJAX){
            $tar_id = intval($this->_post('tar_id'));
            $ary_data = array();
            if(!empty($tar_id)){
                $TryModel = D('Try');
                $ary_data = $TryModel->getApplyQuestion(array('tar_id'=>$tar_id));
            }
            $this->assign('ary_data', $ary_data);
            $this->display();
        }else{
            echo "error";exit();
        }
    }

    /**
     * 批量审核申请
     * @author Tom <helong@guanyisoft.com>
     * 1.检验申请合法性
     * 2.自动选择发货方式,商品规格
     * 3.生成订单(无金额/无需支付)
     * 4.生成订单日志
     * @date 2014-10-11
     */
    public function checkAudit(){
        $str_apply_id = $this->_request('apply_id');
        if($str_apply_id){
            M('', '', 'DB_CUSTOM')->startTrans();
            $ary_apply      = explode(',',$str_apply_id);
            $applyModel     = D('try_apply_records');   // 申请表
            $logisticView   = D('ViewLogistic');        // 物流
            $productsModel  = D('goods_products');      // 商品规格
            $memberModel    = D('members');             // 会员
            $goodsInfoModel = D('goods_info');          // 商品信息
            foreach($ary_apply as $apply_id){
                // 1.1 验证申请合法性(0:是否为未审核状态;)
                $ary_apply_info = $applyModel->where(array('tar_id'=>$apply_id,'try_status'=>0))->find();
                if(empty($ary_apply_info)){
                    $this->error('订单不允许审核');
                }
                // 1.2 获取会员信息
                $ary_member_info = $memberModel->where(array('m_id'=>$ary_apply_info['m_id']))->find();
                if(empty($ary_apply_info)){
                    $this->error('订单不允许审核');
                }
                // 1.3 查询排序最高配送方式
                $ary_logistic = $logisticView->where(array('lc_is_enable'=>1))->order('lc_ordernum ASC')->find();
                if(empty($ary_logistic)){
                    $this->error('无配送!请先添加配送!');
                }
                // 1.4 查找商品规格(随机)
                $ary_pdt = $productsModel->where(array('g_id'=>$ary_apply_info['g_id']))->select();
                if(empty($ary_pdt)){
                    $this->error('订单不允许审核');
                }
                $ary_pdt_key = array_rand($ary_pdt);
                $ary_pdt_info = $ary_pdt[$ary_pdt_key];
                // 1.5 获取商品信息
                $ary_goods_info = $goodsInfoModel->where(array('g_id'=>$ary_apply_info['g_id']))->find();

                // 2.1 订单数据组装
                $ary_order = array(
                 'o_id'                => date('YmdHis') . rand(1000, 9999),
                 'm_id'                => $ary_apply_info['m_id'],                  // 会员ID
                 'o_pay_status'        => 1,                                        // 支付状态 0.未支付，1.已支付，2.处理中，3部分支付
                 'o_receiver_name'     => $ary_apply_info['o_receiver_name'],       // 收货人
                 'o_receiver_mobile'   => $ary_apply_info['o_receiver_mobile'],     // 收货人手机
                 'o_receiver_telphone' => $ary_apply_info['o_receiver_telphone'],   // 收货人电话
                 'o_receiver_state'    => $ary_apply_info['o_receiver_state'],      // 收货人省份
                 'o_receiver_city'     => $ary_apply_info['o_receiver_city'],       // 收货人城市
                 'o_receiver_county'   => $ary_apply_info['o_receiver_county'],     // 地区第三级（文字)
                 'o_receiver_address'  => $ary_apply_info['o_receiver_address'],    // 收货人地址
                 'ra_id'               => $ary_apply_info['ra_id'],                 // 收货地址id（最后一级id
                 'o_receiver_zipcode'  => $ary_apply_info['o_receiver_zipcode'],    // 收货人邮编
                 'o_create_time'       => date('Y-m-d H:i:s'),                      // 订单创建时间
                 'o_receiver_email'    => $ary_member_info['m_email'],              // 收货人Email
                 'lt_id'               => $ary_logistic['lt_id'],                   // 配送ID
                 );
                // 2.2 订单扩展信息
                $ary_order_item = array(
                 'g_id'           => $ary_apply_info['g_id'],   // 商品ID
                 'o_id'           => $ary_order['o_id'],        // 订单ID
                 'pdt_id'         => $ary_pdt_info['pdt_id'],
				 'pdt_sn'         => $ary_pdt_info['pdt_sn'],
                 'g_sn'           => $ary_pdt_info['g_sn'],
                 'oi_cost_price'  => $ary_pdt_info['pdt_cost_price'],   // 成本价
                 'oi_g_name'      => $ary_goods_info['g_name'],         // 商品名称
                 'oi_create_time' => date('Y-m-d H:i:s'),
                 'oi_type'        => 9,  // 商品类型 9.试用商品
                 );
                // 2.3 订单日志
                $ary_order_log = array(
                 'o_id'        => $ary_order['o_id'],
                 'ol_behavior' => '创建并支付',
                 'ol_text'     => '申请试用订单',
                 'ol_uname'    => $ary_member_info['m_name'],
                 'ol_create'   => date('Y-m-d H:i:s')
                 );
                /**
                 * [数据库操作]
                 * 1.生成订单
                 * 2.更新试用申请表
                 * 3.生成订单日志
                 * 4.扣减试用数量
                 */
                $orderModel     = D('orders');
                $orderItemModel = D('orders_items');
                $orderLogModel  = D('orders_log');
                $tryModel       = D('try');
				$IS_AUTO_AUDIT = D('SysConfig')->getCfgByModule('IS_AUTO_AUDIT');
				if($IS_AUTO_AUDIT['IS_AUTO_AUDIT'] == 1 ){
					$ary_order['o_audit'] = 1;
				}				
                $tagOrder     = $orderModel->add($ary_order);            // 添加订单
                $tagOrderItem = $orderItemModel->add($ary_order_item);   // 订单扩展表
                $tagOrderLog  = $orderLogModel->add($ary_order_log);     // 订单日志
                $tagTry = $tryModel->where(array('g_id'=>$ary_apply_info['g_id']))->setDec('try_num');
                $tagApply     = $applyModel
                            ->where(array('tar_id'=>$apply_id,'try_status'=>0))
                            ->save(array('try_status'=>1,'try_oid'=>$ary_order['o_id'],'u_id'=>$_SESSION['Admin']));
                if($tagOrder !== false && $tagOrderItem !== false && $tagOrderLog !== false && $tagApply !== false){
                    M('', '', 'DB_CUSTOM')->commit();
                    $this->success('订单审核成功!');
                }else{
                    M('', '', 'DB_CUSTOM')->rollback();
                    $this->error('订单审核失败!');
                }
            }
        }else{
            $this->error('订单审核失败!');
        }
    }

    /**
     * 试用报告列表
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
    public function report(){
        $this->getSubNav(5, 7, 30, '试用报告列表');
        $ReportModel = D('try_report');
        // 条件组装
        $ary_cond = array();
        $ary_data = $this->_request();
        if(isset($ary_data['field']) && !empty($ary_data['field']) && isset($ary_data['val']) && !empty($ary_data['val'])){
            switch ($ary_data['field']) {
                case 1: // 试用标题搜索
                    $ary_cond['try.try_title'] = array('like','%'.$ary_data['val'].'%');
                    break;
                default:
                    break;
            }
        }
        if(isset($ary_data['reportStatus'])){    // 试用申请状态搜索
            $ary_cond[C('DB_PREFIX').'try_report.tr_status'] = $ary_data['reportStatus'];
        }
        // 查询数据
        $count = $ReportModel
        ->join(C('DB_PREFIX')."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id)")
        ->join(C('DB_PREFIX')."try_apply_records as apply on(apply.g_id=try.g_id and ".C('DB_PREFIX')."try_report.m_id=apply.m_id)")
        ->join(C('DB_PREFIX')."members as m on(m.m_id=apply.m_id)")
        ->where($ary_cond)
        ->count();
        $Page = new Page($count, 20);
        $ary_datalist = $ReportModel
        ->field(C('DB_PREFIX').'try_report.*,try.*,m.m_name,apply.*')
        ->join(C('DB_PREFIX')."try as try on(try.try_id=".C('DB_PREFIX')."try_report.try_id)")
        ->join(C('DB_PREFIX')."try_apply_records as apply on(apply.g_id=try.g_id and ".C('DB_PREFIX')."try_report.m_id=apply.m_id)")
        ->join(C('DB_PREFIX')."members as m on(m.m_id=apply.m_id)")
        ->where($ary_cond)
        ->order(array("tr_create_time"=>"desc"))
        ->limit($Page->firstRow . ',' . $Page->listRows)
        ->select();

        $ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
        // 数据输出
        $this->assign("filter",$ary_data);
        $this->assign($ary_data);
        $this->display();
    }

    /**
     * 试用报告详情
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
    public function ReportDetail(){
        if(IS_AJAX){
            $int_tr_id = intval($this->_post('tr_id'));
            $ary_data = array();
            if(!empty($int_tr_id)){
                $reportModel = D('TryReport');
                $ary_data = $reportModel->getReportQuestion(array('tr_id'=>$int_tr_id));
            }
            $this->assign('ary_data',$ary_data);
            $this->display();
        }else{
            echo "error";exit();
        }
    }

    /**
     * 试用报告审核
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function ReportAudit(){
        if(IS_AJAX){
            $int_tr_id = intval($_REQUEST['tr_id']);
            if(empty($int_tr_id)){
                $this->ajaxReturn('error','不存在该数据',0);
            }
            $tag = D('TryReport')->changeReportStatus(array('tr_id'=>$int_tr_id),array('tr_status'=>1,'tr_update_time'=>date('Y-m-d')));
            if($tag){
                $this->ajaxReturn('success','操作成功!',1);
            }else{
                $this->ajaxReturn('error','操作失败!',0);
            }
        }else{
            echo "error";exit();
        }
    }

}