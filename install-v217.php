<?php
declare(strict_types=1);
/**
 * Motricialy Text to Speech v2.17 — smart sentence-aware take segmentation.
 * CLI only. --check is read-only. --apply backs up then updates bootstrap,
 * validator, and one versioned browser JS asset plus HTML reference.
 * Never accesses API keys or modifies the billing ledger.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root=getenv('TTS_V217_TEST_ROOT')?:'/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$files=[
 'bootstrap'=>$root.'/api/bootstrap.php',
 'validator'=>$root.'/api/validator.php',
 'bundle'=>$root.'/assets/index-tts-v216.js',
 'html'=>$root.'/index.html'
];
$oldHashes=[
 'bootstrap'=>'e5b2c23cb5a44d15771dcab4b180cfc4809b6b879d3f5830cca55587828e8e2c',
 'validator'=>'5974bd0fc84c7e56c4afd12b4cab749ed1e59b1e0c745ed4e088f51615a70e94',
 'bundle'=>'b4cb02b7e1e0b843d0241ef3007b85974d9a832eb902c3ffe9ac966f74f75576'
];
$newHashes=[
 'bootstrap'=>'2b1b765eb57885d0186b1535235d64ed82b5f98867453d561de658b33041221e',
 'validator'=>'67fbae8a5997842631ab945eb2eeb2e1274d641d8e72e662358f45e55a839963',
 'bundle'=>'1eee1f3224c9bb7e04cbb884afcb29b0263b7fff04598bdf85ec7bd9ecbea075'
];
function abort217(string $reason):never{echo "TTS_V217_FAIL_".$reason."\n";exit(21);}
function once217(string $s,string $old,string $replacement,string $label):string{
 if(substr_count($s,$old)!==1)abort217('PATCH_'.$label);
 return str_replace($old,$replacement,$s);
}
try{
 $old=[];
 foreach($files as $name=>$file){
  $data=@file_get_contents($file);
  if(!is_string($data))abort217('MISSING_'.$name);
  $old[$name]=$data;
  if(isset($oldHashes[$name])&&hash('sha256',$data)!==$oldHashes[$name])abort217('OLD_SHA_'.$name);
 }
 if(!str_contains($old['html'],'tts-v216-players.js')||!str_contains($old['html'],'motricialy-tracking.js')||
    substr_count($old['html'],'index-tts-v216.js')!==1)abort217('HTML_BASELINE');
 $bootstrap=once217($old['bootstrap'],"'partial'=>180,","'partial'=>400,",'CONFIG_PARTIAL');
 $validator=once217($old['validator'],
 "      if(\$len<1||\$len>180)throw new InvalidArgumentException('Each take must be 1–180 characters');\n      \$voice=(string)(\$s['voice']??'marin');\$style=(string)(\$s['style']??'Natural');\$lang=(string)(\$s['language']??'en');",
 "      \$voice=(string)(\$s['voice']??'marin');\$style=(string)(\$s['style']??'Natural');\$lang=(string)(\$s['language']??'en');\n      \$maxTake=in_array(\$lang,['zh','ja','ko'],true)?220:TTS_LIMITS['partial'];\n      if(\$len<1||\$len>\$maxTake)throw new InvalidArgumentException('Each take must be 1–'.\$maxTake.' characters for this language');",
 'VALIDATE_TAKE');
 $validator=once217($validator,
 "      if(tts_length(\$spoken)>420)throw new InvalidArgumentException('Pronunciation expansion exceeds 420 characters');",
 "      \$maxSpoken=min(480,\$maxTake+80);\n      if(tts_length(\$spoken)>\$maxSpoken)throw new InvalidArgumentException('Pronunciation expansion exceeds '.\$maxSpoken.' characters');",
 'EXPANSION');
 $validator=once217($validator,
 "      foreach(\$units as \$u)if(tts_length(\$u['text'])>420)throw new InvalidArgumentException('Speech chunk too long');",
 "      foreach(\$units as \$u)if(tts_length(\$u['text'])>\$maxSpoken)throw new InvalidArgumentException('Speech chunk too long');",
 'SPOKEN_LIMIT');
 $bundle=$old['bundle'];
 $oldStart=strpos($bundle,'function k4(s,A=180)');
 $oldEnd=$oldStart!==false?strpos($bundle,'function P4(',$oldStart):false;
 if($oldStart===false||$oldEnd===false)abort217('SPLITTER_BOUNDARY');
 $splitter=<<<'JS'
function v17(s){return /^(zh|ja|ko)$/.test(s||'')?220:400}function k4(s,A=400){const out=[];let rest=String(s||'').trim();const target=Math.min(350,A*.9);while(rest.length){if(rest.length<=A){out.push(rest);break}const head=rest.slice(0,A+1);let end=0;const sentence=/(?:[.!?]+[)”’"']*(?:\s+|$)|[。！？]+[)”’"']*\s*|\n\s*\n)/gu;for(let m;(m=sentence.exec(head));){const x=m.index+m[0].length;if(x<=A)end=x}if(!end){const clauses=/[,，、;；:：]+[)”’"']*(?:\s+|$)/gu;let candidate=0;for(let m;(m=clauses.exec(head));){const x=m.index+m[0].length;if(x<=A&&x>=target*.55)candidate=x}end=candidate}if(!end){const cut=Math.min(A,Math.max(1,Math.round(target)));let space=-1;for(let i=Math.min(A,head.length-1);i>0;i--){if(/\s/u.test(head[i])){space=i+1;break}}if(space>=target*.6)end=space;else end=cut}if(end<=0)end=A;while(end>1){const left=rest.charCodeAt(end-1),right=rest.charCodeAt(end);if(left>=55296&&left<=56319&&right>=56320&&right<=57343){end--;continue}break}const chunk=rest.slice(0,end).trim();if(!chunk)throw Error('Unable to split text');out.push(chunk);rest=rest.slice(end).trimStart();if(out.length>12)throw Error('Too many sections; simplify the text')}return out}
JS;
 $bundle=substr_replace($bundle,$splitter,$oldStart,$oldEnd-$oldStart);
 $bundle=once217($bundle,'Ut={full:2e3,partial:180,takes:12}','Ut={full:2e3,partial:400,takes:12}','JS_LIMITS');
 $bundle=once217($bundle,'function Vs(s,A,_){','function Vs(s,A,_,lang){','VS_SIG');
 $bundle=once217($bundle,'return k4(A).map(Z=>','return k4(A,v17(lang)).map(Z=>','VS_SPLIT');
 $bundle=once217($bundle,'Vs(T,_,s.voice)','Vs(T,_,s.voice,s.language)','VS_EDIT');
 $bundle=once217($bundle,'Vs(Z,_,s.voice)','Vs(Z,_,s.voice,s.language)','VS_GENERATE');
 $bundle=once217($bundle,'maxLength:Ut.partial,rows:3','maxLength:v17(C),rows:5','EDITOR_LIMIT');
 $bundle=once217($bundle,'s.text.length,"/",Ut.partial','s.text.length,"/",v17(C)','EDITOR_COUNT');
 $bundle=once217($bundle,
 'if(T==="partial"&&I[0].text.length>Ut.partial){W("Section regeneration is limited to 180 characters.");return}',
 'if(T==="partial"&&I[0].text.length>v17(s.language)){W("Section regeneration is limited to "+v17(s.language)+" characters for this language.");return}',
 'PARTIAL_NOTICE');
 $new=['bootstrap'=>$bootstrap,'validator'=>$validator,'bundle'=>$bundle];
 foreach($new as $name=>$data){
  if(hash('sha256',$data)!==$newHashes[$name])abort217('NEW_SHA_'.$name.'_'.substr(hash('sha256',$data),0,12));
 }
 $html=once217($old['html'],'index-tts-v216.js','index-tts-v217.js','HTML_SCRIPT');
 if(in_array('--check',$argv,true)){echo "TTS_V217_CHECK_OK SMART_SPLIT=350_TARGET_400_MAX FULL=2000 TAKES=12 CJK=220\n";exit(0);}
 if(!in_array('--apply',$argv,true)){echo "USAGE --check OR --apply\n";exit(1);}
 if(!getenv('TTS_V217_TEST_ROOT')){
  $ledger='/home/u342033576/domains/motricialy.com/motricialy-tts-private/ledger.sqlite';
  if(!is_file($ledger))abort217('LEDGER_UNAVAILABLE');
  $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
  $db->exec('PRAGMA query_only=ON');
  if((int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn()>0)abort217('ACTIVE_GENERATION');
 }
 $backup=getenv('TTS_V217_TEST_ROOT')?dirname($root).'/tts-v217-backup':'/home/u342033576/domains/motricialy.com/tts-v217-backup-20261121';
 if(!is_dir($backup)&&!mkdir($backup,0700,true))abort217('BACKUP_DIRECTORY');
 foreach(['bootstrap'=>$old['bootstrap'],'validator'=>$old['validator'],'html'=>$old['html']] as $name=>$data){
  $dst=$backup.'/'.$name.'.bak';
  if(!is_file($dst)&&file_put_contents($dst,$data,LOCK_EX)!==strlen($data))abort217('BACKUP_'.$name);
 }
 function put217(string $file,string $data):void{
  $tmp=dirname($file).'/.tts-v217-'.basename($file).'-'.getmypid().'.tmp';
  if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data)||!rename($tmp,$file)||hash_file('sha256',$file)!==hash('sha256',$data))abort217('WRITE_'.basename($file));
 }
 put217($root.'/assets/index-tts-v217.js',$bundle);
 put217($files['validator'],$validator);
 put217($files['bootstrap'],$bootstrap);
 put217($files['html'],$html);
 if(!str_contains((string)file_get_contents($files['html']),'index-tts-v217.js'))abort217('HTML_READBACK');
 echo "TTS_V217_DEPLOY_OK SMART_SENTENCE_SEGMENTS=YES TAKE_LIMIT=400 CJK_LIMIT=220 PARTIAL_REGEN=400 BACKUP=YES\n";
}catch(Throwable $e){abort217('EXCEPTION_'.get_class($e));}
