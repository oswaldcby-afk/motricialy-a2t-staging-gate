/* A9.17 experiment: match original GPT transcript words to local Whisper word timestamps.
   Never invent timestamps for unmatched words. No provider calls, no public deployment. */
export function tokens(text){
 if(typeof text!=='string')throw new TypeError('Text expected');
 const norm=text.normalize('NFKC').toLocaleLowerCase('en-US');
 // Han characters are individual units for conservative cross-language matching.
 return norm.match(/[\p{Script=Han}]|[\p{L}\p{N}]+/gu)||[];
}
export function alignTranscriptWords(transcript,wordChunks,durationSeconds){
 const target=tokens(transcript);
 if(target.length===0||target.length>2500)throw new Error('target_token_count_out_of_range');
 if(!Array.isArray(wordChunks)||wordChunks.length===0||wordChunks.length>3000)throw new Error('timestamp_chunk_count_out_of_range');
 if(!Number.isFinite(durationSeconds)||durationSeconds<=0||durationSeconds>900)throw new Error('duration_out_of_bounds');
 const local=[];
 let lastStart=0;
 for(const c of wordChunks){
  const t=c.timestamp??[c.start,c.end];
  if(!Array.isArray(t)||t.length!==2||!t.every(Number.isFinite)||
     t[0]<0||t[1]<t[0]||t[1]>durationSeconds+.25||t[0]<lastStart-.02)
    throw new Error('invalid_time_sequence');
  lastStart=t[0];
  const words=tokens(c.text||'');
  for(const word of words)local.push({word,start:t[0],end:t[1]});
 }
 const n=target.length,m=local.length;
 if(m<1||m>3000)throw new Error('invalid_local_tokens');
 const stride=m+1,mat=new Uint16Array((n+1)*stride);
 for(let i=0;i<=n;i++)mat[i*stride]=i;
 for(let j=0;j<=m;j++)mat[j]=j;
 for(let i=1;i<=n;i++){
  const row=i*stride,above=(i-1)*stride;
  for(let j=1;j<=m;j++){
   const cost=target[i-1]===local[j-1].word?0:1;
   mat[row+j]=Math.min(mat[above+j]+1,mat[row+j-1]+1,mat[above+j-1]+cost);
  }
 }
 const edits=mat[n*stride+m];
 const matches=new Array(n).fill(null);
 let i=n,j=m,matched=0;
 while(i>0||j>0){
  if(i>0&&j>0&&target[i-1]===local[j-1].word&&
     mat[i*stride+j]===mat[(i-1)*stride+j-1]){
   matches[i-1]={text:target[i-1],start:local[j-1].start,end:local[j-1].end};
   i--;j--;matched++;continue;
  }
  if(i>0&&j>0&&mat[i*stride+j]===mat[(i-1)*stride+j-1]+1){i--;j--;continue;}
  if(i>0&&mat[i*stride+j]===mat[(i-1)*stride+j]+1){i--;continue;}
  if(j>0&&mat[i*stride+j]===mat[i*stride+j-1]+1){j--;continue;}
  throw new Error('alignment_backtrace_failed');
 }
 const wordErrorRate=edits/n,coverage=matched/n;
 let reversals=0,prev=0;
 for(const x of matches)if(x){if(x.start<prev-.02)reversals++;prev=x.start;}
 const disposition=(coverage>=.94&&wordErrorRate<=.12&&reversals===0)?
   'REVIEWABLE_DRAFT_ONLY':'REJECT_TIMESTAMPS';
 return {disposition,
  matchedWords:matched,originalWords:n,localWords:m,
  coverage:+coverage.toFixed(4),wordErrorRate:+wordErrorRate.toFixed(4),
  chronologicalViolations:reversals,unmatchedWords:target.filter((_,k)=>matches[k]===null),
  mappedWords:matches,
  source:'local_second_pass_not_forced_alignment',
  caveat:'Whisper text may differ from the GPT transcript; unmatched words have no timestamp.'};
}
