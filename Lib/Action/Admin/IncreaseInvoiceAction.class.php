<?php

/**
 * 后台增值税发票控制器
 * @package Action
 * @subpackage Admin
 * @stage 7.3
 * @author czy 
 * @date 2013-08-12
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class IncreaseInvoiceAction extends AdminAction{
	
     public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.'增值税发票管理');
    }
    
     /**
     * 控制器默认方法，暂时重定向到增值税列表
     * @author czy
     * @date 2013-08-12
     
     */
    public function index(){
        $this->redirect(U('Admin/IncreaseInvoice/pageList'));
    }
    /**
     * 增值税列表
     * @author czy <chenzongyao@guanyisoft.com> 
     * @date 2013-08-12
     */
    public function pageList(){
        $this->getSubNav(4,6,20);
        $where = array();
        //$where['gb_status']=1;
        $content = trim($this->_post('invoice_name'));
    	if($content){
    		$where['invoice_name'] = array('LIKE', '%' . $content . '%');
    	}
        $where['invoice_type'] = 2;
        //print_r($where);exit;
        $count =  M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->count();
        //echo M('goods_brand',C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
        $page_no = max(0, (int) $this->_get('p', '', 0));
        $page_size = 20;
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();
        //echo $count;exit;
        $ary_invoice =  M('invoice_collect',C('DB_PREFIX'),'DB_CUSTOM')->where($where)->limit($page_no,$page_size)->order('`id` DESC')->select();
        foreach($ary_invoice as $key=>$val){
            $ary_invoice[$key]['invoice_phone'] = decrypt($val['invoice_phone']);
            $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/A";
            if(preg_match($RegExp,$ary_invoice[$key]['invoice_phone'])){
                $ary_invoice[$key]['invoice_phone'] = vagueMobile($ary_invoice[$key]['invoice_phone']);
            }
        }
        $this->assign("page", $page);
        $this->assign('ary_invoice',$ary_invoice);
        $this->display();
    }
    
    
    
    
    /**
     * 增值税发票审核
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-08-12
     */
    public function doVerify(){
        $ary_request = $this->_request();
      
        if(!empty($ary_request) && is_array($ary_request)){
            $action = M("invoice_collect",C('DB_PREFIX'),'DB_CUSTOM');
            $ary_data = array();
            $str_msg = '';
            if(intval($ary_request['val']) > 0 ){
                $str_msg = '审核';
            }else{
                $str_msg = '不审核';
            }
            $ary_data[$ary_request['field']]    = $ary_request['val'];
            //保存当前数据对象
//            echo "<pre>";print_r($ary_request);
            $ary_result = $action->where(array('id'=>$ary_request['id']))->save($ary_data);
           // echo M("invoice_collect",C('DB_PREFIX'),'DB_CUSTOM')->getLastSql();exit;
            if(FALSE !== $ary_result){
                 $this->success($str_msg."成功");
            }else{
                 $this->error($str_msg."失败");
            }
        }else{
            $this->error("审核失败");
        }
    }
    
    /**
     * 增值税发票详情
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-08-12
     */
    public function detailInvoiceInfo() {
         $g_id = intval($this->_post('g_id'));
        
        if (isset($g_id) && !empty($g_id)) {
            $OrderRefundInfo = M("invoice_collect", C('DB_PREFIX'), 'DB_CUSTOM');
            $ary_data = $OrderRefundInfo
            ->where(array("id" => $g_id))->find();
        }
        $ary_data['invoice_phone'] = strpos($ary_data['invoice_phone'],':') ? decrypt($ary_data['invoice_phone']) : $ary_data['invoice_phone'];
        $RegExp  = "/^((13[0-9])|147|(15[0-35-9])|177|180|182|(18[5-9]))[0-9]{8}$/A";
        $ary_data['invoice_phone'] = preg_match($RegExp,$ary_data['invoice_phone']) ? vagueMobile($ary_data['invoice_phone']) : $ary_data['invoice_phone'];
       
        $this->assign('ary_data', $ary_data);
       
        $this->display('invoiceDetail');
    }
    
    
}

?>
