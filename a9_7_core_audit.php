<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$target=$root.'/app-a9-7-full';
$files=json_decode('{"private-server/bootstrap.php":"1e33ef157a57c898e17bf89fd45bd147712df405b10049fe5c3de0e2efaa8698","private-server/ledger.php":"90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185","private-server/media.php":"03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265","private-server/provider.php":"03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff","private-server/docx.php":"2138b7a91c4c952a3912a1160af0bc6763e9fedf07a90e04a86fc5ec1d252a17","private-server/schema.sql":"408004d48c12c4ffc94e11e7509d4e8c8342278f1d99320bb712ee7fac5ee7d0","private-server/composer.json":"0fd343c09bd85f349438b3720ea36e91b87d2ec4270d5cfd09572dbf7a776b45","private-server/composer.lock":"8b8a6f616c4a075c57908d461f9d633b0496796d61e74f134240080ee5927698","test-public/tools/audio-to-text/api/index.php":"3f8d989955e8a5aab9e017bcacc3b128547af21f67fae6fd33802fc2dc7d7dc0"}',true,512,JSON_THROW_ON_ERROR);
if(!is_dir($target) || is_link($target)||(fileperms($target)&0777)!==0700){echo "INSTALL_DIR_INVALID\n";exit(92);}
foreach($files as $relative=>$sha){
  $full=$target.'/'.$relative;
  if(!is_file($full)||is_link($full)||(fileperms($full)&0777)!==0600||hash_file('sha256',$full)!==$sha){
   echo json_encode(['stage'=>'A9.7','status'=>'INVALID_CORE','component'=>$relative]).PHP_EOL;exit(93);
  }
}
$ok=!file_exists($root.'/a9-7-core-import-private.pem') && 
!file_exists($target.'/private-server/vendor/autoload.php') &&
strpos((string)realpath($target),(string)realpath('/home/u342033576/domains/motricialy.com/public_html'))!==0;
echo json_encode(['stage'=>'A9.7','status'=>$ok?'CORE_VERIFIED':'CORE_INCOMPLETE',
 'verified_files'=>count($files),'candidate_path_outside_webroot'=>true,
 'file_permissions'=>'0600','private_key_deleted'=>!file_exists($root.'/a9-7-core-import-private.pem'),
 'vendor_installed'=>false,'paid_enabled'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
