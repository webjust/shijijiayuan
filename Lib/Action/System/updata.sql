set names utf8;
/* *
 * 数据库更新文件我已合并到install.sql
 * 本次开始，所有人在提交sql更新时，务必在所提交的变更SQL前后
 * 注明自己的姓名、更新时间、更新原因
 *
 * 1. 提交SQL之前必须在自己机器上测试通过才允许提交！！！！
 * 2. 提交SQL文件不写注释的，拖出去枪毙三天！！！
 * 3. 第一行set names utf8 不允许删除！！！！
 * 4. 这段注释不允许删除！！！
 * 5. SQL脚本提交完毕以后务必在自己的SQL脚本尾部增加一行注释，标记自己的本次更新已经结束！！！
 * 6. 不允许第二次提交时将SQL写到上次自己更新的脚本段中。
 * 7. 已经提交的SQL不允许修改（如确需修改，新增一条表结构修改脚本）。
 */
/* 增加支付方式 Tom 2015-01-27 start */
INSERT INTO `fx_payment_cfg` VALUES (9,'手机支付宝','malipay','MALIPAY','{\"alipay_account\":\"test\",\"pay_safe_code\":\"请技术人员把证书上传到Lib\/Common\/Payments\/Malipay目录下\",\"identity_id\":\"test\",\"interface_type\":\"1\"}',0.000,'支付宝手机端支付','2015-01-21 17:57:03','0',1,1,0)
/* 增加支付方式 Tom 2015-01-27 end */

/* 增加是否在手机app上显示 Tom 2015-01-27 start */
ALTER TABLE `fx_goods_info` ADD COLUMN `mobile_show`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '手机端是否显示(1显示,0不显示)';
/* 增加是否在手机app上显示 Tom 2015-01-27 end */
/* 增加订单来源 Tom 2015-01-27 start */
ALTER TABLE `fx_orders` ADD COLUMN `o_source`  varchar(10) NULL DEFAULT 'pc' COMMENT '订单来源(pc,andriod,ios)';
/* 增加订单来源 Tom 2015-01-27 end */

