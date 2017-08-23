<?php
/**
 * 会员属性项设置模型
 * @package Model
 * @version 7.3
 * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
 * @date 2013-08-02
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersFieldsModel extends GyfxModel {
    
    /**
     * 构造方法
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-02
     */

    public function __construct() {
        parent::__construct();
        $this->table = M('members_fields',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 获得会员属性项列表
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-08-02
     */
     
    public function getList($condition,$ary_field = '',$limit){
        return $result=$this->field($ary_field)->where($condition)->order(array("dis_order"=>"ASC"))->limit($limit['start'],$limit['end'])->select();
    }
    
    /**
     * 自定义用户属性项显示
     * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @param int $m_id  //用户id
     * @date 2013-08-06
     */
     
    public function displayFields($mid,$source){
        $m_count=D('MembersVerify')->where(array('m_id'=>$mid))->count();
        if($m_count>0){
            $sys_data=D('MembersVerify')->where(array('m_id'=>$mid))->find();  
        }else{
            $sys_data=D('Members')->where(array('m_id'=>$mid))->find();  
        }
//        echo'<pre>';print_r($sys_data);die;
        $where=array('is_display'=>1,'is_status'=>1);
        if(!empty($source)){
            $where['is_register']=1;
        }
        $ary_extend_data = $this->getList($where);
        $ary_extend_info=D('MembersFieldsInfo')->getList(array('u_id'=>$mid,'status'=>0),array('field_id,content'));
        if(empty($ary_extend_info)){
            $ary_extend_info=D('MembersFieldsInfo')->getList(array('u_id'=>$mid,'status'=>1),array('field_id,content'));
        }
        foreach ($ary_extend_info as $val){
            if(!empty($val['content'])){
                $val['content']=explode(",",$val['content']);
                foreach ($val['content'] as $value){
                    $temp_ary[$val['field_id']][$value]=$value;
                }
            }
        }
		//获取会员表字段
        $tmp_members_fields = M()->query("desc `fx_members`");
		$ary_members_fields = array();
		foreach($tmp_members_fields as $tmp_val){
			$ary_members_fields[$tmp_val['Field']] = 1;
		}
        foreach ($ary_extend_data as $key => $value){
            if($value['type'] && !empty($ary_members_fields[$value['fields_content']])){

			   //推荐人
			   if(!empty($source) && ($value['fields_content']=='m_recommended')){
					if($value['fields_content']=='m_recommended'){
					   $ary_extend_data[$key]['content']=$sys_data['m_name'];
					}
			   }
			   elseif($value['fields_content']=='m_mobile' && $sys_data['m_mobile']){
				   if(decrypt2($sys_data['m_mobile']) !=''){
					   $ary_extend_data[$key]['content']= vagueMobile(decrypt($sys_data['m_mobile']));
				   }
			   }
			   elseif($value['fields_content']=='m_telphone' && $sys_data['m_telphone']){
				   $ary_extend_data[$key]['content']= decrypt($sys_data['m_telphone']);
			   }
			   else{
					$ary_extend_data[$key]['content']=$sys_data[$value['fields_content']]; 
			   }
            }else{
			    if($value['fields_content']=='m_password_1'){
                   $ary_extend_data[$key]['content']=$sys_data['m_password']; 
                }
				elseif($value['fields_content']=='m_mobile' && $sys_data['m_mobile']){
					if(decrypt2($sys_data['m_mobile']) !=''){
						$ary_extend_data[$key]['content']= vagueMobile(decrypt($sys_data['m_mobile']));
					}
			    }
				elseif($value['fields_content']=='m_telphone' && $sys_data['m_telphone']){
				   $ary_extend_data[$key]['content']= decrypt($sys_data['m_telphone']);
			    }
			    else{
					if(!empty($value['fields_content'])){
						if(strpos($value['fields_content'],',') !== false){
							$ary_extend_data[$key]['fields_content'] = explode(",",$value['fields_content']);
						}else{
							if($value['fields_content']!='tz_point'){
								$tmp_ary=array();
								array_push($tmp_ary,$value['fields_content']);
								$ary_extend_data[$key]['fields_content'] = $tmp_ary;
							}
						}
					}
					if($value['fields_type']=='text'){ 
						if(!empty($temp_ary[$value['id']])){
							$ary_extend_data[$key]['content']=array_pop($temp_ary[$value['id']]);
						}
					}elseif($value['fields_type']=='file'){
						$ary_extend_data[$key]['content']=$temp_ary[$value['id']] ? array_pop($temp_ary[$value['id']]) : $sys_data['m_head_img'];
						$ary_extend_data[$key]['content'] = D('QnPic')->picToQn($ary_extend_data[$key]['content']);
					}else{
						if(!empty($temp_ary[$value['id']])){
							$ary_extend_data[$key]['content']=$temp_ary[$value['id']];
						}
					}
				}
            }
        }
        return $ary_extend_data;
    }

}
