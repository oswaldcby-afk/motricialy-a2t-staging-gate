import test from 'node:test';
import assert from 'node:assert/strict';
import {scoreTiming,scoreDiarization,percentile} from '../src/metrics.mjs';
import {validateCorpus} from '../src/corpus.mjs';
const turn=(speaker,start,end)=>({speaker,start,end});
test('timing gate measures both onset and offset',()=>{
 const ref=[{text:'a',start:1,end:2}];
 assert.equal(scoreTiming(ref,[{text:'a',start:1.2,end:2.4}],4).status,'PASS');
 assert.equal(scoreTiming(ref,[{text:'a',start:1.2,end:2.6}],4).status,'FAIL');
});
test('timing nulls reduce coverage rather than disappear from denominator',()=>{
 const ref=[{text:'a',start:0,end:1},{text:'b',start:1,end:2}];
 assert.equal(scoreTiming(ref,[ref[0],null],3).coverage,.5);assert.equal(scoreTiming(ref,[null,null],3).status,'FAIL');
});
test('timing text mismatch/out-of-range not counted safe',()=>assert.equal(scoreTiming([{text:'a',start:0,end:1}],[{text:'b',start:0,end:1}],2).invalidMarkers,1));
test('nearest rank P90 and missing data',()=>{assert.equal(percentile([1,2,3,4,5,6,7,8,9,10]),9);assert.equal(percentile([]),null);});
test('DER maps anonymous labels globally',()=>{
 const ref=[turn('A',0,1),turn('B',1,2)];const hyp=[turn('Y',0,1),turn('X',1,2)];assert.equal(scoreDiarization(ref,hyp,2).der,0);
});
test('four speakers including overlap score zero after permutation',()=>{
 const ref=[turn('A',0,2),turn('B',1,3),turn('C',3,4),turn('D',4,5)];
 assert.equal(scoreDiarization(ref,ref.map(t=>({...t,speaker:'x'+t.speaker})),5).der,0);
});
test('DER overlap missing speaker uses speaker-seconds denominator',()=>{
 const s=scoreDiarization([turn('A',0,2),turn('B',1,2)],[turn('X',0,2)],3);
 assert.equal(s.referenceSpeakerSeconds,3);assert.equal(s.missedSpeechSeconds,1);assert.equal(s.der,1/3);
});
test('DER false alarms and misses separate',()=>{
 const s=scoreDiarization([turn('A',0,1)],[turn('X',1,2)],2);assert.equal(s.der,2);assert.equal(s.falseAlarmSeconds,1);assert.equal(s.missedSpeechSeconds,1);
});
test('DER confusion under speaker collapse',()=>{
 const s=scoreDiarization([turn('A',0,1),turn('B',1,2)],[turn('X',0,2)],2);assert.equal(s.confusionSeconds,1);assert.equal(s.der,.5);
});
test('DER duplicate same-speaker intervals are set union',()=>assert.equal(scoreDiarization([turn('A',0,2),turn('A',0,2)],[turn('X',0,2)],2).referenceSpeakerSeconds,2));
test('DER silence unscorable, not a free pass',()=>assert.equal(scoreDiarization([],[],2).der,null));
test('DER rejects malformed boundaries',()=>assert.throws(()=>scoreDiarization([turn('A',-1,2)],[],3),/invalid_turn/));
test('empty corpus stays blocked',()=>assert.equal(validateCorpus({recordings:[]}).status,'BLOCKED'));
test('30 synthetic recordings cannot satisfy natural gate',()=>assert.equal(validateCorpus({recordings:Array.from({length:30},(_,i)=>({id:'s-'+i,kind:'synthetic'}))}).eligibleRecordings,0));
test('DER sweep handles dense turns and shared boundaries',()=>{
 const ref=Array.from({length:2000},(_,i)=>turn('S'+(i%4),i*.1,(i+1)*.1));
 const score=scoreDiarization(ref,ref.map(t=>({...t,speaker:'H'+t.speaker})),200);
 assert.equal(score.der,0);assert(Math.abs(score.referenceSpeakerSeconds-200)<1e-8);
});
