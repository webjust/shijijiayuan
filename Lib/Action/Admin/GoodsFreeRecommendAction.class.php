<?php
/**
 * 后台商品自由搭配资料控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.4.5
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-11-04
 * @copyright Copyright (C) 2013, Shanghai GuanYiSoft Co., Ltd.
 */
class GoodsFreeRecommendAction extends AdminAction{
    /**
     * 自由搭配列表
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function freeRecommendList(){
        $this->getSubNav(5,3,10);
        //页面接收的查询条件 ++++++++++++++++++++++++++++++++++++++++++++++++++++
        $chose = array();
        $chose['field'] = $this->_get('field');
        $chose['val'] = $this->_get('val', 'htmlspecialchars,trim', '');
        $chose['fr_statr_time'] = $this->_get('fr_statr_time', 'htmlspecialchars,trim', '');
        $chose['fr_end_time'] = $this->_get('fr_end_time', 'htmlspecialchars,trim', '');
        //拼接查询条件 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        $where = array();
        if($chose['field'] == '1' && !empty($chose['val'])){
            //搭配标题搜索
            $where['fr_name'] = array('LIKE', '%' . $chose['val'] . '%');
        }elseif($chose['field'] == '2' && !empty($chose['val'])){
            //商品ID搜索、获取商品id
            $g_id = D('Goods')->where(array('g_sn'=>$chose['val']))->getField('g_id');
            $where['fr_goods_id'] = $g_id;
        }
        if(!empty($chose['fr_statr_time']) && !empty($chose['fr_end_time'])){
            $where['fr_statr_time'] = array('EGT',$chose['fr_statr_time']);
            $where['fr_end_time'] = array('ELT',$chose['fr_end_time']);
            
        }
        $count = D('FreeRecommend')->where($where)->count();
        $Page = new Page($count, 20);
        $data['page'] = $Page->show();
        $limit['start'] =$Page->firstRow;
        $limit['end'] =$Page->listRows;
        $data['list'] = D('FreeRecommend')->where($where)->limit($limit['start'],$limit['end'])->select();
       // echo  D('FreeRecommend')->getLastSql();exit;
        $this->assign($data);    //赋值数据集，和分页
        $this->display('rec_list');
    }
    
    /**
     * 新增自由搭配页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function addFreeRecommendPage(){
        $this->getSubNav(5,3,20);
        //获取商品品牌并传递到模板
		$array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
		$this->assign("array_brand",$array_brand);
		//获取商品分类并传递到模板
		$array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
        $this->assign("array_category",$array_category);
        $this->display('add_rec');
    }
    
    /**
     * 执行添加自由搭配操作
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function doAdd(){
        $array_post = $this->_post();
        //判断搭配标题是否存在
        $count = D('FreeRecommend')->where(array('fr_name'=>$array_post['fr_name']))->count();
        if($count){
            $this->error('搭配标题已存在');
        }
        //验证自由搭配商品的唯一性
        $check_result_gid = D('FreeRecommend')->where(array('fr_goods_id'=>$array_post['g_id']))->getField("fr_name");
        if(isset($check_result_gid) && !empty($check_result_gid)){
            $this->error('该商品已存在其他搭配，请重新选择');
        }
        //验证时间有效性
        if($array_post['fr_statr_time']!='0000-00-00 00:00:00' || $array_post['fr_end_time']!='0000-00-00 00:00:00'){
            if(strtotime($array_post['fr_statr_time']) > strtotime($array_post['fr_end_time'])){
                $this->error('开始时间不能小于结束时间');
            }
        }
        //执行录入操作
        $array_post['fr_goods_id'] = $array_post['g_id'];
        unset($array_post['search_cats'],$array_post['search_brand'],$array_post['keywords'],$array_post['g_id']);
		$array_post['fr_goods_picture'] = D('ViewGoods')->ReplaceItemPicReal($array_post['fr_goods_picture']);
        if(false === D('FreeRecommend')->add($array_post)){
            $this->error('添加自由搭配失败');
        }
        $string_jump_url = U("Admin/GoodsFreeRecommend/freeRecommendList");
        $this->success("操作成功",$string_jump_url);
    }
    
    /**
     * 停用（启用）自由搭配
     *
     * @author <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function enableFreeRecommend(){
        $fr_id = $this->_post('fr_id');
        $fr_status = $this->_post('fr_status');
        $FreeRecommend = D("FreeRecommend");
        if($fr_status == 1){
            $msg = '开启成功！';
        }else{
            $msg = '关闭成功！';
        }
        if($FreeRecommend->where(array('fr_id'=>$fr_id))->save(array('fr_status'=>$fr_status))){
            return $this->ajaxReturn(array('status'=>'success','Msg'=>$msg));
        }else{
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'失败'));
        }
    }
    
    /**
     * 删除自由搭配信息
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function ajaxDelFreeRecommend(){
        $str_fr_id = $this->_post('fr_id');
        if(empty($str_fr_id)){
            return $this->ajaxReturn(array('status'=>'error','Msg'=>'请选择要删除的自由搭配数据！'));
        }
        //删除主表
        if(false === D('FreeRecommend')->where(array('fr_id'=>array('in',$str_fr_id)))->delete()){
            $this->ajaxReturn(array('status'=>'error','Msg'=>'删除失败'));
        }
        $this->ajaxReturn(array('status'=>'success','Msg'=>'删除成功'));
    }
    
    /**
     * 编辑自由搭配页面
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function editFreeRecommendPage(){
        $this->getSubNav(5,3,30,'编辑自由搭配');
        //获取编辑自由搭配信息
        $array_recommend = D('FreeRecommend')->where(array('fr_id'=>$this->_get('fr_id')))->find();
        if(empty($array_recommend)){
            $this->error('不存在该自由搭配');
        }
        //获取商品信息
        $array_goods_info = D('GoodsInfo')->where(array('g_id'=>$array_recommend['fr_goods_id']))->find();
		$array_goods_info['g_picture'] =  '/' . ltrim(trim($array_goods_info["g_picture"]),'/');
		$array_goods_info['g_picture'] =  D('QnPic')->picToQn($array_goods_info['g_picture']);
        $array_goods_info['g_field'] = $array_goods_info['g_name'].',价格：'.$array_goods_info['g_price'];
        //echo "<pre>";print_r($str_g_info);exit;
        //获取商品品牌并传递到模板
        $array_brand = D("GoodsBrand")->where(array("gb_status"=>1))->select();
        $this->assign("array_brand",$array_brand);
        //获取商品分类并传递到模板
        $array_category = D("GoodsCategory")->getChildLevelCategoryById(0);
        $this->assign("array_category",$array_category);
        $this->assign("goods",$array_goods_info);
        $this->assign($array_recommend);
        $this->display('edit_rec');
    }
    
    /**
     * 执行编辑操作
     *
     * @author Joe <qianyijun@guanyisoft.com>
     * @date 2013-11-04
     */
    public function doEdit(){
        $array_post = $this->_post();
        $fr_id = $array_post['fr_id'];
        //验证商品标题是否存在
        $check_result_name = D('FreeRecommend')->where(array('fr_name'=>$array_post['fr_name'],'fr_id'=>array('neq',$fr_id)))->getField("fr_name");
        if(isset($check_result_name) && !empty($check_result_name)){
            $this->error('搭配标题已存在');
        }
        //验证自由搭配商品的唯一性
        $check_result_gid = D('FreeRecommend')->where(array('fr_goods_id'=>$array_post['g_id'],'fr_id'=>array('neq',$fr_id)))->getField("fr_name");
        if(isset($check_result_gid) && !empty($check_result_gid)){
            $this->error('该商品已存在其他搭配，请重新选择');
        }
        //验证时间有效性
        if($array_post['fr_statr_time']!='0000-00-00 00:00:00' || $array_post['fr_end_time']!='0000-00-00 00:00:00'){
            if(strtotime($array_post['fr_statr_time']) > strtotime($array_post['fr_end_time'])){
                $this->error('开始时间不能小于结束时间');
            }
        }
        $array_post['fr_goods_id'] = $array_post['g_id'];
        //编辑
        unset($array_post['search_cats'],$array_post['search_brand'],$array_post['keywords'],$array_post['fr_id'],$array_post['g_id']);
		$array_post['fr_goods_picture'] = D('ViewGoods')->ReplaceItemPicReal($array_post['fr_goods_picture']);
        if(false === D('FreeRecommend')->where(array('fr_id'=>$fr_id))->save($array_post)){
            $this->error('操作失败');
        }
        $string_jump_url = U("Admin/GoodsFreeRecommend/freeRecommendList");
        $this->success('操作成功！',$string_jump_url);
    }

}