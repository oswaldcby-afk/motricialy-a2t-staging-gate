<?php
declare(strict_types=1);
/* A9.11 strictly CLI-only one-time AES-GCM/RSA-OAEP envelope installer.
 * Transport private key stays outside document root, destroyed after verified install.
 * Never logs or prints the decrypted API key. No paid calls. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$privatePath=$root.'/a9-11-transport-private.pem';
$envelope=__DIR__.'/a9_11_encrypted_secret.json';
$target=$root.'/a9-11-openai-key.private';
if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)exit(93);
if(is_file($target)){echo json_encode(['stage'=>'A9.11','status'=>'KEY_ALREADY_INSTALLED','mode_ok'=>(fileperms($target)&0777)===0600]).PHP_EOL;exit;}
if(!is_file($privatePath)||is_link($privatePath)||(fileperms($privatePath)&0777)!==0600||
 !is_file($envelope)||is_link($envelope))exit(94);
$key=null;$wrapped=null;$cipher=null;$raw=null;$secret=null;
try {
 $obj=json_decode((string)file_get_contents($envelope),true,512,JSON_THROW_ON_ERROR);
 $fingerprint='2826a3dd52a2b9172f18ee93a1c7afc43b30ff123554f5c953821ba3aa999b4f';
 $aad='motricialy-a2t-a11-prod-key-transport-v1';
 if(($obj['v']??null)!==1||($obj['rsa_oaep']??null)!=='sha1'
  ||($obj['public_fingerprint_sha256']??'')!==$fingerprint||($obj['aad']??'')!==$aad)
  throw new RuntimeException('manifest');
 $key=openssl_pkey_get_private((string)file_get_contents($privatePath));
 if(!$key)throw new RuntimeException('private_key');
 $d=openssl_pkey_get_details($key);
 if(!is_array($d)||hash('sha256',$d['key'])!==$fingerprint)throw new RuntimeException('pub_fingerprint');
 $wrapped=base64_decode((string)$obj['wrapped_key_b64'],true);
 $nonce=base64_decode((string)$obj['nonce_b64'],true);
 $cipher=base64_decode((string)$obj['cipher_b64'],true);
 if(!is_string($wrapped)||strlen($wrapped)!==384||!is_string($nonce)||strlen($nonce)!==12
  ||!is_string($cipher)||strlen($cipher)<25||strlen($cipher)>1000
  ||!hash_equals(hash('sha256',$cipher),(string)($obj['cipher_sha256']??'')))throw new RuntimeException('payload');
 $aes=null;
 if(!openssl_private_decrypt($wrapped,$aes,$key,OPENSSL_PKCS1_OAEP_PADDING)||strlen($aes)!==32)throw new RuntimeException('unwrap');
 $raw=openssl_decrypt(substr($cipher,0,-16),'aes-256-gcm',$aes,OPENSSL_RAW_DATA,$nonce,substr($cipher,-16),$aad);
 unset($aes,$wrapped,$cipher,$obj,$key);
 if(!is_string($raw)||strlen($raw)<30||strlen($raw)>500||!str_starts_with($raw,'sk-')
 ||preg_match('/[\\r\\n\\x00]/',$raw))throw new RuntimeException('invalid_key');
 $fp=@fopen($target,'x');
 if(!$fp)throw new RuntimeException('target_create');
 @chmod($target,0600);
 $size=strlen($raw);$write=fwrite($fp,$raw);fflush($fp);fclose($fp);unset($raw);
 @chmod($target,0600);
 if($write!==$size||(fileperms($target)&0777)!==0600||filesize($target)!==$size){
   @unlink($target);throw new RuntimeException('target_integrity');
 }
 @unlink($privatePath);
 echo json_encode(['stage'=>'A9.11','status'=>'HOSTINGER_PRIVATE_OPENAI_KEY_INSTALLED',
 'permissions'=>'0600','outside_webroot'=>true,
 'transport_private_key_destroyed'=>!file_exists($privatePath),
 'paid_api_enabled'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch(Throwable $e){
 echo json_encode(['stage'=>'A9.11','status'=>'IMPORT_FAILED','error_type'=>get_class($e),
 'no_key_disclosed'=>true,'openai_calls'=>0]).PHP_EOL;
 exit(97);
}
