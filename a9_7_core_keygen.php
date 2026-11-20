<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
if(!is_dir($root)||(fileperms($root)&0777)!==0700)exit(92);
$keyfile=$root.'/a9-7-core-import-private.pem';
if(file_exists($keyfile))exit(93);
$key=openssl_pkey_new(['private_key_bits'=>3072,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if($key===false||!openssl_pkey_export($key,$pem))exit(94);
$fp=@fopen($keyfile,'x');if(!$fp)exit(95);
@chmod($keyfile,0600);fwrite($fp,$pem);fclose($fp);@chmod($keyfile,0600);
if((fileperms($keyfile)&0777)!==0600)exit(96);
$d=openssl_pkey_get_details($key);
echo json_encode(['stage'=>'A9.7','status'=>'CORE_TRANSPORT_KEY_READY',
 'public_b64'=>base64_encode($d['key']),'fingerprint'=>hash('sha256',$d['key']),
 'outside_webroot'=>true]).PHP_EOL;
