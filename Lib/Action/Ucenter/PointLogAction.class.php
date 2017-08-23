<?php

/**
 * 我的积分Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.1
 * @author czy
 * @date 2013-4-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PointLogAction extends CommonAction {
      /**
     * 订单控制器初始化
     * @author czy  <chenzongyao@guanyisoft.com>
     * @date 2013-4-17
     */
    public function _initialize() {
        parent::_initialize();
       
    }
    /**
     * 订单控制器默认页
     * @author czy  <chenzongyao@guanyisoft.com>
     * @date 2013-4-17
     * @todo 此处需要跳转到快速选货页面
     */
    public function index() {
        $this->getSubNav(4, 3, 85);
        $this->display();
        $this->redirect(U('Ucenter/PointLog/pageList/'));
    }

     /**
     * @param 积分日志页面
     * @author czy <chenzongyao@guanyisoft.com>
     * @version 7.1
     * @since stage 1.5
     * @modify 2013-4-17
     * @return mixed array
     */
    
    public function pageList(){
        $this->getSubNav(4, 3, 85);
        $ary_post_data = $this->_post();
        $where = array('m_id'=>$_SESSION['Members']['m_id']);
        if(isset($ary_post_data['c_start_time']) && $ary_post_data['c_start_time'] !=''){
            $where['u_create'][] = array('EGT',$ary_post_data['c_start_time']);
        }
        
        if(isset($ary_post_data['c_end_time']) && $ary_post_data['c_end_time'] !=''){
            $where['u_create'][] = array('ELT',$ary_post_data['c_end_time']);
        }
        
        
        $count = D('PointLog')->where($where)->count();
        
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = 20;
        $count =  D('PointLog')->where($where)->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
		$where['page']     = $page_no;
		$where['pagesize'] = $page_size;
		$ary_pointlog_res = D('PointLog')->getPointLog($where);
		if($ary_pointlog_res['status']==true){
			$ary_pointlog =$ary_pointlog_res['data'];
		}
        $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        //print_r($ary_point);exit;
        $valid_point = 0;//有用积分数
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
        $ary_member_fields=M('MembersFields',C('DB_PREFIX'),'DB_CUSTOM')->where(array('is_need'=>0,'is_display'=>1,'if_status'=>1,'filelds_point'=>array('neq',0)))->select();
        $this->assign('members_fields',$ary_member_fields);

        $this->assign('valid_point',$valid_point);//积分总和
        $this->assign('int_nstart',$count);//总页数
        $this->assign('page',$page);
        $this->assign('ary_pointlog',$ary_pointlog);
        $this->display();
    }

    /**
     * 会员中心积分兑换金币
     * @author Hcaijin <Huangcaijin@guanyisoft.com>   
     * @date 2014-08-12
     */
    public function pagePointToJlb(){
        $this->getSubNav(4, 3, 85);
        
        D('SysConfig')->getCfg('JIULONGBI_MONEY_SET','JIULONGBI_AUTO_OPEN','1','是否启用金币功能');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] != 1){
            $this->error('暂未开启预存款提现功能，敬请期待');
        }
        $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        $valid_point = 0;//可用积分数
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
        $members = D("Members")->getInfo($_SESSION["Members"]["m_name"]);
        $jlbNums = $valid_point/$ary_jlb_data['point_proportion'];
        $this->assign('members', $members);
        $this->assign('valid_point', $valid_point);
        $this->assign('jlb', $jlbNums);
        $this->display();
    }

    /**
     * 积分兑换金币生成调整单
     * @author Hcaijin
     * @date 2014-08-12
     */
    public function doAddPointToJlb(){
        $ary_post = $this->_post();
        $ary_member = D("Members")->getInfo($_SESSION["Members"]["m_name"]);
        D('SysConfig')->getCfg('JIULONGBI_MONEY_SET','JIULONGBI_AUTO_OPEN','1','是否启用金币功能');
        $ary_jlb_data = D('SysConfig')->getCfgByModule('JIULONGBI_MONEY_SET');
        if($ary_jlb_data['JIULONGBI_AUTO_OPEN'] == 1 && $ary_jlb_data['point_proportion'] > 0){
            if(!empty($ary_post) && is_array($ary_post) && $ary_post['jlb'] > 0){
                $valid_point=intval($ary_member['total_point']-$ary_member['freeze_point']);
                $total_jlb=$valid_point/$ary_jlb_data['point_proportion'];
                if($ary_post['jlb'] <= $total_jlb){
                    D('')->startTrans();
                    $arr_jlb = array(
                        'jt_id' => '1',
                        'm_id'  => $ary_member['m_id'],
                        'ji_create_time'  => date("Y-m-d H:i:s"),
                        'ji_type' => '0',
                        'ji_money' => $ary_post['jlb'],
                        'ji_desc' => '积分兑换金币获得;'.$ary_post['point_desc'],
                        'ji_finance_verify' => '1',
                        'ji_service_verify' => '1',
                        'ji_verify_status' => '1',
                        'single_type' => '2'
                    );
                    $res_jlb = D('JlbInfo')->addJlb($arr_jlb);
                    $points = $ary_post['jlb']*$ary_jlb_data['point_proportion'];
                    $res_point = D('PointConfig')->setMemberPointToJlb($points,$ary_member['m_id']);
                    if($res_jlb && $res_point['result']){
                        D('')->commit();
                        $this->success("兑换成功");
                    }else{
                        D('')->rollback();
                        $this->error("兑换失败");
                    }
                }else{
                    $this->error("兑换的金币数不能大于可兑换的数量");
                }
            }else{
                $this->error("数据有误,请重新输入");
            }
        }else{
            $this->error("未开启兑换金币功能！");
        }
    }
}
?>
