<?php
declare(strict_types=1);
/** Fix the remaining old TTS FAQ embedded in the hydrated React app.
 * CLI-only, scoped to TTS index.html and new versioned JS asset.
 * --check is read-only; --apply is backup-first and atomic.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$oldJs=$root.'/assets/index-tts-v217.js';
$newJs=$root.'/assets/index-tts-v2211.js';
$backup='/home/u342033576/domains/motricialy.com/tts-v2211-before-index-20261122.html';
$oldHash='1eee1f3224c9bb7e04cbb884afcb29b0263b7fff04598bdf85ec7bd9ecbea075';
$newHash='22975a84b7100aad345080172b1021857250e8b9c94f1219ed290b4d35d3198f';
function fail2211(string $s):never{echo "TTS_V2211_FAIL_".$s."\n";exit(20);}
function once2211(string $s,string $old,string $new,string $label):string{
  if(substr_count($s,$old)!==1)fail2211('PATTERN_'.$label);
  return str_replace($old,$new,$s);
}
$html=@file_get_contents($index);
$bundle=@file_get_contents($oldJs);
if(!is_string($html)||!is_string($bundle)||strlen($html)<35500||strlen($bundle)<280000)fail2211('INPUT');
if(hash('sha256',$bundle)!==$oldHash)fail2211('OLD_JS_HASH');
foreach([
 'Can I use AI-generated speech commercially?',
 'Who owns the copyright to my generated audio?',
 'tts-v220-generate-cta.css',
 'motricialy-tracking.js'
] as $needle)if(!str_contains($html,$needle))fail2211('HTML_BASELINE');
if(str_contains($html,'Can I download MP3 and WAV?')||str_contains($html,'Are the voice previews free?'))fail2211('STATIC_FAQ_OLD');
$oldSrc='index-tts-v217.js';
$newSrc='index-tts-v2211.js';
if(substr_count($html,$newSrc)===1){
 if(!is_file($newJs)||hash_file('sha256',$newJs)!==$newHash)fail2211('ALREADY_ASSET');
 echo "TTS_V2211_ALREADY_INSTALLED\n";exit(0);
}
if(substr_count($html,$oldSrc)!==1)fail2211('SCRIPT_REF');
$updates=[
 'Can I download MP3 and WAV?' =>
 'Can I use AI-generated speech commercially?',
 'Yes. After generation, the tool automatically combines your audio and offers both formats.' =>
 'Yes. You may use audio generated for you with Motricialy Text to Speech in lawful commercial projects, including YouTube videos, podcasts, advertisements, and client work, subject to our [[TERMS]], applicable law, and third-party rights. You must have permission to use your script and clearly disclose that the speech is AI-generated. See [[RIGHTS]].',
 'Are the voice previews free?' =>
 'Who owns the copyright to my generated audio?',
 'Yes. Preview recordings match your selected language and do not use your daily generation allowance. English has 10 voices; each other language has four presets.' =>
 'You retain the rights you already hold in your original script. Motricialy grants or assigns only the AI output rights it can legally provide under our [[TERMS]]. AI-generated audio may not qualify for copyright protection in every jurisdiction, and using a built-in voice does not give you exclusive rights to that voice. Read our [[RIGHTS]] guide.'
];
$newBundle=$bundle;
foreach($updates as $old=>$new)$newBundle=once2211($newBundle,$old,$new,substr(hash('sha256',$old),0,8));
$faqPos=strpos($newBundle,'jd.map(([s,A])');
if($faqPos===false)fail2211('FAQ_RENDERER_MISSING');
$prefix=substr($newBundle,0,$faqPos);
$suffix=substr($newBundle,$faqPos);
$oldRenderer='r.jsx("p",{children:A})]},s))})]})';
$newRenderer=<<<'JSRENDER'
r.jsx("p",{children:A.split(/(\[\[TERMS\]\]|\[\[RIGHTS\]\])/g).map((x,i)=>x==="[[TERMS]]"?r.jsx("a",{href:"/terms-of-use/",children:"Terms of Use"},i):x==="[[RIGHTS]]"?r.jsx("a",{href:"/ai-content-usage-rights/",children:"AI Content Usage Rights"},i):x)})]},s))})]})
JSRENDER;
$suffix=once2211($suffix,$oldRenderer,$newRenderer,'FAQ_LINKS');
$newBundle=$prefix.$suffix;
if(hash('sha256',$newBundle)!==$newHash)fail2211('NEW_JS_HASH');
if(str_contains($newBundle,'Can I download MP3 and WAV?')||str_contains($newBundle,'Are the voice previews free?'))fail2211('OLD_FAQS_REMAIN');
$updatedHtml=once2211($html,$oldSrc,$newSrc,'HTML_JS_REF');
if(in_array('--check',$argv,true)){
 echo "TTS_V2211_CHECK_OK STATIC_AND_REACT_FAQS_SYNCED=YES REAL_LINKS=YES BUNDLE_CHECKSUM=OK\n";exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE --check OR --apply\n";exit(2);}
if(!is_file($backup)&&!copy($index,$backup))fail2211('BACKUP');
@chmod($backup,0600);
function atomic2211(string $file,string $data):void{
 $tmp=dirname($file).'/.tts-v2211-'.basename($file).'-'.getmypid().'.tmp';
 if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data)||
    !rename($tmp,$file)||hash_file('sha256',$file)!==hash('sha256',$data))fail2211('WRITE_'.basename($file));
}
atomic2211($newJs,$newBundle);
atomic2211($index,$updatedHtml);
$after=@file_get_contents($index);
if(!is_string($after)||substr_count($after,$newSrc)!==1||
  !str_contains($after,'Can I use AI-generated speech commercially?')||
  !str_contains($after,'Who owns the copyright to my generated audio?'))fail2211('READBACK');
echo "TTS_V2211_DEPLOY_OK REACT_FAQ_UPDATED=YES STATIC_FAQ_MATCH=YES LEGAL_LINKS=YES VERSIONED_JS=YES BACKUP=YES\n";
