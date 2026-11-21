<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$doc='/home/u342033576/domains/motricialy.com/public_html';
$root=$doc.'/tools/audio-to-text';
$pvt='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server';
$out=['stage'=>'A9.11','audit'=>'production_php_and_static','status'=>'NOT_READY','paid_calls'=>0];
try{
 $phpFiles=[$root.'/api/index.php',$pvt.'/bootstrap.php',$pvt.'/ledger.php',$pvt.'/media.php',$pvt.'/provider.php',$pvt.'/docx.php'];
 foreach($phpFiles as $file){
  if(!is_file($file)||is_link($file))throw new RuntimeException('php_file_missing');
  $cmd=[PHP_BINARY,'-l',$file];
  $p=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($p))throw new RuntimeException('lint_unavailable');
  fclose($pipes[0]);$stdout=(string)stream_get_contents($pipes[1]);$stderr=(string)stream_get_contents($pipes[2]);
  fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);
  $out['php_lint'][basename($file)]=$rc===0&&str_contains($stdout,'No syntax errors');
  if(!$out['php_lint'][basename($file)])throw new RuntimeException('php_lint_failure');
 }
 $html=(string)file_get_contents($root.'/index.html');
 $css=(string)file_get_contents($root.'/assets/audio-to-text.css');
 $js=(string)file_get_contents($root.'/assets/audio-to-text.js');
 $ht=(string)file_get_contents($root.'/api/.htaccess');
 $out['one_h1']=preg_match_all('/<h1\\b/i',$html)===1;
 $out['static_software_schema']=str_contains($html,'SoftwareApplication')&&str_contains($html,'BreadcrumbList');
 $out['static_content_complete']=str_contains($html,'How to convert audio to text')&&str_contains($html,'Audio to Text FAQ')
  &&str_contains($html,'Useful transcription features')&&str_contains($html,'Explore more Motricialy tools');
 $out['chrome_single_mounts']=substr_count($html,'data-motricialy-site-header')===1&&
  substr_count($html,'data-motricialy-site-footer')===1&&substr_count($html,'/assets/motricialy-site-chrome.js')===1;
 $out['mobile_css_present']=str_contains($css,'@media(max-width:650px)')&&str_contains($css,'@media(max-width:940px)');
 $out['javascript_real_api_client']=str_contains($js,"api+'transcribe'")&&str_contains($js,'new FormData()')&&str_contains($js,'transcript');
 $out['js_no_external_calls']=!preg_match('~https?://~',$js);
 $out['directory_php_limits']=str_contains($ht,'php_value upload_max_filesize 24M')&&str_contains($ht,'php_value post_max_size 25M')&&
  str_contains($ht,'php_value max_file_uploads 1')&&str_contains($ht,'LimitRequestBody 27262976');
 $out['prelaunch_noindex']=stripos($html,'noindex,nofollow')!==false;
 $out['release_api_routes_protected']=str_contains((string)file_get_contents($root.'/api/index.php'),'a9-11-public-enabled.flag')&&
  str_contains((string)file_get_contents($root.'/api/index.php'),'a9-11-release-qa-gate.private');
 $all=true;foreach($out as $v)if(is_bool($v)&&$v!==true)$all=false;
 foreach($out['php_lint']??[] as $v)if(!$v)$all=false;
 $out['status']=$all?'PRODUCTION_STATIC_QA_PASS':'PRODUCTION_STATIC_QA_FAILED';
}catch(Throwable $e){$out['status']='PRODUCTION_STATIC_QA_ERROR';$out['error_type']=get_class($e);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PRODUCTION_STATIC_QA_PASS')exit(97);
