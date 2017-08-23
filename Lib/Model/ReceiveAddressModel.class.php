<?php

/**
 * 收货地址模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ReceiveAddressModel extends GyfxModel {

	/**
	 * 输出返回
	*/
	private function result($info='', $code='', $status=false, $data='') {
		$ary_info = array(
			'status'=>$status,
			'info'=>$info,
			'code'=>$code,
			'data'=>$data
		);
		return $ary_info;
	}
	
	/**
	 * 获取会员全部收货地址
	 * @param int $m_id
	 * return array
	*/
	public function getAddressList($m_id) {
		$ary_return = $this->field("cr_id,ra_is_default,ra_post_code,ra_detail,ra_id,ra_name,ra_mobile_phone")->where(array('m_id'=>$m_id,'ra_status'=>1))->select();
		if(!empty($ary_return)){
			foreach($ary_return as &$v){
				$v['address'] = D("CityRegion")->getFullAddressName($v['cr_id']);
			}
		}
		return $ary_return;
	}
	
	/**
	 * 获取默认地址
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-8
	*/
	public function getDefaultAddress($m_id, $default = 1) {
		$ary_address = array();
		$ary_address = $this->field("ra_id,cr_id,ra_name,ra_post_code,ra_detail,ra_mobile_phone,ra_is_default,ra_status")->where(array('m_id'=>$m_id,'ra_is_default'=>$default,'ra_status'=>1))->find();
		if(!empty($ary_address)){
			$ary_return = $this->getCityRegion($ary_address['cr_id']);
			$ary_address_info = array_merge($ary_address, $ary_return);
		}
		return $ary_address_info;
	}
	
	/**
	 * 通过ra_id获取地址信息
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-8
	*/
	public function getAddressByraid($ra_id) {
		$ary_address = array();
		$ary_address = $this->field("ra_id,cr_id,ra_name,ra_post_code,ra_detail,ra_is_default,m_id,ra_mobile_phone")->where(array('ra_id'=>$ra_id, 'ra_status'=>1))->find();
        if(strpos($ary_address['ra_mobile_phone'],':'))
            $ary_address['ra_mobile_phone'] = decrypt($ary_address['ra_mobile_phone']);
        if(!empty($ary_address)){
			$ary_return = $this->getCityRegion($ary_address['cr_id']);
			$ary_address_info = array_merge($ary_address, $ary_return);
		}
		return $ary_address_info;
	}
	
	/**
	 * 获取地址库数据
	 * @date 2014-4-8
	 * @param int $cr_id
	*/
	public function getCityRegion($cr_id) {
		$ary_cr_id = D("CityRegion")->getCityRegionInfoByLastCrId($cr_id);
		foreach	($ary_cr_id as $v){
			$ary_address_name[] = D("CityRegion")->getAddressName($v);
		}
		$ary_address_info = array_merge($ary_cr_id, $ary_address_name);
		return $ary_address_info;
	}
	
	/**
	 * 保存收货地址
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-4-8
	 * return int $ra_id
	*/
	public function doAdd($data) {
		if(!empty($data['ra_is_default']) && 1 == $data['ra_is_default']){
			//原有默认值更新为否
			$save = $this->where(array('m_id'=>$data['m_id']))->save(array('ra_is_default'=>0));
			if(FALSE === $save){
				return $this->result('更新默认值失败！', 'ReceiveAddressModel_doAdd_01');
			}
		}
        $ary_data = array(
            'ra_name' => $data['ra_name'],
            'cr_id' => (int)$data['cr_id'],
            'ra_detail' => $data['ra_detail'],
            //'ra_phone' => (string)encrypt($data['ra_phone']),
            'ra_mobile_phone' => (string)encrypt($data['ra_mobile_phone']),
            'ra_post_code' => (string)$data['ra_post_code'],
            'm_id' => $data['m_id'],
            'ra_create_time' => date('Y-m-d H:i:s')
        );
		if(trim($data['ra_phone'])!=''){
			$ary_data['ra_phone']=(string)encrypt($data['ra_phone']);
		}
		if(!empty($data['ra_is_default']) && 1 == $data['ra_is_default']){
			$ary_data['ra_is_default']=1;
		}
		$ra_id = $this->add($ary_data);
		if(!$ra_id){
			return $this->result('新增收货地址失败！', 'ReceiveAddressModel_doAdd_02');
		}
		return $this->result('新增收货地址成功！','',true,$ra_id);
	}
	
	/**
	 * 保存编辑
	 * @date 2014-4-8
	 * @param array $data
	*/
	public function doEdit($data = array()) {
		$ary_return = $this->field("ra_id,m_id")->where(array('ra_id'=>$data['ra_id'],'m_id'=>$data['m_id']))->find();
		if(!$ary_return){
			return $this->result('此条记录已不存在！','ReceiveAddressModel_doedit_01');
		}
		//原有默认值更新为否
		$save = $this->where(array('m_id'=>$ary_return['m_id']))->save(array('ra_is_default'=>0));
		if(FALSE === $save){
			return $this->result('更新默认值失败！', 'ReceiveAddressModel_doeidt_02');
		}
		$return = $this->where(array('ra_id'=>$data['ra_id']))->save($data);
		if(FALSE === $return){
			return $this->result('更新数据失败！', 'ReceiveAddressModel_doedit_03');
		}
		return $this->result('保存收货地址成功！','',true);
	}
	
	/**
	 * 异步删除收货地址
	*/
	public function doDel($ra_id) {
		$int_ra_id = $this->where(array('ra_id'=>$ra_id))->getField();
		if(!$int_ra_id){
			return $this->result('此条记录已不存在！', 'ReceiveAddressModel_doDel_001');
		}
		$return = $this->where(array('ra_id'=>$ra_id))->save(array('ra_status'=>2));
		if(!$return){
			return $this->result('删除失败！', 'ReceiveAddressModel_doDel_002');
		}
		return $this->result('删除成功！', '', true);
	}

    /**
     * 保存收货地址
     * @author Jerry(zhanghao)
     * @param array $data 地址信息
     * @date 2014-6-17
     */
    public function saveAddress($data) {
        $now = date("Y-m-d H:i:s");
        $ary_data = array(
            'ra_name' => $data['ra_name'],
            'cr_id' => (int)$data['cr_id'],
            'ra_detail' => $data['ra_detail'],
            'ra_phone' => (string)encrypt($data['ra_phone']),
            'ra_mobile_phone' => (string)encrypt($data['ra_mobile_phone']),
            'ra_post_code' => (string)$data['ra_post_code'],
            'm_id' => $data['m_id'],
            'ra_update_time' => $now
        );
        if(isset($data['ra_is_default'])){
            $ary_data['ra_is_default'] = max($data['ra_is_default'],0) >= 1 ? 1 : 0;
        }
        if(1 == $ary_data['ra_is_default']){
            $cancel_data['ra_is_default'] = 0;
            $this->where(array('m_id' => $data['m_id']))->data($cancel_data)->save();
        }
        if(isset($data['ra_id']) && $data['ra_id'] > 0) {
            $ary_data['ra_id'] = (int)$data['ra_id'];
            return $this->save($ary_data);
        } else {
            $ary_data['ra_create_time'] = $now;
            return $this->add($ary_data);
        }
    }
}
