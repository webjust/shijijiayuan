<?php
/**
 * 地址相关Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 1.0
 * @author Joe
 * @date 2012-12-20
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
 class AddressAction extends CommonAction {
    
    private $receiveAddr;
    
    /**
     * 地址控制器初始化
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function _initialize() {
        parent::_initialize();
        $this->cityRegion = D('CityRegion');
       
    }
    
    /**
     * 选择常用收货地址
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function checkReciveAddr() {
        $ra_id = $this->_post('ra_id');
        $cr_id = $this->_post('cr_id');
        $m_id = $_SESSION['Members']['m_id'];
        $this->ajaxReturn($this->cityRegion->checkReciveAddr($ra_id, $m_id,$cr_id));
        
    }
    
    /**
     * 显示更新收货地址页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function updateAddrPage() {
        $ra_id = $this->_post('ra_id');
         //是否显示粘贴收货地址
        $ary_is_show_address = D('SysConfig')->getCfgByModule('IS_SHOW_ADDRESS',1);
        $this->assign('is_show_address', $ary_is_show_address['IS_SHOW_ADDRESS']);
        $ary_addr = $this->cityRegion->getFindReciveAddr($ra_id);

		//解密显示手机和固话号码
		if($ary_addr['ra_mobile_phone'] && strpos($ary_addr['ra_mobile_phone'], ':')) {
			$ary_addr['ra_mobile_phone'] = decrypt($ary_addr['ra_mobile_phone']);
            if($ary_addr['ra_mobile_phone'] && strpos($ary_addr['ra_mobile_phone'], ':')) {
                $ary_addr['ra_mobile_phone'] = decrypt($ary_addr['ra_mobile_phone']);
            }
		}
		if($ary_addr['ra_phone'] && strpos($ary_addr['ra_phone'], ':')) {
			$ary_addr['ra_phone'] = decrypt($ary_addr['ra_phone']);
		}
		if($ary_addr['ra_id_card'] && strpos($ary_addr['ra_id_card'], ':')) {
			$ary_addr['ra_id_card'] = decrypt($ary_addr['ra_id_card']);
		}
        $ary_phone =explode("-",$ary_addr['ra_phone']);
        $ary_addr['ra_phone_area']=$ary_phone[0];
        $ary_addr['ra_phone']=$ary_phone[1];
        $ary_addr['ra_phone_ext']=$ary_phone[2];
        $ary_city_data = $this->cityRegion->getFullAddressId($ary_addr['cr_id']);
        $set_edit_js = "<script>
                        selectCityRegion('1','province','$ary_city_data[1]');
                        selectCityRegion('$ary_city_data[1]','city','$ary_city_data[2]');
                        selectCityRegion('$ary_city_data[2]','region','$ary_city_data[3]');
                        </script>";
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
        $this->assign($is_foreign);
        $this->assign('set_edit_js', $set_edit_js);
        $this->assign('addr', $ary_addr);
        $this->display();
    }

    /**
     * 修改收货地址
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function ajaxUpdateAddr() {
        $ary_update_data = array();
        $ary_addr = $this->_post();
        if($ary_addr['region']==0){
            $ary_addr['region']=$ary_addr['city'];
        }
        $ary_update_data['ra_id'] = $ary_addr['ra_id'];
        $ary_update_data['cr_id'] = $ary_addr['region'];
        $ary_update_data['ra_name'] = $ary_addr['ra_name'];
        $ary_update_data['ra_detail'] = $ary_addr['ra_detail'];
        $ary_update_data['ra_post_code'] = $ary_addr['ra_post_code'];
        $ary_update_data['ra_id_card'] = $ary_addr['ra_id_card'];
        if($ary_addr['ra_phone']){
            $ary_update_data['ra_phone'] = encrypt($ary_addr['ra_phone_area'].'-'.$ary_addr['ra_phone'].'-'.$ary_addr['ra_phone_ext']);
        }
        if($ary_addr['ra_mobile_phone'] && strpos($ary_addr['ra_mobile_phone'],'*')){
            unset($ary_addr['ra_mobile_phone']);
        }elseif(isset($ary_addr['ra_mobile_phone']) && !strpos($ary_addr['ra_mobile_phone'],'*')){
            $ary_update_data['ra_mobile_phone'] = $ary_addr['ra_mobile_phone'];
        }

        $ary_update_data['ra_update_time'] = date('Y-m-d H:i:s');
        $ary_return = $this->cityRegion->updateAddr($ary_update_data);
        $ary_data = $this->cityRegion->getReceivingAddress($_SESSION['Members']['m_id'],$this->_post('ra_id'));
        if($ary_data['ra_mobile_phone']){
            $ary_data['ra_mobile_phone'] = vagueMobile($ary_data['ra_mobile_phone']);
        }
        if($ary_data['ra_phone'] == false){
            $ary_data['ra_phone'] = '';
        }
        $ary_return['data'] = $ary_data;

        $this->ajaxReturn($ary_return);
    }
    
    /**
     * 添加常用收货地址页面
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function addAddrPage(){
         //是否显示粘贴收货地址
        $ary_is_show_address = D('SysConfig')->getCfgByModule('IS_SHOW_ADDRESS',1);
        $this->assign('is_show_address', $ary_is_show_address['IS_SHOW_ADDRESS']);
        $ary_addr = $this->cityRegion->getFindReciveAddr(1);
        $set_edit_js = "<script>
                            selectCityRegion('1','province','$ary_city_data[1]');
                            selectCityRegion('$ary_city_data[1]','city','$ary_city_data[2]');
                            selectCityRegion('$ary_city_data[2]','region','$ary_city_data[3]');
                            </script>";
		$is_foreign = D('SysConfig')->getCfg('GY_SHOP','GY_IS_FOREIGN');
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		$this->assign('is_zt',$is_zt['IS_ZT']['sc_value']);
        $this->assign($is_foreign);
        $this->assign('set_edit_js',$set_edit_js);
        $this->assign('HD',$this->_post('HD'));
        $this->display();
    }
    
    /**
     * 添加常用收货地址
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function ajaxAddAddr(){
        $m_id = $_SESSION['Members']['m_id'];
        $ary_post = $this->_post();
        $ary_result = $this->cityRegion->addReceiveAddr($ary_post,$m_id);
        $ary_data = $this->cityRegion->getReceivingAddress($m_id,$ary_result['data']['ra_id']);
        unset($ary_result['data']['ra_id']);
        $ary_result['data'] = $ary_data[0];
        //dump($ary_result);exit;
        $this->ajaxReturn($ary_result);
    }
    
    /**
     * 获取省市ID下所有区域的名称 （以HTML形式输出）
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function getCityRegion() {
        $parent = $this->_post('parent');
        $string_field = array('cr_id,cr_name');
        $city_region_data = $this->cityRegion->getParentsAddr($parent,$string_field,1);
        if (count($city_region_data) > 0) {
            $html = '<option value="" selected="selected">请选择</option>';
            foreach ($city_region_data as $item) {
                $html .= "<option value='{$item['cr_id']}'>{$item['cr_name']}</option>";
            }
        }else{
            $html = '';
        }
        echo $html;
        exit;
    }
    /**
     * 删除常用收货地址（逻辑删除）
     * @param $int_ra_id  需要删除的地址id
     * 
     */
    public function ajaxDeleteAddress(){
        $ary_post_data = $this->_post();
        $int_ra_id = $ary_post_data['ra_id'];
        $ary_delete_data = array('ra_id'=>$int_ra_id,'ra_status'=>2);
        $ary_return = $this->cityRegion->updateAddr($ary_delete_data);
        $this->ajaxReturn($ary_return);
    }
 }
