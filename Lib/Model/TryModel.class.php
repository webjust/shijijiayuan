<?php

/**
 * 试用模型层 Model
 * @package Model
 * @version 7.6
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-09-21
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class TryModel extends GyfxModel {

	/**
     * 构造方法
     * @author listen
     * @date 2012-12-14
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取试用产品列表
     * @param (array)$array_condition 搜索条件
     * @param (mix) $order_by 排序条件
     * @param (int) $int_page_size 查询多少条数据
     * @param (int) $int_active_status 试用状态
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-18
     */
	public function GetTryPageList($array_condition = array(), $order_by, $int_page_size = 20,$int_active_status = null){
        $array_condition['fx_try.try_status'] = array('EQ',1);
		switch ($int_active_status) {
			case 2:		// 已经结束
				$array_condition['fx_try.try_end_time'] = array('LT',date('Y-m-d H:i:s'));
				break;
			case 1:		// 即将开始
				$array_condition['fx_try.try_start_time'] = array('GT',date('Y-m-d H:i:s'));
				break;
			default:	// 正在进行
				$array_condition['fx_try.try_end_time'] = array('GT',date('Y-m-d H:i:s'));
				$array_condition['fx_try.try_start_time'] = array('LT',date('Y-m-d H:i:s'));
				break;
		}
		$TryModel = D("Try");
        $count = $TryModel
                ->where($array_condition)
                ->join("fx_goods_info as fx_goods_info on(fx_goods_info.g_id=fx_try.g_id) ")
                ->count();
        $obj_page = new Page($count, $int_page_size);
        $data['page'] = $obj_page->show();
        $data['list'] = $TryModel
                ->where($array_condition)
                ->field("
                    fx_try.`try_num`,fx_try.`try_title`,fx_try.`try_id`,fx_try.`try_status`,fx_try.`try_picture`,fx_try.`try_now_num`,
                    fx_goods_info.`g_price`,fx_goods_info.`g_id`
                ")
                ->join("fx_goods_info as fx_goods_info on(fx_goods_info.g_id=fx_try.g_id) ")
                ->order($order_by)
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->select();
		//七牛图片显示
		foreach($data['list'] as $key =>$value ){
			$data['list'][$key]['try_picture'] =D('QnPic')->picToQn($value['try_picture']);
		}
        return $data;
	}

	/**
     * 试用产品详情
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-21
     */
	public function GetTryGoodsDetailsByGid($ary){
		$TryModel = D('Try');
		$where = array(
			'fx_try.try_id' => $ary['int_try_id']
			);
		$data = $TryModel
				->where($where)
				->join("fx_goods_info as fx_goods_info on(fx_goods_info.g_id=fx_try.g_id) ")
                ->order($order_by)
                ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
                ->find();
		//七牛图片显示
		$data['try_picture'] =D('QnPic')->picToQn($data['try_picture']);
        return $data;
	}

    /**
     * 获取试用广告图片
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-22
     */
    public function GetTryAds($tag = FALSE){
        // 获取广告
        if($tag){   // 使用流程
            $ary_ads = D('RelatedTryAds')->order('sort_order DESC')->limit(0,1)->select();
        }else{      // 试用首页banner
            $ary_ads = D('RelatedTryAds')->order('sort_order asc')->limit(0,4)->select();
        }
        $ary_ad_infos = array();
        foreach($ary_ads as $ary_ad){
			$ary_ad['ad_pic_url'] =D('QnPic')->picToQn($ary_ad['ad_pic_url']);
            $ary_ad_infos[$ary_ad['sort_order']] = $ary_ad;
        }
        if($tag) return $ary_ad_infos[5];
        return $ary_ad_infos;
    }

    /**
     * 获取猜你喜欢列表
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-21
     */
	public function GuessUserLikeGoods($int_gid){
		//获取商品所属类目
        $pid = $this->getCateParent($int_gid);
        $tag['cid'] = $pid;
        $tag['num'] = 12;
        $glists = D('ViewGoods')->goodList($tag);
        $glist = array();
        $glists = $glists['list'];
        $count = count($glists);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$glists[$i*3+$k];
            }
        }
        return $glist;
	}

    /**
     * 获取商品类目
     * @source ProductsAction.class.php
     * @author Wangguibin
     * @date 2013-11-21
     */
    public function getCateParent($gid){
        $condition = array();
        $condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
        $ary_goods=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
        ->field(array(C('DB_PREFIX').'goods_category.gc_id',C('DB_PREFIX').'goods_category.gc_parent_id'))
        ->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
        ->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
        ->where($condition)->find();
        if($ary_goods['gc_parent_id']){
            return $ary_goods['gc_parent_id'];
        }else{
            return $ary_goods['gc_id'];;
        }
    }

    /**
     * 检查订单是否合法
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-23
     */
    public function checkOrder($ary){
        $int_try_id = $ary['int_try_id'];
        $int_gid    = $ary['int_gid'];
        $result     = array(
            'status' => 0,
            'msg' => 'error_message'
            );
        // 检查活动是否合法
        $ary_try = D('Try')->where(array('try_id'=>$int_try_id,'try_status'=>1))->find();
        if(empty($ary_try)){
            $result['msg'] = '该活动不存在或者已经下架';
            return $result;
        }
        if($ary_try['try_start_time'] > date('Y-m-d H:i:s')){
            $result['msg'] = '该活动还未开始!请耐心等待!';
            return $result;
        }
        if($ary_try['try_end_time'] < date('Y-m-d H:i:s')){
            $result['msg'] = '该活动已经结束!';
            return $result;
        }
        if($ary_try['try_num'] <= 0){
            $result['msg'] = '试用名额已满!';
            return $result;
        }
        // 检查库存是否足够
        $ary_good_stock = D('goods_products')->where(array('g_id'=>$int_gid,'pdt_status'=>1))->sum('pdt_stock');
        if(empty($ary_good_stock) || $ary_good_stock <= 0){
            $result['msg'] = '库存不足!';
            return $result;
        }
        // 检查是否已经申请
        $ary_apply = D('try_apply_records')->where(array('g_id'=>$int_gid,'m_id'=>$_SESSION['Members']['m_id']))->find();
        if(!empty($ary_apply)){
            $result['msg'] = '您已经申请!请耐心等待!';
            return $result;
        }
        return $result = array('status' => 1);
    }

    /**
     * 获取申请试用前问题
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-10
     */
    public function getFrontQuestion($ary){
        $int_try_id = $ary['int_try_id'];
        if(empty($int_try_id)) return false;
        // 获取试用信息
        $ary_try_info = D('Try')->where(array('try_id'=>$int_try_id))->find();
        if(empty($ary_try_info) || !is_array($ary_try_info) || empty($ary_try_info['property_typeid_front'])) return false;
        // 获取试用答题
        $ary_spec_info = $this->getQuestionById(array('gt_id'=>$ary_try_info['property_typeid_front']));
        if(!empty($ary_spec_info)){
            return $ary_spec_info;
        }
        return false;
    }

    /**
     * 获取问题(借鉴控制器中Admin/Goods/ajaxLoadUnsaleSpec)
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-10
     */
    public function getQuestionById($ary){
        $int_gt_id = $ary['gt_id'];
        //获取与此类型相关联的属性ID
        $array_related_info = D("RelatedGoodsTypeSpec")->where(array("gt_id"=>$int_gt_id))->select();
        $array_spec_info = array();
        //如果此类型下没有关联属性，则输出一个空字符到页面DOM中
        if(is_array($array_related_info) && !empty($array_related_info)){
            //对关联的商品ID做处理，用于生成查询商品属性的条件
            $array_spec_id = array();
            foreach($array_related_info as $val){
                $array_spec_id[] = $val["gs_id"];
            }
            //获取与此类型关联的商品扩展属性详情
            $array_cond = array("gs_id"=>array("IN",$array_spec_id),"gs_status"=>1,"gs_is_system_spec"=>0);
            $array_spec_info = D("GoodsSpec")->where($array_cond)->order(array("gs_order"=>"asc"))->select();
            //对属性进行遍历处理，如果输入类型是下拉选框的属性，还需要将属性值获取出来
            foreach($array_spec_info as $key => $val){
                if(2 == $val["gs_input_type"]){
                    $array_spec_info[$key]["spec_detail"] = D("GoodsSpecDetail")->where(array("gs_id"=>$val["gs_id"]))->select();
                }
            }
        }
        if(!empty($array_spec_info)){
            return $array_spec_info;
        }
        return false;
    }

    /**
     * 改变试用申请状态
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-11
     */
    public function ChangeStatus($ary){
        $int_tar_id     = $ary['tar_id'];
        $int_try_status = $ary['try_status'];
        $tag = true;
        if($int_try_status == 0){
            $tag = D('try_apply_records')->where($ary)->save(array('try_status' => 2));
        }
        return $tag;
    }

    /**
     * 查看问题
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-10
     */
    public function getApplyQuestion($ary){
        $int_tar_id = intval($ary['tar_id']);
        if(empty($int_tar_id)) return false;
        $ary_question_front = D('try_attribute')->where(array('try_apply_id'=>$int_tar_id))->select();
        if(!empty($ary_question_front)){
            return $ary_question_front;
        }else{
            return false;
        }
    }

    /**
     * 申请试用信息
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
    public function getApplyTryByCondi($where){
        if(is_array($where)){
            $data = D('try_apply_records')
                    ->join(" ".C("DB_PREFIX")."try ON ".C("DB_PREFIX")."try_apply_records.`g_id`=".C("DB_PREFIX")."try.`g_id`")
                    ->where($where)
                    ->find();
            return $data;
        }else{
            return false;
        }
    }

    /**
     * 查询获取试用品人数
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-14
     */
    public function getTryGoodsNum($where){
        $total = D('try_apply_records')
                ->count();
        return $total;
    }
}