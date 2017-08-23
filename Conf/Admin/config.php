<?php

return array(
    //跳转桥页模版
    'TMPL_ACTION_ERROR' => 'Admin:Common:jump',
    'TMPL_ACTION_SUCCESS' => 'Admin:Common:jump',
    'USER_AUTH_ON' => true,
    'USER_AUTH_TYPE' => 2, // 默认认证类型 1 登录认证 2 实时认证
    'ADMIN_AUTH_KEY' => 'admin',
    'AUTH_TYPE' => array('NODE', 'MODULE', 'ACTION'), //授权类型的常量
    'USER_AUTH_MODEL' => 'Admin', // 默认验证数据表模型
    'USER_AUTH_KEY' => 'Admin', // 用户认证SESSION标记
    'AUTH_PWD_ENCODER' => 'md5', // 用户认证密码加密方式
    'USER_AUTH_GATEWAY' => '/User/pageLogin', // 默认认证网关
    'NOT_AUTH_MODULE' => 'User,Index', // 默认无需认证模块
    'GUEST_AUTH_ON' => false, // 是否开启游客授权访问
    'GUEST_AUTH_ID' => 0, // 游客的用户ID
    'RBAC_ROLE_TABLE' => 'role',
    'RBAC_USER_TABLE' => 'admin',
    'RBAC_ACCESS_TABLE' => 'role_access',
    'RBAC_NODE_TABLE' => 'role_node',
    'TMPL_LOGO' => '/Public/Admin/images/logo.png',
	//导入管易分销的自定义标签库
    'TAGLIB_PRE_LOAD' => 'Gyfx' ,
);