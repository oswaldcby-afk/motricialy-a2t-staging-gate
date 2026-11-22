import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync,spawnSync} from 'node:child_process';
import {createHash} from 'node:crypto';
import {performance} from 'node:perf_hooks';
import {alignTranscript,tokenize,distance} from '../src/alignment.mjs';
import {validateCorpus} from '../src/corpus.mjs';
const root=fileURLToPath(new URL('../../../',import.meta.url)),out=path.join(root,'docs/a9-18-evidence');
fs.mkdirSync(out,{recursive:true});
const sha=b=>createHash('sha256').update(b).digest('hex');
const baseline='b1ac40ab66775e9b1bf1b8944b2d465fecdd97f2';
const baselineFiles=execFileSync('git',['ls-tree','-r','--name-only',baseline],{cwd:root,encoding:'utf8'}).trim().split(/\r?\n/);
const files=baselineFiles.map(name=>{
 const before=execFileSync('git',['show',baseline+':'+name],{cwd:root,maxBuffer:20*1024*1024});
 // Git blobs use LF; normalize working-tree checkout's CRLF only for tracked text.
 const after=Buffer.from(fs.readFileSync(path.join(root,name),'utf8').replaceAll('\r\n','\n'));
 return {path:name,sha256:sha(before),unchanged:sha(before)===sha(after)};
});
const html=fs.readFileSync(path.join(root,'index.html'),'utf8');
const head=html.match(/<head>([\s\S]*?)<\/head>/i)[1];
const count=s=>head.split(s).length-1;
const localAssets=[...html.matchAll(/(?:src|href)="\.\/([^"?#]+)(?:[^\"]*)"/g)].map(m=>m[1]);
const masks=['file-summary','video-file-label','keywords','context','transcript','summary-text','caption-cues'];
const checks={baselineUnchanged:files.every(f=>f.unchanged),singleH1:(html.match(/<h1\b/gi)||[]).length===1,
 canonical:count('rel="canonical"')===1&&head.includes('href="https://motricialy.com/tools/audio-to-text/"'),
 trackingOnce:count('src="/assets/motricialy-tracking.js"')===1,chromeOnce:count('src="/assets/motricialy-site-chrome.js"')===1,
 localAssetsPresent:localAssets.every(f=>fs.existsSync(path.join(root,f))),
 schemas:['SoftwareApplication','BreadcrumbList'].every(type=>[...head.matchAll(/<script type="application\/ld\+json">(.*?)<\/script>/g)].map(m=>JSON.parse(m[1])['@type']).includes(type)),
 masks:masks.every(id=>new RegExp('<[^>]+id="'+id+'"[^>]*data-clarity-mask="true"').test(html)),
 noPublicExperimentImports:!html.includes('a9-18')&&!fs.readFileSync(path.join(root,'assets/audio-to-text.js'),'utf8').includes('a9-18')};
const cases=fs.readdirSync(path.join(root,'experiments/a9-18/tests')).filter(f=>f.endsWith('.test.mjs')).map(f=>'experiments/a9-18/tests/'+f);
const tests=spawnSync(process.execPath,['--test','--test-reporter=tap',...cases],{cwd:root,encoding:'utf8'});
fs.writeFileSync(path.join(out,'unit-tests.tap'),tests.stdout+tests.stderr);
const perf=[];
for(const size of [100,1000,2500]) {
 const words=Array.from({length:size},(_,i)=>'unit'+i),chunks=words.map((text,i)=>({text,start:i*.3,end:i*.3+.2}));
 const started=performance.now();const result=alignTranscript(words.join(' '),chunks,900);
 perf.push({units:size,milliseconds:performance.now()-started,coverage:result.coverage,disposition:result.disposition});
}
const corpus=validateCorpus(JSON.parse(fs.readFileSync(path.join(root,'experiments/a9-18/fixtures/corpus-manifest.json'),'utf8').replace(/^\uFEFF/,'')));
const historical=JSON.parse(fs.readFileSync(path.join(root,'experiments/a9-18/fixtures/a9-17-base-summary.json'),'utf8').replace(/^\uFEFF/,''));
const reference='The blue valve is open. Set the pressure to seventeen. No action items were assigned.';
const replay=historical.results.map(row=>{
 const actual=tokenize(row.synthetic_text_excerpt),expected=tokenize(reference);
 return {id:row.id,lexicalErrorRate:distance(expected.map(x=>x.key),actual.map(x=>x.key))/expected.length,
 numericAwareEditRate:distance(expected.map(x=>x.matchKey),actual.map(x=>x.matchKey))/expected.length,
 maxRecordedPhraseOnsetError:Math.max(...row.anchor_onsets.map(a=>Math.abs(a.detected_start-a.reference_start))),
 inferenceReproduced:false,wordOffsetsReproduced:false};
});
const report={stage:'A9.18',node:process.version,platform:process.platform,baseline,checks,files,
 unitTestsPassed:tests.status===0,performance:{scope:'desktop JS aligner only, not model inference or phones',cases:perf},historicalReplay:replay,corpus,
 paidApiCalls:0,productionChanged:false,releaseVerdict:'BLOCKED',blockers:['natural_ground_truth','physical_iphone_android','private_backend_source','model_artifacts_and_original_a9_17_audio']};
fs.writeFileSync(path.join(out,'verification.json'),JSON.stringify(report,null,2)+'\n');
console.log(JSON.stringify({checks,unitTestsPassed:report.unitTestsPassed,performance:perf,corpus:corpus.status,releaseVerdict:report.releaseVerdict}));
if(tests.status!==0||Object.values(checks).some(v=>!v))process.exitCode=1;
