set names utf8;
DROP TABLE IF EXISTS `fx_related_coupon_red`;
CREATE TABLE `fx_related_coupon_red` (
  `c_name` varchar(50) NOT NULL DEFAULT '' COMMENT '�Ż�ȯ����',
  `rd_id` int(11) NOT NULL DEFAULT '0' COMMENT '����id',
  KEY `c_name` (`c_name`),
  KEY `rd_id` (`rd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='�Ż�ȯ����������';
ALTER TABLE fx_sms_log MODIFY `sms_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:��ͨ���Ͷ��ţ�1���ֻ��󶨣�2�������ֻ��ţ�3�������һ�,4:��������,5:֧����֤,6:�ŵ����������֤';
INSERT INTO `fx_sms_templates`(code,subject,content,last_modify,last_send) values('GET_CODE','�ŵ����������֤��','�ף����Ķ�������Ϊ:{$ordernum},���������Ϊ:{$receiver_name},����֤�����,лл.{$shop_name}','2015-05-18 17:00:00','2015-05-18 17:00:00');
