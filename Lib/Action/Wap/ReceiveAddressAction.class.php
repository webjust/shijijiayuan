<?php
/**
 * 收货地址控制器
*/
class ReceiveAddressAction extends WapAction {
	
    function _initialize() {
        $Member = session("Members");
        if(!$Member || !$Member['m_id']){
            $this->redirect(":U('/Home/User/Login')");
            die;
        }
        parent::_initialize();
    }
	/**
	 * 添加收货地址页面
	*/
	public function addAddressPage() {
        
		$this->setTitle("添加收货地址");
		$pids=$this->_get('pid');
		$this->assign("pids", $pids);
		$zt=$this->_get('zt');//自提
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT',null,null,1);
		if($zt==true && $is_zt['IS_ZT']['sc_value'] == 1 ){
			$this->assign("zt", $zt);
		}
        $ary_city = D('CityRegion')->getAllCitys(1);
		$this->assign("citys",$ary_city);
		$this->assign("m_id",$_SESSION['Members']['m_id']);
		//$tpl = $this->wap_theme_path . '/cart/addNewAddress.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}
	
	/**
	 * 收货地址列表
	*/
	public function addressListPage() {
		$this->setTitle("收货地址列表");
		
		$address = D("Orders")->getOrderConfirmInfo('address');
		$ary_return = D("ReceiveAddress")->getAddressList($_SESSION['Members']['m_id']);
		if(!empty($ary_return)){
			foreach($ary_return as $k=>$v){
				if($address['ra_id'] == $v['ra_id']){
					$ary_return[$k]['checked'] = 1;
				}
			}
		}
		$this->assign('address',$ary_return);
		//$tpl = $this->wap_theme_path . '/cart/myAddress.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}
	
    public function doUseAddress(){
        
    }


    /**
	 * 编辑收货地址
	*/
	public function editAddressPage() {
		$this->setTitle("编辑收货地址");
		$ra_id = $this->_request('ra_id');
		if(empty($ra_id)){
			$this->error("地址已不存在！");
		}
		$ary_list = D("ReceiveAddress")->getAddressByraid($ra_id);
		$ary_city = D('CityRegion')->getAllCitys(1);
		$this->assign("citys",$ary_city);
		$this->assign('address', $ary_list);
		//$tpl = $this->wap_theme_path . '/cart/editAddressPage.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
	}
	
	/**
	 *执行添加
	*/
	public function doAdd() {
		$ary_post = $this->_post();
		$this->checkForm($ary_post);
		trim($ary_post['ra_name']);
		$str_mobile = '';
        $isMobile = $this->_post('ra_phone_area');
        if (!empty($isMobile)) {
            $isMobile_2 = $this->_post('ra_phone');
            $str_mobile .=$isMobile;
            if (!empty($isMobile_2)) {
                $str_mobile .= "-" . $isMobile_2;
            }
        }
		unset($ary_post['ra_phone_area']);
		if($str_mobile!=''){
			$ary_post['ra_phone']=$str_mobile;
		}
		if(!empty($ary_post)){
			$return = D("ReceiveAddress")->doAdd($ary_post);
			$this->ajaxReturn($return);
		}
	}
    
    /**
	 * 设为默认
	*/
	public function saveAsDefault() {
		$ary_get = $this->_get();
		if(!empty($ary_get)){
            $ary_get['ra_is_default'] = 1;
            $Member = session("Members");
            if(!$Member['m_id']){
                $this->ajaxReturn(0,array('info'=>"无操作权限"),0);
                return ;
            }
            $ary_get['m_id'] = $Member['m_id'];
			$return = D("ReceiveAddress")->doEdit($ary_get);
            $order_confirm_info = D('Orders')->getOrderConfirmInfo('address');
            if(!empty($order_confirm_info) && $order_confirm_info['ra_id'] == $ary_get['ra_id']){
                $config = session('OrderConfirmInfo');
                $config['address']['ra_is_default'] = 1;
                session('OrderConfirmInfo', $config);
            }
			$this->ajaxReturn($return);
		}else{
            $this->ajaxReturn("未知的地址ID");
        }
	}
	
	/**
	 * 保存编辑
	*/
	public function doEdit() {
		$ary_post = $this->_post();
		$this->checkForm($ary_post);
		trim($ary_post['ra_name']);
		if(!empty($ary_post)){
			$return = D("ReceiveAddress")->doEdit($ary_post);
			$this->ajaxReturn($return);
		}
	}
	
	/**
	 * 表单验证
	 * @date 2014-4-8
	 * @param array $ary_data
	*/
	public function checkForm($ary_data) {
		if(empty($ary_data['ra_name'])){
			$this->error('收货人不能为空！');
		}
		if(empty($ary_data['ra_mobile_phone'])){
			$this->error('收货人手机号不能为空！ ');
		}
		if(empty($ary_data['cr_id'])){
			$this->error('收货地址不能为空！');
		}
	}
	
	/**
	 * 删除地址
	*/
	public function doDel(){
		$ra_id = $this->_post('ra_id');
		$ary_return = D("ReceiveAddress")->doDel($ra_id);
		$this->ajaxReturn($ary_return);
	}
	
	/**
	 * 异步获取地区信息
	*/
    public function getCityRegion() {
        $parent = $this->_post('parent');
        $item = $this->_post('item');
        $val = $this->_post('val');
        $ary_city = D("CityRegion")->getAllCitys($parent);
        if (!empty($ary_city) && is_array($ary_city)) {
            $str = '';
            if ($item == 'city') {
                $str = "onchange=\"selectCityRegion(this, 'region','')\";";
            }
			if($item == 'region'){
				$str = "onchange=\"selectCityRegion(this, '','')\";";
			}
            $html = "<select id='" . $item . "' name='" . $item . "' {$str}>";
            $html .= '<option value="0" selected="selected">请选择</option>';
            if (count($ary_city) > 0) {
                foreach ($ary_city as $item) {
                    if ($item['cr_id'] == $val) {
                        $html .= "<option value='{$item['cr_id']}' item='1' selected='selected'>{$item['cr_name']}</option>";
                    } else {
                        $html .= "<option id='option_add_{$item['cr_id']}' value='{$item['cr_id']}' >{$item['cr_name']}</option>";
                    }
                }
            }
            $html .= "</select>";
        } else {
            $html = '';
        }
        echo $html;
        exit;
    }
	
	/**
	 * 获取会员信息
	*/
	function checkMemberIsLogin() {
		$_SESSION['Members']['m_id'] = $this->getMemberInfo('m_id');
		if(empty($_SESSION['Members']['m_id'])){
			$this->redirect(":U('/Home/User/Login')");
		}
		return $_SESSION['Members']['m_id'];
	}
}