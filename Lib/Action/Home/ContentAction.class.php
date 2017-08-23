<?php
/**
 * 投诉建议Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.2
 * @author Terry
 * @date 2013-07-01
 * @license MIT
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class ContentAction extends HomeAction{

    /**
     * 初始化操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-04-01
     */
    public function _initialize() {
        parent::_initialize();

    }

    /**
     * 投诉建议
     * @author zuo <wanghui@guanyisoft.com>
     * @date 2012-12-24
     */
    public function index() {
        $this->redirect(U('Home/Content/Complaints'));
    }

    /**
     * 投诉建议
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-30
     */
    public function Complaints(){
        $ary_request = $this->_request();
        $this->setTitle('投诉建议');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/Complaints.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/Complaints.html';
        }
        $this->display($tpl);
    }

    /**
     * 投诉建议
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-07-01
     */
    public function doComplaints(){
        $ary_request = $this->_request();
        $members = session("Members");
        if(empty($members['m_id'])){
            $this->error("您尚未登录，请先登录");
        }
        if(!empty($ary_request['content']) && isset($ary_request['content'])){
            $data = array(
            	's_create_time'=>date('Y-m-d H:i:s'),
                'm_id'  => $members['m_id'],
                's_content'   => htmlspecialchars($ary_request['content'])
            );
            $ary_result = M('Suggestions',C('DB_PREFIX'),'DB_CUSTOM')->add($data);
            if(FALSE != $ary_result){
                $this->success("操作成功");
            }else{
                $this->error("投诉/建议失败");
            }
        }else{
            $this->error("投诉/建议内容不能为空");
        }
    }

    /**
     * 风湿免疫专题
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-011
     */
    public function rheumatism(){
        $ary_request = $this->_request();
        $this->setTitle('风湿免疫专题');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/rheumatism.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/rheumatism.html';
        }
        $this->assign("ary_request",$ary_request);
        $this->display($tpl);
    }

    /**
     * 肝胆用药专题
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-011
     */
    public function gb(){
        $ary_request = $this->_request();
        $this->setTitle('肝胆用药');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/gb.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/gb.html';
        }
        $this->assign("ary_request",$ary_request);
        $this->display($tpl);
    }

    /**
     * 通络生骨专区
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-011
     */
    public function tlsg(){
        $ary_request = $this->_request();
        $this->setTitle('通络生骨专区');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/tlsg.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/tlsg.html';
        }
        $this->assign("ary_request",$ary_request);
        $this->display($tpl);
    }

    /**
     * 怕长胖搜伊宁曼
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-011
     */
    public function special2(){
        $ary_request = $this->_request();
        $this->setTitle('怕长胖搜伊宁曼');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/special2.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/special2.html';
        }
        $this->assign("ary_request",$ary_request);
        $this->display($tpl);
    }

    /**
     * 康己屋
     * @author wangguibin<wangguibin@guanyisoft.com>
     * @date 2013-07-011
     */
    public function special(){
        $ary_request = $this->_request();
        $this->setTitle('康己屋');
        if(!empty($ary_request['view']) && $ary_request['view'] == 'preview'){
            $tpl = './Public/Tpl/'.CI_SN.'/preview_'.$ary_request['dir'].'/special.html';
        }else{
            $tpl = './Public/Tpl/'.CI_SN.'/'.TPL.'/special.html';
        }
        $this->assign("ary_request",$ary_request);
        $this->display($tpl);
    }

}
