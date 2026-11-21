<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server';
$files=['bootstrap.php'=>'1e33ef157a57c898e17bf89fd45bd147712df405b10049fe5c3de0e2efaa8698',
'ledger.php'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185',
'provider.php'=>'03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff'];
$o=['stage'=>'A9.11','kind'=>'safe_env_contract','paid_calls'=>0];
foreach($files as $file=>$hash){
 $p=$root.'/'.$file;
 if(!is_file($p)||hash_file('sha256',$p)!==$hash)exit(92);
 $src=(string)file_get_contents($p);
 preg_match_all("/a2t_env\\s*\\(\\s*['\"]([A-Z0-9_]+)['\"]\\s*\\)/",$src,$m);
 $o['env_keys'][$file]=array_values(array_unique($m[1]));
 if($file==='bootstrap.php'){
  $lines=preg_split('/\\r\\n|\\r|\\n/',$src);
  foreach(['a2t_env','a2t_live','a2t_visitor','a2t_subjects','a2t_pdo'] as $fn){
   foreach($lines as $i=>$line)if(str_contains($line,'function '.$fn.'(')){
    $o['functions'][$fn]=implode("\\n",array_slice($lines,$i,min(25,count($lines)-$i)));break;
   }
  }
 }
}
echo json_encode($o,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
