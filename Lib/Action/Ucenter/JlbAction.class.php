<?php

/**
 * 会员中心金币相关控制器
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.6
 * @author Hcaijin <huangcaijin@guanyisoft.com>
 * @date 2014-07-15
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 *
 */
class JlbAction extends CommonAction {

    /**
     * 默认控制器，需要重定向到用户消费记录
     * @author Hcaijin 
     * @date 2014-04-15
     */
    public function index() {
        $this->redirect(U('Ucenter/Jlb/pageList'));
    }
    /**
     * 我的金币 - 金币收支明细
     * @author Hcaijin
     * @date 2014-08-04
     */
    public function pageList() {
        $this->getSubNav(4, 3, 88);
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
		$array_cond = array("ji_verify_status"=>array("eq",1));
		$array_cond["ji_create_time"] = array("between",array($start_time . ' 00:00:00',$end_time . ' 23:59:59'));
		
		//如果指定了类型ID
		if(isset($_GET["jt_id"]) && is_numeric($_GET["jt_id"]) && $_GET["jt_id"] > 0){
			$array_cond["jt_id"] = $_GET["jt_id"];
		}
		$member = $_SESSION['Members'];
		$array_cond["fx_jlb_info.m_id"] = $member['m_id'];

        // 订单号
        if (isset($_GET['o_id']) && $_GET['o_id'] != '') {
            $array_cond ['fx_orders.o_id'] = array(
                'EQ',
                $_GET['o_id']
            );
            //获取结余款调整单记录
            $int_count = D("JlbInfo")
                ->join('fx_orders on fx_orders.o_id=fx_jlb_info.o_id')
                ->where($array_cond)->count();
            $pageObj = new Page($int_count,20);
            $string_limit = $pageObj->firstRow . ',' . $pageObj->listRows;
            $array_jlb_info = D("JlbInfo")
                ->join('fx_orders on fx_orders.o_id=fx_jlb_info.o_id')
                ->where($array_cond)->order(array("ji_id"=>'desc'))->limit($string_limit)->select();
        }else{
            $int_count = D("JlbInfo")->where($array_cond)->count();
            $pageObj = new Page($int_count,20);
            $string_limit = $pageObj->firstRow . ',' . $pageObj->listRows;
            $array_jlb_info = D("JlbInfo")->where($array_cond)->order(array("ji_id"=>'desc'))->limit($string_limit)->select();
        }
        $array_cond["m_id"] = $_SESSION['Members']['m_id'];
		//获取会员信息
        $array_member_info = D("Members")->getInfo($_SESSION['Members']['m_name'],$_SESSION['Members']['open_id']);
		$_SESSION["Members"] = $array_member_info;
		//结余款调整类型获取
		$jlbtype = D("jlbType")->where(array("jt_status"=>1))->order(array("jt_orderby"=>"desc"))->select();
        
        $this->assign("member",$array_member_info);
        $this->assign("jlbtype",$jlbtype);
        $this->assign("datalist",$array_jlb_info);
        $this->assign("start_time",$start_time);
        $this->assign("end_time",$end_time);
        $this->assign("page",$pageObj->show());
        $this->display();
    }

    /**
     * 会员中心金币兑换积分
     * @author Hcaijin <Huangcaijin@guanyisoft.com>   
     * @date 2014-08-11
     */
    public function pageJlbToPoint(){
        $this->getSubNav(4, 3, 88);
        
        D('SysConfig')->getCfg('JIULONGBI_MONEY_SET','JIULONGBI_AUTO_OPEN','1','是否启用金币功能');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] != 1){
            $this->error('暂未开启预存款提现功能，敬请期待');
        }
        $members = D("Members")->getInfo($_SESSION["Members"]["m_name"]);
        $pointNums = round($members['m_jlb']*$ary_jlb_data['point_proportion']);
        $this->assign('members', $members);
        $this->assign('point', $pointNums);
        $this->display();
    }

    /**
     * 金币兑换积分生成调整单
     * @author Hcaijin
     * @date 2014-08-12
     */
    public function doAddJlbToPoint(){
        $ary_post = $this->_post();
        $ary_member = D("Members")->getInfo($_SESSION["Members"]["m_name"]);
        D('SysConfig')->getCfg('JIULONGBI_MONEY_SET','JIULONGBI_AUTO_OPEN','1','是否启用金币功能');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] == 1 && $ary_jlb_data['point_proportion'] > 0){
            if(!empty($ary_post) && is_array($ary_post) && $ary_post['points'] > 0){
                $total_point = round($ary_member['m_jlb']*$ary_jlb_data['point_proportion']);
                if($ary_post['points']<$total_point){
                    D('')->startTrans();
                    $jlb_price = $ary_post['points']/$ary_jlb_data['point_proportion'];
                    $arr_jlb = array(
                        'jt_id' => '2',
                        'm_id'  => $ary_member['m_id'],
                        'ji_create_time'  => date("Y-m-d H:i:s"),
                        'ji_type' => '1',
                        'ji_money' => $jlb_price,
                        'ji_desc' => $ary_post['ji_desc'],
                        'ji_finance_verify' => '1',
                        'ji_service_verify' => '1',
                        'ji_verify_status' => '1',
                        'single_type' => '2'
                    );
                    $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                    $res_point = D('PointConfig')->setMemberJlbToPoint($ary_post['points'],$ary_member['m_id']);
                    if($res_jlb && $res_point['result']){
                        D('')->commit();
                        $this->success("兑换成功");
                    }else{
                        D('')->rollback();
                        $this->error("兑换失败");
                    }
                }else{
                    $this->error("兑换的积分数不能大于总兑换的积分");
                }
            }else{
                $this->error("数据有误,请重新输入");
            }
        }else{
            $this->error("未开启兑换金币功能！");
        }
    }
}

