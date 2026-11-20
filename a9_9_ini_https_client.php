<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$root='/home/u342033576/.motricialy-a2t-stage-a9';$file=$root.'/a9-9-http-key.hex';
if(!is_file($file)||is_link($file)||(fileperms($file)&0777)!==0600)exit(91);
$key=(string)file_get_contents($file);
$ch=curl_init('https://motricialy.com/staging/audio-to-text-a9-private-gate-20261120/a9_9_ini_probe.php');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,
 CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,
 CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['X-A2T-Stage-Key: '.$key]]);
$body=curl_exec($ch);$errno=curl_errno($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);unset($key);
$cfg=is_string($body)?json_decode($body,true):null;
$applied=$http===200&&is_array($cfg)&&($cfg['upload_max_filesize']??'')==='24M'&&($cfg['post_max_size']??'')==='25M'
&&($cfg['max_file_uploads']??'')==='1';
echo json_encode(['stage'=>'A9.9','scope'=>'LITESPEED_HTACCESS_PHP_VALUES','status'=>$applied?'SCOPED_PHP_LIMITS_PASS':'SCOPED_PHP_LIMITS_NOT_APPLIED',
 'https_status'=>$http,'curl_errno'=>$errno,'observed'=>$cfg,'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
