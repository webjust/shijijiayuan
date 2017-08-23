<?php

/**
 * 后台退款退货属性控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.3
 * @author czy <chenzongyao@guanyisoft.com>
 * @date 2013-08-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RefundsPropertyAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - 退换货属性管理');
    }
    
    /**
     * 控制器默认方法，暂时重定向到品牌列表
     * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
     */
    public function index(){
        $this->redirect(U('Admin/RefundsProperty/specListPage'));
    }
	
    /**
     * 属性列表
     * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
     */
    public function specListPage(){
        $this->getSubNav(4,1,30);
		$array_cond = array("gs_status"=>1);
		//如果是从类型列表点击过来，则需要处理类型的搜索
		$int_search_type_id = 0;
		
		//统计符合条件的总属性的数量，并处理分页信息
        $count = D('RefundsSpec')->where($array_cond)->count();
        $page_size = 20;
		$obj_page = new Page($count, $page_size);
		//获取数据并将获取到的数据传递到模板上
        $ary_spec = D('RefundsSpec')->where($array_cond)->limit($obj_page->firstRow,$obj_page->listRows)->order('gs_order')->select();
		//处理数据，获取类型的名称，这里的做法是先获取所有的类型
		//然后遍历属性数组，获取此属性关联的类型ID，
		//最后根据类型ID将类型名称插入数组中，最终渲染到视图层展示给用户。
	//	$array_type_info = D("GoodsType")->where(array("gt_status"=>1))->getField("gt_id,gt_name");
		foreach($ary_spec as $key => $val){
			//获取当前属性关联的类型ID
			$array_type_ids = D("RelatedGoodsTypeSpec")->where(array("gs_id"=>$val["gs_id"]))->getField("gt_id",true);
			$array_type_names = array();
			foreach($array_type_ids as $int_type_id){
				$array_type_names[] = $array_type_info[$int_type_id];
			}
			$ary_spec[$key]["gt_name"] = implode(' | ',$array_type_names);
			$ary_spec[$key]["gt_id"] = $int_type_id;
		}
        $this->assign('ary_spec',$ary_spec);
		$this->assign('page',$obj_page->show());
		//获取所有的类型用于搜索
	//	$this->assign("array_type_info",$array_type_info);
		$this->assign('int_type_id',$int_search_type_id);
        //渲染视图
        $this->display();
    }
	
    
	/**
     * 属性编辑页面
     * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
     */
    public function specEditPage(){
        $this->getSubNav(4,1,30);
        //对属性的参数信息进行判断
        $int_gs_id = $this->_get("gsid",0);
 	    if(!isset($int_gs_id) || !is_numeric($int_gs_id)){
			$this->error('参数错误：非法的属性ID参数传入。');
		}
		//获取属性的详细信息
		$ary_spec = D('RefundsSpec')->where(array('gs_id'=>$int_gs_id))->find();
		if(!is_array($ary_spec) || empty($ary_spec)){
			$this->error("您要编辑的属性不存在！");
		}
        
		//变量传递到模板
		$this->assign('spec',$ary_spec);
		
		//渲染试图
		$this->display();
    }
    
    /**
     * 属性编辑页面
     * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
     */
    public function specAddPage(){
        $this->getSubNav(4,1,40);
        //渲染试图
		$this->display();
    }
    
	/**
     * 编辑属性
     * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
     */
    public function doEditSpec(){
        
        $type = ''; 
		$int_gs_id = $this->_post("gs_id",0);
        if($int_gs_id>0) $type = 'update';
        else $type = 'add';
        $post_spec_info = $this->_post();
        
        if(empty($post_spec_info["gs_show_type"]) || !in_array($post_spec_info["gs_show_type"],array(1,2))) {
            $this->error("请勾选属性所属类型退款或者退货");
        }
        if(empty($post_spec_info["gs_input_type"]) || !in_array($post_spec_info["gs_input_type"],array(1,2,3))) {
            $this->error("请勾选属性值录入方式");
        }
        
        $array_spec_info = array();
        $array_spec_info["gs_name"] = $post_spec_info["gs_name"];
        $array_spec_info["gs_input_type"] = $post_spec_info["gs_input_type"];
        $array_spec_info["gs_show_type"] = $post_spec_info["gs_show_type"];
		
        $array_spec_info["gs_status"] = 1;
        $array_spec_info["gs_order"] = intval($post_spec_info["gs_order"]);
        $array_spec_info["gs_create_time"] = date("Y-m-d H:i:s");
                                        
        $msg = '';                               
        switch($type){
                                case 'add':
                                        D("RefundsSpec")->startTrans();
		                                $int_spec_id = D("RefundsSpec")->add($array_spec_info);
		                                
                                      
                                        if(false === $int_spec_id){
			                                 //事务回滚，属性基本信息插入失败
			                                 D("RefundsSpec")->rollback();
			                                 $this->error("属性基本信息存入失败。");
                                        }
                                        else {
                                            D("RefundsSpec")->commit();
                                        }
                                        $msg = '属性新增成功';
                                        break;
                                case 'update':
                                        if(!isset($int_gs_id) || !is_numeric($int_gs_id)){
			                                 $this->error('参数错误：非法的属性ID参数传入。');
                                        }
		                                //获取属性的详细信息
                                        $ary_spec = D('RefundsSpec')->where(array('gs_id'=>$int_gs_id))->find();
                                        if(!is_array($ary_spec) || empty($ary_spec)){
			                                 $this->error("您要编辑的属性不存在！");
                                        }
                                        else {
                                            
                                            $ary_spec = D('RefundsSpec')->where(array('gs_id'=>$int_gs_id))->data($array_spec_info)->save();
                                        }
                                        $msg = '属性修改成功';
                                        break;
                               
                                default:
                                        break; 
                            }
		$this->success($msg, U('Admin/RefundsProperty/specListPage'));
    }
	
	/**
	 * 删除属性，支持批量和单个删除属性
	 *
	 * @author czy<chenzongyao@guanyisoft.com>   
	 * @date 2013-08-14
	 * @version 7.3
	 */
	public function doDelSpec(){
		//获取要删除的属性ID
		$array_delete_ids = array();
		$post_delete_ids = array();
		$get_delete_ids = array();
		
		//先处理需要批量删除的ID
		if(isset($_POST["gs_id"]) && !empty($_POST["gs_id"])){
			$post_delete_ids = $_POST["gs_id"];
		}
		
		//单个属性的删除
		if($this->_get("gsid") && is_numeric($this->_get("gsid"))){
			$get_delete_ids = array($this->_get("gsid"));
		}
		
		//组装要删除的属性ID
		$array_delete_ids = array_merge($post_delete_ids,$get_delete_ids);
		if(!$array_delete_ids || empty($array_delete_ids)){
			$this->error("抱歉，您需要先选择要删除的属性。");
		}
		
		//对要删除的属性进行验证，验证是否有使用此属性
		//根据属性ID去查询/货品-属性关联表查询是否有记录，如果有记录，则此属性不允许被删除
		$check_used_result = true;
		foreach($array_delete_ids as $spec_id){
			$array_result = D("RelatedRefundSpec")->where(array("gs_id"=>$spec_id))->find();
			if(is_array($array_result) && !empty($array_result)){
				$check_used_result = false;
				break;
			}
		}
        
		if(false === $check_used_result){
			//$array_spec_info = D("RefundsSpec")->where(array("gs_id"=>$array_result["gs_id"]))->find();
			$this->error("属性被退货单占用，不允许删除！");
		}
		
		//删除属性，这里做逻辑删除
		//by Mithern 属性删除修改为物理删除
		if(false === D("RefundsSpec")->where(array("gs_id"=>array("IN",$array_delete_ids)))->delete()){
			$this->error("属性删除失败。");
		}
		$this->success("属性删除成功。",U('Admin/RefundsProperty/specListPage'));
	}
    
    /**
    * 自定义退货/退款理由页面
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @date 2014-1-14 
    * @version 7.4.5
    */
    public function returnReason() {
        $this->getSubNav(4, 1, 50);
        $module = "GY_ORDER_AFTERSALE_CONFIG";
        $key = "RETURN_REASON";
        $desc = "退货/退款理由";
        $ary_return_data = D('SysConfig')->getCfgByModule($module);
        unset($ary_return_data['SETAFTERSALE']);
        $ary_return_data['value'] = '';
        $ary_return_data['content'] = '';
        if(!empty($ary_return_data) && is_array($ary_return_data)){
            $sc_value = explode(',', $ary_return_data['RETURN_REASON']);
            $ary_return_data['value'] = $sc_value[1];
            $ary_return_data['content'] = $sc_value[0];
        }
        $this->assign('data', $ary_return_data);
        $this->display();
    }
    
    /**
    * 保存定义退货/退款理由
    * @author WangHaoYu <wanghaoyu@guanyisoft.com>
    * @date 2014-1-14 
    * @version 7.4.5
    */
    public function doReturnReason() {
        $sysSeting = D('SysConfig');
        $ary_post = $this->_post();
        $module = "GY_ORDER_AFTERSALE_CONFIG";
        $key = "RETURN_REASON";
        $desc = "退货/退款理由";
        if(!empty($ary_post) && is_array($ary_post)){
            $str_return_reason = implode(',',$ary_post);
            $return = $sysSeting->setConfig($module, $key, $str_return_reason, $desc);
            if($return){
                $this->success('保存数据成功！');
            }else{
                $this->error('保存数据失败！');
            }
        }else{
            $this->error('请输入要保存的数据！');
        } 
    }

    //自定义退换货理由 列表
    public function returnReasonList(){
        $this->getSubNav(4, 1, 60);
        $count =M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rr_status'=>1))->count();
        $page_size = 10;
        $obj_page = new Page($count, $page_size);
        $ary_reason =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rr_status'=>1))->limit($obj_page->firstRow,$obj_page->listRows)->select();
        $this->assign('ary_reason',$ary_reason);
        $this->assign('page',$obj_page->show());
        $this->display();
    }

    //自定义退换货理由 添加
    public function returnReasonAdd(){
        $this->getSubNav(4, 1, 60);
        $this->display();
    }

    //自定义退换货理由 添加
    public function returnReasondoAdd(){
        $data = $this->_post();
        if(empty($data)){
            $this->error("请填写数据");
        }
        $data["rr_create_time"] = date("Y-m-d H:i:s");
        $return =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
        if($return){
            $this->success('保存数据成功！',U('Admin/RefundsProperty/returnReasonList'));
        }else{
            $this->error('保存数据失败！',U('Admin/RefundsProperty/returnReasonList'));
        }
    }

    //自定义退换货理由 编辑
    public function returnReasonEdit(){
        $this->getSubNav(4, 1, 60);
        $rr_id = $this->_get('rrid');
        $reason =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rr_status'=>1,'rr_id'=>$rr_id))->find();
        $this->assign("reason",$reason);
        $this->display();
    }
    //自定义退换货理由 编辑
    public function returnReasondoEdit(){
        $this->getSubNav(4, 1, 60);
        $data = $this->_post();
        $return =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array("rr_id"=>$data['rr_id']))->data(array('rr_name'=>$data["rr_name"],'rr_order'=>$data['rr_order'],'rr_is_display'=>$data['rr_is_display'],'rr_show_type'=>$data['rr_show_type']))->save();
        if($return === false){
            $this->error('保存数据失败！',U('Admin/RefundsProperty/returnReasonList'));
        }else{
            $this->success('保存数据成功！',U('Admin/RefundsProperty/returnReasonList'));
        }
    }


    //自定义退换货理由 删除
    public function returnReasonDelete(){
        $rr_id = $this->_get('rrid');
        $reason =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rr_status'=>1,'rr_id'=>$rr_id))->find();
        if(empty($reason)){
            $this->error('要删除的数据不存在！',U('Admin/RefundsProperty/returnReasonList'));
        }
        $reason['rr_status'] = 0 ;
        $return =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->save($reason);
        if($return){
            $this->success('数据删除成功！',U('Admin/RefundsProperty/returnReasonList'));
        }else{
            $this->error('数据删除失败！',U('Admin/RefundsProperty/returnReasonList'));
        }
    }

    public function returnReasonbulkDelete(){
        $rr_id_array = $this->_post();
        D("RefundsReason")->startTrans();
        foreach($rr_id_array['rr_id'] as $key=>$value){
            $reason =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->where(array('rr_status'=>1,'rr_id'=>$value))->find();
            if(empty($reason)){
                D("RefundsReason")->rollback();
                $this->error('要删除的数据不存在！',U('Admin/RefundsProperty/returnReasonList'));
            }else{
                $reason['rr_status'] = 0 ;
                $return =  M('refunds_reason',C('DB_PREFIX'),'DB_CUSTOM')->save($reason);
                if(!$return){
                    D("RefundsReason")->rollback();
                    $this->error('数据删除失败！',U('Admin/RefundsProperty/returnReasonList'));
                }
            }
        }
        D("RefundsReason")->commit();
        $this->success('数据删除成功！',U('Admin/RefundsProperty/returnReasonList'));

    }






}
