set names utf8;
DROP TABLE IF EXISTS `fx_qn_pic`;
CREATE TABLE `fx_qn_pic` (
  `pic_url` varchar(250) NOT NULL COMMENT '本地图片路径',
  `pic_qn_url` varchar(500) NOT NULL COMMENT '七牛访问下图片路径',
  `sign_end_time` datetime NOT NULL COMMENT '签名到期日期',
  PRIMARY KEY (`pic_url`),
  UNIQUE KEY `pic_url` (`pic_url`) USING BTREE,
  KEY `pic_qn_url` (`pic_qn_url`(255)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='七牛存储';
INSERT INTO `fx_payment_cfg` VALUES ('9', 'WAP支付宝', 'wapalipay', 'WAPALIPAY', '{\"alipay_account\":\"test\",\"pay_safe_code\":\"test\",\"identity_id\":\"test\",\"interface_type\":\"1\",\"pay_encryp\":\"MD5\"}', '0.000', 'WAP支付宝', '2015-05-06 09:32:23', '', '0', '1', '9', '2');
alter table `fx_members` add column `open_name`  varchar(50) NOT NULL DEFAULT '' COMMENT '第三方用户名';
alter table `fx_members` add column `open_token`  varchar(255) NOT NULL DEFAULT '' COMMENT '第三方登录token';
alter table `fx_members` add column `open_source`  varchar(100) NOT NULL DEFAULT '' COMMENT '第三方来源(QQ,新浪)';
alter table `fx_members` add column `open_id`  varchar(100) NOT NULL DEFAULT '' COMMENT '第三方登录唯一标示ID';
ALTER TABLE fx_members ADD KEY `open_id` (`open_id`);
