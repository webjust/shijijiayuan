<?php
/**
 * 分销商等级模型
 * @package Model
 * @version 7.0
 * @author Terry
 * @date 2013-2-1
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersLevelModel extends GyfxModel {
    /**
     * 构造方法
     @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 获取会员等级信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function getMembersLevels($ml_id = '') {
        if(empty($ml_id)){
            return M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where()->select();
        }else{
            return M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_id' => $ml_id))->find();
        }
        
    }
    
    public function getByMlGuid($guid = '') {
        if(empty($guid)) return null;
        else return M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_erp_guid'=>$guid))->select();
    }
    
    /**
     * 得到会员默认等级
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-05-14
     */
    public function getSelectedLevel(){
    	$data =  M('members_level',C('DB_PREFIX'),'DB_CUSTOM')->field('ml_id')->where(array('ml_default' => '1'))->find();
    	return $data['ml_id'];
    }
    /**
     * 获得会员等级列表
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return ary $result
     * @date 2013-09-30
     */
    public function getgradelist($ary_field){
        return $result=$this->field($ary_field)->order(array('ml_up_fee'=>asc))->select();
    }
    
    /**
     * 判断是否升级，达到升级条件自动升级
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @param int $u_id 会员id
     * @date 2013-09-30
     */
    public function autoUpgrade($u_id){
	    //是否开启自动升级
		$upgrade_status = D('SysConfig')->getCfgByModule('MEMBERSUPGRADE_SET');
		if(!$upgrade_status['MEMBERSUPGRADE_STATUS'] ||empty($upgrade_status['MEMBERSUPGRADE_STATUS'])){
			return false;
		}
        $member = session ( "Members" );
		if(empty($u_id) && empty($member['m_id'])){
			return false;
		}
        if(empty($u_id) && !empty($member['m_id'])){
            $u_id=$member['m_id'];
        }
        $min_id=2;
        $time = mktime(0,0,0,date("m"),date("d")-30,date("Y"));
        $date=date("Y-m-d H:i:s", $time);
        $total=D('Orders')->getUserConsumptionMoney($u_id,$date);
        
        $User_Grade =D('Members')->getByNameLevel(array('fx_members.m_id'=>$u_id),array('fx_members_level.ml_id'));
        
        $grades = array();
        $res = $this->getgradelist(array('ml_up_fee,ml_id,ml_name,ml_discount'));
        $num=count($res);
        foreach($res as $key=>$grade){
            if($num>=3 && $User_Grade['ml_id']>= $res[$min_id]['ml_id']){//最低跌至第三等级
                $min_ml_id=$res[$min_id]['ml_id'];
            }
			$grades[$grade['ml_id']] = $grade;
		}
        $datas=array_reverse($grades,true);
        foreach ($datas as $ml_id=>$grade){
			if($total >= $grade['ml_up_fee']){
			    if($ml_id < $min_ml_id){//最低跌至第三等级
		            $ml_id=$min_ml_id;
			    }

                if($User_Grade['ml_id'] != $ml_id){
                    $member['ml_id']=$ml_id;
                    $member['member_level']=array('ml_id'=>$ml_id,
                                              'ml_name'=>$grade['ml_name'],
                                              'ml_discount'=>$grade['ml_discount']
                                              );
                    session('Members', $member);			
                    D('Members')->UpdateUserLevel($u_id,$ml_id);
					$obj_query = $this->field(array('m_id','m_name','fx_members.ml_id','ml_discount'))->join("fx_members_level on fx_members.ml_id = fx_members_level.ml_id")->where(array('m_id'=>$member['m_id']));	
					D('Gyfx')->deleteQueryCache($obj_query,'find',60);	
					D('Gyfx')->deleteOneCache('members','ml_id', array('m_id'=>$member['m_id']), $ary_order=null);
                }
				break;
			}
		}
    }
}