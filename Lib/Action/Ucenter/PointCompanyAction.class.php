<?php

/**
 * 前台积分商城Action
 *
 * @package Action
 * @subpackage Ucenter
 * @stage 7.1
 * @author czy
 * @date 2013-4-17
 * @license MIT
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class PointCompanyAction extends CommonAction {
      
    /**
     * 订单控制器默认页
     * @author czy  <chenzongyao@guanyisoft.com>
     * @date 2012-12-13
     */
    public function index() {
        $this->getSubNav(4, 4, 90);
        $this->display();
        $this->redirect(U('Ucenter/PointCompany/pageList/'));
    }

    /**
     * @param 积分商城页面
     * @author czy  <chenzongyao@guanyisoft.com>
     * @version 7.1
     * @since stage 1.5
     * @modify 2013-4-17
     * @return mixed array
     */
    
    public function pageList(){
        $this->getSubNav(4, 4, 90);
        $ary_post_data = $this->_post();
        $where = array('m_id'=>$_SESSION['Members']['m_id']);
        if(isset($ary_post_data['c_start_time']) && $ary_post_data['c_start_time'] !=''){
            $where['u_create'] = array('EGT',$ary_post_data['c_start_time']);
        }
        
        if(isset($ary_post_data['c_end_time']) && $ary_post_data['c_end_time'] !=''){
            $where['u_create'] = array('ELT',$ary_post_data['c_end_time']);
        }
        
        $page_no = max(1,(int)$this->_get('p','',1));
        $page_size = 20;
        $count =  D('PointLog')->where($where)->count();
        $obj_page = new Page($count, $page_size);
        $page = $obj_page->show();

        $ary_pointlog = D('PointLog')->where($where)->page($page_no,$page_size)->select();
        $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        //print_r($ary_point);exit;
        $valid_point = 0;//有用积分数
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
            
        $this->assign('valid_point',$valid_point);//积分总和
        $this->assign('int_nstart',$count);//总页数
        $this->assign('page',$page);
        $this->assign('ary_pointlog',$ary_pointlog);
        $this->display();
    }
    
    /**
     * @param 积分购买判断是否用户登录
     * @author czy  <chenzongyao@guanyisoft.com>
     * @version 7.1
     * @since stage 1.5
     * @modify 2013-4-24
     * @return json array
     */
    public function isLogin(){
        if (!session('?Members')) {
            //未登录用户引导到登录页面
            $this->error(L('NO_LOGIN'), U('Ucenter/User/pageLogin'));
        } else {
            //已登录用户将session放入到私有属性
            $this->success('登录成功');
        }
    }
    
    /**
     * @param 积分商城ajax弹出购物车页面
     * @author czy  <chenzongyao@guanyisoft.com>
     * @version 7.1
     * @since stage 1.5
     * @modify 2013-4-19
     * @return mixed array
     */
    public function goodInfo(){
        $g_id =  $this->_post("g_id");
        $products = M('goods_products',C('DB_PREFIX'),'DB_CUSTOM');D("GoodsProducts");
        $ary_product_feild = array('pdt_id','pdt_stock','pdt_sn','g_sn');
        $where = array('g_id'=>$g_id,'pdt_status'=>1);
        
        $ary_pdt = $products->field($ary_product_feild)->where($where)->select();
        
        $goodsSpec = D('GoodsSpec');
        $skus = array();
        $guigeis = array();
        $bool_is_sku = true;
        if(is_array($ary_pdt) && !empty($ary_pdt)){
        	  //如果无规格 
              if(count($ary_pdt) == 1 &&($ary_pdt[0]['pdt_sn'] == $ary_pdt[0]['g_sn'])){
              	$bool_is_sku = false;
              }
                foreach($ary_pdt as $keypdt=>$valpdt){
                    //获取其他属性
                    $specInfo = $goodsSpec->getProductsSpecs($valpdt['pdt_id']);
                    if(!empty($specInfo['color'])){
                        if(!empty($specInfo['color'][1])){
                        	//判断是否存在
                        	$is_exist = 0;
                        	foreach($skus[$specInfo['color'][0]] as $sku){
                        		if($specInfo['color'][1] == $sku['info']){
                        			$is_exist = 1;
                        		}	
                        	}
                        	if($is_exist != 1){
                        		$skus[$specInfo['color'][0]][] = array(
                        				'info'=>$specInfo['color'][1],
                        				'gs_id'=>$specInfo['color'][2],
                        				'gsd_id'=>$specInfo['color'][3],
                        		);
                        	}

                        }
                    }
                    if(!empty($specInfo['size'])){
                        if(!empty($specInfo['size'][1])){
                        	//判断是否存在
                        	$is_exist = 0;
                        	foreach($skus[$specInfo['size'][0]] as $sku){
                        		if($specInfo['size'][1] == $sku['info']){
                        			$is_exist = 1;
                        		}
                        	}
                        	if($is_exist != 1){
                        		$skus[$specInfo['size'][0]][] = array(
                        				'info'=>$specInfo['size'][1],
                        				'gs_id'=>$specInfo['size'][2],
                        				'gsd_id'=>$specInfo['size'][3],
                        		);
                        	}

                        }
                    }
                    if(isset($specInfo['guigei'][$valpdt['pdt_id']]) && !empty($specInfo['guigei'][$valpdt['pdt_id']])) {
                        $guigeis[$valpdt['pdt_id']] = $specInfo['guigei'][$valpdt['pdt_id']];
                    }
                    $ary_pdt[$keypdt]['specName']  = $specInfo['spec_name'];
                    $ary_pdt[$keypdt]['skuName']  = $specInfo['sku_name'];
                }
               
            $ary_picresult = D("GoodsPictures")->where(array('g_id'=>$ary_goods['g_id']))->select();
            if(!empty($ary_picresult) && is_array($ary_picresult)){
                $ary_pic = $ary_picresult;
            }
          
          }
          else $ary_pdt = array();
          
       // print_r(array_flip($guigeis));exit;
        $point = 0;
        $member = session("Members");
       
         if(!empty($member['m_id'])){
            $ary_point = M("members",C('DB_PREFIX'),'DB_CUSTOM')->field('total_point,freeze_point')->where(array('m_id'=>$member['m_id']))->find();
            $point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
         }
         $this->assign("is_sku", $bool_is_sku);
         $this->assign("g_id", $g_id);
         $this->assign("ary_pdt", $ary_pdt);
         $this->assign("guigei",array_flip($guigeis)); 
         $this->assign("point", $point); 
         $this->assign("skus", $skus);
         $this->display();
    }
   
    
}
?>