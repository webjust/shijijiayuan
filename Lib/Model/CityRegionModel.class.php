<?php

/**
 * 地址模型 Model
 * @package Model
 * @version 7.0
 * @author Joe
 * @date 2012-12-13
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class CityRegionModel extends GyfxModel {

    private $receiveAddr;

    /**
     * 私有属性，ajax参数返回
     * @author Joe qianyijun@guanyisoft.com>
     * @date 2012-12-13
     */
    private $ary_return = array(
            'status' => 0,
            'msg' => '参数有误，请重试',
            'error_code' => 1001,
            'data' => '',
            'url' => ''
        );

    public function _initialize() {
        parent::_initialize();
        $this->receiveAddr = M('ReceiveAddress',C('DB_PREFIX'),'DB_CUSTOM');
        $this->logistic = D('Logistic');
    }

    /**
     * 根据最后地址ID获取完整地址
     *
     * @param int $last_address_id 最后一级地址ID (必填)
     * @author Terry <Terry@guanyisoft.com>
     * @return string 上海 上海市 宝山区
     * @date 2012-12-13
     */
    public function getFullAddressName($last_address_id) {
        //获取完整路径
        $str_return = '';
        $ary_path = $this->getFullAddressId($last_address_id);
        foreach ($ary_path as $path) {
            if (!empty($path) && $path != '1') {
                $str_return .= $this->getAddressName($path) . " ";
            }
        }
        return $str_return;
    }
    
    /**
     * 解析地址
     * @author Jerry(zhanghao)
     * @param int $cr_id 地址区域id
     * @date 2014-6-12
     */
    public function parseRegion($cr_id) {
        $ary_level = array(
            0 => 'country',    //国家
            1 => 'province',    //省份
            2 => 'city',        //市
            3 => 'district'		//县区
        );
        $path = $this->getPath($cr_id);
        $result['cr_path'] = $path;
        $path = trim($path,'|');
        $path .= '|'.$cr_id;
        $ary_path = explode('|',$path);
        
        if(is_array($ary_path)) {
            foreach ($ary_path as $k=>$region) {
                $result[$ary_level[$k]] = $this->getName($region);
                $result[$ary_level[$k].'_id'] = $region;
            }
        }
        return $result;
    }
    
     /**
     * 地址路径
     * @author Jerry(zhanghao)
     * @param int $cr_id 区域id
     * @date 2014-6-6
     */
    public function getPath($cr_id) {
        return $this->getRegionField($cr_id, 'cr_path');
    }
    /**
     * 获取地址的字段
     * @author Jerry(zhanghao)
     * @param int $cr_id 地址id
     * @param string $field 字段
     */
    public function getRegionField($cr_id,$field) {
        return $this->where(array('cr_id'=>$cr_id))->getField($field);
    }
     /**
     * 地址名称
     * @author Jerry(zhanghao)
     * @param int $cr_id 区域id
     * @date 2014-6-6
     */
    public function getName($cr_id) {
        return $this->getRegionField($cr_id, 'cr_name');
    }
    
    /**
     * 通过地址ID获取地址名称
     *
     * @param int $cr_id 地址ID (必填)
     * @author Joe <qianyijun@guanyisoft.com>
     * @return string $cr_name 
     * @date 2012-12-13
     */
    public function getAddressName($cr_id) {
        $ary_return = $this->where(array('cr_id' => $cr_id))->field('cr_name')->find();
        return $ary_return['cr_name'];
    }

    /**
     * 通过父级ID找到该父级下所有自己区域信息
     * @param int $cr_parent_id 地址ID (必填)
     * @author Joe <qianyijun@guanyisoft.com>
     * @return array
     * @date 2012-12-13
     */
    public function getParentsAddr($cr_parent_id,$string_field,$is_cache) {
        if(empty($cr_parent_id)){
			return false;
		}else{
            $where['cr_parent_id'] = $cr_parent_id;
            $where['cr_status'] = '1';
			if($is_cache == '1'){
				return D('Gyfx')->selectAllCache('city_region',$string_field,$where);
			}else{
				return $this->field($string_field)->where($where)->select();
			}
			
		}
    }

    /**
     * 获取常用收货地址
     * @param int $m_id 用户ID
     * @param int $ra_id 
     * @author Terry <wanghui@guanyisoft.com>
     * @return array  获取会员所有收货地址
     * @date 2012-12-28
     */
    public function getReceivingAddress($m_id = 0, $ra_id = 0, $tag = array()) {
        if ($m_id == 0) {
            $me = session("Members");
            $m_id = $me['m_id'];
        }
        if ($ra_id != 0) {
            $where['ra_id'] = $ra_id;
        }
		if(!empty($tag['name'])){
			 $where['ra_name'] = array('like','%'.$tag['name'].'%');
		}
        $where['ra_status'] = 1;
        $where['m_id'] = $m_id;
        $ary_addr = $this->receiveAddr->where($where)->order("ra_is_default desc,ra_create_time desc")->limit(0,20)->select();
        $ary_addrs = array();
        if (!empty($ary_addr) && is_array($ary_addr)) {
            foreach ($ary_addr as $addr_key => &$addr_val) {
			    if($addr_val['ra_phone'] && strpos($addr_val['ra_phone'],':')){
                    $addr_val['ra_phone'] = decrypt($addr_val['ra_phone']);
                }
                if($addr_val['ra_mobile_phone'] && strpos($addr_val['ra_mobile_phone'],':')){
                    $addr_val['ra_mobile_phone'] = decrypt($addr_val['ra_mobile_phone']);
                    //if($addr_val['ra_mobile_phone'] && strpos($addr_val['ra_mobile_phone'],':')){
                    //    $addr_val['ra_mobile_phone'] = (decrypt($addr_val['ra_mobile_phone']));
                    //}
                }
			    if($addr_val['ra_id_card'] && strpos($addr_val['ra_id_card'],':')){
                    $addr_val['ra_id_card'] = decrypt($addr_val['ra_id_card']);
                }
                if (!empty($ra_id) && (int) $ra_id > 0) {
                    $addr_val['address'] = $this->getFullAddressName($addr_val['cr_id']);
                    $ary_addr = $addr_val;
                } else {
                    $ary_addr[$addr_key]['address'] = $this->getFullAddressName($addr_val['cr_id']);
                }
                $pos_mobile = strpos($addr_val['ra_mobile_phone'],$tag['mobile']);
                $pos_name = strpos($addr_val['ra_name'],$tag['name']);
                if($pos_mobile !== FALSE || ($pos_mobile !== FALSE && $pos_name !== FALSE)){
                    $ary_addrs[] = $addr_val;
                }
            }
        }
		
        if(isset($tag['mobile']) && is_array($ary_addrs)){
            $count_addr = count($ary_addrs);
            $nowPage = !empty($tag['p']) && isset($tag['p'])?$tag['p']:1;
            $data['page'] = array(
                'nowPage' => $nowPage,
                'totalRow' => $count_addr,
                'totalPage' => ceil($count_addr/10)
            );
            $ary_address = array();
            for($i=0;$i<$data['page']['totalPage'];$i++){
                for($k=0;$k<10;$k++){
                    if(!empty($ary_addrs[$i*10+$k])){
                        $ary_address[$i][$k]=$ary_addrs[$i*10+$k];
                    }
                }
            }
            $data['addr'] = $ary_address[$nowPage-1];
            return $data;
        }
        return $ary_addr;
    }

    /**
     * 获取常用收货地址分页
     * @param int $m_id 用户ID
     * @param array $tag 查询的数组 
     * @author hcaijin 
     * @return array  获取会员所有收货地址
     * @date 2015-03-18
     */
    public function getReceivingAddressPage($m_id = 0, $tag=array(),$ra_id=0) {;
        if ($m_id == 0) {
            $me = session("Members");
            $m_id = $me['m_id'];
        }
        $page = isset($tag['p'])?$tag['p']:'1';
        $first = ($page-1)*$nums;
        $where['ra_status'] = 1;
        $where['m_id'] = $m_id;
        if(isset($tag['name'])){
            $where['ra_name'] = array('like','%'.$tag['name'].'%');
        }
	if((int)$ra_id>0){
	    $where['ra_id'] = $ra_id;
	}

        $page = ($page - 1) * 10;
        $count = $this->receiveAddr->where($where)->count();
        $addr_page = new Pager($count, 10);
        $data['page'] = $addr_page->showArr();
        $data['pageHtml'] = $addr_page->show();
        $first = $page>0?$page:$addr_page->firstRow;
        $nums = $addr_page->listRows;
        $ary_addr = $this->receiveAddr->where($where)->order("ra_is_default desc")->limit($first,$nums)->select();
        foreach ($ary_addr as $addr_key => &$addr_val) {
            if($addr_val['ra_phone'] && strpos($addr_val['ra_phone'],':')){
                $addr_val['ra_phone'] = decrypt($addr_val['ra_phone']);
            }
            if($addr_val['ra_mobile_phone'] && strpos($addr_val['ra_mobile_phone'],':')){
                $addr_val['ra_mobile_phone'] = (decrypt($addr_val['ra_mobile_phone']));
                if($addr_val['ra_mobile_phone'] && strpos($addr_val['ra_mobile_phone'],':')){
                    $addr_val['ra_mobile_phone'] = (decrypt($addr_val['ra_mobile_phone']));
                }
            }
            if($addr_val['ra_id_card'] && strpos($addr_val['ra_id_card'],':')){
                $addr_val['ra_id_card'] = decrypt($addr_val['ra_id_card']);
            }
            if (!empty($ra_id) && (int) $ra_id > 0) {
                $addr_val['address'] = $this->getFullAddressName($addr_val['cr_id']);
                $ary_addr = $addr_val;
            } else {
                $ary_addr[$addr_key]['address'] = $this->getFullAddressName($addr_val['cr_id']);
            }
        }
        $data['addr'] = $ary_addr;
        return $data;
    }

    /**
     * 选择默认收货地址
     * @param int $ra_id 收货地址ID
     * @param int $m_id 会员ID
     * @param int $cr_id 城市最后一级id
     * @author Joe <qianyijun@guanyisoft.com>
     * @return bool
     * @date 2012-12-18
     */
    public function checkReciveAddr($ra_id, $m_id) {
        if (empty($m_id)) {
            $m_id = $_SESSION['Members']['m_id'];
        }
        $cancel_data['ra_is_default'] = 0;
        $data['ra_is_default'] = 1;
        $this->receiveAddr->where(array('m_id' => $m_id))->data($cancel_data)->save();
        $ary_result = $this->receiveAddr->where(array('ra_id' => $ra_id, 'm_id' => $m_id))->data($data)->save();

        if ($ary_result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据收货地址表ID获取一条记录
     * @param int $ra_id 收货地址ID
     * @param int $m_id 会员ID
     * @author Joe qianyijun@guanyisoft.com>
     * @return array
     * @date 2012-12-18
     */
    public function getFindReciveAddr($ra_id=0, $m_id=0) {
        if(!$ra_id && $m_id) {
            $where = array(
                'm_id'  =>  $m_id,
                'ra_is_default' => 1,
            );
        }else{
            $where = array('ra_id' => $ra_id);
        }
        $ary_address = $this->receiveAddr->where($where)->find();
        if(!empty($ary_address['ra_mobile_phone'])) {
            $ary_address['ra_mobile_phone'] = decrypt($ary_address['ra_mobile_phone']);
        }
        return $ary_address;
    }

    /**
     * 修改收货地址
     * @param array $ary_addr
     * @author Joe qianyijun@guanyisoft.com>
     * @return array
     * @date 2012-12-20
     */
    public function updateAddr($ary_addr = array(),$m_id = 0) {
        if (empty($ary_addr)) {
            return $this->ary_return;
        }
        $data['cr_id'] = $ary_addr['cr_id'];
        $data['ra_name'] = $ary_addr['ra_name'];
        $data['ra_detail'] = $ary_addr['ra_detail'];
        $data['ra_post_code'] = $ary_addr['ra_post_code'];
		//加密存储手机和固话号码
        if($ary_addr['ra_mobile_phone'] && !strpos($ary_addr['ra_mobile_phone'],'*')){
            $data['ra_mobile_phone'] = encrypt($ary_addr['ra_mobile_phone']);
        }
		//加密存储身份证号
        if($ary_addr['ra_id_card'] && !strpos($ary_addr['ra_id_card'],'*')){
            $data['ra_id_card'] = encrypt($ary_addr['ra_id_card']);
        }
        $data['ra_phone'] = $ary_addr['ra_phone'];
		$data['ra_is_default'] = $ary_addr['ra_is_default'];
        $data['ra_update_time'] = date('Y-m-d H:i:s');
//        echo'<pre>';print_r($data);die;
		if($data['ra_is_default'] == '1'){
			$this->receiveAddr->where(array('m_id'=>$m_id))->data(array('ra_is_default'=>0))->save();
		}
        if ($this->receiveAddr->where(array('ra_id'=>$ary_addr['ra_id']))->data($data)->save()) {
            $this->ary_return['status'] = 1;
            $this->ary_return['msg'] = '更新成功';
			
            return $this->ary_return;
        }
        return $this->ary_return;
    }

    /**
     * 添加用户收货地址
     * @param array $ary_addr
     * @author Joe <qianyijun@guanyisoft.com>
     * @return array
     * @date 2012-12-20
     */
    public function addReceiveAddr($ary_addr, $m_id = 0) {
        if (empty($m_id)) {
            $m_id = $_SESSION['Members']['m_id'];
        }
		
        $this->receiveAddr->cr_id = $ary_addr['cr_id'];
        $this->receiveAddr->m_id = $m_id;
        $this->receiveAddr->ra_name = $ary_addr['ra_name'];
        $this->receiveAddr->ra_detail = $ary_addr['ra_detail'];
        $this->receiveAddr->ra_post_code = $ary_addr['ra_post_code'];
		//加密存储手机和固话号码
        if($ary_addr['ra_mobile_phone'] && !strpos($ary_addr['ra_mobile_phone'],'*')){
            $this->receiveAddr->ra_mobile_phone = encrypt($ary_addr['ra_mobile_phone']);
        }
        if($ary_addr['ra_phone']){
           // $this->receiveAddr->ra_phone = encrypt($ary_addr['ra_phone']);
        }
		$this->receiveAddr->ra_phone = $ary_addr['ra_phone'];
		//加密存储身份证号
        if($ary_addr['ra_id_card'] && !strpos($ary_addr['ra_id_card'],'*')){
            $this->receiveAddr->ra_id_card = encrypt($ary_addr['ra_id_card']);
        }
        //$this->receiveAddr->ra_is_default = $ary_addr['ra_is_default'];
        $this->receiveAddr->ra_status = 1;
        $this->receiveAddr->ra_create_time = date('Y-m-d H:i:s');
        $int_ra_id = $this->receiveAddr->add();
        if ($int_ra_id > 0) {
			if($ary_addr['ra_is_default'] == '1'){
				$this->checkReciveAddr($int_ra_id, $m_id);//新增收货地址为默认收货地址
			}
            $this->ary_return['status'] = 1;
            $this->ary_return['data']['ra_id'] = $int_ra_id;
            $this->ary_return['msg'] = '添加收货地址成功';
        }
        return $this->ary_return;
    }

    /**
     * 获取所有城市区域
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-25
     */
    public function getCurrLvItem($parent = 0) {
        $where = array();
        $where['cr_status'] = 1;
        $where['cr_parent_id'] = $parent;
        $data = $this->where($where)->order("cr_order DESC")->select();
        return $data;
    }

    /**
     * 获取省市区信息列表
     * @author Terry <wanghui@guanyisoft.com>
     * @param   int 地区ID
     * @return  array   地区数组
     * @date 2012-12-25
     */
    public function getFullAddressId($last_address_id) {
        $where = array();
        $where['cr_id'] = $last_address_id;
        $where['cr_status'] = 1;

        //$data = $this->where($where)->find();
		//$data = $this->where($where)->find();
		$data = D('Gyfx')->selectOneCache('city_region',null, $where);
        $ary_res = explode('|', $data['cr_path']);
        //dump($ary_res);die;
        if($ary_res[1] == 1)
            array_shift($ary_res);
        $ary_res[] = $data['cr_id'];
        return $ary_res;
    }

    /**
     * 删除会员的收货地址
     * @author Terry <wanghui@guanyisoft.com>
     * @param   int   会员ID
     * @return  如果删除成功，则返回TRUE，删除失败则为FALSE
     * @date 2012-12-25
     */
    public function doDelDeliver($ra_id) {
        $where['ra_id'] = $ra_id;
        $data['ra_update_time'] = date("Y-m-d H:i:s");
        $data['ra_status'] = '2';
        $ary_result = $this->receiveAddr->where($where)->save($data);
        if ($ary_result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据第三方地址匹配获取本系统地址id
     *
     * @param str $province省份，$city城市，$region区域
     * @return int 如果没有正确匹配则返回0
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
    public function getAvailableLogisticsList($province = '', $city = '', $region = '') {
        //过滤结尾字符
        $ary_filter_str = array('开发区' => '', '行政区' => '', '自治州' => '', '自治县' => '', '市' => '', '新区' => '', '区' => '', '县' => '');
        $str_filter_province = strtr($province, $ary_filter_str);
        $str_filter_city = strtr($city, $ary_filter_str);
        if ('' != $region) {
            $str_filter_region = strtr($region, $ary_filter_str);
        }
        //匹配省份
        $mix_data = $this->where(array('cr_parent_id' => '1', 'cr_name' => array('LIKE', '%' . $province . '%')))->find();
        if (empty($mix_data) && !is_array($mix_data)) {
            return 0;
        }
        $ary_address = $mix_data;
        if (0 == count($ary_address)) {
            return 0;
        }
        //匹配城市
        $mix_data = $this->where(array('cr_parent_id' => $ary_address['cr_id'], 'cr_name' => array('LIKE', '%' . $str_filter_city . '%')))->find();
        if (empty($mix_data) && !is_array($mix_data)) {
            return 0;
        }
        $ary_address = $mix_data;
        if (0 == count($ary_address)) {
            return 0;
        }
        if ('' == $region) {
            return (int) $ary_address['cr_id'];
        }
        //如果区域值存在，则匹配区域
        $mix_data = $this->where(array('cr_parent_id' => $ary_address['cr_id'], 'cr_name' => array('LIKE', '%' . $str_filter_region . '%')))->find();
        if (empty($mix_data) && !is_array($mix_data)) {
            return 0;
        }
        $ary_address = $mix_data;
        if (0 == count($ary_address)) {
            return 0;
        }
        return (int) $ary_address['cr_id'];
    }

    /**
     * 通过系统地址id取得可用的配送方式列表
     * 
     * @param int $address_id 对应city_region表中的id
     * @return array 结构同delivery_corp_area_region，delivery_corp_area，delivery_corp三个表字段，其中dca_configure为已反序列化后的数组，增加dca_configure_text物流配送显示信息，free_shipping免邮设置；当没有可用的物流配送时，返回空数组
     * @author Teery<wanghui@guanyisoft.com>
     * @version 1.0
     * @since stage 1.0
     * @modify 2013-1-12
     */
    public function getAvailableListById($address_id) {
        $int_address_id = (int) $address_id;
        $ary_address_id = $this->getFullAddressId($int_address_id);
        $str_address_ids = implode(',', $ary_address_id);
        $ary_filter_logistics_items = array();
        $ary_tmp_logistics_items = array();
        $ary_logistics_tiems = array();
        $ary_tmp_logistics_order_items = array();
        $condition = array();
        arsort($ary_address_id);
        if (!empty($ary_address_id) && is_array($ary_address_id)) {
            foreach ($ary_address_id as $kaid => $vaid) {
                $condition['cr_id'] = (int) $vaid;
                $condition['lc_disabled'] = array('neq', 'true');
                if (!empty($vaid)) {
                    $mixed_tmp_logistics_items = M("related_logistic_city",C('DB_PREFIX'),'DB_CUSTOM')
                                    ->join("left join fx_logistic_type on fx_related_logistic_city.lt_id = fx_logistic_type.lt_id")
                                    ->join("left join fx_logistic_corp on fx_logistic_corp.lc_id = fx_logistic_type.lc_id")
                                    ->where($condition)->select();
                    if (empty($mixed_tmp_logistics_items)) {
                        continue;
                    }
                    $ary_tmp_logistics_items = $mixed_tmp_logistics_items;
                    if (!empty($ary_tmp_logistics_items) && is_array($ary_tmp_logistics_items)) {
                        foreach ($ary_tmp_logistics_items as $ary_item_info) {
                            if (isset($ary_filter_logistics_items[$ary_item_info['lc_id']]) && $kaid != 3) {
                                continue;
                            } else {
                                $ary_filter_logistics_items[$ary_item_info['lc_id']] = $ary_item_info;
                                $ary_tmp_logistics_order_items[$ary_item_info['lc_id']] = $ary_item_info['lc_ordernum'];
                            }
                        }
                    }
                }
            }
            if (!arsort($ary_tmp_logistics_order_items)) {
                return array();
            }
            foreach ($ary_tmp_logistics_order_items as $int_dc_id => $int_dc_ordernum) {
                $ary_logistics_tiems[] = $ary_filter_logistics_items[$int_dc_id];
            }
            
            foreach ($ary_logistics_tiems as $k => $v) {
            	if(empty($v['lt_expressions'])){
            		unset($ary_logistics_tiems[$k]);
            		continue;
            	}
                $ary_logistics_configure = json_decode($v['lt_expressions'],true);
                $str_logistics_text = "";
                if ($ary_logistics_configure['calc_type'] == "item") {
                    //按件计费
                    $str_logistics_text = "第一件
                                       {$ary_logistics_configure['weight_init_500']}
                                       元 ,后每一件
                                       {$ary_logistics_configure['weight_continue_500']}元,
                                       免费额度{$ary_logistics_configure['free_credits']}元";
                } else {
                    //按重量计费
                    $str_logistics_text = "首重{$ary_logistics_configure['logistics_first_weight']}克
                                       {$ary_logistics_configure['logistics_first_money']}
                                       元,续重每{$ary_logistics_configure['logistics_add_weight']}克
                                       {$ary_logistics_configure['logistics_add_money']}元";
                }

                $ary_logistics_tiems[$k]['lt_expressions_text'] = $str_logistics_text;
                $ary_logistics_tiems[$k]['lt_expressions'] = $ary_logistics_configure;
            }
            return $ary_logistics_tiems;
        }
    }

    /**
     * 计算订单实际物流费用
     * 
     * @param array $ary_plan 物流配送方案数据结构，对应delivery_corp_area表单条记录
     * @param int $int_nums 货品总数
     * @param float $float_weight 货品总重量
     * @param float $float_goods_price 商品总价格
     * @return float 实际物流费用
     * 
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @since stage 1.0
     * @modify 2013-1-13 Terry
     */
    public function getActualLogisticsFreight($ary_plan, $int_nums, $float_weight, $float_goods_price){
        $float_price = 0;
        //目前只根据重量来计算物流运费
        if('item' == $ary_plan['calc_type']){
            //计件收费
        }else{
            if( $float_weight <= $ary_plan['logistics_first_weight'] ) {
                $float_price = $ary_plan['logistics_first_money'];
            }else{
                $int_overweight = 0;
                $int_overweight = (int)ceil(($float_weight-$ary_plan['logistics_first_weight']) / $ary_plan['logistics_add_weight']);
                $float_price =  $ary_plan['logistics_first_money'] + ($ary_plan['logistics_add_money'] * $int_overweight);
            }
        }
        return $float_price;
    }
    
    /**
     * 更新地址是否显示
     *
     * @param int $cr_id 城市ID
     * @return bool 如果没有正确匹配则返回0
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
    public function delCityAddress($cr_id){
        $where['cr_path'] = array("LIKE","%|".$cr_id."|%");
        $where['cr_parent_id']  = $cr_id;
        $where['cr_id'] = $cr_id;
        $where['_logic']    = 'OR';
        $data['cr_update_time'] = date("Y-m-d H:i:s");
        $data['cr_status'] = '2';
        $ary_result = $this->where($where)->data($data)->save();
        if (!empty($ary_result) && $ary_result > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取地址名称是否存在
     *
     * @param int $cr_id 城市ID
     * @return bool 如果没有正确匹配则返回0
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
    public function selectCityAddress($cr_id,$cityname){
        $where['cr_path'] = array('LIKE','%|'.$cr_id.'|%');
        $where['cr_name'] = $cityname;
        $ary_result = $this->where($where)->find();
        if (!empty($ary_result) && is_array($ary_result)) {
            return $ary_result;
        } else {
            return false;
        }
    }
    
    /**
     * 更新
     *
     * @param array $ary_where 更新条件
     * @param array $ary_data 更新数组
     * @return bool 如果没有正确匹配则返回0
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
    public function editCityAddress($ary_where,$ary_data){
        $ary_result = $this->where($ary_where)->data($ary_data)->save();
        if(!empty($ary_result) && (int)$ary_result > 0){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 添加
     *
     * @param array $ary_where 更新条件
     * @param array $ary_data 更新数组
     * @return bool 如果没有正确匹配则返回0
     * @author Terry<wanghui@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
    public function addCityAddress($ary_data){
        $ary_result = $this->data($ary_data)->add();
        if($ary_result){
            return true;
        }else{
            return false;
        }
    }
	
	/**
     * 根据最后一级的行政区域ID
     *
     * @param int $last_cr_id 区域的最后一级ID
     * 
     * @return array 返回一个数组：array("province"=>'',"city"=>"","region"=>"")
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @version 1.0
     * @modify 2013-1-12
     */
	public function getCityRegionInfoByLastCrId($last_cr_id){
		$array_default_return = array("province"=>'',"city"=>"","region"=>"");
		//根据会最后一级区域ID获取区域Path 信息
		$string_path = $this->where(array("cr_id"=>$last_cr_id,"cr_status"=>1))->getField("cr_path");
		if("" == $string_path){
			//如果没有找到，则需要重新选择
			return $array_default_return;
		}

		$array_path_info = explode("|",trim($string_path,"|"));
		if(empty($array_path_info) || $string_path == "0"){
			return $array_default_return;
		}
		//获取完整的行政区域详细信息
		$array_path_info[] = $last_cr_id;
		$array_full_address_info = $this->where(array("cr_id"=>array("IN",$array_path_info)))->Field("cr_id,cr_type")->select();
        
		foreach($array_full_address_info as $key => $val){
			//cr_type:1表示国家；2：省/自治区/直辖市/特别行政区，3：省会/地级市；4：县（区）；
			if(1 == $key){
				$array_default_return["province"] = $val['cr_id'];
			}else if(2 == $key){
				$array_default_return["city"] = $val['cr_id'];
			}else if(3 == $key){
				$array_default_return["region"] = $val['cr_id'];
			}
		}
		//对结果做特殊化处理，防止台湾省(cr_type=2)-新北市(cr_type=4)这类地址
		if($array_default_return["city"] == "" && $array_default_return["region"] > 0){
			$array_default_return["city"] = $array_default_return["region"];
			$array_default_return["region"] = "";
		}
		return $array_default_return;
	}
    
    
	/**
	 * 获取所有城市区域
	 * @author wanghaoyu <wanghaoyu@guanyisoft.com>
	*/
	public function getAllCitys($parent = 0) {
        $where = array();
        $where['cr_status'] = 1;
        $where['cr_parent_id'] = $parent;
        $data = $this->where($where)->select();
        return $data;
    }

    /**
     * 团购是否支持区域限售
     * str_city_name 市名称
     * int_gp_id 团购ID
     * @author wangguibin <wangguibin@guanyisoft.com>
     */
    public function isGroupCanBuy($str_city_name,$int_gp_id){
        //团购是否启用区域限售
        if(!empty($str_city_name) && !empty($int_gp_id)){
            $is_open = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id'=>$int_gp_id))->count();
            if($is_open>0){
                //判断是否在设置中
                $int_cr_id = $this->where(array('cr_name'=>$str_city_name))->getField('cr_id');
                if(empty($int_cr_id)){
                    return true;
                }else{
                    //判断是否在里面
                    $is_can_buy = M('related_groupbuy_area', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id'=>$int_gp_id,'cr_id'=>$int_cr_id))->count();
                    if($is_can_buy>0){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                return true;
            }
        }
        return true;
    }

    /**
     * 预售是否支持区域限售
     * @param $str_city_name 市名称
     * @param $int_p_id 预售ID
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @return bool
     */
    public function isPresaleCanBuy($str_city_name,$int_p_id){
        //团购是否启用区域限售
        if(!empty($str_city_name) && !empty($int_p_id)){
            $is_open = M('related_presale_area', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('p_id'=>$int_p_id))->count();
            if($is_open>0){
                //判断是否在设置中
                $int_cr_id = $this->where(array('cr_name'=>$str_city_name))->getField('cr_id');
                if(empty($int_cr_id)){
                    return true;
                }else{
                    //判断是否在里面
                    $is_can_buy = M('related_presale_area', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gp_id'=>$int_p_id,'cr_id'=>$int_cr_id))->count();
                    if($is_can_buy>0){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                return true;
            }
        }
        return true;
    }
    /**
     * APP获取所有城市区域
     *
     * @return mixed array
     *
     * @author Terry <wanghui@guanyisoft.com>
     * @version 7.0
     * @since stage 1.5
     * @modify 2012-12-25
     */
    public function getAppCurrLvItem($parent = 0) {
        $where = array();
        $where['cr_status'] = 1;
        $where['cr_parent_id'] = $parent;
        $data = $this->field("cr_id,cr_name")->where($where)->order("cr_order DESC")->select();
        return $data;
    }
}
