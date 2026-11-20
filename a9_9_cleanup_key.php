<?php
declare(strict_types=1);
/* A9.9 cleanup only after deny-all Apache gate has been redeployed. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
if(strpos($rules,'Require all denied')===false||strpos($rules,'a9_9_upload_gateway.php')!==false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)exit(92);
$key=$root.'/a9-9-http-key.hex';
if(is_file($key)){
 if(is_link($key)||(fileperms($key)&0777)!==0600)exit(93);
 if(!unlink($key))exit(94);
}
echo json_encode(['stage'=>'A9.9','status'=>'PRIVATE_UPLOAD_KEY_DESTROYED',
 'apache_deny_all'=>true,
 'test_gateway_no_longer_allowed'=>true,
 'key_absent'=>!file_exists($key),
 'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
