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
 * 8. 添加索引之前需要添加以下方法del_idx('databaseAddIndex','表名','索引名'),databaseAddIndex固定
 */
ALTER TABLE fx_orders add COLUMN receiver_real_name  varchar(50) NOT NULL DEFAULT '' COMMENT '会员真实姓名';