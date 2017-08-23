<?php

/**
 * 分销商品与淘宝商品关联关系模型
 *
 * @package Model
 * @stage 7.4.5
 * @author czy <chenzongyao@guanyisoft.com>
 * @date 2012-12-20
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdTopItemsModel extends GyfxModel {
    private $obj_goods_products;
    public function __construct(){
		parent::__construct();
		$this->obj_goods_products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');
	}
    
	/**
     * 根据session信息获得会员id
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-29
     * @return int $m_id
     */
    public function getMemberId() {
        $member = session("Members");
        $m_id = $member['m_id'];
        return $m_id;
    }
    
   
    
    /**
     * 将[1627207:3232484:颜色分类:天蓝色;20509:28381:尺码:XXS]之类的淘宝属性，过滤掉其中的pid,vid.
     * 转换为[颜色分类:天蓝色;尺码:XXS]样的纯文本
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-29
     * @param string $subTitle 淘宝属性字串
     * @return string 返回解析后的字串
     */
    public function filterSubTitle($subTitle){
        $ary_subTitle = explode(';', $subTitle);
        $str_return = '';
        foreach($ary_subTitle as $v){
            $ary_tmp_value = explode(':', $v);
            $str_return = $str_return.$ary_tmp_value[2].':'.$ary_tmp_value[3].';';
        }
        return $str_return;
    }
    
    /**
	 * @author Mithern 2011-11-30
 	 * 
 	 * @uses 解析本地商品的规格（属性）
 	 * 
 	 * @param string 规格（属性）信息字符串，可选，默认为空
 	 * 
 	 * @return
 	 * 返回解析后的字符串，”属性名称：属性值；属性名称：属性值“形式
 	 * 如果参数不正确，则返回空字符串
	 */
	public function parseSpecFromString($string=''){
		if($string==''){
			return '';
		}
		$string=rtrim($string,';');
		$array=explode(';',$string);
		$key=$val=array();
		for($i=0;$i<count($array);$i++){
			$tmp=explode(':',$array[$i]);
			$key[]=$tmp[0];
			$val[]=$tmp[1];
		}
		if(empty($key) || empty($val)){
			return '';
		}
		$fetch_property_sql="SELECT `gs_id`,`gs_name` FROM `good_spec` WHERE `gs_id` IN (" . implode(',',$key) . ") AND `gs_status`='1';";
		$fetch_values_sql="SELECT `gs_id`,`gsd_value` FROM `good_spec_detail` WHERE `gsd_id` IN (" . implode(',',$val) . ") AND `gsd_status`='1';";
		$keys_array=DB()->fetchAll($fetch_property_sql);
		$vals_array=DB()->fetchAll($fetch_values_sql);
		$return_str='';
		if(!empty($keys_array) && is_array($keys_array)){
			foreach($keys_array as $kk => $kv){
				if(!empty($kv) && is_array($kv)){
					foreach($vals_array as $vk=>$vv){
						if($kv['gs_id']==$vv['gs_id']){
							$return_str.=$kv['gs_name'] . ":" . $vv['gsd_value'] . ";";
							break;
						}
					}
				}
			}
		}
		
		return $return_str;
	}
	
    
    
    /**
     * 根据商家编码 找到分销系统内 可以匹配上的货品，并返回价格和库存
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     * @param string $outer_id 淘宝的商家编码 即 本系统内的商品编号或者货品编号
     * @return array 如果匹配上，则返回匹配状态和本系统内的价格、库存,如果未匹配上，价格库存返回都是0
     * @edit czy 2013-10-31 此处增加三个返回值，g_id,g_sn,pdt_sn
     */
    public function findByOuterId($g_sn,$pdt_sn) {
       
        $str_where = "g_sn='{$g_sn}'";
        if(!empty($pdt_sn) ) $str_where .= " AND pdt_sn='{$pdt_sn}'";
        $result = $this->obj_goods_products->where($str_where)->find();
        if ($result) {
           
            return array($result, $result['pdt_stock'],$result['g_id'], $result['pdt_id'], $result['pdt_sn']);
        } else {
            return array($result, 0, 0, 0, '', '');
        }
    }
    
    /**
     * 根据pdt_id 找到分销系统内 可以匹配上的货品，并返回价格和库存
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     * @param string $pdt_id 本系统内的货品id
     * @return array 如果匹配上，则返回匹配状态和本系统内的价格、库存,如果未匹配上，价格库存返回都是0
     * @edit czy 2012-10-31 此处增加三个返回值，g_id,pdt_id,pdt_sn
     */
    public function findByPdtId($pdt_id) {
      
        $result = $this->obj_goods_products->where(array('pdt_id'=>$pdt_id))->find();
        if ($result) {
            return array($result, $result['pdt_stock'],$result['g_id'], $result['pdt_id'], $result['pdt_sn']);
        } else {
            return array($result, 0, 0, 0, '', '');
        }
    }
    
    /**
     * 根据g_id 找到分销系统内 可以匹配上的货品，并返回价格和库存
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-10-30
     * @param string $g_id 本系统内的商品id
     * @return array 如果匹配上，则返回匹配状态和本系统内的价格、库存,如果未匹配上，价格库存返回都是0
     * @edit czy 2012-10-30 此处增加三个返回值，g_id,g_sn,pdt_id,pdt_sn
     */
    public function findByGId($g_id) {
      
        $result = $this->obj_goods_products->where(array('g_id'=>$g_id))->find();
        if ($result) {
            return array($result, $result['pdt_stock'],$result['g_id'], $result['pdt_id'], $result['pdt_sn']);
        } else {
            return array($result, 0, 0, 0, '', '');
        }
    }
    
    /**
     * 根据g_id 找到分销系统内 可以匹配上的商品图片路径
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-11-16
     * @param string $g_id 本系统内的商品id
     * @return str 商品图片路径
     */
    public function findImgByGId($g_id) {
        $obj_goods_info = M('goods_info',C('DB_PREFIX'),'DB_CUSTOM');
        $result = $obj_goods_info->field('g_picture')->where(array('g_id'=>$g_id))->find();
       // var_dump($result);exit;
        if (isset($result['g_picture']) && !empty($result['g_picture'])) {
            return $result['g_picture'];
        } else return '';
           
        
    }
}