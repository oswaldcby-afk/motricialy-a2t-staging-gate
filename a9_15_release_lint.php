<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$folder='/home/u342033576/domains/motricialy.com/public_html/staging/audio-to-text-wave1-preview';
$file=$folder.'/api/index.php';
if(!is_file($file)||is_link($file))exit(92);
$process=proc_open([PHP_BINARY,'-l',$file],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
if(!is_resource($process))exit(93);
fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);
fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
$code=(string)file_get_contents($file);
$ht=(string)file_get_contents($folder.'/api/.htaccess');
$html=(string)file_get_contents($folder.'/index.html');
$out=['stage'=>'A9.15','mode'=>'staging_readonly_release_gate','paid_calls'=>0,
 'php_syntax_valid'=>$exit===0&&str_contains($stdout,'No syntax errors'),
 'capabilities_route'=>str_contains($code,'capabilities'),
 'private_text_flag'=>str_contains($code,'a9-15-text-enabled.flag'),
 'qa_private_header'=>str_contains($code,'a9-11-release-qa-gate.private'),
 'text_size_bound'=>str_contains($code,'$bytes>45000'),
 'strict_prod_path'=>str_contains($code,"'/tools/audio-to-text/api/'"),
 'rewrites_capabilities'=>str_contains($ht,'process|capabilities'),
 'preview_noindex'=>str_contains((string)file_get_contents($folder.'/.htaccess'),'noindex'),
 'source_php_mentions_api_key'=>str_contains($code,'OPENAI_API_KEY'),
 'visible_upload_controls'=>str_contains($html,'id="audio-player"')&&str_contains($html,'id="record-start"'),
 'no_secrets_in_html'=>!str_contains($html,'sk-proj-')
];
$out['passed']=count(array_filter($out,fn($v)=>is_bool($v)&&$v));
$out['status']=!in_array(false,array_filter($out,fn($v)=>is_bool($v)),true)?'STAGING_PHP_GATE_PASS':'STAGING_PHP_GATE_FAIL';
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='STAGING_PHP_GATE_PASS')exit(97);
