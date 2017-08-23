<?php

/**
 * Class IntegralAction 后台积分兑换 商品控制器
 */

class IntegralAction extends AdminAction {

    public function _initialize() {
        parent::_initialize();
        $this->log = new ILog('db');
        $this->setTitle(' - '.'积分活动');
    }

    /**
     * 后台秒杀控制器默认页，需要重定向
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-01-06
     */
    public function index(){
        $this->redirect(U('Admin/Integral/pageList'));
    }

    /**
     * 列表页
     */
    public function pageList(){
        $this->getSubNav(5, 8, 10);
        $mod = D($this->_name);
        $ary_data = $this->_get();
        //搜索条件处理
        $array_cond = array();
        //如果根据名称进行搜索
        switch ($ary_data['field']) {
            case 1:
                $array_cond[C('DB_PREFIX')."integral.integral_title"] = array("LIKE", "%" . $ary_data['val'] . "%");
                break;
            case 2:
                $array_cond["gi.g_name"] = array("LIKE", "%" . $ary_data['val'] . "%");
                break;
            case 3:
                if(!empty($ary_data['val'])){
                    $array_cond["g.g_sn"] = $ary_data['val'];
                }
                break;
            default:
                break;
        }
        if(!empty($ary_data['gcid'])){
            $array_cond["fx_integral.gc_id"] = intval($ary_data['gcid']);
        }
        //如果根据团购的有效期进行搜索
        if (isset($ary_data["integral_start_time"]) && "" != $ary_data["integral_start_time"]) {
            $array_cond["integral_start_time"] = array("egt", $ary_data["integral_start_time"]);
        }
        if (isset($ary_data["integral_end_time"]) && "" != $ary_data["integral_end_time"]) {
            $array_cond["integral_end_time"] = array("elt", $ary_data["integral_end_time"]);
        }
        $count = $mod
            ->join(" ".C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->join(" ".C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->where($array_cond)->count();

        $Page = new Page($count, 15);
        $ary_datalist = $mod->field('gi.g_name,g.g_sn,'.C('DB_PREFIX').'integral.*')
            ->join( " ".C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->join(" ".C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->where($array_cond)
            ->order(array('integral_update_time' => 'desc'))
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        $ary_data['list'] = $ary_datalist;
        $ary_data['page'] = $Page->show();
        $this->assign("filter", $ary_data);
        $this->assign($ary_data);

        $this->assign("gcid",intval($ary_data['gcid']));
//        $this->assign("cates",$cates);
        $this->display();
    }

    /*活动添加*/
    public function pageAdd(){
        $this->getSubNav(5, 8, 20);
        $array_brand = D("GoodsBrand")->where(array("gb_status" => 1))->select();
        //获取商品分类并传递到模板
        $array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
        $this->assign("array_brand", $array_brand);
        $this->assign("array_category", $array_category);
        $cates = D('Gyfx')->selectAll('integral_category');
        $this->assign("cates",$cates);
        $this->display();
    }

    public function doAdd(){
        $ary_post = $this->_post();
        $mod = D($this->_name);
        $ary_data['integral_title'] = trim($ary_post['integral_title']);
        //验证商品ID是否存在
        $ary_data['g_id'] = trim($ary_post['g_id']);
        if (empty($ary_post['g_id'])) {
            $this->error('请先选择商品信息');
        }
        $goods_count = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $ary_post['g_id']))->count();
        if ($goods_count == 0) {
            $this->error('商品信息不存在');
        }
        $where_integral = "1=1";
        $where_integral .= " and g_id=".$ary_post['g_id'];
        $where_integral .= " and integral_end_time > current_timestamp()";
        $integral_count = $mod->where($where_integral)->count();

        if ($integral_count != 0) {
            $this->error('此商品已被其他积分兑换活动使用',U('Admin/Integral/pageList'));
        }

        //保存上传的图片
        if($_FILES['integral_picture']['error'] == 0){
            $img_path = '/Public/Uploads/' . CI_SN . '/' . 'other/' . date('Ymd') . '/';
            if(!is_dir($img_path)){
                mkdir(APP_PATH . $img_path, 0777, 1);
            }
            $img_url = $img_path.'integral'.date('YmdHis') . $ary_post['g_id'] . '.jpg';
            $img_save_path = APP_PATH.$img_url;
            if(move_uploaded_file($_FILES['integral_picture'],$img_save_path)){
                $ary_data['integral_picture'] = $img_url;
            }
        }
        if ($ary_post['integral_num']) {
            $ary_data['integral_num'] = $ary_post['integral_num'];
        } else {
            $this->error('限购数量必须输入！');
        }
        $ary_data['gc_id'] = intval($ary_post['gcid']);
        if(empty($ary_data['integral_picture'])){
            $ary_data['integral_picture'] = $ary_post['integral_pic'];
        }
        //七牛图片存入
        $ary_data['integral_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['integral_picture']);

        if ($ary_post['integral_start_time']) {
            $ary_data['integral_start_time'] = $ary_post['integral_start_time'];
        }
        if ($ary_post['integral_end_time']) {
            $ary_data['integral_end_time'] = $ary_post['integral_end_time'];
        }
        if ($ary_post['integral_start_time'] > $ary_post['integral_end_time']) {
            $this->error('活动开始时间大于活动实效时间时间！');
        }

        $ary_data['integral_status'] = $ary_post['integral_status'] ? $ary_post['integral_status'] : '0';
        $ary_data['integral_goods_desc_status'] = $ary_post['integral_goods_desc_status'] ? $ary_post['integral_goods_desc_status'] : '0';
        if ($ary_post['integral_desc']) {
            $ary_data['integral_desc'] = $ary_post['integral_desc'];
        }
        if (isset($ary_post['integral_mobile_desc'])) {
            $ary_data['integral_mobile_desc'] = $ary_post['integral_mobile_desc'];
        }else{
            $ary_data['integral_mobile_desc'] = '';
        }
        //七牛图片存入
        $ary_data['integral_desc'] = _ReplaceItemDescPicDomain($ary_data['integral_desc']);

        if ($ary_post['money_need_to_pay']) {
            $ary_data['money_need_to_pay'] = $ary_post['money_need_to_pay'];
        }
		$ary_data['integral_need'] = $ary_post['integral_need']>0 ? $ary_post['integral_need'] : 0;
        $ary_data['integral_num'] = $ary_post['integral_num'];
        $ary_data['integral_create_time'] = date("Y-m-d H:i:s");
        $ary_data['integral_update_time'] = date("Y-m-d H:i:s");
        if($mod->add($ary_data)){
            $this->success("积分兑换添加成功",U('Admin/Integral/pageList'));
        }else{
            $this->error("积分活动添加失败",U('Admin/Integral/Index'));
        }
    }

    /**
     * 检验积分对话名称是否存在
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-11-11
     */
    public function checkName() {
        $ary_get = $this->_get();

        $mod = D($this->_name);
        if(!empty($ary_get['integral_id'])){
            $where = array();
            $where['integral_title'] = $ary_get['integral_title'];
            $where['integral_id'] = array("NEQ",$ary_get['integral_id']);
            $ary_data = $mod->where($where)->find();
            if (!empty($ary_data) && is_array($ary_data)) {
                $this->ajaxReturn("该积分兑换已经存在");
            } else {
                $this->ajaxReturn(true);
            }
        }else{
            $ary_data = $mod->where(array('integral_title' => $ary_get['integral_title']))->find();
            if (!empty($ary_data) && is_array($ary_data)) {
                $this->ajaxReturn("该积分兑换已经存在");
            } else {
                $this->ajaxReturn(true);
            }
        }
    }

    public function  edit(){
        $this->getSubNav(5, 8, 10, '编辑积分兑换');
        $int_integral_id = $this->_get('integral_id');
        $mod = D($this->_name);
        $ary_data = $mod->field('gi.g_name,g.g_sn,'.C('DB_PREFIX').'integral.*')
            ->join(C('DB_PREFIX')."goods as g on(g.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->join(C('DB_PREFIX')."goods_info as gi on(gi.g_id=".C('DB_PREFIX')."integral.g_id)")
            ->where(array('integral_id'=>$int_integral_id))->find();
        if(false == $ary_data){
            $this->error('积分兑换参数错误');
        }else{
            //七牛图片显示
            $ary_data['integral_picture'] = D('QnPic')->picToQn($ary_data['integral_picture']);
            $ary_data['integral_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['integral_desc']);
            $ary_data['integral_mobile_desc'] = D('ViewGoods')->ReplaceItemDescPicDomain($ary_data['integral_mobile_desc']);
            //获取商品品牌并传递到模板
            $array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
            $this->assign("array_brand",$array_brand);
            //获取商品分类并传递到模板
            $array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
            $this->assign("array_category",$array_category);


            $this->assign('info',$ary_data);

            $cates = D('Gyfx')->selectAll('integral_category');
            $this->assign("cates",$cates);

            $this->display();
        }
    }

    public function doEdit(){
        $ary_post = $this->_post();
        $int_integral_id = $ary_post['integral_id'];
        $mod = D($this->_name);
        $ary_data['integral_title'] = trim($ary_post['integral_title']);
        //验证商品ID是否存在
        $ary_data['g_id'] = trim($ary_post['g_id']);
        if (empty($ary_post['g_id'])) {
            $this->error('请先选择商品信息');
        }
        $goods_count = M('goods', C('DB_PREFIX'), 'DB_CUSTOM')->where(array('g_id' => $ary_post['g_id']))->count();
        if ($goods_count == 0) {
            $this->error('商品信息不存在');
        }
        $where_integral = "1=1";
        $where_integral .= " and g_id=".$ary_post['g_id'];
        $where_integral .= " and sp_end_time > current_timestamp()";
        $integral_count = $mod->where($where_integral)->count();

        if ($integral_count != 0) {
            $this->error('此商品已被其他积分兑换活动使用',U('Admin/Integral/pageList'));
        }

        //保存上传的图片
        if($_FILES['integral_picture']['error'] == 0){
            $img_path = '/Public/Uploads/' . CI_SN . '/' . 'other/' . date('Ymd') . '/';
            if(!is_dir($img_path)){
                mkdir(APP_PATH . $img_path, 0777, 1);
            }
            $img_url = $img_path.'integral'.date('YmdHis') . $ary_post['g_id'] . '.jpg';
            $img_save_path = APP_PATH.$img_url;
            if(move_uploaded_file($_FILES['integral_picture'],$img_save_path)){
                $ary_data['integral_picture'] = $img_url;
            }
        }

        if(empty($ary_data['integral_picture'])){
            $ary_data['integral_picture'] = $ary_post['integral_pic'];
        }
        //七牛图片存入
        $ary_data['integral_picture'] = D('ViewGoods')->ReplaceItemPicReal($ary_data['integral_picture']);

        if ($ary_post['integral_start_time']) {
            $ary_data['integral_start_time'] = $ary_post['integral_start_time'];
        }
        if ($ary_post['integral_end_time']) {
            $ary_data['integral_end_time'] = $ary_post['integral_end_time'];
        }
        if ($ary_post['integral_start_time'] > $ary_post['integral_end_time']) {
            $this->error('活动开始时间大于活动实效时间时间！');
        }

        $ary_data['integral_status'] = $ary_post['integral_status'] ? $ary_post['integral_status'] : '0';
        $ary_data['integral_goods_desc_status'] = $ary_post['integral_goods_desc_status'] ? $ary_post['integral_goods_desc_status'] : '0';
        if ($ary_post['integral_desc']) {
            $ary_data['integral_desc'] = $ary_post['integral_desc'];
        }
        if (isset($ary_post['integral_mobile_desc'])) {
            $ary_data['integral_mobile_desc'] = $ary_post['integral_mobile_desc'];
        }else{
            $ary_data['integral_mobile_desc'] = '';
        }
        //七牛图片存入
        $ary_data['integral_desc'] = _ReplaceItemDescPicDomain($ary_data['integral_desc']);

        $ary_data['integral_num'] = $ary_post['integral_num'];

        if ($ary_post['money_need_to_pay']) {
            $ary_data['money_need_to_pay'] = $ary_post['money_need_to_pay'];
        }
		$ary_data['integral_need'] = $ary_post['integral_need']>0 ? $ary_post['integral_need'] : 0;
        $ary_data['gc_id'] = intval($ary_post['gcid']);
        $ary_data['integral_create_time'] = date("Y-m-d H:i:s");
        $ary_data['integral_update_time'] = date("Y-m-d H:i:s");
        $integral_where = array('integral_id'=>$int_integral_id);

        if($mod->data($ary_data)->where($integral_where)->save()){
            $this->success("积分兑换修改成功",U('Admin/Integral/pageList'));
        }else{
            $this->error("积分兑换修改失败",U('Admin/Integral/Index'));
        }
    }

    public function doDel(){
        $mod = D($this->_name);
        $mix_id = $this->_param('integral_id');

        if(empty($mix_id)){
            $this->error('请先选择要删除的积分兑换');
        }
        if(is_array($mix_id)){
            $str_id = implode(",",$mix_id);
            $where = array('integral_id' => array('IN',$str_id));
        }else{
            $ary_id = explode(",",$mix_id);
            if (is_array($ary_id)) {
                //批量删除
                $where = array('integral_id' => array('IN',$mix_id));
            } else {
                //单个删除
                $where = array('integral_id' => $mix_id);
            }
        }
        $props = $mod->where($where)->field('integral_title')->select();
        $str_prop_name = '';
        foreach($props as $prop){
            $str_prop_name .=$prop['integral_title'];
        }
        $str_prop_name = trim($str_prop_name,',');
        $tmp_mix_id = implode(',',$mix_id);

        $mod->startTrans();
        $res_return = $mod->where($where)->delete();
        if (false == $res_return) {
            $mod->rollback();
            $this->error('删除失败');
        } else {
            $mod->commit();
            $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"积分兑换删除成功",'积分兑换为：'.$tmp_mix_id.'-积分兑换名称：'.$str_prop_name));
            $this->success('删除成功');
        }
    }


    /**
     * 后台积分金额兑换分类列表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateList() {
        $this->getSubNav(5, 8, 30);
        $ary_data = D('Gyfx')->selectAll('integral_category',$ary_field=null, $ary_where=null, array('gc_order'=>'asc'),$ary_group=null,$ary_limit=null);
        $this->assign('ary_cates',$ary_data);
        $this->display();
    }

    /**
     * 后台积分金额兑换分类添加
     */
    public function addCategory() {
        $this->getSubNav(5, 8, 30);
        $this->display();
    }

    /**
     * 添加分类操作
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date  2014-07-07
     */
    public function doAddCategory(){
        $array_insert_data = array();

        //验证商品分类的名称是否输入
        if(!isset($_POST['gc_name']) || "" == $_POST['gc_name']){
            $this->error('分类名称不能为空');
        }

        //验证商品分类的父级分类是否合法
        if(isset($_POST["gc_parent_id"]) && (!is_numeric($_POST["gc_parent_id"]) || $_POST["gc_parent_id"] < 0)){
            $this->error("上级分类ID参数不合法。");
        }

        //验证商品分类名称是否已经存在{此处规则修改：同级不重复}
        $array_cond = array('gc_name'=>$_POST['gc_name'],"gc_parent_id"=>$_POST["gc_parent_id"]);
        $array_result =  D('Gyfx')->selectOne('integral_category',$ary_field=null, $array_cond);
        if(is_array($array_result) && !empty($array_result)){
            $this->error('已经存在同级的积分金额兑换分类“' . $_POST['gc_name'] . '“！');
        }

        //验证商品分类排序字段的参数是否合法
        if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }

        //数据组装
        $array_insert_data['gc_name'] = trim($_POST['gc_name']);
        $array_insert_data['gc_parent_id'] = intval($_POST['gc_parent_id']);
        $array_insert_data['gc_order'] = $_POST['gc_order'];
        //gc_level 字段更新：此字段的值等于上级字段的gc_level + 1
        $array_insert_data['gc_level'] = 0;
//		if($array_insert_data['gc_parent_id'] > 0){
//			$array_parent_cond = array("gc_id"=>$array_insert_data['gc_parent_id']);
//			$int_parent_gc_level = D("GoodsCategory")->where($array_parent_cond)->getField("gc_level");
//			$array_insert_data['gc_level'] = $int_parent_gc_level + 1;
//		}
        $array_insert_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_insert_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_insert_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_insert_data['gc_create_time'] = date("Y-m-d h:i:s");
        //上传图片

        if($_FILES['integral_picture']['error'] == 0){
            $img_path = '/Public/Uploads/' . CI_SN . '/integral/';
            if(!is_dir($img_path)){
                mkdir(APP_PATH . $img_path, 0777, 1);
            }
            $img_url = $img_path.'integralcate'.date('YmdHis')  . '.jpg';
            $img_save_path = APP_PATH.$img_url;
            if(move_uploaded_file($_FILES['integral_picture'],$img_save_path)){
                $array_insert_data['gc_pic'] = $img_url;
            }
        }
        if(empty($ary_data['gc_pic'])){
            $array_insert_data['gc_pic'] = $_POST['gp_picture'];
        }
        //七牛图片存入
        $array_insert_data['gc_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_insert_data['gc_pic']);


        //事务开始
        $mixed_result =  D('Gyfx')->insert('integral_category',$array_insert_data);
        if(false === $mixed_result){
            $this->error("积分兑换分类添加失败。");
        }

        //页面跳转
        $page_jump_url = U('Admin/Integral/pageCateList');
        if(isset($_POST["page_jump"]) && 1 == $_POST["page_jump"]){
            $page_jump_url = U('Admin/Integral/addCategory');
        }
        $this->success('积分兑换分类添加成功', $page_jump_url);
    }

    /**
     * 积分金额兑换类目编辑页面显示
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function pageCateEdit(){
        $this->getSubNav(5, 8, 30,'积分金额兑换类目编辑');
        $gc_id=$this->_get('gcid');
        if(isset($gc_id)){
            $ary_data = D('Gyfx')->selectOne('integral_category','', array('gc_id'=>$gc_id));
            $this->assign('category',$ary_data);
            $this->display();
        }else {
            $this->error('参数错误');
        }
    }

    /**
     * 分类编辑操作
     * @author Wangguibin <wangguibin@guanyisoft.com>
     * @date 2014-07-07
     */
    public function doCateEdit(){
        if(!isset($_POST["gc_id"]) || !is_numeric($_POST["gc_id"])){
            $this->error("商品分类编辑参数错误。");
        }
        $int_gc_id = $_POST["gc_id"];

        //商品分类标题是否输入
        if(!isset($_POST["gc_name"]) || $_POST["gc_name"] == ""){
            $this->error("商品分类名称不能为空。");
        }

        //验证商品分类名称在同级分类下是否重复
        $array_cond = array("gc_id"=>array("neq",$int_gc_id),"gc_parent_id"=>$_POST["gc_parent_id"],"gc_name"=>$_POST["gc_name"]);
        $mixed_check_result = D("Gyfx")->selectOne('integral_category','',$array_cond);

        if(is_array($mixed_check_result) && !empty($mixed_check_result)){
            $this->error("已经存在同级的商品分类“" . $_POST["gc_name"] . "”。");
        }

        //验证商品分类排序字段是否是合法的数字
        if (!is_numeric(trim($_POST['gc_order'])) || $_POST['gc_order'] < 0 || $_POST['gc_order'] % 1 != 0) {
            $this->error('排序字段必须输入正整数！');
        }
        //数据拼装
        $array_modify_data = array();
        //上传图片
        if($_FILES['integral_picture']['error'] == 0){
            $img_path = '/Public/Uploads/' . CI_SN . '/integral/';
            if(!is_dir($img_path)){
                mkdir(APP_PATH . $img_path, 0777, 1);
            }
            $img_url = $img_path.'integralcate'.date('YmdHis')  . '.jpg';
            $img_save_path = APP_PATH.$img_url;
            if(move_uploaded_file($_FILES['integral_picture'],$img_save_path)){
                $array_modify_data['gc_pic'] = $img_url;
            }
        }
        if(empty($array_modify_data['gc_pic'])){
            $array_modify_data['gc_pic'] = $_POST['gc_pic'];
        }
        //七牛图片存入
        $array_modify_data['gc_pic'] = D('ViewGoods')->ReplaceItemPicReal($array_modify_data['gc_pic']);


        $array_modify_data["gc_name"] = trim($_POST["gc_name"]);
        $array_modify_data['gc_parent_id'] = $_POST['gc_parent_id'];
        $array_modify_data['gc_order'] = $_POST['gc_order'];
        $array_modify_data['gc_level'] = 0;
        $array_modify_data['gc_keyword'] = (isset($_POST['gc_keyword']) && "" != $_POST['gc_keyword'])?$_POST['gc_keyword']:"";
        $array_modify_data['gc_description'] = (isset($_POST['gc_description']) && "" != $_POST['gc_description'])?$_POST['gc_description']:"";
        $array_modify_data['gc_is_display'] = $_POST['gc_is_display'];
        $array_modify_data['gc_update_time'] = date("Y-m-d h:i:s");

        //事务开始
        $modify_result = D('Gyfx')->update('integral_category',array("gc_id"=>$int_gc_id),$array_modify_data);
        if(false === $modify_result){
            $this->error("积分金额兑换分类更新失败，数据没有更新。");
        }
        $this->success('积分金额兑换分类修改成功。', U('Admin/Integral/pageCateList'));
    }


    //删除分类 图片
    public function delCatePic(){
        $int_gc_id=$this->_get('gc_id');
        if(empty($int_gc_id)){
            $this->error('删除分类图片失败');
        }
        $bool_res = D('Gyfx')->update('integral_category',array('gc_id'=>$int_gc_id),array('gc_pic'=>'','gb_update_time'=>date('Y-m-d H:i:s')));
        if($bool_res){
            $this->success('删除分类图片成功',U('Admin/Integral/pageList',array('gcid'=>$int_gc_id)));
        }else{
            $this->error('删除分类图片失败',U('Admin/Integral/pageList',array('gcid'=>$int_gc_id)));
        }
    }

    public function doDelCate(){
        //判断当前分类id是否为数组
        if(!empty($_POST["gc_ids"])){
            $where = array('gc_id'=>array('in',$_POST["gc_ids"]));
        }else{
            $int_gc_id = $_GET["gcid"];
            $where = array('gc_id'=>$int_gc_id);
        }
        //删除商品分类
        $mixed_delete = D("Gyfx")->deleteInfo('integral_category',$where);
        if(false === $mixed_delete){
            $this->error("积分金额兑换分类删除失败。");
        }
        //页面提示并跳转
        $this->success('删除成功', U('Admin/Integral/pageCateList'));
    }



}