set names utf8;
/* *
 * 数据库更新文件我已合并到install.sql
 * 本次开始，所有人在提交sql更新时，务必在所提交的变更SQL前后
 * 注明自己的姓名、更新时间、更新原因
 *
 * 1. 提交SQL之前必须在自己机器上测试通过才允许提交！！！！
 * 2. 提交SQL文件不写注释的，拖出去枪毙三天！！！
 * 3. 第一行set names utf8 不允许删除！！！！
 * 4. 这段注释不允许删除！！！
 * 5. SQL脚本提交完毕以后务必在自己的SQL脚本尾部增加一行注释，标记自己的本次更新已经结束！！！
 * 6. 不允许第二次提交时将SQL写到上次自己更新的脚本段中。
 * 7. 已经提交的SQL不允许修改（如确需修改，新增一条表结构修改脚本）。
 */
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

/*修改字段信息 Wangguibin 2015-03-09 12:50 start*/
ALTER TABLE `fx_orders_refunds` MODIFY COLUMN `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id';
/*修改字段信息 Wangguibin 2015-03-09 12:50 end*/

/*添加索引加快查询速度 Wangguibin 2015-03-09 12:40 start*/
CREATE INDEX `cr_name` ON `fx_city_region`(`cr_name`) USING BTREE ;

CREATE INDEX `g_is_combination_goods` ON `fx_goods`(`g_is_combination_goods`) USING BTREE ;
CREATE INDEX `o_create_time` ON `fx_orders`(`o_create_time`) USING BTREE ;
CREATE INDEX `oi_create_time` ON `fx_orders_items`(`oi_create_time`) USING BTREE ;
CREATE INDEX `oi_ship_status` ON `fx_orders_items`(`oi_ship_status`) USING BTREE ;
CREATE INDEX `oi_refund_status` ON `fx_orders_items`(`oi_refund_status`) USING BTREE ;
CREATE INDEX `oi_type` ON `fx_orders_items`(`oi_type`) USING BTREE ;
/*添加索引加快查询速度 Wangguibin 2015-03-09 12:40 end*/

/*表添加 Wangguibin 2015-03-09 12:40 start*/
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
/*表添加 Wangguibin 2015-03-09 12:40 end*/

/*字段添加 Wangguibin 2015-03-11 14:40 start*/
ALTER TABLE `fx_thd_shops` MODIFY COLUMN `ts_source` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：默认，1:淘宝，2：拍拍，3：京东';
ALTER TABLE `fx_top_access_info` ADD COLUMN`top_type` tinyint(4) DEFAULT '0' COMMENT '店铺类型 0：默认，1:淘宝，2：拍拍，3：京东';
/*字段添加 Wangguibin 2015-03-11 14:40 end*/

/*字段添加索引 zhangjiasuo 2015-03-13 16:03 start*/
ALTER TABLE `fx_thd_goods` ADD INDEX `thd_goods_sn`( `thd_goods_sn` );
/*字段添加索引 zhangjiasuo 2015-03-13 16:03 end*/

/*收货地址表添加身份证号  By Hcaijin 2015-03-16 Start*/
alter table `fx_receive_address` add column `ra_id_card` varchar(200) not null default '' comment '身份证号';
/*收货地址表添加身份证号  By Hcaijin 2015-03-16 End*/

/*商品税率字段添加 zhangjiasuo 2015-03-16 16:03 start*/
ALTER TABLE `fx_goods_info` ADD COLUMN `g_tax_rate` float DEFAULT '0' COMMENT '商品税率' AFTER `g_market_price`;
/*商品税率字段添加 zhangjiasuo 2015-03-16 16:03 end*/

/*订单表添加收货人身份证号  By Hcaijin 2015-03-18 Start*/
ALTER TABLE `fx_orders` add column `o_receiver_idcard` varchar(200) not null default '' comment '收货人身份证号';
/*订单表添加收货人身份证号  By Hcaijin 2015-03-18 End*/

/*跨境贸易订单提交设定 zhangjiasuo 2015-03-19 11:03 start*/
INSERT INTO fx_sys_config (sc_module, sc_key, sc_value, sc_value_desc, sc_create_time, sc_update_time) VALUES ('GY_FOREIGN_ORDER', 'LIMIT_ORDER_AMOUNT', '1000', '订单1000元控制提示（单件超过1000元商品除外）', '2015-03-19 11:04:34', '2015-03-19 11:04:34'),('GY_FOREIGN_ORDER', 'IS_AUTO_LIMIT_ORDER_AMOUNT', '0', '开启订单限额控制', '2015-03-19 10:35:53', '2015-03-19 10:35:53');
/*跨境贸易订单提交设定 zhangjiasuo 2015-03-16 11:03 start*/

/*订单税额字段添加 zhangjiasuo 2015-03-20 16:03 start*/
ALTER TABLE `fx_orders` ADD COLUMN `o_tax_rate` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单税额' AFTER `o_all_price`;
/*订单税额字段添加 zhangjiasuo 2015-03-20 16:03 end*/
