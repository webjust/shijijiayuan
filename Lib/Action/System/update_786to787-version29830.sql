set names utf8;

INSERT INTO `fx_role_node` VALUES (NULL, 'pageAdminOrdersPay', '���������б�', 'Orders', '��������', '1', '10', '0');
INSERT INTO `fx_source_platform`  VALUES ('8','WX','΢��ƽ̨','0','1','0000-00-00 00:00:00','0000-00-00 00:00:00');
ALTER TABLE `fx_thd_top_items` ADD COLUMN `spec_name` varchar(255) NOT NULL COMMENT '��������';
ALTER TABLE `fx_point_log` ADD COLUMN `o_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '������';

del_idx('databaseAddIndex','fx_point_log','o_id');
ALTER TABLE `fx_point_log` ADD KEY `o_id` (`o_id`);

DROP TABLE IF EXISTS `fx_admin_pay`;
CREATE TABLE `fx_admin_pay` (
  `ap_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '������',
  `add_u_id` int(11) NOT NULL DEFAULT '0' COMMENT '�Ƶ���',
  `verify_u_id` int(11) NOT NULL DEFAULT '0' COMMENT '����',
  `add_u_name` varchar(50) NOT NULL COMMENT '�Ƶ�������',
  `verify_u_name` varchar(50) NOT NULL COMMENT '��������',
  `ap_remark` varchar(255) NOT NULL COMMENT '��ע',
  `ps_gateway_sn` varchar(255) NOT NULL COMMENT '������ˮ��',
  `ap_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '״̬��0δ���,1�����,2������',
  `ps_id` int(11) NOT NULL DEFAULT '0' COMMENT '��������ˮ��ID',
  `ap_create_time` datetime NOT NULL COMMENT '��������ʱ��',
  `ap_update_time` datetime NOT NULL COMMENT '���ݸ���ʱ��',
  PRIMARY KEY (`ap_id`),
  KEY `order_id` (`order_id`),
  KEY `ap_status` (`ap_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='����Աǿ��֧���б�';
