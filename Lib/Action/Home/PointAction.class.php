<?php

/**
 * 积分商城Action
 *
 * @package Action
 * @subpackage Ucenter
 * @version 7.1
 * @author czy
 * @date 2013-04-18
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class PointAction extends HomeAction {

    
    
    /**
     * 积分商城页
     * @params 商品ID:gid
     * @author czy  <chenzongyao@guanyisoft.com>
     * @date 2013-04-18
     */
    public function index() {
        $is_on = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_OPEN');
        if ($is_on['GY_SHOP_OPEN']['sc_value'] == '0') {
            if($_SESSION['Members']){
                header("location:" . U('Ucenter/Index/index'));exit;
            }
            //modify by Mithern 2013-07-05
            //如果网站没启用，则直接引导到会员中心
            header("location:" . U('Home/User/Login'));
            exit;
        }
        //print_r(M('orders', C('DB_PREFIX'), 'DB_CUSTOM')->field('o_freeze_point')->where(array('o_id'=>'201301071514495346'))->find());
        //显示页面
        $ary_request = $this->_request();
		if(!empty($ary_request['cid'])){
			$gc_parent_id =  M('goods_category', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('gc_status' => 1, 'gc_id' => $ary_request['cid']))->getField('gc_parent_id');
			if(!empty($gc_parent_id)){
				$ary_request['gpc_id'] = $gc_parent_id;
			}
		}
        //keyword商品名称
        $this->setTitle('积分商城页');
        $this->assign('ary_request',$ary_request);
        if (!empty($ary_request['view']) && $ary_request['view'] == 'preview') {
        	$tpl = './Public/Tpl/' . CI_SN . '/preview_' . $ary_request['dir'] . '/point.html';
        } else {
        	$tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/point.html';
        }
		$m_id = $_SESSION['Members']['m_id'];
		//获取会员积分信息
		$dataObj = D('Members')->where(array('m_id' => $m_id))->find();
		//获取积分排行信息
		$where['fx_goods_info.is_exchange'] = 1;
		$where['g.g_on_sale'] = 1;
		$where['g.g_hand_sale_status'] = array('neq' , 2);
		$pointObj = M('goods_info', C('DB_PREFIX'), 'DB_CUSTOM')->field('fx_goods_info.g_id as g_id,g_name,g_price,g_market_price,g_picture,g_salenum')
		->join('fx_goods as g ON g.g_id=fx_goods_info.g_id')
		->where($where)
		->order('g_salenum asc,fx_goods_info.g_update_time asc')
		->select();
		$this->assign('pointObj',$pointObj);		
        $this->display($tpl);
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
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . 'pointSelectGoods.html';
        $this->display($tpl);
    }
    
}