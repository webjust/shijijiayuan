<?php

/**
 * 授权线模型
 *
 * @package Model
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class AuthorizeLineModel extends GyfxModel {

	/**
	 * 根据分销商ID判断商品是否拥有授权
	 * @author zuo <zuojianghua@guanyisoft.com>
	 * @date 2013-01-16
	 * @param int $int_mid 会员ID
	 * @param int $int_gid 商品ID
	 * @return boolean 有授权返回true,无授权返回false
	 */
	public function isAuthorize($int_mid, $int_gid,$is_cache=0) {

		if (!$int_mid) {
			//用户ID不存在返回失败
			return false;
		}

		if($is_cache == '1'){
			$AlSeting = D('SysConfig')->getCfgByModule('GY_AUTHORIZE_LINE',1);
		}else{
			$AlSeting = D('SysConfig')->getCfgByModule('GY_AUTHORIZE_LINE');
		}
		
		
		//给其默认值，默认全部开启
		if(empty($AlSeting)){
            D('SysConfig')->setConfig('GY_AUTHORIZE_LINE','GLOBAL','1',$desc='授权线全站开关');
            $AlSeting = D('SysConfig')->getCfgByModule('GY_AUTHORIZE_LINE');
		}
		if (false == $AlSeting || empty($AlSeting) || $AlSeting['GLOBAL'] == 0) {
			//如果全局开关设置不存在，或者关闭状态，则全部返回true
			return true;
		}
		
		//根据商品ID找到商品品牌和分类
		if($is_cache){
			$brand = D('Gyfx')->selectOneCache('goods','g_is_combination_goods,gb_id', array('g_id' => $int_gid), $ary_order=null,300);
		}else{
			$brand = D("Goods")->where(array('g_id' => $int_gid))->field('g_is_combination_goods,gb_id')->find();
		}		
		if($brand['g_is_combination_goods'] == '1'){
			return true;
		}
		if($is_cache){
			$cate = D('Gyfx')->selectAllCache('related_goods_category','gc_id', array('g_id' => $int_gid), $ary_order=null,$ary_group=null,$ary_limit=null,300);
		}else{
			$cate = D('RelatedGoodsCategory')->field('gc_id')->where(array('g_id' => $int_gid))->select();
		}
		if($is_cache){
			$group = D('Gyfx')->selectAllCache('related_goods_group','gg_id', array('g_id' => $int_gid), $ary_order=null,$ary_group=null,$ary_limit=null,300);			
		}else{
			$group = D('RelatedGoodsGroup')->field('gg_id')->where(array('g_id' => $int_gid))->select();
		}
		if($is_cache){
			$obj_query = D('RelatedAuthorizeMember')->join("right join fx_related_authorize ON fx_related_authorize.al_id = fx_related_authorize_member.al_id ")->where(array('m_id' => $int_mid));
			$authorize = D('Gyfx')->queryCache($obj_query,'',300);
		}else{
			$authorize = D('RelatedAuthorizeMember')->join("right join fx_related_authorize ON fx_related_authorize.al_id = fx_related_authorize_member.al_id ")->where(array('m_id' => $int_mid))->select();			
		}
		//前台应用，没有授权线，允许购买所有商品（海信）
		if(empty($authorize)){
			return true;
		}
		foreach ($authorize as $v) {
			if ($brand['gb_id'] != 0 && $v['ra_gb_id'] == $brand['gb_id']) {
				return true;
			}
			foreach($cate as $cate_info){
				if (count($cate_info) != 0 && $v['ra_gc_id'] == $cate_info['gc_id']) {
					return true;
				}
			}
			foreach($group as $group_info){
				if(count($group_info) != 0 && $v['ra_gp_id'] == $group_info['gg_id']){
					return true;
				}
			}
		}
		return false;
	}	
	
	/**
	 * 分销商授权关系继承关系绑定
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2015-12-04
	 * @param array $ary_params($mid,$p_id,$aid)  会员ID 父级ID 授权下ID
	 * @return boolean 授权继承成功返回true,否则返回false
	 */
	public function DistributorAutoAuthorize($ary_params) {
		$RelatedAuthorizeMember =D('RelatedAuthorizeMember');
		if($ary_params['p_id']){
			$ary_auth_ids = $RelatedAuthorizeMember->where(array('m_id'=>$ary_params['p_id']))->select();
			if(!empty($ary_auth_ids)){
				$datas=array();
				foreach($ary_auth_ids as $auth_key=>$auth_val){
					if($ary_params['a_id']!=''){//后台添加会员授权线
						$auth_val['al_id']=$ary_params['a_id'];
						//$mr_mids =  D('MemberRelation')->field('m_id')->where(array('mr_p_id'=>$ary_params['m_id']))->select();
						$mr_mids =  $this->getSubDistributor($ary_params['m_id'],$datas);
						if(!empty($mr_mids)){
							$this->AddSubDistributorAuthorizeLine($mr_mids,$auth_val['al_id']);
						}
					}else{//权限关系列表
						$mr_mids =  $this->getSubDistributor($ary_params['m_id'],$datas);
						//$mr_mids =  D('MemberRelation')->field('m_id')->where(array('mr_p_id'=>$ary_params['m_id']))->select();
						if(!empty($mr_mids)){
							$this->AddSubDistributorAuthorizeLine($mr_mids,$auth_val['al_id']);
						}
						$datas = array('m_id'=>$ary_params['m_id'],'al_id'=>$auth_val['al_id']);
						if (false == $RelatedAuthorizeMember->where($datas)->find()) {
							$results = $RelatedAuthorizeMember->data($datas)->add();
							if(!$results){
								return false;
							}else{
								continue;
							}
						}
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * 获得授权线的子分销商
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2015-12-11
	 * @param int $mid 会员ID
	 * @return array 分销商id
	 */
	public function getSubDistributor($mid,&$datas){
		$res =  D('MemberRelation')->field('m_id')->where(array('mr_p_id'=>$mid))->select();
		if(!empty($res)){
			foreach($res as $key=>$val){
				array_push($datas,$val['m_id']);
				$this->getSubDistributor($val['m_id'],$datas);
			}
		}
		return $datas;
	}
	/**
	 * 子分销商授权线添加
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2015-12-11
	 * @param int $mid 会员ID
	 * @return array 分销商id
	 */
	public function AddSubDistributorAuthorizeLine($ary_mids,$al_id){
		$RelatedAuthorizeMember =D('RelatedAuthorizeMember');
		foreach($ary_mids as $mr_key=>$mr_val){
			$datas = array('m_id'=>$mr_val,'al_id'=>$al_id);
			if (false == $RelatedAuthorizeMember->where($datas)->find()) {
				$results = $RelatedAuthorizeMember->data($datas)->add();
				if(!$results){
					return false;
				}else{
					continue;
				}
			}
		}
		return true;
	}
	
	/**
	 * 分销商授权关系继承关系解除
	 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
	 * @date 2015-12-04
	 * @param int $int_mid 会员ID
	 * @return boolean 授权继承成功返回true,否则返回false
	 */
	public function DistributorDelAuthorize($mid,$aid) {
		if($aid==''){
			$ary_alds=D('RelatedAuthorizeMember')->where(array('m_id'=>$mid))->select();
			if(empty($ary_alds)){
				return true;
			}else{
				//先删除自己
				foreach($ary_alds as $val){
					$result = D('RelatedAuthorizeMember')->where(array('m_id'=>$val['m_id'],'al_id'=>$val['al_id']))->delete();
				}
			}
		}else{
			$ary_alds[]=array('al_id'=>$aid);
		}
		//删除下级分销商
		foreach($ary_alds as $value){
			$Memberrelation = M('member_relation', C('DB_PREFIX'), 'DB_CUSTOM');
			$mr_data = $Memberrelation->where(array('mr_p_id'=>$mid))->select();
			$ary_mrda = array();
			$art_mrdat = array();
			foreach($mr_data as $v){
				$mr_da =  $Memberrelation->where(array('mr_p_id'=>$v['m_id']))->select();
				foreach($mr_da as $val){
					$ary_mrda[] = $val['m_id'];
				}
				$art_mrdat[] = $v['m_id'];
			}
			$ary_mrdata = array_merge($ary_mrda,$art_mrdat);
			foreach($ary_mrdata as $vals){
				$wheres = array();
				if ((int) $value['al_id'] != -1) {
					$wheres['al_id'] = $value['al_id'];
				}
				$wheres['m_id'] = $vals;
				$res_ary=D('RelatedAuthorizeMember')->where($wheres)->find();
				if(!empty($res_ary)){
					$results = D('RelatedAuthorizeMember')->where($wheres)->delete();
					if($results==false){
						return false;
					}
				}
			}
		}
		return true;
	}
	
	

}