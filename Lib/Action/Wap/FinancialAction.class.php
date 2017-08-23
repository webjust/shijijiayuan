<?php

/**
 * 前台财务相关控制器
 *
 * @package Action
 * @subpackage Wap
 * @stage 7.8.2
 * @author hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2015-03-31
 * @copyright Copyright (C) 2015, Shanghai GuanYiSoft Co., Ltd.
 *
 */
class FinancialAction extends WapAction {

    public function _initialize() {
        parent::_initialize();
        $m_id = $_SESSION['Members']['m_id'];
        if(empty($m_id) && !isset($m_id)){
            $string_request_uri = "http://" . $_SERVER["SERVER_NAME"] . $int_port . $_SERVER['REQUEST_URI'];
			$this->error(L('NO_LOGIN'), U('/Wap/User/Login') . '?redirect_uri=' . urlencode($string_request_uri));
        }
    }

    /**
     * 默认控制器，需要重定向到用户消费记录
     * @author hcaijin 
     * @date 2015-03-31
     */
    public function index() {
        $this->redirect(U('Wap/Financial/pageDepositList'));
    }

    /**
     * 我的预存款 - 预存款收支明细
     * @author hcaijin
     * @date 2015-03-31
     */
    public function pageDepositList() {
        $member = D("Members");
        $me = session("Members");
        if($me['open_id'] != '' && $me['login_type'] == 1){
            $mem = $member->getInfo('',$me['open_id']);
            $mem['m_name'] = $mem['open_name'];
            if($mem['m_status'] == 0){
                $this->error('您还未设置账户名，为了您的账号安全，请设置账号名称和密码','/Wap/My/setThdMembers/');
            }
            session("Members", $mem);
        }else{
            $mem=D('MembersVerify')->where(array('m_id'=>$me['m_id']))->find();
            if(empty($mem) && !is_array($mem)){
                $mem=D('Members')->where(array('m_id'=>$me['m_id']))->find();
            }else{
                $sys_data=D('Members')->where(array('m_id'=>$me['m_id']))->find();
                $mem['ml_id']	 =$sys_data['ml_id'];
                $mem['m_balance']=$sys_data['m_balance'];
                $mem['m_card_no']=$sys_data['m_card_no'];
                $mem['m_ali_card_no']=$sys_data['m_ali_card_no'];
            }
            session("Members", $mem);
        }
		//只显示已审核的记录
		$array_cond = array("bi_verify_status"=>array("eq",1));
		//如果指定了类型ID
		if(isset($_GET["bt_id"]) && is_numeric($_GET["bt_id"]) && $_GET["bt_id"] > 0){
			$array_cond["bt_id"] = $_GET["bt_id"];
		}
		$array_cond["fx_balance_info.m_id"] = $_SESSION['Members']['m_id'];
        //获取结余款调整单记录
        $int_count = D("BalanceInfo")->where($array_cond)->count();
        $pageObj = new Page($int_count,20);
        $string_limit = $pageObj->firstRow . ',' . $pageObj->listRows;
        $array_balance_info = D("BalanceInfo")->where($array_cond)->order(array("bi_id"=>'desc'))->limit($string_limit)->select();
		//获取会员信息
        $array_member_info = D("Members")->getInfo($_SESSION['Members']['m_name'],$_SESSION['Members']['open_id']);
		$_SESSION["Members"] = $array_member_info;
		//结余款调整类型获取
		//$balancetype = D("BalanceType")->where(array("bt_status"=>1))->order(array("bt_orderby"=>"desc"))->select();
        $ary_pay = array();
        $ary_income = array();
        foreach($array_balance_info as $key => $val){
            if($val['bt_id'] == 1 || $val['bt_id'] == 4){
                $ary_pay[] = $val;
            }else{
                $ary_income[] = $val;
            }
        }
        
        $this->assign("member",$array_member_info);
        $this->assign("datalist",$array_balance_info);
        $this->assign("ary_pay",$ary_pay);
        $this->assign("ary_income",$ary_income);
        $this->assign("page",$pageObj->show());
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }

}
