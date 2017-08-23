<?php

/**
 * 角色配置模型
 * @package Model
 * @version 7.0
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2012-12-12
 */
class RoleNodeModel extends GyfxModel {

    protected $_validate = array(
        array('module', 'require', '{%ROLE_NODE_MODULE_REQUIRE}'),
    );
    protected $_auto = array(
        array('status', '1'), // 新增的时候把r_status字段设置为1	
    );

    /**
     * 获取节点信息
     * @author  Terry<wanghui@guanyisoft.com>
     * @param int $id   节点ID
     * @date 2013-1-23
     * @return array 对应角色信息
     */
    public function getById($id) {
        $pk = $this->getPk();
        return $this->where($pk . ' = ' . $id)->find();
    }

}
