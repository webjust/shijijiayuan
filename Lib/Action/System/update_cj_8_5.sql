set names utf8;
/**
 * 城建项目资源再生数据
 * 通过对接城建接口把数据导到本地数据库
 * author huangcaijin@guanyisoft.com
 * date 2013-8-5
 */

DROP TABLE IF EXISTS `fx_cj_recources`;
CREATE TABLE `fx_cj_recources` (
  `cr_sn` varchar(10) NOT NULL DEFAULT '' COMMENT '资源编号',
  `cr_name` varchar(60) NOT NULL DEFAULT '' COMMENT '资源名称',
  `cr_spec` varchar(60) NOT NULL DEFAULT '' COMMENT '规格',
  `cr_unit` varchar(10) NOT NULL DEFAULT '' COMMENT '单位',
  `cr_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `cr_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据记录状态，0为废弃，1为有效',
  `cr_create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录创建时间',
  `cr_update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '记录最后更新时间',
  PRIMARY KEY (`cr_sn`),
  KEY `cr_name` (`cr_name`),
  KEY `cr_spec` (`cr_spec`),
  KEY `cr_unit` (`cr_unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='再生资源表';

DROP TABLE IF EXISTS `fx_cj_recovery_unit`;
CREATE TABLE `fx_cj_recovery_unit` (
  `cru_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '单位编号',
  `cru_name` varchar(200) NOT NULL DEFAULT '' COMMENT '单位名称',
  `cru_abbreviation` varchar(60) NOT NULL DEFAULT '' COMMENT '简称',
  `cru_address` varchar(200) NOT NULL DEFAULT '' COMMENT '地址',
  `cru_person` varchar(20) NOT NULL DEFAULT '' COMMENT '负责人',
  `cru_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `cru_fax` varchar(20) NOT NULL DEFAULT '' COMMENT '传真',
  `cru_email` varchar(20) NOT NULL DEFAULT '' COMMENT 'E-Mail',
  `cru_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cru_sn`),
  KEY `cru_name` (`cru_name`),
  KEY `cru_abbreviation` (`cru_abbreviation`),
  KEY `cru_address` (`cru_address`),
  KEY `cru_person` (`cru_person`),
  KEY `cru_phone` (`cru_phone`),
  KEY `cru_fax` (`cru_fax`),
  KEY `cru_email` (`cru_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='回收单位表';


DROP TABLE IF EXISTS `fx_cj_weighing_machine`;
CREATE TABLE `fx_cj_weighing_machine` (
  `cwm_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '称重编号',
  `cru_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '关联回收单位表编号',
  `cwm_model` varchar(200) NOT NULL DEFAULT '' COMMENT '地磅型号',
  `cwm_address` varchar(200) NOT NULL DEFAULT '' COMMENT '地址',
  `cwm_person` varchar(20) NOT NULL DEFAULT '' COMMENT '负责人',
  `cwm_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `cwm_fax` varchar(20) NOT NULL DEFAULT '' COMMENT '传真',
  `cwm_longitude` decimal(18,6) NOT NULL DEFAULT '0' COMMENT '经度',
  `cwm_latitude` decimal(18,6) NOT NULL DEFAULT '0' COMMENT '纬度',
  `cwm_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cwm_sn`),
  KEY `cru_sn` (`cru_sn`),
  KEY `cwm_longitude` (`cwm_longitude`),
  KEY `cwm_latitude` (`cwm_latitude`),
  KEY `cwm_model` (`cwm_model`),
  KEY `cwm_address` (`cwm_address`),
  KEY `cwm_person` (`cwm_person`),
  KEY `cwm_phone` (`cwm_phone`),
  KEY `cwm_fax` (`cwm_fax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='称重地磅表';


DROP TABLE IF EXISTS `fx_cj_carriers`;
CREATE TABLE `fx_cj_carriers` (
  `cc_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '公司编号',
  `cc_name` varchar(200) NOT NULL DEFAULT '' COMMENT '公司名称',
  `cc_type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型',
  `cc_address` varchar(200) NOT NULL DEFAULT '' COMMENT '地址',
  `cc_zip` varchar(20) NOT NULL DEFAULT '' COMMENT '邮编',
  `cc_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `cc_fax` varchar(20) NOT NULL DEFAULT '' COMMENT '传真',
  `cc_email` varchar(20) NOT NULL DEFAULT '' COMMENT 'E-Mail',
  `cc_person` varchar(20) NOT NULL DEFAULT '' COMMENT '负责人',
  `cc_bank` varchar(20) NOT NULL DEFAULT '' COMMENT '开户银行',
  `cc_bank_no` varchar(20) NOT NULL DEFAULT '' COMMENT '开户银行账号',
  `cc_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `cc_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cc_sn`),
  KEY `cc_name` (`cc_name`),
  KEY `cc_type` (`cc_type`),
  KEY `cc_address` (`cc_address`),
  KEY `cc_zip` (`cc_zip`),
  KEY `cc_phone` (`cc_phone`),
  KEY `cc_fax` (`cc_fax`),
  KEY `cc_person` (`cc_person`),
  KEY `cc_bank` (`cc_bank`),
  KEY `cc_bank_no` (`cc_bank_no`),
  KEY `cc_status` (`cc_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='运输公司信息表';


DROP TABLE IF EXISTS `fx_cj_vehicles`;
CREATE TABLE `fx_cj_vehicles` (
  `cv_no` varchar(20) NOT NULL DEFAULT '' COMMENT '车号',
  `cc_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '所属公司编号',
  `cv_type` varchar(60) NOT NULL DEFAULT '' COMMENT '车辆型号',
  `cv_number` varchar(200) NOT NULL DEFAULT '' COMMENT '车牌',
  `cv_brand` varchar(60) NOT NULL DEFAULT '' COMMENT '品牌(就是问什么牌子的车)',
  `cv_weight` numeric(9,2) NOT NULL DEFAULT '0' COMMENT '车重',
  `cv_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cv_no`),
  KEY `cc_sn` (`cc_sn`),
  KEY `cv_type` (`cv_type`),
  KEY `cv_number` (`cv_number`),
  KEY `cv_brand` (`cv_brand`),
  KEY `cv_weight` (`cv_weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='运输车辆信息表';


DROP TABLE IF EXISTS `fx_cj_agreement`;
CREATE TABLE `fx_cj_agreement` (
  `ca_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '合同编号',
  `ca_type` varchar(10) NOT NULL DEFAULT '' COMMENT '合同类型',
  `ca_unit` varchar(100) NOT NULL DEFAULT '' COMMENT '交易单位',
  `ca_project_name` varchar(100) NOT NULL DEFAULT '' COMMENT '回收资源工程名称',
  `ca_contacts` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人',
  `ca_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `ca_sign_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '签订合同时间',
  `ca_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `ca_clause1` varchar(200) NOT NULL DEFAULT '' COMMENT '条款1',
  `ca_clause2` varchar(200) NOT NULL DEFAULT '' COMMENT '条款2',
  `ca_clause3` varchar(200) NOT NULL DEFAULT '' COMMENT '条款3',
  `ca_clause4` varchar(200) NOT NULL DEFAULT '' COMMENT '条款4',
  `ca_console` varchar(20) NOT NULL DEFAULT '' COMMENT '操作员',
  `ca_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`ca_sn`),
  KEY `ca_type` (`ca_type`),
  KEY `ca_unit` (`ca_unit`),
  KEY `ca_project_name` (`ca_project_name`),
  KEY `ca_contacts` (`ca_contacts`),
  KEY `ca_phone` (`ca_phone`),
  KEY `ca_sign_time` (`ca_sign_time`),
  KEY `ca_status` (`ca_status`),
  KEY `ca_console` (`ca_console`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='回收合同表';


DROP TABLE IF EXISTS `fx_cj_agreement_demo`;
CREATE TABLE `fx_cj_agreement_demo` (
  `cad_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '回收合同明细表的自增ID',
  `ca_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '合同编号',
  `cr_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '资源编号',
  `cad_price` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '单价',
  `cad_freight` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '运费',
  `cad_tax` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '税额',
  `cad_remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cad_id`),
  KEY `ca_sn` (`ca_sn`),
  KEY `cr_sn` (`cr_sn`),
  KEY `cad_price` (`cad_price`),
  KEY `cad_freight` (`cad_freight`),
  KEY `cad_tax` (`cad_tax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='回收合同表明细表';


DROP TABLE IF EXISTS `fx_cj_weighing_records`;
CREATE TABLE `fx_cj_weighing_records` (
  `cwr_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '称重编号',
  `cwr_type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型',
  `cwr_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '日期',
  `ca_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '关联合同编号',
  `cc_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '关联运输单位编号',
  `cr_sn` varchar(20) NOT NULL DEFAULT '' COMMENT '关联资源编号',
  `cwr_gross_weight` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '毛重',
  `cwr_tare` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '皮重',
  `cwr_net_weight` numeric(9,3) NOT NULL DEFAULT '0' COMMENT '净重',
  `cwr_unit` varchar(10) NOT NULL DEFAULT '' COMMENT '单位',
  `cwr_gross_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '称毛重时间',
  `cwr_tare_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '称皮重时间',
  `cv_no` varchar(20) NOT NULL DEFAULT '' COMMENT '关联的车号',
  `cwr_driver` varchar(20) NOT NULL DEFAULT '' COMMENT '司机',
  `cwr_buckle_weight` numeric(12,3) NOT NULL DEFAULT '0' COMMENT '扣重',
  `cwr_number` numeric(12,3) NOT NULL DEFAULT '0' COMMENT '计算数量',
  `cwr_price` numeric(12,3) NOT NULL DEFAULT '0' COMMENT '单价',
  `cwr_freight_rates` numeric(12,3) NOT NULL DEFAULT '0' COMMENT '运价',
  `cwr_amount` numeric(12,3) NOT NULL DEFAULT '0' COMMENT '金额',
  `cwr_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `cwr_receiving_clerk` varchar(10) NOT NULL DEFAULT '' COMMENT '收料员',
  `cwr_driver_people` varchar(10) NOT NULL DEFAULT '' COMMENT '司称员',
  `cad_remark` varchar(40) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`cwr_sn`),
  KEY `cwr_type` (`cwr_type`),
  KEY `cwr_date` (`cwr_date`),
  KEY `cwr_gross_weight` (`cwr_gross_weight`),
  KEY `cwr_tare` (`cwr_tare`),
  KEY `cwr_net_weight` (`cwr_net_weight`),
  KEY `cwr_unit` (`cwr_unit`),
  KEY `cwr_gross_time` (`cwr_gross_time`),
  KEY `cwr_tare_time` (`cwr_tare_time`),
  KEY `cwr_driver` (`cwr_driver`),
  KEY `cwr_buckle_weight` (`cwr_buckle_weight`),
  KEY `cwr_number` (`cwr_number`),
  KEY `cwr_price` (`cwr_price`),
  KEY `cwr_freight_rates` (`cwr_freight_rates`),
  KEY `cwr_amount` (`cwr_amount`),
  KEY `cwr_status` (`cwr_status`),
  KEY `cwr_receiving_clerk` (`cwr_receiving_clerk`),
  KEY `cwr_driver_people` (`cwr_driver_people`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='称重记录表';


