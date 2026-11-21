(()=>{'use strict';
const root=document.getElementById('a2t-tool');if(!root)return;
const $=id=>document.getElementById(id);
const fileInput=$('audio-file'),form=$('upload-form'),drop=$('drop-zone'),summary=$('file-summary');
const editor=$('transcript'),status=$('status-line'),editorStatus=$('editor-status');
const btn=$('transcribe-btn'),label=$('transcribe-label'),preview=$('audio-preview'),player=$('audio-player');
const recordPanel=$('recording-panel'),recordStart=$('record-start'),recordStop=$('record-stop'),recordDiscard=$('record-discard');
const quota=$('quota-note'),capacity=$('shared-capacity'),tools=$('ai-tools');
const originalTab=$('tab-original'),cleanTab=$('tab-clean'),resetBtn=$('reset-original');
const summaryPanel=$('summary-panel'),summaryText=$('summary-text'),cleanBtn=$('clean-btn'),summaryBtn=$('summary-btn');
const maxBytes=24000000,extensions=new Set(['mp3','wav','m4a','flac','ogg','mp4','webm']);
let selected=null,objectUrl='',ready=false,busy=false,textReady=false,textBusy=false,filesLeft=null,textLeft=null;
let sourceText='',rawText='',cleanText='',activeVersion='raw',summaryValue='';
let recordStream=null,audioContext=null,microSource=null,microProcessor=null,microGain=null,pcmChunks=[],pcmSamples=0,recordTimer=null,recordStarted=0,recording=false,discarding=false,resamplePosition=0;
const base=location.pathname.replace(/index\.html$/i,'').replace(/\/?$/,'/');
const api=base+'api/';
function statusAt(el,s,type=''){el.hidden=!s;el.textContent=s||'';el.className='status-line'+(el===editorStatus?' editor-status':'')+(type?' '+type:'');}
function message(s,type){statusAt(status,s,type);}
function editMessage(s,type){statusAt(editorStatus,s,type);}
function fmtMB(bytes){return (bytes/1048576).toFixed(2)+' MB';}
function updateButtons(){
 btn.disabled=!ready||!selected||busy||filesLeft===0;
 label.textContent=busy?'Transcribing…':'Transcribe audio';
 btn.setAttribute('aria-busy',String(busy));
 const canText=ready&&textReady&&!busy&&!textBusy&&textLeft!==0&&editor.value.trim().length>0;
 cleanBtn.disabled=!canText;
 summaryBtn.disabled=!canText;
 cleanBtn.textContent=textBusy&&cleanBtn.dataset.running==='true'?'Cleaning…':'Make readable';
 summaryBtn.textContent=textBusy&&summaryBtn.dataset.running==='true'?'Summarizing…':'Summary & key points';
 resetBtn.hidden=!sourceText||activeVersion!=='raw'||editor.value===sourceText;
}
function wordCount(){
 const val=editor.value.trim();
 const count=val?val.split(/\s+/u).length:0;
 $('word-count').textContent=count+' '+(count===1?'word':'words');
}
function dismissSummary(){
 summaryValue='';summaryText.value='';summaryPanel.hidden=true;
}
function updateVersions(){
 originalTab.classList.toggle('active',activeVersion==='raw');
 cleanTab.classList.toggle('active',activeVersion==='clean');
 originalTab.setAttribute('aria-selected',String(activeVersion==='raw'));
 cleanTab.setAttribute('aria-selected',String(activeVersion==='clean'));
 cleanTab.disabled=!cleanText;
 $('transcript-version-note').textContent=activeVersion==='raw'?'Editable transcript':'AI-cleaned version · review against the original';
 updateButtons();
}
function showVersion(which){
 if(which==='clean'&&!cleanText)return;
 if(activeVersion==='raw')rawText=editor.value;else cleanText=editor.value;
 activeVersion=which;
 editor.value=which==='raw'?rawText:cleanText;
 wordCount();editMessage('');updateVersions();
}
originalTab.addEventListener('click',()=>showVersion('raw'));
cleanTab.addEventListener('click',()=>showVersion('clean'));
resetBtn.addEventListener('click',()=>{
 if(!sourceText)return;
 rawText=sourceText;editor.value=sourceText;dismissSummary();wordCount();editMessage('Original transcription restored.','success');updateVersions();
});
editor.addEventListener('input',()=>{
 if(activeVersion==='raw')rawText=editor.value;else cleanText=editor.value;
 dismissSummary();wordCount();editMessage('');updateButtons();
});
wordCount();updateVersions();
function disposePreview(){
 player.pause();player.removeAttribute('src');player.load();
 if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl='';}
 preview.hidden=true;
}
function showPreview(file){
 disposePreview();
 try{
  objectUrl=URL.createObjectURL(file);
  player.src=objectUrl;
  $('playback-speed').value='1';player.playbackRate=1;
  preview.hidden=false;
 }catch(e){disposePreview();}
}
$('playback-speed').addEventListener('change',e=>{
 const rate=Number(e.target.value);
 player.playbackRate=Number.isFinite(rate)&&rate>=.5&&rate<=2?rate:1;
});
function choose(file){
 if(!file)return false;
 const ext=(file.name.split('.').pop()||'').toLowerCase();
 if(!extensions.has(ext)){message('Unsupported file type. Use MP3, WAV, M4A, FLAC, OGG, MP4 or completed WebM.','error');return false;}
 if(file.size>maxBytes){message('File exceeds 24 MB. Choose a smaller file.','error');return false;}
 if(file.size<128){message('This file is too small to contain valid audio.','error');return false;}
 selected=file;
 $('file-name').textContent=file.name;
 $('file-size').textContent=fmtMB(file.size);
 summary.hidden=false;
 $('drop-title').textContent='Change audio file';
 showPreview(file);
 message('');updateButtons();
 return true;
}
function clearFile(){
 selected=null;fileInput.value='';summary.hidden=true;disposePreview();
 $('drop-title').textContent='Choose an audio file';
 if(!recordStream)$('record-status').textContent='';
 message('');updateButtons();
}
$('clear-file').addEventListener('click',clearFile);
window.addEventListener('a2t:prepared-audio',event=>{const file=event.detail?.file;if(file instanceof File)choose(file);});
drop.addEventListener('click',()=>fileInput.click());
drop.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();fileInput.click();}});
fileInput.addEventListener('change',()=>{if(fileInput.files&&fileInput.files[0])choose(fileInput.files[0]);});
['dragenter','dragover'].forEach(n=>drop.addEventListener(n,e=>{e.preventDefault();drop.classList.add('dragging');}));
['dragleave','dragend','drop'].forEach(n=>drop.addEventListener(n,e=>{e.preventDefault();drop.classList.remove('dragging');}));
drop.addEventListener('drop',e=>{const f=e.dataTransfer?.files?.[0];if(f)choose(f);});
player.addEventListener('loadedmetadata',()=>{
 const sec=player.duration;
 if(Number.isFinite(sec)&&sec>900){
  message('Maximum length is 15 minutes. Choose a shorter recording.','error');
  selected=null;updateButtons();
 }
});
const recordingRate=12000;
const recordingMaxSamples=recordingRate*900;
function stopStream(){
 if(recordTimer){clearInterval(recordTimer);recordTimer=null;}
 if(microProcessor){try{microProcessor.onaudioprocess=null;microProcessor.disconnect();}catch(e){}microProcessor=null;}
 if(microSource){try{microSource.disconnect();}catch(e){}microSource=null;}
 if(microGain){try{microGain.disconnect();}catch(e){}microGain=null;}
 if(audioContext){try{audioContext.close();}catch(e){}audioContext=null;}
 if(recordStream){recordStream.getTracks().forEach(track=>{try{track.stop();}catch(e){}});recordStream=null;}
}
function timeLabel(ms){
 const s=Math.floor(Math.max(0,ms)/1000);
 return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0');
}
function recordingUi(active){
 recordStart.disabled=active;
 recordStop.disabled=!active;
 recordDiscard.disabled=!selected&&!active;
}
function makeWav(){
 const dataBytes=pcmSamples*2;
 const data=new ArrayBuffer(44+dataBytes);
 const view=new DataView(data);
 function word(offset,value){view.setUint16(offset,value,true);}
 function dword(offset,value){view.setUint32(offset,value,true);}
 function chunk(offset,value){for(let i=0;i<4;i++)view.setUint8(offset+i,value.charCodeAt(i));}
 chunk(0,'RIFF');dword(4,36+dataBytes);chunk(8,'WAVE');chunk(12,'fmt ');dword(16,16);
 word(20,1);word(22,1);dword(24,recordingRate);dword(28,recordingRate*2);
 word(32,2);word(34,16);chunk(36,'data');dword(40,dataBytes);
 let pos=44;
 for(const arr of pcmChunks){for(let i=0;i<arr.length;i++,pos+=2)view.setInt16(pos,arr[i],true);}
 return new File([data],'recording.wav',{type:'audio/wav',lastModified:Date.now()});
}
function stopRecording(discard=false){
 if(!recording){stopStream();if(discard){clearFile();$('record-timer').textContent='00:00';$('record-status').textContent='';recordDiscard.disabled=true;}return;}
 recording=false;discarding=discard;
 stopStream();
 recordingUi(false);
 if(discard){pcmChunks=[];pcmSamples=0;clearFile();$('record-timer').textContent='00:00';$('record-status').textContent='';return;}
 if(pcmSamples<recordingRate){
  pcmChunks=[];pcmSamples=0;$('record-status').textContent='Recording was too short. Try again.';return;
 }
 try{
  const wav=makeWav();
  pcmChunks=[];pcmSamples=0;
  if(choose(wav)){
   recordDiscard.disabled=false;
   $('record-status').textContent='Recording ready. Listen before transcribing.';
  }
 }catch(e){
  pcmChunks=[];pcmSamples=0;
  $('record-status').textContent='Could not prepare the recording. Please try again.';
 }
}
recordStart.addEventListener('click',async()=>{
 if(recording)return;
 const AudioEngine=window.AudioContext||window.webkitAudioContext;
 if(!navigator.mediaDevices?.getUserMedia||!AudioEngine){
  $('record-status').textContent='Microphone recording is unavailable in this browser. Upload an audio file instead.';return;
 }
 $('record-status').textContent='Requesting microphone access…';
 try{
  recordStream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true},video:false});
  try{audioContext=new AudioEngine({latencyHint:'interactive'});}catch(e){audioContext=new AudioEngine();}
  if(!audioContext.createScriptProcessor)throw new Error('Capture unsupported');
  await audioContext.resume();
  microSource=audioContext.createMediaStreamSource(recordStream);
  microProcessor=audioContext.createScriptProcessor(4096,1,1);
  microGain=audioContext.createGain();microGain.gain.value=0;
  pcmChunks=[];pcmSamples=0;resamplePosition=0;discarding=false;
  const step=audioContext.sampleRate/recordingRate;
  if(!Number.isFinite(step)||step<1)throw new Error('Invalid audio sample rate');
  microProcessor.onaudioprocess=e=>{
   if(!recording||discarding)return;
   const input=e.inputBuffer.getChannelData(0);
   const cap=Math.ceil(input.length/step)+2;
   const out=new Int16Array(cap);
   let count=0;
   let pos=resamplePosition;
   while(pos<input.length&&pcmSamples+count<recordingMaxSamples){
    const start=Math.max(0,Math.floor(pos));
    const finish=Math.min(input.length,Math.max(start+1,Math.ceil(pos+step)));
    let sum=0;for(let i=start;i<finish;i++)sum+=input[i];
    const level=Math.max(-1,Math.min(1,sum/(finish-start)));
    out[count++]=Math.round(level<0?level*32768:level*32767);
    pos+=step;
   }
   resamplePosition=pos-input.length;
   if(count>0){pcmChunks.push(out.subarray(0,count));pcmSamples+=count;}
   if(pcmSamples>=recordingMaxSamples){
    stopRecording(false);$('record-status').textContent='Maximum recording length reached (15 minutes).';
   }
  };
  microSource.connect(microProcessor);
  microProcessor.connect(microGain);
  microGain.connect(audioContext.destination);
  recording=true;
  recordStarted=Date.now();
  $('record-timer').textContent='00:00';
  $('record-status').textContent='Recording…';
  recordingUi(true);
  recordTimer=setInterval(()=>{
   const ms=Date.now()-recordStarted;
   $('record-timer').textContent=timeLabel(ms);
   if(ms>=900000){stopRecording(false);$('record-status').textContent='Maximum recording length reached (15 minutes).';}
  },500);
 }catch(e){
  recording=false;stopStream();recordingUi(false);
  $('record-status').textContent=(e?.name==='NotAllowedError')?
   'Microphone permission was denied. You can upload an audio file instead.':
   'Recording is not supported on this device. Upload an audio file instead.';
 }
});
recordStop.addEventListener('click',()=>stopRecording(false));
recordDiscard.addEventListener('click',()=>stopRecording(true));
window.addEventListener('pagehide',()=>{
 recording=false;stopStream();disposePreview();pcmChunks=[];pcmSamples=0;
},{once:true});
function download(data,filename,mime){
 const blob=new Blob([data],{type:mime});const u=URL.createObjectURL(blob);
 const a=document.createElement('a');a.href=u;a.download=filename;a.hidden=true;
 document.body.appendChild(a);a.click();a.remove();
 setTimeout(()=>URL.revokeObjectURL(u),30000);
}
function editedText(){return editor.value.trim();}
async function jsonReply(resp){const raw=await resp.text();try{return JSON.parse(raw);}catch(e){return {detail:'Unexpected response. Try again later.'};}}
function friendlyError(code,detail){
 if(code===429)return 'Daily allowance or shared processing budget reached.';
 if(code===413)return 'File or text exceeds the allowed size.';
 if(code===422)return typeof detail==='string'?detail:'This file could not be processed.';
 if(code===403)return 'Request blocked. Please reload this page.';
 if(code===503)return 'Processing is temporarily unavailable.';
 return 'Processing failed. Please try again later.';
}
async function refreshQuota(){
 try{
  const resp=await fetch(api+'quota',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
  if(!resp.ok)return;
  const d=await jsonReply(resp);
  if(typeof d.files_remaining==='number'&&typeof d.minutes_remaining==='number'&&typeof d.text_operations_remaining==='number'){
   filesLeft=d.files_remaining;textLeft=d.text_operations_remaining;
   quota.textContent='Today: '+filesLeft+' file'+(filesLeft===1?'':'s')+' / '+d.minutes_remaining.toFixed(1)+' min left · AI actions: '+textLeft;
  }
 }catch(e){/* Quota display is best-effort; server-side enforcement remains authoritative. */}
 updateButtons();
}
async function checkAvailability(){
 try{
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),10000);
  let resp;
  try{resp=await fetch(api+'config',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},signal:controller.signal});}
  finally{clearTimeout(timer);}
  const data=await jsonReply(resp);
  ready=resp.ok&&data.live_api_ready===true&&data.ai_paused===false;
  if(ready){
   message('');
   const budget=data.budget?.public_remaining_estimated_usd;
   if(typeof budget==='number'&&budget>=0){
    capacity.textContent='Shared service budget this cycle: approximately $'+budget.toFixed(2)+' remaining.';
    capacity.hidden=false;
   }
  }else message('Transcription is temporarily unavailable.','error');
  quota.textContent='Free: up to 2 files / 15 minutes each day. Each file: max 24 MB.';
  if(ready){await checkTextTools();await refreshQuota();}
 }catch(e){ready=false;message('Transcription is temporarily unavailable.','error');}
 updateButtons();
}
async function checkTextTools(){
 try{
  const resp=await fetch(api+'capabilities',{cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});
  const data=await jsonReply(resp);
  textReady=resp.ok&&data.text_processing_ready===true;
 }catch(e){textReady=false;}
 tools.hidden=!textReady;
 updateButtons();
}
form.addEventListener('submit',async e=>{
 e.preventDefault();if(!ready||!selected||busy)return;
 busy=true;updateButtons();message('Processing audio. Keep this tab open.');
 const formData=new FormData();
 formData.append('file',selected,selected.name);
 formData.append('language',$('language').value);
 formData.append('keywords',$('keywords').value.trim());
 formData.append('context',$('context').value.trim());
 try{
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),175000);
  let resp;
  try{
   resp=await fetch(api+'transcribe',{method:'POST',body:formData,cache:'no-store',credentials:'same-origin',
    headers:{Accept:'application/json'},signal:controller.signal});
  }finally{clearTimeout(timer);}
  const result=await jsonReply(resp);
  if(!resp.ok)throw {status:resp.status,detail:result.detail||result.error};
  if(typeof result.text!=='string'||!result.text.trim())throw {status:502};
  sourceText=result.text;rawText=result.text;cleanText='';activeVersion='raw';
  editor.value=rawText;dismissSummary();wordCount();updateVersions();
  message('Transcript ready.','success');
  await refreshQuota();
  editor.focus({preventScroll:true});
 }catch(err){
  if(err?.name==='AbortError')message('Request timed out. The server may still be processing; do not retry immediately.','error');
  else message(friendlyError(err?.status,err?.detail),'error');
 }finally{busy=false;updateButtons();}
});
async function processText(mode,button){
 if(!ready||!textReady||textBusy||busy)return;
 const source=editedText();
 if(!source){editMessage('Enter transcript text first.','error');return;}
 if(source.length>12000){editMessage('AI text tools support up to 12,000 characters per request.','error');return;}
 textBusy=true;
 cleanBtn.dataset.running=String(mode==='clean');summaryBtn.dataset.running=String(mode==='summary');
 updateButtons();editMessage(mode==='clean'?'Preparing clean version…':'Preparing summary and action items…');
 try{
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),70000);
  let resp;
  try{resp=await fetch(api+'process',{method:'POST',credentials:'same-origin',cache:'no-store',
   headers:{'Content-Type':'application/json','Accept':'application/json'},
   body:JSON.stringify({text:source,mode:mode}),signal:controller.signal});}
  finally{clearTimeout(timer);}
  const data=await jsonReply(resp);
  if(!resp.ok)throw {status:resp.status,detail:data.detail};
  if(typeof data.text!=='string'||!data.text.trim())throw {status:502};
  if(mode==='clean'){
   if(activeVersion==='raw')rawText=editor.value;else cleanText=editor.value;
   cleanText=data.text;activeVersion='clean';editor.value=cleanText;
   wordCount();updateVersions();editMessage('Clean version ready. Compare it with the transcript.','success');
  }else{
   summaryValue=data.text;summaryText.value=summaryValue;summaryPanel.hidden=false;
   editMessage('Summary ready. Verify details against the recording.','success');
  }
 }catch(err){
  if(err?.name==='AbortError')editMessage('Processing timed out. Do not retry immediately.','error');
  else editMessage(friendlyError(err?.status,err?.detail),'error');
 }finally{textBusy=false;cleanBtn.dataset.running='false';summaryBtn.dataset.running='false';await refreshQuota();updateButtons();}
}
cleanBtn.addEventListener('click',()=>processText('clean',cleanBtn));
summaryBtn.addEventListener('click',()=>processText('summary',summaryBtn));
$('summary-copy').addEventListener('click',async()=>{
 if(!summaryText.value)return;
 try{await navigator.clipboard.writeText(summaryText.value);editMessage('Summary copied.','success');}
 catch(e){summaryText.select();editMessage('Select and copy the summary.');}
});
$('copy-btn').addEventListener('click',async()=>{
 const val=editedText();if(!val){editMessage('Enter text first.','error');return;}
 try{await navigator.clipboard.writeText(editor.value);editMessage('Copied.','success');}
 catch(e){editor.select();editMessage('Select and copy the text.');}
});
$('txt-btn').addEventListener('click',()=>{
 if(!editedText()){editMessage('Enter text first.','error');return;}
 download(editor.value,'motricialy-audio-transcript.txt','text/plain;charset=utf-8');
 editMessage('TXT download started.','success');
});
$('md-btn').addEventListener('click',()=>{
 if(!editedText()){editMessage('Enter text first.','error');return;}
 const title=activeVersion==='clean'?'Clean audio transcript':'Audio transcript';
 download('# '+title+'\n\n'+editor.value.trimEnd()+'\n','motricialy-audio-transcript.md','text/markdown;charset=utf-8');
 editMessage('Markdown download started.','success');
});
$('docx-btn').addEventListener('click',async()=>{
 if(!editedText()){editMessage('Enter text first.','error');return;}
 if(new TextEncoder().encode(editor.value).length>80000){editMessage('DOCX limit: 80 KB of text.','error');return;}
 try{
  editMessage('Preparing DOCX…');
  const resp=await fetch(api+'export/docx',{method:'POST',credentials:'same-origin',cache:'no-store',
   headers:{'Content-Type':'application/json','Accept':'application/vnd.openxmlformats-officedocument.wordprocessingml.document'},
   body:JSON.stringify({title:activeVersion==='clean'?'Clean Audio Transcript':'Audio Transcript',text:editor.value})});
  if(!resp.ok){const data=await jsonReply(resp);throw {status:resp.status,detail:data.detail};}
  const blob=await resp.blob();if(blob.size<300)throw {status:503};
  download(blob,'motricialy-audio-transcript.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document');
  editMessage('DOCX download started.','success');
 }catch(e){editMessage(friendlyError(e?.status,e?.detail),'error');}
});
checkAvailability();
})();