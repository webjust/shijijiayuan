<?php

/**
 * 货品模型层 Model
 * @package Model
 * @version 7.0
 * @author Terry<wanghui@guanyisoft.com>
 * @date 2013-3-28
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsProductsModel extends GyfxModel {

    /**
     * 构造方法
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }

    protected $tableName = 'view_products';
    
    /**
     * 更新货品库存
     * @author Zhangjiasuo
     * @date 2013-04-07
     */
    public function UpdateStock($pdt_id,$num) {
        //产生一张系统的库存调整单
        $stock_revise_receipt = array();
        $stock_revise_receipt["srr_type"] = 1;
        $stock_revise_receipt["srr_desc"] = "下单库存扣除";
        $stock_revise_receipt["srr_verify"] = 1;
        $stock_revise_receipt["srr_create_time"] = date("Y-m-d H:i:s");
        $stock_revise_receipt["srr_verify_time"] = date("Y-m-d H:i:s");
        $stock_revise_receipt["srr_update_time"] = date("Y-m-d H:i:s");
        $int_srr_id = D("StockReviseReceipt")->add($stock_revise_receipt);
        if(false === $int_srr_id){
            //生成库存调整单基本信息失败
            return array("status"=>false,"msg"=>"生成库存调整单失败","code"=>"CREATE-StockReviseReceipt-ERROR");
        }
        
        //生成库存调整单明细
        $stock_revise_receipt_detail = array();
        $stock_revise_receipt_detail["srr_id"] = $int_srr_id;
        $stock_revise_receipt_detail["pdt_id"] = $pdt_id;
        //下单占用库存 - 冻结
        $stock_revise_receipt_detail["srrd_type"] = 2;
        $stock_revise_receipt_detail["srrd_num"] = $num;
        $stock_revise_receipt_detail["srrd_status"] = 1;
        $stock_revise_receipt_detail["srrd_create_time"] = date("Y-m-d H:i:s");
        $stock_revise_receipt_detail["srrd_update_time"] = date("Y-m-d H:i:s");
        $int_detail_id = D("StockReviseReceiptDetail")->add($stock_revise_receipt_detail);
        if(false === $int_detail_id){
            return array("status"=>false,"msg"=>"生成库存调整单明细失败","code"=>"CREATE-StockReviseReceiptDetail-ERROR");
        }
        // Tom 优先扣除分销商锁定/买断库存 (2014-09-13)
        $inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_STOCK');
        if(isset($inventoryConfig['INVENTORY_STOCK']['sc_value']) && $inventoryConfig['INVENTORY_STOCK']['sc_value'] == 1)
        {
            $tag = $this->UpdateInventoryLockStock($pdt_id,$num,$int_srr_id);
            if(false == $tag){
                return array("status"=>false,"msg"=>"扣除分销商库存失败。","code"=>"DEC-PRODUCTS-INVENTORY-LOCK-STOCK-ERROR");
            }
        }
        //扣除SKU表中商品的可下单库存，增加冻结库存
        if(false === D("GoodsProductsTable")->where(array("pdt_id"=>$pdt_id))->setDec("pdt_stock",$num)){
            return array("status"=>false,"msg"=>"扣除商品可下单库存失败。","code"=>"DEC-PRODUCTS-STOCK-ERROR");
        }
        
        //增加SKU的冻结库存
        if(false === D("GoodsProductsTable")->where(array("pdt_id"=>$pdt_id))->setInc("pdt_freeze_stock",$num)){
            return array("status"=>false,"msg"=>"增加商品冻结库存失败。","code"=>"DEC-PRODUCTS-STOCK-ERROR-2");
        }
        
        return array("status"=>true,"msg"=>"生成库存调整单成功","code"=>"SUCCESS");
    }
    /**
     * 扣除分销商锁定/买断库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-13
     */
    public function UpdateInventoryLockStock($int_pdt_id,$num,$int_srr_id){
        $ary_member = session('Members');
        if(!isset($ary_member['m_id']) || empty($ary_member['m_id']) || empty($num)) return true;
        $array_inventory_where = array(
            'fx_inventory_pdt_lock.pdt_id' => array('eq',$int_pdt_id),
            'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'), // 过期时间处理
            'inventory.m_id' => array('eq',$ary_member['m_id'])
            );
        $array_inventory_info = $this->getInventoryLockByCondition($array_inventory_where);
        if(empty($array_inventory_info)) return true;
        // 无差别扣除库存(即不区分锁定和买断优先,以及数量多少优先)
        $tag = true;
        $effect_stock = 0;
        foreach($array_inventory_info as $iny_detail){
             if($iny_detail['ipl_num'] >= $num){
                $ary_effect_data = array(
                    'ipl_num' => $iny_detail['ipl_num'] - $num,
                    'ipl_num_frozen' => $iny_detail['ipl_num_frozen'] + $num,
                    'ipl_update_time' => date('Y-m-d H:i:s')
                    );
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                $effect_stock += $num;
                break;
            }else{
                $ary_effect_data = array(
                    'ipl_num' => 0,
                    'ipl_num_frozen' => $iny_detail['ipl_num_frozen'] + $iny_detail['ipl_num']
                    );
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                if(false === $tag) break;
                $num -= $iny_detail['ipl_num'];
                $effect_stock += $iny_detail['ipl_num'];
            }
        }
        if($tag === false) return false;
        // 生成分销商库存调整单
        $inventory_lock_detail = array();
        $inventory_lock_detail['srr_id'] = $int_srr_id;         // 分销商调整单ID
        $inventory_lock_detail['m_id'] = $ary_member['m_id'];   // 分销商ID
        $inventory_lock_detail['pdt_id'] = $int_pdt_id;         // 被调整的规格ID
        $inventory_lock_detail['sild_num'] = $effect_stock;     // 变更数量
        $inventory_lock_detail['sild_status'] = 1;              // 0删除,1正常
        $inventory_lock_detail['sild_create_time'] = date('Y-m-d H:i:s');
        $inventory_lock_detail['sild_update_time'] = date('Y-m-d H:i:s');
        $sild_id = D('stock_inventory_lock_detail')->add($inventory_lock_detail);
        if(false === $sild_id) return false;
        // 分销商库存调整单日志表
        $inventory_lock_modify_log['srr_id'] = $int_srr_id;         // 被调整单据ID
        $inventory_lock_modify_log['m_id'] = $ary_member['m_id'];   // 分销商ID
        $inventory_lock_modify_log['silml_type'] = 0;               // 操作类型
        $inventory_lock_modify_log['sild_id'] = $sild_id;           // 明细操作涉及的明细ID
        $inventory_lock_modify_log['srrml_create_time'] = date('Y-m-d H:i:s');
        if(false === D('stock_inventory_lock_modify_log')->add($inventory_lock_modify_log)) return false;
        return true;
    }

    /**
     * 返回分销商锁定/买断库存 (无差别返还库存即不区分锁定和买断优先,以及数量多少优先)
     * @param $ary
     * @example $ary = array(
     *                  'm_id' => (int)$m_id,       // 分销商ID
     *                  'pdt_id' => (int)$pdt_id,   // 规格ID
     *                  'num' => (int)$num,         // 返还的库存
     *                  'srr_id' => (int)$srr_id,   // 库存调整单ID
     *                  'u_id' => (int)$u_id,       // 操作者ID
     *                  );
     * @return boolen
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-14
     */
    public function BackInventoryLockStock($ary){
        // 判断是否开启
        $inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_STOCK');
        if(!isset($inventoryConfig['INVENTORY_STOCK']['sc_value']) || $inventoryConfig['INVENTORY_STOCK']['sc_value'] == 0) return true;
        // 判断是否存在分销商ID
        if(!isset($ary['m_id']) || empty($ary['m_id']) || empty($ary['num'])) return true;
        $array_inventory_where = array(
            'fx_inventory_pdt_lock.pdt_id' => array('eq',$ary['pdt_id']),
            'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'), // 过期时间处理
            'inventory.m_id' => array('eq',$ary['m_id'])
            );
        $array_inventory_info = $this->getInventoryLockByCondition($array_inventory_where);
        if(empty($array_inventory_info)) return true;
        $tag = true;
        $effect_stock = 0;
        foreach($array_inventory_info as $iny_detail){
            if(empty($iny_detail['ipl_num_frozen'])) continue;
            if($iny_detail['ipl_num_frozen'] >= $ary['num']){
                $ary_effect_data = array(
                    'ipl_num' => array('exp',"ipl_num+".$ary['num']),
                    'ipl_num_frozen' => array('exp',"ipl_num_frozen-".$ary['num']),
                    );
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                $effect_stock += $ary['num'];
                break;
            }else{
                $ary_effect_data = array(
                    'ipl_num' => array('exp',"ipl_num+".$iny_detail['ipl_num_frozen']),
                    'ipl_num_frozen' => array('exp',"ipl_num_frozen-".$iny_detail['ipl_num_frozen']),
                    );
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                $ary['num'] -= $iny_detail['ipl_num_frozen'];
                $effect_stock += $iny_detail['ipl_num_frozen'];
            }
        }
        if($tag === false) return false;
        if(empty($effect_stock)) return true;   // 如果没有冻结库存返回
        // 生成分销商库存调整单
        $inventory_lock_detail = array();
        $inventory_lock_detail['srr_id'] = $ary['srr_id'];      // 分销商调整单ID
        $inventory_lock_detail['m_id'] = $ary['m_id'];          // 分销商ID
        $inventory_lock_detail['pdt_id'] = $ary['pdt_id'];      // 被调整的规格ID
        $inventory_lock_detail['sild_num'] = $effect_stock;     // 变更数量
        $inventory_lock_detail['sild_status'] = 1;              // 0删除,1正常
        $inventory_lock_detail['sild_create_time'] = date('Y-m-d H:i:s');
        $inventory_lock_detail['sild_update_time'] = date('Y-m-d H:i:s');
        $sild_id = D('stock_inventory_lock_detail')->add($inventory_lock_detail);
        if(false === $sild_id) return false;
        // 分销商库存调整单日志表
        $inventory_lock_modify_log['srr_id'] = $ary['srr_id'];      // 被调整单据ID
        $inventory_lock_modify_log['m_id'] = $ary['m_id'];          // 分销商ID
        $inventory_lock_modify_log['silml_type'] = 1;               // 操作类型
        $inventory_lock_modify_log['sild_id'] = $sild_id;           // 明细操作涉及的明细ID
        $inventory_lock_modify_log['u_id'] = $ary['u_id'];          // 操作者ID
        $inventory_lock_modify_log['srrml_create_time'] = date('Y-m-d H:i:s');
        if(false === D('stock_inventory_lock_modify_log')->add($inventory_lock_modify_log)) return false;
        return true;
    }

    /**
     * 扣除冻结库存
     * @param $ary
     * @example $ary = array(
     *                  'pdt_id' => (int)$pdt_id,       // 规格ID
     *                  'm_id' => (int)$m_id,           // 分销商ID
     *                  'num' => (int)$num,             // 数量
     *                  )
     * @return $result = array(
     *                   'status' => (boolen)$status,    // 状态
     *                   'code' => (string)$code,        // 错误标识
     *                   'msg' => (string)$msg,          // 返回信息
     *                   )
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-15
     */
    public function DeductionInventoryFrozenStock($ary){
        // 判断是否开启
        $inventoryConfig = D('SysConfig')->getConfigs('GY_STOCK','INVENTORY_STOCK');
        if(!isset($inventoryConfig['INVENTORY_STOCK']['sc_value']) || $inventoryConfig['INVENTORY_STOCK']['sc_value'] == 0){
            return array('status' => true,'code' => 'TURN-OFF','msg' => '未开启该设置');
            // return true;
        }
        // 判断是否存在分销商ID
        if(!isset($ary['m_id']) || empty($ary['m_id']) || empty($ary['num'])){
            return array('status' => true,'code' => 'NO-RESELLER','msg' => '无分销商ID');
            // return true;
        }
        $array_inventory_where = array(
            'fx_inventory_pdt_lock.pdt_id' => array('eq',$ary['pdt_id']),
            'fx_inventory_pdt_lock.iny_expired_time' => array(array('eq',0),array('gt',date('Y-m-d H:i:s',time())),'OR'), // 过期时间处理
            'inventory.m_id' => array('eq',$ary['m_id']),
            'fx_inventory_pdt_lock.ipl_num_frozen`' => array('neq',0)
            );
        $array_inventory_info = $this->getInventoryLockByCondition($array_inventory_where);
        if(empty($array_inventory_info)){
            return array('status' => true,'code' => 'NO-DATA','msg' => '无数据');
            // return true;
        }
        $tag = true;
        foreach($array_inventory_info as $iny_detail){
            if(empty($iny_detail['ipl_num_frozen'])) continue;
            if($iny_detail['ipl_num_frozen'] >= $ary['num']){
                // $ary_effect_data = array(
                //     'ipl_num_frozen' => array('exp',"ipl_num_frozen - ".$ary['num'])
                //     );
                // $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->setDec('ipl_num_frozen',$ary['num']);
                break;
            }else{
                // $ary_effect_data = array(
                //     'ipl_num_frozen' => 0
                //     );
                // $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->save($ary_effect_data);
                $tag = D('inventory_pdt_lock')->where(array('iny_pdt_id'=>$iny_detail['iny_pdt_id']))->setDec('ipl_num_frozen',$iny_detail['ipl_num_frozen']);
                $ary['num'] -= $iny_detail['ipl_num_frozen'];
            }
            if($tag === false){
                return array('status' => false,'code' => 'EDIT-INVENTORY-FROZEN-STOCK-LOCK-FAIL','msg' => '修改库存失败');
                // return false;
            }
        }
        return array('status' => true,'code' => 'EDIT-INVENTORY-FROZEN-STOCK-LOCK-SUCCESS','msg' => '执行成功');
    }
    /**
     * 查询符合条件的分销商库存
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-13
     */
    public function getInventoryLockByCondition($where){
        return $array_inventory_info = D('inventory_pdt_lock')
            ->where($where)
            // ->field("sum(fx_inventory_pdt_lock.`ipl_num`) as lock_stock")    // 无法解决过期问题
            ->field("fx_inventory_pdt_lock.`ipl_num` as ipl_num,fx_inventory_pdt_lock.`iny_pdt_id`,fx_inventory_pdt_lock.`ipl_num_frozen`,inventory.`m_id` ")
            ->join("fx_inventory_lock as inventory on(fx_inventory_pdt_lock.iny_id=inventory.iny_id)")
            ->order("fx_inventory_pdt_lock.`ipl_update_time` DESC")
            // ->group('fx_inventory_pdt_lock.`ipl_num`')
            ->select();
    }
    /**
     * 查询指定货号的货品
     * @author Zhangjiasuo
     * @param string 商品货号
     * @date 2013-04-18
     */
    public function Search($ary_where = array(), $ary_field = '') {
        $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                        ->field($ary_field)
                        ->where($ary_where)->find();
        return $res_products;
    }
    

    public function GetProductCount($condition) {
        $res=M('goods_products')->where($condition)->count();
        return $res;
    }

    /**
     * 查询条件结果集
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-06-26
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @param array $group 分组
     * @param array $limit 查询数量
     */
    public function GetProductList($condition = array(), $ary_field = '',$group= '',$limit= '') {
        if(empty($limit)){
            $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                        ->join('fx_goods ON fx_goods.g_id = fx_goods_products.g_id')
                        ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                        ->field($ary_field)
                        ->where($condition)->select();
        }
        else{
            $res_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')
                        ->join('fx_goods ON fx_goods.g_id = fx_goods_products.g_id')
                        ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                        ->field($ary_field)
                        ->where($condition)->limit($limit['start'],$limit['end'])->select();
        }
        
        return $res_products;
    }

    public function GetOrderProductCount($condition) {
        $res=M('orders_items')->where($condition)->count();
        return $res;
    }

    public function GetOrderProductList($condition = array(), $ary_field = '',$group= '',$limit= ''){
        $res_orderproducts = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')
                        ->join('fx_goods_products ON fx_goods_products.g_id = fx_orders_items.g_id')
                        ->join('fx_goods ON fx_goods.g_id = fx_goods_products.g_id')
                        ->join('fx_goods_info ON fx_goods_info.g_id = fx_goods_products.g_id')
                        ->field($ary_field)
                        ->order($group)
                        ->where($condition)->limit($limit['start'],$limit['end'])->select();
        return $res_orderproducts;
    }
    
    /**
     * 判断某个规格属于哪个ERP
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-09-06
     */
    public function getErpByPdtId($pdt_id){
        $res_erps = M('warehouse_stock',C('DB_PREFIX'),'DB_CUSTOM')
                        ->field('erp_id')
                        ->where(array('pdt_id'=>$pdt_id))->select();
        if(empty($res_erps) || count($res_erps)>1){
            return false;
        }else{
            //更新订单ERP_ID
            return $res_erps[0]['erp_id'];
        }
    }
    
    
    /**
     * 根据商家编码 找到分销系统内 可以匹配上的货品，并返回价格和库存
     * @author zuo
     * @date 2012-08-23
     * @param string $outer_id 淘宝的商家编码 即 本系统内的商品编号或者货品编号
     * @return array 如果匹配上，则返回匹配状态和本系统内的价格、库存,如果未匹配上，价格库存返回都是0
     * @edit zuo 2012-10-17 此处增加三个返回值，g_id,g_sn,pdt_sn
     */
    public function getProductInfo($outer_id='',$pdt_id='') {
        if($outer_id){
            $res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_sn'=>$outer_id))->find();
        }
        elseif($pdt_id){
            $res = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pdt_id'=>$pdt_id))->find();
        }
        if ($res) {
            return array($res, $res['pdt_sale_price'], $res['pdt_stock'], $res['g_id'], $res['g_sn'], $res['pdt_sn']);
        } else {
            return array($res, 0, 0, 0, '', '');
        }
    }
    
    /**
     * 将[1627207:3232484:颜色分类:天蓝色;20509:28381:尺码:XXS]之类的淘宝属性，过滤掉其中的pid,vid.
     * 转换为[颜色分类:天蓝色;尺码:XXS]样的纯文本
     * @author zuo
     * @date 2012-08-23
     * @param string $subTitle 淘宝属性字串
     * @return string 返回脱水后的字串
     */
    public function filterSubTitle($subTitle){
        $ary_subTitle = explode(';', $subTitle);
        $str_return = '';
        foreach($ary_subTitle as $v){
            $ary_tmp_value = explode(':', $v);
            $str_return = $str_return.$ary_tmp_value[2].':'.$ary_tmp_value[3].';';
        }
        return $str_return;
    }
    
    /**
     * 获取货品的规格组合
     * @param $condtion
     * @param $selectSpec arrary('规格名字'=>'规格值')
     * @return mixed
     * @ by wanghaoyu 2014-11-4
     */
    public function getPdtBySpec($condtion, $selectSpec){
        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
        $goodsSpec = D('GoodsSpec');
        $ary_product_feild = array('pdt_sn', 'pdt_weight', 'pdt_stock', 'pdt_memo', 'pdt_id', 'pdt_sale_price','pdt_market_price', 'pdt_on_way_stock', 'pdt_is_combination_goods');
        $ary_pdt = $products->field($ary_product_feild)->where(array("g_id"=>$condtion['g_id'],"pdt_status"=>'1'))->select();
        foreach ($ary_pdt as $valpdt) {
            $saleSpecNum = 0;
            $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
			//$goodsStock = D("GoodsStock")->getRegionStock($condtion['g_id'], $valpdt['pdt_id']);
			//$valpdt['pdt_stock'] = $goodsStock;
            $ary_spec = explode(';',$specInfo['spec_name']);
            foreach($ary_spec as $specName){
                $specTmp = explode(':',$specName);
                $filer_spec[$specTmp[0]] = $specTmp[1];
            }

            $is_this = true;
            foreach($selectSpec as $spec_key=>$spec_val) {
                if($filer_spec[$spec_key] != $spec_val){
                    $is_this = false;
                    break;
                }
            }
            if($is_this) {
                return $valpdt;
            }

        }
    }
    
}