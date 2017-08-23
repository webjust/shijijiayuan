set names utf8;

/* 淘宝供应商授权信息表 -- 每个客户只能有一个供应商，所以此表只能存一条记录！*/
drop table if exists `fx_top_supplier_info`;
create table `fx_top_supplier_info`(
	`top_supplier_id` int(11) not null default 0 comment '供应商ID',
	`top_supplier_nick` varchar(255) not null default 0 comment '供应商nick',
	`top_access_token` varchar(255) not null default '' comment '访问token',
	`top_expires_in` timestamp not null default '0000-00-00 00:00:00' comment 'token过期时间',
	`top_refresh_token` varchar(255) not null default '' comment '刷新访问token使用的token',
	`top_refresh_expires_in` timestamp not null default '0000-00-00 00:00:00' comment '刷新token的token过期时间',
	`top_w1_expires_in` int(11) not null default 0 comment 'w1权限字段授权过期时间，单位是秒',
	`top_w2_expires_in` int(11) not null default 0 comment 'w2权限字段授权过期时间，单位是秒',
	`top_r1_expires_in` int(11) not null default 0 comment 'r1权限字段授权过期时间，单位是秒',
	`top_r2_expires_in` int(11) not null default 0 comment 'r2权限字段授权过期时间，单位是秒',
	`top_oauth_time` timestamp not null default '0000-00-00 00:00:00' comment '授权时间',
	primary key `top_supplier_id` (`top_supplier_id`),
	unique key `top_supplier_nick` (`top_supplier_nick`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供应商信息表';

/* 淘宝分销合作关系表 */
drop table if exists `fx_top_fenxiao_cooperation`;
create table `fx_top_fenxiao_cooperation`(
	`cooperate_id` int(11) not null auto_increment comment '合作关系ID',
	`distributor_id` int(11) not null default 0 comment '分销/代销商淘宝ID',
	`distributor_nick` varchar(255) not null default '' comment '分销/代销淘宝nick',
	`product_line` varchar(255) not null default '' comment '授权产品线，多个产品线之间使用逗号分隔',
	`grade_id` int(11) not null default 0 comment '等级ID',
	`trade_type` varchar(20) not null default '' comment '分销方式，AGENT分销，DEALER经销',
	`auth_payway` varchar(255) not null default '' comment '授权支付方式：ALIPAY(支付宝)、OFFPREPAY(预付款)、OFFTRANSFER(转帐)、OFFSETTLEMENT(后期结算)',
	`supplier_id` int(11) not null default 0 comment '供应商ID',
	`supplier_nick` varchar(255) not null default '' comment '供应商nick',
	`start_date` timestamp not null default '0000-00-00 00:00:00' comment '合作起始时间',
	`end_date` timestamp not null default '0000-00-00 00:00:00' comment '合作终止时间',
	`status` varchar(10) not null default '' comment '合作状态，NORMAL，正常；END，终止；ENDING，终止中',
	`product_line_name` varchar(255) not null default '' comment '产品线名称，多个产品线之间使用逗号分隔',
	primary key `cooperate_id` (`cooperate_id`),
	unique key `distributor_id` (`distributor_id`),
	unique key `distributor_nick` (`distributor_nick`),
	key `auth_payway` (`auth_payway`),
	key `trade_type` (`trade_type`),
	key `supplier_id` (`supplier_id`),
	key `start_date` (`start_date`),
	key `end_date` (`end_date`),
	key `status` (`status`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='淘宝分销合作关系表';

/* 供销平台数据下载记录表 */
drop table if exists `fx_top_fenxiao_download_log`;
create table `fx_top_fenxiao_download_log`(
	`id` int(11) not null auto_increment comment '主键，自增',
	`start_time` timestamp not null default '0000-00-00 00:00:00',
	`end_time` timestamp not null default '0000-00-00 00:00:00',
	`status` tinyint(1) not null default 0 comment '下载进程状态，0为未开始，1为进行中，2为下载结束',
	`present` int(3) not null default 0 comment '下载完成率百分比，此参数在异常中断时有效',
	primary key `id` (`id`),
	key `start_time` (`start_time`),
	key `end_time` (`end_time`),
	key `status` (`status`),
	key `present` (`present`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='淘宝供销平台数据下载记录表';

/* 供销平台商品资料表 */
drop table if exists `fx_top_goods_info`;
create table `fx_top_goods_info`(
	`pid` bigint(20) not null default 0 comment '产品ID，主键',
	`trade_type` varchar(20) not null default '' comment '分销方式：AGENT（只做代销，默认值）、DEALER（只做经销）、ALL（代销和经销都做）',
	`is_authz` varchar(10) not null default '' comment '查询产品列表时，查询入参增加is_authz:yes|no yes:需要授权 no:不需要授权',
	`name` varchar(255) not null default '' comment '产品名称',
	`outer_id` varchar(255) not null default '' comment '商家编码',
	`desc_path` varchar(255) not null default '' comment 'dpc路径，即产品描述路径',
	`items_count` int(11) not null default 0 comment '下载人数',
	`orders_count` int(11) not null default 0 comment '累计采购次数',
	`standard_price` decimal(10,3) not null default 0.00 comment '采购基准价，单位：元',
	`dealer_cost_price` decimal(10,3) not null default 0.00 comment '经销采购价',
	`retail_price_low` decimal(10,3) not null default 0.00 comment '最低零售价，单位：分',
	`retail_price_high` decimal(10,3) not null default 0.00 comment '最高零售价，单位：分',
	`upshelf_time` timestamp not null default '0000-00-00 00:00:00' comment '铺货时间',
	`tgi_update_time` timestamp not null default '0000-00-00 00:00:00' comment '数据更改时间',
	primary key `pid` (`pid`),
	key `trade_type` (`trade_type`),
	key `is_authz` (`is_authz`),
	key `name` (`name`),
	key `outer_id` (`outer_id`),
	key `items_count` (`items_count`),
	key `orders_count` (`orders_count`),
	key `standard_price` (`standard_price`),
	key `dealer_cost_price` (`dealer_cost_price`),
	key `upshelf_time` (`upshelf_time`),
	key `tgi_update_time` (`tgi_update_time`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台商品资料表';

/* 供销平台商品SKU资料表 */
drop table if exists `fx_top_goods_sku`;
create table `fx_top_goods_sku`(
	`id` bigint(20) not null default 0 comment 'sku id',
	`pid` bigint(20) not null default 0 comment '商品ID',
	`standard_price` decimal(10,3) not null default 0.00 comment '市场价',
	`properties` text comment 'sku的销售属性组合字符串。格式:pid:vid;pid:vid,如:1627207:3232483;1630696:3284570,表示:机身颜色:军绿色;手机套餐:一电一充。',
	`cost_price` decimal(10,3) not null default 0.00 comment '代销采购价',
	`dealer_cost_price` decimal(10,3) not null default 0.00 comment '经销采购价',
	`scitem_id` bigint(20) not null default 0 comment '关联的后端商品ID',
	`name` varchar(255) not null default '' comment '名称',
	`outer_id` varchar(255) not null default '' comment '商家编码',
	`tgs_update_time` timestamp not null default '0000-00-00 00:00:00' comment '数据更新时间',
	primary key `id` (`id`),
	key `pid` (`pid`),
	key `standard_price` (`standard_price`),
	key `cost_price` (`cost_price`),
	key `dealer_cost_price` (`dealer_cost_price`),
	key `scitem_id` (`scitem_id`),
	key `name` (`name`),
	key `outer_id` (`outer_id`),
	key `tgs_update_time` (`tgs_update_time`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台商品SKU资料表';


/* 供销平台采购单表 */
drop table if exists `fx_top_purchase_order`;
create table `fx_top_purchase_order`(
	`fenxiao_id` bigint(20) not null default 0 comment 'fenxiao_id',
	`pay_type` varchar(20) not null default '' comment 'pay_type支付方式',
	`trade_type` varchar(10) not null default '' comment '分销方式：AGENT（代销）、DEALER（经销）',
	`distributor_from` varchar(10) not null default '' comment '分销商来源网站（taobao）',
	`id` bigint(20) not null default 0 comment '供应商交易ID 非采购单ID,发货时使用该ID',
	`status` varchar(20) not null default '' comment '采购单交易状态',
	`memo` varchar(255) not null default '' comment '采购单留言,代销模式下信息包括买家留言和分销商留言',
	`shipping` varchar(20) not null default '' comment '配送方式',
	`logistics_company_name`  varchar(20) not null default '' comment '物流公司名称',
	`logistics_id` varchar(30) not null default '' comment '物流单号',
	`order_messages` text comment '采购单留言列表,json格式',
	`created` timestamp not null default '0000-00-00 00:00:00' comment '采购单创建时间',
	`analysis` varchar(30) not null default '' comment '乱价标识:true/false/error',
	`cuanhuo` varchar(30) not null default '' comment '串货标识:true/false/error',
	primary key `fenxiao_id` (`fenxiao_id`),
	key `id` (`id`),
	key `status` (`status`),
	key `trade_type` (`trade_type`),
	key `created` (`created`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台采购单表';

/* 供销平台子采购单表（订单详情） */
drop table if exists `fx_top_subpurchase_order`;
create table `fx_top_subpurchase_order`(
	`sub_id` bigint(20) not null auto_increment comment '本表自增主键',
	`fenxiao_id` bigint(20) not null default 0 comment 'fenxiao_id',
	`status` varchar(20) not null default '' comment '交易状态',
	`refund_fee` decimal(10,3) not null default 0.00 comment '退款金额', 
	`item_id` bigint(20) not null default 0 comment '分销平台上的产品id，同FenxiaoProduct 的pid',
	`order_200_status` varchar(20) not null default '' comment '代销采购单对应下游200订单状态',
	`auction_price` decimal(10,3) not null default 0.00 comment '分销商店铺中宝贝一口价',
	`num` int(8) not null default 0 comment '产品的采购数量',
	`title` varchar(255) not null default '' comment '采购的产品标题',
	`price` decimal(10,3) not null default 0.00 comment '产品的采购价格',
	`total_fee` decimal(10,3) not null default 0.00 comment '分销商应付金额=num(采购数量)*price(采购价)',
	`distributor_payment` decimal(10,3) not null default 0.00 comment '分销商实付金额=total_fee（分销商应付金额）+改价-优惠',
	`buyer_payment` decimal(10,3) not null default 0.00 comment '买家订单上对应的子单零售金额，除以num（数量）后等于最终宝贝的零售价格',
	`bill_fee` decimal(10,3) not null default 0.00 comment '发票应开金额',
	`sc_item_id` bigint(20) not null default 0 comment '后端商品id',
	`item_outer_id` varchar(50) not null default '' comment '分销平台上商品商家编码',
	`sku_outer_id` varchar(50) not null default '' comment 'SKU商家编码', 
	`sku_properties` varchar(255) not null default '' comment 'SKU属性值。如: 颜色:红色:别名;尺码:L:别名',
	`created` timestamp not null default '0000-00-00 00:00:00' comment '采购单创建时间',
	primary key `sub_id` (`sub_id`),
	key `fenxiao_id` (`fenxiao_id`),
	key `status` (`status`),
	key `created` (`created`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台子采购单表（订单详情）';

/* 供销平台经销交易监控表 */
drop table if exists `fx_top_trademonitor`;
create table `fx_top_trademonitor`(
	`trade_monitor_id` bigint(20) not null default 0 comment 'trade_monitor_id',
	`distributor_nick` varchar(20) not null default '' comment '经销商的淘宝账号昵称',
	`product_id` bigint(20) not null default 0 comment '供应商的产品id',
	`product_title` varchar(20) not null default '' comment '供应商的产品标题',
	`product_number` varchar(20) not null default '' comment '供应商的产品的商家编码',
	`tc_order_id` bigint(20) not null default 0 comment '交易订单号',
	`sub_tc_order_id` bigint(20) not null default 0 comment '交易订单的子订单号',
	`status` varchar(20) not null default '' comment '采购单交易状态',
	`item_id` bigint(20) not null default 0 comment '交易订单的商品id',
	`item_title` varchar(255)  not null default '' comment '交易订单的商品标题',
	`item_number` varchar(255)  not null default '' comment '交易订单的商品的商家编码',
	`item_sku_number` varchar(255)  not null default '' comment '交易订单的商品的sku商家编码',
	`product_sku_number` varchar(255) not null default '' comment '供应商的产品的sku商家编码',
	`item_price` decimal(10,3) not null default 0.00 comment '交易订单的商品价格',
	`item_total_price` decimal(10,3) not null default 0.00 comment '交易订单的商品总价格（单价×数量+改价+优惠）',
	`buy_amount` int(10) not null default 0 comment '交易订单的商品购买数量',
	`pay_time` timestamp not null default '0000-00-00 00:00:00' comment '交易订单的付款时间',
	`buyer_nick` varchar(255) not null default '' comment '买家的淘宝账号昵称',
	`item_sku_name` varchar(255) not null default '' comment '交易订单的商品的sku名称',
	`retail_price_low` decimal(10,3) not null default 0.00 comment '交易订单的商品最低零售价',
	`retail_price_high` decimal(10,3) not null default 0.00 comment '交易订单的商品最高零售价',
	`analysis` varchar(30) not null default '' comment '乱价标识:true/false/error',
	primary key `trade_monitor_id` (`trade_monitor_id`),
	key `status` (`status`),
	key `analysis` (`analysis`),
	key `pay_time` (`pay_time`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台经销交易监控表';

/* 淘宝商品下载记录表 */
drop table if exists `fx_top_distributor_items`;
create table `fx_top_distributor_items`(
	`distributor_id` int(11) not null default 0 comment '分销商ID',
	`item_id` bigint(20) not null default 0 comment '商品ID',
	`product_id` bigint(20) not null default 0 comment '产品ID',
	`created` timestamp not null default '0000-00-00 00:00:00' comment '下载时间',
	`trade_type` varchar(20) not null default '' comment '分销方式：AGENT（只做代销，默认值）、DEALER（只做经销）',
	primary key `pk` (`distributor_id`,`product_id`),
	key `created` (`created`),
	key `trade_type` (`trade_type`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台商品下载记录';

/* 淘宝供销平台分销等级 */
drop table if exists `fx_top_distributor_grades`;
create table `fx_top_distributor_grades`(
	`grade_id` int(11) not null default 0 comment '等级ID，主键',
	`name` varchar(255) not null default '' comment '分销商等级名称',
	`created` timestamp not null default '0000-00-00 00:00:00' comment '记录创建时间',
	`modified` timestamp not null default '0000-00-00 00:00:00' comment '记录更新时间',
	primary key `grade_id` (`grade_id`),
	key `name` (`name`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='供销平台分销等级数据下载';