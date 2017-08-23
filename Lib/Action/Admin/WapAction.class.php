<?php
/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 2015/1/5
 * Time: 18:56
 */

class WapAction extends AdminAction{

	protected  $dir = '';
	protected  $wap_theme_path = '';

	public function _initialize() {
		$this->dir = C('WAP_TPL_DIR');
		if(!$this->dir) {
			$this->error('WAP主题目录没有设置！');
		}
		//$this->_name = $this->getActionName();
		if(!defined("WAP_TPL")) {
			$array_config = D("SysConfig")->where(array("sc_key" => 'GY_TEMPLATE_WAP_DEFAULT'))->find();
			if (is_array($array_config) && !empty($array_config)) {
				define('WAP_TPL', $array_config['sc_value']);
				$_SESSION['NOW_WAP_TPL'] = $array_config['sc_value'];
			} else {
				define('WAP_TPL', 'default');
				$_SESSION['NOW_WAP_TPL'] = 'default';
			}
		}
		$wap_theme_path = '/Public/Tpl/' . CI_SN . '/' . $this->dir . '/' ;
		$this->wap_theme_path = '.' . $wap_theme_path;
	}
	/**
	 * 模板使用
	 * @author Terry<wanghui@guanyisoft.com>
	 * @date 2013-04-08
	 */
	public function doTplStatus() {
		$ary_post = $this->_post();
		if (!empty($ary_post['tp']) && isset($ary_post['tp'])) {
			$SysSeting = D('SysConfig');
			if ($SysSeting->setConfig('GY_TEMPLATE_DEFAULT', 'GY_TEMPLATE_WAP_DEFAULT', $ary_post['tp'], '设置默认WAP模板')) {
                $SysSeting->deleteCfgByModule('GY_TEMPLATE_DEFAULT',1);
                $this->log = new ILog('db');       //提供了两个类型：file,db file为文件存储日志 db数据库存储 默认为文件
                $this->log->write('operation',array("管理员:".$_SESSION['admin_name'],"设置默认模板",'设置默认模板为:'.$ary_post['tp']));
				//清空runtime
				$runtime_url = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/~runtime.php';
				if(file_exists($runtime_url)){
					unlink($runtime_url);
				}
				//删除当前模板首页缓存
				$path_url1 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/Wap')).'.html';
				$path_url2 = $_SERVER['DOCUMENT_ROOT'].'/Runtime/' . CI_SN.'/TmpHtml/'.CI_SN.md5(md5($_SERVER['HTTP_HOST'].$_SERVER['SERVER_ADDR'].'/Wap/Index/index')).'.html';				
				if(file_exists($path_url1)){
					unlink($path_url1);
				}
				if(file_exists($path_url2)){
					unlink($path_url2);
				}		
				make_fsockopen('/Script/Batch/delFile');				
                $this->success('操作成功');
			} else {
				$this->error('操作失败');
			}
		} else {
			$this->error("模板不存在，请检查");
		}
	}

	/**
	 * 删除模板
	 * @author Pooh<zhaozhicheng@guanyisoft.com>
	 * @date 2015-10-20
     */
	public function TemplateDelete(){
		$ary_request = $this->_request();
		$FileUtil = new FileUtil();
		$rootPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/wap/";
		if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
			$path = $rootPath.$ary_request['dir'];
				if (file_exists($path)) {
					$status = $FileUtil->unlinkDir($path);
					if (FALSE !== $status) {
						if(!empty($ary_request['tid']) && isset($ary_request['tid'])){
							M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_id'=>$ary_request['tid']))->delete();
						}
						$this->success("删除成功！");
					} else {
						$this->error("删除失败，请检查模板文件权限是否设置为可写！");
					}
				} else {
					$this->error("需要删除的模板文件不存在！");
				}
		}else{
			$this->error("请选择需要删除的模板文件");
		}
	}

    /**
     * 删除模板
     * @author Pooh<zhaozhicheng@guanyisoft.com>
     * @date 2015-10-20
     */
    public function TemplateappDelete(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $rootPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/app/";
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
            if (file_exists($path)) {
                $status = $FileUtil->unlinkDir($path);
                if (FALSE !== $status) {
                    if(!empty($ary_request['tid']) && isset($ary_request['tid'])){
                        M("Template", C("DB_PREFIX"), 'DB_CUSTOM')->where(array('ti_id'=>$ary_request['tid']))->delete();
                    }
                    $this->success("删除成功！");
                } else {
                    $this->error("删除失败，请检查模板文件权限是否设置为可写！");
                }
            } else {
                $this->error("需要删除的模板文件不存在！");
            }
        }else{
            $this->error("请选择需要删除的模板文件");
        }
    }

    /**
     *  模板回收站
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-20
     **/
    public function TemplateBin(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT');
        $rootPath = $this->wap_theme_path;
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/wap/";
        $result= $FileUtil->createDir($binPath);
        if($result){
        if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
            $path = $rootPath.$ary_request['dir'];
            //进入回收站下的模板文件路径
            //判断模板目录有删除时间的了就不再加时间了
            $ary_result = preg_match('/^[A-Za-z]+[\_]{1}[0-9]+$/',$ary_request['dir']);
            if($ary_result){
                $binmodulepath = $binPath.$ary_request['dir'];
            }else{
                $binmodulepath = $binPath.$ary_request['dir']."_".date('YmdHis');
            }
            if($config['GY_TEMPLATE_WAP_DEFAULT'] != $ary_request['dir']){
                if (file_exists($path)) {
                    $status = $FileUtil->moveDir($path,$binmodulepath);
                    if (FALSE !== $status) {
                        $this->success("移进回收站成功！");
                    } else {
                        $this->error("移进回收站失败，请检查模板文件权限是否设置为可写！");
                    }
                } else {
                    $this->error("需要移进回收站模板文件不存在！");
                }
            }else{
                $this->error("该模板已经被使用不可移进回收站！");
            }

        }else{
            $this->error("请选择需要移进回收站的模板文件");
        }
        }else{
            $this->error("回收站文件创建失败！");
        }
    }

    /**
     *  app模板回收站
     *  @author Pooh<zhaozhicheng@guanyisoft.com>
     *  @date 2015-10-20
     **/
    public function TemplateappBin(){
        $ary_request = $this->_request();
        $FileUtil = new FileUtil();
        $config = D('SysConfig')->getCfgByModule('GY_TEMPLATE_DEFAULT');
        $rootPath = FXINC. '/Public/Tpl/' . CI_SN . '/app/' ;
        $binPath = FXINC . '/Public/Tpl/Temp/' . CI_SN . "/app/";
        $result= $FileUtil->createDir($binPath);
        if($result){
            if(!empty($ary_request['dir']) && isset($ary_request['dir'])){
                $path = $rootPath.$ary_request['dir'];
                //进入回收站下的模板文件路径
                //判断模板目录有删除时间的了就不再加时间了
                $ary_result = preg_match('/^[A-Za-z]+[\_]{1}[0-9]+$/',$ary_request['dir']);
                if($ary_result){
                    $binmodulepath = $binPath.$ary_request['dir'];
                }else{
                    $binmodulepath = $binPath.$ary_request['dir']."_".date('YmdHis');
                }
                if($config['GY_TEMPLATE_WAP_DEFAULT'] != $ary_request['dir']){
                    if (file_exists($path)) {
                        $status = $FileUtil->moveDir($path,$binmodulepath);
                        if (FALSE !== $status) {
                            $this->success("移进回收站成功！");
                        } else {
                            $this->error("移进回收站失败，请检查模板文件权限是否设置为可写！");
                        }
                    } else {
                        $this->error("需要移进回收站模板文件不存在！");
                    }
                }else{
                    $this->error("该模板已经被使用不可移进回收站！");
                }

            }else{
                $this->error("请选择需要移进回收站的模板文件");
            }
        }else{
            $this->error("回收站文件创建失败！");
        }
    }
} 
