<?php
declare(strict_types=1);
/* CLI-only encrypted source installer. Do not invoke via HTTP.
   RSA OAEP public ciphertext contains no credentials, AES-256-GCM authenticates source.
   Only installs checked A9.5 ledger.php outside the website document root. */
if (PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120') exit(90);
if (getenv('A2T_ENABLE_LIVE')==='1') exit(91);
if (strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false) exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$privateKey=$root.'/a9-7-import-private.pem';
$cipherPath=__DIR__.'/a9_7_ledger_cipher.b64';
$dir=$root.'/app-a9-7';
$serverDir=$dir.'/private-server';
$dest=$serverDir.'/ledger.php';
$marker=$root.'/a9-7-ledger-installed.done';
if (!is_dir($root)||(fileperms($root)&0777)!==0700||is_link($root)) exit(93);
if (file_exists($marker)||is_file($dest)){echo "ALREADY_INSTALLED\n";exit;}
if (!is_file($privateKey)||is_link($privateKey)||(fileperms($privateKey)&0777)!==0600)exit(94);
if (!is_file($cipherPath)||is_link($cipherPath))exit(95);
try {
 $pem=(string)file_get_contents($privateKey);
 $key=openssl_pkey_get_private($pem);unset($pem);
 if ($key===false)throw new RuntimeException('key');
 $details=openssl_pkey_get_details($key);
 if(!is_array($details)||($details['bits']??0)!==3072||
 hash('sha256',(string)($details['key']??''))!=='0d6a68c9bb497d265512b0b30a5d795f9cb63ea8e55de3b06f20bfa41956f74e')
   throw new RuntimeException('fingerprint');
 $wrapped=base64_decode('ZPrxYroCDCEYmQWhCiwhZbgNcIVfwEpIQWkuemwCmnnsXVd8cPa+50zUP+bCQKQNYmbWh6ibhZvwF6WWLJis5OnB1J+YXRpOg/KnB3gQMCiUMgzRTxenP0yOM2gpwYxr0d34XszDQloxhDZE58jvFWnL3OHp6+qEhy1fjvTnNGFu9o+ajr4zh0AGhpnnWrSiSw6TPuoQAsOsTxrS8AGE2TqNHvoPyXswM2HvzdF3+ffDuRNKBpbpLxjUa1XElsyrcmjtXv+UjIx0Akuexh9PH8mT+7BPULUu4U/AUaettxhUUfOfIXDZuYv1Kf+ASpJ5mm0KawmPuvEnO90FLZmqtRWKc4sQpxak7se0/b6Norw/Id9ipr21iLfm1Fa2R/a5VKd1jJdVjzNzX1lqPzTnZQ8828YtXBX/qXgh3gILf8LJHMnyNorxe21gaPM3d9RlysEFzHK+ebRwIP995C3PfVRS+wgfP+O6EQY5/U96Y+q0N+vVy8xHSfk0/ri7Ae/M',true);
 if(!is_string($wrapped)||strlen($wrapped)!==384)throw new RuntimeException('wrapped');
 $ok=openssl_private_decrypt($wrapped,$aesKey,$key,OPENSSL_PKCS1_OAEP_PADDING);
 if(!$ok||!is_string($aesKey)||strlen($aesKey)!==32)throw new RuntimeException('unwrap');
 $cipher=base64_decode(trim((string)file_get_contents($cipherPath)),true);
 if(!is_string($cipher)||strlen($cipher)!==2432||
 hash('sha256',$cipher)!=='53dd73824f523eb094bd339abff02cd579a20765b72ae2526ed86122e0ed1ef7')
  throw new RuntimeException('cipher');
 $nonce=base64_decode('hAL1125qJM/2lOd7',true);
 $tag=substr($cipher,-16);$payload=substr($cipher,0,-16);
 $gz=openssl_decrypt($payload,'aes-256-gcm',$aesKey,OPENSSL_RAW_DATA,$nonce,$tag,'MOTRICIALY:A9.7:LEDGER:1');
 unset($aesKey,$wrapped,$payload,$cipher);
 if(!is_string($gz)||hash('sha256',$gz)!=='88c61c16f3ed78fea249787fcae0b20d1b92ade5ff9e4178fab0bc24013689a0')
  throw new RuntimeException('auth');
 $source=gzdecode($gz);unset($gz);
 if(!is_string($source)||hash('sha256',$source)!=='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185')throw new RuntimeException('sha');
 if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('private mkdir');
 if(!is_dir($serverDir)&&!mkdir($serverDir,0700,true))throw new RuntimeException('server mkdir');
 @chmod($dir,0700);@chmod($serverDir,0700);
 if((fileperms($serverDir)&0777)!==0700)throw new RuntimeException('dir mode');
 $fp=@fopen($dest,'x');
 if(!$fp)throw new RuntimeException('dest exists');
 @chmod($dest,0600);
 $n=fwrite($fp,$source);fflush($fp);fclose($fp);
 if($n!==strlen($source)||hash_file('sha256',$dest)!=='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185')
  throw new RuntimeException('write hash');
 @chmod($dest,0600);
 unset($source);
 @unlink($privateKey);
 @file_put_contents($marker,'done',LOCK_EX);
 echo json_encode(['stage'=>'A9.7','status'=>'LEDGER_INSTALLED',
   'installed_outside_public_html'=>true,'ledger_sha256'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185',
   'ledger_permission'=>'0600','transport_private_key_deleted'=>!file_exists($privateKey),
   'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
 echo json_encode(['stage'=>'A9.7','status'=>'FAIL','type'=>get_class($e),'openai_calls'=>0]).PHP_EOL;exit(97);
}
