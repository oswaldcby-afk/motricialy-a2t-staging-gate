<?php
/* Motricialy TTS v2.7 guarded one-time installer: front-end only. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$source=__DIR__.'/tts-v27-picker.js.gz.b64';
$jsfile=$root.'/tts-v27-voice-picker.js';
$backup='/home/u342033576/domains/motricialy.com/tts-v27-before-index-20261121.html';
$marker='<script defer src="/tools/text-to-speech/tts-v27-voice-picker.js?v=20261121"></script>';
$expected='ec47bb51b8953d1915fd2bd8b1382ce90e9c439019c7349cb027416ff683ee69';
if(!is_file($index)||!is_file($source)){echo "TTS_V27_FAIL_MISSING_INPUT\n";exit(2);}
$html=file_get_contents($index);
$encoded=file_get_contents($source);
$compressed=is_string($encoded)?base64_decode(trim($encoded),true):false;
$script=is_string($compressed)?gzdecode($compressed):false;
if(!is_string($html)||strlen($html)<34000||strpos($html,'tts-v25-layout.js')===false||strpos($html,'tts-v26-voice-details.js')===false||strpos($html,'motricialy-tracking.js')===false||stripos($html,'</body>')===false){
 echo "TTS_V27_FAIL_UNEXPECTED_BASELINE\n";exit(3);
}
if(!is_string($script)||hash('sha256',$script)!==$expected){echo "TTS_V27_FAIL_HASH\n";exit(4);}
if(strpos($html,$marker)!==false){echo "TTS_V27_ALREADY_INSTALLED\n";exit(0);}
if(!is_file($backup)&&!copy($index,$backup)){echo "TTS_V27_FAIL_BACKUP\n";exit(5);}
$tmp=$root.'/.tts-v27-js-'.getmypid().'.tmp';
if(file_put_contents($tmp,$script,LOCK_EX)!==strlen($script)||!rename($tmp,$jsfile)||hash_file('sha256',$jsfile)!==$expected){echo "TTS_V27_FAIL_SCRIPT_WRITE\n";exit(6);}
$new=preg_replace('~</body>~i',$marker."\n</body>",$html,1);
$tmphtml=$root.'/.tts-v27-index-'.getmypid().'.tmp';
if(!is_string($new)||file_put_contents($tmphtml,$new,LOCK_EX)!==strlen($new)||!rename($tmphtml,$index)){echo "TTS_V27_FAIL_HTML_WRITE\n";exit(7);}
if(strpos(file_get_contents($index),$marker)===false){echo "TTS_V27_FAIL_VERIFY\n";exit(8);}
echo "TTS_V27_DEPLOY_OK SCRIPT_SHA256=$expected BACKUP=YES\n";
