<?php
declare(strict_types=1);
/* Motricialy TTS v2.16: correct focused seek-thumb sync and render genuine status progress.
   CLI-only, front-end only; API, limits, data and media unchanged. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$index=$root.'/index.html';
$paths=['player'=>$root.'/tts-v215-seekable-player.js','status'=>$root.'/tts-v210-result-status.js','bundle'=>$root.'/assets/index-tts-v29.js','html'=>$index];
$oldHashes=['player'=>'d02f8035bd49ad2531691c15727483c688112f21b0012c3d0b0beec4ded7e236','status'=>'ed61916b4c664a9dc9c53b4cc44cd50391e9805f538c67b727e44c95e66ff927','bundle'=>'93f761506869d8abf04bfb18c2a196016969b1650be13a86f4bc64a412f6b161'];
$newHashes=['player'=>'8bf978e98bcad4fa731c30a51b8df2387ce596269e26738c70fa04dc5788b9c7','status'=>'5fcbfb047a13d296c1399be34a6e7748e3a9b8afe7fc80634a829e5b57aaf835','bundle'=>'b4cb02b7e1e0b843d0241ef3007b85974d9a832eb902c3ffe9ac966f74f75576'];
function fail216(string $m):never{echo "TTS_V216_FAIL_".$m."\n";exit(22);}
function replace216(string $s,string $old,string $new,string $label):string{
 if(substr_count($s,$old)!==1)fail216('PATCH_'.$label);
 return str_replace($old,$new,$s);
}
$old=[];
foreach($paths as $key=>$path){
 $d=@file_get_contents($path);
 if(!is_string($d))fail216('MISSING_'.$key);
 $old[$key]=$d;
 if(isset($oldHashes[$key])&&hash('sha256',$d)!==$oldHashes[$key])fail216('BASELINE_'.$key);
}
$player=str_replace('tts-v215','tts-v216',$old['player']);
$player=replace216($player,'TTS v2.15','TTS v2.16','comment-player');
$player=str_replace('__motricialyTtsV215Players','__motricialyTtsV216Players',$player,$np);
if($np!==2)fail216('GUARD_PLAYER');
$player=replace216($player,'if(document.activeElement!==seek&&d){seek.value=String(Math.round(current/d*1000));}','if(d&&!controller.isPointerDragging){seek.value=String(Math.round(current/d*1000));}','focus-freeze');
$player=replace216($player,
 'const current=Math.min(d,Math.max(0,Number(audio.currentTime)||0));',
 'const current=controller.isPointerDragging&&d ? d*Math.max(0,Math.min(1,Number(seek.value)/1000)) : Math.min(d,Math.max(0,Number(audio.currentTime)||0));',
 'drag-preview');
$player=replace216($player,
 "seek.addEventListener('input',doSeek);seek.addEventListener('change',doSeek);",
 "seek.addEventListener('pointerdown',()=>{controller.isPointerDragging=true;});\n    const endDrag=()=>{if(controller.isPointerDragging){controller.isPointerDragging=false;paint();}};\n    seek.addEventListener('pointerup',endDrag);seek.addEventListener('pointercancel',endDrag);\n    seek.addEventListener('blur',endDrag);\n    seek.addEventListener('input',doSeek);seek.addEventListener('change',doSeek);",
 'pointer-events');
$status=str_replace('tts-v210','tts-v216',$old['status']);
$status=replace216($status,'v2.10','v2.16','status-comment');
$status=str_replace('__motricialyTtsV210ResultStatus','__motricialyTtsV216ResultStatus',$status,$ns);
if($ns!==2)fail216('GUARD_STATUS');
$status=replace216($status,
 "[/Generating your speech/i,'Generating your speech…','Your voiceover is being created. Please keep this tab open.'],",
 "[/(?:Generating your speech|Creating your voiceover)/i,'Generating your speech…','Your voiceover is being created. Please keep this tab open.'],",
 'status-generating');
$status=replace216($status,
 "const section=document.querySelector('.tts-page #tts-audio-result.studio-output');",
 "const section=document.querySelector('.tts-page section.studio-output.output');",
 'status-selector');
$status=replace216($status,
 "const statusText=section.querySelector('.notice')?.textContent?.trim()||'';",
 "const statusText=[section.querySelector('.notice')?.textContent?.trim()||'', section.querySelector('.output-empty')?.textContent?.trim()||''].join(' ');",
 'status-text');
$bundle=replace216($old['bundle'],
 'P||Z.length>0||U||Nl||k&&!nt?r.jsxs("section",{className:"studio-output output"',
 'P||Z.length>0||U||Nl||N||k&&!nt?r.jsxs("section",{className:"studio-output output"',
 'react-loading-output');
foreach(['player'=>$player,'status'=>$status,'bundle'=>$bundle] as $key=>$d){
 if(hash('sha256',$d)!==$newHashes[$key])fail216('PATCH_SHA_'.$key);
}
$tagsOld=[
 '<script defer src="/tools/text-to-speech/tts-v210-result-status.js?v=20261121"></script>',
 '<script defer src="/tools/text-to-speech/tts-v215-seekable-player.js?v=20261121b"></script>',
 '/tools/text-to-speech/assets/index-tts-v29.js'];
$tagsNew=[
 '<script defer src="/tools/text-to-speech/tts-v216-result-status.js?v=20261121"></script>',
 '<script defer src="/tools/text-to-speech/tts-v216-players.js?v=20261121"></script>',
 '/tools/text-to-speech/assets/index-tts-v216.js'];
$html=$old['html'];
foreach($tagsOld as $i=>$oldTag){$html=replace216($html,$oldTag,$tagsNew[$i],'html-'.$i);}
if(!str_contains($html,'tts-v28-voice-picker.js')||!str_contains($html,'motricialy-tracking.js'))fail216('HTML_BASELINE');
if(in_array('--check',$argv,true)){
 echo "TTS_V216_DRY_RUN_PASS STATUS=CORRECT_SELECTOR REACT_LOADING=RENDERED PLAYHEAD=SYNC FRONTEND_ONLY\n";exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE: --check | --apply\n";exit(2);}
$backup='/home/u342033576/domains/motricialy.com/tts-v216-before-index-20261121.html';
if(!is_file($backup)&&!copy($index,$backup))fail216('BACKUP');
function atomic216(string $target,string $data):void{
 $tmp=dirname($target).'/.tts-v216-'.basename($target).'-'.getmypid().'.tmp';
 if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data)||!rename($tmp,$target)||hash_file('sha256',$target)!==hash('sha256',$data))fail216('WRITE_'.basename($target));
}
atomic216($root.'/tts-v216-players.js',$player);
atomic216($root.'/tts-v216-result-status.js',$status);
atomic216($root.'/assets/index-tts-v216.js',$bundle);
atomic216($index,$html);
$read=(string)file_get_contents($index);
foreach($tagsNew as $tag)if(substr_count($read,$tag)!==1)fail216('VERIFY_INDEX');
echo "TTS_V216_DEPLOY_OK PLAYHEAD_SYNCS=YES LOADING_BAR=VISIBLE READY_TEXT=GREEN OLD_SCRIPTS_REPLACED=YES BACKUP=YES\n";
