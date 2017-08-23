<?php

/**
 * 后台短信控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.6.1
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-08-04
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class SmsAction extends AdminAction{

    /**
     * 控制器初始化
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db'); 
        $this->setTitle(' - 短信设置');
    }

    /**
     * 默认控制器
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
    public function index(){
        $this->redirect(U('Admin/Sms/pageSms'));
    }

    /**
     * 后台短信SMS设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
    public function pageSms(){
        $this->getSubNav(2, 9, 10);
        $info = D('SysConfig')->getSmsCfg();
		if(!empty($info['GY_SMS_NAME']) && !empty($info['GY_SMS_PASS'])){
			$SmsApi_obj=new SmsApi();
			$info['total'] =  $SmsApi_obj->getSmsBalance();
		}
        $this->assign($info);
        $str_method = explode('::',__METHOD__);$this->display($str_method[1]);
    }

    /**
     * 保存Sms配置信息
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
    public function doSetSms(){
        $data = $this->_post();
        if(empty($data['GY_SMS_NAME']) && empty($data['GY_SMS_PASS'])){
        	$this->error('必填信息不能为空');
        }
        $SysSeting = D('SysConfig');
        if(
            $SysSeting->setConfig('GY_SMS', 'GY_SMS_NAME', $data['GY_SMS_NAME'], '接入帐号') &&
            $SysSeting->setConfig('GY_SMS', 'GY_SMS_PASS', $data['GY_SMS_PASS'], '密码')
        ){
			$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"保存Sms配置信息",'保存Sms配置信息为：'.$data['GY_SMS_NAME']));
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    /**
     * 发送测试短信
     * @author wangguibin <wangguibin@guanysoft.com>
     * @date 2014-08-04
     */
    public function doTestSms(){
        $data = $this->_get();
        if(empty($data['GY_SMS_NAME']) && empty($data['GY_SMS_PASS'])){
        	$this->error('必填信息不能为空,SMS设置有问题');
        }
        $test_mobile = $data['testSms'];
		if(!$test_mobile){
			$this->error('测试短信发送失败，请确认Sms设置是否有问题');
		}
		$test_send_content = '测试短信发送成功，Sms设置正确。'.date('Y-m-d H:i:s');
		$SmsApi_obj=new SmsApi();
		$array_params=array('mobile'=>$test_mobile,'content'=>$test_send_content);
		$res=$SmsApi_obj->smsSend($array_params);
        if($res['code'] == '200'){
			//日志记录下
			$ary_data = array();
			$ary_data['sms_type'] = 0;
			$ary_data['mobile'] = $test_mobile;
			$ary_data['content'] = $test_send_content;
			$ary_data['sms_type'] = 0;
			D('SmsLog')->addSms($ary_data);
            $this->success('测试短信发送成功，Sms设置正确');
        }else{
            $this->error('测试短信发送失败，'.$res['msg']);
        }
    }
	
	/**
	 *
	 * Enter 签名函数
	 * @param  $paramArr
	 */

	protected function createSign ($paramArr,$app_secret) {
		$sign = $app_secret;
		ksort($paramArr);
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val != '') {
				$sign .= $key.$val;
			}
		}
		$sign.=$app_secret;
		//dump($sign);die();
		$sign = strtoupper(md5($sign));
		return $sign;
	}
		
	/**
     * 后台短信模板设置
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
    public function pageTemp(){
        $this->getSubNav(2, 9, 20);
        $str_method = explode('::',__METHOD__);$this->display($str_method[1]);
    }
	
	/**
     * 后台获得短信模板内容
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
	public function getTemp() {
		$temp_id = $this->_post('temp_id');
		if (isset($temp_id)) {
			$res = D('Sms')->getTemp(array('id' => $temp_id));
			if ($res) {
				//dump($res['content']);die();
				$content=htmlspecialchars_decode($res['content']);
				$this->ajaxReturn(array('status' => '1', 'msg' => '模板成功！', 'info' => $content));
				exit;
			} else {
				$this->ajaxReturn(array('status' => '0', 'msg' => '模板加载失败！', 'info' => ''));
				exit;
			}
		}
	}
	
	/**
     * 后台短信模板编辑
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-04
     */
	public function doEdit() {
		$temp_id = $this->_post('temp_id');
		if(!empty($temp_id)){
			$data['id']=$temp_id;
			$data['content']=$this->_post('temp_content');
			$data['last_modify']=date('Y-m-d H:i:s');
			$res = D('Sms')->doEditTemp($data);
            if(!$res){
                $this->error('保存失败');
            }
		}
		$this->success('保存成功');
	}
	
	/**
     * 已发送列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-08-06
     */
    public function pageList(){
        $this->getSubNav(2,9,30);
		$ary_request = $this->_request();
        $ary_where = array();
		$ary_where['status'] = 1;
		//手机号搜索
		$str_mobile = trim($this->_post('mobile'));
		if(isset($str_mobile) && ($str_mobile != '')){
			$ary_where['mobile'] = $str_mobile;
		}
		//认证状态
		$check_status = trim($this->_post('check_status'));
		if($check_status == ''){
			$ary_request['check_status'] = -1;
		}
		if($check_status != '' && $check_status!= '-1'){
			$ary_where['check_status'] = $check_status;
		}
		//验证码
		$code = trim($this->_post('code'));
		if(isset($code) && $code != ''){
			$ary_where['code'] = $code;
		}
		//发送类型
		$sms_type = trim($this->_post('sms_type'));
		if($sms_type == ''){
			$ary_request['sms_type'] = -1;
		}
		if($sms_type != '-1' && $sms_type != ''){
			$ary_where['sms_type'] = $sms_type;
		}	
		//时间搜索
		if (!empty($ary_request['start_time']) && isset($ary_request['start_time'])) {
			if (!empty($ary_request['end_time']) && $ary_request['end_time'] > $ary_request['start_time']) {
				$ary_request['end_time'] = trim($ary_request['end_time']) . " 23:59:59";
			} else {
				$ary_request['end_time'] = date("Y-m-d H:i:s");
			}
			$ary_where['update_time'] = array("between", array($ary_request['start_time'] . " 00:00:00", $ary_request['end_time']));
		}
        $count =  M('sms_log',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->count();
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_sms =  M('sms_log',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_where)->limit($limit)->order('`update_time` DESC')->select();
		if(is_array($ary_sms) && !empty($ary_sms)){
            foreach($ary_sms as &$v){
                    $v['mobile'] = strpos($v['mobile'],':') ? vagueMobile(decrypt($v['mobile'])) : vagueMobile($v['mobile']);
            }
        }
        $this->assign("page", $page);
        $this->assign('ary_sms',$ary_sms);
		$this->assign('ary_request',$ary_request);
        $this->display();
    }
	
}