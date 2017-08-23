set names utf8;
DROP TABLE IF EXISTS `fx_groupbuy_brand`;
CREATE TABLE `fx_groupbuy_brand` (
  `gbb_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `gbb_name` varchar(100) NOT NULL DEFAULT '' COMMENT '类目名称',
  `gbb_pic` varchar(100) NOT NULL DEFAULT '' COMMENT '类目图片',
  `gbb_order` int(11) NOT NULL DEFAULT '0' COMMENT '类目排序',
  `gbb_is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否热门品牌促销 1是 0默认不是',
  `gbb_is_display` tinyint(1) NOT NULL DEFAULT '1' COMMENT '前台是否显示 0不显示 1显示',
  `gbb_description` varchar(255) NOT NULL DEFAULT '' COMMENT '分类描述',
  `gbb_keyword` varchar(50) NOT NULL DEFAULT '' COMMENT '关键字',
  `gbb_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，0为废弃，1为有效，2为进入回收站',
  `gbb_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `gbb_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  PRIMARY KEY (`gbb_id`),
  KEY `gbb_name` (`gbb_name`) USING BTREE,
  KEY `gbb_order` (`gbb_order`) USING BTREE,
  KEY `gbb_is_display` (`gbb_is_display`) USING BTREE,
  KEY `gbb_status` (`gbb_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='团购商品品牌类目表';
ALTER TABLE `fx_groupbuy` ADD COLUMN `gbb_id`  int(11) NOT NULL DEFAULT 0 COMMENT '团购所属品牌类目';
DROP TABLE IF EXISTS `fx_groupbuy_category`;
CREATE TABLE `fx_groupbuy_category` (
  `gc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `gc_parent_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '父类目ID',
  `gc_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类分级数，0是一级，1是二级以此类推。',
  `gc_is_parent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是父类目(0不是父级，1是父级)',
  `gc_name` varchar(100) NOT NULL DEFAULT '' COMMENT '类目名称',
  `gc_pic` varchar(100) NOT NULL DEFAULT '' COMMENT '类目图片',
  `gc_order` int(11) NOT NULL DEFAULT '0' COMMENT '类目排序',
  `gc_is_display` tinyint(1) NOT NULL DEFAULT '1' COMMENT '前台是否显示 0不显示 1显示',
  `gc_description` varchar(255) NOT NULL DEFAULT '' COMMENT '分类描述',
  `gc_keyword` varchar(50) NOT NULL DEFAULT '' COMMENT '关键字',
  `gc_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，0为废弃，1为有效，2为进入回收站',
  `gc_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `gc_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  PRIMARY KEY (`gc_id`),
  KEY `gc_parent_id` (`gc_parent_id`) USING BTREE,
  KEY `gc_is_parent` (`gc_is_parent`) USING BTREE,
  KEY `gc_order` (`gc_order`) USING BTREE,
  KEY `gc_is_display` (`gc_is_display`) USING BTREE,
  KEY `gc_status` (`gc_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='团购商品分类';
DROP TABLE IF EXISTS `fx_related_groupbuy_ads`;
CREATE TABLE `fx_related_groupbuy_ads` (
  `rga_id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_url` varchar(255) NOT NULL COMMENT '链接地址',
  `sort_order` tinyint(2) NOT NULL DEFAULT '0' COMMENT '排序',
  `ad_pic_url` varchar(255) NOT NULL COMMENT '图片链接地址',
  PRIMARY KEY (`rga_id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='团购广告图片表';
ALTER TABLE `fx_groupbuy` ADD COLUMN `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '团购所属分类';
DROP TABLE IF EXISTS `fx_spike_category`;
CREATE TABLE `fx_spike_category` (
  `gc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `gc_parent_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '父类目ID',
  `gc_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类分级数，0是一级，1是二级以此类推。',
  `gc_is_parent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是父类目(0不是父级，1是父级)',
  `gc_name` varchar(100) NOT NULL DEFAULT '' COMMENT '类目名称',
  `gc_pic` varchar(100) NOT NULL DEFAULT '' COMMENT '类目图片',
  `gc_order` int(11) NOT NULL DEFAULT '0' COMMENT '类目排序',
  `gc_is_display` tinyint(1) NOT NULL DEFAULT '1' COMMENT '前台是否显示 0不显示 1显示',
  `gc_description` varchar(255) NOT NULL DEFAULT '' COMMENT '分类描述',
  `gc_keyword` varchar(50) NOT NULL DEFAULT '' COMMENT '关键字',
  `gc_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，0为废弃，1为有效，2为进入回收站',
  `gc_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `gc_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  PRIMARY KEY (`gc_id`),
  KEY `gc_parent_id` (`gc_parent_id`) USING BTREE,
  KEY `gc_is_parent` (`gc_is_parent`) USING BTREE,
  KEY `gc_order` (`gc_order`) USING BTREE,
  KEY `gc_is_display` (`gc_is_display`) USING BTREE,
  KEY `gc_status` (`gc_status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='秒杀商品分类';
ALTER TABLE `fx_spike` ADD COLUMN `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '秒杀所属分类';
DROP TABLE IF EXISTS `fx_related_spike_ads`;
CREATE TABLE `fx_related_spike_ads` (
  `rsa_id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_url` varchar(255) NOT NULL COMMENT '链接地址',
  `sort_order` tinyint(2) NOT NULL DEFAULT '0' COMMENT '排序',
  `ad_pic_url` varchar(255) NOT NULL COMMENT '图片链接地址',
  PRIMARY KEY (`rsa_id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='秒杀广告图片表';
DROP TABLE IF EXISTS `fx_bonus_type`;
CREATE TABLE `fx_bonus_type` (
  `bt_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '红包类型ID',
  `bt_code` int(5) NOT NULL COMMENT '红包类型代码',
  `bt_name` varchar(50) NOT NULL DEFAULT '' COMMENT '类型名称',
  `bt_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '类型描述',
  `bt_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型状态：0为停用，1为启用',
  `bt_orderby` int(5) NOT NULL DEFAULT '10' COMMENT '类型排序，数值越大越靠前',
  `bt_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `bt_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录最后更新时间',
  PRIMARY KEY (`bt_id`),
  UNIQUE KEY `bt_code` (`bt_code`) USING BTREE,
  KEY `bt_status` (`bt_status`) USING BTREE,
  KEY `bt_orderby` (`bt_orderby`) USING BTREE,
  KEY `bt_create_time` (`bt_create_time`) USING BTREE,
  KEY `bt_update_time` (`bt_update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='红包类型表';
INSERT INTO `fx_bonus_type` VALUES ('1', '1000', '注册红包', '注册红包', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
INSERT INTO `fx_bonus_type` VALUES ('2', '1001', '抽奖红包', '抽奖红包', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
INSERT INTO `fx_bonus_type` VALUES ('3', '1002', '红包充值', '红包充值', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
INSERT INTO `fx_bonus_type` VALUES ('4', '1003', '消费红包', '消费红包', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
DROP TABLE IF EXISTS `fx_bonus_verify_log`;
CREATE TABLE `fx_bonus_verify_log` (
  `bvl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `u_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人名',
  `bn_sn` bigint(20) NOT NULL COMMENT '红包单据编号',
  `bvl_desc` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `bvl_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作类型，0为新增操作，1为作废操作，2为客审操作，3为财审',
  `bvl_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核结果，0新增记录，1为审核通过,2未审核通过',
  `bvl_create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`bvl_id`),
  KEY `u_id` (`u_id`) USING BTREE,
  KEY `bvl_type` (`bvl_type`) USING BTREE,
  KEY `bvl_status` (`bvl_status`) USING BTREE,
  KEY `bvl_create_time` (`bvl_create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='红包调整单审核日志';
DROP TABLE IF EXISTS `fx_bonus_info`;
CREATE TABLE `fx_bonus_info` (
  `bn_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '红包调整单ID',
  `bn_sn` bigint(20) NOT NULL COMMENT '红包调整单单据编号，当前时间戳+6位ID不足补0',
  `bt_id` int(11) NOT NULL DEFAULT '0' COMMENT '红包类型ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员名',
  `bn_money` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '调整金额',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '制单人',
  `bn_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '调整类型：0为收入，1为支出，2为冻结',
  `o_id` bigint(20) DEFAULT NULL COMMENT '订单号',
  `or_id` bigint(20) DEFAULT NULL COMMENT '退款单号',
  `pc_serial_number` varchar(100) NOT NULL DEFAULT '' COMMENT '充值卡流水号',
  `bn_verify_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '作废状态：0未作废,1已确认,2已作废',
  `bn_service_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客审状态：0未审核，1已审核',
  `bn_finance_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '财审状态：0未审核，1已审核',
  `bn_desc` varchar(255) DEFAULT NULL COMMENT '备注',
  `bn_order` int(5) NOT NULL DEFAULT '10' COMMENT '排序（数字越大排序靠前）',
  `bn_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录制单时间',
  `bn_update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  `single_type` tinyint(1) DEFAULT '1' COMMENT '制单类型：1.系统管理员制单，2.用户制单',
  PRIMARY KEY (`bn_id`),
  UNIQUE KEY `bn_sn` (`bn_sn`) USING BTREE,
  KEY `bt_id` (`bt_id`) USING BTREE,
  KEY `o_id` (`o_id`) USING BTREE,
  KEY `or_id` (`or_id`) USING BTREE,
  KEY `bn_type` (`bn_type`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `bn_order` (`bn_order`) USING BTREE,
  KEY `bn_finance_verify` (`bn_finance_verify`) USING BTREE,
  KEY `bn_verify_status` (`bn_verify_status`) USING BTREE,
  KEY `bn_service_verify` (`bn_service_verify`) USING BTREE,
  KEY `bn_create_time` (`bn_create_time`) USING BTREE,
  KEY `bn_update_time` (`bn_update_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COMMENT='红包调整单表';
DROP TABLE IF EXISTS `fx_lottery`;
CREATE TABLE `fx_lottery` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_name` varchar(50) NOT NULL COMMENT '抽奖名称',
  `l_desc` text COMMENT '抽奖描述(活动规则)',
  `l_start_time` datetime NOT NULL COMMENT '抽奖开始时间',
  `l_end_time` datetime NOT NULL COMMENT '抽奖结束时间',
  `l_create_time` datetime NOT NULL COMMENT '创建时间',
  `l_update_time` datetime NOT NULL COMMENT '更新时间',
  `l_number` int(11) NOT NULL DEFAULT '0' COMMENT '每日限购数量',
  `is_consume_pont` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用抽奖消耗积分',
  `consume_point` int(11) NOT NULL DEFAULT '0' COMMENT '每次抽奖消耗多少积分',
  `is_deleted` tinyint(255) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `l_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用（0：不启用，1：启用）',
  `l_detail` text COMMENT '促销规则设置',
  PRIMARY KEY (`l_id`),
  KEY `l_start_time` (`l_start_time`),
  KEY `l_end_time` (`l_end_time`),
  KEY `is_consume_pont` (`is_consume_pont`),
  KEY `is_deleted` (`is_deleted`),
  KEY `status` (`l_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抽奖表';
DROP TABLE IF EXISTS `fx_lottery_user`;
CREATE TABLE `fx_lottery_user` (
  `ul_id` int(11) NOT NULL AUTO_INCREMENT,
  `ul_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖品类型（0:未中奖；1:红包 2:神秘大奖）',
  `l_id` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖活动ID',
  `bn_id` int(11) NOT NULL DEFAULT '0' COMMENT '抽中红包对应红包调整单',
  `ul_bonus_money` decimal(10,3) DEFAULT '0.000' COMMENT '类型为红包是红包金额',
  `ul_title` varchar(50) DEFAULT NULL COMMENT '类型为神秘大奖是奖品内容',
  `is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已被抽中',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖会员',
  `ul_create_time` datetime NOT NULL COMMENT '创建时间',
  `ul_update_time` datetime NOT NULL COMMENT '修改时间',
  `ul_confirm_time` datetime NOT NULL COMMENT '抽奖时间',
  PRIMARY KEY (`ul_id`),
  KEY `ul_type` (`ul_type`),
  KEY `l_id` (`l_id`),
  KEY `bn_id` (`bn_id`),
  KEY `is_used` (`is_used`),
  KEY `m_id` (`m_id`),
  KEY `ul_confirm_time` (`ul_confirm_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='奖品表';
ALTER TABLE `fx_members` ADD COLUMN `m_bonus`  decimal(10,3) NOT NULL COMMENT '红包金额';
DROP TABLE IF EXISTS `fx_lottery_log`;
CREATE TABLE `fx_lottery_log` (
  `ll_id` int(11) NOT NULL AUTO_INCREMENT,
  `ll_create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `ul_id` int(11) DEFAULT '0' COMMENT '如果中奖中奖ID',
  `m_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `ll_desc` varchar(200) DEFAULT NULL COMMENT '备注',
  `l_id` int(11) NOT NULL DEFAULT '0' COMMENT '抽奖类型ID',
  PRIMARY KEY (`ll_id`),
  KEY `ol_create_time` (`ll_create_time`),
  KEY `ul_id` (`ul_id`),
  KEY `m_id` (`m_id`),
  KEY `l_id` (`l_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抽奖日志表';
DROP TABLE IF EXISTS `fx_related_coupon_red`;
CREATE TABLE `fx_related_coupon_red` (
  `c_name` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `rd_id` int(11) NOT NULL DEFAULT '0' COMMENT '规则id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券与规则关联表';
DROP TABLE IF EXISTS `fx_red_enevlope`;
CREATE TABLE `fx_red_enevlope` (
  `rd_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `rd_name` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券规则名称',
  `rd_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动开始时间',
  `rd_end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动结束时间',
  `rd_is_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用',
  `rd_title` varchar(150) NOT NULL DEFAULT '' COMMENT '规则页面标题',
  `rd_keywords` varchar(200) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `rd_description` varchar(200) NOT NULL DEFAULT '' COMMENT 'SEO商品描述',
  `rd_memo` varchar(255) NOT NULL DEFAULT '' COMMENT '规则备注',
  PRIMARY KEY (`rd_id`),
  UNIQUE KEY `rd_name` (`rd_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='优惠券活动表';
ALTER TABLE `fx_members_fields` MODIFY COLUMN `fields_content` varchar(20) NOT NULL COMMENT '类型内容';
ALTER TABLE `fx_members_fields` ADD KEY `field_name` (`field_name`);
ALTER TABLE `fx_members_fields` ADD KEY `fields_content` (`fields_content`);
ALTER TABLE `fx_coupon` ADD COLUMN `c_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '优惠券类型（0：现金券，1:折扣券）';
ALTER TABLE `fx_coupon` ADD KEY `c_type` (`c_type`);
ALTER TABLE `fx_coupon` ADD KEY `c_name` (`c_name`);
ALTER TABLE `fx_coupon` ADD KEY `c_order_id` (`c_order_id`);
ALTER TABLE `fx_coupon` MODIFY COLUMN `c_order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '使用订单id';
ALTER TABLE `fx_related_coupon_red` ADD KEY `c_name` (`c_name`);
ALTER TABLE `fx_related_coupon_red` ADD  KEY `rd_id` (`rd_id`);
ALTER TABLE `fx_goods_category` ADD COLUMN `gc_is_hot`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '类目是否热销展示';
ALTER TABLE `fx_goods_category` ADD COLUMN `gc_pic_url`  varchar(255) NOT NULL DEFAULT '' COMMENT '类目图片地址';
ALTER TABLE `fx_goods_category` ADD COLUMN `gc_type`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '类目所属类型 0为默认普通分类 1为楼层属性 2为店铺属性';
ALTER TABLE `fx_goods_category` ADD KEY `gc_type` (`gc_type`);
ALTER TABLE `fx_members` ADD COLUMN `m_type`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员类型：1批发商,2供货商,0普通会员';
ALTER TABLE `fx_orders` ADD COLUMN `o_bonus_money`  decimal(10,3) NOT NULL COMMENT '订单使用红包金额';
ALTER TABLE `fx_goods` ADD COLUMN `gm_id`   int(11) NOT NULL DEFAULT '0' COMMENT '商品用户ID';
ALTER TABLE `fx_members` ADD COLUMN `m_card_no`  varchar(36) NOT NULL COMMENT '会员卡卡号';
ALTER TABLE `fx_members` ADD COLUMN `m_ali_card_no`  varchar(36) NOT NULL COMMENT '阿里会员卡卡号';
DROP TABLE IF EXISTS `fx_sms_log`;
CREATE TABLE `fx_sms_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '客服名称，仅用于后台显示用',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态 0,未成功 1,成功',
  `check_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '认证状态 0,未认证 1,已认证 2,无效',
  `code` text NOT NULL COMMENT '验证码',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `sms_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '0:普通发送短信；1：手机绑定；2：更换手机号；3：密码找回 4:重置密码 5:支付验证' ,
  PRIMARY KEY (`id`),
  INDEX `mobile` (`mobile`) USING BTREE ,
  INDEX `status` (`status`) USING BTREE ,
  INDEX `check_status` (`check_status`) USING BTREE ,
  INDEX `sms_type` (`sms_type`) USING BTREE,
  INDEX `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信日志表';
DROP TABLE IF EXISTS `fx_sms_templates`;
CREATE TABLE `fx_sms_templates` (
  `id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板id',
  `code` varchar(30) NOT NULL DEFAULT '' COMMENT '模板编码',
  `subject` varchar(200) NOT NULL DEFAULT '' COMMENT '模板标题',
  `content` text NOT NULL COMMENT '模板内容',
  `last_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '模板修改时间',
  `last_send` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '模板发送时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `fx_sms_templates` VALUES ('1', 'FORGET_PASSWORD', '忘记密码发送验证码', '验证码为:{$authnum},请登录网站,及时验证,谢谢.{$shop_name}', '2014-03-17 11:26:40', '2014-03-17 11:26:40');
INSERT INTO `fx_sms_templates` VALUES ('2', 'REGISTER_CODE', '账号注册发送验证码', '手机验证码为:{$authnum},及时验证,谢谢.{$shop_name}', '2014-08-05 16:36:11', '2014-03-17 11:26:40');
INSERT INTO `fx_sms_templates` VALUES ('3', 'SEND_PASSWORD', '忘记密码重置密码', '密码重置为:{$authnum},请登录网站及时修改密码,谢谢.{$shop_name}', '2014-08-06 14:30:28', '2014-08-06 14:30:36');
INSERT INTO `fx_sms_templates` VALUES ('4', 'MODIFY_MOBILE', '手机更换', '验证码为:{$authnum},请登录网站,及时验证,谢谢.{$shop_name}', '2014-08-06 14:31:41', '2014-08-06 14:31:44');
INSERT INTO `fx_sms_templates` VALUES ('5', 'PAY_CODE', '支付发送验证码', '支付验证码为:{$authnum},请验证支付,谢谢.{$shop_name}', '2014-08-06 14:31:41', '2014-08-06 14:31:41');
INSERT INTO `fx_sms_templates` VALUES ('6', 'BUY_CODE', '提现发送验证码', '提现验证码为:{$authnum},请验证后提现,谢谢.{$shop_name}', '2014-08-06 14:31:41', '2014-08-06 14:31:41');
ALTER TABLE `fx_orders` ADD COLUMN `is_anonymous` tinyint(4) DEFAULT '0' COMMENT '是否是匿名购买(1：是）';
DROP TABLE IF EXISTS `fx_email_templates`;
CREATE TABLE `fx_email_templates` (
  `id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板id',
  `code` varchar(30) NOT NULL DEFAULT '' COMMENT '模板编码',
  `subject` varchar(200) NOT NULL DEFAULT '' COMMENT '模板标题',
  `content` text NOT NULL COMMENT '模板内容',
  `last_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '模板修改时间',
  `last_send` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '模板发送时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `fx_email_templates` VALUES ('1', 'SEND_PASSWORD', '忘记密码邮件通知', '{$user_name}您好！  您已经进行了密码重置的操作，本链接有效期为10分钟，并且只能使用1次,请点击以下链接(或者复制到您的浏览器):{$reset_email}以确认您的新密码重置操作！{$shop_name}{$send_date}', '2014-08-07 08:57:36', '2014-08-07 08:57:36');
INSERT INTO `fx_email_templates` VALUES ('2', 'VALIDATE_EMAIL', '邮箱验证', '{$user_name}您好！  您已经进行了邮箱验证的操作，本链接有效期为10分钟，并且只能使用1次,请点击以下链接(或者复制到您的浏览器):{$reset_email}以确认您的邮箱操作！{$shop_name}{$send_date}', '2014-08-07 08:57:36', '2014-08-07 08:57:36');
DROP TABLE IF EXISTS `fx_email_log`;
CREATE TABLE `fx_email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT 'email',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态 0,未成功 1,成功',
  `check_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '认证状态 0,未认证 1,已认证 2,无效',
  `code` text NOT NULL COMMENT '验证码',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `email_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:普通邮件；1:忘记密码 2:邮件验证 3:重置密码',
  PRIMARY KEY (`id`),
  KEY `email` (`email`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `check_status` (`check_status`) USING BTREE,
  KEY `email_type` (`email_type`) USING BTREE,
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='邮件日志表';
ALTER TABLE `fx_members` ADD COLUMN `m_cards`  decimal(10,3) NOT NULL COMMENT '会员储蓄卡余额';
ALTER TABLE `fx_members` ADD COLUMN `m_jlb`  decimal(10,3) NOT NULL COMMENT '会员的金币余额';
ALTER TABLE `fx_orders` ADD COLUMN `o_cards_money`  decimal(10,3) NOT NULL COMMENT '订单使用储蓄卡支付的金额';
ALTER TABLE `fx_orders` ADD COLUMN `o_jlb_money`  decimal(10,3) NOT NULL COMMENT '订单使用金币金额';
DROP TABLE IF EXISTS `fx_cards_type`;
CREATE TABLE `fx_cards_type` (
  `ct_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '储值卡类型ID',
  `ct_code` int(5) NOT NULL COMMENT '储值卡类型代码',
  `ct_name` varchar(50) NOT NULL DEFAULT '' COMMENT '类型名称',
  `ct_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '类型描述',
  `ct_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型状态：0为停用，1为启用',
  `ct_orderby` int(5) NOT NULL DEFAULT '10' COMMENT '类型排序，数值越大越靠前',
  `ct_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `ct_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录最后更新时间',
  PRIMARY KEY (`ct_id`),
  UNIQUE KEY `ct_code` (`ct_code`) USING BTREE,
  KEY `ct_status` (`ct_status`) USING BTREE,
  KEY `ct_orderby` (`ct_orderby`) USING BTREE,
  KEY `ct_create_time` (`ct_create_time`) USING BTREE,
  KEY `ct_update_time` (`ct_update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='储值卡类型表';
INSERT INTO `fx_cards_type` VALUES ('1', '1000', '储值卡充值', '储值卡充值', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
INSERT INTO `fx_cards_type` VALUES ('2', '1001', '消费储值卡', '消费储值卡', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
DROP TABLE IF EXISTS `fx_cards_verify_log`;
CREATE TABLE `fx_cards_verify_log` (
  `cvl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `u_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人名',
  `ci_sn` bigint(20) NOT NULL COMMENT '储值卡单据编号',
  `cvl_desc` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `cvl_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作类型，0为新增操作，1为作废操作，2为客审操作，3为财审',
  `cvl_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核结果，0新增记录，1为审核通过,2未审核通过',
  `cvl_create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`cvl_id`),
  KEY `u_id` (`u_id`) USING BTREE,
  KEY `cvl_type` (`cvl_type`) USING BTREE,
  KEY `cvl_status` (`cvl_status`) USING BTREE,
  KEY `cvl_create_time` (`cvl_create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='储值卡调整单审核日志';
DROP TABLE IF EXISTS `fx_cards_info`;
CREATE TABLE `fx_cards_info` (
  `ci_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '储值卡调整单ID',
  `ci_sn` bigint(20) NOT NULL COMMENT '储值卡调整单单据编号，当前时间戳+6位ID不足补0',
  `ct_id` int(11) NOT NULL DEFAULT '0' COMMENT '储值卡类型ID',
  `ct_num` varchar(20) NOT NULL DEFAULT '' COMMENT '储值卡卡号',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员名',
  `ci_money` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '调整金额',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '制单人',
  `ci_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '调整类型：0为收入，1为支出，2为冻结',
  `o_id` bigint(20) DEFAULT NULL COMMENT '订单号',
  `or_id` bigint(20) DEFAULT NULL COMMENT '退款单号',
  `pc_serial_number` varchar(100) NOT NULL DEFAULT '' COMMENT '充值卡流水号',
  `ci_verify_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '作废状态：0未作废,1已确认,2已作废',
  `ci_service_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客审状态：0未审核，1已审核',
  `ci_finance_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '财审状态：0未审核，1已审核',
  `ci_desc` varchar(255) DEFAULT NULL COMMENT '备注',
  `ci_order` int(5) NOT NULL DEFAULT '10' COMMENT '排序（数字越大排序靠前）',
  `ci_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录制单时间',
  `ci_update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  `single_type` tinyint(1) DEFAULT '1' COMMENT '制单类型：1.系统管理员制单，2.用户制单',
  PRIMARY KEY (`ci_id`),
  UNIQUE KEY `ci_sn` (`ci_sn`) USING BTREE,
  KEY `ct_id` (`ct_id`) USING BTREE,
  KEY `o_id` (`o_id`) USING BTREE,
  KEY `or_id` (`or_id`) USING BTREE,
  KEY `ci_type` (`ci_type`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `ci_order` (`ci_order`) USING BTREE,
  KEY `ci_finance_verify` (`ci_finance_verify`) USING BTREE,
  KEY `ci_verify_status` (`ci_verify_status`) USING BTREE,
  KEY `ci_service_verify` (`ci_service_verify`) USING BTREE,
  KEY `ci_create_time` (`ci_create_time`) USING BTREE,
  KEY `ci_update_time` (`ci_update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='储值卡调整单表';
DROP TABLE IF EXISTS `fx_jlb_type`;
CREATE TABLE `fx_jlb_type` (
  `jt_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '金币类型ID',
  `jt_code` int(5) NOT NULL COMMENT '金币类型代码',
  `jt_name` varchar(50) NOT NULL DEFAULT '' COMMENT '类型名称',
  `jt_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '类型描述',
  `jt_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型状态：0为停用，1为启用',
  `jt_orderby` int(5) NOT NULL DEFAULT '10' COMMENT '类型排序，数值越大越靠前',
  `jt_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `jt_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录最后更新时间',
  PRIMARY KEY (`jt_id`),
  UNIQUE KEY `jt_code` (`jt_code`) USING BTREE,
  KEY `jt_status` (`jt_status`) USING BTREE,
  KEY `jt_orderby` (`jt_orderby`) USING BTREE,
  KEY `jt_create_time` (`jt_create_time`) USING BTREE,
  KEY `jt_update_time` (`jt_update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='金币类型表';
INSERT INTO `fx_jlb_type` VALUES ('1', '1000', '金币充值', '金币充值', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
INSERT INTO `fx_jlb_type` VALUES ('2', '1002', '消费金币', '消费金币', '1', '10', '2014-07-11 11:43:03', '2014-07-11 11:43:03');
DROP TABLE IF EXISTS `fx_jlb_verify_log`;
CREATE TABLE `fx_jlb_verify_log` (
  `jvl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `u_name` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人名',
  `ji_sn` bigint(20) NOT NULL COMMENT '金币单据编号',
  `jvl_desc` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `jvl_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作类型，0为新增操作，1为作废操作，2为客审操作，3为财审',
  `jvl_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核结果，0新增记录，1为审核通过,2未审核通过',
  `jvl_create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`jvl_id`),
  KEY `u_id` (`u_id`) USING BTREE,
  KEY `jvl_type` (`jvl_type`) USING BTREE,
  KEY `jvl_status` (`jvl_status`) USING BTREE,
  KEY `jvl_create_time` (`jvl_create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='金币调整单审核日志';
DROP TABLE IF EXISTS `fx_jlb_info`;
CREATE TABLE `fx_jlb_info` (
  `ji_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '金币调整单ID',
  `ji_sn` bigint(20) NOT NULL COMMENT '金币调整单单据编号，当前时间戳+6位ID不足补0',
  `jt_id` int(11) NOT NULL DEFAULT '0' COMMENT '金币类型ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员名',
  `ji_money` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '调整金额',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '制单人',
  `ji_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '调整类型：0为收入，1为支出，2为冻结',
  `o_id` bigint(20) DEFAULT NULL COMMENT '订单号',
  `or_id` bigint(20) DEFAULT NULL COMMENT '退款单号',
  `pc_serial_number` varchar(100) NOT NULL DEFAULT '' COMMENT '充值卡流水号',
  `ji_verify_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '作废状态：0未作废,1已确认,2已作废',
  `ji_service_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客审状态：0未审核，1已审核',
  `ji_finance_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '财审状态：0未审核，1已审核',
  `ji_desc` varchar(255) DEFAULT NULL COMMENT '备注',
  `ji_order` int(5) NOT NULL DEFAULT '10' COMMENT '排序（数字越大排序靠前）',
  `ji_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录制单时间',
  `ji_update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  `single_type` tinyint(1) DEFAULT '1' COMMENT '制单类型：1.系统管理员制单，2.用户制单',
  PRIMARY KEY (`ji_id`),
  UNIQUE KEY `ji_sn` (`ji_sn`) USING BTREE,
  KEY `jt_id` (`jt_id`) USING BTREE,
  KEY `o_id` (`o_id`) USING BTREE,
  KEY `or_id` (`or_id`) USING BTREE,
  KEY `ji_type` (`ji_type`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `ji_order` (`ji_order`) USING BTREE,
  KEY `ji_finance_verify` (`ji_finance_verify`) USING BTREE,
  KEY `ji_verify_status` (`ji_verify_status`) USING BTREE,
  KEY `ji_service_verify` (`ji_service_verify`) USING BTREE,
  KEY `ji_create_time` (`ji_create_time`) USING BTREE,
  KEY `ji_update_time` (`ji_update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='金币调整单表';
ALTER TABLE `fx_orders` ADD COLUMN `o_point_money`  decimal(10,3) NOT NULL COMMENT '订单使用积分抵扣的金额';
ALTER TABLE `fx_goods_info` ADD COLUMN `gifts_point`  int(10) NOT NULL DEFAULT '0' COMMENT '购买商品赠送积分数';
ALTER TABLE `fx_orders` ADD COLUMN `o_reward_jlb`  int(10) NOT NULL DEFAULT '0' COMMENT '订单商品促销赠送金币';
DROP TABLE IF EXISTS `fx_points_level`;
CREATE TABLE `fx_points_level` (
  `pl_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '积分等级ID',
  `pl_code` varchar(50) NOT NULL DEFAULT '' COMMENT '积分等级代码',
  `pl_name` varchar(50) NOT NULL DEFAULT '' COMMENT '积分等级名称',
  `pl_discount` float(8,3) NOT NULL COMMENT '积分等级倍数',
  `pl_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为积分默认等级，0为否，1为是',
  `pl_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，0为废弃，1为有效',
  `pl_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `pl_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  `pl_erp_guid` varchar(50) NOT NULL DEFAULT '' COMMENT 'erp积分等级guid',
  `pl_order` int(4) NOT NULL DEFAULT '0' COMMENT '积分等级排序字段，越大越靠前，默认为0',
  `pl_up_fee` int(10) NOT NULL DEFAULT '0' COMMENT '晋升要求',
  PRIMARY KEY (`pl_id`),
  UNIQUE KEY `pl_name` (`pl_name`) USING BTREE,
  KEY `pl_status` (`pl_status`) USING BTREE,
  KEY `pl_create_time` (`pl_create_time`) USING BTREE,
  KEY `pl_update_time` (`pl_update_time`) USING BTREE,
  KEY `pl_default` (`pl_default`) USING BTREE,
  KEY `pl_erp_guid` (`pl_erp_guid`) USING BTREE,
  KEY `pl_up_fee` (`pl_up_fee`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='积分等级表';
ALTER TABLE `fx_goods` ADD COLUMN `taobao_id` varchar(50) NOT NULL DEFAULT '' COMMENT '淘宝商家编码ID';
ALTER TABLE `fx_goods_products` ADD COLUMN `taobao_sku_id` varchar(50) NOT NULL DEFAULT '' COMMENT '淘宝商品规格编码ID';
ALTER TABLE `fx_goods_brand` ADD COLUMN `gb_sn` varchar(50) NOT NULL DEFAULT '' COMMENT '品牌编码';
ALTER TABLE `fx_balance_info` MODIFY `bi_money` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '调整金额';
ALTER TABLE `fx_recharge_examine` MODIFY `re_money` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '调整金额';
DROP TABLE IF EXISTS `fx_try`;
CREATE TABLE `fx_try` (
  `try_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '试用自增ID',
  `try_title` varchar(50) NOT NULL COMMENT '试用标题',
  `try_start_time` datetime NOT NULL COMMENT '试用开始时间',
  `try_end_time` datetime NOT NULL COMMENT '试用结束时间',
  `try_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:停用；1:启用 2:删除',
  `try_create_time` datetime NOT NULL COMMENT '创建时间',
  `try_update_time` datetime NOT NULL COMMENT '修改时间',
  `g_id` int(20) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `g_sn` varchar(50) NOT NULL COMMENT '商品编码',
  `try_picture` varchar(200) DEFAULT NULL COMMENT '试用图片',
  `try_desc` text COMMENT '试用描述',
  `try_now_num` int(11) NOT NULL DEFAULT '0' COMMENT '已申请试用数量',
  `try_order` tinyint(4) NOT NULL DEFAULT '0' COMMENT '显示此讯',
  `property_typeid` int(11) NOT NULL COMMENT '关联类型ID（问卷调查时试用)',
  `try_is_show_detail` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否显示商品详情',
  `try_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '试用数量',
  `property_typeid_front` int(11) NOT NULL DEFAULT '0' COMMENT '关联类型ID（申请试用时调用)',
  PRIMARY KEY (`try_id`),
  KEY `g_id` (`g_id`),
  KEY `property_typeid` (`property_typeid`),
  KEY `try_status` (`try_status`),
  KEY `try_start_time` (`try_start_time`),
  KEY `try_end_time` (`try_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试用表';
DROP TABLE IF EXISTS `fx_try_apply_records`;
CREATE TABLE `fx_try_apply_records` (
  `tar_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '申请会员ID',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '申请商品ID',
  `property_typeid` int(11) NOT NULL DEFAULT '0' COMMENT '试用类型ID',
  `property_typeid_front` int(11) NOT NULL DEFAULT '0' COMMENT '关联类型ID（申请试用时调用)',
  `o_receiver_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `o_receiver_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '收货人手机',
  `o_receiver_telphone` varchar(20) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `o_receiver_state` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人省份',
  `o_receiver_city` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人城市',
  `o_receiver_county` varchar(50) NOT NULL DEFAULT '' COMMENT '地区第三级（文字）',
  `o_receiver_address` varchar(200) NOT NULL DEFAULT '' COMMENT '收货人地址',
  `ra_id` int(10) NOT NULL DEFAULT '0' COMMENT '收货地址id（最后一级id）',
  `o_receiver_zipcode` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人邮编',
  `tar_create_time` datetime NOT NULL COMMENT '新增时间',
  `tar_update_time` datetime NOT NULL COMMENT '修改时间',
  `try_oid` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单号',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `try_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:申请中 1:审核通过 2:审核不通过',
  `try_apply_reason` varchar(255) DEFAULT NULL COMMENT '申请理由',
  PRIMARY KEY (`tar_id`),
  KEY `m_id` (`m_id`),
  KEY `g_id` (`g_id`),
  KEY `property_typeid` (`property_typeid`),
  KEY `try_oid` (`try_oid`),
  KEY `try_status` (`try_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试用申请记录表';
DROP TABLE IF EXISTS `fx_try_attribute`;
CREATE TABLE `fx_try_attribute` (
  `ta_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增',
  `try_apply_id` int(11) NOT NULL DEFAULT '0' COMMENT '试用申请ID',
  `property_typeid` int(11) NOT NULL DEFAULT '0' COMMENT '试用类型ID',
  `attr_id` int(11) NOT NULL DEFAULT '0' COMMENT '属性ID',
  `attr_value` text NOT NULL COMMENT '属性值',
  `attr_name` varchar(50) NOT NULL COMMENT '属性名称',
  PRIMARY KEY (`ta_id`),
  KEY `try_apply_id` (`try_apply_id`),
  KEY `property_typeid` (`property_typeid`),
  KEY `attr_id` (`attr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `fx_try_report`;
CREATE TABLE `fx_try_report` (
  `tr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增',
  `try_id` int(11) NOT NULL DEFAULT '0' COMMENT '试用ID',
  `property_typeid` int(11) NOT NULL DEFAULT '0' COMMENT '试用类型ID',
  `tr_create_time` datetime NOT NULL COMMENT '创建时间',
  `tr_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最后更新时间',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '报告人',
  `tr_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '报告状态 0:未审核 1:已审核',
  PRIMARY KEY (`tr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试用报告';
DROP TABLE IF EXISTS `fx_try_report_attribute`;
CREATE TABLE `fx_try_report_attribute` (
  `ta_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增',
  `try_apply_id` int(11) NOT NULL DEFAULT '0' COMMENT '试用申请ID',
  `property_typeid` int(11) NOT NULL DEFAULT '0' COMMENT '试用类型ID',
  `attr_id` int(11) NOT NULL DEFAULT '0' COMMENT '属性ID',
  `attr_value` text NOT NULL COMMENT '属性值',
  `attr_name` varchar(50) NOT NULL COMMENT '属性名称',
  PRIMARY KEY (`ta_id`),
  KEY `try_apply_id` (`try_apply_id`),
  KEY `property_typeid` (`property_typeid`),
  KEY `attr_id` (`attr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `fx_related_try_ads`;
CREATE TABLE `fx_related_try_ads` (
  `rta_id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_url` varchar(255) NOT NULL COMMENT '链接地址',
  `sort_order` tinyint(2) NOT NULL DEFAULT '0' COMMENT '排序',
  `ad_pic_url` varchar(255) NOT NULL COMMENT '图片链接地址',
  PRIMARY KEY (`rta_id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='试用广告图片表';
ALTER TABLE `fx_goods` ADD COLUMN `g_art_no`   varchar(50) NOT NULL DEFAULT '' COMMENT '对应线下长益商品货号';
alter table `fx_members` add column `m_id_card` varchar(20) not null default '' comment '身份证号';
alter table `fx_members_verify` add column `m_id_card` varchar(20) not null default '' comment '身份证号';
INSERT INTO `fx_members_fields` VALUES (19,'身份证号',1,1,0,1,0,0,1,'text','m_id_card',1);
alter table fx_members modify m_update_time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' comment '记录最后更新时间';
alter table `fx_goods_spec` modify column `gs_input_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '输入类型 1文本输入 2选择 3文本域输入 4评分';
alter table `fx_goods_type` add column `gt_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0商品属性，1商品试用属性';
DROP TABLE IF EXISTS `fx_member_relation`;
CREATE TABLE `fx_member_relation` (
  `mr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `m_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分销商id',
  `mr_path` varchar(100) NOT NULL DEFAULT '' COMMENT '路径',
  `mr_p_id` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
  `mr_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `mr_child_count` int(11) NOT NULL DEFAULT '0' COMMENT '子节点数量',
  `mr_depth` int(11) NOT NULL DEFAULT '0' COMMENT '深度',
  PRIMARY KEY (`mr_id`),
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `mr_p_id` (`mr_p_id`) USING BTREE,
  KEY `mr_order` (`mr_order`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 COMMENT='分销商关系表';
DROP TABLE IF EXISTS `fx_member_sales_set`;
CREATE TABLE `fx_member_sales_set` (
  `mss_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `m_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分销商id',
  `mss_time_begin` datetime NOT NULL COMMENT '开始日期',
  `mss_time_end` datetime NOT NULL COMMENT '结束日期',
  `mss_sales` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '返利额度',
  PRIMARY KEY (`mss_id`),
  KEY `m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 COMMENT='销售额设定表';
DROP TABLE IF EXISTS `fx_member_payback`;
CREATE TABLE `fx_member_payback` (
  `saas_id` int(11) NOT NULL DEFAULT '0' COMMENT 'SAAS ID',
  `m_p_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '分销商id',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `m_o_id` int(11) NOT NULL DEFAULT '0' COMMENT '返利对象id',
  `m_p_amount` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '设置返利金额',
  `pdt_id` int(11) NOT NULL DEFAULT '0' COMMENT '货品id',
  PRIMARY KEY (`m_p_id`),
  KEY `m_id` (`m_id`),
  KEY `m_o_id` (`m_o_id`),
  KEY `g_id` (`g_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 COMMENT='BASE:返利 表';
DROP TABLE IF EXISTS `fx_member_payback_statistics`;
CREATE TABLE `fx_member_payback_statistics` (
  `mps_id` int(11) NOT NULL AUTO_INCREMENT,
  `oi_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应order_items表的oi_id',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应members表m_id',
  `m_o_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应member_payback表的m_o_id',
  `pdt_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应order_items表pdt_id,货品ID',
  `mps_payback_amount` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '返利金额',
  `mps_description` varchar(100) DEFAULT NULL COMMENT '返利规则描述信息,member_payback',
  PRIMARY KEY (`mps_id`),
  KEY `mps_index` (`oi_id`,`m_id`,`pdt_id`,`mps_payback_amount`)
) ENGINE=InnoDB AUTO_INCREMENT=1 COMMENT='分销商返利记录表';
DROP TABLE IF EXISTS `fx_member_differ_price_rebates_record`;
CREATE TABLE `fx_member_differ_price_rebates_record` (
  `mdprr_id` int(10) NOT NULL AUTO_INCREMENT,
  `mdprr_pm_id` int(10) NOT NULL DEFAULT '0' COMMENT '上级分销商id',
  `mdprr_pm_name` varchar(50) NOT NULL DEFAULT '' COMMENT '父级分销商名称',
  `m_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `m_name` varchar(50) NOT NULL DEFAULT '' COMMENT '下级分销商名称',
  `o_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单号',
  `oi_id` int(10) NOT NULL DEFAULT '0' COMMENT '订单明细id',
  `g_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '商品编号',
  `pdt_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '货品编号',
  `mdprr_nums` int(10) NOT NULL DEFAULT '0' COMMENT '返利商品个数',
  `oi_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '下单时商品的价格',
  `mdprr_pm_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '父级分销商的会员价',
  `mdprr_differ_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '商品差价（和上级分销商之间的差价）',
  `mdprr_theory_rebates_amount` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '理论应返利的金额',
  `mdprr_actual_rebates_amount` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '实际返利金额，当返利计算出现异常时实际返利金额可能为0',
  `mdprr_is_unusual` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '返利是否异常，1：异常，0：正常',
  `mdprr_create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录生成时间',
  `mdprr_modify_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录最后修改时间',
  PRIMARY KEY (`mdprr_id`),
  KEY `mdprr_pm_id` (`mdprr_pm_id`),
  KEY `m_id` (`m_id`),
  KEY `orderkey` (`o_id`,`oi_id`),
  KEY `g_sn` (`g_sn`),
  KEY `pdt_sn` (`pdt_sn`),
  KEY `mdprr_is_unusual` (`mdprr_is_unusual`),
  KEY `mdprr_create_time` (`mdprr_create_time`),
  KEY `mdprr_pm_name` (`mdprr_pm_name`),
  KEY `m_name` (`m_name`),
  KEY `mdprr_modify_time` (`mdprr_modify_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1  COMMENT='差价返利记录表';
DROP TABLE IF EXISTS `fx_inventory_lock`;
CREATE TABLE `fx_inventory_lock` (
  `iny_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
  `iny_num` int(11) NOT NULL DEFAULT '0' COMMENT '分配数量',
  PRIMARY KEY (`iny_id`),
  KEY `g_id` (`g_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='BASE:库存锁定/买断 表';
DROP TABLE IF EXISTS `fx_inventory_pdt_lock`;
CREATE TABLE `fx_inventory_pdt_lock` (
  `iny_pdt_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT'自增id',
  `iny_id` int(11) NOT NULL DEFAULT '0' COMMENT 'lock_id',
  `pdt_id` int(11) NOT NULL DEFAULT '0' COMMENT '货品id',
  `ipl_num` int(11) NOT NULL DEFAULT '0' COMMENT '货品库存分量',
  `ipl_num_frozen` int(11) NOT NULL DEFAULT '0' COMMENT '货品冻结库存分量',
  `iny_expired_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '过期时间',
  `iny_is_payed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为买断(0为锁定,1为买断)',
  `ipl_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录更新时间',
  PRIMARY KEY (`iny_pdt_id`),
  KEY `iny_expired_time` (`iny_expired_time`) USING BTREE,
  KEY `iny_is_payed` (`iny_is_payed`) USING BTREE,
  KEY `iny_id` (`iny_id`) USING BTREE,
  KEY `pdt_id` (`pdt_id`) USING BTREE,
  KEY `ipl_num` (`ipl_num`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='BASE:货品库存锁定/买断 表';
DROP TABLE IF EXISTS `fx_stock_inventory_lock_detail`;
CREATE TABLE `fx_stock_inventory_lock_detail` (
  `sild_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分销商库存调整单明细ID',
  `srr_id` int(11) NOT NULL DEFAULT '0' COMMENT '分销商库存调整单ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '分销商ID',
  `pdt_id` int(11) NOT NULL DEFAULT '0' COMMENT '被调整的规格ID',
  `sild_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分销商调整类型：增加/减少，0为增加，1为减少',
  `sild_num` int(11) NOT NULL DEFAULT '0' COMMENT '变更数量',
  `sild_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '记录类型，0为删除，1为正常',
  `sild_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `sild_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录更新时间',
  PRIMARY KEY (`sild_id`),
  KEY `srr_id` (`srr_id`) USING BTREE,
  KEY `pdt_id` (`pdt_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `sild_type` (`sild_type`) USING BTREE,
  KEY `sild_status` (`sild_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分销商库存调整单明细表';
DROP TABLE IF EXISTS `fx_stock_inventory_lock_modify_log`;
CREATE TABLE `fx_stock_inventory_lock_modify_log` (
  `silml_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID，主键自增',
  `srr_id` int(11) NOT NULL DEFAULT '0' COMMENT '调整单据ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '分销商ID',
  `silml_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作类型，0为单据新建，1为单据修改，2为审核通过，3为作废，4为明细操作',
  `silml_detail_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '明细操作类型，当且仅当silml_type等于4时，此字段有效，0为新增明细，1为修改明细，2为删除明细，3为明细审核通过',
  `sild_id` int(11) NOT NULL DEFAULT '0' COMMENT '明细操作涉及的明细ID',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `srrml_create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '日志记录时间',
  PRIMARY KEY (`silml_id`),
  KEY `srr_id` (`srr_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `silml_type` (`silml_type`) USING BTREE,
  KEY `silml_detail_type` (`silml_detail_type`) USING BTREE,
  KEY `sild_id` (`sild_id`) USING BTREE,
  KEY `u_id` (`u_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分销商库存调整单日志表';
ALTER TABLE `fx_related_authorize` ADD COLUMN `ra_gp_id`  int(11) NOT NULL DEFAULT 0 COMMENT '分组ID集合';
ALTER TABLE `fx_related_authorize` ADD INDEX `ra_gp_id` USING BTREE (`ra_gp_id`) ;
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_coupon_menoy` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '优惠券使用金额';
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_bonus_money` decimal(10,3) NOT NULL COMMENT '订单使用红包金额';
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_cards_money` decimal(10,3) NOT NULL COMMENT '订单使用储蓄卡支付的金额';
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_jlb_money` decimal(10,3) NOT NULL COMMENT '订单使用金币金额';
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_point_money` decimal(10,3) NOT NULL COMMENT '订单使用积分抵扣的金额';
ALTER TABLE `fx_orders_log` ADD KEY `o_id` (`o_id`);
ALTER TABLE `fx_orders_log` ADD KEY `ol_behavior` (`ol_behavior`);
ALTER TABLE `fx_groupbuy` ADD column `gp_is_baoyou` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否包邮：0：不包邮；1:包邮';
ALTER TABLE `fx_recharge_examine` ADD COLUMN `re_content` varchar(255) NOT NULL DEFAULT '' COMMENT '作废原因';
ALTER TABLE `fx_point_log` MODIFY COLUMN `type` int(2) NOT NULL DEFAULT '0' COMMENT '类型(0:购物赠送；1：购物消耗；2：注册奖励积分；3：评论送积分；4：订单退货成功还原冻结积分；5：管理员积分调整；6：积分冻结；7：作废订单成功还原冻结积分 8:订单退款成功还原冻结积分；9：抽奖消耗；10:签到赠送积分；11：晒单；12：会员邀请好友；13会员登陆；14推荐注册 15:晒单)';
ALTER TABLE `fx_goods_comments` ADD COLUMN `gcom_order_id` bigint(20) DEFAULT '0' COMMENT '订单号';
ALTER TABLE `fx_goods_comments` ADD KEY `gcom_order_id` (`gcom_order_id`);
ALTER TABLE `fx_recharge_examine` ADD COLUMN `re_admin_message`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '管理员留言' AFTER `re_create_time`;
ALTER TABLE `fx_thd_goods` MODIFY COLUMN `thd_goods_data` mediumtext COMMENT '第三方商品详细信息，存json_encode字符串(所有的数据)';
DROP TABLE IF EXISTS `fx_point_activity`;
CREATE TABLE `fx_point_activity` (
  `pa_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '积分赠送活动自增ID',
  `pa_title` varchar(50) NOT NULL COMMENT '积分赠送活动标题',
  `pa_start_time` datetime NOT NULL COMMENT '积分赠送活动开始时间',
  `pa_end_time` datetime NOT NULL COMMENT '积分赠送活动结束时间',
  `pa_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:停用；1:启用 2:删除',
  `pa_order` tinyint(1) NOT NULL DEFAULT '0' COMMENT '排序',
  `pa_create_time` datetime NOT NULL COMMENT '创建时间',
  `pa_update_time` datetime NOT NULL COMMENT '修改时间',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '绑定的会员ID',
  `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联的店铺ID',
  `pa_desc` text COMMENT '积分赠送活动描述',
  `pa_day_times` tinyint(4) NOT NULL DEFAULT '0' COMMENT '每天赠送次数',
  `pa_times_num` int(4) NOT NULL DEFAULT '0' COMMENT '每次赠送积分数量',
  `pa_how_time` tinyint(4) NOT NULL DEFAULT '0' COMMENT '设置多长时间赠送一次,单位（分钟）',
  PRIMARY KEY (`pa_id`),
  KEY `m_id` (`m_id`),
  KEY `gc_id` (`gc_id`),
  KEY `pa_title` (`pa_title`),
  KEY `pa_status` (`pa_status`),
  KEY `pa_start_time` (`pa_start_time`),
  KEY `pa_end_time` (`pa_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分赠送活动表';
ALTER TABLE `fx_orders_items` ADD COLUMN `oi_balance_money` decimal(10,3) NOT NULL COMMENT '订单使用结余款支付的金额';
DROP TABLE IF EXISTS `fx_template_operation_log`;
CREATE TABLE `fx_template_operation_log` (
  `tl_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `tl_operation` varchar(100) NOT NULL DEFAULT '' COMMENT '操作',
  `u_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人',
  `u_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `u_real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '编辑人姓名',
  `tl_operation_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间',
  `tl_model` varchar(30) NOT NULL DEFAULT '' COMMENT '操作模块',
  PRIMARY KEY (`tl_id`),
  KEY `u_real_name` (`u_real_name`),
  KEY `u_name` (`u_name`),
  KEY `tl_operation_time` (`tl_operation_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板操作日志表';
ALTER TABLE `fx_goods_info` MODIFY COLUMN `g_salenum`  int(11) UNSIGNED NULL DEFAULT 0 COMMENT '销量';
DROP TABLE IF EXISTS `fx_related_groupbuycategory_ads`;
CREATE TABLE `fx_related_groupbuycategory_ads` (
  `rga_id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_url` varchar(255) NOT NULL COMMENT '链接地址',
  `sort_order` tinyint(2) NOT NULL DEFAULT '0' COMMENT '排序',
  `ad_pic_url` varchar(255) NOT NULL COMMENT '图片链接地址',
  `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '类目ID',
  PRIMARY KEY (`rga_id`),
  KEY `gc_id` (`gc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='团购分类广告图片表';
ALTER TABLE `fx_groupbuy` ADD COLUMN `gp_remark` varchar(255) DEFAULT NULL COMMENT '团购简介';
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Lottery', '抽奖活动', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '抽奖活动列表', 'Lottery', '抽奖活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'userList', '奖品列表', 'Lottery', '抽奖活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Sms', 'SMS管理', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSms', 'SMS设置', 'Sms', 'SMS管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '已发送列表', 'Sms', 'SMS管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageTemp', '短信模板', 'Sms', 'SMS管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '红包调整单列表', 'JlbInfo', '红包管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addBonusInfo', '新增红包调整单', 'JlbInfo', '红包管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '红包设置', 'JlbInfo', '红包管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Salespromotion', '分销商引荐管理', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '分销商引荐管理', 'Salespromotion', '推广销售管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'showSalesSetList', '销售额设定', 'Salespromotion', '推广销售管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'index', '商品返利设定', 'Promotings', '推广销售管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'PBStatements', '返利报表', 'Promotings', '推广销售管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Try', '试用活动', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '试用活动列表', 'Try', '试用活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdd', '新增试用活动', 'Try', '试用活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'apply_index', '试用活动申请列表', 'Try', '试用活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'report', '试用报告列表', 'Try', '试用活动', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '试用活动设置', 'Try', '试用活动', '1', '0', '0');
insert into fx_role_node set action='',action_name='',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='1';
insert into fx_role_node set action='edit',action_name='可视化编辑页面',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='huifu',action_name='初始化首页',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='save',action_name='保存首页',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='zancun',action_name='暂存首页',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='index',action_name='预览暂存首页',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='huanyuan',action_name='还原上次编辑',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
insert into fx_role_node set action='searchEditLog',action_name='查看编辑日志',module='Edit',module_name='可视化模块',status='1',sort='10',auth_type='0';
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'SalesStatistics', '销售统计', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'SalesRanking', '销售量排名', 'SalesStatistics', '销售统计', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'MembersRanking', '购买量排名', 'SalesStatistics', '销售统计', '1', '0', '0');

