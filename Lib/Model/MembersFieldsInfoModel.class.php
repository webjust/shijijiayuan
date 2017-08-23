<?php
/**
 * 会员属性项设置模型
 * @package Model
 * @version 7.3
 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
 * @date 2013-08-05
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersFieldsInfoModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-05
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('members_fields_info',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 获得会员属性项列表
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-05
     */
     
    public function getList($condition,$ary_field = ''){
        return $result=$this->field($ary_field)->where($condition)->select();
    }
    
    /**
     *自定义用户属性项信息插入数据库
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @param int $m_id  //用户id
     * @param array $params //自定义用户属性项
     * @date 2013-08-06
     */
     
    public function doAdd($params,$mid,$verify){
        $this->startTrans();
        $where=array('is_display'=>1);
        $ary_extend_data = D('MembersFields')->getList($where);
        $del_extend_info = $this->getList(array('u_id'=>$mid));
        if(!empty($del_extend_info) && $verify==2){
            $int_del = $this->where(array('u_id'=>$mid))->delete();
            if(!$int_del){
                $this->rollback();
                $message=array('msg'=>'自定义用户属性项信息修改失败','result' => false);
                return $message;
            }
        }
		//获取会员表字段
        $tmp_members_fields = M()->query("desc `fx_members`");
		$ary_members_fields = array();
		foreach($tmp_members_fields as $tmp_val){
			$ary_members_fields[$tmp_val['Field']] = 1;
		}
        if(!empty($ary_extend_data)){
            $extend_field_string = '';
            foreach ($ary_extend_data as $val){
                if($val['fields_type']=='file'){
                    $file_extend_index = 'extend_field_' . $val['id'];
                    $tmp_files=$_FILES[$file_extend_index];
                    //print_r($tmp_files);
                    if(isset($tmp_files) && !empty($tmp_files['name']) && !empty($tmp_files['tmp_name'])){
						$path = './Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/';
						if(!file_exists($path)){
							@mkdir('./Public/Uploads/' . CI_SN.'/home/'.date('Ymd').'/', 0777, true);
						}
                        $upfiles[$file_extend_index] = $tmp_files;
						$upload = new UploadFile();// 实例化上传类
						$upload->maxSize  = 3145728 ;// 设置附件上传大小
						$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','bmp');// 设置附件上传类型GIF，JPG，JPEG，PNG，BM
						//$upload->savePath =  $path;// 设置附件上传目录
						if(!$upload->upload($path,$upfiles)) {// 上传错误提示错误信息
                            $this->rollback();
                            $message=array('msg'=>$upload->getErrorMsg(),'result' => false);
                            return $message;
						}else{// 上传成功 获取上传文件信息
							$info =  $upload->getUploadFileInfo();
							$tmp_files_url = '/Public/Uploads/'.CI_SN.'/home/' .date('Ymd').'/'. $info[0]['savename'];
							$files_url = D('ViewGoods')->ReplaceItemPicReal($tmp_files_url);
							if($verify==2){
                                $extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $files_url . "','1'),";
                            }else{
                                $extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $files_url . "','0'),";
                            }
                            unset($upfiles[$file_extend_index]);
						}
                    }else{
                        if(isset($params[$file_extend_index])){
                            if($verify==2){
                                $extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $params[$file_extend_index] . "','1'),";
                            }else{
                                $extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $params[$file_extend_index] . "','0'),";
                            }
                        }
                    }
                }else{
					if(!empty($params[$val['fields_content']]) && empty($ary_members_fields[$val['fields_content']]) && $val['fields_content'] !='m_password_1'){
						$content = $params[$val['fields_content']];	
					}else{
						$extend_index = 'extend_field_' . $val['id'];
						$content = $params[$extend_index];						
					}
					if(!empty($content)){
						if(is_array($content)){
							$content=implode(",",$content);
						}
						if($verify==2){
							$extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $content . "','1'),";
						}else{
							$extend_field_string .= " ('" . $mid . "', '" . $val['id'] . "', '" . $content . "','0'),";
						}
					}					
                }
	        }
            if(!empty($extend_field_string)){
                $extend_field_string = substr($extend_field_string, 0, -1);
    	        $insert_sql = 'INSERT INTO fx_members_fields_info'. ' (`u_id`, `field_id`, `content`, `status`) VALUES' . $extend_field_string;
    	        $int_extend_res = $this->execute($insert_sql);
    	        if(!$int_extend_res){
    	            $this->rollback();
    	            $message=array('msg'=>'自定义用户属性项信息修改失败','result' => false);
    	            return $message;
    	        } 
            }      	
        }
        $this->commit();
        $message=array('msg'=>'自定义用户属性项信息修改成功','result' => true);
        return $message;
    }
}
