set names utf8;
INSERT INTO `fx_sys_config` (`sc_id`, `sc_module`, `sc_key`, `sc_value`, `sc_value_desc`, `sc_create_time`, `sc_update_time`) VALUES (161, 'GY_TEMPLATE_DEFAULT', 'GY_TEMPLATE_WAP_DEFAULT', 'default', '设置默认wap模板', '2015-01-07 15:41:15', '2015-01-05 17:51:58');
/*给会员表里添加会员头像字段 2015-1-15 by huhaiwei start*/
ALTER TABLE fx_members
ADD COLUMN m_head_img VARCHAR(255) NOT NULL DEFAULT '' COMMENT '会员头像';
/*给会员表里添加会员头像字段 2015-1-15 by huhaiwei end*/