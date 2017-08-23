<?php

/**
 * 商品收藏
 * @author Wangguibin <wangguibin@guanyisoft.com>
 * @date 2015-07-13
 */

class ApiCollectModel extends GyfxModel{

	private $result; 	// 返回结果
    private $collect_obj; 	// 会员表

	// 自动执行
	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10010', 		// 收藏错误初始码
			'sub_msg' => '收藏错误', 	// 错误信息
			'status'  => false, 		// 返回状态 : false 错误,true 操作成功.
			'info'    => array(), 		// 正确返回信息
			);
		 $this->collect_obj = M('collect_goods', C('DB_PREFIX'), 'DB_CUSTOM');
	}
	
	//新增收藏
	public function InsertCollect($ary_post){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '添加收藏失败!';
        if(!empty($ary_post['g_id']) && isset($ary_post['g_id'])){
            if(!empty($ary_post['m_id'])){
                $ary_goods = M('goods',C('DB_PREFIX'),'DB_CUSTOM')->field('g_id')->where(array("g_id"=>$ary_post['g_id'],'g_status'=>1,'g_on_sale'=>1))->find();
                if(!empty($ary_goods) && is_array($ary_goods)){
                    $arr_collect = $this->collect_obj->where(array("m_id"=>$ary_post['m_id'],"g_id"=>$ary_goods['g_id']))->find();
                    if(!empty($arr_collect) && is_array($arr_collect)){
						$this->result['sub_msg'] = '该商品已加入收藏!';
                    }else{
                        $arr_res = $this->collect_obj->add(array('add_time'=>date('Y-m-d H:i:s'),"m_id"=>$ary_post['m_id'],"g_id"=>$ary_goods['g_id']));
                        if(false !== $arr_res){
							$this->result['status'] = true;
							$this->result['info']['status'] = true;
							$this->result['info']['g_id'] =  $ary_post['g_id'];
							$this->result['info']['m_id'] =  $ary_post['m_id'];
							$this->result['sub_msg'] = '加入收藏成功!';
                        }else{
							$this->result['sub_msg'] = '加入失败!';
                        }
                    }
                }else{
					$this->result['sub_msg'] = '该商品不存在或者已经下架!';
                }
            }else{
                $this->result['sub_msg'] = '请选择会员ID!';
            } 
        }else{
            $this->result['sub_msg'] = '添加收藏失败,商品ID 不存在!';
        }
        return $this->result;
	}

	//删除收藏
	public function deleteCollect($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '删除收藏失败!';
		if(!isset($params['m_id'])){
			$this->result['sub_msg'] = '删除收藏失败,会员ID不存在!';
		}
		if(!isset($params['g_id'])){
			$this->result['sub_msg'] = '删除收藏失败,商品ID不存在!';
		}
		$arr_collect = $this->collect_obj->where(array("m_id"=>$params['m_id'],"g_id"=>$params['g_id']))->find();
		if(!empty($arr_collect)){
			 $ary_res = $this->collect_obj->where(array("m_id"=>$params['m_id'],"g_id"=>$params['g_id']))->delete();
			if(false !== $ary_res){
			  $this->result['sub_msg'] = '删除商品收藏成功!';
			  $this->result['status'] = true;
			  $this->result['info']['status'] = true;
			  $this->result['info']['m_id'] = $params['m_id'];
			  $this->result['info']['g_id'] = $params['g_id'];
			}else{
				$this->result['sub_msg'] = '删除收藏失败！';
			}
		}else{
			$this->result['sub_msg'] = '删除收藏失败,商品未加入收藏夹或已删除!';
		}
        return $this->result;
	}	
	
	//收藏列表
	public function getCollectList($params){
		$this->result['status'] = false;
		$this->result['code'] = '10012';
		$this->result['sub_msg'] = '删除收藏失败!';
        $m_id = $params['m_id'];
		if(!isset($m_id)){
			$this->result['sub_msg'] = '会员ID不存在!';
			return $this->result;
		}
        $where = array();			
        $where[C("DB_PREFIX").'collect_goods.m_id'] = $m_id;
		$where[C("DB_PREFIX").'goods.g_id'] = array('neq','');
		$total_results = $this->collect_obj
                ->join(" ".C("DB_PREFIX")."goods_info ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."collect_goods.g_id")
                ->join(" ".C("DB_PREFIX")."goods ON (".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."goods.g_id)")
                ->where($where)->count();
		$page_start = ($params['page']-1)*$params['pagesize'];
        $ary_goods = $this->collect_obj
                ->field(" ".C("DB_PREFIX")."collect_goods.m_id,".C("DB_PREFIX")."collect_goods.add_time,".C("DB_PREFIX")."goods_info.g_name,fx_goods_info.g_price,fx_goods.g_sn,fx_goods.g_id,fx_goods_info.g_picture")
                ->join(" ".C("DB_PREFIX")."goods_info ON (".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."collect_goods.g_id)")
                ->join(" ".C("DB_PREFIX")."goods ON ".C("DB_PREFIX")."goods_info.g_id=".C("DB_PREFIX")."goods.g_id")
                ->where($where)->limit($page_start,$params['pagesize'])->order('add_time desc')->select();
        if(!empty($ary_goods) && is_array($ary_goods)){
            foreach($ary_goods as $key=>&$val){
                $val['nums'] = $this->collect_obj->where(array("g_id"=>$val['g_id']))->count();
				if($_SESSION['OSS']['GY_QN_ON'] == '1'){	
					$val['g_picture'] = D('QnPic')->picToQn($val['g_picture']);
				}else{
					$val['g_picture'] = '/'.ltrim($val['g_picture'],'/');
				}
            }
        }
		$this->result['status'] = true;
		$this->result['info']['lists']['list'] = $ary_goods;
		$this->result['info']['total_results'] = $total_results;
        return $this->result;
	}


}