<?php
/* One-time CLI-only installer for Motricialy TTS v2.5 layout; no API/ledger changes. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$src=__DIR__.'/layout.js';
$dst=$root.'/tts-v25-layout.js';
$backup='/home/u342033576/domains/motricialy.com/tts-v25-before-index-20261121.html';
$marker='<script defer src="/tools/text-to-speech/tts-v25-layout.js?v=20261121"></script>';
if (!is_file($index) || !is_file($src)) { echo "FAIL_MISSING_BASE_OR_SOURCE\n"; exit(2); }
$html=file_get_contents($index); $script=file_get_contents($src);
if ($html===false || $script===false || strlen($script)<1800 ||
    stripos($html,'</body>')===false ||
    strpos($html,'motricialy-tracking.js')===false ||
    strpos($html,'studio-workspace')===false) {
  echo "FAIL_UNEXPECTED_BASELINE\n"; exit(3);
}
if (strpos($html,$marker)!==false) { echo "TTS_V25_ALREADY_PRESENT\n"; exit(0); }
if (!is_file($backup) && !copy($index,$backup)) { echo "FAIL_BACKUP\n"; exit(4); }
$tempScript=$root.'/.tts-v25-layout-'.getmypid().'.tmp';
if (file_put_contents($tempScript,$script,LOCK_EX)!==strlen($script) || !rename($tempScript,$dst)) {
  echo "FAIL_SCRIPT_WRITE\n"; exit(5);
}
if (hash_file('sha256',$src)!==hash_file('sha256',$dst)) {
  echo "FAIL_SCRIPT_VERIFY\n"; exit(6);
}
$updated=preg_replace('~</body>~i',$marker."\n</body>",$html,1);
$tmp=$root.'/.index-v25-'.getmypid().'.tmp';
if ($updated===null || file_put_contents($tmp,$updated,LOCK_EX)!==strlen($updated) || !rename($tmp,$index)) {
  echo "FAIL_HTML_WRITE\n"; exit(7);
}
if (strpos(file_get_contents($index),$marker)===false) { echo "FAIL_HTML_VERIFY\n"; exit(8); }
echo "TTS_V25_DEPLOY_OK BACKUP_PRESERVED SCRIPT_SHA256=".hash_file('sha256',$dst)."\n";
