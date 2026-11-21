<?php
/* Motricialy TTS v2.8: update only two front-end voice scripts and HTML references. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root=getenv('TTS_V28_TEST_ROOT')?:'/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$old26=$root.'/tts-v26-voice-details.js';
$old27=$root.'/tts-v27-voice-picker.js';
if(!is_file($index)||!is_file($old26)||!is_file($old27)){echo "FAIL:NO_BASE\n";exit(2);}
$html=file_get_contents($index);$a=file_get_contents($old26);$b=file_get_contents($old27);
if(!is_string($html)||!is_string($a)||!is_string($b)||strlen($html)<34000||!str_contains($html,'motricialy-tracking.js')||!str_contains($html,'tts-v25-layout.js')){echo "FAIL:BASELINE\n";exit(3);}
if(hash('sha256',$a)!=='021737b8e92dc37c2691875e665ee01d2494fc3a498ed45db30c52d553f8022f'||hash('sha256',$b)!=='ec47bb51b8953d1915fd2bd8b1382ce90e9c439019c7349cb027416ff683ee69'){echo "FAIL:OLD_HASH\n";exit(4);}
function replaceOnce($s,$old,$new,$expected=1){if(substr_count($s,$old)!==$expected){echo "FAIL:STRING_".substr(hash('sha256',$old),0,8)."\n";exit(5);}return str_replace($old,$new,$s);}
function genders($s){
 foreach(['sage'=>['Neutral','Female'],'alloy'=>['Neutral','Male'],'ballad'=>['Female','Male']] as $id=>$change){
  [$from,$to]=$change;
  $pat='~('.$id.':\\s*\\{gender:)\\\''.$from.'\\\''.'~';
  $s=preg_replace_callback($pat,fn($m)=>$m[1]."'".$to."'",$s,1,$n);
  if($n!==1){echo "FAIL:GENDER_".$id."\n";exit(6);}
 }
 return $s;
}
$a=genders($a);$b=genders($b);
$a=replaceOnce($a,'v2.6 — centered hero','v2.8 — centered hero');
$a=replaceOnce($a,'window.__motricialyTtsV26','window.__motricialyTtsV28Details',2);
$a=replaceOnce($a,"style.id='tts-v26-voice-styles'","style.id='tts-v28-voice-styles'");
$b=replaceOnce($b,'v2.7: progressive','v2.8: progressive');
$b=replaceOnce($b,'window.__motricialyTtsV27','window.__motricialyTtsV28Picker',2);
$b=replaceOnce($b,"style.id = 'tts-v27-picker-style'","style.id = 'tts-v28-picker-style'");
$b=replaceOnce($b,"['All','Female','Male','Neutral']","['All','Female','Male']");
$icons=<<<'ICONS'
  const PHOSPHOR_PLAY = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48.24-94.78-64-40A8,8,0,0,0,100,88v80a8,8,0,0,0,12.24,6.78l64-40a8,8,0,0,0,0-13.56ZM116,153.57V102.43L156.91,128Z\"/></svg>";
  const PHOSPHOR_PAUSE = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216ZM112,96v64a8,8,0,0,1-16,0V96a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V96a8,8,0,0,1,16,0Z\"/></svg>";
  const PHOSPHOR_CARET = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z\"/></svg>";
  const PHOSPHOR_X = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z\"/></svg>";
  const PHOSPHOR_CHECK = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34ZM232,128A104,104,0,1,1,128,24,104.11,104.11,0,0,1,232,128Zm-16,0a88,88,0,1,0-88,88A88.1,88.1,0,0,0,216,128Z\"/></svg>";
  const PHOSPHOR_WAVEFORM = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 256 256\" fill=\"currentColor\" focusable=\"false\" aria-hidden=\"true\"><path d=\"M56,96v64a8,8,0,0,1-16,0V96a8,8,0,0,1,16,0ZM88,24a8,8,0,0,0-8,8V224a8,8,0,0,0,16,0V32A8,8,0,0,0,88,24Zm40,32a8,8,0,0,0-8,8V192a8,8,0,0,0,16,0V64A8,8,0,0,0,128,56Zm40,32a8,8,0,0,0-8,8v64a8,8,0,0,0,16,0V96A8,8,0,0,0,168,88Zm40-16a8,8,0,0,0-8,8v96a8,8,0,0,0,16,0V80A8,8,0,0,0,208,72Z\"/></svg>";
ICONS;
if(!preg_match("~  const ICON_PLAY='[^\\n]*';\\n  const ICON_PAUSE='[^\\n]*';~",$b)){echo "FAIL:ICON_BASE\n";exit(7);}
$b=preg_replace("~  const ICON_PLAY='[^\\n]*';\\n  const ICON_PAUSE='[^\\n]*';~",$icons."\n  const ICON_PLAY=PHOSPHOR_PLAY;\n  const ICON_PAUSE=PHOSPHOR_PAUSE;",$b,1,$n);
if($n!==1){echo "FAIL:ICON_REPLACE\n";exit(8);}
$repl=[
 "const closeButton = el('button','tts-v27-close','×');" => "const closeButton = el('button','tts-v27-close'); closeButton.innerHTML = PHOSPHOR_X;",
 "const avatar = el('span','tts-v27-avatar',voice.name.trim().slice(0,1).toUpperCase());"=>"const avatar = el('span','tts-v27-avatar'); avatar.innerHTML = PHOSPHOR_WAVEFORM;",
 "const selected = el('span','tts-v27-check','✓');" => "const selected = el('span','tts-v27-check'); selected.innerHTML = PHOSPHOR_CHECK;",
 "const icon = el('span','tts-v27-open-icon','♫'); icon.setAttribute('aria-hidden','true');" => "const icon = el('span','tts-v27-open-icon'); icon.innerHTML = PHOSPHOR_WAVEFORM; icon.setAttribute('aria-hidden','true');",
 "const arrow = el('span','tts-v27-open-chevron','⌄'); arrow.setAttribute('aria-hidden','true');"=>"const arrow = el('span','tts-v27-open-chevron'); arrow.innerHTML = PHOSPHOR_CARET; arrow.setAttribute('aria-hidden','true');",
 '.tts-page .tts-v27-open-chevron{font-size:21px;color:#76638e;margin-left:auto}'=>'.tts-page .tts-v27-open-chevron{color:#76638e;margin-left:auto;display:grid;place-items:center}',
 '#tts-v27-modal-root .tts-v27-close:hover{background:#f6f2ff}'=>"#tts-v27-modal-root .tts-v27-close:hover{background:#f6f2ff}\n#tts-v27-modal-root .tts-v27-close svg{width:19px;height:19px}",
 '#tts-v27-modal-root .tts-v27-avatar{flex:none;background:#eee9ff;color:#7454ce;border-radius:12px;width:39px;height:39px;display:grid;place-items:center;font-size:17px;font-weight:800}'=>'#tts-v27-modal-root .tts-v27-avatar{flex:none;background:#eee9ff;color:#7454ce;border-radius:12px;width:39px;height:39px;display:grid;place-items:center}',
 '#tts-v27-modal-root .tts-v27-check{font-size:17px;font-weight:800;color:#6c4ed2}'=>'#tts-v27-modal-root .tts-v27-check{display:grid;place-items:center;color:#6c4ed2}',
 '#tts-v27-modal-root .tts-v27-empty{color:#73798b;padding:25px;text-align:center}'=>"#tts-v27-modal-root .tts-v27-empty{color:#73798b;padding:25px;text-align:center}\n#tts-v27-modal-root .tts-v27-check svg{width:20px;height:20px}\n#tts-v27-modal-root .tts-v27-preview svg,.tts-page .tts-v27-open-icon svg,#tts-v27-modal-root .tts-v27-avatar svg{width:20px;height:20px;display:block}"
];
foreach($repl as $old=>$new){$b=replaceOnce($b,$old,$new);}
if(hash('sha256',$a)!=='0cfb29a794cca49f56ad5e5c69bad4fe0a5bb27c87ae1f9834df057793264282'||hash('sha256',$b)!=='ce67574f8dc5c92674223b382a5a2bc1b05f7a5fe27ba1bb127727c423511000'){echo "FAIL:NEW_HASH ".hash('sha256',$a).' '.hash('sha256',$b)."\n";exit(9);}
$oldTags=['<script defer src="/tools/text-to-speech/tts-v26-voice-details.js?v=20261121"></script>','<script defer src="/tools/text-to-speech/tts-v27-voice-picker.js?v=20261121"></script>'];
$newTags=['<script defer src="/tools/text-to-speech/tts-v28-voice-details.js?v=20261121"></script>','<script defer src="/tools/text-to-speech/tts-v28-voice-picker.js?v=20261121"></script>'];
if(str_contains($html,$newTags[0])&&str_contains($html,$newTags[1])){echo "TTS_V28_ALREADY_INSTALLED\n";exit(0);}
foreach($oldTags as $tag){if(substr_count($html,$tag)!==1){echo "FAIL:HTML_TAG\n";exit(10);}}
$backup=getenv('TTS_V28_TEST_ROOT')?$root.'/../tts-v28-before-index.html':'/home/u342033576/domains/motricialy.com/tts-v28-before-index-20261121.html';
if(!is_file($backup)&&!copy($index,$backup)){echo "FAIL:BACKUP\n";exit(11);}
foreach([['tts-v28-voice-details.js',$a],['tts-v28-voice-picker.js',$b]] as $item){
 [$filename,$data]=$item;$dst=$root.'/'.$filename;$tmp=$dst.'.'.getmypid().'.tmp';
 if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data)||!rename($tmp,$dst)||hash('sha256',(string)file_get_contents($dst))!==hash('sha256',$data)){echo "FAIL:ASSET\n";exit(12);}
}
$next=str_replace($oldTags,$newTags,$html);
foreach($newTags as $tag){if(substr_count($next,$tag)!==1){echo "FAIL:NEW_TAG\n";exit(13);}}
$tmp=$root.'/.index-v28-'.getmypid().'.tmp';
if(file_put_contents($tmp,$next,LOCK_EX)!==strlen($next)||!rename($tmp,$index)){echo "FAIL:HTML\n";exit(14);}
echo "TTS_V28_DEPLOY_OK 6_MALE_4_FEMALE PHOSPHOR_ICONS BACKUP=YES\n";
