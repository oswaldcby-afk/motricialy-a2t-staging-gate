<?php
declare(strict_types=1);
/* Stage A9.9 test-only credential creation. No logged secrets. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';$file=$root.'/a9-9-http-key.hex';
if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)exit(93);
if(is_file($file)){echo json_encode(['status'=>'KEY_ALREADY_EXISTS','permissions_ok'=>(fileperms($file)&0777)===0600]).PHP_EOL;exit;}
$fp=@fopen($file,'x');if($fp===false)exit(94);
@chmod($file,0600);
$key=bin2hex(random_bytes(32));
$written=fwrite($fp,$key);fclose($fp);@chmod($file,0600);unset($key);
echo json_encode(['stage'=>'A9.9','status'=>$written===64?'PRIVATE_TEST_KEY_READY':'FAILED','key_not_logged'=>true,'permissions_ok'=>(fileperms($file)&0777)===0600,'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
