set names utf8;

ALTER TABLE fx_groupbuy ADD COLUMN `gp_mobile_desc` text NOT NULL COMMENT '手机端描述';
ALTER TABLE fx_spike ADD COLUMN `sp_mobile_desc` text NOT NULL COMMENT '手机端描述';
ALTER TABLE fx_goods_products add `pdt_g_remark` varchar(255) COMMENT '商品备注';

del_idx('databaseAddIndex','fx_thd_orders_items','toi_outer_sku_id');
ALTER TABLE `fx_thd_orders_items` ADD KEY `toi_outer_sku_id` (`toi_outer_sku_id`);

CREATE TABLE `fx_goods_comment_statistics` (
	`gcs_id` INT(11) NOT NULL AUTO_INCREMENT,
	`g_id` INT(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
	`five_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '5星评论数',
	`four_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '4星评论数',
	`three_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '3星评论数',
	`two_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '2星评论数',
	`one_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '1星评论数',
	`total_count` INT(11) NOT NULL DEFAULT '0' COMMENT '总评论数',
	`average_score` INT(11) NOT NULL DEFAULT '0' COMMENT '平均评论得分',
	`last_month_count` INT(11) NOT NULL DEFAULT '0' COMMENT '最近一个月评论数',
	`two_months_count` INT(11) NOT NULL DEFAULT '0' COMMENT '最近2个月评论数',
	`three_months_count` INT(11) NOT NULL DEFAULT '0' COMMENT '最近3个月评论数',
	`pic_comment_count` INT(11) NOT NULL DEFAULT '0' COMMENT '总晒单数',
	`positive_count` INT(11) NOT NULL DEFAULT '0' COMMENT '好评数',
	`positive_ratio` FLOAT NOT NULL DEFAULT '0' COMMENT '好评率',
	PRIMARY KEY (`gcs_id`),
	INDEX `g_id` (`g_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='评论统计';