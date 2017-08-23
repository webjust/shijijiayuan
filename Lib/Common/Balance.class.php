<?php
/**
 * 更新结余款信息
 * @package Common
 * @subpackage Balance
 * @author Terry
 * @since 7.2
 * @version 1.0
 * @date 2013-6-14
 */
class Balance{
    
    protected $request;
    
    /**
     * 初始化连接信息
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-14
     */
    public function __construct() {
        $this->request = M('balance_info',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 获取结余款信息
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-14
     */
    public function getBalanceInfo($params){
        if(!empty($params) && is_array($params)){
            $ary_result = $this->request->where($params)->find();
            return $ary_result;
        }else{
            return array();
        }
    }
    
    /**
     * 修改单据状态
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-14
     */
    public function doBalanceInfoStatus($params){
        if(!empty($params) && is_array($params)){
            $data = array();
            $field = '';
            $str = '';
            switch ($params['field']){
                case 'or_service_verify':
                    $field = 'bi_service_verify';
                    $str = '客审';
                    break;
                case 'or_finance_verify':
                    $field = 'bi_finance_verify';
                    $str = '财审';
                    break;
            }
            $ary_res = $this->request->where(array('or_id'=>$params['or_id']))->data(array($field=>$params['val']))->save();
         //   echo $this->request->getLastSql();exit;
//            echo "<pre>";print_r($field);exit;
            if(FALSE === $ary_res){
                return $this->Error($str."失败");
                
            }else{
                if(!empty($field) && $field == 'bi_finance_verify'){
                    return $this->memberBalance(0,$params);
                }
                return $this->Success($str."成功");
            }
        }else{
            return $this->Error("参数有误", "10001");
        }
    }
    
    public function memberBalance($type,$data=array()){
        $ary_data = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$data['m_id']))->find();
        if($type == '1'){
            $ary_data['m_balance'] = $ary_data['m_balance'] - $data['or_money'];
        }elseif($type == '2'){
            $ary_data['m_balance'] = $ary_data['m_balance'] - $data['or_money'];
        }else{
            $ary_data['m_balance'] = $ary_data['m_balance'] + $data['or_money'];
        }
        $ary_res = M('members',C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_id'=>$data['m_id']))->data(array('m_balance'=>$ary_data['m_balance']))->save();
        if(FALSE === $ary_res){
            return $this->Error("更新结余款失败");
        }else{
            return $this->Success("更新结余款成功");
            
        }
    }
    
    /**
     * 新增结余款调整单
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-14
     */
    public function addBalanceInfo($params = array()){
        $ary_res = $this->request->add($params);
        if(FALSE != $ary_res){
            $str_sn = str_pad($ary_res,6,"0",STR_PAD_LEFT);
            $ary_data['bi_sn'] = time() . $str_sn;
            $this->request->where(array('bi_id'=>$ary_res))->data($ary_data)->save();
            if(!empty($params['bi_finance_verify']) && $params['bi_finance_verify']){
                $data = array(
                    'm_id'  => $params['m_id'],
                    'or_money'  => $params['bi_money']
                );
                return $this->memberBalance(0,$data);
            }else{
                return $this->Success("审核成功");
            }
        }else{
            return $this->Error("审核失败", 1004);
        }
    }
    
    /**
     * 成功信息返回
     * @param array $data 需要返回的数据信息
     */
    public function Success($msg = '',$data=array()){        
        $msg = empty($msg) ? "操作成功" : $msg;
        $data = array(
            "success"   => '1',
            "msg"   =>$msg,   //提示信息
            "data" =>  $data     //错状态码
        );
        return $data;exit;
    }
    
    public function Error($msg,$code){
        $msg = empty($msg) ? "操作失败" : $msg;
        $data = array(
            "success"   => '0',
            "msg"   => $msg,   //提示信息
            "errCode" =>  $code     //错状态码
        );
        return $data;
    }
}