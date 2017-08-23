set names utf8;

ALTER TABLE fx_groupbuy ADD COLUMN `gp_mobile_desc` text NOT NULL COMMENT '�ֻ�������';
ALTER TABLE fx_spike ADD COLUMN `sp_mobile_desc` text NOT NULL COMMENT '�ֻ�������';
ALTER TABLE fx_goods_products add `pdt_g_remark` varchar(255) COMMENT '��Ʒ��ע';

del_idx('databaseAddIndex','fx_thd_orders_items','toi_outer_sku_id');
ALTER TABLE `fx_thd_orders_items` ADD KEY `toi_outer_sku_id` (`toi_outer_sku_id`);

CREATE TABLE `fx_goods_comment_statistics` (
	`gcs_id` INT(11) NOT NULL AUTO_INCREMENT,
	`g_id` INT(11) NOT NULL DEFAULT '0' COMMENT '��ƷID',
	`five_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '5��������',
	`four_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '4��������',
	`three_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '3��������',
	`two_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '2��������',
	`one_star_count` INT(11) NOT NULL DEFAULT '0' COMMENT '1��������',
	`total_count` INT(11) NOT NULL DEFAULT '0' COMMENT '��������',
	`average_score` INT(11) NOT NULL DEFAULT '0' COMMENT 'ƽ�����۵÷�',
	`last_month_count` INT(11) NOT NULL DEFAULT '0' COMMENT '���һ����������',
	`two_months_count` INT(11) NOT NULL DEFAULT '0' COMMENT '���2����������',
	`three_months_count` INT(11) NOT NULL DEFAULT '0' COMMENT '���3����������',
	`pic_comment_count` INT(11) NOT NULL DEFAULT '0' COMMENT '��ɹ����',
	`positive_count` INT(11) NOT NULL DEFAULT '0' COMMENT '������',
	`positive_ratio` FLOAT NOT NULL DEFAULT '0' COMMENT '������',
	PRIMARY KEY (`gcs_id`),
	INDEX `g_id` (`g_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='����ͳ��';