<?php
/**
 * 同步分销会员信息
 * @package Common
 * @subpackage ErpMembers
 * @author Jerry
 * @since 7.0
 * @version 1.0
 * @date 2013-1-31
 */
class ErpMembers extends ErpApi{
    private $errMsg = '';           //存放错误信息
    private $errRemind = array(
        'paramErr'		=> '参数有误！',
        'mailErr'		=> '会员邮箱错误！',
        'u_nameErr'	=> '会员名称有误！',
        'member_hylyErr'	=> '会员来源代码有误！'
    );
    private $shopCode = '';         //店铺代码
    public function __construct(){
        parent::__construct();
    }

    /**
     * 新增会员信息到ERP
     * @auther Terry<wanghui@guanyisoft.com>
     * @date 2013-1-31
     */
    public function addMembers($email){
        $ary_res	= array('success'=>0,'msg'=>'', 'errCode'=>0, 'data'=>array());
        try{
            if(empty($email)){
                $this->errMsg = $this->errRemind['mailErr'];
            }
            //再次根据会员邮箱校验
            $ary_member = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where(array('m_email'=>$email))->find();
            if(empty($ary_member)){
                throw new Exception('数据错误：会员（mail:'.$email.'）数据丢失！', 2003);
            }
            if(empty($ary_member) && intval($ary_member['ml_id'])<=0){
                $str_code = '';
            }else{
                $ary_level = M("MembersLevel",C('DB_PREFIX'),'DB_CUSTOM')->where(array('ml_id'=>$ary_member['ml_id']))->find();
                $str_code = $ary_level['ml_code'];
            }
            $ary_city = $this->getMemberTree($ary_member['cr_id']);
            $state = '';
            $city = '';
            $district = '';
            if(!empty($ary_city) && is_array($ary_city)){
                $state = $ary_city[0]['cr_name'];
                $city = $ary_city[1]['cr_name'];
                $district =$ary_city[2]['cr_name'];
            }
            if(empty($ary_member['m_birthday']) || $ary_member['m_birthday'] == '0000-00-00'){
                $ary_member['m_birthday'] = '';
            }
            $parameters = array(
                'shopcode'=>$this->str_shop_code,
                'mail' =>$email,
                'dzyx'  =>$ary_member['m_email'],                     //电子邮箱
                'receiver_zip'=>$ary_member['m_zipcode'],             //邮编
                'member_hyly'=>'0',              //会员来源。默认为0，分销这里传0(系统网站 = 0,淘宝网站 = 1,其他 = 2,淘宝分销 = 3,拍拍网站 = 4,京东商城 = 5,当当网站 = 6,商派网站 = 8,POS门店 = 9,商派分销王 = 10,一号店 = 11,凡客商城 = 12,商派独立网店 = 99)
                'receiver_phone'=>$ary_member['m_telphone'],           //电话      格式为：021-12345678
                'receiver_mobile'=>$ary_member['m_mobile'],          //手机
                'receiver_name'=>$ary_member['m_real_name'],            //收货人
                'receiver_address'=>$ary_member['m_address_detail'],         //地址
                'receiver_state'=>$state,           //省
                'receiver_city'=>$city,            //市
                'receiver_district'=>$district,        //区
                'birthday'=>$ary_member['m_birthday'],                 //出生日期 格式为：1991-08-09
                'sex'=>$ary_member['m_sex'],                      //性别。( 0-女,1-男,2-无 )
                'u_name'=>$ary_member['m_name'],                   //会员名称
                'QQ'=>$ary_member['m_qq'],                       //QQ号码
                'WW'=>$ary_member['m_wangwang'],                       //旺旺号码
                'reference'=>'',                //推荐人
                'is_proxy'=>'0',                 //是否申请代理商
                'alipay'=>$ary_member['m_alipay_name'],                   //支付宝账号
                'identity'=>'',                 //身份证号码
                'shopsite'=>$ary_member['m_website_url'],                 //网店网址
                'shopaddress'=>'',              //实体店地址
                'proxyshopname'=>'',            //代理店名称
                'sqbz'=>'',                     //申请备注
                'hyjb'=>$str_code,                     //会员级别
                'hybz'=>'',                     //会员备注
            );
            $top = Factory::getTopClient();
            $ary_members = $top->MemberGet(array(
                'fields' => array(':all'),
                'condition' => "hydm='$email' and TY='0'",
                'page_size' => 1,
                'page_no' => 1
            ));
            if(!empty($ary_members) && intval($ary_members['total_results']) > 0){
                $data = $top->MemberUpdate($parameters);
            }else{
                $data = $top->MemberAdd($parameters);
            }
            if($top->getLastResponse()->isError()){
                //错误处理$top->getLastResponse()->getErrorInfo()
               throw new Exception($top->getLastResponse()->getErrorInfo(), 2004);
            }else{
               if(!isset($data['hy_guid']) || empty($data['hy_guid'])){
                   throw new Exception($this->errRemind['member_hylyErr'], 2005);
               }else{
                   $ary_data = array(
                       'm_update_time'  =>  $data['created'],
                       'thd_guid'  =>  $data['hy_guid']
                   );
                   $ary_where = array(
                       'm_email'    =>$email
                   );
                   $ary_result = M("Members",C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->save($ary_data);
//                   echo M("Members")->getLastSql();exit;
                   if(FALSE !==$ary_result){
                       $ary_res['success'] = '1';
                       $ary_res['msg'] = '同步成功';
                   }else{
                       throw new Exception("更新会员 {$ary_member['m_name']}信息失败", 2006);
                   }
               }
            }
        }catch(Exception $e){
            $ary_res['msg']	= $e->getMessage();
            $ary_res['errCode']	= $e->getCode();
        }
        return $ary_res;
    }

    /**
     * 获取会员地址
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-1-31
     */
    public function getMemberTree($cid){
        $city = M("CityRegion",C('DB_PREFIX'),'DB_CUSTOM');
        $ary_result = $city->where(array('cr_id'=>$cid))->find();
        if(!empty($ary_result) && is_array($ary_result)){
            $crid = str_replace("|",",",ltrim($ary_result['cr_path'],"|1")).",".$cid;
            $where['cr_id'] = array('in',$crid);
            $ary_data = $city->where($where)->select();
            return $ary_data;
        }else{
            return array();
        }
    }

}