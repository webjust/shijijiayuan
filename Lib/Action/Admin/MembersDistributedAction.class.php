<?php

/**
 * 后台会员分布图
 * @package Action
 * @subpackage Admin
 * @stage 7.2
 * @author listen 
 * @date 2013-06-04
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class MembersDistributedAction extends AdminAction{
    //put your code here
    /**会员平台分布饼图
     * @author listen   
     * @date 2013-06-06
    */
    public function platformPie(){
        $this->getSubNav(6,0,50);
        $obj_pie = new PieChart();
        
        $ary_platfrom = D('RelatedMembersSourcePlatform')
                        ->join('fx_source_platform on fx_source_platform.sp_id = fx_related_members_source_platform.sp_id')
                        ->where()
                        ->select();
        $labLst =array();
        if(!empty($ary_platfrom)){
            foreach($ary_platfrom as $k=>$v){
                array_push($labLst,$v['sp_name']);
            }
        }
        $ary_temp_datLst = array_count_values($labLst);
        if(!empty($ary_temp_datLst)){
            $int_sum = array_sum($ary_temp_datLst);
            //echo $int_sum;exit;
            foreach($ary_temp_datLst as $k1=>$v1){
               
                //$int_sum += $v1; 
                $ary_labLst[] = $k1; 
                $datLst[] = round(10000*($v1/$int_sum))/100;
            }
        }
        $ary_color  =  $obj_pie->roundColor(count($ary_labLst));
        $image_file = $obj_pie->draw_img($datLst,$ary_labLst,$ary_color);
        $this->assign('image_file',$image_file);
        $this->display();
       
    }   
    
    /**
     * 第三方授权登录平台分步
     * @author Joe <qianyijun@guanyisoft.com>
     */
    public function memberThdPic(){
        $this->getSubNav(6,0,65);
        $obj_pie = new PieChart();
        $member = D('Members')->field('open_source')->where(array('open_source'=>array('neq',' ')))->select();
        $labLst =array();
        foreach ($member as $key=>$val){
            if($val['open_source'] == 'QQ'){
                array_push($labLst,'腾讯授权登录');
            }elseif($val['open_source'] == 'Sina'){
                array_push($labLst,'新浪微博授权登录');
            }elseif($val['open_source'] == 'RenRen'){
            array_push($labLst,'人人网授权登录');
            }
        }
        $ary_temp_datLst = array_count_values($labLst);
        if(!empty($ary_temp_datLst)){
            $int_sum = array_sum($ary_temp_datLst);
            foreach($ary_temp_datLst as $k1=>$v1){
                $ary_labLst[] = $k1; 
                $datLst[] = round(10000*($v1/$int_sum))/100;
            }
        }
        $ary_color  =  $obj_pie->roundColor(count($ary_labLst));
        $image_file = $obj_pie->draw_img($datLst,$ary_labLst,$ary_color);
        $ary_color  =  $obj_pie->roundColor(count($ary_labLst));
        $image_file = $obj_pie->draw_img($datLst,$ary_labLst,$ary_color);
        $this->assign('image_file',$image_file);
        $this->display();exit;
    }
    /*
     * 会员地区分布饼图
     * @author  listen
     * @2013-06-06
     */
    public function membersAreaPie(){
        
         $this->getSubNav(6,0,60);
         $obj_pie = new PieChart(); 
         $ary_members  =  D('Members')->where(array('m_status'=>1,'m_verify'=>2))->select();
         //print_r($ary_members);exit;
         //通过会员中最后一级id 找到上级的id数组
         if(!empty($ary_members)){
             foreach($ary_members as $k=>$v){
                 $ary_address[] = D('CityRegion')->getFullAddressId($v['cr_id']);
             }
         }
         //通过id数组找到地址名字，以省为单位
         if(!empty($ary_address)){
             foreach($ary_address as $k1=>$v1){
                 $ary_temp_addr[] = $v1[1];
                 $ary_addr_name[] = D('CityRegion')->getAddressName($v1[1]);
                 
             }
         }
         $ary_temp_datLst = array_count_values($ary_addr_name);
         if(!empty($ary_temp_datLst)){
            $int_sum = array_sum($ary_temp_datLst);
             foreach($ary_temp_datLst as $k2=>$v2){
                $ary_labLst[] = $k2; //会员分布在哪些省去数组
                $ary_datLst[] = round(10000*($v2/$int_sum))/100;//计算每个省里面占用会员的比例
             }
         }
         //$ary_labLst = array_unique($ary_addr_name);
         //echo "<pre>";print_r($ary_datLst);exit;
         //获取颜色数组，根据省份的个数取几种
         $ary_color  =  $obj_pie->roundColor(count($ary_labLst));
         $image_file = $obj_pie->draw_img($ary_datLst,$ary_labLst,$ary_color);
         $this->assign('image_file',$image_file);
         $this->display();
         
    }
}

?>
