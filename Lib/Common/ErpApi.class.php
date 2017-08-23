<?php
/**
 * 分销连接api的基类
 * @package Common
 * @subpackage ErpApi
 * @author Jerry 
 * @since 7.0
 * @version 1.0
 * @date 2013-1-5
 */
class ErpApi{
    private $str_api_url;        //api地址
    private $str_api_img_url;    //图片地址
    private $str_app_key;        //对接api的appkey
    public $str_shop_code;      //店铺代码
    private $int_page_size = 20;
    private $obj_erp;
    /**
     * 
     * 构造函数
     * @author Jerry
     * @date 2012-1-5
     */
    public function __construct($obj_erp = null) {
        $ary_api_conf = D('SysConfig')->getConfigs('GY_ERP_API',null,null,null,1);
        $this->str_api_url = $ary_api_conf['BASE_URL']['sc_value'];
        $this->str_api_img_url = $ary_api_conf['IMG_URL']['sc_value'];
        $this->str_app_key = $ary_api_conf['APP_KEY']['sc_value'];
        $this->str_shop_code = $ary_api_conf['SHOP_CODE']['sc_value'];
        if($obj_erp instanceof Erp){
            $this->obj_erp = $obj_erp;
        } else {
            $this->obj_erp = new Erp();
        }
        
    }
    /**
     * 
     * 请求api的出口方法
     * @param string $str_method
     * @param string $ary_param
     */
    protected function requestApi($str_method, $ary_param=array()) {
        $ary_param['appkey'] = $this->str_app_key;
        //$ary_param['condition'] .= "ZHANGHAO='".$this->str_shop_code."'";
        $ary_param['method'] = $str_method;
        $xml_result = makeRequest($this->str_api_url, $ary_param, 'POST');
        //把key全部变为大写
        return array_keys_unified(xml2array($xml_result));
    }
    
    /**
     * 获取erp商品的商品列表
     * @author Jerry
     * @date 2012-1-5
     * @return array 该方法的返回值，都是规格格式，无规格商品自动生成规格，但规格自动变成多规格格式，SKU信息中如果NO_SKU=1则代表无规格
     */
    function getGoodsPage($ary_data) {
        if(!isset($ary_data['fields']) || empty($ary_data['fields'])) {
            $str_fields = 'GUID,SPDM,SPMC,TY,QY,SJ,CJSJ,KTH,XP,TUIJIAN,ZP,ZL,BZJJ,BZSJ,CBJ,DANWEI,PFSJ,FKCCK,IMAGES,SPSM,ZHANGHAO,SL3,SL2,SL1,SL,LB_GUID,LB_CODE,LB_NAME,LB2_CODE,LB2_NAME';
        } else {
        $str_fields = $ary_data['fields'];
        }
        $ary_param = array(
            'fields'  => $str_fields,
            'page_no' => $ary_data['page'] ? $ary_data['page'] : 1,
            'page_size' => $ary_data['page_size'] ? $ary_data['page_size'] : $this->int_page_size,
            'orderby'   => strpos(strtoupper($ary_data['orderby']), $str_fields) !== false ? strtoupper($ary_data['orderby']) : 'CJSJ',
            'ordertype' => 'DESC'
        );
        $str_condition = '';
        if(!empty($ary_data['g_sn'])) {
            $str_condition .= "SPDM='".rawurlencode(trim($ary_data['g_sn'])."' and ");
        }
        if(!empty($ary_data['g_name'])) {
            $str_condition .= "SPDM LIKE '%".rawurlencode(trim($ary_data['g_sn'])."%' and ");
        }
        if(!empty($ary_data['ty'])) {
            $str_condition .= "TY='".rawurlencode((int)$ary_data['ty']."' and ");
        }
        if(!empty($ary_data['sj'])) {
            $str_condition .= "SJ='".rawurlencode((int)$ary_data['sj']."' and ");
        }
        $str_condition .= "ZHANGHAO='".$this->str_shop_code."'";
        if(!empty($str_condition)) {
            $ary_param['condition'] = $str_condition;
        }
        $ary_result = $this->requestApi('ecerp.shangpin.get', $ary_param);
        if(isset($ary_result['SHANGPINS']['SHANGPIN'])) {
            //单个商品
            if(isset($ary_result['SHANGPINS']['SHANGPIN']['GUID'])) {
                $ary_result['SHANGPINS']['SHANGPIN'] = array($ary_result['SHANGPINS']['SHANGPIN']);
            }
            foreach($ary_result['SHANGPINS']['SHANGPIN'] as &$ary_goods) {
                //处理规格信息
                if(isset($ary_goods['SPSKUS']['SPSKU'])) {
                    //但规格
                    if(isset($ary_goods['SPSKUS']['SPSKU']['GUID'])) {
                        $ary_goods['SPSKUS']['SPSKU'] = array($ary_goods['SPSKUS']['SPSKU']);
                    }
                } else {
                    //无规格商品
                    $ary_goods['SPSKUS']['SPSKU'] = array(
                        'GUID' => $ary_goods['GUID'],
                        'SKUDM' => $ary_goods['SPDM'],
                        'SKUMC' => $ary_goods['SPMC'],
                        'IS_DEL'=> 0,
                        'BZJJ' => $ary_goods['BZJJ'],
                        'BZSJ' => $ary_goods['BZSJ'],
                        'CBJ' => $ary_goods['CBJ'],
                        'ZL' => $ary_goods['ZL'],
                        'ZHANGHAO' => $ary_goods['ZHANGHAO'],
                        'SL3' => $ary_goods['SL3'],
                        'SL2' => $ary_goods['SL2'],
                        'SL1' => $ary_goods['SL1'],
                        'SL' => $ary_goods['SL'],
                        'NO_SKU' => 1,    //该值为1时说明没有规格
                    );
                }
            }
            return $this->obj_erp->return_success($ary_result['SHANGPINS']['SHANGPIN'], $ary_result['TOTAL_RESULTS']);
        } else {
            if(!isset($ary_result['ERROR'])) {
                if(isset($ary_result['TOTAL_RESULTS']) && $ary_result['TOTAL_RESULTS'] === 0) {
                    return $this->obj_erp->return_error('没有符合条件的商品', 'Erp_getGoodsPage_001');
                } else {
                    return $this->obj_erp->return_error('API接口(ecerp.shangpin.get)异常', 'Erp_getGoodsPage_002');
                }
            } else {
                return $this->obj_erp->return_error($ary_result['ERROR'], 'Erp_getGoodsPage_003');
            }
        }
    }
    
    /**
     * 获取会员的预存款
     * @author Jerry
     * @param array $ary_data HY_GUID:会员guid,fields:字段
     * @date 2012-1-10
     * 测试方法在TestAction getMemberBalance
     */
    public function getMemberBalance($ary_data) {
       if(empty($ary_data['fileds'])) {
           $str_fileds = 'GUID,TOTAL';
       } else {
           $str_fileds = $ary_data['fileds'];
       }
       $str_guid = $ary_data['hy_guid'];
       $ary_param = array(
           'condition' => rawurlencode("HY_GUID='".$str_guid."'"),
           'fileds' => $str_fileds
       );
       $ary_result = $this->requestApi('ecerp.balance.get', $ary_param);
       if(isset($ary_result['BALANCES']['BALANCE'])) {
           if($ary_result['TOTAL_RESULTS'] == 1){
               return $this->obj_erp->return_success($ary_result['BALANCES']['BALANCE'], $ary_result['TOTAL_RESULTS']);
           } else if($ary_result['TOTAL_RESULTS'] == 0) {
               return $this->obj_erp->return_error('没有找到相应的记录', 'Erp_getCurMemberBalance_001');
           } else if($ary_result['TOTAL_RESULTS'] > 1) {
               return $this->obj_erp->return_error('ERP会员GUID重复', 'Erp_getCurMemberBalance_002');
           }
       }else{
           if(!isset($ary_result['ERROR'])) {
                if(isset($ary_result['TOTAL_RESULTS']) && $ary_result['TOTAL_RESULTS'] === 0) {
                    return $this->obj_erp->return_error('没有找到相应的记录', 'Erp_getCurMemberBalance_003');
                } else {
                    return $this->obj_erp->return_error('API接口(ecerp.balance.get)异常', 'Erp_getCurMemberBalance_004');
                }
            } else {
                return $this->obj_erp->return_error($ary_result['ERROR'], 'Erp_getCurMemberBalance_005');
            }
       }
       
    }


}