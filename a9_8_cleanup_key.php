<?php
declare(strict_types=1);
/* A9.8 cleanup: destroy private transport-only key AFTER Apache gate is closed. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
if(strpos($rules,'Require all denied')===false||strpos($rules,'a9_8_gateway.php')!==false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$key=$root.'/a9-8-http-key.hex';
if(!is_dir($root)||(fileperms($root)&0777)!==0700)exit(92);
$exists=is_file($key);
if($exists && (is_link($key)||(fileperms($key)&0777)!==0600))exit(93);
if($exists && !@unlink($key))exit(94);
echo json_encode([
 'stage'=>'A9.8','status'=>'PRIVATE_HTTP_TEST_KEY_DESTROYED',
 'key_absent'=>!file_exists($key),
 'apache_gate_closed'=>true,
 'paid_api_calls'=>0
],JSON_UNESCAPED_SLASHES).PHP_EOL;
