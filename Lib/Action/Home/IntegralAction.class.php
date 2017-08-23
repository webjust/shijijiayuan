<?php
/**
 * 新积分+ 金额兑换商城 前台Action
 */

Class IntegralAction extends HomeAction{

    /**
     * 控制器初始化
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-21
     */
    public function _initialize() {
        parent::_initialize();
    }

    public function  index(){
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
        $get = $this->_get();
        $mod = $this->getActionName();
        $integral = D($mod);

        $array_where = array('integral_status'=>1,
            // 'sp_start_time'=>array('lt',date('Y-m-d H:i:s')),
            'integral_end_time'=>array('gt',date('Y-m-d H:i:s')));
        if($get['startPrice']>=0 && isset($get['startPrice'])){
            if(!empty($get['startPrice']) && $get['startPrice'] >= '5000'){
                $array_where['sp_price'] = array("EGT",$get['startPrice']);
            }else{
                $array_where['sp_price'] = array("between",array($get['startPrice'],$get['endPrice']));
            }
        }
        if(!empty($get['scid'])){
            $array_where['gc_id'] = $get['scid'];
        }
        $count = $integral->where($array_where)->count();
        $obj_page = new Pager($count, 12);
        $page = $obj_page->show();
        $pageInfo = $obj_page->showArr();
        $intergralList = $integral->where($array_where)
            ->limit($obj_page->firstRow . ',' . $obj_page->listRows)
            ->select();

        foreach ($intergralList as $ky=>$kv){
            //七牛图片显示
            $intergralList[$ky]['integral_picture'] = D('QnPic')->picToQn($kv['integral_picture']);
            $goods_info = D('Goods')->where(array('g_id'=>$kv['g_id']))->count();
            if($goods_info == 0){
                unset($intergralList[$ky]);
            }
        }

        //获取积分兑换商品规格属性
        $goodsSpec = D('GoodsSpec');
        $products = M('goods_products ', C('DB_PREFIX'), 'DB_CUSTOM');
        foreach($intergralList as $k=>$val){
            $tag['gid'] = $val['g_id'];
            $info = D('ViewGoods')->goodDetail($tag);
            if(!empty($info) && is_array($info)){
                $intergralList[$k]['detail'] = $info['list'];
            }
        }
        //积分类目获取
        $integralCat = M('integral_category',C('DB_PREFIX'),'DB_CUSTOM');
        $ary_cat_integral = $integralCat->field('gc_name,gc_id,gc_pic')->where(array('gc_is_display'=>1,'gc_status'=>1))->limit('0,10')->order('gc_order asc')->select();
        $this->assign('integral_cat',$ary_cat_integral);



//        $this->assign('city',$ary_city);
        $this->assign('pagearr',$pageInfo);

        $this->assign('data',$intergralList);
//        echo'<pre>';var_dump($intergralList);exit;
        $this->assign('get',$get);
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/integralList.html';
        $title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE','','',1);
        $this->assign('page_title', $title['GY_SHOP_TITLE']['sc_value'] . '- 积分兑换推荐');
        $this->display($tpl);
    }


    public function detail(){
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
        $ary_request = $this->_request();

        $int_integral_id = (int)$ary_request['integral_id'];
        $mod = D("Integral");
        if ($_SESSION['Members']['m_id']) {
            $m_id = $_SESSION['Members']['m_id'];
        }
        //判断积分+金额兑换活动是否已结束
        $btween_time = $mod->where(array('integral_id'=>$int_integral_id))->field("integral_start_time,integral_end_time")->find();

//        if(strtotime($btween_time['integral_start_time']) > mktime()){
//            // $this->error('积分+金额兑换活动未开始',U('Home/Integral'));
//            //exit;
//        }

        if(strtotime($btween_time['integral_end_time']) < mktime()){
            $this->error('积分兑换已结束',U('Home/Integral'));
            exit;
        }
        $ary_data = $mod->field('gi.g_name,g.g_sn,spc.gc_name,'.C('DB_PREFIX').'integral.*')
            ->join(C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->join(C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->join(C('DB_PREFIX')."integral_category as spc on(spc.gc_id=".C('DB_PREFIX')."integral.gc_id)")
            ->where(array('integral_id'=>$int_integral_id))->find();

        if($_SESSION['OSS']['GY_QN_ON'] == '1' ){//七牛图片显示
            $ary_data['integral_picture'] = D('QnPic')->picToQn($ary_data['integral_picture']);
            $ary_data['integral_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['integral_desc']);
        }
        //获取同类积分兑换
        $where['gc_id'] = $ary_data['gc_id'];
        $where['integral_status'] = 1;
        $where['integral_id'] = array('neq',$int_integral_id);
        $likeglist = $mod->where($where)->limit(0,6)->order('integral_start_time desc')->select();
        $glist = array();
        $count = count($likeglist);
        for($i=0;$i<$count/3;$i++){
            for($k=0;$k<3;$k++){
                $glist[$i][$k]=$likeglist[$i*3+$k];
            }
        }

        $goods_info = D('Goods')->where(array('g_id'=>$ary_data['g_id']))->count();

        if($goods_info == 0){
            $this->error('该商品不存在',U('Home/Integral'));
        }

        $pageView = $this->getPageView($ary_data['g_id']);
        //会员浏览历史单个会员浏览记录

        if(is_numeric($ary_data['g_id'])){
            $this->updcookie($ary_data['g_id']);
        }

        //会员可用积分
        $ary_point = D('Members')->where(array('m_id'=>$_SESSION['Members']['m_id']))->field('total_point,freeze_point')->find();
        $valid_point = 0;//有用积分数
        if($ary_point && $ary_point['total_point']>$ary_point['freeze_point']){
            $valid_point = intval($ary_point['total_point'] - $ary_point['freeze_point']);
        }
        $this->assign('valid_point',$valid_point);//积分总和
        $this->assign('pageView', $pageView);
        $this->assign('itemInfo', $ary_request);
        $this->assign('m_id', $m_id);

        $warm_prompt = D('Gyfx')->selectOneCache('sys_config','sc_value', array('sc_module'=>'ITEM_IMAGE_CONFIG','sc_key'=>'TIPS'));
        //D('SysConfig')->where()->getField('sc_value');

        $this->assign('warm_prompt',$warm_prompt['sc_value']);
        $pid = $this->getCateParent($ary_data['g_id']);
        $ary_request['cid'] = $pid;

        $common = D('SysConfig')->getCfgByModule('goods_comment_set','1');
        $this->assign("common",$common);

        $this->assign("likeglist",$glist);
        $this->assign("data",$ary_data);

        $this->assign("ary_request", $ary_request);
        $this->setTitle('商品积分兑换页'.$ary_data['g_name'],'TITLE_GOODS','DESC_GOODS','KEY_GOODS');
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/integralDetail.html';
        $this->display($tpl);

    }

    /**
     * 获取访问量信息
     *
     * @param boolean $increase 是否将访问量增加1
     * @return integer
     */
    function getPageView($gid) {
        //$key_obj = M('keystore',C('DB_PREFIX'),'DB_CUSTOM');
        if (!empty($gid)) {
            try {
                $row = M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->where(array("g_id" => $gid))->find();
                if ($row) {
                    $update_data['value'] = $row['value'] + 1;
                    $update_data['modify_time'] = date("Y-m-d H:i:s");
                    M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $gid))->save($update_data);
                } else {
                    $insert_data['g_id'] = $gid;
                    $insert_data['value'] = 1;
                    $insert_data['create_time'] = date("Y-m-d H:i:s");
                    $insert_data['modify_time'] = date("Y-m-d H:i:s");
                    M('keystore', C('DB_PREFIX'), 'DB_CUSTOM')->add($insert_data);
                }
            } catch (Exception $e) {
                //
            }
            return (int) ($row['value'] + 1);
        }
    }

    /**
     * 记录会员访问历史
     *
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-11-18
     */
    public function updcookie($gid) {
        $expire = time() + $this->history_expire;
        //达到最大显示数目时就更新现有数据
        $ary_cookie_items = cookie('HistoryItems');
        if(!empty($ary_cookie_items)){
            $count = count($ary_cookie_items);
            if($count>=$this->max_num){
                $first_iid = array_keys(array_splice(cookie('HistoryItems'),1));
                cookie("HistoryItems[$first_iid[0]]",null);
            }
        }
        cookie("HistoryItems[$gid]", $gid, $expire);
    }

    /**
     * 获取商品类目
     * @author Wangguibin
     * @date 2013-11-21
     */
    public function getCateParent($gid){
        $condition = array();
        $condition[C('DB_PREFIX').'goods_info.g_id'] = $gid;
        $ary_goods=M('goods_info',C('DB_PREFIX'),'DB_CUSTOM')
            ->field(array(C('DB_PREFIX').'goods_category.gc_id',C('DB_PREFIX').'goods_category.gc_parent_id'))
            ->join(C('DB_PREFIX').'related_goods_category ON '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods_info.g_id')
            ->join(C('DB_PREFIX').'goods_category ON '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
            ->where($condition)->find();
        if($ary_goods['gc_parent_id']){
            return $ary_goods['gc_parent_id'];
        }else{
            return $ary_goods['gc_id'];;
        }
    }
}