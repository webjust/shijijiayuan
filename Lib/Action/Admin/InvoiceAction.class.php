<?php

/**
 * 后台发票设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
 * @date 2013-04-23
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class InvoiceAction extends AdminAction {

    /**
     * 控制器初始化
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function _initialize() {
        parent::_initialize();
		$this->log = new ILog('db');
        $this->setTitle(' - '.L('MENU6_2'));
    }

    /**
     * 默认控制器
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function index() {
        $this->redirect(U('Admin/Stock/pageSet'));
    }

    /**
     * 后台发票设置页面
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-04-23
     */
    public function pageSet() {
        $this->getSubNav(4, 6, 10);
        $ret=D('Invoice')->get();
        
        $invoice_type = explode(",",$ret['invoice_type']);
        $invoice_head = explode(",",$ret['invoice_head']);
        $invoice_content = explode(",",$ret['invoice_content']);
        $invoice_comom =$invoice_type[0];
        $invoice_special =$invoice_type[1];
        $invoice_personal =$invoice_head[0];
        $invoice_unit =$invoice_head[1];
        $this->assign('is_invoice',$ret['is_invoice']);
        $this->assign('is_auto_verify',$ret['is_auto_verify']);
        $this->assign('invoice_comom',$invoice_comom);
        $this->assign('invoice_special',$invoice_special);
        $this->assign('invoice_personal',$invoice_personal);
        $this->assign('invoice_unit',$invoice_unit);
        $this->assign('invoice_content',$invoice_content);
        $this->display();
    }

    /**
     * 修改发票设置
     * @author Zhangjiasuo <Zhangjiasuo@guanyisoft.com>
     * @date 2013-01-16
     */
    public function doSet(){
        $data = $this->_post();
        if(isset($data['invoice_comom'])){
            $invoice_comom = $data['invoice_comom'];
        }else{
            $invoice_comom=0;
        }
        
        if(isset($data['invoice_special'])){
            $invoice_special = $data['invoice_special'];
        }else{
            $invoice_special=0;
        }
        
        
        
        $data['invoice_type'] =$invoice_comom.','.$invoice_special;
        
        if(isset($data['invoice_personal'])){
            $invoice_personal = $data['invoice_personal'];
        }else{
            $invoice_personal=0;
        }
        
        if(isset($data['invoice_unit'])){
            $invoice_unit = $data['invoice_unit'];
        }else{
            $invoice_unit=0;
        }
        $data['invoice_head'] =$invoice_personal.','.$invoice_unit;

        if(!empty($data['content'])){
            if(!empty($content)){
                $content .=','.implode(",",$data['content']);
            }else{
                $content .=implode(",",$data['content']);
            }
            $data['invoice_content'] =$content;
            unset($data['content']);
        }

        D('Invoice')->update($data);
		$this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"发票设置",serialize($data)));
        $this->success('保存成功');
    }
}
