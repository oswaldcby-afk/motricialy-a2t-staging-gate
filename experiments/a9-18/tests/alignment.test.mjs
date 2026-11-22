import test from 'node:test';
import assert from 'node:assert/strict';
import {alignTranscript,tokenize} from '../src/alignment.mjs';
const chunks=(words)=>words.map((text,i)=>({text,timestamp:[i,i+.8]}));
const align=(text,words)=>alignTranscript(text,chunks(words),100);
test('exact original preserved with offsets, whitespace and punctuation',()=>{
 const text='  Hello, WORLD!\n';const a=align(text,['hello','world']);
 assert.equal(a.transcript,text);assert.equal(a.disposition,'REVIEWABLE_DRAFT_ONLY');
 assert.deepEqual(a.mappedWords.map(x=>text.slice(x.from,x.to)),['Hello','WORLD']);
});
test('numeric equivalence does not improve raw lexical error rate',()=>{
 const a=align('Pressure is seventeen',['Pressure','is','17']);assert.equal(a.coverage,1);
 assert.equal(a.numericAwareEditRate,0);assert.equal(a.lexicalErrorRate,1/3);
});
test('wrong number rejects even above 95 percent coverage',()=>{
 const text=Array.from({length:40},(_,i)=>'word'+i);text.push('17');
 const a=align(text.join(' '),[...text.slice(0,-1),'70']);assert(a.coverage>=.95);assert(a.reasons.includes('number_sequence_mismatch'));
});
test('unmatched suffix never receives invented timing',()=>{
 const a=align('go home invented',['go','home']);assert.equal(a.mappedWords[2],null);assert.equal(a.disposition,'REJECT_TIMESTAMPS');
});
test('paraphrase fails',()=>assert.equal(align('the valve is shut',['the','valve','was','open']).disposition,'REJECT_TIMESTAMPS'));
test('repeated identical words with exact sequence are safe',()=>assert.deepEqual(align('go go go',['go','go','go']).mappedWords.map(x=>x.start),[0,1,2]));
test('missing repeated word rejects ambiguous optimal paths',()=>assert.deepEqual(align('go go',['go']).mappedWords,[null,null]));
test('extra repeated word rejects ambiguous timestamps',()=>assert.equal(align('go',['go','go']).mappedWords[0],null));
test('Han/Latin adjacent units are distinct and retain positions',()=>assert.deepEqual(tokenize('Hello北京2026').map(t=>t.text),['Hello','北','京','2026']));
test('single Han timestamps do not become Chinese word accuracy',()=>{
 const a=align('北京',['北','京']);assert.equal(a.coverage,1);assert.match(a.unitDefinition,/not Chinese word/);
});
for(const text of ['hello world','北京','']) test('non-atomic chunk rejected: '+JSON.stringify(text),()=>assert.throws(()=>align('hello',[text]),/non_atomic/));
for(const [name,time] of Object.entries({negative:[-.1,.5],zero:[0,0],nan:[0,NaN],pastEnd:[0,100.001],reversed:[1,.5],nullEnd:[0,null]})) test('invalid time '+name,()=>assert.throws(()=>alignTranscript('one',[{text:'one',timestamp:time}],100),/invalid_time/));
test('overlap fails even when starts are monotonic',()=>assert.throws(()=>alignTranscript('one two',[{text:'one',timestamp:[0,2]},{text:'two',timestamp:[1,3]}],4),/invalid_time/));
test('uncertain candidate rejected',()=>assert.throws(()=>alignTranscript('one',[{text:'one',timestamp:[0,1],uncertain:true}],4),/uncertain/));
test('punctuation containing numeric meaning is not erased',()=>{
 for(const [a,b] of [['-17','17'],['1.7','17'],['1,000','1000'],['17%','17'],['twenty-one','twenty one'],["can't",'cant']]) assert.notEqual(tokenize(a).map(x=>x.matchKey).join('|'),tokenize(b).map(x=>x.matchKey).join('|'));
});
test('bounds and pre-cancellation fail closed',()=>{
 assert.throws(()=>align('', ['a']),/target_token/);assert.throws(()=>align('a '.repeat(2501),['a']),/target_token/);
 assert.throws(()=>alignTranscript('a',chunks(['a']),901),/duration/);
 assert.throws(()=>alignTranscript('a',chunks(['a']),1,{signal:AbortSignal.abort()}),{name:'AbortError'});
});
test('numeric expansion is conservative',()=>assert.equal(align('twenty one',['21']).disposition,'REJECT_TIMESTAMPS'));
test('fullwidth numeric notation and separated signs do not become positive integers',()=>{
 for(const number of ['−17','－17','- 17','１．７','17 %']) assert.notEqual(tokenize(number).map(t=>t.matchKey).join('|'),'17');
 assert.deepEqual(tokenize('2026年').map(t=>t.text),['2026','年']);
});
test('leading-dot decimal is not a whole number',()=>{
 assert.notEqual(tokenize('.95')[0].matchKey,tokenize('95')[0].matchKey);
 assert.notEqual(tokenize('−.95')[0].matchKey,tokenize('95')[0].matchKey);
});
