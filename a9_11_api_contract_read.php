<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full';
$p=$base.'/test-public/tools/audio-to-text/api/index.php';
if(!is_file($p)||hash_file('sha256',$p)!=='3f8d989955e8a5aab9e017bcacc3b128547af21f67fae6fd33802fc2dc7d7dc0')exit(92);
$src=(string)file_get_contents($p);
$out=['stage'=>'A9.11','source'=>'candidate_api','sha_verified'=>true,'contains_secrets'=>false];
$lines=preg_split('/\r\n|\r|\n/',$src);
$out['source_lines']=count($lines);
foreach($lines as $i=>$line)if(stripos($line,'transcribe')!==false||stripos($line,"case 'config'")!==false||stripos($line,"case 'export")!==false){
  $key=(string)($i+1);
  if(count($out['selected_lines']??[])<40){
    $out['selected_lines'][$key]=trim($line);
  }
}
$out['entry_excerpt']=implode("\n",array_slice($lines,0,min(count($lines),235)));
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
