<?php
/**
 * 会员地址接口API
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-11-03
 */
Class ApiMemberAddressModel extends GyfxModel{

	private $result = array(
        'code'    => '10402',       // 会员错误初始码
        'sub_msg' => '会员地址API错误', // 错误信息
        'status'  => false,         // 返回状态 : false 错误,true 操作成功.
        'info'    => array(),       // 正确返回信息
        );

    public function __construct() {
     	parent::__construct();
    }

    /**
     * 获取会员地址API (Home/OrdersAction.class.php中的 orderConfirm 方法)
     * @param (array)$params
     * @example $params = array(
     *          (int)'m_id' => 19
     * );
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-3
     */
    public function getMemberAddress($params){
    	$m_id = $params['m_id'];
        $result = $this->getMemberAddressList($m_id);
        if(empty($result)){
            $this->result['sub_msg'] = '无地址数据';
        }else{
            $this->result['code'] = 10403;
            $this->result['info'] = $result;
            $this->result['status'] = true;
            $this->result['sub_msg'] = '获取成功';
        }
        return $this->result;
    }

    /**
     * 获取会员的收货地址列表
     * @author Jerry(zhanghao)
     * @modify by Tom <helong@guanyisoft.com>
     * @param int $m_id 会员id
     * @param int $limit 返回多少条地址
     * @date 2014-6-11
     */
    public function getMemberAddressList($m_id) {
        $list = D('ReceiveAddress')->where(array('m_id'=>$m_id,'ra_status'=>1))->order('ra_update_time desc')->select();
        if(is_array($list)) {
            $obj_city_region = D('CityRegion');
            foreach ($list as &$address) {
                $native_place = $obj_city_region->parseRegion($address['cr_id']);
                $address = array_merge($address,$native_place);
                $address['full_name'] = $obj_city_region->getFullAddressName($address['cr_id']);
                if($address['ra_mobile_phone'] && strpos($address['ra_mobile_phone'],':')){
                    $address['ra_mobile_phone'] = decrypt($address['ra_mobile_phone']);
                }
                if($address['ra_phone'] && strpos($address['ra_phone'],':')){
                     $address['ra_phone'] = decrypt($address['ra_phone']);
                }
            }
        }
        return empty($list) ? array() : $list;
    }

    /**
     * 新增会员地址API
     * @param (array) $params
     * @example $params = array();
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-3
     */
    public function InsertMemberAddress($params){
    	$result = D('ReceiveAddress')->saveAddress($params);
    	if(empty($result)){
    		$this->result['sub_msg'] = '新增收货地址失败!';
    	}else{
    		$this->result['code'] = 10403;
            $this->result['info'] = $this->getMemberAddressList($params['m_id']);
            $this->result['status'] = true;
            $this->result['sub_msg'] = '新增成功!';
    	}
    	return $this->result;
    }

    /**
     * 修改会员地址API
     * @param (array) $params
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-3
     */
    public function EditMemberAddress($params){
        if($params['ra_is_default'] == 1){
            D('ReceiveAddress')->where(array('m_id'=>$params['m_id']))->save(array('ra_is_default'=>0));
        }
    	$result = D('ReceiveAddress')->saveAddress($params);
    	if(empty($result)){
    		$this->result['sub_msg'] = '修改收货地址失败!';
    	}else{
    		$this->result['code'] = 10403;
            $this->result['info'] = $this->getMemberAddressList($params['m_id']);
            $this->result['status'] = true;
            $this->result['sub_msg'] = '修改成功!';
    	}
    	return $this->result;
    }

    /**
     * [删除会员地址]
     * @param [type] $params [description]
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-6
     */
    public function DeleteMemberAddress($params){
        $result = D('CityRegion')->doDelDeliver($params['ra_id']);
        if(empty($result)){
            $this->result['sub_msg'] = '删除收货地址失败!';
        }else{
            $this->result['code'] = 10403;
            $this->result['info'] = $this->getMemberAddressList($params['m_id']);
            $this->result['status'] = true;
            $this->result['sub_msg'] = '删除成功!';
        }
        return $this->result;
    }

    /**
     * 获取区域地址
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-3
     */
    public function GetCityRegion($params){
		$cr_id  = $params['cr_id'];
		$result = D('CityRegion')->getSubCity($cr_id);
		$result = empty($result) ? array() : $result;
		$this->result['code'] = 10403;
        $this->result['info'] = $result;
        $this->result['status'] = true;
        $this->result['sub_msg'] = '查询成功!';
        return $this->result;
    }

    /**
     * 获取验证码
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-3
     */
    public function GetMobileVerify($params){
    	$obj = new GetMobileVerify();
        $ary_return = $obj->getMobileVerify($params['mobile'], $params['click_num'], $params['m_id']);
        if($ary_return['status'] !== TRUE){
        	$this->result['sub_msg'] = $ary_return['info'];
        }else{
        	$result = D("MobileVerifycode")->getDataByMobile($params['mobile'], $params['m_id']);
        	$this->result['code'] = 10203;
            $this->result['info'] = $result;
            $this->result['status'] = true;
            $this->result['sub_msg'] = '验证码已发送到您的手机，30分钟内输入有效，请勿泄露!';
        }
        return $this->result;
    }

    /**
     * [修改密码]
     * @param [array] $params [description]
     * @example array(
     *          (int) 'm_id' => 用户ID (必填)
     *          (string) 'original_password' => 原密码 (必填)
     *          (string) 'new_password' => 新密码 (必填)
     * );
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-11-14
     */
    public function EditMemberPassword($params){
        $ary_where = array(
            'm_id'       => $params['m_id'],
            'm_password' => $params['original_password']
            );
        $ary_member = D('Members')->where($ary_where)->find();
        if(empty($ary_member)){
            $this->result['sub_msg'] = '原密码错误!';
            return $this->result;
        }
        $data = array(
            'm_password' => $params['new_password']
            );
        $where = array(
            'm_id' => $ary_member['m_id']
            );
        $tag = D('Members')->where($where)->save($data);
        if($tag !== false){
            $this->result['code'] = 10205;
            $this->result['info'] = 'success';
            $this->result['status'] = true;
            $this->result['sub_msg'] = '修改成功!';
        }else{
            $this->result['sub_msg'] = '修改失败!';
        }
        return $this->result;
    }

}