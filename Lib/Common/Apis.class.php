<?php

/**
 * 第三方公共接口类
 * @package Common
 * @subpackage Api
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-19
 */
class Apis {
    /**
     * 静态工厂方法，获取相应的API接口类
     * @param string $str_api_name
     * @param type $array_params
     * @return \IApis
	 * @add by wangguibin 京东接口
     */
    public static function factory($str_api_name, $array_params = array()) {
        switch ($str_api_name) {
            case 'paipai':
                $return = new Paipai($array_params);
                break;
            case 'jd':
                $return = new Jd($array_params['access_token']);
                break;				
            default:
                $return = new Taobao($array_params);
                break;
        }

        if ($return instanceof IApis) {
            return $return;
        }else{
            //尼玛出错了
        }
    }

}