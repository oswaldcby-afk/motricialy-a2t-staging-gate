import {validInterval} from './alignment.mjs';
export function percentile(values, quantile=.9) {
  if(!values.length) return null;
  if(!values.every(Number.isFinite)||!Number.isFinite(quantile)||quantile<=0||quantile>1) throw new Error('invalid_percentile');
  return [...values].sort((a,b)=>a-b)[Math.ceil(quantile*values.length)-1];
}
export function scoreTiming(reference, predicted, duration) {
  if(!Array.isArray(reference)||!reference.length||!Array.isArray(predicted)||reference.length!==predicted.length) throw new Error('invalid_timing_vectors');
  let last=0;
  for(const r of reference) {
    if(!validInterval(r,duration)||r.start<last) throw new Error('invalid_reference');
    last=r.end;
  }
  const onsets=[],offsets=[]; let invalid=0,previousEnd=0,matched=0;
  predicted.forEach((p,i)=>{
    if(p===null) return;
    if(!validInterval(p,duration)||p.start<previousEnd||p.text!==reference[i].text) { invalid++; return; }
    previousEnd=p.end;matched++;
    onsets.push(Math.abs(p.start-reference[i].start));offsets.push(Math.abs(p.end-reference[i].end));
  });
  const coverage=matched/reference.length,onsetP90=percentile(onsets),offsetP90=percentile(offsets);
  return {coverage,matched,referenceUnits:reference.length,invalidMarkers:invalid,onsetP90,offsetP90,
    status:invalid===0&&coverage>=.95&&onsetP90!==null&&onsetP90<=.35&&offsetP90<=.50?'PASS':'FAIL',
    scope:'per-recording timing only; not dataset/release acceptance'};
}
// Exact interval DER; full [0,duration) scoring, zero collar, overlap included.
// Maximum eight anonymous labels per side. Global one-to-one maximum co-occurrence mapping.
export function scoreDiarization(reference, hypothesis, duration) {
  if(!Number.isFinite(duration)||duration<=0||duration>900) throw new Error('invalid_duration');
  for(const turns of [reference,hypothesis]) {
    if(!Array.isArray(turns)||turns.length>10000) throw new Error('invalid_turns');
    for(const t of turns) if(!validInterval(t,duration)||typeof t.speaker!=='string'||!t.speaker.length||t.speaker.length>80) throw new Error('invalid_turn');
  }
  const ref=[...new Set(reference.map(t=>t.speaker))],hyp=[...new Set(hypothesis.map(t=>t.speaker))];
  if(ref.length>8||hyp.length>8) throw new Error('too_many_speakers');
  // Sweep interval events instead of rescanning all turns at every boundary.
  const events=new Map([[0,[]],[duration,[]]]);
  for(const [side,turns,labels] of [[0,reference,ref],[1,hypothesis,hyp]]) for(const t of turns) {
    for(const [time,delta] of [[t.start,1],[t.end,-1]]) {
      if(!events.has(time)) events.set(time,[]);
      events.get(time).push({side,label:labels.indexOf(t.speaker),delta});
    }
  }
  const boundaries=[...events.keys()].sort((a,b)=>a-b),counts=[ref.map(()=>0),hyp.map(()=>0)];
  const cells=[],weights=ref.map(()=>hyp.map(()=>0));
  for(let k=0;k<boundaries.length-1;k++) {
    const start=boundaries[k],end=boundaries[k+1];
    for(const event of events.get(start)) counts[event.side][event.label]+=event.delta;
    const [r,h]=counts.map(side=>side.flatMap((count,index)=>count>0?[index]:[]));
    const seconds=end-start;cells.push({r,h,seconds});
    for(const ri of r) for(const hi of h) weights[ri][hi]+=seconds;
  }
  const size=Math.max(ref.length,hyp.length),memo=new Map();
  function best(row,mask) {
    if(row===size) return {value:0,pairs:[]};
    const key=row+':'+mask;if(memo.has(key)) return memo.get(key);
    let winner={value:-1,pairs:[]};
    for(let col=0;col<size;col++) if(!(mask&(1<<col))) {
      const next=best(row+1,mask|(1<<col)),value=(weights[row]?.[col]??0)+next.value;
      if(value>winner.value) winner={value,pairs:[[row,col],...next.pairs]};
    }
    memo.set(key,winner);return winner;
  }
  const pairs=best(0,0).pairs.filter(([r,h])=>r<ref.length&&h<hyp.length);
  let referenceSeconds=0,falseAlarm=0,missedSpeech=0,confusion=0;
  for(const {r,h,seconds} of cells) {
    const correct=pairs.filter(([ri,hi])=>r.includes(ri)&&h.includes(hi)).length;
    referenceSeconds+=r.length*seconds;
    falseAlarm+=Math.max(0,h.length-r.length)*seconds;
    missedSpeech+=Math.max(0,r.length-h.length)*seconds;
    confusion+=(Math.min(r.length,h.length)-correct)*seconds;
  }
  return {der:referenceSeconds?(falseAlarm+missedSpeech+confusion)/referenceSeconds:null,
    falseAlarmSeconds:falseAlarm,missedSpeechSeconds:missedSpeech,confusionSeconds:confusion,referenceSpeakerSeconds:referenceSeconds,
    mapping:pairs.map(([r,h])=>({reference:ref[r],hypothesis:hyp[h]})),collarSeconds:0,overlapIncluded:true,
    status:referenceSeconds?'SCORED_NOT_ACCEPTED':'UNSCORABLE_NO_REFERENCE_SPEECH'};
}
