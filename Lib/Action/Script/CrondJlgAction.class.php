<?php
 /**
 * 九龙港计划任务
 *
 * @package Action
 * @stage 7.6
 * @author Hcaijin <Huangcaijin@guanyisoft.com>
 * @date 2014-07-30
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class CrondJlgAction extends GyfxAction{

    /**
     * 会员自动同步新增
     */
    public function addMember(){
        $code = "addMember";
        $flg =false;
        $script_info = D('ScriptInfo')->where(array('code'=>'addMember'))->find();
        if(isset($script_info)){
            $where['m_update_time'] = array('gt',$script_info['run_time']);
        }
        $where['m_mobile'] = array('neq','');
		$where['_string'] = "ifnull(m_card_no,'') = ''";
        $arr_members = D('Members')->where($where)->select();
        //dump(D('Members')->getLastSql());exit();
        $count = count($arr_members);
        if(is_array($arr_members) && isset($arr_members)){
            try{
                $crond_obj = new Aeaicrypt();
                foreach($arr_members as $k => $member){
                    $res_data = $crond_obj->requestApi('addMem',$member);
                    //dump($res_data);exit();
                    if($res_data['status'] == 1){
                        $card_data = $res_data['data'];
                        $save_data = array('m_card_no'=>$card_data->card_num);
                        $save_where = array('m_id'=>$card_data->card_ref);
                        $save_res = D('Members')->where($save_where)->save($save_data);
                        //echo D('Members')->getLastSql();exit();
                        if($save_res){
                            $msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$res_data['msg'].'\r\n';
                            $this->logs($code,$msg);
                        }else{
                            $flg = true;
                            $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg'].'\r\n';
                            $this->logs($code,$msg);
                        }
                    }else{
                        $flg = true;
                        $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg'].'\r\n';
                        $this->logs($code,$msg);
                    }
                    if(($k+1)==$count){
                        if($flg){
                            D('ScriptInfo')->UpdateStatus($code,0);
                        }else{
                            D('ScriptInfo')->UpdateStatus($code,1);
                        }
                    }
                }
            }catch(exception $e){
                $code = "addMember";
                $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage().'\r\n';
                $this->logs($code,$msg);
            }
        }else{
            //上线以后去掉这个else
            $this->error('没有要新增的会员数据！');
            exit;
        }
    }

	/**
     * 会员自动同步新增
     */
    public function addMembers(){
        $code = "batchAdd";
        $flg =false;
        $script_info = D('ScriptInfo')->where(array('code'=>'batchAdd'))->find();
        if(isset($script_info)){
            $where['m_update_time'] = array('gt',$script_info['run_time']);
        }
        $where['m_mobile'] = array('neq','');
		$where['_string'] = "ifnull(m_card_no,'') = ''";
		while( true ){
			$arr_members = D('Members')->where($where)->limit(100)->select();
			if(count($arr_members)<1){
				break;
			}
			//dump($arr_members);exit();
			$count = count($arr_members);
			if(is_array($arr_members) && isset($arr_members)){
				try{
					$crond_obj = new Aeaicrypt();
					$res_data = $crond_obj->batchMemApi($arr_members);
					//dump($res_data);exit();
					if($res_data['status'] == 1){
						foreach($res_data['data'] as $k=>$card_data){
							$save_data = array('m_card_no'=>$card_data->card_num);
							$save_where = array('m_id'=>$card_data->card_ref);
							$save_res = D('Members')->where($save_where)->save($save_data);
							//echo D('Members')->getLastSql();exit();
							if($save_res){
								$msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$res_data['msg'].'\r\n';
								$this->logs($code,$msg);
							}else{
								$flg = true;
								$msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg'].'\r\n';
								$this->logs($code,$msg);
							}					
						}
					}else{
						$flg = true;
						$msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg'].'\r\n';
						$this->logs($code,$msg);
					}
					if(($k+1)==$count){
						if($flg){
							D('ScriptInfo')->UpdateStatus($code,0);
						}else{
							D('ScriptInfo')->UpdateStatus($code,1);
						}
					}
				}catch(exception $e){
					$code = "batchAdd";
					$msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage().'\r\n';
					$this->logs($code,$msg);
					break;
				}			
			}else{
				//上线以后去掉这个else
				$this->error('没有要新增的会员数据！');
				exit;
			}			
		}
    }
	
    /**
     * 会员自动同步更新接口
     */
    public function updateMember(){
        $code = "updateMember";
        $flg =false;
        $script_info = D('ScriptInfo')->where(array('code'=>'updateMember'))->find();
        if(isset($script_info)){
            $where['m_update_time'] = array('gt',$script_info['run_time']);
        }
        $where['m_mobile'] = array('neq','');
        $where['_string'] = "ifnull(m_card_no,'') != ''";
        $arr_members = D('Members')->where($where)->select();
        //dump(D('Members')->getLastSql());exit();
        $count = count($arr_members);
        if(is_array($arr_members) && isset($arr_members)){
            try{
                $crond_obj = new Aeaicrypt();
                foreach($arr_members as $k => $member){
                    $res_data = $crond_obj->requestApi('updateMem',$member);
                    //dump($res_data);exit();
                    if($res_data['status'] == 1){
                        $msg = date('Y-m-d H:i:s').'      更新会员卡成功：'.$res_data['msg'].'\r\n';
                        $this->logs($code,$msg);
                    }else{
                        $flg = true;
                        $msg = date('Y-m-d H:i:s').'      更新会员卡失败：'.$res_data['msg'].'\r\n';
                        $this->logs($code,$msg);
                    }
                    if(($k+1)==$count){
                        if($flg){
                            D('ScriptInfo')->UpdateStatus($code,0);
                        }else{
                            D('ScriptInfo')->UpdateStatus($code,1);
                        }
                    }
                }
            }catch(exception $e){
                $code = "updateMember";
                $msg = date('Y-m-d H:i:s').'      链接异常：'.$e->getMessage().'\r\n';
                $this->logs($code,$msg);
            }
        }else{
            //上线以后去掉这个else
            $this->error('没有要更新的会员数据！');
            exit;
        }
    }

    /**
	 * 记录错误日志
     * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
     * @date 2013-03-21
     * @param string $code 同步脚本编号
     * @param string $msg 错误信息
	 */
	function logs($code,$msg){
	   $log_dir = APP_PATH . 'Runtime/Apilog/';
	   if(!file_exists($log_dir)){
           mkdir($log_dir,0700);
       }
       $log_file = $log_dir . date('Ym') .$code . '.log';
       $fp = fopen($log_file, 'a+');
       fwrite($fp, $msg);
       fclose($fp);
	}

}

