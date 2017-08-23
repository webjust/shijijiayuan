<?php

/**
 * 前台模版抽奖页生成
 *
 * @package Action
 * @subpackage Home
 * @stage 7.6.1
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-07-21
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class LotteryAction extends HomeAction {

    protected $dir = '';

    public function _initialize() {
        parent::_initialize();
        
    }

    /**
     * 客户模版默认抽奖首页
     * @author wangguibin@wangguibin@guanyisoft.com
     * @date 2014-07-21
     */
    public function index() {
		$m_id = $_SESSION['Members']['m_id']; 
		if(empty($m_id)){
			$int_port = "";
			if($_SERVER["SERVER_PORT"] != 80){
				$int_port = ':' . $_SERVER["SERVER_PORT"];
			}
			$string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			//$this->redirect(U('Home/User/login'), array('redirect_uri' => urlencode($string_request_uri)), 1, '页面跳转中...');
			$this->error('会员需要登陆之后才能参与抽奖的吆',U('Home/User/login') . '?redirect_uri=' . urlencode($string_request_uri));exit;
		}
        $this->setTitle('抽奖页');
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/lottery.html';
        } else {
            $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/lottery.html';
        }
		$l_id = $this->_param('id');
		if(empty($l_id)){
			//获取最新的一个抽奖信息
			$ary_lottery = M('lottery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('is_deleted'=>0,'l_status'=>1,'l_start_time'=>array('elt',date('Y-m-d H:i:s')),'l_end_time'=>array('egt',date('Y-m-d H:i:s'))))->find();
			//echo M('lottery',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
		}else{
			$ary_lottery = M('lottery',C('DB_PREFIX'),'DB_CUSTOM')->where(array('l_id'=>$l_id))->find();
		}
		$ary_lottery['l_detail'] = unserialize($ary_lottery['l_detail']);
		$this->assign('ary_lottery',$ary_lottery);
		//获取抽奖列表
		$ary_lotterys = M('lottery_user',C('DB_PREFIX'),'DB_CUSTOM')->join('fx_members as m on(m.m_id=fx_lottery_user.m_id)')->where(array('l_id'=>$ary_lottery['l_id'],'is_used'=>'1'))->field('l_id,m.m_id,ul_type,ul_bonus_money,ul_title,ul_confirm_time,m.m_name')->order(array('ul_confirm_time'=>'desc'))->limit(7)->select();
		$this->assign('ary_lotterys',$ary_lotterys);
		$this->assign('l_id',$ary_lottery['l_id']);
        $this->display($tpl);
    }
   

	/**
     * 获取中奖会员信息列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @version 7.6.1
     * @date 2014-07-22
     *
     */
    public function LotteryList() {
		$l_id = intval($this->_request('l_id'));
		//热销商品不存在去最热的商品
		if(!empty($l_id)){
			//获取抽奖列表
			$ary_lotterys = M('lottery_user',C('DB_PREFIX'),'DB_CUSTOM')->join('fx_members as m on(m.m_id=fx_lottery_user.m_id)')->where(array('l_id'=>$l_id,'is_used'=>'1'))->field('l_id,m.m_id,ul_type,ul_bonus_money,ul_title,ul_confirm_time,m.m_name')->order(array('ul_confirm_time'=>'desc'))->limit(7)->select();
			$this->assign('ary_lotterys',$ary_lotterys);
		}
		$ary_request['l_id'] = $l_id;
        $this->assign('ary_request',$ary_request);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/lottery_list.html';
        $this->display($tpl);
    }
	
}