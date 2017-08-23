<?php
/**
 * 订单相关Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 1.0
 * @author listen
 * @date 2013-03-28
 * @license MIT
 */
class MyCouponAction extends CommonAction{
      /**
     * 订单控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     */
    public function _initialize() {
        parent::_initialize();
       
    }
    /**
     * 订单控制器默认页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-13
     * @todo 此处需要跳转到快速选货页面
     */
    public function index() {
        $this->getSubNav(4, 3, 80);
        $this->display();
        $this->redirect(U('Ucenter/MyCoupon/pageList/'));
    }
    /**
     * 我的优惠券列表
     * @author listen
     * @date 2013-03-28
     */
    public function pageList(){
        $this->getSubNav(4, 3, 80);
        //$type  0是全部优惠券，1未使用的优惠券，2已经使用的优惠券，3已经过期的优惠券
        $page_size = 5;
        $type = $this->_get('type');
        $ary_post_data = $this->_post();
        $ary_post_data['type'] = $type;
        $ary_post_data['page_size'] = $page_size;
        $ary_post_data['m_id'] = $_SESSION['Members']['m_id'];
        $ary_post_data['p'] = $this->_get('p');
        $data = D('Coupon')->couponList($ary_post_data);
        $ary_coupon = $data['list'];
        $count = $data['pagination']['page_count'];
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $this->assign('type',$type);
        $this->assign('page',$page);
        $this->assign('ary_coupon',$ary_coupon);
		$this->assign('ary_post_data',$ary_post_data);
        $this->display();
    }
}

?>
