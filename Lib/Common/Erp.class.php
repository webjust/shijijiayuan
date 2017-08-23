<?php
/**
 * 连接erp的类，api和中心
 * @since 7.0
 * @version 1.0
 * @author Jerry
 * @date 2012-1-5
 */
class Erp {
    private $str_con_type = 'API';
    protected $int_page_size = 20;
    /**
     * 
     * 构造
     * @author Jerry
     * @date 2012-1-5
     */
    public function __construct() {
        $ary_erp_conf = D('SysConfig')->getConfigs('GY_ERP', 'GY_ERP_CON_TYPE');
        if(!empty($ary_erp_conf['GY_ERP_CON_TYPE']['sc_value'])) {
            $this->str_con_type = $ary_erp_conf['GY_ERP_CON_TYPE']['sc_value'];
        }
    }
    /**
     * 调用的统一入口
     * @author Jerry
     * @param string $str_method <p>请求的方法名称</p>
     * @param array $ary_param <p>调用传入的参数</p>
     * @date 2012-1-5
     */
    public function request($str_method, $ary_param) {
        $ary_erp_conf = D('SysConfig')->getConfigs('GY_ERP', 'GY_ERP_CON');
        if(1 === (int)$ary_erp_conf['GY_ERP_CON']['sc_value']) {
            if('request' != $str_method) {
                $obj_con = $this->createConObject();
                if(method_exists($obj_con, $str_method)) {
                    return $obj_con->$str_method($ary_param);
                } else {
                    return $this->return_error('不存在的方法：'.$str_method, 'Erp_request_003');
                }
            } else {
                return $this->return_error('不合适的str_method参数', 'Erp_request_002');
            }
        } else {
            return $this->return_error('系统没有开启ERP连接', 'Erp_request_001');
        }
    }
    /**
     * 
     * 生成调取api或者数据平台数据的对象，该对象根据配置文件中的FX_ERP_CON_TYPE确定
     * @author Jerry
     */
    private function createConObject() {
        if($this->str_con_type == 'DP') {
            //连接数据平台
            return new ErpDp($this);
        } else {
            //连接api
            return new ErpApi($this);
        }
    }
    /**
     * 
     * 返回错误结果
     * @author Jerry
     * @param string $str_msg 提示信息
     * @param string $str_code 类名_方法名_编号
     */
    public function return_error($str_msg, $str_code) {
        return array(
            'SUCCESS' => 0,
            'errMsg'  => $str_msg,
            'errCode' => $str_code
        );
    }
    
    /**
     * 返回正确结果
     * @author Jerry
     * @param array $ary_data 要返回的数据信息
     * @param int $int_total_rows 商品总数
     */
    public function return_success($ary_data, $int_total_rows) {
        return array(
            'SUCCESS' => 1,
            'errMsg'  => '',
            'errCode' => '',
            'DATA' => $ary_data,
            'TOTAL_ROWS' => $int_total_rows
        );
    }
}