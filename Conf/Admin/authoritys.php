<?php
/**
 * @author Terry <wanghui@guanyisoft.com>
 * @date 2013-1-29
 * @return array 认证数组
 */
$authoritys = array();
//不需要认证的模板中的操作
/*
 * $authoritys['no'] = array(
 *      'System'        //不需要认证的模块名
 *      'pageEditAdminPasswd'       //对应模块下的方法名
 * )
 */

$authoritys['no']['Index']['index'] = 1;
$authoritys['no']['Announcement']['pageList'] = 1;
$authoritys['no']['Home']['index'] = 1;
$authoritys['no']['Members']['cityRegionOptions'] = 1;
$authoritys['no']['Consultation']['pageList'] = 1;
$authoritys['no']['Article']['pageList'] = 1;
$authoritys['no']['Authorize']['pageSet'] = 1;
$authoritys['no']['Coupon']['pageList'] = 1;
$authoritys['no']['Delivery']['pageList'] = 1;
$authoritys['no']['Email']['pageSmtp'] = 1;
$authoritys['no']['Erp']['pageSet'] = 1;
$authoritys['no']['ErpCategory']['pageList'] = 1;
$authoritys['no']['ErpProducts']['erpPageList'] = 1;
$authoritys['no']['Financial']['pageListOffline'] = 1;
$authoritys['no']['GoodsBrand']['pageList'] = 1;
$authoritys['no']['Notice']['pageList'] = 1;
$authoritys['no']['Goods']['getProductsInfo'] = 1;
$authoritys['no']['Goods']['searchOrdersPdtInfo'] = 1;
$authoritys['no']['Orders']['ordersList'] = 1;
$authoritys['no']['Orders']['computePrice'] = 1;
$authoritys['no']['Orders']['doCoupon'] = 1;
$authoritys['no']['Links']['pageList'] = 1;
$authoritys['no']['Memberlevel']['pageList'] = 1;
$authoritys['no']['Notice']['pageList'] = 1;
$authoritys['no']['Orders']['pageList'] = 1;
$authoritys['no']['Products']['pageList'] = 1;
$authoritys['no']['Promotions']['pageList'] = 1;
$authoritys['no']['Role']['pageList'] = 1;
$authoritys['no']['RoleNode']['pageList'] = 1;

$authoritys['no']['System']['pageEditAdminPasswd'] = 1;
$authoritys['no']['System']['doEditPasswd'] = 1;
$authoritys['no']['Online']['getCategoryInfo'] = 1;
$authoritys['no']['Announcement']['getMemberTr'] = 1;
$authoritys['no']['ErpProducts']['doAddERPGoods'] = 1;
$authoritys['no']['ErpProducts']['erpGoodsList'] = 1;
$authoritys['no']['ErpCategory']['getErpCategoryChildren'] = 1;
$authoritys['no']['ErpCategory']['erpCategoryList'] = 1;
$authoritys['no']['ErpCategory']['erpChildrenList'] = 1;
$authoritys['no']['Promotions']['getMemberTr'] = 1;
$authoritys['no']['Coupon']['getCheck'] = 1;
$authoritys['no']['Members']['getCheckName'] = 1;
$authoritys['no']['Memberlevel']['checkMemberLevelEdit'] = 1;
$authoritys['no']['Memberlevel']['synMemberLevelOne'] = 1;
$authoritys['no']['Package']['pageLogList'] = 1;
$authoritys['no']['Package']['getMemberTr'] = 1;
$authoritys['no']['Package']['doDelLog'] = 1;
$authoritys['no']['System']['checkName'] = 1;
$authoritys['no']['System']['checkEditName'] = 1;
$authoritys['no']['Role']['checkRoleName'] = 1;
$authoritys['no']['Role']['checkEditName'] = 1;
$authoritys['no']['Admin']['getExportFileDownList'] = 1;
$authoritys['no']['BalanceInfo']['getExportFileDownList'] = 1;
$authoritys['no']['Spike']['pageList'] = 1;
$authoritys['no']['Orders']['getOrdersDialog'] = 1;
$authoritys['no']['Orders']['selectOrdersPropetry'] = 1;
$authoritys['no']['Goods']['ajaxLoadUnsaleSpec'] = 1;
$authoritys['no']['Address']['getSelectHtml'] = 1;
$authoritys['no']['Orders']['getLogisticType'] = 1;

