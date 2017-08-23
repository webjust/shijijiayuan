<?php

/**
 * 抽奖模型层 Model
 * @package Model
 * @version 7.6.1
 * @author wanguibin <wangguibin@guanyisoft.com>
 * @date 2014-07-14
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class LotteryModel extends GyfxModel {

	//抽奖表
    private $lottery_obj;
    //奖品表
	private $lottery_user_obj;
    public function __construct() {
		parent::__construct();
		$this->lottery_obj = M('lottery',C('DB_PREFIX'),'DB_CUSTOM');
		$this->lottery_user_obj = M('lottery_user',C('DB_PREFIX'),'DB_CUSTOM');
    }
	
}