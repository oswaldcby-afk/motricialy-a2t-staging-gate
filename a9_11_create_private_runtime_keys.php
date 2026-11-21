<?php
declare(strict_types=1);
/* A9.11 generate production visitor-HMAC and private QA header keys.
 * CLI-only. Secret never echoed or committed. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)exit(93);
$out=['stage'=>'A9.11','status'=>'PRIVATE_SECRETS_READY','exposed_secrets'=>false,'paid_calls'=>0];
foreach(['a9-11-production-hmac.private','a9-11-release-qa-gate.private'] as $name) {
 $path=$root.'/'.$name;
 if(!file_exists($path)){
  $fp=@fopen($path,'x');if(!$fp)exit(94);
  @chmod($path,0600);
  $token=bin2hex(random_bytes(32));$ok=fwrite($fp,$token)===64;fclose($fp);@chmod($path,0600);
  unset($token);
  if(!$ok)exit(95);
 }
 $out['files'][$name]=is_file($path)&&!is_link($path)&&(fileperms($path)&0777)===0600&&filesize($path)===64;
 if(!$out['files'][$name])$out['status']='INVALID_PERMISSIONS';
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PRIVATE_SECRETS_READY')exit(97);
