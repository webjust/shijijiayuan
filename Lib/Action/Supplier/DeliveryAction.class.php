<?php

/**
 * 后台配送公司设置控制器
 *
 * @package Action
 * @subpackage Admin
 * @stage 7.0
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2013-01-18
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class DeliveryAction extends AdminAction {

    /**
     * 控制器初始化
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function _initialize() {
        parent::_initialize();
        $this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
        $this->setTitle(' - ' . L('MENU3_4'));
    }

    /**
     * 默认控制器动作
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function index() {
        $this->redirect(U('Admin/Delivery/pageList'));
    }

    /**
     * 配送公司列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageList() {
        $this->getSubNav(4, 4, 10);

        $Corp = D('LogisticCorp');
        $data['list'] = $Corp->order('lc_ordernum ASC')->select();
        $this->assign($data);
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
		$this->assign('pay_name',$pay_name);
        $this->display();
    }

    /**
     * 配送公司添加
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageAdd(){
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
		$this->assign('pay_name',$pay_name);
        $this->getSubNav(4, 4, 20);
        $this->display();
    }

    /**
     * 配送公司修改
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageEdit(){
        $this->getSubNav(4, 4, 10, '修改配送公司');
        $lc_id = $this->_get('lc_id');
        $Corp = D('LogisticCorp');
        $data['info'] = $Corp->where(array('lc_id'=>$lc_id))->find();
		$is_zt =  D('SysConfig')->getConfigs('IS_ZT', 'IS_ZT');
		$pay_name = '货到付款';
		if($is_zt['IS_ZT']['sc_value'] == 1){
			$pay_name = D('PaymentCfg')->where(array('pc_abbreviation'=>'DELIVERY'))->getField('pc_custom_name');
		}
		$this->assign('pay_name',$pay_name);
        $this->assign($data);
        $this->display();
    }

    /**
     * 添加配送公司
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function doAdd(){
        $Corp = D('LogisticCorp');
        $data = $Corp->create();
        $data['lc_create_time'] = date('Y-m-d h:i:s');
        $data['lc_update_time'] = date('Y-m-d h:i:s');
        $result = $Corp->data($data)->add();
        if($result == false){
            
            $this->error('配送公司添加失败');
        }else{
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"添加配送公司",'添加的配送公司为：'.$data['lc_name']));
            $this->success('配送公司添加成功',U('Admin/Delivery/pageList'));
        }
    }

    /**
     * 修改配送公司
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function doEdit(){
        $Corp = D('LogisticCorp');
        $data = $Corp->create();
        $data['lc_update_time'] = date('Y-m-d h:i:s');
        $result = $Corp->where(array('lc_id'=>$data['lc_id']))->data($data)->save();
        if($result == false){
            $this->error('修改公司添加失败');
        }else{
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"修改配送公司",'修改的ID：'.$data['lc_id']));
            $this->success('修改公司添加成功',U('Admin/Delivery/pageList'));
        }
    }

    /**
     * 删除配送公司
     * @author zuo <zuojianghua@guanyioft.com>
     * @date 2013-01-18
     */
    public function doDel(){
        $lc_id = $this->_get('lc_id');
        $Corp = D('LogisticCorp');
        if(is_array($lc_id)){
            $where = array('lc_id'=>array('IN',$lc_id));
        }else{
            $where = array('lc_id'=>$lc_id);
        }
        $result = $Corp->where($where)->delete();
        if($result == false){
            $this->error('删除失败');
        }else{
            //删除相关的配送区域
            D('LogisticType')->where($where)->delete();
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"删除配送公司",'删除配送公司，ID为：'.$lc_id));
            $this->success('删除成功');
        }
    }

    #### 以下为配送区域处理 #####################################################

    /**
     * 配送区域列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageListArea() {
        $this->getSubNav(4, 4, 10, '配送区域列表');
        $lc_id = $this->_get('lc_id');
        //物流公司信息 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $Corp = D('LogisticCorp');
        $data['info'] = $Corp->where(array('lc_id' => $lc_id))->find();

        //该物流公司已有的配送区域列表 ++++++++++++++++++++++++++++++++++++++++++
        $Area = D('LogisticType');
        $data['list'] = $Area->where(array('lc_id' => $lc_id))->select();

        //查找配送的城市 ++++++++++++++++++++++++++++++++++++++++++++++++++++++
        foreach ($data['list'] as $k => $v) {
            $data['list'][$k]['city'] = D('RelatedLogisticCity')
                    ->where(array('lt_id' => $v['lt_id']))
                    ->field("cr_name")
                    ->join("fx_city_region on fx_city_region.cr_id = fx_related_logistic_city.cr_id")
                    ->select();
        }
        //显示页面 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 为配送公司添加配送区域
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageAddArea() {
        $this->getSubNav(4, 4, 10, '添加配送区域');
        $lc_id = $this->_get('lc_id');
        //物流公司信息 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $Corp = D('LogisticCorp');
        $data['info'] = $Corp->where(array('lc_id' => $lc_id))->find();
        //获取地区数据 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //$parent = $this->_get('parent', 'htmlspecialchars', 1);
        $data['cityRegion'] = D('CityRegion')->getParentsAddr(1);
        //dump($data['city']);exit;
        //显示页面 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 为配送公司修改配送区域
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function pageEditArea(){
        $this->getSubNav(4, 4, 10, '修改配送区域');
        $lc_id = $this->_get('lc_id');
        $lt_id = $this->_get('lt_id');
        //物流公司信息 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $Corp = D('LogisticCorp');
        $data['info'] = $Corp->where(array('lc_id' => $lc_id))->find();
        //获取地区数据 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $parent = $this->_get('parent', 'htmlspecialchars', 1);
        $data['cityRegion'] = D('CityRegion')->getParentsAddr($parent);
        //获取配送区域数据 +++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['area'] = D('LogisticType')->where(array('lt_id'=>$lt_id))->find();
        $data['area']['config'] = json_decode($data['area']['lt_expressions'],true);
        //关联城市 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $data['city'] = D('RelatedLogisticCity')
            ->where(array('lt_id'=>$lt_id))
            ->field(array('cr_name','fx_city_region.cr_id'=>'cr_id'))
            ->join("fx_city_region on fx_city_region.cr_id = fx_related_logistic_city.cr_id")
            ->select();
        //显示页面 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行添加配送区域
     * @author zuo <zuojianghua@guansyisoft.com>
     * @date 2013-01-18
     */
    public function doAddArea() {
        $Area = D('LogisticType');
        $data = $Area->create();
        $expressions = array(
            'logistics_first_weight' => $this->_post('logistics_first_weight'),
            'logistics_first_money' => $this->_post('logistics_first_money'),
            'logistics_add_weight' => $this->_post('logistics_add_weight'),
            'logistics_add_money' => $this->_post('logistics_add_money'),
            'logistics_configure' => $this->_post('logistics_configure'),
        );
        $data['lt_expressions'] = json_encode($expressions);
        $data['lt_create_time'] = date('Y-m-d h:i:s');
        $data['lt_update_time'] = date('Y-m-d h:i:s');
        $data['lt_status'] = 1;

        $result = $Area->data($data)->add();
        if (false == $result) {
            $this->error('添加配送区域失败');
        } else {
            //添加配送区域与城市的关系
            $insert_Related = array();
            foreach ($this->_post('cr_id') as $v) {
                $insert_Related[] = array(
                    'cr_id' => $v,
                    'lt_id' => $result
                );
            }
            D('RelatedLogisticCity')->addAll($insert_Related);
            
            $this->success('添加配送区域成功', U('Admin/Delivery/pageListArea', array('lc_id' => $data['lc_id'])));
        }
    }

    /**
     * 执行修改配送区域
     * @author zuo <zuojianghua@guansyisoft.com>
     * @date 2013-01-18
     */
    public function doEditArea(){
        $Area = D('LogisticType');
        $data = $Area->create();
        $expressions = array(
            'logistics_first_weight' => $this->_post('logistics_first_weight'),
            'logistics_first_money' => $this->_post('logistics_first_money'),
            'logistics_add_weight' => $this->_post('logistics_add_weight'),
            'logistics_add_money' => $this->_post('logistics_add_money'),
            'logistics_configure' => $this->_post('logistics_configure'),
        );
        $data['lt_expressions'] = json_encode($expressions);
        $data['lt_update_time'] = date('Y-m-d h:i:s');

        $result = $Area->where(array('lt_id'=>$data['lt_id']))->data($data)->save();
        if (false == $result) {
            $this->error('修改配送区域失败');
        } else {
            //删除原有的关系
            D('RelatedLogisticCity')->where(array('lt_id'=>$data['lt_id']))->delete();
            //添加配送区域与城市的关系
            $insert_Related = array();
            foreach ($this->_post('cr_id') as $v) {
                $insert_Related[] = array(
                    'cr_id' => $v,
                    'lt_id' => $data['lt_id']
                );
            }
            D('RelatedLogisticCity')->addAll($insert_Related);
            $this->success('修改配送区域成功', U('Admin/Delivery/pageListArea', array('lc_id' => $data['lc_id'])));
        }
    }

    /**
     * 删除配送区域
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function doDelArea(){
        //$lc_id = $this->_get('lc_id');
        $lt_id = $this->_get('lt_id');

        if(is_array($lt_id)){
            $where = array('lt_id'=>array('IN',$lt_id));
        }else{
            $where = array('lt_id'=>$lt_id);
        }

        $result = D('LogisticType')->where($where)->delete();

        if($result == false){
            $this->error('删除错误');
        }else{
            //删除对应关系
            D('RelatedLogisticCity')->where($where)->delete();
            $this->success('删除成功');
        }
    }
    
    /**
     * 地址库管理
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-21
     */
    public function pageAddress(){
        $this->getSubNav(4, 4, 30);
        //获取地区数据 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $parent = $this->_get('parent', 'htmlspecialchars', 1);
        $data['cityRegion'] = D('CityRegion')->getParentsAddr($parent);
        $this->assign($data);
        $this->display();
    }
    
    /**
     * 删除地址库管理
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-21
     */
    public function delCityAddress(){
        $ary_res = array('success'=>'0','msg'=>'删除成功');
        $ary_post = $this->_post();
        if(!empty($ary_post) && is_array($ary_post)){
            $ary_result = D('CityRegion')->delCityAddress($ary_post['cr_id']);
            if($ary_result){
                $ary_res['success'] = '1';
                $ary_res['msg'] = '删除成功';
            }else{
                $ary_res['success'] = '0';
                $ary_res['msg'] = '删除失败';
            }
        }else{
            $ary_res['success'] = '0';
            $ary_res['msg'] = '请选择需要删除的省市区';
        }
        echo json_encode($ary_res);exit;
    }
    
    /**
     * 添加地址库管理
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-21
     */
    public function addCityAddress(){
        $ary_res = array('success'=>'0','msg'=>'添加成功');
        $ary_post = $this->_post();
        $city = M("city_region",C('DB_PREFIX'),'DB_CUSTOM');
        if(!empty($ary_post) && is_array($ary_post)){
            $ary_result = D('CityRegion')->selectCityAddress($ary_post['cr_id'],$ary_post['cityname']);
            if(!empty($ary_result) && is_array($ary_result)){
                $where = array();
                $data = array();
                $data['cr_status'] = 1;
                $data['cr_update_time'] = date("Y-m-d H:i:s");
                $where['cr_id'] = $ary_result['cr_id'];
                $ary_result = D('CityRegion')->editCityAddress($where,$data);
                if($ary_result){
                    $ary_res['success'] = '1';
                    $ary_res['msg'] = '添加成功';
                }else{
                    $ary_res['success'] = '0';
                    $ary_res['msg'] = '添加失败';
                }
            }else{
                $data = array(
                        'cr_name'	=>$ary_post['cityname'],
                        'cr_parent_id' => $ary_post['cr_id'],
                        'cr_status'	=>  1,
                        'cr_create_time' => date("Y-m-d H:i:s")
                );
                if($ary_post['cr_id'] == '1'){
                    $data['cr_path'] = $ary_post['cr_id'];
                    $data['cr_is_parent'] = '1';
                }else{
                    $ary_data = $city->where(array("cr_id"=>$ary_post['cr_id']))->find();
                    $data['cr_is_parent'] = '0';
                    $data['cr_path'] = "1"."|".$ary_data['cr_parent_id']."|".$ary_data['cr_id'];
                }
                $ary_result = D('CityRegion')->addCityAddress($data);
                if($ary_result){
                    $ary_res['success'] = '1';
                    $ary_res['msg'] = '添加成功';
                }else{
                    $ary_res['success'] = '0';
                    $ary_res['msg'] = '添加失败';
                }
            }
        }
        echo json_encode($ary_res);exit;
    }
    
    /**
     * 编辑地址库管理
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-01-21
     */
    public function editCityAddress(){
        $ary_res = array('success'=>'0','msg'=>'编辑成功');
        $ary_post = $this->_post();
        if(!empty($ary_post) && is_array($ary_post)){
            $where = array();
            $data = array();
            $data['cr_name'] = $ary_post['cityname'];
            $data['cr_update_time'] = date("Y-m-d H:i:s");
            $where['cr_id'] = $ary_post['cr_id'];
            $ary_result = D('CityRegion')->editCityAddress($where,$data);
            if($ary_result){
                $ary_res['success'] = '1';
                $ary_res['msg'] = '编辑成功';
            }else{
                $ary_res['success'] = '0';
                $ary_res['msg'] = '编辑失败';
            }
        }
        echo json_encode($ary_res);exit;
    }
    /**
     * 获取省市ID下所有区域的名称 （以HTML形式输出）
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2012-12-20
     */
    public function getCityRegion() {
        $parent = $this->_post('parent');
        $city_region_data = D('CityRegion')->getParentsAddr($parent);
        $html = '<option value="0" selected="selected">请选择</option>';
        if (count($city_region_data) > 0) {
            foreach ($city_region_data as $item) {
                $html .= "<option value='{$item['cr_id']}'>{$item['cr_name']}</option>";
            }
        }
        echo $html;
        exit;
    }
}