set names utf8;
DROP TABLE IF EXISTS `fx_related_coupon_red`;
CREATE TABLE `fx_related_coupon_red` (
  `c_name` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `rd_id` int(11) NOT NULL DEFAULT '0' COMMENT '规则id',
  KEY `c_name` (`c_name`),
  KEY `rd_id` (`rd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券与规则关联表';
ALTER TABLE fx_sms_log MODIFY `sms_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:普通发送短信；1：手机绑定；2：更换手机号；3：密码找回,4:重置密码,5:支付验证,6:门店自提提货验证';
INSERT INTO `fx_sms_templates`(code,subject,content,last_modify,last_send) values('GET_CODE','门店提货发送验证码','亲，您的订单单号为:{$ordernum},提货人姓名为:{$receiver_name},请验证后提货,谢谢.{$shop_name}','2015-05-18 17:00:00','2015-05-18 17:00:00');
