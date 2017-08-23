<?php
/**
 * 红包调整单模型
 *
 * @package Model
 * @version 7.6
 * @author Hcaijin<huangcaijin@guanyisoft.com>
 * @date 2013-07-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class BonusInfoModel extends GyfxModel {

    public function getBonus($params = array()){
        $ary_where = array();
        if(!empty($params['bn_sns']) && isset($params['bn_sns'])){
            $data = explode(',',trim($params['bn_sns'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."bonus_info.`bn_sn` IN (". trim($str,",") .")";
        }
        if(!empty($params['bn_id']) && isset($params['bn_id'])){
            $data = explode(',',trim($params['bn_id'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."bonus_info.`bn_id` IN (". trim($str,",") .")";
        }
        $ary_data = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM')->field(" ".C("DB_PREFIX")."bonus_info.*,".C("DB_PREFIX")."bonus_type.bt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."bonus_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."bonus_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."bonus_type ON ".C("DB_PREFIX")."bonus_type.`bt_id`=".C("DB_PREFIX")."bonus_info.`bt_id`")
                                   ->order(" ".C("DB_PREFIX")."bonus_info.`bn_order` DESC")
                                   ->where($ary_where)
                                   ->select();
        if(!empty($ary_data) && is_array($ary_data)){
            return $ary_data;
        }  else {
            return array();
        }
        
    }

    /**
     * 新增红包调整单
     * @author Hcaijin
     * @date 2014-07-15
     * $arr = array(10) {
     *   ["bt_id"] => string(1) "3"  //红包类型
     *   ["m_id"] => string(3) "702" //会员id
     *   ["bn_type"] => string(1) "0" //调整单类型
     *   ["ps_id"] => string(16) "1545841410184651" 
     *   ["bn_money"] => string(3) "100"
     *   ["bn_desc"] => string(24) "备注"
     *   ["bn_sn"] => int(1405427286)
     *   ["u_id"] => string(1) "1"
     *   ["bn_create_time"] => string(19) "2014-07-15 20:28:06"
     *   ["pc_serial_number"] => string(16) "1545841410184651"
     *   ["bn_finance_verify"] => string(1) "1" //财审状态
     *   ["bn_service_verify"] => string(1) "1" //客审状态
     *   ["bn_verify_status"] => string(1) "1" //确认状态
     *   ["single_type"] => string(1) "2" //1,为管理员制单;2,为会员制单
     *   }
     */
    public function addBonus($arr){
        $result = false;
        $obj = M('BonusInfo',C('DB_PREFIX'),'DB_CUSTOM');
        $obj->startTrans();
        $arr['bn_finance_verify'] = !empty($arr['bn_finance_verify'])&&$arr['bn_finance_verify']=='1' ? '1' : '0';
        $arr['bn_service_verify'] = !empty($arr['bn_service_verify'])&&$arr['bn_service_verify']=='1' ? '1' : '0';
        //$arr['bn_verify_status'] = !empty($arr['bn_verify_status'])&&$arr['bn_verify_status']=='2' ? '2' : '0';
        $bn_desc = $arr['bn_desc'];
        //调整描述
        switch ($arr['bt_id']){
            case 1:
              $arr['bn_desc'] = '注册红包：'.$bn_desc;
              break;  
            case 2:
              $arr['bn_desc'] = '抽奖红包：'.$bn_desc;
              break;
            case 3:
              $arr['bn_desc'] = '红包充值：'.$bn_desc;
              break;
            case 4:
              $arr['bn_desc'] = '消费红包：'.$bn_desc;
              break;
            default:
        }
        $ary_result = $obj->add($arr);
        if(FALSE != $ary_result){
            $ary_data = array();
            $str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
            $ary_data['bn_sn'] = time() . $str_sn;
            $bresult = $obj->where(array('bn_id'=>$ary_result))->data($ary_data)->save();
            $params = array(
                'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                'bn_sn' => $ary_data['bn_sn'],
                'bvl_status'    =>'1',
                'bvl_create_time'   =>date("Y-m-d H:i:s")
            );
            if(!empty($arr['bn_service_verify']) && $arr['bn_finance_verify'] == '1'){
                $params['bvl_desc'] = '已客审成功';
                $params['bvl_type'] = '2';
                $this->writeBonusInfoLog($params);
            }
            if(!empty($arr['bn_finance_verify']) && $arr['bn_finance_verify'] == '1'){
                $params['bvl_desc'] = '已财审成功';
                $params['bvl_type'] = '3';
                $this->writeBonusInfoLog($params);
            }       
            if(FALSE != $bresult){
                //如果已客审,已财审,已确认,更新会员表红包金额
                if($arr['bn_finance_verify'] == '1' && $arr['bn_service_verify'] == '1' && $arr['bn_verify_status'] == '1'){
                    $ary_data = D("Members")->field("m_bonus")->where(array("m_id"=>$arr['m_id']))->find();
                    $m_bonus = '';
                    switch($arr['bn_type']){
                        case '0':
                            $m_bonus = $ary_data['m_bonus'] + $arr['bn_money'];
                            break;
                        case '1':
                            $m_bonus = $ary_data['m_bonus'] - $arr['bn_money'];
                            break;
                        case '2':
                            $m_bonus = $ary_data['m_bonus'] - $arr['bn_money'];
                            break;
                        default :
                            $m_bonus = $ary_data['m_bonus'] + $arr['bn_money'];
                            break;
                    }
                    D("Members")->where(array('m_id'=>$arr['m_id']))->data(array('m_bonus'=>$m_bonus))->save();
                }
                $obj->commit();
                $result = true;
            }else{
                $obj->rollback();
            }
        }else{
            $obj->rollback();
        }
        return $result;
    }

    /**
     * 记录审核日志
     * @author Hcaijin<huangcaijin@guanyisoft.com>
     * @date 2014-07-14
     */
    public function writeBonusInfoLog($params){
        M('bonus_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
}
