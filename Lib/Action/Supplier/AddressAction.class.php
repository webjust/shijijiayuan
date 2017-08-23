<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 后台公共城市区的页面
 *
 * @author listen
 */
class AddressAction extends AdminAction {

    /**
     * 城市去显示select页面
     * @author listen
     * @date 2013-01-15
     *
     */
    public function addressPage() {

        $this->display();
    }
    /**
     * 获取select里面的option
     * @author zuo <zuojianghua@guanyisoft.com>
     * @date 2013-01-18
     */
    public function getSelectHtml() {
        $parent = $this->_get('cr_id', 'htmlspecialchars', -1);
        $data['cityRegion'] = D('CityRegion')->getParentsAddr($parent);
        $this->assign($data);
        $this->display();
    }

}