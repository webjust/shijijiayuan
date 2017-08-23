<?php
/**
 * 商品信息接口
 * @author Tom <helong@guanyisoft.com>
 * @date 2015-01-12
 */

Class ApiGoodsNewModel extends GyfxModel{

	private $result;
	protected $tableName = 'goods';

	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10501', 		// 商品错误初始码
			'sub_msg' => '商品信息错误', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
	}

	/**
	 * 获取商品列表
	 * @param  $params
	 * @example array(
	 * 		(int) cate_id       => 分类ID (选填)
	 * 		(int) brand_id      => 品牌ID (选填)
	 * 		(string) g_id       => 商品ID (选填)	(如果多个以,号隔开)
	 *		(string) keyword => 关键词 (选填)
	 * 		(string) price      => 价格排序 (0默认,无排序 ; 1 升序(ASC) ; 2 降序 (DESC)) (选填)
	 * 		(string) salenum    => 销量排序 (0 默认,无排序 ; 1 升序(ASC) ; 2 降序 (DESC)) (选填)
	 * 		(string) updatetime => 更新时间排序 (0 默认,无排序 ; 1 升序(ASC); 2 降序 (DESC)) (选填)
	 *      (int) 'page'         => 第几页 (选填 默认 0)
	 *      (int) 'pagesize'     => 每页显示条数 (选填 默认 1条)
	 * );
	 * @author Tom <helogn@guanyisoft.com>
	 * @date 2015-01-12
	 */
	public function goodsList($params){
		$width=$params['w'];
		$height=$params['h'];
		unset($params['w']);
		unset($params['h']);
		$where = array();
		$ary_g_id = array();
		// 分类搜素
		if(!empty($params['cate_id'])){
			$ary_cate = explode(',',$params['cate_id']);
			$ary_cate = array_filter($ary_cate);
			$ary_cate = D('ViewGoods')->getStringCateArray(implode(',',$ary_cate));
			$where[C('DB_PREFIX').'goods_category.gc_id'] = array('in',$ary_cate);
			// $ary_g_id = D('RelatedGoodsCategory')->where(array('gc_id'=>array('in',$ary_cate)))->getField('g_id',true);
		}
		// 商品ID集合
		if(!empty($params['g_id'])){
			$gids = explode(',',$params['g_id']);
			$ary_g_id = array_filter($gids);
			// $ary_g_id = array_merge($gids,$ary_g_id);
			// $ary_g_id = array_unique($ary_g_id);
		}
		if(!empty($ary_g_id)){
			$where[C('DB_PREFIX').'goods.g_id'] = array('in',$ary_g_id);
		}
		// 商品品牌ID
		if(!empty($params['brand_id'])){
			$ary_brand_id = explode(',',$params['brand_id']);
			$ary_brand_id = array_filter($ary_brand_id);
			$where[C('DB_PREFIX').'goods.gb_id'] = array('in',$ary_brand_id);
		}
		// 关键词搜索
		if(!empty($params['keyword'])){
			$where[C('DB_PREFIX').'goods_info.g_name|'.C('DB_PREFIX').'goods_info.g_keywords'] = array(
				'like','%'.$params['keyword'].'%'
				);
		}
		$order = '';
		// 销量排序
		if(!empty($params['salenum'])){
			$salenum = $params['salenum'] == 1 ? 'ASC' : 'DESC';
			$order .= C('DB_PREFIX').'goods_info.g_salenum ' . $salenum . ',';
			$order .= C('DB_PREFIX').'goods_info.g_id desc,';
		}
		// 更新时间排序
		if(!empty($params['updatetime'])){
			$updatetime = $params['updatetime'] == 1 ? 'ASC' : 'DESC';
			$order .= C('DB_PREFIX').'goods_info.g_update_time ' . $updatetime . ',';
			$order .= C('DB_PREFIX').'goods_info.g_id desc,';
		}
		// 价格排序
		if(!empty($params['price'])){
			$price = $params['price'] == 1 ? 'ASC' : 'DESC';
			$order .= 'pdt_sale_price ' . $price . ',';
			$order .= C('DB_PREFIX').'goods_info.g_id desc,';
		}
		if(!empty($order)){
			$order = substr($order,0,-1);
		}
		
		$where[C('DB_PREFIX').'goods.g_on_sale'] = 1;
		$where[C('DB_PREFIX').'goods.g_status'] = 1;
		$where[C('DB_PREFIX').'goods_products.pdt_status'] = 1;
		$where[C('DB_PREFIX').'goods_info.mobile_show'] = 1;
		$page_start = $params['page']*$params['pagesize'];
		// 查询字段
		$field ='min('.C('DB_PREFIX').'goods_products.pdt_sale_price) as pdt_sale_price,'. 
				// 'min('.C('DB_PREFIX').'goods_products.pdt_market_price) as pdt_market_price,'. 
				C('DB_PREFIX').'goods_products.pdt_id,'.
				// C('DB_PREFIX').'goods.gb_id,'.
				// C('DB_PREFIX').'goods.g_on_sale_time,'.
				// C('DB_PREFIX').'goods.g_on_sale,'.
				// C('DB_PREFIX').'goods.g_status,'.
				C('DB_PREFIX').'goods_info.g_id,'.
				C('DB_PREFIX').'goods_info.g_name,fx_goods_info.g_discount,'.
				C('DB_PREFIX').'goods_info.mobile_show,'.
				// C('DB_PREFIX').'goods_info.g_keywords,'.
				// C('DB_PREFIX').'goods_info.g_price,'.
				// C('DB_PREFIX').'goods_info.g_stock,'.
				C('DB_PREFIX').'goods_info.g_picture,'.
				C('DB_PREFIX').'goods_info.g_salenum,'.
				// C('DB_PREFIX').'goods_info.g_market_price,'.
				// C('DB_PREFIX').'goods_info.ma_price,'.
				// C('DB_PREFIX').'goods_info.mi_price,'.
				C('DB_PREFIX').'goods.g_update_time';
		// 数据库操作
		$data = D('GoodsBase')
				->field($field)
				->join(C('DB_PREFIX').'goods_info on '.C('DB_PREFIX').'goods_info.g_id = '.C('DB_PREFIX').'goods.g_id')
				->join(C('DB_PREFIX').'goods_products on '.C('DB_PREFIX').'goods_products.g_id = '.C('DB_PREFIX').'goods.g_id')
				->join(C('DB_PREFIX').'related_goods_category on '.C('DB_PREFIX').'related_goods_category.g_id = '.C('DB_PREFIX').'goods.g_id')
				->join(C('DB_PREFIX').'goods_category on '.C('DB_PREFIX').'goods_category.gc_id = '.C('DB_PREFIX').'related_goods_category.gc_id')
				->where($where)
				->group(C('DB_PREFIX').'goods_info.g_id')
				->order($order)
				->limit($page_start,$params['pagesize'])
				->select();
				
		// DEBUG
		// echo D('GoodsBase')->getDbError();
		//echo D('GoodsBase')->getLastSql();die;
		writeLog("商品列表信息sql信息\t". D('GoodsBase')->getLastSql(), 'fxGoodsListGet' . date('Y_m_d') . '.log');
		// 数据处理
		if(!empty($data)){
			foreach($data as $k=>$v){
				$ary_pdt = D('GoodsProducts')->where(array('pdt_id'=>$v['pdt_id']))->find();
				// $data[$k]['pdt_sale_price'] = isset($ary_pdt['pdt_sale_price']) && !empty($ary_pdt['pdt_sale_price']) ? $ary_pdt['pdt_sale_price'] : '';
				$data[$k]['pdt_market_price'] = isset($ary_pdt['pdt_market_price']) && !empty($ary_pdt['pdt_market_price']) ? $ary_pdt['pdt_market_price'] : '';
				// $data[$k]['g_picture'] = !empty($data[$k]['g_picture']) ? thumbImgUrl($data[$k]['g_picture']) : '';
				if($v['g_discount']>0 && $v['g_discount']<1){
					$data[$k]['pdt_sale_price'] = sprintf("%2f",$data[$k]['pdt_sale_price']*$v['g_discount']);
				}
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){//七牛图片显示
					$data[$k]['g_picture'] = D('QnPic')->picToQn($data[$k]['g_picture'],$width,$height);
				}
			}
		}
		$data = !is_array($data) ? array() : $data;
		$this->result['code'] = '10502';
		$this->result['sub_msg'] = '获取商品列表成功';
		$this->result['status'] = true;
		$this->result['info'] = $data;
		return $this->result;
	}
}