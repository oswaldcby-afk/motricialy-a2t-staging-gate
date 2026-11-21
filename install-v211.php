<?php
declare(strict_types=1);
/* Motricialy Text to Speech v2.11 — guarded one-time, front-end-only installer.
   CLI use: php install-v211.php --check | --apply
   No PHP/API/ledger/media changes. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$payload=__DIR__.'/tts-v211-seekable-player.js.gz.b64';
$target=$root.'/tts-v211-seekable-player.js';
$backup='/home/u342033576/domains/motricialy.com/tts-v211-before-index-20261121.html';
$tag='<script defer src="/tools/text-to-speech/tts-v211-seekable-player.js?v=20261121"></script>';
$expected='ff05d7474c558ed48128dab9e9f1ad027c9a30dd97f5d04fa32ecbfd609e66f5';
function failV211(string $reason):void{echo 'TTS_V211_FAIL_'.$reason."\n";exit(20);}
try{
 if(!is_file($index)||!is_file($payload))failV211('MISSING_INPUT');
 $html=file_get_contents($index);
 $encoded=file_get_contents($payload);
 $binary=is_string($encoded)?base64_decode(trim($encoded),true):false;
 $js=is_string($binary)?gzdecode($binary):false;
 if(!is_string($html)||strlen($html)<35000||
    !str_contains($html,'tts-v210-result-status.js')||
    !str_contains($html,'index-tts-v29.js')||
    !str_contains($html,'motricialy-tracking.js')||
    !str_contains($html,'</body>'))failV211('BASELINE');
 if(!is_string($js)||strlen($js)<8000||hash('sha256',$js)!==$expected)failV211('ASSET_HASH');
 if(str_contains($html,$tag)){
   if(!is_file($target)||hash_file('sha256',$target)!==$expected)failV211('EXISTING_ASSET_MISMATCH');
   echo "TTS_V211_ALREADY_INSTALLED\n";exit(0);
 }
 if(substr_count($html,'</body>')!==1)failV211('BODY_TAG');
 if(in_array('--check',$argv,true)){echo "TTS_V211_DRY_RUN_PASS FRONTEND_ONLY\n";exit(0);}
 if(!in_array('--apply',$argv,true)){echo "USAGE: --check or --apply\n";exit(1);}
 if(!is_file($backup)&&!copy($index,$backup))failV211('BACKUP');
 $tmp=$root.'/.tts-v211-asset-'.getmypid().'.tmp';
 if(file_put_contents($tmp,$js,LOCK_EX)!==strlen($js)||!rename($tmp,$target)||hash_file('sha256',$target)!==$expected)failV211('WRITE_ASSET');
 $updated=str_replace('</body>',$tag."\n</body>",$html);
 $tmpHtml=$root.'/.tts-v211-html-'.getmypid().'.tmp';
 if(file_put_contents($tmpHtml,$updated,LOCK_EX)!==strlen($updated)||!rename($tmpHtml,$index)||!str_contains((string)file_get_contents($index),$tag))failV211('WRITE_HTML');
 echo "TTS_V211_DEPLOY_OK SEEKABLE_FULL=YES SEEKABLE_SECTIONS=YES BACKUP=YES\n";
}catch(Throwable $e){failV211('UNEXPECTED_'.substr($e->getMessage(),0,80));}
