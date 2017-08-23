<?php

/**
 * 后台秒杀控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-11-08
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SpikeAction extends AdminAction {

    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
    }

    /**
     * 后台秒杀控制器默认页，需要重定向
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index() {
        $this->redirect(U('Admin/Spike/pageList'));
    }

    public function pageList() {
        $this->getSubNav(5, 4, 10);
        $mod = D($this->_name);
        $ary_data = $this->_get();
        //搜索条件处理
        $array_cond = array();
        //如果根据名称进行搜索
        switch ($ary_data['field']) {
            case 1:
                $array_cond[C('DB_PREFIX')."spike.sp_title"] = array("LIKE", "%" . $ary_data['val'] . "%");
                break;
            case 2:
                $array_cond["gi.g_name"] = array("LIKE", "%" . $ary_data['val'] . "%");
                break;
            case 3:
				if(!empty($ary_data['val'])){
					$array_cond["g.g_sn"] = $ary_data['val'];
				}
                break;
            default:
                break;
        }
		if(!empty($ary_data['gcid'])){
			$array_cond["fx_spike.gc_id"] = intval($ary_data['gcid']);
		}
        //如果根据团购的有效期进行搜索
        if (isset($ary_data["sp_start_time"]) && "" != $ary_data["sp_start_time"]) {
            $array_cond["sp_start_time"] = array("egt", $ary_data["sp_start_time"]);
        }
        if (isset($ary_data["sp_end_time"]) && "" != $ary_data["sp_end_time"]) {
            $array_cond["sp_end_time"] = array("elt", $ary_data["sp_end_time"]);
        }
        $count = $mod
                        ->join(" ".C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."spike.g_id)")
                        ->join(" ".C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."spike.g_id)")
                        ->where($array_cond)->count();
		
        $Page = new Page($count, 15);
        $ary_datalist = $mod->field('gi.g_name,g.g_sn,'.C('DB_PREFIX').'spike.*')
                ->join( " ".C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."spike.g_id)")
                ->join(" ".C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."spike.g_id)")
                ->where($array_cond)
                ->order(array('sp_update_time' => 'desc'))
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        $ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
        $this->assign("filter", $ary_data);
        $this->assign($ary_data);
        $cates = D('Gyfx')->selectAll('spike_category');
		$this->assign("gcid",intval($ary_data['gcid']));
		$this->assign("cates",$cates);        		
        $this->display();
    }

    /**
     * 
     * 新增秒杀活动
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-11
     */
    public function add() {
        $this->getSubNav(5, 4, 20);
        $array_brand = D("GoodsBrand")->where(array("gb_status" => 1))->select();
        //获取商品分类并传递到模板
        $array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
        $this->assign("array_brand", $array_brand);
        $this->assign("array_category", $array_category);
 	 	$cates = D('Gyfx')->selectAll('spike_category');
		$this->assign("cates",$cates);       		
        $this->display();
    }

    /**
     * 处理秒杀添加
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-11
     */
    public function doAdd() {
        $ary_post = $this->_post();
        $mod = D($this->_name);
        $ary_data['sp_title'] = trim($ary_post['sp_title']);
        //验证商品ID是否存在
        $ary_data['g_id'] = trim($ary_post['g_id']);
        if (empty($ary_post['g_id'])) {
            $this->error('请先选择商品信息');
        }
        $goods_count = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $ary_post['g_id']))->count();
        if ($goods_count == 0) {
            $this->error('商品信息不存在');
        }
		
        $where_sp = "1=1";
        $where_sp .= " and g_id=".$ary_post['g_id'];
        $where_sp .= " and sp_end_time > current_timestamp()";
        $spike_count = $mod->where($where_sp)->count();
        
        if ($spike_count != 0) {
            $this->error('此商品已被其他秒杀使用',U('Admin/Spike/pageList'));
        }
        if ($_FILES['sp_picture']['error'] == 0) {
            $img_path = '/Public/Uploads/' . CI_SN . '/' . 'other/' . date('Ymd') . '/';
            if (!is_dir(APP_PATH . $img_path)) {
                //如果目录不存在，则创建之
                mkdir(APP_PATH . $img_path, 0777, 1);
            }
            //生成本地分销地址
            $imge_url = $img_path . 'miao' . date('YmdHis') . $ary_post['g_id'] . '.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $imge_url;
            if (move_uploaded_file($_FILES['sp_picture']['tmp_name'], $img_save_path)) {
                $ary_data['sp_picture'] = $imge_url;
            }
        }
        if ($ary_data['sp_picture']) {
            $ary_data['sp_picture'] = $ary_data['sp_picture'];
        } else {
            $ary_data['sp_picture'] = $ary_post['sp_pic'];
        }
		//七牛图片存入
		$ary_data['sp_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['sp_picture']); 
        if ($ary_post['sp_start_time']) {
            $ary_data['sp_start_time'] = $ary_post['sp_start_time'];
        }
        if ($ary_post['sp_end_time']) {
            $ary_data['sp_end_time'] = $ary_post['sp_end_time'];
        }
        if ($ary_post['sp_start_time'] > $ary_post['sp_end_time']) {
            $this->error('活动开始时间大于活动实效时间时间！');
        }
        $ary_data['sp_number'] = !empty($ary_post['sp_number']) ? $ary_post['sp_number'] : 0;

		$ary_data['gc_id'] = intval($ary_post['gcid']);
        if ($ary_post['sp_send_point']) {
            $ary_data['sp_send_point'] = $ary_post['sp_send_point'];
        }
        $ary_data['sp_status'] = $ary_post['sp_status'] ? $ary_post['sp_status'] : '0';
        $ary_data['sp_goods_desc_status'] = $ary_post['sp_goods_desc_status'] ? $ary_post['sp_goods_desc_status'] : '0';
        if ($ary_post['sp_desc']) {
            $ary_data['sp_desc'] = $ary_post['sp_desc'];
        }
        if (isset($ary_post['sp_mobile_desc'])) {
            $ary_data['sp_mobile_desc'] = $ary_post['sp_mobile_desc'];
        }else{
			$ary_data['sp_mobile_desc'] = '';
		}
		//七牛图片存入
		$ary_data['sp_desc'] = _ReplaceItemDescPicDomain($ary_data['sp_desc']); 
		if(isset($ary_data['sp_mobile_desc'])){
			$ary_data['sp_mobile_desc'] = _ReplaceItemDescPicDomain($ary_data['sp_mobile_desc']); 
		}
        if ($ary_post['sp_price']) {
            $ary_data['sp_price'] = $ary_post['sp_price'];
        }
        $ary_data['sp_tiered_pricing_type'] = $ary_post['sp_tiered_pricing_type'];
        $ary_data['sp_create_time'] = date("Y-m-d H:i:s");
        $ary_data['sp_update_time'] = date("Y-m-d H:i:s");
        $trans = M('', C('DB_PREFIX'), 'DB_CUSTOM');
        $trans->startTrans();
        $res_result = $mod->add($ary_data);
        if (FALSE !== $res_result) {
            //关联区域
            $g_related_goods_ids = $ary_post['goods']['g_related_goods_ids'];
            $g_related_goods_ids = substr($g_related_goods_ids,0 ,-1);
            $g_related_goods_ids = explode(',',$g_related_goods_ids);   
            array_unique($g_related_goods_ids); 
            $area_obj = M('related_spike_area', C('DB_PREFIX'), 'DB_CUSTOM');
            $spike_result = true;
            foreach($g_related_goods_ids as $cr_id){
                if($cr_id){
                        $area_res = $area_obj->data(array('cr_id'=>$cr_id,'sp_id'=>$res_result))->add();
                        if(!$area_res){
                            $spike_result = false;
                            $trans->rollback();
                            $this->error('秒杀生成失败，更新关联区域时失败');
                        }
                }
            }  
            if($spike_result){
                $trans->commit();
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"秒杀活动添加成功",'秒杀为：'.$ary_data['sp_title']));
                $this->success("秒杀活动添加成功",U('Admin/Spike/pageList'));
            }else{
                $trans->rollback();
                $this->error('秒杀生成失败，更新关联区域时失败');
            }
            
        } else {
            $trans->rollback();
            $this->error('秒杀生成失败');
        }
    }

    /**
     * 异步获取 区域数据
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-11
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
        $array_result = D("CityRegion")->where(array("cr_parent_id" => $int_parent_id, 'cr_status' => '1'))->order(array("cr_order" => "asc"))->getField("cr_id,cr_name");
        if (false === $array_result) {
            echo json_encode(array("status" => false, "data" => array(), "message" => "无法获取区域数据"));
            exit;
        }
        // dump($array_result);die();
        echo json_encode(array("status" => true, "data" => $array_result, "message" => "success"));
        exit;
    }

    /**
     * 检验秒杀名称是否存在
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-11
     */
    public function checkName() {
        $ary_get = $this->_get();

        $mod = D($this->_name);
        if(!empty($ary_get['sp_id'])){
            $where = array();
            $where['sp_title'] = $ary_get['sp_title'];
            $where['sp_id'] = array("NEQ",$ary_get['sp_id']);
            $ary_data = $mod->where($where)->find();
            if (!empty($ary_data) && is_array($ary_data)) {
                $this->ajaxReturn("该秒杀活动已经存在");
            } else {
                $this->ajaxReturn(true);
            }
        }else{
            $ary_data = $mod->where(array('sp_title' => $ary_get['sp_title']))->find();
            if (!empty($ary_data) && is_array($ary_data)) {
                $this->ajaxReturn("该秒杀活动已经存在");
            } else {
                $this->ajaxReturn(true);
            }
        }
    }
    
    /**
     * 删除描述
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-11-21
     */
    public function doDel() {
        $mod = D($this->_name);
        $mix_id = $this->_param('sp_id');
//        echo "<pre>";print_r($mix_id);exit;
        if(empty($mix_id)){
        	$this->error('请先选择要删除的秒杀');
        }
        if(is_array($mix_id)){
            $str_id = implode(",",$mix_id);
            $where = array('sp_id' => array('IN',$str_id));
        }else{
            $ary_id = explode(",",$mix_id);
            if (is_array($ary_id)) {
                //批量删除
                $where = array('sp_id' => array('IN',$mix_id));
            } else {
                //单个删除
                $where = array('sp_id' => $mix_id);
            }
        }
		$props = $mod->where($where)->field('sp_title')->select();
		$str_prop_name = '';
		foreach($props as $prop){
			$str_prop_name .=$prop['sp_title'];
		}
		$str_prop_name = trim($str_prop_name,',');
		
		$tmp_mix_id = implode(',',$mix_id);
        $mod->startTrans();
        $res_return = $mod->where($where)->delete();
        
        if (false == $res_return) {
            $mod->rollback();
            $this->error('删除失败');
        } else {
            $ary_res = M('related_spike_area',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->delete();
            if(FALSE !== $ary_res){
                $mod->commit();
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"秒杀删除成功",'秒杀为：'.$tmp_mix_id.'-秒杀名称：'.$str_prop_name));				
                $this->success('删除成功');
            }else{
                $mod->rollback();
                $this->error('删除失败');
            }
            
        }
    }
    
    public function edit(){
        $this->getSubNav(5, 4, 10, '编辑秒杀');
        $int_gp_id = $this->_get('sp_id');
        $mod = D($this->_name);
        $ary_data = $mod->field('gi.g_name,g.g_sn,'.C('DB_PREFIX').'spike.*')
		->join(C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."spike.g_id)")
        ->join(C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."spike.g_id)")
        ->where(array('sp_id'=>$int_gp_id))->find();
        if(false == $ary_data){
            $this->error('秒杀参数错误');
        }else{
			//七牛图片显示
			$ary_data['sp_picture'] = D('QnPic')->picToQn($ary_data['sp_picture']);
			$ary_data['sp_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['sp_desc']);
			$ary_data['sp_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['sp_mobile_desc']);
        	//获取商品品牌并传递到模板
			$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
			$this->assign("array_brand",$array_brand);
			//获取商品分类并传递到模板
			$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
			$this->assign("array_category",$array_category); 
            //获取关联区域表
 	        $ary_data['related_areas'] = M('related_spike_area',C('DB_PREFIX'),'DB_CUSTOM')
 	        ->join("fx_city_region on(".C('DB_PREFIX')."city_region.cr_id=".C('DB_PREFIX')."related_spike_area.cr_id)")
 	        ->field(C('DB_PREFIX').'related_spike_area.*,'.C('DB_PREFIX').'city_region.cr_name')
	        ->where(array(C('DB_PREFIX').'related_spike_area.sp_id'=>$int_gp_id))->select();  
            $this->assign('info',$ary_data);
            $cates = D('Gyfx')->selectAll('spike_category');
			$this->assign("cates",$cates);
			$this->display();
        }
    }
    
    /**
     * 执行修改秒杀
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-11-21
     */
    public function doEdit(){
     	$ary_post = $this->_post();
     	$int_gp_id = $ary_post['sp_id'];
        $mod = D($this->_name);
        //秒杀数组
        $ary_data['sp_title'] = trim($ary_post['sp_title']);
        //验证数据有效性 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $gp_count = $mod->where(array('sp_id'=>$int_gp_id))->count();
        if ($gp_count == 0) {
            $this->error('秒杀不存在或已删除');
        } 
        //更新条件
        $gp_where = array();
        $gp_where['sp_id'] =  $int_gp_id; 
        if (empty($ary_post['sp_title'])) {
            $this->error('秒杀名称不存在');
        }
        //秒杀标题必须输入且不能大约250个字符
        if(strlen($ary_post['sp_title'])>250){
        	$this->error('秒杀名称不能大与250个字符');
        }
        //验证秒杀名称是否重复
        $ary_data['gc_id'] = intval($ary_post['gcid']);
        $spike_where = array('g_id'=>$ary_post['g_id']);
        $spike_where['sp_id'] = array('neq',$int_gp_id);
        $spike_count = $mod->where($spike_where)->count();
        if($spike_count != 0){
            $this->error('秒杀名称已被使用');
        }
        $ary_data['g_id'] = trim($ary_post['g_id']);
        //验证商品ID是否存在
        if(empty($ary_data['g_id'])){
        	 $this->error('请先选择商品信息');
        }
        $goods_count = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array('g_id'=>$ary_post['g_id']))->count();
        if($goods_count == 0){
        	$this->error('商品信息不存在');
        }
        $groupbuy_where = array('g_id'=>$ary_post['g_id']);
        $groupbuy_where['sp_id'] = array('neq',$int_gp_id);
        $groupbuy_count = $mod->where($groupbuy_where)->count();
        if($groupbuy_count != 0){
        	$this->error('此商品已被其他秒杀使用');
        }       
    	if($_FILES['sp_picture']['error']==0){
			 $img_path = '/Public/Uploads/' . CI_SN . '/'.'other/'.date('Ymd').'/';
	         if (!is_dir(APP_PATH .$img_path)) {
            	//如果目录不存在，则创建之
             	mkdir(APP_PATH .$img_path, 0777, 1);
        	 }
             //生成本地分销地址
            $imge_url = $img_path . 'spike'.date('YmdHis').$ary_data['g_id'].'.jpg';
            //生成图片保存路径
            $img_save_path = APP_PATH . $imge_url;
            if(move_uploaded_file($_FILES['sp_picture']['tmp_name'],$img_save_path)){
				$ary_post['sp_picture'] = $imge_url;
            }
		} 
        if ($ary_post['sp_picture']) {
            $ary_data['sp_picture'] = $ary_post['sp_picture'];
        } else {
            $ary_data['sp_picture'] = $ary_post['sp_pic'];
        }
		$ary_data['sp_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['sp_picture']);
        if ($ary_post['sp_start_time']) {
            $ary_data['sp_start_time'] = $ary_post['sp_start_time'];
        }
        if ($ary_post['sp_end_time']) {
            $ary_data['sp_end_time'] = $ary_post['sp_end_time'];
        }
        if ($ary_post['sp_start_time'] > $ary_post['sp_end_time']) {
            $this->error('活动开始时间大于活动实效时间时间！');
        }

        $ary_data['sp_number'] = !empty($ary_post['sp_number']) ? $ary_post['sp_number'] : 0;

        if ($ary_post['sp_send_point']) {
            $ary_data['sp_send_point'] = $ary_post['sp_send_point'];
        }
        $ary_data['sp_status'] = $ary_post['sp_status'] ? $ary_post['sp_status'] : '0';
        $ary_data['sp_goods_desc_status'] = $ary_post['sp_goods_desc_status'] ? $ary_post['sp_goods_desc_status'] : '0';
        if ($ary_post['sp_desc']) {
            $ary_data['sp_desc'] = $ary_post['sp_desc'];
        }
        if (isset($ary_post['sp_mobile_desc'])) {
            $ary_data['sp_mobile_desc'] = $ary_post['sp_mobile_desc'];
        }else{
			$ary_data['sp_mobile_desc'] = '';
		}
		$ary_data['sp_desc'] = _ReplaceItemDescPicDomain($ary_data['sp_desc']);
		if(isset($ary_data['sp_mobile_desc'])){
			$ary_data['sp_mobile_desc'] = _ReplaceItemDescPicDomain($ary_data['sp_mobile_desc']);
		}
        if ($ary_post['sp_price']) {
            $ary_data['sp_price'] = $ary_post['sp_price'];
        }
        $ary_data['sp_tiered_pricing_type'] = $ary_post['sp_tiered_pricing_type'];
        $ary_data['sp_update_time'] = date("Y-m-d H:i:s");
		$mod->startTrans();
        $res_return = $mod->data($ary_data)->where($gp_where)->save();
        if (!$res_return) {
            $mod->rollback();
            $this->error('秒杀修改失败');
        } else {
            //关联区域
            $g_related_goods_ids = $ary_post['goods']['g_related_goods_ids'];
            $g_related_goods_ids = substr($g_related_goods_ids,0 ,-1);
            $g_related_goods_ids = explode(',',$g_related_goods_ids);    
            array_unique($g_related_goods_ids);
            $area_obj = M('related_spike_area', C('DB_PREFIX'), 'DB_CUSTOM');
            //删除关联区域
            if($area_obj->where($gp_where)->count()>0){
                $area_result =  $area_obj->where($gp_where)->delete();
                    if(!$area_result){
                            $mod->rollback();
                            $this->error('秒杀修改失败，删除区域时失败');      		
                    }	        	
            }
            $spike_result = true;
            foreach($g_related_goods_ids as $cr_id){
                    if($cr_id){
                            $area_res = $area_obj->data(array('cr_id'=>$cr_id,'sp_id'=>$int_gp_id))->add();
                            if(!$area_res){
                                $spike_result = false;
                                $mod->rollback();
                                $this->error('秒杀修改失败，更新关联区域时失败');
                            }
                    }
            }  
            if($spike_result){
                $mod->commit();
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"秒杀修改成功",'秒杀为：'.$ary_data['sp_title']));
                $this->success('秒杀修改成功', U('Admin/Spike/pageList'));
            }else{
                $mod->rollback();
                $this->error('秒杀修改失败');
            }
            
        }
    }
    
    /**
     * 后台秒杀分类列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateList() {
        $this->getSubNav(5, 4, 40);
		$ary_data = D('Gyfx')->selectAll('spike_category',$ary_field=null, $ary_where=null, array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
		$this->assign('ary_cates',$ary_data);
        $this->display();
    }
    
    /**
     * 后台秒杀分类添加
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function addCategory() {
        $this->getSubNav(5, 4, 40);
    	$this->display();
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
		$array_cond = array('gc_name'=>$_POST['gc_name'],"gc_parent_id"=>$_POST["gc_parent_id"]);
		$array_result =  D('Gyfx')->selectOne('spike_category',$ary_field=null, $array_cond);
		if(is_array($array_result) && !empty($array_result)){
			$this->error('已经存在同级的秒杀分类“' . $_POST['gc_name'] . '“！');
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
            @mkdir('./Public/Uploads/' . CI_SN.'/spike/');
	    	import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/spike/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_insert_data['gc_pic'] = '/Public/Uploads/'.CI_SN.'/spike/' . $info[0]['savename'];
			}
    	}
		//事务开始
 		$mixed_result =  D('Gyfx')->insert('spike_category',$array_insert_data);
		if(false === $mixed_result){
			$this->error("秒杀分类添加失败。");
		}
		
		//页面跳转
		$page_jump_url = U('Admin/Spike/pageCateList',array('gc_id'=>$cid));
		if(isset($_POST["page_jump"]) && 1 == $_POST["page_jump"]){
			$page_jump_url = U('Admin/Spike/addCategory');
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
            $ary_result = $action->update('spike_category',array('gc_id'=>$ary_request['id']),$ary_data);
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
     * 秒杀类目编辑页面显示
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateEdit(){
    	$this->getSubNav(5, 4, 40,'秒杀类目编辑');
        $gc_id=$this->_get('gcid');  
        if(isset($gc_id)){	
            $ary_data = D('Gyfx')->selectOne('spike_category','', array('gc_id'=>$gc_id));
            $this->assign('category',$ary_data);
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
		$array_cond = array("gc_id"=>array("neq",$int_gc_id),"gc_parent_id"=>$_POST["gc_parent_id"],"gc_name"=>$_POST["gc_name"]);
		$mixed_check_result = D("Gyfx")->selectOne('spike_category','',$array_cond);
		
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
            @mkdir('./Public/Uploads/' . CI_SN.'/spike/');
	    	import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/spike/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$array_modify_data['gc_pic'] = '/Public/Uploads/'.CI_SN.'/spike/' . $info[0]['savename'];
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
		$modify_result = D('Gyfx')->update('spike_category',array("gc_id"=>$int_gc_id),$array_modify_data);
		if(false === $modify_result){
			$this->error("秒杀分类更新失败，数据没有更新。");
		}
		$this->success('秒杀分类修改成功。', U('Admin/Spike/pageCateList'));   
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
    	$bool_res = D('Gyfx')->update('spike_category',array('gc_id'=>$int_gc_id),array('gc_pic'=>'','gb_update_time'=>date('Y-m-d H:i:s')));
    	if($bool_res){
    		$this->success('删除分类图片成功');
    	}else{
    		$this->error('删除分类图片失败');
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
        }else{
            $int_gc_id = $_GET["gcid"];
            $where = array('gc_id'=>$int_gc_id);
        }
        //删除商品分类
        $mixed_delete = D("Gyfx")->deleteInfo('spike_category',$where);
        if(false === $mixed_delete){
            $this->error("秒杀分类删除失败。");
        }
        //页面提示并跳转
        $this->success('删除成功', U('Admin/Spike/pageCateList'));
    }
    	
	/**
     * 后台秒杀设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageSet() {
        $this->getSubNav(5, 4, 30);
		//取出关联广告图片
		$ary_ads = D('RelatedSpikeAds')->order('sort_order asc')->select();
		$ary_ad_infos = array();
		foreach($ary_ads as $ary_ad){
			//七牛图片存入
			$ary_ad['ad_pic_url'] = D('QnPic')->picToQn($ary_ad['ad_pic_url']); 
			$ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
		}
		unset($ary_ads);
        $this->assign('ary_ads', $ary_ad_infos);
        $this->display();
    } 

	/**
     * 新增一条秒杀配置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doAddSet() {
        $ary_data = $this->_post();
		M('')->startTrans();
		//插入广告信息
		//关联广告图片
		$RelatedSpikeAds = D('RelatedSpikeAds');
		//先删除关联的分类和品牌信息
		M()->query('TRUNCATE table `fx_related_spike_ads`');
		//广告图片1
		for($i=0;$i<5;$i++){
			if(!empty($ary_data['GY_SHOP_TOP_AD_'.$i])){
				$ary_insert = array();
				$ary_insert = array(
					//'ad_pic_url'=>str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i])),
					'sort_order'=>intval($ary_data['sort_order_'.$i]),
					'ad_url'=>$ary_data['GY_SHOP_TOP_AD_'.$i.'_URL']
				);	
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){
					$ary_insert['ad_pic_url'] = $ary_data['GY_SHOP_TOP_AD_'.$i];
				}else{
					$ary_insert['ad_pic_url'] = str_replace('//','/',str_replace('/Lib/ueditor/php/../../..','',$ary_data['GY_SHOP_TOP_AD_'.$i]));
				}				
                //七牛图片存入
				$ary_insert['ad_pic_url'] = D('ViewGoods')->ReplaceItemPicReal($ary_insert['ad_pic_url']);				
				$res = $RelatedSpikeAds->data($ary_insert)->add();
				if(!$res){
					M('')->rollback();
					$this->error('团购广告图片保存失败');
				}				
			}
		}
		M('')->commit();
        if (!$res) {
            $this->error('秒杀设置生成失败');
        } else {
            $this->success('秒杀设置生成成功', U('Admin/Spike/pageSet'));
        }
    }
	
}
