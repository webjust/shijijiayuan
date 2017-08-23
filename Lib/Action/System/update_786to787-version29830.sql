set names utf8;

INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdminOrdersPay', '付款申请列表', 'Orders', '订单管理', '1', '10', '0');
INSERT INTO `fx_source_platform`  VALUES ('8','WX','微信平台','0','1','0000-00-00 00:00:00','0000-00-00 00:00:00');
ALTER TABLE `fx_thd_top_items` ADD COLUMN `spec_name` varchar(255) NOT NULL COMMENT '属性名称';
ALTER TABLE `fx_point_log` ADD COLUMN `o_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单号';

del_idx('databaseAddIndex','fx_point_log','o_id');
ALTER TABLE `fx_point_log` ADD KEY `o_id` (`o_id`);

DROP TABLE IF EXISTS `fx_admin_pay`;
CREATE TABLE `fx_admin_pay` (
  `ap_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单号',
  `add_u_id` int(11) NOT NULL DEFAULT '0' COMMENT '制单人',
  `verify_u_id` int(11) NOT NULL DEFAULT '0' COMMENT '审单人',
  `add_u_name` varchar(50) NOT NULL COMMENT '制单人名称',
  `verify_u_name` varchar(50) NOT NULL COMMENT '审单人名称',
  `ap_remark` varchar(255) NOT NULL COMMENT '备注',
  `ps_gateway_sn` varchar(255) NOT NULL COMMENT '网关流水号',
  `ap_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0未审核,1已审核,2已作废',
  `ps_id` int(11) NOT NULL DEFAULT '0' COMMENT '第三方流水号ID',
  `ap_create_time` datetime NOT NULL COMMENT '单据生成时间',
  `ap_update_time` datetime NOT NULL COMMENT '单据更新时间',
  PRIMARY KEY (`ap_id`),
  KEY `order_id` (`order_id`),
  KEY `ap_status` (`ap_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员强制支付列表';
