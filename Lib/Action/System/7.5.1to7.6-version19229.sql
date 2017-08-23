set names utf8;
ALTER TABLE `fx_orders`	CHANGE COLUMN `invoice_head` `invoice_head` VARCHAR(50) NULL DEFAULT '' COMMENT '发票抬头' AFTER `invoice_type`;
ALTER TABLE `fx_orders`	CHANGE COLUMN `invoice_content` `invoice_content` VARCHAR(255) NULL DEFAULT '' COMMENT '发票内容' AFTER `invoice_head`;
ALTER TABLE `fx_orders`	CHANGE COLUMN `promotion` `promotion` TEXT NULL COMMENT '促销信息' AFTER `o_promotion_price`;
ALTER TABLE `fx_orders`	CHANGE COLUMN `erp_id` `erp_id` VARCHAR(50) NULL COMMENT '订单所属ERP的ID' AFTER `is_diff`; 
ALTER TABLE `fx_point_config` ADD COLUMN `is_buy_consumed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '启用积分抵金(0:不启用；1：启用)';
ALTER TABLE `fx_point_config` ADD COLUMN `consumed_buy_ratio` int(11) NOT NULL DEFAULT '0' COMMENT '积分抵金比率应该是大于0小于等于100的数字（小数点后最多两位抵扣。100表示可完全用积分）';
ALTER TABLE `fx_point_config` ADD COLUMN `consumed_points` int(11) NOT NULL DEFAULT '0' COMMENT '每换1分钱需要抵用的积分数必须是大于0的整数（不超过999999）';
ALTER TABLE `fx_point_config` ADD COLUMN `again_recommend_points` int(11) NOT NULL DEFAULT '0' COMMENT '再次评价完成并审核成功发放,享受积分翻倍';
ALTER TABLE `fx_point_config` ADD COLUMN `show_recommend_points` int(11) NOT NULL DEFAULT '0' COMMENT '会员晒单,评定审核成功后发放,享受积分翻倍';
ALTER TABLE `fx_point_config` ADD COLUMN `login_points` int(11) NOT NULL DEFAULT '0' COMMENT ' 每日第一次手动登陆获得,不享受积分翻倍';
ALTER TABLE `fx_point_config` ADD COLUMN `sign_points` int(11) NOT NULL DEFAULT '0' COMMENT '每日签到成功后赠送,不享受积分翻倍';
ALTER TABLE `fx_point_config` ADD KEY `is_consumed` (`is_consumed`);
ALTER TABLE `fx_point_config` ADD KEY `consumed_ratio` (`consumed_ratio`);
ALTER TABLE `fx_point_config` ADD KEY `is_buy_consumed` (`is_buy_consumed`);
ALTER TABLE `fx_point_config` ADD KEY `consumed_buy_ratio` (`consumed_buy_ratio`);
ALTER TABLE `fx_point_config` ADD KEY `consumed_points` (`consumed_points`);
DROP TABLE IF EXISTS `fx_related_goodscategory_ads`;
CREATE TABLE `fx_related_goodscategory_ads` (
  `rgca_id` int(11) NOT NULL AUTO_INCREMENT,
  `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '类目ID',
  `ad_url` varchar(255) NOT NULL COMMENT '链接地址',
  `sort_order` tinyint(2) NOT NULL DEFAULT '0' COMMENT '排序',
  `ad_pic_url` varchar(255) NOT NULL COMMENT '图片链接地址',
  PRIMARY KEY (`rgca_id`),
  UNIQUE KEY `gcad` (`gc_id`,`ad_pic_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目和类目广告图片关联表';
DROP TABLE IF EXISTS `fx_related_goodscategory_brand`;
CREATE TABLE `fx_related_goodscategory_brand` (
  `rgcb_id`  int(11) NOT NULL AUTO_INCREMENT ,
  `gb_id` int(11) NOT NULL DEFAULT '0' COMMENT '品牌ID',
  `gc_id` int(11) NOT NULL DEFAULT '0' COMMENT '类目ID',
  PRIMARY KEY (`rgcb_id`),
  UNIQUE KEY `gcb` (`gb_id`,`gc_id`)
) ENGINE=InnoDB;
ALTER TABLE `fx_article_cat` ADD COLUMN `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐显示';
ALTER TABLE `fx_article_cat` ADD KEY `is_recommend` (`is_recommend`);
ALTER TABLE `fx_point_log` MODIFY COLUMN `type` int(2) NOT NULL DEFAULT '0' COMMENT '类型(10:签到赠送积分)';
ALTER TABLE `fx_point_log` ADD KEY `m_id` (`m_id`);
ALTER TABLE `fx_point_log` ADD KEY `type` (`type`);
ALTER TABLE `fx_goods_comments` ADD COLUMN `gcom_pics`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '晒单图片';
ALTER TABLE `fx_orders`	ADD INDEX `o_pay_status` (`o_pay_status`);
ALTER TABLE `fx_orders`	ADD INDEX `o_audit` (`o_audit`);
ALTER TABLE `fx_orders`	ADD INDEX `o_status` (`o_status`);
ALTER TABLE `fx_orders`	ADD INDEX `lt_id` (`lt_id`); 
ALTER TABLE `fx_orders`	ADD KEY `o_crate_time` (`o_create_time`);
ALTER TABLE `fx_orders`	ADD INDEX `o_receiver_county` (`o_receiver_county`); 
INSERT INTO `fx_script_info` VALUES ('自动确认收货', 'confirmorderstatus', 'ConfirmOrderstatus', '5', '2014-06-20 16:32:29', '0', '1');
alter table fx_goods_products add pdt_min_num int(11) NOT NULL DEFAULT '0' COMMENT '商品( 单次下单最小值 )' after pdt_max_num;
alter table fx_goods_category add `gc_ad_type`  tinyint(4) NOT NULL DEFAULT 0 COMMENT '类目促销图片展示类型（0：Flash,1:图片展示）';
alter table fx_goods_comments add KEY `m_id` (`m_id`);
alter table fx_goods_comments add  KEY `g_id` (`g_id`);
alter table fx_goods_comments add  KEY `gcom_star_score` (`gcom_star_score`);
alter table fx_goods_comments add  KEY `gcom_verify` (`gcom_verify`);
alter table fx_goods_comments add  KEY `gcom_status` (`gcom_status`);
alter table fx_goods_comments add  KEY `u_id` (`u_id`);
alter table fx_goods_comments add  KEY `gcom_parentid` (`gcom_parentid`);
alter table fx_goods_comments add  KEY `gcom_update_time` (`gcom_update_time`);
INSERT INTO `fx_role_node` VALUES (NULL, 'pageTopAd', '首页头部广告图片管理', 'Home', '官网模板', '1', '10', '0');
DROP TABLE IF EXISTS `fx_merger_payment`;
CREATE TABLE `fx_merger_payment` (
  `mp_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '合并支付id',
  `o_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '订单id',
  `o_pay` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '每张订单已支付金额',
  `o_all_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '每张订单应付金额',
  `mp_all_price` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '合并订单总金额',
  `mp_create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '合并支付订单创建时间',
  KEY `mp_id` (`mp_id`),
  KEY `o_id` (`o_id`),
  KEY `mp_create_time` (`mp_create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合并支付表';