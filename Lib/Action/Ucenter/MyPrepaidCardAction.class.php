<?php
/**
 * 我的充值卡Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.5
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2014-03-19
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class MyPrepaidCardAction extends CommonAction{
    public function index() {
        $this->redirect(U('Ucenter/MyPrepaidCard/pageList'));
    }
    
    /**
     * 我的充值卡列表
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2014-03-19
     */
    public function pageList(){
        $this->getSubNav(4, 3, 82);
        $prepaidCard = M('prepaid_card',C('DB_PREFIX'),'DB_CUSTOM');
        $array_get = $this->_get();
        if(isset($array_get['pc_name']) && !empty($array_get['pc_name'])){
            $array_where['pc_name'] = array("LIKE","%" . $array_get['pc_name'] . "%");
        }
        if(isset($array_get['pc_card_number']) && !empty($array_get['pc_card_number'])){
            $array_where['pc_card_number'] = array("LIKE","%" . $array_get['pc_card_number'] . "%");
        }
        if(isset($array_get['pc_processing_status']) && !empty($array_get['pc_processing_status'])){
            if($array_get['pc_processing_status'] == 'success'){
                $array_where['pc_processing_status'] = 1;
            }elseif($array_get['pc_processing_status'] == 'waiting'){
                $array_where['pc_processing_status'] = 0;
            }elseif($array_get['pc_processing_status'] == 'error'){
                $array_where['pc_processing_status'] = 2;
            }
        }
        $array_where['m_id'] = $_SESSION['Members']['m_id'];
        $array_where['is_open'] = 1;
        
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = 7;
        $count =  $prepaidCard->where($array_where)->count();
        $obj_page = new Page($count, $page_size);
        $array_card['page'] = $obj_page->show();
        
        $array_card['list'] = $prepaidCard->where($array_where)->page($page_no,$page_size)->order('pc_use_time desc')->select();
        
        //echo "<pre>";print_r($array_card);exit;
        $this->assign($array_card);
        $this->assign($array_get);
        
        $this->display();
    }
}