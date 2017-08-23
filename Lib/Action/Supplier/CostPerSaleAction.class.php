<?php
/**
 * Cps后台设置
 */

class CostPerSaleAction extends AdminAction {
    public function _initialize() {
        parent::_initialize();
        $this->log = new ILog('db');
        $this->setTitle(' - ' . L('MENU4_1'));
    }

    /**
     * 后台默认控制器，重定向到列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index() {
        $this->redirect(U('Admin/CostPerSale/pageSetting'));
    }

    public function pageSetting(){
        $this->getSubNav(8, 10, 10);
        $ary_data = D('SysConfig')->getCfgByModule('CPS_SET');
        $this->assign($ary_data);
        $this->display();
    }

    public function stationInfo(){
        $this->getSubNav(8, 10, 20);
        $domain ='http://'. $_SERVER['SERVER_NAME'];
        $CPS_51FANLI_URL = $domain.U('Home/Fanli/QueryData');
        $CPS_51SURPERFANLI_URL = $domain.U('Home/Fanli/Superfanli');
        $CPS_51LOGINFANLI_URL = $domain.U('Home/Fanli/login');

        $CPS_51BIGOU_URL = $domain.U('Home/Bigou/QueryData');
        $CPS_51LOGINBIGOU_URL = $domain.U('Home/Bigou/cpsRecord');

        $this->assign('CPS_51SURPERFANLI_URL',$CPS_51SURPERFANLI_URL);
        $this->assign('CPS_51FANLI_URL',$CPS_51FANLI_URL);
        $this->assign('CPS_51BIGOU_URL',$CPS_51BIGOU_URL);
        $this->assign('CPS_51LOGINFANLI_URL',$CPS_51LOGINFANLI_URL);
        $this->assign('CPS_51LOGINBIGOU_URL',$CPS_51LOGINBIGOU_URL);
        $this->display();
    }

    public function doSet(){
        $ary_post = $this->_post();
        foreach ($ary_post as $name=>$set_val){
            D('SysConfig')->setConfig('CPS_SET',$name,$set_val);
        }
        $this->success('保存成功');
    }

}