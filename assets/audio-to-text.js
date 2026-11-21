(()=>{'use strict';
const root=document.getElementById('a2t-tool');
if(!root)return;
const $=id=>document.getElementById(id);
const form=$('upload-form'),fileInput=$('audio-file'),drop=$('drop-zone'),clear=$('clear-file'),summary=$('file-summary'),editor=$('transcript');
const status=$('status-line'),editorStatus=$('editor-status'),btn=$('transcribe-btn'),label=$('transcribe-label');
const maxBytes=24000000,extensions=new Set(['mp3','wav','m4a','flac','ogg','mp4','webm']);
let selected=null,ready=false,running=false;
const relativePath=location.pathname.replace(/index\.html$/i,'').replace(/\/?$/,'/');
const api=relativePath+'api/';
function showStatus(el,s,type=''){el.hidden=!s;el.className='status-line'+(el===editorStatus?' editor-status':'')+(type?' '+type:'');el.textContent=s||'';}
function message(s,type){showStatus(status,s,type);}
function editorMessage(s,type){showStatus(editorStatus,s,type);}
function updateButton(){btn.disabled=!ready||!selected||running;label.textContent=running?'Transcribing…':'Transcribe audio';btn.setAttribute('aria-busy',String(running));}
function readable(bytes){return (bytes/1048576).toFixed(2)+' MB';}
function choose(file){
 if(!file)return;
 const ext=(file.name.split('.').pop()||'').toLowerCase();
 if(!extensions.has(ext)){message('Unsupported file type. Choose MP3, WAV, M4A, FLAC, OGG, MP4 or completed WebM.','error');return;}
 if(file.size>maxBytes){message('File exceeds the 24 MB limit. Please use a smaller file.','error');return;}
 if(file.size<128){message('This file is too small to be a valid audio recording.','error');return;}
 selected=file;
 $('file-name').textContent=file.name;$('file-size').textContent=readable(file.size);summary.hidden=false;
 $('drop-title').textContent='Change audio file';if(ready)message('');updateButton();
}
drop.addEventListener('click',()=>fileInput.click());
drop.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();fileInput.click();}});
fileInput.addEventListener('change',()=>choose(fileInput.files&&fileInput.files[0]));
clear.addEventListener('click',()=>{selected=null;fileInput.value='';summary.hidden=true;$('drop-title').textContent='Choose an audio file';if(ready)message('');updateButton();});
['dragenter','dragover'].forEach(name=>drop.addEventListener(name,e=>{e.preventDefault();drop.classList.add('dragging');}));
['dragleave','dragend','drop'].forEach(name=>drop.addEventListener(name,e=>{e.preventDefault();drop.classList.remove('dragging');}));
drop.addEventListener('drop',e=>choose(e.dataTransfer&&e.dataTransfer.files&&e.dataTransfer.files[0]));
function words(){const s=editor.value.trim();$('word-count').textContent=(s?s.split(/\s+/u).length:0)+' words';}
editor.addEventListener('input',()=>{words();editorMessage('');});words();
async function responseJSON(resp){const raw=await resp.text();try{return JSON.parse(raw);}catch(e){return {detail:'Unexpected server response. Please try again later.'};}}
function humanError(code,detail){if(code===429)return 'Daily allowance or shared budget reached. Please try another day.';if(code===413)return 'File is too large. Please use a recording under 24 MB.';if(code===422)return (typeof detail==='string'?detail:'Could not verify this audio file.');if(code===403)return 'Request blocked by the service safety policy.';if(code===503)return 'Transcription is temporarily unavailable.';return 'Transcription could not finish. Please try again later.';}
async function check(){
 try{
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),10000);
  const resp=await fetch(api+'config',{credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{'Accept':'application/json'}});
  clearTimeout(timeout);
  const data=await responseJSON(resp);
  ready=resp.ok&&data.live_api_ready===true&&data.ai_paused===false;
  if(ready)message('');else message('Transcription is temporarily unavailable.','error');
 }catch(e){ready=false;message('Transcription is temporarily unavailable.','error');}
 updateButton();
}
form.addEventListener('submit',async e=>{
 e.preventDefault();if(!ready||!selected||running)return;
 running=true;updateButton();message('Processing audio. Keep this tab open.');
 const payload=new FormData();payload.append('file',selected,selected.name);payload.append('language',$('language').value);
 payload.append('keywords',$('keywords').value.trim());payload.append('context',$('context').value.trim());
 try{
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),175000);
  const resp=await fetch(api+'transcribe',{method:'POST',body:payload,credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{'Accept':'application/json'}});
  clearTimeout(timer);
  const data=await responseJSON(resp);
  if(!resp.ok)throw {code:resp.status,detail:data.detail||data.error};
  if(typeof data.text!=='string'||!data.text.trim())throw {code:502,detail:'Empty transcript'};
  editor.value=data.text;words();message('Transcript ready.','success');
  editor.focus({preventScroll:true});
 }catch(err){
  if(err&&err.name==='AbortError')message('Request timed out. Do not retry immediately; the server may still be processing it.','error');
  else message(humanError(err&&err.code,err&&err.detail),'error');
 }finally{running=false;updateButton();}
});
function download(data,name,type){const blob=new Blob([data],{type:type});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=name;a.hidden=true;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);}
$('copy-btn').addEventListener('click',async()=>{if(!editor.value.trim()){editorMessage('Enter text first.','error');return;}try{await navigator.clipboard.writeText(editor.value);editorMessage('Copied.','success');}catch(e){editor.select();editorMessage('Select and copy the text.');}});
$('txt-btn').addEventListener('click',()=>{if(!editor.value.trim()){editorMessage('Enter text first.','error');return;}download(editor.value,'motricialy-audio-transcript.txt','text/plain;charset=utf-8');editorMessage('TXT download started.','success');});
$('docx-btn').addEventListener('click',async()=>{if(!editor.value.trim()){editorMessage('Enter text first.','error');return;}
 if(new TextEncoder().encode(editor.value).length>80000){editorMessage('DOCX limit: 80 KB of text.','error');return;}
 try{editorMessage('Preparing DOCX…');const resp=await fetch(api+'export/docx',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','Accept':'application/vnd.openxmlformats-officedocument.wordprocessingml.document'},body:JSON.stringify({title:'Audio Transcript',text:editor.value})});
 if(!resp.ok){const d=await responseJSON(resp);throw {code:resp.status,detail:d.detail};}
 const blob=await resp.blob();if(blob.size<300)throw {code:503};download(blob,'motricialy-audio-transcript.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document');editorMessage('DOCX download started.','success');
 }catch(e){editorMessage(humanError(e&&e.code,e&&e.detail),'error');}});
check();
})();