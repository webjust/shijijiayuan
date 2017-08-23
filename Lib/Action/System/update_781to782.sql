set names utf8;
ALTER TABLE `fx_goods_info` ADD COLUMN `mobile_show`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '手机端是否显示(1显示,0不显示)';
ALTER TABLE `fx_orders` ADD COLUMN `o_source`  varchar(10) NULL DEFAULT 'pc' COMMENT '订单来源(pc,andriod,ios)';
ALTER TABLE `fx_members`
MODIFY COLUMN `m_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `m_zipcode`,
MODIFY COLUMN `m_telphone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `m_mobile`;
ALTER TABLE `fx_orders_delivery`
MODIFY COLUMN `od_receiver_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `od_receiver_name`,
MODIFY COLUMN `od_receiver_telphone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `od_receiver_mobile`;
ALTER TABLE `fx_orders`
MODIFY COLUMN `o_receiver_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `o_receiver_name`,
MODIFY COLUMN `o_receiver_telphone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `o_receiver_mobile`;
ALTER TABLE `fx_receive_address`
MODIFY COLUMN `ra_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `ra_post_code`,
MODIFY COLUMN `ra_mobile_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `ra_phone`;
ALTER TABLE `fx_shipping_address`
MODIFY COLUMN `sh_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `sh_post_code`,
MODIFY COLUMN `sh_mobile_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `sh_phone`;
ALTER TABLE `fx_invoice_collect`
MODIFY COLUMN `invoice_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `invoice_address`;
ALTER TABLE `fx_goods_comments`
MODIFY COLUMN `gcom_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `gcom_email`;
ALTER TABLE `fx_thd_orders`
MODIFY COLUMN `to_receiver_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `to_receiver_district`,
MODIFY COLUMN `to_receiver_phone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `to_thd_status`;
ALTER TABLE `fx_members_verify`
MODIFY COLUMN `m_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '电话' AFTER `m_zipcode`,
MODIFY COLUMN `m_telphone`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机' AFTER `m_mobile`;
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'Try', '使用活动', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'BonusInfo', '红包管理', '1', '10', '1');
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'TopOss', '图片服务器设置', '1', '10', '1');
ALTER TABLE `fx_balance_info`
MODIFY COLUMN `bi_sn`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '结余款调整单单据编号，当前时间戳+6位ID不足补0' AFTER `bi_id`;
INSERT INTO `fx_payment_cfg` (`pc_id`, `pc_custom_name`, `pc_pay_type`, `pc_abbreviation`, `pc_config`, `pc_fee`, `pc_memo`, `pc_last_modify`, `erp_payment_id`, `pc_status`, `pc_trd`, `pc_position`) VALUES ('12', '银联在线v5.0.0', 'chinapayv5', 'CHINAPAYV5', '', '0.000', '银联在线支付(全渠道商户)', '2015-01-23 06:28:04', '0', '0', '1', '6');
ALTER TABLE `fx_orders_refunds` MODIFY COLUMN `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id';
ALTER TABLE `fx_goods` DROP COLUMN `taobao_sku_id`;
ALTER TABLE `fx_city_region` ADD KEY `cr_name`( `cr_name` )USING BTREE ;
ALTER TABLE `fx_goods` ADD KEY `g_is_combination_goods`( `g_is_combination_goods` )USING BTREE ;
ALTER TABLE `fx_orders` ADD KEY `o_create_time`( `o_create_time` )USING BTREE ;
ALTER TABLE `fx_orders_items` ADD KEY `oi_create_time`( `oi_create_time` )USING BTREE ;
ALTER TABLE `fx_orders_items` ADD KEY `oi_ship_status`( `oi_ship_status` )USING BTREE ;
ALTER TABLE `fx_orders_items` ADD KEY `oi_refund_status`( `oi_refund_status` )USING BTREE ;
ALTER TABLE `fx_orders_items` ADD KEY `oi_type`( `oi_type` )USING BTREE ;
DROP TABLE IF EXISTS `fx_oss_pic`;
CREATE TABLE `fx_oss_pic` (
  `pic_url` varchar(250) NOT NULL COMMENT '本地图片路径',
  `pic_oss_url` varchar(500) NOT NULL COMMENT 'saas下图片路径',
  PRIMARY KEY (`pic_url`),
  UNIQUE KEY `pic_url` (`pic_url`) USING BTREE,
  KEY `pic_oss_url` (`pic_oss_url`(255)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='oss阿里云存储';
DROP TABLE IF EXISTS `fx_session`;
CREATE TABLE `fx_session` (
  `session_id` varchar(255) NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob,
  UNIQUE KEY `session_id` (`session_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='session存取使用数据库';
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
  KEY `u_real_name` (`u_real_name`) USING BTREE,
  KEY `u_name` (`u_name`) USING BTREE,
  KEY `tl_operation_time` (`tl_operation_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='模板操作日志表';
ALTER TABLE `fx_thd_shops` MODIFY COLUMN `ts_source` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：默认，1:淘宝，2：拍拍，3：京东';
ALTER TABLE `fx_top_access_info` ADD COLUMN `top_type` tinyint(4) DEFAULT '0' COMMENT '店铺类型 0：默认，1:淘宝，2：拍拍，3：京东';
ALTER TABLE `fx_thd_goods` ADD KEY `thd_goods_sn`( `thd_goods_sn` );
alter table `fx_receive_address` add column `ra_id_card` varchar(200) not null default '' comment '身份证号';
ALTER TABLE `fx_goods_info` ADD COLUMN `g_tax_rate` float DEFAULT '0' COMMENT '商品税率' AFTER `g_market_price`;
ALTER TABLE `fx_orders` add column `o_receiver_idcard` varchar(200) not null default '' comment '收货人身份证号';
INSERT INTO fx_sys_config (sc_module, sc_key, sc_value, sc_value_desc, sc_create_time, sc_update_time) VALUES ('GY_FOREIGN_ORDER', 'LIMIT_ORDER_AMOUNT', '1000', '订单1000元控制提示（单件超过1000元商品除外）', '2015-03-19 11:04:34', '2015-03-19 11:04:34'),('GY_FOREIGN_ORDER', 'IS_AUTO_LIMIT_ORDER_AMOUNT', '0', '开启订单限额控制', '2015-03-19 10:35:53', '2015-03-19 10:35:53');
ALTER TABLE `fx_orders` ADD COLUMN `o_tax_rate` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单税额' AFTER `o_all_price`;
INSERT INTO `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`pc_memo`,`pc_status`,`pc_trd`,`pc_position`) VALUES ('13','微信支付','weixin','WEIXIN','此支付方式为商城微信支付(PC端扫码支付、手机端公众号支付)','0','1','13');
ALTER TABLE `fx_orders` MODIFY COLUMN `invoice_phone`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '注册电话' AFTER `invoice_bank`;
ALTER TABLE `fx_payment_cfg` ADD COLUMN `pc_source` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:PC和MOBILE,1:PC,2:MOBILE';
ALTER TABLE `fx_payment_cfg` ADD KEY `pc_pay_type` (`pc_pay_type`);
ALTER TABLE `fx_payment_cfg` ADD KEY `pc_abbreviation` (`pc_abbreviation`);
ALTER TABLE `fx_payment_cfg` ADD KEY `pc_source` (`pc_source`);
UPDATE `fx_payment_cfg` SET pc_source=1 where pc_pay_type='alipay';
UPDATE `fx_payment_cfg` SET pc_source=2 where pc_pay_type='malipay';
UPDATE `fx_payment_cfg` SET pc_source=2 where pc_pay_type='weixin';
ALTER TABLE `fx_thd_goods` ADD KEY `thd_goods_id` (`thd_goods_id`);
ALTER TABLE `fx_thd_goods` ADD KEY `ts_id` (`ts_id`);
ALTER TABLE `fx_thd_goods` ADD KEY `thd_source` (`thd_source`);
ALTER TABLE `fx_goods_products` ADD KEY `thd_indentify` (`thd_indentify`);
ALTER TABLE `fx_goods_products` ADD KEY `thd_pdtid` (`thd_pdtid`);
ALTER TABLE `fx_goods_type` ADD KEY `gt_name` (`gt_name`);
ALTER TABLE `fx_goods_category` ADD KEY `gc_name` (`gc_name`);
ALTER TABLE `fx_goods_spec` ADD KEY `thd_gpid` (`thd_gpid`);
ALTER TABLE `fx_top_itemprops` ADD KEY `pid` (`pid`);
ALTER TABLE `fx_goods_spec_detail` ADD KEY `thd_indentify` (`thd_indentify`);
ALTER TABLE `fx_goods_spec_detail` ADD KEY `thd_gpid` (`thd_gpid`);
ALTER TABLE `fx_goods_spec_detail` ADD KEY `thd_gpvid` (`thd_gpvid`);
ALTER TABLE `fx_orders` ADD KEY `o_receiver_name` (`o_receiver_name`);
ALTER TABLE `fx_orders` ADD KEY `o_receiver_mobile` (`o_receiver_mobile`);
ALTER TABLE `fx_orders` ADD KEY `admin_id` (`admin_id`);
ALTER TABLE `fx_orders_refunds` MODIFY COLUMN `or_refund_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1,退款,2,退货,3,退运费';
ALTER TABLE `fx_logistic_corp` ADD COLUMN `lc_jd_name` varchar(50) NOT NULL COMMENT '京东物流公司名称';