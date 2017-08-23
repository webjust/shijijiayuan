<?php
/**
 * Class CouponAction
 * 微商城 优惠券
 */
class CouponAction extends WapAction {

    /**
     * 控制器初始化
     */
    public function _initialize() {
        parent::_initialize();
    }


    public function couponList() {
        $ary_member = session('Members');
        $mid = 0;
        if(!empty($ary_member)) {
            $mid = $ary_member['m_id'];
        }else{
            $this->redirect(U('/Wap/User/login'). '?redirect_uri=' . urlencode( ltrim($_SERVER['REQUEST_URI'],'/')));
        }
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
        $this->assign('count',$count);
        $this->assign('ary_coupon',$ary_coupon);
        $this->assign('ary_post_data',$ary_post_data);

        $tpl = '';
        if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

	/**
     * 优惠券领取首页
     */
    public function index() {
        $ary_member = session('Members');
        $m_id = isset($ary_member['m_id']) ? $ary_member['m_id'] : 0;
        $data = D('CouponActivities')->getOppSign();
        $this->setTitle('优惠券领取');
        $this->assign($data);

//        echo'<pre>';var_dump($data);exit;
		//显示页面
        $tpl = $this->wap_theme_path . 'coupon.html';
        $this->display($tpl);
    }
	
	/**
     * 点击获取优惠券
     */
    public function getReceive(){
        $ary_member = session('Members');
        $mid = $ary_member['m_id'];
        if(isset($mid)){
            $arr_post = $this->_post();
            $cname = $arr_post['cname'];
            $result = D('CouponActivities')->doCollectCoupon($mid, $cname);
        }else{
            $result = array('status'=>0,'message'=>'用户没有登陆！');
        }
        $this->ajaxReturn($result);
    }

}
