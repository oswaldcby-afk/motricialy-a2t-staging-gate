<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
if(!is_dir($root)||(fileperms($root)&0777)!==0700)exit(92);
$priv=$root.'/a9-7-import-private.pem';
if(is_file($priv)){echo "EXISTS_STOP\n";exit(93);}
$key=openssl_pkey_new(['private_key_bits'=>3072,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if($key===false||!openssl_pkey_export($key,$pem))exit(94);
$fd=@fopen($priv,'x');if($fd===false)exit(95);
@chmod($priv,0600);fwrite($fd,$pem);fflush($fd);fclose($fd);@chmod($priv,0600);
if((fileperms($priv)&0777)!==0600)exit(96);
$details=openssl_pkey_get_details($key);
if(!is_array($details)||($details['bits']??0)!==3072)exit(97);
echo json_encode(['stage'=>'A9.7','status'=>'PUBLIC_KEY_READY',
 'public_b64'=>base64_encode($details['key']),
 'fingerprint_sha256'=>hash('sha256',$details['key']),'private_dir_outside_webroot'=>true]).PHP_EOL;
