<?php

/**
 * 后台授权线管理
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-15
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class AuthorizeAction extends AdminAction {

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle('- 授权线管理');
    }

    /**
     * 默认控制器，重定向到授权线管理列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function index() {
        $this->redirect(U('Admin/Authorize/pageSet'));
    }

    /**
     * 授权线管理列表页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function pageList() {
        $this->getSubNav(6, 2, 10);
        $Authorize = D('AuthorizeLine');
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        $count = $Authorize->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $Authorize->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($data['list'] as $k => $v) {
            $data['list'][$k]['brands'] = D('RelatedAuthorize')
                    ->where(array('al_id' => $v['al_id'], 'ra_gb_id' => array('NEQ', 0)))
                    ->field(array('gb_name'))
                    ->join("left join fx_goods_brand on fx_goods_brand.gb_id = fx_related_authorize.ra_gb_id")
                    ->select();
            $data['list'][$k]['cates'] = D('RelatedAuthorize')
                    ->where(array('al_id' => $v['al_id'], 'ra_gc_id' => array('NEQ', 0)))
                    ->field(array('gc_name'))
                    ->join("left join fx_goods_category on fx_goods_category.gc_id = fx_related_authorize.ra_gc_id")
                    ->select();
            $data['list'][$k]['groups'] = D('RelatedAuthorize')
                    ->where(array('al_id' => $v['al_id'], 'ra_gp_id' => array('NEQ', 0)))
                    ->field(array('gg_name'))
                    ->join("left join fx_goods_group on fx_goods_group.gg_id = fx_related_authorize.ra_gp_id")
                    ->select();
        }
        $data['page'] = $Page->show();
        // 全局设置 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $AlSeting = D('SysConfig')->getCfgByModule('GY_AUTHORIZE_LINE');
        if (false == $AlSeting || empty($AlSeting)) {
            $data['config'] = array('GLOBAL' => 0);
            D('SysConfig')->setConfig('GY_AUTHORIZE_LINE', 'GLOBAL', 0, '授权线全站开关');
        } else {
            $data['config'] = $AlSeting;
        }
        // 显示页面 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 授权线添加页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function pageAdd() {
        $this->getSubNav(6, 2, 20);
        $Goods = D("ViewGoods");
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['brands'] = $Goods->getBrands();
        $data['cates'] = $Goods->getCates();
        $data['groups'] = $Goods->getGroups();
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行授权线添加
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doAdd() {
        $Authorize = D('AuthorizeLine');
        $data = $Authorize->create();
        $data['al_create_time'] = date('Y-m-d h:i:s');
        $cates = $this->_post('gc_ids');
        $brands = $this->_post('gb_ids');
        $groups = $this->_post('gp_ids');
        $result = $Authorize->data($data)->add();

        if (false == $result) {
            $this->error('授权线添加保存失败');
        } else {
            //如果设置为默认授权线，则将其他授权线的默认设置清除掉
            if ((int) $data['al_default'] == 1) {
                $Authorize->where(array('al_id' => array('NEQ', $result)))->data(array('al_default' => 0))->save();
            }
            $ary_insert = array();
            //增加品牌关联
            foreach ($cates as $v) {
                $ary_insert[] = array(
                    'al_id' => $result,
                    'ra_gb_id' => 0,
                    'ra_gc_id' => $v,
                    'ra_gp_id' => 0
                );
            }
            //增加分类关联
            foreach ($brands as $v) {
                $ary_insert[] = array(
                    'al_id' => $result,
                    'ra_gb_id' => $v,
                    'ra_gc_id' => 0,
                    'ra_gp_id' => 0
                );
            }
            // 增加分组关联
            foreach($groups as $g){
                $ary_insert[] = array(
                    'al_id' => $result,
                    'ra_gb_id' => 0,
                    'ra_gc_id' => 0,
                    'ra_gp_id' => $g
                    );
            }
            D('RelatedAuthorize')->addAll($ary_insert);
            $this->success('授权线添加保存成功', U('Admin/Authorize/pageList'));
        }
    }

    /**
     * 修改授权线
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function pageEdit() {
        $this->getSubNav(6, 2, 10);
        $Authorize = D('AuthorizeLine');
        $aid = $this->_get('aid');
        $Goods = D("ViewGoods");
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['brands'] = $Goods->getBrands();
        $data['cates'] = $Goods->getCates();
        $data['groups'] = $Goods->getGroups();
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['info'] = $Authorize->where(array('al_id' => $aid))->find();
        $result = D('RelatedAuthorize')->where(array('al_id' => $aid))->select();

        foreach ($data['brands'] as $k => $v) {
            $data['brands'][$k]['selected'] = 0;
            foreach ($result as $v2) {
                if ($v['gb_id'] == $v2['ra_gb_id']) {
                    $data['brands'][$k]['selected'] = 1;
                }
            }
        }

        foreach ($data['cates'] as $k => $v) {
            $data['cates'][$k]['selected'] = 0;
            foreach ($result as $v2) {
                if ($v['gc_id'] == $v2['ra_gc_id']) {
                    $data['cates'][$k]['selected'] = 1;
                }
            }
        }
        // 分组
        foreach($data['groups'] as $k=>$v){
            $data['groups'][$k]['selected'] = 0;
            foreach($result as $v2){
                if($v['gg_id'] == $v2['ra_gp_id']){
                    $data['groups'][$k]['selected'] = 1;
                }
            }
        }

        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行修改授权线
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doEdit() {
        $Authorize = D('AuthorizeLine');
        $data = $Authorize->create();
        $data['al_create_time'] = date('Y-m-d h:i:s');
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $cates = $this->_post('gc_ids');
        $brands = $this->_post('gb_ids');
        $groups = $this->_post('gp_ids');
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $result = $Authorize->where(array('al_id' => $data['al_id']))->data($data)->save();
        if (false == $result) {
            $this->error('修改授权线失败');
        } else {
            //如果设置为默认授权线，则将其他授权线的默认设置清除掉
            if ((int) $data['al_default'] == 1) {
                $Authorize->where(array('al_id' => array('NEQ', $data['al_id'])))->data(array('al_default' => 0))->save();
            }
            //删除授权线商品关系，然后重建
            D('RelatedAuthorize')->where(array('al_id' => $data['al_id']))->delete();
            $ary_insert = array();
            //增加品牌关联
            foreach ($cates as $v) {
                $ary_insert[] = array(
                    'al_id' => $data['al_id'],
                    'ra_gb_id' => 0,
                    'ra_gc_id' => $v,
                    'ra_gp_id' => 0
                );
            }

            //增加分类关联
            foreach ($brands as $v) {
                $ary_insert[] = array(
                    'al_id' => $data['al_id'],
                    'ra_gb_id' => $v,
                    'ra_gc_id' => 0,
                    'ra_gp_id' => 0
                );
            }
            // 增加分组
            foreach($groups as $v){
                $ary_insert[] = array(
                    'al_id' => $data['al_id'],
                    'ra_gb_id' => 0,
                    'ra_gc_id' => 0,
                    'ra_gp_id' => $v
                    );
            }
            D('RelatedAuthorize')->addAll($ary_insert);
            $this->success('授权线修改保存成功', U('Admin/Authorize/pageList'));
        }
    }

    /**
     * 删除授权线，支持批量删除
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doDel() {
        $aid = $this->_get('aid');
        $Authorize = D('AuthorizeLine');
        $RelatedAuthorizeMember = D('RelatedAuthorizeMember');
        $result_used = $RelatedAuthorizeMember->field(array('al_id'))->group('al_id')->select();
        $aid_uesd = array();
        foreach ($result_used as $v) {
            $aid_uesd[] = $v['al_id'];
        }
        //删除条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        if (empty($aid_uesd)) {
            $where = " 1 ";
        } else {
            $where = " al_id not in (" . implode(',', $aid_uesd) . ")";
        }
        if (is_array($aid)) {
            $where = $where . " and al_id in (" . implode(',', $aid) . ")";
        } else {
            $where = $where . " and al_id = " . $aid;
        }
        $result = $Authorize->where($where)->delete();
        if ($result) {
            D('related_authorize')->where($where)->delete();
            $this->success('删除成功！如果有授权线没有被删除或者部分删除，可能是因为该授权线已被某些会员应用');
        } else {
            $this->success('删除失败！如果有授权线没有被删除或者部分删除，可能是因为该授权线已被某些会员应用');
        }
    }

    /**
     * 设置默认授权线
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doDefault() {
        $aid = $this->_get('aid');
        $Authorize = D('AuthorizeLine');
        $Authorize->where(array('al_id' => array('NEQ', $aid)))->data(array('al_default' => 0,'al_modify_time'=>date('Y-m-d H:i:s')))->save();
        $result = $Authorize->where(array('al_id' => $aid))->data(array('al_default' => 1,'al_modify_time'=>date('Y-m-d H:i:s')))->save();
        if (false == $result) {
            $this->error('设置默认授权线失败');
        } else {
            $this->success('设置默认授权线成功');
        }
    }

    /**
     * 会员授权线设置页
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function pageSet() {
        $this->getSubNav(6, 2, 30);

        $Member = D('Members');
        $Authorize = D('AuthorizeLine');
        $m_name = trim($this->_get('m_name'));
        //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        if(!empty($m_name)){
        	$where['m_name'] = $m_name;
        }
        $count = $Member->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $Member->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($data['list'] as $k => $v) {
            $data['list'][$k]['authorize'] = D('RelatedAuthorizeMember')
                    ->where(array('m_id' => $v['m_id']))
                    ->join("left join fx_authorize_line on fx_authorize_line.al_id = fx_related_authorize_member.al_id")
                    ->field(array('al_name', 'fx_authorize_line.al_id' => 'al_id'))
                    ->select();
        }

        $data['authorize'] = $Authorize->where(array('al_valid' => 1))->select();
        $data['page'] = $Page->show();
        // 全局设置 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $AlSeting = D('SysConfig')->getCfgByModule('GY_AUTHORIZE_LINE');
        if (false == $AlSeting || empty($AlSeting)) {
            $data['config'] = array('GLOBAL' => 0);
            D('SysConfig')->setConfig('GY_AUTHORIZE_LINE', 'GLOBAL', 0, '授权线全站开关');
        } else {
            $data['config'] = $AlSeting;
        }
        // 页面显示 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign('m_name',$m_name);
        $this->assign($data);
        $this->display();
    }

    /**
     * 为会员设置授权线
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doSet() {
        layout(false);
        $mid = $this->_get('mid');
        $aid = $this->_get('aid');
        // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//给其默认值，默认全部开启
        D('SysConfig')->setConfig('GY_AUTHORIZE_LINE','GLOBAL','1',$desc='授权线全站开关');
        $RelatedAuthorizeMember = D('RelatedAuthorizeMember');
        $data = array('al_id' => $aid, 'm_id' => $mid);
        if (false == $RelatedAuthorizeMember->where($data)->find()) {
            $result = $RelatedAuthorizeMember->data($data)->add();

           //@author zhaozhicheng  ++++++++++++++++++++++++++++++++++++++++++++++++++++
		   $ary_params['m_id']=$mid;
		   $ary_params['p_id']=$mid;
		   $ary_params['a_id']=$aid;
		   $bool_res=D('AuthorizeLine')->DistributorAutoAuthorize($ary_params);
		   if($bool_res==false){
			  $this->ajaxReturn(false); 
		   }
            //  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            if ($result) {
                $info = D('AuthorizeLine')->where(array('al_id' => $aid))->find();
                $this->assign('info', $info);
                $this->display();
            } else {
                $this->ajaxReturn(false);
            }
        } else {
            $this->ajaxReturn(false);
        }
    }

    /**
     * 删除会员的授权线，支持批量删除
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-15
     */
    public function doDelSet() {
        layout(false);
        $mid = $this->_get('mid');
        $aid = $this->_get('aid');
        if(!empty($mid)){
            // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $where = array();
            if (is_array($mid)) {
                $where['m_id'] = array('IN', $mid);
            } else {
                $where['m_id'] = $mid;
            }
            if ((int) $aid != -1) {
                $where['al_id'] = $aid;
            }
            // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $result = D('RelatedAuthorizeMember')->where($where)->delete();

            // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            $bool_res=D('AuthorizeLine')->DistributorDelAuthorize($mid,$aid);
		    if($bool_res==false){
			    $this->error('删除失败');
		    }
            // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            if ($result) {
                $this->success('删除成功', U('Admin/Authorize/pageSet'));
            } else {
                $this->error('删除失败');
            }
        }else{
            $this->error('请选择你要操作的对象');
        }
        
    }

    /**
     * 设置全局授权线开关
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-16
     */
    public function doSetCfg() {
        layout(false);
        $GLOBAL = (int) $this->_get('GLOBAL');
        $result = D('SysConfig')->setConfig('GY_AUTHORIZE_LINE', 'GLOBAL', $GLOBAL, '授权线全站开关');
        if ($result) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

}