<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$p='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server/media.php';
if(!is_file($p)||hash_file('sha256',$p)!=='03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265')exit(93);
$src=(string)file_get_contents($p);
preg_match_all('/\\bA2T_[A-Z][A-Z0-9_]+\\b/',$src,$constantMatches);
preg_match_all('/\\ba2t_[a-z][a-z0-9_]+\\s*\\(/',$src,$funcCalls);
preg_match_all('/\\b(new\\s+[A-Za-z0-9_\\\\]+|getID3|is_uploaded_file|move_uploaded_file|proc_open)\\b/i',$src,$native);
$found=array_unique($constantMatches[0]);sort($found);
$called=array_unique($funcCalls[0]);sort($called);
echo json_encode(['stage'=>'A9.9','kind'=>'media_static_structure',
'constants'=>$found,'function_calls'=>$called,
'has_is_uploaded_file'=>stripos($src,'is_uploaded_file')!==false,
'has_move_uploaded_file'=>stripos($src,'move_uploaded_file')!==false,
'has_getid3'=>stripos($src,'getid3')!==false,
'has_ffmpeg'=>stripos($src,'ffmpeg')!==false,
'has_ffprobe'=>stripos($src,'ffprobe')!==false,
'has_proc_open'=>stripos($src,'proc_open')!==false,
'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
