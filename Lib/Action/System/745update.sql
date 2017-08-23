set names utf8;
Drop TABLE IF EXISTS `fx_members_fields`;
create table `fx_members_fields` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `field_name` varchar(60) not null comment '自定义属性名',
  `dis_order` tinyint(3) unsigned not null default '1' comment '排序 暂时不用',
  `is_display` tinyint(1) unsigned not null default '1' comment '是否显示 1 显示 ，0 不显示',
  `list_display` tinyint(1) unsigned not null default '0' comment '会员列表是否显示 1 显示 ，0 不显示',
  `type` tinyint(1) unsigned not null default '0' comment '是否自定义 1系统初始化，0是自定义',
  `is_need` tinyint(1) unsigned not null default '1' comment '是否必填 1 必填 ,0 可选',
  `is_register` tinyint(1) not null default '0' comment '是否为注册项 1为注册项 ,0 不为注册项',
  `is_edit` tinyint(1) not null default '1' comment '是否编辑 0不可编辑 1可以编辑',
  `fields_type` varchar(10) not null comment '自定义属性类型',
  `fields_content` text not null comment '类型内容',
  `is_status` int(11) DEFAULT '1' comment '记录状态，0为删除，1为有效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='会员属性项设置';

INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(1, '用户名', 1, 1, 0, 1, 1, 0, 'text', 'm_name');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(2, 'E-mail', 2, 1, 0, 1, 0, 0, 'text', 'm_email');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(3, '密码', 3, 1, 0, 1, 0, 0, 'text', 'm_password');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(4, '确认密码', 4, 1, 0, 1, 0, 0, 'text', 'm_password_1');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(5, '姓名', 5, 1, 0, 1, 0, 0, 'text', 'm_real_name');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(6, '邮编', 6, 1, 0, 1, 0, 0, 'text', 'm_zipcode');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(7, '联系地址', 7, 1, 0, 1, 0, 0, 'text', 'm_address_detail');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(8, '移动电话', 8, 1, 0, 1, 0, 0, 'text', 'm_mobile');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(9, '固定电话', 9, 1, 0, 1, 0, 0, 'text', 'm_telphone');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(10, '网站地址', 10, 1, 0, 1, 0, 0, 'text', 'm_website_url');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(11, '支付宝', 11, 1, 0, 1, 0, 0, 'text', 'm_alipay_name');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(12, '银行账户名', 12, 1, 0, 1, 0, 0, 'text', 'm_balance_name');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(13, 'QQ', 13, 1, 0, 1, 0, 0, 'text', 'm_qq');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(14, '省份/城市/区', 14, 1, 0, 1, 0, 1, 'select', '');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(15, '旺旺', 15, 1, 0, 1, 0, 0, 'text', 'm_wangwang');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(16, '保证金', 16, 1, 0, 1, 0, 0, 'text', 'm_security_deposit');
INSERT INTO `fx_members_fields` (`id`, `field_name`, `dis_order`, `is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES(17, '调整积分(增/减)', 17, 1, 0, 1, 0, 0, 'text', 'tz_point');
INSERT INTO `fx_members_fields` (`field_name`,`is_display`, `list_display`, `type`, `is_need`, `is_register`, `fields_type`, `fields_content`) VALUES('推荐人', 1, 0, 1, 0, 0, 'text', 'm_recommended');
Drop TABLE IF EXISTS `fx_members_fields_info`;
create table `fx_members_fields_info` (
  `u_id` mediumint(8) unsigned not null comment '用户Id',
  `field_id` int(10) unsigned not null comment '会员属性项Id',
  `content` text not null comment '内容',
  `status` tinyint(1) not null default '0' comment '会员属性项状态（0未审核 1 审核）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='会员属性项值';

Drop TABLE IF EXISTS `fx_area_jurisdiction`;
create table `fx_area_jurisdiction`(
	`cr_id` int(11) unsigned NOT NULL AUTO_INCREMENT comment '城市区域ID',
    `cr_name` varchar(100) not null default '' comment '城市区域名称',
    `s_id` int(11) not null default 0 comment '公司ID',
	key `cr_id` (`cr_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='子公司管辖区域';

INSERT INTO `fx_area_jurisdiction` (`cr_id`,`cr_name`, `s_id`) VALUES(110000,'北京市', 0),(440000,'广东省', 0),(450000,'广西壮族自治区', 0),(460000,'海南省', 0),(500000,'重庆市', 0),(510000,'四川省', 0),(520000,'贵州省', 0),(530000,'云南省', 0),(540000,'西藏自治区', 0),(610000,'陕西省', 0),(620000,'甘肃省', 0),(630000,'青海省', 0),(640000,'宁夏回族自治区', 0),(650000,'新疆维吾尔自治区', 0),(710000,'台湾省', 0),(810000,'香港特别行政区', 0),(430000,'湖南省', 0),(420000,'湖北省', 0),(120000,'天津市', 0),(130000,'河北省', 0),(140000,'山西省', 0),(150000,'内蒙古自治区', 0),(210000,'辽宁省', 0),(220000,'吉林省', 0),(230000,'黑龙江省', 0),(310000,'上海市', 0),(320000,'江苏省', 0),(330000,'浙江省', 0),(340000,'安徽省', 0),(350000,'福建省', 0),(360000,'江西省', 0),(370000,'山东省', 0),(410000,'河南省', 0),(820000,'澳门特别行政区', 0);

alter table `fx_members` add column `m_subcompany_id`  int(11) NULL DEFAULT NULL comment '子公司ID';

drop table if exists `fx_groupbuy`;
create table `fx_groupbuy` (
  `gp_id` int(11) NOT NULL AUTO_INCREMENT comment '团购ID',
  `gp_title` varchar(255) not null comment '团购标题',
  `g_id` int(11) not null default '0' comment '商品ID',
  `gp_picture` varchar(100) not null comment '团购图片',
  `related_area_id` int(11) not null default '0' comment '关联区域ID',
  `gp_start_time` datetime not null comment '活动开始时间',
  `gp_end_time` datetime not null comment '活动结束时间',
  `gp_deposit_price` decimal(10,3) not null default '0.000' comment '定金',
  `gp_per_number` int(10) not null default '0' comment '每人限购数量',
  `gp_overdue_start_time` datetime not null comment '补交余款开始时间',
  `gp_overdue_end_time` datetime not null comment '补交余款结束时间',
  `gp_send_point` int(11) not null default '0' comment '赠送积分数',
  `related_price_id` int(11) not null default '0' comment '价格关联表',
  `gp_number` int(10) not null default '0' comment '限购数量',
  `gp_pre_number` int(10) not null default '0' comment '虚拟购买数量',
  `gp_desc` text not null comment '团购介绍',
  `gp_goodshow_status` tinyint(1) not null default '0' comment '是否显示商品详情',
  `gp_now_number` int(10) not null default '0' comment '已团购数量',
  `is_active` tinyint(1) not null default '1' comment '状态',
  `gp_order` int(10) not null default '0' comment '显示次序',
  `deleted` tinyint(1) not null default '0' comment '是否删除',
  `is_deposit` tinyint(1) DEFAULT '0' comment '是否启用担保金',
  PRIMARY KEY (`gp_id`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`gp_order`),
  KEY `gp_start_time` (`gp_start_time`),
  KEY `gp_end_time` (`gp_end_time`),
  KEY `g_id` (`g_id`),
  KEY `related_area_id` (`related_area_id`),
  KEY `gp_price_id` (`related_price_id`),
  KEY `gp_goodshow_status` (`gp_goodshow_status`),
  KEY `gp_overdue_start_time` (`gp_overdue_start_time`),
  KEY `gp_overdue_end_time` (`gp_overdue_end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 comment='商品团购表';

drop table if exists `fx_groupbuy_log`;
create table `fx_groupbuy_log` (
  `gpl_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_id` int(10) not null comment '订单ID',
  `gp_id` int(10) not null default '0' comment '团购ID',
  `m_id` int(10) not null default '0' comment '会员ID',
  `g_id` int(10) not null comment '商品ID',
  `num` int(4) not null default '0' comment '购买数量。取值范围:大于零的整数',
  `gpl_remark` varchar(200) not null comment '备注',
  PRIMARY KEY (`gpl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='团购日志表';

drop table if exists `fx_groupbuy_set`;
create table `fx_groupbuy_set` (
  `gs_id` int(11) NOT NULL AUTO_INCREMENT,
  `gs_related_city` text not null comment '关联热销城市',
  `gs_related_price` text not null comment '价格区间',
  `gs_timeshow_status` tinyint(4) not null default '1' comment '是否显示剩余时间（1:显示，0不显示）',
  `gs_create_time` datetime DEFAULT NULL comment '创建时间',
  `gs_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '修改时间',
  PRIMARY KEY (`gs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='团购设置表';

drop table if exists `fx_related_groupbuy_area`;
create table `fx_related_groupbuy_area` (
  `related_area_id` int(11) NOT NULL AUTO_INCREMENT,
  `gp_id` int(11) not null default '0' comment '关联团购ID',
  `cr_id` int(11) not null default '0' comment '关联区域ID',
  PRIMARY KEY (`related_area_id`),
  KEY `gp_id` (`gp_id`),
  KEY `cr_id` (`cr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='团购关联区域表';

drop table if exists `fx_related_groupbuy_price`;
create table `fx_related_groupbuy_price` (
  `related_price_id` int(11) NOT NULL AUTO_INCREMENT,
  `gp_id` int(11) not null comment '团购ID',
  `rgp_price` decimal(10,3) not null comment '享受价格',
  `rgp_num` int(10) not null default '0' comment '数量达到',
  PRIMARY KEY (`related_price_id`),
  KEY `gp_id` (`gp_id`),
  KEY `rgp_num` (`rgp_num`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='团购价格关联表';

alter table `fx_members` add column `is_proxy`  tinyint(1) not null default 0 comment '是否申请代理商（0为否，1 为是）';

drop table if exists `fx_related_promotion_goods_group`;
create table `fx_related_promotion_goods_group` (
	`rpmg_id` int(11) NOT NULL AUTO_INCREMENT,
	`pmn_id`  int(11) not null comment '促销ID',
	`gg_id`   int(11) not null comment '商品分组ID',
	PRIMARY KEY (`rpmg_id`),
	KEY `pmn_id` (`pmn_id`),
	KEY `gg_id` (`gg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='促销与商品分组关联表';

alter table `fx_groupbuy` drop column related_price_id ;
alter table `fx_groupbuy` drop column related_area_id ;
alter table `fx_groupbuy` add column `gp_create_time`  datetime not null comment '团购创建时间';
alter table `fx_groupbuy` add column `gp_update_time`  timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '团购更新时间';

alter table `fx_groupbuy` add column `gp_price`  decimal(10,3) NULL DEFAULT 0.000 comment '团购初始价';

alter table `fx_orders_items` modify column `oi_type`  int(4) not null default 0 comment '商品类型，5:团购商品，4:自由组合商品,3组合商品，2赠品， 1积分商品，0普通商品' after `oi_thd_sale_price`;

delete from fx_source_platform where sp_code='QQ';
delete from fx_source_platform where sp_code='Sina';
delete from fx_source_platform where sp_code='RenRen';
insert into fx_source_platform set sp_code='QQ',sp_name='腾讯',sp_default='1',sp_stauts='1',sp_create_time=now();
insert into fx_source_platform set sp_code='Sina',sp_name='新浪微博',sp_default='1',sp_stauts='1',sp_create_time=now();
insert into fx_source_platform set sp_code='RenRen',sp_name='人人网',sp_default='1',sp_stauts='1',sp_create_time=now();

TRUNCATE TABLE fx_area_jurisdiction;
INSERT INTO `fx_area_jurisdiction` (`cr_id`,`cr_name`, `s_id`) VALUES(110000,'北京市', 0),(440000,'广东省', 0),(450000,'广西壮族自治区', 0),(460000,'海南省', 0),(500000,'重庆市', 0),(510000,'四川省', 0),(520000,'贵州省', 0),(530000,'云南省', 0),(540000,'西藏自治区', 0),(610000,'陕西省', 0),(620000,'甘肃省', 0),(630000,'青海省', 0),(640000,'宁夏回族自治区', 0),(650000,'新疆维吾尔自治区', 0),(710000,'台湾省', 0),(810000,'香港特别行政区', 0),(430000,'湖南省', 0),(420000,'湖北省', 0),(120000,'天津市', 0),(130000,'河北省', 0),(140000,'山西省', 0),(150000,'内蒙古自治区', 0),(210000,'辽宁省', 0),(220000,'吉林省', 0),(230000,'黑龙江省', 0),(310000,'上海市', 0),(320000,'江苏省', 0),(330000,'浙江省', 0),(340000,'安徽省', 0),(350000,'福建省', 0),(360000,'江西省', 0),(370000,'山东省', 0),(410000,'河南省', 0),(820000,'澳门特别行政区', 0);

alter table `fx_members` CHANGE `m_verify` `m_verify` TINYINT( 1 ) not null default '0' comment '是否已经审核，0为未审核，1为审核中，2为审核通过，3为审核未通过,4待审核';

Drop TABLE IF EXISTS `fx_members_verify`;
create table `fx_members_verify` (
  `m_id` int(11) unsigned NOT NULL AUTO_INCREMENT comment '会员ID',
  `m_name` varchar(50) not null default '' comment '会员名',
  `m_password` varchar(32) not null default '' comment '会员密码',
  `m_real_name` varchar(25) not null default '' comment '姓名',
  `m_sex` tinyint(1) not null default '2' comment '性别，0为女，1为男，2为保密',
  `cr_id` int(11) unsigned not null default '0' comment '联系地址表里面的最终一级的ID',
  `m_address_detail` varchar(255) not null default '' comment '联系地址详细',
  `m_birthday` date not null default '0000-00-00' comment '生日',
  `m_zipcode` varchar(10) not null default '' comment '邮编',
  `m_mobile` varchar(20) not null default '' comment '手机',
  `m_telphone` varchar(20) not null default '' comment '固定电话',
  `m_status` tinyint(1) not null default '0' comment '会员状态（0为停用1 为启用）',
  `m_email` varchar(255) not null default '' comment 'email',
  `ml_id` int(11) unsigned not null default '0' comment '会员等级id',
  `mo_id` int(11) unsigned not null default '0' comment '在线客服id',
  `m_wangwang` varchar(255) not null default '' comment '旺旺',
  `m_qq` varchar(20) not null default '' comment 'QQ',
  `m_website_url` varchar(255) not null default '' comment '网站地址',
  `m_verify` tinyint(1) not null default '0' comment '是否已经审核，0为未审核，1为审核中，2为审核通过，3为审核未通过',
  `m_balance` decimal(10,3) not null default '0.000' comment '账户余额',
  `m_all_cost` decimal(10,3) not null default '0.000' comment '账户消费总金额',
  `total_point` int(10) not null default '0' comment '当前积分',
  `freeze_point` int(10) not null default '0' comment '当前冻结积分',
  `m_create_time` timestamp not null default '0000-00-00 00:00:00' comment '记录创建时间',
  `m_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '记录最后更新时间',
  `thd_guid` varchar(100) not null default '' comment '第三方用户唯一标识(包含erp)',
  `m_recommended` varchar(50) DEFAULT NULL comment '推荐人',
  `m_security_deposit` decimal(10,3) not null default '0.000' comment '会员的保证金就是押金',
  `m_alipay_name` varchar(50) not null default '' comment '支付宝账户',
  `m_balance_name` varchar(20) not null default '' comment '支付宝账户或银行账户',
  `m_subcompany_id` int(11) DEFAULT NULL comment '子公司ID',
  PRIMARY KEY (`m_id`),
  UNIQUE KEY `m_name` (`m_name`),
  KEY `a_id` (`cr_id`),
  KEY `m_status` (`m_status`),
  KEY `m_create_time` (`m_create_time`),
  KEY `m_update_time` (`m_update_time`),
  KEY `m_sex` (`m_sex`),
  KEY `m_email` (`m_email`),
  KEY `ml_id` (`ml_id`),
  KEY `m_verify` (`m_verify`),
  KEY `thd_guid` (`thd_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='会员系统默认属性待审核表';

alter table `fx_orders` add column `o_shipping_remarks` tinyint(1) not null default '0' comment '1.发货先发，缺货后发，2.等缺货一起发，3.修改订单，删除缺货商品';

alter table `fx_orders` add column `o_thd_sn`  varchar(255) character set utf8 collate utf8_general_ci not null default '' comment '第三方支付订单sn' after `o_sn`;

insert into `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`erp_payment_id`,`pc_status`,`pc_memo`,`pc_trd`) values (7,'银联在线','chinapay','CHINAPAY',0,1,'支付银联在线',1);


alter table `fx_orders` add column `o_promotion_price`  decimal(10,3) not null default 0.000 comment '促销优惠金额' after `o_shipping_remarks`;

alter table `fx_subcompany` add column `s_sort` int(11) not null default '10' comment '子公司排序，数值越小越靠前' after `s_modify_time`;

alter table `fx_balance_info` add column `single_type`  tinyint(1) NULL DEFAULT '1' comment '制单类型：1.系统管理员制单，2.用户制单';

alter table `fx_members` add column `m_last_login_time` timestamp not null default '0000-00-00 00:00:00' comment '记录最后登入时间' after `m_create_time`;

alter table `fx_invoice_config`
modify column `invoice_type`  varchar(11) character set utf8 collate utf8_general_ci not null default '0' comment '发票类型 0:不选 1:普通发票 2:增值税发票 格式:普通发票,增值税发票' after `is_invoice`,
modify column `invoice_head`  varchar(11) character set utf8 collate utf8_general_ci not null default '0' comment '发票抬头 0:不选 1:个人 2:单位 格式：个人,单位' after `invoice_type`;

alter table `fx_invoice_collect`
modify column `invoice_type`  int(11) not null default 1 comment '发票类型 1普通发票 2增值税发票' after `is_invoice`,
modify column `invoice_head`  varchar(50) character set utf8 collate utf8_general_ci not null default '0' comment '发票抬头 1个人 2单位 ' after `invoice_type`,
modify column `invoice_name`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment '公司名称' after `invoice_head`;

alter table fx_orders add invoice_name varchar(50) not null default '' comment'公司名称';

drop table if exists `fx_log_operation`;
create table `fx_log_operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(80) DEFAULT NULL comment '操作人员',
  `action` varchar(200) DEFAULT NULL comment '动作',
  `content` text comment '内容',
  `datetime` datetime DEFAULT NULL comment '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 comment='日志操作记录';

alter table fx_feedback add msg_address varchar(180) not null default '' comment'地址';

alter table `fx_orders` modify column `o_discount` decimal(10,3) not null default '0.000' comment '购物车优惠金额';

alter table `fx_groupbuy_log` modify column `o_id`  bigint(20) not null comment '订单ID' after `gpl_id`;

delete from `fx_sys_config` where `sc_module` = 'GY_CAHE';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='Memcache_stat',`sc_value`='0',sc_value_desc='是否开启Memcahe缓存 0 不开启 1开启';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='Memcache_host',`sc_value`='127.0.0.1',sc_value_desc='Memcahe服务器IP地址';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='Memcache_port',`sc_value`='11211',sc_value_desc='Memcahe服务器端口';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='Memcache_time',`sc_value`='100',sc_value_desc='Memcahe缓存有效时间';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='File_cahe_stat',`sc_value`='0',sc_value_desc='是否开启文件缓存 0 不开启 1开启';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='File_cahe_name',`sc_value`='test',sc_value_desc='缓存目录文件夹名';
insert into fx_sys_config set `sc_module`='GY_CAHE',`sc_key`='File_cahe_time',`sc_value`='86400',sc_value_desc='文件缓存有效时间';

delete from `fx_sys_config` where `sc_module` = 'GET_COUPON';
insert into fx_sys_config set `sc_module`='GET_COUPON',`sc_key`='GET_COUPON_SET',`sc_value`='0',sc_value_desc='促销优惠券获取 0付款后 1发货后 2确认收货后 3订单完成后';

alter table `fx_members_level` add column `ml_up_fee`  decimal(10,3) not null default '0.000' comment '晋升要求';
alter table `fx_members_level` add column `ml_rebate`  decimal(8,3) not null default '0.000' comment '返点比例';
alter table `fx_members_level` add column `ml_free_shipping`  tinyint(1) not null default '0' comment '是否包邮，0为否，1为是';

alter table `fx_orders_items` add column `ml_rebate`  decimal(8,3) not null default '0.000' comment '返点比例';

alter table `fx_orders_items` add column `ml_discount`  decimal(8,3) not null default '0.000' comment '等级折扣';

drop table if exists `fx_top_access_info`;
create table `fx_top_access_info`(
	`top_user_id` int(11) not null default 0 comment '淘宝用户ID',
	`top_user_nick` varchar(255) not null default 0 comment '淘宝用户nick',
	`top_access_token` varchar(255) not null default '' comment '访问token',
	`top_expires_in` timestamp not null default '0000-00-00 00:00:00' comment 'token过期时间',
	`top_refresh_token` varchar(255) not null default '' comment '刷新访问token使用的token',
	`top_refresh_expires_in` timestamp not null default '0000-00-00 00:00:00' comment '刷新token的token过期时间',
	`top_w1_expires_in` int(11) not null default 0 comment 'w1权限字段授权过期时间，单位是秒',
	`top_w2_expires_in` int(11) not null default 0 comment 'w2权限字段授权过期时间，单位是秒',
	`top_r1_expires_in` int(11) not null default 0 comment 'r1权限字段授权过期时间，单位是秒',
	`top_r2_expires_in` int(11) not null default 0 comment 'r2权限字段授权过期时间，单位是秒',
	`token_type` varchar(255) not null default '' comment '授权类型，token_type',
	`top_oauth_time` timestamp not null default '0000-00-00 00:00:00' comment '授权时间',
	primary key `top_user_id` (`top_user_id`),
	unique key `top_user_nick` (`top_user_nick`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 comment='淘宝用户授权信息表';

alter table `fx_thd_goods` add column `ts_id` int(11) not null default '0' comment '来源于第三方店铺ID';

DROP TABLE IF EXISTS `fx_menus`;
CREATE TABLE `fx_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `name` varchar(255) NOT NULL COMMENT '菜单名称',
  `type` tinyint(2) NOT NULL DEFAULT '3' COMMENT '菜单属于平台类型：1.B2B,2.B2C,3.B2B2C',
  `group` varchar(50) NOT NULL COMMENT '菜单分组',
  `toporder` int(5) NOT NULL DEFAULT '0' COMMENT '一级菜单排序',
  `suborder` int(5) NOT NULL DEFAULT '0' COMMENT '二级菜单排序',
  `threeorder` int(5) DEFAULT '0' COMMENT '三级菜单排序',
  `url` text COMMENT '菜单链接',
  `fid` int(11) NOT NULL DEFAULT '0' COMMENT '父ID',
  `mstatus` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示：0.否，1.是',
  `sn` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单唯一标识',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sn` (`sn`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9370 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of fx_menus
-- ----------------------------
INSERT INTO fx_menus VALUES ('9370', '选购中心', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Products/index', '0', '1', 'UNAV1_0');
INSERT INTO fx_menus VALUES ('9371', '快速订货', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Products/pageList', '9370', '1', 'UMENU0_10');
INSERT INTO fx_menus VALUES ('9372', '购物车', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Cart/pageList', '9370', '1', 'UMENU0_30');
INSERT INTO fx_menus VALUES ('9373', '我的订单', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Orders/pageList', '9370', '1', 'UMENU0_40');
INSERT INTO fx_menus VALUES ('9374', '售后列表', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Aftersale/pageList', '9370', '1', 'UMENU0_50');
INSERT INTO fx_menus VALUES ('9375', '收藏列表', '3', 'Ucenter', '-1', '0', '0', '/Ucenter/Collect/pageList', '9370', '1', 'UMENU0_60');
INSERT INTO fx_menus VALUES ('9376', '第三方平台', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Trdorders/index', '0', '1', 'UNAV1_1');
INSERT INTO fx_menus VALUES ('9377', '店铺授权', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Distribution/pageShops', '9376', '0', 'UMENU1_10');
INSERT INTO fx_menus VALUES ('9378', '淘宝铺货', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Distribution/pageList', '9376', '0', 'UMENU1_20');
INSERT INTO fx_menus VALUES ('9379', '淘宝库存同步', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Distribution/pageUpdate', '9376', '1', 'UMENU1_30');
INSERT INTO fx_menus VALUES ('9380', '淘宝订单下载', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Trdorders/pageTaobao', '9376', '1', 'UMENU1_40');
INSERT INTO fx_menus VALUES ('9381', '拍拍订单下载', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Trdorders/pagePaipai', '9376', '1', 'UMENU1_45');
INSERT INTO fx_menus VALUES ('9382', '订单下载', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Trdorders/thdOrderList', '9376', '0', 'UMENU1_70');
INSERT INTO fx_menus VALUES ('9383', '店铺授权', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Trdorders/yunerpShop', '9376', '1', 'UMENU1_80');
INSERT INTO fx_menus VALUES ('9384', '淘宝店铺授权', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Distribution/thdPageShops', '9376', '1', 'UMENU1_90');
INSERT INTO fx_menus VALUES ('9385', '淘宝商品铺货', '3', 'Ucenter', '0', '0', '0', '/Ucenter/Distribution/showGoodsList', '9376', '1', 'UMENU1_100');
INSERT INTO fx_menus VALUES ('9386', '淘宝库存上传', '3', 'Ucenter', '0', '0', '0', '/Ucenter/UploadStock/showItemsTop', '9376', '0', 'UMENU1_200');
INSERT INTO fx_menus VALUES ('9387', '推广销售', '3', 'Ucenter', '1', '0', '0', '/Ucenter/Promoting/index', '0', '1', 'UNAV1_2');
INSERT INTO fx_menus VALUES ('9388', '我要推广', '3', 'Ucenter', '1', '0', '0', '/Ucenter/Promoting/userSpread', '9387', '1', 'UMENU2_10');
INSERT INTO fx_menus VALUES ('9389', '我的返利', '3', 'Ucenter', '1', '0', '0', '/Ucenter/Promoting/payBack', '9387', '1', 'UMENU2_20');
INSERT INTO fx_menus VALUES ('9390', '个人中心', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/index', '0', '1', 'UNAV1_3');
INSERT INTO fx_menus VALUES ('9391', '我的资料', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/pageProfile', '9390', '1', 'UMENU3_10');
INSERT INTO fx_menus VALUES ('9392', '修改密码', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/pageChangePass', '9390', '1', 'UMENU3_20');
INSERT INTO fx_menus VALUES ('9393', '收支明细', '3', 'Ucenter', '2', '0', '0', '/Ucenter/Financial/pageDepositList', '9390', '1', 'UMENU3_30');
INSERT INTO fx_menus VALUES ('9394', '我的收货地址', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/pageDeliver ', '9390', '1', 'UMENU3_70');
INSERT INTO fx_menus VALUES ('9395', '我的优惠券', '3', 'Ucenter', '2', '0', '0', '/Ucenter/MyCoupon/pageList ', '9390', '1', 'UMENU3_80');
INSERT INTO fx_menus VALUES ('9396', '我的积分', '3', 'Ucenter', '2', '0', '0', '/Ucenter/PointLog/pageList ', '9390', '1', 'UMENU3_85');
INSERT INTO fx_menus VALUES ('9397', '我的增值税发票', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/pageInvoice ', '9390', '1', 'UMENU3_90');
INSERT INTO fx_menus VALUES ('9398', '买家留言', '3', 'Ucenter', '2', '0', '0', '/Ucenter/My/feedBackList ', '9390', '1', 'UMENU3_100');
INSERT INTO fx_menus VALUES ('9399', '站点公告', '3', 'Ucenter', '3', '0', '0', '/Ucenter/Notice/index', '0', '1', 'UNAV1_4');
INSERT INTO fx_menus VALUES ('9400', '站内公告', '3', 'Ucenter', '3', '0', '0', '/Ucenter/Notice/pageList', '9399', '1', 'UMENU4_10');
INSERT INTO fx_menus VALUES ('9401', '站内信', '3', 'Ucenter', '3', '0', '0', '/Ucenter/Message/pageMailBox', '9399', '1', 'UMENU4_20');
INSERT INTO fx_menus VALUES ('9402', '违规记录公告', '3', 'Ucenter', '3', '0', '0', '/Ucenter/Announcement/pageList', '9399', '1', 'UMENU4_30');

alter table `fx_thd_goods` add column `thd_goods_status` tinyint(4) not null default '1' comment '淘宝商品状态(1:在架，2;下架(仓库))';

drop table if exists `fx_thd_upload_tmp`;
create table `fx_thd_upload_tmp` (
  `tut_id` int(11) NOT NULL AUTO_INCREMENT comment '主键，自增',
  `thd_indentify` tinyint(1) not null default '0' comment '第三方平台标识：1，淘宝；2：拍拍',
  `thd_shop_sid` int(11) not null default '0' comment '第三方店铺唯一标识,id',
  `thd_item_id` varchar(100) not null default '' comment '第三方商品g_sn，大B店铺内的商品g_sn',
  `thd_shop_item_iid` bigint(20) not null default '0' comment '第三方商品id，小b店铺内的商品ID',
  `last_upload_time` timestamp not null default '0000-00-00 00:00:00' comment '最后一次上传时间',
  `tut_create_time` timestamp not null default '0000-00-00 00:00:00' comment '记录创建时间',
  `tut_update_time` timestamp not null default '0000-00-00 00:00:00' comment '记录最后更新时间',
  PRIMARY KEY (`tut_id`),
  KEY `thd_indentify` (`thd_indentify`),
  KEY `thd_shop_sid` (`thd_shop_sid`),
  KEY `thd_item_id` (`thd_item_id`),
  KEY `thd_shop_item_iid` (`thd_shop_item_iid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='第三方店铺商品上传记录表';

drop table if exists `fx_thd_agents_pictures`;
create table `fx_thd_agents_pictures`(
	`atp_id` int(11) not null auto_increment comment '图片ID，系统自增；',
	`top_shop_code` int(11) not null default '0' comment '第三方店铺ID唯一标识',
	`top_picture_id` bigint(20) not null default 0 comment '淘宝图片空间ID',
	`top_picture_category_id` bigint(20) not null default 0 comment '淘宝图片空间分类ID',
	`top_picture_path` varchar(255) not null default '' comment '淘宝图片路径',
	`ecfx_picture_path` varchar(255) not null default '' comment '分销图片路径（与淘宝nick联合唯一）',
	`top_title` varchar(255) not null default '' comment '淘宝图片标题 为空即可',
	`top_sizes` int(11) not null default 0 comment '淘宝图片大小,单位是byte',
	`top_pixel` varchar(100) not null default '' comment '图片像素',
	`top_status` varchar(20) not null default '' comment '图片状态,unfroze代表没有被冻结，froze代表被冻结,pass代表排查通过',
	`top_deleted` varchar(20) not null default '' comment '图片是否删除的标记',
	`top_created` timestamp not null default '0000-00-00 00:00:00' comment '图片上传到淘宝图片空间的时间',
	`top_modified` timestamp not null default '0000-00-00 00:00:00' comment '图片在淘宝上的最后更新时间',
	primary key `atp_id` (`atp_id`),
	unique key `nick_img` (`top_shop_code`,`ecfx_picture_path`),
	key `top_status` (`top_status`),
	key `top_deleted` (`top_deleted`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='第三方店铺图片日志操作记录';

alter table `fx_goods` add column `thd_gid` bigint(20) not null default '0' comment '第三方商品ID';

alter table `fx_goods` add column `thd_indentify` tinyint(1) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;';

drop table if exists `fx_thd_shop_itemcats`;
create table `fx_thd_shop_itemcats` (
  `tsi_id` int(11) NOT NULL AUTO_INCREMENT comment '自增ID',
  `tsi_indentify` tinyint(1) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;',
  `ts_sid` int(11) not null default '0' comment '第三方店铺ID',
  `cid` int(11) not null default '0' comment '第三方店铺类目ID',
  `is_parent` varchar(5) not null default 'false' comment '是否是父分类',
  `name` varchar(50) not null default '' comment '第三方店铺分类名称',
  `parent_cid` int(11) not null default '0' comment '第三方店铺分类的父分类ID',
  `sort_order` int(11) not null default '0' comment '排序，默认值0',
  `cat_type` varchar(20) not null default '' comment '分类类型？？',
  `tsi_create_time` timestamp not null default '0000-00-00 00:00:00',
  `tsi_update_time` timestamp not null default '0000-00-00 00:00:00',
  PRIMARY KEY (`tsi_id`),
  KEY `tsi_indentify` (`tsi_indentify`),
  KEY `ts_sid` (`ts_sid`),
  KEY `cid` (`cid`),
  KEY `is_parent` (`is_parent`),
  KEY `parent_cid` (`parent_cid`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8 comment='第三方店铺分类表';

alter table `fx_goods_category` add column `thd_catid` varchar(20) DEFAULT '' comment '第三方分类ID';
alter table `fx_goods_category` add column `thd_cat_identity` varchar(20) DEFAULT '' comment '第三方分类标识，淘宝：taobao，拍拍：paipai，京东：360buy';
alter table `fx_goods_category` add column `thd_cat_info` text comment '第三方分类详细信息';

alter table `fx_goods_brand` add column `thd_indentify` tinyint(1) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;';
alter table `fx_goods_brand` add column `thd_gbid` int(11) not null default '0' comment '第三方品牌ID，淘宝是属性值ID';

drop table if exists `fx_top_itemprops`;
create table `fx_top_itemprops` (
  `cid` int(11) not null default '0' comment '商品所属类目ID',
  `is_input_prop` enum('true','false') not null default 'false' comment '在is_enum_prop是true的前提下，是否是卖家可以自行输入的属性（注：如果is_enum_prop返回false，该参数统一返回false）。可选值:true(是),false(否)。对于品牌和型号属性（包括子属性）：如果用户是C卖家，则可自定义属性；如果是B卖家，则不可自定义属性，而必须要授权的属性。',
  `pid` int(11) not null default '0' comment '属性 ID 例：品牌的PID=20000',
  `parent_pid` int(11) not null default '0' comment '上级属性ID',
  `parent_vid` int(11) not null default '0' comment '上级属性值ID',
  `name` varchar(255) not null default '' comment '属性名',
  `is_key_prop` enum('true','false') not null default 'false' comment '是否关键属性。可选值:true(是),false(否)',
  `is_sale_prop` enum('true','false') not null default 'false' comment '是否销售属性。可选值:true(是),false(否)',
  `is_color_prop` enum('true','false') not null default 'false' comment '是否颜色属性。可选值:true(是),false(否)',
  `is_enum_prop` enum('true','false') not null default 'false' comment '是否枚举属性。可选值:true(是),false(否)。如果返回true，属性值是下拉框选择输入，如果返回false，属性值是用户自行手工输入。',
  `is_item_prop` enum('true','false') not null default 'false' comment '是否商品属性。可选值:true(是),false(否)',
  `must` enum('true','false') not null default 'false' comment '发布产品或商品时是否为必选属性。可选值:true(是),false(否)',
  `multi` enum('true','false') not null default 'false' comment '发布产品或商品时是否可以多选。可选值:true(是),false(否)',
  `status` varchar(10) not null default '' comment '状态。可选值:normal(正常),deleted(删除)',
  `sort_order` int(11) not null default '0' comment '排列序号。取值范围:大于零的整排列序号。取值范围:大于零的整数',
  `child_template` varchar(255) not null default '' comment '子属性的模板（卖家自行输入属性时需要用到）',
  `is_allow_alias` enum('true','false') not null default 'false' comment '是否允许别名。可选值：true（是），false（否）',
  PRIMARY KEY (`cid`,`pid`),
  KEY `parent_pid` (`parent_pid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='商品属性';

drop table if exists `fx_top_itemprop_values`;
create table `fx_top_itemprop_values` (
  `cid` int(11) not null default '0' comment '类目ID',
  `pid` int(11) not null default '0' comment '属性 ID',
  `prop_name` varchar(255) not null default '' comment '属性名',
  `vid` int(11) not null default '0' comment '属性值ID',
  `name` varchar(255) not null default '' comment '属性值',
  `name_alias` varchar(255) not null default '' comment '属性值别名',
  `is_parent` enum('true','false') not null default 'false' comment '是否为父类目属性',
  `status` varchar(10) not null default '' comment '状态。可选值:normal(正常),deleted(删除)',
  `sort_order` int(11) not null default '0' comment '排列序号。取值范围:大于零的整数',
  PRIMARY KEY (`vid`,`pid`,`cid`),
  KEY `cid` (`cid`),
  KEY `pid` (`pid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='商品属性值';

alter table `fx_goods_spec` add column `thd_indentify` varchar(20) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;';
alter table `fx_goods_spec` add column `thd_gpid` int(11) not null default '0' comment '第三方属性ID';
alter table `fx_goods_spec` add column `top_cid` int(11) not null default '0' comment '淘宝商品分类ID，用于批量刷新属性所属类型';

alter table `fx_goods_spec_detail` add column `thd_indentify` tinyint(1) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;';
alter table `fx_goods_spec_detail` add column `thd_gpid` int(11) not null default '0' comment '第三方属性ID';
alter table `fx_goods_spec_detail` add column `thd_gpvid` int(11) not null default '0' comment '第三方属性值ID';

alter table `fx_goods_products` add column `thd_indentify` tinyint(1) not null default '0' comment '第三方对接平台:1,淘宝;2,拍拍;';
alter table `fx_goods_products` add column `thd_pdtid` bigint(11) not null default '0' comment '第三方SKU ID';

drop table if exists `fx_thd_top_items`;
create table `fx_thd_top_items` (
  `it_id` int(11) NOT NULL AUTO_INCREMENT comment '关联关系id，系统自增；',
  `g_id` bigint(11) not null default '0' comment '商品编号',
  `num_iid` bigint(11) not null default '0' comment '商品数字id',
  `pdt_id` bigint(11) not null default '0' comment '商品货号',
  `sku_id` bigint(11) not null default '0' comment 'SKU数字id',
  `it_nick` varchar(255) not null default '' comment '关联的淘宝账号',
  PRIMARY KEY (`it_id`),
  KEY `it_nick` (`it_nick`),
  KEY `it_sn_iid` (`g_id`,`num_iid`) USING BTREE,
  KEY `it_pdt_sku` (`pdt_id`,`sku_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 comment'分销商品与淘宝商品关联关系表';

drop table if exists `fx_free_collocation`;
create table `fx_free_collocation` (
  `fc_id` int(11) NOT NULL AUTO_INCREMENT,
  `fc_title` varchar(200) not null comment '搭配名称',
  `fc_related_good_id` varchar(200) not null comment '关联商品ID',
  `fc_create_time` datetime DEFAULT NULL comment '新增时间',
  `fc_update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP comment '修改时间',
  `fc_start_time` datetime not null default '0000-00-00 00:00:00' comment '有效时间开始时间',
  `fc_end_time` datetime DEFAULT '2099-00-00 00:00:00' comment '有效时间结束时间',
  `fc_status` tinyint(1) not null default '1' comment '1启用，0：停用',
  PRIMARY KEY (`fc_id`),
  KEY `fc_related_good_id` (`fc_related_good_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='自由推荐表';

drop table if exists `fx_free_recommend`;
create table `fx_free_recommend` (
`fr_id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT comment '自增id' ,
`fr_name`  varchar(100) not null default '' comment '自由搭配名称' ,
`fr_goods_id`  int(11) not null default 0 comment '搭配商品id' ,
`fr_goods_picture`  varchar(100) not null default '' comment '商品图片',
`fr_price`  decimal(10,3) not null default 0.000 comment '搭配价格' ,
`fr_original_price`  decimal(10,3) not null default 0.000 comment '商品原价',
`fr_statr_time`  timestamp not null default '0000-00-00 00:00:00' comment '开始时间' ,
`fr_end_time`  timestamp not null default '0000-00-00 00:00:00' comment '结束时间' ,
`fr_status`  tinyint(1) not null default 1 comment '是否启用 0不启用 1启用' ,
PRIMARY KEY (`fr_id`),
INDEX `fr_goods_id` (`fr_goods_id`) ,
INDEX `fr_name` (`fr_name`) 
)ENGINE=InnoDB DEFAULT character set=utf8 collate=utf8_general_ci comment='自由搭配表';

alter table `fx_orders_items` modify column `oi_type`  int(4) not null default 0 comment '商品类型，8:预售 7:秒杀商品 6:自由搭配商品 5:团购商品，4:自由推荐商品,3组合商品，2赠品， 1积分商品，0普通商品';
alter table `fx_orders_items` modify column `fc_id`  int(11) not null default 0 comment 'oi_typ=4:自由推荐ID;oi_type=3:组合ID;oi_type=6:自由搭配ID' after `erp_id`;


drop table if exists `fx_spike`;
create table `fx_spike` (
  `sp_id` int(11) NOT NULL AUTO_INCREMENT comment '秒杀ID',
  `sp_title` varchar(255) not null comment '秒杀标题',
  `sp_desc` text comment '秒杀描述',
  `sp_picture` varchar(100) not null comment '秒杀图片',
  `g_id` int(11) not null default '0' comment '商品ID',
  `sp_number` int(11) not null default '0' comment '限购数量',
  `sp_now_number` int(11) not null default '0' comment '已秒杀数量',
  `sp_send_point` int(11) not null default '0' comment '赠送积分',
  `sp_price` decimal(10,3) not null default '0.000' comment '秒杀价格',
  `sp_goods_desc_status` tinyint(1) not null default '0' comment '是否显示商品描述',
  `sp_start_time` timestamp not null default '0000-00-00 00:00:00' comment '活动开始时间',
  `sp_end_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' comment '活动结束时间',
  `sp_status` tinyint(1) not null default '1' comment '是否启用：0.停用，1.启用',
  `sp_create_time` timestamp not null default '0000-00-00 00:00:00' comment '秒杀创建时间',
  `sp_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '秒杀更新时间',
  PRIMARY KEY (`sp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='秒杀活动表';

drop table if exists `fx_presale`;
create table `fx_presale`(
    `p_id` int(11) NOT NULL AUTO_INCREMENT comment '预售ID',
    `p_title` varchar(90) not null default '' comment '预售标题',
    `g_id` int(11) not null default '0' comment '商品ID',
    `p_picture` varchar(100) not null comment '预售图片',
    `p_start_time` timestamp DEFAULT '0000-00-00 00:00:00' comment '预售开始时间',
    `p_end_time` timestamp DEFAULT '0000-00-00 00:00:00' comment '预售结束时间',
    `p_deposit_price` decimal(10,3) not null default '0.000' comment '预售定金',
    `p_number` int(10) not null default '0' comment '预售限购数量',
    `p_overdue_start_time` datetime not null default '0000-00-00 00:00:00' comment '补交余款开始时间',
    `p_overdue_end_time` datetime not null default '0000-00-00 00:00:00' comment '补交余款结束时间',
    `p_per_number` int(10) not null default '0' comment '每人限购数量',
    `p_pre_number` int(10) not null default '0' comment '虚拟购买数量',
    `p_desc` text not null comment '预售介绍',
    `p_goodshow_status` tinyint(1) not null default '0' comment '是否显示商品详情',
    `is_active` tinyint(1) not null default '1' comment '状态 1为启用预售活动, 0为停用预售活动',
    `p_order` int(10) not null default '0' comment '显示次序, 数值越小越靠前',
    `p_now_number` int(10) not null default '0' comment '已预售数量',
    `p_deleted` tinyint(1) not null default '0' comment '是否删除, 0为正常状态, 1为删除状态',
    `is_deposit` tinyint(1) DEFAULT '0' comment '是否启用定金 0为不启用定金, 1为启用定金',
    `p_create_time` datetime not null default '0000-00-00 00:00:00' comment '预售创建时间',
    `p_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '预售更新时间',
    `p_price` decimal(10,3) not null default '0.000' comment '预售初始价',
    PRIMARY KEY (`p_id`),
    KEY `is_active` (`is_active`),
    KEY `sort_order` (`p_order`),
    KEY `p_start_time` (`p_start_time`),
    KEY `P_end_time` (`p_end_time`),
    KEY `p_create_time` (`p_create_time`),
    KEY `p_update_time` (`p_update_time`),
    KEY `g_id` (`g_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='预售商品基本表';

drop table if exists `fx_presale_log`;
create table `fx_presale_log`(
    `pl_id` int(11) NOT NULL AUTO_INCREMENT comment '预售日志ID',
    `o_id` bigint(20) not null comment '订单ID',
    `p_id` int(10) not null default '0' comment '预售ID',
    `m_id` int(10) not null default '0' comment '会员ID',
    `g_id` int(10) not null default '0' comment '商品ID',
    `num` int(4) not null default '0' comment '购买数量。取值范围:大于零的整数',
    `pl_remark` varchar(200) not null default '' comment '备注',
    PRIMARY KEY (`pl_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='预售日志表';

drop table if exists `fx_presale_set`;
create table `fx_presale_set`(
    `ps_id` int(11) NOT NULL AUTO_INCREMENT comment '预售设置ID',
    `ps_price_range` text not null comment '价格区间',
    `ps_create_time` datetime not null default '0000-00-00 00:00:00' comment '创建时间',
    `ps_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '修改时间',
    PRIMARY KEY (`ps_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='预售价格区间设置表';

drop table if exists `fx_related_presale_area`;
create table `fx_related_presale_area`(
    `related_area_id` int(11) NOT NULL AUTO_INCREMENT comment '关联表ID',
    `p_id` int(11) not null default '0' comment '关联预售ID',
    `cr_id` int(11) not null default '0' comment '关联区域ID',
    PRIMARY KEY (`related_area_id`),
    KEY `p_id` (`p_id`),
    KEY `cr_id` (`cr_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='预售区域关联表';

drop table if exists `fx_related_presale_price`;
create table `fx_related_presale_price`(
    `related_price_id` int(11) NOT NULL AUTO_INCREMENT comment '关联表ID',
    `p_id` int(11) not null default '0' comment '预售ID',
    `rgp_num` int(10) not null default '0' comment '数量达到值',
    `rgp_price` decimal(10,3) not null default '0' comment '享受价格',
    PRIMARY KEY (`related_price_id`),
    KEY `p_id` (`p_id`),
    KEY `rgp_num` (`rgp_num`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='预售价格关联表';

alter table `fx_thd_shops` add column `ts_default` tinyint(4) not null default '0' comment '是否默认此店铺物流模板(0:否,1：是)';

drop table if exists `fx_related_spike_area`;
create table `fx_related_spike_area` (
  `rsa_id` int(11) NOT NULL AUTO_INCREMENT,
  `sp_id` int(11) not null comment '秒杀ID',
  `cr_id` int(11) not null comment '区域ID',
  PRIMARY KEY (`rsa_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 comment='秒杀关联区域表';

alter table fx_goods_spec_detail drop key gsd_value;
alter table `fx_goods_spec_detail` modify column `gsd_value`  varchar(1000) not null default '' comment '明细值';
alter table fx_goods_spec_detail add key gsd_value(gsd_value(30));

delete from `fx_sys_config` where `sc_module` = 'ADMIN_LOGIN_PROMPT';
insert into fx_sys_config set `sc_module`='ADMIN_LOGIN_PROMPT',`sc_key`='ADMIN_LOGIN_PROMPT_SET',`sc_value`='1',sc_value_desc='显示管理员未登录提示语 0不显示 1显示';

alter table `fx_related_goods_spec` modify column `gsd_aliases`  varchar(150) character set utf8 collate utf8_general_ci not null default '' comment '商品属性值别名，例如系统的红色可以别名为大红色或者深红色' after `g_id`;

alter table `fx_admin` DROP INDEX `role_id` ,add index `role_id` (`role_id`) USING BTREE ;

alter table `fx_collect_goods` CHANGE COLUMN `pdt_id` `g_id`  int(11) not null default 0 comment '货品id' after `m_id`,DROP INDEX `pdt_id` ,add index `g_id` (`g_id`) USING BTREE ;

alter table `fx_goods`
modify column `g_gifts`  tinyint(1) not null default 0 comment '是否赠品，0为不是，1为不可正常销售赠品，2为可正常销售赠品' after `g_pre_sale_status`,
add column `g_related_type`  tinyint(1) not null default 0 comment '关联类型' after `erp_guid`,
add column `g_related_goods_ids`  varchar(100) character set utf8 collate utf8_general_ci not null default '' comment '关联商品' after `g_related_type`;

alter table `fx_goods_info`
add column `g_custom_field_1`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '商品资料自定义字段1' after `g_source`,
add column `g_custom_field_2`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '商品资料自定义字段2' after `g_custom_field_1`,
add column `g_custom_field_3`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '商品资料自定义字段3' after `g_custom_field_2`,
add column `g_custom_field_4`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '商品资料自定义字段4' after `g_custom_field_3`,
add column `g_custom_field_5`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '商品资料自定义字段5' after `g_custom_field_4`,
add column `g_collocation_price`  decimal(10,3) not null default 0.000 comment '自由推荐价格' after `g_update_time`;

alter table `fx_goods_products`
add column `pdt_collocation_price`  decimal(10,3) not null default 0.000 comment '自由推荐价格' after `pdt_is_combination_goods`;

alter table `fx_goods_spec`
add column `gs_is_search` tinyint(1) unsigned not null default '1' comment '属性是否允许搜索';

alter table `fx_invoice_collect`
modify column `m_id`  int(11) UNSIGNED not null default 0 comment '发票收藏用户id ' after `invoice_content`,
add column `is_verify`  tinyint(1) not null default 0 comment '增值税发票是否审核' after `modify_time`;

alter table `fx_invoice_config` add column `is_auto_verify`  tinyint(1) not null default 0 comment '增值税发票自动审核' after `invoice_content`;

alter table `fx_logistic_corp`
modify column `lc_website`  varchar(200) character set utf8 collate utf8_general_ci not null default '' comment '物流公司网址' after `lc_ordernum`,
add column `lc_kuaidi100_name`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment '快递100物流公司代码。' after `lc_abbreviation_name`;

alter table `fx_members`
DROP COLUMN `is_proxy`,
add column `m_order_status`  tinyint(1) not null default 0 comment '代理下单审核( 0-否,1-是 )' after `m_balance_name`,
add column `is_proxy`  tinyint(1) not null default 0 comment '是否申请代理商（0为否，1 为是）' after `m_order_status`,
add column `login_type`  tinyint(1) not null default 0 comment '登录方式：1第三方授权登录 0传统登录' after `is_proxy`;

drop table if exists `fx_members_log`;
create table `fx_members_log` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `m_id` bigint(20) not null default '0' comment '用户id',
  `update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '更新时间',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='用户资料日志表';

alter table `fx_orders`
add column `is_evaluate`  tinyint(1) UNSIGNED not null default 0 comment '是否评价：0未评价，1已评价，2部分评价' after `invoice_content`,
add column `o_qc`  varchar(30) character set utf8 collate utf8_general_ci not null default '' comment '质检员' after `is_evaluate`,
add column `o_unfreeze_time`  datetime not null default '0000-00-00 00:00:00' comment '冻结解除时间（延迟发货时间）' after `o_qc`,
add column `admin_id`  int not null default 0 comment '管理员操作者ID' after `o_unfreeze_time`,
add column `cacel_type`  tinyint(4) not null default 0 comment '作废类型（1：用户不想要了;2：商品无货;3:重新下单;4:其他原因）' after `admin_id`,
add column `o_total_discount_fee`  decimal(10,3) not null default 0.000 comment '让利金额' after `cacel_type`,
modify column `invoice_name`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment '公司名称' after `o_goods_discount`,
modify column `o_shipping_remarks`  tinyint(1) not null default 0 comment '1.发货先发，缺货后发，2.等缺货一起发，3.修改订单，删除缺货商品' after `invoice_name`,
modify column `promotion`  text character set utf8 collate utf8_general_ci NOT NULL comment '促销信息' after `o_promotion_price`,
modify column `initial_o_id`  int(11) not null default 0 comment '父订单ID' after `promotion`,
modify column `o_diff_freight`  decimal(10,3) not null default 0.000 comment '邮费差价' after `erp_id`,
modify column `flag_type`  tinyint(4) not null default 0 comment '订单旗帜（6个）' after `o_addorder_id`;

alter table `fx_orders_refunds`
add column `or_finance_u_id`  int(11) not null default 0 comment '财审操作人' after `or_picture`,
add column `or_finance_u_name`  varchar(30) character set utf8 collate utf8_general_ci not null default '' comment '财审管理员名称' after `or_finance_u_id`,
add column `or_service_time`  datetime not null default '0000-00-00 00:00:00' comment '客服确认时间' after `or_finance_u_name`,
add column `or_finance_time`  datetime not null default '0000-00-00 00:00:00' comment '财务确认时间' after `or_service_time`,
add column `or_refuse_reason`  varchar(200) not null default '' comment '拒绝理由' after `or_finance_time`,
add column `or_refunds_type`  tinyint(1) NULL DEFAULT NULL comment '退款渠道：1预存款 2指定账户 3原路返回' after `or_refuse_reason`;

alter table `fx_payment_cfg`
add column `pc_position`  int not null default 0 comment '支付方式排序' after `pc_trd`;

drop table if exists `fx_refunds_spec`;
create table `fx_refunds_spec` (
  `gs_id` int(11) NOT NULL AUTO_INCREMENT comment '规格ID',
  `gs_name` varchar(50) not null default '' comment '规格名称',
  `gs_simple_name` varchar(50) not null default '' comment '规格别名',
  `gs_remark` varchar(50) not null default '' comment '规格备注（暂不启用）',
  `gs_show_type` tinyint(1) not null default '1' comment '类型 1退款 2退货',
  `gs_input_type` tinyint(1) not null default '1' comment '输入类型 1文本输入 2附件 3文本域输入',
  `gs_order` int(11) not null default '0' comment '属性排序',
  `gs_status` tinyint(1) not null default '1' comment '数据记录状态，1为有效，0为删除',
  `gs_create_time` timestamp not null default '0000-00-00 00:00:00' comment '记录创建时间',
  `gs_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '记录最新修改时间',
  PRIMARY KEY (`gs_id`),
  KEY `gs_show_type` (`gs_show_type`),
  KEY `gs_input_type` (`gs_input_type`),
  KEY `gs_status` (`gs_status`),
  KEY `gs_order` (`gs_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='退款退货规格表';

drop table if exists `fx_related_refund_spec`;
create table `fx_related_refund_spec` (
  `or_id` mediumint(8) unsigned NOT NULL comment '退换货单or_id',
  `gs_id` int(10) unsigned NOT NULL comment '属性项gs_id',
  `content` text NOT NULL comment '内容',
  KEY `or_id` (`or_id`),
  KEY `gs_id` (`gs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='退款退货属性项值';

drop table if exists `fx_releted_spec_combination`;
create table `fx_releted_spec_combination` (
  `sc_id` int(11) not null default '0' comment '规格组合商品id',
  `rsc_spec_name` varchar(50) not null default '' comment '规格组合名',
  `rsc_spec_detail` varchar(100) not null default '' comment '规格组合值',
  `rsc_rel_good_id` int(11) not null default '0' comment '规格组合关联商品id',
  `rsc_rel_good_sn` varchar(100) not null default '' comment '规格组合关联商品编号',
  `rsc_show_type` tinyint(1) not null default '1' comment '显示类型：1文字 2图片',
  `rsc_order` int(11) not null default '0' comment '属性排序',
  KEY `sc_show_type` (`rsc_show_type`),
  KEY `sc_spec_name` (`rsc_spec_name`) USING BTREE,
  KEY `sc_spec_detail` (`rsc_spec_detail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='规格组合商品关联表';

drop table if exists `fx_spec_combination`;
create table `fx_spec_combination` (
  `scg_id` int(11) unsigned NOT NULL AUTO_INCREMENT comment '自增id',
  `scg_name` varchar(100) not null default '' comment '规格组合标题',
  `scg_status` tinyint(1) not null default '1' comment '数据记录状态，0为废弃，1为有效，2为进入回收站',
  `scg_create_time` timestamp not null default '0000-00-00 00:00:00' comment '数据创建时间',
  `scg_update_time` timestamp not null default '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP comment '数据更新时间',
  PRIMARY KEY (`scg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment='规格组合表';

alter table `fx_warehouse`
modify column `w_name`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment '商家的仓库名称，可更新' after `w_id`,
modify column `w_code`  varchar(100) character set utf8 collate utf8_general_ci not null default '' comment '商家的仓库编码，不允许重复，不允许更新(erp_id+erp_code)' after `w_name`,
add column `erp_id`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment '对应ERPid' after `w_postcode`,
add column `erp_code`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment 'erp仓库编码' after `erp_id`,
add index `erp_id` (`erp_id`) ,
add index `erp_code` (`erp_code`) ;

alter table `fx_warehouse_stock`
add column `erp_id`  varchar(50) character set utf8 collate utf8_general_ci not null default '' comment 'ERP的ID' after `ws_update_time`,
add column `erp_code`  varchar(100) character set utf8 collate utf8_general_ci not null default '' comment 'erp仓库代码' after `erp_id`,
add column `pdt_sn`  varchar(50) not null default '' comment '商品规格编码' after `erp_code`,
add column `g_sn`  varchar(50) not null default '' comment '商品编码' after `pdt_sn`,
add index `code` (`erp_id`, `erp_code`) ,
add index `pdt_sn` (`pdt_sn`) ,
add index `g_sn` (`g_sn`) ;

ALTER TABLE `fx_thd_orders_items` CHANGE `to_id` `to_id` VARCHAR( 50 ) NOT NULL DEFAULT '0' COMMENT '第三方订单的主键id';
ALTER TABLE `fx_thd_orders` CHANGE `to_oid` `to_oid` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '第三方订单ID';
set names utf8;
/* *
 * 分销后台权限节点单独维护
 * 注明自己的姓名、功能点、更新时间
 *
 * 1. 提交SQL之前必须在自己机器上测试通过才允许提交！！！！
 * 2. 提交SQL文件不写注释的，拖出去枪毙三天！！！
 * 3. 第一行set names utf8 不允许删除！！！！
 * 4. 提交的SQL语句只允许增加，不允删除或修改
 * 5. SQL脚本提交完毕以后务必在自己的SQL脚本尾部增加一行注释，标记自己的本次更新已经结束！！！
 * 6. 增加的功能根据SQL实例，书写节点SQL语句
 * 7.节点表为：fx_role_node，SQL实例如下
 ********************************************************************************
 * id                                           节点ID
 * action                                       节点控制器(对应module下的action)
 * action_name                                  节点控制器名称(对应action显示的名称)
 * module                                       节点模型(对应项目中的module)
 * module_name                                  节点模型名称(对应module显示的名称) 
 * status                                       节点是否可用:0为禁用,1为启用(默认为启用状态)
 * sort                                         节点排序(数值越大越靠前)
 * auth_type                                    授权模式：0:操作授权(action) 1:模块授权(module) 2:节点授权(node) 
 * 
 * 以商品管理为例：商品管理module为Products
 * INSERT INTO fx_role_node SET `module`='Products',`module_name`='商品管理',`auth_type`='1';  
 *
 * 以商品列表为例：商品列表属于商品管理下的ACTION(类似于商品管理为一级菜单，商品列表属于商品管理的下级菜单)
 * INSERT INTO fx_role_node SET `action`='pageList',`action_name`='商品列表',`module`='Products',`module_name`='商品管理',`auth_type`='0';       
 ********************************************************************************
 */
DROP TABLE IF EXISTS `fx_role_node`;
CREATE TABLE `fx_role_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '节点ID',
  `action` varchar(60) NOT NULL DEFAULT '' COMMENT '节点控制器',
  `action_name` varchar(60) NOT NULL DEFAULT '' COMMENT '节点控制器名称',
  `module` varchar(60) NOT NULL DEFAULT '' COMMENT '节点模型',
  `module_name` varchar(60) NOT NULL DEFAULT '' COMMENT '节点模型名称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '节点是否可用:0为禁用,1为禁用',
  `sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '节点排序',
  `auth_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '授权模式：1:模块授权(module) 2:操作授权(action) 0:节点授权(node)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `action_module` (`action`,`module`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8 COMMENT='节点表';

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Home', '官网模板', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSetting', '查看官网基本信息设置', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSet', '更新官网基本信息', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageClose', '暂停营业公告', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSetClose', '添加/修改暂停营业公告', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageRegister', '注册协议', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddRegister', '处理注册协议', 'Home', '官网模板', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageTpl', '官网模板管理', 'Home', '官网模板', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Notice', '网站公告', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '公告列表', 'Notice', '网站公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '发布公告', 'Notice', '网站公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '处理添加公告', 'Notice', '网站公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑公告', 'Notice', '网站公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新公告', 'Notice', '网站公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除公告', 'Notice', '网站公告', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Announcement', '违规公告', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '违规公告列表', 'Announcement', '违规公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增违规公告', 'Announcement', '违规公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加违规公告', 'Announcement', '违规公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑违规公告', 'Announcement', '违规公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新违规公告', 'Announcement', '违规公告', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除违规公告', 'Announcement', '违规公告', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Article', '官网资讯', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '官网资讯列表', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增官网资讯', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加官网资讯', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑资讯', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新资讯', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除资讯', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListCate', '官网资讯分类', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAddCate', '新增资讯分类', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddCate', '添加资讯分类', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEditCate', '编辑资讯分类', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditCate', '更新资讯分类', 'Article', '官网资讯', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelCate', '删除资讯分类', 'Article', '官网资讯', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Seo', '搜索引擎优化设置', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', 'SEO列表', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑SEO', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新SEO信息', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageMap', '站点地图设置展示', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doMapSave', '保存站点地图生成的配置信息', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doMapRefresh', '刷新站点地图缓存', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageCount', '统计脚本设置', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doCount', '保存第三方统计脚本', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageCach', '查看缓存设置', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'cachAdd', '执行保存缓存设置', 'Seo', '搜索引擎优化设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'deleteCacheDir', '删除缓存数据', 'Seo', '搜索引擎优化设置', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Links', '友情链接', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '友情链接列表', 'Links', '友情链接', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增友情链接', 'Links', '友情链接', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加友情链接', 'Links', '友情链接', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑友情链接', 'Links', '友情链接', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新友情链接', 'Links', '友情链接', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除友情链接', 'Links', '友情链接', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Online', '在线客服', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '在线客服列表', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增在线客服', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加在线客服', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑在线客服', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新在线客服', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除在线客服', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListCate', '在线客服分类', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEditCate', '编辑在线客服分类', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAddCate', '新增在线客服分类', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddCate', '添加在线客服分类', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditCate', '更新在线客服分类', 'Online', '在线客服', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelCate', '删除在线客服分类', 'Online', '在线客服', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Guestbook', '商品评论', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageProductsList', '商品评论列表', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageProductsSetting', '商品评论设置', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doPageProductsSetting', '更新商品评论设置', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doProductsDel', '商品评论删除', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doProductsAudit', '商品评论审核', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setGoodComment', '点击回复评论', 'Guestbook', '商品评论', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doGoodsComment', '回复评论', 'Guestbook', '商品评论', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Message', '站内信', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageMailBox', '站内信列表', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSend', '新增站内信', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加站内信', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageRead', '查看站内信', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageReply', '回复站内信', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doReply', '处理回复站内信', 'Message', '站内信', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除站内信', 'Message', '站内信', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Suggestions', '投诉建议', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '投诉建议列表', 'Suggestions', '投诉建议', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageDetail', '投诉建议详情', 'Suggestions', '投诉建议', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doIshandle', '处理投诉建议', 'Suggestions', '投诉建议', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Nav', '自定义导航栏', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '自定义导航列表', 'Nav', '自定义导航栏', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增自定义导航', 'Nav', '自定义导航栏', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑自定义导航', 'Nav', '自定义导航栏', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新自定义导航', 'Nav', '自定义导航栏', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除自定义导航', 'Nav', '自定义导航栏', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Operation', '日志操作记录设置', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '后台操作记录列表 ', 'Operation', '日志操作记录设置', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Products', '商品管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '商品列表', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doGoodsisDel', '删除商品', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageDetail', '查看商品详情', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setGoodPoint', '设置商品积分', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setItemSaleNumbers', '修改商品销量', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doGoodsOnSale', '商品上下架', 'Products', '商品管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'detail', '商品预览', 'Products', '商品管理', '1', '0', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Goods', '商品管理详情', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'goodsAdd', '新增商品页面', 'Goods', '商品管理详情', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doGoodsAdd', '添加商品', 'Goods', '商品管理详情', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'goodsEdit', '商品编辑页面', 'Goods', '商品管理详情', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doGoodsEdit', '修改商品', 'Goods', '商品管理详情', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxSetGoodsFlag', '商品标记状态翻转(是否是新品、热销)', 'Goods', '商品管理详情', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'combinationGoodsList', '组合商品列表 ', 'Goods', '组合商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addCombinationGoodPage', '新增组合商品', 'Goods', '组合商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editCombinationGoodsPage', '编辑组合商品', 'Goods', '组合商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'deleteCombiantionGoods', '删除组合商品', 'Goods', '组合商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'combinationPropertyGoodsList', '规格组合商品列表', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addCombinationPropertyGoodsPage', '添加规格组合商品', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editCombinationPropertyGoodsPage', '规格组合商品详情', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editCombinationPropertyGoods', '修改组合规格商品', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxDelCombinationPropertyGoods', '删除组合规格商品', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'enableCombinationPropertyGoods', '启用（停用）规格组合商品', 'Goods', '组合规格商品', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'configIndustrySpec', '行业属性配置', 'Goods', '组合商品', '1', '0', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Stock', '库存管理', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '库存调整单', 'Stock', '库存管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageDetail', '库存调整单明细', 'Stock', '库存管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增库存调整单', 'Stock', '库存管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '库存设置', 'Stock', '库存管理', '1', '0', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsCategory', '商品分类管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '商品分类列表 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '分类编辑 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addCategory', '添加商品分类 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageDisplay', '更新分类显示状态(显示或不显示) ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '保存分类 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新分类 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除分类 ', 'GoodsCategory', '商品分类管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsType', '商品类型管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '商品类型列表 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addGoodsType', '类型添加 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'eidtGoodsType', '类型编辑 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelTypeDetail', '类型删除 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelType', '类型批量删除 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEidtType', '类型更新 ', 'GoodsType', '商品类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddType', '类型保存 ', 'GoodsType', '商品类型管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsProperty', '商品属性管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'specListPage', '商品属性列表 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'specEditPage', '编辑属性 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addSpecPage', '属性添加 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelSpec', '删除属性 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditSpec', '更新属性 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddSpec', '保存属性 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doPropertyIsSearch', '启用/停用属性 ', 'GoodsProperty', '商品属性管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'freeCollocationList', '自由推荐列表', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addFreeCollocationPage', '新增自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addFreeCollocation', '保存自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'enableFreeCollocation', '启用/关闭自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editFreeCollocationPage', '启用/编辑自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editFreeCollocation', '更新自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxDelFreeCollocation', '删除自由推荐', 'GoodsFreeCollocation', '商品自由推荐管理', '1', '0', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsBrand', '商品品牌管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '品牌列表', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addBrand', '新增商品品牌', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加商品品牌', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑商品品牌', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新商品品牌', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除商品品牌', 'GoodsBrand', '商品品牌管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doStatus', '品牌启用/停用', 'GoodsBrand', '商品品牌管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'GoodsGroup', '商品分组管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '商品分组列表', 'GoodsGroup', '商品分组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addGroup', '新增商品分组', 'GoodsGroup', '商品分组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addGoodsToGroup', '添加商品到分组', 'GoodsGroup', '商品分组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑商品分组', 'GoodsGroup', '商品分组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'removeGoodsToGroup', '移出分组的商品', 'GoodsGroup', '商品分组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除商品分组', 'GoodsGroup', '商品分组管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Orders', '订单管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '订单列表', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageDetails', '订单详情', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxInvalidOrder', '订单作废', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageWaitPayOrdersList', '待付款订单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageWaitDeliverOrdersList', '待发货订单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersProceedsList', '收款单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersRefundList', '退款单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setOrderRefund', '退款单详情', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersDeliverList', '发货单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersReturnList', '退货单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setOrderReturn', '退货单详情', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersReceipt', '售后单据', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doOrderStatus', '处理售后单据', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setAftersale', '售后服务配置', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddAftersale', '更新售后服务配置', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAftersaleList', '售后服务列表', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageOrdersLog', '订单日志', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '订单设置', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSet', '处理订单设置', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setSendShip', '发货设置', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'UpdateOrderStatus', '更新订单状态(发货状态)', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'explortOrdersInfo', '订单导出', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxUpdateOrderItemsPrice', '更新订单明细中商品价格', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'OrderRemarkUpdate', '订单列表设置备注', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'setOrdersRemark', '设置卖家备注', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'getOrdersSearch', '订单高级搜索', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doOrderPay', '订单支付', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEditOk', '等待发货订单编辑', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '未付款订单编辑', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新未付款订单信息', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditOk', '更新等待发货订单信息', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'delItems', '删除订单商品', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'checkAudit', '订单审核', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'removeOrderItems', '订单拆分', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'autoRemoveOrderItems', '手工拆单', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'overOrder', '订单完结', 'Orders', '订单管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'RefundsProperty', '退换货属性管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'specListPage', '退换货属性列表', 'RefundsProperty', '退换货属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'specEditPage', '退换货属性编辑', 'RefundsProperty', '退换货属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'specAddPage', '退换货属性添加', 'RefundsProperty', '退换货属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditSpec', '保存修改退换货属性', 'RefundsProperty', '退换货属性管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelSpec', '删除退换货属性', 'RefundsProperty', '退换货属性管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Delivery', '配送设置', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '配送公司列表', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增配送公司', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑配送公司', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加配送公司', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新配送公司', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除配送公司', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListArea', '配送区域列表', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAddArea', '新增配送区域', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEditArea', '编辑配送区域', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddArea', '添加配送区域', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditArea', '更新配送区域', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelArea', '删除配送区域', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAddress', '地址库管理', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'delCityAddress', '删除地址库管理', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addCityAddress', '添加地址库管理', 'Delivery', '配送设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'editCityAddress', '更新地址库管理', 'Delivery', '配送设置', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Promotions', '促销活动管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '促销活动列表', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增促销活动', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加促销活动', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑促销活动', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新促销活动', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除促销活动', 'Promotions', '促销活动管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'ajaxDoProEdit', '启用/停用促销活动', 'Promotions', '促销活动管理', '1', '10', '0');


INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Coupon', '优惠券管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '后台优惠券列表', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAuto', '批量新增优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAuto', '批量添加优惠劵', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新优惠劵', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageGetExeclCoupon', '导出优惠券', 'Coupon', '优惠券管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '优惠券获取节点', 'Coupon', '优惠券管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Groupbuy', '团购活动', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '团购活动列表', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddSet', '处理团购设置', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增团购', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '保存团购', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑团购', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新团购', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除团购', 'Groupbuy', '团购活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '团购设置', 'Groupbuy', '团购活动', '1', '0', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Members', '会员信息', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '会员列表', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'memberAdd', '新增会员', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加会员', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑会员', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新会员', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '会员删除', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doBatDelMembers', '批量删除会员', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pointList', '会员积分记录', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doBacthMembers', '批量冻结', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'feedBackList', '买家留言列表', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'replyAjax', '回复买家留言', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'explortFeedBackList', '导出买家留言', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '会员基本设置', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSet', '保存会员基本设置', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'fieldsList', '会员属性项列表', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'fieldsAdd', '会员属性项详情', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doFields', '会员属性项添加', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doFieldDel', '会员属性项删除', 'Members', '会员管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addOrder', '替客户下单', 'Members', '会员管理', '1', '10', '0');

INSERT INTO `fx_role_node` SET `module`='Sourceplatform',`module_name`='会员所属平台管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='pageList',`action_name`='所属平台列表',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='pageAdd',`action_name`='添加平台',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doAdd',`action_name`='新增平台',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='pageEdit',`action_name`='所属平台编辑',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doEdit',`action_name`='更新所属平台信息',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doDel',`action_name`='删除所属平台',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doBatDelPlat',`action_name`='批量删除所属平台',`module`='Sourceplatform',`module_name`='所属平台管理',`auth_type`='0';

INSERT INTO `fx_role_node` SET `module`='MembersDistributed',`module_name`='会员平台分布管理',`auth_type`='1 ';
INSERT INTO `fx_role_node` SET `action`='platformPie',`action_name`='会员平台分布饼图',`module`='MembersDistributed',`module_name`='会员平台分布管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='membersAreaPie',`action_name`='会员地区分布饼图',`module`='MembersDistributed',`module_name`='会员平台分布管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='memberThdPic',`action_name`='第三方授权登录平台分步',`module`='MembersDistributed',`module_name`='会员平台分布管理',`auth_type`='0';


INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Memberlevel', '会员等级', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '会员等级列表', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增会员等级', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加会员等级', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑会员等级', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新会员等级', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除会员等级', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditLevelDefault', '设置会员等级默认值', 'Memberlevel', '会员等级', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doBacthLevel', '批量设置等级', 'Memberlevel', '会员等级', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Membergroup', '会员分组', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '会员分组列表', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增会员分组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加会员分组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑会员分组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新会员分组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除会员分组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'groupingPage', '会员归组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSet', '添加会员归组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelSet', '删除会员归组', 'Membergroup', '会员分组', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doBacthGroup', '批量设置分组', 'Membergroup', '会员分组', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Authorize', '分销产品授权', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '授权线管理列表', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增产品授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加产品授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑产品授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新产品授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDefault', '设置默认授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '会员授权线设置', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSet', '更新会员授权线', 'Authorize', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelSet', '删除会员授权线', 'Authorize', '分销产品授权', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Thdlogin', '第三方授权登录设置管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '授权登录设置页面', 'Thdlogin', '分销产品授权', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '保存第三方授权登录信息', 'Thdlogin', '分销产品授权', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Subcompany', '子公司管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '子公司列表页', 'Subcompany', '子公司管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '子公司添加页', 'Subcompany', '子公司管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '新增子公司', 'Subcompany', '子公司管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑子公司', 'Subcompany', '子公司管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '修改子公司', 'Subcompany', '子公司管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除子公司', 'Subcompany', '子公司管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'BalanceInfo', '结余款管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '结余款调整单列表', 'BalanceInfo', '结余款管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'detailBalanceInfo', '结余款调整单详情', 'BalanceInfo', '结余款管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addBalanceInfo', '新增结余款调整单', 'BalanceInfo', '结余款管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddBalanceInfo', '添加结余款调整单', 'BalanceInfo', '结余款管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'explortBalanceInfo', '导出结余款', 'BalanceInfo', '结余款管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doStatus', '审核结余款调整单', 'BalanceInfo', '结余款管理', '1', '10', '0');


INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'BalanceType', '结余款类型管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '结余款类型列表', 'BalanceType', '结余款类型管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doStatusBalanceType', '启用/停用结余款类型', 'BalanceType', '结余款类型管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Voucher', '销货收款单管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '销货收款单列表', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addVoucher', '新增销货收款单', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '保存销货收款单', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑销货收款单', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新销货收款单', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'explortVoucher', '导出销货收款单', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doStatus', '处理审核状态', 'Voucher', '销货收款单管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'detailVoucher', '销货收款单详情', 'Voucher', '销货收款单管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Financial', '支付管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListOnline', '线上支付设置 ', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListOffline', '线下支付配置', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAddOffline', '新增线下支付表单', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddOffline', '保存线下收款帐号', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageListVerify', '线下充值审核列表', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelOffline', '删除线下支付账号', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEditOffline', '编辑线下收款帐号', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditOffline', '更新线下收款帐号', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSetOnline', '设置默认线下支付方式', 'Financial', '支付管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doStatusOnline', '启用线上支付方式', 'Financial', '支付管理', '1', '10', '0');


INSERT INTO `fx_role_node` SET `module`='Invoice',`module_name`='发票设置管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='pageSet',`action_name`='设置发票',`module`='Invoice',`module_name`='发票设置',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doSet',`action_name`='修改发票设置',`module`='Invoice',`module_name`='发票设置',`auth_type`='0';

INSERT INTO `fx_role_node` SET `module`='IncreaseInvoice',`module_name`='增值税发票设置管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='pageList',`action_name`='增值税列表',`module`='IncreaseInvoice',`module_name`='增值税发票设置',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doVerify',`action_name`='增值税发票审核',`module`='IncreaseInvoice',`module_name`='增值税发票设置',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='detailInvoiceInfo',`action_name`='增值税发票详情',`module`='IncreaseInvoice',`module_name`='增值税发票设置',`auth_type`='0';

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'System', '管理帐号', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '管理员列表', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增管理员', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加管理员', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑管理员', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新管理员', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditStatus', '启用/停用管理员账号', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelete', '删除管理员', 'System', '管理帐号', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdminLog', '管理员登陆日志', 'System', '管理帐号', '1', '10', '0');

INSERT INTO `fx_role_node` SET `module`='Images',`module_name`='网站图片空间管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='index',`action_name`='图片列表',`module`='Images',`module_name`='网站图片空间管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doDel',`action_name`='删除图片',`module`='Images',`module_name`='网站图片空间管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='pageSet',`action_name`='水印设置',`module`='Images',`module_name`='网站图片空间管理',`auth_type`='0';
INSERT INTO `fx_role_node` VALUES (NULL, 'itemImageConfig', '商品详情页显示设置', 'Images', '官网模板', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Role', '权限组管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '角色列表', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增角色', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加角色', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑角色', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新角色', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditStatus', '角色启用/停用', 'Role', '权限组管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelete', '删除角色', 'Role', '权限组管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'RoleNode', '节点管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '节点列表', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增节点', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加节点', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑节点', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新节点', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEditStatus', '节点启用/停用', 'RoleNode', '节点管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDelete', '删除节点', 'RoleNode', '节点管理', '1', '10', '0');

INSERT INTO `fx_role_node` SET `module`='Point',`module_name`='积分设置管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='pageSet',`action_name`='积分设置',`module`='Point',`module_name`='积分设置管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doSet',`action_name`='修改积分设置',`module`='Point',`module_name`='积分设置管理',`auth_type`='0';

INSERT INTO `fx_role_node` SET `module`='Api',`module_name`='分销开放平台管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='pageSet',`action_name`='获取密钥',`module`='Api',`module_name`='分销开放平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='yunerppageSet',`action_name`='云ERP店铺授权',`module`='Api',`module_name`='分销开放平台管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='DoSetyunerp',`action_name`='保存云ERP店铺授权',`module`='Api',`module_name`='分销开放平台管理',`auth_type`='0';

INSERT INTO `fx_role_node` SET `module`='Consultation',`module_name`='购买咨询管理',`auth_type`='1';
INSERT INTO `fx_role_node` SET `action`='index',`action_name`='购买咨询列表',`module`='Consultation',`module_name`='购买咨询管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='pageDetail',`action_name`='购买咨询详情',`module`='Consultation',`module_name`='购买咨询管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doConsultationReply',`action_name`='回复购买咨询',`module`='Consultation',`module_name`='购买咨询管理',`auth_type`='0';
INSERT INTO `fx_role_node` SET `action`='doDel',`action_name`='删除咨询',`module`='Consultation',`module_name`='购买咨询管理',`auth_type`='0';

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Package', '数据包管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '数据包列表', 'Package', '数据包管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增数据包', 'Package', '数据包管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '添加数据包', 'Package', '数据包管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑数据包', 'Package', '数据包管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新数据包', 'Package', '数据包管理', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除数据包', 'Package', '数据包管理', '1', '10', '0');

INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Email', '邮件设置', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', 'SMTP设置', 'Email', '邮件设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doSetSmtp', '更新SMTP设置', 'Email', '邮件设置', '1', '10', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doTestSmtp', '发送测试邮件', 'Email', '邮件设置', '1', '10', '0');

/** 以上是7.4的节点 By Wangguibin 2013-11-14 共计419个 **/

/** 以下是7.4.5的节点 By Wangguibin 2013-11-14 Start **/

insert into fx_role_node set `module`='Distirbution',`module_name`='淘宝铺货',`auth_type`='1';
insert into fx_role_node set `action`='taobaoIndex',`action_name`='店铺绑定',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='taobaoSetSynRules',`action_name`='下载淘宝商品',`module`='Distirbution',`module_name`='下载淘宝商品',`auth_type`='0';
insert into fx_role_node set `action`='deliveryTemplateList',`action_name`='物流模板列表',`module`='Distirbution',`module_name`='下载物流模板',`auth_type`='0';
insert into fx_role_node set `action`='taobaoSet',`action_name`='淘宝铺货设置',`module`='Distirbution',`module_name`='淘宝铺货设置',`auth_type`='0';
insert into fx_role_node set `action`='doDelShops',`action_name`='取消店铺授权',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='topOauth',`action_name`='店铺授权更新',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='doDelShopGoods',`action_name`='删除已下载淘宝商品',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='downAllShopGoods',`action_name`='同步全部淘宝商品',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='downShopGoods',`action_name`='批量下载淘宝商品',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';
insert into fx_role_node set `action`='downDeliveryTemplate',`action_name`='下载物流模板',`module`='Distirbution',`module_name`='店铺绑定',`auth_type`='0';


insert into fx_role_node set `module`='Promoting',`module_name`='推广销售',`auth_type`='1';
insert into fx_role_node set `action`='payBack',`action_name`='返利列表',`module`='Promoting',`module_name`='返利列表',`auth_type`='0';

insert into fx_role_node set `module`='GoodsFreeRecommend',`module_name`='自由搭配管理',`auth_type`='1';
insert into fx_role_node set `action`='freeRecommendList',`action_name`='自由搭配列表',`module`='GoodsFreeRecommend',`module_name`='自由搭配管理',`auth_type`='0';
insert into fx_role_node set `action`='addFreeRecommendPage',`action_name`='新增自由搭配',`module`='GoodsFreeRecommend',`module_name`='自由搭配管理',`auth_type`='0';
insert into fx_role_node set `action`='editFreeRecommendPage',`action_name`='编辑自由搭配',`module`='GoodsFreeRecommend',`module_name`='自由搭配管理',`auth_type`='0';
insert into fx_role_node set `action`='ajaxDelFreeRecommend',`action_name`='删除自由搭配',`module`='GoodsFreeRecommend',`module_name`='自由搭配管理',`auth_type`='0';

insert into fx_role_node set `module`='Menus',`module_name`='系统菜单管理',`auth_type`='1';
insert into fx_role_node set `action`='index',`action_name`='后台菜单列表',`module`='Menus',`module_name`='返利列表',`auth_type`='0';
insert into fx_role_node set `action`='getUcenterMenus',`action_name`='会员中心菜单列表',`module`='Menus',`module_name`='返利列表',`auth_type`='0';
insert into fx_role_node set `action`='doStatusMenus',`action_name`='启用/停用菜单',`module`='Menus',`module_name`='返利列表',`auth_type`='0';

/** 以下是7.4.5的节点 By Wangguibin 2013-11-14  新增22个 共计441个 End **/
insert into fx_role_node set `module`='Spike',`module_name`='秒杀活动',`auth_type`='1';
insert into fx_role_node set `action`='index',`action_name`='秒杀列表',`module`='Spike',`module_name`='秒杀活动',`auth_type`='0';
insert into fx_role_node set `action`='add',`action_name`='新增秒杀',`module`='Spike',`module_name`='秒杀活动',`auth_type`='0';

/*预售节点新增 start by wanghaoyu*/
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Presale', '预售活动', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '预售活动列表', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAddSet', '处理预售设置', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增预售', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doAdd', '保存预售', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageEdit', '编辑预售', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doEdit', '更新预售', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'doDel', '删除预售', 'Presale', '预售活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '预售设置', 'Presale', '预售活动', '1', '0', '0');
/* 预售节点新增 end by wanghaoyu*/
