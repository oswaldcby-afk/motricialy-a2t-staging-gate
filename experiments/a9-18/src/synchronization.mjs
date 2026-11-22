import {validInterval} from './alignment.mjs';
export function createTimeline(items,duration) {
  if(!Number.isFinite(duration)||duration<=0||duration>900||!Array.isArray(items)||items.length>3000) throw new Error('invalid_timeline');
  let last=0;
  const accepted=[];
  for(let index=0;index<items.length;index++) {
    const item=items[index];
    if(item===null) continue;
    if(!validInterval(item,duration)||item.start<last) throw new Error('invalid_timeline_interval');
    last=item.end;accepted.push(Object.freeze({...item,index}));
  }
  return Object.freeze({duration,items:Object.freeze(accepted)});
}
export function activeAt(timeline,time) {
  if(!Number.isFinite(time)||time<0||time>=timeline.duration) return null;
  return timeline.items.find(t=>t.start<=time&&time<t.end)?.index??null;
}
export function seekTo(timeline,index) { return timeline.items.find(t=>t.index===index)?.start??null; }
// Leaves manual cues under their existing owner. No playback/autoplay or upload on construction.
export function bindPlayback(audio,timeline,onHighlight) {
  let disposed=false, invalidated=false;
  const source=audio.currentSrc || audio.src;
  const sameMedia=()=>!disposed&&!invalidated&&audio.duration===timeline.duration&&(audio.currentSrc||audio.src)===source;
  const update=()=>onHighlight(sameMedia()?activeAt(timeline,audio.currentTime):null);
  const clear=()=>onHighlight(null);
  const invalidate=()=>{invalidated=true;clear();};
  audio.addEventListener('emptied',invalidate);
  for(const event of ['timeupdate','seeking','seeked','ratechange']) audio.addEventListener(event,update);
  for(const event of ['emptied','ended','error']) audio.addEventListener(event,clear);
  return {
    seek(index) {
      const time=seekTo(timeline,index);
      if(!sameMedia()||time===null) return false;
      audio.currentTime=time;update();return true;
    },
    dispose() {
      disposed=true;audio.removeEventListener('emptied',invalidate);
      for(const event of ['timeupdate','seeking','seeked','ratechange']) audio.removeEventListener(event,update);
      for(const event of ['emptied','ended','error']) audio.removeEventListener(event,clear);
      clear();
    }
  };
}
export function splitCue(cue,time,textOffset,duration) {
  if(!validInterval(cue,duration)||!Number.isFinite(time)||time<=cue.start||time>=cue.end||typeof cue.text!=='string'||!Number.isInteger(textOffset)||textOffset<=0||textOffset>=cue.text.length||(/[\uD800-\uDBFF]/.test(cue.text[textOffset-1])&&/[\uDC00-\uDFFF]/.test(cue.text[textOffset]))) throw new Error('invalid_split');
  // Both boundaries are explicit human choices; never interpolate words.
  return [{...cue,end:time,text:cue.text.slice(0,textOffset)},{...cue,start:time,text:cue.text.slice(textOffset)}];
}
export function mergeCues(first,second,duration) {
  if(!validInterval(first,duration)||!validInterval(second,duration)||first.end!==second.start||first.speaker!==second.speaker||typeof first.text!=='string'||typeof second.text!=='string') throw new Error('incompatible_merge');
  return {...first,end:second.end,text:first.text+second.text};
}
export function relabelCue(cue,label) {
  if(label!==null && !/^Speaker [1-8]$/.test(label)) throw new Error('anonymous_label_required');
  return {...cue,speaker:label,speakerSource:'manual'};
}
export function speakerAt(cue,turns) {
  // Abstain unless one non-overlapping, explicitly trusted anonymous turn contains the entire cue.
  const overlapping=turns.filter(t=>t.start<cue.end&&t.end>cue.start);
  if(overlapping.length!==1) return {speaker:null,reason:'unknown_or_overlap'};
  const turn=overlapping[0];
  if(turn.start>cue.start||turn.end<cue.end||turn.trusted!==true||!/^Speaker [1-8]$/.test(turn.speaker)) return {speaker:null,reason:'unverified_speaker'};
  return {speaker:turn.speaker,reason:'experimental_anonymous_label_not_identity'};
}
