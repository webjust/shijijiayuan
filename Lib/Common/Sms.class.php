<?php
/**
*  封装SMS send
* 
* @copyright Copyright (c) 2011, guanyisoft.com inc
* @package guanyisoft
* @author HCaijin 
* @date 2013年 08月 16日 星期五 
*/
class Sms_Send{

    private $sn;
    private $pwd;

    public function __construct() {
        $this->sn = C("SMS_SN");
        $this->pwd = C("SMS_PWD");
    }

    /**
     * 发送短信息方法
     * @param array $rows 发送参数
     * @return boolean $flag 是否发送成功
     *
     * $rows参数说明:
     * 'mobile'=>发送到的手机号码
     * 'content'=>短信息正文,字符串类型,这里只发送验证码数字
     */
	function send($rows){
		$flag = 0; 
		$argv = array( 
         	'sn'=>$this->sn , 
		 	'pwd'=>strtoupper(md5($this->sn.$this->pwd)), 
		 	'title'=>urlencode('短信验证码'),
		 	'mobile'=>$rows['mobile'], 
		 	'ext'=>'',
	     	'rrid'=>'',
	     	'content'=>iconv("UTF-8","GB2312//IGNORE",$rows['content']) ,
		 	'stime'=>''); 
		foreach ($argv as $key=>$value) { 
            if ($flag!=0) { 
                     $params .= "&"; 
                     $flag = 1; 
            } 
            $params.= $key."="; $params.= urlencode($value); 
            $flag = 1; 
         } 
         $length = strlen($params); 
         $fp = fsockopen("sdk1.entinfo.cn",8060,$errno,$errstr,10) or exit($errstr."--->".$errno); 
         $header = "POST /webservice.asmx/mt HTTP/1.1\r\n"; 
         $header .= "Host:sdk1.entinfo.cn\r\n"; 
         $header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
         $header .= "Content-Length: ".$length."\r\n"; 
         $header .= "Connection: Close\r\n\r\n"; 
         $header .= $params."\r\n"; 
         fputs($fp,$header); 
         $inheader = 1; 
		 
         while (!feof($fp)) { 
            $line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据 
            if ($inheader && ($line == "\n" || $line == "\r\n")) { 
               $inheader = 0; 
            } 
            if ($inheader == 0) { 
                // echo $line; 
            } 
         }

        $flg=strrpos($line,"</string>");

        fclose($fp); 
        return $flg;
	}
}
