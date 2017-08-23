<?php
/**
 * 工行在线支付类
 *
 * @package Common
 * @subpackage Payments
 * @stage 7.5
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-03-15
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
 class ICBCPAY extends Payments implements IPayments {
    /**
     * 设置支付方式的配置信息
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-01-17
     * @param array $param 支付方式的配置数组
     */
    public function setCfg($param = array()) {
        $config = array();
        $config['merAcct'] = $param['merAcct'];
        $config['merID'] = $param['merID'];
        $config['creditType'] = $param['creditType'];
        $config['merHint'] = $param['merHint'];
        $config['installmentTimes'] = $param['installmentTimes'];
        $this->config = $config;
    }
    
    /**
     * 工行支付网关请求参数
     * 
     * @param o_id 订单id
     * @authoe Joe <qianyijun@guanyisoft.com>
     * @date 2013-08-21
     */
    public function pay($o_id){
        $ary_order = parent::pay($o_id);
        $merUrl = U('Ucenter/Payment/synPayReturn?code=' . $this->code, '', true, false, true);
        $TDT = $this->get_code($ary_order,$merUrl);
        $tranData = base64_encode($TDT);
        //初始化工行支付对象
        $icbcPayObj= new com('ICBCEBANKUTIL.B2CUtil');
        $rc=$icbcPayObj->init(FXINC.'/Lib/Common/Payments/icbcpay/clz.crt',FXINC.'/Lib/Common/Payments/icbcpay/clz.crt',FXINC.'/Lib/Common/Payments/icbcpay/clz.key',$TDT);
        if($rc != 0){
            $errorCode = "初始化失败 调试代码:".$icbcPayObj->getRC();
            return $errorCode;
        }//echo "<pre>";print_r($icbcPayObj);die();
        //签名
        $merSignMsg = '';
        $qianMing = $icbcPayObj->signC($TDT, strlen($TDT));
        if($qianMing == ''){
            $errorCode = "签名失败! 调试代码:".$icbcPayObj->getRC();
            return $errorCode;
        }else{
            $merSignMsg = base64_encode($qianMing);
        }
        //验证签名
        $qm_ok = $icbcPayObj->verifySignC($TDT, strlen($TDT), $qianMing, strlen($qianMing));
        if($qm_ok != 0){
            $errorCode = "签名验证失败! 调试代码:".$icbcPayObj->getRC();
            return $errorCode;
        }
        //获取商户证书
        $cert = $icbcPayObj->getCert(1);
        if($cert == ''){
            $errorCode = "获取商户证书失败! 调试代码:".$icbcPayObj->getRC();
            return $errorCode;
        }
        //商城证书公钥
        $file_handle = fopen(FXINC.'/Lib/Common/Payments/icbcpay/clz.crt', "r");
        $line = '';
        while (!feof($file_handle)) {
           $line .= fgets($file_handle);
        }
        $merCert = base64_encode($line);
        fclose($file_handle);
        $data = array('tranData'=>$tranData,
                      'merSignMsg'=>$merSignMsg,
                      'merCert'=>$merCert);
        $this->assign($data);
        //订单预处理有事务此处要提交
        M('')->commit();
        $this->display("Ucenter:Pay:ICBCPAY");
        
    }
    
    /**
     * 获取tranData明文数据，xml格式
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-28
     */
    private function get_code($order,$merUrl){
        $order_item = M('orders_items',C('DB_PREFIX'),'DB_CUSTOM')->where(array('o_id'=>$order['o_id']))->find();
        $strReturnCode = '';
        $amt = sprintf('%.2f', $order['o_all_price']);
        $ary_amt = explode('.',$amt);
        $TDT = '<?xml version="1.0" encoding="GBK" standalone="no"?>';
        $TDT .= '<B2CReq>';
        
        $TDT .= '<interfaceName>ICBC_PERBANK_B2C</interfaceName>';
        $TDT .= '<interfaceVersion>1.0.0.11</interfaceVersion>';
        $TDT .= '<orderInfo>';
            $TDT .= '<orderDate>'.date('YmdHis').'</orderDate>';//支付时间
            $TDT .= '<curType>001</curType>';//支付币种  001为人民币
            $TDT .= '<merID>'.$this->config['merID'].'</merID>';//商户代码
            $TDT .= '<subOrderInfoList>';
            $TDT .= '<subOrderInfo>';
                $TDT .= "<orderid>{$order['o_id']}</orderid>";//订单号
                $TDT .= '<amount>'.$ary_amt[0].$ary_amt[1].'</amount>';//订单金额
                $TDT .= '<installmentTimes>'.$this->config['installmentTimes'].'</installmentTimes>';//分期付款
                $TDT .= '<merAcct>'.$this->config['merAcct'].'</merAcct>';//商户帐号
                $TDT .= "<goodsID>{$order_item['g_sn']}</goodsID>";//商品编号
                $TDT .= "<goodsName>{$order_item['oi_g_name']}</goodsName>";//商品名称
                $TDT .= "<goodsNum>{$order_item['oi_nums']}</goodsNum>";//商品数量
                $TDT .= "<carriageAmt>{$order['o_cost_freight']}</carriageAmt>";//已含运费金额
            $TDT .= '</subOrderInfo>';
            $TDT .= '</subOrderInfoList>';
        $TDT .= '</orderInfo>';
        
        $TDT .= '<custom>';
        $TDT .= '<verifyJoinFlag>0</verifyJoinFlag>';//是否检验联名标志 D
        $TDT .= '<Language>ZH_CN</Language>';//语种  中文 ZH_CN
        $TDT .= '</custom>';
        
        $TDT .= '<message>';
        $TDT .= '<creditType>'.$this->config['creditType'].'</creditType>'; //订单支付的银行卡种类 0只允许借记卡 1只允许信用卡 2借记卡和信用卡都可以支付
        $TDT .= '<notifyType>HS</notifyType>'; //通知类型
        $TDT .= '<resultType>0</resultType>'; //结果发送类型
        $TDT .= '<goodsType>1</goodsType>'; //商品类型 1实物商品 0虚拟商品
        $TDT .= '<merOrderRemark></merOrderRemark>'; //订单备注
        $TDT .= '<merHint>'.$this->config['merHint'].'</merHint>';//商城提示
        $TDT .= '<remark1></remark1>';//备注字段1
        $TDT .= '<remark2></remark2>';//备注字段2
        $TDT .= "<merURL>{$merUrl}</merURL>";//处理完成后 跳转到的地址 D
        $TDT .= '<merVAR>TEST</merVAR>';//商户变量  会按原样返回
        $TDT .= '</message>';
        $TDT .= '</B2CReq>';
        return $TDT;
    }



 }