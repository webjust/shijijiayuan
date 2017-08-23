<?php

/**
 * 后台资讯控制器
 *
 * @subpackage Admin
 * @package Action
 * @stage 7.0
 * @author lf <liufeng@guanyisoft.com>
 * @date 2013-1-6
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class FenxiaoAction extends AdminAction {

    public function _initialize() {
        parent::_initialize();
        $this->setTitle(' - '.L('MENU6_6'));
    }

	/**
     * 淘宝供销平台对接，授权绑定
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-04-26
     */
	public function bindOauth(){
		$this->getSubNav(6,6,10);
		//获取淘宝分销平台授权信息
		$this->assign("bind_shop",M("top_supplier_info")->select());
		$this->display();
	}

	/**
     * 淘宝供销平台对接，授权绑定请求发起页面
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-04-26
     */
	public function topOauth(){
		//生成callback url
		$callback_url = "http://";
		$callback_url .= $_SERVER["HTTP_HOST"];
		if(80 != $_SERVER["SERVER_PORT"]){
			$callback_url .= ':' . $_SERVER["SERVER_PORT"];
		}
		$callback_url .= '/' . trim(U("Admin/Fenxiao/callback"),'/');
		//构造上传参数
		$array_params = array();
		$array_params["app_key"] = C("SAAS_KEY");
		$array_params["app_secret"] = C("SAAS_SECRET");
		$array_params["client_sn"] = CI_SN;
		$array_params["callback"] = $callback_url;
		//301 跳转到收取管理中心
		$array_saas = C("TMPL_PARSE_STRING");
		header("location:" . $array_saas["__FXCENTER__"] . "/Oauth/Top/index/?" . http_build_query($array_params) .'&newfx=1');
		exit;
	}

	/**
     * 淘宝供销平台对接，授权完成回跳页面
     * @author Mithern <sunguangxu@guanyisoft.com>
     * @date 2013-04-26
     */
	public function callback(){
		$array_data = $_GET;
		unset($array_data["state"]);
		$array_data["top_oauth_time"] = date("Y-m-d H:i:s");
		//删除已经存在的授权，将此授权保存
		if(false === M("top_supplier_info")->where(1)->delete()){
			$this->error("删除已经存在的授权出错！",U("Admin/Fenxiao/bindOauth"));
			exit;
		}
		//保存用户授权
		if(false === M("top_supplier_info")->add($array_data)){
			$this->error("保存用户收取信息出错！",U("Admin/Fenxiao/bindOauth"));
			exit;
		}
		//301到授权成功页面
		header("location:" . U("Admin/Fenxiao/bindOauth"));
		exit;
	}

	public function downloadData(){
		//$datalist = M("top_fenxiao_download_log")->where()->order('id desc')->select();
		//自己制造测试数据
		$datalist = array(
			array('id'=>10001,'name'=>'分销合作关系数据','model'=>'TopFenxiaoCooperation','desc'=>'供销平台合作关系数据' ),
			array('id'=>20001,'name'=>'会员品数据','model'=>'TopGoodsInfo','desc'=>'供销平台商品资料数据。' ),
			//array('id'=>30001,'name'=>'用户铺货记录','model'=>'TopDistributorItems','desc'=>'淘宝分销铺货记录数据下载' ),
			//array('id'=>40001,'name'=>'会员信息数据（pv，UV数据）','model'=>'TopFenxiaoCooperation','desc'=>'<span style="color:#ff0000;" title="聚石塔调用">[增值]</span>供销平台合作关系数据' ),
			array('id'=>50001,'name'=>'会员等级数据','model'=>'TopDistributorGrades','desc'=>'会员等级数据下载更新' ),
            array('id'=>60001,'name'=>'会员采购单数据','model'=>'TopPurchaseOrder','desc'=>'会员采购单数据下载' ),
		);
		$this->assign("datalist",$datalist);
		$this->getSubNav(6,6,20);
		$this->display();
	}

	public function getPageInfo(){
		if(!isset($_POST["mod"]) || "" == trim($_POST["mod"])){
			echo json_encode(array("status"=>false,"code"=>"error_params","message"=>"非法的mod参数传入"));
			exit;
		}
		$objModel = D(trim($_POST["mod"]));
		if(false === method_exists($objModel,"getPageInfo")){
			echo json_encode(array("status"=>false,"code"=>"object_not_exists","message"=>"实例化对象不存在"));
			exit;
		}
		//获取总的记录数
		$array_result = $objModel->getPageInfo();
		if(is_array($array_result) && !empty($array_result)){
			echo json_encode($array_result);
			exit;
		}
		//对分页进行处理
		$int_page_size = 30;
		$total_result = $array_result;
		$array_return = array("status"=>true,"total_results"=>$total_result,"pagesize"=>$int_page_size);
		$array_return["total_page"] = (0 == $total_result)?0:ceil($total_result/$int_page_size);
		echo json_encode($array_return);
		exit;
	}

	public function downloadRoute(){
		//系统级参数mod验证
		if(!isset($_POST["mod"]) || "" == trim($_POST["mod"])){
			echo json_encode(array("status"=>false,"code"=>"error_params_code","message"=>"非法的mod参数传入"));
			exit;
		}
		//对象实例化
		$objModel = D(trim($_POST["mod"]));
		//验证实例化对象方法是否存在
		if(false === method_exists($objModel,"download")){
			echo json_encode(array("status"=>false,"code"=>"object_not_exists","message"=>"实例化对象不存在"));
			exit;
		}
		//分页处理，默认下载第一页的数据
		$int_page_no = 1;
		if(isset($_POST["page_no"]) && is_numeric($_POST["page_no"])){
			$int_page_no = $_POST["page_no"];
		}
		//每页下载记录数处理
		$int_page_size = 30;
		if(isset($_POST["page_size"]) && is_numeric($_POST["page_size"])){
			$int_page_size = $_POST["page_size"];
		}
		//处理数据下载
		$array_result = $objModel->download($int_page_no,$int_page_size);
		//插入下一次要同步的页码
		$array_result["page_no"] = $int_page_no +1;
		echo json_encode($array_result);
		exit;
	}

	public function fenxiaoCount(){
		//var_dump(D("TopPurchaseOrder")->download(1));exit;
		//var_dump(D("TopFenxiaoCooperation")->download(1));exit;
		//var_dump(D("TopGoodsInfo")->download(1));exit;
		$this->getSubNav(6,6,30);
		$this->display();
	}
	public function distributorManger(){
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		$array_cond = 1;
		$count = M("top_fenxiao_cooperation")->where($array_cond)->count();
		$obj_page = new Page($count, $page_size);
		$this->assign('datalist',M("top_fenxiao_cooperation")->where($array_cond)->page($page_no,$page_size)->select());
		$this->assign('page', $obj_page->show());
		$this->getSubNav(6,6,40);
		$this->display();
	}

	public function pageGoodsList(){
		$page_no = max(1,(int)$this->_get('p','',1));
		$page_size = 10;
		//TODO 此处需要做按照商品名称 商家编码等查询条件进行搜索
		$array_cond = 1;
		$count = D("TopGoodsInfo")->where($array_cond)->count();
		$obj_page = new Page($count, $page_size);
		$array_goods = D("TopGoodsInfo")->where($array_cond)->page($page_no,$page_size)->select();
		$obj_sku = D("TopGoodsSku");
		//对商品规格进行处理
		foreach($array_goods as $key => $goods){
			$int_pid = $goods["pid"];
			$ary_cond = array("pid"=>$int_pid);
			$array_sku = $obj_sku->where($ary_cond)->select();
			$array_goods[$key]["rows"] = 1;
			if(is_array($array_sku) && count($array_sku) > 1){
				$array_goods[$key]["rows"] = count($array_sku)+1;
			}
			$array_goods[$key]["sku_info"] = $array_sku;
		}
		$this->getSubNav(6,6,70);
		$this->assign("datalist",$array_goods);
		$this->assign("page",$obj_page->show());
		$this->display();
	}

	public function goodsDetailPage(){
		$this->getSubNav(6,6,70);
		//必选参数pid验证
		if(!isset($_GET["pid"]) || !is_numeric($_GET["pid"])){
			$this->error("URL参数不合法，缺少Pid！",U('Admin/Fenxiao/pageGoodsList'),3);
		}
		//如果客户指定了要刷新数据，则重新下载商品铺货记录数据
		if(isset($_GET["refresh"]) && 'yes' == $_GET["refresh"]){
			$obj_model = D("TopDistributorItems");
			$total_result = $obj_model->getPageInfo($_GET["pid"]);
			$int_page_no = 1;
			$int_page_size = 50;
			$pages = ceil($total_result/$int_page_size);
			//处理数据下载，如果一个商品的铺货数据过多，可能需要的时间较长
			//这里将最常的执行时间设置为2分钟，预计可以下载2000条铺货记录
			$int_time_limit = 120;
			if(isset($_GET["time_limit"]) && is_numeric($_GET["time_limit"])){
				$int_time_limit = $_GET["time_limit"];
			}
			set_time_limit($int_time_limit);
			for($i=$int_page_no;$i<=$pages;$i++){
				$result = $obj_model->download($_GET["pid"]);
			}
		}
		$this->assign("int_pid",$_GET["pid"]);
		$this->display();
	}

    ##########################################################################
    /**
     * 采购单列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-02
     *
     */

    public function pageOrder() {
        $this->getSubNav(6, 6, 50);

        $fxOrder = D('TopPurchaseOrder');
        //$where = array();
        $count = $fxOrder->count();
        $Page = new Page($count, 15);
        $data['list'] = $fxOrder->limit($Page->firstRow . ',' . $Page->listRows)->order(array('created' => 'desc'))->select();
        $data['page'] = $Page->show();
        $this->assign($data);
        $this->display();
    }

    /**
     * 采购单列表
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-02
     *
     */
    public function pageOrderDetail() {
        $fenxiao_id = $this->_post('oid');
        $fxOrder = D('TopSubpurchaseOrder');
        $where = array(
            'fenxiao_id' => $fenxiao_id
        );
        $data['list'] = $fxOrder->where($where)->order(array('created' => 'desc'))->select();
        $this->assign($data);
        $this->display();
    }

    /**
     * 对采购单进行分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function pageOrderAnalysis() {
        $this->getSubNav(6, 6, 60);
        $this->display();
    }

    /**
     * 乱价分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function doAnalysisPriceDaixiao() {
        $start = $this->_get('start', 'htmlspecialchars', 1);
        $fxOrder = D('TopPurchaseOrder');
        $count = $fxOrder->count();
        $result = $fxOrder->limit("$start,1")->select();
        if($result == false){
            $this->ajaxReturn(false);
        }else{
            $array_priceWrong = $fxOrder->priceWrong($result[0]['fenxiao_id']);
            if($array_priceWrong['result'] == 'true'){
                $fxOrder->data(array('analysis'=>'true'))->where(array('fenxiao_id'=>$result[0]['fenxiao_id']))->save();
            }
            $this->ajaxReturn(array('result'=>$start,'total'=>$count));
        }
    }

    /**
     * 经销乱价分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function doAnalysisPriceJingxiao(){

    }

    /**
     * 窜货分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function doAnalysisCuanhuo(){
        $start = $this->_get('start', 'htmlspecialchars', 1);
        $fxOrder = D('TopPurchaseOrder');
        $count = $fxOrder->count();
        $result = $fxOrder->limit("$start,1")->select();

        if($result == false){
            $this->ajaxReturn(false);
        }else{
            $array_priceWrong = $fxOrder->cuanHuo($result[0]['fenxiao_id']);
            if($array_cuanHuo['result'] == 'true'){
                $fxOrder->data(array('cuanhuo'=>'true'))->where(array('fenxiao_id'=>$result[0]['fenxiao_id']))->save();
            }
            $this->ajaxReturn(array('result'=>$start,'total'=>$count));
        }
    }

    /**
     * 代销乱价查询，查询的结果是缓存在数据库的，需要先分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function pagePriceDaixiao() {
        $this->getSubNav(6, 6, 60);

        $fxOrder = D('TopPurchaseOrder');

        $where = array('analysis' => 'true');

        $count = $fxOrder->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $fxOrder->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order(array('created' => 'desc'))->select();
        $data['page'] = $Page->show();
        $this->assign($data);
        $this->display();
    }

    /**
     * 代销乱价查询，查询的结果是缓存在数据库的，需要先分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function pagePriceJingxiao() {
        $this->getSubNav(6, 6, 60);

        $fxOrder = D('TopPurchaseOrder');

        $where = array('analysis' => 'true');

        $count = $fxOrder->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $fxOrder->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order(array('created' => 'desc'))->select();
        $data['page'] = $Page->show();
        $this->assign($data);
        $this->display();
    }

    /**
     * 代销乱价查询，查询的结果是缓存在数据库的，需要先分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function pagePriceCuanhuo() {
        $this->getSubNav(6, 6, 60);

        $fxOrder = D('TopPurchaseOrder');

        $where = array('analysis' => 'true');

        $count = $fxOrder->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $fxOrder->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order(array('created' => 'desc'))->select();
        $data['page'] = $Page->show();
        $this->assign($data);
        $this->display();
    }

    /**
     * 代销乱价查询，查询的结果是缓存在数据库的，需要先分析
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-05-09
     */
    public function pagePriceChart() {
        $this->getSubNav(6, 6, 60);

        $fxOrder = D('TopPurchaseOrder');

        $where = array('analysis' => 'true');

        $count = $fxOrder->where($where)->count();
        $Page = new Page($count, 15);
        $data['list'] = $fxOrder->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order(array('created' => 'desc'))->select();
        $data['page'] = $Page->show();
        $this->assign($data);
        $this->display();
    }

}