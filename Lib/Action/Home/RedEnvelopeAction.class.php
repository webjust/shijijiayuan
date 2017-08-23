<?php
class RedEnvelopeAction extends HomeAction{
    public function index(){
        //为了快速实现功能，读取当前已启用的规则
        $time = date('Y-m-d H:i:s');
        $array_rules = M('red_enevlope')->where(array('rd_is_status'=>1))->find();
        $seo_title = D('SysConfig')->getConfigs('GY_SHOP', 'GY_SHOP_TITLE');
        $rd_title = empty($array_rules['rd_title']) ? "百万红包疯狂抢" : $array_rules['rd_title'];
        $array_seo['page_title'] = $rd_title.' - '.$seo_title['GY_SHOP_TITLE']['sc_value'];
        $array_seo['page_keywords'] = $array_rules['rd_keywords'];
        $array_seo['page_description'] = $array_rules['rd_description'];
        
        if(!empty($array_rules) && isset($array_rules)){
            if(strtotime($array_rules['rd_start_time']) > mktime()){
            //活动未开始
            $array_rules['stat_time'] = '1';
            }elseif((strtotime($array_rules['rd_start_time']) < mktime()) && (strtotime($array_rules['rd_end_time'])< mktime())){
                //活动已结束
                $array_rules['stat_time'] = '2';
            }else{
                //活动进行中
                $array_rules['stat_time'] = '3';
            }
        }else{
            $array_rules['stat_time'] = '2';
            $array_rules['rd_start_time'] = '0000-00-00 00:00:00';
            $array_rules['rd_end_time'] = '0000-00-00 00:00:00';
        }
        //所有时间统一使用服务器时间，不使用客户端的时间
        $this->assign('time',$time);
        $this->assign($array_seo);
        $this->assign($array_rules);
        $this->assign('url',urlencode("http://".$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL']));
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/RedEnvelope.html';
        $this->display($tpl);
    }
    
    public function doAdd(){
        if(empty($_POST['rd_id'])){
            $this->error('暂无活动');
        }
        if(empty($_SESSION['Members']['m_id'])){
            $this->error('请先登录');
        }
        //实例化缓存,为了提高效率，配置缓存读取文件
        $cache = new Caches();
        if($cache->getStat() == 0 && !ini_get('memcache.allow_failover')){
            $this->error('请先开启Memcache缓存');
        }
        //设置关卡，最高以当前发放优惠券数量控制
        if($cache->C()->add('user_id_'.$_POST['rd_id'],'')){
            $ary_red_rules = M('red_enevlope')->where(array('rd_id'=>$_POST['rd_id'],'rd_is_status'=>1))->find();
            
            if(empty($ary_red_rules) && !isset($ary_red_rules)){
                $this->error('活动不存在或已结束');
            }
            
            $ary_related = M('related_coupon_red')->where(array('rd_id'=>$_POST['rd_id']))->select();
            $ary_coupon_used = 0;
            foreach($ary_related as $relVal){
                $coupon = D('Coupon')->where(array('c_name'=>$relVal['c_name']))->select();
                foreach ($coupon as $key=>$valss){
                    if($valss['c_user_id'] == 0){
                        $ary_coupon_used ++;
                    }
                }
            }
            $ary_coupon_used ++;
            $cache->C()->add('Red_'.$_POST['rd_id'].'_'.$ary_coupon_used, "PeopleWith");
            $cache->C()->add('red_rules'.$_POST['rd_id'], json_encode($ary_red_rules));
        }
        
        
        $ary_red_rules = json_decode($cache->C()->get('red_rules'.$_POST['rd_id']),true);
        
        if(empty($ary_red_rules) && !isset($ary_red_rules)){
            $this->error('活动不存在或已结束');
        }
        if(strtotime($ary_red_rules['rd_start_time']) > mktime()){
            $this->error('活动还未开始');
        }elseif((strtotime($ary_red_rules['rd_start_time']) < mktime()) && (strtotime($ary_red_rules['rd_end_time'])< mktime())){
            $this->error('活动已结束');
        }
        $result = $this->foreachAdd($cache,$_SESSION['Members'],$_POST['rd_id']);
        switch($result){
            case "msg_1":
                $this->success("恭喜{$_SESSION['Members']['m_name']}！抢到红包，该红包会在活动结束后发放。");
                break;
            case "msg_2":
                $this->error("您已经抢过了");
                break;
            default:
                $this->error("很遗憾，红包已抢完咯~！！");
        }
    }
    
    /**
     * @return msg_1 抢红包成功 msg_2 已抢过 msg_3 已抢完了
     *
     */
    public function foreachAdd($cache,$Members,$rd_id){
        //获取当前内存中的会员id
        $user_id = $cache->C()->get('user_id_'.$rd_id);
        if($user_id == ''){
            $user_id = $Members['m_id'];
            if($cache->C()->add("Red_".$rd_id."_1",'Now First')){
                $cache->C()->set('user_id_'.$rd_id,$user_id);
                return "msg_1";
            }else{
                $str_tmp_result = $cache->C()->get('Red_'.$rd_id.'_1');
                if($str_tmp_result == 'PeopleWith'){
                    return "msg_3";
                }else{
                    return $this->foreachAdd($cache,$Members);
                }
            }
        }else{
            //判断当前会员id是否已经存在内存中
            $array_tmp_mid = explode(',',$user_id);
            if(in_array($Members['m_id'],$array_tmp_mid)){
                return "msg_2";
            }
            $user_id .= ",".$Members['m_id'];
            $int_count = count(explode(',',$user_id));
            if(!$cache->C()->add('Red_'.$rd_id."_".$int_count)){
                $str_tmp_result = $cache->C()->get('Red_'.$rd_id.'_'.$int_count);
                if($str_tmp_result == 'PeopleWith'){
                    return "msg_3";
                }else{
                    return $this->foreachAdd($cache,$Members);
                }
            }else{
                $cache->C()->set('user_id_'.$rd_id,$user_id);
                return "msg_1";
            }
        }
        
    }
    
    /**
     * 打开发放红包页面
     *
     */
    public function synTouchRedCard(){
       // echo "<pre>";print_r($_SESSION);die();
        if(empty($_SESSION['Admin'])){
            die("请先登录管理员");
        }
        $datalist = M('red_enevlope')->order(array("rd_id"=>"desc"))->select();
        $cache = new Caches();
        foreach($datalist as &$val){
            $ary_related = M('related_coupon_red')->where(array('rd_id'=>$val['rd_id']))->select();
            $cache_members_nums = $cache->C()->get('user_id_'.$val['rd_id']);
            $array_coupon = array();
            $ary_coupon_used = 0;
            $str_coupon_name = '';
            foreach($ary_related as $relVal){
                $coupon = D('Coupon')->where(array('c_name'=>$relVal['c_name']))->select();
                $array_coupon = array_merge($array_coupon,$coupon);
                foreach ($coupon as $key=>$valss){
                    if($valss['c_user_id'] != 0){
                        $ary_coupon_used ++;
                    }
                }
                $str_coupon_name .= $relVal['c_name'].',';
            }
            $val['is_use_num'] = $ary_coupon_used;
            $val['coupon_name'] = rtrim($str_coupon_name,',');;
            $val['coupon_nums'] = count($array_coupon);
            if(empty($cache_members_nums)){
                $val['cache_members_nums'] =0;
            }else{
                $val['cache_members_nums'] =count(explode(',',$cache_members_nums));
            }
		}
		$data['list'] = $datalist;
        $this->assign($data);
        //echo "<pre>";print_r(explode(',',$cache_members_nums));die();
        $tpl = './Public/Tpl/' . CI_SN . '/' . TPL . '/synTouchRedCard.html';
        $this->display($tpl);
    }
    
    /**
     * 发红包咯
     *
     */
    public function fafanghongbao(){
        $cache = new Caches();
        $cache_members_nums = $cache->C()->get('user_id_'.$_POST['rd_id']);
        $datalist = M('red_enevlope')->where(array("rd_id"=>$_POST['rd_id']))->find();
        $ary_related = M('related_coupon_red')->where(array('rd_id'=>$_POST['rd_id']))->select();
        $array_coupon = array();
        foreach($ary_related as $relVal){
            $coupon = D('Coupon')->where(array('c_name'=>$relVal['c_name']))->select();
            foreach ($coupon as $key=>$valss){
                if($valss['c_user_id'] != 0){
                    unset($coupon[$key]);
                }
            }
            $array_coupon = array_merge($array_coupon,$coupon);
        }
        $array_members_id = explode(',',$cache_members_nums);
        if(count($array_members_id) > count($array_coupon)){
            $this->error('亲，这数量不对啊');
        }
        foreach ($array_members_id as $key=>$val){
            $coupon = $array_coupon[$key];
            M('Coupon')->where(array('c_id'=>$coupon['c_id']))->save(array('c_user_id'=>$val));
        }
        M('red_enevlope')->where(array('rd_id'=>$_POST['rd_id']))->save(array('rd_is_status'=>0));
        //清除所有缓存，清除前先把本次成功会员的id记录下来
        writeLog($cache_members_nums,'qianghongbao.log');
        $cache->C()->flush_all();
        $this->success('发放成功~！缓存已清空');
        
    }
    
    public function LockHB(){
        $array_coupon = array();
        $ary_related = M('related_coupon_red')->where(array('rd_id'=>$_POST['rd_id']))->select();
        foreach($ary_related as $relVal){
            $coupon = D('Coupon')->where(array('c_name'=>$relVal['c_name']))->select();
            foreach ($coupon as $key=>$valss){
                if($valss['c_user_id'] != 0){
                    array_push($array_coupon,$valss);
                }
            }
        }
        $html = "<tr class='showPHP{$_POST['rd_id']}'><td colspan='8'>";
        foreach($array_coupon as $key=>$val){
            $m_name = M('members')->where(array('m_id'=>$val['c_user_id']))->getField('m_name');
            $member_html .= $m_name."&nbsp;&nbsp;&nbsp;&nbsp;获得面值".$val['c_money']."元红包一张，编号：".$val['c_sn']."<br/>";
        }
        if(empty($member_html)){
            $member_html = "没有人获得红包~！！！";
        }
        $html .= $member_html."</td></tr>";
        echo $html;die();
    }

}