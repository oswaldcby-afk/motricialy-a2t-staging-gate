<?php
declare(strict_types=1);
/* TTS v2.19: desktop editor height + header Generate button position.
   CLI only; modifies one CSS asset and inserts one stylesheet link.
   Does not touch PHP API, audio, React bundle, user quotas or budgets. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$source=__DIR__.'/tts-v219-editor-layout.css';
$dest=$root.'/tts-v219-editor-layout.css';
$backup='/home/u342033576/domains/motricialy.com/tts-v219-before-index-20261122.html';
$tag='<link rel="stylesheet" href="/tools/text-to-speech/tts-v219-editor-layout.css?v=20261122">';
$sha='3f40707e3308b827ed087c045473155c2286e409141542061af35b4b1e55ecb4';
function stop219(string $s):never {echo "TTS_V219_FAIL_".$s."\n";exit(20);}
if(!is_file($index)||!is_file($source)||!is_file($root.'/tts-v25-layout.js'))stop219('INPUT');
$html=file_get_contents($index);$css=file_get_contents($source);
if(!is_string($html)||strlen($html)<34500||!str_contains($html,'tts-v25-layout.js')||!str_contains($html,'index-tts-v217.js')||!str_contains($html,'motricialy-tracking.js')||substr_count($html,'</head>')!==1)stop219('BASELINE');
if(!is_string($css)||strlen($css)<2200||strlen($css)>4500||hash('sha256',$css)!==$sha||!str_contains($css,':has(> .studio-writing)'))stop219('CSS_HASH');
if(substr_count($html,$tag)===1){
  if(!is_file($dest)||hash_file('sha256',$dest)!==$sha)stop219('ASSET_MISMATCH');
  echo "TTS_V219_ALREADY_INSTALLED\n";exit(0);
}
if(str_contains($html,'tts-v219-editor-layout.css'))stop219('EXISTING_REF');
if(in_array('--check',$argv,true)){echo "TTS_V219_DRY_RUN_PASS CSS_ONLY REACT_UNMODIFIED\n";exit(0);}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(2);}
if(!is_file($backup)&&!copy($index,$backup))stop219('BACKUP');
$temp=$root.'/.tts-v219-css-'.getmypid().'.tmp';
if(file_put_contents($temp,$css,LOCK_EX)!==strlen($css)||!rename($temp,$dest)||hash_file('sha256',$dest)!==$sha)stop219('WRITE_CSS');
$newHtml=str_replace('</head>',$tag."\n</head>",$html);
$tempHtml=$root.'/.tts-v219-index-'.getmypid().'.tmp';
if(file_put_contents($tempHtml,$newHtml,LOCK_EX)!==strlen($newHtml)||!rename($tempHtml,$index))stop219('WRITE_HTML');
$read=file_get_contents($index);
if(!is_string($read)||substr_count($read,$tag)!==1||!str_contains($read,'index-tts-v217.js'))stop219('READBACK');
echo "TTS_V219_DEPLOY_OK EDITOR_FILL=YES HEADER_GENERATE=YES MOBILE_UNCHANGED=YES BACKUP=YES\n";
