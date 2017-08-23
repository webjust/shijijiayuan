<?php

/**
 * 后台管理员用户组控制器
 *
 * @subpackage Role
 * @package Action
 * @stage 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-22
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RoleAction extends AdminAction {

    /**
     * 控制器初始化
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle(' - ' . L('MENU7_4_0'));
    }

    /**
     * 默认控制器
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function index() {
        $this->redirect(U('Admin/Role/pageList'));
    }

    /**
     * 角色列表
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function pageList() {
        $this->getSubNav(8, 4, 10);
        $role = M("role",C('DB_PREFIX'),'DB_CUSTOM');
        $count = $role->where()->count();
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $ary_data = $role->where()->limit($obj_page->firstRow, $obj_page->listRows)->select();
        $this->assign("data", $ary_data);
        $this->assign("page", $page);
        $this->display();
    }

    /**
     * 添加角色
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function pageAdd() {
        $this->getSubNav(8, 4, 20);
        //取出模块授权
        $modules = D("RoleNode")->where("status = 1 and auth_type = 1")->select();
        if (!empty($modules) && is_array($modules)) {
            foreach ($modules as $key => $val) {
                $actions = D("RoleNode")->where("status=1 and auth_type = 0 and module='" . $val['module'] . "'")->select();
                if (!empty($actions) && is_array($actions)) {
                    $modules[$key]['actions'] = $actions;
                }
            }
        }
        $this->assign('access_list', $modules);
        $this->display();
    }

    /**
     * 判断用户组名称是否存在
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-23
     * @return string 存在在返回字符串 否则返回FALSE
     */
    public function checkRoleName(){
        $ary_get = $this->_get();
        $role = M("role",C('DB_PREFIX'),'DB_CUSTOM');
        $ary_data = $role->where(array('name'=>$ary_get['name']))->find();
        if(!empty($ary_data) && is_array($ary_data)){
            $this->ajaxReturn("该用户组名称已经存在");
        }else{
            $this->ajaxReturn(true);
        }
    }


    /**
     * 处理添加角色
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function doAdd() {
        $model = D("Role");
        $roleAccess = M("RoleAccess",C('DB_PREFIX'),'DB_CUSTOM');
        $data = $model->create();
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
        $model->startTrans();
        //保存当前数据对象
        $list = $model->add($data);
        if (false !== $list) {
            $node_ids = $this->_request("access_node");
            if (!empty($node_ids) && is_array($node_ids)) {
                foreach ($node_ids as $node_id) {
                    $access['role_id'] = $list;
                    $access['node_id'] = $node_id;
                    $roleAccess->add($access);
                }
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"添加角色",'添加角色：'.$data['name']));
                $model->commit();//成功
                $this->success("数据添加成功");
            } else {
                $model->rollback();//不成功，回滚
                $this->error("请选择控制权限");
            }
        } else {
            $model->rollback();//不成功，回滚
            $this->error("数据添加失败");
        }
    }

    /**
     * 编辑角色
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function pageEdit() {
        $this->getSubNav(8, 4, 10, "编辑角色");
        $role = D("Role");
        $ary_get = $this->_get();
        $vo = $role->getById($ary_get['id']);
        $this->assign("vo", $vo);
        $roleAccess = M("RoleAccess",C('DB_PREFIX'),'DB_CUSTOM');
        $role_access = $roleAccess->where("role_id=" . $ary_get['id'])->field("node_id")->select();
        
        $node_ids = array();
        if (!empty($role_access) && is_array($role_access)) {
            foreach ($role_access as $access) {
                array_push($node_ids, $access['node_id']);
            }
        }
        //echo "<pre>";print_r($node_ids);exit;
        //取出模块授权
        $modules = D("RoleNode")->where("status = 1 and auth_type = 1")->select();
        //echo "<pre>";print_r($modules);exit;
        if (!empty($modules) && is_array($modules)) {
            foreach ($modules as $k => $v) {
                $actions = D("RoleNode")->where("status=1 and auth_type = 0 and module='" . $v['module'] . "'")->select();
                if ($actions) {
                    $modules[$k]['actions'] = $actions;
                }
            }
            //echo "<pre>";print_r($modules);
            foreach ($modules as $mk => $module) {
                if (in_array($module['id'], $node_ids)) {
                    $modules[$mk]['checked'] = true;
                } else {
                    $modules[$mk]['checked'] = false;
                }
                foreach ($module['actions'] as $ak => $action) {
                    $checkall = true;

                    if (in_array($action['id'], $node_ids)) {
                        $modules[$mk]['actions'][$ak]['checked'] = true;
                    } else {
                        $checkall = false;
                        $modules[$mk]['actions'][$ak]['checked'] = false;
                    }
                    //echo "<pre>";var_dump($modules[$mk]['actions'][$ak]['checked']);
                }

                if ($checkall) {
                    $modules[$mk]['checkall'] = true;
                } else {
                    $modules[$mk]['checkall'] = false;
                }
            }
        }
        require_once('./Conf/Admin/authoritys.php');
        foreach($modules as &$module){
        	foreach($module['actions'] as &$action){
        		foreach($authoritys['no'] as $key=>$val){
					foreach($val as $subkey=>$subval){
						if(($action['module'] == $key) && ($action['action'] == $subkey)){
	        				$action['checked'] = true;
	        				$action['readonly'] = true;
	        			}	
					}
        		}
        	}
        }
        //echo "<pre>";print_r($modules);exit;
        $this->assign('access_list', $modules);
        $this->display();
    }
    
    /**
     * 校验角色编辑时，角色名称是否存在
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-23
     */
    public function checkEditName(){
        $name = $this->getActionName();
        $ary_get = $this->_get();
        $where = array();
        $where['id'] = array("NEQ",intval($ary_get['id']));
        $where['name'] = $ary_get['name'];
        $count = D($name)->where($where)->count();
        if(intval($count) > 0){
            $this->ajaxReturn('该管理员已存在！');
        }else{
            $this->ajaxReturn(true);
        }
    }


    /**
     * 处理编辑角色
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function doEdit(){
	    @set_time_limit(0);  
        @ignore_user_abort(TRUE); 
        $ary_request = $this->_request();
        $model = D("Role");
        $roleAccess = M("RoleAccess",C('DB_PREFIX'),'DB_CUSTOM');
        $data = $model->create();
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
//        echo "<pre>";print_r($ary_request);exit;
        $where = array();
        $where['id'] = array("NEQ",$ary_request['id']);
        $where['name']     = $ary_request["name"];
        $count = $model->where($where)->count();
        if($count > 0){
            $this->error("编辑的角色已经存在");
        }
        //保存当前数据对象
        $list = $model->where(array('id'=>$ary_request['id']))->save($data);
        if(false !== $list){
            $roleAccess->where(array('role_id'=>$ary_request['id']))->delete();
            $node_ids = $ary_request['access_node'];
            if (!empty($node_ids) && is_array($node_ids)) {
                foreach ($node_ids as $node_id) {
                    $access['role_id'] = $ary_request['id'];
                    $access['node_id'] = $node_id;
                    $roleAccess->add($access);
                }
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"编辑角色成功",'编辑角色成功：'.$data['name']));
                $this->success("编辑成功");
            } else {
                $this->error("请选择控制权限");
            }
        }else{
            $this->error("编辑失败");
        }
    }
    
    /**
     * 角色启用/停用
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-22
     */
    public function doEditStatus(){
        $ary_request = $this->_request();
        //echo "<pre>";print_r($ary_request);exit;
        if(!empty($ary_request) && is_array($ary_request)){
            $model = D("Role");
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '启用';
            }else{
                $str_msg = '停用';
                
                $where = array();
                $where['fx_admin.role_id']     = $ary_request["id"];
                $where['fx_admin.u_status']     = '1';
                $count = $model->join(" fx_admin on fx_admin.role_id=fx_role.id")->where($where)->count();
                if($count > 0){
                    $this->error("角色已经被使用，不可停用");
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
    /**
    * @date 2013-12-7 
    * @version 7.4.5
    * @modify by wanghaoyu
    * @添加批量删除
    */
    public function doDelete(){
        $id = intval($this->_get('id'));
        $ary_role_id = $this->_post('role_id');
        $model = D("Role");
        if(!empty($id) && $id > 0){
            $where = array();
            $where['fx_admin.role_id']     = $id;
            $where['fx_admin.u_status']     = '1';
            $count = $model->join(" fx_admin on fx_admin.role_id=fx_role.id")->where($where)->count();
            if($count > 0){
                IS_AJAX && $this->ajaxReturn(0, "角色已经被使用，不可删除");
                $this->error("角色已经被使用，不可删除");
            }
            //保存当前数据对象
            $list = $model->where(array('id'=>$id))->delete();
            if(false !== $list){
                M("RoleAccess",C('DB_PREFIX'),'DB_CUSTOM')->where(array('role_id'=>$id))->delete();
                IS_AJAX && $this->ajaxReturn(1, "删除成功");
                $this->success("删除成功");
            }else{
                IS_AJAX && $this->ajaxReturn(0, "删除失败");
                $this->success("删除失败");
            }
        }else if(!empty($ary_role_id) && is_array($ary_role_id)){
            $ary_where = array();
            $ary_where['fx_admin.role_id'] = array('IN',$ary_role_id);
            $ary_where['fx_admin.u_status'] = 1;
            $ary_role['id'] = array('IN',$ary_role_id);
            $count = $model->join('fx_admin on fx_admin.role_id = fx_role.id')->where($ary_where)->count();
            if(0 < $count){
                $this->error('角色正在被使用, 不可删除!');
            }
            $return = $model->where($ary_role)->delete();
            //删除关联表里角色下的所有节点
            if(FALSE !== $return){
                M('role_access',C('DB_PREFIX'),'DB_CUSTOM')->where($ary_role)->delete();
				$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除角色成功！",'删除角色成功：'.$ary_role_id));
                $this->success('删除角色成功！');
            }else{
                $this->error('删除角色失败！');
            }
        }else{
            IS_AJAX && $this->ajaxReturn(0, "数据错误");
            $this->error("数据错误");
        }
    }
}