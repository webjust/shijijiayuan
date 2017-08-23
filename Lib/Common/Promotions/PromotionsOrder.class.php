<?php

/**
 * 促销规则接口
 * @package Common
 * @subpackage PromotionsOrder
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-05-28
 */
interface PromotionsOrder {
    /**
     * 获取该促销规则类型，代码，说明等
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     */
    public function getType();

    /**
     * 获取该促销规则的基本配置信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     */
    public function getCfg();

    /**
     * 设置该促销规则的基本配置信息
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     * @param array $array_param 从页面里面提交的数组，可能包含不是本规则需要的键值，因此需要过滤掉
     */
    public function setCfg($array_param);

    /**
     * 获取该促销规则与商品的关系数据
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     * @return array 返回对应关系的数组
     */
    public function getRel();

    /**
     * 从提交的各种数据中过滤出本规则与商品的关系数据
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     * @param array $array_param 未过滤前的数组
     */
    public function setRel($array_param);

    /**
     * 向HTML页面中设置额外的变量，例如待选择的商品数据
     * @auther zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-05-28
     */
    public function assignHtml();
    
    public function setpromotion($ary_param);
}
?>