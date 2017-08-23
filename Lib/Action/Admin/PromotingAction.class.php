<?php

/**
 * 推广销售Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.4.5
 * @author zhangjiasuo
 * @date 2013-11-06
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class PromotingAction extends AdminAction {

    /**
     * 控制器初始化
     *
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-11-06
     */
    public function _initialize() {
        parent::_initialize();
    }
	
	/**
     * 默认控制器，重定向到订单列表页
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-11-06
     */
	public function index(){
		 $this->redirect(U('Admin/Promoting/payBack'));
	}

    /**
     * 返利列表
     *
     * @version 7.4.5
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-11-06
     */
    public function payBack() {
		$this->getSubNav(6, 6, 30);
        $page_size = 20;
        $user_name = trim($this->_get('user_name'));
        $page_no = max(1,(int)$this->_get('p','',1));
        if(!empty($user_name)){
		   $ary_datas=D("Members")->getRecommended(array('m_name'=>$user_name),array('m_id'));
		   if(is_array($ary_datas) && !empty($ary_datas)){
		       $ary_m_id=array_shift($ary_datas);
			   $ary_where['m_id']=$ary_m_id['m_id'];
			   $ary_where['m_recommended']=$user_name;
			   $data=D("Orders")->getpayBackCount($ary_where);
			   $obj_page = new Page($data['count'], $page_size);
			   $page = $obj_page->show();
			   $limit['start']=$obj_page->firstRow;
			   $limit['end']=$obj_page->listRows;
			   $ary_field=array('fx_members.m_name,
					fx_orders_items.oi_type,
					fx_orders_items.ml_rebate,
					fx_orders.o_all_price,
					fx_orders.promotion,
					fx_orders.o_create_time'
				);
				$res=D("Orders")->getpayBack(array('m_id'=>$data['m_id']),$ary_field,$limit);
				$total_price=0;
				
				foreach($res as &$val){
					$ary_promotion_info=unserialize($val['promotion']);
					$ary_promotion_data=array_shift($ary_promotion_info);
					if(isset($ary_promotion_data['pmn_class'])&&!empty($ary_promotion_data['pmn_class'])){			    //订单只要包含一个促销商品，整个订单为促销
						$val['oi_type']=1;
						$val['ml_rebate']=0;//促销订单返点显示为零
					}
					if(isset($ary_promotion_data['products'])&&!empty($ary_promotion_data['products']) &&!isset($ary_promotion_data['pmn_class'])){
						$promotion_flg=false;
						foreach($ary_promotion_data['products'] as $promotion_rule){
							if(!empty($promotion_rule['rule_info']['name'])){
								$promotion_flg=true;
							}
						}
						if($promotion_flg){//订单只要包含一个促销商品，整个订单为促销
							$val['oi_type']=1;
							$val['ml_rebate']=0;//促销订单返点显示为零
						}else{
							$val['oi_type']=0;
						}
					}
					$total_price +=sprintf("%0.3f", $val['o_all_price']*($val['ml_rebate']/ 100));
				}
		   }else{
		       $this->error('分销商不存在');
		   }
        }
        $this->assign('datalist',$res);
        $this->assign('price',$total_price);
        $this->assign('user_name',$user_name);
        $this->assign('page', $page);
        $this->display();
    }
}