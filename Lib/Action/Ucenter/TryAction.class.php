<?php
/**
 * 试用相关Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 1.0
 * @author Tom <helong@guanyisoft.com>
 * @date 2014-10-11
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
 class TryAction extends CommonAction {

    private $tryModel;
    /**
     * 试用控制器初始化
     * @author Tom <helong@guanyisoft.com>
     * @date 2012-12-20
     */
    public function _initialize() {
        parent::_initialize();
        $this->tryModel = D('Try');
    }

    /**
     * 首先运行
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-09-18
     */
    protected function _autoload(){
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
    }

    /**
     * 试用报告
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-11
     */
    public function pageAdd(){
        $this->_autoload();
        $this->getSubNav(1, 0, 40);
        $int_oid = $this->_get('oid');
        $applyModel = D('try_apply_records');
        if(empty($int_oid)){
            $this->error('请选择订单!');
        }
        $ary_where = array(C("DB_PREFIX").'try_apply_records.try_oid' => $int_oid);
        $try_info = $this->tryModel->getApplyTryByCondi($ary_where);
        $ary_spec_info = $this->tryModel->getQuestionById(array('gt_id'=>$try_info['property_typeid']));  // 获取试用报告题型
        if($this->checkTryReport(array('try_id'=>$try_info['try_id']))){
            $this->error('您已提交报告!',U('Ucenter/Apply/pageList'));
        }

        $this->assign('ary_apply',$try_info);
        $this->assign('ary_question',$ary_spec_info);
        $this->display();
    }

    /**
     * 添加试用报告
     * @author Tom <helong@guanyisoft.com>
     * @date 2014-10-13
     */
    public function doReport(){
        $this->_autoload();
        $ary_request   = $this->_request();
        $ary_member    = session('Members');
        $questionModel = D("try_report_attribute");
        $reportModel   = D("try_report");
        if(empty($ary_request['try_oid'])){
            $this->error('不存在该订单!',U('Ucenter/Apply/pageList'));
        }
        // 获取试用申请信息
        $applyModel = D('try_apply_records');
        $ary_where = array(C("DB_PREFIX").'try_apply_records.try_oid' => $ary_request['try_oid']);
        $try_apply_info = $this->tryModel->getApplyTryByCondi($ary_where);
        if(empty($try_apply_info)){
            $this->error('不存在该试用订单!',U('Ucenter/Apply/pageList'));
        }
        if($this->checkTryReport(array('try_id'=>$try_apply_info['try_id']))){
            $this->error('您已提交报告!',U('Ucenter/Apply/pageList'));
        }
        // 报告数据
        $ary_report = array(
            'try_id'          => $try_apply_info['try_id'],
            'property_typeid' => $try_apply_info['property_typeid'],
            'tr_create_time'  => date('Y-m-d H:i:s'),
            'm_id'            => $ary_member['m_id']
            );
        M()->startTrans();
        try{
            $tagReport = $reportModel->add($ary_report);
            // 问题属性存入数据库，如果有的话。
            $tagQuestion = true;
            if(isset($_POST["question_spec"]) && !empty($_POST["question_spec"])){
                foreach($_POST["question_spec"] as $gs_id=>$spec_value){
                    if("" != $spec_value){
                        $array_tmp_spec_info = D("GoodsSpec")->where(array("gs_id"=>$gs_id))->find();
                        $string_spec_value = $spec_value;
                        if(2 == $array_tmp_spec_info["gs_input_type"]){
                            if($spec_value <= 0){
                                //如果是select类型的扩展属性，且值小于0，则说明未设置此属性的值
                                continue;
                            }
                            $array_tmp_spec_detail = D("GoodsSpecDetail")->where(array("gsd_id"=>$spec_value))->find();
                            $string_spec_value = $array_tmp_spec_detail["gsd_value"];
                        }
                        $question_spec = array();
                        $question_spec["try_apply_id"]    = $try_apply_info['tar_id'];  // 试用申请ID
                        $question_spec["property_typeid"] = $try_apply_info['property_typeid']; // 试用类型ID
                        $question_spec["attr_id"]         = $gs_id; // 属性ID
                        $question_spec["attr_value"]      = $string_spec_value; // 属性值
                        $question_spec["attr_name"]       = $array_tmp_spec_info['gs_name'];    // 属性名称
                        $result = $questionModel->add($question_spec);
                        if(false === $result){
                            $tagQuestion = false;
                            break;
                        }
                    }
                }
            }
            if($tagReport && $tagQuestion){
                M()->commit();
                $this->success('填写报告成功', U('Ucenter/Apply/pageList'));
            }else{
                M()->rollback();
                $this->error('填写报告失败');
            }
        }catch(Exception $e){
            M()->rollback();
            $this->error('填写报告失败!');
        }
    }

    /**
     * 检查是否已经填写报告
     * @author Tom <helong@guanyisoft.com>
     * @param (array)$ary
     * @return (boolen) true:存在记录;false:不存在记录
     * @date 2014-10-13
     */
    public function checkTryReport($ary = array()){
        $ary_member = session('Members');
        $ary_where = array(
            'try_id' => $ary['try_id'],
            'm_id'   => $ary_member['m_id']
            );
        $try_report = D('try_report')->where($ary_where)->find();
        if(empty($try_report)){
            return false;
        }else{
            return true;
        }
    }
 }