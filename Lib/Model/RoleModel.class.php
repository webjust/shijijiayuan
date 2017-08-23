<?php

/**
 * 角色配置模型
 * @package Model
 * @version 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2012-12-12
 */
class RoleModel extends GyfxModel {

    protected $_validate = array(
        array('name', 'require', '{%ROLE_NAME_REQUIRE}'),
    );
    
    protected $_auto = array(
        array('status', '1'), // 新增的时候把r_status字段设置为1	
    );

    /**
     * 获取角色信息
     * @author  Terry<wanghui@guanyisoft.com>
     * @param int $id   角色ID
     * @date 2013-1-23
     * @return array 对应角色信息
     */
    public function getById($id) {
        $pk = $this->getPk();
        return $this->where($pk . ' = ' . $id)->find();
    }

}
