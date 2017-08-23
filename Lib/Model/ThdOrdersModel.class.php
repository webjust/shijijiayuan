<?php

/**
 * 第三方订单数据模型
 *
 * @package Model
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-7
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdOrdersModel extends GyfxModel {

    private $thdOrdersItems;

    public function _initialize() {
        parent::_initialize();
        $this->thdOrdersItems = M('ThdOrdersItems',C('DB_PREFIX'),'DB_CUSTOM');
    }

    /**
     * 新增第三方淘宝订单并保存到分销平台
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-7
     * @param array $ary_result 第三方订单数据
     * @param int $int_mid 对应用户ID，否则为0
     * @return boolean 成功则返回ID 失败返回false 
     */
    public function saveAddTrdordersOrder($ary_val, $int_mid = 0) {

        //echo "<pre>";print_r($ary_result);exit;
        $set_save_data = array(
            'to_oid' => $ary_val['tt_id'],
            'to_source' => $ary_val['tt_source'],
            'to_buyer_id' => $ary_val['buyer'],
            'to_created' => $ary_val['created'],
            'to_modified' => $ary_val['modified'],
            'to_pay_time' => $ary_val['pay_time'],
            'to_post_fee' => $ary_val['post_fee'],
            'to_payment' => $ary_val['payment'],
            'to_receiver_address' => $ary_val['receiver_address'],
            'to_receiver_city' => $ary_val['receiver_city'],
            'to_receiver_district' => $ary_val['receiver_district'],
            'to_receiver_mobile' => $ary_val['receiver_mobile'],
            'to_receiver_name' => $ary_val['receiver_name'],
            'to_receiver_province' => $ary_val['receiver_state'],
            'to_receiver_zip' => $ary_val['receiver_zip'],
            'm_id' => $int_mid,
            'to_seller_title' => $ary_val['title'],
            'to_thd_status' => $ary_val['thd_status'],
            'to_buyer_message' => $ary_val['buyer_message'],
            'to_seller_memo' => $ary_val['seller_memo'],
            'ts_id' => $ary_val['ts_id'],
            'to_receiver_phone' => $ary_val['receiver_phone']
        );
        $to_id = $this->add($set_save_data);
        //echo "<pre>";print_r($to_id);exit;
        if (!empty($to_id) && $to_id > 0) {
            return $to_id;
        } else {
            return false;
        }
    }

    /**
     * 更新第三方淘宝订单并保存到分销平台
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-7
     * @param array $ary_result 第三方订单数据
     * @param int $int_mid 对应用户ID，否则为0
     * @return boolean 成功则返回ID 失败返回false 
     */
    public function saveUpdateTrdordersOrder($ary_val, $int_mid = 0) {
        $where['to_id'] = $ary_val['to_id'];
        $set_save_data = array(
            'to_oid' => $ary_val['tt_id'],
            'to_source' => $ary_val['tt_source'],
            'to_buyer_id' => $ary_val['buyer'],
            'to_created' => $ary_val['created'],
            'to_modified' => $ary_val['modified'],
            'to_pay_time' => $ary_val['pay_time'],
            'to_post_fee' => $ary_val['post_fee'],
            'to_payment' => $ary_val['payment'],
            'to_receiver_address' => $ary_val['receiver_address'],
            'to_receiver_city' => $ary_val['receiver_city'],
            'to_receiver_district' => $ary_val['receiver_district'],
            'to_receiver_mobile' => $ary_val['receiver_mobile'],
            'to_receiver_name' => $ary_val['receiver_name'],
            'to_receiver_province' => $ary_val['receiver_state'],
            'to_receiver_zip' => $ary_val['receiver_zip'],
            'm_id' => $int_mid,
            'to_seller_title' => $ary_val['title'],
            'to_thd_status' => $ary_val['thd_status'],
            'to_buyer_message' => $ary_val['buyer_message'],
            'to_seller_memo' => $ary_val['seller_memo'],
            'ts_id' => $ary_val['ts_id'],
            'to_receiver_phone' => $ary_val['receiver_phone'],
            'to_seller_flag' => $ary_val['seller_flag']
        );
		
		if($ary_val["to_pay_type"]){
			$set_save_data['to_pay_type'] = $ary_val["to_pay_type"];
		}
        //echo "<pre>";print_r($set_save_data);exit;
        $to_id = $this->where($where)->data($set_save_data)->save();
		//echo $this->getLastSql();exit;
        //var_dump($to_id);
		if($to_id === false){
			return false;
		}else{
			return true;
		}
    }
    
    /**
     * 在下载前更新第三方淘宝订单状态为空
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2014-4-14
     * @param array $where 条件
     * @return boolean 成功则返回true 失败返回false 
     */
    public function UpdateTrdordersStatus($where = array()) {
        if(empty($where)) return true;
        $set_save_data = array(
            'to_thd_status' => ''
        );
        
        $return_bool = $this->where($where)->data($set_save_data)->save();
       // echo $this->getLastSql();exit;
        if ($return_bool === false) {
            return false;
        } else {
            return true;
        }
    }
    

    /**
     * 新增第三方淘宝订单明细并保存到分销平台
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param array $ary_result 第三方订单明细
     * @return boolean 成功则返回ID 失败返回false 
     */
    public function saveAddTrdordersOrderItem($ary_result, $tt_id) {
        $set_data = array(
            'toi_id' => '',
            'to_id' => $tt_id,
            'toi_num' => $ary_result['toi_num'],
            'toi_num_id' => $ary_result['toi_num_id'],
            'toi_price' => $ary_result['toi_price'],
            'toi_title' => $ary_result['toi_title'],
            'toi_outer_id' => $ary_result['toi_outer_id'],
            'toi_outer_sku_id' => $ary_result['toi_outer_sku_id'],
            'sku_properties_name' => $ary_result['sku_properties_name'],
            'toi_url' => $ary_result['toi_url'],
            'toi_b2b_pdt_sn_info' => $ary_result['toi_b2b_pdt_sn_info']
        );
        $to_id = $this->thdOrdersItems->add($set_data);
        if (!empty($to_id) && $to_id > 0) {
            if (!empty($ary_result['toi_b2b_pdt_sn_info'])) {
                $this->where(array('to_oid' => $tt_id))->data(array('to_is_match' => '1'))->save();
            }
            return $to_id;
        } else {
            return false;
        }
    }

    /**
     * 更新第三方淘宝订单明细并保存到分销平台
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param array $ary_result 第三方订单明细
     * @return boolean 成功true 失败返回false 
     */
    public function saveTrdordersOrderHandle($where, $set_data = array()) {
        if (!empty($where) && is_array($where)) {
            $tt_id = $this->where($where)->data($set_data)->save();
            if (!empty($tt_id) && $tt_id > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 更新第三方淘宝订单明细并保存到分销平台
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param array $ary_result 第三方订单明细
     * @return boolean 成功true 失败返回false 
     */
    public function updateTrdordersOrderItem($ary_filter, $ary_data) {
        
        if (!empty($ary_filter) && is_array($ary_filter)) {
            $to_id = $this->thdOrdersItems->where($ary_filter)->save($ary_data);
            if (FALSE !== $to_id) {
                if (!empty($ary_data['toi_b2b_pdt_sn_info'])) {
                    $ary_res = $this->where(array('to_oid' => $ary_filter['to_id']))->data(array('to_is_match' => '1'))->save();
                   
                    if(false !== $ary_res){
                        return true;
                    }else{
                        return false;
                    }
                    
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 查询第三方订单是在在本地存在
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param int $tt_id 对应第三方订单ID
     * @return boolean 返回成功或失败
     */
    public function getTrdordersTtid($tt_id) {
        $where['to_oid'] = $tt_id;
        $ary_result = $this->where($where)->find();
        if (!empty($ary_result) && is_array($ary_result)) {
            return $ary_result['to_id'];
        } else {
            return false;
        }
    }

    /**
     * 查询第三方订单明细是在在本地存在
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param int $toi_id 对应第三方订单明细ID
     * @param int $tt_id 对应第三方订单ID
     * @param int $m_id 对应会员ID
     * @return boolean 返回成功或失败
     */
    public function getTrdordersTotid($toi_id, $tt_id) {
        $where['toi_id'] = $toi_id;
        $where['to_id'] = $tt_id;
        $ary_result = $this->where($where)->find();
        if (!empty($ary_result) && is_array($ary_result)) {
            return $ary_result['toi_id'];
        } else {
            return false;
        }
    }

    /**
     * 将第三方订单中的商品匹配本地分销商品 只是适用于自动匹配
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param int $g_sn 第三方商家编码
     * @param int $pdt_sn 第三方货品的商家编码
     * @return array 查询到则返回数组 否则返回false
     */
    public function getMatchTrdOrders($g_sn, $pdt_sn='', $nums) {
        $ary_data = array();
        $where['g_sn'] = $g_sn;
        if(!empty($pdt_sn)) $where['pdt_sn'] = $pdt_sn;
		//用规格代码匹配商品,不用商品编码和规格编码
		if(!empty($g_sn) && !empty($pdt_sn)){
			unset($where['g_sn']);
		}
		if(empty($g_sn)){
			return false;
		}
        $i = 1;
        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_result = $products->where($where)->field("g_sn,pdt_sn,g_id,pdt_id")->find();
//        echo "<pre>";print_r($products->getLastSql());exit;
        if (!empty($ary_result) && is_array($ary_result)) {
            $ary_data[$i]['pdt_sn'] = $ary_result['pdt_sn'];
            $ary_data[$i]['num'] = $nums;
			$ary_data[$i]['g_id'] = $ary_result['g_id'];
			$ary_data[$i]['pdt_id'] = $ary_result['pdt_id'];
            return $ary_data;
        } else {
            return false;
        }
    }

    /**
     * 获取第三方订单
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param int $where 搜索条件
     * @param int $page  当前页
     * @param int $pagesize 每页显示多少条
     * @return array 查询到则返回数组
     */
    public function getThdOrdersPageList($where, $page, $pagesize) {
        $ary_data = $this->where($where)->order(array('to_created' => 'desc'))->limit($page . ',' . $pagesize)->select();
        
        if (!empty($ary_data) && is_array($ary_data)) {
            foreach ($ary_data as $kdata => $vdata) {
                $condition['to_id'] = $vdata['to_oid'];
                $ary_data[$kdata]['orders'] = M('ThdOrdersItems',C('DB_PREFIX'),'DB_CUSTOM')->where($condition)->order(array('toi_id' => 'desc'))->select();
            }
        }
        return $ary_data;
    }

    /**
     * 获取第三方订单信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-8
     * @param int $where 搜索条件
     * @return array 查询到则返回数组
     */
    public function getTrdordersData($where) {
        $ary_result = $this->where($where)->find();
        if (!empty($ary_result) && is_array($ary_result)) {
            return $ary_result;
        } else {
            return array();
        }
    }
	
	/**
     * 更新第三方淘宝订单发货状态并保存到分销平台
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2014-04-09
     * @param array $ary_result 第三方订单数据
     * @param int $int_mid 对应用户ID，否则为0
     * @return boolean 成功则返回ID 失败返回false 
     */
    public function saveUpdateTrdordersStatus($ary_val, $int_mid = 0) {
        $where['to_id'] = $ary_val['to_id'];
        $set_save_data = array(
            'to_thd_status' => $ary_val['thd_status']
        );
        $to_id = $this->where($where)->data($set_save_data)->save();
        if ($to_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除新添加的商品
     */
    public function deleteTrdordersOrderItem($ary_filter) {
        if (!empty($ary_filter) && is_array($ary_filter)) {
            $to_id = $this->thdOrdersItems->where($ary_filter)->delete();
            if (FALSE !== $to_id) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
