<?php

/**
 * 会员中心储值卡相关控制器
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.6
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2014-07-15
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 *
 */
class CardsAction extends CommonAction {

    /**
     * 默认控制器，需要重定向到用户消费记录
     * @author Hcaijin 
     * @date 2014-04-15
     */
    public function index() {
        $this->redirect(U('Ucenter/Cards/pageList'));
    }
    /**
     * 我的储值卡 - 储值卡收支明细
     * @author Hcaijin
     * @date 2014-04-15
     */
    public function pageList() {
        $this->getSubNav(4, 3, 87);
		//查询条件，默认的开始和结束时间（默认显示30天以内的）
		$start_time = date("Y-m-d",time()-30*24*60*60);
		$end_time = date("Y-m-d");
		if(isset($_GET["starttime"]) && preg_match("/^\d{4}-\d{2}-\d{2}/s",$_GET["starttime"])){
			$start_time = $_GET["starttime"];
		}
		if(isset($_GET["endtime"]) && preg_match("/^\d{4}-\d{2}-\d{2}/s",$_GET["endtime"])){
			$end_time = $_GET["endtime"];
		}
		
		//只显示已审核的记录
		$array_cond = array("ci_verify_status"=>array("eq",1));
		$array_cond["ci_create_time"] = array("between",array($start_time . ' 00:00:00',$end_time . ' 23:59:59'));
		
		//如果指定了类型ID
		if(isset($_GET["ct_id"]) && is_numeric($_GET["ct_id"]) && $_GET["ct_id"] > 0){
			$array_cond["ct_id"] = $_GET["ct_id"];
		}
		$member = $_SESSION['Members'];
		$array_cond["fx_cards_info.m_id"] = $member['m_id'];

        // 订单号
        if (isset($_GET['o_id']) && $_GET['o_id'] != '') {
            $array_cond ['fx_orders.o_id'] = array(
                'EQ',
                $_GET['o_id']
            );
            //获取结余款调整单记录
            $int_count = D("CardsInfo")
                ->join('fx_orders on fx_orders.o_id=fx_cards_info.o_id')
                ->where($array_cond)->count();
            $pageObj = new Page($int_count,20);
            $string_limit = $pageObj->firstRow . ',' . $pageObj->listRows;
            $array_cards_info = D("CardsInfo")
                ->join('fx_orders on fx_orders.o_id=fx_cards_info.o_id')
                ->where($array_cond)->order(array("ci_id"=>'desc'))->limit($string_limit)->select();
        }else{
            $int_count = D("CardsInfo")->where($array_cond)->count();
            $pageObj = new Page($int_count,20);
            $string_limit = $pageObj->firstRow . ',' . $pageObj->listRows;
            $array_cards_info = D("CardsInfo")->where($array_cond)->order(array("ci_id"=>'desc'))->limit($string_limit)->select();
        }
        $array_cond["m_id"] = $_SESSION['Members']['m_id'];
		//获取会员信息
        $array_member_info = D("Members")->getInfo($_SESSION['Members']['m_name'],$_SESSION['Members']['open_id']);
		$_SESSION["Members"] = $array_member_info;
		//结余款调整类型获取
		$cardstype = D("cardsType")->where(array("ct_status"=>1))->order(array("ct_orderby"=>"desc"))->select();
        
        $this->assign("member",$array_member_info);
        $this->assign("cardstype",$cardstype);
        $this->assign("datalist",$array_cards_info);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time);
        $this->assign("page",$pageObj->show());
        $this->display();
    }
}

