<?php

/**
 * 后台商品品牌控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author listen 
 * @date 2013-01-16
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsBrandAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU2_3'));
    }
    
     /**
     * 控制器默认方法，暂时重定向到品牌列表
     * @author listen
     * @date 2013-02-26
     
     */
    public function index(){
        $this->redirect(U('Admin/GoodsBrand/pageList'));
    }
    /**
     * 品牌列表
     * @author listen 
     * @date 2013-02-26
     */
    public function pageList(){
        $this->getSubNav(3,4,10);
        $where = array();
        $where['gb_status']=1;
        $content = trim($this->_post('gb_name'));
    	if($content){
    		$where['gb_name'] = array('LIKE', '%' . $content . '%');
    	}
        $count =  M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->count();
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        $limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $ary_brand =  M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->limit($limit)->order('`gb_order` DESC')->select();
        $this->assign("page", $page);
        $this->assign('ary_brand',$ary_brand);
        $this->display();
    }
    /**
     * 添加品牌页面显示
     * @author listen
     * @date 2013-02-26
     */
    public function addBrand(){
        $this->getSubNav(3,4,20);
        $this->display();
    }
    /**
     * 添加品牌操作
     * @author listen
     * @date 2013-02-26
     */
    public function doAdd(){
        $data = $this->_post();
        //echo "<pre>";print_r($data);exit;
        
        $data['gb_create_time'] = date("Y-m-d h:i:s");
        if(!isset($data['gb_name'])){
             $this->error('用户名不能为空');
        }
        //上传图片
		/**
        if($_FILES['gb_logo']['name']){
            @mkdir('./Public/Uploads/' . CI_SN.'/brand/');
	    	//import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/brand/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$data['gb_logo'] = '/Public/Uploads/'.CI_SN.'/brand/' . $info[0]['savename'];
				$data['gb_logo'] = D('ViewGoods')->ReplaceItemPicReal($data['gb_logo']);				
			}
    	}**/
		$data['gb_logo'] = D('ViewGoods')->ReplaceItemPicReal($data['gb_logo']);
        $res =  M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
        if(!$res){
             $this->error(' 添加失败');
        }else {
             $this->success('品牌添加成功', U('Admin/GoodsBrand/pageList'));
        }
    }
    /**
     * 品牌logo添加
     * @author listen   
     * @date 2013-02-26
     */
    public function addLogo(){
        
    }
    /**
     * 品牌编辑页面显示
     * @author listen 
     * @date 2013-02-26
     */
    public function pageEdit(){
        $this->getSubNav(3,4,20,'品牌编辑');
        $gb_id=$this->_get('gbid');  
        if(isset($gb_id)){
            $where =  array('gb_id'=>$gb_id);
            $ary_brand =  M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->find();
			$ary_brand['gb_logo'] = D('QnPic')->picToQn($ary_brand['gb_logo']);
            //echo  M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
//            echo "<pre>";print_r($ary_brand);exit;
            $this->assign('brand',$ary_brand);
            $this->display();
        }else {
            $this->error('参数错误');
        }
       
    }
    
     /**
     * 删除LOGO
     * @author Wanguigin <wangguibin@guanyisoft.com>
     * @date 2013-07-19
     */   
    public function delLogoPic() {
    	$int_gb_id=$this->_get('gb_id');  
    	$bool_res = M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_id'=>$int_gb_id))
    	->data(array('gb_logo'=>'','gb_update_time'=>date('Y-m-d H:i:s')))
    	->save();
    	if($bool_res){
    		$this->success('删除品牌LOGO成功');
    	}else{
    		$this->error('删除品牌LOGO失败');
    	}
    }
    
    /**
     * 品牌编辑操作
     * @author listen
     * @date 2013-02-26
     */
    public function doEdit(){
        $ary_get_data = $this->_post();
        
        //exit();
            //上传图片
			
        /**if(!empty($_FILES['gb_logo']['name'])){
            @mkdir('./Public/Uploads/' . CI_SN.'/brand/');
	    	import('ORG.Net.UploadFile');
			$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 3145728 ;// 设置附件上传大小
			$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->savePath =  './Public/Uploads/'.CI_SN.'/brand/';// 设置附件上传目录
			if(!$upload->upload()) {// 上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
				$ary_get_data['gb_logo'] = '/Public/Uploads/'.CI_SN.'/brand/' . $info[0]['savename'];
				$ary_get_data['gb_logo'] = D('ViewGoods')->ReplaceItemPicReal($ary_get_data['gb_logo']);				
			}
    	}**/
        $gb_id = $ary_get_data['gb_id'];
        if(isset($gb_id)){
           $ary_get_data['gb_update_time'] = date("Y-m-d H:i:s");
		   $ary_get_data['gb_logo'] = D('ViewGoods')->ReplaceItemPicReal($ary_get_data['gb_logo']);
           //$ary_get_data['gb_banner'] = _ReplaceItemDescPicDomain($ary_get_data['gb_banner']);
           //$data['gb_banner'] ='dsadsa';  
           //$ary_get_data['tt'] ='dsadsa'; 
           //var_dump($ary_get_data);
           //exit();
           $res =   M('GoodsBrand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_id'=>$gb_id))->data($ary_get_data)->save();
           //$res =   M('GoodsBrand',C('DB_PREFIX'),'DB_CUSTOM')->where(array('gb_id'=>$gb_id))->setField($data);

           //echo M('GoodsBrand',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql().'pp'; 
           //var_dump($ary_get_data);
           //exit();
           if(FALSE != $res){
               $this->success('修改成功', U('Admin/GoodsBrand/pageList'));
               
           }else {
               $this->error('修改失败');
           }
            
        }else {
            $this->error('参数错误');
        }
            
    }
    /**
     * 品牌删除
     * @author listen
     * @date 2013-02-26
     */
    public function doDel(){
		if(!isset($_GET["gbid"]) || !is_numeric($_GET["gbid"])){
			$this->error("商品品牌ID参数不合法");
		}
		
		$int_gb_id = $_GET["gbid"];
		
		//验证此商品品牌是否存在
		$array_result = D("GoodsBrand")->where(array("gb_id"=>$_GET["gbid"]))->find();
		if(!is_array($array_result) || empty($array_result)){
			$this->error("您要删除的商品品牌不存在或者已被管理员删除。");
		}
		
		//验证此商品品牌是否被其他商品占用
		$array_check = D("GoodsBase")->where(array("gb_id"=>$int_gb_id))->find();
		if(is_array($array_check) && !empty($array_check)){
			$this->error("您要删除的商品品牌存在商品数据，请先删除商品。");
		}
		
		//删除品牌
		$delete_result = D("GoodsBrand")->where(array("gb_id"=>$_GET["gbid"]))->delete();
		if(false === $delete_result){
			$this->error("商品品牌删除失败。");
		}
		
		//提示和跳转
		$this->success("商品品牌删除成功。",U('Admin/GoodsBrand/pageList'));
    }
    
    /**
     * 品牌批量删除
     * @author czy
     * @date 2013-05-27
     */
    public function doDelBrands(){
       
        $ary_post = $this->_post();
        $brand_obj = D("GoodsBrand");
        if(!isset($ary_post['gb_ids']) || empty($ary_post['gb_ids'])){
			$this->error("请选择您要删除的商品品牌。");
		}
		
		$where = array();
		$where['gb_id'] = array('in',$ary_post['gb_ids']);
		
		//验证商品品牌是否被商品资料占用
		$array_result = D("GoodsBase")->where($where)->find();
		if(is_array($array_result) && !empty($array_result)){
			$this->error("部分商品品牌由于已经被商品占用，所以不允许删除。");
		}
		
		$ary_result = $brand_obj->where($where)->delete();
		if($ary_result){
			$this->success("删除成功",U("Admin/GoodsBrand/pageList"));
			exit;
		}
		
		//商品品牌删除失败。
		$this->error("删除失败，请重试！");
    }
    
    /**
     * 品牌启用/停用
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-06-04
     */
    public function doStatus(){
        $ary_request = $this->_request();
        if(!empty($ary_request) && is_array($ary_request)){
            $action = M("GoodsBrand",C('DB_PREFIX'),'DB_CUSTOM');
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '显示';
            }else{
                $str_msg = '不显示';
            }
            $ary_data[$ary_request['field']]    = $ary_request['val'];
            //保存当前数据对象
//            echo "<pre>";print_r($ary_request);
            $ary_result = $action->where(array('gb_id'=>$ary_request['id']))->save($ary_data);
//            echo M("GoodsBrand",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
            if(FALSE !== $ary_result){
                 $this->success($str_msg."成功");
            }else{
                 $this->error($str_msg."失败");
            }
        }else{
            $this->error("编辑失败");
        }
    }

    public function addGoodsToBrand(){
        //验证是否制定分组ID
        if(!isset($_POST["gb_id"]) || !is_numeric($_POST["gb_id"])){
            echo json_encode(array("status"=>false,"msg"=>"参数错误：非法的商品品牌ID参数传入。"));
            exit;
        }

        //验证是否选择商品
        if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
            echo json_encode(array("status"=>false,"msg"=>"请选择您要加入品牌的商品。"));
            exit;
        }

        //验证分组是否存在
        $array_check_exists = D("GoodsBrand")->where(array("gb_id"=>$_POST["gb_id"]))->find();
        if(empty($array_check_exists) || !is_array($array_check_exists)){
            echo json_encode(array("status"=>false,"msg"=>"商品品牌不存在。"));
            exit;
        }

        $array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));

        foreach($array_goods_ids as $key => $val){
            $array_save_data = array();
            $array_save_data["gb_id"] = $_POST["gb_id"];
            $array_save_data["g_id"] = $val;
            //使用replace into 方式存入数据库
            if(false === D("GoodsBase")->save($array_save_data,array(),true)){
                echo json_encode(array("status"=>false,"msg"=>"将商品加入品牌时遇到错误。"));
                exit;
            }
        }
        echo json_encode(array("status"=>true,"msg"=>"操作成功"));
        exit;
    }

    public function removeGoodsToBrand(){
        //验证是否选择商品
        if(!isset($_POST["g_ids"]) || rtrim(trim($_POST["g_ids"]),',') == ""){
            echo json_encode(array("status"=>false,"msg"=>"请选择您要移出品牌的商品。"));
            exit;
        }
        $array_goods_ids = explode(',',rtrim(trim($_POST["g_ids"]),','));
        foreach($array_goods_ids as $key => $val){
            $array_save_data = array();
//            $array_save_data["gb_id"] = $_POST["gb_id"];
            $array_save_data["g_id"] = $val;
            if(false === D("GoodsBase")->where($array_save_data)->save(array('gb_id'=>0))){
                echo json_encode(array("status"=>false,"msg"=>"将商品从品牌中移除时遇到错误。"));
                exit;
            }
        }
        echo json_encode(array("status"=>true,"msg"=>"操作成功"));
        exit;
    }
}
?>
