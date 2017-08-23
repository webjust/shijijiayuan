set names utf8;
DROP TABLE IF EXISTS `fx_related_coupon_red`;
CREATE TABLE `fx_related_coupon_red` (
  `c_name` varchar(50) NOT NULL DEFAULT '' COMMENT '红包名称',
  `rd_id` int(11) NOT NULL DEFAULT '0' COMMENT '规则id',
  KEY `c_name` (`c_name`),
  KEY `rd_id` (`rd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='红包与规则关联表';
del_idx('databaseAddIndex','fx_keystore','g_id');
ALTER TABLE `fx_keystore` ADD PRIMARY KEY (`g_id`);
del_idx('databaseAddIndex','fx_free_collocation','fc_start_time');
ALTER TABLE `fx_free_collocation` ADD KEY `fc_start_time` (`fc_start_time`);
del_idx('databaseAddIndex','fx_free_collocation','fc_end_time');
ALTER TABLE `fx_free_collocation` ADD KEY `fc_end_time` (`fc_end_time`);
del_idx('databaseAddIndex','fx_free_collocation','fc_status');
ALTER TABLE `fx_free_collocation` ADD KEY `fc_status` (`fc_status`);
del_idx('databaseAddIndex','fx_free_recommend','fr_statr_time');
ALTER TABLE `fx_free_recommend` ADD KEY `fr_statr_time` (`fr_statr_time`);
del_idx('databaseAddIndex','fx_free_recommend','fr_end_time');
ALTER TABLE `fx_free_recommend` ADD KEY `fr_end_time` (`fr_end_time`);
del_idx('databaseAddIndex','fx_free_recommend','fr_status');
ALTER TABLE `fx_free_recommend` ADD KEY `fr_status` (`fr_status`);
del_idx('databaseAddIndex','fx_thd_orders','to_thd_status');
ALTER TABLE `fx_thd_orders` ADD KEY `to_thd_status` (`to_thd_status`);
del_idx('databaseAddIndex','fx_thd_orders','m_id');
ALTER TABLE `fx_thd_orders` ADD KEY `m_id` (`m_id`);
del_idx('databaseAddIndex','fx_thd_orders','to_created');
ALTER TABLE `fx_thd_orders` ADD KEY `to_created` (`to_created`);
del_idx('databaseAddIndex','fx_thd_orders','to_tt_status');
ALTER TABLE `fx_thd_orders` ADD KEY `to_tt_status` (`to_tt_status`);
del_idx('databaseAddIndex','fx_orders','o_update_time');
ALTER TABLE `fx_orders` ADD KEY `o_update_time` (`o_update_time`);
ALTER TABLE `fx_feedback` MODIFY `user_mobile` varchar(200) NOT NULL DEFAULT '' COMMENT '手机号';
INSERT INTO `fx_payment_cfg` VALUES ('9', 'WAP支付宝', 'wapalipay', 'WAPALIPAY', '{\"alipay_account\":\"test\",\"pay_safe_code\":\"test\",\"identity_id\":\"test\",\"interface_type\":\"1\",\"pay_encryp\":\"MD5\"}', '0.000', 'WAP支付宝', '2015-05-06 09:32:23', '', '0', '1', '9', '2');
INSERT INTO `fx_source_platform` VALUES (1,'taobao','淘宝',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(2,'paipai','拍拍',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(3,'dangdang','当当',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(4,'360buy','京东',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(5,'amazon','亚马逊',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(6,'suning','苏宁易购',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(7,'1haodian','一号店',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12');