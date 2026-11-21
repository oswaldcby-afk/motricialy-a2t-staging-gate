<?php
declare(strict_types=1);
/* One-off CLI-only code inspection. No credentials or user data. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full';
$out=['stage'=>'A9.15','kind'=>'private_php_contract_only','paid_calls'=>0];
$files=['private-server/provider.php','private-server/ledger.php','private-server/bootstrap.php','test-public/tools/audio-to-text/api/index.php'];
foreach($files as $file){
 $p=$base.'/'.$file;if(!is_file($p)||is_link($p))continue;
 $src=(string)file_get_contents($p);
 $lines=preg_split('/\\r\\n|\\r|\\n/',$src);
 $out['files'][$file]=['sha256'=>hash_file('sha256',$p),'lines'=>count($lines)];
 preg_match_all('/function\\s+([a-zA-Z_][a-zA-Z_0-9]*)\\s*\\(/',$src,$m);
 $out['files'][$file]['functions']=$m[1];
 if(str_contains($file,'provider.php')) {
  $out['files'][$file]['code']=substr($src,0,12000);
 }
 if(str_contains($file,'ledger.php')){
  $safe=[];
  foreach($lines as $i=>$line)if(preg_match('/function |A2T_.*MICRO|const .*MICRO|status|INSERT INTO/', $line)&&count($safe)<90)$safe[]=(string)($i+1).': '.trim($line);
  $out['files'][$file]['contracts']=$safe;
 }
 if(str_contains($file,'api/index.php')){
  $out['files'][$file]['code']=substr($src,0,19000);
 }
 if(str_contains($file,'bootstrap.php')){
  $safe=[];
  foreach($lines as $i=>$line)if(preg_match('/function |A2T_.*|session|Cookie|Origin|Referer|REMOTE_ADDR/', $line)&&count($safe)<100)$safe[]=(string)($i+1).': '.trim($line);
  $out['files'][$file]['contracts']=$safe;
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
