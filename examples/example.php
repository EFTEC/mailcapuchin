<?php

use eftec\MailCapuchin\MailCapuchin;

include "../vendor/autoload.php";

$mail=new MailCapuchin();

$config=[
	'dbserver'=>'127.0.0.1',
	'dbuser'=>'root',
	'dbpassword'=>'abc.123',
	'dbschema'=>'mailcapuchin',
	'mailserver'=>'127.0.0.1',
	'mailuser'=>'aa@aaa.com',
	'mailpassword'=>'abc.123',
	'mailsecurity'=>''
];

//$mail->serialize_php_array($config);
$mail->showUI();