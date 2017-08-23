<?php

include_once ('Phpmailer.class.php');
include_once ('Smtp.class.php');

/**
 * 发送邮件类
 * 调用phpmailer生成
 * @package Common
 * @author zuojianghua <zuojianghua@guanyisoft.com>
 * @date 2012-06-28
 */
class Mail {

    //put your code here
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer();
    }

    /**
     * 发送邮件方法
     * @param array $ary_option
     * @return boolean 是否发送成功
     *
     * $ary_option参数说明:
     * 'receiveMail'=>收件人地址，示例：28842136@qq.com
     * 'message'=>邮件正文,字符串类型
     * 'from'=>发送人邮件地址，示例：abcd@163.com
     * 'fromName'=>发送人姓名(仅用于显示的)，示例：张三
     * 'subject'=>邮件标题，示例：这是一封测试邮件
     * 'host'=>邮件SMTM地址，示例：smtp.163.com
     * 'port'=>邮件SMTM端口，示例：25
     * 'smtpAuth'=>是否进行验证(通常都需要密码进行验证的，尤其是163,qq,gmail之类的免费邮箱)，示例：true
     * 'username'=>发件人帐号(通常就是发件人的邮箱)，示例：abcd@163.com
     * 'password'=>发件人密码，示例：123456
     * 'isHtml'=>是否以html格式发送，示例：true
     */
    public function sendMail($ary_option) {
        //print_r($ary_option);
        $this->mailer->IsSMTP();
        $this->mailer->CharSet = 'UTF-8';
        //收件人地址
        $this->mailer->AddAddress($ary_option['receiveMail']);

        //设置邮件
        ////正文
        $this->mailer->Body = $ary_option['message'];
        ////发送人邮件地址
        $this->mailer->From = $ary_option['from'];
        ////发送人姓名
        $this->mailer->FromName = $ary_option['fromName'];
        ////邮件标题
        $this->mailer->Subject = $ary_option['subject'];
        ////发送SMTP服务器地址
        $this->mailer->Host = $ary_option['host'];
        ////SMTP端口号
        $this->mailer->Port = $ary_option['port'];
        ////是否验证
        $this->mailer->SMTPAuth = $ary_option['smtpAuth'];
        ////发件人用户名：通常就是发送人的邮件地址
        $this->mailer->Username = $ary_option['username'];
        ////正文是否以html格式发送，true可以加超链接和图片之类的
        $this->mailer->IsHTML($ary_option['isHtml']);
        ////发件人邮箱用户密码
        $this->mailer->Password = $ary_option['password'];
        //发送邮件
        if ($this->mailer->Send()) {
            //	echo "1";
            return true;
        } else {
            return false;
        }
    }

    /**
     * 使用一个内置的SMTP地址测试是否可以发送邮件
     * @param string $email 收件人
     * @return boolean
     */
    public function testSendMail($email) {
        return $this->sendMail(
                        array(
                            'host' => 'smtp.163.com',
                            'from' => 'guanyitest@163.com',
                            'fromName' => '管易分销测试',
                            'smtpAuth' => true,
                            'username' => 'guanyitest@163.com',
                            'password' => 'abcde12345',
                            'port' => 25,
                            'isHtml' => true,
                            'subject' => '测试测试邮件是否可以发送',
                            'message' => '这是一封测试邮件',
                            'receiveMail' => $email
                        )


                        /*  array(
                          'receiveMail' => '28842136@qq.com',
                          'message' => "<a href='http://www.baidu.com/'>这是一封测试邮件</a>",
                          'from' => 'guanyitest@163.com',
                          'fromName' => '管易软件',
                          'subject' => '这是一封测试邮件',
                          'host' => 'smtp.163.com',
                          'port' => 25,
                          'smtpAuth' => true,
                          'username' => 'guanyitest@163.com',
                          'password' => 'abcde12345',
                          'isHtml' => true
                          ) */
        );
    }

}