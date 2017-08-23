set names utf8;
DROP TABLE IF EXISTS `fx_refunds_reason`;
CREATE TABLE `fx_refunds_reason` (
  `rr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '退换货理由ID',
  `rr_name` varchar(50) NOT NULL DEFAULT '' COMMENT '退换货理由',
  `rr_show_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1退款 2退货',
  `rr_order` int(11) NOT NULL DEFAULT '0' COMMENT '属性排序',
  `rr_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，1为有效，0为删除',
  `rr_is_display` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否前台显示，1为显示，0为不显示',
  `rr_is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认，1为默认，0为不是默认',
  `rr_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `rr_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '记录最新修改时间',
  PRIMARY KEY (`rr_id`),
  KEY `rr_show_type` (`rr_show_type`) USING BTREE,
  KEY `rr_status` (`rr_status`) USING BTREE,
  KEY `rr_order` (`rr_order`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='退款退货用户自定义表';

INSERT INTO fx_refunds_reason VALUE ("1",'卖家缺货','1','1','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("2",'收到的物品不符','1','2','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("3",'商品质量问题','1','3','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("4",'未按约定时间发货','1','4','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("5",'买家拍错商品','1','5','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("6",'与卖家协商一致','1','6','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("7",'其他','1','7','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');

INSERT INTO fx_refunds_reason VALUE ("8",'七天无理由退换货','1','2','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("9",'收到假货','2','2','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("10",'商品需要维修','2','3','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("11",'发票问题','2','4','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("12",'收到商品破损','2','5','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("13",'商品错发/漏发','2','6','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("14",'收到商品描述不符','2','7','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("15",'商品未按约定时间发货','2','8','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');
INSERT INTO fx_refunds_reason VALUE ("16",'商品质量问题','2','9','1','1','1','2015-07-03 10:52:52','2015-07-03 10:52:52');

ALTER TABLE `fx_goods` ADD COLUMN `g_order`  int(5) NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）';
ALTER TABLE `fx_presale` ADD COLUMN `p_tiered_pricing_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '价格阶梯类型';

ALTER TABLE `fx_groupbuy` ADD COLUMN `gp_tiered_pricing_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '价格阶梯类型';

ALTER TABLE `fx_spike` ADD COLUMN `sp_tiered_pricing_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '价格优惠类型';

DROP TABLE IF EXISTS `fx_coupon_activities`;
CREATE TABLE `fx_coupon_activities` (
  `ca_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `ca_name` varchar(50) NOT NULL DEFAULT '' COMMENT '活动名称',
  `ca_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '同号为优惠券编码,异号为活动生成优惠券规则明细',
  `ca_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '活动类型（0:同号券，1:异号券，2:注册券）',
  `ca_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有效,默认0,为有效',
  `ca_total` int(11) NOT NULL DEFAULT '0' COMMENT '总数',
  `ca_used_num` int(11) NOT NULL DEFAULT '0' COMMENT '已领取的数量',
  `ca_limit_nums` int(11) NOT NULL DEFAULT '0' COMMENT '限制一个会员最多可以参与活动的次数,默认0,不限制',
  `ca_memo` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `ca_ggid` varchar(255) NOT NULL DEFAULT '' COMMENT '所属的商品分组',
  `ca_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动开始时间',
  `ca_end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '活动结束时间',
  `ca_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `c_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '优惠券类型（0：现金券，1:折扣券）',
  `c_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '优惠券可用开始时间',
  `c_end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '优惠券可用结束时间',
  `c_money` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `c_condition_money` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT '使用条件(满足订单多少钱使用)',
  PRIMARY KEY (`ca_id`),
  KEY `ca_name` (`ca_name`),
  KEY `ca_sn` (`ca_sn`), 
  KEY `ca_type` (`ca_type`),
  KEY `ca_status` (`ca_status`),
  KEY `ca_ggid` (`ca_ggid`),
  KEY `ca_start_time` (`ca_start_time`),
  KEY `ca_end_time` (`ca_end_time`),
  KEY `c_start_time` (`c_start_time`),
  KEY `c_end_time` (`c_end_time`),
  KEY `c_type` (`c_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券活动表';

ALTER TABLE `fx_coupon` ADD COLUMN `ca_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联优惠券活动表';

ALTER TABLE `fx_promotion` ADD COLUMN `pmn_category` varchar(100) NOT NULL COMMENT '促销关联类目';
ALTER TABLE `fx_promotion` ADD COLUMN `pmn_brand` varchar(100) NOT NULL COMMENT '促销关联品牌';
DROP TABLE IF EXISTS `fx_related_promotion_goods_brand`;
CREATE TABLE `fx_related_promotion_goods_brand` (
  `rpgb_id` int(11) NOT NULL AUTO_INCREMENT,
  `pmn_id` int(11) NOT NULL COMMENT '促销ID',
  `brand_id` int(11) NOT NULL COMMENT '商品品牌ID',
  PRIMARY KEY (`rpgb_id`),
  KEY `pmn_id` (`pmn_id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='促销与商品品牌关联表';

DROP TABLE IF EXISTS `fx_related_promotion_goods_category`;
CREATE TABLE `fx_related_promotion_goods_category` (
  `rpgc_id` int(11) NOT NULL AUTO_INCREMENT,
  `pmn_id` int(11) NOT NULL COMMENT '促销ID',
  `gc_id` int(11) NOT NULL COMMENT '商品品牌ID',
  PRIMARY KEY (`rpgc_id`),
  KEY `pmn_id` (`pmn_id`),
  KEY `gc_id` (`gc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='促销与商品分类关联表';

update fx_payment_cfg set pc_source=7 where pc_id=1;
update fx_payment_cfg set pc_source=1 where pc_id=2;
update fx_payment_cfg set pc_source=1 where pc_id=3;
update fx_payment_cfg set pc_source=1 where pc_id=4;
update fx_payment_cfg set pc_source=1 where pc_id=5;
update fx_payment_cfg set pc_source=5 where pc_id=6;
update fx_payment_cfg set pc_source=1 where pc_id=7;
update fx_payment_cfg set pc_source=1 where pc_id=8;
update fx_payment_cfg set pc_source=6 where pc_id=9;
update fx_payment_cfg set pc_source=5 where pc_id=12;
update fx_payment_cfg set pc_source=4 where pc_id=13;
ALTER TABLE `fx_payment_cfg` MODIFY COLUMN `pc_source` int COMMENT '1 PC端,3 PC端或APP,4 wap ,5 PC端或wap,6 APP 或wap ,其他 PC 端 或APP或 wap ';

ALTER TABLE `fx_orders` ADD COLUMN `channel_id`  varchar(255) NULL DEFAULT '' COMMENT 'cps 订单来源' , ADD COLUMN `channel_related_info`  varchar(255) NULL DEFAULT '' COMMENT '联合登陆存储的一些相关信息 包括 u_id ';

ALTER TABLE `fx_members` ADD COLUMN `union_data`  varchar(255) NULL DEFAULT '' COMMENT '来源cps的一些数据 u_id username usersafekey json格式存储';

alter table `fx_balance_info` add `local_ip` varchar(100) NOT NULL DEFAULT '' COMMENT '记录ip';

INSERT INTO `fx_script_info` VALUES ('自动确认收货', 'confirmorderstatus', 'ConfirmOrderstatus', '10', '2015-11-24 13:00:00', '0', '1');
INSERT INTO `fx_script_info` VALUES ('自动确认完成', 'FinishOrderstatus', 'FinishOrderstatus', '10', '2015-11-24 13:00:00', '0', '1');

ALTER TABLE `fx_members` ADD COLUMN `m_role` ENUM('distributor','buyer') NOT NULL DEFAULT 'buyer' COMMENT '会员角色' ,ADD INDEX `m_role` (`m_role`);

INSERT INTO `fx_payment_cfg` (`pc_id`,`pc_custom_name`,`pc_pay_type`,`pc_abbreviation`,`pc_memo`,`erp_payment_id`,`pc_status`,`pc_trd`,`pc_position`) VALUES ('15','交行支付','bocompay','BOCOMPAY','此支付方式为中国交通银行支付','0','0','1','15');

update fx_city_region set cr_name='栾城区' where cr_id='130124';
update fx_city_region set cr_name='藁城区' where cr_id='130182';
update fx_city_region set cr_name='鹿泉区' where cr_id='130185';
update fx_city_region set cr_name='浑南区' where cr_id='210112';
update fx_city_region set cr_name='九台区' where cr_id='220181';
update fx_city_region set cr_name='藁城区' where cr_id='130182';
update fx_city_region set cr_name='双城区' where cr_id='230182';
update fx_city_region set cr_name='赣榆区' where cr_id='320721';
update fx_city_region set cr_name='富阳区' where cr_id='330183';
update fx_city_region set cr_name='柯桥区' where cr_id='330621';
update fx_city_region set cr_name='上虞区' where cr_id='330682';
update fx_city_region set cr_name='建阳区' where cr_id='350784';
update fx_city_region set cr_name='永定区' where cr_id='350822';
update fx_city_region set cr_name='南康区' where cr_id='360782';
update fx_city_region set cr_name='广丰区' where cr_id='361122';
update fx_city_region set cr_name='兖州区' where cr_id='370882';
update fx_city_region set cr_name='文登区' where cr_id='371081';
update fx_city_region set cr_name='兰陵县' where cr_id='371324';
update fx_city_region set cr_name='陵城区' where cr_id='371421';
update fx_city_region set cr_name='沾化区' where cr_id='371624';
update fx_city_region set cr_name='祥符区' where cr_id='410224';
update fx_city_region set cr_name='陕州区' where cr_id='411222';
update fx_city_region set cr_name='郧阳区' where cr_id='420321';
update fx_city_region set cr_name='增城区' where cr_id='440183';
update fx_city_region set cr_name='从化区' where cr_id='440184';
update fx_city_region set cr_name='电白区' where cr_id='440903';
update fx_city_region set cr_name='梅县区' where cr_id='441421';
update fx_city_region set cr_name='阳东区' where cr_id='441723';
update fx_city_region set cr_name='云安区' where cr_id='445323';
update fx_city_region set cr_name='武鸣区' where cr_id='450122';
update fx_city_region set cr_name='铜梁区' where cr_id='500224';
update fx_city_region set cr_name='璧山区' where cr_id='500227';
update fx_city_region set cr_name='彭山区' where cr_id='511422';
update fx_city_region set cr_name='康定市' where cr_id='513321';
update fx_city_region set cr_name='平坝区' where cr_id='520421';
update fx_city_region set cr_name='香格里拉市' where cr_id='533421';
update fx_city_region set cr_name='昌都市' where cr_id='542100';
update fx_city_region set cr_name='卡若区' where cr_id='542121';
update fx_city_region set cr_name='桑珠孜区' where cr_id='542301';
update fx_city_region set cr_name='日喀则市' where cr_id='542300';
update fx_city_region set cr_name='林芝市' where cr_id='542600';
update fx_city_region set cr_name='巴宜区' where cr_id='542621';
update fx_city_region set cr_name='高陵区' where cr_id='610126';
update fx_city_region set cr_name='平安区' where cr_id='632121';
update fx_city_region set cr_name='吐鲁番市' where cr_id='652100';
update fx_city_region set cr_name='高昌区' where cr_id='652101';

alter table `fx_integral` add `integral_need` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '积分+金额兑换 所需积分';

DROP TABLE IF EXISTS `fx_spike_log`;
CREATE TABLE `fx_spike_log` (
  `pl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '秒杀日志ID',
  `o_id` bigint(20) NOT NULL COMMENT '订单ID',
  `sp_id` int(10) NOT NULL DEFAULT '0' COMMENT '秒杀ID',
  `m_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `g_id` int(10) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `num` int(4) NOT NULL DEFAULT '0' COMMENT '购买数量。取值范围:大于零的整数',
  `pl_remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`pl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='秒杀日志表';