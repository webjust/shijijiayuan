<?php
/**
 * Description of UpdateAction
 *
 * @author zuojianghua
 */
class UpdateAction extends Action{
    /**
     * 测试站点脚本，用于更新192.168.0.264上的代码
     */
    public function svnup(){
        $cmd = 'svn update /data/www/fx7';
        $res = array();
        //exec($cmd,$res);
       // dump($res);exit;
        $this->success($res,'http://192.168.0.164:8070/');
    }
    
}