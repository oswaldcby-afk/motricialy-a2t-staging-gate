<?php
/**
 * Motricialy Text to Speech v2.6 one-time Hostinger installer.
 * Changes only the TTS index.html and adds one front-end JS file.
 * No API, voice demos, ledger or WordPress root changes.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$destination=$root.'/tts-v26-voice-details.js';
$source=__DIR__.'/voice-details.js.gz.b64';
$backup='/home/u342033576/domains/motricialy.com/tts-v26-before-index-20261121.html';
$marker='<script defer src="/tools/text-to-speech/tts-v26-voice-details.js?v=20261121"></script>';
$expected='021737b8e92dc37c2691875e665ee01d2494fc3a498ed45db30c52d553f8022f';
if (!is_file($index)||!is_file($source)) {echo "V26_FAIL_MISSING_INPUT\n";exit(2);}
$html=file_get_contents($index);
$encoded=file_get_contents($source);
$decoded=is_string($encoded)?base64_decode(trim($encoded),true):false;
$js=is_string($decoded)?gzdecode($decoded):false;
if (!is_string($html)||strlen($html)<30000||strpos($html,'tts-v25-layout.js')===false||strpos($html,'motricialy-tracking.js')===false||strpos($html,'studio-workspace')===false||stripos($html,'</body>')===false) {
 echo "V26_FAIL_UNEXPECTED_LIVE_HTML\n";exit(3);
}
if (!is_string($js)||hash('sha256',$js)!==$expected) {echo "V26_FAIL_PAYLOAD_HASH\n";exit(4);}
if (strpos($html,$marker)!==false) {echo "TTS_V26_ALREADY_INSTALLED\n";exit(0);}
if (!is_file($backup)&&!copy($index,$backup)) {echo "V26_FAIL_BACKUP\n";exit(5);}
$temp=$root.'/.tts-v26-js-'.getmypid().'.tmp';
if (file_put_contents($temp,$js,LOCK_EX)!==strlen($js)||!rename($temp,$destination)||hash_file('sha256',$destination)!==$expected) {
 echo "V26_FAIL_SCRIPT_COPY\n";exit(6);
}
$new=preg_replace('~</body>~i',$marker."\n</body>",$html,1);
$tempHtml=$root.'/.tts-v26-index-'.getmypid().'.tmp';
if ($new===null||file_put_contents($tempHtml,$new,LOCK_EX)!==strlen($new)||!rename($tempHtml,$index)) {
 echo "V26_FAIL_HTML_WRITE\n";exit(7);
}
if (strpos(file_get_contents($index),$marker)===false) {echo "V26_FAIL_VERIFY\n";exit(8);}
echo "TTS_V26_DEPLOY_OK SHA256=".$expected." BACKUP=YES\n";
