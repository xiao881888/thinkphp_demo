<?php
/**
 * @date 2014-12-25
 * @author tww <merry2014@vip.qq.com>
 */
return array(
		'MAIL_ADDRESS'	 => 'tw198611@163.com', // 邮箱地址
		'MAIL_LOGINNAME' => 'tw198611@163.com', // 邮箱登录帐号
		'MAIL_SMTP'		 => 'smtp.163.com', // 邮箱SMTP服务器
		'MAIL_PASSWORD'	 => 'tpb131224', // 邮箱密码
		
		'EVENT_LEVEL' => array(
				'NOTICE' 	=> 1,
				'WARNING' 	=> 2,
				'ERROR'	 	=> 3	
		),
		'NOTICE_MODEL' => array(//错误等级=》操作模型
				'1' => 'NoticeEmail',
				'2' => 'NoticeEmail',//NoticeEmail,NoticeMessage
				'3' => 'NoticeMessage'
		),
		'SEND_TYPE' => array(
				'EMAIL'   => 1,
				'MESSAGE' => 2,
		),
		
);