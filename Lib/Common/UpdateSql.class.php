<?php
/**
 * 脚本升级
 *
 * @package Action
 * @subpackage Home
 * @stage 7.4.5
 * @author wangguibin <wangguibin@guanyisoft.com>
 * @date 2014-01-17
 * @copyright Copyright (C) 2014, Shanghai GuanYiSoft Co., Ltd.
 */
class UpdateSql{
    
    protected $request;
    
    /**
     * 初始化连接信息
     * @author Terry<wanghui@guanyisoft.com>
     * @date 2013-06-14
     */
    public function __construct() {
        $this->request = M('',C('DB_PREFIX'),'DB_CUSTOM');
    }
    
	function createFromFile($sqlPath,$delimiter = '(;\n)|((;\r\n))|(;\r)',$prefix = '',$commenter = array('#','--','/*','*'))
	    {
	        //判断文件是否存在
	        if(!file_exists($sqlPath))
	            return false;
	        $handle = fopen($sqlPath,'rb');    
	        $sqlStr = fread($handle,filesize($sqlPath));
	        //通过sql语法的语句分割符进行分割
	        $segment = explode(";",trim($sqlStr)); 
	        //echo '<pre>';print_r($segment);exit;
	        //去掉注释和多余的空行
	        
	        foreach($segment as & $statement)
	        {
	        	$newStatement = array();
	            $sentence = explode("\n",$statement);
	            foreach($sentence as $subSentence)
	            {
	                if('' != trim($subSentence))
	                {
	                    //判断是会否是注释
	                    $isComment = false;
	                    foreach($commenter as $comer)
	                    {
	                    	//echo '<pre>';print_r($subSentence);
	                        if(strstr(trim($subSentence),$comer))
	                        {
	                        	//echo $subSentence;echo '-1';echo '<br />';
	                            $isComment = true;
	                            break;
	                        }
	                    }
	                    //如果不是注释，则认为是sql语句
	                    if(!$isComment){
	                    	$newStatement[] = $subSentence;
							//print_r($newStatement);
	                    }                        
	                }
	            }
	            $statement = $newStatement;
	        }
	        //echo '<pre>';print_r($newStatement);die();
	        //对表名加前缀
	        if('' != $prefix)
	        {
	            //只有表名在第一行出现时才有效 例如 CREATE TABLE talbeName
	            $regxTable = "^[\`\'\"]{0,1}[\_a-zA-Z]+[\_a-zA-Z0-9]*[\`\'\"]{0,1}$";//处理表名的正则表达式
	            $regxLeftWall = "^[\`\'\"]{1}";
	            $sqlFlagTree = array(
	                    "CREATE" => array(
	                            "TABLE" => array(
	                                    "$regxTable" => 0
	                                )
	                        ),
	                     "DROP" => array(
	                            "TABLE" => array(
	                                "$regxTable" => 0
	                            )
	                       ),
	                     "ALTER" => array(
	                            "TABLE" => array(
	                                "$regxTable" => 0
	                            )
	                       ),	                       
	                     "INSERT" => array(
	                            "INTO" => array(
	                                "$regxTable" => 0
	                            )
	                      ),	
	                     "REPLACE" => array(
	                            "INTO" => array(
	                                "$regxTable" => 0
	                            )
	                      ),	
	                      "UPDATE" => array(
	                            "$regxTable" => 0
	                      )                                          
	            );
	            //echo '<pre>';print_r($segment);exit;            
	            foreach($segment as &$statement)
	            {
	                $tokens = split(" ",$statement[0]);
	                
	                $tableName = array();
	                $this->findTableName($sqlFlagTree,$tokens,0,$tableName);
	                
	                if(empty($tableName['leftWall']))
	                {
	                    $newTableName = $prefix.$tableName['name'];
	                }
	                else{
	                    $newTableName = $tableName['leftWall'].$prefix.substr($tableName['name'],1);
	                }
	                
	                $statement[0] = str_replace($tableName['name'],$newTableName,$statement[0]);
	            }
	            
	        }        
	        //组合sql语句
	        foreach($segment as & $statement)
	        {
	            $newStmt = '';
	            foreach($statement as $sentence)
	            {
	                $newStmt = $newStmt.trim($sentence)."\n";
	            } 
	            $statement = $newStmt;
	        }
	        //echo '<pre>';print_r($segment);exit;
	        if(!empty($segment)){
	        	$this->saveByQuery($segment);
	        }
	        return true;
	    }
	    
		/**
		 * 写日志
		 * @author Wangguibin
		 * @date 2013-08-015
		 * @param string 日志内容
		 * @param string 日志文件名
		 */
		function writeLog($str_content) {
			$error_path = './Lib/Action/System/';
			if(!is_dir($error_path)){
			    @mkdir($error_path);
			    @chmod($error_path, 0755);
			}
			error_log(date("c")."\t".$str_content."\n", 3, $error_path.date('Ymd').'error.txt');
		}
	
	    private function saveByQuery($sqlArray)
	    {
	        foreach($sqlArray as $sql)
	        {
	        	if(strstr($sql,'payment_cfg')){
	        		$this->writeLog('支付配置表不允许更改,执行的sql为：'.$sql);
	        	}else{
	        		if(!empty($sql)){
		        		$res = $this->request->execute($sql);
		        		if($res === false){
		        			$this->writeLog('表更新失败,执行的sql为：'.$sql);
		        		}	        			
	        		}
	        	}
	        }  
	        return true;      
	    }
	    
//	    public function findTableName($sqlFlagTree,$tokens,$tokensKey=0,& $tableName = array())
//	    {
//	        $regxLeftWall = "^[\`\'\"]{1}";
//	        
//	        if(count($tokens)<=$tokensKey)
//	            return false;        
//	        
//	        if('' == trim($tokens[$tokensKey]))
//	        {
//	            return $this->findTableName($sqlFlagTree,$tokens,$tokensKey+1,$tableName);
//	        }
//	        else
//	        {
//	            foreach($sqlFlagTree as $flag => $v)
//	            {    
//	                if(eregi($flag,$tokens[$tokensKey]))
//	                {
//	                    if(0==$v)
//	                    {
//	                        $tableName['name'] = $tokens[$tokensKey];
//	            
//	                        if(eregi($regxLeftWall,$tableName['name']))
//	                        {
//	                            $tableName['leftWall'] = $tableName['name']{0};
//	                        }
//	                        
//	                        return true;
//	                    }
//	                    else{
//	                        return $this->findTableName($v,$tokens,$tokensKey+1,& $tableName);
//	                    }
//	                }
//	            }
//	        }
//	        
//	        return false;
//	    }

	function writeArrayToFile($fileName,$dataArray,$delimiter="\r\n")
	{
	    $handle=fopen($fileName, "wb");
	    
	    $text = '';
	    
	    foreach($dataArray as $data)
	    {
	        $text = $text.$data.$delimiter;
	    }
	    fwrite($handle,$text);
	}
}