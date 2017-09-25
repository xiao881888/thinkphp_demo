<?php
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
function SendMail($address, $title, $message) {
	import('Org.PHPMailer.phpmailer');
	$mail = new PHPMailer();
	$mail->IsSMTP();	// 设置PHPMailer使用SMTP服务器发送Email
	$mail->CharSet = 'UTF-8';
	if(is_array($address)){
		foreach ($address as $val){
			$mail->addAddress($val);
		}
	}else{
		$mail->AddAddress($address);// 添加收件人地址，可以多次使用来添加多个收件人
	}
		
	// 	$mail->Body = $message;	// 设置邮件正文
	$mail->msgHTML($message);
	$mail->From = C('MAIL_ADDRESS');	// 设置邮件头的From字段。
	$mail->FromName = '日志系统';	// 设置发件人名字
	$mail->Subject = $title;	// 设置邮件标题
	$mail->Host = C('MAIL_SMTP');	// 设置SMTP服务器。
	$mail->SMTPAuth = true;	// 设置为“需要验证”
	// 设置用户名和密码。
	$mail->Username = C('MAIL_LOGINNAME');
	$mail->Password = C('MAIL_PASSWORD');
	$result = $mail->Send();
	return $result;
}