set names utf8;

alter table fx_goods_info add g_phone_desc text COMMENT '�ֻ��˲�Ʒ����' after g_desc;

replace INTO `fx_role_node` VALUES (NULL, 'doCouponSet', '�Ż�ȯ����', 'Coupon', '�Ż�ȯ����', '1', '10', '0');
replace INTO `fx_role_node` VALUES (NULL, 'pageOrdersRefundDeliverList', '���˷ѵ�', 'Orders', '��������', '1', '10', '0');
replace INTO `fx_role_node` VALUES (NULL, '', '', 'WapEdit', 'WAP���ӻ�ģ��', '1', '0', '1');
replace INTO `fx_role_node` VALUES (NULL, 'edit', '���ӻ��༭ҳ��', 'WapEdit', 'WAP���ӻ�ģ��', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'save', '������ҳ', 'WapEdit', 'WAP���ӻ�ģ��', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'zancun', '�ݴ���ҳ', 'WapEdit', 'WAP���ӻ�ģ��', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, '', '', 'AppEdit', 'APP���ӻ�ģ��', '1', '0', '1');
replace INTO `fx_role_node` VALUES (NULL, 'edit', '���ӻ��༭ҳ��', 'AppEdit', 'APP���ӻ�ģ��', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'save', '������ҳ', 'AppEdit', 'APP���ӻ�ģ��', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'zancun', '�ݴ���ҳ', 'AppEdit', 'APP���ӻ�ģ��', '1', '0', '0');

REPLACE INTO `fx_source_platform` VALUES (1,'taobao','�Ա�',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(2,'paipai','����',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(3,'dangdang','����',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(4,'360buy','����',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(5,'amazon','����ѷ',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(6,'suning','�����׹�',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(7,'1haodian','һ�ŵ�',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(11,'QQ','��Ѷ',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00'),(12,'Sina','����΢��',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00'),(13,'RenRen','������',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00');

alter table fx_members add shop_code varchar(100) COMMENT 'O2O���̴���';
alter table fx_members add shop_id int(11) COMMENT 'O2O����id';
ALTER TABLE fx_members ADD KEY `shop_id` (`shop_id`);


ALTER TABLE `fx_feedback` MODIFY COLUMN `user_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '�ֻ���' AFTER `user_name`;