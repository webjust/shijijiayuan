<?php

/**
 * 后台商品分销商库存分配控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-09-10
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
Class InventoryAction extends AdminAction{

	public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU2_0'));
    }

    /**
     * 库存分配默认控制器
     * 跳转到pageList控制器
     */
    public function index(){
    	$this->redirect(U('Admin/Inventory/pageList'));
    }

    /**
     * 列表页
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-10
     */
    public function pageList(){
    	// 获取数据
    	$ary_request = $this->_request();
    	// 分页处理
		$currentPage = (int)$ary_request['p'];
		if(0 != $currentPage){
			session('page',$currentPage);
		}
		$int_page_size = 10;
		$ary_where = array('inventory_lock.g_id'=>$ary_request['id']);
		// 搜索条件处理
		$order_by = '';
		$data = $this->pageInventory($ary_where, $order_by, $int_page_size);	// 获取数据
		// 产品规格信息
		$product = $this->getProductsByGid($ary_request['id']);
        // 计算库存
        if(!empty($product)){
            $ary_inventory_data = $this->getPdtStockByPdtId($product[0]['pdt_id']);
            $product[0]['pdt_stock'] = $ary_inventory_data['pdt_stock'];
        }
    	$this->assign("product",$product);
    	$this->assign("filter", $ary_request);	// 过滤条件
    	$this->assign("page", $data['page']);	// 分页
        $this->assign("data", $data['list']);	// 分页数据
    	//商品列表页页签处理
        $admin_left_menu = 30;
        $this->getSubNav(3, 0, $admin_left_menu);
    	$this->display();
    }

    /**
     * 添加分配库存信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    public function doInventoryAdd(){
        if(IS_POST){
            // 条件判断
            $ary_post = $this->_post();
            if(empty($ary_post['g_name']) || empty($ary_post['pdt_id'])){
                $this->error('请填写规格和会员');
            }
            $member = $this->getMemberByMname($ary_post['g_name']);
            $product = $this->getPdtStockByPdtId($ary_post['pdt_id']);
            if(empty($member)){
                $this->error('不存在该会员');exit();
            }
            if(empty($product)){
                $this->error('不存在该规格商品');exit();
            }
            if(!isset($ary_post['is_payed']) && empty($ary_post['expired_time'])){
                $this->error('请填写过期时间');exit();
            }
            if($product['pdt_stock']==0 || $ary_post['ipl_num'] <= 0){
                $this->error('请正确分配库存');exit();
            }
            if($product['pdt_stock']<$ary_post['ipl_num']){
                $ary_post['ipl_num'] = $product['pdt_stock'];
            }
            $ary_inventory_data = array(
                'g_id' => $ary_post['g_id'],
                'm_id' => $member['m_id'],
                );
            $ary_inventory_pdt_data = array(
                'pdt_id' => $ary_post['pdt_id'],
                'ipl_num' => $ary_post['ipl_num'],
                'iny_is_payed' => isset($ary_post['is_payed']) ? $ary_post['is_payed'] : 0,
                'iny_expired_time' => isset($ary_post['expired_time']) ? $ary_post['expired_time'] : 0
                );
            $tag = false;
            $inventory_lock = D('inventory_lock');
            $inventory_lock->startTrans();
            try{
                // 更新fx_inventory_lock表
                $condition = array(
                    'fx_inventory_lock.m_id' => $member['m_id'],
                    'fx_inventory_lock.g_id' => $ary_post['g_id'],
                    'fx_inventory_pdt_lock.pdt_id' => $ary_post['pdt_id']
                    );
                $inventory = $this->getInventoryLockByCondition($condition);
                if(empty($inventory)){
                    // 如果存在记录
                    $ary_inventory_data['iny_num'] = $ary_post['ipl_num'];
                    $tag = $inventory_lock->add($ary_inventory_data);
                    if(!$tag){
                        $inventory_lock->rollback();
                        $this->error('添加失败');exit();
                    }
                    $ary_inventory_pdt_data['iny_id'] = $tag;
                }else{
                    // 如果存在记录
                    $ary_inventory_data['iny_num'] = $ary_post['ipl_num']+$inventory['iny_num'];
                    $tag = $inventory_lock->where(array("iny_id"=>$inventory['iny_id']))->save($ary_inventory_data);
                    if(!$tag){
                        $inventory_lock->rollback();
                        $this->error('添加失败');exit();
                    }
                    $ary_inventory_pdt_data['iny_id'] = $inventory['iny_id'];
                }
                // 更新fx_inventory_pdt_lock表
                $tag = D('inventory_pdt_lock')->add($ary_inventory_pdt_data);
                if(!$tag){
                    $inventory_lock->rollback();
                    $this->error('添加失败');exit();
                }
                // 更新fx_goods_products(规格表)
                // $goods_products = $this->getPdtStockByPdtId($ary_post['pdt_id']);
                // $ary_goods_products = array(
                //     'pdt_stock' => $goods_products['pdt_stock']-$ary_post['ipl_num'],                  // 可下单库存
                //     'pdt_freeze_stock' => $goods_products['pdt_freeze_stock'] + $ary_post['ipl_num']   // 冻结库存
                //     );
                // $tag = D('goods_products')->where(array('pdt_id'=>$ary_post['pdt_id']))->save($ary_goods_products);
                // if(!$tag){
                //     $inventory_lock->rollback();
                //     $this->error('添加失败');exit();
                // }
                $inventory_lock->commit();
            }catch(Exception $e){
                $inventory_lock->rollback();
                $this->error($e);exit();
            }
            $this->success('分配成功',U("Admin/Inventory/pageList",array('id'=>$ary_post['g_id'])));
        }
    }

    /**
     * 删除分配记录
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    public function doInventoryisDel(){
        $data = array(
            'status' => 0,
            'info' => '删除失败!'
            );
        if(IS_AJAX){
            $ary_request = $this->_request();
            $iny_pdt_id = intval($ary_request['iny_pdt_id']);
            $inventory_pdt = array();
            $inventory = array();
            if(!empty($iny_pdt_id)){
                $inventory_pdt = D('inventory_pdt_lock')
                    ->where(array('iny_pdt_id'=>$iny_pdt_id))
                    ->field('ipl_num,iny_id,ipl_num')
                    ->limit(0,1)
                    ->find();
            }
            if(!empty($inventory_pdt)){
                $inventory = D('inventory_lock')
                    ->where(array('iny_id'=>$inventory_pdt['iny_id']))
                    ->field('iny_id,iny_num')
                    ->limit(0,1)
                    ->find();
            }
            if(!empty($inventory)){
                $inventoryModel = D('inventory_lock');
                $inventoryModel->startTrans();
                try{
                    $inventory_lock_data = array(
                        'iny_num' => $inventory['iny_num']-$inventory_pdt['ipl_num']
                        );
                    $tag = D('inventory_lock')->where(array('iny_id'=>$inventory['iny_id']))->save($inventory_lock_data);
                    if($tag && false !== D("inventory_pdt_lock")->where(array("iny_pdt_id"=>$iny_pdt_id))->delete()){
                        $data = array(
                            'status' => 1,
                            'info' => '操作成功',
                            );
                        $inventoryModel->commit();
                    }else{
                        $inventoryModel->rollback();
                    }
                }catch(Exception $e){
                    $inventoryModel->rollback();
                    $data['info'] = $e;
                }
            }
            $this->ajaxReturn($data,'JSON');
        }else{
            echo FALSE;exit();
        }
    }

    /**
     * 检测是否存在该用户
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    public function checkMember(){
    	if(IS_AJAX){
    		$ary_request = $this->_request();
    		$member = $this->getMemberByMname($ary_request['m_name']);
            if($member) echo TRUE;
            echo FALSE;
    	}else{
            echo FALSE;
        }
    }

    /**
     * 更换商品规格库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    public function changePdtStock(){
        if(IS_AJAX){
            $ary_request = $this->_request();
            $member = $this->getPdtStockByPdtId($ary_request['pdt_id']);
            if($member) echo $member['pdt_stock'];
            echo FALSE;
        }else{
            echo FALSE;
        }
    }

    /**
     * 根据条件获取inventory_lock数据记录
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    public function getInventoryLockByCondition($where){
        $inventory = D('inventory_lock')
            ->where($where)
            ->join("fx_inventory_pdt_lock as fx_inventory_pdt_lock on(fx_inventory_pdt_lock.iny_id=fx_inventory_lock.iny_id) ")
            ->field('fx_inventory_lock.`iny_num`,fx_inventory_lock.`iny_id`,fx_inventory_lock.`g_id`,fx_inventory_lock.`m_id`')
            ->limit(0,1)->find();
        if(!empty($inventory)) return $inventory;
        return false;
    }

    /**
     * 根据pdt_id获取商品规格库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    protected function getPdtStockByPdtId($pdt_id){
        $pdt_id = intval($pdt_id);
        if(empty($pdt_id)) return false;
        $pdtModel = D('goods_products');
        $where = array('pdt_id'=>$pdt_id);
        $data = $pdtModel
            ->where($where)
            ->field("pdt_stock,pdt_freeze_stock")
            ->limit(0,1)
            ->find();
        $InventoryStock = $this->getIntventLockStock($pdt_id);
        if(!empty($data)){
            if(!empty($InventoryStock)){
                $data['pdt_stock'] -= $InventoryStock['ipl_num'];
            }
            return $data;
        }
        return false;
    }

    /**
     * 获取可分配库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    protected function getIntventLockStock($pdt_id){
        $pdt_id = intval($pdt_id);
        if(empty($pdt_id)) return false;
        $pdtModel = D('inventory_pdt_lock');
        $where = array(
            'pdt_id' => array('eq',$pdt_id),
            'iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'),
            );
        $data = $pdtModel
            ->where($where)
            ->field("sum(ipl_num) as ipl_num")
            ->limit(0,1)
            ->find();
        if(!empty($data)) return $data;
        return false;
    }

    /**
     * 根据用户名获取用户ID
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    protected function getMemberByMname($mname){
    	if(empty($mname)) return false;
    	$where = array('m_name'=>$mname);
    	$memberModel = D('members');
    	$data = $memberModel
    		->where($where)
            ->field("m_name,m_id")
            ->limit(0,1)
            ->find();
        if(!empty($data)) return $data;
        return false;
    }

    /**
     * 根据g_id获取他的规格信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-11
     */
    protected function getProductsByGid($gid){
    	$gid = intval($gid);
    	if($gid==0) return array();
    	$where = array('fx_goods.g_id' => $gid);
    	$productModel = D('goods');
    	$data = $productModel
    		->where($where)
    		->field("
    			fx_goods.`g_id`,product.`pdt_id`,product.`pdt_sn`,product.`pdt_stock`
    			")
    		->join("fx_goods_products as product on(fx_goods.g_id=product.g_id)")
    		->group('product.pdt_id')
    		->limit(0,100)
    		->select();
    	return $data;
    }

    /**
     * 分页列表
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-10
     */
    protected function pageInventory($array_condition = array(), $order_by, $int_page_size = 20) {
        $InventoryModel = D("Inventory_pdt_lock");
        $count = $InventoryModel
                ->where($array_condition)
                ->join("fx_inventory_lock as inventory_lock on(inventory_lock.iny_id=fx_inventory_pdt_lock.iny_id) ")
                ->join("fx_goods_products as goods_products on(goods_products.pdt_id=fx_inventory_pdt_lock.pdt_id)")
                ->join("fx_members as member on(member.m_id = inventory_lock.m_id)")
                ->join("fx_members_level as level on(level.ml_id=member.ml_id)")
                ->count();
        // echo "<pre>";print_r($InventoryModel->getLastSql());exit;
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $InventoryModel
                ->where($array_condition)
                ->field("
                member.`m_name` AS `m_name`,level.`ml_name` AS `ml_name`,
                fx_inventory_pdt_lock.`ipl_num` AS `ipl_num`,fx_inventory_pdt_lock.`iny_id` AS `iny_id`,fx_inventory_pdt_lock.`iny_pdt_id`,
                goods_products.`pdt_sn` AS `pdt_sn`,
                fx_inventory_pdt_lock.`iny_is_payed` AS `iny_is_payed`,fx_inventory_pdt_lock.`iny_expired_time` AS `iny_expired_time`
                ")
                ->join("fx_inventory_lock as inventory_lock on(inventory_lock.iny_id=fx_inventory_pdt_lock.iny_id) ")
                ->join("fx_goods_products as goods_products on(goods_products.pdt_id=fx_inventory_pdt_lock.pdt_id)")
                ->join("fx_members as member on(member.m_id = inventory_lock.m_id)")
                ->join("fx_members_level as level on(level.ml_id=member.ml_id)")
                ->order($order_by)
                // ->group()
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
                // echo "<pre>";print_r($InventoryModel->getLastSql());exit;
        return $data;
    }
}