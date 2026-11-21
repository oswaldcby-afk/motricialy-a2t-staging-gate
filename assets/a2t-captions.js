/* Motricialy Audio to Text: manually timed captions (no AI-generated timestamps). */
(()=>{'use strict';
const root=document.getElementById('caption-workspace'),audio=document.getElementById('audio-player');
if(!root||!audio)return;
const $=id=>document.getElementById(id),cuesEl=$('caption-cues'),status=$('caption-status');
const editor=$('transcript'),maxCues=200,maxCueLength=500;
let seq=0,cues=[];
function notify(message,error=false){status.textContent=message;status.classList.toggle('error',!!error);}
function hasMedia(){return !!audio.src&&Number.isFinite(audio.duration)&&audio.duration>0;}
function timeString(value){
 if(!Number.isFinite(value)||value<0)return '';
 let ms=Math.round(value*1000);
 const h=Math.floor(ms/3600000);ms-=h*3600000;
 const m=Math.floor(ms/60000);ms-=m*60000;
 const s=Math.floor(ms/1000);ms-=s*1000;
 return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')+'.'+String(ms).padStart(3,'0');
}
function parseTime(input){
 const s=input.trim();
 const match=/^(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)(?:[.,](\d{1,3}))?$/.exec(s);
 if(!match)return null;
 const h=Number(match[1]||0),m=Number(match[2]),sec=Number(match[3]);
 const millis=Number((match[4]||'0').padEnd(3,'0'));
 const time=h*3600+m*60+sec+millis/1000;
 return Number.isFinite(time)&&time>=0&&time<=900?time:null;
}
function textButton(label,className,fn){
 const b=document.createElement('button');
 b.type='button';b.textContent=label;b.className=className;b.addEventListener('click',fn);
 return b;
}
function inputFor(label,value,placeholder,onChange){
 const wrap=document.createElement('label');
 wrap.className='cue-field';
 const name=document.createElement('span');name.textContent=label;
 const input=document.createElement('input');input.type='text';
 input.inputMode='decimal';input.value=value;input.placeholder=placeholder;
 input.autocomplete='off';input.spellcheck=false;
 input.addEventListener('input',()=>{onChange(input.value);status.textContent='';});
 wrap.append(name,input);return {wrap,input};
}
function render(){
 cuesEl.replaceChildren();
 if(!cues.length){
  const empty=document.createElement('p');empty.className='cue-empty';
  empty.textContent='No captions yet. Add a cue or split your transcript into untimed captions.';
  cuesEl.append(empty);
  return;
 }
 cues.forEach((cue,index)=>{
  const article=document.createElement('article');article.className='cue-item';
  const head=document.createElement('div');head.className='cue-head';
  const heading=document.createElement('strong');heading.textContent='Caption '+(index+1);
  const btnRemove=textButton('Remove','cue-remove',()=>{cues=cues.filter(x=>x.id!==cue.id);render();notify('Caption removed.');});
  head.append(heading,btnRemove);
  const times=document.createElement('div');times.className='cue-times';
  const start=inputFor('Start',cue.start===null?'':timeString(cue.start),'00:00:00.000',text=>{cue.start=parseTime(text);});
  const end=inputFor('End',cue.end===null?'':timeString(cue.end),'00:00:00.000',text=>{cue.end=parseTime(text);});
  times.append(start.wrap,end.wrap);
  const buttons=document.createElement('div');buttons.className='cue-timing-buttons';
  buttons.append(
   textButton('Mark start','cue-mark',()=>mark(cue,'start')),
   textButton('Mark end','cue-mark',()=>mark(cue,'end')),
   textButton('Play cue','cue-play',()=>seek(cue))
  );
  const box=document.createElement('div');box.className='cue-text-row';
  const textLabel=document.createElement('label');textLabel.className='cue-field';
  const textName=document.createElement('span');textName.textContent='Caption text';
  const textArea=document.createElement('textarea');textArea.value=cue.text;
  textArea.rows=2;textArea.maxLength=maxCueLength;
  textArea.setAttribute('aria-label','Caption '+(index+1)+' text');
  textArea.addEventListener('input',()=>{cue.text=textArea.value;notify('');});
  textLabel.append(textName,textArea);
  const speakerLabel=document.createElement('label');speakerLabel.className='cue-field cue-speaker';
  const speakerName=document.createElement('span');speakerName.textContent='Speaker (optional)';
  const speaker=document.createElement('select');
  ['','Speaker 1','Speaker 2','Speaker 3','Speaker 4'].forEach(value=>{
   const opt=document.createElement('option');opt.value=value;opt.textContent=value||'No label';speaker.append(opt);
  });
  speaker.value=cue.speaker;
  speaker.addEventListener('change',()=>{cue.speaker=speaker.value;notify('');});
  speakerLabel.append(speakerName,speaker);
  box.append(textLabel,speakerLabel);
  article.append(head,times,buttons,box);
  cuesEl.append(article);
 });
}
function mark(cue,which){
 if(!hasMedia()){notify('Select an audio recording and use the player to mark a time.',true);return;}
 cue[which]=Math.round(audio.currentTime*1000)/1000;
 render();notify('Marked '+which+' at '+timeString(cue[which])+'.');
}
function seek(cue){
 if(!hasMedia()){notify('Choose an audio recording first.',true);return;}
 if(cue.start===null){notify('Enter or mark a valid start time first.',true);return;}
 audio.currentTime=Math.min(Math.max(0,cue.start),audio.duration);
 const played=audio.play();if(played&&played.catch)played.catch(()=>{});
 notify('Playing from '+timeString(cue.start)+'.');
}
function createCue(text=''){
 if(cues.length>=maxCues){notify('Maximum 200 captions. Remove a cue before adding more.',true);return;}
 cues.push({id:++seq,start:hasMedia()?Math.round(audio.currentTime*1000)/1000:null,end:null,text,speaker:''});
 render();notify('Caption added. Mark the start and end times before exporting.');
}
function splitTranscript(){
 if(cues.length){notify('Remove existing cues before splitting the transcript again.',true);return;}
 const txt=editor.value.trim().replace(/\r/g,'');
 if(!txt){notify('Add transcript text first.',true);return;}
 const paragraphs=txt.split(/\n+/u).map(x=>x.trim()).filter(Boolean);
 const chunks=[];
 for(const paragraph of paragraphs){
  const segments=paragraph.match(/[^.!?。！？]+[.!?。！？]?/gu)||[paragraph];
  for(const segment of segments){
   const value=segment.trim();if(!value)continue;
   if(value.length<=maxCueLength)chunks.push(value);
   else{
    const words=value.split(/\s+/u);
    if(words.length===1)for(let i=0;i<value.length;i+=maxCueLength)chunks.push(value.slice(i,i+maxCueLength));
    else{
     let part='';
     for(const w of words){
      if(part&&part.length+w.length+1>maxCueLength){chunks.push(part);part='';}
      if(w.length>maxCueLength){
       if(part){chunks.push(part);part='';}
       for(let j=0;j<w.length;j+=maxCueLength)chunks.push(w.slice(j,j+maxCueLength));
      }else part+=(part?' ':'')+w;
     }
     if(part)chunks.push(part);
    }
   }
   if(chunks.length>maxCues){notify('Too many caption segments. Shorten the transcript before splitting.',true);return;}
  }
 }
 if(!chunks.length){notify('No text could be split into captions.',true);return;}
 cues=chunks.map(text=>({id:++seq,start:null,end:null,text,speaker:''}));
 render();notify(chunks.length+' captions created without timestamps. Mark each start and end manually.');
}
function validate(){
 if(!cues.length)throw new Error('Add at least one caption first.');
 const sorted=cues.map((c,i)=>({...c,position:i+1})).sort((a,b)=>(a.start??Infinity)-(b.start??Infinity));
 let previousEnd=-1,total=0;
 for(const cue of sorted){
  if(cue.start===null||cue.end===null)throw new Error('Caption '+cue.position+': mark both start and end times.');
  if(cue.end<=cue.start)throw new Error('Caption '+cue.position+': end must be after start.');
  if(cue.start<previousEnd-0.001)throw new Error('Captions overlap. Adjust times before exporting.');
  if(hasMedia()&&cue.end>audio.duration+0.2)throw new Error('Caption '+cue.position+': end exceeds the audio duration.');
  if(!cue.text.trim())throw new Error('Caption '+cue.position+': add caption text.');
  if(cue.text.length>maxCueLength)throw new Error('Caption '+cue.position+': text is too long.');
  previousEnd=cue.end;total+=cue.text.length;
 }
 if(total>80000)throw new Error('Caption content exceeds 80,000 characters.');
 return sorted;
}
function buildContent(type){
 const sorted=validate();
 const eol='\r\n',pieces=type==='vtt'?['WEBVTT','']:[];
 sorted.forEach((cue,i)=>{
  const t1=timeString(cue.start).replace('.',type==='srt'?',':'.');
  const t2=timeString(cue.end).replace('.',type==='srt'?',':'.');
  const text=(cue.speaker?cue.speaker+': ':'')+cue.text.replace(/\r/g,'').trim();
  if(type==='srt')pieces.push(String(i+1));
  pieces.push(t1+' --> '+t2,text,'');
 });
 return pieces.join(eol)+eol;
}
function download(type){
 try{
  const data=buildContent(type),blob=new Blob([data],{type:type==='vtt'?'text/vtt;charset=utf-8':'application/x-subrip;charset=utf-8'});
  const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;
  a.download='motricialy-captions.'+type;document.body.append(a);a.click();a.remove();
  setTimeout(()=>URL.revokeObjectURL(url),30000);
  notify(type.toUpperCase()+' subtitles downloaded. Verify timing before publishing.');
 }catch(e){notify(e.message||'Could not export captions.',true);}
}
$('caption-add').addEventListener('click',()=>createCue());
$('caption-split').addEventListener('click',splitTranscript);
$('caption-srt').addEventListener('click',()=>download('srt'));
$('caption-vtt').addEventListener('click',()=>download('vtt'));
render();
})();