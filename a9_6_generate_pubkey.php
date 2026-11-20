<?php
declare(strict_types=1);
/* Stage A9.6: generate one 3072-bit RSA transport keypair on hosting CLI,
   store private key outside document root, print PUBLIC KEY ONLY.
   No OpenAI keys and no database operations. */
if (PHP_SAPI !== 'cli' || basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120') exit(9);
$gate=(string)@file_get_contents(__DIR__.'/.htaccess');
if (strpos($gate,'Require all denied')===false) {echo "GATE_ERROR\n";exit(8);}
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
if (str_starts_with($dir,realpath('/home/u342033576/domains/motricialy.com/public_html')?:'/WRONG'))exit(10);
if(!is_dir($dir) && !@mkdir($dir,0700,true)){echo "PRIVATE_DIR_FAILURE\n";exit(11);}
@chmod($dir,0700);
$keyfile=$dir.'/transport-private.pem';
if(is_file($keyfile)){
 $existing=@file_get_contents($keyfile);
 $key=is_string($existing)?openssl_pkey_get_private($existing):false;
 if(!$key){echo "EXISTING_KEY_FAILURE\n";exit(12);}
} else {
 $key=openssl_pkey_new(['private_key_bits'=>3072,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
 if($key===false || !openssl_pkey_export($key,$pem)){echo "KEYGEN_FAILURE\n";exit(13);}
 $fd=@fopen($keyfile,'x');
 if($fd===false){echo "KEY_SAVE_FAILURE\n";exit(14);}
 @chmod($keyfile,0600);
 fwrite($fd,$pem);fflush($fd);fclose($fd);
 @chmod($keyfile,0600);
 unset($pem);
}
$d=openssl_pkey_get_details($key);
if(!is_array($d)||($d['bits']??0)<3072||!isset($d['key'])){echo "PUBLIC_KEY_ERROR\n";exit(15);}
$pub=$d['key'];
if((fileperms($dir)&0777)!==0700||(fileperms($keyfile)&0777)!==0600){echo "PERMISSION_ERROR\n";exit(16);}
echo json_encode(['stage'=>'A9.6','status'=>'PUBLIC_KEY_READY','bits'=>$d['bits'],
 'private_key_outside_webroot'=>true,'private_key_permissions'=>'0600','directory_permissions'=>'0700',
 'public_key_b64'=>base64_encode($pub),'public_key_sha256'=>hash('sha256',$pub)]).PHP_EOL;
