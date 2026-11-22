import test from 'node:test';
import assert from 'node:assert/strict';
import {createTimeline,activeAt,seekTo,bindPlayback,splitCue,mergeCues,relabelCue,speakerAt} from '../src/synchronization.mjs';
import {createDraftSession} from '../src/draft-session.mjs';
const items=[{text:'a ',start:0,end:1},null,{text:'b',start:2,end:3}],timeline=createTimeline(items,4);
test('half-open highlights and pauses',()=>assert.deepEqual([0,.9,1,1.5,2,3,4,NaN].map(t=>activeAt(timeline,t)),[0,0,null,null,2,null,null,null]));
test('unmatched and invalid index cannot seek',()=>{assert.equal(seekTo(timeline,1),null);assert.equal(seekTo(timeline,2),2);assert.equal(seekTo(timeline,99),null);});
test('timeline snapshots do not follow input mutations',()=>{const x=[{start:0,end:1}];const t=createTimeline(x,2);x[0].start=1;assert.equal(seekTo(t,0),0);});
test('manual timeline independent of model and no automatic playing',()=>{
 const audio=new EventTarget();audio.currentTime=0;audio.duration=4;let active=null;
 const sync=bindPlayback(audio,timeline,x=>active=x);assert(sync.seek(2));assert.equal(audio.currentTime,2);assert.equal(active,2);
 audio.currentTime=3;audio.dispatchEvent(new Event('timeupdate'));assert.equal(active,null);sync.dispose();assert(!sync.seek(0));
});
test('media replacement invalidates seeks',()=>{
 const audio=new EventTarget();audio.currentTime=0;audio.duration=5;const sync=bindPlayback(audio,timeline,()=>{});assert(!sync.seek(0));sync.dispose();
});
test('split and merge require explicit human boundaries and preserve text',()=>{
 const cue={text:'Hello world',start:0,end:3,speaker:null};const [a,b]=splitCue(cue,1,6,4);assert.deepEqual(mergeCues(a,b,4),cue);
 assert.throws(()=>splitCue(cue,0,5,4),/invalid_split/);assert.throws(()=>mergeCues(a,{...b,speaker:'Speaker 2'},4),/incompatible/);
});
test('speaker defaults unknown for overlap, low trust or names',()=>{
 const cue=items[0];assert.equal(speakerAt(cue,[]).speaker,null);
 assert.equal(speakerAt(cue,[{start:0,end:2,speaker:'Alice',trusted:true}]).speaker,null);
 assert.equal(speakerAt(cue,[{start:0,end:2,speaker:'Speaker 1',trusted:false}]).speaker,null);
 assert.equal(speakerAt(cue,[{start:0,end:2,speaker:'Speaker 1',trusted:true}]).speaker,'Speaker 1');
 assert.equal(relabelCue(cue,null).speaker,null);assert.throws(()=>relabelCue(cue,'Alice'),/anonymous/);
});
test('session is opt-in; no worker before user action',async()=>{
 let created=0;const session=createDraftSession({workerFactory:()=>{created++;}});
 await assert.rejects(session.start({optIn:false}),/opt_in/);assert.equal(created,0);
});
test('cancel terminates worker and ignores late results',async()=>{
 const workers=[];const session=createDraftSession({workerFactory:()=>{const w={postMessage(data){this.data=data;},terminate(){this.terminated=true;}};workers.push(w);return w;}});
 const first=session.start({optIn:true,transcript:'a'});const rejected=assert.rejects(first,{name:'AbortError'});session.cancel();await rejected;assert(workers[0].terminated);
 const second=session.start({optIn:true,transcript:'b'});
 workers[0].onmessage({data:{id:workers[0].data.id,result:'stale'}});
 workers[1].onmessage({data:{id:workers[1].data.id,result:'fresh'}});assert.equal(await second,'fresh');
});
test('worker error sanitized and terminated',async()=>{
 let w;const session=createDraftSession({workerFactory:()=>w={postMessage(){},terminate(){this.terminated=true;}}});
 const p=session.start({optIn:true});w.onerror(new Error('private transcript'));await assert.rejects(p,/alignment_worker_failed/);assert(w.terminated);
});
test('same-duration media replacement clears stale highlights',()=>{
 const audio=new EventTarget();audio.currentTime=0;audio.duration=4;audio.src='blob:first';let active=null;
 const sync=bindPlayback(audio,timeline,x=>active=x);assert(sync.seek(0));
 audio.src='blob:second';audio.dispatchEvent(new Event('timeupdate'));assert.equal(active,null);assert(!sync.seek(0));
 audio.src='blob:first';audio.dispatchEvent(new Event('emptied'));assert(!sync.seek(0));sync.dispose();
});
test('empty timeline still needs valid duration and splits preserve Unicode',()=>{
 assert.throws(()=>createTimeline([],NaN),/invalid_timeline/);
 assert.throws(()=>splitCue({text:'a😀b',start:0,end:2},1,2,3),/invalid_split/);
});
