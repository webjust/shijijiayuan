<?php

/**
 * 后台财务相关控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class FinancialAction extends AdminAction
{

    /**
     * 本控制器初始化操作
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-13
     */
    public function _initialize ()
    {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU6_1'));
    }

    /**
     * 默认控制器，需要重定向
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-11-13
     * @todo 暂时重定向到线下收款账户列表页，再等调整
     */
    public function index ()
    {
        $this->redirect(u('Admin/BalanceInfo/pageList'));
    }
    
    // ## 线下设置 ##############################################################
    
    /**
     * 线下收款帐号列表页
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-13
     */
    public function pageListOffline ()
    {
        $this->getSubNav(7, 1, 20);
        $Account = D('Account');
        $data['list'] = $Account->select();
        $this->assign($data);
        $this->display();
    }

    /**
     * 新增线下收款帐号页面
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-13
     */
    public function pageAddOffline ()
    {
        $this->getSubNav(7, 1, 20);
        $this->display();
    }

    /**
     * 执行新增线下收款帐号操作
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-13
     */
    public function doAddOffline ()
    {
        $Account = D('Account');
        $data = $Account->create();
        $res = $Account->data($data)->add();
        if ($res) {
            if ((int) $data['a_default'] == 1) {
                $Account->where(" `a_id` <> $res ")
                    ->data(array(
                        'a_default' => 0
                ))
                    ->save();
            }
            $this->success('线下收款方式新增成功', U('Admin/Financial/pageListOffline'));
        } else {
            $this->error('保存线下收款方式失败');
        }
    }

    /**
     * 删除线下支付
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function doDelOffline ()
    {
        $Account = D('Account');
        $a_id = $this->_get('a_id');
        
        if (is_array($a_id)) {
            $where = array(
                    'a_id' => array(
                            'IN',
                            $a_id
                    )
            );
        } else {
            $where = array(
                    'a_id' => $a_id
            );
        }
        
        if ($Account->where($where)->delete()) {
            $this->success('删除线下支付帐号成功');
        } else {
            $this->error('删除线下支付帐号失败');
        }
    }

    /**
     * 修改线下收款帐号页面
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-14
     */
    public function pageEditOffline ()
    {
        $this->getSubNav(7, 1, 20, '修改线下支付设置');
        
        $aid = $this->_get('a_id');
        $Account = D('Account');
        $data['info'] = $Account->where(array(
                'a_id' => $aid
        ))->find();
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行修改线下收款帐号
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function doEditOffline ()
    {
        $Account = D('Account');
        $data = $Account->create();
        $data['a_update_time'] = date('Y-m-d H:i:s');
        $res = $Account->where(array(
                'a_id' => $data['a_id']
        ))
            ->data($data)
            ->save();
        if ($res) {
            if ((int) $data['a_default'] == 1) {
                $Account->where(" `a_id` <> {$data['a_id']} ")
                    ->data(array(
                        'a_default' => 0
                ))
                    ->save();
            }
            $this->success('线下收款方式修改成功', U('Admin/Financial/pageListOffline'));
        } else {
            $this->error('保存线下收款方式失败');
        }
    }

    /**
     * 设置线下支付方式为默认
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-21
     */
    public function doSetOnline ()
    {
        $Account = D('Account');
        $aid = $this->_get('aid');
        if ($Account->where(" `a_id` <> $aid ")
            ->data(array(
                'a_default' => 0,
                'a_update_time' => date('Y-m-d H:i:s')
        ))
            ->save() &&
                 $Account->where(" `a_id` = $aid ")
                    ->data(
                        array(
                                'a_default' => 1,
                                'a_update_time' => date('Y-m-d H:i:s')
                        ))
                    ->save()) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }
    
    // ## 线上设置 ##############################################################
    
    /**
     * 线上支付帐号列表页
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function pageListOnline ()
    {
        $this->getSubNav(7, 1, 10);
        $Payment = D('PaymentCfg');
        $data['list'] = $Payment->order('pc_position asc')->select();
        $types = $Payment->getTypes();
        foreach ($data['list'] as $k => $v) {
            // 0为无需配置 1为需要配置
            $data['list'][$k]['hasCfg'] = $types[$v['pc_abbreviation']]['type'];
        }
        // dump($data);die;
        $this->assign($data);
        $this->display();
    }

    /**
     * 线上支付配置页面
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function pageConfigOnline (){
        $this->getSubNav(7, 1, 10, '配置线上支付方式');
        $code = $this->_get('code');
        
        $Payment = D('PaymentCfg');
        $data['info'] = $Payment->where(array('pc_abbreviation' => $code))->find();
        if (false != $data['info']) {
            $data['info']['config'] = json_decode($data['info']['pc_config'], TRUE);
        }
        $this->assign($data);
		$host = M('sys_config',C('DB_PREFIX'),'DB_CUSTOM')->field('sc_value')->where(array('sc_module'=>'GY_SHOP','sc_key'=>'GY_SHOP_HOST'))->find();
		$this->assign('hostUrl',$host['sc_value'].'Wap/Orders/');
        $this->display('Admin:Payment:' . $code);
    }

    /**
     * 保存线上支付配置
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function doConfigOnline ()
    {
        $code = $this->_post('code');
        if(!$code) {
            $this->error('参数【code】不能为空');
        }
        $Payment = D('PaymentCfg');
        if ($code == 'BOCOMPAY') {
            $data = $this->_doBOCOMConfig();
        }else if ($code == 'CHINAPAYV5') {
            $data = $this->_doChinaPayV5Config();
        }
        else if ($code == 'CHINAPAY') {
            $array_files = $_FILES;
            // 设置上传路径
            $str_path = APP_PATH . 'Lib/Common/Payments/chinapay/' . CI_SN . '/';
            if (! is_dir($str_path)) {
                @mkdir($str_path, 0777, 1);
            }
            // 上传MerPrk文件
            if (isset($array_files['MerPrk']['name']) &&
                     ! empty($array_files['MerPrk']['name'])) {
                $data['MerPrk']['upload_time'] = date('Y-m-d H:i:s');
                // 验证文件有效性
                if (substr(strrchr($array_files['MerPrk']['name'], '.'), 1) !=
                         'key') {
                    $this->error('MerPrk请上传key文件');
                }
                // 目标文件
                // $str_merprk_path =
                // $str_path."MerPrK_808080201302169_20130812165712.key";
                $str_merprk_path = $str_path . $array_files['MerPrk']['name'];
                $data['MerPrk']['upload_path'] = $str_merprk_path;
                // 执行上传文件
                if (! move_uploaded_file($array_files['MerPrk']['tmp_name'], 
                        $str_merprk_path)) {
                    $this->error('上传MerPrk失败，请检查php.ini上传文件最大值');
                }
            } else {
                $this->error('请上传MerPrk文件');
            }
            // 上传netpayclient文件
            if (isset($array_files['netpayclient']['name']) &&
                     ! empty($array_files['netpayclient']['name'])) {
                $data['netpayclient']['upload_time'] = date('Y-m-d H:i:s');
                // 验证文件有效性
                if (substr(strrchr($array_files['netpayclient']['name'], '.'), 
                        1) != 'php') {
                    $this->error('netpayclient请上传php文件');
                }
                // 目标文件
                $str_netpayclient_path = $str_path . "netpayclient.php";
                $data['netpayclient']['upload_path'] = $str_netpayclient_path;
                // 执行上传文件
                if (! move_uploaded_file(
                        $array_files['netpayclient']['tmp_name'], 
                        $str_netpayclient_path)) {
                    $this->error('上传netpayclient失败，请检查php.ini上传文件最大值');
                }
            } else {
                $this->error('请上传netpayclient文件');
            }
            if (isset($array_files['PgPubk']['name']) &&
                     ! empty($array_files['PgPubk']['name'])) {
                $data['PgPubk']['upload_time'] = date('Y-m-d H:i:s');
                // 验证文件有效性
                if (substr(strrchr($array_files['PgPubk']['name'], '.'), 1) !=
                         'key') {
                    $this->error('PgPubk请上传key文件');
                }
                // 目标文件
                $str_pgpubk_path = $str_path . "PgPubk.key";
                $data['PgPubk']['upload_path'] = $str_pgpubk_path;
                // 执行上传文件
                if (! move_uploaded_file($array_files['PgPubk']['tmp_name'], 
                        $str_pgpubk_path)) {
                    $this->error('上传netpayclient失败，请检查php.ini上传文件最大值');
                }
            } else {
                $this->error('请上传PgPubk文件');
            }
        } else {
            $data = $this->_post();
            if ($code == 'ICBC') {
                $array_files = $_FILES;
                // 设置上传路径
                $str_path = APP_PATH . 'Lib/Common/Payments/icbc/' . CI_SN . '/';
                if (! is_dir($str_path)) {
                    @mkdir($str_path, 0777, 1);
                }
                // 上传工行公钥文件
                if (isset($array_files['icbcFile']['name']) &&
                         ! empty($array_files['icbcFile']['name'])) {
                    $data['icbcFile']['upload_time'] = date('Y-m-d H:i:s');
                    // 验证文件有效性
                    if (substr(strrchr($array_files['icbcFile']['name'], '.'), 
                            1) != 'crt' && substr(strrchr($array_files['icbcFile']['name'], '.'), 
                            1) != 'cer' ) {
                        $this->error('请上传工行公钥文件');
                    }
                    // 目标文件
                    // $str_merprk_path =
                    // $str_path."MerPrK_808080201302169_20130812165712.key";
                    $str_merprk_path = $str_path .
                             $array_files['icbcFile']['name'];
                    $data['icbcFile']['upload_path'] = $str_merprk_path;
                    // 执行上传文件
                    if (! move_uploaded_file(
                            $array_files['icbcFile']['tmp_name'], 
                            $str_merprk_path)) {
                        $this->error('上传上传工行公钥失败，请检查php.ini上传文件最大值');
                    }
                } else {
                    $this->error('请上传工行公钥文件');
                }
                // 上传商户公钥文件文件
                if (isset($array_files['certFile']['name']) &&
                         ! empty($array_files['certFile']['name'])) {
                    $data['certFile']['upload_time'] = date('Y-m-d H:i:s');
                    // 验证文件有效性
                    if (substr(strrchr($array_files['certFile']['name'], '.'), 
                            1) != 'crt' && substr(strrchr($array_files['certFile']['name'], '.'), 
                            1) != 'cer') {
                        $this->error('请上传商户公钥文件');
                    }
                    // 目标文件
                    $str_netpayclient_path = $str_path .
                             $array_files['certFile']['name'];
                    $data['certFile']['upload_path'] = $str_netpayclient_path;
                    // 执行上传文件
                    if (! move_uploaded_file(
                            $array_files['certFile']['tmp_name'], 
                            $str_netpayclient_path)) {
                        $this->error('上传商户公钥文件失败，请检查php.ini上传文件最大值');
                    }
                } else {
                    $this->error('请上传商户公钥文件');
                }
                // 上传商户私钥文件
                if (isset($array_files['keyFile']['name']) &&
                         ! empty($array_files['keyFile']['name'])) {
                    $data['keyFile']['upload_time'] = date('Y-m-d H:i:s');
                    // 验证文件有效性
                    if (substr(strrchr($array_files['keyFile']['name'], '.'), 1) !=
                             'key') {
                        $this->error('请上传商户私钥');
                    }
                    // 目标文件
                    $str_pgpubk_path = $str_path .
                             $array_files['keyFile']['name'];
                    ;
                    $data['keyFile']['upload_path'] = $str_pgpubk_path;
                    // 执行上传文件
                    if (! move_uploaded_file(
                            $array_files['keyFile']['tmp_name'], 
                            $str_pgpubk_path)) {
                        $this->error('上传商户私钥文件失败，请检查php.ini上传文件最大值');
                    }
                } else {
                    $this->error('请上传商户私钥文件');
                }
            }else{
				if ($code == 'WEIXIN') {
					//暂时没有要处理的
					//dump($data);die();
				}	
			}
        }
        //如果等于货到付款
		if($code == 'DELIVERY'){
			$pc_custom_name = trim($this->_post('pc_custom_name'));
			$pc_memo = trim($this->_post('pc_memo'));
			$save_data = array(
					'pc_custom_name' => $pc_custom_name,
					'pc_memo'=>$pc_memo,
					'pc_last_modify'=>date('Y-m-d H:i:s')
			);
			$result = $Payment->where(array(
					'pc_abbreviation' => $code
			))->data($save_data)->save();				
		}else{
			// 数据处理		
			$ary_data = array('WAPALIPAY','APPALIPAY');
			if(in_array($code,$ary_data)){
				$op = '_do'.$code.'Config';
				$data = $this->$op();
			}
			$Pay = $Payment::factory($code, $data);
			$cfg = $Pay->getCfg();
			$save_data = array(
					'pc_config' => json_encode($cfg),
					'pc_last_modify' => date('Y-m-d h:i:s'),
					'pc_fee' => $data['pc_fee']
			);
			$result = $Payment->where(array(
					'pc_abbreviation' => $code
			))
				->data($save_data)
				->save();			
		}
        if ($result) {
            $this->success('保存成功', U('Admin/Financial/pageListOnline'));
        } else {
            $this->error('保存失败');
        }
    }

    private function _doAPPALIPAYConfig(){
        return $this->_doWAPALIPAYConfig();
    }

    /**
     * 手机网页版支付宝支付
     * @author Tom <helong@guanyisoft.com>
     * @date 2015-03-24
     */
    private function _doWAPALIPAYConfig(){
        $data = $this->_post();
        if($data['pay_encryp'] == 'MD5'){
            if(empty($data['pay_safe_code'])) $this->error('请填写交易安全校验码');
        }

        if($data['pay_encryp'] == '0001' || $data['pay_encryp'] == 'RSA'){
            $array_files = $_FILES;
            // 设置上传路径
            $str_path = APP_PATH . 'Lib/Common/Payments/'.$data['code'].'/' . CI_SN . '/';
            if (! is_dir($str_path)) {
                @mkdir($str_path, 0777, 1);
            }
            if (!isset($array_files['alipay_public_key']['name']) || empty($array_files['alipay_public_key']['name']) || pathinfo($array_files['alipay_public_key']['name'], PATHINFO_EXTENSION) != 'pem'){
                $this->error('请正确上传支付宝公钥!');
            }
            if (!isset($array_files['shop_public_key']['name']) || empty($array_files['shop_public_key']['name']) || pathinfo($array_files['shop_public_key']['name'], PATHINFO_EXTENSION) != 'pem'){
                $this->error('请正确上传商户公钥!');
            }
            if (!isset($array_files['shop_private_key']['name']) || empty($array_files['shop_private_key']['name']) || pathinfo($array_files['shop_private_key']['name'], PATHINFO_EXTENSION) != 'pem'){
                $this->error('请正确上传商户私钥!');
            }
            $prefix_name = strtolower(substr($data['code'],0,3));
            foreach($array_files as $key => $file){
                $data[$prefix_name.'_'.$key]['upload_path'] = $path = $str_path . $prefix_name .'_' . $key . '.pem';
                $data[$prefix_name.'_'.$key]['upload_time'] = date('Y-m-d H:i:s');
                // 执行上传文件
                if (!move_uploaded_file($file['tmp_name'], $path)){
                    $this->error('上传文件失败，请检查php.ini上传文件最大值');
                }
            }
        }
        return $data;
    }

    /**
     * 银联支付v5.0.0配置
     */
    private function _doChinaPayV5Config ()
    {
        $code = $this->_post('code');
        $Payment = D('PaymentCfg');
        $where = array(
            'pc_abbreviation' => $code
        );
        $payment_cfg = $Payment->getPayCfgId($where);
        if($payment_cfg && !empty($payment_cfg['pc_config'])) {
            $payment_config = json_decode($payment_cfg['pc_config'], true);
        }

        //商户代码
        if(isset($_POST['MERCHANT_ID'])) {
            $data['MERCHANT_ID'] = trim($_POST['MERCHANT_ID']);
        }else{
            $this->error('请输入商户代码');
        }

        $array_files = $_FILES;
        // 设置上传路径
        $str_path = APP_PATH . 'Lib/Common/Payments/ChinaPayV5/' . CI_SN . '/';
        if (! is_dir($str_path)) {
            @mkdir($str_path, 0777, 1);
        }
        // 上传签名证书文件
        if (isset($array_files['SIGN_CERT_PATH']['name']) &&
                 ! empty($array_files['SIGN_CERT_PATH']['name'])) {
            $data['SIGN_CERT_PATH']['upload_time'] = date('Y-m-d H:i:s');
            // 验证文件有效性
            if (strtolower(substr(strrchr($array_files['SIGN_CERT_PATH']['name'], '.'), 1)) !=
                     'pfx') {
                $this->error('请上传后缀为.pfx的签名证书文件');
            }
            // 目标文件
            // $str_sign_cert_path =
            // $str_path."SIGN_CERT_PATH_808080201302169_20130812165712.key";
            $str_sign_cert_path = $str_path .
                                    $array_files['SIGN_CERT_PATH']['name'];
            $data['SIGN_CERT_PATH']['upload_path'] = $str_sign_cert_path;
            // 执行上传文件
            if (! move_uploaded_file($array_files['SIGN_CERT_PATH']['tmp_name'], 
                    $str_sign_cert_path)) {
                $this->error('上传签名证书失败，请检查php.ini上传文件最大值');
            }
        } elseif(!empty($payment_config['SIGN_CERT_PATH'])){
            $data['SIGN_CERT_PATH'] = $payment_config['SIGN_CERT_PATH'];
        }else {
            $this->error('请上传签名证书文件');
        }
        
        //签名证书密码
        if(isset($_POST['SIGN_CERT_PWD'])) {
            $data['SIGN_CERT_PWD'] = trim($_POST['SIGN_CERT_PWD']);
        }else{
            $this->error('请输入签名证书密码');
        }
        
        // 上传验签证书文件
        if (isset($array_files['VERIFY_CERT_PATH']['name']) &&
                 ! empty($array_files['VERIFY_CERT_PATH']['name'])) {
            $data['VERIFY_CERT_PATH']['upload_time'] = date('Y-m-d H:i:s');
            // 验证文件有效性
            if (strtolower(substr(strrchr($array_files['VERIFY_CERT_PATH']['name'], '.'), 
                    1)) != 'cer') {
                $this->error('请上传.cer格式验签证书文件');
            }
            // 目标文件
            $str_verify_cert_path = $str_path . "verify_sign_acp.cer";
            $data['VERIFY_CERT_PATH']['upload_path'] = $str_verify_cert_path;
            // 执行上传文件
            if (! move_uploaded_file(
                    $array_files['VERIFY_CERT_PATH']['tmp_name'], 
                    $str_verify_cert_path)) {
                $this->error('上传验签证书失败，请检查php.ini上传文件最大值');
            }
        } elseif(!empty($payment_config['VERIFY_CERT_PATH'])){
            //验签证书不变
            $data['VERIFY_CERT_PATH'] = $payment_config['VERIFY_CERT_PATH'];
        }else {
            $this->error('请上传验签证书文件');
        }
        
        // 上传密码加密证书文件
        if (isset($array_files['ENCRYPT_CERT_PATH']['name']) &&
                 ! empty($array_files['ENCRYPT_CERT_PATH']['name'])) {
            $data['ENCRYPT_CERT_PATH']['upload_time'] = date('Y-m-d H:i:s');
            // 验证文件有效性
            if (substr(strrchr($array_files['ENCRYPT_CERT_PATH']['name'], '.'), 
                    1) != 'cer') {
                $this->error('请上传.cer格式密码加密证书文件');
            }
            // 目标文件
            $str_verify_cert_path = $str_path . "encrypt.cer";
            $data['ENCRYPT_CERT_PATH']['upload_path'] = $str_verify_cert_path;
            // 执行上传文件
            if (! move_uploaded_file(
                    $array_files['ENCRYPT_CERT_PATH']['tmp_name'], 
                    $str_verify_cert_path)) {
                $this->error('上传密码加密证书文件，请检查php.ini上传文件最大值');
            }
        }elseif(!empty($payment_config['ENCRYPT_CERT_PATH'])){
            //密码加密证书文件不变
            $data['ENCRYPT_CERT_PATH'] = $payment_config['ENCRYPT_CERT_PATH'];
        }
        
                
        return $data;
    }

    /**
     * 交行支付配置
     */
    private function _doBOCOMConfig () {
        $code = $this->_post('code');
        $Payment = D('PaymentCfg');
        $where = array(
            'pc_abbreviation' => $code
        );
        $payment_cfg = $Payment->getPayCfgId($where);
        if($payment_cfg && !empty($payment_cfg['pc_config'])) {
            $payment_config = json_decode($payment_cfg['pc_config'], true);
        }

        //商户代码
        if(isset($_POST['MERCHANT_ID'])) {
            $data['MERCHANT_ID'] = trim($_POST['MERCHANT_ID']);
        }else{
            $this->error('请输入商户代码');
        }
        //获取当前系统信息
        $ary_uname = explode(" ",php_uname());
        $uname = strtolower($ary_uname[0]);

        $array_files = $_FILES;
        // 设置上传路径
        $str_path = APP_PATH . 'Public/socket/'.$uname.'/cert/';
        if (! is_dir($str_path)) {
            @mkdir($str_path, 0777, 1);
        }
        // 上传签名证书文件
        if (isset($array_files['SIGN_CERT_PATH']['name']) &&
                 ! empty($array_files['SIGN_CERT_PATH']['name'])) {
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('pfx');// 设置附件上传类型
			$upload->savePath =  $str_path;// 设置附件上传目录
			$upload->saveRule =  '';// 设置文件命名规则
            $upload->uploadReplace = true;
			if(!$info = $upload->uploadOne($array_files['SIGN_CERT_PATH'])) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				//$info =  $upload->getUploadFileInfo();
                // 目标文件
                $str_sign_cert_path = $str_path.$info[0]['savename'];
                $data['SIGN_CERT_PATH']['upload_path'] = $str_sign_cert_path;
                $data['SIGN_CERT_PATH']['upload_time'] = date('Y-m-d H:i:s');
			}
        } elseif(!empty($payment_config['SIGN_CERT_PATH'])){
            $data['SIGN_CERT_PATH'] = $payment_config['SIGN_CERT_PATH'];
        }else {
            $this->error('请上传商户签名证书文件');
        }
        //签名证书密码
        if(isset($_POST['SIGN_CERT_PWD'])) {
            $data['SIGN_CERT_PWD'] = trim($_POST['SIGN_CERT_PWD']);
        }else{
            $this->error('请输入商户签名证书密码');
        }
        // 上传验签证书文件
        if (isset($array_files['VERIFY_CERT_PATH']['name']) &&
                 ! empty($array_files['VERIFY_CERT_PATH']['name'])) {
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('cer');// 设置附件上传类型
			$upload->savePath =  $str_path;// 设置附件上传目录
			$upload->saveRule =  '';// 设置文件命名规则
            $upload->uploadReplace = true;
			if(!$info = $upload->uploadOne($array_files['VERIFY_CERT_PATH'])) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				//$info =  $upload->getUploadFileInfo();
                // 目标文件
                $str_verify_cert_path = $str_path.$info[0]['savename'];
                $data['VERIFY_CERT_PATH']['upload_path'] = $str_verify_cert_path;
                $data['VERIFY_CERT_PATH']['upload_time'] = date('Y-m-d H:i:s');
			}
        }elseif(!empty($payment_config['VERIFY_CERT_PATH'])){
            //验签证书不变
            $data['VERIFY_CERT_PATH'] = $payment_config['VERIFY_CERT_PATH'];
        }else {
            $this->error('请上传验签证书文件');
        }
        if(!empty($data)){
            //获取当前系统信息
            $ary_uname = explode(" ",php_uname());
            $uname = strtolower($ary_uname[0]);
            // 设置配置文件ini
            $iniXml = APP_PATH . 'Public/socket/'.$uname.'/ini/';
            $intName = 'B2CMerchantSocket.xml';
            if (! is_dir($iniXml)) {
                @mkdir($iniXml, 0777, 1);
            }
            $iniXml .= $intName;
            $certFile = basename($data['SIGN_CERT_PATH']['upload_path']);
            //如果是测试的就设置为T
            //$setting = ( $data['MERCHANT_ID'] == "301310063009501" || $data['MERCHANT_ID'] == "301310063009502" ) ? "T" : "P";
            $ary_xml = array();
            $ary_certs_null = array();
            $ary_xml['Setting'] = 'P';
            $ary_xml['ConnetionTimeOut'] = '30000';
            $ary_xml['ReadTimeOut'] = '30000';
            $ary_xmls = xml2array($iniXml);
            if(!empty($ary_xmls['Certs'])){
                $ary_certs = array();
                $array_xmls = isset($ary_xmls['Certs']['CertInf'][0]) ? $ary_xmls['Certs']['CertInf'] : $ary_xmls['Certs'];
                foreach($array_xmls as $k => $certs){
                    if($certs['MerchantCertFile'] == $certFile){
                        continue;
                    }
                    $ary_certs[] = $certs;
                }
                $ary_certs[] = array('MerchantCertFile' => $certFile,'MerchantCertPassword' => $data['SIGN_CERT_PWD']);
                $ary_xml['Certs']['CertInf'] = $ary_certs;
            }else{
                $certs['MerchantCertFile'] = $certFile;
                $certs['MerchantCertPassword'] = $data['SIGN_CERT_PWD'];
                $ary_xml['Certs']['CertInf'] = $certs;
            }
            $str_xml = toXml($ary_xml,array('root_tag'=>'BOCOMB2C'));
            $exitFile = file_put_contents($iniXml,$str_xml);
            if(!$exitFile) return array("result"=>false,"message"=>"配置文件".$iniXml."写入失败，请重试!");
        }
        return $data;
    }

    /**
     * 设置线上支付方式是否启用
     * 
     * @author zuo <zuojianghua@guanyisoft.com>
     *         @date 2013-01-17
     */
    public function doStatusOnline ()
    {
        $code = $this->_get('code');
        $status = $this->_get('status');
        $Payment = D('PaymentCfg');
        $result = $Payment->where(array(
                'pc_abbreviation' => $code
        ))
            ->data(array(
                'pc_status' => $status
        ))
            ->save();
        if ($result) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * 会员结余款记录
     * 
     * @author listen
     *         @date 2013-01-21
     */
    public function pageListDeposits ()
    {
        $this->getSubNav(7, 0, 10);
        $data = $this->_param();
        $ary_where = array(
                'm_verify' => 2
        );
        if (isset($data['m_name']) && ! empty($data['m_name'])) {
            $ary_where['m_name'] = array(
                    'like',
                    '%' . $data['m_name'] . '%'
            );
        }
        $ary_members_account = M('view_members', C('DB_PREFIX'), 'DB_CUSTOM')->where(
                $ary_where)->select();
        $count = M('view_members', C('DB_PREFIX'), 'DB_CUSTOM')->where(
                $ary_where)->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        if (! empty($ary_members_account) && is_array($ary_members_account)) {
            foreach ($ary_members_account as $k => $v) {
                $ary_data = M('running_account', C('DB_PREFIX'), 'DB_CUSTOM')->where(
                        array(
                                'ra_type' => 0,
                                'm_id' => $v['m_id']
                        ))->sum('ra_money');
                $ary_members_account[$k]['all_account'] = $ary_data;
            }
        }
        $this->assign('ary_members_account', $ary_members_account);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 预存款充值页面
     * 
     * @author listen
     *         @date 2013-01-21
     */
    public function pageAddDeposits ()
    {
        $this->getSubNav(7, 0, 10, '预存款充值');
        $mid = $this->_get('m_id');
        $this->assign('mid', $mid);
        $this->display();
    }

    /**
     * 预存款充值操作
     * 
     * @author listen
     *         @date 2013-01-21
     */
    public function doAddDeposits ()
    {
        $ary_data = $this->_post();
        $obj_members = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_members->startTrans();
        $date = date('Y-m-d h:i:s');
        // 不连erp
        if (! isset($ary_data['m_id']) && ! isset($ary_data['m_balance'])) {
            $this->error('充值失败');
        }
        if (! is_numeric($ary_data['m_balance']) || $ary_data['m_balance'] < 0) {
            $this->error('充值金额必须是正数');
        }
        
        $ary_members = $obj_members->where(array(
                'm_id' => $ary_data['m_id']
        ))->find();
        if (empty($ary_members)) {
            $this->error('会员不存在');
        }
        // 余额
        $before_money = $ary_members['m_balance'];
        // 更新余额记录
        $after_money = $ary_members['m_balance'] + $ary_data['m_balance'];
        $int_return_members = $obj_members->where(
                array(
                        'm_id' => $ary_data['m_id']
                ))->save(array(
                'm_balance' => $after_money
        ));
        if ($int_return_members <= 0) {
            $obj_members->rollback();
            $this->error('更新会员预存款失败');
        }
        $ary_account_data = array(
                'm_id' => $ary_data['m_id'],
                'ra_money' => $ary_data['m_balance'],
                'ra_type' => 0,
                'ra_before_money' => $before_money,
                '$before_money' => $after_money,
                'ra_payment_method' => '预存款',
                'ra_memo' => $ary_data['ra_memo'],
                'ra_create_time' => $date
        );
        $int_return_account = M('running_account', C('DB_PREFIX'), 'DB_CUSTOM')->data(
                $ary_account_data)->add();
        if ($int_return_account <= 0) {
            $obj_members->rollback();
            $this->error('更新预存款记录失败');
        }
        
        $obj_members->commit();
        $this->success('充值成功', U('Admin/Financial/pageListDeposits'));
    }

    /**
     * 预存款代扣页面
     * 
     * @author listen
     *         @date 2013-01-21
     */
    public function pageDeductDeposits ()
    {
        $this->getSubNav(7, 0, 10, '预存款代扣');
        $mid = $this->_get('m_id');
        $this->assign('mid', $mid);
        $this->display();
    }

    /**
     * 代扣预存操作
     * 
     * @author listen
     *         @date 2013-01-21
     */
    public function doDeductDeposits ()
    {
        $ary_data = $this->_post();
        $obj_members = M('members', C('DB_PREFIX'), 'DB_CUSTOM');
        $obj_members->startTrans();
        $date = date('Y-m-d h:i:s');
        // 不连erp
        if (! isset($ary_data['m_id']) && ! isset($ary_data['m_balance'])) {
            $this->error('代扣失败');
        }
        $ary_members = $obj_members->where(array(
                'm_id' => $ary_data['m_id']
        ))->find();
        if (empty($ary_members)) {
            $this->error('会员不存在');
        }
        // 余额
        $before_money = $ary_members['m_balance'];
        // 消费记录
        $all_cost = $ary_members['m_all_cost'];
        // 更新余额
        $after_money = $ary_members['m_balance'] - $ary_data['m_balance'];
        
        $int_return_members = $obj_members->where(
                array(
                        'm_id' => $ary_data['m_id']
                ))->save(
                array(
                        'm_balance' => $after_money,
                        'm_all_cost' => $all_cost
                ));
        if ($int_return_members <= 0) {
            $obj_members->rollback();
            $this->error('更新会员预存款失败');
        }
        $ary_account_data = array(
                'm_id' => $ary_data['m_id'],
                'ra_money' => '-' . $ary_data['m_balance'],
                'ra_type' => 1,
                'ra_before_money' => $before_money,
                '$before_money' => $after_money,
                'ra_payment_method' => '预存款',
                'ra_memo' => $ary_data['ra_memo'],
                'ra_create_time' => $date
        );
        $int_return_account = M('running_account', C('DB_PREFIX'), 'DB_CUSTOM')->data(
                $ary_account_data)->add();
        if ($int_return_account <= 0) {
            $obj_members->rollback();
            $this->error('更新预存款记录失败');
        }
        $obj_members->commit();
        $this->success('代扣成功', U('Admin/Financial/pageListDeposits'));
    }

    /**
     * 预存款审核列表
     * 
     * @author liste
     *         @date 2013-01-22
     */
    public function pageListVerify ()
    {
        $this->getSubNav(7, 4, 60);
        $page_size = 20;
        $data = $this->_param();
        $page_no = max(1, (int) $this->_param('p', '', 1));
        
        if (isset($data['user_name']) && ! empty($data['user_name'])) {
            $ary_where['fx_members.m_name'] = array(
                    'like',
                    '%' . $data['user_name'] . '%'
            );
        }
        if (isset($data['re_user_name']) && ! empty($data['re_user_name'])) {
            $ary_where['re_name'] = array(
                    'like',
                    '%' . $data['re_user_name'] . '%'
            );
        }
        if (isset($data['payment_sn']) && ! empty($data['payment_sn'])) {
            $ary_where['re_payment_sn'] = array(
                    'like',
                    '%' . $data['payment_sn'] . '%'
            );
        }
        $result = D(RechargeExamine)->pageListVerify($ary_where, $page_no, 
                $page_size);
        
        $obj_page = new Page($result['count'], 20);
        $page = $obj_page->show();
        $this->assign('data', $data);
        $this->assign('ary_examine', $result['data']);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 预存款充值详细
     * 
     * @author listen
     *         @date 2013-01-23
     */
    public function pageExamineDetails ()
    {
        $re_id = $this->_post('re_id');
        if (isset($re_id)) {
            $ary_where = array(
                    're_id' => $re_id
            );
            $ary_examine = M('recharge_examine', C('DB_PREFIX'), 'DB_CUSTOM')->where(
                    $ary_where)->find();
            if (! empty($ary_examine)) {
                $this->assign('examine', $ary_examine);
                $this->display();
            }
        }
    }

    /**
     *
     * @author Terry<wanghui@guanyisoft.com>
     *         @date 2013-06-20
     *        
     */
    public function doStatus ()
    {
        $ary_post = $this->_post();
        $action = M('recharge_examine', C('DB_PREFIX'), 'DB_CUSTOM');
		$ary_rec_result = $action->where(array(
                're_id' => $ary_post['id']
        ))->find();
		if($ary_rec_result['re_verify'] == 1){
			$this->error("此单据已审核，无需再次审核,请刷新页面重试");exit;
		}
		$action->startTrans();
        $ary_data = array(
                $ary_post['field'] => $ary_post['val'],
                're_update_time' => date('Y-m-d H:i:s'),
				're_user_id'=>$_SESSION['Admin']
        );
        if (! empty($ary_post['comments'])) {
            $ary_data['re_admin_message'] = $ary_post['comments'];
        }
        $ary_result = $action->where(array(
                're_id' => $ary_post['id']
        ))->save($ary_data);
        if (FALSE != $ary_result) {
            if ($ary_post['field'] == 're_status') {
                $action->commit();
                $this->success("审核成功");
            } elseif ($ary_post['field'] == 're_verify' && $ary_post['val'] ==
                     '2') {
                $action->commit();
                $this->success("审核成功");
            } else {
                $ary_res = $action->where(array(
                        're_id' => $ary_post['id']
                ))->find();
                if (! empty($ary_res) && is_array($ary_res)) {
                    $arr_data['m_id'] = $ary_res['m_id'];
                    $arr_data['bi_accounts_receivable'] = $ary_res['a_account_number'];
                    $arr_data['bi_accounts_bank'] = $ary_res['a_apply_bank'];
                    $arr_data['bi_payeec'] = $ary_res['a_apply_name'];
                    $arr_data['bi_type'] = '0';
                    $arr_data['bi_money'] = $ary_res['re_money'];
                    $arr_data['bi_payment_time'] = date("Y-m-d H:i:s");
                    $arr_data['u_id'] = $_SESSION[C('USER_AUTH_KEY')];
                    $arr_data['bi_create_time'] = date("Y-m-d H:i:s");
                    $arr_data['bt_id'] = '3';
                    $arr_data['bi_service_verify'] = '0';
                    $arr_data['bi_finance_verify'] = '0';
                    $arr_data['bi_sn'] = time();
                    $arr_data['bi_desc'] = '线下充值';
                    $ary_rest = D("BalanceInfo")->add($arr_data);
                    
                    if (FALSE != $ary_rest) {
                        $ary_data = array();
                        $str_sn = str_pad($ary_rest, 6, "0", STR_PAD_LEFT);
                        $ary_data['bi_sn'] = time() . $str_sn;
                        D("BalanceInfo")->where(array(
                                'bi_id' => $ary_result
                        ))
                            ->data($ary_data)
                            ->save();
                    }
                    $action->commit();
                    $this->success('审核成功');
                    exit();
                } else {
                    $action->rollback();
                    $this->error("审核失败");
                }
            }
        } else {
            $action->rollback();
            $this->error("审核失败");
        }
    }

    /**
     * 审核预存款
     * 
     * @author listen
     *         @date 2013-01-23
     *        
     */
    public function doVerify ()
    {
        $ary_post_data = $this->_post();
        $message = $ary_post_data['message'];
        $verify = $ary_post_data['verify']; // 1是审核通过，2是审核不通过
        $re_id = $ary_post_data['re_id'];
        $mid = 1; // session('user');
        $recharge = M('recharge_examine', C('DB_PREFIX'), 'DB_CUSTOM');
        $recharge->startTrans();
        $ary_data = array(
                're_user_message' => $message,
                're_verify' => $verify,
                're_user_id' => $mid
        );
        $date = date('Y-m-d h:i:s');
        $res = $recharge->where(array(
                're_id' => $re_id
        ))->save($ary_data);
        if (! $res) {
            $recharge->rollback();
            $this->ajaxReturn(false);
        }
        // 更新流水账记录
        $ary_recharge_examine = M('view_recharge_examine', C('DB_PREFIX'), 
                'DB_CUSTOM')->where(array(
                're_id' => $re_id
        ))->find();
        if (! empty($ary_recharge_examine) && is_array($ary_recharge_examine)) {
            $ary_running_account = array(
                    'ra_money' => $ary_recharge_examine['re_money'],
                    'm_id' => $ary_recharge_examine['m_id'],
                    'ra_type' => 0,
                    'ra_before_money' => $ary_recharge_examine['m_balance'],
                    'ra_after_money' => $ary_recharge_examine['m_balance'] +
                             $ary_recharge_examine['re_money'],
                            'ra_payment_method' => '预存款',
                            'ra_create_time' => $date
            );
            $ra_res = M('running_account', C('DB_PREFIX'), 'DB_CUSTOM')->add(
                    $ary_running_account);
            if (! $ra_res) {
                $recharge->rollback();
                $this->ajaxReturn(false);
            }
            // 更新会员预存款
            $menoy = $ary_recharge_examine['m_balance'] +
                     $ary_recharge_examine['re_money'];
            $ary_members = array(
                    'm_balance' => $menoy
            );
            $members = M('members', C('DB_PREFIX'), 'DB_CUSTOM')->where(
                    array(
                            'm_id' => $ary_recharge_examine['m_id']
                    ))->save($ary_members);
            if ($members) {
                $recharge->commit();
                $this->ajaxReturn(true);
                exit();
            }
        } else {
            $this->ajaxReturn(false);
            exit();
        }
    }

    /**
     * 支付方式顺序修改
     * 
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     *         @date 2013-08-01
     */
    public function doSequence ()
    {
        $this->getSubNav(7, 1, 10);
        $ary_data = $this->_post();
        foreach ($ary_data as $key => $value) {
            $code = ltrim($key, 'Sequence_');
            D(PaymentCfg)->where(array(
                    'pc_abbreviation' => $code
            ))->save(array(
                    'pc_position' => $value
            ));
        }
        $this->success('排序保存成功', 'pageListOnline');
    }

    /*
     * 线下充值作废
     * @author huhaiwei <huhaiwei@guanyisoft.com>
     * @date 2014-9-22
     */
    public function ajaxDoStatus ()
    {
        $ary_post = $this->_post();
        
        // 判断作废理由
        if (! $ary_post['re_content']) {
            $this->error('作废理由不能为空');
        }
        
        $action = M('recharge_examine', C('DB_PREFIX'), 'DB_CUSTOM');
        $ary_data = array(
                $ary_post['field'] => $ary_post['val'],
                're_content' => $ary_post['re_content'],
                're_update_time' => date('Y-m-d H:i:s')
        );
        $ary_result = $action->where(array(
                're_id' => $ary_post['re_id']
        ))->save($ary_data);
        if ($ary_result) {
            $this->ajaxReturn(true);
        }
    }
}
