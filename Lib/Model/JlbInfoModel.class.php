<?php
/**
 * 金币调整单模型
 *
 * @package Model
 * @version 7.6
 * @author Hcaijin<huangcaijin@guanyisoft.com>
 * @date 2013-07-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class JlbInfoModel extends GyfxModel {

    public function getJlb($params = array()){
        $ary_where = array();
        if(!empty($params['ji_sns']) && isset($params['ji_sns'])){
            $data = explode(',',trim($params['ji_sns'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."jlb_info.`ji_sn` IN (". trim($str,",") .")";
        }
        if(!empty($params['ji_id']) && isset($params['ji_id'])){
            $data = explode(',',trim($params['ji_id'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."jlb_info.`ji_id` IN (". trim($str,",") .")";
        }
        $ary_data = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM')->field(" ".C("DB_PREFIX")."jlb_info.*,".C("DB_PREFIX")."jlb_type.jt_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."jlb_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."jlb_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."jlb_type ON ".C("DB_PREFIX")."jlb_type.`jt_id`=".C("DB_PREFIX")."jlb_info.`jt_id`")
                                   ->order(" ".C("DB_PREFIX")."jlb_info.`ji_order` DESC")
                                   ->where($ary_where)
                                   ->select();
        if(!empty($ary_data) && is_array($ary_data)){
            return $ary_data;
        }  else {
            return array();
        }
        
    }

    /**
     * 新增金币调整单
     * @author Hcaijin
     * @date 2014-07-15
     * $arr = array(10) {
     *   ["jt_id"] => string(1) "3"  //金币类型
     *   ["m_id"] => string(3) "702" //会员id
     *   ["ji_type"] => string(1) "0" //调整单类型
     *   ["ps_id"] => string(16) "1545841410184651" 
     *   ["ji_money"] => string(3) "100"
     *   ["ji_desc"] => string(24) "备注"
     *   ["ji_sn"] => int(1405427286)
     *   ["u_id"] => string(1) "1"
     *   ["ji_create_time"] => string(19) "2014-07-15 20:28:06"
     *   ["pc_serial_number"] => string(16) "1545841410184651"
     *   ["ji_finance_verify"] => string(1) "1" //财审状态
     *   ["ji_service_verify"] => string(1) "1" //客审状态
     *   ["ji_verify_status"] => string(1) "1" //确认状态
     *   ["single_type"] => string(1) "2" //1,为管理员制单;2,为会员制单
     *   }
     */
    public function addJlb($arr){
        $result = false;
        $obj = M('JlbInfo',C('DB_PREFIX'),'DB_CUSTOM');
        $obj->startTrans();
        $arr['ji_finance_verify'] = !empty($arr['ji_finance_verify'])&&$arr['ji_finance_verify']=='1' ? '1' : '0';
        $arr['ji_service_verify'] = !empty($arr['ji_service_verify'])&&$arr['ji_service_verify']=='1' ? '1' : '0';
        //$arr['ji_verify_status'] = !empty($arr['ji_verify_status'])&&$arr['ji_verify_status']=='2' ? '2' : '0';
        $ji_desc = $arr['ji_desc'];
        //调整描述
        switch ($arr['jt_id']){
            case 1:
              $arr['ji_desc'] = '金币充值：'.$ji_desc;
              break;  
            case 2:
              $arr['ji_desc'] = '消费金币：'.$ji_desc;
              break;
            case 3:
              $arr['ji_desc'] = '兑换金币：'.$ji_desc;
              break;
            default:
        }
        $ary_result = $obj->add($arr);
        if(FALSE != $ary_result){
            $ary_data = array();
            $str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
            $ary_data['ji_sn'] = time() . $str_sn;
            $bresult = $obj->where(array('ji_id'=>$ary_result))->data($ary_data)->save();
            $params = array(
                'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                'ji_sn' => $ary_data['ji_sn'],
                'bvl_status'    =>'1',
                'bvl_create_time'   =>date("Y-m-d H:i:s")
            );
            if(!empty($arr['ji_service_verify']) && $arr['ji_finance_verify'] == '1'){
                $params['bvl_desc'] = '已客审成功';
                $params['bvl_type'] = '2';
                $this->writeJlbInfoLog($params);
            }
            if(!empty($arr['ji_finance_verify']) && $arr['ji_finance_verify'] == '1'){
                $params['bvl_desc'] = '已财审成功';
                $params['bvl_type'] = '3';
                $this->writeJlbInfoLog($params);
            }       
            if(FALSE != $bresult){
                //如果已客审,已财审,已确认,更新会员表金币金额
                if($arr['ji_finance_verify'] == '1' && $arr['ji_service_verify'] == '1' && $arr['ji_verify_status'] == '1'){
                    $ary_data = D("Members")->field("m_jlb")->where(array("m_id"=>$arr['m_id']))->find();
                    $m_jlb = '';
                    switch($arr['ji_type']){
                        case '0':
                            $m_jlb = $ary_data['m_jlb'] + $arr['ji_money'];
                            break;
                        case '1':
                            $m_jlb = $ary_data['m_jlb'] - $arr['ji_money'];
                            break;
                        case '2':
                            $m_jlb = $ary_data['m_jlb'] - $arr['ji_money'];
                            break;
                        default :
                            $m_jlb = $ary_data['m_jlb'] + $arr['ji_money'];
                            break;
                    }
                    D("Members")->where(array('m_id'=>$arr['m_id']))->data(array('m_jlb'=>$m_jlb))->save();
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
    public function writeJlbInfoLog($params){
        M('jlb_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
}
