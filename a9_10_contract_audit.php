<?php
declare(strict_types=1);
/* A9.10: isolated read-only PHP contract audit (no secrets, no HTTP, no API).
   Emits only reviewed candidate function source and selected provider references. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server';
$files=[
 'media.php'=>'03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265',
 'provider.php'=>'03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff',
 'bootstrap.php'=>'1e33ef157a57c898e17bf89fd45bd147712df405b10049fe5c3de0e2efaa8698'
];
$out=['stage'=>'A9.10','paid_calls'=>0,'source_hashes_match'=>true];
foreach($files as $f=>$hash) {
 $p=$root.'/'.$f;
 if(!is_file($p)||is_link($p)||hash_file('sha256',$p)!==$hash){$out['source_hashes_match']=false;break;}
 $src=(string)file_get_contents($p);
 preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',$src,$mm);
 $out['function_names'][$f]=$mm[1];
 $out['direct_reference_patterns'][$f]=[
  'gpt_transcribe'=>str_contains($src,'gpt-transcribe'),
  'uses_ffmpeg'=>stripos($src,'ffmpeg')!==false,
  'uses_curl'=>stripos($src,'curl_init')!==false,
  'uses_languages'=>str_contains($src,'languages'),
  'uses_language'=>str_contains($src,'language'),
  'uses_ledger_reserve'=>str_contains($src,'reserve('),
  'uses_mark_uncertain'=>str_contains($src,'markUncertain'),
  'uses_settle'=>str_contains($src,'settle(')
 ];
}
if($out['source_hashes_match']){
 $src=(string)file_get_contents($root.'/media.php');
 $lines=preg_split('/\r\n|\r|\n/',$src);
 if(preg_match('/function\s+a2t_prepare_direct\s*\(/',$src)){
  $start=null;
  foreach($lines as $i=>$line)if(str_contains($line,'function a2t_prepare_direct')){$start=$i;break;}
  if($start!==null){
   $block=array_slice($lines,$start,min(70,count($lines)-$start));
   // Include only the direct-prep implementation; no environment data.
   $out['prepare_direct_source_excerpt']=implode("\n",$block);
  }
 }
}
if($out['source_hashes_match']){
 $src=(string)file_get_contents($root.'/provider.php');
 $lines=preg_split('/\\r\\n|\\r|\\n/',$src);
 foreach(['a2t_transcribe_provider','a2t_http','a2t_multipart_text'] as $fn){
   foreach($lines as $i=>$line)if(str_contains($line,'function '.$fn.'(')){
     $out['source_excerpt'][$fn]=implode("\\n",array_slice($lines,$i,min(85,count($lines)-$i)));
     break;
   }
 }
}
if($out['source_hashes_match']){
 $b=(string)file_get_contents($root.'/bootstrap.php');
 $ls=preg_split('/\\r\\n|\\r|\\n/',$b);
 foreach($ls as $i=>$l){if(str_contains($l,'function a2t_ready(')){
   $out['a2t_ready_source_excerpt']=implode("\\n",array_slice($ls,$i,min(75,count($ls)-$i)));
   break;
 }}
 $entry=dirname($root).'/test-public/tools/audio-to-text/api/index.php';
 if(is_file($entry)&&hash_file('sha256',$entry)==='3f8d989955e8a5aab9e017bcacc3b128547af21f67fae6fd33802fc2dc7d7dc0'){
   $s=(string)file_get_contents($entry);
   $out['entrypoint_contract']=[
     'includes_media_direct_preparation'=>str_contains($s,'a2t_prepare_direct('),
     'includes_provider_request'=>str_contains($s,'a2t_transcribe_provider('),
     'includes_ledger_reservation'=>str_contains($s,'->reserve('),
     'includes_ledger_settlement'=>str_contains($s,'->settle('),
     'includes_uncertain_reservation'=>str_contains($s,'->markUncertain('),
     'mentions_ffmpeg'=>stripos($s,'ffmpeg')!==false,
     'mentions_upload'=>str_contains($s,'$_FILES')
   ];
 }
}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
