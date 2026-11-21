<?php
declare(strict_types=1);
/* Motricialy Text to Speech v2.20: prominent, vertically centered Generate CTA.
   CLI only. CSS + one index.html link; no changes to APIs, quotas or media. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$src=__DIR__.'/tts-v220-generate-cta.css';
$dst=$root.'/tts-v220-generate-cta.css';
$backup='/home/u342033576/domains/motricialy.com/tts-v220-before-index-20261122.html';
$tag='<link rel="stylesheet" href="/tools/text-to-speech/tts-v220-generate-cta.css?v=20261122a">';
$hash='ae2a7850cb2cc4e846fdfb0389e478b00bd8cab6f9d978f1e2e81bb54ba22d98';
function fail220(string $reason):never{echo "TTS_V220_FAIL_".$reason."\n";exit(20);}
if(!is_file($index)||!is_file($src))fail220('MISSING_INPUT');
$html=file_get_contents($index);$css=file_get_contents($src);
if(!is_string($html)||strlen($html)<35000||!str_contains($html,'tts-v219-editor-layout.css')||
   !str_contains($html,'tts-v25-layout.js')||!str_contains($html,'index-tts-v217.js')||
   !str_contains($html,'motricialy-tracking.js')||substr_count($html,'</head>')!==1)fail220('HTML_BASELINE');
if(!is_string($css)||hash('sha256',$css)!==$hash||strlen($css)<2800||strlen($css)>3500)fail220('CSS_CHECKSUM');
if(substr_count($html,$tag)===1){
 if(!is_file($dst)||hash_file('sha256',$dst)!==$hash)fail220('ALREADY_ASSET_MISMATCH');
 echo "TTS_V220_ALREADY_INSTALLED\n";exit(0);
}
if(str_contains($html,'tts-v220-generate-cta.css'))fail220('EXISTING_LINK');
if(in_array('--check',$argv,true)){echo "TTS_V220_CHECK_OK DESKTOP_ALIGN=EXACT BIGGER_CTA=YES CSS_ONLY\n";exit(0);}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(1);}
if(!is_file($backup)&&!copy($index,$backup))fail220('BACKUP');
$tmpCss=$root.'/.tts-v220-css-'.getmypid().'.tmp';
if(file_put_contents($tmpCss,$css,LOCK_EX)!==strlen($css)||!rename($tmpCss,$dst)||
   hash_file('sha256',$dst)!==$hash)fail220('WRITE_CSS');
$new=str_replace('</head>',$tag."\n</head>",$html);
$tmpHtml=$root.'/.tts-v220-html-'.getmypid().'.tmp';
if(file_put_contents($tmpHtml,$new,LOCK_EX)!==strlen($new)||!rename($tmpHtml,$index))fail220('WRITE_HTML');
$read=file_get_contents($index);
if(!is_string($read)||substr_count($read,$tag)!==1||!str_contains($read,'tts-v219-editor-layout.css'))fail220('READBACK');
echo "TTS_V220_DEPLOY_OK PROMINENT_GENERATE=YES HEADING_CENTER_ALIGNED=YES MOBILE_UNCHANGED=YES BACKUP=YES\n";
