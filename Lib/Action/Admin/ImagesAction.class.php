<?php

/**
 * 后台图片处理控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-03-14
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class ImagesAction extends AdminAction {

    /**
     * 初始化控制器
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-14
     */
    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - ' . L('MENU7_1'));
    }

    /**
     * 默认控制器，重定向到图片列表页
     * @auhtor zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-14
     */
    public function index() {
        $this->redirect('Admin/Images/pageList');
    }

    /**
     * 图片资料列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-14
     */
    public function pageList() {
        $this->getSubNav(8, 1, 10);
        $dir = $this->_get('dir', 'htmlspecialchars', '');
        $str_dir = str_replace('@@', '/', APP_PATH . 'Public/Uploads/' . CI_SN . $dir);
        $ary_dir = scandir($str_dir);
        $res_dir = array();
        foreach ($ary_dir as $k => $v) {
            if ($v[0] == '.') {
                //去除隐藏文件夹
                unset($ary_dir[$k]);
            } else {
                //判断该路径是文件夹还是文件
                if (is_dir($str_dir . '/' . $v)) {
                    $res_dir[$k] = array(
                        'name' => $v,
                        'prop' => 'dir',
                        'path' => str_replace('/', '@@', $dir . '/' . $v)
                    );
                    //判断是几级类目以及是否允许删除
                    $ary_dirs = explode('@@',$res_dir[$k]['path']);
                    $res_dir[$k]['path_size']  = count($ary_dirs)-1;
                    //此处需要做额外判断，只有以other目录开头的文件才可以被删除
                    if($ary_dirs[1] == 'other'){
                        $res_dir[$k]['del'] = true;
                    }else{
                    	$res_dir[$k]['del']  = false;
                    }
                } else {
                    $res_dir[$k] = array(
                        'name' => $v,
                        'prop' => 'file',
                        'path' => str_replace('/', '@@', '/Public/Uploads/' . CI_SN . $dir . '/' . $v),
                    	'path_size' => 0
                    );
                    //此处需要做额外判断，只有以other目录开头的文件才可以被删除
                    $dirs = explode('@@', $dir);
                    if($dirs[1] == 'other'){
                        $res_dir[$k]['del'] = true;
                    }
                }
            }
        }
        $this->assign('list', $res_dir);
        $this->display();
    }
	
	public function pageQnList() {
        $this->getSubNav(8, 1, 15);
		$ary_data = $this->_request();
		$ary_query = array();
		if($ary_data['marker']){
			$ary_query['marker'] = $ary_data['marker'];
		}
		if(!empty($ary_data['dir'])){
			$ary_query['prefix'] = '/Public/Uploads/'.CI_SN.'/'.$ary_data['dir'];
		}
		$qiniu_obj = new Qiniu();
		$res_dir = $qiniu_obj->getList($ary_query);
		if(!empty($res_dir['marker'])){
			$ary_data['next_marker'] = $res_dir['marker'];
		}
		$lists = $res_dir['items'];
		if(!empty($_SESSION['OSS']['GY_QN_DOMAIN'])){
			foreach($lists as &$dir){
				$res = $qiniu_obj->getInfo($dir['key']);
				$dir['pic_url'] = $qiniu_obj->getSignUrl($dir['key']);
				$dir['del'] = true;		
			}
		}
		$this->assign('ary_data', $ary_data);
        $this->assign('list', $lists);
        $this->display();
    }
    /**
     * 返回上级目录的列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-14
     */
    public function doUp() {
        $dir = $this->_get('dir', 'htmlspecialchars', '');
        $ary_dir = explode('@@', $dir);
        array_pop($ary_dir);
        $str_dir = implode('@@', $ary_dir);
        $this->redirect('Admin/Images/pageList', array('dir' => $str_dir));
    }

    /**
     * 删除图片
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-27
     */
    public function doDel(){
        $ary_del = $this->_post('imagesDel');
        //去除重复
        $ary_del = array_unique($ary_del);
        if(!empty($ary_del) && is_array($ary_del)){
            $FileUtil = new FileUtil();
            $bln_result = true;
            if(is_array($ary_del)){
                foreach ($ary_del as $v) {
                    $del_path =  "Public/Uploads/" . CI_SN . str_replace('@@', '/', $v);
                    if(is_dir($del_path)){
                        if(FALSE == $FileUtil->unlinkDir($del_path)){
                            $bln_result = false;
                        }
                    }else{
                        $del_path = str_replace('@@', '/', $v);
                        $del_file = substr(strrchr($del_path,"/"),1);
                        $str_file = substr(strrchr($del_path,"."),1);
                        $preg_array = array(
                            'png','gif','bmp','jpg'
                        );
                        $del_path = FXINC.$del_path;
                        //$path = APP_PATH . "Public/Uploads/" . CI_SN . "/" . $del_file;
                        if(file_exists($del_path)){
                            if(in_array($str_file, $preg_array)){
                                if(FALSE == $FileUtil->unlinkFile($del_path)){
                                    $bln_result = false;
                                }
                            }else{
                                $bln_result = false;
                            }
                        }else{
                            $bln_result = false;
                        }
                    }
                }
            }
            if($bln_result){
                $this->success('删除成功');
            }else{
                $this->error('删除操作执行完成，可能有部分文件没有被删除!');
            }
        }else{
            $this->error('请选择需要删除的内容');
        }
    }
	
	/**
     * 删除七牛图片
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-27
     */
    public function doDelQn(){
        $ary_del = $this->_post('imagesDel');
        //去除重复
        $ary_del = array_unique($ary_del);
        if(!empty($ary_del) && is_array($ary_del)){
			$bln_result = true;
			//先删除本地数据库
			foreach($ary_del as $del_url){
				M('qn_pic',C('DB_PREFIX'),'DB_CUSTOM')->where(array('pic_url'=>$del_url))->delete();
			}
			//删除七牛服务器图片信息
			$qiniu_obj = new Qiniu();
			$qiniu_res = $qiniu_obj->delBatch($ary_del);
			foreach($qiniu_res as $res){
				if($res['code'] != '200'){
					$bln_result = false;
				}
			}
            if($bln_result){
                $this->success('删除成功');
            }else{
                $this->error('删除操作执行完成，可能有部分文件没有被删除!');
            }
        }else{
            $this->error('请选择需要删除的内容');
        }
    }
	
    /**
     * 站点水印设置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-22
     */
    public function pageSet() {
        $this->getSubNav(8, 1, 30);
        $data['info'] = D('SysConfig')->getCfgByModule('GY_IMAGEWATER');
        $this->assign($data);
        $this->display();
    }

    /**
     * 保存图片水印设置
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-03-22
     */
    public function doSet() {
        $data = $this->_post();
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_ON', $data['water_on']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_POS', $data['water_pos']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_ALPHA', $data['water_alpha']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_TYPE', $data['water_type']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_TEXT', $data['water_text']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_SIZE', $data['water_size']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'WATER_IMAGE', $data['water_image']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'MAGNIFIER_ON', $data['magnifier_on']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'MAGNIFIER_PIC_WIDTH', $data['magnifier_pic_width']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'MAGNIFIER_PIC_HEIGHT', $data['magnifier_pic_height']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'THUMB_PIC_WIDTH', $data['thumb_pic_width']);
        D('SysConfig')->setConfig('GY_IMAGEWATER', 'THUMB_PIC_HEIGHT', $data['thumb_pic_height']);
        $this->success('保存成功！');
    }
	
	/**
	 * 商品图片设置
	 *
	 * @author Mithern<sunguangxu@guanyisoft.com>
	 * @version 1.0
	 * @date 2013-07-27
	 */
	public function itemImageConfig(){
		if(isset($_POST["dosubmit"]) && !empty($_POST["ITEM_IMAGE_CONFIG"])){
			//保存商品图片设置信息
			//事务开始
			D("SysConfig")->startTrans();
			foreach($_POST["ITEM_IMAGE_CONFIG"] as $key => $val){
				$array_data = array("sc_module"=>'ITEM_IMAGE_CONFIG',"sc_key"=>$key,'sc_value'=>$val);
				if(false === D("SysConfig")->add($array_data,array(),true)){
					D("SysConfig")->rollback();
					$this->error("配置项保存失败，请稍候重试。");
					exit;
				}
			}
			D("SysConfig")->commit();
			$this->success("配置项保存成功。");
			exit;
		}
		$this->getSubNav(2, 3, 25);
		$array_item_image_config = D("SysConfig")->itemImageConfigInfoGet();
		$this->assign("config_info",$array_item_image_config);
		$this->display("item_image_config");
	}

    #### 以下为私有方法 ########################################################
}