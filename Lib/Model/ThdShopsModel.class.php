<?php

/**
 * 第三方店铺模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-12-20
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ThdShopsModel extends GyfxModel {

    /**
     * 保存店铺信息到数据库
     * 如果有店铺则更新信息，无店铺则新增
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2012-12-20
     * @param array $ary_token 获取到授权信息数组
     * @param string $str_shop_info_json 获取到的店铺信息json字符串
     * @param string $str_seller_info_json 获取到的卖家信息json字符串
     * @param string $str_pf 平台类型：taobao/paipai
     * @param int $int_mid 如果是前台用户授权此处为用户ID，否则为0
     * @param int $int_uid 如果是后台管理员授权此处为管理员ID，否则为0
     * @return boolean 返回授权成功或失败
     */
    public function saveShop($ary_token, $str_shop_info_json, $str_seller_info_json, $str_pf = 'taobao', $int_mid = 0, $int_uid = 0) {
        $ary_shop_info = json_decode($str_shop_info_json, true);
		$ary_seller_info = json_decode($str_seller_info_json, true);
        switch ($str_pf) {
            case 'paipai':
                $int_ts_source = 2;
                //拍拍数据处理情况 ++++++++++++++++++++++++++++++++++++++++++++++
                $ary_save_data = array(
                    'ts_title' => $ary_shop_info['shopName'],
                    'ts_source' => $int_ts_source,
                    'ts_sid' => $ary_shop_info['sellerUin'],
                    'ts_pic_path' => $ary_shop_info['logo'],
                    'ts_nick' => $ary_shop_info['shopName'],
                    'ts_modified' => date('Y-m-d H:i:s'),
                    'ts_shop_created' => $ary_shop_info['regTime'],
                    'ts_seller_info_json' => $str_seller_info_json,
                    'ts_shop_info_json' => $str_shop_info_json,
                    'ts_shop_token' => json_encode($ary_token)
                );
                //前台录入
                if ($int_mid) {
                    $ary_save_data['m_id'] = $int_mid;
                }
                //后台录入
                if ($int_uid) {
                    $ary_save_data['u_id'] = $int_uid;
                }

                //判断是新插入还是更新，利用平台类型和用户昵称判断唯一
                $is_insert = $this->field('ts_id')->where(array('ts_source' => 2, 'ts_nick' => $ary_save_data['ts_nick']))->find();
                if ($is_insert) {
                    //更新数据
                    $mix_return = $this->where(array('ts_source' => 2, 'ts_nick' => $ary_save_data['ts_nick']))->data($ary_save_data)->save();
                } else {
                    //新插入数据
                    $ary_save_data['ts_created'] = date('Y-m-d H:i:s');
                    $mix_return = $this->data($ary_save_data)->add();
                }

                break;
            //拍拍处理情况结束 ++++++++++++++++++++++++++++++++++++++++++++++
            case 'jingdong':
                $int_ts_source = 3;
                //拼成要保存的数组
                $ary_save_data = array(
                    'ts_title' => $ary_shop_info['jingdong_vender_shop_query_responce']['shop_jos_result']['shop_name'],
                    'ts_source' => $int_ts_source,
                    'ts_sid' => $ary_shop_info['jingdong_vender_shop_query_responce']['shop_jos_result']['shop_id'],
                    'ts_pic_path' => $ary_shop_info['jingdong_vender_shop_query_responce']['shop_jos_result']['logo_url'],
                    'ts_nick' => $ary_seller_info['top_user_nick'],
                    'ts_modified' => date('Y-m-d H:i:s'),
                    'ts_shop_created' => $ary_shop_info['jingdong_vender_shop_query_responce']['shop_jos_result']['open_time'],
                    'ts_seller_info_json' => $str_seller_info_json,
                    'ts_shop_info_json' => $str_shop_info_json,
                    'ts_shop_token' => json_encode($ary_token),
                );
				if(empty($ary_save_data['ts_pic_path'])){
					unset($ary_save_data['ts_pic_path']);
				}
                //前台录入
                if ($int_mid) {
                    $ary_save_data['m_id'] = $int_mid;
                }
                //后台录入
                if ($int_uid) {
                    $ary_save_data['u_id'] = $int_uid;
                }

                //判断是新插入还是更新，利用平台类型和用户昵称判断唯一
                $is_insert = $this->field('ts_id')->where(array('ts_source' => 3, 'ts_nick' => $ary_save_data['ts_nick']))->find();
                if ($is_insert) {
                    //更新数据
                    $mix_return = $this->where(array('ts_source' => 3, 'ts_nick' => $ary_save_data['ts_nick']))->data($ary_save_data)->save();
                } else {
                    //新插入数据
                    $ary_save_data['ts_created'] = date('Y-m-d h:i:s');
                    $mix_return = $this->data($ary_save_data)->add();
                }
                break;
            //京东处理情况结束 ++++++++++++++++++++++++++++++++++++++++++++++			
			
            default:
                //淘宝数据处理情况 ++++++++++++++++++++++++++++++++++++++++++++++
                $int_ts_source = 1;
                //拼成要保存的数组
                $ary_save_data = array(
                    'ts_title' => $ary_shop_info['shop_get_response']['shop']['title'],
                    'ts_source' => $int_ts_source,
                    'ts_sid' => $ary_shop_info['shop_get_response']['shop']['sid'],
                    'ts_pic_path' => $ary_shop_info['shop_get_response']['shop']['pic_path'],
                    'ts_nick' => $ary_shop_info['shop_get_response']['shop']['nick'],
                    'ts_modified' => date('Y-m-d H:i:s'),
                    'ts_shop_created' => $ary_shop_info['shop_get_response']['shop']['created'],
                    'ts_seller_info_json' => $str_seller_info_json,
                    'ts_shop_info_json' => $str_shop_info_json,
                    'ts_shop_token' => json_encode($ary_token),
                );
                //前台录入
                if ($int_mid) {
                    $ary_save_data['m_id'] = $int_mid;
                }
                //后台录入
                if ($int_uid) {
                    $ary_save_data['u_id'] = $int_uid;
                }

                //判断是新插入还是更新，利用平台类型和用户昵称判断唯一
                $is_insert = $this->field('ts_id')->where(array('ts_source' => 1, 'ts_nick' => $ary_save_data['ts_nick']))->find();
                if ($is_insert) {
                    //更新数据
                    $mix_return = $this->where(array('ts_source' => 1, 'ts_nick' => $ary_save_data['ts_nick']))->data($ary_save_data)->save();
                } else {
                    //新插入数据
                    $ary_save_data['ts_created'] = date('Y-m-d h:i:s');
                    $mix_return = $this->data($ary_save_data)->add();
                }
                break;
            //淘宝处理情况结束 ++++++++++++++++++++++++++++++++++++++++++++++
        }

        return (false === $mix_return) ? false : true;
    }
    
    /**
     * 获取相应店铺信息
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-21
     * @param array $ary_where
     * @return array $res
     */
    public function getThdShop($ary_where = array(),$ary_field='*',$ary_order) {
        $res=$this->where($ary_where)->field($ary_field)->order($ary_order)->select();
        return $res;
    }
    
    /**
     * 删除授权店铺
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-21
     * @param array $ary_where
     * @return array $res
     */
    public function delThdShop($ary_where = array()) {
        $res=$this->where($ary_where)->delete();
        return $res;
    }
    
    /**
     * 获取相应店铺统计数量
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-10-22
     * @param array $ary_where
     * @return int $res
     */
    public function getThdShopCount($ary_where = array()) {
        $res=$this->where($ary_where)->count();
        return $res;
    }
    
    /**
     * 获取相应店店铺tocken
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-10-23
     * @param array $ary_where
     * @return array $res
     */
    public function getAccessToken($ary_where = array(),$ary_field='*',&$ary_param) {
    	$ary_param=$this->where($ary_where)->field($ary_field)->find();
    	$access_token = json_decode($ary_param['ts_shop_token'],true);
    	$access_token = $access_token['access_token'];
    	return $access_token;
    }
    
}