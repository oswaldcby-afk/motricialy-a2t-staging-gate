<?php
declare(strict_types=1);
/**
 * Motricialy Text to Speech v2.21 - update exactly two existing FAQ entries.
 * CLI only. --check does not modify files. --apply writes a backed-up HTML-only change.
 * No WordPress, tracking, API, budget, JS, CSS, or generation logic alterations.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root='/home/u342033576/domains/motricialy.com';
$file=$root.'/public_html/tools/text-to-speech/index.html';
$backup=$root.'/tts-v221-faq-before-index-20261122.html';
function failFaq(string $reason):never{echo "TTS_V221_FAQ_FAIL_".$reason."\n";exit(21);}
$original=@file_get_contents($file);
if(!is_string($original)||strlen($original)<35000||strlen($original)>50000)failFaq('PAGE_BASELINE');
if(!str_contains($original,'<h2>Frequently Asked Questions</h2>')||
   !str_contains($original,'motricialy-tracking.js')||
   !str_contains($original,'tts-v220-generate-cta.css')||
   !str_contains($original,'index-tts-v217.js'))failFaq('SOURCE_BASELINE');
$faqCount=preg_match_all('~<details\b[^>]*\bclass="faq"~',$original);
if($faqCount!==6)failFaq('FAQ_COUNT_BEFORE_'.$faqCount);
$replacements=[
  'Can I download MP3 and WAV?' =>
  'Can I use AI-generated speech commercially?',
  'Yes. After generation, the tool automatically combines your audio and offers both formats.' =>
  'Yes. You may use audio generated for you with Motricialy Text to Speech in lawful commercial projects, including YouTube videos, podcasts, advertisements, and client work, subject to our <a href="/terms-of-use/">Terms of Use</a>, applicable law, and third-party rights. You must have permission to use your script and clearly disclose that the speech is AI-generated. See <a href="/ai-content-usage-rights/">AI Content Usage Rights</a>.',
  'Are the voice previews free?' =>
  'Who owns the copyright to my generated audio?',
  'Yes. Preview recordings match your selected language and do not use your daily generation allowance. English has 10 voices; each other language has four presets.' =>
  'You retain the rights you already hold in your original script. Motricialy grants or assigns only the AI output rights it can legally provide under our <a href="/terms-of-use/">Terms of Use</a>. AI-generated audio may not qualify for copyright protection in every jurisdiction, and using a built-in voice does not give you exclusive rights to that voice. Read our <a href="/ai-content-usage-rights/">AI Content Usage Rights</a> guide.'
];
$updated=$original;
$already=true;
foreach($replacements as $old=>$new){
  $oldCount=substr_count($updated,$old);$newCount=substr_count($updated,$new);
  if($oldCount===1&&$newCount===0){
    $updated=str_replace($old,$new,$updated);
    $already=false;
  }elseif($oldCount===0&&$newCount===1){
    // All parts must have been applied together. Checked below.
  }else{
    failFaq('FAQ_STRING_PATTERN_'.substr(hash('sha256',$old),0,8));
  }
}
if($already){
  echo "TTS_V221_FAQ_ALREADY_PUBLISHED\n";exit(0);
}
if(substr_count($updated,'Can I use AI-generated speech commercially?')!==1||
   substr_count($updated,'Who owns the copyright to my generated audio?')!==1||
   substr_count($updated,'/ai-content-usage-rights/')!==2||
   substr_count($updated,'/terms-of-use/')!==2||
   preg_match_all('~<details\b[^>]*\bclass="faq"~',$updated)!==6||
   substr_count($updated,'<h1')!==substr_count($original,'<h1')||
   substr_count($updated,'application/ld+json')!==substr_count($original,'application/ld+json'))failFaq('UPDATED_HTML_INTEGRITY');
foreach(['Is this Text to Speech tool free?','Can I change just one part of my voiceover?','Will the AI pronounce every name and number correctly?','Is my text sent to OpenAI?'] as $other){
 if(substr_count($updated,$other)!==1)failFaq('OTHER_FAQ_CHANGED');
}
if(in_array('--check',$argv,true)){
 echo "TTS_V221_FAQ_CHECK_OK FAQS=6 NEW_QUESTIONS=2 TERMS_LINKS=2 RIGHTS_LINKS=2 RAW_HTML=YES\n";exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(2);}
if(!is_file($backup)&&!copy($file,$backup))failFaq('BACKUP');
@chmod($backup,0600);
$tmp=dirname($file).'/.tts-v221-faq-'.getmypid().'.tmp';
if(file_put_contents($tmp,$updated,LOCK_EX)!==strlen($updated)||!rename($tmp,$file)||
   hash_file('sha256',$file)!==hash('sha256',$updated))failFaq('WRITE');
echo "TTS_V221_FAQ_DEPLOY_OK FAQS=6 COMMERCIAL_USE=YES COPYRIGHT=YES INTERNAL_LINKS=YES BACKUP=YES\n";
