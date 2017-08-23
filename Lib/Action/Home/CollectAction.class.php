<?php

/**
 * 收藏基类
 * 用户中心添加收藏，及收藏列表 控制器均需要继承此类
 *
 * @stage 7.1
 * @package Action
 * @subpackage Home
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-20
 * @license saas
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CollectAction extends HomeAction {

    /**
     * 控制器初始化
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 显示用户已收藏的商品
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     * @return type array
     */
    public function pageList() {
        $this->getSubNav(1, 1, 60);
        $member = session("Members");
        $ary_goodinfo=D(CollectGoods)->GetCollectGood($member['m_id']);
        $count = $ary_goodinfo['num'];
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $this->assign('ary_goodinfo', $ary_goodinfo['data']);
        $this->assign('page', $page);    //赋值分页输出
        $this->display();
        
    }
    /**
     * 商品添加收藏夹
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     * @todo 此处应该是ajax加入到我的收藏里面
     */
    public function doAddCollect() {
        $pdt_id = $this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
            $res=D('CollectGoods')->AddCollect($member['m_id'],$pdt_id);
            if($res['status']){
                $this->success(L('COLLECT_SUCCESS'));
            }else{
                $this->success(L('HAVE_COLLECT'));
            }
        }else{
            $url = U('Home/User/pageLogin');
            $this->success(L('NO_LOGIN'));
        }
    }
    
    /**
     * 批量商品添加收藏夹
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     * @todo 此处应该是ajax加入到我的收藏里面
     */
    public function doAddAllCollect() {
        $pdt_id = $this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
            if(empty($pdt_id)){
                $this->success(L('SELECT_COLLECT'));
            }else{
                $ary_res=D(CollectGoods)->GetCollectRecord($member['m_id']);
                if(!empty($ary_res)){
                    $insert_data=array();
                    foreach($pdt_id as $key=>$val){
                        if(array_key_exists($val,$ary_res)){
                            unset($ary_res[$key]);
                        }else{
                            $insert_data[] = array('m_id'=>$member['m_id'],'pdt_id'=>$val,'add_time'=>date('Y-m-d H:i:s'));
                        }
                    }
                    if(empty($insert_data)){
                        $this->success(L('HAVE_COLLECT'));
                    }else{
                        $res=D(CollectGoods)->AddAllCollect($insert_data);
                        $this->success(L('COLLECT_SUCCESS'));
                    }
                }else{
                    foreach($pdt_id as $key=>$val){
                        $insert_data[] = array('m_id'=>$member['m_id'],'pdt_id'=>$val,'add_time'=>date('Y-m-d H:i:s'));
                    }
                    $res=D(CollectGoods)->AddAllCollect($insert_data);
                    $this->success(L('COLLECT_SUCCESS'));
                }
            }
        }else{
            $url = U('Home/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }
    }
    
    /**
     * 删除收藏夹中的商品
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2012-12-25
     * @todo 此处应该是ajax加入到我的收藏里面
     */
     public function doDelCollect() {
        $pdt_id = $this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
           $res=D(CollectGoods)->DelCollect($pdt_id,$member['m_id']);
            if($res){
                $this->success(L('DELETE_SUCCESS'),array(
                                L('OK')=>U('Home/Collect/pageList')
                            ));
            }else{
                $this->success(L('DELETE__FAIL'));
            } 
        }else{
            $url = U('Home/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }
     }
     
     /**
     * 收藏夹中的商品加入购物车
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2012-12-25
     * @todo 此处应该是ajax加入到我的收藏里面
     */
     public function doCart() {
        $pdt_id = (int)$this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
            $ary_db_carts=D('Cart')->ReadMycart();
            $insert_data=array();
            if(is_array($ary_db_carts) && !empty($ary_db_carts)){
                foreach($ary_db_carts as $key=>$value){
                    if ($value['num'] <= 0 || !is_int($value['num'])) {
                        unset($ary_db_carts[$key]);
                    }
                    if (array_key_exists($pdt_id, $ary_db_carts)) {
                         $insert_data[$pdt_id]['pdt_id']=$pdt_id;
                         $insert_data[$pdt_id]['num']=$value['num']+1;
                         unset($ary_db_carts[$pdt_id]);
                    }else{
                        $insert_data[$key]=array('pdt_id'=>$key,'num'=>$value['num']);
                    }
                }
                $insert_data[$pdt_id]=array('pdt_id'=>$pdt_id,'num'=>1);
            }else{
                $insert_data[$pdt_id]=array('pdt_id'=>$pdt_id,'num'=>1);
            }
            $Cart = D('Cart')->WriteMycart($insert_data);
            if($Cart){
                $this->success(L('ADD_CART_SUCCESS'),array(
                                L('OK')=>U('Home/Cart/pageList')
                            ));
            }else{
                $this->success(L('ADD_CART_FAIL'));
            }
        }else{
            $url = U('Home/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }
     }
}