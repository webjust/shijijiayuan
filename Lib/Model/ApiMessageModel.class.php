<?php

/**
 * 消息接口
 * @author Tom <helong@guanyisoft.com>
 * @date 2015-01-27
 */

class ApiMessageModel extends GyfxModel{

	private $result = array();
	protected $message;

	public function __construct() {
		parent::__construct();
		$this->result = array(
			'code'    => '10402',
			'sub_msg' => '消息接口错误',
			'status'  => false,
			'info'    => array(),
			);
		$this->message = D('StationLetters');
	}

	/**
	 * 获取消息数量接口
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2015-01-27
	 */
	public function getCount($params){
		$m_id = $params['m_id'];
		$where = array(
			C('DB_PREFIX').'related_station_letters.rsl_to_del_status' => 1,
			C('DB_PREFIX').'related_station_letters.rsl_to_m_id' => $m_id,
			C('DB_PREFIX').'related_station_letters.rsl_is_look' => 0,
			);
		$count = $this->message
				->join('inner join fx_related_station_letters on(fx_station_letters.sl_id=fx_related_station_letters.sl_id)')
				->where($where)
				->count();
		// echo $this->message->getLastSql();die;
		$data['num'] = empty($count) ? 0 : $count;
		$this->result['sub_msg'] = '获取条数成功';
		$this->result['status'] = true;
		$this->result['info'] = $data;
		return $this->result;
	}

	/**
	 * 获取消息列表
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2015-01-27
	 */
	public function getMessageList($params){
		$where = array();
		$where[C('DB_PREFIX').'related_station_letters.rsl_to_del_status'] = 1;
		if(!empty($params['is_look'])){
			$where[C('DB_PREFIX').'related_station_letters.rsl_is_look'] = $params['is_look'] == 1 ? 0 : 1;
		}
		if(!empty($params['m_id'])){
			$where[C('DB_PREFIX').'related_station_letters.rsl_to_m_id'] = $params['m_id'];
		}
		$page_start = $params['page']*$params['pagesize'];
		$data = $this->message
				->field('fx_station_letters.sl_id,sl_title,sl_content,ifnull(m_name,\'管理员\') from_name,rsl_is_look,sl_create_time')
				->join('inner join fx_related_station_letters on(fx_station_letters.sl_id=fx_related_station_letters.sl_id)')
				->join('left join fx_members on(fx_station_letters.sl_from_m_id=fx_members.m_id)')
				->where($where)
				->order('sl_create_time desc')
				->limit($page_start,$params['pagesize'])
				->select();
		$this->result['sub_msg'] = '获取消息列表成功';
		$this->result['status'] = true;
		$this->result['info'] = !empty($data) ? $data : array();
		return $this->result;
	}

	/**
	 * 阅读消息
	 * @author Tom <helong@guanyisoft.com>
	 * @date 2015-01-29
	 */
	public function messageRead($params){
		$where = array(
			'm_id' => $params['m_id'],
			'sl_id' => $params['sl_id']
			);
		$data = array(
			'rsl_is_look' => 1
			);
		$tag = D('RelatedStationLetters')->where($where)->save($data);
		if($tag === false){
			$this->result['sub_msg'] = '记录更新失败!';
		}else{
			$this->result['sub_msg'] = '记录更新成功';
			$this->result['status'] = true;
			$this->result['info'] = array('data'=>'SUCCESS');
		}
		return $this->result;
	}
}