<?php
declare(strict_types=1);
/* A9.8 CLI-only private HTTP test key, no user data, no paid API. */
if (PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120') exit(90);
if (strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false) exit(91);
if (getenv('A2T_ENABLE_LIVE')==='1') exit(92);
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
$key=$dir.'/a9-8-http-key.hex';
if (!is_dir($dir)||is_link($dir)||(fileperms($dir)&0777)!==0700) exit(93);
if (is_file($key)) {echo json_encode(['stage'=>'A9.8','status'=>'KEY_ALREADY_EXISTS','mode_ok'=>(fileperms($key)&0777)===0600]).PHP_EOL; exit;}
$fp=@fopen($key,'x');
if ($fp===false) exit(94);
@chmod($key,0600);
$value=bin2hex(random_bytes(32));
$ok=(fwrite($fp,$value)===64);
fclose($fp);
@chmod($key,0600);
echo json_encode(['stage'=>'A9.8','status'=>$ok?'PRIVATE_KEY_READY':'KEY_WRITE_FAILED','outside_webroot'=>true,'mode_0600'=>(fileperms($key)&0777)===0600,'secret_not_printed'=>true,'paid_api_enabled'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
