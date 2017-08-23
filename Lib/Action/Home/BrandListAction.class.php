<?php
class BrandListAction extends HomeAction {
	
	/**
     * 版本升级品牌列表页
     * @author lixiaolong <lixiaolong@guanyisoft.com>
     * @date 2014-09-19
     */
    public function _initialize() {
        parent::_initialize();
    }

	public function index(){
		//显示页面
        $ary_request = $this->_request();		
		$this->setTitle($ary_request['name']);
		$is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }		
		 if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/brandList.html';
        } else {
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/brandList.html';
        }		
		$this->display($tpl);				
	}
}