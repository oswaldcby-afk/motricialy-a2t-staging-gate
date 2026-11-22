import assert from 'node:assert/strict';
import fs from 'node:fs';
import {alignTranscriptWords,tokens} from '../../a9-17/align_existing_transcript.mjs';
const results={stage:'A9.17',mode:'no-cost-alignment-unit',checks:{},paid_api_calls:0};
function check(name,fn){try{fn();results.checks[name]=true;}catch(e){results.checks[name]=false;results[name+'_error']=String(e.message).slice(0,160);}}
const words=[
{text:'The',timestamp:[0.2,0.5]},
{text:' blue',timestamp:[.5,.8]},
{text:' valve',timestamp:[.8,1.1]},
{text:' is',timestamp:[1.1,1.35]},
{text:' open.',timestamp:[1.35,1.65]}];
check('punctuation_and_case_normalization',()=>assert.deepEqual(tokens('THE blue valve, OPEN.'),['the','blue','valve','open']));
check('exact_transcript_all_matched',()=>{
 const a=alignTranscriptWords('The blue valve is open.',words,3);
 assert.equal(a.disposition,'REVIEWABLE_DRAFT_ONLY');
 assert.equal(a.matchedWords,5);assert.equal(a.coverage,1);assert.equal(a.wordErrorRate,0);
 assert.equal(a.mappedWords[0].start,.2);assert.equal(a.mappedWords[4].end,1.65);
});
check('paraphrased_transcript_rejected',()=>{
 const a=alignTranscriptWords('The blue valve was shut at seven.',words,3);
 assert.equal(a.disposition,'REJECT_TIMESTAMPS');assert(a.unmatchedWords.length>0);
});
check('invented_text_does_not_get_timestamp',()=>{
 const a=alignTranscriptWords('The blue valve is open. Totally invented.',words,3);
 assert.equal(a.disposition,'REJECT_TIMESTAMPS');
 assert.equal(a.mappedWords.at(-1),null);
});
check('non_chronological_source_rejected',()=>{
 const bad=[...words];bad[2]={text:'valve',timestamp:[.3,.4]};
 assert.throws(()=>alignTranscriptWords('The blue valve is open.',bad,3),/invalid_time_sequence/);
});
check('out_of_duration_rejected',()=>{
 const bad=[...words];bad[4]={text:'open',timestamp:[3.2,3.6]};
 assert.throws(()=>alignTranscriptWords('The blue valve is open.',bad,3),/invalid_time_sequence/);
});
check('mixed_chinese_tokenization',()=>{
 assert.deepEqual(tokens('Hello 北京2026'),['hello','北','京','2026']);
});
check('number_word_and_digits_equivalent',()=>{
 const numeric=[{text:'Pressure',timestamp:[0,.3]},{text:'is',timestamp:[.3,.5]},
  {text:'17',timestamp:[.5,.9]}];
 const a=alignTranscriptWords('Pressure is seventeen',numeric,2);
 assert.equal(a.disposition,'REVIEWABLE_DRAFT_ONLY');
 assert.equal(a.coverage,1);
});
check('wrong_number_not_equivalent',()=>{
 const numeric=[{text:'Pressure',timestamp:[0,.3]},{text:'is',timestamp:[.3,.5]},
  {text:'70',timestamp:[.5,.9]}];
 const a=alignTranscriptWords('Pressure is seventeen',numeric,2);
 assert.equal(a.disposition,'REJECT_TIMESTAMPS');
 assert(a.unmatchedWords.includes('17'));
});
check('empty_original_rejected',()=>{
 assert.throws(()=>alignTranscriptWords(' ',words,3),/target_token_count/);
});
check('duplicate_word_alignment_monotonic',()=>{
 const dup=[{text:'go',timestamp:[0,.3]},{text:'go',timestamp:[.4,.7]},{text:'go',timestamp:[.8,1]}];
 const a=alignTranscriptWords('Go go go',dup,2);
 assert.equal(a.disposition,'REVIEWABLE_DRAFT_ONLY');
 assert.deepEqual(a.mappedWords.map(x=>x.start),[0,.4,.8]);
});
results.passed=Object.values(results.checks).filter(Boolean).length;
results.total=Object.keys(results.checks).length;
results.status=results.passed===results.total?'ALIGNER_QA_PASS':'ALIGNER_QA_FAILED';
fs.writeFileSync(new URL('../../../docs/a9-18-evidence/a9-17-safety.json',import.meta.url),JSON.stringify(results,null,2));
console.log(JSON.stringify({status:results.status,passed:results.passed,total:results.total,
 failures:Object.entries(results.checks).filter(([k,v])=>!v).map(([k])=>k)}));
if(results.status!=='ALIGNER_QA_PASS')process.exitCode=1;
