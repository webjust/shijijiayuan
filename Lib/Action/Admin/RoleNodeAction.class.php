<?php

/**
 * 后台管理员节点管理
 *
 * @subpackage RoleNode
 * @package Action
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-23
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RoleNodeAction extends AdminAction {

    /**
     * 控制器初始化
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU7_5_0'));
    }

    /**
     * 默认控制器
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function index() {
        $this->redirect(U('Admin/RoleNode/pageList'));
    }

    /**
     * 节点列表
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function pageList() {
        $this->getSubNav(8, 5, 10);
        $ary_request = $this->_request();
        $where = array();
        if(!empty($ary_request['auth_type']) && $ary_request['auth_type'] != '-1'){
            $where['auth_type'] = $ary_request['auth_type'];
        }
        if(!empty($ary_request['module_name']) && isset($ary_request['module_name'])){
            $where['module_name'] = $ary_request['module_name'];
        }
        if(!empty($ary_request['action_name']) && isset($ary_request['action_name'])){
            $where['action_name'] = $ary_request['action_name'];
        }
        $name = $this->getActionName();
        $count = D($name)->where($where)->count();
        $obj_page = new Page($count, 20);
        $page = $obj_page->show();
        $ary_data = D($name)->where($where)->limit($obj_page->firstRow, $obj_page->listRows)->select();
        $this->assign("data", $ary_data);
        $this->assign("filter",$ary_request);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 节点添加
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function pageAdd() {
        $this->getSubNav(8, 5, 20);
        $this->display();
    }

    /**
     * 处理节点添加
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function doAdd() {
        $ary_request = $this->_request();
        $name = $this->getActionName();
        $model = D($name);
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
        if ($data['module_name'] == '') {
            $data['module_name'] = $data['module'];
        }
        if ($ary_request['module'] == "" && $ary_request['action'] != "") {
            $data['auth_type'] = 2;
        } elseif ($ary_request['module'] != "" && $ary_request['action'] == "") {
            $data['auth_type'] = 1;
        } else {
            $data['auth_type'] = 0;
        }
        $count = D($name)->where(array('module' => $ary_request['module'], 'action' => $ary_request['action']))->count();
        if ($count > 0) {
            $this->error("添加的节点已经存在");
        }
        //echo "<pre>";print_r($data);exit;
        //保存当前数据
        $list = $model->add($data);
        if (false !== $list) {
            $this->success("节点添加成功");
        } else {
            $this->error("节点添加失败");
        }
    }

    /**
     * 编辑节点
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-23
     */
    public function pageEdit() {
        $this->getSubNav(8, 5, 30, "编辑节点");
        $node_id = intval($this->_get("id"));
        $name = $this->getActionName();
        $vo = D($name)->getById($node_id);
        $this->assign("vo", $vo);
        $this->display();
    }

    
    /**
     * 处理编辑节点
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-23
     */
    public function doEdit() {
        $ary_request = $this->_request();
        $roleNode = $this->getActionName();
        $model = D($roleNode);
        $data = $model->create();
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
        if (!empty($data) && is_array($data)) {
            if ($data['module_name'] == '') {
                $data['module_name'] = $data['module'];
            }
            if ($ary_request['module'] == "" && $ary_request['action'] != "") {
                $data['auth_type'] = 2;
            } elseif ($ary_request['module'] != "" && $ary_request['action'] == "") {
                $data['auth_type'] = 1;
            } else {
                $data['auth_type'] = 0;
            }
            $where = array();
            $where['module']   = $ary_request['module'];
            $where['action']   = $ary_request['action'];
            $where['id']   = array('NEQ',$ary_request['id']);
            $count = D($roleNode)->where($where)->count();
            if ($count > 0) {
                $this->error("添加的节点已经存在");
            }
            //保存当前数据
            $list = $model->where(array('id' => $ary_request['id']))->save($data);
            if (false !== $list) {
                $this->success("节点编辑成功");
            } else {
                $this->error("节点编辑失败");
            }
        } else {
            $this->error("数据错误");
        }
    }

    /**
     * 节点启用/停用
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function doEditStatus(){
        $ary_request = $this->_request();
        //echo "<pre>";print_r($ary_request);exit;
        if(!empty($ary_request) && is_array($ary_request)){
            $model = D("RoleNode");
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '启用';
            }else{
                $str_msg = '停用';
                
                $where = array();
                $where['fx_role_node.id']     = $ary_request["id"];
                $where['fx_role.status']     = '1';
                $count = $model
                        ->join(" fx_role_access on fx_role_access.node_id=fx_role_node.id")
                        ->join(" fx_role on fx_role_access.role_id=fx_role.id")
                        ->where($where)->count();
                //echo $model->getLastSql();exit;
                if($count > 0){
                    $this->error("节点已经被使用，不可停用");
                }
            }
            $ary_data[$ary_request['field']]    = $ary_request['val'];
            //保存当前数据对象
            $list = $model->where(array('id'=>$ary_request['id']))->save($ary_data);
            if(false !== $list){
                 $this->success($str_msg."成功");
            }else{
                 $this->error($str_msg."失败");
            }
        }else{
            $this->error("编辑失败");
        }
    }
    
    public function doDelete(){
        $id = intval($this->_get('id'));
        $model = D("RoleNode");
        if(!empty($id) && $id > 0){
            $where = array();
                $where['fx_role_node.id']     = $id;
                $where['fx_role.status']     = '1';
                $count = $model
                        ->join(" fx_role_access on fx_role_access.node_id=fx_role_node.id")
                        ->join(" fx_role on fx_role_access.role_id=fx_role.id")
                        ->where($where)->count();
            if($count > 0){
                IS_AJAX && $this->ajaxReturn(0, "节点已经被使用，不可删除");
                $this->error("节点已经被使用，不可删除");
            }
            //保存当前数据对象
            $list = $model->where(array('id'=>$id))->delete();
            if(false !== $list){
                M("RoleAccess",C('DB_PREFIX'),'DB_CUSTOM')->where(array('node_id'=>$id))->delete();
                IS_AJAX && $this->ajaxReturn(1, "删除成功");
                $this->success("删除成功");
            }else{
                IS_AJAX && $this->ajaxReturn(0, "删除失败");
                $this->success("删除失败");
            }
        }else{
            IS_AJAX && $this->ajaxReturn(0, "数据错误");
            $this->error("数据错误");
        }
    }
}
