<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
$priv=$dir.'/a9-11-transport-private.pem';
if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0777)!==0700||file_exists($priv))exit(93);
$key=openssl_pkey_new(['private_key_bits'=>3072,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if($key===false||!openssl_pkey_export($key,$pem))exit(94);
$fp=@fopen($priv,'x');if(!$fp)exit(95);
@chmod($priv,0600);$ok=fwrite($fp,$pem)===strlen($pem);fclose($fp);@chmod($priv,0600);
if(!$ok||(fileperms($priv)&0777)!==0600)exit(96);
$d=openssl_pkey_get_details($key);
echo json_encode(['stage'=>'A9.11','status'=>'TRANSPORT_PUBLIC_READY',
 'public_pem_b64'=>base64_encode($d['key']),
 'fingerprint'=>hash('sha256',$d['key']),
 'private_key_mode'=>'0600','outside_webroot'=>true,
 'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
