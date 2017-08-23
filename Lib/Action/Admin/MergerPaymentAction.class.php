<?php
/**
 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
 * @date 2014-5-12
*/
class MergerPaymentAction extends AdminAction{

	/**
     * 控制器初始化操作
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-18
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU3_0'));
    }

    /**
     * 默认控制器，重定向到订单列表页
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-18
     */
    public function index() {
        $this->redirect(U('Admin/MergerPayment/pageList'));
    }
	
	/**
	 * 合并支付列表
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	 * @date 2014-5-12
	*/
	public function pageList() {
		$this->getSubNav(4, 0, 50);
		$ary_get = $this->_get();
		// $mp_id = $this->_get('mp_id');
		// $sort = $this->_get('sort');
		$group = "mp_id";
		$where = array();
		if(isset($ary_get['mp_id']) && !empty($ary_get['mp_id'])){
			$where['mp_id'] = array("EQ", $ary_get['mp_id']);
		}
		$order = '';
		if(isset($ary_get['sort']) && !empty($ary_get['sort']) && in_array($ary_get['sort'],array('createTime_DESC','createTime_ASC'))){
			$createTime = str_replace("createTime_"," ",$ary_get['sort']);
			$order = 'mp_create_time '.$createTime;
		}
		$ary_result = D("MergerPayment")->distinct(true)->where($where)->order($order)->group($group)->select();
		$count = count($ary_result);
		if(0 < $count){
			$obj_page = new Page($count, 20);
			$page = $obj_page->show();
			$limit = $obj_page->firstRow . ',' . $obj_page->listRows;
			$ary_data = D("MergerPayment")->distinct(true)->where($where)->order($order)->group($group)->limit($limit)->select();
		}
		$this->assign('filter',$ary_get);
		$this->assign('data',$ary_data);
		$this->assign('page',$page);
		$this->display();
	}
}