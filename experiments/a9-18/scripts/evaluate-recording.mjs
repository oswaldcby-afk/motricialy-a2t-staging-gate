import fs from 'node:fs';
import {alignTranscript} from '../src/alignment.mjs';
import {scoreTiming,scoreDiarization} from '../src/metrics.mjs';
// Local private input only. Never logs transcript, filename, speaker names, or raw exceptions.
// Usage: node evaluate-recording.mjs PRIVATE_CASE.json > PRIVATE_METRICS.json
try {
  if(process.argv.length!==3) throw new Error();
  const input=JSON.parse(fs.readFileSync(process.argv[2],'utf8').replace(/^\uFEFF/,''));
  const aligned=alignTranscript(input.transcript,input.chunks,input.duration);
  const timing=scoreTiming(input.referenceWords,aligned.mappedWords,input.duration);
  const speakers=scoreDiarization(input.referenceTurns,input.hypothesisTurns,input.duration);
  delete speakers.mapping;
  console.log(JSON.stringify({scope:'single recording; requires independently reviewed provenance and human listening',
    alignment:{disposition:aligned.disposition,coverage:aligned.coverage,lexicalErrorRate:aligned.lexicalErrorRate,numericAwareEditRate:aligned.numericAwareEditRate,reasons:aligned.reasons},
    timing,diarization:speakers,releaseVerdict:'NOT_EVALUATED'},null,2));
} catch {console.error('INVALID_PRIVATE_EVALUATION_INPUT');process.exitCode=1;}
