<?php
/**
 * 积分日志模型
 * @package Model
 * @version 7.1
 * @author czy<chenzongyao@guanyisoft.com>
 * @date 2012-04-17
 */
class PointLogModel extends GyfxModel{
    
    
    private $pointLog;

    /**
     * 私有属性，ajax参数返回
     * @author czy <chenzongyao@guanyisoft.com>
     * @date 2013-4-17
     */
    private $ary_return = array(
            'status' => 0,
            'msg' => '参数有误，请重试',
            'error_code' => 1001,
            'data' => '',
            'url' => ''
    );
    
    private static $ary_type  = array(
           '0'=>'交易获得',
           '1'=>'交易使用',
           '2'=>'新会员注册获得',
           '3'=>'会员评论',
           '4'=>'会员邀请好友',
           '5'=>'管理员修改'
    );

    public function _initialize() {
        parent::_initialize();
        $this->pointLog = M('PointLog',C('DB_PREFIX'),'DB_CUSTOM');
        
    }
    /**
     * 添加积分日志
     * @param array $ary_addr
     * @author czy <chenzongyao@guanyisoft.com>
     * @return array
     * @date 2013-4-17
     */
    public function addPointLog($ary_data, $m_id = 0) {
        if (empty($m_id)) {
            $m_id = $_SESSION['Members']['m_id'];
        }
        $this->pointLog->m_id = $m_id;
		if(isset($ary_data['o_id'])){
			$this->pointLog->o_id = $ary_data['o_id'];
		}
        $this->pointLog->type = $ary_data['type'];
        $this->pointLog->consume_point = !empty($ary_data['consume_point']) ? $ary_data['consume_point']: 0;
        $this->pointLog->reward_point = !empty($ary_data['reward_point']) ? $ary_data['reward_point']: 0;
        $this->pointLog->memo = isset(self::$ary_type[$ary_data['memo']])? self::$ary_type[$ary_data['memo']] :'';
        $this->pointLog->u_create = date('Y-m-d H:i:s');
        $int_ra_id = $this->pointLog->add();
        if ($int_ra_id > 0) {
            $this->ary_return['status'] = 1;
            $this->ary_return['data']['ra_id'] = $int_ra_id;
            $this->ary_return['msg'] = '添加积分日志成功';
        }
		else{
		    $this->ary_return['msg'] = '添加积分日志异常';
		}
        return $this->ary_return;
    }
	
	/**
     * 获得积分明细
     * @param array $ary_addr
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @return array
     * @date 2015-11-17
     */
    public function getPointLog($ary_data) {
        if (empty($ary_data['mid'])) {
            $m_id = $_SESSION['Members']['m_id'];
        }else{
			$m_id = $ary_data['mid'];
		}
		
		$where = array('m_id'=>$m_id);
		if($ary_data['c_start_time']!=''){
			 $where['u_create'][] = array('EGT',$ary_data['c_start_time']);
		}
		if($ary_data['c_end_time']!=''){
			 $where['u_create'][] = array('ELT',$ary_data['c_end_time']);
		}
		
		$ary_pointlog = D('PointLog')->where($where)->order('u_create desc')->page($ary_data['page'],$ary_data['pagesize'])->select();
		
        if (!empty($ary_pointlog)) {
            $this->ary_return['status'] = true;
			$this->ary_return['data'] = $ary_pointlog;
            $this->ary_return['msg'] = '';
        }else{
			$this->ary_return['status'] = false;
			$this->ary_return['data'] = '';
		    $this->ary_return['msg'] = '暂无记录';
		}
        return $this->ary_return;
    }
    
    
}