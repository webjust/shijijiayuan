<?php
/**
 * 优惠券领取页面Action
 *
 * @package Action
 * @subpackage Home
 * @version 7.6
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2014-07-21
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CouponAction extends HomeAction {

    /**
     * 优惠券领取首页
     * @author Hcaijin  
     * @date 2014-07-21
     */
    public function index() {
        //可领取的优惠券异号活动列表
        $data = D('CouponActivities')->getOppSign();
        $this->assign($data);
        $this->setTitle('优惠券领取');
		//显示页面
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/coupon.html';
        if($this->isAjax()){
            $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/ajaxcoupon.html';
        }
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

