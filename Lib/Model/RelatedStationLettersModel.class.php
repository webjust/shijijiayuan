<?php

/**
 * 站内信关系模型
 *
 * @package Model
 * @version 7.1
 * @author Zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2013-04-1
 * @copyright Copyright (C) 2012, Shanghai GuanYiSoft Co., Ltd.
 */
class RelatedStationLettersModel extends GyfxModel {
    /**
     * 插入会员关系权限表
     * @author wangguibin <wangguibin@guanyisoft.com>
     * @date 2013-05-15
     */
    public function addAll($insert_pn_mlid) {
		foreach($insert_pn_mlid as $insertData){
			M('related_station_letters',C('DB_PREFIX'),'DB_CUSTOM')->data($insertData)->add();
		}
    }
}