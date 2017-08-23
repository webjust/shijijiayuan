set names utf8;
DROP TABLE IF EXISTS `fx_qn_pic`;
CREATE TABLE `fx_qn_pic` (
  `pic_url` varchar(250) NOT NULL COMMENT '����ͼƬ·��',
  `pic_qn_url` varchar(500) NOT NULL COMMENT '��ţ������ͼƬ·��',
  `sign_end_time` datetime NOT NULL COMMENT 'ǩ����������',
  PRIMARY KEY (`pic_url`),
  UNIQUE KEY `pic_url` (`pic_url`) USING BTREE,
  KEY `pic_qn_url` (`pic_qn_url`(255)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='��ţ�洢';
INSERT INTO `fx_payment_cfg` VALUES ('9', 'WAP֧����', 'wapalipay', 'WAPALIPAY', '{\"alipay_account\":\"test\",\"pay_safe_code\":\"test\",\"identity_id\":\"test\",\"interface_type\":\"1\",\"pay_encryp\":\"MD5\"}', '0.000', 'WAP֧����', '2015-05-06 09:32:23', '', '0', '1', '9', '2');
alter table `fx_members` add column `open_name`  varchar(50) NOT NULL DEFAULT '' COMMENT '�������û���';
alter table `fx_members` add column `open_token`  varchar(255) NOT NULL DEFAULT '' COMMENT '��������¼token';
alter table `fx_members` add column `open_source`  varchar(100) NOT NULL DEFAULT '' COMMENT '��������Դ(QQ,����)';
alter table `fx_members` add column `open_id`  varchar(100) NOT NULL DEFAULT '' COMMENT '��������¼Ψһ��ʾID';
ALTER TABLE fx_members ADD KEY `open_id` (`open_id`);
