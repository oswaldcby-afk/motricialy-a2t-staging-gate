// Research only. No I/O, models, telemetry, or production imports.
const numeric = new Map(Object.entries({zero:0,one:1,two:2,three:3,four:4,five:5,six:6,seven:7,eight:8,nine:9,ten:10,eleven:11,twelve:12,thirteen:13,fourteen:14,fifteen:15,sixteen:16,seventeen:17,eighteen:18,nineteen:19,twenty:20,thirty:30,forty:40,fifty:50,sixty:60,seventy:70,eighty:80,ninety:90}));
export function tokenize(text) {
  if (typeof text !== 'string' || text.length > 80000) throw new Error('invalid_text');
  // Preserve UTF-16 offsets into the authoritative original, including punctuation/gaps.
  // Han characters are MATCHING UNITS, not a claim of Chinese word boundaries.
  const pattern = /[\p{Script=Han}]|(?:[+\-−＋－]\s*)?[.．]?\p{N}+(?:[.,:．，：]\p{N}+)*(?:\s*[%％]|(?:(?!\p{Script=Han})[\p{L}])+)?|(?:(?!\p{Script=Han})[\p{L}\p{M}\p{N}])+(?:['’\-](?:(?!\p{Script=Han})[\p{L}\p{M}\p{N}])+)*/gu;
  return [...text.matchAll(pattern)].map(m => {
    const key = m[0].normalize('NFKC').toLowerCase().replaceAll('’', "'").replaceAll('−', '-');
    return { text: m[0], from: m.index, to: m.index + m[0].length, key,
      matchKey: numeric.has(key) ? String(numeric.get(key)) : key,
      numeric: numeric.has(key) || /\p{N}/u.test(key) };
  });
}
export function distance(a, b) {
  let row = Array.from({length:b.length+1}, (_, i) => i);
  for (let i=1;i<=a.length;i++) {
    let diagonal=row[0]; row[0]=i;
    for(let j=1;j<=b.length;j++) {
      const above=row[j]; row[j]=Math.min(above+1,row[j-1]+1,diagonal+(a[i-1]===b[j-1]?0:1)); diagonal=above;
    }
  }
  return row[b.length];
}
export function validInterval(item, duration) {
  return !!item && Number.isFinite(duration) && duration>0 && duration<=900 &&
    Number.isFinite(item.start) && Number.isFinite(item.end) &&
    item.start>=0 && item.end>item.start && item.end<=duration;
}
export function alignTranscript(transcript, chunks, duration, {signal}={}) {
  signal?.throwIfAborted();
  const target=tokenize(transcript);
  if(!target.length || target.length>2500) throw new Error('target_token_count_out_of_range');
  if(!Number.isFinite(duration)||duration<=0||duration>900) throw new Error('duration_out_of_bounds');
  if(!Array.isArray(chunks)||!chunks.length||chunks.length>3000) throw new Error('timestamp_chunk_count_out_of_range');
  const local=[]; let previousEnd=0;
  for(const chunk of chunks) {
    if(!chunk || typeof chunk.text!=='string') throw new Error('invalid_chunk');
    const t=chunk.timestamp ?? [chunk.start,chunk.end];
    if(!Array.isArray(t)||t.length!==2) throw new Error('invalid_time_sequence');
    const interval={start:t[0],end:t[1]};
    if(!validInterval(interval,duration)||interval.start<previousEnd) throw new Error('invalid_time_sequence');
    previousEnd=interval.end;
    const units=tokenize(chunk.text);
    // Never divide a segment/chinese multi-character chunk into fabricated word times.
    if(units.length!==1) throw new Error('non_atomic_timestamp_chunk');
    if(chunk.uncertain===true || chunk.overlap===true) throw new Error('uncertain_timestamp_chunk');
    local.push({...units[0],...interval});
  }
  const n=target.length,m=local.length,stride=m+1;
  const forward=new Uint16Array((n+1)*stride), backward=new Uint16Array((n+1)*stride);
  for(let i=0;i<=n;i++) forward[i*stride]=i;
  for(let j=0;j<=m;j++) forward[j]=j;
  for(let i=1;i<=n;i++) {
    signal?.throwIfAborted();
    for(let j=1;j<=m;j++) forward[i*stride+j]=Math.min(forward[(i-1)*stride+j]+1,forward[i*stride+j-1]+1,forward[(i-1)*stride+j-1]+(target[i-1].matchKey===local[j-1].matchKey?0:1));
  }
  for(let i=0;i<=n;i++) backward[i*stride+m]=n-i;
  for(let j=0;j<=m;j++) backward[n*stride+j]=m-j;
  for(let i=n-1;i>=0;i--) for(let j=m-1;j>=0;j--) backward[i*stride+j]=Math.min(backward[(i+1)*stride+j]+1,backward[i*stride+j+1]+1,backward[(i+1)*stride+j+1]+(target[i].matchKey===local[j].matchKey?0:1));
  const optimal=forward[n*stride+m];
  const mapped=target.map((token,i)=>{
    const choices=[]; let canBeUnmatched=false;
    // A token is safe only if EVERY minimum-edit path maps it to the same source.
    for(let j=0;j<=m;j++) {
      const prefix=forward[i*stride+j];
      if(prefix+1+backward[(i+1)*stride+j]===optimal) canBeUnmatched=true;
      if(j<m) {
        const equal=token.matchKey===local[j].matchKey;
        if(prefix+(equal?0:1)+backward[(i+1)*stride+j+1]===optimal) {
          if(equal) choices.push(j); else canBeUnmatched=true;
        }
      }
    }
    if(canBeUnmatched||choices.length!==1) return null;
    const source=local[choices[0]];
    return {text:token.text,from:token.from,to:token.to,start:source.start,end:source.end,sourceIndex:choices[0]};
  });
  const matched=mapped.filter(Boolean).length,coverage=matched/n;
  const numericMismatch=target.filter(t=>t.numeric).map(t=>t.matchKey).join('|')!==local.filter(t=>t.numeric).map(t=>t.matchKey).join('|');
  const reasons=[];
  if(coverage<.95) reasons.push('coverage_below_95_percent');
  if(matched<n) reasons.push('unmatched_or_ambiguous_units');
  if(optimal/n>.12) reasons.push('excessive_normalized_edits');
  if(numericMismatch) reasons.push('number_sequence_mismatch');
  return {transcript,duration,mappedWords:mapped,coverage,matchedUnits:matched,totalUnits:n,
    lexicalErrorRate:distance(target.map(t=>t.key),local.map(t=>t.key))/n,
    numericAwareEditRate:optimal/n,unitDefinition:'Han character / lexical token; not Chinese word WER',
    disposition:reasons.length?'REJECT_TIMESTAMPS':'REVIEWABLE_DRAFT_ONLY',reasons,
    label:'Draft timings — review before export',source:'local_second_pass_not_forced_alignment'};
}
