/* On-device media extraction. The source video is never posted by this module. */
(()=>{'use strict';
const root=document.getElementById('video-extract-panel');
if(!root)return;
const $=id=>document.getElementById(id);
const chooser=$('video-file-input'),pick=$('video-choose'),extract=$('video-extract');
const label=$('video-file-label'),status=$('video-extract-status'),progress=$('video-progress');
const allowed=new Set(['mp4','m4v','mov','mkv','webm']);
const maxVideoBytes=500*1024*1024;
let source=null,busy=false;
function setStatus(message,error=false){status.textContent=message;status.classList.toggle('error',error);}
pick.addEventListener('click',()=>{if(!busy)chooser.click();});
chooser.addEventListener('change',()=>{
 if(busy)return;
 const file=chooser.files?.[0];source=null;extract.disabled=true;progress.hidden=true;
 if(!file){label.textContent='';setStatus('');return;}
 const ext=(file.name.split('.').pop()||'').toLowerCase();
 if(!allowed.has(ext)){setStatus('Unsupported video file. Try MP4, MOV, MKV or WebM.',true);return;}
 if(file.size<128||file.size>maxVideoBytes){setStatus('Video must be between 128 bytes and 500 MB.',true);return;}
 source=file;
 label.textContent=file.name+' · '+(file.size/1048576).toFixed(1)+' MB';
 extract.disabled=false;setStatus('Ready to extract audio locally.');
});
extract.addEventListener('click',async()=>{
 if(!source||busy)return;
 busy=true;extract.disabled=true;pick.disabled=true;progress.value=0;progress.hidden=false;
 setStatus('Preparing local audio extraction…');
 const file=source;
 try{
  const {extractVideoAudio}=await import('./video-extract.mjs');
  const result=await extractVideoAudio(file,percent=>{
   progress.value=percent;
   setStatus('Extracting audio on this device: '+percent+'%');
  });
  if(!(result.file instanceof File)||result.file.size>24_000_000)
    throw new Error('The extracted audio exceeds the upload limit.');
  // The existing file-upload flow remains the only path that can submit data.
  window.dispatchEvent(new CustomEvent('a2t:prepared-audio',{detail:{file:result.file}}));
  label.textContent=result.file.name+' · '+(result.outputBytes/1048576).toFixed(1)+' MB';
  source=null;chooser.value='';progress.value=100;
  setStatus('Audio extracted locally. Preview it, then click Transcribe.');
 }catch(e){
  progress.hidden=true;
  setStatus(e instanceof Error?e.message:'Video audio extraction failed in this browser.',true);
 }finally{busy=false;extract.disabled=!source;pick.disabled=false;}
});
})();