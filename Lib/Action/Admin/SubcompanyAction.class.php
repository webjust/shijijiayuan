<?php

/**
 * 后台授权线管理
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
 * @date 2013-07-24
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class SubcompanyAction extends AdminAction {
	public function _initialize() {
		parent::_initialize();
		$this->setTitle('- 子公司管理');
	}

	/**
	 * 子公司列表页
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 */
	public function pageList() {
		$this->getSubNav(6, 3, 10);
		$Subcompany = D('Subcompany');
		$RelatedGoodSubcompany = D('RelatedGoodSubcompany');
		$str_s_name = trim($this->_post('s'));
		$where = array();
		//公司名称搜索
		if(isset($str_s_name)&&!empty($str_s_name)){
			$where['s_name']=array('like','%'.$str_s_name.'%');
			$this->assign('smessage',$str_s_name);
		}
		//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$count = $Subcompany->where($where)->count();
		$Page = new Page($count, 15);
		//获取子公司信息
		$data['list'] = $Subcompany->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach($data['list'] as $key => $val){
			//取出子公司商品分类名称
			$rs = $RelatedGoodSubcompany->join("fx_goods_category ON fx_goods_category.gc_id = fx_related_good_subcompany.ra_gc_id ")
			->where("ifnull(fx_goods_category.gc_name,'') != '' and fx_related_good_subcompany.s_id=".$val['s_id'])
			->Field("fx_goods_category.gc_name")
			->select();
			$result=array();
			foreach($rs as $v){
				$result[] = $v['gc_name'];
			}
			//取出子公司商品品牌名称
			$brand_rs = $RelatedGoodSubcompany->join("fx_goods_brand ON fx_goods_brand.gb_id = fx_related_good_subcompany.ra_gb_id ")
			->where("ifnull(fx_goods_brand.gb_name,'') != '' and fx_related_good_subcompany.s_id=".$val['s_id'])
			->Field("fx_goods_brand.gb_name")
			->select();
			foreach($brand_rs as $v){
				$result[] = $v['gb_name'];
			}
			$data['list'][$key]['catelist']=implode('、',$result);
		}
		$data['page'] = $Page->show();
		$this->assign($data);
		$this->display();
	}

	/**
	 * 子公司添加页
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 */
	public function pageAdd(){
		$this->getSubNav(6, 3, 20);
		$Subcompany = M('Subcompany');
		$RelatedGoodSubcompany = D('RelatedGoodSubcompany');
		$arr_other_data_info = $RelatedGoodSubcompany->select();
		$othercates = array();
		$otherbrands = array();
		foreach ($arr_other_data_info as $v) {
			if(!empty($v['ra_gb_id'])){
				$otherbrands[] = $v['ra_gb_id'];
			}
			if(!empty($v['ra_gc_id'])){
				$othercates[] = $v['ra_gc_id'];
			}
		}
		//得到商品分类
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌
		$brandHtml = $this->get_brand($brands,$otherbrands);
        //取出子公司管辖区域
        $where['s_id'] =0;
        $ary_area=D('AreaJurisdiction')->where($where)->select();
		$this->assign($data);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);
        $this->assign('ary_area', $ary_area);
		$this->display();
	}

	/**
	 * 子公司添加
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 * @update by Wangguibin 
	 * @updateTime 2013-07-26
	 */
	public function doAdd(){
		$Subcompany = D('Subcompany');
		$data = $Subcompany->create();
		$data['s_create_time'] = date('Y-m-d h:i:s');
		$data['s_modify_time'] = date('Y-m-d h:i:s');
		//三级升级行政区域的ID，如果没设置地三级，就取第二级，否则第一级
		$data['cr_id'] = 0;
		if (isset($_POST['region1']) && $_POST['region1'] > 0) {
			$data['cr_id'] = $_POST['region1'];
		} else if (isset($_POST['city']) && $_POST['city'] > 0) {
			$data['cr_id'] = $_POST['city'];
		} else if (isset($_POST['province']) && $_POST['province'] > 0) {
			$data['cr_id'] = $_POST['province'];
		}
		//子公司主表保存信息
		$al_id = $this->_post('al_id');
        $where=array();
        $where['s_name']=$_POST['s_name'];
        $rs=$Subcompany->where($where)->find();
        if(empty($rs)){
            $result = $Subcompany->data($data)->add();
        }else{
            $this->error('子公司添加失败，公司名称已存在');
        }
		
		if (false == $result) {
			$this->error('子公司添加保存失败');
		} else {
            //子公司管辖区域
            $ary_area=$this->_post('area');
            if(!empty($ary_area)){
                foreach($ary_area as $area){
                    $s_data['s_id']=$result;
                    D('AreaJurisdiction')->where(array('cr_id' => $area))->data($s_data)->save();
                }
            }
			$RelatedGoodSubcompany = D('RelatedGoodSubcompany');
			$cates = $this->_post('shopCat');
			$brands = $this->_post('shopBrand');
			//已被其他子公司选择的类目品牌则不允许保存
			$otherwhere = array();
			$otherwhere['s_id'] = array('neq',$result);
			$arr_other_data_info = $RelatedGoodSubcompany->where($otherwhere)->select();
			$othercates = array();
			$otherbrands = array();
			foreach ($arr_other_data_info as $v) {
				if(!empty($v['ra_gb_id'])){
					$otherbrands[] = $v['ra_gb_id'];
				}
				if(!empty($v['ra_gc_id'])){
					$othercates[] = $v['ra_gc_id'];
				}
			}
			foreach($cates as $c){
				if(in_array($c,$othercates)){
				    $wheresub=array();
                    $wheresub['s_id']=$result;
                    $Subcompany->where($wheresub)->delete();
					$this->error('子公司修改保存失败,部分类目已被其他子公司使用');
				}
			}
			foreach($brands as $b){
				if(in_array($b,$otherbrands)){
				    $wheresub=array();
                    $wheresub['s_id']=$result;
                    $Subcompany->where($wheresub)->delete();
					$this->error('子公司修改保存失败,部分品牌已被其他子公司使用');
				}
			}
			//先删除所有关联关系
			$RelatedGoodSubcompany->where(array('s_id' => $result))->delete();
			$ary_insert = array();
			//插入类目信息
			if(!empty($cates)){
				foreach($cates as $cate){
					$ary_insert[] = array(
	                    'ra_gc_id' => $cate,
						'ra_gb_id'=>'0',
	                    's_id' => $result
					);
				}
			}
			//插入品牌信息
			if(!empty($brands)){
				foreach($brands as $brand){
					$ary_insert[] = array(
	                    'ra_gc_id' => 0,
						'ra_gb_id'=>$brand,
	                    's_id' => $result
					);
				}
			}
			//插入子公司关联商品信息
			$res = $RelatedGoodSubcompany->addAll($ary_insert);
			if($res){
				$this->success('子公司添加保存成功', U('Admin/Subcompany/pageList'));
			}else{
                $wheresub=array();
                $wheresub['s_id']=$result;
                $Subcompany->where($wheresub)->delete();
				$this->error('子公司添加保存失败,管理类目品牌失败');
			}
		}
	}

	/**
	 * 修改子公司页
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 */
	public function pageEdit() {
		$this->getSubNav(6, 3, 10);
		$sid = $this->_get('sid');
		$Subcompany = D('Subcompany');
		$RelatedAuthorizeSubcompany = D('RelatedGoodSubcompany');
		//查询出相关商品分类和品牌信息
		$arr_data_info = $RelatedAuthorizeSubcompany->where(array('s_id' => $sid))->select();
		$cates = array();
        $cate_name = array();
		$brands = array();
        $brand_name = array();
		foreach ($arr_data_info as $v) {
			if(!empty($v['ra_gb_id'])){
				$brands[] = $v['ra_gb_id'];
                $brand_name[] = D('GoodsBrand')->where(array('gb_id'=>$v['ra_gb_id']))->getField('gb_name');
			}
			if(!empty($v['ra_gc_id'])){
				$cates[] = $v['ra_gc_id'];
                $cate_name[] = D('GoodsCategory')->where(array('gc_id'=>$v['ra_gc_id']))->getField('gc_name');
			}
		}
		//已被其他子公司选择的类目
		$otherwhere = array();
		$otherwhere['s_id'] = array('neq',$sid);
		$arr_other_data_info = $RelatedAuthorizeSubcompany->where($otherwhere)->select();
		$othercates = array();
		$otherbrands = array();
		foreach ($arr_other_data_info as $v) {
			if(!empty($v['ra_gb_id'])){
				$otherbrands[] = $v['ra_gb_id'];
			}
			if(!empty($v['ra_gc_id'])){
				$othercates[] = $v['ra_gc_id'];
			}
		}
		//把品牌信息和分类信息组合成变量用来展示
		$brands = implode(',',$brands);
		$cates = implode(',', $cates);
		$brand_name = implode(',', $brand_name);
		$cate_name = implode(',', $cate_name);
		$data['info'] = $Subcompany->where(array('s_id' => $sid))->find();
		$data['info']['cids'] = $cates;
		$data['info']['bids'] = $brands;
		$data['info']['cname'] = $cate_name;
        $data['info']['bname'] = $brand_name;
		//得到商品分类html
		$catHtml = $this->get_cate($cates,$othercates);
		//得到商品品牌html
		$brandHtml = $this->get_brand($brands,$otherbrands);
		//获得公司区域信息
        //取出子公司管辖区域
        $where['s_id'] =array('like',array($sid,0),'OR');
        $ary_area=D('AreaJurisdiction')->where($where)->select();
        $this->assign('ary_area', $ary_area);
		$array_region = D("CityRegion")->getCityRegionInfoByLastCrId($data['info']['cr_id']);
		$this->assign('region', $array_region);
		$this->assign('catHtml', $catHtml);
		$this->assign('brandHtml', $brandHtml);
		$this->assign($data);
		$this->display();
	}

	/**
	 * 得到类目
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-26
	 */
	public function get_cate($cate_id='',$othercates){
		if(!empty($cate_id)) $cate_ids = explode(',',$cate_id);
		else $cate_ids = array();
		$cate_html = '';
		//获取商品分类并传递到模板
		$cateList = D("GoodsCategory")->getChildLevelCategoryById(0);
		foreach($cateList as $cat){
			$cate_html .='<li class="cat_list_li" id="li_catid_'.$cat['gc_id'].'" is_parent="'.$cat['gc_is_parent'].'" parent_id="'.$cat['gc_parent_id'].'" style="margin-left:'.intval($cat[gc_level]*3).'em;" >';
			$cate_html .='<input type="checkbox"  id="shopCat__'.$cat['gc_id'].'" ';
			//展示已选择的商品类目
			if(!empty($cate_ids)){
				if(in_array($cat['gc_id'],$cate_ids)){
					$cate_html .=' checked="checked" ';
				}
			}
			//已被其他公司选择的商品分类和品牌不允许重复选择使用
			if(!empty($othercates)){
				if(in_array($cat[gc_id],$othercates)){
					$cate_html .=' disabled="true"  ';
				}
			}
			$cate_html .=' value="'.$cat['gc_id'].'" ref="'.$cat['gc_name'].'"  name="shopCat[]" class="cat-checkbox" pid="'.$cat['gc_parent_id'].'" />';
			$cate_html .=' <label for="shopCat__'.$cat['gc_id'].'" style="cursor:pointer;" ';
				
			if(in_array($cat[gc_id],$othercates)){
				$cate_html .=' disabled="true"  title="此分类已被其他子公司使用不能再次使用"';
			}
			$cate_html .='>'.$cat['gc_name'];
			if($cat['gc_is_display'] != '1'){
				$cate_html .='<span style="color:#ff0000;">[前台不显示]</span>';
			}
			$cate_html .='</label></li>';
		}
		return $cate_html;
	}

	/**
	 * 得到品牌
	 * @author wangguibin <wangguibin@guanyisoft.com>
	 * @date 2013-07-26
	 */
	public function get_brand($brand_id='',$otherbrands){
		if(!empty($brand_id)) $$brand_ids = explode(',',$brand_id);
		else $brand_ids = array();
		$brand_html = '';
		$brandList = D("GoodsBrand")->where(array("gb_status"=>1))->field('gb_id,gb_name')->order('gb_order asc')->select();
		foreach($brandList as $brand){
			$brand_html .='<li class="brand_list_li" >';
			$brand_html .='<input type="checkbox"  id="shopBrand__'.$brand['gb_id'].'" ';
			if(!empty($brand_ids)){
				if(in_array($brand[gb_id],$brand_ids)){
					$brand_html .=' checked="checked" ';
				}
			}
			if(!empty($otherbrands)){
				if(in_array($brand[gb_id],$otherbrands)){
					$brand_html .=' disabled="true"  ';
				}
			}
			$brand_html .=' value="'.$brand['gb_id'].'" ref="'.$brand['gb_name'].'"  name="shopBrand[]" class="brand-checkbox"  />';
			$brand_html .=' <label for="shopBrand__'.$brand['gb_id'].'" style="cursor:pointer;" ';
				
			if(in_array($brand[gb_id],$otherbrands)){
				$brand_html .=' disabled="true"  title="此品牌已被其他子公司使用不能再次使用"';
			}
			$brand_html .='>'.$brand['gb_name'];
			$brand_html .='</label></li>';
		}
		return $brand_html;
	}

	/**
	 * 修改子公司
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 */
	public function doEdit() {
		$Subcompany = D('Subcompany');
		$data = $Subcompany->create();
		$data['s_modify_time'] = date('Y-m-d h:i:s');
		$data['cr_id'] = 0;
		if (isset($_POST['region1']) && $_POST['region1'] > 0) {
			$data['cr_id'] = $_POST['region1'];
		} else if (isset($_POST['city']) && $_POST['city'] > 0) {
			$data['cr_id'] = $_POST['city'];
		} else if (isset($_POST['province']) && $_POST['province'] > 0) {
			$data['cr_id'] = $_POST['province'];
		}
		//保存公司主信息
		$result = $Subcompany->where(array('s_id' => $data['s_id']))->data($data)->save();
		if (false == $result) {
			$this->error('子公司修改保存失败');
		} else {
            //子公司管辖区域
            $ary_area=$this->_post('area');
            if(!empty($ary_area)){
                $area_data=D('AreaJurisdiction')->where(array('s_id' => $data['s_id']))->select();
                foreach($area_data as $val){
                    $sub_data['s_id']=0;
                    D('AreaJurisdiction')->where(array('cr_id' => $val['cr_id']))->data($sub_data)->save();
                }
                foreach($ary_area as $area){
                    $s_data['s_id']=$data['s_id'];
                    D('AreaJurisdiction')->where(array('cr_id' => $area))->data($s_data)->save();
                }
            }
			$RelatedGoodSubcompany = D('RelatedGoodSubcompany');
			$cates = $this->_post('shopCat');
			$brands = $this->_post('shopBrand');
			//已被其他子公司选择的类目品牌则不允许保存
			$otherwhere = array();
			$otherwhere['s_id'] = array('neq',$data['s_id']);
			$arr_other_data_info = $RelatedGoodSubcompany->where($otherwhere)->select();
			$othercates = array();
			$otherbrands = array();
			foreach ($arr_other_data_info as $v) {
				if(!empty($v['ra_gb_id'])){
					$otherbrands[] = $v['ra_gb_id'];
				}
				if(!empty($v['ra_gc_id'])){
					$othercates[] = $v['ra_gc_id'];
				}
			}
			//判断商品分类和品牌是否被其他子公司使用
			foreach($cates as $c){
				if(in_array($c,$othercates)){
					$this->error('子公司修改保存失败,部分类目已被其他子公司使用');
				}
			}
			//判断商品分类和品牌是否被其他子公司使用
			foreach($brands as $b){
				if(in_array($b,$otherbrands)){
					$this->error('子公司修改保存失败,部分品牌已被其他子公司使用');
				}
			}
			//先删除关联的分类和品牌信息
			$RelatedGoodSubcompany->where(array('s_id' => $data['s_id']))->delete();
			$ary_insert = array();
			//插入类目信息
			if(!empty($cates)){
				foreach($cates as $cate){
					$ary_insert[] = array(
	                    'ra_gc_id' => $cate,
						'ra_gb_id'=>'0',
	                    's_id' => $data['s_id']
					);
				}
			}
			//插入品牌信息
			if(!empty($brands)){
				foreach($brands as $brand){
					$ary_insert[] = array(
	                    'ra_gc_id' => 0,
						'ra_gb_id'=>$brand,
	                    's_id' => $data['s_id']
					);
				}
			}
			$res = $RelatedGoodSubcompany->addAll($ary_insert);
			if($res){
				$this->success('子公司修改保存成功', U('Admin/Subcompany/pageList'));
			}else{
				$this->error('子公司修改保存失败,管理类目品牌失败');
			}
		}
	}

	/**
	 * 删除子公司
	 * @author zhuyuanjie <zhuyuanjie@guanyisoft.com>
	 * @date 2013-07-24
	 */
	public function doDel() {
		$sid = $this->_get('sid');
		$Subcompany = D('Subcompany');
		if(is_array($sid)){
			$sids=explode(',',$sid);
			$where['s_id']=array('in',$sid);
		}else{
			$where=array('s_id' => $sid);
		}
		
		$obj = M('', C('DB_PREFIX'), 'DB_CUSTOM');
		$obj->startTrans();
		$result = $Subcompany->where($where)->delete();
		if ($result) {
			
				$RelatedGoodSubcompany = D('RelatedGoodSubcompany');
				$sub_count  = $RelatedGoodSubcompany->where($where)->count();
				if($sub_count>0){
					
					$res = $RelatedGoodSubcompany->where($where)->delete();
					if(!$res){
						$obj->rollback();
						$this->error('删除失败！');
					}			
				}
                $sub_area_count  = D('AreaJurisdiction')->where($where)->count();
                if($sub_area_count>0){
                    $sub_data['s_id']=0;
                    $sub_area_res=D('AreaJurisdiction')->where(array('s_id' => array("IN",$sid)))->data($sub_data)->save();
                    if(!$sub_area_res){
                        $obj->rollback();
						$this->error('删除失败！');
                    }
                }
				$obj->commit();
				$this->success('删除成功！', U('Admin/Subcompany/pageList'));
		} else {
			$this->error('删除失败！');
		}
	}

}
?>