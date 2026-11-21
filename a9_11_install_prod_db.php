<?php
declare(strict_types=1);
/* A9.11 production DB password transport. Hostinger PHP 8.1 CLI only.
 * Decrypts AES-256-GCM envelope with one-time RSA private key, verifies
 * exact DB identity, writes mode 0600 outside public_html, destroys RSA key.
 * No password or other secret printed. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$keyPath=$root.'/a9-11-transport-private.pem';
$cipherFile=__DIR__.'/a9_11_prod_db_cipher.json';
$dest=$root.'/a9-11-mysql-production.json';
if(is_file($dest)){echo json_encode(['stage'=>'A9.11','status'=>'PROD_DB_CONFIG_ALREADY_INSTALLED','secure'=>(fileperms($dest)&0777)===0600]).PHP_EOL;exit;}
if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700||
 !is_file($keyPath)||is_link($keyPath)||(fileperms($keyPath)&0777)!==0600||
 !is_file($cipherFile)||is_link($cipherFile))exit(93);
try{
 $obj=json_decode((string)file_get_contents($cipherFile),true,512,JSON_THROW_ON_ERROR);
 if(($obj['v']??null)!==1||($obj['rsa_oaep']??'')!=='sha1'||
 ($obj['aad']??'')!=='motricialy-a2t-prod-db-a11-v1')throw new RuntimeException('manifest');
 $priv=openssl_pkey_get_private((string)file_get_contents($keyPath));
 if(!$priv)throw new RuntimeException('private');
 $details=openssl_pkey_get_details($priv);
 if(!is_array($details)||($obj['public_fingerprint_sha256']??'')!==hash('sha256',$details['key']))throw new RuntimeException('recipient');
 $wrapped=base64_decode((string)$obj['wrapped_key_b64'],true);
 $nonce=base64_decode((string)$obj['nonce_b64'],true);
 $cipher=base64_decode((string)$obj['cipher_b64'],true);
 if(!is_string($wrapped)||strlen($wrapped)!==384||!is_string($nonce)||strlen($nonce)!==12||
 !is_string($cipher)||strlen($cipher)<80||strlen($cipher)>2000||
 !hash_equals((string)($obj['cipher_sha256']??''),hash('sha256',$cipher)))throw new RuntimeException('cipher');
 if(!openssl_private_decrypt($wrapped,$aes,$priv,OPENSSL_PKCS1_OAEP_PADDING)||strlen($aes)!==32)throw new RuntimeException('unwrap');
 $plain=openssl_decrypt(substr($cipher,0,-16),'aes-256-gcm',$aes,OPENSSL_RAW_DATA,$nonce,substr($cipher,-16),'motricialy-a2t-prod-db-a11-v1');
 unset($priv,$aes,$wrapped,$cipher,$obj);
 if(!is_string($plain))throw new RuntimeException('authentication_failed');
 $cfg=json_decode($plain,true,512,JSON_THROW_ON_ERROR);unset($plain);
 if(($cfg['environment']??'')!=='production-a2t'||
 ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4'||
 ($cfg['user']??'')!=='u342033576_a2t_runtime'||
 !is_string($cfg['password']??null)||strlen($cfg['password'])<32||strlen($cfg['password'])>128)throw new RuntimeException('db_scope');
 $bytes=json_encode($cfg,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);unset($cfg);
 $fp=@fopen($dest,'x');if(!$fp)throw new RuntimeException('create_failed');
 @chmod($dest,0600);
 $written=fwrite($fp,$bytes);fflush($fp);fclose($fp);
 @chmod($dest,0600);unset($bytes);
 if($written<125||!is_file($dest)||(fileperms($dest)&0777)!==0600){
  @unlink($dest);throw new RuntimeException('file_invalid');
 }
 @unlink($keyPath);
 echo json_encode(['stage'=>'A9.11','status'=>'PROD_DB_CONFIG_SECURELY_INSTALLED',
 'outside_webroot'=>true,'mode_0600'=>true,
 'one_time_rsa_private_destroyed'=>!file_exists($keyPath),
 'paid_api_enabled'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
 echo json_encode(['stage'=>'A9.11','status'=>'PROD_DB_IMPORT_FAILED','error_type'=>get_class($e),
 'secrets_not_disclosed'=>true]).PHP_EOL;exit(97);
}
