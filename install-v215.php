<?php
declare(strict_types=1);
/* TTS v2.15 — replace v2.11 player with blob-backed true-seekable player.
   CLI only. No API, budget, voice/model, content, quota, or media alterations. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$base='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$base.'/index.html';
$oldPlayer=$base.'/tts-v211-seekable-player.js';
$newPlayer=$base.'/tts-v215-seekable-player.js';
$payload=__DIR__.'/tts-v215-seekable.js.gz.b64';
$backup='/home/u342033576/domains/motricialy.com/tts-v215-before-index-20261121.html';
$oldRef='<script defer src="/tools/text-to-speech/tts-v211-seekable-player.js?v=20261121"></script>';
$newRef='<script defer src="/tools/text-to-speech/tts-v215-seekable-player.js?v=20261121b"></script>';
$oldHash='ff05d7474c558ed48128dab9e9f1ad027c9a30dd97f5d04fa32ecbfd609e66f5';
$newHash='d02f8035bd49ad2531691c15727483c688112f21b0012c3d0b0beec4ded7e236';
function failV215(string $reason):never{echo "TTS_V215_FAIL_".$reason."\n";exit(20);}
if(!is_file($index)||!is_file($payload)||!is_file($oldPlayer))failV215('MISSING_INPUT');
$html=file_get_contents($index);
$encoded=file_get_contents($payload);
$compressed=is_string($encoded)?base64_decode(trim($encoded),true):false;
$js=is_string($compressed)?gzdecode($compressed):false;
if(!is_string($html)||strlen($html)<34000||!str_contains($html,'motricialy-tracking.js')||!str_contains($html,'tts-v210-result-status.js')||!str_contains($html,'tts-v28-voice-picker.js'))failV215('HTML_BASELINE');
if(!is_string($js)||strlen($js)<9000||hash('sha256',$js)!==$newHash)failV215('NEW_HASH');
if(hash_file('sha256',$oldPlayer)!==$oldHash)failV215('OLD_HASH');
if(substr_count($html,$newRef)===1){
 if(!is_file($newPlayer)||hash_file('sha256',$newPlayer)!==$newHash)failV215('ALREADY_BAD_ASSET');
 echo "TTS_V215_ALREADY_INSTALLED\n";exit(0);
}
if(substr_count($html,$oldRef)!==1||str_contains($html,'tts-v215-seekable-player.js'))failV215('HTML_REF');
if(in_array('--check',$argv,true)){echo "TTS_V215_DRY_RUN_PASS FULL_AND_SECTION SEEKBLOB=ON FRONTEND_ONLY\n";exit(0);}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(1);}
if(!is_file($backup)&&!copy($index,$backup))failV215('BACKUP');
$temp=$base.'/.tts-v215-js-'.getmypid().'.tmp';
if(file_put_contents($temp,$js,LOCK_EX)!==strlen($js)||!rename($temp,$newPlayer)||hash_file('sha256',$newPlayer)!==$newHash)failV215('WRITE_ASSET');
$next=str_replace($oldRef,$newRef,$html);
$tempHtml=$base.'/.tts-v215-index-'.getmypid().'.tmp';
if(file_put_contents($tempHtml,$next,LOCK_EX)!==strlen($next)||!rename($tempHtml,$index))failV215('WRITE_HTML');
$readback=file_get_contents($index);
if(!is_string($readback)||substr_count($readback,$newRef)!==1||str_contains($readback,$oldRef))failV215('READBACK');
echo "TTS_V215_DEPLOY_OK OLD_PLAYER_REPLACED=YES FULL_SEEK=YES SECTION_SEEK=YES BACKUP=YES\n";
