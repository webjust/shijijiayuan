<?php
/**
 * 管理员模型
 * @package Model
 *
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-22
 */
class SystemModel extends GyfxModel{
    private $table_admin;

    public function _initialize() {
        parent::_initialize();
        $this->table_admin = M('Admin',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
    /**
     * 更新管理员信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-23
     * @param array $ary_result 更新数据
     * @param array $ary_where 对应更新条件
     * @return boolean 成功true 失败返回false 
     */
    public function saveUpdateAdmin($ary_result,$ary_where){
        if(!empty($ary_result) && is_array($ary_result)){
            $ary_res = $this->table_admin->where($ary_where)->data($ary_result)->save();
            if($ary_res){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    /**
     * 更新管理员信息
     * @author Terry <wanghui@guanyisoft.com>
     * @date 2013-1-23
     * @param array $ary_result 更新数据
     * @param array $ary_where 对应更新条件
     * @return boolean 成功true 失败返回false 
     */
    public function saveAddAdmin($ary_result){
        if(!empty($ary_result) && is_array($ary_result)){
            //echo "<pre>";print_r($ary_result);exit;
            $ary_res = $this->table_admin->add($ary_result);
            //echo $this->table_admin->getLastSql();exit;
            //echo "<pre>";var_dump($ary_res);exit;
            if($ary_res){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
