<?php

/**
 * 后台促销活动控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-05
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PromotionsAction extends AdminAction {

    /**
     * 控制器初始化操作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
        $this->setTitle(' - ' . L('MENU4_0'));
    }

    /**
     * 默认控制器，重定向到促销规则列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function index() {
        $this->redirect(U('Admin/Promotions/pageProList'));
    }

    /**
     * 促销活动列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function pageList() {
        $this->getSubNav(5, 0, 10);
        $Promotion = D('Promotion');
        $data['types'] = $Promotion->getTypes();
        $order = array('pmn_order' => 'desc');
        $count = $Promotion->count();
        $Page = new Page($count, 15);
        $data['page'] = $Page->show();
        $data['list'] = $Promotion->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign($data);
        $this->display();
    }

    /**
     * 新增促销活动
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-05
     */
    public function pageAdd() {
 
        $this->getSubNav(5, 0, 20);
        $Promotion = D('Promotion');
        //当前已有的活动名称 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['active'] = $Promotion->field(array('pmn_activity_name'))->group('pmn_activity_name')->select();
        //当前可以使用的优先级 ++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['orders'] = $Promotion->getOrders();
        //会员组和会员等级 +++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        //促销的种类 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['types'] = $Promotion->getTypes();
        //echo "<pre>";print_r($data['types']);
        //默认全部会员
        $data['mAll'] = 1;
        // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 新增一个促销活动
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function doAdd() {
        $data = $this->_post();
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        $PromotionRule = PromotionModel::factory($data['pmn_class'], $data);
        $ary_type = $PromotionRule->getType(); //当前的促销规则说明
        $ary_cfg = $PromotionRule->getCfg();
        $ary_relation = $PromotionRule->getRel();
        //促销主表的操作 
        $insert['pmn_activity_name'] = $data['pmn_activity_name'];
        $insert['pmn_name'] = $data['pmn_name'];
        $insert['pmn_order'] = $data['pmn_order'];
        $insert['pmn_enable'] = $data['pmn_enable'];
        $insert['pmn_start_time'] = $data['pmn_start_time'];
        $insert['pmn_end_time'] = $data['pmn_end_time'];
        $insert['pmn_memo'] = $data['pmn_memo'];
        $insert['pmn_class'] = $data['pmn_class'];
        $insert['pmn_type'] = $ary_type['type'];
        $insert['pmn_create_time'] = date('Y-m-d h:i:s');
        $int_pmn_id = $Promotion->data($insert)->add();
        if (false == $int_pmn_id) {
            $this->error('促销规则添加失败');
        }
        //促销与会员、会员组、会员等级关系表的操作 +++++++++++++++++++++++++++++++++
        //1)促销与会员
        $insert_ra_mid = array();
        if ((int) $data['ra_all'] == 1) {
            //允许全部会员
            $insert_ra_mid = array('pmn_id' => $int_pmn_id, 'm_id' => -1);
            $PromotionMembers->data($insert_ra_mid)->add();
        } else {
            //指定的会员
            foreach ($data['ra_mid'] as $v) {
                $insert_ra_mid[] = array('pmn_id' => $int_pmn_id, 'm_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mid);
        }
        //2)促销与会员组
        $insert_ra_mgid = array();
        if ((int) $data['ra_all'] == 0) {
            foreach ($data['ra_mg'] as $v) {
                $insert_ra_mgid[] = array('pmn_id' => $int_pmn_id, 'mg_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mgid);
        }
        //3)促销与会员等级
        $insert_ra_mlid = array();
        if ((int) $data['ra_all'] == 0) {
            foreach ($data['ra_ml'] as $v) {
                $insert_ra_mlid[] = array('pmn_id' => $int_pmn_id, 'ml_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mlid);
        }
        //促销与商品关系表的操作 +++++++++++++++++++++++++++++++++++++++++++++++++
        foreach ($ary_relation as $k => $v) {
            $ary_relation[$k]['pmn_id'] = $int_pmn_id;
        }
        //echo "<pre>";print_r($ary_relation);exit;
        $PromotionGoods->addAll($ary_relation);
        //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"促销规则保存成功",'促销规则为：'.$data['pmn_name'].',ID:'.$int_pmn_id));
        $this->success('促销规则保存成功', U('Admin/Promotions/pageList'));
    }

    /**
     * 修改一个已有的促销活动
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-09
     */
    public function pageEdit() {
        $this->getSubNav(5, 0, 10, '编辑促销活动');
        $pid = (int) $this->_get('pid');
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        //当前的促销信息 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['info'] = $Promotion->where(array('pmn_id' => $pid))->find();
        //当前已有的活动名称 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['active'] = $Promotion->field(array('pmn_activity_name'))->group('pmn_activity_name')->select();
        //当前可以使用的优先级 ++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['orders'] = $Promotion->getOrders();
        //会员组和会员等级 +++++++++++++++++++++++++++++++++++++++++++++++++++++
        $ary_now = $PromotionMembers->where(array('pmn_id' => $pid))->select(); //当前规则的会员关系
        //print_r($ary_now);exit;
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        foreach ($data['mGroups'] as $k => $v) {
            $data['mGroups'][$k]['checked'] = 0;
            foreach ($ary_now as $now) {
                if ($v['mg_id'] == $now['mg_id']) {
                    $data['mGroups'][$k]['checked'] = 1;
                }
            }
        }

        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        foreach ($data['mLevels'] as $k => $v) {
            $data['mLevels'][$k]['checked'] = 0;
            foreach ($ary_now as $now) {
                if ($v['ml_id'] == $now['ml_id']) {
                    $data['mLevels'][$k]['checked'] = 1;
                }
            }
        }

        $field['rpm'] = array('fx_related_promotion_members.m_id' => 'm_id', 'pmn_id', 'ml_name', 'm_name');
        $where['rpm'] = " fx_related_promotion_members.pmn_id = $pid and fx_related_promotion_members.m_id <> 0 ";
        $join['rpm'] = " left join fx_members on fx_members.m_id = fx_related_promotion_members.m_id left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id";
        $data['mIds'] = $PromotionMembers->field($field['rpm'])->where($where['rpm'])->join($join['rpm'])->select();

        $data['mAll'] = 0;
        foreach ($data['mIds'] as $v) {
            if ($v['m_id'] == -1) {
                $data['mAll'] = 1;
            }
        }
        //促销与商品 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    
        $field['rpg'] = array('fx_related_promotion_goods.g_id' => 'g_id', 'pmn_id', 'g_price_config', 'g_name', 'g_sn');
        $where['rpg'] = array('pmn_id' => $pid);
        $join['rpg'] = " left join fx_view_goods on fx_related_promotion_goods.g_id = fx_view_goods.g_id ";
        $data['rGoods'] = $PromotionGoods->field($field['rpg'])->where($where['rpg'])->join($join['rpg'])->select();
        if(!empty($data['rGoods'])){
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
        }
        
        foreach ($data['rGoods'] as $k => $v) {
            $data['rGoods'][$k]['g_price_config'] = json_decode($v['g_price_config'], true);
        }
        //促销的种类 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['types'] = $Promotion->getTypes();
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行修改操作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-09
     */
    public function doEdit() {
        $data = $this->_post();
        $pid = (int) $this->_post('pmn_id');
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        $PromotionRule = PromotionModel::factory($data['pmn_class'], $data);
        $ary_type = $PromotionRule->getType(); //当前的促销规则说明
        $ary_cfg = $PromotionRule->getCfg();
        $ary_relation = $PromotionRule->getRel();
        //促销主表的操作 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $modify['pmn_activity_name'] = $data['pmn_activity_name'];
        $modify['pmn_name'] = $data['pmn_name'];
        $modify['pmn_order'] = $data['pmn_order'];
        $modify['pmn_enable'] = $data['pmn_enable'];
        $modify['pmn_start_time'] = $data['pmn_start_time'];
        $modify['pmn_end_time'] = $data['pmn_end_time'];
        $modify['pmn_memo'] = $data['pmn_memo'];
        $modify['pmn_class'] = $data['pmn_class'];
        $modify['pmn_type'] = $ary_type['type'];
        $modify['pmn_create_time'] = date('Y-m-d h:i:s');
        $modify['pmn_config'] = json_encode($ary_cfg);
        $mix_result = $Promotion->where(array('pmn_id' => $pid))->data($modify)->save();

        if (false == $mix_result) {
            $this->error('修改促销规则失败');
        }
        //促销与会员、会员组、会员等级关系表的操作 +++++++++++++++++++++++++++++++++
        //0)先删除全部关系再重建
        $PromotionMembers->where(array('pmn_id' => $pid))->delete();
        //1)促销与会员
        $insert_ra_mid = array();
        if ((int) $data['ra_all'] == 1) {
            //允许全部会员
            $insert_ra_mid = array('pmn_id' => $pid, 'm_id' => -1);
            $PromotionMembers->data($insert_ra_mid)->add();
        } else {
            //指定的会员
            foreach ($data['ra_mid'] as $v) {
                $insert_ra_mid[] = array('pmn_id' => $pid, 'm_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mid);
        }
        //2)促销与会员组
        $insert_ra_mgid = array();
        if ((int) $data['ra_all'] == 0) {
            foreach ($data['ra_mg'] as $v) {
                $insert_ra_mgid[] = array('pmn_id' => $pid, 'mg_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mgid);
        }
        //3)促销与会员等级
        $insert_ra_mlid = array();
        if ((int) $data['ra_all'] == 0) {
            foreach ($data['ra_ml'] as $v) {
                $insert_ra_mlid[] = array('pmn_id' => $pid, 'ml_id' => $v);
            }
            $PromotionMembers->addAll($insert_ra_mlid);
        }
        //促销与商品关系表的操作 +++++++++++++++++++++++++++++++++++++++++++++++++
        $PromotionGoods->where(array('pmn_id' => $pid))->delete();
        if($data['goodsSelecter']==0){
            foreach ($ary_relation as $k => $v) {
                $ary_relation[$k]['pmn_id'] = $pid;
            }
            $PromotionGoods->addAll($ary_relation);
        }
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"促销规则修改成功",'促销规则为：'.$data['pmn_name'].',ID:'.$pid));
		D('Gyfx')->deleteAllCache('related_promotion_goods_group','gg_id', array('pmn_id'=>$pid), null,$ary_group=null,$ary_limit=null,600);
		D("Gyfx")->deleteAllCache("related_promotion_goods","*",array('pmn_id'=>$pid),null,null,null,5);
        $this->success('促销规则保存成功', U('Admin/Promotions/pageList'));
    }

    /**
     * 删除促销活动，支持批量删除
     * @todo 此处要作关联判断，如果促销规则已被用户应用，则不可更改和删除
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-09
     */
    public function doDel() {
        $mix_id = $this->_get('pid');
        if (is_array($mix_id)) {
            //批量删除
            $where = array('pmn_id' => array('IN', $mix_id));
        } else {
            //单个删除
            $where = array('pmn_id' => $mix_id);
        }
		$Promotion = D('Promotion');
		$props = $Promotion->where($where)->field('pmn_name')->select();
		$str_prop_name = '';
		foreach($props as $prop){
			$str_prop_name .=$prop['pmn_name'];
		}
		$str_prop_name = trim($str_prop_name,',');
		$tmp_mix_id = implode(',',$mix_id);

        
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');

        $res = $Promotion->where($where)->delete();
        if (false == $res) {
            $this->error('删除失败');
        } else {
            //执行关联删除，但不作成功失败判断，最多在表内留垃圾数据
            $PromotionMembers->where($where)->delete();
            $PromotionGoods->where($where)->delete();
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除促销规则",'删除促销规则为：'.$tmp_mix_id.'-促销名称：'.$str_prop_name));			
            $this->success('删除成功');
        }
    }

    /**
     * 根据用户名，获取用户信息Tr页面用于新增促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getMemberTr() {
        layout(false);
        $name = $this->_post('name');
        $member_id = $this->_post('member_id');
        //判断页面参数
       $str_tmp_name = str_replace(array(',','\n',' '), ';', $name);
       $ary_tmp_name = explode(';', $str_tmp_name);
        if(!empty($ary_tmp_name) && is_array($ary_tmp_name)){ 
            foreach($ary_tmp_name as $k=>$v){
                if(isset($v)){
                    $where = array('m_name' => $v);
                    $search_field=array('m_name', 'm_id', 'ml_name');
                    $member_info = D('Members')->getByNameLevel($where,$search_field);  
                    $m_id=$member_info['m_id'];
                    if(!empty($m_id)){
                        if (!in_array($m_id,$member_id)){
                            $ary_members[$m_id] =$member_info;
                        }
                    }else{
                        $this->ajaxReturn(false);
                        exit;
                    }
                }
            }
        }
        if (empty($ary_members)) {
            $this->ajaxReturn(false);
            exit;
        } else {
            $this->assign('mIds',$ary_members);
            $this->display();
        }
    }
    /**
     * 数组去重复
     * 
     */
     function assoc_unique(&$arr, $key) {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }
    /**
     * 根据商品ID，获取商品信息Tr页面用于新增促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     */
    public function getGoodsTr() {
        layout(false);
        $ary_get = $this->_get();
        //如果是赠品
        $gift_g_id = $this->_post('gs_gift_gid');
        $g_id = $this->_post('gs_gid');
		if(empty($g_id)){
		$g_id = $gift_g_id;
		}
        if(!empty($g_id) && is_array($g_id)){
            foreach($g_id as $k=>$v){
                $ary_goods_page = explode(',',$v);
                if(empty($ary_goods_page)){
                   $g_temp_id[$k] =$v; 
                }else {
                    $g_temp_id[$k] = $ary_goods_page[0];
                }
            }
        }
        
        $where = array('fx_goods.g_id' => array('IN', $g_temp_id));
        $count = D("Goods")->GetGoodCount($where);
        $Page = new Page($count, count($g_id));
        $data['page'] = $Page->show();
        
        $field=array('fx_goods.g_id','fx_goods.g_sn','fx_goods_info.g_name','fx_goods_info.g_price');
        $group='fx_goods.g_id';
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        $data['rGoods'] = D("Goods")->GetGoodList($where,$field,$group,$limit,true);
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
            $this->assign('goods_page',$ary_goods_page[1]);
            $this->assign($data);
            $this->assign("filter",$ary_get);
            $this->display();
        }
    }
    
     /**
     * 根据商品ID，获取商品信息Tr页面用于新增促销规则
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-11
     */
    public function getGoodsGiftTr() {
        layout(false);
        $ary_get = $this->_get();
        //如果是赠品
        $gift_g_id = $this->_post('gs_gift_gid');
        $g_id = $this->_post('gs_gid');
		if(empty($g_id)){
		$g_id = $gift_g_id;
		}
        if(!empty($g_id) && is_array($g_id)){
            foreach($g_id as $k=>$v){
                $ary_goods_page = explode(',',$v);
                if(empty($ary_goods_page)){
                   $g_temp_id[$k] =$v; 
                }else {
                    $g_temp_id[$k] = $ary_goods_page[0];
                }
            }
        }
        
        $where = array('fx_goods.g_id' => array('IN', $g_temp_id));
        $count = D("Goods")->GetGoodCount($where);
        $Page = new Page($count, count($g_id));
        $data['page'] = $Page->show();
        
        $field=array('fx_goods.g_id','fx_goods.g_sn','fx_goods_info.g_name','fx_goods_info.g_price');
        $group='fx_goods.g_id';
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        $data['rGoods'] = D("Goods")->GetGoodList($where,$field,$group,$limit,true);
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
            $this->assign('goods_page',$ary_goods_page[1]);
            $this->assign($data);
            $this->assign("filter",$ary_get);
            $this->display();
        }
    }
    

    /**
     * 根据不同的促销种类，载入不同的规则录入页面
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-08
     */
    public function getPromotionRuler() {
        $code = $this->_post('code');
        //如果存在pid，则取出pid相对应的数据，用于修改 ++++++++++++++++++++++++++++
        $pid = $this->_post('pid');
        if (!empty($pid)) {
            $Promotion = D('Promotion');
            $res = $Promotion->field('pmn_config')->where(array('pmn_id' => $pid))->find();
            $data['config'] = json_decode($res['pmn_config']);
            //促销规则与商品的关系
            $PromotionGoods = D('RelatedPromotionGoods');
            $field['rpg'] = array('fx_related_promotion_goods.g_id' => 'g_id', 'pmn_id', 'g_price_config', 'g_name', 'g_price', 'g_sn','pdt_sn','fx_view_products.pdt_id' => 'pdt_id', 'pdt_sale_price');
            $where['rpg'] = array('pmn_id' => $pid);
            $join['rpg'] = " right join fx_view_products on fx_related_promotion_goods.pdt_id = fx_view_products.pdt_id ";
            $ary_goods= $PromotionGoods->field($field['rpg'])->group('fx_view_products.pdt_id')->where($where['rpg'])->join($join['rpg'])->select();
            //echo $PromotionGoods->getLastSql();exit;
            $data['rGoods'] = array();
            $ary_goods_temp = array();
            $ary_products_temp =array();
            $goodsSpec = D('GoodsSpec');
            
            if(!empty($ary_goods)){
                foreach($ary_goods as $k=>$v){
                     //获取其他属性
                    $ary_goods[$k]['specName'] = $goodsSpec->getProductsSpec($v['pdt_id']);
                    $ary_goods[$k]['g_price_config'] = json_decode($v['g_price_config'], true);
                    if(!array_key_exists($v['g_id'],$ary_goods_temp)){
                        $ary_goods_temp[$v['g_id']]= $ary_goods[$k]; 
                    }
                    $ary_goods_temp[$v['g_id']]['products'][$k]= $ary_goods[$k]; 
                    
                }
                $data['rGoods'] = $ary_goods_temp;
            }
            $this->assign($data);
           
        }else{
            $json_string=json_encode(array("cfg_goods_area" => -1));
            $data['config'] = json_decode($json_string);
            $this->assign($data);
        }
        //有些促销规则需要额外数据支持 +++++++++++++++++++++++++++++++++++++++++++
        //例如与商品相关的需要选择商品
        
        $PromotionRule = PromotionModel::factory($code);
        $this->assign('goods_page',$code);
        $this->assign($PromotionRule->assignHtml());
        $this->display($code, 'utf-8');
    }
    /**
     * 新增促销
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-21
     */
    public function pageProAdd(){
        $this->getSubNav(5, 0, 30);
        $Promotion = D('Promotion');
        //获取到所有自定义促销的分组
        $promotion_group = $Promotion->Distinct(true)->field('pmn_group')->where(array('pmn_group'=>array('NEQ','0')))->select();
        if(!empty($promotion_group) && is_array($promotion_group)){
            $data_group = array();
            foreach($promotion_group as $key=>$val){
                $data_group[$key] = $val['pmn_group'];
            }
        }
        //获取商品分组
        $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
        //当前可以使用的优先级 ++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['orders'] = $Promotion->getOrders();
        //促销的种类 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['types'] = $Promotion->getTypes();
        //会员组和会员等级 +++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        //默认全部会员
        $data['mAll'] = 1;
		/**
        //开关金币选项
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] != 1){
            unset($data['types']['MJLB']);
        }
		**/
        $this->assign($data);
        $this->assign("allgroup",$data_group);
		//得到商品分类
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌
		$brandHtml = $this->get_brand($brands,$otherbrands);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);
        $this->display();

    }

    /**
     * 新增促销时,赠品页面
     * @author WangHaoYu<wanghaoyu@guanyisoft.com>
     * @date 2013-09-26
     */
    public function getProPremiums(){
       $ary_post = $this->_post();
        if(!empty($ary_post['pmn_id']) && isset($ary_post['pmn_id'])){
            $Promotion = D('Promotion');
            $res = $Promotion->field('pmn_config,pmn_class')->where(array('pmn_id' => $ary_post['pmn_id']))->find();
            $data['config'] = json_decode($res['pmn_config'],true);
            $goodsSpec = D('GoodsSpec');
            foreach($data['config']['cfg_products'] as $k_g_id => $v_g_id){
                $ary_goods[$k_g_id] = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
                                ->join("fx_goods_products on fx_goods_info.g_id = fx_goods_products.g_id")
                                ->field("fx_goods_info.g_name,fx_goods_info.g_price,fx_goods_info.g_id,fx_goods_products.g_sn")
                                ->where(array("fx_goods_info.g_id"=>$k_g_id))
                                ->find();
                if(!empty($v_g_id) && is_array($v_g_id)){
                    foreach($v_g_id as $k_pdt_id => $v_pdt_id){
                        $ary_goods[$k_g_id]['products'][$k_pdt_id] = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                                        ->field("pdt_sn,pdt_sale_price")
                                        ->where(array("pdt_id"=>$k_pdt_id))
                                        ->find();
                        $ary_goods[$k_g_id]['products'][$k_pdt_id]['specName'] = $goodsSpec->getProductsSpec($k_pdt_id,2);
                    }
                    
                }

            }
            $data['rGoods'] = $ary_goods;
            $this->assign($data);
        }
        $this->assign('goods_page',$res['pmn_class']);
        $this->display();
    }
	
	/**
	 * 得到类目
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-11-12
	 */
	public function get_cate($cate_id='',$othercates){
		if(!empty($cate_id)) $cate_ids = explode(',',$cate_id);
		else $cate_ids = array();
		$cate_html = '';
		//获取商品分类并传递到模板
		$cateList = D("GoodsCategory")->getChildLevelCategoryById(0);
		foreach($cateList as $cat){
			$cate_html .='<li class="cat_list_li" id="li_catid_'.$cat['gc_id'].'" is_parent="'.$cat['gc_is_parent'].'" parent_id="'.$cat['gc_parent_id'].'" style="margin-left:'.intval($cat[gc_level]*3).'em;" >';
			$cate_html .='<input type="checkbox"  id="shopCat__'.$cat['gc_id'].'" ';
			//展示已选择的商品类目
			if(!empty($cate_ids)){
				if(in_array($cat['gc_id'],$cate_ids)){
					$cate_html .=' checked="checked" ';
				}
			}
			//已被其他公司选择的商品分类和品牌不允许重复选择使用
			if(!empty($othercates)){
				if(in_array($cat[gc_id],$othercates)){
					$cate_html .=' disabled="true"  ';
				}
			}
			$cate_html .=' value="'.$cat['gc_id'].'" ref="'.$cat['gc_name'].'"  name="shopCat[]" class="cat-checkbox" pid="'.$cat['gc_parent_id'].'" />';
			$cate_html .=' <label for="shopCat__'.$cat['gc_id'].'" style="cursor:pointer;" ';
				
			if(in_array($cat[gc_id],$othercates)){
				$cate_html .=' disabled="true"  title="此分类已被其他子公司使用不能再次使用"';
			}
			$cate_html .='>'.$cat['gc_name'];
			if($cat['gc_is_display'] != '1'){
				$cate_html .='<span style="color:#ff0000;">[前台不显示]</span>';
			}
			$cate_html .='</label></li>';
		}
		return $cate_html;
	}

	/**
	 * 得到品牌
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2015-11-12
	 */
	public function get_brand($brand_id='',$otherbrands){
		if(!empty($brand_id)) $brand_ids = explode(',',$brand_id);
		else $brand_ids = array();
		$brand_html = '';
		$brandList = D("GoodsBrand")->where(array("gb_status"=>1))->field('gb_id,gb_name')->order('gb_order asc')->select();
		foreach($brandList as $brand){
			$brand_html .='<li class="brand_list_li" >';
			$brand_html .='<input type="checkbox"  id="shopBrand__'.$brand['gb_id'].'" ';
			if(!empty($brand_ids)){
				if(in_array($brand[gb_id],$brand_ids)){
					$brand_html .=' checked="checked" ';
				}
			}
			if(!empty($otherbrands)){
				if(in_array($brand[gb_id],$otherbrands)){
					$brand_html .=' disabled="true"  ';
				}
			}
			$brand_html .=' value="'.$brand['gb_id'].'" ref="'.$brand['gb_name'].'"  name="shopBrand[]" class="brand-checkbox"  />';
			$brand_html .=' <label for="shopBrand__'.$brand['gb_id'].'" style="cursor:pointer;" ';
				
			if(in_array($brand[gb_id],$otherbrands)){
				$brand_html .=' disabled="true"  title="此品牌已被其他子公司使用不能再次使用"';
			}
			$brand_html .='>'.$brand['gb_name'];
			$brand_html .='</label></li>';
		}
		return $brand_html;
	}
	
    /**
     * 新增促销时,获取优惠劵页面
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-21
     * @modify by wanghaoyu 2013-09-29 
     */
    public function getPreferential(){
        $ary_post = $this->_post();
        if(!empty($ary_post['pmn_id']) && isset($ary_post['pmn_id'])){
            $Promotion = D('Promotion');
            $arr_config = D('Promotion')->where(array('pmn_id'=>$ary_post['pmn_id']))->find();
            $config = json_decode($arr_config['pmn_config'],true);
            //获取商品分组
            $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
            $this->assign($data);
            $this->assign("config",$config);
        }
        //获取商品分组
        $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
        $this->assign($data);
        $this->display();
    }

    /**
     * 新增促销时,获取商品页面
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-21
     */
    public function getPromotionsGoods(){
        $ary_post = $this->_post();
        
        if(!empty($ary_post['pmn_id']) && isset($ary_post['pmn_id'])){
            $Promotion = D('Promotion');
            $res = $Promotion->field('pmn_config,pmn_class,pmn_group')->where(array('pmn_id' => $ary_post['pmn_id']))->find();
            
            $data['config'] = json_decode($res['pmn_config'],true);
            if(!empty($ary_post['params']) && $ary_post['params'] == '1'){
                //促销商品与商品关系
                if($res['pmn_group'] != '0'){
                    $where = array();
                    $where[C('DB_PREFIX').'related_goods_group.gg_id'] = $res['pmn_group'];
                    $ary_goods = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                                ->join( C('DB_PREFIX')."goods_info ON ".C('DB_PREFIX')."goods_products.`g_id`=".C('DB_PREFIX')."goods_info.`g_id`")
                                ->join( C('DB_PREFIX')."related_goods_group ON ".C('DB_PREFIX')."related_goods_group.`g_id`=".C('DB_PREFIX')."goods_products.`g_id`")
                                ->where($where)
                                ->select();
                    //echo "<pre>";print_r(M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql());exit;
                    $data['rGoods'] = array();
                    $ary_goods_temp = array();
                    $ary_products_temp =array();
                    $goodsSpec = D('GoodsSpec');
                    if(!empty($ary_goods)){
                        foreach($ary_goods as $k=>$v){
                             //获取其他属性
                            $ary_goods[$k]['specName'] = $goodsSpec->getProductsSpec($v['pdt_id']);
                            $ary_goods[$k]['g_price_config'] = json_decode($v['g_price_config'], true);
                            if(!array_key_exists($v['g_id'],$ary_goods_temp)){
                                $ary_goods_temp[$v['g_id']]= $ary_goods[$k]; 
                            }
                            $ary_goods_temp[$v['g_id']]['products'][$k]= $ary_goods[$k]; 

                        }
                        $data['rGoods'] = $ary_goods_temp;
                    }
                }
                $this->assign($data);
            }else{
                //促销规则与商品的关系
                $PromotionGoods = D('RelatedPromotionGoods');
                $field['rpg'] = array('fx_related_promotion_goods.g_id' => 'g_id', 'pmn_id', 'g_price_config', 'g_name', 'g_price', 'g_sn','pdt_sn','fx_view_products.pdt_id' => 'pdt_id', 'pdt_sale_price');
                $where['rpg'] = array('pmn_id' => $ary_post['pmn_id']);
                $join['rpg'] = " right join fx_view_products on fx_related_promotion_goods.pdt_id = fx_view_products.pdt_id ";
                $ary_goods= $PromotionGoods->field($field['rpg'])->group('fx_view_products.pdt_id')->where($where['rpg'])->join($join['rpg'])->select();
                $data['rGoods'] = array();
                $ary_goods_temp = array();
                $ary_products_temp =array();
                $goodsSpec = D('GoodsSpec');

                if(!empty($ary_goods)){
                    foreach($ary_goods as $k=>$v){
                         //获取其他属性
                        $ary_goods[$k]['specName'] = $goodsSpec->getProductsSpec($v['pdt_id']);
                        $ary_goods[$k]['g_price_config'] = json_decode($v['g_price_config'], true);
                        if(!array_key_exists($v['g_id'],$ary_goods_temp)){
                            $ary_goods_temp[$v['g_id']]= $ary_goods[$k]; 
                        }
                        $ary_goods_temp[$v['g_id']]['products'][$k]= $ary_goods[$k]; 

                    }
                    $data['rGoods'] = $ary_goods_temp;
                }
                $this->assign($data);
            }
        }
        $this->assign('goods_page',$res['pmn_class']);
        $this->assign("filter",$ary_post);
//        echo "<pre>";print_r($data);exit;
        $this->display();
    }

    /**
     * 促销列表页
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-21
     * @ modify by wanghaoyu 2013-09-26 
     */
    public function pageProList(){
        $this->getSubNav(5, 0, 40);
        $ary_get = $this->_get();
        $Promotion = D('Promotion');
        $where = array();
        if(!empty($ary_get['val'])){
            $where[$ary_get['field']] = $ary_get['val'];
        }
        if(!empty($ary_get['pmn_start_time']) && empty($ary_get['pmn_end_time'])){
            $where['pmn_start_time'] = array("EGT",$ary_get['pmn_start_time']);
        }else if(!empty($ary_get['pmn_start_time']) && !empty($ary_get['pmn_end_time']) && $ary_get['pmn_start_time'] !=$ary_get['pmn_end_time']){
            if($ary_get['pmn_start_time'] > $ary_get['pmn_end_time']){
                $where['pmn_start_time'] = array("EGT",$ary_get['pmn_end_time']);
                $where['pmn_end_time'] = array("ELT",$ary_get['pmn_start_time']);
            }else{
                $where['pmn_start_time'] = array("EGT",$ary_get['pmn_start_time']);
                $where['pmn_end_time'] = array("ELT",$ary_get['pmn_end_time']);
            }
        }else{
            if(!empty($ary_get['pmn_end_time'])){
                $where['pmn_end_time'] = array("ELT",$ary_get['pmn_end_time']);
            }
        }
        $count = $Promotion->where($where)->count();
        $Page = new Page($count, 15);
        $data['page'] = $Page->show();
        $order = array('pmn_order' => 'desc');
        $data['list'] = $Promotion->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign($data);
        $this->assign("filter",$ary_get);
        $this->display();
    }

    /**
     * 促销新增
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-21
     */
    public function doProAdd(){
        $data = $this->_post();
		if($data['pmn_class'] == 'PYIKOUJIA'){
			unset($data['gg_name']);
			unset($data['shopBrand']);
			unset($data['shopCat']);
			unset($data['cat_selValue']);
			unset($data['brand_selValue']);
		}
        if(empty($data['ra_gid']) && empty($data['gg_name']) && empty($data['shopCat']) && empty($data['shopBrand'])){
            $this->error('促销商品、商品分组、商品分类、商品品牌至少选择一个');
        }
        //时间判断
        if(!empty($data['pmn_start_time']) && !empty($data['pmn_end_time'])){
            if($data['pmn_start_time'] > $data['pmn_end_time']){
                $this->error('开始时间不能大于结束时间！');
            }
        }
        //选择赠品促销是否选择赠品
        if($data['pmn_class'] == 'MZENPIN' && empty($data['ra_gift_gid'])){
        	$this->error('请选择赠品');
        }
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        $PromotionRule = PromotionModel::factory($data['pmn_class'], $data);
        $ary_type = $PromotionRule->getType(); //当前的促销规则说明
        $ary_cfg = $PromotionRule->getCfg();
        $ary_relation = $PromotionRule->getRel();
        //促销主表的操作 
        $insert['pmn_activity_name'] = $data['pmn_name'];
        $insert['pmn_name'] = $data['pmn_name'];
        $insert['pmn_order'] = $data['pmn_order'];
        $insert['pmn_enable'] = $data['pmn_enable'];
        $insert['pmn_start_time'] = $data['pmn_start_time'];
        $insert['pmn_end_time'] = $data['pmn_end_time'];
        $insert['pmn_memo'] = $data['pmn_memo'];
        $insert['pmn_class'] = $data['pmn_class'];
        $insert['pmn_type'] = $ary_type['type'];
        $insert['pmn_create_time'] = date('Y-m-d h:i:s');
        $ary_merge = array_merge($ary_cfg,$ary_relation);
        if($data['pmn_class'] != 'PYIKOUJIA'){
            $insert['pmn_config'] = json_encode($ary_merge);
        }else{
            $insert['pmn_config'] = json_encode($ary_cfg);
        }
		if($data['cat_selValue']){
			$insert['pmn_category'] = $data['cat_selValue'];
		}
		if($data['brand_selValue']){
			$insert['pmn_brand'] = $data['brand_selValue'];
		}
        $Promotion->startTrans();
        $int_pmn_id = $Promotion->data($insert)->add();
        if (false == $int_pmn_id) {
            $Promotion->rollback();
            $this->error('促销规则添加失败');
        }
        //促销与会员、会员组、会员等级关系表的操作 +++++++++++++++++++++++++++++++++
        //1)促销与会员
        $insert_ra_mid = array();
        if ((int) $data['ra_all'] == 1) {
            //允许全部会员
            $insert_ra_mid = array('pmn_id' => $int_pmn_id, 'm_id' => -1);
            $ary_ra_res = $PromotionMembers->data($insert_ra_mid)->add();
            if(FALSE == $ary_ra_res){
                $Promotion->rollback();
                $this->error("更新会员信息失败");
            }
        } else {
            $pMembers = true;
            //指定的会员
            foreach ($data['ra_mid'] as $v) {
                $insert_ra_mid = array('pmn_id' => $int_pmn_id, 'm_id' => $v);
                $ary_ra_res = $PromotionMembers->add($insert_ra_mid);
                if(FALSE == $ary_ra_res){
                    $pMembers = FALSE;
                    break;
                }
            }
            if(FALSE == $pMembers){
                $Promotion->rollback();
                $this->error("指定促销会员失败");
            }

        }
        //2)促销与会员组
        $insert_ra_mgid = array();
        if ((int) $data['ra_all'] == 0) {
            $pMemberGroup = true;
            foreach ($data['ra_mg'] as $v) {
                $insert_ra_mgid = array('pmn_id' => $int_pmn_id, 'mg_id' => $v);
                $ary_ra_group = $PromotionMembers->add($insert_ra_mgid);
                if(FALSE == $ary_ra_group){
                    $pMemberGroup = FALSE;
                    break;
                }
            }
            
            if(FALSE == $pMemberGroup){
                $Promotion->rollback();
                $this->error("指定促销会员组失败");
            }
        }
        //3)促销与会员等级
        $insert_ra_mlid = array();
        if ((int) $data['ra_all'] == 0) {
            $pMemberLevel = true;
            foreach ($data['ra_ml'] as $v) {
                $insert_ra_mlid = array('pmn_id' => $int_pmn_id, 'ml_id' => $v);
                $ary_ra_level = $PromotionMembers->add($insert_ra_mlid);
                if(FALSE == $ary_ra_level){
                    $pMemberLevel = FALSE;
                    break;
                }
            }
            if(FALSE == $pMemberLevel){
                $Promotion->rollback();
                $this->error("指定促销会员等级失败");
            }
        }
        if(!empty($data['pmn_class']) && $data['pmn_class'] == 'PYIKOUJIA'){
            foreach ($ary_relation as $k => $v) {
				$tmp_price_config = json_decode($v['g_price_config'],true);
				if(isset($tmp_price_config['cfg_products']) && $tmp_price_config['cfg_products']==''){
					if($data['cfg_discounts_all']!=''){
						$tmp_price_config['cfg_products']=$data['cfg_discounts_all'];
						$ary_relation[$k]['g_price_config']=json_encode($tmp_price_config);
					}else{
						$Promotion->rollback();
						$this->error("促销商品一口价没有设定！");
					}
				}
                $ary_relation[$k]['pmn_id'] = $int_pmn_id;
            }
            $arr_relation_res = $PromotionGoods->addAll($ary_relation);
            if(FALSE == $arr_relation_res){
                $Promotion->rollback();
                $this->error("更新促销商品失败");
            }else{
                $Promotion->commit();
                $this->success('促销规则保存成功', U('Admin/Promotions/pageProList'));
            } 
        }else{
            //判断该促销有没有指定单个商品
            if(!empty($data['ra_gid']) && is_array($data['ra_gid']) ){
                $array_check_result = D("GoodsGroup")->where(array("gg_name"=>trim($data['pmn_name']).date('YmdHis')))->find();
                if(is_array($array_check_result) && !empty($array_check_result) && isset($array_check_result["gg_id"])){
                    $Promotion->rollback();
                    $this->error("生成商品分组失败，已经存在相同名称的商品分组，为便于管理，请勿重复添加。");
                }
                //数据入库
                $array_insert_data = array();
                $array_insert_data["gg_name"] = trim($data['pmn_name']);
                $array_insert_data["gg_desc"] = (isset($data['pmn_name']) && trim($data['pmn_name']) != "")?trim($data['pmn_name']):"";
                $array_insert_data["gg_status"] = 1;
                $array_insert_data["gg_order"] = 0;
                $array_insert_data["gg_create_time"] = date("Y-m-d H:i:s");
                $array_insert_data["gg_update_time"] = date("Y-m-d H:i:s");
                $ary_group_res = D("GoodsGroup")->add($array_insert_data);
                if(FALSE === $ary_group_res){
                    $Promotion->rollback();
                    $this->error("生成商品分组失败，请重试...");
                }
                $GroupAdd = TRUE;
                foreach($data['ra_gid'] as $val){
                    $arr_add_group_res = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('gg_id'=>$ary_group_res,'g_id'=>$val));
                    if(FALSE == $arr_add_group_res){
                        $GroupAdd = FALSE;
                        break;
                    }
                }
                if(FALSE !== $GroupAdd){
                    $promotion_ggid_res = $Promotion->where(array('pmn_id'=>$int_pmn_id))->data(array('pmn_group'=>$ary_group_res))->save();
                    if(FALSE === $promotion_ggid_res){
                        $Promotion->rollback();
                        $this->error("更新促销信息，请重试");
                    }
                    //促销信息关联商品分组
                    $arr_relation_group_res = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$int_pmn_id,'gg_id'=>$ary_group_res));
                    if(FALSE == $arr_relation_group_res){
                        $Promotion->rollback();
                        $this->error("促销信息关联商品分组失败，请重试");
                    }
                }else{
                    $Promotion->rollback();
                    $this->error("商品归组失败，请重试");
                }
            }
            //促销关联商品分组表
            if(!empty($data['gg_name']) && is_array($data['gg_name'])){
                $pGoodsGroup = true;
                foreach($data['gg_name'] as $val){
                    $arr_relation_group_res = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$int_pmn_id,'gg_id'=>$val));
                    if(FALSE == $arr_relation_group_res){
                        $pGoodsGroup = FALSE;
                        break;
                    }
                }
                if(FALSE == $pGoodsGroup){
                    $Promotion->rollback();
                    $this->error("更新促销商品分组失败");
                }
            }
			//促销关联商品分类表
            if(!empty($data['shopCat']) && is_array($data['shopCat'])){
                $pGoodsGroup = true;
                foreach($data['shopCat'] as $val){
                    $arr_relation_cate_res = M('related_promotion_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$int_pmn_id,'gc_id'=>$val));
                    if(FALSE == $arr_relation_cate_res){
                        $pGoodsGroup = FALSE;
                        break;
                    }
                }
                if(FALSE == $pGoodsGroup){
                    $Promotion->rollback();
                    $this->error("更新促销商品分类失败");
                }
            }	
			//促销关联商品品牌表
            if(!empty($data['shopBrand']) && is_array($data['shopBrand'])){
                $pGoodsGroup = true;
                foreach($data['shopBrand'] as $val){
                    $arr_relation_group_res = M('related_promotion_goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$int_pmn_id,'brand_id'=>$val));
                    if(FALSE == $arr_relation_group_res){
                        $pGoodsGroup = FALSE;
                        break;
                    }
                }
                if(FALSE == $pGoodsGroup){
                    $Promotion->rollback();
                    $this->error("更新促销商品品牌失败");
                }
            }				
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"促销规则新增",'促销规则为：'.$data['pmn_name']));
            $Promotion->commit();
            $this->success('促销规则保存成功', U('Admin/Promotions/pageProList'));
        }
            
    }

    /**
     * 促销编辑
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-23
     */
    public function pageProEdit(){
        $this->getSubNav(5, 0, 40,'编辑促销活动');
        $pid = (int) $this->_get('pid');
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        
        //获取到所有自定义促销的分组
        $promotion_group = $Promotion->Distinct(true)->field('pmn_group')->where(array('pmn_group'=>array('NEQ','0')))->select();
        if(!empty($promotion_group) && is_array($promotion_group)){
            $data_group = array();
            foreach($promotion_group as $key=>$val){
                $data_group[$key] = $val['pmn_group'];
            }
        }
        //当前的促销信息 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['info'] = $Promotion->where(array('pmn_id' => $pid))->find();
        //当前可以使用的优先级 ++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['orders'] = $Promotion->getOrders();
        //会员组和会员等级 +++++++++++++++++++++++++++++++++++++++++++++++++++++
        $ary_now = $PromotionMembers->where(array('pmn_id' => $pid))->select(); //当前规则的会员关系
        $data['mGroups'] = M('membersGroup',C('DB_PREFIX'),'DB_CUSTOM')->where(array('mg_status' => '1'))->select();
        foreach ($data['mGroups'] as $k => $v) {
            $data['mGroups'][$k]['checked'] = 0;
            foreach ($ary_now as $now) {
                if ($v['mg_id'] == $now['mg_id']) {
                    $data['mGroups'][$k]['checked'] = 1;
                }
            }
        }

        $data['mLevels'] = M('membersLevel',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_status' => '1'))->select();
        foreach ($data['mLevels'] as $k => $v) {
            $data['mLevels'][$k]['checked'] = 0;
            foreach ($ary_now as $now) {
                if ($v['ml_id'] == $now['ml_id']) {
                    $data['mLevels'][$k]['checked'] = 1;
                }
            }
        }

        $field['rpm'] = array('fx_related_promotion_members.m_id' => 'm_id', 'pmn_id', 'ml_name', 'm_name');
        $where['rpm'] = " fx_related_promotion_members.pmn_id = $pid and fx_related_promotion_members.m_id <> 0 ";
        $join['rpm'] = " left join fx_members on fx_members.m_id = fx_related_promotion_members.m_id left join fx_members_level on fx_members.ml_id = fx_members_level.ml_id";
        $data['mIds'] = $PromotionMembers->field($field['rpm'])->where($where['rpm'])->join($join['rpm'])->select();

        $data['mAll'] = 0;
        foreach ($data['mIds'] as $v) {
            if ($v['m_id'] == -1) {
                $data['mAll'] = 1;
            }
        }
        //获取商品分组
        $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
        //促销的种类 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['types'] = $Promotion->getTypes();
        //获得促销的商品
        $ary_goods = $PromotionGoods->Getgoods($pid);
        foreach($ary_goods as $gdv){
            $data['info']['g_id'] = $gdv['g_id'];
        }
        //获取促销关联的商品分组+++++++++++++++++==
        $ary_pro_ggroup = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$pid))->select();
        $ggroup = array();
        if(!empty($ary_pro_ggroup) && is_array($ary_pro_ggroup)){
            foreach ($ary_pro_ggroup as $keyg => $valg) {
                $ggroup[] = $valg['gg_id'];
            }
        }
		//获取促销关联的商品分类
        $ary_pro_gcategory = M('related_promotion_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$pid))->select();	
		$cates = '';
		foreach($ary_pro_gcategory as $tmp_cate){
			$cates.=$tmp_cate['gc_id'].',';
		}
		$cates = trim($cates,',');
		$this->assign("gcategory",$ary_pro_gcategory);		
		//获取促销关联的商品品牌
        $ary_pro_gbrand = M('related_promotion_goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id'=>$pid))->select();	
		$brands = '';
		foreach($ary_pro_gbrand as $tmp_brand){
			$brands.=$tmp_brand['brand_id'].',';
		}
		$brands = trim($brands,',');
		$this->assign("gbrand",$ary_pro_gbrand);	
		//dump($data);die();
        $this->assign($data);
        $pmn_config = json_decode($data['info']['pmn_config']);
        $this->assign("config",$pmn_config);
        $this->assign("ggroup",$ggroup);
        $this->assign("allgroup",$data_group);
		//得到商品分类
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌
		$brandHtml = $this->get_brand($brands,$otherbrands);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);		
        $this->display();     
    }

    /**
     * 删除促销活动，支持批量删除
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-27
     */
    public function doProDel(){
        $pid = $this->_post('pid');
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        $PromotionGoodsGroup = D('RelatedPromotionGoodsGroup');
		$PromotionGoodsCategory = D('RelatedPromotionGoodsCategory');
		$PromotionGoodsBrand = D('RelatedPromotionGoodsBrand');
        if (is_array($pid)) {
            //批量删除
            $where = array('pmn_id' => array('IN', $pid));
        } else {
            //单个删除
            $where = array('pmn_id' => $pid);
        }
		$props = $Promotion->where($where)->field('pmn_name')->select();
		$str_prop_name = '';
		foreach($props as $prop){
			$str_prop_name .=$prop['pmn_name'];
		}
		$str_prop_name = trim($str_prop_name,',');
		
		//$tmp_pid = implode(',',$pid);
        $Promotion->startTrans();
        $res = $Promotion->where($where)->delete();
        if (false == $res) {
            $Promotion->rollback();
            $this->error('删除失败');
        } else {
            //执行关联删除
            $ary_result = $PromotionMembers->where($where)->delete();
			$ary_cresult = $PromotionGoodsCategory->where($where)->delete();
			$ary_bresult = $PromotionGoodsBrand->where($where)->delete();
            if(FALSE !== $ary_result && $ary_cresult !==false && $ary_bresult !==false){
                $arr_res = $PromotionGoods->where($where)->delete();
                if(FALSE !== $arr_res){
                    
                    $arr_result = $PromotionGoodsGroup->where($where)->delete();
                    if(FALSE !== $arr_result){
                        $Promotion->commit();
						$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"促销规则删除",'促销规则为：'.$pid.'-'.$str_prop_name));
                        $this->success('删除成功');
                    }else{
                        $Promotion->rollback();
                        $this->error('删除失败');
                    }
                    
                }else{
                    $Promotion->rollback();
                    $this->error('删除失败');
                }
            }else{
                $Promotion->rollback();
                $this->error('删除失败');
            }
            
        }
    }

    /**
     * 处理编辑促销
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-28
     */
    public function doProEdit(){
        $data = $this->_post();
        //验证数据有效性
        if(!empty($data['pmn_start_time']) && !empty($data['pmn_end_time'])){
            if($data['pmn_start_time'] >= $data['pmn_end_time']){
                $this->error('结束时间不能大于开始时间！');
            }
        }
		if($data['pmn_class'] == 'PYIKOUJIA'){
			unset($data['gg_name']);
			unset($data['shopBrand']);
			unset($data['shopCat']);
			unset($data['cat_selValue']);
			unset($data['brand_selValue']);
		}
        if(empty($data['group']) && empty($data['pmn_name']) && empty($data['shopCat']) && empty($data['shopBrand'])){
            $this->error('促销商品、商品分组、商品分类、商品品牌至少选择一个');
        }		
        //选择赠品促销是否选择赠品
        if($data['pmn_class'] == 'MZENPIN' && empty($data['ra_gift_gid'])){
        	$this->error('请选择赠品');
        }
        //当满赠品时
        if($data['pmn_class'] == 'MZENPIN'){
        	$cfg_products = array();
        	//赠品ID
        	$ra_gift_gids = $data['ra_gift_gid'];
        	foreach($data['cfg_products'] as $key=>$cfg_product){
        		foreach($ra_gift_gids as $gift_g_id){
        			if($key == $gift_g_id){
        				$cfg_products[$key] = $data['cfg_products'][$key];
        			}
        		}
        	}
        	$data['cfg_products'] = $cfg_products;	
        }
        //echo '<pre>'; print_r($data);die();       
        $pid = (int) $this->_post('pmn_id');
        $Promotion = D('Promotion');
        $PromotionMembers = D('RelatedPromotionMembers');
        $PromotionGoods = D('RelatedPromotionGoods');
        
        $PromotionRule = PromotionModel::factory($data['pmn_class'], $data);
        $ary_type = $PromotionRule->getType(); //当前的促销规则说明
        $ary_cfg = $PromotionRule->getCfg();
        $ary_relation = $PromotionRule->getRel();
        $modify['pmn_activity_name'] = $data['pmn_activity_name'];
        $modify['pmn_name'] = $data['pmn_name'];
        $modify['pmn_order'] = $data['pmn_order'];
        $modify['pmn_enable'] = $data['pmn_enable'];
        $modify['pmn_start_time'] = $data['pmn_start_time'];
        $modify['pmn_end_time'] = $data['pmn_end_time'];
        $modify['pmn_memo'] = $data['pmn_memo'];
        $modify['pmn_class'] = $data['pmn_class'];
        $modify['pmn_type'] = $ary_type['type'];
        $modify['pmn_create_time'] = date('Y-m-d h:i:s');		
        $ary_merge = array_merge($ary_cfg,$ary_relation);
        if($data['pmn_class'] != 'PYIKOUJIA'){
            $modify['pmn_config'] = json_encode($ary_merge);
        }else{
            $modify['pmn_config'] = json_encode($ary_cfg);
        }
        $modify['pmn_group'] = 0;
//		if($data['cat_selValue']){
			$modify['pmn_category'] = $data['cat_selValue'];
//		}
//		if($data['brand_selValue']){
			$modify['pmn_brand'] = $data['brand_selValue'];
//		}
        $arr_promotion = $Promotion->where(array('pmn_id'=>$pid))->find();
        $Promotion->startTrans();
        $mix_result = $Promotion->where(array('pmn_id' => $pid))->data($modify)->save();
        if (false === $mix_result) {
            $Promotion->rollback();
            $this->error('修改促销规则失败');
        }
        //删除原有自定义分组数据
        if($arr_promotion['pmn_group']) {
			$r_count = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_id'=>$arr_promotion['pmn_group']))->count();
			if($r_count>0){
	            if(!M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_id'=>$arr_promotion['pmn_group']))->delete()) {
                $Promotion->rollback();
                $this->error('删除历史商品数据失败！');
				}		
			}
			$p_count = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_id'=>$arr_promotion['pmn_group']))->count();
            if($p_count>0){
				if(!M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_id'=>$arr_promotion['pmn_group']))->delete()) {
                $Promotion->rollback();
                $this->error('删除历史商品数据失败！');
				}		
			}
        }
        //促销与会员、会员组、会员等级关系表的操作 +++++++++++++++++++++++++++++++++
        //0)先删除全部关系再重建
        $ary_pro_member_res = $PromotionMembers->where(array('pmn_id' => $pid))->delete();
        if (false === $ary_pro_member_res) {
            $Promotion->rollback();
            $this->error('重建会员关系失败');
        }
        //1)促销与会员
        $insert_ra_mid = array();
        if ((int) $data['ra_all'] == 1) {
            //允许全部会员
            $insert_ra_mid = array('pmn_id' => $pid, 'm_id' => -1);
            $ary_ra_res = $PromotionMembers->data($insert_ra_mid)->add();
            if(FALSE === $ary_ra_res){
                $Promotion->rollback();
                $this->error("更新会员信息失败");
            }
        } else {
            $pMembers = true;
            //指定的会员
            foreach ($data['ra_mid'] as $v) {
                $insert_ra_mid = array('pmn_id' => $pid, 'm_id' => $v);
                $ary_ra_res = $PromotionMembers->add($insert_ra_mid);

                if(FALSE == $ary_ra_res){
                    $pMembers = FALSE;
                    break;
                }
            }
            if(FALSE === $pMembers){
                $Promotion->rollback();
                $this->error("指定促销会员失败");
            }
        }
        //2)促销与会员组
        $insert_ra_mgid = array();
        if ((int) $data['ra_all'] == 0) {
            $pMemberGroup = true;
            foreach ($data['ra_mg'] as $v) {
                $insert_ra_mgid = array('pmn_id' => $pid, 'mg_id' => $v);
                $ary_ra_group = $PromotionMembers->add($insert_ra_mgid);
                if(FALSE === $ary_ra_group){
                    $pMemberGroup = FALSE;
                    break;
                }
            }
            
            if(FALSE == $pMemberGroup){
                $Promotion->rollback();
                $this->error("指定促销会员组失败");
            }
        }
        //3)促销与会员等级
        $insert_ra_mlid = array();
        if ((int) $data['ra_all'] == 0) {
            $pMemberLevel = true;
            foreach ($data['ra_ml'] as $v) {
                $insert_ra_mlid = array('pmn_id' => $pid, 'ml_id' => $v);
                $ary_ra_level = $PromotionMembers->add($insert_ra_mlid);
                if(FALSE === $ary_ra_level){
                    $pMemberLevel = FALSE;
                    break;
                }
            }
            if(FALSE === $pMemberLevel){
                $Promotion->rollback();
                $this->error("指定促销会员等级失败");
            }
        }
        //促销与商品关系表的操作 +++++++++++++++++++++++++++++++++++++++++++++++++
        $PromotionGoods->where(array('pmn_id' => $pid))->delete();
        if(!empty($data['pmn_class']) && $data['pmn_class'] == 'PYIKOUJIA'){
            
            //促销与商品关系表的操作 +++++++++++++++++++++++++++++++++++++++++++++++++
            foreach ($ary_relation as $k => $v) {
				$tmp_price_config = json_decode($v['g_price_config'],true);
				if(isset($tmp_price_config['cfg_products']) && $tmp_price_config['cfg_products']==''){
					if($data['cfg_discounts_all']!=''){
						$tmp_price_config['cfg_products']=$data['cfg_discounts_all'];
						$ary_relation[$k]['g_price_config']=json_encode($tmp_price_config);
					}else{
						$Promotion->rollback();
						$this->error("促销商品一口价没有设定！");
					}
				}
                $ary_relation[$k]['pmn_id'] = $pid;
            }
            if(!empty($ary_relation) && is_array($ary_relation)){
                $arr_relation_res = $PromotionGoods->addAll($ary_relation);
                if(FALSE == $arr_relation_res){
                    $Promotion->rollback();
                    $this->error("更新促销商品失败");
                }else{
                    $Promotion->commit();
                    $this->success('促销规则保存成功', U('Admin/Promotions/pageProList'));
                }    
            }else{
                $Promotion->commit();
                $this->success('促销规则保存成功', U('Admin/Promotions/pageProList'));
            }
            
        }else{
            $pro_delete_ggroup = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id' => $pid))->delete();
            
            if(FALSE === $pro_delete_ggroup){
                $Promotion->rollback();
                $this->error("更新商品分组失败");
            }else{
                //判断该促销有没有指定单个商品
                if(!empty($data['ra_gid']) && is_array($data['ra_gid'])){
                    //数据入库
                    $array_insert_data = array();
                    $array_insert_data["gg_name"] = trim($data['pmn_name']);
                    $array_insert_data["gg_desc"] = (isset($data['pmn_name']) && trim($data['pmn_name']) != "")?trim($data['pmn_name']):"";
                    $array_insert_data["gg_status"] = 1;
                    $array_insert_data["gg_order"] = 0;
                    $array_insert_data["gg_update_time"] = date("Y-m-d H:i:s");
                    //重新生成促销自定义分组
                    $ary_group_res = D("GoodsGroup")->data($array_insert_data)->add();
                    if(FALSE === $ary_group_res){
                        $Promotion->rollback();
                        $this->error("生成商品分组失败，请重试...");
                    }
                    $GroupAdd = TRUE;
                    $data['ra_gid'] = array_unique($data['ra_gid']);
                    foreach($data['ra_gid'] as $val){
                        //商品和分组关联
                        $arr_add_group_res = M('related_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('gg_id'=>$ary_group_res,'g_id'=>$val));
                        if(FALSE == $arr_add_group_res){
                            $GroupAdd = FALSE;
                            break;
                        }
                    }
                    if(FALSE === $GroupAdd){
                        $Promotion->rollback();
                        $this->error("商品归组失败，请重试");
                    }
                    $arr_relation_group_res = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$pid,'gg_id'=>$ary_group_res));
                    if(!$arr_relation_group_res){
                        $Promotion->rollback();
                        $this->error("商品分组和促销关联失败，请重试");
                    }
                }
                //将自定义分组信息写入促销
                $res_pmn_group = $Promotion->where(array('pmn_id'=>$arr_promotion['pmn_id']))->data(array('pmn_group'=>$ary_group_res))->save();
                if(false === $res_pmn_group) {
                    $Promotion->rollback();
                    $this->error("修改促销信息失败！");
                }
                //促销关联商品分组表
                if(!empty($data['group']) && is_array($data['group'])){
                    $pGoodsGroup = true;
                    foreach($data['group'] as $val){
                        $arr_relation_group_res = M('related_promotion_goods_group',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$pid,'gg_id'=>$val));
                        if(FALSE == $arr_relation_group_res){
                            $pGoodsGroup = FALSE;
                            break;
                        }
                    }
                    if(FALSE === $pGoodsGroup){
                        $Promotion->rollback();
                        $this->error("更新促销商品分组失败");
                    } 
                }
				
				//促销关联商品分类表
				$pro_delete_gcategory = M('related_promotion_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id' => $pid))->delete();           
				if(FALSE === $pro_delete_gcategory){
					$Promotion->rollback();
					$this->error("更新商品分类失败");
				}				
				if(!empty($data['shopCat']) && is_array($data['shopCat'])){
					$pGoodsGroup = true;
					foreach($data['shopCat'] as $val){
						$arr_relation_cate_res = M('related_promotion_goods_category',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$pid,'gc_id'=>$val));
						if(FALSE == $arr_relation_cate_res){
							$pGoodsGroup = FALSE;
							break;
						}
					}
					if(FALSE == $pGoodsGroup){
						$Promotion->rollback();
						$this->error("更新促销商品分类失败");
					}											
				}	
				//促销关联商品品牌表
				$pro_delete_gbrand = M('related_promotion_goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pmn_id' => $pid))->delete();           
				if(FALSE === $pro_delete_gbrand){
					$Promotion->rollback();
					$this->error("更新商品品牌失败");
				}					
				if(!empty($data['shopBrand']) && is_array($data['shopBrand'])){
					$pGoodsGroup = true;
					foreach($data['shopBrand'] as $val){
						$arr_relation_group_res = M('related_promotion_goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->add(array('pmn_id'=>$pid,'brand_id'=>$val));
						if(FALSE == $arr_relation_group_res){
							$pGoodsGroup = FALSE;
							break;
						}
					}
					if(FALSE == $pGoodsGroup){
						$Promotion->rollback();
						$this->error("更新促销商品品牌失败");
					}
				}				
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"更新促销规则",'促销规则为：'.$data['pmn_name']));
                $Promotion->commit();
                $this->success('促销规则保存成功', U('Admin/Promotions/pageProList'));
            }
        }
    }

    /**
     * AJAX 控制促销的停用/启用
     * @author WangHaoYu
     * @version 7.4
     * @date 2013-10-12
     */
    public function ajaxDoProEdit() {
        $ary_res = array(
            'status'=>'0',
            'msg'=>'',
            'data'=>array()
        );
        $data = $this->_post();
        $pmn_id = (int) $this->_post('pmn_id');
        $promotions = M('Promotion',C('DB_PREFIX'),'DB_CUSTOM');
        $modify = array();
        $modify['pmn_enable'] = $data['pmn_enable'];
        $promotions->startTrans();
        $boo_res = $promotions->where(array('pmn_id'=>$pmn_id))->save($modify);
        if(false === $boo_res) {
            $promotions->rollback();
            $ary_res['msg'] = '启用失败';
            exit;
        }
        $promotions->commit();
        $ary_res['msg'] = '启用成功';
        $ary_res['status'] = '1';

        echo json_encode($ary_res);
        exit;

    }

    /**
     * 新增促销时,获取金币
     * @author Hcaijin
     * @date 2014-08-12
     */
    public function getPromotionsJlb(){
        $ary_post = $this->_post();
        if(!empty($ary_post['pmn_id']) && isset($ary_post['pmn_id'])){
            $Promotion = D('Promotion');
            $arr_config = D('Promotion')->where(array('pmn_id'=>$ary_post['pmn_id']))->find();
            $config = json_decode($arr_config['pmn_config'],true);
            //获取商品分组
            $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
            $this->assign($data);
            $this->assign("config",$config);
        }
        //获取商品分组
        $data['gGroup'] = M('goods_group',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gg_status'=>'1'))->select();
        $this->assign($data);
        $this->display();
    }
    
}

