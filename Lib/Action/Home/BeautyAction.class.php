<?php
class BeautyAction extends HomeAction{

    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function _initialize() {
        parent::_initialize();
    }
    public function index()
    {
        $videoList = $this->GetVideoList();
        $this->assign("videoList",$videoList);

        $videocategory = $this->GetVideoCategory();
        $this->assign("videocategory",$videocategory);

        $videolesson = $this->GetVideoLesson();
        $this->assign("videolesson",$videolesson);

        $videoteacher = $this->GetVideoTeacher();
        $this->assign("videoteacher",$videoteacher);

        $this->setTitle('美妆课程');
        $tpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/beauty-v2.html';
        $this->assign("v",'-v2');
        $headerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/header-v2.html';
        $footerTpl = FXINC . '/Public/Tpl/' . CI_SN . '/' . TPL . '/footer-v2.html';
        $this->assign("headerTpl",$headerTpl);
        $this->assign("footerTpl",$footerTpl);
        $this->display($tpl);
    }

    public function GetVideoCategory() {
        $M  = M("videos_category");
        $field = "vc_id,vc_name";

        $videocategory = $M->field($field)
        ->select();
        return $videocategory;
    }   

    public function GetVideoLesson() {
        $M  = M("videos_lesson");
        $field = "*";

        $videolesson = $M->field($field)
        ->select();
        return $videolesson;
    }

    public function GetVideoTeacher() {
        $M  = M("videos_teacher");
        $field = "*";

        $videoteacher = $M->field($field)
        ->select();
        foreach ($videoteacher as $key => $value) {
            if($value['t_photo']){
                $videoteacher[$key]['t_photo'] = 'http://www.caizhuangguoji.com/Public/Uploads/teacher/'.$value['t_photo'];
            }
        } 
        return $videoteacher;           
    }

    public function GetVideoList() {
        // $pages = empty($_REQUEST["page"])?"1":$_REQUEST["page"];
        // $limit = empty($_REQUEST["pageSize"])?10:intval($_REQUEST["pageSize"]);
        // $page  = max(1, intval($pages));
        // $startindex=($page-1)*$limit;

        $where = 1;
        if($_REQUEST["v_category_id"]){
            $where.= " and fx_videos_info.v_category_id=".$_REQUEST["v_category_id"];
        }
        if($_REQUEST["v_lesson_id"]){
            $where.= " and fx_videos_info.v_lesson_id=".$_REQUEST["v_lesson_id"];
        }
        if($_REQUEST["v_teacher_id"]){
            $where.= " and fx_videos_info.v_teacher_id=".$_REQUEST["v_teacher_id"];
        }


        $M = M("videos_info");

        $videoList = $M->field('fx_videos_info.v_id,fx_videos_info.v_name,fx_videos_info.v_code,fx_videos_info.v_picture,fx_videos_teacher.t_name,fx_videos_teacher.t_photo')
        ->join('left join fx_videos_teacher on fx_videos_info.v_teacher_id = fx_videos_teacher.t_id')
        ->where($where)
        // ->limit("{$startindex},{$limit}")
        ->select();
        foreach ($videoList as $key => $value) {
            if(!$value['v_picture']){
                $videoList[$key]['v_picture'] = 'http://cdn.dvr.aodianyun.com/pic/long-vod/u/30278/images/'.$value['v_code'].'/145/80';
            }
            if($value['t_photo']){
                $videoList[$key]['t_photo'] = 'http://www.caizhuangguoji.com/Public/Uploads/teacher/'.$value['t_photo'];
            }
            $videoList[$key]['v_url'] = 'http://30278.long-vod.cdn.aodianyun.com/u/30278/m3u8/adaptive/'.$value['v_code'].'.m3u8';  
        }  

        return $videoList;           
    }
}