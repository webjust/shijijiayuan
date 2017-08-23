del_idx('databaseAddIndex','fx_red_enevlope','rd_is_status');
ALTER TABLE `fx_red_enevlope` ADD KEY `rd_is_status` (`rd_is_status`);
del_idx('databaseAddIndex','fx_red_enevlope','rd_start_time');
ALTER TABLE `fx_red_enevlope` ADD KEY `rd_start_time` (`rd_start_time`);
del_idx('databaseAddIndex','fx_red_enevlope','rd_end_time');
ALTER TABLE `fx_red_enevlope` ADD KEY `rd_end_time` (`rd_end_time`);
del_idx('databaseAddIndex','fx_coupon','c_start_time');
ALTER TABLE `fx_coupon` ADD KEY `c_start_time` (`c_start_time`);
del_idx('databaseAddIndex','fx_coupon','c_end_time');
ALTER TABLE `fx_coupon` ADD KEY `c_end_time` (`c_end_time`);
del_idx('databaseAddIndex','fx_coupon','c_user_id');
ALTER TABLE `fx_coupon` ADD KEY `c_user_id` (`c_user_id`);
del_idx('databaseAddIndex','fx_coupon','c_is_use');
ALTER TABLE `fx_coupon` ADD KEY `c_is_use` (`c_is_use`);
del_idx('databaseAddIndex','fx_related_goods_group','g_id');
ALTER TABLE `fx_related_goods_group` ADD KEY `g_id` (`g_id`);
del_idx('databaseAddIndex','fx_keystore','g_id');
ALTER TABLE `fx_keystore` ADD KEY `g_id` (`g_id`);
del_idx('databaseAddIndex','fx_goods_products','pdt_stock');
ALTER TABLE `fx_goods_products` ADD KEY `pdt_stock` (`pdt_stock`);
del_idx('databaseAddIndex','fx_goods_comments','gcom_create_time');
ALTER TABLE `fx_goods_comments` ADD KEY `gcom_create_time` (`gcom_create_time`);
del_idx('databaseAddIndex','fx_article','a_status');
ALTER TABLE `fx_article` ADD KEY `a_status` (`a_status`);
del_idx('databaseAddIndex','fx_article','a_is_display');
ALTER TABLE `fx_article` ADD KEY `a_is_display` (`a_is_display`);
ALTER TABLE `fx_thd_top_items` modify  `num_iid` varchar(255) NOT NULL DEFAULT '0' COMMENT '商品数字id';
ALTER TABLE `fx_thd_top_items` modify  `sku_id` varchar(255) NOT NULL DEFAULT '0' COMMENT 'SKU数字id';
ALTER TABLE `fx_thd_top_items` modify `spec_name` varchar(300) NOT NULL COMMENT '属性备注';
INSERT INTO `fx_payment_cfg` VALUES ('11', '工商银行', 'icbc', 'ICBC', null, '0.000', '此支付方式为工行接口', '2015-10-15 11:31:30', '', '0', '1', '12', '1');
ALTER TABLE fx_point_log MODIFY `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型(0:购物赠送；1：购物消耗；2：注册奖励积分；3：评论送积分；4：订单退货成功还原冻结积分；5：管理员积分调整；6：积分冻结；7：作废订单成功还原冻结积分 8:订单作废还原冻结积分；9：抽奖消耗；10:签到赠送积分；11：晒单；12：会员邀请好友；13会员登陆；14推荐注册 15:晒单)';
del_idx('databaseAddIndex','fx_payment_cfg','pc_status');
ALTER TABLE `fx_payment_cfg` ADD KEY `pc_status` (`pc_status`);
del_idx('databaseAddIndex','fx_receive_address','ra_name');
ALTER TABLE `fx_receive_address` ADD KEY `ra_name` (`ra_name`);
DROP TABLE IF EXISTS `fx_integral`;
CREATE TABLE `fx_integral` (
  `integral_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '积分兑换ID',
  `integral_title` varchar(255) NOT NULL COMMENT '秒杀标题',
  `integral_desc` text COMMENT '积分+金额兑换描述',
  `integral_picture` varchar(100) NOT NULL COMMENT '秒杀图片',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `integral_now_number` int(11) NOT NULL DEFAULT '0' COMMENT '已兑换数量',
  `money_need_to_pay` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '还需缴纳金额',
  `integral_goods_desc_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示商品描述',
  `integral_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动开始时间',
  `integral_end_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动结束时间',
  `integral_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用：0.停用，1.启用',
  `integral_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '积分兑换创建时间',
  `integral_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '积分兑换更新时间',
  `integral_mobile_desc` text NOT NULL COMMENT '手机端描述',
   `integral_num`  int(11) NOT NULL DEFAULT 0 COMMENT '限购数量 当购买数量大于限购数量时 活动结束',
  `gc_id` int(11) NOT NULL DEFAULT 0 COMMENT '积分分类',
  PRIMARY KEY (`integral_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分兑换';
DROP TABLE IF EXISTS `fx_integral_category`;
CREATE TABLE `fx_integral_category` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分兑换商品分类';
ALTER TABLE fx_orders_items  MODIFY  COLUMN  oi_type int(4) NOT NULL DEFAULT '0' COMMENT '商品类型，11:积分+金额兑换 8:预售 7:秒杀商品 6:自由搭配商品 5:团购商品，4:自由推荐商品,3组合商品，2赠品， 1积分商品，0普通商品';
ALTER TABLE `fx_template` ADD COLUMN `ti_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '模板类型 0-pc端 1-wap端 2-手机端 ';
ALTER TABLE `fx_try_apply_records`ADD COLUMN `try_id`  int(11) NOT NULL DEFAULT 0 COMMENT '申请试用的活动id';
ALTER TABLE `fx_point_config` modify  `consumed_points` float DEFAULT NULL COMMENT '每换1分钱需要抵用的积分数（不超过999999）';
ALTER TABLE fx_balance_info MODIFY  COLUMN `bi_sn` varchar(100) NOT NULL COMMENT '结余款调整单单据编号，当前时间戳+6位ID不足补0';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `promotion` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '参与促销信息';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `erp_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '商品所属ERP的ID';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `oi_bonus_money` DECIMAL(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单使用红包金额';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `oi_cards_money` DECIMAL(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单使用储蓄卡支付的金额';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `oi_jlb_money` DECIMAL(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单使用金币金额';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `oi_point_money` DECIMAL(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单使用积分抵扣的金额';
ALTER TABLE `fx_orders_items` MODIFY COLUMN `oi_balance_money` DECIMAL(10,3) NOT NULL DEFAULT '0.000' COMMENT '订单使用结余款支付的金额';
alter table `fx_point_config` add `is_low_consumed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '启用积分抵扣最低额(0:不启用；1：启用)';
alter table `fx_point_config` add `low_consumed_points` int(11) DEFAULT '100' COMMENT '积分抵扣最低额：(大于等于100的整数)';
/*暂时新增*/
 ALTER TABLE `fx_thd_orders` ADD COLUMN `to_pay_type` varchar(30) NOT NULL COMMENT '支付方式（1-货到付款, 2-邮局汇款, 3-自提, 4-在线支付, 5-公司转账, 6-银行转账）';

