<?php

/**
 * 收藏基类
 * 用户中心添加收藏，及收藏列表 控制器均需要继承此类
 *
 * @stage 7.1
 * @package Action
 * @subpackage Ucenter
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-20
 * @license saas
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class CollectAction extends WapAction {

    /**
     * 控制器初始化
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     */
    public function _initialize() {
        parent::_initialize();
    }
    
    /**
     * 更改加入收藏夹
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function index(){
        $this->redirect(U('Wap/Collect/pageList'));
    }
    
    /**
     * 加入收藏夹列表
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function pageList(){

        $member = D('WapInfo')->memberInfo();
		if(empty($member['m_id'])){
			$this->redirect(U('/Wap/User/login'). '?redirect_uri=' . urlencode( ltrim($_SERVER['REQUEST_URI'],'/')));
		}
        $module = M('CollectGoods',C('DB_PREFIX'),'DB_CUSTOM');
        $where = array();			
        $where[C("DB_PREFIX").'collect_goods.m_id'] = $member['m_id'];
		$where[C("DB_PREFIX").'goods.g_id'] = array('neq','');
		$count = $module
                ->field(" ".C("DB_PREFIX")."collect_goods.m_id,".C("DB_PREFIX")."goods_info.*,".C("DB_PREFIX")."goods.*")
                ->join(" ".C("DB_PREFIX")."collect_goods ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."collect_goods.g_id")
                ->join(" ".C("DB_PREFIX")."goods ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."goods.g_id")
                ->where($where)->count();
        $obj_page = new Page($count, 20);
        $string_limit = $obj_page->firstRow . ',' . $obj_page->listRows;
        $page = $obj_page->show();
        $ary_goods = $module
                ->field(" ".C("DB_PREFIX")."collect_goods.m_id,".C("DB_PREFIX")."collect_goods.add_time,".C("DB_PREFIX")."goods_info.*,".C("DB_PREFIX")."goods.*")
                ->join(" ".C("DB_PREFIX")."goods_info ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."collect_goods.g_id")
                ->join(" ".C("DB_PREFIX")."goods ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."goods.g_id")
                ->where($where)->select();
        if(!empty($ary_goods) && is_array($ary_goods)){
            foreach($ary_goods as $key=>&$val){
                $val['nums'] = $module->where(array("g_id"=>$val['g_id']))->count();
                $val['g_picture'] = '/'.ltrim($val['g_picture'],'/');
            }
        }
        $this->assign('page', $page);    //赋值分页输出
        $this->assign("ary_goodinfo",$ary_goods);
        $this->assign("member",$member);
        //$tpl = $this->wap_theme_path . 'ucenter/CollectpageList.html';
		$tpl = '';
		if(file_exists($this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' )){
            $tpl = $this->wap_theme_path.'Ucenter/Tpl/'.MODULE_NAME.'/'.ACTION_NAME.'.html' ;
        }
        $this->display($tpl);
    }
    
    /**
     * 显示用户已收藏的商品
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     * @return type array
     */
    /*public function pageList() {
        $member = session("Members");
        $ary_goodinfo=D(CollectGoods)->GetCollectGood($member['m_id']);
        $count = $ary_goodinfo['num'];
        $obj_page = new Page($count, 10);
        $page = $obj_page->show();
        $this->assign('ary_goodinfo', $ary_goodinfo['data']);
        $this->assign('page', $page);    //赋值分页输出
        $this->display();
        
    }*/
    /**
     * 商品添加收藏夹
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-04-20
     * @todo 此处应该是ajax加入到我的收藏里面
     */
    public function doAddCollect() {
        $pdt_id = $this->_param("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
            $res=D('CollectGoods')->AddCollect($member['m_id'],$pdt_id);
            if($res['status']){
                $this->success(L('COLLECT_SUCCESS'));
            }else{
                $this->success(L('HAVE_COLLECT'));
            }
        }else{
            $url = U('Wap/User/pageLogin');
            $this->success(L('NO_LOGIN'),$url);
        }
    }
    
    /**
     * 无规格山坪商品添加收藏夹
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-07-10
     * @todo 此处应该是ajax加入到我的收藏里面
     */
    public function doCollectInfo() {
        $g_id = $this->_param("gid");
        $pdt = D('GoodsProducts')->field("pdt_id")->where(array('g_id'=>$g_id,'pdt_status'=>'1'))->find();
        $pdt_id = $pdt['pdt_id'];
        $member = session("Members");
        if(!empty($member['m_id'])){
            $res=D('CollectGoods')->AddCollect($member['m_id'],$pdt_id);
            if($res['status']){
                $this->success(L('COLLECT_SUCCESS'));
            }else{
                $this->success(L('HAVE_COLLECT'));
            }
        }else{
            $url = U('Wap/User/pageLogin');
            $this->success(L('NO_LOGIN'),$url);
        }
    }
    
    /**
     * 将商品加入收藏夹
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-08-01
     */
    public function doAddGoodsCollect(){
        $ary_post = $this->_post();
        if(!empty($ary_post['gid']) && isset($ary_post['gid'])){
            $member = session("Members");
            if(!empty($member['m_id'])){
                $ary_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array("g_id"=>$ary_post['gid']))->find();
                if(!empty($ary_goods) && is_array($ary_goods)){
                    $arr_collect = M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->where(array("m_id"=>$member['m_id'],"g_id"=>$ary_goods['g_id']))->find();
                    if(!empty($arr_collect) && is_array($arr_collect)){
                        $this->ajaxReturn(array('status'=>'0','info'=>"该商品已加入收藏"));
                    }else{
                        $arr_res = M('collect_goods',C('DB_PREFIX'),'DB_CUSTOM')->add(array("m_id"=>$member['m_id'],"g_id"=>$ary_goods['g_id']));
                        if(false !== $arr_res){
                            $this->ajaxReturn(array('status'=>'1','info'=>"加入收藏成功"));
                        }else{
                            $this->ajaxReturn(array('status'=>'0','info'=>"加入失败"));
                        }
                    }
                    
                    
                }else{
                    $this->ajaxReturn(array('status'=>'0','info'=>"该商品不存在或者已经下架"));
                }
            }else{
                $this->ajaxReturn(array('status'=>'0','info'=>L('NO_LOGIN')));
            }
            
        }else{
            $this->ajaxReturn(array('status'=>'0','info'=>"请选择商品"));
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
        $cart_data = D('Cart')->ReadMycart();
		foreach($pdt_id as $key=>$int_pdt_id){
			//如果是自由组合商品
			if($cart_data[$int_pdt_id]['type'] == 4){
				unset($pdt_id[$key]);
				foreach($cart_data[$int_pdt_id]['pdt_id'] as $sub_pdt_id){
					$pdt_id[] = $sub_pdt_id;
				}
			}
		}
		$pdt_id = array_unique($pdt_id);
        $member = session("Members");
        if(!empty($member['m_id'])){
            if(empty($pdt_id)){
                $this->success(L('SELECT_COLLECT'));
            }else{
                $ary_res=D(CollectGoods)->GetCollectRecord($member['m_id']);
//                echo "<pre>";print_r($ary_res);exit;
                if(!empty($ary_res)){
                    $insert_data=array();
                    foreach($pdt_id as $key=>$val){
                        if(!empty($val)){
                            $g_id = D('GoodsProducts')->where(array('pdt_id'=>$val))->getField('g_id');
                            if(array_key_exists($g_id,$ary_res)){
                                unset($ary_res[$key]);
                            }else{
                                
                                $insert_data[] = array('m_id'=>$member['m_id'],'g_id'=>$g_id,'add_time'=>date('Y-m-d H:i:s'));
                            }
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
                        if(!empty($val)){
                            $g_id = D('GoodsProducts')->where(array('pdt_id'=>$val))->getField('g_id');
                            $insert_data[] = array('m_id'=>$member['m_id'],'g_id'=>$g_id,'add_time'=>date('Y-m-d H:i:s'));
                        }
                        
                    }
                    $res=D(CollectGoods)->AddAllCollect($insert_data);
                    $this->success(L('COLLECT_SUCCESS'));
                }
            }
        }else{
            $url = U('Ucenter/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }
    }
    
    /**
     * 删除收藏夹中的商品
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2012-12-25
     * @todo 此处应该是ajax加入到我的收藏里面
     * @modify 2013-08-01 Terry<wanghui@guanyisoft.com>
     */
     public function doDelCollect() {
        $module = M('CollectGoods',C('DB_PREFIX'),'DB_CUSTOM');
        $gid = $this->_post('gid');
        $member = session("Members");
        if(!empty($member['m_id'])){
            $ary_res = $module->where(array("m_id"=>$member['m_id'],"g_id"=>$gid))->delete();
            if(false !== $ary_res){
                $this->ajaxReturn(array("status"=>'1',"info"=>L('DELETE_SUCCESS')));
            }else{
                $this->ajaxReturn(array("status"=>'0',"info"=>L('DELETE__FAIL')));
            }
        }else{
            $this->ajaxReturn(array("status"=>'0',"info"=>L('NO_LOGIN')));
        }
        /*$pdt_id = $this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
           $res=D(CollectGoods)->DelCollect($pdt_id,$member['m_id']);
            if($res){
                $this->success(L('DELETE_SUCCESS'),array(
                                L('OK')=>U('Ucenter/Collect/pageList')
                            ));
            }else{
                $this->success(L('DELETE__FAIL'));
            } 
        }else{
            $url = U('Ucenter/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }*/
     }
     
     /**
     * 收藏夹中的商品加入购物车
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2012-12-25
     * @todo 此处应该是ajax加入到我的收藏里面
     */
     public function doCart() {
        $good_type = 0;
        $pdt_id = (int)$this->_get("pid");
        $member = session("Members");
        if(!empty($member['m_id'])){
            $where=array('fx_goods_products.pdt_id' => $pdt_id);
            $field =array('fx_goods.g_is_combination_goods','fx_goods.g_gifts');
            $ary_data = D('GoodsProducts')->GetProductList($where,$field);
            if($ary_data[0]['g_gifts']){
                $this->error('赠品不能购买！');
                return false;
            }
            $ary_db_carts=D('Cart')->ReadMycart();
            if(is_array($ary_db_carts) && !empty($ary_db_carts)){
                foreach($ary_db_carts as $key=>$value){
                    if ($value['num'] <= 0 || !is_int($value['num'])) {
                        unset($ary_db_carts[$key]);
                    }
                }
                if (array_key_exists($pdt_id, $ary_db_carts)) {
                    $ary_db_carts[$pdt_id]['num'] +=1;
                }else{
                    $ary_db_carts[$pdt_id]=array('pdt_id'=>$pdt_id,'num'=>1,'type'=>$good_type);
                }
            }else{
                $ary_db_carts[$pdt_id]=array('pdt_id'=>$pdt_id,'num'=>1,'type'=>$good_type);
            }
            $Cart = D('Cart')->WriteMycart($ary_db_carts);
            if($Cart){
                $this->success(L('ADD_CART_SUCCESS'),array(
                                L('OK')=>U('Ucenter/Cart/pageList')
                            ));
            }else{
                $this->success(L('ADD_CART_FAIL'));
            }
        }else{
            $url = U('Ucenter/User/pageLogin');
            $this->success('NO_LOGIN',$url);
        }
     }
}