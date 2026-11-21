<?php
declare(strict_types=1);
/**
 * Motricialy TTS v2.10 isolated result-status UI patch.
 * CLI only; modifies index.html and creates one JS in /tools/text-to-speech/.
 * Does not touch PHP API, API keys, quotas, voice samples, or download logic.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$htmlFile=$root.'/index.html';
$assetFile=$root.'/tts-v210-result-status.js';
$payloadFile=__DIR__.'/tts-v210-progress.js.gz.b64';
$backup='/home/u342033576/domains/motricialy.com/tts-v210-before-index-20261121.html';
$marker='<script defer src="/tools/text-to-speech/tts-v210-result-status.js?v=20261121"></script>';
$expectedHash='ed61916b4c664a9dc9c53b4cc44cd50391e9805f538c67b727e44c95e66ff927';
function failV210(string $problem):void {echo "TTS_V210_FAIL_".$problem."\n";exit(20);}
try {
  if(!is_file($htmlFile)||!is_file($payloadFile))failV210('MISSING_INPUT');
  $html=file_get_contents($htmlFile);
  $encoded=file_get_contents($payloadFile);
  $binary=is_string($encoded)?base64_decode(trim($encoded),true):false;
  $js=is_string($binary)?gzdecode($binary):false;
  if(!is_string($html)||strlen($html)<34000||
      !str_contains($html,'tts-v28-voice-picker.js')||
      !str_contains($html,'index-tts-v29.js')||
      !str_contains($html,'motricialy-tracking.js')||
      !str_contains($html,'</body>'))failV210('BASELINE');
  if(!is_string($js)||hash('sha256',$js)!==$expectedHash)failV210('ASSET_HASH');
  if(str_contains($html,$marker)){
    if(!is_file($assetFile)||hash_file('sha256',$assetFile)!==$expectedHash)failV210('ALREADY_PRESENT_ASSET_MISMATCH');
    echo "TTS_V210_ALREADY_INSTALLED\n";exit(0);
  }
  if(substr_count($html,'</body>')!==1)failV210('BODY_TAG');
  if(in_array('--check',$argv,true)){echo "TTS_V210_DRY_RUN_PASS FRONTEND_ONLY\n";exit(0);}
  if(!in_array('--apply',$argv,true)){echo "TTS_V210_USAGE --check OR --apply\n";exit(1);}
  if(!is_file($backup)&&!copy($htmlFile,$backup))failV210('BACKUP');
  $tempAsset=$root.'/.tts-v210-asset-'.getmypid().'.tmp';
  if(file_put_contents($tempAsset,$js,LOCK_EX)!==strlen($js)||
     !rename($tempAsset,$assetFile)||
     hash_file('sha256',$assetFile)!==$expectedHash)failV210('WRITE_ASSET');
  $updated=str_replace('</body>',$marker."\n</body>",$html);
  $tempHtml=$root.'/.tts-v210-html-'.getmypid().'.tmp';
  if(file_put_contents($tempHtml,$updated,LOCK_EX)!==strlen($updated)||
     !rename($tempHtml,$htmlFile)||
     !str_contains((string)file_get_contents($htmlFile),$marker))failV210('WRITE_HTML');
  echo "TTS_V210_DEPLOY_OK RESULT_PROGRESS=INDETERMINATE READY_STATUS=GREEN_TEXT BACKUP=YES\n";
}catch(Throwable $e){failV210('UNEXPECTED_'.substr($e->getMessage(),0,80));}
