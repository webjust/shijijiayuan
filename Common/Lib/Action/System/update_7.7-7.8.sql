set names utf8;
/**
INSERT INTO `fx_payment_cfg` VALUES (8,'微支付','weipay','WEIPAY','',0.000,'APP微支付','0000-00-00 00:00:00','0',1,0,0);
INSERT INTO `fx_script_info` VALUES ('自动批量同步会员数据到SNS','syncMemToSns','syncMemToSns',5,'2014-10-11 03:46:50',0,1);
INSERT INTO `fx_script_info` VALUES ('自动更新获取结余款利息金币','autointerestrates','AutoInterestRates',1440,'2014-08-17 03:46:50',0,1);
INSERT INTO `fx_script_info` VALUES ('自动更新同步会员数据','updateMember','updateMember',5,'2014-08-17 03:46:50',0,1);
INSERT INTO `fx_script_info` VALUES ('自动更新同步会员数据','addMember','addMember',5,'2014-08-17 03:46:50',0,1);
INSERT INTO `fx_script_info` VALUES ('自动批量更新同步会员数据','batchAdd','addMembers',5,'2014-08-17 03:46:50',0,1);
INSERT INTO `fx_script_info` VALUES ('自动同步订单信息到客户客服系统','autosynctrade','AutoSyncTrade',1,'2014-08-22 15:46:50',0,1);
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '储值卡调整单列表', 'JlbInfo', '储值卡管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addCardsInfo', '新增储值卡调整单', 'JlbInfo', '储值卡管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '储值卡设置', 'JlbInfo', '储值卡管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '金币调整单列表', 'JlbInfo', '金币管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'addJlbInfo', '新增金币调整单', 'JlbInfo', '金币管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageSet', '金币设置', 'JlbInfo', '金币管理', '1', '0', '0');
DROP TABLE IF EXISTS `fx_related_sync_order`;
CREATE TABLE `fx_related_sync_order` (
  `o_id` bigint(20) NOT NULL COMMENT '订单号',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1成功，0不成功',
  `time` datetime DEFAULT NULL COMMENT '同步时间',
  PRIMARY KEY (`o_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='蓝源订单同步相关表';

**/
ALTER TABLE `fx_orders` ADD COLUMN `o_ip`  varchar(50) NULL DEFAULT NULL;
ALTER TABLE `fx_groupbuy` ADD COLUMN `gp_start_code` tinyint(1) NULL DEFAULT '0' COMMENT '是否启用验证码';

ALTER TABLE `fx_payment_serial` ADD KEY `o_id` (`o_id`);
ALTER TABLE `fx_payment_serial` ADD KEY `ps_type` (`ps_type`);
ALTER TABLE `fx_payment_serial` ADD KEY `ps_gateway_sn` (`ps_gateway_sn`);
ALTER TABLE `fx_payment_serial` ADD KEY `ps_status` (`ps_status`);
ALTER TABLE `fx_payment_serial` ADD KEY `ps_update_time` (`ps_update_time`);

/*新增确认收货时间 By huhaiwei 2014-10-27 Start*/
ALTER TABLE `fx_orders` ADD COLUMN `o_confirm_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '确认收货时间';
/*新增确认收货时间 By huhaiwei 2014-10-27 End*/

/*添加友好宝支付方式 By wangguibin 2014-11-13 Start*/
INSERT INTO `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`pc_memo`,`pc_status`,`pc_trd`,`pc_position`) VALUES ('9','全世达网银支付','youhaopay','YOUHAOPAY','全世达网银支付','0','1','9');
INSERT INTO `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`pc_memo`,`pc_status`,`pc_trd`,`pc_position`) VALUES ('10','全世达预付卡支付','yufuka','YUFUKA','全世达预付卡支付','0','1','9');
/*添加友好宝支付方式 By wangguibin 2014-11-13 End*/

/*添加 资讯定时字段 By lixiaolong end 2014-10-15 09:48:00*/
alter table `fx_article` add `a_startime`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间';
alter table `fx_article` add `a_endtime`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间';
/*添加 资讯定时字段 By lixiaolong end 2014-10-15 09:48:00*/

/*新增优惠金额 By wangguibin 2014-11-20 Start*/
ALTER TABLE `fx_orders_items` ADD COLUMN `promotion_price` decimal(20,3) NOT NULL DEFAULT '0.000' COMMENT '优惠金额';
/*新增优惠金额 By wangguibin 2014-11-20 End*/

/*会员属性表增加赠送积分数量  By wanghaijun 2014-11-20 start*/
ALTER TABLE `fx_members_fields` ADD COLUMN `fields_point` int(4) NOT NULL DEFAULT '0' COMMENT '赠送积分数';
/*会员属性表增加赠送积分数量  By wanghaijun 2014-11-20 end*/

/*合并支付节点 start By Wangguibin*/
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'MergerPayment', '合并支付管理', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'pageList', '合并支付订单列表', 'MergerPayment', '合并支付管理', '1', '0', '0');
/*合并支付节点 end By Wangguibin*/

/*合并支付节点 start By Wangguibin*/
INSERT INTO `fx_role_node` VALUES (NULL, '', '', 'SalesStatistics', '销售统计管理', '1', '0', '1');
INSERT INTO `fx_role_node` VALUES (NULL, 'SalesRanking', '合并支付订单列表', 'SalesStatistics', '销售统计管理', '1', '0', '0');
INSERT INTO `fx_role_node` VALUES (NULL, 'MembersRanking', '购买量排名', 'SalesStatistics', '销售统计管理', '1', '0', '0');
/*合并支付节点 end By Wangguibin*/

/*添加工商银行支付方式 By wangguibin 2014-12-04 Start*/
INSERT INTO `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`pc_memo`,`pc_status`,`pc_trd`,`pc_position`) VALUES ('11','工商银行(1.0.0.11)','icbc','ICBC','此支付方式为工行接口1.0.0.11版本使用，<br />需要提供商户公钥文件、商户私钥文件、工行公钥钥文件','0','1','11');
/*添加工商银行支付方式 By wangguibin 2014-12-04 End*/
