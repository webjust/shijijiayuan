<?php
/**
 * 同步分销等级信息
 * @package Common
 * @subpackage ErpMembersLevel
 * @author Jerry
 * @since 7.0
 * @version 1.0
 * @date 2013-1-31
 */
class ErpMembersLevel extends ErpApi{
    private $errMsg = '';           //存放错误信息
    private $errRemind = array(
        'paramErr'		=> '参数有误！',
        'nameErr'		=> '会员等级名称错误！',
        'codeErr'		=> '会员等级代码错误！',
        'level_Err'	=> '会员等级有误！'
    );
    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取会员等级总数
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    public function getMembersLevelCount(){
        $count = 0;
        $top = Factory::getTopClient();
        $data = $top->MemberLevelGet(array(
            'fields' => array(':all'),
            'condition' => "IS_TY='0' or IS_TY='1'",
            'page_size' => 1,
            'page_no' => 1
        ));
        if($top->getLastResponse()->isError()){
            return $count;
            //错误处理$top->getLastResponse()->getErrorInfo()
        }else{
            //数据处理
            return $data['total_results'];
        }
    }

    /**
     * 获取会员等级信息
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    public function synMemberLevelOne($page_size, $page_no , $condition=''){
        $ary_res	= array('success' => 1, 'errMsg' => '', 'errCode' => '', 'succRows' => 0, 'errRows' => 0, 'errData' => array());
        try{
            $top = Factory::getTopClient();
            $data = $top->MemberLevelGet(array(
                'fields' => array(':all'),
                'condition' => empty($condition) ? "IS_TY='0' or IS_TY='1' and {$condition}" :"IS_TY='0' or IS_TY='1'" ,
                'page_size' => $page_size,
                'page_no' => $page_no
            ));
            if($top->getLastResponse()->isError()){
                //错误处理getLastResponse$top->getLastResponse()->getErrorInfo()
                throw new Exception($top->getLastResponse()->getErrorInfo(), 3001);
            }else{
                //数据处理
                if (!is_array($data) || empty($data)) {
                    throw new Exception('接口返回数据有误，请检查API是否正确！', 3002);
                }else{
                    if (!isset($data['hyjbs']['hyjb']) || !is_array($data['hyjbs']['hyjb'])) {
                        if ($data['total_results'] == 0) {
                            throw new Exception('没有可同步的数据！！', 3003);
                        } else {
                            throw new Exception('接口返回数据有误，请检查API是否正确！', 3004);
                        }
                    }
                   // print_r($data['hyjbs']['hyjb']);exit;
                    foreach ($data['hyjbs']['hyjb'] as $keyml=>$valml){
                        $ary_result = $this->saveMemberLevel($valml);
                        if(FALSE !== $ary_result['success']){
                            $ary_res['errData'][$ary_result['jbdm']] = array(
                                'errMsg' => $ary_result['msg'],
                                'errCode' => '3005',
                                'ml_name' => $ary_result['data']
                            );
                            $ary_res['succRows']++;
                        }else{
                            $ary_res['errRows']++;
                        }
                    }
                    //$this->saveMemberLevel($data);
                }
            }
        }catch(Exception $e){
            $ary_res['success'] = 0;
            $ary_res['msg']	= $e->getMessage();
            $ary_res['errCode']	= $e->getCode();
        }
        return $ary_res;
    }

    /**
     * 保存会员等级到分销平台
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-2-1
     */
    protected function saveMemberLevel($data){
        $ary_res	= array('success'=>0,'msg'=>'', 'errCode'=>0, 'data'=>array());
        $ml = D("MembersLevel");
        $ary_data = $ml->getByMlGuid($data['guid']);
        $ty = intval($data['is_ty'])>0?0:1;
        if(!empty($ary_data) && is_array($ary_data)){
            //存在则更新数据
            $where = array(
                'ml_erp_guid'   => $data['guid']
            );
            $arr_data = array(
                'ml_code'   => $data['jbdm'],
                'ml_name'   => $data['jbmc'],
                'ml_discount'   => $data['jbzk']*100,
                'ml_status'   => $ty,
                'ml_update_time'    => date("Y-m-d H:i:s")
            );
            $ary_result = $ml->where($where)->data($arr_data)->save();
            if($ary_result){
                $ary_res['success'] = 1;
                $ary_res['msg'] = "更新数据成功";
            }else{
                $ary_res['success'] = 0;
                $ary_res['data'] = $data['jbmc'];
                $ary_res['msg'] = "更新数据失败";
            }
        }else{
            //不存在则将数据写入分销平台
            $arr_data = array(
                'ml_code'   => $data['jbdm'],
                'ml_name'   => $data['jbmc'],
                'ml_discount'   => $data['jbzk']*100,
                'ml_status'   => $ty,
                'ml_create_time'    => date("Y-m-d H:i:s"),
                'ml_erp_guid'   => $data['guid']
            );
            $ary_result = $ml->add($arr_data);
            if($ary_result){
                $ary_res['success'] = 1;
                $ary_res['msg'] = "添加数据成功";
            }else{
                $ary_res['success'] = 0;
                $ary_res['data'] = $data['jbmc'];
                $ary_res['msg'] = "添加数据失败";
            }
        }
        return $ary_res;
    }
}