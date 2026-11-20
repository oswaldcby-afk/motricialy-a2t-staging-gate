<?php
declare(strict_types=1);
/* Authenticated A9.7 core installer, CLI only, never served over HTTP.
   Decrypts a small approved package and installs only reviewed files OUTSIDE public_html. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(91);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$privateKey=$root.'/a9-7-core-import-private.pem';
$encrypted=__DIR__.'/a9_7_encrypted_core.json';
$destination=$root.'/app-a9-7-full';
$expected='6ce8ba499e7d67962398b811e70c0171a3c86a4a2f8cadb0920683c008c2ad43';
if(!is_dir($root)||(fileperms($root)&0777)!==0700||is_link($root))exit(93);
if(is_dir($destination)||is_link($destination)){echo "ALREADY_INSTALLED\n";exit(94);}
if(!is_file($privateKey)||is_link($privateKey)||(fileperms($privateKey)&0777)!==0600||
 !is_file($encrypted)||is_link($encrypted))exit(95);
$tmpdir=null;$archive=null;
try{
 $obj=json_decode((string)file_get_contents($encrypted),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($obj)||($obj['v']??0)!==1||($obj['plain_sha256']??'')!==$expected||
 ($obj['pub_fingerprint']??'')!=='4cbb92e0c2ee3f23954ab390bc0fe236aa6d17794a6f2a2a6dd932d9b08fb02b')throw new RuntimeException('manifest');
 $private=openssl_pkey_get_private((string)file_get_contents($privateKey));
 if(!$private)throw new RuntimeException('private key');
 $details=openssl_pkey_get_details($private);
 if(!is_array($details)||hash('sha256',(string)$details['key'])!==$obj['pub_fingerprint'])throw new RuntimeException('fingerprint');
 $wrapped=base64_decode((string)$obj['wrapped_key_b64'],true);
 $nonce=base64_decode((string)$obj['nonce_b64'],true);
 $cipher=base64_decode((string)$obj['cipher_b64'],true);
 if(!is_string($wrapped)||strlen($wrapped)!==384||!is_string($nonce)||strlen($nonce)!==12||
 !is_string($cipher)||strlen($cipher)<100||strlen($cipher)>200000||hash('sha256',$cipher)!==$obj['cipher_sha256'])
 throw new RuntimeException('cipher');
 if(!openssl_private_decrypt($wrapped,$aes,$private,OPENSSL_PKCS1_OAEP_PADDING)||strlen($aes)!==32)throw new RuntimeException('unwrap');
 $zipBytes=openssl_decrypt(substr($cipher,0,-16),'aes-256-gcm',$aes,OPENSSL_RAW_DATA,$nonce,substr($cipher,-16),'MOTRICIALY:A9.7:PRIVATE-CORE:1');
 unset($aes,$cipher,$wrapped);
 if(!is_string($zipBytes)||hash('sha256',$zipBytes)!==$expected)throw new RuntimeException('authenticated package');
 $tmpdir=$root.'/a9-7-install-'.bin2hex(random_bytes(8));
 if(!mkdir($tmpdir,0700))throw new RuntimeException('tmp dir');
 @chmod($tmpdir,0700);
 $archive=$tmpdir.'/.core.zip';
 if(file_put_contents($archive,$zipBytes,LOCK_EX)!==strlen($zipBytes))throw new RuntimeException('tmp zip');
 @chmod($archive,0600);unset($zipBytes);
 $zip=new ZipArchive();
 if($zip->open($archive)!==true)throw new RuntimeException('zip');
 $manifestText=$zip->getFromName('MANIFEST.json');
 $manifest=is_string($manifestText)?json_decode($manifestText,true):null;
 if(!is_array($manifest)||!isset($manifest['files'])||!is_array($manifest['files'])||count($manifest['files'])!==9)throw new RuntimeException('files manifest');
 $allowed=[];
 foreach($manifest['files'] as $f){
  if(!is_array($f)||!isset($f['path'],$f['sha256']))throw new RuntimeException('manifest shape');
  $name=$f['path'];
  if(!preg_match('#^(private-server/[a-z0-9_.-]+|test-public/tools/audio-to-text/api/index\.php)$#D',$name)||
  !preg_match('/^[a-f0-9]{64}$/D',$f['sha256'])||isset($allowed[$name]))throw new RuntimeException('unexpected filename');
  $allowed[$name]=$f['sha256'];
 }
 if(!isset($allowed['private-server/ledger.php'])||
 $allowed['private-server/ledger.php']!=='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185')
 throw new RuntimeException('ledger mismatch');
 if($zip->numFiles!==10)throw new RuntimeException('zip count');
 for($i=0;$i<$zip->numFiles;$i++){
  $name=$zip->getNameIndex($i);
  if($name==='MANIFEST.json')continue;
  if(!isset($allowed[$name]))throw new RuntimeException('unlisted zip file');
  $contents=$zip->getFromIndex($i);
  if(!is_string($contents)||strlen($contents)>100000||hash('sha256',$contents)!==$allowed[$name])throw new RuntimeException('file hash');
  $path=$tmpdir.'/'.$name;
  $parent=dirname($path);
  if(!is_dir($parent)&&!mkdir($parent,0700,true))throw new RuntimeException('mkdir');
  @chmod($parent,0700);
  if(file_put_contents($path,$contents,LOCK_EX)!==strlen($contents))throw new RuntimeException('write');
  @chmod($path,0600);
 }
 $zip->close();unlink($archive);$archive=null;
 if(!rename($tmpdir,$destination))throw new RuntimeException('atomic install');
 $tmpdir=null;
 @chmod($destination,0700);
 @unlink($privateKey);
 echo json_encode(['stage'=>'A9.7','status'=>'PRIVATE_CORE_INSTALLED',
 'file_count'=>9,'manifest_verified'=>true,'archive_sha256'=>$expected,
 'outside_webroot'=>true,'vendor_installed'=>false,'paid_api_enabled'=>false,
 'private_key_deleted'=>!is_file($privateKey),'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
 if(is_string($archive)&&is_file($archive))@unlink($archive);
 // Leave no incomplete publicly reachable material; never print sensitive details.
 echo json_encode(['stage'=>'A9.7','status'=>'CORE_INSTALL_FAILED','type'=>get_class($e),'openai_calls'=>0]).PHP_EOL;exit(97);
}
