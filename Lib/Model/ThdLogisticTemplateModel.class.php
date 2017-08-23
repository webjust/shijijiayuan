<?php

/**
 * 第三方物流模版模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdLogisticTemplateModel extends GyfxModel {
	/**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
     */

    public function __construct() {
        parent::__construct();
    }
	/**
     * 上传全部的物流模版
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-24
     * @param array $ary_where
     * @return array $res
     */
    public function uploadDeliveryTemplates($str_access_token,$shop_id) {
		$status=false;
		//使用默认模板所属店铺
		$default_temp_where['ts_default']=1;
		$default_temp_where['u_id']=array('exp',' is not null');
		$default_temp_field=array('ts_sid');
		$default_temp_res=D('ThdShops')->getThdShop($default_temp_where,$default_temp_field);
		$default_shop_id=array_shift($default_temp_res);
		if(empty($default_shop_id['ts_sid'])){
			return array('status' => $status, 'message' => '没有选择默认模板所属店铺');
		}
		$temp_where=array('lt_shop_id'=>$default_shop_id['ts_sid']);
		$templates_data = $this->where($temp_where)->select();
		if(!empty($templates_data )){
			//创建对象
			$top = new TaobaoApi($str_access_token);
			foreach($templates_data as $val){
				$ary_fee_list = json_decode($val['lt_fee_list']);
                
                $ary_updatas = $this->changeLogisticUploadInfo($ary_fee_list);
				//运费模板的名称
				$ary_updatas['name'] = $val['lt_name'];
				//0:表示买家承担服务费;1:表示卖家承担服务费				
                $ary_updatas['assumer'] = $val['lt_assumer']; 
				//0:表示按宝贝件数计算运费1:表示按宝贝重量计算运费3:表示按宝贝体积计算运费 
                $ary_updatas['valuation'] = $val['lt_valuation'];
				//卖家发货地址区域ID 
                $ary_updatas['consign_area_id'] = $val['lt_consign_area_id'];
				//上传数据
				$res_upload =$top->addDeliveryTemplate($ary_updatas);
				
				if(isset($res_upload['delivery_template_add_response']['delivery_template']['template_id'])){
					/*上传成功
                     *将原店铺ID，原物流模版ID，新店铺ID，新物流模版ID对应关系入库表(物流模版铺货新旧店内的ID对应关系表)
					 */
                    $insert_data = array(
                        'thd_lt_id_old' => $val['lt_template_id'],
                        'thd_lt_id_new' => $res_upload['delivery_template_add_response']['delivery_template']['template_id'],
                        'thd_shop_id_old' => $val['lt_shop_id'],
                        'thd_shop_id_new' => $shop_id,
                        'thd_update_time' => mktime()
                    );
                    $res=M('thd_related_logistic',C('DB_PREFIX'),'DB_CUSTOM')->add($insert_data);   
					if($res){
						$status=true;
						$message= "logistic template upload success!";
					}else{
						$message= "relation logistic template fail!"; 
					}
				}else{
					$message= "logistic template upload fail!";
				}
			}
		}else{
			$message= "logistic template not exist!";
		}
		$res=array('status' => $status, 'message' => $message);
        return $res;
    }
	/**
     * 根据原店铺Id，新店铺Id以及原物流模版Id，从对应关系表中获取新的物流模版Id
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-25
     * @param $int $nick 原卖家nick
     * @param $int $shop_id_new 新店铺Id
     * @param $int $dt_id_old 原物流模版Id
     * @return $int 新物流模版ID
     */
    public function getLogisticTemplateId($ary_data){
		if($ary_data['postage_id'] == 0){
           return 0;
        } 
		//先根据nick反查出店铺ID
		$shop_where=array('ts_nick'=>$ary_data['nick']);
		$shop_field=array('ts_sid');
		$shop_order=array('ts_id'=>'desc');
		$shop_res=D('ThdShops')->getThdShop($shop_where,$shop_field,$shop_order);
		
		$logistic_where = array(
           'thd_lt_id_old'=>$ary_data['postage_id'],
           'thd_shop_id_old'=>$shop_res[0]['ts_sid'],
           'thd_shop_id_new'=>$ary_data['shop_code']
        );
		$logistic_field=array('thd_lt_id_new');
		$logistic_order=array('thd_update_time'=>'desc');
		$logistic_res=D('ThdRelatedLogistic')->getLogisticInfo($logistic_where,$logistic_field,$logistic_order);
		if ($logistic_res){
            return $logistic_res[0]['thd_lt_id_new'];
        }else{
            return 0;
        }
	}
	 
	/**
     * 物流模版信息转换成上传时的需要的数据格式
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-29
     * @param array $data
     * @return array $ary_return
     */
	
	private function changeLogisticUploadInfo($obj){
        $ary_return = array();
        $ary_tmp = array();
        foreach($obj->top_fee as $value){
			$key=$value->service_type;
            $ary_tmp[$key]['destination']     .= str_replace(',', '|', $value->destination) . ',' ;
            $ary_tmp[$key]['start_standards'] .= $value->start_standard . ',' ;
            $ary_tmp[$key]['start_fees']      .= $value->start_fee . ',' ;
            $ary_tmp[$key]['add_standards']   .= $value->add_standard . ',' ;
            $ary_tmp[$key]['add_fees']        .= $value->add_fee . ',' ;
        }
        foreach($ary_tmp as $k=>$val){
            $ary_return['template_types']           .=$k . ';';
            $ary_return['template_dests']           .=rtrim($val['destination'], ',') . ';';
            $ary_return['template_start_standards'] .=rtrim($val['start_standards'], ',') . ';';
            $ary_return['template_start_fees']      .=rtrim($val['start_fees'], ',') . ';';
            $ary_return['template_add_standards']   .=rtrim($val['add_standards'], ',') . ';';
            $ary_return['template_add_fees']        .=rtrim($val['add_fees'], ',') . ';';
        }
        foreach ($ary_return as $index=>$data){
            $ary_return[$index] = rtrim($data, ';');
        }
        return $ary_return;
    }
}