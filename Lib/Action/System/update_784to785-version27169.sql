set names utf8;

alter table fx_goods_info add g_phone_desc text COMMENT '手机端产品介绍' after g_desc;

replace INTO `fx_role_node` VALUES (NULL, 'doCouponSet', '优惠券设置', 'Coupon', '优惠券管理', '1', '10', '0');
replace INTO `fx_role_node` VALUES (NULL, 'pageOrdersRefundDeliverList', '退运费单', 'Orders', '订单管理', '1', '10', '0');
replace INTO `fx_role_node` VALUES (NULL, '', '', 'WapEdit', 'WAP可视化模块', '1', '0', '1');
replace INTO `fx_role_node` VALUES (NULL, 'edit', '可视化编辑页面', 'WapEdit', 'WAP可视化模块', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'save', '保存首页', 'WapEdit', 'WAP可视化模块', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'zancun', '暂存首页', 'WapEdit', 'WAP可视化模块', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, '', '', 'AppEdit', 'APP可视化模块', '1', '0', '1');
replace INTO `fx_role_node` VALUES (NULL, 'edit', '可视化编辑页面', 'AppEdit', 'APP可视化模块', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'save', '保存首页', 'AppEdit', 'APP可视化模块', '1', '0', '0');
replace INTO `fx_role_node` VALUES (NULL, 'zancun', '暂存首页', 'AppEdit', 'APP可视化模块', '1', '0', '0');

REPLACE INTO `fx_source_platform` VALUES (1,'taobao','淘宝',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(2,'paipai','拍拍',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(3,'dangdang','当当',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(4,'360buy','京东',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(5,'amazon','亚马逊',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(6,'suning','苏宁易购',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(7,'1haodian','一号店',1,1,'2013-05-29 04:12:12','2013-05-29 04:12:12'),(11,'QQ','腾讯',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00'),(12,'Sina','新浪微博',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00'),(13,'RenRen','人人网',1,1,'2013-12-08 11:56:24','0000-00-00 00:00:00');

alter table fx_members add shop_code varchar(100) COMMENT 'O2O店铺代码';
alter table fx_members add shop_id int(11) COMMENT 'O2O店铺id';
ALTER TABLE fx_members ADD KEY `shop_id` (`shop_id`);


ALTER TABLE `fx_feedback` MODIFY COLUMN `user_mobile`  varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号' AFTER `user_name`;