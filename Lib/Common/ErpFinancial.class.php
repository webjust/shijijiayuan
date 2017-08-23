<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 同步会员结余款
 * @package Common
 * @subpackage ErpFinancial
 * @since 7.0
 * @version 1.0
 * @date 2013-2-28
 * @author listen
 */
class ErpFinancial extends ErpApi{
   
    private $errMsg = '';           //存放错误信息
    private $errRemind = array(
        'paramErr'		=> '参数有误！',
        'mailErr'		=> '会员代码错误！',
        'membersErr'            =>'erp会员代码不唯一',
        'money'                 =>'金额大于0'
       
    );
    private $shopCode = '';         //店铺代码
    public function __construct() {
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API');
        $this->shopCode = $ary_api_conf['SHOP_CODE']['sc_value'];
        parent::__construct();
    }
    /**
     * 增加线下充值到erp
     * @author listen
     * @param $ary_financial 需要同步的结余款信息
     * $status 先下充值还是线上 到账方式。( 0-线下转账,1-即时到账,2-作废结余款单 )
     * @return $ary_res 
     * @date 2013-4-2
     */
    public function addFinancial($mail,$ary_financial,$status=0){
        $ary_res =  array('success'=>0,'msg'=>'', 'errCode'=>0, 'data'=>array());
        $top = Factory::getTopClient(); 
       //找到指定的会员
        if(!isset($mail) || $mail == ''){
            $ary_res['msg'] = $this->errRemind['paramErr'];
            return $ary_res;exit;
            //$this->errMsg = $this->errRemind['paramErr'];
        }
        $ary_members = $top->MemberGet(array(
            'fields' => array(':all'),
            'condition' => "hydm='$mail' and TY='0'",
            'page_size' => 1,
            'page_no' => 1
        ));
        if(intval($ary_members['total_results']) > 1){
            $ary_res['msg'] = $this->errRemind['membersErr'];
            return $ary_res;exit;
             //$this->errMsg = $this->errRemind['paramErr'];
        }
        
        if(empty($ary_financial)){
            $ary_res['msg'] = $this->errRemind['paramErr'];
            return $ary_res;exit; 
        }else {
            $ary_erp_financial = array(
                'mail' => $mail,
                'id'=>$ary_financial['re_payment_sn'],
                'shop_code'=>$this->shopCode,
                'money'=>$ary_financial['re_money'],
                'status'=>$status,
                'memo'=>isset($ary_financial['re_message'])?$ary_financial['re_message']:'',
                'bank'=>isset($ary_financial['a_apply_bank'])?$ary_financial['a_apply_bank']:''          
            );
           //dump($ary_erp_financial);exit;
           $ary_erp_res = $top->BalanceAdd($ary_erp_financial);
           if(!$ary_erp_res){
              
               $ary_res['msg'] = ' 同步erp出错--'.$top->getLastResponse()->getErrorInfo();;
               return $ary_res;exit;
           }else {
                $ary_res['success'] = '1';
                $ary_res['msg'] = '同步成功';
           }
           //dump($ary_erp_res);exit;
        }
        return $ary_res;
        //
    }
}

?>
