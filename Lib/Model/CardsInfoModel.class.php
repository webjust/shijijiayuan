<?php
/**
 * 储值卡调整单模型
 *
 * @package Model
 * @version 7.6
 * @author Hcaijin<huangcaijin@guanyisoft.com>
 * @date 2014-08-07
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CardsInfoModel extends GyfxModel {

    public function getCards($params = array()){
        $ary_where = array();
        if(!empty($params['ci_sns']) && isset($params['ci_sns'])){
            $data = explode(',',trim($params['ci_sns'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."cards_info.`ci_sn` IN (". trim($str,",") .")";
        }
        if(!empty($params['ci_id']) && isset($params['ci_id'])){
            $data = explode(',',trim($params['ci_id'],","));
            $str = '';
            foreach($data as $ky=>$vl){
                $str .= "'".$vl."',";
            }
            $ary_where = C("DB_PREFIX")."cards_info.`ci_id` IN (". trim($str,",") .")";
        }
        $ary_data = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM')->field(" ".C("DB_PREFIX")."cards_info.*,".C("DB_PREFIX")."cards_type.ct_name,".C("DB_PREFIX")."members.`m_name`,".C("DB_PREFIX")."admin.`u_name`")
                                   ->join(" ".C("DB_PREFIX")."members ON ".C("DB_PREFIX")."cards_info.`m_id`=".C("DB_PREFIX")."members.`m_id`")
                                   ->join(" ".C("DB_PREFIX")."admin ON ".C("DB_PREFIX")."cards_info.`u_id`=".C("DB_PREFIX")."admin.`u_id`")
                                   ->join(" ".C("DB_PREFIX")."cards_type ON ".C("DB_PREFIX")."cards_type.`ct_id`=".C("DB_PREFIX")."cards_info.`ct_id`")
                                   ->order(" ".C("DB_PREFIX")."cards_info.`ci_order` DESC")
                                   ->where($ary_where)
                                   ->select();
        if(!empty($ary_data) && is_array($ary_data)){
            return $ary_data;
        }  else {
            return array();
        }
        
    }

    /**
     * 新增储值卡调整单
     * @author Hcaijin
     * @date 2014-08-07
     * $arr = array(10) {
     *   ["ct_id"] => string(1) "3"  //储值卡类型
     *   ["m_id"] => string(3) "702" //会员id
     *   ["ci_type"] => string(1) "0" //调整单类型
     *   ["ps_id"] => string(16) "1545841410184651" 
     *   ["ci_money"] => string(3) "100"
     *   ["ci_desc"] => string(24) "备注"
     *   ["ci_sn"] => int(1405427286)
     *   ["u_id"] => string(1) "1"
     *   ["ci_create_time"] => string(19) "2014-07-15 20:28:06"
     *   ["pc_serial_number"] => string(16) "1545841410184651"
     *   ["ci_finance_verify"] => string(1) "1" //财审状态
     *   ["ci_service_verify"] => string(1) "1" //客审状态
     *   ["ci_verify_status"] => string(1) "1" //确认状态
     *   ["single_type"] => string(1) "2" //1,为管理员制单;2,为会员制单
     *   }
     */
    public function addCards($arr){
        $result = false;
        $obj = M('CardsInfo',C('DB_PREFIX'),'DB_CUSTOM');
        $obj->startTrans();
        $arr['ci_finance_verify'] = !empty($arr['ci_finance_verify'])&&$arr['ci_finance_verify']=='1' ? '1' : '0';
        $arr['ci_service_verify'] = !empty($arr['ci_service_verify'])&&$arr['ci_service_verify']=='1' ? '1' : '0';
        //$arr['ci_verify_status'] = !empty($arr['ci_verify_status'])&&$arr['ci_verify_status']=='2' ? '2' : '0';
        $ci_desc = $arr['ci_desc'];
        //调整描述
        switch ($arr['ct_id']){
            case 1:
              $arr['ci_desc'] = '储值卡充值：'.$ci_desc;
              break;  
            case 2:
              $arr['ci_desc'] = '消费储值卡：'.$ci_desc;
              break;
            default:
        }
        $ary_result = $obj->add($arr);
        if(FALSE != $ary_result){
            $ary_data = array();
            $str_sn = str_pad($ary_result,6,"0",STR_PAD_LEFT);
            $ary_data['ci_sn'] = time() . $str_sn;
            $bresult = $obj->where(array('ci_id'=>$ary_result))->data($ary_data)->save();
            $params = array(
                'u_id'  =>$_SESSION[C('USER_AUTH_KEY')],
                'ci_sn' => $ary_data['ci_sn'],
                'bvl_status'    =>'1',
                'bvl_create_time'   =>date("Y-m-d H:i:s")
            );
            if(!empty($arr['ci_service_verify']) && $arr['ci_finance_verify'] == '1'){
                $params['bvl_desc'] = '已客审成功';
                $params['bvl_type'] = '2';
                $this->writeCardsInfoLog($params);
            }
            if(!empty($arr['ci_finance_verify']) && $arr['ci_finance_verify'] == '1'){
                $params['bvl_desc'] = '已财审成功';
                $params['bvl_type'] = '3';
                $this->writeCardsInfoLog($params);
            }       
            if(FALSE != $bresult){
                //如果已客审,已财审,已确认,更新会员表储值卡金额
                if($arr['ci_finance_verify'] == '1' && $arr['ci_service_verify'] == '1' && $arr['ci_verify_status'] == '1'){
                    $ary_data = D("Members")->field("m_cards")->where(array("m_id"=>$arr['m_id']))->find();
                    $m_cards = '';
                    switch($arr['ci_type']){
                        case '0':
                            $m_cards = $ary_data['m_cards'] + $arr['ci_money'];
                            break;
                        case '1':
                            $m_cards = $ary_data['m_cards'] - $arr['ci_money'];
                            break;
                        case '2':
                            $m_cards = $ary_data['m_cards'] - $arr['ci_money'];
                            break;
                        default :
                            $m_cards = $ary_data['m_cards'] + $arr['ci_money'];
                            break;
                    }
                    D("Members")->where(array('m_id'=>$arr['m_id']))->data(array('m_cards'=>$m_cards))->save();
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
    public function writeCardsInfoLog($params){
        M('cards_verify_log',C('DB_PREFIX'),'DB_CUSTOM')->add($params);
    }
}
