(()=>{'use strict';
const root=document.getElementById('a2t-tool');
if(!root)return;
const $=id=>document.getElementById(id);
const form=$('upload-form'),fileInput=$('audio-file'),drop=$('drop-zone'),clear=$('clear-file'),summary=$('file-summary'),editor=$('transcript');
const status=$('status-line'),badge=$('service-badge'),btn=$('transcribe-btn'),label=$('transcribe-label');
const maxBytes=24000000,extensions=new Set(['mp3','wav','m4a','flac','ogg','mp4','webm']);
let selected=null,ready=false,running=false;
const relativePath=location.pathname.replace(/index\.html$/i,'').replace(/\/?$/,'/');
const api=relativePath+'api/';
function message(s,type){status.textContent=s;status.className='status-line'+(type?' '+type:'');}
function updateButton(){btn.disabled=!ready||!selected||running;label.textContent=running?'Transcribing audio...':ready?'Transcribe audio':'Transcription unavailable';}
function readable(bytes){return (bytes/1048576).toFixed(2)+' MB';}
function choose(file){
 if(!file)return;
 const ext=(file.name.split('.').pop()||'').toLowerCase();
 if(!extensions.has(ext)){message('Unsupported file type. Choose MP3, WAV, M4A, FLAC, OGG, MP4 or completed WebM.','error');return;}
 if(file.size>maxBytes){message('File exceeds the 24 MB limit. Please use a smaller file.','error');return;}
 if(file.size<128){message('This file is too small to be a valid audio recording.','error');return;}
 selected=file;
 $('file-name').textContent=file.name;$('file-size').textContent=readable(file.size);summary.hidden=false;
 $('drop-title').textContent='Change selected file';message('File selected. Ready to transcribe when the service is available.');updateButton();
}
drop.addEventListener('click',()=>fileInput.click());
drop.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();fileInput.click();}});
fileInput.addEventListener('change',()=>choose(fileInput.files&&fileInput.files[0]));
clear.addEventListener('click',()=>{selected=null;fileInput.value='';summary.hidden=true;$('drop-title').textContent='Drop your audio file here';message('File removed.');updateButton();});
['dragenter','dragover'].forEach(name=>drop.addEventListener(name,e=>{e.preventDefault();drop.classList.add('dragging');}));
['dragleave','dragend','drop'].forEach(name=>drop.addEventListener(name,e=>{e.preventDefault();drop.classList.remove('dragging');}));
drop.addEventListener('drop',e=>choose(e.dataTransfer&&e.dataTransfer.files&&e.dataTransfer.files[0]));
function words(){const s=editor.value.trim();$('word-count').textContent=(s?s.split(/\s+/u).length:0)+' words';}
editor.addEventListener('input',words);words();
async function responseJSON(resp){const raw=await resp.text();try{return JSON.parse(raw);}catch(e){return {detail:'Unexpected server response. Please try again later.'};}}
function humanError(code,detail){if(code===429)return 'Daily allowance or shared budget reached. Please try another day.';if(code===413)return 'File is too large. Please use a recording under 24 MB.';if(code===422)return (typeof detail==='string'?detail:'Could not verify this audio file.');if(code===403)return 'Request blocked by the service safety policy.';if(code===503)return 'Transcription is temporarily unavailable. The editor still works.';return 'Transcription could not finish. Please try again later.';}
async function check(){
 try{
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),10000);
  const resp=await fetch(api+'config',{credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{'Accept':'application/json'}});
  clearTimeout(timeout);
  const data=await responseJSON(resp);
  ready=resp.ok&&data.live_api_ready===true&&data.ai_paused===false;
  badge.textContent=ready?'Service available':'Temporarily unavailable';badge.className='status-chip '+(ready?'ready':'offline');
  message(ready?'Ready. Select a completed audio file to begin.':'Transcription is currently unavailable. You can still paste, edit and export text.',ready?'success':'');
 }catch(e){ready=false;badge.textContent='Temporarily unavailable';badge.className='status-chip offline';message('Cannot reach the transcription service. The text editor is still available.','error');}
 updateButton();
}
form.addEventListener('submit',async e=>{
 e.preventDefault();if(!ready||!selected||running)return;
 running=true;updateButton();message('Transcribing your recording. Keep this tab open...');
 const payload=new FormData();payload.append('file',selected,selected.name);payload.append('language',$('language').value);
 payload.append('keywords',$('keywords').value.trim());payload.append('context',$('context').value.trim());
 try{
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),175000);
  const resp=await fetch(api+'transcribe',{method:'POST',body:payload,credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{'Accept':'application/json'}});
  clearTimeout(timer);
  const data=await responseJSON(resp);
  if(!resp.ok)throw {code:resp.status,detail:data.detail||data.error};
  if(typeof data.text!=='string'||!data.text.trim())throw {code:502,detail:'Empty transcript'};
  editor.value=data.text;words();message('Transcript ready. Review the text, then copy or download it.','success');
  editor.focus({preventScroll:true});
 }catch(err){
  if(err&&err.name==='AbortError')message('Request timed out. Do not retry immediately; the server may still be processing it.','error');
  else message(humanError(err&&err.code,err&&err.detail),'error');
 }finally{running=false;updateButton();}
});
function download(data,name,type){const blob=new Blob([data],{type:type});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=name;a.hidden=true;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);}
$('copy-btn').addEventListener('click',async()=>{if(!editor.value.trim()){message('Nothing to copy yet.','error');return;}try{await navigator.clipboard.writeText(editor.value);message('Text copied to clipboard.','success');}catch(e){editor.select();message('Please use your browser copy command.');}});
$('txt-btn').addEventListener('click',()=>{if(!editor.value.trim()){message('There is no transcript to export yet.','error');return;}download(editor.value,'motricialy-audio-transcript.txt','text/plain;charset=utf-8');message('TXT download prepared.','success');});
$('docx-btn').addEventListener('click',async()=>{if(!editor.value.trim()){message('Enter transcript text before exporting.','error');return;}
 if(new TextEncoder().encode(editor.value).length>80000){message('DOCX export supports up to 80 KB of transcript text.','error');return;}
 try{message('Preparing Word document...');const resp=await fetch(api+'export/docx',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','Accept':'application/vnd.openxmlformats-officedocument.wordprocessingml.document'},body:JSON.stringify({title:'Audio Transcript',text:editor.value})});
 if(!resp.ok){const d=await responseJSON(resp);throw {code:resp.status,detail:d.detail};}
 const blob=await resp.blob();if(blob.size<300)throw {code:503};download(blob,'motricialy-audio-transcript.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document');message('DOCX download prepared.','success');
 }catch(e){message(humanError(e&&e.code,e&&e.detail),'error');}});
check();
})();